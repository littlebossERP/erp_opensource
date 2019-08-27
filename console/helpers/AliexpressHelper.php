<?php
namespace console\helpers;

use eagle\modules\platform\helpers\WishAccountsHelper;
use \Yii;
use eagle\models\SaasAliexpressAutosync;
use common\api\aliexpressinterface\AliexpressInterface_Auth;
use common\api\aliexpressinterface\AliexpressInterface_Api;
use eagle\models\QueueAliexpressGetorder;
use eagle\models\QueueAliexpressGetorder2;
use eagle\models\QueueAliexpressGetorder4;
use eagle\models\QueueAliexpressGetfinishorder;
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
use console\helpers\AliexpressClearHelper;
use eagle\modules\util\helpers\SQLHelper;
use eagle\models\AliexpressCategory;
use eagle\models\AliexpressListingDetail;
use eagle\modules\util\models\UserBackgroundJobControll;
use common\helpers\Helper_Array;
use common\helpers\Helper_Currency;
use eagle\modules\util\helpers\ExcelHelper;
use Qiniu\json_decode;
use eagle\models\ImportEnsogoListing;
use eagle\modules\listing\helpers\EnsogoStoreMoveHelper;
use eagle\modules\listing\models\EnsogoCategories;
use eagle\modules\listing\helpers\EnsogoHelper;
use eagle\models\SaasEnsogoUser;
use eagle\models\QueueManualOrderSync;
use console\controllers\CommentHelperController as console;
use eagle\modules\order\helpers\OrderHelper;
use eagle\modules\util\helpers\RedisHelper;
/**
 * +------------------------------------------------------------------------------
 * Aliexpress 数据同步类
 * +------------------------------------------------------------------------------
 */
class AliexpressHelper
{
    public static $cronJobId = 0;
    private static $aliexpressGetOrderListVersion = null;
    private static $version = null;


    protected static $active_users;

    const LOG_ID = "console_helpers_aliexpressHelper";

    private static function localLog($msg)
    {
        console::log($msg, self::LOG_ID);
    }

    public static function isActiveUser($uid)
    {
		return true;
        // if (empty(self::$active_users)) {
            // self::$active_users = \eagle\modules\util\helpers\UserLastActionTimeHelper::getPuidArrByInterval(72);
        // }

        // if (in_array($uid, self::$active_users)) {
            // return true;
        // }

        // return false;
    }

    /**
     * @return the $cronJobId
     */
    public static function getCronJobId()
    {
        return self::$cronJobId;
    }

    /**
     * @param number $cronJobId
     */
    public static function setCronJobId($cronJobId)
    {
        self::$cronJobId = $cronJobId;
    }

    /**
     * @param string $format . output time string format
     * @param timestamp $timestamp
     * @return America/Los_Angeles formatted time string
     */
    static function getLaFormatTime($format, $timestamp)
    {
        $dt = new \DateTime();
        $dt->setTimestamp($timestamp);
        $dt->setTimezone(new \DateTimeZone('America/Los_Angeles'));
        return $dt->format($format);


    }


    /**
     * 同步Aliexpress订单120天
     * @author million 2015-04-03
     * 88028624@qq.com
     */
    static function getOrderListByDay120()
    {
        //Step 0, 检查当前本job版本，如果和自己的版本不一样，就自动exit吧
        $currentAliexpressGetOrderListVersion = ConfigHelper::getGlobalConfig("Order/aliexpressGetOrderListVersion", 'NO_CACHE');
        if (empty($currentAliexpressGetOrderListVersion)) {
            $currentAliexpressGetOrderListVersion = 0;
        }
        //如果自己还没有定义，去使用global config来初始化自己
        if (empty(self::$aliexpressGetOrderListVersion)) {
            self::$aliexpressGetOrderListVersion = $currentAliexpressGetOrderListVersion;
        }
        //如果自己的version不同于全局要求的version，自己退出，从新起job来使用新代码
        if (self::$aliexpressGetOrderListVersion <> $currentAliexpressGetOrderListVersion) {
            exit("Version new $currentAliexpressGetOrderListVersion , this job ver " . self::$aliexpressGetOrderListVersion . " exits for using new version $currentAliexpressGetOrderListVersion.");
        }

        self::$cronJobId++;
        echo date('Y-m-d H:i:s') . ' day120 script start ' . self::$cronJobId . PHP_EOL;
        $connection = Yii::$app->db;
        $hasGotRecord = false;
        $now = time();

        $dataReader = $connection->createCommand('select `id` from  `saas_aliexpress_autosync` where `is_active` = 1 AND `status` in (0,3) AND `times` < 10 AND `type`="day120" order by `last_time` ASC limit 5')->query();
        while (false !== ($row = $dataReader->read())) {
            //1. 先判断是否可以正常抢到该记录
            $affectRows = $connection->createCommand("update saas_aliexpress_autosync set status=1 where id =" . $row['id'] . " and status<>1 ")->execute();

            if ($affectRows <= 0) {
                continue; //抢不到
            }

            //2. 抢到记录
            $hasGotRecord = true;
            $SAA_obj = SaasAliexpressAutosync::findOne($row['id']);
            if (!$SAA_obj) {
                echo 'exception' . $row['id'] . PHP_EOL;
                continue;
            }

            // 检查授权是否过期或者是否授权,返回true，false
            if (!AliexpressInterface_Auth::checkToken($SAA_obj->sellerloginid)) {
                $SAA_obj->message = $SAA_obj->sellerloginid . ' token 过期';
                $SAA_obj->status = 3;
                $SAA_obj->times += 1;
                $SAA_obj->last_time = $now;
                $SAA_obj->update_time = $now;
                $bool = $SAA_obj->save(false);
                if (!$bool) {
                    echo __FUNCTION__ . "STEP 1 : " . var_export($SAA_obj->errors, true);
                }
                continue;
            }

            $api = new AliexpressInterface_Api ();
            $access_token = $api->getAccessToken($SAA_obj->sellerloginid);
            //获取访问token失败
            if ($access_token === false) {
                $SAA_obj->message = $SAA_obj->sellerloginid . ' token 异常';
                $SAA_obj->status = 3;
                $SAA_obj->times += 1;
                $SAA_obj->last_time = $now;
                $SAA_obj->update_time = $now;
                $bool = $SAA_obj->save(false);
                if (!$bool) {
                    echo __FUNCTION__ . "STEP 2 : " . var_export($SAA_obj->errors, true);
                }
                continue;
            }

            $api->access_token = $access_token;
            $page = 1;
            $pageSize = 50;
            // 是否全部同步完成
            $success = true;
            $start_time = $SAA_obj->binding_time - (86400 * 120);
            $format_start_time = self::getLaFormatTime("m/d/Y H:i:s", $start_time);
            $end_time = $SAA_obj->binding_time;
            $format_end_time = self::getLaFormatTime("m/d/Y H:i:s", $end_time);
            do {
                // 接口传入参数
                $param = array(
                    'page' => $page,
                    'pageSize' => $pageSize,
                );
                ###################################################
                $param['createDateStart'] = $format_start_time;
                $param['createDateEnd'] = $format_end_time;
                #######################################################
                // 调用接口获取订单列表
                $result = $api->findOrderListSimpleQuery($param);
                // 判断是否有订单
                if (isset ($result ['totalItem'])) {
                    //保存本次同步订单数
                    $affectRows = $connection->createCommand("update saas_aliexpress_autosync set order_item= " . $result ['totalItem'] . " where id ={$row['id']}  ")->execute();
                    if ($result ['totalItem'] > 0) {
                        // 保存数据到同步订单详情队列
                        foreach ($result ['orderList'] as $one) {
                            // 订单产生时间
                            $gmtCreate = AliexpressInterface_Helper::transLaStrTimetoTimestamp($one ['gmtCreate']);
                            $QAG_obj = QueueAliexpressGetorder::findOne(['orderid' => $one ['orderId']]);
                            if (isset ($QAG_obj)) {
                                $QAG_obj->type = 3;
                                $QAG_obj->order_status = $one['orderStatus'];
                                $QAG_obj->order_info = json_encode($one);
                                $QAG_obj->update_time = $now;
                                $QAG_obj->last_time = $now;
                                $bool = $QAG_obj->save(false);
                                if (!$bool) {
                                    echo __FUNCTION__ . "STEP 3 : " . var_export($QAG_obj->errors, true);
                                }
                            } else {
                                $QAG_obj = new QueueAliexpressGetorder ();
                                $QAG_obj->uid = $SAA_obj->uid;
                                $QAG_obj->sellerloginid = $SAA_obj->sellerloginid;
                                $QAG_obj->aliexpress_uid = $SAA_obj->aliexpress_uid;
                                $QAG_obj->status = 0;
                                $QAG_obj->type = 3;
                                $QAG_obj->order_status = $one['orderStatus'];
                                $QAG_obj->orderid = $one ['orderId'];
                                $QAG_obj->times = 0;
                                $QAG_obj->order_info = json_encode($one);
                                $QAG_obj->last_time = 0;
                                $QAG_obj->gmtcreate = $gmtCreate;
                                $QAG_obj->create_time = $now;
                                $QAG_obj->update_time = $now;
                                $bool = $QAG_obj->save(false);
                                if (!$bool) {
                                    echo __FUNCTION__ . "STEP 4 : " . var_export($QAG_obj->errors, true);
                                }
                            }
                        }
                    }
                } else {
                    $success = false;
                }

                $page++;
                $total = isset($result ['totalItem']) ? $result ['totalItem'] : 0;
                $p = ceil($total / 50);
            } while ($page <= $p);
            // 是否全部同步成功
            if ($success) {
                $SAA_obj->start_time = $start_time;
                $SAA_obj->end_time = $end_time;
                $SAA_obj->last_time = $now;
                $SAA_obj->status = 4;
                $SAA_obj->times = 0;
                $bool = $SAA_obj->save(false);
                if (!$bool) {
                    echo __FUNCTION__ . "STEP 5 : " . var_export($SAA_obj->errors, true);
                }
            } else {
                $SAA_obj->message = isset($result ['error_message']) ? $result ['error_message'] : '接口返回结果错误V1' . print_r($result, true);
                $SAA_obj->last_time = $now;
                $SAA_obj->status = 3;
                $SAA_obj->times += 1;
                $bool = $SAA_obj->save(false);
                if (!$bool) {
                    echo __FUNCTION__ . "STEP 6 : " . var_export($SAA_obj->errors, true);
                }
            }
        }
        return $hasGotRecord;
    }

    /**
     * 同步Aliexpress订单所有已完成
     * @author million 2015-04-03
     * 88028624@qq.com
     */
    static function getOrderListByFinish()
    {
        //Step 0, 检查当前本job版本，如果和自己的版本不一样，就自动exit吧
        $currentAliexpressGetOrderListVersion = ConfigHelper::getGlobalConfig("Order/aliexpressGetOrderListVersion", 'NO_CACHE');
        if (empty($currentAliexpressGetOrderListVersion))
            $currentAliexpressGetOrderListVersion = 'v1';

        //如果自己还没有定义，去使用global config来初始化自己
        if (empty(self::$aliexpressGetOrderListVersion))
            self::$aliexpressGetOrderListVersion = $currentAliexpressGetOrderListVersion;

        //如果自己的version不同于全局要求的version，自己退出，从新起job来使用新代码
        if (self::$aliexpressGetOrderListVersion <> $currentAliexpressGetOrderListVersion) {
            exit("Version new $currentAliexpressGetOrderListVersion , this job ver " . self::$aliexpressGetOrderListVersion . " exits for using new version $currentAliexpressGetOrderListVersion.");
        }
        $backgroundJobId = self::getCronJobId();
        $connection = Yii::$app->db;
        #########################
        $type = 'finish';
        $hasGotRecord = false;
        $command = $connection->createCommand('select `id` from  `saas_aliexpress_autosync` where `is_active` = 1 AND `status` in (0,2,3) AND `times` < 10 AND `type`="' . $type . '" order by `last_time` ASC limit 5');
        #################################
        $dataReader = $command->query();
        while (($row = $dataReader->read()) !== false) {
            //echo '<pre>';print_r($row);exit; //8614
            //1. 先判断是否可以正常抢到该记录
            $command = $connection->createCommand("update saas_aliexpress_autosync set status=1 where id =" . $row['id'] . " and status<>1 ");
            $affectRows = $command->execute();
            if ($affectRows <= 0) continue; //抢不到
            \Yii::info("aliexress_get_order_list_by_finish gotit jobid=$backgroundJobId start");
            //2. 抢到记录，设置同步需要的参数
            $hasGotRecord = true;
            $SAA_obj = SaasAliexpressAutosync::findOne($row['id']);
            // 检查授权是否过期或者是否授权,返回true，false
            $a = AliexpressInterface_Auth::checkToken($SAA_obj->sellerloginid);
            if ($a) {
                echo $SAA_obj->sellerloginid . "\n";
                $api = new AliexpressInterface_Api ();
                $access_token = $api->getAccessToken($SAA_obj->sellerloginid);
                //获取访问token失败
                if ($access_token === false) {
                    echo $SAA_obj->sellerloginid . 'not getting access token!' . "\n";
                    \Yii::info($SAA_obj->sellerloginid . 'not getting access token!' . "\n");
                    $SAA_obj->message = $SAA_obj->sellerloginid . ' not getting access token!';
                    $SAA_obj->status = 3;
                    $SAA_obj->times += 1;
                    $SAA_obj->last_time = time();
                    $SAA_obj->update_time = time();
                    $bool = $SAA_obj->save(false);
                    if (!$bool) {
                        echo __FUNCTION__ . "STEP 1 : " . var_export($SAA_obj->errors, true);
                    }
                    continue;
                }
                $api->access_token = $access_token;
                $page = 1;
                $pageSize = 50;
                // 是否全部同步完成
                $success = true;
                $exit = false;
                #####################################
                if ($SAA_obj->end_time == 0) {
                    $start_time = $SAA_obj->binding_time - (86400 * 30);
                    $end_time = $SAA_obj->binding_time;
                    if ($SAA_obj->start_time > $start_time) {
                        $start_time = $SAA_obj->start_time;
                    }
                } else {
                    $start_time = $SAA_obj->end_time - (86400 * 30);
                    $end_time = $SAA_obj->end_time;
                    //当重新绑定或者重新同步的账号，不用同步所有的订单数据
                    if ($SAA_obj->start_time > $start_time) {
                        $start_time = $SAA_obj->start_time;
                    }
                }

                ########################################
                do {
                    // 接口传入参数
                    $param = array(
                        'page' => (int)$page,
                        'pageSize' => $pageSize,
                    );
                    ###################################################
// 					$param['createDateStart']=date ( "m/d/Y H:i:s",$start_time );
// 					$param['createDateEnd']=date ( "m/d/Y H:i:s", $end_time );
                    $param['createDateStart'] = self::getLaFormatTime("m/d/Y H:i:s", $start_time);
                    $param['createDateEnd'] = self::getLaFormatTime("m/d/Y H:i:s", $end_time);
                    #######################################################
                    $param['orderStatus'] = 'FINISH';
                    ####################################################
                    // 调用接口获取订单列表
                    //$result = $api->findOrderListQuery ( $param );//old
                    $result = $api->findOrderListSimpleQuery($param);
                    //echo print_r ( $result, 1 );exit;
                    // 判断是否有订单
                    //保存本次同步订单数
                    if (isset ($result ['totalItem'])) {
                        $affectRows = $connection->createCommand("update saas_aliexpress_autosync set order_item= " . $result ['totalItem'] . " where id ={$row['id']}  ")->execute();
                        echo $result ['totalItem'] . "\n";
                        if ($result ['totalItem'] > 0) {
                            // 保存数据到同步订单详情队列
                            foreach ($result ['orderList'] as $one) {
                                // 订单产生时间
// 							$gmtCreate_str = substr ( $one ['gmtCreate'], 0, 14 );
// 							$gmtCreate = strtotime ( $gmtCreate_str );
                                $gmtCreate = AliexpressInterface_Helper::transLaStrTimetoTimestamp($one ['gmtCreate']);
                                //原先所有类型都在queue_aliexpress_getorder表，现在单独把finish独立出来，放表QueueAliexpressGetfinishorder
//							$QAG_obj = QueueAliexpressGetorder::findOne(['orderid'=>$one ['orderId']]);
//							if (isset ( $QAG_obj )) {
//								$QAG_obj->order_status = $one['orderStatus'];
//									$QAG_obj->order_info = json_encode ( $one );
//									$QAG_obj->update_time = time ();
//									$QAG_obj->save ();
//								} else {
//								$QAG_obj = new QueueAliexpressGetorder ();
//									$QAG_obj->uid = $SAA_obj->uid;
//									$QAG_obj->sellerloginid = $SAA_obj->sellerloginid;
//									$QAG_obj->aliexpress_uid = $SAA_obj->aliexpress_uid;
//									$QAG_obj->status = 0;
//									$QAG_obj->type = 2;
//									$QAG_obj->order_status = $one['orderStatus'];
//									$QAG_obj->orderid = $one ['orderId'];
//									$QAG_obj->times = 0;
//									$QAG_obj->order_info = json_encode ( $one );
//									$QAG_obj->last_time = 0;
//									$QAG_obj->gmtcreate = $gmtCreate;
//									$QAG_obj->create_time = time ();
//									$QAG_obj->update_time = time ();
//									$QAG_obj->save ();
//								}
                                //new 把finish的订单单独存在一张表里面
                                $QAG_finish = QueueAliexpressGetfinishorder::findOne(['orderid' => $one ['orderId']]);
                                if (isset ($QAG_finish)) {
                                    $QAG_finish->order_status = $one['orderStatus'];
                                    $QAG_finish->order_info = json_encode($one);
                                    $QAG_finish->update_time = time();
                                    $bool = $QAG_finish->save(false);
                                    if (!$bool) {
                                        echo __FUNCTION__ . "STEP 2 : " . var_export($QAG_finish->errors, true);
                                    }
                                } else {
                                    $QAG_finish = new QueueAliexpressGetfinishorder ();
                                    $QAG_finish->uid = $SAA_obj->uid;
                                    $QAG_finish->sellerloginid = $SAA_obj->sellerloginid;
                                    $QAG_finish->aliexpress_uid = $SAA_obj->aliexpress_uid;
                                    $QAG_finish->status = 0;
                                    $QAG_finish->type = 2;
                                    $QAG_finish->order_status = $one['orderStatus'];
                                    $QAG_finish->orderid = $one ['orderId'];
                                    $QAG_finish->times = 0;
                                    $QAG_finish->order_info = json_encode($one);
                                    $QAG_finish->last_time = 0;
                                    $QAG_finish->gmtcreate = $gmtCreate;
                                    $QAG_finish->create_time = time();
                                    $QAG_finish->update_time = time();
                                    $QAG_finish->next_time = time();
                                    $bool = $QAG_finish->save(false);
                                    if (!$bool) {
                                        echo __FUNCTION__ . "STEP 3 : " . var_export($QAG_finish->errors, true);
                                    }
                                }
                            }
                        } else {
                            $exit = true;//当已完成订单数量为0时说明已完成订单已经同步完毕
                        }
                    } else {
                        $success = false;
                    }

                    $page++;
                    $total = isset($result ['totalItem']) ? $result ['totalItem'] : 0;
                    $p = ceil($total / 50);
                } while ($page <= $p);
                // 是否全部同步成功
                if ($success) {
                    $SAA_obj->end_time = $start_time;
                    $SAA_obj->last_time = time();
                    if ($exit) {
                        $SAA_obj->status = 4;//已完成订单全部同步
                    } else {
                        $SAA_obj->status = 2;
                    }
                    $SAA_obj->times = 0;
                    $bool = $SAA_obj->save(false);
                    if (!$bool) {
                        echo __FUNCTION__ . "STEP 4 : " . var_export($SAA_obj->errors, true);
                    }
                } else {
                    $SAA_obj->message = isset($result ['error_message']) ? $result ['error_message'] : '接口返回结果错误V1' . print_r($result, true);
                    $SAA_obj->last_time = time();
                    $SAA_obj->status = 3;
                    $SAA_obj->times += 1;
                    $bool = $SAA_obj->save(false);
                    if (!$bool) {
                        echo __FUNCTION__ . "STEP 5 : " . var_export($SAA_obj->errors, true);
                    }
                }
            } else {
                echo $SAA_obj->sellerloginid . ' Unauthorized or expired!' . "\n";
                $SAA_obj->message = $SAA_obj->sellerloginid . ' Unauthorized or expired!';
                $SAA_obj->last_time = time();
                $SAA_obj->status = 3;
                $SAA_obj->times += 1;
                $bool = $SAA_obj->save(false);
                if (!$bool) {
                    echo __FUNCTION__ . "STEP 6 : " . var_export($SAA_obj->errors, true);
                }
            }
            \Yii::info("aliexress_get_order_list_by_finish gotit jobid=$backgroundJobId end");
        }
        return $hasGotRecord;
    }


    /**
     * 同步Aliexpress新产生的订单时间从绑定，重新绑定或者重新开启时间开始
     * @author million 2015-04-03
     * 88028624@qq.com
     */
    public static function getOrderListByTime()
    {
        //Step 0, 检查当前本job版本，如果和自己的版本不一样，就自动exit吧
        $currentAliexpressGetOrderListVersion = ConfigHelper::getGlobalConfig("Order/aliexpressGetOrderListVersion", 'NO_CACHE');
        if (empty($currentAliexpressGetOrderListVersion)) {
            $currentAliexpressGetOrderListVersion = 0;
        }
        //如果自己还没有定义，去使用global config来初始化自己
        if (empty(self::$aliexpressGetOrderListVersion)) {
            self::$aliexpressGetOrderListVersion = $currentAliexpressGetOrderListVersion;
        }
        //如果自己的version不同于全局要求的version，自己退出，从新起job来使用新代码
        if (self::$aliexpressGetOrderListVersion <> $currentAliexpressGetOrderListVersion) {
            exit("Version new $currentAliexpressGetOrderListVersion , this job ver " . self::$aliexpressGetOrderListVersion . " exits for using new version $currentAliexpressGetOrderListVersion.");
        }

        self::$cronJobId++;
        echo date('Y-m-d H:i:s') . ' time script start ' . self::$cronJobId . PHP_EOL;
        $connection = Yii::$app->db;

        //30分钟之前
        //$t = time()-1800;
        $nowTime = time();
        $hasGotRecord = false;

        //查询同步控制表所有time队列，最后同步时间为半小时前数据，倒序取前五条
        $sql = "select `id` from  `saas_aliexpress_autosync` where `is_active` = 1 AND `status` <>1 AND `times` < 10 AND `type`='time' AND next_time < {$nowTime}  order by `next_time` ASC limit 5 ";
        $dataReader = $connection->createCommand($sql)->query();
        echo date('Y-m-d H:i:s') . ' select count ' . $dataReader->count() . PHP_EOL;
        while (false !== ($row = $dataReader->read())) {
            //1. 先判断是否可以正常抢到该记录
            $nowTime = time();
            $affectRows = $connection->createCommand("update saas_aliexpress_autosync set status=1,last_time={$nowTime} where id ={$row['id']} and status<>1 ")->execute();
            if ($affectRows <= 0) {
                continue; //当前这条抢不到
            }

            //2. 抢到记录，设置同步需要的参数
            $hasGotRecord = true;
            $SAA_obj = SaasAliexpressAutosync::findOne($row['id']);
            if (!$SAA_obj) {
                echo date('Y-m-d H:i:s') . ' Exception ' . $row['id'] . PHP_EOL;
                continue;
            }
            $puid = $SAA_obj->uid;
            $sellerloginid = $SAA_obj->sellerloginid;
            $timeMS1 = TimeUtil::getCurrentTimestampMS();
            echo date('Y-m-d H:i:s') . " step1 puid=$puid,sellerloginid=$sellerloginid" . PHP_EOL;

            //检查是否活跃用户，如不活跃，则放到晚上执行新订单同步，减少白天的负载
            if (!self::isActiveUser($SAA_obj->uid)) {
                $next_time = date('G');
                if ($next_time < 23 && $next_time > 8) {
                    $next_time = strtotime(date('Y-m-d 23:20:00'));
                    $SAA_obj->next_time = $next_time;
                    $SAA_obj->last_time = time();
                    $SAA_obj->status = 2;
                    $bool = $SAA_obj->save(false);
                    if (!$bool) {
                        echo __FUNCTION__ . "STEP 1 : " . var_export($SAA_obj->errors, true);
                    }
                    echo date('Y-m-d H:i:s') . " not_active_skip puid={$puid},sellerloginid={$sellerloginid},next_time:{$next_time}" . PHP_EOL;
                    continue;
                }
            }
            echo date('Y-m-d H:i:s') . " step2 puid=$puid,sellerloginid=$sellerloginid" . PHP_EOL;

            // 检查授权是否过期或者是否授权,返回true，false
            if (!AliexpressInterface_Auth::checkToken($SAA_obj->sellerloginid)) {
                $SAA_obj->message .= " {$SAA_obj->sellerloginid} Unauthorized or expired!";
                $SAA_obj->last_time = time();
                $SAA_obj->next_time = time() + 1200;
                $SAA_obj->status = 3;
                $SAA_obj->times += 1;
                $bool = $SAA_obj->save(false);
                if (!$bool) {
                    echo __FUNCTION__ . "STEP 2 : " . var_export($SAA_obj->errors, true);
                }
                continue;
            }

            $api = new AliexpressInterface_Api ();
            $access_token = $api->getAccessToken($SAA_obj->sellerloginid);
            //获取访问token失败
            if ($access_token === false) {
                $SAA_obj->message .= " {$SAA_obj->sellerloginid} not getting access token!";
                $SAA_obj->status = 3;
                $SAA_obj->times += 1;
                $SAA_obj->last_time = time();
                $SAA_obj->next_time = time() + 1200;
                $bool = $SAA_obj->save(false);
                if (!$bool) {
                    echo __FUNCTION__ . "STEP 3 : " . var_export($SAA_obj->errors, true);
                }
                continue;
            }

            echo date('Y-m-d H:i:s') . " step3 puid=$puid,sellerloginid=$sellerloginid" . PHP_EOL;
            $api->access_token = $access_token;
            $timeMS2 = TimeUtil::getCurrentTimestampMS();

            //分页设置
            $page = 1;
            $pageSize = 50;
            // 是否全部同步完成
            $success = true;
            #####################################
            $time = time();
            if ($SAA_obj->end_time == 0) {
                //初始同步
                $start_time = $SAA_obj->binding_time;
                $end_time = $time;
            } else {
                //增量同步
                $start_time = $SAA_obj->end_time;
                $end_time = $time;
            }

            $format_start_time = self::getLaFormatTime("m/d/Y H:i:s", $start_time);
            $format_end_time = self::getLaFormatTime("m/d/Y H:i:s", $end_time);
            //echo date('Y-m-d H:i:s').' one shop start '.$row['id'].PHP_EOL;
            echo date('Y-m-d H:i:s') . " step4 puid=$puid,sellerloginid=$sellerloginid,start_time=$start_time,end_time=$end_time,format_start_time=$format_start_time,format_end_time=$format_end_time" . PHP_EOL;
            $totalOrderNum = 0;
            //获取最后发货时间
            $orders = [];
            $sendGoodsOperator_arr= array();

            do {
                // 接口传入参数
                $param = ['page' => $page, 'pageSize' => $pageSize, 'createDateStart' => $format_start_time, 'createDateEnd' => $format_end_time];
                // 调用接口获取订单列表
                $result2 = $api->findOrderListQuery($param);
                \Yii::info('getOrderListByTime--findOrderListQuery---'.json_encode($result2), "file");
                // 判断是否有订单
                if (!isset ($result2['totalItem']) || !isset($result2['orderList'])) {
                    $success = false;
                    break;
                }
                print_r ($result2 ['orderList']);
                foreach ($result2 ['orderList'] as $order) {
                    $orders[$order['orderId']]['day'] = isset($order['leftSendGoodDay']) ? $order['leftSendGoodDay'] : 0;
                    $orders[$order['orderId']]['hour'] = isset($order['leftSendGoodHour']) ? $order['leftSendGoodHour'] : 0;
                    $orders[$order['orderId']]['min'] = isset($order['leftSendGoodMin']) ? $order['leftSendGoodMin'] : 0;

                    if( isset( $order['productList'] ) ){
                        foreach( $order['productList'] as $pl ){
                            //客选物流
                            if( isset( $pl['logisticsServiceName'] ) ){
                                $logisticsServiceName= $pl['logisticsServiceName'];
                                $productid= $pl['productId'];
                                $orders[$order['orderId']]['shipping_service'][$productid]= $logisticsServiceName;
                            }
                            //发货类型
                            if (isset($pl['sendGoodsOperator'])) {
                            	$sendGoodsOperator = $pl['sendGoodsOperator'];
                            	$productid = $pl['productId'];
                            	$sendGoodsOperator_arr[$order['orderId']][$productid] = $sendGoodsOperator;
                            }
                        }
                    }

                }
                $page++;
                $p = ceil($result2['totalItem'] / 50);
            } while ($page <= $p);

            //重置
            $page = 1;
            do {
                // 接口传入参数
                $param = ['page' => $page, 'pageSize' => $pageSize, 'createDateStart' => $format_start_time, 'createDateEnd' => $format_end_time];
                // 调用接口获取订单列表
                $result = $api->findOrderListSimpleQuery($param);
                // 判断是否有订单
                if (!isset ($result['totalItem'])) {
                    $success = false;
                    break;
                }
                //保存本次同步订单数
                $affectRows = $connection->createCommand("update saas_aliexpress_autosync set order_item= " . $result ['totalItem'] . " where id ={$row['id']}  ")->execute();

                if ($result ['totalItem'] > 0) {
                    $totalOrderNum = $totalOrderNum + $result ['totalItem'];

                    // 保存数据到同步订单详情队列
                    foreach ($result ['orderList'] as $one) {
                        // 订单产生时间
                        $gmtCreate = AliexpressInterface_Helper::transLaStrTimetoTimestamp($one['gmtCreate']);
                        $QAG_obj = QueueAliexpressGetorder::findOne(['orderid' => $one['orderId']]);
                        $order_info = self::_setOrderDay($orders, $one);
                        
                        //发货类型
                        if(!empty($sendGoodsOperator_arr[$one['orderId']])){
                        	$order_info['sendGoodsOperator'] = $sendGoodsOperator_arr[$one['orderId']];
                        }
                        
                        print_r ($order_info);

                        if (isset ($QAG_obj)) {
                            $QAG_obj->type = 3;
                            $QAG_obj->times = 0;
                            $QAG_obj->order_status = $one['orderStatus'];
                            $QAG_obj->order_info = json_encode($order_info);
                            $QAG_obj->update_time = $time;
                            $bool = $QAG_obj->save(false);
                            if (!$bool) {
                                \Yii::info("step4 error puid=$puid,sellerloginid=$sellerloginid,totalOrderNum=$totalOrderNum error_msg : " . var_export($QAG_obj->errors, true), "file");
                            }
                        } else {
                            $QAG_obj = new QueueAliexpressGetorder ();
                            $QAG_obj->uid = $SAA_obj->uid;
                            $QAG_obj->sellerloginid = $SAA_obj->sellerloginid;
                            $QAG_obj->aliexpress_uid = $SAA_obj->aliexpress_uid;
                            $QAG_obj->status = 0;
                            $QAG_obj->type = 3;  //新增订单标识
                            $QAG_obj->order_status = $one['orderStatus'];
                            $QAG_obj->orderid = $one ['orderId'];
                            $QAG_obj->times = 0;
                            $QAG_obj->order_info = json_encode($order_info);
                            $QAG_obj->last_time = 0;
                            $QAG_obj->gmtcreate = $gmtCreate;
                            $QAG_obj->create_time = $time;
                            $QAG_obj->update_time = $time;
                            $bool = $QAG_obj->save(false);
                            if (!$bool) {
                                \Yii::info("step5 error puid=$puid,sellerloginid=$sellerloginid,totalOrderNum=$totalOrderNum error_msg : " . var_export($QAG_obj->errors, true), "file");
                            }

                        }

                    }
                }
                $page++;
                $p = ceil($result['totalItem'] / 50);
            } while ($page <= $p);

            echo date('Y-m-d H:i:s') . " step5 puid=$puid,sellerloginid=$sellerloginid,totalOrderNum=$totalOrderNum" . PHP_EOL;
            $timeMS3 = TimeUtil::getCurrentTimestampMS();
            // 是否全部同步成功
            if ($success || $result['totalItem'] == 0) {
                $SAA_obj->start_time = $start_time;
                $SAA_obj->end_time = $end_time;
                $SAA_obj->status = 2;
                $SAA_obj->times = 0;
                $SAA_obj->message = '';
                $SAA_obj->next_time = time() + 3600;

                //记录最后同步成功的时间段
                RedisHelper::RedisSet('Aliexpress_getOrderListByTime_redis','puid_'.$puid.'_sellerloginid_'.$sellerloginid,$end_time);
                
            } else {
                $SAA_obj->message .= isset($result ['error_message']) ? $result['error_message'] : '接口返回结果错误V1' . print_r($result, true);
                $SAA_obj->status = 3;
                $SAA_obj->times += 1;
                $SAA_obj->next_time = time() + 1800;
            }
            $SAA_obj->last_time = $time;
            $bool = $SAA_obj->save(false);
            if (!$bool) {
                \Yii::info("step6 error puid=$puid,sellerloginid=$sellerloginid,totalOrderNum=$totalOrderNum error_msg : " . var_export($SAA_obj->errors, true), "file");
            }
            $timeMS4 = TimeUtil::getCurrentTimestampMS();

            $timeStr = "t4_t3=" . ($timeMS4 - $timeMS3) . ",t3_t2=" . ($timeMS3 - $timeMS2) . ",t2_t1=" . ($timeMS2 - $timeMS1) . ",t4_t1=" . ($timeMS4 - $timeMS1);

            echo date('Y-m-d H:i:s') . " step6 one shop end puid=$puid,sellerloginid=$sellerloginid,totalOrderNum=$totalOrderNum " . $timeStr . PHP_EOL;

            \Yii::info("step6 one shop end puid=$puid,sellerloginid=$sellerloginid,totalOrderNum=$totalOrderNum " . $timeStr, "file");

        }
        echo date('Y-m-d H:i:s') . ' time script end ' . self::$cronJobId . PHP_EOL;
        return $hasGotRecord;
    }


