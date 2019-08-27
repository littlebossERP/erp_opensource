<?php
namespace common\api\ebayinterface;

use common\api\ebayinterface\base;
/**
 * 回复站内信
 * @package interface.ebay.tradingapi
 */
class addmembermessagertq extends base{
    public $verb='AddMemberMessageRTQ';
    /**
     * 
     * @param message中的  externalmessageid    $ParentMessageID
     * @param message中的 sender $RecipientID
     * @param 内容 $Body
     * @param unknown_type $ItemID
     * @param unknown_type $EmailCopyToSender
     * @date 2014-4-27
     */
    public function api($token,$ParentMessageID,$RecipientID,$Body,$ItemID=0,$EmailCopyToSender=0 ){
        $xmlArr=array(
        	'RequesterCredentials'=>array(
        		'eBayAuthToken'=>$token,
        	),
            'MemberMessage'=>array(
                'Body'=>$Body,
                'ParentMessageID'=>$ParentMessageID, //
                'RecipientID'=>$RecipientID,
				'EmailCopyToSender'=>$EmailCopyToSender,
            ),
        );
        if ($ItemID){
            $xmlArr['ItemID']=$ItemID;
        }
        //发送获得response
        $result=$this->setRequestBody($xmlArr)->sendRequest(0);
		return $result;
    }
}
?>