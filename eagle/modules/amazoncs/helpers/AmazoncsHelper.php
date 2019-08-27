<?php
/**
 * @link http://www.witsion.com/
 * @copyright Copyright (c) 2014 Yii Software LLC
 * @license http://www.witsion.com/
 */
namespace eagle\modules\amazoncs\helpers;

use yii;
use yii\base\Exception;
use eagle\modules\order\models\OdOrder;
use eagle\modules\platform\apihelpers\PlatformAccountApi;
use eagle\modules\dash_board\apihelpers\DashBoardStatisticHelper;
use eagle\modules\message\apihelpers\MessageApiHelper;
use common\helpers\Helper_Array;
use eagle\modules\util\helpers\SysLogHelper;
use eagle\modules\util\helpers\RedisHelper;
use eagle\modules\util\helpers\MailHelper;
use eagle\modules\amazoncs\models\CsQuestTemplate;
use yii\data\Pagination;
use eagle\modules\util\helpers\TimeUtil;
use eagle\modules\amazoncs\models\CsMailQuestList;
use eagle\modules\amazon\apihelpers\AmazonApiHelper;
use eagle\modules\amazon\apihelpers\AmazonSyncFetchOrderApiHelper;
use eagle\modules\catalog\models\Product;
use eagle\modules\order\models\OdOrderItem;
use eagle\modules\amazoncs\models\AmazonFeedbackInfo;
use eagle\modules\amazoncs\models\CsSellerEmailAddress;
use eagle\modules\order\helpers\OrderTrackerApiHelper;
use eagle\modules\tracking\helpers\TrackingApiHelper;
use eagle\modules\message\helpers\MessageHelper;
use eagle\models\sys\SysCountry;
use eagle\modules\util\helpers\SQLHelper;
use eagle\modules\message\helpers\EdmHelper;
use eagle\modules\amazoncs\models\AmazonReviewInfo;
use eagle\modules\util\helpers\ConfigHelper;

/**
 +------------------------------------------------------------------------------
 * Amazon客服helper
 +------------------------------------------------------------------------------
 * @category	amazoncs
 * @author		lzhl <zhiliang.lu@witsion.com>
 * @version		1.0
 +------------------------------------------------------------------------------
 */

class AmazoncsHelper{
	private static $queueVersion = '';
	
	/**
	 * 获取任务模板列表
	 * @param	array	$condition
	 * @return	array
	 * @author	lzhl	2017/02		初始化
	 */
	public static function getAmazoncsTemplateList($condition){
		
		$query = CsQuestTemplate::find()->where("id <> 0");
		if(!empty($condition['platform']))
			$query->andWhere(['platform'=>$condition['platform']]);
		if(!empty($condition['seller_id']))
			$query->andWhere(['seller_id'=>$condition['seller_id']]);
		if(!empty($condition['site_id']))
			$query->andWhere(['site_id'=>$condition['site_id']]);
		if(!empty($condition['name']))
			$query->andWhere(['like','name',$condition['name']]);
		
		$pagination = new Pagination([
				'defaultPageSize' => 20,
				'totalCount' => $query->count(),
				'pageSizeLimit'=>[5,200],//每页显示条数范围
				'params'=>$condition,
				]);
		
		$models = $query->offset($pagination->offset)
			->limit($pagination->limit)
			->all();
		
		return ['models'=>$models,'pagination'=>$pagination];
	}

	/**
	 * 保存任务模板设置
	 * @param	object	$template	模板model
	 * @param	array	$data		改动的数据
	 * @return	array				修改结果
	 * @author	lzhl	2017/02		初始化
	 */
	public static function saveTemplate($template, $data=[]){
		$rtn = ['success'=>true,'message'=>''];
		$puid = \Yii::$app->subdb->getCurrentPuid();
		
		$journal_id = SysLogHelper::InvokeJrn_Create("Amazoncs",__CLASS__, __FUNCTION__ , array( 'uid'=>\Yii::$app->user->id, 'data'=>json_encode($data) ));
		$err_msg = '';
		
		try{
			$template->name = $data['name'];
			$template->subject = $data['subject'];
			$template->contents = $data['contents'];
			$template->send_after_order_created_days = $data['send_after_order_created_days'];
			$template->order_in_howmany_days = $data['order_in_howmany_days'];
			$template->send_one_pre_howmany_days = empty($data['send_one_pre_howmany_days'])?0:$data['send_one_pre_howmany_days'];
			$template->filter_order_item_type = empty($data['filter_order_item_type'])?'non':$data['filter_order_item_type'];
			$template->order_item_key_type = empty($data['order_item_key_type'])?'':$data['order_item_key_type'];
			$template->auto_generate  =empty($data['auto_generate'])?0:$data['auto_generate'];
			
			//标记有模板需要自动生成
			try{
				self::registAmzCsAutoGenerateUsre($puid,empty($template->id)?'':$template->id,$template->auto_generate);
			}catch(\Exception $e) {
				$rtn['success'] = 0;
				$rtn['message'] = '后台操作出错，请联系客服';
				$err_msg = print_r($e->getMessage());
			}
			
			$order_item_keys_str = empty($data['order_item_keys'])?'':$data['order_item_keys'];
			if(!empty($order_item_keys_str)){
				$order_item_keys_str = str_replace('；', ';', $order_item_keys_str);
				$order_item_keys_arr = explode(';', $order_item_keys_str);
				Helper_Array::removeEmpty($order_item_keys_arr);
				$order_item_keys_str = implode(';', $order_item_keys_arr);
			}
			$template->order_item_keys = $order_item_keys_str;
			
			if(!empty($data['for_order_type'])){
				if(is_array($data['for_order_type']))
					$template->for_order_type = implode(';', $data['for_order_type']);
				elseif(is_string($data['for_order_type']))
					$template->for_order_type = $data['for_order_type'];
				else 
					$template->for_order_type ='';
			}else 
				$template->for_order_type = '';
			
			if(!empty($data['pending_send_time'])){
				$template->pending_send_time = $data['pending_send_time'];
			}else 
				$template->pending_send_time = '';//为空时，表示 ‘不考虑时段，尽快发送’
			
			if(empty($template->create_time) || $template->create_time=='0000-00-00 00:00:00')
				$template->create_time = TimeUtil::getNow();
			
			$template->update_time = TimeUtil::getNow();
			
			$template->platform = empty($data['platform'])?'':$data['platform'];
			$template->seller_id = empty($data['seller_id'])?'':$data['seller_id'];
			$template->site_id = empty($data['site_id'])?'':$data['site_id'];
			
			$template->can_send_when_reviewed = (!empty($data['can_send_when_reviewed']) && $data['can_send_when_reviewed']='N')?0:1;
			$template->can_send_when_feedbacked = (!empty($data['can_send_when_feedbacked']) && $data['can_send_when_feedbacked']=='N')?0:1;
			$template->can_shen_when_contacted = (!empty($data['can_shen_when_contacted']) && $data['can_shen_when_contacted']=='N')?0:1;
			$template->can_send_to_blacklist_buyer = (!empty($data['can_send_to_blacklist_buyer']) && $data['can_send_to_blacklist_buyer']=='N')?0:1;
			
			$addi_info = empty($template->addi_info)?[]:json_decode($template->addi_info,true);
			
			$addi_url_title = [];
			if(!empty($data['query_url_title']))
				$addi_url_title['query_url'] = $data['query_url_title'];
			if(!empty($data['contact_seller_url_title']))
				$addi_url_title['contact_seller'] = $data['contact_seller_url_title'];
			if(!empty($data['feedback_url_title']))
				$addi_url_title['feedback_url'] = $data['feedback_url_title'];
			$addi_info['url_title'] = $addi_url_title;
			
			if(!empty($data['recommended_group']))
				$addi_info['recommended_group'] = (int)$data['recommended_group'];
			else 
				$addi_info['recommended_group'] = 0;
			
			$template->addi_info = json_encode($addi_info);
			
			if(!$template->save()){
				$rtn['success'] = false;
				$rtn['message'] = '保存信息时出现错误，请联系客服';
				$err_msg = print_r($template->getErrors(),true);
			}
		}catch(\Exception $e) {
			$rtn['success'] = 0;
			$rtn['message'] = '后台操作出错，请联系客服';
			$err_msg = print_r($e->getMessage());
		}
		SysLogHelper::InvokeJrn_UpdateResult($journal_id, ['success'=>$rtn['success'],'message'=>$err_msg]);
		return $rtn;
	}
	