    public static function firstToDb()
    {
        //Step 0, 检查当前本job版本，如果和自己的版本不一样，就自动exit吧
        $currentAliexpressGetOrderListVersion = ConfigHelper::getGlobalConfig("Order/aliexpressGetOrderListVersion", 'NO_CACHE');
        if (empty($currentAliexpressGetOrderListVersion)) {
            $currentAliexpressGetOrderListVersion = 0;
        }
        //如果自己还没有定义，去使用global config来初始化自己
        if (empty(self::$aliexpressGetOrderListVersion)) {
            self::$aliexpressGetOrderListVersion = $currentAliexpressGetOrderListVersion;
        }
        //如果自己的version不同于全局要求的version，自己退出，从新起job来使用新代码
        if (self::$aliexpressGetOrderListVersion <> $currentAliexpressGetOrderListVersion) {
            exit("Version new $currentAliexpressGetOrderListVersion , this job ver " . self::$aliexpressGetOrderListVersion . " exits for using new version $currentAliexpressGetOrderListVersion.");
        }

        self::$cronJobId++;
        echo date('Y-m-d H:i:s') . ' first to db script start ' . self::$cronJobId . PHP_EOL;

        // 同步订单
        $connection = Yii::$app->db_queue;
        $now = time();
        $hasGotRecord = false;
        //查新订单
        $sql = 'select `id`,`sellerloginid`,`status`,`orderid`,`last_time`,`order_status`, `type` from  `queue_aliexpress_getorder` where `status` <> 1 and `type` = 3 AND `times` < 10  limit 100';
        $dataReader = $connection->createCommand($sql)->query();
        echo date('Y-m-d H:i:s') . ' select count ' . $dataReader->count() . PHP_EOL;
        while (false !== ($row = $dataReader->read())) {
            $now = time();
            $timeMS1 = TimeUtil::getCurrentTimestampMS();
            //1. 先判断是否可以正常抢到该记录
            $affectRows = $connection->createCommand("update `queue_aliexpress_getorder` set status=1,update_time={$now} where id ={$row['id']} and status<>1 ")->execute();
            if ($affectRows <= 0) {
                continue; //抢不到
            }

            //2. 抢到记录，设置同步需要的参数
            $hasGotRecord = true;
            $QAG_obj = QueueAliexpressGetorder::findOne($row['id']);
            if (!$QAG_obj) {
                echo date('Y-m-d H:i:s') . ' exception ' . $row['orderid'] . PHP_EOL;
                continue;
            }
            echo date('Y-m-d H:i:s') . ' api start ' . $QAG_obj->orderid . PHP_EOL;
            $timeMS2 = TimeUtil::getCurrentTimestampMS();
            // 检查授权是否过期或者是否授权,返回true，false
            if (!AliexpressInterface_Auth::checkToken($QAG_obj->sellerloginid)) {
                $QAG_obj->message .= " {$QAG_obj->sellerloginid} Unauthorized or expired!";
                $QAG_obj->last_time = $now;
                $QAG_obj->status = 3;
                $QAG_obj->times += 1;
                $bool = $QAG_obj->save(false);
                if (!$bool) {
                    echo __FUNCTION__ . "STEP 1 : " . var_export($QAG_obj->errors, true);
                }
                continue;
            }

            $api = new AliexpressInterface_Api ();
            $access_token = $api->getAccessToken($QAG_obj->sellerloginid);
            //获取访问token失败
            if ($access_token === false) {
                $QAG_obj->message .= " {$QAG_obj->sellerloginid} not getting access token!";
                $QAG_obj->status = 3;
                $QAG_obj->times += 1;
                $QAG_obj->last_time = $now;
                $bool = $QAG_obj->save(false);
                if (!$bool) {
                    echo __FUNCTION__ . "STEP 2 : " . var_export($QAG_obj->errors, true);
                }
                continue;
            }

            $api->access_token = $access_token;
            // 接口传入参数速卖通订单号
            $param = ['orderId' => $row['orderid']];
            // 调用接口获取订单列表
            $result = $api->findOrderById($param);
            echo date('Y-m-d H:i:s') . ' api end ' . $QAG_obj->orderid . PHP_EOL;
            //是否同步成功
            if (isset ($result ['error_message']) || empty ($result)) {
                $QAG_obj->message .= isset ($result ['error_message']) ? $result ['error_message'] . " findOrderById " : 'findOrderById接口返回错误，在新订单入库时';
                $QAG_obj->status = 3;
                $QAG_obj->times += 1;
                $QAG_obj->last_time = $now;
                $bool = $QAG_obj->save(false);
                if (!$bool) {
                    echo __FUNCTION__ . "STEP 3 : " . var_export($QAG_obj->errors, true);
                }
                continue;
            }

            echo date('Y-m-d H:i:s') . ' save start ' . $QAG_obj->uid . ' ' . $result['id'] . PHP_EOL;
            $timeMS3 = TimeUtil::getCurrentTimestampMS();
            $uid = $QAG_obj->uid;
             

            //平台订单id必须为字符串，不然后面在sql作为搜索where字段的时候，就使用不了索引！！！！！！
            $result['id'] = strval($QAG_obj->orderid);

            //速卖通返回的sellerOperatorLoginId是子账号的loginid（就算账号绑定的是主账号）
            //这里强制转成主账号loginid，这是为了后面保存到订单od_order_v2的订单信息能对应上绑定的账号
            $result["sellerOperatorLoginId"] = $QAG_obj->sellerloginid;
            $r = AliexpressInterface_Helper::saveAliexpressOrder($QAG_obj, $result);
            // 判断是否付款并且保存成功,是则删除数据，否则继续同步
            if ($r['success'] != 0 || !isset($result['orderStatus'])) {
                $QAG_obj->status = 3;
                $QAG_obj->times += 1;
                $QAG_obj->message .= "速卖通订单saveAliexpressOrder " . $QAG_obj->orderid . "保存失败" . $r ['message'];
                $bool = $QAG_obj->save(false);
                if (!$bool) {
                    echo __FUNCTION__ . "STEP 5 : " . var_export($QAG_obj->errors, true);
                }
                continue;
            }

            $timeMS4 = TimeUtil::getCurrentTimestampMS();

            if ($result ['orderStatus'] == 'FINISH') {
                $bool = $QAG_obj->delete();
                if (!$bool) {
                    echo __FUNCTION__ . "STEP 6 : " . var_export($QAG_obj->errors, true);
                }
            } else {
                //写入临时表,待更新队列
                $QAG_two = new QueueAliexpressGetorder2();
                $QAG_two->uid = $QAG_obj->uid;
                $QAG_two->sellerloginid = $QAG_obj->sellerloginid;
                $QAG_two->aliexpress_uid = $QAG_obj->aliexpress_uid;
                $QAG_two->status = 2;
                $QAG_two->type = QueueAliexpressGetorder::NOFINISH;
                $QAG_two->order_status = $result ['orderStatus'];
                $QAG_two->orderid = $QAG_obj->orderid;
                $QAG_two->times = 0;
                $QAG_two->order_info = $QAG_obj->order_info;
                $QAG_two->last_time = $now;
                $QAG_two->gmtcreate = $QAG_obj->gmtcreate;
                $QAG_two->message = '';
                $QAG_two->create_time = $QAG_obj->create_time;
                $QAG_two->update_time = $now;
                $QAG_two->next_time = self::calcNextSyncTime($QAG_obj->uid, $result['orderStatus']);
                $bool = $saveRes = $QAG_two->save();
                if (!$bool) {
                    echo __FUNCTION__ . "STEP 7 : " . var_export($QAG_two->errors, true);
                }

                //写入队列4
                $QAG_four = new QueueAliexpressGetorder4();
                $QAG_four->uid = $QAG_obj->uid;
                $QAG_four->sellerloginid = $QAG_obj->sellerloginid;
                $QAG_four->aliexpress_uid = $QAG_obj->aliexpress_uid;
                $QAG_four->order_status = $result ['orderStatus'];
                $QAG_four->orderid = $QAG_obj->orderid;
                $QAG_four->order_info = $QAG_obj->order_info;
                $QAG_four->gmtcreate = $QAG_obj->gmtcreate;
                $boolfour = $QAG_four->save();

                if( $result ['orderStatus']=='RISK_CONTROL' || $result['orderStatus']=='WAIT_SELLER_SEND_GOODS' ){
                    //获取list数据
                    $paramx = array(
                        'page' => 1,
                        'pageSize' => 50,
                    );
                    $paramx['createDateStart'] = date("m/d/Y H:i:s", $QAG_obj->gmtcreate);
                    $paramx['createDateEnd'] = date("m/d/Y H:i:s", $QAG_obj->gmtcreate);
                    $paramx['orderStatus']= $result ['orderStatus'];
                    \Yii::info($paramx, "file");
                    \Yii::info(json_encode($paramx), "file");
                    $api_time= time();//接口调用时间
                    $result2 = $api->findOrderListQuery($paramx);
                    \Yii::info('firstToDb-findOrderListQuery---'.$QAG_obj->orderid.'----'.json_encode($result2), "file");
                    if( isset( $result2['totalItem'] ) && isset( $result2['orderList'] ) ){
                        if( !empty( $result2['orderList'] ) ){
                            $logisticsServiceName_arr= array();
                            foreach ( $result2 ['orderList'] as $ordervs ) {
                                if( isset( $ordervs['productList'] ) ){
                                    foreach( $ordervs['productList'] as $pl ){
                                        //客选物流
                                        if( isset( $pl['logisticsServiceName'] ) ){
                                            $logisticsServiceName= $pl['logisticsServiceName'];
                                            $productid= $pl['productId'];
                                            $logisticsServiceName_arr["shipping_service"][$productid]= $logisticsServiceName;
                                        }
                                    }
                                }
                            }
                        }
                    }

                    if( !empty( $logisticsServiceName_arr ) ){
                        print_r ($logisticsServiceName_arr);
                        list( $order_status,$msg )= OrderHelper::updateOrderAddiInfoByOrderID( $QAG_obj->orderid, $QAG_obj->sellerloginid , 'aliexpress' , $logisticsServiceName_arr );
                        if( $order_status===false ){
                            echo $QAG_obj->orderid.'可选物流更新失败--'.$msg.PHP_EOL;
                        }else{
                            echo $QAG_obj->orderid.'可选物流更新成功--'.$msg.PHP_EOL;
                        }
                    }
                }



                if ($saveRes) {
                    $bool = $QAG_obj->delete();
                    if (!$bool) {
                        echo __FUNCTION__ . "STEP 8 : " . var_export($QAG_obj->errors, true);
                    }
                } else {
                    $QAG_obj->status = 3;
                    $QAG_obj->times += 1;
                    //$QAG_obj->message .= "速卖通订单" . $QAG_obj->orderid . "保存失败".$r ['message'];
                    $QAG_obj->message .= "QAG_two->save fails ---" . print_r($QAG_two->errors, true);

                    $bool = $QAG_obj->save(false);
                    if (!$bool) {
                        echo __FUNCTION__ . "STEP 9 : " . var_export($QAG_obj->errors, true);
                    }
                }
            }
            echo date('Y-m-d H:i:s') . ' save end ' . $uid . ' ' . $result['id'] . PHP_EOL;
            $timeMS5 = TimeUtil::getCurrentTimestampMS();

            $logStr = "aliexpress_firsttodb_finish puid=$uid,t2_1=" . ($timeMS2 - $timeMS1) .
                ",t3_2=" . ($timeMS3 - $timeMS2) . ",t4_3=" . ($timeMS4 - $timeMS3) . ",t5_4=" . ($timeMS5 - $timeMS4) . ",t5_1=" . ($timeMS5 - $timeMS1);

            echo $logStr . "\n";
            \Yii::info($logStr, "file");

        }
        echo date('Y-m-d H:i:s') . ' first to db script end ' . self::$cronJobId . PHP_EOL;
        return $hasGotRecord;
    }


    static function getOrderFinish($type, $orderBy = "id", $time_interval = 1800)
    {
        //Step 0, 检查当前本job版本，如果和自己的版本不一样，就自动exit吧
        $currentAliexpressGetOrderListVersion = ConfigHelper::getGlobalConfig("Order/aliexpressGetOrderListVersion", 'NO_CACHE');
        if (empty($currentAliexpressGetOrderListVersion))
            $currentAliexpressGetOrderListVersion = 0;

        //如果自己还没有定义，去使用global config来初始化自己
        if (empty(self::$aliexpressGetOrderListVersion))
            self::$aliexpressGetOrderListVersion = $currentAliexpressGetOrderListVersion;

        //如果自己的version不同于全局要求的version，自己退出，从新起job来使用新代码
        if (self::$aliexpressGetOrderListVersion <> $currentAliexpressGetOrderListVersion) {
            exit("Version new $currentAliexpressGetOrderListVersion , this job ver " . self::$aliexpressGetOrderListVersion . " exits for using new version $currentAliexpressGetOrderListVersion.");
        }
        $logTimeMS2_1 = TimeUtil::getCurrentTimestampMS();
        $backgroundJobId = self::getCronJobId();
        // 同步订单
        $connection = Yii::$app->db_queue;
        $order_status = array('PLACE_ORDER_SUCCESS', 'IN_CANCEL', 'WAIT_SELLER_SEND_GOODS', 'IN_ISSUE', 'IN_FROZEN', 'WAIT_SELLER_EXAMINE_MONEY', 'RISK_CONTROL');
        //只区分type是不是finish
        $t = time();
        $table = 'queue_aliexpress_getfinishorder';
        $command = $connection->createCommand('select `id`,`sellerloginid`,`status`,`orderid`,`last_time`,`order_status` from  `' . $table . '` where `status` <> 1 AND `times` < 10 AND next_time < ' . $t . '  limit 100');

        $dataReader = $command->query();
        $hasGotRecord = false;
        $logTimeMS2_2 = TimeUtil::getCurrentTimestampMS();
        \Yii::info("aliexress_select_order_" . $type . " jobid=$backgroundJobId t2_1=" . ($logTimeMS2_2 - $logTimeMS2_1), "file");
        while (($row = $dataReader->read()) !== false) {
            $logTimeMS1 = TimeUtil::getCurrentTimestampMS(); //获取当前时间戳，毫秒为单位，该数值只有比较意义。
            $last_time = time();
            echo $row['orderid'] . "\n";
            //1. 先判断是否可以正常抢到该记录
            $command = $connection->createCommand("update `" . $table . "` set status=1 where id =" . $row['id'] . " and status<>1 ");
            $affectRows = $command->execute();
            if ($affectRows <= 0) continue; //抢不到
            $logTimeMS2 = TimeUtil::getCurrentTimestampMS();
            //2. 抢到记录，设置同步需要的参数
            $hasGotRecord = true;
            $QAG_obj = QueueAliexpressGetfinishorder::findOne($row['id']);
            // 检查授权是否过期或者是否授权,返回true，false
            $a = AliexpressInterface_Auth::checkToken($row['sellerloginid']);
            if ($a) {
                $api = new AliexpressInterface_Api ();
                $access_token = $api->getAccessToken($row['sellerloginid']);
                //获取访问token失败
                if ($access_token === false) {
                    echo $QAG_obj->sellerloginid . 'not getting access token!' . "\n";
                    \Yii::info("aliexress_get_order_" . $type . " token_not_access jobid=$backgroundJobId,sid=" . $QAG_obj->sellerloginid . ",puid=" . $QAG_obj->uid, "file");
                    $QAG_obj->message = $QAG_obj->sellerloginid . ' not getting access token!';
                    $QAG_obj->status = 3;
                    $QAG_obj->times += 1;
                    $QAG_obj->last_time = $last_time;
                    $QAG_obj->update_time = time();
                    $QAG_obj->next_time = time() + 3600;
                    $bool = $QAG_obj->save(false);
                    if (!$bool) {
                        echo __FUNCTION__ . "STEP 1 : " . var_export($QAG_obj->errors, true);
                    }
                    continue;
                }
                $api->access_token = $access_token;
                // 接口传入参数速卖通订单号
                $param = array(
                    'orderId' => $row['orderid']
                );
                // 调用接口获取订单列表
                $result = $api->findOrderById($param);

                $logTimeMS3 = TimeUtil::getCurrentTimestampMS();

                // 是否同步成功
                if (isset ($result ['error_message']) || empty ($result)) {
                    $QAG_obj->message = $result ['error_message'];
                    $QAG_obj->status = 3;
                    $QAG_obj->times += 1;
                    $QAG_obj->last_time = $last_time;
                    $QAG_obj->update_time = time();
                    $QAG_obj->next_time = time() + 3600;
                    $bool = $QAG_obj->save(false);
                    if (!$bool) {
                        echo __FUNCTION__ . "STEP 2 : " . var_export($QAG_obj->errors, true);
                    }
                } else {
                    // 同步成功保存数据到订单表
                    if (true) {
                        //平台订单id必须为字符串，不然后面在sql作为搜索where字段的时候，就使用不了索引！！！！！！
                        if (isset($result['id'])) $result['id'] = strval($result['id']);
                        //速卖通返回的sellerOperatorLoginId是子账号的loginid（就算账号绑定的是主账号）
                        //这里强制转成主账号loginid，这是为了后面保存到订单od_order_v2的订单信息能对应上绑定的账号
                        $result["sellerOperatorLoginId"] = $QAG_obj->sellerloginid;
                        $r = AliexpressInterface_Helper::saveAliexpressOrder($QAG_obj, $result);
                        $logTimeMS4 = TimeUtil::getCurrentTimestampMS();

                        //print_r($result);
                        // 判断是否付款并且保存成功,是则删除数据，否则继续同步
                        if ($r ['success'] == 0 && isset($result ['orderStatus']) && $result ['orderStatus'] == 'FINISH') {
                            $bool = $QAG_obj->delete();
                            if (!$bool) {
                                echo __FUNCTION__ . "STEP 3 : " . var_export($QAG_obj->errors, true);
                            }
                        } else {
                            if ($r ['success'] == 1) {
                                $QAG_obj->status = 3;
                                $QAG_obj->times += 1;
                                $QAG_obj->message = "速卖通订单" . $QAG_obj->orderid . "保存失败" . $r ['message'];
                            } else {
                                $QAG_obj->status = 2;
                                $QAG_obj->times = 0;
                            }
                            $QAG_obj->order_status = isset($result ['orderStatus']) ? $result ['orderStatus'] : $QAG_obj->order_status;
                            $QAG_obj->last_time = $last_time;
                            $QAG_obj->update_time = time();
                            $QAG_obj->next_time = time() + 3600;
                            $bool = $QAG_obj->save(false);
                            if (!$bool) {
                                echo __FUNCTION__ . "STEP 4 : " . var_export($QAG_obj->errors, true);
                            }

                        }
                        $logTimeMS5 = TimeUtil::getCurrentTimestampMS();

                        \Yii::info("aliexress_get_order_" . $type . " saveok jobid=$backgroundJobId t2_1=" . ($logTimeMS2 - $logTimeMS1) .
                            ",t3_2=" . ($logTimeMS3 - $logTimeMS2) . ",t4_3=" . ($logTimeMS4 - $logTimeMS3) . ",t5_4=" . ($logTimeMS5 - $logTimeMS4) .
                            ",t5_1=" . ($logTimeMS5 - $logTimeMS1) . ",puid=" . $QAG_obj->uid, "file");

                    }
                }
            } else {
                //接口获取失败
                echo $QAG_obj->sellerloginid . ' Unauthorized or expired!' . "\n";
                \Yii::info("aliexress_get_order_" . $type . " unauthorized_or_expired jobid=$backgroundJobId,sid=" . $QAG_obj->sellerloginid . ",puid=" . $QAG_obj->uid, "file");
                $QAG_obj->message = $QAG_obj->sellerloginid . ' Unauthorized or expired!';
                $QAG_obj->status = 3;
                $QAG_obj->times += 1;
                $QAG_obj->last_time = $last_time;
                $QAG_obj->update_time = time();
                $QAG_obj->next_time = time() + 3600;
                $bool = $QAG_obj->save(false);
                if (!$bool) {
                    echo __FUNCTION__ . "STEP 5 : " . var_export($QAG_obj->errors, true);
                }
            }
        }
        return $hasGotRecord;
    }




    //根据优先级规则，计算等待队列中下一次更新时间
    //发货前订单状态，活跃用户半小时
    //发货前定案状态，非活跃用户2天
    //发货后订单状态，活跃用户1天
    //发货后订单状态，非活跃用户5天
    protected static function calcNextSyncTime($uid, $order_status)
    {
        $status = array('PLACE_ORDER_SUCCESS', 'IN_CANCEL', 'WAIT_SELLER_SEND_GOODS', 'IN_ISSUE', 'IN_FROZEN', 'WAIT_SELLER_EXAMINE_MONEY', 'RISK_CONTROL');

        $next_time = 0;
        if (in_array($order_status, $status)) {
            if (self::isActiveUser($uid)) {
                //$next_time = time() + 1800;
                $next_time = time() + 10800;
            } else {
                //$next_time = time() + 172800;
                $next_time = time() + 1036800;
            }
        } else {
            if (self::isActiveUser($uid)) {
                //$next_time = time() + 86400;
                $next_time = time() + 518400;
            } else {
                //$next_time = time() + 432000;
                $next_time = time() + 2592000;
            }
        }

        return $next_time;
    }


