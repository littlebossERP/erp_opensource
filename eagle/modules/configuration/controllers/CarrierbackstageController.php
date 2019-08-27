<?php

namespace eagle\modules\configuration\controllers;

use Yii;
use yii\filters\VerbFilter;
use \Exception;
use yii\web\Controller;
use yii\filters\AccessControl;
use yii\data\Pagination;
use eagle\modules\carrier\models\SysCarrier;
use eagle\modules\carrier\helpers\CarrierHelper;
use eagle\modules\carrier\models\SysCarrierParam;
use eagle\models\SysShippingMethod;
use common\helpers\Helper_Array;
use eagle\modules\permission\apihelpers\UserApiHelper;

class CarrierbackstageController extends \eagle\components\Controller
{
	public $enableCsrfValidation = FALSE;
	
	//添加权限控制是否可以打开该controller
	public function beforeAction($action){
	
	    $isMain = UserApiHelper::isMainAccount();
	    if($isMain)
	        return parent::beforeAction($action);
	    
	    
        echo $this->render('//site/error', ['name'=>"物流设置", 'message'=>"账号无权限操作。"]);
        return false;
	}
	
	//物流商管理入口
    public function actionIndex()
    {
//         AppTrackerApiHelper::actionLog("eagle_v2","/carrier/carrier/index");
    	 if(!empty($_GET['sort'])){
			$sort = $_GET['sort'];
			if( '-' == substr($sort,0,1) ){
				$sort = substr($sort,1);
				$order = 'desc';
			} else {
				$order = 'asc';
			}
		}
		if(!isset($sort))$sort = 'create_time';
		if(!isset($order))$order = 'desc';

    	$carriers = SysCarrier::find()->orderBy($sort.' '.$order)->all();
        return $this->render('index',['carriers'=>$carriers]);
    }
    
    //创建或修改物流商
    public function actionCreate(){
//     	AppTrackerApiHelper::actionLog("eagle_v2","/carrier/carrier/create");
    	$carrier = array();
    	//判断物流商代码
    	$code = empty($_REQUEST['code'])?'':htmlentities($_REQUEST['code']);
    	if(!empty($_POST['check'])){
    		if(!empty($code)){
    			$c_obj = SysCarrier::findOne($code);
    			$c_obj->update_time = time();
    		}else{
    			$c_obj = new SysCarrier();
    			$c_obj->carrier_code = $_POST['carrier_code'];
    			$c_obj->create_time = time();
    		}
    		$c_obj->carrier_name = $_POST['carrier_name'];
    		$c_obj->carrier_type = $_POST['carrier_type'];
    		$c_obj->api_class = $_POST['api_class'];
    		$c_obj->address_list = isset($_POST['address_list'])?$_POST['address_list']:'';
    		$c_obj->is_active = isset($_POST['is_active']) ? 1 : 0;
    		$c_obj->help_url = $_POST['help_url'];
    		
    		return $c_obj->save();
    	}
    	if(!empty($code)){
    		$c = SysCarrier::findOne($code);
    		$carrier = empty($c)?'':$c->attributes;
    	}
    	return $this->renderPartial('create',['carrier'=>$carrier,'code'=>$code]);
    }
    
