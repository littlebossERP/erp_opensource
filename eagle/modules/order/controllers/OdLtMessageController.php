<?php
namespace eagle\modules\order\controllers;

use eagle\modules\order\models\OdOrder;
use eagle\modules\order\helpers\OrderHelper;
use eagle\assets\PublicAsset;
use yii\data\Sort;
use eagle\modules\order\helpers\OrderTrackingMessageHelper;
use eagle\modules\tracking\helpers\TrackingHelper;
use eagle\modules\tracking\helpers\TrackingTagHelper;
use eagle\modules\inventory\helpers\InventoryApiHelper;
use eagle\modules\util\helpers\SysLogHelper;
use eagle\modules\util\helpers\OperationLogHelper;
use eagle\modules\message\helpers\MessageHelper;
use eagle\modules\app\apihelpers\AppTrackerApiHelper;
use eagle\modules\message\models\MsgTemplate;
use common\helpers\Helper_Array;
use eagle\modules\app\apihelpers\AppApiHelper;
use eagle\modules\tracking\models\Tracking;
use eagle\modules\message\helpers\MessageBGJHelper;
use eagle\models\UserEdmQuota;
use eagle\modules\platform\apihelpers\PlatformAccountApi;
use eagle\models\LtCustomizedRecommendedProd;
use yii\data\Pagination;
use eagle\models\LtCustomizedRecommendedGroup;
use eagle\modules\util\helpers\ConfigHelper;
use eagle\modules\util\helpers\ResultHelper;
use eagle\modules\carrier\apihelpers\CarrierApiHelper;
use eagle\modules\carrier\models\SysShippingService;


class OdLtMessageController extends \eagle\components\Controller
{
	public $enableCsrfValidation = false;
	