	/**
	 * 获取邮件任务列表
	 * @param	array	$condition	筛选条件
	 * @param	string	$stor		排序
	 * @param	string	$orderBy	排序

	 * @return	array
	 * @author	lzhl	2017/02		初始化
	 */
	public static function getMailQuestListByCondition($condition, $stor='priority', $orderBy='ASC'){
		$data = [];
		$page=empty($condition['page'])?1:$condition['page'];
		$pageSize=empty($condition['per-page'])?50:$condition['per-page'];
		$query = CsMailQuestList::find()->where(" 1 ");
		foreach ($condition as $key=>$val){
			if(in_array($key,['page','per-page']))
				continue;
			if($key=='quest_number'){
				$query->andWhere(['like',$key,$val]);
			}else
				$query->andWhere([$key=>$val]);
		}
		
		//$commandQuery = clone $query;
		//echo "<br>".$commandQuery->createCommand()->getRawSql()."<br>";
		
		$pagination = new Pagination([
			//'pageSize' => $pageSize,
			'defaultPageSize' => 50,
			'totalCount' =>$query->count(),
			'pageSizeLimit'=>[20,200],//每页显示条数范围
			'params'=>$condition,
		]);
		$data['pagination'] = $pagination;
		
		$data['list'] = $query->orderBy(" $stor $orderBy ")->limit($pagination->limit)->offset($pagination->offset)->all();
		
		return $data;
	}
	
