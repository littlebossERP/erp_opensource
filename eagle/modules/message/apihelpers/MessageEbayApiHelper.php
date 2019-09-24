<?php
namespace eagle\modules\message\apihelpers;

use yii;
use common\api\ebayinterface\addmembermessageaaqtopartner;
use eagle\models\SaasEbayUser;
use eagle\modules\order\models\OdOrder;
use eagle\modules\order\models\OdEbayTransaction;
use eagle\modules\tracking\helpers\phpQuery;
use eagle\modules\message\models\TicketSession;
use eagle\modules\listing\models\EbayItem;
use eagle\modules\order\models\OdOrderItem;
use eagle\modules\message\helpers\SaasMessageAutoSyncApiHelper;
use common\api\ebayinterface\addmembermessagertq;
use eagle\modules\util\helpers\TimeUtil;

class MessageEbayApiHelper{
/**
 * 
 * @param unknown $sellerid seller账号
 * @param unknown $orderid 进行站内信交互的对应item的ID
 * @param unknown $body 站内信的主题内容
 * @param $questiontype 站内信的类型:'General','MultipleItemShipping','Payment','Shipping'
 * (in/out) General questions about the item.
MultipleItemShipping
(in/out) Questions related to the shipping of this item bundled with other items also purchased on eBay.
Payment
(in/out) Questions related to the payment for the item.
Shipping
(in/out) Questions related to the shipping of the item.
 * @param $buyerid 买家的ebay账号
 * @param $subject站内信的标题
 * @param $emailtoseller 是否将该次事件发送email到卖家账号，默认为否
 * @return [
 * 	'Ack':Success,Warning 表示成功,Failure:呼叫失败 
 *  'Errors':呼叫失败时报的错误信息
 *  	 LongMessage:错误长信息
 *   	 ShortMessage:错误短信息
 * ]
 * 如果只有一组错误信息,报错的格式是Errors['LongMessage'=>'','ShortMessage'=>];
 * *如果有多组错误信息,报错的格式是Errors['0'=>['LongMessage'=>'','ShortMessage'=>],'1'=>['LongMessage'=>'','ShortMessage'=>]];
 */
	public static function addMessage($sellerid,$orderid,$body,$questiontype,$subject,$emailtoseller=0){
		$selleruserid = SaasEbayUser::find()->where(['selleruserid'=>$sellerid])->one();
		if (empty($selleruserid)){
			return [
				'Ack'=>'Failure',
				'Errors'=>['LongMessage'=>'SellerID not found!','ShortMessage'=>'SellerID not found!']
			];
		}
		$order = OdOrder::findOne(['order_source_order_id'=>$orderid]);
		if (empty($order)){
			return [
				'Ack'=>'Failure',
				'Errors'=>['LongMessage'=>'Order not found!','ShortMessage'=>'Order not found!']
			];
		}
		if (count($order->items)==0){
			return [
				'Ack'=>'Failure',
				'Errors'=>['LongMessage'=>'Item not found!','ShortMessage'=>'Item not found!']
			];
		}
		$item = $order->items['0'];
		if ($item->order_source_order_item_id<=0){
			return [
				'Ack'=>'Failure',
				'Errors'=>['LongMessage'=>'Transaction not found!','ShortMessage'=>'Transaction not found!']
			];
		}
		$transaction = OdEbayTransaction::findOne($item->order_source_order_item_id);
		if (empty($transaction)){
			return [
				'Ack'=>'Failure',
				'Errors'=>['LongMessage'=>'Transaction not found!','ShortMessage'=>'Transaction not found!']
			];
		}
		$api = new addmembermessageaaqtopartner();
		$api->resetConfig($selleruserid->DevAcccountID);
		$result = $api->api($selleruserid->token, $transaction->itemid, $body, $questiontype, $order->source_buyer_user_id, $subject,$emailtoseller);
		return $result;
	}
	
	/**
	 *
	 * @param string $sellerid seller账号
	 * @param string $buyerNickname 买家的ebay账号
	 * @param int $itemId 进行站内信交互的对应item的ID
	 * @param string $body 站内信的主题内容
	 * @param string $subject站内信的标题
	 * @param boolean $emailtoseller 是否将该次事件发送email到卖家账号，默认为否
	 * @return [
	 * 	'Ack':Success,Warning 表示成功,Failure:呼叫失败
	 *  'Errors':呼叫失败时报的错误信息
	 * ]
	 */
	public static function addMessage2($sellerid,$buyerNickname,$itemId,$body,$subject,$emailtoseller=0){
		$questiontype="General";
		
		$selleruserid = SaasEbayUser::find()->where(['selleruserid'=>$sellerid])->one();
		if (empty($selleruserid)){
			return [
			'Ack'=>'Failure',
			'Error'=>['LongMessage'=>'SellerID not found!','ShortMessage'=>'SellerID not found!']
			];
		}
		
		if (!isset($itemId)){
			return [
			'Ack'=>'Failure',
			'Errors'=>['LongMessage'=>'Item not found!','ShortMessage'=>'Item not found!']
			];
		}
		
		$api = new addmembermessageaaqtopartner();
		$api->resetConfig($selleruserid->DevAcccountID);
		$result = $api->api($selleruserid->token , $itemId , $body , $questiontype , $buyerNickname , $subject , $emailtoseller);
		return $result;
	}
	
