<?php
namespace console\helpers;

use \Yii;
use eagle\modules\util\helpers\ConfigHelper;
use common\api\ensogointerface\EnsogoProxyConnectHelper;
use eagle\modules\listing\models\WishFanben;
/**
+------------------------------------------------------------------------------
 * Ensogo 数据同步类
+------------------------------------------------------------------------------
 */
class EnsogoQueueHelper {

    public static $cronJobId=0;
    private static $ensogoVersion = null;
    private static $version = null;
    /**
     * @return the $cronJobId
     */
    public static function getCronJobId() {
        return self::$cronJobId;
    }

    /**
     * @param number $cronJobId
     */
    public static function setCronJobId($cronJobId) {
        self::$cronJobId = $cronJobId;
    }

    /**
     * @param string $format. output time string format
     * @param timestamp $timestamp
     * @return America/Los_Angeles formatted time string
     */
    public static function getLaFormatTime($format , $timestamp){
        $dt = new \DateTime();
        $dt->setTimestamp($timestamp);
        $dt->setTimezone(new \DateTimeZone('America/Los_Angeles'));
        return $dt->format($format);
    }

    public static function refreshAccessToekn(){
        //Step 0, 检查当前本job版本，如果和自己的版本不一样，就自动exit吧
        $currentEnsogoVersion = ConfigHelper::getGlobalConfig("ensogoRefreshToeknVersion",'NO_CACHE');
        if (empty($currentEnsogoVersion)){
            $currentEnsogoVersion = 'v1';
        }
        //如果自己还没有定义，去使用global
        if (empty(self::$ensogoVersion))
            self::$ensogoVersion = $currentEnsogoVersion;

        //如果自己的version不同于全局要求的version，自己退出，从新起job来使用新代码
        if (self::$ensogoVersion <> $currentEnsogoVersion){
            exit("Version new $currentEnsogoVersion , this job ver ".self::$ensogoVersion." exits for using new version $currentEnsogoVersion.");
        }
        $backgroundJobId=self::getCronJobId();

        \Yii::info(date('Y-m-d H:i:s')."ensogo_refresh_access_token gotit jobid=$backgroundJobId start");

        $connection = Yii::$app->db;
        $time = time() - 1800;
        $hasGotRecord = false;
        #检索access_token有效期只有 半个小时的账号信息
        $sql = "SELECT * FROM saas_ensogo_user WHERE created_at <= {$time} limit 100";
        $dataReader = $connection->createCommand($sql)->query();
        while( ($row = $dataReader->read()) !== false){
            $hasGotRecord = true;
            $params = [];
            $params['refresh_token'] = $row['refresh_token'];
            $params['puid'] = $row['uid'];
            if($row['refresh_token_number'] >= 2200 ){
                $params['new_refresh_token'] = 1;
            }

            $result = EnsogoProxyConnectHelper::call_ENSOGO_api("getAccessToken",$params);
            $result = $result['proxyResponse'];

            self::saveApiLog(['puid'=>$row['uid'],'request_info'=>json_encode($params),'result_info'=>json_encode($result)]);
            //保存访问信息
            \Yii::info(date('Y-m-d H:i:s')."ensogo_refresh_access_token " . var_export($result,true));
            if(empty($result) || $result['success'] === false){
                \Yii::info(date('Y-m-d H:i:s')."ensogo_refresh_access_token api fail error_message : " . var_export($result,true),"file");
            } else {
                \Yii::info(date('Y-m-d H:i:s')."ensogo_refresh_access_token api success","file");
                $token_info = $result['data'];
                $data_info = [
                    'created_at' => $token_info['created_at'],
                    'expires_in' => $token_info['expires_in'],
                    'token' => $token_info['access_token'],
                    'refresh_token' => $token_info['refresh_token'],
                    'refresh_token_number' => intval($row['refresh_token_number']) + 1
                ];
                if(isset($params['new_refresh_token'])){
                    $data_info['refresh_token_number'] = 0;
                    $data_info['refresh_expires_time'] = time() + (86400*180) ;
                }
                $command = $connection->createCommand()->update('saas_ensogo_user',$data_info,['site_id'=>$row['site_id']]);
                if($command->execute()){
                    \Yii::info(date('Y-m-d H:i:s')."ensogo_refresh_access_token update user success sql : {$command->getRawSql()}","file");
                } else {
                    \Yii::info(date('Y-m-d H:i:s')."ensogo_refresh_access_token update user fail sql : {$command->getRawSql()}","file");
                }
            }
        }
        return $hasGotRecord;
    }

