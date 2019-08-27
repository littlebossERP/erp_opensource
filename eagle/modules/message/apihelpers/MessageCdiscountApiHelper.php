<?php
namespace eagle\modules\message\apihelpers;

use \yii;
use \Exception;
use eagle\modules\message\models\TicketSession;
use eagle\modules\message\models\TicketMessage;
use eagle\modules\message\models\Customer;
use eagle\models\SaasCdiscountUser;
use eagle\modules\order\helpers\CdiscountOrderHelper;
use eagle\modules\order\models\OdOrder;
use eagle\models\SellerCallPlatformApiCounter;


class MessageCdiscountApiHelper{
    /**
	 +----------------------------------------------------------
	 * 调用cdiscount获取所有类型的订单留言详细信息并保存在user级别的数据库中
	 +----------------------------------------------------------
	 * @access static
	 +----------------------------------------------------------
	 * @param     $uid //小老板平台uid
	 * 			  $selleruserid //cdiscount saas_cdiscount_user 的username
	 * 			  $start_time //拉取的开始时间
	 *            $end_time //拉取的结束时间
	 +----------------------------------------------------------
	 								是否执行成功         错误信息         
	 * @return			    Array 'success'=>true,'error'=>''
	 *
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		lwj		2016-03-14   			初始化
	 +----------------------------------------------------------
	 **/
    public static function getCdiscountOrderMsg($uid, $selleruserid, $start_time, $end_time){
        //时间格式转换，cdiscount格式2015-02-01T09:03:09
        $t_time = date('Y-m-d H:i:s',$start_time);
        $e_time = date('Y-m-d H:i:s',$end_time);
        $t_time_array = explode(' ',$t_time);
        $e_time_array = explode(' ',$e_time);
        $new_start_time = $t_time_array[0].'T'.$t_time_array[1];
        $new_end_time = $e_time_array[0].'T'.$e_time_array[1];
//         $sellername = '';
        
        $touched_session_ids = array();
        $touched_customer_ids = array();
        $messages_array = array();
        $cdiscountUser = SaasCdiscountUser::findOne(["uid"=>$uid,"username"=>$selleruserid]);
        
        if ($cdiscountUser == null){
            return array('success'=>false,'error'=>'没有该用户记录 uid='.$uid." and username = $selleruserid");
        }
//         $sellername = $cdiscountUser->shopname;
        $status = array("All");
        $token_key = $cdiscountUser->token;
        $responeList = CdiscountOrderHelper::getOrderClaimList($token_key, '', $new_start_time, $new_end_time, $status);
        if(empty($responeList)){
            return array('success'=>false,'error'=>'CdiscountOrderHelper::getOrderClaimList 执行失败');
        }
        
        if($responeList['success'] == false){
            return array('success'=>false,'error'=>$responeList['message']);
        }
        
        if(empty($responeList['proxyResponse']['claimList'])&&$responeList['proxyResponse']['success'] != true){//一般为参数传失败
            return array('success'=>false,'error'=>$responeList['proxyResponse']['message']);
        }
        
        if(!empty($responeList['proxyResponse']['claimList'])){
            $msgList = $responeList['proxyResponse']['claimList'];
            $detailMsgList = $msgList['GetOrderClaimListResult']['OrderClaimList'];
            if(!empty($detailMsgList)){
                if(isset($detailMsgList['OrderClaim'][0])){//多个message
                    $handleMsg = self::handleCdiscountMsg($detailMsgList['OrderClaim']);//去掉重复的session
                    foreach ($handleMsg['message_claim'] as $key => $msg_array){
                        $messages_array[] = $msg_array;
                    }
//                     $sellername = $handleMsg['seller_name'];
                }else{//单个message
                    $messages_array[] = $detailMsgList['OrderClaim'];
//                     if(isset($detailMsgList['OrderClaim']['Messages']['Message'][0])){
//                         if($detailMsgList['OrderClaim']['Subject'] == $detailMsgList['OrderClaim']['OrderNumber']){//order questions
//                             $sellername = $detailMsgList['OrderClaim']['Messages']['Message'][0]['Sender'];
//                         }else{//claims,第一条sender一般为buyer
//                             foreach ($detailMsgList['OrderClaim']['Messages']['Message'] as $Claim_key=>$Claim_value){
//                                 if($detailMsgList['OrderClaim']['Messages']['Message'][0]['Sender'] != $Claim_value['Sender']){
//                                     $sellername = $Claim_value['Sender'];
//                                 }
//                             }
//                             if($sellername == ''){
//                                 $sellername = 'seller';
//                             }
//                         }
//                     }else{
//                         if($detailMsgList['OrderClaim']['Subject'] == $detailMsgList['OrderClaim']['OrderNumber']){//order questions
//                             $sellername = $detailMsgList['OrderClaim']['Messages']['Message']['Sender'];
//                         }else{
//                             $sellername = 'seller';
//                         }
//                     }
                }
                if(!empty($messages_array)){
                    foreach ($messages_array as $message_key => $message_value){//循环message
                        $resultInsert = self::cdiscountMsgToEagleMsg($message_value, $uid, $selleruserid);//多种信息
                        if($resultInsert['success'] == false){
                            return array('success'=>false,'error'=>$resultInsert['error']);
                        }else if(!empty($resultInsert['touched_session_ids'])&&!empty($resultInsert['touched_customer_ids'])){
                            foreach ($resultInsert['touched_session_ids'] as $session_key => $session_value){
                                $touched_session_ids[''.$session_value] = $session_value;
                            }
                            foreach ($resultInsert['touched_customer_ids'] as $customer_key => $customer_value){
                                $touched_customer_ids[''.$customer_value] = $customer_value;
                            }
                            $touched_session_ids[''.$eagleMsgOne['session_id']] = $eagleMsgOne['session_id'];
                            $touched_customer_ids[''.$eagleMsgOne['buyer_id']] = $eagleMsgOne['buyer_id'];
                        }
                    } 
                }
                return array('success'=>true,'error'=>'','touched_session_ids'=>$touched_session_ids,'touched_customer_ids'=>$touched_customer_ids);
            }else{
                return array('success'=>true,'error'=>'','updateLog'=>'No message updated');
            }
        }
    }
    
