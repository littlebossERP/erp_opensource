<?php

namespace eagle\modules\carrier\controllers;
use Yii;
use yii\web\Controller;
use eagle\modules\order\models\OdOrder;
use yii\data\Pagination;
use eagle\modules\carrier\apihelpers\CarrierApiHelper;
use common\helpers\Helper_Array;
use eagle\models\SaasEbayUser;
use eagle\modules\util\models\OperationLog;
use eagle\modules\util\helpers\OperationLogHelper;
use eagle\modules\carrier\helpers\CarrierHelper;
use yii\base\Exception;
use eagle\modules\order\models\OdOrderShipped;
use eagle\modules\util\helpers\TranslateHelper;
use eagle\modules\order\helpers\OrderTagHelper;
use eagle\modules\order\models\OdOrderItem;
use eagle\modules\order\models\OdEbayTransaction;
use eagle\modules\inventory\helpers\InventoryApiHelper;
use eagle\modules\carrier\models\SysShippingService;
use eagle\modules\order\helpers\OrderHelper;
use eagle\modules\app\apihelpers\AppApiHelper;
use eagle\modules\catalog\helpers\ProductApiHelper;
use eagle\modules\carrier\controllers\Carrieroperate;
use eagle\modules\app\apihelpers\AppTrackerApiHelper;
use eagle\modules\order\apihelpers\OrderApiHelper;
use eagle\modules\inventory\helpers\InventoryHelper;
use eagle\modules\inventory\models\Warehouse;
use yii\helpers\Url;

class DefaultController extends \eagle\components\Controller
{
	public $enableCsrfValidation = false;

    /*
     * 物流业务入口，显示订单列表数据
     */
	public function actionIndex()
    {
    	return "<a href='/carrier/carrierprocess/waitingpost'>请使用新的流程入口</a>";
    	
    	AppTrackerApiHelper::actionLog("eagle_v2","/carrier/default/index");
    	$pageSize = isset($_GET['per-page'])?$_GET['per-page']:20;
    	if (Yii::$app->request->isPost){
    		$order_id = Yii::$app->request->post('order_id');
    		$customer_number = Yii::$app->request->post('customer_number');
    		$default_shipping_method_code = Yii::$app->request->post('default_shipping_method_code');
    		$default_carrier_code = Yii::$app->request->post('default_carrier_code');
    		$carrier_step = Yii::$app->request->post('carrier_step');
    		$data = Yii::$app->request->post();
    	}else{
    		$order_id = Yii::$app->request->get('order_id');
    		$customer_number = Yii::$app->request->get('customer_number');
    		$default_shipping_method_code = Yii::$app->request->get('default_shipping_method_code');
    		$default_carrier_code = Yii::$app->request->get('default_carrier_code');
    		$carrier_step = Yii::$app->request->get('carrier_step');
    		$data = Yii::$app->request->get();
    	}
        //根据查询条件查询订单数据 od_order表 order_status = 300表示待生成包裹
    	$query = OdOrder::find()->where('order_status = 300 and is_manual_order = 0 ');
    	if (isset($order_id) && strlen($order_id)){
    		$query->andWhere(['order_id'=>$order_id]);
    	}
    	if (isset($customer_number) && strlen($customer_number)){
    		$query->andWhere(['customer_number'=>$customer_number]);
    	}
    	if (isset($default_shipping_method_code) && strlen($default_shipping_method_code)){
    		$query->andWhere(['default_shipping_method_code'=>$default_shipping_method_code]);
    	}
    	if (isset($default_carrier_code) && strlen($default_carrier_code)){
    		$query->andWhere(['default_carrier_code'=>$default_carrier_code]);
    	}
    	
    	if (isset($carrier_step) && strlen($carrier_step)){
    		$query->andWhere(['carrier_step'=>$carrier_step]);
    	}
        //计算分页数据
    	$pagination = new Pagination([
    			'defaultPageSize' => 20,
    			'pageSize' => $pageSize,
    			'totalCount' => $query->count(),
    			'pageSizeLimit'=>[5,200],//每页显示条数范围
    			'params'=>$data,
    			]);
    	$result['pagination'] = $pagination;
    	$query->orderBy('order_id desc');
    	$query->limit($pagination->limit);
    	$query->offset( $pagination->offset );
        //执行查询
    	$result['data'] = $query->all();

        //取物流运输服务数据 user库sys_shipping_service
    	$services = CarrierApiHelper::getShippingServices();
        //取自定义物流商和标准物流商合集 managedb库sys_carrier
    	$carriers = CarrierApiHelper::getCarriers();
    	//$selleruserids=Helper_Array::toHashmap(SaasEbayUser::find()->where(['uid'=>Yii::$app->user->identity->getParentUid()])->orderBy('selleruserid')->select(['selleruserid'])->asArray()->all(),'selleruserid','selleruserid');
        return $this->render('index',['orders'=>$result,'services'=>$services,'carriers'=>$carriers,'search_data'=>$data,'tag_class_list'=> OrderTagHelper::getTagColorMapping()]);
    }


    // =====================================物流状态数据列表=============================================
    /**
     * 待上传至物流商
     * million
     */
    public function actionWaitingupload(){
    	return "<a href='/carrier/carrierprocess/waitingpost'>请使用新的流程入口</a>";
    	AppTrackerApiHelper::actionLog("eagle_v2","/carrier/default/waitingupload");
    	$pageSize = isset($_GET['per-page'])?$_GET['per-page']:15;
        //接收参数
    	if (Yii::$app->request->isPost){
    		$order_id = Yii::$app->request->post('order_id');
    		$default_shipping_method_code = Yii::$app->request->post('default_shipping_method_code');
    		$default_carrier_code = Yii::$app->request->post('default_carrier_code');
    		$data = Yii::$app->request->post();
    	}else{
    		$order_id = Yii::$app->request->get('order_id');
    		$default_shipping_method_code = Yii::$app->request->get('default_shipping_method_code');
    		$default_carrier_code = Yii::$app->request->get('default_carrier_code');
    		$data = Yii::$app->request->get();
    	}

        //查非手工订单，状态为等待上传或交运取消的订单
    	$query = OdOrder::find()->where('order_status = 300 and is_manual_order = 0  and (carrier_step = '.OdOrder::CARRIER_WAITING_UPLOAD.' or carrier_step = '.OdOrder::CARRIER_CANCELED.')');
    	if (isset($order_id) && strlen($order_id)){
    		$query->andWhere(['order_id'=>$order_id]);
    	}
    	if (isset($default_shipping_method_code) && strlen($default_shipping_method_code)){
    		$query->andWhere(['default_shipping_method_code'=>$default_shipping_method_code]);
    	}
    	if (isset($default_carrier_code) && strlen($default_carrier_code)){
    		$query->andWhere(['default_carrier_code'=>$default_carrier_code]);
    	}

        //分页参数处理
    	$pagination = new Pagination([
    			'defaultPageSize' => 15,
    			'pageSize' => $pageSize,
    			'totalCount' => $query->count(),
    			'pageSizeLimit'=>[15,200],//每页显示条数范围
    			'params'=>$data,
    			]);
    	$result['pagination'] = $pagination;
    	$query->orderBy('order_id desc');
    	$query->limit($pagination->limit);
    	$query->offset( $pagination->offset );
        //执行查询
    	$result['data'] = $query->all();
    	$services = CarrierApiHelper::getShippingServices();
    	$carriers = CarrierApiHelper::getCarriers();
    	return $this->render('waitingupload',['orders'=>$result,'services'=>$services,'carriers'=>$carriers,'search_data'=>$data,'tag_class_list'=> OrderTagHelper::getTagColorMapping()]);
    }


