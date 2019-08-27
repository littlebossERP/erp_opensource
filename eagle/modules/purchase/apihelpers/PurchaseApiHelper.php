<?php
/**
 * @link http://www.witsion.com/
 * @copyright Copyright (c) 2014 Yii Software LLC
 * @license http://www.witsion.com/
 */
namespace eagle\modules\purchase\apihelpers;
use eagle\modules\purchase\models\Purchase;

use yii;
use yii\data\Pagination;



/**
 * BaseHelper is the base class of module BaseHelpers.
 *

 */
class PurchaseApiHelper {
	
//采购单的付款状态
	const PURCHASE_UNPAID= 1; //等待付款
	const PURCHASE_PAID  = 2; // 已经付款
	const PURCHASE_NOT_REQUIRED  = 3; //采购计划不需要付款，没付款而且被取消的采购单
	
	//采购单的状态	
	const WAIT_FOR_ARRIVAL  = 1; //审批通过,由采购计划正式转成采购单，等待到货
	const WAIT_FOR_PARTIAL_ARRIVAL  = 2;
	const ALL_ARRIVED  = 3;
	const PARTIAL_ARRIVED_CANCEL_LEFT  = 4;
	const CANCELED  = 5;
	const DRAFT=6; //采购计划草稿,没提审
	const PLAN_WAIT_FOR_APPROVAL=7; //采购计划,已经提审,等待审批
	const REJECT_WAIT_FOR_MODIFY=8; //计划审批失败，等待修改重新提审
	const PLAN_CANCELED=9;

	public static function testconsole(){
		echo "purchaseHelper testconsole \n";
	}
	
	//采购相关的所有状态信息。             type ---order 表示采购单；plan表示采购计划
	//label 是用于采购list界面的状态列显示
	// operation list界面mouseover的操作选项
	//key 主要是为了前端js来判断当前订单的状态
	public static function getAllStatusInfo() {		
		$statusInfo=array(
		    	self::WAIT_FOR_ARRIVAL=>array("operation"=>"view,edit,cancel,arrival,print","type"=>"order","label"=>"等待到货","key"=>"WAIT_FOR_ARRIVAL"),
				self::WAIT_FOR_PARTIAL_ARRIVAL=>array("operation"=>"view,edit,cancel_left,arrival,arrival_record,print","type"=>"order","label"=>"部分到货等待剩余","key"=>"WAIT_FOR_PARTIAL_ARRIVAL"),
				self::ALL_ARRIVED =>array("operation"=>"view,arrival_record,print","type"=>"order","label"=>"全部到货","key"=>"ALL_ARRIVED"),
				self::PARTIAL_ARRIVED_CANCEL_LEFT=>array("operation"=>"view,arrival_record,print","type"=>"order","label"=>"部分到货不等剩余","key"=>"PARTIAL_ARRIVED_CANCEL_LEFT"),
				self::CANCELED=>array("operation"=>"view","type"=>"order","label"=>"已作废","key"=>"CANCELED"),
				self::DRAFT=>array("operation"=>"view,edit,commit_review,cancel_plan","type"=>"plan","label"=>"计划草稿","key"=>"DRAFT"),
				self::PLAN_WAIT_FOR_APPROVAL=>array("operation"=>"view,review,cancel_plan","type"=>"plan","label"=>"等待审批","key"=>"PLAN_WAIT_FOR_APPROVAL"),
				self::REJECT_WAIT_FOR_MODIFY=>array("operation"=>"view,edit,cancel_plan","type"=>"plan","label"=>"等待修改重新提审","key"=>"REJECT_WAIT_FOR_MODIFY"),				
				self::PLAN_CANCELED=>array("operation"=>"view","type"=>"plan","label"=>"计划已作废","key"=>"PLAN_CANCELED"),
		);
		return $statusInfo;
		
	}	
	public static function getStatusIdLabelMap(){
		$statusInfo=self::getAllStatusInfo();
		$statusIdLabelMap=array();
		foreach($statusInfo as $statusId =>$info){
			$statusIdLabelMap[$statusId]=$info["label"];
		}
		return $statusIdLabelMap;
	}
	
	
	/**
	 * 获取订单来源列表
	 */
	static public function getOrderSource() {
		return array(
			1 => 'igv',
			2 => 'ebay',
			3 => 'wap',
			4 => 'ios',
			5 => 'Android'
		);
	}
	
