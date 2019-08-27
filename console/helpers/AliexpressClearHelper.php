<?php
namespace console\helpers;

use eagle\modules\platform\helpers\WishAccountsHelper;
use \Yii;
use eagle\models\SaasAliexpressAutosync;
use common\api\aliexpressinterface\AliexpressInterface_Auth;
use common\api\aliexpressinterface\AliexpressInterface_Api;
use common\api\aliexpressinterface\AliexpressInterface_Helper;
use eagle\modules\util\helpers\ConfigHelper;
use eagle\modules\util\helpers\TimeUtil;
use eagle\models\SaasAliexpressUser;
use eagle\models\listing\AliexpressListing;
use eagle\modules\app\apihelpers\AppApiHelper;
use eagle\models\CheckSync;
use eagle\models\QueueAliexpressPraise;
use eagle\models\QueueAliexpressPraiseInfo;
use eagle\modules\util\helpers\UserLastActionTimeHelper;

use eagle\models\QueueAliexpressRetryAccount;
use eagle\models\QueueAliexpressRetryOrder;
use eagle\models\QueueAliexpressRetryAccountInfo;
use eagle\models\QueueAliexpressRetryAccountLog;
use eagle\modules\util\helpers\RedisHelper;

/**
+------------------------------------------------------------------------------
 * Aliexpress 数据同步类
+------------------------------------------------------------------------------
 */
class AliexpressClearHelper {

    public static $cronJobId=0;
    private static $aliexpressGetOrderListVersion = null;

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
    static function getLaFormatTime($format , $timestamp){
        $dt = new \DateTime();
        $dt->setTimestamp($timestamp);
        $dt->setTimezone(new \DateTimeZone('America/Los_Angeles'));
        return $dt->format($format);
    }

    static function saveRetryAccountInfo(&$data){
        $where = [
            'sellerloginid' => $data->sellerloginid
        ];
        $info = QueueAliexpressRetryAccountInfo::find()->where($where)->one();
        if($info === false){
            $QARAI_OBJ = new QueueAliexpressRetryAccountInfo();
            $QARAI_OBJ->uid = $data->uid;
            $QARAI_OBJ->sellerloginid = $data->sellerloginid;
            $QARAI_OBJ->orderid = $data->orderid;
            $QARAI_OBJ->times = 0;
            $QARAI_OBJ->last_time = 0;
            $QARAI_OBJ->message = '';
            $QARAI_OBJ->create_time = time();
            $QARAI_OBJ->update_time = time();
            $QARAI_OBJ->next_time = time() + 1800;//新的账号信息 半小时之后执行
            $bool = $QARAI_OBJ->save(false);
            if(!$bool){
                \Yii::info(date('Y-m-d H:i:s')."aliexpress_clear_helper save_retry_account_info error_message : " . var_export($QARAI_OBJ->errors,true),"file");
            } else {
                $bool = self::saveRetryAccount($data);
            }
        } else {
            $bool = self::saveRetryAccount($data);
        }
        return $bool;
    }

    static function saveRetryAccount(&$data){
        $QARA_OBJ = new QueueAliexpressRetryAccount();
        $QARA_OBJ->uid = $data->uid;
        $QARA_OBJ->sellerloginid = $data->sellerloginid;
        $QARA_OBJ->aliexpress_uid = $data->aliexpress_uid;
        $QARA_OBJ->status = 0;
        $QARA_OBJ->type = $data->type;
        $QARA_OBJ->order_status = $data->order_status;
        $QARA_OBJ->orderid = $data->orderid;
        $QARA_OBJ->times = 0;
        $QARA_OBJ->order_info = $data->order_info;
        $QARA_OBJ->last_time = $data->last_time;
        $QARA_OBJ->gmtcreate = $data->gmtcreate;
        $QARA_OBJ->message = $data->message;
        $QARA_OBJ->create_time = $data->create_time;
        $QARA_OBJ->update_time = $data->update_time;
        $QARA_OBJ->next_time = time() + 3600;
        $bool = $QARA_OBJ->save(false);
        if(!$bool){
            \Yii::info(date('Y-m-d H:i:s')."aliexpress_clear_helper save_retry_account error_message : " . var_export($QARA_OBJ->errors,true),"file");
        }
        return $bool;
    }

