<?php

namespace eagle\modules\carrier\controllers;

use yii;
use yii\web\Controller;
use eagle\modules\carrier\models\SysCarrierAccount;
use eagle\modules\carrier\models\SysShippingService;
use eagle\modules\carrier\models\SysCarrier;
use eagle\modules\carrier\models\SysCarrierParam;
use yii\helpers\Html;
use common\helpers\Helper_Array;
use yii\helpers\Url;
use eagle\models\SysCountry;
use common\api\overseaWarehouseAPI\LB_WINITCNOverseaWarehouseAPI;
use common\api\carrierAPI\LB_YILONGCarrierAPI;
use common\api\overseaWarehouseAPI\winitAPI;
use yii\data\Pagination;
use eagle\modules\util\helpers\TranslateHelper;
use eagle\modules\amazon\apihelpers\AmazonApiHelper;
use common\api\wishinterface\WishInterface_Helper;
use common\api\aliexpressinterface\AliexpressInterface_Api;
use common\api\aliexpressinterface\AliexpressInterface_Helper;
use eagle\modules\order\helpers\WishOrderInterface;
use eagle\modules\dhgate\apihelpers\DhgateApiHelper;
use eagle\modules\carrier\models\MatchingRule;
use eagle\modules\platform\apihelpers\PlatformAccountApi;
use eagle\modules\carrier\apihelpers\CarrierApiHelper;
use eagle\modules\carrier\models\SysCarrierCustom;
use eagle\modules\carrier\helpers\ShippingServiceHelper;
use eagle\modules\lazada\apihelpers\LazadaApiHelper;
use eagle\modules\app\apihelpers\AppTrackerApiHelper;
use eagle\models\SysShippingMethod;
use eagle\modules\order\helpers\EnsogoOrderInterface;
use common\api\aliexpressinterfacev2\AliexpressInterface_Api_Qimen;


class ShippingserviceController extends \eagle\components\Controller
{
    public $enableCsrfValidation = false;
    public function actionIndex()
    {
    	return "<a href='/configuration/carrierconfig/index'>请使用新的流程入口</a>";
    	
    	AppTrackerApiHelper::actionLog("eagle_v2","/carrier/shippingservice/index");
    	$pageSize = isset($_GET['per-page'])?$_GET['per-page']:15;
    	if (Yii::$app->request->isPost){
    		$carrier_code = Yii::$app->request->post('carrier_code');
    		$is_used = Yii::$app->request->post('is_used');
    		$is_custom = Yii::$app->request->post('is_custom');
    		$carrier_account_id = Yii::$app->request->post('carrier_account_id');
    		$data = Yii::$app->request->post();
    	}else{
    		$carrier_code = Yii::$app->request->get('carrier_code');
    		$is_used = Yii::$app->request->get('is_used');
    		$is_custom = Yii::$app->request->get('is_custom');
    		$carrier_account_id = Yii::$app->request->get('carrier_account_id');
    		$data = Yii::$app->request->get();
    	}
    	//所有物流账号加运输服务
    	$account_objs = SysCarrierAccount::find()->where('is_used = 1')->all();
    	foreach ($account_objs as $account_obj){
    		$SCP_obj = SysCarrierParam::find()->where('carrier_code')->one();
    	}
    	 if(!empty($_GET['sort'])){
			$sort = $_GET['sort'];
			if( '-' == substr($sort,0,1) ){
				$sort = substr($sort,1);
				$order = 'desc';
			} else {
				$order = 'asc';
			}
		}
		if(!isset($sort))$sort = 'is_used';
		if(!isset($order))$order = 'desc';
    	$query= SysShippingService::find();
    	
    	if (isset($carrier_code) && strlen($carrier_code)){
    		$query->andWhere(['carrier_code'=>$carrier_code]);
    	}
    	
    	if (isset($is_used) && strlen($is_used)){
    		$query->andWhere(['is_used'=>$is_used]);
    	}
    	if (isset($is_custom) && $is_custom==1){
    		$query->andWhere(['is_custom'=>1]);
    	}else{
    		$query->andWhere(['is_custom'=>0]);
    	}
    	if (isset($carrier_account_id) && strlen($carrier_account_id)){
    		$query->andWhere(['carrier_account_id'=>$carrier_account_id]);
    	}
    	$pagination = new Pagination([
    			'defaultPageSize' => 15,
    			'pageSize' => $pageSize,
    			'totalCount' => $query->count(),
    			'pageSizeLimit'=>[15,200],//每页显示条数范围
    			'params'=>$data,
    			]);
    	$list['pagination'] = $pagination;
    	$sort_arr = array('is_used'=>'is_used desc','carrier_code'=>'carrier_code asc','create_time'=>'create_time asc','shipping_method_name'=>'shipping_method_name asc','service_name'=>'service_name asc','carrier_account_id'=>'carrier_account_id desc');
    	unset($sort_arr[$sort]);
    	$str = $sort.' '.$order.','.implode(',', $sort_arr);
    	//var_dump($str);die;
    	$query->orderBy($str);
    	$query->limit($pagination->limit);
    	$query->offset( $pagination->offset );
    	$list['data'] = $query->all();
        $carriers = CarrierApiHelper::getCarriers(true,$is_custom);
        asort($carriers);
        //查询出物流账号
        $accounts = Helper_Array::toHashmap(SysCarrierAccount::find()->select(['id','carrier_name'])->asArray()->all(), 'id','carrier_name');
        $url_arr = array_merge(['/carrier/shippingservice/index'],$data);
        $return_url = Url::to($url_arr);
        return $this->render('index',['list'=>$list,'carriers'=>$carriers,'accounts'=>$accounts,'search_data'=>$data,'return_url'=>$return_url]);
    }


