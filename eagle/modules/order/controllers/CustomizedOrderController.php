<?php
namespace eagle\modules\order\controllers;

use yii\data\Pagination;
use eagle\modules\order\models\OdOrder;
use eagle\modules\order\models\Excelmodel;

use common\helpers\Helper_Array;
use eagle\modules\order\models\OdOrderItem;
use eagle\modules\util\helpers\OperationLogHelper;
use eagle\modules\order\models\Usertab;
use Exception;
use eagle\modules\order\models\OdOrderShipped;
use eagle\models\carrier\SysShippingService;
use yii\data\Sort;

use eagle\modules\inventory\models\Warehouse;
use eagle\modules\inventory\helpers\InventoryApiHelper;
use eagle\modules\order\helpers\OrderTagHelper;
use eagle\modules\order\helpers\OrderHelper;
use eagle\modules\app\apihelpers\AppTrackerApiHelper;
use eagle\modules\carrier\apihelpers\CarrierApiHelper;
use eagle\modules\util\helpers\ResultHelper;
use eagle\modules\util\helpers\TranslateHelper;
use yii\helpers\Url;
use eagle\modules\delivery\helpers\DeliveryHelper;
use eagle\models\SysCountry;
use eagle\modules\order\helpers\OrderBackgroundHelper;
use eagle\modules\inventory\helpers\WarehouseHelper;
use eagle\modules\carrier\helpers\CarrierOpenHelper;
use eagle\models\SaasCustomizedUser;
use eagle\modules\order\apihelpers\OrderApiHelper;
use eagle\modules\util\helpers\ConfigHelper;
use eagle\modules\order\helpers\CustomizedOrderHelper;
use eagle\modules\order\helpers\OrderUpdateHelper;
use eagle\modules\platform\apihelpers\PlatformAccountApi;
/*
use eagle\modules\order\helpers\OrderUpdateHelper;
use eagle\modules\order\helpers\OrderGetDataHelper;
use eagle\modules\order\helpers\OrderBackgroundHelper;
*/

class CustomizedOrderController extends \eagle\components\Controller{
	public $enableCsrfValidation = false;
	
