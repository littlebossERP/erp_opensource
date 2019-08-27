<?php
/**
 * Created by PhpStorm.
 * User: vizewang
 * Date: 16/6/22
 * Time: 下午1:35
 */

namespace eagle\modules\comment\helpers;

use common\api\aliexpressinterface\AliexpressInterface_Auth;
use console\helpers\CommonHelper;
use console\helpers\LogType;
use eagle\components\OpenApi;
use eagle\modules\comment\dal_mongo\CommentConstances;
use eagle\modules\comment\dal_mongo\CommentErrorLog;
use eagle\modules\comment\dal_mongo\CommentLog;
use eagle\modules\comment\dal_mongo\CommentManagerFactory;
use eagle\modules\comment\dal_mongo\QueueAliexpressComment;
use eagle\modules\comment\dal_mongo\QueueAliexpressCommentLog;
use eagle\modules\comment\dal_mongo\QueueCommentStatus;
use eagle\modules\order\models\OdOrder;

/**
 * Class CommentHelperV2
 * @package eagle\modules\comment\helpers
 * Lazada 好评帮助,主要对之前版本进行重构和改进
 * @author vizewang
 * @version 2.0
 * @since 2.0
 */
class CommentHelperV2
{

    const LOG_ID = "eagle-modules-comment-helpers-commentHelperV2";
    public static $platform = ['aliexpress']; // 系统开通的好评平台

    /**
     * 调用各平台好评API
     */
    static private function callApi($apiName, $param)
    {
        self::localLog('-- call api :' . $apiName);
        self::localLog(json_encode($param));
        try {
            $run = OpenApi::run($apiName, $param);
            $result = json_decode($run)->response;
        } catch (\Exception $e) {
            self::localLog('!!!!Caught Exception for call api :' . $apiName . " param is " . json_encode($param) . " error is " . $e->getMessage());
            $result = new \stdClass();
            $result->code = "402";
        }
        return $result;
    }

    static private function localLog($msg, $type = LogType::INFO)
    {
        CommonHelper::log($msg, self::LOG_ID, $type);
    }

    /**
     * 根据uid和平台信息获取好评规则
     * @param $uid
     * @param string $platform
     * @return array
     */
    public static function getRulesByUidAndPlatform($uid, $platform = "aliexpress")
    {
//        self::localLog("getRulesByUidAndPlatform param is uid=" . $uid . " platform=" . $platform);
        $cursor = CommentManagerFactory::getManagerByStatic(CommentConstances::COMMENT_RULE)->find(array("isUse" => 1, "platform" => $platform, "uid" => $uid));
        return iterator_to_array($cursor);
    }


    /**
     * 正真拉取自动好评订单操作
     */
    public function doPullCommentOrders()
    {

        foreach (self::$platform as $platform) {    // 平台遍历
            CommonHelper::switchSubDb($platform, function ($puid) use ($platform) { // 切子库
                // 用户开启的所有规则
                $rules = CommentHelperV2::getRulesByUidAndPlatform($puid, $platform);
//                $this->localLog("start merge rules and rules is " . json_encode($rules));
                CommentHelperV2::rulesReform($rules);
//                $this->localLog("end merge rules and rules is " . json_encode($rules));

                foreach ($rules as $rule) {
                    $orders = CommentHelperV2::matchOrdersFromRules($rule);
                    // 进行好评！
                    foreach ($orders as $order) {
                        CommentHelperV2::evaluation($order, $rule['score'], $rule['content'], $rule['_id']->{'$id'}, $puid);
                    }
                }
            });
        }
    }