    /*
     * 创建或修改运输服务
     * 增加标记发货默认推荐配置
     * modify by rice
     * modify date 2015-08-02
     */
    public function actionCreate(){
    	
    	AppTrackerApiHelper::actionLog("eagle_v2","/carrier/shippingservice/create");
        $id = empty($_GET['id'])?'':intval($_GET['id']);
        $service = array();
        if(!empty($id)){
            //如果有id传入 查询出对应内容
            $service = SysShippingService::find()->where(['id'=>$id])->one();
        }else {
        	$service = new SysShippingService();
        	if (Yii::$app->request->isGet){
        		$service->carrier_code =Yii::$app->request->get('carrier_code');
        	}
        }
        $errors = [];
        if (Yii::$app->request->isPost){
//         	print_r($_POST);
//         	exit;
        	
        	$return_url = \Yii::$app->request->post('return_url');
        	$id = empty($_POST['id'])?'':intval($_POST['id']);
        	if(empty($id)){
        		$service = new SysShippingService();
        		$service->create_time = time();
        	}else{
        		$service = SysShippingService::findOne($id);
        		$service->update_time = time();
        	}
        	//$service->carrier_code =Yii::$app->request->post('carrier_code');
        	$service->carrier_params =Yii::$app->request->post('carrier_params');
        	//$service->ship_address =Yii::$app->request->post('ship_address');
        	//$service->return_address =Yii::$app->request->post('return_address');
        	$service->is_used =Yii::$app->request->post('is_used');
        	
        	$service->service_name =Yii::$app->request->post('service_name');
        	$service->service_code =Yii::$app->request->post('service_code');
			//用于记录映射配置
        	$platform_shipping_mapping = Yii::$app->request->post('service_code');
        	//$service->auto_ship =Yii::$app->request->post('auto_ship');
        	$service->web =Yii::$app->request->post('web');
        	//$service->carrier_account_id =Yii::$app->request->post('carrier_account_id');
        	
        	$service->print_type = Yii::$app->request->post('print_type');
        	
        	$print_params = Yii::$app->request->post('lable_list');
        	
        	if(is_array($print_params)){
        		$service->print_params = array('label_littleboss'=>$print_params);
        	}
        	
        	if ($service->isNewRecord){
        		$count = SysShippingService::find()->where(['service_name'=>Yii::$app->request->post('service_name')])->count();
        	}else{
        		$count = SysShippingService::find()->where('service_name = :service_name and id <>:id',[':service_name'=>Yii::$app->request->post('service_name'),':id'=>$id])->count();
        	}
        	if ($count>0){
        		$errors[] = '运输服务名称重复';
        	}else{
        		if (\Yii::$app->request->post('is_used')==0){
        			$count=MatchingRule::find(['transportation_service_id'=>$id])->andWhere(['is_active'=>1])->count();
        			if ($count>0){
        				$errors[] = '请先关闭对应运输服务匹配规则！';
        			}
        		}else{
	        		$selleruserids_group = PlatformAccountApi::getAllPlatformOrderSelleruseridLabelMap();
	        		//检查用户绑定账号并判断是否选择标记发货物流
	        		$service_code=Yii::$app->request->post('service_code');
	        		
	        		if($service->carrier_code != 'lb_LGS'){
		        		foreach ($selleruserids_group as $platform=>$selleruserids){
		        			if (count($selleruserids)>0){
		        				if (strlen($service_code[$platform])==0){
		        					$errors[] = '请选择'.(empty(MatchingRule::$source[$platform]) ? '' : MatchingRule::$source[$platform]).'标记发货物流！';
		        				}
		        			}
		        		}
	        		}
        		}
        		if (empty($errors)){
        			if ($service->save()){
                        /*
                         * 增加标记发货默认推荐配置
                         * modify by rice
                         * modify date 2015-08-02
                         */
						foreach($platform_shipping_mapping as $platform => $mapping) {
							ShippingServiceHelper::saveConfigLogByShippingMapping($platform, $mapping);
						}
        				return $this->redirect($return_url);
        			}else{
        				$modelErrors = $service->getErrors();
        				foreach ($modelErrors as $error){
        					$errors[] = $error[0];
        				}
        			}
        		}
        	}
        }else{
        	$service->is_used =Yii::$app->request->get('is_used');
        }
        //查询出物流商
        $carrier = Helper_Array::toHashmap(SysCarrier::find()->orderBy('carrier_name')->select(['carrier_code','carrier_name'])->asArray()->all(), 'carrier_code','carrier_name');
        //国家
        $countrys = Helper_Array::toHashmap(SysCountry::find()->orderBy('country_en')->select(['country_code','country_en'])->asArray()->all(),'country_code','country_en');
        //查询出各大平台运输服务
        //阿里
       	//$ali = json_decode(file_get_contents(Yii::getAlias('@web').'docs/aliexpressServiceCode.json'));
       	$ali = AliexpressInterface_Helper::getShippingCodeNameMap();
       	//ebay暂时没用，需要用户填
        $ebay = json_decode(file_get_contents(Yii::getAlias('@web').'docs/ebayServiceCode.json'));
        //亚马逊
        $amazon = AmazonApiHelper::getShippingCodeNameMap();
        asort($amazon);
        //wish
        $wish = WishOrderInterface::getShippingCodeNameMap();
        asort($wish);
        
        $dhgate = DhgateApiHelper::getShippingCodeNameMap();
        $lazada = LazadaApiHelper::getShippingCodeNameMap();
        $linio = LazadaApiHelper::getLinioShippingCodeNameMap();
        $ensogo = EnsogoOrderInterface::getShippingCodeNameMap();
		/*
		 * 获取标记发货的默认推荐
		 * modify by rice
		 * modify date 2015-08-02
		 */
        $platform_mapping = $service->getAttribute('service_code');
		//$platform_mapping = null时表示第一次配置，则需要给与推荐信息
        if(is_null($platform_mapping)) {
            //to-do 取平台列表需要到公共的接口取，待改进
            $platform_mapping = array('ebay'=>'', 'amazon'=>'', 'aliexpress'=>'', 'wish'=>'', 'dhgate'=>'', 'cdiscount'=>'');
            foreach($platform_mapping as $platform => &$mapping) {
                if(!$mapping) {
                    $mapping = ShippingServiceHelper::getRecommendConfigByShippingMapping($platform); 
                }
            }
            $service->setAttribute('service_code', $platform_mapping);
        }
 
        if (Yii::$app->request->isGet){
        	$return_url = \Yii::$app->request->get('return_url');
        }
        
        $service['third_party_code'] = empty($service['third_party_code']) ? '' : $service['third_party_code'];
        
        $sysShippingMethod = SysShippingMethod::find()
        	->where(['carrier_code'=>$service['carrier_code'],'shipping_method_code'=>$service['shipping_method_code'],'third_party_code'=>$service['third_party_code']])->asArray()->one();
        
        return $this->render('create',['service'=>$service,'carrier'=>$carrier,'id'=>$id,
        		'countrys'=>$countrys,'ali'=>$ali,'ebay'=>$ebay,'amazon'=>$amazon,'wish'=>$wish,'dhgate'=>$dhgate,
        		'lazada'=>$lazada,'linio'=>$linio,'ensogo'=>$ensogo,'errors'=>$errors,'return_url'=>$return_url,
        		'sysShippingMethod'=>$sysShippingMethod]);
    }
    