	/**
	 *
	 * @param string $sellerid seller账号
	 * @param string $buyerNickname 买家的ebay账号
	 * @param int $itemId 进行站内信交互的对应item的ID
	 * @param string $body 站内信的主题内容
	 * @param string $parentMessageId 收到站内信的messageid
	 * @param boolean $emailtoseller 是否将该次事件发送email到卖家账号，默认为否
	 * @return [
	 * 	'Ack':Success,Warning 表示成功,Failure:呼叫失败
	 *  'Errors':呼叫失败时报的错误信息
	 * ]
	 */
	public static function responseMessage($sellerid,$buyerNickname,$itemid=0,$body,$parentMessageId,$emailtoseller=0){
	
		$selleruserid = SaasEbayUser::find()->where(['selleruserid'=>$sellerid])->one();
		if (empty($selleruserid)){
			return [
					'Ack'=>'Failure',
					'Error'=>['LongMessage'=>'SellerID not found!','ShortMessage'=>'SellerID not found!']
					];
		}
	
		$api = new addmembermessagertq();
		$api->resetConfig($selleruserid->DevAcccountID);
		$result = $api->api($selleruserid->token,$parentMessageId,$buyerNickname,$body,$itemid,$emailtoseller );
		return $result;
	}
	
	/**
	 * 调用ebay接口获取 站内信详细信息并保存在user级别的数据库中
	 *
	 * log			name	date					note
	 * @author		dzt 	2015/07/21				初始化
	 * @author		hqw 	2016/03/01				修改
	 *
	 * @param int $uid	//小老板平台uid
	 * @param string $selleruserid	//ebay上卖家id
	 * @param timestamp $start_time	//拉取开始时间
	 * @param timestamp $end_time	//拉取结束时间
	 *
	 * @return array('success'=>true,'error'=>'');
	 */
	public static function getMessages($uid, $selleruserid, $start_time, $end_time){
		$eu = SaasEbayUser::find()->where(['selleruserid'=>$selleruserid])->one();
		if (empty($eu)){
			echo 'no selleruserid'.$selleruserid.'!';
			return array('success'=>false,'error'=>'no selleruserid:'.$selleruserid.'!');
		}
	
		$flag = false;
		$messageids = array();
		$messageIdListInfoMap = array();
	
		try{
			 
			$final_end_time = $end_time;
			// 由于ebay 第一次抓取30 日站内信比较慢 而且占资源 ，所以这里将请求时间间隔大于一天的都按一天来拉取
				
			do{
				if(($end_time - $start_time) > 86400){
					$end_time = $start_time + 86400;
				}
	
				//是否首次同步同步间隔不同
				echo "\n++++++++++++++++++++\n[".date('Ymd His')."]beginCron\nselleruserid:".$selleruserid."\nfrom:".date('Ymd His', $start_time).'- to:'.date('Ymd His', $end_time)."\n";
	
				$api = new \common\api\ebayinterface\getmymessages();
				$api->resetConfig($eu->DevAcccountID);
				$api->eBayAuthToken = $eu->token;
				$api->StartTime = $start_time;
				$api->EndTime = $end_time;
				$api->DetailLevel = 'ReturnHeaders';
				$api->EntriesPerPage = 50;
				$api->PageNumber = 1;
	
				do{
					$responseArr=$api->api();
	
					if($api->responseIsFailure()){  //接口失败 退出
						return array('success'=>false,'error'=>"ebay api responseIsFailure.".(empty($responseArr['Errors']['ShortMessage']) ? '' : $responseArr['Errors']['ShortMessage']));
					}
	
					//保存 全部的message,只是 检查messageid 信息. api只有一条信息返回时结构不同
					if (isset($responseArr['Messages']['Message']['MessageID'])){
						$responseArr['Messages']['Message']=array($responseArr['Messages']['Message']);
					}
	
					//没有条目
					if(!isset($responseArr['Messages']['Message'])) {
						$flag = true;
						break;
					}
	
					foreach ($responseArr['Messages']['Message'] as $msgNode){
						array_push($messageids,$msgNode['MessageID']);
						$messageIdListInfoMap[$msgNode['MessageID']] = $msgNode;
					}
	
					$api->PageNumber++;
				}while(1);
	
				if($flag){
					echo "ready to sync messages:".count($messageids)."\n";
	
					//需要新同步进来的 message
					foreach ($messageids as $messageid){
						// api获取单条信息 并写入 ebay的message表里
						echo "$selleruserid get message : $messageid ".date('Y-m-d H:i:s')."\n";
						$resMsgDetails = \common\api\ebayinterface\getmymessages::cronRequestOne2($eu,$messageid);
							
						if ($resMsgDetails['success'] === false){
						\Yii::error('message:'.",".__CLASS__.",".__FUNCTION__.json_encode($messageIdListInfoMap[$messageid]).",selleruserid:".$selleruserid ,"file");
	
								$errorMessage = $resMsgDetails['error_message'];
							return array('success'=>false,'error'=>$errorMessage);
						}
	
						$resultInsert = self::ebayMsgToEagleMsg($messageIdListInfoMap[$messageid], $resMsgDetails['return_messages'], $uid, $selleruserid);
						if ($resultInsert['success']===false){
							$errorMessage = $resultInsert['error'];
							return array('success'=>false,'error'=>$errorMessage);
						}
							
						if (!empty($resultInsert['touched_session_id']))
							$touched_session_ids[''.$resultInsert['touched_session_id']] = $resultInsert['touched_session_id'];
	
						if (!empty($resultInsert['touched_customer_id']))
							$touched_customer_ids[''.$resultInsert['touched_customer_id']] = $resultInsert['touched_customer_id'];
					}
				}
	
				$result = SaasMessageAutoSyncApiHelper::updateSaasMsgAutosyncRunTime($uid,$selleruserid,'msgTime',$start_time,$end_time);
	
				// for 下一次循环
				$start_time = $end_time;
				$end_time = $final_end_time;
	
				//因为是循环执行，所以需要重新初始化以下变量
				$flag = false;
				unset($messageids);
				unset($messageIdListInfoMap);
				$messageids = array();
				$messageIdListInfoMap = array();
	
			}while ($final_end_time > $start_time);
		}catch(\Exception $ex){
			echo 'Error Message: file:'.$ex->getFile()." line:".$ex->getLine()." ".$ex->getMessage()."\n";
			return array('success'=>false,'error'=> 'Error Message:'.print_r($ex,true)."\n");
		}
	
		return array('success'=>true , 'error'=>'','touched_session_ids'=>$touched_session_ids,'touched_customer_ids'=>$touched_customer_ids);
	}
	
	
	

