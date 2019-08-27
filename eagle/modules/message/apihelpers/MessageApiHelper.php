<?php
namespace eagle\modules\message\apihelpers;
error_reporting(0);

use yii;
use eagle\modules\message\apihelpers\MessageAliexpressApiHelper;
use eagle\models\SaasMessageAutosync;
use eagle\models\SaasAliexpressUser;
use \Exception;
use eagle\modules\util\helpers\OperationLogHelper;
use eagle\modules\message\apihelpers\MessageDhgateApiHelper;
use eagle\models\SaasDhgateUser;
use eagle\modules\message\models\Customer;
use eagle\modules\message\models\TicketSession;
use eagle\modules\message\models\TicketMessage;
use yii\db\Query;
use yii\data\Pagination;
use eagle\modules\tracking\helpers\TrackingRecommendProductHelper;
use \eagle\modules\message\helpers\MessageBGJHelper;
use eagle\models\SaasWishUser;
use eagle\modules\message\apihelpers\MessageWishApiHelper;
use eagle\models\SaasEbayUser;
use eagle\models\SaasEbayAutosyncstatus;
use eagle\modules\tracking\models\Tracking;
use eagle\modules\platform\apihelpers\PlatformAccountApi;
use eagle\modules\util\helpers\ConfigHelper;
use eagle\modules\order\models\OdOrder;
use eagle\modules\order\helpers\OrderTrackerApiHelper;
use eagle\modules\util\helpers\SysLogHelper ;
use eagle\models\message\CustomerMsgTemplateDetail;
use eagle\models\message\CustomerMsgTemplate;
use eagle\modules\message\apihelpers\MessageCdiscountApiHelper;
use eagle\models\SaasCdiscountUser;
use yii\helpers\Url;
use eagle\modules\order\helpers\CdiscountOrderInterface;
use eagle\models\SaasPriceministerUser;
use eagle\modules\util\helpers\SQLHelper;
use eagle\modules\message\models\EdmSentHistory;
use eagle\modules\util\helpers\TimeUtil;
use eagle\modules\dash_board\apihelpers\DashBoardStatisticHelper;
use eagle\modules\util\helpers\RedisHelper;
use eagle\modules\permission\helpers\UserHelper;

class MessageApiHelper{
	/**
	 * 根据传进来的$platformType来调用不同平台的接口来保存站内信信息
	 * 对于敦煌只需要一个接口就搞掂
	 * 
	 * @param $platformType	平台类型:wish/amazon/ebay/aliexpress/dhagte
	 * @param $uid	小老板平台uid:1
	 * @param $sellerloginid	速卖通上卖家id
	 * @param $start_time	拉取开始时间:时间戳
	 * @param $end_time	拉取结束时间:时间戳
	 * @param $platform_uid saas_xxxx_user 表的主键id 暂时敦煌有用
	 * @return array('success'=>true,'error'=>'');
	  		success :返回是否发送成功true false
			error: 错误信息
	 * 
	 * +-------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		hqw		2015/7/3				初始化
	 * +-------------------------------------------------------------------------------------------
	 */
	public static function getPubMsg($platformType, $uid, $sellerloginid, $start_time, $end_time, $platform_uid='', $apiVersion='1.0'){
		$result = array();
		
		//不再在这里删除发送成功的数据，在每次拉取后再删除特定的ticket_id
		
		if ($platformType == 'aliexpress'){
			if($apiVersion == '1.0'){
				$result = MessageAliexpressApiHelper::getMsgAliexpress($platformType,$uid,$sellerloginid, $start_time, $end_time);
			}else if($apiVersion == 'Relation1.0'){
				$result = MessageAliexpressApiHelper::getMsgAliexpressRelation($platformType,$uid,$sellerloginid, $start_time, $end_time, 2);
			}
		}
		
		if ($platformType == 'dhgate'){
			$result = MessageDhgateApiHelper::getMsgDhgateAll($uid,$sellerloginid, $start_time, $end_time, $platform_uid);
		}
		
		if ($platformType == 'wish'){
			$result = MessageWishApiHelper::getMsgWishAwaiting($uid, $sellerloginid, $platform_uid);
		}
		
		if ($platformType == 'ebay'){
			$result = MessageEbayApiHelper::getMessages($uid, $sellerloginid, $start_time, $end_time);
			$result = MessageEbayApiHelper::getMessagesSent($uid, $sellerloginid, $start_time, $end_time);
		}
		
		if ($platformType == 'cdiscount'){
		    $result = MessageCdiscountApiHelper::getCdiscountOrderMsg($uid, $sellerloginid, $start_time, $end_time);
		}
		
		if ($platformType == 'priceminister'){
		    $result = MessagePriceministerApiHelper::getPriceministerItemMsg($uid, $sellerloginid);
		}
		
		if (count($result) == 0){
			$result = array('success' => false,'error' => __FUNCTION__.'No call to '.$platformType);
		}
		
		return $result;
	}
	
	/**
	 * 根据传进来的$platformType来调用不同平台的接口来保存订单留言
	 *
	 * @param $platformType	平台类型:wish/amazon/ebay/aliexpress/dhagte
	 * @param $uid	小老板平台uid:1
	 * @param $sellerloginid	速卖通上卖家id
	 * @param $start_time	拉取开始时间:时间戳
	 * @param $end_time	拉取结束时间:时间戳
	 * @return array('success'=>true,'error'=>'');
	 		success :返回是否发送成功true false
			error: 错误信息
	 * 
	 * +-------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		hqw		2015/7/3				初始化
	 * +-------------------------------------------------------------------------------------------
	 */
	public static function getPubOrderMsg($platformType, $uid, $sellerloginid, $start_time, $end_time, $apiVersion='1.0'){
		$result = array();
		
		//不再在这里删除发送成功的数据，在每次拉取后再删除特定的ticket_id
	
		if ($platformType == 'aliexpress'){
			if($apiVersion == '1.0'){
				$result = MessageAliexpressApiHelper::getOrderMsgAliexpress($platformType,$uid,$sellerloginid, $start_time, $end_time);
			}else if($apiVersion == 'Relation1.0'){
				$result = MessageAliexpressApiHelper::getMsgAliexpressRelation($platformType,$uid,$sellerloginid, $start_time, $end_time, 1);
			}
		}
	
		if (count($result) == 0){
			$result = array('success' => false,'error' => __FUNCTION__.'No call to '.$platformType);
		}
	
		return $result;
	}
	
	/**
	 * 不同平台保存cs_customer,cs_ticket_session,cs_ticket_message共用
	 * 
	 * @param $puid //小老板平台uid
	 * @param $selleruserid //saas_xxxx_user 上的sellerloginid
	 * @param $platformType //平台类型
	 * @param $msgKeyName //cs_ticket_message判断msg是否唯一字段
	 * @param $msgOne = array(
	 * session_id //每个平台的会话Id
	 * msgKey //cs_ticket_message 唯一值的判断
	 * buyer_id //买家id
	 * buyer_nickname //买家呢称
	 * isSend //是否发送
	 * seller_nickname //卖家呢称
	 * msgCreateTime //msg平台生成时间
	 * content //消息内容
	 * messageType //msg类型 1--订单留言,2--站内信,3--平台消息
	 * list_related_id //cs_ticket_session的related_id 这个需要看各个平台的区别，有的平台会将会话对象设置在session有的会设置在cs_ticket_message
	 * list_related_type //cs_ticket_session的related_type
	 * app_time //eagle系统时间,速卖通用的是西七区时间有冬夏令时之分，所以要转为北京时间。其它平台冇需要就知需要直接用msgCreateTime
	 * haveFile //是否有文件
	 * fileUrl //文件url
	 * related_id //订单单号/商品sku
	 * related_type //相关类别，P：商品，O：订单, S:系统平台
	 * msg_contact //msg原始数据内容
	 * 
	 * recieverId //敦煌用到的通信会话id
	 * sellerOrgId //敦煌返回的卖家id
	 * original_msg_type //原始信息类型 --暂时敦煌用到
	 * msgTitle //msg标题
	 * list_contact //msg_list原始数据内容
	 * msgDetailsTitle //msg明细标题
	 * );
	 * @return array('success'=>true,'error'=>'');
	 		success :返回是否发送成功true false
			error: 错误信息
	 * 
	 * +-------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		hqw		2015/7/3				初始化
	 * +-------------------------------------------------------------------------------------------
	 */
	public static function _insertMsg($puid, $selleruserid, $platformType, $msgKeyName, &$msgOne){
		 
		//根据平台类型$platformType和会话session_id,来确定系统是否存在该站内信的会话列表信息
		$TSession_obj = TicketSession::findOne(['session_id'=>$msgOne['session_id'],'platform_source'=>$platformType]);
		
		//记录需要更新管理客户表的数据数组
		if (in_array($msgOne['messageType'], array('1','2'))){
			$customerParams = array(
					'platform_source' => $platformType,
					'seller_id' => $selleruserid,
					'customer_id' => $msgOne['buyer_id'],
					'customer_nickname' => $msgOne['buyer_nickname'],
					'recieverId' => empty($msgOne['recieverId']) ? '' : $msgOne['recieverId'],
					'isCreateCustomer' => 'Y',
					'isSend' => '0',
			);
		}
		
		try{
			//假如之前存在就更新列表的是否已读，最后回复日期，更新日期等信息。没有就插入新的列表信息
			if ($TSession_obj !== null){
				$TSession_obj->updated = date('Y-m-d H:i:s', time ());
		
				if (strtotime($msgOne['msgCreateTime']) > strtotime($TSession_obj->lastmessage)){
					$TSession_obj->lastmessage = $msgOne['msgCreateTime'];
					
					//cdiscount需要存两个新的变量
					if(isset($msgOne['session_type'])){
					    $TSession_obj->session_type = $msgOne['session_type'];
					}
					if(isset($msgOne['session_status'])){
					    $TSession_obj->session_status = $msgOne['session_status'];
					    if($TSession_obj->session_status != $msgOne['session_status']){//查看是否状态改变
					        $status_change = true;
					    }else{
					        $status_change = false;
					    }
					}
					
					if (!empty($customerParams))
						$customerParams['last_message_time'] = $msgOne['msgCreateTime']; //客户管理表最后回复时间
		            //itemid暂时只有priceminster
					if(!empty($msgOne["itemid"])){
					    $TSession_obj->item_id = $msgOne["itemid"];
					}
					
					if (mb_strlen(strip_tags($msgOne['content']),'utf-8') > 20)
						$TSession_obj->last_omit_msg = mb_substr(strip_tags($msgOne['content']), 0, 20,"UTF-8")."...\n";
					else
						$TSession_obj->last_omit_msg = $msgOne['content'];
						
					//如果最后一条记录为发送就设置has_replied=1,否则has_replied=0
					$TSession_obj->has_replied = $msgOne['isSend'];
					
					//判断是否有额外的信息
					if (!empty($msgOne['list_addi_info'])){
						$addi_info = json_decode($TSession_obj->addi_info,true);
						
						foreach($msgOne['list_addi_info'] as $key => $value){
							$addi_info[$key] = $value;
						}
						
						$TSession_obj->addi_info = json_encode($addi_info);
					}
					
					if ($msgOne['messageType'] == '2' && $msgOne['isSend'] == '0'){
						if(!empty($msgOne['related_id']))
							$TSession_obj->related_id = $msgOne['related_id'];
						else
							$TSession_obj->related_id = '';
							
						if(!empty($msgOne['related_type']))
							$TSession_obj->related_type = $msgOne['related_type'];
						else
							$TSession_obj->related_type = '';
					}
				}
				if($platformType == "cdiscount"){//暂时针对CD对OMS推送
				    //维护之前错误的信息
				    $TSession_obj->buyer_id = $msgOne['buyer_id'];
				    $TSession_obj->seller_nickname = $msgOne['seller_nickname'];
				    $TSession_obj->buyer_nickname = $msgOne['buyer_nickname'];
				    
				    if($TSession_obj->save (false)){
				        \Yii::info('puid '.$puid.' success insert cdiscount session :'.json_encode($msgOne),'file');
				        if(!empty($msgOne['related_id'])&&$status_change&&!empty($msgOne['related_type'])){
				           if($msgOne['related_type']=='O'){
				               $sessionStaus = [
				                   "Open"=>"IN_ISSUE",
				                   "Closed"=>"END_ISSUE",
				               ];
				               $IssueStatus = !empty($sessionStaus[$msgOne['session_status']])?$sessionStaus[$msgOne['session_status']]:"";
				               $updateIssueStatus_result = self::updateIssueStatusToOms($msgOne['related_id'],$IssueStatus,$platformType);
				               if(!$updateIssueStatus_result){
				                   echo $updateIssueStatus_result['message'];
				               }
				           }
				        }
				    }else{
				        \Yii::info('puid '.$puid.' fail insert cdiscount session :'.json_encode($msgOne).' fail: '.json_encode($TSession_obj->errors),'file');
				    }
				}else{
				    $TSession_obj->save (false);
				}
			}else {
				$TSession_obj = new TicketSession ();
		
				$TSession_obj->platform_source = $platformType;
				$TSession_obj->message_type = $msgOne['messageType'];
				$TSession_obj->seller_id = empty($msgOne['sellerOrgId']) ? $selleruserid : $msgOne['sellerOrgId']; //敦煌的登陆账号有后台id
				if (!empty($msgOne['list_related_id'])){
					$TSession_obj->related_id = $msgOne['list_related_id'];
				}
				if (!empty($msgOne['list_related_type'])){
					$TSession_obj->related_type = $msgOne['list_related_type'];
				}
				//cdiscount需要存两个新的变量
				if(isset($msgOne['session_type'])){
				    $TSession_obj->session_type = $msgOne['session_type'];
				}
				if(isset($msgOne['session_status'])){
				    $TSession_obj->session_status = $msgOne['session_status'];
				}
				
				//itemid暂时只有priceminster
				if(!empty($msgOne["itemid"])){
				    $TSession_obj->item_id = $msgOne["itemid"];
				}
				
				$TSession_obj->buyer_id = $msgOne['buyer_id'];
				$TSession_obj->session_id = $msgOne['session_id'];
		
				$TSession_obj->seller_nickname = $msgOne['seller_nickname'];
				$TSession_obj->buyer_nickname = $msgOne['buyer_nickname'];
		
				//如果拉取有接收的信息就设置为未读
				$TSession_obj->has_replied = $msgOne['isSend'];
				
				if (!empty($customerParams))
					$customerParams['isSend'] = $msgOne['isSend'];
				
				if(!empty($msgOne['original_msg_type']))
					$TSession_obj->original_msg_type = $msgOne['original_msg_type'];
		
				$TSession_obj->created = date('Y-m-d H:i:s', time ());
				$TSession_obj->updated = date('Y-m-d H:i:s', time ());
				$TSession_obj->lastmessage = $msgOne['msgCreateTime'];
				if (!empty($customerParams))
					$customerParams['last_message_time'] = $msgOne['msgCreateTime']; //客户管理表最后回复时间
		
				if (mb_strlen(strip_tags($msgOne['content']),'utf-8') > 20)
					$TSession_obj->last_omit_msg = mb_substr(strip_tags($msgOne['content']), 0, 20,"UTF-8")."...\n";
				else
					$TSession_obj->last_omit_msg = $msgOne['content'];
				
				if (!empty($msgOne['list_contact']))
					$TSession_obj->list_contact = $msgOne['list_contact'];
				
				if (!empty($msgOne['msgTitle']))
					$TSession_obj->msgTitle = $msgOne['msgTitle'];
				
				//判断是否有额外的信息
				if (!empty($msgOne['list_addi_info'])){
					$addi_info = json_decode($TSession_obj->addi_info,true);
				
					foreach($msgOne['list_addi_info'] as $key => $value){
						$addi_info[$key] = $value;
					}
				
					$TSession_obj->addi_info = json_encode($addi_info);
				}
				
				if ($msgOne['messageType'] == '2' && $msgOne['isSend'] == '0'){
					if(!empty($msgOne['related_id']))
						$TSession_obj->related_id = $msgOne['related_id'];
					else
						$TSession_obj->related_id = '';
						
					if(!empty($msgOne['related_type']))
						$TSession_obj->related_type = $msgOne['related_type'];
					else
						$TSession_obj->related_type = '';
				}
				if($platformType == "cdiscount"){//暂时针对CD对OMS推送
				    if($TSession_obj->save (false)){
				        \Yii::info('puid '.$puid.' success insert cdiscount session :'.json_encode($msgOne),'file');
				        if(!empty($msgOne['related_id'])&&!empty($msgOne['related_type'])){
				           if($msgOne['related_type']=='O'){
				               $sessionStaus = [
				                   "Open"=>"IN_ISSUE",
				                   "Closed"=>"END_ISSUE",
				               ];
				               $IssueStatus = !empty($sessionStaus[$msgOne['session_status']])?$sessionStaus[$msgOne['session_status']]:"";
				               $updateIssueStatus_result = self::updateIssueStatusToOms($msgOne['related_id'],$IssueStatus,$platformType);
				               if(!$updateIssueStatus_result){
				                   echo $updateIssueStatus_result['message'];
				               }
				           }
				        }
				    }else{
				        \Yii::info('puid '.$puid.' fail insert cdiscount session :'.json_encode($msgOne).' fail: '.json_encode($TSession_obj->errors),'file');
				    }
				}else{
				    $TSession_obj->save (false);
				}
			}
				
			//cs_customer 客户管理表更新最后一次回复时间,速卖通需要对比站内信的日期才能知道last_message_time
			if (!empty($customerParams))
				if (isset($customerParams['last_message_time']))
					MessageApiHelper::customerUpdateLastTime($customerParams);
				
			//通过小老板系统的$TSession_obj->ticket_id，会话$msgOne['relationId'],messageId 来检测系统是否存在该记录。
			$TMsg_obj = TicketMessage::findOne(['ticket_id'=>$TSession_obj->ticket_id,
					'session_id'=>$msgOne['session_id'],$msgKeyName=>$msgOne['msgKey']]);
				
			if ($TMsg_obj == null){
				$TMsg_obj = new TicketMessage();
		
				$TMsg_obj->ticket_id = $TSession_obj->ticket_id;
				$TMsg_obj->session_id = $msgOne['session_id'];
				$TMsg_obj->message_id = $msgOne['msgKey'];
		
				if(!empty($msgOne['related_id']))
					$TMsg_obj->related_id = $msgOne['related_id'];
				else 
					$TMsg_obj->related_id = '';
				
				if(!empty($msgOne['related_type']))
					$TMsg_obj->related_type = $msgOne['related_type'];
				else
					$TMsg_obj->related_type = '';
		
				$TMsg_obj->send_or_receiv = $msgOne['isSend'];
				$TMsg_obj->content = $msgOne['content'];
				$TMsg_obj->headers = "";
				$TMsg_obj->has_read = $msgOne['isSend'];
				
				$msgOne['msg_contact'] = json_encode($msgOne['msg_contact']);
				$msgOne['msg_contact'] = gzcompress($msgOne['msg_contact'], 9);//压缩级别为9
				$msgOne['msg_contact'] = base64_encode($msgOne['msg_contact']);
				$TMsg_obj->msg_contact = addslashes($msgOne['msg_contact']);
// 				$TMsg_obj->msg_contact = json_encode($msgOne['msg_contact']);
				
				$TMsg_obj->created = date('Y-m-d H:i:s', time ());
				$TMsg_obj->updated = date('Y-m-d H:i:s', time ());
		
				$TMsg_obj->app_time = $msgOne['app_time'];
				$TMsg_obj->platform_time = $msgOne['msgCreateTime'];
		
				if(!empty($msgOne['haveFile'])){
					$TMsg_obj->haveFile = $msgOne['haveFile'];
					$TMsg_obj->fileUrl = $msgOne['fileUrl'];
				}else {
					$TMsg_obj->haveFile = 0;
					$TMsg_obj->fileUrl = '';
				}
				//暂时只有priceminster用到
				if (isset($msgOne['is_claim'])){
				    $addi_info = json_decode($TMsg_obj->addi_info,true);
				
				    foreach($msgOne['is_claim'] as $key => $value){
				        $addi_info[$key] = $value;
				    }
				
				    $TMsg_obj->addi_info = json_encode($addi_info);
				}
				
				if(!empty($msgOne['msgDetailsTitle']))
					$TMsg_obj->headers = $msgOne['msgDetailsTitle'];
				
				$TMsg_obj->save (false);
			}
			//cdiscount由于message时间会变所以要更新
		    if($TMsg_obj != null&&$platformType == "cdiscount"){
		        if($msgOne['app_time'] > $TMsg_obj->app_time){
		            $TMsg_obj->app_time = $msgOne['app_time'];
		        }
		        if($msgOne['msgCreateTime'] > $TMsg_obj->platform_time){
		            $TMsg_obj->platform_time = $msgOne['msgCreateTime'];
		            $TMsg_obj->created = date('Y-m-d H:i:s', time ());
		            $TMsg_obj->updated = date('Y-m-d H:i:s', time ());
		        }
		        if($TMsg_obj->send_or_receiv != $msgOne['isSend']){//维护错误消息
		            $TMsg_obj->send_or_receiv = $msgOne['isSend'];
		        }
		        $TMsg_obj->save (false);
		    }
		}catch (\Exception $ex){
			\Yii::error(__CLASS__.' '.__FUNCTION__.' '.$platformType.' msg '.print_r($ex,true));
			return array('success'=>false,'error'=>__CLASS__.' '.__FUNCTION__.' '.$platformType.' msg '.print_r($ex,true));
		}
		
		return array('success'=>true,'error'=>'');
	}
	
