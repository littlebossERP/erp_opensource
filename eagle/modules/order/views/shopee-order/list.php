<?php
use yii\helpers\Html;
use yii\helpers\Url;
use eagle\modules\order\models\OdOrder;
use eagle\modules\carrier\apihelpers\CarrierApiHelper;
use eagle\modules\inventory\helpers\InventoryApiHelper;
use eagle\modules\util\helpers\TranslateHelper;
use eagle\modules\order\helpers\OrderTagHelper;
use eagle\modules\carrier\helpers\CarrierHelper;
use eagle\modules\tracking\helpers\TrackingHelper;
use eagle\modules\carrier\helpers\CarrierOpenHelper;
use eagle\modules\tracking\models\Tracking;
use eagle\modules\order\helpers\OrderFrontHelper;
use eagle\modules\tracking\helpers\TrackingApiHelper;
use eagle\modules\tracking\helpers\CarrierTypeOfTrackNumber;
use eagle\modules\order\helpers\ShopeeOrderHelper;
use eagle\modules\order\helpers\OrderHelper;
use eagle\modules\platform\apihelpers\ShopeeAccountsApiHelper;
use eagle\modules\util\helpers\SysBaseInfoHelper;
use eagle\modules\order\helpers\OrderListV3Helper;


$baseUrl = \Yii::$app->urlManager->baseUrl . '/';
$this->registerJsFile(\Yii::getAlias('@web')."/js/project/order/OrderTag.js", ['depends' => ['yii\jui\JuiAsset']]);
$this->registerJsFile(\Yii::getAlias('@web')."/js/project/order/orderOrderList.js", ['depends' => ['yii\web\JqueryAsset']]);
$this->registerJsFile(\Yii::getAlias('@web')."/js/origin_ajaxfileupload.js", ['depends' => ['yii\web\JqueryAsset']]);
$this->registerCssFile(\Yii::getAlias('@web').'/css/order/order.css');
$this->registerJs("OrderTag.TagClassList=".json_encode($tag_class_list).";" , \yii\web\View::POS_READY);
$this->registerJsFile(\Yii::getAlias('@web')."/js/project/order/orderCommon.js?v=".OrderHelper::$OrderCommonJSVersion, ['depends' => ['yii\web\JqueryAsset']]);
if (!empty($_REQUEST['country'])){
	$this->registerJs("OrderCommon.currentNation=".json_encode(array_fill_keys(explode(',', $_REQUEST['country']),true)).";" , \yii\web\View::POS_READY);
}

#####################################	左侧菜单新功能提示end	#################################################################

$this->registerJs("OrderList.initClickTip();" , \yii\web\View::POS_READY);

$this->registerJs("OrderCommon.NationList=".json_encode(@$countrys).";" , \yii\web\View::POS_READY);
$this->registerJs("OrderCommon.NationMapping=".json_encode(@$country_mapping).";" , \yii\web\View::POS_READY);
$this->registerJs("OrderCommon.initNationBox($('div[name=div-select-nation][data-role-id=0]'));" , \yii\web\View::POS_READY);

$this->registerJs("OrderCommon.customCondition=".json_encode(@$counter['custom_condition']).";" , \yii\web\View::POS_READY);
$this->registerJsFile(\Yii::getAlias('@web')."/js/project/order/shopeeOrder/shopeeOrder.js", ['depends' => ['yii\web\JqueryAsset']]);
$this->registerJs("shopeeOrder.OMSLeftMenuAutoLoad();" , \yii\web\View::POS_READY);
$orderStatus21 = OdOrder::getOrderStatus('oms21'); // oms 2.1 的订单状态

$uid = \Yii::$app->user->id;

$tmpCustomsort = Odorder::$customsort;

$showMergeOrder = 0;
if (200 == @$_REQUEST['order_status'] && 223 == @$_REQUEST['exception_status']){
	$this->registerJs("OrderCommon.showMergeRow();" , \yii\web\View::POS_READY);
	$nowMd5 = "";
	$showMergeOrder = 1;
}
 
//************************************** dash board end  ********************************************//
$this->registerJsFile(\Yii::getAlias('@web')."/js/project/order/shopeeOrder/tongbu.js", ['depends' => ['yii\web\JqueryAsset','eagle\assets\PublicAsset']]);
$this->registerJs("$('.prod_img').popover();" , \yii\web\View::POS_READY);

$non17Track = CarrierTypeOfTrackNumber::getNon17TrackExpressCode();

$this->registerJsFile(\Yii::getAlias('@web')."/js/project/order/orderListV3.js?v=".\eagle\modules\order\helpers\OrderListV3Helper::$OrderCommonJSV3, ['depends' => ['yii\web\JqueryAsset']]);

$this->registerJsFile ( \Yii::getAlias ( '@web' ) . "/js/project/delivery/order/cainiao.js?v=".\eagle\modules\order\helpers\OrderListV3Helper::$OrderCommonJSV3, ['depends' => ['yii\web\JqueryAsset']]);
$this->registerJsFile(\Yii::getAlias('@web').'js/jquery.json-2.4.js', ['depends' => ['yii\jui\JuiAsset','yii\bootstrap\BootstrapPluginAsset']]);
$this->registerJs("doConnect();" , \yii\web\View::POS_READY);
?>
<script type="text/javascript" src="//www.17track.net/externalcall.js"></script>
<style>
.popover{
	max-width: 500px;
}