   /* list出可以发送二次营销信息的订单
	 * @author		lzhl		2016/07/10		初始化
	 */
	public function actionListTrackingMessage(){
		if(empty($_REQUEST['platform']))
			return "没有选择有效的销售平台！";
		else 
			$platform = trim($_REQUEST['platform']);
		
		$puid = \Yii::$app->subdb->getCurrentPuid();
		
		//keyword参数
		if(!empty($_REQUEST['txt_search']) && trim($_REQUEST['txt_search'])!==''){
			$keyword = trim($_REQUEST['txt_search']);
		}else 
			$keyword = '';
		//日期参数
		if(!empty($_REQUEST['startdate']) && trim($_REQUEST['startdate'])!==''){
			$date_from = trim($_REQUEST['startdate']);
		}else 
			$date_from = '';
		if(!empty($_REQUEST['enddate']) && trim($_REQUEST['enddate'])!==''){
			$date_to = trim($_REQUEST['enddate']);
		}else 
			$date_to = '';
		//排序d参数
		$sort = !empty($_REQUEST['sort']) ? $_REQUEST['sort'] : 'create_time';
		if( '-' == substr($sort,0,1) ){
			$sort = substr($sort,1);
			$order = 'desc';
		} else {
			$order = 'asc';
		}
		$sortConfig = new Sort(['attributes' => ['customer_number','create_time','order_source_order_id','update_time']]);
		if(!in_array($sort, array_keys($sortConfig->attributes))){
			$sort = '';
			$order = '';
		}
		//其他参数
		$params = [];
		/*
		if(!empty($_REQUEST['hasComment']) && trim($_REQUEST['hasComment'])!==''){
			$params['hasComment'] = trim($_REQUEST['hasComment']);
		}
		*/
		//只显示有权限的账号，lrq20170828
		$account_data = \eagle\modules\platform\apihelpers\PlatformAccountApi::getPlatformAuthorizeAccounts(strtolower($platform));
		$selleruserids = array();
		foreach($account_data as $key => $val){
			$selleruserids[] = $key;
		}
		
		if(!empty($_REQUEST['selleruserid']) && trim($_REQUEST['selleruserid'])!==''){
			$params['selleruserid'] = trim($_REQUEST['selleruserid']);
		}
		else{
			$params['selleruserid'] = $selleruserids;
		}
		
		if(!empty($_REQUEST ['to_nations'] )){
			$params['to_nation'] =strtoupper( $_REQUEST ['to_nations']);
		}
		
		// 平台类型
		if (! empty ( $_REQUEST ['platform'] )) {
			// platform 只要小写
			if (strtolower($_REQUEST ['platform']) != 'all'){
				$params ['order_source'] = strtolower($_REQUEST ['platform']);
			}
		}
		
		if (!empty($_REQUEST['shipmethod'])){
			$params['default_shipping_method_code'] = $_REQUEST['shipmethod'];
		}
		
		$is_send = '';
		if (!empty($_REQUEST['is_send'])){
			$is_send = strtoupper($_REQUEST['is_send']);
		}
		//提醒类型
		if (!empty($_REQUEST['pos'])){
			//RSHP=>启运通知,
			if($_REQUEST['pos']=='RSHP'){
				if($platform!=='ebay')
					$params['order_status']=[400,500];
				if(!empty($is_send))
					$params['shipping_notified'] = $is_send;
				else 
					$params['shipping_notified'] = ['Y','N'];
				$params['order_ship_time'] = 'not null';
			}
			/*
			//RPF=>到达待取通知,
			if($_REQUEST['pos']=='RPF'){
				$params['order_status']=[500];
				$params['pending_fetch_notified'] = ($is_send=='Y')?'Y':'N';
				$params['order_ship_time'] = 'not null';
			}
			//RRJ=>异常退回,
			if($_REQUEST['pos']=='RRJ'){
				$params['order_status']=[500];
				$params['rejected_notified'] = ($is_send=='Y')?'Y':'N';
				$params['order_ship_time'] = 'not null';
			}
			*/
			//RGE=>已签收求好评
			if($_REQUEST['pos']=='RGE'){
				if($platform!=='ebay')
					$params['order_status']=[500];
				if(!empty($is_send))
					$params['shipping_notified'] = $is_send;
				else 
					$params['shipping_notified'] = ['Y','N'];
				$params['order_ship_time'] = '-20days';
				//排除已经给了评价的订单
				$params['seller_commenttype'] = null;
			}
			
			//if ($_REQUEST['pos'] == 'RGE'){
			//	if (!empty($params['status'])) $params['status'] .= ',platform_confirmed';
			//}
		}
		
		//默认打开的列表记录数为上次用户选择的page size 数	//lzhl	2016-11-30
		$page_url = '/order/od-lt-message/list-tracking-message';
		$last_page_size = ConfigHelper::getPageLastOpenedSize($page_url);
		if(empty($last_page_size))
			$last_page_size = 50;//默认显示值
		if(empty($_REQUEST['per-page']) && empty($_REQUEST['page']))
			$pageSize = $last_page_size;
		else{
			$pageSize = empty($_REQUEST['per-page'])?50:$_REQUEST['per-page'];
		}
		ConfigHelper::setPageLastOpenedSize($page_url, $pageSize);
		
		
		//获取对应tracker记录list
		$list = OrderTrackingMessageHelper::getOrderTrackingMessageListDataByCondition($keyword,$params,$date_from,$date_to,$sort,$order,$pageSize);
		
		
		//获取用户常用的物流方式
		$using_carriers = TrackingHelper::getTrackerTempDataFromRedis("using_carriers" ); //ConfigHelper::getConfig("Tracking/using_carriers");
		 
		if (!empty($using_carriers))
			$using_carriers = json_decode($using_carriers,true);
		 
		/*//获取用户账号信息
		$all_account_data = PlatformAccountApi::getPlatformAllAccount($puid,$platform);
		$account_data = $all_account_data['data'];*/
		
		//获取 所有 tag 标签
		$AllTagData = TrackingTagHelper::getTagByTagID();
		 
		 
		$tag_class_list = TrackingTagHelper::getTagColorMapping();
		//获取国家数据
		$country_list = [];
		/*
		 $setConNations = ['CN','US'];
		ConfigHelper::setConfig("Tracking/to_nations", json_encode($setConNations));
		*/
		//$tmp_country_list = ConfigHelper::getConfig("Tracking/to_nations" );
		$tmp_country_list = TrackingHelper::getTrackerTempDataFromRedis("to_nations" );
		if (!empty($tmp_country_list)){
			if (is_string($tmp_country_list)){
				$country_code_lsit =  json_decode($tmp_country_list);
			}elseif(is_array($tmp_country_list)){
				$country_code_lsit = $tmp_country_list;
			}else{
				$country_code_lsit = [];
			}
		
			foreach($country_code_lsit as $code){
				$label = TrackingHelper::autoSetCountriesNameMapping($code);
				$country_list [$code] = $label;
			}
		}
		//print_r($list);
		
		if (!empty($_REQUEST['seller_id'])){
			$counter = OrderHelper::getMenuStatisticData($platform,['selleruserid'=>$_REQUEST['seller_id']]);
			$counter['todayorder'] = OdOrder::find()->where(['and',['order_source'=>$platform],['>=','order_source_create_time',strtotime(date('Y-m-d'))],['<','order_source_create_time',strtotime('+1 day')] ])
			->andWhere(['selleruserid'=>$_REQUEST['seller_id']])->count();
			$counter['sendgood'] = OdOrder::find()->where(['order_source'=>$platform])->andwhere('order_status>=200 and order_status < 300')
			->andWhere(['selleruserid'=>$_REQUEST['seller_id']])->count();
			//$claimOrderIDs = MessageCdiscountApiHelper::claimOrderIDs($_REQUEST['selleruserid']);
			$counter['issueorder'] = OdOrder::find()->where(['order_source'=>$platform,'issuestatus'=>'IN_ISSUE'])->andWhere(['selleruserid'=>$_REQUEST['seller_id']])->count();
		}else{
			$counter = OrderHelper::getMenuStatisticData($platform);
			$counter['todayorder'] = OdOrder::find()->where(['and',['order_source'=>$platform],['>=','order_source_create_time',strtotime(date('Y-m-d'))],['<','order_source_create_time',strtotime('+1 day')] ])->count();
			$counter['sendgood'] = OdOrder::find()->where(['order_source'=>$platform])->andwhere('order_status>=200 and order_status < 300')->count();
			//$claimOrderIDs = MessageCdiscountApiHelper::claimOrderIDs();
			$counter['issueorder'] = OdOrder::find()->where(['order_source'=>$platform,'issuestatus'=>'IN_ISSUE'])->count();
		}
		//$counter['newmessage'] = empty($claimOrderIDs['unRead'])?0:count($claimOrderIDs['unRead']['orderIds']);
		$counter['newmessage'] = 0;
		
		$warehouseids = InventoryApiHelper::getWarehouseIdNameMap();
		
		return $this->renderAjax('list_tracking_msg', [
				'models' => $list['data'],
				'pagination'=>$list['pagination'],
				'warehouseids'=>$warehouseids,
				'carriers'=>CarrierApiHelper::getShippingServices(),
				//'IsShowProgressBar'=> false,
				//'using_carriers'=> $using_carriers,
				'account_data'=> $account_data,
				'AllTagData'=>$AllTagData,
				'country_list'=>$country_list,
				'tag_class_list'=>$tag_class_list,
				'counter'=>$counter,
				]);
	}
	