    static function saveRetryOrder($data){
        $QARO_OBJ = new QueueAliexpressRetryOrder();
        $QARO_OBJ->uid = $data->uid;
        $QARO_OBJ->sellerloginid = $data->sellerloginid;
        $QARO_OBJ->aliexpress_uid = $data->aliexpress_uid;
        $QARO_OBJ->status = 0;
        $QARO_OBJ->type = $data->type;
        $QARO_OBJ->order_status = $data->order_status;
        $QARO_OBJ->orderid = $data->orderid;
        $QARO_OBJ->times = 0;
        $QARO_OBJ->order_info = $data->order_info;
        $QARO_OBJ->last_time = $data->last_time;
        $QARO_OBJ->gmtcreate = $data->gmtcreate;
        $QARO_OBJ->message = $data->message;
        $QARO_OBJ->create_time = $data->create_time;
        $QARO_OBJ->update_time = $data->update_time;
        $QARO_OBJ->next_time = time() + 3600;
        $bool = $QARO_OBJ->save(false);
        if(!$bool){
            \Yii::info(date('Y-m-d H:i:s')."aliexpress_clear_helper save_retry_order error_message : " . var_export($QARO_OBJ->errors,true),"file");
        }
        return $bool;
    }

    private static function _saveGetOrder($row){
        unset($row['id']);
        $row['status'] = 0;
        $row['times'] = 0;
        $row['message'] = '';
        //推送至getorder表
        var_dump($row);
        $command = \Yii::$app->db_queue->createCommand()->insert('queue_aliexpress_getorder',$row);
        $bool = $command->execute();
        \Yii::info(date('Y-m-d H:i:s')." aliexpress_clear_helper  pushOrdersave_queue_aliexpress_get_order execute_sql : {$command->getRawSql()}","file");
        return $bool ? true : false;
    }

    private static function _saveAllGetOrder($sellerloginid){
        $account = \Yii::$app->db_queue->createCommand("SELECT count(id) as total FROM queue_aliexpress_retry_account WHERE sellerloginid = '{$sellerloginid}'")->query()->read();
        $limit = 50;
        $total = ceil($account['total']/$limit);

        \Yii::info(date('Y-m-d H:i:s')." aliexpress_clear_helper push_retry_account ordertotal ".var_export($account,true),"file");
        for($page = 1; $page < $total; $page++){
            $start = ($page - 1)*$limit;
            $sql = "SELECT * FROM queue_aliexpress_retry_account WHERE sellerloginid = '{$sellerloginid}' limit {$start},{$limit}";
            $account_info = \Yii::$app->db_queue->createCommand($sql)->query();
            while( ($row = $account_info->read()) !== false){
                $bool = self::_saveGetOrder($row);
                if($bool){
                    \Yii::info(date('Y-m-d H:i:s')." aliexpress_clear_helper pushOrderAll push_retry_account success orderid {$row['orderid']}","file");
                    self::_delRetryAccount($row['id']);
                } else {
                    \Yii::info(date('Y-m-d H:i:s')." aliexpress_clear_helper pushOrderAll push_retry_account fail orderid {$row['orderid']}","file");
                    return false;
                }
            }
        }
        return true;
    }

    private static function _delRetryAccount($id){
        $return_command = \Yii::$app->db_queue->createCommand()->delete("queue_aliexpress_retry_account","id = {$id}");
        $result = $return_command->execute();
        \Yii::info(date('Y-m-d H:i:s')."aliexpress_clear_helper del_retry_account {$result} execute_sql : {$return_command->getRawSql()}","file");
        return $result ? true : false;
    }
    private static function _delRetryOrder($id){
        $return_command = \Yii::$app->db_queue->createCommand()->delete("queue_aliexpress_retry_order","id = {$id}");
        $result = $return_command->execute();
        \Yii::info(date('Y-m-d H:i:s')."aliexpress_clear_helper del_retry_order {$result} execute_sql : {$return_command->getRawSql()}","file");
        return $result ? true : false;
    }

    private static function _delRetryAccountInfo($id){
        $return_command = \Yii::$app->db_queue->createCommand()->delete("queue_aliexpress_retry_account_info","id = {$id}");
        $result = $return_command->execute();
        \Yii::info(date('Y-m-d H:i:s')."aliexpress_clear_helper del_retry_account_info {$result} execute_sql : {$return_command->getRawSql()}","file");
        return $result ? true : false;
    }