    /**
     * 待交运
     * million
     */
    public function actionWaitingdispatch(){
    	return "<a href='/carrier/carrierprocess/waitingpost'>请使用新的流程入口</a>";
    	AppTrackerApiHelper::actionLog("eagle_v2","/carrier/default/waitingdispatch");
    	$pageSize = isset($_GET['per-page'])?$_GET['per-page']:20;
    	if (Yii::$app->request->isPost){
    		$order_id = Yii::$app->request->post('order_id');
    		$customer_number = Yii::$app->request->post('customer_number');
    		$default_shipping_method_code = Yii::$app->request->post('default_shipping_method_code');
    		$default_carrier_code = Yii::$app->request->post('default_carrier_code');
    		$data = Yii::$app->request->post();
    	}else{
    		$order_id = Yii::$app->request->get('order_id');
    		$customer_number = Yii::$app->request->get('customer_number');
    		$default_shipping_method_code = Yii::$app->request->get('default_shipping_method_code');
    		$default_carrier_code = Yii::$app->request->get('default_carrier_code');
    		$data = Yii::$app->request->get();
    	}
    	$query = OdOrder::find()->where('order_status = 300 and is_manual_order = 0  and carrier_step = '.OdOrder::CARRIER_WAITING_DELIVERY);
    	if (isset($order_id) && strlen($order_id)){
    		$query->andWhere(['order_id'=>$order_id]);
    	}
    	if (isset($customer_number) && strlen($customer_number)){
    		$query->andWhere(['customer_number'=>$customer_number]);
    	}
    	if (isset($default_shipping_method_code) && strlen($default_shipping_method_code)){
    		$query->andWhere(['default_shipping_method_code'=>$default_shipping_method_code]);
    	}
    	if (isset($default_carrier_code) && strlen($default_carrier_code)){
    		$query->andWhere(['default_carrier_code'=>$default_carrier_code]);
    	}
    	$pagination = new Pagination([
    			'defaultPageSize' => 20,
    			'pageSize' => $pageSize,
    			'totalCount' => $query->count(),
    			'pageSizeLimit'=>[5,200],//每页显示条数范围
    			'params'=>$data,
    			]);
    	$result['pagination'] = $pagination;
    	$query->orderBy('order_id desc');
    	$query->limit($pagination->limit);
    	$query->offset( $pagination->offset );
    	$result['data'] = $query->all();
    	$services = CarrierApiHelper::getShippingServices();
    	$carriers = CarrierApiHelper::getCarriers();
    	//$selleruserids=Helper_Array::toHashmap(SaasEbayUser::find()->where(['uid'=>Yii::$app->user->identity->getParentUid()])->orderBy('selleruserid')->select(['selleruserid'])->asArray()->all(),'selleruserid','selleruserid');
    	return $this->render('waitingdispatch',['orders'=>$result,'services'=>$services,'carriers'=>$carriers,'search_data'=>$data,'tag_class_list'=> OrderTagHelper::getTagColorMapping()]);
    }


    /**
     * 待获取物流号
     * million
     */
    public function actionWaitinggettrackingno(){
    	return "<a href='/carrier/carrierprocess/waitingpost'>请使用新的流程入口</a>";
    	AppTrackerApiHelper::actionLog("eagle_v2","/carrier/default/waitinggettrackingno");
    	$pageSize = isset($_GET['per-page'])?$_GET['per-page']:20;
    	if (Yii::$app->request->isPost){
    		$order_id = Yii::$app->request->post('order_id');
    		$customer_number = Yii::$app->request->post('customer_number');
    		$default_shipping_method_code = Yii::$app->request->post('default_shipping_method_code');
    		$default_carrier_code = Yii::$app->request->post('default_carrier_code');
    		$data = Yii::$app->request->post();
    	}else{
    		$order_id = Yii::$app->request->get('order_id');
    		$customer_number = Yii::$app->request->get('customer_number');
    		$default_shipping_method_code = Yii::$app->request->get('default_shipping_method_code');
    		$default_carrier_code = Yii::$app->request->get('default_carrier_code');
    		$data = Yii::$app->request->get();
    	}
    	$query = OdOrder::find()->where('order_status = 300 and is_manual_order = 0  and carrier_step = '.OdOrder::CARRIER_WAITING_GETCODE);
    	if (isset($order_id) && strlen($order_id)){
    		$query->andWhere(['order_id'=>$order_id]);
    	}
    	if (isset($customer_number) && strlen($customer_number)){
    		$query->andWhere(['customer_number'=>$customer_number]);
    	}
    	if (isset($default_shipping_method_code) && strlen($default_shipping_method_code)){
    		$query->andWhere(['default_shipping_method_code'=>$default_shipping_method_code]);
    	}
    	if (isset($default_carrier_code) && strlen($default_carrier_code)){
    		$query->andWhere(['default_carrier_code'=>$default_carrier_code]);
    	}
    	 
    	$pagination = new Pagination([
    			'defaultPageSize' => 20,
    			'pageSize' => $pageSize,
    			'totalCount' => $query->count(),
    			'pageSizeLimit'=>[5,200],//每页显示条数范围
    			'params'=>$data,
    			]);
    	$result['pagination'] = $pagination;
    	$query->orderBy('order_id desc');
    	$query->limit($pagination->limit);
    	$query->offset( $pagination->offset );
    	$result['data'] = $query->all();
    	$services = CarrierApiHelper::getShippingServices();
    	$carriers = CarrierApiHelper::getCarriers();
    	//$selleruserids=Helper_Array::toHashmap(SaasEbayUser::find()->where(['uid'=>Yii::$app->user->identity->getParentUid()])->orderBy('selleruserid')->select(['selleruserid'])->asArray()->all(),'selleruserid','selleruserid');
    	return $this->render('waitinggettrackingno',['orders'=>$result,'services'=>$services,'carriers'=>$carriers,'search_data'=>$data,'tag_class_list'=> OrderTagHelper::getTagColorMapping()]);
    }