    //物流商对应参数设置入口
    public function actionParams(){
//     	AppTrackerApiHelper::actionLog("eagle_v2","/carrier/carrier/params");
    	//判断如果有提交 执行参数的添加
    	if(isset($_POST['check']) && $_POST['check']==1){
    		unset($_POST['check']);
    		$params_value = array();
    		//判断传过来的参数值格式是否合法
    		foreach($_POST['carrier_param_value'] as $k=> $v){
    			if(in_array($_POST['display_type'][$k], array('text','hidden')))continue;
    			$re = CarrierHelper::checkValues($v);
    			if(!$re)return 'paramserror';
    			//将参数拼接为数组
    			$params_value[$k] = $re;
    		}
    		$code = $_POST['code'];
    		if(empty($code))return 'error';
    		unset($_POST['code']);
    		$count = count($_POST['carrier_param_name']);
    		// var_dump($_POST);die;
    		for($i = 0;$i<$count;$i++){
    			$carrier_param = new SysCarrierParam();
    			//执行添加新参数
    			foreach($_POST as $key=>$value){
    				//验证参数的合法性 排除空值
    				// if(empty($value[$i]) && strlen($value[$i]) <1){
    				//     if($key != 'carrier_param_value' && $_POST['display_type'][$i] != 'text')return 'error';
    				//     else $f = 1;
    				// }
    				$carrier_param->$key = $value[$i];
    			}
    			// if(!isset($f)){
    			$carrier_param->carrier_param_value = @$params_value[$i];
    			// }
    			$carrier_param->carrier_code = $code;
    			$carrier_param->create_time = time();
    			$addResult = false;
    			if($carrier_param->validate()){
    				//删除所有参数 只执行一次
    				if($i==0){
    					$re = CarrierHelper::deleteParams($code);
    					if(!$re){
    						return 'error';
    					}
    				}
    				$addResult = $carrier_param->save();
    			}
    			if(!$addResult){
    				return 'error';
    			}
    			if(isset($f))unset($f);
    		}
    		return 'success';
    	}
    	$carrier_param = array();
    	//判断物流商代码
    	if(!empty($_GET['code'])){
    		$carrier_param = SysCarrierParam::find()->where(['carrier_code'=>$_GET['code']])->orderBy('sort asc')->all();
    	}
    	return $this->render('params',['carrier_param'=>$carrier_param,'code'=>$_GET['code']]);
    }
    
    //某个物流商运输方式详细
    public function actionChannelList(){
    	$carrier_method = array();
    	//判断物流商代码
    	if(!empty($_GET['code'])){
    		$carrier_method = SysShippingMethod::find()->where(['carrier_code'=>$_GET['code']])->orderBy('is_close')->asArray()->all();
    	}
    	
    	return $this->render('channelList',['carrier_method'=>$carrier_method,'code'=>$_GET['code'],'carrier_name'=>$_GET['carrier_name']]);
    }
    
    //物流商运输方式更新接口
    public function actionCreateChannel(){
    	//判断物流商代码
    	$code = empty($_REQUEST['code'])?'':htmlentities($_REQUEST['code']);
    	$channel_str = empty($_REQUEST['channel_textarea']) ? '' : $_REQUEST['channel_textarea'];
    	
    	$obj = SysCarrier::findOne(['carrier_code'=>$code]);
    	
    	if($obj === null){
    		return false;
    	}
    	
    	if(!empty($_POST['check'])){
    		if($obj->carrier_type == 1){
    			//海外仓:在文件中添加对应的【物流商代码.php】文件 直接读取该文件生成物流运输方式
    			require_once(dirname(dirname(__DIR__)).DIRECTORY_SEPARATOR."eagle".DIRECTORY_SEPARATOR."web".DIRECTORY_SEPARATOR."docs".DIRECTORY_SEPARATOR.$code.'.php');
    			
    			foreach ($warehouse as $k=>$v){
    				foreach ($warehouseService[$k] as $shipcode=>$shipname){
    					$ship_obj = SysShippingMethod::find()->where('carrier_code = :carrier_code and third_party_code=:third_party_code and shipping_method_code=:shipping_method_code',[':carrier_code'=>$code,':third_party_code'=>(string)$k,':shipping_method_code'=>(string)$shipcode])->one();
    					if ($ship_obj===null){
    						$ship_obj = new SysShippingMethod();
    						$ship_obj->create_time= time();
    					}
    					$ship_obj->carrier_code = $obj->carrier_code;
    					$ship_obj->shipping_method_code = (string)$shipcode;
    					$ship_obj->shipping_method_name= (string)$shipname;
    					$ship_obj->update_time= time();
    					$ship_obj->third_party_code= (string)$k;
    					$ship_obj->template= (string)$v;
    					if (!$ship_obj->save()){
    						print_r($ship_obj->getErrors());die;
    					}
    				}
    			}
    		}else{
    			//货代生成运输方式
    			
    			//增加兼容json格式
    			$channel_arr = json_decode($channel_str,true);
    			
    			if(json_last_error() == JSON_ERROR_NONE){
    				foreach ($channel_arr as $channel_val){
    					if(!isset($channel_val['shipping_method_code'])){
    						print_r('失败');die;
    					}
    					
    					$ship_obj = SysShippingMethod::find()->where(['carrier_code'=>$code,'shipping_method_code'=>$channel_val['shipping_method_code']])->one();
    					if ($ship_obj===null){
    						$ship_obj = new SysShippingMethod();
    						$ship_obj->create_time= time();
    					}
    					$ship_obj->carrier_code = (string)$code;
    					$ship_obj->shipping_method_code = (string)$channel_val['shipping_method_code'];
    					$ship_obj->shipping_method_name= (string)$channel_val['shipping_method_name'];
    					
    					if(isset($channel_val['service_code'])){
    						$ship_obj->service_code= (string)$channel_val['service_code'];
    					}
    					
    					$ship_obj->update_time= time();
    					if (!$ship_obj->save()){
    						print_r($ship_obj->getErrors());die;
    					}
    				}
    			}else{
    				if(empty($channel_str) || !strpos($channel_str,';') || !strpos($channel_str,':'))return false;
    				
    				$params = explode(';',rtrim($channel_str,';'));
    				Helper_Array::removeEmpty($params);
    				$result = array();
    				foreach($params as $v){
    					$value = explode(':',$v);
    					if(count($value)<2)return false;
    					$result[$value[0]] = $value[1];
    				}
    				foreach ( $result as $shipcode=>$shipname){
    					$ship_obj = SysShippingMethod::find()->where(['carrier_code'=>$code,'shipping_method_code'=>$shipcode])->one();
    					if ($ship_obj===null){
    						$ship_obj = new SysShippingMethod();
    						$ship_obj->create_time= time();
    					}
    					$ship_obj->carrier_code = (string)$code;
    					$ship_obj->shipping_method_code = (string)$shipcode;
    					$ship_obj->shipping_method_name= (string)$shipname;
    					$ship_obj->update_time= time();
    					if (!$ship_obj->save()){
    						print_r($ship_obj->getErrors());die;
    					}
    				}
    			}
    		}
    		
    		return true;
    	}
    	
    	return $this->renderPartial('createChannel',['code'=>$code,'carrier_type'=>$obj->carrier_type]);
    }
    