    public  function actionCreatecustom(){
    	AppTrackerApiHelper::actionLog("eagle_v2","/carrier/shippingservice/createcustom");
    	 $id = empty($_GET['id'])?'':intval($_GET['id']);
        $service = array();
        if(!empty($id)){
            //如果有id传入 查询出对应内容
            $service = SysShippingService::find()->where(['id'=>$id])->one();
        }else {
        	$service = new SysShippingService();
        	if (Yii::$app->request->isGet){
        		$service->carrier_code =Yii::$app->request->get('carrier_code');
        	}
        }
        
        $errors = [];
        if (Yii::$app->request->isPost){
        	$return_url = \Yii::$app->request->post('return_url');
        	$id = empty($_POST['id'])?'':intval($_POST['id']);
        	if(empty($id)){
        		$service = new SysShippingService();
        		$service->create_time = time();
        	}else{
        		$service = SysShippingService::findOne($id);
        		$service->update_time = time();
        	}
        	$service->is_used =Yii::$app->request->post('is_used');
        	$service->carrier_code = Yii::$app->request->post('carrier_code');
        	$carrierCustom_obj = SysCarrierCustom::findOne(['carrier_code'=>$service->carrier_code]);
        	if ($carrierCustom_obj!=null){
        		$service->carrier_name = $carrierCustom_obj->carrier_name;
        	}
        	$service->service_name =Yii::$app->request->post('service_name');
        	$service->shipping_method_name =Yii::$app->request->post('service_name');
        	$service->service_code =Yii::$app->request->post('service_code');
        	$service->web =Yii::$app->request->post('web');
        	$service->is_custom =1;
        	$service->address = Yii::$app->request->post('address');
        	$service->custom_template_print = Yii::$app->request->post('custom_template_print');
        	if ($service->isNewRecord){
        		$count = SysShippingService::find()->where(['service_name'=>Yii::$app->request->post('service_name')])->count();
        	}else{
        		$count = SysShippingService::find()->where('service_name = :service_name and id <>:id',[':service_name'=>Yii::$app->request->post('service_name'),':id'=>$id])->count();
        	}
        	if ($count>0){
        		$errors[] = '运输服务名称重复';
        	}else{
        		if (\Yii::$app->request->post('is_used')==0){
        			$count=MatchingRule::find(['transportation_service_id'=>$id])->andWhere(['is_active'=>1])->count();
        			if ($count>0){
        				$errors[] = '请先关闭对应运输服务匹配规则！';
        			}
        		}else{
	        		$selleruserids_group = PlatformAccountApi::getAllPlatformOrderSelleruseridLabelMap();
	        		//检查用户绑定账号并判断是否选择标记发货物流
	        		$service_code=Yii::$app->request->post('service_code');
	        		foreach ($selleruserids_group as $platform=>$selleruserids){
	        			if (count($selleruserids)>0){
	        				if (strlen($service_code[$platform])==0){
	        					$errors[] = '请选择'.MatchingRule::$source[$platform].'标记发货物流！';
	        				}
	        			}
	        		}
        		}
        		if (empty($errors)){
        			if ($service->save()){
        				return $this->redirect($return_url);
        			}else{
        				$modelErrors = $service->getErrors();
        				foreach ($modelErrors as $error){
        					$errors[] = $error[0];
        				}
        			}
        		}
        	}
        }
        //查询出物流商
       // $carrier = CarrierApiHelper::getCarriers();
       $carrier =  CarrierApiHelper::getCustomCarriers();
        //国家
        $countrys = CarrierApiHelper::getcountrys();
        //查询出各大平台运输服务
       	$ali = AliexpressInterface_Helper::getShippingCodeNameMap();
       	//ebay暂时没用，需要用户填
        $ebay = json_decode(file_get_contents(Yii::getAlias('@web').'docs/ebayServiceCode.json'));
        //亚马逊
        $amazon = AmazonApiHelper::getShippingCodeNameMap();
        asort($amazon);
        //wish
        $wish = WishOrderInterface::getShippingCodeNameMap();
        asort($wish);
        
        $dhgate = DhgateApiHelper::getShippingCodeNameMap();
        $lazada = LazadaApiHelper::getShippingCodeNameMap();
        $linio = LazadaApiHelper::getLinioShippingCodeNameMap();
        $ensogo = EnsogoOrderInterface::getShippingCodeNameMap();
        if (Yii::$app->request->isGet){
        	$return_url = \Yii::$app->request->get('return_url');
        }
        return $this->render('createcustom',['service'=>$service,'carrier'=>$carrier,'id'=>$id,'countrys'=>$countrys,'ali'=>$ali,
        		'ebay'=>$ebay,'amazon'=>$amazon,'wish'=>$wish,'dhgate'=>$dhgate,'lazada'=>$lazada,'linio'=>$linio,'ensogo'=>$ensogo,'errors'=>$errors,'return_url'=>$return_url]);
    }
    //根据用户选择的物流商 加载对应参数和账号
    public function actionLoadParams(){    	
        if(!\Yii::$app->request->getIsAjax())return false;
        
        AppTrackerApiHelper::actionLog("eagle_v2","/carrier/shippingservice/load-params");
        
        $code = \Yii::$app->request->post('code');
        $id = \Yii::$app->request->post('id');
        if(empty($code))return false;
        $code = Html::encode($code);
        if ($id){
        	$service = SysShippingService::find()->where(['id'=>$id])->one();
        }else{
        	$service = new SysShippingService();
        }
        //根据code 查询参数
        $carrier = SysCarrier::find()->select(['carrier_type'])->where(['carrier_code'=>$code])->one();
        $warehouse = [];
        $warehouseService = [];
        if($carrier->carrier_type==1){
            //如果是海外仓 则直接在文件中获取仓库和运输方式信息
            require_once(\Yii::getAlias('@web').'docs/'.$code.'.php');
        }
        $params = SysCarrierParam::find()->where(['carrier_code'=>$code,'type'=>1])->orderBy('sort asc')->all();
        return $this->renderPartial('loadparams',['params'=>$params,'service'=>$service,'warehouse'=>$warehouse,'warehouseService'=>$warehouseService]);
    }
    