	/**
	 * 自定义平台 订单列表
	 * @author LZHL
	 */
	public function actionList(){
		//check模块权限
		$permission = \eagle\modules\permission\apihelpers\UserApiHelper::checkPlatformPermission('customized');
		if(!$permission)
			return $this->render('//errorview_no_close',['title'=>'访问受限:没有权限','error'=>'您还没有订单模块的权限!']);
		
		AppTrackerApiHelper::actionLog("Oms-customized", "/order/customized/list");
	
		$puid = \Yii::$app->user->identity->getParentUid();
		$tmpSellerIDList = PlatformAccountApi::getPlatformAuthorizeAccounts('customized');//添加账号权限	lzhl 2017-03
		/*
		$customizedUsers = SaasCustomizedUser::find()->where(['uid'=>$puid])->asArray()->all();
		$customizedUsersDropdownList = array();
		foreach ($customizedUsers as $customizedUser){
			$customizedUsersDropdownList[$customizedUser['username']] = $customizedUser['store_name'];
		}
		*/
		foreach($tmpSellerIDList as $sellerloginid=>$store_name){
			$customizedUsersDropdownList[$sellerloginid] = $store_name;
		}
		//如果为测试用的账号就不受平台绑定限制
		$test_userid=\eagle\modules\tool\helpers\MirroringHelper::$test_userid;
		if(empty($customizedUsersDropdownList)){
			if (!in_array($puid,$test_userid['yifeng'])){
				//无有效权限账号时
				return $this->render('//errorview_no_close',['title'=>'访问受限:没有权限','error'=>'您还没有获得任何 自定义店铺  账号管理权限!']);
			}
		}

		$data=OdOrder::find();
		//不显示 解绑的账号的订单
		if (!in_array($puid,$test_userid['yifeng']))
			$data->andWhere(['selleruserid'=>array_keys($customizedUsersDropdownList)]);
		
		//默认打开的列表记录数为上次用户选择的page size 数	//lzhl	2016-11-30
		$page_url = $page_url = '/'.\Yii::$app->controller->module->id.'/'.\Yii::$app->controller->id.'/'.\Yii::$app->controller->action->id;
		$last_page_size = ConfigHelper::getPageLastOpenedSize($page_url);
		if(empty($last_page_size))
			$last_page_size = 20;//默认显示值
		if(empty($_REQUEST['per-page']) && empty($_REQUEST['page']))
			$pageSize = $last_page_size;
		else{
			$pageSize = empty($_REQUEST['per-page'])?20:$_REQUEST['per-page'];
		}
		ConfigHelper::setPageLastOpenedSize($page_url, $pageSize);
		
		/*
		if (!empty($customizedUsersDropdownList)){
			//不显示 unActive的账号的订单
			$data->andWhere(['selleruserid'=>array_keys($customizedUsersDropdownList)]);
		}
		*/
		
		$showsearch=0;
		$op_code = '';
		
		$addi_condition = ['order_source'=>'customized'];
		$addi_condition['sys_uid'] = \Yii::$app->user->id;
		$addi_condition['selleruserid_tmp'] = $customizedUsersDropdownList;
		
		$startDateTime = empty($_REQUEST['starttime'])?'':$_REQUEST['starttime'];
		$endDateTime = empty($_REQUEST['endtime'])?'':$_REQUEST['endtime'];
		
		$_REQUEST;
		$tmp_REQUEST_text['where']=empty($data->where)?Array():$data->where;
		$tmp_REQUEST_text['orderBy']=empty($data->orderBy)?Array():$data->orderBy;
		$omsRT = OrderApiHelper::getOrderListByConditionOMS($_REQUEST,$addi_condition,$data,$pageSize,false,'all');
		
		if (!empty($_REQUEST['order_status'])){
			//生成操作下拉菜单的code
			$op_code = $_REQUEST['order_status'];
		}
		if (!empty($omsRT['showsearch'])) $showsearch = 1;
		
		//$commandQuery = clone $data;
		//echo $commandQuery->createCommand()->getRawSql();
		
		$pages = new Pagination([
				'defaultPageSize' => 20,
				'pageSize' => $pageSize,
				'totalCount' => $data->count(),
				'pageSizeLimit'=>[20,200],//每页显示条数范围
				'params'=>$_REQUEST,
				]);
		$models = $data->offset($pages->offset)
			->limit($pages->limit)
			->all();
		
		
		$excelmodel	=	new Excelmodel();
		$model_sys	=	$excelmodel->find()->all();
		 
		$excelmodels=array(''=>'导出订单');
		if(isset($model_sys)&&!empty($model_sys)){
			foreach ($model_sys as $m){
				$excelmodels[$m->id]=$m->name;
			}
		}
		
		$counter=[];
		/*
		if (!empty($_REQUEST['selleruserid'])){
			$counter = OrderHelper::getMenuStatisticData('customized',['selleruserid'=>$_REQUEST['selleruserid']]);
			$counter['todayorder'] = OdOrder::find()->where(['and',['order_source'=>'customized'],['>=','order_source_create_time',strtotime(date('Y-m-d'))],['<','order_source_create_time',strtotime('+1 day')] ])
				->andWhere(['selleruserid'=>$_REQUEST['selleruserid']])->count();
			$counter['sendgood'] = OdOrder::find()->where(['order_source'=>'customized','order_source_status'=>['Unshipped','PartiallyShipped']])->andwhere('order_status < 300')
				->andWhere(['selleruserid'=>$_REQUEST['selleruserid']])->count();
		}else{
			if(!empty($customizedUsersDropdownList)){
				$selleruserid_arr = array_keys($customizedUsersDropdownList);
				$counter = OrderHelper::getMenuStatisticData('customized',['selleruserid'=>$selleruserid_arr]);
				$counter['todayorder'] = OdOrder::find()->where(['and',['order_source'=>'customized','selleruserid'=>$selleruserid_arr],['>=','order_source_create_time',strtotime(date('Y-m-d'))],['<','order_source_create_time',strtotime('+1 day')] ])->count();
				$counter['sendgood'] = OdOrder::find()->where(['order_source'=>'customized','selleruserid'=>$selleruserid_arr,'order_source_status'=>['Unshipped','PartiallyShipped']])->andwhere('order_status < 300')->count();
			}else{
				$counter=[];
			}
		}
		*/
		
		$usertabs = Helper_Array::toHashmap(Helper_Array::toArray(Usertab::find()->all()),'id','tabname');
		$warehouseids = InventoryApiHelper::getWarehouseIdNameMap();
		$selleruserids = $customizedUsersDropdownList;
		$countrycode = OdOrder::getDb()->createCommand('select consignee_country_code from '.OdOrder::tableName().' group by consignee_country_code')->queryColumn();
		$countrycode=array_filter($countrycode);
		
		//tag 数据获取
		$allTagDataList = OrderTagHelper::getTagByTagID();
		$allTagList = [];
		foreach($allTagDataList as $tmpTag){
			$allTagList[$tmpTag['tag_id']] = $tmpTag['tag_name'];
		}
			
		//订单导航
		if (!empty($_REQUEST['order_status']))
			$order_nav_key_word = $_REQUEST['order_status'];
		else
			$order_nav_key_word='';
	
	
		//国家
		$query = SysCountry::find();
		$regions = $query->orderBy('region')->groupBy('region')->select('region')->asArray()->all();
		$countrys =[];
		foreach ($regions as $region){
			$arr['name']= $region['region'];
			$arr['value']=Helper_Array::toHashmap(SysCountry::find()->where(['region'=>$region['region']])->orderBy('country_en')->select(['country_code', "CONCAT( country_zh ,'(', country_en ,')' ) as country_name "])->asArray()->all(),'country_code','country_name');
			$countrys[]= $arr;
		}
	
		$country_list=[];
	
		//获取国家列表
		$countryArr = array();
		$tmpCountryArr = OdOrder::find()->select('consignee_country_code, consignee_country')->distinct('consignee_country')->where(['order_source' => 'customized'])->asArray()->all();
		$countryArr = Helper_Array::toHashmap($tmpCountryArr , 'consignee_country_code' , 'consignee_country');
		$countryArr = array_filter($countryArr);
		//end 获取国家列表
	
		//获取dash board cache 数据
		$uid = \Yii::$app->user->identity->getParentUid();
		// 		$DashBoardCache = AliexpressOrderHelper::getOmsDashBoardCache($uid);
	
		//检查报关信息是否存在 start
		$OrderIdList = [];
		$existProductResult = OrderBackgroundHelper::getExistProductRuslt($models);
		//检查报关信息是否存在 end
		
		$tmp_REQUEST_text['REQUEST']=$_REQUEST;
		$tmp_REQUEST_text['order_source']=$addi_condition;
		$tmp_REQUEST_text['params']=empty($data->params)?Array():$data->params;
		$tmp_REQUEST_text=base64_encode(json_encode($tmp_REQUEST_text));
	
		return $this->render('list',array(
				'models' => $models,
				'existProductResult'=>$existProductResult,
				'pages' => $pages,
				'excelmodels'=>$excelmodels,
				'usertabs'=>$usertabs,
				'counter'=>$counter,
				'warehouseids'=>$warehouseids,
				'selleruserids'=>$selleruserids,
				'countrys'=>$countrys,
				'showsearch'=>$showsearch,
				'tag_class_list'=> OrderTagHelper::getTagColorMapping(),
				'all_tag_list'=>$allTagList,
				'doarr'=>CustomizedOrderHelper::getCurrentOperationList($op_code,'b'),
				'doarr_one'=>CustomizedOrderHelper::getCurrentOperationList($op_code,'s'),
				'country_mapping'=>$country_list,
				'region'=>WarehouseHelper::countryRegionChName(),
				'search'=>'',
				'countryArr'=>$countryArr,
				'order_nav_html'=>CustomizedOrderHelper::getCustomizedOmsNav($order_nav_key_word),
				'search_condition'=>$tmp_REQUEST_text,
				'search_count'=>$pages->totalCount,
		));
	}
	
	
	/**
	 * 订单编辑
	 * @author winton
	 */
	public function actionEdit(){
		if (\Yii::$app->request->isPost){
			if (count($_POST['item']['product_name'])==0){
				return $this->render('//errorview',['title'=>'编辑订单','error'=>'订单必需有相应商品']);
			}
			$order = OdOrder::findOne($_POST['orderid']);
			if (empty($order)){
				return $this->render('//errorview',['title'=>'编辑订单','error'=>'无对应订单']);
			}
			$item_tmp = $_POST['item'];
			$_tmp = $_POST;
			unset($_tmp['orderid']);
			unset($_tmp['item']);
			if (!empty($_tmp['default_shipping_method_code'])){
				$serviceid = SysShippingService::findOne($_tmp['default_shipping_method_code']);
				if (!empty($serviceid)||!$serviceid->isNewRecord){
					$_tmp['default_shipping_method_code']=$_tmp['default_shipping_method_code'];
					$_tmp['default_carrier_code']=$serviceid->carrier_code;
				}
			}
			
			/*使用康华统一修改数量方法，旧有的弃用————2016/10/08
			$order->setAttributes($_tmp);
			$order->save();
			*/
			$new_status = $order->order_status;
				
			$action = '修改订单';
			$module = 'order';
			$fullName = \Yii::$app->user->identity->getFullName();
			$rt = OrderUpdateHelper::updateOrder($order, $_tmp , false , $fullName , $action , $module);
			
			//存储订单对应商品
			foreach ($item_tmp['product_name'] as $key=>$val){
				if (strlen($item_tmp['itemid'][$key])){
					$item = OdOrderItem::findOne($item_tmp['itemid'][$key]);
				}else{
					$item = new OdOrderItem();
				}
				$item->order_id = $order->order_id;
				$item->product_name = $item_tmp['product_name'][$key];
				$item->sku = $item_tmp['sku'][$key];
				$item->quantity = $item_tmp['quantity'][$key];
				$item->price = $item_tmp['price'][$key];
				$item->update_time = time();
				$item->create_time = is_null($item->create_time)?time():$item->create_time;
				if ($item->save()){
    				/*
    				 if ($OriginQty != $item_tmp['quantity'][$key]){
    					list($ack , $message , $code , $rootSKU ) = array_values(OrderGetDataHelper::getRootSKUByOrderItem($item));
    					
    					list($ack , $code , $message  )  = array_values( OrderBackgroundHelper::updateUnshippedQtyOMS($rootSKU, $order->default_warehouse_id, $order->default_warehouse_id, $OriginQty, $item_tmp['quantity'][$key]));
    					if ($ack){
    						$addtionLog .= "$rootSKU $OriginQty=>".$item_tmp['quantity'][$key];
    					}
    				}
    				*/
    			}
			}
			//$order->checkorderstatus();
			//$order->save();
				
			AppTrackerApiHelper::actionLog("Oms-customized", "/order/customized/edit-save");
			OperationLogHelper::log('order',$order->order_id,'修改订单','编辑订单进行订单修改',\Yii::$app->user->identity->getFullName());
			
			echo "<script language='javascript'>window.opener.location.reload();</script>";
			return $this->render('//successview',['title'=>'编辑订单']);
		}
	
		AppTrackerApiHelper::actionLog("Oms-customized", "/order/customized/edit-page");
		if (!isset($_GET['orderid'])){
			return $this->render('//errorview',['title'=>'编辑订单','error'=>'链接有误']);
		}
		$order = OdOrder::findOne($_GET['orderid']);
		if (empty($order)||$order->isNewRecord){
			return $this->render('//errorview',['title'=>'编辑订单','error'=>'未找到相应订单']);
		}
	
		$carriers = CarrierApiHelper::getShippingServices2_1();// 所有运输服务
		$warehouses = InventoryApiHelper::getWarehouseIdNameMap();// 所有仓库
	
		$warehouseList = InventoryApiHelper::getWarehouseIdNameMap(true);
		$shipmethodList = [];
		if(!empty($warehouseList)){
			foreach ($warehouseList as $k=>$name){
				$shippingMethodInfo = CarrierOpenHelper::getShippingMethodNameInfo(array('proprietary_warehouse'=>$k), -1);
				$shippingMethodInfo = $shippingMethodInfo['data'];
				if(!empty($shippingMethodInfo)){
					foreach ($shippingMethodInfo as $id=>$ship){
						$shipmethodList[$id] = $ship['service_name'];
					}
				}
				break;
			}
		}
	
		$selfCustomizedOrderStatus = OdOrder::$status;
		unset($selfCustomizedOrderStatus[100]);
		unset($selfCustomizedOrderStatus[400]);
	
		return $this->render('edit',['order'=>$order,'carriers'=>$shipmethodList , 'warehouses'=>$warehouseList , 'selfCustomizedOrderStatus'=>$selfCustomizedOrderStatus]);
	}
	

	/**
	 +----------------------------------------------------------
	 * 异步加载 left menu 数据
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * @author		lzhl 	2016/12/21				初始化
	 +----------------------------------------------------------
	 **/
	public function actionLeftMenuAutoLoad(){
		//不显示 解绑的账号的订单 start
		$puid = \Yii::$app->user->identity->getParentUid();
		$tmpSellerIDList = SaasCustomizedUser::find()->where(['uid'=>$puid])->asArray()->all();
		$customizedAccountList = array();
		foreach ($tmpSellerIDList as $customizedUser){
			$customizedAccountList[$customizedUser['username']] = $customizedUser['store_name'];
		}
	
		if (!empty($_REQUEST['selleruserid'])){
			$counter = OrderHelper::getMenuStatisticData('customized',['selleruserid'=>$_REQUEST['selleruserid']]);

		}else{
			if(!empty($customizedAccountList)){
				$counter = OrderHelper::getMenuStatisticData('customized',['selleruserid'=>array_keys($customizedAccountList)]);
			}else{
				//无有效绑定账号
				$counter=[];
			}
		}
		exit( json_encode($counter) );
	}
}

?>