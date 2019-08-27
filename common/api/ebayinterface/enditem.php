<?php
namespace common\api\ebayinterface;

use common\api\ebayinterface\base;
/**
 * 结束刊登
 * @package interface.ebay.tradingapi
 *
 */
class enditem extends base{
    static public $EndingReason=array(
        //'CustomCode',
        'Incorrect'=>'Incorrect',
        'LostOrBroken'=>'LostOrBroken',
        'NotAvailable'=>'NotAvailable',
        'OtherListingError'=>'OtherListingError',
        'SellToHighBidder'=>'SellToHighBidder',
        'Sold'=>'Sold',
    );
    /***
     * $EndingReason: 
     *   CustomCode ,     
     */         
    public function api($ItemID,$EndingReason='',$SellerInventoryID=''){
        $this->verb = 'EndItem';
        $xmlArrData=array(
            'RequesterCredentials'=>array(
    			'eBayAuthToken'=>$this->eBayAuthToken,
    		),
    		'ItemID'=>$ItemID,
        );
        if(strlen($EndingReason)>0){
            $xmlArrData['EndingReason']=$EndingReason;
        }
        if(strlen($SellerInventoryID)){
            $xmlArrData['SellerInventoryID']=$SellerInventoryID;
        }
        $xmlArr=array(
			'EndItemRequest xmlns="urn:ebay:apis:eBLBaseComponents"'=>$xmlArrData
		);
		$result=$this->sendHttpRequest($xmlArr);
        return $result;
    }
}