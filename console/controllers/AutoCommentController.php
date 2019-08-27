<?php
/**
 * Created by PhpStorm.
 * User: vizewang
 * Date: 16/5/19
 * Time: 下午3:27
 */

namespace console\controllers;


use console\helpers\CommonHelper;
use eagle\models\AliexpressOrder;
use eagle\modules\assistant\models\SaasAliexpressUser;
use eagle\modules\comment\dal_mongo\CommentConstances;
use eagle\modules\comment\helpers\CommentHelper;
use eagle\modules\comment\helpers\CommentStatus;
use eagle\modules\order\models\OdOrder;
use yii\base\Controller;


class AutoCommentController extends Controller
{
    public static $platform = ['aliexpress']; // 系统开通的好评平台
    const LOG_ID = "console-controllers-AutoCommentController";
    private static $pullCommentOrdersJobVersion = 0;
    private static $jobUpdateVersion = "2.0.0";
    private static $commentJobVersion = 0;
    private static $controllerId = "auto-comment";
    private static $fullClass = "eagle.modules.comment.helpers.CommentHelperV2";
    private static $refreshStatus2UserDbJobVersion = 0;

    private function localLog($msg)
    {
        CommonHelper::log($msg, self::LOG_ID);
    }

    /**
     * 获取所有用户需要好评订单,根据好评规则和好评模版放入队列等待执行
     */
    public function actionPullCommentOrders()
    {
        CommonHelper::startJob(self::$fullClass . ".doPullCommentOrders", 10, "pull-comment-orders", "1.0", self::$pullCommentOrdersJobVersion, self::$controllerId);
    }

    /**
     * 对队列中的订单进行好评
     */
    public function actionComment()
    {
        CommonHelper::startJob(self::$fullClass . ".doComment", 10, "comment", "1.0", self::$commentJobVersion, self::$controllerId);
    }

    /**
     * 将错误信息同步到用户表
     */
    public function actionRefreshStatus2UserDb()
    {
        CommonHelper::startJob(self::$fullClass . ".doRefreshStatus2UserDb", 10, "comment", "1.0", self::$refreshStatus2UserDbJobVersion, self::$controllerId);
    }


    /**
     * 速卖通订单评价状态重置
     */
    public function actionResetCommentStatusOfOdOrderV2()
    {
        $start = time();
        $aliexpressUsers = SaasAliexpressUser::find()
            ->select(['sellerloginid', 'uid'])
            ->all();
        $uidSLIdMap = [];
        self::localLog("aliexpressUsers size is " . count($aliexpressUsers));
        foreach ($aliexpressUsers as $aliexpressUser) {
            $uidSLIdMap[$aliexpressUser->uid][] = $aliexpressUser->sellerloginid;
        }
        $uidLength = count($uidSLIdMap);
        self::localLog("aliexpressUsers uid size is" . $uidLength);
        foreach ($uidSLIdMap as $uid => $sellerLoginIdArr) {
            if (true) {
                self::localLog('-- changeUserDataBase success: ' . $uid . " left " . $uidLength--);
                foreach ($sellerLoginIdArr as $sellerLogId) {
                    $nonCmOrders = CommentHelper::aliexpressNonHaopingOrders($sellerLogId );
                    self::localLog("nonCmOrders size is " . count($nonCmOrders));
                    if (isset($nonCmOrders)) {
                        $order_obj = OdOrder::find();
                        $order_obj->where(['IN', 'order_source_order_id', $nonCmOrders])->andWhere(['IN', 'is_comment_status', array(1, 2, 3)]);
                        $ordersNeedUpdate = $order_obj->all();
                        self::localLog("ordersNeedUpdate size is " . count($ordersNeedUpdate));
                        foreach ($ordersNeedUpdate as $orderNeedUpdate) {
                            $orderNeedUpdate->is_comment_status = CommentStatus::RETRY_FAILED;
                            $orderNeedUpdate->save();
                        }
                    }
                }
            } else {
                self::localLog('!! changeUserDataBase fail: ' . $uid);
            }
        }
        $end = time();
        self::localLog("time cost is " . ($end - $start));
    }

    /**
     * 将纠纷状态变为最新版本
     */
    public function actionResetIssuestatus()
    {
        $start = time();
        $aliexpressUsers = SaasAliexpressUser::find()
            ->select(['sellerloginid', 'uid'])
            ->all();
        $uidSLIdMap = [];
        $taskSize = count($aliexpressUsers);
        self::localLog("aliexpressUsers size is " . $taskSize);
        foreach ($aliexpressUsers as $aliexpressUser) {
            $uidSLIdMap[$aliexpressUser->uid][] = $aliexpressUser->sellerloginid;
        }
        foreach ($uidSLIdMap as $uid => $sellerLoginIdArr) {
            if (true) {
                self::localLog('-- changeUserDataBase success: ' . $uid . ' left ' . $taskSize);
                $taskSize--;
                $order_obj = OdOrder::find();
                $order_obj->where(['order_source' => CommentConstances::ALIEXPRESS])->andWhere(['IN', 'issuestatus', array("0", "1", "")]);
//                $ordersNeedUpdate = $order_obj->all();
                $batchCount = 0;
                foreach ($order_obj->batch(1000) as $ordersNeedUpdate) {
                    self::localLog("update " . $batchCount . "'th batch size is " . count($ordersNeedUpdate));
                    $batchCount++;
                    foreach ($ordersNeedUpdate as $orderNeedUpdate) {
                        $rawAliOrder = AliexpressOrder::find()->where(['id' => $orderNeedUpdate->order_source_order_id])->one();
                        $orderNeedUpdate->issuestatus = $rawAliOrder['issuestatus'];
                        $orderNeedUpdate->save();
                    }
                    unset($ordersNeedUpdate);
                }
            } else {
                self::localLog('!! changeUserDataBase fail: ' . $uid);
            }
        }
        $end = time();
        self::localLog("time cost is " . ($end - $start));
    }


}