    /**
     * 待打印
     * million
     */
    public function actionWaitingprint(){
    	return "<a href='/carrier/carrierprocess/waitingpost'>请使用新的流程入口</a>";
    	AppTrackerApiHelper::actionLog("eagle_v2","/carrier/default/waitingprint");
    	$pageSize = isset($_GET['per-page'])?$_GET['per-page']:20;
    	if (Yii::$app->request->isPost){
    		$order_id = Yii::$app->request->post('order_id');
    		$customer_number = Yii::$app->request->post('customer_number');
    		$default_shipping_method_code = Yii::$app->request->post('default_shipping_method_code');
    		$default_carrier_code = Yii::$app->request->post('default_carrier_code');
    		$tracking_number = Yii::$app->request->post('tracking_number');
    		$data = Yii::$app->request->post();
    	}else{
    		$order_id = Yii::$app->request->get('order_id');
    		$customer_number = Yii::$app->request->get('customer_number');
    		$default_shipping_method_code = Yii::$app->request->get('default_shipping_method_code');
    		$default_carrier_code = Yii::$app->request->get('default_carrier_code');
    		$tracking_number = Yii::$app->request->get('tracking_number');
    		$data = Yii::$app->request->get();
    	}
    	$query = OdOrder::find()->where('order_status = 300 and is_manual_order = 0   and carrier_step = '.OdOrder::CARRIER_WAITING_PRINT);
    	if (isset($order_id) && strlen($order_id)){
    		$query->andWhere(['order_id'=>$order_id]);
    	}
    	if (isset($customer_number) && strlen($customer_number)){
    		$query->andWhere(['customer_number'=>$customer_number]);
    	}
    	if (isset($default_shipping_method_code) && strlen($default_shipping_method_code)){
    		$query->andWhere(['default_shipping_method_code'=>$default_shipping_method_code]);
    	}
    	if (isset($default_carrier_code) && strlen($default_carrier_code)){
    		$query->andWhere(['default_carrier_code'=>$default_carrier_code]);
    	}
    	 
    	if (isset($tracking_number) && strlen($tracking_number)){
    		$one = OdOrderShipped::findOne('tracking_number = :tracking_number',[':tracking_number'=>$tracking_number]);
    		if (!$one===null){
    			$query->andWhere(['order_id'=>$one->order_id]);
    		}else{
    			$query->andWhere(['order_id'=>0]);
    		}
    	}
    	$pagination = new Pagination([
    			'defaultPageSize' => 20,
    			'pageSize' => $pageSize,
    			'totalCount' => $query->count(),
    			'pageSizeLimit'=>[5,200],//每页显示条数范围
    			'params'=>$data,
    			]);
    	$result['pagination'] = $pagination;
    	$query->orderBy('order_id desc');
    	$query->limit($pagination->limit);
    	$query->offset( $pagination->offset );
    	$result['data'] = $query->all();
    	$services = CarrierApiHelper::getShippingServices();
    	$carriers = CarrierApiHelper::getCarriers();
    	//$selleruserids=Helper_Array::toHashmap(SaasEbayUser::find()->where(['uid'=>Yii::$app->user->identity->getParentUid()])->orderBy('selleruserid')->select(['selleruserid'])->asArray()->all(),'selleruserid','selleruserid');
    	return $this->render('waitingprint',['orders'=>$result,'services'=>$services,'carriers'=>$carriers,'search_data'=>$data,'tag_class_list'=> OrderTagHelper::getTagColorMapping()]);
    }


    /**
     * 物流已完成
     * million
     */
    public function actionCarriercomplete(){
    	return "<a href='/carrier/carrierprocess/waitingpost'>请使用新的流程入口</a>";
    	
    	AppTrackerApiHelper::actionLog("eagle_v2","/carrier/default/carriercomplete");
    	$pageSize = isset($_GET['per-page'])?$_GET['per-page']:20;
    	if (Yii::$app->request->isPost){
    		$order_id = Yii::$app->request->post('order_id');
    		$customer_number = Yii::$app->request->post('customer_number');
    		$default_shipping_method_code = Yii::$app->request->post('default_shipping_method_code');
    		$default_carrier_code = Yii::$app->request->post('default_carrier_code');
    		$tracking_number = Yii::$app->request->post('tracking_number');
    		$data = Yii::$app->request->post();
    	}else{
    		$order_id = Yii::$app->request->get('order_id');
    		$customer_number = Yii::$app->request->get('customer_number');
    		$default_shipping_method_code = Yii::$app->request->get('default_shipping_method_code');
    		$default_carrier_code = Yii::$app->request->get('default_carrier_code');
    		$tracking_number = Yii::$app->request->get('tracking_number');
    		$data = Yii::$app->request->get();
    	}
    	$query = OdOrder::find()->where('order_status = 300 and is_manual_order = 0   and carrier_step = '.OdOrder::CARRIER_FINISHED);
    	if (isset($order_id) && strlen($order_id)){
    		$query->andWhere(['order_id'=>$order_id]);
    	}
    	if (isset($customer_number) && strlen($customer_number)){
    		$query->andWhere(['customer_number'=>$customer_number]);
    	}
    	if (isset($default_shipping_method_code) && strlen($default_shipping_method_code)){
    		$query->andWhere(['default_shipping_method_code'=>$default_shipping_method_code]);
    	}
    	if (isset($default_carrier_code) && strlen($default_carrier_code)){
    		$query->andWhere(['default_carrier_code'=>$default_carrier_code]);
    	}
    	 
    	if (isset($tracking_number) && strlen($tracking_number)){
    		$one = OdOrderShipped::findOne('tracking_number = :tracking_number',[':tracking_number'=>$tracking_number]);
    		if (!$one===null){
    			$query->andWhere(['order_id'=>$one->order_id]);
    		}else{
    			$query->andWhere(['order_id'=>0]);
    		}
    	}
    	$pagination = new Pagination([
    			'defaultPageSize' => 20,
    			'pageSize' => $pageSize,
    			'totalCount' => $query->count(),
    			'pageSizeLimit'=>[5,200],//每页显示条数范围
    			'params'=>$data,
    			]);
    	$result['pagination'] = $pagination;
    	$query->orderBy('order_id desc');
    	$query->limit($pagination->limit);
    	$query->offset( $pagination->offset );
    	$result['data'] = $query->all();
    	$services = CarrierApiHelper::getShippingServices();
    	$carriers = CarrierApiHelper::getCarriers();
    	//$selleruserids=Helper_Array::toHashmap(SaasEbayUser::find()->where(['uid'=>Yii::$app->user->identity->getParentUid()])->orderBy('selleruserid')->select(['selleruserid'])->asArray()->all(),'selleruserid','selleruserid');
    	return $this->render('carriercomplete',['orders'=>$result,'services'=>$services,'carriers'=>$carriers,'search_data'=>$data,'tag_class_list'=> OrderTagHelper::getTagColorMapping()]);
    }