    /**
     * 更新订单状态
     * @author million 2015-04-03
     * 88028624@qq.com
     */
    public static function getOrder2($type, $orderBy = "id", $time_interval = 1800)
    {
        //Step 0, 检查当前本job版本，如果和自己的版本不一样，就自动exit吧
        $currentAliexpressGetOrderListVersion = ConfigHelper::getGlobalConfig("Order/aliexpressGetOrderListVersion", 'NO_CACHE');
        if (empty($currentAliexpressGetOrderListVersion)) {
            $currentAliexpressGetOrderListVersion = 0;
        }
        //如果自己还没有定义，去使用global config来初始化自己
        if (empty(self::$aliexpressGetOrderListVersion)) {
            self::$aliexpressGetOrderListVersion = $currentAliexpressGetOrderListVersion;
        }
        //如果自己的version不同于全局要求的version，自己退出，从新起job来使用新代码
        if (self::$aliexpressGetOrderListVersion <> $currentAliexpressGetOrderListVersion) {
            exit("Version new $currentAliexpressGetOrderListVersion , this job ver " . self::$aliexpressGetOrderListVersion . " exits for using new version $currentAliexpressGetOrderListVersion.");
        }

        self::$cronJobId++;
        echo date('Y-m-d H:i:s') . ' order status change script start ' . self::$cronJobId . PHP_EOL;

        // 同步订单
        $connection = Yii::$app->db_queue;
        $now = time();
        $hasGotRecord = false;


        //查询队列里的非新订单
        $sql = "select `id`,`sellerloginid`,`status`,`orderid`,`last_time`,`order_status`, `type` from  queue_aliexpress_getorder where `status` <> 1 and type=5 AND `times` < 10  limit 100";
        $dataReader = $connection->createCommand($sql)->query();
        echo date('Y-m-d H:i:s') . ' select count ' . $dataReader->count() . PHP_EOL;
        while (false !== ($row = $dataReader->read())) {
            TimeUtil::beginTimestampMark("aligetorder2");

            TimeUtil::markTimestamp("t1", "aligetorder2");


            //1. 先判断是否可以正常抢到该记录
            $affectRows = $connection->createCommand("update `queue_aliexpress_getorder` set status=1,update_time={$now} where id ={$row['id']} and status<>1 ")->execute();
            if ($affectRows <= 0) {
                continue; //抢不到
            }
            //2. 抢到记录，设置同步需要的参数
            $hasGotRecord = true;

            TimeUtil::markTimestamp("t2", "aligetorder2");

            $QAG_obj = QueueAliexpressGetorder::findOne($row['id']);
            if (!$QAG_obj) {
                echo date('Y-m-d H:i:s') . ' exception ' . $row['orderid'] . PHP_EOL;
                continue;
            }
            $puid = $QAG_obj->uid;

            TimeUtil::markTimestamp("t3", "aligetorder2");
            echo date('Y-m-d H:i:s') . " api start uid:{$puid} orderId:{$QAG_obj->orderid} " . PHP_EOL;
            // 检查授权是否过期或者是否授权,返回true，false
            if (!AliexpressInterface_Auth::checkToken($QAG_obj->sellerloginid)) {
                $QAG_obj->message .= " {$QAG_obj->sellerloginid} Unauthorized or expired!";
                $QAG_obj->last_time = $now;
                $QAG_obj->status = 3;
                $QAG_obj->times += 1;
                $bool = $QAG_obj->save(false);
                if (!$bool) {
                    echo __FUNCTION__ . "STEP 1 : " . var_export($QAG_obj->errors, true);
                }
                continue;
            }


            $api = new AliexpressInterface_Api ();
            $access_token = $api->getAccessToken($QAG_obj->sellerloginid);
            //获取访问token失败
            if ($access_token === false) {
                $QAG_obj->message .= " {$QAG_obj->sellerloginid} not getting access token!";
                $QAG_obj->status = 3;
                $QAG_obj->times += 1;
                $QAG_obj->last_time = $now;
                $bool = $QAG_obj->save(false);
                if (!$bool) {
                    echo __FUNCTION__ . "STEP 2 : " . var_export($QAG_obj->errors, true);
                }
                continue;
            }

            $api->access_token = $access_token;

            //$timeMS4=TimeUtil::getCurrentTimestampMS();
            TimeUtil::markTimestamp("t4", "aligetorder2");
            $orderId = $QAG_obj->orderid;
            // 接口传入参数速卖通订单号
            $param = ['orderId' => $row['orderid']];
            // 调用接口获取订单列表
            $result = $api->findOrderById($param);
            //$timeMS5=TimeUtil::getCurrentTimestampMS();
            TimeUtil::markTimestamp("t5", "aligetorder2");

            echo date('Y-m-d H:i:s') . " api end uid:{$puid} orderId:{$QAG_obj->orderid} " . PHP_EOL;
            // 是否同步成功
            if (isset ($result['error_message']) || empty ($result)) {
                $QAG_obj->message .= isset ($result ['error_message']) ? $result ['error_message'] : '接口返回错误，在订单状态变更检查时';
                $QAG_obj->status = 3;
                $QAG_obj->times += 1;
                $QAG_obj->last_time = $now;
                $bool = $QAG_obj->save(false);
                if (!$bool) {
                    echo __FUNCTION__ . "STEP 3 : " . var_export($QAG_obj->errors, true);
                }
                continue;
            }

            //订单未完成，且状态没有改变，则回到等待队列中
            if ($QAG_obj->order_status === $result['orderStatus'] && $result['orderStatus'] !== 'FINISH') {
                $QAG_two = new QueueAliexpressGetorder2();
                $QAG_two->uid = $QAG_obj->uid;
                $QAG_two->sellerloginid = $QAG_obj->sellerloginid;
                $QAG_two->aliexpress_uid = $QAG_obj->aliexpress_uid;
                $QAG_two->status = 2;
                $QAG_two->type = $QAG_obj->type;
                $QAG_two->order_status = $QAG_obj->order_status;
                $QAG_two->orderid = $QAG_obj->orderid;
                $QAG_two->times = 0;
                $QAG_two->order_info = $QAG_obj->order_info;
                $QAG_two->last_time = $now;
                $QAG_two->gmtcreate = $QAG_obj->gmtcreate;
                $QAG_two->message = '';
                $QAG_two->create_time = $QAG_obj->create_time;
                $QAG_two->update_time = $now;
                $QAG_two->next_time = self::calcNextSyncTime($QAG_obj->uid, $QAG_obj->order_status);
                $bool = $saveRes = $QAG_two->save(false);
                if (!$bool) {
                    echo __FUNCTION__ . "STEP 4 : " . var_export($QAG_two->errors, true);
                }
                if ($saveRes) {
                    $bool = $QAG_obj->delete();
                    if (!$bool) {
                        echo __FUNCTION__ . "STEP 5 : " . var_export($QAG_obj->errors, true);
                    }
                } else {
                    $QAG_obj->message .= ' 保存等待队列失败';
                    $QAG_obj->status = 3;
                    $QAG_obj->times += 1;
                    $QAG_obj->last_time = $now;
                    $bool = $QAG_obj->save(false);
                    if (!$bool) {
                        echo __FUNCTION__ . "STEP 6 : " . var_export($QAG_obj->errors, true);
                    }
                }
                //$timeMS6=TimeUtil::getCurrentTimestampMS();
                TimeUtil::markTimestamp("t6", "aligetorder2");

                //\Yii::info("ali_getorder2 orderId=$orderId,t2-t1 ","file");

                \Yii::info("ali_getorder2 orderId=$orderId," . TimeUtil::getTimestampMarkInfo("aligetorder2"), "file");


                continue;
            }

            TimeUtil::markTimestamp("t7", "aligetorder2");
            echo date('Y-m-d H:i:s') . " save start uid:{$puid} orderId:{$QAG_obj->orderid} " . PHP_EOL;
            $uid = $QAG_obj->uid;
             
            TimeUtil::markTimestamp("t8", "aligetorder2");

            //平台订单id必须为字符串，不然后面在sql作为搜索where字段的时候，就使用不了索引！！！！！！
            $result['id'] = strval($result['id']);

            //速卖通返回的sellerOperatorLoginId是子账号的loginid（就算账号绑定的是主账号）
            //这里强制转成主账号loginid，这是为了后面保存到订单od_order_v2的订单信息能对应上绑定的账号
            $result["sellerOperatorLoginId"] = $QAG_obj->sellerloginid;
            $r = AliexpressInterface_Helper::saveAliexpressOrder($QAG_obj, $result);
            TimeUtil::markTimestamp("t9", "aligetorder2");
            // 判断是否付款并且保存成功,是则删除数据，否则继续同步
            if ($r['success'] != 0 || !isset($result['orderStatus'])) {
                $QAG_obj->status = 3;
                $QAG_obj->times += 1;
                $QAG_obj->message .= "速卖通订单" . $QAG_obj->orderid . "保存失败" . $r ['message'];
                $bool = $QAG_obj->save(false);
                if (!$bool) {
                    echo __FUNCTION__ . "STEP 8 : " . var_export($QAG_obj->errors, true);
                }
                continue;
            }

            if ($result ['orderStatus'] == 'FINISH') {
                $bool = $QAG_obj->delete();
                if (!$bool) {
                    echo __FUNCTION__ . "STEP 9 : " . var_export($QAG_obj->errors, true);
                }
            } else {
                //写入等待队列
                $QAG_two = new QueueAliexpressGetorder2();
                $QAG_two->uid = $QAG_obj->uid;
                $QAG_two->sellerloginid = $QAG_obj->sellerloginid;
                $QAG_two->aliexpress_uid = $QAG_obj->aliexpress_uid;
                $QAG_two->status = 2;
                $QAG_two->type = QueueAliexpressGetorder::NOFINISH;;
                $QAG_two->order_status = $result ['orderStatus'];
                $QAG_two->orderid = $QAG_obj->orderid;
                $QAG_two->times = 0;
                $QAG_two->order_info = $QAG_obj->order_info;
                $QAG_two->last_time = $now;
                $QAG_two->gmtcreate = $QAG_obj->gmtcreate;
                $QAG_two->message = '';
                $QAG_two->create_time = $QAG_obj->create_time;
                $QAG_two->update_time = $now;
                $QAG_two->next_time = self::calcNextSyncTime($QAG_obj->uid, $result['orderStatus']);
                $saveRes = $QAG_two->save();
                if (!$saveRes) {
                    echo __FUNCTION__ . "STEP 10 : " . var_export($QAG_two->errors, true);
                }
                if ($saveRes) {
                    $bool = $QAG_obj->delete();
                    if (!$bool) {
                        echo __FUNCTION__ . "STEP 11 : " . var_export($QAG_obj->errors, true);
                    }
                } else {
                    $QAG_obj->status = 3;
                    $QAG_obj->times += 1;
                    $QAG_obj->message .= "速卖通订单" . $QAG_obj->orderid . "保存失败" . $r ['message'];
                    $bool = $QAG_obj->save(false);
                    if (!$bool) {
                        echo __FUNCTION__ . "STEP 12 : " . var_export($QAG_obj->errors, true);
                    }
                }
                TimeUtil::markTimestamp("t10", "aligetorder2");
            }
            //echo date('Y-m-d H:i:s').' save end '.$uid.' '.$result['id'].PHP_EOL;
            echo date('Y-m-d H:i:s') . " save end uid:{$puid} orderId:{$QAG_obj->orderid} " . PHP_EOL;

            \Yii::info("ali_getorder2 orderId=$orderId," . TimeUtil::getTimestampMarkInfo("aligetorder2"), "file");
        }
        echo date('Y-m-d H:i:s') . ' order status change script end ' . self::$cronJobId . PHP_EOL;
        return $hasGotRecord;
    }


    /**
     * 更新订单状态
     * @author million 2016-01-06
     * 88028624@qq.com
     */
    public static function getOrder3($type, $orderBy = "id", $time_interval = 1800)
    {
        //Step 0, 检查当前本job版本，如果和自己的版本不一样，就自动exit吧
        $currentAliexpressGetOrderListVersion = ConfigHelper::getGlobalConfig("Order/aliexpressRetryGetOrderListVersion", 'NO_CACHE');
        if (empty($currentAliexpressGetOrderListVersion)) {
            $currentAliexpressGetOrderListVersion = 0;
        }
        //如果自己还没有定义，去使用global config来初始化自己
        if (empty(self::$aliexpressGetOrderListVersion)) {
            self::$aliexpressGetOrderListVersion = $currentAliexpressGetOrderListVersion;
        }
        //如果自己的version不同于全局要求的version，自己退出，从新起job来使用新代码
        if (self::$aliexpressGetOrderListVersion <> $currentAliexpressGetOrderListVersion) {
            exit("Version new $currentAliexpressGetOrderListVersion , this job ver " . self::$aliexpressGetOrderListVersion . " exits for using new version $currentAliexpressGetOrderListVersion.");
        }

        self::$cronJobId++;
        echo date('Y-m-d H:i:s') . ' order status change script start ' . self::$cronJobId . PHP_EOL;

        // 同步订单
        $connection = Yii::$app->db_queue;
        $now = time();
        $hasGotRecord = false;


        //查询队列里的非新订单
        $sql = "select `id`,`sellerloginid`,`status`,`orderid`,`last_time`,`order_status`, `type` from  queue_aliexpress_getorder where `status` <> 1 and type=5 AND `times` < 10  limit 100";
        $dataReader = $connection->createCommand($sql)->query();
        echo date('Y-m-d H:i:s') . ' select count ' . $dataReader->count() . PHP_EOL;
        while (false !== ($row = $dataReader->read())) {
            TimeUtil::beginTimestampMark("aligetorder3");

            TimeUtil::markTimestamp("t1", "aligetorder3");

            //1. 先判断是否可以正常抢到该记录
            $affectRows = $connection->createCommand("update `queue_aliexpress_getorder` set status=1,update_time={$now} where id ={$row['id']} and status<>1 ")->execute();
            if ($affectRows <= 0) {
                continue; //抢不到
            }
            //2. 抢到记录，设置同步需要的参数
            $hasGotRecord = true;

            //执行计数
            $redis_key = date("Y-m-d H") . ",getOrder3";
            RedisHelper::RedisAdd("Aliexpress_get_order", $redis_key);

            TimeUtil::markTimestamp("t2", "aligetorder3");

            $QAG_obj = QueueAliexpressGetorder::findOne($row['id']);
            if (!$QAG_obj) {
                echo date('Y-m-d H:i:s') . ' exception ' . $row['orderid'] . PHP_EOL;
                continue;
            }
            $puid = $QAG_obj->uid;
            $orderid = $QAG_obj->orderid;
            TimeUtil::markTimestamp("t3", "aligetorder3");
            echo date('Y-m-d H:i:s') . " api start uid:{$puid} orderId:{$QAG_obj->orderid} " . PHP_EOL;
            // 检查授权是否过期或者是否授权,返回true，false
            if (!AliexpressInterface_Auth::checkToken($QAG_obj->sellerloginid)) {
                //执行计数
                $redis_key = date("Y-m-d H") . ",getOrder3_checktoken_error";
                RedisHelper::RedisAdd("Aliexpress_get_order", $redis_key);
                $bool = AliexpressClearHelper::saveRetryAccount($QAG_obj);
                if ($bool) {
                    \Yii::info("ali_getorder3 check_token_fail push_retry_account orderId={$orderid}, success", "file");
                    $bool = $QAG_obj->delete();
                    if ($bool) {
                        \Yii::info("ali_getorder3 check_token_fail delete_row orderId={$orderid}, success", "file");
                    } else {
                        \Yii::info("ali_getorder3 check_token_fail delete_row orderId={$orderid}, fail", "file");
                    }
                } else {
                    $QAG_obj->message .= " {$QAG_obj->sellerloginid} Unauthorized or expired!";
                    $QAG_obj->last_time = $now;
                    $QAG_obj->status = 3;
                    $QAG_obj->times += 1;
                    $bool = $QAG_obj->save(false);
                    if (!$bool) {
                        echo __FUNCTION__ . "STEP 1 : " . var_export($QAG_obj->errors, true);
                    }
                    \Yii::info("ali_getorder3 check_token_fail push_retry_account orderId={$orderid}, fail", "file");
                }
                continue;
            }


            $api = new AliexpressInterface_Api ();
            $access_token = $api->getAccessToken($QAG_obj->sellerloginid);
            //获取访问token失败
            if ($access_token === false) {
                //执行计数
                $redis_key = date("Y-m-d H") . ",getOrder3_gettoken_error";
                RedisHelper::RedisAdd("Aliexpress_get_order", $redis_key);
                $bool = AliexpressClearHelper::saveRetryAccount($QAG_obj);
                if ($bool) {
                    \Yii::info("ali_getorder3 get_token_fail push_retry_account orderId={$orderid}, success", "file");
                    $bool = $QAG_obj->delete();
                    if ($bool) {
                        \Yii::info("ali_getorder3 get_token_fail delete_row orderId={$orderid}, success", "file");
                    } else {
                        \Yii::info("ali_getorder3 get_token_fail delete_row orderId={$orderid}, fail", "file");
                    }
                } else {
                    $QAG_obj->message .= " {$QAG_obj->sellerloginid} not getting access token!";
                    $QAG_obj->status = 3;
                    $QAG_obj->times += 1;
                    $QAG_obj->last_time = $now;
                    $bool = $QAG_obj->save(false);
                    if (!$bool) {
                        echo __FUNCTION__ . "STEP 2 : " . var_export($QAG_obj->errors, true);
                    }
                    \Yii::info("ali_getorder3 get_token_fail push_retry_account orderId={$orderid}, fail", "file");
                }
                continue;
            }

            $api->access_token = $access_token;

            //$timeMS4=TimeUtil::getCurrentTimestampMS();
            TimeUtil::markTimestamp("t4", "aligetorder2");
            $orderId = $QAG_obj->orderid;
            // 接口传入参数速卖通订单号
            $param = ['orderId' => $row['orderid']];
            // 调用接口获取订单列表
            $result = $api->findOrderById($param);
            //$timeMS5=TimeUtil::getCurrentTimestampMS();
            TimeUtil::markTimestamp("t5", "aligetorder2");

            echo date('Y-m-d H:i:s') . " api end uid:{$puid} orderId:{$QAG_obj->orderid} " . PHP_EOL;
            // 是否同步成功
            if (isset ($result['error_message']) || empty ($result)) {
                if ($result['error_message'] == 'Beyond the app call frequency limit') {
                    //执行计数
                    $redis_key = date("Y-m-d H") . ",getOrder3_api_call_error";
                    RedisHelper::RedisAdd("Aliexpress_get_order", $redis_key);
                    $bool = AliexpressClearHelper::saveRetryOrder($QAG_obj);
                    if ($bool) {
                        \Yii::info("ali_getorder3 api_result_error push_retry_order success orderId={$orderid}, msg=" . $result['error_message'], "file");
                        $bool = $QAG_obj->delete();
                        if ($bool) {
                            \Yii::info("ali_getorder3 api_result_error push_retry_order delete_row orderId={$orderid} success", "file");
                        } else {
                            \Yii::info("ali_getorder3 api_result_error push_retry_order delete_row orderId={$orderid} fail", "file");
                        }
                    } else {
                        $QAG_obj->message .= isset ($result ['error_message']) ? $result ['error_message'] : '接口返回错误，在订单状态变更检查时';
                        $QAG_obj->status = 3;
                        $QAG_obj->times += 1;
                        $QAG_obj->last_time = $now;
                        $bool = $QAG_obj->save(false);
                        if (!$bool) {
                            echo __FUNCTION__ . "STEP 3 : " . var_export($QAG_obj->errors, true);
                        }
                        \Yii::info("ali_getorder3 api_result_error push_retry_order fail orderId={$orderid}, msg=" . $result['error_message'], "file");
                    }
                } else {
                    //执行计数
                    $redis_key = date("Y-m-d H") . ",getOrder3_api_other_error";
                    RedisHelper::RedisAdd("Aliexpress_get_order", $redis_key);
                    $bool = AliexpressClearHelper::saveRetryAccount($QAG_obj);
                    if ($bool) {
                        \Yii::info("ali_getorder3 api_result_error push_retry_account success orderId={$orderid}, msg=" . $result['error_message'], "file");
                        $bool = $QAG_obj->delete();
                        if ($bool) {
                            \Yii::info("ali_getorder3 api_result_error push_retry_account delete_row orderId={$orderid} success", "file");
                        } else {
                            \Yii::info("ali_getorder3 api_result_error push_retry_account delete_row orderId={$orderid} fail", "file");
                        }
                    } else {
                        $QAG_obj->message .= isset ($result ['error_message']) ? $result ['error_message'] : '接口返回错误，在订单状态变更检查时';
                        $QAG_obj->status = 3;
                        $QAG_obj->times += 1;
                        $QAG_obj->last_time = $now;
                        $bool = $QAG_obj->save(false);
                        if (!$bool) {
                            echo __FUNCTION__ . "STEP 3 : " . var_export($QAG_obj->errors, true);
                        }
                        \Yii::info("ali_getorder3 api_result_error push_retry_account fail orderId={$orderid}, msg=" . $result['error_message'], "file");
                    }
                }
                continue;
            }

            //订单未完成，且状态没有改变，则回到等待队列中
            if ($QAG_obj->order_status === $result['orderStatus'] && $result['orderStatus'] !== 'FINISH') {
                $QAG_two = new QueueAliexpressGetorder2();
                $QAG_two->uid = $QAG_obj->uid;
                $QAG_two->sellerloginid = $QAG_obj->sellerloginid;
                $QAG_two->aliexpress_uid = $QAG_obj->aliexpress_uid;
                $QAG_two->status = 2;
                $QAG_two->type = $QAG_obj->type;
                $QAG_two->order_status = $QAG_obj->order_status;
                $QAG_two->orderid = $QAG_obj->orderid;
                $QAG_two->times = 0;
                $QAG_two->order_info = $QAG_obj->order_info;
                $QAG_two->last_time = $now;
                $QAG_two->gmtcreate = $QAG_obj->gmtcreate;
                $QAG_two->message = '';
                $QAG_two->create_time = $QAG_obj->create_time;
                $QAG_two->update_time = $now;
                $QAG_two->next_time = self::calcNextSyncTime($QAG_obj->uid, $QAG_obj->order_status);
                $bool = $saveRes = $QAG_two->save(false);
                if (!$bool) {
                    echo __FUNCTION__ . "STEP 4 : " . var_export($QAG_two->errors, true);
                }
                if ($saveRes) {
                    $bool = $QAG_obj->delete();
                    if (!$bool) {
                        echo __FUNCTION__ . "STEP 5 : " . var_export($QAG_obj->errors, true);
                    }
                } else {
                    //执行计数
                    $redis_key = date("Y-m-d H") . ",getOrder3_savegetOrder2_error";
                    RedisHelper::RedisAdd("Aliexpress_get_order", $redis_key);
                    $QAG_obj->message .= ' 保存等待队列失败';
                    $QAG_obj->status = 3;
                    $QAG_obj->times += 1;
                    $QAG_obj->last_time = $now;
                    $bool = $QAG_obj->save(false);
                    if (!$bool) {
                        echo __FUNCTION__ . "STEP 6 : " . var_export($QAG_obj->errors, true);
                    }
                }
                TimeUtil::markTimestamp("t6", "aligetorder3");

                \Yii::info("ali_getorder2 orderId=$orderId," . TimeUtil::getTimestampMarkInfo("aligetorder3"), "file");


                continue;
            }

            TimeUtil::markTimestamp("t7", "aligetorder3");
            echo date('Y-m-d H:i:s') . " save start uid:{$puid} orderId:{$QAG_obj->orderid} " . PHP_EOL;
            $uid = $QAG_obj->uid;
            // 同步成功保存数据到订单表
             
            TimeUtil::markTimestamp("t8", "aligetorder2");

            //平台订单id必须为字符串，不然后面在sql作为搜索where字段的时候，就使用不了索引！！！！！！
            $result['id'] = strval($result['id']);

            //速卖通返回的sellerOperatorLoginId是子账号的loginid（就算账号绑定的是主账号）
            //这里强制转成主账号loginid，这是为了后面保存到订单od_order_v2的订单信息能对应上绑定的账号
            $result["sellerOperatorLoginId"] = $QAG_obj->sellerloginid;
            $r = AliexpressInterface_Helper::saveAliexpressOrder($QAG_obj, $result);
            TimeUtil::markTimestamp("t9", "aligetorder2");
            // 判断是否付款并且保存成功,是则删除数据，否则继续同步
            if ($r['success'] != 0 || !isset($result['orderStatus'])) {
                //执行计数
                $redis_key = date("Y-m-d H") . ",getOrder3_saveAliexpressOrder_error";
                RedisHelper::RedisAdd("Aliexpress_get_order", $redis_key);
                $bool = AliexpressClearHelper::saveRetryOrder($QAG_obj);
                if ($bool) {
                    $bool = $QAG_obj->delete();
                    if ($bool) {
                        \Yii::info("ali_getorder3 saveAliexpressOrder delete_row orderId={$orderid} success", "file");
                    } else {
                        \Yii::info("ali_getorder3 saveAliexpressOrder delete_row orderId={$orderid} fail", "file");
                    }
                    \Yii::info("ali_getorder3 saveAliexpressOrder error push_retry_order success orderId={$orderid},", "file");
                } else {
                    \Yii::info("ali_getorder3 saveAliexpressOrder error push_retry_order error orderId={$orderid},", "file");
                    $QAG_obj->status = 3;
                    $QAG_obj->times += 1;
                    $QAG_obj->message .= "速卖通订单" . $QAG_obj->orderid . "保存失败" . $r ['message'];
                    $bool = $QAG_obj->save(false);
                    if (!$bool) {
                        echo __FUNCTION__ . "STEP 8 : " . var_export($QAG_obj->errors, true);
                    }
                }
                continue;
            }

            if ($result ['orderStatus'] == 'FINISH') {
                $bool = $QAG_obj->delete();
                if (!$bool) {
                    echo __FUNCTION__ . "STEP 9 : " . var_export($QAG_obj->errors, true);
                }
            } else {
                //写入等待队列
                $QAG_two = new QueueAliexpressGetorder2();
                $QAG_two->uid = $QAG_obj->uid;
                $QAG_two->sellerloginid = $QAG_obj->sellerloginid;
                $QAG_two->aliexpress_uid = $QAG_obj->aliexpress_uid;
                $QAG_two->status = 2;
                $QAG_two->type = QueueAliexpressGetorder::NOFINISH;;
                $QAG_two->order_status = $result ['orderStatus'];
                $QAG_two->orderid = $QAG_obj->orderid;
                $QAG_two->times = 0;
                $QAG_two->order_info = $QAG_obj->order_info;
                $QAG_two->last_time = $now;
                $QAG_two->gmtcreate = $QAG_obj->gmtcreate;
                $QAG_two->message = '';
                $QAG_two->create_time = $QAG_obj->create_time;
                $QAG_two->update_time = $now;
                $QAG_two->next_time = self::calcNextSyncTime($QAG_obj->uid, $result['orderStatus']);
                $saveRes = $QAG_two->save();
                if ($saveRes) {
                    $bool = $QAG_obj->delete();
                    if (!$bool) {
                        echo __FUNCTION__ . "STEP 11 : " . var_export($QAG_obj->errors, true);
                    }
                } else {
                    //执行计数
                    $redis_key = date("Y-m-d H") . ",getOrder3_updategetOrder2_error";
                    RedisHelper::RedisAdd("Aliexpress_get_order", $redis_key);
                    echo __FUNCTION__ . "STEP 10 : " . var_export($QAG_two->errors, true);
                    $QAG_obj->status = 3;
                    $QAG_obj->times += 1;
                    $QAG_obj->message .= "速卖通订单" . $QAG_obj->orderid . "保存失败" . $r ['message'];
                    $bool = $QAG_obj->save(false);
                    if (!$bool) {
                        echo __FUNCTION__ . "STEP 12 : " . var_export($QAG_obj->errors, true);
                    }
                }
                TimeUtil::markTimestamp("t10", "aligetorder3");
            }
            //echo date('Y-m-d H:i:s').' save end '.$uid.' '.$result['id'].PHP_EOL;
            echo date('Y-m-d H:i:s') . " save end uid:{$puid} orderId:{$QAG_obj->orderid} " . PHP_EOL;

            \Yii::info("ali_getorder3 orderId=$orderId," . TimeUtil::getTimestampMarkInfo("aligetorder3"), "file");
        }
        echo date('Y-m-d H:i:s') . ' order status change script end ' . self::$cronJobId . PHP_EOL;
        return $hasGotRecord;
    }


