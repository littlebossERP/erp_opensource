<?php
namespace eagle\modules\message\apihelpers;

use yii;
use common\api\aliexpressinterface\AliexpressInterface_Auth;
use common\api\aliexpressinterface\AliexpressInterface_Api;
use eagle\modules\message\models\TicketSession;
use eagle\modules\message\models\TicketMessage;
use eagle\modules\message\models\Customer;
use eagle\modules\message\apihelpers\MessageApiHelper;
use common\api\aliexpressinterface\AliexpressInterface_Helper;
use \Exception;
use \eagle\modules\message\helpers\MessageBGJHelper;
use eagle\modules\order\models\OdOrder;
use console\helpers\AliexpressHelper;
use common\api\aliexpressinterfacev2\AliexpressInterface_Helper_Qimen;
use common\api\aliexpressinterfacev2\AliexpressInterface_Api_Qimen;

class MessageAliexpressApiHelper{
	/**
	 * 新增订单留言
	 * @author million 
	 * $orderid 速卖通订单号
	 * $selleruserid 速卖通账号
	 * $content 发送的内容
	 * return array('Ack'=>布尔值,'error'=>字符串)
	 */
	public static function addOrderMessage($orderid,$selleruserid, $buyer_id, $content){
		try {
			//****************判断此速卖通账号信息是否v2版    是则跳转     start*************
			$is_aliexpress_v2 = AliexpressInterface_Helper_Qimen::CheckAliexpressV2($selleruserid);
			if($is_aliexpress_v2){
				$result = self::addOrderMessageV2($orderid,$selleruserid, $buyer_id, $content);
				return $result;
			}
			//****************判断此账号信息是否v2版    end*************
			
			// 检查授权是否过期或者是否授权,返回true，false
			$a = AliexpressInterface_Auth::checkToken ( $selleruserid );
			// 同步成功保存数据到订单表
			if ($a) {
				$api = new AliexpressInterface_Api ();
				$access_token = $api->getAccessToken ( $selleruserid );
				if ($access_token == false){
					return array('Ack'=>false, 'code'=>-2, 'error'=>'速卖通账号：'.$selleruserid.'授权失效！');
				}else{
					$api->access_token = $access_token;
				}
				// 接口传入参数
				$param = array (
                                                'buyerId' => $buyer_id,
						'channelId' => $orderid,
						'content' => $content,
                                                'msgSources' => 'order_msg'
				);
				
				
				// 调用接口获取订单列表
				$result = $api->addMsg( $param );
				//print_r($result);
				//\Yii::info(print_r($result,true));

                                $result_error = '';
                                if(!$result['status']) {
                                    if(isset(self::$errors[$result['code']])) {
                                        $result_error = self::$errors[$result['code']];
                                    }else {
                                        $result_error = '速卖通账号：'.$selleruserid.'接口返回值错误！'.$result['msg'];
                                    }
                                     
                                }
                                return array('Ack'=>$result['status'], 'code'=>$result['code'], 'error'=>$result_error);
				
			}else{
				return array('Ack'=>false, 'code'=>-1, 'error'=>'速卖通账号：'.$selleruserid.'授权过期！');
			}
		}catch (\Exception $ex){
			return array('Ack'=>false, 'code'=>-100, 'error'=>$ex->getMessage());
		}
	}
	
	/**
	 +----------------------------------------------------------
	 * 新增订单留言，v2
	 +----------------------------------------------------------
	 * @param	$orderid 		速卖通订单号
	 * @param	$selleruserid 	速卖通账号
	 * @param	$content 		发送的内容
	 +----------------------------------------------------------
	 * log			name	date			note
	 * @author		lrq		2018/01/11		初始化
	 +----------------------------------------------------------
	 **/
	public static function addOrderMessageV2($orderid, $selleruserid, $buyer_id, $content){
		try {
			// 检查授权是否过期或者是否授权,返回true，false
			$a = AliexpressInterface_Helper_Qimen::checkToken ( $selleruserid );
			// 同步成功保存数据到订单表
			if ($a) {
				$api = new AliexpressInterface_Api_Qimen();
				
				//先获取渠道ID
				$channel_id = '';
				$cs_ticket_session = TicketSession::findOne(['platform_source' => 'aliexpress', 'seller_id' => $selleruserid, 'buyer_id' => $buyer_id]);
				if(!empty($cs_ticket_session) && !empty($cs_ticket_session->session_id)){
					$channel_id = $cs_ticket_session->session_id;
				}
				else{
					$res = $api->queryMsgChannelIdByBuyerId(['id' => $selleruserid, 'buyer_id' => $buyer_id]);
					if(!empty($res['channel_id'])){
						$channel_id = $res['channel_id'];
					}
				}
				
				// 接口传入参数
				$param = ['id' => $selleruserid, 'create_param' => json_encode([
					'buyer_id' => $buyer_id,
					'extern_id' => $orderid,
					'channel_id' => $channel_id,
					'content' => $content,
					'seller_id' => $selleruserid,
					'message_type' => 'order'
				])];
				// 调用接口获取订单列表
				$result = $api->addMsg( $param );
				
				$result_error = '';
				if(!$result['result_success']) {
					if(isset(self::$errors[$result['error_code']])) {
						$result_error = self::$errors[$result['error_code']];
					}else {
						$result_error = '速卖通账号：'.$selleruserid.'接口返回值错误！'.$result['error_message'];
					}
					 
				}
				return array('Ack'=>$result['result_success'], 'code'=>$result['error_code'], 'error'=>$result_error);
	
			}else{
				return array('Ack'=>false, 'code'=>-1, 'error'=>'速卖通账号：'.$selleruserid.'授权过期！');
			}
		}catch (\Exception $ex){
			return array('Ack'=>false, 'code'=>-100, 'error'=>$ex->getMessage());
		}
	}
	
