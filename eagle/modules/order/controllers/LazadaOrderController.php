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
use eagle\models\carrier\SysShippingService;
use yii\data\Sort;

use eagle\modules\inventory\helpers\InventoryApiHelper;
use eagle\modules\order\helpers\OrderTagHelper;
use eagle\modules\order\helpers\OrderHelper;
use eagle\modules\app\apihelpers\AppTrackerApiHelper;
use eagle\models\SaasLazadaUser;
use eagle\modules\lazada\apihelpers\LazadaApiHelper;
use eagle\modules\carrier\apihelpers\CarrierApiHelper;
use eagle\modules\util\helpers\ResultHelper;
use common\api\lazadainterface\LazadaInterface_Helper;
use eagle\modules\util\helpers\TranslateHelper;
use eagle\models\SysCountry;
use eagle\modules\order\helpers\OrderBackgroundHelper;
use eagle\modules\inventory\helpers\WarehouseHelper;
use eagle\modules\carrier\helpers\CarrierOpenHelper;
use eagle\modules\order\helpers\LazadaOrderHelper;
use eagle\modules\order\apihelpers\OrderApiHelper;
use eagle\modules\order\helpers\OrderUpdateHelper;
use eagle\modules\order\helpers\OrderGetDataHelper;
use common\helpers\Helper_Curl;
use eagle\modules\util\helpers\ConfigHelper;
use eagle\modules\platform\apihelpers\PlatformAccountApi;
use eagle\modules\util\helpers\RedisHelper;
use eagle\modules\order\helpers\OrderListV3Helper;

class LazadaOrderController extends \eagle\components\Controller{
	public $enableCsrfValidation = false;
	
