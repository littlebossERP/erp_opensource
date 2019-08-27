<?php namespace console\controllers;

use console\helpers\CommonHelper;
use eagle\modules\comment\config\CommentConfig;

use eagle\modules\comment\helpers\CommentHelper;

use eagle\modules\comment\models\CmCommentLog;
use eagle\modules\util\helpers\ConfigHelper;
use Yii;

class CommentHelperController extends \yii\console\Controller
{

    public static $platform = ['aliexpress']; // 系统开通的好评平台
    private static $jobsVersion = ['aliexpressAutoComment' => null, 'aliexpressCommentResultSync' => null];//任务版本号
    const LOG_ID = "console-controllers-commentHelpler";

    /**
     * 发送好评脚本  1天1次
     * @return [type] [description]
     */
    public function actionRun()
    {
        //Step 0, 检查当前本job版本，如果和自己的版本不一样，就自动exit吧
        $this->checkVersion("AutoComment", "aliexpressAutoComment");

        foreach (self::$platform as $platform) {    // 平台遍历
            $this->switchSubDb($platform, function ($puid) use ($platform) { // 切子库
                // 查询匹配
                $rules = CommentHelper::getRulesByPlatform($platform, $puid);
                foreach ($rules as $rule) {
                    $rule->filter();
                    $orders = CommentHelper::matchOrdersFromRules($rule);
                    // 进行好评！
                    foreach ($orders as $order) {
                        $log = CommentHelper::evaluation($order, CommentConfig::DEFAULT_RANK, $rule->content, $order->selleruserid);
                        // 写入rule_id
                        CommentHelper::setRuleIdtoLog($log['log_id'], $rule->id);
                    }
                }
            });
        }
    }

    /**
     * 发送好评，1个小时内可以进行多次运行
     */
    public function actionV2Run()
    {
        $startRunTime = time();
        do {
            $this->localLog("start run");
            $this->actionRun();
            //如果没有需要handle的request了，sleep 100s后再试
            $this->localLog("sleep 100s");
            sleep(100);
        } while (time() < $startRunTime + 3600);
    }

    public function actionLogTest()
    {
        $this->localLog("log test ");
    }

    private function checkVersion($actionKey, $jobKey)
    {
        $redisKey = "CommentHelper:" . $actionKey . ":" . $jobKey;
        $currentVersion = ConfigHelper::getGlobalConfig($redisKey, 'NO_CACHE');
        if (empty($currentVersion)) {
            $currentVersion = 0;
        }
        //如果自己还没有定义，去使用global config来初始化自己
        if (empty(self::$jobsVersion[$jobKey])) {
            self::$jobsVersion[$jobKey] = $currentVersion;
        }
        //如果自己的version不同于全局要求的version，自己退出，从新起job来使用新代码
        if (self::$jobsVersion[$jobKey] <> $currentVersion) {
            exit("Version new $currentVersion , this job ver " . self::$jobsVersion[$jobKey] . " exits for using new version $currentVersion.");
        }
    }


    
    /**
     * 获取好评结果脚本  1小时1次
     * @return [type] [description]
     */
    public function actionGetresult()
    {
        $this->checkVersion("Getresult", "aliexpressCommentResultSync");
        foreach (self::$platform as $platform) {    // 平台遍历
            $this->switchSubDb($platform, function ($puid) use ($platform) { // 切子库
                // 查询上次发送结果
                $logs = CmCommentLog::find()
                    ->where([
                        'is_success' => CmCommentLog::STATUS_WAIT
                    ]);
                foreach ($logs->all() as $log) {
                    CommentHelper::updateLogStatus($log);
                }
            });
        }
    }

    /**
     * 获取好评结果，1个小时内可以进行多次运行
     */
    public function actionV2Getresult()
    {
        $startRunTime = time();
        do {
            self::localLog("start v2_getresult");
            $this->actionGetresult();
            //如果没有需要handle的request了，sleep 100s后再试
            self::localLog("sleep 100s before restart get v2_getresult");
            sleep(100);
        } while (time() < $startRunTime + 3600);
    }

    public function actionTest()
    {
//		self::log('123'.PHP_EOL.'newline');
//		echo "cool";
        $str = 'jobsVersion';
        echo empty(self::$jobsVersion['aliexpressAutoComment']);
    }


    protected function switchSubDb($platform, $callback)
    {
        // 获取平台用户列表
        $model = '\eagle\models\Saas' . ucfirst($platform) . 'User';
        $users = $model::find()
            ->select('uid')->distinct()
            ->where(['is_active' => 1]);
        foreach ($users->all() as $user) {
            // 切换数据库
            if (true) {
                $this->localLog('-- changeUserDataBase success: ' . $user->uid);
                $callback($user->uid);
            } else {
                $this->localLog('!! changeUserDataBase fail: ' . $user->uid);
            }
        }
    }

    /**
     * 记录这个类中的日志
     * @param $msg log info
     *
     */
    private function localLog($msg)
    {
        CommonHelper::log($msg, self::LOG_ID);
    }

    /**
     * 纠正好评失败的回写状态
     * @return [type] [description]
     */
    public function actionUpdateOrderCmStatus()
    {
        // 获取速卖通用户列表
        $Users = \eagle\models\SaasAliexpressUser::find();
        foreach ($Users->each() as $user) {
            $this->updateCmOrderStatus($user->uid);
        }
    }

    private function updateCmOrderStatus($uid)
    {
        if (true) {
            self::localLog('change db success' . $uid);
            $Logs = CmCommentLog::find()
                ->where([
                    'is_success' => 0,
                ])
                ->groupBy('order_source_order_id');
            self::localLog('total: ' . $Logs->count());
            foreach ($Logs->each() as $log) {
                if ($order = \eagle\modules\order\models\OdOrder::find()
                    ->where(['order_source_order_id' => $log->order_source_order_id])->one()
                ) {
                    $order->is_comment_status = 0;
                    if ($order->save()) {
                        self::localLog($order->order_source_order_id . ' success');
                    }
                }
            }
        } else {
            self::localLog('change db fail' . $uid);
        }
    }
    public static function log($output = '', $logId = '', $type = 'info')
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

}