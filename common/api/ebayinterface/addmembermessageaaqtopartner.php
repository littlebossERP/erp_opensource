<?php
namespace common\api\ebayinterface;

use common\api\ebayinterface\base;
/**
 * 回复站内信
 * @package interface.ebay.tradingapi
 * 发送有交易关系的站内信
 */
class addmembermessageaaqtopartner extends base{
    public $verb='AddMemberMessageAAQToPartner';
    
    /**
     * General
(in/out) General questions about the item.
MultipleItemShipping
(in/out) Questions related to the shipping of this item bundled with other items also purchased on eBay.
Payment
(in/out) Questions related to the payment for the item.
Shipping
(in/out) Questions related to the shipping of the item.
     * @var unknown
     */
    public $questiontype = ['General','MultipleItemShipping','Payment','Shipping'];
    
    public function api($token,$itemid,$body,$questiontype,$buyerid,$subject,$emailtoseller=0){
    	$body = str_replace(';', '&#59;', $body);
        $xmlArr=array(
            'RequesterCredentials'=>array(
                'eBayAuthToken'=>$token,
            ),
            'ItemID'=>$itemid,
            'MemberMessage'=>array(
				'Body'=>htmlspecialchars($body),
				'QuestionType'=>$questiontype,
				'RecipientID'=>$buyerid,
				'Subject'=>$subject,
				'EmailCopyToSender'=>$emailtoseller
            ),
        );
        
        //发送获得response
        $result=$this->setRequestBody($xmlArr)->sendRequest(0);
		return $result;
    }
}
?>