	/**
	 * 调用ebay接口获取 站内信详细信息并保存在user级别的数据库中
	 *
	 * log			name	date					note
	 * @author		lzhl 	2017/09/01				初始化
	 *
	 * @param int $uid	//小老板平台uid
	 * @param string $selleruserid	//ebay上卖家id
	 * @param timestamp $start_time	//拉取开始时间
	 * @param timestamp $end_time	//拉取结束时间
	 *
	 * @return array('success'=>true,'error'=>'');
	 */
	public static function getMessagesSent($uid, $selleruserid, $start_time, $end_time){
		$eu = SaasEbayUser::find()->where(['selleruserid'=>$selleruserid])->one();
		if (empty($eu)){
			echo 'no selleruserid'.$selleruserid.'!';
			return array('success'=>false,'error'=>'no selleruserid:'.$selleruserid.'!');
		}
	
		$flag = false;
		$messageids = array();
		$messageIdListInfoMap = array();
	
		try{
	
			$final_end_time = $end_time;
			// 由于ebay 第一次抓取30 日站内信比较慢 而且占资源 ，所以这里将请求时间间隔大于一天的都按一天来拉取
	
			do{
				if(($end_time - $start_time) > 86400){
					$end_time = $start_time + 86400;
				}
	
				//是否首次同步同步间隔不同
				echo "\n++++++++++++++++++++\n[".date('Ymd His')."]beginCron\nselleruserid:".$selleruserid."\nfrom:".date('Ymd His', $start_time).'- to:'.date('Ymd His', $end_time)."\n";
	
				$api = new \common\api\ebayinterface\getmymessages();
				$api->resetConfig($eu->DevAcccountID);
				$api->eBayAuthToken = $eu->token;
				$api->StartTime = $start_time;
				$api->EndTime = $end_time;
				$api->DetailLevel = 'ReturnHeaders';
				$api->EntriesPerPage = 50;
				$api->PageNumber = 1;
				$api->FolderID = 1;
	
				do{
					$responseArr=$api->api();
	
					if($api->responseIsFailure()){  //接口失败 退出
						return array('success'=>false,'error'=>"ebay api responseIsFailure.".(empty($responseArr['Errors']['ShortMessage']) ? '' : $responseArr['Errors']['ShortMessage']));
					}
	
					//保存 全部的message,只是 检查messageid 信息. api只有一条信息返回时结构不同
					if (isset($responseArr['Messages']['Message']['MessageID'])){
						$responseArr['Messages']['Message']=array($responseArr['Messages']['Message']);
					}
	
					//没有条目
					if(!isset($responseArr['Messages']['Message'])) {
						$flag = true;
						break;
					}
	
					foreach ($responseArr['Messages']['Message'] as $msgNode){
						array_push($messageids,$msgNode['MessageID']);
						$messageIdListInfoMap[$msgNode['MessageID']] = $msgNode;
					}
	
					$api->PageNumber++;
				}while(1);
	
				if($flag){
					echo "ready to sync messages:".count($messageids)."\n";
	
					//需要新同步进来的 message
					foreach ($messageids as $messageid){
						// api获取单条信息 并写入 ebay的message表里
						echo "$selleruserid get message : $messageid ".date('Y-m-d H:i:s')."\n";
						$resMsgDetails = \common\api\ebayinterface\getmymessages::cronRequestOne2($eu,$messageid,1);
							
						if ($resMsgDetails['success'] === false){
						\Yii::error('message:'.",".__CLASS__.",".__FUNCTION__.json_encode($messageIdListInfoMap[$messageid]).",selleruserid:".$selleruserid ,"file");
	
						$errorMessage = $resMsgDetails['error_message'];
								return array('success'=>false,'error'=>$errorMessage);
						}
	
						$resultInsert = self::ebayMsgToEagleMsg($messageIdListInfoMap[$messageid], $resMsgDetails['return_messages'], $uid, $selleruserid);
						if ($resultInsert['success']===false){
						$errorMessage = $resultInsert['error'];
						return array('success'=>false,'error'=>$errorMessage);
						}
							
						if (!empty($resultInsert['touched_session_id']))
							$touched_session_ids[''.$resultInsert['touched_session_id']] = $resultInsert['touched_session_id'];
	
						if (!empty($resultInsert['touched_customer_id']))
							$touched_customer_ids[''.$resultInsert['touched_customer_id']] = $resultInsert['touched_customer_id'];
					}
				}
	
					$result = SaasMessageAutoSyncApiHelper::updateSaasMsgAutosyncRunTime($uid,$selleruserid,'msgTime',$start_time,$end_time);
	
					// for 下一次循环
					$start_time = $end_time;
					$end_time = $final_end_time;
	
					//因为是循环执行，所以需要重新初始化以下变量
					$flag = false;
					unset($messageids);
					unset($messageIdListInfoMap);
					$messageids = array();
					$messageIdListInfoMap = array();
	
			}while ($final_end_time > $start_time);
		}catch(\Exception $ex){
					echo 'Error Message: file:'.$ex->getFile()." line:".$ex->getLine()." ".$ex->getMessage()."\n";
					return array('success'=>false,'error'=> 'Error Message:'.print_r($ex,true)."\n");
					}
	
					return array('success'=>true , 'error'=>'','touched_session_ids'=>$touched_session_ids,'touched_customer_ids'=>$touched_customer_ids);
	}
	
	
	
