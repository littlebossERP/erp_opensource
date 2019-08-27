<?php
namespace eagle\modules\message\apihelpers;

use \yii;
use \Exception;
use eagle\modules\message\models\TicketSession;
use eagle\modules\message\models\TicketMessage;
use eagle\modules\message\models\Customer;
use eagle\models\SaasWishUser;
use eagle\modules\listing\helpers\WishProxyConnectHelper;

class MessageWishApiHelper{
	
	/**
	 +----------------------------------------------------------
	 * 调用Wish获取所有类型的站内信详细信息并保存在user级别的数据库中
	 +----------------------------------------------------------
	 * @access static
	 +----------------------------------------------------------
	 * @param     $uid //小老板平台uid
	 * 			  $selleruserid //wish saas_wish_user 的store_name
	 * 			  $platform_uid //saas_wish_user 的主键Id
	 *
	 +----------------------------------------------------------
	 					是否执行成功         错误信息
	 * @return array('success'=>true,'error'=>'');
	 *
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		hqw		2015-07-23   			初始化
	 +----------------------------------------------------------
	 **/
	public static function getMsgWishAwaiting($uid, $selleruserid, $platform_uid){
		
		$wishUser = SaasWishUser::findOne(['store_name'=>$selleruserid,'site_id'=>$platform_uid]);
		
		if ($wishUser == null){
			return array('success'=>false,'error'=>'没有该用户记录');
		}
		
		$token_key = $wishUser->token;
		
		$start = 0; //An offset into the list of returned tickets
		$pageSize = 200; //每页显示记录数
		$isEnd = true; //结束标记
		$touched_session_ids = array();
		$touched_customer_ids = array();
		do{
// 			$get_params = array('start' => $start,'limit' => $pageSize,'key' => $token_key);
			
			$reqParams['token'] = $token_key;
			$reqParams['parms']=json_encode(array('start'=>$start, 'limit'=>$pageSize));
			$msgListArr=WishProxyConnectHelper::call_WISH_api("getAllTicketsAwaiting",$reqParams );
			
// 			$msgListArr = self::call_WISH_api('getAwaitingTickets',$get_params);

			if (empty($msgListArr))
				return array('success'=>false,'error'=>'WishProxyConnectHelper::call_WISH_api 执行失败');
			
			if ($msgListArr['success'] == false)
				return array('success'=>false,'error'=>$msgListArr['message']);
			
			if (!isset($msgListArr['proxyResponse']['success']))
				return array('success'=>false,'error'=>'WishProxyConnectHelper::call_WISH_api 返回没有 proxyResponse:success');
			
			if ($msgListArr['proxyResponse']['success'] == false)
				return array('success'=>$msgListArr['proxyResponse']['success'],'error'=>$msgListArr['proxyResponse']['message']);
			
			$wishListArr = $msgListArr['proxyResponse']['wishReturn']['data'];
			
			if (count($wishListArr) > 0 && ($wishUser->merchant_id=='')){
				try{
					$wishUser->merchant_id = $wishListArr[0]['Ticket']['merchant_id'];
					$wishUser->save(false);
				}catch(Exception $ex){
					\Yii::error(__FUNCTION__.print_r($ex,true));
					return array('success'=>false,'error'=>__FUNCTION__.print_r($ex,true));
				}
			}
			
			foreach ($wishListArr as $wishMsgInfo){
				$resultInsert = self::wishMsgToEagleMsg($wishMsgInfo, $uid, $selleruserid);
				
				if ($resultInsert['success']===false){
					return $resultInsert;
				}else{
					//因为wish只能获取未处理的msg信息，所以只能在成功拉取后才可以将之前发送成功的删除
					try{
						$resultDelRow = \Yii::$app->subdb->createCommand("delete a from cs_ticket_message a
							left join cs_ticket_session b on b.ticket_id=a.ticket_id
							where b.ticket_id=a.ticket_id and b.platform_source=:platform_source
							and a.session_id=:session_id and a.status='C' and IFNULL(a.message_id,'')='' ",
									[':session_id'=>$wishMsgInfo['Ticket']['id'],':platform_source'=>'wish'])->execute();
					}catch(Exception $ex){
						\Yii::error(__FUNCTION__.print_r($ex,true));
						return array('success'=>false,'error'=>__FUNCTION__.print_r($ex,true));
					}
				}
				
				if (!empty($resultInsert['touched_session_id']))
					$touched_session_ids[''.$resultInsert['touched_session_id']] = $resultInsert['touched_session_id'];
			
				if (!empty($resultInsert['touched_customer_id']))
					$touched_customer_ids[''.$resultInsert['touched_customer_id']] = $resultInsert['touched_customer_id'];
			}
			
			if (count($wishListArr) == 0){
				$isEnd = false;
			}
			
			$start += $pageSize;
			unset($msgListArr);
			
		} while($isEnd);
		
		return array('success'=>true,'error'=>'','touched_session_ids'=>$touched_session_ids,'touched_customer_ids'=>$touched_customer_ids);
	}
	