	/**
	 * 速卖通返回错误
	 */
	public static $errors = [
			'9001'=>'参数错误',
			'9002'=>'卖家主账号值校验不成功',
			'9005'=>'发送者ID校验不成功',
			'9006'=>'接收者ID校验不成功',
			'9012'=>'内容校验不成功',
			'9013'=>'内容不能含有html',
			'9014'=>'内容超出长度大小限制',
			'9015'=>'内容不能包含中文(但可以包含中文标点)',
			'9017'=>'不能给自已发站内信',
			'9018'=>'发送者不存在',
			'9019'=>'接收者不存在',
			'9021'=>'查询产品信息时无此产品',
			'9027'=>'参数不匹配',
			'9028'=>'搜索关键字不符合规范要求!',
			'9029'=>'搜索引擎异常!',
			'9030'=>'当前登录者账号不存在!',
			'9031'=>'你没有权限对此订单进行留言!',
			'9032'=>'该订单不存在!',
			'9033'=>'买家账号不存在!',
			'9034'=>'发送量过于频繁！',
			'9035'=>'你没有权限对此用户发送站内信!',
			'9043'=>'已被对方设为黑名单，发送失败'
	];
	
	/**
	 * 调用速卖通接口获取站内信详细信息并保存在user级别的数据库中
	 * 
	 * log			name	date					note
     * @author		hqw 	2015/07/03				初始化
	 * 
	 * @param unknown $platformType	//平台类型
	 * @param unknown $uid	//小老板平台uid
	 * @param unknown $selleruserid	//速卖通上卖家id
	 * @param unknown $start_time	//拉取开始时间
	 * @param unknown $end_time	//拉取结束时间
	 * 
	 * @return array('success'=>true,'error'=>'');
	 */
	public static function getMsgAliexpress($platformType, $uid, $selleruserid, $start_time, $end_time){
		// 检查授权是否过期或者是否授权,返回true，false
		$a = AliexpressInterface_Auth::checkToken ( $selleruserid );
		
		if ($a === false) return array('success'=>false,'error'=>'速卖通账号：'.$selleruserid.'授权过期！');
		
		$api = new AliexpressInterface_Api ();
		$access_token = $api->getAccessToken ( $selleruserid );
		if ($access_token == false){
			return array('success'=>false,'error'=>'速卖通账号：'.$selleruserid.'授权失效！');
		}else{
			$api->access_token = $access_token;
		}
		
		//因为速卖通上面"gmtCreate": "20131008015109000-0700" 日期格式为西7区 所以需要做日期格式转换
// 		$start_time = strtotime(AliexpressInterface_Helper::getAliJetlagTime($start_time)->format('m/d/Y H:i:s'));
		$createDateStart = AliexpressHelper::getLaFormatTime ("m/d/Y H:i:s", $start_time );
		$createDateEnd = AliexpressHelper::getLaFormatTime ("m/d/Y H:i:s", $end_time );
		$pageNo=1;
		
		// 接口传入参数
		$param = array (
				'startTime' => $createDateStart,
				'endTime' => $createDateEnd,
				'currentPage' => $pageNo,
				'pageSize' => 50,
		);
		$touched_session_ids = array();
		$touched_customer_ids = array();
		do{
			//  调用aliexpress的api来获取对应账号的站内信信息。 由于有个数限制，这里需要循环多页面
			$param["currentPage"] = $pageNo;
			
			try{
				$result = $api->queryMessageList( $param );
			}catch (Exception $exApi){
				return array('success'=>false,'error'=>print_r($exApi,true));
			}
			
			\Yii::info(print_r($result,true),"file");

			//暂时不确定返回错误信息
			if (empty($result['success'])){
				\Yii::error(__FUNCTION__.':'.print_r($result,true));
				return array('success'=>false,'error'=>print_r($result,true));
			}

			if ($result['success'] === false){
				\Yii::error(__FUNCTION__.':'.print_r($result,true));
				return array('success'=>false,'error'=>print_r($result,true));
			}
			
			//不存在msgList时即说明api返回的数据有问题201150820
			if (!isset($result["msgList"])){
				\Yii::error(__FUNCTION__.':'.print_r($result,true));
				return array('success'=>false,'error'=>print_r($result,true));
			}
		
			$resultCount = $result["total"];
			$resultPages = ceil($result["total"] / 50.0);
			
			if ($resultCount>0){
				$msgList=$result["msgList"];
				foreach($msgList as $one){
					$resultInsert = self::aliexpressMsgToEagleMsg($one, $uid, $selleruserid, $platformType, "2");
					//there is $resultInsert['touched_session_id'] for touched session id
					if ($resultInsert['success']===false){
						return $resultInsert;
					}
					
					if (!empty($resultInsert['touched_session_id']))
						$touched_session_ids[''.$resultInsert['touched_session_id']] = $resultInsert['touched_session_id'];
				
					if (!empty($resultInsert['touched_customer_id']))
						$touched_customer_ids[''.$resultInsert['touched_customer_id']] = $resultInsert['touched_customer_id'];
				}
			}
		
			$pageNo++;
		} while($pageNo<=$resultPages);
		
		return array('success'=>true,'error'=>'','touched_session_ids'=>$touched_session_ids,'touched_customer_ids'=>$touched_customer_ids);
	}
	
	
	/**
	 * 调用速卖通接口获取订单留言详细信息并保存在user级别的数据库中
	 *
	 * log			name	date					note
	 * @author		hqw 	2015/07/03				初始化
	 *
	 * @param unknown $platformType	//平台类型
	 * @param unknown $uid	//小老板平台uid
	 * @param unknown $selleruserid	//速卖通上卖家id
	 * @param unknown $start_time	//拉取开始时间
	 * @param unknown $end_time	//拉取结束时间
	 *
	 * @return array('success'=>true,'error'=>'');
	 */
	public static function getOrderMsgAliexpress($platformType, $uid, $selleruserid, $start_time, $end_time){
		// 检查授权是否过期或者是否授权,返回true，false
		$a = AliexpressInterface_Auth::checkToken ( $selleruserid );
	
		if ($a === false) return array('success'=>false,'error'=>'速卖通账号：'.$selleruserid.'授权过期！');
	
		$api = new AliexpressInterface_Api ();
		$access_token = $api->getAccessToken ( $selleruserid );
		if ($access_token == false){
			return array('success'=>false,'error'=>'速卖通账号：'.$selleruserid.'授权失效！');
		}else{
			$api->access_token = $access_token;
		}
		
		//因为速卖通上面"gmtCreate": "20131008015109000-0700" 日期格式为西7区 所以需要做日期格式转换
// 		$start_time = strtotime(AliexpressInterface_Helper::getAliJetlagTime($start_time)->format('m/d/Y H:i:s'));
		$createDateStart = AliexpressHelper::getLaFormatTime ("m/d/Y H:i:s", $start_time );
		$createDateEnd = AliexpressHelper::getLaFormatTime ("m/d/Y H:i:s", $end_time );
		$pageNo=1;
	
		// 接口传入参数
		$param = array (
				'startTime' => $createDateStart,
				'endTime' => $createDateEnd,
				'currentPage' => $pageNo,
				'pageSize' => 50,
		);
		$touched_session_ids = array();
		$touched_customer_ids = array();
		do{
			//  调用aliexpress的api来获取对应账号的站内信信息。 由于有个数限制，这里需要循环多页面
			$param["currentPage"] = $pageNo;
			
			try{
				$result = $api->queryOrderMsgList( $param );
			}catch (Exception $exApi){
				return array('success'=>false,'error'=>print_r($exApi,true));
			}
			
			\Yii::info(print_r($result,true),"file");
	
			//暂时不确定返回错误信息
			if (empty($result['success'])){
				\Yii::error(__FUNCTION__.':'.print_r($result,true));
				return array('success'=>false,'error'=>print_r($result,true));
			}
	
			if ($result['success'] === false){
				\Yii::error(__FUNCTION__.':'.print_r($result,true));
				return array('success'=>false,'error'=>print_r($result,true));
			}
			
			//不存在msgList时即说明api返回的数据有问题201150826
			if (!isset($result["msgList"])){
				\Yii::error(__FUNCTION__.':'.print_r($result,true));
				return array('success'=>false,'error'=>print_r($result,true));
			}
	
			$resultCount = $result["total"];
			$resultPages = ceil($result["total"] / 50.0);
				
			if ($resultCount>0){
				$msgList=$result["msgList"];
				foreach($msgList as $one){
					$resultInsert = self::aliexpressMsgToEagleMsg($one, $uid, $selleruserid, $platformType, "1");
						
					if ($resultInsert['success']===false){
						return $resultInsert;
					}
					
					if (!empty($resultInsert['touched_session_id']))
						$touched_session_ids[''.$resultInsert['touched_session_id']] = $resultInsert['touched_session_id'];
					
					if (!empty($resultInsert['touched_customer_id']))
						$touched_customer_ids[''.$resultInsert['touched_customer_id']] = $resultInsert['touched_customer_id'];
				}
			}
	
			$pageNo++;
		} while($pageNo<=$resultPages);
	
		return array('success'=>true,'error'=>'','touched_session_ids'=>$touched_session_ids,'touched_customer_ids'=>$touched_customer_ids);
	}
	
