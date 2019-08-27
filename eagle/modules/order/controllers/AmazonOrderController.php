<?php

namespace eagle\modules\order\controllers;

use yii\data\Pagination;
use yii\web\Controller;
use eagle\modules\order\models\OdOrder;
use eagle\modules\order\models\Excelmodel;
use common\helpers\Helper_Array;
use eagle\modules\order\models\OdOrderItem;
use eagle\modules\util\helpers\OperationLogHelper;
use eagle\modules\order\models\Usertab;
use Exception;
use eagle\modules\order\models\OdOrderShipped;
use eagle\models\carrier\SysShippingService;
use eagle\models\SaasAmazonUser;
use yii\data\Sort;
use eagle\models\sys\SysCountry;
use eagle\modules\inventory\models\Warehouse;
use eagle\modules\inventory\helpers\InventoryApiHelper;
use eagle\modules\order\helpers\OrderTagHelper;
use eagle\modules\amazon\apihelpers\AmazonApiHelper;
use eagle\modules\order\helpers\OrderHelper;
use eagle\modules\app\apihelpers\AppTrackerApiHelper;
use eagle\modules\util\helpers\StandardConst;
use eagle\modules\util\helpers\ResultHelper;
use eagle\modules\order\helpers\AmazonOrderHelper;
use eagle\modules\order\helpers\OrderBackgroundHelper;
use eagle\modules\inventory\helpers\WarehouseHelper;
use eagle\modules\platform\apihelpers\AmazonAccountsApiHelper;
use eagle\modules\util\helpers\DataStaticHelper;
use eagle\modules\order\apihelpers\OrderApiHelper;
use eagle\modules\order\helpers\OrderGetDataHelper;
use eagle\modules\order\helpers\OrderUpdateHelper;
use eagle\modules\util\helpers\ConfigHelper;
use eagle\modules\util\helpers\SysLogHelper;
use eagle\modules\util\helpers\RedisHelper;
use eagle\modules\platform\apihelpers\PlatformAccountApi;