	/* 忽略订单，使其不再于OMS的二次营销页面列表中显示
	 * @author		lzhl		2016/07/15		初始化
	 */
	public function actionIgnoreMsgSend($order_id){
		$uid = \Yii::$app->user->id;
		if (empty($uid))
			exit('您还未登录，不能进行该操作');
		
		if(empty($order_id) || trim($order_id)=='')
			exit('订单号有误，请选择有效的订单');
	
		$order_id = explode(',', $order_id);
		
		$rtn['success'] = true;
		$rtn['message'] = "";
	
		//更新状态到od_order_v2
		$orders = OdOrder::find()->where(['order_id'=>$order_id])->all();
		
		$journal_id = SysLogHelper::InvokeJrn_Create("Order",__CLASS__, __FUNCTION__ , array($order_id));
		$errMsg='';
		if(!empty($orders)){
			foreach ($orders as $order){
				$order->update_time = time();
				$order->shipping_notified = 'I';
				$order->pending_fetch_notified = 'I';
				$order->rejected_notified = 'I';
				$order->received_notified = 'I';
				
				if(!$order->save()){
					$rtn['success']= false;
					$rtn['message'].= "订单$order->order_source_order_id更新失败";
					$errMsg .= print_r($order->getErrors());
				}else 
					OperationLogHelper::log('order', $order->order_id,'忽略发信','设置为忽略发信通知及营销信息',\Yii::$app->user->identity->getFullName());
			}
		}else{
			$rtn['success'] = false;
			$rtn['message'] = "没有需要更新的订单!";
		}
		
		if(!empty($errMsg))
			$rtn['errMsg']= $errMsg;
		SysLogHelper::InvokeJrn_UpdateResult($journal_id, $rtn);
		exit(json_encode($rtn));
	}
	
	
	/**
	 +----------------------------------------------------------
	 * 显示 订单号 站内信与发送记录的 页面
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * @author		lzhl 	2016/07/16		初始化
	 +----------------------------------------------------------
	 **/
	public function actionStationLetterDetail(){
		//$this->changeDBPuid();
		$data = [];

		// 设置 order id
		if (!empty($_REQUEST['order_id'])){
			$order_id = explode(',', $_REQUEST['order_id']);
			Helper_Array::removeEmpty($order_id);
			//get message history
			$data['history'] = MessageHelper::getMessageDataByOrderId($order_id);
		}else{
			exit("empty");
		}
		if (!empty($_REQUEST['order_no'])){
			$data['order_no'] = $_REQUEST['order_no'];
		}
			
		if (!empty($_REQUEST['track_no'])){
			$track_no = explode(',', $_REQUEST['track_no']);
			Helper_Array::removeEmpty($track_no);
			$data['track_no'] = implode(',', $track_no);
		}
	
		if (!empty($_REQUEST['show_method'])){
			$data['show_method'] = $_REQUEST['show_method'];
		}
	
		//get all template data
		$data['listTemplate'] = MessageHelper::listAllTemplate();
		$data['listTemplateAddinfo'] = [];
		foreach($data['listTemplate']  as $oneTemplate){
			if (!empty($oneTemplate['addi_info'])){
				$data['listTemplateAddinfo'][$oneTemplate['id']]  = json_decode($oneTemplate['addi_info'],true);
			}else{
				$data['listTemplateAddinfo'][$oneTemplate['id']] = ['layout'=>1 , 'recom_prod_count'=>8];
			}
			$data['listTemplateAddinfo'][$oneTemplate['id']]['name'] = $oneTemplate['name'];
				
		}
		//get default template id
		//$data['defaultTemplate']  = TrackingHelper::getDefaultTemplate($TracknoList);
	
		$data['defaultTemplate']  = ['template_id'=>-2];//-2 = 自动匹配
	
		$data['hideSendMessageBtn'] = true;
	
		//get message history
	
		AppTrackerApiHelper::actionLog("order", "进入发信二次营销"  );
		
		$matchRT = OrderTrackingMessageHelper::matchMessageRole_Oms($order_id);
		$data['matchRoleTracking'] = $matchRT['matchRoleTracking'];
		$data['unMatchRoleTracking'] = $matchRT['unMatchRoleTracking'] ;
		$data['isSameSeller'] = $matchRT['isSameSeller'];
	
		return $this->renderPartial('station_letter_detail', [ 'data'=>$data
				]);
	}//end of actionStationLetterDetail
	

	/**
	 +----------------------------------------------------------
	 * 发送 站内信
	 * 复制自tracker,需要做一些兼容和同时将发送记录也保存到tracker
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * @author		lkh 	2014/05/06				初始化
	 +----------------------------------------------------------
	 **/
	public function actionSendMessage(){
		try {
			$trackNoList = [];
			$template = [];
			$addi_params = [];
			$template_addi_info = [];
			$isUpdate = true;
			if (!empty($_REQUEST['letter_template_name']))
				$template ['name']  =  $_REQUEST['letter_template_name'];
				
				
			if (!empty($_REQUEST['letter_template']))
				$template ['body']  =  $_REQUEST['letter_template'];
			else
				$template ['body']  =  '';
				
			if (!empty($_REQUEST['template_id'])){
				if ( $_REQUEST['template_id'] > 0 )
					$template ['id']  =  $_REQUEST['template_id'];
			}
			
			$orderIdList=[];
			if (!empty($_REQUEST['order_no_list'])){
				$orderNoList = explode(',', $_REQUEST['order_no_list']);
				$orders = OdOrder::find()->select('order_id')->where(['order_source_order_id'=>$orderNoList,'order_relation'=>['sm','normal']])->asArray()->all();
				foreach ($orders as $od){
					$orderIdList[] = $od['order_id'];
				}
			}
			
			if (!empty($_REQUEST['track_no_list'])){
				$trackNoList = explode(',', $_REQUEST['track_no_list']);
			}
			if (!empty($_REQUEST['subject'])){
				//$addi_params['subject'] = $_REQUEST['subject'];
				$template ['subject']  =  $_REQUEST['subject'];
			}

			if (!empty($_REQUEST['template_layout'])){
				$template_addi_info['layout'] = $_REQUEST['template_layout'];
				$LayOutId =$_REQUEST['template_layout'];
			}else{
				$LayOutId =1 ;
			}
			
			if (!empty($_REQUEST['recom_prod_count'])){
				$template_addi_info['recom_prod_count'] = $ReComProdCount = $_REQUEST['recom_prod_count'];
			}else{
				$ReComProdCount=8;
			}

			if (!empty($_REQUEST['recom_prod_group'])){
				$ReComGroup = $template_addi_info['recom_prod_group'] = $_REQUEST['recom_prod_group'];
			}else 
				$ReComGroup = $template_addi_info['recom_prod_group'] = 0;
			
				
			if (!empty($_REQUEST['path'])){
				$addi_params['path'] = $_REQUEST['path'];
			}
				
			if (!empty($_REQUEST['op_method'])){
				$addi_params['op_method'] = $_REQUEST['op_method'];
			}
				
			if (!empty($_REQUEST['msg_id'])){
				$addi_params['msg_id'] = $_REQUEST['msg_id'];
			}
				
			if (!empty($template_addi_info)){
				$template['addi_info'] = json_encode($template_addi_info) ;
			}

			//发信类型：启运/到达待取/异常退回/已签收求好评
			if(!empty($_REQUEST['pos'])){
				if($_REQUEST['pos']=='RSHP')
					$status = 'shipping';
				if($_REQUEST['pos']=='RPF')
					$status = 'arrived_pending_fetch';
				if($_REQUEST['pos']=='RRJ')
					$status = 'rejected';
				if($_REQUEST['pos']=='RGE')
					$status = 'received';
				if($_REQUEST['pos']=='DF')
					$status = 'delivery_failed';
			}else 
				$status='';
			
			if (!empty($_REQUEST['isUpdate'])){
	
				if (strtoupper($_REQUEST['isUpdate'])!='T'){
					if (strtoupper($_REQUEST['isUpdate'])=='M')
						$isUpdate = false;
						
					if (strtoupper($_REQUEST['isUpdate'])=='B')
						$isUpdate = true;
					
					$result = MessageHelper::sendStationMessageByOms($template , $orderIdList , $addi_params , $isUpdate, $status);
					//$result = MessageHelper::sendStationMessage($template , $trackNoList , $addi_params , $isUpdate);
					
					AppTrackerApiHelper::actionLog("Order", "批量发站内信",['paramstr1'=>implode(",", $orderIdList)] );
					
					//保存 数量 和layout id 到lt_tracking
					TrackingHelper::setMessageConfigByOms($orderIdList, $LayOutId, $ReComProdCount ,$template['recom_prod_group']);
						
				}else{
					$result = MessageHelper::saveMessageTemplate($template);
					AppTrackerApiHelper::actionLog("Tracker", "保存模板",['paramstr1'=>implode(",", $trackNoList)] );
				}
			}
			
		} catch (\Exception $e) {
			$result ['success'] = false;
			$result ['message'] = $e->getMessage();
		}
	
		exit(json_encode ( $result ));
	}//end of actionSendMessage
	
