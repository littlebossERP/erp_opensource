<?php
namespace eagle\modules\listing\helpers;

use common\api\lazadainterface\LazadaInterface_Helper;
use console\helpers\CommonHelper;
use console\helpers\LogType;
use eagle\models\SaasLazadaAutosync;
use eagle\models\SaasLazadaUser;
use eagle\modules\listing\models\LazadaListing;
use eagle\modules\util\helpers\ConfigHelper;
use eagle\modules\util\helpers\SQLHelper;
use eagle\modules\util\helpers\TimeUtil;
use Yii;

/**
 * +------------------------------------------------------------------------------
 * Lazada 数据同步类 主要执行订单数据同步
 * +------------------------------------------------------------------------------
 */
class LazadaAutoFetchListingHelper
{
    public static $cronJobId = 0;
    private static $lazadaGetListVersion = null;
    const ALL_EXISTING = 1;
    const NEW_BY_TIME = 2;
    const LOG_ID = "eagle-modules-listing-helpers-LazadaAutoFetchListingHelper";
    const ALL = "all";
    public static $type = array(
        1 => '首次需要同步的所有订单',
        2 => '',
    );

    //lazada平台的状态跟eagle的订单状态的对应关系
    public static $LAZADA_EAGLE_ORDER_STATUS_MAP = array(
        //	'Pending' =>100,  //等待买家付款
        'pending' => 200, //买家已付款
        'ready_to_ship' => 200, //买家已付款
        'shipped' => 500,//CUBE_CONST::SentGood,  //卖家已发货
        'delivered' => 500,
        'returned' => 600,
        'failed' => 600,
        'canceled' => 600
    );

    //lazad产品filter 状态
    public static $LAZADA_PRODUCT_SUBSTATUS = array("pending", "live", "rejected", "image-missing", "sold-out", "inactive", "deleted");// 拉取顺序有关 显示状态

    public static $SUBSTATUS_KEY_NAME_MAP = array(
        "inactive" => "Product Inactive", "pending" => "Quality Approval Pending", "live" => "Live", "rejected" => "Poor Quality",
        "image-missing" => "Image Missing", "sold-out" => "Sold Out", "deleted" => "Deleted Products"
    );

    // 在线商品的修改状态
    const PUTING_ON = 1;
    const PUTING_OFF = 2;
    const EDITING_QUANTITY = 3;
    const EDITING_PRICE = 4;
    const EDITING_SALESINFO = 5;
    public static $LISTING_EDITING_TYPE_MAP = array(
        1 => "上架中",
        2 => "下架中",
        3 => "正在修改库存",
        4 => "正在修改价格",
        5 => "正在修改促销信息",
    );

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
     * 先判断是否真的抢到待处理账号
     * @param  $autosyncId -- saas_lazada_autosync表的id
     * @return null或者$SAA_obj , null表示抢不到记录
     */
    private static function _lockLazadaAutosyncRecord($autosyncId)
    {
        $nowTime = time();
        $connection = Yii::$app->db;
        $command = $connection->createCommand("update saas_lazada_autosync set status=1,last_finish_time=$nowTime where id =" . $autosyncId . " and status<>1 ");
        $affectRows = $command->execute();
        if ($affectRows <= 0) return null; //抢不到---如果是多进程的话，有抢不到的情况
        // 抢到记录
        $SAA_obj = SaasLazadaAutosync::findOne($autosyncId);
        return $SAA_obj;
    }

    /**
     * 该进程判断是否需要退出
     * 通过配置全局配置数据表ut_global_config_data的Order/lazadaGetOrderVersion 对应数值
     *
     * @return  true or false
     */
    private static function checkNeedExitNot()
    {
        $lazadaGetListVersionFromConfig = ConfigHelper::getGlobalConfig("Order/lazadaGetListVersion", 'NO_CACHE');
        if (empty($lazadaGetListVersionFromConfig)) {
            //数据表没有定义该字段，不退出。
            //	self::$lazadaGetOrderListVersion ="v0";
            return false;
        }

        //如果自己还没有定义，去使用global config来初始化自己
        if (self::$lazadaGetListVersion === null) self::$lazadaGetListVersion = $lazadaGetListVersionFromConfig;

        //如果自己的version不同于全局要求的version，自己退出，从新起job来使用新代码
        if (self::$lazadaGetListVersion <> $lazadaGetListVersionFromConfig) {
            echo "Version new $lazadaGetListVersionFromConfig , this job ver " . self::$lazadaGetListVersionFromConfig . " exits \n";
            return true;
        }
        return false;
    }

    /**
     * 获取所有lazada用户的api访问信息。 email,token,销售站点
     */
    private static function getAllLazadaAccountInfoMap()
    {
        $lazadauserMap = array();

        $lazadaUsers = SaasLazadaUser::find()->all();
        foreach ($lazadaUsers as $lazadaUser) {
            $lazadauserMap[$lazadaUser->lazada_uid] = array(
                "userId" => $lazadaUser->platform_userid,
                "apiKey" => $lazadaUser->token,
                "countryCode" => $lazadaUser->lazada_site
            );
        }

        return $lazadauserMap;

    }

    /**
     * 获取指定ladazaUid的api访问信息。 email,token,销售站点
     * @param unknown $lazadaUid
     * @return
     */
    private static function _getLazadaAccountInfo($lazadaUid)
    {
        $lazadaUser = SaasLazadaUser::findOne(["lazada_uid" => $lazadaUid]);
        if ($lazadaUser === null) return null;

        $returnInfo = array(
            "userId" => $lazadaUser->platform_userid,
            "apiKey" => $lazadaUser->token,
            "countryCode" => $lazadaUser->lazada_site
        );
        return $returnInfo;
    }

    /**
     *
     * @param unknown $SAA_obj
     * @param unknown $errorMessage
     * @param unknown $nowTime
     * @param unknown $fetchPeriod
     */
    public static function handleGetListError($SAA_obj, $errorMessage, $nowTime, $fetchPeriod)
    {
        \Yii::info("lazadaUpList_fetch handleGetListError  autoSyncId:" . $SAA_obj->id . " puid:" . $SAA_obj->puid . " lazadaUid:" . $SAA_obj->lazada_uid . " errorMsg:" . $errorMessage, "file");
        $SAA_obj->message = $errorMessage;
        $SAA_obj->error_times = $SAA_obj->error_times + 1;
        $SAA_obj->status = 3;
        //$SAA_obj->last_finish_time=$nowTime;//上一次运行时间
        $SAA_obj->next_execution_time = $nowTime + $fetchPeriod;
        $SAA_obj->update_time = $nowTime;
        if (!$SAA_obj->save(false)) {
            \Yii::info("lazadaUpList_fetch handleGetListError  autoSyncId:" . $SAA_obj->id . " puid:" . $SAA_obj->puid . " lazadaUid:" . $SAA_obj->lazada_uid . "  SAA_obj->save false", "file");
        }
    }

    /**
     *
     * @param unknown $SAA_obj
     * @param unknown $errorMessage
     * @param unknown $nowTime
     * @param unknown $fetchPeriod
     */
    public static function handleGetListErrorV2(&$SAA_obj, $errorMessage, $nowTime, $fetchPeriod)
    {
        self::localLog("lazadaUpList_fetch handleGetListError  autoSyncId:" . $SAA_obj->id . " puid:" . $SAA_obj->puid . " lazadaUid:" . $SAA_obj->lazada_uid . " errorMsg:" . $errorMessage);
        $SAA_obj->message = $errorMessage;
        $SAA_obj->error_times = $SAA_obj->error_times + 1;
        $SAA_obj->status = 3;
        //$SAA_obj->last_finish_time=$nowTime;//上一次运行时间
        $SAA_obj->next_execution_time = $nowTime + $fetchPeriod;
        $SAA_obj->update_time = $nowTime;
        if (!$SAA_obj->save(false)) {
            self::localLog("lazadaUpList_fetch handleGetListError  autoSyncId:" . $SAA_obj->id . " puid:" . $SAA_obj->puid . " lazadaUid:" . $SAA_obj->lazada_uid . "  SAA_obj->save false", "file");
        }
    }

