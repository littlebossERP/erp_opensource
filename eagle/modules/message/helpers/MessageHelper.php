<?php
/*+----------------------------------------------------------------------
| 小老板
+----------------------------------------------------------------------
| Copyright (c) 2011 http://www.xiaolaoban.cn All rights reserved.
+----------------------------------------------------------------------
| Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
+----------------------------------------------------------------------
| Author: yzq lkh
+----------------------------------------------------------------------
| Create Date: 2015-5
+----------------------------------------------------------------------
 */
namespace eagle\modules\message\helpers;
use yii;
use yii\data\Pagination;
use eagle\models\SaasWishUser;
use eagle\modules\listing\models\WishApiQueue;
use eagle\modules\listing\models\WishFanben;
use eagle\modules\listing\models\WishFanbenVariance;
use eagle\modules\listing\models\WishOrder;
use eagle\modules\listing\helpers\WishProxyConnectHelper;
use eagle\modules\listing\models\WishOrderDetail;
use eagle\modules\util\helpers\OperationLogHelper;
use eagle\modules\util\helpers\StandardConst;
use eagle\modules\util\helpers\TranslateHelper;
use eagle\modules\util\helpers\GetControlData;
use yii\helpers\StringHelper;
use eagle\modules\message\models\MsgTemplate;
use eagle\modules\util\helpers\ConfigHelper;
use eagle\modules\message\models\Message;
use eagle\modules\tracking\models\Tracking;
use eagle\modules\tracking\helpers\TrackingHelper;
use yii\base\Exception;
use eagle\modules\message\models\AutoRoles;
use eagle\modules\app\apihelpers\AppApiHelper;
use eagle\models\sys\SysCountry;
use common\helpers\Helper_Array;
use eagle\modules\message\models\TicketSession;
use eagle\modules\order\models\OdOrder;
use eagle\modules\tracking\helpers\TrackingApiHelper;
use eagle\modules\message\apihelpers\MessageApiHelper;
use eagle\modules\message\apihelpers\MessageEbayApiHelper;
use eagle\modules\order\helpers\OrderTrackingMessageHelper;
use eagle\modules\util\helpers\TimeUtil;
use eagle\modules\util\helpers\SQLHelper;
use eagle\models\UserEdmQuota;
use eagle\modules\message\models\EdmSentHistory;
use eagle\modules\order\models\OrderRelation;
use eagle\modules\util\helpers\SysLogHelper;
use eagle\modules\order\models\OdOrderItem;
use eagle\models\OdOrderShipped;

/**
 * 
 +------------------------------------------------------------------------------
 * 刊登模块模板业务
 +------------------------------------------------------------------------------
 * @category	Order
 * @package		Helper/order
 * @subpackage  Exception
 * @author		fanjs
 +------------------------------------------------------------------------------
 */
class MessageHelper {
	static private $Insert_Message_data = [];
	
	private static $DUMP_OMS_DATA = '{"order_id":"00000002305","order_status":"500","order_manual_id":"0","pay_status":"1","shipping_status":"2","is_manual_order":"0","order_source":"ebay","order_type":"","order_source_order_id":"151449798817-120055065xxxx","order_source_site_id":"UK","selleruserid":"vipwitsionstore","saas_platform_user_id":"26","order_source_srn":"2205","customer_id":"1995","source_buyer_user_id":"brunoacoutinho","order_source_shipping_method":"UK_OtherCourierOrDeliveryInternational","order_source_create_time":"1422028677","subtotal":"9.34","shipping_cost":"0.00","discount_amount":"0.00","grand_total":"9.34","returned_total":"0.00","price_adjustment":"0.00","currency":"GBP","consignee":"Petter","consignee_postal_code":"2840-123","consignee_phone":"93722xxxx","consignee_email":"littleboss@gmail.com","consignee_company":"","consignee_country":"Portugal","consignee_country_code":"PT","consignee_city":"Tampa","consignee_province":"New York","consignee_district":"","consignee_county":"","consignee_address_line1":"Vasstrondvegen xxx","consignee_address_line2":"","consignee_address_line3":"","default_warehouse_id":"0","warehouse_type":"0","default_carrier_code":"","default_shipping_method_code":"","paid_time":"1422028678","delivery_time":"0","create_time":"1422260154","update_time":"1422260154","user_message":"","carrier_type":"0","is_feedback":"0","items":[{"order_item_id":"2394","order_id":"00000002305","order_source_srn":"2205","order_source_order_item_id":"2223","source_item_id":"151449798817","sku":"151449798817","product_name":"Cycling Bicycle Bike Pannier Rear Seat Bag Rack Trunk Shoulder Handbag New","photo_primary":"http:\/\/i.ebayimg.com\/00\/s\/MTAwMFgxMjAw\/z\/bO8AAOSwZkJUR2pV\/$_1.JPG?set_id=880000500F","shipping_price":"0.00","shipping_discount":"0.00","price":"9.34","promotion_discount":"0.00","ordered_quantity":"1","quantity":"1","sent_quantity":"1","packed_quantity":"1","returned_quantity":"0","invoice_requirement":"","buyer_selected_invoice_category":"","invoice_title":"","invoice_information":"","remark":null,"create_time":"1422029460","update_time":"1422029460","platform_sku":"CZEB14102405A","is_bundle":"0","bdsku":""}]}';
	
	 
	/**
	 +---------------------------------------------------------------------------------------------
	 * 根据 模版名字 获取模版内容
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param  $template_name 	string		模版名字
	 +---------------------------------------------------------------------------------------------
	 * @return array
	 * 					success  boolean  调用 是否成功
	 * 					message  string   调用结果说明
	 * 					template array	      模版格式
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2015/5/9				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function getMessageTemplate($template_name){
		try {
				//1.init return
				$result ['success'] = true;
				$result ['message'] = '';
				$result ['template'] = [];
				
				
				//2.get template data by name
				 
				$result ['template']  = MsgTemplate::find()
				->andWhere(['name'=>$template_name])
				->asArray()
				->one();
				
				
			} catch (Exception $e) {
				$result ['success'] = false;
				$result ['message'] = $e->getMessage();
				$result ['template'] = [];
			}
			return $result;
	}//end of getMessageTemplate
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 获取用户最近 一次的模版格式 
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param  na
	 +---------------------------------------------------------------------------------------------
	 * @return array
	 * 					success  		boolean  调用 是否成功
	 * 					message  		string   调用结果说明
	 * 					templateName 	string   模版名称
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2015/5/9				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static function getLastTemplateName(){
		try {
			//1.init return
			$result ['success'] = true;
			$result ['message'] = '';
			$result ['template'] = [];
		
		
			/*
			 * 找随便一个物流号，
			 * 看他的 platform + sellerid + 状态，如果有这个中组合记录上一次用模板，默认使用上一次的。
			 * 如果上面的历史没有命中，找   platform + 状态
			 * 如果上面的历史没有命中，找   sellerid + 状态
			 * 如果上面的历史没有命中，找   状态
			 */
			//@todo get default template 
			
