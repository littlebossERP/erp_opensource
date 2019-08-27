<?php
namespace common\api\paypalinterface;

use common\api\paypalinterface\PaypalInterface_Abstract;
use eagle\models\QueuePaypaltransaction;
use eagle\models\SaasEbayUser;
use eagle\modules\order\model\OdPaypalTransaction;
use eagle\modules\order\models\OdEbayTransaction;
use eagle\modules\order\models\OdOrder;
use eagle\models\SaasPaypalUser;

class PaypalInterface_GetTransactionDetails extends PaypalInterface_Abstract {
	public $verb='GetTransactionDetails';
	function request($transactionid){
		$arr=array(
			'TRANSACTIONID'=>$transactionid,
		);
		return $this->doNvpRequest($arr);
	}

	/**
	 * 请求一项
	 */
	static function request_one($transactionid,$paypal,$uid,$eorderid=0,$ebay_orderid=null,&$error){
		$api=new self($paypal);
		$r=$api->request($transactionid);
		//Yii::log('paypal request :'.print_r($api->_last_request,1));
		//Yii::log('paypal response :'.print_r($r,1));
		echo "\n v1.4 paypal_request_one tid= $transactionid , pp= $paypal, uid=$uid, eoid=$eorderid, eeid=$ebay_orderid";//test kh
		if (isset($r['detail'][0]['ERRORCODE']) && $r['detail'][0]['ERRORCODE']==10002 ){
			
		}else{
			echo '\n paypal response :'.print_r($r,1);
		}
		
		if($r['ACK']=='Failure'){ //失败时的处理
			if($r['detail'][0]['ERRORCODE']==10002 || $r['detail'][0]['ERRORCODE']==10007){ // 这个paypal 还没有权限 .
				//SaasPaypalUser::model()->updateAll(array('is_active'=>0),'paypal_user=?',array($paypal));
				//没有 授权 的情况下 把状态改为 3 不同步的状态
				$effect  = QueuePaypaltransaction::updateAll(['status'=>3,'error'=>@$r['detail'][0]['LONGMESSAGE'],'updated'=>time()],['ebay_orderid'=>$ebay_orderid , 'eorderid'=>$eorderid]);
				echo "\n effect : $effect  :".@$r['detail'][0]['LONGMESSAGE']."\n";
			}
			echo $r['detail'][0]['SHORTMESSAGE'].'('.$r['detail'][0]['LONGMESSAGE'].')'." 【".$r['detail'][0]['ERRORCODE']."】\n";
			return $r['detail'][0]['LONGMESSAGE'];
		}
		// 查找 myorderid
		$MT = OdEbayTransaction::findOne(['eorderid'=>$eorderid]);
		$order_id=$MT->order_id;
		if (empty($order_id)){
			$orderModel = OdOrder::find()->where(['order_source_order_id'=>$ebay_orderid , 'order_capture'=>'N'])->asArray()->one();
			if (!empty($orderModel['order_id'])){
				$order_id = $orderModel['order_id'];
			}else{
				echo "\n puid=$uid ,eorderid=$eorderid  , ebay_orderid=$ebay_orderid not found order id!";
			}
			
		}
		$PT = OdPaypalTransaction::findOne(['transactionid'=>$transactionid]);
		if(is_null($PT))$PT = new OdPaypalTransaction();
		$PT->uid = $uid;
		$PT->eorderid=$eorderid;
		$PT->ebay_orderid=$ebay_orderid;
		$PT->transactionid=$transactionid;
		$PT->order_id=$order_id;
		$PT->transactiontype=@$r['TRANSACTIONTYPE'];
		$PT->ordertime= strtotime($r['ORDERTIME']);
		$PT->amt=@$r['AMT'];
		$PT->feeamt=@$r['FEEAMT'];
		//			$PT->netamt=@$r['NETAMT'];
		$PT->currencycode=@$r['CURRENCYCODE'];
		$PT->buyerid=@$r['BUYERID'];
		$PT->email=@$r['EMAIL'];
		$PT->receiverbusiness=@$r['RECEIVERBUSINESS'];
		$PT->receiveremail=@$r['RECEIVEREMAIL'];
		$PT->shiptoname=@$r['SHIPTONAME'];
		$PT->shiptostreet=@$r['SHIPTOSTREET'];
		$PT->shiptostreet2=@$r['SHIPTOSTREET2'];
		$PT->shiptocity=@$r['SHIPTOCITY'];
		$PT->shiptostate=@$r['SHIPTOSTATE'];
		$PT->shiptocountrycode=@$r['SHIPTOCOUNTRYCODE'];
		$PT->shiptocountryname=@$r['SHIPTOCOUNTRYNAME'];
		$PT->shiptozip=@$r['SHIPTOZIP'];
		$PT->addressowner=@$r['ADDRESSOWNER'];
		/*
		$PT->setAttributes(array(
			'uid'=>$uid,
			'eorderid'=>$eorderid,
			'ebay_orderid'=>$ebay_orderid,
			'transactionid'=>$transactionid,
			'order_id'=>$order_id,
			'transactiontype'=>@$r['TRANSACTIONTYPE'],
			'ordertime'=> strtotime($r['ORDERTIME']),
			'amt'=>@$r['AMT'],
			'feeamt'=>@$r['FEEAMT'],
//			'netamt'=>@$r['NETAMT'],
			'currencycode'=>@$r['CURRENCYCODE'],
			'buyerid'=>@$r['BUYERID'],
			'email'=>@$r['EMAIL'],
			'receiverbusiness'=>@$r['RECEIVERBUSINESS'],
			'receiveremail'=>@$r['RECEIVEREMAIL'],
			'shiptoname'=>@$r['SHIPTONAME'],
			'shiptostreet'=>@$r['SHIPTOSTREET'],
			'shiptostreet2'=>@$r['SHIPTOSTREET2'],
			'shiptocity'=>@$r['SHIPTOCITY'],
			'shiptostate'=>@$r['SHIPTOSTATE'],
			'shiptocountrycode'=>@$r['SHIPTOCOUNTRYCODE'],
//			'shiptocountryname'=>@$r['SHIPTOCOUNTRYNAME'],
			'shiptozip'=>@$r['SHIPTOZIP'],
			'addressowner'=>@$r['ADDRESSOWNER'],

		),false);
*/
		try {
			if ($PT->save(false)){
				//根据paypal设置上的开头决定是否更新paypal地址
				if (strlen($PT->shiptoname) && strlen($PT->shiptostreet)){
					$PPaccount = SaasPaypalUser::findOne(['paypal_user'=>$paypal]);
					if (!empty($PPaccount)){
						if ($PPaccount->overwrite_ebay_consignee_address == 'Y'){
							$orderModel = OdOrder::findOne(['order_source_order_id'=>$ebay_orderid]);
							if (!empty($orderModel)){
								
								if ($orderModel->order_verify != 'verified'){
									$paypalAddress = [
									'consignee'=>$PT->shiptoname,
									'consignee_email'=>$PT->email,
									'consignee_country'=>empty($PT->shiptocountryname)?$PT->shiptocountrycode:$PT->shiptocountryname,
									'consignee_country_code'=>$PT->shiptocountrycode,
									'consignee_province'=>$PT->shiptostate,
									'consignee_city'=>$PT->shiptocity,
									'consignee_address_line1'=>$PT->shiptostreet,
									'consignee_address_line2'=>$PT->shiptostreet2,
									'consignee_postal_code'=>$PT->shiptozip,
									'order_verify'=>'verified',
									];
									$updateRt = \eagle\modules\order\helpers\OrderUpdateHelper::updateOrder($orderModel, $paypalAddress , false , 'System','paypal地址同步','order');
									echo "\n uid=$uid update order address , orderid = $ebay_orderid  resulst=".print_r($updateRt,1);
								}else{
									echo "\n uid=$uid not update order address , orderid = $ebay_orderid  due to order_verify =".$orderModel->order_verify ;
								}
								
							}else{
							    echo "\n uid=$uid orderid = $ebay_orderid  order not exist  , fail to update ebay address !";
							}
						}else{
						    echo "\n uid=$uid orderid = $ebay_orderid  don't replace ebay address !";
						}
					}else{
						echo "\n uid=$uid orderid = $ebay_orderid  paypal account $paypal is not exist !";
					}
				}else{
					echo " shiptoname:".$PT->shiptoname." shiptostreet:".$PT->shiptostreet;
					return false;
				}
			
			}else{
				echo "\n uid=$uid orderid = $ebay_orderid  paypal account $paypal  error message:".print_r($PT->getErrors(),true);
				return false;
			}
		} catch (\Exception $e) {
			echo "\n uid=$uid orderid = $ebay_orderid  paypal account $paypal  error message:".$e->getMessage()." line no=".$e->getLine();
		}
		 
		return true;
	}
	 
