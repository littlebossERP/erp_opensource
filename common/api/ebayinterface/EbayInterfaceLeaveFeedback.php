<?php
/**
 * 自动回评
 * @package interface.ebay.tradingapi
 */
class EbayInterfaceLeaveFeedback extends EbayInterface_base{
    public $verb='LeaveFeedback';
    
	/*
	 *token   Ebay token
	 *CommentText  评价内容
	 *CommentType  评价类型
	 *ItemID       商品ID
	 *OrderLineItemID 订单ID
	 *TargetUser   被评价者ID
	 *TransactionID交易号
	 */
    public function api($token,$CommentText,$CommentType,$TargetUser,$ItemID=null, $TransactionID=null, $OrderLineItemID=null){
		//|商品ID和订单ID和交易号不得同时为空
		if(!($ItemID || $OrderLineItemID || $TransactionID)) return false;
        $xmlArr=array(
            'RequesterCredentials'=>array(
                'eBayAuthToken'=>$token,
            ),
            'CommentText'=>$CommentText,
            'CommentType'=>$CommentType,
            'ItemID'=>$ItemID,
            //'OrderLineItemID'=>$OrderLineItemID,
			/*
			 *   <SellerItemRatingDetailArray> ItemRatingDetailArrayType
    <ItemRatingDetails> ItemRatingDetailsType
      <Rating> int </Rating>
      <RatingDetail> FeedbackRatingDetailCodeType </RatingDetail>
    </ItemRatingDetails>
    <!-- ... more ItemRatingDetails nodes allowed here ... -->
	</SellerItemRatingDetailArray>
			 */
            'TargetUser'=>$TargetUser,
            'TransactionID'=>$TransactionID
        );
        
        //发送获得response
        $result=$this->setRequestBody($xmlArr)->sendRequest(1);
        //转成数组
        $xml = simplexml_load_string($result);
        $data = json_decode(json_encode($xml),TRUE);
		return $data;
    }
}
?>
