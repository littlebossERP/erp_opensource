<?php namespace eagle\modules\comment\helpers;

use common\helpers\Helper_Array;
use console\controllers\CommentHelperController as console;
use eagle\components\OpenApi;
use eagle\models\AliexpressOrder;
use eagle\models\comment\CmCommentEnable;
use eagle\models\comment\CmCommentLog;
use eagle\models\comment\CmCommentTemplate;
use eagle\models\SaasAliexpressUser;
use eagle\modules\comment\config\CommentConfig;
use eagle\modules\comment\dal_mongo\CommentConstances;
use eagle\modules\comment\dal_mongo\CommentLog;
use eagle\modules\comment\dal_mongo\CommentManagerFactory;
use eagle\modules\comment\dal_mongo\QueueAliexpressComment;
use eagle\modules\comment\models\CmCommentRule;
use eagle\modules\order\models\OdOrder;

// use eagle\models\comment\CmCommentRule;


/**
 * 好评助手自动运行脚本
 */
class CommentHelper
{
    const STATUS_SUCCESS = 1;
    const STATUS_FAIL = 0;
    const STATUS_WAIT = 2;
    const LOG_ID = "eagle-modules-comment-helpers-commentHelper";
    static $countrySet;


    static private function localLog($msg)
    {
        console::log($msg, self::LOG_ID);
    }

    // 设置是否开启接口调用
    static private function getEnv()
    {
        return CommentConfig::API_OPEN_STATUS;
    }

    /*******************   私有方法，调用各平台好评API   ***********************/

    // 调用api并保存日志
    static private function callApi($apiName, $param)
    {
        self::localLog('-- call api :' . $apiName);
        self::localLog(json_encode($param));
        try {
            $run = OpenApi::run($apiName, $param);
            $result = json_decode($run)->response;
//            self::localLog(json_encode($result));
        } catch (\Exception $e) {
            self::localLog('!!!!Caught Exception for call api :' . $apiName . " param is " . json_encode($param) . " error is " . $e->getMessage());
            $result = new \stdClass();
            $result->code = "402";
        }
        // $run = '{"response":{"code":"500","msg":"HSFTimeOutException-Timeout waiting for task. ERR-CODE: [HSF-0002], Type: [\u4e1a\u52a1\u95ee\u9898], More: [http:\/\/console.taobao.net\/help\/HSF-0002]\n\u63cf\u8ff0\u4fe1\u606f\uff1a5000","data":[]}}';
        // console::log($result->code?'!! fail':'-- success');
        return $result;
    }

    static private function callApiV2($apiName, $param)
    {
        self::localLog('-- call api :' . $apiName);
        self::localLog(json_encode($param));
        $run = OpenApi::run($apiName, $param);
        // $run = '{"response":{"code":"500","msg":"HSFTimeOutException-Timeout waiting for task. ERR-CODE: [HSF-0002], Type: [\u4e1a\u52a1\u95ee\u9898], More: [http:\/\/console.taobao.net\/help\/HSF-0002]\n\u63cf\u8ff0\u4fe1\u606f\uff1a5000","data":[]}}';
        $result = $run['response'];
        self::localLog($run);
        // console::log($result->code?'!! fail':'-- success');
        return $result;
    }
    /* -----------------------------------------------  */

    // 发送速卖通好评
    static protected function aliexpressApi($param)
    {
        $response = self::callApi('eagle.modules.order.openapi.orderApi.insertOrderToQueue', $param);
        if ($response->msg !== 'true') {
            $response->code = 500;
        }
        return $response;
    }

    /**
     *  查询速卖通好评
     * @return
     * code 0 success
     * code 0 data ['success'] :'true','false' 错误
     * code 1 fail 没有进入队列
     */
    static protected function aliexpressResult($param)
    {
        $response = self::callApi('eagle.modules.order.openapi.orderApi.getPraiseInfo', $param);
        $rtn = new \StdClass;
        if ($response->code) { // 未进入队列
            $rtn->code = -1;
            $rtn->msg = '未找到此订单的好评信息';
        } elseif ($response->data->success === 'true') {
            $rtn->code = 1; // 成功
            $rtn->msg = '发送成功';
        } else {
            $rtn->code = 0; // 失败
            if (isset($response->data->errorMessage)) {
                $rtn->msg = '发送失败：' . $response->data->errorMessage;
            }
        }
        return $rtn;
    }

