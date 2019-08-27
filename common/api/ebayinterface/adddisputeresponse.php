<?php
/**
 * 回应纠纷
 * @package interface.ebay.tradingapi
 *
 */
class EbayInterface_AddDisputeResponse extends EbayInterface_base{
	const DISPUTEACTIVITY_SellerAddInformation='SellerAddInformation';
	public $verb='AddDisputeResponse';
	//从ebay获取相应itemid的信息
    public function api($DisputeID,$DisputeActivity,$MessageText=null,$ShipmentTrackNumber=null,$ShippingCarrierUsed=null,$ShippingTime=null){
 
        $xmlArr=array(
        	'DisputeID'=>$DisputeID,
        	'DisputeActivity'=>$DisputeActivity,
		);
		if (strlen($MessageText)){
			$xmlArr['MessageText']=$MessageText;
		}
		if (strlen($ShipmentTrackNumber)){
			$xmlArr['ShipmentTrackNumber']=$ShipmentTrackNumber;
		}
		if (strlen($ShippingCarrierUsed)){
			$xmlArr['ShippingCarrierUsed']=$ShippingCarrierUsed;
		}
		if (strlen($ShippingTime)){
			$xmlArr['ShippingTime']=$ShippingTime;
		}
		
		$result=$this->setRequestBody($xmlArr)->sendRequest();

		return $result;
	}
	
}
?>