			//2.get last template name
			ConfigHelper::getConfig($path);
		
		
		} catch (Exception $e) {
			$result ['success'] = false;
			$result ['message'] = $e->getMessage();
			$result ['template'] = [];
		}
		return $result;
	}//end of getLastTemplateName
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 修改保存 用户对这个template的变更
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
	 * @author		lkh		2015/5/9				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	public static function saveMessageTemplate($template){
		try {
			if (!empty($template['id'])){
				//check create or update
				$model = MsgTemplate::findOne(['id'=>$template['id']]);
			}else{
				$existTemplate = MsgTemplate::find()->andWhere(['name'=>$template['name']])->count();
				if (!empty($existTemplate)){
					return ['success'=>false,'message'=>'模板名称重复!'];
				}
			}
			
			if (empty($model)){
				//model not found , then create a new Template
				$model = new MsgTemplate();
			}
			
			if (!empty($model->addi_info))
				$addi_info = json_decode($model->addi_info,true);
			else 
				$addi_info = [];
			
			if (!empty($template['body'])){
				if (stripos( $template['body'],'买家查看包裹追踪及商品推荐链接')){
					if(!empty($template['recom_prod_group'])){
						$addi_info['recom_prod'] = 'Y';
						$addi_info['recom_prod_group'] = $template['recom_prod_group'];
					}
				}else{
					if (isset($addi_info['recom_prod'])) unset($addi_info['recom_prod']);
				}
			}
			
			if (empty($template['addi_info'])){
				if(is_array($template['addi_info']))
					$template['addi_info'] = json_encode($addi_info);
			}else{
				
				if (is_string($template['addi_info'])) $template['addi_info'] = json_decode($template['addi_info'],true);
				
				if (is_array($template['addi_info']))
					$template['addi_info'] = array_merge($addi_info,$template['addi_info']);
			}
			
			if (is_array($template['addi_info'] )) $template['addi_info'] = json_encode($template['addi_info'] );
			
			if (isset($addi_info['recom_prod'])) unset($addi_info['recom_prod']);
			if (isset($addi_info['recom_prod_group'])) unset($addi_info['recom_prod_group']);
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
			
		} catch (Exception $e) {
			\Yii::info(['tracking',__CLASS__,__FUNCTION__,'Online',$e->getMessage() ],"edb\global");
			$result ['success'] = false;
			$result ['message'] = $e->getMessage();
		}
		return $result;
	}//end of saveMessageTemplate
	
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 列举出所有模版数据
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param  na
	 +---------------------------------------------------------------------------------------------
	 * @return array
	 * 					success  		boolean  调用 是否成功
	 * 					message  		string   调用结果说明
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2015/5/9				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function listAllTemplate(){
		self::createDefaultTemplateIfNotExists();
		$result = MsgTemplate::find()->asArray()->all();
		return $result;
	}//end of listAllTemplate
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 把该msg 通过变量匹配，替换后的最终content结果以及标题，写到表 user_x 里面的 cs_message
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param  $msg  与 cs_message 的结构对应
	 +---------------------------------------------------------------------------------------------
	 * @return array
	 * 					success  		boolean  调用 是否成功
	 * 					message  		string   调用结果说明
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2015/5/12				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function insertMessage($msg){
		try {
			$model = new Message();
			$model->attributes = $msg;
				
			if (!$model->save()){
				\Yii::info(['message',__CLASS__,__FUNCTION__,'Online', json_encode($model->errors) ],"edb\global");
				$result ['success'] = false;
				$result ['message'] = $model->errors;
			}else{
				$result ['success'] = true;
				$result ['message'] = "保存成功";
			}
				
		} catch (Exception $e) {
			\Yii::info(['message',__CLASS__,__FUNCTION__,'Online',$e->getMessage() ],"edb\global");
			$result ['success'] = false;
			$result ['message'] = $e->getMessage();
		}
		return $result;
		
	}//end of insertMessage
	
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 封装 发送站内信的 logic
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param  	$template				array		template表 的数据 格式
	 * 			$trackNoList			array		需要发送的物流号
	 * 			$addi_params			array		附加参数
	 * 			$isUpdateTemplate		boolean		是否更新/保存 template
	 +---------------------------------------------------------------------------------------------
	 * @return array
	 * 					success  		boolean  调用 是否成功
	 * 					message  		string   调用结果说明
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2015/5/12				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function sendStationMessage($template , $trackNoList , $addi_params , $isUpdateTemplate=true, $status=''){
		global $SourceBuyerID;
		//update template
		if ($isUpdateTemplate){
			$result = self::saveMessageTemplate($template);
			
			//template 保存失败后 返回失败的原因
			if ($result['success'] == false) return $result;
		}
		
		//resend message 
		if (!empty($addi_params['op_method']) && !empty($addi_params['msg_id'])){
			// 重发 站内信需要 把原来 发信的内容 删除
			if (strtolower($addi_params['op_method']) == 'resend'){
				Message::deleteAll(['id'=>$addi_params['msg_id'] , 'status'=>'F']);
			}
		}
		
		//overdue
		/*
		if (!empty($result['template_id']) && !empty($addi_params['path'])){
			ConfigHelper::setConfig($addi_params['path'], json_encode($result['template_id']));
		}
		*/
		/*
		// 定义需要替换的字段名
		$FieldNameMapping = [
		'收件人名称' => 'source_buyer_user_id',
		'收件人国家'  => 'consignee_county',
		'收件人地址，包含城市'  => 'consignee_city',
		'收件人邮编'  => 'consignee_postal_code',
		'收件人电话'  => 'consignee_phone',
		'平台订单号'  => 'order_source_order_id',
		'订单金额'  => 'grand_total',
		'订单物品列表(商品sku，名称，数量，单价)'  => 'item',
		'包裹物流号'  => 'track_no',
		'包裹递送物流商'  => 'ship_by',
		];
		*/
		$FieldNameMapping = [];
		$msg = [];
		$QueueData = [];
		$Validation = [];
		$puid = \Yii::$app->subdb->getCurrentPuid();
		$now_str = date('Y-m-d H:i:s');
		$EdmQueueData = [];//插入邮件发送队列的数据
		
		//消息发送indicate
		$notified_indicates = TrackingHelper::getNotifiedFieldNameByStatus($status);
		$pos = empty($status)?'no_pos':$notified_indicates;
		$orderNoList = [];
		$this_notification_md5_track = [];//本次track发送的消息的md5列表
		$this_notification_md5_order = [];//本次order发送的消息的md5列表
		
		//循环所以物流单号进行发送站内信
		foreach($trackNoList as $track_no){
			//获取 tracking 数据 用来代换数据
			$tmpTracking = Tracking::find()->andWhere(['track_no'=>$track_no])->One();
			if(empty($tmpTracking))
				continue;
			
			$odOrders = OdOrder::find()->where(['order_source_order_id'=>$tmpTracking->order_id])->asArray()->all();
			if(count($odOrders)>1){//订单表记录>1，则可能是合并订单,合并订单基于单个订单的模式做多次	//2016-11-29 lzhl
				//echo "test liang 02";
				$smOrderId='';
				$orders=[];
				foreach ($odOrders as $od){
					if($od['order_relation']=='sm')
						$smOrderId = $od['order_id'];
				}
				if(!empty($smOrderId)){
					$fms = OrderRelation::find()->where(['son_orderid'=>$smOrderId,'type'=>'merge'])->asArray()->all();
					$fmOrderIds = [];
					foreach ($fms as $fm){
						$fmOrderIds[] = $fm['father_orderid'];
					}
						
					$orders =  OdOrder::find()->where(['order_id'=>$fmOrderIds])->all();
				}
				foreach ($orders as $order){
					//上次发送的消息的md5
					$order_addi = empty($order->addi_info)?[]:json_decode($order->addi_info,true);
					if(!empty($order_addi['last_notification_md5'])){
						if(empty($status) or is_array($notified_indicates) ){
							$last_md5 = empty($order_addi['last_notification_md5']['no_pos'])?'':$order_addi['last_notification_md5']['no_pos'];
						}else{
							$last_md5 = empty($order_addi['last_notification_md5'][$notified_indicates])?'':$order_addi['last_notification_md5'][$notified_indicates];
						}
					}
					
					//替换  相关的
					$subject = $template['subject'];
					$body = $template['body'];
					try{
						self::replaceTemplateDataByOms($subject, $body, $order->order_source_order_id,'fm',$tmpTracking->order_id);
					}catch (\Exception $e){
						continue;
					}
					if (empty($BuyerID)) $BuyerID = '';
					
					//对比md5值，和上次同类型内容md5一样的话就跳过;
					$new_md5 = md5($body);
					if(!empty($last_md5) && $last_md5==$new_md5){
						$Validation[$track_no] = '本次发送的消息类型和内容和上次同类型发送的内容一样，不再发送！';
						break;
					}
					
					//如果是不支持发送站内信，而支持发邮件的平台
					if(in_array($order->order_source , ['cdiscount','amazon',])){
						$tmp_data = self::_getEdmQueueDataForSpecificPlatformOrder($puid, $order, $subject, $body);
						if(!empty($tmp_data['success']) && !empty($tmp_data['queueData'])){
							$EdmQueueData[] = $tmp_data['queueData'];
							$this_notification_md5_track[$track_no] = $new_md5;
							$this_notification_md5_order[$order->order_id] = $new_md5;
						}else{
							if(!empty($tmp_data['Validation'])){
								$Validation[$track_no] = $tmp_data['Validation'];
							}
						}
						continue;
					}
					
					$msg ['order_id']  = $order->order_source_order_id;
					$orderNoList[] = $order->order_source_order_id;
					$addi_info ['tail'] =  MessageBGJHelper::make17TrackMessageTail($puid,$msg ['order_id'] );
					$msg ['cpur_id']  = $puid; //谁说这句话的，user id，如果是客户来的
					$msg ['create_time']  = $now_str; // 说话时间
					$msg ['subject'] = $subject; // message标题
					$msg ['content']  = $body."\r\n".$addi_info ['tail']; // msg 内容
					$msg ['platform']  = $tmpTracking->platform; //'平台：''ebay'',''aliexpress'',''wish'',''amazon'
					
					//$addi_info ['tail'] = "test tail";
					$msg ['addi_info']  = json_encode($addi_info);
					
					$aData = ['puid'=>$msg ['cpur_id'] ,
					'create_time'=>$msg ['create_time'] ,
					'subject'=>$msg ['subject'],
					'content'=>$msg ['content'],
					'platform'=>$msg ['platform'],
					'order_id'=>$msg ['order_id'] ,
					'buyer_id'=>$SourceBuyerID,
					];
					
					$aData['seller_id'] = $tmpTracking->seller_id;
					$QueueData[] = $aData;
					unset($BuyerID);
					//insert message
					/*
					 khcomment 20150525 不使用逐条保存的方法 , 改为批量保存
					$result = self::insertMessage($msg);
					if ($result['success'] == false) return $result;
					*/
					
					/*
					 * 以下2种情况返回错误信息
					* 1）ebay msg， subject 不大于100 长度
					* 2）ebay msg，content 不大于 2000 长度
					*/
					if (in_array(strtolower($msg ['platform']), ['ebay'])){
						if (strlen($msg ['subject']) > 100){
							$Validation[$track_no]['subject'] = $msg ['subject'];
						}
							
						if (strlen($msg ['content']) > 2000 ){
							$Validation[$track_no]['content'] = true;
						}
					}
					if (empty($Validation[$track_no])){
						self::putToMessageData($msg);
						$this_notification_md5_track[$track_no] = $new_md5;
						$this_notification_md5_order[$order->order_id] = $new_md5;
					}
				}
			}else{
				$order = OdOrder::find()->where(['order_source_order_id'=>$tmpTracking->order_id,'order_source'=>$tmpTracking->platform])->one();
				if(empty($order)){
					$Validation[$track_no] = '本次发送的消息的关联订单信息缺失或订单时间太旧，操作失败！';
					break;
				}
				//上次发送的消息的md5
				$order_addi = empty($order->addi_info)?[]:json_decode($order->addi_info,true);
				if(!empty($order_addi['last_notification_md5'])){
					if(empty($status) or is_array($notified_indicates) ){
						$last_md5 = empty($order_addi['last_notification_md5']['no_pos'])?'':$order_addi['last_notification_md5']['no_pos'];
					}else{
						$last_md5 = empty($order_addi['last_notification_md5'][$notified_indicates])?'':$order_addi['last_notification_md5'][$notified_indicates];
					}
				}
				
				//替换  相关的
				$subject = $template['subject'];
				$body = $template['body'];
				
				self::replaceTemplateData($subject,$body,$track_no);
				if (empty($BuyerID)) $BuyerID = '';
				
				//对比md5值，和上次同类型内容md5一样的话就跳过;
				$new_md5 = md5($body);
				if(!empty($last_md5) && $last_md5==$new_md5){
					$Validation[$track_no] = '本次发送的消息类型和内容和上次同类型发送的内容一样，不再发送！';
					break;
				}
				
				//如果是不支持发送站内信，而支持发邮件的平台
				if(in_array($tmpTracking->platform , ['cdiscount','amazon',])){
					if(empty($order))
						continue;
					$tmp_data = self::_getEdmQueueDataForSpecificPlatformOrder($puid, $order, $subject, $body);
					if(!empty($tmp_data['success']) && !empty($tmp_data['queueData'])){
						$EdmQueueData[] = $tmp_data['queueData'];
						$this_notification_md5_track[$track_no] = $new_md5;
						$this_notification_md5_order[$order->order_id] = $new_md5;
					}else{
						if(!empty($tmp_data['Validation'])){
							$Validation[$track_no] = $tmp_data['Validation'];
						}
					}
					continue;
				}
				
				$msg ['order_id']  = $tmpTracking->order_id;
				if(!empty($tmpTracking->order_id)) $orderNoList[] = $tmpTracking->order_id;
				$addi_info ['tail'] =  MessageBGJHelper::make17TrackMessageTail($puid,$msg ['order_id'] );
				$msg ['cpur_id']  = $puid; //谁说这句话的，user id，如果是客户来的
				$msg ['create_time']  = $now_str; // 说话时间
				$msg ['subject'] = $subject; // message标题
				$msg ['content']  = $body."\r\n".$addi_info ['tail']; // msg 内容
				$msg ['platform']  = $tmpTracking->platform; //'平台：''ebay'',''aliexpress'',''wish'',''amazon'
				
				//$addi_info ['tail'] = "test tail";
				$msg ['addi_info']  = json_encode($addi_info);
				
				$aData = ['puid'=>$msg ['cpur_id'] ,
				'create_time'=>$msg ['create_time'] ,
				'subject'=>$msg ['subject'],
				'content'=>$msg ['content'],
				'platform'=>$msg ['platform'],
				'order_id'=>$msg ['order_id'] ,
				'buyer_id'=>$SourceBuyerID,
				];
				
				$aData['seller_id'] = $tmpTracking->seller_id;
				$QueueData[] = $aData;
				unset($BuyerID);
				//insert message
				/*
				khcomment 20150525 不使用逐条保存的方法 , 改为批量保存
				$result = self::insertMessage($msg);
				if ($result['success'] == false) return $result;
				 */
				
				/*
				 * 以下2种情况返回错误信息
				 * 1）ebay msg， subject 不大于100 长度
				 * 2）ebay msg，content 不大于 2000 长度
				 */
				if (in_array(strtolower($msg ['platform']), ['ebay'])){
					if (strlen($msg ['subject']) > 100){
						$Validation[$track_no]['subject'] = $msg ['subject'];
					}
					
					if (strlen($msg ['content']) > 2000 ){
						$Validation[$track_no]['content'] = true;
					}
				}
				
				if (empty($Validation[$track_no])){
					self::putToMessageData($msg);
					$this_notification_md5_track[$track_no] = $new_md5;
					$this_notification_md5_order[$order->order_id] = $new_md5;
				}
			}	
			
		}//end of each track no 
		//var_dump($EdmQueueData);
		if (empty($Validation)){
			//有需要插入edm队列的数据
			if(!empty($EdmQueueData)){
				//为了避免超额发，预扣quota，到时发送失败再将quota退回去
				$ch = EdmHelper::EdmQuotaChange($puid, count($EdmQueueData), '-');
				if(!$ch['success']){
					return ['success'=>false,'message'=>$ch['message']];
				}
				//扣除成功后，写入队列
				$ret = SQLHelper::groupInsertToDb("edm_email_send_queue", $EdmQueueData, 'db_queue2');
			}
			$order_msg_id_map = self::postMessageDataToDb();
		}else{
			$order_msg_id_map = [];
			$result['success'] = false;
			$result['message'] = '格式错误';
			$result['validation'] = $Validation;
			return $result;
		}
		
		// put api queue
		foreach($QueueData as $aData ){
			//set Queuer buffer 
			if (!empty($order_msg_id_map[$aData['order_id']]))
				$aData['msg_id'] = $order_msg_id_map[$aData['order_id']];
			
			MessageBGJHelper::putToMessageQueueBuffer($aData);
		}
		
		MessageBGJHelper::postMessageApiQueueBufferToDb();
		
		
		//update tracking
		$NSM = TrackingHelper::getNotifiedFieldNameByStatus($status);
		
		if(is_array($NSM)){
			foreach($NSM as $status=>$fieldName){
				//Tracking::updateAll([$fieldName=>'Y'], ['track_no'=>$trackNoList,'status'=>$status]);
				Tracking::updateAll([$fieldName=>'Y'], ['track_no'=>$trackNoList]);
				OdOrder::updateAll([$fieldName=>'Y'], ['order_source_order_id'=>$orderNoList]);
			}
		}else{
			OdOrder::updateAll([$NSM=>'Y'], ['order_source_order_id'=>$orderNoList]);
			Tracking::updateAll([$NSM=>'Y'], ['track_no'=>$trackNoList]);
		}
		//update md5
		foreach ($this_notification_md5_track as $track_no=>$md5){
			$tracks = Tracking::find()->andWhere(['track_no'=>$track_no])->all();
			foreach ($tracks as $track){
				$addi_info = $track->addi_info;
				$addi_info = empty($addi_info)?[]:json_decode($addi_info,true);
				$addi_info['last_notification_md5'][$pos] = $md5;
				$track->addi_info = json_encode($addi_info);
				$track->save();
			}
		}
		foreach ($this_notification_md5_order as $order_id=>$md5){
			$order = OdOrder::findOne($order_id);
			$addi_info = $order->addi_info;
			$addi_info = empty($addi_info)?[]:json_decode($addi_info,true);
			$addi_info['last_notification_md5'][$pos] = $md5;
			$order->addi_info = json_encode($addi_info);
			$order->save();
			
		}
		
		if (!empty($template['body'])){
			//是否调用 turnOnUserFunction 当前发送并发信内容存在 [买家查看包裹追踪及商品推荐链接] 为true
			if (stripos( $template['body'],'买家查看包裹追踪及商品推荐链接')){
				//call turnOnUserFunction
				AppApiHelper::turnOnUserFunction($puid, 'tracker_recommend');
			}else{
				
			}
		}
		
		$result['success'] = true;
		$result['message'] = '发送成功';
		return $result;
		
	}//end of sendStationMessage
	
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 封装 发送站内信的 logic---oms
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param  	$template				array		template表 的数据 格式
	 * 			$orderIdList			array		需要发送的订单
	 * 			$addi_params			array		附加参数
	 * 			$isUpdateTemplate		boolean		是否更新/保存 template
	 +---------------------------------------------------------------------------------------------
	 * @return array
	 * 					success  		boolean  调用 是否成功
	 * 					message  		string   调用结果说明
	 +---------------------------------------------------------------------------------------------
	 * @author		lzhl		2016/7/18			初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function sendStationMessageByOms($template , $orderIdList , $addi_params , $isUpdateTemplate=true,$status=''){
		$result['success'] = true;
		$result['message'] = '';
		SysLogHelper::SysLog_Create('OdLtMessage', __CLASS__, __FUNCTION__,'log',"template:".print_r($template,true).";orderIdList:".print_r($orderIdList,true) );
		try{
			//update template
			if ($isUpdateTemplate){
				$result = self::saveMessageTemplate($template);
					
				//template 保存失败后 返回失败的原因
				if ($result['success'] == false) return $result;
			}
			
			//resend message
			if (!empty($addi_params['op_method']) && !empty($addi_params['msg_id'])){
				// 重发 站内信需要 把原来 发信的内容 删除
				if (strtolower($addi_params['op_method']) == 'resend'){
					Message::deleteAll(['id'=>$addi_params['msg_id'] , 'status'=>'F']);
				}
			}
			//overdue
	
			/*
			// 定义需要替换的字段名
			$FieldNameMapping = [
			'收件人名称' => 'source_buyer_user_id',
			'收件人国家'  => 'consignee_county',
			'收件人地址，包含城市'  => 'consignee_city',
			'收件人邮编'  => 'consignee_postal_code',
			'收件人电话'  => 'consignee_phone',
			'平台订单号'  => 'order_source_order_id',
			'订单金额'  => 'grand_total',
			'订单物品列表(商品sku，名称，数量，单价)'  => 'item',
			'包裹物流号'  => 'track_no',
			'包裹递送物流商'  => 'ship_by',
			];
			*/
			$FieldNameMapping = [];
			$msg = [];
			$QueueData = [];//插入message queue的数据
			$EdmQueueData = [];//插入邮件发送队列的数据
			$Validation = [];
			$puid = \Yii::$app->subdb->getCurrentPuid();
			$now_str = date('Y-m-d H:i:s');
			
			$notified_indicates = TrackingHelper::getNotifiedFieldNameByStatus($status);
			$pos = empty($status)?'no_pos':$notified_indicates;
			
			$this_notification_md5_order = [];//本次order发送的消息的md5列表
			
			$transaction = \Yii::$app->get('subdb')->beginTransaction();//主要为ebay创建新session服务
			
			$orderNoList = [];
			//循环所有订单号进行发送站内信
			foreach($orderIdList as $order_id){
				//获取 order 数据 用来代换数据
				$odOrder = OdOrder::find()->where(['order_id'=>$order_id])->One();
				
				//合并过的订单，需要把所有子单找出来进行发送
				$sm_order_no = '';
				$order_relation='normal';
				$orders=[];
				if(!empty($odOrder->order_relation) && $odOrder->order_relation=='sm'){
					$sm_order_no = $odOrder->order_source_order_id;
					$orders = [];
					$fms = OrderRelation::find()->where(['son_orderid'=>$odOrder->order_id,'type'=>'merge'])->asArray()->all();
					$fmOrderIds = [];
					foreach ($fms as $fm){
						$fmOrderIds[] = $fm['father_orderid'];
					}
					
					$orders =  OdOrder::find()->where(['order_id'=>$fmOrderIds])->all();
				}else{
					$orders[] = $odOrder;
				}
				foreach ($orders as $order){
					$subject = $template['subject'];
					$body = $template['body'];
					//替换  相关的
					$order_no = $order->order_source_order_id;
					
					//上次发送的消息的md5
					$order_addi = empty($order->addi_info)?[]:json_decode($order->addi_info,true);
					if(!empty($order_addi['last_notification_md5'])){
						if(empty($status) or is_array($notified_indicates) ){
							$last_md5 = empty($order_addi['last_notification_md5']['no_pos'])?'':$order_addi['last_notification_md5']['no_pos'];
						}else{
							$last_md5 = empty($order_addi['last_notification_md5'][$notified_indicates])?'':$order_addi['last_notification_md5'][$notified_indicates];
						}
					}
					
					$orderNoList[] = $order_no;
					//如果是合并订单的子单，需要记录该类型，要做额外处理
					if(!empty($sm_order_no)) 
						$order_relation = 'fm';
					try{
						self::replaceTemplateDataByOms($subject,$body,$order_no,$order_relation,$sm_order_no);
					}catch (\Exception $e){
						continue;
					}
					if(empty($body))
						$Validation[$order_no]['body'] = true;
					
					//对比md5值，和上次同类型内容md5一样的话就跳过;
					$new_md5 = md5($body);
					if(!empty($last_md5) && $last_md5==$new_md5){
						$Validation[$order_no] = '本次发送的消息类型和内容和上次同类型发送的内容一样，不再发送！';
						break;
					}
					
					if (in_array(strtolower($order->order_source), ['ebay'])){
						if (isset($subject) && strlen($subject) > 100){
							$Validation[$order_no]['subject'] = $subject;
						}
					
						if (strlen($body) > 2000 ){
							$Validation[$order_no]['content'] = true;
						}
					}
					
					//如果是不支持发送站内信，而支持发邮件的平台
					if(in_array($order->order_source , ['cdiscount','amazon',])){
						$history = [];
						$queue = [];
						//Validation start
						//邮箱格式验证
						if(preg_match('/^\w+([-+.]\w+)*@\w+([-.]\w+)*\.\w+([-.]\w+)*$/i',$order->consignee_email)){
							$history['send_to'] = $queue['send_to'] = $order->consignee_email;
						}else{
							$Validation[$order_no]['to_email'] =  $queue['send_to']= $order->consignee_email;
							continue;
						}
						$store_addi_key = '';
						$storeMailInfo = OrderTrackingMessageHelper::getPlatformStoreMatchMailAddress($puid, $order->order_source, $order->selleruserid,$store_addi_key);
						
						if(empty($storeMailInfo['mail_address'])){
							$Validation[$order_no]['from_email'] = true;
							continue;
						}else 
							$history['send_from'] = $queue['send_from'] = $storeMailInfo['mail_address'];
						
						//Validation end
						
						$queue['puid'] = $puid;
						$history['act_name'] = $queue['act_name'] = 'EDM/'.$order->order_source.'_OMS';
						$history['module_key'] = (string)$order->order_id;
						$history['subject'] = $subject;
						$history['status'] = $queue['status'] = 0;
						$history['create_time'] = $queue['create_time'] = TimeUtil::getNow();
						$addi_info= [];
						$addi_info['subject']= $subject;
						
						$body = str_replace("\r\n", '<br>', $body);
						
						$addi_info['body'] = $body;
						$addi_info['from_name'] = empty($storeMailInfo['name'])?'':$storeMailInfo['name'];
						$history['addi_info'] = $queue['addi_info'] = json_encode($addi_info);
						
						$history_modle = new EdmSentHistory();
						$history_modle->setAttributes($history);
						if(!$history_modle->save()){
							$transaction->rollBack();
							return ['success'=>false,'message'=>print_r($history_modle->getErrors())];
						}
						$queue['history_id'] = $history_modle->id;
						
						$this_notification_md5_order[$order->order_id] = $new_md5;
						$EdmQueueData[] = $queue;
						continue;
					}
					
					//调用客服模块发送
					$ticket_id='';
		            $existingTicket=TicketSession::find()->where(['related_id'=>$order_no])->one();
		            if(empty($existingTicket) && $order->order_source=='ebay'){
		            	$ticketCheck = MessageEbayApiHelper::saveNewTicketSession($order, $subject, $body);
		            	if(!empty($ticketCheck['success']) && !empty($ticketCheck['session'])){
		            		$ticket_id = $ticketCheck['session']->ticket_id;
		            	}else{
		            		$result['success'] = false;
		            		$result['message'] .= $ticketCheck['err_msg'];
		            	}
		            }
		            if(empty($ticket_id) && !empty($existingTicket)){
		            	$ticket_id = $existingTicket->ticket_id;
		            }
		            
					$message=array();
					$message['platform_source']=$order->order_source;
					$message['msgType']=2;//站内信类型 1--订单，2--站内信 必须
					if($message['platform_source']=='aliexpress')
						$message['msgType']=1;
					$message['puid']=$puid;
					$message['contents']=$body;
					$message['ticket_id']= empty($ticket_id)?'':$ticket_id;
					$message['order_id']=$order_no;
					$message['orderId']=$order_no;
					$message['item_id']='';
					
					//insert message
					/*
					 * 以下2种情况返回错误信息
					* 1）ebay msg， subject 不大于100 长度
					* 2）ebay msg，content 不大于 2000 长度
					*/
					
					$this_notification_md5_order[$order->order_id] = $new_md5;
					$QueueData[] = $message;
				}
			}//end of each track no
			
			if(empty($result['success'])){
				$transaction->rollBack();
				return $result;
			}
			
			if (!empty($Validation)){
				//$order_msg_id_map = [];
				$result['success'] = false;
				$result['message'] = '格式错误';
				$result['validation'] = $Validation;
				$transaction->rollBack();
				return $result;
			}
			
			
			
			// put api queue
			foreach($QueueData as $aData ){
				$rtn = MessageApiHelper::sendMsgToPlatform($aData,$changeUserDb=false);
				if(empty($rtn['success'])){
					$result['success'] = false;
					$result['message'] .= $rtn['error'];
				}
			}
			if(empty($result['success'])){
				return $result;
			}
			
			if(!empty($EdmQueueData)){
				//为了避免超额发，预扣quota，到时发送失败再将quota退回去
				$ch = EdmHelper::EdmQuotaChange($puid, count($EdmQueueData), '-');
				if(!$ch['success']){
					$transaction->rollBack();
					return ['success'=>false,'message'=>$ch['message']];
				}
				
				//扣除成功后，写入队列
				$ret = SQLHelper::groupInsertToDb("edm_email_send_queue", $EdmQueueData, 'db_queue2');
				
			}
			
			$transaction->commit();
			
			//update tracking
			$NSM = TrackingHelper::getNotifiedFieldNameByStatus($status);
		
			if(is_array($NSM)){
				foreach($NSM as $state=>$fieldName){
					OdOrder::updateAll([$fieldName=>'Y'], ['order_source_order_id'=>$orderNoList]);
					//Tracking::updateAll([$fieldName=>'Y'], ['order_id'=>$orderNoList,'status'=>$state]);
					Tracking::updateAll([$fieldName=>'Y'], ['order_id'=>$orderNoList]);
				}
			}else{
				OdOrder::updateAll([$NSM=>'Y'], ['order_source_order_id'=>$orderNoList]);
				Tracking::updateAll([$NSM=>'Y'], ['order_id'=>$orderNoList]);
			}
			
			foreach ($this_notification_md5_order as $order_id=>$md5){
				$order = OdOrder::findOne($order_id);
				$addi_info = $order->addi_info;
				$addi_info = empty($addi_info)?[]:json_decode($addi_info,true);
				$addi_info['last_notification_md5'][$pos] = $md5;
				$order->addi_info = json_encode($addi_info);
				$order->save();
					
			}
			
			if (!empty($template['body'])){
				//是否调用 turnOnUserFunction 当前发送并发信内容存在 [买家查看包裹追踪及商品推荐链接] 为true
				if (stripos( $template['body'],'买家查看包裹追踪及商品推荐链接')){
					//call turnOnUserFunction
					AppApiHelper::turnOnUserFunction($puid, 'tracker_recommend');
				}else{
		
				}
			}
			$result['success'] = true;
			$result['message'] = '发送成功';
			return $result;
		}catch (\Exception $e) {
			return ['success'=>false,'message'=>$e->getMessage()];
		}
	}//end of sendStationMessage
	
	/**
	 * 对于不能发站内信，而可以发站内邮件的平台，发送二次营销通知时，通过插入EDM队列来发送
	 * @param 	$puid		int
	 * @param 	$order		model		order model
	 * @param 	$subject	string		标题
	 * @param 	$body		string		内容
	 */
	public static function _getEdmQueueDataForSpecificPlatformOrder($puid,$order,$subject,$body,$app='tracker'){
		$history = [];
		$queueData = [];
		$Validation = [];
		//邮箱格式验证
		if(preg_match('/^\w+([-+.]\w+)*@\w+([-.]\w+)*\.\w+([-.]\w+)*$/i',$order->consignee_email)){
			$history['send_to'] = $queueData['send_to'] = $order->consignee_email;
		}else{
			$Validation['to_email'] =  $queueData['send_to']= $order->consignee_email;
			return ['success'=>false,'message'=>'','Validation'=>$Validation,'queueData'=>$queueData];
		}
		$store_addi_key = '';
		$storeMailInfo = OrderTrackingMessageHelper::getPlatformStoreMatchMailAddress($puid, $order->order_source, $order->selleruserid,$store_addi_key);
		if(empty($storeMailInfo['mail_address'])){
			$Validation['from_email'] = true;
			return ['success'=>false,'message'=>'','Validation'=>$Validation,'queueData'=>$queueData];
		}else
			$history['send_from'] = $queueData['send_from'] = $storeMailInfo['mail_address'];
		
		//Validation end
		
		$queueData['puid'] = $puid;
		$history['act_name'] = $queueData['act_name'] = ($app=='tracker')?'EDM/'.$app:'EDM/'.$order->order_source.'_'.$app;
		$history['module_key'] = (string)$order->order_id;
		$history['subject'] = $subject;
		$history['status'] = $queueData['status'] = 0;
		$history['create_time'] = $queueData['create_time'] = TimeUtil::getNow();
		$addi_info= [];
		$addi_info['subject']= $subject;
		
		$body = str_replace("\r\n", '<br>', $body);
		
		$addi_info['body'] = $body;
		$addi_info['from_name'] = empty($storeMailInfo['name'])?'':$storeMailInfo['name'];
		$history['addi_info'] = $queueData['addi_info'] = json_encode($addi_info);
		
		$history_modle = new EdmSentHistory();
		$history_modle->setAttributes($history);
		if(!$history_modle->save()){
			return ['success'=>false,'message'=>print_r($history_modle->getErrors()),'Validation'=>$Validation,'queueData'=>$queueData];
		}
		$queueData['history_id'] = $history_modle->id;
		return  ['success'=>true,'message'=>'','Validation'=>$Validation,'queueData'=>$queueData];
	}
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 根据 参数  物流号 替换 主题 , 模块的内容 指定的 固定变量
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param  	$subject			邮件主题
	 * 			$template			邮件内容
	 * 			$track_no			数据来源物流号
	 +---------------------------------------------------------------------------------------------
	 * @return na
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2015/5/12				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function replaceTemplateData(&$subject , &$template , $track_no ){
		global $SourceBuyerID;
		$SourceBuyerID = '';//初始化
		// 定义需要替换的字段名
		$FieldNameMapping = [
		'收件人名称' => 'consignee',
		'收件人国家'  => 'consignee_county',
		'收件人地址，包含城市'  => 'consignee_address_lines',
		'收件人邮编'  => 'consignee_postal_code',
		'收件人电话'  => 'consignee_phone',
		'平台订单号'  => 'order_source_order_id',
		'ebay订单SRN' => 'order_source_srn',
		'订单金额'  => 'grand_total',
		'订单物品列表(商品sku，名称，数量，单价)'  => 'items_list',
		'ebay订单Item Id' =>'ebay_items_list',
		'包裹物流号'  => 'track_no',
		'包裹递送物流商'  => 'ship_by',
		'买家查看包裹追踪及商品推荐链接'=> 'query_url'
		];
		
		if ($track_no == "demodata01"){
			$tmpTracking = '{"id":"735","seller_id":"Wish\u8881\u8d85","order_id":"271586578303-1473512808017","track_no":"demodata01","status":"shipping","state":"normal","source":"O","platform":"wish","parcel_type":"127","carrier_type":"100008","is_active":"Y","batch_no":"M20150519","create_time":"2015-05-15","update_time":"2015-07-14 08:07:22","from_nation":"--","to_nation":"US","mark_handled":"N","notified_seller":"N","notified_buyer":"N","shipping_notified":"N","pending_fetch_notified":"N","rejected_notified":"N","received_notified":"N","ship_by":"EMS","delivery_fee":"0.0000","ship_out_date":"2015-05-05","total_days":"70","all_event":"[{\"when\":\"2015-05-14 01:15\",\"where\":\"\",\"what\":\"WW91ciBzaGlwbWVudCBoYXMgYXJyaXZlZCBhdCB0aGUgcG9zdGFsIG9wZXJhdG9yIG9mIHRoZSBjb3VudHJ5IG9mIGRlc3RpbmF0aW9uIGFuZCB3aWxsIGJlIGRlbGl2ZXJlZCBpbiB0aGUgY29taW5nIGRheXMu\",\"lang\":\"en\",\"type\":\"fromNation\"},{\"when\":\"2015-05-12 05:56\",\"where\":\"\",\"what\":\"RGVwYXJ0dXJlIHRvIGNvdW50cnkgb2YgZGVzdGluYXRpb24=\",\"lang\":\"en\",\"type\":\"fromNation\"},{\"when\":\"2015-05-07 13:00\",\"where\":\"\",\"what\":\"QXJyaXZhbCBhdCBleHBvcnQgaHVi\",\"lang\":\"en\",\"type\":\"fromNation\"},{\"when\":\"2015-05-07 00:01\",\"where\":\"QmVsZ2l1bQ==\",\"what\":\"SXRlbSBpcyByZWFkeSBmb3IgdHJhbnNwb3J0\",\"lang\":\"en\",\"type\":\"fromNation\"},{\"when\":\"2015-05-06 09:10\",\"where\":\"\",\"what\":\"SXRlbSBpcyBhbm5vdW5jZWQgLyBicG9zdCByZWNlaXZlZCB0aGUgaW5mb3JtYXRpb24=\",\"lang\":\"en\",\"type\":\"fromNation\"}]","first_event_date":"2015-05-06","last_event_date":"2015-05-14","stay_days":"62","msg_sent_error":"N","from_lang":"en","to_lang":"","first_track_result_date":null,"remark":null,"addi_info":"{\"2015-07-13 try other carrier\":1}"}';
			$tmpTracking = json_decode($tmpTracking,true);
			
			$orderInfo = '{"order_id":"00000002305","order_status":"500","order_manual_id":"0","pay_status":"1","shipping_status":"2","is_manual_order":"0","order_source":"ebay","order_type":"","order_source_order_id":"151449798817-120055065xxxx","order_source_site_id":"UK","selleruserid":"vipwitsionstore","saas_platform_user_id":"26","order_source_srn":"2205","customer_id":"1995","source_buyer_user_id":"brunoacoutinho","order_source_shipping_method":"UK_OtherCourierOrDeliveryInternational","order_source_create_time":"1422028677","subtotal":"9.34","shipping_cost":"0.00","discount_amount":"0.00","grand_total":"9.34","returned_total":"0.00","price_adjustment":"0.00","currency":"GBP","consignee":"Petter","consignee_postal_code":"2840-123","consignee_phone":"93722xxxx","consignee_email":"littleboss@gmail.com","consignee_company":"","consignee_country":"Portugal","consignee_country_code":"PT","consignee_city":"Tampa","consignee_province":"New York","consignee_district":"","consignee_county":"","consignee_address_line1":"Vasstrondvegen xxx","consignee_address_line2":"","consignee_address_line3":"","default_warehouse_id":"0","warehouse_type":"0","default_carrier_code":"","default_shipping_method_code":"","paid_time":"1422028678","delivery_time":"0","create_time":"1422260154","update_time":"1422260154","user_message":"","carrier_type":"0","is_feedback":"0","items":[{"order_item_id":"2394","order_id":"00000002305","order_source_srn":"2205","order_source_order_item_id":"2223","source_item_id":"151449798817","sku":"151449798817","product_name":"Cycling Bicycle Bike Pannier Rear Seat Bag Rack Trunk Shoulder Handbag New","photo_primary":"http:\/\/i.ebayimg.com\/00\/s\/MTAwMFgxMjAw\/z\/bO8AAOSwZkJUR2pV\/$_1.JPG?set_id=880000500F","shipping_price":"0.00","shipping_discount":"0.00","price":"9.34","promotion_discount":"0.00","ordered_quantity":"1","quantity":"1","sent_quantity":"1","packed_quantity":"1","returned_quantity":"0","invoice_requirement":"","buyer_selected_invoice_category":"","invoice_title":"","invoice_information":"","remark":null,"create_time":"1422029460","update_time":"1422029460","platform_sku":"CZEB14102405A","is_bundle":"0","bdsku":""}]}';
			$orderInfo = json_decode($orderInfo,true);
		}else{
			//获取 tracking 数据 用来代换数据
			$tmpTracking = Tracking::find()->andWhere(['track_no'=>$track_no])->asArray()->One();
			
			//获取 order 数据 用来代换数据
			$order = self::getOrderDetailFromOMSByTrackNo($track_no);
			if ($order['success'])
				$orderInfo = $order['order'];
			else
				$orderInfo = [];
			
			/* kh20170401
			//跟踪号找不到订单时候， 使用当前的跟踪号到订单表中再查找一次订单数据
			if (empty($orderInfo)){
			    // 非手工订单 ， 非和平订单 的指定单号号
			    $orderInfo = OdOrder::find()->where(['order_source_order_id'=>$track_no , 'order_capture'=>'N' , 'order_relation'=>['normal' , 'sm']])->asArray()->one();
			    $tmpItems = OdOrderItem::find()->where(['order_id'=>$orderInfo['order_id']])->asArray()->all();
			    $orderInfo['items'] =$tmpItems;
			}
			*/
		}
		
		$orderData = $orderInfo;
		//buyer id 重新赋值
		if (!empty($orderInfo['source_buyer_user_id'])){
			$SourceBuyerID = $orderInfo['source_buyer_user_id'];
		}else{
			$SourceBuyerID = "";
		}
		
		//根据 替换的映射关系   , 进行替换处理
		foreach($FieldNameMapping as $fieldname =>$value ){
		
			if (in_array($value, ['track_no', 'ship_by'])){
				//tracking 部分
				if($value=='track_no'){
					$addi_info = json_decode($tmpTracking["addi_info"],true);
					if(!empty($addi_info['return_no']))
						$abroad_no_str = "(".$addi_info['return_no'].")";
					else
						$abroad_no_str='';
					
					$subject = str_replace("[$fieldname]", $tmpTracking[$value].$abroad_no_str ,$subject);
					$template = str_replace("[$fieldname]", $tmpTracking[$value].$abroad_no_str ,$template);
					
				}else{
					//ship_by
					$carrier_name=TrackingApiHelper::getTrackNoCarrierEnName($tmpTracking['track_no'], $tmpTracking["order_id"]);
					if(!empty($carrier_name)){
						$tmpTracking[$value] = $carrier_name;
					}
					$tmpTracking[$value] = preg_replace('/([\x80-\xff]*)/i','',$tmpTracking[$value]);
					$subject = str_replace("[$fieldname]", $tmpTracking[$value] ,$subject);
					$template = str_replace("[$fieldname]", $tmpTracking[$value] ,$template);
				}	
			}else{
				// order 部分
				if (!empty($orderInfo[$value])){
					if($orderInfo['order_source']!=='ebay' && $value=='order_source_srn'){
						continue;
					}else{
						$subject = str_replace("[$fieldname]", $orderInfo[$value] , $subject);
						$template = str_replace("[$fieldname]", $orderInfo[$value] , $template);
					}
					
				}else{//special handling fields
					$replaced_value = '';
					if ($value =='consignee_address_lines'){
						$replaced_value .= (empty($orderData['consignee_company'])?"":($replaced_value==''?'':' , ').$orderData['consignee_company']) ;
						$replaced_value	.= (empty($orderData['consignee_address_line1'])?"":($replaced_value==''?'':' , ').$orderData['consignee_address_line1']);
						$replaced_value	.= (empty($orderData['consignee_address_line2'])?"":($replaced_value==''?'':' , ').$orderData['consignee_address_line2']);
						$replaced_value	.= (empty($orderData['consignee_address_line3'])?"":($replaced_value==''?'':' , ').$orderData['consignee_address_line3']);
						$replaced_value	.= (empty($orderData['consignee_district'])?"":($replaced_value==''?'':' , ').$orderData['consignee_district']);
						$replaced_value	.= (empty($orderData['consignee_county'])?"":($replaced_value==''?'':' , ').$orderData['consignee_county']);
						$replaced_value	.= (empty($orderData['consignee_city'])?"":($replaced_value==''?'':' , ').$orderData['consignee_city']);
						$replaced_value	.= (empty($orderData['consignee_province'])?"":($replaced_value==''?'':' , ').$orderData['consignee_province']);
						$replaced_value	.= (empty($orderData['consignee_postal_code'])?"":($replaced_value==''?'':' , ').$orderData['consignee_postal_code']);
						$replaced_value	.= (empty($orderData['consignee_country'])?"":($replaced_value==''?'':' , ').$orderData['consignee_country']);
					}
					
					if ($value =='items_list'){
						if (!empty($orderData['items'])){
							if (is_string($orderData['items']))
								$items = json_decode($orderData['items'],true);
							else
								$items = $orderData['items'];
						}else{
							$items = [];
						}
						
						$total_qty = 0;
						$replaced_value = "";
						if (!empty($items) and is_array($items))
						foreach ($items as $anItem){
							$replaced_value .= (!empty($anItem['sku'])?$anItem['sku']:"")." "
									        .(!empty($anItem['product_name'])?$anItem['product_name']:"")." x "
									        .(!empty($anItem['ordered_quantity'])?$anItem['ordered_quantity']:0)." "
									        .(!empty($orderData['currency'])?$orderData['currency']:"") ." "
									        .(!empty($anItem['price'])?$anItem['price']:0)
											;
						}
						
					}
					if ($value =='ebay_items_list' && $orderInfo['order_source']=='ebay'){
						if (!empty($orderData['items'])){
							if (is_string($orderData['items']))
								$items = json_decode($orderData['items'],true);
							else
								$items = $orderData['items'];
						}else{
							$items = [];
						}
						$item_id_arr = [];
						if (!empty($items) and is_array($items)){
							foreach ($items as $anItem){
								if(!empty($anItem['order_source_itemid'])){
									$item_id_arr[]=$anItem['order_source_itemid'];
								}
							}
						}
						$replaced_value = empty($item_id_arr)?' ':implode(',', $item_id_arr);
					}
					
					if ($value =='query_url' && !empty($tmpTracking['id'])){
						$puid = \Yii::$app->subdb->getCurrentPuid();
						$replaced_value = "http://littleboss.17track.net/message/tracking/index?parcel=". MessageHelper::encryptBuyerLinkParam($puid, $tmpTracking['id']) ;
					}//end of when query_url
					else{
					    $replaced_value = "http://littleboss.17track.net/message/tracking/index";
				    }
					
					
					$subject = str_replace("[$fieldname]", $replaced_value , $subject);
					$template = str_replace("[$fieldname]", $replaced_value , $template);
				}
			}
		
		}
		
		return ['subject'=>$subject , 'template'=>$template , 'order'=>$orderInfo ];
	}//end of replaceTemplateData
	
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 根据 参数  、订单号， 替换主题、模块的内容  指定的 固定变量
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param  	$subject			邮件主题
	 * 			$template			邮件内容
	 * 			$order_no			数据来源订单号
	 * 			$order_relation		订单类型(普通:normal/合并原子订单:fm)
	 * 			$sm_order_no		合并后的单号(如果有)
	 +---------------------------------------------------------------------------------------------
	 * @return na
	 +---------------------------------------------------------------------------------------------
	 * @author		lkh		2015/5/12			初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function replaceTemplateDataByOms(&$subject , &$template , $order_no ,$order_relation='normal',$sm_order_no=''){
		global $SourceBuyerID;
		$SourceBuyerID = '';//初始化
		// 定义需要替换的字段名
		$FieldNameMapping = [
			'收件人名称' => 'consignee',
			'收件人国家'  => 'consignee_county',
			'收件人地址，包含城市'  => 'consignee_address_lines',
			'收件人邮编'  => 'consignee_postal_code',
			'收件人电话'  => 'consignee_phone',
			'平台订单号'  => 'order_source_order_id',
			'ebay订单SRN' => 'order_source_srn',
			'订单金额'  => 'grand_total',
			'订单物品列表(商品sku，名称，数量，单价)'  => 'items_list',
			'ebay订单Item Id' =>'ebay_items_list',
			'包裹物流号'  => 'track_no',
			'包裹递送物流商'  => 'ship_by',
			'买家查看包裹追踪及商品推荐链接'=> 'query_url'
		];
		
		$tracking_info_src = 'lt_tracking';//物流信息来源
		if ($order_no == "demodata01"){
			$tmpTracking = '{"id":"735","seller_id":"Wish\u8881\u8d85","order_id":"271586578303-1473512808017","track_no":"demodata01","status":"shipping","state":"normal","source":"O","platform":"wish","parcel_type":"127","carrier_type":"100008","is_active":"Y","batch_no":"M20150519","create_time":"2015-05-15","update_time":"2015-07-14 08:07:22","from_nation":"--","to_nation":"US","mark_handled":"N","notified_seller":"N","notified_buyer":"N","shipping_notified":"N","pending_fetch_notified":"N","rejected_notified":"N","received_notified":"N","ship_by":"EMS","delivery_fee":"0.0000","ship_out_date":"2015-05-05","total_days":"70","all_event":"[{\"when\":\"2015-05-14 01:15\",\"where\":\"\",\"what\":\"WW91ciBzaGlwbWVudCBoYXMgYXJyaXZlZCBhdCB0aGUgcG9zdGFsIG9wZXJhdG9yIG9mIHRoZSBjb3VudHJ5IG9mIGRlc3RpbmF0aW9uIGFuZCB3aWxsIGJlIGRlbGl2ZXJlZCBpbiB0aGUgY29taW5nIGRheXMu\",\"lang\":\"en\",\"type\":\"fromNation\"},{\"when\":\"2015-05-12 05:56\",\"where\":\"\",\"what\":\"RGVwYXJ0dXJlIHRvIGNvdW50cnkgb2YgZGVzdGluYXRpb24=\",\"lang\":\"en\",\"type\":\"fromNation\"},{\"when\":\"2015-05-07 13:00\",\"where\":\"\",\"what\":\"QXJyaXZhbCBhdCBleHBvcnQgaHVi\",\"lang\":\"en\",\"type\":\"fromNation\"},{\"when\":\"2015-05-07 00:01\",\"where\":\"QmVsZ2l1bQ==\",\"what\":\"SXRlbSBpcyByZWFkeSBmb3IgdHJhbnNwb3J0\",\"lang\":\"en\",\"type\":\"fromNation\"},{\"when\":\"2015-05-06 09:10\",\"where\":\"\",\"what\":\"SXRlbSBpcyBhbm5vdW5jZWQgLyBicG9zdCByZWNlaXZlZCB0aGUgaW5mb3JtYXRpb24=\",\"lang\":\"en\",\"type\":\"fromNation\"}]","first_event_date":"2015-05-06","last_event_date":"2015-05-14","stay_days":"62","msg_sent_error":"N","from_lang":"en","to_lang":"","first_track_result_date":null,"remark":null,"addi_info":"{\"2015-07-13 try other carrier\":1}"}';
			$tmpTracking = json_decode($tmpTracking,true);
				
			$orderInfo = '{"order_id":"00000002305","order_status":"500","order_manual_id":"0","pay_status":"1","shipping_status":"2","is_manual_order":"0","order_source":"ebay","order_type":"","order_source_order_id":"151449798817-120055065xxxx","order_source_site_id":"UK","selleruserid":"vipwitsionstore","saas_platform_user_id":"26","order_source_srn":"2205","customer_id":"1995","source_buyer_user_id":"brunoacoutinho","order_source_shipping_method":"UK_OtherCourierOrDeliveryInternational","order_source_create_time":"1422028677","subtotal":"9.34","shipping_cost":"0.00","discount_amount":"0.00","grand_total":"9.34","returned_total":"0.00","price_adjustment":"0.00","currency":"GBP","consignee":"Petter","consignee_postal_code":"2840-123","consignee_phone":"93722xxxx","consignee_email":"littleboss@gmail.com","consignee_company":"","consignee_country":"Portugal","consignee_country_code":"PT","consignee_city":"Tampa","consignee_province":"New York","consignee_district":"","consignee_county":"","consignee_address_line1":"Vasstrondvegen xxx","consignee_address_line2":"","consignee_address_line3":"","default_warehouse_id":"0","warehouse_type":"0","default_carrier_code":"","default_shipping_method_code":"","paid_time":"1422028678","delivery_time":"0","create_time":"1422260154","update_time":"1422260154","user_message":"","carrier_type":"0","is_feedback":"0","items":[{"order_item_id":"2394","order_id":"00000002305","order_source_srn":"2205","order_source_order_item_id":"2223","source_item_id":"151449798817","sku":"151449798817","product_name":"Cycling Bicycle Bike Pannier Rear Seat Bag Rack Trunk Shoulder Handbag New","photo_primary":"http:\/\/i.ebayimg.com\/00\/s\/MTAwMFgxMjAw\/z\/bO8AAOSwZkJUR2pV\/$_1.JPG?set_id=880000500F","shipping_price":"0.00","shipping_discount":"0.00","price":"9.34","promotion_discount":"0.00","ordered_quantity":"1","quantity":"1","sent_quantity":"1","packed_quantity":"1","returned_quantity":"0","invoice_requirement":"","buyer_selected_invoice_category":"","invoice_title":"","invoice_information":"","remark":null,"create_time":"1422029460","update_time":"1422029460","platform_sku":"CZEB14102405A","is_bundle":"0","bdsku":""}]}';
			$orderInfo = json_decode($orderInfo,true);
		}else{
			//获取 tracking 数据 用来代换数据
			if($order_relation=='fm' && !empty($sm_order_no))
				$tmpTracking = Tracking::find()->andWhere(['order_id'=>$sm_order_no])->asArray()->One();
			else 
				$tmpTracking = Tracking::find()->andWhere(['order_id'=>$order_no])->asArray()->One();
			
			//跟踪号未同步到lt_tracking表的话，用od_order_shipped_v2表数据代替
			if(empty($tmpTracking)){
				$tmp_sql = OdOrderShipped::find();
				if($order_relation=='fm' && !empty($sm_order_no))
					$tmp_sql->andWhere(['order_source_order_id'=>$sm_order_no]);
				else 
					$tmp_sql->andWhere(['order_source_order_id'=>$order_no]);
				$tmpTracking = $tmp_sql->orderBy("id DESC")->asArray()->one();
				if(!empty($tmpTracking)){
					$tracking_info_src = 'od_order_shipped_v2';
					$tmpTracking['track_no'] = $tmpTracking['tracking_number'];
					$tmpTracking['ship_by'] = $tmpTracking['shipping_method_code'];
				}
			}
			
			//获取 order 数据 用来代换数据
			$orderInfo = OdOrder::find()->where(['order_source_order_id'=>$order_no])->asArray()->One();
			if(!empty($orderInfo)){
				$itemsModel	= OdOrderItem::find()->where(["order_id"=>$orderInfo['order_id']])->asArray()->all();
				$orderInfo['items'] = array();
				if (!empty($itemsModel)){
					$orderInfo['items'] = $itemsModel;
				}
			}
		}
		$orderData = $orderInfo;
		//buyer id 重新赋值
		if (!empty($orderInfo['source_buyer_user_id'])){
			$SourceBuyerID = $orderInfo['source_buyer_user_id'];
		}else{
			$SourceBuyerID = "";
		}
	
		//根据 替换的映射关系   , 进行替换处理
		foreach($FieldNameMapping as $fieldname =>$value ){
	
			if (in_array($value, ['track_no', 'ship_by'])){
				//tracking 部分
				if($value=='track_no'){
					if($tracking_info_src=='lt_tracking'){
						$addi_info = json_decode($tmpTracking["addi_info"],true);
						if(!empty($addi_info['return_no']))
							$abroad_no_str = "(".$addi_info['return_no'].")";
						else
							$abroad_no_str='';
					}elseif($tracking_info_src=='od_order_shipped_v2'){
						if(!empty($tmpTracking['return_no']['TrackingNo']))
							$abroad_no_str = "(".$tmpTracking['return_no']['TrackingNo'].")";
						else 
							$abroad_no_str='';
					}
					$subject = str_replace("[$fieldname]", $tmpTracking[$value].$abroad_no_str ,$subject);
					$template = str_replace("[$fieldname]", $tmpTracking[$value].$abroad_no_str ,$template);
						
				}else{
					//ship_by
					if($tracking_info_src=='lt_tracking'){
						$carrier_name=TrackingApiHelper::getTrackNoCarrierEnName($tmpTracking['track_no'], $order_no);
						if(!empty($carrier_name)){
							$tmpTracking[$value] = $carrier_name;
						}
					}
					$tmpTracking[$value] = preg_replace('/([\x80-\xff]*)/i','',$tmpTracking[$value]);
					$subject = str_replace("[$fieldname]", $tmpTracking[$value] ,$subject);
					$template = str_replace("[$fieldname]", $tmpTracking[$value] ,$template);
				}
	
			}else{
				// order 部分
				if (!empty($orderInfo[$value])){
					if($orderInfo['order_source']!=='ebay' && $value=='order_source_srn'){
						continue;
					}else{
						$subject = str_replace("[$fieldname]", $orderInfo[$value] , $subject);
						$template = str_replace("[$fieldname]", $orderInfo[$value] , $template);
					}
						
				}else{//special handling fields
					$replaced_value = '';
					if ($value =='consignee_address_lines'){
						$replaced_value .= (empty($orderData['consignee_company'])?"":($replaced_value==''?'':' , ').$orderData['consignee_company']) ;
						$replaced_value	.= (empty($orderData['consignee_address_line1'])?"":($replaced_value==''?'':' , ').$orderData['consignee_address_line1']);
						$replaced_value	.= (empty($orderData['consignee_address_line2'])?"":($replaced_value==''?'':' , ').$orderData['consignee_address_line2']);
						$replaced_value	.= (empty($orderData['consignee_address_line3'])?"":($replaced_value==''?'':' , ').$orderData['consignee_address_line3']);
						$replaced_value	.= (empty($orderData['consignee_district'])?"":($replaced_value==''?'':' , ').$orderData['consignee_district']);
						$replaced_value	.= (empty($orderData['consignee_county'])?"":($replaced_value==''?'':' , ').$orderData['consignee_county']);
						$replaced_value	.= (empty($orderData['consignee_city'])?"":($replaced_value==''?'':' , ').$orderData['consignee_city']);
						$replaced_value	.= (empty($orderData['consignee_province'])?"":($replaced_value==''?'':' , ').$orderData['consignee_province']);
						$replaced_value	.= (empty($orderData['consignee_postal_code'])?"":($replaced_value==''?'':' , ').$orderData['consignee_postal_code']);
						$replaced_value	.= (empty($orderData['consignee_country'])?"":($replaced_value==''?'':' , ').$orderData['consignee_country']);
					}
						
					if ($value =='items_list'){
						if (!empty($orderData['items'])){
							if (is_string($orderData['items']))
								$items = json_decode($orderData['items'],true);
							else
								$items = $orderData['items'];
						}else{
							$items = [];
						}
	
						$total_qty = 0;
						$replaced_value = "";
						if (!empty($items) and is_array($items)){
							foreach ($items as $anItem){
								$replaced_value .= (!empty($anItem['sku'])?$anItem['sku']:"")." "
									.(!empty($anItem['product_name'])?$anItem['product_name']:"")." x "
									.(!empty($anItem['ordered_quantity'])?$anItem['ordered_quantity']:0)." "
									.(!empty($orderData['currency'])?$orderData['currency']:"") ." "
									.(!empty($anItem['price'])?$anItem['price']:0);
							}
						}
					}
					if ($value =='ebay_items_list' && $orderInfo['order_source']=='ebay'){
						if (!empty($orderData['items'])){
							if (is_string($orderData['items']))
								$items = json_decode($orderData['items'],true);
							else
								$items = $orderData['items'];
						}else{
							$items = [];
						}
						$item_id_arr = [];
						if (!empty($items) and is_array($items)){
							foreach ($items as $anItem){
								if(!empty($anItem['order_source_itemid'])){
									$item_id_arr[]=$anItem['order_source_itemid'];
								}
							}
						}
						$replaced_value = empty($item_id_arr)?' ':implode(',', $item_id_arr);
					}
						
					if ($value =='query_url'){
						$puid = \Yii::$app->subdb->getCurrentPuid();
						if(!empty($tmpTracking['id']) && $tracking_info_src=='lt_tracking')
							$replaced_value = "http://littleboss.17track.net/message/tracking/index?parcel=". MessageHelper::encryptBuyerLinkParam($puid, $tmpTracking['id']) ;
						else
							$replaced_value = "http://littleboss.17track.net/message/tracking/index?parcel=". MessageHelper::encryptBuyerLinkParam($puid, $orderData['order_id'],'order_id');
					}//end of when query_url
						
						
					$subject = str_replace("[$fieldname]", $replaced_value , $subject);
					$template = str_replace("[$fieldname]", $replaced_value , $template);
				}
			}
	
		}
	
		return ['subject'=>$subject , 'template'=>$template , 'order'=>$orderInfo ];
	}//end of replaceTemplateData
	
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 根据 参数  物流号 替换 主题 , 模块的内容 指定的 固定变量
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param  	$subject			邮件主题
	 * 			$template			邮件内容
	 * 			$TicketId			数据来源站内信号码
	 +---------------------------------------------------------------------------------------------
	 * @return na
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2015/5/12				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function replaceTemplateDataByticketId($subject , $template , $TicketId){
		$TicketSessionModel = TicketSession::find()->where(['ticket_id'=>$TicketId])->asArray()->one();
		$FieldNameMapping = [
		'收件人名称' => 'buyer_nickname',
		];
		//根据 替换的映射关系   , 进行替换处理
		foreach($FieldNameMapping as $fieldname =>$value ){
			$subject = str_replace("[$fieldname]", $TicketSessionModel[$value] , $subject);
			$template = str_replace("[$fieldname]", $TicketSessionModel[$value] , $template);
		}
		return ['subject'=>$subject , 'template'=>$template ];
	}//end of replaceTemplateDataByticketId
	
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 调用OMS接口，某个Order的详细信息
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param  track_no             物流编号
	 +---------------------------------------------------------------------------------------------
	 * @return						array('success'=true,'message'='',order='')
	 *
	 * @invoking					TrackingHelper::getOrderDetailFromOMSByTrackNo();
	 * @Call Eagle 1                http://erp.littleboss.com/api/GetOrderDetail?track_no=RG234234232CN&puid=1
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		yzq		2015/2/9				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function getOrderDetailFromOMSByTrackNo($track_no){
		return TrackingHelper::getOrderDetailFromOMSByTrackNo($track_no);
		/*
		if (isset(\Yii::$app->params["currentEnv"]) and \Yii::$app->params["currentEnv"]=="test" ){
			return ['success'=>true,'order'=>json_decode(self::$DUMP_OMS_DATA,true)];
		}else
			return TrackingHelper::getOrderDetailFromOMSByTrackNo($track_no);
			*/
	}
	
	public static function createDefaultTemplateIfNotExists(){
		$exists = MsgTemplate::find()->andWhere(['name' => '已签收请求好评(预设1)'])->one();
		if (empty($exists)){
			$MsgTemplate = new MsgTemplate();
			$MsgTemplate->name ='已签收请求好评(预设1)';
			$MsgTemplate->subject = "Your parcel (number [包裹物流号]) has arrived, which is shipped by [包裹递送物流商].";
			$MsgTemplate->body = "Dear [收件人名称],

Your parcel (number [包裹物流号]) has arrived, which is shipped by [包裹递送物流商].

Shipping address:
[收件人地址，包含城市].

Do you encounter any problem while operating this product?
Please consult us if any issue.

If you find the product is good, would you please give us a positive evaluation?
The related order ID is [平台订单号].

Thanks and regards
Good day.";
			$id = $MsgTemplate->save(false); 
		}
	}
	
	
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 根据 order id  来获取  message 内容
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param         $order_id 			string 			订单编号 
	 +---------------------------------------------------------------------------------------------
	 * @return array   cs_message 的 表结构
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2015/5/30				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	public static function getMessageDataByOrderId($order_id){
		$MsgData = [];
		$MsgData = Message::find()->where(['order_id'=>$order_id])->asArray()->all();
		return $MsgData;
	}//end of getMessageDataByOrderId
	
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 吧data中的message 内容，放入到message 缓冲区，等到批量insert
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param         array of data for message queue, e.g.:
	 *                ['cpur_id'=>1,'create_time'=>,'subject'=>'hihi','content'='Money pls',
	 *                'platform'=>ebay,'order_id'=>'123123123','addi_info'=5]
	 *                清注意：platform 的值是固定的 enum('ebay', 'aliexpress', 'wish', 'amazon')
	 +---------------------------------------------------------------------------------------------
	 * @return n/a
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		yzq		2015/4/9				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	public static function putToMessageData($aData){
		//$aData = array();
		if (isset($aData['platform']))
			$aData['platform'] = strtolower($aData['platform']);
	
		self::$Insert_Message_data[] = $aData;
	}//end of putToMessageData
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 把Message data  ，批量insert 到数据库中
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 +---------------------------------------------------------------------------------------------
	 * @return array
	 *
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2015/5/25				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function postMessageDataToDb(){
		//use sql PDO, not Model here, for best performance
		$order_ids = [];
		$sql = " INSERT INTO  `cs_message`".
				"( status, `cpur_id`, `create_time`,`subject`,`content`,`platform`,`order_id`,".
				"`addi_info`) VALUES ";
	
		$sql_values = '';
		$Datas = self::$Insert_Message_data;
		self::$Insert_Message_data = array();
	
		$fields = ['cpur_id','create_time','subject','content','platform','order_id','addi_info'];
	
		//step 1, create a full SQL.
		$i = 10000;
		$starti =$i + 1;
		$bindDatas = array();
		foreach ($Datas as $data){
			$i++;
				
			if (empty($data['update_time']))
				$data['update_time'] = $data['create_time'];

			$bindDatas[$i] = $data;
			$order_ids[] = $data['order_id'];
			$sql_values .= ($sql_values==''?'':",").
			"('P',:cpur_id$i,:create_time$i,:subject$i, :content$i ,:platform$i, :order_id$i, :addi_info$i ".
			")";
	
			if (strlen($sql_values) > 3000){
				//one sql syntax do not exceed 4800, so make 3000 as a cut here
				//	\Yii::info(['Message',__CLASS__,__FUNCTION__,'Background',"SQL to be execute:".$sql.$sql_values ],"edb\global");
				$command = Yii::$app->subdb->createCommand($sql.$sql_values .";");
	
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
			$command = Yii::$app->subdb->createCommand($sql.$sql_values.";");
			for($tempi = $starti; $tempi <= $i; $tempi ++){
				foreach ($fields as $aField){
					$command->bindValue(':'.$aField.$tempi, $bindDatas[$tempi][$aField], \PDO::PARAM_STR);
				}
			}//end of each data index for this bulk insert
			$command->execute();
		}
	
		//step 3, try to get msg_id map order_id after insert
		$rtn_map = [];
		$messages = Message::find()->where(['order_id'=>$order_ids])->asArray()->all();
		if (!empty($messages)){
			foreach ($messages as $message){
				$rtn_map[$message['order_id']  ] = $message['id'];
			}
		}
		
		return $rtn_map;
		
	}//end of function postMessageBufferToDb
	
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 把Message data  ，批量insert 到数据库中
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 +---------------------------------------------------------------------------------------------
	 * @return na
	 *
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2015/5/25				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function resendAllFailureMessage(){
		$msgData = Message::find()->where(['status'=>'F'])->asArray()->all();
		
		//update message status  = P
		$tmp = Message::updateAll(['status'=>'P'],['status'=>'F']);
		//update tracking  msg_sent_error  = N
		$tmp = Tracking::updateAll(['msg_sent_error'=>'N'],['msg_sent_error'=>'Y']);
		
		// put api queue
		foreach($msgData as $msg ){
			
			$sellerid = Tracking::find()->select(['seller_id'])->where(['order_id'=>$msg ['order_id']])->scalar();
			$aData = ['puid'=>$msg ['cpur_id'] ,
			'create_time'=>$msg ['create_time'] ,
			'subject'=>$msg ['subject'],
			'content'=>$msg ['content'],
			'platform'=>$msg ['platform'],
			'order_id'=>$msg ['order_id'] ,
			'msg_id'=>$msg['id'],
			'seller_id'=> $sellerid , 
			];
			
			//set Queuer buffer
			MessageBGJHelper::putToMessageQueueBuffer($aData);
		}
		// post messsage queue buffer
		MessageBGJHelper::postMessageApiQueueBufferToDb();
	}//end of resendAllFailureMessage
	
	
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 获取当前  order id 发送失败的数量
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param         $order_id 			string 			订单编号 
	 +---------------------------------------------------------------------------------------------
	 * @return 		int
	 *
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2015/06/05				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function getFailureMessageCount($order_id=""){
		if (empty($order_id))
			return Message::find()->where(['status'=>'F'])->count();
		else
			return Message::find()->where(['order_id'=>$order_id , 'status'=>'F'])->count();
	}//end of getFailureMessageCount
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 把该msg 通过变量匹配，替换后的最终content结果以及标题，写到表 user_x 里面的 cs_message
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param  $id  							cs_auto_roles 的的主键
	 * 		   $name							规则名称
	 +---------------------------------------------------------------------------------------------
	 * @return array
	 * 						roles的表结构数组 
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2015/5/12				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function getMessageRoles($id=0 , $name='' ){
		$query = AutoRoles::find();
		if (!empty($id))
			$query->andWhere(['id'=>$id]);
		
		if (!empty($name))
			$query->andWhere(['name'=>$name]);
		
		$result = $query->asArray()->orderBy(' priority ')->all();
		
		return $result;
	}//end of getMessageRoles
	
	/**
	 * 获取给定平台,店铺,对应国家  的首选  匹配规则名称
	 * @params	string	$platform
	 * 			int		$account_id
	 *			string	$nation
	 *			string	$status
	 * @return	string  :ruel name,or ''
	 */
	public static function getTopTrackerAuotRuleName($platform,$account_id, $nation,$status){
		$ruleName = '';
		//所有参数 都 是必填项, 其中 一个没有,则不作匹配
		if (empty($platform) || empty($account_id) || empty($nation) || empty($status))
			return $ruleName;
		$role = self::getTopTrackerAuotRule($platform, $account_id, $nation, $status);
		
		if (!empty($role['name'])) $ruleName = $role['name'];
		return $ruleName;
		
	}
	
	/**
	 * 获取给定平台,店铺,对应国家  的首选  匹配规则名称 oms调用
	 * @params	string	$platform
	 * 			int		$account_id
	 *			string	$nation
	 * @return	string  :ruel name,or ''
	 */
	public static function getTopOmsAuotRuleName($platform,$account_id, $nation){
		$ruleName = '';
		//所有参数 都 是必填项, 其中 一个没有,则不作匹配
		if (empty($platform) || empty($account_id) || empty($nation))
			return $ruleName;
		$role = self::getTopTrackerAuotRule($platform, $account_id, $nation, $status='');
	
		if (!empty($role['name'])) $ruleName = $role['name'];
		return $ruleName;
	
	}
	
	/**
	 * 获取给定平台,店铺,对应国家  的首选  匹配规则名称
	 * @params	string	$platform
	 * 			int		$account_id
	 *			string	$nation
	 *			string	$status
	 * @return	array  :ruel 
	 */
	public  static function getTopTrackerAuotRule($platform,$account_id, $nation,$status){
		$reuslt = [];
	
		//模糊查询数据
		$query = AutoRoles::find()
			->where(['like','platform',$platform])
			->andWhere(' accounts like \'%"'.$account_id.'"%\' ')
			->andWhere(['like','nations',$nation]);
		if(empty($status) || $status=='na'){
			$status='na';
			$query->andWhere(['like','status','na']);
		}
		$Rules = $query->orderBy('priority ASC')
			->asArray()
			->all();
		
		//从模糊查询的结果中再验证
		foreach ($Rules as $rule){
			$platforms = empty($rule['platform'])?array():json_decode($rule['platform'],true);
			if(!in_array($platform, $platforms))
				break;
			$accounts = empty($rule['accounts'])?array():json_decode($rule['accounts'],true);
			if(!in_array($platform, array_keys($accounts)))
				break;
			else{
				$hasTargetAccount = false;
				foreach ($accounts[$platform] as $id){
					if((int)$account_id==(int)$id) $hasTargetAccount=true;
				}
				if(!$hasTargetAccount) break;
			}
			$nations = empty($rule['nations'])?array():json_decode($rule['nations'],true);
			if(!in_array($nation, $nations))
				break;
			$ruleStatus = empty($rule['status'])?array():json_decode($rule['status'],true);
			if(!in_array($status, $ruleStatus))
				break;
			//一旦某条结果完全匹配，立即返回
			$reuslt = $rule;
			break;
		}
		return $reuslt;
	}
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 把该msg role 删除  , 小于删除role的优先级全部加1
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param  $id  					cs_auto_roles 的的主键
	 +---------------------------------------------------------------------------------------------
	 * @return na
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2015/7/12				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function deleteMessageRole($id){
		$query = AutoRoles::find();
		$priority = $query->andWhere(['id'=>$id])->select('priority')->scalar();
		$up_record = 0;
		//删除 role
		$del_record = AutoRoles::deleteAll(['id'=>$id]);
		
		if (!empty($priority) && !empty($del_record)){
			//小于删除role的优先级全部加1
			$up_record =AutoRoles::updateAllCounters(['priority'=>-1],['>','priority',$priority]);
		}
		
		return $del_record;
	}//end of deleteMessageRole
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 把该msg role 的优先级加1 , 原来上一个级的role的优先级加1
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param  $id  					cs_auto_roles 的的主键
	 +---------------------------------------------------------------------------------------------
	 * @return na
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2015/7/12				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function setRoleUp($id){
		$query = AutoRoles::find();
		$priority = $query->andWhere(['id'=>$id])->select('priority')->scalar();
		$up_record = 0;
		
		//原来上一个级的role的优先级减1
		if ($priority>1){
			$up_record = AutoRoles::updateAllCounters(['priority'=>1],['priority'=>($priority-1)]);
		}
		
		$up_record = AutoRoles::updateAllCounters(['priority'=>-1],['id'=>$id]);
		
		return;
	}//end of setRoleUp
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 保存 roles 规则
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param  $data  					cs_auto_roles 的数据 结构
	 +---------------------------------------------------------------------------------------------
	 * @return array
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2015/7/12				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function saveRole($data){
		if (!empty($data['id'])){
			$model = AutoRoles::findOne(['id'=>$data['id']]);
		}else{
			$existRoleCount = AutoRoles::find()->andWhere(['name'=>$data['name']])->count();
			if (!empty($existRoleCount)){
				return ['success'=>false,'message'=>'规则名称重复!'];
			}
		}
		$roledata = [];
		if (empty($model)){
			$model = new AutoRoles();
		}
		$attr = $model->attributes;
		foreach($data as $key=>$value){
			if (array_key_exists($key, $attr))
				$roledata[$key] = $value;
		}
		
		$model->attributes = $roledata;
		
		if ($model->save()){
			return ['success'=>true,'message'=>''];
		}else{
			return ['success'=>false,'message'=>$model->errors];
		}
	}//saveRole
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 查找orderid status，针对速卖通
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param  $data  					
	 +---------------------------------------------------------------------------------------------
	 * @return array
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lwj		2016/5/19				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	public static function searchOrderStatus($order_id,$platform){
    	if(!empty($order_id)&&!empty($platform)){
    	    $result = OdOrder::find()->where(['order_source_order_id'=>$order_id,'order_source'=>$platform])->asArray()->one();
            if(!empty($result)){
                return array('success'=>true,'message'=>'','status'=>$result["order_status"]);
            }else{
                return array('success'=>false,'message'=>'没有相关订单','status'=>'');
            }    	
    	}else{
    	    return array('success'=>false,'message'=>'传输参数错误','status'=>'');
    	}
    	
	}//saveRole
	/**
	 +---------------------------------------------------------------------------------------------
	 * 获取  roles 规则
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param  na  					na
	 +---------------------------------------------------------------------------------------------
	 * @return array 
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2015/7/12				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function getlistRoleSetting(){
		$addi_info = [];
		$country_list = [];
		//获取账户信息
		$addi_info['accounts']  = TrackingHelper::getAccountFilterData('all');
		//ConfigHelper::setConfig("Tracking/to_nations", json_encode(["AR","AU","BE","BG","BH","BR","BY","CA","CH","CL","CO","CZ","DE","DK","EE","ES","FI","FR","GB","GR","GT","HU","IE","IL","IN","IT","KR","LB","LT","LV","MK","MU","MX","MY","NG","NL","NO","NZ","PE","PL","PT","RO","RU","SE","SG","SI","SK","TH","UK","US","UY"]));
		//获取国家信息
		
		$countrys =[];
		
		//$tmp_country_list = ConfigHelper::getConfig("Tracking/to_nations" );
		//$tmp_country_list = TrackingHelper::getTrackerTempDataFromRedis("to_nations" );
		$country_code_lsit= [];
		$tmp_country_list = '123';//随意赋值，使判断不往下面的情况走
		if (!empty($tmp_country_list)){
			/*
			if (is_string($tmp_country_list)){
				$country_code_lsit =  json_decode($tmp_country_list,true);
			}elseif(is_array($tmp_country_list)){
				$country_code_lsit = $tmp_country_list;
			}else{
				$country_code_lsit = [];
			}
			*/
			
			//收件国家
			$query = SysCountry::find();
			$regions = $query->orderBy('region')->groupBy('region')->select('region')->asArray()->all();
			foreach ($regions as $region){
				$arr['name']= $region['region'];
				$arr['value']=Helper_Array::toHashmap(SysCountry::find()->where(['region'=>$region['region'] , /*'country_code'=>$country_code_lsit*/])->orderBy('country_en')->select(['country_code', "CONCAT( country_zh ,'(', country_en ,')' ) as country_name "])->asArray()->all(),'country_code','country_name');
				if (!empty($arr['value'])){
					$countrys[]= $arr;
					foreach ($arr['value'] as $code=>$name){
						$country_code_lsit[] = $code;
					}
				}
				
			}
		}else{
			$toNations = Tracking::find()->select('to_nation')->distinct(true)->where(" to_nation<>'--' and to_nation<>'' and to_nation is not null ")->asArray()->all();  
			foreach ($toNations as $toNation){
				$country_code_lsit[] = $toNation['to_nation'];
			}
			TrackingHelper::setTrackerTempDataToRedis("to_nations", json_encode($country_code_lsit));
			
			$query = SysCountry::find();
			$regions = $query->orderBy('region')->groupBy('region')->select('region')->asArray()->all();
			foreach ($regions as $region){
				$arr['name']= $region['region'];
				$arr['value']=Helper_Array::toHashmap(SysCountry::find()->where(['region'=>$region['region'],'country_code'=>$country_code_lsit])->orderBy('country_en')->select(['country_code', "CONCAT( country_zh ,'(', country_en ,')' ) as country_name "])->asArray()->all(),'country_code','country_name');
				if (!empty($arr['value'])){
					$countrys[]= $arr;
				}
			
			}
		}
		
		/**/
		foreach($country_code_lsit as $code){
			$label = TrackingHelper::autoSetCountriesNameMapping($code);
			$country_list [$code] = $label;
		}
		
		$addi_info['country'] = $countrys;
		$addi_info['country_mapping'] = $country_list;
		 
		//物流状态
		$addi_info['ship_status'] =  Tracking::getChineseStatus('',true);
		
		$addi_info['ship_status']['na'] = '所有状态';
		 
		//模块数据
		$templateData = MsgTemplate::find()->asArray()->all();
		$TempLateList = [];
		foreach($templateData as $aTmp){
			$TempLateList[$aTmp['id']] = $aTmp['name'];
		}
		 
		$addi_info['template'] = $TempLateList;
		 
		//exit(json_encode($addi_info['ship_status']));
		$roles = MessageHelper::getMessageRoles();
		
		$addi_info['platform_label']=["ebay"=>"eBay" , "aliexpress"=>"速卖通","wish"=>"Wish","dhgate"=>"敦煌"];
		 
		foreach($roles as &$role){
			$role['platform_account'] = '';
			$role['nations_label'] = '';
			$role['status_label'] = '';
			//设置 平台账号显示格式
			if (!empty($role['accounts'])){
				$tmp_platform_account = json_decode($role['accounts'],true);
			}
		
			foreach($tmp_platform_account as $tmpPlatform=>$tmpAccount){
				foreach($addi_info['accounts'] as $row){
					$tmp_platform_cn = '';
					if ($row['platform'] == $tmpPlatform && in_array($row['id'], $tmpAccount)){
						if (!empty($role['platform_account'] ))
							$role['platform_account'] .=",";
						if (!empty($addi_info['platform_label'][$tmpPlatform]))
							$tmp_platform_cn = $addi_info['platform_label'][$tmpPlatform];
						
						$role['platform_account'] .= $tmp_platform_cn.":".$row['name'];
					}
				}
			}
			 
			//设置国家格式
			if (!empty($role['nations'])){
				$tmp_nation = json_decode($role['nations'],true);
			}
			 
			foreach($tmp_nation as $code){
				if (!empty($role['nations_label'])) $role['nations_label'] .=",";
				if (!empty($country_list [$code]))
				$role['nations_label'] .= $country_list [$code] ;
			}
			 
			//设置状态格式
			if (!empty($role['status'])){
				$tmp_status = json_decode($role['status'],true);
			}
			 
			foreach($tmp_status as $code){
				if (!empty($role['status_label'])) $role['status_label'] .=",";
				$role['status_label'] .= $addi_info['ship_status'] [$code] ;
			}
			 
		
		}//end of each role
		
		return [
    				'roles'=>$roles,
    				'addi_info'=>$addi_info,
    			];
	}//end of getlistRoleSetting
	
	
	static public function encryptBuyerLinkParam($puid,$parcel_id,$parcel_type='track_id'){
		//step 1：把 ((puid +7) * ($parcel_id + 5) ) % 100 作为校验码
		$ccr = (($puid +777) * ($parcel_id + 555) ) % 10000;
		
		//step 2：把2个数,检验吗 都转成16进制
		$n1 = dechex($puid);
		$n2 = dechex($parcel_id);
		$n3 = dechex($ccr);
		
		if(!empty($parcel_type)){
			//step 3: 把 "$puid-$parcel_id-校验码" 连在一起, base64 encode 发出去
			if($parcel_type=='track_id'){
				$n4 = dechex(0);
			}
			if($parcel_type=='order_id'){
				$n4 = dechex(1);
			}
			return base64_encode($n1."-".$n2."-".$n3."-".$n4);
		}else {
			//step 3: 把 "$puid-$parcel_id-校验码" 连在一起, base64 encode 发出去
			return base64_encode($n1."-".$n2."-".$n3);
		}
		
	}
	
	static public function decryptBuyerLinkParam($str){
		$rtn = '';
		//step 1: 把 "$puid-$parcel_id-校验码" 连在一起, base64 decode 得到
		$str1 = base64_decode($str);
		//step 2: 把2个数,检验吗 都转成10进制
		$ns = explode("-", $str1);
		if (is_array($ns) and count($ns) == 3){
			$puid = hexdec($ns[0]);
			$parcel_id = hexdec($ns[1]);
			$ccr = hexdec($ns[2]);
			
			if ($ccr == ((($puid +777) * ($parcel_id + 555) ) % 10000))
				$rtn = $puid."-".$parcel_id;
			
			return $rtn;
		}
		if (is_array($ns) and count($ns) == 4){
			$puid = hexdec($ns[0]);
			$parcel_id = hexdec($ns[1]);
			$ccr = hexdec($ns[2]);
			$parcel_type = hexdec($ns[3]);
			if ($ccr == ((($puid +777) * ($parcel_id + 555) ) % 10000))
				$rtn = $puid."-".$parcel_id."-".$parcel_type;
		}
		return $rtn;
	}
}
