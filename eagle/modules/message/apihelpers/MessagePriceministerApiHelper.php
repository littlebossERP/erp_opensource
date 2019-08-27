<?php
namespace eagle\modules\message\apihelpers;

use \yii;
use \Exception;
use eagle\modules\message\models\TicketSession;
use eagle\modules\message\models\TicketMessage;
use eagle\modules\message\models\Customer;
use eagle\modules\order\helpers\CdiscountOrderHelper;
use eagle\modules\order\models\OdOrder;
use eagle\models\SellerCallPlatformApiCounter;
use eagle\models\SaasPriceministerUser;
use eagle\modules\order\helpers\PriceministerOrderInterface;
use eagle\models\SaasMessageAutosync;
use eagle\modules\message\helpers\SaasMessageAutoSyncApiHelper;
use eagle\modules\util\helpers\ConfigHelper;
use eagle\modules\util\helpers\RedisHelper;


class MessagePriceministerApiHelper{
    
    public static function getPriceministerItemMsg($uid, $selleruserid){
      $touched_session_ids = array();
      $touched_customer_ids = array();
      $item_array = array();
      $priceministerUser = SaasPriceministerUser::findOne(["uid"=>$uid,"username"=>$selleruserid]);
      if ($priceministerUser == null){
          return array('success'=>false,'error'=>'没有该用户记录 uid='.$uid." and username = $selleruserid");
      }
      $PmOrderInterface = new PriceministerOrderInterface();
      $PmOrderInterface->setStoreNamePwd($priceministerUser["username"],$priceministerUser["token"]);
      $apiReturn = $PmOrderInterface->GetItemToDoList();//取用户itemid
      if(!$apiReturn["success"]){
          return array('success'=>false,'error'=>$apiReturn['message']);
      }
      if(!$apiReturn["content"]["success"]){
          return array('success'=>false,'error'=>$apiReturn["content"]['message']);
      }
      
      if(!empty($apiReturn['content']['rtn']['response']['items'])){
          if(isset($apiReturn['content']['rtn']['response']['items']['item'][0])){
              foreach ($apiReturn['content']['rtn']['response']['items']['item'] as $item_id_array){
                  $item_array[] = $item_id_array['itemid'];
              }
          }else if(isset($apiReturn['content']['rtn']['response']['items']['item']['itemid'])){
              $item_array[] =$apiReturn['content']['rtn']['response']['items']['item']['itemid'];
          }
          
          if(!empty($item_array)){
              foreach ($item_array as $detail_item_id){
                  $ItemInfosData = $PmOrderInterface->GetItemInfos($detail_item_id);//根据itemId获取message
                  
                  $returnMsgData = self::getPriceministerMsg($uid, $selleruserid, $ItemInfosData);//处理message并录入数据
                  if($returnMsgData['success']){
                      if(!empty($returnMsgData['touched_session_ids'])&&!empty($returnMsgData['touched_customer_ids'])){
                          foreach ($returnMsgData['touched_session_ids'] as $session_key => $session_value){
                              $touched_session_ids[''.$session_value] = $session_value;
                          }
                          foreach ($returnMsgData['touched_customer_ids'] as $customer_key => $customer_value){
                              $touched_customer_ids[''.$customer_value] = $customer_value;
                          }
                          $touched_session_ids[''.$eagleMsgOne['session_id']] = $eagleMsgOne['session_id'];
                          $touched_customer_ids[''.$eagleMsgOne['buyer_id']] = $eagleMsgOne['buyer_id'];
                      }
                  }else{
                      return array('success'=>false,'error'=>$returnMsgData['error']);
                  }
              }
              
              return array('success'=>true,'error'=>'','touched_session_ids'=>$touched_session_ids,'touched_customer_ids'=>$touched_customer_ids);
          }else{
              return array('success'=>false,'error'=>'Get itemId fail');
          }
          
      }else{
          return array('success'=>true,'error'=>'','updateLog'=>'No itemId message updated');
      }
    }
    
