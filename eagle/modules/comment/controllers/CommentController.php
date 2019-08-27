<?php
namespace eagle\modules\comment\controllers;

use common\helpers\Helper_Array;
use eagle\models\comment\CmCommentEnable;
use eagle\models\comment\CmCommentLog;
use eagle\models\comment\CmCommentTemplate;
use eagle\models\SaasAliexpressUser;
use eagle\models\sys\SysCountry;
use eagle\modules\app\apihelpers\AppTrackerApiHelper;
use eagle\modules\comment\config\CommentConfig;
use eagle\modules\comment\dal_mongo\CommentConstances;
use eagle\modules\comment\dal_mongo\CommentManagerFactory;
use eagle\modules\comment\dal_mongo\CommentRule;
use eagle\modules\comment\dal_mongo\CommentTemplate;
use eagle\modules\comment\dal_mongo\DefaultInfoType;
use eagle\modules\comment\helpers\CommentHelper;
use eagle\modules\comment\helpers\CommentHelperV2;
use eagle\modules\comment\helpers\CommentStatus;
use eagle\modules\comment\models\CmCommentRule;
use eagle\modules\order\models\OdOrder;
use eagle\modules\order\models\OdOrderItem;
use MongoId;
use yii\data\Pagination;
use yii\data\Sort;
use console\controllers\CommentHelperController as console;

// use eagle\models\comment\CmCommentRule;

class CommentController extends \eagle\components\Controller
{
    public $enableCsrfValidation = false;

    const MAX_TIMEOUT = '1 year';
    const LOG_ID = "eagle-modules-comment-controllers-commentController";

    static private function localLog($msg)
    {
        console::log($msg, self::LOG_ID);
    }

    protected function apptracker($key)
    {
        AppTrackerApiHelper::actionLog(CommentConfig::APP_TRACKER_KEY, "/comment/" . $key);
    }

    /** ================================= 未 评 价 订 单  ===================================== **/
    //未评价订单
    public function actionIndex()
    {
        self::apptracker("comment/index");
        $puid = \Yii::$app->user->identity->getParentUid();
        // 先从速卖通平台获取待好评订单列表
        // -- 获取店铺
        $selleruserid = Helper_Array::getCols(SaasAliexpressUser::find()
            ->select(['sellerloginid'])
            ->where([
                'uid' => $puid,
                'is_active' => 1
            ])->asArray()->all(), 'sellerloginid');
        $pOrders = [];
        foreach ($selleruserid as $seller) {
            $nonCmOrders = CommentHelper::aliexpressNonHaopingOrders($seller);
            if ($nonCmOrders === false) {
                $nonCmOrders = [];
                // trigger_error('获取未好评订单出错!');die;
            }
            $pOrders = array_merge($pOrders, $nonCmOrders);
        }

        //从 oms 订单表查询详细数据
        $order_obj = OdOrder::find();
        $order_obj->where(['IN', 'order_source_order_id', $pOrders]);
        // $order_obj->andWhere(['order_source'=>'aliexpress','order_status'=>OdOrder::STATUS_SHIPPED]);
        //需要从订单表查询出的字段
        $order_obj->select(['order_id', 'order_source_order_id', 'source_buyer_user_id', 'selleruserid', 'consignee', 'subtotal', 'currency', 'order_source_create_time', 'issuestatus']);

        //如果有处理条件
        $type = \Yii::$app->request->get('operate_type');
        if ($type === null || $type === '0') {
            $order_obj->andWhere([
                // 'is_comment_status'=>0,
                'is_comment_ignore' => 0
            ]);
        }
        if ($type === '1') {
            $order_obj->andWhere(['is_comment_status' => 1]);
        }
        if ($type === '2') {
            $order_obj->andWhere(['is_comment_ignore' => 1]);
        }

        //如果有时间条件
        $starttime = strtotime('-' . self::MAX_TIMEOUT);
        if (($stime = \Yii::$app->request->get('stime'))) {
            $stime = strtotime($stime);
            if ($stime > $starttime) {
                $starttime = $stime;
            }
        }
        $endtime = time();
        if (($etime = \Yii::$app->request->get('etime')) && $etime < $endtime) {
            $endtime = strtotime($etime . ' 23:59:59');
        }
        $order_obj->andWhere(['between', 'order_source_create_time', $starttime, $endtime]);

        //按号码搜索
        if ($searchValue = \Yii::$app->request->get('searchval')) {
            $keys = \Yii::$app->request->get('keys', 'comment_order_id');
            //搜索用户自选搜索条件
            if (in_array($keys, ['comment_order_id', 'comment_source_id', 'comment_buyerid', 'comment_buyername'])) {
                $kv = [
                    'comment_order_id' => 'order_id',
                    'comment_source_id' => 'order_source_order_id',
                    'comment_buyerid' => 'source_buyer_user_id',
                    'comment_buyername' => 'consignee',
                ];
                $key = $kv[$keys];
                $order_obj->andWhere([$key => $searchValue]);
            } elseif ($keys == 'comment_sku') {
                $ids = Helper_Array::getCols(OdOrderItem::find()->where('sku = :sku', [':sku' => $searchValue])->select('order_id')->asArray()->all(), 'order_id');
                $order_obj->andWhere(['IN', 'order_id', $ids]);
            }
        }
        //按店铺筛选
        if ($selleruserid = \Yii::$app->request->get('selleruser')) {
            $order_obj->andWhere(['selleruserid' => $selleruserid]);
        }


        //排序信息
        $sort = new Sort([
            'attributes' => [
                'issuestatus',
                'order_source_create_time'
            ],
            'defaultOrder' => [
                'order_source_create_time' => SORT_DESC
            ]
        ]);

        //分页
        //
        $pages = new Pagination([
            'defaultPageSize' => 50,
            'pageSize' => \Yii::$app->request->get('per-page', 50),
            'totalCount' => $order_obj->count(),
            'pageSizeLimit' => [50, 100, 200],//每页显示条数范围
            'params' => $_REQUEST,
        ]);

        $orders = $order_obj
            ->offset($pages->offset)
            ->limit($pages->limit)
            ->orderBy($sort->orders)
            ->with('items')
            ->all();

        // var_dump($order_obj->createCommand()->getRawSql());

        /* = 留言内容 = */
        $comment_template_obj = CmCommentTemplate::find()
            ->where(['is_use' => 1, 'platform' => 'aliexpress'])
            ->orderby('createtime desc')
            ->all();

        //查询出绑定的所有店铺信息
        $saasaliexpressusers = SaasAliexpressUser::find()->where(['uid' => $puid])->all();
        return $this->render('index', [
            'orders' => $orders,
            'pages' => $pages,
            'sort' => $sort,
            'comment_templates' => $comment_template_obj,
            'aliexpressusers' => $saasaliexpressusers,
            'selleruser' => $selleruserid,
        ]);
    }