.table td,.table th{
	text-align: center;
}

table{
	font-size:12px;
}
.table>tbody td{
color:#637c99;
}
.table>tbody a{
color:#337ab7;
}
.table>thead>tr>th {
height: 35px;
vertical-align: middle;
}
.table>tbody>tr>td {
/* height: 35px; */
vertical-align: middle;
}
.sprite_pay_0,.sprite_pay_1,.sprite_pay_2,.sprite_pay_3,.sprite_shipped_0,.sprite_shipped_1,.sprite_check_1,.sprite_check_0
{
	display:block;
	background-image:url(/images/MyEbaySprite.png);
	overflow:hidden;
	float:left;
	width:20px;
	height:20px;
	text-indent:20px;
}
.sprite_pay_0
{
	background-position:0px -92px;
}
.sprite_pay_1
{
	background-position:-50px -92px;
}
.sprite_pay_2
{
	background-position:-95px -92px;
}
.sprite_pay_3
{
	background-position:-120px -92px;
}
.sprite_shipped_0
{
	background-position:0px -67px;
}
.sprite_shipped_1
{
	background-position:-50px -67px;
}
.sprite_check_1
{
	background-position:-100px -15px;
}
.sprite_check_0
{
	background-position:-77px -15px;
}
.exception_201,.exception_202,.exception_221,.exception_210,.exception_222,.exception_223,.exception_299{
	display:block;
	background-image:url(/images/icon-yichang-eBay.png);
	overflow:hidden;
	float:left;
	width:30px;
	height:15px;
	text-indent:20px;
}
.exception_201{
	background-position:-3px -10px;
}
.exception_202{
	background-position:-26px -10px;
}
.exception_221{
	background-position:-55px -10px;
	width:50px;
}
.exception_210{
	background-position:-107px -10px;
}
.exception_222{
	background-position:-135px -10px;
}
.exception_223{
	background-position:-170px -10px;
}
.exception_299{
	background-position:-199px -10px;
}
.input-group-btn > button{
  padding: 0px;
  height: 28px;
  width: 30px;
  border-radius: 0px;
  border: 1px solid #b9d6e8;
}

.div-input-group{
	  width: 150px;
  display: inline-block;
  vertical-align: middle;
	margin-top:1px;

}

.div-input-group>.input-group>input{
	height: 28px;
}

.div_select_tag>.input-group , .div_new_tag>.input-group{
  float: left;
  width: 32%;
  vertical-align: middle;
  padding-right: 10px;
  padding-left: 10px;
  margin-bottom: 10px;
}

.div_select_tag{
	display: inline-block;
	border-bottom: 1px dotted #d4dde4;
	margin-bottom: 10px;
}

.div_new_tag {
  display: inline-block;
}

.span-click-btn{
	cursor: pointer;
}

.btn_tag_qtip a {
  margin-right: 5px;
}

.div_add_tag{
	width: 600px;
}
.multiitem{
	padding:0 4px 0 4px;
	background:rgb(255,153,0);
	border-radius:8px;
	color:#fff;
}

.dash-board .modal-dialog{
	width: 900px;
}
.nopadingAndnomagin{
	padding:0px;
	margin:0px;
}

</style>	
<div class="tracking-index col2-layout">
<?php 
	echo $this->render('_leftmenu',['counter'=>$counter]);
?>



<div class="content-wrapper" >
<?php 
    $problemAccounts = ShopeeAccountsApiHelper::getUserAccountProblems($uid);
?>
    <!-- 账号异常提示区域 -->
	 <?php if(!empty($problemAccounts['order_retrieve_failed'])){?>
	<div class="alert alert-danger" role="alert" style="width:100%;">
	   <?php foreach ($problemAccounts['order_retrieve_failed'] as $detailAccounts):?>
		<span>您绑定的Shopee账号：<?=$detailAccounts['store_name'] ?> 自动同步订单失败！ 请联系客服。</span><br />
	   <?php endforeach;?>
	</div>
	<?php } ?>
	<?php if(!empty($SignShipWarningCount)){?>
	<div class="alert alert-danger" role="alert" style="width:100%;line-height: 18px;">
		<span>您绑定的Shopee账号有<?=$SignShipWarningCount?>张订单通知平台发货失败，请尽快确认！<a href="/order/shopee-order/list?order_sync_ship_status=F" >点击</a><br>
	</div>
	<?php } ?>

<!-- ----------------------------------已付款订单分类搜索 start--------------------------------------------------------------------------------------------- -->	
	<?php //echo OrderFrontHelper::displayOrderPaidProcessHtml($counter)?>
	
<!-- ----------------------------------已付款订单分类搜索 end--------------------------------------------------------------------------------------------- -->