	public function actionList(){
// 		print_r($_REQUEST);die;
		//check模块权限
		$permission = \eagle\modules\permission\apihelpers\UserApiHelper::checkPlatformPermission('lazada');
		if(!$permission)
			return $this->render('//errorview_no_close',['title'=>'访问受限:没有权限','error'=>'您还没有订单模块的权限!']);

		AppTrackerApiHelper::actionLog("Oms-lazada", "/order/lazada/list");
		
		//默认打开的列表记录数为上次用户选择的page size 数	//lzhl	2016-11-30
		$page_url = '/'.\Yii::$app->controller->module->id.'/'.\Yii::$app->controller->id.'/'.\Yii::$app->controller->action->id;
		$last_page_size = ConfigHelper::getPageLastOpenedSize($page_url);
		if(empty($last_page_size))
			$last_page_size = 20;//默认显示值
		if(empty($_REQUEST['per-page']) && empty($_REQUEST['page']))
			$pageSize = $last_page_size;
		else{
			$pageSize = empty($_REQUEST['per-page'])?50:$_REQUEST['per-page'];
		}
		ConfigHelper::setPageLastOpenedSize($page_url, $pageSize);
		
		$data=OdOrder::find();
// 		$data->andWhere(['order_source'=>'lazada']);
		
		$tmpSellerIDList = PlatformAccountApi::getPlatformAuthorizeAccounts('lazada');//添加账号权限	lzhl 2017-03
		$accountList = [];
		$selleruserids = [];
		$addi_condition = ['order_source'=>'lazada'];
		
		// @todo dzt20170522 由于platform接口用saas_lazada_user platform_userid （邮箱）来区分账号唯一，导致一个邮箱多个站点的账号无法读写针对站点的授权
		// 目前先不fix这个问题 ， 仅oms 通过platform_userid 搜回所有账号，然后再oms 界面显示store name 代替显示账号邮箱
		foreach($tmpSellerIDList as $sellerloginid=>$store_name){
			$accountList[] = $sellerloginid;
			$selleruserids[$sellerloginid] = $store_name;
		}
		//如果为测试用的账号就不受平台绑定限制
		$test_userid=\eagle\modules\tool\helpers\MirroringHelper::$test_userid;
		if(empty($accountList)){
			if (!in_array(\Yii::$app->user->identity->getParentUid(),$test_userid['yifeng'])){
				//无有效权限账号时
				return $this->render('//errorview_no_close',['title'=>'访问受限:没有权限','error'=>'您还没有获得任何 Lazada 账号管理权限!']);
			}
		}
		
		
		//不显示 解绑的账号的订单
		if (!in_array(\Yii::$app->user->identity->getParentUid(),$test_userid['yifeng'])){
// 		    $data->andWhere(['selleruserid'=>$accountList]);
		    $addi_condition['selleruserid'] = $accountList;
		}
			
		
		$showsearch=0;
		$op_code = '';
		
		//不显示 解绑的账号的订单 start
		//testkh start
		if (isset(\Yii::$app->params["currentEnv"]) and \Yii::$app->params["currentEnv"]=="test" ){
			$aliAccountList = [];//testkh
		}
		//testkh end
// 		if (!empty($aliAccountList)){
// 			//不显示 解绑的账号的订单
// // 			$data->andWhere(['selleruserid'=>$aliAccountList]);
// 		}
		
		//不显示 解绑的账号的订单 end
		
		if (!empty($_REQUEST['order_source_status'])){
				
			//Aliexpress状态
			if ($_REQUEST['order_source_status'] == 'CUSTOM_WAIT_SEND_MOENY'){
				//部分发货 , 等待买家收货 , 等待您确认金额
				$data->andWhere(['order_source_status'=>['SELLER_PART_SEND_GOODS' , 'WAIT_BUYER_ACCEPT_GOODS','WAIT_SELLER_EXAMINE_MONEY']]);
			}else{
				$data->andWhere('order_source_status = :order_source_status',[':order_source_status'=>$_REQUEST['order_source_status']]);
			}
				
			$showsearch=1;
		}
		
		if(!empty($_REQUEST['order_type'])){
		    $showsearch = 1;
		    if($_REQUEST['order_type']=='FBM')
		        $data->andWhere(['order_type'=>['',null,'FBM']]);
		    elseif(strtolower($_REQUEST['order_type']=='FBL'))
                $data->andWhere(['order_type'=>'FBL']);
		    else
		        unset($_REQUEST['order_type']);
		}
		 
		
		$addi_condition['sys_uid'] = \Yii::$app->user->id;
		$addi_condition['selleruserid_tmp'] = $accountList;
		
		$tmp_REQUEST_text['where']=empty($data->where)?Array():$data->where;
		$tmp_REQUEST_text['orderBy']=empty($data->orderBy)?Array():$data->orderBy;
		
		
		$request_condition = $_REQUEST;
		if (!empty($request_condition['selleruserid'])){
		    //搜索卖家账号
		    list($selleruserid , $siteId) = explode("_@@_", $_REQUEST['selleruserid']);// xxx@xx.com_XX_my
		    if(strtolower($siteId) == 'cb'){
		        $data->andWhere(['selleruserid'=>$selleruserid]);
		    }else{
		        $data->andWhere(['selleruserid'=>$selleruserid , 'order_source_site_id'=>$siteId]);
		        $_REQUEST['country'] = $siteId;
		    }
		    
		    // 不unset的话 OrderApiHelper::getOrderListByConditionOMS接口会重新search selleruserid 覆盖结果
		    // order_source_site_id 也unset 了 如果有客户选择其他站点国家，可能会找了其他账号的订单
		    unset($request_condition['selleruserid']);
		    unset($request_condition['country']);
		    
		}else{
			
			if (!empty($request_condition['selleruserid_combined'])){
				$tmp_selleruserid_combined = $request_condition['selleruserid_combined'];
				$tmp_pcCombination = array();
				
				if((strlen($tmp_selleruserid_combined) > 8) && (substr($tmp_selleruserid_combined, 0, 4) == 'com-') && (substr($tmp_selleruserid_combined, -4) == '-com')){
					$tmp_selleruserid_combined = substr($tmp_selleruserid_combined, 4);
					$tmp_selleruserid_combined = substr($tmp_selleruserid_combined, 0, strlen($tmp_selleruserid_combined)-4);
					
					$pcCombination = OrderListV3Helper::getPlatformCommonCombination(array('type'=>'oms','platform'=>'lazada','com_name'=>$tmp_selleruserid_combined), $addi_condition['sys_uid']);
					
					if(count($pcCombination) > 0){
						$tmp_pcCombination = $pcCombination;
					}
				}else{
					$tmp_pcCombination = explode(",", $tmp_selleruserid_combined);
				}
				
				if(count($tmp_pcCombination) > 0){
					$tmp_laza_selleruserid_arr = array();
					$tmp_laza_country_arr = array();
					//$in_params['selleruserid'] = $tmp_pcCombination;
				
					foreach ($tmp_pcCombination as $tmp_pcCombination_V){
						unset($tmp_la_selleruserid);
						unset($tmp_la_siteId);
						list($tmp_la_selleruserid , $tmp_la_siteId) = explode("_@@_", $tmp_pcCombination_V);
						$tmp_laza_selleruserid_arr[] = $tmp_la_selleruserid;
						if(strtolower($tmp_la_siteId) != 'cb')
					        $tmp_laza_country_arr[] = $tmp_la_siteId;
					}
					
					if(!empty($tmp_laza_country_arr))
				        $data->andWhere(['selleruserid'=>$tmp_laza_selleruserid_arr , 'order_source_site_id'=>$tmp_laza_country_arr]);
				    else 
				        $data->andWhere(['selleruserid'=>$tmp_laza_selleruserid_arr]);
				    
					unset($addi_condition['selleruserid_tmp']);
					unset($request_condition['selleruserid_combined']);
					unset($request_condition['selleruserid']);
					unset($request_condition['country']);
					$_REQUEST['country'] = implode(',',$tmp_laza_country_arr);
					
				}else{
					//不成功时直接查找xxxxx
					$data->andWhere(['selleruserid'=>'xxxxx']);
				}
			}
		}
		
		$omsRT = OrderApiHelper::getOrderListByConditionOMS($request_condition,$addi_condition,$data,$pageSize,false,'all');
		if (!empty($_REQUEST['order_status'])){
		    //生成操作下拉菜单的code
		    $op_code = $_REQUEST['order_status'];
		}
		if (!empty($omsRT['showsearch'])) $showsearch = 1;
		
		$pages = new Pagination([
				'defaultPageSize' => 20,
				'pageSize' => $pageSize,
				'totalCount' => $data->count(),
				'pageSizeLimit'=>[5,200],//每页显示条数范围
				'params'=>$_REQUEST,
				]);
		$models = $data->offset($pages->offset)
		->limit($pages->limit)
		->all();
		 
		 
		// 调试sql
// 		$tmpCommand = $data->createCommand();
// 		echo "<br>".$tmpCommand->getRawSql();

		$excelmodel	=	new Excelmodel();
		$model_sys	=	$excelmodel->find()->all();
		 
		$excelmodels=array(''=>'导出订单');
		if(isset($model_sys)&&!empty($model_sys)){
			foreach ($model_sys as $m){
				$excelmodels[$m->id]=$m->name;
			}
		}
		 
		//订单数量统计
		$counter = [];
		$hitCache = "NoHit";
		$cachedArr = array();
		$uid = \Yii::$app->user->id;
		$stroe = 'all';
		if(!empty($_REQUEST['selleruserid']))
			$stroe  = trim($_REQUEST['selleruserid']);
		 
		$puid = \Yii::$app->user->identity->getParentUid();
		$isParent = \eagle\modules\permission\apihelpers\UserApiHelper::isMainAccount();
		if($isParent){
			$gotCache = RedisHelper::getOrderCache2($puid,$uid,'lazada',"MenuStatisticData",$stroe) ;
		}else{
			if (!empty($_REQUEST['selleruserid'])){
				$gotCache = RedisHelper::getOrderCache2($puid,$uid,'lazada',"MenuStatisticData",$_REQUEST['selleruserid']) ;
			}else{
				$gotCache = RedisHelper::getOrderCache2($puid,$uid,'lazada',"MenuStatisticData",'all') ;
			}
		}
		if (!empty($gotCache)){
			$cachedArr = is_string($gotCache)?json_decode($gotCache,true):$gotCache;
			$counter = $cachedArr;
			$hitCache= "Hit";
		}
		 
		//redis没有记录的话，则实时计算，再记录到redis
		if ($hitCache <>"Hit"){
			if (!empty($_REQUEST['selleruserid'])){
				$counter = OrderHelper::getMenuStatisticData('lazada',['selleruserid'=>$_REQUEST['selleruserid']]);
			}else{
				if(!empty($accountList)){
					$counter = OrderHelper::getMenuStatisticData('lazada',['selleruserid'=>$accountList]);
				}else{
					//无有效绑定账号
					$counter=[];
					$claimOrderIDs=[];
				}
			}
			//save the redis cache for next time use
			if (!empty($_REQUEST['selleruserid'])){
				RedisHelper::setOrderCache2($puid,$uid,'lazada',"MenuStatisticData",$_REQUEST['selleruserid'],$counter) ;
			}else{
				RedisHelper::setOrderCache2($puid,$uid,'lazada',"MenuStatisticData",'all',$counter) ;
			}
		}
		/*
		//订单数量统计
		if (!empty($_REQUEST['selleruserid'])){
			$counter = OrderHelper::getMenuStatisticData('lazada',['selleruserid'=>$_REQUEST['selleruserid']]);
			$counter['todayorder'] = OdOrder::find()->where(['and',['order_source'=>'lazada'],['>=','order_source_create_time',strtotime(date('Y-m-d'))],['<','order_source_create_time',strtotime('+1 day')] ])
			->andWhere(['selleruserid'=>$_REQUEST['selleruserid']])->count();
			$counter['sendgood'] = OdOrder::find()->where(['order_source'=>'lazada','order_source_status'=>'WaitingForShipmentAcceptation'])->andwhere('order_status < 300')
			->andWhere(['selleruserid'=>$_REQUEST['selleruserid']])->count();
			$counter['issueorder'] = OdOrder::find()->where(['order_source'=>'lazada','issuestatus'=>'IN_ISSUE'])->andWhere(['selleruserid'=>$_REQUEST['selleruserid']])->count();
		}else{
			$counter = OrderHelper::getMenuStatisticData('lazada');
			$counter['todayorder'] = OdOrder::find()->where(['and',['order_source'=>'lazada'],['>=','order_source_create_time',strtotime(date('Y-m-d'))],['<','order_source_create_time',strtotime('+1 day')] ])->count();
			$counter['sendgood'] = OdOrder::find()->where(['order_source'=>'lazada','order_source_status'=>'WaitingForShipmentAcceptation'])->andwhere('order_status < 300')->count();
			$counter['issueorder'] = OdOrder::find()->where(['order_source'=>'lazada','issuestatus'=>'IN_ISSUE'])->count();
		}
		*/	  
		
		$usertabs = Helper_Array::toHashmap(Helper_Array::toArray(Usertab::find()->all()),'id','tabname');
		$warehouseids = InventoryApiHelper::getWarehouseIdNameMap();
		//$selleruserids=Helper_Array::toHashmap(SaasLazadaUser::find()->where(['puid'=>\Yii::$app->user->identity->getParentUid()])->select(['platform_userid'])->andwhere(['platform'=>'lazada'])->andWhere("status <> 3")->asArray()->all(),'platform_userid','platform_userid');
		$countrycode=OdOrder::getDb()->createCommand('select consignee_country_code from '.OdOrder::tableName().' group by consignee_country_code')->queryColumn();
		$countrycode=array_filter($countrycode);
		// 	    $countrys=Helper_Array::toHashmap(EbayCountry::find()->where(['country'=>$countrycode])->orderBy('description asc')->select(['country','description'])->asArray()->all(),'country','description');
		$search = array('is_comment_status'=>'等待您留评');
		//tag 数据获取
		$allTagDataList = OrderTagHelper::getTagByTagID();
		$allTagList = [];
		foreach($allTagDataList as $tmpTag){
			$allTagList[$tmpTag['tag_id']] = $tmpTag['tag_name'];
		}
		 
		//订单导航
// 		if (!empty($_REQUEST['order_status']))
// 			$order_nav_key_word = $_REQUEST['order_status'];
// 		else
// 			$order_nav_key_word='';
		
		
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
		$tmpCountryArr = OdOrder::find()->select('consignee_country_code, consignee_country')->distinct('consignee_country')->where(['order_source' => 'lazada'])->asArray()->all();
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
		
		$doarr = LazadaOrderHelper::getCurrentOperationList($op_code,'b');
		$doarr_one = LazadaOrderHelper::getCurrentOperationList($op_code,'s');
		
		$order_types = array('FBM'=>"普通订单","FBL"=>"FBL订单");
		
		$tmp_REQUEST_text['REQUEST']=$_REQUEST;
		$tmp_REQUEST_text['order_source']=$addi_condition;
		$tmp_REQUEST_text['params']=empty($data->params)?Array():$data->params;
		$tmp_REQUEST_text=base64_encode(json_encode($tmp_REQUEST_text));
		
		// dzt20170522 重新获取下拉账号
		if(!empty($selleruserids))
		    $selleruserids = LazadaOrderHelper::getAccountStoreNameMapByEmail($selleruserids);
		
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
				'doarr'=>$doarr,
				'doarr_one'=>$doarr_one,
				'country_mapping'=>$country_list,
				'region'=>WarehouseHelper::countryRegionChName(),
				'search'=>$search,
				'countryArr'=>$countryArr,
		        'order_types'=>$order_types,
				'search_condition'=>$tmp_REQUEST_text,
				'search_count'=>$pages->totalCount,
		));
	}
	
	
	/**
	 * 订单编辑
	 * @author fanjs
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
			$addtionLog = '';
			if (!empty($_tmp['default_shipping_method_code'])){
				$serviceid = SysShippingService::findOne($_tmp['default_shipping_method_code']);
				if (!empty($serviceid)||!$serviceid->isNewRecord){
					$_tmp['default_shipping_method_code']=$_tmp['default_shipping_method_code'];
					$_tmp['default_carrier_code']=$serviceid->carrier_code;
				}
			}
			
			// 订单模块支持 待发货数量更新
			$action = '修改订单';
			$module = 'order';
			$fullName = \Yii::$app->user->identity->getFullName();
			$rt = OrderUpdateHelper::updateOrder($order, $_tmp , false , $fullName , $action , $module);
// 			$order->setAttributes($_tmp);
// 			$order->save();
			//存储订单对应商品
			foreach ($item_tmp['product_name'] as $key=>$val){
				if (strlen($item_tmp['itemid'][$key])){
					$item = OdOrderItem::findOne($item_tmp['itemid'][$key]);
					$OriginQty = $item->quantity; //修改前的数量 
				}else{
					$item = new OdOrderItem();
					$OriginQty = 0; //修改前的数量
				}
				$item->order_id = $order->order_id;
				$item->product_name = $item_tmp['product_name'][$key];
				$item->sku = $item_tmp['sku'][$key];
				$item->quantity = $item_tmp['quantity'][$key];
				$item->price = $item_tmp['price'][$key];
				$item->update_time = time();
				$item->create_time = is_null($item->create_time)?time():$item->create_time;
				if ($item->save()){
					if ($OriginQty != $item_tmp['quantity'][$key]){
						list($ack , $message , $code , $rootSKU ) = array_values(OrderGetDataHelper::getRootSKUByOrderItem($item));
							
						list($ack , $code , $message  )  = array_values( OrderBackgroundHelper::updateUnshippedQtyOMS($rootSKU, $order->default_warehouse_id, $order->default_warehouse_id, $OriginQty, $item_tmp['quantity'][$key]));
						if ($ack){
							$addtionLog .= "$rootSKU $OriginQty=>".$item_tmp['quantity'][$key];
						}
					}
				}
			}
			
			$order->checkorderstatus();
			$order->save();
			
			AppTrackerApiHelper::actionLog("Oms-lazada", "/order/lazada/edit-save");
// 			OperationLogHelper::log('order',$order->order_id,'修改订单','编辑订单进行订单修改',\Yii::$app->user->identity->getFullName());
			echo "<script language='javascript'>window.opener.location.reload();</script>";
			return $this->render('//successview',['title'=>'编辑订单']);
		}
		
		AppTrackerApiHelper::actionLog("Oms-lazada", "/order/lazada/edit-page");
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
		
		$selfLazadaOrderStatus = OdOrder::$status;
		unset($selfLazadaOrderStatus[100]);
		unset($selfLazadaOrderStatus[400]);
		
		return $this->render('edit',['order'=>$order,'carriers'=>$shipmethodList , 'warehouses'=>$warehouseList , 'selfLazadaOrderStatus'=>$selfLazadaOrderStatus]);
	}
	
	/**
	 * 订单列表页面进行批量平台标记发货,提供给用户填写物流号，运输服务等的页面
	 * @author dzt
	 */
	public function actionSignshipped(){
		AppTrackerApiHelper::actionLog("Oms-lazada", "/order/lazada/signshipped");
		$lazadaShippingMethod = LazadaApiHelper::getShippingCodeNameMap();
		if (\Yii::$app->request->getIsPost()){
			$orders = OdOrder::find()->where(['in','order_id',\Yii::$app->request->post()['order_id']])->all();
			return $this->render('signshipped',['orders'=>$orders,'lazadaShippingMethod'=>$lazadaShippingMethod]);
		}else {
			$orders = OdOrder::find()->where(['in','order_id',\Yii::$app->request->get('order_id')])->all();
			return $this->render('signshipped',['orders'=>$orders,'lazadaShippingMethod'=>$lazadaShippingMethod]);
		}
	}
	/**
	 * 更新lazada物流服务
	 */
	public function actionUpdateLazadaShipping(){
		$postarr = \Yii::$app->request->post();
		if(empty($postarr['order_id']))
			return ResultHelper::getResult(400, "", "请到指定订单标记发货页面更新该账号运输服务");
		
		$order = OdOrder::findOne($postarr['order_id']);
		if(empty($order))
			return ResultHelper::getResult(400, "", "请到指定订单不存在");
		
		AppTrackerApiHelper::actionLog("Oms-lazada", "/order/lazada/updatelazadashipping");
		
		$puid = \Yii::$app->user->identity->getParentUid();
		
		$countryCodeMap = array_flip(LazadaApiHelper::$COUNTRYCODE_COUNTRYCode2_MAP);
		return LazadaApiHelper::updateShipmentProviders($puid,"lazada","one",$order->selleruserid,$countryCodeMap[$order->order_source_site_id]);
	}
	/**
	 * 订单列表页面进行批量平台标记发货,插入标记队列
	 * @author dzt
	 */
	public function actionSignshippedsubmit(){		
		
		if (\Yii::$app->request->getIsPost()){			
			AppTrackerApiHelper::actionLog("Oms-lazada", "/order/lazada/signshippedsubmit");
			
			$user = \Yii::$app->user->identity;
			$postarr = \Yii::$app->request->post();
			$lazadaShippingMethod = LazadaApiHelper::getShippingCodeNameMap();
			if (count($postarr['order_id'])){
				foreach ($postarr['order_id'] as $oid){
					try {
						if(empty($postarr['shipmethod'][$oid])){
							return $this->render('//errorview',['title'=>'订单合并','error'=>"订单：$oid 请选择lazada平台对应的运输服务"]);
						}
							
						$shipping_method_code = $postarr['shipmethod'][$oid];
						$order = OdOrder::findOne($oid);
						$logisticInfoList=[
						'0'=>[
						'order_source'=>$order->order_source,
						'selleruserid'=>$order->selleruserid,
						'tracking_number'=>$postarr['tracknum'][$oid],
						'tracking_link'=>"http://www.17track.net",//dzt20150928 lazada 标记发货屏蔽了tracking link填写。为防止其他地方有用，所以这里hardcode了
						'shipping_method_code'=>$shipping_method_code,
						'shipping_method_name'=>$lazadaShippingMethod[$shipping_method_code],//平台物流服务名
						'order_source_order_id'=>$order->order_source_order_id,
						'description'=>'',//dzt20150928 lazada 标记发货屏蔽了发货备注，没用
						'signtype'=>"all",//dzt20150928 lazada 根据发货产品自己判断是否部分发货，所以这里的标记没用，mark个 all
						'addtype'=>'手动标记发货',
						]
						];
						if(!OrderHelper::saveTrackingNumber($oid, $logisticInfoList,0,1)){
							\Yii::error(["Order",__CLASS__,__FUNCTION__,"Online",'订单'.$oid.'插入失败'],'edb\global');
						}else{
							OperationLogHelper::log('order', $oid,'标记发货','手动批量标记发货',\Yii::$app->user->identity->getFullName());
						}
					}catch (\Exception $ex){
						\Yii::error(["Order",__CLASS__,__FUNCTION__,"Online","save to SignShipped failure:".print_r($ex->getMessage())],'edb\global');
					}
				}
				return $this->render('//successview',['title'=>'Lazada标记发货完成','message'=>'标记结果可查看Lazada状态']);
			}
		}
	}
	
	/**
	 * 订单合并
	 * @author dzt
	 */
	public function actionMergeorder(){
		if (\Yii::$app->request->isPost){
			AppTrackerApiHelper::actionLog("Oms-lazada", "/order/lazada/mergeorder");
			
			$orders = OdOrder::find()->where(['in','order_id',$_POST['order_id']])->all();
			$_tmporder = $orders[0];
			$ismerge = true;//判断选择的订单是否符合合并，买家名，地址等是否一样
			$error = '';
			if (count($orders)<2||count($orders)!=count($_POST['order_id'])){
				$ismerge = false;
				$error='合并订单数必须大于2,或传入的订单数ID与系统获取的订单数不符';
			}
			if ($ismerge){
				foreach ($orders as $order){
					if ($order->order_status != OdOrder::STATUS_PAY){
						$ismerge = false;
						$error='合并订单的状态必须为[已付款]';
						break;
					}
					if ($order->selleruserid != $_tmporder->selleruserid
					||$order->source_buyer_user_id != $_tmporder->source_buyer_user_id
					||$order->consignee != $_tmporder->consignee
					||$order->consignee_address_line1 != $_tmporder->consignee_address_line1){
						$ismerge = false;
						$error='合并订单的收件人等信息必须一致';
						break;
					}
					if ($order->order_source != $_tmporder->order_source||
					$order->currency != $_tmporder->currency){
						$ismerge = false;
						$error='合并订单的平台及币种必须一致';
						break;
					}
					// 由于系统的lazada账号是通过lazada用户邮箱+站点来确认唯一的，为了方便查找lazada账号，发货队列的记录记录了订单站点id。
					// 确保合并订单时，lazada不同站点订单不能合并，否则查找lazada账号会找错账号。
					if ($order->order_source_site_id != $_tmporder->order_source_site_id){
						$ismerge = false;
						$error='lazada合并订单的站点要一致';
						break;
					}
				}
			}
			if (!$ismerge){
				return $this->render('//errorview',['title'=>'订单合并','error'=>$error]);
			}else{
				//开始合并订单
				$droporderids = [];
				$shipping_cost = 0;
				$subtotal = 0;
				$grand_total=0;
				foreach ($orders as $order){
					foreach ($order->items as $item){
						//原订单的商品修正订单号
						$item->order_id = $_tmporder->order_id;
						$item->save();
					}
					$shipping_cost+=$order->shipping_cost;
					$subtotal+=$order->subtotal;
					$grand_total+=$order->grand_total;
					if ($order->order_id!=$_tmporder->order_id){
						array_push($droporderids,$order->order_id);
					}
				}
				$_tmporder->setAttributes([
						'shipping_cost'=>$shipping_cost,
						'subtotal'=>$subtotal,
						'grand_total'=>$grand_total
						]);
				$_tmporder->save();
				//删除旧订单数据
				foreach ($droporderids as $id){
					OdOrder::deleteAll('order_id = :oi',[':oi'=>$id]);
					OperationLogHelper::log('order', $id,'合并订单','删除该订单,合并到订单'.$_tmporder->order_id,\Yii::$app->user->identity->getFullName());
					OperationLogHelper::log('order', $_tmporder->order_id,'合并订单','合并原订单'.$id.'到该订单',\Yii::$app->user->identity->getFullName());
				}
				echo "<script language='javascript'>alert('Success');window.opener.location.reload();window.close();</script>";
				//return $this->render('//successview',['title'=>'合并已付款订单']);
			}
		}
	}
	
	//更新lazada proxy的图片缓存
	// /order/lazada-order/update-image
	public function actionUpdateImage(){
		AppTrackerApiHelper::actionLog("Oms-lazada", "/order/lazada/updateimage");
		if(!isset($_POST['order_id'])){
			return ResultHelper::getResult(400, "", "异常操作！");
		}
		
		$order = OdOrder::findOne($_POST['order_id']);
		if(empty($order)){
			return ResultHelper::getResult(400, "", "订单不存在！");
		}
		
		$countryCodeMap = array_flip(LazadaApiHelper::$COUNTRYCODE_COUNTRYCode2_MAP);
		$lazadaUsers = SaasLazadaUser::find()->where(['platform_userid'=>$order->selleruserid,'lazada_site'=>$countryCodeMap[$order->order_source_site_id]])->andWhere('status <> 3')->one();
		
		// dzt20190426 cb支持
		if (empty($SLU)) {
		    $SLU = SaasLazadaUser::findOne(['platform_userid' => $order->selleruserid, 'lazada_site' => 'cb' ,'status'=>1]);
			if(!empty($SLU)){
                $lazadaSites = json_decode($SLU->country_user_info, true);
                $lazadaSitesMap = Helper_Array::toHashmap($lazadaSites, 'country');
                if(empty($lazadaSitesMap[strtolower($order->order_source_site_id)]))
                    $SLU = null;
            }
		}
		if(empty($lazadaUsers)){
			return ResultHelper::getResult(400, "", "订单所属账号不存在！");
		}
		
		$config = array(
			"userId"=>$lazadaUsers->platform_userid,
			"apiKey"=>$lazadaUsers->token,
			"countryCode"=>strtolower($order->order_source_site_id),
		);
		$msg = array();
		foreach ($order->items as $item){
			$response = LazadaInterface_Helper::getOrderItemImage($config,array('ShopSku'=>$item->order_source_itemid,'SellerSku'=>$item->sku,'purge'=>true));
			if($response['success']){
				if(isset($response['response']['SmallImageUrl'])&&isset($response['response']['ProductUrl'])){
				    $update = array();
				    if(!empty($response['response']['SmallImageUrl'])){
				        $update['photo_primary'] = $response['response']['SmallImageUrl'];
				    }
				    
				    if(!empty($response['response']['ProductUrl'])){
				        $update['product_url'] = $response['response']['ProductUrl'];
				    }
				    
				    if(empty($update))
				        return ResultHelper::getResult(400, "", "更新失败，获取不到要更新的数据");
				    
					$db = OdOrderItem::getDb();
					try{
						$r = $db->createCommand()->update(OdOrderItem::tableName(),
								$update,
								['order_source_itemid'=>$item->order_source_itemid]
						)->execute();
					}catch (\Exception $e){
						$msg[] = 'order_id:'.$order->order_id.' ,order_item_id:'.$item->order_source_itemid.' 保存到数据库时失败';
					}
				}else{
					$msg[] = 'order_id:'.$order->order_id.' ,order_item_id:'.$item->order_source_itemid.' call API error.';
				}
			}else{
				$msg[] = 'order_id:'.$order->order_id.' ,order_item_id:'.$item->order_source_itemid.' has error .'.$response['response']['message'];
			}
		}
		
		if(!empty($msg)){
			return ResultHelper::getResult(400, "", implode(';', $msg));
		}else{
			return ResultHelper::getResult(200, "", "更新成功");
		}
	}
	
	// 获取lazada 发票
	// /order/lazada-order/order-invoice
	public function actionOrderInvoice(){
		AppTrackerApiHelper::actionLog("Oms-lazada", "/order/lazada/orderinvoice");
		if(!isset($_GET['order_id'])){
			return $this->render('//errorview',['title'=>'发票','error'=>'异常操作！']);
		}
		
		$order = OdOrder::findOne($_GET['order_id']);
		if (empty($order)){
			return $this->render('//errorview',['title'=>'发票','error'=> $_GET['order_id']."是无效订单！"]);
		}
		
		$code2CodeMap = array_flip(LazadaApiHelper::$COUNTRYCODE_COUNTRYCode2_MAP);
		if (empty($code2CodeMap[$order->order_source_site_id]))
			return $this->render('//errorview',['title'=>'发票','error'=>"站点" . $order->order_source_site_id . "不是 lazada的站点。"]);
		$SLU = SaasLazadaUser::findOne(['platform_userid' => $order->selleruserid, 'lazada_site' => $code2CodeMap[$order->order_source_site_id]]);
		
		if (empty($SLU)) {
			return $this->render('//errorview',['title'=>'发票','error'=>$order->selleruserid . "站点:" . LazadaApiHelper::$COUNTRYCODE_NAME_MAP_CARRIER[$order->order_source_site_id] . " 账号不存在"]);
		}
		
		$OrderItemIds = array();
		foreach ($order->items as $item) {
			$OrderItemIds[] = $item->order_source_order_item_id;
		}
		
		$config = array(
			"userId" => $SLU->platform_userid,
			"apiKey" => $SLU->token,
			"countryCode" => $SLU->lazada_site
		);
		
		$appParams = array(
			'OrderItemIds' => implode(',', $OrderItemIds), // 订单下的多个 OrderItemIds。格式为：OrderItemIds1,OrderItemIds2的字符串发送到proxy
		);
		
		\Yii::info("actionOrderInvoice getOrderInvoice ready to proxy.config:" . json_encode($config). ",appParams:" . json_encode($appParams), "file");
		$result = LazadaInterface_Helper::getOrderInvoice($config, $appParams);
		\Yii::info("actionOrderInvoice getOrderInvoice return from proxy.result:" . json_encode($result), "file");
		
		if ($result['success'] && $result['response']['success'] == true) { // 成功
			$text = base64_decode($result["response"]["body"]["Body"]["Documents"]["Document"]["File"]);
			$result = Helper_Curl::post("http://120.55.86.71/wkhtmltopdf.php",['html'=>$text,'uid'=>$SLU->puid,'pringType'=>"A4",'returnFileType'=>'invoice']);// 打A4还是热敏纸
			if(false !== $result){
				$rtn = json_decode($result,true);
				if(1 == $rtn['success']){
					$response = Helper_Curl::get($rtn['url']);
					$pdfUrl = \eagle\modules\carrier\helpers\CarrierAPIHelper::savePDF($response, $SLU->puid, md5("wkhtmltopdf")."_".time());
					$this->redirect($pdfUrl);
				}else{
					return $this->render('//errorview',['title'=>'发票','error'=> "打印出错，请联系小老板客服。"]);
				}
			}else{
				return $this->render('//errorview',['title'=>'发票','error'=> "请重试，如果再有问题请联系小老板客服。"]);
			}
		} else {
			return $this->render('//errorview',['title'=>'发票','error'=> "获取发票失败：".$result['message']]);
		}
	}
	
	//查找有问题的图片url
	public static function updateLazadaImg(){
		$tmp_order_ids = OdOrderItem::find()->select('order_id')
			->where(['photo_primary'=>'//sg.s.alibaba.lzd.co/original/e0ec5f455dab26f82ed300a5c11d9651.jpg'])->groupBy('order_id')->asArray()
			->all();
		
		$order_ids = array();
		
		if(count($tmp_order_ids) > 0){
			foreach ($tmp_order_ids as $order_id){
				$order_ids[] = $order_id['order_id'];
			}
		}
		
		$countryCodeMap = array_flip(LazadaApiHelper::$COUNTRYCODE_COUNTRYCode2_MAP);
		$lazadaConfig = array();
		
		
		if(count($order_ids) > 0){
			$orders_all = OdOrder::find()->where(['order_id'=>$order_ids])->orderBy('selleruserid,order_source_site_id')->all();
			
			if(!empty($orders_all)){
				foreach($orders_all as $order){
					if(!isset($lazadaConfig[$order->selleruserid.$order->order_source_site_id])){
						$lazadaUsers = SaasLazadaUser::find()->where(['platform_userid'=>$order->selleruserid,'lazada_site'=>$countryCodeMap[$order->order_source_site_id]])->andWhere('status <> 3')->one();
						if(empty($lazadaUsers)){
							continue;
						}
						
						$lazadaConfig[$order->selleruserid.$order->order_source_site_id] = array("userId"=>$lazadaUsers->platform_userid,"apiKey"=>$lazadaUsers->token,"countryCode"=>$lazadaUsers->lazada_site);
					}
					
					unset($config);
					$config = array(
							"userId"=>$lazadaConfig[$order->selleruserid.$order->order_source_site_id]['userId'],
							"apiKey"=>$lazadaConfig[$order->selleruserid.$order->order_source_site_id]['apiKey'],
							"countryCode"=>$lazadaConfig[$order->selleruserid.$order->order_source_site_id]['countryCode']
					);
					
					unset($msg);
					$msg = array();
					foreach ($order->items as $item){
						$response = LazadaInterface_Helper::getOrderItemImage($config,array('ShopSku'=>$item->order_source_itemid,'SellerSku'=>$item->sku,'purge'=>true));
						if($response['success']){
							if(isset($response['response']['SmallImageUrl'])&&isset($response['response']['ProductUrl'])){
								$update = array();
								if(!empty($response['response']['SmallImageUrl'])){
									$update['photo_primary'] = $response['response']['SmallImageUrl'];
								}
					
								if(!empty($response['response']['ProductUrl'])){
									$update['product_url'] = $response['response']['ProductUrl'];
								}
					
								if(empty($update)){
									continue;
								}
					
								$db = OdOrderItem::getDb();
								try{
									$r = $db->createCommand()->update(OdOrderItem::tableName(),
											$update,
											['order_source_itemid'=>$item->order_source_itemid]
									)->execute();
								}catch (\Exception $e){
// 									$msg[] = 'order_id:'.$order->order_id.' ,order_item_id:'.$item->order_source_itemid.' 保存到数据库时失败';
								}
							}else{
// 								$msg[] = 'order_id:'.$order->order_id.' ,order_item_id:'.$item->order_source_itemid.' call API error.';
							}
						}else{
// 							$msg[] = 'order_id:'.$order->order_id.' ,order_item_id:'.$item->order_source_itemid.' has error .'.$response['response']['message'];
						}
					}
				}
			}
		}
		
	}
}



?>