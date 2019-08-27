<?php
namespace eagle\modules\order\helpers;
use eagle\modules\util\helpers\TranslateHelper;
use eagle\modules\order\models\OdOrder;
use eagle\modules\message\apihelpers\MessageApiHelper;
use eagle\modules\util\helpers\ConfigHelper;
use yii\helpers\Html;
use eagle\modules\order\apihelpers\OrderApiHelper;
use eagle\modules\util\helpers\StandardConst;
use eagle;
use eagle\models\OdOrderShipped;
use eagle\modules\inventory\apihelpers\InventoryApiHelper;
/**
 * 主要封装oms 前台使用的logic 方便 调用 ， 不放在orderhelper 是因为 太多方法放在orderhelper
 * @author lkh
 */
class OrderFrontHelper{
	/**
	 +---------------------------------------------------------------------------------------------
	 * 封装显示订单产品展示页面的代码 
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param     string			订单产品展示页面的link
	 +---------------------------------------------------------------------------------------------
	 * @return						string html
	 *
	 * @invoking					OrderFrontHelper::displayPlatformProductUrl('aliexpress',$item);
	 *
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2016/10/24				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function displayPlatformProductUrl($platform,$OrderItem){
		switch (true){
			case in_array($platform,['aliexpress']) :
				return "https://www.aliexpress.com/item/xxx/".$OrderItem->order_source_itemid.".html";
			default:
				return $OrderItem->product_url;
		}
	}//end of function 
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 封装显示订单的已付款流程
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param     $counter			array 统计数据
	 +---------------------------------------------------------------------------------------------
	 * @return						string html
	 *
	 * @invoking					OrderFrontHelper::dispalyOrderPaidProcessHtml();
	 *
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2016/4/26				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function displayOrderPaidSubProcessHtml($counter,$platform='aliexpress'){
		$sub_selected = [OdOrder::EXCEP_SKUNOTMATCH=>'',OdOrder::EXCEP_NODEFAULTWAREHOUSE=>'',OdOrder::EXCEP_NOSTOCK=>'',OdOrder::EXCEP_HASNOSHIPMETHOD=>'',OdOrder::EXCEP_WAITMERGE=>''];
		$htmlStr = '';
		//改成通用
		switch ($platform){
			case 'cdiscount':
				$controller = 'cdiscount-order';
				$action='list';
				break;
			default:
				$controller = \Yii::$app->controller->id;
				$action=\Yii::$app->controller->action->id;
				break;
		}
		
		$disabled = [OdOrder::EXCEP_SKUNOTMATCH=>'a_disable' ,OdOrder::EXCEP_NODEFAULTWAREHOUSE=>'a_disable' ,OdOrder::EXCEP_NOSTOCK=>'a_disable',OdOrder::EXCEP_HASNOSHIPMETHOD=>'a_disable',OdOrder::EXCEP_WAITMERGE=>'a_disable'];
		$hrefs = [OdOrder::EXCEP_SKUNOTMATCH=>'',OdOrder::EXCEP_NODEFAULTWAREHOUSE=>'',OdOrder::EXCEP_NOSTOCK=>'',OdOrder::EXCEP_HASNOSHIPMETHOD=>'',OdOrder::EXCEP_WAITMERGE=>''];
		foreach ($disabled as $t=>&$v){
			if(!empty($counter[$t])){
				$v='';
				$hrefs[$t] = 'href="/order/'.$controller.'/'.$action.'?order_status=200&pay_order_type=exception&exception_status='.$t.'"';
			}
		}
		
		if (@$_REQUEST['pay_order_type']=='exception' || in_array(@$_REQUEST['exception_status'], [OdOrder::EXCEP_SKUNOTMATCH,OdOrder::EXCEP_NODEFAULTWAREHOUSE,OdOrder::EXCEP_NOSTOCK,OdOrder::EXCEP_HASNOSHIPMETHOD,OdOrder::EXCEP_WAITMERGE])):
			if (isset($_REQUEST['exception_status'])){
				$sub_selected[$_REQUEST['exception_status']] = 'btn-important';
			}
			$htmlStr .='
			<div style="height:10px"></div>
			<div id="tabs_exception_options">
				<ul class="nav nav-pills">
				  <li role="presentation" ><a /*qtipkey="oms_order_exception_pending_merge"*/ class ="iv-btn '.$sub_selected[OdOrder::EXCEP_WAITMERGE].' '.$disabled[OdOrder::EXCEP_WAITMERGE].'" '.$hrefs[OdOrder::EXCEP_WAITMERGE].'>'.TranslateHelper::t('待合并').'('.$counter[OdOrder::EXCEP_WAITMERGE].')</a></li>
				  <li role="presentation" ><a /*qtipkey="oms_order_exception_sku_not_exist"*/ class ="iv-btn '.$sub_selected[OdOrder::EXCEP_SKUNOTMATCH].' '.$disabled[OdOrder::EXCEP_SKUNOTMATCH].'" '.$hrefs[OdOrder::EXCEP_SKUNOTMATCH].'>'.TranslateHelper::t('SKU不存在').'('.$counter[OdOrder::EXCEP_SKUNOTMATCH].')</a></li>
				  <li role="presentation" ><a /*qtipkey="oms_order_exception_no_warehouse"*/ class ="iv-btn '.$sub_selected[OdOrder::EXCEP_NODEFAULTWAREHOUSE].' '.$disabled[OdOrder::EXCEP_NODEFAULTWAREHOUSE].'" '.$hrefs[OdOrder::EXCEP_NODEFAULTWAREHOUSE].'>'.TranslateHelper::t('未分配仓库').'('.$counter[OdOrder::EXCEP_NODEFAULTWAREHOUSE].')</a></li>
				  <li role="presentation" ><a /*qtipkey="oms_order_exception_out_of_stock"*/ class ="iv-btn '.$sub_selected[OdOrder::EXCEP_NOSTOCK].' '.$disabled[OdOrder::EXCEP_NOSTOCK].'" '.$hrefs[OdOrder::EXCEP_NOSTOCK].'>'.TranslateHelper::t('库存不足').'('.$counter[OdOrder::EXCEP_NOSTOCK].')</a></li>
				  <li role="presentation" ><a /*qtipkey="oms_order_exception_no_shipment"*/ class ="iv-btn '.$sub_selected[OdOrder::EXCEP_HASNOSHIPMETHOD].' '.$disabled[OdOrder::EXCEP_HASNOSHIPMETHOD].'" '.$hrefs[OdOrder::EXCEP_HASNOSHIPMETHOD].'>'.TranslateHelper::t('未分配运输服务').'('.$counter[OdOrder::EXCEP_HASNOSHIPMETHOD].')</a></li>
	