	/**
	 +----------------------------------------------------------
	 * 根据规则发送 站内信
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh 	2014/07/22				初始化
	 +----------------------------------------------------------
	 **/
	public function actionSendMessageByRole(){
		$addi_params = [];
		
		//发信类型：启运/到达待取/异常退回/已签收求好评
		if(!empty($_REQUEST['pos'])){
			if($_REQUEST['pos']=='RSHP')
				$status = 'shipping';
			if($_REQUEST['pos']=='RPF')
				$status = 'arrived_pending_fetch';
			if($_REQUEST['pos']=='RRJ')
				$status = 'rejected';
			if($_REQUEST['pos']=='RGE')
				$status = 'received';
			if($_REQUEST['pos']=='DF')
				$status = 'delivery_failed';
		}else
			$status='';
		
		if (!empty($_REQUEST['order_id_mapping_role'])){
			foreach($_REQUEST['order_id_mapping_role'] as $tempalate_id=>$orderIdList){
				$template = MsgTemplate::find()->where(['id'=>$tempalate_id])->asArray()->one();
				$isUpdate = false;
				$result = MessageHelper::sendStationMessageByOms($template, $orderIdList, $addi_params, $isUpdate,$status);
				
				$addi_info = json_decode($template['addi_info'],true);
					
				//$LayOutId = empty($addi_info['layout_id'])?1:$addi_info['layout_id'];
				$LayOutId = 1;
				//$ReComProdCount = empty($addi_info['recom_prod_count'])?8:$addi_info['recom_prod_count'];
				$ReComProdCount = 8;
				$ReComProdGroup = empty($addi_info['recom_prod_group'])?0:$addi_info['recom_prod_group'];
				
				//保存 数量 和layout id 到lt_tracking
				TrackingHelper::setMessageConfigByOms($orderIdList, $LayOutId, $ReComProdCount, $ReComProdGroup);
				
				if (!empty($_REQUEST['track_no_mapping_role'][$tempalate_id])){
					TrackingHelper::setMessageConfig($_REQUEST['track_no_mapping_role'][$tempalate_id], $LayOutId, $ReComProdCount );
				}
			}
			
			AppTrackerApiHelper::actionLog("order", "规则匹配发信",['paramstr1'=>json_encode($_REQUEST['order_id_mapping_role'])] );
		}else{
			$result=['success'=>false,'message'=>'请选择订单'];
		}
	
		exit(json_encode ( $result ));
	
	}//end of actionSendMessageByRole
	
	/**
	 +----------------------------------------------------------
	 * 获取当前订单号匹配规则的数据
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * @author		lzhl 	2016/07/19			初始化
	 +----------------------------------------------------------
	 **/
	public function actionRefreshMatchRole(){
		$data = [];
	
		if (!empty($_REQUEST['is_decode']))
			$isdecode =true;
		else
			$isdecode = false;
		// 设置 order no
		if (! empty($_REQUEST['order_no'])){
			/**/
			if ($isdecode){
				$orderNoList = explode(',', $_REQUEST['order_no']);
				$data['order_no'] = implode(',', $orderNoList);
			}else{
				$orderNoList = $_REQUEST['order_no'];
				$data['order_no'] = implode(',', $orderNoList);
	
			}
		}
	
		if (empty($data['order_no'])){
			exit("empty");
		}
	
		$matchRT = OrderTrackingMessageHelper::matchMessageRole_Oms($orderNoList);
		//$matchRT = TrackingHelper::matchMessageRole($TracknoList);
		$data['match_data'] = $matchRT['matchRoleTracking'];
		$data['unmatch_data'] = $matchRT['unMatchRoleTracking'] ;
		exit(json_encode($data));
	}//end of actionrefreshMatchRole
	