	/**
	 * 获取信息内容 。@todo 一旦发现内容为空时，应先测试 api 返回的html 通过phpQuery 下面的 选择器能否选出内容。
	 * $msgDetails //获取平台的原始detail数据
	 * @return string
	 */
	public static function extractApiMessageContent($msgDetails){
		phpQuery::newDocument($msgDetails['Text']);
		$msgDetailsContent = '';
		if(in_array($msgDetails['Sender'], self::$ebaySenders)){
			if(phpQuery::pq('table[width="600"]')->length() != 0){
				$tables = phpQuery::pq('table[width="600"]');
				if ($tables->length() == 3){
					$msgDetailsContent .= $tables->eq(1)->text();
				}else if ($tables->length() == 5){
					$msgDetailsContent .= $tables->eq(3)->text();
				} else if ($tables->length() == 7 || $tables->length() == 8){
					$msgDetailsContent .= $tables->eq(3)->text();
					$msgDetailsContent .= $tables->eq(4)->text();
					$msgDetailsContent .= $tables->eq(5)->text();
				}
// 				print_r('1');
			}else if(phpQuery::pq('table[width="606"] table[width="100%"][cellspacing="0"][cellpadding="0"]')->length() != 0){
				$billsTables = phpQuery::pq('table[width="606"] table[width="100%"][cellspacing="0"][cellpadding="0"]');// ebay 月份账单
				$msgDetailsContent .= $billsTables->eq(0)->text();
				$msgDetailsContent .= $billsTables->eq(1)->text();
				$msgDetailsContent .= $billsTables->eq(2)->text();
				$msgDetailsContent .= $billsTables->eq(3)->text();
// 				print_r('2');
			}else if(phpQuery::pq('div#EmailBody')->length() != 0){// 提高销售额度
				$msgDetailsContent .= phpQuery::pq('div#EmailBody')->html();
// 				print_r('3');
			}else if(phpQuery::pq('div#TextCTA table td')->length() != 0){// dzt20151113 add. eBay Selling Manager Subscription Canceled
				$firstTd = phpQuery::pq('div#TextCTA table td');
				$msgDetailsContent = $firstTd->eq(0)->text();
// 				print_r('4');
			}else if(phpQuery::pq('div#SingleItemCTA table td')->length() != 0){// dzt20151113 add.	A case was opened on eBay.com
				$firstTd = phpQuery::pq('div#SingleItemCTA table td');// 对话
				$msgDetailsContent = $firstTd->eq(0)->text();
// 				print_r('5');
			}else if(phpQuery::pq('table[width="100%"][cellspacing="0"][cellpadding="0"] table.BodyFont[width="100%"][cellspacing="0"][cellpadding="0"]')->length() != 0){// ebay 还价过期
				$counterOfferTr = phpQuery::pq('table[width="100%"][cellspacing="0"][cellpadding="0"] table.BodyFont[width="100%"][cellspacing="0"][cellpadding="0"] > tr');
				$msgDetailsContent .= $counterOfferTr->eq(2)->text();
				$msgDetailsContent .= $counterOfferTr->eq(3)->text();
				$msgDetailsContent .= $counterOfferTr->eq(5)->text();
// 				print_r('6');
			}else if(phpQuery::pq('table#bodyContainer table.mobWrapperTemplate')->length() != 0){//dzt20190819 添加抓取站内信信息 eBay Customer Service , ebay sender :eBay
				$bodyContainerTables = phpQuery::pq('table#bodyContainer table.mobWrapperTemplate');
				
				$msgDetailsContent .= $bodyContainerTables->eq(0)->text();
				$msgDetailsContent .= $bodyContainerTables->eq(1)->text();
				$msgDetailsContent .= $bodyContainerTables->eq(2)->text();
// 				print_r('8');
			}else if(phpQuery::pq('p.cs95E872D0')->length() != 0){// eBay Customer Service , ebay sender :eBay CS Support
				$msgDetailsContent .= phpQuery::pq('p.cs95E872D0')->text();
// 				print_r('7');
			}else if ((strpos($msgDetails['Text'], '<html xmlns:ebay="eBayV3" xmlns:co="CheckoutV3">') !== false) && (strpos($msgDetails['Text'], '<table width="100%" cellpadding="2" cellspacing="3" border="0">') !== false)){
				$msgDetails['Text']=preg_replace("/[\t\n\r]+/","",$msgDetails['Text']);
				$partern= '/<td[^>]*([\s\S]*?)<\/td>/i';
				preg_match_all($partern,$msgDetails['Text'],$macthes);
				
				if(isset($macthes[0][7])){
					$macthes[0][7] = explode('<table',$macthes[0][7]);
					$msgDetailsContent = $macthes[0][7][0];
				}else{
					$msgDetailsContent = '';
				}
// 				print_r('8');
			}
		}else{
			if(phpQuery::pq('div#TextCTA table td')->length() != 0){
				$firstTd = phpQuery::pq('div#TextCTA table td');// 对话
				$msgDetailsContent = $firstTd->eq(0)->text();
			}else if(phpQuery::pq('div#UserInputtedText')->length() != 0){
				$firstTd = phpQuery::pq('div#UserInputtedText');
				$msgDetailsContent = $firstTd->eq(0)->text();
			}else if(phpQuery::pq('table[width="600"]')->length() != 0){
				$tables = phpQuery::pq('table[width="600"]');
				if ($tables->length() == 3){
					$msgDetailsContent .= $tables->eq(1)->text();
				}else if ($tables->length() == 5){
					$msgDetailsContent .= $tables->eq(3)->text();
				} else if ($tables->length() == 7 || $tables->length() == 8){
					$msgDetailsContent .= $tables->eq(3)->text();
					$msgDetailsContent .= $tables->eq(4)->text();
					$msgDetailsContent .= $tables->eq(5)->text();
				}
			}
		}
		$msgDetailsContent = trim($msgDetailsContent);
		return $msgDetailsContent;
	}	
	
