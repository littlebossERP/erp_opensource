<?php

namespace eagle\modules\order\controllers;

use yii\data\Pagination;
use eagle\modules\message\helpers\MessageHelper;
use eagle\modules\order\models\OdOrder;
use eagle\models\SysCountry;
use eagle\modules\order\helpers\OrderHelper;
use yii\web\Controller;
use eagle\modules\tracking\models\Tracking;

/**
 * 外部访客获得订单信息的controller
 * @author		lzhl 	2015/12/18		初始化
 +----------------------------------------------------------
 **/
class OrderCustomerController extends Controller{
	public $enableCsrfValidation = false;

	/**
	 +----------------------------------------------------------
	 * 获取单个订单发票   action
	 +----------------------------------------------------------
	 * @access 		public
	 * @params 		$order_id		订单id
	 * @params		$app			调用的app，默认为oms
	 +----------------------------------------------------------
	 * log			name	date			note
	 * @author		lzhl 	2017/01/25		初始化
	 +----------------------------------------------------------
	 **/
	public function actionOrderInvoice($order_id,$app='oms'){
		if(!empty($_REQUEST['order_id']))
			$order_id = $_REQUEST['order_id'];
		
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
					$uid = (int)$parmas[0];
					$track_id = (int)$parmas[1];
				}
			}
		}
		if (empty($uid)){
			//异常情况
			return $this->render('//errorview',['title'=>'请先登录','message'=>'您还未登录，不能进行该操作']);
		}
 
	
		$mpdf=new \HTML2PDF('P','A4','en');
		//亚洲字体处理
		
		$track = Tracking::findOne($track_id);
		if(empty($track->platform) || empty($track->order_id)){
			return $this->render('//errorview',['title'=>'order info lost','message'=>'订单信息不完整。']);
		}
		
		$orderModel = OdOrder::find()->where(['order_source'=>$track->platform,'order_source_order_id'=>$order_id])->one();
		if(!empty($orderModel->consignee_country_code)){
			$toCountry = SysCountry::findOne(strtoupper($orderModel->consignee_country_code));
			if(!empty($toCountry->region) && in_array($toCountry->region, ['Asia','Southeast Asia']))
				$mpdf->setDefaultFont('droidsansfallback');
		}
		
		$text = OrderHelper::pdf_order_invoice($orderModel->order_id,'',$uid);
		$mpdf->WriteHTML($text);
		$mpdf->Output('order_invoice_'.$orderModel->order_source_order_id.'.pdf');
		exit();
	}
	
}

?>