	/**
	 * 根据某个任务模板，生成任务
	 * @param	int		$templateId		模板ID
	 * @param	int		$puid			用户PUID
	 * @param	boolean	$cronAuto		是否后台自生成
	 * @return	array					生成结果
	 * @author	lzhl	2017/02		初始化
	 */
	public static function generateTemplateQuest($templateId,$puid=0,$cronAuto=false){
		$rtn = ['success'=>true,'message'=>''];
		
		if(is_string($templateId))
			$templateId = explode(';', $templateId);
		
		$templates = CsQuestTemplate::findAll($templateId);
		if(empty($templates)){
			return ['success'=>false,'message'=>'无有效模板'];
		}
		
		$xlb_cs_platform = ['amazon'];
		$generateResult = [];//生成结果记录
		$generate_time = date("Y-m-d H-i-s",time());
		$generate_time_short =  date("Y-m-d H-i",time());
		foreach ($templates as $temp){
			$temp_id = (string)$temp->id;
			$quest_number = $temp->id.'-'.str_replace(['-',' '], '', $generate_time_short);
			$existing_quest_number = CsMailQuestList::find()->where(['quest_template_id'=>$temp->id,'quest_number'=>$quest_number])->One();
			if(!empty($existing_quest_number)){
				$generateResult[$temp_id]['success'] = false;
				$generateResult[$temp_id]['message'] = '任务['.$temp->name.']生成失败：一分钟内已经生产过任务，不能操作太频繁。';
				continue;
			}
			
			$generateResult[$temp_id]['success'] = true;
			$SellerEmailAddress = CsSellerEmailAddress::find()->where(['platform'=>$temp->platform,'seller_id'=>$temp->seller_id,'site_id'=>$temp->site_id,'status'=>'active'])->one();
			if(empty($SellerEmailAddress)){
				$generateResult[$temp_id]['success'] = false;
				$generateResult[$temp_id]['message'] = '任务['.$temp->name.']生成失败：店铺未设置有效的客服邮箱地址。';
				continue;
			}
			
			$generateResult[$temp_id] = [];
			if(!in_array($temp->platform,$xlb_cs_platform)){
				$generateResult[$temp_id]['success'] = false;
				$generateResult[$temp_id]['message'] = '任务['.$temp->name.']生成失败：暂时不支持该平台的订单。';
				continue;
			}
			//++++++ 订单筛选条件 start ++++++
			$order_source_site_id = AmazonApiHelper::getCountryCodeByMarketPlaceId($temp->site_id);
			if(empty($order_source_site_id)){
				$generateResult[$temp_id]['success'] = false;
				$generateResult[$temp_id]['message'] = '任务['.$temp->name.']生成失败：没有模板中指定的amazon站点。';
				continue;	
			}
			//多少日内的订单
			$order_in_howmany_days = (int)$temp->order_in_howmany_days;
			
			//订单类型&订单状态
			$for_order_type = '';
			switch ($temp->platform){
				case 'amazon':
					$for_order_type_tmp = $temp->for_order_type;
					if($for_order_type_tmp=='all' || empty($for_order_type_tmp))
						$for_order_type = ['MFN','AFN'];
					else
						$for_order_type = explode(';', $for_order_type_tmp);
						
					$AMAZON_EAGLE_ORDER_STATUS_MAP = AmazonSyncFetchOrderApiHelper::$AMAZON_EAGLE_ORDER_STATUS_MAP;
					$complated_status = array_search(500, $AMAZON_EAGLE_ORDER_STATUS_MAP);
					break;
				default:
					break;
			}
				
			if(empty($complated_status)){
				$generateResult[$temp_id]['success'] = false;
				$generateResult[$temp_id]['message'] = '任务['.$temp->name.']生成失败：不能指定订单完成状态的值。';
				continue;
			}
			//++++++ 订单筛选条件 end ++++++
			//++++++ 订单商品筛选条件 start ++++++
			if(empty($temp->filter_order_item_type) || $temp->filter_order_item_type=='non'){
				$filter_order_item_type = '';
				$order_item_key_type = '';
				$order_item_keys = '';
			}else{
				$filter_order_item_type = $temp->filter_order_item_type;
				$order_item_key_type = $temp->order_item_key_type;
				$order_item_keys = explode(';',$temp->order_item_keys);
				Helper_Array::removeEmpty($order_item_keys);
				if(empty($order_item_key_type) || empty($order_item_keys)){
					$generateResult[$temp_id]['success'] = false;
					$generateResult[$temp_id]['message'] = '任务['.$temp->name.']生成失败：模板规则中，订单商品匹配规则设置有误。';
					continue;
				}
			}
			//++++++ 订单商品筛选条件 end ++++++
			
			$od_query = OdOrder::find()->where(['order_source'=>$temp->platform,'selleruserid'=>$temp->seller_id,'order_source_site_id'=>$order_source_site_id]);
			$od_query->andWhere(['order_relation'=>['normal','fm']]);//只查询普通订单和合并原始单
			$od_query->andWhere(" order_source_create_time >= ".(time()-3600*24*$order_in_howmany_days) );
			$od_query->andWhere(['order_type'=>$for_order_type]);
			$od_query->andWhere(['order_source_status'=>$complated_status]);//只要已完成的订单
			
			//$commandQuery = clone $od_query;
			//echo $commandQuery->createCommand()->getRawSql();
			
			$orders = $od_query->all();
			if(empty($orders)){
				$generateResult[$temp_id]['success'] = true;
				$generateResult[$temp_id]['message'] = '任务['.$temp->name.']生成完成：暂无订单符合模板设置的规则。';
				
				$temp->last_generated_time = $generate_time;
				$temp->save(false);
				continue;
			}
			
			$unMatchOrdersMsg = [];//不合符模板的订单信息
			$matchOrders = [];//符合模板订单和商品条件的订单数组
			$matchOrdersBuyers = [];//订单买家名单
			
			foreach ($orders as $order){
				$od_items = $order->items;
				if(!empty($filter_order_item_type)){
					//包含的情况
					if($filter_order_item_type=='in'){
						$match = false;
						foreach ($od_items as $item){
							if($order_item_key_type=='sku'){
								if(in_array($item->sku, $order_item_keys))
									$match = true;
							}elseif ($order_item_key_type=='platform_item_id'){
								if(in_array($item->order_source_itemid, $order_item_keys) || in_array($item->source_item_id, $order_item_keys))
									$match = true;
							}
							if($match)
								break;
						}
					}
					//不包含的情况
					elseif($filter_order_item_type=='out'){
						$match = true;
						foreach ($od_items as $item){
							if($order_item_key_type=='sku'){
								if(in_array($item->sku, $order_item_keys))
									$match = false;
							}elseif ($order_item_key_type=='platform_item_id'){
								if(in_array($item->order_source_itemid, $order_item_keys) || in_array($item->source_item_id, $order_item_keys))
									$match = false;
							}
							if($match==false)
								break;
						}
					}
					if($match==false){
						$unMatchOrdersMsg[$order->order_source_order_id] = '订单'.$order->order_source_order_id.'匹配失败：订单商品不符合模板规则';
						continue;
					}
				}
				$matchOrders[$order->order_source_order_id] = $order;
				$matchOrdersBuyers[] = $order->source_buyer_user_id;
			}
			
			unset($orders);
			//$matchOrdersConsignee = array_unique($matchOrdersConsignee);
			
			//发送条件筛选
			$can_shen_when_contacted = $temp->can_shen_when_contacted;
			if(empty($can_shen_when_contacted) || $cronAuto==true ){//后台自动生成时，默认就是联系过就不自动生成
				$contacted_orders = self::checkAmazonCsHasContacted(array_keys($matchOrders));
				foreach ($contacted_orders as $source_order_id=>$contacted){
					$unMatchOrdersMsg[$source_order_id] = '订单'.$source_order_id.'已和买家有往来邮件，不再重发，如之前的邮件发送失败，请先处理。';
					if(isset($matchOrders[$source_order_id]))
						unset($matchOrders[$source_order_id]);
				}
			}
			
			#todo 黑名单
			$can_send_to_blacklist_buyer = $temp->can_send_to_blacklist_buyer;
			
			//review 检查
			$can_send_when_reviewed = $temp->can_send_when_reviewed;
			if(empty($can_send_when_reviewed)){
				//echo "\n can't send when reviewed \n";
				$tmp_matchOrders = [];
				foreach ($matchOrders as $source_order_id=>$orderData){
					$asins = [];
					if(empty($orderData->items))
						continue;
					foreach ($orderData->items as $item){
						$asins[] = $item->order_source_itemid;
					}
					$had_reviewed = self::checkBuyerHadReviewedThisAsin($orderData->source_buyer_user_id,$orderData->selleruserid,$temp->site_id, $asins);
					//echo "order : ".$source_order_id ." had reviewed ?:". var_dump($had_reviewed);
					if($had_reviewed){
						$unMatchOrdersMsg[$source_order_id] = '订单'.$source_order_id.' 买家已经留过review，不符合模板规则。';
					}else{
						$tmp_matchOrders[$source_order_id] = $orderData;
					}
				}
				unset($matchOrders);
				$matchOrders = $tmp_matchOrders;
			}
			
			
			//feedback 检查
			$can_send_when_feedbacked = $temp->can_send_when_feedbacked;
			if(empty($can_send_when_feedbacked)){
				$feedbacked_orders = self::checkAmazonFeedBacks(array_keys($matchOrders));
				//print_r($feedbacked_orders);
				foreach ($feedbacked_orders as $source_order_id=>$feedbacked){
					$unMatchOrdersMsg[$source_order_id] = '订单'.$source_order_id.'买家已经给过feedback，不符合模板规则。';
					if(isset($matchOrders[$source_order_id]))
						unset($matchOrders[$source_order_id]);
				}
			}
			
			if(!empty($matchOrders)){
				//发送设置
				$send_after_order_created_days = empty($temp->send_after_order_created_days)?0:(int)$temp->send_after_order_created_days;//订单生成N天后发送
				$pending_send_time =  ($temp->pending_send_time=='')?'':(int)$temp->pending_send_time;//发送时间(买家时区)
				$tmpInsertData = [];
				$order_to_generate = [];
				$quest_number = $temp->id.'-'.str_replace(['-',' '], '', $generate_time_short);
				
				foreach ($matchOrders as $source_order_id=>$orderData){
					$tmp = [];
					$tmp['quest_template_id'] = $temp->id;
					$tmp['quest_number'] = $quest_number;
					$tmp['platform'] = $temp->platform;
					$tmp['seller_id'] = $temp->seller_id;
					$tmp['site_id'] = $temp->site_id;
					$tmp['order_source_order_id'] = $source_order_id;
					$tmp['status'] = 'P';
					
					$tmp['mail_from'] = $SellerEmailAddress->email_address;
					$tmp['mail_to'] = $orderData->consignee_email;
					if(empty($tmp['mail_to'])){
						$unMatchOrdersMsg[$source_order_id] = '订单'.$source_order_id.'没有获取到买家邮箱地址，请先到订单管理页面设置。';
					}
					$tmp['consignee'] = $orderData->consignee;
					$subject = $temp->subject;
					$body = $temp->contents;
					//保存推荐商品组
					try{
						$temp_addi_info = empty($temp->addi_info)?[]:json_decode($temp->addi_info,true);
						if(!empty($temp_addi_info['recommended_group']))
							\eagle\modules\tracking\helpers\TrackingHelper::setMessageConfigByOms($orderData->order_source_order_id, 1, 8, (int)$temp_addi_info['recommended_group']);
					}catch(\Exception $e) {
						//continue;
					}
					//替换模板变量
					self::replaceCsTemplateDataByOrderData($subject, $body, $orderData, $temp);
					$tmp['subject'] = $subject;
					$tmp['body'] = $body;
					
					$order_creation = (int)$orderData->order_source_create_time;
					$pending_send_date = $order_creation + 3600*24*$send_after_order_created_days;
					if($pending_send_time==''){//不考虑时段立即发送
						$tmp['pending_send_time_consignee'] = $pending_send_date;
					}else{
						$pending_send_date = date("Y-m-d H:i:s",$pending_send_date);//'2017-01-01 01:11:11'
						$tmp_hour = (int)substr($pending_send_date,11,2);
							
						if($pending_send_time==$tmp_hour){//预期时间等于计划发送时间，则直接套用
							$tmp['pending_send_time_consignee'] = $pending_send_date;
						}
						if($pending_send_time < $tmp_hour){//预期时间小于计划发送时间，即当日时机已过，留待下一日发
							$tmp['pending_send_time_consignee'] = date("Y-m-d",strtotime($pending_send_date)+3600*24).' '.$pending_send_time.':00:00';
						}
						if($pending_send_time > $tmp_hour){//预期时间大于计划发送时间，即当日时机还没有到，延长到当日n时
							$tmp['pending_send_time_consignee'] = date("Y-m-d",strtotime($pending_send_date)).' '.$pending_send_time.':00:00';
						}
					}
					
					//时区转换
					$consignee_country_code = empty($orderData->order_source_site_id)?$orderData->consignee_country_code:$orderData->order_source_site_id;
					$jet_lag = self::getCountryJetLagWithGMT8($consignee_country_code);
					if(empty($jet_lag))
						$tmp['pending_send_time_location'] = $tmp['pending_send_time_consignee'];
					else{
						$tmp['pending_send_time_location'] = strtotime($tmp['pending_send_time_consignee'])+3600*$jet_lag;
						$tmp['pending_send_time_location'] = date("Y-m-d H:i:s",$tmp['pending_send_time_location']);
					}
					$tmp['created_time'] = TimeUtil::getNow();
					
					$tmpInsertData[] = $tmp;
					$order_to_generate[] = $orderData->order_source_order_id;
				}
				
				//****** 保存到数据库 start ******
				//扣除edm quota
				if(empty($puid))
					$puid = \Yii::$app->subdb->getCurrentPuid();
				
				/* #todo暂时不考虑邮件额度
				$change_quota_result = EdmHelper::EdmQuotaChange($puid, count($tmpInsertData));
				if(!$change_quota_result['success']){
					$generateResult[$temp_id]['success'] = false;
					$generateResult[$temp_id]['message'] = '任务['.$temp->name.']生成完成：剩余邮件额度不足。';
					continue;
				}
				*/
				$insert_count = 0;
				$quota_feedback = 0;
				$total_to_insert = count($tmpInsertData);
				$insert_result = [];
				$insert_failed = [];
				$insert_successed = [];
				foreach ($tmpInsertData as $insert_data){
					$step = '0';
					try{
						$step = '1';
						//检测订单是否已经有待处理任务
						$existing_todo = CsMailQuestList::find()->where([
								'platform'=>$insert_data['platform'],
								'seller_id'=>$insert_data['seller_id'],
								'site_id'=>$insert_data['site_id'],
								'order_source_order_id'=>$insert_data['order_source_order_id'],
								'status'=>['P','S']
							])->one();
						if(!empty($existing_todo)){
							$insert_failed[$insert_data['order_source_order_id']] = '订单'.$insert_data['order_source_order_id'].'已经有待发送的任务，本次跳过。';
							$quota_feedback ++;
							continue;
						}
						$step = '2';
						$quest = new CsMailQuestList();
						$step = '3';
						$quest->setAttributes($insert_data);
						$step = '4';
						if(!$quest->save()){
							$step = '4-1';
							$insert_failed[$insert_data['order_source_order_id']] = '订单'.$insert_data['order_source_order_id'].'生成任务时发生错误，E-BD001。';
							$quota_feedback++;
							continue;
						}else{
							$step = '4-2';
							$addi_info = [
								'subject'=>$insert_data['subject'],
								'body'=>$insert_data['body']
							];
							try{
								$sql = "INSERT INTO `edm_email_send_queue` 
										( `puid`, `history_id`, `send_from`, `send_to`, `act_name`, `create_time`, `addi_info`,`pending_send_time`) 
										VALUES 
										($puid,".$quest->id.", :send_from, :send_to, 'amazoncs', '".TimeUtil::getNow()."', :addi_info, '".$insert_data['pending_send_time_location']."')";
								$command = \Yii::$app->db_queue2->createCommand ($sql);
								$command->bindValue(":send_from",$insert_data['mail_from'],\PDO::PARAM_STR);
								$command->bindValue(":send_to",$insert_data['mail_to'],\PDO::PARAM_STR);
								$command->bindValue(":addi_info",json_encode($addi_info),\PDO::PARAM_STR);
								$ret= $command->execute();
							}catch(\Exception $e) {
								$quest->status = 'CF';
								$quest->update_time = TimeUtil::getNow();
								$quest->save();
								$insert_failed[$insert_data['order_source_order_id']] = '订单'.$insert_data['order_source_order_id'].'生成任务时发生错误，E-BD002。';
								$quota_feedback++;
								continue;
							}
							if(!empty($ret)){
								$insert_successed[] = $insert_data['order_source_order_id'];
								$insert_count ++;
							}else{
								$insert_failed[$insert_data['order_source_order_id']] = '订单'.$insert_data['order_source_order_id'].'生成任务时发生错误，E-BD003。';
								$quota_feedback++;
								continue;
							}
						}
						
					}catch(\Exception $e) {
						$insert_failed[$insert_data['order_source_order_id']] = '订单'.$insert_data['order_source_order_id'].'生成任务时发生错误，EX-step:'.$step;
						$quota_feedback++;
						continue;
					}
				}//end of each $tmpInsertData
				
				
				//****** 保存到数据库 end ******
				
				/* #todo 暂时不考虑额度
				if(!empty($quota_feedback))
					EdmHelper::EdmQuotaChange($puid, $quota_feedback, '+');
				*/
				
				//if($insert_count !== $total_to_insert){
					$insert_result['result'] = '任务['.$temp->name.']检测到'.$total_to_insert.'订单符合任务模板规则，成功创建了'.$insert_count.'条发送任务。';
				//}
				$insert_result['successed'] = $insert_successed;
				$insert_result['failed'] = $insert_failed;
				
				$generateResult[$temp_id]['insert_result'] = $insert_result;
				
				
				//$insert_count = SQLHelper::groupInsertToDb('cs_mail_quest_list',$tmpInsertData,'subdb');
				//$generateResult[$temp_id]['matchOrders'] = $order_to_generate;
				//if($insert_count !== count($order_to_generate)){
				//	$unMatchOrdersMsg['insert_result'] = '检测到'. count($order_to_generate).'订单符合任务模板规则，成功创建了'.$insert_count.'条发送任务。';
				//}
				//$generateResult[$temp_id]['unMatchOrdersMsg'] = $unMatchOrdersMsg;
				
				$generateResult[$temp_id]['unMatchOrdersMsg'] = empty($unMatchOrdersMsg)?'':$unMatchOrdersMsg;
				$generateResult[$temp_id]['matchOrders'] = array_keys($matchOrders);
			}else{
				$generateResult[$temp_id]['unMatchOrdersMsg'] = empty($unMatchOrdersMsg)?'':$unMatchOrdersMsg;
				$generateResult[$temp_id]['matchOrders'] = array_keys($matchOrders);
				continue;
			}
			
			if(isset($generateResult[$temp_id]['success']) &&  $generateResult[$temp_id]['success']==false ){
				//$temp->last_generated_time = $generate_time;
			}else{
				$temp->last_generated_time = $generate_time;
			}
			
			$generateResult[$temp_id]['generate_time'] = $generate_time;
			$temp->last_generated_log = json_encode($generateResult[$temp_id]);
			$temp->save(false);
			
		}//end of each template
		
		return $generateResult;
	}
	
	