    //根据用户选择的物流商 加载对应参数和账号
    public function actionLoadAccount(){
    	if(!\Yii::$app->request->getIsAjax())return false;
    	$code = \Yii::$app->request->post('code');
    	$id = \Yii::$app->request->post('id');
    	if(empty($code))return false;
    	$code = Html::encode($code);
    	if ($id){
    		$service = SysShippingService::find()->where(['id'=>$id])->one();
    	}else{
    		$service = new SysShippingService();
    	}
    	//根据code 查询账号
    	$accounts_arr = SysCarrierAccount::find()->where(['carrier_code'=>$code,'is_used'=>1])->select(['id','carrier_name'])->asArray()->all();
    	$accounts = Helper_Array::toHashmap($accounts_arr, 'id','carrier_name');
    	return $this->renderPartial('loadaccount',['accounts'=>$accounts,'service'=>$service]);
    }
    
    public function actionOnoff(){
    	AppTrackerApiHelper::actionLog("eagle_v2","/carrier/shippingservice/onoff");
    	try {
    		$carrierAccountObj = SysShippingService::findOne(\Yii::$app->request->get('id'));
    		if (\Yii::$app->request->get('is_used')==0){
	    		$count=MatchingRule::find()->where(['transportation_service_id'=>\Yii::$app->request->get('id')])->andWhere(['is_active'=>1])->count();
	    		if ($count>0){
	    			exit(json_encode(array("code"=>"fail","message"=>'请先关闭对应运输服务匹配规则！')));
	    		}
    		}
    		$carrierAccountObj->is_used = \Yii::$app->request->get('is_used');
    		$carrierAccountObj->save();
    	}catch (\Exception $ex){
    		exit(json_encode(array("code"=>"fail","message"=>$ex->getMessage())));
    	}
    	exit(json_encode(array("code"=>"ok","message"=>TranslateHelper::t('操作成功！'))));
    }
    public function actionInit2(){
//     	$a	=	new winitAPI();
//     	echo $a->setToken();
    	$w	=	new LB_WINITCNOverseaWarehouseAPI;
    	$w->inspWarehouseList();die;
    	$api	= new LB_WINITCNOverseaWarehouseAPI();
    	$res	=	$api->inspWarehouseList();
    	exit($res);
    }
    public function actionInit3(){
    	//     	$a	=	new winitAPI();
    	//     	echo $a->setToken();
    	$w	=	new LB_WINITCNOverseaWarehouseAPI;
    	$w->winitProductlist();die;
    	$api	= new LB_WINITCNOverseaWarehouseAPI();
    	$res	=	$api->inspWarehouseList();
    	exit($res);
    }
    public function actionInit4(){
    	//     	$a	=	new winitAPI();
    	//     	echo $a->setToken();
    	$w	=	new LB_YILONGCarrierAPI();
    	$w->emskindlist();die;
    }
    
