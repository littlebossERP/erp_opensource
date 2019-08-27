<?php

namespace eagle\modules\configuration\controllers;

use Yii;
use yii\web\Controller;
use \yii\web\Response;
use yii\filters\VerbFilter;
use eagle\modules\inventory\helpers\InventoryHelper;
use eagle\modules\inventory\helpers\WarehouseHelper;
use eagle\modules\inventory\models\WarehouseMatchingRule;
use common\helpers\Helper_Array;
use eagle\models\sys\SysCountry;
use eagle\modules\carrier\models\MatchingRule;
use eagle\modules\platform\apihelpers\PlatformAccountApi;
use eagle\modules\catalog\helpers\ProductApiHelper;
use Qiniu\json_decode;
use eagle\modules\carrier\helpers\CarrierOpenHelper;
use yii\helpers\Html;
use console\helpers\CarrierconversionHelper;
use eagle\modules\util\helpers\ExcelHelper;
use eagle\modules\carrier\models\SysCarrierAccount;
use eagle\models\SysShippingMethod;
use eagle\modules\util\models\LabelTip;
use yii\web\BadRequestHttpException;

class WarehouseconfigController extends \eagle\components\Controller
{
	public $enableCsrfValidation = FALSE;
	
    public function actionIndex()
    {
        return $this->render('index');
    }
    
    /**
     * 打开自定义仓库列表界面
     *
     * +-------------------------------------------------------------------------------------------
     * log			name	date					note
     * @author		hqw		2016/02/23				初始化
     * +-------------------------------------------------------------------------------------------
     */
    public function actionSelfWarehouseList(){
    	if(!\eagle\modules\permission\apihelpers\UserApiHelper::checkModulePermission('inventory')){
    		throw new BadRequestHttpException(Yii::t('yii', '你没有权限访问此页面'));
    	}
    	
    	$pageSize = isset($_GET['per-page'])?$_GET['per-page']:20;
    	$page = isset($_GET['page'])?$_GET['page']:1;
    	
    	$sort = 'is_active';
    	$order = 'desc';
    	$queryString = array();
    	$queryString['is_oversea'] = 0;
    	
    	$warehouseList = WarehouseHelper::listWarehouseData($page, $pageSize, $sort, $order, $queryString);
    	
    	return $this->render('warehouse_index',['warehouseList'=>$warehouseList]);
    }
    
    /**
     * 获取运输服务详细信息
     * 
     * +-------------------------------------------------------------------------------------------
     * @param	$_POST['type']			海外仓还是自定义仓	oversea表示海外仓
     * @param	$_POST['warehouse_id']	仓库ID
     * @param	$_POST['server_id']		运输服务ID
     * +-------------------------------------------------------------------------------------------
     * log			name	date					note
     * @author		hqw		2016/02/23				初始化
     * +-------------------------------------------------------------------------------------------
     * @return	返回html格式
     * +-------------------------------------------------------------------------------------------
     */
    public function actionGetShippingMethodInfo(){
//     	print_r($_POST);
//     	exit;
    	
    	$type = empty($_POST['type']) ? '' : $_POST['type'];
    	$tmp_oversea_type = empty($_POST['oversea_type']) ? 0 : $_POST['oversea_type'];
    	
    	if(($type == 'oversea') && ($tmp_oversea_type == 0)){
    		$tmpParrier['warehouse_id'] = $_POST['warehouse_id'];
    	}else if(($type == 'oversea') && ($tmp_oversea_type == 1)){
    		$tmpParrier['self_warehouse_id'] = $_POST['warehouse_id'];
    	}else{
    		$tmpParrier['proprietary_warehouse'] = $_POST['warehouse_id'];
    	}
    	
    	if(!empty($_POST['server_id'])){
    		if($tmp_oversea_type == 0){
    			$tmpParrier['shipping_method_code'] = $_POST['server_id'];
    		}
 			else   	
    			$tmpParrier['shipping_id'] = $_POST['server_id'];
    	}
    	
    	$shippingMethodInfo = CarrierOpenHelper::getShippingMethodNameInfo($tmpParrier, -1);
    	
    	$shippingMethodInfo = $shippingMethodInfo['data'];
    	
    	$tmp_html = '';
    	
    	if(count($shippingMethodInfo) > 0){
    		//第三方仓库需要组织运输方式的匹配规则
    		if(($type == 'oversea') && (($tmp_oversea_type == 0) || ($tmp_oversea_type == 1))){
    			$tmpShippingMethodInfo = current($shippingMethodInfo);
    			reset($shippingMethodInfo);
    			
	    		$serviceUser = array();
	    		
	    		$serviceUserArr = CarrierOpenHelper::getCarrierShippingServiceUserByCarrierCode($tmpShippingMethodInfo['carrier_code']);
	    		
	    		if(count($serviceUserArr) > 0){
	    			foreach ($serviceUserArr as $serviceUserone){
	    				$serviceUser[$serviceUserone['id']] = $serviceUserone['rule'];
	    			}
	    		}
    		}

    		//根据不同仓库类型生成对应的运输服务html代码
    		foreach ($shippingMethodInfo as $key => $ship){
    			if(($type == 'oversea') && ($tmp_oversea_type == 0)){
    				$tmp_html .= "<tr>"
    						."<td style='width: 50px;text-align:center;'>".Html::checkbox('check_all'.$tmp_oversea_type.'[]',false,['class'=>'selectShip'.$tmp_oversea_type,'value'=>$ship['id']])."</td>"
    						."<td>".$ship['service_name'].'('.@$ship['shipping_method_code'].')'."</td>"
    						."<td>".$ship['shipping_method_name']."</td>"
    						."<td>".($ship['is_used'] == 1 ? '开启' : '关闭')."</td>"
    						."<td>".$ship['account_name']."</td>"
    						."<td>".($ship['is_used'] == 1 ? Html::dropDownList('setrules','',['-1'=>'分配规则','0'=>'添加分配规则']+(empty($serviceUser[$key]) ? array() : $serviceUser[$key]),['onchange'=>'selectRuless(this,'.$ship['id'].','.$_POST['warehouse_id'].')','class'=>'iv-input']) : '')."</td>"
    							."<td>".(($ship['is_used'] == 1) ? 
    								"<a class='iv-btn' onclick='openOrCloseShipping(".$ship['id'].",\"".$ship['carrier_code']."\",\"close\")' >关闭</a> ".
    								"<a class='iv-btn' onclick='$.openModal(\"/configuration/warehouseconfig/oversea-shippingservice\",{type:\"edit\",id:".$ship['id'].",code:\"".$ship['carrier_code']."\",key:\"\"},\"编辑\",\"get\")'>编辑</a>".
    								"<a class='iv-btn' onclick='$.openModal(\"/configuration/warehouseconfig/oversea-shippingservice\",{type:\"copy\",id:".$ship['id'].",code:\"".$ship['carrier_code']."\",key:\"\"},\"复制\",\"get\")'>复制</a>" :
    								"<a class='iv-btn' onclick='$.openModal(\"/configuration/warehouseconfig/oversea-shippingservice\",{type:\"open\",id:".$ship['id'].",code:\"".$ship['carrier_code']."\",key:\"\"},\"开启\",\"get\")'>开启</a>" )
    							."</td>"
    						."</tr>";
    			}else{
	    			$tmp_html .= "<tr>"
    						."<td style='width: 50px;text-align:center;'>".Html::checkbox('check_all'.$tmp_oversea_type.'[]',false,['class'=>'selectShip'.$tmp_oversea_type,'value'=>$ship['id']])."</td>"
    						."<td>".$ship['service_name'].'('.@$ship['shipping_method_code'].')'."</td>"
    						."<td>".($ship['is_used'] == 1 ? '开启' : '关闭')."</td>"
    						."<td>".($ship['is_used'] == 1 ? Html::dropDownList('setrules','',['-1'=>'分配规则','0'=>'添加分配规则']+(empty($serviceUser[$key]) ? array() : $serviceUser[$key]),['onchange'=>'selectRuless(this,'.$ship['id'].','.$_POST['warehouse_id'].')','class'=>'iv-input']) : '')."</td>"
    						."<td>".(($ship['is_used'] == 1) ? 
    								"<a class='iv-btn' onclick='openOrCloseShipping(".$ship['id'].",\"".$ship['carrier_code']."\",\"close\")' >关闭</a> ".
    								"<a class='iv-btn' onclick='$.openModal(\"/configuration/warehouseconfig/oversea-shippingservice\",{type:\"edit\",id:".$ship['id'].",code:\"".$ship['carrier_code']."\",key:\"custom_oversea\"},\"编辑\",\"get\")'>编辑</a>"
    								: 
    								"<a class='iv-btn' onclick='$.openModal(\"/configuration/warehouseconfig/oversea-shippingservice\",{type:\"open\",id:".$ship['id'].",code:\"".$ship['carrier_code']."\",key:\"custom_oversea\"},\"开启\",\"get\")'>开启</a>" 
    								)
    						."</td>"
    						."</tr>";
    			}
    		}
    	}
    	
    	return $tmp_html;
    }
    