    /**
     * 调用速卖通接口对队列中的订单进行评价,评价成功后删除队列中的订单,同时往队列日志表中插入日志。
     * @return bool
     */
    public function doComment()
    {
        $notPermitUIDQut = 0;
        while (true) {
            $notPraised = CommentManagerFactory::getManagerByStatic(CommentConstances::QUEUE_ALIEXPRESS_COMMENT)->find()->limit(200);
            $notPraised = iterator_to_array($notPraised);
            $length = count($notPraised);

            self::localLog("get data from QUEUE_ALIEXPRESS_COMMENT size is " . $length);
            $count = $length;
            $praisedIds = array();
            foreach ($notPraised as $row) {
                $mgid = $row['_id'];
                $_id = $mgid->{'$id'};
                $a = AliexpressInterface_Auth::checkTokenMoreDetail($row['sellerLoginId']);
                if ($a == 1) {
                    $rlt = CommentHelperV2::saveFeedback($row['sellerLoginId'], $row['orderId'], $row['score'], $row['feedbackContent']);
                    if ($rlt->code == 1) {
                        continue;
                    }
                    self::localLog("end praise!!! id is " . $_id . " and orderSourceOrderId is " . $row['orderId'] . " left " . $count-- . " result is " . json_encode($rlt));
                    if ($rlt->code == 0) {
                        $success = 'true';
                        CommentManagerFactory::getManagerByStatic(CommentConstances::COMMENT_LOG)->findAndModify(array('orderSourceOrderId' => $row['orderId']), array('$set' => array('isSuccess' => CommentStatus::SUCCESS)));
                    } else {
                        $success = 'false';
                        $this->errorOrderDigest($row, $rlt->code, $rlt->msg);
                    }
                    $rlt = CommentHelperV2::pushToQueueAliexpressCommentLog($row, $rlt->code, $rlt->msg, $success);
                    if ($rlt['ok'] == 1) {
                        array_push($praisedIds, $mgid);
                    }
                } else {
                    $mgid = $row['_id'];
                    array_push($praisedIds, $mgid);
                    self::localLog($row['sellerLoginId'] . " auth failed");
                    $errorMsg = "";
                    if ($a == 0) {
                        $errorMsg = "token out of date";
                    } else if ($a == 2) {
                        $errorMsg = "sellerloginid is not exist";
                    }
                    $this->errorOrderDigest($row, $a, $errorMsg);
                }
            }
            if ($length == $notPermitUIDQut) {
                self::localLog("end this batch");
                return false;
            }
            $notPermitUIDQut = 0;
            if (!empty($praisedIds)) {
                $rlt = CommentManagerFactory::getManagerByStatic(CommentConstances::QUEUE_ALIEXPRESS_COMMENT)->remove(array('_id' => array('$in' => $praisedIds)));
                if ($rlt['n'] > 0) {
                    self::localLog("comment success and delete from queue_aliexpress_praise for orders " . json_encode($praisedIds));
                } else {
                    self::localLog("failed delete from queue_aliexpress_praise for orders " . json_encode($praisedIds));
                }
            }
        }
    }


    /**
     * 将评论出现错误的订单状态更新回od_order_v2
     */
    public function doRefreshStatus2UserDb()
    {
        while (true) {
            $queueCommentStatusC = CommentManagerFactory::getManagerByStatic(CommentConstances::QUEUE_COMMENT_STATUS)->find()->limit(10000);
            $queueCommentStatusA = iterator_to_array($queueCommentStatusC);
            $queueLength = count($queueCommentStatusA);
            if ($queueLength == 0) {
                self::localLog("end this batch");
                break;
            }
            $uidStatusMap = array();
            $refreshedIds = array();
            foreach ($queueCommentStatusA as $queueCommentStatusE) {
                $mgid = $queueCommentStatusE['_id'];
                $uidStatusMap[$queueCommentStatusE['uid']][$queueCommentStatusE['orderId']] = $queueCommentStatusE['status'];
                $refreshedIds[] = $mgid;
            }
            foreach ($uidStatusMap as $uid => $statusA) {
                if (true) {
                     
                    $orderIds = array();
                    foreach ($statusA as $orderId => $status) {
                        $orderIds[] = $orderId;
                    }
                    $order_obj = OdOrder::find();
//                    var_dump($orderIds);
                    $order_obj->where(['IN', 'order_source_order_id', $orderIds]);
                    foreach ($order_obj->batch(1000) as $ordersNeedRefresh) {
                        foreach ($ordersNeedRefresh as $ordersNeedRefreshE) {
                            $ordersNeedRefreshE->is_comment_status = $statusA[$ordersNeedRefreshE->order_source_order_id];
                            $ordersNeedRefreshE->save();
                        }
                        unset($ordersNeedRefresh);
                    }
                }  
            }
//            die();
            if (!empty($refreshedIds)) {
                self::localLog('end refresh to user db and data is ' . json_encode($refreshedIds));
                CommentManagerFactory::getManagerByStatic(CommentConstances::QUEUE_COMMENT_STATUS)->remove(array('_id' => array('$in' => $refreshedIds)));
            }

        }

    }
    
