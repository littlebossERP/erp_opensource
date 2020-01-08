<?php

namespace eagle\modules\order\controllers;

use eagle\modules\listing\models\OdOrderItem2;
use yii\data\Pagination;
use eagle\modules\order\models\OdOrder;
use eagle\modules\order\models\Excelmodel;
use eagle\modules\order\models\OdEbayTransaction;
use eagle\models\EbaySite;
use eagle\models\EbayShippingservice;
use common\api\ebayinterface\getorders;
use common\api\ebayinterface\getsellertransactions;
use common\helpers\Helper_Array;
use eagle\models\SaasEbayUser;
use common\api\ebayinterface\sendinvoice;
use eagle\modules\order\models\OdOrderItem;
use eagle\modules\util\helpers\OperationLogHelper;
use eagle\modules\order\helpers\OrderHelper;
use eagle\modules\util\helpers\ExcelHelper;
use eagle\modules\order\models\Usertab;
use Exception;
use eagle\modules\order\models\OdOrderShipped;
use eagle\models\carrier\SysShippingService;
use eagle\modules\inventory\helpers\InventoryApiHelper;
use eagle\models\EbayCountry;
use eagle\modules\order\helpers\OrderTagHelper;
use common\api\ebayinterface\addmembermessageaaqtopartner;
use eagle\modules\app\apihelpers\AppTrackerApiHelper;
use eagle\models\QueueGetorder;
use eagle\modules\order\model\OdPaypalTransaction;
use eagle\modules\tracking\helpers\TrackingApiHelper;
use eagle\modules\order\helpers\OrderProfitHelper;
use eagle\modules\catalog\helpers\ProductApiHelper;
use eagle\modules\util\helpers\SysLogHelper;
use eagle\modules\carrier\apihelpers\CarrierApiHelper;
use eagle\modules\util\helpers\TranslateHelper;
use eagle\modules\order\apihelpers\OrderApiHelper;
use eagle\modules\order\models\OdOrderGoods;
use eagle\modules\carrier\helpers\CarrierOpenHelper;
use eagle\modules\inventory\helpers\InventoryHelper;
use common\helpers\Helper_xml;
use eagle\models\SysCountry;
use eagle\modules\util\helpers\StandardConst;
use eagle\modules\order\helpers\CdiscountOrderInterface;
use eagle\modules\platform\apihelpers\PlatformAccountApi;
use eagle\modules\message\helpers\MessageHelper;
use yii\db\Query;
use eagle\modules\order\models\OrderRelation;
use eagle\modules\catalog\helpers\ProductHelper;
use eagle\modules\util\helpers\ConfigHelper;
use eagle\modules\util\helpers\DataStaticHelper;
use eagle\modules\carrier\models\SysCarrierCustom;
use eagle\modules\order\helpers\OrderGetDataHelper;
use eagle\modules\order\helpers\OrderBackgroundHelper;
use eagle\modules\order\helpers\OrderFrontHelper;
use eagle\modules\order\helpers\OrderUpdateHelper;
use eagle\modules\catalog\models\ProductBundleRelationship;
use eagle\modules\catalog\models\Product;
use eagle\modules\carrier\helpers\CarrierDeclaredHelper;
use eagle\widgets\SizePager;
use Qiniu\json_decode;
use eagle\modules\util\helpers\RedisHelper;
use frontend;
use Qiniu\base64_urlSafeDecode;
use eagle\modules\order\helpers\OrderListV3Helper;
use eagle\modules\order\helpers\LazadaOrderHelper;
use eagle\modules\platform\apihelpers\EbayAccountsApiHelper;
use eagle\modules\permission\helpers\UserHelper;
use PayPal\Api\Order;
use eagle\modules\util\helpers\ImageCacherHelper;


class OrderController extends \eagle\components\Controller{
	public $enableCsrfValidation = false;
	
	public function actionListebay(){
		$url = '/order/ebay-order/list';
		return $this->redirect($url);
		AppTrackerApiHelper::actionLog("Oms-ebay", "/order/listebay");
		$data=OdOrder::find();
		$data->andWhere(['order_source'=>'ebay']);
		$showsearch=0;
		if (!empty($_REQUEST['order_status'])){
			//搜索订单状态
			$data->andWhere('order_status = :os',[':os'=>$_REQUEST['order_status']]);
		}
		if (!empty($_REQUEST['exception_status'])){
			//搜索订单异常状态
			$data->andWhere('exception_status = :es',[':es'=>$_REQUEST['exception_status']]);
			$data->andWhere('order_status < '.OdOrder::STATUS_WAITSEND);
		}
		if (!empty($_REQUEST['is_manual_order'])){
			//搜索订单挂起状态
			$data->andWhere('is_manual_order = :imo',[':imo'=>$_REQUEST['is_manual_order']]);
		}
		if (!empty($_REQUEST['cangku'])){
			//搜索仓库
			$data->andWhere('default_warehouse_id = :dwi',[':dwi'=>$_REQUEST['cangku']]);
			$showsearch=1;
		}
		if (!empty($_REQUEST['shipmethod'])){
			//搜索运输服务
			$data->andWhere('default_shipping_method_code = :dsmc',[':dsmc'=>$_REQUEST['shipmethod']]);
			$showsearch=1;
		}
		if (!empty($_REQUEST['trackstatus'])){
			//搜索物流状态
			$data->andWhere(['trackstatus'=>$_REQUEST['trackstatus']]);
		}
		if (!empty($_REQUEST['fuhe'])){
			$showsearch=1;
			//搜索符合条件
			switch ($_REQUEST['fuhe']){
				case 'haspayed':
					$data->andWhere('pay_status = 1');
					break;
				case 'hasnotpayed':
					$data->andWhere('pay_status = 0');
					break;
				case 'pending':
					$data->andWhere('pay_status = 2');
					break;
				case 'hassend':
					$data->andWhere('shipping_status = 1');
					break;
				case 'payednotsend':
					$data->andWhere('shipping_status = 0 and pay_status = 1');
					break;
				case 'hasmessage':
					//$data->andWhere('user_message is not null');
					$data->andWhere('length(user_message)>0');
					break;
				case 'hasinvoice':
					$data->andWhere('hassendinvoice = 1');
					break;
				default:break;
			}
		}
		if (!empty($_REQUEST['searchval'])){
			//搜索用户自选搜索条件
			if (in_array($_REQUEST['keys'], ['order_id','ebay_orderid','srn','buyeid','email','consignee'])){
				$kv=[
					'order_id'=>'order_id',
					'ebay_orderid'=>'order_source_order_id',
					'srn'=>'order_source_srn',
					'buyeid'=>'source_buyer_user_id',
					'email'=>'consignee_email',
					'consignee'=>'consignee'
				];
				$key = $kv[$_REQUEST['keys']];
				$data->andWhere("$key = :val",[':val'=>$_REQUEST['searchval']]);
			}elseif ($_REQUEST['keys']=='sku'){
				$ids = Helper_Array::getCols(OdOrderItem::find()->where('sku = :sku',[':sku'=>$_REQUEST['searchval']])->select('order_id')->asArray()->all(),'order_id');
				$data->andWhere(['IN','order_id',$ids]);
			}elseif ($_REQUEST['keys']=='itemid'){
				
			}elseif ($_REQUEST['keys']=='tracknum'){
				$ids = Helper_Array::getCols(OdOrderShipped::find()->where('tracking_number = :tn',[':tn'=>$_REQUEST['searchval']])->select('order_id')->asArray()->all(),'order_id');
				$data->andWhere(['IN','order_id',$ids]);
			}
		}
		if (!empty($_REQUEST['selleruserid'])){
			//搜索卖家账号
			$data->andWhere('selleruserid = :s',[':s'=>$_REQUEST['selleruserid']]);
		}
		if (!empty($_REQUEST['country'])){
			//搜索订单国家
			$data->andWhere('consignee_country_code = :c',[':c'=>$_REQUEST['country']]);
			$showsearch=1;
		}
		if (!empty($_REQUEST['startdate'])||!empty($_REQUEST['enddate'])){
			//搜索订单日期
			switch ($_REQUEST['timetype']){
				case 'soldtime':
					$tmp='order_source_create_time';
				break;
				case 'paidtime':
					$tmp='paid_time';
				break;
				case 'printtime':
					$tmp='printtime';
				break;
				case 'shiptime':
					$tmp='delivery_time';
				break;
			}
			if (!empty($_REQUEST['startdate'])){
				$data->andWhere("$tmp >= :stime",[':stime'=>strtotime($_REQUEST['startdate'])]);
			}
			if (!empty($_REQUEST['enddate'])){
				$data->andWhere("$tmp <= :time",[':time'=>strtotime($_REQUEST['enddate'])+24*3599]);
			}
			$showsearch=1;
		}
		if (empty($_REQUEST['ordersort'])){
			$orderstr = 'order_source_create_time';
		}else{
			switch ($_REQUEST['ordersort']){
				case 'soldtime':
					$orderstr='order_source_create_time';
					break;
				case 'paidtime':
					$orderstr='paid_time';
					break;
				case 'printtime':
					$orderstr='printtime';
					break;
				case 'shiptime':
					$orderstr='delivery_time';
					break;
			}
		}
		if (empty($_REQUEST['ordersorttype'])){
			$orderstr .= ' DESC';
		}else{
			$orderstr.=' '.$_REQUEST['ordersorttype'];
		}
		$data->orderBy($orderstr)->with('items');
	    $pages = new Pagination(['totalCount' => $data->count(),'pageSize'=>isset($_REQUEST['per-page'])?$_REQUEST['per-page']:'50','params'=>$_REQUEST]);
	    $models = $data->offset($pages->offset)
	        ->limit($pages->limit)
	        ->all();
	    
	    $excelmodel	=	new Excelmodel();
// 	    $myexcelmodel	=	new MyExcelmodel();
// 	    $models	=	$myexcelmodel->findAllBySql($sql);
	    $model_sys	=	$excelmodel->find()->all();
	    
	    $excelmodels=array(''=>'导出订单');
	    if(isset($model_sys)&&!empty($model_sys)){
	    	foreach ($model_sys as $m){
	    		$excelmodels[$m->id]=$m->name;
	    	}
	    }
	    
	    //订单数量统计
	    $counter[OdOrder::STATUS_NOPAY]=OdOrder::find()->where('order_source = "ebay" and order_status = '.OdOrder::STATUS_NOPAY)->count();
	    $counter[OdOrder::STATUS_PAY]=OdOrder::find()->where('order_source = "ebay" and order_status = '.OdOrder::STATUS_PAY)->count();
	    $counter[OdOrder::STATUS_WAITSEND]=OdOrder::find()->where('order_source = "ebay" and order_status = '.OdOrder::STATUS_WAITSEND)->count();
	    $counter['all']=OdOrder::find()->where('order_source = "ebay"')->count();
	    $counter['guaqi']=OdOrder::find()->where('order_source = "ebay" and is_manual_order = 1')->count();
	    
	    $counter[OdOrder::EXCEP_WAITSEND]=OdOrder::find()->where('order_source = "ebay" and exception_status = '.OdOrder::EXCEP_WAITSEND.' and order_status < '.OdOrder::STATUS_WAITSEND)->count();
	    $counter[OdOrder::EXCEP_HASNOSHIPMETHOD]=OdOrder::find()->where('order_source = "ebay" and exception_status = '.OdOrder::EXCEP_HASNOSHIPMETHOD.' and order_status < '.OdOrder::STATUS_WAITSEND)->count();
	    $counter[OdOrder::EXCEP_PAYPALWRONG]=OdOrder::find()->where('order_source = "ebay" and exception_status = '.OdOrder::EXCEP_PAYPALWRONG.' and order_status < '.OdOrder::STATUS_WAITSEND)->count();
	    $counter[OdOrder::EXCEP_SKUNOTMATCH]=OdOrder::find()->where('order_source = "ebay" and exception_status = '.OdOrder::EXCEP_SKUNOTMATCH.' and order_status < '.OdOrder::STATUS_WAITSEND)->count();
	    $counter[OdOrder::EXCEP_NOSTOCK]=OdOrder::find()->where('order_source = "ebay" and exception_status = '.OdOrder::EXCEP_NOSTOCK.' and order_status < '.OdOrder::STATUS_WAITSEND)->count();
	    $counter[OdOrder::EXCEP_WAITMERGE]=OdOrder::find()->where('order_source = "ebay" and exception_status = '.OdOrder::EXCEP_WAITMERGE.' and order_status < '.OdOrder::STATUS_WAITSEND)->count();
	    $usertabs = Helper_Array::toHashmap(Helper_Array::toArray(Usertab::find()->all()),'id','tabname');
	    $warehouseids = InventoryApiHelper::getWarehouseIdNameMap();
	    $selleruserids=Helper_Array::toHashmap(SaasEbayUser::find()->where(['uid'=>\Yii::$app->user->identity->getParentUid()])->select(['selleruserid'])->asArray()->all(),'selleruserid','selleruserid');
	    
	    //这里添加对应状态下面的国家筛选
	    if (!empty($_REQUEST['order_status'])){
	    	$countrycode=OdOrder::getDb()->createCommand('select consignee_country_code from '.OdOrder::tableName().
	    			' where order_status = :os and order_source=:order_source group by consignee_country_code',[':os'=>$_REQUEST['order_status'],':order_source'=>'ebay'])->queryColumn();
	    }else{
	    	$countrycode=OdOrder::getDb()->createCommand('select consignee_country_code from '.OdOrder::tableName().' group by consignee_country_code')->queryColumn();
	    }
	    
// 	    $countrycode=OdOrder::getDb()->createCommand('select consignee_country_code from '.OdOrder::tableName().' group by consignee_country_code')->queryColumn();
	    $countrycode=array_filter($countrycode);
	    $countrys=Helper_Array::toHashmap(EbayCountry::find()->where(['country'=>$countrycode])->orderBy('description asc')->select(['country','description'])->asArray()->all(),'country','description');
		return $this->render('list',array(
			'models' => $models,
		    'pages' => $pages,
			'excelmodels'=>$excelmodels,
			'usertabs'=>$usertabs,
			'counter'=>$counter,
			'warehouseids'=>$warehouseids,
			'selleruserids'=>$selleruserids,
			'countrys'=>$countrys,
			'showsearch'=>$showsearch,
			'tag_class_list'=> OrderTagHelper::getTagColorMapping()
		));
		
	}
	/**
	 * 单独订单发送ebay账单
	 * @author fanjs
	 */
	public function actionSendinvoice(){
		AppTrackerApiHelper::actionLog("Oms-ebay", "/order/sendinvoice");
		if (\Yii::$app->request->getIsPost()){
			$order = OdOrder::findOne($_POST['order_id']);
			$transactions = OdEbayTransaction::find()->where('order_id = :oi',[':oi'=>$_POST['order_id']])->all();
			$transaction = $transactions[0];
			$user = SaasEbayUser::find()->where('selleruserid = :s',[':s'=>$transaction->selleruserid])->one();
			$isinternational = $_POST['isinternational'];
			$siteid = $_POST['siteid'];
			
			//调用接口
			$api = new sendinvoice();
			$api->resetConfig($user->DevAcccountID); 
			$api->siteID = $transaction->transactionsiteid;
			$api->eBayAuthToken = $user->token;
			$ids = [
					'ItemID' => $transaction->itemid,
					'TransactionID' => $transaction->transactionid
					];
			$shippingDetail = [
					'ShippingServiceCost' => $_POST['ShippingServiceCost'],
					'ShippingServiceAdditionalCost' => isset($_POST['ShippingServiceAdditionalCost'])?$_POST['ShippingServiceAdditionalCost']:'0.00',
					'ShippingService' => $_POST['ShippingService']
					];
			Helper_Array::removeEmpty ( $shippingDetail );
			if ($isinternational) {
				@$shipping ['InternationalShippingServiceOptions'] = $shippingDetail;
			} else {
				@$shipping ['ShippingServiceOptions'] = $shippingDetail;
			}
			$api->siteID = $siteid;
			$r = $api->api ( $ids, $shipping, $_POST['EmailCopyToSeller'],$_POST['CheckoutInstructions']);
			if ($api->responseIsSuccess ()) {
				$order->hassendinvoice=1;
				$order->save();
				foreach ( $transactions as $t ) {
					$t->sendinvoice ++;
					$t->save ();
				}
				return $this->render('//successview',['title'=>'发送eBay账单']);
			}else{
				return $this->render('sendinvoice',['error'=>$r['Errors']]);
			}
		}
		$error=[];
		if (!isset($_REQUEST['orderid'])){
			$error[]='无相应订单号';
			return $this->render('sendinvoice',['error'=>$error]);
		}else{
			$order = OdOrder::findOne($_REQUEST['orderid']);
			if (is_null($order)){
				$error[]='无法查找对应订单';
			}
			if (is_null($order->order_source_order_id)){
				$error[]='暂不支持此类订单SendInvoice';
			}
			$transactions = OdEbayTransaction::find()->where('order_id = :oi',[':oi'=>$_REQUEST['orderid']])->all();
			if (count($transactions)>1){
				$error[]='暂不支持此类订单SendInvoice';
			}
			$transaction = $transactions[0];
			
			$site = EbaySite::find()->where('site =:site',['site'=> $transaction->transactionsiteid])->one();
			$siteid = $site->siteid!=100?$site->siteid:0;
				
	 		$shippingservices = EbayShippingservice::find()->where('siteid = :siteid and validforsellingflow=\'true\'',['siteid'=>$siteid] );
	 		// 是否国际物流
	 		$isinternational = EbayShippingservice::find()->where('shippingservice = :s',[':s'=>$transaction->shippingserviceselected['ShippingService']])->one()->internationalservice;
			if ($isinternational) {
				$shippingservices->where ( 'internationalservice = "true"' );
			} else {
				$shippingservices->where ( 'internationalservice is null' );
			}
			return $this->render('sendinvoice',['shippingservices'=>$shippingservices->all(),
										'transaction'=>$transaction,
										'order'=>$order,
										'siteid'=>$siteid,
										'isinternational'=>$isinternational,
										'error'=>$error]);
		}
// 				if (count($myoo->transactions)>0 && strlen($myoo->ebay_orderid)>0){
// 					$ids = array (
// 							'OrderID' => $myoo->ebay_orderid
// 					);
// 				}elseif (request ( 'orderid' ) > 0) {
// 					$ids = array (
// 							'OrderID' => request ( 'orderid' )
// 					);
// 				} else {
// 					$ids = array (
// 							'ItemID' => $transaction->itemid,
// 							'TransactionID' => $transaction->transactionid
// 					);
// 				}
	}
	
		
	/**
	 * 订单列表页面进行批量平台标记发货,提供给用户填写物流号，运输服务等的页面
	 * @author fanjs
	 */
	public function actionSignshippedOld(){
		AppTrackerApiHelper::actionLog("Oms-erp", "/order/signshipped");
		if (\Yii::$app->request->getIsPost()){
			//用于区分不同js的调用入口
			if(empty($_REQUEST['js_submit'])){
				$tmpOrders = \Yii::$app->request->post()['order_id'];
			}else{
				$tmpOrders = json_decode($_REQUEST['order_id'], true);
			}
		}else {
			$tmpOrders = [\Yii::$app->request->get('order_id')];
		}
		
		if(empty($tmpOrders))
			return $this->render('//errorview',['title'=>'虚拟发货','error'=>'未找到相应订单']);
		$orders = OdOrder::find()->where(['in','order_id',$tmpOrders])->andwhere(['order_capture'=>'N'])->all();
		if (empty($orders))
			return $this->render('//errorview',['title'=>'虚拟发货','error'=>'未找到有效订单']);
		$allPlatform = []; // 所有平台
		foreach ($orders as $key=>$order){
			if (!in_array($order->order_source ,$allPlatform )){
				$allPlatform[] = $order->order_source;
			}
		
			if('sm' == $order->order_relation){// 合并订单标记发货获取原始订单信息发货
				$father_orderids = OrderRelation::findAll(['son_orderid'=>$order->order_id , 'type'=>'merge' ]);
				foreach ($father_orderids as $father_orderid){
					$tmpOrders[] = $father_orderid->father_orderid;
					$orders[] = OdOrder::findOne($father_orderid->father_orderid);
				}
		
				unset($orders[$key]);
			}
		}
			
		$allShipcodeMapping = [];
		foreach ($allPlatform as $_platform){
			list($rt , $type)	  = \eagle\modules\delivery\apihelpers\DeliveryApiHelper::getShippingCodeByPlatform($_platform);
			if('ebay' == $_platform){// ebay 标记发货不用下拉框
				$allShipcodeMapping[$_platform] = $rt;
			}else{// 下拉框常用项
				$tmpShippingMethod = DataStaticHelper::getUseCountTopValuesFor("erpOms_ShippingMethod" ,$rt );
				$shippingMethods = [];
				if(!empty($tmpShippingMethod['recommended'])){
					$shippingMethods += $tmpShippingMethod['recommended'];
					$shippingMethods[''] = '---常用/非常用 分割线---';
				}
				if(!empty($tmpShippingMethod['rest']))
					$shippingMethods += $tmpShippingMethod['rest'];
				$allShipcodeMapping[$_platform] = $shippingMethods;
			}
			
		}

		$logs = OdOrderShipped::findAll(['order_id'=>$tmpOrders]);
		return $this->render('signshipped',['orders'=>$orders,'logs'=>$logs,'allShipcodeMapping'=>$allShipcodeMapping]);
	}
	
	/**
	 * 订单列表页面进行批量平台标记发货,插入标记队列
	 * @author fanjs
	 */
	public function actionSignshippedsubmit(){
		if (\Yii::$app->request->getIsPost()){
			$user = \Yii::$app->user->identity;
			$postarr = \Yii::$app->request->post();
			if (count($postarr['order_id'])){
				// 处理前检查
				foreach ($postarr['order_id'] as $oid){
					if(empty($postarr['shipmethod'][$oid])){
						return exit(json_encode(array('code'=>'1', 'msg'=>'失败:请选择运输服务!')));
// 						return $this->render('//errorview',['title'=>'标记发货','error'=>'请选择运输服务!']);
					}
				}
				$checkReport = '';
				
				foreach ($postarr['order_id'] as $oid){
					try {
						$order = OdOrder::findOne($oid);
						
						list($rt , $type)	  = \eagle\modules\delivery\apihelpers\DeliveryApiHelper::getShippingCodeByPlatform($order->order_source);
						if (!empty($rt[$postarr['shipmethod'][$oid]])){
							if(strtolower($postarr['shipmethod'][$oid]) == 'other' && $order->order_source == 'cdiscount')
								$shipMethodName = $postarr['othermethod'][$oid];
							else
								$shipMethodName = $rt[$postarr['shipmethod'][$oid]];
						}else{
							$shipMethodName='';
						}
						
						if($order->order_source == 'cdiscount' && empty($postarr['trackurl'][$oid]))
							$postarr['trackurl'][$oid] = CdiscountOrderInterface::getShippingMethodDefaultURL($postarr['shipmethod'][$oid]);
						
						$signtype = (empty($postarr['signtype']) || empty($postarr['signtype'][$oid]))?"all":$postarr['signtype'][$oid];
						$description = (empty($postarr['message']) || empty($postarr['message'][$oid]))?"":$postarr['message'][$oid];
						
						$logisticInfoList=[
							'0'=>[
								'order_source'=>$order->order_source,
								'selleruserid'=>$order->selleruserid,
								'tracking_number'=>$postarr['tracknum'][$oid],
								'tracking_link'=>$postarr['trackurl'][$oid],
								'shipping_method_code'=>$postarr['shipmethod'][$oid],
								'shipping_method_name'=>$shipMethodName,//平台物流服务名
								'order_source_order_id'=>$order->order_source_order_id,
								'signtype'=>$signtype,
								'description'=>$description,
								'addtype'=>'手动标记发货',
							]
						];
						
						// 虚拟发货 检查
						if($order->order_source == 'cdiscount'){
							$checkRT = \eagle\modules\order\helpers\CdiscountOrderInterface::preCheckSignShippedInfo($postarr['tracknum'][$oid],$order->order_source_shipping_method, $postarr['shipmethod'][$oid], $shipMethodName, $postarr['trackurl'][$oid]);
							if ($checkRT['success'] == false){
								$checkReport .= "<br> 平台订单".$order->order_source_order_id." 提交失败：". $checkRT['message'];
								continue;
							}
						}
						
						
						if(!OrderHelper::saveTrackingNumber($oid, $logisticInfoList,0,1)){
							\Yii::error(["Order",__CLASS__,__FUNCTION__,"Online",'订单'.$oid.'插入失败'],'edb\global');
						}else{
							OperationLogHelper::log('order', $oid,'标记发货','手动批量标记发货 [运单号]='.@$postarr['tracknum'][$oid] ." [查询网址]=".@$postarr['trackurl'][$oid]." [运输服务]=".@$postarr['shipmethod'][$oid]."($shipMethodName)"." [发货留言]=".@$description,\Yii::$app->user->identity->getFullName());
							DataStaticHelper::addUseCountFor("erpOms_ShippingMethod", $postarr['shipmethod'][$oid],8);
							
							//写入操作日志
							UserHelper::insertUserOperationLog('order', '标记发货, 手动批量标记发货 [运单号]='.@$postarr['tracknum'][$oid] ." [查询网址]=".@$postarr['trackurl'][$oid]." [运输服务]=".@$postarr['shipmethod'][$oid]."($shipMethodName)"." [发货留言]=".@$description);
						}
						
					}catch (\Exception $ex){
						\Yii::error(["Order",__CLASS__,__FUNCTION__,"Online","save to SignShipped failure:".print_r($ex->getMessage())],'edb\global');
					}
				}
				
				if(empty($checkReport)){
					return exit(json_encode(array('code'=>'0', 'msg'=>'操作已成功')));
// 					echo "<script language='javascript'>alert('操作已成功,即将关闭页面');window.close();</script>";
				}
				
				return exit(json_encode(array('code'=>'1', 'msg'=>$checkReport)));
// 				return $this->render('//successview',['title'=>'平台标记发货','message'=>$checkReport]);
			}			
		}
	}