    /**
     * 保存仓库信息
     *
     * +-------------------------------------------------------------------------------------------
     * log			name	date					note
     * @author		hqw		2016/02/23				初始化
     * +-------------------------------------------------------------------------------------------
     * @return	array('code'=>1,'msg'=>'')
     * +-------------------------------------------------------------------------------------------
     */
    public function actionSaveWarehouseInfoByid(){
    	$result = array('code'=>1,'msg'=>'');
    	
    	if(empty($_POST)){
    		$result['msg'] = '非法操作,传入后台数据为空';
    		return json_encode($result);
    	}
    	
    	$saveResult = InventoryHelper::saveWarehouseInfoById($_POST);
    	
    	return json_encode($saveResult);
    }
    
    /**
     * 仓库关闭或开启
     *
     * +-------------------------------------------------------------------------------------------
     * log			name	date					note
     * @author		hqw		2016/02/23				初始化
     * +-------------------------------------------------------------------------------------------
     * @return	array('code'=>1,'msg'=>'')
     * +-------------------------------------------------------------------------------------------
     */
    public function actionWarehouseOnoffById(){
    	$result = array('code'=>1,'msg'=>'');
    	
    	if(empty($_POST)){
    		$result['msg'] = '非法操作,传入后台数据为空';
    		return json_encode($result);
    	}
    	
    	$result = InventoryHelper::warehouseOnoffById($_POST['warehouse_id'], $_POST['warehouse_active']);
    	
    	return json_encode($result);
    }
    
    /**
     * 获取仓库匹配规则信息
     *
     * +-------------------------------------------------------------------------------------------
     * log			name	date					note
     * @author		hqw		2016/02/23				初始化
     * +-------------------------------------------------------------------------------------------
     */
    public function actionGetWarehouseMatchRuleInfoByid(){
    	$id = $_GET['match_rule_id'];
    	$warehouse_id = $_GET['warehouse_id'];
    	
    	$rule = WarehouseMatchingRule::find()->where(['id'=>$id])->asArray()->one();
    	if (empty($rule)){
    		$rule = array();
    	}else{
    		$rule['rules'] = json_decode($rule['rules'], true);
    	}
    	
    	//收件国家
    	$query = SysCountry::find();
    	$regions = $query->orderBy('region')->groupBy('region')->select('region')->asArray()->all();
    	$countrys =[];
    	foreach ($regions as $region){
    		$arr['name']= $region['region'];
    		$arr['value']=Helper_Array::toHashmap(SysCountry::find()->where(['region'=>$region['region']])->orderBy('country_en')->select(['country_code', "CONCAT( country_zh ,'(', country_en ,')' ) as country_name "])->asArray()->all(),'country_code','country_name');
    		$countrys[]= $arr;
    	}
    	//国家中文名
    	$region = WarehouseHelper::countryRegionChName();
    	//平台
    	$source = MatchingRule::$source;
    	//站点
    	$sites = PlatformAccountApi::getAllPlatformOrderSite();
    	//账号
    	$selleruserids=PlatformAccountApi::getAllPlatformOrderSelleruseridLabelMap();
    	//买家选择物流物流
    	$buyer_transportation_services = PlatformAccountApi::getAllPlatformShippingServices();
    	//商品标签
    	$product_tags = ProductApiHelper::getAllTags();
    	
    	return $this->renderPartial('warehouse_rules',[
    			'countrys'=>$countrys,
    			'region'=>$region,
    			'rule'=>$rule,
    			'rules'=>InventoryHelper::getWarehouseMatchRuleArr(),//获取分配项
    			'source'=>$source,
    			'sites'=>$sites,
    			'selleruserids'=>$selleruserids,
    			'buyer_transportation_services'=>$buyer_transportation_services,
    			'product_tags'=>$product_tags,
//     			'sevice_name'=>$sevice_name,
    			'warehouse_rules_id'=>$warehouse_id,
    			]);
    }
    
