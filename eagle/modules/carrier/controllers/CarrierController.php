<?php

namespace eagle\modules\carrier\controllers;
use \yii;
use yii\web\Controller;
use eagle\modules\carrier\models\SysCarrier;
use eagle\modules\carrier\models\SysCarrierParam;
use eagle\modules\carrier\helpers\CarrierHelper;
use eagle\models\SysShippingMethod;
use common\helpers\Helper_Array;
use yii\data\Pagination;
use yii\helpers\Url;
use common\api\aliexpressinterface\AliexpressInterface_Api;
use eagle\modules\carrier\models\SysCarrierCustom;
use eagle\modules\app\apihelpers\AppTrackerApiHelper;
use eagle\modules\carrier\models\CarrierUserUse;
use eagle\modules\util\helpers\ConfigHelper;
use eagle\modules\order\models\OdOrder;
use common\helpers\Helper_Curl;
use Qiniu\json_decode;

class CarrierController extends \eagle\components\Controller
{
    public $enableCsrfValidation = false;
    
    public function actionIndex()
    {
    	return '请使用CRM入口';
    	
    	AppTrackerApiHelper::actionLog("eagle_v2","/carrier/carrier/index");
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
    	return '请使用CRM入口';
    	
    	AppTrackerApiHelper::actionLog("eagle_v2","/carrier/carrier/create");
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
	    	return $c_obj->save();
    	}
    	if(!empty($code)){
    		$c = SysCarrier::findOne($code);
    		$carrier = empty($c)?'':$c->attributes;
    	}
    	return $this->renderPartial('create',['carrier'=>$carrier,'code'=>$code]);
    }

    //删除物流商
    public function actionDelCarrier(){
    	return '请使用CRM入口';
    	
        if(empty($_GET['code']))return false;
        //先删除物流商参数
        $delResult = CarrierHelper::deleteParams($_GET['code']);
        if($delResult){
            //删除参数本身
            $carrier = SysCarrier::findOne(['carrier_code'=>$_GET['code']]);
            if($carrier != null)$carrier->delete();
            return $this->redirect(\Yii::$app->urlManager->createUrl('carrier/carrier/index'));
        }
    }

    public function actionParams(){
    	return '请使用CRM入口';
    	
    	AppTrackerApiHelper::actionLog("eagle_v2","/carrier/carrier/params");
        //判断如果有提交 执行参数的添加
        if(isset($_POST['check']) && $_POST['check']==1){
            unset($_POST['check']);
            $params_value = array();
            //判断传过来的参数值格式是否合法
            foreach($_POST['carrier_param_value'] as $k=> $v){
                if($_POST['display_type'][$k]=='text')continue;
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
    
    
    public function actionList()
    {
    	return "<a href='/configuration/carrierconfig/index'>请使用新的流程入口</a>";
    	
    	AppTrackerApiHelper::actionLog("eagle_v2","/carrier/carrier/list");
    	$pageSize = isset($_GET['per-page'])?$_GET['per-page']:15;
    	$query=SysCarrier::find();
    	
    	
    	if(!empty($_POST['carrier_code_sel'])){
    		if($_POST['carrier_code_sel'] == 'all'){
    			$query->where(' api_class!=:api_class ',[':api_class'=>'LB_RTBCOMPANYCarrierAPI']);
    		}
    		else
    		$query->where(['carrier_code'=>$_POST['carrier_code_sel']]);
    	}else{
    		$query->where(' api_class!=:api_class ',[':api_class'=>'LB_RTBCOMPANYCarrierAPI']);
    	}
    	
    	$pagination = new Pagination([
    			'defaultPageSize' => 15,
    			'pageSize' => $pageSize,
    			'totalCount' => $query->count(),
    			'pageSizeLimit'=>[15,200],//每页显示条数范围
    			//'params'=>$data,
    			]);
    	$result['pagination'] = $pagination;
    	$query->orderBy('carrier_name asc , carrier_type asc');
    	
    	$rtbArr = array();
    	
    	//强制软通宝在首页
    	/*
    	if(empty($pagination->offset)){
    		$query->limit($pagination->limit-1);
    		$query->offset( $pagination->offset );
    		
    		$rtbArr=SysCarrier::find()->where(' api_class=:api_class ',[':api_class'=>'LB_RTBCOMPANYCarrierAPI'])->asArray()->all();
    	}else{
    		$query->limit($pagination->limit);
    		$query->offset( $pagination->offset-1 );
    	}
    	*/
    	
    	//查询出物流商
    	$carrierList = SysCarrier::find()->orderBy('carrier_name asc')->select(['carrier_code','carrier_name'])->asArray()->all();
    	$carrierList = Helper_Array::toHashmap($carrierList,'carrier_code','carrier_name');
    	
    	$query->limit($pagination->limit);
    	$query->offset( $pagination->offset );
    	
    	$result['data'] = $query->asArray()->all();
    	
    	$url_arr = array_merge(['/carrier/carrieraccount/index']);
    	$return_url = Url::to($url_arr);
    	return $this->render('list',['carriers'=>$result,'return_url'=>$return_url,'rtbArr'=>$rtbArr,'carrierList'=>$carrierList]);
    }
    /**
     * 自定义物流列表
     * million
     */
    public function actionListcustom(){
    	return "<a href='/configuration/carrierconfig/custom'>请使用新的流程入口</a>";
    	
    	AppTrackerApiHelper::actionLog("eagle_v2","/carrier/carrier/listcustom");
    	$pageSize = isset($_GET['per-page'])?$_GET['per-page']:15;
    	$query=SysCarrierCustom::find();
    	$pagination = new Pagination([
    			'defaultPageSize' => 15,
    			'pageSize' => $pageSize,
    			'totalCount' => $query->count(),
    			'pageSizeLimit'=>[15,200],//每页显示条数范围
    			//'params'=>$data,
    			]);
    	$result['pagination'] = $pagination;
    	$query->orderBy('carrier_name asc , carrier_type asc');
    	$query->limit($pagination->limit);
    	$query->offset( $pagination->offset );
    	$result['data'] = $query->asArray()->all();
    	$url_arr = array_merge(['/carrier/carrieraccount/custom']);
    	$return_url = Url::to($url_arr);
    	return $this->render('listcustom',['carriers'=>$result,'return_url'=>$return_url]);
    }
    /**
     * 添加自定义物流商
     */
    public function actionCreatecustom(){
    	AppTrackerApiHelper::actionLog("eagle_v2","/carrier/carrier/createcustom");
    	
    	if(\Yii::$app->request->isPost){
    		$code = isset($_POST['code'])?$_POST['code']:'';
    		$c_obj = SysCarrierCustom::findOne(['carrier_code'=>$code]);
    		if($c_obj==null){
    			$c_obj = new SysCarrierCustom();
    			$c_obj->create_time = time();
    			$c_obj->update_time = time();
    		}else{
    			$c_obj->update_time = time();
    		}
    		
    		if (strlen($_POST['carrier_name'])==0){
    			return '自定义物流商名必填！';
    		}
    		if ($_POST['carrier_name'] != $c_obj->carrier_name){
    			$count = SysCarrierCustom::find()->where(['carrier_name' =>$_POST['carrier_name']])->count();
    			if ($count>0){
    				return '自定义物流商名重复！';
    			}
    		}
    		$c_obj->address_list = isset($_POST['address_list'])?$_POST['address_list']:'';
    		$c_obj->carrier_name = $_POST['carrier_name'];
    		$c_obj->carrier_type = 0;
    		if ($c_obj->save()){
    			
    			try{
    				//当新增物流商时需要向managedb记录user绑定了哪些物流
    				$carrierUserUse = CarrierUserUse::find()->where(['carrier_account_id' => $c_obj->carrier_code, 'puid' => \Yii::$app->subdb->getCurrentPuid(),'carrier_code' => 'lb_customcarrier'])->one();
    				 
    				if($carrierUserUse === null){
    					$carrierUserUse = new CarrierUserUse();
    					$carrierUserUse->puid = \Yii::$app->subdb->getCurrentPuid();
    					$carrierUserUse->carrier_code = 'lb_customcarrier';
    					$carrierUserUse->carrier_account_id = $c_obj->carrier_code;
    				}
    			
    				$carrierUserUse->is_used = 1;
    			
    				$carrierUserUse->save(false);
    			}catch(\Exception $ex){
    				//暂时不作任何处理
    			}
    			
    			return true;
    		}else{
    			/**
    			 * @todo 后面优化返回错误信息
    			 */
    			return print_r($c_obj->getErrors(),true);
    		}
    	}else{
    		$code = isset($_GET['code'])?$_GET['code']:'';
    		$carrier = SysCarrierCustom::findOne(['carrier_code'=>$code]);
    		if ($carrier == null){
    			$carrier = new SysCarrierCustom();
    		}
    		
    	}
    	return $this->renderPartial('createcustom',['carrier'=>$carrier,'code'=>$code]);
    }
    /**
     * 物流打印设置
     * hqw
     */
    public function actionCarrierPrintSetList(){
    	if (Yii::$app->request->isPost){
    		$carrierConfig = $_POST;
    		
    		if(isset($carrierConfig['label_paper_size']['val'])){
    			
    			if($carrierConfig['label_paper_size']['val'] == '210x297'){
    				$carrierConfig['label_paper_size']['template_width']=210;
    				$carrierConfig['label_paper_size']['template_height']=297;
    			}else{
    				$carrierConfig['label_paper_size']['template_width']=100;
    				$carrierConfig['label_paper_size']['template_height']=100;
    			}
    		}
    		
    		$result = ConfigHelper::setConfig("CarrierOpenHelper/CommonCarrierConfig", json_encode($carrierConfig));
    	}
    	
    	$carrierConfig = ConfigHelper::getConfig("CarrierOpenHelper/CommonCarrierConfig", 'NO_CACHE');
    	$carrierConfig = json_decode($carrierConfig,true);
    	 
    	if(!isset($carrierConfig['label_paper_size'])){
    		$carrierConfig['label_paper_size'] = array('val' => '100x100','template_width' => 100,'template_height' => 100);
    	}
    	 
    	return $this->render('carrierPrintSetList',['carrierConfig'=>$carrierConfig]);
    }
    
    /**
     * 由客户确定选中的订单是否已经成功打印
     * hqw
     */
    public function actionCarrierPrintConfirm(){
    	$puid = \Yii::$app->user->identity->getParentUid();
    	
    	if(isset($_POST['orders'])&&!empty($_POST['orders'])){
    		$orders	=	$_POST['orders'];
    	}else{
    		return "错误：未传入订单号(send none order)";
    	}
    	
    	$tmp_printed = 1;
    	if(isset($_POST['printed'])){
    		if($_POST['printed'] != ''){
    			$tmp_printed = $_POST['printed'];
    		}
    	}
    	
    	$orders = rtrim($orders,',');
    	$orderlist = OdOrder::find()->where("order_id in ({$orders})")->all();
    	
    	//处理成接口需要的数据
    	foreach($orderlist as $order){
    		$order->is_print_carrier = $tmp_printed;
    		$order->print_carrier_operator = $puid;
    		$order->printtime = time();
    		$order->save(false);
    	}
    	
    	if($tmp_printed == 1){
    		return '标记打印已成功';
    	}else{
    		return '标记打印未成功';
    	}
    }

    /**
     * 进入出口易授权界面
     * lgw
     */
    public function actionChukouyiAuth(){   
    	
    	if (!empty($_REQUEST['account_id']))
    	{
    		Yii::$app->session['carrierid'] = $_REQUEST['account_id'];
    	}
    	
    	//开发者测试账号
    	
    	//开发者正式账号
    	// TODO carrier dev account @XXX@
    	$client_id="@XXX@";
    	$client_secret="@XXX@";
    	// 要到出口易开发者后台设置 redirect_uri 为 https://您的erp网址/carrier/carrier/chukouyi-auth-get
    	$tempu = parse_url(\Yii::$app->request->hostInfo);
    	$host = $tempu['host'];
    	$redirect_uri="https://{$host}/carrier/carrier/chukouyi-auth-get";
    	
    	$response_type="code";
    	$scope="OpenApi";
    	
    	$url="https://openapi.chukou1.cn/oauth2/authorization?client_id=".$client_id."&response_type=".$response_type."&scope=".$scope."&redirect_uri=".$redirect_uri;
    	$this->redirect($url);    	
    }
    
    /**
     * 获取出口易授权
     * lgw
     */
    public function actionChukouyiAuthGet(){
    	if (!empty($_REQUEST['code']))
    	{
    		$code=$_REQUEST['code'];
    		
    		//开发者测试账号
    		
	    	//开发者正式账号
    		// TODO carrier dev account @XXX@
	    	$client_id="@XXX@";
	    	$client_secret="@XXX@";
	    	// 要到出口易开发者后台设置 redirect_uri 为 https://您的erp网址/carrier/carrier/chukouyi-auth-get
	    	$tempu = parse_url(\Yii::$app->request->hostInfo);
	    	$host = $tempu['host'];
	    	$redirect_uri="https://{$host}/carrier/carrier/chukouyi-auth-get";

    		$grant_type="authorization_code";
    		
    		$url="https://openapi.chukou1.cn/oauth2/token?client_id=".$client_id."&client_secret=".$client_secret."&redirect_uri=".$redirect_uri."&grant_type=".$grant_type."&code=".$code;
    		$request=Helper_Curl::post($url);
    		
    		$arr=json_decode($request,true);
    		
    		if(!isset($arr['AccessToken']))
    			return $this->render('errorview',['title'=>$arr['Message']]);
    			
    		//取回session数据
    		$account_id = Yii::$app->session['carrierid'];

    		$account = \eagle\modules\carrier\models\SysCarrierAccount::find()->where(['id'=>$account_id])->one();
    		$account->is_used = 1;
    		
    		$nextuptime=strtotime(date("Y-m-d H:i:s"))+86400*30;  //下次需要更新时间,预定时间减一天

    		$api_params = $account->api_params;
			$api_params['AccessToken'] = $arr['AccessToken'];
			$api_params['next_time'] = intval($nextuptime);
			$api_params['RefreshToken'] = $arr['RefreshToken'];
			$account->api_params = $api_params;
			$account->save();
    		
    		if($account->save())
    		{
    			return $this->render('successview',['title'=>'授权成功']);
    		}
    		
    	}
    }
    

}