    // =====================================物流状态列表结束=============================================


    // =====================================物流操作动作=============================================
    /**
     * 变更订单物流状态（可批量）
     * @return string
     */
    public function actionMovestep(){
    	AppTrackerApiHelper::actionLog("eagle_v2","/carrier/default/movestep");
        try {
        	
            $orderids = Yii::$app->request->post('orderids');
            $status = Yii::$app->request->post('status');
            $orderids_arr = explode(',', $orderids);
            Helper_Array::removeEmpty($orderids_arr);
            if (count($orderids_arr)){
                foreach ($orderids_arr as $orderid){
                    //查询订单数据
                    $order = OdOrder::findOne($orderid);
                    //记录原物流状态
                    $old = $order->carrier_step;
                    //设置新物流状态
                    $order->carrier_step = $status;
                    //执行保存
                    if (!$order->save()){
                        return json_encode(array('Ack' => 1, 'msg' => $orderid.'：移动物流操作状态失败！'));
                    }else {
                        //记录日志
                        OperationLogHelper::log('order', $orderid,'移动订单','手动批量移动订单物流操作状态,状态:'.CarrierHelper::$carrier_step[$old].'->'.CarrierHelper::$carrier_step[$status],\Yii::$app->user->identity->getFullName());
                    }
                }
            }else{
                return json_encode(array('Ack' => 1, 'msg' => '未选择订单！'));
            }
        }catch (Exception $ex){
            return json_encode(array('Ack' => 1,'msg'=>print_r($ex->getMessage(),1)));
        }
        return json_encode(array('Ack' => 0,'msg'=>'操作成功！'));
    }


    /**
     * 确认已打印
     * complete
     */
    public function actionCompleteprint(){
    	
    	AppTrackerApiHelper::actionLog("eagle_v2","/carrier/default/completeprint");
    	try {
    		$obj = OdOrder::findOne(['order_id'=>\Yii::$app->request->get('order_id')]);
    		$obj->carrier_step = OdOrder::CARRIER_FINISHED;
    		if (!$obj->save()){
    			exit(json_encode(array("code"=>"fail","message"=>print_r($obj->getErrors(),1))));
    		}
    	}catch (\Exception $ex){
    		exit(json_encode(array("code"=>"fail","message"=>$ex->getMessage())));
    	}
    	exit(json_encode(array("code"=>"ok","message"=>TranslateHelper::t('操作成功！'))));
    }


    public function actionCompletecarrier(){    	
    	AppTrackerApiHelper::actionLog("eagle_v2","/carrier/default/completecarrier");
    	//20171125 屏蔽代码 hqw
    	return json_encode(array('code' => 'fail', 'message' => '系统异常,请联系小老板客服！'));
    	
    	try {
    		if (\Yii::$app->request->isPost){
    			$orderids = Yii::$app->request->post('orderids');
    		}else{
    			$orderids = Yii::$app->request->get('order_id');
    		}
    		$orderids_arr = explode(',', $orderids);
    		Helper_Array::removeEmpty($orderids_arr);
    		if (count($orderids_arr)){
    			$str = '';
    			foreach ($orderids_arr as $orderid){
    				$order = OdOrder::findOne(['order_id'=>$orderid]);
                    //最后再获取一次新的跟踪号，有些物流商跟踪号与之前的可能不同
                    if(substr($order->default_carrier_code, 0,3) == 'lb_'){
	                    $carrier_interface = CarrierHelper::getrequestapi($order->default_carrier_code);
	                    if(!is_object($carrier_interface))return json_encode(array('code' => 'fail', 'message' => $carrier_interface));
	                    //获取跟踪号(忽略错误)
	                    $carrier_interface->getTrackingNO(['order'=>$order]);
                    }
                    
    				$products = array();
	    			//处理订单的预约sku信息
					if (count($order->items)){
						foreach ($order->items as $item){
							if (!is_null($item->sku)&&strlen($item->sku)>0){
								$skus = ProductApiHelper::getSkuInfo($item->sku, $item->quantity);
								foreach ($skus as $one){
									$products[] = array('sku'=>$one['sku'] ,'qty'=>$one['qty'],'order_id'=>$order->order_id);
								}
							}
						}
					}
    				//减库存
    				//if (AppApiHelper::checkAppIsActive('warehouse')){
	    				$rtn = InventoryApiHelper::OrderProductStockOut($order->order_id);
	    				if ($rtn['success']!==true){
	    					if ($rtn["code"]=='E_OPSO_001'){
	    						//如果么有预约库存立即重新预约
	    						$rtn2 = InventoryApiHelper::OrderProductReserve($order->order_id, $order->default_warehouse_id, $products);
	    						if ($rtn2['success']!==true){//重新预约失败
	    							return json_encode(array('code' => 'fail', 'message' => $order->order_id.$rtn2['message']));
	    						}else{//重新预约成功
	    							if($rtn2['message'] !== '没有商品需要预约'){
		    							//重新减库存
		    							$rtn3 = InventoryApiHelper::OrderProductStockOut($order->order_id);
		    							if ($rtn3['success']!==true){//减库存失败
		    								return json_encode(array('code' => 'fail', 'message' => $order->order_id.$rtn3['message']));
		    							}
	    							}
	    						}
	    					}else{//有预约但是减库存失败
	    						return json_encode(array('code' => 'fail', 'message' => $order->order_id.$rtn['message']));
	    					}
	    				}
    				//}
    				
    				$old = $order->order_status;
    				if ($old == 300){
    					if ($order->order_source=='aliexpress'){
    						$order->order_status = OdOrder::STATUS_SHIPPING;
    					}else{
    						$order->order_status = OdOrder::STATUS_SHIPPED;
    					}
    				}else{
    					continue;
    				}
    				//weird_status处理 liang 2015-12-26 
    				$addtionLog = '';
    				if(!empty($order->weird_status))
    					$addtionLog = ',确认发货完成，自动清除操作超时标签';
    				$order->weird_status='';
    				//weird_status处理 end
    				if (!$order->save()){
    					return json_encode(array('code' => 'fail', 'message' => $orderid.'：确认发货失败！'));
    				}else {
    					
    					$default_value = [
								'order_source'=>$order->order_source,
								'selleruserid'=>$order->selleruserid,
								'tracking_number'=>'',
								'tracking_link'=>'',
								'shipping_method_code'=>'',
								'shipping_method_name'=>'',
								'order_source_order_id'=>$order->order_source_order_id,
								'description'=>'',
								'signtype'=>'',
								'addtype'=>'自动标记发货',
								];
    					//shipping_status>0 || $order->delivery_time>0 ||$count>0 表示可能已经标记过
    					$count = OdOrderShipped::find()->where(['order_id'=>$order->order_id,'status'=>1])->count();
    					if ($order->shipping_status>0 || $order->delivery_time>0 || $count>0){
    						$odship = OdOrderShipped::find()->where(['order_id'=>$order->order_id,'status'=>0])->andWhere('length(tracking_number)>0')->orderBy('id DESC')->one();
    						if ($odship!==null){
    							$tmp_arr = array();
    							foreach ($default_value as $k=>$v){
    								$tmp_arr[$k]=$odship[$k];
    							}
    							$logisticInfoList=['0'=>$tmp_arr];
    						}else{
    							//假如没有新的物流， 则需要unset $logisticInfoList 否则 saveTrackingNumber 会生成 最后一次执行成功的order_shipped 数据
    							unset($logisticInfoList);
    						}
	    				
    					}else{//没有标记过
    						$odship = OdOrderShipped::find()->where(['order_id'=>$order->order_id,'status'=>0])->orderBy('tracking_number DESC,id DESC')->one();
    						if ($odship==null){
    							$logisticInfoList=['0'=>$default_value];
    						}else{
    							$tmp_arr = array();
    							foreach ($default_value as $k=>$v){
    								$tmp_arr[$k]=$odship[$k];
    							}
    							$logisticInfoList=['0'=>$tmp_arr];
    						}
    					}
    					if (isset($logisticInfoList)){
    						if( in_array( $order->order_source, array( 'ebay','amazon','aliexpress','wish','dhgate','cdiscount','ensogo','priceminister' ) ) ){
    							$is_shipped = true;
    						}else {
    							$is_shipped = false;
    						}	
    						OrderHelper::saveTrackingNumber($orderid,$logisticInfoList,0,$is_shipped);
    					}
    					OperationLogHelper::log('order', $orderid,'物流模块确认发货','确认发货完成,状态:'.OdOrder::$status[$old].'->'.OdOrder::$status['500'].$addtionLog, \Yii::$app->user->identity->getFullName());
    					
    					
    				}
    			}
    		}else{
    			return json_encode(array('code' => 'fail', 'message' => '未选择订单！'));
    		}
    	}catch (Exception $ex){
    		return json_encode(array('code' => 'fail','message'=>print_r($ex->getMessage(),1)));
    	}
    	$order_str = strlen($str)>5?$str.'状态为“待发货”,请到发货模块操作发货':'';
    	return json_encode(array('code' => 'ok','message'=>'确认发货成功！'.$order_str));
    }


