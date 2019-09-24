<?php

namespace eagle\modules\configuration\controllers;

use Yii;
use eagle\modules\carrier\helpers\CarrierOpenHelper;
use eagle\modules\carrier\models\SysCarrier;
use yii\web\Controller;
use \yii\web\Response;
use common\helpers\Helper_Array;
use eagle\models\SysCountry;
use eagle\modules\carrier\models\MatchingRule;
use eagle\modules\platform\apihelpers\PlatformAccountApi;
use eagle\modules\catalog\helpers\ProductApiHelper;
use eagle\modules\inventory\helpers\WarehouseHelper;
use yii\filters\VerbFilter;
use eagle\modules\util\helpers\ExcelHelper;
use eagle\modules\carrier\apihelpers\CarrierApiHelper;
use eagle\modules\inventory\helpers\InventoryHelper;
use eagle\modules\util\helpers\ConfigHelper;
use eagle\modules\util\models\LabelTip;
use eagle\models\SysShippingMethod;
use eagle\models\CrCarrierTemplate;
use eagle\models\carrier\CrTemplate;
use yii\helpers\Html;
use eagle\models\carrier\SysShippingService;
use eagle\models\carrier\CommonDeclaredInfo;
use eagle\modules\catalog\models\Product;
use eagle\models\SaasAliexpressUser;
use common\api\carrierAPI\LB_ALIONLINEDELIVERYCarrierAPI;
use Qiniu\json_decode;
use eagle\modules\carrier\apihelpers\PrintPdfHelper;
use eagle\modules\lazada\apihelpers\LazadaApiHelper;

class CarrierconfigController extends \eagle\components\Controller
{
	public $enableCsrfValidation = FALSE;
	
	/**
	 * 常用筛选管理
	 * 
	 */
	public function actionSearchlist(){
		$searchlist = [];
		if(isset($_GET['type'])){
			$path = '';
			switch ($_GET['type']){
				case 'ebayoms':$path = '';break;
				case 'aliexpressoms':$path = 'aliexpressorder/order_custom_condition';break;
				case 'delivery':$path = 'delivery/order';break;
				case 'carrier':$path = 'carrier/carrierprocess';break;
			}
			if(!empty($path)){
				$custom_condition = ConfigHelper::getConfig($path);
				if (!empty($custom_condition) && is_string($custom_condition)){
					$searchlist = array_keys(json_decode($custom_condition,true));
				}
			}
		}
		return $this->render('searchlist',['searchlist'=>$searchlist]);
	}
	/**
	 * 删除指定常用筛选
	 * 
	 */
	public function actionDelCommonSearch(){
		if(isset($_POST['type']) && isset($_POST['name'])){
			$path = '';
			switch ($_POST['type']){
				case 'ebayoms':$path = '';break;
				case 'aliexpressoms':$path = 'aliexpressorder/order_custom_condition';break;
				case 'delivery':$path = 'delivery/order';break;
				case 'carrier':$path = 'carrier/carrierprocess';break;
			}
			if(empty($path)) return '0请确认传入参数2!';
			$res = ConfigHelper::delConfig($path, $_POST['name']);
			return $res['code'].$res['msg'];
		}
		return '0请确认传入参数！';
	}
	/**
	 * 常用地址管理
	 * 
	 */
	public function actionCommonaddresslist(){
		$address_list = [];
		$address = CarrierOpenHelper::getCarrierAddressNameArrByType(1);
		if(isset($address['response']['data']['list']) && count($address['response']['data']['list'])>0){
			foreach ($address['response']['data']['list'] as $k=>$name){
				$address_list[] = CarrierOpenHelper::getCarrierAccountAdderssById($k,1,'');
			}
		}
		return $this->render('commonaddresslist',['address'=>$address,'address_list'=>$address_list]);
	}
	/**
	 * 以json格式输出，主要用于ajax响应
	 */
	public function renderJson($data){
		\Yii::$app->response->format = Response::FORMAT_JSON;
		return is_array($data)?$data:(isset($data->attributes)?$data->attributes:$data);
	}
	/**
	 * 打开物流标签自定义界面
	 *
	 *
	 * +-------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		zwd		2016/1/5				初始化
	 * +-------------------------------------------------------------------------------------------
	 */
	public function actionCarrierCustomLabelList(){
		$params = $_GET;
		$customLabel = CarrierOpenHelper::getCarrierCustomLabelTemplates($params);
		$pagination = $customLabel['pagination'];
		$templates_total = $pagination->totalCount;
		$data = $customLabel['data'];
		$sort = $customLabel['sort'];
		 
		$sysLabel = CarrierOpenHelper::getCarrierSysLabelTemplates($params);
		$sys_pagination = $sysLabel['pagination'];
		$sys_templates_total = $sys_pagination->totalCount;
		$sys_data = $sysLabel['data'];
		$sys_sort = $sysLabel['sort'];
		$size = $sysLabel['size'];
		
		//页面上tab的激活标识
		$tab_active = \Yii::$app->request->get('tab_active');
		return $this->render('CarrierCustomLabel',[
					'templates_total'=>$templates_total,
					'templates' => $data,
					'pages' => $pagination,
					'sort'=>$sort,
					'sys_templates_total'=>$sys_templates_total,
					'systemplates'=>$sys_data,
					'syspages' => $sys_pagination,
					'syssort'=>$sys_sort,
					'size'=>$size,
					'selftemplate'=>'',
					'tab_active'=>$tab_active
				]);
	}
	/**
	 * 预览物流标签
	 * 预览分三种情况：
	 * 1 编辑中未保存时预览：POST提交template_content
	 * 2 已保存未编辑预览（列表页）：GET提交template_id
	 * 3 打印前预览：替换参数
	 */
	public function actionPreviewCarrierTemplate(){
		$params = $_GET;
		if(\Yii::$app->request->IsPost){
			$params = $_POST;
		}
		$template = CarrierOpenHelper::getCarrierTemplateById(@$params['is_sys'],@$params['template_id'],$params);
		return $this->renderPartial('PreviewCarrierTemplate',[
					'template'=>$template,
				]);
	}
	/**
	 * 编辑物流标签模版
	 */
	public function actionEditCarrierTemplate(){
		// 获取模版信息
		$id = isset($_GET['id'])?$_GET['id']:'';
		$uid = \Yii::$app->user->id;
		
		if(($id == -1) && ($uid == 1)){
			$addressListType = ['地址单'=>'地址单','报关单'=>'报关单','配货单'=>'配货单'];
			$sysShippingMethodArr = SysShippingMethod::find()->select(['id','carrier_code','shipping_method_name','template'])->where(['is_print'=>1])->orderBy('carrier_code')->asArray()->all();
			 
			$sysCarrierArr = SysCarrier::find()->select(['carrier_code','carrier_name'])->asArray()->all();
			$sysCarrierArr = Helper_Array::toHashmap($sysCarrierArr, 'carrier_code', 'carrier_name');
			 
			$shippingMethodCarrierMap = array();
			 
			foreach ($sysShippingMethodArr as $sysShippingMethod){
				if(isset($sysCarrierArr[$sysShippingMethod['carrier_code']]))
					$shippingMethodCarrierMap[$sysShippingMethod['id']] = $sysCarrierArr[$sysShippingMethod['carrier_code']].'-'.$sysShippingMethod['shipping_method_name'].(empty($sysShippingMethod['template']) ? '' : '-'.$sysShippingMethod['template']).':'.$sysShippingMethod['carrier_code'];
			}
			 
			$sysTemplateArr = array();
			$sysTemplate = CrCarrierTemplate::find()->select(['id','template_name','template_type','shipping_method_id','country_codes'])->asArray()->all();
			 
			foreach ($sysTemplate as $tmpsysTemplateone){
				$sysTemplateArr[$tmpsysTemplateone['id']] = $tmpsysTemplateone['template_name'].'-'.$tmpsysTemplateone['template_type'].
				'-'.$tmpsysTemplateone['shipping_method_id'].'-'.$tmpsysTemplateone['country_codes'];
			}
			 
			$template = new CrTemplate();
			$template->template_id = '';
			$template->template_name = '';
			$template->template_type = empty($sysTemplateOne['template_type']) ? '地址单' : $sysTemplateOne['template_type'];
			$template->template_width = empty($sysTemplateOne['template_width']) ? '100' : $sysTemplateOne['template_width'];
			$template->template_height = empty($sysTemplateOne['template_height']) ? '100' : $sysTemplateOne['template_height'];
			$template->template_content = empty($sysTemplateOne['template_content']) ? '' : $sysTemplateOne['template_content'];
			 
			$userCrTemplate = CrTemplate::find()->asArray()->all();
			$userCrTemplateArr = Helper_Array::toHashmap($userCrTemplate, 'template_id', 'template_name');
		}else{
			$template = CarrierOpenHelper::getCarrierTemplateById(0,$id,$_GET);
			$userCrTemplateArr = array();
			$addressListType = array();
			$sysTemplateArr = array();
		}
		
		return $this->renderPartial('EditCarrierTemplate',[
				'template'=>$template,'uid'=>$uid,'id'=>$id,'userCrTemplateArr'=>$userCrTemplateArr,'addressListType'=>$addressListType,
				'sysTemplateArr'=>$sysTemplateArr
				]);
	}
	/*
	 * 复制系统模版到自定义
	*/
	public function actionCopytemplateToCustom(){
		//验证
		if(!\Yii::$app->request->getIsAjax())return false;
		$id = \Yii::$app->request->post('template_id');
		$name = \Yii::$app->request->post('template_name');
		if($id == null || $name == null)return false;
		//复制
		$res = CarrierOpenHelper::copySysTemplateToCustom($id,$name);
		if($res['response']['code'] == 0) return true;
		return false;
	}
	
	/*
	 * 复制自定义模版到自定义
	*/
	public function actionCopytemplateToCustomCus(){
		//验证
		if(!\Yii::$app->request->getIsAjax())return false;
		$id = \Yii::$app->request->post('template_id_cus');
		$name = \Yii::$app->request->post('template_name_cus');
		
		if($id == null || $name == null)return false;
		//复制
		$res = CarrierOpenHelper::copyCusTemplateToCustom($id,$name);
		if($res['response']['code'] == 0) return true;
		return false;
	}
	