<!-- ----------------------------------搜索区域 start--------------------------------------------------------------------------------------------- -->	
	<div>
		<form class="form-inline" id="form1" name="form1" action="/order/shopee-order/list" method="post">
		<input type="hidden" name ="menu_select" value="<?php echo isset($_REQUEST['menu_select'])?$_REQUEST['menu_select']:'';?>">
		<input type="hidden" name ="select_bar" value="<?php echo isset($_REQUEST['select_bar'])?$_REQUEST['select_bar']:'';?>">
		<input type="hidden" name ="order_status" value="<?php echo isset($_REQUEST['order_status'])?$_REQUEST['order_status']:'';?>">
		<input type="hidden" name ="exception_status" value="<?php echo isset($_REQUEST['exception_status'])?$_REQUEST['exception_status']:'';?>">
		<input type="hidden" name ="pay_order_type" value="<?php echo isset($_REQUEST['pay_order_type'])?$_REQUEST['pay_order_type']:'';?>">
		<input type="hidden" name ="is_merge" value="<?php echo isset($_REQUEST['is_merge'])?$_REQUEST['is_merge']:'';?>">
		<?=Html::hiddenInput('customsort', @$_REQUEST['customsort'],['id'=>'customsort']);?>
		<?=Html::hiddenInput('ordersorttype', @$_REQUEST['ordersorttype'],['id'=>'ordersorttype']);?>
		<!-- ----------------------------------第一行 --------------------------------------------------------------------------------------------- -->
		<div style="margin:10px 0px 0px 0px">
		<?php //echo Html::dropDownList('selleruserid',@$_REQUEST['selleruserid'],$selleruserids,['class'=>'iv-input','id'=>'selleruserid','style'=>'margin:0px;width:150px','prompt'=>'卖家账号'])?>
		
			<?php
			//店铺查找代码 S
			?>
			<input type="hidden" name ="selleruserid_combined" id="selleruserid_combined" value="<?php echo isset($_REQUEST['selleruserid_combined'])?$_REQUEST['selleruserid_combined']:'';?>">
			<?php
			$omsPlatformFinds = array();