	/**
	 * 将速卖通的数据转为可以保存在eagle中的格式
	 * log			name	date					note
	 * @author		hqw 	2015/07/03				初始化
	 *
	 * @param
	 * $msgOne //获取平台的原始数据
	 * $puid //小老板平台uid
	 * $selleruserid //速卖通上卖家id
	 * $platformType //平台类型
	 * $msgOrOrderType //站内信类型 0->其它 , 1->订单留言 , 2->站内信
	 */
	public static function aliexpressMsgToEagleMsg(&$msgOne, $puid, $selleruserid, $platformType, $msgOrOrderType){
		//平台session_id
		$eagleMsgOne['session_id'] = $msgOne['relationId'];
		//平台返回的判断唯一值
		$eagleMsgOne['msgKey'] = $msgOne['id'];
		
		//不确定边个是buyerId，所以需要用传进来的$selleruserid做对比确定buyerid，确定哪条信息是发送，哪条是接收.
		//$eagleMsgOne['isSend'] 是否发送，0--接收，1--发送
		
		if ($selleruserid == $msgOne['senderLoginId']){
			$eagleMsgOne['buyer_id'] = $msgOne['receiverLoginId'];
			$eagleMsgOne['buyer_nickname'] = $msgOne['receiverName'];
			$eagleMsgOne['isSend'] = 1;
				
			$eagleMsgOne['seller_nickname'] = $msgOne['senderName'];
		}
		else{
			$eagleMsgOne['buyer_id'] = $msgOne['senderLoginId'];
			$eagleMsgOne['buyer_nickname'] = $msgOne['senderName'];
			$eagleMsgOne['isSend'] = 0;
				
			$eagleMsgOne['seller_nickname'] = $msgOne['receiverName'];
		}
		
		//速卖通返回的日期格式:20150703022943000-0700,所以需要做转换
// 		$eagleMsgOne['msgCreateTime'] = substr ( $msgOne ['gmtCreate'], 0, 14 );
		$eagleMsgOne['msgCreateTime'] = date('Y-m-d H:i:s', AliexpressInterface_Helper::transLaStrTimetoTimestamp($msgOne ['gmtCreate']));
		
		$eagleMsgOne['content'] = $msgOne['content'];
		
		$eagleMsgOne['messageType'] = $msgOrOrderType;
		if ($msgOrOrderType == "1"){
			$eagleMsgOne['list_related_id'] = $msgOne['orderId'];
			$eagleMsgOne['list_related_type'] = 'O';
		}
		
// 		$eagleMsgOne['app_time'] = AliexpressInterface_Helper::changAliToEagleDate($msgOne['gmtCreate']);
		$eagleMsgOne['app_time'] = date('Y-m-d H:i:s', AliexpressInterface_Helper::transLaStrTimetoTimestamp($msgOne['gmtCreate']));
		
		if($msgOne['haveFile'] == true){
			$eagleMsgOne['haveFile'] = 1;
			$eagleMsgOne['fileUrl'] = $msgOne['fileUrl'];
		}else {
			$eagleMsgOne['haveFile'] = 0;
			$eagleMsgOne['fileUrl'] = '';
		}
		
		if($msgOne['typeId'] != "0")
			$eagleMsgOne['related_id'] = $msgOne['typeId'];
		
		if ($msgOne['messageType'] == 'order')
			$eagleMsgOne['related_type'] = 'O';
		else
		if ($msgOne['messageType'] == 'product')
			$eagleMsgOne['related_type'] = 'P';
		else 
		if ($msgOne['messageType'] == 'member')
			$eagleMsgOne['related_type'] = '';
		else
			$eagleMsgOne['related_type'] = 'M'; //暂时不清楚速卖通messageType有几种值，所以将不能解释的转为其他
		
		$eagleMsgOne['msg_contact'] = $msgOne;
		
		$result = MessageApiHelper::_insertMsg($puid, $selleruserid, $platformType, 'message_id', $eagleMsgOne);
		//yzq，记录这个session的session id，作为dirty data的cache
		$result['touched_session_id'] = $eagleMsgOne['session_id'];
		
		$result['touched_customer_id'] = $eagleMsgOne['buyer_id'];
		return $result;
	}
	
