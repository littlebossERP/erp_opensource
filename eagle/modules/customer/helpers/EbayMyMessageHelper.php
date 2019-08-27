<?php
namespace eagle\modules\customer\helpers;

use eagle\modules\customer\models\EbayMymessage;
use eagle\modules\customer\models\EbayMymessageDetail;
use Exception;
/**
 +------------------------------------------------------------------------------
 * 客服站内信模块业务逻辑类
 +------------------------------------------------------------------------------
 * @category	Customer
 * @subpackage  Exception
 * @version		2.0
 * @author fanjs
 +------------------------------------------------------------------------------
 */
class EbayMyMessageHelper {
	/**
	 * 从 Api请求数据并 保存
	 * @param unknown_type $disputeArray
	 * @param unknown_type $selleruserid
	 * @author fanjs
	 * @date 2015-07-06
	 */
	static function apiSave($responseArr,$eu){
		try{
			$MCEM = EbayMymessage::findOne(['messageid'=>$responseArr['MessageID']]);
			//echo __LINE__; var_dump($MCEM);
			$MCEMD=EbayMymessageDetail::findOne(['messageid'=>$responseArr['MessageID']]);
			//echo __LINE__; var_dump($MCEMD);
	
			if ( !empty($MCEM) && !empty($MCEMD)){
				return ;
			}
			if (empty($MCEM)){$MCEM = new EbayMymessage();}
			if (empty($MCEMD)){$MCEMD = new EbayMymessageDetail();}
			if($responseArr['Sender']=='eBay'){
				$from_who = "eBay";
			}else{
				$from_who = "Members";
			}
			/*
			 if (strlen($responseArr['ExternalMessageID'])){
			$responseArr['ExternalMessageID']=$responseArr['ExternalMessageID']!=0?$responseArr['ExternalMessageID']:intval($responseArr['ExternalMessageID']);
			}
			*/
			$MCEM->setAttributes(array(
					'uid'=>$eu->uid,
					'ebay_uid'=>$eu->ebay_uid,
					'expirationdate'=>strtotime(@$responseArr['ExpirationDate']),
					'listingstatus'=>@$responseArr['ListingStatus'],
					'externalmessageid'=>@$responseArr['ExternalMessageID'],
					'messageid'=>@$responseArr['MessageID'],
					'itemid'=>@$responseArr['ItemID'],
					'messagetype'=>@$responseArr['MessageType'],
					'questiontype'=>@$responseArr['QuestionType'],
					'receivedate'=>@strtotime($responseArr['ReceiveDate']),
					'recipientuserid'=>@$responseArr['RecipientUserID'],
					'sender'=>@$responseArr['Sender'],
					'responseenabled'=>@$responseArr['ResponseDetails']['ResponseEnabled'],
					'subject'=>@$responseArr['Subject'],
					'from_who' =>$from_who,
					'highpriority'=>@$responseArr['HighPriority'],
					'is_read'=>$responseArr['Read']=='true'?1:0,
					'is_flagged'=>$responseArr['Flagged']=='true'?1:0,
					'replied'=>$responseArr['Replied']=='true'?1:0,
			),false);
			$MCEMD->setAttributes(array(
					'messageid'=>@$responseArr['MessageID'],
					'responseurl'=>@$responseArr['ResponseDetails']['ResponseURL'],
					'text'=>@$responseArr['Text']
			),false);
			$MCEM->save(false);
			$MCEMD->save(false);
		} catch (Exception $e) {
			\Yii::info(["Customer",__CLASS__,__FUNCTION__,"Background","保存EbayMyMessage失败".print_r($e->getMessage())],"edb\global");
			return $e;
		}
		 
	}
}