    /** ================================= 未 评 价 订 单  ===================================== **/
    //未评价订单
    public function actionIndexV2()
    {
        self::apptracker("comment/indexV2");
        $puid = \Yii::$app->user->identity->getParentUid();

        // 先从速卖通平台获取待好评订单列表
        // -- 获取店铺
        $selleruserid = Helper_Array::getCols(SaasAliexpressUser::find()
            ->select(['sellerloginid'])
            ->where([
                'uid' => $puid,
                'is_active' => 1
            ])->asArray()->all(), 'sellerloginid');
        $pOrders = [];
        foreach ($selleruserid as $seller) {
            $nonCmOrders = CommentHelper::aliexpressNonHaopingOrders($seller);
            if ($nonCmOrders === false) {
                $nonCmOrders = [];
            }
            $pOrders = array_merge($pOrders, $nonCmOrders);
        }
        self::localLog("需要好评订单=>".json_encode($pOrders));
        //从 oms 订单表查询详细数据
        $order_obj = OdOrder::find();
        $order_obj->where(['IN', 'order_source_order_id', $pOrders]);
        //区别在队列中的订单
        $order_obj->andWhere(['IN', 'is_comment_status', array(CommentStatus::NOT_COMMENT, CommentStatus::NOT_RETRY_FAILED, CommentStatus::RETRY_FAILED)]);

        //需要从订单表查询出的字段
        $order_obj->select(['order_id', 'order_source_order_id', 'source_buyer_user_id', 'selleruserid', 'consignee', 'subtotal', 'currency', 'order_source_create_time', 'issuestatus']);

        //按号码搜索
        if ($searchValue = \Yii::$app->request->get('searchval')) {
            $order_obj->andWhere(['LIKE', 'order_source_order_id', $searchValue]);
        }
        //按店铺筛选
        if ($selleruserid = \Yii::$app->request->get('selleruser')) {
            $order_obj->andWhere(['selleruserid' => $selleruserid]);
        }


        //排序信息
        $sort = new Sort([
            'attributes' => [
                'issuestatus',
                'order_source_create_time'
            ],
            'defaultOrder' => [
                'order_source_create_time' => SORT_DESC
            ]
        ]);

        //分页
        //
        $pages = new Pagination([
            'defaultPageSize' => 50,
            'pageSize' => \Yii::$app->request->get('per-page', 50),
            'totalCount' => $order_obj->count(),
            'pageSizeLimit' => [50, 100, 200],//每页显示条数范围
            'params' => $_REQUEST,
        ]);


        $orders = $order_obj
            ->offset($pages->offset)
            ->limit($pages->limit)
            ->orderBy($sort->orders)
//            ->with('items')
            ->all();


        /* = 留言内容 = */
        $comment_template_obj = CommentManagerFactory::getManagerByStatic(CommentConstances::COMMENT_TEMPLATE)->find(array("uid" => $puid, "isUse" => "1", "platform" => CommentConstances::ALIEXPRESS));

        //查询出绑定的所有店铺信息
        $saasaliexpressusers = SaasAliexpressUser::find()->where(['uid' => $puid])->all();
        $comment_template_obj = iterator_to_array($comment_template_obj);

        return $this->render('indexV2', [
            'orders' => $orders,
            'pages' => $pages,
            'sort' => $sort,
            'comment_templates' => $comment_template_obj,
            'aliexpressusers' => $saasaliexpressusers,
            'selleruser' => $selleruserid,
        ]);
    }