    /**
     * 更新订单状态
     * @author million 2016-01-06
     * 88028624@qq.com
     */
    public static function getOrder4($type, $orderBy = "id", $time_interval = 1800)
    {
        //Step 0, 检查当前本job版本，如果和自己的版本不一样，就自动exit吧
        $currentAliexpressGetOrderListVersion = ConfigHelper::getGlobalConfig("Order/aliexpressRetryGetOrderListVersion2", 'NO_CACHE');
        if (empty($currentAliexpressGetOrderListVersion)) {
            $currentAliexpressGetOrderListVersion = 0;
        }
        //如果自己还没有定义，去使用global config来初始化自己
        if (empty(self::$aliexpressGetOrderListVersion)) {
            self::$aliexpressGetOrderListVersion = $currentAliexpressGetOrderListVersion;
        }
        //如果自己的version不同于全局要求的version，自己退出，从新起job来使用新代码
        if (self::$aliexpressGetOrderListVersion <> $currentAliexpressGetOrderListVersion) {
            exit("Version new $currentAliexpressGetOrderListVersion , this job ver " . self::$aliexpressGetOrderListVersion . " exits for using new version $currentAliexpressGetOrderListVersion.");
        }

        self::$cronJobId++;
        echo date('Y-m-d H:i:s') . ' order status change script start ' . self::$cronJobId . PHP_EOL;

        // 同步订单
        $connection = Yii::$app->db_queue;
        $now = time();
        $hasGotRecord = false;


        //查询队列里的非新订单
        $sql = "select `id`,`sellerloginid`,`status`,`orderid`,`last_time`,`order_status`, `type` from  queue_aliexpress_getorder where `status` <> 1 and type=5 AND `times` < 10  limit 100";
        $dataReader = $connection->createCommand($sql)->query();
        echo date('Y-m-d H:i:s') . ' select count ' . $dataReader->count() . PHP_EOL;
        while (false !== ($row = $dataReader->read())) {
            TimeUtil::beginTimestampMark("aligetorder4");

            TimeUtil::markTimestamp("t1", "aligetorder4");

            //1. 先判断是否可以正常抢到该记录
            $affectRows = $connection->createCommand("update `queue_aliexpress_getorder` set status=1,update_time={$now} where id ={$row['id']} and status<>1 ")->execute();
            if ($affectRows <= 0) {
                continue; //抢不到
            }
            //2. 抢到记录，设置同步需要的参数
            $hasGotRecord = true;

            //执行计数
            $redis_key = date("Y-m-d H") . ",getOrder4";
            RedisHelper::RedisAdd("Aliexpress_get_order", $redis_key);

            TimeUtil::markTimestamp("t2", "aligetorder4");

            $QAG_obj = QueueAliexpressGetorder::findOne($row['id']);
            if (!$QAG_obj) {
                echo date('Y-m-d H:i:s') . ' exception ' . $row['orderid'] . PHP_EOL;
                continue;
            }
            $puid = $QAG_obj->uid;
            $orderid = $QAG_obj->orderid;
            TimeUtil::markTimestamp("t3", "aligetorder4");
            echo date('Y-m-d H:i:s') . " api start uid:{$puid} orderId:{$QAG_obj->orderid} " . PHP_EOL;
            // 检查授权是否过期或者是否授权,返回true，false
            if (!AliexpressInterface_Auth::checkToken($QAG_obj->sellerloginid)) {
                //执行计数
                $redis_key = date("Y-m-d H") . ",getOrder4_checktoken_error";
                RedisHelper::RedisAdd("Aliexpress_get_order", $redis_key);
                $error_msg = $QAG_obj->message;
                $QAG_obj->message = " {$QAG_obj->sellerloginid} Unauthorized or expired!";
                $bool = AliexpressClearHelper::saveRetryAccountInfo($QAG_obj);
                //$bool = AliexpressClearHelper::saveRetryAccount($QAG_obj);
                if ($bool) {
                    \Yii::info("ali_getorder3 check_token_fail push_retry_account orderId={$orderid}, success", "file");
                    $bool = $QAG_obj->delete();
                    if ($bool) {
                        \Yii::info("ali_getorder3 check_token_fail delete_row orderId={$orderid}, success", "file");
                    } else {
                        \Yii::info("ali_getorder3 check_token_fail delete_row orderId={$orderid}, fail", "file");
                    }
                } else {
                    $QAG_obj->message = $error_msg . " {$QAG_obj->sellerloginid} Unauthorized or expired!";
                    $QAG_obj->last_time = $now;
                    $QAG_obj->status = 3;
                    $QAG_obj->times += 1;
                    $bool = $QAG_obj->save(false);
                    if (!$bool) {
                        echo __FUNCTION__ . "STEP 1 : " . var_export($QAG_obj->errors, true);
                    }
                    \Yii::info("ali_getorder3 check_token_fail push_retry_account orderId={$orderid}, fail", "file");
                }
                continue;
            }


            $api = new AliexpressInterface_Api ();
            $access_token = $api->getAccessToken($QAG_obj->sellerloginid);
            //获取访问token失败
            if ($access_token === false) {
                //执行计数
                $redis_key = date("Y-m-d H") . ",getOrder4_gettoken_error";
                RedisHelper::RedisAdd("Aliexpress_get_order", $redis_key);
                $error_msg = $QAG_obj->message;
                $QAG_obj->message = " {$QAG_obj->sellerloginid} not getting access token!";
                $bool = AliexpressClearHelper::saveRetryAccountInfo($QAG_obj);
                //$bool = AliexpressClearHelper::saveRetryAccount($QAG_obj);
                if ($bool) {
                    \Yii::info("ali_getorder3 get_token_fail push_retry_account orderId={$orderid}, success", "file");
                    $bool = $QAG_obj->delete();
                    if ($bool) {
                        \Yii::info("ali_getorder3 get_token_fail delete_row orderId={$orderid}, success", "file");
                    } else {
                        \Yii::info("ali_getorder3 get_token_fail delete_row orderId={$orderid}, fail", "file");
                    }
                } else {
                    $QAG_obj->message = $error_msg . " {$QAG_obj->sellerloginid} Unauthorized or expired!";
                    $QAG_obj->status = 3;
                    $QAG_obj->times += 1;
                    $QAG_obj->last_time = $now;
                    $bool = $QAG_obj->save(false);
                    if (!$bool) {
                        echo __FUNCTION__ . "STEP 2 : " . var_export($QAG_obj->errors, true);
                    }
                    \Yii::info("ali_getorder3 get_token_fail push_retry_account orderId={$orderid}, fail", "file");
                }
                continue;
            }

            $api->access_token = $access_token;

            //$timeMS4=TimeUtil::getCurrentTimestampMS();
            TimeUtil::markTimestamp("t4", "aligetorder2");
            $orderId = $QAG_obj->orderid;
            // 接口传入参数速卖通订单号
            $param = ['orderId' => $row['orderid']];
            // 调用接口获取订单列表
            $result = $api->findOrderById($param);
            //$timeMS5=TimeUtil::getCurrentTimestampMS();
            TimeUtil::markTimestamp("t5", "aligetorder2");

            echo date('Y-m-d H:i:s') . " api end uid:{$puid} orderId:{$QAG_obj->orderid} " . PHP_EOL;
            // 是否同步成功
            if (isset ($result['error_message']) || empty ($result)) {
                if ($result['error_message'] == 'Beyond the app call frequency limit') {
                    //执行计数
                    $redis_key = date("Y-m-d H") . ",getOrder4_api_call_error";
                    RedisHelper::RedisAdd("Aliexpress_get_order", $redis_key);
                    $error_msg = $QAG_obj->message;
                    $QAG_obj->message = isset ($result ['error_message']) ? $result ['error_message'] : '接口返回错误，在订单状态变更检查时';
                    $bool = AliexpressClearHelper::saveRetryOrder($QAG_obj);
                    if ($bool) {
                        \Yii::info("ali_getorder3 api_result_error push_retry_order success orderId={$orderid}, msg=" . $result['error_message'], "file");
                        $bool = $QAG_obj->delete();
                        if ($bool) {
                            \Yii::info("ali_getorder3 api_result_error push_retry_order delete_row orderId={$orderid} success", "file");
                        } else {
                            \Yii::info("ali_getorder3 api_result_error push_retry_order delete_row orderId={$orderid} fail", "file");
                        }
                    } else {
                        $QAG_obj->message = isset ($result ['error_message']) ? $error_msg . $result ['error_message'] : $error_msg . '接口返回错误，在订单状态变更检查时';
                        $QAG_obj->status = 3;
                        $QAG_obj->times += 1;
                        $QAG_obj->last_time = $now;
                        $bool = $QAG_obj->save(false);
                        if (!$bool) {
                            echo __FUNCTION__ . "STEP 3 : " . var_export($QAG_obj->errors, true);
                        }
                        \Yii::info("ali_getorder3 api_result_error push_retry_order fail orderId={$orderid}, msg=" . $result['error_message'], "file");
                    }
                } else {
                    //执行计数
                    $redis_key = date("Y-m-d H") . ",getOrder4_api_other_error";
                    RedisHelper::RedisAdd("Aliexpress_get_order", $redis_key);
                    $error_msg = $QAG_obj->message;
                    $QAG_obj->message = isset ($result ['error_message']) ? $result ['error_message'] : '接口返回错误，在订单状态变更检查时';
                    $bool = AliexpressClearHelper::saveRetryAccountInfo($QAG_obj);
                    //$bool = AliexpressClearHelper::saveRetryAccount($QAG_obj);
                    if ($bool) {
                        \Yii::info("ali_getorder3 api_result_error push_retry_account success orderId={$orderid}, msg=" . $result['error_message'], "file");
                        $bool = $QAG_obj->delete();
                        if ($bool) {
                            \Yii::info("ali_getorder3 api_result_error push_retry_account delete_row orderId={$orderid} success", "file");
                        } else {
                            \Yii::info("ali_getorder3 api_result_error push_retry_account delete_row orderId={$orderid} fail", "file");
                        }
                    } else {
                        $QAG_obj->message = isset ($result ['error_message']) ? $error_msg . $result ['error_message'] : $error_msg . '接口返回错误，在订单状态变更检查时';
                        $QAG_obj->status = 3;
                        $QAG_obj->times += 1;
                        $QAG_obj->last_time = $now;
                        $bool = $QAG_obj->save(false);
                        if (!$bool) {
                            echo __FUNCTION__ . "STEP 3 : " . var_export($QAG_obj->errors, true);
                        }
                        \Yii::info("ali_getorder3 api_result_error push_retry_account fail orderId={$orderid}, msg=" . $result['error_message'], "file");
                    }
                }
                continue;
            }

            //订单未完成，且状态没有改变，则回到等待队列中
            if ($QAG_obj->order_status === $result['orderStatus'] && $result['orderStatus'] !== 'FINISH') {
                $QAG_two = new QueueAliexpressGetorder2();
                $QAG_two->uid = $QAG_obj->uid;
                $QAG_two->sellerloginid = $QAG_obj->sellerloginid;
                $QAG_two->aliexpress_uid = $QAG_obj->aliexpress_uid;
                $QAG_two->status = 2;
                $QAG_two->type = $QAG_obj->type;
                $QAG_two->order_status = $QAG_obj->order_status;
                $QAG_two->orderid = $QAG_obj->orderid;
                $QAG_two->times = 0;
                $QAG_two->order_info = $QAG_obj->order_info;
                $QAG_two->last_time = $now;
                $QAG_two->gmtcreate = $QAG_obj->gmtcreate;
                $QAG_two->message = '';
                $QAG_two->create_time = $QAG_obj->create_time;
                $QAG_two->update_time = $now;
                $QAG_two->next_time = self::calcNextSyncTime($QAG_obj->uid, $QAG_obj->order_status);
                $bool = $saveRes = $QAG_two->save(false);
                if (!$bool) {
                    echo __FUNCTION__ . "STEP 4 : " . var_export($QAG_two->errors, true);
                }
                if ($saveRes) {
                    $bool = $QAG_obj->delete();
                    if (!$bool) {
                        echo __FUNCTION__ . "STEP 5 : " . var_export($QAG_obj->errors, true);
                    }
                } else {
                    //执行计数
                    $redis_key = date("Y-m-d H") . ",getOrder4_savegetOrder2_error";
                    RedisHelper::RedisAdd("Aliexpress_get_order", $redis_key);
                    $QAG_obj->message .= ' 保存等待队列失败';
                    $QAG_obj->status = 3;
                    $QAG_obj->times += 1;
                    $QAG_obj->last_time = $now;
                    $bool = $QAG_obj->save(false);
                    if (!$bool) {
                        echo __FUNCTION__ . "STEP 6 : " . var_export($QAG_obj->errors, true);
                    }
                }
                TimeUtil::markTimestamp("t6", "aligetorder4");

                \Yii::info("ali_getorder2 orderId=$orderId," . TimeUtil::getTimestampMarkInfo("aligetorder4"), "file");


                continue;
            }

            TimeUtil::markTimestamp("t7", "aligetorder4");
            echo date('Y-m-d H:i:s') . " save start uid:{$puid} orderId:{$QAG_obj->orderid} " . PHP_EOL;
            $uid = $QAG_obj->uid;
            // 同步成功保存数据到订单表
             
            TimeUtil::markTimestamp("t8", "aligetorder2");

            //平台订单id必须为字符串，不然后面在sql作为搜索where字段的时候，就使用不了索引！！！！！！
            $result['id'] = strval($result['id']);

            //速卖通返回的sellerOperatorLoginId是子账号的loginid（就算账号绑定的是主账号）
            //这里强制转成主账号loginid，这是为了后面保存到订单od_order_v2的订单信息能对应上绑定的账号
            $result["sellerOperatorLoginId"] = $QAG_obj->sellerloginid;
            $r = AliexpressInterface_Helper::saveAliexpressOrder($QAG_obj, $result);
            TimeUtil::markTimestamp("t9", "aligetorder2");
            // 判断是否付款并且保存成功,是则删除数据，否则继续同步
            if ($r['success'] != 0 || !isset($result['orderStatus'])) {
                //执行计数
                $redis_key = date("Y-m-d H") . ",getOrder4_saveAliexpressOrder_error";
               RedisHelper::RedisAdd("Aliexpress_get_order", $redis_key);
                $error_msg = $QAG_obj->message;
                $QAG_obj->message = "速卖通订单" . $QAG_obj->orderid . "保存失败" . $r ['message'];
                $bool = AliexpressClearHelper::saveRetryOrder($QAG_obj);
                if ($bool) {
                    $bool = $QAG_obj->delete();
                    if ($bool) {
                        \Yii::info("ali_getorder3 saveAliexpressOrder delete_row orderId={$orderid} success", "file");
                    } else {
                        \Yii::info("ali_getorder3 saveAliexpressOrder delete_row orderId={$orderid} fail", "file");
                    }
                    \Yii::info("ali_getorder3 saveAliexpressOrder error push_retry_order success orderId={$orderid},", "file");
                } else {
                    \Yii::info("ali_getorder3 saveAliexpressOrder error push_retry_order error orderId={$orderid},", "file");
                    $QAG_obj->status = 3;
                    $QAG_obj->times += 1;
                    $QAG_obj->message = $error_msg . "速卖通订单" . $QAG_obj->orderid . "保存失败" . $r ['message'];
                    $bool = $QAG_obj->save(false);
                    if (!$bool) {
                        echo __FUNCTION__ . "STEP 8 : " . var_export($QAG_obj->errors, true);
                    }
                }
                continue;
            }

            if ($result ['orderStatus'] == 'FINISH') {
                $bool = $QAG_obj->delete();
                if (!$bool) {
                    echo __FUNCTION__ . "STEP 9 : " . var_export($QAG_obj->errors, true);
                }
            } else {
                //写入等待队列
                $QAG_two = new QueueAliexpressGetorder2();
                $QAG_two->uid = $QAG_obj->uid;
                $QAG_two->sellerloginid = $QAG_obj->sellerloginid;
                $QAG_two->aliexpress_uid = $QAG_obj->aliexpress_uid;
                $QAG_two->status = 2;
                $QAG_two->type = QueueAliexpressGetorder::NOFINISH;;
                $QAG_two->order_status = $result ['orderStatus'];
                $QAG_two->orderid = $QAG_obj->orderid;
                $QAG_two->times = 0;
                $QAG_two->order_info = $QAG_obj->order_info;
                $QAG_two->last_time = $now;
                $QAG_two->gmtcreate = $QAG_obj->gmtcreate;
                $QAG_two->message = '';
                $QAG_two->create_time = $QAG_obj->create_time;
                $QAG_two->update_time = $now;
                $QAG_two->next_time = self::calcNextSyncTime($QAG_obj->uid, $result['orderStatus']);
                $saveRes = $QAG_two->save();
                if ($saveRes) {
                    $bool = $QAG_obj->delete();
                    if (!$bool) {
                        echo __FUNCTION__ . "STEP 11 : " . var_export($QAG_obj->errors, true);
                    }
                } else {
                    //执行计数
                    $redis_key = date("Y-m-d H") . ",getOrder4_updategetOrder2_error";
                    RedisHelper::RedisAdd("Aliexpress_get_order", $redis_key);
                    echo __FUNCTION__ . "STEP 10 : " . var_export($QAG_two->errors, true);
                    $QAG_obj->status = 3;
                    $QAG_obj->times += 1;
                    $QAG_obj->message .= "速卖通订单" . $QAG_obj->orderid . "保存失败" . $r ['message'];
                    $bool = $QAG_obj->save(false);
                    if (!$bool) {
                        echo __FUNCTION__ . "STEP 12 : " . var_export($QAG_obj->errors, true);
                    }
                }
                TimeUtil::markTimestamp("t10", "aligetorder4");
            }
            //echo date('Y-m-d H:i:s').' save end '.$uid.' '.$result['id'].PHP_EOL;
            echo date('Y-m-d H:i:s') . " save end uid:{$puid} orderId:{$QAG_obj->orderid} " . PHP_EOL;

            \Yii::info("ali_getorder3 orderId=$orderId," . TimeUtil::getTimestampMarkInfo("aligetorder4"), "file");
        }
        echo date('Y-m-d H:i:s') . ' order status change script end ' . self::$cronJobId . PHP_EOL;
        return $hasGotRecord;
    }

    /**
     * 刷新refresh_token
     * @author dzt 2015-07-13
     */
    static function postponeToken($time_interval = 86400)
    {
        $t = time() + 86400 * 30;// refresh_token 在30 天内过期的
        $SAA_objs = SaasAliexpressUser::find()->where(' `is_active` = 1 AND `refresh_token_timeout` > ' . time() . ' AND  `refresh_token_timeout` < ' . $t)->orderBy('refresh_token_timeout asc')->all();
        echo "count:" . count($SAA_objs) . " \n";
        \Yii::info("There are " . count($SAA_objs) . " ali users waiting for being postponeToken to eagle");

        if (count($SAA_objs) > 0) {
            foreach ($SAA_objs as $SAU_obj) {
                $a = AliexpressInterface_Auth::checkToken($SAU_obj->sellerloginid);
                if ($a) {
                    $api = new AliexpressInterface_Api ();
                    $api->access_token = $api->getAccessToken($SAU_obj->sellerloginid);
                    $rtn = $api->postponeToken($SAU_obj->refresh_token, $api->access_token);
                    if (isset($rtn['refresh_token'])) {
                        $SAU_obj->refresh_token = $rtn['refresh_token'];
                        $SAU_obj->refresh_token_timeout = AliexpressInterface_Helper::transLaStrTimetoTimestamp($rtn['refresh_token_timeout']);
                        $SAU_obj->access_token = $rtn['access_token'];
                        $SAU_obj->access_token_timeout = time() + 28800;// 8 小时过期
                        $SAU_obj->update_time = time();
                        if (!$SAU_obj->save()) {
                            echo $SAU_obj->sellerloginid . ' $SAU_obj->save() save fail!' . "\n";
                            \Yii::info('aliexress_postpone_token  $SAU_obj->save() save fail! Error:' . print_r($SAU_obj->getErrors(), true) . ' ,sid=' . $SAU_obj->sellerloginid . ',puid=' . $SAU_obj->uid . ' rtn Arr:' . print_r($rtn, true), "file");
                        }
                    } else {
                        echo $SAU_obj->sellerloginid . ' get refresh_token fail!' . "\n";
                        \Yii::info('aliexress_postpone_token get refresh_token fail! ,sid=' . $SAU_obj->sellerloginid . ',puid=' . $SAU_obj->uid . ' rtn Arr:' . print_r($rtn, true), "file");
                    }

                } else {
                    echo $SAU_obj->sellerloginid . ' Unauthorized or expired!' . "\n";
                    \Yii::info("aliexress_postpone_token  unauthorized_or_expired ,sid=" . $SAU_obj->sellerloginid . ",puid=" . $SAU_obj->uid, "file");
                    // @todo 要不要对user is_active之类的值进行设置
                }

            }
        }

    }

    /**
     * 同步在线商品
     *
     * @param unknown $type 商品在线状态
     * @param string $orderBy 排序
     * @param number $time_interval 时间间隔
     */
    static function getListing($type, $orderBy = "last_time", $time_interval = 86400, $isImmediate = 'N',$sellerloginid ='')
    {
        //Step 0, 检查当前本job版本，如果和自己的版本不一样，就自动exit吧
        $version = ConfigHelper::getGlobalConfig("Listing/aliexpressGetListingVersion", 'NO_CACHE');
        if (empty($version))
            $version = 0;

        //如果自己还没有定义，去使用global config来初始化自己
        if (empty(self::$version))
            self::$version = $version;

        //如果自己的version不同于全局要求的version，自己退出，从新起job来使用新代码
        if (self::$version <> $version) {
            exit("Version new $version , this job ver " . self::$version . " exits for using new version $version.");
        }


        $backgroundJobId = self::getCronJobId();
        $queue = Yii::$app->db_queue;
        $connection = Yii::$app->db;
        #########################
        $hasGotRecord = false;//是否抢到账号
        $t = time() - $time_interval;

        //	$andSql = ' AND `last_time` > 0 ';
        //	$andSql = '  ';
        //if($isImmediate == 'Y')
        //		$andSql = ' AND `last_time` = 0 ';
        //	where `is_active` = 1 AND `status` <> 1 AND `times` < 10 AND `type`="'.$type.'" AND `last_time` < '.$t.$andSql.' order by `last_time` ASC limit 5');
        if( $sellerloginid=='' ){
            $sqlStr = 'select `id`,`uid` from `saas_aliexpress_autosync`
		where `last_time` < ' . $t . ' AND `is_active` = 1 AND `status` <> 1 AND `times` < 10 AND `type`="' . $type . '" order by `last_time` ASC limit 50';
        }else{
            //`last_time` < ' . $t . ' AND
            $sqlStr = 'select `id`,`uid` from `saas_aliexpress_autosync`
		where  `is_active` = 1 AND `status` <> 1 AND `times` < 10 AND `type`="' . $type . '" AND sellerloginid="'.$sellerloginid.'"  ';
        }
        echo $sqlStr . " \n";
        $command = $connection->createCommand($sqlStr);

        #################################
        $dataReader = $command->query();
        //	$activeUsersPuidArr = UserLastActionTimeHelper::getPuidArrByInterval(7*24);
        while (($row = $dataReader->read()) !== false) {
            //检查是否开启同步商品
            /*	$ret = AppApiHelper::getFunctionstatusByPuidKey($row['uid'], "tracker_recommend");
		     if($ret == 0){
		     //没有开启，更新，跳过当前循环
		     $command = $connection->createCommand("update saas_aliexpress_autosync set status=4 ,last_time=".time()." where id =". $row['id']) ;
		     $command->execute();
		     continue;
		     }*/
             


            $puid = $row['uid'];
            $autoSyncId = $row['id'];
            echo "puid:$puid autoSyncId:$autoSyncId \n";
            $logTimeMS1 = TimeUtil::getCurrentTimestampMS(); //获取当前时间戳，毫秒为单位，该数值只有比较意义。
            //1. 先判断是否可以正常抢到该记录
            $command = $connection->createCommand("update saas_aliexpress_autosync set status=1 where id =" . $row['id'] . " and status<>1 ");
            $affectRows = $command->execute();
            if ($affectRows <= 0) continue; //抢不到
            $logTimeMS2 = TimeUtil::getCurrentTimestampMS();
            echo "aliexpress_get_listing_onselling gotit puid=$puid start \n";
            \Yii::info("aliexpress_get_listing_onselling gotit id=$autoSyncId,puid=$puid start", "file");
            $logPuidTimeMS1 = TimeUtil::getCurrentTimestampMS();
            //2. 抢到记录
            $hasGotRecord = true;
            $SAA_obj = SaasAliexpressAutosync::findOne($autoSyncId);
            $sellerloginid = $SAA_obj->sellerloginid;
 
            // 检查授权是否过期或者是否授权,返回true，false
            $a = AliexpressInterface_Auth::checkToken($SAA_obj->sellerloginid);
            if ($a) {
                echo $SAA_obj->sellerloginid . "\n";
                $api = new AliexpressInterface_Api ();
                //$token= $api->refreshTokentoAccessToken('d22e4580-2ac2-4703-ae7a-a38fef697d49');
                //print_r ($token);exit;
                $access_token = $api->getAccessToken($SAA_obj->sellerloginid);
                //获取访问token失败
                if ($access_token === false) {
                    echo $SAA_obj->sellerloginid . 'not getting access token!' . "\n";
                    \Yii::info("Error: aliexpress_get_listing_onselling " . $SAA_obj->sellerloginid . 'not getting access token!', "file");
                    $SAA_obj->message = $SAA_obj->sellerloginid . ' not getting access token!';
                    $SAA_obj->status = 3;
                    $SAA_obj->times += 1;
                    $SAA_obj->last_time = time();
                    $SAA_obj->update_time = time();
                    $bool = $SAA_obj->save(false);
                    if (!$bool) {
                        echo __FUNCTION__ . "STEP 2 : " . var_export($SAA_obj->errors, true);
                    }
                    continue;
                }
                $api->access_token = $access_token;
                $page = 1;
                $pageSize = 100;
                // 是否全部同步完成
                $success = true;
                $hasDeleted = false;
                do {
                    // 接口传入参数
                    $param = array(
                        'currentPage' => $page,
                        'pageSize' => $pageSize,
                        'productStatusType' => $type,
                    );
                    echo "page:$page pageSize:$pageSize type:$type " . "\n";

                    ###################################################
                    //$param['createDateEnd'] = self::getLaFormatTime ( "m/d/Y H:i:s", $end_time );
                    //$param['productStatusType'] =$type;
                    #######################################################
                    // 调用接口获取订单列表
                    $logTimeMS3 = TimeUtil::getCurrentTimestampMS();
                    try {
                        $result = $api->findProductInfoListQuery($param);
                    } catch (Exception $exApi) {
                        $result = array();
                        $success = false;
                        $result['error_message'] = print_r($exApi, true);
                    }
                    $logTimeMS4 = TimeUtil::getCurrentTimestampMS();

                    // 判断是否有订单
                    if (isset ($result ['productCount'])) {
                        echo $result ['productCount'] . "\n";
                        if ($result ['productCount'] > 0) {
                            //先删除再批量插入
                            if ($hasDeleted === false) {

                               // $command = \Yii::$app->subdb->createCommand("DELETE FROM aliexpress_listing where selleruserid='" . $sellerloginid . "'")->execute();
                                $hasDeleted = true;
                            }

                            echo "page:$page pageSize:$pageSize type:$type productCount:" . count($result ['aeopAEProductDisplayDTOList']) . "\n";
                            $batchInsertDatas = array();
                            $nowTime = time();
                            foreach ($result ['aeopAEProductDisplayDTOList'] as $one) {

                                $gmtCreate = AliexpressInterface_Helper::transLaStrTimetoTimestamp($one ['gmtCreate']);
                                $gmtModified = AliexpressInterface_Helper::transLaStrTimetoTimestamp($one ['gmtModified']);
                                $WOD = AliexpressInterface_Helper::transLaStrTimetoTimestamp($one ['wsOfflineDate']);
                                if ($one['imageURLs']!= '') {
                                    $photo_arr= explode( ';',$one['imageURLs'] );
                                    $photo_primary= $photo_arr[0];
                                    if( count( $photo_arr )==1 ){
                                        $imageurls= '';
                                    }else{
                                        unset($photo_arr[0]);
                                        $imageurls= implode(";",$photo_arr);
                                    }
                                } else {
                                    $photo_primary= '';
                                    $imageurls= '';
                                }


                                $batchInsertData = array();
                                $batchInsertData["productid"] = $one['productId'];
                                $batchInsertData["freight_template_id"] = isset($one['freightTemplateId']) ? $one['freightTemplateId'] : '';
                                $batchInsertData["owner_member_seq"] = $one['ownerMemberSeq'];
                                $batchInsertData["subject"] = $one['subject'];
                                $batchInsertData["photo_primary"] = $photo_primary;
                                $batchInsertData["imageurls"] = $imageurls;
                                $batchInsertData["selleruserid"] = $SAA_obj->sellerloginid;
                                $batchInsertData["ws_offline_date"] = $WOD;
                                $batchInsertData["product_min_price"] = $one['productMinPrice'];
                                $batchInsertData["ws_display"] = $one['wsDisplay'];
                                $batchInsertData["product_max_price"] = $one['productMaxPrice'];
                                $batchInsertData["gmt_modified"] = $gmtModified;
                                $batchInsertData["gmt_create"] = $gmtCreate;
                                $batchInsertData["sku_stock"] = 0;
                                $batchInsertData["created"] = $nowTime;
                                $batchInsertData["updated"] = $nowTime;
                                $batchInsertData["product_status"]= 1;
                                //$batchInsertDatas[] = $batchInsertData;
                                //aliexpress_listing  $AL_obj = new AliexpressListing ();

                                //当前productid的商品状态和数据库中的对比,如果不一致,则删除listing和detail中的
                                $listingsx = AliexpressListing::find()->where(['productid' => $one['productId']])->asArray()->one();
                                if( !empty( $listingsx ) ){
                                    if( $listingsx['product_status']!=1 ){
                                        //删除
                                        AliexpressListing::deleteAll(['productid' => $one['productId']]);
                                        AliexpressListingDetail::deleteAll(['productid' => $one['productId']]);
                                    }
                                }

                                //json
                                $md5_json_data= md5( json_encode( $one ) );
                                //save queue_product_info_md5
                                $query = $queue->createCommand("SELECT * FROM queue_product_info_md5 WHERE product_id= '".$one['productId']."' ")->query();
                                $re= $query->read();
                                if( empty( $re ) ){
                                    AliexpressListing::deleteAll(['productid' => $one['productId']]);
                                    AliexpressListingDetail::deleteAll(['productid' => $one['productId']]);

                                    //insert
                                    $insert= "INSERT INTO queue_product_info_md5( `product_id`,`listen_md5`,`selleruserid` )VALUES( '".$one['productId']."','{$md5_json_data}','{$sellerloginid}' )";
                                    $queue->createCommand( $insert )->execute();

                                    $batchInsertDatas[] = $batchInsertData;
                                }else{
                                    //当保存的md5 和 现在的md5 不一致,才修改等操作
                                    $alerdy_save_md5= $re['listen_md5'];
                                    if( $alerdy_save_md5!=$md5_json_data ) {
                                        //update
                                        $update = "UPDATE queue_product_info_md5 SET listen_md5='{$md5_json_data}',listen_detail_md5 ='' WHERE id=" . $re['id'];
                                        $queue->createCommand($update)->execute();

                                        //delete
                                        AliexpressListing::deleteAll( ['productid'=>$one['productId']] );
                                        AliexpressListingDetail::deleteAll( ['productid'=>$one['productId']] );
                                        $batchInsertDatas[] = $batchInsertData;
                                    }
                                }
                            }

                            SQLHelper::groupInsertToDb("aliexpress_listing", $batchInsertDatas);



                        }
                    } else {
                        $success = false;
                    }
                    $logTimeMS5 = TimeUtil::getCurrentTimestampMS();
                    $page++;
                    $p = isset($result['totalPage']) ? $result['totalPage'] : 0;


                } while ($page <= $p);

                // 是否全部同步成功
                if ($success) {
                    $SAA_obj->last_time = time();
                    $SAA_obj->status = 2;
                    $SAA_obj->times = 0;
                    $bool = $SAA_obj->save(false);
                    if (!$bool) {
                        echo __FUNCTION__ . "STEP 5 : " . var_export($SAA_obj->errors, true);
                    }
                } else {
                    $SAA_obj->message = isset($result ['error_message']) ? $result ['error_message'] : '接口返回结果错误V1' . print_r($result, true);
                    $SAA_obj->last_time = time();
                    $SAA_obj->status = 3;
                    $SAA_obj->times += 1;
                    $bool = $SAA_obj->save(false);
                    if (!$bool) {
                        echo __FUNCTION__ . "STEP 6 : " . var_export($SAA_obj->errors, true);
                    }
                }
                echo '开始同步detail的数据';
                //接着同步
                //吧,慢就慢点

                self::getListingDetail( $puid, $sellerloginid );

            } else {
                echo $SAA_obj->sellerloginid . ' Unauthorized or expired!' . "\n";
                $SAA_obj->message = $SAA_obj->sellerloginid . ' Unauthorized or expired!';
                $SAA_obj->last_time = time();
                $SAA_obj->status = 3;
                $SAA_obj->times += 1;
                $bool = $SAA_obj->save(false);
                if (!$bool) {
                    echo __FUNCTION__ . "STEP 7 : " . var_export($SAA_obj->errors, true);
                }
            }
        }

        return $hasGotRecord;


    }

    /**
     * 手动同步Aliexpress订单
     * @author yuhettian 2015-9-14
     * 601353571@qq.com
     */
    static function getOrderListByManual()
    {


        //接收参数
        $user_info = \Yii::$app->user->identity;
        if ($user_info['puid'] == 0) {
            $uid = $user_info['uid'];
        } else {
            $uid = $user_info['puid'];
        }

        $sellerloginid = $_POST['sellerloginid'];
        $startdate = $_POST['startdate'];
        $enddate = $_POST['enddate'];
        $synctype = $_POST['synctype'];
        $judgeTime = time();

        //判断用户是否符合使用条件
        $checkSync = CheckSync::findOne(['sellerloginid' => $sellerloginid]);
        if ($checkSync === null || $checkSync->sellerloginid == '' || $checkSync->sync_time == 0) {
            $checkSync = new CheckSync();
            $checkSync->sellerloginid = $sellerloginid;
            $checkSync->sync_time = time();
            $checkSync->save();
        }
        if (($checkSync->sync_time - $judgeTime) < 3600) {
            return $success = 'Today has been synchronized!';
        }

        $checkToken = AliexpressInterface_Auth::checkToken($sellerloginid);

        if ($checkToken) {
            //获取访问token
            $api = new AliexpressInterface_Api ();
            $access_token = $api->getAccessToken($sellerloginid);

            //获取访问token失败
            if ($access_token === false) {
                return $success = 'Token acquisition failure!';
            }
            $api->access_token = $access_token;

            $page = 1;
            $pageSize = 50;
            // 是否全部同步完成
            $success = "Synchronous success";

            #####################################
            if (!empty($startdate) && !empty($enddate)) {
                if (((strtotime($enddate) - strtotime($startdate)) > (86400 * 3)) || ((strtotime($enddate) - strtotime($startdate)) < 0)) {
                    return $success = "Please fill in the complete parameters";
                } else {
                    $start_time = strtotime($startdate);
                    $end_time = strtotime($enddate);
                }
            } else {
                $start_time = time() - (86400 * 2);
                $end_time = time();
            }


            ########################################
            // 接口传入参数
            do {
                $param = array(
                    'page' => $page,
                    'pageSize' => $pageSize,
                );
                $param['createDateStart'] = self::getLaFormatTime("m/d/Y H:i:s", $start_time);
                $param['createDateEnd'] = self::getLaFormatTime("m/d/Y H:i:s", $end_time);
                if ($synctype != "MO_CHOSE") {
                    $param['orderStatus'] = $synctype;
                }
                #######################################################
                // 调用接口获取订单列表
                //$result = $api->findOrderListQuery ( $param );//old
                $result = $api->findOrderListSimpleQuery($param);

                // 判断是否有订单
                if (isset ($result ['totalItem'])) {
                    if ($result ['totalItem'] > 0) {
                        // 保存数据到同步订单详情队列
                        foreach ($result ['orderList'] as $one) {
                            // 订单产生时间
                            $one['gmtCreate'] = AliexpressInterface_Helper::transLaStrTimetoTimestamp($one ['gmtCreate']);

                            //对比相应数据
                            $orders = OdOrder::findOne(['order_source_order_id' => $one['orderId']]);
                            if ($orders->order_source_status == $one['orderStatus']) {
                                continue;
                            }
                            //接口传入参数
                            $source = array(
                                'orderId' => $one['orderId']
                            );
                            $results = $api->findOrderById($source);

                            //转换为object参数
                            $result_obj = new QueueAliexpressGetorder ();
                            $result_obj->uid = $uid;
                            $result_obj->order_info = json_encode($one);
                            //相关数据进saveAliexpressOrderManual方法
                            $data = AliexpressInterface_Helper::saveAliexpressOrderManual($result_obj, $results);
                            if ($data ['success'] == 0) {
                                //error_log($data['orderId'], 3, 'D:/Aliexpress.txt');
                                return $success;
                            } elseif ($data['success'] == 1) {
                                //error_log($data['orderId'], 3, 'D:/Aliexpress1.txt');
                                return $success = "Failed to save the order";
                            }
                        }
                    }
                }
                $page++;
                $total = isset($result ['totalItem']) ? $result ['totalItem'] : 0;
                $pages = ceil($total / 50);
            } while ($page <= $pages);
            // 是否全部同步成功
        }
        return $success = "Token Invalid";
    }