    /**
     * 物流上传取消（只在交运这一步珂操作）
     *
     */
    public function actionCarriercancel(){
    	AppTrackerApiHelper::actionLog("eagle_v2","/carrier/default/carriercancel");
    	try {
    		if (\Yii::$app->request->isPost){
    			$orderids = Yii::$app->request->post('orderids');
    		}else{
    			$orderids = Yii::$app->request->get('order_id');
    		}
    		$orderids_arr = explode(',', $orderids);
    		Helper_Array::removeEmpty($orderids_arr);
    		if (count($orderids_arr)){
    			foreach ($orderids_arr as $orderid){
    				$order = OdOrder::findOne($orderid);
    				$old = $order->order_status;
    				if ($old == 300){
    					$oldStep = $order->carrier_step;
    					$oldStatus = $order->order_status;
    					$order->order_status = OdOrder::STATUS_PAY;
    					$order->carrier_step = OdOrder::CARRIER_WAITING_UPLOAD;
    				}else{
    					continue;
    				}
    				if (!$order->save()){
    					return json_encode(array('code' => 'fail', 'message' => $orderid.'：取消物流失败！'));
    				}else {
    					OperationLogHelper::log('order', $orderid,'物流模块取消物流操作','取消物流完成,状态:'.CarrierHelper::$carrier_step[$oldStep].'->'.CarrierHelper::$carrier_step[OdOrder::CARRIER_WAITING_UPLOAD],\Yii::$app->user->identity->getFullName());
    					OperationLogHelper::log('order', $orderid,'物流模块取消物流操作','取消物流完成,状态:'.OdOrder::$status[$oldStatus].'->'.OdOrder::$status[OdOrder::STATUS_PAY],\Yii::$app->user->identity->getFullName());
    				}
    			}
    		}else{
    			return json_encode(array('code' => 'fail', 'message' => '未选择订单！'));
    		}
    	}catch (Exception $ex){
    		return json_encode(array('code' => 'fail','message'=>print_r($ex->getMessage(),1)));
    	}
    	return json_encode(array('code' => 'ok','message'=>'取消物流成功！'));
    }


