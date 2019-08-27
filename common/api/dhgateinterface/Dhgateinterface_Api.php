<?php
namespace common\api\dhgateinterface;

use eagle\modules\dhgate\apihelpers\DhgateApiHelper;
use eagle\models\SaasDhgateUser;
use eagle\modules\order\models\OdOrder;

class Dhgateinterface_Api extends Dhgateinterface_Auth{
	
	function beforeCallApi($dhgate_uid){
		$this->access_token = $this->getAccessToken($dhgate_uid);
		if($this->access_token == false){
			$User_obj = SaasDhgateUser::find()->where(['dhgate_uid'=>$dhgate_uid , 'is_active'=>1])->one(); // is_active = 1才能设置 user 过期
			if($User_obj != null){
				$User_obj->is_active = 2;// token过期状态
				$User_obj->update_time = time();
				if($User_obj->save(false)){
					DhgateApiHelper::SwitchDhgateCronjob(0, $dhgate_uid);
				}else{
					return ['success'=>false , 'error_message' => "refresh token 已过期，需要重新绑定，SaasDhgateUser is_active 更改失败。" , 'response' => [] ];
				}
			} 
			
			return ['success'=>false , 'error_message' => "refresh token 已过期，需要重新绑定。" , 'response' => [] ];
		}
		return [];
	}
	
	function afterCallApi($dhgate_uid , $response){
		if(!empty($response['code'])){
			if( 40 == $response['code'] ){// 令牌Access Token过期或不存在
				$this->access_token = $this->getAccessToken($dhgate_uid ,true);
				if($this->access_token == false){
					DhgateApiHelper::SwitchDhgateCronjob(0, $dhgate_uid);
					return [ 'success'=>false , 'error_message'=>"refresh token 已过期，需要重新绑定。" , 'response'=>[] ];
				}else{
					return [ 'success'=>false , 'error_message'=>$response['message'] , 'response'=>[] ];
				}
			}
			return [ 'success'=>false , 'error_message'=>$response['message'] , 'response'=>[] ];
		}
		
		if($response['status']['code'] !== '00000000' && $response['status']['code'] != 109){
			return [ 'success'=>false , 'error_message'=>$response['status']['message'] , 'response'=>[] ];
		}
		
		DhgateApiHelper::apiCallSum("DhgateApi", 0);
		return [];
	}
################################敦煌对用户一般操作相关接口#############################################
	/**
	 * 查询账号基本信息,可查询买家账号与卖家账号的基本信息
	 */
	function dh_user_base_get(){
		try {
			$sysParams = array(
					"access_token"  => $this->access_token,
					"method"		=> "dh.user.base.get" ,
					"timestamp"		=> time() * 1000 ,
					"v"				=> "1.0"
			);
			
			$response = $this->call_dh_api($this->apiUrl , $sysParams);
			return $response;
			
		}catch (\Exception $e){
			\Yii::info(__FUNCTION__." exception:".print_r($e , true),"file");
			return ['success'=> false , 'error_message'=>$e->getMessage() , 'response'=>[] ];
		}	
	}
	
	/**
	 * 查询卖家用户信息接口
	 * 
	 */
	function dh_user_seller_get(){
		try {
			$sysParams = array(
					"access_token"  => $this->access_token,
					"method"		=> "dh.user.seller.get" ,
					"timestamp"		=> time() * 1000 ,
					"v"				=> "2.0"
			);
			
			$response = $this->call_dh_api($this->apiUrl , $sysParams);
			return $response;
		}catch (\Exception $e){
			\Yii::info(__FUNCTION__." exception:".print_r($e , true),"file");
			return ['success'=> false , 'error_message'=>$e->getMessage() , 'response'=>[] ];
		}	
	}
################################敦煌标记发货相关接口########################################

/**
 * 	相关接口 
 * @param  $dhgate_uid 
 * @param  $appParams  接口参数
 *
 * @return array (  'success'=>true  // 调用是否成功
 *                  'error_message'=>"卖家id不能为空" ,// 返回的错误消息
 * 					'response'=>array() // api返回内容 
 * 			)
 */	
################################敦煌同步订单相关接口########################################
	
	/**
	 * 查询订单列表
	 */
	public function getOrderList ($dhgate_uid,$appParams){
		try {
			$beforeRtn = $this->beforeCallApi($dhgate_uid);		
			if(!empty($beforeRtn))
				return $beforeRtn;
			
			$sysParams = array(
					"access_token"  => $this->access_token,
					"method"		=> "dh.order.list.get" ,
					"timestamp"		=> time() * 1000 ,
					"v"				=> "2.0"
			);
			
			$response = $this->call_dh_api($this->apiUrl , $sysParams + $appParams , $appParams , false);
			
			$afterRtn = $this->afterCallApi($dhgate_uid, $response);
			if(!empty($afterRtn))
				return $afterRtn;
			
			return ['success'=> true , 'error_message'=>'' , 'response'=>$response ];
		}catch (\Exception $e){
			\Yii::info(__FUNCTION__." exception:".print_r($e , true),"file");
			return ['success'=> false , 'error_message'=>$e->getMessage() , 'response'=>[] ];
		}
	}
	