class AmazonOrderController extends \eagle\components\Controller{
	public $enableCsrfValidation = false;
	/**
	 * [actionList amazon订单列表页面]
	 * @Author   willage
	 * @DateTime 2016-07-04T10:06:22+0800
	 * @return   [type]                   [description]
	 */
	public function actionList(){
		global $hitCache;
		//check模块权限
		$permission = \eagle\modules\permission\apihelpers\UserApiHelper::checkPlatformPermission('amazon');
		if(!$permission)
			return $this->render('//errorview_no_close',['title'=>'访问受限:没有权限','error'=>'您还没有订单模块的权限!']);
 	
		$puid = \Yii::$app->subdb->getCurrentPuid();
		$current_time=explode(" ",microtime()); $time1=round($current_time[0]*1000+$current_time[1]*1000);
		 
		AppTrackerApiHelper::actionLog("Oms-amazon", "/order/amazon/list");
		// $data = OdOrder::find()->where(['order_source' => 'amazon' ]);
		$data = OdOrder::find();
		
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
		
		$showsearch=0;
		$op_code = '';
		
		$puid = \Yii::$app->user->identity->getParentUid();
		$isParent = \eagle\modules\permission\apihelpers\UserApiHelper::isMainAccount();
		$addi_condition = ['order_source'=>'amazon'];
		//过滤解绑账号订单
		//不显示 解绑的账号的订单 start
		//$tmpSellerIDList =  AmazonAccountsApiHelper::listActiveAccounts(\Yii::$app->user->identity->getParentUid());//添加账号权限前	lzhl 2017-03
		$tmpSellerIDList = PlatformAccountApi::getPlatformAuthorizeAccounts('amazon');//添加账号权限	lzhl 2017-03
		
		$amzUsersDropdownList = [];
		$amzStoreDropdownList = [];
		foreach($tmpSellerIDList as $merchant=>$store_name){
			$amzUsersDropdownList[] = $merchant;
			$amzStoreDropdownList[$merchant] = $store_name;

		}
		if (!empty($amzUsersDropdownList)){
			//不显示 解绑的账号的订单
			// $data->andWhere(['selleruserid'=>$amzUsersDropdownList]);
			$addi_condition['selleruserid'] = $amzUsersDropdownList;
		}else{
			//如果为测试用的账号就不受平台绑定限制
			$test_userid=\eagle\modules\tool\helpers\MirroringHelper::$test_userid;
			if (!in_array($puid,$test_userid['yifeng'])){
				return $this->render('//errorview_no_close',['title'=>'访问受限:没有权限','error'=>'您还没有获得任何 Amazon 账号管理权限!']);
			}
		}
		//不显示 解绑的账号的订单 end

		
		//查询$_REQUEST要求的订单
		//list($datatmp,$showsearch,$op_code,$orderstr) = AmazonOrderHelper::orderListDataSearch($_REQUEST,$data);

		if (!empty($_REQUEST['order_type'])){
			//搜索amazon特有MFN/AFN
			$data->andWhere('`order_type` = :ot',[':ot'=>$_REQUEST['order_type']]);
			$showsearch=1;
		}

		if (!empty($_REQUEST['selleruserid'])){
			//搜索卖家账号
			$data->andWhere('selleruserid = :s',[':s'=>$_REQUEST['selleruserid']]);
		}

		if (!empty($_REQUEST['amzStoreDropdownList'])){
			//搜索卖家账号
			$data->andWhere('selleruserid = :merchant_id',[':merchant_id'=>$_REQUEST['amzStoreDropdownList']]);
		}
		
		if (!empty($_REQUEST['order_source_shipping_method'])){
			//搜索客选物流
			$data->andWhere(['order_source_shipping_method' => $_REQUEST['order_source_shipping_method']]);
		}

		if (!empty($_REQUEST['country'])){
			$data->andWhere(['consignee_country_code'=>explode(',', $_REQUEST['country'])]);
			$showsearch=1;
		}
		$tmp_REQUEST_text['where']=empty($data->where)?Array():$data->where;
		$tmp_REQUEST_text['orderBy']=empty($data->orderBy)?Array():$data->orderBy;
		/**
		 * [OrderApiHelper::getOrderListByConditionOMS 统一OMS接口]
		 */
		$addi_condition = ['order_source'=>'amazon'];
		$addi_condition['sys_uid'] = \Yii::$app->user->id;
		$addi_condition['selleruserid_tmp'] = $amzStoreDropdownList;
		$omsRT = OrderApiHelper::getOrderListByConditionOMS($_REQUEST,$addi_condition,$data,$pageSize,false,'all');

		if (!empty($_REQUEST['order_status'])){
			//生成操作下拉菜单的code
			$op_code = $_REQUEST['order_status'];
		}
		if (!empty($omsRT['showsearch'])) $showsearch = 1;
		/**end**/

	    /**
	     * [$tmpCommand 调试sql]
	     * @var [type]
	     */
	     //$tmpCommand = $data->createCommand();
	    //echo "<br>".$tmpCommand->getRawSql();


		//$data->orderBy($orderstr)->with('items');
		

		$pages = new Pagination([
				'defaultPageSize' => 20,
				'pageSize' => $pageSize,
				'totalCount' => $data->count(),
				'pageSizeLimit'=>[5,200],//每页显示条数范围
				'params'=>$_REQUEST,
				]);
		/**
		 * [$models description]
		 * @var [type]
		 */
		$models = $data->offset($pages->offset)
			->limit($pages->limit)
			->all();

		//save the redis cache for next time use
		/*
		$cachedArrAll[$subKey]['pages'] = serialize($pages);
		$cachedArrAll[$subKey]['models'] = serialize($models);
		$cachedArrAll[$subKey]['data'] = serialize($data);
		RedisHelper::setOrderCache($puid,'amazon',"PagesModels",json_encode($cachedArrAll)) ;
		*/
	
		//yzq 2017-2-21, to do bulk loading the order items, not to use lazy load
		OrderHelper::bulkLoadOrderItemsToOrderModel($models);
		OrderHelper::bulkLoadOrderShippedModel($models);
		    
	    /**
	     * [$excelmodel description]
	     * @var Excelmodel
	     */
	    $excelmodel	=	new Excelmodel();
	    $model_sys	=	$excelmodel->find()->all();
	    $excelmodels=array(''=>'导出订单');
	    if(isset($model_sys)&&!empty($model_sys)){
	    	foreach ($model_sys as $m){
	    		$excelmodels[$m->id]=$m->name;
	    	}
	    }

	    /**
		 * [$counter 订单数量统计]
		 * @var array
		 */
	    $hitCache = "NoHit";
	    $cachedArr = array();
	    $uid = \Yii::$app->user->id;
	    $stroe = 'all';
	    if(!empty($_REQUEST['selleruserid']))
	    	$stroe  = trim($_REQUEST['selleruserid']);
	     
	    if($isParent){
	    	$gotCache = RedisHelper::getOrderCache2($puid,$uid,'amazon',"MenuStatisticData",$stroe) ;
	    }else{
	    	if (!empty($_REQUEST['selleruserid'])){
	    		$gotCache = RedisHelper::getOrderCache2($puid,$uid,'amazon',"MenuStatisticData",$_REQUEST['selleruserid']) ;
	    	}else{
	    		$gotCache = RedisHelper::getOrderCache2($puid,$uid,'amazon',"MenuStatisticData",'all') ;
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
	    		$counter = AmazonOrderHelper::getMenuStatisticData(['selleruserid'=>$_REQUEST['selleruserid']]);
	    	}else{
	    		if(!empty($amzUsersDropdownList)){
	    			$counter = AmazonOrderHelper::getMenuStatisticData(['selleruserid'=>$amzUsersDropdownList]);
	    		}else{
	    			//无有效绑定账号
	    			$counter=[];
	    			$claimOrderIDs=[];
	    		}
	    	}
	    	//save the redis cache for next time use
	    	if (!empty($_REQUEST['selleruserid'])){
	    		RedisHelper::setOrderCache2($puid,$uid,'amazon',"MenuStatisticData",$_REQUEST['selleruserid'],$counter) ;
	    	}else{
	    		RedisHelper::setOrderCache2($puid,$uid,'amazon',"MenuStatisticData",'all',$counter) ;
	    	}
	    }
	    
	    /**
	     * [$warehouseids 获取仓库列表]
	     * @var [type]
	     */
		//$warehouseids = InventoryApiHelper::getWarehouseIdNameMap();

		/**
		 * [$selleruserids amazon的selleruserids对应叫merchant_id]
		 * @var [type]
		 */
	    $selleruserids=Helper_Array::toHashmap(SaasAmazonUser::find()->where(['uid'=>\Yii::$app->user->identity->getParentUid()])->select(['merchant_id'])->asArray()->all(),'merchant_id','merchant_id');
	    
	    /**
	     * [$search description]
	     * @var array
	     */
		$search = array('is_comment_status'=>'等待您留评');

		/**
		 * [$allTagDataList tag 数据获取]
		 * @var [type]
		 */
	    $allTagDataList = OrderTagHelper::getTagByTagID();
	    $allTagList = [];
	    foreach($allTagDataList as $tmpTag){
	    	$allTagList[$tmpTag['tag_id']] = $tmpTag['tag_name'];
	    }

	    /**
	     * [$order_nav_key_word 订单导航]
	     * @var [type]
	     */
		if (!empty($_REQUEST['order_status']))
			$order_nav_key_word = $_REQUEST['order_status'];
		else
			$order_nav_key_word='';

	    /**
	     * [$regions description]
	     * [$countrys 国家]
	     * @var [type]
	     */
		//use this function to performance tuning, it use redis
// 		$countryArr = OrderHelper::getCountryAndRegion();

		
		/**
		 * [$countryArr 获取国家列表，这里添加对应状态下面的国家筛选]
		 * @var array
		 */
		//use this function to performance tuning, it use redis
		if (isset($_REQUEST['order_status'])){
			$redis_order_status = $_REQUEST['order_status'];
		}else{
			$redis_order_status = '';
		}
	 	$countrys = OrderHelper::getPlatformOrderCountries($puid , 'amazon',$amzUsersDropdownList ,$redis_order_status);
	 	
	    
		/**
		 * [$DashBoardCache 获取dash board cache 数据]
		 * @var [type]
		 */
		//$uid = \Yii::$app->user->identity->getParentUid();
		//$DashBoardCache = AmazonOrderHelper::getOmsDashBoardCache($uid);

		/**
		 * [$existProductResult 检查报关信息是否存在 start]
		 * @var [type]
		 */
		//$OrderIdList = [];
		
	    
		$existProductResult = OrderBackgroundHelper::getExistProductRuslt($models);

		$tmp_REQUEST_text['REQUEST']=$_REQUEST;
		$tmp_REQUEST_text['order_source']=$addi_condition;
		$tmp_REQUEST_text['params']=empty($data->params)?Array():$data->params;
		$tmp_REQUEST_text=base64_encode(json_encode($tmp_REQUEST_text));
		
		//$op_code = OdOrder::STATUS_PAY;
		return $this->render('list',array(
			'models' => $models,
		    //'pagination' => $pagination,
			//'sort' => $sortConfig,
			'excelmodels'=>$excelmodels,
			'amzStoreDropdownList'=>$amzStoreDropdownList,
			'counter'=>$counter,
// 			'countryArr'=>$countryArr,
			'showsearch'=>$showsearch,
			'tag_class_list'=> OrderTagHelper::getTagColorMapping(),
			//'doarr'=>OrderHelper::getCurrentOperationList($op_code,'b') ,
			//'doarr_one'=>OrderHelper::getCurrentOperationList($op_code,'s'),
			'doarr'=>AmazonOrderHelper::getAmazonCurrentOperationList($op_code,'b') ,
			'doarr_one'=>AmazonOrderHelper::getAmazonCurrentOperationList($op_code,'s'),
			//
			'existProductResult'=>$existProductResult,
			//
			'pages' => $pages,
			//
			//'warehouseids'=>$warehouseids,
			//
			'selleruserids'=>$selleruserids,
			//
			'countrys'=>$countrys,
			//
			'all_tag_list'=>$allTagList,
			//
			//'region'=>WarehouseHelper::countryRegionChName(),
			//
			'search'=>$search,
			//
			//'DashBoardCache'=>$DashBoardCache,
			'search_condition'=>$tmp_REQUEST_text,
			'search_count'=>$pages->totalCount,
		));
	}//end actionList

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
			if (!empty($_tmp['default_shipping_method_code'])){
				$serviceid = SysShippingService::findOne($_tmp['default_shipping_method_code']);
				if (!empty($serviceid)||!$serviceid->isNewRecord){
					$_tmp['default_shipping_method_code']=$_tmp['default_shipping_method_code'];
					$_tmp['default_carrier_code']=$serviceid->carrier_code;
				}
			}
			// $order->setAttributes($_tmp);
			// $order->save();
			$action = '修改订单';
    		$module = 'order';
    		$fullName = \Yii::$app->user->identity->getFullName();
    		$rt = OrderUpdateHelper::updateOrder($order, $_tmp , false , $fullName , $action , $module);
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
				//$item->ordered_quantity = $item_tmp['ordered_quantity'][$key];
				$item->quantity = $item_tmp['quantity'][$key];
				$item->price = $item_tmp['price'][$key];
				$item->update_time = time();
				$item->create_time = is_null($item->create_time)?time():$item->create_time;
				// $item->save();
				$addtionLog = '';
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