    public function actionGetservices()
    {
    	$api = new AliexpressInterface_Api();
    	$access_token = $api->getAccessToken ( 'cn1510671045' );
    	$api->access_token = $access_token;
    	$return = $api->listLogisticsService();
    	$arr = asort($return['result']);
    	$arr = array();
    	foreach ($return['result'] as $one){
    		$arr[$one['serviceName']]=$one['displayName'];
    	}
    	asort($arr);
    	foreach ($arr as $k=>$v){
    	echo '"'.$k.'"=>"'.$v.'",<br>';
    	}
    	die;
    
    }
    
    public function actionGetservicesV2()
    {
    	$api = new AliexpressInterface_Api_Qimen();
    	$return = $api->listLogisticsService(['id' => 'cn1510671045']);
    	//$arr = asort($return['result_list']['aeop_logistics_service_result']);
    	$arr = array();
    	foreach ($return as $one){
    		$arr[$one['service_name']]=$one['display_name'];
    	}
    	asort($arr);
    	foreach ($arr as $k=>$v){
    		echo '"'.$k.'"=>"'.$v.'",<br>';
    	}
    	die;
    
    }
    
    public function actionLoadaddress(){
    	if(!\Yii::$app->request->getIsAjax())return false;
    	
    	AppTrackerApiHelper::actionLog("eagle_v2","/carrier/shippingservice/loadaddress");
    	$carrier_code = \Yii::$app->request->post('carrier_code');
    	if(empty($carrier_code))return false;
    	if ($carrier_code){
    		$carrierCustom = SysCarrierCustom::find()->where(['carrier_code'=>$carrier_code])->one();
    	}else{
    		$carrierCustom = new SysCarrierCustom();
    	}
    	$id = \Yii::$app->request->post('id');
    	if ($id){
    		$service = SysShippingService::find()->where(['id'=>$id])->one();
    	}else{
    		$service = new SysShippingService();
    	}
    	$countrys = CarrierApiHelper::getcountrys('zh');
    	return $this->renderPartial('loadaddress',['carrierObj'=>$carrierCustom,'service'=>$service,'countrys'=>$countrys]);
    }
	
}