	/**
	 * 查询单个订单详情
	 */
	public function getOrderDetail ($dhgate_uid,$appParams){
		try {
			$beforeRtn = $this->beforeCallApi($dhgate_uid);		
			if(!empty($beforeRtn))
				return $beforeRtn;
			
			$sysParams = array(
					"access_token"  => $this->access_token,
					"method"		=> "dh.order.get" ,
					"timestamp"		=> time() * 1000 ,
					"v"				=> "2.0"
			);
		
			$response = $this->call_dh_api($this->apiUrl , $sysParams + $appParams);
			
			$afterRtn = $this->afterCallApi($dhgate_uid, $response);
			if(!empty($afterRtn))
				return $afterRtn;
			
			return ['success'=> true , 'error_message'=>'' , 'response'=>$response ];
		}catch (\Exception $e){
			\Yii::info(__FUNCTION__." exception:".print_r($e , true),"file");
			return ['success'=> false , 'error_message'=>$e->getMessage() , 'response'=>[] ];
		}
	}
	
	/**
	 * 查询单个订单产品信息
	 */
	public function getOrderItems ($dhgate_uid,$appParams){
		try {
			$beforeRtn = $this->beforeCallApi($dhgate_uid);		
			if(!empty($beforeRtn))
				return $beforeRtn;
			
			$sysParams = array(
					"access_token"  => $this->access_token,
					"method"		=> "dh.order.product.get" ,
					"timestamp"		=> time() * 1000 ,
					"v"				=> "2.0"
			);
		
			$response = $this->call_dh_api($this->apiUrl , $sysParams + $appParams);
			
			$afterRtn = $this->afterCallApi($dhgate_uid, $response);
			if(!empty($afterRtn))
				return $afterRtn;
			
			return ['success'=> true , 'error_message'=>'' , 'response'=>$response ];
		}catch (\Exception $e){
			\Yii::info(__FUNCTION__." exception:".print_r($e , true),"file");
			return ['success'=> false , 'error_message'=>$e->getMessage() , 'response'=>[] ];
		}
	}
	
	/**
	 * 获取国家列表
	 */
	public function getCountryList ($dhgate_uid,$appParams){
		try {
			$beforeRtn = $this->beforeCallApi($dhgate_uid);
			if(!empty($beforeRtn))
				return $beforeRtn;
				
			$sysParams = array(
					"access_token"  => $this->access_token,
					"method"		=> "dh.base.countrys.get" ,
					"timestamp"		=> time() * 1000 ,
					"v"				=> "1.0"
			);
	
			$response = $this->call_dh_api($this->apiUrl , $sysParams + $appParams);
				
			$afterRtn = $this->afterCallApi($dhgate_uid, $response);
			if(!empty($afterRtn))
				return $afterRtn;
				
			return ['success'=> true , 'error_message'=>'' , 'response'=>$response ];
		}catch (\Exception $e){
			\Yii::info(__FUNCTION__." exception:".print_r($e , true),"file");
			return ['success'=> false , 'error_message'=>$e->getMessage() , 'response'=>[] ];
		}
	}

################################敦煌刊登相关接口###########################################

################################修改在线商品相关接口########################################


################################同步在线商品相关接口########################################
	/**
	 * 商品列表查询接口
	 */
	
	
	/**
	 * 获取单个产品信息
	 */



##############################敦煌线上发货相关接口#############################################
	/**
	 * 订单标记发货
	 */
	public function shipOrder ($dhgate_uid,$appParams){
		try {
			$beforeRtn = $this->beforeCallApi($dhgate_uid);
			if(!empty($beforeRtn))
				return $beforeRtn;
				
			$sysParams = array(
					"access_token"  => $this->access_token,
					"method"		=> "dh.order.delivery.save" ,
					"timestamp"		=> time() * 1000 ,
					"v"				=> "2.0"
			);
		
			$response = $this->call_dh_api($this->apiUrl , $sysParams + $appParams , $appParams , false);
				
			$afterRtn = $this->afterCallApi($dhgate_uid, $response);
			if(!empty($afterRtn))
				return $afterRtn;
				
			return ['success'=> true , 'error_message'=>'' , 'response'=>$response ];
		}catch (\Exception $e){
			\Yii::info(__FUNCTION__." exception:".print_r($e , true),"file");
			return ['success'=> false , 'error_message'=>$e->getMessage() , 'response'=>[] ];
		}
	}
	
