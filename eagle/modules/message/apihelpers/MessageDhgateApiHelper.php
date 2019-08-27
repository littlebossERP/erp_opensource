<?php
namespace eagle\modules\message\apihelpers;

use \yii;
use \Exception;
use common\api\dhgateinterface\Dhgateinterface_Api;
use eagle\modules\message\models\TicketSession;
use eagle\modules\message\models\TicketMessage;
use eagle\modules\message\models\Customer;
use \eagle\modules\message\helpers\MessageBGJHelper;
use eagle\models\SaasDhgateUser;

class MessageDhgateApiHelper{
	
	/**
	 * 调用敦煌接口获取所有类型的站内信详细信息并保存在user级别的数据库中
	 *
	 * log			name	date					note
	 * @author		hqw 	2015/07/14				初始化
	 *
	 * @param unknown $uid	//小老板平台uid
	 * @param unknown $selleruserid	//敦煌saas_dhgate_user 的sellerloginid
	 * @param unknown $start_time	//拉取开始时间
	 * @param unknown $end_time	//拉取结束时间
	 * @param unknown $platform_uid //敦煌saas_dhgate_user 的主键id
	 *
	 * @return array('success'=>true,'error'=>'');
	 */
	public static function getMsgDhgateAll($uid, $selleruserid, $start_time, $end_time, $platform_uid){
		$dhgateinterface_Api = new Dhgateinterface_Api();
		
		$appParams = array();
		$appParams["startDate"] = date ( "Y-m-d H:i:s",$start_time );
		$appParams["endDate"] = date ( "Y-m-d H:i:s",$end_time );
		
		//获取时间段内不同类型站内信数量,用于判断是否需要拉取meeeage信息
		$response = $dhgateinterface_Api->getMsgTypeListCount($platform_uid, $appParams);
		
		//如果获取失败，则直接退出
		if ($response['success'] === false){
			$success=false;
			$errorMessage=$response['error_message'];
			return array('success'=>false,'error'=>$errorMessage);
		}
		
		//记录需要调用接口getMsgList的类型
		$msgTypeStr = '';
		
		//返回的是不同的站内信类型数组，而且只需要msgCount大于0的类型
		$msgTypeListCountArr = $response["response"]["msgCountList"];
		
		foreach ($msgTypeListCountArr as $msgTypeListCount){
			if ($msgTypeListCount['msgCount'] > 0)
				$msgTypeStr .= $msgTypeListCount['msgType'].',';
		}
		
		//如果类型为空则证明该时间段没有新的站内信
		if ($msgTypeStr == "")
			return array('success'=>true,'error'=>'');
			
		$msgTypeStr = substr($msgTypeStr,0,strlen($msgTypeStr)-1);
		
		//将用过的参数清空用于下次使用
		unset($appParams);
		unset($response);
		
		$appParams = array();
		
		//beforeDay查询的时间范围 int  当前日期的前几天,单位为天数,取值必须在1到366之间
		$appParams["beforeDay"] = ceil(($end_time-$start_time)/(3600*24));
		
		//查询范围不能大于366日
		if ($appParams["beforeDay"] > 366){
			return array('success'=>false,'error'=>'日期查询范围超过366日');
		}
		
		//预防参数有误强制小于等于0的强制设为0
		if ($appParams["beforeDay"] <= 0){
			$appParams["beforeDay"] = 1;
		}
		
		//站内信展现类型
		$appParams['msgType'] = $msgTypeStr;
		
		//设置分页的初始值
		$pageNo = '1';
		$pageSize = '50';
		
		//$lastMsg,$isEnd记录最后每次循环的最后一条记录数组，用来判断是否到最后一页
		//因为敦煌获取站内信的api没有总记录数，所以需要写代码判断是否到最后一页
		$lastMsg = array();
		$isEnd = true;
		
		$touched_session_ids = array();
		$touched_customer_ids = array();
		//开始循环
		do{
			$appParams["pageNo"] = $pageNo;
			$appParams["pageSize"] = $pageSize;
		
			//调用获取站内信主题列表
			$response = $dhgateinterface_Api->getMsgList($platform_uid, $appParams);
			if ($response['success'] === false){
				$errorMessage=$response['error_message'];
				return array('success'=>false,'error'=>$errorMessage);
			}
			
			$msgListArr = $response["response"]["msgList"];
			
			//敦煌的分页没有返回总记录数，所以只可以每次循环的最后一条记录来跟现时取到的数据最后一条记录做对比看是否出现过
			if (($pageNo > 1) && (count($msgListArr) == $pageSize)){
				$lastNowMsg=end($msgListArr);
			
				if ($lastMsg['msgId'] == $lastNowMsg['msgId']){
					$isEnd = false;
				}
			}
				
			if ($isEnd==true){
				foreach ($msgListArr as $msgList){
					
					//假如最后回复时间小于拉取$start_time代表Details已经拉取过，暂时没有新的Details
					if (strtotime($msgList['lastReplyTime']) < $start_time){
						continue;
					}
				
					//获取一条站内信的详细回复内容信息 参数
					$appDetailsParams = array();
					$appDetailsParams['msgId'] = $msgList['msgId'];
				
					$resMsgDetails = $dhgateinterface_Api->getMsgDetails($platform_uid, $appDetailsParams);
				
					if ($resMsgDetails['success'] === false){
						$errorMessage=$resMsgDetails['error_message'];
						return array('success'=>false,'error'=>$errorMessage);
					}
				
					//敦煌返回的是所有的Detail数组
					$msgDetailArr = $resMsgDetails['response']["msgDetailList"];
				
					foreach($msgDetailArr as $msgOne){
						$resultInsert = self::dhgateMsgToEagleMsg($msgList ,$msgOne, $uid, $selleruserid);
				
						if ($resultInsert['success']===false){
							return $resultInsert;
						}
						
						if (!empty($resultInsert['touched_session_id']))
							$touched_session_ids[''.$resultInsert['touched_session_id']] = $resultInsert['touched_session_id'];
					
						if (!empty($resultInsert['touched_customer_id']))
							$touched_customer_ids[''.$resultInsert['touched_customer_id']] = $resultInsert['touched_customer_id'];
					}
				}
			}
			
			//假如本次的获取记录数小于$pageSize，就相当于到最后一页
			if ((count($msgListArr) < $pageSize)){
				$isEnd = false;
			}
			
			$lastMsg=end($msgListArr);
			unset($msgListArr);
			$pageNo++;
		} while($isEnd);
		
		return array('success'=>true,'error'=>'','touched_session_ids'=>$touched_session_ids,'touched_customer_ids'=>$touched_customer_ids);
	}
	
