<?php
namespace eagle\modules\delivery\controllers;
use \Yii;
use yii\web\Controller;
use eagle\modules\order\models\OdOrder;
use eagle\modules\catalog\helpers\ProductApiHelper;
use eagle\modules\inventory\helpers\InventoryApiHelper;
use common\helpers\Helper_Array;
use eagle\modules\carrier\apihelpers\ApiHelper;
use Faker\Provider\Barcode;

class PrintController extends \eagle\components\Controller{
	public $enableCsrfValidation = false;
	/**
	 * 打印拣货单1
	 * author million
	 * 88028624@qq.com
	 * 2015-03-19
	 * @return Ambigous <string, string>
	 */
	public function actionPicking(){
		$this->layout='/mainPrint';
		if (Yii::$app->request->isPost){
			$order_ids = Yii::$app->request->post('order_id');
			$warehouse_id = Yii::$app->request->post('warehouse_id');
		}else{
			$order_ids = [Yii::$app->request->get('order_id')];
			$warehouse_id = Yii::$app->request->get('warehouse_id');
		}
		$orders =OdOrder::find()->where(['in','order_id',$order_ids])->all();
		$products = [];
		//操作人
		$uid = Yii::$app->user->identity->getParentUid();
		//是否打印拣货单
		$time = time();
		foreach ($orders as $order){
			$order->is_print_picking=1;
			$order->print_picking_operator=$uid;
			$order->print_picking_time=$time;
			if ($order->delivery_status < 1){
				$order->delivery_status = OdOrder::DELIVERY_PICKING;//打印拣货单以后拣货中
			}
			$order->save(false);
			foreach ($order->items as $one){
				if (ProductApiHelper::hasProduct($one->sku)){
					$productInfo = ProductApiHelper::getProductInfo($one->sku);
					if(empty($productInfo)){
						@$products[$one->sku] = array('sku'=>$one->sku,'name'=>$one->title,'location'=>'','qty_in_stock'=>'');
						@$products[$one->sku]['quantity']+=$one->ordered_quantity;
					}else{
						if (isset($productInfo['children'])){
							foreach ($productInfo['children'] as $v){
								@$products[$v['sku']] = array('sku'=>$v['sku'],'name'=>$v['name'],'location'=>'','qty_in_stock'=>'');
								//需要乘以绑定的子商品数量
								@$products[$v['sku']]['quantity']+=$one->ordered_quantity;
								
							}
						}else{
							@$products[$productInfo['sku']] = array('sku'=>$productInfo['sku'],'name'=>$productInfo['name'],'location'=>'','qty_in_stock'=>'');
							@$products[$productInfo['sku']]['quantity']+=$one->ordered_quantity;
							
						}
					}
				}else{
					@$products[$one->sku] = array('sku'=>$one->sku,'name'=>$one->product_name,'location'=>'','qty_in_stock'=>'');
					@$products[$one->sku]['quantity']+=$one->ordered_quantity;
				}
			}
		}
		$skus = Helper_Array::getCols($products, 'sku');
		$pickingInfo = InventoryApiHelper::getPickingInfo($skus,$warehouse_id);
		if (!empty($pickingInfo)){
			foreach ($pickingInfo as $one){
				$products[$one['sku']]['location'] = $one['location_grid'];
				@$products[$one['sku']]['qty_in_stock'] = $one['qty_in_stock'];
			}
		}
		$operator = Yii::$app->user->identity->getUsername();
		$fullName = Yii::$app->user->identity->getFullName();
		$operator.=strlen($fullName)>0?'('.$fullName.')':'';
		return $this->render('picking',
				array(
						'products'=>$products,
						'time'=>$time,
						'operator'=>$operator,
				));
	}
	
	/**
	 * 打印拣货单2
	 * author million
	 * 88028624@qq.com
	 * 2015-03-19
	 * @return Ambigous <string, string>
	 */
	public function actionPicking2(){
		$this->layout='/mainPrint';
		if (Yii::$app->request->isPost){
			$order_ids = Yii::$app->request->post('order_id');
		}else{
			$order_ids = [Yii::$app->request->get('order_id')];
		}
		$orders =OdOrder::find()->where(['in','order_id',$order_ids])->all();
		//操作人
		$uid = Yii::$app->user->identity->getParentUid();
		//是否打印拣货单
		$time = time();
		foreach ($orders as $order){
			$order->is_print_picking=1;
			$order->print_picking_operator=$uid;
			$order->print_picking_time=$time;
			if ($order->delivery_status < 1){
				$order->delivery_status = OdOrder::DELIVERY_PICKING;//打印拣货单以后拣货中
			}
			$order->save(false);
		}
		//物流商
		$carriers = ApiHelper::getCarriers();
		//运输服务
		$shipping_services = ApiHelper::getShippingServices();
		$operator = Yii::$app->user->identity->getUsername();
		$fullName = Yii::$app->user->identity->getFullName();
		$operator.=strlen($fullName)>0?'('.$fullName.')':'';
		
		return $this->render('distribution',
				array(
						'orders'=>$orders,
						'carriers'=>$carriers,
						'shipping_services'=>$shipping_services,
						'time'=>$time,
						'operator'=>$operator,
				));
	}
	/**
	 * 打印配货单
	 * author million
	 * 88028624@qq.com
	 * 2015-03-19
	 * @return Ambigous <string, string>
	 */
	public function actionDistribution(){
		$this->layout='/mainPrint';
		if (Yii::$app->request->isPost){
			$order_ids = Yii::$app->request->post('order_id');
		}else{
			$order_ids = [Yii::$app->request->get('order_id')];
		}
		$orders =OdOrder::find()->where(['in','order_id',$order_ids])->all();
		//操作人
		$uid = Yii::$app->user->identity->getParentUid();
		//是否打印拣货单
		$time = time();
		OdOrder::updateAll(['is_print_distribution'=>1,'print_distribution_operator'=>$uid,'print_distribution_time'=>$time],['in','order_id',$order_ids]);
		//物流商
		$carriers = ApiHelper::getCarriers();
		//运输服务
		$shipping_services = ApiHelper::getShippingServices();
		$operator = Yii::$app->user->identity->getUsername();
		$fullName = Yii::$app->user->identity->getFullName();
		$operator.=strlen($fullName)>0?'('.$fullName.')':'';
		
		return $this->render('distribution',
				array(
						'orders'=>$orders,
						'carriers'=>$carriers,
						'shipping_services'=>$shipping_services,
						'time'=>$time,
						'operator'=>$operator,
				));
	}
}