    public static function refreshWishTag(){

        //Step 0, 检查当前本job版本，如果和自己的版本不一样，就自动exit吧
        $currentEnsogoVersion = ConfigHelper::getGlobalConfig("ensogoRefreshWishTag",'NO_CACHE');
        if (empty($currentEnsogoVersion)){
            $currentEnsogoVersion = 'v1';
        }
        //如果自己还没有定义，去使用global config来初始化自己
        if (empty(self::$ensogoVersion))
            self::$ensogoVersion = $currentEnsogoVersion;

        //如果自己的version不同于全局要求的version，自己退出，从新起job来使用新代码
        if (self::$ensogoVersion <> $currentEnsogoVersion){
            exit("Version new $currentEnsogoVersion , this job ver ".self::$ensogoVersion." exits for using new version $currentEnsogoVersion.");
        }
        $backgroundJobId=self::getCronJobId();

        \Yii::info(date('Y-m-d H:i:s')."ensogo_refresh_wish_tag gotit jobid=$backgroundJobId start");

        $connection = Yii::$app->db;
        #检索access_token有效期只有 半个小时的账号信息
        $sql = "SELECT * FROM ensogo_wish_tag_queue order by update_time desc";
        $dataReader = $connection->createCommand($sql)->query();
        while( ($row = $dataReader->read()) !== false){
            //获取ENSOGO用户的WISH账号信息
            $wish_sql = "SELECT * FROM saas_wish_user WHERE uid = ".$row['puid'];
            $userQuery = $connection->createCommand($wish_sql)->query();
            while($wish_user_info = $userQuery->read()){
                //切换至用户数据库
                $ret=true;
                $tags = [];
                echo $wish_user_info['site_id'].PHP_EOL;
                if($ret){
                    //循环WISH下所有ONLINE APPROVED 状态的商品
                    $wish_obj = WishFanben::find()->select(["tags"])->where(["and" , "site_id={$wish_user_info['site_id']}", ["or","`status`='online'","`status`='approved'"] ]);
                    $total = $wish_obj->count();
                    if($total == 0){
                        $log_message = date('Y-m-d H:i:s')."ensogo_refresh_wish_tag site_id : {$wish_user_info['site_id']} no wish product";
                        echo $log_message."\n";
                        \Yii::info($log_message,"file");
                        continue;
                    }
                    $limit = 100;
                    $pages = ceil($total/$limit);
                    for($i=1;$i<=$pages;$i++){
                        $start = ($i-1)*$limit;
                        $wish_info = $wish_obj->offset($start)->limit($limit)->asArray()->all();
                        foreach($wish_info as $key => $wish){
                            $tag = explode(',',$wish['tags']);
                            foreach($tag as $k => $v){
                                if(isset($tags[$v])){
                                    $tags[$v] = intval($tags[$v]) + 1;
                                } else {
                                    $tags[$v] = 1;
                                }
                            }
                        }
                    }
                    arsort($tags);//根据数量排序
                    $tags = array_slice($tags,0,20);
                    $tag_log = $connection->createCommand("SELECT * FROM ensogo_wish_tag_log WHERE puid={$row['puid']} and store_id = {$wish_user_info['site_id']}")->query()->read();
                    if($tag_log === false){
                        $params = [
                          "puid"=>$row['puid'],
                            "store_id"=>$wish_user_info['site_id'],
                            "tags_info"=>json_encode($tags),
                            "validity_period"=>strtotime(date("Y-m-d 23:59:59")),
                            "create_time"=>time(),
                            "update_time"=>time()
                        ];
                        $connection->createCommand()->insert("ensogo_wish_tag_log",$params)->execute();
                        $log_message = date('Y-m-d H:i:s')."ensogo_refresh_wish_tag site_id : {$wish_user_info['site_id']} create success";
                        echo $log_message."\n";
                        \Yii::info($log_message,"file");
                    } else {
                        $params = [
                            "tags_info"=>json_encode($tags),
                            "validity_period"=>strtotime(date("Y-m-d 23:59:59")),
                            "update_time"=>time()
                        ];
                        $connection->createCommand()->update("ensogo_wish_tag_log",$params," id = {$tag_log['id']}")->execute();
                        $log_message = date('Y-m-d H:i:s')."ensogo_refresh_wish_tag site_id : {$wish_user_info['site_id']} update success";
                        echo $log_message."\n";
                        \Yii::info($log_message,"file");
                    }
                } else {
                    \Yii::info(date('Y-m-d H:i:s')."ensogo_refresh_wish_tag change db error","file");
                }
            }

        }
        return false;

    }

    public function wishStoreMoveQueue(){

        //Step 0, 检查当前本job版本，如果和自己的版本不一样，就自动exit吧
        $currentEnsogoVersion = ConfigHelper::getGlobalConfig("wishStoreMoveQueue",'NO_CACHE');
        if (empty($currentEnsogoVersion)){
            $currentEnsogoVersion = 'v1';
        }
        //如果自己还没有定义，去使用global config来初始化自己
        if (empty(self::$ensogoVersion))
            self::$ensogoVersion = $currentEnsogoVersion;

        //如果自己的version不同于全局要求的version，自己退出，从新起job来使用新代码
        if (self::$ensogoVersion <> $currentEnsogoVersion){
            exit("Version new $currentEnsogoVersion , this job ver ".self::$ensogoVersion." exits for using new version $currentEnsogoVersion.");
        }
        $backgroundJobId=self::getCronJobId();

        \Yii::info(date('Y-m-d H:i:s')."wish_store_move_queue gotit jobid=$backgroundJobId start");

        $connection = Yii::$app->db;
        #检索未同步过的 搬家商品数大于100的 待搬家信息
        $sql = "SELECT * FROM ensogo_store_move_log where status = 1 and move_number > 100 order by create_time asc";
        $dataReader = $connection->createCommand($sql)->query();
        while( ($row = $dataReader->read()) !== false){

        }
        return false;
    }


    /**
     * @param $data [
     *      puid 用户ID
     *      request_info 请求参数
     *      result_info 返回参数
     * ]
     */
    public static function saveApiLog($data){
        $data['create_time'] = time();
        $data['update_time'] = time();
        $action_type = debug_backtrace()[1]['function'];
        $data['action_type'] = $action_type;
        $command = Yii::$app->db->createCommand()->insert("ensogo_api_log",$data);
        $command->execute();
        \Yii::info(date('Y-m-d H:i:s')."ensogo_refresh_access_token save_log sql : {$command->getRawSql()}","file");
    }
}