	/**
	 * 获取订单状态列表
	 */
	static public function getOrderStatusList() {
		return array(
			1 => '处理中',
			2 => '已完成',
			3 => '取消作废',
		);
	}
	
	/**
	 * 获取付款状态列表
	 */
	static public function getPayStatusList() {
		return array(
			1 => '未付款',
			2 => '等待付款确认',
			3 => '已付款未处理',
			4 => '付款审核中',
			5 => '付款已审核',
			6 => 'chargeback',
			7 => '退款处理中',
			8 => '全额退款',
			9 => '部分退款'
		);
	}
	
	/**
	 * 获取递送状态列表
	 */
	static public function getDeliveryStatusList() {
		return array(
			1 => '未递送',
			2 => '部分递送',
			3 => '完全递送'
		);
	}
	
	/**
	 * 获取订单商品处理状态列表
	 */
	static public function getOperatorStatusList() {
		return array(
			0 => '待完善信息',
			1 => '加急递送',
			2 => '正常递送',
			4 => '需审核',
			8 => '审核中',
			16 => '递送完成'
		);
	}
	
	/**
	 * 订单信息列表
	 */
	static public function getListDataByCondition()
	{
		/*if (Yii::$app->request->post('_search')) { //搜索查询
			$params = Yii::$app->request->post();
			$order = new OrdOrder();
			$order_search = $order->search($params['order']);
			$query = $order_search->query;
		} else { //一般查询
			$query = \oms\modules\order\models\OrdOrder::find();
		}
		*/
		
		$query = Purchase::find();
		$pagination = new Pagination([
				'defaultPageSize' => 20,
				'totalCount' => $query->count(),
		]);
		$data['pagination'] = $pagination;
		$data['data'] = $query->orderBy('pc_purchase.create_time desc')
		//->joinWith(['ordPay' => function ($query) {}])
		->offset($pagination->offset)
		->limit($pagination->limit)
		->asArray()
		->all();
		
		$statusIdLabelMap = self::getStatusIdLabelMap();
		$data['purchaseStatusMap'] = $statusIdLabelMap;
		foreach ($data['data'] as $key => $val) {
			$data['data'][$key]['status']=$statusIdLabelMap[$val['status']];
		}
		/*$data['pagination'] = $pagination;
		$data['orderSource'] = self::getOrderSource();
		$data['orderStatus'] = self::getOrderStatusList();
		$data['payStatus'] = self::getPayStatusList();
		$data['deliveryStatus'] = self::getDeliveryStatusList();
		$data['operatorStatus'] = self::getOperatorStatusList();
		foreach ($data['data'] as $key => $val) {
			$data['data'][$key]['order_status'] = $val['order_status'] ? $data['orderStatus'][$val['order_status']] : '未处理';
			$data['data'][$key]['pay_status'] = $val['pay_status'] ? $data['payStatus'][$val['pay_status']] : '未处理';
			$data['data'][$key]['delivery_status'] = $val['delivery_status'] ? $data['deliveryStatus'][$val['delivery_status']] : '未处理';
			foreach ($data['operatorStatus'] as $ops => $os) {
				if ($val['operator_status'] & $ops) {
				 	$operator_status[$key][] = $os;
				}
			}
			$data['data'][$key]['operator_status'] = empty($operator_status[$key]) ? '未处理' : implode(',', $operator_status[$key]);
			$data['data'][$key]['is_tag'] = OrdOrderHasTag::findAll(['order_id' => $val['order_id']]) ? true : false;
		}*/
		return $data;
	}
	static public function addPurchase($data){
		$model = new Purchase();
		$model->purchase_order_id="pu0001";
		$model->warehouse_id=$data['warehouse_id'];
		$model->status=1;
		$model->save();
	}
}
