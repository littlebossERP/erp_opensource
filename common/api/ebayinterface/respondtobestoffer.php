<?php
namespace common\api\ebayinterface;

use common\api\ebayinterface\base;
use eagle\modules\order\models\EbayBestoffer;
/**
 * 回应BestOffer议价信息
 * @package interface.ebay.tradingapi
 */
class respondtobestoffer extends base{
	public $verb='RespondToBestOffer';
	/**
	 * 执行操作
	 *
	 * @param bigint $BestOfferID
	 * @param string $Action
	 * @param string $CounterOfferPrice
	 * @param string $CounterOfferQuantity
	 * @param int $ItemID
	 * @param string $SellerResponse
	 */
	function api($array,$id,$operate = NULL){
		$rs=$this->setRequestBody($array)->sendRequest();
		if ($rs['Ack']=='Success'||$rs['Ack']=='Warning'){
			$this->save($array,$id,$operate);
		}
		return $rs;
	}
	
	function save($array,$id,$operate){
		$eb = EbayBestoffer::find()->where('bestofferid=:b',[':b'=>$id])->one();
		switch ($array['Action']){
			case 'Accept':
				$eb->desc='接受议价';
				$eb->operate=$operate;
				break;
			case 'Counter':
				$eb->desc='与买家进行议价,您的价格为'.$array['CounterOfferPrice'];
				$eb->counterofferprice=$array['CounterOfferPrice'];//议价价格
				$eb->operate=$operate;
				break;
			case 'Decline':
				$eb->desc='拒绝议价';
				$eb->operate=$operate;	
				break;
		}
		$eb->status=1;
		$eb->save();
		return;
	}
}
?>