    /*
     * 发送好评
     */
    public function actionAddcomment()
    {
        if (!\Yii::$app->request->isAjax) {
            return $this->jsonResult();
        }
        //检验数据
        $ids_str = \Yii::$app->request->post('ids');
        $template_id = (int)\Yii::$app->request->post('template_id');
        if (!strlen($ids_str) || !strlen($template_id)) {
            return $this->jsonResult();
        }
        //处理数据 平台订单号
        $ids = explode(',', trim($ids_str));
        if (count($ids) > 1) {
            $trackerKey = "comment/addcomment";
        } else {
            $trackerKey = "comment/batchaddcomment";
        }
        //发送到接口
        $result = CommentHelper::syncEvaluation($ids, CommentConfig::DEFAULT_RANK, $template_id);
        self::apptracker($trackerKey);
        if ($result) {
            return $this->jsonResult(0);
        }
        return $this->jsonResult(1, '', '平台数据错误');
    }


    /*
   * 发送好评
   */
    public function actionAddcommentV2()
    {
        if (!\Yii::$app->request->isAjax) {
            return $this->jsonResult();
        }
        $puid = \Yii::$app->user->identity->getParentUid();
        $content = \Yii::$app->request->post('content');
        $score = \Yii::$app->request->post('score');
        $orderSourceOrderId = \Yii::$app->request->post('orderSourceOrderIds');
        $defaultCommentContent = \Yii::$app->request->post('customizedContent');
        $defaultCustomizedContentId = \Yii::$app->request->post('defaultCustomizedContentId');
        if (isset($defaultCustomizedContentId))
            if ($defaultCustomizedContentId != "") {
                CommentManagerFactory::getManagerByStatic(CommentConstances::DEFAULT_INFO)->findAndModify(array('_id' => new MongoId($defaultCustomizedContentId)), array('$set' => array("content" => $defaultCommentContent)));
            } else {
                CommentManagerFactory::getManagerByStatic(CommentConstances::DEFAULT_INFO)->insert(array('uid' => $puid, 'infoType' => DefaultInfoType::CUSTOMIZED_COMMENT_CONTENT, 'content' => $defaultCommentContent));
            }
        //处理数据 平台订单号
        if (count($orderSourceOrderId) > 1) {
            $trackerKey = "comment/addcomment";
        } else {
            $trackerKey = "comment/batchaddcomment";
        }
        //发送到接口
        $result = CommentHelperV2::syncEvaluation($orderSourceOrderId, $score, $content, $puid);
        self::apptracker($trackerKey);
        self::localLog("syncEvaluation result " . json_encode($result));
        foreach ($result as $val) {
            if (!$val['success']) {
                return $this->jsonResult(1, '', '平台数据错误');
            }
        }
        return $this->jsonResult(0, '', "成功");
    }

    /**
     * 根据买家id获取没有好评的订单号
     */
    public function actionNoCommentOrder(){
        $sellerId=$_GET["sellerId"];
        var_dump($sellerId);
        $nonCmOrders = CommentHelper::aliexpressNonHaopingOrders($sellerId);
        return json_encode($nonCmOrders);
    }
    
    public function actionEditComment()
    {
        $ids_str = \Yii::$app->request->post('orderSourceOrderId');
        $puid = \Yii::$app->user->identity->getParentUid();
        $tmpl = CommentManagerFactory::getManagerByStatic(CommentConstances::COMMENT_TEMPLATE)->find(array("uid" => $puid, "platform" => CommentConstances::ALIEXPRESS));
        $defaultContent = CommentManagerFactory::getManagerByStatic(CommentConstances::DEFAULT_INFO)->findOne(array("uid" => $puid, "infoType" => DefaultInfoType::CUSTOMIZED_COMMENT_CONTENT));
        $tmpl = iterator_to_array($tmpl);
        return $this->renderAuto("editcomment", array('id' => $ids_str, 'commentTemplate' => $tmpl, "defaultContent" => $defaultContent));
    }

    /*
     * 不予处理
     */
    public function actionIgnoreorder()
    {
        //判断条件
        if (!\Yii::$app->request->isAjax) return $this->jsonResult();
        $source_ids = \Yii::$app->request->post('source_id');
        if (!strlen($source_ids)) {
            return $this->jsonResult();
        }
        //这里获得的是小老板订单号
        $ids = explode(',', trim($source_ids));
        if (count($ids) > 1) {
            $trackerKey = "comment/batchignoreorder";
        } else {
            $trackerKey = "comment/ignoreorder";
        }
        self::apptracker($trackerKey);

        //改变订单为不处理状态
        foreach ($ids as $id) {
            $order = OdOrder::findOne(intval($id));
            $order->is_comment_ignore = 1;
            if (!$order->save()) {
                $this->jsonResult();
            }
        }

        return $this->jsonResult(0);
    }