    /**
     * 从queue_aliexpress_getorder2 回写 queue_aliexpress_getorder脚本
     * @author yangjun 2015-08-24
     */
    static function getOrderInsertQueue()
    {
        //Step 0, 检查当前本job版本，如果和自己的版本不一样，就自动exit吧
        $version = ConfigHelper::getGlobalConfig("Listing/aliexpressOrder2PushOrderVersion", 'NO_CACHE');
        if (empty($version))
            $version = 0;

        //如果自己还没有定义，去使用global config来初始化自己
        if (empty(self::$version))
            self::$version = $version;

        //如果自己的version不同于全局要求的version，自己退出，从新起job来使用新代码
        if (self::$version <> $version) {
            exit("Version new $version , this job ver " . self::$version . " exits for using new version $version.");
        }

        $t = time(); //需要同步时间节点
        $hasGotRecord = false;
        $connection = Yii::$app->db_queue;
        $command = $connection->createCommand('select  count(*) as c  from  `queue_aliexpress_getorder2` where `times` < 10  AND `next_time` < ' . $t);
        $dataReaderCount = $command->query();
        $count = $dataReaderCount->read();
        if ($count['c'] > 0) {
            //符合的条数大于0
            $page = 1;
            $pageSize = 100;
            $p = ceil($count['c'] / $pageSize);
            do {
                //分页查询数据，是为了不让单次查询数据过大，导致内存耗尽
                $currentPage = ($page - 1) * $pageSize;
                $command = $connection->createCommand('select * from  `queue_aliexpress_getorder2` where `times` < 10  AND `next_time` < ' . $t . ' limit ' . $currentPage . ',' . $pageSize);
                $dataReaderRow = $command->query();
                //获取100条数据的结果集，做循环插入操作（回写主表）
                while (($row = $dataReaderRow->read()) !== false) {
                    if ($row['type'] == 2) {
                        $QAG_obj = new QueueAliexpressGetfinishorder();
                    } else {
                        $QAG_obj = new QueueAliexpressGetorder();
                    }
                    $QAG_obj->uid = $row['uid'];
                    $QAG_obj->sellerloginid = $row['sellerloginid'];
                    $QAG_obj->aliexpress_uid = $row['aliexpress_uid'];
                    $QAG_obj->status = $row['status'];
                    $QAG_obj->type = $row['type'];
                    $QAG_obj->order_status = $row['order_status'];
                    $QAG_obj->orderid = $row['orderid'];
                    $QAG_obj->times = $row['times'];
                    $QAG_obj->order_info = $row['order_info'];
                    $QAG_obj->last_time = $row['last_time'];
                    $QAG_obj->gmtcreate = $row['gmtcreate'];
                    $QAG_obj->create_time = $row['create_time'];
                    $QAG_obj->update_time = $row['update_time'];
                    $QAG_obj->next_time = $row['next_time'];
                    $saveRes = $QAG_obj->save(false);
                    if (!$saveRes) {
                        echo __FUNCTION__ . "STEP 1 : " . var_export($QAG_obj->errors, true);
                    }
                    if ($saveRes) {
                        $command = $connection->createCommand("delete from queue_aliexpress_getorder2 where id = " . $row['id']);
                        $command->execute();
                    }
                }
                $page++;
            } while ($page <= $p);
            $hasGotRecord = true;
        }
        return $hasGotRecord;
    }


    /**
     * 获取需要发送好评的队列
     *
     */
    static function getListingPraise()
    {
        //Step 0, 检查当前本job版本，如果和自己的版本不一样，就自动exit吧
        $currentAliexpressGetOrderListVersion = ConfigHelper::getGlobalConfig("Order/aliexpressGetOrderListVersion", 'NO_CACHE');
        if (empty($currentAliexpressGetOrderListVersion))
            $currentAliexpressGetOrderListVersion = 0;

        //如果自己还没有定义，去使用global config来初始化自己
        if (empty(self::$aliexpressGetOrderListVersion))
            self::$aliexpressGetOrderListVersion = $currentAliexpressGetOrderListVersion;

        //如果自己的version不同于全局要求的version，自己退出，从新起job来使用新代码
        if (self::$aliexpressGetOrderListVersion <> $currentAliexpressGetOrderListVersion) {
            exit("Version new $currentAliexpressGetOrderListVersion , this job ver " . self::$aliexpressGetOrderListVersion . " exits for using new version $currentAliexpressGetOrderListVersion.");
        }

        $connection = Yii::$app->db;
        $command = $connection->createCommand("select * from queue_aliexpress_praise where `status` <> 1 limit 5 ");
        $dataReader = $command->query();
        while (($row = $dataReader->read()) !== false) {

            $command = $connection->createCommand("update queue_aliexpress_praise set status = 1 where id = " . $row['id'] . " and status != 1");
            $affectRows = $command->execute();
            if ($affectRows <= 0) {
                continue;
            }
            $a = AliexpressInterface_Auth::checkToken($row['sellerloginid']);
            if ($a) {
                $api = new AliexpressInterface_Api ();
                $access_token = $api->getAccessToken($row['sellerloginid']);
                //获取访问token失败
                if ($access_token === false) {
                    continue;
                }
                $api->access_token = $access_token;
                $param['orderId'] = $row['orderId'];
                $param['score'] = $row['score'];
                $param['feedbackContent'] = $row['feedbackContent'];
                $result = $api->saveSellerFeedback($param);

                //错误信息记录
                $error = ['code' => 0, 'msg' => ''];
                if (isset($result['errorCode'])) {
                    $error['code'] = $result['errorCode'];
                    $error['msg'] = $result['errorMessage'];
                } else if (isset($result['error_code'])) {
                    $error['code'] = $result['error_code'];
                    $error['msg'] = $result['error_message'];
                }

                $QAPI = new QueueAliexpressPraiseInfo();
                $QAPI->orderId = $row['orderId'];
                $QAPI->score = $row['score'];
                $QAPI->feedbackContent = $row['feedbackContent'];
                $QAPI->sellerloginid = $row['sellerloginid'];
                $QAPI->errorCode = $error['code'];
                $QAPI->errorMessage = $error['msg'];
                if (isset($result['success']) && ($result['success'] == 1 || $result['success'] == true)) {
                    $success = 'true';
                } else {
                    $success = 'false';
                }
                $QAPI->success = $success;
                $saveRes = $QAPI->save();
                if ($saveRes) {
                    $command = $connection->createCommand("delete from queue_aliexpress_praise where id = " . $row['id']);
                    $command->execute();
                }
            }
        }
    }

    /**
     * 获取需要发送好评的队列
     *
     */
    static function getListingPraiseV2()
    {
        //Step 0, 检查当前本job版本，如果和自己的版本不一样，就自动exit吧
        $currentAliexpressGetOrderListVersion = ConfigHelper::getGlobalConfig("Aliexpress:GetListingPraise:V2", 'NO_CACHE');
        if (empty($currentAliexpressGetOrderListVersion))
            $currentAliexpressGetOrderListVersion = 0;

        //如果自己还没有定义，去使用global config来初始化自己
        if (empty(self::$aliexpressGetOrderListVersion))
            self::$aliexpressGetOrderListVersion = $currentAliexpressGetOrderListVersion;

        //如果自己的version不同于全局要求的version，自己退出，从新起job来使用新代码
        if (self::$aliexpressGetOrderListVersion <> $currentAliexpressGetOrderListVersion) {
            exit("Version new $currentAliexpressGetOrderListVersion , this job ver " . self::$aliexpressGetOrderListVersion . " exits for using new version $currentAliexpressGetOrderListVersion.");
        }

        $connection = Yii::$app->db;


        while (true) {
            $command = $connection->createCommand("select * from queue_aliexpress_praise where `status` <> 1 limit 100 ");
            $notPraised = $command->queryAll();
            $length = count($notPraised);
            if ($length == 0) {
                self::localLog("end this batch");
                return false;
            }
            $ids = array();
            for ($i = 0; $i < $length; $i++) {
                $ids[$i] = $notPraised[$i]['id'];
            }
            $ids = implode(',', $ids);
            self::localLog("update ids is " . $ids . " and size is " . $length);
            $command = $connection->createCommand("update queue_aliexpress_praise set status = 1 where id IN ($ids)");
            $affectRows = $command->execute();
            $praisedIds = array();
            if ($affectRows > 0) {
                foreach ($notPraised as $row) {
                    $a = AliexpressInterface_Auth::checkToken($row['sellerloginid']);
                    if ($a) {
                        $api = new AliexpressInterface_Api ();
                        $access_token = $api->getAccessToken($row['sellerloginid']);
                        //获取访问token失败
                        if ($access_token === false) {
                            continue;
                        }
                        $api->access_token = $access_token;
                        $param['orderId'] = $row['orderId'];
                        $param['score'] = $row['score'];
                        $param['feedbackContent'] = $row['feedbackContent'];
                        self::localLog("start praise ... id is " . $row['id']);
                        $result = $api->saveSellerFeedback($param);
                        self::localLog("end praise!!! id is " . $row['id'] . "result is " . json_encode($result));
                        //错误信息记录
                        $error = ['code' => 0, 'msg' => ''];
                        if (isset($result['errorCode'])) {
                            $error['code'] = $result['errorCode'];
                            $error['msg'] = $result['errorMessage'];
                        } else if (isset($result['error_code'])) {
                            $error['code'] = $result['error_code'];
                            $error['msg'] = $result['error_message'];
                        }

                        $QAPI = new QueueAliexpressPraiseInfo();
                        $QAPI->orderId = $row['orderId'];
                        $QAPI->score = $row['score'];
                        $QAPI->feedbackContent = $row['feedbackContent'];
                        $QAPI->sellerloginid = $row['sellerloginid'];
                        $QAPI->errorCode = $error['code'];
                        $QAPI->errorMessage = $error['msg'];
                        if (isset($result['success']) && ($result['success'] == 1 || $result['success'] == true)) {
                            $success = 'true';
                        } else {
                            $success = 'false';
                        }
                        $QAPI->success = $success;
                        $saveRes = $QAPI->save();
                        if ($saveRes) {
                            self::localLog("end praise id " . $row['id'] . " orderId is " . $row['orderId']);
                            array_push($praisedIds, $row['id']);
                        }
                    }
                }
                if (!empty($praisedIds)) {
                    $praisedIds = implode(',', $praisedIds);
                    $affectRows = $connection->createCommand("DELETE from queue_aliexpress_praise where id IN ($praisedIds)")->execute();
                    if ($affectRows > 0) {
                        self::localLog("comment success and delete from queue_aliexpress_praise for orders " . $praisedIds);
                    } else {
                        self::localLog("failed delete from queue_aliexpress_praise for orders " . $praisedIds);
                    }
                }
            }
        }
    }

    /**
     * +---------------------------------------------------------------------------------------------
     * 手动删除订单并重新同步订单信息队列
     * +---------------------------------------------------------------------------------------------
     * @access static
     * +---------------------------------------------------------------------------------------------
     * @param na
    +---------------------------------------------------------------------------------------------
     * @return                boolean
    +---------------------------------------------------------------------------------------------
     * log            name    date                    note
     * @author        lkh        2015/12/08                初始化
     * +---------------------------------------------------------------------------------------------
     **/
    static public function refreshOrderInfoByManualQueue()
    {
        $hasGotRecord = false;//是否抢到账号
        $queue_table = "queue_manual_order_sync";

        //Step 0, 检查当前本job版本，如果和自己的版本不一样，就自动exit吧
        $currentAliexpressGetOrderListVersion = ConfigHelper::getGlobalConfig("Order/aliexpressGetOrderListVersion", 'NO_CACHE');
        if (empty($currentAliexpressGetOrderListVersion))
            $currentAliexpressGetOrderListVersion = 0;

        //如果自己还没有定义，去使用global config来初始化自己
        if (empty(self::$aliexpressGetOrderListVersion))
            self::$aliexpressGetOrderListVersion = $currentAliexpressGetOrderListVersion;

        //如果自己的version不同于全局要求的version，自己退出，从新起job来使用新代码
        if (self::$aliexpressGetOrderListVersion <> $currentAliexpressGetOrderListVersion) {
            exit("Version new $currentAliexpressGetOrderListVersion , this job ver " . self::$aliexpressGetOrderListVersion . " exits for using new version $currentAliexpressGetOrderListVersion.");
        }

        $logTime1 = TimeUtil::getCurrentTimestampMS();
        $connection = Yii::$app->db_queue;
        $command = $connection->createCommand("select * from $queue_table where `status` = 'P'  order by priority limit 5 ");
        $QueueList = $command->queryAll();
        foreach ($QueueList as $row) {
 

            //1. 先判断是否可以正常抢到该记录
            $command = $connection->createCommand("update $queue_table set status = 'S' where id = " . $row['id'] . " and status = 'P' ");
            $affectRows = $command->execute();
            if ($affectRows <= 0) {
                continue;
            }
            //2. 抢到记录
            $hasGotRecord = true;
            echo "\n start to sync " . $row['sellerloginid'] . " order id :" . $row['order_id'];
            $a = AliexpressInterface_Auth::checkToken($row['sellerloginid']);

            if ($a) {
                $api = new AliexpressInterface_Api ();
                $access_token = $api->getAccessToken($row['sellerloginid']);
                //获取访问token失败
                if ($access_token === false) {
                    continue;
                }
                $api->access_token = $access_token;


                //接口传入参数
                $source = array(
                    'orderId' => $row['order_id']
                );
                $results = $api->findOrderById($source);


                //$result_obj->order_info = json_encode($one);
                echo "\n save order id " . $row['order_id'];
                //相关数据进saveAliexpressOrderManual方法
                $data = AliexpressInterface_Helper::saveEagleOrder($row['puid'], $results);
                if ($data ['success'] == 0) {
                    //error_log($data['orderId'], 3, 'D:/Aliexpress.txt');

                    $command = $connection->createCommand("update $queue_table set status = 'C' ,err_msg = '' where id = " . $row['id'] . " and status = 'S' ");
                    $affectRows = $command->execute();
                    $success = '';
                    return $success;
                } elseif ($data['success'] == 1) {
                    //error_log($data['orderId'], 3, 'D:/Aliexpress1.txt');
                    $command = $connection->createCommand("update $queue_table set status = 'F',err_msg =:err_msg  where id = " . $row['id'] . " and status = 'S' ");
                    $command->bindValue(':err_msg', '同步失败');
                    $affectRows = $command->execute();
                    return $success = "Synchronous failure";
                }
            } else {
                //授权失败的情况下
                $command = $connection->createCommand("update $queue_table set status = 'F' ,err_msg =:err_msg  where id = " . $row['id'] . " and status = 'S' ");
                $command->bindValue(':err_msg', '授权失败');
                $affectRows = $command->execute();
                return false;
            }

        }
        return $hasGotRecord;

    }//end of refreshOrderInfo


    /**
     * 同步Aliexpress订单所有已完成30天的
     * @author 陈斌
     * 88028624@qq.com
     */
    static function getOrderListByFinishDay30()
    {
        //Step 0, 检查当前本job版本，如果和自己的版本不一样，就自动exit吧
        $currentAliexpressGetOrderListVersion = ConfigHelper::getGlobalConfig("Order/aliexpressGetOrderListVersion", 'NO_CACHE');
        if (empty($currentAliexpressGetOrderListVersion))
            $currentAliexpressGetOrderListVersion = 'v1';

        //如果自己还没有定义，去使用global config来初始化自己
        if (empty(self::$aliexpressGetOrderListVersion))
            self::$aliexpressGetOrderListVersion = $currentAliexpressGetOrderListVersion;

        //如果自己的version不同于全局要求的version，自己退出，从新起job来使用新代码
        if (self::$aliexpressGetOrderListVersion <> $currentAliexpressGetOrderListVersion) {
            exit("Version new $currentAliexpressGetOrderListVersion , this job ver " . self::$aliexpressGetOrderListVersion . " exits for using new version $currentAliexpressGetOrderListVersion.");
        }
        $backgroundJobId = self::getCronJobId();
        $connection = Yii::$app->db;
        #########################
        $type = 'finish30';
        $hasGotRecord = false;
        $command = $connection->createCommand('select `id` from  `saas_aliexpress_autosync` where `is_active` = 1 AND `status` in (0,2,3) AND `times` < 10 AND `type`="' . $type . '" order by `last_time` ASC limit 5');
        #################################
        $dataReader = $command->query();
        while (($row = $dataReader->read()) !== false) {
            //echo '<pre>';print_r($row);exit; //8614
            //1. 先判断是否可以正常抢到该记录
            $command = $connection->createCommand("update saas_aliexpress_autosync set status=1 where id =" . $row['id'] . " and status<>1 ");
            $affectRows = $command->execute();
            if ($affectRows <= 0) continue; //抢不到
            \Yii::info("aliexress_get_order_list_by_finish30 gotit jobid=$backgroundJobId start");
            //2. 抢到记录，设置同步需要的参数
            $hasGotRecord = true;
            $SAA_obj = SaasAliexpressAutosync::findOne($row['id']);
            // 检查授权是否过期或者是否授权,返回true，false
            $a = AliexpressInterface_Auth::checkToken($SAA_obj->sellerloginid);
            if ($a) {
                echo $SAA_obj->sellerloginid . "\n";
                $api = new AliexpressInterface_Api ();
                $access_token = $api->getAccessToken($SAA_obj->sellerloginid);
                //获取访问token失败
                if ($access_token === false) {
                    echo $SAA_obj->sellerloginid . 'not getting access token!' . "\n";
                    \Yii::info($SAA_obj->sellerloginid . 'not getting access token!' . "\n");
                    $SAA_obj->message = $SAA_obj->sellerloginid . ' not getting access token!';
                    $SAA_obj->status = 3;
                    $SAA_obj->times += 1;
                    $SAA_obj->last_time = time();
                    $SAA_obj->update_time = time();
                    $bool = $SAA_obj->save(false);
                    if (!$bool) {
                        echo __FUNCTION__ . "STEP 1 : " . var_export($SAA_obj->errors, true);
                    }
                    continue;
                }
                $api->access_token = $access_token;
                $page = 1;
                $pageSize = 50;
                // 是否全部同步完成
                $success = true;
                $exit = false;
                #####################################
                $start_time = $SAA_obj->binding_time - (86400 * 5);
                $end_time = $SAA_obj->binding_time;
                if ($SAA_obj->start_time > $start_time) {
                    $start_time = $SAA_obj->start_time;
                }

                ########################################
                do {
                    // 接口传入参数
                    $param = array(
                        'page' => (int)$page,
                        'pageSize' => $pageSize,
                    );
                    ###################################################
                    $param['createDateStart'] = self::getLaFormatTime("m/d/Y H:i:s", $start_time);
                    $param['createDateEnd'] = self::getLaFormatTime("m/d/Y H:i:s", $end_time);
                    #######################################################
                    $param['orderStatus'] = 'FINISH';
                    ####################################################
                    // 调用接口获取订单列表
                    $result = $api->findOrderListSimpleQuery($param);
                    // 判断是否有订单
                    if (isset ($result ['totalItem'])) {
                        echo $result ['totalItem'] . "\n";
                        if ($result ['totalItem'] > 0) {
                            // 保存数据到同步订单详情队列
                            foreach ($result ['orderList'] as $one) {
                                // 订单产生时间
                                $gmtCreate = AliexpressInterface_Helper::transLaStrTimetoTimestamp($one ['gmtCreate']);
                                //new 把finish的订单单独存在一张表里面
                                $QAG_finish = QueueAliexpressGetfinishorder::findOne(['orderid' => $one ['orderId']]);
                                if (isset ($QAG_finish)) {
                                    $QAG_finish->order_status = $one['orderStatus'];
                                    $QAG_finish->order_info = json_encode($one);
                                    $QAG_finish->update_time = time();
                                    $bool = $QAG_finish->save(false);
                                    if (!$bool) {
                                        echo __FUNCTION__ . "STEP 2 : " . var_export($QAG_finish->errors, true);
                                    }
                                } else {
                                    $QAG_finish = new QueueAliexpressGetfinishorder ();
                                    $QAG_finish->uid = $SAA_obj->uid;
                                    $QAG_finish->sellerloginid = $SAA_obj->sellerloginid;
                                    $QAG_finish->aliexpress_uid = $SAA_obj->aliexpress_uid;
                                    $QAG_finish->status = 0;
                                    $QAG_finish->type = 2;
                                    $QAG_finish->order_status = $one['orderStatus'];
                                    $QAG_finish->orderid = $one ['orderId'];
                                    $QAG_finish->times = 0;
                                    $QAG_finish->order_info = json_encode($one);
                                    $QAG_finish->last_time = 0;
                                    $QAG_finish->gmtcreate = $gmtCreate;
                                    $QAG_finish->create_time = time();
                                    $QAG_finish->update_time = time();
                                    $QAG_finish->next_time = time();
                                    $bool = $QAG_finish->save(false);
                                    if (!$bool) {
                                        echo __FUNCTION__ . "STEP 3 : " . var_export($QAG_finish->errors, true);
                                    }
                                }
                            }
                        } else {
                            $exit = true;//当已完成订单数量为0时说明已完成订单已经同步完毕
                        }
                    } else {
                        $success = false;
                    }

                    $page++;
                    $total = isset($result ['totalItem']) ? $result ['totalItem'] : 0;
                    $p = ceil($total / 50);
                } while ($page <= $p);
                // 是否全部同步成功
                if ($success) {
                    $SAA_obj->end_time = $end_time;
                    $SAA_obj->start_time = $start_time;
                    $SAA_obj->last_time = time();
                    $SAA_obj->status = 4;//已完成订单全部同步
                    $SAA_obj->times = 0;
                    $bool = $SAA_obj->save(false);
                    if (!$bool) {
                        echo __FUNCTION__ . "STEP 4 : " . var_export($QAG_finish->errors, true);
                    }
                } else {
                    $SAA_obj->message = isset($result ['error_message']) ? $result ['error_message'] : '接口返回结果错误V1' . print_r($result, true);
                    $SAA_obj->last_time = time();
                    $SAA_obj->status = 3;
                    $SAA_obj->times += 1;
                    $bool = $SAA_obj->save(false);
                    if (!$bool) {
                        echo __FUNCTION__ . "STEP 5 : " . var_export($SAA_obj->errors, true);
                    }
                }
            } else {
                echo $SAA_obj->sellerloginid . ' Unauthorized or expired!' . "\n";
                $SAA_obj->message = $SAA_obj->sellerloginid . ' Unauthorized or expired!';
                $SAA_obj->last_time = time();
                $SAA_obj->status = 3;
                $SAA_obj->times += 1;
                $bool = $SAA_obj->save(false);
                if (!$bool) {
                    echo __FUNCTION__ . "STEP 6 : " . var_export($SAA_obj->errors, true);
                }
            }
            \Yii::info("aliexress_get_order_list_by_finish30 gotit jobid=$backgroundJobId end");
        }
        return $hasGotRecord;
    }

    public function checkOrderStatus()
    {
        //Step 0, 检查当前本job版本，如果和自己的版本不一样，就自动exit吧
        $currentAliexpressGetOrderListVersion = ConfigHelper::getGlobalConfig("Order/aliexpressCheckOrderStatusVersion", 'NO_CACHE');
        if (empty($currentAliexpressGetOrderListVersion))
            $currentAliexpressGetOrderListVersion = 'v1';

        //如果自己还没有定义，去使用global config来初始化自己
        if (empty(self::$aliexpressGetOrderListVersion))
            self::$aliexpressGetOrderListVersion = $currentAliexpressGetOrderListVersion;

        //如果自己的version不同于全局要求的version，自己退出，从新起job来使用新代码
        if (self::$aliexpressGetOrderListVersion <> $currentAliexpressGetOrderListVersion) {
            exit("Version new $currentAliexpressGetOrderListVersion , this job ver " . self::$aliexpressGetOrderListVersion . " exits for using new version $currentAliexpressGetOrderListVersion.");
        }
        $backgroundJobId = self::getCronJobId();
        $connection = Yii::$app->db_queue;
        //getorder
        \Yii::info("aliexress_check_orderstatus queue_aliexpress_getorder jobid=$backgroundJobId start", "file");
        #######################
        $command = $connection->createCommand('select `sellerloginid`,orderid from  `queue_aliexpress_getorder` where times >= 10 group by sellerloginid asc order by sellerloginid desc limit 100');
        #######################
        $dataReader = $command->query();
        while (($row = $dataReader->read()) !== false) {
            $log = "aliexress_get_order_check_status queue_aliexpress_getorder jobid= " . $backgroundJobId;
            $sellerloginid = $row['sellerloginid'];
            $orderid = $row['orderid'];
            $params = ['sellerloginid' => $sellerloginid];
            //检查账号是否存在
            $aliexpress_account = \Yii::$app->db->createCommand('select * from saas_aliexpress_user where sellerloginid = "' . $sellerloginid . '"')->query()->read();
            $logTimeMS1 = TimeUtil::getCurrentTimestampMS();
            if ($aliexpress_account === false) {
                //删除对应速卖通账号下的所有队列信息
                $getorder_number = QueueAliexpressGetorder::deleteAll($params);
                $logTimeMS2 = TimeUtil::getCurrentTimestampMS();
                $log .= ",t2_1_1=" . ($logTimeMS2 - $logTimeMS1);
                $getorder2_number = QueueAliexpressGetorder2::deleteAll($params);
                $logTimeMS2 = TimeUtil::getCurrentTimestampMS();
                $log .= ",t2_2_1=" . ($logTimeMS2 - $logTimeMS1);
                $finish_number = QueueAliexpressGetfinishorder::deleteAll($params);
                $logTimeMS2 = TimeUtil::getCurrentTimestampMS();
                $log .= ",t2_3_1=" . ($logTimeMS2 - $logTimeMS1);
                $log .= ", FINISHORDER={$finish_number}, GETORDER={$getorder_number}, GETORDER2={$getorder2_number}";
            }
            $log .= ",sellerloginid=" . $sellerloginid;
            \Yii::info($log, "file");
        }
        //getorder2
        \Yii::info("aliexress_check_orderstatus queue_aliexpress_getorder2 jobid=$backgroundJobId start", "file");
        #######################
        $command = $connection->createCommand('select `sellerloginid`,orderid from  `queue_aliexpress_getorder2` where times >= 10 group by sellerloginid asc order by sellerloginid desc limit 100');
        #######################
        $dataReader = $command->query();
        while (($row = $dataReader->read()) !== false) {
            $log = "aliexress_get_order_check_status queue_aliexpress_getorder2 jobid= " . $backgroundJobId;
            $sellerloginid = $row['sellerloginid'];
            $orderid = $row['orderid'];
            $params = ['sellerloginid' => $sellerloginid];
            //检查账号是否存在
            $aliexpress_account = \Yii::$app->db->createCommand('select * from saas_aliexpress_user where sellerloginid = "' . $sellerloginid . '"')->query()->read();
            $logTimeMS1 = TimeUtil::getCurrentTimestampMS();
            if ($aliexpress_account === false) {
                //删除对应速卖通账号下的所有队列信息
                $getorder2_number = QueueAliexpressGetorder2::deleteAll($params);
                $logTimeMS2 = TimeUtil::getCurrentTimestampMS();
                $log .= ",t2_4_1=" . ($logTimeMS2 - $logTimeMS1);
                $getorder_number = QueueAliexpressGetorder::deleteAll($params);
                $logTimeMS2 = TimeUtil::getCurrentTimestampMS();
                $log .= ",t2_5_1=" . ($logTimeMS2 - $logTimeMS1);
                $finish_number = QueueAliexpressGetfinishorder::deleteAll($params);
                $logTimeMS2 = TimeUtil::getCurrentTimestampMS();
                $log .= ",t2_6_1=" . ($logTimeMS2 - $logTimeMS1);
                $log .= ", FINISHORDER={$finish_number}, GETORDER={$getorder_number}, GETORDER2={$getorder2_number}";
            }
            $log .= ",sellerloginid=" . $sellerloginid;
            \Yii::info($log, "file");
        }

        //getorder2
        \Yii::info("aliexress_check_orderstatus queue_aliexpress_getfinishorder jobid=$backgroundJobId start", "file");
        #######################
        $command = $connection->createCommand('select `sellerloginid`,orderid from  `queue_aliexpress_getfinishorder` where times >= 10 group by sellerloginid asc order by sellerloginid desc limit 100');
        #######################
        $dataReader = $command->query();
        while (($row = $dataReader->read()) !== false) {
            $log = "aliexress_get_order_check_status queue_aliexpress_getfinishorder jobid= " . $backgroundJobId;
            $sellerloginid = $row['sellerloginid'];
            $orderid = $row['orderid'];
            $params = ['sellerloginid' => $sellerloginid];
            //检查账号是否存在
            $aliexpress_account = \Yii::$app->db->createCommand('select * from saas_aliexpress_user where sellerloginid = "' . $sellerloginid . '"')->query()->read();
            $logTimeMS1 = TimeUtil::getCurrentTimestampMS();
            if ($aliexpress_account === false) {
                //删除对应速卖通账号下的所有队列信息
                $finish_number = QueueAliexpressGetfinishorder::deleteAll($params);
                $logTimeMS2 = TimeUtil::getCurrentTimestampMS();
                $log .= ",t2_7_1=" . ($logTimeMS2 - $logTimeMS1);
                $getorder_number = QueueAliexpressGetorder::deleteAll($params);
                $logTimeMS2 = TimeUtil::getCurrentTimestampMS();
                $log .= ",t2_8_1=" . ($logTimeMS2 - $logTimeMS1);
                $getorder2_number = QueueAliexpressGetorder2::deleteAll($params);
                $logTimeMS2 = TimeUtil::getCurrentTimestampMS();
                $log .= ",t2_9_1=" . ($logTimeMS2 - $logTimeMS1);
                $log .= ", FINISHORDER={$finish_number}, GETORDER={$getorder_number}, GETORDER2={$getorder2_number}";
            }
            $log .= ",sellerloginid=" . $sellerloginid;
            \Yii::info($log, "file");
        }
        return false;
    }