// 			$omsPlatformFinds[] = array('event'=>'OrderCommon.order_platform_find(\'oms\',\'shopee\',\'\')','label'=>'全部');
			
			if(count($selleruserids) > 0){
				$selleruserids['select_shops_xlb'] = '选择账号';
	
				foreach ($selleruserids as $tmp_selleruserKey => $tmp_selleruserid){
					$omsPlatformFinds[] = array('event'=>'OrderCommon.order_platform_find(\'oms\',\'shopee\',\''.$tmp_selleruserKey.'\')','label'=>$tmp_selleruserid);
				}
				
				$pcCombination = OrderListV3Helper::getPlatformCommonCombination(array('type'=>'oms','platform'=>'shopee'));
				if(count($pcCombination) > 0){
					foreach ($pcCombination as $pcCombination_K => $pcCombination_V){
						$omsPlatformFinds[] = array('event'=>'OrderCommon.order_platform_find(\'oms\',\'shopee\',\''.'com-'.$pcCombination_K.'-com'.'\')',
							'label'=>'组合-'.$pcCombination_K, 'is_combined'=>true, 'combined_event'=>'OrderCommon.platformcommon_remove(this,\'oms\',\'shopee\',\''.$pcCombination_K.'\')');
					}
				}
			}
			echo OrderListV3Helper::getDropdownToggleHtml('卖家账号', $omsPlatformFinds);
			if(!empty($_REQUEST['selleruserid_combined'])){
				echo '<span onclick="OrderCommon.order_platform_find(\'oms\',\'shopee\',\'\')" class="glyphicon glyphicon-remove" style="cursor:pointer;font-size: 10px;line-height: 20px;color: red;margin-right:5px;" aria-hidden="true" data-toggle="tooltip" data-placement="top" data-html="true" title="" data-original-title="清空卖家账号查询条件"></span>';
			}
			//店铺查找代码 E
			?>
		
			<div class="input-group iv-input">
		        <?php $sel = [
		        	'order_source_order_id'=>'shopee订单号',
					'sku'=>'SKU',
					'tracknum'=>'物流号',
					'buyerid'=>'买家账号',
		        	'consignee'=>'买家姓名',
					'email'=>'买家Email',
					'order_id'=>'小老板单号',
					'root_sku'=>'配对SKU',
					'product_name'=>'产品标题',
				]?>
				<?=Html::dropDownList('keys',@$_REQUEST['keys'],$sel,['class'=>'iv-input','style'=>'width:140px;margin:0px','onchange'=>'OrderCommon.keys_change_find(this)'])?>
		      	<?=Html::textInput('searchval',@$_REQUEST['searchval'],['class'=>'iv-input','id'=>'num','style'=>'width:250px','placeholder'=>'多个请用空格分隔或Excel整列粘贴'])?>
		      	
		    </div>
		    
		    <?=Html::checkbox('fuzzy',@$_REQUEST['fuzzy'],['label'=>TranslateHelper::t('模糊搜索')])?>
		    
	    	
	    	<a id="simplesearch" href="#" style="font-size:12px;text-decoration:none;" onclick="mutisearch();">高级搜索<span class="glyphicon glyphicon-menu-down"></span></a>
			<?=Html::submitButton('搜索',['class'=>"iv-btn btn-search btn-spacing-middle",'id'=>'search'])?>
	    	

			<a id="sync-btn" href="sync-order-ready" target="_modal" title="同步订单" class="iv-btn btn-important" auto-size style="color:white; display: none; " btn-resolve="false" btn-reject="false">同步订单</a>
			
			<a target="_blank"  title="手工订单" class="iv-btn btn-important" href="/order/order/manual-order-box?platform=shopee" >手工订单</a>
			
			<!----------------------------------------------------------- 同步情况 ----------------------------------------------------------->
			<?php if (!empty($_REQUEST['menu_select']) && $_REQUEST['menu_select'] =='all'):?>
			<div class="pull-right" style="height: 40px; display: none; ">
				<?=Html::button(TranslateHelper::t('同步情况'),['class'=>"iv-btn btn-important",'onclick'=>"OrderCommon.showAccountSyncInfo(this);",'name'=>'btn_account_sync_info','data-url'=>'/order/shopee-order/order-sync-info'])?>
			</div>
			<?php endif;?>
			<!----------------------------------------------------------- 常用筛选 ----------------------------------------------------------->
			<div class="pull-right" style="height: 40px;display:none;">
				<?php
		    	if (!empty($counter['custom_condition'])){
		    		$sel_custom_condition = array_merge(['加载常用搜索'] , array_keys($counter['custom_condition']));
		    	}else{
		    		$sel_custom_condition =['0'=>'加载常用搜索'];
		    	}
		    	
		    	echo Html::dropDownList('sel_custom_condition',@$_REQUEST['sel_custom_condition'],$sel_custom_condition,['class'=>'iv-input'])?>
		    	<?=Html::button('保存为常用搜索',['class'=>"iv-btn btn-search",'onclick'=>"showCustomConditionDialog()",'name'=>'btn_save_custom_condition'])?>
			</div>
			
	    	<div class="mutisearch" <?php if ($showsearch!='1'){?>style="display: none;"<?php }?>>
	    	
			<!-- ----------------------------------第二行--------------------------------------------------------------------------------------------- -->
	    	<div style="margin:20px 0px 0px 0px">
			<div class="input-group"  name="div-select-nation"  data-role-id="0"  style='margin:0px'>
				<?=Html::textInput('country',@$_REQUEST['country'],['class'=>'iv-input','placeholder'=>'收件人国家','id'=>'country','style'=>'width:202px;margin:0px'])?>
			</div>
			
			<?php 
			// 物流商
			$carriersProviderList = CarrierOpenHelper::getOpenCarrierArr('2');
			
			echo Html::dropDownList('carrier_code',@$_REQUEST['carrier_code'],$carriersProviderList,['class'=>'iv-input','prompt'=>'物流商','id'=>'order_carrier_code','style'=>'width:200px;margin:0px'])
			?>

			<?php $carriers=CarrierApiHelper::getShippingServices()?>
			<?=Html::dropDownList('shipmethod',@$_REQUEST['shipmethod'],$carriers,['class'=>'iv-input','prompt'=>'运输服务','id'=>'shipmethod','style'=>'width:200px;margin:0px'])?>
			<?php $TrackerStatusList = [''=>'物流跟踪状态'];
			 	$tmpTrackerStatusList = Tracking::getChineseStatus('',true);
				$TrackerStatusList+= $tmpTrackerStatusList;
				echo Html::dropDownList('tracker_status',@$_REQUEST['tracker_status'],$TrackerStatusList,['class'=>'iv-input','style'=>'width:200px;margin:0px'])?>
			</div>

			<!-- ----------------------------------第三行--------------------------------------------------------------------------------------------- -->
			<div style="margin:20px 0px 0px 0px">
			<?=Html::dropDownList('order_status',@$_REQUEST['order_status'],$orderStatus21,['class'=>'iv-input','prompt'=>'小老板订单状态','id'=>'order_status','style'=>'width:200px;margin:0px'])?>
			<?=Html::dropDownList('order_source_status',@$_REQUEST['order_source_status'],ShopeeOrderHelper::$shopeeStatus,['class'=>'iv-input','prompt'=>'Shopee状态','id'=>'order_source_status','style'=>'width:200px;margin:0px'])?>
			
			 <?php $PayOrderTypeList = [''=>'已付款订单类型'];
				$PayOrderTypeList+=Odorder::$payOrderType ;
				$PayOrderTypeList[OdOrder::PAY_EXCEPTION] = '待合并';
				?>
			 <?=Html::dropDownList('pay_order_type',@$_REQUEST['pay_order_type'],$PayOrderTypeList,['class'=>'iv-input','style'=>'width:200px;margin:0px'])?>
			 
			<?php $reorderTypeList = [''=>'重新发货类型'];
			$reorderTypeList+=Odorder::$reorderType ;?>
			<?=Html::dropDownList('reorder_type',@$_REQUEST['reorder_type'],$reorderTypeList,['class'=>'iv-input','style'=>'width:200px;margin:0px'])?>
			
			 <?php 
			 /*
			 $exceptionStatusList = [''=>'异常状态'];
				$exceptionStatusList+= ['223'=>'待合并'] ;
				
				?>
			 <?=Html::dropDownList('exception_status',@$_REQUEST['exception_status'],$exceptionStatusList,['class'=>'iv-input','style'=>'width:200px;margin:0px'])
			 */
			 ?>
			 </div>
			 <!-- ----------------------------------第四行--------------------------------------------------------------------------------------------- -->
			 <div style="margin:20px 0px 0px 0px">
			<?php 
			$warehouses = InventoryApiHelper::getWarehouseIdNameMap(true); 
			if (count($warehouses)>1){
				echo Html::dropDownList('cangku',@$_REQUEST['cangku'],$warehouses,['class'=>'iv-input','prompt'=>'仓库','style'=>'width:200px;margin:0']);
				echo ' ';
			}
			$warehouses +=['-1'=>'未分配'];//table 中可能 有-1的值
			$orderCaptureList = ['N'=>'正常订单','Y'=>'手工订单'];
			echo Html::dropDownList('order_capture',@$_REQUEST['order_capture'],$orderCaptureList,['class'=>'iv-input','prompt'=>'全部']);
			?> 
			  
			 <?=Html::dropDownList('timetype',@$_REQUEST['timetype'],['soldtime'=>'售出日期','paidtime'=>'付款日期','printtime'=>'打单日期','shiptime'=>'发货日期'],['class'=>'iv-input'])?>
        	<?=Html::input('date','startdate',@$_REQUEST['startdate'],['class'=>'iv-input','style'=>'width:150px;margin:0px'])?>
        	至
			<?=Html::input('date','enddate',@$_REQUEST['enddate'],['class'=>'iv-input','style'=>'width:150px;margin:0px'])?>
			</div>
			<!-- ----------------------------------第五行--------------------------------------------------------------------------------------------- -->
			<div id="div_sys_tag" style="margin:20px 0px 0px 0px">
			<strong style="font-weight: bold;font-size:12px;">系统标签：</strong>
			<?php foreach (OrderTagHelper::$OrderSysTagMapping as $tag_code=> $label){
				echo Html::checkbox($tag_code,@$_REQUEST[$tag_code],['label'=>TranslateHelper::t($label)]);
			}
			//echo Html::checkbox('is_reverse',@$_REQUEST['is_reverse'],['label'=>TranslateHelper::t('取反')]);
			?>
			<!-- 
			<div class="btn-group" data-toggle="buttons">
			<?php foreach (OrderTagHelper::$OrderSysTagMapping as $tag_code=> $label){
				//echo Html::($tag_code,@$_REQUEST[$tag_code],['label'=>TranslateHelper::t($label)]);
				echo '<button type="button" class="btn btn-success btn-sm"><span class="glyphicon glyphicon-ok-sign" style="margin-right: 5px;"></span>'.TranslateHelper::t($label).'</button>';
				
			}
			
			//echo Html::checkbox('is_reverse',@$_REQUEST['is_reverse'],['label'=>TranslateHelper::t('取反')]);
			?>
			</div>
			-->
			</div>
			<!-- ----------------------------------第六行--------------------------------------------------------------------------------------------- -->
			<?php if (!empty($all_tag_list)):?>
			<div style="margin:20px 0px 0px 0px">
				<div class="pull-left">
				<strong style="font-weight: bold;font-size:12px;">自定义标签：</strong>
				</div>
				<div class="pull-left" style="height: 40px;">
				<?=Html::checkboxlist('sel_tag',@$_REQUEST['sel_tag'],$all_tag_list);?>
				</div>
			</div>
			<?php endif; // 自定义标签?>
			</div> 
			
			<!-- ----------------------------------第七行--------------------------------------------------------------------------------------------- -->
			
			<?php 
			if (@$_REQUEST['order_status'] != OdOrder::STATUS_CANCEL){
				\eagle\modules\order\helpers\OrderFrontHelper::displayOrderSyncShipStatusToolbar(@$_REQUEST['order_sync_ship_status'],'shopee');
			}
			?>
			<div style="margin:20px 0px 0px 0px">
				<strong style="font-weight: bold;font-size:12px;">排序方式：</strong>
				<?php
				if(empty($_REQUEST['customsort'])){
					$_REQUEST['customsort'] = 'soldtime';
				}
				
				foreach ($tmpCustomsort as $tag_code=> $label){
					echo "<a style='margin-right: 30px;' class=' ".($_REQUEST['customsort'] == $tag_code ? 'text-rever-important' : '')."' value='".$tag_code.
						"' sorttype='".@$_REQUEST['ordersorttype']."' onclick='OrderCommon.sortModeBtnClick(this)'>".$label.
						($_REQUEST['customsort'] == $tag_code ? " <span class='glyphicon glyphicon-sort-by-attributes".((empty($_REQUEST['ordersorttype']) || strtolower($_REQUEST['ordersorttype'])=='desc') ? '-alt' : '')."'></span>" : '').
						"</a>";
				}
				?>
			</div>
	    </div>
		</form>
	</div>
	<br><br>