	/**
	 +----------------------------------------------------------
	 * cdiscount用于保存拉取下来的msg信息
	 +----------------------------------------------------------
	 * @access static
	 +----------------------------------------------------------
	 * @param     $msg_array //处理完的订单留言数组
	 * 			  $uid //小老板平台uid
	 * 			  $selleruserid //cdiscount saas_cdiscount_user 的username
	 +----------------------------------------------------------
	 								是否执行成功         错误信息         
	 * @return			    Array 'success'=>true,'error'=>''
	 *
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		lwj		2016-03-14   			初始化
	 +----------------------------------------------------------
	 **/ 
    public static function cdiscountMsgToEagleMsg(&$msg_array, $uid, $selleruserid){
            $buyername = '';
            $sellername = '';
            $touched_session_ids = array();
            $touched_customer_ids = array();
            $detail_message_array = array();
            
//             $eagleMsgOne['session_id'] = $msg_array['Id'];
//             $session_Id = self::handleMsgSessionID($msg_array['OrderNumber'], $msg_array['Subject'], 3, -3);//组织唯一的session_id
//             if($session_Id == ''){
//                 return array('success'=>false,'error'=>'获取sessionId失败');
//             }else{
//                 $eagleMsgOne['session_id'] = $session_Id;
//             }
            
            $eagleMsgOne['list_related_id'] = $msg_array['OrderNumber'];
            $check_reslut = strpos($msg_array['Subject'],$msg_array['OrderNumber']);
            if($msg_array['OrderNumber'] == $msg_array['Subject']||$check_reslut !== false){//Orders questions
                $eagleMsgOne['related_type'] = 'Q';
                $eagleMsgOne['list_related_type'] = 'Q';
            }else{
                $eagleMsgOne['related_type'] = 'O';
                $eagleMsgOne['list_related_type'] = 'O';
            }
            $eagleMsgOne['messageType'] = '1';
            $eagleMsgOne['related_id'] = $eagleMsgOne['list_related_id'];
            $eagleMsgOne['list_contact'] = json_encode($msg_array);
            $eagleMsgOne['msgTitle'] = $msg_array['Subject'];
            $eagleMsgOne['session_type'] = $msg_array['ClaimType'];
            $eagleMsgOne['session_status'] = $msg_array['Status'];
            
            $cdiscountMsgArr = $msg_array['Messages']['Message'];
            $check_reslut = strpos($msg_array['Subject'],$msg_array['OrderNumber']);
            if(isset($cdiscountMsgArr[0])){//多个message
                if($msg_array['OrderNumber'] == $msg_array['Subject']||$check_reslut !== false){//Orders questions
                    $res = OdOrder::findOne(["order_source"=>"cdiscount","order_source_order_id"=>$msg_array['OrderNumber']]);
                    if(!empty($res)){
                        $buyername = $res->source_buyer_user_id;
                    }else{//暂时处理，假如找不到，仍然为空
                        $buyername = 'buyer';
                    }
                    $sellername = $cdiscountMsgArr[0]['Sender'];
                }else{
                    $buyername = $cdiscountMsgArr[0]['Sender'];
                    foreach ($cdiscountMsgArr as $cdiscountMsgArr_key=>$cdiscountMsgArr_value){//查找当前session seller_nickname
                        if($buyername != $cdiscountMsgArr_value['Sender']){
                            $sellername = $cdiscountMsgArr_value['Sender'];
                        }
                    }
                    if($sellername == ''){
                        $sellername = 'seller';
                    }
                }
                foreach ($cdiscountMsgArr as $arr_key =>$arr_value){
                    $detail_message_array[] = $arr_value;
                }
            }else{  //单个message
                if($msg_array['OrderNumber'] == $msg_array['Subject']||$check_reslut !== false){//Orders questions
                    $res = OdOrder::findOne(["order_source"=>"cdiscount","order_source_order_id"=>$msg_array['OrderNumber']]);
                    if(!empty($res)){
                        $buyername = $res->source_buyer_user_id;
                    }else{//暂时处理，假如找不到，仍然为空
                        $buyername = 'buyer';
                    }
                    $sellername = $cdiscountMsgArr['Sender'];
                }else{
                    $buyername = $cdiscountMsgArr['Sender'];//只有一个message，暂时不知道sellername
                    $sellername = 'seller';
                }
                $detail_message_array[] = $cdiscountMsgArr;
            }
            
            if(!empty($detail_message_array)){
                foreach ($detail_message_array as $msg_key =>$msg_value){
                    if(empty($msg_value['Content'])){
                       continue; 
                    }
                    
                    if(is_null($msg_value['Sender']))
                    	$msg_value['Sender'] = 'Cdiscount Serveice@'.date("Y-m-d H:i:s",time());
                    
                    //去掉换行 空格 单双引号
                    $unit_key = '';
                    $fin_sendered = self::standardContent($msg_value['Sender']);
                    $unit_key = self::handleMsgSessionID($fin_sendered, $msg_value['Content'], 10, -10);
                    if($unit_key['session_string'] == ''){
                    	return array('success'=>false,'error'=>'获取MsgKey失败');
                    }else{
                        $eagleMsgOne['msgKey'] = $unit_key['session_string'].$msg_key;
                    }
                    
                    //有可能order_number与subject都相同，所以需要用到对应第一个message的后几个字母来串
                    if($msg_key == 0){
                        $last_key = self::handleMsgSessionID($fin_sendered, $msg_value['Content'], 10, -10);//$last_key['session_last']主要用来组织sessionId
                    }
                    $session_Id = self::handleMsgSessionID($msg_array['OrderNumber'], $msg_array['Subject'], 3, -3);//组织唯一的session_id
                    if($session_Id['session_string'] == ''||$last_key['session_last'] ==''){
                        return array('success'=>false,'error'=>'获取sessionId失败');
                    }else{
                        $eagleMsgOne['session_id'] = $session_Id['session_string'].$last_key['session_last'];//用到content后4位来组织sessionid
                    }
                    
                    if($msg_value['Sender'] != $sellername){//获取buyername的值
                        $eagleMsgOne['buyer_id'] = $msg_value['Sender'];
                        $eagleMsgOne['buyer_nickname'] = $msg_value['Sender'];
                        $eagleMsgOne['seller_nickname'] = $sellername;
                        $eagleMsgOne['isSend'] = 0;
                        $eagleMsgOne['msgCreateTime'] = date('Y-m-d H:i:s',strtotime($msg_value['Timestamp']));
                        $eagleMsgOne['content'] = $msg_value['Content'];
                        $eagleMsgOne['app_time'] = date('Y-m-d H:i:s',strtotime($msg_value['Timestamp'])+28800);
                    }else{//seller信息
                        $eagleMsgOne['buyer_id'] = $buyername;
                        $eagleMsgOne['buyer_nickname'] = $buyername;
                        $eagleMsgOne['seller_nickname'] = $msg_value['Sender'];
                        $eagleMsgOne['isSend'] = 1;
                        $eagleMsgOne['msgCreateTime'] = date('Y-m-d H:i:s',strtotime($msg_value['Timestamp']));
                        $eagleMsgOne['content'] = $msg_value['Content'];
                        $eagleMsgOne['app_time'] = date('Y-m-d H:i:s',strtotime($msg_value['Timestamp'])+28800);
                    }
                    $result = MessageApiHelper::_insertMsg($uid, $selleruserid, 'cdiscount', 'message_id', $eagleMsgOne);
                    if($result['success'] == false){//插入当前数据是否成功
                        return array('success'=>false,'error'=>$result['error']);
                    }else{
                        if(!empty($eagleMsgOne['session_id'])){
                            $touched_session_ids[$eagleMsgOne['session_id']] = $eagleMsgOne['session_id'];
                        }
                        if(!empty($eagleMsgOne['buyer_id'])){
                            $touched_customer_ids[$eagleMsgOne['buyer_id']] = $eagleMsgOne['buyer_id'];
                        }
                    }
//                     print_r($eagleMsgOne);
                }
            }
            return array('success'=>true,'error'=>'','touched_session_ids'=>$touched_session_ids,'touched_customer_ids'=>$touched_customer_ids);
    
    } 
    /**
     +----------------------------------------------------------
     * cdiscount用于处理拉取下来的msg信息，主要用于过滤重复的message（即包含关系的信息，一般取较多信息的message数组）
     +----------------------------------------------------------
     * @access static
     +----------------------------------------------------------
     * @param     $claim_array //在cdiscount拉取下来的原始订单留言数组
     +----------------------------------------------------------
                                                                                                  是否执行成功         错误信息
     * @return			    Array 'message_claim'=>,'seller_name'=>
     *
     +----------------------------------------------------------
     * log			name	date					note
     * @author		lwj		2016-03-14   			初始化
     +----------------------------------------------------------
     **/
    public static function handleCdiscountMsg($claim_array){
        $new_claim_array = array();
        $message_claim = array();
        $seller_name = '';
        foreach ($claim_array as $claim_key => $claim_value){
            $check_reslut = strpos($claim_value['Subject'],$claim_value['OrderNumber']);//order_id含于标题中
            if($claim_value['OrderNumber'] == $claim_value['Subject']||$check_reslut !== false){//获取seller_name
                if(isset($claim_value['Messages']['Message'][0])){
                    $seller_name = $claim_value['Messages']['Message'][0]['Sender'];
                }else{
                    $seller_name = $claim_value['Messages']['Message']['Sender'];
                }
            }
            //保证key值有效性
            $last_con = self::countCdiscountMsg($claim_value['Messages']['Message']);//取第一个message后4位
            $subject = self::standardContent($claim_value['Subject']);
            $last_sub = substr($subject,-5);
            $last_sub_change = base64_encode($last_sub);
            $session_key = $last_sub_change.'-'.$claim_value['OrderNumber'].$last_con['last_con'];
            
            if(isset($new_claim_array[$session_key])){//假如存在相同的session，比较message长度，用message多的覆盖短的；
                $old_session_array = $new_claim_array[$session_key];
                $new_session_array = $claim_value;
                
                $old_messages = $old_session_array['Messages']['Message'];
                $new_messages = $new_session_array['Messages']['Message'];
                
                $old_compare = self::countCdiscountMsg($old_messages);
                $new_compare = self::countCdiscountMsg($new_messages);
                
                if(($old_compare['first_content'] == $new_compare['first_content'])&&($old_compare['sender'] == $new_compare['sender'])){
                    if($old_compare['count'] == $new_compare['count']){//因为返沪的message有可能内容和长度都相同，这时需要比较LastUpdatedDate
                        $new_time = date('Y-m-d H:i:s',strtotime($new_session_array['LastUpdatedDate']));
                        $old_time = date('Y-m-d H:i:s',strtotime($old_session_array['LastUpdatedDate']));
                        if($new_time > $old_time){//取更新时间最迟的那个
                            $new_claim_array[$session_key] = $claim_value;
                        }
                    }else if($new_compare['count'] > $old_compare['count']){//多于旧的message长度就覆盖
                        $new_claim_array[$session_key] = $claim_value;
                    }
                }else{//订单及对象相同，message不相同，避免覆盖
                    $new_session_key = $session_key.$new_compare['time'];
                    $new_claim_array[$new_session_key] = $claim_value;
                }
            }else{
                $new_claim_array[$session_key] = $claim_value;
            }
            
            
        }
        
        foreach ($new_claim_array as $new_claim_key => $new_claim_value){
            $message_claim[] = $new_claim_value;
        }
        return array('message_claim'=>$message_claim,'seller_name'=>$seller_name);
    }
    /**
     +----------------------------------------------------------
     * 主要暂时提取所有message第一个的内容以及统计所有message的总数量，以及相关的唯一标识符
     +----------------------------------------------------------
     * @access static
     +----------------------------------------------------------
     * @param     $message_array //在cdiscount拉取下来单个session数组
     +----------------------------------------------------------
     * @return	  Array 'first_content'=>'','count'=>'','time'=>'','sender'=>'','sender'=>'','last_con'=>''
     *            first_content 第一条message的内容信息
     *            count 统计该session的message总数量
     *            time 第一条message的发送时间
     *            sender 第一条message的发送人
     *            last_con 第一条message的后5位，作为唯一的标识符
     +----------------------------------------------------------
     * log			name	date					note
     * @author		lwj		2016-03-14   			初始化
     +----------------------------------------------------------
     **/
    public static function countCdiscountMsg($message_array){//主要暂时提取所有message第一个的内容以及统计所有message的总数量
        if(isset($message_array[0])){
            $first_content = $message_array[0]['Content'];
            $count = count($message_array);
            $time = $message_array[0]['Timestamp'];
            $sender = $message_array[0]['Sender'];
            
            $fin_subjected = self::standardContent($first_content);
            $last_con = substr($fin_subjected,-5);
            $last_con = base64_encode($last_con);
        }else{
            $first_content = $message_array['Content'];
            $count = 1;//message长度
            $time = $message_array['Timestamp'];
            $sender = $message_array['Sender'];
            
            $fin_subjected = self::standardContent($first_content);
            $last_con = substr($fin_subjected,-5);
            $last_con = base64_encode($last_con);
            
        }
        return array('first_content'=>$first_content,'count'=>$count,'time'=>$time, 'sender'=>$sender, 'last_con'=>$last_con);
    }
    