    /** ================================= 好 评 规 则  ===================================== **/
    /**
     * 自动好评界面入口
     * @return [type] [description]
     */
    public function actionRule()
    {
        self::apptracker("comment/rule");
        $puid = \Yii::$app->user->identity->getParentUid();
        //比对comment设置表与用户绑定的店铺是否一致
        $users = SaasAliexpressUser::find()->where(['uid' => $puid, 'is_active' => 1]);
        $enable = CmCommentEnable::find()->where(['platform' => 'aliexpress'])->all();
        $enables_obj = CommentHelper::initSets($puid);

        $rule_Obj = CmCommentRule::find()->where(['platform' => 'aliexpress']);
        $page = new Pagination([
            'defaultPageSize' => 5,
            'totalCount' => $users->count(),
            'pageSizeLimit' => [5, 10, 20],
            'pageParam' => 'page1',
            'pageSizeParam' => 'per-page1'
        ]);

        $showpage = new Pagination([
            'defaultPageSize' => 5,
            'totalCount' => $rule_Obj->count(),
            'pageSizeLimit' => [5, 10, 20],
            'pageParam' => 'page2',
            'pageSizeParam' => 'per-page2'
        ]);

        $users = $users
            ->offset($page->offset)
            ->limit($page->limit)
            ->orderBy('create_time DESC')
            ->all();

        $rules = $rule_Obj
            ->offset($showpage->offset)
            ->limit($showpage->limit)
            ->orderBy('createtime DESC')
            ->all();

        return $this->render('rule', [
            'rules' => $rules,
            'page' => $page,
            'showpage' => $showpage,
            'enables_obj' => $enables_obj,
        ]);
    }

    /** ================================= 好 评 规 则 V2 ===================================== **/
    /**
     * 自动好评界面入口
     * @return [type] [description]
     */
    public function actionRuleV2()
    {
        self::apptracker("comment/rule");
        $puid = \Yii::$app->user->identity->getParentUid();
        //比对comment设置表与用户绑定的店铺是否一致
        $users = SaasAliexpressUser::find()->where(['uid' => $puid, 'is_active' => 1]);

        $rule_Obj = CommentManagerFactory::getManagerByStatic(CommentConstances::COMMENT_RULE)->find(array("uid" => $puid, "platform" => "aliexpress"));
        $page = new Pagination([
            'defaultPageSize' => 5,
            'totalCount' => $users->count(),
            'pageSizeLimit' => [5, 10, 20],
            'pageParam' => 'page1',
            'pageSizeParam' => 'per-page1'
        ]);

        $showpage = new Pagination([
            'defaultPageSize' => 5,
            'totalCount' => $rule_Obj->count(),
            'pageSizeLimit' => [5, 10, 20],
            'pageParam' => 'page2',
            'pageSizeParam' => 'per-page2'
        ]);


        $rules = $rule_Obj
            ->sort(array('createtime' => -1))
            ->skip($showpage->offset)
            ->limit($showpage->limit);
        $rules = iterator_to_array($rules);
        return $this->render('ruleV2', [
            'rules' => $rules,
            'page' => $page,
            'showpage' => $showpage,
        ]);
    }

    public function actionTest()
    {
        echo phpinfo();
    }

    public function actionAddrule()
    {
        $puid = \Yii::$app->user->identity->getParentUid();
        //编辑
        if ($id = \Yii::$app->request->get('id')) {
            $rule = CmCommentRule::findOne($id);
            $trackerKey = "comment/editrule";
        } else {
            $rule = new CmCommentRule();
            $trackerKey = "comment/addrule";
        }
        self::apptracker($trackerKey);
        //查询店铺
        $users = CmCommentEnable::find()->where(['platform' => 'aliexpress'])->all();
        $selleruserid = [];
        foreach ($users as $user) {
            $selleruserid[$user->selleruserid] = $user->selleruserid;
        }
        return $this->render('addrule', [
            'aliexpressuser' => $selleruserid,
            'rule' => $rule,
        ]);
    }

