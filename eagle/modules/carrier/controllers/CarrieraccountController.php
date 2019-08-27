<?php

namespace eagle\modules\carrier\controllers;

use \Yii;
use yii\helpers\Url;
use \eagle\components\Controller;
use eagle\modules\carrier\models\SysCarrierAccount;
use eagle\modules\carrier\models\SysCarrier;
use eagle\modules\carrier\helpers\CarrierHelper;
use eagle\modules\carrier\models\SysCarrierParam;
use common\helpers\Helper_Array;
use eagle\models\SysCountry;
use eagle\models\SysShippingMethod;
use eagle\modules\carrier\models\SysShippingService;
use yii\data\Pagination;
use eagle\modules\util\helpers\TranslateHelper;
use eagle\modules\app\apihelpers\AppTrackerApiHelper;
use eagle\modules\carrier\models\CarrierUserUse;
use eagle\modules\util\models\LabelTip;

/*
 * 物流账号管理
 */
class CarrieraccountController extends Controller {
    public $enableCsrfValidation = false;
    public function actionIndex()
    {
    	return "<a href='/configuration/carrierconfig/index'>请使用新的流程入口</a>";
    	
    	AppTrackerApiHelper::actionLog("eagle_v2","/carrier/carrieraccount/index");
    	$pageSize = isset($_GET['per-page'])?$_GET['per-page']:15;
    	if (Yii::$app->request->isPost){
    		$carrier_code = Yii::$app->request->post('carrier_code');
    		$carrier_type = Yii::$app->request->post('carrier_type');
    		$is_used = Yii::$app->request->post('is_used');
    		$data = Yii::$app->request->post();
    	}else{
    		$carrier_code = Yii::$app->request->get('carrier_code');
    		$carrier_type = Yii::$app->request->get('carrier_type');
    		$is_used = Yii::$app->request->get('is_used');
    		$data = Yii::$app->request->get();
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
		
		//只显示用户使用中的物流商
		$useCarrierAccount=SysCarrierAccount::find()->select('carrier_code')->groupBy('carrier_code')->asArray()->all();
		$useCarrierAccount = Helper_Array::getCols($useCarrierAccount,'carrier_code');
		
		//查询出物流商
		$carrier = SysCarrier::find()->orderBy('carrier_name asc')->select(['carrier_code','carrier_name'])->where(['carrier_code'=>$useCarrierAccount])->asArray()->all();
		$carrier = Helper_Array::toHashmap($carrier,'carrier_code','carrier_name');
    	$query=SysCarrierAccount::find();
    	if (isset($carrier_code) && strlen($carrier_code)){
    		$query->andWhere(['carrier_code'=>$carrier_code]);
    	}
    	 
    	if (isset($carrier_type) && strlen($carrier_type)){
    		$query->andWhere(['carrier_type'=>$carrier_type]);
    	}
    	if (isset($is_used) && strlen($is_used)){
    		$query->andWhere(['is_used'=>$is_used]);
    	}
        $pagination = new Pagination([
        		'defaultPageSize' => 15,
        		'pageSize' => $pageSize,
        		'totalCount' => $query->count(),
        		'pageSizeLimit'=>[15,200],//每页显示条数范围
        		'params'=>$data,
        		]);
        $list['pagination'] = $pagination;
    	$sort_arr = array('is_used'=>'is_used desc','carrier_type'=>'carrier_type asc','carrier_code'=>'carrier_code asc','create_time'=>'create_time asc','carrier_name'=>'carrier_name asc');
    	unset($sort_arr[$sort]);
    	$str = $sort.' '.$order.','.implode(',', $sort_arr);
    	//var_dump($str);die;
    	$query->orderBy($str);
    	$query->limit($pagination->limit);
    	$query->offset( $pagination->offset );
    	$list['data'] = $query->all();
    	
    	$url_arr = array_merge(['/carrier/carrieraccount/index'],$data);
    	$return_url = Url::to($url_arr);
        return $this->render('index',['list'=>$list,'carrier'=>$carrier,'search_data'=>$data,'return_url'=>$return_url]);
    }


    /*
     *  创建或修改物流商帐号
     *  modify by rice
     *  last modify date 2015-07-31
     */
    public function actionCreate(){
    	
    	AppTrackerApiHelper::actionLog("eagle_v2","/carrier/carrieraccount/create");
        $id = intval(Yii::$app->request->get('id'));
        if(!empty($id)){
            //如果有id传入 查询出对应内容
            $account = SysCarrierAccount::findOne($id);
        }else{
        	$account = new SysCarrierAccount();
        	$account->carrier_code = \Yii::$app->request->get('carrier_code');
        	$account->is_used = 1;
        }

        //查询所有物流商数据
        $carrier = SysCarrier::find()->select(['carrier_code','carrier_name'])->orderBy('carrier_code asc')->asArray()->all();
        //转换数据结构array('carrier_code'=>'carrier_name')
        $carrier = Helper_Array::toHashmap($carrier,'carrier_code','carrier_name');

        // modify by rice 2015-07-31 载入地址库数据准备开始
        //查询该用户所有的物流账号地址库记录,用于载入地址库方案
        $account_data = SysCarrierAccount::find()->select(array('id', 'carrier_name', 'address'))->where(array('is_used'=>1, 'carrier_type'=>0))->asArray()->all();
        //var_dump($account_data);exit;
        $account_list = Helper_Array::toHashmap($account_data, 'id', 'carrier_name');
        $account_list[0] = '';
        ksort($account_list);

        //address字段反序列化
        $account_address_library = [];
        foreach($account_data as $k => $v) {
            $account_address_library[$v['id']] = unserialize($v['address']);
        }//var_dump($account_address_library);exit;
        $account_address_library = json_encode($account_address_library);
        // modify by rice 2015-07-31 载入地址库数据准备结束

        $errors = [];
        if (Yii::$app->request->isPost){
        	//用于判断是否可以继续保存
        	$isSave = true;
        	
        	$return_url = \Yii::$app->request->post('return_url');
        	$id = empty($_POST['id'])?'':intval($_POST['id']);
        	if(empty($id)){
        		$account = new SysCarrierAccount();
        		$account->create_time = time();
        	}else{
        		$account = SysCarrierAccount::findOne($id);
        		$account->update_time = time();
        	}
        	//对数据进行转义处理
        	$Arr = CarrierHelper::formatData($_POST);
        	
        	if($Arr['carrier_code'] == 'rtbcompany')
        		$tmpcarrier_code = $Arr['carrierrtb_code'];
        	else
        		$tmpcarrier_code = $Arr['carrier_code'];
        	
        	if(!empty($id)){
        		if($tmpcarrier_code != $account->carrier_code){
        			$errors[] = '编辑时不能更换物流商';
        			$isSave = false;
        		}
        	}
        	
        	$account->carrier_code = $tmpcarrier_code;
        	$account->carrier_name = $Arr['carrier_name'];
        	$Carrier = SysCarrier::findOne($account->carrier_code);
        	$account->carrier_type = $Carrier['carrier_type'];
        	$account->is_used = $Arr['is_used'];
        	$account->user_id = \Yii::$app->user->id;
        	$account->api_params = isset($Arr['carrier_params'])?$Arr['carrier_params']:"";
        	$account->warehouse = isset($Arr['warehouse'])?$Arr['warehouse']:array();
        	//处理地址部分
        	$tmp = array();
        	$address_list = ['shippingfrom','pickupaddress','returnaddress','shippingfrom_en'];
        	foreach($address_list as $v){
        		if(isset($Arr[$v]))$tmp[$v] = $Arr[$v];
        	}
        	$account->address = $tmp;
        	
        	//账号昵称不能重复
        	if ($account->isNewRecord){
        		$count = SysCarrierAccount::find()->where(['carrier_name'=>$Arr['carrier_name']])->count();
        	}else{
        		$count = SysCarrierAccount::find()->where('carrier_name = :carrier_name and id <>:id',[':carrier_name'=>$Arr['carrier_name'],':id'=>$id])->count();
        	}
        	
        	if ($count>0){
        		$errors[] = '账号昵称重复';
        		$isSave = false;
        	}
        		
        	if($isSave === true){
        		if ($account->save()){
        			try{
        				//当新增物流商时需要向managedb记录user绑定了哪些物流
        				$carrierUserUse = CarrierUserUse::find()->where(['carrier_account_id' => $account->id, 'puid' => \Yii::$app->subdb->getCurrentPuid(),'carrier_code' => $account->carrier_code])->one();
        				 
        				if($carrierUserUse === null){
        					$carrierUserUse = new CarrierUserUse();
        					$carrierUserUse->puid = \Yii::$app->subdb->getCurrentPuid();
        					$carrierUserUse->carrier_code = $account->carrier_code;
        					$carrierUserUse->carrier_account_id = $account->id;
        				}
        				
        				$carrierUserUse->is_used = $account->is_used;
        				
        				if((substr($account->carrier_code,-10) == 'rtbcompany') || ($account->carrier_code == 'lb_haoyuan')){
        					$carrierUserUse->param1 = $account->api_params['appToken'];
        					$carrierUserUse->param2 = $account->api_params['appKey'];
        				}
        				$carrierUserUse->save(false);
        			}catch(\Exception $ex){
        				//暂时不作任何处理
        			}
        			
					//添加帐号成功后，添加或更新运输方式
					$result = CarrierHelper::refreshShippingMethod($account,$carrier);
					if($result){print_r($result);die;}
					return $this->redirect($return_url);
        		}else{
        			$modelErrors = $account->getErrors();
        			foreach ($modelErrors as $error){
        				$errors[] = $error[0];
        			}
        		}
        	}
        }
        if (Yii::$app->request->isGet){
        	 $return_url = \Yii::$app->request->get('return_url');
        }
        
        foreach ($carrier as $carrierKey => $carrierVal){
        	if(substr($carrierKey,-10) == 'rtbcompany'){
        		$carrier['rtbcompany'][$carrierKey] = $carrierVal;
        		
        		unset($carrier[$carrierKey]);
        	}
        }
        
        arsort($carrier);
        
        return $this->render('create', ['account'=>$account,
                                        'carrier'=>$carrier,
                                        'errors'=>$errors,
                                        'return_url'=>$return_url,
                                        'account_list'=>$account_list,
                                        'account_address_library'=>$account_address_library
                                        ]
                            );
    }


    /*
     * 自动加载表单信息（html整体加载）
     * modify by rice
     * last modify date 2015-07-31
     */
    //查询出物流商认证参数
    public function actionLoadParams() {
    	AppTrackerApiHelper::actionLog("eagle_v2","/carrier/carrieraccount/load-params");
    	if(!Yii::$app->request->getIsAjax())return false;
    	$code = Yii::$app->request->post('code');
    	$id = Yii::$app->request->post('id');
    	//地址类型
    	$address_list = CarrierHelper::$address_list;
    	//物流商对象
    	$carrierObj = SysCarrier::find()->where(['carrier_code'=>$code])->one();
    	if ($carrierObj->carrier_type == 1){
    		require_once(\Yii::getAlias('@web').'docs/'.$code.'.php');
    	}else{
    		$warehouse=[];
    	}
    	//物流商接口请求认证参数
    	$params = SysCarrierParam::find()->where(['carrier_code'=>$code,'type'=>0])->orderby('sort asc')->asArray()->all();
    	//国家
    	$country = Helper_Array::toHashmap(SysCountry::find()->select(['country_zh','country_code'])->asArray()->all(), 'country_code','country_zh');
        //认证参数解释
        $qtipKeyArr= LabelTip::find()->asArray()->all();
    	//物流账号
    	if ($id>0){
            //编辑模式下查询物流账号的信息，用来预载入
    		$carrierAccountObj = SysCarrierAccount::findOne($id);
    	}else{
    		$carrierAccountObj = new SysCarrierAccount();
    	}
    	return $this->renderPartial('loadparams',[
    			'params'=>$params,
    			'country'=>$country,
    			'address_list'=>$address_list,
    			'carrierObj'=>$carrierObj,
    			'carrierAccountObj'=>$carrierAccountObj,
    			'warehouse'=>$warehouse,
                'qtipKeyArr'=>$qtipKeyArr,
    	]);
    }
    
    public function actionOnoff(){    	
    	AppTrackerApiHelper::actionLog("eagle_v2","/carrier/carrieraccount/onoff");
    	try {
    		$carrierAccountObj = SysCarrierAccount::findOne(\Yii::$app->request->get('id'));
    		$carrierAccountObj->is_used = \Yii::$app->request->get('is_used');
    		$carrierAccountObj->save();
    		
    		try{
    			$carrierUserUse = CarrierUserUse::find()->where(['carrier_account_id' => $carrierAccountObj->id, 'puid' => \Yii::$app->subdb->getCurrentPuid(),'carrier_code' => $carrierAccountObj->carrier_code])->one();
    			 
    			if($carrierUserUse === null){
    				$carrierUserUse = new CarrierUserUse();
    				$carrierUserUse->puid = \Yii::$app->subdb->getCurrentPuid();
    				$carrierUserUse->carrier_code = $carrierAccountObj->carrier_code;
    				$carrierUserUse->carrier_account_id = $carrierAccountObj->id;
    			}
    		
    			$carrierUserUse->is_used = $carrierAccountObj->is_used;
    		
    			if((substr($carrierAccountObj->carrier_code,-10) == 'rtbcompany') || ($carrierAccountObj->carrier_code == 'lb_haoyuan')){
    				$carrierUserUse->param1 = $carrierAccountObj->api_params['appToken'];
    				$carrierUserUse->param2 = $carrierAccountObj->api_params['appKey'];
    			}
    			$carrierUserUse->save(false);
    		}catch(\Exception $ex){
    			//暂时不作任何处理
    		}
    	}catch (\Exception $ex){
    		exit(json_encode(array("code"=>"fail","message"=>$ex->getMessage())));
    	}
    	exit(json_encode(array("code"=>"ok","message"=>TranslateHelper::t('操作成功！'))));
    }

    /*
     * 获取总库中的运输服务，更新到用户数据库中
     */
    public static function actionRefreshshippingservice(){
    	AppTrackerApiHelper::actionLog("eagle_v2","/carrier/carrieraccount/refreshshippingservice");
        $id = \Yii::$app->request->get('carrier_account_id');
        //查询出物流商
        $carrier = SysCarrier::find()->orderBy('carrier_name asc')->select(['carrier_code','carrier_name'])->asArray()->all();
        $carrier = Helper_Array::toHashmap($carrier,'carrier_code','carrier_name');

        $account = SysCarrierAccount::findOne($id);

        $result = CarrierHelper::refreshShippingMethod($account,$carrier);
        if($result)return print_r($result,1);
        return 'success';
    }
}