	/*
	 * 获取自定义标签菜单项
	*/
	public function actionGetCustomMenu(){
		// 设置菜单项
		$allItems = require(__DIR__.'/../views/carrierconfig/customtemplate_menu.php');
		$items = $allItems[$_GET['template_type']];
		return $this->renderJson($items);
	}
	/**
	 * 保存自定义标签模版
	 */
	public function actionSavecustomprint(){
		$res = CarrierOpenHelper::saveCustomPrintLabel($_POST);
		return $this->renderJson($res);
	}
	/**
	 * 删除自定义标签模版
	 */
	public function actionDeleteCarrierCustomTemplate(){
		$data = CarrierOpenHelper::delCustomTemplate($_GET['template_id']);
		return $this->renderJson($data);
	}
	/*
	 * 检查模版名称是否重复
	*/
	public function actionChecktemplatename(){
		if(!\Yii::$app->request->getIsAjax())return false;
		$name = $_POST['templatename'];
		return CarrierOpenHelper::Checktemplatename($name);
	}
	/**
	 * 打开物流模块习惯设置界面
	 *
	 *
	 * +-------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		zwd		2016/1/5				初始化
	 * +-------------------------------------------------------------------------------------------
	 */
	public function actionCarrierModuleCustomSettingList(){
		$msg = '';
		if (Yii::$app->request->isPost){
			$res = CarrierOpenHelper::setCommonCarrierConfig($_POST);
			if($res['response']['code'] != 0){
				$msg = '1'.$res['response']['msg'];
			}
			else $msg = '0';
		}
		$config = CarrierOpenHelper::getCommonCarrierConfig();
		$platformCustomerNumberMode = CarrierOpenHelper::$platformCustomerNumberMode;
		
		$countrys = CarrierApiHelper::getcountrys();
		
		return $this->render('CarrierModuleCustomSettingList',[
					'config'=>$config,
					'platformCustomerNumberMode'=>$platformCustomerNumberMode,
					'msg'=>$msg,
					'countrys'=>$countrys,
				]);
	}
	/**
	 * 打开跟踪号库界面
	 *
	 *
	 * +-------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		zwd		2016/1/5				初始化
	 * +-------------------------------------------------------------------------------------------
	 */
	public function actionTrackwarehouse(){
		//自定义物流商列表
		$carriers = ['-1'=>'自定义物流商'];
		$carrier = CarrierOpenHelper::getOpenCarrierArr(5,1);
		if(!empty($carrier)) $carriers += $carrier;
		//自定义运输服务列表
		$methods = ['-1'=>'自定义运输服务'];
		$method = CarrierOpenHelper::getSysShippingMethodList('',5,false,1);
		if(isset($method['response']['data']) && !empty($method['response']['data'])) $methods += $method['response']['data'];
		//分配状态
		$status = [-1=>'分配状态',0=>'未分配',1=>'已分配'];
		//table列表信息 
		$defaultPageSize = null;
		$data = $params = $_POST;
		if(!empty($_POST)){
			if($params['carrier_name'] == -1) $params['carrier_name'] = null;
			if($params['shipping_method_name'] == -1) $params['shipping_method_name'] = null;
			if($params['is_used'] == -1) $params['is_used'] = null;
		}
		if(!empty($params['carrier_name']) && isset($carrier[$params['carrier_name']]))
			$params['carrier_name'] = $carrier[$params['carrier_name']];
		if(!empty($params['shipping_method_name']) && isset($method['response']['data'][$params['shipping_method_name']]))
			$params['shipping_method_name'] = $method['response']['data'][$params['shipping_method_name']];

		$table = CarrierOpenHelper::getCarrierTrackingNumberList(@$_REQUEST['per-page'], $params);
		return $this->render('trackwarehouse',[
					'carriers'=>$carriers,
					'methods'=>$methods,
					'status'=>$status,
					'table'=>$table,
					'data'=>$data,
				]);
	}
	/**
	 * 打开'添加跟踪号'界面
	 *
	 *
	 * +-------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		zwd		2016/1/5				初始化
	 * +-------------------------------------------------------------------------------------------
	 */
	public function actionInsertTrack(){
		$methods = [];
		$method = CarrierOpenHelper::getSysShippingMethodList('',5);
		if(isset($method['response']['data']) && !empty($method['response']['data'])) $methods = $method['response']['data'];
		return $this->renderPartial('_insertTrack',[
					'methods'=>$methods,
				]);
	}
	/**
	 * 保存新的跟踪号
	 *
	 *
	 * +-------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		zwd		2016/1/5				初始化
	 * +-------------------------------------------------------------------------------------------
	 */
	public function actionSaveTrack(){
		if(!isset($_POST['shipping_service_id']) || trim($_POST['shipping_service_id']) == ''){
			return '*请选择运输服务';
		}
		if(!isset($_POST['insertType']) ||($_POST['insertType'] != 0 && $_POST['insertType'] != 1 && $_POST['insertType'] != 2)){
			return '*请选择添加方式';
		}
		if(!isset($_POST[$_POST['insertType']]['Text']) || empty($_POST[$_POST['insertType']]['Text'])){
			return '*跟踪号不能为空';
		}
		$shipping_service_id = $_POST['shipping_service_id'];
		$trackingNumber_str = $_POST[$_POST['insertType']]['Text'];
		$res = CarrierOpenHelper::saveCustomCarrierTrackingnumber($shipping_service_id, $trackingNumber_str);
		if(!empty($res['response']['data'])){
			print_r($res['response']['data']);
		}
		else{
			return '0';
		}
	}
	/**
	 * 删除跟踪号
	 *
	 *
	 * +-------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		zwd		2016/1/5				初始化
	 * +-------------------------------------------------------------------------------------------
	 */
	public function actionDelTrack(){
		$res = CarrierOpenHelper::delCustomCarrierTrackingnumber($_POST['id']);
		echo $res['response']['code'].','.$res['response']['msg'];
	}
	/**
	 * 跟踪号标记已分配
	 *
	 *
	 * +-------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		zwd		2016/1/6				初始化
	 * +-------------------------------------------------------------------------------------------
	 */
	public function actionMarkTrackNumber(){
		$res = CarrierOpenHelper::tagDistributionCustomCarrierTrackingnumber($_POST['id']);
		echo $res['response']['code'].','.$res['response']['msg'];
	}
	/**
	 * 根据excel文件返回数据
	 *
	 *
	 * +-------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		zwd		2016/1/5				初始化
	 * +-------------------------------------------------------------------------------------------
	 */
	public function actionExceluploadall(){
		if (!empty ($_FILES["input_import_file"]))
			$files = $_FILES["input_import_file"];
	
		$result = \eagle\modules\util\helpers\ExcelHelper::excelToArray($files,[],false);
		$rtnstr = json_encode($result);
		exit($rtnstr);
	}
	/**
	 * 打开运输服务分配规则管理界面
	 *
	 *
	 * +-------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		zwd		2016/1/4				初始化
	 * +-------------------------------------------------------------------------------------------
	 */
	public function actionRule(){
		//获取所有已开启的物流的列表
		$open_carriers = CarrierOpenHelper::getOpenCarrierArr(2,1);
		if(empty($open_carriers)){
			return $this->render('rule',[
					'open_carriers'=>$open_carriers,
					]);
		}
		
		//获取自定义物流的列表的第一对键值对
		list($first_key, $first) = (reset($open_carriers) ? each($open_carriers) : each($open_carriers));
		//如果没有传来的物流key，则用列表中的第一个
		$codes = (isset($_POST['carrier_name']) && isset($open_carriers[$_POST['carrier_name']]))?$_POST['carrier_name']:null;

			//获取已开启的物流运输服务
			$ships = CarrierOpenHelper::getSysShippingMethodList('',4,false,1)['response'];
			$ships = isset($ships['data'])?$ships['data']:[];
			//获取开启的仓库列表信息
			$warehouses = InventoryHelper::getWarehouseOrOverseaIdNameMap(1, -1);
			
			//获取全部的仓库列表信息
			$warehousesAll = InventoryHelper::getWarehouseOrOverseaIdNameMap(-1, -1);
			
			$params = [];
			if(isset($_POST['carrier_name']) && isset($open_carriers[$_POST['carrier_name']])) $params['carrier_name'] = $open_carriers[$_POST['carrier_name']];
			if(isset($_POST['shipping_method_name']) && trim($_POST['shipping_method_name'])!='') $params['shipping_method_name']=@$ships[$_POST['shipping_method_name']];
			
			if(isset($_POST['proprietary_warehouse']) && trim($_POST['proprietary_warehouse'])!=''){
				$params['proprietary_warehouse']=$_POST['proprietary_warehouse'];
				
				if($params['proprietary_warehouse'] == -1){
					unset($params['proprietary_warehouse']);
				}
			}
			
			//获取匹配规则的列表信息
// 			$rules = CarrierOpenHelper::getMatchingRuleList(@$_POST['per-page'],$params,-1);
			$rules = CarrierOpenHelper::getMatchingRuleListNew($params,-1);
			return $this->render('rule',[
					'codes'=>$codes,
					'open_carriers'=>$open_carriers,
					'ships'=>$ships,
					'warehouses'=>$warehouses,
					'warehousesAll'=>$warehousesAll,
					'rules'=>$rules,
					'shipping_method_name'=>@$_POST['shipping_method_name'],
					'proprietary_warehouse'=>@$params['proprietary_warehouse'],
					]);
	}
	/**
	 * 运输服务分配规则优先级移动
	 *
	 *
	 * +-------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		zwd		2016/1/4				初始化
	 * +-------------------------------------------------------------------------------------------
	 */
	public function actionRulePriority(){
		$res = CarrierOpenHelper::setMatchingRulePriority($_POST['ruleIdHigh'], $_POST['ruleIdLow']);
		echo $res['response']['code'].','.$res['response']['msg'];
	}
	
	//运输服务匹配规则优先级移动 新版
	public function actionRuleMovePriority(){
		$result['success'] = true;
		$result['message'] = '';
// 		print_r($_POST['r_ids']);

		if(!isset($_POST['r_ids'])){
			$result['success'] = false;
			$result['message'] = '非法操作';
			exit(json_encode($result));
		}
		
		$tmp_ids = $_POST['r_ids'];
		
		$tmp_mRules = MatchingRule::find()->where(['id'=>$tmp_ids])->all();
		
// 		print_r($tmp_mRules);

		if(count($tmp_mRules) != count($tmp_ids)){
			$result['success'] = false;
			$result['message'] = '异常操作，请刷新界面再操作!';
			exit(json_encode($result));
		}

		if(count($tmp_mRules) > 0){
			//重新排序获取最新的优先级
			$tmp_idbef = array();
			$tmp_int1 = 1;
			foreach ($tmp_ids as $tmp_id_val){
				$tmp_idbef[$tmp_id_val] = $tmp_int1;
				$tmp_int1++;
			}
			
			foreach ($tmp_mRules as $tmp_mRule_Val){
				if(isset($tmp_idbef[$tmp_mRule_Val->id])){
					$tmp_mRule_Val->priority = $tmp_idbef[$tmp_mRule_Val->id];
					$tmp_mRule_Val->save(false);
				}
			}
		}
		
		$result['message'] = '成功操作';
		exit(json_encode($result));
	}
	
	/**
	 * 删除指定运输服务分配规则并返回结果
	 *
	 *
	 * +-------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		zwd		2016/3/11				初始化
	 * +-------------------------------------------------------------------------------------------
	 */
	public function actionDelRule(){
		if(!Yii::$app->request->isPost){
			return json_encode(['code' => 1 ,'msg' => '非法操作']);
		}
		$res = CarrierOpenHelper::DelShippingServiceMatch($_POST['id']);
		return json_encode($res['response']);
	}
	/**
	 * 打开自定义物流界面
	 *
	 *
	 * +-------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		zwd		2015/12/31				初始化
	 * +-------------------------------------------------------------------------------------------
	 */
	public function actionCustom(){
		$custom_carriers = array();
		$customArr = CarrierOpenHelper::getHasOpenCustomCarrier('',array('warehouse_id'=>-1));
		
		foreach ($customArr as $customVal){
			$custom_carriers[$customVal['carrier_code']]['carrier_type'] = $customVal['carrier_type'];
			$custom_carriers[$customVal['carrier_code']]['is_used'] = $customVal['is_used'];
			$custom_carriers[$customVal['carrier_code']]['carrier_name'] = $customVal['carrier_name'];
		}
		
		$tab_active = \Yii::$app->request->get('tab_active');
		
		return $this->render('custom',['tab_active'=>$tab_active,'custom_carriers'=>$custom_carriers]);
	}
	
	/**
	 * 打开自定义物流-新建物流界面
	 *
	 *
	 * +-------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		zwd		2015/12/31				初始化
	 * +-------------------------------------------------------------------------------------------
	 */
	public function actionNewcarrier(){
		return $this->renderPartial('_newCarrier');
	}
	