    /**
     * 保存仓库匹配规则
     *
     * +-------------------------------------------------------------------------------------------
     * log			name	date					note
     * @author		hqw		2016/02/23				初始化
     * +-------------------------------------------------------------------------------------------
     * @return	{"code":1,"msg":""}
     * +-------------------------------------------------------------------------------------------
     */
    public function actionSaveWarehouseMatchRuleInfo(){
    	$result = array('code'=>1,'msg'=>'');
    	
    	if(empty($_POST)){
    		$result['msg'] = '非法操作,传入后台数据为空';
    		return json_encode($result);
    	}
    	
    	$tmpRule = $_POST;
    	
    	if(empty($tmpRule['rules'])){
    		$result['msg'] = '请选择匹配项';
    		return json_encode($result);
    	}
    	
    	if((in_array('items_location_country', $tmpRule['rules'])) && (empty($tmpRule['items_location_country']))){
    		$result['msg'] = '请选择物品所在国家';
    		return json_encode($result);
    	}
    	
    	if((in_array('items_location_provinces', $tmpRule['rules'])) && (empty($tmpRule['items_location_provinces']))){
    		$result['msg'] = '请选择物品所在州/省份';
    		return json_encode($result);
    	}
    		
    	if((in_array('items_location_city', $tmpRule['rules'])) && (empty($tmpRule['items_location_city']))){
    		$result['msg'] = '请选择物品所在城市';
    		return json_encode($result);
    	}
    		
    	if((in_array('receiving_country', $tmpRule['rules'])) && (empty($tmpRule['receiving_country']))){
    		$result['msg'] = '请选择收件人国家';
    		return json_encode($result);
    	}
    	
    	if((in_array('receiving_provinces', $tmpRule['rules'])) && (empty($tmpRule['receiving_provinces']))){
    		$result['msg'] = '请选择收件人州/省份';
    		return json_encode($result);
    	}
    	
    	if((in_array('receiving_city', $tmpRule['rules'])) && (empty($tmpRule['receiving_city']))){
    		$result['msg'] = '请选择收件人城市';
    		return json_encode($result);
    	}
    		
    	if((in_array('skus', $tmpRule['rules'])) && (empty($tmpRule['skus']))){
    		$result['msg'] = '请填写SKU';
    		return json_encode($result);
    	}

    	if((in_array('sources', $tmpRule['rules'])) && (empty($tmpRule['sources']))){
    		$result['msg'] = '请选择平台、账号、站点';
    		return json_encode($result);
    	}
    	
    	if((in_array('freight_amount', $tmpRule['rules'])) && (empty($tmpRule['freight_amount']['min'])) && (empty($tmpRule['freight_amount']['max']))){
    		$result['msg'] = '请输入买家支付运费范围';
    		return json_encode($result);
    	}

    	if((in_array('freight_amount', $tmpRule['rules']))){
    		if(!is_numeric($tmpRule['freight_amount']['min'])){
    			$result['msg'] = '买家支付运费最小值只能用数字！';
    			return json_encode($result);
    		}
    		
    		if(!is_numeric($tmpRule['freight_amount']['max'])){
    			$result['msg'] = '买家支付运费最大值只能用数字！';
    			return json_encode($result);
    		}
    		
    		if ($tmpRule['freight_amount']['min'] > $tmpRule['freight_amount']['max']){
    			$result['msg'] = '买家支付运费最大值必须大于最小值！';
    			return json_encode($result);
    		}
    	}
    	
    	$tmprules = array();
    	
    	$tmprules['rules'] = $tmpRule['rules'];
    	$tmprules['items_location_country'] = empty($tmpRule['items_location_country']) ? '' : $tmpRule['items_location_country'];
    	$tmprules['items_location_provinces'] = $tmpRule['items_location_provinces'];
    	$tmprules['items_location_city'] = $tmpRule['items_location_city'];
    	$tmprules['receiving_provinces'] = $tmpRule['receiving_provinces'];
    	$tmprules['receiving_city'] = $tmpRule['receiving_city'];
    	$tmprules['skus'] = $tmpRule['skus'];
    	$tmprules['freight_amount'] = $tmpRule['freight_amount'];
    	
    	if(isset($tmpRule['receiving_country'])){
    		$tmprules['receiving_country'] = $tmpRule['receiving_country'];
    	}
    	
    	if(isset($tmpRule['sources'])){
    		$tmprules['sources'] = $tmpRule['sources'];
    	}
    	
    	$rule = WarehouseMatchingRule::find()->where(['id'=>$_POST['id']])->one();
    	if (empty($rule)){
    		$rule = new WarehouseMatchingRule();
    		$rule->warehouse_id = $tmpRule['warehouse_rules_id'];
    		$rule->created = time();
    		$rule->priority = InventoryHelper::getWarehouseMaxMatchingRulePriority();
    	}
    	
    	//规则名不能重复
    	if ($rule->isNewRecord){
    		$count = WarehouseMatchingRule::find()->where(['rule_name'=>$tmpRule['name']])->count();
    	}else{
    		$count = WarehouseMatchingRule::find()->where('rule_name = :rule_name and id <> :id',[':rule_name'=>$tmpRule['name'],':id'=>$_POST['id']])->count();
    	}
    	
    	if ($count>0){
    		$result['msg'] = '规则名重复';
    		return json_encode($result);
    	}
    	
    	$rule->rule_name = $tmpRule['name'];
    	
    	$rule->is_active = $tmpRule['is_active'];
    	$rule->updated = time();
    	
    	$rule->rules = json_encode($tmprules);
    	
    	if($rule->save(false)){
    		$result['code'] = 0;
    		$result['msg'] = '保存成功';
    		return json_encode($result);
    	}else{
    		$result['msg'] = '保存失败';
    		return json_encode($result);
    	}
    }
    
    /**
     * 运输服务从关联的仓库中移除
     *
     * +-------------------------------------------------------------------------------------------
     * log			name	date					note
     * @author		hqw		2016/02/23				初始化
     * +-------------------------------------------------------------------------------------------
     * @return	{"code":1,"msg":""}
     * +-------------------------------------------------------------------------------------------
     */
    public function actionRemoveShippingBywarehouseid(){
    	$result = array('code'=>1,'msg'=>'');
    	 
    	if(empty($_POST['shipping_id']) || (!isset($_POST['warehouse_id']))){
    		$result['msg'] = '非法操作,传入后台数据为空';
    		return json_encode($result);
    	}
    	
    	$resultRemove = CarrierOpenHelper::shippingMethodRemoveWarehouse($_POST['shipping_id'], $_POST['warehouse_id']);
    	
    	$result['code'] = $resultRemove['response']['code'];
    	$result['msg'] = $resultRemove['response']['msg'];
    	
    	return json_encode($result);
    }
    
    /**
     * 仓库添加运输服务列表
     *
     * +-------------------------------------------------------------------------------------------
     * log			name	date					note
     * @author		hqw		2016/02/23				初始化
     * +-------------------------------------------------------------------------------------------
     * @return	{"code":1,"msg":""}
     * +-------------------------------------------------------------------------------------------
     */
    public function actionAddWarehouseShippingList(){
		if(!isset($_GET['warehouse_id']))
			return false;
		
		$tmpAndParams['not_proprietary_warehouse'] = $_GET['warehouse_id'];
		
		if(!empty($_GET['warehouse_shipping_name'])){
			$tmpAndParams['warehouse_shipping_name'] = $_GET['warehouse_shipping_name'];
		}
		
    	if(!empty($_GET['warehouse_carrier_code'])){
			$tmpAndParams['warehouse_carrier_code'] = $_GET['warehouse_carrier_code'];
		}

    	$carrierIdNameMap = CarrierOpenHelper::getOpenCarrierArr(2, 1);
    	$shippingMethodInfo = CarrierOpenHelper::getShippingMethodNameInfo($tmpAndParams, (empty($_GET['per-page']) ? 10 : $_GET['per-page']));
    	
    	return $this->renderAjax('warehouse_shipping_add',['carrierIdNameMap'=>$carrierIdNameMap,'shippingMethodInfo'=>$shippingMethodInfo,'warehouse_id'=>$_GET['warehouse_id']]);
    }
    
    /**
     * 仓库添加运输服务保存
     *
     * +-------------------------------------------------------------------------------------------
     * log			name	date					note
     * @author		hqw		2016/02/23				初始化
     * +-------------------------------------------------------------------------------------------
     * @return	{"code":1,"msg":""}
     * +-------------------------------------------------------------------------------------------
     */
    public function actionSaveWarehouseShipping(){
    	$result = array('code'=>1,'msg'=>'');
    	
    	if(empty($_POST['selectShip'])){
    		$result['msg'] = '非法操作,传入后台数据为空';
    		return json_encode($result);
    	}
    	
    	$resultAdd = CarrierOpenHelper::shippingMethodAddWarehouse($_POST);
    	
    	$result['code'] = $resultAdd['response']['code'];
    	$result['msg'] = $resultAdd['response']['msg'];
    	
    	return json_encode($result);
    }
    