	/**
	 +----------------------------------------------------------
	 * 站内信的 获取 预览数据的 action
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh 	2014/05/14				初始化
	 +----------------------------------------------------------
	 **/
	public function actionPreviewMessage(){
		$result = [];
		if (!empty($_GET['order_no_list'])){
			$order_no_list = explode(',', $_GET['order_no_list']);
			$puid = \Yii::$app->subdb->getCurrentPuid();
			AppApiHelper::turnOnUserFunction($puid, 'tracker_recommend');
			
			if (is_array($order_no_list)){
				foreach($order_no_list as $order_no){
					$subject = $_GET['subject'];
					$template = $_GET['template'];
						
					if (stripos( $_GET['template'],'买家查看包裹追踪及商品推荐链接')){
						$result [$order_no] ['recom_prod'] = 'Y';
					}else{
						$result [$order_no] ['recom_prod'] = 'N';
					}
						
					$result [$order_no] = MessageHelper::replaceTemplateDataByOms($subject, $template, $order_no);
					if (!empty($result [$order_no] ['template']))
						$result [$order_no] ['template'] = nl2br($result [$order_no] ['template']);
					$aTracking = Tracking::find()->where(['order_id'=>$order_no])->asArray()->One();
					$result [$order_no] ['tail'] = MessageBGJHelper::make17TrackMessageTail($puid,'',$aTracking);
					if (!empty($aTracking['id']))
						$result [$order_no]['recom_prod_preview_link'] = "http://littleboss.17track.net/message/tracking/index?parcel=". MessageHelper::encryptBuyerLinkParam($puid, $aTracking['id']);
					else{
						$order=OdOrder::find()->where(['order_source_order_id'=>$order_no])->asArray()->One();
						if(!empty($order))
							$result [$order_no]['recom_prod_preview_link'] = "http://littleboss.17track.net/message/tracking/index?parcel=". MessageHelper::encryptBuyerLinkParam($puid, $order['order_id'],'order_id');
						else 
							$result [$order_no]['recom_prod_preview_link'] = "";
					}
				}
			}
		}
		exit(json_encode ( $result ));
	}//end of actionPreviewMessage
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 设置 指定 订单号 的推荐商品 页面的布局  和 推荐商品数量
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param
	 +---------------------------------------------------------------------------------------------
	 * @author		lzhl		2016/7/19			初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	public function actionSetProductRecommendSetting(){
		if (! empty($_REQUEST['order_no'])){
			/*
			if (! empty($_REQUEST['layout_id']))
				$layout_id = $_REQUEST['layout_id'];
			else
				$layout_id = 1;
			*/
			$layout_id = 1;
			
			/*
			if (!empty($_REQUEST['product_count']))
				$product_count  = $_REQUEST['product_count'];
			else
				$product_count = 8;
			*/
			$product_count = 8;
				
			if (!empty($_REQUEST['recom_prod_group']))
				$recom_prod_group  = $_REQUEST['recom_prod_group'];
			else
				$recom_prod_group = 0;
			
			$orderNoList[] = $_REQUEST['order_no'];
			TrackingHelper::setMessageConfigByOms($orderNoList,$layout_id,$product_count,$recom_prod_group);
		}
	
	
	}//end of actionSetProductRecommendSetting
	
	public function actionSetStoreMailInfo(){
		if(empty($_REQUEST['platform']))
			exit('需要先选择平台！');
		$platform = trim($_REQUEST['platform']);
		$listStoreMailInfo = OrderTrackingMessageHelper::listPlatformStoreMailAddressSetting($platform);
		
		return $this->renderAjax('_set_store_mail_info',[
				'platform'=>$platform,
				'listStoreMailInfo'=>$listStoreMailInfo,
			]);
	}
	
	public function actionSaveMailInfo(){
		if(empty($_REQUEST['platform']))
			exit(json_encode(['success'=>false,'message'=>'平台信息缺失，操作失败！']));
		if(empty($_REQUEST['store_name']))
			exit(json_encode(['success'=>false,'message'=>'没有店铺信息，操作失败！']));
		
		foreach ($_REQUEST['mail_address'] as $mail_address){
			if(!empty($mail_address)){
				if(!preg_match('/^([a-z0-9]*[-_]?[a-z0-9]+)*@([a-z0-9]*[-_]?[a-z0-9]+)+[\.][a-z]+([\.][a-z]+)?$/i',$mail_address)){
					exit(json_encode(['success'=>false,'message'=>'一旦填写了邮箱地址，则必须填写正确的邮箱格式！']));
				}	
			}
		}
		
		$rtn = OrderTrackingMessageHelper::savePlatformStoreMailInfo($_REQUEST);
		exit(json_encode($rtn));
	}
	
	public function actionViewQuotaHistory(){
		$puid = \Yii::$app->user->identity->getParentUid();
		$quotaInfo = UserEdmQuota::findOne($puid);
		if(empty($quotaInfo)){
			$remaining_quota = '--';
			$historys[] = '未使用过邮件发送。';
		}else{
			$remaining_quota = $quotaInfo->remaining_quota;
			$addi_info = json_decode($quotaInfo->addi_info,true);
			if(!empty($addi_info['payment_record']))
				$historys = $addi_info['payment_record'];
			else 
				$historys = '';
		}
		rsort($historys);
		return $this->renderPartial('_view_quota_history',[
				'remaining_quota'=>$remaining_quota,
				'historys'=>$historys,
			]);
	}
	
	/*
	 * 发信模板设置 页面
	 */
	public function actionMail_template_setting(){

		if(isset($_GET['sort'])){
			$sort = $_GET['sort'];
			if( '-' == substr($sort,0,1) ){
				$sort = substr($sort,1);
				$order = 'desc';
			} else {
				$order = 'asc';
			}
		}else{
			$sort = 'id';
			$order = 'asc';
		}
		$sortConfig = new Sort(['attributes' => ['id','name','subject']]);
		if(!in_array($sort, array_keys($sortConfig->attributes))){
			$sort = '';
			$order = '';
		}
	
		$templateData =TrackingHelper::getMsgTemplate($sort, $order);
	
	
		//get all template data
		$data['listTemplate'] = $templateData['data'];
		$data['listTemplateAddinfo'] = [];
		foreach($data['listTemplate']  as $oneTemplate){
			if (!empty($oneTemplate['addi_info'])){
				$data['listTemplateAddinfo'][$oneTemplate['id']]  = json_decode($oneTemplate['addi_info'],true);
			}else{
				$data['listTemplateAddinfo'][$oneTemplate['id']] = ['layout'=>1 , 'recom_prod_count'=>4];
			}
			$data['listTemplateAddinfo'][$oneTemplate['id']]['name'] = $oneTemplate['name'];
	
		}
	
		return $this->renderAjax('mail_template_setting',[
				'templateData'=>$templateData,
				'sortConfig'=>$sortConfig,
				'data'=>$data,
				]);
	
	}
	