	/**
	 * 自定义物流中，保存物流
	 *
	 *
	 * +-------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		zwd		2015/12/31				初始化
	 * +-------------------------------------------------------------------------------------------
	 */
	public function actionSavecustomcarrier(){
		$params = $_POST;
		$params['is_used'] = 1;
		$res = CarrierOpenHelper::saveCustomCarrier('',$params);
// 		echo $res['response']['code'].','.$res['response']['msg'];

		$result = array();
		
		$result['code'] = $res['response']['code'];
		$result['msg'] = $res['response']['msg'];
		$result['data'] = $res['response']['data'];
		
		exit(json_encode($result));
	}
	/**
	 * 自定义物流中，开启或关闭<自定义物流商>
	 *
	 *
	 * +-------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		zwd		2015/12/31				初始化
	 * +-------------------------------------------------------------------------------------------
	 */
	public function actionOpen_or_close_custom_carrier(){
		$res = CarrierOpenHelper::customCarrierOpenOrCloseRecord($_POST['carrier_code'],$_POST['is_active']);
		echo $res['response']['code'].','.$res['response']['msg'];
	}
	/**
	 * 自定义物流中，打开Excel格式编辑页面
	 *
	 *
	 * +-------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		zwd		2015/12/31				初始化
	 * +-------------------------------------------------------------------------------------------
	 */
	public function actionOpen_excel_format(){
		$excelSysDATA = ExcelHelper::$content;
		$res = CarrierOpenHelper::getCustomCarrierExcelFormat($_POST['carrier_code']);
// 		print_r($res);
		$excel_format = isset($res['response']['data']['excel_format'])?$res['response']['data']['excel_format']:'';
		$excel_mode = (isset($res['response']['data']['excel_mode']) && !empty($res['response']['data']['excel_mode']))?$res['response']['data']['excel_mode']:'orderToOneLine';
		
		return $this->renderPartial('_excelFormat',[
					'carrier_code'=>$_POST['carrier_code'],
					'excelSysDATA'=>$excelSysDATA,
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
	 * @author		zwd		2015/12/31				初始化
	 * +-------------------------------------------------------------------------------------------
	 */
	public function actionExcelupload(){
		if (!empty ($_FILES["input_import_file"]))
			$files = $_FILES["input_import_file"];
		
		$result = \eagle\modules\util\helpers\ExcelHelper::excelToArray($files,[],false);
		if(is_array($result)){
			$rtnstr = json_encode($result[1]);
			exit($rtnstr);
		}
		else{
			echo $result;
		}
	}
	/**
	 * 自定义物流中，保存当前的Excel格式
	 *
	 *
	 * +-------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		zwd		2015/12/31				初始化
	 * +-------------------------------------------------------------------------------------------
	 */
	public function actionSaveExcelFormat(){
		$res = CarrierOpenHelper::saveCustomCarrierExcelFormat($_POST['carrier_code'],$_POST['params']);
		echo $res['response']['code'].','.$res['response']['msg'];
	}
	/**
	 * 打开物流对接界面
	 *
	 *
	 * +-------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		zwd		2015/12/10				初始化
	 * +-------------------------------------------------------------------------------------------
	 */
	public function actionIndex()
    {
    	//API
//		获取已开启和关闭的物流列表
    	$openCarrierArr = CarrierOpenHelper::getOpenCarrierArr(0, 2, true);
    	$tab_active = \Yii::$app->request->get('tab_active');
    	if(empty($tab_active))
    		$tab_active='apicarrier';
    	
    	//自定义
    	$custom_carriers = array();
    	$customArr = CarrierOpenHelper::getHasOpenCustomCarrier('',array('warehouse_id'=>-1));

    	foreach ($customArr as $customVal){
    		$custom_carriers[$customVal['carrier_code']]['carrier_type'] = $customVal['carrier_type'];
    		$custom_carriers[$customVal['carrier_code']]['is_used'] = $customVal['is_used'];
    		$custom_carriers[$customVal['carrier_code']]['carrier_name'] = $customVal['carrier_name'];
    		$custom_carriers[$customVal['carrier_code']]['carrier_code'] = $customVal['carrier_code'];
    	}
    	
    	//跟踪号库
    	//自定义物流商列表
    	$carriers = ['-1'=>'自定义物流商'];
    	$carrier = CarrierOpenHelper::getOpenCarrierArr(5,1);
    	if(!empty($carrier)) $carriers += $carrier;
    	//自定义运输服务列表
    	$methods = ['-1'=>'自定义运输服务'];
    	$method = CarrierOpenHelper::getSysShippingMethodList('',5,false,1);
    	if(isset($method['response']['data']) && !empty($method['response']['data'])) $methods += $method['response']['data'];
    	//分配状态
    	$status = [-1=>'分配状态',0=>'未分配',1=>'已分配'];
    	//table列表信息
    	$defaultPageSize = null;
    	$data = $params = $_POST;
    	if(!empty($_POST)){
    		if($params['carrier_name'] == -1) $params['carrier_name'] = null;
    		if($params['shipping_method_name'] == -1) $params['shipping_method_name'] = null;
    		if($params['is_used'] == -1) $params['is_used'] = null;
    	}
    	if(!empty($params['carrier_name']) && isset($carrier[$params['carrier_name']]))
    		$params['carrier_name'] = $carrier[$params['carrier_name']];
    	if(!empty($params['shipping_method_name']) && isset($method['response']['data'][$params['shipping_method_name']]))
    		$params['shipping_method_name'] = $method['response']['data'][$params['shipping_method_name']];
    	
    	$table = CarrierOpenHelper::getCarrierTrackingNumberList(@$_REQUEST['per-page'], $params);
    	
    	//海外仓
    	//获取所有仓库ID对应仓库名的值
    	$warehouseIdNameMap = CarrierOpenHelper::getShippingOrerseaWarehouseMapNew('',true,true);
    	    	    	    	
        return $this->render('index',[
        		'tab_active'=>$tab_active,
        		'openCarrierArr'=>$openCarrierArr,
        		
        		'custom_carriers'=>$custom_carriers,
        		
        		'carriers'=>$carriers,
        		'methods'=>$methods,
        		'status'=>$status,
        		'table'=>$table,
        		'data'=>$data,
        		
        		'warehouseIdNameMap'=>$warehouseIdNameMap,
        		]);
        
        
    }
    /**
     * 根据物流账号id，开启或关闭该物流账号
     *
     *
     * +-------------------------------------------------------------------------------------------
     * log			name	date					note
     * @author		zwd		2016/3/10				初始化
     * +-------------------------------------------------------------------------------------------
     */
    public function actionOpenOrCloseAccount(){
    	if (Yii::$app->request->isPost){
    		return $response = json_encode(CarrierOpenHelper::carrierAccountOpenOrCloseById($_POST['aid'],$_POST['is_used'])['response']);
    	}
    	else{
    		return json_encode(['code'=>1,'msg'=>'非法操作']);
    	}
    }
    /**
     * 链接到‘物流商开启’界面
     *
     *
     * +-------------------------------------------------------------------------------------------
     * log			name	date					note
     * @author		zwd		2015/12/10				初始化
     * +-------------------------------------------------------------------------------------------
     */
    public function actionOpen(){
    	$notOpenCarr = CarrierOpenHelper::getNotOpenCarrierArr(0, 0, 1);
    	return $this->renderPartial('_open',['notOpenCarr'=>$notOpenCarr]);
    }

    /**
     * 开启或关闭物流商
     *
     *
     * +-------------------------------------------------------------------------------------------
     * log			name	date					note
     * @author		zwd		2015/12/10				初始化
     * +-------------------------------------------------------------------------------------------
     */
    public function actionOpenorclosecarriernow(){
    	$v = \Yii::$app->request->get();
    	//开启或关闭
    	$status = $v['status'];
    	//物流商代码
    	$code = $v['code'];
    	$res = CarrierOpenHelper::carrierOpenOrCloseRecord($code,$status);
    	echo $res['response']['code'].','.$res['response']['msg'];
    }
    /**
     * 打开新建物流账号界面
     *
     *
     * +-------------------------------------------------------------------------------------------
     * log			name	date					note
     * @author		zwd		2015/12/10				初始化
     * +-------------------------------------------------------------------------------------------
     */
    public function actionNewaccount(){
    	$v = \Yii::$app->request->post();
    	$code = $v['code'];

    	if(empty($code)){
    		$oversea=-1;
    		$id=$v['id'];
    	}
    	else{
    		$oversea=0;
    		$id='';
    	}
    	
//     	if(isset($v['type'])){
//     		$type=1;
//     	}
    	
    	$tmpHtml='';
    	if(isset($v['oversea'])){
    		$oversea=1;
    		$code_relatedArr = explode(":",$code);
    		$carrier_account = CarrierOpenHelper::carrierUserAccountShowById($code_relatedArr[0], 0);

	    	foreach (@$carrier_account['response']['data']['authParams'] as $k=>$p){
	    		$req = $p['carrier_is_required'];
	    		$req_class = ($req)?'required':'';
	    		$type = $p['carrier_display_type'];
	    		if($p['carrier_is_encrypt']) $type = 'password';
	    		$name = $p['carrier_param_name'];
	    		$list = $p['carrier_param_value'];
	    		$val = $p['param_value'];
	    		
	    		$tmpHtml .= "<p class='myline col-xs-12'><span class='col-xs-5 text-right'>"
	    			."<label>".($req == true ? '<b style="color: red">*</b>' : '').$name."：</label></span><span>".($type == 'text' ? Html::input($type,'carrier_params['.$k.']',$val,['class'=>'modal_input iv-input '.$req_class]) : Html::dropDownList('carrier_params['.$k.']',$val,$list,['class'=>'modal_input iv-input '.$req_class]))
	    			."</span><span qtipkey='".$code_relatedArr[0]."-".$k."'></span></p>";
	    	}
    	}
    	
    	$hidwarehouse='';
    	if(isset($v['hidwarehouse']))
    		$hidwarehouse=$v['hidwarehouse'];
    	
    	$account = CarrierOpenHelper::carrierUserAccountShowById($code, 0);

    	//认证参数解释
    	$qtipKeyArr= LabelTip::find()->asArray()->all();
    	
  
    	return $this->renderPartial('_newaccount',[
    			'account'=>$account,
    			'carrier_code'=>$code,
    			'qtipKeyArr'=>$qtipKeyArr,
    			'custom_code'=>$id,
    			'oversea'=>$oversea,
    			'tmpHtml'=>$tmpHtml,
    			'hidwarehouse'=>$hidwarehouse,
    			]);
    }
    /**
     * 打开修改物流账号界面
     *
     *
     * +-------------------------------------------------------------------------------------------
     * log			name	date					note
     * @author		zwd		2015/12/10				初始化
     * +-------------------------------------------------------------------------------------------
     */
    public function actionEditaccount(){
    	$v = \Yii::$app->request->post();
    	$id = $v['id'];
    	$code = $v['code'];
    	if($id == null) $id = 0;
    	$account = CarrierOpenHelper::carrierUserAccountShowById($code, $id);
    	$account_count='';
    	if(isset($v['account']))
    		$account_count = $v['account'];
    	
    	//认证参数解释
    	$qtipKeyArr= LabelTip::find()->asArray()->all();
    	
    	return $this->renderAjax('_editaccount',[
    			'account'=>$account,
    			'carrier_code'=>$code,
    			'accountcount'=>$account_count,
    			'qtipKeyArr'=>$qtipKeyArr,
    			]);
    }
    /**
     * 打开删除物流账号界面
     *
     *
     * +-------------------------------------------------------------------------------------------
     * log			name	date					note
     * @author		zwd		2015/12/10				初始化
     * +-------------------------------------------------------------------------------------------
     */
    public function actionDelaccount(){
    	$v = \Yii::$app->request->get();
    	$id = $v['id'];
    	return $this->renderPartial('_delaccount',['id'=>$id]);
    }
    /**
     * 删除物流账号
     *
     *
     * +-------------------------------------------------------------------------------------------
     * log			name	date					note
     * @author		zwd		2015/12/10				初始化
     * +-------------------------------------------------------------------------------------------
     */
    public function actionDelaccountnow(){
    	$v = \Yii::$app->request->post();
    	$id = $v['id'];
     	$res = CarrierOpenHelper::carrierUserAccountDelById($id);
     	return $res['response']['msg'];
    }
    /**
     * 设置默认物流账号
     *
     *
     * +-------------------------------------------------------------------------------------------
     * log			name	date					note
     * @author		zwd		2015/12/10				初始化
     * +-------------------------------------------------------------------------------------------
     */
    public function actionSetdefaultaccount(){
    	$v = \Yii::$app->request->post();
    	$id = $v['id'];
    	$res = CarrierOpenHelper::carriserUserAccountSetDefault($id);
    	return $res['response']['msg'];
    }
    /**
     * 获取特殊情况的地址显示列表
     * //自定义国家列表
     * $country = array('0'=>[],'1'=>[],'2'=>[]);
     * //自定义省份列表
     * $province = array('0'=>[],'1'=>[],'2'=>[],'3'=>[]);//0发货，1揽收，2回邮，3发货en
     * //自定义城市列表
     * $city = array('0'=>[],'1'=>[],'2'=>[],'3'=>[]);
     * //自定义区域列表
     * $district = array('0'=>[],'1'=>[],'2'=>[],'3'=>[]);
     * //自定义特殊参数
     *$more['shippingfrom'][] = ['type'=>'text','req'=>false,'label'=>'区域代码','name'=>'areacode'];
    			$more['shippingfrom'][] = ['type'=>'dropdownlist','req'=>false,'label'=>'下拉框','name'=>'areacode','list'=>['0'=>'sel1']];
    			$more['pickupaddress'][] = ['type'=>'text','req'=>false,'label'=>'区域代码','name'=>'areacode'];
    			$more['pickupaddress'][] = ['type'=>'dropdownlist','req'=>false,'label'=>'下拉框','name'=>'areacode','list'=>['0'=>'sel1']];
    			$more['returnaddress'][] = ['type'=>'text','req'=>false,'label'=>'区域代码','name'=>'areacode'];
    			$more['returnaddress'][] = ['type'=>'dropdownlist','req'=>false,'label'=>'下拉框','name'=>'areacode','list'=>['0'=>'sel1']];
     *
     * +-------------------------------------------------------------------------------------------
     * log			name	date					note
     * @author		zwd		2015/12/25				初始化
     * +-------------------------------------------------------------------------------------------
     */
    static private function getSpecialAddress($codes){
    	//特殊情况
    	$tmpCarrierCode = $codes;
    	//对接软通宝所属物流
    	if($codes == 'LB_RTBCOMPANYCarrierAPI' || strstr($codes, 'rtbcompany') == 'rtbcompany'){
    		$tmpCarrierCode = 'lb_rtbcompany';
    	}
//     	include(\Yii::getAlias('@web').'docs/carrierconfig/CollectAddress.php');
    	$myxml = simplexml_load_file(\Yii::getAlias('@eagle/web/docs/CollectAddress.xml'));
    	$province_def = [];
    	$city_def = [];
    	$district_def = [];
    	foreach ($myxml->province as $p){
    		$province_def[intval($p['code'])] = strval($p['name']);
    		foreach ($p->city as $c){
    			$city_def[intval($p['code'])][intval($c['code'])] = strval($c['name']);
    			foreach ($c->county as $d){
    				$district_def[intval($c['code'])][intval($d['code'])] = strval($d['name']);
    			}
    		}
    	}
//     	print_r($district_def);
//     	exit;
    	//数据初始化
    	$country = array('0'=>[],'1'=>[],'2'=>[]);
    	$province = array('0'=>[],'1'=>[],'2'=>[],'3'=>[]);//0发货，1揽收，2回邮，3发货en
    	$city = array('0'=>[],'1'=>[],'2'=>[],'3'=>[]);
    	$district = array('0'=>[],'1'=>[],'2'=>[],'3'=>[]);
    	$more = ['shippingfrom'=>[],'pickupaddress'=>[],'returnaddress'=>[]];
    	switch ($tmpCarrierCode){
    		case 'lb_IEUB' :
			case 'lb_IEUBNew' :
    			$country = array('CN'=>'中国','HK'=>'香港','TW'=>'台湾');
    			$province['0'] = $province_def;
    			$province['1'] = $province_def;
    			$city['0'] = $city_def;
    			$city['1'] = $city_def;
    			$district['0'] = $district_def;
    			$district['1'] = $district_def;
    			break;
    		case 'lb_epacket' :
    			$province_def_en=array(
    					'ANHUI'=>'Anhui',
    					'BEIJING'=>'Beijing',
    					'CHONGQING'=>'Chongqing',
    					'FUJIAN'=>'Fujian',
    					'GANSU'=>'Gansu ',
    					'GUANGDONG'=>'Guangdong',
    					'GUANGXI'=>'Guangxi',
    					'GUIZHOU'=>'Guizhou',
    					'HAINAN'=>'Hainan',
    					'HEBEI'=>'Hebei',
    					'HENAN'=>'Henan',
    					'HEILONGJIANG'=>'Heilongjiang',
    					'HUBEI'=>'Hubei',
    					'HUNAN'=>'Hunan',
    					'JIANGXI'=>'Jiangxi',
    					'JIANGSU'=>'Jiangsu',
    					'JILIN'=>'Jilin',
    					'LIAONING'=>'Liaoning',
    					'NEIMENGGU'=>'Inner',
    					'NINGXIA'=>'Ningxia',
    					'QINGHAI'=>'Qinghai',
    					'SHANXI'=>'Shaanxi',
    					'SHANXI'=>'Shanxi',
    					'SHANDONG'=>'Shandong',
    					'SHANGHAI'=>'Shanghai',
    					'SICHUAN'=>'Sichuan',
    					'TIANJIN'=>'Tianjin',
    					'XINJIANG'=>'Xinjiang',
    					'XIZANG'=>'Tibet',
    					'YUNNAN'=>'Yunnan',
    					'ZHEJIANG'=>'Zhejiang',
    					'AOMEN'=>'Macao'
    			);
    			$province_re=array(
    					'ANHUI'=>'安徽',
    					'BEIJING'=>'北京',
    					'CHONGQING'=>'重庆',
    					'FUJIAN'=>'福建',
    					'GANSU'=>'甘肃 ',
    					'GUANGDONG'=>'广东',
    					'GUANGXI'=>'广西',
    					'GUIZHOU'=>'贵州',
    					'HAINAN'=>'海南',
    					'HEBEI'=>'河北',
    					'HENAN'=>'河南',
    					'HEILONGJIANG'=>'黑龙江',
    					'HUBEI'=>'湖北',
    					'HUNAN'=>'湖南',
    					'JIANGXI'=>'江西',
    					'JIANGSU'=>'江苏',
    					'JILIN'=>'吉林',
    					'LIAONING'=>'辽宁',
    					'NEIMENGGU'=>'内蒙古',
    					'NINGXIA'=>'宁夏',
    					'QINGHAI'=>'青海',
    					'SHANXI'=>'陕西',
    					'SHANXI'=>'山西',
    					'SHANDONG'=>'山东',
    					'SHANGHAI'=>'上海',
    					'SICHUAN'=>'四川',
    					'TIANJIN'=>'天津',
    					'XINJIANG'=>'新疆',
    					'XIZANG'=>'西藏',
    					'YUNNAN'=>'云南',
    					'ZHEJIANG'=>'浙江',
    					'AOMEN'=>'澳门'
    			);
    			$country['0'] = array('CN'=>'China','HK'=>'Hongkong','TW'=>'Taiwan');
    			$country['1'] = array('CN'=>'中国','HK'=>'香港','TW'=>'台湾');
    			$country['2'] = array('CN'=>'中国','HK'=>'香港','TW'=>'台湾');
    			$province['0'] = $province_def;
    			$province['1'] = $province_def;
    			$province['2'] = $province_re;
    			$province['3'] = $province_def_en;
    			$city['1'] = $city_def;
    			$district['1'] = $district_def;
    			break;
    		case 'lb_ebaytnt':
    			$province_def_en=array(
    					'ANHUI'=>'Anhui',
    					'BEIJING'=>'Beijing',
    					'CHONGQING'=>'Chongqing',
    					'FUJIAN'=>'Fujian',
    					'GANSU'=>'Gansu',
    					'GUANGDONG'=>'Guangdong',
    					'GUANGXI'=>'Guangxi',
    					'GUIZHOU'=>'Guizhou',
    					'HAINAN'=>'Hainan',
    					'HEBEI'=>'Hebei',
    					'HENAN'=>'Henan',
    					'HEILONGJIANG'=>'Heilongjiang',
    					'HUBEI'=>'Hubei',
    					'HUNAN'=>'Hunan',
    					'JIANGXI'=>'Jiangxi',
    					'JIANGSU'=>'Jiangsu',
    					'JILIN'=>'Jilin',
    					'LIAONING'=>'Liaoning',
    					'NEIMENGGU'=>'Inner',
    					'NINGXIA'=>'Ningxia',
    					'QINGHAI'=>'Qinghai',
    					'SHANXI'=>'Shaanxi',
    					'SHANXI'=>'Shanxi',
    					'SHANDONG'=>'Shandong',
    					'SHANGHAI'=>'Shanghai',
    					'SICHUAN'=>'Sichuan',
    					'TIANJIN'=>'Tianjin',
    					'XINJIANG'=>'Xinjiang',
    					'XIZANG'=>'Tibet',
    					'YUNNAN'=>'Yunnan',
    					'ZHEJIANG'=>'Zhejiang',
    					'AOMEN'=>'Macao'
    			);
    			$country['1'] = array('CN'=>'China','HK'=>'Hongkong','TW'=>'Taiwan');
    			$country['2'] = array('CN'=>'China','HK'=>'Hongkong','TW'=>'Taiwan');
    			$country['3'] = array('CN'=>'China','HK'=>'Hongkong','TW'=>'Taiwan');
    			$province['1'] = $province_def_en;
    			$province['2'] = $province_def_en;
    			$province['3'] = $province_def_en;
    			break;
    		case 'lb_BPOST' :
    			break;
    		case 'lb_FEDEX' :
    			break;
    		case 'lb_tnt' :
    			break;
    		case 'lb_TNT' :
    			break;
    		case 'lb_4px' :
    			break;
    		case 'lb_haoyuan' :
    			$more['shippingfrom'][] = ['type'=>'text','req'=>false,'label'=>'区域代码','name'=>'areacode'];
    			break;
    		case 'lb_alionlinedelivery' :
    			break;
    		case 'lb_rtbcompany' :
    			$more['shippingfrom'][] = ['type'=>'text','req'=>false,'label'=>'区域代码','name'=>'areacode'];
    			break;
    		default :
    			break;
    	}
    	if(empty($country[0])) $country[0] = Helper_Array::toHashmap(SysCountry::find()->select(['country_zh','country_code'])->asArray()->all(), 'country_code','country_zh');
    	if(empty($country[1])) $country[1] = Helper_Array::toHashmap(SysCountry::find()->select(['country_zh','country_code'])->asArray()->all(), 'country_code','country_zh');
    	if(empty($country[2])) $country[2] = Helper_Array::toHashmap(SysCountry::find()->select(['country_zh','country_code'])->asArray()->all(), 'country_code','country_zh');
    	return $data = ['country'=>$country,'province'=>$province, 'city'=>$city, 'district'=>$district,'more'=>$more];
    } 
    /**
     * 打开添加/编辑地址界面
     *
     *
     * +-------------------------------------------------------------------------------------------
     * log			name	date					note
     * @author		zwd		2015/12/10				初始化
     * +-------------------------------------------------------------------------------------------
     */
    public function actionAddress(){
    	$v = \Yii::$app->request->get();
    	
    	$address = CarrierOpenHelper::getCarrierAccountAdderssById($v['id'], 0,$v['codes']);
    	$add_List = CarrierOpenHelper::getCarrierAddressNameArrByType(1,$v['codes']);
    	
    	//特殊情况
    	$add_data = self::getSpecialAddress($v['codes']);
    	
    	return $this->renderAjax('_address',[
    			'address'=>$address,
    			'add_List'=>$add_List,
    			'add_data'=>$add_data,
    			]);
    }
    /**
     * 保存揽收/发货地址（添加/修改地址）
     *
     *
     * +-------------------------------------------------------------------------------------------
     * log			name	date					note
     * @author		zwd		2015/12/10				初始化
     * +-------------------------------------------------------------------------------------------
     */
    public function actionSaveaddress(){
	    if (Yii::$app->request->isPost){
	    	$addressParams = Array
	    	(
	    			'id' => $_POST['id'],
	    			'carrier_code' => $_POST['carrier_code'],
	    			'type' => 0,
	    			'address_name' =>$_POST['address_name'],
	    			'is_default' => isset($_POST['is_default'])?1:0,
	    			'address_params' => array(
	    					'shippingfrom' => @$_POST['shippingfrom'],
	    					'pickupaddress' => @$_POST['pickupaddress'],
	    					'returnaddress' => @$_POST['returnaddress'],
	    			),
	    			'isSaveCommonAddress' => isset($_POST['isSaveCommonAddress'])?1:0,	//是否将物流地址保存为常用地址	0:否		1:是
	    	);
	     	$res = CarrierOpenHelper::saveCarrierAddressInfo($addressParams);
	    	print_r($res['response']['msg']);
    	}
    }
    /**
     * 打开‘删除揽收/发货地址’的界面
     *
     *
     * +-------------------------------------------------------------------------------------------
     * log			name	date					note
     * @author		zwd		2015/12/10				初始化
     * +-------------------------------------------------------------------------------------------
     */
    public function actionDeladdress(){
    	$v = \Yii::$app->request->get();
    	$id = $v['id'];
    	return $this->renderPartial('_deladdress',['id'=>$id]);
    }
    /**
     * 删除揽收/发货地址
     *
     *
     * +-------------------------------------------------------------------------------------------
     * log			name	date					note
     * @author		zwd		2015/12/10				初始化
     * +-------------------------------------------------------------------------------------------
     */
    public function actionDeladdressnow(){
    	$v = \Yii::$app->request->post();
    	$res = CarrierOpenHelper::carrierAddressDelById($v['id']);
    	return $res['response']['code'].','.$res['response']['msg'];
    }
    /**
     * 设置默认揽收/发货地址
     *
     *
     * +-------------------------------------------------------------------------------------------
     * log			name	date					note
     * @author		zwd		2015/12/10				初始化
     * +-------------------------------------------------------------------------------------------
     */
    public function actionSetdefaultaddress(){
    	$v = \Yii::$app->request->post();
    	$id = $v['id'];
    	$res = CarrierOpenHelper::carriserAddressSetDefault($id);
//     	$res = CarrierOpenHelper::carriserUserAccountSetDefault($id);
    	//     	print_r($res);
    	return $res['response']['msg'];
    }
    /**
     * 获取常用地址数据页面
     *@param $id
     *
     * +-------------------------------------------------------------------------------------------
     * log			name	date					note
     * @author		zwd		2015/12/16				初始化
     * +-------------------------------------------------------------------------------------------
     */
    public function actionGetcommonaddress(){
    	$v = \Yii::$app->request->post();
    	$address = CarrierOpenHelper::getCarrierAccountAdderssById($v['id'], 1, '');
    	$country = Helper_Array::toHashmap(SysCountry::find()->select(['country_zh','country_code'])->asArray()->all(), 'country_code','country_zh');
    	
    	//特殊情况
    	$add_data = self::getSpecialAddress($v['codes']);
    	$add_data_load = self::getSpecialAddress($address['response']['data']['carrier_code']);
//     	print_r($address['response']['data']);
    	return $this->renderPartial('_Commonaddress',[
    			'address'=>$address,
    			'country'=>$country,
    			'myHas'=>$v['myHas'],
    			'add_data'=>$add_data,
    			'add_data_load'=>$add_data_load,
    			]);
    }
    /**
     * 打开运输服务详细界面
     *
     *
     * +-------------------------------------------------------------------------------------------
     * log			name	date					note
     * @author		zwd		2015/12/10				初始化
     * +-------------------------------------------------------------------------------------------
     */
    public function actionShippingservice(){
    	$key = 'none';
    	if(isset($_GET['key'])){
    		$key = $_GET['key'];
    	}

//     	//开启渠道时用户表没有的
//     	if($_GET['type']=='open' && empty($_GET['id']) && $key=='api'){
//     		$id=CarrierOpenHelper::openShippingServer($_GET['code'],$_GET['shipcode']);
//     		if(!empty($id))
//     			$_GET['id']=$id[0]['id'];
//     		else 
//     			return '该运输服务没有找到对接的账号';
//     	}

    	$shipcode='';
    	if(isset($_GET['shipcode']))
    		$shipcode=$_GET['shipcode'];
    	
    	if($key == 'custom'){
    		$serviceUserById = CarrierOpenHelper::getCustomCarrierShippingServiceUserById($_GET['id'],$_GET['code']);
    	}else{
    		$serviceUserById = CarrierOpenHelper::getCarrierShippingServiceUserById($_GET['id'],$_GET['code'],-1,$shipcode,'',1);
    	}
//     	print_r($serviceUserById['response']['data']['carrierParams']);die;
    	$param_set_count=0;
    	if(isset($serviceUserById['response']['data']['carrierParams'])){
	    	foreach ($serviceUserById['response']['data']['carrierParams'] as $carrierParams){
	    		if($carrierParams["carrier_param_key"]=="edisAddressoinfo")
	    			continue;
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
    	
    	if(isset($serviceUserById['response']['data']['print_params']))
    		$carrier_template_highcopy=CarrierOpenHelper::getCarrierTemplateHighcopy($serviceUserById['response']['data']['print_params']);
    	else
    		$carrier_template_highcopy=array(				
    				'carrier_lable'=>array(),
					'declare_lable'=>array(),
					'items_lable'=>array(),
					'printFormat'=>'0',
					'printAddVal'=>array(),);
    	
    	//速卖通地址信息 S
    	$aliexpressAddressInfo = array();
    	
    	if($_GET['code'] == 'lb_alionlinedelivery'){
	    	$user=\Yii::$app->user->identity;
	    	$puid = $user->getParentUid();
	    	
	    	$aliexpressUsers = SaasAliexpressUser::find()->where(['uid'=>$puid])
		    	->orderBy('refresh_token_timeout desc')
		    	->asArray()
		    	->all();
	    	
		    if(count($aliexpressUsers) > 0){
		    	foreach ($aliexpressUsers as $aliexpressUserVal){
		    		if(!empty($aliexpressUserVal['address_info'])){
		    			$aliexpressAddressInfo[$aliexpressUserVal['sellerloginid']] = array('sellerloginid'=>$aliexpressUserVal['sellerloginid'].'【'.$aliexpressUserVal['store_name'].'】', 
		    					'address_info'=>array('sender'=>array(),'pickup'=>array(),'refund'=>array()));
		    			
		    			$tmpAliexpressUserVal = json_decode($aliexpressUserVal['address_info'], true);
		    			
		    			if(isset($tmpAliexpressUserVal['sender'])){
		    				foreach ($tmpAliexpressUserVal['sender'] as $tmpKey => $tmpVal){
		    					$aliexpressAddressInfo[$aliexpressUserVal['sellerloginid']]['address_info']['sender'][$tmpKey] = $tmpVal['name'].' '.$tmpVal['province'].' '.$tmpVal['city'].' '.$tmpVal['county'];
		    					
		    					if(empty($serviceUserById['response']['data']['address']['aliexpressAddress']['sender'])){
		    						if($tmpVal['isDefault'] == 1){
		    							$serviceUserById['response']['data']['address']['aliexpressAddress']['sender'] = $tmpVal['addressId'];
		    						}
		    					}
		    				}
		    			}
		    			
		    			if(isset($tmpAliexpressUserVal['pickup'])){
		    				foreach ($tmpAliexpressUserVal['pickup'] as $tmpKey => $tmpVal){
		    					$aliexpressAddressInfo[$aliexpressUserVal['sellerloginid']]['address_info']['pickup'][$tmpKey] = $tmpVal['name'].' '.$tmpVal['province'].' '.$tmpVal['city'].' '.$tmpVal['county'];
		    					
		    					if(empty($serviceUserById['response']['data']['address']['aliexpressAddress']['pickup'])){
		    						if($tmpVal['isDefault'] == 1){
		    							$serviceUserById['response']['data']['address']['aliexpressAddress']['pickup'] = $tmpVal['addressId'];
		    						}
		    					}
		    				}
		    			}
		    			
		    			if(isset($tmpAliexpressUserVal['refund'])){
		    				foreach ($tmpAliexpressUserVal['refund'] as $tmpKey => $tmpVal){
		    					$aliexpressAddressInfo[$aliexpressUserVal['sellerloginid']]['address_info']['refund'][$tmpKey] = $tmpVal['name'].' '.$tmpVal['province'].' '.$tmpVal['city'].' '.$tmpVal['county'];
		    					
		    					if(empty($serviceUserById['response']['data']['address']['aliexpressAddress']['refund'])){
		    						if($tmpVal['isDefault'] == 1){
		    							$serviceUserById['response']['data']['address']['aliexpressAddress']['refund'] = $tmpVal['addressId'];
		    						}
		    					}
		    				}
		    			}
		    		}
		    	}
	    	}
    	}
    	//速卖通地址信息 E

    	//edis地址信息
    	$result_view=""; 
    	if($_GET['code'] == 'lb_edis'){
	    	if(isset($serviceUserById['response']['data']["carrierParams"])){ 
	    		$edisAddressoinfo=$serviceUserById['response']['data']["carrierParams"][0]["param_value"];   
	    		if(!empty($edisAddressoinfo) && is_array($edisAddressoinfo)){	
	    			$result=json_decode($edisAddressoinfo["alledislist"],true);
	    			$result_view = \eagle\modules\carrier\helpers\CarrierOpenHelper::getEdisAddressHtml($result,$edisAddressoinfo["edisAddress"],$edisAddressoinfo["edisConsign"]);
	    		}
	    	}
    	}
    	
    	
    	return $this->renderPartial('_shippingservice',[
    				'carrier_code'=>$_GET['code'],
    				'type'=>$_GET['type'],
    				'serviceUserById'=>$serviceUserById['response']['data'],
    				'key'=>$key,
    				'account'=>$account,
    				'param_set_count'=>$param_set_count,
    				'carrier_template_highcopy'=>$carrier_template_highcopy,
    				'aliexpressAddressInfo'=>$aliexpressAddressInfo,
    				'edisAddressInfo'=>$result_view,
    			]);
    }
    /**
     * 搜索显示运输服务
     *
     *
     * +-------------------------------------------------------------------------------------------
     * log			name	date					note
     * @author		zwd		2015/12/22				初始化
     * +-------------------------------------------------------------------------------------------
     */
    public function actionFindshipping(){
    	$res = CarrierOpenHelper::getCarrierShippingServiceUserByCarrierCode($_POST['carrier_code'],$_POST['service_name']);
    	//获取匹配规则
    	$serviceUser = CarrierOpenHelper::getCarrierShippingServiceUserByCarrierCode($_POST['carrier_code'],$_POST['service_name']);
    	return $this->renderPartial('_reloadshippingservice',[
    			'Shipping'=>$res,
    			'serviceUser'=>$serviceUser,
    			'carrier_code'=>$_POST['carrier_code'],
    			'type'=>$_POST['type'],
    			]);
    }
    
    /**
     * 开启/关闭运输服务
     *
     * +-------------------------------------------------------------------------------------------
     * log			name	date					note
     * @author		zwd		2015/12/21				初始化
     * +-------------------------------------------------------------------------------------------
     */
    public function actionOpenorcloseshipping(){
    	if(isset($_POST['shippings'])){
    			$shippings = explode(',',$_POST['shippings']);
    			Helper_Array::removeEmpty($shippings);
    			if (count($shippings)>0){
    				try {
    					foreach ($shippings as $shipping_id){
    						$res = CarrierOpenHelper::carrierShippingServiceOnOff($shipping_id, 0);
    					}
    					return '操作已完成';
    				}catch (\Exception $e){
    					return $e->getMessage();
    				}
    			}else{
    				return '选择的运输服务有问题';
    			}
    	}else{
    		$res = CarrierOpenHelper::carrierShippingServiceOnOff($_POST['id'],$_POST['is_used']);
    		return $res['response']['code'].','.$res['response']['msg'];
    	}
    }
    
    /**
     * 打开运输服务批量修改(地址、关闭、仓库)的界面
     *
     *
     * +-------------------------------------------------------------------------------------------
     * log			name	date					note
     * @author		zwd		2015/12/22				初始化
     * +-------------------------------------------------------------------------------------------
     */
    public function actionBatchchangeshipping(){
    	if(empty($_POST['selectShip']))
    		return 1;
    	else{
    		if($_GET['reurl'] == "_batchchangeaddress"){
		    	$addressNames = CarrierOpenHelper::getCarrierAddressNameArrByType(0,$_POST['carrier_code']);
		    	return $this->renderPartial($_GET['reurl'],[
		    			'addressNames'=>$addressNames['response']['data'],
		    			'selectShip'=>$_POST['selectShip'],
		    			'carrier_code'=>$_POST['carrier_code'],
		    			]);
    		}
    		else if($_GET['reurl'] == "_batchcloseship"){
    			return $this->renderPartial($_GET['reurl'],[
    					'selectShip'=>$_POST['selectShip'],
    					]);
    		}
    		else if($_GET['reurl'] == "_batchchangewarehouse"){
		    	$addressNames = CarrierOpenHelper::getCarrierAddressNameArrByType(0,$_POST['carrier_code']);
		    	$warehouses = WarehouseHelper::getOpenWarehouseList();
		    	return $this->renderPartial($_GET['reurl'],[
		    			'warehouses'=>$warehouses,
		    			'selectShip'=>$_POST['selectShip'],
		    			'carrier_code'=>$_POST['carrier_code'],
		    			]);
    		}
    	}
    }
    /**
     * 运输服务批量修改地址
     *
     *
     * +-------------------------------------------------------------------------------------------
     * log			name	date					note
     * @author		zwd		2015/12/22				初始化
     * +-------------------------------------------------------------------------------------------
     */
    public function actionBatchchangeaddressnow(){
    	if(empty($_POST['addressNames']))
    		return 1;
    	else{
    		$res = CarrierOpenHelper::saveShippingServiceCommonAddress($_POST['addressNames'],$_POST['selectShip'],$_POST['carrier_code']);
    		return '0'.$res['response']['code'].','.$res['response']['msg'];
    	}
    }
    /**
     * 批量关闭运输服务
     *
     *
     * +-------------------------------------------------------------------------------------------
     * log			name	date					note
     * @author		zwd		2015/12/22				初始化
     * +-------------------------------------------------------------------------------------------
     */
    public function actionBatchcloseshippingnow(){
//     	print_r($_POST);exit;
    	$res = array();
    	foreach($_POST['selectShip'] as $k=>$v){
    		$res[$k] = CarrierOpenHelper::carrierShippingServiceOnOff($v);
    	}
    	$msg = array();
    	$success = 0;
    	$err = 0;
    	$err_Msg = array();
    	foreach($res as $k=>$v){
    		if($v['response']['code'] == 0){
    			$msg['修改成功'][] = $k;
    			$success++;
    		}
    		else if($v['response']['code'] == 1){
    			$msg['修改失败'][] = $k;
    			$err_Msg[$k] = $v['response']['msg'];
    			$err++;
    		}
    	}
    	$msg['成功条数'] = $success;
    	$msg['失败条数'] = $err;
    	if(!empty($err)){
    		$msg['失败原因:'] = $err_Msg;
    	}
    	print_r($msg);
    }
    /**
     * 批量修改运输服务的仓库
     *
     *
     * +-------------------------------------------------------------------------------------------
     * log			name	date					note
     * @author		zwd		2015/12/22				初始化
     * +-------------------------------------------------------------------------------------------
     */
    public function actionBatchchangewarehousenow(){
    	if(empty($_POST['mywarehouse']))
    		return 1;
    	else{
    		$res = CarrierOpenHelper::saveShippingServiceProprietaryWarehouse($_POST['warehouse'],$_POST['selectShip'],$_POST['carrier_code']);
    		return '0'.$res['response']['code'].','.$res['response']['msg'];
    	}
    }
    /**
     * 保存运输服务
     *
     *
     * +-------------------------------------------------------------------------------------------
     * log			name	date					note
     * @author		zwd		2015/12/21				初始化
     * +-------------------------------------------------------------------------------------------
     */
    public function actionSaveshippingservice(){
    	$id = $_POST['serviceID'];
    	$carrier_code = $_POST['carrier_code'];
    	$type = $_POST['type'];
    	$params = $_POST['params'];
    	$add=empty($_POST['add'])?'':$_POST['add'];
    	
    	if(!isset($params['print_type']) || $params['print_type']=='')
    		return exit(json_encode(array('code'=>1, 'msg'=>'请选择打印方式', 'data'=>'')));
    	
    	if($_REQUEST['key']!= 'custom'){
    		$result=CarrierOpenHelper::CheckShipping($carrier_code,$params['accountID'],$_POST['params']['shipping_method_code']);
    		if($carrier_code != 'lb_yanwen'){
    			if(is_array($result) && isset($result['error']) && $result['error'])
    				return exit(json_encode(array('code'=>1, 'msg'=>$result['msg'], 'data'=>'')));
    		}
    		
    		if($result===0)
    			return exit(json_encode(array('code'=>1, 'msg'=>'该物流账号已关闭或者因为物流的限制，该物流账号不能对接该运输服务', 'data'=>'')));
    		 
    		if($params['print_type']==1 && (!isset($params['print_params']['label_littleboss']) || !in_array('label_address',$params['print_params']['label_littleboss'])))
    			return exit(json_encode(array('code'=>1, 'msg'=>'高仿标签必须选择地址单', 'data'=>'')));
    	}
    	if($params['print_type']==2 && (!isset($params['print_params']['label_custom']) || empty($params['print_params']['label_custom']['carrier_lable'])))
    		return exit(json_encode(array('code'=>1, 'msg'=>'自定义标签必须选择物流面单', 'data'=>'')));
    	
    	if($params['print_type']==3 && (!isset($params['print_params']['label_littlebossOptionsArrNew']['carrier_lable']) || empty($params['print_params']['label_littlebossOptionsArrNew']['carrier_lable']) ))
    		return exit(json_encode(array('code'=>1, 'msg'=>'高仿标签必须选择地址单(面单)', 'data'=>'')));


    	if($type=='open' && $_REQUEST['key']=='api' && empty($id)){
    		$idtmp=CarrierOpenHelper::openShippingServer($carrier_code,$_POST['params']['shipping_method_code'],'',$params,$result);
    		if(!empty($idtmp))
    			$id=$idtmp[0]['id'];
    	}

    	if(!isset($_REQUEST['key']) || ($_REQUEST['key'] != 'custom')){
	    	if($type != 'edit' && (!isset($params['accountID']) || empty($params['accountID']))){
	    		return exit(json_encode(array('code'=>1,'msg'=>'物流账号为必填项！')));
	    	}
    	}
    	
    	//客户选择的加打内容json格式[{AddOrder:on;AddSku:on;addCustomsCn:on}]
    	if(empty($add))
    		$params['print_params']['label_littlebossOptionsArrNew']['printAddVal']='';
    	else{
    		$json=json_encode($add);
    		$params['print_params']['label_littlebossOptionsArrNew']['printAddVal']=$json;
    	}
    	
//     	//判断是否有设置最大报关金额
//     	if(isset($params['carrierParams']['max_declared_value'])){
//     		if(($params['carrierParams']['max_declared_value']) != ''){
//     			if(!is_numeric($params['carrierParams']['max_declared_value'])){
//     				return exit(json_encode(array('code'=>1,'msg'=>'最大报关金额必须为数字')));
//     			}
    			
//     			if($params['carrierParams']['max_declared_value'] <= 0){
//     				return exit(json_encode(array('code'=>1,'msg'=>'最大报关金额必须大于0')));
//     			}
//     		}
//     	}

    	$res = CarrierOpenHelper::saveCarrierShippingServiceUserById($id, $carrier_code, $type, $params);
// 	    return $res['response']['code'].','.$res['response']['msg'][0];
	    return exit(json_encode(array('code'=>$res['response']['code'], 'msg'=>$res['response']['msg'][0], 'data'=>$res['response']['data'])));
    }
    /**
     * 更新平台认可方式
     *
     *
     * +-------------------------------------------------------------------------------------------
     * log			name	date					note
     * @author		zwd		2015/12/26				初始化
     * +-------------------------------------------------------------------------------------------
     */
    public function actionUpdateplatform(){
    	$res = CarrierOpenHelper::updateSysShippingCodeNameMap($_POST['platform']);
    	if($res['response']['code'] == 0){
    		$options = "";
    		foreach ($res['response']['data'] as $k=>$opt){
    			$options .= '<option value="'.$k.'">'.$opt.'</option>';
    		}
    		return $res['response']['code'].$options;
    	}
		else{
			return $res['response']['code'].','.$res['response']['msg'];
		}
    }
    /**
     * 更新运输服务
     *
     *
     * +-------------------------------------------------------------------------------------------
     * log			name	date					note
     * @author		zwd		2015/12/21				初始化
     * +-------------------------------------------------------------------------------------------
     */
    public function actionUpdateshippingservice(){
    	$carrier_code = $_POST['carrier_code'];
    	$res = CarrierOpenHelper::refreshCarrierShippingMethod($carrier_code);
    	return $res['response']['code'].','.$res['response']['msg'];
    }
    /**
     * 删除运输服务
     *
     *
     * +-------------------------------------------------------------------------------------------
     * log			name	date					note
     * @author		zwd		2015/12/21				初始化
     * +-------------------------------------------------------------------------------------------
     */
    public function actionDelshippingservice(){
    	$res = CarrierOpenHelper::delShippingServiceByID($_POST['id']);
    	return $res['response']['code'].','.$res['response']['msg'];
    }
    
    /**
     * 保存物流账号信息
     *
     * +-------------------------------------------------------------------------------------------
     * log			name	date					note
     * @author		hqw		2015/12/10				初始化
     * +-------------------------------------------------------------------------------------------
     */
    public function actionSaveaccount(){
    	if(!isset($_POST['is_default'])) $_POST['is_default'] = 0;
    	
    	$tmpParams = $_POST;
    	if(!isset($tmpParams['id']))
    		$tmpParams['id'] = 0;
    	
    	//过滤前后空格
    	if(isset($tmpParams['carrier_params'])){
    		foreach ($tmpParams['carrier_params'] as $tmp_carrier_paramsKey => $tmp_carrier_paramsVal){
    			$tmpParams['carrier_params'][$tmp_carrier_paramsKey] = trim($tmp_carrier_paramsVal);
    		}
    	}
    	
    	$is_continue = false;

    	if(isset($tmpParams['notCarrierDropDownid']) && !isset($tmpParams['carrier_code'])){
    		$tmpParams['carrier_code'] = $tmpParams['notCarrierDropDownid'];
    		unset($tmpParams['notCarrierDropDownid']);
    		
    		$is_continue = true;
//     		$res = CarrierOpenHelper::carrierOpenOrCloseRecord($tmpParams['carrier_code'], 1);
    	}

    	$res = CarrierOpenHelper::carrierUserAccountAddOrEdit($tmpParams['id'],$tmpParams);
    	
    	if(($is_continue == true) && ($res['response']['code'] == 0)){
    		$res_record = CarrierOpenHelper::carrierOpenOrCloseRecord($tmpParams['carrier_code'], 1);
    	}
	
//     	return $res['response']['code'].','.$res['response']['msg'];
    	$result = array();
    	$result['success'] = ($res['response']['code'] == 0) ? true : false;
    	$result['message'] = $res['response']['msg'];
    	
    	exit(json_encode($result));
    }
    
    /**
     * 获取运输服务分配规则界面
     *
     * 物品所在城市不要显示出来，然后把物品所在州/省份改为：物品所在地区(2016/3/5)
     * +-------------------------------------------------------------------------------------------
     * log			name	date					note
     * @author		zwd		2015/12/16				初始化
     * +-------------------------------------------------------------------------------------------
     */
    public function actionShippingrules(){
    	$transportation_service_id = $_GET['sid'];
    	$proprietary_warehouse_id = isset($_GET['warehouse_id']) ? $_GET['warehouse_id'] : 0;
    	
    	$id = $_GET['id'];
    	//获取对应$id的分配规则
    	$rule = MatchingRule::find()->where(['id'=>$id])->one();
    	if ($rule === null){
    		$rule = new MatchingRule();
    	}else{
    		$transportation_service_id = $rule->transportation_service_id;
    		$proprietary_warehouse_id = $rule->proprietary_warehouse_id;
    	}
    	
//     	//收件国家
//     	$query = SysCountry::find();
//     	$regions = $query->orderBy('region')->groupBy('region')->select('region')->asArray()->all();
//     	$countrys =[];
//     	foreach ($regions as $region){
//     		$arr['name']= $region['region'];
//     		$arr['value']=Helper_Array::toHashmap(SysCountry::find()->where(['region'=>$region['region']])->orderBy('country_en')->select(['country_code', "CONCAT( country_zh ,'(', country_en ,')' ) as country_name "])->asArray()->all(),'country_code','country_name');
//     		$countrys[]= $arr;
//     	}
//     	//国家中文名
//     	$region = WarehouseHelper::countryRegionChName();
    	$region = array();
    	
    	//国家列表信息
    	$countrys = \eagle\modules\util\helpers\CountryHelper::getScopeCountry();
    	
    	//已绑定平台
    	$source = MatchingRule::$source;
    	try {
        	$platformAccountInfo = PlatformAccountApi::getAllPlatformOrderSelleruseridLabelMap();
        	foreach ($platformAccountInfo as $p_key=>$p_v){
        		if(empty($p_v)){
        		    //清除未绑定的平台
        		    unset($source[$p_key]);
        		}
        	}
    	}
    	catch(\Exception $e){}
    	//站点
    	$sites = PlatformAccountApi::getAllPlatformOrderSite();
    	//应为cdiscount、priceminister只有一个站点，所以不需显示出来
    	unset($sites['cdiscount']);
    	unset($sites['priceminister']);
    	//账号
    	$selleruserids=PlatformAccountApi::getAllPlatformOrderSelleruseridLabelMap();
    	//买家选择物流物流
    	$buyer_transportation_services = PlatformAccountApi::getAllPlatformShippingServices();
    	//商品标签
    	$product_tags = ProductApiHelper::getAllTags();
    	
    	//获取仓库Map
    	$warehouseIdNameMap = InventoryHelper::getWarehouseOrOverseaIdNameMap(1, -1);
    	
//     	$shippingMethods = CarrierOpenHelper::getSysShippingMethodList('',4,false, -1)['response'];
//     	$shippingMethods = isset($shippingMethods['data'])?$shippingMethods['data']:[];
    	$shippingMethods = CarrierOpenHelper::getShippingServiceIdNameMapByWarehouseId($proprietary_warehouse_id);

    	//币种类型
    	$currency_type = array(
    		'GBP'=>'英磅',
    		'IDR'=>'印尼卢比',
    		'RUB'=>'俄罗斯卢布',
			'AUD'=>'澳元',
			'THB'=>'泰铢',
			'MXN'=>'墨西哥元',
			'CNY'=>'人民币',
			'PHP'=>'菲律宾比索',
			'MYR'=>'马来西亚林吉特',
			'HKD'=>'港元',
			'JPY'=>'日元',
			'SGD'=>'新加坡元',
			'USD'=>'美元',
			'EUR'=>'欧元',
			'CAD'=>'加元'
    	);
    	
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
    				'transportation_service_id'=>$transportation_service_id,
    				'warehouseIdNameMap'=>$warehouseIdNameMap,
    				'shippingMethods'=>$shippingMethods,
    				'proprietary_warehouse_id'=>$proprietary_warehouse_id,
    				'currency_type'=>$currency_type
    			]);
    }
    /**
     * 保存运输服务分配规则
     *
     * 物品所在城市不要显示出来，然后把物品所在州/省份改为：物品所在地区(2016/3/5)
     * +-------------------------------------------------------------------------------------------
     * log			name	date					note
     * @author		zwd		2015/12/18				初始化
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
     * 获取API物流的界面
     */
    public function actionGetSysCarrierInfo(){
    	if(empty($_POST['carrier_code'])){
    		return false;
    	}

    	$relatedparams = array();

    	$tmpCarrierCode = $_POST['carrier_code'];

    	if($tmpCarrierCode == -1){
    		$notOpenCarrier = CarrierOpenHelper::getNotOpenCarrierArr(0, 0, 1, true);
    		
    		$relatedparams['notOpenCarrier'] = $notOpenCarrier;
    	}else{
    		$opens_a = CarrierOpenHelper::getHasOpenCarrier($tmpCarrierCode);
    		    		
    		if(isset($opens_a[0]) && !empty($opens_a[0])){
    			$data['carrier_code'] = $opens_a[0]['carrier_code'];
    			$data['is_active'] = $opens_a[0]['is_active'];
    			$data['is_show_address'] = $opens_a[0]['is_show_address'];
    			$data['carrier_name'] = $opens_a[0]['carrier_name'];
    			
    			$relatedparams['carrier_data'] = $data;
    		}
    		
    		$account = CarrierOpenHelper::getBindingCarrierAccount($tmpCarrierCode);
    		$relatedparams['account'] = $account;
    		
    		//获取对应物流代码的揽收地址或发货地址
    		$address = CarrierOpenHelper::getCarrierAccountAdderssByCarrierCode($tmpCarrierCode);
    		$relatedparams['address'] = $address;
    		
    		//获取物流商已经接入的物流运输服务
    		$ShippingMethodList = CarrierOpenHelper::getSysShippingMethodList($tmpCarrierCode);
    		$relatedparams['ShippingMethodList'] = $ShippingMethodList['response']['data'];

    		//获取对应user所使用的运输服务
    		$Shipping = CarrierOpenHelper::getCarrierShippingServiceUserByCarrierCode($tmpCarrierCode);
    		$relatedparams['Shipping'] = $Shipping;
    		
    		//获取匹配规则
    		$serviceUser = CarrierOpenHelper::getCarrierShippingServiceUserByCarrierCode($tmpCarrierCode);
    		$relatedparams['serviceUser'] = $serviceUser;
    	}
    	
    	return $this->renderPartial('reloadSysCarrier',['carrier_code'=>$tmpCarrierCode,'relatedparams'=>$relatedparams]);
    }
    
    /**
     * 获取自定义物流界面
     * @return boolean|string
     */
    public function actionGetSelfCarrierInfo(){
    	if(empty($_REQUEST['carrier_code'])){
    		return false;
    	}
 
    	$relatedparams = array();
    	
    	$tmpCarrierCode = $_REQUEST['carrier_code'];
    	$tmpCarrierType = isset($_POST['carrier_type']) ? $_POST['carrier_type'] : 0;

    	if($tmpCarrierCode != -1){
    		$customCarrier = CarrierOpenHelper::getHasOpenCustomCarrier($tmpCarrierCode)[0];
    		
    		//获取物流商已经接入的物流运输服务
    		$shippingMethodList = CarrierOpenHelper::getSysShippingMethodList($tmpCarrierCode);
    		if(isset($shippingMethodList['response']['data']) && !empty($shippingMethodList['response']['data'])){
    			$shippingMethodList = $shippingMethodList['response']['data'];
    		}else{
    			$shippingMethodList = array();
    		}
    		
    		//运输服务信息
    		$shippingMethodInfo = CarrierOpenHelper::getCarrierShippingServiceUserByCarrierCode($tmpCarrierCode);
    		
    		//获取匹配规则
    		$serviceUser = CarrierOpenHelper::getCarrierShippingServiceUserByCarrierCode($tmpCarrierCode);
    		
    		
    		if($tmpCarrierType == 0){
	    		$params['carrier_code'] = $tmpCarrierCode;
	    		
	    		$trackingNoList = CarrierOpenHelper::getCarrierTrackingNumberList((empty($_REQUEST['per-page']) ? 10 : $_REQUEST['per-page']), $params);
	    		
	    		$relatedparams['trackingNoList'] = $trackingNoList;
    		}
    		
    		$relatedparams['customCarrier'] = $customCarrier;
    		$relatedparams['shippingNameIdMap'] = $shippingMethodList;
    		$relatedparams['shippingMethodInfo'] = $shippingMethodInfo;
    		$relatedparams['serviceUser'] = $serviceUser;
    	}
    	
    	return $this->renderAjax('reloadSelfCarrier',['carrier_code'=>$tmpCarrierCode,'carrier_type'=>$tmpCarrierType,'relatedparams'=>$relatedparams]);
    }
    
    /**
     * 根据物流code获取自定义物流界面的运输服务列表
     * @return string
     */
    public function actionGetCustomShippingMethodInfo(){
//     	print_r($_POST);
//     	exit;
    	
    	$reusltHtml = '';
    	
    	if(empty($_REQUEST['carrier_code'])) return $reusltHtml;
    	
    	$tmpCarrierCode = $_REQUEST['carrier_code'];
    	$carrier_type = $_REQUEST['oversea_type'];
    	
    	//运输服务信息
    	$tmpParrier['shipping_id'] = $_POST['server_id'];
    	$tmpParrier['not_used'] = true;
    	$tmpParrier['warehouse_carrier_code'] = $tmpCarrierCode;
    	$shippingMethodInfo = CarrierOpenHelper::getShippingMethodNameInfo($tmpParrier, -1);
    	$shippingMethodInfo = $shippingMethodInfo['data'];
    	
    	//获取匹配规则
    	$serviceUser = array();
    	$tmpserviceUser = CarrierOpenHelper::getCarrierShippingServiceUserByCarrierCode($tmpCarrierCode);
    	
    	foreach ($tmpserviceUser as $tmpserviceVal){
    		$serviceUser[$tmpserviceVal['id']] = $tmpserviceVal;
    	}
    	
    	foreach ($shippingMethodInfo as $k=>$ship){
    		$reusltHtml .= '<tr data="'.$ship['id'].'" >'.
      			'<td style="width: 50px;text-align:center;">'.Html::checkbox('check_all'.$carrier_type,false,['class'=>'selectShip'.$carrier_type,'value'=>$ship['id']]).'</td>'.
      			'<td>'.$ship['service_name'].'('.$ship['shipping_method_code'].')'.'</td>'.
      			'<td>'.($ship['is_used'] == 1 ? '开启' : '关闭').'</td>'.
      			'<td>'.($ship['is_used'] == 1 ? Html::dropDownList('setrules','',['-1'=>'分配规则','0'=>'添加分配规则']+(empty($serviceUser[$k]['rule']) ? array() : $serviceUser[$k]['rule']),
					['onchange'=>'selectRuless(this,'.$ship['id'].')','class'=>'iv-input']) : '').'</td>'.
				'<td>'.(($ship['is_used'] == 1) ? 
						"<a class='iv-btn' onclick='openOrCloseShipping(this,\"close\",\"custom\")' >关闭</a> ".
						"<a class='iv-btn' onclick='$.openModal(\"/configuration/carrierconfig/shippingservice\",{type:\"edit\",id:".$ship['id'].",code:\"".$tmpCarrierCode."\",key:\"custom\"},\"编辑\",\"get\")'>编辑</a>" :
						"<a class='iv-btn' onclick='$.openModal(\"/configuration/carrierconfig/shippingservice\",{type:\"open\",id:".$ship['id'].",code:\"".$tmpCarrierCode."\",key:\"custom\"},\"开启\",\"get\")'>开启</a>" )
				.'</td>'.
				'</tr>';
		}
    	
    	return $reusltHtml;
    }
    
    /**
     * 跟踪号批量标记已分配
     */
    public function actionBatchMarkTrackNumber(){
    	if (\Yii::$app->request->isPost){
    		$tracknos = explode(',',$_POST['tracknos']);
    		
    		$distribution = false;
    		if(isset($_POST['remove'])){
    			if($_POST['remove'] == 1)
    			$distribution = true;
    		}
    		
    		Helper_Array::removeEmpty($tracknos);
    		if (count($tracknos)>0){
    			try {
    				foreach ($tracknos as $trackno){
    					$res = CarrierOpenHelper::tagDistributionCustomCarrierTrackingnumber($trackno, $distribution);
    				}
    				return '操作已完成';
    			}catch (\Exception $e){
    				return $e->getMessage();
    			}
    		}else{
    			return '选择的跟踪号有问题';
    		}
    	}
    }
    
    /**
     * 批量删除跟踪号
     */
    public function actionBatchDelTracksno(){
    	if (\Yii::$app->request->isPost){
    		$tracknos = explode(',',$_POST['tracknos']);
    		Helper_Array::removeEmpty($tracknos);
    		if (count($tracknos)>0){
    			try {
    				foreach ($tracknos as $trackno){
    					$res = CarrierOpenHelper::delCustomCarrierTrackingnumber($trackno);
    				}
    				return '操作已完成';
    			}catch (\Exception $e){
    				return $e->getMessage();
    			}
    		}else{
    			return '选择的跟踪号有问题';
    		}
    	}
    }
    
    /**
     * 批量修改运输方式的物流账号界面
     */
    public function actionBatchShippingCarrieraccountList(){
    	if(empty($_POST['carrier_code']) || empty($_POST['edit_type']))
    		return false;
    	
    	$carrier_code = $_POST['carrier_code'];
    	$edit_type = $_POST['edit_type'];
    	
    	$relatedparams = array();
    	
    	if($edit_type == 'shipping_account'){
	    	//获取$carrier_code已绑定的账号列表
	    	$bindingCarrierAccounts = CarrierOpenHelper::getBindingCarrierAccount($carrier_code);
	    	$bindingCarrierAccounts = Helper_Array::toHashmap($bindingCarrierAccounts, 'id', 'carrier_name');
    	
	    	$relatedparams['bindingCarrierAccounts'] = $bindingCarrierAccounts;
    	}else if($edit_type == 'shipping_address'){
    		$commonAddressArr = CarrierOpenHelper::getCarrierAddressNameArrByType(0, $carrier_code)['response']['data']['list'];
    		
    		$relatedparams['commonAddressArr'] = $commonAddressArr;
    	}
    	
    	
    	
    	return $this->renderPartial('batchEditCarrierList',['carrier_code'=>$carrier_code,'edit_type'=>$edit_type,'relatedparams'=>$relatedparams]);
    }
    
    /**
     * 批量修改运输方式的物流账号保存
     */
    public function actionSaveBatchEditCarrierinfo(){
    	if (\Yii::$app->request->isPost){
    		$shippings = explode(',',$_POST['shippings']);
    		$common_id = $_POST['common_id'];
    		Helper_Array::removeEmpty($shippings);
    		if (count($shippings)>0){
    			try {
    				foreach ($shippings as $shipping){
    					$res = CarrierOpenHelper::editCarrierAccountShipping($shipping, $common_id, $_POST['edit_type']);
    				}
    				return '操作已完成';
    			}catch (\Exception $e){
    				return $e->getMessage();
    			}
    		}else{
    			return '选择的运输服务有问题';
    		}
    	}
    }

    public function actionGetSysCarrierInfoNew(){
    	if(empty($_POST['carrier_code'])){
    		return false;
    	}
    	 
    	$relatedparams = array();
    
    	$tmpCarrierCode = $_POST['carrier_code'];
    
    	if($tmpCarrierCode == -1){
    		$notOpenCarrier = CarrierOpenHelper::getNotOpenCarrierArr(0, 0, 1, true);

    		$relatedparams['notOpenCarrier'] = $notOpenCarrier;
    	}else{
    		$opens_a = CarrierOpenHelper::getHasCarrier($tmpCarrierCode);
 
    		if(isset($opens_a[0]) && !empty($opens_a[0])){
    			$data['carrier_code'] = $opens_a[0]['carrier_code'];
    			$data['is_active'] = $opens_a[0]['is_active'];    //crm物流是否开启
    			$data['is_show_address'] = $opens_a[0]['is_show_address'];
    			$data['carrier_name'] = $opens_a[0]['carrier_name'];
    			$data['is_user_active'] = $opens_a[0]['is_useractive'];    //用户表物流是否开启
    			 
    			$relatedparams['carrier_data'] = $data;
    		}

    		$account = CarrierOpenHelper::getBindingCarrierAccount($tmpCarrierCode);
    		$relatedparams['account'] = $account;
    		
    		foreach ($account as $key=>$accountone){
    			if($accountone['is_used']==0)
    				unset($account[$key]);
    		}
    		$relatedparams['account_isused'] = $account;

    		//获取对应物流代码的揽收地址或发货地址
    		$address = CarrierOpenHelper::getCarrierAccountAdderssByCarrierCode($tmpCarrierCode);
    		$relatedparams['address'] = $address;
    
    		//获取物流商已经接入的物流运输服务
    		$ShippingMethodList = CarrierOpenHelper::getSysShippingMethodList($tmpCarrierCode);
    		$relatedparams['ShippingMethodList'] = $ShippingMethodList['response']['data'];
    
    		//获取对应user所使用的运输服务
    		$Shipping = CarrierOpenHelper::getCarrierShippingServiceUserByCarrierCodeNew($tmpCarrierCode);
    		$relatedparams['Shipping'] = $Shipping;
    
    		//获取匹配规则
    		$serviceUser = CarrierOpenHelper::getCarrierShippingServiceUserByCarrierCode($tmpCarrierCode);
    		$relatedparams['serviceUser'] = $serviceUser;
    	}
    	 
    	return $this->renderPartial('reloadSysCarrier',['carrier_code'=>$tmpCarrierCode,'relatedparams'=>$relatedparams]);
    }
    
    /**
     * 获取常用报关信息
     * +-------------------------------------------------------------------------------------------
     * log			name	date					note
     * @author		lgw		2016/08/12				初始化
     * +-------------------------------------------------------------------------------------------
     */
    public function actionCommonDeclaredInfo(){
    	$commonDeclaredInfo = CommonDeclaredInfo::find()->asArray()->all();

    	return $this->render('CommonDeclaredInfo',[
    			'commonDeclaredInfo'=>$commonDeclaredInfo,
    			]);
    }

    /**
     * 新建修改常用报关信息
     * @param $type 类型 1：新增，2：修改
     * +-------------------------------------------------------------------------------------------
     * log			name	date					note
     * @author		lgw		2016/08/12				初始化
     * +-------------------------------------------------------------------------------------------
     */
    public function actionCreateoreditDeclare(){
    	$v = \Yii::$app->request->post();
    	$type = $v['type'];
    	$id = $v['id'];
    	    	
    	$commonDeclaredInfo=array();
    	if($type==2 && !empty($id)){
    		$commonDeclaredInfo = CommonDeclaredInfo::find()->where(['id'=>$id])->asArray()->all();
    		$commonDeclaredInfo=$commonDeclaredInfo[0];
    	}
    	//print_r($commonDeclaredInfo);die;
    	return $this->renderAjax('_createoreditdeclare',[
    			'type'=>$type,
    			'commonDeclaredInfo'=>$commonDeclaredInfo,
    			]);
    }
    
    /**
     * 修改保存常用报关信息
     *
     * @param $type 类型 1：新增，2：修改，3：删除，4：更改默认值
     * +-------------------------------------------------------------------------------------------
     * log			name	date					note
     * @author		lgw		2016/08/12				初始化
     * +-------------------------------------------------------------------------------------------
     */
    public function actionSaveDeclare(){
    	$type = $_GET['type'];
    	$reponse=array();
    	    	    	
    	$reponse=array(
    			'cfName'=>isset($_POST['cfName'])?$_POST['cfName']:'',
    			'nameCh'=>isset($_POST['cfName'])?$_POST['nameCh']:'',
    			'nameEn'=>isset($_POST['cfName'])?$_POST['nameEn']:'',
    			'declaredValue'=>isset($_POST['cfName'])?$_POST['declaredValue']:'',
    			'weight'=>isset($_POST['cfName'])?$_POST['weight']:'',
    			'defaultHsCode'=>isset($_POST['cfName'])?$_POST['defaultHsCode']:'',
    			'is_default'=>isset($_POST['ck'])?$_POST['ck']:'0',
    	);
    	
    	if($type!=1)
    		$reponse['cid']=$_POST['cid'];
//     	$result=CarrierOpenHelper::getDeclare(1,true);
    	$result=CarrierOpenHelper::saveDeclare($type,$reponse);
    	return exit(json_encode(array('code'=>$result['response']['code'], 'msg'=>$result['response']['msg'][0], 'data'=>$result['response']['data'])));   		
    }
    
    /**
     * 检测sku集合，哪些未在商品库，哪些是别名需转换为root sku
     *
     * @param $skulist sku集合
     * +-------------------------------------------------------------------------------------------
     * log			name	date					note
     * @author		lrq		2016/10/12				初始化
     * +-------------------------------------------------------------------------------------------
     */
    public function actionCheckSkuInfo()
    {
        if(!empty( $_POST['skulist']))
        {
        	$skulist = $_POST['skulist'];
        	//将换行符替换空格
        	$replaceArr=array("\t","\n","\r","，");
		    $replaceAfterArr=array("","","",",");
		    $skulist = str_replace($replaceArr, $replaceAfterArr, $skulist);
		
		    $not_exist_sku = [];
		    $sku_aliases =[];
		    $sku_root_list = [];
		    $sku_root = '';
		    
        	$skus = explode(",",$skulist);
        	foreach ($skus as $sku)
        	{
        	    if(!empty($sku))
        	    {
            	    //查询对应的root sku
            	    $root = ProductApiHelper::getRootSKUByAlias($sku);
            	    if(!empty($root))
            	    {
                	    if(strtoupper($root) != strtoupper($sku)){
                	        if( !in_array(strtoupper($sku), $sku_aliases)){
                	            $sku_aliases[] = strtoupper($sku);
                	        }
                	    }
                	    
                	    if( !in_array(strtoupper($root), $sku_root_list)){
                	        $sku_root_list[] = strtoupper($root);
                	        $sku_root .= $root .',';
                	    }
            	    }
            	    else 
            	    {
                	    //检测sku是否存在商品库
                	    $model = Product::findOne(['sku'=>$sku]);
                	    if(empty($model)){
                	    	if( !in_array($sku, $not_exist_sku)){
                	    		$not_exist_sku[] = $sku;
                	    	}
                	    }
            	         else if( !in_array(strtoupper($sku), $sku_root_list)){
                	        $sku_root_list[] = strtoupper($sku);
                	        $sku_root .= $root .',';
                	    }
            	    }
        	    }
        	}
        	
        	//去掉$sku_root后面的逗号
        	$sku_root = rtrim($sku_root, ',');
        	
        	$rtn = array(
        	    'not_exist_sku' => $not_exist_sku,
    	        'sku_aliases' => $sku_aliases,
    	        'sku_root' => $sku_root,
        	    'skulist' => $skulist,
        	);
        	
        	return json_encode($rtn);
        }
    }

    /**
     * 打开高仿标签详细界面
     *
     *@param $type 高仿标签类型 md:地址面单, bg:报关面单, jh:配货单'
     *@param $id   高仿标签ID
     * +-------------------------------------------------------------------------------------------
     * log			name	date					note
     * @author		lgw		2016/10/10				初始化
     * +-------------------------------------------------------------------------------------------
     */
    public function actionCustomlabellist(){
    	$type = 'none';
    	$id='0';
    	if(isset($_GET['type'])){
    		$type = $_GET['type'];
    	}    
    	if(isset($_GET['id'])){
    		$id = $_GET['id'];
    	}
    	
    	$sysLabel=array();
    	if($type=='md')
    		$sysLabel = CarrierOpenHelper::getCarrierTemplateHighcopyType('carrier_lable',$id);
    	else if($type=='bg')
    		$sysLabel = CarrierOpenHelper::getCarrierTemplateHighcopyType('declare_lable',$id);
    	else if($type=='jh')
    		$sysLabel = CarrierOpenHelper::getCarrierTemplateHighcopyType('items_lable',$id);

    	return $this->renderPartial('_customlabellist',[
    			'sysLabel'=>$sysLabel,
    			'type'=>$_GET['type'],
    			]);
    }
    
    //同步速卖通线上发货地址
    public function actionUpdateAliexpressAddressInof(){
    	$user=\Yii::$app->user->identity;
    	$puid = $user->getParentUid();
    	
    	$result = \eagle\modules\carrier\helpers\CarrierOpenHelper::setUpdateAliexpressAddressInof($puid);
    	
    	return json_encode($result);
    }
    
    //eDis同步地址和交运偏好信息
    public function actionUpdateEdisAddressInof(){
    	$user=\Yii::$app->user->identity;
    	$puid = $user->getParentUid();
    	$serviceID=$_POST["serviceID"];
 
    	$result_view=\eagle\modules\carrier\helpers\CarrierOpenHelper::UpdateEdisAddressInof($puid,$serviceID);
    	    	 
    	return $result_view;
    }
    
    //获取买家选择运输服务列表
    public function actionBuyerTransportationServiceList(){
    	$selectedServiceArr = array();
    	 
    	if(!empty($_GET['selected_service'])){
    		$tmp_selected_service = $_GET['selected_service'];
    	
    		$selectedServiceArr = explode(",",$tmp_selected_service);
    	}
    	
    	//买家选择物流物流
    	$buyer_transportation_services = PlatformAccountApi::getAllPlatformShippingServices();

    	$aliexpress_services = $buyer_transportation_services['aliexpress'];
    	$ebay_services = $buyer_transportation_services['ebay'];
    	$amazon_services = $buyer_transportation_services['amazon'];
    	$cdiscount_services = $buyer_transportation_services['cdiscount'];
    	$priceminister_services = $buyer_transportation_services['priceminister'];
    	$newegg_services = $buyer_transportation_services['newegg'];
    	$linio_services = $buyer_transportation_services['linio'];
    	$jumia_services = $buyer_transportation_services['jumia'];
    	
    	//ebay站点
    	$ebay_services_site = array();
    	
    	foreach ($ebay_services as $ebay_servicesKey => $ebay_servicesVal){
    		$ebay_services_site[$ebay_servicesKey] = $ebay_servicesKey;
    	}
    	
    	return $this->renderPartial('buyerTransportationServiceList' , ['selectedServiceArr'=>$selectedServiceArr, 
    			'aliexpress_services'=>$aliexpress_services,'ebay_services'=>$ebay_services, 'ebay_services_site'=>$ebay_services_site,
    			'amazon_services'=>$amazon_services, 'cdiscount_services'=>$cdiscount_services, 'priceminister_services'=>$priceminister_services, 'newegg_services'=>$newegg_services,
    			'linio_services'=>$linio_services,'jumia_services'=>$jumia_services,
    			]);
    }
    
    /**
     * 打开物流标签自定义界面New
     * +-------------------------------------------------------------------------------------------
     * log			name	date					note
     * @author		hqw		2017/1/5				初始化
     * +-------------------------------------------------------------------------------------------
     */
    public function actionCarrierCustomLabelListNew(){
    	$params = $_GET;
    	$customLabel = CarrierOpenHelper::getCarrierCustomLabelTemplates($params, 1);
    	$pagination = $customLabel['pagination'];
    	$templates_total = $pagination->totalCount;
    	$data = $customLabel['data'];
    	$sort = $customLabel['sort'];
    		
    	$sysLabel = CarrierOpenHelper::getCarrierSysLabelTemplates($params, 1);
    	$sys_pagination = $sysLabel['pagination'];
    	$sys_templates_total = $sys_pagination->totalCount;
    	$sys_data = $sysLabel['data'];
    	$sys_sort = $sysLabel['sort'];
    	$size = $sysLabel['size'];
    
    	//页面上tab的激活标识
    	$tab_active = \Yii::$app->request->get('tab_active');
    	return $this->render('carrierCustomLabelNew',[
    			'templates_total'=>$templates_total,
    			'templates' => $data,
    			'pages' => $pagination,
    			'sort'=>$sort,
    			'sys_templates_total'=>$sys_templates_total,
    			'systemplates'=>$sys_data,
    			'syspages' => $sys_pagination,
    			'syssort'=>$sys_sort,
    			'size'=>$size,
    			'selftemplate'=>'',
    			'tab_active'=>$tab_active
    			]);
    }
    
    /**
     * 编辑物流标签模版New
     */
    public function actionEditCarrierTemplateNew(){
		//获取模版信息
		$id = isset($_GET['id'])?$_GET['id']:'';
		$uid = \Yii::$app->user->id;

		if(($id == -1) && ($uid == 1)){
			$template = new CrTemplate();
			$template->template_id = '';
			$template->template_name = '';
			$template->template_type = empty($sysTemplateOne['template_type']) ? '地址单' : $sysTemplateOne['template_type'];
			$template->template_width = empty($sysTemplateOne['template_width']) ? '100' : $sysTemplateOne['template_width'];
			$template->template_height = empty($sysTemplateOne['template_height']) ? '100' : $sysTemplateOne['template_height'];
			$template->template_content = empty($sysTemplateOne['template_content']) ? '' : $sysTemplateOne['template_content'];
		}else{
		    $_GET['template_name'] = @\yii\helpers\Html::encode(@$_GET['template_name']);
		    $_GET['width'] = (int)$_GET['width'];
		    $_GET['height'] = (int)$_GET['height'];
		    
			$template = CarrierOpenHelper::getCarrierTemplateById(0,$id,$_GET);
		}
    	
    	return $this->renderPartial('editCarrierTemplateNew',['template'=>$template]);
    }
    
    /**
     * 预览物流标签New
     */
    public function actionPreviewCarrierTemplateNew(){
    	$params = $_GET;
    	if(\Yii::$app->request->IsPost){
    		$params = $_POST;
    	}
    	
    	$orderOne = new \eagle\modules\order\models\OdOrder();
    	
    	
    	$str = $params['template_content'];
    	$custom_format_label = array($params['width'], $params['height']);
    	
    	$result = PrintPdfHelper::getCustomFormatPDF(array($orderOne), $str, $custom_format_label);
    	
    	print_r($result);
    	
    	
//     	$template = CarrierOpenHelper::getCarrierTemplateById(@$params['is_sys'],@$params['template_id'],$params);
//     	return $this->renderPartial('previewCarrierTemplateNew',[
//     			'template'=>$template,
//     	]);
    }
}