    public function actionAddruleV2()
    {
        $puid = \Yii::$app->user->identity->getParentUid();
        //编辑
        //查询店铺
        $users = SaasAliexpressUser::find()->where(array('uid' => $puid))->all();
        $selleruserid = [];
        $selleruseridArr = [];
        foreach ($users as $user) {
            $selleruserid[$user->sellerloginid] = $user->sellerloginid;
            $selleruseridArr[] = $user->sellerloginid;
        }
        if ($id = \Yii::$app->request->get('id')) {
            $rule = CommentManagerFactory::getManagerByStatic(CommentConstances::COMMENT_RULE)->find(array('_id' => new MongoID($id['$id'])));
            if ($rule->hasNext())
                $rule = $rule->next();
            $trackerKey = "comment/editruleV2";
        } else {
            $rule = array();
            $rule['uid'] = null;
            $rule['_id'] = null;
            $rule['score'] = null;
            $rule['sellerIdList'] = $selleruseridArr;
            $rule['content'] = "Thanks for your visit, we sincerely hope acquire your lasting support and will provide better commodities for you.";
            $rule['isCommentIssue'] = null;
            $rule['countryList'] = array('*');
            $rule['isUse'] = null;
            $rule['platform'] = CommentConstances::ALIEXPRESS;
            $rule['createTime'] = time();
            $rule['updateTime'] = time();
            $trackerKey = "comment/addruleV2";
        }
        self::apptracker($trackerKey);

        return $this->render('addruleV2', [
            'aliexpressuser' => $selleruserid,
            'rule' => $rule,
        ]);
    }

    public function actionDoaddrule()
    {
        // var_dump(\Yii::$app->request->post('is_dispute'));die;
        if (!\Yii::$app->request->getIsAjax()) return $this->jsonResult();
        //是否包含纠纷
        $is_dispute = \Yii::$app->request->post('is_dispute') ? 1 : 0;
        //店铺
        if ($shop_id = \Yii::$app->request->post('shop_id')) {
            $selleruseridlist = implode(',', $shop_id);
        } else {
            return $this->jsonResult(1, '', '请选择关联店铺');
        }
        //好评内容
        if (empty($content = \Yii::$app->request->post('content')) || empty($country = \Yii::$app->request->post('countries'))) {
            return $this->jsonResult(1, '', '好评内容及匹配国家不能为空');
        }
        $countrylist = implode(',', $country);
        //将规则内容插入到规则表中
        if ($id = \Yii::$app->request->post('ruleid')) {
            $trackerKey = 'comment/do-rule-save';
            $rule = CmCommentRule::findOne($id);
        } else {
            $trackerKey = 'comment/do-rule-save';
            $rule = new CmCommentRule();
        }
        $rule->selleruseridlist = $selleruseridlist;
        $rule->content = $content;
        $rule->is_dispute = $is_dispute;
        $rule->countrylist = $countrylist;
        $rule->is_use = 1;
        $rule->platform = 'aliexpress';
        $rule->createtime = time();
        // 检查所选的国家是否已经存在
        $existCountries = CommentHelper::checkCountriesExist($rule);
        if (count($existCountries)) {
            $s = [];
            foreach ($existCountries as $seller => $c) {
                $s[] = '店铺' . $seller . ' 所对应国家(' . implode('、', SysCountry::getCountriesName($c)) . ')';
            }
            return $this->jsonResult(1, '', '已设置' . implode(' ', $s) . '的好评规则');
        }

        if ($rule->save()) {
            self::apptracker($trackerKey);
            return $this->jsonResult(0);
        }
        return $this->jsonResult();
    }

    public function actionDoaddruleV2()
    {
        $puid = \Yii::$app->user->identity->getParentUid();
        // var_dump(\Yii::$app->request->post('is_dispute'));die;
        if (!\Yii::$app->request->getIsAjax()) return $this->jsonResult();
        //是否包含纠纷
        $is_dispute = \Yii::$app->request->post('is_dispute') ? 1 : 0;
        //店铺
        if ($shop_id = \Yii::$app->request->post('shop_id')) {
            $selleruseridlist = $shop_id;
        } else {
            return $this->jsonResult(1, '', '请选择关联店铺');
        }
        //好评内容
        if (empty($content = \Yii::$app->request->post('content')) || empty($country = \Yii::$app->request->post('countries'))) {
            return $this->jsonResult(1, '', '好评内容及匹配国家不能为空');
        }
        $countrylist = $country;
        //将规则内容插入到规则表中
        if ($id = \Yii::$app->request->post('ruleid')) {
            $trackerKey = 'comment/do-rule-save';
            $ruleRtn = CommentManagerFactory::getManagerByStatic(CommentConstances::COMMENT_RULE)->find(array('_id' => new MongoID($id)));
            if ($ruleRtn->hasNext()) {
                $ruleRtn = $ruleRtn->next();
                $rule = $ruleRtn;
            } else {
                unset($ruleRtn);
            }
        } else {
            $trackerKey = 'comment/do-rule-save';
            $rule = array();
        }
        if (isset($puid)) {
            $rule['uid'] = $puid;
        }
//        var_dump("score");
//        var_dump(\Yii::$app->request->post['score']);
//        die();
//        var_dump(\Yii::$app->request->post('score'));
        $rule['score'] = \Yii::$app->request->post('score');
        $rule['sellerIdList'] = $selleruseridlist;
        $rule['content'] = $content;
        $rule['isCommentIssue'] = $is_dispute;
        $rule['countryList'] = $countrylist;
        $rule['isUse'] = 1;
        $rule['platform'] = CommentConstances::ALIEXPRESS;
        if (isset($ruleRtn['createTime'])) {
            $rule['createTime'] = $ruleRtn['createTime'];
        } else {
            $rule['createTime'] = time();
        }
        $rule['updateTime'] = time();

        // 检查所选的国家是否已经存在
        $existCountries = CommentHelper::checkCountriesExistV2($rule, $rule['uid']);
        if (count($existCountries)) {
            $s = [];
            foreach ($existCountries as $seller => $c) {
                $s[] = '店铺' . $seller . ' 所对应国家(' . implode('、', SysCountry::getCountriesName($c)) . ')';
            }
            return $this->jsonResult(1, '', '已设置' . implode(' ', $s) . '的好评规则');
        }
        try {
            if (isset($ruleRtn)) {
                CommentManagerFactory::getManagerByStatic(CommentConstances::COMMENT_RULE)->findAndModify(array('_id' => $ruleRtn['_id']), $rule);
            } else {
                CommentManagerFactory::getManagerByStatic(CommentConstances::COMMENT_RULE)->insert($rule);
            }
            self::apptracker($trackerKey);
            return $this->jsonResult(0);
        } catch (\Exception $e) {
            return $this->jsonResult();
        }
    }