    /**
     +----------------------------------------------------------
     * 主要组织返回的每个session的sessionId
     +----------------------------------------------------------
     * @access static
     +----------------------------------------------------------
     * @param     $mid_string //一般为订单号
     *            $subject //可能为订单标题对象或为message内容
     *            $first_num //起始截取的位数
     *            $last_num //内容最后截取的位数
     +----------------------------------------------------------
     * @return	  Array 'session_string'=>'','session_last'=>''
     *            session_string 一般为sessionID
     *            session_last 一般为标识符
     +----------------------------------------------------------
     * log			name	date					note
     * @author		lwj		2016-03-14   			初始化
     +----------------------------------------------------------
     **/
    public static function handleMsgSessionID($mid_string, $subject, $first_num, $last_num){
        $msgSessionID = '';
        $session_last = '';
        if(!empty($mid_string)&&!empty($subject)){//对标题对象进行处理
            
            $fin_subjected = self::standardContent($subject);
            
            $first = substr($fin_subjected,0,$first_num);
            $last = substr($fin_subjected,$last_num);
            
            $first = base64_encode($first);
            $last = base64_encode($last);
            
            $msgSessionID = $first.$mid_string.$last;//组装sessionID
            
            $session_last = substr($fin_subjected,-4);//主要用来取content最后4位，只有sessionId用到
            $session_last = base64_encode($session_last);
        }
        return array('session_string'=>$msgSessionID,'session_last'=>$session_last);
    }
    
