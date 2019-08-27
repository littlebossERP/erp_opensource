<?php

namespace eagle\modules\order\models;

use Yii;
use yii\behaviors\SerializeBehavior;
use eagle\modules\order\helpers\OrderHelper;
use common\api\ebayinterface\reviseinventorystatus;

/**
 * This is the model class for table "od_ebay_transaction".
 *
 * @property string $id
 * @property string $uid
 * @property string $selleruserid
 * @property string $transactionid
 * @property string $order_id
 * @property string $goods_id
 * @property string $itemid
 * @property integer $eorderid
 * @property string $orderid
 * @property string $sku
 * @property integer $storage_id
 * @property integer $createddate
 * @property integer $quantitypurchased
 * @property string $platform
 * @property string $listingtype
 * @property string $buyer
 * @property string $title
 * @property string $status
 * @property string $amountpaid
 * @property string $adjustmentamount
 * @property string $transactionprice
 * @property string $shippingservicecost
 * @property string $shippingservice
 * @property string $finalvaluefee
 * @property string $finalvaluefee_currency
 * @property string $transactionsiteid
 * @property string $paypalemailaddress
 * @property string $shippingserviceselected
 * @property string $currency
 * @property string $desc
 * @property string $paidtime
 * @property string $seller_commenttype
 * @property string $seller_commenttext
 * @property integer $shipped
 * @property string $rn
 * @property string $property_id
 * @property string $status_payment
 * @property string $backmoney
 * @property integer $second_chance_offer_sent
 * @property string $buyer_feedback
 * @property string $buyer_dispute
 * @property string $additemfee
 * @property string $additemfee_currency
 * @property string $variation
 * @property integer $sendinvoice
 * @property integer $salesrecordnum
 * @property string $buyercheckoutmessage
 * @property string $orderlineitemid
 * @property integer $lasttimemodified
 * @property integer $goodscategory_id
 * @property integer $lotsize
 * @property integer $is_peihuo
 * @property string $peihuo_user
 * @property integer $peihuo_time
 * @property string $created
 * @property string $updated
 */