    /**
     * 仓库添加或修改弹出界面
     *
     * +-------------------------------------------------------------------------------------------
     * log			name	date					note
     * @author		hqw		2016/02/23				初始化
     * +-------------------------------------------------------------------------------------------
     */
    public function actionWarehouseEnableOrCreate(){
		if(empty($_GET['type'])){
			return false;
		}
		
		if($_GET['type'] == 'enable_oversea'){
			$notWarehouseIdNameMap = InventoryHelper::getWarehouseOrOverseaIdNameMap(0, 1);
		}else{
			$notWarehouseIdNameMap = InventoryHelper::getWarehouseOrOverseaIdNameMap(0, 0);
		}
    	
    	$carrierOverseaList = CarrierOpenHelper::getNotOpenCarrierArr(1, 2 ,1);
    	
    	return $this->renderPartial('warehouse_enable_or_create',['type'=>$_GET['type'], 'notWarehouseIdNameMap'=>$notWarehouseIdNameMap,'carrierOverseaList'=>$carrierOverseaList]);
    }
    
    /**
     * 仓库编辑或创建保存
     *
     * +-------------------------------------------------------------------------------------------
     * log			name	date					note
     * @author		hqw		2016/02/23				初始化
     * +-------------------------------------------------------------------------------------------
     * @return	{"code":1,"msg":""}
     * +-------------------------------------------------------------------------------------------
     */
    public function actionSaveWarehouseEnableOrCreate(){
    	$result = array('code'=>1,'msg'=>'');
    	
    	if(empty($_POST['warehouse_type'])){
    		$result['msg'] = '非法操作,传入后台数据为空e_1';
    		return json_encode($result);
    	}
    	
    	if(isset($_POST['new_warehouse_name'])){
    		if(empty($_POST['new_warehouse_name'])){
    			$result['msg'] = '仓库名不能为空';
    			return json_encode($result);
    		}
    	}
    	
    	if(($_POST['warehouse_type'] == 'enable') || ($_POST['warehouse_type'] == 'enable_oversea')){
    		if(!isset($_POST['notWarehouseDropDownid'])){
    			$result['msg'] = '非法操作,传入后台数据为空e_2';
    			return json_encode($result);
    		}
    		
    		$resultEnagle = InventoryHelper::warehouseOnoffById($_POST['notWarehouseDropDownid'], 'Y');
    	}else if($_POST['warehouse_type'] == 'create'){
    		$tmpParams['warehouse_name'] = $_POST['new_warehouse_name'];
    		$tmpParams['warehouse_id'] = -1;
    		$tmpParams['address_nation'] = '中国';
    		$tmpParams['is_active'] = 'Y';
    		$tmpParams['is_oversea'] = '0';
    		
    		$resultEnagle = InventoryHelper::saveWarehouseInfoById($tmpParams);
    	}else if($_POST['warehouse_type'] == 'create_oversea'){
    		if($_POST['oversea_type_radio'] == 0){
    			if(empty($_POST['carrierOverseaDropDownid'])){
    				$result['msg'] = '请选择物流商';
    				return json_encode($result);
    			}
    			
    			if(!isset($_POST['overseaWarehouseID'])){
    				$result['msg'] = '请选择对应仓库';
    				return json_encode($result);
    			}
    		}
    		
    		$tmpParams['warehouse_name'] = $_POST['new_warehouse_name'];
    		$tmpParams['warehouse_id'] = -1;
    		$tmpParams['address_nation'] = '中国';
    		$tmpParams['is_active'] = 'Y';
    		$tmpParams['is_oversea'] = '1';
    		$tmpParams['oversea_type'] = $_POST['oversea_type_radio'];
    		
    		if($_POST['oversea_type_radio'] == 0){
    			$tmpParams['carrier_code'] = $_POST['carrierOverseaDropDownid'];
    			$tmpParams['third_party_code'] = $_POST['overseaWarehouseID'];
    		}
    		
    		$resultEnagle = InventoryHelper::saveWarehouseInfoById($tmpParams);
    	}
    	
    	$result['code'] = $resultEnagle['response']['code'];
    	$result['msg'] = $resultEnagle['response']['msg'];
    	return json_encode($result);
    }

    /**
     * 第三方仓库列表
     *
     * +-------------------------------------------------------------------------------------------
     * log			name	date					note
     * @author		hqw		2016/02/23				初始化
     * +-------------------------------------------------------------------------------------------
     */
    public function actionOverseaWarehouseList(){
    	$serviceUser = array();
    	
    	//获取所有仓库ID对应仓库名的值
    	$warehouseIdNameMap = InventoryHelper::getWarehouseOrOverseaIdNameMap(-1, 1 ,1);
    	
    	$tab_active = \Yii::$app->request->get('tab_active');
    	
    	return $this->render('overseaWarehouseList',['warehouseIdNameMap'=>$warehouseIdNameMap,'tab_active'=>$tab_active]);
    }
    
    /**
     * 获取第三方仓库对应的对应的物流仓库
     *
     * +-------------------------------------------------------------------------------------------
     * log			name	date					note
     * @author		hqw		2016/02/23				初始化
     * +-------------------------------------------------------------------------------------------
     */
    public function actionGetOverseaWarehouseByCarriercode(){
    	$carrierOverseawarehouseMap = CarrierOpenHelper::getShippingOrerseaWarehouseMap($_POST['carrier_code']);
    	
    	return Html::dropDownList('overseaWarehouseID', '', $carrierOverseawarehouseMap,['class'=>'iv-input','id'=>'overseaWarehouseID']);
    }
    
    /**
     * 第三方仓库添加海外仓账户界面
     *
     * +-------------------------------------------------------------------------------------------
     * log			name	date					note
     * @author		hqw		2016/02/23				初始化
     * +-------------------------------------------------------------------------------------------
     * @return	{"code":1,"msg":""}
     * +-------------------------------------------------------------------------------------------
     */
    public function actionAddOrEditOrverseaCarrierAccount(){
    	if(empty($_GET['type'])) return false;
    	if(empty($_GET['carrier_code'])) return false;
    	if(!isset($_GET['warehouse_id'])) return false;
    	$id = empty($_GET['id']) ? 0 : $_GET['id'];

    	$account='';
    	if(isset($_GET['account']))
    		$account=$_GET['account'];
    	
    	$carrier_code = $_GET['carrier_code'];
    	$carrier_account = CarrierOpenHelper::carrierUserAccountShowById($carrier_code, $id);
    	
    	//认证参数解释
    	$qtipKeyArr= LabelTip::find()->asArray()->all();

    	return $this->renderAjax('add_or_edit_orversea_carrier_account',
    			['carrier_account'=>$carrier_account,'carrier_code'=>$carrier_code,'warehouse_id'=>$_GET['warehouse_id'],'third_party_code'=>$_GET['third_party_code'],'id'=>$id,'account'=>$account,'qtipKeyArr'=>$qtipKeyArr]);
    }
    