<!-- ----------------------------------搜索区域 end--------------------------------------------------------------------------------------------- -->	
	
<!-- ----------------------------------列表 start--------------------------------------------------------------------------------------------- -->		
<div style="clear: both;">
		<form name="a" id="a" action="" method="post">
		<div class="pull-left" style="height: 40px;">
		<!--
		<?=Html::button('刷新左侧统计',['class'=>"iv-btn btn-refresh",'onclick'=>"refreshLeftMenu()",'name'=>'btn_refresh_left_menu'])?>
		<span qtipkey="oms_refresh_left_menu_shopee" class ="click-to-tip"></span>
		-->
		<?php 
		if (isset($_REQUEST['order_status']) && $_REQUEST['order_status'] =='200' ){
			$PayBtnHtml = Html::button('指定运输服务',['class'=>"iv-btn btn-important",'onclick'=>"orderCommonV3.doaction('changeWHSM');"])."&nbsp;";
			$PayBtnHtml .= Html::button('修改报关信息',['class'=>"iv-btn btn-important",'onclick'=>"orderCommonV3.doaction('changeItemDeclarationInfo');"]). "&nbsp;";
			$PayBtnHtml .= Html::button('移入发货中',['class'=>"iv-btn btn-important",'onclick'=>"orderCommonV3.doaction('signwaitsend');"]). "&nbsp;";
			$PayBtnHtml .=  '<span data-qtipkey="oms_batch_ship_pack" class ="click-to-tip"><img style="cursor:pointer;padding-top:8px;" width="16" src="/images/questionMark.png"></span>&nbsp;';
				
			if (in_array(@$_REQUEST['exception_status'], ['223'])){
				echo Html::button(TranslateHelper::t('合并订单'),['class'=>"iv-btn btn-important",'onclick'=>"orderCommonV3.doaction('mergeorder');"]);echo "&nbsp;";
				echo '<span data-qtipkey="oms_order_exception_pending_merge_merge" class ="click-to-tip"><img style="cursor:pointer;padding-top:8px;" width="16" src="/images/questionMark.png"></span>&nbsp;';
			
			}elseif (!empty($_REQUEST['is_merge'])){
				echo Html::button(TranslateHelper::t('取消合并'),['class'=>"iv-btn btn-important",'onclick'=>"orderCommonV3.doaction('cancelMergeOrder');"]).'&nbsp;';
			}
			
			echo $PayBtnHtml;
			
		}
		
		if (@$_REQUEST['pay_order_type'] == 'reorder' || in_array(@$_REQUEST['exception_status'], ['203','222','202']) ){
			//echo Html::checkbox( 'chk_refresh_force','',['label'=>TranslateHelper::t('强制重新检测')]);
		}
		?>
	
		<?php
			//echo Html::dropDownList('do','',$doarr,['onchange'=>"orderCommonV3.doaction2(this,'shopee');",'class'=>'iv-input do','style'=>'width:200px;margin:0px']);
			echo \eagle\modules\order\helpers\OrderListV3Helper::getDropdownToggleHtml('批量操作', $doarr, 'orderCommonV3.doaction3');
			
		?>
	
		<!--<?=Html::dropDownList('do','',$excelmodels,['onchange'=>"exportorder2(this,$(this).val());",'class'=>'iv-input do','style'=>'width:200px;margin:0px']);?>--> 
		<!--<?=Html::dropDownList('orderExcelprint','',['-1'=>'导出订单','0'=>'按勾选导出','1'=>'按所有页导出'],['onchange'=>"OrderCommon.orderExcelprint($(this).val())",'class'=>'iv-input do','style'=>'width:200px;margin:0px']);?>-->
		<?php
		$excelActionItems = array('0'=>array('event'=>'OrderCommon.orderExcelprint(0)','label'=>'按勾选导出'), '1'=>array('event'=>'OrderCommon.orderExcelprint(1)','label'=>'按所有页导出'));
		$excelDownListHtml = OrderListV3Helper::getDropdownToggleHtml('导出订单', $excelActionItems);
		echo $excelDownListHtml;
		?>	
		<?php $divTagHtml = "";
			$div_event_html = "";
		?>
		</div>