    //匹配发货仓库和运输服务
    public function actionMatchshipping(){
    	if (\Yii::$app->request->isPost){
    		AppTrackerApiHelper::actionLog("eagle_v2","/carrier/default/matchshipping");
    		
    		//是否批量匹配订单
    		$is_batch = empty($_POST['is_batch']) ? 0 : $_POST['is_batch'];
    		
    		//返回的格式
    		$return_type = empty($_POST['return_type']) ? 0 : $_POST['return_type'];
    		
    		//批量记录订单信息数组
    		$batch_order_info = array();
    		
    		$orderids = $_POST['orderids'];
    		//重新匹配
    		$reset = isset($_POST['reset'])?$_POST['reset']:0;
    		Helper_Array::removeEmpty($orderids);
    		$name=\Yii::$app->user->identity->getFullName();
    		if (count($orderids)>0){
    			try {
    				foreach ($orderids as $orderid){
    					$order = OdOrder::findOne($orderid);
    					//匹配物流方式
    					if ($order->default_shipping_method_code=='' || $reset==1){
							if (CarrierApiHelper::matchShippingService($order, $reset)){
								$newAttr = array(
										'default_carrier_code' => $order->default_carrier_code,
										'default_shipping_method_code' => $order->default_shipping_method_code,
										'default_warehouse_id' => $order->default_warehouse_id,
										'rule_id' => $order->rule_id
								);
								
								//调用OMS的订单保存接口
								$fullName = \Yii::$app->user->identity->getFullName();
								$updateOrderResult = \eagle\modules\order\helpers\OrderUpdateHelper::updateOrder($order->order_id, $newAttr, false , $fullName, '运输服务匹配', 'carrier');
								
// 								$order->save(false);
								
								$serviceName = SysShippingService::findOne($order->default_shipping_method_code)->service_name;
								OperationLogHelper::log('order',$order->order_id,'匹配发货仓库和运输服务','匹配到运输服务:['.$order->default_shipping_method_code.'-'.$serviceName.']',$name);
								$warehouse = Warehouse::findOne($order->default_warehouse_id)->name;
								
								if($updateOrderResult['ack'] == false){
									$tmpReturn = array('success'=>false,'code'=>"",'message'=>$updateOrderResult['message'],'data'=>array('orderid'=>$order->order_id));
								}else{
									$tmpReturn = array('success'=>true,'code'=>"",'message'=>'已匹配到'.$warehouse.' 和 '.$serviceName,'data'=>array('orderid'=>$order->order_id));
								}
								
								if($is_batch == 0){
									return json_encode($tmpReturn);
								}else{
									$batch_order_info[$order->order_id] = $tmpReturn;
								}
							}else{
								$order->save(false);
								if ($order->default_shipping_method_code==''){
									OperationLogHelper::log('order',$order->order_id,'匹配运输服务','未匹配到发货运输服务',$name);
									
									$tmpReturn = array('success'=>false,'code'=>'100002','message'=>'<a href="'. Url::to(['/order/logshow/list','orderid'=>$order->order_id]).'" target="_blank" class="alert-link">订单'.$order->order_id.'未匹配到发货运输服务,请查看订单单日志！<a>','data'=>array('orderid'=>$order->order_id,'100002'=>''));
									
									if($is_batch == 0){
										return json_encode($tmpReturn);
									}else{
										$batch_order_info[$order->order_id] = $tmpReturn;
									}
								}else{
									OperationLogHelper::log('order',$order->order_id,'匹配运输服务','未重新匹配到发货运输服务',$name);
									
									$tmpReturn = array('success'=>false,'code'=>'100002','message'=>'<a href="'. Url::to(['/order/logshow/list','orderid'=>$order->order_id]).'" target="_blank" class="alert-link">订单'.$order->order_id.'未重新匹配到发货运输服务,请查看订单单日志！<a>','data'=>array('orderid'=>$order->order_id,'100002'=>''));
									
									if($is_batch == 0){
										return json_encode($tmpReturn);
									}else{
										$batch_order_info[$order->order_id] = $tmpReturn;
									}
								}
							}
    					}else{
    						$tmpReturn = array('success'=>false,'code'=>"",'message'=>'订单已指定发货运输服务，如需匹配请操作“重新匹配发货仓库和运输服务”','data'=>array('orderid'=>$order->order_id));
    						
    						if($is_batch == 0){
    							return json_encode($tmpReturn);
    						}else{
    							$batch_order_info[$order->order_id] = $tmpReturn;
    						}
    					}
    				}
    			}catch (\Exception $e){
    				$tmpReturn = array('success'=>false,'code'=>"",'message'=>'系统错误或网络不稳定,请联系我们!'.$e->getMessage().'line'.$e->getLine(),'data'=>array('orderid'=>$order->order_id));
    				
    				if(empty($return_type)){
    					return json_encode($tmpReturn);
    				}else{
    					return $tmpReturn;
    				}
    			}
    		}else{
    			$tmpReturn = array('success'=>false,'code'=>"",'message'=>'没有需要匹配运输服务的订单','data'=>array('orderid'=>$order->order_id));
    			
    			if(empty($return_type)){
    				return json_encode($tmpReturn);
    			}else{
    				return $tmpReturn;
    			}
    		}
    		
    		if(empty($return_type)){
    			return json_encode(array('success'=>true,'orders'=>$batch_order_info));
    		}else{
    			return array('success'=>true,'orders'=>$batch_order_info);
    		}
    		
    	}
    }
    
    //批量修改报关信息保存
    public function actionEditCustomsInfo(){
    	if (\Yii::$app->request->isPost){
    		AppTrackerApiHelper::actionLog("eagle_v2","/carrier/default/edit-customs-info");

    		$infos = array(
    				'name' => '',
    				'prod_name_en' => '',
    		);
    		
    		if(!empty($_POST['customsName'])){
    			$infos['declaration_ch'] = $_POST['customsName'];
    		}
    		
    		if(!empty($_POST['customsEName'])){
    			$infos['declaration_en'] = $_POST['customsEName'];
    		}
    		
    		if(!empty($_POST['customsDeclaredValue'])){
    			$infos['declaration_value'] = $_POST['customsDeclaredValue'];
    		}
    		
    		if(!empty($_POST['customsweight'])){
    			$infos['prod_weight'] = $_POST['customsweight'];
    		}
    		
    		$orderids = explode(',',$_POST['orders']);
    		Helper_Array::removeEmpty($orderids);
    		if (count($orderids)>0){
    			try {
    				foreach ($orderids as $orderid){
    					$orderItems = OdOrderItem::find()->select('product_name,sku')->where('order_id=:order_id',[':order_id'=>$orderid])->asArray()->all();
    					
    					foreach ($orderItems as $orderItem){
    						$infos['name'] = $orderItem['product_name'];
    						$infos['prod_name_en'] = $orderItem['product_name'];
    						
    						$result = ProductApiHelper::modifyProductInfo($orderItem['sku'], $infos);
    					}
    				}
    				return '批量修改报关信息完成';
    			}catch (\Exception $e){
    				return $e->getMessage();
    			}
    		}else{
    			return '没有需要批量修改报关信息的订单';
    		}
    	}else{
    		return $this->renderAjax('editCustomsInfo');
    	}
    }

    // =====================================物流操作动作结束=============================================
    /**
     * 待上传
     * glp 2016/2/1
     */
    public function actionWaitingpost(){
    	AppTrackerApiHelper::actionLog("eagle_v2","/carrier/default/waitingpost");
    	$order_nav_html=CarrierHelper::getOrderNav();
    	$pageSize = isset($_GET['per-page'])?$_GET['per-page']:15;
    	//接收参数
    	if (Yii::$app->request->isPost){
//     		$order_id = Yii::$app->request->post('order_id');
    		$default_shipping_method_code = Yii::$app->request->post('default_shipping_method_code');
    		$default_carrier_code = Yii::$app->request->post('default_carrier_code');
    		$data = Yii::$app->request->post();
    	}else{
//     		$order_id = Yii::$app->request->get('order_id');
    		$default_shipping_method_code = Yii::$app->request->get('default_shipping_method_code');
    		$default_carrier_code = Yii::$app->request->get('default_carrier_code');
    		$data = Yii::$app->request->get();
    	}
    
    	//查非手工订单，状态为等待上传或交运取消的订单
    	$query = OdOrder::find()->where('order_status = 300 and is_manual_order = 0  and (carrier_step = '.OdOrder::CARRIER_WAITING_UPLOAD.' or carrier_step = '.OdOrder::CARRIER_CANCELED.')');
//     	if (isset($order_id) && strlen($order_id)){
//     		$query->andWhere(['order_id'=>$order_id]);
//     	}
    	if (isset($default_shipping_method_code) && strlen($default_shipping_method_code)){
    		$query->andWhere(['default_shipping_method_code'=>$default_shipping_method_code]);
    	}
    	if (isset($default_carrier_code) && strlen($default_carrier_code)){
    		$query->andWhere(['default_carrier_code'=>$default_carrier_code]);
    	}
    
    	//分页参数处理
    	$pagination = new Pagination([
    			'defaultPageSize' => 15,
    			'pageSize' => $pageSize,
    			'totalCount' => $query->count(),
    			'pageSizeLimit'=>[15,200],//每页显示条数范围
    			'params'=>$data,
    			]);
    	$result['pagination'] = $pagination;
    	$query->orderBy('order_id desc');
    	$query->limit($pagination->limit);
    	$query->offset( $pagination->offset );
    	//执行查询
    	$result['data'] = $query->all();
    	$services = CarrierApiHelper::getShippingServices();
    	$carriers = CarrierApiHelper::getCarriers();
    	 
    	return $this->render('waitingpost',['orders'=>$result,'services'=>$services,'carriers'=>$carriers,'search_data'=>$data,'tag_class_list'=> OrderTagHelper::getTagColorMapping(),'order_nav_html'=>$order_nav_html]);
    }
    