    /**
     * 处理对列中错误订单
     * @param $row queue_aliexpress_comment一条记录信息
     * @param $error 错误代码
     * @return array
     */
    private static function errorOrderDigest(array $row, $errorCode = 1000, $errorMsg = "")
    {
        $commentErrorLog = new CommentErrorLog();
        $orderId = $row['orderId'];
        $commentErrorLog->orderId = $orderId;
        list($outMsg, $internalMsg) = self::errorMsgIdentify($row['sellerLoginId'], $errorCode, $errorMsg);
        $commentErrorLog->msg = $internalMsg;
        $commentErrorLog->createTime = time();
        $uid = 0;
        $status = CommentStatus::NOT_RETRY_FAILED;
        if (isset($row['uid'])) {
            $uid = $row['uid'];
            $commentErrorLog->uid = $uid;
        }
        if ($errorCode == 2301) {
            $status = CommentStatus::RETRY_FAILED;
        } else if ($errorCode == 2) {
            $status = CommentStatus::SELLER_LOGIN_ID_NOT_EXIST;
        }
        CommentHelperV2::pushToQueueCommentStatus($orderId, $uid, $status);
        CommentManagerFactory::getManagerByStatic(CommentConstances::COMMENT_ERROR_LOG)->insert($commentErrorLog);
        CommentManagerFactory::getManagerByStatic(CommentConstances::COMMENT_LOG)->findAndModify(array('orderSourceOrderId' => $row['orderId']), array('$set' => array('isSuccess' => CommentStatus::NOT_RETRY_FAILED, 'errorMsg' => $outMsg)));

    }

    /**
     * 区分用户可以看到和屏蔽的错误信息
     * @param $sellerLoginId
     * @param $errorCode
     * @param string $errorMsg
     * @return array
     */
    private static function errorMsgIdentify($sellerLoginId, $errorCode, $errorMsg = "")
    {
        $internalMsg = $errorMsg . ". 1.sellerLoginId is " . $sellerLoginId . "; 2.error code is " . $errorCode;;
        if ($errorCode == 2 || $errorCode == 0) {
            $outMsg = "";
        } else {
            $outMsg = $errorMsg;
        }
        return array($outMsg, $internalMsg);
    }

    /**
     * @param $rule 好评规则
     * @return array|\yii\db\ActiveRecord[] 符合好评规则的所有订单
     */
    public static function matchOrdersFromRules($rule)
    {

        $step = 100;
        $orders = [];
        $sellloginids = $rule['sellerIdList'];
        $nonCmOrders = [];
        foreach ($sellloginids as $sellloginid) {
            $nonCmOrder = CommentHelper::aliexpressNonHaopingOrders($sellloginid);
//            self::localLog("sellloginid is " . $sellloginid . " and data is " . json_encode($nonCmOrder));
            if (is_array($nonCmOrder))
                $nonCmOrders = array_merge($nonCmOrders, $nonCmOrder);
        }

        $nonCmOrdersLength = count($nonCmOrders);
        self::localLog("not comment order num is " . $nonCmOrdersLength);
        for ($i = 0; $i < $nonCmOrdersLength; $i = $i + $step) {
            $nonCmOrderSlice = array_slice($nonCmOrders, $i, $step);
            $odSlice = self::matchRuleFromOdOrder($rule, $nonCmOrderSlice);
            $orders = array_merge($orders, $odSlice);
        }
        self::localLog("ready to comment order quanty is " . count($orders));
        return $orders;
    }

    /**
     * 从od_order_v2表中根据规则读取数据
     * @param $rule 规则
     * @param $nonCmOrders 没有好评的订单
     * @return \yii\db\ActiveQuery
     */
    private static function matchRuleFromOdOrder($rule, $nonCmOrders)
    {
        $odQuery = OdOrder::find();
        $odQuery->where(['IN', 'order_source_order_id', $nonCmOrders])
            ->andWhere(['IN', 'is_comment_status', array(CommentStatus::NOT_COMMENT, CommentStatus::NOT_RETRY_FAILED, CommentStatus::RETRY_FAILED)]);
        if (!(count($rule['countryList']) == CommentConstances::TOTAL_COUNTRY_QUANTITY)) {
            $odQuery->andWhere(['IN', 'consignee_country_code', $rule['countryList']]);
        }

        if (!$rule['isCommentIssue']) {
            $odQuery->andWhere(['IN', 'issuestatus', CommentConstances::$NEED_COMMENT_ISSUESTATUS]);
        };
//        self::localLog('-- Match Orders SQL2: ');
//        self::localLog($odQuery->createCommand()->getRawSql());
        return $odQuery->all();
    }

