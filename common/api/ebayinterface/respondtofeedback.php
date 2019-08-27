<?php
/**
 * 对买家的评价进行回复
 * @package interface.ebay.tradingapi
 */
class EbayInterface_RespondToFeedback extends EbayInterface_base{
	public $verb='RespondToFeedback';
	/**
	 * 执行操作
	 *
	 * @param bigint $FeedbackID
	 * @param string $TargetUserID
	 * @param string $ResponseText 回应内容，Max length: 80 (125 for the Taiwan site).
	 * @param string $ResponseType	FollowUp|Reply
	 * @param unknown_type $ItemID	Required if FeedbackID is not provided
	 * @param unknown_type $TransactionID
	 */
	function api($FeedbackID,$TargetUserID,$ResponseText,$ResponseType,$ItemID,$TransactionID){
		$xmlArr=array(
			'FeedbackID'=>$FeedbackID,
			'TargetUserID'=>$TargetUserID,
			'ResponseText'=>$ResponseText,
			'ResponseType'=>'Reply', //$ResponseType,
			'ItemID'=>$ItemID,
			'TransactionID'=>$TransactionID,
		);
		$r=$this->setRequestBody($xmlArr)->sendRequest();
		return $r;
	}
}