	/**
	 * 获取运费模板中物流方式列表
	 */
	public function getShippingTypeList ($dhgate_uid){
		try {
			$beforeRtn = $this->beforeCallApi($dhgate_uid);
			if(!empty($beforeRtn))
				return $beforeRtn;
				
			$sysParams = array(
					"access_token"  => $this->access_token,
					"method"		=> "dh.shipping.types.get" ,
					"timestamp"		=> time() * 1000 ,
					"v"				=> "1.0"
			);
		
			$response = $this->call_dh_api($this->apiUrl , $sysParams);
				
			$afterRtn = $this->afterCallApi($dhgate_uid, $response);
			if(!empty($afterRtn))
				return $afterRtn;
				
			return ['success'=> true , 'error_message'=>'' , 'response'=>$response ];
		}catch (\Exception $e){
			\Yii::info(__FUNCTION__." exception:".print_r($e , true),"file");
			return ['success'=> false , 'error_message'=>$e->getMessage() , 'response'=>[] ];
		}
	}
	
	/**
	 * 获取回填中物流方式列表信息，返回所有物流方式。即上传运单号时敦煌支持的物流方式
	 */
	public function getShippingTypeList2 ($dhgate_uid){
		try {
			$beforeRtn = $this->beforeCallApi($dhgate_uid);
			if(!empty($beforeRtn))
				return $beforeRtn;
	
			$sysParams = array(
					"access_token"  => $this->access_token,
					"method"		=> "dh.shipping.typelist" ,
					"timestamp"		=> time() * 1000 ,
					"v"				=> "2.0"
			);
	
			$response = $this->call_dh_api($this->apiUrl , $sysParams);
	
			$afterRtn = $this->afterCallApi($dhgate_uid, $response);
			if(!empty($afterRtn))
				return $afterRtn;
	
			return ['success'=> true , 'error_message'=>'' , 'response'=>$response ];
		}catch (\Exception $e){
			\Yii::info(__FUNCTION__." exception:".print_r($e , true),"file");
			return ['success'=> false , 'error_message'=>$e->getMessage() , 'response'=>[] ];
		}
	}
	
##############################敦煌站内信相关接口#############################################
	/**
	 * 订单站内信发送
	 * 
	 * @param
	 * 	$dhgate_uid		//表saas_dhgate_user  字段dhgate_uid
		$orderid,		//订单号
		$content,		//站内信内容
	 */
	public function sendOrderMessage ($dhgate_uid, $orderid, $content){
		try {
			$beforeRtn = $this->beforeCallApi($dhgate_uid);
			if(!empty($beforeRtn))
				return $beforeRtn;
			
			//根据订单号查找buyerID
			$odOrderOne = OdOrder::find()->select('source_buyer_user_id')->where(['order_source_order_id' => $orderid])->one();
			if ($odOrderOne == null) 
				return ['success'=> false , 'error_message'=>'订单号有误。'];
				
			$sysParams = array(
					"access_token"  => $this->access_token,
					"method"		=> "dh.message.send" ,
					"timestamp"		=> time() * 1000 ,
					"v"				=> "2.0"
			);
			
			$appParams = array();
			
			$appParams["content"] = $content;
			$appParams["msgTitle"] = 'PO#'.$orderid;
			$appParams["param"] = $orderid;
			$appParams["reciverId"] = $odOrderOne->source_buyer_user_id;
			$appParams['sendMsgType'] = '2';
			
			$response = $this->call_dh_api($this->apiUrl , $sysParams + $appParams);
			
			$afterRtn = $this->afterCallApi($dhgate_uid, $response);
			if(!empty($afterRtn)){
				unset($afterRtn['response']);
				return $afterRtn;
			}
			
			if ($response['status']['code'] != "00000000"){
				$errorMessages = $response['status']['message'];
				
				$subErrorsArr = $response['status']['subErrors'];
				
				foreach ($subErrorsArr as $subErrors){
					$errorMessages .= $subErrors['message'];
				}
				
				return ['success'=> false , 'error_message'=>$errorMessages];
			}
			
			// 		//发送成功时 返回站内信ID:msgId 当返回为空时说明站内信发信失败
			// 		$msgId = $response['msgId'];  暂时没有需求返回
			
			return ['success'=> true , 'error_message'=>''];
		}catch (\Exception $e){
			\Yii::info(__FUNCTION__." exception:".print_r($e , true),"file");
			return ['success'=> false , 'error_message'=>$e->getMessage()];
		}
	}
	