				</ul>
			</div>';
		endif;
		return $htmlStr;
	}//end of displayOrderPaidSubProcessHtml;
	/**
	 +---------------------------------------------------------------------------------------------
	 * 封装显示订单的已付款流程
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param     $counter			array 		统计数据
	 * @param     $platform			string 		平台
	 * @param     $unShouw			array		不显示的项目
	 +---------------------------------------------------------------------------------------------
	 * @return						string html
	 *
	 * @invoking					OrderFrontHelper::dispalyOrderPaidProcessHtml();
	 *
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2016/4/26				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function displayOrderPaidProcessHtml($counter,$platform='aliexpress',$unShouw=[]){
		$selected = ['all'=>'',OdOrder::PAY_REORDER=>'' , OdOrder::PAY_EXCEPTION=>'',OdOrder::PAY_CAN_SHIP=>'',OdOrder::EXCEP_WAITMERGE=>'',OdOrder::PAY_MERGED=>'','split'=>''];
		$unShouw[] = OdOrder::PAY_REORDER;
		$unShouw[] = OdOrder::PAY_CAN_SHIP;

		if (isset($_REQUEST['pay_order_type']) && isset($selected[$_REQUEST['pay_order_type']])){
			$selected[$_REQUEST['pay_order_type']] =  'btn-important';
		}
		
		if (!empty($_REQUEST['exception_status']) && isset($selected[$_REQUEST['exception_status']])){
			$selected[$_REQUEST['exception_status']] =  'btn-important';
		}
		
		if (!empty($_REQUEST['is_merge']) && isset($selected[OdOrder::PAY_MERGED])){
			$selected[OdOrder::PAY_MERGED] =  'btn-important';
		}
		
		if(!empty($_REQUEST['order_relation_fs'])){
			$selected['split'] =  'btn-important';
		}
		
		$htmlStr = '';
		//改成通用
		switch ($platform){
			case 'cdiscount':
				$controller = 'cdiscount-order';
				$action='list';
				break;
			default:
				$controller = \Yii::$app->controller->id;
				$action=\Yii::$app->controller->action->id;
				break;
		}
		$disabled = [OdOrder::STATUS_PAY=>'a_disable',OdOrder::PAY_REORDER=>'a_disable',OdOrder::PAY_EXCEPTION=>'a_disable',OdOrder::PAY_CAN_SHIP=>'a_disable',OdOrder::EXCEP_WAITMERGE=>'a_disable',OdOrder::PAY_MERGED=>'a_disable','split'=>'a_disable'];
		$hrefs = ['all'=>'href="/order/'.$controller.'/'.$action.'?order_status='.OdOrder::STATUS_PAY.'&pay_order_type=all"',OdOrder::PAY_REORDER=>'',OdOrder::PAY_EXCEPTION=>'',OdOrder::PAY_CAN_SHIP=>'',OdOrder::EXCEP_WAITMERGE=>'',OdOrder::PAY_MERGED=>'','split'=>''];
		foreach ($disabled as $t=>&$v){
			if(!empty($counter[$t])){
				$v='';
				$isShowHref = 'href';
			}else{
				$counter[$t] = 0;
				$isShowHref = 'data-href';
			}
			
			if (in_array($t,[OdOrder::EXCEP_WAITMERGE])){
				$hrefs[$t] = $isShowHref.'="/order/'.$controller.'/'.$action.'?order_status='.OdOrder::STATUS_PAY.'&exception_status='.$t.'"';
			}else if($t == OdOrder::PAY_MERGED){
				$hrefs[$t] = $isShowHref.'="/order/'.$controller.'/'.$action.'?order_status='.OdOrder::STATUS_PAY.'&is_merge=1"';
			}else if($t == 'split'){
				$hrefs[$t] = $isShowHref.'="/order/'.$controller.'/'.$action.'?order_status='.OdOrder::STATUS_PAY.'&order_relation_fs=1"';
			}else{
				$hrefs[$t] = $isShowHref.'="/order/'.$controller.'/'.$action.'?order_status='.OdOrder::STATUS_PAY.'&pay_order_type='.$t.'"';
			}
		}
		
		//lgw 作为测试用，指定user可以看到 20170508
// 		if(\Yii::$app->subdb->getCurrentPuid()=='1'){
			$test_temp_txt='<li role="presentation"><a style="display: inline-block;border-radius: 0;padding: 5px 15px" class ="iv-btn '.$selected['split'].' '.$disabled['split'].'" '.$hrefs['split'].'>'.TranslateHelper::t('已拆分').'<span data-top-menu-badge="split">('.$counter['split'].')</span></a></li>';
// 		}
// 		else{
// 			$test_temp_txt='';
// 		}

		if (@$_REQUEST['order_status'] == 200 || in_array(@$_REQUEST['exception_status'], ['0','210','203','222','202','223','299']) ):	
			$htmlStr .='
			<div>
			<ul class="nav nav-pills"><!-- main-tab -->
			  <li role="presentation" ><a style="display: inline-block;border-radius: 0;padding: 5px 15px" class ="iv-btn '.$selected['all'].'" '.$hrefs['all'].'>'.TranslateHelper::t('全部').'<span data-top-menu-badge="'.OdOrder::STATUS_PAY.'">('.((!empty($counter[OdOrder::STATUS_PAY])?$counter[OdOrder::STATUS_PAY]:0)).')</span></a></li>
			  <li role="presentation" '.( in_array((string)OdOrder::PAY_REORDER, $unShouw)?'style="display:none"':'' ).'><a style="display: inline-block;border-radius: 0;padding: 5px 15px" class ="iv-btn '.$selected[OdOrder::PAY_REORDER].' '.$disabled[OdOrder::PAY_REORDER].'" '.$hrefs[OdOrder::PAY_REORDER].'>'.TranslateHelper::t('重新发货').'<span data-top-menu-badge="'.OdOrder::PAY_REORDER.'">('.$counter[OdOrder::PAY_REORDER].')</span></a><span data-qtipkey="oms_order_reorder" class ="click-to-tip"><img style="cursor:pointer;padding-top:8px;" width="16" src="/images/questionMark.png"></span></li>
			  <li role="presentation" '.( in_array((string)OdOrder::EXCEP_WAITMERGE, $unShouw)?'style="display:none"':'' ).'><a style="display: inline-block;border-radius: 0;padding: 5px 15px;" class ="iv-btn '.$selected[OdOrder::EXCEP_WAITMERGE].' '.$disabled[OdOrder::EXCEP_WAITMERGE].'" '.$hrefs[OdOrder::EXCEP_WAITMERGE].'>'.TranslateHelper::t('待合并').'<span data-top-menu-badge="'.OdOrder::EXCEP_WAITMERGE.'">(<font style="color: red;">'.$counter[OdOrder::EXCEP_WAITMERGE].'</font>)</span></a><span data-qtipkey="oms_order_exception_pending_merge" class ="click-to-tip hidden"><img style="cursor:pointer;padding-top:8px;" width="16" src="/images/questionMark.png"></span></li>
			  <li role="presentation" '.( in_array((string)OdOrder::PAY_MERGED, $unShouw)?'style="display:none"':'' ).'><a style="display: inline-block;border-radius: 0;padding: 5px 15px" class ="iv-btn '.$selected[OdOrder::PAY_MERGED].' '.$disabled[OdOrder::PAY_MERGED].'" '.$hrefs[OdOrder::PAY_MERGED].'>'.TranslateHelper::t('已合并').'<span data-top-menu-badge="'.OdOrder::PAY_MERGED.'">('.$counter[OdOrder::PAY_MERGED].')</span></a></li>
'.$test_temp_txt.'
			  <li role="presentation" '.( in_array((string)OdOrder::PAY_CAN_SHIP, $unShouw)?'style="display:none"':'' ).'><a style="display: inline-block;border-radius: 0;padding: 5px 15px" class ="iv-btn '.$selected[OdOrder::PAY_CAN_SHIP].' '.$disabled[OdOrder::PAY_CAN_SHIP].'" '.$hrefs[OdOrder::PAY_CAN_SHIP].'>'.TranslateHelper::t('可发货').'<span data-top-menu-badge="'.OdOrder::PAY_CAN_SHIP.'">('.$counter[OdOrder::PAY_CAN_SHIP].')</span></a><span data-qtipkey="oms_order_can_ship" class ="click-to-tip"><img style="cursor:pointer;padding-top:8px;" width="16" src="/images/questionMark.png"></span></li>
			</ul>
			</div>';
			//$htmlStr .=self::displayOrderPaidSubProcessHtml($counter,$platform);
		endif;
		
		return $htmlStr;
	}//end of displayOrderPaidProcessHtml
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 封装 订单最后 一条站内信 信息
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param     $counter			array 统计数据
	 +---------------------------------------------------------------------------------------------
	 * @return						string html
	 *
	 * @invoking					OrderFrontHelper::dispalyOrderPaidProcessHtml();
	 *
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2016/4/26				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function getLastMessage($platform_order_id){
		//客服模块最后一条对话 start
		$showLastMessage=0;
		if(empty($showLastMessage)){
			$lastMessageInfo = MessageApiHelper::getOrderLastMessage($platform_order_id);
			if(empty($lastMessageInfo)){
				$lastMessage = '';
				$showLastMessage = 1;
			}else{
				$lastMessage='N/A';
				if(isset($lastMessageInfo['send_or_receiv']))
				if((int)$lastMessageInfo['send_or_receiv']==1){
					$talk='您';
					$talkTo ='买家';
				}else{
					$talk='买家';
					$talkTo ='您';
				}
				if(!empty($lastMessageInfo['last_time']))
					$lastTime=$lastMessageInfo['last_time'];
				else
					$lastTime='--年--月--日';
				if(!empty($lastMessageInfo['content']))
					$lastMessage=$lastMessageInfo['content'];
				if(strlen($lastMessage)>200){
					$lastMessage = substr($lastMessage,0,200).'...';
				}
				$lastMessage = $talk.'于'.$lastTime.'对'.$talkTo.'说：<br>'.$lastMessage;
		
				if(!empty($envelope_class) && $envelope_class=='egicon-envelope-remove')
					$lastMessage = '<span style="color:red">'.$lastMessage.'</span>';
				else
					$lastMessage = '<span style="">'.$lastMessage.'</span>';
			}
		}
		//客服模块最后一条对话 end
		return $lastMessage;
	}//end of getLastMessage
	
	static public function generateTagIconHtmlByOrderId($orderid){
		return OrderTagHelper::generateTagIconHtmlByOrderId($orderid);
	}//end of function generateTagIconHtmlByOrderId
	
	static public function getItemDeclarationInfoHtml($order , $items , $existProductResult){
		$isExistProduct = true;
		$html = '';
		foreach($items as $item){
			if (empty($item->sku))
				$key = $item->product_name;
			else
				$key = $item->sku;
			
			if (isset($existProductResult[strtolower($key)]) && $existProductResult[strtolower($key)] ==false){
				$isExistProduct = false;
				break;
			}
		}
		if ( ($isExistProduct == false)  && ($order->order_status ==OdOrder::STATUS_PAY) ){
			if ($order->order_source =='ebay'){
				$jsFuntionName = 'ebayOrder.doactionone';
			}else{
				$jsFuntionName = 'doactionone';
			}
			
			$html=  '<div class="nopadingAndnomagin alert alert-danger">'.TranslateHelper::t('报关信息不全')."<br/><a onclick=\"".$jsFuntionName."('changeItemDeclarationInfo','".$order->order_id."')\">".TranslateHelper::t("修改")."</a></div>";
		}
		return $html;
	}
	
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 封装 ebay status html
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param     $counter			array 统计数据
	 +---------------------------------------------------------------------------------------------
	 * @return						string html
	 *
	 * @invoking					OrderFrontHelper::dispalyOrderPaidProcessHtml();
	 *
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2016/4/26				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function getEbayStatus($order , $orderCheckOutList){
		//<!-- check图标 -->
		if (!empty($orderCheckOutList[$order->order_source_order_id])){
			$check = $orderCheckOutList[$order->order_source_order_id];
		}else{
			$check = [];
		}
		?>
		<?php if (!empty($check)&&$check['checkoutstatus']=='Complete'):?>
		<div title="已结款" class="sprite_check_1"></div>
		<?php else:?>
		<div title="未结款" class="sprite_check_0"></div>
		<?php endif;?>
		<!-- 付款状态图标 -->
		<?php if ($order->pay_status==0):?>
		<div title="未付款" class="sprite_pay_0"></div>
		<?php elseif ($order->pay_status==1):?>
		<div title="已付款" class="sprite_pay_1"></div>
		<?php elseif ($order->pay_status==2):?>
		<div title="支付中" class="sprite_pay_2"></div>
		<?php elseif ($order->pay_status==3):?>
		<div title="已退款" class="sprite_pay_3"></div>
		<?php endif;?>
		<!-- 发货图标 -->
		<?php if ($order->shipping_status==1):?>
		<div title="已发货" class="sprite_shipped_1"></div>
		<?php else:?>
		<div title="未发货" class="sprite_shipped_0"></div>
		<?php endif;
	}//end of function getEbayStatus
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 封装 oms 虚拟发货选择栏html代码
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param     $counter			array 统计数据
	 +---------------------------------------------------------------------------------------------
	 * @return						string html
	 *
	 * @invoking					OrderFrontHelper::dispalyOrderPaidProcessHtml();
	 *
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2016/4/26				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function displayOrderSyncShipStatusToolbar($SyncShipstatus='' , $platform=''){
		$items = OrderApiHelper::$OrderSyncShipStatus;
	?>
		<div id="div_order_sync_ship_status_toolbar" style="margin:20px 0px 0px 0px ;display:inline-block;width:100%" data-platform ="<?= $platform?>" >
			
			<strong style="font-weight: bold;font-size:12px;">虚拟发货状态：</strong>
			<?php
				echo html::hiddenInput('order_sync_ship_status' ,$SyncShipstatus );
				echo "<a style='margin-right: 20px;' class='".($SyncShipstatus == '' ? 'text-rever-important' : '')."' data-value='' >".'全部'."</a>";
				
				foreach($items as $key=>$label){
					echo "<a style='margin-right: 20px;' class='".($SyncShipstatus == $key ? 'text-rever-important' : '')."' data-value='$key' >".$label." <span data-key='$key'></span></a>";
				}
				
			?>
			
			
		</div>
	<?php 
	}//end of function displayOrderSyncShipStatusToolbar
	
	
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 封装 运输服务 的 html
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param     $counter			array 统计数据
	 +---------------------------------------------------------------------------------------------
	 * @return						string html
	 *
	 * @invoking					OrderFrontHelper::dispalyOrderPaidProcessHtml();
	 *
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2016/4/26				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function getShippingServicesHtml($platForm, $warehouseList, $shipmethodList , $default_shipping_method_code='' ,$order=null){
		?>
	<!-- 
	<div class="alert alert-warning" role="alert">
	  <a href="http://www.littleboss.com/faq_30.html" target="_blank" class="alert-link">如何设置仓库和运输服务？</a>
	</div>
	-->
	<style>
	.ui-autocomplete{
	z-index:9999;
	z-index: 9999 !important;
	overflow-y: scroll;
	max-height: 320px;
	}
	</style>
	<div id="div_warehouse" class="row"><?= Html::label(TranslateHelper::t('仓库：'),null,['style'=>'width:13%;text-align: left;'])?><?=Html::dropDownList('change_warehouse',@$default_shipping_method_code,$warehouseList,['onchange'=>'OrderCommon.load_shipping_method(this)','class'=>'iv-select'])?><a href="/configuration/warehouseconfig/self-warehouse-list" target="_blank"><?=TranslateHelper::t('设置自营仓库')?></a></div>
	
	<?php if(!empty($order) && $order->order_status==300){ ?>
	<div id="div_ShippingType" class="row">
		<label class="col-md-1 control-label p0" style="width:13%;">获取单号方式:</label>
			<input type="radio" id="agentRadio" name="agentRadio" value="1" <?php echo $default_shipping_method_code=='manual_tracking_no'?'':'checked'; ?> onclick="OrderCommon.agentRadioSelect(1)" style="cursor: pointer;"><label for="agentRadio" style="cursor: pointer;">自动获取</label>&nbsp;&nbsp;&nbsp;&nbsp;
			<input type="radio" id="agentRadio1" name="agentRadio" value="0" <?php echo $default_shipping_method_code=='manual_tracking_no'?'checked':''; ?> onclick="OrderCommon.agentRadioSelect(0)" style="cursor: pointer;"><label for="agentRadio1" style="cursor: pointer;">手动获取</label>
	</div>
	<?php } ?>
	<div id="change_ShippingType1" class="<?php echo $default_shipping_method_code=='manual_tracking_no'?'hidden':''; ?>">
		<div id="div_ShippingServices" class="row">
		<?= Html::label(TranslateHelper::t('运输服务：'),null,['style'=>'line-height:40px;width:12%;'])?>
		<?=html::dropDownList('change_shipping_method_code',@$default_shipping_method_code,$shipmethodList,['class'=>'iv-select' , 'style'=>'max-width: 610px;','onclick'=>'OrderCommon.changeShippingCodeSetEmptyTracking(this)'])?>
		<a href="/configuration/carrierconfig/index" target="_blank"><?=TranslateHelper::t('设置运输服务')?></a>
		</div>
		
		<?php if(!empty($order) && $order->order_status==300){ ?>
		<div id="div_trackingNumberDiv" class="row">
			<label class="col-md-2 control-label p0" style="line-height: 40px;width:13%;">跟踪单号:</label>
			<input type="text" class="form-control" id="trackingNumber" style="width: 250px;display: inline;" placeholder="请输入" data-no='<?=$order->tracking_number; ?>' data-service='<?=@$default_shipping_method_code ?>' value="<?php echo $order->tracking_number; ?>">
			<button type="button" class="btn btn-primary <?php echo (!empty($order->tracking_number))?'hidden':''; ?>" onclick="OrderCommon.reApplyTrackNum(<?php echo preg_replace('/^0+/','',$order->order_id); ?>);" id="reApplayTrackBtn">获取跟踪号</button>
		</div>
		<div id="div_trackingNumberDiv_msg_ed" class="row displaycss"></div>
		<?php } ?>
	</div>
	
	<?php if(!empty($order) && $order->order_status==300){ ?>
	<div id="change_ShippingType2" class="<?php echo $default_shipping_method_code=='manual_tracking_no'?'':'hidden'; ?>">
		<?php
			//获取平台运输服务商
			$rt=\eagle\modules\carrier\helpers\CarrierOpenHelper::getShippingCodeByPlatform($platForm);//print_r($rt);die;
			$ShippingCodeByPlatformArr=OdOrderShipped::find()->select(['shipping_method_code','tracking_link'])->where(['order_id'=>$order->order_id])->orderBy(["created" => SORT_DESC])->asArray()->one();
			if(empty($ShippingCodeByPlatformArr))
				$ShippingCodeByPlatform='';
			else 
				$ShippingCodeByPlatform=$ShippingCodeByPlatformArr['shipping_method_code'];
			?>
				<div id="div_ShippingServices2" class="row">
					<label class="col-md-2 control-label p0" style="line-height: 40px;width:13%;">运输服务：</label>
			<?php 
			if($rt['type']=='dropdownlist'){
				foreach ($rt['shippingServices'] as $keys=>$shippingServicesone){
					$allShipcodeMapping[$keys]=$shippingServicesone['service_val'];
				}
				//amazon平台支持手写
				if($ShippingCodeByPlatform != ''){
					if(!isset($allShipcodeMapping[$ShippingCodeByPlatform])){
						$allShipcodeMapping[$ShippingCodeByPlatform] = $ShippingCodeByPlatform;
					}
				}
				?>
					<?=html::dropDownList('change_shipping_method_code2',$ShippingCodeByPlatform,$allShipcodeMapping,['data-platform'=>$order->order_source, 'onchange'=>'OrderCommon.changeshippingmethodcode(this,\''.$platForm.'\')','class'=>'iv-select' , 'style'=>'max-width: 610px;margin-top: 9px;'])?>
				<?php
			}
			else if($rt['type']=='text'){
				?>
					<input type="text" class="form-control" name="change_shipping_method_code2" id="change_shipping_method_code2" placeholder="" style="width: 610px;display: inline;" value="<?php echo $ShippingCodeByPlatform; ?>">
				<?php 
			}
			?></div><?php 
			$ShippingLinkByPlatform=empty($ShippingCodeByPlatformArr['tracking_link'])?'http://www.17track.net':$ShippingCodeByPlatformArr['tracking_link'];
			
			if($rt['web_url_tyep']===0){
				?>
				<div id="div_web2" class="row">
					<label class="col-md-2 control-label p0" style="line-height: 40px;width:13%;">查询网址：</label>
					<input type="text" class="form-control" style="width: 300px;display: inline;" id="change_web2" value="<?php echo $ShippingLinkByPlatform;?>">
				</div>
				<?php 
			}
			else if($rt['web_url_tyep']===2){
				$shippingServicesArr=empty($ShippingCodeByPlatform)?reset($rt['shippingServices']):$rt['shippingServices'][$ShippingCodeByPlatform];
				if($shippingServicesArr['is_web_url']==1){
					?>
					<div id="div_web2" class="row">
						<label class="col-md-2 control-label p0" style="line-height: 40px;width:13%;">查询网址：</label>
						<input type="text" class="form-control" style="width: 300px;display: inline;" id="change_web2" value="<?php echo $ShippingLinkByPlatform;?>">
					</div>
					<?php 
				}
			}
			
			if($order->order_source=='cdiscount'){
				?>
					<div id="div_othermethod2" class="row hidden">
						<label class="col-md-2 control-label p0" style="line-height: 40px;width:13%;">物流名称 ：</label>
						<input type="text" class="form-control" style="width: 300px;display: inline;" id="change_othermethod2" value="" placeholder="最好填法文或英文运输服务名">
					</div>
				<?php 
			}
		?>		
		<div id="div_trackingNumberDiv2" class="row">
			<label class="col-md-2 control-label p0" style="line-height: 40px;width:13%;">跟踪单号:</label>
			<input type="text" class="form-control" id="trackingNumber2" style="width: 250px;display: inline;" placeholder="请输入" value="<?php echo $order->tracking_number; ?>">
		</div>
	
	</div>
	<script>
	$('[data-platform=amazon]').combobox({removeIfInvalid:false,allNull:true});
	</script>
	<?php } ?>
		<?php 
	}//end of function getEditOrderAmountInfoHTML
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 封装 编辑订单 金额 显示的HTML 
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param     $order			order model 
	 +---------------------------------------------------------------------------------------------
	 * @return						string html
	 *
	 * @invoking					OrderFrontHelper::displayEditOrderAmountInfoHTML();
	 *
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2016/8/23				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function displayEditOrderAmountInfoHTML($order){
		switch (true){
			case in_array($order->order_source,['cdiscount']):
				$currencySing = $order->currency;
				$currencyInfo = StandardConst::getCurrencyInfoByCode($currencySing);
				if(!empty($currencyInfo) && !empty($currencyInfo['html'])){
					$currencySing = $currencyInfo['html'];
				}
				?>
				<div style="float:left;width:100%;"><span style="float:left;width:40px;">产品+</span><span style="float:left;"><?=$order->subtotal?>&nbsp;<?=$currencySing?></span></div>
				<div style="float:left;width:100%;"><span style="float:left;width:40px;">佣金-</span><span style="float:left;"><?=!empty($order->commission_total)?$order->commission_total:$order->discount_amount ?>&nbsp;<?=$currencySing?></span></div>
				<div style="float:left;width:100%;"><span style="float:left;width:40px;">运费+</span><span style="float:left;"><?=$order->shipping_cost?>&nbsp;<?=$currencySing?></span></div>
				<div style="float:left;width:100%;font-weight:bold;"><span style="float:left;;width:40px;">合计=</span><span style="float:left;"><?=$order->grand_total?>&nbsp;<?=$currencySing?></span></div>
				<?php 
				break;
				case in_array($order->order_source,['priceminister']):
					$currencySing = $order->currency;
					$currencyInfo = StandardConst::getCurrencyInfoByCode($currencySing);
					if(!empty($currencyInfo) && !empty($currencyInfo['html'])){
						$currencySing = $currencyInfo['html'];
					}
					?>
				<div style="float:left;width:100%;"><span style="float:left;width:40px;">产品+</span><span style="float:left;"><?=$order->subtotal?>&nbsp;<?=$currencySing?></span></div>
				<div style="float:left;width:100%;"><span style="float:left;width:40px;">运费+</span><span style="float:left;"><?=$order->shipping_cost?>&nbsp;<?=$currencySing?></span></div>
				<div style="float:left;width:100%;font-weight:bold;"><span style="float:left;;width:40px;">合计=</span><span style="float:left;"><?=$order->grand_total?>&nbsp;<?=$currencySing?></span></div>
				<?php 
				break;
			case in_array($order->order_source,['ebay']):
				if (!empty($order->addi_info)){
					$addi_info = json_decode($order->addi_info,true);
				}
				$currencySing = $order->currency;
				?>
				<div style="float:left;width:100%;"><span style="float:left;width:40px;">总价:</span><span style="float:left;"><?=$order->grand_total?>&nbsp;<?=$currencySing?></span></div>
				<div style="float:left;width:100%;"><span style="float:left;width:40px;">佣金:</span><span style="float:left;"><?=!empty($order->commission_total)?$order->commission_total:0 ?>&nbsp;<?=(!empty($addi_info['FinalValueFeeCurrency'])?$addi_info['FinalValueFeeCurrency']:'USD')?></span></div>
				<div style="float:left;width:100%;"><span style="float:left;width:40px;">PP费:</span><span style="float:left;"><?=!empty($order->paypal_fee)?$order->paypal_fee:0 ?>&nbsp;<?=(!empty($addi_info['FeeOrCreditAmountCurrency'])?$addi_info['FeeOrCreditAmountCurrency']:$currencySing)?></span></div>
				<?php 
				break;
			default:
				//共用部分
				echo Html::label('总价:',null,['class'=>'edit-order-label'])?><b style="color:#637c99;"><?=$order->grand_total ."&nbsp;".$order->currency?></b><br/><?php 
				/*
				?>
			<?=Html::label('商品总金额:',null,['class'=>'edit-order-label'])?><b style="color:#637c99;"><?=$order->subtotal?> <?=$order->currency?> </b><br>
			<?=Html::label('买家支付运费:',null,['class'=>'edit-order-label'])?><b style="color:#637c99;"><?= $order->shipping_cost?> <?=$order->currency?> </b><br>
			<?=Html::label('订单总金额:',null,['class'=>'edit-order-label'])?><?= $order->grand_total?> <?=$order->currency?><br>
			<?=Html::label('交易费:',null,['class'=>'edit-order-label'])?><?=$order->commission_total?> <?=$order->currency?><br>
				<?php 
				*/
				break;
		}
	}//end of function displayEditOrderAmountInfoHTML
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 封装 编辑订单  商品  显示的HTML
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param     $order			order model
	 +---------------------------------------------------------------------------------------------
	 * @return						string html
	 *
	 * @invoking					OrderFrontHelper::displayEditOrderAmountInfoHTML();
	 *
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2016/8/23				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function displayEditOrderItemInfoHtml($platform , $item){
		/*
		$ItemIDLabel = 'product id';
		if (in_array($platform , ['aliexpress' , 'cdiscount'])){
			$ItemIDLabel = 'product id'; 
		}
		*/
		$transactionIDHtmlStr = ''; 
		switch (true){
			case in_array($platform , ['aliexpress' , 'cdiscount']):
				$ItemIDLabel = 'product id';
				break;
			case in_array($platform , ['amazon']):
				$ItemIDLabel = 'ASIN';
				break;
			case in_array($platform , ['ebay']):
					$ItemIDLabel = 'item id';
					$transactionIDHtmlStr .= "交易号: ".$item['order_source_transactionid'];
					break;
			default:
				$ItemIDLabel = 'item id';
				break;
		}
	
		switch ($platform){
			default:
				//共用部分
				if (isset($item->item_source) &&  $item->item_source == 'platform'){
					// 平台产品
					echo '<p>平台产品</p>';
				}else{
					// 本地产品
					echo '<p>本地产品</p>';
				}
	?>
				<?= $ItemIDLabel?> : <?= $item['order_source_itemid']?><br>
				sku : <?=$item['sku']?><?= Html::hiddenInput('item[sku][]',$item['sku'] , []);?> <br>
				<?php 
				echo $item['product_name'];
				if (!empty($transactionIDHtmlStr)){
					echo "<br>".$transactionIDHtmlStr;
				}
				if ((!empty($item['platform_status'])) && (array_key_exists($item['platform_status'],OdOrder::$status) ==false)){					echo "<br>状态 : ".$item['platform_status'];
				}
				
				
				
				break;
		}
	}//end of function displayEditOrderItemInfoHtml
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 封装 编辑订单  商品  显示的HTML
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param     $order			order model
	 +---------------------------------------------------------------------------------------------
	 * @return						string html
	 *
	 * @invoking					OrderFrontHelper::displayEditOrderAmountInfoHTML();
	 *
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2016/8/23				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function displayEditOrderHeaderInfo($platform , $order){
		switch (true){
			case in_array($platform , ['ebay' ]):
				//ebay 平台状态需要特殊处理
				if (!empty(\eagle\modules\platform\apihelpers\PlatformAccountApi::$PLATFORMLOGO[$order->order_source])):?>
					<img alt="<?= $order->order_source?>" src="<?= \eagle\modules\platform\apihelpers\PlatformAccountApi::$PLATFORMLOGO[$order->order_source]?>" class="platform-logo">
					<?php endif;?>
					
					<?=Html::label('订单号:',null,['class'=>'edit-order-label'])?><span><?=$order->order_id?></span>
					<?=Html::label('平台订单号：',null,['class'=>'edit-order-label'])?><span><?=$order->order_source_order_id?></span>
					<?=Html::label('平台状态：',null,['class'=>'edit-order-label'])?><span><?=$order->order_source_status?></span>
					<?=Html::label('小老板状态：',null,['class'=>'edit-order-label'])?><span><?= OdOrder::$status[$order->order_status]?></span>
				<?php 
				break;
			default:
				if (!empty(\eagle\modules\platform\apihelpers\PlatformAccountApi::$PLATFORMLOGO[$order->order_source])):?>
					<img alt="<?= $order->order_source?>" src="<?= \eagle\modules\platform\apihelpers\PlatformAccountApi::$PLATFORMLOGO[$order->order_source]?>" class="platform-logo">
					<?php endif;?>
					
					<?=Html::label('订单号:',null,['class'=>'edit-order-label'])?><span><?=$order->order_id?></span>
					<?=Html::label('平台订单号：',null,['class'=>'edit-order-label'])?><span><?=$order->order_source_order_id?></span>
					<?=Html::label('平台状态：',null,['class'=>'edit-order-label'])?><span><?=$order->order_source_status?></span>
					<?=Html::label('小老板状态：',null,['class'=>'edit-order-label'])?><span><?= OdOrder::$status[$order->order_status]?></span>
				<?php 
				break;
		}
	}//end of function displayEditOrderHeaderInfo
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 封装 编辑订单  全部商品  显示的table 相关的html
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param     $order			order model
	 +---------------------------------------------------------------------------------------------
	 * @return						string html
	 *
	 * @invoking					OrderFrontHelper::displayEditOrderItemInfo();
	 *
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2016/10/14				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function displayEditOrderItemInfo( $order ,$existProductResult, $order_rootsku_product_image = array()){
		$manual_sel = [
			'enable'=>'启用',
			'disable'=>'禁用',
		];

		//获取库存信息
		$stock_list = array();
		$default_warehouse_id = 0;
		if (count($order->items)){
			$sku_list = array();
			$warehouse_list = ['0'];
			foreach($order->items as $item){
				if(!empty($item->root_sku) && !in_array($item->root_sku, $sku_list)){
					$sku_list[] = $item->root_sku;
				}
			}
			if(!empty($order->default_warehouse_id)){
				$default_warehouse_id = $order->default_warehouse_id;
				$warehouse_list[] = $order->default_warehouse_id;
			}
			$stock_list = InventoryApiHelper::GetSkuStock($sku_list, $warehouse_list);
		}

		switch (true){
			default:
				?>
				<form id="form_order_item_info" name="form_order_item_info" method="post" action="">
				
				<table id="order_item_info" class="table table-condensed table-bordered" id="TableTransactionModify" style="font-size:12px;border:0px;">
					<tr>
						<th width='60px'>图片</th>
						<th>商品信息</th>
						<th>原始数量</th>
						<th>数量</th>
						<th>单价</th>
						<th>SKU</th>
						<th><?=Html::button('添加商品',['class'=>'iv-btn btn-important','onclick'=>'OrderCommon.addEditOrderItem();'])?>
						<?=Html::button('保存',['class'=>'iv-btn btn-success','onclick'=>'OrderCommon.saveEditOrderItem();'])?>
						</th>
					</tr>
					<?php 
					if (count($order->items)):foreach($order->items as $item):
						if (in_array($item->sku, CdiscountOrderInterface::getNonDeliverySku())) continue;
					?>
					<tr style="background-color: #f4f9fc">
						<td><img src="<?= (!empty($item->root_sku) && !empty($order_rootsku_product_image[$order->order_id][$item->root_sku])) ? $order_rootsku_product_image[$order->order_id][$item->root_sku] : $item->photo_primary?>" width="60px" height="60px"></td>
						<td style="text-align: left;"><?php OrderFrontHelper::displayEditOrderItemInfoHtml($order->order_source, $item)?></td>
						<td><?php
						if ($item->ordered_quantity != $item->quantity){
							echo "<del>".$item->ordered_quantity."</delv>";
						}else{
							echo $item->ordered_quantity;
						}
						
						if (!empty($item->addi_info)){
							$itemInfo = json_decode($item->addi_info,true);
							if (isset($itemInfo['packingQuantityMemo'])){
								echo "(".$itemInfo['packingQuantityMemo'].")";
							}
						}
						?></td>
						<td>
						<!--
						<span <?php if ($item->quantity>1){echo 'class="multiitem"';}?>><?=$item->quantity?></span>
						-->
						<?= Html::hiddenInput('item[itemid][]',$item->order_item_id , ['class'=>"form-control"]);?>
						<?= Html::hiddenInput('item[price][]',$item->price , ['class'=>"form-control"]);?>
						<?= Html::hiddenInput('item[product_name][]',$item->product_name , ['class'=>"form-control"]);?>
						<?= Html::hiddenInput('item[manual_status][]',$item->manual_status , ['class'=>"form-control"]);?>
						<?= Html::textInput('item[quantity][]',$item->quantity , ['class'=>"form-control"]);?>
						</td>
						<td><?php  //手工非补发非已完成的订单支持修改单价 
							echo ($order->order_status < 500 && $order->order_capture == 'Y' && $order['reorder_type'] != 'after_shipment') ? (Html::textInput('item[edit_price][]',$item->price , ['class'=>"form-control"])) : $item->price ?>
						</td>
						<td>
						<div  data-item-id="<?= $item->order_item_id?>"  class="" style="min-width: 150px;">
							<?php 																		
							if(isset($item->root_sku) &&  !empty($item->root_sku)){
								echo $item->root_sku;
								echo "<br>";
								//echo Html::button('重置',['class'=>"iv-btn btn-important",'onclick'=>"OrderCommon.generateProductBox('".$item->order_item_id."')"]);
								//echo Html::button('解绑',['class'=>"iv-btn btn-warn",'onclick'=>"OrderCommon.generateProductBox('".$item->order_item_id."')"]);
								//echo '<p style="margin: 5px;"><span id="rootskurealtion" class="label label-success" style="width: 77px;display: inline-block;"></span></p>';
								if($order->order_status != 100 && $order->order_status != 500 && $order->order_status != 600 ){
									echo "<span style='color: #999999;'> 可用库存: ".(empty($stock_list[$default_warehouse_id][$item->root_sku]) ? '0' : $stock_list[$default_warehouse_id][$item->root_sku])."</span>";
									echo "<br>";
								}
								if($order->order_status==200 || $order->order_status==100 || ($order->order_status==300 && ($order->carrier_step==0 || $order->carrier_step==4))){
									echo Html::button('更换配对',['class'=>"iv-btn btn-important rootskubtn-pd",'onclick'=>"OrderCommon.PairProduct('".$item->order_item_id."','".$item->sku."','".$item->root_sku."',1)"]);
									echo Html::button('解除配对',['style'=>"margin-left:5px;",'class'=>"iv-btn btn-warn rootskubtn-pd",'onclick'=>"OrderCommon.PairProduct('".$item->order_item_id."','".$item->sku."','".$item->root_sku."',0)"]);
								}
							}
							else{
								if($order->order_status==200 || $order->order_status==100 || ($order->order_status==300 && ($order->carrier_step==0 || $order->carrier_step==4))){
									echo Html::button('配对商品',['class'=>"iv-btn btn-important rootskubtn-pd",'onclick'=>"OrderCommon.PairProduct('".$item->order_item_id."','".(empty($item->sku)?'':$item->sku)."','".(empty($item->root_sku)?'':$item->root_sku)."',1)"]);
								}
								else
									echo '未配对SKU';
							}
							?>
						</div>
						</td>
						<td>
							<?php					
							if (isset($item->item_source) &&  $item->item_source == 'platform'){
								if($order->order_relation!='fs'){
									// 平台产品
									//echo '<p style="margin: 5px;"><span class="label label-success" style="width: 77px;display: inline-block;">平台产品</span></p>';
									echo '<p style="margin: 5px;">';
									echo Html::dropDownList('manual_status',$item->manual_status,$manual_sel,['class'=>'iv-input','style'=>'width:77px;margin:0px;']);
									echo "</p>";

								}
								else {
									echo '<p style="margin: 5px;">'.$manual_sel[$item->manual_status].'</p>';
								}
							}else{
								if($order->order_relation!='fs'){
									// 本地产品
									//echo '<p style="margin: 5px;"><span class="label label-info" style="width: 77px;display: inline-block;">本地产品</span></p>';
									echo '<p style="margin: 5px;">';
									echo Html::button('删除商品',['class'=>"iv-btn btn-warn",'onclick'=>"OrderCommon.editOrderDelRow(this , '".$item->order_item_id."' )"]);
									echo "</p>";
								}
							}
							if (isset($existProductResult[$item->sku]) && empty($existProductResult[$item->sku]) ){
								echo Html::button('生成商品',['class'=>"iv-btn btn-important",'onclick'=>"OrderCommon.generateProductBox('".$item->order_item_id."')"]);
							}
							?>
						</td>
					</tr>
					<?php endforeach;endif;?>
				</table>
				</form>
		<?php 
		}//end of switch
	}//end of function displayEditOrderItemInfo
	
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 封装 编辑订单  平台状态的  相关的html
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param     $order			order model
	 +---------------------------------------------------------------------------------------------
	 * @return						string html
	 *
	 * @invoking					OrderFrontHelper::displayOrderSourceStatus();
	 *
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2016/10/14				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function displayOrderSourceStatus($order,$orderCheckOutList=[]){
		switch (true){
			case in_array($order->order_source , ['ebay' ]):
				OrderFrontHelper::getEbayStatus($order,$orderCheckOutList);
				break;
			case in_array($order->order_source , ['aliexpress' ]):
				echo OdOrder::$aliexpressStatus[$order->order_source_status];
				break;
			default:
				echo $order->order_source_status;
				break;
		}
	}//end of function displayOrderSourceStatus
	
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 封装 编辑订单  订单号  相关的html
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param     $order			order model
	 +---------------------------------------------------------------------------------------------
	 * @return						string html
	 *
	 * @invoking					OrderFrontHelper::displayEditOrderPlatformOrderIDInfo($order);
	 *
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2016/10/25				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function displayEditOrderPlatformOrderIDInfo($order){
		switch (true){
			case ($order->order_capture=='Y'):
				?>
				<?=Html::label('手工订单号：',null,['class'=>'edit-order-label'])?><span><?=$order->order_source_order_id?></span>
				<?php
				break;
			case in_array($order->order_source , ['ebay']) :
				?>
				<?=Html::label('SRN：',null,['class'=>'edit-order-label'])?><span><?=$order->order_source_srn?></span>
				<?php
				
				break;
			default:
				?>
				<?=Html::label('平台订单号：',null,['class'=>'edit-order-label'])?><span><?=$order->order_source_order_id?></span>
				<?php
				break;
		}
	}//end of function displayEditOrderPlatformOrderIDInfo
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 封装 利润计算  相关的html
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param     $order			order model
	 +---------------------------------------------------------------------------------------------
	 * @return						string html
	 *
	 * @invoking					OrderFrontHelper::displayProfixCalcToolbar();
	 *
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2016/11/01				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function displayProfixCalcToolbar(){
		switch (true){
			default:
				?>
				<div class="oms_list_profit" style="line-height: 20px;position: relative;display: inline-block;">
					<label style="border: 1px transparent;color: #555;font-size: 14px;height: 30px;padding: 5px;margin: 5px 0 10px 0;">选中订单的利润</label>
					<input type="text" id="selected_profit_calculated_rate" value="" readonly class='iv-input' style="width:50px;"> 
					<input type="text" id="selected_profit_total" value="" readonly class='iv-input' style="width:100px;">
					<img class="list_profit_tip" style="cursor: pointer;margin: 10px 0 0px 0;" width="16" src="/images/questionMark.png" data-toggle="popover" data-content="<?="显示已经勾选的订单的利润统计情况;<br>第一项数值为:已统计/已勾选;<br>第二项数值为:已统计的总利润(RMB)"?>" data-html="true" data-trigger="hover"  >
				</div>
				<?php 
				break;
		}
	}//end of displayProfixCalcToolbar
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 封装 利润计算  相关的html
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param     $order			order model
	 +---------------------------------------------------------------------------------------------
	 * @return						string html
	 *
	 * @invoking					OrderFrontHelper::displayProfixTipDialog($order);
	 *
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2016/11/01				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function displayProfixTipDialog($order){
		switch (true){
			default:
				$currencySing = $order->currency;
				if(!is_null($order->profit)){
					if(floatval($order->profit) >0 ) $color = 'green';
					else $color = 'red';
					echo "<br>";
					if(!empty($order->addi_info)){
						$addi_info = json_decode($order->addi_info,true);
						$exchange_rate = empty($addi_info['exchange_rate'])?'--':$addi_info['exchange_rate'];
						$exchange_loss = empty($addi_info['exchange_loss'])?'--':$addi_info['exchange_loss'];
						$product_cost = empty($addi_info['product_cost'])?'--':$addi_info['product_cost'];
						$logistics_cost = empty($addi_info['logistics_cost'])?'--':$addi_info['logistics_cost'];
						$profit_detail = '商品成本:'.$product_cost.'<br>物流成本:'.$logistics_cost.'<br>销售金额:'.$order->grand_total.$currencySing.'<br>货币汇率:'.$exchange_rate.'<br>汇损:'.$exchange_loss.'%'.'<br>计算公式:利润(RMB)=订单销售额*汇率*（1-汇损）-商品总成本-订单物流成本';
						echo "<span class='profit_detail' data-profit='".$order->profit."' data-toggle='popover' data-content='".$profit_detail."' data-html='true' data-trigger='hover' style='color:".$color."'>";
					}else{
						echo "<span style='font-weight:bold;'>";
					}
					echo "利润：".$order->profit.'</span>';
		
				}else{?>
					<br><span style="color:#FFB0B0" title="勾选订单后可于批量操作中计算利润">未计算利润</span>
				<?php }
				break;
		}//end of switch 
		
	}//end of function displayProfixTipDialog
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 封装 修改ebay订单  相关的收货地址 html
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param     $order			order model
	 +---------------------------------------------------------------------------------------------
	 * @return						string html
	 *
	 * @invoking					OrderFrontHelper::displayEditEbayOrderShippingAddressHtml( $order , $paypal);
	 *
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2017/01/04				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function displayEditEbayOrderShippingAddressHtml( $order , $countryList, $paypal ){
		switch (true){
			case in_array($order->order_source , ['ebay' ]):
			?>
<div id="consignee-info-view" class="col-md-12">
     
				<?php 
					if (!empty($order->origin_shipment_detail)){
						$consigneeInfo = json_decode($order->origin_shipment_detail,true);
					}else{
						$consigneeInfo = [];
					}
				?>
				<div class="row">
					<div class="col-md-6  ">
						<div class="row"  style="line-height: 15px;"><h3>收货地址</h3></div>
						<?php 
							$leftColumnsList = [
								"consignee"=>"收件人",
								"consignee_country"=>"国家",
								"consignee_address_line1"=>"地址行1",
								"consignee_address_line2"=>"行2",
								"consignee_city"=>"城市",
								"consignee_province"=>"州/省",
								"consignee_postal_code"=>"邮编",
								"consignee_phone"=>"电话",
								"consignee_mobile"=>"手机",
								"consignee_fax"=>"传真",
								"consignee_company"=>"公司",
							];
	
							foreach($leftColumnsList as $tmpColName=>$tmpColLabelName){
								?>
							<div class="row">
									
					      			<div class="col-md-2 nopadding"><?=Html::label($tmpColLabelName.'：',null,['class'=>'text-right'])?></div>
									<div class="col-md-10"><?= html::label($order->$tmpColName,null,['class'=>'text-left'  , 'data-key'=>$tmpColName])?>
										<?php if (!empty($consigneeInfo[$tmpColName])):?>
										<del class="text-important <?=($consigneeInfo[$tmpColName]==$order->$tmpColName)?"hidden":"";?>"><?=$consigneeInfo[$tmpColName]?></del>
										<?php else:?>
										<del class="text-important hidden"><?=$order->$tmpColName?></del>
										<?php endif;?>
									</div>
							</div>
							<?php
							}
							?>
					</div>
					<div class="col-md-6">
						<div class="row"  style="line-height: 15px;"><h3>paypal地址</h3></div>
						<?php 
						$rightColumnsList = [
							"shiptoname"=>"收件人",
							"shiptocountryname"=>"国家",
							"shiptostreet"=>"地址行1",
							"shiptostreet2"=>"行2",
							"shiptocity"=>"城市",
							"shiptostate"=>"州/省",
							"shiptozip"=>"邮编",
							"paypal_phone"=>"电话",
							"paypal_mobile"=>"手机",
							"paypal_fax"=>"传真",
							"paypal_company"=>"公司",
						];

						foreach($rightColumnsList as $tmpColName=>$tmpColLabelName){
						?>
						<div class="row">
							<div class="col-md-2 nopadding"><?=Html::label($tmpColLabelName.'：',null,['class'=>'text-right'])?></div>
							<div class="col-md-10"><?= html::label((isset($paypal->$tmpColName)?$paypal->$tmpColName:''),null,['class'=>'text-left'  , 'data-key'=>$tmpColName])?>
							</div>
						</div>
						<?php
						}
						?>
					</div>
				</div><!-- end of row -->
				</div> <!-- end of consignee-info-view -->
    
    
    <div id="consignee-info-edit"  class="col-md-12 hidden">
    				
    				<div class="row">
    					<div class="col-md-6">
    						<div class="row"  style="line-height: 15px;"><h3>收货地址</h3></div>
    						<?php 
		    				foreach($leftColumnsList as $tmpColName=>$tmpColLabelName){
								if ($tmpColName == "consignee_country"){
									unset($countryList['--']);
							?>
								<div class="row">
									<div class="col-md-2 nopadding"><?=Html::label($tmpColLabelName.'：',null,['class'=>'text-right'])?></div>
									<div class="col-md-10"><?=html::dropDownList('consignee_country_code',$order->consignee_country_code ,$countryList ,['style'=>'width:190px'])?></div>
								</div>
								<?php 
									echo html::hiddenInput($tmpColName ,$order->consignee_country );
								}else{
									?>
			    					<div class="row">
			    						<div class="col-md-2 nopadding"><?=Html::label($tmpColLabelName.'：',null,['class'=>'text-right'])?></div>
										<div class="col-md-10"><?=Html::textInput($tmpColName,nl2br($order->$tmpColName),['class'=>'iv-input text-left'])?></div>
			    					</div>
			    					<?php 
								}
							}
		    				?>
    					</div>
		    			<div class="col-md-6">
		    				<div class="row"  style="line-height: 15px;"><h3>paypal地址</h3></div>
		    				<?php 
		    				
		    				foreach($rightColumnsList as $tmpColName=>$tmpColLabelName){
		    					?>
    							<div class="row">
    								<div class="col-md-2 nopadding"><?=Html::label($tmpColLabelName.'：',null,['class'=>'text-right'])?></div>
    								<div class="col-md-10"><?= html::label((isset($paypal->$tmpColName)?$paypal->$tmpColName:''),null,['class'=>'text-left'  , 'data-key'=>$tmpColName])?>
    								</div>
    							</div>
    							<?php
    							}
		    				?>
		    			</div>
    				</div><!-- end of row -->
    			
				</div>	<!--  end of consignee-info-edit -->		
			<?php 
			break;
		}
	}//end of function displayEditEbayOrderShippingAddressHtml
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 封装 修改订单  相关的收货地址 html
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param     $order			order model
	 +---------------------------------------------------------------------------------------------
	 * @return						string html
	 *
	 * @invoking					OrderFrontHelper::displayEditEbayOrderShippingAddressHtml( $order , $paypal);
	 *
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2017/01/04				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function displayEditOrderShippingAddressHtml($order,$countryList){
		?>
	<div id="consignee-info-view" class="col-md-12">
     
				<?php 
					if (!empty($order->origin_shipment_detail)){
						$consigneeInfo = json_decode($order->origin_shipment_detail,true);
					}else{
						$consigneeInfo = [];
					}
				?>
					
				<div class="row">
					<div class="col-md-6">
						<?php 
						$leftColumnsList = [
							"consignee"=>"收件人",
							"consignee_address_line1"=>"地址行1",
							"consignee_address_line2"=>"行2",
							"consignee_county"=>"镇",
							"consignee_city"=>"城市",
							"consignee_district"=>"区",
							"consignee_province"=>"州/省",
						];
						foreach($leftColumnsList as $tmpColName=>$tmpColLabelName){
							?>
						<div class="row">
							<div class="col-md-2 nopadding"><?=Html::label($tmpColLabelName.'：',null,['class'=>'text-right'])?></div>
							<div class="col-md-10"><?= html::label($order->$tmpColName,null,['class'=>'text-left'  , 'data-key'=>$tmpColName])?>
								<?php if (!empty($consigneeInfo[$tmpColName])):?>
								<del class="text-important <?=($consigneeInfo[$tmpColName]==$order->$tmpColName)?"hidden":"";?>"><?=$consigneeInfo[$tmpColName]?></del>
								<?php else:?>
								<del class="text-important hidden"><?=$order->$tmpColName?></del>
								<?php endif;?>
							</div>
						</div>
						<?php
						}
						?>
					</div>
					
					<div class="col-md-6">
						<?php
						$rightColumnsList = [
							"consignee_country"=>"国家",
							"consignee_postal_code"=>"邮编",
							"consignee_phone"=>"电话",
							"consignee_mobile"=>"手机",
							"consignee_fax"=>"传真",
							"consignee_company"=>"公司",
						];
						
						foreach($rightColumnsList as $tmpColName=>$tmpColLabelName){
	?>
						<div class="row">
							<div class="col-md-2 nopadding"><?=Html::label($tmpColLabelName.'：',null,['class'=>'text-right'])?></div>
							<div class="col-md-10"><?= html::label($order->$tmpColName,null,['class'=>'text-left'  , 'data-key'=>$tmpColName])?>
								<?php if (isset($consigneeInfo[$tmpColName])):?>
								<del class="text-important <?=($consigneeInfo[$tmpColName]==$order->$tmpColName)?"hidden":"";?>"><?=$consigneeInfo[$tmpColName]?></del>
								<?php else:?>
								<del class="text-important hidden"><?=$order->$tmpColName?></del>
								<?php endif;?>
							</div>
						</div>
						<?php
						}
						?>
					</div>
				
				</div>
				
    </div> <!-- end of consignee-info-view -->
    
    <div id="consignee-info-edit"  class="col-md-12 hidden">
    				<div class="row">
    					<div class="col-md-6">
    						<?php 
		    				foreach($leftColumnsList as $tmpColName=>$tmpColLabelName){
								if ($tmpColName == "consignee_country"){
									unset($countryList['--']);
							?>
								<div class="row">
									<div class="col-md-2 nopadding"><?=Html::label($tmpColLabelName.'：',null,['class'=>'text-right'])?></div>
									<div class="col-md-10"><?=html::dropDownList('consignee_country_code',$order->consignee_country_code ,$countryList ,['style'=>'width:190px'])?></div>
								</div>
								<?php 
									echo html::hiddenInput($tmpColName ,$order->consignee_country );
								}else{
									?>
			    					<div class="row">
			    						<div class="col-md-2 nopadding"><?=Html::label($tmpColLabelName.'：',null,['class'=>'text-right'])?></div>
										<div class="col-md-10"><?=Html::textInput($tmpColName,nl2br($order->$tmpColName),['class'=>'iv-input text-left'])?></div>
			    					</div>
			    					<?php 
								}
							}
		    				?>
    					</div>
		    			<div class="col-md-6">
		    				<?php 
		    				foreach($rightColumnsList as $tmpColName=>$tmpColLabelName){
		    					if ($tmpColName == "consignee_country"){
									unset($countryList['--']);
									?>
									<div class="row">
										<div class="col-md-2 nopadding"><?=Html::label($tmpColLabelName.'：',null,['class'=>'text-right'])?></div>
										<div class="col-md-10"><?=html::dropDownList('consignee_country_code',$order->consignee_country_code ,$countryList ,['style'=>'width:190px'])?></div>
									</div>
								<?php 
									echo html::hiddenInput($tmpColName ,$order->consignee_country );
								}else{
									?>
			    					<div class="row">
			    						<div class="col-md-2 nopadding"><?=Html::label($tmpColLabelName.'：',null,['class'=>'text-right'])?></div>
										<div class="col-md-10"><?=Html::textInput($tmpColName,nl2br($order->$tmpColName),['class'=>'iv-input text-left'])?></div>
			    					</div>
			    					<?php 
								}
							}
		    				?>
		    			</div>
    				</div>
    			
				</div>
		
		<?php 
	}//end of function  displayEditOrderShippingAddressHtml
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 封装 修改订单  相关的报关信息 html
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param     $order			order model
	 +---------------------------------------------------------------------------------------------
	 * @return						string html
	 *
	 * @invoking					OrderFrontHelper::displayViewOrderDeclarationInfo( $order);
	 *
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lhw		2017/03/10				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function displayViewOrderDeclarationInfo( $order){
		?>
						<table class="myj-table col-xs-12 mTop5" id="customsFormTable">
															<tbody><tr>
																<th>SKU</th>
																<th>报关信息</th>
																<th>操作</th>
															</tr>
															<?php 
																$declaration_list=eagle\modules\carrier\helpers\CarrierDeclaredHelper::getOrderDeclaredInfoByOrder($order);

																foreach($declaration_list as $declaration_list_key=>$declaration_listone){
															?>
																	<tr>
																		<td style="min-width:280px;"><a href="/catalog/product/list?txt_tag=all&txt_brand=all&txt_supplier=all&product_type=&txt_search=<?php echo empty($declaration_listone['sku'])?'':$declaration_listone['sku'];?>" target="_blank" class="fBlue"><?php echo empty($declaration_listone['sku'])?'':$declaration_listone['sku'];?></a></td>
																		<td style="min-width:380px;text-align:left;" class="tdCustomsForm" id="td<?php echo $declaration_list_key;?>">
																		<?php  if($declaration_listone['not_declaration']==1){ ?>
																				<span class="fRed" id="">请点击「编辑」，填写报关信息</span>
																		<?php }else{ ?>
																				<span class="nameChSpan"><?php echo isset($declaration_listone['declaration']['nameCN'])?$declaration_listone['declaration']['nameCN']:'';?></span>&nbsp;/&nbsp;<span class="nameEnSpan"><?php echo isset($declaration_listone['declaration']['nameEN'])?$declaration_listone['declaration']['nameEN']:'';?></span>&nbsp;/&nbsp;$<span class="deValSpan"><?php echo isset($declaration_listone['declaration']['price'])?$declaration_listone['declaration']['price']:'';?></span>&nbsp;/&nbsp;<span class="weightSpan"><?php echo isset($declaration_listone['declaration']['weight'])?floor($declaration_listone['declaration']['weight']):'';?></span>（g）&nbsp;/&nbsp;<span class="hsCodeSpan"><?php echo isset($declaration_listone['declaration']['code'])?$declaration_listone['declaration']['code']:'';?></span>
																		<?php } ?>
																		</td>
																		<td style="width:94px;">
																			<input type="hidden" name="productAttr" value="<?php echo $declaration_list_key;?>">
			                                                                
																			    <a href="javascript:" class="fBlue" onclick="OrderCommon.EditOrderDeclaration(<?php echo $declaration_list_key;?>,this)">编辑</a>
			                                                                
																		</td>
																	</tr>
																
														<?php } //endforeach?>
														</tbody></table>
					<?php 
	}//end of function displayViewOrderDeclarationInfo

	/**
	 +---------------------------------------------------------------------------------------------
	 * 封装 修改订单  相关的报关信息 html
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param     $order			order model
	 +---------------------------------------------------------------------------------------------
	 * @return						string html
	 *
	 * @invoking					OrderFrontHelper::displayEditOrderDeclarationInfo( $order);
	 *
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lhw		2017/03/10				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function displayEditOrderDeclarationInfo($order){
					?>
													<div class="col-xs-12 p0 mTop5" id="editCustomsFormDiv">
																			<form id="form-horizontal" class="form-horizontal"  action="">
																				<input id="order_itemid"  name="order_itemid[]" type="hidden" value="">
																				<input id="order_sku"  name="order_sku[]" type="hidden" value="">
																				<input id="order_source"  name="order_source" type="hidden" value="<?php echo $order->order_source; ?>">
																				<div class="col-xs-5">
																					<div class="col-xs-12">
																						<div class="form-group">
																							<label class="col-sm-2 control-label p0">中文名称:</label>
																							<div class="col-sm-8">
																								<input type="text" class="form-control horizontal" placeholder="" maxlength="100" id="nameCh" name="nameCN[]" style="margin: 0px;">
																							</div>
																						</div>
																						<div class="form-group">
																							<label class="col-sm-2 control-label p0">英文名称:</label>
																							<div class="col-sm-8">
																								<input type="text" class="form-control horizontal" placeholder="" maxlength="100" id="nameEn"  name="nameEN[]" style="margin: 0px;">
																							</div>
																						</div>
																						<div class="form-group">
																							<label class="col-sm-2 control-label p0">申报金额:</label>
																							<div class="col-sm-8 input-group" style="padding:0 15px;">
																								<input type="text" class="form-control horizontal" placeholder="" maxlength="20" id="declaredValue"  name="price[]">
																								<span class="input-group-addon">USD</span>
																							</div>
																						</div>
																						<div class="form-group">
																							<label class="col-sm-2 control-label p0">申报重量:</label>
																							<div class="col-sm-8 input-group" style="padding:0 15px;">
																								<input type="text" class="form-control horizontal" placeholder="" maxlength="20" id="weight"  name="weight[]">
																								<span class="input-group-addon" style="width:55px;">g</span>
																							</div>
																						</div>
																						<div class="form-group">
																							<label class="col-sm-2 control-label p0">海关编码:</label>
																							<div class="col-sm-8">
																								<input type="text" class="form-control horizontal" placeholder="非必填" id="detailHsCode" maxlength="50" style="margin: 0px;">
																							</div>
																						</div>
																					</div>
																				</div>
																				<div><a href="/configuration/carrierconfig/common-declared-info#show_div_undefined" class="fBlue" target="_blank">添加常用报关信息</a></div>
																				<div class="col-xs-4 border3 borderRdio mTop5" style="height:134px;overflow-y:scroll;padding-left: 8px;">
																					<div class="col-xs-12 p0">
																							<?php 
																								$CommonDeclaredInfo = eagle\models\carrier\CommonDeclaredInfo::find()->asArray()->all();
																								foreach ($CommonDeclaredInfo as $CommonDeclaredInfoone){
																									?>
																									<span style="font-size: 13px;line-height: 20px;"><a href="javascript:" data-ch="<?=$CommonDeclaredInfoone['ch_name']?>" data-en="<?=$CommonDeclaredInfoone['en_name']?>" data-weight="<?=(float)$CommonDeclaredInfoone['declared_weight']?>" data-decval="<?=$CommonDeclaredInfoone['declared_value']?>" data-hscode="<?=$CommonDeclaredInfoone['detail_hs_code']?>" onclick="OrderCommon.defaultCustomsForm(this);"><?=$CommonDeclaredInfoone['custom_name']?></a></span><br>
																									<?php 
																								}
																							?>
																					</div>
																				</div>
																			</form>
																<input id="changeold"  name="changeold" type="hidden" value="">
																		</div>
												<?php 
	}//end of function displayEditOrderDeclarationInfo
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 封装 修改订单  相关的发票地址 html
	 * @access static
	 * @param	$order			order model
	 * @param	$countryList
	 * @return					string html
	 * log		name			date				note
	 * @author	lzhl			2017/03/17			初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function displayEditOrderBillingAddressHtml($order,$countryList){
		?>
		<div id="billing-info-view" class="col-md-12">
	    <?php 
	    	$order_billing_info = [];
	    	//使用订单保存的发票信息
	    	if(!empty($order->billing_info))
	    		$order_billing_info = json_decode($order->billing_info,true);
	    	//如果订单信息里面未有发票信息 ,使用原始表的
	    	if(empty($order_billing_info) && $order->order_source=='cdiscount'){
	    		$order_billing_info = CdiscountOrderInterface::getBillingInfoByOrder($order->order_source_order_id);
	    		$order_billing_info['address_line1'] = $order_billing_info['address'];
	    		$order_billing_info['address_line2'] = '';
	    	}
	    	//如果订单信息里面未有billing info ,原始表也没有，则使用收件人地址
	    	if(empty($order_billing_info)){
	    		$order_billing_info = [
	    			'address_line1' => $order->consignee_address_line1,
					'address_line2' => $order->consignee_address_line2,
					'post_code' => $order->consignee_postal_code,
					'phone' => empty($order->consignee_mobile)?$order->consignee_phone:$order->consignee_mobile,
					'company' => $order->consignee_company,
					'city' => $order->consignee_city,
					'country' => $order->consignee_country,
					'name' => $order->consignee,
				];
	    	}
		?>
			<div class="row">
				<div class="col-md-6">
				<?php 
				$leftColumnsList = [
					"name"=>"收件人",
					"address_line1"=>"地址行1",
					"address_line2"=>"行2",
					//"company"=>"公司",
				];
				foreach($leftColumnsList as $tmpColName=>$tmpColLabelName){
				?>
					<div class="row">
						<div class="col-md-2 nopadding"><?=Html::label($tmpColLabelName.'：',null,['class'=>'text-right'])?></div>
						<div class="col-md-10"><?= html::label($order_billing_info[$tmpColName],null,['class'=>'text-left'  , 'data-key'=>'billing_info['.$tmpColName.']'])?></div>
					</div>
				<?php
				}
				?>
				</div>
						
				<div class="col-md-6">
					<?php
					$rightColumnsList = [
						"country"=>"国家",
						"city"=>"城市",
						"post_code"=>"邮编",
						"phone"=>"电话",
					];
					foreach($rightColumnsList as $tmpColName=>$tmpColLabelName){
					?>
					<div class="row">
						<div class="col-md-2 nopadding"><?=Html::label($tmpColLabelName.'：',null,['class'=>'text-right'])?></div>
						<div class="col-md-10"><?= html::label($order_billing_info[$tmpColName],null,['class'=>'text-left'  , 'data-key'=>'billing_info['.$tmpColName.']'])?></div>
					</div>
					<?php
					}
					?>
				</div>
			</div>	
	    </div> <!-- end of billing-info-view -->
	    
		<div id="billing-info-edit"  class="col-md-12 hidden">
	    	<div class="row">
    			<div class="col-md-6">
    				<?php 
    				foreach($leftColumnsList as $tmpColName=>$tmpColLabelName){
					?>
					<div class="row">
						<div class="col-md-2 nopadding"><?=Html::label($tmpColLabelName.'：',null,['class'=>'text-right'])?></div>
						<div class="col-md-10"><?=Html::textInput('billing_info['.$tmpColName.']',nl2br($order_billing_info[$tmpColName]),['class'=>'iv-input text-left'])?></div>
					</div>
					<?php 
					}
    				?>
    			</div>
		    	<div class="col-md-6">
    				<?php 
    				foreach($rightColumnsList as $tmpColName=>$tmpColLabelName){
    				?>
					<div class="row">
						<div class="col-md-2 nopadding"><?=Html::label($tmpColLabelName.'：',null,['class'=>'text-right'])?></div>
						<?php if($tmpColName=='country'):?>
						<div class="col-md-10"><?=html::dropDownList('billing_info[country]',$order_billing_info[$tmpColName] ,$countryList ,['style'=>'width:190px'])?></div>
						<?php else:?>
						<div class="col-md-10"><?=Html::textInput('billing_info['.$tmpColName.']',nl2br($order_billing_info[$tmpColName]),['class'=>'iv-input text-left'])?></div>
						<?php endif;?>
					</div>
					<?php 
					}
		    		?>
		    	</div>
    		</div>	
		</div>
	<?php 
	}//end of function  displayEditOrderBillingAddressHtml
		
}