    public function checkTokenStatus()
    {
        //Step 0, 检查当前本job版本，如果和自己的版本不一样，就自动exit吧
        $currentAliexpressGetOrderListVersion = ConfigHelper::getGlobalConfig("Order/aliexpressCheckTokenStatusVersion", 'NO_CACHE');
        if (empty($currentAliexpressGetOrderListVersion))
            $currentAliexpressGetOrderListVersion = 'v1';

        //如果自己还没有定义，去使用global config来初始化自己
        if (empty(self::$aliexpressGetOrderListVersion))
            self::$aliexpressGetOrderListVersion = $currentAliexpressGetOrderListVersion;

        //如果自己的version不同于全局要求的version，自己退出，从新起job来使用新代码
        if (self::$aliexpressGetOrderListVersion <> $currentAliexpressGetOrderListVersion) {
            exit("Version new $currentAliexpressGetOrderListVersion , this job ver " . self::$aliexpressGetOrderListVersion . " exits for using new version $currentAliexpressGetOrderListVersion.");
        }
        $backgroundJobId = self::getCronJobId();
        $connection = Yii::$app->db_queue;

        $api = new AliexpressInterface_Api ();

        #########################getorder##########################################
        \Yii::info("aliexpress_check_token_status queue_aliexpress_checkorder jobid=$backgroundJobId start", "file");
        ###获取数据and (message like '%Unauthorized or expired%' or message like '%not getting access token%')
        $command = $connection->createCommand("select sellerloginid,orderid from queue_aliexpress_getorder where times >= 10 group by sellerloginid asc limit 100");
        $dataReader = $command->query();
        while (($row = $dataReader->read()) !== false) {
            $logTimeMS1 = TimeUtil::getCurrentTimestampMS();
            $log = "aliexpress_check_token_status queue_aliexpress_checkorder jobid= " . $backgroundJobId;
            $log .= " sellerloginid=" . $row['sellerloginid'];
            //获取用户信息
            $aliexpress_account = self::_getAliexpressToken($row['sellerloginid']);
            $logTimeMS2 = TimeUtil::getCurrentTimestampMS();
            $log .= " t2_1 = " . ($logTimeMS2 - $logTimeMS1);

            if ($aliexpress_account === false) {
                self::_saveOrderInfo('queue_aliexpress_getorder', $row['sellerloginid']);
                $logTimeMS2 = TimeUtil::getCurrentTimestampMS();
                $log .= " t2_1_1 = " . ($logTimeMS2 - $logTimeMS1);
                \Yii::info("aliexpress_check_token_status queue_aliexpress_checkorder account_faile sellerloginid : {$row['sellerloginid']}", "file");
            } else {
                //第一次调用API，查看TOKEN是否可用
                $bool = self::_checkOrderInfo($api, $aliexpress_account, $row['orderid']);
                $logTimeMS3 = TimeUtil::getCurrentTimestampMS();
                $log .= " t3_2 = " . ($logTimeMS3 - $logTimeMS2);
                if ($bool === false) {
                    //获取TOKEN
                    $aliexpress_bool = self::_getAccessToken($aliexpress_account, $api);
                    $logTimeMS4 = TimeUtil::getCurrentTimestampMS();
                    $log .= " t4_3 " . ($logTimeMS4 - $logTimeMS3);
                    if ($aliexpress_bool !== false) {
                        $aliexpress_account['access_token'] = $aliexpress_bool['access_token'];
                        $bool = self::_checkOrderInfo($api, $aliexpress_account, $row['orderid']);
                        $logTimeMS5 = TimeUtil::getCurrentTimestampMS();
                        $log .= " t5_4 = " . ($logTimeMS5 - $logTimeMS4);
                        if ($bool === false) {
                            //执行删除操作
                            self::_saveOrderInfo('queue_aliexpress_getorder', $row['sellerloginid']);
                            $logTimeMS6 = TimeUtil::getCurrentTimestampMS();
                            $log .= " t6_1_5 = " . ($logTimeMS6 - $logTimeMS5);
                        } else {
                            //执行恢复操作
                            self::_recoveryOrderInfo('queue_aliexpress_getorder', $row['sellerloginid']);
                            $logTimeMS7 = TimeUtil::getCurrentTimestampMS();
                            $log .= " t7_1_5 = " . ($logTimeMS7 - $logTimeMS5);
                        }
                    } else {
                        //执行删除操作
                        self::_saveOrderInfo('queue_aliexpress_getorder', $row['sellerloginid']);
                        $logTimeMS8 = TimeUtil::getCurrentTimestampMS();
                        $log .= " t8_1_4 = " . ($logTimeMS8 - $logTimeMS4);
                    }
                } else {
                    //执行恢复操作
                    self::_recoveryOrderInfo('queue_aliexpress_getorder', $row['sellerloginid']);
                    $logTimeMS9 = TimeUtil::getCurrentTimestampMS();
                    $log .= " t9_1_3 = " . ($logTimeMS9 - $logTimeMS3);
                }
            }
            \Yii::info($log, "file");
        }
        \Yii::info("aliexpress_check_token_status queue_aliexpress_checkorder jobid=$backgroundJobId end", "file");
        #########################getorder##########################################


        #########################getorder2##########################################
        ###获取数据
//        \Yii::info("aliexpress_check_token_status queue_aliexpress_checkorder2 jobid=$backgroundJobId start","file");
//        $command = $connection->createCommand("select sellerloginid,orderid from queue_aliexpress_getorder2 where times >= 10 group by sellerloginid asc limit 100");
//        $dataReader=$command->query();
//        while(($row=$dataReader->read())!==false) {
//            $logTimeMS1 = TimeUtil::getCurrentTimestampMS();
//            $log = "aliexpress_check_token_status queue_aliexpress_checkorder2 jobid= " .$backgroundJobId;
//            $log .= " sellerloginid=".$row['sellerloginid'];
//            //获取用户信息
//            $aliexpress_account = self::_getAliexpressToken($row['sellerloginid']);
//            $logTimeMS2 = TimeUtil::getCurrentTimestampMS();
//            $log .= " t2_1 = ".($logTimeMS2 - $logTimeMS1);
//            if($aliexpress_account === false){
//                self::_saveOrderInfo('queue_aliexpress_getorder2',$row['sellerloginid']);
//                $logTimeMS2 = TimeUtil::getCurrentTimestampMS();
//                $log .= " t2_1_1 = ".($logTimeMS2 - $logTimeMS1);
//                \Yii::info("aliexpress_check_token_status queue_aliexpress_checkorder2 account_faile sellerloginid : {$row['sellerloginid']}","file");
//            } else {
//                //第一次调用API，查看TOKEN是否可用
//                $bool = self::_checkOrderInfo($api,$aliexpress_account,$row['orderid']);
//                $logTimeMS3 = TimeUtil::getCurrentTimestampMS();
//                $log .= " t3_2 = ".($logTimeMS3 - $logTimeMS2);
//                if($bool === false){
//                    //获取TOKEN
//                    $aliexpress_bool = self::_getAccessToken($aliexpress_account,$api);
//                    $logTimeMS4 = TimeUtil::getCurrentTimestampMS();
//                    $log .= " t4_3 = ".($logTimeMS4 - $logTimeMS3);
//                    if($aliexpress_bool !== false){
//                        $aliexpress_account['access_token'] = $aliexpress_bool['access_token'];
//                        $bool = self::_checkOrderInfo($api,$aliexpress_account,$row['orderid']);
//                        $logTimeMS5 = TimeUtil::getCurrentTimestampMS();
//                        $log .= " t5_4 = ".($logTimeMS5 - $logTimeMS4);
//                        if($bool === false){
//                            //执行删除操作
//                            self::_saveOrderInfo('queue_aliexpress_getorder2',$row['sellerloginid']);
//                            $logTimeMS6 = TimeUtil::getCurrentTimestampMS();
//                            $log .= " t6_2_5 = ".($logTimeMS6 - $logTimeMS5);
//                        } else {
//                            //执行恢复操作
//                            self::_recoveryOrderInfo('queue_aliexpress_getorder2',$row['sellerloginid']);
//                            $logTimeMS7 = TimeUtil::getCurrentTimestampMS();
//                            $log .= " t7_2_5 = ".($logTimeMS7 - $logTimeMS5);
//                        }
//                    } else {
//                        //执行删除操作
//                        self::_saveOrderInfo('queue_aliexpress_getorder2',$row['sellerloginid']);
//                        $logTimeMS8 = TimeUtil::getCurrentTimestampMS();
//                        $log .= " t8_2_4 = ".($logTimeMS8 - $logTimeMS4);
//                    }
//                } else {
//                    //执行恢复操作
//                    self::_recoveryOrderInfo('queue_aliexpress_getorder2',$row['sellerloginid']);
//                    $logTimeMS9 = TimeUtil::getCurrentTimestampMS();
//                    $log .= " t9_2_3 = ".($logTimeMS9 - $logTimeMS3);
//                }
//            }
//            \Yii::info($log,"file");
//        }
//        \Yii::info("aliexpress_check_token_status queue_aliexpress_checkorder2 jobid=$backgroundJobId end","file");
        #########################getorder2##########################################

        #########################getfinishorder##########################################
        \Yii::info("aliexpress_check_token_status queue_aliexpress_checkfinishorder jobid=$backgroundJobId start", "file");
        $command = $connection->createCommand("select sellerloginid,orderid from queue_aliexpress_getfinishorder where times >= 10 group by sellerloginid asc limit 100");
        $dataReader = $command->query();
        while (($row = $dataReader->read()) !== false) {
            $logTimeMS1 = TimeUtil::getCurrentTimestampMS();
            $log = "aliexpress_check_token_status queue_aliexpress_checkfinishorder jobid= " . $backgroundJobId;
            $log .= " sellerloginid=" . $row['sellerloginid'];
            //获取用户信息
            $aliexpress_account = self::_getAliexpressToken($row['sellerloginid']);
            $logTimeMS2 = TimeUtil::getCurrentTimestampMS();
            $log .= " t2_1 = " . ($logTimeMS2 - $logTimeMS1);
            if ($aliexpress_account === false) {
                self::_saveOrderInfo('queue_aliexpress_getfinishorder', $row['sellerloginid']);
                $logTimeMS2 = TimeUtil::getCurrentTimestampMS();
                $log .= " t2_1_1 = " . ($logTimeMS2 - $logTimeMS1);
                \Yii::info("aliexpress_check_token_status queue_aliexpress_checkfinishorder account_faile sellerloginid : {$row['sellerloginid']}", "file");
            } else {
                //第一次调用API，查看TOKEN是否可用
                $bool = self::_checkOrderInfo($api, $aliexpress_account, $row['orderid']);
                $logTimeMS3 = TimeUtil::getCurrentTimestampMS();
                $log .= " t3_2 = " . ($logTimeMS3 - $logTimeMS2);
                if ($bool === false) {
                    //获取TOKEN
                    $aliexpress_bool = self::_getAccessToken($aliexpress_account, $api);
                    $logTimeMS4 = TimeUtil::getCurrentTimestampMS();
                    $log .= " t4_3 = " . ($logTimeMS4 - $logTimeMS3);
                    if ($aliexpress_bool !== false) {
                        $aliexpress_account['access_token'] = $aliexpress_bool['access_token'];
                        $bool = self::_checkOrderInfo($api, $aliexpress_account, $row['orderid']);
                        $logTimeMS5 = TimeUtil::getCurrentTimestampMS();
                        $log .= " t5_4 = " . ($logTimeMS5 - $logTimeMS4);
                        if ($bool === false) {
                            //执行删除操作
                            self::_saveOrderInfo('queue_aliexpress_getfinishorder', $row['sellerloginid']);
                            $logTimeMS6 = TimeUtil::getCurrentTimestampMS();
                            $log .= " t6_3_5 = " . ($logTimeMS6 - $logTimeMS5);
                        } else {
                            //执行恢复操作
                            self::_recoveryOrderInfo('queue_aliexpress_getfinishorder', $row['sellerloginid']);
                            $logTimeMS7 = TimeUtil::getCurrentTimestampMS();
                            $log .= " t7_3_5 = " . ($logTimeMS7 - $logTimeMS5);
                        }
                    } else {
                        //执行删除操作
                        self::_saveOrderInfo('queue_aliexpress_getfinishorder', $row['sellerloginid']);
                        $logTimeMS8 = TimeUtil::getCurrentTimestampMS();
                        $log .= " t8_3_4 = " . ($logTimeMS8 - $logTimeMS4);
                    }
                } else {
                    //执行恢复操作
                    self::_recoveryOrderInfo('queue_aliexpress_getfinishorder', $row['sellerloginid']);
                    $logTimeMS9 = TimeUtil::getCurrentTimestampMS();
                    $log .= " t9_3_3 = " . ($logTimeMS9 - $logTimeMS3);
                }
            }
            \Yii::info($log, "file");
        }

        \Yii::info("aliexpress_check_token_status queue_aliexpress_checkfinishorder jobid=$backgroundJobId end", "file");
        #########################getfinishorder##########################################
        return false;
    }

    /**
     * 恢复异常信息订单
     * @param $table
     * @param $sellerloginid
     * @return array
     */
    private function _recoveryOrderInfo($table, $sellerloginid)
    {

        //
        $finish_sql = "UPDATE `{$table}` SET `message`='',`status`=0,`times`=0 WHERE sellerloginid = '{$sellerloginid}' AND `times` >= 10 AND `type` = 2";
        $finish_number = \Yii::$app->db_queue->createCommand($finish_sql)->execute();
        \Yii::info("aliexpress_check_token_status save_{$table}_abnormalorder, SQL : ({$finish_sql}), EXECUTE : {$finish_number}", "file");

        $new_sql = "UPDATE `{$table}` SET `message`='',`status`=0,`times`=0 WHERE sellerloginid = '{$sellerloginid}' AND `times` >= 10 AND `type` = 3";
        $new_number = \Yii::$app->db_queue->createCommand($new_sql)->execute();
        \Yii::info("aliexpress_check_token_status save_{$table}_neworder, SQL : ({$new_sql}), EXECUTE : {$new_number}", "file");

        $old_sql = "UPDATE `{$table}` SET `message`='',`status`=0,`times`=0 WHERE sellerloginid = '{$sellerloginid}' AND `times` >= 10 AND `type` = 5";
        $old_number = \Yii::$app->db_queue->createCommand($old_sql)->execute();
        \Yii::info("aliexpress_check_token_status save_{$table}_oldorder, SQL : ({$old_sql}), EXECUTE : {$old_number}", "file");

        $abnormal_sql = "UPDATE `{$table}` SET `type`=3,`message`='',`status`=0,`times`=0 WHERE sellerloginid = '{$sellerloginid}' AND `times` >= 10 AND `type` = 11";
        $abnormal_number = \Yii::$app->db_queue->createCommand($abnormal_sql)->execute();
        \Yii::info("aliexpress_check_token_status save_{$table}_abnormalorder, SQL : ({$abnormal_sql}), EXECUTE : {$abnormal_number}", "file");


        return;
    }

    private function _saveOrderInfo($table, $sellerloginid)
    {
        $command = \Yii::$app->db_queue->createCommand("select * from {$table} WHERE sellerloginid = '{$sellerloginid}'");
        $order_info = $command->query();
        while (($row = $order_info->read()) !== false) {
            $sql = "select * from queue_aliexpress_check_token_order where id = " . $row['id'];
            $check_info = \Yii::$app->db_queue->createCommand($sql)->query()->read();
            if ($check_info === false) {
                //移动数据
                $command = \Yii::$app->db_queue->createCommand()->insert('queue_aliexpress_check_token_order', $row);
                $command->execute();
                \Yii::info("aliexpress_check_token_status insert_queue_aliexpress_check_token_order, SQL : ({$command->getRawSql()})", "file");
            } else {
                $params = $row;
                unset($params['id']);
                $command = \Yii::$app->db_queue->createCommand()->update('queue_aliexpress_check_token_order', $params, ['id' => $row['id']]);
                $command->execute();
                \Yii::info("aliexpress_check_token_status update_queue_aliexpress_check_token_order, SQL : ({$command->getRawSql()})", "file");
            }
            \Yii::$app->db_queue->createCommand()->delete($table, ['id' => $row['id']])->execute();
            \Yii::info("aliexpress_check_token_status delete_order_id  : ({$row['id']})", "file");
        }
    }

    /**
     * @param $sellerloginid
     * @return array
     */
    private function _getAliexpressToken($sellerloginid)
    {
        return \Yii::$app->db->createCommand('select * from saas_aliexpress_user where sellerloginid = "' . $sellerloginid . '"')->query()->read();
    }

    private function _getAccessToken($aliexpress_account, &$api)
    {
        $refresh_token_timeout = $aliexpress_account['refresh_token_timeout'];
        $current_time = time();

        $api->access_token = $aliexpress_account['access_token'];

        $api->setAppInfo($aliexpress_account['app_key'], $aliexpress_account['app_secret']);
        if ($refresh_token_timeout < $current_time) {//refresh_token已经过期
            $day = ($current_time - $refresh_token_timeout) / 86400;//过期多少天
            if ($day < 30) {
                $rtn = $api->postponeToken($aliexpress_account['refresh_token'], $api->access_token);//换取新的refreshToken
                if (isset($rtn['refresh_token'])) {
                    $params = [
                        'refresh_token' => $rtn['refresh_token'],
                        'refresh_token_timeout' => AliexpressInterface_Helper::transLaStrTimetoTimestamp($rtn['refresh_token_timeout']),
                        'access_token' => $rtn['access_token'],
                        'access_token_timeout' => (time() + 28800), // 8 小时过期
                    ];
                    \Yii::$app->db->createCommand()->update('saas_aliexpress_user', $params, ['aliexpress_uid' => $aliexpress_account['aliexpress_uid']])->execute();
                    \Yii::info("aliexpress_check_token_status postponeToken success, sellerloginid = {$aliexpress_account['sellerloginid']} , result :" . json_encode($rtn, true), "file");
                } else {
                    \Yii::info("aliexpress_check_token_status get_refresh_token fail, sellerloginid = {$aliexpress_account['sellerloginid']} ,error_msg :" . json_encode($rtn, true), "file");
                    return false;
                }
            } else {
                \Yii::info("aliexpress_check_token_status get_refresh_token fail, sellerloginid = {$aliexpress_account['sellerloginid']} , error_msg  day > {$day}", "file");
                return false;
            }
        } else {
            //直接通过refresh_token获取新的access_token
            $rtn = $api->refreshTokentoAccessToken($aliexpress_account['refresh_token']);
            if (isset($rtn['access_token'])) {
                $params = [
                    'access_token' => $rtn['access_token'],
                    'access_token_timeout' => (time() + 28800), // 8 小时过期
                ];
                \Yii::$app->db->createCommand()->update('saas_aliexpress_user', $params, ['aliexpress_uid' => $aliexpress_account['aliexpress_uid']])->execute();
                \Yii::info("aliexpress_check_token_status refreshTokentoAccessToken success, sellerloginid = {$aliexpress_account['sellerloginid']} , result :" . json_encode($rtn, true), "file");
            } else {
                \Yii::info("aliexpress_check_token_status get_access_token fail, sellerloginid = {$aliexpress_account['sellerloginid']} , error_msg :" . json_encode($rtn, true), "file");
                return false;
            }
        }
        return $rtn;
    }

    /**
     * @param $api
     * @param $row
     * @param $orderid
     * @return bool
     */
    private function _checkOrderInfo(&$api, $row, $orderid)
    {
        $api->access_token = $row['access_token'];
        $api->AppKey = $row['app_key'];
        $api->appSecret = $row['app_secret'];
        // 接口传入参数速卖通订单号
        $param = array(
            'orderId' => $orderid
        );
        // 调用接口获取订单列表
        $api->setAppInfo($row['app_key'], $row['app_secret']);
        $result = $api->findOrderById($param);
        \Yii::info("queue_aliexpress_check_token_status, sellerloginid = {$row['sellerloginid']}, api_result : " . json_encode($result, true), "file");
        if (isset($result['error_message'])) {
            if ($result['error_message'] == 'Request need user authorized') {
                return false;
            } else {
                return true;
            }
        } else {
            return true;
        }
    }

    /**
     * 同步速卖通刊登类目
     *
     */
    public static function autoSyncAliexpressCategory($sellerloginid, $categoryidsArr, $access_token = null)
    {
        if ($access_token === null) {
            $a = AliexpressInterface_Auth::checkToken($sellerloginid);
            if ($a) {
                $api = new AliexpressInterface_Api ();
                $access_token = $api->getAccessToken($sellerloginid);
                //获取访问token失败
                if ($access_token === false) {
                    echo $sellerloginid . 'not getting access token!' . "\n";
                    return;

                }
            }
            $api->access_token = $access_token;
        } else {
            $api = new AliexpressInterface_Api ();
            $api->access_token = $access_token;
        }


        foreach ($categoryidsArr as $cateid) {
            echo "cateid:$cateid \n"; //lolotest
            $response = $api->getChildrenPostCategoryById(array('cateId' => $cateid));
            
            if (isset($response['success'])) {
                if ($response['success']) {
                    $arr = array();
                    foreach ($response['aeopPostCategoryList'] as $category) {
                        //保存类目信息

                        $obj = AliexpressCategory::find()->where(["cateid" => $category['id']])->one();
                        if ($obj == null) {
                            $obj = new AliexpressCategory();
                            $obj->created = time();
                            $obj->updated = time();
                        } else {
                            $obj->updated = time();
                        }

                        $obj->cateid = $category['id'];
                        $obj->pid = $cateid;
                        $obj->level = $category['level'];
                        if (!isset($category['names']['zh'])) {
                            $obj->name_zh = $category['names']['en'];
                        } else $obj->name_zh = $category['names']['zh'];
                        $obj->name_en = $category['names']['en'];
                        $obj->isleaf = $category['isleaf'] == 1 ? 'true' : 'false';

                        //是否叶子节点
                        if (!$category['isleaf']) {
                            $arr[] = $category['id'];
                        } else {
                            //同步属性
                            //$attribute = self::getAttributeForOneCat($api, $category['id']);
                            //if ($attribute != false) {
                                //$obj->attribute = json_encode($attribute);
                            //}
                        }
                        $obj->save(false);
                    }

                    if (count($arr) > 0) {
                        self::autoSyncAliexpressCategory($sellerloginid, $arr, $access_token);
                    }

                }
            }
        }


    }

    /**
     *
     * @param unknown $sellerloginid
     * @param unknown $cateid
     */
    public static function getAttributeForOneCat(&$api, $cateid)
    {
        $response = $api->getAttributesResultByCateId(array('cateId' => $cateid));
        if (!isset($response['success']) or !$response['success']) return false;
        $attributes = $response['attributes'];
        $batchInsertDatas = array();

        $attributesInsertDatas = array();
        foreach ($attributes as $attributeInfo) {

            $attributesInsertData = array();
            $attributesInsertData["id"] = $attributeInfo["id"];
            $attributesInsertData["zh_name"] = $attributeInfo["names"]["zh"];
            $attributesInsertData["en_name"] = $attributeInfo["names"]["en"];

            if ($attributeInfo["attributeShowTypeValue"] == "input") {
                //该属性的值是不可选的
            } else {
                //该属性的值是可选的
                if (isset($attributeInfo["values"])) {
                    $valuesArr = $attributeInfo["values"];
                    $attributesInsertData["values"] = array();
                    foreach ($valuesArr as $valueArr) {
                        $valueInsertData = array();
                        $valueInsertData["id"] = $valueArr["id"];
                        $valueInsertData["zh_name"] = $valueArr["names"]["zh"];
                        $valueInsertData["en_name"] = $valueArr["names"]["en"];
                        $attributesInsertData["values"][$valueArr["id"]] = $valueInsertData;
                    }

                }
            }

            $attributesInsertDatas[$attributeInfo["id"]] = $attributesInsertData;
        }
        return $attributesInsertDatas;

        /*if ($attributeInfo["attributeShowTypeValue"]=="input"){
    			$batchInsertData=array();
    			$batchInsertData["category_id"]=$cateid;
    			$batchInsertData["attribute_id"]=$attributeInfo["id"];
    			$batchInsertData["attribute_value_id"]=-1;
    			$batchInsertData["zh_name"]="";
    			$batchInsertData["en_name"]="";
    			$batchInsertData["attribute_zh_name"]=$attributeInfo["names"]["zh"];
    			$batchInsertData["attribute_en_name"]=$attributeInfo["names"]["en"];

    			$batchInsertDatas[]=$batchInsertData;
    			continue;
    		}

    		$valuesArr=$attributeInfo["values"];
    		foreach($valuesArr as $valueArr){
    			$batchInsertData=array();
    			$batchInsertData["category_id"]=$cateid;
    			$batchInsertData["attribute_id"]=$attributeInfo["id"];
    			$batchInsertData["attribute_value_id"]=$valueArr["id"];
    			$batchInsertData["zh_name"]=$valueArr["names"]["zh"];
    			$batchInsertData["en_name"]=$valueArr["names"]["en"];
    			$batchInsertData["attribute_zh_name"]=$attributeInfo["names"]["zh"];
    			$batchInsertData["attribute_en_name"]=$attributeInfo["names"]["en"];

    			$batchInsertDatas[]=$batchInsertData;
    		}*/
        //	}

        //	SQLHelper::groupInsertToDb("aliexpress_category_attribute", $batchInsertDatas,"db");

    }

    /**
     * 同步速卖通刊登类目属性信息
     *
     */
    /*    public static function autoSyncAliexpressCategoryAttribute($sellerloginid){


    	$a = AliexpressInterface_Auth::checkToken ( $sellerloginid);
    	if ($a) {
    		$api = new AliexpressInterface_Api ();
    		$access_token = $api->getAccessToken ( $sellerloginid);
    		//获取访问token失败
    		if ($access_token === false){
    			echo $sellerloginid. 'not getting access token!' . "\n";
    			return;

    		}
    	}
    	$api->access_token = $access_token;

    	$connection=\Yii::$app->db;
    	$sqlStr='select cateid from `aliexpress_category` where `isleaf`="true"';
    	$command=$connection->createCommand($sqlStr);
    	#################################
    	$dataReader=$command->query();
    	//	$activeUsersPuidArr = UserLastActionTimeHelper::getPuidArrByInterval(7*24);
    	$index=0;
    	while(($row=$dataReader->read())!==false) {

    		$cateid=$row["cateid"];
    		self::saveAttributeForOneCat($api,$cateid);
    		$index++;
    		if ($index>20) die;

    	}


    }    */


    public static function getListingDetail($puid, $sellerloginid)
    {


        $queue = Yii::$app->db_queue;
        $db= Yii::$app->db;
        $api = new AliexpressInterface_Api ();
        $access_token = $api->getAccessToken($sellerloginid);
        $api->access_token = $access_token;
         
//     	$connection=\Yii::$app->subdb;
//     	$sqlStr='select productid from `aliexpress_listing`';
//     	$command=$connection->createCommand($sqlStr);

//     	#################################
//     	$dataReader=$command->query();
//     	//	$activeUsersPuidArr = UserLastActionTimeHelper::getPuidArrByInterval(7*24);
//     	$index=0;
//     	while(($row=$dataReader->read())!==false) {

        $listings = AliexpressListing::find()->where(['selleruserid' => $sellerloginid])->asArray()->all();
        echo "aliexpress_listing count:" . count($listings) . PHP_EOL;

        //开启验证修改后,以下的删除功能注释
        //AliexpressListingDetail::deleteAll(['selleruserid' => $sellerloginid]);

        foreach ($listings as $row) {
            $productid = $row["productid"];
            if( $productid==0 ){
                continue;
            }
            $productInfo = $api->findAeProductById(array('productId' => $productid));

//     		print_r($productInfo);exit();
            if (empty($productInfo['success']) || $productInfo['success'] != 1) {
                //没有获取到任何信息,删除listing表中的数据
                AliexpressListing::deleteAll(['productid' => $productid]);
                AliexpressListingDetail::deleteAll(['productid' => $productid]);
                echo "puid:$puid,sellerloginid:$sellerloginid,productid:$productid not get detail. message:" . $productInfo['error_message'] . PHP_EOL;
                continue;
            }
            //加密详细信息
            $listen_detail_md5= md5( json_encode( $productInfo ) );
            //通过商品ID ,获取加密表数据
            $sql= "SELECT * FROM queue_product_info_md5 WHERE product_id='{$productid}' ";
            $query= $queue->createCommand( $sql )->query();
            $rs= $query->read();
            if( empty( $rs )  ){
                $insert= "INSERT INTO queue_product_info_md5 (`product_id`,`listen_detail_md5`,`selleruserid`)VALUES ('{$productid}','{$listen_detail_md5}','{$sellerloginid}')";
                $queue->createCommand( $sql )->execute();

            }else{
                $alerdy_listen_detail_md5= $rs['listen_detail_md5'];
                if( $alerdy_listen_detail_md5!=$listen_detail_md5 ){
                    //update
                    $sql= "UPDATE queue_product_info_md5 SET listen_detail_md5='{$listen_detail_md5}' WHERE id= ".$rs['id'];

                    $queue->createCommand( $sql )->execute();
                    //delete
                     AliexpressListingDetail::deleteAll(['productid' => $productid]);

                }else{
                    continue;
                }
            }

            //$index++;
            //if ($index>20) die;
            $aliexpressListDetail = new AliexpressListingDetail;
            $aliexpressListDetail->productid = $productid;

            if (!empty($productInfo["categoryId"])) {
                $aliexpressListDetail->categoryid = $productInfo["categoryId"];
            }
            $aliexpressListDetail->selleruserid = $sellerloginid;
            if (!empty($productInfo["productPrice"])) {
                $aliexpressListDetail->product_price = $productInfo["productPrice"];
            }

            if (!empty($productInfo["grossWeight"])) {
                $aliexpressListDetail->product_gross_weight = $productInfo["grossWeight"];
            }

            if (!empty($productInfo["packageLength"])) {
                $aliexpressListDetail->product_length = $productInfo["packageLength"];
            }

            if (!empty($productInfo["packageWidth"])) {
                $aliexpressListDetail->product_width = $productInfo["packageWidth"];
            }

            if (!empty($productInfo["packageHeight"])) {
                $aliexpressListDetail->product_height = $productInfo["packageHeight"];
            }

            if (!empty($productInfo["currencyCode"])) {
                $aliexpressListDetail->currencyCode = $productInfo["currencyCode"];
            }

            if (!empty($productInfo["aeopAeProductPropertys"])) {
                $aliexpressListDetail->aeopAeProductPropertys = json_encode($productInfo["aeopAeProductPropertys"]);
            } else {
                $aliexpressListDetail->aeopAeProductPropertys = json_encode(array());
            }

            if (!empty($productInfo["aeopAeProductSKUs"])) {
                $aliexpressListDetail->aeopAeProductSKUs = json_encode($productInfo["aeopAeProductSKUs"]);
                $arr_sku= $productInfo['aeopAeProductSKUs'];
                $skucode_arr= array();
                foreach( $arr_sku as $vss_sku ){
                    if( isset($vss_sku['skuCode']) && $vss_sku['skuCode']!='' ){
                        $skucode_arr[]= $vss_sku['skuCode'];
                    }
                }
                if( !empty( $skucode_arr ) ){
                    $skucode_str= implode(';',$skucode_arr);
                }else{
                    $skucode_str= '';
                }
                $aliexpressListDetail->sku_code= $skucode_str;

            } else {
                $aliexpressListDetail->aeopAeProductSKUs = json_encode(array());
            }

            if (!empty($productInfo["detail"])) {
                $aliexpressListDetail->detail = $productInfo["detail"];
            }

            $aliexpressListDetail->listen_id = $row['id'];

            if( !empty( $productInfo['deliveryTime'] ) ){
                $aliexpressListDetail->delivery_time = $productInfo['deliveryTime'];
            }

            if( !empty( $productInfo['packageType'] ) ){
                $aliexpressListDetail->package_type = $productInfo['packageType'];
            }

            if( !empty( $productInfo['lotNum'] ) ){
                $aliexpressListDetail->lot_num = $productInfo['lotNum'];
            }

            if( !empty( $productInfo['isPackSell'] ) ){
                $aliexpressListDetail->isPackSell = $productInfo['isPackSell'];
            }

            if( !empty( $productInfo['reduceStrategy'] ) ){
                $aliexpressListDetail->reduce_strategy = $productInfo['reduceStrategy'];
            }
            if( !empty( $productInfo['productUnit'] ) ){
                $aliexpressListDetail->product_unit = $productInfo['productUnit'];
            }

            if( !empty( $productInfo['wsValidNum'] ) ){
                $aliexpressListDetail->wsValidNum = $productInfo['wsValidNum'];
            }

            if( !empty( $productInfo['currencyCode'] ) ){
                $aliexpressListDetail->currencyCode = $productInfo['currencyCode'];
            }

            //if( !empty( $productInfo['promiseTemplateId'] ) ){
                $aliexpressListDetail->promise_templateid = $productInfo['promiseTemplateId'];
            //}

            if( !empty( $productInfo['groupIds'] ) ){
                $aliexpressListDetail->product_groups = implode(',',$productInfo['groupIds']);
            }


            $aliexpressListDetail->save(false);

            if( !empty( $productInfo['freightTemplateId'] ) ){
                AliexpressListing::updateAll( ['freight_template_id'=>$productInfo['freightTemplateId']],'id='.$row['id'] );
            }

        }
    }