	/**
	 * 
	 * $msgList //获取平台原始列表数据
	 * $msgOne //获取平台的原始detail数据
	 * $uid //小老板平台uid
	 * $selleruserid //敦煌saas_dhgate_user 的sellerloginid
	 * @return Ambigous <\eagle\modules\message\apihelpers\multitype:boolean, multitype:boolean string >
	 */
	public static function dhgateMsgToEagleMsg(&$msgList, &$msgOne, $uid, $selleruserid){
		//平台session_id
		$eagleMsgOne['session_id'] = $msgList['msgId'];
		
		//平台返回的判断唯一值
		$eagleMsgOne['msgKey'] = $msgOne['detailId'];
		
		//收信人systemuserbaseid 发送人的systemuserbaseid 敦煌用于当前会话的通信id
		$senderId = '';
		
		$eagleMsgOne['seller_nickname'] = $selleruserid;
	
		//敦煌的呢称相当于 saas_dhgate_user 的 sellerloginid
		if($selleruserid == $msgList['senderNickName']){//保存buyer的接收ID
		    $eagleMsgOne['recieverId'] = $msgList['recieverId'];
		    $senderId = $msgList['senderId'];
		    $eagleMsgOne['buyer_nickname'] = $msgList['receiverNickName'];
		    $eagleMsgOne['buyer_id'] = $msgList['recieverOrgId'];
		}else{
		    $eagleMsgOne['recieverId'] = $msgList['senderId'];
		    $senderId = $msgList['recieverId'];
		    $eagleMsgOne['buyer_nickname'] = $msgList['senderNickName'];
		    $eagleMsgOne['buyer_id'] = $msgList['senderOrgId'];
		}
		
		if ($selleruserid == $msgOne['senderNickName']){
// 			$eagleMsgOne['buyer_id'] = $msgOne['recieverOrgId'];
// 			$eagleMsgOne['buyer_id'] = $msgOne['receiverId'];
// 			$eagleMsgOne['buyer_nickname'] = $msgOne['receiverNickName'];
// 			$eagleMsgOne['recieverId'] = $msgOne['recieverId'];
			$eagleMsgOne['isSend'] = 1;
			
// 			$eagleMsgOne['sellerOrgId'] = $msgList['senderOrgId'];
// 			$senderId = $msgOne['senderId'];
		}else{
// 			$eagleMsgOne['buyer_id'] = $msgOne['senderOrgId'];
// 			$eagleMsgOne['buyer_id'] = $msgOne['senderId'];
// 			$eagleMsgOne['buyer_nickname'] = $msgOne['senderNickName'];
// 			$eagleMsgOne['recieverId'] = $msgOne['senderId'];
			$eagleMsgOne['isSend'] = 0;
			
// 			$eagleMsgOne['sellerOrgId'] = $msgList['recieverOrgId'];
// 			$senderId = $msgOne['recieverId'];
		}
		
		$eagleMsgOne['msgCreateTime'] = $msgOne['createTime'];
		
		$eagleMsgOne['content'] = $msgOne['content'];
		
		switch ($msgList['msgType'])
		{
			case '001':
			case '003':
				$eagleMsgOne['messageType'] = '2';
				break;
			case '002':
				$eagleMsgOne['messageType'] = '1';
				$eagleMsgOne['list_related_id'] = $msgList['param'];
				$eagleMsgOne['list_related_type'] = 'O';
				break;
			case '004':
			case '005':
			case '006':
			case '007':
			case '008':
			case '009':
				$eagleMsgOne['messageType'] = '3';  //平台系统消息
				$eagleMsgOne['list_related_type'] = 'S';
				break;
			default:
				$eagleMsgOne['messageType'] = '0';
		}
		
		$eagleMsgOne['original_msg_type'] = $msgList['msgType'];
		
		$eagleMsgOne['list_contact'] = json_encode($msgList);
		$eagleMsgOne['msgTitle'] = $msgList['msgTitle'];
		
		$eagleMsgOne['msgDetailsTitle'] = $msgList['msgTitle'];
		
		switch ($msgList['msgType'])
		{
			case '001':
				$eagleMsgOne['related_id'] = $msgList['param'];
				$eagleMsgOne['related_type'] = 'P';
				break;
			case '002':
				$eagleMsgOne['related_id'] = $msgList['param'];
				$eagleMsgOne['related_type'] = 'O';
				break;
		}
		
		$eagleMsgOne['msg_contact'] = $msgOne;
		$eagleMsgOne['app_time'] = $msgOne['createTime'];
		
		$eagleMsgOne['list_addi_info'] = array(
				'recieverId' => $eagleMsgOne['recieverId'],
				'senderId' => $senderId
		);
		
		$result = MessageApiHelper::_insertMsg($uid, $selleruserid, 'dhgate', 'message_id', $eagleMsgOne);
		//yzq，记录这个session的session id，作为dirty data的cache hqw Edit
		$result['touched_session_id'] = $eagleMsgOne['session_id'];
		$result['touched_customer_id'] = $eagleMsgOne['buyer_id'];
		return $result;
	}
	