	/**
	 * 删除选中的订单,并删除相应的odorderitem.并添加业务log
	 * @author fanjs;
	 */
	public function actionDeleteorder(){
		if (\Yii::$app->request->getIsPost()){
			if (count($_POST['order_id'])){
				AppTrackerApiHelper::actionLog("Oms-erp", "/order/deleteorder");

				try {
					OdOrder::deleteAll(['in','order_id',$_POST['order_id']]);
					OdOrderItem::deleteAll(['in','order_id',$_POST['order_id']]);
					foreach ($_POST['order_id'] as $orderid){
						OperationLogHelper::log('order', $orderid,'删除订单','手动批量删除订单',\Yii::$app->user->identity->getFullName());
					}
					return $this->render('//successview',['title'=>'删除订单']);
				}catch (\Exception $e){
					\Yii::error(["Order",__CLASS__,__FUNCTION__,"muti delete odorder failure:".print_r($e->getMessage())],'edb\global');
				}
			}
		}else{
			$orderids  =[];
			$orderids[]=$_GET['order_id'];
			if (count($orderids)){
				try {
					OdOrder::deleteAll(['in','order_id',$orderids]);
					OdOrderItem::deleteAll(['in','order_id',$orderids]);
					foreach ($orderids as $orderid){
						OperationLogHelper::log('order', $orderid,'删除订单','手动批量删除订单',\Yii::$app->user->identity->getFullName());
					}
					return $this->render('//successview',['title'=>'删除订单']);
				}catch (\Exception $e){
					\Yii::error(["Order",__CLASS__,__FUNCTION__,"muti delete odorder failure:".print_r($e->getMessage())],'edb\global');
				}
			}
		}
	}
	
	
	
	/**
	 * 导入物流单号
	 * @author fanjs
	 */
	public function actionImportordertracknum(){
		if (\yii::$app->request->isPost){
			//添加默认平台，现在OMS传递进来都需要传递对应的平台
			if(!empty($_REQUEST['paltform'])) 
			    $platform = $_REQUEST['paltform'];
			else 
			    $platform = '';
			
			//自动虚拟发货（标记发货）
			if(!empty($_REQUEST['autoship']))
				$autoship = $_REQUEST['autoship'];
			else
				$autoship = '';
			
			//自动移动到已完成
			if(!empty($_REQUEST['autoComplete']))
				$autoComplete = $_REQUEST['autoComplete'];
			else
				$autoComplete = '';
			
			
			AppTrackerApiHelper::actionLog("Oms-".$platform, "/order/tracknum");
			
			AppTrackerApiHelper::actionLog("Oms-erp", "/order/tracknum");
			if (isset($_FILES['order_tracknum'])){
				try {
					$result = OrderHelper::importtracknumfromexcel($_FILES['order_tracknum'] ,$platform, $autoship,$autoComplete);
					return $result;
				}catch(\Exception $e){
					return $e->getMessage();
				}
			}
		}
	}
	
	/**
	 * 导入物流单号
	 * @author fanjs
	 */
	public function actionImportordertracknumcommon(){
		if (\yii::$app->request->isPost){
			if (!empty($_REQUEST['paltform'])) 
			    $platform = $_REQUEST['paltform'];
			else 
			    $platform = "";
			AppTrackerApiHelper::actionLog("Oms-".$platform, "/order/tracknum");
			
			if (isset($_FILES['order_tracknum'])){
				try {
					$result = OrderHelper::importtracknumfromexcel($_FILES['order_tracknum']);
					return $result;
				}catch(\Exception $e){
					return $e->getMessage();
				}
			}
		}
	}
	
	/**
	 * 移动订单的状态
	 * @author fanjs
	 */
	public function actionMovestatus(){
		AppTrackerApiHelper::actionLog("Oms-erp", "/order/movestatus");
		if (\yii::$app->request->isPost){
			$message = '';
			$orderids = explode(',',$_POST['orderids']);
			$orderids = array_filter($orderids);
			if (count($orderids)){
				foreach ($orderids as $orderid){
					$order = OdOrder::findOne($orderid);
					
					//操作超时状态处理  liang 2015-12-26
					if($order->order_status!==$_POST['status']){//订单状态变更时，移除所有weird_status
						OperationLogHelper::log('order', $orderid,'移动订单','手动批量移动订单,状态:'.OdOrder::$status[$order->order_status].'->'.OdOrder::$status[$_POST['status']].(empty($order->weird_status)?'':',同时移除操作超时标签'),\Yii::$app->user->identity->getFullName());
						$order->weird_status = '';
					}else{
						OperationLogHelper::log('order', $orderid,'移动订单','手动批量移动订单,状态:'.OdOrder::$status[$order->order_status].'->'.OdOrder::$status[$_POST['status']],\Yii::$app->user->identity->getFullName());
					}//操作超时状态处理  end
					
					$order->order_status = $_POST['status'];
					$order->save();
				}
			}
			return 'success';
		}
	}
	
	/**
	 * 修改订单的挂起状态
	 * @author fanjs
	 */
	public function actionChangemanual(){
		if (\yii::$app->request->isPost){
			AppTrackerApiHelper::actionLog("Oms-erp", "/order/changemanual");
			$order = OdOrder::findOne($_POST['orderid']);
			if (empty($order)){
				return '未找到相应订单';
			}
			if ($order->is_manual_order == 0){
				$order->is_manual_order = 1;
				//兼容2.1 发货流程
				$rt = OrderApiHelper::suspendOrders([$order->order_id]);
				if ($rt['success'] ==false){
					return $rt['message'];
				}
				
			}else{
				$order->is_manual_order = 0;
				$order->order_status = OdOrder::STATUS_PAY;
				$order->save(false);
			}
			return 'success';
		}
	}
	
	/**
	 * 用户自定义标签列表
	 * @author fanjs
	 */
	public function actionUsertab(){
		$uids = [\Yii::$app->user->id,\Yii::$app->user->identity->getParentUid()];
		$tabs = Usertab::findAll(['uid'=>$uids]);
		return $this->render('usertab',['tabs'=>$tabs]);
	}
	
	/**
	 * 用户自定义标签编辑
	 * @author fanjs
	 */
	public function actionEdittab(){
		AppTrackerApiHelper::actionLog("Oms-ebay", "/order/edittab");
		if(\Yii::$app->request->isPost){
			if (isset($_POST['templateid'])){
				$template = Usertab::findOne($_POST['templateid']);
			}else{
				$template = new Usertab();
			}
			try {
				$template->tabname = $_POST['tabname'];
				$template->uid = \Yii::$app->subdb->getCurrentPuid();
				$template->save();
				return $this->actionUsertab();
			}catch (\Exception $e){
				print_r($e->getMessage());
			}
		}
		if(isset($_GET['id'])&&$_GET['id']>0){
			$template = Usertab::findOne($_GET['id']);
		}else{
			$template = new Usertab();
		}
		return $this->renderPartial('tabedit',['template'=>$template]);
	}
	
	/**
	 * 用户自定义标签删除
	 * @author fanjs
	 */
	public function actionDeletetab(){
		if(\Yii::$app->request->isPost){
			try {
				Usertab::deleteAll('id = '.$_POST['id']);
				return 'success';
			}catch (Exception $e){
				return print_r($e->getMessage());
			}
		}
	}
	
	/**
	 * 订单添加自定义标签
	 * @author fanjs
	 */
	public function actionSetusertab(){
		AppTrackerApiHelper::actionLog("Oms-erp", "/order/settab");
		if(\Yii::$app->request->isPost){
			try {
				$order = OdOrder::findOne($_POST['orderid']);
				$order->order_manual_id = $_POST['tabid'];
				if ($order->save()){
					return 'success';
				}else{
					return '添加自定义标签失败';
				}
			}catch (Exception $e){
				return print_r($e->getMessage());
			}
		}
	}
	
	/**
	 * 订单商品添加备注
	 * @author fanjs
	 */
	public function actionAjaxdesc(){
		AppTrackerApiHelper::actionLog("Oms-erp", "/order/desc");
		if(\Yii::$app->request->isPost){
// 			$item = OdOrderItem::findOne($_POST['oiid']);
// 			if (!empty($item)){
// 				$olddesc = $item->desc;
// 				$item->desc = $_POST['desc'];
// 				$item->save();
// 				OperationLogHelper::log('order',$item->order_id,'添加备注','修改备注: ('.$olddesc.'->'.$_POST['desc'] .')',\Yii::$app->user->identity->getFullName());
// 				$ret_array = array (
// 						'result' => true,
// 						'message' => '修改成功'
// 				);
// 				echo json_encode ( $ret_array );
// 				exit();
// 			}
			$order = OdOrder::findOne($_POST['oiid']);
			if (!empty($order)){
				$olddesc = $order->desc;
				$order->desc = $_POST['desc'];
				$order->save();
				OperationLogHelper::log('order',$order->order_id,'添加备注','修改备注: ('.$olddesc.'->'.$_POST['desc'] .')',\Yii::$app->user->identity->getFullName());
				$ret_array = array (
						'result' => true,
						'message' => '修改成功'
				);
				echo json_encode ( $ret_array );
				exit();
			}
		}
	}
	
	/**
	 * 订单编辑
	 * @author fanjs
	 */
	public function actionEdit(){
		//overdue
		return false;// 20161012
		$url = '/order/ebay-order/edit?orderid='.$_REQUEST['orderid'];
		return $this->redirect($url);
		AppTrackerApiHelper::actionLog("Oms-ebay", "/order/edit");
		if (\Yii::$app->request->isPost){
			if (count($_POST['item']['product_name'])==0){
				return $this->render('//errorview','订单必需有相应商品');
			}
			$order = OdOrder::findOne($_POST['orderid']);
			if (empty($order)){
				return $this->render('//errorview','无对应订单');
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
			$old_status = $order->order_status;
			$order->setAttributes($_tmp);
			$new_status = $order->order_status;
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
				$item->ordered_quantity = $item_tmp['quantity'][$key];
				$item->quantity = $item_tmp['quantity'][$key];
				$item->order_source_srn = $item_tmp['order_source_srn'][$key];
				$item->price = $item_tmp['price'][$key];
				$item->update_time = time();
				$item->create_time = is_null($item->create_time)?time():$item->create_time;
				$item->save();
			}
			$order->checkorderstatus();
			//处理weird_status liang 2015-12-26 
			if($old_status!==$new_status && ($new_status!==500 ||$new_status!==600) ){
				$addtionLog = '';
				if(!empty($order->weird_status))
					$addtionLog = ',并自动清除操作超时标签';
				$order->weird_status = '';
			}//处理weird_status end
			$order->save();
			OperationLogHelper::log('order',$order->order_id,'修改订单','编辑订单进行订单修改'.$addtionLog,\Yii::$app->user->identity->getFullName());
			echo "<script language='javascript'>window.opener.location.reload();window.close();</script>";
//			return $this->render('//successview',['title'=>'编辑订单']);
		}
		if (!isset($_GET['orderid'])){
			return $this->render('//errorview',['title'=>'编辑订单','message'=>'链接有误']);
		}
		$order = OdOrder::findOne($_GET['orderid']);
		$paypal_t = OdPaypalTransaction::findOne(['order_id'=>$order->order_id]);
		if (empty($order)||$order->isNewRecord){
			return $this->render('//errorview',['title'=>'编辑订单','message'=>'未找到相应订单']);
		}
		return $this->render('edit',['order'=>$order,'paypal'=>$paypal_t,'countrys'=>StandardConst::$COUNTRIES_CODE_NAME_EN]);
	}
	
	
	
	/**
	 * 订单的手动选择检测
	 * @author fanjs
	 */
	public function actionCheckorderstatus(){
		AppTrackerApiHelper::actionLog("Oms-erp", "/order/checkstatus");
		if (\Yii::$app->request->isPost){
			$orderids = explode(',',$_POST['orders']);
			Helper_Array::removeEmpty($orderids);
			if (count($orderids)>0){
				try {
					$list_clear_platform = []; //需要清除redisear_platform
					foreach ($orderids as $orderid){
						
						$order = OdOrder::findOne($orderid);
						$origin_exception_status = $order->exception_status;
						if ($order->order_status=='200'){
							$order->checkorderstatus(null,1);
							if ($order->save(false)){
								//增加清除 平台redis
								if ((!in_array($order->order_source, $list_clear_platform) )&& ( $order->exception_status != $origin_exception_status)){
									$list_clear_platform[] = $order->order_source;
								}
							}
						}
					}
					
					//left menu 清除redis
					if (!empty($list_clear_platform)){
						foreach ($list_clear_platform as $platform){
							//echo "$platform is reset !";
							RedisHelper::delOrderCache(\Yii::$app->subdb->getCurrentPuid(),$platform,'Menu StatisticData');
						}
						//OrderHelper::clearLeftMenuCache($list_clear_platform);
					}
					return '操作已完成';
				}catch (\Exception $e){
					return $e->getMessage();
				}
			}else{
				return '选择的订单有问题';
			}
		}
	}
	
	/**
	 * 订单合并
	 * @author fanjs
	 */
	public function actionMergeorder(){
		AppTrackerApiHelper::actionLog("Oms-erp", "/order/mergeorder");
		if (\Yii::$app->request->isPost){
			$orderIdList =  $_POST['order_id'];
			$rt = OrderHelper::mergeOrder($orderIdList);
			
			if ($rt['success'] ==false){
				return $this->render('//errorview',['title'=>'订单合并','error'=>$rt['message']]);
			}else{
				echo "<script language='javascript'>alert('Success');window.opener.location.reload();window.close();</script>";
			}
			return;
		}
	}
	
	/**
	 +----------------------------------------------------------
	 * 取消合并订单   action
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh 	2016/06/02				初始化
	 +----------------------------------------------------------
	 **/
	public function actionCancelMergeOrder(){
		if (!empty($_POST['order_id'])){
			$orderIdList =  $_POST['order_id'];
			$rt = OrderHelper::RollbackmergeOrder($orderIdList);
			if($rt['success'] == false){
				return $this->renderJson(['response'=>['success'=>false,'code'=>400,'type'=>'message','timeout'=>2,'message'=>$rt['message']]]);
			}else{
				return $this->renderJson(['response'=>['success'=>true,'code'=>200,'type'=>'message','timeout'=>2,'message'=>'操作成功，拆分订单移动到暂停发货订单中！','reload'=>true]]);
			}
		}else{
			return $this->renderJson(['response'=>['success'=>false,'code'=>400,'type'=>'message','timeout'=>2,'message'=>'找不到有效订单！']]);
		}
		
	}
	
	/**
	 * 订单拆分
	 * @author fanjs
	 */
	public function actionSplitorder(){
		AppTrackerApiHelper::actionLog("Oms-ebay", "/order/splitorder");
		if(\Yii::$app->request->isPost){
			$oldorder = OdOrder::findOne($_POST['orderid']);
			$orderarr = $oldorder->attributes;
			unset($orderarr['order_id']);
			$orderarr['create_time']=time();
			$orderarr['update_time']=time();
			$neworder = new OdOrder();
			//20151119 instead of setAttributes
			$attrs = $oldorder->attributes();
			foreach($orderarr as $k=>$v){
				if(in_array($k,$attrs)) {
					$neworder->$k = $v;
				}
			}
			//20151119 instead of setAttributes
			//$neworder->setAttributes($orderarr);
			$neworder->subtotal=$_POST['new_subtotal'];
			$neworder->shipping_cost=$_POST['new_shipping_cost'];
			$neworder->grand_total=$_POST['new_grand_total'];
			if($neworder->save(false)){
				$oldorder->subtotal=$_POST['old_subtotal'];
				$oldorder->shipping_cost=$_POST['old_shipping_cost'];
				$oldorder->grand_total=$_POST['old_grand_total'];
				$oldorder->save(false);
				//将原订单的商品ID进行更换
				foreach ($_POST['item'] as $key=>$val){
					if ($val=='new'){
						$item = OdOrderItem::findOne($key);
						$item->order_id = $neworder->order_id;
						$item->save(false);
					}
				}
				OperationLogHelper::log('order', $neworder->order_id,'拆分订单','从原订单'.$oldorder->order_id.'拆分出该新订单',\Yii::$app->user->identity->getFullName());
				OperationLogHelper::log('order', $oldorder->order_id,'拆分订单','拆分该原订单,已生成新订单'.$neworder->order_id,\Yii::$app->user->identity->getFullName());
				return $this->render('//successview',['title'=>'拆分订单']);
			}else{
				return $this->render('//errorview',['title'=>'拆分订单','error'=>'新订单生成失败']);
			}
		}
		if (!isset($_GET['orderid'])){
			return $this->render('//errorview',['title'=>'拆分订单','error'=>'传入的订单ID有误']);
		}
		$order = OdOrder::findOne($_GET['orderid']);
		if (empty($order)||$order->isNewRecord){
			return $this->render('//errorview',['title'=>'拆分订单','error'=>'未找到相应订单']);
		}
		if (count($order->items)<2){
			return $this->render('//errorview',['title'=>'拆分订单','error'=>'拆封订单必须超过2件商品']);
		}
		return $this->render('splitorder',['order'=>$order]);
	}
	
	/**
	 * 提交发货，将已付款的订单转为待发货订单
	 * @author fanjs
	 */
	function actionSignwaitsend(){
		AppTrackerApiHelper::actionLog("Oms-erp", "/order/signwaitsend");
		if (\Yii::$app->request->isPost){
			$orderids = explode(',',$_POST['orders']);
			Helper_Array::removeEmpty($orderids);
			if (count($orderids)>0){
				$rt = OrderApiHelper::setOrderShipped($orderids);
				if ($rt['success'] == count($orderids)){
					return "操作成功";
					/*
					return "成功！<br>已经把以下".$rt['success']."个订单提交到物流模块<br>".$rt['message']."<br>请跳转到顶部菜单的‘发货’模块界面，进行批量精细化的物流发货操作，包含
					<br>1）匹配运输服务
					<br>2）报关信息
					<br>3）申请跟踪号-更改运输服务
					<br>4）打印标签
					<br>5）配货单";
					*/
				}else{
					return nl2br($rt['message']);
				}
				/*
				if ($rt['success'] == count($orderids)){
					return "操作成功";
				}else{
					return nl2br($rt['message']);
				}
				*/
			}else{
				return '选择的订单有问题';
			}
		}
	}
	
	/**
	 * 获取订单的自定义标签页面
	 * @author fanjs
	 */
	function actionGetOneTagInfo(){
		$tagdata = [];
		if (!empty($_REQUEST['order_id'])){
			$tagdata = OrderTagHelper::getALlTagDataByOrderId($_REQUEST['order_id']);
		}
		exit(json_encode($tagdata));
	}
	
	/**
	 * 保存某个自定义的标签
	 * @author fanjs
	 */
	function actionSaveOneTag(){
		AppTrackerApiHelper::actionLog("Oms-erp", "/order/savetab");
		if (!empty($_REQUEST['order_id'])){
			$order_id = $_REQUEST['order_id'];
		}else{
			exit(json_encode(['success'=>false, 'message'=>'未知错误1']));
		}
		/*
		if (!empty($_REQUEST['tag_name'])){
			$tag_name = $_REQUEST['tag_name'];
		}else{
			exit(json_encode(['success'=>false, 'message'=>'未知错误2']));
		}
		*/
		if (!empty($_REQUEST['operation'])){
			$operation = strtolower($_REQUEST['operation']);
		}else{
			exit(json_encode(['success'=>false, 'message'=>'未知错误3']));
		}
		
		if (!empty($_REQUEST['color'])){
			$color = strtolower($_REQUEST['color']);
		}else{
			exit(json_encode(['success'=>false, 'message'=>'未知错误4']));
		}
		
		$tag_name = empty($_REQUEST['tag_name']) ? '' : trim($_REQUEST['tag_name']);
		
		$result = OrderTagHelper::saveOneOrderTag($order_id, $tag_name, $operation, $color);
		exit(json_encode($result));
	}
	
	/**
	 * 标签设置好后的更新
	 * @author fanjs
	 */
	function actionUpdateOrderTrInfo(){
		if (!empty($_REQUEST['order_id'])){
				$row = OrderTagHelper::generateTagIconHtmlByOrderId($_REQUEST['order_id']);
				$sphtml['sphtml'] = $row;
				exit(json_encode($sphtml));
		}
	}
	
	/**
	 * 标签设置好后的更新(紧标签)
	 * @author lzhl
	 */
	function actionUpdateOrderTagHtml(){
		if (!empty($_REQUEST['order_id'])){
			$TagStr = OrderTagHelper::generateTagIconHtmlByOrderId($_REQUEST['order_id']);
		}else 
			$TagStr = '';

		if (!empty($TagStr)){
			$TagStr = "<span class='btn_order_tag_qtip' data-order-id='".$_REQUEST['order_id']."' >$TagStr</span>";
		}
		exit($TagStr);
	}
	
	/**
	 * 即时发送站内信的操作
	 * @author fanjs
	 */
	function actionSendmessage(){
		if (\Yii::$app->request->isPost){
			AppTrackerApiHelper::actionLog("Oms-ebay", "/order/sendmessage");
			if (empty($_POST['orderid'])){
				return '未传输相应的议价ID';
			}
			$order = OdOrder::findOne(['order_id'=>$_POST['orderid']]);
			return $this->renderPartial('sendmessage',['order'=>$order]);
		}
	}
	
	/**
	  * 即时发送站内信的操作逻辑
	 * @author fanjs
	 */
	function actionAjaxsendmessage(){
		if(\Yii::$app->request->isPost){
			if (empty($_POST['orderid'])){
				return '未传输相应的订单ID';
			}
			$order = OdOrder::findOne($_POST['orderid']);
			$item=OdEbayTransaction::findOne(['order_id'=>$_POST['orderid']]);
			$itemid = $item->itemid;
			$buyer = $order->source_buyer_user_id;
			$api = new addmembermessageaaqtopartner();
			$ebayuser = SaasEbayUser::find()->where('selleruserid=:s',[':s'=>$order->selleruserid])->one ();
			$api->resetConfig($ebayuser->DevAcccountID); //授权配置
			$token = $ebayuser->token;
			$result = $api->api ($token,$itemid,$_POST['content'],$_POST['type'],$buyer,$_POST['title'],$_POST['mail']);
			if ($api->responseIsSuccess ()){
				return 'success';
			}else{
				return $result['Errors']['LongMessage'];
			}
		}
	}
	
	
	
	/**
	 * ajax处理同步订单优先请求的处理
	 * @author fanjs
	 */
	function actionAjaxsyncmt(){
		if (\Yii::$app->request->isPost){
			try {
				QueueGetorder::updateAll(['updated'=>'1'],'status !=2 and selleruserid ="'.$_POST['selleruserid'].'"');
			}catch (Exception $e){
				return json_encode(['ack'=>'failure','msg'=>$e->getMessage()]);
			}
			return json_encode(['ack'=>'success']);
		}
	}
	