	/**
	 +----------------------------------------------------------
	 * wish用于保存拉取下来的msg信息
	 +----------------------------------------------------------
	 * @access static
	 +----------------------------------------------------------
	 * @param     $wishMsgInfo //平台返回的列表
	 * 			  $uid //小老板平台uid
	 * 			  $selleruserid //wish saas_wish_user 的store_name
	 * 
	 +----------------------------------------------------------
	 								是否执行成功         错误信息         
	 * @return			    Array 'success'=>true,'error'=>''
	 *
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		hqw		2015-07-23   			初始化
	 +----------------------------------------------------------
	 **/
	public static function wishMsgToEagleMsg(&$wishMsgInfo, $uid, $selleruserid){
		//平台session_id
		$eagleMsgOne['session_id'] = $wishMsgInfo['Ticket']['id'];
		
		$wishMsgArr = $wishMsgInfo['Ticket']['replies'];
		
		foreach ($wishMsgArr as $wishMsg){
			//平台返回的判断唯一值
			$eagleMsgOne['msgKey'] = $wishMsg['Reply']['date'];
			
			$eagleMsgOne['seller_nickname'] = $selleruserid;
			
			$eagleMsgOne['buyer_id'] = $wishMsgInfo['Ticket']['UserInfo']['id'];
			$eagleMsgOne['buyer_nickname'] = $wishMsgInfo['Ticket']['UserInfo']['name'];
			
			if($wishMsg['Reply']['sender'] == 'user')
				$eagleMsgOne['isSend'] = 0;
			else 
				$eagleMsgOne['isSend'] = 1;
			
			//wish用的是UTC时间格式
			$eagleMsgOne['msgCreateTime'] = date('Y-m-d H:i:s',strtotime($wishMsg['Reply']['date']));
			
			$eagleMsgOne['content'] = $wishMsg['Reply']['message'];
			
			if (isset($wishMsgInfo['Ticket']['items']['0']['Order'])){
				if(count($wishMsgInfo['Ticket']['items']['0']['Order']) > 0){
					$eagleMsgOne['messageType'] = '1';
					$eagleMsgOne['list_related_id'] = $wishMsgInfo['Ticket']['items']['0']['Order']['order_id'];
					$eagleMsgOne['list_related_type'] = 'O';
					
					$eagleMsgOne['related_id'] = $eagleMsgOne['list_related_id'];
					$eagleMsgOne['related_type'] = 'O';
				}
			}
			
			if(($wishMsg['Reply']['sender'] != 'merchant') && $wishMsg['Reply']['sender'] != 'user')
				$eagleMsgOne['related_type'] = 'S';
			
			//wish平台所有的都是订单留言
			if (!isset($eagleMsgOne['messageType'])){
				$eagleMsgOne['messageType'] = '1';
			}
			
			$eagleMsgOne['list_contact'] = json_encode($wishMsgInfo);
			$eagleMsgOne['msgTitle'] = $wishMsgInfo['Ticket']['label'];
			
			$eagleMsgOne['msg_contact'] = $wishMsg;
			
			$eagleMsgOne['app_time'] = date('Y-m-d H:i:s',strtotime($wishMsg['Reply']['date'])+28800);
			
		}
	
		$result = MessageApiHelper::_insertMsg($uid, $selleruserid, 'wish', 'message_id', $eagleMsgOne);
		//yzq，记录这个session的session id，作为dirty data的cache hqw Edit
		$result['touched_session_id'] = $eagleMsgOne['session_id'];
		
		$result['touched_customer_id'] = $eagleMsgOne['buyer_id'];
		return $result;
	}
	