    public static function cronExportListing()
    {

        $job_name = "export_ali_listing";
        $nowTime = time();

        //status： 0 未处理 ，1处理中 ，2完成 ，3失败，4已下载
        $recommendJobObjs = UserBackgroundJobControll::find()
            ->where('is_active="Y" AND (status=0 or status=3) AND job_name="' . $job_name . '" AND error_count<5')
            ->orderBy('next_execution_time')->limit(5)->all(); // 多到一定程度的时候，就需要使用多进程的方式来并发拉取。

        if (!empty($recommendJobObjs)) {
            try {
                foreach ($recommendJobObjs as $recommendJobObj) {
                    //1. 先判断是否可以正常抢到该记录
                    $recommendJobAttrs = array();
                    $recommendJobAttrs['status'] = 1;
                    $recommendJobAttrs['last_begin_run_time'] = $nowTime;
                    $affectRows = UserBackgroundJobControll::updateAll($recommendJobAttrs, "id=" . $recommendJobObj->id . " and status <> 1");
                    if ($affectRows <= 0) continue; //抢不到

                    $recommendJobObj = UserBackgroundJobControll::findOne($recommendJobObj->id);
                    $puid = $recommendJobObj->puid;
 
                    // 获取导出格式
                    $additionalInfo = json_decode($recommendJobObj->additional_info, true);
                    $ensogoAccount = "";
                    if (!empty($additionalInfo['ensogo_account'])) {
                        $ensogoAccount = $additionalInfo['ensogo_account'];
                    }
                    if ("csv" == $additionalInfo['exportType']) {
                        self::exportLiting($recommendJobObj->custom_name, $ensogoAccount, "csv", false, $puid);
                    } else {// $additionalInfo['exportType'] == "excel"
                        self::exportLiting($recommendJobObj->custom_name, $ensogoAccount, "xls", false, $puid);
                    }

                    $recommendJobObj->status = 2;
                    $recommendJobObj->is_active = "N"; // 导出完关闭job
                    $recommendJobObj->last_finish_time = time();
                    if (!$recommendJobObj->save(false)) {
                        \Yii::error("exportListing " . json_encode($recommendJobObj), "file");
                    }

                }
            } catch (\Exception $e) {
                print_r($e);
                $errorMessage = "file:" . $e->getFile() . " line:" . $e->getLine() . " message:" . $e->getMessage();
                \Yii::error("exportListing " . $errorMessage, "file");
                self::handleBgJobError($recommendJobObj, $errorMessage);
            }
        }

    }

    // 导出ali listing 到excel for ensogo 刊登
    // dzt 2016-02-23
    public static function exportLiting($sellerloginid, $ensogoAccount = NULL, $type = "xls", $isDownload = true, $puid = NULL)
    {
        if ($puid === null) {
            $puid = \Yii::$app->user->identity->getParentUid();
        }

        //1. get ali listing 信息 然后将ali listing 信息组合成 ensogo 信息
        $aliListListings = AliexpressListing::find()->where(['selleruserid' => $sellerloginid])->asArray()->all();
        $aliListListingDetails = AliexpressListingDetail::find()->where(['selleruserid' => $sellerloginid])->asArray()->all();
        $aliListListingDetailIdMap = Helper_Array::toHashmap($aliListListingDetails, "productid");
        $aliCategories = AliexpressCategory::find()->asArray()->all();
        $aliCategoryCidMap = Helper_Array::toHashmap($aliCategories, "cateid");

//     	echo "start puid:".$puid.",selleruserid:".$sellerloginid."\n";
        \Yii::info("start puid:" . $puid . ",selleruserid:" . $sellerloginid, "file");

        $ensogoListings = array();
        foreach ($aliListListings as $aliListListing) {
            if (empty($aliListListingDetailIdMap[$aliListListing['productid']])) continue;

            $aliListListingDetail = $aliListListingDetailIdMap[$aliListListing['productid']];
//     		echo "categoryid:".$aliListListingDetail['categoryid']."\n";

            $aliCategory = array();
            $catAttrs = array();
            $aeopAeProductSKUs = array();
            $aeopAeProductPropertys = array();
            if (!empty($aliListListingDetail['categoryid']) && !empty($aliCategoryCidMap[$aliListListingDetail['categoryid']])) {
                $aliCategory = $aliCategoryCidMap[$aliListListingDetail['categoryid']];
                $catAttrs = json_decode($aliCategory['attribute'], true);
            }

            $aeopAeProductSKUs = json_decode($aliListListingDetail['aeopAeProductSKUs'], true);
            $aeopAeProductPropertys = json_decode($aliListListingDetail['aeopAeProductPropertys'], true);

            $aliCatWithParents = "";
            if (!empty($aliCategory)) {// 获取多层目录名
                $loopLimit = 0;
                $tempCliCategory = $aliCategory;
                while ($tempCliCategory['pid'] <> 0 && $loopLimit <= 50) {// 不清楚会不会出现死循环，所以加Limit
                    if ($loopLimit == 0) {
                        $aliCatWithParents = $tempCliCategory['name_zh'];
                    } else {
                        $aliCatWithParents = $tempCliCategory['name_zh'] . " > " . $aliCatWithParents;
                    }

                    $loopLimit++;
                    $tempCliCategory = $aliCategoryCidMap[$tempCliCategory['pid']];
                }
            }


            $prodAttrs = array();
            foreach ($aeopAeProductPropertys as $oneProperty) {// 获取产品所属目录属性
                $en_name = "";
                if (isset($oneProperty['attrNameId'])) {
//     				if(empty($catAttrs[$oneProperty['attrNameId']])){// 更新目录属性
//     					// 200001091目录的200000631 这个属性刷了几次都刷不到
//     					echo "attrNameId:".$oneProperty['attrNameId']."\n";
//     					echo "cid:".$aliCategory['cateid']."\n";
//     					AliexpressHelper::autoSyncAliexpressCategory($sellerloginid, array($aliCategory['pid']));
//     					$aliCategory = AliexpressCategory::findOne($aliListListingDetail['categoryid']);// 必须有
//     					$catAttrs = json_decode($aliCategory['attribute'],true);
//     				}

                    // 因为有些目录属性的缺失 更新过目录属性依然找不到，所以这里找不到就跳过了。
                    if (empty($catAttrs[$oneProperty['attrNameId']])) {
                        \Yii::error("exportListing cannot get property:" . json_encode($oneProperty), "file");
                        continue;
                    }

                    if (isset($catAttrs[$oneProperty['attrNameId']])) {
                        $attr = $catAttrs[$oneProperty['attrNameId']];
                        $en_name = $attr['en_name'];
                        if (isset($oneProperty['attrValue'])) {
                            $prodAttrs[$en_name] = $oneProperty['attrValue'];
                        } else if (isset($oneProperty['attrValueId']) && isset($attr['values'][$oneProperty['attrValueId']])) {
                            $prodAttrs[$en_name] = $attr['values'][$oneProperty['attrValueId']]['en_name'];
                        } else {
                            \Yii::error("exportListing category:" . $aliCategory['cateid'] . " porperty:$en_name cannot get value:" . json_encode($oneProperty), "file");
                        }
                    }

                } else if (isset($oneProperty['attrName'])) {
                    $en_name = $oneProperty['attrName'];
                    if (isset($oneProperty['attrValue'])) {
                        $prodAttrs[$en_name] = $oneProperty['attrValue'];
                    }
                }

                if (empty($en_name)) {
                    \Yii::error("exportListing category:" . $aliCategory['cateid'] . " cannot get property:" . json_encode($oneProperty), "file");
                }
            }

            $prodSkus = array();
            $isEnsogoConfig = false;
            if (!empty($aeopAeProductSKUs)) {
                foreach ($aeopAeProductSKUs as $oneSku) {// 获取变参属性
                    // 确保有sku
                    if (!empty($oneSku['skuCode']))
                        $skuCode = $oneSku['skuCode'];
//     				else if(!empty($oneSku['id']))
//     					$skuCode = $oneSku['id'];// 14:771 这样子的
                    else
                        $skuCode = self::generateSku();// 随机8位 0-9 A-Z a-z

                    $prodSkus[$skuCode] = array();
                    // 应该是原价，listing 和 listing detail 都没找到discount 信息，可能在$prodAttrs里面
                    $prodSkus[$skuCode]['skuCode'] = $skuCode;
                    $prodSkus[$skuCode]['skuPrice'] = $oneSku['skuPrice'];
                    $prodSkus[$skuCode]['currencyCode'] = $oneSku['currencyCode'];
                    $prodSkus[$skuCode]['ipmSkuStock'] = $oneSku['ipmSkuStock'];

                    if (!empty($oneSku['aeopSKUProperty'])) {
                        // 查看过两个或以上 config属性的数据结构
//     					if(count($oneSku['aeopSKUProperty'])>1){
//     						print_r($oneSku['aeopSKUProperty']);
//     						self::handleBgJobError($recommendJobObj, "test");
//     						exit();
//     					}

                        // 查看过两个或以上 config属性时的获取情况，$SKUProperty 都是只有skuPropertyId和propertyValueId
                        foreach ($oneSku['aeopSKUProperty'] as $SKUProperty) {
                            if (!empty($SKUProperty['skuImage']))
                                $prodSkus[$skuCode]['skuImage'] = $SKUProperty['skuImage'];// 目前skuImage是填最后一个aeopSKUProperty 的skuImage
                            $en_name = "";
                            if (isset($SKUProperty['skuPropertyId']) && isset($catAttrs[$SKUProperty['skuPropertyId']])) {
                                $attr = $catAttrs[$SKUProperty['skuPropertyId']];
                                $en_name = $attr['en_name'];
                                if ("Color" == $en_name || "Size" == $en_name) {
                                    $isEnsogoConfig = true;
                                }
                                if (isset($attr['values'][$SKUProperty['propertyValueId']])) {
                                    $prodSkus[$skuCode][$en_name] = $attr['values'][$SKUProperty['propertyValueId']]['en_name'];
                                } else {
                                    \Yii::error("exportListing cannot get Property:$en_name value .SKUProperty:" . json_encode($SKUProperty), "file");
                                }
                            }

                            if (empty($en_name)) {
                                \Yii::error("exportListing cannot get SKUProperty:" . json_encode($SKUProperty), "file");
                            }
                        }
                    }
                }
            } else {// 木有$aeopAeProductSKUs 信息，自己伪造 sku 信息
//     			$skuCode = self::generateSku();
//     			$prodSkus[$skuCode] = array();
//     			$prodSkus[$skuCode]['skuCode'] = $skuCode;
                // 如果加了很多信息都要判断是否为空，所以这里还是不伪造了。这样这个Listing就不会导出
                \Yii::error("exportListing  would not export listing with no aeopAeProductSKUs info:" . json_encode($aliListListing), "file");
            }

//     		print_r($aliListListing);
//     		print_r($aliListListingDetail);
//     		print_r($prodAttrs);
//     		print_r($prodSkus);
//     		if('女装 > 上衣，T恤 > T恤' == $aliCatWithParents){
//     			print_r($prodSkus);
//     		}

            $moreDetail = "<table><tbody>";
            foreach ($prodAttrs as $attrKey => $attrValue) {
                $moreDetail .= "<tr>";
                $moreDetail .= "<td>" . $attrKey . "</td>";
                $moreDetail .= "<td>" . $attrValue . "</td>";
                $moreDetail .= "</tr>";
            }
            $moreDetail .= "</tbody></table>";
            // dzt20160218 现在ensogo 所有平台的搬家 我们这里都会舍弃父类SKU 用第一个变种的SKU替代
            // 图片都放到这个sku，子sku 只保存主图
            // ensogo 支持的config 属性只有 color 和 size 对应 ali Color , Size 属性
            // ali config 属性如果不是color 或者 size ， 则将这个数据加入 subject

            // dzt20160324 建鹏要求 速卖通的变参不管是否color和size都 写入ensogo的变参里面
            // 多属性没有color的话,color就填第一个值，剩下的多属性值就以逗号为分隔符串在size里面;
            // 多属性如果有color,剩下的多属性值就都串在size里面

            // 2.1 判断是否 ali config 产品，不是的话，直接当父产品
            // 注意，添加字段顺序不能随便改，会影响导出

            \Yii::info("exportListing count sku:" . count($prodSkus), "file");
//     		echo "exportListing count sku:".count($prodSkus),PHP_EOL;
            if (count($prodSkus) == 1) {
                foreach ($prodSkus as $skuCode => $prodSku) {
                    $ensogoListings[$skuCode] = array();
                    $ensogoListings[$skuCode]['ali_cat'] = $aliCatWithParents;//速卖通分类
                    $ensogoListings[$skuCode]['ensogo_cat'] = "";//*ensogo分类
                    $ensogoListings[$skuCode]['ensogo_store'] = $ensogoAccount;// *发布店铺
//     				$ensogoListings[$skuCode]['parent_sku'] = $aliListListing['productid'];//*父SKU. 由于只有一个产品，所以用product id即可
                    $ensogoListings[$skuCode]['parent_sku'] = $prodSku['skuCode'];// dzt20160218 由于只有一个产品，所以用自身sku即可
                    $ensogoListings[$skuCode]['sku'] = $prodSku['skuCode'];//*子SKU
                    $ensogoListings[$skuCode]['subject'] = $aliListListing['subject'];//*产品标题
                    $ensogoListings[$skuCode]['color'] = "";//颜色

                    $ensogoListings[$skuCode]['size'] = "";//尺寸。
                    if (isset($prodAttrs['Size'])) {
                        $ensogoListings[$skuCode]['size'] = $prodAttrs['Size'];// 尽管这里填了size，但只有一个产品
                    }

                    // 去掉纯数字的标签，去掉只有1个或者2个字符的标签，去掉For，To（忽略大小写）标签，取前10个标签，Rear-end这种单词合并为一个标签
                    $tempTagStr = $aliListListing['subject'];
//     				$tempTagStr = str_ireplace('rear end','Rear-end',$tempTagStr);// 替换词语
                    $tempTags = explode(" ", $tempTagStr);
                    $tempTags = str_ireplace(array('for', 'to'), '', $tempTags);// 去掉for to
                    $tags = array();

                    foreach ($tempTags as $tag) {
                        if (empty($tag) || strlen($tag) <= 2 || is_numeric($tag))
                            continue;

                        if (count($tags) >= 10)
                            break;

                        $tags[] = $tag;
                    }
//     				Helper_Array::removeEmpty($tags);
                    $ensogoListings[$skuCode]['tag'] = implode(",", $tags);//*产品标签(用英文逗号[,]隔开)

                    $ensogoListings[$skuCode]['description'] = "";// *产品描述
                    if (isset($aliListListingDetail['detail'])) {
                        // dzt20160321 detail字段在拉取时候 json_encode了，但没留意到把它转为array再encode，这样json_decode这个字符串会报错，所以这里做些bug fix
                        // 后面detail拉取已经改了就可以不用这部分逻辑
                        $tempDetail = @json_decode($aliListListingDetail['detail'], true);
                        if (empty($tempDetail) && !is_array($tempDetail)) {
                            $aliListListingDetail['detail'] = '{"detail":' . $aliListListingDetail['detail'] . "}";
                            $tempDetail = @json_decode($aliListListingDetail['detail'], true);
                            if (!empty($tempDetail)) {
                                $aliListListingDetail['detail'] = $tempDetail['detail'];
                            } else {
                                $aliListListingDetail['detail'] = "";
                            }
                        } elseif (!empty($tempDetail['detail'])) {
                            $aliListListingDetail['detail'] = $tempDetail['detail'];
                        } elseif (is_string($tempDetail)) {
                            $aliListListingDetail['detail'] = $tempDetail;
                        }

//     					$aliListListingDetail['detail'] = stripslashes($aliListListingDetail['detail']);
                        // 替换速卖通aeProduct.getSubject() 为产品subject
                        $aliListListingDetail['detail'] = str_ireplace("aeProduct.getSubject()", $ensogoListings[$skuCode]['subject'], $aliListListingDetail['detail']);
                        $ensogoListings[$skuCode]['description'] = $moreDetail . $aliListListingDetail['detail'];
                    } else {
                        $ensogoListings[$skuCode]['description'] = $moreDetail;
                    }

                    $ensogoListings[$skuCode]['general_price'] = "";// 市场价($)

                    $ensogoListings[$skuCode]['price'] = "";// *售价($)
                    if (isset($prodSku['skuPrice'])) {
                        $ensogoListings[$skuCode]['price'] = Helper_Currency::convert($prodSku['skuPrice'], "USD", $prodSku['currencyCode']);
                    } else if (isset($aliListListingDetail['product_price'])) {
                        $ensogoListings[$skuCode]['price'] = Helper_Currency::convert($aliListListingDetail['product_price'], "USD", $aliListListingDetail['currencyCode']);
                    }

                    $ensogoListings[$skuCode]['stock'] = "";
                    if (isset($prodSku['ipmSkuStock'])) {
                        $ensogoListings[$skuCode]['stock'] = $prodSku['ipmSkuStock'];// *库存
                    }

                    $ensogoListings[$skuCode]['delivery_fee'] = "";//*运费($)
                    $ensogoListings[$skuCode]['delivery_duration'] = "15-30";//*运输时间(天)

                    $ensogoListings[$skuCode]['brand'] = "Other";//品牌
                    if (isset($prodAttrs['Brand Name'])) {
                        $ensogoListings[$skuCode]['brand'] = $prodAttrs['Brand Name'];
                    } else if (isset($prodAttrs['Model Number'])) {
                        $ensogoListings[$skuCode]['brand'] = $prodAttrs['Model Number'];
                    }

                    $ensogoListings[$skuCode]['upc'] = "";//UPC（通用产品代码）
                    $ensogoListings[$skuCode]['product_url'] = "";//Landing Page URL
                    $ensogoListings[$skuCode]['main_image'] = $aliListListing["photo_primary"];//*产品主图链接
                    if (!empty($aliListListing["imageurls"])) {
                        $otherImages = explode(";", $aliListListing["imageurls"]);
                        $index = 1;
                        foreach ($otherImages as $image) {
                            $ensogoListings[$skuCode]['images' . $index++] = $image; // 附图链接
                            if ($index > 10) break;
                        }
                    }
                }
            } else {// 2.2 如果是ali conf 产品，检查是否符合 ensogo config 产品，不是的话，以ali config 属性加入 subject 当一个父产品
                if ($isEnsogoConfig == false) {
                    $prodSkuIndex = 1;
                    $parentSku = "";
                    foreach ($prodSkus as $skuCode => $prodSku) {
                        // dzt20160324 之前这里有Bug 2.2 按道理不用设置公共 $parentSku而是每个变参 都一个$parentSku,但根据 建鹏的修改这里又可以保留了。
                        if (empty($parentSku)) // dzt20160218 舍弃父类SKU 用第一个变种的SKU替代
                            $parentSku = $prodSku['skuCode'];

                        $ensogoListings[$skuCode] = array();
                        $ensogoListings[$skuCode]['ali_cat'] = $aliCatWithParents;//速卖通分类
                        $ensogoListings[$skuCode]['ensogo_cat'] = "";//*ensogo分类
                        $ensogoListings[$skuCode]['ensogo_store'] = $ensogoAccount;// *发布店铺
//     					$ensogoListings[$skuCode]['parent_sku'] = $aliListListing['productid']."-".$prodSkuIndex;//*父SKU. 由于只有一个产品，所以用product id即可
                        $ensogoListings[$skuCode]['parent_sku'] = $parentSku;
                        $ensogoListings[$skuCode]['sku'] = $prodSku['skuCode'];//*子SKU
                        $ensogoListings[$skuCode]['subject'] = $aliListListing['subject'];//*产品标题

                        $otherConfig = array();
                        foreach ($prodSku as $key => $skuProperty) {
                            // config 属性加入到subject 里面，要留意会不会超长。
                            if (!in_array($key, array("skuCode", "skuPrice", "currencyCode", "ipmSkuStock", "skuImage"))) {
                                $otherConfig[$key] = $skuProperty;
//     							$ensogoListings[$skuCode]['subject'] .= " (".$skuProperty.")";
                            }
                        }

                        $ensogoListings[$skuCode]['color'] = "";//颜色
                        $ensogoListings[$skuCode]['color'] = array_shift($otherConfig);

                        $ensogoListings[$skuCode]['size'] = "";//尺寸。由于这个size 不是config 属性，所有sku的size 一样，所以这里设置不是config设置。
                        $ensogoListings[$skuCode]['size'] = implode(',', $otherConfig);
                        if (isset($prodAttrs['Size'])) {
                            if (!empty($ensogoListings[$skuCode]['size'])) {
                                $ensogoListings[$skuCode]['size'] = $ensogoListings[$skuCode]['size'] . ',' . $prodAttrs['Size'];
                            } else {
                                $ensogoListings[$skuCode]['size'] = $prodAttrs['Size'];
                            }
                        }

//     					$tags = explode(" ", $aliListListing['subject']);
//     					Helper_Array::removeEmpty($tags);

                        // 去掉纯数字的标签，去掉只有1个或者2个字符的标签，去掉For，To（忽略大小写）标签，取前10个标签，Rear-end这种单词合并为一个标签
                        $tempTagStr = $aliListListing['subject'];
//     					$tempTagStr = str_ireplace('rear end','Rear-end',$tempTagStr);// 替换词语
                        $tempTags = explode(" ", $tempTagStr);
                        $tempTags = str_ireplace(array('for', 'to'), '', $tempTags);// 去掉for to
                        $tags = array();
                        foreach ($tempTags as $tag) {
                            if (empty($tag) || strlen($tag) <= 2 || is_numeric($tag))
                                continue;

                            if (count($tags) >= 10)
                                break;

                            $tags[] = $tag;
                        }
                        $ensogoListings[$skuCode]['tag'] = implode(",", $tags);//*产品标签(用英文逗号[,]隔开)

                        $ensogoListings[$skuCode]['description'] = "";// *产品描述
                        if (isset($aliListListingDetail['detail'])) {
                            // dzt20160321 detail字段在拉取时候 json_encode了，但没留意到把它转为array再encode，这样json_decode这个字符串会报错，所以这里做些bug fix
                            // 后面detail拉取已经改了就可以不用这部分逻辑
                            $tempDetail = @json_decode($aliListListingDetail['detail'], true);
                            if (empty($tempDetail) && !is_array($tempDetail)) {
                                $aliListListingDetail['detail'] = '{"detail":' . $aliListListingDetail['detail'] . "}";
                                $tempDetail = @json_decode($aliListListingDetail['detail'], true);
                                if (!empty($tempDetail)) {
                                    $aliListListingDetail['detail'] = $tempDetail['detail'];
                                } else {
                                    $aliListListingDetail['detail'] = "";
                                }
                            } elseif (!empty($tempDetail['detail'])) {
                                $aliListListingDetail['detail'] = $tempDetail['detail'];
                            } elseif (is_string($tempDetail)) {
                                $aliListListingDetail['detail'] = $tempDetail;
                            }

//     						$aliListListingDetail['detail'] = stripslashes($aliListListingDetail['detail']);
                            // 替换速卖通aeProduct.getSubject() 为产品subject
                            $aliListListingDetail['detail'] = str_ireplace("aeProduct.getSubject()", $ensogoListings[$skuCode]['subject'], $aliListListingDetail['detail']);
                            $ensogoListings[$skuCode]['description'] = $moreDetail . $aliListListingDetail['detail'];
                        } else {
                            $ensogoListings[$skuCode]['description'] = $moreDetail;
                        }

                        $ensogoListings[$skuCode]['general_price'] = "";// 市场价($)

                        $ensogoListings[$skuCode]['price'] = "";// *售价($)
                        if (isset($prodSku['skuPrice'])) {
                            $ensogoListings[$skuCode]['price'] = Helper_Currency::convert($prodSku['skuPrice'], "USD", $prodSku['currencyCode']);
                        } else if (isset($aliListListingDetail['product_price'])) {
                            $ensogoListings[$skuCode]['price'] = Helper_Currency::convert($aliListListingDetail['skuPrice'], "USD", $aliListListingDetail['currencyCode']);
                        }

                        $ensogoListings[$skuCode]['stock'] = "";
                        if (isset($prodSku['ipmSkuStock'])) {
                            $ensogoListings[$skuCode]['stock'] = $prodSku['ipmSkuStock'];// *库存
                        }

                        $ensogoListings[$skuCode]['delivery_fee'] = "";//*运费($)
                        $ensogoListings[$skuCode]['delivery_duration'] = "15-30";//*运输时间(天) hardcode 15-30天

                        $ensogoListings[$skuCode]['brand'] = "Other";//品牌
                        if (isset($prodAttrs['Brand Name'])) {
                            $ensogoListings[$skuCode]['brand'] = $prodAttrs['Brand Name'];
                        } else if (isset($prodAttrs['Model Number'])) {
                            $ensogoListings[$skuCode]['brand'] = $prodAttrs['Model Number'];
                        }

                        $ensogoListings[$skuCode]['upc'] = "";//UPC（通用产品代码）
                        $ensogoListings[$skuCode]['product_url'] = "";//Landing Page URL
                        $ensogoListings[$skuCode]['main_image'] = $aliListListing["photo_primary"];//*产品主图链接
                        if (!empty($aliListListing["imageurls"])) {
                            $otherImages = explode(";", $aliListListing["imageurls"]);
                            $index = 1;
                            foreach ($otherImages as $image) {
                                $ensogoListings[$skuCode]['images' . $index++] = $image; // 附图链接
                                if ($index > 10) break;
                            }
                        }

                        $prodSkuIndex++;
                    }
                } else {// 2.3 如果符合ensogo config 产品 即先生成一个伪造的父产品，然后再保存 其他config 产品
                    $prodSkuIndex = 1;
                    $parentSku = "";
                    foreach ($prodSkus as $skuCode => $prodSku) {
                        if (empty($parentSku)) // dzt20160218 舍弃父类SKU 用第一个变种的SKU替代
                            $parentSku = $prodSku['skuCode'];

                        $ensogoListings[$skuCode] = array();
                        $ensogoListings[$skuCode]['ali_cat'] = $aliCatWithParents;//速卖通分类
                        $ensogoListings[$skuCode]['ensogo_cat'] = "";//*ensogo分类
                        $ensogoListings[$skuCode]['ensogo_store'] = $ensogoAccount;// *发布店铺
//     					$ensogoListings[$skuCode]['parent_sku'] = $aliListListing['productid'];//*父SKU. 由于只有一个产品，所以用product id即可
                        $ensogoListings[$skuCode]['parent_sku'] = $parentSku;
                        $ensogoListings[$skuCode]['sku'] = $prodSku['skuCode'];//*子SKU
                        $ensogoListings[$skuCode]['subject'] = $aliListListing['subject'];//*产品标题
                        $otherConfig = array();
                        foreach ($prodSku as $key => $skuProperty) {
                            // config 属性加入到subject 里面，要留意会不会超长。
                            if (!in_array($key, array("skuCode", "skuPrice", "currencyCode", "ipmSkuStock", "skuImage", "Color", "Size"))) {
                                $otherConfig[$key] = $skuProperty;
//     							$ensogoListings[$skuCode]['subject'] .= " (".$skuProperty.")";
                            }
                        }

                        $ensogoListings[$skuCode]['color'] = "";//颜色
                        if (isset($prodSku['Color'])) {
                            $ensogoListings[$skuCode]['color'] = $prodSku['Color'];
                        }

                        $ensogoListings[$skuCode]['size'] = "";//尺寸
                        if (isset($prodSku['Size'])) {
                            $ensogoListings[$skuCode]['size'] = $prodSku['Size'];
                        } else if (isset($prodAttrs['Size'])) {
                            $ensogoListings[$skuCode]['size'] = $prodAttrs['Size'];
                        }

                        if (empty($ensogoListings[$skuCode]['color'])) {
                            $ensogoListings[$skuCode]['color'] = array_shift($otherConfig);
                        }

                        if (!empty($otherConfig)) {
                            if (!empty($ensogoListings[$skuCode]['size'])) {
                                $ensogoListings[$skuCode]['size'] = $ensogoListings[$skuCode]['size'] . ',' . implode(',', $otherConfig);
                            } else {
                                $ensogoListings[$skuCode]['size'] = implode(',', $otherConfig);
                            }
                        }

//     					$tags = explode(" ", $aliListListing['subject']);
//     					Helper_Array::removeEmpty($tags);
                        // 去掉纯数字的标签，去掉只有1个或者2个字符的标签，去掉For，To（忽略大小写）标签，取前10个标签，Rear-end这种单词合并为一个标签
                        $tempTagStr = $aliListListing['subject'];
//     					$tempTagStr = str_ireplace('rear end','Rear-end',$tempTagStr);// 替换词语
                        $tempTags = explode(" ", $tempTagStr);
                        $tempTags = str_ireplace(array('for', 'to'), '', $tempTags);// 去掉for to
                        $tags = array();
                        foreach ($tempTags as $tag) {
                            if (empty($tag) || strlen($tag) <= 2 || is_numeric($tag))
                                continue;

                            if (count($tags) >= 10)
                                break;

                            $tags[] = $tag;
                        }
                        $ensogoListings[$skuCode]['tag'] = implode(",", $tags);//*产品标签(用英文逗号[,]隔开)

                        $ensogoListings[$skuCode]['description'] = "";// *产品描述
                        if (isset($aliListListingDetail['detail'])) {
                            // dzt20160321 detail字段在拉取时候 json_encode了，但没留意到把它转为array再encode，这样json_decode这个字符串会报错，所以这里做些bug fix
                            // 后面detail拉取已经改了就可以不用这部分逻辑
                            $tempDetail = @json_decode($aliListListingDetail['detail'], true);
                            if (empty($tempDetail) && !is_array($tempDetail)) {
                                $aliListListingDetail['detail'] = '{"detail":' . $aliListListingDetail['detail'] . "}";
                                $tempDetail = @json_decode($aliListListingDetail['detail'], true);
                                if (!empty($tempDetail)) {
                                    $aliListListingDetail['detail'] = $tempDetail['detail'];
                                } else {
                                    $aliListListingDetail['detail'] = "";
                                }
                            } elseif (!empty($tempDetail['detail'])) {
                                $aliListListingDetail['detail'] = $tempDetail['detail'];
                            } elseif (is_string($tempDetail)) {
                                $aliListListingDetail['detail'] = $tempDetail;
                            }

//     						$aliListListingDetail['detail'] = stripslashes($aliListListingDetail['detail']);
                            // 替换速卖通aeProduct.getSubject() 为产品subject
                            $aliListListingDetail['detail'] = str_ireplace("aeProduct.getSubject()", $ensogoListings[$skuCode]['subject'], $aliListListingDetail['detail']);
                            $ensogoListings[$skuCode]['description'] = $moreDetail . $aliListListingDetail['detail'];
                        } else {
                            $ensogoListings[$skuCode]['description'] = $moreDetail;
                        }

                        $ensogoListings[$skuCode]['general_price'] = "";// 市场价($)

                        $ensogoListings[$skuCode]['price'] = "";// *售价($)
                        if (isset($prodSku['skuPrice'])) {
                            $ensogoListings[$skuCode]['price'] = Helper_Currency::convert($prodSku['skuPrice'], "USD", $prodSku['currencyCode']);
                        } else if (isset($aliListListingDetail['product_price'])) {
                            $ensogoListings[$skuCode]['price'] = Helper_Currency::convert($aliListListingDetail['skuPrice'], "USD", $aliListListingDetail['currencyCode']);
                        }

                        $ensogoListings[$skuCode]['stock'] = "";
                        if (isset($prodSku['ipmSkuStock'])) {
                            $ensogoListings[$skuCode]['stock'] = $prodSku['ipmSkuStock'];// *库存
                        }

                        $ensogoListings[$skuCode]['delivery_fee'] = "";//*运费($)
                        $ensogoListings[$skuCode]['delivery_duration'] = "15-30";//*运输时间(天)

                        $ensogoListings[$skuCode]['brand'] = "Other";//品牌
                        if (isset($prodAttrs['Brand Name'])) {
                            $ensogoListings[$skuCode]['brand'] = $prodAttrs['Brand Name'];
                        } else if (isset($prodAttrs['Model Number'])) {
                            $ensogoListings[$skuCode]['brand'] = $prodAttrs['Model Number'];
                        }

                        $ensogoListings[$skuCode]['upc'] = "";//UPC（通用产品代码）
                        $ensogoListings[$skuCode]['product_url'] = "";//Landing Page URL

                        $ensogoListings[$skuCode]['main_image'] = "";//*产品主图链接
                        if (isset($prodSku['skuImage'])) {
                            $ensogoListings[$skuCode]['main_image'] = $prodSku['skuImage'];
                        } else {
                            $ensogoListings[$skuCode]['main_image'] = $aliListListing["photo_primary"];
                        }

                        if ($prodSkuIndex == 1 && !empty($aliListListing["imageurls"])) {// 其他图片只在第一个产品show出来
                            $otherImages = explode(";", $aliListListing["imageurls"]);
                            $index = 1;
                            foreach ($otherImages as $image) {
                                $ensogoListings[$skuCode]['images' . $index++] = $image; // 附图链接
                                if ($index > 10) break;
                            }
                        }

                        $prodSkuIndex++;
                    }
                }
            }
        }
//     	echo "exportListing count export:".count($ensogoListings),PHP_EOL;
        yii::info("exportListing count export:" . count($ensogoListings), "file");

//     	print_r($ensogoListings);
//     	yii::info(json_encode($ensogoListings),"file");// 查看为什么中间有空行

        $filed_array = array(
            "速卖通分类",
            "*ensogo分类",
            "*发布店铺",
            "*父SKU",
            "*子SKU",
            "*产品标题",
            "颜色",
            "尺寸",
            "*产品标签(用英文逗号[,]隔开)",
            "*产品描述",
            "市场价($)",
            "*售价($)",
            "*库存",
            "*运费($)",
            "*运输时间(天)",
            "品牌",
            "UPC（通用产品代码）",
            "Landing Page URL",
            "*产品主图链接",
            "附图链接1",
            "附图链接2",
            "附图链接3",
            "附图链接4",
            "附图链接5",
            "附图链接6",
            "附图链接7",
            "附图链接8",
            "附图链接9",
            "附图链接10",
        );

        // 要导出两个sheet ，ensogo 分类在第一个，数据在第二个
        $sheetInfo = array();

        $dataSheetIndex = 0;
        $catSheetIndex = 1;

        $sheetInfo[$dataSheetIndex]['title'] = "搬家数据";
        $sheetInfo[$dataSheetIndex]['filed_array'] = $filed_array;
        $sheetInfo[$dataSheetIndex]['data_array'] = $ensogoListings;

        // 获取所有ensogo 分类
//     	$sheetInfo[$catSheetIndex]['title'] = "分类列表";
//     	$sheetInfo[$catSheetIndex]['filed_array'] = array("分类","Categories","分类代码","所属分类");

//     	$categoriesJsonStrObj = EnsogoCategories::findOne(['site'=>"all"]);
//     	$categories = json_decode($categoriesJsonStrObj->categories , true);
//     	$exportCatInfo = array();
//     	foreach ($categories as $category){
//     		$exportCatInfo[] = array($category['name_zh_tw'],$category['name'],$category['id'],$category['parent_id']);
//     	}
//     	$sheetInfo[$catSheetIndex]['data_array'] = $exportCatInfo;

        $categories = EnsogoHelper::_getEnsogoCategory('', $puid);

        $sheetInfo[$catSheetIndex]['title'] = "分类列表";
//     	$sheetInfo[$catSheetIndex]['filed_array'] = $categories['filed_array'];
//     	$sheetInfo[$catSheetIndex]['data_array'] = $categories['data_array'];

        array_unshift($categories['data_array'], $categories['filed_array']);
        array_unshift($categories['data_array'], array("“*ensogo分类”属性填写分类代码"), array("“*ensogo分类”和“*运费($)”为必须修改的两个属性，其他属性可根据情况修改"), array());
        $sheetInfo[$catSheetIndex]['data_array'] = $categories['data_array'];

        \Yii::info("exportListing memory usage:" . round(memory_get_usage() / 1024 / 1024, 2) . " M", "file");
//     	echo "start to export to file\n";
        \Yii::info("exportListing start to export to file", "file");

        //3. export to 文件
//     	echo "memory usage:".round(memory_get_usage()/1024/1024 , 2)."M.".PHP_EOL;
        if ("csv" == $type) {
            ExcelHelper::justExportToExcel($sheetInfo, "export_ali_listing_{$sellerloginid}.csv", $isDownload);
        } else if ("xlsx" == $type) {
            ExcelHelper::justExportToExcel($sheetInfo, "export_ali_listing_{$sellerloginid}.xlsx", $isDownload);
        } else {
            ExcelHelper::justExportToExcel($sheetInfo, "export_ali_listing_{$sellerloginid}.xls", $isDownload);
        }
    }

