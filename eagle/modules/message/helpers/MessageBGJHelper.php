<?php
/*+----------------------------------------------------------------------
| 小老板
+----------------------------------------------------------------------
| Copyright (c) 2011 http://www.xiaolaoban.cn All rights reserved.
+----------------------------------------------------------------------
| Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
+----------------------------------------------------------------------
| Author: fanjs
+----------------------------------------------------------------------
| Create Date: 2014-08-01
+----------------------------------------------------------------------
 */
namespace eagle\modules\message\helpers;
use yii;
use yii\data\Pagination;

use eagle\modules\util\helpers\OperationLogHelper;
use eagle\modules\util\helpers\StandardConst;
use eagle\modules\tracking\helpers\TrackingAgentHelper;
use eagle\modules\util\helpers\ConfigHelper;
use eagle\modules\tracking\models\Tracking;
use eagle\modules\util\helpers\GetControlData;
use yii\helpers\StringHelper;
use eagle\modules\tracking\helpers\TrackingHelper;
use eagle\modules\message\apihelpers\MessageAliexpressApiHelper;
use eagle\modules\message\models\Message;
use eagle\modules\message\apihelpers\MessageEbayApiHelper;
use yii\base\Exception;
use common\api\dhgateinterface\Dhgateinterface_Api;
use eagle\modules\message\models\TicketMessage;
use eagle\modules\message\apihelpers\MessageApiHelper;
use eagle\modules\util\helpers\SQLHelper;
use eagle\modules\message\apihelpers\MessageWishApiHelper;
use eagle\modules\message\apihelpers\MessageDhgateApiHelper;
use eagle\modules\order\models\OdOrder;
use eagle\modules\message\apihelpers\MessagePriceministerApiHelper;
use eagle\modules\order\models\OdEbayTransaction;

/**
 * 
 +------------------------------------------------------------------------------
 * Message 后台job 发送接收的业务逻辑
 +------------------------------------------------------------------------------
 * @author		yzq
 +------------------------------------------------------------------------------
 */
class MessageBGJHelper {
	private static $Insert_Message_Queue_Buffer = array();
	private static $sendQueueVersion = '';
	
	private static $subQueueVersion = '';
	private static $putIntoMessageQueueVersion = '';

