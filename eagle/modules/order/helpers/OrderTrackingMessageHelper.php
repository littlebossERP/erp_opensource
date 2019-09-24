<?php
namespace eagle\modules\order\helpers;

use yii\data\Pagination;
use eagle\modules\order\models\OdOrder;
use eagle\modules\message\helpers\MessageHelper;
use eagle\modules\tracking\helpers\TrackingHelper;
use eagle\models\LtCustomizedRecommendedGroup;
use eagle\models\LtCustomizedRecommendedProd;
use eagle\modules\tracking\models\Tracking;
use eagle\modules\tracking\helpers\CarrierTypeOfTrackNumber;
use eagle\modules\order\models\OdEbayOrder;
use eagle\modules\util\helpers\ImageCacherHelper;
use eagle\models\UserEdmQuota;
use eagle\modules\util\helpers\TimeUtil;
use eagle\modules\carrier\helpers\CarrierHelper;
use eagle\modules\message\models\PlatformStoreEmailAddress;
use eagle\modules\platform\apihelpers\CdiscountAccountsApiHelper;
use eagle\modules\platform\apihelpers\AmazonAccountsApiHelper;
use eagle\modules\util\helpers\SysLogHelper;
use eagle\modules\util\helpers\TranslateHelper;
use eagle\modules\order\models\OdOrderShipped;
use eagle\modules\util\helpers\ConfigHelper;

class OrderTrackingMessageHelper
{
	/**
	 +---------------------------------------------------------------------------------------------
	 * 获取可发送二次营销/启运通知等信件的订单列表
	 +---------------------------------------------------------------------------------------------
	 * @param	string		$keyword		订单或者物流号
	 * @param	array		$params			筛选条件数组
	 * @param	int			$date_from		筛选 订单创建最早于
	 * @param	int			$date_to		筛选 订单创建最迟于
	 * @param	string		$sort			排序column
	 * @param	string		$order			ASC / DECS
	 +---------------------------------------------------------------------------------------------
	 * @return	array  		 订单数据models
	 +---------------------------------------------------------------------------------------------
	 * @author	lzhl		2016/07/16		初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	public static function getOrderTrackingMessageListDataByCondition($keyword,$params,$date_from,$date_to,$sort,$order,$pageSize=50){
		$delivery_days_settings = ConfigHelper::getConfig('UserSettingdServiceDeliveryDays','NO_CACHE');
		$delivery_days_settings = empty($delivery_days_settings)?[]:json_decode($delivery_days_settings,true);
		
		$query=OdOrder::find()->where(['order_relation'=>['normal','sm'] ]);//只显示normal和合并出来的新订单 sm
		
		if(empty($sort)){
			$sort = 'create_time';
			$order = 'desc';
		}
	
		//如果keyword不为空，用户录入了模糊查询
		if(!empty($keyword)){
			$query->andWhere("order_source_order_id ='$keyword' or customer_number ='$keyword' ");
		}
	
		//如果from日期或者to日期有，添加进去filter
		if(!empty($date_from)){
			$date_from = strtotime($date_from);
			$query->andWhere('order_source_create_time >= :create_time_from',[':create_time_from'=>$date_from]);
		}
		if(!empty($date_to)){
			$date_to = strtotime($date_to);
			$query->andWhere('order_source_create_time <= :create_time_to',[':create_time_to'=>$date_to]);
		}
	
		if(isset($params['page'])) unset($params['page']);
		if(isset($params['per-page'])) unset($params['per-page']);
	
		foreach ($params as $fieldName=>$val){
			if(!empty($val)){
				if($fieldName=='order_ship_time'){
					if($val=='not null'){
						$query->andWhere(" (order_ship_time is not null and order_ship_time<>'') or (delivery_time is not null and delivery_time<>'' and delivery_time != 0) or (complete_ship_time is not null and complete_ship_time<>'' and complete_ship_time != 0)");
						
					}elseif($val=='-20days'){//求好评，默认是20日//新加入自定义运输服务到达日期，需要加入到查询条件
						if(!empty($params['default_shipping_method_code'])){
							$delivery_days = empty($delivery_days_settings[(string)$params['default_shipping_method_code']])?20:(int)$delivery_days_settings[(string)$params['default_shipping_method_code']];
							$query->andWhere(' (order_ship_time<=\''.date("Y-m-d H:i:s" ,time()-3600*24*$delivery_days).'\') or (delivery_time is not null and delivery_time != 0 and delivery_time<= '. (time()-3600*24*$delivery_days) .') or (complete_ship_time is not null and complete_ship_time != 0 and complete_ship_time<= '. (time()-3600*24*$delivery_days) .')' );
						}else{
							$addi_sql = '';
							$code_group_by_days = [];
							$all_codes = [];
							foreach ($delivery_days_settings as $method_code=>$delivery_days){
								$code_group_by_days[$delivery_days][] = (int)$method_code;
								$all_codes[] = (int)$method_code;
							}
							foreach ($code_group_by_days as $days=>$code_arr){
								$days = (int)$days;
								$time_query = '(order_ship_time<=\''.date("Y-m-d H:i:s" ,time()-3600*24*$days).'\') or (delivery_time is not null and delivery_time != 0 and delivery_time<= '. (time()-3600*24*$days) .') or (complete_ship_time is not null and complete_ship_time != 0 and complete_ship_time<= '. (time()-3600*24*$days) .')';
								if(empty($addi_sql))
									$addi_sql = ' (`default_shipping_method_code` in ('.implode(',', $code_arr).') and ('.$time_query.') ) ';
								else
									$addi_sql .= ' or (`default_shipping_method_code` in ('.implode(',', $code_arr).') and ('.$time_query.') ) ';
							}
							if(!empty($all_codes)){
								$default_time_query = '(order_ship_time<=\''.date("Y-m-d H:i:s" ,time()-3600*24*20).'\') or (delivery_time is not null and delivery_time != 0 and delivery_time<= '. (time()-3600*24*20) .') or (complete_ship_time is not null and complete_ship_time != 0 and complete_ship_time<= '. (time()-3600*24*20) .')';
								if(empty($addi_sql))
										$addi_sql = ' (`default_shipping_method_code` not in ('.implode(',', $all_codes).') and ('.$default_time_query.') ) ';
									else
										$addi_sql .= ' or (`default_shipping_method_code` not in ('.implode(',', $all_codes).') and ('.$default_time_query.') ) ';
							}else{
								$addi_sql = '(order_ship_time<=\''.date("Y-m-d H:i:s" ,time()-3600*24*20).'\') or (delivery_time is not null and delivery_time != 0 and delivery_time<= '. (time()-3600*24*20) .') or (complete_ship_time is not null and complete_ship_time != 0 and complete_ship_time<= '. (time()-3600*24*20) .')';
							}
							/*
							foreach ($delivery_days_settings as $method_code=>$delivery_days){
								$delivery_days = (int)$delivery_days;
								$time_query = '(order_ship_time<=\''.date("Y-m-d H:i:s" ,time()-3600*24*$delivery_days).'\') or (delivery_time is not null and delivery_time != 0 and delivery_time<= '. (time()-3600*24*$delivery_days) .')';
								if(empty($addi_sql))
									$addi_sql = ' (`default_shipping_method_code`='.$method_code.' and ('.$time_query.') ) ';
								else 
									$addi_sql .= ' or (`default_shipping_method_code`='.$method_code.' and ('.$time_query.') ) ';
							}
							*/
							