    /**
     * 第三方仓库添加海外仓账户保存
     *
     * +-------------------------------------------------------------------------------------------
     * log			name	date					note
     * @author		hqw		2016/02/23				初始化
     * +-------------------------------------------------------------------------------------------
     * @return	{"code":1,"msg":""}
     * +-------------------------------------------------------------------------------------------
     */
    public function actionEditOrverseaCarrierAccountSave(){
    	$result = array('code'=>1,'msg'=>'','data'=>0);
    	$tmp_post = \Yii::$app->request->post();
    
    	if(empty($tmp_post['name'])){
    		$result['msg'] = '账号简称不能为空';
    		return json_encode($result);
    	}
    	
    	if((empty($tmp_post['carrier_code'])) || (empty($tmp_post['warehouse_id']))){
    		$result['msg'] = '非法操作';
    		return json_encode($result);
    	}
    	
    	$id = empty($tmp_post['id']) ? '' : $tmp_post['id'];
    	$accountParams = array(
    			'accountNickname'=>$tmp_post['name'],//账户别名	必填
    			'carrier_code'=>$tmp_post['carrier_code'],//物流商代码	必填
    			'is_default'=>empty($tmp_post['default']) ? 0 : $tmp_post['default'],//是否开启为默认账号	必填
    			'carrier_params' => empty($tmp_post['carrier_params']) ? '' : $tmp_post['carrier_params'],
    			'warehouse_id'=> $tmp_post['warehouse_id'],
    			'warehouse'=>isset($tmp_post['third_party_code']) ? array($tmp_post['third_party_code']) : array(),
    	);
    	
    	$res = CarrierOpenHelper::carrierUserAccountAddOrEdit($id,$accountParams);

    	$result['code'] = $res['response']['code'];
    	$result['msg'] = $res['response']['msg'];
    	$result['data'] = $tmp_post['warehouse_id'];
    	$result['carrier_code'] = $tmp_post['carrier_code'];

    	return json_encode($result);
    }
    
    /**
     * 第三方仓库设置账户默认
     *
     * +-------------------------------------------------------------------------------------------
     * log			name	date					note
     * @author		hqw		2016/02/23				初始化
     * +-------------------------------------------------------------------------------------------
     * @return	{"code":1,"msg":""}
     * +-------------------------------------------------------------------------------------------
     */
    public function actionSetCarrierAccountDefault(){
    	$v = \Yii::$app->request->post();
    	$id = $v['id'];
    	$res = CarrierOpenHelper::carriserUserAccountSetDefault($id);
    	return $res['response']['msg'];
    }
    
    /**
     * 第三方仓库账户删除
     *
     * +-------------------------------------------------------------------------------------------
     * log			name	date					note
     * @author		hqw		2016/02/23				初始化
     * +-------------------------------------------------------------------------------------------
     * @return	{"code":1,"msg":""}
     * +-------------------------------------------------------------------------------------------
     */
    public function actionDelCarrierAccount(){
    	$v = \Yii::$app->request->post();
    	$id = $v['id'];
    	$res = CarrierOpenHelper::carrierUserAccountDelById($id);
    	return $res['response']['msg'];
    }
    
    /**
     * 第三方仓库运输服务编辑
     *
     * +-------------------------------------------------------------------------------------------
     * log			name	date					note
     * @author		hqw		2016/02/23				初始化
     * +-------------------------------------------------------------------------------------------
     */
    public function actionOverseaShippingservice(){
    	$key = 'none';
    	if(isset($_GET['key'])){
    		$key = $_GET['key'];
    	}
    	
    	//开启渠道时用户表没有的
    	if($_GET['type']=='open' && empty($_GET['id']) && $key=='oversea'){
    		$id=CarrierOpenHelper::openShippingServer($_GET['code'],$_GET['shipcode'],$_GET['thirdcode']);
    		if(!empty($id))
    			$_GET['id']=$id[0]['id'];
    	}

    	if($key == 'custom_oversea'){
    		$serviceUserById = CarrierOpenHelper::getCustomCarrierShippingServiceUserById($_GET['id'],$_GET['code']);
    	}
    	else{
    		if(isset($_GET['warehouseid']))
    			$serviceUserById = CarrierOpenHelper::getCarrierShippingServiceUserById($_GET['id'],$_GET['code'],$_GET['warehouseid'],'','',1);
    		else
    			$serviceUserById = CarrierOpenHelper::getCarrierShippingServiceUserById($_GET['id'],$_GET['code'],-1,'','',1);
    	}
    	
    	$param_set_count=0;
    	if(isset($serviceUserById['response']['data']['carrierParams'])){
    		foreach ($serviceUserById['response']['data']['carrierParams'] as $carrierParams){
    			if($carrierParams['ui_type']=='param_set' || empty($carrierParams['ui_type']))
    				$param_set_count++;
    		}
    	}
    	
    	$account="1";
    	if(isset($_GET['account']))
    		$account=$_GET['account'];
    	
    	//重新统计物流账号数量
    	if(!empty($serviceUserById['response']['data']['carrierAccountList'])){
    		$account = count($serviceUserById['response']['data']['carrierAccountList']);
    	}
    	
    	return $this->renderPartial('_shippingservice',[
    			'carrier_code'=>$_GET['code'],
    			'type'=>$_GET['type'],
    			'serviceUserById'=>$serviceUserById['response']['data'],
    			'key'=>$key,
    			'account'=>$account,
    			'param_set_count'=>$param_set_count,
    			]);
    }
    
    /**
     * 第三方仓库运输服务编辑保存
     *
     * +-------------------------------------------------------------------------------------------
     * log			name	date					note
     * @author		hqw		2016/02/23				初始化
     * +-------------------------------------------------------------------------------------------
     */
    public function actionSaveOverseaShippingservice(){
    	$tmp_post = \Yii::$app->request->post();
    	$id = $tmp_post['serviceID'];
    	$carrier_code = $tmp_post['carrier_code'];
    	$type = $tmp_post['type'];
    	$params = $tmp_post['params'];
    	
    	if($type != 'add' && (!isset($params['accountID']) || empty($params['accountID']))){
    		if(substr($carrier_code, 0, 3) == 'lb_'){
    			return exit(json_encode(array('code'=>1,'msg'=>'物流账号为必填项！')));
    		}
    	}
    	$res = CarrierOpenHelper::saveCarrierShippingServiceUserById($id, $carrier_code, $type, $params);
    	
    	$result = array('code'=>$res['response']['code'],'msg'=>$res['response']['msg'][0]);
    	
    	return exit(json_encode($result));
    }
    
    /**
     * 第三方仓库运输服务开启或关闭
     *
     * +-------------------------------------------------------------------------------------------
     * log			name	date					note
     * @author		hqw		2016/02/23				初始化
     * +-------------------------------------------------------------------------------------------
     * @return	
     * +-------------------------------------------------------------------------------------------
     */
    public function actionOpenOrCloseShipping(){
    	$res = CarrierOpenHelper::carrierShippingServiceOnOff($_POST['id'],$_POST['is_used']);
    	return $res['response']['code'].','.$res['response']['msg'];
    }
    