    /**
     * 获取速卖通未好评的订单
     * @return [type] [description]
     */
    static public function aliexpressNonHaopingOrders($selleruserid, $pageSize = 50)
    {

        $response = self::callApi('eagle.modules.order.openapi.orderApi.getEvaluationOrders', [
            'seller_id' => $selleruserid,
            'page_size' => $pageSize
        ]);
        if ($response->code) {
            // trigger_error($response->msg);die;
            \Yii::error("用户" . $selleruserid . "授权失败","file");
            self::localLog("!!!用户授权失败, id is " . $selleruserid);
            self::localLog("response ".json_encode($response));
            return array();
        } else {
            $data = [];
            foreach ($response->data as $item) {
                $data[] = $item->orderId;
            }
            return $data;
        }
    }

    /**
     * 批量同步好评（手动好评）
     * @return [type]                  [description]
     */
    static public function syncEvaluation($order, $rank, $message, $responseDetail = false)
    {
        if (is_array($order)) {
            $result = [];
            foreach ($order as $od_id) {
                $result[] = self::syncEvaluation($od_id, $rank, $message, $responseDetail);
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
            if (is_int($message)) {    // 获取模板内容
                // var_dump($message);die;
                $message = CmCommentTemplate::findOne($message)->content;
            }
            if (is_array($message)) {
                $message = $message[$order->order_source_order_id];
            }
            $param = [
                'seller_id' => $order->selleruserid,
                'order_id' => $order->order_source_order_id,
                'content' => $message,
                'score' => $rank
            ];
            // $result = (object)[
            // 	'code'=>500,
            // 	'msg'=>'aaabbccc'
            // ];
            $result = self::callApi('eagle.modules.order.openapi.OrderApi.saveEvaluation', $param);
            // 保存结果
            // 写入日志
            $log = new CmCommentLog();
            $log->order_id = $order->order_id;
            $log->order_source_order_id = $order->order_source_order_id;
            $log->selleruserid = $order->selleruserid;
            $log->platform = $order->order_source;
            $log->source_buyer_user_id = $order->source_buyer_user_id;
            $log->subtotal = $order->grand_total;
            $log->currency = $order->currency;
            $log->paid_time = $order->paid_time;
            $log->content = $message;
            $log->is_success = !$result->code ? self::STATUS_SUCCESS : self::STATUS_FAIL;
            $log->error_msg = $result->code ? $result->msg : '';
            $log->createtime = time();
            $log->order_source_create_time = $order->order_source_create_time;
            if ($log->save()) {
                $rtn['log_id'] = $log->id;
                if ($responseDetail) {
                    $rtn['log'] = $log;
                }
                $rtn['success'] = true;
            }
            return $rtn;
        }

    }

   


    /*******************   私有方法结束   ***********************/

    /**
     * 设置自动好评记录的规则id
     * @param [type] $log_id  [description]
     * @param [type] $rule_id [description]
     */
    static public function setRuleIdtoLog($log_id, $rule_id)
    {

        $log = CmCommentLog::findOne($log_id);
        self::localLog('returned CmCommentLog is ' . json_encode($log));
        $log->rule_id = $rule_id;
        return $log->save();
    }


    /**
     * 批量调用平台好评接口 (自动好评)
     * message int 代表好评模版id  array 代表 [order_source_order_id=>content]
     * @return [type] [description]
     */
    static public function evaluation($order, $rank, $message, $responseDetail = false)
    {
        if (is_array($order)) {
            $result = [];
            foreach ($order as $od_id) {
                $result[] = self::evaluation($od_id, $rank, $message, $responseDetail);
            }
            return $result;
        } else {
            // 接口逻辑开始
            $rtn = [];
            if (!is_object($order)) {
                $order = OdOrder::find()
                    ->where([
                        'order_source_order_id' => $order
                    ])
                    ->one();
            }
            // var_dump($order);die;
            if (!is_string($message)) {    // 获取模板内容
                $message = CmCommentTemplate::findOne($message)->content;
            }
            $param = [
                'orderId' => $order->order_source_order_id,
                'score' => $rank,
                'feedbackContent' => $message,
                'sellerloginid' => $order->selleruserid
            ];
            $methodName = $order->order_source . 'Api';
            if (self::getEnv() === 'production') {
                self::localLog("start evaluation order " . $order->order_source_order_id);

                $result = self::$methodName($param); // 调用api
                self::localLog("end evaluation order " . $order->order_source_order_id . " result is " . json_encode($result));

            } else {
                self::localLog('-- runOpenApi Success[test env]: ');
                self::localLog($param);
                $result = (object)['code' => 0];
            }

            // var_dump($result);die;
            if (!$result->code) {
                // 写入日志
                $log = new CmCommentLog();
                $log->order_id = $order->order_id;
                $log->order_source_order_id = $order->order_source_order_id;
                $log->selleruserid = $order->selleruserid;
                $log->platform = $order->order_source;
                $log->source_buyer_user_id = $order->source_buyer_user_id;
                $log->subtotal = $order->grand_total;
                $log->currency = $order->currency;
                $log->paid_time = $order->paid_time;
                $log->content = $message;
                $log->is_success = self::STATUS_WAIT;
                $log->createtime = time();
                $log->order_source_create_time = $order->order_source_create_time;
                if ($log->save()) {
                    $rtn['log_id'] = $log->id;
                    self::localLog("log_id is " . $log->id);
                    if ($responseDetail) {
                        $rtn['log'] = $log;
                    }
                }
                // 改变订单状态
                self::changeOrdersToCommented($order->order_source_order_id);
                $rtn['success'] = true;
            } else {
                $rtn['success'] = false;
            }
            return $rtn;
        }
    }

    

  

    /**
     * 改变好评接口调用结果状态
     * @return [type] [description]
     */
    static public function updateLogStatus(CmCommentLog $log)
    {
        if (self::getEnv() === 'production') {
            $param = ['orderId' => $log->order_source_order_id];
            $apiName = $log->platform . 'Result';
            $result = self::$apiName($param);
            if ($result->code >= 0) {
//				$order = OdOrder::find()->where([
//					'order_source_order_id'=>$log->order_source_order_id
//				])->one();
                self::changeOrdersToCommented($log->order_source_order_id, 'is_comment_status', $result->code);
                switch ($result->code) { // code;
                    case 1:
                        $log->is_success = self::STATUS_SUCCESS;
                        break;
                    case 0:
                        $log->is_success = self::STATUS_FAIL;
                        $log->error_msg = $result->msg;
                        break;
                    case -1:
                        break;
                }
                if ($log->save()) {
                    self::localLog('-- updateLogStatus success! and orderId is ' . $log->order_source_order_id);
                }
                return true;
            }
            return false;
        }
        return null;
    }

    /**
     * 获取自动好评规则
     * @param  string $platform 平台名称
     */
    static public function getRulesByPlatform($platform = 'aliexpress', $puid = false)
    {
        // 找规则
        $rules = CmCommentRule::find()
            ->where([
                'is_use' => 1
            ])->all();
        return $rules;
    }

  


    static public function matchOrdersFromRules(CmCommentRule $rule)
    {
        $orders = OdOrder::find()
            ->where(['IN', 'is_comment_status', array(0, 2, 4)

//				'order_source_status'=>OdOrder::STATUS_SHIPPED,
//              'is_comment_ignore' => 0
            ])
            ->andWhere(['IN', 'order_source_status', array('FINISH', 'FUND_PROCESSING')])
            ->andWhere(['IN', 'selleruserid', $rule->selleruseridlist])
            ->andWhere(['IN', 'consignee_country_code', $rule->countrylist])
            ->andWhere([
                'or',
                ['>', 'paid_time', strtotime('-2 month')],
                ['paid_time' => 0]
            ]);

        if (!$rule->is_dispute) {
            $orders->andWhere(['issuestatus' => 0]);
        };
        self::localLog('-- Match Orders SQL: ');
        self::localLog($orders->createCommand()->getRawSql());
        return $orders->all();
    }

    

    /**
     * 检查规则中的国家是否已存在
     * @return [type] [description]
     */
    static public function checkCountriesExist(CmCommentRule $rule)
    {
        // 查询
        $result = [];
        $countries = explode(',', $rule->countrylist);
        $sellers = explode(',', $rule->selleruseridlist);
        foreach ($sellers as $seller) {
            foreach ($countries as $country) {
                $query = CmCommentRule::find()
                    ->where([
                        'is_use' => 1
                    ])
                    ->andWhere('FIND_IN_SET(:seller,`selleruseridlist`)', [
                        ':seller' => $seller
                    ])
                    ->andWhere('FIND_IN_SET(:country,`countrylist`)', [
                        ':country' => $country
                    ]);
                if (isset($rule->id) && $rule->id) {
                    $query->andWhere([
                        '<>', 'id', $rule->id
                    ]);
                }
                if ($query->count()) {
                    $result[$seller][] = $country;
                }
            }
        }
        return $result;
    }


    /**
     * 检查规则中的国家是否已存在
     * @return [type] [description]
     */
    static public function checkCountriesExistV2($rule, $uid)
    {
        // 查询
        $result = [];
        $countries = $rule['countryList'];
        $sellers = $rule['sellerIdList'];

        $rules = CommentManagerFactory::getManagerByStatic(CommentConstances::COMMENT_RULE)->find(array('uid' => $uid));
        $rules = iterator_to_array($rules);

        foreach ($rules as $ruleRtn) {

            if (isset($rule['_id']) && $rule['_id'] == $ruleRtn['_id']->{'$id'}) {
                continue;
            }


            $overlapSeller = array_intersect($ruleRtn['sellerIdList'], $sellers);
            if (!empty($overlapSeller)) {

                $dbRtnCountryCount = count($ruleRtn['countryList']);
                $resetCountryCount = count($countries);
                if ($dbRtnCountryCount == CommentConstances::TOTAL_COUNTRY_QUANTITY || $resetCountryCount == CommentConstances::TOTAL_COUNTRY_QUANTITY) {
                    if ($resetCountryCount == $dbRtnCountryCount) {
                        foreach ($sellers as $sl) {
                            $result[$sl] = $ruleRtn['countryList'];
                        }
                    } else if ($resetCountryCount != $dbRtnCountryCount) {
                        continue;
                    }
                }
                $overlapCountry = array_intersect($ruleRtn['countryList'], $countries);
                if (!empty($overlapCountry)) {
                    foreach ($overlapSeller as $ol) {
                        $result[$ol] = $overlapCountry;
                    }
                }
            }
        }
        return $result;
    }

    /**
     * 改变订单状态
     */
    static public function changeOrdersToCommented($order_id, $field = 'is_comment_status', $status = 1)
    {
        $order_obj = OdOrder::find()
            ->where([
                'order_source_order_id' => $order_id
            ])->one();
        $order_obj->$field = $status;
        if ($field == 'is_comment_status' && $status == 1) {
            $order_obj->is_comment_ignore = 0;
        }
        if ($order_obj->save()) {
            self::localLog('-- change order_v2 status success! and order_source_order_id is ' . $order_id);
        }
        return false;
    }

    public static function changeOrdersToCommentedV2($order_id, $field = 'is_comment_status', $status = CommentStatus::SUCCESS)
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
     * 店铺规则初始化
     */
    public static function initSets($puid)
    {
        //比对comment设置表与用户绑定的店铺是否一致
        $users_obj = SaasAliexpressUser::find()->where(['uid' => $puid])->all();
        $enable_obj = CmCommentEnable::find()->where(['platform' => 'aliexpress'])->all();

        //取出enable表有的 但是user表没有的
        $enable_arr = array_map(function ($enable_obj) {
            return $enable_obj->selleruserid;
        }, $enable_obj);

        $users_arr = array_map(function ($users_obj) {
            return $users_obj->sellerloginid;
        }, $users_obj);

        $addItems = array_diff($users_arr, $enable_arr);
        $deleteItems = array_diff($enable_arr, $users_arr);

        foreach ($addItems as $add) {
            self::insertCommentEnable($puid, $add);
        }

        foreach ($deleteItems as $delete) {
            self::deleteCommentEnable($delete);
        }
        return CmCommentEnable::find()->where(['platform' => 'aliexpress'])->all();

    }

    /**
     * 店铺设置插入新的店铺信息
     */
    public static function insertCommentEnable($puid, $sellerid)
    {
        if (!self::checkEnable($sellerid)) {
            $en = new CmCommentEnable();
            $en->uid = $puid;
            $en->selleruserid = $sellerid;
            $en->platform = 'aliexpress';
            $en->enable_status = 0;
            $en->createtime = time();
            return $en->save();
        }
    }

    /**
     * 检查是否存在
     */
    public static function checkEnable($sellerid)
    {
        return CmCommentEnable::find()->where(['selleruserid' => $sellerid, 'platform' => 'aliexpress'])->one();
    }

    /**
     * 删除好评设置表
     */
    public static function deleteCommentEnable($sellerid)
    {
        return CmCommentEnable::find()->where(['selleruserid' => $sellerid])->one()->delete();
    }

}

  