			AppTrackerApiHelper::actionLog("Oms-amazon", "/order/amazon/edit-save");
			OperationLogHelper::log('order',$order->order_id,'修改订单','编辑订单进行订单修改',\Yii::$app->user->identity->getFullName());
			if(empty($_GET['is_delivery'])){
				echo "<script language='javascript'>window.opener.location.reload();</script>";
// 				echo "<script language='javascript'>window.opener.location.reload();window.close();</script>";
			}else{
				echo "<script language='javascript'>window.opener.deliveryImplantOmsPublic();window.close();</script>";
			}
			return $this->render('//successview',['title'=>'编辑订单']);
		}

		AppTrackerApiHelper::actionLog("Oms-amazon", "/order/amazon/edit-page");
		if (!isset($_GET['orderid'])){
			return $this->render('//errorview',['title'=>'编辑订单','error'=>'链接有误']);
		}
		$order = OdOrder::findOne($_GET['orderid']);
		if (empty($order)||$order->isNewRecord){
			return $this->render('//errorview',['title'=>'编辑订单','error'=>'未找到相应订单']);
		}
		return $this->render('edit',['order'=>$order,'countrys'=>StandardConst::$COUNTRIES_CODE_NAME_EN]);
	}
	/**
	 * 订单列表页面进行批量平台标记发货,提供给用户填写物流号，运输服务等的页面
	 * @author dzt
	 */
	public function actionSignshipped(){
		AppTrackerApiHelper::actionLog("Oms-amazon", "/order/amazon/signshipped");
		
		$tmpShippingMethod = AmazonApiHelper::getShippingCodeNameMap();
		$tmpShippingMethod = DataStaticHelper::getUseCountTopValuesFor("AmazonOms_ShippingMethod" ,$tmpShippingMethod );
		
		$amazonShippingMethod = [];
		if(!empty($tmpShippingMethod['recommended'])){
			$amazonShippingMethod += $tmpShippingMethod['recommended'];
			$amazonShippingMethod[''] = '---常用/非常用 分割线---';
		}
		if(!empty($tmpShippingMethod['rest']))
			$amazonShippingMethod += $tmpShippingMethod['rest'];
		
		if (\Yii::$app->request->getIsPost()){
			$orders = OdOrder::find()->where(['in','order_id',\Yii::$app->request->post()['order_id']])->all();
		}else {
			$orders = OdOrder::find()->where(['in','order_id',\Yii::$app->request->get('order_id')])->all();
		}
		return $this->render('signshipped',['orders'=>$orders,'amazonShippingMethod'=>$amazonShippingMethod]);
	}
	
	/**
	 * 订单列表页面进行批量平台标记发货,插入标记队列
	 * @author dzt
	 */
	public function actionSignshippedsubmit(){
		
		if (\Yii::$app->request->getIsPost()){
			AppTrackerApiHelper::actionLog("Oms-amazon", "/order/amazon/signshippedsubmit");
				
			$user = \Yii::$app->user->identity;
			$postarr = \Yii::$app->request->post();
			$amazonShippingMethod = AmazonApiHelper::getShippingCodeNameMap();
			if (count($postarr['order_id'])){
				foreach ($postarr['order_id'] as $oid){
					try {
						$shipping_method_code = strlen($postarr['shipmethod'][$oid])>0?$postarr['shipmethod'][$oid]:'Other';
						$order = OdOrder::findOne($oid);
						$logisticInfoList=[
						'0'=>[
						'order_source'=>$order->order_source,
						'selleruserid'=>$order->selleruserid,
						'tracking_number'=>$postarr['tracknum'][$oid],
						'tracking_link'=>"http://www.17track.net",//dzt20150928 amazon 标记发货屏蔽了tracking link填写。为防止其他地方有用，所以这里hardcode了
						'shipping_method_code'=>$shipping_method_code,
						'shipping_method_name'=>isset($amazonShippingMethod[$shipping_method_code])?$amazonShippingMethod[$shipping_method_code]:$shipping_method_code,//平台物流服务名
						'order_source_order_id'=>$order->order_source_order_id,
						'description'=>'',//dzt20150928 amazon 标记发货屏蔽了发货备注，没用
						'signtype'=>"all",//dzt20150928 amazon 根据发货产品自己判断是否部分发货，所以这里的标记没用，mark个 all
						'addtype'=>'手动标记发货',
						]
						];
						if(!OrderHelper::saveTrackingNumber($oid, $logisticInfoList,0,1)){
							\Yii::error(["Order",__CLASS__,__FUNCTION__,"Online",'订单'.$oid.'插入失败'],'edb\global');
						}else{
							OperationLogHelper::log('order', $oid,'标记发货','手动批量标记发货',\Yii::$app->user->identity->getFullName());
							//标记成功后记录物流服务使用频率	lzhl 2016-08-01
							DataStaticHelper::addUseCountFor("AmazonOms_ShippingMethod", $shipping_method_code,8);
						}
					}catch (\Exception $ex){
						\Yii::error(["Order",__CLASS__,__FUNCTION__,"Online","save to SignShipped failure:".print_r($ex->getMessage())],'edb\global');
					}
				}
				return $this->render('//successview',['title'=>'Amazon标记发货完成','message'=>'标记结果可查看Amazon状态']);
			}
		}
	}

	/**
	 * [actionUpdateImage 更新amazon proxy的图片缓存]
	 * @Author   willage
	 * @DateTime 2016-07-12T15:26:56+0800
	 * @return   [type]                   [description]
	 */
	public function actionUpdateImage(){
		if(!isset($_POST['order_id'])){
			return ResultHelper::getFailed('',1,'异常操作！');
		}
		$res = AmazonApiHelper::updateOrderItemImage($_POST['order_id']);
		return $res;
	}