    public function actionEditShippingPrint(){
    	//判断物流商代码
    	$code = empty($_REQUEST['code'])?'':htmlentities($_REQUEST['code']);
    	$methodCode = !isset($_REQUEST['methodCode'])?'':htmlentities($_REQUEST['methodCode']);
    	$thirdPartyCode = empty($_REQUEST['thirdPartyCode'])?'':htmlentities($_REQUEST['thirdPartyCode']);
    	
    	$shippingMethodOne = SysShippingMethod::find()->where(['carrier_code'=>$code,'shipping_method_code'=>$methodCode,'third_party_code'=>$thirdPartyCode])->one();
    	
    	if(!empty($_POST['check'])){
    		
    		if(isset($_POST['high_copy']) && !isset($_POST['lable_list'])){
    			return false;
    		}
    		
    		$shippingMethodOne->update_time = time();
    		$shippingMethodOne->is_api_print = isset($_POST['is_api_print']) ? 1 : 0;
    		$shippingMethodOne->is_print = isset($_POST['high_copy']) ? 1 : 0;
    		$shippingMethodOne->print_params = isset($_POST['lable_list']) ? json_encode($_POST['lable_list']) : '';
    		$shippingMethodOne->is_close = isset($_POST['is_close']) ? 1 : 0;
    		
    		return $shippingMethodOne->save(false);
    	}
    	
    	if($shippingMethodOne->is_print == 1){
    		$print_high_copy = array('high_copy');
    	}else{
    		$print_high_copy = array();
    	}
    	
    	if($shippingMethodOne->is_api_print == 1){
    		$print_is_api = array('is_api_print');
    	}else{
    		$print_is_api = array();
    	}
    	
    	if($shippingMethodOne->is_close == 1){
    		$is_close = array('is_close');
    	}else{
    		$is_close = array();
    	}
    	
    	return $this->renderPartial('editShippingPrint',['code'=>$code,'methodCode'=>$methodCode,
    			'thirdPartyCode'=>$thirdPartyCode,'shippingMethod'=>$shippingMethodOne,'print_high_copy'=>$print_high_copy,
    			'print_is_api'=>$print_is_api,'is_close'=>$is_close]);
    }

}
