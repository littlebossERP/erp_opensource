<?php namespace eagle\modules\assistant\controllers;

use yii\data\Pagination;
use yii\data\Sort;
use \Yii;
// models
use eagle\models\assistant\DpRule;
use eagle\models\assistant\DpEnable;
use eagle\models\assistant\DpTemplates;
use eagle\models\SysCountry;
use eagle\modules\assistant\models\SaasAliexpressUser;
use eagle\models\assistant\OmOrderMessageTemplate;

// helpers
use eagle\modules\assistant\helpers\HtmlHelper;
use eagle\modules\app\apihelpers\AppTrackerApiHelper;

class RuleController extends \eagle\components\Controller
{
	public $puid;
    public $rule;

	public function __construct(){
		call_user_func_array('parent::__construct',func_get_args());
    	$this->puid = \Yii::$app->user->identity->getParentUid();
        $this->rule = DpRule::find()->where([
            'status'=>1,
            'puid'=>$this->puid
        ]);
//        if($this->puid != 1505 && $this->puid != 602 && $this->puid != 552 && $this->puid != 1999 && $this->puid != 297 ){
//            $this->redirect('/');
//        }
	}
//    //相关用户写日志
//    private function setLog($type){
//        if($this->puid =='2444'){
//            \Yii::info('11-12,yht','file');
//            \Yii::info($type,'file');
//        }
//    }

    private function getRule($id=NULL){
        $rules = DpRule::find()
            ->where([
            'status'=>1,
            'puid'=>$this->puid
        ]);
        if($id){
            $rules->andWhere([
                'rule_id'=>$id
            ]);
        }
        return $rules;
    }

    // 翻译语言  Ajax
    public function actionTranslate(){
        $template = DpTemplates::findOne($_GET['id']);
        $lang = $_GET['language'];
        return $this->renderJson([
            'content'=>$template->$lang
        ]);
    }

    /**
     * 访问规则列表页
     * @return [type] [description]
     */
    public function actionList(){
        AppTrackerApiHelper::actionLog("AliCuiKuan", "/assistant/rule/list");
        //催款规则列表
    	$rules = $this->rule;
    	$page = new Pagination([
    	    'totalCount' => $rules->count(), 
    	    'defaultPageSize' => 10,
    	    'pageSizeLimit'=>[5,10,20,50],
            'pageParam' => 'page1',
            'pageSizeParam' => 'per-page1'
    	]);
    	$sort = new Sort(['attributes' => [
    		'timeout','is_active'
    	]]);
    	$country = SysCountry::find()->all();
    	$countries = [];
    	foreach($country as $c){
    		$countries[$c['country_code']] = $c['country_zh'];
    	}
    	$data = $rules
            ->offset($page->offset)
            ->limit($page->limit)
            ->orderBy($sort->orders)
            ->all();
        //催款规则列表 end
    	//只显示有权限的账号，lrq20170828
    	$selleruserids = array();
    	$account_data = \eagle\modules\platform\apihelpers\PlatformAccountApi::getPlatformAuthorizeAccounts('aliexpress');
    	foreach($account_data as $key => $val){
    		$selleruserids[] = $key;
    	}
        //店铺是否开启催款助手  start
        $shops = SaasAliexpressUser::find()
            ->where(['uid'=>$this->puid, 'sellerloginid' => $selleruserids]);
        if(!$shops->count()){
            $datas = [];
            $counts = 0;
        }else{
            foreach($shops->all() as $shop){;
                $ary_shop = DpEnable::findOne(['dp_puid'=>$shop->uid,'dp_shop_id'=>$shop->sellerloginid]);
                if(!isset($ary_shop->dp_shop_id)){
                    $enable = new DpEnable;
                    $enable->dp_shop_id = $shop->sellerloginid;
                    $enable->dp_puid = $shop->uid;
                    $enable->enable_status = 1;
                    $enable->platform = 'AliExpress';
                    $enable->create_time = date('Y-m-d H:i:s');
                    $enable->status = 1;
                    $enable->save();
                }
            }
            $shops = DpEnable::find()
                ->where(['dp_puid' => $this->puid, 'status'=>1, 'dp_shop_id' => $selleruserids]);
        }
//        //用户绑定的商铺已被删除，商铺表更新
//        $shop_user = DpEnable::find()
//            ->where(['dp_puid'=>$this->puid]);
//        foreach($shop_user->all() as $_user){
//            if($_user->sassAliexpressUser){
//                $err_enable = DpEnable::findOne(['dp_shop_id'=>$_user->dp_shop_id]);
//                $err_enable->status = 0;
//                $err_enable->save();
////                $err_enable->delete();
//            }
//        }
        $showpage = new Pagination([
            'totalCount' => isset($datas)?count($datas):$shops->count(),
            'defaultPageSize' => 5,
            'pageSizeLimit'=>[5,10,20,50],
            'pageParam' => 'page2',
            'pageSizeParam' => 'per-page2'
        ]);
        $datas = $shops
            ->offset($showpage->offset)
            ->limit($showpage->limit)
            ->all();
        //店铺是否开启催款助手  end
    	return $this->render('list',[
            'shops'=> $datas,
    		'rules' => $data,
    		'countries' => $countries,
    		'pages' => $page,
            'showpages' => $showpage,
    		'sort' => $sort
    	]);

    }