/**
 * [actionOrderSyncInfo 用于更新同步订单状态]
 * @Author   willage
 * @DateTime 2016-06-27T17:31:48+0800
 * @return   [order_sync]     [ 'sync_list'=>$detail,
 *                              'order_nav_html'=>,
 *                              'counter'=>,
 *                              'selleruserids'=>,]
 */
	public function actionOrderSyncInfo(){
		//判断参数
		if (isset($_REQUEST['sync_status'])){
			$status = $_REQUEST['sync_status'];
		}else{
			$status = "";
		}
		if (isset($_REQUEST['last_sync_time'])){
			$last_sync_time = $_REQUEST['last_sync_time'];
		}else{
			$last_sync_time = "";
		}
		//获取所有国家编码对应国家中文名称
	    $sysCountry = [];
	    $countryModels = SysCountry::find()->asArray()->all();
		foreach ($countryModels as $countryModel){
			$sysCountry[$countryModel['country_code']] = ['country_zh'=>$countryModel['country_zh'],'country_en'=>$countryModel['country_en']];
		}
		//获取订单同步信息
		$detail = AmazonOrderHelper::getOrderSyncInfoDataList($status,$last_sync_time );
		if (!empty($_REQUEST['order_status']))
			$order_nav_key_word = $_REQUEST['order_status'];
		else
			$order_nav_key_word='';

		//获取sellerid（）
		$selleruserids=Helper_Array::toHashmap(SaasAmazonUser::find()->where(['uid'=>\Yii::$app->user->identity->getParentUid()])->select(['merchant_id'])->asArray()->all(),'merchant_id','merchant_id');
		$merchStoreMap=Helper_Array::toHashmap(SaasAmazonUser::find()->where(['uid'=>\Yii::$app->user->identity->getParentUid()])->select(['merchant_id','store_name'])->asArray()->all(),'merchant_id','store_name');

		$counter = AmazonOrderHelper::getMenuStatisticData(['selleruserid'=>$selleruserids]);
		return $this->render('order_sync',['sync_list'=>$detail,
				'order_nav_html'=>AmazonOrderHelper::getAmazonOmsNav($order_nav_key_word),
				'counter'=>$counter,
				'selleruserids'=>$selleruserids,
				'sysCountry'=>$sysCountry,
				'merchStoreMap'=>$merchStoreMap,
				]);
	}//end actionOrderSyncInfo