	public function actionCustom_product_list(){
		$puid = \Yii::$app->user->identity->getParentUid();
		$platforms = [];
		$binding_account = PlatformAccountApi::getAllPlatformBindingSituation(array(),$puid);
		if(!empty($binding_account)){//查看绑定平台
			foreach ($binding_account as $account_key =>$account_value){
				if($account_key){
					$platforms[$account_key] = $account_key;
				}
			}
		}
	
		$query = LtCustomizedRecommendedProd::find()->where(["puid"=>$puid]);
	
		if(!empty($_REQUEST['platform_search'])){
			$query->andWhere(['platform'=>$_REQUEST['platform_search']]);
		}
		if(!empty($_REQUEST['seller_search'])){
			$query->andWhere(['seller_id'=>$_REQUEST['seller_search']]);
		}
		if(!empty($_REQUEST['condition_search'])){
			$query->andWhere(['and', "title like :search or sku like :search "],[':search'=>'%'.$_REQUEST['condition_search'].'%']);
		}
		
		//只显示有权限的账号，lrq20170828
		$platformAccountInfo = PlatformAccountApi::getAllAuthorizePlatformOrderSelleruseridLabelMap(false, false, true);
		foreach($platformAccountInfo as $key => $val){
			if(is_array($val)){
				foreach($val as $key2 => $val2){
					$selleruserids[] = $key2;
				}
			}
		}
		$query->andWhere(['seller_id'=>$selleruserids]);
	
		$pages = new Pagination(['totalCount' =>$query->count(), 'defaultPageSize'=>20 , 'pageSizeLimit'=>[5,200] , 'params'=>$_REQUEST]);
		$prodData = $query->orderBy(['create_time'=>SORT_DESC])->offset($pages->offset)->limit($pages->limit)->asArray()->all();
	
		return $this->renderAjax('custom-product-list',['data'=>$prodData,'pages'=>$pages,'platform'=>$platforms]);
	}
	
	public function actionGroup_list(){
		$puid = \Yii::$app->user->identity->getParentUid();
		$query =LtCustomizedRecommendedGroup::find()->where(["puid"=>$puid]);
	
		$platforms = [];
		$binding_account = PlatformAccountApi::getAllPlatformBindingSituation(array(),$puid);
		if(!empty($binding_account)){//查看绑定平台
			foreach ($binding_account as $account_key =>$account_value){
				if($account_key){
					$platforms[$account_key] = $account_key;
				}
			}
		}
	
		if(!empty($_REQUEST['platform_search'])){
			$query->andWhere(['platform'=>$_REQUEST['platform_search']]);
		}
		if(!empty($_REQUEST['seller_search'])){
			$query->andWhere(['seller_id'=>$_REQUEST['seller_search']]);
		}
		if(!empty($_REQUEST['condition_search'])){
			$query->andWhere(['and', "group_name like :search or group_comment like :search "],[':search'=>'%'.$_REQUEST['condition_search'].'%']);
		}
		
		//只显示有权限的账号，lrq20170828
		$platformAccountInfo = PlatformAccountApi::getAllAuthorizePlatformOrderSelleruseridLabelMap(false, false, true);
		foreach($platformAccountInfo as $key => $val){
			if(is_array($val)){
				foreach($val as $key2 => $val2){
					$selleruserids[] = $key2;
				}
			}
		}
		$query->andWhere(['seller_id'=>$selleruserids]);
	
		$pages = new Pagination(['totalCount' =>$query->count(), 'defaultPageSize'=>20 , 'pageSizeLimit'=>[5,200] , 'params'=>$_REQUEST]);
		$prodData = $query->orderBy(['create_time'=>SORT_DESC])->offset($pages->offset)->limit($pages->limit)->asArray()->all();
	
		return $this->renderAjax('group-list',['data'=>$prodData,'pages'=>$pages,'platform'=>$platforms]);
	}
	
	public function actionCustomizedRecommendedProduct(){
		$puid = \Yii::$app->user->identity->getParentUid();
		//取缓存
	
		$userHabit = json_decode(ConfigHelper::getConfig("ErciCustomeProduct/CustomUserHabit","NO_CACHE"),true);
	
		$platforms = [];
		$binding_account = PlatformAccountApi::getAllPlatformBindingSituation(array(),$puid);
		if(!empty($binding_account)){//查看绑定平台
			foreach ($binding_account as $account_key =>$account_value){
				if($account_key){
					$platforms[$account_key] = $account_key;
				}
			}
		}
	
		$groups = LtCustomizedRecommendedGroup::find()->where(['puid'=>$puid])->asArray()->all();
		$group_array = array();
		if(!empty($groups)){
			foreach ($groups as $detail_group){
				$group_array[$detail_group['id']] = $detail_group['group_name'];
			}
		}
		return $this->renderAjax('_recommended-product-list',['group_array'=>$group_array,'platform_array'=>$platforms,'userHabit'=>$userHabit]);
	}
	
	

	public function actionAddProductsToGroup(){
		$puid = \Yii::$app->user->identity->getParentUid();
	
		$ids = '';
		if(!empty($_POST['ids'])){
			$ids = json_encode($_POST['ids']);
		}
	
		$groups = LtCustomizedRecommendedGroup::find()->where(['puid'=>$puid])->asArray()->all();
		$group_array = array();
		if(!empty($groups)){
			foreach ($groups as $detail_group){
				$group_array[$detail_group['id']] = $detail_group['group_name'];
			}
		}
		return $this->renderAjax('_add-product-group',['ids'=>$ids,'group_array'=>$group_array]);
	}
	