    // 规则编辑/新增页面
    public function actionAdd(){
        AppTrackerApiHelper::actionLog("AliCuiKuan", "/assistant/rule/add");
        $tpls = DpTemplates::find()->all();

        $languages = [];
        //if(!empty($tpls)) {
            $tpl = $tpls[0];
            foreach($tpl->attributes as $key=>$val){
                if(in_array($key, ['id','content_zh'])) continue;
                $languages[$key] = $tpl->attributeLabels()[$key]; // 从备注获取对应的语言名称
            }
        //}
        if(!isset($_GET['id']) || !($rule = $this->getRule($_GET['id'])->one())){
            $rule = new DpRule;
            $rule->timeout = 0;
            $rule->country = '*-';
            $rule->message_content = $tpl->content_en;
            $rule->expire_time = 7200;
        }
    	return $this->renderAjax('add',[
            'tpls' => $tpls,
            'rule' => $rule,
            'languages' => $languages
    	]);
    }

    public function actionCreate(){
        AppTrackerApiHelper::actionLog("AliCuiKuan", "/assistant/rule/create");
        // 检查是否已存在
        //获取相关参数
        $request = \Yii::$app->request;
        $rule_id = $request->post('rule_id');
        //获取模板
        $selModel_1 = 0;
        $selModel_2 = 0;
        $selModel_3 = 0;
        $selModel_4 = 0;
        //获取催款时间
        $timeout_1 = 0;
        $timeout_2 = 0;
        $timeout_3 = 0;
        $expiretime = $request->post('expiretime',0);
        //获取checkbox状态
        $onecheck = $request->post('onecheck');
        $twocheck = $request->post('twocheck');
        $threecheck = $request->post('threecheck');
        $fourcheck = $request->post('fourcheck');
        //根据checkbox状态控制内容
        if($onecheck === '1'){
            $selModel_1 = $request->post('selModel_1',0);
            $timeout_1 = $request->post('timeout1',0);
        }
        if($twocheck === '2'){
            $selModel_2 = $request->post('selModel_2',0);
            $timeout_2 = $request->post('timeout2',0);
            if( ceil($timeout_1/60+1) > ceil($timeout_2) ){
                return $this->renderJson([
                    'error'=>400,
                    'message'=>'第二次催款时间需大于第一次催款时间'
                ]);
            }
        }
        if($threecheck === '3'){
            $selModel_3 = $request->post('selModel_3',0);
            $timeout_3 = $request->post('timeout3',0);
            if( ceil($timeout_2+1) > ceil($timeout_3) ){
                return $this->renderJson([
                    'error'=>400,
                    'message'=>'第三次催款时间需大于第二次催款时间'
                ]);
            }
        }
        if($fourcheck === '4'){
            $selModel_4 = $request->post('selModel_4',0);
        }

        $countries = $request->post('countries');
        //判断是否选择所有国家
        $countrys = SysCountry::find()->count();
        if( $countrys == count($countries)){
            $countries = [] ;
            $countries[0] = '*';
        }
        $errCountries = [];
        $ary_countries = HtmlHelper::getSysCountries();
        //判断规则内是否已存在所有国家选项
        foreach($countries as $country){
            $rules = DpRule::find()
                ->where([
                    'status'=>1,
                    'puid'=>$this->puid
                ])->andWhere('FIND_IN_SET(:country,`country`)',[
                    ':country' => $country
                ])->andWhere(['!=','rule_id',$rule_id]);
            if($rules->count()){
                $errCountries[] = $country=='*'?'所有国家':$ary_countries[$country];
            }
        }

        if(isset($_POST['rule_id']) && $_POST['rule_id']){
            if($rule = $this->getRule($_POST['rule_id'])->one()){
                $rule->status = 0;
                $rule->save();
            }
        }
        if(count($errCountries)){
            return $this->renderJson([
                'error'=>400,
                'message'=>implode(',',$errCountries).' 已经存在匹配的规则'
            ]);
        }
        if($onecheck === '' && $twocheck == ''&& $threecheck == ''&& $fourcheck === '' ){
            return $this->renderJson([
                'error'=>400,
                'message'=>'请至少选择一项催款设置！'
            ]);
        }
        //判断催款时间
        if($timeout_1>0){
            if($selModel_1 == 0){
                return $this->renderJson([
                    'error'=>400,
                    'message'=>'第一次催款：请选择相应催款模板！'
                ]);
            }
        }
        if($timeout_2>0){
            if($selModel_2 == 0){
                return $this->renderJson([
                    'error'=>400,
                    'message'=>'第二次催款：请选择相应催款模板！'
                ]);
            }
        }
        if($timeout_3>0){
            if($selModel_3 == 0){
                return $this->renderJson([
                    'error'=>400,
                    'message'=>'第三次催款：请选择相应催款模板！'
                ]);
            }
        }
        if($timeout_1 == 0 && $timeout_2 == 0 && $timeout_3 == 0){
            if($selModel_4 == 0){
                return $this->renderJson([
                    'error'=>400,
                    'message'=>'已付款留言：请选择相应留言模板！'
                ]);
            }
        }



        // 写入数据库
    	$rule = new DpRule();
    	$rule->puid = $this->puid;
    	$rule->country = implode(',',$countries);
    	$rule->create_time = date('Y-m-d H:i:s');
    	$rule->timeout =$timeout_1*60;
        $rule->timeout2 =$timeout_2*3600;
        $rule->timeout3 =$timeout_3*3600;
    	$rule->min_money = $_POST['min_money'];
    	$rule->max_money = $_POST['max_money'];
        $rule->expire_time = $expiretime*3600;
        $rule->message_content = $selModel_1;
        $rule->message_content2 = $selModel_2;
        $rule->message_content3 = $selModel_3;
        $rule->order_message = $selModel_4;

        $rule->is_active = 2;

    	return $this->renderJson([
    		'error'=>$rule->save()?0:500
    	]);
    }