    /**
     * 对订单进行好评,本质上是将订单推送到队列
     * @param $order
     * @param $rank
     * @param $message
     * @param $ruleId
     * @param $uid
     * @return array|void
     */
    public static function evaluation($order, $rank, $message, $ruleId, $uid)
    {

        $rtn = [];
        if (!is_object($order)) {
            $order = OdOrder::find()
                ->where([
                    'order_source_order_id' => $order
                ])
                ->one();
        }

        if (self::checkCommentLog($order->order_source_order_id)) {
            self::localLog("end evaluation order " . $order->order_source_order_id . " it has been evaluated ");
            return;
        }
//        self::localLog("start evaluation order " . $order->order_source_order_id);
        $rlt = self::pushReadyToCommentOrderToQueue($order, $rank, $message, $uid);
        if ($rlt['ok'] == 1) {
            // 写入日志
            $rlt = self::logCommentingCommentLog($order, $message, $ruleId, $uid, CommentStatus::COMMENTING);
            if ($rlt["ok"] == 1) {
                self::changeOrdersToCommented($order->order_source_order_id);
            }
        } else {
            $rtn['success'] = false;
        }
        return $rtn;
    }

    /**
     * 回写订单评价状态为成功,本质上是成功插入队列
     * @param $order_id
     * @param string $field
     * @param int $status
     * @return bool
     */
    public static function changeOrdersToCommented($order_id, $field = 'is_comment_status', $status = CommentStatus::SUCCESS)
    {
        $order_obj = OdOrder::find()
            ->where([
                'order_source_order_id' => $order_id
            ])->one();
//        var_dump($order_obj);
        if (isset($order_obj)) {
            $order_obj->$field = $status;
            if ($order_obj->save()) {
                self::localLog('-- change order_v2 status to ' . $status . '! and order_source_order_id is ' . $order_id);
            }
            return true;
        }
        return false;

    }

    /**
     * @param $orderSourceOrderId 查看日志是否进行过好评
     * @return bool 如果已经进行好评并且成功,则返回true,否则false
     */
    private static function checkCommentLog($orderSourceOrderId)
    {
        $comment_log = CommentManagerFactory::getManagerByStatic(CommentConstances::COMMENT_LOG)->find(array('orderSourceOrderId' => $orderSourceOrderId));
        $count = $comment_log->count();
        if ($count > 0) {
            $comment_log = iterator_to_array($comment_log);
            foreach ($comment_log as $value) {
                if ($value['isSuccess'] == 1) {
                    return true;
                }
            }
            CommentManagerFactory::getManagerByStatic(CommentConstances::COMMENT_LOG)->remove(array('orderSourceOrderId' => $orderSourceOrderId));
        }
        return false;
    }

    /**
     * 将需要好评的订单信息推送到queue_aliexpress_comment队列中
     * @param $order
     * @param $rank
     * @param $message
     * @param $uid
     * @return mixed
     */
    private static function pushReadyToCommentOrderToQueue($order, $rank, $message, $uid)
    {
        $queueAliexpressComment = new QueueAliexpressComment();
        $queueAliexpressComment->orderId = $order->order_source_order_id;
        $queueAliexpressComment->score = $rank;
        $queueAliexpressComment->feedbackContent = $message;
        $queueAliexpressComment->sellerLoginId = $order->selleruserid;
        $queueAliexpressComment->uid = $uid;
        $queueAliexpressComment->updateTime = time();
        $rlt = CommentManagerFactory::getManagerByStatic(CommentConstances::QUEUE_ALIEXPRESS_COMMENT)->insert($queueAliexpressComment);
        self::localLog("end evaluation order " . $order->order_source_order_id . " result is " . json_encode($rlt));
        return $rlt;
    }

    /**
     * 写入好评记录表,评价状态为好评中
     * @param $order
     * @param $message
     * @param $ruleId
     * @param $uid
     * @return mixed
     */
    private static function logCommentingCommentLog($order, $message, $ruleId, $uid, $isSuccess, $errorMsg = "")
    {
        $cl = new CommentLog();
        $cl->uid = $uid;
        $cl->orderId = $order->order_id;
        $cl->orderSourceOrderId = $order->order_source_order_id;
        $cl->sellerUserId = $order->selleruserid;
        $cl->platform = $order->order_source;
        $cl->sourceBuyerUserId = $order->source_buyer_user_id;
        $cl->subTotal = floatval($order->grand_total);
        $cl->currency = $order->currency;
        $cl->paidTime = $order->paid_time;
        $cl->content = $message;
        $cl->isSuccess = $isSuccess;
        $cl->errorMsg = $errorMsg;
        $cl->createTime = time();
        $cl->orderSourceCreateTime = $order->order_source_create_time;
        $cl->ruleId = $ruleId;
        $rlt = CommentManagerFactory::getManagerByStatic(CommentConstances::COMMENT_LOG)->insert($cl);
        return $rlt;
    }