	/**
	 * 新增站内信
	 * @author million
	 * $buyerid 买家账号id
	 * $selleruserid 速卖通账号
	 * $content 发送的内容
	 * $channelId 站内信的关系ID，约等于会话ID
	 * return array('Ack'=>布尔值,'error'=>字符串)
	 * 
	 * 
	 * 由于2015-10-15更换接口，返回值变更，重写解析逻辑 by rice
	 *
	 */
	public static function addMessage($buyerid,$selleruserid,$content, $channelId = '') {
		try {
			//****************判断此速卖通账号信息是否v2版    是则跳转     start*************
			$is_aliexpress_v2 = AliexpressInterface_Helper_Qimen::CheckAliexpressV2($selleruserid);
			if($is_aliexpress_v2){
				$result = self::addMessageV2($buyerid,$selleruserid,$content, $channelId = '');
				return $result;
			}
			//****************判断此账号信息是否v2版    end*************
			
			// 检查授权是否过期或者是否授权,返回true，false
			$a = AliexpressInterface_Auth::checkToken ( $selleruserid );
			// 同步成功保存数据到订单表
			if ($a) {
				$api = new AliexpressInterface_Api ();
				$access_token = $api->getAccessToken ( $selleruserid );
				if ($access_token == false){
					return array('Ack'=>false,'error'=>'Aliexpress account：'.$selleruserid.' Token invalid！');
				}else{
					$api->access_token = $access_token;
				}
				
// 				if(!empty($content)){
// 					$tmpContent = substr($content, 0, 1 );
// 					if($tmpContent == '@')
// 						$content = '.'.$content;
// 				}
				
				// 接口传入参数
				$param = array (
						'buyerId' => $buyerid,
						'content' => $content,
						'msgSources' => 'message_center'
				);
	
				//站内信的关系ID，约等于会话ID
				if(!empty($channelId)) {
					$param['channelId'] = $channelId;
				}
				// 调用接口新增站内信
				$result = $api->addMsg( $param );


                                $result_error = '';
                                if(!$result['status']) {
                                    if(isset(self::$errors[$result['code']])) {
                                        $result_error = self::$errors[$result['code']];
                                    }else {
                                        $result_error = 'Aliexpress account：'.$selleruserid.'API return false！Message: '.$result['msg'];
                                    }
                                     
                                }
                                return array('Ack'=>$result['status'], 'error'=>$result_error);
				
			}else{
				return array('Ack'=>false,'error'=>'Aliexpress account：'.$selleruserid.' Token invalid！');
			}
		}catch (\Exception $ex){
			return array('Ack'=>false,'error'=>print_r($ex,true));
		}
	}
	