	/**
	 * 敦煌用户保存发送站内信或订单留言接口
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
	public static function dhgateSaveMsgQueue($params = array(), $changeUserDb=true){
		 
		//ticket_id 来查找对应的会话信息
		$TSession_obj = TicketSession::findOne(['ticket_id'=>$params['ticket_id']]);
		
		if ($TSession_obj == null){
			\Yii::error('function:'.__FUNCTION__.'没有该会话信息，请检查');
			return array('success'=>false,'error'=>'function:'.__FUNCTION__.'没有该会话信息，请检查');
		}
		
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
		
		$tmpAddi_info = array();
		$addi_info = json_decode($TSession_obj->addi_info,true);
		$tmpAddi_info['recieverId'] = $addi_info['recieverId'];
		$tmpAddi_info['msgId'] = $TSession_obj->session_id;
		//敦煌发送站内信需要的是账号名，而不是账号id
		$tmpAddi_info['seller_nickname'] = $TSession_obj->seller_nickname;
		
		$msgBufferArr = array(
				'seller_id'=>$TSession_obj->seller_id,'message_type'=>$params['msgType'],
				'order_id'=>$params['msgType'] == "1" ? $TSession_obj->related_id : '',
				'buyer_id'=>$params['msgType'] == "2" ? $TSession_obj->buyer_id : '',
				'addi_info' => json_encode($tmpAddi_info)
		);
		
		$result = MessageApiHelper::platformSaveMsgQueue($params['puid'], $params['platform_source'],
				$msgOne, $msgBufferArr, [],$changeUserDb);
		
		return $result;
	}
	
	public static function addMessage($puid, $seller_id, $addi_info, $content){
		$dhgateUser = SaasDhgateUser::findOne(['sellerloginid'=>$addi_info['seller_nickname'],'uid'=>$puid]);
		
		if ($dhgateUser == null){
			return array('success'=>false,'error_message'=>'没有该用户记录');
		}
		
		$dhgateinterface_Api = new Dhgateinterface_Api();
		$rtn1 = $dhgateinterface_Api->replyMessage($dhgateUser->dhgate_uid, $addi_info, $content);
		
		return $rtn1;
	}
	
}