    private static function handleBgJobError($recommendJobObj, $errorMessage)
    {
        $nowTime = time();
        $recommendJobObj->status = 3;
        $recommendJobObj->error_count = $recommendJobObj->error_count + 1;
        $recommendJobObj->error_message = $errorMessage;
        $recommendJobObj->last_finish_time = $nowTime;
        $recommendJobObj->update_time = $nowTime;
        $recommendJobObj->next_execution_time = $nowTime + 5 * 60;//5分钟后重试
        $recommendJobObj->save(false);
        return true;
    }

    private static function generateSku($length = 8)
    {
        // 密码字符集，可任意添加你需要的字符
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';

        $password = '';
        for ($i = 0; $i < $length; $i++) {
            // 这里提供两种字符获取方式
            // 第一种是使用 substr 截取$chars中的任意一位字符；
            // 第二种是取字符数组 $chars 的任意元素
            // $password .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
            $password .= $chars[mt_rand(0, strlen($chars) - 1)];
        }
        return $password;
    }

    // 将客户修改好的excel 产品内容 导入到数据库 等待 ensogo 刊登处理
    // dzt 2016-02-23
    public static function cronGetEnsogoListingFromExcel()
    {
        $job_name = "collect_ensogo_listing_from_excel";
        $nowTime = time();

        //status： 0 未处理 ，1处理中 ，2完成 ，3失败
        $recommendJobObjs = UserBackgroundJobControll::find()
            ->where('is_active="Y" AND (status=0 or status=3) AND job_name="' . $job_name . '" AND error_count<5')//AND (status=0 or status=3)
            ->orderBy('next_execution_time')->limit(5)->all(); // 多到一定程度的时候，就需要使用多进程的方式来并发拉取。
        if (!empty($recommendJobObjs)) {
            try {
                foreach ($recommendJobObjs as $recommendJobObj) {
                    //1. 先判断是否可以正常抢到该记录
                    $recommendJobAttrs = array();
                    $recommendJobAttrs['status'] = 1;
                    $recommendJobAttrs['last_begin_run_time'] = $nowTime;
                    $affectRows = UserBackgroundJobControll::updateAll($recommendJobAttrs, "id=" . $recommendJobObj->id . " and status <> 1");
                    if ($affectRows <= 0) continue; //抢不到

                    $recommendJobObj = UserBackgroundJobControll::findOne($recommendJobObj->id);
                    $puid = $recommendJobObj->puid;

                    $EXCEL_PRODUCT_COLUMN_MAPPING = array(
                        "A" => "aliexpress_category", //速卖通分类
                        "B" => "ensogo_category", // ensogo分类
                        "C" => "ensogo_store", // 发布店铺
                        "D" => "parent_sku", // 父SKU
                        "E" => "sku", // 子SKU
                        "F" => "subject", // 产品标题
                        "G" => "color", // 颜色
                        "H" => "size", // 尺寸
                        "I" => "tag",// 产品标签(用英文逗号[,]隔开)
                        "J" => "description",// 产品描述
                        "K" => "market_price", // 市场价($)
                        "L" => "price", // 售价($)
                        "M" => "stock", // 库存
                        "N" => "delivery_fee", // 运费($)
                        "O" => "delivery_duration", // 运输时间(天)
                        "P" => "brand", // 品牌
                        "Q" => "upc", // UPC（通用产品代码）
                        "R" => "url", // Landing Page URL
                        "S" => "main_image", // 产品主图链接
                        "T" => "imageurl_1", // 附图链接1
                        "U" => "imageurl_2", // 附图链接2
                        "V" => "imageurl_3", // 附图链接3
                        "W" => "imageurl_4", // 附图链接4
                        "X" => "imageurl_5", // 附图链接5
                        "Y" => "imageurl_6", // 附图链接6
                        "Z" => "imageurl_7", // 附图链接7
                        "AA" => "imageurl_8", // 附图链接8
                        "AB" => "imageurl_9", // 附图链接9
                        "AC" => "imageurl_10", // 附图链接10
                    );

                    $additionalInfo = json_decode($recommendJobObj->additional_info, true);
                    $file = array();
                    $file['name'] = $additionalInfo['fileName'];
                    $file['tmp_name'] = $additionalInfo['filePath'];
//     				echo "memory usage1:".(memory_get_usage()/1024/1024)."M.".PHP_EOL;

                    // 1. 从excel读出数据
                    $productsData = \eagle\modules\util\helpers\ExcelHelper::excelToArray($file, $EXCEL_PRODUCT_COLUMN_MAPPING);

                    if (is_string($productsData)) {
                        self::handleBgJobError($recommendJobObj, $productsData);
                        continue;
                    }

                    // 删除相同店铺已存在的sku 导入记录
                    $deleteProd = array();
                    foreach ($productsData as &$prod) {
                        if (empty($deleteProd[$prod['ensogo_store']]))
                            $deleteProd[$prod['ensogo_store']] = array();
                        $deleteProd[$prod['ensogo_store']][] = $prod['sku'];
                    }

                    foreach ($deleteProd as $ensogoStore => $skus) {
                        $num = ImportEnsogoListing::deleteAll(['ensogo_store' => $ensogoStore, 'sku' => $skus]);
                        \Yii::info("cronGetEnsogoListingFromExcel ensogo store:$ensogoStore, deleted:$num ", "file");
                    }

                    $batchNum = $nowTime;
                    foreach ($productsData as &$prod) {
                        $prod['status'] = 0;
                        $prod['batch_num'] = $batchNum;
                        $prod['puid'] = $puid;
                    }

                    // 2. 导入数据到数据库
                    $totalNum = count($productsData);
                    // @todo 要测试 这里批量导入如果一条sql 大于3000 就变相是一个个导入了。
                    $insertNum = SQLHelper::groupInsertToDb(ImportEnsogoListing::tableName(), $productsData, "db_queue");
                    if ($totalNum != $insertNum) {
                        \Yii::error("cronGetEnsogoListingFromExcel groupInsertToDb num:$insertNum , but there are total $totalNum to be insert.", "file");
                    } else {
                        // 全部导入完成，设置成可以import 到ensogo 状态
                        ImportEnsogoListing::updateAll(["status" => 1], ["puid" => $puid, "batch_num" => $batchNum]);
                    }

//     				echo "insertNum:$insertNum,totalNum:$totalNum".PHP_EOL;
//     				echo "memory usage8:".(memory_get_usage()/1024/1024)."M.".PHP_EOL;
//     				echo count($productsData).PHP_EOL;

// 		     		unset($productsData);// 5W 记录 800M
// 					echo "memory usage9:".(memory_get_usage()/1024/1024)."M.".PHP_EOL;

                    $recommendJobObj->error_count = 0;
                    $recommendJobObj->error_message = "";
                    $recommendJobObj->status = 2;
                    $recommendJobObj->is_active = "N"; // 导出完关闭job
                    $recommendJobObj->last_finish_time = time();
                    if (!$recommendJobObj->save(false)) {
                        \Yii::error("cronGetEnsogoListingFromExcel " . json_encode($recommendJobObj), "file");
                    }

                    // 3. 将导入ensogo listing任务 写入后台任务
                    $importEnsogoListingJobName = "import_listing_to_ensogo";
                    $userBgControl = UserBackgroundJobControll::find()->where(["puid" => $puid, "job_name" => $importEnsogoListingJobName])->one();
                    if ($userBgControl === null) {
                        $userBgControl = new UserBackgroundJobControll;
                        $userBgControl->puid = $puid;
                        $userBgControl->job_name = $importEnsogoListingJobName;
                        $userBgControl->create_time = $nowTime;
                        $userBgControl->status = 0;// 0 未处理 ，1处理中 ，2完成 ，3失败
                    } else {
                        if (1 != $userBgControl->status) {
                            $userBgControl->status = 0;
                        }
                    }

                    $userBgControl->error_count = 0;
                    $userBgControl->is_active = "Y";// 运行完之后，关闭
                    $userBgControl->next_execution_time = $nowTime;
                    $userBgControl->update_time = $nowTime;
                    $userBgControl->save(false);

                    // 删除导入的excel文件
// 					unlink($file['tmp_name']);
                }
            } catch (\Exception $e) {
                print_r($e);
                $errorMessage = "file:" . $e->getFile() . " line:" . $e->getLine() . " message:" . $e->getMessage();
                \Yii::error("cronGetEnsogoListingFromExcel " . $errorMessage, "file");
                self::handleBgJobError($recommendJobObj, $errorMessage);
            }
        }
    }

    // cron job import_ensogo_listing 表拉取可以导入的Listing 组织数据并触发 ensogo 接口进行刊登
    public static function importListingToEnsogo()
    {

        $job_name = "import_listing_to_ensogo";
        $nowTime = time();

//     	$puid = 297;
//     	$userBgControl = UserBackgroundJobControll::find()->where(["puid"=>$puid,"job_name"=>$job_name])->one();
//     	if ($userBgControl === null){
//     		$userBgControl = new UserBackgroundJobControll;
//     		$userBgControl->puid = $puid;
//     		$userBgControl->job_name = $job_name;
//     		$userBgControl->create_time = $nowTime;
//     	}else{
//     		$userBgControl->status = 0;
//     		$userBgControl->error_count = 0;
//     		$userBgControl->is_active = "Y";// 运行完之后，关闭
//     	}
//     	$userBgControl->next_execution_time = $nowTime;
//     	$userBgControl->update_time = $nowTime;
//     	$userBgControl->save(false);

        $recommendJobObjs = UserBackgroundJobControll::find()
            ->where('is_active="Y" AND (status=0 or status=3) AND job_name="' . $job_name . '" AND error_count<5')
            ->orderBy('next_execution_time')->limit(5)->all(); // 多到一定程度的时候，就需要使用多进程的方式来并发拉取。

        if (!empty($recommendJobObjs)) {
            try {
                foreach ($recommendJobObjs as $recommendJobObj) {
                    $puid = $recommendJobObj->puid;
 
                    echo "puid:$puid" . PHP_EOL;

                    // 删除非最近一批 3天前导入的失刊登败记录 @TODO 后面稳定之后我觉得可以全部都删除
                    $recentBatchNum = ImportEnsogoListing::find()->where(['puid' => $puid])->max("batch_num");
                    $deadline = time() - 3 * 86400;
                    $delCount = ImportEnsogoListing::deleteAll("puid=$puid and status=4 and batch_num <>'$recentBatchNum' and batch_num<$deadline ");
                    if (!empty($delCount))
                        echo "======= importListingToEnsogo deleted: $delCount ,puid:$puid";

                    // listing 状态过滤 status： 0 未处理 ，1处理中 ，2完成 ，3失败
                    $batchListings = ImportEnsogoListing::find()->where(['status' => 1, 'puid' => $puid])->groupBy('ensogo_store,ensogo_category,delivery_duration,batch_num')->orderBy('batch_num')->limit(20)->asArray()->all();
                    echo "batchListings:" . count($batchListings) . PHP_EOL;
                    if (!empty($batchListings)) {
                        foreach ($batchListings as $batchListing) {
                            $ensogoStore = $batchListing['ensogo_store'];
                            $SEU = SaasEnsogoUser::findOne(['store_name' => $ensogoStore, 'uid' => $puid]);
                            $ensogoSiteId = -1;
                            if (!empty($SEU)) {
                                $ensogoSiteId = $SEU->site_id;
                            } else {
                                $errorMessage = "Store name:$ensogoStore not exist";
                                ImportEnsogoListing::updateAll(['status' => 4, 'error_message' => $errorMessage], ['status' => 1, 'puid' => $puid, 'ensogo_store' => $ensogoStore]);
                                echo "importListingToEnsogo store_name:$ensogoStore not exist" . PHP_EOL;
                                continue;
                            }

                            $ensogoCategory = $batchListing['ensogo_category'];
                            $deliveryDuration = $batchListing['delivery_duration'];
                            $batchNum = $batchListing['batch_num'];

                            // 确保$where条件下的parent_sku只有一个，加上批次过滤 @todo 同一批产品出现相同sku 怎么办
                            $where = ['puid' => $puid, 'ensogo_store' => $ensogoStore, 'ensogo_category' => $ensogoCategory, 'delivery_duration' => $deliveryDuration, 'batch_num' => $batchNum];

                            // 控制20个 parent sku 一次，不然这里不加限制的话一次跑太久。
                            $parentListings = ImportEnsogoListing::find()->where($where)->andWhere(['status' => 1])->groupBy('parent_sku')->limit(20)->all();

                            // ensogo 根据这个 log 同步一批 在线产品
                            // 但是参数规定了调用这个接口后 上传的产品必须 目录 和运输时间是一样的。所以上面的groupBy 要ensogo_store,ensogo_category,delivery_duration
                            $logResult = EnsogoStoreMoveHelper::saveEnsogoStoreMoveLog($puid, $ensogoSiteId, count($parentListings), $deliveryDuration, $ensogoCategory, "aliexpress");
                            if ($logResult["success"] == false) {
                                \Yii::error("importListingToEnsogo saveEnsogoStoreMoveLog message:" . $logResult["message"], "file");
                                continue;
                            }

                            $logId = $logResult['id'];// 日志ID
                            echo count($parentListings) . " parents" . PHP_EOL;

                            echo "ready to fommat product info.where:" . json_encode($where) . PHP_EOL;

                            foreach ($parentListings as $parentListing) {
                                $aliProductInfo = array();
                                $aliVarianceInfo = array();
                                $where['parent_sku'] = $parentListing->parent_sku;
                                // @todo 简单做一些合法性判断，过滤客户不正常输入。确保下面的结果不为空
                                $parentListingObj = ImportEnsogoListing::find()->where($where)->andWhere(['status' => 1, 'sku' => $parentListing->parent_sku])->one();

                                $parentSkuWithoutVariant = false;
                                if (empty($parentListingObj)) {// dzt20160308 如果父sku不存在变体，把第一个变体的SKU改成商品的SKU
// 			    					echo "导入信息有误：子sku 产品信息中不存在 父sku的产品信息。where:".json_encode($where);
// 			    					$parentListing->status = 4;
// 			    					$parentListing->error_message = "导入信息有误：子sku 产品信息中不存在 父sku的产品信息";
// 			    					$parentListing->save();
// 			    					continue;
                                    $parentSkuWithoutVariant = true;

                                    $todoVariantNum = ImportEnsogoListing::updateAll(['status' => 2], $where);
                                    if (empty($todoVariantNum)) continue;// 处理中
                                } else {
                                    $parentListingObj->status = 2;// 这里主要是以parent sku 的变参来控制改组的 sku是否已经在发布中。
                                    if ($parentListingObj->update() == false) {// 处理中
                                        continue;
                                    }

                                    ImportEnsogoListing::updateAll(['status' => 2], $where);
                                }

                                $listings = ImportEnsogoListing::find()->where($where)->asArray()->all();
                                echo count($listings) . " variants" . PHP_EOL;

                                echo "ready to fommat one product info.where:" . json_encode($where) . PHP_EOL;

                                foreach ($listings as $index => $listing) {
                                    // 商品info，获取parent_sku 对应sku 的商品信息。
                                    // 请留意速卖通产品的 非主图片（即image_1..._10）都记在parent_sku 对应的sku上面，变参的图片只记录变参的主图。
                                    if ($listing['sku'] == $listing['parent_sku'] || (empty($aliProductInfo) && $parentSkuWithoutVariant)) {
                                        $aliProductInfo = [
                                            'sku' => $listing['sku'],
                                            'name' => $listing['subject'],
                                            'description' => $listing['description'],
                                            'tags' => $listing['tag'],
                                            'main_image' => $listing['main_image']
                                        ];

                                        if ($parentSkuWithoutVariant)
                                            $aliProductInfo['parent_sku'] = $listing['sku'];
                                        else
                                            $aliProductInfo['parent_sku'] = $listing['parent_sku'];

                                        $aliProductInfo['brand'] = $listing['brand'];
                                        $aliProductInfo['url'] = $listing['url'];
                                        $aliProductInfo['upc'] = $listing['upc'];
                                        $aliProductInfo['msrps'] = $listing['market_price'];

                                        for ($i = 1; $i <= 10; $i++) {
                                            $aliProductInfo["extra_image_{$i}"] = $listing["imageurl_{$i}"];
                                        }
                                    }

                                    // 变参info
                                    $aliVarianceInfo[$index] = [
                                        'sku' => $listing['sku'],
                                        'inventory' => $listing['stock'],
                                        'price' => $listing['price'],
                                        'shipping' => $listing['delivery_fee']
                                    ];

                                    $aliVarianceInfo[$index]['color'] = $listing['color'];
                                    $aliVarianceInfo[$index]['size'] = $listing['size'];
                                }

                                echo "ready to EnsogoStoreMoveHelper::smtStoreMove" . PHP_EOL;

// 			    				echo json_encode($aliProductInfo);
// 			    				echo PHP_EOL.PHP_EOL;
// 			    				echo json_encode($aliVarianceInfo);
// 			    				exit();

                                // 商品，变参数据组织完成   调用ensogo 接口
                                $publishResult = EnsogoStoreMoveHelper::smtStoreMove($aliProductInfo, $aliVarianceInfo, $deliveryDuration, $ensogoCategory, $logId, $ensogoSiteId, $puid);
                                if ($publishResult['success']) {
                                    echo "publish success.where:" . json_encode($where) . ", publish resule:" . json_encode($publishResult) . PHP_EOL;
                                    ImportEnsogoListing::updateAll(['status' => 3], $where);
                                } else {
                                    echo "publish fail.where:" . json_encode($where) . ", publish resule:" . json_encode($publishResult) . PHP_EOL;
                                    if (empty($publishResult['error_message'])) {
                                        ImportEnsogoListing::updateAll(['status' => 4, 'error_message' => $publishResult['message']], $where);
                                    } else {
                                        $errorMessage = is_array($publishResult['error_message']) ? implode('<br>', $publishResult['error_message']) : $publishResult['error_message'];
                                        ImportEnsogoListing::updateAll(['status' => 4, 'error_message' => $errorMessage], $where);
                                    }

                                }
                            }
                        }

                        echo "all product run finish." . PHP_EOL;
                    }


                    // 检查改puid下 Listing是否已处理完，修改状态
                    $lastToImportNum = ImportEnsogoListing::find()->where(['puid' => $puid, 'status' => 1])->count();
                    if (empty($lastToImportNum)) {
                        $recommendJobObj->status = 2;
                        $recommendJobObj->is_active = "N"; // 导出完关闭job
                        $recommendJobObj->error_message = "";
                    } else {
                        $recommendJobObj->status = 0;// @TODO UserBackgroundJobControll 没有一个未完成 状态，这里只能改为0
                    }
                    $recommendJobObj->last_finish_time = time();
                    if (!$recommendJobObj->save(false)) {
                        \Yii::error("importListingToEnsogo " . json_encode($recommendJobObj), "file");
                    }
                }
            } catch (\Exception $e) {
                print_r($e);
                $errorMessage = "file:" . $e->getFile() . " line:" . $e->getLine() . " message:" . $e->getMessage();
                \Yii::error("importListingToEnsogo " . $errorMessage, "file");

                if (!empty($where['parent_sku'])) {// 其他excetion,ImportEnsogoListing 改会待执行状态
                    ImportEnsogoListing::updateAll(['status' => 4, 'error_message' => $errorMessage], $where);
                }
                self::handleBgJobError($recommendJobObj, $errorMessage);
            }
        }
    }


    private static function _setOrderDay($orders, $one)
    {
        if (isset($orders[$one['orderId']])) {
            $one['day'] = $orders[$one['orderId']]['day'];
            $one['hour'] = $orders[$one['orderId']]['hour'];
            $one['min'] = $orders[$one['orderId']]['min'];
        } else {
            $one['day'] = 0;
            $one['hour'] = 0;
            $one['min'] = 0;
        }
        return $one;
    }

    /**
     * 获取saas_aliexpress_autosync中的错误数据
     *
     * @author akirametero
     */
    public static function getAliexpresssysErrorList($status, $type, $last_time)
    {
        $connection = Yii::$app->db;
        $type_arr = ['day120', 'finish', 'finish30', 'time', 'onSelling'];
        if (in_array($type, $type_arr) === false) {
            echo "type参数错误";
            yii::info("type参数错误", "file");
            return false;
        }
        $res = $connection->createCommand("SELECT id,uid,sellerloginid,FROM_UNIXTIME(last_time) as lt,FROM_UNIXTIME(next_time) as nt FROM saas_aliexpress_autosync WHERE `status`='{$status}' AND `type`='{$type}' AND last_time <'{$last_time}' AND is_active=1 ORDER BY last_time DESC  ")->query();
        $result = $res->readAll();
        $msg = "";
        if (!empty($result)) {
            foreach ($result as $vs) {
                //检查是否活跃用户,在邮件主体中标记出来吧
                $mt = self::isActiveUser($vs['uid']) === false ? '非活跃用户' : '活跃用户';
                //如果是活跃用户,就先把status,改成0
                if ($mt == '活跃用户') {
                    $id = $vs['id'];
                    $update = $connection->createCommand("UPDATE saas_aliexpress_autosync SET `status`=0 WHERE id='{$id}'")->execute();
                }

                $msg .= $mt . '--PUID:' . $vs['uid'] . ',最后更新时间--' . $vs['lt'] . ',速卖通登录账户--' . $vs['sellerloginid'] . PHP_EOL;
            }

            $mail = Yii::$app->mailer->compose();
            $mail->setTo("akirametero@vip.qq.com");
            $mail->setSubject('速卖通数据拉去错误:type-' . $type);
            $mail->setTextBody($msg);
            $result = $mail->send();
            if ($result === false) {
                echo "发送邮件失败";
                yii::info("发送邮件失败", "file");
            } else {
                yii::info("发送邮件成功", "file");
                echo "发送邮件成功";
            }
            return true;
        } else {
            echo "{$status}没有type={$type}的异常数据";
            yii::info("{$status}没有type={$type}的异常数据", "file");
            return false;
        }

    }
    //end function


}