	public function actionAddProductsToGroupSave(){
		$puid = \Yii::$app->user->identity->getParentUid();
	
		if(!empty($_POST['product_ids'])&&!empty($_POST['group_name'])){
			$product_ids = json_decode($_POST['product_ids'],true);
			//批量加入商品组，改变商品组的数量以及检查是否符合加入商品组
			$orgin_group_id = '';
			$new_group_id = '';
			$add_result = [
			'success'=>0,
			'fail'=>0
			];
			$group_data = LtCustomizedRecommendedGroup::find()->where(['id'=>$_POST['group_name'],'puid'=>$puid])->one();
			if(empty($group_data)){
				return ResultHelper::getResult(400,'','添加到商品组失败，没有相关商品组数据');
			}
			$group_data_count = $group_data->member_count;
	
			$custom_prouduct = LtCustomizedRecommendedProd::find()->where(['id'=>$product_ids,'puid'=>$puid])->all();
			if(!empty($custom_prouduct)&&!empty($group_data)){
				foreach ($custom_prouduct as $detail_prouduct){
					$orgin_group_id = $detail_prouduct->group_id;
					$new_group_id = $_POST['group_name'];
					if($detail_prouduct->platform == $group_data->platform && $detail_prouduct->seller_id == $group_data->seller_id && $detail_prouduct->group_id != $_POST['group_name']){//平台与帐号必须要一致
						$detail_prouduct->group_id = $_POST['group_name'];
						if($detail_prouduct->save(false)){
							$group_data_count = $group_data_count + 1;
							$add_result['success'] = $add_result['success'] + 1;
							if($orgin_group_id != ''){//假如保存成功且原来的商品组id不为空，需要维护被移走的商品组数量
								$orgin_group_data = LtCustomizedRecommendedGroup::find()->where(['id'=>$orgin_group_id,'puid'=>$puid])->one();
								if(!empty($orgin_group_data)){
									$orgin_group_data->member_count = $orgin_group_data->member_count - 1;
									$orgin_group_data->save(false);
								}
							}
						}else{
							$add_result['fail'] = $add_result['fail'] + 1;
						}
					}else{
						$add_result['fail'] = $add_result['fail'] + 1;
					}
				}
				$group_data->member_count = $group_data_count;//统计
				if($group_data->save(false)){
					return ResultHelper::getResult(200,$add_result,'添加到商品组完成');
				}
			}else{
				return ResultHelper::getResult(400,'','添加到商品组失败，没有相关自定义商品数据');
			}
			//             $custom_prouduct = new LtCustomizedRecommendedProd();
			//             if($custom_prouduct::updateAll(['group_id'=>$_POST['group_name']],['id'=>$product_ids])){
			//                 return ResultHelper::getResult(200,'','添加到商品组成功');
			//             }else{
			//                 return ResultHelper::getResult(400,'','添加到商品组失败');
			//             }
		}else{
			return ResultHelper::getResult(400,'','选择商品参数有误!');
		}
	}
	 
	public function actionEditProduct(){
		$puid = \Yii::$app->user->identity->getParentUid();
		
		$platforms = [];
		$binding_account = PlatformAccountApi::getAllPlatformBindingSituation(array(),$puid);
		if(!empty($binding_account)){//查看绑定平台
			foreach ($binding_account as $account_key =>$account_value){
				if($account_key){
					$platforms[$account_key] = $account_key;
				}
			}
		}
		$data = LtCustomizedRecommendedProd::find()->where(['id'=>$_POST['product_id']])->asArray()->one();
	
		$groups = LtCustomizedRecommendedGroup::find()->where(['puid'=>$puid])->asArray()->all();
		$group_array = array();
		$groups_detail = array();
		if(!empty($groups)){
			foreach ($groups as $detail_group){
				$group_array[$detail_group['id']] = $detail_group['group_name'];
				$groups_detail[$detail_group['id']] = $detail_group;
			}
		}
		return $this->renderAjax('_recommended-product-list',['data'=>$data,'group_array'=>$group_array,'platform_array'=>$platforms,'groups_detail'=>$groups_detail]);
	}
	
	public function actionNewGroup(){
		$puid = \Yii::$app->user->identity->getParentUid();
		$platforms = [];
		$binding_account = PlatformAccountApi::getAllPlatformBindingSituation(array(),$puid);
		if(!empty($binding_account)){//查看绑定平台
			foreach ($binding_account as $account_key =>$account_value){
				if($account_key){
					$platforms[$account_key] = $account_key;
				}
			}
		}
		return $this->renderAjax('_custom-group',['platform_array'=>$platforms]);
	}
	
	

	public function actionEditGroupList(){
		$puid = \Yii::$app->user->identity->getParentUid();
	
		$platforms = [];
		$binding_account = PlatformAccountApi::getAllPlatformBindingSituation(array(),$puid);
		if(!empty($binding_account)){//查看绑定平台
			foreach ($binding_account as $account_key =>$account_value){
				if($account_key){
					$platforms[$account_key] = $account_key;
				}
			}
		}
		$one_group = LtCustomizedRecommendedGroup::findOne($_REQUEST['id']);
		//获取该平台下、该puid下的所有帐号
		$seller_array = array();
		if(!empty($one_group)){
			$account_result = PlatformAccountApi::getPlatformAllAccount($puid, $one_group['platform']);
			if($account_result['success']){
				foreach ($account_result['data'] as $seller_key => $seller_value){
					$seller_array[$seller_key] = $seller_value;
				}
			}
		}
	
		$query = LtCustomizedRecommendedProd::find()->where(["puid"=>$puid,"group_id"=>$_REQUEST['id']]);
		$pages = new Pagination(['totalCount' =>$query->count(), 'defaultPageSize'=>20 , 'pageSizeLimit'=>[5,200] , 'params'=>$_REQUEST]);
		$prodData = $query->orderBy(['create_time'=>SORT_DESC])->offset($pages->offset)->limit($pages->limit)->asArray()->all();
	
		return $this->renderAjax('_edit-group-list',['data'=>$prodData,'pages'=>$pages,'group_data'=>$one_group,'platform_array'=>$platforms,'seller_array'=>$seller_array]);
	}
	