							$query->andWhere($addi_sql);
						}
						//$query->andWhere(' (order_ship_time<=\''.date("Y-m-d H:i:s" ,time()-3600*24*20).'\') or (delivery_time is not null and delivery_time != 0 and delivery_time<= '. (time()-3600*24*20) .') ' );
					}
				}
				else
					$query->andWhere([$fieldName=>$val]);
			}
		}
		//这两个字段其实是卖家给买家的评价，不能作为排除条件
// 		$query->andWhere("(seller_commenttype is null or seller_commenttype='') or (seller_commenttext is null or seller_commenttext='')");
		
		//$commandQuery = clone $query;
		//echo $commandQuery->createCommand()->getRawSql();
	
		$pagination = new Pagination([
				'totalCount'=> $query->count(),
				'defaultPageSize'=> 50,
				'pageSize'=> $pageSize,
				'pageSizeLimit'=>  [50,200],
				'params'=>$_REQUEST,
				]);
	
		$data['pagination'] = $pagination;
	
		$data['data'] = $query
			->offset($pagination->offset)
			->limit($pagination->limit)
			->orderBy(" $sort $order ")
			->all();
	
		return $data;
	}
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 指定order no 然后从规则库中逐一将其归类成 匹配成功或 匹配失败两种情况
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param     $OrderIdList		string/array			xlb单号    eg : '123' or ['123','124']
	 *
	 +---------------------------------------------------------------------------------------------
	 * @return						['matchRoleTracking'=>[0=>['track_no'=>'123',
	 *															'role_name'=>'role name',
	 *															'platform'=>'ebay', // ebay , wish , aliexpress , dhgate ...
	 *															'order_id'=>'123',
	 *															'nation'=>'中国',
	 *															'template_id'=>1, ],..... ] ,
	 * 								'unMatchRoleTracking'=>[0=>['track_no'=>'123',
	 *															'role_name'=>'role name',
	 *															'platform'=>'ebay', // ebay , wish , aliexpress , dhgate ...
	 *															'order_id'=>'123',
	 *															'nation'=>'中国', ] , .....]
	 * 									]
	 +---------------------------------------------------------------------------------------------
	 * @author		lzhl	2016/7/17		初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function matchMessageRole_Oms($OrderIdList){
		$orderInfos =  OdOrder::find()->Where(['order_id'=>$OrderIdList])->asArray()->all();
	
		$AccountMapping = TrackingHelper::getAccountMappingIDData();
		//print_r($AccountMapping);
		$data['matchRoleTracking'] = [];
		$data['unMatchRoleTracking'] = [];
		$order_source_arr = [];//所有订单来源平台
		$order_seller_arr = [];//所有订单来源seller
		$isSameSeller = false;//判断订单是否是同一个卖家账号
		foreach($orderInfos as $order ){
			if(!in_array($order['order_source'], $order_source_arr))
				$order_source_arr[] = $order['order_source'];
			if(!in_array($order['selleruserid'], $order_seller_arr))
				$order_seller_arr[] = $order['selleruserid'];
			
			
			if (!empty($AccountMapping[$order['order_source']][$order['selleruserid']]))
				$account_id = $AccountMapping[$order['order_source']][$order['selleruserid']];
			else{
				$account_id = 0;
			}
			/**/	
			$tmp_platform = (!empty($order['order_source']))?$order['order_source']:'';
			$tmp_to_nation = (!empty($order['consignee_country_code']))?$order['consignee_country_code']:'--';
			//因为暂时还是用tracker的匹配规则，status暂时设置为空已应对所有tracker状态
			$tmp_status = '';
			
			// dzt20190912 有速卖通客户要求匹配已付款未发货订单 发送站内信来确认收货地址。
			if($order['order_status'] == 200)
			    $tmp_status = "paidandunship";
			
			$role = MessageHelper::getTopTrackerAuotRule($tmp_platform, $account_id, $tmp_to_nation, $tmp_status);
			if (!empty($role['name']))
				$roleName = $role['name'];
			else
				$roleName ='';
			if (!empty($role['template_id'] ))
				$templateId = $role['template_id'] ;
			else
				$templateId = 0;
			/*
			$roleName = MessageHelper::getTopOmsAuotRuleName($tmp_platform, $account_id, $tmp_to_nation);
			if (!empty($role['template_id'] ))
				$templateId = $role['template_id'] ;
			else
				$templateId = 0;
			*/
			$odShipped = OdOrderShipped::find()->where(['order_id'=>$order['order_id']])->orderBy("created DESC")->one();
			if(!empty($odShipped))
				$track_no = $odShipped->tracking_number;
			else 
				$track_no = empty($order['customer_number'])?'':$order['customer_number'];
			if (!empty($roleName)){
				//matched role
				$data['matchRoleTracking'][] = [
				'track_no'=>$track_no,
				'role_name'=>$roleName,
				'platform'=>$order['order_source'],
				'order_id'=>$order['order_id'],
				'order_no'=>$order['order_source_order_id'],
				'nation'=>$label = TrackingHelper::autoSetCountriesNameMapping($tmp_to_nation),
				'template_id'=>$templateId,
				];
			}else{
				//unmatch role
				$data['unMatchRoleTracking'][] = [
				'track_no'=>$track_no,
				'role_name'=>'',
				'platform'=>$order['order_source'],
				'order_id'=>$order['order_id'],
				'order_no'=>$order['order_source_order_id'],
				'nation'=>TrackingHelper::autoSetCountriesNameMapping($tmp_to_nation),
				];
			}
		}
		if(count($order_source_arr)==1 && count($order_seller_arr)==1)
			$isSameSeller = true;
		
		$data['isSameSeller'] = $isSameSeller;
		return $data;
	}//end of matchMessageRole
	
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 获取 二次营销 用户自定义推荐商品分组商品
	 +---------------------------------------------------------------------------------------------
	 * @param	int			$puid			用户uid
	 * @param	string		$platform		平台
	 * @param	string		$sellerid		卖家账号
	 * @param	int			$group_id		推荐商品组id
	 * @param	int			$showCount		显示商品数
	 +---------------------------------------------------------------------------------------------
	 * @return	array
	 +---------------------------------------------------------------------------------------------
	 * @author	lzhl		2016/07/16		初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	public static function getTargetCustomizedRecommendedProds($puid, $platform, $sellerid, $group_id=0, $showCount=8){
		$rtn = [];
		if(empty($puid))
			$puid = \Yii::$app->user->identity->getParentUid();
		
		//var_dump($puid);var_dump($platform);var_dump($sellerid);var_dump($group_id);var_dump($showCount);
		
		//先查找gruop id，保证group能对应platform和sellerid，避免数据错配
		$targetGroup = LtCustomizedRecommendedGroup::find()->where(['puid'=>$puid])
			->andWhere(['platform'=>$platform])
			->andWhere(['seller_id'=>$sellerid])
			->andWhere(['id'=>$group_id])
			->asArray()->One();
		
		if(empty($targetGroup) || (int)$group_id!==(int)$targetGroup['id'])
			return $rtn;//无有效对应分组
		
		$groupProds = LtCustomizedRecommendedProd::find()->where(['puid'=>$puid,'group_id'=>$targetGroup['id']])->asArray()->All();
		if(empty($groupProds))
			return $rtn;//分组无商品
		
		if(count($groupProds) > $showCount){
			$numbers = range(0, count($groupProds)-1 ); //range 是将1到100列成一个数组
			$rand_result = array_rand($numbers, $showCount); //array_rand 顺序随机取出数组中的指定数目
		}else{
			$rand_result = range(0, count($groupProds)-1 );
		}
		$index = 0;
		foreach ($groupProds as $i=>$prod){
			if(in_array($i,$rand_result) ){
				$rtn['prods'][$index] = $prod;
				$rtn['prods'][$index]['product_name'] = empty($prod['title'])?'':$prod['title'];
				$rtn['prods'][$index]['product_image'] = empty($prod['photo_url'])?'':$prod['photo_url'];
				$rtn['prods'][$index]['product_url'] = empty($prod['product_url'])?'':$prod['product_url'];
				$rtn['prods'][$index]['sale_currency'] = $prod['currency'];
				$rtn['prods'][$index]['sale_price'] = $prod['price'];
				$index++;
			}
		}
		return $rtn;
	}
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 根据不用的订单平台，生成对应的订单列表信息tr (order信息行) 
	 +---------------------------------------------------------------------------------------------
	 * @author	lzhl		2016/07/26		初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	public static function generateOneOrderInfoTr($platform,$order,$countryArr,$carriers){
		$non17Track = CarrierTypeOfTrackNumber::getNon17TrackExpressCode();
		 
		$Html = "";
		$divTagHtml="";
		$div_event_html="";
		$Html .= '<tr style="background-color: #f4f9fc">';
		//order health
		$ck_health_check = '';
		if (empty($order->default_shipping_method_code) ||empty($order->default_carrier_code)){
			$ck_health_check = 'data-health="noshipment"';
		}elseif($order->exception_status == OdOrder::EXCEP_SKUNOTMATCH){
			$ck_health_check = 'data-health="nosku"';
		}
		//勾选框TD
		$Html .='<td>
					<span><span class="orderspread glyphicon glyphicon-minus" onclick="spreadorder(this,\''.$order->order_id.'\');"></span></span>
					<label><input type="checkbox" class="ck" name="order_id[]" value="'.$order->order_id.'" '.$ck_health_check.' data-orderNo="'.$order->order_source_order_id.'"></label>
				</td>';
		//订单号TD
		$Html .='<td>'.$order->order_source_order_id.'<br>小老板id:'.(int)$order->order_id.'<br>';
			if ($order->exception_status>0 && $order->exception_status!='201'){
				$Html .='<div title="'.OdOrder::$exceptionstatus[$order->exception_status].'" class="exception_'.$order->exception_status.'"></div>';
			}
			if (strlen($order->user_message)>0){
				$Html .='<div title="'.OdOrder::$exceptionstatus[OdOrder::EXCEP_HASMESSAGE].'" class="exception_'.OdOrder::EXCEP_HASMESSAGE.'"></div>';
			}
			
			$divTagHtml .= '<div id="div_tag_'.$order->order_id.'"  name="div_add_tag" class="div_add_tag"></div>';
			$TagStr = OrderTagHelper::generateTagIconHtmlByOrderId($order->order_id);
						
			if (!empty($TagStr)){
				$TagStr = "<span class='btn_tag_qtip' data-order-id='".$order->order_id."' >$TagStr</span>";
			}
			$Html .= $TagStr;
		$Html .= '</td>';
		//商品sku统计TD
		$Html .= '<td>';
			if (count($order->items)){
				foreach ($order->items as $item){
					if (isset($item->sku) && strlen($item->sku)){
						$Html .= $item->sku.'&nbsp;<b>X<span'.($item->quantity>1?' class="multiitem"':'').'>'.$item->quantity.'</span></b><br>';
					}
				}
			}
		$Html .= '</td>';
		//金额统计TD
		$Html .= '<td>';
			$Html .= $order->grand_total.'&nbsp;'.$order->currency;
		$Html .= '</td>';
		//付款日期TD
		$Html .= '<td>';
			$Html .= $order->paid_time>0?date('y/m/d H:i:s',$order->paid_time):'';
			if (in_array($order->order_status , [OdOrder::STATUS_PAY , OdOrder::STATUS_WAITSEND  , OdOrder::STATUS_SHIPPING])){
				$tmpTimeLeft =  ((!empty($order->fulfill_deadline))?'<br><span id="timeleft_'.$order->order_id.'" class="fulfill_timeleft" data-order-id="'.$order->order_id.'" data-time="'.($order->fulfill_deadline-time()).'"></span>':"");
				$Html .= $tmpTimeLeft;
			}
		$Html .= '</td>';
		//收件国家TD
		$Html .= '<td>
					<label title="'.$order->consignee_country.'">'.$order->consignee_country_code.'</label>
				 </td>';
		//运输服务TD
		$Html .= '<td>';
		if($order->order_source=='cdiscount'){
			$cd_customer_shipped_method = CarrierHelper::getCdiscountBuyerShippingServices();
			$Html .= '[客选物流:'.(isset($cd_customer_shipped_method[$order->order_source_shipping_method])?$cd_customer_shipped_method[$order->order_source_shipping_method]:$order->order_source_shipping_method ).']<br>';
		}
		if (strlen($order->default_shipping_method_code))
				$Html .= '<a onclick="StationLetter.setServiceDeliveryDays(\''.$order->default_shipping_method_code.'\')">['.@$carriers[$order->default_shipping_method_code].']</a>';
		$Html .= '</td>';
		//平台原始状态TD
		$Html .= '<td>';
			switch ($order->order_source){
				case 'aliexpress':
					if (isset(OdOrder::$aliexpressStatus[$order->order_source_status])){
						$Html .= OdOrder::$aliexpressStatus[$order->order_source_status];
					}else{
						$Html .= $order->order_source_status;
					}
					break;
				case 'ebay':
					//<!-- check图标 -->
					$check = OdEbayOrder::findOne(['ebay_orderid'=>$order->order_source_order_id]);
					if (!empty($check) && $check->checkoutstatus=='Complete'){
						$Html .= '<div title="已结款" class="sprite_check_1"></div>';
					}else{
						$Html .= '<div title="未结款" class="sprite_check_0"></div>';
					}
					//<!-- 付款状态图标 -->
					if ($order->pay_status==0 || $order->paid_time==0){
						$Html .=  '<div title="未付款" class="sprite_pay_0"></div>';
					}elseif ($order->pay_status==1 && $order->paid_time>0){
						$Html .= '<div title="已付款" class="sprite_pay_1"></div>';
					}elseif ($order->pay_status==2 ){
						$Html .= '<div title="支付中" class="sprite_pay_2"></div>';
					}elseif ($order->pay_status==3){
						$Html .= '<div title="已退款" class="sprite_pay_3"></div>';
					}
					//<!-- 发货图标 -->
					if ($order->shipping_status==1){
						$Html .= '<div title="已发货" class="sprite_shipped_1"></div>';
					}else{
						$Html .= '<div title="未发货" class="sprite_shipped_0"></div>';
					}
					break;
				case 'cdiscount':
					$cd_source_status_mapping = CdiscountOrderHelper::$cd_source_status_mapping;
					if(isset($cd_source_status_mapping[$order->order_source_status]))
						$Html .= $cd_source_status_mapping[$order->order_source_status];
					else
						$Html .= $order->order_source_status;
					break;
				default:
					$Html .= $order->order_source_status;
					break;
					
			}
		$Html .= '</td>';
		//小老板状态TD
		$Html .= '<td>';
			$Html .= '<b>'.((isset(OdOrder::$status[$order->order_status]))?OdOrder::$status[$order->order_status]:$order->order_status).'</b>';
		$Html .= '</td>';
		//物流状态TD
		$Html .= '<td>';
			$carrierErrorHtml = '';
			if (!empty($order->carrier_error)){
				$carrierErrorHtml .= $order->carrier_error;
				//echo 'rt='.stripos('123'.$order->carrier_error,'地址信息没有设置好，请到相关的货代设置地址信息');
				if (stripos('123'.$order->carrier_error,'地址信息没有设置好，请到相关的货代设置地址信息')){
					//echo "<br><br>************<br>".$order->default_carrier_code;
					if (!empty($order->default_carrier_code)){
						$carrierErrorHtml .= '<br><a  target="_blank" href="/configuration/carrierconfig/index?carrier_code='.$order->default_carrier_code.'">'.TranslateHelper::t('设置发货地址').'</a>';
					}
				}
			}
			if (!empty($carrierErrorHtml)) $carrierErrorHtml.='<br>';
			
			$shipmentHealthCheckHtml = '';
			if ($order->order_status==OdOrder::STATUS_PAY){
				
				if (empty($order->default_shipping_method_code) ||empty($order->default_carrier_code) ){
					$shipmentHealthCheckHtml .=  '<b style="color:red;">'.TranslateHelper::t('运输服务未选择').'</b>';
				}
				if ($order->default_warehouse_id <0){
					if (!empty($shipmentHealthCheckHtml)) $shipmentHealthCheckHtml.='<br>';
					$shipmentHealthCheckHtml .=  '<b style="color:red;">'.TranslateHelper::t('仓库未选择').'</b>';
				}
				
				if (!empty($shipmentHealthCheckHtml))  $shipmentHealthCheckHtml.='<br><a onclick="doactionone(\'changeWHSM\',\''.$order->order_id.'\');">'.TranslateHelper::t('设置运输服务与仓库').'</a>';
				
			}
						
			if (!empty($shipmentHealthCheckHtml) || !empty($carrierErrorHtml)){
				if ($order->order_status ==OdOrder::STATUS_PAY)
					$Html .= '<div class="nopadingAndnomagin alert alert-danger">'.$carrierErrorHtml.$shipmentHealthCheckHtml."</div>";
			}

			if ($order->order_status=='300'){
				$Html .= CarrierHelper::$carrier_step[$order->carrier_step].'<br>';
			}
			if (count($order->trackinfos)){
				$tracking_number='';
				foreach ($order->trackinfos as $ot){
					$class = 'text-info';
					$qtip = '';
					if ($ot->status==1){
						$class = 'text-success';
						$qtip = '<span qtipkey="tracking_number_with_non_error"></span>';
					}elseif ($ot->status==0){
						$class = 'text-warning';
						$qtip = '<span qtipkey="tracking_number_with_pending_status"></span>';
					}elseif($ot->status==2){
						$class = 'text-danger';
						$qtip = '<span qtipkey="tracking_number_with_error"></span>';
					}elseif($ot->status==4){
						continue;
					}
					if(!empty($ot->errors)){
						$Html .= '<br><b style="color:red;">'.(($ot->addtype=='手动标记发货')?'手动标记发货失败:':'物流处理问题:').$ot->errors.'<br></b>';
					}
					if (strlen($ot->tracking_number)){
						$tracking_number = $ot->tracking_number;
						$trackingOne=Tracking::find()->where(['track_no'=>$ot->tracking_number,'order_id'=>$order->order_source_order_id])
								->orderBy(['update_time'=>SORT_DESC])->one();
						if(!empty($trackingOne)) $carrier_type = $trackingOne->carrier_type;
						else $carrier_type = '';
						if(!in_array($carrier_type, $non17Track)) $tracking_info_type='17track';
						else $tracking_info_type = '';
						$Html .= '<a href="javascript:void(0);" onclick="OmsViewTracker(this,\''.$ot->tracking_number.'\')" title="'.$ot->shipping_method_name.'" data-info-type="'.$tracking_info_type.'"><span class="order-info"><font class="'.$class.'">'.$ot->tracking_number.'</font>'.$qtip.'</span></a>';
						//Tracker忽略物此流号操作	liang 16-02-27 start
						//当标记发货成功时，才出现忽略操作按钮
						if($ot->status==1 && $order->logistic_status!=='ignored'){
							$Html .= '<span class="iconfont icon-ignore_search" onclick="OrderTrackingMessage.ignoreTrackingNo(\''.$order->order_id.'\',\''.$ot->tracking_number.'\')" data-toggle="popover" data-content="使物流查询助手忽略此物流号(不可逆操作)。当标记发货成功后，可选择此操作。忽略后，物流助手将不会再查询其信息" data-html="true" data-trigger="hover" data-placement="top" style="vertical-align:baseline;cursor:pointer;"></span>';
						}
						if( $order->logistic_status=='ignored'){
							$Html .= '<span style="color: #DCDCDC;cursor:pointer;"title="已经忽略物流信息查询">已忽略</span>';
						}
						$Html .= '<br>';
						//组织显示物流明细的东东
						$div_event_html .= "<div id='div_more_info_".$ot->tracking_number."' class='div_more_tracking_info div_space_toggle tracking_info_dialog_".$ot->tracking_number."'>";
							
						$all_events_str = "";
							
						$all_events_rt = TrackingHelper::generateTrackingEventHTML([$ot->tracking_number],[],true);
						if (!empty($all_events_rt[$ot->tracking_number])){
							$all_events_str = $all_events_rt[$ot->tracking_number];
						}
								
						$div_event_html .=  $all_events_str;
							
						$div_event_html .= "</div>";
					}
				}
			}
		$Html .= '</td>';
		//操作选项TD
		$Html .= '<td><div style="width:100px;position:relative;display:inline-block;">';
			$edit_order_link = '';
			switch($order->order_source){
				case 'aliexpress':
					$edit_order_link = '/order/aliexpressorder/edit';
					break;
				case 'ebay':
					$edit_order_link = '/order/order/edit';
					break;
				case 'ebay':
					$edit_order_link = '/order/cdiscount-order/edit';
					break;
				case 'amazon':
					$edit_order_link = '/order/amazon-order/edit';
					break;
				case 'cdiscount':
					$edit_order_link = '/order/cdiscount-order/edit';
					break;
				default:
					$edit_order_link = '/order/order/edit';
					break;
			}
			$Html .= '<a href="'.$edit_order_link.'?orderid='.$order->order_id.'" target="_blank" style="margin:3px"><span class="egicon-edit" title="编辑订单" style="height:16px"></span></a>';
			//$Html .= '<a href="#" onclick="ShowDetailMessage(\''.$order->selleruserid.'\',\''.$order->source_buyer_user_id.'\',\'aliexpress\',\'\',\'0\',\'\')"><span class="egicon-envelope2" titile="发送站内信"></span></a>';
			$Html .= '<a href="#" onclick="StationLetter.showMessageBox(\''.$order->order_id.'\',\''.$order->order_source_order_id.'\',\''.(!empty($tracking_number)?$tracking_number:'').'\',\'role\')" style="margin:3px"><span class="egicon-envelope" title="发送通知" style="height:16px"></span></a>';
			$Html .= '<a href="#" onclick="OrderTrackingMessage.ignoreMsgSend(\''.$order->order_id.'\')" style="margin:3px;top:-4px;position:absolute;"><span class="iconfont icon-mail_sended" title="忽略发送" style="color:red"></span></a>';
			$Html .= '</div>';
		$Html .= '</td>';
		$Html .= '</tr>';
		
		return [
			'trHtml'=>$Html,
			'divTagHtml'=>$divTagHtml,
			'div_event_html'=>$div_event_html,
		];
	}
	
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 根据不用的订单平台，生成对应的订单列表信息tr (item信息行)
	 +---------------------------------------------------------------------------------------------
	 * @param	int			$puid			用户uid
	 * @param	string		$platform		平台
	 * @param	string		$sellerid		卖家账号
	 * @param	int			$group_id		推荐商品组id
	 * @param	int			$showCount		显示商品数
	 +---------------------------------------------------------------------------------------------
	 * @return	array
	 +---------------------------------------------------------------------------------------------
	 * @author	lzhl		2016/07/26		初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	public static function generateOneOrderItemTr($platform,$order,$item,$itemIndex,$warehouses,$lastMessage){
		$Html="";
		$uid = \Yii::$app->user->id;
		
		$Html.='<tr class="xiangqing '.$order->order_id.'">';
		//图片
		if(in_array($platform, ['aliexpress','amazon']))//读原图的平台
			$Html.='<td style="border:1px solid #d9effc;"><img class="prod_img" src="'.$item->photo_primary.'" width="60px" height="60px" data-toggle="popover" data-content="<img width=\'350px\' src=\''.str_replace('.jpg_50x50','',$item->photo_primary).'\'>" data-html="true" data-trigger="hover"> </td>';
		if($platform=='ebay')
			$Html.='<td style="border:1px solid #d9effc;"><img class="prod_img" src="'.$item->photo_primary.'" width="60px" height="60px" data-toggle="popover" data-content="<img width=\'350px\' src=\''.$item->photo_primary.'\'>" data-html="true" data-trigger="hover"> </td>';
		if($platform=='cdiscount'){
			if(!empty($item->photo_primary))
				$photo_primary = ImageCacherHelper::getImageCacheUrl($item->photo_primary,$uid,1);
			else 
				$photo_primary='';
			$Html.='<td style="border:1px solid #d9effc;"><img class="prod_img" src="'.$photo_primary.'" width="60px" height="60px" data-toggle="popover" data-content="<img width=\'350px\' src=\''.$photo_primary.'\'>" data-html="true" data-trigger="hover"> </td>';
		}
		
		
		//SKU & NAME
		$Html.='<td colspan="2" style="border:1px solid #d9effc;text-align:justify;">';
			$Html.='SKU:<b>'.$item->sku.'</b><br>';
			if(in_array($platform, ['aliexpress','amazon','cdiscount']))//SKU和名称没有额外处理的平台
				$Html.='<a href="'.$item->product_url.'" target="_blank">'.$item->product_name.'</a><br>';
			if($platform=='ebay'){
				$Html.='['.$item->order_source_itemid.']';
				$Html.='<a href="order_source_itemid'.$item->order_source_itemid.'" target="_blank">'.$item->product_name.'</a><br>';
			}
			
			if ($platform=='aliexpress' && !empty($item->product_attributes)){
				$tmpProdctAttrbutes = explode(' + ' ,$item->product_attributes );
				if (!empty($tmpProdctAttrbutes)){
					foreach($tmpProdctAttrbutes as $_tmpAttr){
						$Html.='<span class="label label-warning">'.$_tmpAttr.'</span>';
					}
				}
			}						
		$Html.='</td>';
		//数量及单价
		$Html.='<td  style="border:1px solid #d9effc">';
			$Html.=$item->quantity;
		$Html.='</td>';
		//显示买家卖家信息，仓库信息，物流，留言等
		if ($platform=='aliexpress'){
			if ($itemIndex=='0'){
				//买家信息
				$Html.='<td rowspan="'.count($order->items).'" style="border:1px solid #d9effc;text-align:left;" >';
				$Html.='<font color="#8b8b8b">卖家账号:</font><b>'.$order->selleruserid.'</b><br>';
				$Html.='<font color="#8b8b8b">买家姓名:</font><b>'.$order->consignee.'</b><br>';
				$Html.='<font color="#8b8b8b">买家账号:</font><b>'.$order->source_buyer_user_id.'</b><br>';
				$Html.='<font color="#8b8b8b">买家邮箱:</font><b>'.$order->consignee_email.'</b>';
				$Html.='</td>';
				//仓库信息
				$Html.='<td rowspan="'.count($order->items).'" style="border:1px solid #d9effc">';
				$Html.='<b>'.(count($warehouses)?$warehouses[$order->default_warehouse_id]:'').'</b>';
				$Html.='</td>';
				//订单其他信息
				$Html.='<td rowspan="'.count($order->items).'" colspan="2" style="border:1px solid #d9effc;text-align:left;" >';
				$Html.='<font color="#8b8b8b">Aliexpress订单号:</font><b>'.$item->order_source_order_id.'</b><br>';
				$Html.='<font color="#8b8b8b">下单日期:</font><b>'.($order->order_source_create_time>0?date('y/m/d H:i:s',$order->order_source_create_time):'').'</b>';
				$Html.='<br>';
				if( $order->addi_info!='' ){
					$addi_info_arr= json_decode($order->addi_info);
					$Html.='<font color="#8b8b8b">客选物流:</font>';
					if( !empty($addi_info_arr) ){
						if( isset( $addi_info_arr->shipping_service ) )
							foreach( $addi_info_arr->shipping_service as $addi_info_vss )
								$Html.='<b>'.$addi_info_vss.'</b>';
					}
				}
				$Html.='</td>';
				//留言
				$Html.='<td colspan="2" rowspan="'.count($order->items).'" width="150px" style="word-break:break-all;word-wrap:break-word;border:1px solid #d9effc;text-align:left;">';
				$Html.='<font color="#8b8b8b">付款备注:</font><br><b class="text-warning">'.$order->user_message.'</b>';
				$Html.='<font color="#8b8b8b">买家留言:</font><b>';
				if(!empty($lastMessage)){
					$Html.='<a href="javascript:void(0);" onclick="ShowDetailMessage(\''.$order->selleruserid.'\',\''.$order->source_buyer_user_id.'\',\'aliexpress\',\'\',\'0\',\'\')">';
					$Html.=$lastMessage;
					$Html.='</a>';
				}
				$Html.='</b>';
				$Html.='</td>';
				//备注
				$Html.='<td rowspan="'.count($order->items).'" width="150" style="word-break:break-all;word-wrap:break-word;border:1px solid #d9effc">';
				$Html.='<span><font color="red">'.$order->desc.'</font></span>';
				$Html.='<a href="javascript:void(0)" style="border:1px solid #00bb9b;" onclick="updatedesc(\''.$order->order_id.'\',this)" oiid="'.$order->order_id.'"><font color="00bb9b">备注</font></a>';
				$Html.='</td>';
			}
		}
		
		if ($platform=='ebay'){
			if ($itemIndex=='0'){
				//仓库信息
				$Html.='<td rowspan="'.count($order->items).'" style="border:1px solid #d9effc">';
				$Html.='<font color="#8b8b8b">仓库:</font><br>';
				$Html.='<b>'.(($order->default_warehouse_id>0 && count($warehouses))?$warehouses[$order->default_warehouse_id]:'').'</b>';
				$Html.='</td>';
				//买家信息
				$Html.='<td rowspan="'.count($order->items).'" style="border:1px solid #d9effc">';
				$Html.='<font color="#8b8b8b">用户名/邮箱:</font><br><b>'.$order->source_buyer_user_id.'<br>'.$order->consignee_email.'</b>';
				$Html.='</td>';
			}
			//item其他信息
			$Html.='<td colspan="2" style="border:1px solid #d9effc">';
			$Html.='<font color="#8b8b8b">SRN:</font><b>'.$item->order_source_srn.'['.$order->selleruserid.']</b><br>';
			$Html.='<font color="#8b8b8b">下单日期:</font><b>'.($order->order_source_create_time>0?date('Y-m-d H:i:s',$order->order_source_create_time):'').'</b>';
			$Html.='</td>';
			//留言 & 备注
			if ($itemIndex=='0'){
				$Html.='<td colspan="2"  rowspan="'.count($order->items).'" width="150px" style="word-break:break-all;word-wrap:break-word;border:1px solid #d9effc">';
				$Html.='<font color="#8b8b8b">买家留言:</font><b>'.(!empty($order->user_message)?$order->user_message:'');
				if(!empty($lastMessage)){
					$Html.='<a href="javascript:void(0);" onclick="ShowDetailMessage(\''.$order->selleruserid.'\',\''.$order->source_buyer_user_id.'\',\'ebay\',\'\',\'\',\'\',\'\',\'\',\''.$order->order_source_order_id.'\')">';
					$Html.=$lastMessage;
					$Html.='</a>';
				}
				$Html.='</b>';
				$Html.='</td>';
				
				$Html.='<td  rowspan="'.count($order->items).'" width="150" style="word-break:break-all;word-wrap:break-word;border:1px solid #d9effc">';
				$Html.='<span><font color="red">'.$order->desc.'</font></span>';
				$Html.='<a href="javascript:void(0)" style="border:1px solid #00bb9b;" onclick="updatedesc(\''.$order->order_id.'\',this)" oiid="'.$order->order_id.'"><font color="00bb9b">备注</font></a>';
				$Html.='</td>';
			}
		}
		
		if ($platform=='cdiscount'){
			if ($itemIndex=='0'){
				$Html.='<td rowspan="'.count($order->items).'" style="border:1px solid #d9effc">
						<font color="#8b8b8b">仓库:</font><br>
						<b>'.(($order->default_warehouse_id>0&&count($warehouses))?$warehouses[$order->default_warehouse_id]:'').'</b>
					</td>';
				$Html.='<td rowspan="'.count($order->items).'" style="border:1px solid #d9effc">
						<font color="#8b8b8b">cdiscount店铺名 / 买家用户名</font><br>
						<b>'.$order->selleruserid.' / '.$order->consignee.'</b>
					</td>';
				$Html.='<td colspan="2" rowspan="'.count($order->items).'" style="border:1px solid #d9effc;text-align: left;">
						<font color="#8b8b8b">最后操作日期:</font><b>'.(($order->update_time>0)?date('Y-m-d H:i:s',$order->update_time):'').'</b>'.
						((!empty($item->returned_quantity) && !stripos($item->remark, 'refunded')===false)?'<br><b style="color:red">'.$item->remark.'</b>':'').
					'</td>';
				$Html.='<td colspan="2" rowspan="'.count($order->items).'" width="150px" style="word-break:break-all;word-wrap:break-word;border:1px solid #d9effc">
						<font color="#8b8b8b">买家留言:</font>
						<b>'.(!empty($order->user_message)?$order->user_message:'');
						if(!empty($lastMessage) && empty($showLastMessage)){
								$Html.= '<a href="javascript:void(0);" onclick="ShowDetailMessage(\''.$order->selleruserid.'\',\''.$order->source_buyer_user_id.'\',\'cdiscount\',\'\',\'O\',\'\',\'\',\'\',\''.$order->order_source_order_id.'\')">';
								$Html.= $lastMessage;
								$Html.= '</a>';
						}
				$Html.='</b>
					</td>';
				
				$Html.='<td rowspan="'.count($order->items).'" width="150" style="word-break:break-all;word-wrap:break-word;border:1px solid #d9effc">
						<span><font color="red">'.$order->desc.'</font></span>
						<a href="javascript:void(0)" style="border:1px solid #00bb9b;" onclick="updatedesc(\''.$order->order_id.'\',this)" oiid="'.$order->order_id.'"><font color="00bb9b">备注</font></a>
					</td>';
			}
		}
		
		if ($platform=='amazon'){
			if ($itemIndex=='0'){
				//买家信息
				$Html.='<td rowspan="'.count($order->items).'" style="border:1px solid #d9effc;text-align:left;" >';
				//获得店铺名称
				$puid = \Yii::$app->user->identity->getParentUid ();
				$amazonStoreName = AmazonAccountsApiHelper::getStoreNameNameByMerchantId($puid,$order->selleruserid);
				
				$Html.='<font color="#8b8b8b">amazon店铺名:</font><b>'.(empty($amazonStoreName)?$order->selleruserid:$amazonStoreName).'</b><br>';
				$Html.='<font color="#8b8b8b">买家姓名:</font><b>'.$order->consignee.'</b><br>';
				$Html.='<font color="#8b8b8b">买家账号:</font><b>'.$order->source_buyer_user_id.'</b><br>';
				$Html.='<font color="#8b8b8b">marketplace:</font><b>'.$order->order_source_site_id.'</b>';
				$Html.='</td>';
				//仓库信息
				$Html.='<td rowspan="'.count($order->items).'" style="border:1px solid #d9effc">';
				$Html.='<b>'.(count($warehouses)?$warehouses[$order->default_warehouse_id]:'').'</b>';
				$Html.='</td>';
				//订单其他信息
				$Html.='<td rowspan="'.count($order->items).'" colspan="2" style="border:1px solid #d9effc;text-align:left;" >';
				$Html.='<font color="#8b8b8b">amazon订单类型:</font><b>'.$order->order_type.'</b><br>
						<font color="#8b8b8b">amazon订单号: </font><b>'.$item->order_source_order_id.'</b><br>
						<font color="#8b8b8b">售出日期:</font><b>'.($order->order_source_create_time>0?date('Y-m-d H:i:s',$order->order_source_create_time):'').'</b>';
				$Html.='</td>';
				//留言
				$Html.='<td colspan="2" rowspan="'.count($order->items).'" width="150px" style="word-break:break-all;word-wrap:break-word;border:1px solid #d9effc;text-align:left;">';
				$Html.='</td>';
				//备注
				$Html.='<td rowspan="'.count($order->items).'" width="150" style="word-break:break-all;word-wrap:break-word;border:1px solid #d9effc">';
				$Html.='<span><font color="red">'.$order->desc.'</font></span>';
				$Html.='<a href="javascript:void(0)" style="border:1px solid #00bb9b;" onclick="updatedesc(\''.$order->order_id.'\',this)" oiid="'.$order->order_id.'"><font color="00bb9b">备注</font></a>';
				$Html.='</td>';
			}
		}
		$Html.='</tr>';
		
		return $Html;
	}
	
	
	static public function getOdLtMessageMenuByPlatform($platform){
		//获取主url
		$url = \Yii::$app->request->hostinfo;
	
		$MenuArr = array();
	
		$MenuArr['发货确认'] = array(
		        'url'=>$url.'/order/od-lt-message/list-tracking-message?platform='.$platform.'&pos=PNSHP&is_send=N',
		        'qtipkey'=>'oms_tracking_request_shipping',
		        'target'=>'_norefresh'
		);
		
		$MenuArr['启运通知'] = array(
				'url'=>$url.'/order/od-lt-message/list-tracking-message?platform='.$platform.'&pos=RSHP&is_send=N',
				'qtipkey'=>'oms_tracking_request_shipping',
				'target'=>'_norefresh'
		);
	
		$MenuArr['已发货求好评'] = array(
				'url'=>$url.'/order/od-lt-message/list-tracking-message?platform='.$platform.'&pos=RGE&is_send=N',
				'qtipkey'=>'oms_tracking_request_good_evaluation',
				'target'=>'_norefresh'
		);
		
		$MenuArr['发信模板设置'] = array(
				'url'=>$url.'/order/od-lt-message/mail_template_setting',
				'qtipkey'=>'mail_template_setting',
				'target'=>'_norefresh',
		);
		
		$MenuArr['二次营销商品设置'] = array(
				'url'=>$url.'/order/od-lt-message/custom_product_list',
				'qtipkey'=>'custom_recommend_setting',
				'target'=>'_norefresh',
		);
		
		$MenuArr['二次营销商品分组设置'] = array(
				'url'=>$url.'/order/od-lt-message/group_list',
				'qtipkey'=>'custom_recommend_setting',
				'target'=>'_norefresh',
		);
		
		return $MenuArr;
	}
	
	/*
	 * 用户购买edm额度成功之后调用，增加后台emd剩余额度
	 * @author	lzhl		2016/08/11		初始化
	 */
	public static function EDMPurchasedSuccess($puid,$params){
		if(empty($puid))
			return ['success'=>false,'message'=>'user id not setted!'];
		
		if(!isset($params['value']))
			return ['success'=>false,'message'=>'edm quota value not setted!'];
 
		try{
			$quota = (int)$params['value'];
			$edm_quota = UserEdmQuota::findOne($puid);
			if(empty($edm_quota)){
				$edm_quota = new UserEdmQuota();
				$edm_quota->uid = $puid;
				$edm_quota->remaining_quota = $quota;
				$edm_quota->create_time = TimeUtil::getNow();
				$edm_quota->update_time = $edm_quota->create_time;
				$addi_info['payment_record'][] = "于".$edm_quota->create_time."首次购买".$quota."配额"; 
				$edm_quota->addi_info = json_encode($addi_info);
			}else{
				$remaining_quota = (int)$edm_quota->remaining_quota;
				$edm_quota->remaining_quota = $remaining_quota + $quota;
				$edm_quota->update_time = TimeUtil::getNow();
				$addi_info = json_decode($edm_quota->addi_info,true);
				if(empty($addi_info))
					$addi_info=[]; 
				$addi_info['payment_record'][] = "于".$edm_quota->update_time."购买".$quota."配额";
				$edm_quota->addi_info = json_encode($addi_info);
			}
			
			if(!$edm_quota->save()){
				return ['success'=>false,'message'=>print_r($edm_quota->getErrors())];
			}else 
				return ['success'=>true,'message'=>''];
			
		}catch (\Exception $e) {
			return ['success'=>false,'message'=>$e->getMessage()];
		}
	}
	
	/**
	 * 获取指定店铺的发送email地址
	 * @author	lzhl		2016/08/12		初始化
	**/
	public static function getPlatformStoreMatchMailAddress($puid,$platform,$store,$addi_key=''){
		if(strtolower($platform)=='amazon'){
			//amazon的selleruserid记录的是Merchant Id (addi_key)
			$query = PlatformStoreEmailAddress::find()->where(['puid'=>$puid,'platform'=>$platform,'addi_key'=>$store,'is_active'=>1]);
		}else{
			$query = PlatformStoreEmailAddress::find()->where(['puid'=>$puid,'platform'=>$platform,'store'=>$store,'is_active'=>1]);
			if(!empty($addi_key))
				$query->andWhere(['addi_key'=>$addi_key]);
		}
		//$commandQuery = clone $query;
		//echo $commandQuery->createCommand()->getRawSql();
		$email_address = $query->asArray()->One();
		return $email_address;
		
	}
	
	
	/**
	 * 按平台列出用户所有active店铺的emial设置信息
	 * @param string $platform
	 * @return array
	 * @author	lzhl		2016/08/12		初始化
	 */
	public static function listPlatformStoreMailAddressSetting($platform){
		if(empty($platform))
			return  [];
		
		$puid = \Yii::$app->user->identity->getParentUid();
		
		$platform = strtolower($platform);
		switch ($platform){
			case 'cdiscount':
				$activeStores = CdiscountAccountsApiHelper::listActiveAccounts($puid);
				break;
			case 'amazon':
				$activeStores = AmazonAccountsApiHelper::listActiveAccounts($puid);
				break;
			default:
				$activeStores = [];
				break;
		}
		//已经设置的店铺邮件信息
		$platformStoreMails = PlatformStoreEmailAddress::find()->where(['platform'=>$platform,'puid'=>$puid])->asArray()->all();
		$existing_store_mail = [];
		foreach ($platformStoreMails as $mail_info){
			$existing_store_mail[$mail_info['store']] = $mail_info;
		}
		
		//只显示有权限的账号，lrq20170828
		$account_data = \eagle\modules\platform\apihelpers\PlatformAccountApi::getPlatformAuthorizeAccounts(strtolower($platform));
		$selleruserids = array();
		foreach($account_data as $key => $val){
			$selleruserids[] = $key;
		}
		
		$listStoreInfo = [];
		foreach ($activeStores as $store){
			//每个平台的店铺唯一key可能各有不同，因此需要分别处理
			if($platform=='amazon'){
				if(!in_array($store['merchant_id'], $selleruserids)){
					continue;
				}
				$listStoreInfo[$store['merchant_id']] = $store;
				if(isset($existing_store_mail[$store['store_name']])){
					$listStoreInfo[$store['merchant_id']]['setted_mail_address'] = true;
					$listStoreInfo[$store['merchant_id']]['mail_address'] = $existing_store_mail[$store['store_name']]['mail_address'];
					$listStoreInfo[$store['merchant_id']]['mail_sender_name'] = $existing_store_mail[$store['store_name']]['name'];
					$listStoreInfo[$store['merchant_id']]['mail_is_active'] = $existing_store_mail[$store['store_name']]['is_active'];
				}else
					$listStoreInfo[$store['merchant_id']]['setted_mail_address'] = false;
			}
			if($platform=='cdiscount'){
				if(!in_array($store['username'], $selleruserids)){
					continue;
				}
				$listStoreInfo[$store['username']] = $store;
				if(isset($existing_store_mail[$store['username']])){
					$listStoreInfo[$store['username']]['setted_mail_address'] = true;
					$listStoreInfo[$store['username']]['mail_address'] = $existing_store_mail[$store['username']]['mail_address'];
					$listStoreInfo[$store['username']]['mail_sender_name'] = $existing_store_mail[$store['username']]['name'];
					$listStoreInfo[$store['username']]['mail_is_active'] = $existing_store_mail[$store['username']]['is_active'];
				}else 
					$listStoreInfo[$store['username']]['setted_mail_address'] = false;
			}
			//@todo more platform
		}//end foreach $activeStores
		
		return $listStoreInfo;
	}
	
	public static function savePlatformStoreMailInfo($data){
		$rtn=['success'=>true,'message'=>''];
		$err_msg = '';
		$puid = \Yii::$app->user->identity->getParentUid();
		$journal_id = SysLogHelper::InvokeJrn_Create("Message",__CLASS__, __FUNCTION__ , array(json_encode($data)));
		
		$transaction = \Yii::$app->get('db')->beginTransaction();
		$platform = $data['platform'];
		$active_record_id = [];//生效的店铺，其同平台余的最后设为unActive
		
		foreach ($data['store_name'] as $index=>$store_name){
			$query = PlatformStoreEmailAddress::find()->where(['platform'=>$platform,'store'=>$store_name]);
			$addi_key='';
			if(!empty($data['addi_key'][$index])){
				$addi_key = $data['addi_key'][$index];
				$query->andWhere(['addi_key'=>$addi_key]);
			}
			
			$record = $query->one();
			if(empty($record)){
				if(empty($data['mail_address'][$index]))
					continue;
				$record = new PlatformStoreEmailAddress();
				$record->puid = $puid;
				$record->platform = $platform;
				$record->store = $store_name;
				$record->addi_key = $addi_key;
				$record->mail_address = empty($data['mail_address'][$index])?'':$data['mail_address'][$index];
				$record->name = empty($data['name'][$index])?'':$data['name'][$index];
				$record->create_time = TimeUtil::getNow();
				$record->is_active = 1;
				if(!$record->save()){
					$rtn['success'] = false;
					$rtn['message'] .= '<br>'.$store_name.'信息保存失败:'.print_r($record->getErrors());
					$err_msg .= print_r($record->getErrors());
				}
			}else{
				if(empty($data['mail_address'][$index])){
					$record->update_time = TimeUtil::getNow();
					$record->is_active = 0;
				}else{
					$record->mail_address = empty($data['mail_address'][$index])?'':$data['mail_address'][$index];;
					$record->name = empty($data['name'][$index])?'':$data['name'][$index];
					$record->update_time = TimeUtil::getNow();
					$record->is_active = 1;
				}
				if(!$record->save()){
					$rtn['success'] = false;
					$rtn['message'] .= '<br>'.$store_name.'信息保存失败:'.print_r($record->getErrors());
					$err_msg .= print_r($record->getErrors());
				}
			}
			
			if ($record->is_active !== 0)
				$active_record_id[] = $record->id;
			unset($record);
		}//end foreach
		
		if(!$rtn['success']){
			$transaction->rollBack();
			SysLogHelper::InvokeJrn_UpdateResult($journal_id, $rtn);
			return $rtn;
		}
		
		PlatformStoreEmailAddress::updateAll(
			['is_active'=>0],
			'id not in ('. implode(",", $active_record_id) .') and puid='.$puid
		);
		$transaction->commit();
		SysLogHelper::InvokeJrn_UpdateResult($journal_id, $rtn);
		return $rtn;
	}
}
