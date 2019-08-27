<?php
/**
 * 获得站内信列表
 * @package interface.ebay.tradingapi
 */
class EbayInterface_GetMemberMessages extends EbayInterface_base{
	public $verb='GetMemberMessages';
	/**
	 * api
	 *
	 * @param Ebay_User $ebay_user
	 * @param int $startTime
	 * @param int $endTime
	 * @param int $page
	 */
	public function api($startTime,$endTime,$page=1){
		$xmlArr=array(
			'MailMessageType'=>'All',
			'StartTime'=>self::dateTime(strtotime($startTime)),
			'EndTime'=>self::dateTime(strtotime($endTime)),
			'Pagination'=>array(
				'EntriesPerPage'=>5,
				'PageNumber'=>$page
			),
		);
		return $this->setRequestBody($xmlArr)->sendRequest(0,150);
		
	}
    //将通过message 单个处理
    public function saveSingle($msg){
    	
        	$eu=SaasEbayUser::model()->where('selleruserid = ?',$msg['Question']['RecipientID'])->getOne();
        	$mymsg= Ebay_MyMessage::find('messageid=? ',$msg['Question']['MessageID'])->getOne();
			$detail=Ebay_Mymessagedetail::find('messageid=?',$msg['Question']['MessageID'])->getOne();
            $mymsg->setAttributes(array(
                'uid'=>$eu->user->parent_uid,
                'listingstatus'=>@$msg['Item']['ListingStatus'],
            	'messageid'=>@$msg['Question']['MessageID'],
                'itemid'=>@$msg['Item']['ItemID'],
                'messagetype'=>@$msg['Question']['MessageType'],
                'questiontype'=>@$msg['Question']['QuestionType'],
                'receivedate'=>@$msg['CreationDate'],
                'recipientuserid'=>@$msg['Question']['RecipientID'],
                'sender'=>@$msg['Question']['SenderID'],
                'responseenabled'=>'true',
//                'responseurl'=>@$msg['ResponseDetails']['ResponseURL'],
                'subject'=>@$msg['Question']['Subject'],
//                'text'=>@$msg['Text']
            ));
            @$mymsg->save(0,'replace');
            if ($detail->isNewRecord){
	            $detail->setAttributes(array(
	            	'messageid'=>@$msg['Question']['MessageID'],
	            	'responseurl'=>'none',
	            	'text'=>@$msg['Question']['Body']
	            ));
	            $detail->save(0,'replace');
            }
        return true;
    }
}
?>