	/**
	 +----------------------------------------------------------
	 * wish保存订单留言接口，用于保存在队列表中，到时会有后台Job处理发送
	 +----------------------------------------------------------
	 * @access static
	 +----------------------------------------------------------
	 * @param     $params = array(
					  'platform_source' => '' //平台类型
					  'msgType' => '' //站内信类型 1--订单，2--站内信
					  'puid' => '' //小老板平台uid
					  'contents' => '' //内容
					  'ticket_id' => '' //小老板会话id 此值必须
					  );
	 *
	 +----------------------------------------------------------
	 是否执行成功         错误信息
	 * @return			    Array 'success'=>true,'error'=>''
	 *
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		hqw		2015-07-23   			初始化
	 +----------------------------------------------------------
	 **/
	public static function wishSaveMsgQueue($params = array(),$changeUserDb=true){
		 
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
		$tmpAddi_info['session_id'] = $TSession_obj->session_id;
	
		$msgBufferArr = array(
				'seller_id'=>$TSession_obj->seller_id,'message_type'=>$params['msgType'],
				'order_id'=>$params['msgType'] == "1" ? $TSession_obj->related_id : '',
				'buyer_id'=>$params['msgType'] == "2" ? $TSession_obj->buyer_id : '',
				'addi_info' => json_encode($tmpAddi_info)
		);
	
		$result = MessageApiHelper::platformSaveMsgQueue($params['puid'], $params['platform_source'],
				$msgOne, $msgBufferArr, @$sessionList, $changeUserDb);
	
		return $result;
	}
	