    /**
     * 第三方仓库运输服务匹配规则界面
     *
     * +-------------------------------------------------------------------------------------------
     * log			name	date					note
     * @author		hqw		2016/02/23				初始化
     * +-------------------------------------------------------------------------------------------
     */
    public function actionShippingrules(){
    	$sevice_name = $_GET['sname'];
    	
    	$transportation_service_id = $_GET['sid'];
    	$id = $_GET['id'];
    	//获取对应$id的分配规则
    	$rule = MatchingRule::find()->where(['id'=>$id])->one();
    	if (empty($rule)){
    		$rule = new MatchingRule();
    	}
    	//收件国家
    	$query = SysCountry::find();
    	$regions = $query->orderBy('region')->groupBy('region')->select('region')->asArray()->all();
    	$countrys =[];
    	foreach ($regions as $region){
    		$arr['name']= $region['region'];
    		$arr['value']=Helper_Array::toHashmap(SysCountry::find()->where(['region'=>$region['region']])->orderBy('country_en')->select(['country_code', "CONCAT( country_zh ,'(', country_en ,')' ) as country_name "])->asArray()->all(),'country_code','country_name');
    		$countrys[]= $arr;
    	}
    	//国家中文名
    	$region = WarehouseHelper::countryRegionChName();
    	//平台
    	$source = MatchingRule::$source;
    	//站点
    	$sites = PlatformAccountApi::getAllPlatformOrderSite();
    	//账号
    	$selleruserids=PlatformAccountApi::getAllPlatformOrderSelleruseridLabelMap();
    	//买家选择物流物流
    	$buyer_transportation_services = PlatformAccountApi::getAllPlatformShippingServices();
    	//商品标签
    	$product_tags = ProductApiHelper::getAllTags();
    	 
    	return $this->renderPartial('_shippingrules',[
    			'countrys'=>$countrys,
    			'region'=>$region,
    			'rule'=>$rule,
    			'rules'=>MatchingRule::$rules,//获取分配项
    			'source'=>$source,
    			'sites'=>$sites,
    			'selleruserids'=>$selleruserids,
    			'buyer_transportation_services'=>$buyer_transportation_services,
    			'product_tags'=>$product_tags,
    			'sevice_name'=>$sevice_name,
    			'transportation_service_id'=>$transportation_service_id,
    			'proprietary_warehouse_id'=>$_GET['warehouse_id']
    			]);
    }
    
    /**
     * 第三方仓库运输服务匹配规则保存
     *
     * +-------------------------------------------------------------------------------------------
     * log			name	date					note
     * @author		hqw		2016/02/23				初始化
     * +-------------------------------------------------------------------------------------------
     * @return	
     * +-------------------------------------------------------------------------------------------
     */
    public function actionSaveshippingrules(){
    	if (Yii::$app->request->isPost){
    		$id = Yii::$app->request->post('id');
    		$shippingRules = $_POST;
    		$res = CarrierOpenHelper::saveShippingRules($id, $shippingRules);
    		return $res['response']['code'].','.$res['response']['msg'];
    	}
    }
    
    /**
     * 第三方仓库运输服务更新
     *
     * +-------------------------------------------------------------------------------------------
     * log			name	date					note
     * @author		hqw		2016/02/23				初始化
     * +-------------------------------------------------------------------------------------------
     * @return	
     * +-------------------------------------------------------------------------------------------
     */
    public function actionUpdateOverseaShippingService(){
    	$carrier_code = $_POST['carrier_code'];
    	
    	$res = CarrierOpenHelper::refreshCarrierShippingMethod($carrier_code);
    	return $res['response']['code'].','.$res['response']['msg'];
    }
    
    /**
     * 仓库分配规则列表
     *
     * +-------------------------------------------------------------------------------------------
     * log			name	date					note
     * @author		hqw		2016/02/23				初始化
     * +-------------------------------------------------------------------------------------------
     */
    public function actionWarehouseRuleList(){
    	//获取启用的仓库Map
    	$warehouseIdNameMap = InventoryHelper::getWarehouseOrOverseaIdNameMap(1, -1);

    	$tmpParams = array();
    	
    	$tmpParams['warehouse_is_active'] = 'Y';
    	
    	$tmpGet = \Yii::$app->request->get();
    	
    	if(isset($tmpGet['warehouse_id'])){
    		$tmpParams['warehouse_id'] = $tmpGet['warehouse_id'];
    	}
    	
    	if(isset($tmpGet['warehouse_type'])){
    		$tmpParams['warehouse_type'] = $tmpGet['warehouse_type'];
    	}
    	
    	if(isset($tmpGet['warehouse_state'])){
    		$tmpParams['warehouse_state'] = $tmpGet['warehouse_state'];
    	}
    	
    	$warehouseMatchRuleInfo = InventoryHelper::getAllWarehouseMatchingRuleList(@$_GET['per-page'], array('warehouse_arr'=>$warehouseIdNameMap)+$tmpParams);
    	return $this->render('warehouseRuleList',['warehouseIdNameMap'=>$warehouseIdNameMap,'warehouseMatchRuleInfo'=>$warehouseMatchRuleInfo]);
    }
    
    public function actionEditWarehouseExcelFormat(){
    	$res = InventoryHelper::getWarehouseOverseaExcelFormat($_POST['warehouse_id']);
    	
    	$excel_format = isset($res['excel_format'])?$res['excel_format']:'';
    	$excel_mode = (isset($res['excel_mode']) && !empty($res['excel_mode']))?$res['excel_mode']:'orderToOneLine';
    	
    	return $this->renderPartial('_excelFormat',[
    			'carrier_code'=>$_POST['warehouse_id'],
    			'excelSysDATA'=>ExcelHelper::$content,
    			'excel_mode'=>$excel_mode,
    			'excel_format'=>$excel_format,
    			]);
    }
    
    /**
     * 根据excel文件返回列名数组
     *
     *
     * +-------------------------------------------------------------------------------------------
     * log			name	date					note
     * @author		hqw		2016/02/25				初始化
     * +-------------------------------------------------------------------------------------------
     */
    public function actionExcelupload(){
    	if (!empty ($_FILES["input_import_file"]))
    		$files = $_FILES["input_import_file"];
    
    	$result = \eagle\modules\util\helpers\ExcelHelper::excelToArray($files,[],false);
    	$rtnstr = json_encode($result[1]);
    	exit($rtnstr);
    }
    
    /**
     * 自定义物流中，保存当前的Excel格式
     *
     * +-------------------------------------------------------------------------------------------
     * log			name	date					note
     * @author		hqw		2016/02/25				初始化
     * +-------------------------------------------------------------------------------------------
     */
    public function actionSaveExcelFormat(){
    	$res = InventoryHelper::saveCustomWarehouseExcelFormat($_POST['carrier_code'],$_POST['params']);
    	echo $res['response']['code'].','.$res['response']['msg'];
    }
    
    /**
     * 修改仓库名称、仓库地址信息
     *
     * +-------------------------------------------------------------------------------------------
     * log			name	date					note
     * @author		hqw		2016/04/09				初始化
     * +-------------------------------------------------------------------------------------------
     */
    public function actionEditWarehouseInfo(){
    	$v = \Yii::$app->request->post();
    	$warehouse_id = $v['warehouse_id'];
    	
    	$warehouseOneInfo = InventoryHelper::getWarehouseInfoOneById($warehouse_id);
    	
    	//获取国家代码
    	$countryComboBox = WarehouseHelper::countryComboBoxData();
    	
    	return $this->renderPartial('editWarehouseInfo',['warehouseOneInfo'=>$warehouseOneInfo, 'countryComboBox'=>$countryComboBox]);
    }
    