class OdEbayTransaction extends \yii\db\ActiveRecord
{
	/**
	 *  支付状态
	 */
	const STATUS_PAYMENT_COMPLETE = 'complete';
	const STATUS_PAYMENT_PROCESSING = 'progress';
	const STATUS_PAYMENT_WAITING = 'wait';
	const STATUS_PAYMENT_BACKMONEY = 'backmoney';
	const STATUS_PAYMENT_CANCEL_UNPAID = 'cancel_unpaid';
	const STATUS_PAYMENT_CANCEL_PAID = 'cancel_paid';
	
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'od_ebay_transaction';
    }

    /**
     * @return \yii\db\Connection the database connection used by this AR class.
     */
    public static function getDb()
    {
        return Yii::$app->get('subdb');
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['uid', 'transactionid', 'order_id', 'goods_id', 'itemid', 'eorderid', 'storage_id', 'createddate', 'quantitypurchased', 'paidtime', 'shipped', 'second_chance_offer_sent', 'sendinvoice', 'salesrecordnum', 'lasttimemodified', 'goodscategory_id', 'lotsize', 'is_peihuo', 'peihuo_time', 'created', 'updated'], 'integer'],
            [['listingtype', 'buyer', 'status', 'shippingserviceselected', 'property_id', 'variation', 'buyercheckoutmessage'], 'string'],
            [['amountpaid', 'adjustmentamount', 'transactionprice', 'shippingservicecost', 'finalvaluefee', 'backmoney', 'additemfee'], 'number'],
            [['selleruserid', 'title', 'buyer_feedback', 'buyer_dispute'], 'string', 'max' => 100],
            [['orderid'], 'string', 'max' => 64],
            [['sku', 'orderlineitemid'], 'string', 'max' => 55],
            [['platform', 'status_payment'], 'string', 'max' => 20],
            [['shippingservice', 'paypalemailaddress', 'desc', 'seller_commenttext'], 'string', 'max' => 255],
            [['finalvaluefee_currency', 'additemfee_currency'], 'string', 'max' => 16],
            [['transactionsiteid', 'currency', 'seller_commenttype', 'peihuo_user'], 'string', 'max' => 32],
            [['rn'], 'string', 'max' => 128]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'uid' => 'Uid',
            'selleruserid' => 'Selleruserid',
            'transactionid' => 'Transactionid',
            'order_id' => 'Order ID',
            'goods_id' => 'Goods ID',
            'itemid' => 'Itemid',
            'eorderid' => 'Eorderid',
            'orderid' => 'Orderid',
            'sku' => 'Sku',
            'storage_id' => 'Storage ID',
            'createddate' => 'Createddate',
            'quantitypurchased' => 'Quantitypurchased',
            'platform' => 'Platform',
            'listingtype' => 'Listingtype',
            'buyer' => 'Buyer',
            'title' => 'Title',
            'status' => 'Status',
            'amountpaid' => 'Amountpaid',
            'adjustmentamount' => 'Adjustmentamount',
            'transactionprice' => 'Transactionprice',
            'shippingservicecost' => 'Shippingservicecost',
            'shippingservice' => 'Shippingservice',
            'finalvaluefee' => 'Finalvaluefee',
            'finalvaluefee_currency' => 'Finalvaluefee Currency',
            'transactionsiteid' => 'Transactionsiteid',
            'paypalemailaddress' => 'Paypalemailaddress',
            'shippingserviceselected' => 'Shippingserviceselected',
            'currency' => 'Currency',
            'desc' => 'Desc',
            'paidtime' => 'Paidtime',
            'seller_commenttype' => 'Seller Commenttype',
            'seller_commenttext' => 'Seller Commenttext',
            'shipped' => 'Shipped',
            'rn' => 'Rn',
            'property_id' => 'Property ID',
            'status_payment' => 'Status Payment',
            'backmoney' => 'Backmoney',
            'second_chance_offer_sent' => 'Second Chance Offer Sent',
            'buyer_feedback' => 'Buyer Feedback',
            'buyer_dispute' => 'Buyer Dispute',
            'additemfee' => 'Additemfee',
            'additemfee_currency' => 'Additemfee Currency',
            'variation' => 'Variation',
            'sendinvoice' => 'Sendinvoice',
            'salesrecordnum' => 'Salesrecordnum',
            'buyercheckoutmessage' => 'Buyercheckoutmessage',
            'orderlineitemid' => 'Orderlineitemid',
            'lasttimemodified' => 'Lasttimemodified',
            'goodscategory_id' => 'Goodscategory ID',
            'lotsize' => 'Lotsize',
            'is_peihuo' => 'Is Peihuo',
            'peihuo_user' => 'Peihuo User',
            'peihuo_time' => 'Peihuo Time',
            'created' => 'Created',
            'updated' => 'Updated',
        ];
    }
    
    public function behaviors(){
    	return array(
    			'SerializeBehavior' => array(
    					'class' => SerializeBehavior::className(),
    					'serialAttributes' => array('buyer','status','shippingserviceselected','property_id','variation','shipmenttrackingdetail'),
    			)
    	);
    }
    
    /**
     * transaction保存的时候触发事件，将对应的item所对应的物流发货信息进行更新
     * @author fanjs
     */
    public function afterSave($insert, $changedAttributes){
    	parent::afterSave($insert, $changedAttributes);
    	if(array_intersect(array_keys($changedAttributes), ['shipped', 'shipmenttrackingdetail'])){
    		$item=OdOrderItem::find()->where('order_source_order_item_id ='.$this->id)->one();
    		if (!empty($item)){
    			$odorder = OdOrder::findOne($item->order_id);
    			if (!empty($odorder)){
    				if ($this->shipped==1){
    					$odorder->shipping_status=1;
    					$odorder->save(false);
    				}echo 'transactionID '.$this->id."\n";
    				if (count($this->shipmenttrackingdetail)){
    					$num=count($this->shipmenttrackingdetail);
    					$shipment=$this->shipmenttrackingdetail[$num-1];
    					//print_r($shipment)."\n";
    					echo $shipment['ShipmentTrackingNumber']."\n";
    					$logisticInfoList['0']=[
    						'order_source_order_id'=>$this->orderid,
    						'order_source'=>'ebay',
    						'selleruserid'=>$this->selleruserid,
    						'tracking_number'=>@$shipment['ShipmentTrackingNumber'],
    						'tracking_link'=>'',
    						'shipping_method_name'=>@$shipment['ShippingCarrierUsed'],
    						'addtype'=>'订单同步获取',
    					];echo $item->order_id.'-->'.@$shipment['ShipmentTrackingNumber']."\n";
    					OrderHelper::saveTrackingNumber($item->order_id, $logisticInfoList,'1');
    				}
    			}
    		}
    	}
    	
    	//自动补库存插入队列 @author fanjs
    	if (isset($this->itemid)&&strlen($this->itemid)>0){
    		$sku=isset($this->variation['SKU'])?$this->variation['SKU']:$this->sku;
    		reviseinventorystatus::AddQueue( $this->itemid, $sku, $this->quantitypurchased, $this->transactionid );
    	}
    }
}
