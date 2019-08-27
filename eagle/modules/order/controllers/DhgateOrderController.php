<?php

namespace eagle\modules\order\controllers;

use yii\data\Pagination;
use eagle\modules\order\models\OdOrder;
use eagle\modules\order\models\Excelmodel;

use common\helpers\Helper_Array;
use eagle\modules\order\models\OdOrderItem;
use eagle\modules\util\helpers\OperationLogHelper;
use eagle\modules\order\helpers\OrderHelper;
use eagle\modules\order\models\Usertab;
use eagle\modules\order\models\OdOrderShipped;
use eagle\models\carrier\SysShippingService;
use eagle\modules\inventory\helpers\InventoryApiHelper;
use eagle\modules\order\helpers\OrderTagHelper;
use eagle\models\SaasDhgateUser;
use eagle\modules\dhgate\apihelpers\DhgateApiHelper;
use eagle\modules\app\apihelpers\AppTrackerApiHelper;
use eagle\modules\order\helpers\OrderBackgroundHelper;
use eagle\models\SysCountry;
use eagle\modules\carrier\helpers\CarrierOpenHelper;
use eagle\modules\order\apihelpers\OrderApiHelper;
use eagle\modules\util\helpers\ConfigHelper;
use eagle\modules\util\helpers\RedisHelper;
use eagle\modules\platform\apihelpers\PlatformAccountApi;


class DhgateOrderController extends \eagle\components\Controller{
	public $enableCsrfValidation = false;
	