    public function actionGetOverseaWarehouseInfo(){
		$type = empty($_POST['type']) ? 'syscarrier' : $_POST['type'];
    	$warehouse_id = empty($_POST['warehouse_id']) ? -1 : $_POST['warehouse_id'];

    	$relatedparams = array();
    	
    	if(($type == 'syscarrier') && ($warehouse_id == -1)){
    		//组织未开启过的海外仓库代码
    		$overseaWarehouseArr = CarrierOpenHelper::getShippingOrerseaWarehouseMap('', true);
    		 
    		$warehouseIdNameMap = InventoryHelper::getWarehouseOrOverseaIdNameMap(1, 1 ,1);
    		 
    		foreach ($warehouseIdNameMap as $warehouseIdNameVal){
    			if(empty($warehouseIdNameVal['carrier_code']) || empty($warehouseIdNameVal['third_party_code'])) continue;
    		
    			if(isset($overseaWarehouseArr[$warehouseIdNameVal['carrier_code']][$warehouseIdNameVal['third_party_code']])){
    				unset($overseaWarehouseArr[$warehouseIdNameVal['carrier_code']][$warehouseIdNameVal['third_party_code']]);
    			}
    		}
    		 
    		$carrierOverseaList = CarrierOpenHelper::getNotOpenCarrierArr(1, 2 ,1);
    		
    		$relatedparams['notOverseaWarehouseArr'] = array();
    		
    		foreach ($overseaWarehouseArr as $overseaWarehouseKey => $overseaWarehouseVal){
    			foreach ($overseaWarehouseVal as $overseaWarehouseVal_key => $overseaWarehouseVal_val){
    				$relatedparams['notOverseaWarehouseArr'][$overseaWarehouseKey.':'.$overseaWarehouseVal_key] = $carrierOverseaList[$overseaWarehouseKey].'-'.$overseaWarehouseVal_val;
    			}
    		}
    	}else if((($type == 'syscarrier') || ($type == 'selfcarrier')) && ($warehouse_id != -1)){

    		$code='';$third_party_code='';
    		if($type == 'syscarrier'){
    			$code=substr($_POST['code'], 0,strrpos($_POST['code'],'_'));
    			$third_party_code=substr($_POST['code'],strrpos($_POST['code'],'_')+1);
    		}
    		
    		if($warehouse_id==-2){
    			$warehouseOneInfo=[
	    			'warehouse_id' => '0',
    				'name' => '',
    				'is_active' => 'N',
    				'is_oversea' => '1',
    				'carrier_code' => $code,
    				'third_party_code' => $third_party_code,
    				'oversea_type' => '0',
    				'is_zero_inventory' => '0',
    			];
    		}
    		else
    			$warehouseOneInfo = InventoryHelper::getWarehouseInfoOneById($warehouse_id);

    		if($type == 'syscarrier'){
    			$shippingMethodInfo = CarrierOpenHelper::getShippingMethodNameInfo(array('warehouse_id'=>$warehouse_id), -1);

    			//和总表作比较，没有的插入
    			$shippingMethodInfo = $shippingMethodInfo['data'];			
    			$sysshippingMethod = SysShippingMethod::find()->where(['carrier_code'=>$code,'third_party_code'=>$third_party_code])->asArray()->all();
    			foreach ($sysshippingMethod as $sysshippingMethodone){
    				$tmp=0;
    				if(!empty($shippingMethodInfo)){
    					foreach ($shippingMethodInfo as &$shippingMethodInfoone){
    						if($sysshippingMethodone['carrier_code']==$shippingMethodInfoone['carrier_code'] && $sysshippingMethodone['shipping_method_code']==$shippingMethodInfoone['shipping_method_code']){
    							$shippingMethodInfoone['shipping_method_name']=$sysshippingMethodone['shipping_method_name'];
    							$tmp=1;
    							break;
    						}
    					}
    				}
    				if($tmp==0 && $sysshippingMethodone['is_close']==0){
    					$shippingMethodInfo[]=[
    							'id' => 0,
    							'carrier_code' => $sysshippingMethodone['carrier_code'],
    							'carrier_params' => '',
    							'ship_address' => '',
    							'return_address' =>'',
    							'is_used' => 0,
    							'service_name' => '',
    							'service_code' =>'',
    							'auto_ship' => 0,
    							'web' => 'http://www.17track.net',
    							'create_time' => '',
    							'update_time' => '',
    							'carrier_account_id' => 0,
    							'extra_carrier' =>'',
    							'carrier_name' => '',
    							'shipping_method_name' => $sysshippingMethodone['shipping_method_name'],
    							'shipping_method_code' => $sysshippingMethodone['shipping_method_code'],
    							'third_party_code' => $sysshippingMethodone['third_party_code'],
    							'warehouse_name' => '',
    							'address' =>'',
    							'is_custom' => 0,
    							'[custom_template_print' =>'',
    							'print_type' => 0,
    							'print_params' =>'',
    							'transport_service_type' => 0,
    							'aging' =>'',
    							'is_tracking_number' => 0,
    							'[proprietary_warehouse' =>'',
    							'declaration_max_value' => 0.00,
    							'declaration_max_currency' => 'USD',
    							'declaration_max_weight' => 0.0000,
    							'customer_number_config' =>'',
    							'is_del' => 0,
    							'common_address_id' => 0,
    							'is_copy' => 0,
    							'account_name' => '',
    					];
    				}
    			}
    			
    			$carrierAccountInfo = CarrierOpenHelper::getCarrierAccountInfoByWarehouseId($warehouseOneInfo['carrier_code'], $warehouse_id);
    			$carrierAccountIsused=$carrierAccountInfo;
    			foreach ($carrierAccountIsused as $key=>$accountone){
    				if($accountone['is_used']==0)
    					unset($carrierAccountIsused[$key]);
    			}
    		}else{
    			$carrierAccountInfo = CarrierOpenHelper::getCustomCarrierCodeByWarehouseId($warehouse_id, $warehouseOneInfo['name']);
    			
    			if($carrierAccountInfo['response']['code'] == 0){
    				$carrierAccountInfo = $carrierAccountInfo['response']['data'];
    			}else{
    				$carrierAccountInfo = 0;
    			}
    			
    			$shippingMethodInfo = CarrierOpenHelper::getShippingMethodNameInfo(array('self_warehouse_id'=>$warehouse_id), -1);
    			
    			$shippingMethodInfo = $shippingMethodInfo['data'];
    		}
    		
    		$shippingNameIdMap = array();
    		if(count($shippingMethodInfo) > 0){
    			foreach ($shippingMethodInfo as $key=>$val){
    				if($type == 'syscarrier'){
    					$shippingNameIdMap[$val['shipping_method_code']] = $val['shipping_method_name'];
    				}else{
    					$shippingNameIdMap[$key] = $val['shipping_method_name'];
    				}
    			}
    		}
    		
    		$serviceUser = array();
    		if($type == 'syscarrier'){
    			$serviceUserArr = CarrierOpenHelper::getCarrierShippingServiceUserByCarrierCode($warehouseOneInfo['carrier_code']);
    		}else{
    			$serviceUserArr = CarrierOpenHelper::getCarrierShippingServiceUserByCarrierCode($carrierAccountInfo);
    		}
    		 
    		if(count($serviceUserArr) > 0){
    			foreach ($serviceUserArr as $serviceUserone){
    				$serviceUser[$serviceUserone['id']] = $serviceUserone['rule'];
    			}
    		}
    		
    		$relatedparams['carrierAccountInfo'] = $carrierAccountInfo;
    		$relatedparams['carrierAccountInfo_isused'] = $carrierAccountIsused;
    		$relatedparams['shippingNameIdMap'] = $shippingNameIdMap;
    		$relatedparams['shippingMethodInfo'] = $shippingMethodInfo;
    		$relatedparams['warehouseOneInfo'] = $warehouseOneInfo;
    		$relatedparams['serviceUser'] = $serviceUser;
    	}
		
    	return $this->renderPartial('reloadOverseaWarehouse',['type'=>$type,'warehouse_id'=>$warehouse_id,'relatedparams'=>$relatedparams]);
    }
    