	/**
	 +---------------------------------------------------------------------------------------------
	 * 吧data中的message queue 内容，放入到message Q 缓冲区，等到批量insert
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param         array of data for message queue, e.g.:
	 *                ['puid'=>1,'create_time'=>,'subject'=>'hihi','content'='Money pls',
	 *                'platform'=>ebay,'order_id'=>'123123123','priority'=5]
	 *                清注意：platform 的值是固定的 enum('ebay', 'aliexpress', 'wish', 'amazon')
	 +---------------------------------------------------------------------------------------------
	 * @return n/a
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		yzq		2015/4/9				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	public static function putToMessageQueueBuffer($aData){
		//$aData = array();	
		if (isset($aData['platform']))
			$aData['platform'] = strtolower($aData['platform']);
		
		if (!isset($aData['priority']))
			$aData['priority'] = 5;
		
		self::$Insert_Message_Queue_Buffer[] = $aData;
	}
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 把Buffer data 放到 Message Queue db里面 ，批量insert 到数据库中
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 +---------------------------------------------------------------------------------------------
	 * @return array
	 *
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		yzq		2015/4/9				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function postMessageApiQueueBufferToDb(){
		//use sql PDO, not Model here, for best performance
		$sql = " INSERT INTO  `message_api_queue`".
				"( `status`, `puid`,seller_id,`create_time`,subject,content,".
				"platform, order_id ,priority,msg_id , buyer_id ) VALUES ";
	
		$sql_values = '';
		$Datas = self::$Insert_Message_Queue_Buffer;
		self::$Insert_Message_Queue_Buffer = array();
		
		$fields = ['puid','seller_id','create_time','subject','content','platform','order_id','priority','msg_id' , 'buyer_id'];
		
		//step 1, create a full SQL.
		$i = 10000;
		$starti =$i + 1;
		$bindDatas = array();
		foreach ($Datas as $data){
	        $i++;
			
			if (empty($data['update_time']))
				$data['update_time'] = $data['create_time'];
			
			if (empty($data['priority']))
				$data['priority'] = 5;
	
			$bindDatas[$i] = $data;

			$sql_values .= ($sql_values==''?'':",").
			"('P',:puid$i,:seller_id$i,:create_time$i,:subject$i, :content$i ,:platform$i, :order_id$i, :priority$i, :msg_id$i , :buyer_id$i  ".
			")";

			if (strlen($sql_values) > 3000){
				//one sql syntax do not exceed 4800, so make 3000 as a cut here
				//	\Yii::info(['Message',__CLASS__,__FUNCTION__,'Background',"SQL to be execute:".$sql.$sql_values ],"edb\global");
				$command = Yii::$app->db->createCommand($sql.$sql_values .";");
				
				//bind all values
				for($tempi = $starti; $tempi <= $i; $tempi ++){
					foreach ($fields as $aField){
						$command->bindValue(':'.$aField.$tempi, $bindDatas[$tempi][$aField], \PDO::PARAM_STR);
					}
				}//end of each data index for this bulk insert

				$command->execute();
				$sql_values = '';
				$starti = $i + 1;
			}
		}//end of each track no
	
		//step 2, insert the rest
		if ($sql_values <> ''){
			//	\Yii::info(['Message',__CLASS__,__FUNCTION__,'Background',"SQL to be execute:".$sql.$sql_values ],"edb\global");
			$command = Yii::$app->db->createCommand($sql.$sql_values.";");
			for($tempi = $starti; $tempi <= $i; $tempi ++){
				foreach ($fields as $aField){
					$command->bindValue(':'.$aField.$tempi, $bindDatas[$tempi][$aField], \PDO::PARAM_STR);
				}
			}//end of each data index for this bulk insert
			$command->execute();
		}
	
	}//end of function postMessageBufferToDb
	
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * API队列处理器。按照priority执行一个API，然后把结果以及状态update到queue，
	 * 同时把信息写到每个user数据库的 Message 表中.
	 * 该方法只会执行排在最前面的一个request，然后就返回了，不会持续执行好多
	 * 该任务支持多进程并发执行
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param  orderid    可以指定只做某个order id
	 * @param  platform   必须指定平台，可选option: ebay,aliexpress
	 +---------------------------------------------------------------------------------------------
	 * @return						array('success'=true,'message'='')
	 *
	 * @invoking					MessageHelper::queueHandlerProcessing1();
	 *
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		yzq		2015/2/9				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function queueHandlerProcessing1($order_id='',$platform='',$app_source='tracker',$jobTail=-1,$totalProcess=0){
		global $CACHE;
		$dummySend = false;
		if (empty($CACHE['JOBID']))
			$CACHE['JOBID'] = "MS".rand(0,99999);
		
		$WriteLog = false;
		if ($WriteLog)
			\Yii::info(['Message',__CLASS__,__FUNCTION__,'Background',"SendQueue 0 Enter:".$CACHE['JOBID'] ],"edb\global");
	
		$rtn['message'] = "";
		$rtn['success'] = true;
		$now_str = date('Y-m-d H:i:s');
		$seedMax = 15;
		$seed = rand(0,$seedMax);
		$one_go_count = 50;

		//Step 0, 检查当前本job版本，如果和自己的版本不一样，就自动exit吧
	
		$JOBID=$CACHE['JOBID'];
		$current_time=explode(" ",microtime());	$start1_time=round($current_time[0]*1000+$current_time[1]*1000);
		\Yii::info("multiple_process_main step1 mainjobid=$JOBID");
	
		$currentSendQueueVersion = ConfigHelper::getGlobalConfig("Message/sendQueueVersion",'NO_CACHE');
		if (empty($currentSendQueueVersion))
			$currentSendQueueVersion = 0;
		
		//如果自己还没有定义，去使用global config来初始化自己
		if (empty(self::$sendQueueVersion))
			self::$sendQueueVersion = $currentSendQueueVersion;
			
		//如果自己的version不同于全局要求的version，自己退出，从新起job来使用新代码
		if (self::$sendQueueVersion <> $currentSendQueueVersion){
			TrackingAgentHelper::extCallSum("",0,true);
			exit("Version new $currentSendQueueVersion , this job ver ".self::$sendQueueVersion." exits for using new version $currentSendQueueVersion.");
		}
	
		//step 1, try to get a pending request in queue, according to priority
		$coreCriteria = ' status="P" ';
		$coreCriteria .=" and platform='$platform'" .($order_id==''?'':" and order_id=:orderid");
		
		$coreCriteria .=" and app_source='$app_source' "; //20150925 hqw 不需要判断站内信类型
		
		//如果是多个job并发的，这里是分配每个job做哪一些任务，防止 互相打架
		if ($totalProcess > 0 and $jobTail >= 0)
			$coreCriteria .= " and id % $totalProcess = $jobTail ";
		
		//防止一个客户太多request，每次随机一个数，优先处理puid mod 5 ==seed 的这个
		$command= Yii::$app->get('db')->createCommand(
				"select * from message_api_queue force index (spo) where $coreCriteria order by priority,id asc limit $one_go_count");
		
		$command->bindValue(':orderid' , $order_id, \PDO::PARAM_STR);
		
		if($platform=='ebay')
			echo "\n query: \n ".$command->getRawSql();
		
		$pendingOnes = $command->queryAll();
		
		//if no pending one found, return true, message = 'n/a';
		if ( empty($pendingOnes)  ){
			$rtn['message'] = "n/a";
			$rtn['success'] = true;
// 			echo "No pending, idle 4 sec... ";
			return $rtn;
		}
		
		$current_time=explode(" ",microtime()); $start2_time=round($current_time[0]*1000+$current_time[1]*1000);
		\Yii::info("send_message_$platform step2 jobid=$JOBID,t2_t1=".($start2_time-$start1_time));
		TrackingAgentHelper::extCallSum("MS.MainQPickOne",$start2_time-$start1_time);
		
		$doneMsgIds = array();
		$donePuidOrders = array();
		
		foreach ($pendingOnes as $pendingMessage){
			$msg_id = $pendingMessage['id'];
			$puid = $pendingMessage['puid'];
			
			//检测MD5，如果已经有发成功过一样的内容，就skip掉
			if(!empty($pendingMessage['content_md5'])){
				$sameMd5= Yii::$app->get('db')->createCommand(
						"select id from message_api_queue where puid=".$puid." and buyer_id='".$pendingMessage['buyer_id']."' and  content_md5='".$pendingMessage['content_md5']."' and status='C' "
				)->queryOne();
				if(!empty($sameMd5)){
					$doneMsgIds["C"][$msg_id] = $msg_id;
					continue;
				}
			}
			//检测MD5 end
			
			 
			
			//step 1: call platform api to send the message
			$content_tail = '';
			if (!empty($pendingMessage['addi_info'])){
				$addi_info = json_decode($pendingMessage['addi_info'],true);
				if (!empty($addi_info['tail']))
					$content_tail = $addi_info['tail'];
				//PM用到item_id
				if(!empty($addi_info["item_id"])){
				    $item_id = $addi_info["item_id"];
				}
			}

			$current_time=explode(" ",microtime()); $start1_time=round($current_time[0]*1000+$current_time[1]*1000);

			//Support empty buyerid got, ys20150924
			if (empty($pendingMessage['buyer_id'])){
				$theOrder = OdOrder::find()->where( array("order_source_order_id"=>$pendingMessage['order_id'] , 'selleruserid'=>$pendingMessage['seller_id']))->one();
				if (!empty($theOrder)){
					$pendingMessage['buyer_id'] = $theOrder->source_buyer_user_id;
				}//else
					//\Yii::info(['Message',__CLASS__,__FUNCTION__,'Background',"Not Load the order for=".$pendingMessage['order_id']." sellerid=".$pendingMessage['seller_id']],"edb\global");
			}
			
			if ($platform == 'ebay'){ //20150706 hqw 添加类型
			 
				if($app_source=='tracker'){
					try{
						if (strlen($pendingMessage['subject']) > 100 )
							$pendingMessage['subject'] = substr($pendingMessage['subject'],0,100);
						
						if (strlen($pendingMessage['content']) > 2000 )
							$pendingMessage['content'] = substr($pendingMessage['content'],0, 2000);
						
						$rtn1 = MessageEbayApiHelper::addMessage($pendingMessage['seller_id'], $pendingMessage['order_id'], $pendingMessage['content'], 'General',   $pendingMessage['subject'], 0);
					} catch (Exception $e) {
						$rtn1 = [   'Ack'=>'Failure',
									'Errors'=>['LongMessage'=>'eBay API Failed','ShortMessage'=>'eBay API Failed']
								];
					}
				}else if($app_source=='cs_ticket'){
					$tmpAddi_info = json_decode($pendingMessage['addi_info'],true);
					
					try{
						if(empty($tmpAddi_info['ItemID']) || !is_numeric($tmpAddi_info['ItemID'])){
							$tmpEbayList = \eagle\modules\message\models\TicketSession::find()->select(['list_contact','related_id'])->where("ticket_id=:ticket_id",array(':ticket_id'=>$pendingMessage['ticket_id']))->one();
							
							if (!empty($tmpEbayList)){
								$tmpAddi_info['ItemID'] = empty(json_decode($tmpEbayList->list_contact, true)['ItemID']) ? '' : json_decode($tmpEbayList->list_contact, true)['ItemID'];
								if($tmpAddi_info['ItemID']==''){
									$related_id = empty($tmpEbayList->related_id)?'':$tmpEbayList->related_id;
									$tmp_id_info = explode('-', $related_id);
									$tmpAddi_info['ItemID'] = empty($tmp_id_info[0])?'':$tmp_id_info[0];
								}
								
								if(empty($tmpAddi_info['ItemID']) && !empty($pendingMessage['order_id'])){
									$order = OdOrder::findOne(['order_source_order_id'=>$pendingMessage['order_id']]);
									if (empty($order)){
										$rtn1 = [
											'Ack'=>'Failure',
											'Errors'=>['LongMessage'=>'Order not found!','ShortMessage'=>'Order not found!']
										];
									}
									if (count($order->items)==0){
										$rtn1 = [
											'Ack'=>'Failure',
											'Errors'=>['LongMessage'=>'Item not found!','ShortMessage'=>'Item not found!']
										];
									}
									$item = $order->items['0'];
									if ($item->order_source_order_item_id<=0){
										$rtn1 = [
											'Ack'=>'Failure',
											'Errors'=>['LongMessage'=>'Transaction not found!','ShortMessage'=>'Transaction not found!']
										];
									}
									$transaction = OdEbayTransaction::findOne($item->order_source_order_item_id);
									if (empty($transaction)){
										$rtn1 = [
											'Ack'=>'Failure',
											'Errors'=>['LongMessage'=>'Transaction not found!','ShortMessage'=>'Transaction not found!']
										];
									}
									else 
										$tmpAddi_info['ItemID'] = $transaction->itemid;
								}elseif(empty($tmpAddi_info['ItemID']) && empty($pendingMessage['order_id'])){
									$rtn1 = [
										'Ack'=>'Failure',
										'Errors'=>['LongMessage'=>'OrderId and order_id Undefined!','ShortMessage'=>'OrderId and order_id Undefined!']
									];
								}
							}
						}
						if(!empty($tmpAddi_info['ItemID'])){
							$rtn1 = MessageEbayApiHelper::addMessage2($pendingMessage['seller_id'], $tmpAddi_info['buyer_nickname'], $tmpAddi_info['ItemID'], $pendingMessage['content'], $tmpAddi_info['msgTitle']);
							echo "\n liang test 01";
							print_r($rtn1);
							echo "\n parmas: seller:".$pendingMessage['seller_id']." buyer_nickname:".$tmpAddi_info['buyer_nickname']." ItemID:".$tmpAddi_info['ItemID'];
							if(isset($rtn1['Ack'])){
								if($rtn1['Ack']!='Success'){
									echo "\n liang test 02";
									//Support empty MessageID got, hqw201151209
									if (empty($tmpAddi_info['MessageID'])){
										echo "\n liang test 03";
										$tmpEbayMsg = \eagle\modules\message\models\TicketMessage::find()->select(['msg_contact'])->where("ticket_id=:ticket_id and message_id!='' ",array(':ticket_id'=>$pendingMessage['ticket_id']))->one();
										if (!empty($tmpEbayMsg)){
											$tmpMsgContact = MessageApiHelper::msgDetectionJsonToArr($tmpEbayMsg->msg_contact);
											$tmpAddi_info['MessageID'] = empty($tmpMsgContact['ExternalMessageID']) ? '' : $tmpMsgContact['ExternalMessageID'];
										}
									}
									
									if(empty($tmpAddi_info['MessageID'])){
										$rtn1 = MessageEbayApiHelper::addMessage2($pendingMessage['seller_id'], $tmpAddi_info['buyer_nickname'], $tmpAddi_info['ItemID'], $pendingMessage['content'], $tmpAddi_info['msgTitle']);
										echo "\n liang test 04-a";
									}else{
										$rtn1 = MessageEbayApiHelper::responseMessage($pendingMessage['seller_id'], $tmpAddi_info['buyer_nickname'], '', $pendingMessage['content'], $tmpAddi_info['MessageID']);	//$tmpAddi_info['ItemID'] 这个值可填可不填
										echo "\n liang test 04-b";
									}
									echo "\n liang test 05";
									print_r($rtn1);
								}
							}
						}
						else{
							$rtn1 = [
								'Ack'=>'Failure',
								'Errors'=>['LongMessage'=>'ItemId Undefined','ShortMessage'=>'ItemId Undefined!']
							];
						}
					} catch (Exception $e) {
						$rtn1 = [   'Ack'=>'Failure',
						'Errors'=>['LongMessage'=>'eBay API Failed','ShortMessage'=>'eBay API Failed']
						];
					}
				}
				 
				if (isset($rtn1['Ack']) and $rtn1['Ack']=='Success'){
					$doneMsgIds["C"][$msg_id] = $msg_id;
					$donePuidOrders["C"][$puid][$pendingMessage['msg_id']] = $pendingMessage['msg_id'];
					
				}else {
					if (!isset($rtn1['Errors']['LongMessage']))
						$rtn1['Errors']['LongMessage'] = '';
					
					$doneMsgIds["F"][$msg_id] = $msg_id;
					$addi_info['error'] = isset($rtn1['Errors']['LongMessage'])?$rtn1['Errors']['LongMessage']: $rtn1;
					$doneMsgIds["addi_info"][$msg_id] = json_encode($addi_info);
					$donePuidOrders["F"][$puid][ $pendingMessage['msg_id'] ] = $rtn1['Errors']['LongMessage'];
				}
				 
			} //end of ebay api
			
			if ($platform == 'aliexpress'){ //20150706 hqw 添加类型
			 
				if($app_source=='tracker'){
					try{ 
						$rtn1 = MessageAliexpressApiHelper::addOrderMessage($pendingMessage['order_id'], $pendingMessage['seller_id'], $pendingMessage['buyer_id'], $pendingMessage['content'].$content_tail);
						
						if(!empty($rtn1['error'])){
							\Yii::info('id'.$pendingMessage['id'].' puid:'.$pendingMessage['puid'].' '.$rtn1['error'],"file");
								
							if (strpos(strtoupper($rtn1['error']), "权限对此订单进行留言") !== false){
								$rtn1 = MessageAliexpressApiHelper::addMessage($pendingMessage['buyer_id'], $pendingMessage['seller_id'], $pendingMessage['content'], '');
							}
						}
					} catch (Exception $e) {
						$rtn1 = array('Ack'=>false,'error'=>'Aliexpress API Failed');
					}
				}else if($app_source=='cs_ticket'){
					try{
						if ($pendingMessage['message_type']=='1'){
							$rtn1 = MessageAliexpressApiHelper::addOrderMessage($pendingMessage['order_id'], $pendingMessage['seller_id'], $pendingMessage['buyer_id'], $pendingMessage['content']);
						}else{
							echo "\n liang test smt 01";
							$tmpAddi_info = json_decode($pendingMessage['addi_info'],true);
							$channelId = empty($tmpAddi_info['channelId']) ? '' : $tmpAddi_info['channelId'];
							echo "\n liang test smt 02;";
							echo "\n channelId : $channelId";
							$rtn1 = MessageAliexpressApiHelper::addMessage($pendingMessage['buyer_id'], $pendingMessage['seller_id'], $pendingMessage['content'], $channelId);
						}
						
						if(!empty($rtn1['error'])){
							echo '\n id'.$pendingMessage['id'].' puid:'.$pendingMessage['puid'].' '.$rtn1['error'];
							\Yii::info('id'.$pendingMessage['id'].' puid:'.$pendingMessage['puid'].' '.$rtn1['error'],"file");
							
							if (strpos(strtoupper($rtn1['error']), "权限对此订单进行留言") !== false){
								$rtn1 = MessageAliexpressApiHelper::addMessage($pendingMessage['buyer_id'], $pendingMessage['seller_id'], $pendingMessage['content'], '');
							}
						}
						
					} catch(Exception $e) {
						$rtn1 = array('Ack'=>false,'error'=>'Aliexpress API Failed. app_source'.$app_source);
					}
				}else {
					$rtn1 = array('Ack'=>false,'error'=>'Without this parameter app_source:'.$app_source);
				}
					
				if (isset($rtn1['Ack']) and $rtn1['Ack']){					
					$doneMsgIds["C"][$msg_id] = $msg_id;
					$donePuidOrders["C"][$puid][$pendingMessage['msg_id']] = $pendingMessage['msg_id'];
				}else {
					if (!isset($rtn1['error']))
						$rtn1['error'] = '';
					$doneMsgIds["F"][$msg_id] = $msg_id;
					$addi_info['error'] = $rtn1['error'];
					$doneMsgIds["addi_info"][$msg_id] = json_encode($addi_info);
					$donePuidOrders["F"][$puid][ $pendingMessage['msg_id'] ] = $rtn1['error'];
				}
			}//end of aliexpress api

			if ($platform == 'dhgate'){ //20150706 hqw 添加类型
				if ($app_source == 'tracker'){
					try{ 
						$dhgateinterface_Api = new Dhgateinterface_Api();
						$rtn1 = $dhgateinterface_Api->sendOrderMessage($puid, $pendingMessage['order_id'], $pendingMessage['content'].$content_tail);
					} catch (Exception $e) {
						$rtn1 = array('success'=>false,'error_message'=>'Dhgate API Failed');
					}
				}else if($app_source=='cs_ticket'){
					$tmpAddi_info = json_decode($pendingMessage['addi_info'],true);
					
					//敦煌的站内信回复不需要知道orderid或者buyer_id
					try{
						$rtn1 = MessageDhgateApiHelper::addMessage($puid, $pendingMessage['seller_id'], $tmpAddi_info, $pendingMessage['content']);
					} catch (Exception $e) {
						$rtn1 = array('success'=>false,'error_message'=>'Dhgate API Failed');
					}
				}
			
				if (isset($rtn1['success']) and $rtn1['success']){
					$doneMsgIds["C"][$msg_id] = $msg_id;
					$donePuidOrders["C"][$puid][$pendingMessage['msg_id']] = $pendingMessage['msg_id'];
				}else {
					if (!isset($rtn1['error_message']))
						$rtn1['error_message'] = '';
					$doneMsgIds["F"][$msg_id] = $msg_id;
					$addi_info['error'] = $rtn1['error_message'];
					$doneMsgIds["addi_info"][$msg_id] = json_encode($addi_info);
					$donePuidOrders["F"][$puid][ $pendingMessage['msg_id'] ] = $rtn1['error_message'];
				}
			}//end of dhgate api
			
			if (($platform == 'wish') && ($app_source=='cs_ticket')){ //20150723 hqw 添加类型
					
				$tmpAddi_info = json_decode($pendingMessage['addi_info'],true);
					
				//wish的站内信回复不需要知道orderid或者buyer_id
				try{
					$rtn1 = MessageWishApiHelper::replyTicket($puid, $pendingMessage['seller_id'], $tmpAddi_info, $pendingMessage['content']);
				} catch (Exception $e) {
					$rtn1 = array('success'=>false,'error_message'=>'Wish API Failed');
				}
					
				if (isset($rtn1['success']) and $rtn1['success']){
					$doneMsgIds["C"][$msg_id] = $msg_id;
					$donePuidOrders["C"][$puid][$pendingMessage['msg_id']] = $pendingMessage['msg_id'];
				}else {
					if (!isset($rtn1['error_message']))
						$rtn1['error_message'] = '';
					$doneMsgIds["F"][$msg_id] = $msg_id;
					$addi_info['error'] = $rtn1['error_message'];
					$doneMsgIds["addi_info"][$msg_id] = json_encode($addi_info);
					$donePuidOrders["F"][$puid][ $pendingMessage['msg_id'] ] = $rtn1['error_message'];
				}
			}//end of wish api
			
			if ($platform == 'priceminister'){ //201960602 lwj 添加类型
						 
				if($app_source=='tracker'){
					try{ 
						$rtn1 = MessagePriceministerApiHelper::addItemMessage($puid, $pendingMessage['seller_id'], $pendingMessage['content'],$item_id);
						if(!$rtn1["Ack"]){
						    \Yii::info('id'.$pendingMessage['id'].' puid:'.$pendingMessage['puid'].' '.$rtn1['error'],"file");
						}
// 						if(!empty($rtn1['error'])){
// 							\Yii::info('id'.$pendingMessage['id'].' puid:'.$pendingMessage['puid'].' '.$rtn1['error'],"file");
								
// 							if (strpos(strtoupper($rtn1['error']), "权限对此订单进行留言") !== false){
// 								$rtn1 = MessageAliexpressApiHelper::addMessage($pendingMessage['buyer_id'], $pendingMessage['seller_id'], $pendingMessage['content'], '');
// 							}
// 						}
					} catch (Exception $e) {
						$rtn1 = array('Ack'=>false,'error'=>'priceminster API Failed');
					}
				}else if($app_source=='cs_ticket'){
					try{
					    $rtn1 = MessagePriceministerApiHelper::addItemMessage($puid, $pendingMessage['seller_id'], $pendingMessage['content'],$item_id);
					    
// 						if ($pendingMessage['message_type']=='1'){
// 							$rtn1 = MessagePriceministerApiHelper::addItemMessage($pendingMessage['order_id'], $pendingMessage['seller_id'], $pendingMessage['buyer_id'], $pendingMessage['content']);
// 						}else{
// 							$tmpAddi_info = json_decode($pendingMessage['addi_info'],true);
// 							$channelId = empty($tmpAddi_info['channelId']) ? '' : $tmpAddi_info['channelId'];
							
// 							$rtn1 = MessageAliexpressApiHelper::addMessage($pendingMessage['buyer_id'], $pendingMessage['seller_id'], $pendingMessage['content'], $channelId);
// 						}
						
						if(!$rtn1["Ack"]){
							\Yii::info('id'.$pendingMessage['id'].' puid:'.$pendingMessage['puid'].' '.$rtn1['error'],"file");
							
// 							if (strpos(strtoupper($rtn1['error']), "权限对此订单进行留言") !== false){
// 								$rtn1 = MessageAliexpressApiHelper::addMessage($pendingMessage['buyer_id'], $pendingMessage['seller_id'], $pendingMessage['content'], '');
// 							}
						}
						
					} catch(Exception $e) {
						$rtn1 = array('Ack'=>false,'error'=>'priceminster API Failed. app_source'.$app_source);
					}
				}else {
					$rtn1 = array('Ack'=>false,'error'=>'Without this parameter app_source:'.$app_source);
				}
					
				if (isset($rtn1['Ack']) and $rtn1['Ack']){					
					$doneMsgIds["C"][$msg_id] = $msg_id;
					$donePuidOrders["C"][$puid][$pendingMessage['msg_id']] = $pendingMessage['msg_id'];
				}else {
					if (!isset($rtn1['error']))
						$rtn1['error'] = '';
					$doneMsgIds["F"][$msg_id] = $msg_id;
					$addi_info['error'] = $rtn1['error'];
					$doneMsgIds["addi_info"][$msg_id] = json_encode($addi_info);
					$donePuidOrders["F"][$puid][ $pendingMessage['msg_id'] ] = $rtn1['error'];
				}
			}//end of priceminster api
			
			if ($app_source=='tracker'){
				$donePuidOrdersId[$puid][$pendingMessage['msg_id']] = $pendingMessage['order_id'];
			}else{
				$donePuidOrdersId[$puid][$pendingMessage['msg_id']] = $pendingMessage['ticket_id'];
			}
			
			$current_time=explode(" ",microtime()); $start2_time=round($current_time[0]*1000+$current_time[1]*1000);
			TrackingAgentHelper::extCallSum('CS.MS.'.$platform, $start2_time - $start1_time );

			//step 2: set this message id is complete	
		}//end of each pendingMessage
		
		//step 3, bulk update all complete flag
		$id_array = isset($doneMsgIds["C"])?$doneMsgIds["C"]:array();
		$now_str = date('Y-m-d H:i:s');
		if (!empty($id_array)){ 
			$command = Yii::$app->db->createCommand("update message_api_queue set status='C' ,update_time='$now_str'
			       where id in (". implode(",", $id_array) .")"  );
			$affectRows = $command->execute();
		}
		
		$lastPuid = 0;
		
		if (!empty($donePuidOrders["C"])){
			foreach ($donePuidOrders["C"] as $puid1=> $internal_msg_ids){
				foreach ($internal_msg_ids as $internal_msg_id){
					 
					if ($app_source=='tracker'){  //20150706 hqw 添加类型
						$command = Yii::$app->subdb->createCommand("update cs_message set status='C' where id  = $internal_msg_id"  );
					}else{
						$command = Yii::$app->subdb->createCommand("update cs_ticket_message set status='C' where msg_id  = '$internal_msg_id'"  );
					}
					$affectRows = $command->execute();
					
					if ($app_source=='tracker'){  //20150706 hqw 添加类型
						//update lt tracking , set msg setn error = 'Clear' for those , by order id as key
						$order_id = $donePuidOrdersId[$puid1][$internal_msg_id];
						$command = Yii::$app->subdb->createCommand("update lt_tracking set msg_sent_error='C' where order_id  = :order1	 and
												not exists (select 1 from cs_message where status<>'C' and order_id= :order2	)  "  );
						$command->bindValue(':order1', $order_id, \PDO::PARAM_STR);
						$command->bindValue(':order2', $order_id, \PDO::PARAM_STR);
						$affectRows = $command->execute();
					}else{
						//update lt cs_ticket_session , set msg setn error = 'Clear' for those , by order id as key
						$ticket_id = $donePuidOrdersId[$puid1][$internal_msg_id];
						
						//某个会话 发送站内信或订单留言成功时更新msg_sent_error
						MessageApiHelper::setMsgSendSuccess($ticket_id);
					}
					
				}//end of each orderid
			}
		}//end of each complete
		
		//step 4, update each failed, with error message in addi info
		if (isset($doneMsgIds["F"])){
			foreach ($doneMsgIds["F"] as $msg_id){
				$command = Yii::$app->db->createCommand("update message_api_queue set status='F',   update_time='$now_str',
						addi_info=:addi_info  where id  = $msg_id"  );
				$command->bindValue(':addi_info', $doneMsgIds["addi_info"][$msg_id], \PDO::PARAM_STR);
				$affectRows = $command->execute();
			}
		}
		
		$lastPuid = 0; //$donePuidOrders["F"][$puid][ $pendingMessage['order_id'] ] = $rtn1['error'];
		if (!empty($donePuidOrders["F"])){
			foreach ($donePuidOrders["F"] as $puid1=> $orderids){
				foreach ($orderids as $internal_msg_id=>$errMsg){
					
					if ($app_source=='tracker'){  //20150706 hqw 添加类型
						$theMessage = Message::find()->where(array("id"=>$internal_msg_id))->asArray()->one();
						$addi_info = json_decode($theMessage['addi_info'],true);
						$addi_info['error'] = $errMsg;
						
						$command = Yii::$app->subdb->createCommand("update cs_message set status='F', addi_info=:addi_info where id  = $internal_msg_id"  );
						
						$command->bindValue(':addi_info', json_encode($addi_info), \PDO::PARAM_STR);
						$affectRows = $command->execute();
						
						$orderid = $donePuidOrdersId[$puid1][$internal_msg_id];
						TrackingHelper::setMsgSendError($orderid,$errMsg);
					}else{
						$theMessage = TicketMessage::find()->where(array("msg_id"=>$internal_msg_id))->asArray()->one();
						$addi_info = json_decode($theMessage['addi_info'],true);
						$addi_info['error'] = $errMsg;
						
						$command = Yii::$app->subdb->createCommand("update cs_ticket_message set status='F', addi_info=:addi_info where msg_id = $internal_msg_id"  );
						
						$command->bindValue(':addi_info', json_encode($addi_info), \PDO::PARAM_STR);
						$affectRows = $command->execute();
						
						$ticket_id = $donePuidOrdersId[$puid1][$internal_msg_id];
						
						//某个会话 发送站内信或订单留言失败时更新msg_sent_error
						MessageApiHelper::setMsgSendError($ticket_id,$errMsg);
					}
				 
				}//end of each orderid
			}//end of each failed
		}
		
		return $rtn;
	}
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 根据这个order或者track no的详细信息，来生成17Track的广告小尾巴
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param  puid                    
	 * @param  order id    平台order id     如果puid 和 order id 为空，会自动使用参数 tracking的值来
	 * @param  tracking    array of this tracking. 如果该参数非空，那么前面2个参数会自动被ignore 
	 +---------------------------------------------------------------------------------------------
	 * @return				tail wording or '' if error
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		yzq		2015/2/9				初始化
	 +---------------------------------------------------------------------------------------------
	 **/	
	public static function make17TrackMessageTail($puid=0, $order_id='',$aTracking=array()){
		return "";
		/*sophia suggest wording:
		 * To view detailed transportation status, please visit 
		 * http://www.17Track.net/en/result/post.shtml?o=xlb&nums=xxxxxx 
		 * 
		 * 17Track sugguest link:
		 *  1,首页我们才会自动判断,其它页面都是需要url指定的
			2,物流号,除了刚才发的链接是邮政的,快递的都是下面这种,数字部分就是API中的渠道ID
			  http://www.17track.net/en/result/express-100001.shtml?nums=123456789 
			3,nums就是单号参数,如果多个单号,可以使用逗号分隔
			4,指定小老板来源,只需要加多个参数即可
		 * */
		$defaultLang = 'en';
		$wordings['en'] = "To view detailed transportation status, please visit: ";
		$wordings['de']="Bitte verfolgen Sie Ihre Sendung unter der Webseite: ";
		$wordings['fr']="A savoir l'information détaillée sur Logistique, Vous pourriez le  rechercher par ce lien: ";

		$wording = $wordings[$defaultLang]; 
		if (empty($aTracking) and !empty($order_id)){
			 
		
			$aTracking = Tracking::find()->andWhere("order_id=:order_id",array(':order_id'=>$order_id) )
							->asArray()
							->one();
		}
		
		if (empty($aTracking) or $aTracking['carrier_type']==888000001)
			return '';
		
		if ($aTracking['carrier_type'] > 0)
			$url = "http://www.17track.net/en/result/express-".$aTracking['carrier_type'].".shtml?o=xlb&nums=".$aTracking['track_no'];
		else 
			$url = "http://www.17Track.net/en/result/post.shtml?o=xlb&nums=".$aTracking['track_no'] ;
		
		return $wording.$url;
	} //end of function make17TrackMessageTail
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 把Buffer data 放到 Message Queue db里面 ，批量insert 到数据库中
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 +---------------------------------------------------------------------------------------------
	 * @return array
	 *
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		hqw		2015/7/7				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function postMsgOrOrdermsgApiQueueBufferToDb(){
		//use sql PDO, not Model here, for best performance
		$Datas = self::$Insert_Message_Queue_Buffer;
		self::$Insert_Message_Queue_Buffer = array();
		
		SQLHelper::groupInsertToDb("message_api_queue", $Datas, 'db');

	}//end of function postMessageBufferToDb
	
	
}