	/**
	 * 手动创建订单
	 * @author
	 */
	function actionAddOrderSelf(){
		if(\Yii::$app->request->isPost){
			if (count($_POST['item']['product_name'])==0){
				return $this->render('//errorview','订单必需有相应商品');
			}
			$order = new OdOrder();
			$item_tmp = $_POST['item'];
			$_tmp = $_POST;
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
				if(strlen($val)==0){
					continue;
				}
				$item->order_id = $order->order_id;
				$item->product_name = $item_tmp['product_name'][$key];
				$item->sku = $item_tmp['sku'][$key];
				$item->ordered_quantity = $item_tmp['quantity'][$key];
				$item->quantity = $item_tmp['quantity'][$key];
				$item->price = $item_tmp['price'][$key];
				$item->update_time = time();
				$item->create_time = is_null($item->create_time)?time():$item->create_time;
				$item->save();
			}
//			$order->checkorderstatus();
			$order->save();
			OperationLogHelper::log('order',$order->order_id,'手动新建订单','手动新建订单',\Yii::$app->user->identity->getFullName());
//			echo "<script language='javascript'>window.opener.location.reload();window.close();</script>";
			echo "<script language='javascript'>alert('已完成');</script>";
		}
		return $this->render('addorderself');
	}
	
	/**
	 * 测试action，
	 */
	function actionTest(){
		$odorder = OdOrder::findOne(['order_source'=>'ebay','order_source_order_id'=>'123321']);
		var_dump(empty($odorder));die();
//		$odOrder = empty($odOrder) ?OdOrder::find()->where("`order_source` = :os AND `order_source_order_id` = :osoi",[':os'=>$os['order_source'],':osoi'=>$os['order_source_order_id']])->one(): '';
	}
	
	/**
	 * order/update-order-address
	 */
	function actionUpdateOrderAddress(){
		$selleruserid=$_GET['selleruserid'];
		$orderid=$_GET['orderid'];
		if(empty($orderid)) die('No orderid Input .');
		if($selleruserid){
			$eu=SaasEbayUser::findOne(['selleruserid'=>$selleruserid]);
		}else{
			die('No selleruserid Input .');
		}
	
		$api=new getorders();
		$api->resetConfig($eu->DevAcccountID);
		$api->eBayAuthToken=$eu->token;
		$api->_before_request_xmlarray['OrderID']=$orderid;
		$r=$api->api();
		echo "<pre>";
		print_r(@$r['OrderArray']['Order']['ShippingAddress']);
		echo "</pre>";
		/**/
		if (isset($r['OrderArray']['Order']['ShippingAddress'])){
			//$orderModel = OdOrder::find()->where(['order_source_order_id'=>$orderid ])->andWhere(['order_capture'=>'N'])->One();
			$orderModel = $_GET['erp_order_id'];
			echo "orderid = ".$orderModel->order_id;
			$addressArr = $r['OrderArray']['Order']['ShippingAddress'];
			$paypalAddress = [
			'consignee'=>$addressArr['Name'],
			//'consignee_email'=>$PT->email,
			'consignee_country'=>empty($addressArr['CountryName'])?$addressArr['Country']:$addressArr['CountryName'],
			'consignee_country_code'=>$addressArr['Country'],
			'consignee_province'=>$addressArr['StateOrProvince'],
			'consignee_city'=>$addressArr['CityName'],
			'consignee_address_line1'=>$addressArr['Street1'],
			'consignee_address_line2'=>$addressArr['Street2'],
			'consignee_postal_code'=>$addressArr['PostalCode'],
			
			];
			$updateRt = \eagle\modules\order\helpers\OrderUpdateHelper::updateOrder($orderModel, $paypalAddress , false , 'System','地址同步','order');
			
			echo "<pre>";
			print_r($updateRt);
			echo "</pre>";
		}
		
		die;
	}

	
	/**
	 +----------------------------------------------------------
	 * 批量更改运输服务   action
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh 	2015/11/18				初始化
	 +----------------------------------------------------------
	 **/
	public function actionChangeshipmethod(){
		if (!empty($_POST['orderIDList']) && !empty($_POST['shipmethod']) ){
			AppTrackerApiHelper::actionLog("Oms-erp", "/order/changeshipmethod");
			$serviceid = SysShippingService::findOne($_POST['shipmethod']);
			if (!empty($serviceid)||!$serviceid->isNewRecord){
				$rt = OdOrder::updateAll(['default_shipping_method_code'=>$_POST['shipmethod'] , 'default_carrier_code'=>$serviceid->carrier_code ] ,['order_id'=>$_POST['orderIDList']]);
				if (!empty($rt)){
					
					exit(json_encode(['success'=>true,'message'=>'']));
				}else
					exit(json_encode(['success'=>false,'message'=>'修改失败！']));
			}
			exit(json_encode(['success'=>false,'message'=>'数据错误，请联系客服！']));
			
		}
		exit(json_encode(['success'=>false,'message'=>'数据异常，请联系客服！']));
	}//end of actionChangeshipmethod
	
	/**
	 +----------------------------------------------------------
	 * 获取单个订单发票   action
	 +----------------------------------------------------------
	 * @access 		public
	 * @params 		$order_id		订单id
	 * @params		$app			调用的app，默认为oms
	 +----------------------------------------------------------
	 * log			name	date			note
	 * @author		lzhl 	2015/12/18		初始化
	 +----------------------------------------------------------
	 **/
	public function actionOrderInvoice($order_id,$app='oms'){
		
		if(strtolower($app)=='oms')
			$uid = \Yii::$app->subdb->getCurrentPuid();
		else{
			$parmaStr = (isset($_GET['parcel']))?$_GET['parcel']:'';
			$parmaStr = MessageHelper::decryptBuyerLinkParam($parmaStr);
			if(empty($parmaStr)){
				exit('参数丢失!');
			}else{
				$parmas = explode('-', $parmaStr,2);
				if(count($parmas)<2){
					exit('参数丢失!');
				}else{
					$uid = $parmas[0];
					$order_id = $parmas[1];
				}
			}
		}
		if (empty($uid)){
			//异常情况
			return $this->render('//errorview',['title'=>'请先登录','message'=>'您还未登录，不能进行该操作']);
		}
		
		
		$mpdf=new \HTML2PDF('P','A4','en');
		
		if(is_string($order_id)){
			$order_id = str_replace(';', ',', $order_id);
			$order_id_arr = explode(',', $order_id);
		}
		
		foreach ($order_id_arr as $order_id){
			//亚洲字体处理
			$orderModel = OdOrder::findOne($order_id);
			if(!empty($orderModel->consignee_country_code)){
				$toCountry = SysCountry::findOne(strtoupper($orderModel->consignee_country_code));
				//东南亚字体支持
				if(!empty($toCountry->region) && in_array($toCountry->region, ['Asia','Southeast Asia']))
					$mpdf->setDefaultFont('droidsansfallback');
				//泰文，老挝文支持
				if(in_array($orderModel->consignee_country_code,['TH','LA'])){
					$mpdf->setDefaultFont('angsau');
				}
			}
			$text = OrderHelper::pdf_order_invoice($order_id);
			//exit($text);	//test liang
			$mpdf->WriteHTML($text);
		}
		
		$mpdf->Output('order_invoice_'.$orderModel->order_source_order_id.'.pdf');
		exit();
	}
	
	/**
	 +----------------------------------------------------------
	 * 获取多个订单发票   action
	 +----------------------------------------------------------
	 * @access 		public
	 * @params 		$orderids		订单id集合
	 * @params		$app			调用的app，默认为gaoqing
	 +----------------------------------------------------------
	 * log			name	date			note
	 * @author		lrq 	2016/07/29		初始化
	 +----------------------------------------------------------
	 **/
	public function actionOrderlistInvoice($orderids,$type='G')
	{
		$mpdf=new \HTML2PDF('P','A4','en');
		$orderidlist = explode(',',$orderids);
		Helper_Array::removeEmpty($orderidlist);
		if (count($orderids)>0)
		{
			foreach ($orderidlist as $order_id)
			{
				
				//亚洲字体处理
				$orderModel = OdOrder::findOne($order_id);
				if(!empty($orderModel->consignee_country_code)){
					$toCountry = SysCountry::findOne(strtoupper($orderModel->consignee_country_code));
					if(!empty($toCountry->region) && in_array($toCountry->region, ['Asia','Southeast Asia']))
						$mpdf->setDefaultFont('droidsansfallback');
				}
				$text = OrderHelper::pdf_order_invoice($order_id, $type);
				$mpdf->WriteHTML($text);
			}
			
			$mpdf->Output('order_invoice_'.$orderModel->order_source_order_id.'.pdf');
		}
		exit();
	}
	
	public function actionProfitOrder(){
		$uid = \Yii::$app->subdb->getCurrentPuid();
		if (empty($uid)){//异常情况
			exit('您还未登录，不能进行该操作');
		}
 
		if(empty($_REQUEST['order_ids'])){
			exit('请选择需要计算利润的订单');
		}
		
		$order_ids = [];
		if(is_string($_REQUEST['order_ids']))
			$order_ids = explode(',',$_REQUEST['order_ids']);
		elseif(is_array($_REQUEST['order_ids']))
			$order_ids = $_REQUEST['order_ids'];
		
		$check = OrderProfitHelper::checkOrdersBeforProfit($order_ids);
		//$check['success']=true;
		
		if(!empty($check['success']) && empty($check['data']['need_set_price']) && empty($check['data']['need_logistics_cost']) ){
			//所有前提已经设置好的情况，直接计算利润，不再需要前段设置	
			$result = OrderProfitHelper::profitOrderByOrderId($order_ids,1);
			if($result['success'])
				return '统计成功，请刷新页面查看最新数据。';
			else 
				return '有订单利润统计失败：'.$result['message'].'请刷新页面重新统计';
		}else{
			return $this->renderAjax('_set_orders_cost',[
					'order_ids'=>$order_ids,
					'need_set_price'=>empty($check['data']['need_set_price'])?[]:$check['data']['need_set_price'],
					'need_logistics_cost'=>empty($check['data']['need_logistics_cost'])?[]:$check['data']['need_logistics_cost'],
					'exchange_data' => empty($check['data']['exchange'])?[]:$check['data']['exchange'],
					'exchange_loss' => empty($check['data']['exchange_loss'])?[]:$check['data']['exchange_loss'],
				]);
		}
	}
	
	public function actionSetCostAndProfitOrder(){
		AppTrackerApiHelper::actionLog("Oms-erp", "/order/setCostAndProfitOrder");
		$data = $_POST;
		$order_ids = explode(',', $_POST['order_ids']);
		$journal_id = SysLogHelper::InvokeJrn_Create("Order",__CLASS__, __FUNCTION__ , array($order_ids,$data));
		$rtn = OrderProfitHelper::setOrderCost($data, $journal_id);
		if($rtn['success']){//所有成本设置成功，则进入下一步计算订单利润
			
			$price_type = empty($_POST['price_type'])?0:1;
			$rtn = OrderProfitHelper::profitOrderByOrderId($order_ids,$price_type);
			
			$rtn['calculated_profit'] = true;
			
		}else{//有成本设置失败，则返回失败提示，并前段关闭价格设置窗口。
			$rtn['calculated_profit'] = false;
		}
		exit(json_encode($rtn));
	}
	
	public function actionExcel2OrderCost(){
		AppTrackerApiHelper::actionLog("Oms-erp", "/order/excel2OrderCost");
		if (!empty ($_FILES["input_import_file"]))
			$files = $_FILES["input_import_file"];
		else 
			exit(json_encode(['success'=>false,'message'=>'文件上传失败！']));
		
		$type = empty($_REQUEST['type'])?'':trim($_REQUEST['type']);
		if(empty($type) || ($type!=='product_cost' && $type!=='logistics_cost')){
			exit(json_encode(['success'=>false,'message'=>'上传类型未选择，或不支持该类型']));
		}
		try {
			if($type=='product_cost'){
				$EXCEL_PRODUCT_COST_COLUMN_MAPPING = OrderProfitHelper::get_EXCEL_PRODUCT_COST_COLUMN_MAPPING();
				$productsData = \eagle\modules\util\helpers\ExcelHelper::excelToArray($files, $EXCEL_PRODUCT_COST_COLUMN_MAPPING );
				
				$result = ProductApiHelper::importProductCostData($productsData);
			}
			if($type=='logistics_cost'){
				$ORDER_LOGISTICS_COST_COLUMN_MAPPING = OrderProfitHelper::get_EXCEL_ORDER_LOGISTICS_COST_COLUMN_MAPPING();
				$logisticsData = \eagle\modules\util\helpers\ExcelHelper::excelToArray($files, $ORDER_LOGISTICS_COST_COLUMN_MAPPING );
			
				$result = OrderProfitHelper::importOrderLogisticsCostData($logisticsData);
			}
		}
		catch (Exception $e) {
			SysLogHelper::SysLog_Create('Order',__CLASS__, __FUNCTION__,'error',$e->getMessage());
			$result = ['success'=>false,'message'=>'E001：后台处理异常，请联系客服获取支援！'];
		}
		exit(json_encode($result));
	}
	public function actionOmsViewTracker(){
		$invoker = empty($_REQUEST['invoker'])?'':trim($_REQUEST['invoker']);
		if(empty($invoker)){
			exit('E001');
		}
		$uid = \Yii::$app->subdb->getCurrentPuid();
		if(empty($uid)){
			exit('E002');
		}
		
		$called_app='Tracker';
		$func_name = 'OmsViewTracker';
		/*
		$affectRows = TrackingAgentHelper::intCallSum($called_app,$invoker,$func_name,$uid);
		if($affectRows>0)
			exit('E003');
		else 
			*/
			exit(true);
	}
	
	/*
	 * 通过OMS忽略物流号码，使其不再在tracker更新
	 * @author		lzhl		2016/xx/xx		初始化
	 */
	public function actionIgnoreTrackingNo($order_id,$track_no){
		$uid = \Yii::$app->subdb->getCurrentPuid();
		if (empty($uid)){
			//异常情况
			exit('您还未登录，不能进行该操作');
		}
	 
		
		$rtn['success'] = true;
		$rtn['message'] = "";
		
		$transaction = \Yii::$app->get('subdb')->beginTransaction();
		//更新状态到lt_tracking
		$rtn = TrackingApiHelper::changeTrackingStatus($track_no, 'ignored');
		if(!$rtn['success'] && $rtn['message']!=='该物流号不存在'){//该物流号存在，且update失败的话，return
			$transaction->rollBack();
			exit(json_encode($rtn));
		}
		//更新状态到od_order_shipped_v2
		$rtn = OrderApiHelper::setOrderShippedInfo($order_id, $track_no,['sync_to_tracker'=>'Y','tracker_status'=>'ignored']);
		if(!$rtn['success']){
			$transaction->rollBack();
			exit(json_encode($rtn));
		}
		//更新od_order_v2
		$order = OdOrder::findOne($order_id);
		if(empty($order)){
			$transaction->rollBack();
			$rtn['success'] = false;
			$rtn['message'] = "订单数据丢失";
			exit(json_encode($rtn));
		}
		$order->logistic_status = 'ignored';
		$order->logistic_last_event_time = date("Y-m-d H:i:s");
		if($order->weird_status=='tuol'){
			$order->weird_status = '';
			$order->update_time = time();
		}
		if(!$order->save()){
			$transaction->rollBack();
			$rtn['success']= false;
			$rtn['message'].= "订单".$order->order_source_order_id."更新失败:".print_r($order->getErrors());
			exit(json_encode($rtn));
		}
		
		if($rtn['success']){
			$transaction->commit();
			OperationLogHelper::log('order', $order_id,'忽略物流查询','设置为忽略查询物流信息',\Yii::$app->user->identity->getFullName());
			exit(json_encode($rtn));
		}
	}
	
	
	/**
	 * 技术调试同步订单
	 * @author fanjs
	 * @selleruserid 所同步订单的ebay账号
	 * @orderid  所同步订单的ebay订单ID
	 */
	public function actionMtsyncorder(){
		$selleruserid = $_REQUEST['selleruserid'];
		$orderid = $_REQUEST['orderid'];
		$ebay_user = SaasEbayUser::findOne(['selleruserid'=>$selleruserid]);
		
		$api = new getorders();
		$api->eBayAuthToken=$ebay_user->token;
		$api->_before_request_xmlarray['DetailLevel'] = 'ReturnAll';
		$api->_before_request_xmlarray['OrderID'] = [$orderid];
		$result = $api->api();
		
		if ($result['Ack']=='Warning'&&isset($result['Errors']['ErrorCode'])&&$result['Errors']['ErrorCode']=='21917182'){
			return false;
		}
		if (!$api->responseIsFailure()){
			$requestArr=$api->_last_response_xmlarray;
		
//			\Yii::info(print_r($requestArr,1),'requestOrders   _last_response_xmlarray');
		
			if (!isset($requestArr['OrderArray']['Order'])){
				return false;
			}
				
			if(isset($requestArr['OrderArray']['Order']['OrderID'])){
				$OrderArray['Order']=array($requestArr['OrderArray']['Order']);
			}elseif(Helper_xml::isArray($requestArr['OrderArray']['Order'])&&count($requestArr['OrderArray']['Order'])){
				$OrderArray['Order']=$requestArr['OrderArray']['Order'];
			}
			if(count($OrderArray['Order'])){
				$response_orderids=array();
				foreach ($OrderArray['Order'] as $o){
					if(isset($response_orderids[$o['OrderID']])){
						$response_orderids[$o['OrderID']]++;
					}else{
						$response_orderids[$o['OrderID']]=1;
					}
				}
		
				foreach ($OrderArray['Order'] as $o){
					try {
						$api->saveOneOrder($o,$o['OrderID'],$ebay_user,$ebay_user->selleruserid);
					}catch(Exception $ex){
						echo "Error Message :  ". $ex->getMessage()."\n";
					}
					//Yii::log($logstr);
				}
			}
		}else{
			break 1;
		}
	}
	
	/**
	 +----------------------------------------------------------
	 * 取消订单
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh 	2014/05/06				初始化
	 +----------------------------------------------------------
	 **/
	public function actionCancelorder(){
		if (\Yii::$app->request->isPost){
			$orderids = $_POST['orders'];
			Helper_Array::removeEmpty($orderids);
			if (count($orderids)>0){
				try {
					$edit_log = array();
					foreach ($orderids as $order_id){
						$tmpRT = OrderHelper::CancelOneOrder($order_id);
						if ($tmpRT['success']==false){
							//取消失败
							if (empty($error_message)) $error_message = '';
							
							$error_message .= $order_id.':'.$tmpRT['message'];
						}
						else{
							$edit_log[] = $order_id;
						}
					}
					
					if(!empty($edit_log)){
						//写入操作日志
						UserHelper::insertUserOperationLog('order', "取消订单, 订单号: ".implode(', ', $edit_log));
					}
					
					if (!empty($error_message)) return $error_message;
					return '操作已完成';
				}catch (\Exception $e){
					return $e->getMessage();
				}
			}else{
				return '选择的订单有问题';
			}
		}//end of post
	}//end of actionCancelorder
	
	/**
	 +----------------------------------------------------------
	 * 废弃订单
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh 	2015/12/17				初始化
	 +----------------------------------------------------------
	 **/
	public function actionAbandonorder(){
		if (\Yii::$app->request->isPost){
			$orderids = $_POST['orders'];
			Helper_Array::removeEmpty($orderids);
			if (count($orderids)>0){
				try {
					foreach ($orderids as $order_id){
						$tmpRT = OrderHelper::AbandonOrder($order_id);
						
						if ($tmpRT['success']==false){
							//取消失败
							if (empty($error_message)) $error_message = '';
								
							$error_message .= $order_id.':'.$tmpRT['message'];
						}
					}
						
					if (!empty($error_message)) return $error_message;
					return '操作已完成';
				}catch (\Exception $e){
					return $e->getMessage();
				}
			}else{
				return '选择的订单有问题';
			}
		}//end of post
	}//end of actionAbandonorder
	
	/**
	 +----------------------------------------------------------
	 * 暂停发货
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh 	2015/12/17				初始化
	 +----------------------------------------------------------
	 **/
	public function actionSuspenddelivery(){
		if (\Yii::$app->request->isPost){
			$orderids = $_POST['orders'];
			$module = isset($_POST['m'])?$_POST['m']:'order';
			$action = isset($_POST['a'])?$_POST['a']:TranslateHelper::t('OMS暂停发货');
			Helper_Array::removeEmpty($orderids);
			if (count($orderids)>0){
				try {
					$r = OrderApiHelper::suspendOrders($orderids,$module,$action);
		
					if (!$r['success']) return $r['message'];
					return '操作已完成';
				}catch (\Exception $e){
					return $e->getMessage();
				}
			}else{
				return '选择的订单有问题';
			}
		}//end of post
	}//end of actionSuspenddelivery
	
	/**
	 +----------------------------------------------------------
	 * 标记缺货
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh 	2015/12/17				初始化
	 +----------------------------------------------------------
	 **/
	public function actionOutofstock(){
		if (\Yii::$app->request->isPost){
			$orderids = $_POST['orders'];
			$module = isset($_POST['m'])?$_POST['m']:'order';
			$action = isset($_POST['a'])?$_POST['a']:TranslateHelper::t('OMS标记缺货');
			Helper_Array::removeEmpty($orderids);
			if (count($orderids)>0){
				try {
					$r = OrderHelper::setOrderOutOfStock($orderids,$module,$action);
		
					if (!$r['success']) return $r['message'];
					return '操作已完成';
				}catch (\Exception $e){
					return $e->getMessage();
				}
			}else{
				return '选择的订单有问题';
			}
		}//end of post
	}//end of actionOutofstock
	
	/**
	 +----------------------------------------------------------
	 * 不合并发货
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh 	2015/12/17				初始化
	 +----------------------------------------------------------
	 **/
	public function actionSkipmerge(){
		if (\Yii::$app->request->isPost){
			$orderids = $_POST['orders'];
			Helper_Array::removeEmpty($orderids);
			if (count($orderids)>0){
				try {
					$error_message = OrderHelper::skipMergeOrder($orderids);
		
					if (!empty($error_message)) return $error_message;
					return '操作已完成';
				}catch (\Exception $e){
					return $e->getMessage();
				}
			}else{
				return '选择的订单有问题';
			}
		}//end of post
	}//end of actionSkipmerge
	
	/**
	 +----------------------------------------------------------
	 * 生成商品
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh 	2016/01/15				初始化
	 +----------------------------------------------------------
	 **/
	public function actionGenerateProduct(){
		/*
		if (\Yii::$app->request->isPost){
			$orderids = \Yii::$app->request->post('orderids');
		}else{
			$orderids = \Yii::$app->request->get('order_id');
		}
		Helper_Array::removeEmpty($orderids);
		if (count($orderids)==0){
			return json_encode(array('result'=>false,'message'=>TranslateHelper::t('未选择订单！')));
		}
		$orders=OdOrder::find()->where(['order_id'=>$orderids])->all();
		//检查商品是否重复生成  
		foreach ($orders as $order){
			$checkProductExist = OrderHelper::_autoCompleteProductInfo($order->order_id,'order','生成sku');
			if ($checkProductExist['success'] == false){
				return json_encode(array('result'=>false,'message'=> $order->order_id.' '.$checkProductExist['message'].' \n'));
			}
			
		}
		return json_encode(array('result'=>true,'message'=>TranslateHelper::t('生成sku成功')));
		*/
		
		if(!empty($_REQUEST['sku']) && !empty($_REQUEST['itemid'])){
			$item = OdOrderItem::findOne($_REQUEST['itemid']);
			
			$rt = OrderHelper::generateProductByOrderItem($item ,$_REQUEST['sku'] );
		}else{
			$rt = ['success'=>false , 'message'=>'参数不正确！'];
		}
		exit(json_encode($rt));
	}
	
	
	/**
	 +----------------------------------------------------------
	 * 设置商品sku页面
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh 	2016/08/26				初始化
	 +----------------------------------------------------------
	 **/
	public function actionGenerateProductBox(){
		if (!empty($_REQUEST['orderItemId'])){
			return $this->renderPartial('GenerateProductBox.php',['itemid'=>$_REQUEST['orderItemId']]);
		}else{
			return "没有有效的商品";
		}
	}//end of function actionGenerateProductBox

	/**
	 +----------------------------------------------------------
	 * 修改仓库和运输服务
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh 	2015/12/22				初始化
	 +----------------------------------------------------------
	 **/
	public function actionShowWarehouseAndShipmentMethodBox(){
		$orderIdList = @$_REQUEST['orderIdList'];
// 		$shipmethodList = CarrierApiHelper::getShippingServices();
/*
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
		
		//检查仓库是自营还是第三方
		$allWHList = InventoryApiHelper::getAllWarehouseInfo();
		$locList = [];
		foreach($allWHList as $whRow){
			$locList[$whRow['warehouse_id']] = $whRow['is_oversea'];
		}
		*/
		list($shipmethodList, $warehouseList , $locList) = OrderHelper::getWarehouseAndShipmentMethodData();
		
		
		return $this->renderPartial('set_wahrehouse_and_shipment_method' , ['shipmethodList'=>$shipmethodList , 'warehouseList'=>$warehouseList , 'orderIdList'=>$orderIdList , 'locList'=>$locList ] );
	}//end of actionShowWarehouseAndShipmentMethodBox
	
	/**
	 +----------------------------------------------------------
	 * 修改仓库和运输服务
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh 	2015/12/30				初始化
	 +----------------------------------------------------------
	 **/
	public function actionChangeWarehouseAndShipmentMethod(){
		if (!empty($_REQUEST['orderIdList']) && isset($_REQUEST['warehouse']) && ! empty($_REQUEST['shipmentMethod'])){
			
			AppTrackerApiHelper::actionLog("Oms-erp", "/order/changeshipmethod");
			$serviceid = SysShippingService::findOne($_REQUEST['shipmentMethod']);
			
			if (isset($_REQUEST['isUpload'])){
				$isUpload = $_REQUEST['isUpload'];
			}else{
				$isUpload = '0';
			}
			
			//用于判断相关的订单是否符合可以更改运输服务 S  20170904hqw
			$tmp_error_orderid = array();
			$tmp_Orders = OdOrder::find()->select('order_id,reorder_type,order_capture')->where(['order_id'=>$_REQUEST['orderIdList']])->asArray()->all();
			
			if(count($tmp_Orders) > 0){
				foreach ($tmp_Orders as $tmp_Order_one){
					if(($tmp_Order_one['order_capture'] == 'Y') && ($tmp_Order_one['reorder_type'] == 'after_shipment')){
						if(in_array($serviceid->carrier_code, array('lb_epacket','lb_ebaytnt','lb_ebayubi'))){
							$tmp_error_orderid[] = $tmp_Order_one['order_id'];
						}
					}
				}
			}
			
			if(count($tmp_error_orderid) > 0){
				exit(json_encode(['success'=>false,'message'=>'已出库订单补发不能再使用该渠道，小老板订单号：'.implode(',', $tmp_error_orderid)]));
			}
			//用于判断相关的订单是否符合可以更改运输服务 E
			
			if ($isUpload ==='1'){
				$rt = OdOrder::updateAll(['order_status'=>OdOrder::STATUS_WAITSEND, 'carrier_step'=>OdOrder::CARRIER_CANCELED,'tracking_number'=>''  ] ,['order_id'=>$_REQUEST['orderIdList']]);
				OperationLogHelper::batchInsertLog('order', $_REQUEST['orderIdList'], '重新上传','订单状态变成【'.OdOrder::$status[OdOrder::STATUS_WAITSEND].'】');
				$tmpMsg = "并重新上传";
			}else{
				$tmpMsg ='';
			}
			
			if (!empty($serviceid)||!$serviceid->isNewRecord){
				//should update require order qty item
				$errorMsg = '';
				try{
					$changeWarehouseOrder = OdOrder::find()->where(['order_id'=>$_REQUEST['orderIdList']])->andWhere(['<>','default_warehouse_id',$_REQUEST['warehouse']])->all();
					$updateQtyItemList = [];
					foreach($changeWarehouseOrder as $tmpOrder){
						$updateQtyItemList [$tmpOrder->default_warehouse_id] = [];
						foreach($tmpOrder->items as $item){
							if (!empty($item->root_sku)){
								$rootSKU = $item->root_sku;
								if (isset($updateQtyItemList [$tmpOrder->default_warehouse_id] [$rootSKU])){
									$updateQtyItemList [$tmpOrder->default_warehouse_id] [$rootSKU] += $item['quantity'];
								}else{
									$updateQtyItemList [$tmpOrder->default_warehouse_id] [$rootSKU] = $item['quantity'];
								}
							}
							
							//$sku = empty($item['sku'])?$item['product_name']:$item['sku'];
							/*20170321start
							 
							list($ack , $message , $code , $rootSKU ) = array_values(OrderGetDataHelper::getRootSKUByOrderItem($item));
							if (empty($ack)) $errorMsg .= " ".$message ;
							
							if (isset($updateQtyItemList [$tmpOrder->default_warehouse_id] [$rootSKU])){
								$updateQtyItemList [$tmpOrder->default_warehouse_id] [$rootSKU] += $item['quantity'];
							}else{
								$updateQtyItemList [$tmpOrder->default_warehouse_id] [$rootSKU] = $item['quantity'];
							}
							20170321end*/
							
							
						}
					}
						
					if (!empty($updateQtyItemList)){
						foreach($updateQtyItemList as $OriginWHID=>$tmpItemList){
							foreach($tmpItemList as $sku=>$qty){
								list($ack , $code , $message  )  = array_values(OrderBackgroundHelper::updateUnshippedQtyOMS($sku, $OriginWHID, $_REQUEST['warehouse'], $qty, $qty));
								if (empty($ack)) $errorMsg .= " ".$message ;
							}
						}
					}
						
				}catch(\Exception $e){
					$errorMsg .= " 内部错误";
					\Yii::error(__FUNCTION__." Error :".$e->getMessage()." line no ".$e->getLine(),'file');
				}

				$TrackArr=empty($_REQUEST['trackArr'])?'':$_REQUEST['trackArr'];
				if(!empty($TrackArr) && !empty($TrackArr[0])){
					$TrackingNoManual_paramsArr=array(
							'tracking_number'=>'',
							'tracking_link'=>'',
							'shipping_method_code'=>'',
							'shipping_method_name'=>'',
							'description'=>'',
					);
				
					foreach ($TrackArr as $trKeys=>$TrackArrone){
						if($trKeys===0)
							$TrackingNoManual_paramsArr['tracking_number']=$TrackArrone;
						else if($trKeys===1)
							$TrackingNoManual_paramsArr['shipping_method_name']=$TrackArrone;
						else if($trKeys===2)
							$TrackingNoManual_paramsArr['tracking_link']=$TrackArrone;
						else if($trKeys===3)
							$TrackingNoManual_paramsArr['shipping_method_code']=$TrackArrone;
						else if($trKeys===4){
							if($TrackArrone=='Manual'){
								$TrackingNoManual_paramsArr['default_shipping_method_code'] = 'manual_tracking_no';
								$_REQUEST['shipmentMethod']='manual_tracking_no';
							}else{
								$TrackingNoManual_paramsArr['default_shipping_method_code'] = $_REQUEST['shipmentMethod'];
							}
						}
					}
				
					$TrackingNoManual_Orderid=empty($_REQUEST['orderIdList'][0])?'':$_REQUEST['orderIdList'][0]; //因为编辑订单只能一张一张编辑
					$TrackingNoManual_rt=\eagle\modules\carrier\helpers\CarrierOpenHelper::saveTrackingNoManual($TrackingNoManual_Orderid,$TrackingNoManual_paramsArr);
				}

				//$OldOrderList = OdOrder::find()->select(['order_id','default_shipping_method_code','default_carrier_code','default_warehouse_id' ])->where(['order_id'=>$_REQUEST['orderIDList']])->asArray()->all();
				$rt = OdOrder::updateAll(['default_shipping_method_code'=>$_REQUEST['shipmentMethod'], 'default_carrier_code'=>$serviceid->carrier_code   , 'default_warehouse_id'=>$_REQUEST['warehouse'] ] ,['order_id'=>$_REQUEST['orderIdList']]);
				
				if (!empty($rt)){
					
					
					foreach($_REQUEST['orderIdList'] as $orderid){
						OperationLogHelper::log('order',$orderid,'修改仓库和运输服务','修改仓库为:'.@$_REQUEST['warehouseName'].' ,修改运输服务 为:'.@$_REQUEST['shipmentMethodName'].$tmpMsg,\Yii::$app->user->identity->getFullName());
					}
					
					//修改运输服务的时候，把之前的错误信息覆盖掉
					$rt_carrier_error = OdOrder::updateAll(['carrier_error'=>''] ,['order_id'=>$_REQUEST['orderIdList']]);
					
					if (empty($errorMsg)){
						exit(json_encode(['success'=>true,'message'=>'']));
					}else{
						exit(json_encode(['success'=>false,'message'=>$errorMsg]));
					}
					
				}else
					exit(json_encode(['success'=>true,'message'=>'已经修改，无重复修改！']));
			}
			exit(json_encode(['success'=>false,'message'=>'数据错误，请联系客服！']));
			
		}else{
			exit(json_encode(['success'=>false , 'message'=>'E1 内部错误，请联系客户跟进处理！']));
		}
	}//end of actionChangeWarehouseAndShipmentMethod
	
	/**
	 +----------------------------------------------------------
	 * 订单重发
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh 	2015/12/23				初始化
	 +----------------------------------------------------------
	 **/
	public function actionReorder(){
		if (!empty($_REQUEST['orderIdList'])){
				
			if (is_array($_REQUEST['orderIdList'])){
				$rt = OrderHelper::reorder($_REQUEST['orderIdList']);
				exit(json_encode(['success'=>($rt['success'] == count($_REQUEST['orderIdList'])),'message'=>$rt['message']]));
				
			}else{
				
				exit(json_encode(['success'=>false,'message'=>'E001 内部错误， 请联系客服！']));
			}
		}else{
			exit(json_encode(['success'=>false,'message'=>'没有选择订单！']));
		}
	}//end of actionReorder
	
	/**
	 +----------------------------------------------------------
	 * 复制订单
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh 	2015/12/23				初始化
	 +----------------------------------------------------------
	 **/
	public function actionCopyOrder(){
		if (!empty($_REQUEST['orderIDList'])){
	
			if (is_array($_REQUEST['orderIDList'])){
				$rt = OrderHelper::copyOrder($_REQUEST['orderIDList']);
				exit(json_encode(['success'=>($rt['success'] == count($_REQUEST['orderIDList'])),'message'=>$rt['message']]));
	
			}else{
	
				exit(json_encode(['success'=>false,'message'=>'E001 内部错误， 请联系客服！']));
			}
		}else{
			exit(json_encode(['success'=>false,'message'=>'没有选择订单！']));
		}
	}//end of actionCopyOrder
	
	/**
	 +----------------------------------------------------------
	 * 批量添加备注
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh 	2015/12/24				初始化
	 +----------------------------------------------------------
	 **/
	public  function actionShowAddMemoBox(){
		if (!empty($_REQUEST['orderIdList'])){
			
			if (is_array($_REQUEST['orderIdList'])){
				$orderList  = OdOrder::find()->where(['order_id'=>$_REQUEST['orderIdList']])->asArray()->all();
				return $this->renderPartial('_addMemoBox.php' , ['orderList'=>$orderList] );
			}else{
				return $this->renderPartial('//errorview','E001 内部错误， 请联系客服！');
			}
		}else{
			return $this->renderPartial('//errorview','没有选择订单！');
		}
	}//end of actionShowAddMemoBox
	
	/**
	 +----------------------------------------------------------
	 * 批量保存订单备注
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh 	2015/12/25				初始化
	 +----------------------------------------------------------
	 **/
	public function actionBatchSaveOrderDesc(){
		if (!empty($_REQUEST['orderList'])){
			$orderIdList = [];
			$MemoList = [];
			$err_msg = "";
			foreach ($_REQUEST['orderList'] as $row){
				$orderIdList[] = $row['order_id'];
				$MemoList[(int)$row['order_id']]  = $row['memo']; // linux 下对00开头敏感
			}
			
			$OrderList = OdOrder::find()->where(['order_id'=>$orderIdList])->all();
			foreach($OrderList as $OrderModel){
				if (isset($MemoList[(int)$OrderModel->order_id])){
					$rt = OrderHelper::addOrderDescByModel($OrderModel, $MemoList[(int)$OrderModel->order_id], 'order', '添加备注');
					
					$OrderModel->desc = $MemoList[(int)$OrderModel->order_id];
					if ($rt['success'] == true){
						//OperationLogHelper::log('order',$OrderModel->order_id,'添加备注','修改备注: ('.$olddesc.'->'.$OrderModel->desc .')',\Yii::$app->user->identity->getFullName());
					}else{
						$err_msg .= $OrderModel->order_id." 添加备注失败！";
					}
				}else{
					$err_msg .= $OrderModel->order_id.'找不到相关的备注<br>';
				}
				
			}
			if (!empty($OrderList)){
				$result = ['success'=>empty($err_msg) , 'message'=>$err_msg];
			}else{
				$result = ['success'=>false , 'message'=>'E002找不到对应 的订单！'];
			}
			
		}else{
			$result = ['success'=>false , 'message'=>'E001内部错误， 请联系客服！'];
		}
		exit(json_encode($result));
	}//end of actionBatchSaveOrderDesc


	/**
	 * 显示发货地的弹框
	 * @return string
	 * akirametero
	 */
	public  function actionShowAddPointOrigin(){
		if (!empty($_REQUEST['orderIdList'])){
			if (is_array($_REQUEST['orderIdList'])){
				$countryList = StandardConst::$COUNTRIES_CODE_NAME_EN;
				$orderList  = OdOrder::find()->where(['order_id'=>$_REQUEST['orderIdList']])->asArray()->all();
				return $this->renderPartial('_addPointOrigin.php' , ['orderList'=>$orderList,'countryList'=>$countryList] );
			}else{
				return $this->renderPartial('//errorview','E001 内部错误， 请联系客服！');
			}
		}else{
			return $this->renderPartial('//errorview','没有选择订单！');
		}
	}//end of actionShowAddPointOrigin

	/**
	 * 批量保存发货地啊
	 * akirametero
	 */
	public function actionBatchSaveOrderPointOrigin(){
		if (!empty($_REQUEST['orderList'])){

			$orderIdList = [];
			$OriginList = [];
			$err_msg = "";
			foreach ($_REQUEST['orderList'] as $row){
				$orderIdList[] = $row['order_id'];
				$OriginList[(int)$row['order_id']]  = $row['select_country']; // linux 下对00开头敏感
			}


			$OrderList = OdOrder::find()->where(['order_id'=>$orderIdList])->all();
			foreach($OrderList as $OrderModel){
				if (isset($OriginList[(int)$OrderModel->order_id])){
					$origin= $OriginList[(int)$OrderModel->order_id];

					if (!empty( $OriginList[(int)$OrderModel->addi_info] )){
						$addInfo = json_decode($OriginList[(int)$OrderModel->addi_info],true);
					}else{
						$addInfo = [];
					}
					$addInfo['order_point_origin']= $origin;
					$update= OdOrder::findOne( (int)$OrderModel->order_id );
					$update->addi_info= json_encode( $addInfo );
					$update->update(false);
				}else{
					$err_msg .= $OrderModel->order_id.'找不到相关的备注<br>';
				}

			}
			if (!empty($OrderList)){
				$result = ['success'=>empty($err_msg) , 'message'=>$err_msg];
			}else{
				$result = ['success'=>false , 'message'=>'E002找不到对应 的订单！'];
			}

		}else{
			$result = ['success'=>false , 'message'=>'E001内部错误， 请联系客服！'];
		}
		exit(json_encode($result));
	}//end of actionBatchSaveOrderDesc


	
	/**
	 +----------------------------------------------------------
	 * 库存处理
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh 	2015/12/30				初始化
	 +----------------------------------------------------------
	 **/
	public function actionShowStockManageBox(){
		if (!empty($_REQUEST['orderIdList'])){
			$orderIdList = $_REQUEST['orderIdList'];
			$OrderList = OdOrder::find()->select(['order_id', 'default_warehouse_id'])->where(['order_id'=>$orderIdList])->asArray()->all();
			
			$OrderGroup = [];
			//选中的仓库归组
			foreach($OrderList as $oneOrder){
				$OrderGroup [$oneOrder['default_warehouse_id']] [] = $oneOrder['order_id'];
				//保存订单商品冗余数据
				OrderApiHelper::saveOrderGoods($oneOrder['order_id']);
			}
			
			$OrderItemList = OdOrderItem::find()->where(['order_id'=>$orderIdList])->asArray()->all();
			//重新排列order item 数据
			$ItemListMapping = [];
			$ItemInfoGroup = []; //商品信息记录
			foreach($OrderItemList as $anItem){
				$ItemListMapping[$anItem['order_id']][] = $anItem;
				if (empty($ItemInfoGroup[$anItem['sku']]))
					$ItemInfoGroup[$anItem['sku']] = $anItem;
			}
			
			//统计需要的商品数量
			$ItemGroup = [];//统计后的商品入库结果
			
			$ProductStock = []; //商品库存
			
			foreach($OrderGroup as $warehouse_id => $aGroup){
				//统计订单的数量
				$ItemGroup[$warehouse_id] = [];
				foreach($aGroup as $order_id){
					$skus = OdOrderGoods::findAll(['order_id'=>$order_id]);
					foreach($skus as $tmpItem){
						if (isset($ItemGroup[$warehouse_id][$tmpItem['sku']])){
							$ItemGroup[$warehouse_id][$tmpItem['sku']] += $tmpItem['quantity'];
						}else{
							$ItemGroup[$warehouse_id][$tmpItem['sku']] = $tmpItem['quantity'];
						}
					}
				}
				
				//获取库存
				foreach ($ItemGroup[$warehouse_id] as $sku=>$qty){
					//性能有待忧化
					$ProductStock[$warehouse_id][$sku] = InventoryHelper::getProductInventory($sku,$warehouse_id);
				}
			}
			
			
			
		}
		$warehouseids = InventoryApiHelper::getWarehouseIdNameMap();
		
		
		
		return $this->renderPartial('order_item_import_stock.php' , ['ItemGroup'=>$ItemGroup ,  'warehouseids'=>$warehouseids , 'ItemInfoGroup'=>$ItemInfoGroup , 'ProductStock'=>$ProductStock ] );
	}//end of actionShowInStockBox
	
	/**
	 +----------------------------------------------------------
	 * oms生成入库单
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh 	2015/12/30				初始化
	 +----------------------------------------------------------
	 **/
	public function actionCreateStockIn(){
		
		if (!empty($_REQUEST['stockInList'])){
			$rtn = ['success'=>true, 'message'=>''];
			$ischeck = false;
			foreach($_REQUEST['stockInList'] as $warehouseID=>$items){
				$info = [
				'stockchangetype'=>1, //"入库"
				'stockchangereason'=>101,//"采购入库"
				'stock_change_id'=>'OMS'.$warehouseID."T".time(),
				'prods'=>$items,
				'comment'=>'',
				'warehouse_id'=>$warehouseID,
					
				];
				$result = InventoryApiHelper::createNewStockIn($info);
				
				if ($result['success']){
					//保存成功，
					$ischeck = true; //需要检查order 的状态
					
				}else{
					//保存失败返回失败信息
					$rtn['success'] = false;
					$rtn['message'] .= $result['message'];
				}
			}
			
			if ($ischeck && ! (empty($_REQUEST['orderIdList']))){
				//检查order 的状态
				$OrderList = OdOrder::findAll($_REQUEST['orderIdList']);
				foreach($OrderList as $order){
					$order->checkorderstatus('System');
				}
			}
			exit(json_encode($rtn));
			
		}else{
			exit(json_encode(['success'=>false, 'message'=>'E001 系统异常，请联系客服']));
		}
		
		
	}//end of actionCreateStockIn
	
	/**
	 +----------------------------------------------------------
	 * 根据仓库id获取该仓库支持的运输服务
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		zwd 	2016/3/10				初始化
	 +----------------------------------------------------------
	 **/
	public function actionGetShippingMethodByWarehouseid(){
		/*
		 $shippingMethodInfo = CarrierOpenHelper::getShippingMethodNameInfo(array('proprietary_warehouse'=>$_POST['warehouse_id']), -1);
		 $shippingMethodInfo = $shippingMethodInfo['data'];
		
		if(!empty($shippingMethodInfo)){
			$shipp_arr = [];
			foreach ($shippingMethodInfo as $id=>$ship){
				$shipp_arr[$id] = $ship['service_name'];
			}
			return json_encode($shipp_arr);
		}
		return '';
		 */
		
		if (isset($_POST['warehouse_id']) && trim($_POST['warehouse_id'] != '')){
			$result = CarrierOpenHelper::getShippingServiceIdNameMapByWarehouseId($_POST['warehouse_id']);
			exit(json_encode($result));
		}else{
			exit(json_encode([]));
			
		}
	}
	/**
	 +----------------------------------------------------------
	 * 标记发货中的订单未已完成，此功能主要是把实际已近发货但是订单还在发货中的订单批量修改订单状态，没有在小老板系统确认发货的订单会需要此功能
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		million 	2016/3/18				初始化
	 +----------------------------------------------------------
	 **/
	public function actionSigncomplete(){
		if (\Yii::$app->request->isPost){
			$orderids = \Yii::$app->request->post('orderids');
		}else{
			$orderids = \Yii::$app->request->get('order_id');
		}
		Helper_Array::removeEmpty($orderids);
		if (count($orderids)==0){
			return json_encode(array('result'=>false,'message'=>TranslateHelper::t('未选择订单！')));
		}
		
		OrderApiHelper::completeOrder($orderids);
		return json_encode(array('result'=>true,'message'=>TranslateHelper::t('操作成功!立即刷新页面?')));
	}
	
	/**
	 +----------------------------------------------------------
	 * oms 导入物流单号 界面
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh 	2015/12/30				初始化
	 +----------------------------------------------------------
	 **/
	 public function actionImportTracknoBox(){
		 return $this->renderPartial('importTracknoBox');
	 }

	/**
	 +----------------------------------------------------------
	 * 生成商品
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh 	2016/05/27				初始化
	 +----------------------------------------------------------
	 **/
	public function actionShowChangeItemDeclarationInfoBox(){
		if (!empty($_REQUEST['orderIdList'])){
			$NotSKUList = CdiscountOrderInterface::getNonDeliverySku();
			$ItemList = OdOrderItem::find()->select(['photo_primary','sku','product_name','order_id','order_item_id'])->distinct(true)->where(['order_id'=>$_REQUEST['orderIdList']])->andwhere(['not in',"ifnull(sku,'')",$NotSKUList])->asArray()->all();
			
			//读取报关信息
			$declaration_list=array();
			$List = OdOrder::find()->where(['order_id'=>$_REQUEST['orderIdList']])->all();
			foreach ($List as $Listone){
				$declaration_list[intval($Listone->order_id)]=CarrierDeclaredHelper::getOrderDeclaredInfoByOrder($Listone);
			}
			//echo OdOrderItem::find()->select(['photo_primary','sku' , 'product_name'])->distinct(true)->where(['order_id'=>$_REQUEST['orderIdList']])->andwhere(['not in',"ifnull(sku,'')",$NotSKUList])->createCommand()->getRawSql();
			
			//OrderHelper::_autoCompleteProductInfo($_REQUEST['orderIdList'],'order','报关信息');
			// $extendData 初始化
			$productData = [];
							
			//检查商品是否重复生成
			foreach($ItemList as &$oditem){
				//检查当前的订单商品是否 已经生成到商品库中或者 已经绑定 别名
// 				if (!empty($oditem['sku']))
// 					$key = $oditem['sku'];
// 				else 
// 					$key = $oditem['product_name']; //sku 为空， 使用product name
// 				$productData[$oditem['order_item_id']] = ProductApiHelper::getProductInfo($key);
				
				$productData[$oditem['order_item_id']]['order_id'] = $oditem['order_id'];
				$productData[$oditem['order_item_id']]['order_item_id'] = $oditem['order_item_id'];
				//订单有报关信息取报关信息
				$order_id_int=intval($oditem['order_id']);
				if(isset($declaration_list[$order_id_int]) && $declaration_list[$order_id_int][$oditem['order_item_id']]['not_declaration']==0){
					$declaration = $declaration_list[$order_id_int][$oditem['order_item_id']]['declaration'];
					foreach($declaration as $declaration_keys=>$declarationone)
						$productData[$oditem['order_item_id']][$declaration_keys]=$declarationone;
				}
			}//end of each orderItem

			return $this->renderPartial('showchangeitemdeclarationinfobox',['items'=>$ItemList  , 'productData'=>$productData ,'OrderIdList'=>$_REQUEST['orderIdList']]);
		}else{
			return '选择的订单有问题';
		}
	
	}
	
	/**
	 +----------------------------------------------------------
	 * 保存 报关信息
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh 	2016/05/27				初始化
	 +----------------------------------------------------------
	 **/
	public function actionBatchSaveDeclarationInfo(){
		$NameCNList = array_combine($_REQUEST['order_itemid'], $_REQUEST['nameCN']);
		$NameENList = array_combine($_REQUEST['order_itemid'], $_REQUEST['nameEN']);
		$WeightList = array_combine($_REQUEST['order_itemid'], $_REQUEST['weight']);
		$PriceList = array_combine($_REQUEST['order_itemid'], $_REQUEST['price']);
		$Order_itemid = array_combine($_REQUEST['order_itemid'], $_REQUEST['order_itemid']);
		//$ProdNameCNList = array_combine($_REQUEST['order_itemid'], $_REQUEST['ProdNameCN']);
		$ischangeList = array_combine($_REQUEST['order_itemid'], $_REQUEST['json_itemid']);
		$skuList=array_combine($_REQUEST['order_itemid'], $_REQUEST['sku']);
		$codeList=array_combine($_REQUEST['order_itemid'], $_REQUEST['code']);
		
		$influencescopeList_this=array();
		$influencescopeList_all=array();
		foreach ($_REQUEST['influencescope'] as $keys=>$influencescopeone){
			if(!$influencescopeone)
				$influencescopeList_this[$_REQUEST['order_itemid'][$keys]]=$influencescopeone;
			else{
				$influencescopeList_all[$_REQUEST['order_itemid'][$keys]]=$influencescopeone;
			}
		}
		$success = true;
		$msg="";
		$status=0;

		//选择已付款和以后的
		$identical_sku=array();
		foreach ($influencescopeList_all as $keys=>$influencescopeList_allone){	
			//重复sku时以第一个为准		
			if(isset($identical_sku[$skuList[$keys]])){
				$NameCN_temp=$identical_sku[$skuList[$keys]]['NameCN'];
				$NameEN_temp=$identical_sku[$skuList[$keys]]['NameEN'];
				$Price_temp=$identical_sku[$skuList[$keys]]['Price'];
				$Weight_temp=$identical_sku[$skuList[$keys]]['Weight'];
				$code_temp=$identical_sku[$skuList[$keys]]['code'];
			}
			else{
				$NameCN_temp=$NameCNList[$keys];
				$NameEN_temp=$NameENList[$keys];
				$Price_temp=$PriceList[$keys];
				$Weight_temp=$WeightList[$keys];
				$code_temp=$codeList[$keys];
				
				$identical_sku[$skuList[$keys]]=array(
						'NameCN'=>$NameCNList[$keys],
						'NameEN'=>$NameENList[$keys],
						'Price'=>$PriceList[$keys],
						'Weight'=>$WeightList[$keys],
						'code'=>$codeList[$keys],
				);
			}
						
			$item = OdOrderItem::find()->where(['order_item_id'=>$keys])->one();
			$order_source = OdOrder::find()->select(['order_source'])->where(['order_id'=>$item->order_id])->one();
			if(strpos($Weight_temp,'.') || (float)$Weight_temp<0){
				$success=false;
				$msg.='<span style="color:red;">'.$item->order_id.':'.$item->product_name.'报关信息修改失败，原因：申报重量不能为小数和负数;</span><br/>';
			}
			else{
				$result=OrderUpdateHelper::setOrderItemDeclaration($keys,$NameCN_temp,$NameEN_temp,$Price_temp,$Weight_temp,$code_temp,'Y');
				if(isset($result['ack']) && $result['ack']==false){
					$success=false;
					$msg.='<span style="color:red;">'.$item->order_id.':'.$item->product_name.'报关信息修改失败，原因：'.$result['message'].";</span><br/>";
				}
				
				$items=OrderGetDataHelper::getPayOrderItemBySKU($skuList[$keys]);
				foreach ($items as $itemsone){
					$result=OrderUpdateHelper::setOrderItemDeclaration($itemsone->order_item_id,$NameCN_temp,$NameEN_temp,$Price_temp,$Weight_temp,$code_temp,'Y');
					if(isset($result['ack']) && $result['ack']==false){
						$msg.=$itemsone->order_id.':'.$result['message']." err1;";
						$success=false;
					}
				}
					
				if($success==true){
					$tmp_platform_itme_id = \eagle\modules\order\helpers\OrderGetDataHelper::getOrderItemSouceItemID($order_source->order_source, $item);
					$declared_params[]=array(
							'platform_type'=>$order_source->order_source,
							'itemID'=>$tmp_platform_itme_id,
							'sku'=>$skuList[$keys],
							'ch_name'=>$NameCN_temp,
							'en_name'=>$NameEN_temp,
							'declared_value'=>$Price_temp,
							'declared_weight'=>$Weight_temp,
							'detail_hs_code'=>$code_temp,
					);
					$result=CarrierDeclaredHelper::setOrderSkuDeclaredInfoBatch($declared_params);
					if($result==false){
						$msg.=$itemsone->order_id.':'."保存失败err2;";
						$success=false;
					}
				
					if(!empty($item->root_sku)){
						$info=array(
								'declaration_ch'=>$NameCN_temp,
								'declaration_en'=>$NameEN_temp,
								'declaration_value'=>$Price_temp,
								'prod_weight'=>$Weight_temp,
								'declaration_code'=>$code_temp,
						);
						$rt = \eagle\modules\catalog\helpers\ProductApiHelper::modifyProductInfo($item->root_sku,$info);
						if($rt['success']==false){
							$msg.='商品报关信息保存失败:'.$rt['message']." err3;";
							$success=false;
						}
					}
				}	
			}
		}
		unset($identical_sku);
		
		//选择当前订单的
		foreach ($influencescopeList_this as $keys=>$influencescopeList_allone){
			$item = OdOrderItem::find()->where(['order_item_id'=>$keys])->one();
			if(strpos($WeightList[$keys],'.') || (float)$WeightList[$keys]<0){
				$success=false;
				$msg.='<span style="color:red;">'.$item->order_id.':'.$item->product_name.'报关信息修改失败，原因：申报重量不能为小数和负数;</span><br/>';
			}
			else{
				$result=OrderUpdateHelper::setOrderItemDeclaration($keys,$NameCNList[$keys],$NameENList[$keys],$PriceList[$keys],$WeightList[$keys],$codeList[$keys],'Y');
				if(isset($result['ack']) && $result['ack']==false){
					$success=false;
					$msg.='<span style="color:red;">'.$item->order_id.':'.$item->product_name.'报关信息修改失败，原因：'.$result['message'].";</span><br/>";
				}
			}
		}
		
		return json_encode(['success'=>$success, 'message'=>$msg]);
	}
	
	/**
	 +----------------------------------------------------------
	 * 打开 扫描绑定跟踪号 界面
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		lrq 	2016/07/27				初始化
	 +----------------------------------------------------------
	 **/
	public function actionShowScanningTrackingNumberBox()
	{
	
		if (!empty($_REQUEST['orderIdList']))
		{
			$conn=\Yii::$app->subdb;
			$queryTmp = new Query;
			
			$queryTmp->select("a.order_id,b.shipping_method_name,b.is_tracking_number")
			->from("od_order_v2 a")
			->leftJoin("sys_shipping_service b", "a.default_shipping_method_code = b.id")
			->where(['a.order_id'=>$_REQUEST['orderIdList']]);
			
			$OrderInfo = $queryTmp->createCommand($conn)->queryAll();
			
			//重新按照传递过来的顺序来排序
			$orderlist = array();
			$is_tracking_numberlist = array();
			foreach ( $_REQUEST['orderIdList'] as $orderid)
			{
				foreach ( $OrderInfo as $order)
				{
					if($orderid == $order['order_id'])
					{
						$orderlist[$orderid] = $order['shipping_method_name'];
						$is_tracking_numberlist[$orderid] = $order['is_tracking_number'];
					}
				}
			}
			
			return $this->renderPartial('showscanningtrackingnumberbox',['OrderList'=>$orderlist, 'is_tracking_numberlist'=>$is_tracking_numberlist]);
		}else{
			return '选择的订单有问题';
		}
	}
	
	/**
	 +----------------------------------------------------------
	 * 根据小老板单号、跟踪号、SKU 查询订单信息
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		lrq 	2016/08/11				初始化
	 +----------------------------------------------------------
	 **/
	public function actionGetOrderListByCondition()
	{
	    try 
	    {
	        $scaning_val = '';
	        $start_time = date('Y-m-d H:i:s');
	        
	        if( $_REQUEST['type'] == 1)
	        {
	            $scaning_val = $_REQUEST['val'];
    	        //小老板单号查询
        	    $order['keys'] = 'order_id';
        	    $order['searchval'] = $_REQUEST['val'];
        	    $orders = OrderApiHelper::getOrderListByCondition($order);
        	    $datas = $orders['data'];
        	    
        	    //跟踪号查询
        	    $order['keys'] = 'tracknum';
        	    $order['searchval'] = $_REQUEST['val'];
        	    $orders = OrderApiHelper::getOrderListByCondition($order);
        	    $datas += $orders['data'];
	        }
	        else if( $_REQUEST['type'] == 2)
	        {
	        	//发货中
	        	$order['order_status'] = 300;
	        	//面单未打印
	        	$order['carrierPrintType'] = 'no_print_carrier';
	        	
	            //root_sku查询
	            $order['keys'] = 'root_sku';
	            $order['searchval'] = $_REQUEST['val'];
	            $orders = OrderApiHelper::getOrderListByCondition($order);
	            if(empty($orders)){
	            	//sku查询
	            	$order['keys'] = 'sku';
	            	$order['searchval'] = $_REQUEST['val'];
	            	$orders = OrderApiHelper::getOrderListByCondition($order);
	            }
	            foreach ($orders['data'] as $index => $o){
	            	$datas[$o->order_id] = $o;
	            }
	            
	            //查询对应的捆绑产品
	            $bundlelist_root = ProductBundleRelationship::find()->select(['bdsku'])->where(["assku"=>$_REQUEST['val']])->asArray()->all();
	            if(!empty($bundlelist_root)){
	                $bdsku = $bundlelist_root[0]['bdsku'];
	                
	                $order['searchval'] = $bdsku;
	                $orders = OrderApiHelper::getOrderListByCondition($order);
	                foreach ($orders['data'] as $index => $o){
	                	$datas[$o->order_id] = $o;
	                }
	            }
	            
	            //按照sku重新排序
	            ksort($datas);
	        }
	        
    	    if(!empty($datas))
    	    {
        	    foreach ($datas as $order_key => $order)
        	    {
        	        $data[0] =
        	        [
            	        'order_id' => preg_replace('/^0+/','',$order['order_id']),         //小老板订单号
            	        'order_source_order_id' => $order['order_source_order_id'],   //平台订单号
            	        'customer_number' => $order['customer_number'],     //客户参考号
            	        'track_number' => CarrierOpenHelper::getOrderShippedTrackingNumber($order['order_id'],$order['customer_number'],$order['default_shipping_method_code']),   //跟踪号
            	        'desc' => empty($order['desc']) ? '' : $order['desc'],       //订单备注
            	        'order_source' => $order['order_source'],           //订单来源
            	        'logistics_weight' => empty($order['logistics_weight']) ? '' : $order['logistics_weight'],    //订单包裹重量
            	        'seller_weight' => empty($order['seller_weight']) ? '' : $order['seller_weight'],    //卖家自己称重
            	        'delivery_status' => empty( OdOrder::$status[$order['order_status']]) ? '' : OdOrder::$status[$order['order_status']],    //订单状态
            	        'carrier_step' => $order['carrier_step'],    //物流状态
            	        'default_carrier_code' => $order['default_carrier_code'],    //物流商代码
            	        'carrier_type' => '1',    //物流商类型，1api物流，2Excel对接，3无数据对接
        	        ];
        	        
        	        //转换物流状态代码为中文----------start
        	        $carrier_type = 'api';
        	        if(isset($data[0]['default_carrier_code']) && strpos($data[0]['default_carrier_code'], 'lb_') === false)
        	        {
        	            //判断Excel对接、无数据对接
        	            $custom = SysCarrierCustom::find()->where(['carrier_code'=>$data[0]['default_carrier_code']])->one();
        	            if(!empty($custom))
        	            {
        	                if($custom['carrier_type'] == 1)
        	                {
        	                    $carrier_type = 'excel';
        	                    $data[0]['carrier_type'] = '2';
        	                }
        	                else 
        	                {
        	                    $carrier_type = 'trackno';
        	                    $data[0]['carrier_type'] = '3';
        	                }
        	            }
        	        }
        	        
        	        $carrierprocess_carrier_step = OdOrder::$carrierprocess_carrier_step[$carrier_type];
        	        if(!empty($carrierprocess_carrier_step))
        	        {
        	            foreach ($carrierprocess_carrier_step as $s_key => $s_val)
        	            {
        	            	if(is_array($s_val['value']))
        	            	{
        	            		if(in_array($data[0]['carrier_step'], $s_val['value']))
        	            		{
        	            			$data[0]['carrier_step'] = $s_val['name'];
        	            			break;
        	            		}
        	            	}
        	            	else if($s_val['value'] == $data[0]['carrier_step'])
        	            	{
        	            		$data[0]['carrier_step'] = $s_val['name'];
        	            		break;
        	            	}
        	            }
        	        }
        	        //转换物流状态代码为中文----------end
        	       
        	        //扫描SKU时，跳过条件
        	        if( $_REQUEST['type'] == 2)
        	        {
        	        	//去掉已跳过订单
        	        	$skip_val = explode(',', $_REQUEST['skip_val']);
        	        	foreach ($skip_val as $val)
        	        	{
        	        		if( $val == $order['order_id'])
        	        			continue 2;
        	        	}
        	        	 
        	        	//去掉已打印面单
        	        	if($order['is_print_carrier'] == 1)
        	        		continue;
        	        	
        	        	//去掉不在发货中
        	        	if(trim($data[0]['delivery_status']) != '发货中')
        	        	    continue;
        	        	
        	        	//去掉不在已交运、已分配、已导出
        	        	$carrier_step = $data[0]['carrier_step'];
        	        	if(!($carrier_step == '已交运' || $carrier_step == '已导出' || $carrier_step == '已分配'))
        	        	    continue;
        	        }
        	        
        	        foreach ($order->items as $item_key => $item)
        	        {
        	            $status = 0;
        	            $rootSku = empty($item['root_sku']) ? $item['sku'] : $item['root_sku'];
        	            
        	            //扫描SKU时，整理别名、捆绑商品
        	            if( $_REQUEST['type'] == 2)
        	            {
            	            $sku = empty($item['root_sku']) ? $item['sku'] : $item['root_sku'];
            	            //查询对应的主SKU
            	            $rootSku = ProductHelper::getRootSkuByAlias($sku);
            	            if(empty($rootSku))
            	                $rootSku = $sku;
            	            //判断是否捆绑商品，是则拆分为子产品
            	            $prod = Product::findOne(['sku'=>$rootSku]);
            	            if(!empty($prod) && $prod->type == "B")
            	            {
            	                $qty = $item['quantity'];
            	                //查询子产品
            	                $bundlelist = ProductBundleRelationship::find()->select(['assku','qty'])->where(["bdsku"=>$rootSku])->asArray()->all();
            	                if(!empty($bundlelist)){
            	                	foreach ($bundlelist as $bundle){
            	                	    //子产品信息
            	                	    $prod_assku = Product::findOne(['sku'=>$bundle['assku']]);
            	                	    if(!empty($prod_assku)){
            	                	        $product_name = $prod_assku->name;
            	                	        $photo_primary = $prod_assku->photo_primary;
            	                	    }
            	                	    
            	                	    $data[0]['items'][] =
            	                	    [
                	                	    'sku' => $bundle['assku'],
                	                	    'quantity' => $bundle['qty'] * $qty,         //数量
                	                	    'product_url' => empty($item['product_url']) ? '' : $item['product_url'],   //商品链接
                	                	    'product_name' => empty($product_name) ? $item['product_name'] : $product_name, //商品名称
                	                	    'photo_primary' => empty($photo_primary) ? $item['photo_primary'] : $photo_primary, //主图路劲
            	                	    ];
            	                	}
            	                	$status = 1;
            	                }
            	            }
        	            }
        	            
        	            if($status == 0){
        	                $data[0]['items'][] =
        	                [
            	                'sku' => $rootSku,
            	                'quantity' => $item['quantity'],         //数量
            	                'product_url' => empty($item['product_url']) ? '' : $item['product_url'],   //商品链接
            	                'product_name' => $item['product_name'], //商品名称
            	                'photo_primary' => $item['photo_primary'], //主图路劲
        	                ];
        	            }
        	        }
        	        
        	        //查询p4760所需时间差
        	        $puid = \Yii::$app->subdb->getCurrentPuid();
        	        if(!empty($puid) && $puid == '4760'){
            	        $end_time = date('Y-m-d H:i:s');
            	        $dis = strtotime($end_time) - strtotime($start_time);
            	        \Yii::info('GetOrderListByCondition puid:'.$puid.'，starttime:'.$start_time.'，val:'.$scaning_val.'，time:'.$dis, "file");
        	        }
        	        
        	        return json_encode(['code'=>'0', 'data'=>$data]);
        	    }
        	    
        	    return json_encode(['code'=>'1', 'data'=>'此小老板单号或跟踪号不存在！']);
    	    }
    	    else 
    	    {
    	        return json_encode(['code'=>'1', 'data'=>'此小老板单号或跟踪号不存在！']);
    	    }
	    }
	    catch(\exception $ex)
	    {
	        return json_encode(['code'=>'1', 'data'=>$ex->getMessage()]);
	    }
	}
	
	/**
	 +----------------------------------------------------------
	 * 手工订单录入 界面
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh 	2016/08/06				初始化
	 +----------------------------------------------------------
	 **/
	public function actionManualOrderBox(){
		$platform = '';
		if (!empty($_REQUEST['platform'])){
			$platform = $_REQUEST['platform'];
		}
		AppTrackerApiHelper::actionLog("Oms-erp", "/order/manualorder");
		$uid = \Yii::$app->subdb->getCurrentPuid();
		
		
		//账号
		/*$account_result = PlatformAccountApi::getPlatformAllAccount($uid, $platform);
		
		
		if($account_result['success']){
			foreach ($account_result['data'] as $seller_key => $seller_value){
				$seller_array[$seller_key] = $seller_value;
			}
		}else{
			return $this->render('//errorview',['title'=>'手工订单','error'=>'找不到绑定的账号， 不能创建 手工 订单！']);
			echo "找不到绑定的账号， 不能创建 手工 订单！";
			return ;
		}*/
		//只显示有权限的账号，lrq20170828
		$seller_array = PlatformAccountApi::getPlatformAuthorizeAccounts(strtolower($platform));
		if(empty($seller_array)){
			return $this->render('//errorview',['title'=>'手工订单','error'=>'找不到绑定的账号， 不能创建 手工 订单！']);
			echo "找不到绑定的账号， 不能创建 手工 订单！";
			return ;
		}
		//默认值
		$path = "/order/order/".ucfirst(strtolower($platform))."ManualCaptureOrderDefault";
		$defaultRT = ConfigHelper::getConfig($path);
		
		if (!empty($defaultRT) && is_string($defaultRT)){
			$defaultRT = json_decode($defaultRT,true);
		}
		
		//国家
		$country = StandardConst::$COUNTRIES_CODE_NAME_CN;
		
		foreach($country as $country_code=>&$country_label){
			$country_label = $country_code."(".$country_label.")";
		}
		
		unset($country['--']);
		
// 		$country = DataStaticHelper::getUseCountTopValuesFor(OrderHelper::$ManualOrderFrequencyCountryPath ,$country );
		
// 		$countryFormatter = [];
// 		if (!empty($country['recommended'])){
// 			$countryFormatter =['已使用'=>$country['recommended'],'未使用'=>$country['rest']];
// 		}else{
// 			$countryFormatter = $country['rest'];
// 		}
		
		//站点
		$ALLsites = PlatformAccountApi::getAllPlatformOrderSite();
		
		if (!empty($ALLsites[$platform])){
			$sites = $ALLsites[$platform];
		}else{
			$sites = [];
		}
		
		if($platform == 'wish'){
			//获取wish账号别名
			$selleruserids_new = \eagle\modules\platform\apihelpers\WishAccountsApiHelper::getWishAliasAccount($seller_array);
			unset($seller_array);
			$seller_array = $selleruserids_new;
		}else if($platform == 'ebay'){
			//获取ebay账号别名
			$selleruserids_new = EbayAccountsApiHelper::getEbayAliasAccount($seller_array);
			unset($seller_array);
			$seller_array = $selleruserids_new;
		}
		
		return $this->render('manualorder',['platform'=>$platform , 'seller_array'=>$seller_array , 'defaultRT'=>$defaultRT , 'country'=>$country , 'sites'=>$sites]);
	}//end of function actionManualOrderBox
	
	/**
	 +----------------------------------------------------------
	 * 保存手工订单
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh 	2016/08/06				初始化
	 +----------------------------------------------------------
	 **/
	public function actionSaveManualOrder(){
		$uid = \Yii::$app->subdb->getCurrentPuid();
		$order = [];
		
		$requireColumn = ['order_source_order_id'=>'订单号' ,'selleruserid'=>'店铺' , 'currency'=>'币种' , 'consignee'=>'买家姓名' , 'consignee_country_code'=>'国家' ,'consignee_address_line1'=>'地址1' , 'consignee_postal_code'=>'邮编','consignee_city'=>'城市','consignee_province'=>'省/州'];
		$optionColumn = ['consignee_address_line2'=>'地址2','consignee_phone'=>'电话','consignee_address_line3'=>'地址3','consignee_mobile'=>'手机','desc'=>'订单备注','shipping_cost'=>'运费' ,'consignee_email'=>'买家邮箱' , 'order_source_site_id'=>'站点'];
		
		foreach($_REQUEST as $key=>$value){
			
			if (array_key_exists($key ,$requireColumn )){
				//必须 项
				if (!empty($_REQUEST[$key])){
					$order[$uid][$key]=$value;
				}else{
					exit('failure'.$requireColumn[$key]."不能为空");
				}
			}elseif(array_key_exists($key ,$optionColumn )){
				//可选项
				if (!empty($_REQUEST[$key])){
					$order[$uid][$key]=$value;
				}
			}
		}
		
		//检查订单是否存在 
		$checkRT = OdOrder::find()->where(['order_source_order_id'=>$_REQUEST['order_source_order_id'] ])->asArray()->one();
		if (!empty($checkRT)){
			exit('failure'.$_REQUEST['order_source_order_id']."已经存在，请到【".$checkRT['order_source']."订单】中查找修改");
		}
		
		//detail 生成 
		if (!empty($_REQUEST['item'])){
			$item_count = count($_REQUEST['item']['sku']);
		}
		
		$subTotal = 0;
		for($index =0;$index<$item_count;$index++){
			//商品信息
			$sku = $_REQUEST['item']['sku'][$index];
			$productInfo = ProductHelper::getProductInfo($sku);
			$currentItem = [
				'order_source_order_id'=>$_REQUEST['order_source_order_id'] , 
				'sku'=>$sku , 
				'ordered_quantity'=> $_REQUEST['item']['qty'][$index],
				'quantity'=> $_REQUEST['item']['qty'][$index],
				'price'=>$_REQUEST['item']['price'][$index],
				'order_source_itemid'=>'',
				'delivery_status'=>'allow',
			];
			
			if (!empty($productInfo)){
				$currentItem['product_name'] =  $productInfo['name'];
				$currentItem['photo_primary'] =  $productInfo['photo_primary'];
			}
			
			if (empty($currentItem['product_name'])) $currentItem['product_name'] = $sku;
			
			$subTotal += $_REQUEST['item']['qty'][$index]*$_REQUEST['item']['price'][$index];
			$order[$uid]['items'][]=$currentItem;
		}
		$order[$uid]['order_status'] =OdOrder::STATUS_PAY; //订单状态
		$order[$uid]['subtotal'] = $subTotal; //
		$order[$uid]['grand_total'] = $subTotal+$_REQUEST['shipping_cost'];
		$order[$uid]['order_source'] = $_REQUEST['order_source'];
		$order[$uid]['order_source_create_time'] = time();
		$order[$uid]['consignee_country'] = StandardConst::$COUNTRIES_CODE_NAME_EN[$_REQUEST['consignee_country_code']];
		$order[$uid]['order_capture'] = 'Y';  //手工 订单标志
		$order[$uid]['paid_time'] = time(); //付款时间
		
		$rt = OrderHelper::importPlatformOrder($order);
		
		if ($rt['success'] ===0){
			$path = "/order/order/".ucfirst(strtolower($order[$uid]['order_source']))."ManualCaptureOrderDefault";
			$default = ['selleruserid'=>$_REQUEST['selleruserid'] , 'currency'=>$_REQUEST['currency'] ,'consignee_country_code'=>$_REQUEST['consignee_country_code']];
			if (!empty($_REQUEST['order_source_site_id'])){
				$default['order_source_site_id'] = $_REQUEST['order_source_site_id'];
			}
			ConfigHelper::setConfig($path, json_encode($default));
			DataStaticHelper::addUseCountFor(OrderHelper::$ManualOrderFrequencyCountryPath, $order[$uid]["consignee_country_code"]);
			exit('success');
		}else{
			exit('failure'.$rt['message']);
		}
		
	}//end of function actionSaveManualOrder
	
	/**
	 +----------------------------------------------------------
	 * excel导入手工订单 的界面
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh 	2016/08/06				初始化
	 +----------------------------------------------------------
	 **/
	public function actionImportManualOrderModal(){
		$platform = '';
		if (!empty($_REQUEST['platform'])){
			$platform = $_REQUEST['platform'];
		}
		AppTrackerApiHelper::actionLog("Oms-erp", "/order/manualorder");
		$uid = \Yii::$app->subdb->getCurrentPuid();
		
		$account_result = PlatformAccountApi::getPlatformAllAccount($uid, $platform);
		
		
		if($account_result['success']){
			foreach ($account_result['data'] as $seller_key => $seller_value){
				$seller_array[$seller_key] = $seller_value;
			}
		}else{
			return $this->render('//errorview',['title'=>'手工订单','error'=>'找不到绑定的账号， 不能创建 手工 订单！']);
			echo "找不到绑定的账号， 不能创建 手工 订单！";
			return ;
		}
		
		//默认值 
		$path = "/order/order/".ucfirst(strtolower($platform))."ManualCaptureOrderDefault";
		$defaultRT = ConfigHelper::getConfig($path);
		
		if (!empty($defaultRT) && is_string($defaultRT)){
			$defaultRT = json_decode($defaultRT,true);
		}
		
		//站点
		$ALLsites = PlatformAccountApi::getAllPlatformOrderSite();
		
		if (!empty($ALLsites[$platform])){
			$sites = $ALLsites[$platform];
		}else{
			$sites = [];
		}
		return $this->renderPartial('importManualOrderModal',['platform'=>$platform , 'seller_array'=>$seller_array , 'defaultRT'=>$defaultRT ,'sites'=>$sites]);
	}//end of function actionImportManualOrderModal
	
	/**
	 +----------------------------------------------------------
	 * 公共 修改订单 弹窗
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh 	2016/08/16				初始化
	 +----------------------------------------------------------
	 **/
	public function actionEditOrderModal(){
		if (!empty($_REQUEST['orderid'])){
			AppTrackerApiHelper::actionLog("Oms-erp", "/order/EditOrderModal");
			$order = OdOrder::findOne($_REQUEST['orderid']);
			if (!empty($order)){
				//$warehouses = InventoryApiHelper::getWarehouseIdNameMap();
				$ShippingServices = CarrierApiHelper::getShippingServices2_1();
				$countryList = StandardConst::$COUNTRIES_CODE_NAME_EN;
				$ordershipped = $order->getTrackinfos();
				//仓库  对应的运输方式 ， 海外仓信息
				list($shipmethodList, $warehouseList , $locList) = OrderHelper::getWarehouseAndShipmentMethodData();
				
				$customerShippingMethod = OrderApiHelper::getCustomerShippingMethod($_REQUEST['orderid']);
				
				
				//检查报关信息是否存在 start
				$existProductResult = OrderBackgroundHelper::getExitProductRootSKU([$order]);
				//检查报关信息是否存在 end
				
				//订单地址(一般情况也是没有 问题)，物流信息 health check
				$HealthCheckClassList = ['consignee'=>'glyphicon glyphicon-ok text-success'];
				
				//这里要取订单的报关信息是否存在？1个没有都为叉
				$declaration=0;
				$List = OdOrder::find()->where(['order_id'=>$_REQUEST['orderid']])->one();
				$declaration_list=CarrierDeclaredHelper::getOrderDeclaredInfoByOrder($List);
				foreach($declaration_list as $declaration_listone){
					if($declaration_listone['not_declaration']==1)
						$declaration=1;
				}
				if ($declaration==1){
					$HealthCheckClassList['declaration'] = 'glyphicon glyphicon-remove text-warn';
				}else{
					$HealthCheckClassList['declaration'] = 'glyphicon glyphicon-ok text-success';
				}
				
				//获取ebay  check out 状态
				$orderCheckOutList = [];//ebay 独有的
				$paypal=[];//ebay 独有的
				if ($order->order_source == 'ebay'){
					//付款 信息
					$orderCheckOutList = OrderApiHelper::getEbayCheckOutInfo($order->order_source_order_id);
					//paypay 相关的信息
					$paypal = OdPaypalTransaction::findOne(['order_id'=>$order->order_id]);
				}
				
    			//当设置个别平台使用商品库图片，获取对应订单的商品库图片
                $order_rootsku_product_image = OrderHelper::GetRootSkuImage(['0' => $order]);
				
				if (empty($order->default_shipping_method_code) ||empty($order->default_carrier_code) ){
					$HealthCheckClassList['shipmethod'] = 'glyphicon glyphicon-remove text-warn';
				}else{
					$HealthCheckClassList['shipmethod'] = 'glyphicon glyphicon-ok text-success';
				}
				
				//判断下一个上一个
				$upOrDownDiv=array('cursor'=>0,'up'=>'','down'=>'');
				$rtnjson='';
				if(!empty($_REQUEST['upOrDownDivtxt'])){
					$is_json=json_decode(base64_decode($_REQUEST['upOrDownDivtxt']));
					if(!empty($is_json)){
						$rtnjson=base64_decode($_REQUEST['upOrDownDivtxt']);
					}
					else{
						$orderAllId=array();
						$orderAllId['order_id']=explode(',',$_REQUEST['upOrDownDivtxt']); 
						$rtnjson=json_encode($orderAllId);
					}
					if(!empty($rtnjson)){
						$rtn=json_decode($rtnjson,true);
						foreach ($rtn['order_id'] as $rtnkeys=> $rtnone){
							$rtnone_str=preg_replace('/^0+/','',$rtnone);
							$rtnone_id=preg_replace('/^0+/','',$_REQUEST['orderid']);
							if($rtnone_str==$rtnone_id){
								if($rtnkeys==0)
									$upOrDownDiv['cursor']=1;
								else if($rtnkeys==(count($rtn['order_id'])-1))
									$upOrDownDiv['cursor']=3;
								else
									$upOrDownDiv['cursor']=2;
								
								$upOrDownDiv['up']=empty($rtn['order_id'][$rtnkeys-1])?'':$rtn['order_id'][$rtnkeys-1];
								$upOrDownDiv['down']=empty($rtn['order_id'][$rtnkeys+1])?'':$rtn['order_id'][$rtnkeys+1];
								
								if(empty($upOrDownDiv['up']) && empty($upOrDownDiv['down']))
									$upOrDownDiv['cursor']=0;
								break;
							}
						}
					}
				}
				
				$selleruserids_new = array();
				
				//获取ebay账号别名 S
				if($order->order_source == 'ebay'){
					$selleruserids = array();
					$selleruserids[$order->selleruserid] = $order->selleruserid;
					
					$selleruserids_new = EbayAccountsApiHelper::getEbayAliasAccount($selleruserids);
				}
				//获取ebay账号别名 E
				
				//获取wish账号别名
				if($order->order_source == 'wish'){
					$selleruserids_new = \eagle\modules\platform\apihelpers\WishAccountsApiHelper::getWishAliasAccount(array($order->selleruserid=>$order->selleruserid));
				}

				return $this->renderPartial('editOrderModal.php',['order'=>$order , 'warehouses'=>$warehouseList , 
						'ShippingServices'=>$ShippingServices , 'countryList'=>$countryList , 'ordershipped'=>$ordershipped , 
						'shipmethodList'=>$shipmethodList , 'customerShippingMethod'=>$customerShippingMethod['data'] ,'existProductResult'=>$existProductResult , 
						'HealthCheckClassList'=>$HealthCheckClassList , 'orderCheckOutList'=>$orderCheckOutList ,'paypal'=>$paypal,'upOrDownDiv'=>$upOrDownDiv,
						'upOrDownDivtxt'=>base64_encode($rtnjson), 'order_rootsku_product_image' => $order_rootsku_product_image, 'selleruserids_new'=>$selleruserids_new]);
			}else{
				return $_REQUEST['orderid']."是无效订单！";
			}
			
		}else{
			return "找不到订单！";
		}
		
	}//end of function actionEditOrderModal
	
	
	/**
	 +----------------------------------------------------------
	 * 虚拟发货状态统计数量
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh 	2016/08/16				初始化
	 +----------------------------------------------------------
	 **/
	public function actionGetOrderShipStatusSituation(){
		$rt = OrderGetDataHelper::getOrderSyncShipSituation(@$_REQUEST['platform'],@$_REQUEST['order_status']);
		return json_encode($rt);
	}//end of function actionCalcOrderShipStatusCount
	
	/**
	 +----------------------------------------------------------
	 * 虚拟发货状态 变成 提交成功
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh 	2016/09/28				初始化
	 +----------------------------------------------------------
	 **/
	public function actionSetOrderSyncShipStatusComplete(){
		
		if (!empty($_REQUEST['orderIdList'])){
			$orderIdList = $_REQUEST['orderIdList'];
			if (count($orderIdList)==0){
				return json_encode(array('ack'=>false,'message'=>TranslateHelper::t('未选择订单！')));
			}
			AppTrackerApiHelper::actionLog("Oms-erp", "/order/setordershipstatuscomplete");
			$rt = OrderApiHelper::setOrderSyncShipStatusComplete($orderIdList);
			return json_encode($rt);
		}else{
			return json_encode(array('ack'=>false,'message'=>TranslateHelper::t('未选择订单！')));
		}
		
		
	}//end of function actionSetOrderShipStatusComplete
	
	
	/**
	 +----------------------------------------------------------
	 * 修改订单弹窗 ， 保存仓库与运输服务
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh 	2016/10/10				初始化
	 +----------------------------------------------------------
	 **/
	public function actionSaveWarehouseShipservice(){
		$fullName = \Yii::$app->user->identity->getFullName();
		$serviceid = SysShippingService::findOne($_REQUEST['shipmentMethod']);
		$newAttr = ['default_shipping_method_code'=>$_REQUEST['shipmentMethod'], 'default_carrier_code'=>$serviceid->carrier_code   , 'default_warehouse_id'=>$_REQUEST['warehouse'] ] ;
		$action = '修改订单';
		$module = 'order';
		$rt = \eagle\modules\order\helpers\OrderUpdateHelper::updateOrder($_REQUEST['orderId'], $newAttr,false ,$fullName  , $action , $module );
		$rt['success'] = $rt['ack'];
		exit(json_encode($rt));
	}//end of function actionSaveWarehouseShipservice
	
	
	/**
	 +----------------------------------------------------------
	 * ajax 方式， 保存订单的属性
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh 	2014/10/26				初始化
	 +----------------------------------------------------------
	 **/
	public function actionAjaxSaveOrder(){
		$fullName = \Yii::$app->user->identity->getFullName();
		if (isset($_REQUEST['consignee_email']) && $_REQUEST['orderId']){
			$newAttr = ['consignee_email'=>$_REQUEST['consignee_email'] ] ;
			$action = '修改订单';
			$module = 'order';
			$rt = \eagle\modules\order\helpers\OrderUpdateHelper::updateOrder($_REQUEST['orderId'], $newAttr,false ,$fullName  , $action , $module );
			$rt['success'] = $rt['ack'];
		}else{
			$rt = ['success'=>false , 'message'=>'参数不正常'];
		}
		
		exit(json_encode($rt));
	}//end of function function actionAjaxSaveOrder
	
	
	/**
	 +----------------------------------------------------------
	 * 保存收件人信息
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh 	2014/05/06				初始化
	 +----------------------------------------------------------
	 **/
	public function actionSaveConsigneeInfo(){
		if (!empty($_REQUEST['order_id'])){
			AppTrackerApiHelper::actionLog("Oms-erp", "/order/SaveConsigneeInfo");
			$OrderModel = OdOrder::find()->where(['order_id'=>$_REQUEST['order_id']])->One();
				
			
			if (empty($OrderModel)) exit(json_encode(['success'=>false , 'message'=>'E002 找不到订单!']));
				
			/*
			$rt = OrderHelper::setOriginShipmentDetail($OrderModel);
			if ($rt['success'] ==false){
				exit(json_encode(['success'=>false , 'message'=>'E003内部错误，请联系客服!']));
			}
			*/
			$_tmp = $_POST;
			unset($_tmp['order_id']);
			
			if (empty($OrderModel->origin_shipment_detail)){
				//没有原始订单收件人数据时候需要保存一份
				$shipment_info = [
				'consignee'=>$OrderModel->consignee,
				'consignee_postal_code'=>$OrderModel->consignee_postal_code,
				'consignee_phone'=>$OrderModel->consignee_phone,
				'consignee_mobile'=>$OrderModel->consignee_mobile,
				'consignee_fax'=>$OrderModel->consignee_fax,
				'consignee_email'=>$OrderModel->consignee_email,
				'consignee_company'=>$OrderModel->consignee_company,
				'consignee_country'=>$OrderModel->consignee_country,
				'consignee_country_code'=>$OrderModel->consignee_country_code,
				'consignee_city'=>$OrderModel->consignee_city,
				'consignee_province'=>$OrderModel->consignee_province,
				'consignee_district'=>$OrderModel->consignee_district,
				'consignee_county'=>$OrderModel->consignee_county,
				'consignee_address_line1'=>$OrderModel->consignee_address_line1,
				'consignee_address_line2'=>$OrderModel->consignee_address_line2,
				'consignee_address_line3'=>$OrderModel->consignee_address_line3,
				];
					
				$_tmp['origin_shipment_detail'] = json_encode($shipment_info);
			}
			$fullName = \Yii::$app->user->identity->getFullName();
			$action = '修改订单';
			$module = 'order';
			
			$rt = OrderUpdateHelper::updateOrder($OrderModel, $_tmp ,  false , $fullName  , $action , $module  );
			
			
			if ($rt['ack']){
				//AppTrackerApiHelper::actionLog("Oms-aliexpress", "/order/aliexpress/edit-save");
				//OperationLogHelper::log('order',$OrderModel->order_id,'修改订单','编辑订单进行订单修改',\Yii::$app->user->identity->getFullName());
				exit(json_encode(['success'=>true , 'message'=>'']));
			}else{
				exit(json_encode(['success'=>false , 'message'=>'E004内部错误，请联系客服!'.$rt['message']]));
			}
		}else{
			exit(json_encode(['success'=>false , 'message'=>'E001内部错误，请联系客服!']));
		}
	}//end of actionSaveConsigneeInfo
	
	/**
	 +----------------------------------------------------------
	 * 修改订单界面的 保存订单备注
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh 	2016/10/08				初始化
	 +----------------------------------------------------------
	 **/
	public function actionSaveMemoInfo(){
		try {
			if (!empty($_REQUEST['order_id'])){
				
				AppTrackerApiHelper::actionLog("Oms-erp", "/order/SaveMemoInfo");
				$OrderModel = OdOrder::find()->where(['order_id'=>$_REQUEST['order_id']])->One();
				
					
				if (empty($OrderModel)) exit(json_encode(['success'=>false , 'message'=>'E002 找不到订单!']));
				$err_msg = '';
				
				$rt = OrderHelper::addOrderDescByModel($OrderModel, $_REQUEST['desc'], 'order', '添加备注');
					
				if ($rt['success'] == true){
					//OperationLogHelper::log('order',$OrderModel->order_id,'添加备注','修改备注: ('.$olddesc.'->'.$OrderModel->desc .')',\Yii::$app->user->identity->getFullName());
				}else{
					$err_msg .= $OrderModel->order_id." 添加备注失败！";
				}
				exit(json_encode(['success'=>$rt['success'] , 'message'=>$err_msg]));
			}else{
				exit(json_encode(['success'=>false , 'message'=>'E001内部错误，请联系客服!']));
			}
		} catch (\Exception $e) {
			
			exit(json_encode(['success'=>false , 'message'=>'E003 '.$e->getMessage()]));
		}
		
		
	}//end of function actionSaveMemoInfo 
	
	
	/**
	 +----------------------------------------------------------
	 * 修改订单界面的 保存订单billing地址
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		lzhl 	2017/03/17				初始化
	 +----------------------------------------------------------
	 **/
	public function actionSaveBillingInfo(){
		try {
			if (!empty($_REQUEST['order_id'])){
	
				AppTrackerApiHelper::actionLog("Oms-erp", "/order/SaveBillingInfo");
				$OrderModel = OdOrder::find()->where(['order_id'=>$_REQUEST['order_id']])->One();
	
					
				if (empty($OrderModel)) exit(json_encode(['success'=>false , 'message'=>'E002 找不到订单!']));
				$err_msg = '';
				
				$billing_info = empty($_REQUEST['billing_info'])?[]:$_REQUEST['billing_info'];
				$OrderModel->billing_info = json_encode($billing_info);
				//
				//$rt = OrderHelper::addOrderDescByModel($OrderModel, $_REQUEST['desc'], 'order', '添加备注');
					
				if ($OrderModel->save(false)){
					$rt = true;
					//OperationLogHelper::log('order',$OrderModel->order_id,'添加备注','修改备注: ('.$olddesc.'->'.$OrderModel->desc .')',\Yii::$app->user->identity->getFullName());
				}else{
					$rt = false;
					$err_msg .= $OrderModel->order_id." 添加备注失败！";
				}
				exit(json_encode(['success'=>$rt , 'message'=>$err_msg]));
			}else{
				exit(json_encode(['success'=>false , 'message'=>'E001内部错误，请联系客服!']));
			}
		} catch (\Exception $e) {
				
			exit(json_encode(['success'=>false , 'message'=>'E003 '.$e->getMessage()]));
		}
	
	
	}//end of function actionSaveMemoInfo
	
	/**
	 +----------------------------------------------------------
	 * 修改订单界面的 保存订单商品信息
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh 	2016/10/12				初始化
	 +----------------------------------------------------------
	 **/
	public function actionSaveEditOrderItem(){
		if (isset($_POST['item']) && isset($_REQUEST['order_id'])){
			$item_tmp = $_POST['item'];
			$order = OdOrder::findOne($_REQUEST['order_id']);
			
			//检查 小老板订单号
			if (empty($order)) exit(json_encode(['success'=>false ,'message'=>$_REQUEST['order_id'].'为无效的小老板订单号 ！']));
			
			$result = ['success'=>true , 'message'=>''];
			$addtionLog = '';
			
			//删除item 相关信息
			if (!empty($_REQUEST['deldetailstr'])){
				$delOrderItemIdList = explode(',',$_REQUEST['deldetailstr']);
			}else{
				$delOrderItemIdList = [];
			}
			
			$isUpdateFirstSKU = false;
			$isUpdateMultiProduct = false;
			$firstSKU = '';
			
			$ignoreSKUList = CdiscountOrderInterface::getNonDeliverySku();
			 
			//存储订单对应商品
			$subtotal = 0;
			$is_edit_price = false;   //是否修改单价
			foreach ($item_tmp['sku'] as $key=>$val){
				$currentSKUMsg = '';
				if (isset($item_tmp['itemid'][$key])){
					$item = OdOrderItem::findOne($item_tmp['itemid'][$key]);
					
					//删除的商品 跳过， 最后 一次过进行操作删除操作
					if (in_array($item->order_item_id ,$delOrderItemIdList )){
						continue;
					}else{
						if (empty($firstSKU) && in_array($item_tmp['sku'][$key],$ignoreSKUList ) ==false){
							$firstSKU = $item_tmp['sku'][$key];
						}
						if ($item->manual_status =='enable'){
							$OriginQty = $item->quantity; //修改前的数量
						}else{
							$OriginQty = 0; // 禁用的商品不算待发货数量
						}
						
						//设置sku
						$OriginSKU = $item->sku ;
						$item->sku = $item_tmp['sku'][$key];
						//订单商品只能修改数量
						$item->quantity = $item_tmp['quantity'][$key];
						$item->manual_status = $item_tmp['manual_status'][$key];
						if ($item->item_source == 'platform' ){
							if ($item->manual_status == 'enable' ){
								$item->delivery_status = 'allow';//允许发货
							}else{
								$item->delivery_status = 'ban';//禁止发货
							}
						}
					}
					
					
				}else{
					$item = new OdOrderItem();
					$OriginQty = 0; //修改前的数量
					
					//新商品需要保存相关的信息
					$item->order_id = $order->order_id;
					$item->product_name = $item_tmp['product_name'][$key];
					$item->sku = $item_tmp['sku'][$key];
					$item->ordered_quantity = $item_tmp['quantity'][$key];
					$item->quantity = $item_tmp['quantity'][$key];
					$item->order_source_srn =  empty($item_tmp['order_source_srn'][$key])?$order->order_source_srn:$item_tmp['order_source_srn'][$key];
					$item->price = $item_tmp['price'][$key];
					$item->update_time = time();
					$item->create_time = is_null($item->create_time)?time():$item->create_time;
					$item->manual_status = 'enable';// 启用
					$item->item_source = 'local';  //本地商品
					$currentSKUMsg = '增加了';
					$item->delivery_status = 'allow'; //允许发货
					$item->root_sku = ProductHelper::getRootSkuByAlias($item->sku,$order->order_source ,$order->selleruserid );
					
					if (!empty($item->root_sku)){
						$productInfo = \eagle\modules\catalog\helpers\ProductHelper::getProductInfo($item->root_sku);
						if (isset($productInfo['photo_primary'])){
							$item->photo_primary = $productInfo['photo_primary'];
						}
					}
					
					
					if (empty($firstSKU) && in_array($item->sku,$ignoreSKUList ) ==false){
						$firstSKU = $item->sku;
					}
				}
				
				//手工非补发订单非已完成支持修改单价
				if($order->order_status < 500 && $order->order_capture == 'Y' && $order['reorder_type'] != 'after_shipment' && isset($item_tmp['edit_price'][$key])){
					$OriginPrice = $item->price;
					$item->price = (empty($item_tmp['edit_price'][$key]) ? 0 : $item_tmp['edit_price'][$key]);
					if($OriginPrice != $item->price){
						$is_edit_price = true;
						$subtotal += (empty($item->quantity) ? 0 : $item->quantity) * (empty($item->price) ? 0 : $item->price);
						
						$addtionLog .= " ".$item->sku."  price $OriginPrice=>".$item->price;
					}
				}
				
				if ($item->save()){
					//没有 修改sku 只对比数量
					if ($OriginQty != $item_tmp['quantity'][$key] && !empty($item->root_sku)){
					
						list($ack , $code , $message  )  = array_values( OrderBackgroundHelper::updateUnshippedQtyOMS($item->root_sku, $order->default_warehouse_id, $order->default_warehouse_id, $OriginQty, $item_tmp['quantity'][$key]));
						if ($ack){
							//$addtionLog .= "$currentSKUMsg ".$item->root_sku." $OriginQty=>".$item_tmp['quantity'][$key];
						}
					}
					
					/*
					
					//修改sku
					if ($OriginSKU != $item_tmp['sku'][$key]){
						//修改sku， 无论数量有没有变化也要更新待发货数量
						list($ack , $code , $message  )  = array_values( OrderBackgroundHelper::updateUnshippedQtyOMS($OriginSKU, $order->default_warehouse_id, $order->default_warehouse_id, $OriginQty, 0));
						if ($ack){
							$addtionLog .= ' ，原sku 由'.$OriginSKU."=>".$item_tmp['sku'][$key];
							$addtionLog .= " ，修改了 原sku $OriginSKU 待发货数量 $OriginQty=>0";
						}
						
						list($ack , $message , $code , $rootSKU ) = array_values(OrderGetDataHelper::getRootSKUByOrderItem($item));
							
						list($ack , $code , $message  )  = array_values( OrderBackgroundHelper::updateUnshippedQtyOMS($rootSKU, $order->default_warehouse_id, $order->default_warehouse_id, 0, $item_tmp['quantity'][$key]));
						if ($ack){
							$addtionLog .= "，修改后 sku $rootSKU 0=>".$item_tmp['quantity'][$key];
						}
						
					}else{
						//没有 修改sku 只对比数量
						if ($OriginQty != $item_tmp['quantity'][$key]){
							list($ack , $message , $code , $rootSKU ) = array_values(OrderGetDataHelper::getRootSKUByOrderItem($item));
								
							list($ack , $code , $message  )  = array_values( OrderBackgroundHelper::updateUnshippedQtyOMS($rootSKU, $order->default_warehouse_id, $order->default_warehouse_id, $OriginQty, $item_tmp['quantity'][$key]));
							if ($ack){
								$addtionLog .= "$currentSKUMsg $rootSKU $OriginQty=>".$item_tmp['quantity'][$key];
							}
						}
					}
					*/
					
					//没有 修改sku 只对比数量 
					if ($OriginQty != $item_tmp['quantity'][$key]){
						$addtionLog .= " $currentSKUMsg ".$item_tmp['sku'][$key] ." qty $OriginQty=>".$item_tmp['quantity'][$key];
					}
				}//end of item save 
				else {
					$result['success'] = false;
					foreach($item->getErrors() as $row ){
						$result['message'] .= $row;
					}
					
				}
			}//end of each item 
			
			//手工非补发非已完成订单，统计金额
			if($is_edit_price && $order->order_status < 500 && $order->order_capture == 'Y' && $order['reorder_type'] != 'after_shipment'){
				$order->subtotal = $subtotal;
				$order->grand_total = $order->subtotal + $order->shipping_cost;
				$order->save(false);
			}
			
			//删除商品 ,
			foreach($delOrderItemIdList as $delOrderItemID){
				$delRT = OrderUpdateHelper::deleteOrderItem($delOrderItemID,$order->default_warehouse_id);
				$isUpdateFirstSKU = true;
			}
			$updateData = [];
			//是否需要更新首个sku
			if ($order->first_sku != $firstSKU){
				$updateData['first_sku'] = $firstSKU;
			}
			
			//是否需要更新多品标志
			if (count($item_tmp['sku']) >1 && $order->ismultipleProduct !='Y'){
				// 目前的商品数量大于 1 然后多品标志为N的订单需要更新Y
				$updateData['ismultipleProduct'] = 'Y'; 
			}else if (count($item_tmp['sku']) == 1 && $order->ismultipleProduct =='Y'){
				// 目前的商品数量等于于 1 然后多品标志为Y的订单需要更新N
				$updateData['ismultipleProduct'] = 'N';
			}
			$fullName = \Yii::$app->user->identity->getFullName();
			$action = '修改订单';
			$module = 'order';
			
			$updateOrderRT = OrderUpdateHelper::updateOrder($order, $updateData , false , $fullName  , $action, $module );
			
			//写订单操作日志
			if (!empty($addtionLog)){
    			OperationLogHelper::log($module,$order->order_id,$action,'编辑订单进行订单修改'.$addtionLog,$fullName);
    		}
    		
			exit(json_encode($result));
		}//end of validate  $_POST
		else{
			exit(json_encode(['success'=>false ,'message'=>'参数异常 ！']));
		}
	}//end of function actionSaveEditOrderItem
	
	/**
	 +----------------------------------------------------------
	 * 刷新 编辑订单的订单item 数据
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh 	2016/10/12				初始化
	 +----------------------------------------------------------
	 **/
	public function actionRefreshEditOrderItemInfo(){
		if (!empty($_REQUEST['order_id'])){
			AppTrackerApiHelper::actionLog("Oms-erp", "/order/EditOrderModal");
			$order = OdOrder::findOne($_REQUEST['order_id']);
			if (!empty($order)){
				//检查报关信息是否存在 start
				$existProductResult = OrderBackgroundHelper::getExitProductRootSKU([$order]);
				//检查报关信息是否存在 end
		
				return OrderFrontHelper::displayEditOrderItemInfo($order, $existProductResult);
			}else{
				return $_REQUEST['orderid']."是无效订单！";
			}
				
		}else{
			return "找不到订单！";
		}
	}//end of function actionRefreshEditOrderItemInfo
	
	/**
	 +----------------------------------------------------------
	 * 订单设置 为验证通过 ，  ebay 订单为paypal地址已同步
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh 	2016/12/14				初始化
	 +----------------------------------------------------------
	 **/
	public function actionSetOrderVerifyPass(){
		if (!empty($_REQUEST['orderIdList'])){
			$orderIdList = $_REQUEST['orderIdList'];
			if (count($orderIdList)==0){
				return json_encode(array('ack'=>false,'message'=>TranslateHelper::t('未选择订单！')));
			}
			AppTrackerApiHelper::actionLog("Oms-erp", "/order/setorderverifypass");
			$rt = OrderApiHelper::setOrderVerifyPass($orderIdList);
			return json_encode($rt);
		}else{
			return json_encode(array('ack'=>false,'message'=>TranslateHelper::t('未选择订单！')));
		}
	}//end of actionSetOrderVerifyPass
	
	/**
	 +----------------------------------------------------------
	 * 删除手工订单   action
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		hqw 	2016/12/15				初始化
	 +----------------------------------------------------------
	 **/
	public function actionDeleteManualOrder(){
		if (!empty($_POST['orders'])){
			$orderIdList =  $_POST['orders'];
			$rt = OrderHelper::deleteManualOrder($orderIdList);
			
			if($rt['success'] == false){
				return $this->renderJson(['success'=>false,'code'=>400,'type'=>'message','timeout'=>2,'message'=>$rt['message']]);
			}else{
				return $this->renderJson(['success'=>true,'code'=>200,'type'=>'message','timeout'=>2,'message'=>'操作成功！','reload'=>true]);
			}
		}else{
			return $this->renderJson(['success'=>false,'code'=>400,'type'=>'message','timeout'=>2,'message'=>'找不到有效订单！']);
		}
	}
	
	/**
	 +----------------------------------------------------------
	 * 解绑 订单的别名
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh 	2016/10/26				初始化
	 +----------------------------------------------------------
	 **/
	public function actionUnbindingProductAlias(){
		
	}//end of function actionUnbindingProductAlias

	/**
	 +----------------------------------------------------------
	 * 保存订单报关信息
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		lgw 	2017/03/07				初始化
	 +----------------------------------------------------------
	 **/
	public function actionOrderSaveDeclarationInfo(){
		$type = empty($_REQUEST['type'])?'':$_REQUEST['type'];   //保存类型
		$order_source=empty($_REQUEST['data'][0])?'':$_REQUEST['data'][0];      //订单来源
		$Order_itemid = empty($_REQUEST['data'][1])?'':$_REQUEST['data'][1];        //itemsid
		$sku=empty($_REQUEST['data'][2])?'':$_REQUEST['data'][2];       //sku
		$NameCNList = empty($_REQUEST['data'][3])?'':$_REQUEST['data'][3];        //中文报关名
		$NameENList = empty($_REQUEST['data'][4])?'':$_REQUEST['data'][4]; 		//英文报关名
		$PriceList = empty($_REQUEST['data'][5])?'0':$_REQUEST['data'][5]; 		//金额
		$WeightList = empty($_REQUEST['data'][6])?'0':$_REQUEST['data'][6];			 //重量
		$detailHsCodeList = empty($_REQUEST['data'][7])?'':$_REQUEST['data'][7];			 //海关编码
		
		if(strpos($WeightList,'.') || (float)$WeightList<0){
			return json_encode(['success'=>false, 'message'=>'申报重量不能为小数和负数','data'=>'']);
		}

		$success = true;
		$err="";

		//曾经修改过的以后都为修改过
		$item = OdOrderItem::find()->where(['order_item_id'=>$Order_itemid])->one();
		$declaration = json_decode($item->declaration,true);
		if(!empty($declaration['isChange']) && $declaration['isChange']=='Y')
			$ischange='Y';
		else
			$ischange = empty($_REQUEST['ischange'])?'N':'Y';			 //本次是否有改变
			
		$msg = "修改成功！"; //返回结果
		$log=array($Order_itemid,$NameCNList,$NameENList,$PriceList,$WeightList,$detailHsCodeList);
		OperationLogHelper::batchInsertLog('order', $log, '修改报关信息',$sku.'报关信息修改为'.json_encode($log));
		$result=OrderUpdateHelper::setOrderItemDeclaration($Order_itemid,$NameCNList,$NameENList,$PriceList,$WeightList,$detailHsCodeList,$ischange);
		if(isset($result['ack']) && $result['ack']==false){
			$err.=$result['message']." err1;";
			$success=false;
		}

		if($type==1 || $type==2){
			$items=OrderGetDataHelper::getPayOrderItemBySKU($sku);
			foreach ($items as $itemsone){
				$result=OrderUpdateHelper::setOrderItemDeclaration($itemsone->order_item_id,$NameCNList,$NameENList,$PriceList,$WeightList,$detailHsCodeList,'Y');
				if(isset($result['ack']) && $result['ack']==false){
					$err.=$itemsone->order_id.':'.$result['message']." err1;";
					$success=false;
				}
			}
			
			if($type==2 && $success==true){
				$item = OdOrderItem::find()->where(['order_item_id'=>$Order_itemid])->one();
				$tmp_platform_itme_id = \eagle\modules\order\helpers\OrderGetDataHelper::getOrderItemSouceItemID($order_source, $item);
				$declared_params[]=array(
						'platform_type'=>$order_source,
						'itemID'=>$tmp_platform_itme_id,
						'sku'=>$sku,
						'ch_name'=>$NameCNList,
						'en_name'=>$NameENList,
						'declared_value'=>$PriceList,
						'declared_weight'=>$WeightList,
						'detail_hs_code'=>$detailHsCodeList,
				);
				$result=CarrierDeclaredHelper::setOrderSkuDeclaredInfoBatch($declared_params);
				if($result==false){
					$err.=$itemsone->order_id.':'."保存失败err2;";
					$success=false;
				}
				
				if(!empty($item->root_sku)){
					$info=array(
							'declaration_ch'=>$NameCNList,
							'declaration_en'=>$NameENList,
							'declaration_value'=>$PriceList,
							'prod_weight'=>$WeightList,
							'declaration_code'=>$detailHsCodeList,
					);
					OperationLogHelper::batchInsertLog('order', $info, '修改报关信息',$sku.'商品报关信息修改为'.json_encode($info));
					$rt = \eagle\modules\catalog\helpers\ProductApiHelper::modifyProductInfo($item->root_sku,$info);
					if($rt['success']==false){
						$err.='商品报关信息保存失败:'.$rt['message']." err3;";
						$success=false;
					}
				}
			}
				
		}
		
		$html='<span class="nameChSpan">'.$NameCNList.'</span>&nbsp;/&nbsp;<span class="nameEnSpan">'.$NameENList.'</span>&nbsp;/&nbsp;$<span class="deValSpan">'.$PriceList.'</span>&nbsp;/&nbsp;<span class="weightSpan">'.$WeightList.'</span>（g）&nbsp;/&nbsp;<span class="hsCodeSpan">'.$detailHsCodeList.'</span>';
	
		return json_encode(['success'=>$success, 'message'=>$err,'data'=>$html]);
	}
	
	/**
	 +----------------------------------------------------------
	 * 打开配对sku
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		lgw 	2017/03/07				初始化
	 +----------------------------------------------------------
	 **/
	public function actionPairProduct(){
		$orderitemid=empty($_POST['orderitemid'])?'':$_POST['orderitemid'];
		$sku=empty($_POST['sku'])?'':$_POST['sku'];
		$type=empty($_POST['type'])?'':$_POST['type'];   //配对/解除/更换
	
		return $this->renderPartial('pairproduct',['orderitemid'=>$orderitemid  , 'sku'=>$sku,'type'=>$type]);
	}
	
	/**
	 +----------------------------------------------------------
	 * 列出配对sku搜索页的商品
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		lgw 	2017/03/07				初始化
	 +----------------------------------------------------------
	 **/
	public function actionSelectWareHoseProducts(){
		$page=empty($_POST['page'])?1:$_POST['page'];
		$conditionList=empty($_POST['condition'])?[]:$_POST['condition'];
		$type=empty($_POST['type'])?[]:$_POST['type'];
		
		if(empty($conditionList))  //条件为空的就全部
			$condition=[];
		else{
			if($type==1){
				$condition [] = ['or'=>['like','sku', $conditionList]];
			}
			else {
				$condition [] = ['or'=>['like','name', $conditionList]];
				$condition [] = ['or'=>['like','prod_name_ch', $conditionList]];
				$condition [] = ['or'=>['like','prod_name_en', $conditionList]];
				$condition [] = ['or'=>['like','declaration_ch', $conditionList]];
				$condition [] = ['or'=>['like','declaration_en', $conditionList]];
			}
		}

		$condition [] = ['and'=>"type!='C'"];
		$productData =ProductHelper::getProductlist($condition,'sku','asc',10,null,true,$page-1);

		if(empty($productData['data'])){
			$html='<div id="productbody_nosearch" class="modal-body tab-content col-xs-12"><span>找不到相应的商品,需要<a href="/catalog/product/index" target="_blank">创建商品</a></span></div>';
		}
		else{
			$html='<table class="table table-condensed table-bordered myj-table">
			<thead>
			<tr class="text-center">
			<th>商品信息</th>
			<th>操作</th>
			<th>商品信息</th>
			<th>操作</th>
			</tr>
			</thead>
			<tbody>';
			$Wrap=0;
			if(!empty($productData['data'])){
				foreach($productData['data'] as $index=>$row):
				if($Wrap==0){
					$html.='<tr>';
			    } 
			                       $html.='<td style="width:309px;border: 1px solid #ccc;">
			                            <table>
			                                <tbody><tr>
			                                    <td>
			                                        <div class="quoteImgDivOut" style="margin-left: 6px;">
			                                            <div class="quoteImgDivIn">
			                                                        <img id="search_photo" src="'.$row['photo_primary'].'" class="imgCss" style="cursor: wait">
			                                            </div>
			                                        </div>
			                                    </td>
			                                    <td class="vAlignTop" style="padding-left: 6px;">
			                                                <p class="m0 txtleft">
			                                                    <span id="search_name" data-placement="right" data-toggle="popover" data-content="" data-original-title="" title="">
			                                                    '.$row['name'].'
			                                                    </span>
			                                                </p>
			                                                <p class="m0 txtleft">
			                                                    <span id="search_sku" data-placement="right" data-toggle="popover" data-content="" data-original-title="" title="">
			                                                    '.$row['sku'].'
			                                                    </span>
			                                                </p>
			                                    </td>
			                                </tr>
			                            </tbody></table>
			                        </td>
			                        <td style="width:77px;border: 1px solid #ccc;">
			                            <a class="Choice" href="javascript:javascript:OrderCommon.Choice(\''.$row['sku'].'\');" >选择</a>
			                        </td>'; 
			                if($Wrap==1)
			                	$Wrap=0;
			                else 
			                	$Wrap=1;
			                if($Wrap==0){ 
			                	$html.='</tr>';
			                } 
			                 endforeach;
			                 }
			           $html.='</tbody>
			        </table>';
			if(! empty($productData['pagination'])):
			//SizePager::widget(['pagination'=>$productData['pagination'] , 'pageSizeOptions'=>array( 5 , 20 , 50 , 100 , 200 ) , 'class'=>'btn-group dropup'])
			$html.='<div>
			    <div id="pagination" class="btn-group" style="width: 100%;text-align: center;">'.
			    	\yii\widgets\LinkPager::widget(['pagination' => $productData['pagination'],'options'=>['class'=>'pagination']]).
				'</div>
			</div>';
			endif;
		}
		return $html;
	}
	
	/**
	 +----------------------------------------------------------
	 * 保存配对sku
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		lgw 	2017/03/07				初始化
	 +----------------------------------------------------------
	 **/
	public function actionSaveRealtion(){
		$orderitemid=empty($_POST['orderitemid'])?'':$_POST['orderitemid'];
		$rootsku=empty($_POST['rootsku'])?'':$_POST['rootsku'];
		$sku=empty($_POST['sku'])?'':$_POST['sku'];
		$ordertype=empty($_POST['ordertype'])?'':$_POST['ordertype'];    //操作类型 0为解除配对，1为修改配对
		$type=empty($_POST['type'])?'':$_POST['type'];    //保存类型 0：本订单；1：所有已付款；2：所有已付款和以后
		$fullName = \Yii::$app->user->identity->getFullName();
		$msg='';
		
		$data_rootsku=($ordertype==0?'':$rootsku);    
		$tmp_type = !$type; // 是否更新待发货数量 ， 只需要更新一次就好了， 所以 假如用户选择了批量更新的话，  单个就不需要更新待发货数量
		$result=array('ack'=>true,'success'=>true);
		
		$item = OdOrderItem::find()->select(['root_sku', 'order_id'])->where(['order_item_id'=>$orderitemid])->one();
		$old_root_sku=$item->root_sku;
		
		$log=array($orderitemid,$data_rootsku,$tmp_type,$fullName);
		OperationLogHelper::batchInsertLog('order', $log, '配对SKU-1','['.$sku.']配对了['.$data_rootsku.']');
		$rt=OrderUpdateHelper::saveItemRootSKU($orderitemid,$data_rootsku,$tmp_type , $fullName ,'sku配对');
		if($rt['ack']==false){
			$result['ack']=false;
			$msg.=$rt['message'].';';
		}
		
		//重置first_sku
		OrderUpdateHelper::resetFirstSku($item->order_id);

		if($type==1 || $type==2){
			$rt=OrderUpdateHelper::batchSaveItemRootSKU($sku,$data_rootsku ,$old_root_sku, $type, $fullName ,'sku配对');
			if($rt['ack']==false){
				$result['ack']=false;
				$msg.=$rt['message'].';';
			}
			
			if($type==2 && $result['ack']==true){
				if($ordertype==0){
					//删除别名
					$log=array($rootsku,$sku);
					OperationLogHelper::batchInsertLog('order', $log, '删除别名','['.$rootsku.']删除别名:['.$sku.']');
					$rt=\eagle\modules\catalog\helpers\ProductApiHelper::deleteSkuAliases($rootsku,$sku);
					if($rt['success']==false){
						$msg.='删除别名失败:'.$rt['msg'].';';
						$result['success']=false;
					}	
				}
				else{
					//已经配对的先解除配对
					if(!empty($old_root_sku)){
						$log=array($old_root_sku,$sku);
						OperationLogHelper::batchInsertLog('order', $log, '删除别名','['.$old_root_sku.']删除别名:['.$sku.']');
						$rt=\eagle\modules\catalog\helpers\ProductApiHelper::deleteSkuAliases($old_root_sku,$sku);
						if($rt['success']==false){
							$msg.='删除别名失败:'.$rt['msg'].';';
							$result['success']=false;
						}
					}
						
					//添加别名
					$aliasesList[$sku]=array(
							'alias_sku'=>$sku,
							'forsite'=>'',
							'pack'=>1,
							'comment'=>''
					);
					OperationLogHelper::batchInsertLog('order', $aliasesList[$sku], '添加别名','['.$data_rootsku.']添加别名['.$sku.']');
					$rt=\eagle\modules\catalog\helpers\ProductApiHelper::addSkuAliases($data_rootsku,$aliasesList);
					if($rt['success']==false){
						$msg.='添加别名失败:'.$rt['message'].';';
						$result['success']=false;
					}
				}
			}
		}

		if($result['ack']==true){
			if($ordertype==0){
				$html=$data_rootsku."<br>
						<button type='button' class='iv-btn btn-important rootskubtn-pd' onclick='OrderCommon.PairProduct(\"".$orderitemid."\",\"".$sku."\",\"".$rootsku."\",1)'>配对商品</button>
								";
			}
			else{
				//获取库存信息
				$stock_list = array();
				$default_warehouse_id = 0;
				if(!empty($item)){
					$row = OdOrder::find()->select(['default_warehouse_id'])->where(['order_id' => $item->order_id])->one();
					if (!empty($row)){
						$default_warehouse_id = $row->default_warehouse_id;
						$warehouse_list[] = $row->default_warehouse_id;
						$sku_list[] = $data_rootsku;
						$stock_list = \eagle\modules\inventory\apihelpers\InventoryApiHelper::GetSkuStock($sku_list, $warehouse_list);
					}
				}
				
				$html=$data_rootsku."<br>
						<span style='color: #999999;'> 可用库存: ".(empty($stock_list[$default_warehouse_id][$data_rootsku]) ? '0' : $stock_list[$default_warehouse_id][$data_rootsku])."</span>
						<br>
						<button type='button' class='iv-btn btn-important rootskubtn-pd' onclick='OrderCommon.PairProduct(\"".$orderitemid."\",\"".$sku."\",\"".$rootsku."\",1)'>更换配对</button>
						<button type='button' class='iv-btn btn-warn rootskubtn-pd' style='margin-left:5px;' onclick='OrderCommon.PairProduct(\"".$orderitemid."\",\"".$sku."\",\"".$rootsku."\",0)'>解除配对</button>
								";
			}
		}
	
		$json=array(
				'result'=>$result,
				'html'=>isset($html)?$html:'',
				'message'=>$msg,
		);
	
		return json_encode($json);
	}
	/**
	 +----------------------------------------------------------
	 * 编辑商品刷新报关信息页面
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		lgw 	2017/03/07				初始化
	 +----------------------------------------------------------
	 **/
	public function actionRefreshOrderDeclarationEdit(){
		if (!empty($_REQUEST['order_id'])){
			$order = OdOrder::findOne($_REQUEST['order_id']);
			if (!empty($order)){	
				return OrderFrontHelper::displayViewOrderDeclarationInfo($order);
			}else{
				return '';
			}
	
		}else{
			return '';
		}
	}//end of function actionRefreshEditOrderItemInfo
	public function actionGetOrderRedisData(){
		$platform = empty($_REQUEST['platform'])?'all':$_REQUEST['platform'];
		$category = empty($_REQUEST['category'])?'':$_REQUEST['category'];
		
		$uid = \Yii::$app->user->id;
		$puid = \Yii::$app->user->identity->getParentUid();
		var_dump($puid);
		var_dump($uid);
		var_dump($platform);
		var_dump($category);
		$rtn = RedisHelper::getOrderCache2($puid, $uid, $platform, $category);
		var_dump($rtn);
		exit();
	}
	
	public function actionDelOrderRedisData(){
		$platform = empty($_REQUEST['platform'])?'all':$_REQUEST['platform'];
		$category = empty($_REQUEST['category'])?'':$_REQUEST['category'];
	
		$uid = \Yii::$app->user->id;
		$puid = \Yii::$app->user->identity->getParentUid();
	
		$rtn = RedisHelper::delOrderCache($puid, $platform, $category);
		var_dump($rtn);
		
		$rtn = RedisHelper::delSubAccountOrderCache($puid, $uid, $platform, $category);
		var_dump($rtn);
		exit();
	}
	
	/**
	 +----------------------------------------------------------
	 * 批量合并订单
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh 	2017/04/27				初始化
	 +----------------------------------------------------------
	 **/
	public function actionBatchMergeOrder(){
		
		AppTrackerApiHelper::actionLog("Oms-erp", "/order/mergeorder");
		if (\Yii::$app->request->isPost){
			$errorMsg = '';
			if (!empty($_REQUEST['orderIdList'])){
				foreach($_REQUEST['orderIdList'] as $orderIdList){
					$rt = OrderHelper::mergeOrder($orderIdList);
					if ($rt['success'] ==false){
						$idStr = '';
						foreach($orderIdList  as $orderid){
							$idStr .= $orderid.",";
						}
						$errorMsg .= $idStr.$rt['message'];
					}
				}
			}
			
			if (!empty($errorMsg)){
// 				return $this->render('//errorview',['title'=>'订单合并','error'=>$errorMsg]);
				echo $errorMsg;
			}else{
				echo "MergeSuccess";
				//echo "<script language='javascript'>alert('Success');window.location.reload();</script>";
			}
			return;
		}
		
		
		
	}//end of function actionBatchMergeOrder
	
	/**
	 * 近30日 指定平台 所有用户的平台订单日均统计
	 * @param string $auth
	 * @param string $platform
	 */
	public function actionUserOrderCount($auth,$platform){
		if($auth!=='eagle-admin')
			exit('auth denied !');
		
		if(empty($platform))
			exit('platform cant not be empty !');
		
		$sql = "select puid, update_date, ".$platform."_orders as orders, oms_action_logs,addi_info from user_30day_order_statistic where ".$platform."_orders <> 0";
		if(empty($_REQUEST['sort'])){
			$sql .= " order by ".$platform."_orders DESC ";
		}else{
			if( '-' == substr($_REQUEST['sort'],0,1) ){
				$sort = substr($_REQUEST['sort'],1);
				$order = 'desc';
			} else {
				$sort = $_REQUEST['sort'];
				$order = 'asc';
			}
			
			$sql .= " order by $sort $order ";
		}
		$command = \Yii::$app->db_queue->createCommand( $sql );
		$pagination = new Pagination([
			'totalCount' => count( $command->queryAll() ),
			'pageSize'=>isset($_REQUEST['per-page'])?$_REQUEST['per-page']:50,
			'pageSizeLimit'=>[50,500],//每页显示条数范围
		]);
		
		$sql .= " LIMIT ".$pagination->offset.",".$pagination->limit;
		//echo "<br><br>".$sql."<br><br>";
		$command = \Yii::$app->db_queue->createCommand( $sql );
		$datas = $command->queryAll();
		
		return $this->render('_user_order_count',[
			'datas'=>$datas,
			'pages'=>$pagination,
		]);
	}

	//拆分订单界面split-order-new
	public function actionSplitOrderNew(){
		$orderid = empty($_POST['orderid'])?'':$_POST['orderid'];
		
// 		$orderid[1]="55673";

		$obOrderRelation=OrderRelation::find()->where(['son_orderid'=>$orderid,'type'=>'merge'])->asArray()->all();
		$obOrderRelation2=OrderRelation::find()->where(['son_orderid'=>$orderid,'type'=>'split'])->asArray()->all();
		$obOrderRelation3=OrderRelation::find()->where(['father_orderid'=>$orderid,'type'=>'split'])->asArray()->all();
		$odOrder=OdOrder::find()->where(['order_id'=>$orderid,'order_capture'=>'Y'])->asArray()->all();
		if(!empty($obOrderRelation) || !empty($obOrderRelation2) || !empty($obOrderRelation3) || !empty($odOrder)){
			$msg="";
			foreach ($obOrderRelation as $obOrderRelationone){
				$msg.='<div class="bootbox-body">小老板订单号:'.$obOrderRelationone['father_orderid'].'不能进行拆分;</div>';
			}
			foreach ($obOrderRelation2 as $obOrderRelation2one){
				$msg.='<div class="bootbox-body">小老板订单号:'.$obOrderRelation2one['son_orderid'].'不能进行拆分;</div>';
			}
			foreach ($obOrderRelation3 as $obOrderRelation3one){
				$msg.='<div class="bootbox-body">小老板订单号:'.$obOrderRelation3one['father_orderid'].'不能进行拆分;</div>';
			}
			foreach ($odOrder as $odOrderone){
				$msg.='<div class="bootbox-body">小老板订单号:'.$odOrderone['order_id'].'不能进行拆分;</div>';
			}
			return $msg;
		}
			
		
		
		$item = OdOrderItem::find()->where(['order_id'=>$orderid,'manual_status'=>'enable'])->asArray()->all();
		
		$delarr=array();
		$splitarr=array();
		$temp=array();
		foreach ($item as $itemone){
			$orderid_len11=preg_replace('/^0+/','',$itemone['order_id']);
			
			if(array_key_exists($orderid_len11,$temp)){
				$delarr[$orderid_len11][0][$itemone['order_item_id']]=array(
								'photo'=>'',
								'sku'=>'',
								'quantity'=>'',
				);
			}
			else{
				$delarr[$orderid_len11]=array(
								'0'=>array(
										$itemone['order_item_id']=>array(
												'photo'=>'',
												'sku'=>'',
												'quantity'=>0,
										),
								),
				);
				
				$temp[$orderid_len11]=$orderid_len11;
			}
			
			$splitarr[$orderid_len11][$itemone['order_item_id']]['qty']=$itemone['quantity'];
		}

		return $this->renderPartial('splitordernew',['orderid'=>$orderid,
													'order_item_list'=>$item,
													'deldata'=>base64_encode(json_encode($delarr)),
													'splitqty'=>base64_encode(json_encode($splitarr)),
				]);
	}
	
	//移动拆分订单的数量
	public function actionSplotOrderChildren(){
		$order_item_id = empty($_POST['order_item_id'])?'':$_POST['order_item_id'];
		$dellist = empty($_POST['dellist'])?'':json_decode(base64_decode($_POST['dellist']),true);     //已拆分的数据
		$splitqty = empty($_POST['splitqty'])?'':json_decode(base64_decode($_POST['splitqty']),true); //原订单数量
		$signt = $_POST['signt'];   //拆分到哪个订单
		$sign = $_POST['sign'];   //加减 0:+ 1：- 2:删除
		
		$item = OdOrderItem::find()->where(['order_item_id'=>$order_item_id])->asArray()->all();
		$item=$item[0];

		$html="";
		$orderitemid_len11=preg_replace('/^0+/','',$item['order_id']);

		$dellist[$orderitemid_len11][$signt][$order_item_id]['photo']=$item['photo_primary'];
		$dellist[$orderitemid_len11][$signt][$order_item_id]['sku']=$item['sku'];

		if($sign==0){
			$dellist[$orderitemid_len11][$signt][$order_item_id]['quantity']=$dellist[$orderitemid_len11][$signt][$order_item_id]['quantity']+1;
			$splitqty[$orderitemid_len11][$order_item_id]['qty']=$splitqty[$orderitemid_len11][$order_item_id]['qty']-1;
		}
		else if($sign==2){
			$dellist[$orderitemid_len11][$signt][$order_item_id]['photo']='';
			$dellist[$orderitemid_len11][$signt][$order_item_id]['sku']='';
			$splitqty[$orderitemid_len11][$order_item_id]['qty']=$splitqty[$orderitemid_len11][$order_item_id]['qty']+$dellist[$orderitemid_len11][$signt][$order_item_id]['quantity'];
			$dellist[$orderitemid_len11][$signt][$order_item_id]['quantity']=0;
		}
		else{
			$dellist[$orderitemid_len11][$signt][$order_item_id]['quantity']=$dellist[$orderitemid_len11][$signt][$order_item_id]['quantity']-1;
			$splitqty[$orderitemid_len11][$order_item_id]['qty']=$splitqty[$orderitemid_len11][$order_item_id]['qty']+1;
		}
		
		$li_html='';
		foreach ($dellist[$orderitemid_len11][$signt] as $keys=>$dellistone){
			if(!empty($dellistone['sku']) && $dellistone['quantity']>0){
				$li_html.='
							<li class="prd ng-scope pre">
		                        <div class="mui-media">
		                                <img class="mui-media-object" src="'.$dellistone['photo'].'">
		                                <div class="mui-media-body" style="width: 85%;">
		                                    <div class="ng-binding">'.$dellistone['sku'].'</div>
		                                    <div class="input-group input-group-sm" style="margin-top:7px;">
                                        		<span class="input-group-btn"><button style="padding: 0px;" type="button" id="splitleft" class="btn btn-default input-group-btn2" onclick="OrderCommon.splitOrderChildren(this,\''.$orderitemid_len11.'\',\''.$keys.'\',1,'.$signt.')"><i class="glyphicon glyphicon-minus"></i></button></span>
                                        		<h4 class="ng-binding" id="quantity" style="font-size: 17px;text-align: center;margin-top: 6px;">'.$dellistone['quantity'].'</h4>
                                        		<span class="input-group-btn"><button style="padding: 0px;" type="button" id="splitright" class="btn btn-default input-group-btn2" onclick="OrderCommon.splitOrderChildren(this,\''.$orderitemid_len11.'\',\''.$keys.'\',0,'.$signt.')"><i class="glyphicon glyphicon-plus"></i></button></span>
                                    		</div>
		                                </div>
                                        <a href="javascript:void(0)" class="del display" data-order="'.$orderitemid_len11.'" data-item="'.$keys.'" data-index="'.$signt.'"><i class="glyphicon glyphicon-remove red"></i>删除</a>
		                           </div>
		                    </li>
					';
			}
		}
		
		$html.='<div class="panel-heading ng-binding">
	                    	'.$orderitemid_len11.'-'.($signt+1).'
	                    	<div class="pull-right"><button type="button" class="btn btn-xs btn-danger" onclick="OrderCommon.splitPackageDel(this,\''.$orderitemid_len11.'\')">删除</button></div>
	                	</div>
	                	<div class="panel-body">';
		
		if(!empty($li_html)){
			$html.='<ul class="list-unstyled">'.$li_html.'</ul>';
		}
		else{
			$html.='</div>';
		}
		
		$oldqty=0;
		foreach ($splitqty as $splitqtyone){
			foreach ($splitqtyone as $splitqtyoneone){
				$oldqty+=$splitqtyoneone['qty'];
			}
		}
		if(empty($oldqty))
			return json_encode(['code'=>'false','message'=>'原始订单必须有一个商品']);

		return json_encode(['code'=>'true','dellist'=>base64_encode(json_encode($dellist)),'splitqty'=>base64_encode(json_encode($splitqty)),'html'=>$html,'signr'=>$signt,'splitqtylist'=>json_encode($splitqty)]);
		
	}
	
	//拆分包裹
	public function actionSplitPackage(){
		$orderid = empty($_POST['orderid'])?'':$_POST['orderid'];
		$sum = $_POST['sum'];
		$dellist = empty($_POST['dellist'])?'':json_decode(base64_decode($_POST['dellist']),true);     //已拆分的数据

		if(empty($sum)){
			$item = OdOrderItem::find()->where(['order_id'=>$orderid])->asArray()->all();
			$temp=array();
			foreach ($item as $itemone){
				$orderitemid_len11=preg_replace('/^0+/','',$itemone['order_id']);
// 				while(strlen($orderitemid_len11)<11){
// 					$orderitemid_len11='0'.$orderitemid_len11;
// 				}
				
				if(array_key_exists($orderitemid_len11,$temp)){
					$dellist[$orderitemid_len11][0][$itemone['order_item_id']]=array(
							'photo'=>'',
							'sku'=>'',
							'quantity'=>'',
					);
				}
				else{
					$dellist[$orderitemid_len11]=array(
							'0'=>array(
									$itemone['order_item_id']=>array(
											'photo'=>'',
											'sku'=>'',
											'quantity'=>0,
									),
							),
					);
			
					$temp[$orderitemid_len11]=$orderitemid_len11;
				}
			}
			
		}
		else{
			$dellist[$orderid][$sum]=$dellist[$orderid][$sum-1];
			foreach ($dellist[$orderid][$sum] as $keys=>$dellistone){
				foreach($dellistone as $title=>$dellistoneone)
					$dellist[$orderid][$sum][$keys][$title]='';
			}
		}
		
		$html='
				<div class="panel panel-default ng-scope" id="pk'.$orderid.'-'.$sum.'" data-number="'.$orderid.'">
                <div class="panel-heading ng-binding">
                    '.$orderid.'-'.($sum+1).'
                    <div class="pull-right"><button type="button" class="btn btn-xs btn-danger" onclick="OrderCommon.splitPackageDel(this,\''.$orderid.'\')">删除</button></div>
                </div>
                <div class="panel-body">
                </div>
    			</div>
		';
		
		return json_encode(['dellist'=>base64_encode(json_encode($dellist)),'html'=>$html]);
		
	}
	
	//删除包裹
	public function actionSplitPackageDel(){
		$divid = empty($_POST['divid'])?'':$_POST['divid'];
		$dellist = empty($_POST['dellist'])?'':json_decode(base64_decode($_POST['dellist']),true);     //已拆分的数据
		$splitqty = empty($_POST['splitqty'])?'':json_decode(base64_decode($_POST['splitqty']),true);
		
		$divid_arr=explode('-',$divid);
		$delindex=$divid_arr[1];
		$orderid=substr($divid_arr[0], 2);
		
		$dellist_tmp=$dellist;
		
		foreach ($dellist_tmp[$orderid][$delindex] as $keys=>$dellist_tmpone){
			$splitqty[$orderid][$keys]['qty']=$splitqty[$orderid][$keys]['qty']+$dellist_tmpone['quantity'];
		}
		unset($dellist_tmp[$orderid][$delindex]);
		
		unset($dellist[$orderid]);

		//重新组织包裹顺序
		$newsign=0;
		foreach ($dellist_tmp[$orderid] as $keys=>$dellist_tmpone){
			$dellist[$orderid][$newsign]=$dellist_tmpone;
			$newsign++;
		}
		
		return json_encode(['dellist'=>base64_encode(json_encode($dellist)),'splitqty'=>base64_encode(json_encode($splitqty)),'splitqtylist'=>json_encode($splitqty)]);
	}
	
	//拆分订单
	public function actionSplitOrderReorder(){
		if (!empty($_REQUEST['orderIdList'])){

			if (is_array($_REQUEST['orderIdList'])){
				$splotOrderDelList=empty($_POST['splotOrderDelList'])?array():json_decode(base64_decode($_POST['splotOrderDelList']),true);
				$splotOrderqtyList=empty($_POST['splotOrderqtyList'])?array():json_decode(base64_decode($_POST['splotOrderqtyList']),true);
				
				$qtysum=0;
				foreach ($splotOrderDelList as $splotOrderDelListone){
					foreach ($splotOrderDelListone as $splotOrderDelListoneone){
						foreach ($splotOrderDelListoneone as $splotOrderDelListoneoneone){
							$qtysum+=$splotOrderDelListoneoneone['quantity'];
						}
					}
				}
				if(empty($qtysum))
					exit(json_encode(['success'=>false,'message'=>'没有包裹拆分！']));
				
				$rt = OrderHelper::splitOrderReorder($_REQUEST['orderIdList'],'order','拆分订单',$splotOrderDelList,$splotOrderqtyList);
				exit(json_encode(['success'=>(empty($rt['failure'])?$rt['success']:0),'message'=>$rt['message']]));
	
			}else{
	
				exit(json_encode(['success'=>false,'message'=>'E001 内部错误， 请联系客服！']));
			}
		}else{
			exit(json_encode(['success'=>false,'message'=>'没有选择订单！']));
		}
	}

	//取消拆分订单页面
	public function actionSplitOrderCancel(){
		$orderid = empty($_POST['orderid'])?'':$_POST['orderid'];

		/*2017-07-08有bug
		$orderList=array();
		$orderChildrenList=array();
		
		foreach ($orderid as $orderidone){
			$OrderRelation=OrderRelation::find()->select(['father_orderid'])->where(['father_orderid'=>$orderidone,'type'=>'split'])->orWhere(['son_orderid'=>$orderidone,'type'=>'split'])->asArray()->one();	
			if(!empty($OrderRelation)){
				$obOrderRelation=OrderRelation::find()->where(['father_orderid'=>$OrderRelation['father_orderid'],'type'=>'split'])->asArray()->all();

				if(!empty($obOrderRelation) && !array_key_exists($obOrderRelation[0]['father_orderid'], $orderList)){				
					$order=OdOrder::find()->select(['order_status'])->where(['order_id'=>$obOrderRelation[0]['father_orderid']])->asArray()->all();
					$orderitem=OdOrderItem::find()->select(['order_item_id','sku','photo_primary','ordered_quantity','quantity'])->where(['order_id'=>$obOrderRelation[0]['father_orderid'],'manual_status'=>'enable'])->asArray()->all();
					$orderList[$obOrderRelation[0]['father_orderid']]['order_status']=OdOrder::$status[$order[0]['order_status']];
					foreach ($orderitem as $orderitemone){
						$orderList[$obOrderRelation[0]['father_orderid']]['items'][]=array(
								'sku'=>$orderitemone['sku'],
								'photo_primary'=>$orderitemone['photo_primary'],
								'quantity'=>$orderitemone['quantity'],
						);
					}
					$orderChildrenList[$obOrderRelation[0]['father_orderid']]=array();
					foreach ($obOrderRelation as $obOrderRelationone){
						$order=OdOrder::find()->select(['order_status'])->where(['order_id'=>$obOrderRelationone['son_orderid']])->asArray()->all();
						$orderitem=OdOrderItem::find()->select(['order_item_id','sku','photo_primary','ordered_quantity','quantity'])->where(['order_id'=>$obOrderRelationone['son_orderid']])->asArray()->all();
						$orderChildrenList[$obOrderRelation[0]['father_orderid']][$obOrderRelationone['son_orderid']]['order_status']=OdOrder::$status[$order[0]['order_status']];
						foreach ($orderitem as $orderitemone){
							$orderChildrenList[$obOrderRelation[0]['father_orderid']][$obOrderRelationone['son_orderid']]['items'][]=array(
									'sku'=>$orderitemone['sku'],
									'photo_primary'=>$orderitemone['photo_primary'],
									'quantity'=>$orderitemone['quantity'],
							);
						}
					}
				}
				
			}
			
			
		}*/
		
		//////////////////////////////////////////////////2017-07-08
		$OrderRelation=OrderRelation::find()->select(['father_orderid'])->where(['father_orderid'=>$orderid,'type'=>'split'])->orWhere(['son_orderid'=>$orderid,'type'=>'split'])->asArray()->one();
		
		$orderList=array();
		$orderChildrenList=array();
		if(!empty($OrderRelation)){
			$obOrderRelation=OrderRelation::find()->where(['father_orderid'=>$OrderRelation['father_orderid'],'type'=>'split'])->asArray()->all();
		
			if(!empty($obOrderRelation)){
		
				$order=OdOrder::find()->select(['order_status'])->where(['order_id'=>$obOrderRelation[0]['father_orderid']])->asArray()->all();
				$orderitem=OdOrderItem::find()->select(['order_item_id','sku','photo_primary','ordered_quantity','quantity'])->where(['order_id'=>$obOrderRelation[0]['father_orderid'],'manual_status'=>'enable'])->asArray()->all();
				$orderList[$obOrderRelation[0]['father_orderid']]['order_status']=OdOrder::$status[$order[0]['order_status']];
				foreach ($orderitem as $orderitemone){
					$orderList[$obOrderRelation[0]['father_orderid']]['items'][]=array(
							'sku'=>$orderitemone['sku'],
							'photo_primary'=>$orderitemone['photo_primary'],
							'quantity'=>$orderitemone['quantity'],
					);
				}
		
				foreach ($obOrderRelation as $obOrderRelationone){
					$order=OdOrder::find()->select(['order_status'])->where(['order_id'=>$obOrderRelationone['son_orderid']])->asArray()->all();
					$orderitem=OdOrderItem::find()->select(['order_item_id','sku','photo_primary','ordered_quantity','quantity'])->where(['order_id'=>$obOrderRelationone['son_orderid']])->asArray()->all();
					$orderChildrenList[$obOrderRelationone['son_orderid']]['order_status']=OdOrder::$status[$order[0]['order_status']];
					foreach ($orderitem as $orderitemone){
						$orderChildrenList[$obOrderRelationone['son_orderid']]['items'][]=array(
								'sku'=>$orderitemone['sku'],
								'photo_primary'=>$orderitemone['photo_primary'],
								'quantity'=>$orderitemone['quantity'],
						);
					}
				}
			}
				
		}
		
		///////////////////////////////////////////////////
		
		
		

		return $this->renderPartial('splitordercancel',['orderList'=>$orderList,
				'orderChildrenList'=>$orderChildrenList,
				]);
		
	}
	
	//取消拆分订单
	public function actionSplitorderCancels(){
		try{
			$orderid = empty($_POST['orderIdList'])?'':$_POST['orderIdList'];
			
			$rt = OrderHelper::splitOrderReorderCancel($orderid);

			return json_encode($rt);
		}
		catch (\Exception $err){
			return json_encode(['code'=>1,'message'=>$err->getMessage()]);
		}
	}
	
	
	/**
	 +----------------------------------------------------------
	 * 设置指定订单的数据（为测试人员专用，只对user1有效）
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh 	2017/04/27				初始化
	 +----------------------------------------------------------
	 **/
	public function actionSetDemoData(){
		 
		if (!empty($_REQUEST['orderid'])){
			$updateSql = "update  od_order_v2  set order_capture = 'N' where order_id  = '".$_REQUEST['orderid']."'";
			$updateRT = \Yii::$app->subdb->createCommand($updateSql)->execute();
			if ($updateRT){
				echo "done!";
			}else{
				echo "orderid not exist or had done ！";
			}
			
		}else{
			echo "not any order id ！";
		}
	}

	/**
	 +----------------------------------------------------------
	 * 编辑订单运输服务获取跟踪号html
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		lgw 	2017/06/12				初始化
	 +----------------------------------------------------------
	 **/
	public function actionReApplyTrackNum(){
		try{
			$shipping_method_code=empty($_POST['shipping_method_code'])?'0':$_POST['shipping_method_code'];
			$change_warehouse=empty($_POST['change_warehouse'])?'0':$_POST['change_warehouse'];
			$orderid=empty($_POST['orderid'])?'':$_POST['orderid'];
			$html='';

			$rt=CarrierOpenHelper::getCarrierOrderTrackingNoHtml($orderid,$change_warehouse,$shipping_method_code);
			if($rt['error']==1){
				$html='<div>'.$rt['msg'].'</div>';
			}
			else{
				$html = isset($rt['data']['html']) ? $rt['data']['html'] : '';
				
// 				if(!empty($rt['data'])){
// 					$html=$rt['data']['html'];
// 				}
				
// 				if(empty($html)){
// // 					$html='<div>运输界面获取失败</div>';
// // 					$rt['error']=1;
// 				}
			}
		}
		catch (\Exception $err){
			$html='<div>运输界面获取失败,'.$err->getMessage().'</div>';
			$rt['error']=1;
		}

		return $this->renderPartial('applyTrackNum',['html'=>$html,'error'=>$rt['error'],'msg'=>$rt['msg'],
				'order_id'=>$orderid, 'tracking_number'=>(isset($rt['tracking_number']) ? $rt['tracking_number'] : '')]);
	}
	/**
	 +----------------------------------------------------------
	 * 编辑订单运输服务是否显示网址
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		lgw 	2017/06/12				初始化
	 +----------------------------------------------------------
	 **/
	public function actionChangeshippingmethodcode(){
		$selectval=$_POST['selectval'];
		$platForm=$_POST['platForm'];
		$html='';
		
		$rt=\eagle\modules\carrier\helpers\CarrierOpenHelper::getShippingCodeByPlatform($platForm);
		if($rt['web_url_tyep']===1){
			$html='<label class="col-md-2 control-label p0" style="line-height: 40px;width:13%;">查询网址：</label>
								<input type="text" class="form-control" id="change_web2" style="width: 300px;" value="http://www.17track.net">';
		}
		else if($rt['web_url_tyep']===2){
			$shippingServicesArr=empty($selectval)?reset($rt['shippingServices']):$rt['shippingServices'][$selectval];
			if($shippingServicesArr['is_web_url']==1){
				$html='<label class="col-md-2 control-label p0" style="line-height: 40px;width:13%;">查询网址：</label>
									<input type="text" class="form-control" id="change_web2" style="width: 300px;"  value="http://www.17track.net">';
			}
		}
		
		return $html;
		
	}
	
	/**
	 * 订单列表页面进行批量平台标记发货,提供给用户填写物流号，运输服务等的页面
	 * @author fanjs
	 */
	public function actionSignshipped(){
		AppTrackerApiHelper::actionLog("Oms-erp", "/order/signshipped");
		if (\Yii::$app->request->getIsPost()){
			//用于区分不同js的调用入口
			if(empty($_REQUEST['js_submit'])){
				$tmpOrders = \Yii::$app->request->post()['order_id'];
			}else{
				$tmpOrders = json_decode($_REQUEST['order_id'], true);
			}
		}else {
			$tmpOrders = [\Yii::$app->request->get('order_id')];
		}
		
		if(empty($tmpOrders)){
			return $this->renderPartial('signshipped_new', ['error_arr'=>['title'=>'虚拟发货','error'=>'未找到相应订单']]);
// 			return $this->render('//errorview',['title'=>'虚拟发货','error'=>'未找到相应订单']);
		}
		$orders = OdOrder::find()->where(['in','order_id',$tmpOrders])->andwhere(['order_capture'=>'N'])->all();
		if (empty($orders)){
			return $this->renderPartial('signshipped_new', ['error_arr'=>['title'=>'虚拟发货','error'=>'未找到有效订单']]);
// 			return $this->render('//errorview',['title'=>'虚拟发货','error'=>'未找到有效订单']);
		}
			
		$allPlatform = []; // 所有平台
		foreach ($orders as $key=>$order){
			if (!in_array($order->order_source ,$allPlatform )){
				$allPlatform[] = $order->order_source;
			}
		
			if('sm' == $order->order_relation){// 合并订单标记发货获取原始订单信息发货
				$father_orderids = OrderRelation::findAll(['son_orderid'=>$order->order_id , 'type'=>'merge' ]);
				foreach ($father_orderids as $father_orderid){
					$tmpOrders[] = $father_orderid->father_orderid;
					$orders[] = OdOrder::findOne($father_orderid->father_orderid);
				}
		
				unset($orders[$key]);
			}
		}
			
		$allShipcodeMapping = [];
		foreach ($allPlatform as $_platform){
			$tmp_c_ShippingMethod = \eagle\modules\carrier\helpers\CarrierOpenHelper::getShippingCodeByPlatform($_platform);
			
			if($tmp_c_ShippingMethod['type'] == 'text'){
				$allShipcodeMapping[$_platform] = $tmp_c_ShippingMethod;
			}else{
				$tmpShippingMethod = DataStaticHelper::getUseCountTopValuesFor("erpOms_ShippingMethod", $tmp_c_ShippingMethod['shippingServices']);
				$shippingMethods = [];
				
				if(!empty($tmpShippingMethod['recommended'])){
					$shippingMethods += $tmpShippingMethod['recommended'];
					$shippingMethods[''] = '---常用/非常用 分割线---';
				}
				
				if(!empty($tmpShippingMethod['rest']))
					$shippingMethods += $tmpShippingMethod['rest'];
				
				$allShipcodeMapping[$_platform]['shippingServices'] = $shippingMethods;
				unset($tmp_c_ShippingMethod['shippingServices']);
				$allShipcodeMapping[$_platform] += $tmp_c_ShippingMethod;
			}
		}
		
		$logs = OdOrderShipped::findAll(['order_id'=>$tmpOrders]);
		
		return $this->renderPartial('signshipped_new', ['orders'=>$orders,'logs'=>$logs,'allShipcodeMapping'=>$allShipcodeMapping]);
	}
	
	/**
	 +----------------------------------------------------------
	 * 打回已付款
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		hqw 	2017/08/15				初始化
	 +----------------------------------------------------------
	 **/
	public function actionRepulsePaid(){
		if (\Yii::$app->request->isPost){
			$orderids = $_POST['orders'];
			$module = isset($_POST['m'])?$_POST['m']:'order';
			$action = isset($_POST['a'])?$_POST['a']:TranslateHelper::t('打回已付款');
			Helper_Array::removeEmpty($orderids);
			if (count($orderids)>0){
				try {
					$r = OrderApiHelper::repulsePaidOrders($orderids,$module,$action);
	
					if (!$r['success']) return $r['message'];
					return '操作已完成';
				}catch (\Exception $e){
					return $e->getMessage();
				}
			}else{
				return '选择的订单有问题';
			}
		}//end of post
	}
	
	//选择店铺
	public function actionGetPlatformSelected(){
		$type = empty($_GET['type']) ? '' : $_GET['type'];
		$platform = empty($_GET['platform']) ? '' : $_GET['platform'];
		
		$selleruseridMap = PlatformAccountApi::getAllAuthorizePlatformOrderSelleruseridLabelMap(false, false, true, true);//引入平台账号权限后
		
		//lazada平台特殊处理
		if(!empty($selleruseridMap['lazada'])){
			$tmp_lazadaMap = $selleruseridMap['lazada'];
			unset($selleruseridMap['lazada']);
			
			$selleruseridMap['lazada'] = LazadaOrderHelper::getAccountStoreNameMapByEmail($tmp_lazadaMap);
		}
		
		if(!empty($selleruseridMap['wish'])){
			$tmp_wishMap = $selleruseridMap['wish'];
			unset($selleruseridMap['wish']);
				
			$selleruseridMap['wish'] = \eagle\modules\platform\apihelpers\WishAccountsApiHelper::getWishAliasAccount($tmp_wishMap);
		}
		
		return $this->renderPartial('getPlatformSelected', ['type'=>$type, 'platform'=>$platform, 'selleruseridMap'=>$selleruseridMap]);
	}
	
	/**
	 * 打开常用的平台组合
	 */
	public function actionPlatformCommonCombinationList(){
		return $this->renderPartial('platformCommonCombinationList',[]);
	}
	
	//保存账号常用组合
	public function actionSetPlatformCommonCombination(){
		$result = array('error'=>false, 'msg'=>'');
		if(empty($_POST)){
			$result['error'] = true;
			$result['msg'] = '非法操作，请传入参数';
			exit(json_encode($result));
		}
		
		$result = OrderListV3Helper::setPlatformCommonCombination($_POST);
		
		exit(json_encode($result));
	}
	
	//清除账号常用组合
	public function actionRemovePlatformCommonCombination(){
		$result = array('error'=>false, 'msg'=>'');
		if(empty($_POST)){
			$result['error'] = true;
			$result['msg'] = '非法操作，请传入参数';
			exit(json_encode($result));
		}
	
		$result = OrderListV3Helper::removePlatformCommonCombination($_POST);
	
		exit(json_encode($result));
	}
	
	/**
	 +----------------------------------------------------------
	 * 缺货、暂停发货订单，恢复原来状态
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		lrq 	2017/09/25				初始化
	 +----------------------------------------------------------
	 **/
	public function actionRecovery(){
		if (\Yii::$app->request->isPost){
			$orderids = $_POST['orders'];
			$module = isset($_POST['m'])?$_POST['m']:'order';
			$action = isset($_POST['a'])?$_POST['a']:TranslateHelper::t('恢复发货');
			Helper_Array::removeEmpty($orderids);
			if (count($orderids)>0){
				try {
					$r = OrderApiHelper::recoveryOrders($orderids,$module,$action);
	
					if (!$r['success']) return $r['message'];
					return '操作已完成';
				}catch (\Exception $e){
					return $e->getMessage();
				}
			}else{
				return '选择的订单有问题';
			}
		}//end of post
	}
	
	//所有平台的订单统一处理界面
	public function actionAllPlatformOrderList(){
		$puid = \Yii::$app->subdb->getCurrentPuid();
		AppTrackerApiHelper::actionLog("eagle_v2", "/order/allPlatformOrderList");
		
		
		
		
	}
	
	public function actionDelItemImgCacher(){
		$rtn = ['success'=>true,'message'=>''];
		$order_ids = explode(',', $_REQUEST['order_ids']);
		try{
			$items = OdOrderItem::find()->where(['order_id' => $order_ids])->asArray()->all();
			foreach ($items as $item){
				if(!empty($item['photo_primary'])){
					$orig_url = trim($item['photo_primary']);
					ImageCacherHelper::delImageRedisCacheUrl($orig_url);
				}
			}
		} catch(\Exception $ex){
			$rtn = ['success'=>false,'message'=>'清除商品图片缓存出错'];
			SysLogHelper::SysLog_Create('Order',__CLASS__, __FUNCTION__,'error',print_r($ex->getMessage()));
			$rtn = ['success'=>false,'message'=>'清除缓存失败，重试或联系客服！'];
		}
		exit(json_encode($rtn));
	}



	/**
	+----------------------------------------------------------
	 * 批量添加商品链接 wish 邮
	 * akirametero
	+----------------------------------------------------------
	+----------------------------------------------------------
	 **/
	public  function actionShowProductUrlAddBox(){
		if (!empty($_REQUEST['orderIdList'])){

			if (is_array($_REQUEST['orderIdList'])){
				$orderList= OdOrderItem::find()->where(['order_id'=>$_REQUEST['orderIdList']])->asArray()->all();
				
				//$orderList  = OdOrder::find()->where(['order_id'=>$_REQUEST['orderIdList']])->asArray()->all();
				return $this->renderPartial('_addProductUrlBox.php' , ['orderList'=>$orderList] );
			}else{
				return $this->renderPartial('//errorview','E001 内部错误， 请联系客服！');
			}
		}else{
			return $this->renderPartial('//errorview','没有选择订单！');
		}
	}//end of actionShowAddMemoBox

	/**
	+----------------------------------------------------------
	 * 批量保存商品
	+----------------------------------------------------------
	 * @access public
	 * akirametero
	+----------------------------------------------------------
	+----------------------------------------------------------
	 **/
	public function actionBatchSaveOrderProductUrl(){
		if (!empty($_REQUEST['orderList'])){
			$orderIdList = [];
			$MemoList = [];
			$err_msg = "";
			foreach ($_REQUEST['orderList'] as $row){
				$orderIdList[] = $row['order_id'];
				$MemoList[(int)$row['order_id']]  = $row['memo']; // linux 下对00开头敏感
			}

			$OrderList = OdOrderItem::find()->where(['order_id'=>$orderIdList])->all();
			foreach($OrderList as $OrderModel ){

				$update= OdOrderItem::findOne($OrderModel['order_item_id']);
				$update->product_url= $MemoList[(int)$OrderModel->order_id];
				$update->update(false);

			}
			if (!empty($OrderList)){
				$result = ['success'=>empty($err_msg) , 'message'=>$err_msg];
			}else{
				$result = ['success'=>false , 'message'=>'E002找不到对应 的订单！'];
			}

		}else{
			$result = ['success'=>false , 'message'=>'E001内部错误， 请联系客服！'];
		}
		exit(json_encode($result));
	}//end of actionBatchSaveOrderDesc
}

?>