    public function actionGetOverseaCarrierInfo(){
    	if(empty($_POST['code_related'])) return false;

    	$code_relatedArr = explode(":",$_POST['code_related']);
    	$carrier_account = CarrierOpenHelper::carrierUserAccountShowById($code_relatedArr[0], 0);

    	$tmpHtml = '';

    	foreach (@$carrier_account['response']['data']['authParams'] as $k=>$p){
    		$req = $p['carrier_is_required'];
    		$req_class = ($req)?'required':'';
    		$type = $p['carrier_display_type'];
    		if($p['carrier_is_encrypt']) $type = 'password';
    		$name = $p['carrier_param_name'];
    		$list = $p['carrier_param_value'];
    		$val = $p['param_value'];
    		
    		$tmpHtml .= "<tr><td>"
    			."<label>".($req == true ? '<b style="color: red">*</b>' : '').$name."：</label>".($type == 'text' ? Html::input($type,'carrier_params['.$k.']',$val,['class'=>'modal_input iv-input '.$req_class]) : Html::dropDownList('carrier_params['.$k.']',$val,$list,['class'=>'modal_input iv-input '.$req_class]))
    			."</td></tr>";
    	}
    	
    	return $tmpHtml;
    }

    //第三方仓库保存，海外仓/自定义仓库
    public function actionOverseaAndCarrierSave(){
    	$result = array('code'=>1,'msg'=>'');
    	
    	$tmp_post = \Yii::$app->request->post();

    	if(isset($tmp_post['nicknameself'])){
    		if(empty($tmp_post['nicknameself'])){
    			$result['msg'] = '仓库名不能为空';
    			return json_encode($result);
    		}
    		
    		$tmpParams['warehouse_name'] = $_POST['nicknameself'];
    		$tmpParams['warehouse_id'] = -1;
    		$tmpParams['address_nation'] = '中国';
    		$tmpParams['is_active'] = 'Y';
    		$tmpParams['is_oversea'] = '1';
    		$tmpParams['oversea_type'] = 1;
    		
    		$resultEnagle = InventoryHelper::saveWarehouseInfoById($tmpParams);
    		
    		if($resultEnagle['response']['code'] == 0){
    			$customParams = array();
    			$customParams['carrier_name'] = $tmp_post['nicknameself'].'self';
    			$customParams['is_used'] = 1;
    			$customParams['carrier_type'] = 1;
    			$customParams['warehouse_id'] = $resultEnagle['response']['data'];
    			
    			$resCustomCarrier = CarrierOpenHelper::saveCustomCarrier('', $customParams);
    		}
    		
    		$result['code'] = $resultEnagle['response']['code'];
    		$result['msg'] = $resultEnagle['response']['msg'];
    	}else{
    		if(empty($tmp_post['notWarehouseDropDownid'])){
    			$result['msg'] = '非法操作,传入后台数据为空e_1';
    			return json_encode($result);
    		}
    		
    		if(empty($tmp_post['hidwarehouse'])){
    			$result['msg'] = '仓库名不能为空';
    			return json_encode($result);
    		}
    
    		if(empty($tmp_post['nickname'])){
    			$result['msg'] = '账号别名不能为空';
    			return json_encode($result);
    		}
    		 
    		//这里把判断账号是否重复的逻辑写在这里是因为现在仓库跟物流账号绑定写在一起
    		$count = SysCarrierAccount::find()->where(['carrier_name'=>$tmp_post['nickname'],'is_del'=>0])->count();
    		 
    		if ($count>0){
    			$result['msg'] = '账号昵称重复.';
    			return json_encode($result);
    		}
    		 
    		$code_relatedArr = explode(":",$tmp_post['notWarehouseDropDownid']);
    		
    		$tmpParams['warehouse_name'] = $tmp_post['hidwarehouse'];
    		$tmpParams['warehouse_id'] = -1;
    		$tmpParams['address_nation'] = '中国';
    		$tmpParams['is_active'] = 'Y';
    		$tmpParams['is_oversea'] = '1';
    		$tmpParams['oversea_type'] = 0;
    		 
    		//这里定义了两个参数组一个用于仓库的保存，一个用户物流账号的报存
    		$tmpParams['carrier_code'] = $code_relatedArr[0];
    		$tmpParams['third_party_code'] = $code_relatedArr[1];
    		$tmp_post['carrier_code'] = $code_relatedArr[0];
    		$tmp_post['third_party_code'] = $code_relatedArr[1];
    		 
    		if(empty($tmpParams['carrier_code'])){
    			$result['msg'] = '物流商代码必填';
    			return json_encode($result);
    		}
    		 
    		$resultEnagle = InventoryHelper::saveWarehouseInfoById($tmpParams);
    		 
    		if($resultEnagle['response']['code'] == 0){
    			$tmp_post['warehouse_id'] = $resultEnagle['response']['data'];
    		
    			$id = empty($tmp_post['id']) ? '' : $tmp_post['id'];
    			$accountParams = array(
    					'accountNickname'=>$tmp_post['nickname'],//账户别名	必填
    					'carrier_code'=>$tmp_post['carrier_code'],//物流商代码	必填
    					'is_default'=>empty($tmp_post['default']) ? 0 : $tmp_post['default'],//是否开启为默认账号	必填
    					'carrier_params' => empty($tmp_post['carrier_params']) ? '' : $tmp_post['carrier_params'],
    					'warehouse_id'=> $tmp_post['warehouse_id'],
    					'warehouse'=>isset($tmp_post['third_party_code']) ? array($tmp_post['third_party_code']) : array(),
    			);
    		
    			$res = CarrierOpenHelper::carrierUserAccountAddOrEdit($id, $accountParams);
    		
    			$result['code'] = $res['response']['code'];
    			$result['msg'] = $res['response']['msg'];
    		
    			return json_encode($result);
    		}else{
    			$result['code'] = $resultEnagle['response']['code'];
    			$result['msg'] = $resultEnagle['response']['msg'];
    		}
    	}
    	
    	return json_encode($result);
    }
    
    /**
     +----------------------------------------------------------
     * 仓库习惯设置
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * log			name	   date			note
     * @author		lrq 	2018/07/04		初始化
     +----------------------------------------------------------
     **/
    public function actionCustomsettings(){
    	return $this->render('customsettings');
    }
   
}