    /**
     * 规则删除
     */
    public function actionDodeleterule()
    {
        if (\Yii::$app->request->getIsAjax() && ($id = \Yii::$app->request->post('id'))) {
            self::apptracker("comment/do-rule-delete");
            $rule = CmCommentRule::findOne($id);
            if (!empty($rule)) return $rule->delete();
        }
        return $this->jsonResult();
    }


    public function actionDodeleteruleV2()
    {
        if (\Yii::$app->request->getIsAjax() && ($id = \Yii::$app->request->post('id'))) {
            self::apptracker("comment/do-rule-delete-v2");
            try {
                CommentManagerFactory::getManagerByStatic(CommentConstances::COMMENT_RULE)->remove(array('_id' => new MongoId($id)));
                return $this->jsonResult(0, null, "删除成功");
            } catch (\MongoException $e) {
                return $this->jsonResult();
            }
        }
        return $this->jsonResult();
    }
    /** ================================= 好 评 模 版  ===================================== **/

    /**
     * 好评模版
     */
    public function actionTemplate()
    {

        self::apptracker("comment/template");

        $template_obj = CmCommentTemplate::find()->where(['platform' => 'aliexpress', 'is_use' => 1]);

        //分页
        $pages = new Pagination([
            'defaultPageSize' => 10,
            'pageSize' => \Yii::$app->request->get('per-page', 10),
            'totalCount' => $template_obj->count(),
            'pageSizeLimit' => [5, 10, 20, 50],//每页显示条数范围
            'params' => $_REQUEST,
        ]);

        $templates = $template_obj
            ->orderby('createtime desc')
            ->limit($pages->limit)
            ->offset($pages->offset)
            ->all();


        return $this->render('commenttemplate', [
            'templates' => $templates,
            'pages' => $pages,

        ]);
    }

    /**
     * 好评模版
     */
    public function actionTemplateV2()
    {

        self::apptracker("comment/templateV2");

        $puid = \Yii::$app->user->identity->getParentUid();

        $tmpl = CommentManagerFactory::getManagerByStatic(CommentConstances::COMMENT_TEMPLATE)->find(array("uid" => $puid, "platform" => CommentConstances::ALIEXPRESS));

        //分页
        $pages = new Pagination([
            'defaultPageSize' => 10,
            'pageSize' => \Yii::$app->request->get('per-page', 10),
            'totalCount' => $tmpl->count(),
            'pageSizeLimit' => [5, 10, 20, 50],//每页显示条数范围
            'params' => $_REQUEST,
        ]);

        $tmplRtn = $tmpl
            ->sort(array('createtime' => -1))
            ->skip($pages->offset)
            ->limit($pages->limit);
        $tmpls = iterator_to_array($tmplRtn);

        return $this->render('commenttemplateV2', [
            'templates' => $tmpls,
            'pages' => $pages,
        ]);
    }

    public function actionAddtemplateV2()
    {

        self::apptracker("comment/addtemplate-v2");

        $template = '';
        //获取templateid
        $template_id = \Yii::$app->request->get('template_id');
        //如果有id 则查询相应的模版信息
        if ($template_id) {
            $template = CommentManagerFactory::getManagerByStatic(CommentConstances::COMMENT_TEMPLATE)->findOne(array('_id' => new MongoId($template_id)));
        }
        return $this->renderAuto('addtemplateV2', array('template' => $template));
    }

    /**
     * 添加好评模版
     */
    public function actionAddtemplate()
    {
        self::apptracker("comment/addtemplate");

        $template = '';
        //获取templateid
        $template_id = intval(\Yii::$app->request->get('template_id'));
        //如果有id 则查询相应的模版信息
        if ($template_id) {
            $template = CmCommentTemplate::find()
                ->where(['platform' => 'aliexpress', 'is_use' => 1, 'id' => $template_id])
                ->one();
        }
        return $this->renderModal('addtemplate', [
            'template' => $template,
        ], '添加好评模板');
    }