	/**
	 * 重新拉取站内信时，删除之前系统成功发送，但没有同步到eagle系统中的cs_ticket_message数据
	 * @param 
	 * $uid eagle系统uid
	 * $platformType 平台类型
	 * $msgType	站内信类型 :1--订单，2--站内信
	 * 
	 * @return array('success'=>true,'error'=>'');
	 		success :返回是否发送成功true false
			error: 错误信息
	 * 
	 * +-------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		hqw		2015/7/3				初始化
	 * +-------------------------------------------------------------------------------------------
	 */
	public static function delSendSuccessMsgOrOrder($uid, $platformType, $msgType, $touched_customer_ids){
		if(count($touched_customer_ids) == 0){
			return array('success'=>true,'error'=>'');
		}		
		 
		$sqlStr = '';
		$sqlArr = array();
		
		$sqlArr[':platform_source'] = $platformType;
		
		$isEmptyMsg = \Yii::$app->subdb->createCommand("select b.platform_source from cs_ticket_message a
			left join cs_ticket_session b on b.ticket_id=a.ticket_id
			where b.ticket_id=a.ticket_id and b.platform_source=:platform_source
			and b.message_type in (".$msgType.") and a.status='C' and IFNULL(a.message_id,'')='' limit 1 ",$sqlArr)->queryAll();
		
		if(count($isEmptyMsg) == 0){
			return array('success'=>true,'error'=>'');
		}
		
		$result = \Yii::$app->subdb->createCommand("delete a from cs_ticket_message a
			left join cs_ticket_session b on b.ticket_id=a.ticket_id
			where b.ticket_id=a.ticket_id and b.platform_source=:platform_source 
			and b.message_type in (".$msgType.") and a.status='C' and IFNULL(a.message_id,'')='' ",$sqlArr)->execute();
		
		$result = \Yii::$app->subdb->createCommand("delete a from cs_ticket_session a
			where a.platform_source=:platform_source and a.message_type in (".$msgType.") and IFNULL(a.session_id,'')='' and a.msg_sent_error='C' ",$sqlArr)->execute();
		
		return array('success'=>true,'error'=>'');
	}
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 设置某个会话  发送站内信失败或订单留言失败
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param     $ticket_id    //cs_ticket_session 主键
	 * @param     $error       string of 错误信息
	 +---------------------------------------------------------------------------------------------
	 * @return					array ('success' => true, 'message'='')
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		hqw		2015/7/6				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	 public static function setMsgSendError($ticket_id,$error=''){
		$result ['success'] = true;
		$result ['message'] = '';
	
		$csTSession_obj = TicketSession::find()->where(['ticket_id'=>$ticket_id])->asArray()->one();
		if (empty($csTSession_obj)){
			$result ['success'] = false;
			$result ['message'] = 'Failed to Load object for TicketSession ticket_id '.$ticket_id;
			return $result;
		}
	
		if (!empty($error)){
			$addi_info = json_decode($csTSession_obj['addi_info'],true);
			$addi_info['send_msg_error'] = $error;
			$csTSession_obj['addi_info'] = json_encode($addi_info);
		}
		
		try{
			//msg_sent_error = "Y";
			$command = Yii::$app->subdb->createCommand("update cs_ticket_session set msg_sent_error='Y', addi_info=:addi_info where ticket_id  = :ticket_id"  );
			$command->bindValue(':addi_info', $csTSession_obj['addi_info'], \PDO::PARAM_STR);
			$command->bindValue(':ticket_id', $ticket_id, \PDO::PARAM_STR);
			$affectRows = $command->execute();
			
			//将发送失败的会话对应的customer，msg_sent_error设置为Y
			$customerCommand = Yii::$app->subdb->createCommand("update cs_customer a set msg_sent_error='Y'
				where a.customer_id=:customer_id and exists (select 1 from cs_ticket_session a1
				where a1.msg_sent_error='Y' and a1.buyer_id=a.customer_id
					and a1.seller_id=a.seller_id and a1.buyer_id=:buyer_id)");
				
			$customerCommand->bindValue(':customer_id', $csTSession_obj['buyer_id'], \PDO::PARAM_STR);
			$customerCommand->bindValue(':buyer_id', $csTSession_obj['buyer_id'], \PDO::PARAM_STR);
			$affectcustomerRows = $customerCommand->execute();
		}catch (\Exception $ex){
			\Yii::error('function:'.__FUNCTION__.print_r($ex,true));
			return array('success'=>false,'message'=>'function:'.__FUNCTION__.print_r($ex,true));
		}
	
		return $result;
	}//end of  function
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 设置某个会话  发送站内信或订单留言成功
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param     $ticket_id：cs_ticket_session 主键
	 +---------------------------------------------------------------------------------------------
	 * @return		array ('success' => true, 'message'='')
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		hqw		2015/8/7				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	public static function setMsgSendSuccess($ticket_id){
		$result ['success'] = true;
		$result ['message'] = '';
		
		$tSession_obj = TicketSession::find()->where(['ticket_id'=>$ticket_id])->asArray()->one();
		
		if (empty($tSession_obj)){
			$result ['success'] = false;
			$result ['message'] = 'Failed to Load object for TicketSession ticket_id '.$ticket_id;
			return $result;
		}
		
		try{
			//将发送成功的会话，msg_sent_error设置为C
			$command = Yii::$app->subdb->createCommand("update cs_ticket_session set msg_sent_error='C' where ticket_id = :ticket_id1 and
				not exists (select 1 from cs_ticket_message where status<>'C' and ticket_id= :ticket_id2	)  "  );
			$command->bindValue(':ticket_id1', $ticket_id, \PDO::PARAM_STR);
			$command->bindValue(':ticket_id2', $ticket_id, \PDO::PARAM_STR);
			$affectRows = $command->execute();
			
			//将发送成功的会话对应的customer，msg_sent_error设置为C
			$customerCommand = Yii::$app->subdb->createCommand("update cs_customer a set msg_sent_error='C'
			where a.customer_id=:customer_id and not exists (select 1 from cs_ticket_session a1
			where a1.msg_sent_error='Y' and a1.buyer_id=a.customer_id
				and a1.seller_id=a.seller_id and a1.buyer_id=:buyer_id)");
			
			$customerCommand->bindValue(':customer_id', $tSession_obj['buyer_id'], \PDO::PARAM_STR);
			$customerCommand->bindValue(':buyer_id', $tSession_obj['buyer_id'], \PDO::PARAM_STR);
			$affectcustomerRows = $customerCommand->execute();
		}catch (\Exception $ex){
			\Yii::error('function:'.__FUNCTION__.print_r($ex,true));
			return array('success'=>false,'message'=>'function:'.__FUNCTION__.print_r($ex,true));
		}
		
		return $result;
	}
	
	
	/**
	 +----------------------------------------------------------
	 * 修改或新增saas_message_autosync
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * param
	 * @param$puid	小老板平台uid
	 * @param$platform_uid	saas_xxxx_user 上的主键ID
	 * @param$sellerloginid	速卖通上卖家id   敦煌saas_xxxx_user的sellerloginid
	 * @param$platform_source	平台类型
	 +----------------------------------------------------------
	 * @return array('code'=>ok,'message'=>'');
	 		success :返回是否发送成功ok fail
			message: 错误信息
	 * 
	 * +-------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		hqw		2015/7/8				初始化
	 * +-------------------------------------------------------------------------------------------
	 **/
	public static function setSaasMsgAutosync($puid ,$platform_uid, $sellerloginid, $platform_source){
		try{
			//绑定成功写入同步站内信订单留言队列
			$types = array();
			$is_active = false;
			if ($platform_source == 'aliexpress'){
				//新版速卖通有1个后台job
				//$types = array('msgTime','OrdermsgTime');
				$types = array('msgTime');
				
				$User_obj = SaasAliexpressUser::find()->where('sellerloginid=:p and aliexpress_uid=:a', array(':p' => $sellerloginid,':a'=>$platform_uid))->one();
				$is_active = $User_obj->is_active;
			}
			
			if ($platform_source == 'dhgate'){
				//敦煌有1个后台job
				$types = array('msgTime');
				
				$User_obj = SaasDhgateUser::find()->where('sellerloginid=:p and dhgate_uid=:a', array(':p' => $sellerloginid,':a'=>$platform_uid))->one();
				$is_active = $User_obj->is_active;
			}
			
			if ($platform_source == 'wish'){
				$types = array('msgTime');
				
				$User_obj = SaasWishUser::find()->where('store_name=:p and site_id=:a', array(':p' => $sellerloginid,':a'=>$platform_uid))->one();
				$is_active = $User_obj->is_active;
			}
			
			if ($platform_source == 'ebay'){
				//ebay有1个后台job
				$types = array('msgTime');
				
				// SaasEbayUser 目前没有 is_active 属性 使用SaasEbayAutosyncstatus type=3 代替 
				$User_obj = SaasEbayAutosyncstatus::find()->where(['selleruserid' => $sellerloginid,'ebay_uid'=>$platform_uid ,'type'=>3])->one();
				$is_active = $User_obj->status;
			}
			
			if ($platform_source == 'cdiscount'){
			    //cdiscount有1个后台job
			    $types = array('msgTime');
			    $User_obj = SaasCdiscountUser::find()->where(['username' => $sellerloginid,'site_id'=>$platform_uid])->one();
			    $is_active = $User_obj->is_active;
			}
			
			if ($platform_source == 'priceminister'){
			    //priceminister有1个后台job
			    $types = array('msgTime');
			    $User_obj = SaasPriceministerUser::find()->where(['username' => $sellerloginid,'site_id'=>$platform_uid])->one();
			    $is_active = $User_obj->is_active;
			}
			
			if(!isset($User_obj)) {
// 				exit(json_encode(array("code"=>"fail","message"=>'操作失败'.$sellerloginid.'账号不存在'))); // 后面再json_encode
				return array("code"=>"fail","message"=>'操作失败'.$sellerloginid.'账号不存在');
			}

			//如果用户设置账号不启用,则关闭站内信订单留言的相关同步功能
			SaasMessageAutosync::updateAll(array('is_active'=>$is_active,'update_time'=>time()),
				'sellerloginid=:p and platform_uid=:a and platform_source=:source',
				array(':p' => $sellerloginid,':a'=>$platform_uid,':source'=>$platform_source));
			
			if ($is_active ==1){
				foreach ($types as $type){
					$SAA_obj = SaasMessageAutosync::find()->where('sellerloginid=:sellerloginid and type=:type and platform_source=:source',
							array(':sellerloginid'=>$sellerloginid,':type'=>$type,':source'=>$platform_source))->one();
					if (isset($SAA_obj)){//已经有数据，只要更新
						$SAA_obj->is_active = $is_active;
						$SAA_obj->status = 0;
						$SAA_obj->type=$type;
						$SAA_obj->times=0;
						$SAA_obj->binding_time=time();
						$SAA_obj->update_time = time();
// 						$SAA_obj->start_time=0;
// 						$SAA_obj->end_time=0;
						$SAA_obj->save();
					}else{//新数据，插入一行数据
						$SAA_obj=new SaasMessageAutosync();
						$SAA_obj->uid = $puid;
						$SAA_obj->platform_source = $platform_source;
						$SAA_obj->sellerloginid = $sellerloginid;
						$SAA_obj->platform_uid = $platform_uid;
						$SAA_obj->is_active = $is_active;
						$SAA_obj->status = 0;
						$SAA_obj->type=$type;
						$SAA_obj->times=0;
						$SAA_obj->start_time=0;
						$SAA_obj->end_time=0;
						$SAA_obj->last_time=0;
						$SAA_obj->binding_time=time();
						$SAA_obj->create_time = time();
						$SAA_obj->update_time = time();
						$SAA_obj->save();
					}
				}
			}
		}catch (\Exception $ex){
// 			exit(json_encode(array("code"=>"fail","message"=>$ex->getMessage())));
			return array("code"=>"fail","message"=>$ex->getMessage());
		}
		
// 		exit(json_encode(array("code"=>"ok","message"=>'账号修改成功')));
		return array("code"=>"ok","message"=>'账号修改成功');
	}
	
	
	/**
	 +----------------------------------------------------------
	 * 删除saas_message_autosync
	 * @param	$puid				小老板平台uid
	 * @param	$platform_uid		saas_xxxx_user 上的主键ID
	 * @param	$sellerloginid		速卖通上卖家id   敦煌saas_xxxx_user的sellerloginid
	 * @param	$platform_source	平台类型
	 * @return	array('code'=>ok,'message'=>'')	: success :返回是否发送成功ok failmessage: 错误信息
	 * @author	lzhl	2017/7/25	初始化
	 * +-------------------------------------------------------------------------------------------
	 **/
	public static function delSaasMsgAutosync($puid ,$platform_uid, $sellerloginid, $platform_source){
		try{
			//绑定成功写入同步站内信订单留言队列
			$types = array();
			$is_active = false;
			if ($platform_source == 'aliexpress'){
				//速卖通有2个后台job
				$types = array('msgTime','OrdermsgTime');
			}
				
			if ($platform_source == 'dhgate'){
				//敦煌有1个后台job
				$types = array('msgTime');
			}
				
			if ($platform_source == 'wish'){
				//敦煌有1个后台job
				$types = array('msgTime');
			}
				
			if ($platform_source == 'ebay'){
				//ebay有1个后台job
				$types = array('msgTime');
			}
				
			if ($platform_source == 'cdiscount'){
				//cdiscount有1个后台job
				$types = array('msgTime');
			}
				
			if ($platform_source == 'priceminister'){
				//priceminister有1个后台job
				$types = array('msgTime');
			}
			if(!isset($types)) {
				return array("code"=>"fail","message"=>'操作失败,该平台没有需要删除的消息同步记录');
			}
			//如果用户设置账号不启用,则关闭站内信订单留言的相关同步功能
			SaasMessageAutosync::deleteAll([
				'sellerloginid'=>$sellerloginid,
				'platform_uid'=>$platform_uid,
				'platform_source'=>$platform_source,
				'type'=>$types]);
		}catch (\Exception $ex){
			// 			exit(json_encode(array("code"=>"fail","message"=>$ex->getMessage())));
			return array("code"=>"fail","message"=>$ex->getMessage());
		}
	
		// 		exit(json_encode(array("code"=>"ok","message"=>'账号修改成功')));
		return array("code"=>"ok","message"=>'客服消息同步记录删除成功');
	}
	
	/**
	 * 将队列数据message_api_queue,cs_ticket_message发送失败的数据转为待发送状态
	 * 支持批量,单个session,或单条msg来进行将发送失败的数据转为待发送状态
	 * 
	 * @param $selleruserid：卖家id	必填
	 * @param $platform_source：平台类型	必填
	 * @param $msgType：站内信类型 1--订单，2--站内信  必填
	 * 
	 * @param $ticket_id //小老板后台会话Id 当需要将某个会话全部重新发送需要传送该值  	可选
	 * @param $msgid //小老板后台会话明细Id 当需要将某条会话记录重新发送需要传送该值	可选
	 * 
	 * @return array('success'=>true,'error'=>'');
	 		success :返回是否发送成功true false
			error: 错误信息
			
	 * use eagle\modules\message\apihelpers\MessageApiHelper;
	 * Sample:
	 * MessageApiHelper::msgStatusFailToPending ($selleruserid=88,$platform_source='',$msgType=1,$ticket_id='',$msgid='')
	 * 
	 * +-------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		hqw		2015/7/8				初始化
	 * +-------------------------------------------------------------------------------------------
	 */
	public static function msgStatusFailToPending($selleruserid,$platform_source,
			$msgType='1',$ticket_id='',$msgid=''){
		$sqlStr = '';
		$sqlArr = array();
		
		$sqlArr[':platform_source'] = $platform_source;
		$sqlArr[':seller_id'] = $selleruserid;
		
		
		if ($ticket_id != ""){
			$sqlStr = " and a.ticket_id=:ticket_id";
			$sqlArr[':ticket_id'] = $ticket_id;
		}
		
		if ($msgid != ""){
			$sqlStr .= " and a.msg_id=:msg_id";
			$sqlArr[':msg_id'] = $msgid;
		}
		
		if (!empty($msgType)){
			$sqlStr .= "  and b.message_type=:message_type ";
			$sqlArr[':message_type'] = $msgType;
		}
		
		$now = date('Y-m-d H:i:s');
	
		
		//step 1 更新message 状态， 时间
		$command = \Yii::$app->subdb->createCommand("update cs_ticket_message a
			left join cs_ticket_session b on b.ticket_id=a.ticket_id
			set a.status='P' , app_time = '$now' , platform_time = '$now'
			where b.ticket_id=a.ticket_id and b.platform_source=:platform_source 
				and b.seller_id=:seller_id and a.status='F' ".$sqlStr,$sqlArr);
		$result =  $command->execute();
		
		//echo "  $result  || ".$command->getRawSql(); //test kh
		
		//step 2 更新queue中的状态
		$command = \Yii::$app->db->createCommand("update message_api_queue a set a.status='P' 
				where a.platform=:platform_source and a.seller_id=:seller_id  and a.status='F' ".
				$sqlStr,$sqlArr);
		$result =  $command->execute();
		//echo "  $result  || ".$command->getRawSql(); //test kh
		//step 3 更新 session 表 的时间
		
		$command = \Yii::$app->subdb->createCommand("update cs_ticket_session  b
			left join cs_ticket_message a on  a.ticket_id = b.ticket_id
			set b.lastmessage  = '$now' where  b.ticket_id=a.ticket_id and b.platform_source=:platform_source
				and b.seller_id=:seller_id ".$sqlStr,$sqlArr);
		$result =  $command->execute();
		//echo "  $result  || ".$command->getRawSql(); //test kh
		//step 4更新 customer 表 的时间
		$command =  \Yii::$app->subdb->createCommand("update cs_customer  c
			left join cs_ticket_session b on c.customer_id = b.buyer_id
			left join cs_ticket_message a on  a.ticket_id = b.ticket_id
			set c.last_message_time  = '$now' where c.customer_id = b.buyer_id and  b.ticket_id=a.ticket_id and b.platform_source=:platform_source
				and b.seller_id=:seller_id ".$sqlStr,$sqlArr);
		$result =  $command->execute();
		//echo "  $result  || ".$command->getRawSql(); //test kh
// 		if ($msgType=="1")
// 			$msgCn = "订单留言";
// 		else
// 			$msgCn = "站内信";
		
// 		if ($ticket_id != "")
// 			$msgCn .= "会话id:$ticket_id";
		
// 		if ($msgid != "")
// 			$msgCn .= "会话明细id:$msgid";
		
// 		OperationLogHelper::log("messages", $platform_source,"重新发送站内信","将不成功发送的.$msgCn.重新发送");
		
		return array('success'=>true,'error'=>'');
	}
	
	/**
	 * 将发送不成功的Msg删除
	 * 支持批量,单个session,或单条msg来进行删除
	 * @param
	 * @param $selleruserid :卖家id
	 * @param $platform_source //平台类型
	 * @param $msgType //站内信类型 1--订单，2--站内信
	 * 
	 * @param $ticket_id //小老板后台会话Id 当需要将某个会话全部重新发送需要传送该值	可选
	 * @param $msgid //小老板后台会话明细Id 当需要将某条会话记录重新发送需要传送该值	可选
	 * 
	 * @return array('success'=>true,'error'=>'');
	 		success :返回是否发送成功true false
			error: 错误信息
	 * 
	 * use eagle\modules\message\apihelpers\MessageApiHelper;
	 * Sample:
	 * MessageApiHelper::msgNotSendDel ($selleruserid=88,$platform_source='',$msgType=1,$ticket_id='',$msgid='')
	 * 
	 * +-------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		hqw		2015/7/8				初始化
	 * +-------------------------------------------------------------------------------------------
	 */
	public static function msgNotSendDel($selleruserid,$platform_source,
			$msgType,$ticket_id='',$msgid=''){
		$sqlStr = '';
		$sqlArr = array();
		
		//当传入的参数有$ticket_id用于记录buyer_id来定位需要更新的客户
		$buyerSqlStr = '';
		$buyerSqlArr = array();
		$customerSqlStr = '';
		$customerSqlArr = array();
		
		$sqlArr[':platform_source'] = $platform_source;
		$sqlArr[':seller_id'] = $selleruserid;
	
		
		if ($ticket_id != ""){
			$sqlStr = " and a.ticket_id=:ticket_id";
			$sqlArr[':ticket_id'] = $ticket_id;
			
			$tSession_obj = TicketSession::find()->where(['ticket_id'=>$ticket_id])->asArray()->one();
			
			if(!empty($tSession_obj)){
				$buyerSqlStr = ' and a.buyer_id=:buyer_id';
				$buyerSqlArr = array(':buyer_id'=>$tSession_obj['buyer_id']);
				
				$customerSqlStr = ' and a.customer_id=:customer_id';
				$customerSqlArr = array(':customer_id'=>$tSession_obj['buyer_id']);
			}
		}
		
		if ($msgid != ""){
			$sqlStr .= " and a.msg_id=:msg_id";
			$sqlArr[':msg_id'] = $msgid;
		}
		
		if (!empty($msgType)){
			$sqlStr .= "  and b.message_type=:message_type ";
			$sqlArr[':message_type'] = $msgType;
		}
		
		$result = \Yii::$app->subdb->createCommand("delete a from cs_ticket_message a,cs_ticket_session b
				where b.ticket_id=a.ticket_id and b.platform_source=:platform_source
				and b.seller_id=:seller_id and a.status='F' ".$sqlStr,$sqlArr)->execute();
		
		$result = \Yii::$app->db->createCommand("delete a from message_api_queue a 
				where a.platform=:platform_source and a.seller_id=:seller_id  and a.status='F' ".
				$sqlStr,$sqlArr)->execute();
		
		$result = \Yii::$app->subdb->createCommand("delete a
			from cs_ticket_session a
			where a.platform_source=:platform_source and ifnull(a.session_id,'')='' 
			and not exists(select 1 from cs_ticket_message b where a.ticket_id=b.ticket_id)",
				array(':platform_source'=>$platform_source))->execute();
		/*  2015-09-24 kh start 由于这状态维护交给 refreshMessageStatusInfo ， 所以屏蔽
		//将发送失败的删除后，cs_ticket_session需要将msg_sent_error设置回N
		$affSessionRows = Yii::$app->subdb->createCommand("update cs_ticket_session a set msg_sent_error='N'
			where a.msg_sent_error='Y' and a.platform_source=:platform_source
			and not exists (select 1 from cs_ticket_message a1 where a1.ticket_id=a.ticket_id and a1.status='F')".$buyerSqlStr,
				array(':platform_source'=>$platform_source)+$buyerSqlArr)->execute();
		
		//将发送失败的删除后，cs_customer需要将msg_sent_error设置回C
		$affectCustomerRows = Yii::$app->subdb->createCommand("update cs_customer a set msg_sent_error='C'
			where a.platform_source=:platform_source and not exists (select 1 from cs_ticket_session a1
			where a1.msg_sent_error='Y' and a1.buyer_id=a.customer_id)".$customerSqlStr,
				array(':platform_source'=>$platform_source)+$customerSqlArr)->execute();
		2015-09-24 kh end*/
		return array('success'=>true,'error'=>'');
	}
	
	/**
	 * 批量 更新msg已读状态
	 * 
	 * @param string $ticket_id //小老板后台会话Id  当需要将某个会话标记已读
	 * @param string $customer_id //客户管理id
	 * @param $puid //小老板id 非必填, 可选择
	 * 
	 * @return array('success'=>true,'error'=>'');
	 		success :返回是否发送成功true false
			error: 错误信息
	 * 
	 * use eagle\modules\message\apihelpers\MessageApiHelper;
	 * Sample:
	 * MessageApiHelper::msgUpateHasRead ($ticket_id=88,$customer_id='',$puid=1)
	 * 
	 * +-------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		hqw		2015/7/8				初始化
	 * +-------------------------------------------------------------------------------------------
	 */
	public static function msgUpateHasRead($ticket_id, $customer_id, $puid=''){
	
		$sqlArr = array();
		$sqlArr[':ticket_id'] = $ticket_id;
	
		$result = \Yii::$app->subdb->createCommand("update cs_ticket_message a
				left join cs_ticket_session b on b.ticket_id=a.ticket_id
				set a.has_read='1'
				where b.ticket_id=a.ticket_id and a.ticket_id=:ticket_id
				and a.has_read='0' ",$sqlArr)->execute();
	
		$resultH = \Yii::$app->subdb->createCommand("update cs_ticket_session a set a.has_read=1
				where a.ticket_id=:ticket_id ",$sqlArr)->execute();
		
		//已读状态变动时需要重新统计记录数
		//ConfigHelper::setConfig("Message/left_menu_statistics", json_encode(array()));
		if(empty($puid)) $puid = \Yii::$app->user->identity->getParentUid();
		RedisHelper::delMessageCache($puid);
		
		return array('success'=>true,'error'=>'');
	}
	
	/**
	 * 速卖通,敦煌,wish,ebay回复站内信或订单留言
	 * 用于保存在队列表中，到时会有后台Job处理发送
	 * 
	 * @params
	 * 速卖通,敦煌,wish,ebay需要的参数列表 
	 * $params = array(
	 * 'platform_source' => '' //平台类型 必须
	 * 'msgType' => '' //站内信类型 1--订单，2--站内信 必须
	 * 'puid' => '' //小老板平台uid 必须
	 * 'contents' => '' //内容 必须
	 * 'ticket_id' => '' //小老板会话id  速卖通当值为空时代表新建会话, 敦煌要必填, wish要必填, ebay要必填
	 * 'orderId' => '' //速卖通订单号 当新增订单留言时必须传入该值 , 敦煌/wish/ebay没有用到
	 * );
	 * @params $changeUserDb	Boolean	是否需要切换数据库(前端还是后端调用)
	 * @return array('success'=>true,'error'=>'');
	 		success :返回是否发送成功true false
			error: 错误信息
	 * 
	 * use eagle\modules\message\apihelpers\MessageApiHelper;
	 * Sample:
	 * MessageApiHelper::sendMsgToPlatform ($params=array())
	 * 
	 * +-------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		hqw		2015/7/8				初始化
	 * +-------------------------------------------------------------------------------------------
	 */
	public static function sendMsgToPlatform($params=array() ,$changeUserDb=true){
		$journal_id = SysLogHelper::InvokeJrn_Create("Message",__CLASS__, __FUNCTION__ , $params);
			
		
		if ($params['platform_source'] == "aliexpress"){
			$result = MessageAliexpressApiHelper::aliexpressSaveMsgQueue($params,$changeUserDb);
		}
		
		if ($params['platform_source'] == "dhgate"){
			$result = MessageDhgateApiHelper::dhgateSaveMsgQueue($params,$changeUserDb);
		}
		
		if ($params['platform_source'] == "wish"){
			$result = MessageWishApiHelper::wishSaveMsgQueue($params,$changeUserDb);
		}
		
		if ($params['platform_source'] == "ebay"){
			$result = MessageEbayApiHelper::saveMsgQueue($params,$changeUserDb);
		}
		if($params['platform_source'] == "priceminister"){
		    $result = MessagePriceministerApiHelper::priceministerSaveMsgQueue($params,$changeUserDb);
		}
		if(count($result) <= 0){
			return array('success'=>false,'error'=>'没有该平台'.$params['platform_source'].'的保存类型');
		}
		//write the invoking result into journal before return
		SysLogHelper::InvokeJrn_UpdateResult($journal_id, $result);
			
		return $result;
	}
	
	/**
	 * 各个平台调用保存在message_api_queue,cs_ticket_session,cs_ticket_message
	 * 
	 * @param $puid //小老板平台uid
	 * @param $platform_source //平台类型
	 * @param $msgOne = array(
	 * 'ticket_id' => '', //小老板平台ticket_id
	 * 'session_id' => '', //各个平台会话ID
	 * 'contents' => '', //内容
	 * 'headers' => '', //标题
	 * 'created' => '', //创建时间
	 * 'updated' => '', //更新时间
	 * 'addi_info' => '', //特殊信息
	 * 'app_time' => '', //小老板平台时间
	 * 'platform_time' => '', //不同平台的时间
	 * 'seller_id' => '', //卖家id
	 * 'buyer_id' => '', //买家id
	 * );
	 * @param $msgBufferArr = array(
	 *  'seller_id'=>'', //卖家id
	 *	'message_type'=>'', //发送类型 1--订单留言,2--站内信
	 *	'order_id'=>'', //订单id
	 *	'buyer_id'=>'', //买家id
	 * );
	 * @param $sessionList = array(  //该变量的ticket_id用于判断是否生成新的cs_ticket_session,假如需要为空时需要用到的值需要填好
	 * 'ticket_id' => '', //小老板平台ticket_id
	 * 'session_id' => '', //各个平台会话ID
	 * 'platform_source' => '', //平台类型
	 * 'message_type' => '', //发送类型 1--订单留言,2--站内信
	 * 'seller_id' => '', //卖家id
	 * 'related_id' => '', //订单留言时填订单单号
	 * 'buyer_id' => '', //买家id
	 * 'seller_nickname' => '', //卖家呢称
	 * 'buyer_nickname' => '', //买家呢称
	 * 'has_read' => '', //是否已读
	 * 'created' => '', //创建时间
	 * 'updated' => '', //更新时间
	 * 'lastmessage' => '', //平台最后更新时间
	 * 'last_omit_msg' => '', //最后的消息
	 * );
	 * 
	 * @return array('success'=>true,'error'=>'','ticket_id'=>123,'msg_id'=>555);
	 		success :返回是否发送成功true false
			error: 错误信息
			ticket_id:会话id
			msg_id:该消息生成的message id
	 * 
	 * +-------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		hqw		2015/7/8				初始化
	 * +-------------------------------------------------------------------------------------------
	 */
	public static function platformSaveMsgQueue($puid = '', $platform_source, &$msgOne, $msgBufferArr, $sessionList = array(), $changeUserDb=true){
 
		try{
			if (!empty($sessionList)){
				if ($sessionList['ticket_id']==''){
					$TSession_obj = new TicketSession ();
					
					//暂时针对priceminster
					if($sessionList['platform_source'] == "priceminister"){
					    $TSession_obj->item_id = $sessionList["item_id"];
					    $msgOne["item_id"] = $sessionList["item_id"];
					}
					//标识速卖通自行发送站内信
					if(isset($sessionList['sent_myself'])){
					    $addInfo = array('sent_myself'=>$sessionList['sent_myself']);
					    $TSession_obj->addi_info = json_encode($addInfo);
					}
					
					$TSession_obj->platform_source = $sessionList['platform_source'];
					$TSession_obj->message_type = $sessionList['message_type'];
					$TSession_obj->seller_id = $sessionList['seller_id'];
					$TSession_obj->related_id = $sessionList['related_id'];
					$TSession_obj->related_type = empty($sessionList['related_type']) ? '' : $sessionList['related_type'];
					$TSession_obj->buyer_id = $sessionList['buyer_id'];
					$TSession_obj->session_id = '';
					$TSession_obj->seller_nickname = $sessionList['seller_nickname'];
					$TSession_obj->buyer_nickname = $sessionList['buyer_nickname'];
					$TSession_obj->has_read = 1;
					$TSession_obj->created = $sessionList['created'];
					$TSession_obj->updated = $sessionList['updated'];
					$TSession_obj->lastmessage = $sessionList['lastmessage'];
					$TSession_obj->last_omit_msg = $sessionList['last_omit_msg'];
					$TSession_obj->save (false);
			
					$msgOne['ticket_id'] = $TSession_obj->ticket_id;
					$msgOne['session_id'] = $TSession_obj->session_id;
					$msgOne['related_id'] = $TSession_obj->related_id;

				}
			}
			
			$TMsg_obj = new TicketMessage();
			$TMsg_obj->ticket_id = $msgOne['ticket_id'];
			$TMsg_obj->session_id = $msgOne['session_id'];
			$TMsg_obj->message_id = '';
			$TMsg_obj->send_or_receiv = '1';
			$TMsg_obj->content = $msgOne['contents'];
			$TMsg_obj->headers = empty($msgOne['headers']) ? '' : $msgOne['headers'];
			$TMsg_obj->has_read = '1';
			$TMsg_obj->msg_contact = '';
			$TMsg_obj->created = $msgOne['created'];
			$TMsg_obj->updated = $msgOne['updated'];
			$TMsg_obj->status = 'P';
			$TMsg_obj->addi_info = $msgOne['addi_info'];
			$TMsg_obj->app_time = $msgOne['app_time'];
			$TMsg_obj->platform_time = $msgOne['platform_time'];
			
			$TMsg_obj->save (false);
		}catch(Exception $ex){
			return array('success'=>false,'error'=>print_r($ex,true));
		}
		
		$msgTypeBuffArr = array('status'=>'P','puid'=>$puid,'create_time'=>date('Y-m-d H:i:s'),
				'subject'=>'','content'=>$msgOne['contents'],'platform'=>$platform_source,
				'app_source'=>'cs_ticket','msg_id'=>$TMsg_obj->msg_id, 'ticket_id'=>$msgOne['ticket_id'],'content_md5'=>md5($msgOne['contents']));
		
		MessageBGJHelper::putToMessageQueueBuffer($msgTypeBuffArr+$msgBufferArr);
		
		try{
			MessageBGJHelper::postMsgOrOrdermsgApiQueueBufferToDb();
		}catch(Exception $ex){
			\Yii::error(__CLASS__.' function:'.__FUNCTION__.' postMsgOrOrdermsgApiQueueBufferToDb '.'uid:'.$puid.print_r($ex,true));
			return array('success'=>false,'error'=>__CLASS__.' function:'.__FUNCTION__.' postMsgOrOrdermsgApiQueueBufferToDb '.'uid:'.$puid.print_r($ex,true));
		}
		
		//$ticket_id来判断是否最后已回复
		self::updateTicketSessionReplied($msgOne['ticket_id'], $TMsg_obj->platform_time);
		
		$customerParams = array(
				'platform_source' => $platform_source,
				'seller_id' => $msgOne['seller_id'],
				'customer_id' => $msgOne['buyer_id'],
				'isCreateCustomer' => empty($sessionList['isCreateCustomer']) ? 'N' : $sessionList['isCreateCustomer'],
				'customer_nickname' => empty($sessionList['buyer_nickname']) ? '' : $sessionList['buyer_nickname'],
				'last_message_time' => $TMsg_obj->platform_time,
		);
		
		//cs_customer 客户管理表更新最后一次回复时间
		self::customerUpdateLastTime($customerParams);
		
		//更新cs_customer的os_flag是否outstanding状态 不能放在外面因为要到这里才可以真正获取$TSession_obj->ticket_id
		self::customerUpdateOsFlag($puid, $platform_source, $msgOne['ticket_id'],[],'',$changeUserDb);
		
		return array('success'=>true,'error'=>'','ticket_id'=>$TMsg_obj->ticket_id,'msg_id'=>$TMsg_obj->msg_id);
	}
	
	/**
	 * cs_customer 客户管理表更新最后一次回复时间，或新增客户
	 * 需要先切换数据库后再进来
	 * 
	 * @param 
	 * $params = array(
	 * platform_source => '', //平台类型
	 * seller_id => '', //卖家id
	 * customer_id => '', //买家Id
	 * customer_nickname => '', //买家呢称
	 * last_message_time => '', //最后回复时间
	 * isCreateCustomer => '' //当客户不存在时是否新建  Y表示不存在会新建，N就不会新建
	 * 
	 * recieverId=>'', //敦煌用到的通讯会话id
	 * );
	 * @return array('success'=>true,'error'=>'');
	 		success :返回是否发送成功true false
			error: 错误信息
	 * 
	 * +-------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		hqw		2015/7/8				初始化
	 * +-------------------------------------------------------------------------------------------
	 */
	public static function customerUpdateLastTime($params=array()){
		//管理客户表
		$customer = Customer::findOne(['platform_source'=>$params['platform_source'],
				'seller_id'=>$params['seller_id'],'customer_id'=>$params['customer_id']]);
		
		if ($customer !== null){
			$customer->update_time = date('Y-m-d H:i:s', time ());
		}else{
			try{
				//根据该标志来判断是否新建customer表
				if ($params['isCreateCustomer'] == 'Y'){
					$customer = new Customer ();
					$customer->platform_source = $params['platform_source'];
					$customer->seller_id = $params['seller_id'];
					$customer->customer_id = $params['customer_id'];
					$customer->customer_nickname = $params['customer_nickname'];
					$customer->create_time = date('Y-m-d H:i:s', time ());
					$customer->update_time = date('Y-m-d H:i:s', time ());
					$customer->msg_sent_error = 'N';
					
					if (isset($params['recieverId'])){
						$tmpAddi_info = json_decode($customer->addi_info,true);
						$tmpAddi_info['recieverId'] = $params['recieverId'];
						$customer->addi_info = json_encode($tmpAddi_info);
					}
				}
			}catch(Exception $ex){
				return array('success'=>false,'error'=>print_r($ex,true));
			}
		}
		
		//假如拉取站内信时发现和该客户建立过沟通的话就将msg_sent_error设置为C
		if(isset($params['isSend'])){
			if (($customer->msg_sent_error == 'N'))
				$customer->msg_sent_error = 'C';
		}
		
		if ($customer === null){
			\Yii::error(__CLASS__.' function:'.__FUNCTION__.' No customer information to the platform');
			return array('success'=>false,'error'=>__CLASS__.' function:'.__FUNCTION__.' No customer information to the platform');
		}
		
		$customer->last_message_time = $params['last_message_time'];
		
		try {
			$customer->save(false);
		}catch (Exception $ex){
			\Yii::error(__CLASS__.' function:'.__FUNCTION__.' '.print_r($ex,true));
			return array('success'=>false,'error'=>__CLASS__.' function:'.__FUNCTION__.' '.print_r($ex,true));
		}
		
		return array('success'=>true,'error'=>'');
	}
	
	/**
	 * 根据传过来的$ticket_id来判断是否最后已回复
	 * 需要先切换数据库后再进来
	 * 
	 * @param unknown $ticket_id //小老板平台会话id
	 * @return multitype:boolean string
	 */
	public static function updateTicketSessionReplied($ticket_id, $last_msg_time = ''){
		$tMsg_obj = TicketMessage::find()->where(['ticket_id'=>$ticket_id])
			->orderBy(['platform_time' => SORT_DESC,])->limit(1)->asArray()->one();
		
		if (count($tMsg_obj) <= 0)
			return array('success'=>false,'error'=>'没有Msg');
		
		$tSession_obj = TicketSession::findOne(['ticket_id'=>$ticket_id]);
		
		if ($tSession_obj === null) 
			return array('success'=>false,'error'=>'没有Session');
		
		if ($tMsg_obj['send_or_receiv'] == '1')
			$tSession_obj->has_replied = 1;
		else
			$tSession_obj->has_replied = 0;
		
		if (!empty($last_msg_time)){
			$tSession_obj->lastmessage = $last_msg_time;
		}
		
		$tSession_obj->save(false);
		
		return array('success'=>true,'error'=>'');
	}
	
	/**
	 * 批量更新cs_customer的os_flag是否outstanding状态，1为是，0为否
	 * 
	 * @param $puid //小老板平台uid 必须
	 * @param $platform_source //平台类型
	 * 
	 * @param $ticket_id //会话id 当该值不为空，只会更新该$ticket_id对应的customer_id的os_flag
	 * @param $touched_customer_ids customerID数组
	 * @param $seller_id 卖家账号ID
	 * @return multitype:boolean string
	 */
	public static function customerUpdateOsFlag($puid, $platform_source, $ticket_id = '',$touched_customer_ids=array(),$seller_id = '',$changeUserDb=true){
 
		//cs_ticket_session连接查询SQL语句
		$andSql="";
    	$andParams=array(':platform_source'=>$platform_source);
    	
    	//cs_customer连接查询SQL语句
    	$customerAndSql="";
    	$customerAndParams=array(':platform_source' => $platform_source);
    	
    	if (!empty($seller_id)){
    		$andSql .= ' and seller_id=:seller_id ';
    		$andParams[":seller_id"]=$seller_id;
    		
    		$customerAndSql .= ' and seller_id=:seller_id ';
    		$customerAndParams[":seller_id"]=$seller_id;
    	}
		
		if ($ticket_id != ''){
			$buyer_id = TicketSession::find()->select('buyer_id')->
				where(' ticket_id=:ticket_id ',array(':ticket_id'=>$ticket_id))->
				groupBy('buyer_id')->asArray()->all();
			
			$buyer_id = $buyer_id[0]['buyer_id'];
			
			$andSql .= ' and buyer_id=:buyer_id ';
			$andParams[":buyer_id"]=$buyer_id;
			
			$customerAndSql .= ' and customer_id=:customer_id ';
			$customerAndParams[":customer_id"]=$buyer_id;
		}
		
		$tSessionCommand = TicketSession::find()->select('buyer_id')->
			where('message_type in (1,2) and has_replied=0 and platform_source=:platform_source'.$andSql, 
				$andParams)->groupBy('buyer_id');
		
		if(count($touched_customer_ids) > 0)
			$tSessionCommand->andWhere(['buyer_id'=>$touched_customer_ids]);
		
		$tSessionArr = $tSessionCommand->asArray()->all();
		
		Customer::updateAll(array('os_flag'=>'0'),' platform_source=:platform_source '.$customerAndSql.' and os_flag = 1 ',
			$customerAndParams);
		
		if (count($tSessionArr) == 0) return array('success'=>true,'error'=>'');
		
		$buyerIdArr = array();
		
		foreach ($tSessionArr as $tSession){
			$buyerIdArr[] = $tSession['buyer_id'];
		}
		
		try{
			$csCustomer = \Yii::$app->get('subdb')->createCommand()->update('cs_customer',
					['os_flag' => 1], ['and', ['customer_id' => $buyerIdArr], ['os_flag'=>'0'], ['platform_source'=>$platform_source]])->execute();
		}catch (Exception $ex){
			\Yii::error(__CLASS__.' function:'.__FUNCTION__.print_r($ex,true));
			return array('success'=>false,'error'=>__CLASS__.' function:'.__FUNCTION__.print_r($ex,true));
		}
		return array('success'=>true,'error'=>'');
	}
	
	/**
	 * 批量管理已读状态
	 * 
	 * @param $puid //小老板平台uid 必须
	 * @param $platform_source //平台类型
	 * @param $touched_session_ids  //被insert或者更新过的session
	 * @return multitype:boolean string
	 */
	public static function updateTicketSessionHasRead($puid, $platform_source , $touched_session_ids){		
		if (empty($touched_session_ids))
			return array('success'=>true,'error'=>'');
		
		
		$andSql=" and session_id in ('". implode("','",$touched_session_ids) ."') and a.platform_source=:platform_source ";
		$andParams=array(
				':platform_source'=>$platform_source
		);
		
		try{
			
			//首先批量全部更新已读状态，然后再根据明细来判断是否未读
			$result = Yii::$app->get('subdb')->createCommand("update cs_ticket_session a set has_read = 1
				where 1=1 ".$andSql,$andParams)->execute();
			
			$current_time=explode(" ",microtime());	$start2_time=round($current_time[0]*1000+$current_time[1]*1000);//ystest
			echo "updateTicketSessionHasRear t2-t1 used time  ".($start2_time-$start1_time)."\n";
			
			$result = Yii::$app->get('subdb')->createCommand("update cs_ticket_session a set has_read = 0
				where 1=1 ".$andSql.
					" and exists(select 1 from cs_ticket_message a1 where a1.ticket_id=a.ticket_id and a1.has_read=0)",$andParams)->execute();
			
			$current_time=explode(" ",microtime());	$start3_time=round($current_time[0]*1000+$current_time[1]*1000);//ystest
			echo "updateTicketSessionHasRear t3-t2 used time  ".($start3_time-$start2_time)."\n";
				
			
		}catch (Exception $ex){
			\Yii::error(__CLASS__.' function:'.__FUNCTION__.print_r($ex,true));
			return array('success'=>false,'error'=>__CLASS__.' function:'.__FUNCTION__.print_r($ex,true));
		}
		
		//ConfigHelper::setConfig("Message/left_menu_statistics", json_encode(array()));
		if(empty($puid)) $puid = \Yii::$app->user->identity->getParentUid();
		RedisHelper::delMessageCache($puid);
		
		return array('success'=>true,'error'=>'');
	}
	
	/**
	 * 名称：getTicketSessionList
	 * desc：返回某个客户所有ticket session List
	 * 
	 * 参数： 
	 * @param $buyer_id 买家id
	 * @param $platform 平台类型:wish/amazon/ebay/aliexpress
	 * @param $seller_id 卖家id
	 * @param $params  array() , 非必填，可选。
			在array里面设置key包含
			message_type 站内信类型, 1--订单留言，2--站内信，3--平台类型  , 非必填，可选。
			sort 排序字段 , 默认t.lastmessage , 非必填，可选。
			order 倒序排序 或 正序排序 desc Or asc, 非必填，可选。 默认desc
	 * @param $defaultPageSize 每页显示记录数,默认显示5条 ,非必填，可选。
	 * @param $puid 用户uid ,非必填，可选。
	  
	 * @return arrar(success =>true , error=>’’,ticketSessionList=>array())
		success :返回是否发送成功true false
		error: 错误信息
		ticketSessionList 返回  => Array
        (
            [pagination] => yii\data\Pagination Object
                (
                    [pageParam] => page
                    [pageSizeParam] => per-page
                    [forcePageParam] => 1
                    [route] => 
                    [params] => 
                    [urlManager] => 
                    [validatePage] => 1
                    [totalCount] => 1
                    [defaultPageSize] => 5
                    [pageSizeLimit] => Array
                        (
                            [0] => 1
                            [1] => 50
                        )
                    [_pageSize:yii\data\Pagination:private] => 5
                    [_page:yii\data\Pagination:private] => 0
                )
            [data] => Array
                (
                    [0] => Array
                        (
                            [ticket_id] => 209
                            [platform_source] => aliexpress
                            [message_type] => 1
                            [related_id] => 67538679087051
                            [related_type] => O
                            [seller_id] => cn1510671045
                            [buyer_id] => br1043985458
                            [session_id] => 67538679087051
                            [has_read] => 0
                            [has_replied] => 1
                            [has_handled] => 0
                            [created] => 2015-08-06 15:12:02
                            [updated] => 2015-08-06 15:12:43
                            [lastmessage] => 2015-06-30 18:22:12
                            [msg_sent_error] => N
                            [addi_info] => 
                            [last_omit_msg] => ok,I give you $ 9 th...

                            [seller_nickname] => Bin Wu
                            [buyer_nickname] => Cristiano  Samp
                            [msgTitle] => 
                            [track_no] => 
                        )
                )
        )

	 * use eagle\modules\message\apihelpers\MessageApiHelper;
	 * Sample:
	 * MessageApiHelper::getTicketSessionList ($buyer_id='xxxxx',$platform_source=”aliexpress”, $seller_id=’14654897’,$params=array(),$defaultPageSize=5,$puid='')
	 * 
	 * +-------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		hqw		2015/8/6				初始化
	 * +-------------------------------------------------------------------------------------------
	 */
	public static function getTicketSessionList($buyer_id ,$platform_source, $seller_id, $params = array(),$defaultPageSize=5, $puid=''){
 
		$conn=\Yii::$app->subdb;
		
		$queryTmp = new Query;
		
		$queryTmp->select("ticket_id,platform_source,message_type,
				related_id,related_type,seller_id,buyer_id,session_id,has_read,has_replied,has_handled,created,
				updated,lastmessage,msg_sent_error,addi_info,last_omit_msg,seller_nickname,
				buyer_nickname,msgTitle,'' as `track_no`")
						->from("cs_ticket_session t")
						->where(['and',"t.buyer_id=:buyer_id", "t.platform_source=:platform_source", "t.seller_id=:seller_id"],
								[':buyer_id'=>$buyer_id,':platform_source'=>$platform_source,':seller_id'=>$seller_id]);
		
		if(isset($params['message_type'])){
			$queryTmp->andWhere(['and', "t.message_type='".$params['message_type']."'"]);
		}
		
		$DataCount = $queryTmp->count("1", $conn);
		
		$pagination = new Pagination([
				'defaultPageSize' => $defaultPageSize,
				'totalCount' => $DataCount,
				]);
		
		$data['pagination'] = $pagination;
		
		if(empty($params['sort'])){
			$params['sort'] = ' t.lastmessage ';
			$params['order'] = 'desc';
		}
			
		$queryTmp->orderBy($params['sort']." ".$params['order']);
		$queryTmp->limit($pagination->limit);
		$queryTmp->offset($pagination->offset);
		
		$allSessionDataArr = $queryTmp->createCommand($conn)->queryAll();
		
		
		foreach ($allSessionDataArr as $allSessionDataKey => $allSessionData){
			if ($allSessionData['related_type'] != 'O') continue;
			
			$trackingOne=Tracking::find()->where(['order_id'=>$allSessionData['related_id'],'seller_id'=>$allSessionData['seller_id']])
				->orderBy(['update_time'=>SORT_DESC])->asArray()->one();
		
			if (count($trackingOne) != 0 ){
				$allSessionDataArr[$allSessionDataKey]['track_no'] = $trackingOne['track_no'];
			}
			unset($trackingOne);
		}
		
		$data['data'] = $allSessionDataArr;
		return array('success'=>true,'error'=>'','ticketSessionList'=>$data);
	}
	
	/**
	 * 名称：getTicketMsgList
	 * desc：返回某个session下所有message记录
	 *
	 * 参数：
	 * @param $ticket_id 小老板平台的会话id
	 * @param $params  array() , 非必填，可选。
		  在array里面设置key包含
		 sort 排序字段 , 默认t.platform_time 平台实际时间 , 非必填，可选。
		 order 倒序排序 或 正序排序 desc Or asc, 非必填，可选。默认 desc
	 * @param $defaultPageSize 每页显示记录数,默认0 为显示全部msg记录 , 非必填，可选。
	 * @param $puid 用户uid ,非必填，可选。
	  
	 * @return arrar(success =>true , error=>’’,msgList=>array())
		 success :返回是否发送成功true false
		 error: 错误信息
		 msgList 返回 array(
		 		[pagination] => yii\data\Pagination Object
                (
                    [pageParam] => page
                    [pageSizeParam] => per-page
                    [forcePageParam] => 1
                    [route] => 
                    [params] => 
                    [urlManager] => 
                    [validatePage] => 1
                    [totalCount] => 11
                    [defaultPageSize] => 11
                    [pageSizeLimit] => Array
                        (
                            [0] => 1
                            [1] => 50
                        )

                    [_pageSize:yii\data\Pagination:private] => 11
                    [_page:yii\data\Pagination:private] => 0
                )
                [msgdata] => Array  //msg会话记录
                (
                    [0] => Array
                        (
                            [msg_id] => 442
                            [ticket_id] => 209
                            [session_id] => 67538679087051
                            [message_id] => 2285910732
                            [send_or_receiv] => 1
                            [related_id] => 67538679087051
                            [related_type] => O
                            [content] => ok,I give you $ 9 the refund
                            [headers] => 
                            [has_read] => 1
                            [created] => 2015-08-06 15:12:03
                            [updated] => 2015-08-06 15:12:03
                            [status] => C
                            [addi_info] => 
                            [app_time] => 2015-07-01 09:22:12
                            [platform_time] => 2015-06-30 18:22:12
                            [haveFile] => 0
                            [fileUrl] => 
                            [msg_contact] => {"haveFile":false,"orderUrl":"http:\/\/trade.alibaba.com\/order_detail.htm?orderId=67538679087051","gmtCreate":"20150630182212000-0700","receiverLoginId":"br1043985458","messageType":"order","fileUrl":"","productId":0,"id":2285910732,"content":"ok,I give you $ 9 the refund","senderName":"Bin Wu","senderLoginId":"cn1510671045","productUrl":"","receiverName":"Cristiano  Samp","read":true,"typeId":67538679087051,"productName":"","orderId":67538679087051,"relationId":67538679087051}
                            [track_no] => 
                            [productUrl] => 
                        )
					...
                    ...
					...
                )
                
                [headData] => Array // 该会话id的列表信息
                (
                    [ticket_id] => 209
                    [platform_source] => aliexpress
                    [message_type] => 1
                    [related_id] => 67538679087051
                    [related_type] => O
                    [seller_id] => cn1510671045
                    [buyer_id] => br1043985458
                    [session_id] => 67538679087051
                    [has_read] => 0
                    [has_replied] => 1
                    [has_handled] => 0
                    [created] => 2015-08-06 15:12:02
                    [updated] => 2015-08-06 15:12:43
                    [lastmessage] => 2015-06-30 18:22:12
                    [msg_sent_error] => N
                    [addi_info] => 
                    [last_omit_msg] => ok,I give you $ 9 th...

                    [seller_nickname] => Bin Wu
                    [buyer_nickname] => Cristiano  Samp
                    [original_msg_type] => 
                    [msgTitle] => 
                )
		 	);
	
	 * use eagle\modules\message\apihelpers\MessageApiHelper;
	 * Sample:
	 * MessageApiHelper::getTicketMsgList ($ticket_id=95,$params=array(),$defaultPageSize=0,$puid='')
	 *
	 * +-------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		hqw		2015/8/6				初始化
	 * +-------------------------------------------------------------------------------------------
	 */
	public static function getTicketMsgList($ticket_id, $params = array(), $defaultPageSize=0, $puid=''){
		
		$headData = TicketSession::find()->select('ticket_id,platform_source,message_type,
				related_id,related_type,seller_id,buyer_id,session_id,has_read,has_replied,
				has_handled,created,updated,lastmessage,msg_sent_error,addi_info,last_omit_msg,
				seller_nickname,buyer_nickname,original_msg_type,msgTitle,item_id')
				->where(['ticket_id'=>$ticket_id])->asArray()->one();
		
		$conn=\Yii::$app->subdb;
		
		$queryTmp = new Query;
		
		$queryTmp->select("msg_id,ticket_id,session_id,message_id,send_or_receiv,related_id,related_type,
				content,English_content,Chineses_content,headers,has_read,created,updated,status,addi_info,app_time,platform_time,
				haveFile,fileUrl,msg_contact,'' as `track_no`,'' as `productUrl`")
				->from("cs_ticket_message t")
				->where(['and', "t.ticket_id='$ticket_id'"]);
		
		$DataCount = $queryTmp->count("1", $conn);
		
		if ($defaultPageSize == 0){
			$defaultPageSize = $DataCount;
		}
		
		$pagination = new Pagination([
				'defaultPageSize' => $defaultPageSize,
				'totalCount' => $DataCount,
				]);
		
		$data['pagination'] = $pagination;
		
		if(empty($params['sort'])){
			$params['sort'] = ' t.platform_time ';
			$params['order'] = 'desc';
		}
			
		$queryTmp->orderBy($params['sort']." ".$params['order']);
		$queryTmp->limit($pagination->limit);
		$queryTmp->offset($pagination->offset);
		$queryTmp->orderBy('platform_time desc');
		
		$allMessageDataArr = $queryTmp->createCommand($conn)->queryAll();
		
		foreach ($allMessageDataArr as $allMessageDataKey => &$allMessageData){
			if ($headData['platform_source'] == 'aliexpress'){
// 				$tmpMsgContactArr = json_decode($allMessageData['msg_contact'], true);
				$tmpMsgContactArr = self::msgDetectionJsonToArr($allMessageData['msg_contact']);
				
				if(isset($tmpMsgContactArr['summary']['productDetailUrl']))
					$allMessageData['productUrl'] = $tmpMsgContactArr['summary']['productDetailUrl'];
			}
			
			if ($allMessageData['related_type'] != 'O') continue;
			
			$result = OrderTrackerApiHelper::getTrackingNoByOrderId($headData['seller_id'],$allMessageData['related_id']);
			$allMessageData['track_no'] = $result['track_no'];
		}
		
		$data['msgdata'] = $allMessageDataArr;
		$data['headData'] = $headData;
		
		return array('success'=>true,'error'=>'','msgList'=>$data);
	}
	
	/**
	 * 名称：getMsgOne
	 * desc：返回单条msg记录
	 *
	 * 参数：
	 * @param $msg_id cs_ticket_message 主键id
	 * @param $puid 用户uid ,非必填，可选。
	 * @return arrar(success =>true , error=>’’,msgOne=>array())
		 success :返回是否发送成功true false
		 error: 错误信息
		 msgOne => Array	//返回 array(); 单条msg信息
        (
            [msg_id] => 487
            [ticket_id] => 214
            [session_id] => 67433040474573
            [message_id] => 2252335110
            [send_or_receiv] => 1
            [related_id] => 67433040474573
            [related_type] => O
            [content] => Hi Roman Sad,...

            [headers] => 
            [has_read] => 1
            [created] => 2015-08-06 15:12:31
            [updated] => 2015-08-06 15:12:31
            [status] => C
            [addi_info] => 
            [app_time] => 2015-06-15 10:52:09
            [platform_time] => 2015-06-14 19:52:09
            [haveFile] => 0
            [fileUrl] => 
        )
	
	 * use eagle\modules\message\apihelpers\MessageApiHelper;
	 * Sample:
	 * MessageApiHelper::getMsgOne ($msg_id=88,$puid='')
	 *
	 * +-------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		hqw		2015/8/6				初始化
	 * +-------------------------------------------------------------------------------------------
	 */
	public static function getMsgOne($msg_id, $puid=''){
 
		$conn=\Yii::$app->subdb;
		
		$queryTmp = new Query;
		
		$queryTmp->select("msg_id,ticket_id,session_id,message_id,send_or_receiv,related_id,related_type,
				content,headers,has_read,created,updated,status,addi_info,app_time,platform_time,
				haveFile,fileUrl")
				->from("cs_ticket_message t")
				->where(['and', "t.msg_id='$msg_id'"]);
		
		$msgArr = $queryTmp->createCommand($conn)->queryAll();
		$msgArr = $msgArr[0];
		
		return array('success'=>true,'error'=>'','msgOne'=>$msgArr);
	}
	
	
	/**
	 * 更新cs_customer表中相关的订单统计信息
	 * 
	 * @param $params = array(platform_source=>'',seller_id=>'',customer_id=>'',customer_nickname=>'',
	 * 		order_id=>'',nation_code=>'',email=>'',order_time=>'',currency=>'',amount=>'' )
	 * 		platform_source  平台类型：wish,dhgate/ebay,
	 * 		seller_id	卖家Id
	 * 		customer_id	买家id
	 * 		customer_nickname	买家呢称
	 * 		order_id	平台来源订单单号
	 * 		nation_code	国家代码，如CN，US
	 * 		email 		邮箱地址
	 * 		order_time 最后一个订单日期
	 * 		currency	货币
	 * 		amount	消费金额:80
	 * );
	 * 
	 * @param $puid 看是否需要切换数据库 非必填
	 * 
	 * @return array('success'=>true,'error'=>'');
	  		success :返回是否发送成功true false
			error: 错误信息
	 * 
	 * +-------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		hqw		2015/7/26				初始化
	 * +-------------------------------------------------------------------------------------------
	 */
	public static function customerUpdateOrderInfo($params=array(), $puid=''){
		 
		
		//管理客户表
		$customer = Customer::findOne(['platform_source'=>$params['platform_source'],
				'seller_id'=>$params['seller_id'],'customer_id'=>$params['customer_id']]);
		
		if ($customer === null){
			$customer = new Customer ();
			$customer->platform_source = $params['platform_source'];
			$customer->seller_id = $params['seller_id'];
			$customer->customer_id = $params['customer_id'];
			$customer->customer_nickname = $params['customer_nickname'];
			$customer->create_time = date('Y-m-d H:i:s', time ());
			
			if($params['platform_source'] == "dhgate"){
				$tmpAddi_info = json_decode($customer->addi_info,true);
				if(!isset($tmpAddi_info['recieverId'])){
					$tmpAddi_info['recieverId'] = '';
					$customer->addi_info = json_encode($tmpAddi_info);
				}
			}
		}
		
		try{
			$customer->update_time = date('Y-m-d H:i:s', time ());
			$customer->last_order_id = $params['order_id'];
			$customer->nation_code = $params['nation_code'];
			$customer->email = $params['email'];
			$customer->last_order_time = $params['order_time'];
			$customer->currency = $params['currency'];
			$customer->life_order_amount += $params['amount'];
			$customer->life_order_count += 1;
			
			$customer->save(false);
		}catch (Exception $ex){
			\Yii::error(__CLASS__.' function:'.__FUNCTION__.print_r($ex->getMessage(),true));
			return array('success'=>false,'error'=>__CLASS__.' function:'.__FUNCTION__.print_r($ex->getMessage(),true)." pass in param is:".print_r($params,true));
		}
		
		return array('success'=>true,'error'=>'');
	}
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 统计Left menu 上的cs_ticket_session 未读数据
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param na
	 +---------------------------------------------------------------------------------------------
	 * @return array  $menuLabelList 各平台 未读 数
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		hqw		2015/8/4				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	public static function getMenuStatisticData(){
	    $puid = \Yii::$app->user->identity->getParentUid();
		
		//step 1, 尝试load 统计的cache，如果没有，才立即计算，并且放入cache
		//$msg_statistics_str = ConfigHelper::getConfig("Message/left_menu_statistics",'NO_CACHE');//旧
		$msg_statistics_str = RedisHelper::getMessageCache($puid, '', 'MenuStatisticData');//新
		$msg_statistics = empty($msg_statistics_str)?'':json_decode($msg_statistics_str,true);
		if (empty($msg_statistics)) $msg_statistics = array();
		//检查是否有设置dashbord数据
		if(DashBoardStatisticHelper::CounterGet($puid,'customerService_note') != 1){
		    $dashBoard_check = true;
		}else{
		    $dashBoard_check = false;
		}
		
		if(isset($msg_statistics['all_platform_unread'])&&isset($msg_statistics['aliexpress'])){//刷新之前aliexpress的缓存格式
		    if(!isset($msg_statistics['aliexpress']['order'])){
		        $aliexpress_check = true;
		    }else{
		        $aliexpress_check = false;
		    }
		}else{
		    $aliexpress_check = false;
		}
		if (!isset($msg_statistics['all_platform_unread']) || $aliexpress_check || $dashBoard_check){
			
			//判断平台是否开通
			//$platformUseArr = PlatformAccountApi::getAllPlatformBindingSituation();
			$AuthorizePlatformArr = UserHelper::getUserAllAuthorizePlatformAccountsArr();
			
			$menuLabelList = array();
			
			$menuLabelList = [
				'ebay'=>[
					'all_unread_msg'=>0,
					'order_unread_msg'=>0,
					'product_unread_msg'=>0,
					'system_unread_msg'=>0,
				    'fail_message'=>0,
					],
			    'aliexpress'=>[
			        'order'=>[//订单留言
			            'all_unread_msg'=>0,
			            'order_unread_msg'=>0,
			            'product_unread_msg'=>0,
			            'other_unread_msg'=>0,
			             ],
			        'station'=>[//站内信
			            'all_unread_msg'=>0,
			            'order_unread_msg'=>0,
			            'product_unread_msg'=>0,
			            'other_unread_msg'=>0,
			             ],
			        'fail_message'=>0,
			    ],
				'dhgate'=>[	
					'all_unread_msg'=>0,
					'order_unread_msg'=>0,
					'product_unread_msg'=>0,
					'system_unread_msg'=>0,
				    'fail_message'=>0,
					],
				'wish'=>[
					'all_unread_msg'=>0,
				    'fail_message'=>0,
					],
			    'cdiscount'=>[
			        'all_unread_msg'=>0,
			        'order_unread_msg'=>0,
			        'order_question_unread_msg'=>0,
			        'fail_message'=>0,
			    ],
			    'priceminister'=>[
			        'all_unread_msg'=>0,
			        'order_unread_msg'=>0,
			        'fail_message'=>0,
			    ],
				'all_platform_unread'=>0,
			];
			
			/*
			foreach ($platformUseArr as $platformUseKey=>$platformUseValue){
				if ($platformUseValue == false){
				    unset($menuLabelList[$platformUseKey]);
				}
			}
			*/
			$used_platform_type = array_keys($menuLabelList);
			$platformKeys = array_keys($AuthorizePlatformArr);
			foreach ($used_platform_type as $typeKey=>$countData){
				if($typeKey=='all_platform_unread')
					continue;
				if(!in_array($typeKey,$platformKeys))
					unset($menuLabelList[$typeKey]);
			}
			
			$failMessage = TicketMessage::find()->select('ticket_id')->where(['status'=>'F'])->asArray()->all();
			
			foreach($menuLabelList as $platform_type=>$value){
				if($platform_type=='$platform_type')
					continue;
				$platformAccounts = empty($AuthorizePlatformArr[$platform_type])?[]:$AuthorizePlatformArr[$platform_type];
// 				$queryTmp->andWhere(['in','t.seller_id',$allPlatformArr['aliexpress']+$allPlatformArr['ebay']+$allPlatformArr['wish']+$allPlatformArr['dhgate']]);
				
// 				$msg_relatedArr = Yii::$app->subdb->
// 					createCommand("select ifnull(related_type,'') as `related_type`,count(1) as `related_count`,message_type
// 					from cs_ticket_session
// 					where has_read=0 and message_type in (1,2,3) and platform_source=:platform_source
// 					group by ifnull(related_type,''),message_type
// 					",[':platform_source'=>$platform_type])->queryAll();

				$queryTmp = new Query;
				$queryTmp->select("ifnull(related_type,'') as `new_related_type`,count(1) as `related_count`,message_type")
				->from("cs_ticket_session")
				->where(['and'," has_read=0 and message_type in (1,2,3) and platform_source=:platform_source "],array(':platform_source'=>$platform_type))
				->groupBy("new_related_type,message_type");
				$queryTmp->andFilterWhere(['seller_id'=>$platformAccounts]);
				
				$conn=\Yii::$app->subdb;
				$msg_relatedArr = $queryTmp->createCommand($conn)->queryAll();
				
// 				print_r($msg_relatedArr);
// 				exit;
				
				//获取失败message数量
				/*
				$failMessage_array = array();
				$platform_failMessage_array = array();
				*/
				//$failMessage = TicketMessage::find()->select('ticket_id')->where(['status'=>'F'])->asArray()->all();
				if(!empty($failMessage)){
				    //统计fail message数量
				    /*
				    foreach ($failMessage as $fail_detail){
                        if(isset($failMessage_array[$fail_detail['ticket_id']])){
                            $failMessage_array[$fail_detail['ticket_id']] += 1;
                        }else{
                            $failMessage_array[$fail_detail['ticket_id']] = 1;
                        }
				    }
				    */
				    //获取平台
				    $failSession = TicketSession::find()->select(['ticket_id','platform_source'])
				    ->where(['ticket_id'=>$failMessage])
				    ->andWhere(['seller_id'=>$platformAccounts])->asArray()->all();
				    
				    if(!empty($failSession)){
				    	$menuLabelList[$platform_type]['fail_message'] = count($failSession);
				    }
				    /*
				    $failSession = TicketSession::find()->select(['ticket_id','platform_source'])->where(['ticket_id'=>$failMessage])->andWhere(['seller_id',$allPlatformArr['aliexpress']+$allPlatformArr['ebay']+$allPlatformArr['wish']+$allPlatformArr['dhgate']+$allPlatformArr['cdiscount']+$allPlatformArr['priceminister']])->asArray()->all();
				    
				    if(!empty($failSession)){
				        foreach ($failSession as $v){
				            if(isset($failMessage_array[$v['ticket_id']])){
				                $platform_failMessage_array[$v['platform_source']] += $failMessage_array[$v['ticket_id']];
				            }else{
				                $platform_failMessage_array[$v['platform_source']] = $failMessage_array[$v['ticket_id']];
				            }
				        }
				    }
// 				    print_r($platform_failMessage_array);
				    //设置fail_message
				    if(!empty($platform_failMessage_array)){
				        foreach ($platform_failMessage_array as $fail_key=>$fail_val){
				            if(isset($menuLabelList[$fail_key])){
				                $menuLabelList[$fail_key]['fail_message'] = $fail_val;
				            }
				        }
				    }
				    */
				}
				
				foreach ($msg_relatedArr as $msg_related){
				    if($platform_type == "aliexpress"){
				        if($msg_related['message_type'] == 1){//速卖通订单留言
				            switch ($msg_related['new_related_type'])
				            {
				                case 'O':
				                    $menuLabelList[$platform_type]['order']['order_unread_msg'] += $msg_related['related_count'];
				                    break;
				                case 'P':
				                    $menuLabelList[$platform_type]['order']['product_unread_msg'] += $msg_related['related_count'];
				                    break;
			                    case 'M':
			                        $menuLabelList[$platform_type]['order']['other_unread_msg'] += $msg_related['related_count'];
			                        break;
			                    default:
			                        $menuLabelList[$platform_type]['order']['other_unread_msg'] += $msg_related['related_count'];
			                        break;
				            }
				            $menuLabelList[$platform_type]['order']['all_unread_msg'] += $msg_related['related_count'];
				            $menuLabelList['all_platform_unread'] += $msg_related['related_count'];
				        }else{////速卖通站内信
				            switch ($msg_related['new_related_type'])
				            {
				                case 'O':
				                    $menuLabelList[$platform_type]['station']['order_unread_msg'] += $msg_related['related_count'];
				                    break;
				                case 'P':
				                    $menuLabelList[$platform_type]['station']['product_unread_msg'] += $msg_related['related_count'];
				                    break;
			                    case 'M':
			                        $menuLabelList[$platform_type]['station']['other_unread_msg'] += $msg_related['related_count'];
			                        break;
		                        default://其他类型
		                            $menuLabelList[$platform_type]['station']['other_unread_msg'] += $msg_related['related_count'];
		                            break;
				            }
				            $menuLabelList[$platform_type]['station']['all_unread_msg'] += $msg_related['related_count'];
				            $menuLabelList['all_platform_unread'] += $msg_related['related_count'];
				        }
				    }else{
				        if (in_array($msg_related['message_type'], array(1,2))){
				            switch ($msg_related['new_related_type'])
				            {
				                case 'O':
				                    $menuLabelList[$platform_type]['order_unread_msg'] += $msg_related['related_count'];
				                    break;
				                case 'P':
				                    $menuLabelList[$platform_type]['product_unread_msg'] += $msg_related['related_count'];
				                    break;
				                case 'S':
				                    $menuLabelList[$platform_type]['system_unread_msg'] += $msg_related['related_count'];
				                    break;
				                case 'M':
				                    $menuLabelList[$platform_type]['other_unread_msg'] += $msg_related['related_count'];
				                    break;
			                    case 'Q'://cdiscount order_question
			                        $menuLabelList[$platform_type]['order_question_unread_msg'] += $msg_related['related_count'];
			                        break;
				            }
				        }else if(($msg_related['message_type'] == 3)){
				            $menuLabelList[$platform_type]['system_unread_msg'] += $msg_related['related_count'];
				        }
				        	
				        $menuLabelList[$platform_type]['all_unread_msg'] += $msg_related['related_count'];
				        $menuLabelList['all_platform_unread'] += $msg_related['related_count'];
				    }
					
				}
// 				//错误信息数量赋值
// 				if(!empty($platform_failMessage_array)){
// 				    foreach ($platform_failMessage_array as $key=>$val){
// 				        if(isset($menuLabelList[$key])){
// 				            $menuLabelList[$key]['fail_message'] = $platform_failMessage_array[$key];
// 				        }
// 				    }
// 				}
			}
			
			$msg_statistics = $menuLabelList;
			//ConfigHelper::setConfig("Message/left_menu_statistics", json_encode($msg_statistics));
			RedisHelper::setMessageCache($puid,'','MenuStatisticData',json_encode($msg_statistics));
			
		}//end of not cached
		$menuArray = self::setMenuData($msg_statistics);
		self::setDashBoardMessageData($msg_statistics);//setDashBord数据
		return $menuArray;
// 		return $msg_statistics;
	}
	
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 组织新的左侧菜单
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param $msg_array 所有平台统计的总数据
	 +---------------------------------------------------------------------------------------------
	 * @return array  menu 菜单；active 选中项；
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lwj		2016/4/11				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	public static function setMenuData($msg_array){
	    $backup_array = array();
	    //子账号 权限控制 start
	    $isParent = \eagle\modules\permission\apihelpers\UserApiHelper::isMainAccount();
	    //true 为主账号， 不需要增加平台过滤 ， false 为子账号， 需要增加权限控制
	    if ($isParent == false){
	        $UserAuthorizePlatform = \eagle\modules\permission\apihelpers\UserApiHelper::getUserAuthorizePlatform();
	        if (!in_array('all',$UserAuthorizePlatform)){
	            if(!empty($UserAuthorizePlatform)){
	                foreach ($UserAuthorizePlatform as $v){
	                    if(isset($msg_array[$v])){
	                        $backup_array[$v] = $msg_array[$v];
	                    }
	                }
	            }
	            $msg_array = $backup_array;
	        }
	    }
	    //子账号 权限控制 end
	    $platform_array = [];
// 	    $platform_array = [
// 	        '客户管理' =>[
// 	            'icon'=>'icon-jiufen',
// 	            'items'=>[
// 	                '所有客户'=>[
// 	                    'url'=>Url::to(['/message/all-customer/customer-list','selected_type'=>'所有客户']),
//                         'tabbar'=>0
//                     ],
//                 ],
//             ],
//         ];
	    if(!empty($msg_array)){
	        $platform_array = [
	            '客户管理' =>[
	                'icon'=>'icon-jiufen',
	                'items'=>[
	                    '所有客户'=>[
	                        'url'=>Url::to(['/message/all-customer/customer-list','selected_type'=>'所有客户']),
	                            'tabbar'=>0
	                   ],
	               ],
	            ],
	        ];
	         if(isset($msg_array['ebay'])){
	             $num = $msg_array['ebay'];
	             $platform_array['eBay 站内信/留言'] = [
	                 'icon'=>'icon-fa-mail',
	                 'items'=>[
	                     '所有信息(eBay)'=>[
	                         'url'=>Url::to(['/message/all-customer/show-letter','select_platform'=>'ebay','selected_type'=>'所有信息(eBay)']),
	                         'tabbar'=>$num['all_unread_msg']
	                         ],
	                     '订单相关(eBay)'=>[
	                         'url'=>Url::to(['/message/all-customer/show-letter','select_platform'=>'ebay','select_type'=>'O','selected_type'=>'订单相关(eBay)']),
	                         'tabbar'=>$num['order_unread_msg']
	                         ],
	                     '商品相关(eBay)'=>[
	                         'url'=>Url::to(['/message/all-customer/show-letter','select_platform'=>'ebay','select_type'=>'P','selected_type'=>'商品相关(eBay)']),
	                         'tabbar'=>$num['product_unread_msg']
	                         ],
	                     '系统平台(eBay)'=>[
	                         'url'=>Url::to(['/message/all-customer/show-letter','select_platform'=>'ebay','select_type'=>'S','selected_type'=>'系统平台(eBay)']),
	                         'tabbar'=>$num['system_unread_msg']
	                         ],
	                     'ebay纠纷'=>[
	                         'url'=>Url::to(['/message/all-customer/show-ebay-disputes','selected_type'=>'ebay纠纷']),
	                         ],
	                 ],
	             ];
	         }
	         
	         if(isset($msg_array['aliexpress'])){
	             $num2=$msg_array['aliexpress']['order'];
	             $num6=$msg_array['aliexpress']['station'];
	             $all_num = $num2['all_unread_msg'] + $num6['all_unread_msg'];
	             $platform_array['速卖通  站内信/留言'] = [
	                 'icon'=>'icon-fa-mail',
	                 'items'=>[
	                     '所有信息(速卖通)'=>[
	                         'url'=>Url::to(['/message/all-customer/show-letter','select_platform'=>'aliexpress','selected_type'=>'所有信息(速卖通)']),
                             'tabbar'=>$all_num
                             ],
                         '订单留言'=>[
                             'url'=>Url::to(['/message/all-customer/show-letter','select_platform'=>'aliexpress','message_type'=>1,'select_type'=>'O','selected_type'=>'订单留言']),
                             'tabbar'=>$num2['order_unread_msg']
                         ],
                         '站内信-订单'=>[
                             'url'=>Url::to(['/message/all-customer/show-letter','select_platform'=>'aliexpress','message_type'=>2,'select_type'=>'O','selected_type'=>'站内信-订单']),
                             'tabbar'=>$num6['order_unread_msg']
                             ],
                         '站内信-商品'=>[
                             'url'=>Url::to(['/message/all-customer/show-letter','select_platform'=>'aliexpress','message_type'=>2,'select_type'=>'P','selected_type'=>'站内信-商品']),
                             'tabbar'=>$num6['product_unread_msg']
                         ],
                         '站内信-其他'=>[
                             'url'=>Url::to(['/message/all-customer/show-letter','select_platform'=>'aliexpress','message_type'=>2,'select_type'=>'M','selected_type'=>'站内信-其他']),
                             'tabbar'=>$num6['other_unread_msg']                         
                         ],
                    ],
                 ];
 	         }
 	         
 	         if(isset($msg_array['dhgate'])){
 	             $num3 = $msg_array['dhgate'];
 	             $platform_array['敦煌  站内信/留言'] = [
 	                 'icon'=>'icon-fa-mail',
 	                 'items'=>[
                         '所有信息(敦煌)'=>[
                             'url'=>Url::to(['/message/all-customer/show-letter','select_platform'=>'dhgate','selected_type'=>'所有信息(敦煌)']),
                             'tabbar'=>$num3['all_unread_msg']
                             ],
                         '订单相关(敦煌)'=>[
                             'url'=>Url::to(['/message/all-customer/show-letter','select_platform'=>'dhgate','select_type'=>'O','selected_type'=>'订单相关(敦煌)']),
                             'tabbar'=>$num3['order_unread_msg']
                             ],
                         '商品相关(敦煌)'=>[
                             'url'=>Url::to(['/message/all-customer/show-letter','select_platform'=>'dhgate','select_type'=>'P','selected_type'=>'商品相关(敦煌)']),
                             'tabbar'=>$num3['product_unread_msg']
                             ],
                         '系统平台(敦煌)'=>[
                             'url'=>Url::to(['/message/all-customer/show-letter','select_platform'=>'dhgate','select_type'=>'S','selected_type'=>'系统平台(敦煌)']),
                             'tabbar'=>$num3['system_unread_msg']
                             ],
                    ],
                 ];
 	         }
 	         
 	         if(isset($msg_array['wish'])){
 	             $num4 = $msg_array['wish'];
 	             $platform_array['wish 站内信/留言'] = [
 	                 'icon'=>'icon-fa-mail',
 	                 'items'=>[
 	                     '所有信息(wish)'=>[
 	                         'url'=>Url::to(['/message/all-customer/show-letter','select_platform'=>'wish','selected_type'=>'所有信息(wish)']),
 	                             'tabbar'=>$num3['all_unread_msg']
 	                         ],
                         ],
                     ];
 	         }
 	         
 	         if(isset($msg_array['cdiscount'])){
 	             $num5 = $msg_array['cdiscount'];
 	             $platform_array['cdiscount 订单留言'] = [
 	                 'icon'=>'icon-fa-mail',
 	                 'items'=>[
 	                     '所有信息'=>[
 	                         'url'=>Url::to(['/message/all-customer/show-letter','select_platform'=>'cdiscount','selected_type'=>'所有信息']),
                             'tabbar'=>$num5['all_unread_msg']
                             ],
                         'Your claims'=>[
                             'url'=>Url::to(['/message/all-customer/show-letter','select_platform'=>'cdiscount','select_type'=>'O','selected_type'=>'Your claims']),
                             'tabbar'=>$num5['order_unread_msg']
                         ],
                         'Orders questions'=>[
                             'url'=>Url::to(['/message/all-customer/show-letter','select_platform'=>'cdiscount','select_type'=>'Q','selected_type'=>'Orders questions']),
                             'tabbar'=>$num5['order_question_unread_msg']
                         ],
                    ],
                 ];
 	         }
 	         
 	         if(isset($msg_array['priceminister'])){
 	             $num6 = $msg_array['priceminister'];
 	             $platform_array['priceminister 订单留言'] = [
 	                 'icon'=>'icon-fa-mail',
 	                 'items'=>[
 	                     '所有信息(PM)'=>[
 	                         'url'=>Url::to(['/message/all-customer/show-letter','select_platform'=>'priceminister','selected_type'=>'所有信息(PM)']),
                             'tabbar'=>$num6['all_unread_msg']
                             ],
                         'ItemId message'=>[
                             'url'=>Url::to(['/message/all-customer/show-letter','select_platform'=>'priceminister','select_type'=>'O','selected_type'=>'ItemId message']),
                             'tabbar'=>$num6['order_unread_msg']
                         ],
                     ],
                 ];
 	         }
	    }
	    
        $platform_array['模版管理'] = [
            'icon'=>'icon-moban',
            'items'=>[
                '所有模版'=>[
                    'url'=>Url::to(['/message/all-customer/mail-template','select_platform'=>'template-manage','selected_type'=>'所有模版']),
                    ],
                ],
         ];
	    

// 	    //合并菜单
// 	    if(!empty($platform_array)){
// 	        $merg_array = $menu + $platform_array;
// 	    }else{
// 	        $merg_array = $merg_array;
// 	    }
	    
	    $menu_array = [];
	    $selected_type = !empty($_REQUEST['selected_type'])?$_REQUEST['selected_type']:"";
	    $meun_array = [
	        'menu'=>$platform_array,
            'active'=>$selected_type        
	    ];
	    return $meun_array;
	}
	
	
	/**
	 * 名称：sendTicketMsg 
	 * Desc：可以主动发送站内信或者根据传递入来的$order_id找之前的ticket信息
	 * $order_id必须存在于user库中，不然发送失败
	 * 发送的是订单留言
	 * 
	 * 1.如果cs_customer表不存在则会自动创建该用户记录
	 * 2.如果不存在cs_ticket_session的会话记录则会创建会话
	 * 3.会保存cs_ticket_message记录
	 * 4.会保存队列表message_api_queue 待发送

	 * 参数： 
	 * @param $platform 平台类型:wish/amazon/ebay/aliexpress
	 * @param $order_id 订单Id
	 * @param $contents 内容: string,free text
	 * @param $params  array() , 非必填，可选。
			在array里面设置key包含
			seller_id卖家id ,
			customer_id 客户id ,
			customer_nickname 客户呢称
				    可选参数的填入可以提高系统的性能不用再去user库中查找
	 * @param $puid 用户uid ,非必填，可选。				    
	  
	 * @return arrar(success =>true , message=>’’,’ticket_id’=>111,”msg_id”=>123)
		success :返回是否发送成功true false
		Message: 错误信息
		ticket_id： 客服系统内部用对话自动标识字段
		Msg_id: 该消息生成的message id

	 * use eagle\modules\message\apihelpers\MessageApiHelper;
	 * Sample:
	 * MessageApiHelper::sendTicketMsg ($platform=”aliexpress”, $order_id=’14654-897’,$contents=”ebay”)
	 * 
	 * +-------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		hqw		2015/8/6				初始化
	 * +-------------------------------------------------------------------------------------------
	 */
	public static function sendTicketMsg($platform, $order_id, $contents, $params=array(),$puid = ''){
		$journal_id = SysLogHelper::InvokeJrn_Create("Message",__CLASS__, __FUNCTION__ , array($platform, $order_id, $contents, $params));
		
		//切换user数据库
		if($puid != ""){
		 
		}else{
			//用于获取当前登录的uid
			$puid = \Yii::$app->user->id;
		}
		
		$order = OdOrder::findOne(['order_source'=>$platform,'order_source_order_id'=>$order_id]);
		
		if ($order == null)
			return array('success'=>false,'message'=>'Does not exist OrderId:'.$order_id);
		
		$tSession_param = array(
				'platform_source'=>$platform,
				'seller_id'=>$order->selleruserid,
				'buyer_id'=>$order->source_buyer_user_id
		);
		
		if($platform != 'ebay'){
			$tSession_param['message_type'] = '1';
			$tSession_param['related_id'] = $order_id;
		}
		
		$tSession_obj = TicketSession::findOne($tSession_param);
		
		if ($tSession_obj == null){
			$sessionList = array(
					'ticket_id' => '',
					'session_id' => '',
					'platform_source' => $platform,
					'message_type' => '1',
					'seller_id' => $order->selleruserid,
					'related_id' => $order_id,
					'related_type' => 'O',
					'buyer_id' => $order->source_buyer_user_id,
					'seller_nickname' => '',
					'buyer_nickname' => $order->consignee,
					'has_read' => 1,
					'created' => date('Y-m-d H:i:s', time ()),
					'updated' => date('Y-m-d H:i:s', time ()),
					'lastmessage' => date('Y-m-d H:i:s', time ()),
					'last_omit_msg' => (mb_strlen($contents,'utf-8') > 20) ? mb_substr($contents, 0, 20,"UTF-8")."...\n" : $contents,
			);
		}
		
		if ($tSession_obj != null && empty($sessionList)){
			$sessionList = array(
					'ticket_id' => $tSession_obj->ticket_id,
					'session_id' => $tSession_obj->session_id,
					'related_id' => $tSession_obj->related_id,
					'seller_id' => $tSession_obj->seller_id,
					'buyer_id' => $tSession_obj->buyer_id,
					'buyer_nickname' => $tSession_obj->buyer_nickname,
			);
		}
		
		$msgOne = array(
				'ticket_id' => $sessionList['ticket_id'],
				'session_id' => $sessionList['session_id'],
				'contents' => $contents,
				'headers' => "",
				'created' => date('Y-m-d H:i:s', time ()),
				'updated' => date('Y-m-d H:i:s', time ()),
				'addi_info' => "",
				'app_time' => date('Y-m-d H:i:s', time ()),
				'platform_time' => date('Y-m-d H:i:s', time ()),
				'seller_id' => $sessionList['seller_id'],
				'buyer_id' => $sessionList['buyer_id'],
		);
		
		//速卖通发的就是订单留言
		if($platform=='aliexpress'){
			$msgBufferArr = array(
					'seller_id'=>$sessionList['seller_id'],
					'message_type'=>'1',
					'order_id'=>$sessionList['related_id'],
					'buyer_id'=>$order->source_buyer_user_id,
			);
		}
		
		//ebay站内信没有订单留言和站内信区分所以统一加入站内信
		if($platform=='ebay'){
			$tmpAddi_info = array();
			$tmpAddi_info['buyer_nickname'] = $order->consignee;
			$tmpAddi_info['ItemID'] = $order_id;
			$tmpAddi_info['msgTitle'] = "Anwser about:Item#".$order_id;//用已有的$TSession_obj->msgTitle 超长，所以这里统一改。 由于目前站内信是回复的所以用这个subject
			
			$msgBufferArr = array(
					'seller_id'=>$sessionList['seller_id'],
					'message_type'=>'2',
					'buyer_id'=>$order->source_buyer_user_id,
					'addi_info' => json_encode($tmpAddi_info)
			);
		}
		//用于判断Customer不存在时是否新建Customer
		$sessionList['isCreateCustomer'] = 'Y';
		
		$result = self::platformSaveMsgQueue($puid, $platform, $msgOne, $msgBufferArr, $sessionList);
		
		//因为接口写明返回message，而platformSaveMsgQueue是之前写落代码所以为了兼容只能重新赋值
		$result['message']=$result['error'];
		unset($result['error']);
		
		//write the invoking result into journal before return
		SysLogHelper::InvokeJrn_UpdateResult($journal_id, $result);
		
		
		return $result;
	}
	
	/**
	 * 对某个session进行标记已处理，即最终维护cs_customer：os_flag
	 * 进行标记已处理字段是has_replied，而不是has_handled
	 * 
	 * 参数： 
	 * @param $platform 平台类型:wish/amazon/ebay/aliexpress/dhagte
	 * @param $ticket_id 会话Id: 201
	 * 
	 * @return arrar(success =>true , error=>’’)
		success :返回是否发送成功true false
		error: 错误信息
	 * 
	 * +-------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		hqw		2015/8/6				初始化
	 * +-------------------------------------------------------------------------------------------
	 */
	public static function markSessionHandled($platform, $ticket_id){
		try{
			//对该会话has_replied设置为已回复
			$command = Yii::$app->subdb->createCommand("update cs_ticket_session set has_replied='1' where ticket_id  = :ticket_id"  );
			$command->bindValue(':ticket_id', $ticket_id, \PDO::PARAM_STR);
			$affectRows = $command->execute();
		}catch (Exception $e){
			\Yii::error(__CLASS__.' function:'.__FUNCTION__.print_r($e,true));
			return array('success'=>false,'error'=>__CLASS__.' function:'.__FUNCTION__.print_r($e,true));
		}
		
		$result = self::customerUpdateOsFlag('', $platform, $ticket_id);
		
		return $result;
	}
	
	/**
	 * 根据传入来的平台来源订单号来查找是否存在站内信和订单留言
	 *
	 * 参数：
	 * @param $plat_source_order_id	平台来源订单号:65000934059169	必填
	 * @param $platform	平台类型:wish/amazon/ebay/aliexpress/dhagte	非必填
	 *
	 * @return arrar(success =>true , error=>’’)
	 success :返回是否发存在站内信或订单留言 true:表示存在 false:表示不存在
	 error: 错误信息
	 *
	 * use eagle\modules\message\apihelpers\MessageApiHelper;
	 * Sample:
	 * MessageApiHelper::isOrderWithMsg('1575825007','aliexpress');
	 *
	 * +-------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		hqw		2015/8/25				初始化
	 * +-------------------------------------------------------------------------------------------
	 */
	public static function isOrderWithMsg($plat_source_order_id, $platform=''){
		if(empty($plat_source_order_id)){
			return array('success'=>false,'error'=>'平台来源订单号为必填');
		}
		
		$conn=\Yii::$app->subdb;
		
		$queryTmp = new Query;
		
// 		$queryTmp->select("ticket_id")
// 					->from("cs_ticket_session t")
// 					->where(['and', " 1 exists(select 1 from cs_ticket_message a1 where a1.related_type='O'
// 					 and a1.ticket_id=t.ticket_id and a1.related_id=:related_id limit 1 ) "],[':related_id'=>$plat_source_order_id]);
		
		$queryTmp->select("t.ticket_id")
		->from("cs_ticket_session t")
		->leftJoin("cs_ticket_message a1", " a1.ticket_id = t.ticket_id ")
		->where(['and', " a1.related_type='O' and a1.ticket_id=t.ticket_id and a1.related_id=:related_id "],
				[':related_id'=>$plat_source_order_id]);
		
		if ($platform != '')
			$queryTmp->andWhere(['and', "t.platform_source=:platform"],[':platform'=>$platform]);
		
		$queryTmp->limit(1);
		
		$oneSessionArr = $queryTmp->createCommand($conn)->queryAll();
		
		if (count($oneSessionArr) == 0){
			return array('success'=>false,'error'=>'');
		}else{
			return array('success'=>true,'error'=>'');
		}
	}
	
	/**
	 * 根据传入来的平台来源订单号来查找关于该单号的站内信和订单留言列表
	 *
	 * 参数：
	 * @param $plat_source_order_id	平台来源订单号:65000934059169	必填
	 * @param $platform	平台类型:wish/amazon/ebay/aliexpress/dhagte	非必填
	 * @param $params  array() , 非必填，可选。
			在array里面设置key包含
			sort 排序字段 , 默认t.lastmessage , 非必填，可选。
			order 倒序排序 或 正序排序 desc Or asc, 非必填，可选。 默认desc
	 *
	* @return arrar(success =>true , error=>’’,ticketSessionList=>array())
		success :返回是否执行成功 true false
		error: 错误信息
		ticketSessionList => Array
        (
            [data] => Array
                (
                    [0] => Array
                        (
                            [ticket_id] => 132
                            [platform_source] => dhgate
                            [message_type] => 1
                            [related_id] => 1575825007
                            [related_type] => O
                            [seller_id] => alldeals001
                            [buyer_id] => ff8080814daf6702014dd216a14b53d1
                            [session_id] => 351627013
                            [has_read] => 1
                            [has_replied] => 1
                            [has_handled] => 0
                            [created] => 2015-08-19 16:32:20
                            [updated] => 2015-08-19 16:32:46
                            [lastmessage] => 2015-08-07 11:00:09
                            [msg_sent_error] => N
                            [addi_info] => {"recieverId":"ff8080814daf6702014dd
16a14b53d2","senderId":"ff8080814dae42ff014dc6ec2d81234f"}
                            [last_omit_msg] => hi, 201508071040
                            [seller_nickname] => alldeals001
                            [buyer_nickname] => loloxiao
                            [msgTitle] => PO#1575825007
                            [track_no] =>
                        )
                        ...
                        ...
                        ...
                )
        )
	 *
	 * use eagle\modules\message\apihelpers\MessageApiHelper;
	 * Sample:
	 * MessageApiHelper::getSessionListByOrderId('1575825007','aliexpress');
	 * 
	 * +-------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		hqw		2015/8/25				初始化
	 * +-------------------------------------------------------------------------------------------
	 */
	public static function getSessionListByOrderId($plat_source_order_id, $platform='', $params = array()){
		if(empty($plat_source_order_id)){
			return array('success'=>false,'error'=>'平台来源订单号为必填');
		}
		
		$conn=\Yii::$app->subdb;
		
		$queryTmp = new Query;
		
		$queryTmp->select(" t.ticket_id,t.platform_source,t.message_type,
				t.related_id,t.related_type,t.seller_id,t.buyer_id,t.session_id,t.has_read,t.has_replied,t.has_handled,t.created,
				t.updated,t.lastmessage,t.msg_sent_error,t.addi_info,t.last_omit_msg,t.seller_nickname,
				t.buyer_nickname,t.msgTitle,'' as `track_no`")
				->from("cs_ticket_session t")
				->leftJoin("cs_ticket_message a1", " a1.ticket_id = t.ticket_id ")
				->where(['and', " a1.related_type='O' and a1.ticket_id=t.ticket_id and a1.related_id=:related_id "],[':related_id'=>$plat_source_order_id]);
		$queryTmp->distinct();
		
		if ($platform != '')
			$queryTmp->andWhere(['and', "t.platform_source=:platform"],[':platform'=>$platform]);
		
		if(empty($params['sort'])){
			$params['sort'] = ' t.lastmessage ';
			$params['order'] = 'desc';
		}
			
		$queryTmp->orderBy($params['sort']." ".$params['order']);
		
		$allSessionDataArr = $queryTmp->createCommand($conn)->queryAll();
		
		foreach ($allSessionDataArr as $allSessionDataKey => $allSessionData){
			if ($allSessionData['related_type'] != 'O') continue;
			
			$result = OrderTrackerApiHelper::getTrackingNoByOrderId($allSessionData['seller_id'],$allSessionData['related_id']);
			$allSessionDataArr[$allSessionDataKey]['track_no'] = $result['track_no'];
		}
		
		$data['data'] = $allSessionDataArr;
		return array('success'=>true,'error'=>'','ticketSessionList'=>$data);
	}
	
	/**
	 * 获取指定平台，平台账号，平台站点（英国，美国。。。）推荐商品
	 * @param string $platform --- ebay,aliexpress等等
	 * @param string $seller_id --- 平台账号id
	 * @param string $platform_site_id --- 平台销售站点 ebay和amazon是有这个概念的。  US、UK等等
	 * @param number $product_count --- 需要的商品数量
	 *
	 * @return  没有结果时候，返回array()
	 * 有结果的时候返回
	 * Return array(
	 ‘listing_id1’=>array(
	 ‘listing_id’=>’listing_id1’,
	 ’product_url’=>’http://ebay.com/sku1’,
	 ‘product_image’=>’http://xxxx.jpg’,
	 ‘sale_price’=>’19.6’,
	 ‘sale_currency’=>’EUR’,
	 ’product_name’=>’Very good iphone 5 case kitty’
	 ),
	 ‘listing_id2’=>array(
	 ‘listing_id’=>’listing_id2’,
	 ’product_url’=>’http://ebay.com/sku2’,
	 ‘product_image’=>’http://xxxx.jpg’,
	 ‘sale_price’=>’19.69’,
	 ‘sale_currency’=>’EUR’,
	 ’product_name’=>’Very good iphone 6 case kitty’
	 ),
	 }
	 *
	 */
	public static function getRecomProductsFor($platform='',$seller_id='',$platform_site_id='',$product_count=10){
		return TrackingRecommendProductHelper::getRecomProductsFor($platform,$seller_id,$platform_site_id,$product_count);
	}
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 把 所有发送失败Message重新发送一次
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param	$platform				//平台
	 * @param	$sellerid				//卖家ID
	 * @param	$site_id				//平台销售站点 ebay和amazon是有这个概念的。  US、UK等等
	 +---------------------------------------------------------------------------------------------
	 * @return na
	 *
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2015/9/25				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function resendAllFailureMessage($platform='' , $sellerid='' , $site_id=''){
		return self::msgStatusFailToPending( $platform,$sellerid, '0');
	}//end of resendAllFailureMessage
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 刷新 message 状态的
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param	$msg_id					//msg id
	 * @param	$ticket_id				//ticket id
	 * @param	$buyer_id				//买家ID
	 +---------------------------------------------------------------------------------------------
	 * @return na
	 *
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2015/9/25				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function refreshMessageStatusInfo($ticket_id = '', $buyer_id = ''){
		
		//step 1 刷新 session 部分  
		//是否指定ID
		$sqlStr = '';
		$sqlArr = [];
		/*
		if (!empty($msg_id)){
		
			$sqlStr = " msg_id=:msg_id and ";
			$sqlArr[':msg_id'] = $msg_id;
		}
		*/
		
		if (!empty($ticket_id)){
				
			$sqlStr .= " ticket_id=:ticket_id and ";
			$sqlArr[':ticket_id'] = $ticket_id;
		}
		
		//补充说明 ：关联 部分 cs_ticket_message 与  cs_ticket_session 直接用ticket_id
		//step 1-1.假如 cs_ticket_message 中有error message 即 存在cs_ticket_message.status = F的数据 时， 对session msg_sent_error不等于Y 的设置为 Y;
		
		$sql = "update cs_ticket_session set msg_sent_error = 'Y' where ".$sqlStr." msg_sent_error <> 'Y'  and   ticket_id in (select ticket_id from cs_ticket_message where status = 'F')";
		//echo \Yii::$app->subdb->createCommand($sql,$sqlArr)->getRawSql();
		$result = \Yii::$app->subdb->createCommand($sql,$sqlArr)->execute();
		
		//step 1-2.假如 cs_ticket_message 中没有error message 即不存在  cs_ticket_message.status = F 的数据时， 对 session msg_sent_error不等于N 的设置为 N;
		
		$sql = "update cs_ticket_session set msg_sent_error = 'N' where ".$sqlStr." msg_sent_error <> 'N'  and   ticket_id not in (select ticket_id from cs_ticket_message where status = 'F')";
		$result = \Yii::$app->subdb->createCommand($sql,$sqlArr)->execute();
		
		//step 2 刷新 customer 部分
		
		$sqlStr = '';
		$sqlArr = [];
		if (!empty($ticket_id)){
				
			$sqlStr = " customer_id =:buyer_id and ";
			$sqlArr[':buyer_id'] = $buyer_id;
		}
		
		//补充说明 ： cs_customer:customer_id 与  cs_ticket_session:buyer_id 
		//step 2-1.假如 cs_ticket_session 中有error message 即 存在cs_ticket_session.msg_sent_error =  Y 的数据 时， 对customer msg_sent_error不等于Y 的设置为 Y;
		
		$sql = "update cs_customer set msg_sent_error = 'Y' where ".$sqlStr." msg_sent_error <> 'Y'  and  customer_id   in (select buyer_id  from cs_ticket_session where msg_sent_error  <> 'Y')";
		$result = \Yii::$app->subdb->createCommand($sql,$sqlArr)->execute();
		
		//step 2-2.假如 cs_ticket_session 中没有error message 即不存在cs_ticket_session.msg_sent_error =  Y 并且有session 的情况下， 对 customer msg_sent_error不等于Y 的设置为 C;
		
		$sql = "update cs_customer set msg_sent_error = 'C' where ".$sqlStr." msg_sent_error <> 'C'  and  customer_id  not in (select buyer_id from cs_ticket_session where msg_sent_error  = 'Y')";
		$result = \Yii::$app->subdb->createCommand($sql,$sqlArr)->execute();
		
		//step 2-3.假如cs_ticket_session 没有session数据的情况下，，对 customer msg_sent_error不等于N 的设置为 N;
		
		$sql = "update cs_customer set msg_sent_error = 'N' where ".$sqlStr." msg_sent_error <> 'N' and customer_id  not in (select buyer_id from cs_ticket_session )";
		$result = \Yii::$app->subdb->createCommand($sql,$sqlArr)->execute();
		
	}//end of refreshMessageStatusInfo
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 修改保存 用户对这个template的详情
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param  $template 与 cs_msg_template 对应
	 +---------------------------------------------------------------------------------------------
	 * @return array
	 * 					success  		boolean  调用 是否成功
	 * 					message  		string   调用结果说明
	 * 					template_id     int		 template id
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2015/9/28				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function saveCustomerMessageTemplateDetail($template){
		try {
			if (!empty($template['id'])){
				//check create or update
				$model = CustomerMsgTemplateDetail::findOne(['id'=>$template['id']]);
			}else{
				
			}
			
			if (empty($model)){
				//model not found , then create a new Template
				$model = new CustomerMsgTemplateDetail();
			}
			
			if (!empty($model->addi_info))
				$addi_info = json_decode($model->addi_info,true);
			else
				$addi_info = [];
				
			if (!empty($template['body'])){
				if (stripos( $template['body'],'买家查看包裹追踪及商品推荐链接')){
					$addi_info['recom_prod'] = 'Y';
				}else{
					if (isset($addi_info['recom_prod'])) unset($addi_info['recom_prod']);
				}
			}
				
			if (empty($template['addi_info'])){
				$template['addi_info'] = json_encode($addi_info);
			}else{
			
				if (is_string($template['addi_info'])) $template['addi_info'] = json_decode($template['addi_info'],true);
			
				if (is_array($template['addi_info']))
					$template['addi_info'] = array_merge($addi_info,$template['addi_info']);
			}
				
			if (is_array($template['addi_info'] )) $template['addi_info'] = json_encode($template['addi_info'] );
				
			//var_dump($template);
			$model->attributes = $template;
				
			if (!empty($model->id))
				$result ['template_id'] = $model->id;
				
			if (!$model->save()){
				\Yii::info(['tracking',__CLASS__,__FUNCTION__,'Online', json_encode($model->errors) ],"edb\global");
				$result ['success'] = false;
				$result ['message'] = $model->errors;
			}else{
				$result ['success'] = true;
				$result ['message'] = "保存成功";
			}
		} catch (\Exception $e) {
			//\Yii::info(['message',__CLASS__,__FUNCTION__,'Online',$e->getMessage() ],"edb\global");
			$result ['success'] = false;
			$result ['message'] = $e->getMessage();
		}
		return $result;
	}//end of saveCustomerMessageTemplateDetail
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 修改保存 用户对这个template的变更
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param
	 * 			$template [
	 * 					'id'=>1												//模板ID ， 假如新建为0或者不需要设置， 修改需要填入
	 * 					'type'=>"L",        								//模板类型 ， L为卖家模版 ， C为官方模版
	 * 					'puid'=>1,											//puid 
	 * 					'seq'=>1,											//优先级
	 * 					'detail_id'=>1										//tempalte detail id 假如新建为0或者不需要设置， 修改需要填入
	 * 					'template_name'=>'已签收请求好评(预设1)',   				// 模版名称
	 * 					'lang'=>'en' , 										//模版语言
	 * 					'subject'=>'hi , dear xxxx' , 						// 邮件标题
	 * 					'content'=>'your tracker number xxx xxx ....'  		// 邮件内容
	 * 
	 * ]
	 +---------------------------------------------------------------------------------------------
	 * @return array
	 * 					success  		boolean  调用 是否成功
	 * 					message  		string   调用结果说明
	 * 					template_id     int		 template id
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2015/9/28				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function saveCustomerMessageTemplate($template){
		try {
			if (!empty($template['id'])){
				
				//detail 为空则表示detail 新建， 不为空表示为修改
				if (empty($template['detail_id'])){
					// 语言 ， 与template id 为确定是否重复的依据
					$existTemplate = CustomerMsgTemplateDetail::find()->andWhere(['lang'=>$template['lang'] , 'template_id'=>$template['id']])->count();
					if (!empty($existTemplate)){
						return ['success'=>false,'message'=>'模板'.$template['lang'].'重复!'];
					}
					
				}
				
				//check create or update
				$model = CustomerMsgTemplate::findOne(['id'=>$template['id']]);
				
			}else{
				// 语言 ， 与template id 为确定是否重复的依据
				$existTemplate = CustomerMsgTemplate::find()->andWhere(['puid'=>$template['puid'] ,'type'=>$template['type'] , 'template_name'=>$template['template_name']])->count();
				if (!empty($existTemplate)){
					return ['success'=>false,'message'=>'模板名称重复!'];
				}
			}
			
			if (empty($model)){
				//model not found , then create a new Template
				$model = new CustomerMsgTemplate();
			}
				
			if (!empty($model->addi_info))
				$addi_info = json_decode($model->addi_info,true);
			else
				$addi_info = [];
			
			if (empty($template['addi_info'])){
				$template['addi_info'] = '';
				
			}else{
					
				if (is_string($template['addi_info'])) $template['addi_info'] = json_decode($template['addi_info'],true);
					
				if (is_array($template['addi_info']))
					$template['addi_info'] = array_merge($addi_info,$template['addi_info']);
			}
			
			if (is_array($template['addi_info'] ) ) $template['addi_info'] = json_encode($template['addi_info'] );
			
			if (empty($template['seq'])) $template['seq'] = 3;
			
			if (empty($template['type'])) $template['type'] = 'L';
			
			//$template['id'] 为空表示 为新增template
			if (empty($template['create_time']) && empty($template['id'])) $template['create_time'] = date('Y-m-d H:i:s', time ());
			
			//每次都要保存
			if (empty($template['update_time'])) $template['update_time'] = date('Y-m-d H:i:s', time ());
			
			//var_dump($template);
			$model->attributes = $template;
			
			if (!empty($model->id))
				$result ['id'] = $model->id;
			
			if (!$model->save()){
				\Yii::info(['tracking',__CLASS__,__FUNCTION__,'Online', json_encode($model->errors) ],"edb\global");
				$result ['success'] = false;
				$result ['message'] = $model->errors;
			}else{
				$result ['success'] = true;
				$result ['message'] = "保存成功";
				if (!empty($model->id)){
					$result ['template_id'] = $model->id;
					$templateDetail ['template_id'] = $model->id;
					$sub_model = new CustomerMsgTemplateDetail();
					$detailAttrs =  $sub_model->attributes;
					//var_dump($template);
					//var_dump($detailAttrs);
					foreach($template as $fieldName=>$value){
						//因为detail表也有id表， 所以跳过这个id值
						if (strtolower($fieldName)=='id'){
							//echo "<br>g1".$fieldName;
						}elseif (array_key_exists($fieldName, $detailAttrs)){
							$templateDetail[$fieldName] = $value;
							//echo "<br>g2".$fieldName;
						}else{
							//echo "<br>g3".$fieldName;
						}
					}
				
					if (!empty($template['detail_id'])){
						$templateDetail['id'] = $template['detail_id'];
					}
					
					if (!empty($templateDetail)){
						//var_dump($templateDetail);
						$detailResult = self::saveCustomerMessageTemplateDetail($templateDetail);
					}
				}
			}
			//var_dump($result);
		} catch (\Exception $e) {
			//未知错误
			$result = [
			'success'=>false,
			'message'=>$e->getMessage(),
			'template'=>$template,
			];
		}
		
		return $result;
	}//end of saveCustomerMessageTemplate
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 根据template id 删除 template 
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param
	 * 			$template_id                         //模板ID ， 假如新建为0或者不需要设置， 修改需要填入
	 +---------------------------------------------------------------------------------------------
	 * @return array
	 * 					success  		boolean  调用 是否成功
	 * 					message  		string   调用结果说明
	 * 					template_id     int		 template id
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2015/10/14				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function deleteCustomerMessageTemplateData($template_id){
		try {
			$result = [
			'success'=>true,
			'message'=>'',
			'template_id'=>$template_id,
			];
			
			// $template_id 无效
			if (empty($template_id)){
				$result = [
				'success'=>false,
				'message'=>'请选择模版！',
				'template_id'=>$template_id,
				];
			}else{
				//$template_id 无效 
				$del_row = CustomerMsgTemplate::deleteAll(['type'=>'L' , 'id'=> $template_id]);
				//成功删除template
				if (!empty($del_row)){
					//template detail 删除
					$sub_del_row = CustomerMsgTemplateDetail::deleteAll(['template_id'=>$template_id]);
					
				}else{
					$result = [
					'success'=>false,
					'message'=>'该模版已经删除了！',
					'template_id'=>$template_id,
					];
				}
			}
			
		} catch (\Exception $e) {
			//未知错误
			$result = [
			'success'=>false,
			'message'=>$e->getMessage(),
			'template_id'=>$template_id,
			];
			//\Yii::error(['Message',__CLASS__,__FUNCTION__,'Online',$e->getMessage()],"edb\global");
		}
		return $result;
	}//end of deleteCustomerMessageTemplateData
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 获取已接入站内信的账户
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param
	 +---------------------------------------------------------------------------------------------
	 * @return
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		hqw		2015/12/17				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	public static function getPlatformAccountList($puid = false){
		if(empty($puid)){
			$puid = \Yii::$app->user->identity->getParentUid();
		}
		
		$aliexpressUsers = \eagle\models\SaasAliexpressUser::find()->select(['sellerloginid'])->where(['uid'=>$puid])->asArray()->all();
		$wishUsers = \eagle\models\SaasWishUser::find()->select(['store_name'])->where(['uid'=>$puid])->asArray()->all();
		$ebayUsers = \eagle\models\SaasEbayUser::find()->select(['selleruserid'])->where(['uid'=>$puid])->asArray()->all();
		$dhgateUsers = \eagle\models\SaasDhgateUser::find()->select(['sellerloginid'])->where(['uid'=>$puid])->andWhere('is_active <> 3')->asArray()->all();
		$cdiscountUsers = \eagle\models\SaasCdiscountUser::find()->select(['username'])->where(['uid'=>$puid])->asArray()->all();
		$priceministerUsers = \eagle\models\SaasPriceministerUser::find()->select(['username'])->where(['uid'=>$puid])->asArray()->all();
		
		$aliSelleruids = \common\helpers\Helper_Array::toHashmap($aliexpressUsers , 'sellerloginid' , 'sellerloginid');
		$wishSelleruids = \common\helpers\Helper_Array::toHashmap($wishUsers , 'store_name' , 'store_name');
		$ebaySelleruids = \common\helpers\Helper_Array::toHashmap($ebayUsers , 'selleruserid' , 'selleruserid');
		$dhgateSelleruids = \common\helpers\Helper_Array::toHashmap($dhgateUsers , 'sellerloginid' , 'sellerloginid');
		$cdiscountSelleruids = \common\helpers\Helper_Array::toHashmap($cdiscountUsers , 'username' , 'username');
		$priceministerSelleruids = \common\helpers\Helper_Array::toHashmap($priceministerUsers , 'username' , 'username');
		
		return array('aliexpress'=>$aliSelleruids , 'ebay'=>$ebaySelleruids , 'wish'=>$wishSelleruids, 'dhgate'=>$dhgateSelleruids, 'cdiscount'=>$cdiscountSelleruids, 'priceminister'=>$priceministerSelleruids);
	}
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * ebay站内信存在解释内容时，当时判断的情况小了导致content内容丢失	这个方法用来提供给站内信界面调用更新丢失的站内信
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		hqw		2016/02/27				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	public static function ebayMsgConversion(){
		$conn=\Yii::$app->subdb;
		$queryTmp = new Query;
		
		$queryTmp->select("t.ticket_id")
			->from("cs_ticket_session t")
			->leftJoin("cs_ticket_message a1", " a1.ticket_id = t.ticket_id ")
			->where(['and', " a1.content='' and t.platform_source='ebay' "]);
		$queryTmp->distinct();
		
		$allSessionDataArr = $queryTmp->createCommand($conn)->queryAll();
		
		if(count($allSessionDataArr) == 0){
			return false;
		}
		
		$tmpTicketIds = array();
		foreach ($allSessionDataArr as $allSessionData){
			$tmpTicketIds[] = $allSessionData['ticket_id'];
		}
		
		$tmpMsgs = TicketMessage::find()->where(['content'=>''])->andWhere(['in','ticket_id',$tmpTicketIds])->orderBy('msg_id')->all();
		
		try{
			foreach ($tmpMsgs as $tmpMsg){
				$str1 = self::msgDetectionJsonToArr($tmpMsg->msg_contact);
				if(!empty($str1)){
					$tmpMsg->content = MessageEbayApiHelper::extractApiMessageContent($str1);
					
					$tmpMsg->save(false);
				}
			}
		}catch(\Exception $e){
		}
	}
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 兼容站内信数据,因为以后将会压缩站内信msg_contact
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		hqw		2016/02/27				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	public static function msgDetectionJsonToArr($msgs, $type = 0){
		$str1 = json_decode($msgs,true);
		
		if(json_last_error() == JSON_ERROR_NONE){
			return $str1;
		}else{
			$str1 = $msgs;
			$str1 = base64_decode($str1);
			$str1 = gzuncompress($str1);
				
			if(!empty($str1)){
				$str1 = json_decode($str1,true);
				if(json_last_error() == JSON_ERROR_NONE){
					return $str1;
				}else{
					if($type == 1){
						return array('xlb_msg_error'=>'data_loss');
					}else{
						return array();
					}
				}
			}else{
				if($type == 1){
					return array('xlb_msg_error'=>'data_loss');
				}else{
					return array();
				}
			}
		}
	}
	
	/**
	 * 根据传进来的参数来设置是否同步站内信
	 *
	 * @param $platform	平台类型:wish/amazon/ebay/aliexpress/dhagte/cdiscount
	 * @param $platform_uid	saas账户ID
	 * @param $account_id	帐号
	 * @param $active	开启（1）或关闭（0），
	 * @return array('success'=>true,'error'=>'');
	 success :返回是设置成功true false
	 error: 错误信息
	 *
	 * +-------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lwj		2016/3/1				初始化
	 * +-------------------------------------------------------------------------------------------
	 */
	public static function setSyncMsg($platform, $platform_uid, $account_id, $active){
	    $saas_user = SaasMessageAutosync::find()->where(['sellerloginid'=>$account_id,'platform_uid'=>$platform_uid,'platform_source'=>$platform])->all();
	    if(empty($saas_user)){
	        return array('success' => false,'error'=>'没有找到相关用户！');
	    }else{
	        foreach ($saas_user as $user){
	            $user->is_active = $active;
	            $user->update_time = time();
	            $user->save();
	        }
	        return array('success' => true,'error'=>'');
	    }
	
	}
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 删除指定 的 站内信 同步 队列数据
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param
	 +---------------------------------------------------------------------------------------------
	 * @return
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2016/03/02				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	public static function delMsgQueue($platform,$platform_uid){
		return SaasMessageAutosync::deleteAll(['platform_uid'=>$platform_uid,'platform_source'=>$platform]);
	}
	
	/**
	 * 查看order_id对应的消息的所有状态
	 *
	 * @param $platform	平台类型:wish/amazon/ebay/aliexpress/dhagte/cdiscount
	 * @param $order_number	平台来源订单ID
	 * @return array('success'=>true,'msssage'=>''，
	 * 'data'=>[
	 *          'hasRead'=>$order_session_status['has_read']==0?'未读':'已读',
	            'hasReplied'=>$order_session_status['has_replied']==0?'未回复':'已回复',
	            'msgSentError'=>$order_session_status['msg_sent_error']=='Y'?'发送失败':'',
	 * ]
	 * );
	 success :返回是设置成功true false
	 msssage: 错误信息
	 *
	 * +-------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lwj		2016/5/4				初始化
	 * +-------------------------------------------------------------------------------------------
	 */
	public static function orderSessionStatus($platform, $order_number){
	    $order_session_status = TicketSession::find()->where(['platform_source'=>$platform,'related_id'=>$order_number])->asArray()->one();
	    if(empty($order_session_status)){
	        return array('success' => false,'message'=>'没有找到相关订单状态！','data'=>null);
	    }else{
	        $status =[
	            'hasRead'=>$order_session_status['has_read']==0?false:true,//未读/已读
	            'hasReplied'=>$order_session_status['has_replied']==0?false:true,//未回复/已回复
	            'msgSentError'=>$order_session_status['msg_sent_error']=='Y'?true:false,//发送失败
	        ];
	        return array('success' => true,'message'=>'' ,'data'=>$status);
	    }
	
	}
	
	/**
	 * 对需要同步消息状态到issue status 的订单进行处理，将状态同步到oms表
	 * @param string $order_source_order_id
	 * @param string $status
	 * @param string $platform
	 * @return multitype:boolean string
	 * @author	lzhl	2016/5/9	初始化
	 */
	public static function updateIssueStatusToOms($order_source_order_id,$status,$platform){
		$platform_arr = ['cdiscount'];//需要这步处理的平台
		if(in_array($platform, $platform_arr)){
			switch ($platform){
				case 'cdiscount':
					$rtn = CdiscountOrderInterface::setOrderIssueStatus($order_source_order_id, $status);
					break;
				default:
					$rtn = ['success'=>true,'message'=>''];
			}
		}else{//不在指定平台的，直接return
			$rtn = ['success'=>true,'message'=>'无需处理'];
		}
		return $rtn;
	}
	
	/**
	 * 获取订单的最后一条留言
	 * @param 	string 	$order_source_order_id
	 * @param 	array 	$items
	 * @param	string	$session_id
	 * @return	array or empty
	 * @author	lzhl	2016/5/9	初始化
	 */
	public static function getOrderLastMessage($order_source_order_id,$items=[],$session_id=''){
		$query = TicketMessage::find()->where("1");
		
		
		if(!empty($order_source_order_id) && !empty($items)){
			$query->andWhere(['related_id'=>$order_source_order_id]);
			$query->orWhere(['related_id'=>$items]);
		}
		elseif(!empty($order_source_order_id))
			$query->andWhere(['related_id'=>$order_source_order_id]);
		elseif(!empty($items))
			$query->andWhere(['related_id'=>$items]);
		else{}	
		
		if(!empty($session_id))
			$query->andWhere(['session_id'=>$session_id]);
		
		$lastMessage = $query->orderBy("platform_time DESC")->one();
		if(empty($lastMessage)){
			return '';
		}else{
			$rtn['last_time'] = $lastMessage->platform_time;
			$rtn['content'] = $lastMessage->content;
			$rtn['send_or_receiv'] = $lastMessage->send_or_receiv;
			return $rtn;
		}
	}
	

	/*
	 * 保存邮件发送历史
	* @access static
	* @param  	string	$send_to	收件人邮箱
	* @param  	string	$send_from	发件人邮箱
	* @param  	string	$actName	调用的actName
	* @param  	string	$subject	邮件标题
	* @param  	string	$body		邮件内容
	* @param  	string	$module_key	模块的标识key，如订单发送通知调用，key即为order_id
	* @param  	string	$from_name	发送人邮件称呼
	+---------------------------------------------------------------------------------------------
	* @return array	[success:boolean//调用 是否成功,message:string//调用结果说明]
	+---------------------------------------------------------------------------------------------
	* @author		lzhl		2016/8/18			初始化
	*/
	public static function saveEdmSentHistory($send_to,$send_from,$actName,$subject,$body,$module_key='',$from_name=''){
		$result = ['success'=>true,'message'=>'','history_id'=>''];
		//Validation start
		//邮箱格式验证
		if(preg_match('/^\w+([-+.]\w+)*@\w+([-.]\w+)*\.\w+([-.]\w+)*$/i',$send_to)){
			$history['send_to'] = $send_to;
		}else{
			return $result = ['success'=>false,'message'=>'收件人邮箱格式不正确'];
		}

		if(preg_match('/^\w+([-+.]\w+)*@\w+([-.]\w+)*\.\w+([-.]\w+)*$/i',$send_from)){
			$history['send_from'] = $send_from;
		}else{
			return $result = ['success'=>false,'message'=>'发件人邮箱格式不正确'];
		}

		if(empty($subject) || empty($body))
			return $result = ['success'=>false,'message'=>'邮件标题和内容不能为空'];

		if(empty($actName))
			return $result = ['success'=>false,'message'=>'actName不能为空'];
		//Validation end

		$history['act_name'] = $actName;
		$history['module_key'] = $module_key;
		$history['subject'] = $subject;
		$history['status'] = 0;
		$history['create_time'] = TimeUtil::getNow();
		$addi_info= [];
		$addi_info['subject']= $subject;
		$addi_info['body'] = $body;
		$addi_info['from_name'] = $from_name;
		$history['addi_info'] = $queue['addi_info'] = json_encode($addi_info);

		$history_modle = new EdmSentHistory();
		$history_modle->setAttributes($history);
		if(!$history_modle->save()){
			return ['success'=>false,'message'=>print_r($history_modle->getErrors())];
		}else
			$result['history_id'] = $history_modle->id;
		return $result;
	}

	/**
	* 添加数据到邮件发送队列(db_queue2)
	* @access static
	* @param  	array:[
	* 					0=>[send_to		收件人邮箱//必填
	*						send_from	发件人邮箱//必填
	*						puid		//必填
	*						act_name	调用的act_name//必填
	* 						subject		邮件标题//必填
	* 						body		邮件内容//必填
	* 						from_name	发送人邮件称呼//必填
	* 						priority	发送优先级,非必要,默认3
	* 						history_id	user表的对应history记录id，没有则可以不填
	* 					],
	* 					1=>[....],
	* 					......
	* 				]
	+---------------------------------------------------------------------------------------------
	* @return array	[success:boolean//调用 是否成功,message:string//调用结果说明]
	+---------------------------------------------------------------------------------------------
	* @author		lzhl		2016/8/18			初始化
	*/
	public static function insertEdmQueue($datas){
		$result = ['success'=>true,'message'=>''];

		$queue_data = [];
		foreach ($datas as $i=>$data){
			$tmp = [];
			if(empty($data['puid'])){
				$result['message'] .= '第'.$i.'条记录  用户id未设置';
				$result['success'] = false;
				continue;
			}else
				$tmp['puid'] = $data['puid'];

			//邮箱格式验证
			if(empty($data['send_to'])){
				$result['message'] .= '第'.$i.'条记录  收件人邮箱未设置';
				$result['success'] = false;
				continue;
			}
			if(!preg_match('/^\w+([-+.]\w+)*@\w+([-.]\w+)*\.\w+([-.]\w+)*$/i',$data['send_to'])){
				$result['message'] .= '第'.$i.'条记录 收件人邮箱格式不正确';
				$result['success'] = false;
				continue;
			}else
				$tmp['send_to'] =  $data['send_to'];

			if(empty($data['send_from'])){
				$result['message'] .= '第'.$i.'条记录 发件人邮箱未设置';
				$result['success'] = false;
				continue;
			}
			if(!preg_match('/^\w+([-+.]\w+)*@\w+([-.]\w+)*\.\w+([-.]\w+)*$/i',$data['send_from'])){
				$result['message'] .= '第'.$i.'条记录 发件人邮箱格式不正确';
				$result['success'] = false;
				continue;
			}else
				$tmp['send_from'] =  $data['send_from'];

			if(empty($data['act_name'])){
				$result['message'] .= '第'.$i.'条记录 actName未设置';
				$result['success'] = false;
				continue;
			}else
				$tmp['act_name'] =  $data['act_name'];
				
			if(empty($data['subject']) || empty($data['body'])){
				$result['message'] .= '第'.$i.'条记录 邮件标题或内容为空';
				$result['success'] = false;
				continue;
			}
				
			$tmp['status'] = 0;
			$tmp['create_time'] = TimeUtil::getNow();
			$tmp['priority'] = empty($data['priority'])?3:$data['priority'];
			$tmp['history_id'] = empty($data['history_id'])?0:$data['history_id'] ;
				
			$addi_info= [];
			$addi_info['subject']= $data['subject'];
			$addi_info['body'] = $data['body'];
			$addi_info['from_name'] = empty($data['from_name'])?'':$data['from_name'];
			$tmp['addi_info'] = json_encode($addi_info);
				
			$queue_data[] = $tmp;
		}
	
		if(!$result['success']){
			return $result;
		}
	
		try{
			SQLHelper::groupInsertToDb("edm_email_send_queue", $queue_data, 'db_queue2');
		}catch (\Exception $e) {
			return ['success'=>false,'message'=>$e->getMessage()];
		}
		return $result;
	}
	
	public static function setDashBoardMessageData($msg_array,$return = false){
// 	    $name = array();
	    $puid = \Yii::$app->user->identity->getParentUid();
        if(!empty($msg_array)){
            foreach ($msg_array as $key=>$val){
                if($key != 'all_platform_unread'){
                    if($key == 'aliexpress'){
                        $aliexpress_all_unread = 0;
                        foreach ($val as $k=>$v){
                            if($k == 'fail_message'){
                                DashBoardStatisticHelper::CounterSet($puid,'customerService_'.$key.'_'.$k, $v);//customerService_cdiscount_all_unread_msg
//                                 $name[] = 'customerService_'.$key.'_'.$k.' = '.$v;
                            }else if($k == 'order'){
                                DashBoardStatisticHelper::CounterSet($puid,'customerService_'.$key.'_'.$k.'_order_unread_msg', $msg_array[$key][$k]['order_unread_msg']);
//                                 $name[] = 'customerService_'.$key.'_'.$k.'_order_unread_msg'.' = '.$msg_array[$key][$k]['order_unread_msg'];
                                $aliexpress_all_unread += $msg_array[$key][$k]['all_unread_msg'];
                            }else{
                                foreach ($v as $v_key=>$v_val){//速卖通分订单留言郁站内信
                                    if($v_key == 'all_unread_msg'){
                                        $aliexpress_all_unread += $v_val;
                                    }else{
                                        DashBoardStatisticHelper::CounterSet($puid,'customerService_'.$key.'_'.$k.'_'.$v_key, $v_val);
//                                         $name[] = 'customerService_'.$key.'_'.$k.'_'.$v_key.' = '.$v_val;
                                    }
                                } 
                            }
                        }
                        DashBoardStatisticHelper::CounterSet($puid,'customerService_'.$key.'_all_unread_msg', $aliexpress_all_unread);//设置速卖通所有未读数量
//                         $name[] = 'customerService_'.$key.'_all_unread_msg'.' = '.$aliexpress_all_unread;
                    }else{
                        foreach ($val as $k=>$v){
                            DashBoardStatisticHelper::CounterSet($puid,'customerService_'.$key.'_'.$k,$v);//customerService_cdiscount_all_unread_msg
//                             $name[] = 'customerService_'.$key.'_'.$k.' = '.$v;
                        }
                    }
                }
            }
            DashBoardStatisticHelper::CounterSet($puid,'customerService_note',1);
//             $name[] = 'customerService_note = 0';//标识有没有第一次保存数据
        }
        if($return){
            return true;
        }
//         print_r($name);	    
	}
	
	/*
	 * 获取dashbord message 未读以及错误信息数量的数据
	 * @access static
	 * @param  	$puid
	 *          $platform 客服模块开通的平台，默认是全部平台，可以单独选择一个平台
	 *          $reset  是否马上刷新缓存
	 +---------------------------------------------------------------------------------------------
	 * @return array	失败：return array('success'=>false,'message'=>'错误信息');
	 *                  成功：return array=[
	 *                      'cdiscount'=>[
	 *                         'unreadMessage'=>数量,
	 *                         'failMessage'=>数量,
	 *                      ],
	 *                      'ebay'=>[
	 *                         'unreadMessage'=>数量,
	 *                         'failMessage'=>数量,
	 *                      ],
	 *                      ......   
	 *                  ]
	 +---------------------------------------------------------------------------------------------
	 * @author		lwj		2016/9/8			初始化
	 */
	public static function getDashBoardMessageData($puid,$platform = 'all',$reset = false){
	    if(empty($puid)){
	        return array('success'=>false,'message'=>'puid不能为空');
	    }
	    $returnArray = array();
	    $messagePlatform = ['ebay','aliexpress','dhgate','wish','cdiscount','priceminister'];
	    if(DashBoardStatisticHelper::CounterGet($puid,'customerService_note') != 1 || $reset){
	        if(self::setDashBoardMessageDataNow()){
	            return self::getDashBoardMessageData($puid,$platform,false);//马上回调一次，set值
	        }
	    }else if($platform == 'all'){
	        $platformArray = array();
	        $platformUseArr = PlatformAccountApi::getAllPlatformBindingSituation();
	        foreach ($platformUseArr as $platformUseKey=>$platformUseValue){
	            if ($platformUseValue == true&&in_array($platformUseKey, $messagePlatform)){//有客服模块的平台才加入
	                $platformArray[] = $platformUseKey;
	            }
	        }
	        
	        if(!empty($platformArray)){
	            foreach ($platformArray as $key){
	                $returnArray[$key]['unreadMessage'] = DashBoardStatisticHelper::CounterGet($puid,'customerService_'.$key.'_all_unread_msg');
	                $returnArray[$key]['failMessage'] = DashBoardStatisticHelper::CounterGet($puid,'customerService_'.$key.'_fail_message');
	            }
	        }
	        return $returnArray;
	    }else{
	        if(!in_array($platform, $messagePlatform)){
	            return array('success'=>false,'message'=>'该平台没有开通客服模块');
	        }else{
	            $unreadNum = DashBoardStatisticHelper::CounterGet($puid,'customerService_'.$platform.'_all_unread_msg');
	            if($unreadNum === 0||$unreadNum > 0){
	                $returnArray[$platform]['unreadMessage'] = DashBoardStatisticHelper::CounterGet($puid,'customerService_'.$platform.'_all_unread_msg');;
	                $returnArray[$platform]['failMessage'] = DashBoardStatisticHelper::CounterGet($puid,'customerService_'.$platform.'_fail_message');
	            }else{
	                self::getDashBoardMessageData($puid,$platform,true);//马上回调一次，set值
	            }
	        }
	        
	        return $returnArray;
	    }
	}
	
	public static function setDashBoardMessageDataNow(){
        //判断平台是否开通
        $platformUseArr = PlatformAccountApi::getAllPlatformBindingSituation();
        
        $menuLabelList = array();
        	
        $menuLabelList = [
            'ebay'=>[
                'all_unread_msg'=>0,
				'order_unread_msg'=>0,
				'product_unread_msg'=>0,
				'system_unread_msg'=>0,
                'fail_message'=>0,
            ],
            'aliexpress'=>[
                'order'=>[//订单留言
                    'all_unread_msg'=>0,
                    'order_unread_msg'=>0,
                    'product_unread_msg'=>0,
                    'other_unread_msg'=>0,
                ],
                'station'=>[//站内信
                    'all_unread_msg'=>0,
                    'order_unread_msg'=>0,
                    'product_unread_msg'=>0,
                    'other_unread_msg'=>0,
                ],
                'fail_message'=>0,
            ],
            'dhgate'=>[
                'all_unread_msg'=>0,
                'order_unread_msg'=>0,
    			'product_unread_msg'=>0,
    			'system_unread_msg'=>0,
                'fail_message'=>0,
            ],
			'wish'=>[
			    'all_unread_msg'=>0,
			    'fail_message'=>0,
    				],
			'cdiscount'=>[
			    'all_unread_msg'=>0,
			    'order_unread_msg'=>0,
			    'order_question_unread_msg'=>0,
			    'fail_message'=>0,
			],
			'priceminister'=>[
			    'all_unread_msg'=>0,
		        'order_unread_msg'=>0,
	            'fail_message'=>0,
		    ],
           'all_platform_unread'=>0,
		];
    				
		foreach ($platformUseArr as $platformUseKey=>$platformUseValue){
			if ($platformUseValue == false){
    			unset($menuLabelList[$platformUseKey]);
            }
        }
    
        foreach($menuLabelList as $platform_type=>$value){
            $allPlatformArr = MessageApiHelper::getPlatformAccountList();
// 				$queryTmp->andWhere(['in','t.seller_id',$allPlatformArr['aliexpress']+$allPlatformArr['ebay']+$allPlatformArr['wish']+$allPlatformArr['dhgate']]);

// 				$msg_relatedArr = Yii::$app->subdb->
// 					createCommand("select ifnull(related_type,'') as `related_type`,count(1) as `related_count`,message_type
// 					from cs_ticket_session
// 					where has_read=0 and message_type in (1,2,3) and platform_source=:platform_source
// 					group by ifnull(related_type,''),message_type
// 					",[':platform_source'=>$platform_type])->queryAll();

            $queryTmp = new Query;
            $queryTmp->select("ifnull(related_type,'') as `new_related_type`,count(1) as `related_count`,message_type")
            ->from("cs_ticket_session")
            ->where(['and'," has_read=0 and message_type in (1,2,3) and platform_source=:platform_source "],array(':platform_source'=>$platform_type))
            ->groupBy("new_related_type,message_type");
            $queryTmp->andWhere(['in','seller_id',$allPlatformArr['aliexpress']+$allPlatformArr['ebay']+$allPlatformArr['wish']+$allPlatformArr['dhgate']+$allPlatformArr['cdiscount']+$allPlatformArr['priceminister']]);
        
            $conn=\Yii::$app->subdb;
            $msg_relatedArr = $queryTmp->createCommand($conn)->queryAll();
        
            // 				print_r($msg_relatedArr);
            // 				exit;
        
            //获取失败message数量
            $failMessage_array = array();
            $platform_failMessage_array = array();
            $failMessage = TicketMessage::find()->select('ticket_id')->where(['status'=>'F'])->asArray()->all();
            if(!empty($failMessage)){
                //统计fail message数量
                foreach ($failMessage as $fail_detail){
                    if(isset($failMessage_array[$fail_detail['ticket_id']])){
                        $failMessage_array[$fail_detail['ticket_id']] += 1;
                    }else{
                        $failMessage_array[$fail_detail['ticket_id']] = 1;
                    }
                }
                        //获取平台
                $failSession = TicketSession::find()->select(['ticket_id','platform_source'])->where(['ticket_id'=>$failMessage])->andWhere(['in','seller_id',$allPlatformArr['aliexpress']+$allPlatformArr['ebay']+$allPlatformArr['wish']+$allPlatformArr['dhgate']+$allPlatformArr['cdiscount']+$allPlatformArr['priceminister']])->asArray()->all();
    
                if(!empty($failSession)){
                    foreach ($failSession as $v){
                        if(isset($failMessage_array[$v['ticket_id']])){
                            $platform_failMessage_array[$v['platform_source']] += $failMessage_array[$v['ticket_id']];
                        }else{
                            $platform_failMessage_array[$v['platform_source']] = $failMessage_array[$v['ticket_id']];
                        }
                    }
                }
    // 				    print_r($platform_failMessage_array);
                //设置fail_message
                if(!empty($platform_failMessage_array)){
                    foreach ($platform_failMessage_array as $fail_key=>$fail_val){
                        if(isset($menuLabelList[$fail_key])){
                            $menuLabelList[$fail_key]['fail_message'] = $fail_val;
                        }
                    }
                }
            }
        
            foreach ($msg_relatedArr as $msg_related){
                if($platform_type == "aliexpress"){
                    if($msg_related['message_type'] == 1){//速卖通订单留言
                        switch ($msg_related['new_related_type'])
                        {
                            case 'O':
                                $menuLabelList[$platform_type]['order']['order_unread_msg'] += $msg_related['related_count'];
                                break;
                            case 'P':
                                $menuLabelList[$platform_type]['order']['product_unread_msg'] += $msg_related['related_count'];
                                break;
                            case 'M':
                                $menuLabelList[$platform_type]['order']['other_unread_msg'] += $msg_related['related_count'];
                                break;
                            default:
                                $menuLabelList[$platform_type]['order']['other_unread_msg'] += $msg_related['related_count'];
                                break;
                    }
                    $menuLabelList[$platform_type]['order']['all_unread_msg'] += $msg_related['related_count'];
                        $menuLabelList['all_platform_unread'] += $msg_related['related_count'];
                }else{////速卖通站内信
                        switch ($msg_related['new_related_type'])
                        {
                        case 'O':
                            $menuLabelList[$platform_type]['station']['order_unread_msg'] += $msg_related['related_count'];
                            break;
                            case 'P':
                            $menuLabelList[$platform_type]['station']['product_unread_msg'] += $msg_related['related_count'];
                            break;
                            case 'M':
                            $menuLabelList[$platform_type]['station']['other_unread_msg'] += $msg_related['related_count'];
                                break;
                                    default://其他类型
                            $menuLabelList[$platform_type]['station']['other_unread_msg'] += $msg_related['related_count'];
                                break;
                        }
                        $menuLabelList[$platform_type]['station']['all_unread_msg'] += $msg_related['related_count'];
                            $menuLabelList['all_platform_unread'] += $msg_related['related_count'];
    			        }
                }else{
                    if (in_array($msg_related['message_type'], array(1,2))){
                    switch ($msg_related['new_related_type'])
                    {
                    case 'O':
                        $menuLabelList[$platform_type]['order_unread_msg'] += $msg_related['related_count'];
                        break;
                            case 'P':
                                $menuLabelList[$platform_type]['product_unread_msg'] += $msg_related['related_count'];
                                break;
                            case 'S':
                                $menuLabelList[$platform_type]['system_unread_msg'] += $msg_related['related_count'];
                                break;
                            case 'M':
                                $menuLabelList[$platform_type]['other_unread_msg'] += $msg_related['related_count'];
                                break;
                            case 'Q'://cdiscount order_question
                                $menuLabelList[$platform_type]['order_question_unread_msg'] += $msg_related['related_count'];
                                break;
                    }
                    }else if(($msg_related['message_type'] == 3)){
                        $menuLabelList[$platform_type]['system_unread_msg'] += $msg_related['related_count'];
                    }
                     
                    $menuLabelList[$platform_type]['all_unread_msg'] += $msg_related['related_count'];
                    $menuLabelList['all_platform_unread'] += $msg_related['related_count'];
                }
                        	
            }
        }
    
        $msg_statistics = $menuLabelList;
        if(self::setDashBoardMessageData($msg_statistics,true)){//setDashBord数据
            return true;
        }
	}
}