	public static $ebaySenders = array("eBay" , "eBay CS Support" , "csfeedback@ebay.com");//ebay 站内信的Sender 可能不仅仅是“eBay” , 这里测到一个eBay CS Support  @todo 可能还会有多的Sender名称 
	/**
	 * 
	 * $messageIdListInfo //获取平台原始列表数据
	 * $msgDetails //获取平台的原始detail数据
	 * $uid //小老板平台uid
	 * $selleruserid //敦煌saas_dhgate_user 的sellerloginid
	 * @return string
	 */
	public static function ebayMsgToEagleMsg($messageIdListInfo , $msgDetails , $uid, $selleruserid){
		$eagleMsgOne = array();
		$eagleMsgOne['msgKey'] = $msgDetails['MessageID'];
		
		//$selleruserid做对比确定buyerid，确定哪条信息是发送，哪条是接收.
		//$eagleMsgOne['isSend'] 是否发送，0--接收，1--发送
		if(in_array($msgDetails['Sender'], self::$ebaySenders)){
			$eagleMsgOne['session_id'] = 'eBay'.$selleruserid;
			$eagleMsgOne['messageType'] = 3; // 平台消息
			
			$eagleMsgOne['buyer_id'] = '';
			$eagleMsgOne['buyer_nickname'] = '';
			$eagleMsgOne['isSend'] = 0;
			$eagleMsgOne['seller_nickname'] = $msgDetails['RecipientUserID'];
			
			// @todo 可以通过Messages.Message.MessageType 对related_type , list_related_type(相关类别，P：商品，O：订单, S:系统平台 ) 做初步区分，如ContactTransactionPartner =>订单
			// 但是某些 MessageType 如回复等类型 就无法区分了（可能再通过session的list_related_type 再作判断）
			$eagleMsgOne['related_type'] = 'S';
			$eagleMsgOne['list_related_type'] = 'S';
		}else{
			if($msgDetails['Sender'] == $selleruserid){
				$eagleMsgOne['session_id'] = $msgDetails['SendToName'].$selleruserid;
				
				$eagleMsgOne['buyer_id'] = $msgDetails['SendToName'];// @todo 拉取卖家发的信息，这里没有buyer id
				$eagleMsgOne['buyer_nickname'] = $msgDetails['SendToName'];
				$eagleMsgOne['isSend'] = 1;
				$eagleMsgOne['seller_nickname'] = $msgDetails['Sender'];
			}else{
				$eagleMsgOne['session_id'] = $msgDetails['Sender'].$selleruserid;
				
				$eagleMsgOne['buyer_id'] = $msgDetails['Sender'];
				$eagleMsgOne['buyer_nickname'] = $msgDetails['Sender'];
				$eagleMsgOne['isSend'] = 0;
				$eagleMsgOne['seller_nickname'] = $msgDetails['SendToName'];
			}
			$eagleMsgOne['messageType'] = 2; // 平台消息
		}
		
		$reseiveDate = new \DateTime($msgDetails['ReceiveDate']);// 2015-06-04T10:33:56.000Z 这类型的时间字符需要转换时区
		$eagleMsgOne['msgCreateTime'] = $reseiveDate->format('Y-m-d H:i:s');
		$reseiveDate->setTimezone ( new \DateTimeZone(date_default_timezone_get()));// 'Asia/Shanghai'
		$eagleMsgOne['app_time'] = $reseiveDate->format('Y-m-d H:i:s');
		
		$eagleMsgOne['msgTitle'] = $messageIdListInfo['Subject'];
		$eagleMsgOne['msgDetailsTitle'] = $msgDetails['Subject'];
	
		$eagleMsgOne['content'] = self::extractApiMessageContent($msgDetails);
		$eagleMsgOne['list_contact'] = json_encode($messageIdListInfo);
		$eagleMsgOne['msg_contact'] = $msgDetails;
		
		// 根据api 返回的$msgDetails['ItemID'] 查找系统内是否有该item的订单或产品而归类 , 关联成功的话就将ebay的订单id或者ebay的产品id写入related_id字段
		// 没有ItemID的 就关联不到东西了，这时就不处理
		if(isset($msgDetails['ItemID'])){
			$item = EbayItem::findOne(['itemid'=>$msgDetails['ItemID']]);
			if(!empty($item)){
				$eagleMsgOne['related_id'] = $msgDetails['ItemID'];
				$eagleMsgOne['list_related_id'] = $msgDetails['ItemID'];
				$eagleMsgOne['related_type'] = 'P';
				$eagleMsgOne['list_related_type'] = 'P';
			}
			
			//关联订单优先级比产品高 , 如有关联则覆盖。这里需要使用站内信的内容去跟ebay的订单做查找处理不然找不出订单留言的站内信
			if(($eagleMsgOne['messageType'] == 2) && (isset($msgDetails['Content']))){
				$tmpOrders = OdOrder::find()->select(['order_source_order_id'])->where(['source_buyer_user_id'=>$eagleMsgOne['buyer_id']])->asArray()->all();
				
				foreach ($tmpOrders as $tmpOrder){
					if (strpos($msgDetails['Content'], $tmpOrder['order_source_order_id']) !== false){
						$eagleMsgOne['related_id'] = $tmpOrder['order_source_order_id'];
						$eagleMsgOne['list_related_id'] = $tmpOrder['order_source_order_id'];
						
						$eagleMsgOne['related_type'] = 'O';
						$eagleMsgOne['list_related_type'] = 'O';
						break;
					}
				}
			}
		}
		
		$result = MessageApiHelper::_insertMsg($uid, $selleruserid, 'ebay', 'message_id', $eagleMsgOne);
	
		//yzq，记录这个session的session id，作为dirty data的cache hqw Edit
		$result['touched_session_id'] = $eagleMsgOne['session_id'];
		$result['touched_customer_id'] = $eagleMsgOne['buyer_id'];
		return $result;
	}
	
	
	/**
	 * 将接口获得的feedback数据保存到客服模块表
	 * @param 	$msgDetails //获取平台的原始detail数据
	 * @param 	$uid //小老板平台uid
	 * @param 	$selleruserid //saas_ebay_user 的sellerloginid
	 * @return 	string
	 * @author	lzhl	2017-11
	 */
	public static function ebayFeedbackToEagleMsg($feedbackDetails , $uid, $selleruserid){
		$eagleMsgOne = array();
		$eagleMsgOne['msgKey'] = $feedbackDetails['FeedbackID'];
	
		//$selleruserid做对比确定buyerid，确定哪条信息是发送，哪条是接收.
		//$eagleMsgOne['isSend'] 是否发送，0--接收，1--发送
		
	
		$eagleMsgOne['session_id'] = $feedbackDetails['CommentingUser'].$selleruserid;

		$eagleMsgOne['buyer_id'] = $feedbackDetails['CommentingUser'];
		$eagleMsgOne['buyer_nickname'] = $feedbackDetails['CommentingUser'];
		$eagleMsgOne['isSend'] = 0;
		$eagleMsgOne['seller_nickname'] = $selleruserid;
	
		$eagleMsgOne['messageType'] = 2; // 平台消息
		
	
		$reseiveDate = new \DateTime($feedbackDetails['CommentTime']);// 2015-06-04T10:33:56.000Z 这类型的时间字符需要转换时区
		$eagleMsgOne['msgCreateTime'] = $reseiveDate->format('Y-m-d H:i:s');
		$reseiveDate->setTimezone ( new \DateTimeZone(date_default_timezone_get()));// 'Asia/Shanghai'
		$eagleMsgOne['app_time'] = $reseiveDate->format('Y-m-d H:i:s');
	
		$eagleMsgOne['msgTitle'] = $feedbackDetails['CommentType'];
		$eagleMsgOne['msgDetailsTitle'] = $feedbackDetails['CommentType'];
	
		$eagleMsgOne['content'] = $feedbackDetails['CommentText'];
		$eagleMsgOne['list_contact'] = json_encode($feedbackDetails);
		$eagleMsgOne['msg_contact'] = $feedbackDetails;
	
		// 根据api 返回的$feedbackDetails['ItemID'] 查找系统内是否有该item的订单或产品而归类 , 关联成功的话就将ebay的订单id或者ebay的产品id写入related_id字段
		// 没有ItemID的 就关联不到东西了，这时就不处理
		if(isset($feedbackDetails['ItemID'])){
			$item = EbayItem::findOne(['itemid'=>$feedbackDetails['ItemID']]);
			if(!empty($item)){
				$eagleMsgOne['related_id'] = $feedbackDetails['ItemID'];
				$eagleMsgOne['list_related_id'] = $feedbackDetails['ItemID'];
				$eagleMsgOne['related_type'] = 'P';
				$eagleMsgOne['list_related_type'] = 'P';
			}
				
			//关联订单优先级比产品高 , 如有关联则覆盖。这里需要使用站内信的内容去跟ebay的订单做查找处理不然找不出订单留言的站内信
			if(($eagleMsgOne['messageType'] == 2) && (isset($feedbackDetails['Content']))){
				$tmpOrders = OdOrder::find()->select(['order_source_order_id'])->where(['source_buyer_user_id'=>$eagleMsgOne['buyer_id']])->asArray()->all();
	
				foreach ($tmpOrders as $tmpOrder){
					if (strpos($feedbackDetails['Content'], $tmpOrder['order_source_order_id']) !== false){
						$eagleMsgOne['related_id'] = $tmpOrder['order_source_order_id'];
						$eagleMsgOne['list_related_id'] = $tmpOrder['order_source_order_id'];
	
						$eagleMsgOne['related_type'] = 'O';
						$eagleMsgOne['list_related_type'] = 'O';
						break;
					}
				}
			}
		}
	
		$result = MessageApiHelper::_insertMsg($uid, $selleruserid, 'ebay', 'message_id', $eagleMsgOne);
	
		//yzq，记录这个session的session id，作为dirty data的cache hqw Edit
		$result['touched_session_id'] = $eagleMsgOne['session_id'];
		$result['touched_customer_id'] = $eagleMsgOne['buyer_id'];
		return $result;
	}
	
