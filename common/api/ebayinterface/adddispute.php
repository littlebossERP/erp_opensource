<?php
namespace common\api\ebayinterface;

use common\api\ebayinterface\base;
/**
 * 发起售前纠纷
 * @package interface.ebay.tradingapi
 */
class adddispute extends base{
	public $verb='AddDispute';
    public $ItemID;
    public $TransactionID;
    function setItemAndTransaction($ItemID,$TransactionID){
        $this->ItemID=$ItemID;
        $this->TransactionID=$TransactionID;
    }
	//从ebay获取相应itemid的信息
    public function add($reason,$explanation){
    	$this->config["compatabilityLevel"]=909;
        $xmlArr=array(
			'TransactionID'=>$this->TransactionID,
        	'ItemID'=>$this->ItemID,
        	'DisputeExplanation'=>$explanation,
        	'DisputeReason'=>$reason
		);
		$result=$this->setRequestBody($xmlArr)->sendRequest();

		//暂时不做该类纠纷的保存
// 		if(!$this->responseIsFailure()){
// 			$this->save($reason,$explanation,$result);
// 		}
		return $result;
	}
	
	/***
	 * 成功后将提交的数据 保存到数据库中
	 */
	public function save($reason,$explanation,$responseXmlArr){
		$transaction=OdEbayTransaction::model()->find('itemid=? and transactionid=?',array($this->ItemID,$this->TransactionID));
		$dispute=Ebay_Dispute::find('disputeid=?',array($responseXmlArr['DisputeID']));
		$dispute->setAttributes(array(
			'transactionid'=>$transaction->transactionid,
			'itemid'=>$transaction->itemid,
			'ctid'=>$transaction->id,
			'disputereason'=>$reason,
			'disputeexplanation'=>$explanation,
			'selleruserid'=>$transaction->ebayuser->selleruserid,
			'buyeruserid'=>$transaction->myorders->buyer_id,
			'isread'=>1,
		    'disputeid'=>$responseXmlArr['DisputeID'],
		));
		$dispute->save(0,'replace');
		#获得真实资料
		try {
			$eif=new EbayInterface_GetDispute();
			$eif->eBayAuthToken=$this->eBayAuthToken;
			$r=$eif->api($responseXmlArr['DisputeID']);
			$eif->save($r['Dispute']);
			$dispute->isread=1;
			$dispute->save(0);
		}catch (Exception $ex){
			Yii::log(print_r($ex,true));
		}
		return true;
	}
}
?>