	/**
	 +----------------------------------------------------------
	 * 新增站内信，v2
	 +----------------------------------------------------------
	 * @param	$buyerid		买家账号id
	 * @param	$selleruserid	速卖通账号
	 * @param	$content		发送的内容
	 * @param	$channelId		站内信的关系ID，约等于会话ID
	 +----------------------------------------------------------
	 * log			name	date			note
	 * @author		lrq		2018/01/11		初始化
	 +----------------------------------------------------------
	 **/
	public static function addMessageV2($buyerid,$selleruserid,$content, $channelId = '') {
		try {
				
			// 检查授权是否过期或者是否授权,返回true，false
			$a = AliexpressInterface_Helper_Qimen::checkToken ( $selleruserid );
			// 同步成功保存数据到订单表
			if ($a) {
				$channel_id = '';
				$api = new AliexpressInterface_Api_Qimen ();
				//先获取渠道ID
				$cs_ticket_session = TicketSession::findOne(['platform_source' => 'aliexpress', 'seller_id' => $selleruserid, 'buyer_id' => $buyerid]);
				if(!empty($cs_ticket_session) && !empty($cs_ticket_session->session_id)){
					$channel_id = $cs_ticket_session->session_id;
				}
				else{
					$res = $api->queryMsgChannelIdByBuyerId(['id' => $selleruserid, 'buyer_id' => $buyerid]);
					if(!empty($res['channel_id'])){
						$channel_id = $res['channel_id'];
					}
				}
				
				// 接口传入参数
				$param = ['id' => $selleruserid, 'create_param' => json_encode([
					'buyer_id' => $buyerid,
					'extern_id' => 0,
					'channel_id' => $channel_id,
					'content' => $content,
					'seller_id' => $selleruserid,
					'message_type' => 'member'
				])];
				// 调用接口新增站内信
				$result = $api->addMsg( $param );
	
				$result_error = '';
				if(!$result['result_success']) {
					if(isset(self::$errors[$result['error_code']])) {
						$result_error = self::$errors[$result['error_code']];
					}else {
						$result_error = 'Aliexpress account：'.$selleruserid.'API return false！Message: '.$result['error_message'];
					}
				}
				return array('Ack'=>$result['result_success'], 'error'=>$result_error);
	
			}else{
				return array('Ack'=>false,'error'=>'Aliexpress account：'.$selleruserid.' Token invalid！');
			}
		}catch (\Exception $ex){
			return array('Ack'=>false,'error'=>print_r($ex,true));
		}
	}
	
	/**
	 * 速卖通用户保存发送站内信或订单留言接口
	 * 
	 * 用于保存在队列表中，到时会有后台Job处理发送
	 *
	 * @param
	 * $params = array(
	 * 'platform_source' => '' //平台类型
	 * 'msgType' => '' //站内信类型 1--订单，2--站内信
	 * 'puid' => '' //小老板平台uid
	 * 'contents' => '' //内容
	 * 'ticket_id' => '' //小老板会话id  当值为空时代表新建会话
	 * 'orderId' => '' //订单号 当新增订单留言时必须传入该值
	 * );
	 */
	public static function aliexpressSaveMsgQueue($params = array() , $changeUserDb=true){
	 
		//速卖通可以直接通过订单号发送站内信，所以需要记录session信息
		$sessionList = array();
		
		//当$params['ticket_id']为空时代表新的会话
		if($params['ticket_id'] == ""){
			if(!isset($params['orderId']))
				return array('success'=>false,'error'=>'请传入订单号参数');
			
			$orderOne = OdOrder::findOne(['order_source_order_id'=>$params['orderId'],'order_source'=>'aliexpress']);
			if ($orderOne == null)
				return array('success'=>false,'error'=>'没有对应的订单不能发送站内信');
			
			$TSession_obj = TicketSession::findOne(['related_id'=>$params['orderId']]);
			
			if ($TSession_obj == null){
				$sessionList = array(
						'ticket_id' => '',
						'session_id' => '',
						'platform_source' => $params['platform_source'],
						'message_type' => $params['msgType'],
						'seller_id' => $orderOne->selleruserid,
						'related_id' => $params['orderId'],
						'buyer_id' => $orderOne->source_buyer_user_id,
						'seller_nickname' => '',
						'buyer_nickname' => '',
						'has_read' => 1,
						'created' => date('Y-m-d H:i:s', time ()),
						'updated' => date('Y-m-d H:i:s', time ()),
						'lastmessage' => date('Y-m-d H:i:s', time ()),
						'last_omit_msg' => (mb_strlen($params['contents'],'utf-8') > 20) ? mb_substr($params['contents'], 0, 20,"UTF-8")."...\n" : $params['contents'],
				);
			}
		}else{
			//ticket_id 来查找对应的会话信息
			$TSession_obj = TicketSession::findOne(['ticket_id'=>$params['ticket_id']]);
		}
		
		if(empty($sessionList) && $TSession_obj == null ){
			\Yii::error('function:'.__FUNCTION__.'没有该会话信息，请检查');
			return array('success'=>false,'error'=>'function:'.__FUNCTION__.'没有该会话信息，请检查');
		}
		
		if ($TSession_obj != null && empty($sessionList)){
			$sessionList = array(
					'ticket_id' => $TSession_obj->ticket_id,
					'session_id' => $TSession_obj->session_id,
					'related_id' => $TSession_obj->related_id,
					'seller_id' => $TSession_obj->seller_id,
					'buyer_id' => $TSession_obj->buyer_id,
			);
		}
		
		$msgOne = array(
				'ticket_id' => $sessionList['ticket_id'],
				'session_id' => $sessionList['session_id'],
				'contents' => $params['contents'],
				'headers' => "",
				'created' => date('Y-m-d H:i:s', time ()),
				'updated' => date('Y-m-d H:i:s', time ()),
				'addi_info' => "",
				'app_time' => date('Y-m-d H:i:s', time ()),
				'platform_time' => date('Y-m-d H:i:s', time ()),
				'seller_id' => $sessionList['seller_id'],
				'buyer_id' => $sessionList['buyer_id'],
		);
		
		$tmpAddi_info = array();
		if($TSession_obj != null){
			$addi_info = json_decode($TSession_obj->addi_info,true);
			$tmpAddi_info['channelId'] = $TSession_obj->session_id;
			
			$sessionList['buyer_id'] = $TSession_obj->buyer_id;
		}
		
		$msgBufferArr = array(
				'seller_id'=>$sessionList['seller_id'],'message_type'=>$params['msgType'],
				'order_id'=>$params['msgType'] == "1" ? $sessionList['related_id'] : '',
				'buyer_id'=>empty($orderOne->source_buyer_user_id) ? $sessionList['buyer_id'] : $orderOne->source_buyer_user_id,
				'addi_info' => json_encode($tmpAddi_info),
		);
		
		if(empty($msgBufferArr['buyer_id']))
			return array('success'=>false,'error'=>'buyer_id为空请联系客服');
		
		$result = MessageApiHelper::platformSaveMsgQueue($params['puid'], $params['platform_source'],
			$msgOne, $msgBufferArr, $sessionList, $changeUserDb);
		
		return $result;
	}
	