    /**
     * 为指定的lazada账号拉取一段时间内的新增的和有修改过的listing
     * @param  $config ------该lazada的对接信息。 email和token
     * @param  $SAA_obj --- Saas_lazada_autosync 的对象
     * @return listing数量 or false
     */
    public static function _getUpdatedListForOneLazada($config, $SAA_obj)
    {
        $fetchPeriod = 60 * 60; //秒为单位. 2次后台自动拉取的时间间隔
        $failRetryPeriod = 5 * 60;
        $fetchEndTimeAdvanced = 5 * 60;  //秒为单位 .为了避免由于服务器的时间不准确导致尝试拉取超前时间点的订单。这里end_time主动往前移5分钟。

        $nowTime = time();
        $reqStatus = "all";
        $isFirstTime = 0;

        if ($SAA_obj->end_time == 0) {
            //第一次拉取
            $reqStatus = "all";
            $isFirstTime = 1;
            $start_time = 0;
            $end_time = $nowTime;
        } else if ($nowTime - $SAA_obj->end_time >= 86400) {// 为曾经中断的job 设置的
            $start_time = $SAA_obj->end_time;
            $end_time = $start_time + 86400;
        } else {
            $start_time = $SAA_obj->end_time;
            $end_time = $nowTime;
        }

        //为了避免由于服务器的时间不准确导致尝试拉取超前时间点的订单。这里end_time主动往前移5分钟。
        $end_time = $end_time - $fetchEndTimeAdvanced;
        if ($start_time > $end_time) $start_time = $end_time - $fetchEndTimeAdvanced;

        $timeMS1 = TimeUtil::getCurrentTimestampMS();
        //3.2  访问proxy并保存结果
// 		\Yii::info("lazadaUpList_fetch  autoSyncId:".$SAA_obj->id.",puid:".$SAA_obj->puid.",lazadaUid:".$SAA_obj->lazada_uid." isFirstTime:$isFirstTime,reqStatus:$reqStatus,start_time:$start_time,end_time:$end_time","file");
// 		list($ret,$errorMessage,$totalNum)=self::_getListingAndSave($config,$start_time,$end_time,$SAA_obj,$reqStatus,$isFirstTime);
// 		if ($ret==false) {
// 			self::handleGetListError($SAA_obj,$errorMessage,$nowTime,$fetchPeriod);
// 			return false;
// 		}
        //3.3 通过简单listing拉取返回的listing的status是很模糊 的，需要通过指定状态再次拉取  all, live, inactive, deleted, image-missing, pending, rejected, sold-out
// 		foreach(self::$LAZADA_PRODUCT_SUBSTATUS as $reqStatus){
// 			\Yii::info("lazadaUpList_fetch  before self::_getListingAndSave autoSyncId:".$SAA_obj->id.",puid:".$SAA_obj->puid.",lazadaUid:".$SAA_obj->lazada_uid.",reqStatus:$reqStatus,start_time:$start_time,end_time:$end_time","file");
// 		    list($ret,$errorMessage,$tempNum)=self::_getListingAndSave($config,$start_time,$end_time,$SAA_obj,$reqStatus,$isFirstTime);
// 		    if ($ret==false) {
// 		        self::handleGetListError($SAA_obj,$errorMessage,$nowTime,$fetchPeriod);
// 		        return false;
// 		    }	
// 		    \Yii::info("lazadaUpList_fetch  after self::_getListingAndSave autoSyncId:".$SAA_obj->id.",puid:".$SAA_obj->puid.",lazadaUid:".$SAA_obj->lazada_uid.",reqStatus:$reqStatus","file");
// 		}

        // dzt20151216 合并所有状态一次性向proxy拉取
        $reqStatus = self::$LAZADA_PRODUCT_SUBSTATUS;
// 		array_unshift($reqStatus,"all");// dzt20160112 all 单独拿出来get了
        \Yii::info("lazadaUpList_fetch  before self::_getFilterListingAndSave autoSyncId:" . $SAA_obj->id . ",puid:" . $SAA_obj->puid . ",lazadaUid:" . $SAA_obj->lazada_uid . ",reqStatus:" . implode(',', $reqStatus) . ",start_time:$start_time,end_time:$end_time", "file");
        list($ret, $errorMessage, $totalNum) = self::_getFilterListingAndSave($config, $start_time, $end_time, $SAA_obj, $reqStatus, $isFirstTime);
        if ($ret == false) {
            if ($SAA_obj->error_times > 5) {
                self::handleGetListError($SAA_obj, $errorMessage, $nowTime, $fetchPeriod);
            } else {
                self::handleGetListError($SAA_obj, $errorMessage, $nowTime, $failRetryPeriod);
            }
            return false;
        }
        \Yii::info("lazadaUpList_fetch  after self::_getFilterListingAndSave autoSyncId:" . $SAA_obj->id . ",puid:" . $SAA_obj->puid . ",lazadaUid:" . $SAA_obj->lazada_uid . ",reqStatus:" . implode(',', $reqStatus), "file");

        $SAA_obj->start_time = $start_time;
        $SAA_obj->end_time = $end_time;
        $SAA_obj->status = 2;
        $SAA_obj->error_times = 0;
        $SAA_obj->message = "";
        $SAA_obj->update_time = $nowTime;
        $SAA_obj->next_execution_time = $nowTime + $fetchPeriod;
        if (!$SAA_obj->save(false)) {
            \Yii::info("lazadaUpList_fetch  error  autoSyncId:" . $SAA_obj->id . ",puid:" . $SAA_obj->puid . "  saas_obj save false message:" . print_r($SAA_obj->errors, true), "file");
        }

        $timeMS2 = TimeUtil::getCurrentTimestampMS();
        \Yii::info("lazadaUpList_fetch_finish  autoSyncId:" . $SAA_obj->id . ",puid:" . $SAA_obj->puid . ",lazadaUid:" . $SAA_obj->lazada_uid . ",totalNum=$totalNum,t2_1=" . ($timeMS2 - $timeMS1), "file");

        return $totalNum;

    }


    /**
     * 为指定的lazada账号拉取一段时间内的新增的和有修改过的listing
     * @param  $config ------该lazada的对接信息。 email和token
     * @param  $SAA_obj --- Saas_lazada_autosync 的对象
     * @return listing数量 or false
     */
    public static function _getUpdatedListForOneLazadaV2($config, $SAA_obj)
    {
        $fetchPeriod = 60 * 60; //秒为单位. 2次后台自动拉取的时间间隔
        $failRetryPeriod = 5 * 60;
        $fetchEndTimeAdvanced = 5 * 60;  //秒为单位 .为了避免由于服务器的时间不准确导致尝试拉取超前时间点的订单。这里end_time主动往前移5分钟。

        $nowTime = time();
        $isFirstTime = 0;

        if ($SAA_obj->end_time == 0) {
            //第一次拉取
            $isFirstTime = 1;
            $start_time = 0;
            $end_time = $nowTime;
        } else if ($nowTime - $SAA_obj->end_time >= 86400) {// 为曾经中断的job 设置的
            $start_time = $SAA_obj->end_time;
            $end_time = $start_time + 86400;
        } else {
            $start_time = $SAA_obj->end_time;
            $end_time = $nowTime;
        }

        //为了避免由于服务器的时间不准确导致尝试拉取超前时间点的订单。这里end_time主动往前移5分钟。
        $end_time = $end_time - $fetchEndTimeAdvanced;
        if ($start_time > $end_time) $start_time = $end_time - $fetchEndTimeAdvanced;

        $timeMS1 = TimeUtil::getCurrentTimestampMS();
        $reqStatus = self::$LAZADA_PRODUCT_SUBSTATUS;
        list($ret, $errorMessage, $totalNum) = self::_getFilterListingAndSaveV2($config, $start_time, $end_time, $SAA_obj, $reqStatus, $isFirstTime);
        if ($ret == false) {
            if ($SAA_obj->error_times > 5) {
                self::handleGetListErrorV2($SAA_obj, $errorMessage, $nowTime, $fetchPeriod);
            } else {
                self::handleGetListErrorV2($SAA_obj, $errorMessage, $nowTime, $failRetryPeriod);
            }
            return false;
        }
        self::localLog("lazadaUpList_fetch  after self::_getFilterListingAndSave autoSyncId:" . $SAA_obj->id . ",puid:" . $SAA_obj->puid . ",lazadaUid:" . $SAA_obj->lazada_uid . ",reqStatus:" . implode(',', $reqStatus));

        $SAA_obj->start_time = $start_time;
        $SAA_obj->end_time = $end_time;
        $SAA_obj->status = 2;
        $SAA_obj->error_times = 0;
        $SAA_obj->message = "";
        $SAA_obj->update_time = $nowTime;
        $SAA_obj->next_execution_time = $nowTime + $fetchPeriod;
        if (!$SAA_obj->save(false)) {
            self::localLog("lazadaUpList_fetch  error  autoSyncId:" . $SAA_obj->id . ",puid:" . $SAA_obj->puid . "  saas_obj save false message:" . print_r($SAA_obj->errors, true), LogType::ERROR);
        }

        $timeMS2 = TimeUtil::getCurrentTimestampMS();
        self::localLog("lazadaUpList_fetch_finish  autoSyncId:" . $SAA_obj->id . ",puid:" . $SAA_obj->puid . ",lazadaUid:" . $SAA_obj->lazada_uid . ",totalNum=$totalNum,t2_1=" . ($timeMS2 - $timeMS1));

        return $totalNum;

    }