    public static function getPriceministerMsg($uid, $selleruserid,$messageData){//PM比较特殊，要根据item_id拿message
      if($messageData['success'] == false){
          return array('success'=>false,'error'=>$messageData['message']);
      }
      
      if($messageData["content"]["success"] == false){
          return array('success'=>false,'error'=>$messageData["content"]["message"]);
      }
      
      if(!empty($messageData["content"]["rtn"])){//将xml的返回结果转为数组
      	  $obj_html = simplexml_load_string($messageData["content"]["rtn"],'SimpleXMLElement', LIBXML_NOCDATA);
      	  $json_html = json_encode($obj_html);
      	  $data_array = json_decode($json_html,true);
      	  if(!empty($data_array["response"])){
      	      $resultInsert = self::priceministerMsgToEagleMsg($data_array["response"], $uid, $selleruserid, $data_array["request"]);
      	      return $resultInsert;
      	  }else{
      	      return array('success'=>false,'error'=>'No response!');
      	  }
      }
      
    }
    
    public static function priceministerMsgToEagleMsg(&$msg_array, $uid, $selleruserid, $user_request_info){//$user_request_info主要PM用到，含接口返回的user，itemid
        $buyername = '';
        $sellername = '';
        $touched_session_ids = array();
        $touched_customer_ids = array();
        $detail_message_array = array();
        
        $eagleMsgOne['list_related_id'] = $msg_array["purchaseid"];
        $eagleMsgOne['related_type'] = 'O';
        $eagleMsgOne['list_related_type'] = 'O';
        
        $eagleMsgOne['messageType'] = '1';
        $eagleMsgOne['related_id'] = $eagleMsgOne['list_related_id'];
        $eagleMsgOne['list_contact'] = json_encode($msg_array);
        $eagleMsgOne['msgTitle'] = $msg_array['item']["product"]["headline"];
        $eagleMsgOne["itemid"] = $user_request_info["itemid"];
        //正常message
        
        if(isset($msg_array['item']['message'])){
            $priceministerMsgArr = $msg_array['item']['message'];
        }else{
            $priceministerMsgArr = '';
        }
        if(!empty($priceministerMsgArr)){//PM有可能没有message，只有claim
            if(isset($priceministerMsgArr[0])){//多个message
                foreach ($priceministerMsgArr as $arr_key =>$arr_value){
                    $detail_message_array[] = $arr_value;
                    if($buyername == ''||$sellername == ''){
                        if($arr_value["sender"] != $arr_value["recipient"]){
                            $buyername = $arr_value["recipient"];
                            $sellername = $arr_value["sender"];
                        }else{
                            $buyername = $arr_value["recipient"];
                        }
                    }
            
                }
            }else{  //单个message
                $detail_message_array[] = $priceministerMsgArr;
                if($buyername == ''||$sellername == ''){
                    if($priceministerMsgArr["sender"] != $priceministerMsgArr["recipient"]){
                        $buyername = $priceministerMsgArr["recipient"];
                        $sellername = $priceministerMsgArr["sender"];
                    }else{
                        $buyername = $priceministerMsgArr["recipient"];
                    }
                }
            }
        }else{//只能从mail获取buyer
            $sellername = $user_request_info["user"];
            if(isset($msg_array['item']['mail'])){
                if(isset($msg_array['item']['mail'][0])){
                    $buyername = $msg_array['item']['mail'][0]['recipient'];
                }else{
                    $buyername = $msg_array['item']['mail']['recipient'];
                }
                
                if($sellername == $buyername){//防止两者名字相同
                    $buyername = ''; 
                }
            }
        }
     
        
        if($buyername == ''){
            $buyername == "buyer";
        }
        if($sellername == ''){
            $sellername == $user_request_info["user"];
        }
        //是否含有claim
        if(isset($msg_array['item']['claim'])){
            $priceministerClaimArr = $msg_array['item']['claim'];
            $new_claim_array = array();
            if(isset($priceministerClaimArr[0])){
                foreach ($priceministerClaimArr as $claim_arr_key =>$claim_arr_value){
                    $new_claim_array["sender"] = $buyername;
                    $new_claim_array["recipient"] = $buyername;
                    $new_claim_array["senddate"] = $claim_arr_value["creationdate"];
                    $new_claim_array["content"] = $claim_arr_value["claimcomment"];
                    $new_claim_array["is_claim"] = 1;
                    $detail_message_array[] = $new_claim_array;
                }
            }else{
                $new_claim_array["sender"] = $buyername;
                $new_claim_array["recipient"] = $buyername;
                $new_claim_array["senddate"] = $priceministerClaimArr["creationdate"];
                $new_claim_array["content"] = $priceministerClaimArr["claimcomment"];
                $new_claim_array["is_claim"] = 1;
                $detail_message_array[] = $new_claim_array;
            }
        }
        
        
        if(!empty($detail_message_array)){
            foreach ($detail_message_array as $msg_key =>$msg_value){
                $msgCreateTime = self::standardTimeStr($msg_value["senddate"]);
                $eagleMsgOne['msgKey'] = $msgCreateTime;
                
                //有可能order_number与subject都相同，所以需要用到对应第一个message的后几个字母来串
                $eagleMsgOne['session_id'] = $msg_array["sellerid"].$user_request_info["itemid"];//以sellerId与itemid作为sessionId
                
                if(isset($msg_value["is_claim"])){
                    $eagleMsgOne["is_claim"] = array("is_claim"=>$msg_value["is_claim"]);//查看是否有claim
                }
                
                if($msg_value['sender'] != $msg_value['recipient']){
                    $eagleMsgOne['buyer_id'] = $msg_value['recipient'];
                    $eagleMsgOne['buyer_nickname'] = $msg_value['recipient'];
                    $eagleMsgOne['seller_nickname'] = $msg_value['sender'];
                    $eagleMsgOne['isSend'] = 1;//1为seller发给buyer
                    $eagleMsgOne['msgCreateTime'] = date('Y-m-d H:i:s',$msgCreateTime);
                    $eagleMsgOne['content'] = $msg_value['content'];
                    $eagleMsgOne['app_time'] = date('Y-m-d H:i:s',$msgCreateTime+28800);
                }else{//seller信息
                    $eagleMsgOne['buyer_id'] = $msg_value['recipient'];
                    $eagleMsgOne['buyer_nickname'] = $msg_value['recipient'];
                    $eagleMsgOne['seller_nickname'] = $sellername;
                    $eagleMsgOne['isSend'] = 0;
                    $eagleMsgOne['msgCreateTime'] = date('Y-m-d H:i:s',$msgCreateTime);
                    $eagleMsgOne['content'] = $msg_value['content'];
                    $eagleMsgOne['app_time'] = date('Y-m-d H:i:s',$msgCreateTime+28800);
                }
                $result = MessageApiHelper::_insertMsg($uid, $selleruserid, 'priceminister', 'message_id', $eagleMsgOne);
                
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
    
    public static function standardTimeStr($time_str){
        $replace_time_str = str_replace(array('/','-'), ' ', $time_str);
        $time_array = explode(" ", $replace_time_str);
        $standard_time_str = $time_array[2]."-".$time_array[1]."-".$time_array[0]." ".$time_array[3].":00";
        $standard_time_str2 = strtotime($standard_time_str);
        return $standard_time_str2;
    }
    
    public static function priceministerSaveMsgQueue($params = array(),$changeUserDb=true){
 
        //速卖通可以直接通过订单号发送站内信，所以需要记录session信息
        $sessionList = array();
        
        //当$params['ticket_id']为空时代表新的会话
        if($params['ticket_id'] == ""){
            if(!isset($params['orderId']))
                return array('success'=>false,'error'=>'请传入订单号参数');
            	
            $orderOne = OdOrder::findOne(['order_source_order_id'=>$params['orderId'],'order_source'=>'priceminister']);
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
                    'item_id' => $params["item_id"],
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
            
            if(empty($TSession_obj->item_id)){
                return array('success'=>false,'error'=>'item_id为空请联系客服');
            }
            
            $sessionList = array(
                'ticket_id' => $TSession_obj->ticket_id,
                'session_id' => $TSession_obj->session_id,
                'related_id' => $TSession_obj->related_id,
                'seller_id' => $TSession_obj->seller_id,
                'buyer_id' => $TSession_obj->buyer_id,
                'item_id' => $TSession_obj->item_id,
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
            'item_id' => $sessionList["item_id"],
        );
        
        $tmpAddi_info = array();
        if($TSession_obj != null){
            $addi_info = json_decode($TSession_obj->addi_info,true);
            $tmpAddi_info['channelId'] = $TSession_obj->session_id;
            $tmpAddi_info['item_id'] = $TSession_obj->item_id;//PM专用
            $sessionList['buyer_id'] = $TSession_obj->buyer_id;
        }
        
        
        $msgBufferArr = array(
            'seller_id'=>$sessionList['seller_id'],
            'message_type'=>$params['msgType'],
            'order_id'=>$params['msgType'] == "1" ? $sessionList['related_id'] : '',
//             'item_id'=>$params['msgType'] == "1" ? $sessionList['item_id'] : '',//Pm专用
            'buyer_id'=>empty($orderOne->source_buyer_user_id) ? $sessionList['buyer_id'] : $orderOne->source_buyer_user_id,
            'addi_info' => json_encode($tmpAddi_info),
        );
        
        if(empty($msgBufferArr['buyer_id']))
            return array('success'=>false,'error'=>'buyer_id为空请联系客服');
        
        $result = MessageApiHelper::platformSaveMsgQueue($params['puid'], $params['platform_source'],
            $msgOne, $msgBufferArr, $sessionList, $changeUserDb);
        
        return $result;
        
    }
    
    public static function addItemMessage($puid, $selleruserid, $content, $item_id){
        try {
            $priceminsterUser = SaasPriceministerUser::findOne(['username'=>$selleruserid,'uid'=>$puid]);
            if ($priceminsterUser == null){
                return array('Ack'=>false,'error'=>'没有该用户记录');
            }
            if(empty($item_id)){
                return array('Ack'=>false,'error'=>'item_id不能为空');
            }
            if(empty($content)){
                return array('Ack'=>false,'error'=>'发送内容不能为空');
            }
            $PMInterface = new PriceministerOrderInterface();
            $PMInterface->setStoreNamePwd($priceminsterUser["username"], $priceminsterUser["token"]);
            $result = $PMInterface->ContactUserAboutItem($item_id,$content);
            if(!$result["success"]){
                return array('Ack'=>false,'error'=>$result["message"]);
            }else{
                return array('Ack'=>$result["success"],'error'=>'');
            }
        }catch (\Exception $ex){
			return array('Ack'=>false, 'error'=>$ex->getMessage());
		}
        
    }
    
    public static function getPriceministerDetaliItemMsg($uid, $selleruserid ,$item_ids = array()){//获取单个订单噶相关itemId获取message
        $touched_session_ids = array();
        $touched_customer_ids = array();
        $item_array = array();
        $priceministerUser = SaasPriceministerUser::findOne(["uid"=>$uid,"username"=>$selleruserid]);
        if ($priceministerUser == null){
            return array('success'=>false,'error'=>'没有该用户记录 uid='.$uid." and username = $selleruserid");
        }
        if(count($item_ids) == 0){
            return array('success'=>false,'error'=>'item_id不能为空！');
        }else{
            $item_array = $item_ids;
        }
        
        $PmOrderInterface = new PriceministerOrderInterface();
        $PmOrderInterface->setStoreNamePwd($priceministerUser["username"],$priceministerUser["token"]);
        
        if(!empty($item_array)){
            foreach ($item_array as $detail_item_id){
                $ItemInfosData = $PmOrderInterface->GetItemInfos($detail_item_id);//根据itemId获取message
        
                $returnMsgData = self::getPriceministerMsg($uid, $selleruserid, $ItemInfosData);//处理message并录入数据
                if($returnMsgData['success']){
                    if(!empty($returnMsgData['touched_session_ids'])&&!empty($returnMsgData['touched_customer_ids'])){
                        foreach ($returnMsgData['touched_session_ids'] as $session_key => $session_value){
                            $touched_session_ids[''.$session_value] = $session_value;
                        }
                        foreach ($returnMsgData['touched_customer_ids'] as $customer_key => $customer_value){
                            $touched_customer_ids[''.$customer_value] = $customer_value;
                        }
                        $touched_session_ids[''.$eagleMsgOne['session_id']] = $eagleMsgOne['session_id'];
                        $touched_customer_ids[''.$eagleMsgOne['buyer_id']] = $eagleMsgOne['buyer_id'];
                    }
                }else{
                    return array('success'=>false,'error'=>$returnMsgData['error']);
                }
            }
            $result = array('success'=>true,'error'=>'','touched_session_ids'=>$touched_session_ids,'touched_customer_ids'=>$touched_customer_ids);
        }
        
        $SAA_obj = SaasMessageAutosync::findOne(["uid"=>$uid,"sellerloginid"=>$selleruserid,"platform_source"=>"priceminister"]);
        $nowTime = time();
        if ($SAA_obj->end_time == 0){
            //第一次拉取
            if($SAA_obj->binding_time == 0){//假如绑定时间为0,将当前时间设置为绑定时间
                $SAA_obj->binding_time = $nowTime;
                $start_time = $nowTime - (86400 * SaasMessageAutoSyncApiHelper::$platformFetchFewDaysAgoMsg['priceminister']);
            }else{
                $start_time = $SAA_obj->binding_time - (86400 * SaasMessageAutoSyncApiHelper::$platformFetchFewDaysAgoMsg['priceminister']);
            }
            $end_time = $nowTime;
        }else {
            $start_time = $SAA_obj->end_time;
            $end_time = $nowTime;
        }
        //4. 保存 账户同步表（MSG同步表）saas_message_autosync 提取的账号处理后的结果
        if ($result['success']) {
            $SAA_obj->start_time = $start_time;
            $SAA_obj->end_time = $end_time;
        
            $SAA_obj->last_time = time();
        
            $SAA_obj->status = 2;
            $SAA_obj->times = 0;
            $SAA_obj->message = '';
        
            //批量更新cs_customer的os_flag是否outstanding状态，1为是，0为否
            MessageApiHelper::customerUpdateOsFlag($SAA_obj->uid, 'priceminister', '', (empty($result['touched_customer_ids'])?array():$result['touched_customer_ids']), $SAA_obj->sellerloginid);
        
//             $current_time=explode(" ",microtime());	$start3a_time=round($current_time[0]*1000+$current_time[1]*1000);//ystest
//             echo "used time customerUpdateOsFlag".($start3a_time-$start3_time)."\n"; //ystest
        
            //批量更改已读状态
            MessageApiHelper::updateTicketSessionHasRead($SAA_obj->uid, 'priceminister' , (empty($result['touched_session_ids'])?array():$result['touched_session_ids']) );
//             $current_time=explode(" ",microtime());	$start3b_time=round($current_time[0]*1000+$current_time[1]*1000);//ystest
//             echo "used timeupdateTicketSessionHasRead ".($start3b_time-$start3a_time)."\n"; //ystest
        
            //删除主动发送成功，后主动拉取的msg
            MessageApiHelper::delSendSuccessMsgOrOrder($SAA_obj->uid,'priceminister','1',(empty($result['touched_customer_ids'])?array():$result['touched_customer_ids']));
        
        } else {
            $SAA_obj->message = $result['error'];
            $SAA_obj->last_time = time();
            $SAA_obj->status = 3;
            $SAA_obj->times += 1;
        }
        	
        if (!$SAA_obj->save ()){
            \Yii::error(['message',__CLASS__,__FUNCTION__,'Online', json_encode($SAA_obj->errors) ],"edb\global");
//             echo "Failed to update  ".$SAA_obj->uid." - ". $SAA_obj->sellerloginid .json_encode($SAA_obj->errors)."\n";
            return array('success'=>false,'error'=>"Failed to update  ".$SAA_obj->uid." - ". $SAA_obj->sellerloginid .json_encode($SAA_obj->errors));
        }
        	
        //吧这个 puid 的message 左边菜单 清空计数器，下次online user 读取会立即计算一次,这个时候已经 切换成改puid 的user库了。
        //ConfigHelper::setConfig("Message/left_menu_statistics", json_encode(array()));
        if(empty($puid)) $puid = \Yii::$app->user->identity->getParentUid();
        RedisHelper::delMessageCache($puid);
        return array('success'=>true,'error'=>'');
//         $current_time=explode(" ",microtime());	$start4_time=round($current_time[0]*1000+$current_time[1]*1000);//ystest
//         echo "finish for ".$SAA_obj->uid." - ". $SAA_obj->sellerloginid."used time t4-t3 ".($start4_time-$start3_time)."\n";
        
        
    }
    
}
