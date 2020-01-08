<?php
/**
 * Created by PhpStorm.
 * User: vizewang
 * Date: 16/5/19
 * Time: 下午3:25
 */

namespace console\helpers;


use common\mongo\lljListing\LLJListingDBConfig;
use common\mongo\lljListing\LLJListingManagerFactory;
use common\mongo\lljListing\RawResponseDate;
use eagle\components\OpenApi;
use eagle\modules\comment\config\CommentConfig;
use eagle\modules\util\helpers\ConfigHelper;
use eagle\models\UserDatabase;

class CommonHelper
{

    private static $INSTANCE_MAP = array();

    /**
     * @param string $output 记录及输出信息
     * @param string $logId 记录标识
     * @param string $type 记录所属类型 error,info
     * error 会保存到eagle_error.log.*; info 会保存到eagle.log.*
     * @return bool
     */
    public static function log($output = '', $logId = 'console-helpers-CommonHelper', $type = LogType::INFO)
    {
        $_response = \Yii::$app->response;
        $str = is_string($output) ? $output : print_r($output, true);
        if ((\Yii::$app->controller->module->id == 'app-console' || \Yii::$app->controller->id == 'test') && CommentConfig::LOG_IN_CONSOLE) { // 判断是否来自控制台
            if (isset($_response->format) && $_response->format == 'html') {
                echo '<pre>' . $logId . '====' . $str . '</pre>';
            } else {
                echo $logId . '====' . $str . PHP_EOL;
            }
        }
        // echo '<pre>'.$str.'</pre>';
        \Yii::$type($logId . '====' . $output, 'file');
        return true;
    }

    /**
     * 检查job在redis中是否已经存在版本号,如果没有则生成新的版本号 0,如果redis中存在的版本好与现有版本好不一致则停止运行
     * @param $controllerId
     * @param $actionId
     * @param $jobId
     * @param $jobVersion 现在运行job的版本
     */
    public static function checkVersion($controllerId, $actionId, $jobId, $jobVersion = null)
    {
        $redisKey = $controllerId . ":" . $actionId . ":" . $jobId;
        $currentVersion = ConfigHelper::getGlobalConfig($redisKey, 'NO_CACHE');
        if (empty($currentVersion)) {
            $currentVersion = 0;
        }
        //如果自己还没有定义，去使用global config来初始化自己
        if (empty($jobVersion)) {
            $jobVersion = $currentVersion;
        }
        //如果自己的version不同于全局要求的version，自己退出，从新起job来使用新代码
        if ($jobVersion <> $currentVersion) {
            exit("Version new $currentVersion , this job ver " . $jobVersion . " exits for using new version $currentVersion.".PHP_EOL);
        }
        return $jobVersion;
    }


    /**
     * 获取所有相关平台上的用户,切库执行
     * @param $platform
     * @param $callback
     */
    public static function switchSubDb($platform, $callback)
    {
        // 获取平台用户列表
        $model = '\eagle\models\Saas' . ucfirst($platform) . 'User';
        $users = $model::find()
            ->select('uid')->distinct()
            ->where(['is_active' => 1]);

        foreach ($users->all() as $user) {
            // 切换数据库
            if (true) {
//                self::log('-- changeUserDataBase success: ' . $user->uid);
                $callback($user->uid);
            } else {
//                self::log('!! changeUserDataBase fail: ' . $user->uid);
            }
        }
    }


    private static function creatInstance($fullNamespaceAndClass)
    {
        $ary_element = explode('.', $fullNamespaceAndClass);
        $fullClassName = implode('\\', $ary_element);
        $class = new \ReflectionClass($fullClassName);
        $instance = $class->newInstance();
        return $instance;
    }

    private static function dividFullMethodName($fullMethodName)
    {
        $ary_element = explode('.', $fullMethodName);
        $methodName = array_pop($ary_element);
        $fullClassName = implode('.', $ary_element);
        return array($fullClassName, $methodName);
    }

    /**
     * 按照优雅退出后台的方式启动job
     * @param $fullMethodName 空间名+类名+方法名 例如console.controllers.AutoCommentController.test
     * @param $sleepTime job完成后睡眠时间
     * @param $jobName 推荐使用action name
     * @param $version 软件的版本
     * @param int $nativeVersion 在本地中保存的版本,如果这个版本号与redis不一致,会重启进程
     * @param $controllerId
     * @param array $params 调用方法参数
     */
    public static function startJob($fullMethodName, $sleepTime, $jobName, $version, &$nativeVersion = 0, $controllerId,array $params=array())
    {
        $startRunTime = time();// dzt20170125 加上自动退出限制，方便查log
        do {
            $start = time();
            self::log($jobName . " job start... job version is " . $nativeVersion);
            $nativeVersion = CommonHelper::checkVersion($controllerId, $jobName, $version, $nativeVersion);
            list($fullClassName, $methodName) = self::dividFullMethodName($fullMethodName);
            if (!isset(self::$INSTANCE_MAP[$fullClassName])) {
                self::$INSTANCE_MAP[$fullClassName] = self::creatInstance($fullClassName);
            }
            if(isset($params)){
                self::$INSTANCE_MAP[$fullClassName]->$methodName($params);
            }else{
                self::$INSTANCE_MAP[$fullClassName]->$methodName();
            }
            $timeCost = time() - $start;
            self::log($jobName . " job sleep " . $sleepTime . "s...and time cost is " . $timeCost);
            sleep($sleepTime);
            
        } while (time() < $startRunTime+3600);
    }

    public static function insertRawDataToRawResponseData($api,$info){
        $rawRespDataManager = LLJListingManagerFactory::getManagerByStatic(LLJListingDBConfig::RAW_RESPONSE_DATA);
        $rawRespData = new RawResponseDate();
        $rawRespData->api = $api;
        $rawRespData->response = $info;
        $rawRespDataManager->insert($rawRespData);
    }
    
    /**
     * 获取是否有新机出现
     * 
     * @author		hqw		2016/07/07				初始化
     * @return boolean true 表示有新机，false 表示暂时没有新机
     */
    public static function getIsNewDbServer(){
    	$dbServerCount = UserDatabase::find()->groupBy(['dbserverid'])->count();
    	
    	//表示现时的机器数，假如添加了机器这里需要添加加了几部机器，一部机器就要$nowCount + 1
    	$nowCount = 4;
    	
    	if($dbServerCount > $nowCount){
    		return true;
    	}else{
    		return false;
    	}
    }
}