    /**
     * 待交运
     * glp 2016/2/1
     */
    public function actionWaitingdelivery(){
    	
    	AppTrackerApiHelper::actionLog("eagle_v2","/carrier/default/waitingdelivery");
    	$order_nav_html=CarrierHelper::getOrderNav();
    	$pageSize = isset($_GET['per-page'])?$_GET['per-page']:20;
    	if (Yii::$app->request->isPost){
//     		$order_id = Yii::$app->request->post('order_id');
//     		$customer_number = Yii::$app->request->post('customer_number');
    		$default_shipping_method_code = Yii::$app->request->post('default_shipping_method_code');
    		$default_carrier_code = Yii::$app->request->post('default_carrier_code');
    		$data = Yii::$app->request->post();
    	}else{
//     		$order_id = Yii::$app->request->get('order_id');
//     		$customer_number = Yii::$app->request->get('customer_number');
    		$default_shipping_method_code = Yii::$app->request->get('default_shipping_method_code');
    		$default_carrier_code = Yii::$app->request->get('default_carrier_code');
    		$data = Yii::$app->request->get();
    	}
    	$query = OdOrder::find()->where('order_status = 300 and is_manual_order = 0  and carrier_step = '.OdOrder::CARRIER_WAITING_DELIVERY);
//     	if (isset($order_id) && strlen($order_id)){
//     		$query->andWhere(['order_id'=>$order_id]);
//     	}
//     	if (isset($customer_number) && strlen($customer_number)){
//     		$query->andWhere(['customer_number'=>$customer_number]);
//     	}
    	if (isset($default_shipping_method_code) && strlen($default_shipping_method_code)){
    		$query->andWhere(['default_shipping_method_code'=>$default_shipping_method_code]);
    	}
    	if (isset($default_carrier_code) && strlen($default_carrier_code)){
    		$query->andWhere(['default_carrier_code'=>$default_carrier_code]);
    	}
    	$pagination = new Pagination([
    			'defaultPageSize' => 20,
    			'pageSize' => $pageSize,
    			'totalCount' => $query->count(),
    			'pageSizeLimit'=>[5,200],//每页显示条数范围
    			'params'=>$data,
    			]);
    	$result['pagination'] = $pagination;
    	$query->orderBy('order_id desc');
    	$query->limit($pagination->limit);
    	$query->offset( $pagination->offset );
    	$result['data'] = $query->all();
    	$services = CarrierApiHelper::getShippingServices();
    	$carriers = CarrierApiHelper::getCarriers();
    	$result['ems']=CarrierHelper::Createems($result['data']);
    	//$selleruserids=Helper_Array::toHashmap(SaasEbayUser::find()->where(['uid'=>Yii::$app->user->identity->getParentUid()])->orderBy('selleruserid')->select(['selleruserid'])->asArray()->all(),'selleruserid','selleruserid');
    	return $this->render('waitingdelivery',['orders'=>$result,'services'=>$services,'carriers'=>$carriers,'search_data'=>$data,'tag_class_list'=> OrderTagHelper::getTagColorMapping(),'order_nav_html'=>$order_nav_html]);
    }
    
    /**
     * 已交运
     * glp 2016/2/1
     */
    public function actionDeliveryed(){
    	AppTrackerApiHelper::actionLog("eagle_v2","/carrier/default/waitingdelivery");
    	$order_nav_html=CarrierHelper::getOrderNav();
    	$pageSize = isset($_GET['per-page'])?$_GET['per-page']:20;
    	if (Yii::$app->request->isPost){
//     		$order_id = Yii::$app->request->post('order_id');
//     		$customer_number = Yii::$app->request->post('customer_number');
    		$default_shipping_method_code = Yii::$app->request->post('default_shipping_method_code');
    		$default_carrier_code = Yii::$app->request->post('default_carrier_code');
    		$tracking_number = Yii::$app->request->post('tracking_number');
    		$data = Yii::$app->request->post();
    	}else{
//     		$order_id = Yii::$app->request->get('order_id');
//     		$customer_number = Yii::$app->request->get('customer_number');
//     		$default_shipping_method_code = Yii::$app->request->get('default_shipping_method_code');
    		$default_carrier_code = Yii::$app->request->get('default_carrier_code');
    		$tracking_number = Yii::$app->request->get('tracking_number');
    		$data = Yii::$app->request->get();
    	}
    	$query = OdOrder::find()->where('order_status = 300 and is_manual_order = 0')->andWhere('carrier_step in ('.OdOrder::CARRIER_WAITING_GETCODE.','.OdOrder::CARRIER_WAITING_PRINT.')');
//     	if (isset($order_id) && strlen($order_id)){
//     		$query->andWhere(['order_id'=>$order_id]);
//     	}
//     	if (isset($customer_number) && strlen($customer_number)){
//     		$query->andWhere(['customer_number'=>$customer_number]);
//     	}
    	if (isset($default_shipping_method_code) && strlen($default_shipping_method_code)){
    		$query->andWhere(['default_shipping_method_code'=>$default_shipping_method_code]);
    	}
    	if (isset($default_carrier_code) && strlen($default_carrier_code)){
    		$query->andWhere(['default_carrier_code'=>$default_carrier_code]);
    	}
    	if (isset($tracking_number) && strlen($tracking_number)){
    		$one = OdOrderShipped::findOne('tracking_number = :tracking_number',[':tracking_number'=>$tracking_number]);
    		if (!$one===null){
    			$query->andWhere(['order_id'=>$one->order_id]);
    		}else{
    			$query->andWhere(['order_id'=>0]);
    		}
    	}
    	$pagination = new Pagination([
    			'defaultPageSize' => 20,
    			'pageSize' => $pageSize,
    			'totalCount' => $query->count(),
    			'pageSizeLimit'=>[5,200],//每页显示条数范围
    			'params'=>$data,
    			]);
    	$result['pagination'] = $pagination;
    	$query->orderBy('order_id desc');
    	$query->limit($pagination->limit);
    	$query->offset( $pagination->offset );
    	$result['data'] = $query->all();
    	$services = CarrierApiHelper::getShippingServices();
    	$carriers = CarrierApiHelper::getCarriers();
    	$result['ems']=CarrierHelper::Createems($result['data']);
    	//$selleruserids=Helper_Array::toHashmap(SaasEbayUser::find()->where(['uid'=>Yii::$app->user->identity->getParentUid()])->orderBy('selleruserid')->select(['selleruserid'])->asArray()->all(),'selleruserid','selleruserid');
    	return $this->render('deliveryed',['orders'=>$result,'services'=>$services,'carriers'=>$carriers,'search_data'=>$data,'tag_class_list'=> OrderTagHelper::getTagColorMapping(),'order_nav_html'=>$order_nav_html]);
    }
    