	/**
	 * 调用速卖通接口获取订单留言详细信息并保存在user级别的数据库中  新接口
	 *
	 * log			name	date					note
	 * @author		hqw 	2015/07/03				初始化
	 *
	 * @param unknown $platformType	//平台类型
	 * @param unknown $uid	//小老板平台uid
	 * @param unknown $selleruserid	//速卖通上卖家id
	 * @param unknown $start_time	//拉取开始时间
	 * @param unknown $end_time	//拉取结束时间
	 *
	 * @return array('success'=>true,'error'=>'');
	 */
	public static function getMsgAliexpressRelation($platformType, $uid, $selleruserid, $start_time, $end_time, $msgOrOrderType){
		//****************判断此速卖通账号信息是否v2版    是则跳转     start*************
		$is_aliexpress_v2 = AliexpressInterface_Helper_Qimen::CheckAliexpressV2($selleruserid);
		if($is_aliexpress_v2){
			$result = self::getMsgAliexpressRelationV2($platformType, $uid, $selleruserid, $start_time, $end_time, $msgOrOrderType);
			return $result;
		}
		//****************判断此账号信息是否v2版    end*************
		
		if ($msgOrOrderType == 1)
			$msgType = 'order_msg';
		else if($msgOrOrderType == 2){
			$msgType = 'message_center';
		}else{
			return array('success'=>false,'error'=>'msg type error, Only 1 or 2.');
		}
		
		// 检查授权是否过期或者是否授权,返回true，false
		$a = AliexpressInterface_Auth::checkToken ( $selleruserid );
	
		if ($a === false) return array('success'=>false,'error'=>'速卖通账号：'.$selleruserid.'授权过期！');
	
		$api = new AliexpressInterface_Api ();
		$access_token = $api->getAccessToken ( $selleruserid );
		if ($access_token == false){
			return array('success'=>false,'error'=>'速卖通账号：'.$selleruserid.'授权失效！');
		}else{
			$api->access_token = $access_token;
		}
	
		$pageNo=1;
		
		//判断是否继续翻页，因为速卖通现在不提供时间段的查找所以只能用传进来的$start_time作比较
		$isNextListPage = true;
	
		// 接口传入参数
		$param = array (
				'currentPage' => $pageNo,
				'pageSize' => 100,
				'msgSources' => $msgType,
		);
		$touched_session_ids = array();
		$touched_customer_ids = array();
		do{
			//  调用aliexpress的api来获取对应账号的站内信信息。 由于有个数限制，这里需要循环多页面
			$param["currentPage"] = $pageNo;
				
			try{
				$result = $api->queryMsgRelationList( $param );
			}catch (Exception $exApi){
				return array('success'=>false,'error'=>print_r($exApi,true));
			}
				
// 			\Yii::info(print_r($result,true),"file");
	
			//暂时不确定返回错误信息
			if (!isset($result['result'])){
				\Yii::error(__FUNCTION__.':'.print_r($result,true));
				return array('success'=>false,'error'=>print_r($result,true));
			}
			
			if (isset($result['error_code'])){
				\Yii::error(__FUNCTION__.':'.print_r($result,true));
				return array('success'=>false,'error'=>print_r($result,true));
			}
			
			if (empty($result['result'])){
				break;
			}
	
			$msgListArr=$result["result"];
			foreach($msgListArr as $msgListOne){
				//假如最后回复时间小于拉取$start_time代表Details已经拉取过，暂时没有新的Details
				if (substr($msgListOne['messageTime'], 0, 10) < $start_time){
					$isNextListPage = false;
					break;
				}
				
				$isNextDetailPage = true;
				$detailParam['currentPage'] = 1;
				$detailParam['pageSize'] = 100;
				$detailParam['channelId'] = $msgListOne['channelId'];
				$detailParam['msgSources'] = $msgType;
				
				do{
					try{
						$detailResult = $api->queryMsgDetailList($detailParam);
					}catch (Exception $exApi){
						return array('success'=>false,'error'=>print_r($exApi,true));
					}
					
// 					\Yii::info(print_r($detailResult,true),"file");
					
					//暂时不确定返回错误信息
					if (!isset($detailResult['result'])){
						\Yii::error(__FUNCTION__.':'.print_r($detailResult,true));
						return array('success'=>false,'error'=>print_r($detailResult,true));
					}
						
					if (isset($detailResult['error_code'])){
						\Yii::error(__FUNCTION__.':'.print_r($detailResult,true));
						return array('success'=>false,'error'=>print_r($detailResult,true));
					}
						
					if (empty($detailResult['result'])){
						break;
					}
					
					foreach($detailResult['result'] as $msgOne){
						if (substr($msgOne['gmtCreate'], 0, 10) < $start_time){
							$isNextDetailPage = false;
							break;
						}
						
						$resultInsert = self::aliexpressRelationMsgToEagleMsg($msgListOne , $msgOne, $uid, $selleruserid, $msgOrOrderType);
					
						if ($resultInsert['success']===false){
							return $resultInsert;
						}
					
						if (!empty($resultInsert['touched_session_id']))
							$touched_session_ids[''.$resultInsert['touched_session_id']] = $resultInsert['touched_session_id'];
							
						if (!empty($resultInsert['touched_customer_id']))
							$touched_customer_ids[''.$resultInsert['touched_customer_id']] = $resultInsert['touched_customer_id'];
					}
					
					$detailParam['currentPage']++;
				}while ($isNextDetailPage);
			}
	
			$pageNo++;
		} while($isNextListPage);
	
		return array('success'=>true,'error'=>'','touched_session_ids'=>$touched_session_ids,'touched_customer_ids'=>$touched_customer_ids);
	}
	
