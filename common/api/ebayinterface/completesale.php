<?php
namespace common\api\ebayinterface;

use eagle\modules\order\models\OdEbayOrder;
use eagle\modules\order\models\OdEbayTransaction;
use eagle\modules\util\helpers\SysLogHelper;
/**
 * 标记付款、发货
 * @package interface.ebay.tradingapi
 */
class completesale extends base{
    //从ebay获取相应itemid的信息
    public $verb = 'CompleteSale';
    public $ItemID;
    public $TransactionID;
    public $Order;
    public $OrderID;
    function setItemAndTransaction($ItemID,$TransactionID){
        $this->ItemID=$ItemID;
        $this->TransactionID=$TransactionID;
    }
    
    function setOrder(OdEbayOrder $order){
    	$this->Order=$order;
    	if (strlen($order->ebay_orderid)){
    		$this->OrderID=$order->ebay_orderid;
    	}
    	if(0==strlen($this->OrderID)){
        	 
        	$MT=OdEbayTransaction::find()->where('eorderid='.$order->eorderid)->one();
        	if(!empty($MT)){
        	    $this->setItemAndTransaction($MT->itemid,$MT->transactionid);
        	}
    	}
    }
    
    /**
     * 处理id
     *
     * @param unknown_type $xmlArr
     */
    function dealWithOrder(&$xmlArr){
		if ($this->OrderID){
        	$xmlArr['OrderID']=$this->OrderID;
        }else {
			$xmlArr['ItemID']=$this->ItemID;
            $xmlArr['TransactionID']=$this->TransactionID;
        }
    }
    /**
     * 标记为已发货
     *
     * @param unknown_type $ShipmentTrackingNumber	运单号
     * @param unknown_type $ShippingCarrierUsed		物流名称
     * @param unknown_type $Notes					注释
     * @param unknown_type $ShippedTime				发货时间
     * @return unknown
     */
    function shipped($ShipmentTrackingNumber,$ShippingCarrierUsed=null,$Notes=null,$ShippedTime=null){
    	global $LOG_CONTENT;
//        $this->config["compatabilityLevel"]=635;
        $xmlArr=array(
            'Shipment'=>array(
                'ShipmentTrackingDetails'=>array(
                    'ShipmentTrackingNumber'=>$ShipmentTrackingNumber,
                )
            ),
            'Shipped'=>true,
        );
//         if (count($params)){
//         	$line=[];
//         	foreach ($params as $k=>$v){
//         		$line[$k]['LineItem']['ItemID']=$v['itemid'];
//         		$line[$k]['LineItem']['TransactionID']=$v['transactionid'];
//         	}
//         	$xmlArr['Shipment']['ShipmentTrackingDetails']['ShipmentLineItem']=$line;
//         }
        $this->dealWithOrder($xmlArr);
        if (strlen($ShippingCarrierUsed)) {
        	$xmlArr['Shipment']['ShipmentTrackingDetails']['ShippingCarrierUsed']=$ShippingCarrierUsed;
        }
        if (strlen($Notes)) {
        	$xmlArr['Shipment']['Notes']=$Notes;
        }
        if (!strlen($ShipmentTrackingNumber)){
        	unset($xmlArr['Shipment']);
        }
        if (strlen($ShippedTime)){
            $xmlArr['ShippedTime']=$ShippedTime;
        }
       // print_r($xmlArr);
       // \Yii::info(print_r($xmlArr,1));
        
        $rtn= $this->setRequestBody($xmlArr)
                    ->sendRequest();
        
        \Yii::info("\nebay ship order:". print_r($xmlArr,true)."\nWith Detail:". print_r($LOG_CONTENT,true)."\n Get eaby result:". print_r($rtn,true) ,"sync_order_ship" );
        
        if (isset($LOG_CONTENT['puid']) and $LOG_CONTENT['puid'] == 2123){
     	   $journal_id = SysLogHelper::InvokeJrn_Create("OMS",__CLASS__, __FUNCTION__ , array("sync_order_ship", "ebay ship order:". print_r($xmlArr,true)," With Detail:". print_r($LOG_CONTENT,true),"Get eaby result:". print_r($rtn,true) ));
        }
        
        return $rtn;
    }
    //对客户评价
    function feedback($CommentType,$CommentText){
        $xmlArr=array(
            'FeedbackInfo'=>array(
                'CommentType'=>$CommentType,
                'CommentText'=>$CommentText,
            ),
        );
        $this->dealWithOrder($xmlArr);
        return $this->setRequestBody($xmlArr)
                    ->sendRequest();
    }
    //标记为客户已付款
    function paid(){
        $xmlArr=array(
            'Paid'=>true,
        );
        $this->dealWithOrder($xmlArr);
        return $this->setRequestBody($xmlArr)
                    ->sendRequest();
    }
}
?>