    /**
     *  后台触发------获取指定时间内所有账户的有更新的订单，第一次就是获取所有listing，后面就是拉取新增或者有修改的listing
     */
    public static function getUpdatedisting()
    {
        echo "++++++++++++getUpdatedisting \n";
        //1. 检查当前本job版本，如果和自己的版本不一样，就自动exit吧
        $ret = self::checkNeedExitNot();
        if ($ret === true) exit;

        $backgroundJobId = self::getCronJobId(); //获取进程id号，主要是为了打印log
        $connection = \Yii::$app->db;
        $type = 4;//同步job类型:1--获取旧订单,2--获取最新create产生的订单,3--获取最新update产生的订单，4----获取listing
        $hasGotRecord = false;//是否抢到账号

        //2. 从账户同步表
        $hasGotRecord = false;
        $nowTime = time();
        // 同步状态:  status 0--没处理过, 1--执行中, 2--完成, 3--上一次执行有问题
        $sqlStr = 'select `id` from  `saas_lazada_autosync` where `is_active` = 1 AND `status` <>1 ' .
            'AND `error_times` < 15 AND `type`=' . $type . '  AND  next_execution_time<' . $nowTime . ' ORDER BY `next_execution_time` ASC LIMIT 10 ';

// 		$sqlStr='select `id` from  `saas_lazada_autosync` where  `is_active` = 1 AND `message` like "%local%" AND `status` <>1 AND `error_times` >= 10 AND `type`='.$type.'  ORDER BY `binding_time` desc LIMIT 1 ' ;


        //	echo $sqlStr." \n";
        $command = $connection->createCommand($sqlStr);

        $allLazadaAccountsInfoMap = self::getAllLazadaAccountInfoMap();
        $dataReader = $command->query();
        while (($row = $dataReader->read()) !== false) {
            echo "++++++++dataReader->read() " . $row['id'] . "\n";
            // 先判断是否真的抢到待处理账号
            $SAA_obj = self::_lockLazadaAutosyncRecord($row['id']);
            if ($SAA_obj === null) continue; //抢不到---如果是多进程的话，有抢不到的情况

            $hasGotRecord = true;  // 抢到记录

            \Yii::info("lazadaUpList_get autoSyncId:" . $SAA_obj->id . ",puid:" . $SAA_obj->puid . ",lazadaUid:" . $SAA_obj->lazada_uid, "file");

            //3. 调用渠道的api来获取对应账号的订单列表信息，并把渠道返回的所有的订单header的信息保存到订单详情同步表QueueLazadaGetorder
            //3.1 整理请求参数
            $config = $allLazadaAccountsInfoMap[$SAA_obj->lazada_uid];


            \Yii::info("lazadaUpList_get before _getUpLazada autoSyncId:" . $SAA_obj->id . ",puid:" . $SAA_obj->puid . ",lazadaUid:" . $SAA_obj->lazada_uid, "file");
            $result = self::_getUpdatedListForOneLazada($config, $SAA_obj);
            if ($result !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     *  后台触发------获取指定时间内所有账户的有更新的订单，第一次就是获取所有listing，后面就是拉取新增或者有修改的listing
     */
    public static function getUpdatedistingByID($id)
    {
        self::localLog("getUpdatedistingByID");

        $connection = \Yii::$app->db;
        $type = 4;//同步job类型:1--获取旧订单,2--获取最新create产生的订单,3--获取最新update产生的订单，4----获取listing
        $hasGotRecord = false;//是否抢到账号

        //2. 从账户同步表
        $hasGotRecord = false;
        $nowTime = time();
        // 同步状态:  status 0--没处理过, 1--执行中, 2--完成, 3--上一次执行有问题
        $sqlStr = 'select `id` from  `saas_lazada_autosync` where `is_active` = 1 AND `id`=' . $id .
            ' AND `type`=' . $type;

        $command = $connection->createCommand($sqlStr);

        $allLazadaAccountsInfoMap = self::getAllLazadaAccountInfoMap();
        $dataReader = $command->query();
        while (($row = $dataReader->read()) !== false) {
            self::localLog("dataReader->read() " . $row['id']);
            // 先判断是否真的抢到待处理账号
            $SAA_obj = self::_lockLazadaAutosyncRecord($row['id']);
            if ($SAA_obj === null) {
                self::localLog("task is processing and skip");
                continue; //抢不到---如果是多进程的话，有抢不到的情况
            }
            self::localLog("lazadaUpList_get autoSyncId:" . $SAA_obj->id . ",puid:" . $SAA_obj->puid . ",lazadaUid:" . $SAA_obj->lazada_uid);

            //3. 调用渠道的api来获取对应账号的订单列表信息，并把渠道返回的所有的订单header的信息保存到订单详情同步表QueueLazadaGetorder
            //3.1 整理请求参数
            $config = $allLazadaAccountsInfoMap[$SAA_obj->lazada_uid];


//            self::localLog("lazadaUpList_get before _getUpLazada autoSyncId:" . $SAA_obj->id . ",puid:" . $SAA_obj->puid . ",lazadaUid:" . $SAA_obj->lazada_uid);
            $result = self::_getUpdatedListForOneLazadaV2($config, $SAA_obj);
            if ($result !== false) {
                return true;
            }
        }
        return false;
    }

    /**
     *
     * @param unknown $lazadaUid
     * @param unknown $ret
     * @param unknown $message
     * @param unknown $number
     */
    public static function _manualSyncResult($lazadaUid, $ret, $message, $number)
    {
        $result = array($ret, $message, $number);

        \Yii::info("lazadaUpList_man lazadaUid:$lazadaUid result:" . print_r($result, true), "file");
        return $result;
    }

    /**
     * 用户在前端点击"手工同步"
     * @param  $lazadaUid ---saas_lazada_user的id
     * @return array($ret,$message,$number)
     * ret----true or false
     * message----失败原因
     * number----获取的 数量
     */
    public static function manualSync($lazadaUid)
    {

        \Yii::info("lazadaUpList_man trigger lazadaUid:$lazadaUid", "file");
        $SAA_obj = SaasLazadaAutosync::findOne(["lazada_uid" => $lazadaUid, "type" => 4]);
        if ($SAA_obj === null) return self::_manualSyncResult($lazadaUid, false, "Error_LAZ002  指定店铺不存在 ", 0);
        if ($SAA_obj->is_active === 0) return self::_manualSyncResult($lazadaUid, false, "Error_LAZ003  指定店铺已停止同步 ", 0);

        $manualInterval = 10 * 60;//s为单位。  二次手工同步的至少时间间隔 10分钟

        //1.合理性检查。
        //1.1是否已经在拉取
        $tryCount = 0;
        while ($SAA_obj->status == 1 and $tryCount < 3) {//刚好后台正在拉取新的listing
            \Yii::info("lazadaUpList_man SAA_obj->status==1 sleep10", "file");
            $tryCount++;
            sleep(10);
            $SAA_obj = SaasLazadaAutosync::findOne(["lazada_uid" => $lazadaUid, "type" => 4]);
        }
        if ($SAA_obj->status == 1) return self::_manualSyncResult($lazadaUid, false, "Error_LAZ001  同步失败:产品同步任务正在进行，请稍后再试 ", 0);

        //1.2 手工触发频率控制
        $nowTime = time();
        if ($SAA_obj->manual_trigger_time > 0 and $nowTime - $SAA_obj->manual_trigger_time < $manualInterval) {
            return self::_manualSyncResult($lazadaUid, false, "您的商品同步操作频率过高，请10分钟后再更新 ", 0);
        }


        //2.设置锁，正在拉取
        $autosyncId = $SAA_obj->id;
        $connection = Yii::$app->db;
        $command = $connection->createCommand("update saas_lazada_autosync set status=1,last_finish_time=$nowTime,manual_trigger_time=$nowTime where id =" . $autosyncId . " and status<>1 ");
        $affectRows = $command->execute();
        if ($affectRows <= 0) return self::_manualSyncResult($lazadaUid, false, "Error_LAZ002  同步失败 ", 0);

        $SAA_obj = SaasLazadaAutosync::findOne(["id" => $autosyncId]);

        //3. 指定lazada uid进行拉取，获取拉取的listing数量
        $config = self::_getLazadaAccountInfo($lazadaUid);
        \Yii::info("lazadaUpList_man trigger lazadaUid:$lazadaUid,puid=" . $SAA_obj->puid . " before _getUpdatedListForOneLazada", "file");
        $ret = self::_getUpdatedListForOneLazada($config, $SAA_obj);
        \Yii::info("lazadaUpList_man trigger lazadaUid:$lazadaUid,puid=" . $SAA_obj->puid . " after _getUpdatedListForOneLazada", "file");


        if ($ret === false) $result = self::_manualSyncResult($lazadaUid, false, "拉取失败。请重试", 0);
        else $result = self::_manualSyncResult($lazadaUid, true, "", $ret);

        return $result;

    }


    /**
     * 指定时间段，从proxy中获取listing并保存。 这里start_time=0表示第一次拉取
     *
     * @param unknown $config
     * @param unknown $start_time
     * @param unknown $end_time
     * @param unknown $SAA_obj
     * @param unknown $listStatus ---
     *  lazada接口支持 all, live, inactive, deleted, image-missing, pending, rejected, sold-out
     *
     */
    private static function _getListingAndSave($config, $start_time, $end_time, $SAA_obj, $status, $isFirstTime)
    {
        \Yii::info("lazadaUpList_getListSave  autoSyncId:" . $SAA_obj->id . ",puid:" . $SAA_obj->puid . ",status:$status, start:$start_time,end:$end_time start:" . date("Y-m-d H:i:s", $start_time) . "  end:" . date("Y-m-d H:i:s", $end_time), "file");

        $pageSize = 1000;
        //	$pageSize=2;
        $page = 1;
        $lazadaUid = $SAA_obj->lazada_uid;

        if ($start_time == 0) {
            $reqParams = array(
                //	"CreatedAfter"=>gmdate("Y-m-d\TH:i:s+0000",$start_time),
                "Filter" => $status,
                "Limit" => $pageSize,
                //"CreatedBefore"=>gmdate("Y-m-d\TH:i:s+0000",$end_time)
                "UpdatedBefore" => gmdate("Y-m-d\TH:i:s+0000", $end_time)
            );
        } else {
            $reqParams = array(
                "Filter" => $status,
                "Limit" => $pageSize,
                //	"CreatedAfter"=>gmdate("Y-m-d\TH:i:s+0000",$start_time),
                //	"CreatedBefore"=>gmdate("Y-m-d\TH:i:s+0000",$end_time)
                "UpdatedAfter" => gmdate("Y-m-d\TH:i:s+0000", $start_time),
                "UpdatedBefore" => gmdate("Y-m-d\TH:i:s+0000", $end_time)

            );
        }


        $rejectedInfoArr = array();
        $sellerSkuListArr = array();

        $timeMS1 = TimeUtil::getCurrentTimestampMS();
        $totalNum = 0;
        $hasGotAll = false;
        while ($hasGotAll === false) {
            $offset = isset($reqParams["Offset"]) ? $reqParams["Offset"] : "0";
            \Yii::info("lazadaUpList_getListSave  before LazadaInterface_Helper::getProducts autoSyncId:" . $SAA_obj->id . " puid:" . $SAA_obj->puid . "reqParams:" . print_r($reqParams, true), "file");
            $timeMSTmp1 = TimeUtil::getCurrentTimestampMS();
            $result = LazadaInterface_Helper::getProducts($config, $reqParams);
            $timeMSTmp2 = TimeUtil::getCurrentTimestampMS();
            \Yii::info("lazadaUpList_getListSave  after LazadaInterface_Helper::getProducts t2_t1=" . ($timeMSTmp2 - $timeMSTmp1) . ",autoSyncId:" . $SAA_obj->id . " puid:" . $SAA_obj->puid, "file");
            if ($result["success"] === false) {
                return array(false, $result["message"], 0);
            }
            if (!isset($result["response"]) or !isset($result["response"]["products"])) {
                return array(false, "response->products not exist", 0);
            }

            $products = $result["response"]["products"];

            //没有结果了
            if (empty($products)) {
                $hasGotAll = true;
                break;
            }
            //最后一页了,不需要再翻页
            if (count($products) < $pageSize) {
                $hasGotAll = true;
            }
            \Yii::info("lazadaUpList_getListSave  after LazadaInterface_Helper::getProducts totalNum=$totalNum,autoSyncId:" . $SAA_obj->id . " puid:" . $SAA_obj->puid, "file");

            $totalNum = count($products) + $totalNum;
            $nowTime = time();
            foreach ($products as $listingInfo) {
                $sellerSku = $listingInfo["SellerSku"];
                //print_r($listingInfo);
                if ($listingInfo["SalePrice"] === "") $listingInfo["SalePrice"] = 0;
                //(1) all的拉取
                if ($status == "all") {
                    //if ($reqStatus<>"first-all") {
                    if ($isFirstTime == 0) {
                        //	$lazadaListingObj=LazadaListing::findOne(["ShopSku"=>$listingInfo["ShopSku"]]);
                        $lazadaListingObj = LazadaListing::findOne(["SellerSku" => $sellerSku, "lazada_uid_id" => $lazadaUid]);
                        if ($lazadaListingObj === null) {
                            $lazadaListingObj = new LazadaListing;
                            $lazadaListingObj->setAttributes($listingInfo);
                            if (!empty($listingInfo["SaleStartDate"])) {//dzt20151130 不能用isset ，api 返回 确实isset 但为 "" ,这样下面操作会把当前时间写入字段
                                $lazadaListingObj->SaleStartDate = TimeUtil::getTimestampFromISO8601($listingInfo["SaleStartDate"]);
                            } else $lazadaListingObj->SaleStartDate = 0;
                            if (!empty($listingInfo["SaleEndDate"])) {//dzt20151130 api传输为"" setAttributes SaleStartDate为"" 但数据库不能为"" 只能为int default 0 ,  上下的SaleStartDate , SaleEndDate 同理
                                $lazadaListingObj->SaleEndDate = TimeUtil::getTimestampFromISO8601($listingInfo["SaleEndDate"]);
                            } else $lazadaListingObj->SaleEndDate = 0;

                            $lazadaListingObj->create_time = $nowTime;
                            $lazadaListingObj->update_time = $nowTime;
                            $lazadaListingObj->lazada_uid_id = $lazadaUid;

                            $lazadaListingObj->save(false);
                            continue;
                        }
                    } else { //第一拉取的不需要判断数据库是否已经存在该listing
                        $lazadaListingObj = new LazadaListing;
                    }

                    $lazadaListingObj->setAttributes($listingInfo);
                    if (!empty($listingInfo["SaleStartDate"])) {//dzt20151130
                        $lazadaListingObj->SaleStartDate = TimeUtil::getTimestampFromISO8601($listingInfo["SaleStartDate"]);
                    } else {
                        $lazadaListingObj->SaleStartDate = 0;//dzt20151130
                    }
                    if (!empty($listingInfo["SaleEndDate"])) {//dzt20151130
                        $lazadaListingObj->SaleEndDate = TimeUtil::getTimestampFromISO8601($listingInfo["SaleEndDate"]);
                    } else {
                        $lazadaListingObj->SaleEndDate = 0;//dzt20151130
                    }
                    $lazadaListingObj->create_time = $nowTime;
                    $lazadaListingObj->update_time = $nowTime;
                    $lazadaListingObj->lazada_uid_id = $lazadaUid;
                    $lazadaListingObj->save(false);
                    continue;
                }


                //(2) 非 all的拉取
                //(2.1)只需要更新有限状态信息
                $lazadaListingObj = LazadaListing::findOne(["SellerSku" => $sellerSku, "lazada_uid_id" => $lazadaUid]);
                $sellerSkuListArr[] = $sellerSku;

                if ($lazadaListingObj === null) { //正常来说应该不会走入这个分支
                    $lazadaListingObj = new LazadaListing;
                    $lazadaListingObj->setAttributes($listingInfo);
                    if (!empty($listingInfo["SaleStartDate"])) {//dzt20151130
                        $lazadaListingObj->SaleStartDate = TimeUtil::getTimestampFromISO8601($listingInfo["SaleStartDate"]);
                    } else {
                        $lazadaListingObj->SaleStartDate = 0;//dzt20151130
                    }
                    if (!empty($listingInfo["SaleEndDate"])) {//dzt20151130
                        $lazadaListingObj->SaleEndDate = TimeUtil::getTimestampFromISO8601($listingInfo["SaleEndDate"]);
                    } else {
                        $lazadaListingObj->SaleEndDate = 0;//dzt20151130
                    }
                    $lazadaListingObj->sub_status = $status;
                    $lazadaListingObj->create_time = $nowTime;
                    $lazadaListingObj->update_time = $nowTime;
                    $lazadaListingObj->lazada_uid_id = $lazadaUid;
                    $lazadaListingObj->save(false);

                } else {
                    $lazadaListingObj->sub_status = $status;
                    $lazadaListingObj->update_time = $nowTime;
                    if ($status == "rejected") {
                        $rejectedInfoArr[$sellerSku] = $listingInfo["RejectReason"];
                        $lazadaListingObj->error_message = $listingInfo["RejectReason"];
                    }
                    $lazadaListingObj->save(false);

                }

            }

            $page++;
            $reqParams["Offset"] = ($page - 1) * $pageSize;
            //echo "count:".print_r($products,true)." \n";
        }
        if ($isFirstTime == 0) { //不是第一次的话，需要通知
            if ($status == "rejected") {
                //TODO
            }

        }


        $timeMS2 = TimeUtil::getCurrentTimestampMS();
        //echo "lazada_getListingAndSave lazadaUid=$lazadaUid,status=$status,totalNum=$totalNum,t2_1=".($timeMS2-$timeMS1)." \n";
        //\Yii::info("lazadaUpList_getListSave  autoSyncId:".$SAA_obj->id.",puid:".$SAA_obj->puid,"file");
        \Yii::info("lazadaUpList_getListSave_finish  autoSyncId:" . $SAA_obj->id . ",puid:" . $SAA_obj->puid . ",status=$status,totalNum=$totalNum,t2_1=" . ($timeMS2 - $timeMS1), "file");

        return array(true, "", $totalNum);


    }

    /**
     * 指定时间段，从proxy中获取listing并保存。 这里start_time=0表示第一次拉取
     *
     * @param unknown $config
     * @param int $start_time
     * @param int $end_time
     * @param unknown $SAA_obj
     * @param array $statusArr 下面状态的数组
     *  lazada接口支持 all, live, inactive, deleted, image-missing, pending, rejected, sold-out
     *
     */
    private static function _getFilterListingAndSave($config, $start_time, $end_time, $SAA_obj, $statusArr, $isFirstTime)
    {
        \Yii::info("_getFilterListingAndSave  autoSyncId:" . $SAA_obj->id . ",puid:" . $SAA_obj->puid . ",status:" . implode(',', $statusArr) . ", start:$start_time,end:$end_time start:" . date("Y-m-d H:i:s", $start_time) . "  end:" . date("Y-m-d H:i:s", $end_time), "file");

        $lazadaUid = $SAA_obj->lazada_uid;

        if ($start_time == 0) {
            $reqParams = array(
// 				"filterStatus"=>$statusArr,
                "UpdatedBefore" => gmdate("Y-m-d\TH:i:s+0000", $end_time)
            );
        } else {
            $reqParams = array(
// 				"filterStatus"=>$statusArr,
                "UpdatedAfter" => gmdate("Y-m-d\TH:i:s+0000", $start_time),
                "UpdatedBefore" => gmdate("Y-m-d\TH:i:s+0000", $end_time)

            );
        }

        $rejectedInfoArr = array();
        $sellerSkuListArr = array();

        $timeMS1 = TimeUtil::getCurrentTimestampMS();
        $totalNum = 0;

        // 由于拉取个数太多 导致重新在ealge 添加分页
        // 由于上w 个产品所有状态一起获取会导致proxy 内存爆了，所以这里 重新加入分页
        // 但由于分页获取所有状态比较麻烦，控制其他状态get 到的产 品必须 all也 get 到，所以这里干脆先单独把all get 完在get其他状态的产品
        $reqParams["filterStatus"] = array("all");
// 		$reqParams["page"] = 1;
        $reqParams["offset"] = 0;
        $hasGotAll = false;
        $products = array();
        \Yii::info("_getFilterListingAndSave all before autoSyncId:" . $SAA_obj->id . " puid:" . $SAA_obj->puid, "file");
        try {

            while ($hasGotAll === false) {

                \Yii::info("_getFilterListingAndSave LazadaInterface_Helper::getFilterProducts all before autoSyncId:" . $SAA_obj->id . " puid:" . $SAA_obj->puid . "reqParams:" . json_encode($reqParams), "file");
                // 目前测试到 us proxy 获取1000个product 要3s ，10000产品 60+基本可以从proxy 获取完。但100+M 传输时间也比较长
                // 但是260s 后仍然获取不到返回结果。
                $timeMSTmp1 = TimeUtil::getCurrentTimestampMS();
                $result = LazadaInterface_Helper::getFilterProducts($config, $reqParams, $isFirstTime);
                $timeMSTmp2 = TimeUtil::getCurrentTimestampMS();
                \Yii::info("_getFilterListingAndSave LazadaInterface_Helper::getFilterProducts all after t2_t1=" . ($timeMSTmp2 - $timeMSTmp1) . ",autoSyncId:" . $SAA_obj->id . " puid:" . $SAA_obj->puid, "file");
                if ($result["success"] === false) {
                    return array(false, $result["message"], 0);
                }
                if (!isset($result["response"]) or !isset($result["response"]["allProducts"])) {
                    return array(false, "response->products not exist", 0);
                }

// 				$page = $result["response"]["page"];
                $offset = $result["response"]["offset"];
                $tempProducts = $result["response"]["allProducts"];

                if (!empty($tempProducts['all'])) {
                    if (!empty($products['all'])) {
                        $products['all'] = array_merge($products['all'], $tempProducts['all']);
                    } else {
                        $products['all'] = $tempProducts['all'];
                    }

                    $num = count($tempProducts['all']);
// 					\Yii::info("all page:$page Num=$num,autoSyncId:".$SAA_obj->id." puid:".$SAA_obj->puid,"file");
                    \Yii::info("offset:$offset Num=$num,autoSyncId:" . $SAA_obj->id . " puid:" . $SAA_obj->puid, "file");
                    $totalNum += $num;
                }

                if ($result["response"]["hasGotAll"] == 1) {
                    $hasGotAll = true;
                }

// 				$reqParams["page"] = $page+1;
                $reqParams["offset"] = $offset;
            }// end of $hasGotAll while all

            $timeMS2 = TimeUtil::getCurrentTimestampMS();
            \Yii::info("_getFilterListingAndSave all after autoSyncId:" . $SAA_obj->id . ",puid:" . $SAA_obj->puid . ",status=all,totalNum=$totalNum,t2_1=" . ($timeMS2 - $timeMS1), "file");

            // (1) all 的内容先更新到数据库 ， 由于第一次拉取比较多，all 要插入比较多的数据，这时候要 通过group insert 来优化
            $allProdSku = array();
            if (!empty($products['all'])) {
                $nowTime = time();
                if ($isFirstTime == 1) {
                    // 1.1 组织数组数据
                    $batchInsertArr = array();
                    foreach ($products['all'] as $listingInfo) {
                        $sellerSku = $listingInfo["SellerSku"];
                        $allProdSku[] = $sellerSku;
                        //print_r($listingInfo);
                        if ($listingInfo["SalePrice"] === "") $listingInfo["SalePrice"] = 0;
                        if (empty($listingInfo["Price"])) $listingInfo["Price"] = 0;

                        if (!empty($listingInfo["SaleStartDate"])) {//dzt20151130 不能用isset ，api 返回 确实isset 但为 "" ,这样下面操作会把当前时间写入字段
                            $listingInfo["SaleStartDate"] = TimeUtil::getTimestampFromISO8601($listingInfo["SaleStartDate"]);
                        } else
                            $listingInfo["SaleStartDate"] = 0;

                        if (!empty($listingInfo["SaleEndDate"])) {//dzt20151130 api传输为"" setAttributes SaleStartDate为"" 但数据库不能为"" 只能为int default 0 ,  上下的SaleStartDate , SaleEndDate 同理
                            $listingInfo["SaleEndDate"] = TimeUtil::getTimestampFromISO8601($listingInfo["SaleEndDate"]);
                        } else
                            $listingInfo["SaleEndDate"] = 0;

                        // SQLHelper::groupInsertToDb 插入拼出来的key值 在第一个data的 key里面获取，如果数组的key数量不一致的话会 报错
                        // 所以这里控制数组所有元素 的key的数量一致
                        if (empty($listingInfo["FulfillmentBySellable"]))
                            $listingInfo["FulfillmentBySellable"] = 0;

                        if (empty($listingInfo["FulfillmentByNonSellable"]))
                            $listingInfo["FulfillmentByNonSellable"] = 0;

                        if (empty($listingInfo["ReservedStock"]))
                            $listingInfo["ReservedStock"] = 0;

                        if (empty($listingInfo["RealTimeStock"]))
                            $listingInfo["RealTimeStock"] = 0;

                        $listingInfo['create_time'] = $nowTime;
                        $listingInfo['update_time'] = $nowTime;
                        $listingInfo['lazada_uid_id'] = $lazadaUid;

                        // dzt20160309 添加这一步防止 api 多返回东西导致 SQLHelper::groupInsertToDb 插入报错
                        $toAddListing = array();
                        $objSchema = LazadaListing::getTableSchema();
                        foreach ($objSchema->columnNames as $column) {
                            if (isset($listingInfo[$column])) {
                                $toAddListing[$column] = $listingInfo[$column];
                            }
                        }

                        if (!empty($toAddListing))
                            $batchInsertArr[] = $toAddListing;
                    }

                    // 1.2 吸取以前教训 在第一次插入前先请空 该店铺下的产品，确保不重复插入
                    LazadaListing::deleteAll(["lazada_uid_id" => $lazadaUid]);

                    // 1.3 批量插入
                    $insertNum = SQLHelper::groupInsertToDb(LazadaListing::tableName(), $batchInsertArr);
                    if ($totalNum != $insertNum) {
                        \Yii::error("_getFilterListingAndSave groupInsertToDb num:$insertNum , but there are total $totalNum to be insert.", "file");
                    }

                } else {// 不是第一次拉取的话，还是按照以前的逻辑 一个个先搜索，再保存，
// 					foreach($products['all'] as $listingInfo){
// 						$sellerSku=$listingInfo["SellerSku"];
// 						$allProdSku[] = $sellerSku;
// 						//print_r($listingInfo);
// 						if ($listingInfo["SalePrice"]==="") $listingInfo["SalePrice"]=0;
// 						if (empty($listingInfo["Price"])) $listingInfo["Price"]=0;

// 						if (!empty($listingInfo["SaleStartDate"])) {//dzt20151130 不能用isset ，api 返回 确实isset 但为 "" ,这样下面操作会把当前时间写入字段
// 							$listingInfo["SaleStartDate"]=TimeUtil::getTimestampFromISO8601($listingInfo["SaleStartDate"]);
// 						}else
// 							$listingInfo["SaleStartDate"]=0;

// 						if (!empty($listingInfo["SaleEndDate"])) {//dzt20151130 api传输为"" setAttributes SaleStartDate为"" 但数据库不能为"" 只能为int default 0 ,  上下的SaleStartDate , SaleEndDate 同理
// 							$listingInfo["SaleEndDate"]=TimeUtil::getTimestampFromISO8601($listingInfo["SaleEndDate"]);
// 						}else
// 							$listingInfo["SaleEndDate"]=0;

// 						$lazadaListingObj = LazadaListing::findOne(["SellerSku"=>$sellerSku,"lazada_uid_id"=>$lazadaUid]);
// 						if (empty($lazadaListingObj)){
// 							$lazadaListingObj=new LazadaListing;
// 							$lazadaListingObj->create_time=$nowTime;
// 							$lazadaListingObj->lazada_uid_id=$lazadaUid;
// 						}

// 						// 这里清除上一次 reject reason之类的错误信息和在我们这里操作的错误信息，如果还有reject reason 后面更新sub status 的时候会更新
// 						$listingInfo['error_message'] = '';

// 						$lazadaListingObj->update_time=$nowTime;
// 						$lazadaListingObj->setAttributes($listingInfo);
// 						$lazadaListingObj->save(false);
// 					}

                    // dzt20160429 客户大批量更新产品是一个个保存始终不是办法，2456 20160304-05 一天更新了5W个产品，更新信息并更新substatus 要10个小时以上
                    // 由于更新substatus已使用批量更新，所以10小时大部分都是用在这里更新信息
                    // 现在通过先全部删除，再全部插入的方式处理。
                    $batchInsertArr = array();
                    $objSchema = LazadaListing::getTableSchema();
                    foreach ($products['all'] as $listingInfo) {
                        $sellerSku = $listingInfo["SellerSku"];
                        $allProdSku[] = $sellerSku;
                        //print_r($listingInfo);
                        if ($listingInfo["SalePrice"] === "") $listingInfo["SalePrice"] = 0;
                        if (empty($listingInfo["Price"])) $listingInfo["Price"] = 0;

                        if (!empty($listingInfo["SaleStartDate"])) {//dzt20151130 不能用isset ，api 返回 确实isset 但为 "" ,这样下面操作会把当前时间写入字段
                            $listingInfo["SaleStartDate"] = TimeUtil::getTimestampFromISO8601($listingInfo["SaleStartDate"]);
                        } else
                            $listingInfo["SaleStartDate"] = 0;

                        if (!empty($listingInfo["SaleEndDate"])) {//dzt20151130 api传输为"" setAttributes SaleStartDate为"" 但数据库不能为"" 只能为int default 0 ,  上下的SaleStartDate , SaleEndDate 同理
                            $listingInfo["SaleEndDate"] = TimeUtil::getTimestampFromISO8601($listingInfo["SaleEndDate"]);
                        } else
                            $listingInfo["SaleEndDate"] = 0;

                        // SQLHelper::groupInsertToDb 插入拼出来的key值 在第一个data的 key里面获取，如果数组的key数量不一致的话会 报错
                        // 所以这里控制数组所有元素 的key的数量一致
                        if (empty($listingInfo["FulfillmentBySellable"]))
                            $listingInfo["FulfillmentBySellable"] = 0;

                        if (empty($listingInfo["FulfillmentByNonSellable"]))
                            $listingInfo["FulfillmentByNonSellable"] = 0;

                        if (empty($listingInfo["ReservedStock"]))
                            $listingInfo["ReservedStock"] = 0;

                        if (empty($listingInfo["RealTimeStock"]))
                            $listingInfo["RealTimeStock"] = 0;

                        $listingInfo['create_time'] = $nowTime;
                        $listingInfo['update_time'] = $nowTime;
                        $listingInfo['lazada_uid_id'] = $lazadaUid;

                        // dzt20160309 添加这一步防止 api 多返回东西导致 SQLHelper::groupInsertToDb 插入报错
                        $toAddListing = array();
                        $toAddColumns = array();
                        foreach ($objSchema->columnNames as $column) {
                            if (!empty($listingInfo[$column])) {
                                $toAddColumns[] = $column;
                                $toAddListing[$column] = $listingInfo[$column];
                            } else if (array_key_exists($column, $listingInfo)) {// null 值
                                $toAddColumns[] = $column;
                                $toAddListing[$column] = '';
                            }
                        }

                        if (!empty($toAddListing))
                            $batchInsertArr[] = $toAddListing;
                    }

                    // 1.2 吸取以前教训 在第一次插入前先请空 该店铺下的产品，确保不重复插入
                    LazadaListing::deleteAll(["lazada_uid_id" => $lazadaUid, "SellerSku" => $allProdSku]);

                    // 1.3 批量插入
                    $insertNum = \Yii::$app->subdb->createCommand()->batchInsert(LazadaListing::tableName(), $toAddColumns, $batchInsertArr)->execute();
                    if ($totalNum != $insertNum) {
                        \Yii::error("_getFilterListingAndSave groupInsertToDb num:$insertNum , but there are total $totalNum to be insert.", "file");
                    }
                }
            }

            $timeMS3 = TimeUtil::getCurrentTimestampMS();
            \Yii::info("_getFilterListingAndSave all saved after autoSyncId:" . $SAA_obj->id . ",puid:" . $SAA_obj->puid . ",status=all,t3_2=" . ($timeMS3 - $timeMS2), "file");

            unset($products["all"]);

            // (2) 非 all的拉取
            $reqParams["filterStatus"] = $statusArr;
// 			$reqParams["page"] = 1;
            $reqParams["offset"] = 0;
            $hasGotAll = false;
            $products = array();
            \Yii::info("_getFilterListingAndSave others status before autoSyncId:" . $SAA_obj->id . " puid:" . $SAA_obj->puid, "file");
            while ($hasGotAll === false) {

                \Yii::info("_getFilterListingAndSave LazadaInterface_Helper::getFilterProducts other status before autoSyncId:" . $SAA_obj->id . " puid:" . $SAA_obj->puid . "reqParams:" . json_encode($reqParams), "file");
                // 目前测试到 us proxy 获取1000个product 要3s ，10000产品 60+基本可以从proxy 获取完。但100+M 传输时间也比较长
                // 但是260s 后仍然获取不到返回结果。
                $timeMSTmp1 = TimeUtil::getCurrentTimestampMS();
                $result = LazadaInterface_Helper::getFilterProducts($config, $reqParams, $isFirstTime);
                $timeMSTmp2 = TimeUtil::getCurrentTimestampMS();
                \Yii::info("_getFilterListingAndSave LazadaInterface_Helper::getFilterProducts other status after t2_t1=" . ($timeMSTmp2 - $timeMSTmp1) . ",autoSyncId:" . $SAA_obj->id . " puid:" . $SAA_obj->puid, "file");
                if ($result["success"] === false) {
                    return array(false, $result["message"], 0);
                }
                if (!isset($result["response"]) or !isset($result["response"]["allProducts"])) {
                    return array(false, "response->products not exist", 0);
                }

// 				$page = $result["response"]["page"];
                $offset = $result["response"]["offset"];
                $tempProducts = $result["response"]["allProducts"];

                foreach ($tempProducts as $tempStatus => $tempStatusProds) {
                    if (!empty($tempStatusProds)) {
                        if (!empty($products[$tempStatus])) {
                            $products[$tempStatus] = array_merge($products[$tempStatus], $tempStatusProds);
                        } else {
                            $products[$tempStatus] = $tempStatusProds;
                        }
                        $num = count($tempStatusProds);
// 						\Yii::info("_getFilterListingAndSave $tempStatus page:$page Num=$num,autoSyncId:".$SAA_obj->id." puid:".$SAA_obj->puid,"file");
                        \Yii::info("_getFilterListingAndSave $tempStatus offset:$offset Num=$num,autoSyncId:" . $SAA_obj->id . " puid:" . $SAA_obj->puid, "file");
                    } else {
                        // 如果返回产品为空，就不再提交改状态到proxy ，然后proxy根据剩余状态的多少自动调整翻页数，目前proxy默认翻5页
                        $reqParams["filterStatus"] = array_merge(array_diff($reqParams["filterStatus"], array($tempStatus)));
                    }
                }

                if ($result["response"]["hasGotAll"] == 1) {
                    $hasGotAll = true;
                }

// 				$reqParams["page"] = $page+1;
                $reqParams["offset"] = $offset;
            }// end of $hasGotAll while all

            $timeMS4 = TimeUtil::getCurrentTimestampMS();
            \Yii::info("_getFilterListingAndSave others status after autoSyncId:" . $SAA_obj->id . " puid:" . $SAA_obj->puid . ",status=others,t4_3=" . ($timeMS4 - $timeMS3), "file");

            $testCount = array();
            $nowTime = time();
            foreach ($statusArr as $status) {
                if (!empty($products[$status])) {
                    $timeMS21 = TimeUtil::getCurrentTimestampMS();
                    $oneStatusNum = count($products[$status]);
                    $toUpSubStatusSkus = array();
                    foreach ($products[$status] as $listingInfo) {
                        $sellerSku = $listingInfo["SellerSku"];
                        //print_r($listingInfo);
                        if ($listingInfo["SalePrice"] === "") $listingInfo["SalePrice"] = 0;
                        if (empty($listingInfo["Price"])) $listingInfo["Price"] = 0;

                        if (!empty($listingInfo["SaleStartDate"])) {//dzt20151130 不能用isset ，api 返回 确实isset 但为 "" ,这样下面操作会把当前时间写入字段
                            $listingInfo["SaleStartDate"] = TimeUtil::getTimestampFromISO8601($listingInfo["SaleStartDate"]);
                        } else
                            $listingInfo["SaleStartDate"] = 0;

                        if (!empty($listingInfo["SaleEndDate"])) {//dzt20151130 api传输为"" setAttributes SaleStartDate为"" 但数据库不能为"" 只能为int default 0 ,  上下的SaleStartDate , SaleEndDate 同理
                            $listingInfo["SaleEndDate"] = TimeUtil::getTimestampFromISO8601($listingInfo["SaleEndDate"]);
                        } else
                            $listingInfo["SaleEndDate"] = 0;

                        //(2.1) 更新状态信息 @todo 以后可能要考虑一个listing 多个substatus更新情况。
                        if ($status == "deleted" || $status == "rejected") {// 由于deleted 不在all 里，而rejected需要更新其他内容，所以这两个状态抽出来处理
                            $lazadaListingObj = LazadaListing::findOne(["SellerSku" => $sellerSku, "lazada_uid_id" => $lazadaUid]);
                            // 由于下面$status=="deleted"的setAttributes报错过 ， 所以这里加上。只是不明白为什么会出现报错。如果find 不到，按道理会进入上面的if $notCreate==true条件里面
                            if (empty($lazadaListingObj)) {
                                $lazadaListingObj = new LazadaListing;
                            }
                            if ($status == "deleted") { // dzt20151225 all get 不了deleted产品
                                $lazadaListingObj->setAttributes($listingInfo);
                            }
                            $lazadaListingObj->sub_status = $status;
                            $lazadaListingObj->update_time = $nowTime;
                            if ($status == "rejected") {
                                if (empty($listingInfo["RejectReason"])) $listingInfo["RejectReason"] = "";
                                $rejectedInfoArr[$sellerSku] = $listingInfo["RejectReason"];
                                $lazadaListingObj->error_message = $listingInfo["RejectReason"];
                            }
                            $lazadaListingObj->save(false);
                        } else {// 其他状态产品只要更新状态信息
                            $notCreate = false;
                            if (!empty($allProdSku)) {// 保底以防$allProdSku 忘了填。。
                                if (!in_array($sellerSku, $allProdSku)) {// $allProdSku 是这次任务 这个账号更新的所有产品，所以不存在串号问题，这样省一个步骤的sql查询
                                    $notCreate = true;
                                }
                            } else {
                                $lazadaListingObj = LazadaListing::findOne(["SellerSku" => $sellerSku, "lazada_uid_id" => $lazadaUid]);
                                if ($lazadaListingObj === null) {
                                    $notCreate = true;
                                }
                            }

                            if ($notCreate) { //正常来说应该不会走入这个分支,get all的时候会插入所有
                                // 测试究竟有多少状态会进入这里或者，根本不会进入这里
                                if (empty($testCount[$status])) {
                                    $testCount[$status] = 1;
                                } else {
                                    $testCount[$status] = $testCount[$status] + 1;
                                }

                                $lazadaListingObj = new LazadaListing;
                                $lazadaListingObj->setAttributes($listingInfo);
                                $lazadaListingObj->sub_status = $status;
                                $lazadaListingObj->create_time = $nowTime;
                                $lazadaListingObj->update_time = $nowTime;
                                $lazadaListingObj->lazada_uid_id = $lazadaUid;

                                if ($status == "rejected") {
                                    $rejectedInfoArr[$sellerSku] = $listingInfo["RejectReason"];
                                    $lazadaListingObj->error_message = $listingInfo["RejectReason"];
                                }

                                $lazadaListingObj->save(false);
                            } else {
                                $toUpSubStatusSkus[] = $sellerSku;// 剩余的通过update All 更新sub_status
                            }
                        }
                    }// end of one status $products foreach

                    // 测试过5500+个产品sku update 生成的sql也大概是70+k 而 mysql sql 最长默认听说是 1m
                    LazadaListing::updateAll(['sub_status' => $status, 'update_time' => $nowTime], ["lazada_uid_id" => $lazadaUid, "SellerSku" => $toUpSubStatusSkus]);

                    if ($isFirstTime == 0) { //不是第一次的话，需要通知
                        if ($status == "rejected") {
                            //TODO
                        }
                    }

                    $timeMS22 = TimeUtil::getCurrentTimestampMS();
                    \Yii::info("_getFilterListingAndSave autoSyncId:" . $SAA_obj->id . ",puid:" . $SAA_obj->puid . ",status=$status,totalNum=$oneStatusNum,t22_21=" . ($timeMS22 - $timeMS21), "file");
                }
            }// end of one $statusArr foreach

            \Yii::info("_getFilterListingAndSave testCount:" . $SAA_obj->id . ",puid:" . $SAA_obj->puid . " " . json_encode($testCount), "file");

            $timeMS5 = TimeUtil::getCurrentTimestampMS();
            \Yii::info("_getFilterListingAndSave finial autoSyncId:" . $SAA_obj->id . ",puid:" . $SAA_obj->puid . ", all several status ,totalNum=$totalNum,t5_1=" . ($timeMS5 - $timeMS1), "file");

            return array(true, "", $totalNum);
        } catch (\Exception $e) {
            \Yii::error("_getFilterListingAndSave autoSyncId:" . $SAA_obj->id . ",puid:" . $SAA_obj->puid . ",Exception:" . print_r($e, true), "file");
            return array(false, "file:" . $e->getFile() . ",line:" . $e->getLine() . ",message:" . $e->getMessage(), 0);
        }
    }


    private static function _getFilterListingAndSaveV2($config, $start_time, $end_time, $SAA_obj, $statusArr, $isFirstTime)
    {
        self::localLog("_getFilterListingAndSaveV2  autoSyncId:" . $SAA_obj->id . ",puid:" . $SAA_obj->puid . ",status:" . implode(',', $statusArr) . ", start:$start_time,end:$end_time start:" . date("Y-m-d H:i:s", $start_time) . "  end:" . date("Y-m-d H:i:s", $end_time));

        $lazadaUid = $SAA_obj->lazada_uid;

        $reqParams = self::setUpdateTimeParam($start_time, $end_time);

        $rejectedInfoArr = array();


        // 由于拉取个数太多 导致重新在ealge 添加分页
        // 由于上w 个产品所有状态一起获取会导致proxy 内存爆了，所以这里 重新加入分页
        // 但由于分页获取所有状态比较麻烦，控制其他状态get 到的产 品必须 all也 get 到，所以这里干脆先单独把all get 完在get其他状态的产品
        $reqParams["filterStatus"] = array("all");
        $reqParams["offset"] = 0;
        $allProdSku = array();
        self::localLog("_getFilterListingAndSave all before autoSyncId:" . $SAA_obj->id . " puid:" . $SAA_obj->puid);
        try {
            $hasGotAll = false;
            $offset = 0;
            //(1)拉取all状态
            while (!$hasGotAll) {
                $products = array();
                $reqParams["offset"] = $offset;
                $rtn = self::getProducts($config, $SAA_obj, $isFirstTime, $reqParams, $products, $hasGotAll);
//                self::localLog("end getProducts, outside,return is ".json_encode($rtn));
                if (is_array($rtn)) {
                    return $rtn;
                } else {
                    $offset += $rtn;
                }
                //插入数据库
//                self::localLog("start update db, outside");
                $allProdSku = array_merge($allProdSku, self::updateToDB($isFirstTime, $products, $lazadaUid, $rtn, $SAA_obj));
            }
            // (2) 非 all的拉取
            $reqParams["filterStatus"] = $statusArr;
            $reqParams["offset"] = 0;
            $hasGotAll = false;
            $products = array();
            self::localLog("_getFilterListingAndSave others status before autoSyncId:" . $SAA_obj->id . " puid:" . $SAA_obj->puid);
            while ($hasGotAll === false) {
                self::localLog("_getFilterListingAndSave LazadaInterface_Helper::getFilterProducts other status before autoSyncId:" . $SAA_obj->id . " puid:" . $SAA_obj->puid . "reqParams:" . json_encode($reqParams));
                // 目前测试到 us proxy 获取1000个product 要3s ，10000产品 60+基本可以从proxy 获取完。但100+M 传输时间也比较长
                // 但是260s 后仍然获取不到返回结果。
                $timeMS3 = TimeUtil::getCurrentTimestampMS();
                $timeMSTmp1 = TimeUtil::getCurrentTimestampMS();
                $result = LazadaInterface_Helper::getFilterProducts($config, $reqParams, $isFirstTime);
                $timeMSTmp2 = TimeUtil::getCurrentTimestampMS();
                self::localLog("_getFilterListingAndSave LazadaInterface_Helper::getFilterProducts other status after t2_t1=" . ($timeMSTmp2 - $timeMSTmp1) . ",autoSyncId:" . $SAA_obj->id . " puid:" . $SAA_obj->puid);
                if ($result["success"] === false) {
                    return array(false, $result["message"], 0);
                }
                if (!isset($result["response"]) or !isset($result["response"]["allProducts"])) {
                    return array(false, "response->products not exist", 0);
                }

// 				$page = $result["response"]["page"];
                $offset = $result["response"]["offset"];
                $tempProducts = $result["response"]["allProducts"];

                foreach ($tempProducts as $tempStatus => $tempStatusProds) {
                    if (!empty($tempStatusProds)) {
                        if (!empty($products[$tempStatus])) {
                            $products[$tempStatus] = array_merge($products[$tempStatus], $tempStatusProds);
                        } else {
                            $products[$tempStatus] = $tempStatusProds;
                        }
                        $num = count($tempStatusProds);
// 						\Yii::info("_getFilterListingAndSave $tempStatus page:$page Num=$num,autoSyncId:".$SAA_obj->id." puid:".$SAA_obj->puid,"file");
                        self::localLog("_getFilterListingAndSave $tempStatus offset:$offset Num=$num,autoSyncId:" . $SAA_obj->id . " puid:" . $SAA_obj->puid);
                    } else {
                        // 如果返回产品为空，就不再提交改状态到proxy ，然后proxy根据剩余状态的多少自动调整翻页数，目前proxy默认翻5页
                        $reqParams["filterStatus"] = array_merge(array_diff($reqParams["filterStatus"], array($tempStatus)));
                    }
                }

                if ($result["response"]["hasGotAll"] == 1) {
                    $hasGotAll = true;
                }

// 				$reqParams["page"] = $page+1;
                $reqParams["offset"] = $offset;
            }// end of $hasGotAll while all

            $timeMS4 = TimeUtil::getCurrentTimestampMS();
            self::localLog("_getFilterListingAndSave others status after autoSyncId:" . $SAA_obj->id . " puid:" . $SAA_obj->puid . ",status=others,t4_3=" . ($timeMS4 - $timeMS3));

            $testCount = array();
            $nowTime = time();
            foreach ($statusArr as $status) {
                if (!empty($products[$status])) {
                    $timeMS21 = TimeUtil::getCurrentTimestampMS();
                    $oneStatusNum = count($products[$status]);
                    $toUpSubStatusSkus = array();
                    foreach ($products[$status] as $listingInfo) {
                        $sellerSku = $listingInfo["SellerSku"];
                        //print_r($listingInfo);
                        if ($listingInfo["SalePrice"] === "") $listingInfo["SalePrice"] = 0;
                        if (empty($listingInfo["Price"])) $listingInfo["Price"] = 0;

                        if (!empty($listingInfo["SaleStartDate"])) {//dzt20151130 不能用isset ，api 返回 确实isset 但为 "" ,这样下面操作会把当前时间写入字段
                            $listingInfo["SaleStartDate"] = TimeUtil::getTimestampFromISO8601($listingInfo["SaleStartDate"]);
                        } else
                            $listingInfo["SaleStartDate"] = 0;

                        if (!empty($listingInfo["SaleEndDate"])) {//dzt20151130 api传输为"" setAttributes SaleStartDate为"" 但数据库不能为"" 只能为int default 0 ,  上下的SaleStartDate , SaleEndDate 同理
                            $listingInfo["SaleEndDate"] = TimeUtil::getTimestampFromISO8601($listingInfo["SaleEndDate"]);
                        } else
                            $listingInfo["SaleEndDate"] = 0;

                        //(2.1) 更新状态信息 @todo 以后可能要考虑一个listing 多个substatus更新情况。
                        if ($status == "deleted" || $status == "rejected") {// 由于deleted 不在all 里，而rejected需要更新其他内容，所以这两个状态抽出来处理
                            $lazadaListingObj = LazadaListing::findOne(["SellerSku" => $sellerSku, "lazada_uid_id" => $lazadaUid]);
                            // 由于下面$status=="deleted"的setAttributes报错过 ， 所以这里加上。只是不明白为什么会出现报错。如果find 不到，按道理会进入上面的if $notCreate==true条件里面
                            if (empty($lazadaListingObj)) {
                                $lazadaListingObj = new LazadaListing;
                            }
                            if ($status == "deleted") { // dzt20151225 all get 不了deleted产品
                                $lazadaListingObj->setAttributes($listingInfo);
                            }
                            $lazadaListingObj->sub_status = $status;
                            $lazadaListingObj->update_time = $nowTime;
                            if ($status == "rejected") {
                                if (empty($listingInfo["RejectReason"])) $listingInfo["RejectReason"] = "";
                                $rejectedInfoArr[$sellerSku] = $listingInfo["RejectReason"];
                                $lazadaListingObj->error_message = $listingInfo["RejectReason"];
                            }
                            $lazadaListingObj->save(false);
                        } else {// 其他状态产品只要更新状态信息
                            $notCreate = false;
                            if (!empty($allProdSku)) {// 保底以防$allProdSku 忘了填。。
                                if (!in_array($sellerSku, $allProdSku)) {// $allProdSku 是这次任务 这个账号更新的所有产品，所以不存在串号问题，这样省一个步骤的sql查询
                                    $notCreate = true;
                                }
                            } else {
                                $lazadaListingObj = LazadaListing::findOne(["SellerSku" => $sellerSku, "lazada_uid_id" => $lazadaUid]);
                                if ($lazadaListingObj === null) {
                                    $notCreate = true;
                                }
                            }

                            if ($notCreate) { //正常来说应该不会走入这个分支,get all的时候会插入所有
                                // 测试究竟有多少状态会进入这里或者，根本不会进入这里
                                if (empty($testCount[$status])) {
                                    $testCount[$status] = 1;
                                } else {
                                    $testCount[$status] = $testCount[$status] + 1;
                                }

                                $lazadaListingObj = new LazadaListing;
                                $lazadaListingObj->setAttributes($listingInfo);
                                $lazadaListingObj->sub_status = $status;
                                $lazadaListingObj->create_time = $nowTime;
                                $lazadaListingObj->update_time = $nowTime;
                                $lazadaListingObj->lazada_uid_id = $lazadaUid;

                                if ($status == "rejected") {
                                    $rejectedInfoArr[$sellerSku] = $listingInfo["RejectReason"];
                                    $lazadaListingObj->error_message = $listingInfo["RejectReason"];
                                }

                                $lazadaListingObj->save(false);
                            } else {
                                $toUpSubStatusSkus[] = $sellerSku;// 剩余的通过update All 更新sub_status
                            }
                        }
                    }// end of one status $products foreach

                    // 测试过5500+个产品sku update 生成的sql也大概是70+k 而 mysql sql 最长默认听说是 1m
                    LazadaListing::updateAll(['sub_status' => $status, 'update_time' => $nowTime], ["lazada_uid_id" => $lazadaUid, "SellerSku" => $toUpSubStatusSkus]);

                    if ($isFirstTime == 0) { //不是第一次的话，需要通知
                        if ($status == "rejected") {
                            //TODO
                        }
                    }

                    $timeMS22 = TimeUtil::getCurrentTimestampMS();
                    \Yii::info("_getFilterListingAndSave autoSyncId:" . $SAA_obj->id . ",puid:" . $SAA_obj->puid . ",status=$status,totalNum=$oneStatusNum,t22_21=" . ($timeMS22 - $timeMS21), "file");
                }
            }// end of one $statusArr foreach

            \Yii::info("_getFilterListingAndSave testCount:" . $SAA_obj->id . ",puid:" . $SAA_obj->puid . " " . json_encode($testCount), "file");

            $timeMS5 = TimeUtil::getCurrentTimestampMS();
            \Yii::info("_getFilterListingAndSave finial autoSyncId:" . $SAA_obj->id . ",puid:" . $SAA_obj->puid . ", all several status ,totalNum=$totalNum,t5_1=" . ($timeMS5 - $timeMS1), "file");

            return array(true, "", $totalNum);
        } catch (\Exception $e) {
            \Yii::error("_getFilterListingAndSave autoSyncId:" . $SAA_obj->id . ",puid:" . $SAA_obj->puid . ",Exception:" . print_r($e, true), "file");
            return array(false, "file:" . $e->getFile() . ",line:" . $e->getLine() . ",message:" . $e->getMessage(), 0);
        }
    }

    private static function localLog($msg, $type = LogType::INFO)
    {
        CommonHelper::log($msg, self::LOG_ID, $type);
    }

    /**
     * @param $start_time
     * @param $end_time
     * @return array
     */
    private static function setUpdateTimeParam($start_time, $end_time)
    {
        if ($start_time == 0) {
            $reqParams = array(
                "UpdatedBefore" => gmdate("Y-m-d\TH:i:s+0000", $end_time)
            );
            return $reqParams;
        } else {
            $reqParams = array(
                "UpdatedAfter" => gmdate("Y-m-d\TH:i:s+0000", $start_time),
                "UpdatedBefore" => gmdate("Y-m-d\TH:i:s+0000", $end_time)

            );
            return $reqParams;
        }
    }

    /**
     * 调用获取产品接口,并做相关判断解析
     * @param $config
     * @param $SAA_obj
     * @param $isFirstTime
     * @param $reqParams
     * @param $products 引用类型,获取产品
     * @return array|int 如果返回值为array则结束流程,返回值表示信息,如果为int,表示返回商品数量
     * 如果返回值为array则结束流程,返回值表示信息,如果为int,表示返回商品数量
     */
    private static function getProducts($config, $SAA_obj, $isFirstTime, $reqParams, &$products, &$hasGotAll)
    {
        $totalNum = 0;
        self::localLog("getProducts config is " . json_encode($config));
        $timeMS1 = TimeUtil::getCurrentTimestampMS();
        while ($hasGotAll === false && $totalNum < LazadaConfig::GET_PRODUCTS_BATCH_SIZE) {
            $result = self::doGetProductsCall($config, $SAA_obj, $isFirstTime, $reqParams);
            if ($result["success"] === false) {
                return array(false, $result["message"], 0);
            }
            if (!isset($result["response"]) or !isset($result["response"]["allProducts"])) {
                return array(false, "response->products not exist", 0);
            }

            $offset = $result["response"]["offset"];
            $tempProducts = $result["response"]["allProducts"];

            if (!empty($tempProducts[self::ALL])) {
                if (!empty($products[self::ALL])) {
                    $products[self::ALL] = array_merge($products[self::ALL], $tempProducts[self::ALL]);
                } else {
                    $products[self::ALL] = $tempProducts[self::ALL];
                }

                $num = count($tempProducts[self::ALL]);
                $totalNum += $num;
                self::localLog("offset:$offset totalnum=$totalNum,autoSyncId:" . $SAA_obj->id . " puid:" . $SAA_obj->puid);
            }

            if ($result["response"]["hasGotAll"] == 1) {
                $hasGotAll = true;
            }
            $reqParams["offset"] = $offset;
        }// end of $hasGotAll while all
        $timeMS2 = TimeUtil::getCurrentTimestampMS();
        self::localLog("end getProducts all autoSyncId:" . $SAA_obj->id . ",puid:" . $SAA_obj->puid . ",status=all,totalNum=$totalNum,t2_1=" . ($timeMS2 - $timeMS1));
        return $totalNum;
    }

    /**
     * 调用接口
     * @param $config
     * @param $SAA_obj
     * @param $isFirstTime
     * @param $reqParams
     * @return mixed
     */
    private static function doGetProductsCall($config, $SAA_obj, $isFirstTime, $reqParams)
    {
        self::localLog("start getFilterProducts and autoSyncId:" . $SAA_obj->id . " puid:" . $SAA_obj->puid . "reqParams:" . json_encode($reqParams));
        // 目前测试到 us proxy 获取1000个product 要3s ，10000产品 60+基本可以从proxy 获取完。但100+M 传输时间也比较长
        // 但是260s 后仍然获取不到返回结果。
        $timeMSTmp1 = TimeUtil::getCurrentTimestampMS();
        $result = LazadaInterface_Helper::getFilterProductsV2($config, $reqParams, $isFirstTime);
        $timeMSTmp2 = TimeUtil::getCurrentTimestampMS();
        self::localLog("end getFilterProducts and time consume t2_t1=" . ($timeMSTmp2 - $timeMSTmp1) . ",autoSyncId:" . $SAA_obj->id . " puid:" . $SAA_obj->puid);
        return $result;
    }

    /**
     * @param $isFirstTime
     * @param $products
     * @param $lazadaUid
     * @param $totalNum
     * @return array
     * @throws \yii\base\InvalidConfigException
     */
    private static function updateToDB($isFirstTime, $products, $lazadaUid, $totalNum, $SAA_obj)
    {
        // (1) all 的内容先更新到数据库 ， 由于第一次拉取比较多，all 要插入比较多的数据，这时候要 通过group insert 来优化
        $timeMS2 = TimeUtil::getCurrentTimestampMS();
        $allProdSku = array();
        self::localLog("start update db and total num is " . $totalNum);
        if (!empty($products['all'])) {
            $nowTime = time();
            if ($isFirstTime == 1) {
                // 1.1 组织数组数据
                $batchInsertArr = array();
                foreach ($products['all'] as $listingInfo) {
                    $sellerSku = $listingInfo["SellerSku"];
                    $allProdSku[] = $sellerSku;
                    $listingInfo = self::assembleLazadaListingInfo($lazadaUid, $listingInfo, $nowTime);
                    // dzt20160309 添加这一步防止 api 多返回东西导致 SQLHelper::groupInsertToDb 插入报错
                    $toAddListing = array();
                    $objSchema = LazadaListing::getTableSchema();
                    foreach ($objSchema->columnNames as $column) {
                        if (isset($listingInfo[$column])) {
                            $toAddListing[$column] = $listingInfo[$column];
                        }
                    }

                    if (!empty($toAddListing))
                        $batchInsertArr[] = $toAddListing;
                }

                // 1.2 吸取以前教训 在第一次插入前先请空 该店铺下的产品，确保不重复插入
                LazadaListing::deleteAll(["lazada_uid_id" => $lazadaUid]);
                // 1.3 批量插入
                $insertNum = SQLHelper::groupInsertToDb(LazadaListing::tableName(), $batchInsertArr);
                if ($totalNum != $insertNum) {
                    self::localLog("groupInsertToDb num:$insertNum , but there are total $totalNum to be insert.", LogType::ERROR);
                }
            } else {
                // 不是第一次拉取的话，还是按照以前的逻辑 一个个先搜索，再保存，
                // dzt20160429 客户大批量更新产品是一个个保存始终不是办法，2456 20160304-05 一天更新了5W个产品，更新信息并更新substatus 要10个小时以上
                // 由于更新substatus已使用批量更新，所以10小时大部分都是用在这里更新信息
                // 现在通过先全部删除，再全部插入的方式处理。
                $batchInsertArr = array();
                $objSchema = LazadaListing::getTableSchema();
                foreach ($products['all'] as $listingInfo) {
                    $sellerSku = $listingInfo["SellerSku"];
                    $allProdSku[] = $sellerSku;
                    $listingInfo = self::assembleLazadaListingInfo($lazadaUid, $listingInfo, $nowTime);

                    // dzt20160309 添加这一步防止 api 多返回东西导致 SQLHelper::groupInsertToDb 插入报错
                    $toAddListing = array();
                    $toAddColumns = array();
                    foreach ($objSchema->columnNames as $column) {
                        if (!empty($listingInfo[$column])) {
                            $toAddColumns[] = $column;
                            $toAddListing[$column] = $listingInfo[$column];
                        } else if (array_key_exists($column, $listingInfo)) {// null 值
                            $toAddColumns[] = $column;
                            $toAddListing[$column] = '';
                        }
                    }

                    if (!empty($toAddListing))
                        $batchInsertArr[] = $toAddListing;
                }

                // 1.2 吸取以前教训 在第一次插入前先请空 该店铺下的产品，确保不重复插入
                LazadaListing::deleteAll(["lazada_uid_id" => $lazadaUid, "SellerSku" => $allProdSku]);
                // 1.3 批量插入
                $insertNum = \Yii::$app->subdb->createCommand()->batchInsert(LazadaListing::tableName(), $toAddColumns, $batchInsertArr)->execute();
                if ($totalNum != $insertNum) {
                    self::localLog("_getFilterListingAndSave groupInsertToDb num:$insertNum , but there are total $totalNum to be insert.", LogType::ERROR);
                }
            }
        }
        $timeMS3 = TimeUtil::getCurrentTimestampMS();
        self::localLog("end update db autoSyncId:" . $SAA_obj->id . ",puid:" . $SAA_obj->puid . ",status=all,t3_2=" . ($timeMS3 - $timeMS2));
        unset($products["all"]);
        return $allProdSku;
    }

    /**
     * @param $lazadaUid
     * @param $listingInfo
     * @param $nowTime
     * @return mixed
     */
    private static function assembleLazadaListingInfo($lazadaUid, $listingInfo, $nowTime)
    {
        if ($listingInfo["SalePrice"] === "") $listingInfo["SalePrice"] = 0;
        if (empty($listingInfo["Price"])) $listingInfo["Price"] = 0;

        if (!empty($listingInfo["SaleStartDate"])) {//dzt20151130 不能用isset ，api 返回 确实isset 但为 "" ,这样下面操作会把当前时间写入字段
            $listingInfo["SaleStartDate"] = TimeUtil::getTimestampFromISO8601($listingInfo["SaleStartDate"]);
        } else
            $listingInfo["SaleStartDate"] = 0;

        if (!empty($listingInfo["SaleEndDate"])) {//dzt20151130 api传输为"" setAttributes SaleStartDate为"" 但数据库不能为"" 只能为int default 0 ,  上下的SaleStartDate , SaleEndDate 同理
            $listingInfo["SaleEndDate"] = TimeUtil::getTimestampFromISO8601($listingInfo["SaleEndDate"]);
        } else
            $listingInfo["SaleEndDate"] = 0;

        // SQLHelper::groupInsertToDb 插入拼出来的key值 在第一个data的 key里面获取，如果数组的key数量不一致的话会 报错
        // 所以这里控制数组所有元素 的key的数量一致
        if (empty($listingInfo["FulfillmentBySellable"]))
            $listingInfo["FulfillmentBySellable"] = 0;

        if (empty($listingInfo["FulfillmentByNonSellable"]))
            $listingInfo["FulfillmentByNonSellable"] = 0;

        if (empty($listingInfo["ReservedStock"]))
            $listingInfo["ReservedStock"] = 0;

        if (empty($listingInfo["RealTimeStock"]))
            $listingInfo["RealTimeStock"] = 0;

        $listingInfo['create_time'] = $nowTime;
        $listingInfo['update_time'] = $nowTime;
        $listingInfo['lazada_uid_id'] = $lazadaUid;
        return $listingInfo;
    }
}


?>