    public function actionDelete(){
        AppTrackerApiHelper::actionLog("AliCuiKuan", "/assistant/rule/delete");
        $rule = DpRule::findOne($_POST['rule_id']);
        if($rule->puid == $this->puid){
            $rule->status = 0;
            if($rule->save()){
                $result = ['error'=>0,'message'=>'ok'];
            }else{
                $result = ['error'=>500,'message'=>'fail'];
            }
        }else{
            $result = ['error'=>403,'message'=>'forbidden'];
        }
        return $this->renderJson($result);
    }


    // 店铺启用设置页面
    public function actionEnable(){

        AppTrackerApiHelper::actionLog("AliCuiKuan", "/assistant/rule/enable");
        $shops = SaasAliexpressUser::find()
            ->where(['uid'=>$this->puid]);

        if(!$shops->count()){
            $data = [];
            $count = 0;
        }else{
            foreach($shops->all() as $shop){
                if(!$shop->dpEnable){
                    $enable = new DpEnable;
                    $enable->dp_shop_id = $shop->sellerloginid;
                    $enable->dp_puid = $shop->uid;
                    $enable->enable_status = 1;
                    $enable->platform = 'AliExpress';
                    $enable->create_time = date('Y-m-d H:i:s');
                    $enable->status = 1;
                    $enable->save();
                }
            }
            $shops = DpEnable::find()
                ->where(['dp_puid' => $this->puid]);
        }
        $page = new Pagination([
            'totalCount' => isset($data)?count($data):$shops->count(), 
            'defaultPageSize' => 10,
            'pageSizeLimit'=>[5,10,20,50]
        ]);
        $data = $shops
                ->offset($page->offset)
                ->limit($page->limit)
                ->all();
        return $this->render('enablelist',[
            'shops' => $data,
            'pages' => $page
        ]);
    }





    public function actionTest(){
        $url = parse_url($_SERVER['HTTP_REFERER'])['path'];
        var_dump($url);

    }

    public function actionTest2(){
        return $this->renderModal('test',[],'abc');
    }


    public function actionShow(){
        $request = \Yii::$app->request;
        $template = $request->post('m_id','');
        $tpl = OmOrderMessageTemplate::findOne($template);
        return $this->renderAjax('showtpl',[
            'tpl' => $tpl
        ]);

    }





}