	/**
	 * ebay用户保存发送站内信或订单留言接口
	 *
	 * 用于保存在队列表中，到时会有后台Job处理发送
	 *
	 * @param
	 * $params = array(
	 * 'platform_source' => '' //平台类型
	 * 'msgType' => '' //站内信类型 1--订单，2--站内信
	 * 'puid' => '' //小老板平台uid
	 * 'contents' => '' //内容
	 * 'ticket_id' => '' //小老板会话id 此值必须
	 * );
	 */
	public static function saveMsgQueue($params,$changeUserDb=true){
 
		
		//ticket_id 来查找对应的会话信息
		$TSession_obj = TicketSession::findOne(['ticket_id'=>$params['ticket_id']]);
		
		if ($TSession_obj == null){
			\Yii::error('function:'.__FUNCTION__.'没有该会话信息，请检查');
			return array('success'=>false,'error'=>'function:'.__FUNCTION__.'没有该会话信息，请检查');
		}
		//假如站内信先拉取到，而订单后拉取到，可能会出现没有related_id以及related_type
// 		if(empty($TSession_obj->related_id)){
// 			\Yii::error('function:'.__FUNCTION__.'没有对应的ItemID，该信息不能回复');
// 			return array('success'=>false,'error'=>'function:'.__FUNCTION__.'没有对应的ItemID，该信息不能回复');
// 		}
		
		$tmpAddi_info = array();
		$tmpAddi_info['buyer_nickname'] = $TSession_obj->buyer_nickname;
		$tmpAddi_info['ItemID'] = empty(json_decode($TSession_obj->list_contact, true)['ItemID']) ? '' : json_decode($TSession_obj->list_contact, true)['ItemID'];;
		$tmpAddi_info['msgTitle'] = "Anwser about:Item#".$TSession_obj->related_id;//用已有的$TSession_obj->msgTitle 超长，所以这里统一改。 由于目前站内信是回复的所以用这个subject
		
		$msgOne = array(
			'ticket_id' => $TSession_obj->ticket_id,
			'session_id' => $TSession_obj->session_id,
			'contents' => $params['contents'],
			'headers' => "",
			'created' => date('Y-m-d H:i:s', time ()),
			'updated' => date('Y-m-d H:i:s', time ()),
			'addi_info' => "",
			'app_time' => date('Y-m-d H:i:s', time ()),
			'platform_time' => date('Y-m-d H:i:s', time ()),
			'seller_id' => $TSession_obj->seller_id,
			'buyer_id' => $TSession_obj->buyer_id,
		);
		
		$tmpEbayMsg = \eagle\modules\message\models\TicketMessage::find()->select(['msg_contact'])->where("ticket_id=:ticket_id and message_id!='' ",array(':ticket_id'=>$TSession_obj->ticket_id))->one();
		
		if (!empty($tmpEbayMsg)){
			$tmpMsgContact = MessageApiHelper::msgDetectionJsonToArr($tmpEbayMsg->msg_contact);
			$tmpAddi_info['MessageID'] = empty($tmpMsgContact['ExternalMessageID']) ? '' : $tmpMsgContact['ExternalMessageID'];
		}
		
		$msgBufferArr = array(
				'seller_id'=>$TSession_obj->seller_id,
				'message_type'=>$params['msgType'],
				'order_id'=>empty($params['order_id'])?'':$params['order_id'],
				'buyer_id'=>empty($TSession_obj->buyer_id)?"":$TSession_obj->buyer_id,
				'addi_info' => json_encode($tmpAddi_info)
		);
		
		$result = MessageApiHelper::platformSaveMsgQueue($params['puid'], $params['platform_source'], $msgOne, $msgBufferArr, array(), $changeUserDb);
		
		return $result;
	}
	