    /**
     * 去除包含所有国家规则的中与其他规则重复的国家,
     * @param array $rules
     */
    public static function rulesReform(&$rules = [])
    {
        foreach ($rules as $key => $val) {
            if (is_array($val)) {
                $countryCount = count($val['countryList']);
                if ($countryCount == CommentConstances::TOTAL_COUNTRY_QUANTITY) {
                    $allCountryRule[] = $val;
                    unset($rules[$key]);
                }
            }
        }
        if (isset($allCountryRule)) {
            foreach ($allCountryRule as $val) {
                $sell = $val['sellerIdList'];
                foreach ($sell as $sellVal) {
                    $sellCountryMap[$sellVal] = $val;
                    $sellCountryMap[$sellVal]['sellerIdList'] = array($sellVal);
                }
            }
        }

        if (isset($sellCountryMap)) {
            foreach ($rules as $ruleVal) {
                $ruleSell = $ruleVal['sellerIdList'];
                foreach ($ruleSell as $rsVal) {
                    if (isset($sellCountryMap[$rsVal])) {
                        $sellCountryMap[$rsVal]['countryList'] = array_diff($sellCountryMap[$rsVal]['countryList'], $ruleVal['countryList']);
                    }
                }
            }
            foreach ($sellCountryMap as $key => $scmVal2) {
                $rules[] = $scmVal2;
                var_dump(count($rules));
            }
        }
    }

    public static function syncEvaluation($order, $rank, $message, $uid, $responseDetail = false)
    {
        if (is_array($order)) {
            $result = [];
            foreach ($order as $od_id) {
                $result[] = self::syncEvaluation($od_id, $rank, $message, $uid, $responseDetail);
            }
            return $result;
        } else {
            $rtn = [];
            $order = OdOrder::find()
                ->where([
                    'order_source_order_id' => $order
                ])
                ->one();
            // 如果message是id则获取内容
            if (self::checkCommentLog($order->order_source_order_id)) {
                self::localLog("end evaluation order " . $order->order_source_order_id . " it has been evaluated ");
                return;
            }
            $result = self::saveFeedback($order->selleruserid, $order->order_source_order_id, $rank, $message);
            self::localLog("comment result is " . json_encode($result));
            // 写入日志
            if ($result->code) {
                $isSuccess = CommentStatus::NOT_RETRY_FAILED;
                self::pushToQueueCommentStatus($order->order_source_order_id, $uid);
            } else {
                $isSuccess = CommentStatus::SUCCESS;
            }
            $errorMsg = $result->code ? $result->msg : '';
            try {
                self::logCommentingCommentLog($order, $message, "", $uid, $isSuccess, $errorMsg);
                $rtn['success'] = true;
                self::localLog("comment_log insert successfully");
            } catch (\MongoException $e) {
                $rtn['success'] = false;
                self::localLog(json_encode($e), LogType::ERROR);
            }
            return $rtn;
        }

    }

    /**
     * @param $order
     * @param $uid
     */
    public static function pushToQueueCommentStatus($orderSourceOrderId, $uid, $status = CommentStatus::NOT_RETRY_FAILED)
    {
        $queueCommentStatus = new QueueCommentStatus();
        $queueCommentStatus->orderId = $orderSourceOrderId;
        $queueCommentStatus->uid = $uid;
        $queueCommentStatus->status = $status;
        CommentManagerFactory::getManagerByStatic(CommentConstances::QUEUE_COMMENT_STATUS)->insert($queueCommentStatus);
    }


    public static function pushToQueueAliexpressCommentLog($row, $errorCode, $errorMsg, $success = true)
    {
        $qacl = new QueueAliexpressCommentLog();
        $qacl->orderId = $row['orderId'];
        $qacl->score = $row['score'];
        $qacl->feedbackContent = $row['feedbackContent'];
        $qacl->sellerLoginId = $row['sellerLoginId'];
        $qacl->errorCode = $errorCode;
        $qacl->errorMessage = $errorMsg;
        $qacl->success = $success;
        $qacl->updateTime = time();
        $rlt = CommentManagerFactory::getManagerByStatic(CommentConstances::QUEUE_ALIEXPRESS_COMMENT_LOG)->insert($qacl);
        return $rlt;
    }

    /**
     * @param $order
     * @param $rank
     * @param $message
     * @return array
     */
    public static function saveFeedback($sellerId, $orderId, $rank, $message = "")
    {
        $param = [
            'seller_id' => $sellerId,
            'order_id' => $orderId,
            'score' => $rank
        ];
        if (!isset($message) || $message != '') {
            $param['content'] = $message;
        }
        $result = self::callApi('eagle.modules.order.openapi.OrderApi.saveEvaluation', $param);
        return $result;
    }
}