    private static function _updateRetryOrder($data,$where){
        $command = \Yii::$app->db_queue->createCommand()->update('queue_aliexpress_retry_order',$data,$where);
        $result = $command->execute();
        \Yii::info(date('Y-m-d H:i:s')."aliexpress_clear_helper update_retry_order execute_sql : {$command->getRawSql()}","file");
        return $result ? true : false;
    }

    /**
     * 更新用户TOKEN 失效的订单信息
     * @param $data
     * @param $where
     */
    private static function _updateRetryAccount($data,$where){
        $command = \Yii::$app->db_queue->createCommand()->update('queue_aliexpress_retry_account',$data,$where);
        $result = $command->execute();
        \Yii::info(date('Y-m-d H:i:s')."aliexpress_clear_helper update_retry_account execute_sql : {$command->getRawSql()}","file");
        return $result ? true : false;
    }

    /**
     * 更新用户账户表信息
     * @param $data
     * @param $where
     */
    private static function _updateRetryAccountInfo($data,$where){
        $command = \Yii::$app->db_queue->createCommand()->update('queue_aliexpress_retry_account_info',$data,$where);
        $result = $command->execute();
        \Yii::info(date('Y-m-d H:i:s')."aliexpress_clear_helper update_retry_account_info execute_sql : {$command->getRawSql()}","file");
        return $result ? true : false;
    }

    private static function _saveAccountLog($data,$message){
        $params = [
            'uid' => $data['uid'],
            'sellerloginid' => $data['sellerloginid'],
            'orderid' => $data['orderid'],
            'message' => $message,
            'create_time' => time()
        ];
        $command = \Yii::$app->db_queue->createCommand()->insert('queue_aliexpress_retry_account_log',$params);
        $result = $command->execute();
        \Yii::info(date('Y-m-d H:i:s')."aliexpress_clear_helper queue_aliexpress_retry_account_log {$result} execute_sql : {$command->getRawSql()}","file");
        return $result ? true : false;

    }
    /**
     * 获取下一次重试时间
     * @param $data
     * @return int
     */
    private static function _getNextTime($data){
        if(empty($data)){
            return 0;
        } else {
            $times = $data['times'] + 1;
            if($times <= 5){
                return (time()+3600);
            } else if ($times <= 10){
                return (time()+7200);
            } else {
                return (time()+86400);
            }
        }
        return (time()+(86400*30));//异常事件 当前时间推后一个月
    }

    public static function pushOrderInfo(){
        //Step 0, 检查当前本job版本，如果和自己的版本不一样，就自动exit吧
        $currentAliexpressGetOrderListVersion = ConfigHelper::getGlobalConfig("Order/aliexpressPushOrderVersion",'NO_CACHE');
        if (empty($currentAliexpressGetOrderListVersion))
            $currentAliexpressGetOrderListVersion = 'v1';

        //如果自己还没有定义，去使用global config来初始化自己
        if (empty(self::$aliexpressGetOrderListVersion))
            self::$aliexpressGetOrderListVersion = $currentAliexpressGetOrderListVersion;

        //如果自己的version不同于全局要求的version，自己退出，从新起job来使用新代码
        if (self::$aliexpressGetOrderListVersion <> $currentAliexpressGetOrderListVersion){
            exit("Version new $currentAliexpressGetOrderListVersion , this job ver ".self::$aliexpressGetOrderListVersion." exits for using new version $currentAliexpressGetOrderListVersion.");
        }
        $backgroundJobId=self::getCronJobId();

        \Yii::info(date('Y-m-d H:i:s')."aliexress_retry_push_order gotit jobid=$backgroundJobId start");

        $connection = Yii::$app->db_queue;
        $time = time();
        $hasGotRecord = false;
        $sql = "SELECT * FROM queue_aliexpress_retry_order WHERE next_time <= {$time} and status = 0 limit 100";
        $dataReader = $connection->createCommand($sql)->query();
        while( ($row = $dataReader->read()) !== false){
            //1. 先判断是否可以正常抢到该记录
            $affectRows = $connection->createCommand("update queue_aliexpress_retry_order set status=1 where id =". $row['id']." and status = 0 ")->execute();
            if ($affectRows <= 0) {
                continue; //抢不到
            }
            $hasGotRecord = true;
            //推送订单
            $bool = self::_saveGetOrder($row);
            if($bool === true){
                \Yii::info("pushOrder push_get_order orderId={$row['orderid']}, success","file");
                self::_delRetryOrder($row['id']);
            } else {
                \Yii::info("pushOrder push_get_order orderId={$row['orderid']}, fail","file");
                $times = intval($row['times']);
                $data = [
                    'status' => 0,
                    'times' => $times + 1,
                    'message' => $row['message']. "第{$times}次重新推送失败",
                    'next_time' => time() + 3600
                ];
                self::_updateRetryOrder($data,['id'=>$row['id']]);
            }
        }
        return $hasGotRecord;
    }