    /**
     +----------------------------------------------------------
     * 主要处理message content内容的 空格 单双引号 换行
     +----------------------------------------------------------
     * @access static
     +----------------------------------------------------------
     * @param     $content //message 内容
     +----------------------------------------------------------
     * @return	  $fin_sub //处理完字符串
     +----------------------------------------------------------
     * log			name	date					note
     * @author		lwj		2016-03-14   			初始化
     +----------------------------------------------------------
     **/
    public static function standardContent($content){
        
        $new_subject = preg_replace('/\r|\n|\s/', '', $content);
        $subjected = str_replace("'","",$new_subject);
        $fin_sub = str_replace('"','',$subjected);
        
        return $fin_sub;
    }
    
    /**
     +----------------------------------------------------------
     * 查找这两种情况 的对所有订单
     * 1.有claim未closed；
       2.有任何小老板message未读；
     +----------------------------------------------------------
     * @access static
     +----------------------------------------------------------
     * @return	   $orderIds = [
                    'openStatus'=>[
                            'success'=>true,  
                            'message'=>'',
                            'orderIds'=>$ids],
                    'unRead'=>[
                            'success'=>true,  
                            'message'=>'',
                            'orderIds'=>$ids],
                    ];
     +----------------------------------------------------------
     * log			name	date					note
     * @author		lwj		2016-04-18   			初始化
     +----------------------------------------------------------
     **/
    public static function claimOrderIDs($seller_id=''){
        $orderIds = array();
        $notClosed = array();
        $unRead = array();
        
        $condition = ['session_status'=>'Open','platform_source'=>'cdiscount'];
        if(!empty($seller_id))
        	$condition['seller_id'] = $seller_id;
        
        $statusIds = TicketSession::find()->where($condition)->asArray()->all();
        if(!empty($statusIds)){//Opend状态的订单号
            $ids = array();
            foreach ($statusIds as $o){
                $ids[] = $o['related_id'];
            }
            $notClosed = [
              'success'=>true,  
              'message'=>'',
              'orderIds'=>$ids
            ];
        }else{
            $notClosed = [
                'success'=>false,
                'message'=>'没有找到相关订单',
                'orderIds'=>''
            ];
        }
        
        $condition = ['has_read'=>0,'platform_source'=>'cdiscount'];
        if(!empty($seller_id))
        	$condition['seller_id'] = $seller_id;
        $unReadIds = TicketSession::find()->where($condition)->asArray()->all();
        if(!empty($unReadIds)){//Opend状态的订单号
            $unread_ids = array();
            foreach ($unReadIds as $v){
                $unread_ids[] = $v['related_id'];
            }
            $unRead = [
                'success'=>true,
                'message'=>'',
                'orderIds'=>$unread_ids
            ];
        }else{
            $unRead = [
                'success'=>false,
                'message'=>'没有找到相关订单',
                'orderIds'=>''
            ];
        }
        
        $orderIds = [
            'openStatus'=>$notClosed,
            'unRead'=>$unRead
        ];
        
        return $orderIds;
    }
    
    
    
}