	/**
	 * 获取站内信主题列表
	 */
	public function getMsgList ($dhgate_uid,$appParams){
		try {
			$beforeRtn = $this->beforeCallApi($dhgate_uid);
			if(!empty($beforeRtn))
				return $beforeRtn;
	
			$sysParams = array(
					"access_token"  => $this->access_token,
					"method"		=> "dh.message.list" ,
					"timestamp"		=> time() * 1000 ,
					"v"				=> "2.0"
			);
				
			$response = $this->call_dh_api($this->apiUrl , $sysParams + $appParams);
	
			$afterRtn = $this->afterCallApi($dhgate_uid, $response);
			if(!empty($afterRtn))
				return $afterRtn;
	
			return ['success'=> true , 'error_message'=>'' , 'response'=>$response ];
		}catch (\Exception $e){
			\Yii::info(__FUNCTION__." exception:".print_r($e , true),"file");
			return ['success'=> false , 'error_message'=>$e->getMessage() , 'response'=>[] ];
		}
	}
	
	
	/**
	 * 获取时间段内不同类型站内信数量接口
	 */
	public function getMsgTypeListCount ($dhgate_uid,$appParams){
		try {
			$beforeRtn = $this->beforeCallApi($dhgate_uid);
			if(!empty($beforeRtn))
				return $beforeRtn;
	
			$sysParams = array(
					"access_token"  => $this->access_token,
					"method"		=> "dh.message.count.list" ,
					"timestamp"		=> time() * 1000 ,
					"v"				=> "2.0"
			);
	
			$response = $this->call_dh_api($this->apiUrl , $sysParams + $appParams, $appParams);
	
			$afterRtn = $this->afterCallApi($dhgate_uid, $response);
			if(!empty($afterRtn))
				return $afterRtn;
	
			return ['success'=> true , 'error_message'=>'' , 'response'=>$response ];
		}catch (\Exception $e){
			\Yii::info(__FUNCTION__." exception:".print_r($e , true),"file");
			return ['success'=> false , 'error_message'=>$e->getMessage() , 'response'=>[] ];
		}
	}
	
	/**
	 * 获取一条站内信的详细回复内容信息
	 */
	public function getMsgDetails ($dhgate_uid,$appParams){
		try {
			$beforeRtn = $this->beforeCallApi($dhgate_uid);
			if(!empty($beforeRtn))
				return $beforeRtn;
	
			$sysParams = array(
					"access_token"  => $this->access_token,
					"method"		=> "dh.message.get" ,
					"timestamp"		=> time() * 1000 ,
					"v"				=> "2.0"
			);
	
			$response = $this->call_dh_api($this->apiUrl , $sysParams + $appParams, $appParams);
	
			$afterRtn = $this->afterCallApi($dhgate_uid, $response);
			if(!empty($afterRtn))
				return $afterRtn;
	
			return ['success'=> true , 'error_message'=>'' , 'response'=>$response ];
		}catch (\Exception $e){
			\Yii::info(__FUNCTION__." exception:".print_r($e , true),"file");
			return ['success'=> false , 'error_message'=>$e->getMessage() , 'response'=>[] ];
		}
	}
	
	/**
	 * 对站内信进行回复
	 */
	public function replyMessage ($dhgate_uid, $addi_info, $content){
		try {
			$beforeRtn = $this->beforeCallApi($dhgate_uid);
			if(!empty($beforeRtn))
				return $beforeRtn;
				
			$sysParams = array(
					"access_token"  => $this->access_token,
					"method"		=> "dh.message.info.reply" ,
					"timestamp"		=> time() * 1000 ,
					"v"				=> "2.0"
			);
			
			$appParams = array();
				
			$appParams["content"] = $content;
			$appParams["msgId"] = $addi_info['msgId'];
			$appParams["receiverId"] = $addi_info['recieverId'];
				
			$response = $this->call_dh_api($this->apiUrl , $sysParams + $appParams);
				
			$afterRtn = $this->afterCallApi($dhgate_uid, $response);
			if(!empty($afterRtn)){
				unset($afterRtn['response']);
				return $afterRtn;
			}
				
			if ($response['status']['code'] != "00000000"){
				$errorMessages = $response['status']['message'];
	
				$subErrorsArr = $response['status']['subErrors'];
	
				foreach ($subErrorsArr as $subErrors){
					$errorMessages .= $subErrors['message'];
				}
	
				return ['success'=> false , 'error_message'=>$errorMessages];
			}
				
			// 		//发送成功时 返回站内信ID:msgId 当返回为空时说明站内信发信失败
			// 		$msgId = $response['msgId'];  暂时没有需求返回
				
			return ['success'=> true , 'error_message'=>''];
		}catch (\Exception $e){
			\Yii::info(__FUNCTION__." exception:".print_r($e , true),"file");
			return ['success'=> false , 'error_message'=>$e->getMessage()];
		}
	}
	
}