    public static function retryAccountInfo(){
        //Step 0, 检查当前本job版本，如果和自己的版本不一样，就自动exit吧
        $currentAliexpressGetOrderListVersion = ConfigHelper::getGlobalConfig("Order/aliexpressRetryAccountVersion",'NO_CACHE');
        if (empty($currentAliexpressGetOrderListVersion))
            $currentAliexpressGetOrderListVersion = 'v1';

        //如果自己还没有定义，去使用global config来初始化自己
        if (empty(self::$aliexpressGetOrderListVersion))
            self::$aliexpressGetOrderListVersion = $currentAliexpressGetOrderListVersion;

        //如果自己的version不同于全局要求的version，自己退出，从新起job来使用新代码
        if (self::$aliexpressGetOrderListVersion <> $currentAliexpressGetOrderListVersion){
            exit("Version new $currentAliexpressGetOrderListVersion , this job ver ".self::$aliexpressGetOrderListVersion." exits for using new version $currentAliexpressGetOrderListVersion.");
        }
        $backgroundJobId=self::getCronJobId();

        \Yii::info(date('Y-m-d H:i:s')."aliexress_retry_account gotit jobid=$backgroundJobId start");

        $connection = Yii::$app->db_queue;
        $hasGotRecord = false;
        $t = time();
        $sql = "SELECT * FROM queue_aliexpress_retry_account_info WHERE next_time < {$t} limit 100";
        $dataReader = $connection->createCommand($sql)->query();
        while( ($row = $dataReader->read()) !== false){
            //锁定账号
            //$result = \Yii::$app->redis->executeCommand("INCR",[$row['sellerloginid']]);
            $result = RedisHelper::RedisAdd("Aliexpress_retry_account", $row['sellerloginid']);
            if($result > 1){
                //\Yii::$app->redis->executeCommand("DECR",[$row['sellerloginid']]);
                RedisHelper::RedisAdd("Aliexpress_retry_account", $row['sellerloginid'], -1);
                continue;
            }
            $hasGotRecord = true;
            //获取速卖通账号信息
            $aliexpress_account = self::_getAliexpressToken($row['sellerloginid']);
            if($aliexpress_account === false){
                $params = [
                    'last_time' => time(),
                    'message' => '获取saas_aliexpress_user信息失败',
                    'times' => ($row['times'] + 1),
                    'next_time' => (time()+86400*1000)
                ];
                //推迟重试时间
                self::_updateRetryAccountInfo($params,['id'=>$row['id']]);
                self::_saveAccountLog($row,'获取saas_aliexpress_user信息失败！');
                continue;
            }
            //重置TOKEN
            $api = new AliexpressInterface_Api ();
            $token = self::_getAccessToken($aliexpress_account,$api);
            if($token !== false){
                $aliexpress_account['access_token'] = $token['access_token'];
                //接口调用成功
                $bool = self::_checkOrderInfo($api,$aliexpress_account,$row['orderid']);
                if($bool === true){
                    //推送所用订单
                    $bool = self::_saveAllGetOrder($row['sellerloginid']);
                    if($bool === true){
                        $bool = self::_delRetryAccountInfo($row['id']);
                        if($bool === true){
                            self::_saveAccountLog($row,'重试订单成功,推送订单成功,删除重试账号信息！');
                        } else {
                            $params = [
                                'last_time' => time(),
                                'message' => '重试TOKEN成功,推送订单至getOrder表成功,删除重试账号信息失败！',
                                'times' => ($row['times'] + 1),
                                'next_time' => (time()+86400*365)
                            ];
                            self::_updateRetryAccountInfo($params,['id'=>$row['id']]);
                        }
                    } else {
                        $params = [
                            'last_time' => time(),
                            'message' => '重试TOKEN成功,推送订单至getOrder表失败',
                            'times' => ($row['times'] + 1),
                            'next_time' => self::_getNextTime($row)
                        ];
                        self::_updateRetryAccountInfo($params,['id'=>$row['id']]);
                        self::_saveAccountLog($row,'重试订单成功,推送订单成功,删除重试账号信息！');
                    }
                } else {
                    \Yii::info("retryAccount api_result orderId={$row['orderid']}, fail","file");
                    $params = [
                        'last_time' => time(),
                        'message' => '调用API获取订单信息失败',
                        'times' => ($row['times'] + 1),
                        'next_time' => self::_getNextTime($row)
                    ];
                    //推迟重试时间
                    self::_updateRetryAccountInfo($params,['id'=>$row['id']]);
                    self::_saveAccountLog($row,'调用API获取订单信息失败！');
                }
            } else {
                \Yii::info("retryAccount retry_token orderId={$row['orderid']}, fail","file");
                $params = [
                    'last_time' => time(),
                    'message' => '检查TOKEN信息失败',
                    'times' => ($row['times'] + 1),
                    'next_time' => self::_getNextTime($row)
                ];
                //推迟重试时间
                self::_updateRetryAccountInfo($params,['id'=>$row['id']]);
                self::_saveAccountLog($row,'检查TOKEN信息失败！');
            }
            //还原计数
            //\Yii::$app->redis->executeCommand("DECR",[$row['sellerloginid']]);
            RedisHelper::RedisAdd("Aliexpress_retry_account", $row['sellerloginid'], -1);
        }
        return $hasGotRecord;
    }
    /**
     * @param $api
     * @param $row
     * @param $orderid
     * @return bool
     */
    public static function _checkOrderInfo(&$api,$row,$orderid){
        $api->access_token = $row['access_token'];
        $api->AppKey = $row['app_key'];
        $api->appSecret = $row['app_secret'];
        // 接口传入参数速卖通订单号
        $param = array (
            'orderId' => $orderid
        );
        // 调用接口获取订单列表
        $api->setAppInfo($row['app_key'],$row['app_secret']);
        $result =  $api->findOrderById ( $param );
        if(isset($result['error_message'])){
            \Yii::info("retryAccount api_find_order_by_id orderId={$row['orderid']}, fail error_msg : " . var_export($result,true),"file");
            return false;
        } else {
            return true;
        }
    }
    /**
     * @param $sellerloginid
     * @return array
     */
    public static function _getAliexpressToken($sellerloginid){
        return \Yii::$app->db->createCommand('select * from saas_aliexpress_user where sellerloginid = "'.$sellerloginid.'"')->query()->read();
    }