/**
 * [actionAjaxdesc 订单商品添加备注]
 * @Author   willage
 * @DateTime 2016-07-12T16:03:53+0800
 * @return   [type]                   [description]
 */
	public function actionAjaxdesc(){
		if(\Yii::$app->request->isPost){
			$order = OdOrder::findOne($_POST['oiid']);
			if (!empty($order)){
				$rt = OrderHelper::addOrderDescByModel($order,  $_POST['desc'], 'order', '添加备注');
				/*
				$olddesc = $order->desc;
				$order->desc = $_POST['desc'];
				$order->save();
				OperationLogHelper::log('order',$order->order_id,'添加备注','修改备注: ('.$olddesc.'->'.$_POST['desc'] .')',\Yii::$app->user->identity->getFullName());
				
				*/
				$ret_array = array (
						'result' => true,
						'message' => '修改成功'
				);
				echo json_encode ( $ret_array );
				exit();
			}
		}
	}//end acitonAjaxdesc
/**
 * [actionCheckorderstatus description]
 * @Author   willage
 * @DateTime 2016-07-12T17:56:26+0800
 * @return   [type]                   [description]
 */
	public function actionCheckorderstatus(){
		if (\Yii::$app->request->isPost){
			$orderids = explode(',',$_POST['orders']);
			Helper_Array::removeEmpty($orderids);
			if (count($orderids)>0){
				try {
					foreach ($orderids as $orderid){
						$order = OdOrder::findOne($orderid);
						if ($order->order_status=='200'){
							
							if (!empty($_REQUEST['refresh_force']) && $_REQUEST['refresh_force'] =='true'){
								$isreset = 1;
							}else{
								$isreset = 0;
							}
							$order->checkorderstatus(NULL,$isreset);
							$order->save(false);
						}
					}
					return '操作已完成';
				}catch (\Exception $e){
					return $e->getMessage();
				}
			}else{
				return '选择的订单有问题';
			}
		}
	}//end actionCheckorderstatus