	/**
	 +----------------------------------------------------------
	 * 调用速卖通接口获取订单留言详细信息并保存在user级别的数据库中  新接口，v2
	 +----------------------------------------------------------
	 * @param	$platformType		平台类型
	 * @param	$uid				小老板平台uid
	 * @param	$selleruserid		速卖通上卖家id
	 * @param	$start_time			拉取开始时间
	 * @param	$end_time			拉取结束时间
	 +----------------------------------------------------------
	 * log			name	date			note
	 * @author		lrq		2018/01/11		初始化
	 +----------------------------------------------------------
	 **/
	public static function getMsgAliexpressRelationV2($platformType, $uid, $selleruserid, $start_time, $end_time, $msgOrOrderType){
		if ($msgOrOrderType == 1)
			$msgType = 'order';
		else if($msgOrOrderType == 2){
			$msgType = 'member';
		}else{
			return array('success'=>false,'error'=>'msg type error, Only 1 or 2.');
		}
	
		// 检查授权是否过期或者是否授权,返回true，false
		$a = AliexpressInterface_Helper_Qimen::checkToken ( $selleruserid );
	
		if ($a === false) return array('success'=>false,'error'=>'速卖通账号：'.$selleruserid.'授权过期！');
	
		$api = new AliexpressInterface_Api_Qimen ();
	
		$pageNo=1;
	
		//判断是否继续翻页，因为速卖通现在不提供时间段的查找所以只能用传进来的$start_time作比较
		$isNextListPage = true;
	
		// 接口传入参数
		$param = [
			'current_page' => $pageNo,
			'page_size' => 100,
			//'msg_sources' => $msgType,
		];
		$touched_session_ids = array();
		$touched_customer_ids = array();
		do{
			//  调用aliexpress的api来获取对应账号的站内信信息。 由于有个数限制，这里需要循环多页面
			$param["current_page"] = $pageNo;
	
			try{
				$result = $api->queryMsgRelationList( ['id' => $selleruserid, 'query' => json_encode($param)] );
			}catch (Exception $exApi){
				return array('success'=>false,'error'=>print_r($exApi,true));
			}
	
			//暂时不确定返回错误信息
			if (!isset($result['relation_list'])){
				\Yii::error(__FUNCTION__.':'.print_r($result,true));
				return array('success'=>false,'error'=>print_r($result,true));
			}
				
			if (!empty($result['error_message'])){
				\Yii::error(__FUNCTION__.':'.print_r($result,true));
				return array('success'=>false,'error'=>print_r($result,true));
			}
				
			if (empty($result['relation_list'])){
				break;
			}
	
			$msgListArr = $result["relation_list"];
			foreach($msgListArr as $one){
				$msgListOne = [
					'unreadCount' => $one['unread_count'],
					'channelId' => $one['channel_id'],
					'lastMessageId' => $one['last_message_id'],
					'readStat' => $one['read_stat'],
					'lastMessageContent' => $one['last_message_content'],
					'lastMessageIsOwn' => $one['last_message_is_own'],
					'childName' => $one['child_name'],
					'messageTime' => $one['message_time'],
					'childId' => $one['child_id'],
					'otherName' => $one['other_name'],
					'otherLoginId' => $one['other_login_id'],
					'dealStat' => $one['deal_stat'],
					'rank' => $one['rank'],
				];
				//假如最后回复时间小于拉取$start_time代表Details已经拉取过，暂时没有新的Details
				if (substr($msgListOne['messageTime'], 0, 10) < $start_time){
					$isNextListPage = false;
					break;
				}
	
				$isNextDetailPage = true;
				$detailParam['id'] = $selleruserid;
				$detailParam['current_page'] = 1;
				$detailParam['page_size'] = 100;
				$detailParam['channel_id'] = $msgListOne['channelId'];
				//$detailParam['msg_sources'] = $msgType;
	
				do{
					try{
						$detailResult = $api->queryMsgDetailList($detailParam);
					}catch (Exception $exApi){
						return array('success'=>false,'error'=>print_r($exApi,true));
					}
					
					//暂时不确定返回错误信息
					if (!isset($detailResult['message_detail_list'])){
						\Yii::error(__FUNCTION__.':'.print_r($detailResult,true));
						return array('success'=>false,'error'=>print_r($detailResult,true));
					}
	
					if (isset($detailResult['error_response'])){
						\Yii::error(__FUNCTION__.':'.print_r($detailResult,true));
						return array('success'=>false,'error'=>print_r($detailResult,true));
					}
	
					if (empty($detailResult['message_detail_list'])){
						break;
					}
						
					foreach($detailResult['message_detail_list'] as $detail){
						$msgOne = [
							'id' => $detail['id'],
							'gmtCreate' => $detail['gmt_create'],
							'senderName' => $detail['sender_name'],
							'messageType' => $detail['message_type'],
							'content' => $detail['content'],
							'typeId' => empty($detail['extern_id']) ? '' : $detail['extern_id'],
							'filePath' => [
								'sPath' => empty($detail['filePath']['s_path']) ? '' : $detail['filePath']['s_path'],
								'mPath' => empty($detail['filePath']['m_path']) ? '' : $detail['filePath']['m_path'],
								'lPath' => empty($detail['filePath']['l_path']) ? '' : $detail['filePath']['l_path'],
							],
							'summary' => [
								'productName' => $detail['summary']['product_name'],
								'productImageUrl' => $detail['summary']['product_image_url'],
								'productDetailUrl' => empty($detail['summary']['product_detail_url']) ? '' : $detail['summary']['product_detail_url'],
								'orderUrl' => $detail['summary']['order_url'],
								'senderName' => $detail['summary']['sender_name'],
								'receiverName' => $detail['summary']['receiver_name'],
								'senderLoginId' => $detail['summary']['sender_login_id'],
							],
						];
						
						if (substr($msgOne['gmtCreate'], 0, 10) < $start_time){
							$isNextDetailPage = false;
							break;
						}
	
						$resultInsert = self::aliexpressRelationMsgToEagleMsg($msgListOne , $msgOne, $uid, $selleruserid, $msgOrOrderType);
							
						if ($resultInsert['success']===false){
							return $resultInsert;
						}
							
						if (!empty($resultInsert['touched_session_id']))
							$touched_session_ids[''.$resultInsert['touched_session_id']] = $resultInsert['touched_session_id'];
							
						if (!empty($resultInsert['touched_customer_id']))
							$touched_customer_ids[''.$resultInsert['touched_customer_id']] = $resultInsert['touched_customer_id'];
					}
						
					$detailParam['current_page']++;
				}while ($isNextDetailPage);
			}
	
			$pageNo++;
		} while($isNextListPage);
	
		return array('success'=>true,'error'=>'','touched_session_ids'=>$touched_session_ids,'touched_customer_ids'=>$touched_customer_ids);
	}
	