	public function actionList(){
		//check模块权限
		$permission = \eagle\modules\permission\apihelpers\UserApiHelper::checkPlatformPermission('dhgate');
		if(!$permission)
			return $this->render('//errorview_no_close',['title'=>'访问受限:没有权限','error'=>'您还没有订单模块的权限!']);
		
		AppTrackerApiHelper::actionLog("Oms-dhgate", "/order/dhgate/list");
		
		$data = OdOrder::find()->where(['order_source'=>'dhgate']);
		$showsearch = 0;
		$op_code = '';
		
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
		
		$addi_condition = array();
		
		$tmpSellerIDList = PlatformAccountApi::getPlatformAuthorizeAccounts('dhgate');//添加账号权限	lzhl 2017-03
		$dhAccountList = [];
		$selleruserids = [];
		foreach($tmpSellerIDList as $sellerloginid=>$store_name){
			$dhAccountList[] = $sellerloginid;
			$selleruserids[$sellerloginid] = $store_name;
		}
		if(empty($dhAccountList)){
			//如果为测试用的账号就不受平台绑定限制
			$test_userid=\eagle\modules\tool\helpers\MirroringHelper::$test_userid;
			if (!in_array(\Yii::$app->user->identity->getParentUid(),$test_userid['yifeng'])){
				//无有效权限账号时
				return $this->render('//errorview_no_close',['title'=>'访问受限:没有权限','error'=>'您还没有获得任何 敦煌 账号管理权限!']);
			}
		}
			
		//不显示 解绑的账号的订单
		$data->andWhere(['selleruserid'=>$dhAccountList]);
		
		$addi_condition['order_source'] = 'dhgate';
		$addi_condition['sys_uid'] = \Yii::$app->user->id;
		$addi_condition['selleruserid_tmp'] = $selleruserids;
		
		$tmp_REQUEST_text['where']=empty($data->where)?Array():$data->where;
		$tmp_REQUEST_text['orderBy']=empty($data->orderBy)?Array():$data->orderBy;
		$omsRT = OrderApiHelper::getOrderListByConditionOMS($_REQUEST,$addi_condition,$data,$pageSize,false,'all');
		if (!empty($_REQUEST['order_status'])){
			//生成操作下拉菜单的code
			$op_code = $_REQUEST['order_status'];
		}
		if (!empty($omsRT['showsearch'])) $showsearch = 1;
		
		$pages = new Pagination([
			'defaultPageSize' => $pageSize,
			'totalCount' => $data->count(),
			'pageSizeLimit'=>[5,200],//每页显示条数范围
			'params'=>$_REQUEST,
		]);
	    $models = $data->offset($pages->offset)
	        ->limit($pages->limit)
	        ->all();
	    
	    $excelmodel	= new Excelmodel();
	    $model_sys = $excelmodel->find()->all();
	    
	    $excelmodels = array(''=>'导出订单');
	    if(isset($model_sys)&&!empty($model_sys)){
	    	foreach ($model_sys as $m){
	    		$excelmodels[$m->id] = $m->name;
	    	}
	    }
	    
	    //订单数量统计
	    $hitCache = "NoHit";
	    $cachedArr = array();
	    $uid = \Yii::$app->user->id;
	    $stroe = 'all';
	    if(!empty($_REQUEST['selleruserid']))
	    	$stroe  = trim($_REQUEST['selleruserid']);
	    
	    $puid = \Yii::$app->user->identity->getParentUid();
	    $isParent = \eagle\modules\permission\apihelpers\UserApiHelper::isMainAccount();
	    if($isParent){
	    	$gotCache = RedisHelper::getOrderCache2($puid,$uid,'dhgate',"MenuStatisticData",$stroe) ;
	    }else{
	    	if (!empty($_REQUEST['selleruserid'])){
	    		$gotCache = RedisHelper::getOrderCache2($puid,$uid,'dhgate',"MenuStatisticData",$_REQUEST['selleruserid']) ;
	    	}else{
	    		$gotCache = RedisHelper::getOrderCache2($puid,$uid,'dhgate',"MenuStatisticData",'all') ;
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
	    		$counter = OrderHelper::getMenuStatisticData('dhgate',['selleruserid'=>$_REQUEST['selleruserid']]);
	    	}else{
	    		if(!empty($dhAccountList)){
	    			$counter = OrderHelper::getMenuStatisticData('dhgate',['selleruserid'=>$dhAccountList]);
	    		}else{
	    			//无有效绑定账号
	    			$counter=[];
	    			$claimOrderIDs=[];
	    		}
	    	}
	    	//save the redis cache for next time use
	    	if (!empty($_REQUEST['selleruserid'])){
	    		RedisHelper::setOrderCache2($puid,$uid,'dhgate',"MenuStatisticData",$_REQUEST['selleruserid'],$counter) ;
	    	}else{
	    		RedisHelper::setOrderCache2($puid,$uid,'dhgate',"MenuStatisticData",'all',$counter) ;
	    	}
	    }
	    
	    $usertabs = Helper_Array::toHashmap(Helper_Array::toArray(Usertab::find()->all()),'id','tabname');
	    $warehouses = InventoryApiHelper::getWarehouseIdNameMap();
	    //$selleruserids = Helper_Array::toHashmap(SaasDhgateUser::find()->where(['uid'=>\Yii::$app->user->identity->getParentUid()])->select(['sellerloginid'])->asArray()->all(),'sellerloginid','sellerloginid');
	 
	    // 客户自定义标签获取
	    $allTagDataList = OrderTagHelper::getTagByTagID();
	    $allTagList = [];
	    foreach($allTagDataList as $tmpTag){
	    	$allTagList[$tmpTag['tag_id']] = $tmpTag['tag_name'];
	    }
	    
	    // 已有订单发货国家
	    $tmpCountryArr = OdOrder::find()->select('consignee_country,consignee_country_code')->distinct('consignee_country,consignee_country_code')->where(['order_source' => 'dhgate'])->asArray()->all();
	    $countryArr = Helper_Array::toHashmap($tmpCountryArr , 'consignee_country_code' , 'consignee_country');
	    $countryArr = array_filter($countryArr);
	    
	    // 搜索国家
	    $query = SysCountry::find();
	    $regions = $query->orderBy('region')->groupBy('region')->select('region')->asArray()->all();
	    $countrys =[];
	    foreach ($regions as $region){
	    	$arr['name']= $region['region'];
	    	$arr['value']=Helper_Array::toHashmap(SysCountry::find()->where(['region'=>$region['region']])->orderBy('country_en')->select(['country_code', "CONCAT( country_zh ,'(', country_en ,')' ) as country_name "])->asArray()->all(),'country_code','country_name');
	    	$countrys[]= $arr;
	    }
	    
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
		    'pages' => $pages,
			'excelmodels'=>$excelmodels,
			'usertabs'=>$usertabs,
			'counter'=>$counter,
			'warehouses'=>$warehouses,
			'selleruserids'=>$selleruserids,
			'countrys'=>$countrys,
			'countryArr'=>$countryArr,
			'showsearch'=>$showsearch,
			'all_tag_list'=>$allTagList,
			'doarr'=>OrderHelper::getCurrentOperationList($op_code,'b') ,
			'doarr_one'=>OrderHelper::getCurrentOperationList($op_code,'s'),
			'existProductResult'=>$existProductResult,
			'tag_class_list'=> OrderTagHelper::getTagColorMapping(),
			'search_condition'=>$tmp_REQUEST_text,
			'search_count'=>$pages->totalCount,
		));
		
	}
		
	/**
	 * 订单列表页面进行批量平台标记发货,提供给用户填写物流号，运输服务等的页面
	 * @author dzt
	 */
	public function actionSignshipped(){
		
		AppTrackerApiHelper::actionLog("Oms-dhgate", "/order/dhgate/signshipped");
		
		if (\Yii::$app->request->getIsPost()){
			$orders = OdOrder::find()->where(['in','order_id',\Yii::$app->request->post()['order_id']])->all();
			$dhgateShippingMethod = DhgateApiHelper::getShippingCodeNameMap();
			return $this->render('signshipped',['orders'=>$orders,'dhgateShippingMethod'=>$dhgateShippingMethod]);
		}else {
			$orders = OdOrder::find()->where(['in','order_id',\Yii::$app->request->get('order_id')])->all();
			$dhgateShippingMethod = DhgateApiHelper::getShippingCodeNameMap();
			return $this->render('signshipped',['orders'=>$orders,'dhgateShippingMethod'=>$dhgateShippingMethod]);
		}
	}
	
	/**
	 * 订单列表页面进行批量平台标记发货,插入标记队列
	 * @todo 订单已发货 ， 再手动标记发货会覆盖部分 od_ship信息，但不会进入发货队列，所以目前系统做不到物流信息更新到平台。但敦煌不支持对同一个订单同一个物流号再次上传，所以敦煌也做不到这个物流的信息更新（例如更新/添加备注）。
	 * @author dzt
	 */
	public function actionSignshippedsubmit(){
		if (\Yii::$app->request->getIsPost()){
			AppTrackerApiHelper::actionLog("Oms-dhgate", "/order/dhgate/signshippedsubmit");
			
			$user = \Yii::$app->user->identity;
			$postarr = \Yii::$app->request->post();
			$dhgateShippingMethod = DhgateApiHelper::getShippingCodeNameMap();
			if (count($postarr['order_id'])){
				foreach ($postarr['order_id'] as $oid){
					try {
						$shipping_method_code = strlen($postarr['shipmethod'][$oid])>0?$postarr['shipmethod'][$oid]:'Home delivery';
						$order = OdOrder::findOne($oid);
						$logisticInfoList=[
							'0'=>[
								'order_source'=>$order->order_source,
								'selleruserid'=>$order->selleruserid,
								'tracking_number'=>$postarr['tracknum'][$oid],
								'tracking_link'=>$postarr['trackurl'][$oid],
								'shipping_method_code'=>$shipping_method_code,
								'shipping_method_name'=>$dhgateShippingMethod[$shipping_method_code],//平台物流服务名
								'order_source_order_id'=>$order->order_source_order_id,
								'description'=>$postarr['message'][$oid],
								'signtype'=>$postarr['signtype'][$oid],
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
				return $this->render('//successview',['title'=>'Dhgate标记发货完成','message'=>'标记结果可查看Dhgate状态']);
			}			
		}
	}
	
	/**
	 * 订单编辑
	 * @author dzt
	 */
	public function actionEdit(){
		if (\Yii::$app->request->isPost){
			if (count($_POST['item']['product_name'])==0){
				return $this->render('//errorview',['title'=>'编辑订单','error'=>'订单必需有相应商品']);
			}
			$order = OdOrder::findOne(['order_id'=>$_POST['orderid'] , 'order_source'=>'dhgate']);
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
			$order->setAttributes($_tmp);
			$order->save();
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
				$item->save();
			}
			
			AppTrackerApiHelper::actionLog("Oms-dhgate", "/order/dhgate/edit-save");
			OperationLogHelper::log('order',$order->order_id,'修改订单','编辑订单进行订单修改',\Yii::$app->user->identity->getFullName());
			return $this->render('//successview',['title'=>'编辑订单']);
		}
		if (!isset($_GET['orderid'])){
			return $this->render('//errorview',['title'=>'编辑订单','error'=>'链接有误']);
		}
		$order = OdOrder::findOne(['order_id'=>$_GET['orderid'] , 'order_source'=>'dhgate']);
		if (empty($order)||$order->isNewRecord){
			return $this->render('//errorview',['title'=>'编辑订单','error'=>'无相应订单']);
		}
		
		AppTrackerApiHelper::actionLog("Oms-dhgate", "/order/dhgate/edit-page");
		
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
		return $this->render('edit',['order'=>$order,'warehouseList'=>$warehouseList,'shipmethodList'=>$shipmethodList]);
	}
	
}

?>