	/**
	 +---------------------------------------------------------------------------------------------
	 * 封装同步paypal 信息
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2017/4/26				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static private  function _sync_paypalInfo($temArr){
		foreach($temArr as $k => $v){
			$eu = SaasEbayUser::findOne(['selleruserid'=>$k]);
			if (empty($eu)){
				//没有 授权 的情况下 把状态改为 4不同步的状态
				$effect = QueuePaypaltransaction::updateAll(['status'=>4,'error'=>'account:'.$k.'is not found '."\n",'updated'=>time()],['selleruserid'=>$k]);
				echo 'account:'.$k.'is not found '."effect=".$effect."\n";
				continue;
			}
		 
			foreach ($v as $R) {
				try{
					QueuePaypaltransaction::updateAll(['status'=>1,'updated'=>time()],'qid='.$R['qid']);
					$paypal_business=null;
		
					//paypal transaction 是否存在
					$PT = OdPaypalTransaction::findOne(['transactionid'=>$R['externaltransactionid']]);
					if((!is_null($PT)) && strlen($PT->amt) && strlen($PT->receiverbusiness) ){ //完成
						$paypal_business=$PT->receiverbusiness;
						//QueuePaypaltransaction::meta()->deleteWhere('qid=?',$R['qid']);
						//continue;
					}
					if(empty($paypal_business)){
						//通过 Tansaction 确定卖家 paypal
						$MTs = OdEbayTransaction::findAll(['orderid'=>$R['ebay_orderid']]);
						$paypal_business='';
						foreach($MTs as $MT){
							if($MT->paypalemailaddress){
								$paypal_business=$MT->paypalemailaddress;
								break 1;
							}
						}
					}
					//去取
					$r=PaypalInterface_GetTransactionDetails::request_one($R['externaltransactionid'],$paypal_business,$eu->uid,$R['eorderid'],$R['ebay_orderid'],$error);
					if($r===true) {
						$effect =QueuePaypaltransaction::deleteAll(['qid'=>$R['qid']]);
						//$effect = QueuePaypaltransaction::updateAll(['status'=>4,'updated'=>time()],['qid'=>$R['qid']]);
						echo "\n ".$R['qid']." delete  effect=".$effect." \n";
					} else {
						if (trim($r) == "You do not have permissions to make this API call"){
							//没有 授权 的情况下 把状态改为 3 不同步的状态
							$effect = QueuePaypaltransaction::updateAll(['status'=>3,'error'=>$r,'updated'=>time()],['qid'=>$R['qid']]);
							echo "\n ".$R['qid']." update status =3  effect=".$effect." \n";
						}else{
							throw new \Exception($r);
						}
					}
				}catch(\Exception $ex){
					$error=$ex->getMessage();
					echo $error . " line no ".$ex->getLine();
		
					if (trim($error) == "You do not have permissions to make this API call"){
						$status = 3;
					}else{
						$status = 0;
					}
					$effect = QueuePaypaltransaction::updateAll(['status'=>$status,'updated'=>time(),'error'=>$error],'qid='.$R['qid']);
					echo "\n exception ".$R['qid']." effect = $effect";
				}
			}
		}
	}

	/**
	 +---------------------------------------------------------------------------------------------
	 * 根据原来 的逻辑改造出来的同步 指定puid 的paypal 信息
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param     $puid			int 	
	 +---------------------------------------------------------------------------------------------
	 * @return						int 
	 *
	 * @invoking					PaypalInterface_GetTransactionDetails::CronProcessQueueByPuid(1);
	 *
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2017/4/26				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function CronProcessQueueByPuid($puid=null){
		echo "\n ".(__function__)." v1.0 ";
		$sql =" select a.* ,  b.uid from queue_paypaltransaction a , saas_ebay_user b  where a.selleruserid = b.selleruserid and status =0 and ifnull(error,'')='' ";
		if (!empty($puid)){
			$sql .=" and b.uid = ".$puid;
		}
		
		$sql .= " order by updated asc limit 100" ;
		
		$rows = \Yii::$app->db->createCommand($sql)->queryAll();
		$temArr = [];
		foreach($rows as $v){
			$temArr[$v['selleruserid']][] = $v;
		}
		
		if (!empty($temArr)){
			self::_sync_paypalInfo($temArr);
		}
		
		
		return count($rows);
	}//end of function CronProcessQueueByPuid
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 同步paypal地址专用后台job
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param     $puid			int
	 +---------------------------------------------------------------------------------------------
	 * @return						int
	 *
	 * @invoking					PaypalInterface_GetTransactionDetails::CronProcessQueueByPuid(1);
	 *
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2017/4/26				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function CronSyncPaypalAddress($sub_id=''){
		echo "\n ".(__function__)." v1.0 ";
		$sub_id=''; // TODO 需要开多进程拉取再把这个去掉
		if ($sub_id !==""){
			//多进程
			$totalJob = 1;
			if ($totalJob<=$sub_id){
				echo "\n sub id(".$sub_id.") must < total job number(".$totalJob.")";
				return;
			}
			$coreStr = ' and puid %'.$totalJob.'= '.$sub_id;
		}else{
			//单进程
			$coreStr = '';
		}
		
		$sql =" SELECT puid  FROM `saas_paypal_user` where overwrite_ebay_consignee_address = 'Y'  $coreStr  group by puid ";
	
		$rows = \Yii::$app->db->createCommand($sql)->queryAll();
		$total = count($rows);
		$i = 0;
		$result = 0;
		foreach($rows as $v){
			$i++;
			if (!empty($v['puid'])){
				$number = self::CronProcessQueueByPuid($v['puid']);
				$result += $number;
				echo "\n puid=".$v['puid']."  total=".$total." current = $i , effect=$number";
			}else{
				echo "\n puid is empty ".print_r($v,true);
			}
		}
		return $result;
	}//end of function CronSyncPaypalAddress
	
	
	
	

	/**
	 * 处理
	 */
	static function CronProcessQueue($selleruserid=null){
		$error = '';
		$sqlStr = $selleruserid ? " And selleruserid='{$selleruserid}'" : '';
		$rows = array();
		$rows = QueuePaypaltransaction::find()->where(" error IS NULL OR error = ''".$sqlStr)->all();
		$sqlStr .= " ) AND error <>'You do not have permissions to make this API call'";
		if(empty($rows)|| count($rows) < 200) {
			$rows += QueuePaypaltransaction::find()->where('(`status` = 1 AND `updated` < '.(time() - 300).$sqlStr)->all();
		}
		if(empty($rows) || count($rows) < 200) {
			$rows += QueuePaypaltransaction::find()->where(" (status = 0 OR status =2 ".$sqlStr)->all();
		}
		$temArr =array();
		foreach($rows as $v){
			$temArr[$v['selleruserid']][] = $v;
		}
		
		if (!empty($temArr)){
			self::_sync_paypalInfo($temArr);
		}
		
		/*
		foreach($temArr as $k => $v){
			$eu = SaasEbayUser::findOne(['selleruserid'=>$k]);
			if (empty($eu)){
				//没有 授权 的情况下 把状态改为 4不同步的状态
				$effect = QueuePaypaltransaction::updateAll(['status'=>4,'error'=>'account:'.$k.'is not found '."\n",'updated'=>time()],['selleruserid'=>$k]);
				echo 'account:'.$k.'is not found '."effect=".$effect."\n";
				continue;
			}
			 
			foreach ($v as $R) {
				try{
					QueuePaypaltransaction::updateAll(['status'=>1,'updated'=>time()],'qid='.$R['qid']);
					$paypal_business=null;
				
					//paypal transaction 是否存在
					$PT = OdPaypalTransaction::findOne(['transactionid'=>$R['externaltransactionid']]);
					if((!is_null($PT)) && strlen($PT->amt) && strlen($PT->receiverbusiness) ){ //完成
						$paypal_business=$PT->receiverbusiness;
						//QueuePaypaltransaction::meta()->deleteWhere('qid=?',$R['qid']);
						//continue;
					}
					if(empty($paypal_business)){
						//通过 Tansaction 确定卖家 paypal
						$MTs = OdEbayTransaction::findAll(['orderid'=>$R['ebay_orderid']]);
						$paypal_business='';
						foreach($MTs as $MT){
							if($MT->paypalemailaddress){
								$paypal_business=$MT->paypalemailaddress;
								break 1;
							}
						}
					}
					//去取
					$r=PaypalInterface_GetTransactionDetails::request_one($R['externaltransactionid'],$paypal_business,$eu->uid,$R['eorderid'],$R['ebay_orderid'],$error);
					if($r===true) {
						$effect =QueuePaypaltransaction::deleteAll(['qid'=>$R['qid']]);
						//$effect = QueuePaypaltransaction::updateAll(['status'=>4,'updated'=>time()],['qid'=>$R['qid']]);
						echo "\n ".$R['qid']." delete  effect=".$effect." \n";
					} else {
						if (trim($r) == "You do not have permissions to make this API call"){
							//没有 授权 的情况下 把状态改为 3 不同步的状态
							$effect = QueuePaypaltransaction::updateAll(['status'=>3,'error'=>$r,'updated'=>time()],['qid'=>$R['qid']]);
							echo "\n ".$R['qid']." update status =3  effect=".$effect." \n";
						}else{
							throw new \Exception($r);
						}
					}
				}catch(\Exception $ex){
					$error=$ex->getMessage();
					echo $error . " line no ".$ex->getLine();
					
					if (trim($error) == "You do not have permissions to make this API call"){
						$status = 3;
					}else{
						$status = 0;
					}
					$effect = QueuePaypaltransaction::updateAll(['status'=>$status,'updated'=>time(),'error'=>$error],'qid='.$R['qid']);
					echo "\n exception ".$R['qid']." effect = $effect";
				}
			}
		}
		*/
		return count($rows);
		// 完成后 清除 过期的
		//QueuePaypaltransaction::model()->deleteWhere('created<?',time()-86400*2);
	}
	
	/**
	 * 绑定paypal账号时立即测试该账号是否已经授权了小老板
	 * @param 	string 	$transactionid
	 * @param 	string 	$paypal_user
	 * @author	lzhl	2016/11/13		初始化
	 */
	static function test_request($transactionid,$paypal_user){
		$api=new self($paypal_user);
		$r=$api->request($transactionid);
		
		if($r['ACK']=='Failure'){ //失败时的处理
			return $r['detail'][0]['LONGMESSAGE'];
		}else 
			return true;
	}
}
