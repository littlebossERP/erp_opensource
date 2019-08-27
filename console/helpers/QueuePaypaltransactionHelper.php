<?php
namespace console\helpers;

use eagle\models\QueuePaypaltransaction;

use yii\base\Exception;

class QueuePaypaltransactionHelper
{
	/**
	 * 添加
	 * @param unknown_type $orderid
	 * @param unknown_type $ebay_orderid   
	 * @param unknown_type $selleruserid
	 * @param unknown_type $externaltransactionid
	 * @param unknown_type $itemids
	 */
	static function Add($eorderid,$ebay_orderid,$selleruserid,$externaltransactionid,$itemids=null){
		$M = QueuePaypaltransaction::find()->where( 'externaltransactionid=:p', array (
				':p' => $externaltransactionid 
		))->one();
		if (is_null($M)){
			if (is_array ( $itemids )) {
				$itemids = implode ( ',', $itemids );
			}
			
			$M = new QueuePaypaltransaction ();
			$M->eorderid = $eorderid;
			$M->ebay_orderid = $ebay_orderid;
			$M->selleruserid = $selleruserid;
			$M->externaltransactionid = $externaltransactionid;
			$M->itemids = $itemids;
			$M->created = time();
			$M->updated = time();
			$M->save ();
			try {
				echo "\n add paypal queue eorderid:".@$M->eorderid." ebay_orderid:".@$M->ebay_orderid." selleruserid:".@$M->selleruserid." externaltransactionid:".@$M->externaltransactionid." itemids:".@$M->itemids;
			} catch (Exception $e) {
				echo (__function__)."error message:".$e->getMessage()." line no:".$e->getLine();
			}
			
		} else {
			if ($M ['status'] != 0) {
				$M->status = 0;
				$M->save ();
// 				QueuePaypaltransaction::model ()->updateAll ( array (
// 						'status' => 0 
// 				), 'qid =?', $M ['qid'] );
			}
		}
	}
	

}