    /**
     * 处理订单模版的新增或编辑
     */
    public function actionDoaddtemplate()
    {
        if (!\Yii::$app->request->isAjax) return $this->jsonResult();
        $content = \Yii::$app->request->post('content');
        $id = \Yii::$app->request->post('id');
        if (($len = strlen($content)) == 0 || $len > 1000) {
            return $this->jsonResult(1, '', '留言内容长度不符合');
        }
        //执行保存或新增
        if ($id) {
            $trackerKey = "comment/do-template-edit";
            $template_obj = CmCommentTemplate::findOne($id);
            $template_obj->content = $content;
            $template_obj->is_use = 1;
        } else {
            $trackerKey = "comment/do-template-add";
            $template_obj = new CmCommentTemplate;
            $template_obj->content = $content;
            $template_obj->createtime = time();
            $template_obj->is_use = 1;
            $template_obj->platform = 'aliexpress';
            $template_obj->content = $content;
        }
        self::apptracker($trackerKey);
        if ($template_obj->save()) {
            return $this->jsonResult(0);
        }
        return $this->jsonResult(1, '', '请检查数据内容是否完整');
    }

    /**
     * 处理订单模版的新增或编辑
     */
    public function actionDoaddtemplateV2()
    {
        $puid = \Yii::$app->user->identity->getParentUid();
        if (!\Yii::$app->request->isAjax) return $this->jsonResult();
        $content = \Yii::$app->request->post('content');
        $id = \Yii::$app->request->post('id');
        if (($len = strlen($content)) == 0 || $len > 1000) {
            return $this->jsonResult(1, '', '留言内容长度不符合');
        }
        //执行保存或新增
        if ($id) {
            $trackerKey = "comment/do-template-edit";
//            var_dump($id);
//            die();
            try {
                CommentManagerFactory::getManagerByStatic(CommentConstances::COMMENT_TEMPLATE)->findAndModify(array('_id' => new MongoId($id)), array('$set' => array('content' => $content)));
            } catch (\MongoException $e) {
                return $this->jsonResult(1, '', '请检查数据内容是否完整');
            }
        } else {
            $trackerKey = "comment/do-template-add";
            $template_obj = new CommentTemplate();
            $template_obj->content = $content;
            $template_obj->createTime = time();
            $template_obj->isUse = 1;
            $template_obj->platform = CommentConstances::ALIEXPRESS;
            $template_obj->uid = $puid;
            try {
                CommentManagerFactory::getManagerByStatic(CommentConstances::COMMENT_TEMPLATE)->insert($template_obj);
            } catch (\MongoException $e) {
                return $this->jsonResult(1, '', '请检查数据内容是否完整');
            }
        }
        self::apptracker($trackerKey);
        return $this->jsonResult(0, '', '成功');
    }

    /**
     * 好评模版删除（软删除）
     */
    public function actionDeletetemplate()
    {
        $id = \Yii::$app->request->post('id');
        if (!\Yii::$app->request->isAjax || empty($id)) {
            return $this->jsonResult();
        }
        self::apptracker("comment/do-template-delete");
        //执行删除
        $template_obj = CmCommentTemplate::findOne($id);
        $template_obj->is_use = 0;
        if ($template_obj->save()) {
            return $this->jsonResult(0);
        }
        return $this->jsonResult(1, '', '系统错误,请重试');
    }

    /**
     * 好评模版删除（软删除）
     */
    public function actionDeletetemplateV2()
    {
        $id = \Yii::$app->request->post('id');
        if (!\Yii::$app->request->isAjax || empty($id)) {
            return $this->jsonResult();
        }
        self::apptracker("comment/do-template-delete");
        try {
            CommentManagerFactory::getManagerByStatic(CommentConstances::COMMENT_TEMPLATE)->remove(array('_id' => new MongoId($id)));
            return $this->jsonResult(0);

        } catch (\MongoException $e) {
            return $this->jsonResult(1, '', '系统错误,请重试');
        }
    }

