<?php

namespace eagle\modules\message\helpers;

use eagle\modules\message\models\CmEbayUsercase;
use eagle\modules\order\models\OdEbayTransaction;
use eagle\modules\message\models\CmEbayUsercaseEbpdetail;
use eagle\modules\message\models\CmEbayDispute;

class ResolutionEbayHelper{
	
	/**
	 * 常用的操作数组映射
	 * @author witsionjs
	 */
	static $activity = [
		'issueFullRefund'=>'发起全额退款',
		'issuePartialRefund'=>'发起部分退款',
		'offerOtherSolution'=>'提议其他解决方式',
		'offerPartialRefund'=>'提议部分退款',
		'offerRefundUponReturn'=>'提议退款且要求退货',
		'provideRefundInfo'=>'提供退货信息',
		'provideShippingInfo'=>'提供物流信息',
		'provideTrackingInfo'=>'提供追踪信息',
		'appealToCustomerSupport'=>'开启仲裁',
		'escalateToCustomerSupport'=>'升级仲裁'
	];
	
	static $disputesStatus = [
			'CASE_CLOSED_CS_RESPONDED'=>'eBay有通知',
			'CLOSED'=>'已关闭',
			'CS_CLOSED'=>'eBay已关闭',
			'EXPIRED'=>'过期',
			'MY_PAYMENT_DUE'=>'等待付款',
			'MY_RESPONSE_DUE'=>'等待回复',
			'OTHER'=>'其他',
			'OTHER_PARTY_CONTACTED_CS_AWAITING_RESPONSE'=>'对方等待eBay回复',
			'OTHER_PARTY_RESPONSE_DUE'=>'对方已回复',
			'PAID'=>'已付款',
			'WAITING_DELIVERY'=>'待收货',
			'YOU_CONTACTED_CS_ABOUT_CLOSED_CASE'=>'已向eBay申请关闭',
			'YOU_CONTACTED_CS_AWAITING_RESPONSE'=>'卖家等待eBay回复'
			];
	static $disputesType = [
			'EBP_INR'=>'未收到',
			'EBP_SNAD'=>'描述不符',
			'CANCEL_TRANSACTION'=>'取消交易',
			'UPI'=>'未付款',
			];
	 
	/**
	 * 将通过 api取得的值 保存到数据库中
	 * @param $responseArr 从接口返回的数组
	 * @param $selleruserid 字符串  
	 * @param $refresh 是否需要重新取
	 * @author hqw
	 * @date 2015-11-18
	 */
	static function ebayUserCaseApiSave($responseArr,$selleruserid,$uid,&$refresh){
		try{
			//符合需要的数据，则进行保存
			//if ($responseArr['caseId']['type']=='INR' || $responseArr['caseId']['type']=='EBP_INR'||$responseArr['caseId']['type']=='EBP_SNAD'){
			$cmEbayUsercaseOne = CmEbayUsercase::findOne(['caseid'=>$responseArr['caseId']['id']]);
			//查找记录的归属UID
			
			if($cmEbayUsercaseOne === null){
				$cmEbayUsercaseOne = new CmEbayUsercase();
				$cmEbayUsercaseOne->create_time = time();
			}
			
			$refresh=false;
			if ($cmEbayUsercaseOne->lastmodified_date < strtotime(@$responseArr['lastModifiedDate'])){
				$cmEbayUsercaseOne->has_read=0;
				$refresh=true;
			}
	
			$data=array(
					'caseid'=>$responseArr['caseId']['id'],
					'type'=>$responseArr['caseId']['type'],
					'user_role'=>$responseArr['user']['role'], //发起人
					'itemid'=>$responseArr['item']['itemId'],
					'itemtitle'=>$responseArr['item']['itemTitle'],
					'transactionid'=>$responseArr['item']['transactionId'],
					'casequantity'=>$responseArr['caseQuantity'],
					'caseamount'=>$responseArr['caseAmount'],
					'created_date'=>strtotime($responseArr['creationDate']),
					'lastmodified_date'=>strtotime(@$responseArr['lastModifiedDate']),
					'respondbydate'=>strtotime(@$responseArr['respondByDate']),
					'uid'=>$uid,
					'selleruserid'=>$selleruserid,
					'update_time'=>time(),
			);
	
			/// 发起人  判断  客户是 谁
			if($responseArr['user']['role']=='SELLER'){
				$data['selleruserid']=$responseArr['user']['userId'];
				$data['buyeruserid']=@$responseArr['otherParty']['userId'];
			}else{
				$data['buyeruserid']=$responseArr['user']['userId'];
			}
	
			//状态
			if (isset($responseArr['status'])){
				foreach ($responseArr['status'] as $key=>$val){
					$data['status_type']=$key;
					$data['status_value']=$val;
				}
			}
	
			/**
			 * 系统类型 $mytype
			 *   1  //未付款
			 *   2  //取消订单
			 *   3  //买家未收到货
			 *   4  //描述不符
			 */
			$mytype=0;
			if(in_array($data['type'],array('EBP_INR','INR','PAYPAL_INR' ) )){ //买家未收到货
				$mytype=3;
			}elseif(in_array($data['type'],array('EBP_SNAD','PAYPAL_SNAD','SNAD'))){ //描述不符
				$mytype=4;
			}elseif( $data['type']=='UPI'){
				$mytype=1;
			}elseif($data['type']=='CANCEL_TRANSACTION'){
				$mytype=2;
			}
			$data['mytype']=$mytype;
			
			//找出相关订单
			if(empty($cmEbayUsercaseOne->order_id)){
				$odEbayTransactionOne = OdEbayTransaction::findOne(['itemid'=>$responseArr['item']['itemId'], 'transactionid'=>$responseArr['item']['transactionId']]);
				
				if($odEbayTransactionOne !== null){
					$data['order_id']=$odEbayTransactionOne->order_id;
					$data['order_source_srn']=$odEbayTransactionOne->salesrecordnum;
				}
			}
			if(!isset($data['order_id'])){
				$data['order_id'] = 0;
			}
	
			$cmEbayUsercaseOne->setAttributes($data, 0);
			$cmEbayUsercaseOne->save(false);
			return true;
		} catch (Exception $e) {
			\Yii::error('ebay resolution '.__FUNCTION__.' failure. Exception '.print_r($e,true));
			return $e;
		}
	}
	