	/**
	 * 新建一个session，用于主动发信一个新的会话给买家，后续ticket message需要这个session 的id
	 * @param object 	$order		订单model
	 * @param string	$subject	标题
	 * @param string	$body		内容
	 * @return array
	 * @author	lzhl 	2016/07/26			初始化
	 */
	public static function saveNewTicketSession($order,$subject,$body){
		
		$TSession_obj = TicketSession::find()->where(['related_id'=>$order->order_source_order_id])->one();
		$addi_info = [];
		if(empty($TSession_obj)){
			/*
			$related_id = '';
			if(empty($order->items))
				return ['success'=>false,'err_msg'=>'订单商品详情缺失','session'=>''];
			foreach ($order->items as $item){
				if(empty($related_id)){
					$related_id = $item->order_source_itemid;
					$addi_info['ItemID'] = $item->order_source_itemid;
				}
				$TSession_obj = TicketSession::find()->where(['related_id'=>$item->order_source_itemid])->one();
				if(!empty($TSession_obj))
					return ['success'=>true,'err_msg'=>'','session'=>$TSession_obj];
			}
			*/
		}else 
			return ['success'=>true,'err_msg'=>'','session'=>$TSession_obj];
		
		$addi_info['buyer_nickname'] = $order->source_buyer_user_id;
		$addi_info['msgTitle'] = $subject;
		$addi_info['SRN'] = $order->order_source_srn;
		
		$TSession_obj = new TicketSession();
		
		$TSession_obj->platform_source = 'ebay';
		$TSession_obj->message_type = 2;
		$TSession_obj->related_type= 'O';
		$TSession_obj->related_id= $order->order_source_order_id;
		$TSession_obj->seller_id = $order->selleruserid;
		$TSession_obj->buyer_id = $order->source_buyer_user_id;
		$TSession_obj->buyer_nickname = $order->source_buyer_user_id;
		$TSession_obj->created = TimeUtil::getNow();
		$TSession_obj->updated = TimeUtil::getNow();
		$TSession_obj->lastmessage = TimeUtil::getNow();
		$TSession_obj->msgTitle = $subject;
		$TSession_obj->list_contact = empty($addi_info)?'':json_encode($addi_info);
		if($TSession_obj->save())
			return ['success'=>true,'err_msg'=>'','session'=>$TSession_obj];
		else{
			return ['success'=>false,'err_msg'=>'Failed to save session!','session'=>''];
		}
	}
}