	/**
	 +---------------------------------------------------------------------------------------------
	 * 根据 参数  、订单号， 替换主题、模块的内容  指定的 固定变量
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param  	$subject			邮件主题
	 * 			$template			邮件内容
	 * 			$order				来源订单号model
	 * 			$template			邮件模板model
	 +---------------------------------------------------------------------------------------------
	 * @return na
	 +---------------------------------------------------------------------------------------------
	 * @author		lkh		2015/5/12			初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function replaceCsTemplateDataByOrderData(&$subject , &$body , $order , $template=false){
		
		// 定义需要替换的字段名
		$FieldNameMapping = [
			"平台订单号"=>"order_source_order_id",
			"店铺SKU"=>"SKU",
			"ASIN"=>"ASIN",
			"收件人名称"=>"consignee",
			"买家名称"=>"source_buyer_user_id",
			"订单物品列表(商品sku，名称，数量，单价)"=>"items_list",
			"包裹物流号"=>"track_no",
			"包裹递送物流商"=>"ship_by",
			"买家查看包裹追踪及商品推荐链接"=>"query_url",
			"商品链接"=>"product_url",
			"带图片的商品链接"=>"product_img&url",
			"联系卖家链接"=>"contact_seller",
			"review链接"=>"review_url",
			"feedback链接"=>"feedback_url",
		];
		
		$template_addi_info = empty($template->addi_info)?[]:json_decode($template->addi_info,true);
		$url_title_arr = empty($template_addi_info['url_title'])?[]:$template_addi_info['url_title'];
		
		//获取 tracking 数据 用来替换数据
		$tmpTracking = OrderTrackerApiHelper::getOrderTrackingInfoByOrder($order);
		
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
					$body = str_replace("[$fieldname]", $tmpTracking[$value].$abroad_no_str ,$body);
	
				}else{
					//ship_by
					$carrier_name=TrackingApiHelper::getTrackNoCarrierEnName($tmpTracking['track_no'], $order->source_buyer_user_id);
					if(!empty($carrier_name)){
						$tmpTracking[$value] = $carrier_name;
					}
					$tmpTracking[$value] = preg_replace('/([\x80-\xff]*)/i','',$tmpTracking[$value]);
					$subject = str_replace("[$fieldname]", $tmpTracking[$value] ,$subject);
					$body = str_replace("[$fieldname]", $tmpTracking[$value] ,$body);
				}
	
			}else{
				// order 部分
				$replaced_value = '';
				if ($value =='order_source_order_id'){
					$replaced_value .= $order->order_source_order_id;
				}
				
				if ($value =='consignee'){
					$replaced_value .= $order->consignee;
				}
				
				if ($value =='source_buyer_user_id'){
					$replaced_value .= $order->source_buyer_user_id;
				}
	
				if ($value =='items_list'){
					if (!empty($order->items)){
						$items = $order->items;
					}else{
						$items = [];
					}
					
					$replaced_value .= "<br>";
					if (!empty($items)){
						
						/*
						$replaced_value .= '<table style="max-width:600px;">';
						$replaced_value .= '<tr><td style="text-align:center;border:1px solid;">SKU</td>';
						$replaced_value .= '<td style="text-align:center;border:1px solid;">Product Name</td>';
						$replaced_value .= '<td style="text-align:center;border:1px solid;">Quantity</td>';
						$replaced_value .= '<td style="text-align:center;border:1px solid;">Unit Price</td></tr>';
						foreach ($items as $anItem){
							$replaced_value .= "<tr>";
							$replaced_value .= "<td style='text-align:center;border:1px solid;'>" .(!empty($anItem->sku)?$anItem->sku:""). "</td>";
							$replaced_value .= "<td style='text-align:center;border:1px solid;'>" .(!empty($anItem->product_name)?$anItem->product_name:""). "</td>";
							$replaced_value .= "<td style='text-align:center;border:1px solid;'>" .(!empty($anItem->ordered_quantity)?$anItem->ordered_quantity:0). "</td>";
							$replaced_value .= "<td style='text-align:center;border:1px solid;'>" .(!empty($anItem->price)?$anItem->price:0)."".(!empty($order->currency)?$order->currency:""). "</td>";
							$replaced_value .= "</tr>";
						}
						$replaced_value .= '</table><br>';
						*/
						