	/**
	 * 将通过 api取得的值 保存到数据库中
	 * @author hqw
	 * @date 2015-11-18
	 */
	static function ebayUserCaseEbpdetailApiSave($caseid,$responseArr,$eu){
		try{
			$ebpdetail = CmEbayUsercaseEbpdetail::findOne(['caseid'=>$caseid]);
			
			if(is_null($ebpdetail)){
				$ebpdetail = new CmEbayUsercaseEbpdetail();
			}
			
			$ebpdetail->caseid = $caseid;
			$ebpdetail->agreedrefundamount = isset($responseArr['agreedRefundAmount']) ? $responseArr['agreedRefundAmount'] : '';
			$ebpdetail->appeal = isset($responseArr['appeal']) ? serialize($responseArr['appeal']): '';
			$ebpdetail->buyerreturnshipment = isset($responseArr['buyerReturnShipment']) ? serialize($responseArr['buyerReturnShipment']) : '';
			$ebpdetail->casedocumentinfo = isset($responseArr['caseDocumentInfo']) ? $responseArr['caseDocumentInfo'] : '';
			$ebpdetail->decision = isset($responseArr['decision']) ? $responseArr['decision'] : '';
			$ebpdetail->decisiondate = isset($responseArr['decisionDate']) ? $responseArr['decisionDate'] : '';
			$ebpdetail->decisionreason = isset($responseArr['decisionReason']) ? $responseArr['decisionReason'] : '';
			$ebpdetail->decisionreasondetail = isset($responseArr['decisionReasonDetail']) ? serialize($responseArr['decisionReasonDetail']) : '';
			$ebpdetail->detailstatus = isset($responseArr['detailStatus']) ? $responseArr['detailStatus'] : '';
			$ebpdetail->detailstatusinfo = isset($responseArr['detailStatusInfo']) ? serialize($responseArr['detailStatusInfo']) : '';
			$ebpdetail->fvfcredited = isset($responseArr['FVFCredited']) ? $responseArr['FVFCredited'] : '';
			$ebpdetail->globalid = isset($responseArr['globalId']) ? $responseArr['globalId'] : '';
			$ebpdetail->initialbuyerexpectation = isset($responseArr['initialBuyerExpectation']) ? $responseArr['initialBuyerExpectation'] : '';
			$ebpdetail->initialbuyerexpectationdetail = isset($responseArr['initialBuyerExpectationDetail']) ? serialize($responseArr['initialBuyerExpectationDetail']) : '';
			$ebpdetail->notcountedinbuyerprotectioncases = isset($responseArr['notCountedInBuyerProtectionCases']) ? $responseArr['notCountedInBuyerProtectionCases'] : '';
			$ebpdetail->openreason = isset($responseArr['openReason']) ? $responseArr['openReason'] : '';
			$ebpdetail->paymentdetail = isset($responseArr['paymentDetail']) ? serialize($responseArr['paymentDetail']) : '';
			$ebpdetail->responsehistory = isset($responseArr['responseHistory']) ? serialize($responseArr['responseHistory']) : '';
			$ebpdetail->returnmerchandiseauthorization = isset($responseArr['returnMerchandiseAuthorization']) ? $responseArr['returnMerchandiseAuthorization'] : '';
			$ebpdetail->sellershipment = isset($responseArr['sellerShipment']) ? $responseArr['sellerShipment'] : '';
			
			if (is_null($ebpdetail->create_time) || strlen($ebpdetail->create_time)==0){
				$ebpdetail->create_time = time();
			}
			$ebpdetail->update_time = time();

			if($ebpdetail->save()){
				return true;
			}else{
				\Yii::error(print_r($ebpdetail->getErrors(),true));
			}
			
		} catch (\Exception $e) {
			\Yii::error('ebay resolution '.__FUNCTION__.' failure. get import file info Exception '.print_r($e,true));
			return $e;
		}
	}
	
	
	/**
	 * 保存
	 * @param $disputeArray
	 * @param $selleruserid
	 * @author hqw
	 * @date 2015-11-23
	 */
	static function ebayGetDisputeApiSave($disputeArray,$selleruserid){
		try{
			$dispute = CmEbayDispute::findOne(['disputeid'=>$disputeArray['DisputeID']]);
			
			if($dispute == null){
				$dispute = new CmEbayDispute();
			}
			
			$mytype=0;
			if($disputeArray['DisputeReason']=='BuyerHasNotPaid'){ //长期未付款
				$mytype=1;
			}elseif($disputeArray['DisputeReason']=='TransactionMutuallyCanceled'){ //取消交易
				$mytype=2;
			}elseif($disputeArray['DisputeReason']=='ItemNotReceived'){ //买家未收到货
				$mytype=3;
			}elseif($disputeArray['DisputeReason']=='SignificantlyNotAsDescribed'){ //描述不符
				$mytype=4;
			}
	
			$data=array(
					'mytype'=>$mytype,
					'disputeid'=>$disputeArray['DisputeID'],
					'transactionid'=>$disputeArray['TransactionID'],
					'itemid'=>$disputeArray['Item']['ItemID'],
					'disputereason'=>$disputeArray['DisputeReason'],
					'disputeexplanation'=>@$disputeArray['DisputeExplanation'],
					'selleruserid'=>$selleruserid,
// 					'escalation'=>@$disputeArray['Escalation'],
					'disputerecordtype'=>$disputeArray['DisputeRecordType'],
					'disputestate'=>$disputeArray['DisputeState'],
					'disputestatus'=>$disputeArray['DisputeStatus'],
// 					'purchaseprotection'=>@$disputeArray['PurchaseProtection'],
					'has_read'=>0,
// 					'user_role'=>@$disputeArray['UserRole'],
					'disputecreatedtime'=>strtotime($disputeArray['DisputeCreatedTime']),
					'disputecreatedtime'=>strtotime($disputeArray['DisputeModifiedTime']),
	
			);
			
			if(isset($disputeArray['Escalation'])){
				$data['escalation'] = $disputeArray['Escalation'];
			}
			
			if(isset($disputeArray['PurchaseProtection'])){
				$data['purchaseprotection'] = $disputeArray['PurchaseProtection'];
			}
			
			if(isset($disputeArray['UserRole'])){
				$data['user_role'] = $disputeArray['UserRole'];
			}
	
			if (isset($disputeArray['DisputeMessage']['MessageID'])){
				$disputeArray['DisputeMessage']=array($disputeArray['DisputeMessage']);
			}
			if (isset($disputeArray['DisputeMessage'])){
				$data['disputemessages']=serialize( $disputeArray['DisputeMessage']);
			}
			/// 发起人  判断  客户是 谁
			if(isset($disputeArray['OtherPartyRole'])){
				if($disputeArray['OtherPartyRole']=='Seller'){
					$data['buyeruserid']=$disputeArray['OtherPartyName'];
				}else{
					$data['buyeruserid']=$disputeArray['OtherPartyName'];
				}
			}
			if(isset($disputeArray['BuyerUserID'])){
				$data['buyeruserid']=$disputeArray['BuyerUserID'];
			}
	
			//找出相关订单
			if(empty($dispute->order_id)){
				$odEbayTransactionOne = OdEbayTransaction::findOne(['itemid'=>$disputeArray['Item']['ItemID'], 'transactionid'=>$disputeArray['TransactionID']]);
				
				if($odEbayTransactionOne !== null)
					$data['order_id']=$odEbayTransactionOne->order_id;
			}
			$dispute->setAttributes($data,0);
	
			$dispute->save(false);
	
			//更新transaction中的buyer_dispute ,
			//       		if ($dispute->disputerecordtype!='UnpaidItem'){
			//           		$tr = OdEbayTransaction::model()->where('id=?',array($dispute->ctid))->getOne();
			//           		$tr->buyer_dispute=$dispute->disputerecordtype;
			//           		$tr->save();
			//       		}
			return $dispute;
		} catch (Exception $e) {
			\Yii::error('ebay resolution '.__FUNCTION__.' failure. get import file info Exception '.print_r($e,true));
			return $e;
		}
	}
	
}
?>