	/**
	 +----------------------------------------------------------
	 * 调用proxy接口回复wish站内信
	 +----------------------------------------------------------
	 * @access static
	 +----------------------------------------------------------
	 * @param		$puid //小老板平台uid
	 * 			 	$selleruserid //卖家id
	 * 				$addi_info //wish平台的Ticket_id
	 * 				$content //回复内容
	 *
	 +----------------------------------------------------------
	 是否执行成功         错误信息
	 * @return			    Array 'success'=>true,'error_message'=>''
	 *
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		hqw		2015-07-24   			初始化
	 +----------------------------------------------------------
	 **/
	public static function replyTicket($puid, $selleruserid, $addi_info, $content){
		
		$wishUser = SaasWishUser::findOne(['store_name'=>$selleruserid,'uid'=>$puid]);
		
		if ($wishUser == null){
			return array('success'=>false,'error'=>'没有该用户记录');
		}
		
		$token_key = $wishUser->token;
		
		$reqParams['token'] = $token_key;
		$reqParams['parms']=json_encode(array('id'=>$addi_info['session_id'], 'reply'=>$content));
		$result = WishProxyConnectHelper::call_WISH_api("replyOneTickets",$reqParams );
		
// 		$get_params = array('id' => $addi_info['session_id'],'reply' => $content,'key' => $token_key);
// 		$post_params = array('id' => $addi_info['session_id'],'reply' => $content,'key' => $token_key);
// 		$result = self::call_WISH_api('replyMsg', $get_params, $post_params);

		if($result['success'] == false){
			return array('success'=>$result['success'],'error_message'=>$result['message']);
		}else {
			return array('success'=>$result['proxyResponse']['success'],'error_message'=>$result['proxyResponse']['message']);
		}
	}
	
	
	
// 	public static $wish_url_mapping = [
// 	'getAwaitingTickets'=>'https://sandbox.merchant.wish.com/api/v1/ticket/get-action-required',
// 	'getOneTicket'=>'https://sandbox.merchant.wish.com/api/v1/ticket',
// 	'replyMsg'=>'https://sandbox.merchant.wish.com/api/v1/ticket/reply',
// 	'replyOpenTicket'=>'https://sandbox.merchant.wish.com/api/v1/ticket/re-open',
// 	'appealToWish'=>'https://sandbox.merchant.wish.com/api/v1/ticket/appeal-to-wish-support',
// 	'closeTicket'=>'https://sandbox.merchant.wish.com/api/v1/ticket/close',
// 	];
	
// 	public static function call_WISH_api($action , $get_params = array()  , $post_params=array(),$TIME_OUT=180 ){
// 		try {
// 			if (!empty(self::$wish_url_mapping[$action])){
// 				$url = self::$wish_url_mapping[$action];
// 			}else{
// 				return ['success'=>false , 'message'=>$action.'不是有效的action!'];
// 			}
	
// 			if (!empty($get_params))
// 				$url .= "?".http_build_query($get_params);
				
// 			$handle = curl_init($url);
// 			//echo $url;//test kh
	
// 			curl_setopt($handle,  CURLOPT_RETURNTRANSFER, TRUE);
// 			curl_setopt($handle, CURLOPT_TIMEOUT, $TIME_OUT);
// 			//echo "time out : ".$TIME_OUT;
	
// 			if (count($post_params)>0){
// 				curl_setopt($handle, CURLOPT_POST, true);
// 				curl_setopt($handle, CURLOPT_POSTFIELDS, http_build_query(array("parms"=>json_encode($post_params) ) ) );
// 			}
// 			//  output  header information
// 			// curl_setopt($handle, CURLINFO_HEADER_OUT , true);
				
				
// 			//https20150711
// 			curl_setopt($handle, CURLOPT_SSL_VERIFYPEER, 0);
// 			curl_setopt($handle, CURLOPT_SSL_VERIFYHOST, 0);
// 			curl_setopt($handle, CURLOPT_PORT, 443);
				
	
// 			/* Get the HTML or whatever is linked in $url. */
// 			$response = curl_exec($handle);
// 			$curl_errno = curl_errno($handle);
// 			$curl_error = curl_error($handle);
// 			if ($curl_errno > 0) { // network error
// 				$rtn['message']="cURL Error $curl_errno : $curl_error";
// 				$rtn['success'] = false ;
// 				curl_close($handle);
// 				return $rtn;
// 			}
	
// 			/* Check for 404 (file not found). */
// 			$httpCode = curl_getinfo($handle, CURLINFO_HTTP_CODE);
// 			//echo $httpCode.$response."\n";
// 			if ($httpCode == '200' ){
					
// 				$rtn['wishResponse'] = json_decode($response , true);
					
// 				if ($rtn['wishResponse']==null){
// 					// json_decode fails
// 					$rtn['message'] = "content return from proxy is not in json format, content:".$response;
// 					$rtn['success'] = false ;
// 				}else{
// 					$rtn['message'] = "Done With http Code 200";
// 					$rtn['success'] = true ;
	
// 				}
					
// 			}else{ // network error
// 				$rtn['message'] = "Failed for $action , Got error respond code $httpCode from Proxy";
// 				$rtn['success'] = false ;
// 				$rtn['wishResponse'] = "";
// 			}
// 			curl_close($handle);
	
// 		} catch (Exception $e) {
// 			$rtn['success'] = false;  //跟proxy之间的网站是否ok
// 			$rtn['message'] = $e->getMessage();
// 			curl_close($handle);
// 		}
// 		return $rtn;
	
// 	}//end of call_WISH_api by proxy
	
	
}