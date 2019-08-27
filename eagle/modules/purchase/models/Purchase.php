<?php

namespace eagle\modules\purchase\models;

use Yii;

/**
 * This is the model class for table "pc_purchase".
 *
 * @property integer $id
 * @property string $purchase_order_id
 * @property string $purchase_source_id
 * @property integer $warehouse_id
 * @property integer $supplier_id
 * @property string $supplier_name
 * @property integer $status
 * @property integer $delivery_method
 * @property string $delivery_number
 * @property string $delivery_fee
 * @property string $amount
 * @property string $amount_subtotal
 * @property string $amount_refunded
 * @property integer $payment_status
 * @property string $pay_date
 * @property integer $payment_method
 * @property string $payment_record_id
 * @property string $is_refunded
 * @property string $is_pending_check
 * @property string $expected_arrival_date
 * @property string $is_arrive_goods
 * @property string $comment
 * @property string $capture_user_name
 * @property string $create_time
 * @property string $update_time
 * @property string $reject_reason
 */
class Purchase extends \yii\db\ActiveRecord {
	/**
	 * @inheritdoc
	 */
	public static function tableName() {
		return 'pc_purchase';
	}
	
	/**
	 * @inheritdoc
	 */
	public function rules() {
		return [ 
				[ 
						[ 
								'warehouse_id',
								'supplier_id',
								'status',
								'delivery_method',
								'payment_status',
								'payment_method',
								'payment_record_id' 
						],
						'integer' 
				],
				[ 
						[ 
								'delivery_fee',
								'amount',
								'amount_subtotal',
								'amount_refunded' 
						],
						'number' 
				],
				[ 
						[ 
								'pay_date',
								'expected_arrival_date',
								'create_time',
								'update_time' 
						],
						'safe' 
				],
				[ 
						[ 
								'comment' 
						],
						'string' 
				],
				[ 
						[ 
								'purchase_order_id' 
						],
						'string',
						'max' => 255 
				],
				[ 
						[ 
								'purchase_source_id' 
						],
						'string',
						'max' => 100 
				],
				[ 
						[ 
								'supplier_name',
								'reject_reason' 
						],
						'string',
						'max' => 255 
				],
				[ 
						[ 
								'delivery_number' 
						],
						'string',
						'max' => 30 
				],
				[ 
						[ 
								'is_refunded',
								'is_pending_check',
								'is_arrive_goods' 
						],
						'string',
						'max' => 1 
				],
				[ 
						[ 
								'capture_user_name' 
						],
						'string',
						'max' => 50 
				] 
		];
	}
	
	/**
	 * @inheritdoc
	 */
	public function attributeLabels() {
		return [ 
				'id' => 'ID',
				'purchase_order_id' => 'Purchase Order ID',
				'purchase_source_id' => 'Purchase Source ID',
				'warehouse_id' => 'Warehouse ID',
				'supplier_id' => 'Supplier ID',
				'supplier_name' => 'Supplier Name',
				'status' => 'Status',
				'delivery_method' => 'Delivery Method',
				'delivery_number' => 'Delivery Number',
				'delivery_fee' => 'Delivery Fee',
				'amount' => 'Amount',
				'amount_subtotal' => 'Amount Subtotal',
				'amount_refunded' => 'Amount Refunded',
				'payment_status' => 'Payment Status',
				'pay_date' => 'Pay Date',
				'payment_method' => 'Payment Method',
				'payment_record_id' => 'Payment Record ID',
				'is_refunded' => 'Is Refunded',
				'is_pending_check' => 'Is Pending Check',
				'expected_arrival_date' => 'Expected Arrival Date',
				'is_arrive_goods' => 'Is Arrive Goods',
				'comment' => 'Comment',
				'capture_user_name' => 'Capture User Name',
				'create_time' => 'Create Time',
				'update_time' => 'Update Time',
				'reject_reason' => 'Reject Reason' 
		];
	}
	public static function getDb() {
		\Yii::warning ( "purchase getDb 1" );
		$ddb = Yii::$app->subdb;
		\Yii::warning ( "purchase getDb 2" );
		return $ddb;
	}
}