	/**
	 * 速卖通改了接口所以需要重写
	 * 将速卖通的数据转为可以保存在eagle中的格式  
	 * log			name	date					note
	 * @author		hqw 	2015/09/17				初始化
	 *
	 * @param
	 * $msgListOne	站内信list信息
	 * $msgOne	获取平台的原始数据
	 * $puid	小老板平台uid
	 * $selleruserid	速卖通上卖家id
	 * $msgOrOrderType	站内信类型 0->其它 , 1->订单留言 , 2->站内信
	 */
	public static function aliexpressRelationMsgToEagleMsg($msgListOne, &$msgOne, $puid, $selleruserid, $msgOrOrderType){
		//平台session_id
		$eagleMsgOne['session_id'] = $msgListOne['channelId'];
		//平台返回的判断唯一值
		$eagleMsgOne['msgKey'] = $msgListOne['channelId'].$msgOne['id'].$selleruserid;	//因为现在ali返回的msgID就是时间戳, API官方技术支持(速卖通开放平台技术支持)提示:可以用channelId+messageId+senderId作为唯一标识
	
		//$eagleMsgOne['isSend'] 是否发送，0--接收，1--发送
		//$msgListOne['otherName']与当前卖家或子账号建立关系的买家名字
		$eagleMsgOne['buyer_nickname'] = $msgListOne['otherName'];
		$eagleMsgOne['buyer_id'] = $msgListOne['otherLoginId'];
		
		if($msgListOne['otherName'] != $msgOne['senderName']){
			$eagleMsgOne['isSend'] = 1;
			$eagleMsgOne['seller_nickname'] = $msgOne['summary']['senderName'];
		}else{
			$eagleMsgOne['isSend'] = 0;
			$eagleMsgOne['seller_nickname'] = $msgOne['summary']['receiverName'];
		}
		
		//速卖通返回的日期格式:$msgOne[gmtCreate] => 1442287813000,所以需要做转换
		$eagleMsgOne['msgCreateTime'] = date('Y-m-d H:i:s', substr($msgOne['gmtCreate'], 0, 10));
	
		$eagleMsgOne['content'] = $msgOne['content'];
	
		$eagleMsgOne['messageType'] = $msgOrOrderType;
		if ($msgOrOrderType == "1"){
			$eagleMsgOne['list_related_id'] = $msgOne['typeId'];
			$eagleMsgOne['list_related_type'] = 'O';
		}
	
		$eagleMsgOne['app_time'] = date('Y-m-d H:i:s', substr($msgOne['gmtCreate'], 0, 10));
	
		//判断是否存在 站内信/订单留言图片
		if(isset($msgOne['filePath'][0]['lPath'])){
			$eagleMsgOne['haveFile'] = 1;
			$eagleMsgOne['fileUrl'] = $msgOne['filePath'][0]['lPath'];
		}else {
			$eagleMsgOne['haveFile'] = 0;
			$eagleMsgOne['fileUrl'] = '';
		}

		if($msgOne['typeId'] != "0")
			$eagleMsgOne['related_id'] = $msgOne['typeId'];
	
		if ($msgOne['messageType'] == 'order')
			$eagleMsgOne['related_type'] = 'O';
		else
		if ($msgOne['messageType'] == 'product')
			$eagleMsgOne['related_type'] = 'P';
		else
		if ($msgOne['messageType'] == 'member')
			$eagleMsgOne['related_type'] = '';
		else
			$eagleMsgOne['related_type'] = 'M'; //暂时不清楚速卖通messageType有几种值，所以将不能解释的转为其他
	
		$eagleMsgOne['list_contact'] = json_encode($msgListOne);
		$eagleMsgOne['msg_contact'] = $msgOne;
	
		$result = MessageApiHelper::_insertMsg($puid, $selleruserid, 'aliexpress', 'message_id', $eagleMsgOne);
		//yzq，记录这个session的session id，作为dirty data的cache
		$result['touched_session_id'] = $eagleMsgOne['session_id'];
		$result['touched_customer_id'] = $eagleMsgOne['buyer_id'];
		return $result;
	}
	
}