<!-- ----------------------------------导入跟踪号start--------------------------------------------------------------------------------------------- -->	
		<?php
			if(@$_REQUEST['order_status'] == OdOrder::STATUS_PAY){?>
				<div class="pull-right" style="height: 40px;">
					<button type="button" class="iv-btn btn-important" onclick="OrderCommon.importTrackNoBox('shopee')">导入跟踪号</button>	
				</div>
		<?php	}
		?>
<!-- ----------------------------------导入跟踪号end--------------------------------------------------------------------------------------------- -->			
		<br>
			<?php
			echo $this->render('../order/order_list_v3',[ 'carriers'=>$carriers, 'models'=>$models,'countrys'=>$countryArr,
				'warehouses'=>$warehouses, 'non17Track'=>$non17Track, 'tag_class_list'=>$tag_class_list,
				'current_order_status' => empty($_REQUEST['order_status']) ? '' : $_REQUEST['order_status'],
				'current_exception_status' => empty($_REQUEST['exception_status']) ? '' : $_REQUEST['exception_status'],
				'platform_type'=>'shopee']);
			?>
			
		</form>
		
<!-- ----------------------------------分页start--------------------------------------------------------------------------------------------- -->			
		<?php if($pages):?>
		<div id="pager-group">
		    <?= \eagle\widgets\SizePager::widget(['pagination'=>$pages , 'pageSizeOptions'=>array( 5 , 20 , 50 , 100 , 200 ) , 'class'=>'btn-group dropup']);?>
		    <div class="btn-group" style="width: 49.6%; text-align: right;">
		    	<?=\yii\widgets\LinkPager::widget(['pagination' => $pages,'options'=>['class'=>'pagination']]);?>
			</div>
			</div>
		<?php endif;?>