    /**
     * 已完成
     * glp 2016/2/1
     */
    public function actionCompleted(){
    	$order_nav_html=CarrierHelper::getOrderNav();
    	AppTrackerApiHelper::actionLog("eagle_v2","/carrier/default/carriercomplete");
    	$pageSize = isset($_GET['per-page'])?$_GET['per-page']:20;
    	if (Yii::$app->request->isPost){
//     		$order_id = Yii::$app->request->post('order_id');
//     		$customer_number = Yii::$app->request->post('customer_number');
    		$default_shipping_method_code = Yii::$app->request->post('default_shipping_method_code');
    		$default_carrier_code = Yii::$app->request->post('default_carrier_code');
    		$tracking_number = Yii::$app->request->post('tracking_number');
    		$data = Yii::$app->request->post();
    	}else{
//     		$order_id = Yii::$app->request->get('order_id');
//     		$customer_number = Yii::$app->request->get('customer_number');
    		$default_shipping_method_code = Yii::$app->request->get('default_shipping_method_code');
    		$default_carrier_code = Yii::$app->request->get('default_carrier_code');
    		$tracking_number = Yii::$app->request->get('tracking_number');
    		$data = Yii::$app->request->get();
    	}
    	$query = OdOrder::find()->where('order_status = 300 and is_manual_order = 0   and carrier_step = '.OdOrder::CARRIER_FINISHED);
//     	if (isset($order_id) && strlen($order_id)){
//     		$query->andWhere(['order_id'=>$order_id]);
//     	}
//     	if (isset($customer_number) && strlen($customer_number)){
//     		$query->andWhere(['customer_number'=>$customer_number]);
//     	}
    	if (isset($default_shipping_method_code) && strlen($default_shipping_method_code)){
    		$query->andWhere(['default_shipping_method_code'=>$default_shipping_method_code]);
    	}
    	if (isset($default_carrier_code) && strlen($default_carrier_code)){
    		$query->andWhere(['default_carrier_code'=>$default_carrier_code]);
    	}
    	
    	if (isset($tracking_number) && strlen($tracking_number)){
    		$one = OdOrderShipped::findOne('tracking_number = :tracking_number',[':tracking_number'=>$tracking_number]);
    		if (!$one===null){
    			$query->andWhere(['order_id'=>$one->order_id]);
    		}else{
    			$query->andWhere(['order_id'=>0]);
    		}
    	}
    	$pagination = new Pagination([
    			'defaultPageSize' => 20,
    			'pageSize' => $pageSize,
    			'totalCount' => $query->count(),
    			'pageSizeLimit'=>[5,200],//每页显示条数范围
    			'params'=>$data,
    			]);
    	$result['pagination'] = $pagination;
    	$query->orderBy('order_id desc');
    	$query->limit($pagination->limit);
    	$query->offset( $pagination->offset );
    	$result['data'] = $query->all();
    	$services = CarrierApiHelper::getShippingServices();
    	$carriers = CarrierApiHelper::getCarriers();
    	$result['ems']=CarrierHelper::Createems($result['data']);
    	//$selleruserids=Helper_Array::toHashmap(SaasEbayUser::find()->where(['uid'=>Yii::$app->user->identity->getParentUid()])->orderBy('selleruserid')->select(['selleruserid'])->asArray()->all(),'selleruserid','selleruserid');
    	return $this->render('completed',['orders'=>$result,'services'=>$services,'carriers'=>$carriers,'search_data'=>$data,'tag_class_list'=> OrderTagHelper::getTagColorMapping(),'order_nav_html'=>$order_nav_html]);
    }
    
    /**
     * 暂停发货
     * glp 2016/2/1
     */
    public function actionSuspend(){
    	$order_id=[];
    	if (Yii::$app->request->isPost){
    		$order_id = Yii::$app->request->post('order_id');
    		$rtn=OrderApiHelper::suspendOrders($order_id ,'carrier');
    		if($rtn['success']){
    			return $order_id."订单已暂停发货";
    		}else{
    			return $rtn['message'];
    		}
    	}
	}
	/**
	 * 重新上传
	 * glp 2016/2/1
	 */
	public function actionReupload(){
		AppTrackerApiHelper::actionLog("eagle_v2","/carrier/default/movestep");
		try { 
			$orderids = Yii::$app->request->post('orderids');
			
// 			$orderids_arr = explode(',', $orderids);
// 			Helper_Array::removeEmpty($orderids_arr);
			
			if (count($orderids)){
				foreach ($orderids as $orderid){
					//查询订单数据
					$order = OdOrder::findOne($orderid);
					//记录原物流状态
					$old = $order->carrier_step;
					//设置新物流状态
					$order->carrier_step = OdOrder::CARRIER_WAITING_UPLOAD;
					//执行保存
					if (!$order->save()){
						return json_encode(array('Ack' => 1, 'msg' => $orderid.'：重新上传失败！'));
					}else {
						//记录日志
						OperationLogHelper::log('order', $orderid,'重新上传','重新上传订单物流操作,状态:'.CarrierHelper::$carrier_step[$old].'->'.CarrierHelper::$carrier_step[OdOrder::CARRIER_WAITING_UPLOAD],\Yii::$app->user->identity->getFullName());
					}
				}
			}else{
				return json_encode(array('Ack' => 1, 'msg' => '未选择订单！'));
			}
		}catch (Exception $ex){
			return json_encode(array('Ack' => 1,'msg'=>print_r($ex->getMessage(),1)));
		}
		return json_encode(array('Ack' => 0,'msg'=>'操作成功！'));
	}
}