						foreach ($items as $anItem){
							$replaced_value .= 
								(!empty($anItem->sku)?$anItem->sku:"")." "
								.(!empty($anItem->product_name)?$anItem->product_name:"")." x "
								.(!empty($anItem->ordered_quantity)?$anItem->ordered_quantity:0)." "
								.(!empty($orderData['currency'])?$orderData['currency']:"") ." "
								.(!empty($anItem->price)?$anItem->price:0). "<br>";
						}
					}
				}
				
				if ($value =='SKU'){
					if (!empty($order->items)){
						$items = $order->items;
					}else{
						$items = [];
					}
						
					$replaced_value .= "<br>";
					if (!empty($items)){
						foreach ($items as $anItem){
							$replaced_value .= (!empty($anItem->sku)?$anItem->sku:"")."<br>";
						}
					}
				}
				
				if ($value =='ASIN'){
					if (!empty($order->items)){
						$items = $order->items;
					}else{
						$items = [];
					}
				
					$replaced_value .= "<br>";
					if (!empty($items)){
						foreach ($items as $anItem){
							$replaced_value .= (!empty($anItem->order_source_itemid)?$anItem->order_source_itemid:"")."<br>";
						}
					}
				}
				
				if ($value =='product_url'){
					if (!empty($order->items)){
						$items = $order->items;
					}else{
						$items = [];
					}
					
					$replaced_value .= "<br>";
					if (!empty($items)){
						foreach ($items as $anItem){
							switch ($order->order_source){
								case 'amazon' :
									$MARKETPLACE_DOMAIN = AmazonApiHelper::$AMAZON_MARKETPLACE_DOMAIN_CONFIG;
									if(empty($MARKETPLACE_DOMAIN[$order->order_source_site_id]) || empty($anItem->order_source_itemid))
										break;
									$replaced_value .= "<a href='http://".$MARKETPLACE_DOMAIN[$order->order_source_site_id]."/gp/product/".$anItem->order_source_itemid."' target='_blank'>".$anItem->order_source_itemid."</a><br>";
									break;
								default:
									break;
							}
							
						}
					}
				}
				
				if ($value =='product_img&url'){
					if (!empty($order->items)){
						$items = $order->items;
					}else{
						$items = [];
					}
						
					$replaced_value .= "<br>";
					if (!empty($items)){
						foreach ($items as $anItem){
							switch ($order->order_source){
								case 'amazon' :
									$MARKETPLACE_DOMAIN = AmazonApiHelper::$AMAZON_MARKETPLACE_DOMAIN_CONFIG;
									if(empty($MARKETPLACE_DOMAIN[$order->order_source_site_id]) || empty($anItem->order_source_itemid))
										break;
									$replaced_value .= "<a href='http://".$MARKETPLACE_DOMAIN[$order->order_source_site_id]."/gp/product/".$anItem->order_source_itemid."' target='_blank'><img src='".$anItem->photo_primary."'>".$anItem->order_source_itemid."</a><br>";
									break;
								default:
									break;
							}
								
						}
					}
				}
				
				if ($value =='contact_seller'){
					switch ($order->order_source){
						case 'amazon' :
							$MARKETPLACE_DOMAIN = AmazonApiHelper::$AMAZON_MARKETPLACE_DOMAIN_CONFIG;
							if(empty($MARKETPLACE_DOMAIN[$order->order_source_site_id]) || empty($order->selleruserid))
								break;
							$replaced_value .= "<a href='https://".$MARKETPLACE_DOMAIN[$order->order_source_site_id]."/gp/help/contact/contact.html?orderID=".$order->order_source_order_id."&sellerID=".$order->selleruserid."' target='_blank'>".(empty($url_title_arr['contact_seller'])?"Contact Us":$url_title_arr['contact_seller'])."</a>";
							break;
						default:
							break;
					}
				}
				
				if ($value =='feedback_url'){
					switch ($order->order_source){
						case 'amazon' :
							$MARKETPLACE_DOMAIN = AmazonApiHelper::$AMAZON_MARKETPLACE_DOMAIN_CONFIG;
							$MarketPlaceId = AmazonApiHelper::getMarketPlaceIdByCountryCode($order->order_source_site_id);
							if(empty($MARKETPLACE_DOMAIN[$order->order_source_site_id]) || empty($MarketPlaceId))
								break;
							$replaced_value .= "<a href='https://".$MARKETPLACE_DOMAIN[$order->order_source_site_id]."/gp/feedback/leave-consolidated-feedback.html/marketplaceID=".$MarketPlaceId."&orderID=".$order->order_source_order_id."' target='_blank'>".(empty($url_title_arr['feedback_url'])?"Leave Feedback":$url_title_arr['feedback_url'])."</a>";
							break;
						default:
							break;
					}
				}
				
				if ($value =='review_url'){
					if (!empty($order->items)){
						$items = $order->items;
					}else{
						$items = [];
					}
				
					$replaced_value .= "<br>";
					if (!empty($items)){
						foreach ($items as $anItem){
							switch ($order->order_source){
								case 'amazon' :
									$MARKETPLACE_DOMAIN = AmazonApiHelper::$AMAZON_MARKETPLACE_DOMAIN_CONFIG;
									if(empty($MARKETPLACE_DOMAIN[$order->order_source_site_id]) || empty($anItem->order_source_itemid))
										break;
									$replaced_value .= "<a href='http://".$MARKETPLACE_DOMAIN[$order->order_source_site_id]."/review/create-review?asin=".$anItem->order_source_itemid."' target='_blank'>Review for ".$anItem->order_source_itemid."</a>";
									break;
								default:
									break;
							}
				
						}
					}
				}
				
				if ($value =='query_url'){
					$puid = \Yii::$app->subdb->getCurrentPuid();
					if(!empty($url_title_arr['query_url'])){
						if(!empty($tmpTracking['id'])){
							$replaced_value = "<a target='_blank' href='http://littleboss.17track.net/message/tracking/index?parcel=". MessageHelper::encryptBuyerLinkParam($puid, $tmpTracking['id'])."'>".$url_title_arr['query_url']."</a>" ;
						}else{
							$replaced_value = "<a target='_blank' href='http://littleboss.17track.net/message/tracking/index?parcel=". MessageHelper::encryptBuyerLinkParam($puid, $order->order_id,'order_id')."'>".$url_title_arr['query_url']."</a>" ;
					
						}
					}else{
						if(!empty($tmpTracking['id']))
							$replaced_value = "http://littleboss.17track.net/message/tracking/index?parcel=". MessageHelper::encryptBuyerLinkParam($puid, $tmpTracking['id']) ;
						else
							$replaced_value = "http://littleboss.17track.net/message/tracking/index?parcel=". MessageHelper::encryptBuyerLinkParam($puid, $order->order_id,'order_id');
					}//end of when query_url
				}
				
				$subject = str_replace("[$fieldname]", $replaced_value , $subject);
				$body = str_replace("[$fieldname]", $replaced_value , $body);
			}
		}
		
		$subject = str_replace("\r\n", "<br>" , $subject);
		$body = str_replace("\r\n", "<br>" , $body);
		
		return ['subject'=>$subject , 'body'=>$body ];
	}//end of replaceTemplateData
	
	/**
	 * 获取目的国相对GMT+8(北京)时区的时差
	 * @param	string	$country_code	目的地国二字代码
	 * @return	'' or int				无结果返回''，有结果返回时差小时数(正负整数)
	 * @author	lzhl	2017/02		初始化
	 */
	public static function getCountryJetLagWithGMT8($country_code){
		$JetLag = '';
		$country = SysCountry::find()->where(['country_code'=>strtoupper($country_code)])->one();
		if(empty($country))
			return '';
		$time_zone_gmt = $country->time_zone_gmt;
		if(!is_null($time_zone_gmt) && $time_zone_gmt!==''){
			$JetLag = 8-(int)$time_zone_gmt;
		}
		return $JetLag;
	}
	
	
	/**
	 +----------------------------------------------------------
	 * 检查订单号数组中有哪些订单，已经发邮件联系过买家
	 * @access static
	 +----------------------------------------------------------
	 * @param	array	$orders		order_source_order_id's array
	 * @return	array	
	 * @author	lzhl	2017-2-14	初始化
	 +----------------------------------------------------------
	 **/
	public static function checkAmazonCsHasContacted($orders=[]){
		$hasContacted = [];
		if(is_string($orders)){
			$orders = explode(';', $orders);
		}
		if(empty($orders))
			return $hasContacted;
		$contacted_order = CsMailQuestList::find()->select('order_source_order_id')->distinct(true)
			->where(['order_source_order_id'=>$orders])
			//->andWhere(" status <>'F' ")
			->asArray()->all();
		foreach ($contacted_order as $contacted){
			$hasContacted[$contacted['order_source_order_id']] = 1;
		}
		return $hasContacted;
	}
	
	public static function checkBuyerHadReviewedThisAsin($ordersBuyers, $merchant_id ,$marketplace_id, $asin){
		//echo "\n" .print_r($ordersBuyers) .";".$merchant_id.";" . $marketplace_id.";" .print_r($asin);
		$had = AmazonReviewInfo::find()->where(['author'=>$ordersBuyers, 'merchant_id'=>$merchant_id, 'marketplace_id'=>$marketplace_id, 'asin'=>$asin])->count();
		
		//if($ordersBuyers==['josh']) var_dump($had);
		if(!empty($had))
			return true;
		else 
			return false;
	}
	
	/**
	 +----------------------------------------------------------
	 * 检查订单号数组中有哪些订单，买家已经给过feeback（基于客户端下载获取的信息）
	 * @access static
	 +----------------------------------------------------------
	 * @param	array	$orders		order_source_order_id's array
	 * @return	array
	 * @author	lzhl	2017-2-14	初始化
	 +----------------------------------------------------------
	 **/
	public static function checkAmazonFeedBacks($orders=[]){
		$feedbackData = [];
		if(is_string($orders)){
			$orders = explode(';', $orders);
		}
		if(empty($orders))
			return $feedbackData;
		//print_r($orders);
		$feedbacks = AmazonFeedbackInfo::find()->where(['order_source_order_id'=>$orders])->asArray()->all();
		foreach ($feedbacks as $feedback){
			$feedbackData[$feedback['order_source_order_id']][] = $feedback;
		}
		return $feedbackData;
	}
	
	/**
	 +----------------------------------------------------------
	 * 获取某个订单的Detail信息
	 * @access static
	 +----------------------------------------------------------
	 * @param	string	$paramKey	order_source_order_id OR order_id
	 * @param	string	$order_id	平台订单号Id OR 小老板平台订单id
	 * @return	array	Order & items data array
	 * @author	lzhl	2017-2-14	初始化
	 +----------------------------------------------------------
	 **/
	public static function getOrderDetail($paramKey, $platform, $order_id) {
		$orderDetail = array();
	
		$order = OdOrder::find()->where(['order_source_order_id'=>$order_id,'order_source'=>$platform,'order_relation'=>['normal','fm']])->asArray()->one();
		
		if (!empty($order)){
			$itemsModel	= OdOrderItem::findAll(["order_id"=>$order['order_id']]);
			$orderDetail = $order;//$order->attributes;
			$orderDetail['items'] = array();
			//set photo for each item, if there is no photo in record, try to load it from product model
			if ($itemsModel != null){
				foreach ($itemsModel as $anItemModel){
					$anItem = $anItemModel->attributes;
					if (empty($anItem['photo_primary'])){
						$prodInfo = Product::findone(['sku'=>$anItem['sku']]);
						if ($prodInfo != null)
							$anItem['photo_primary'] = $prodInfo['photo_primary'];
					}
						
					$orderDetail['items'][] = $anItem;
				}//end of each item for this order
			}//end if items found
		}
		return $orderDetail;
	}
	
	/**
	 +----------------------------------------------------------
	 * 返回某个日期时间是多少天前
	 * @access static
	 +----------------------------------------------------------
	 * @param	string	$date_time	日期时间
	 * @return	string	
	 * @author	lzhl	2017-2-14	初始化
	 +----------------------------------------------------------
	 **/
	public static function returnDaysAgoStr($date_time){
		//统一先转换成timestamp
		if(!is_numeric($date_time)){
			$time_stamp = strtotime($date_time);
		}else 
			$time_stamp = (int)$date_time;
		
		//转换成年月日格式
		$theday = date('Y-m-d',$time_stamp);
		$today = date('Y-m-d',time());
		
		$dayPass = ( (int)strtotime($today) - (int)strtotime($theday) ) / (3600*24);
		if($dayPass<1){
			return '今天';
		}elseif(1 <= $dayPass && $dayPass < 2){
			return '昨天';
		}elseif(2 <= $dayPass && $dayPass < 3){
			return '前天';
		}elseif(3 <= $dayPass && $dayPass < 4){
			return '3天前';
		}elseif(4 <= $dayPass && $dayPass < 5){
			return '4天前';
		}elseif(5 <= $dayPass && $dayPass < 6){
			return '5天前';
		}elseif(6 <= $dayPass && $dayPass < 7){
			return '6天前';
		}elseif(7 <= $dayPass){
			return '超过一周';
		}
		
	}
	
	/**
	 +----------------------------------------------------------
	 * 获取某个账号站点的所有订单商品Asin码列表
	 * @param	int		$uid			用户uid
	 * @param	string	$merchant_id	Amazon账号merchant_id
	 * @param	string	$site			站点(国家二字代码)
	 * @return	array	like ['success'=>boolean, 'message'=>string, 'asin'=>array('B00MQKQ79U','B01AUNVPLO',.....)]	
	 * @author	lzhl	2017-3-10	初始化
	 +----------------------------------------------------------
	 **/
	public static function getAsinListByAccountSite($uid,$merchant_id,$site_id){
		if(empty($uid))
			return ['success'=>false,'message'=>'用户id丢失','asin'=>[] ];
		
		 
		
		$orders = OdOrder::find()->distinct(true)->select('order_id')
			->where(['order_source'=>'amazon','selleruserid'=>$merchant_id,'order_source_site_id'=>$site_id])
			->asArray()->all();
		if(empty($orders))
			return ['success'=>true,'message'=>'','asin'=>[] ];
		
		$order_source_itemids = OdOrderItem::find()->distinct(true)->select('order_source_itemid')
			->where(['order_id'=>$orders])
			->asArray()->all();
		
		$asin=[];
		foreach ($order_source_itemids as $row){
			$itemid = trim($row['order_source_itemid']);
			if(!empty($itemid) && !is_null($itemid) && !in_array($itemid,$asin))
				$asin[] = $itemid;
		}
		return ['success'=>true,'message'=>'','asin'=>$asin ];
	}
	
	
	public static function getFeedbackList($condition=[],$stor='feedback_id',$order='DESC',$page=1,$pageSize=20){
		$rtn = [];
		$query = AmazonFeedbackInfo::find();
		foreach ($condition as $key=>$val){
			if($key=='order_source_order_id'){
				$query->andWhere(['like','order_source_order_id',$val]);
			}else{
				$query->andWhere([$key=>$val]);
			}
		}
		
		$pages = new Pagination([
			'defaultPageSize' => 20,
			'pageSize' => $pageSize,
			'totalCount' => $query->count(),
			'pageSizeLimit'=>[20,100],//每页显示条数范围
			'params'=>$_REQUEST,
		]);
		
		$rtn['pagination'] = $pages;
		
		if(!empty($stor) && !empty($order)){
			$query->orderBy(" $stor $order");
		}
		
		//$commandQuery = clone $query;
		//echo $commandQuery->createCommand()->getRawSql();
		
		$rtn['list'] = $query->offset($pages->offset)
			->limit($pages->limit)
			->all();
		return $rtn;
	}
	
	public static function getReviewList($condition=[],$stor='review_id',$order='DESC',$page=1,$pageSize=20){
		$rtn = [];
		$query = AmazonReviewInfo::find();
		foreach ($condition as $key=>$val){
			if($key=='asin'){
				$query->andWhere(['like','asin',$val]);
			}
			elseif($key=='author'){
				$query->andWhere(['like','author',$val]);
			}else{
				$query->andWhere([$key=>$val]);
			}
		}
	
		$pages = new Pagination([
				'defaultPageSize' => 20,
				'pageSize' => $pageSize,
				'totalCount' => $query->count(),
				'pageSizeLimit'=>[20,100],//每页显示条数范围
				'params'=>$_REQUEST,
				]);
	
		$rtn['pagination'] = $pages;
	
		if(!empty($stor) && !empty($order)){
			$query->orderBy(" $stor $order");
		}
	
		//$commandQuery = clone $query;
		//echo $commandQuery->createCommand()->getRawSql();
	
		$rtn['list'] = $query->offset($pages->offset)
		->limit($pages->limit)
		->all();
		return $rtn;
	}
	
	public static function registAmzCsAutoGenerateUsre($puid, $this_template_id ='', $auto_generate=1){
		$redis = RedisHelper::RedisGet('AmzCs', 'AutoGenerateUsre');
		$redis = empty($redis)?[]:json_decode($redis,true);
		
		$puid = (string)$puid;
		if($auto_generate){//需要自动生成
			if(!in_array($puid,$redis)){
				$redis[] = $puid;//redis数据添加
			}
		}else{
			//不需要自动生成的时候，先检查一下其他模板
			$other_auto_templates = CsQuestTemplate::find()->where(" auto_generate=1 and id<>'$this_template_id' ")->andWhere(" status='active' ")->count();
			if(empty($other_auto_templates)){
				$key = array_search($puid,$redis);
				if($key===false){}
				else 
					unset($redis[$key]);//移出redis数据
			}
		}
		
		return RedisHelper::RedisSet('AmzCs', 'AutoGenerateUsre',json_encode($redis));
	}
	
	public static function cronAutoGenerateAmzCsTemplateQuest(){
		$currentQueueVersion = ConfigHelper::getGlobalConfig("cronGenerateAmzCsTemplateQuest/QueueVersion",'NO_CACHE');
		if (empty($currentQueueVersion))
			$currentQueueVersion = 0;
		
		//如果自己还没有定义，去使用global config来初始化自己
		if (empty(self::$queueVersion))
			self::$queueVersion = $currentQueueVersion;
			
		//如果自己的version不同于全局要求的version，自己退出，从新起job来使用新代码
		if (self::$queueVersion <> $currentQueueVersion){
			exit("Version new $currentQueueVersion , this job ver ".self::$queueVersion." exits for using new version $currentQueueVersion.");
		}
		
		try{
			$redis = RedisHelper::RedisGet('AmzCs', 'AutoGenerateUsre');
			$redis = empty($redis)?[]:json_decode($redis,true);
			
			foreach ($redis as $puid){
				try{
					$puid = (int)$puid;
					echo "\n start for puid:$puid autoGenerateAmzCsTemplateQuest;";
				 
					$auto_templates = CsQuestTemplate::find()->where(['status'=>'active','auto_generate'=>'1'])->all();
					foreach ($auto_templates as $template){
						$rtn = self::generateTemplateQuest($template->id,$puid, true);
						if(isset($rtn[$template->id]['success']) && $rtn[$template->id]['success']==false){
							echo "\n generate step failed:";
							echo "\n ".print_r($rtn);
						}
						echo "\n end of puid:$puid autoGenerateAmzCsTemplateQuest;";
					}
					
				}catch(\Exception $e) {
					echo "\n generate puid step Exception:";
					echo "\n ".print_r($e->getMessage());
					continue;
				}
			}
		}catch(\Exception $e) {
			echo "\n generate main step Exception:";
			echo "\n ".print_r($e->getMessage());
		}
	}
	
	
	/**
	 +----------------------------------------------------------
	 * 检查手动生成任务的模板是否所有都近期拉取过review和feedback(模板设置排除给过review feedback的才检查，其他当ok)
	 * @param	string/array	$templateIds
	 * @return	array
	 * @author	lzhl	2017-4-24	初始化
	 +----------------------------------------------------------
	 **/
	public static function checkAllAmzCsTemplateReviewFeedbackOkForGenerate($templateIds=[]){
		$rtn = ['result'=>true,'info'=>''];
		if(is_string($templateIds))
			$templateIds = explode(';',$templateIds);
		
		if(empty($templateIds))
			return $rtn;
		
		$templates = CsQuestTemplate::find()
			->where(['id'=>$templateIds])
			->andWhere("can_send_when_reviewed=0 or can_send_when_feedbacked=0")
			->all();
		
		$check = self::checkIfAmzCsTemplateHasGetReviewFeedbackRecently($templates);
		
		if(empty($check))
			return $rtn;
		else{
			$all_ok = true;
			$info = '';
			foreach ($check as $temp_id=>$check_ret){
				if( !empty($check_ret['review']) && !empty($check_ret['feedback']) ){
					//review and feedback 最近拉取ok
				}else{
					//review or feedback 拉取outdated
					$all_ok = false;
					$info .= '模板'.( empty($check_ret['temp_name'])? '模板id:'.$temp_id : '模板:'.$check_ret['temp_name'] ).' 最近12小时内没有同步过review或feedback！';
				}
			}
			$rtn['result'] = $all_ok;
			$rtn['info'] = $info;
			return $rtn;
		}
	}
	
	/**
	 +----------------------------------------------------------
	 * 检查模板是否近期拉取过review和feedback
	 * @param	YiiModels	$templates
	 * @return	array
	 * @author	lzhl	2017-4-24	初始化
	 +----------------------------------------------------------
	 **/
	public static function checkIfAmzCsTemplateHasGetReviewFeedbackRecently($templates, $time_out=43200 ){
		$rtn = [];
		if(empty($templates))
			return $rtn;
		
		$time_info = ClientHelper::getClientReportDateInfo('all', 'all');
		$MARKETPLACE_REGION = AmazonApiHelper::$AMAZON_MARKETPLACE_REGION_CONFIG;
		foreach ($templates as $temp){
			$temp_id = empty($temp->id)?'':$temp->id;
			$rtn[$temp_id] = ['review'=>false,'feedback'=>false];//初始化两种行为最近都无进行过
			if(empty($temp_id))	//没有对应模板记录
				continue;
			$rtn[$temp_id]['temp_name'] = $temp->name;
			if(!empty($time_info[$temp->seller_id])){//有该店铺的拉取记录
				
				$template_site = empty($MARKETPLACE_REGION[$temp->site_id])?$temp->site_id:$MARKETPLACE_REGION[$temp->site_id];
				//判断review拉取时间是否outdate
				if(!empty($time_info[$temp->seller_id][$template_site]['review_time'])){
					$rtn[$temp_id]['review'] = ((time() - (int)$time_info[$temp->seller_id][$template_site]['review_time']) > $time_out)?true:false;
				}
				//判断feedback拉取时间是否outdate
				if(!empty($time_info[$temp->seller_id]['feedback_time'])){
					$rtn[$temp_id]['feedback'] = ((time() - (int)$time_info[$temp->seller_id][$template_site]['feedback_time']) > $time_out)?true:false;
				}
			}
		}
	
		return $rtn;
	}
	
	/**
	 * @desc	检查邮箱地址是否已经授权小老板aws
	 * @desc	返回信息中的error_code ： '0':无异常,'1':参数有误,'2'：接口调用失败,'3'：未授权,'4'：Exception
	 * @param	$email_address
	 * @return	array('success'=>boolean,'message'=>string,'error_code'=>string/int)
	 * @author	lzhl	2017-5-09	初始化
	 **/
	public static function checkEmailAddressVerifye($email_address){
		$result=['success'=>true,'message'=>'','error_code'=>0];
		$puid = \Yii::$app->subdb->getCurrentPuid();
		try{
			$email_address = trim($email_address);
			if(empty($email_address))
				return ['success'=>false,'message'=>'email地址有误!','error_code'=>'1'];
			
			//获取aws账号已经授权的邮箱list
			$rtn = \eagle\modules\amazon\apihelpers\AmazonSesHelper::listIdentities('default');
			if(!$rtn['success']){
				$result['success'] = false;
				$result['message'] = '获取验证结果失败，请联系客服。E001';
				$result['error_code'] = '2';
				$journal_id = SysLogHelper::InvokeJrn_Create("Amazoncs",__CLASS__, __FUNCTION__ , array($puid,$email_address,$rtn));
			}else{
				$Identities = $rtn['Identities'];
				if(!in_array($email_address,$Identities)){
					$result['success'] = false;
					$result['message'] = '验证失败：邮箱未授权！';
					$result['error_code'] = '3';
				}
			}
		}catch(\Exception $e){
			$result['success'] = false;
			$result['message'] = '获取验证结果失败，请联系客服。E002';
			$journal_id = SysLogHelper::InvokeJrn_Create("Amazoncs",__CLASS__, __FUNCTION__ , array($puid,$email_address,print_r($e->getMessage(),true)));
			$result['error_code'] = '4';
		}
		return $result;
	}
	
	/**
	 * @desc	检查邮箱地址是否已经授权小老板aws
	 * @desc	返回信息中的error_code ： '0':无异常,'1':参数有误,'2'：接口调用失败,'3'：未授权,'4'：Exception
	 * @param	$email_address
	 * @return	array('success'=>boolean,'message'=>string,'error_code'=>string/int)
	 * @author	lzhl	2017-5-09	初始化
	 **/
	public static function sendAwsVerifyeEmailToUserEmailAddress($email_address){
		$result=['success'=>true,'message'=>'','error_code'=>0];
		try{
			$email_address = trim($email_address);
			if(empty($email_address))
				return ['success'=>false,'message'=>'email地址有误!','error_code'=>1];
			
			$puid = \Yii::$app->subdb->getCurrentPuid();
			$userFullName = \Yii::$app->user->identity->getFullName();
			if(empty($puid))
				return ['success'=>false,'message'=>'登录状态失效，请重新登录!','error_code'=>1];
			 
			$journal_id = SysLogHelper::InvokeJrn_Create("Amazoncs",__CLASS__, __FUNCTION__ , array($puid,$email_address));
	
			$rtn = \eagle\modules\amazon\apihelpers\AmazonSesHelper::sendVerifyEmail($email_address);
			if(!$rtn['success']){
				$result['success'] = false;
				$result['message'] = '发送验证邮件失败：接口出错';
				$result['error_code'] = '2';
				SysLogHelper::InvokeJrn_UpdateResult($journal_id, print_r($rtn,true));
			}else{
				SysLogHelper::InvokeJrn_UpdateResult($journal_id, $result);
			}
		}catch(\Exception $e){
			$result['success'] = false;
			$result['message'] = '发送验证邮件失败:后台传输出错';
			$result['error_code'] = '3';
			if(isset($journal_id)){
				SysLogHelper::InvokeJrn_UpdateResult($journal_id, print_r($e->getMessage(),true));
			}
		}
		return $result;
	}
}//end of class
?>