/**
 * [actionRefreshorder 删除重新同步订单]
 * @Author   willage
 * @DateTime 2016-07-14T15:56:40+0800
 * @return   [type]                   [description]
 */
	public function actionRefreshorder(){
		if (\Yii::$app->request->isPost){
			$orderids = $_POST['orders'];
			Helper_Array::removeEmpty($orderids);
			$uid = \Yii::$app->user->identity->getParentUid();
			$error_message = "";
			if (count($orderids)>0){
				try {
					foreach ($orderids as $order_id){
						$tmpRT = OrderHelper::requestRefreshOrderQueue($uid,  $order_id, 'amazon');
						if ($tmpRT['success']==false){
							//插入队列失败
							$error_message .= $order_id.':'.$tmpRT['message'];
						}else{
							//同步成功删除订单数据
							OdOrderItem::deleteAll(['order_id'=>$order_id]);
							OdOrder::deleteAll(['order_id'=>$order_id]);
						}
					}
					if (!empty($error_message)) return $error_message;
					return '操作已完成,请等待同步';
				}catch (\Exception $e){
					\Yii::info("\n".(__FUNCTION__)." $uid refreshorder E:".print_r($e->getMessage(),true),"file");
					return $e->getMessage();
				}
			}else{
				return '选择的订单有问题';
			}
		}//end of post
	}//end actionRefreshorder

}//end class



?>