<!-- ----------------------------------分页end--------------------------------------------------------------------------------------------- -->	
		
</div>
<!-- ----------------------------------列表end--------------------------------------------------------------------------------------------- -->	
<?php if (@$_REQUEST['order_status'] == 200 ):?>
<div>
<ul style="line-height: 60px;">
	<li style="float: left;">你还可以查看以下处理阶段的订单：</li>
	<li style="float: left;"><?php echo OrderFrontHelper::displayOrderPaidProcessHtml($counter)?></li>
</ul>

</div>
<?php endif;?>
<div style="clear: both;"></div>
</div>
</div>
<div style="display: none;">
<?=$divTagHtml?>
<?=$div_event_html?>
<div id="div_ShippingServices"><?=Html::dropDownList('demo_shipping_method_code',@$order->default_shipping_method_code,CarrierApiHelper::getShippingServices())?></div>
</div>

<div id="oms_order_pending_check" style="display: none">
	<div>新同步订单的默认都为“待检测”的状态，用户需要都为新订单手动做一次检测订单来开始订单流程操作</div>
</div>
<div id="oms_order_reorder" style="display: none">
	<div>操作暂停订单、缺货订单、已出库订单、取消订单和废弃订单的【重新发货】功能的订单都会汇总到当前状态，集中处理</div>
</div>
<div id="oms_order_exception" style="display: none">
	<div>汇总【检测订单】操作和该页面的【自动检测】操作中发现的有异常的订单，该异常订单都是需要用户处理后才能发货的订单。<br>异常详情参看详情介绍：<br><a href="<?=SysBaseInfoHelper::getHelpdocumentUrl('faq_66.html')?>" target="_blank"><?=SysBaseInfoHelper::getHelpdocumentUrl('faq_66.html')?></a></div>
</div>
<div id="oms_order_can_ship" style="display: none">
	<div>汇总所有没有异常可以直接发货的订单</div>
</div>
<div id="oms_order_status_check" style="display: none">
	<div>更新所有已付款订单的状态。<br>监测订单主要检测的内容参看详情介绍：<br><a href="<?=SysBaseInfoHelper::getHelpdocumentUrl('faq_61.html')?>" target="_blank"><?=SysBaseInfoHelper::getHelpdocumentUrl('faq_61.html')?></a></div>
</div>
<div id="oms_batch_ship_pack" style="display: none">
	<div>批量提交订单到 发货 物流模块<br><b style="font-weight: 600;color:red">该操作后，订单商品将不会出现于采购模块的见单采购中！</b><br>已付款的订单可以被多选，批量进行物流以及发货，请到 菜单顶部的 <b style="font-weight: 600;color:blue">物流</b>模块 或者 <b style="font-weight: 600;color:blue">发货</b>模块，进行物流商的运单申请 以及 批量拣货 配货 打印标签。<br>如果有部分订单处于 <b style="font-weight: 600;color:red">异常</b>状态的，建议点击右边的<b style="font-weight: 600;color:red">有异常</b>按钮，对异常订单进行处理，然后刷新该页面.</div>
</div>
<div id="oms_order_exception_pending_merge" style="display: none">
	<div>订单的收件人和收件地址都相同的订单可以合并。<br>可参看详情介绍：<br><a href="<?=SysBaseInfoHelper::getHelpdocumentUrl('faq_62.html')?>" target="_blank"><?=SysBaseInfoHelper::getHelpdocumentUrl('faq_62.html')?></a></div>
</div>
<div id="oms_order_exception_pending_merge_merge" style="display: none">
	<div>选中订单进行合并，合并完成后需要再检测订单是否有其他异常</div>
</div>
<div id="ms_order_exception_pending_merge_skip" style="display: none">
	<div>选中订单被标记为不可合并，标记完成后需要再检测订单是否有其他异常</div>
</div>
<div id="oms_order_exception_sku_not_exist" style="display: none">
	<div>订单商品的SKU不存在于商品库中<br>可参看详情介绍：<br><a href="<?=SysBaseInfoHelper::getHelpdocumentUrl('faq_63.html')?>" target="_blank"><?=SysBaseInfoHelper::getHelpdocumentUrl('faq_63.html')?></a></div>
</div>
<div id="oms_order_exception_sku_not_exist_create" style="display: none">
	<div>汇总选中订单不存在的商品，然后在生成sku的页面填写相关的商品信息就能生成小老板商品库</div>