    /** ================================= 评 论记 录  ===================================== **/
    /**
     * 好评记录
     */
    public function actionLog()
    {

        self::apptracker("comment/log");

        $puid = \Yii::$app->user->identity->getParentUid();
        $logs_obj = CmCommentLog::find()->where(['platform' => 'aliexpress']);

        //如果有时间条件
        $starttime = 0;
        if (($stime = \Yii::$app->request->get('stime')) && strlen($stime)) {
            $starttime = strtotime($stime);
        }
        $endtime = time();
        if (($etime = \Yii::$app->request->get('etime')) && $etime < $endtime) {
            $endtime = strtotime($etime . '23:59:59');
        }
        $logs_obj->andWhere(['between', 'createtime', $starttime, $endtime]);
        //按店铺筛选
        if ($selleruserid = \Yii::$app->request->get('selleruser')) {
            $logs_obj->andWhere(['selleruserid' => $selleruserid]);
        }
        $logs_obj->andWhere(['<>', 'error_msg', '发送失败：It is already leave feedback.']);
        $logs_obj->andWhere(['<>', 'error_msg', 'It is already leave feedback.']);
        $logs_obj->andWhere(['<>', 'error_msg', '发送失败：The order can not be null.']);

        //排序信息
        $sort = new Sort([
            'attributes' => [
                'selleruserid',
                'is_success',
                'createtime',
                'order_source_order_id'
            ],
            'defaultOrder' => [
                // 'order_source_create_time'=>SORT_DESC
            ]
        ]);

        //分页

        $pages = new Pagination([
            'defaultPageSize' => 50,
            'pageSize' => \Yii::$app->request->get('per-page', 50),
            'totalCount' => $logs_obj->count(),
            'pageSizeLimit' => [50, 100, 200],//每页显示条数范围
            'params' => $_REQUEST,
        ]);

        $logs = $logs_obj
            ->offset($pages->offset)
            ->limit($pages->limit)
            ->orderBy('order_source_create_time DESC,createtime DESC')
            ->all();
        //查询出绑定的所有店铺信息
        $saasaliexpressusers = SaasAliexpressUser::find()->where(['uid' => $puid])->all();

        return $this->render('log', [
            'logs' => $logs,
            'sort' => $sort,
            'pages' => $pages,
            'aliexpressusers' => $saasaliexpressusers,
        ]);
    }

    public function actionLogV2()
    {

        self::apptracker("comment/log-v2");

        $puid = \Yii::$app->user->identity->getParentUid();
        $criteria = array('platform' => CommentConstances::ALIEXPRESS, 'uid' => $puid);

        //按店铺筛选
        if ($selleruserid = \Yii::$app->request->get('selleruser')) {
            $criteria['sellerUserId'] = $selleruserid;
        }

        if ($commentStatus = \Yii::$app->request->get('commentStatus')) {
            $commentStatus = intval($commentStatus);
            if ($commentStatus != -1)
                $criteria['isSuccess'] = $commentStatus;
        }

        if ($searchval = \Yii::$app->request->get('searchval')) {
            if ($searchval != "")
                $criteria['orderSourceOrderId'] = new \MongoRegex('/.*' . $searchval . '.*/');
        }
        $criteria['error_msg'] = array('$nin' => array('发送失败：It is already leave feedback.', 'It is already leave feedback.'));
//        var_dump($criteria);
//        die();
        $logCursor = CommentManagerFactory::getManagerByStatic(CommentConstances::COMMENT_LOG)->find($criteria);
        //排序信息
        $sort = new Sort([
            'attributes' => [
                'selleruserid',
                'is_success',
                'createtime',
                'order_source_order_id'
            ],
            'defaultOrder' => [
                // 'order_source_create_time'=>SORT_DESC
            ]
        ]);

        //分页

        $showpage = new Pagination([
            'defaultPageSize' => 50,
            'pageSize' => \Yii::$app->request->get('per-page', 50),
            'totalCount' => $logCursor->count(),
            'pageSizeLimit' => [50, 100, 200],//每页显示条数范围
            'params' => $_REQUEST,

        ]);


        $logs = $logCursor
            ->sort(array('createTime' => -1))
            ->skip($showpage->offset)
            ->limit($showpage->limit);
        $logs = iterator_to_array($logs);

        $saasaliexpressusers = SaasAliexpressUser::find()->where(['uid' => $puid])->all();
//

        return $this->render('logV2', [
            'logs' => $logs,
            'sort' => $sort,
            'pages' => $showpage,
            'aliexpressusers' => $saasaliexpressusers,
            'selleruserid' => $selleruserid,
            'commentStatus' => $commentStatus,
            'searchval' => $searchval,
        ]);
    }

    /**
     * 再次好评
     */
    public function actionRecomment()
    {
        if (!\Yii::$app->request->isAjax) return $this->jsonResult();
        if (!$ids = \Yii::$app->request->post('ids')) {
            return $this->jsonResult();
        }
        $ids = array_map(function ($id) {
            return intval($id);
        }, explode(',', $ids));
        //将获取到的Log信息处理
        $source_ids = [];
        $contents = [];
        foreach ($ids as $id) {
            $log = CmCommentLog::findOne($id);
            if (!$log) {
                return $this->jsonResult();
            }
            $source_ids[] = $log->order_source_order_id;
            $contents[$log->order_source_order_id] = $log->content;
        }
        if (count($ids) > 1) {
            self::apptracker("comment/batchrecomment");
        } else {
            self::apptracker("comment/recomment");
        }
        //将获取到的source_id 发送
        $result = CommentHelper::syncEvaluation($source_ids, '', $contents);
        if ($result) {
            return $this->jsonResult(0);
        }
        return $this->jsonResult(1, '', '网络通信错误');
    }

    //模态框控制器
    function actionModal()
    {
        return $this->renderAuto('modal', []);     // 增加新的方法！renderAuto
    }


}