    public static function _getAccessToken($aliexpress_account,&$api){
        $refresh_token_timeout = $aliexpress_account['refresh_token_timeout'];
        $current_time = time();

        $api->access_token = $aliexpress_account['access_token'];

        $api->setAppInfo($aliexpress_account['app_key'],$aliexpress_account['app_secret']);
        if($refresh_token_timeout < $current_time){//refresh_token已经过期
            $day = ($current_time - $refresh_token_timeout)/86400;//过期多少天
            if($day < 30){
                $rtn = $api->postponeToken($aliexpress_account['refresh_token'] , $api->access_token);//换取新的refreshToken
                if(isset($rtn['refresh_token'])){
                    $params = [
                        'refresh_token' => $rtn['refresh_token'],
                        'refresh_token_timeout' => AliexpressInterface_Helper::transLaStrTimetoTimestamp($rtn['refresh_token_timeout']),
                        'access_token' => $rtn['access_token'],
                        'access_token_timeout' => (time() + 28800), // 8 小时过期
                    ];
                    \Yii::$app->db->createCommand()->update('saas_aliexpress_user',$params,['aliexpress_uid' => $aliexpress_account['aliexpress_uid']])->execute();
                } else {
                    return false;
                }
            } else {
                return false;
            }
        } else {
            //直接通过refresh_token获取新的access_token
            $rtn = $api->refreshTokentoAccessToken($aliexpress_account['refresh_token']);
            if(isset($rtn['access_token'])){
                $params = [
                    'access_token' => $rtn['access_token'],
                    'access_token_timeout' => (time() + 28800), // 8 小时过期
                ];
                \Yii::$app->db->createCommand()->update('saas_aliexpress_user',$params,['aliexpress_uid' => $aliexpress_account['aliexpress_uid']])->execute();
            } else {
                return false;
            }
        }
        return $rtn;
    }
}