</div>
<div id="oms_order_exception_no_warehouse" style="display: none">
	<div>订单没有符合仓库分配规则<br>可参看详情介绍：<br><a href="<?=SysBaseInfoHelper::getHelpdocumentUrl('faq_64.html')?>" target="_blank"><?=SysBaseInfoHelper::getHelpdocumentUrl('faq_64.html')?></a></div>
</div>
<div id="oms_order_exception_no_warehouse_assign" style="display: none">
	<div>临时方案：给选中的订单分配仓库。<br>提示：分配库存的时候可以指定运输服务方式，节约时间</div>
</div>
<div id="oms_order_exception_out_of_stock" style="display: none">
	<div>订单中的SKU库存不足<br>可参看详情介绍：<br><a href="<?=SysBaseInfoHelper::getHelpdocumentUrl('faq_65.html')?>" target="_blank"><?=SysBaseInfoHelper::getHelpdocumentUrl('faq_65.html')?></a></div>
</div>
<div id="oms_order_exception_out_of_stock_manage" style="display: none">
	<div>给选中的订单快速录入入库单 ，保证高效地完成库存管理</div>
</div>
<div id="oms_order_exception_out_of_stock_mark" style="display: none">
	<div>把选中的订单移动到缺货订单中，保证不影响其他订单的发货</div>
</div>
<div id="oms_order_exception_no_shipment" style="display: none">
	<div>订单没有匹配到适应的运输服务，需要修该运输服务匹配规则。<br>可参看详情介绍：<br><a href="<?=SysBaseInfoHelper::getHelpdocumentUrl('faq_67.html')?>" target="_blank"><?=SysBaseInfoHelper::getHelpdocumentUrl('faq_67.html')?></a></div>
</div>
<div id="oms_order_exception_no_shipment_assign" style="display: none">
	<div>给选中的订单分配运输方式,<br>请注意这里也可以修改指定仓库，若指定的产品库存不足的话，流程会回到库存不足的异常</div>
</div>

<div><input type='hidden' id='search_condition' value='<?=$search_condition ?>'>
	    	 <input type='hidden' id='search_count' value='<?=$search_count ?>'></div>

<script>
//导出订单
function exportorder2(obj,type){
	var val = $(obj).val();
	if (val != ''){
		exportorder(type);
		$(obj).val('');
	}
}
function exportorder(type){
	if(type==""){
		bootbox.alert("请选择您的操作");return false;
    }
	if($('.ck:checked').length==0&&type!=''){
		bootbox.alert("请选择要操作的订单");return false;
    }
    var idstr='';
	$('input[name="order_id[]"]:checked').each(function(){
		idstr+=','+$(this).val();
	});
	window.open('<?=Url::to(['/order/excel/export-excel'])?>'+'?orderids='+idstr+'&excelmodelid='+type);
}

//高级搜索
function mutisearch(){
	var status = $('.mutisearch').is(':hidden');
	if(status == true){
		//未展开
		$('.mutisearch').show();
		$('#simplesearch').html('收起<span class="glyphicon glyphicon-menu-up"></span>');
		return false;
	}else{
		$('.mutisearch').hide();
		$('#simplesearch').html('高级搜索<span class="glyphicon glyphicon-menu-down"></span>');
		return false;
	}
	
}

function showCustomConditionDialog(){
	var html = '<label>'+Translator.t('搜索条件名称')+'</label><?=Html::textInput('filter_name',@$_REQUEST['filter_name'],['class'=>'iv-input','id'=>'filter_name'])?>';
	var modalbox = bootbox.dialog({
		title: Translator.t("保存为常用搜索条件"),
		className: "", 
		message: html,
		buttons:{
			Ok: {  
				label: Translator.t("保存"),  
				className: "btn-primary",  
				callback: function () { 
					if ($('#filter_name').val() == "" ){
						bootbox.alert(Translator.t('请输入搜索条件名称!'));
						return false;
					}

					saveCustomCondition(modalbox , $('#filter_name').val() );
					return false;
					//result = ListTracking.AppendRemark(track_no , $('#filter_name').val());
				}
			}, 
			Cancel: {  
				label: Translator.t("返回"),  
				className: "btn-default",  
				callback: function () {  
				}
			}, 
		}
	});	
}

function saveCustomCondition(modalbox , filter_name){
	$.ajax({
		type: "POST",
			dataType: 'json',
			url:'/order/shopee-order/append-custom-condition?custom_name='+filter_name, 
			data: $('#form1').serialize(),
			success: function (result) {
				if (result.success == false){
					bootbox.alert(result.message);	
					return false
				}
				modalbox.modal('hide');
				return true;
			},
			error: function(){
				bootbox.alert("Internal Error");
				return false;
			}
	});

	
}

function refreshLeftMenu(){
	$.ajax({
		type: "POST",
			dataType: 'text',
			url:'/order/shopee-order/clear-left-menu-cache', 
			success: function (result) {
				location.reload();
			},
			error: function(){
				bootbox.alert("Internal Error");
				return false;
			}
	});
}

</script>

<?php
 echo eagle\modules\carrier\helpers\CarrierOpenHelper::getCainiaoPrintModalHtml();
?>