	public function actionEditGroupListSave(){
		if(!isset($_POST['groupId'])){
			return ResultHelper::getResult(400,'','商品组数据有误，保存失败！');
		}
		$puid = \Yii::$app->user->identity->getParentUid();
		$one_product = LtCustomizedRecommendedGroup::find()->where(['puid'=>$puid,'id'=>$_POST['groupId']])->one();
		if(empty($one_product)){
			return ResultHelper::getResult(400,'','没有找到相关的商品组');
		}else{
			$one_product->seller_id = $_POST['seller_id'];
			$one_product->platform = $_POST['platform'];
			$one_product->group_name = $_POST['group_name'];
			$one_product->group_comment = $_POST['group_comment'];
			$one_product->update_time = time();
	
			if(!$one_product->save(false)){
				\Yii::info('$new_product->save() fail , error:'.print_r($new_product->errors,true),"file");
				return ResultHelper::getResult(400, '', "编辑商品组失败，".$new_product->errors);
			}else{
				return ResultHelper::getResult(200, '', "编辑商品组成功。");
			}
		}
	}
	
	public function actionSetServiceDeliveryDays(){
		$method_code = isset($_REQUEST['method_code'])?$_REQUEST['method_code']:'';
		if(empty($method_code))
			exit('错误操作！没有选择服务商！');
		
		$queue = SysShippingService::find();
		if ($method_code!=='all'){
			$queue->where(['id'=>1]);
		}
		
		$arr = $queue->select(['id','service_name'])->orderBy('service_name asc')->asArray()->all();
		
		$ShippingService = Helper_Array::toHashmap($arr, 'id','service_name');
		
		$settings = ConfigHelper::getConfig('UserSettingdServiceDeliveryDays','NO_CACHE');
		$settings = empty($settings)?[]:json_decode($settings,true);
		
		$service_setting = [];
		foreach ($ShippingService as $id=>$name){
			$service_setting[(string)$id] = ['name'=>$name,'days'=>20];
		}
		
		foreach ($settings as $id=>$days){
			if( isset($service_setting[(string)$id]) ){
				$service_setting[(string)$id]['days'] = (int)$days;
			}
		}
		
		return $this->renderAjax('_set_service_delivery_days',[
			'service_setting'=>$service_setting,
				
		]);
	}
	
	public function actionSaveDeliveryDaysSetting(){
		$delivery_days = empty($_REQUEST['delivery_days'])?[]:$_REQUEST['delivery_days'];
		if(empty($delivery_days))
			exit(json_encode(['success'=>false,'message'=>'没有提交任何数据！']));
		
		try{
			$settings = ConfigHelper::getConfig('UserSettingdServiceDeliveryDays','NO_CACHE');
			$settings = empty($settings)?[]:json_decode($settings,true);
			
			foreach ($delivery_days as $id=>$days){
				$id = (string)$id;
				$settings[$id] = (int)$days;
			}
			$rtn = ConfigHelper::setConfig('UserSettingdServiceDeliveryDays', json_encode($settings));
		}catch (\Exception $e) {
			$puid = \Yii::$app->user->identity->getParentUid();
			SysLogHelper::InvokeJrn_Create("OdLtMessage",__CLASS__, __FUNCTION__ , array($puid,$delivery_days, print_r($e->getMessage(),true)));
			exit(json_encode(['success'=>false,'message'=>'保存设置是发送错误，请联系客服']));
		}
		
		exit(json_encode(['success'=>true,'message'=>'']));
	}
	
	public function actionTestRole(){
		$_REQUEST = [
			'order_no_mapping_role'=>[
				10=>[
					262438128564-1866457696016,
				],
			],
			'track_no_mapping_role'=>[
				10=>[],
			],
			'pos'=>'RGE',
		];
		$addi_params = [];
	
		//发信类型：启运/到达待取/异常退回/已签收求好评
		if(!empty($_REQUEST['pos'])){
			if($_REQUEST['pos']=='RSHP')
				$status = 'shipping';
			if($_REQUEST['pos']=='RPF')
				$status = 'arrived_pending_fetch';
			if($_REQUEST['pos']=='RRJ')
				$status = 'rejected';
			if($_REQUEST['pos']=='RGE')
				$status = 'received';
			if($_REQUEST['pos']=='DF')
				$status = 'delivery_failed';
		}else
			$status='';
	
		if (!empty($_REQUEST['order_no_mapping_role'])){
			foreach($_REQUEST['order_no_mapping_role'] as $tempalate_id=>$orderNoList){
				$template = MsgTemplate::find()->where(['id'=>$tempalate_id])->asArray()->one();
				$isUpdate = false;
				echo "<br>ts1";
				$result = MessageHelper::sendStationMessageByOms($template, $orderNoList, $addi_params, $isUpdate,$status);
				echo "<br>ts2";
				$addi_info = json_decode($template['addi_info'],true);
					
				//$LayOutId = empty($addi_info['layout_id'])?1:$addi_info['layout_id'];
				$LayOutId = 1;
				//$ReComProdCount = empty($addi_info['recom_prod_count'])?8:$addi_info['recom_prod_count'];
				$ReComProdCount = 8;
				$ReComProdGroup = empty($addi_info['recom_prod_group'])?0:$addi_info['recom_prod_group'];
	
				//保存 数量 和layout id 到lt_tracking
				TrackingHelper::setMessageConfigByOms($orderNoList, $LayOutId, $ReComProdCount, $ReComProdGroup);
				echo "<br>ts3";
				if (!empty($_REQUEST['track_no_mapping_role'][$tempalate_id])){
					echo "<br>ts4";
					TrackingHelper::setMessageConfig($_REQUEST['track_no_mapping_role'][$tempalate_id], $LayOutId, $ReComProdCount );
					echo "<br>ts5";
				}
			}
			echo "<br>ts6";
			AppTrackerApiHelper::actionLog("order", "规则匹配发信",['paramstr1'=>json_encode($_REQUEST['order_no_mapping_role'])] );
			echo "<br>ts7";
		}else{
			$result=['success'=>false,'message'=>'请选择订单'];
		}
	
		exit(json_encode ( $result ));
	
	}//end of actionSendMessageByRole
}
