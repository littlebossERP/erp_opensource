<?php
use yii\helpers\Html;
use yii\widgets\LinkPager;
use yii\helpers\Url;
use eagle\modules\order\models\OdOrder;
use eagle\modules\carrier\apihelpers\CarrierApiHelper;
use eagle\modules\inventory\helpers\InventoryApiHelper;
use eagle\modules\order\helpers\OrderTagHelper;
use eagle\modules\carrier\helpers\CarrierHelper;
use eagle\widgets\SizePager;
use eagle\models\SaasPriceministerUser;
use eagle\models\catalog\Product;
use eagle\modules\util\helpers\StandardConst;
use eagle\modules\order\helpers\PriceministerOrderInterface;
use eagle\modules\tracking\helpers\TrackingApiHelper;
use eagle\modules\tracking\helpers\TrackingHelper;
use eagle\modules\order\helpers\PriceministerOrderHelper;
use eagle\modules\tracking\models\Tracking;
use eagle\modules\util\helpers\ImageCacherHelper;
use eagle\modules\carrier\helpers\CarrierOpenHelper;
use eagle\modules\util\helpers\TranslateHelper;
use eagle\modules\order\helpers\OrderFrontHelper;
use eagle\modules\util\helpers\ConfigHelper;
use eagle\modules\message\apihelpers\MessageApiHelper;
use eagle\modules\order\helpers\OrderHelper;
use eagle\modules\tracking\helpers\CarrierTypeOfTrackNumber;
use eagle\modules\util\helpers\SysBaseInfoHelper;
use eagle\modules\order\helpers\OrderListV3Helper;
use eagle\modules\util\helpers\RedisHelper;

$this->registerCssFile(\Yii::getAlias('@web').'/css/order/order.css');
$this->registerJsFile(\Yii::getAlias('@web')."/js/project/order/OrderTag.js", ['depends' => ['yii\web\JqueryAsset']]);
$this->registerJsFile(\Yii::getAlias('@web')."/js/project/order/orderOrderList.js", ['depends' => ['yii\web\JqueryAsset']]);
$this->registerJsFile(\Yii::getAlias('@web')."/js/origin_ajaxfileupload.js", ['depends' => ['yii\web\JqueryAsset']]);
$this->registerJsFile(\Yii::getAlias('@web')."/js/project/order/orderCommon.js?v=".OrderHelper::$OrderCommonJSVersion, ['depends' => ['yii\web\JqueryAsset']]);
$this->registerJsFile(\Yii::getAlias('@web')."/js/project/order/priceministerOrder/priceministerOrderList.js", ['depends' => ['yii\web\JqueryAsset']]);
$this->registerJs("OrderTag.TagClassList=".json_encode($tag_class_list).";" , \yii\web\View::POS_READY);
$this->registerJs("OrderList.initClickTip();" , \yii\web\View::POS_READY);

$this->registerCssFile(\Yii::getAlias('@web')."/css/message/customer_message.css");
$this->registerJsFile(\Yii::getAlias('@web')."/js/project/message/customer_message.js", ['depends' => ['yii\web\JqueryAsset']]);
$this->registerJsFile(\Yii::getAlias('@web')."/js/project/order/priceministerOrder/priceminister_manual_sync.js", ['depends' => ['yii\web\JqueryAsset','eagle\assets\PublicAsset']]);


$uid = \Yii::$app->user->id;

//$next_show = \Yii::$app->redis->hget('PriceministerOms_DashBoard',"user_$uid".".next_show");
$next_show = RedisHelper::RedisGet('PriceministerOms_DashBoard',"user_$uid".".next_show");
$show=true;
if(!empty($next_show)){
	if(time()<strtotime($next_show))
		$show=false;
}
if(!empty($_REQUEST))
	$show=false;

/*
######################################	重要提示展示start	#################################################################
$important_change_tip_show = false;
//$important_change_tip_times= \Yii::$app->redis->hget('PriceministerOms_VerChangeTip',"user_$uid".".oms-2-1");//redis记录根据需要变换
$important_change_tip_times = RedisHelper::RedisGet('PriceministerOms_VerChangeTip',"user_$uid".".oms-2-1");
if(empty($important_change_tip_times))
	$important_change_tip_times = 0;
if($important_change_tip_times<3){
	$important_change_tip_show = true;
}
if($important_change_tip_show){
	$showDashBoard = false;
	$this->registerJs("showImportantChangeTip();" , \yii\web\View::POS_READY);
	$important_change_tip_times = (int)$important_change_tip_times+1;
	//\Yii::$app->redis->hset('PriceministerOms_VerChangeTip',"user_$uid".".oms-2-1",$important_change_tip_times);;
	RedisHelper::RedisSet('PriceministerOms_VerChangeTip',"user_$uid".".oms-2-1",$important_change_tip_times);
}
#####################################	重要提示展示end		#################################################################
*/

if($show)
	$this->registerJs("showDashBoard(1);" , \yii\web\View::POS_READY);

$this->registerJs("$('.prod_img').popover();" , \yii\web\View::POS_READY);
$this->registerJs("$('.icon-ignore_search').popover();" , \yii\web\View::POS_READY);

$orderStatus21 = OdOrder::getOrderStatus('oms21'); // oms 2.1 的订单状态
$pm_source_status_mapping = PriceministerOrderHelper::$orderStatus;

$non17Track = CarrierTypeOfTrackNumber::getNon17TrackExpressCode();

$tmpCustomsort = Odorder::$customsort;

//合并订单显示
$showMergeOrder = 0;
if (200 == @$_REQUEST['order_status'] && 223 == @$_REQUEST['exception_status']){
	$this->registerJs("OrderCommon.showMergeRow();" , \yii\web\View::POS_READY);
	$nowMd5 = "";
	$showMergeOrder = 1;
}

$this->registerJsFile(\Yii::getAlias('@web')."/js/project/order/orderListV3.js?v=".\eagle\modules\order\helpers\OrderListV3Helper::$OrderCommonJSV3, ['depends' => ['yii\web\JqueryAsset']]);
 
?>

<script type="text/javascript" src="//www.17track.net/externalcall.js"></script>

<style>
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
height: 35px;
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
	background-position:-200px -10px;
}
.text-invalid{
	color: #8a6d3b;
	text-decoration: line-through;
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
.pm_fbc_inco{
	width:15px;
	height:15px;
	background:url("/images/priceminister/clogpicto.jpg") no-repeat;
	display: block;
    background-size: 15px;
	float:left;
}
#dash-board-enter{
	position: fixed;
	bottom: 30px;
	left: 0px;
	width: 34px;
	height: 56px;
	padding: 3px;
    margin-bottom: 20px;
    border: 1px solid transparent;
    border-radius: 0px 5px 5px 0px;	
	cursor: pointer;
}
#pm-oms-reminder-content{
	left: 0px;
	padding: 5px;
    border: 2px solid transparent;
    border-radius: 5px;
	float: left;
    width: 100%;
	padding-bottom: 10px;
}
#pm-oms-reminder-close{
	-webkit-appearance: none;
    padding: 0;
    cursor: pointer;
    background: transparent;
    border: 0;
	color: #609ec4;
	float: right;
    font-size: 21px;
    font-weight: bold;
    line-height: 1;
    text-shadow: 0 1px 0 #fff;
}
#pm-oms-reminder-close-day{
	-webkit-appearance: none;
    padding: 0;
    cursor: pointer;
    background: transparent;
    border: 0;
	color: #609ec4;
	float: right;
    font-size: 12px;
    font-weight: bold;
    line-height: 1;
    text-shadow: 0 1px 0 #fff;
}
.pm-oms-weird-status-wfs{
	background: url(/images/priceminister/priceminister_icon.png) no-repeat -1px -1627px;
    background-size: 100%;
    float: left;
    width: 18px;
    height: 18px;
}
td .popover{
	max-width: inherit;
    max-height: inherit;
}
.popover{
	min-width: 200px;
}
.text-success{
	color: #2ecc71!important;
}
.text-warning{
	color: #8a6d3b!important;
}
.iv-btn.btn-important{
	padding-right:15px!important;
}
</style>	
<div class="tracking-index col2-layout">
<?=$this->render('_leftmenu',['counter'=>$counter]);?>

<div class="hidden" id="dash-board-enter" onclick="showDashBoard(0)" style="background-color:#374655;color: white;" title="展开dash-board">展开订单监测面板</div>

<div class="content-wrapper" >
	<?php $autoAccept = ConfigHelper::getConfig("PriceministerOrder/AutoAccept",'NO_CACHE');?>
	
	<div style="width:100%;display:inline-block">
		<div style="font-size:14px;padding:5px 5px;float:left;margin:0px;" class="alert alert-success" role="alert">
			<label>是否开启自动接受订单(不包括讨价单)：</label>
			<label for="auto_accept_Y">是</label><input type="radio" name="auto_accept" id="auto_accept_Y" value="true" <?=(!empty($autoAccept) && $autoAccept=='true')?'checked':''?> ><span style="margin:0px 5px;"></span>
			<label for="auto_accept_N">否</label><input type="radio" name="auto_accept" id="auto_accept_N" value="false" <?=(empty($autoAccept) || $autoAccept=='false')?'checked':''?>><span style="margin:0px 5px;"></span>
			<button type="button" onclick="setAutoAccept()" class="btn-xs btn-primary">设置</button>
			<span qtipkey="pm_auto_accept_order"></span>
		</div>
	</div>
	
	<?php $problemAccounts = PriceministerOrderInterface::getUserAccountProblems($uid); ?>
	<?php if(!empty($problemAccounts['token_expired'])){ $problemAccountNames=[];?>
	<?php foreach ($problemAccounts['token_expired'] as $account){
		$problemAccountNames[] = $account['store_name'];
	}?>
	<!-- 账号异常提示区域 -->
	<div class="alert alert-danger" role="alert" style="width:100%;">
		<span>您绑定的Priceminister账号：<?=implode(' , ', $problemAccountNames) ?> 的账号或token错误！<br>请检查您的绑定信息是否有误。</span>
	</div>
	<?php } ?>
	
	<!-- oms 2.1 nav start  -->
	<!-- 
	<?php 
	if (in_array(@$_REQUEST['order_status'], [OdOrder::STATUS_NOPAY, OdOrder::STATUS_PAY  ,OdOrder::STATUS_WAITSEND , OdOrder::STATUS_SHIPPED]) ){
		echo $order_nav_html;
	}
	?>
	 -->
	<!-- oms 2.1 nav end  -->
	
		
<!-- ----------------------------------已付款订单分类筛选 start--------------------------------------------------------------------------------------------- -->	
	<?php 
		if (@$_REQUEST['order_status'] == 200 || in_array(@$_REQUEST['exception_status'], ['0','210','203','222','202','223','299']) ){
			echo '<ul class="clearfix"><li style="float: left;line-height: 22px;">订单类型：</li><li style="float: left;line-height: 22px;">';
			echo OrderFrontHelper::displayOrderPaidProcessHtml($counter,'priceminister',[(string)OdOrder::PAY_PENDING]);
			echo '</li><li class="clear-both"></li></ul>';
		}
	?>
<!-- ----------------------------------已付款订单分类筛选 end--------------------------------------------------------------------------------------------- -->
	
	<!-- ----------------------------------搜索区域 start--------------------------------------------------------------------------------------------- -->	
	<div>
		<form class="form-inline" id="form1" name="form1" action="/order/priceminister-order/list" method="post">
		<input type="hidden" name ="select_bar" value="<?php echo isset($_REQUEST['select_bar'])?$_REQUEST['select_bar']:'';?>">
		<input type="hidden" name ="order_status" value="<?php echo isset($_REQUEST['order_status'])?$_REQUEST['order_status']:'';?>">
		<input type="hidden" name ="exception_status" value="<?php echo isset($_REQUEST['exception_status'])?$_REQUEST['exception_status']:'';?>">
		<input type="hidden" name ="pay_order_type" value="<?php echo isset($_REQUEST['pay_order_type'])?$_REQUEST['pay_order_type']:'';?>">
		<input type="hidden" name ="is_merge" value="<?php echo isset($_REQUEST['is_merge'])?$_REQUEST['is_merge']:'';?>">
		<?=Html::hiddenInput('customsort', @$_REQUEST['customsort'],['id'=>'customsort']);?>
		<?=Html::hiddenInput('ordersorttype', @$_REQUEST['ordersorttype'],['id'=>'ordersorttype']);?>
		<!-- ----------------------------------第一行 --------------------------------------------------------------------------------------------- -->
		<div style="margin:10px 0px 0px 0px">
		<?php //echo Html::dropDownList('selleruserid',@$_REQUEST['selleruserid'],$priceministerUsersDropdownList,['class'=>'iv-input','id'=>'selleruserid','style'=>'margin:0px;width:150px','prompt'=>'卖家账号'])?>
			<?php
			//店铺查找代码 S
			?>
			<input type="hidden" name ="selleruserid_combined" id="selleruserid_combined" value="<?php echo isset($_REQUEST['selleruserid_combined'])?$_REQUEST['selleruserid_combined']:'';?>">
			<?php
			$omsPlatformFinds = array();
// 			$omsPlatformFinds[] = array('event'=>'OrderCommon.order_platform_find(\'oms\',\'priceminister\',\'\')','label'=>'全部');
			
			if(count($priceministerUsersDropdownList) > 0){
				$priceministerUsersDropdownList['select_shops_xlb'] = '选择账号';
	
				foreach ($priceministerUsersDropdownList as $tmp_selleruserKey => $tmp_selleruserid){
					$omsPlatformFinds[] = array('event'=>'OrderCommon.order_platform_find(\'oms\',\'priceminister\',\''.$tmp_selleruserKey.'\')','label'=>$tmp_selleruserid);
				}
				
				$pcCombination = OrderListV3Helper::getPlatformCommonCombination(array('type'=>'oms','platform'=>'priceminister'));
				if(count($pcCombination) > 0){
					foreach ($pcCombination as $pcCombination_K => $pcCombination_V){
						$omsPlatformFinds[] = array('event'=>'OrderCommon.order_platform_find(\'oms\',\'priceminister\',\''.'com-'.$pcCombination_K.'-com'.'\')',
							'label'=>'组合-'.$pcCombination_K, 'is_combined'=>true, 'combined_event'=>'OrderCommon.platformcommon_remove(this,\'oms\',\'priceminister\',\''.$pcCombination_K.'\')');
					}
				}
			}
			echo OrderListV3Helper::getDropdownToggleHtml('卖家账号', $omsPlatformFinds);
			if(!empty($_REQUEST['selleruserid_combined'])){
				echo '<span onclick="OrderCommon.order_platform_find(\'oms\',\'priceminister\',\'\')" class="glyphicon glyphicon-remove" style="cursor:pointer;font-size: 10px;line-height: 20px;color: red;margin-right:5px;" aria-hidden="true" data-toggle="tooltip" data-placement="top" data-html="true" title="" data-original-title="清空卖家账号查询条件"></span>';
			}
			//店铺查找代码 E
			?>
			
			
			<div class="input-group iv-input">
		        <?php $sel = [
		        	'order_source_order_id'=>'PM订单号',
					'sku'=>'SKU',
					'tracknum'=>'物流号',
					'buyerid'=>'买家账号',
		        	'consignee'=>'买家姓名',
					'order_id'=>'小老板单号',
					'root_sku'=>'配对SKU',
					'product_name'=>'产品标题',
				]?>
				<?=Html::dropDownList('keys',@$_REQUEST['keys'],$sel,['class'=>'iv-input','style'=>'width:140px;margin:0px','onchange'=>'OrderCommon.keys_change_find(this)'])?>
		      	<?=Html::textInput('searchval',@$_REQUEST['searchval'],['class'=>'iv-input','id'=>'num','style'=>'width:250px','placeholder'=>'多个请用空格分隔或Excel整列粘贴'])?>
		      	
		    </div>
		    <?=Html::checkbox('fuzzy',@$_REQUEST['fuzzy'],['label'=>TranslateHelper::t('模糊搜索')])?>
		    <?=Html::submitButton('搜索',['class'=>"iv-btn btn-search btn-spacing-middle",'id'=>'search'])?>
		    <!-- 
	    	<?=Html::button('重置',['class'=>"iv-btn btn-search btn-spacing-middle",'onclick'=>"javascript:cleform();"])?>
			 -->
	    	<a id="simplesearch" href="#" style="font-size:12px;text-decoration:none;" onclick="mutisearch();">高级搜索<span class="glyphicon glyphicon-menu-down"></span></a>	 
	    	<a target="_blank" title="手工订单" class="iv-btn btn-important" href="/order/order/manual-order-box?platform=priceminister">手工订单</a>
	    	
	    	<!----------------------------------------------------------- 手工同步  ----------------------------------------------------------->
	    	<div style="display: inline-block;">
	    		<a id="sync-btn" href="sync-order-ready" target="_modal" title="拉取订单" class="iv-btn btn-important" auto-size style="color:white;" btn-resolve="false" btn-reject="false">同步订单</a>
	    		<span qtipkey="pm_manual-sync-order"></span>
	    	</div>
			
	    	<!----------------------------------------------------------- 同步情况 ----------------------------------------------------------->
			<?php if (!empty($_REQUEST['menu_select']) && $_REQUEST['menu_select'] =='all'):?>
			<div class="pull-right" style="height: 40px;">
				<?=Html::button(TranslateHelper::t('同步情况'),['class'=>"iv-btn btn-important",'onclick'=>"OrderCommon.showAccountSyncInfo(this);",'name'=>'btn_account_sync_info','data-url'=>'/order/priceminister-order/order-sync-info'])?>
			</div>
			<?php endif;?>
			
	    	<?php
	    	/*
	    	if (!empty($counter['custom_condition'])){
	    		$sel_custom_condition = array_merge(['加载常用筛选'] , array_keys($counter['custom_condition']));
	    	}else{
	    		$sel_custom_condition =['0'=>'加载常用筛选'];
	    	}
	    	
	    	echo Html::dropDownList('sel_custom_condition',@$_REQUEST['sel_custom_condition'],$sel_custom_condition,['class'=>'iv-input'])?>
	    	<?=Html::button('保存为常用筛选',['class'=>"iv-btn btn-search",'onclick'=>"showCustomConditionDialog()",'name'=>'btn_save_custom_condition'])
	    	*/
	    	?>
	    	<div class="mutisearch" <?php if ($showsearch!='1'){?>style="display: none;"<?php }?>>
			<!-- ----------------------------------第二行--------------------------------------------------------------------------------------------- -->
	    	<div style="margin:20px 0px 0px 0px">
			<?php $warehouses = InventoryApiHelper::getWarehouseIdNameMap(); $warehouses +=['-1'=>'未分配'];?>
			<?=Html::dropDownList('cangku',@$_REQUEST['cangku'],$warehouses,['class'=>'iv-input','prompt'=>'仓库','style'=>'width:200px;margin:0px'])?>
			<?php echo ' '; ?>
			<?php 
			// 物流商
			$carriersProviderList = CarrierOpenHelper::getOpenCarrierArr('2');
			echo Html::dropDownList('carrier_code',@$_REQUEST['carrier_code'],$carriersProviderList,['class'=>'iv-input','prompt'=>'物流商','id'=>'order_carrier_code','style'=>'width:200px;margin:0px'])
			?>
			<?php $carriers=CarrierApiHelper::getShippingServices()?>
			<?=Html::dropDownList('shipmethod',@$_REQUEST['shipmethod'],$carriers,['class'=>'iv-input','prompt'=>'运输服务','id'=>'shipmethod','style'=>'width:200px;margin:0px'])?>
			<?php //echo Html::dropDownList('profit_calculated',@$_REQUEST['profit_calculated'],[2=>'未计算',1=>'已计算'],['class'=>'iv-input','prompt'=>'是否计算过利润','id'=>'profit_calculated']); ?>
			<?=Html::dropDownList('order_capture',@$_REQUEST['order_capture'],['N'=>'普通订单','Y'=>'手工订单'],['class'=>'iv-input','prompt'=>'订单类型','id'=>'order_capture','style'=>'width:200px;margin:0px'])?>
			<?=Html::dropDownList('country',@$_REQUEST['country'],$countryArr,['class'=>'iv-input','prompt'=>'国家','id'=>'country','style'=>'width:200px;margin:0px'])?>
			</div>
			<!-- ----------------------------------第三行--------------------------------------------------------------------------------------------- -->
			<div style="margin:20px 0px 0px 0px">
			<?=Html::dropDownList('order_status',@$_REQUEST['order_status'],$orderStatus21,['class'=>'iv-input','prompt'=>'小老板订单状态','id'=>'order_status','style'=>'width:200px;margin:0px'])?>
			<?=Html::dropDownList('order_source_status',@$_REQUEST['order_source_status'],$pm_source_status_mapping,['class'=>'iv-input','prompt'=>'PM原始状态','id'=>'order_source_status','style'=>'width:200px;margin:0px'])?>
			
			 <?php $PayOrderTypeList = [''=>'已付款订单类型'];
				$PayOrderTypeList+=Odorder::$payOrderType ;?>
			 <?=Html::dropDownList('pay_order_type',@$_REQUEST['pay_order_type'],$PayOrderTypeList,['class'=>'iv-input','style'=>'width:200px;margin:0px'])?>
			 
			<?php $reorderTypeList = [''=>'重新发货类型'];
			$reorderTypeList+=Odorder::$reorderType ;?>
			<?=Html::dropDownList('reorder_type',@$_REQUEST['reorder_type'],$reorderTypeList,['class'=>'iv-input','style'=>'width:200px;margin:0px'])?>
			
			 <?php $exceptionStatusList = [''=>'异常状态'];
				$exceptionStatusList+=Odorder::$exceptionstatus ;
				unset($exceptionStatusList['201']);//有留言
				unset($exceptionStatusList['299']);//可发货
				//$weirdstatus=[];
			 ?>
			 <?=Html::dropDownList('exception_status',@$_REQUEST['exception_status'],$exceptionStatusList,['class'=>'iv-input','style'=>'width:200px;margin:0px'])?>
			 <?php //echo Html::dropDownList('weird_status',@$_REQUEST['weird_status'],$weirdstatus,['class'=>'iv-input','prompt'=>'操作异常标签','id'=>'weird_status','style'=>'width:250px;margin:0px']); ?>
			 </div>
			 <!-- ----------------------------------第四行--------------------------------------------------------------------------------------------- -->
			 <div style="margin:20px 0px 0px 0px">
			 <?php $TrackerStatusList = [''=>'物流跟踪状态'];
			 	$tmpTrackerStatusList = Tracking::getChineseStatus('',true);
				$TrackerStatusList+= $tmpTrackerStatusList;?>
			 <?=Html::dropDownList('tracker_status',@$_REQUEST['tracker_status'],$TrackerStatusList,['class'=>'iv-input','style'=>'width:200px;margin:0px'])?>
			 
			 
			 <?=Html::dropDownList('timetype',@$_REQUEST['timetype'],['soldtime'=>'售出日期','paidtime'=>'付款日期','printtime'=>'打单日期',/*'shiptime'=>'发货日期'*/],['class'=>'iv-input'])?>
        	<?=Html::input('date','startdate',@$_REQUEST['startdate'],['class'=>'iv-input','style'=>'width:150px;margin:0px'])?>
        	至
			<?=Html::input('date','enddate',@$_REQUEST['enddate'],['class'=>'iv-input','style'=>'width:150px;margin:0px'])?>
			</div>
			<!-- ----------------------------------第五行--------------------------------------------------------------------------------------------- -->
			<div style="margin:20px 0px 0px 0px;display: inline-block;width:100%">
			<strong style="font-weight: bold;font-size:12px;">系统标签：</strong>
			<?php foreach (OrderTagHelper::$OrderSysTagMapping as $tag_code=> $label){
				echo Html::checkbox($tag_code,@$_REQUEST[$tag_code],['label'=>TranslateHelper::t($label)]);
			}
			echo Html::checkbox('is_reverse',@$_REQUEST['is_reverse'],['label'=>TranslateHelper::t('取反')]);
			?>
			</div>
			<!-- ----------------------------------第六行--------------------------------------------------------------------------------------------- -->
			<div style="margin:20px 0px 0px 0px;display:inline-block;width:100%">

			<strong style="font-weight: bold;font-size:12px;float:left;margin:5px 0px 0px 0px;">自定义标签：</strong>


			<?=Html::checkboxlist('sel_tag',@$_REQUEST['sel_tag'],$all_tag_list);?>

			</div>
			</div> 
			<!-- ---------------------------------- 虚拟发货 状态--------------------------------------------------------------------------------------------- -->
			
			<?php 
			if (@$_REQUEST['order_status'] != OdOrder::STATUS_CANCEL){
				\eagle\modules\order\helpers\OrderFrontHelper::displayOrderSyncShipStatusToolbar(@$_REQUEST['order_sync_ship_status'],'priceminister');
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

	<br>
	<div style="">
		<form name="a" id="a" action="" method="post">
		<?php 
		/*
		$doarr=[
			''=>'批量操作',
			'checkorder'=>'检查订单',
			'signshipped'=>'priceminister标记发货',
			'mergeorder'=>'合并已付款订单',
			'changeshipmethod'=>'更改物流方式',
			//'getEmail'=>'获取订单收件人邮箱',
		];
		*/
		/* if (isset($_REQUEST['order_status'])&&$_REQUEST['order_status']>='300'){
			unset($doarr['signshipped']);
		} */
		?>
		<!-- 
		<div class="col-md-2">
		<?//=Html::dropDownList('do','',$doarr,['onchange'=>"doaction($(this).val());",'class'=>'form-control input-sm do']);?> 
		</div>
		 -->
		<?php if (isset($_REQUEST['order_status'])&&$_REQUEST['order_status']<'300'):?>
 		<!-- 
 		<div class="col-md-2" style="">
		<?php 
		/*
		$doCarrier=[
			''=>'发货处理',
			'getorderno'=>'提交物流',
			'signwaitsend'=>'提交发货',
		];
		*/
		?>
		<?//=Html::dropDownList('do','',$doCarrier,['class'=>'form-control input-sm do-carrier do']);?>
		</div> 
		 -->
		<?php endif;?>
		<!-- 
		<div class="col-md-2">
		<?php 
		/*
			$movearr = [''=>'移动到']+OdOrder::$status;
			unset($movearr[100]);
			*/
		?>
			<?//=Html::dropDownList('do','',$movearr,['onchange'=>"movestatus($(this).val());",'class'=>'form-control input-sm do']);?> 
		</div>
		 -->
		<div style="width:100%;">
		<?php if (isset($_REQUEST['order_status']) && $_REQUEST['order_status'] =='200' ){
			
			//预留之前版本的代码，方便新版上线后可以追问题,稳定后可以删除
			if( 1== 0){
			$PayBtnHtml = Html::button('指定运输服务',['class'=>"iv-btn btn-important",'onclick'=>"javascript:doaction('changeWHSM');"])."&nbsp;";
			$PayBtnHtml .= Html::button('修改报关信息',['class'=>"iv-btn btn-important",'onclick'=>"javascript:doaction('changeItemDeclarationInfo');"]). "&nbsp;";
			//$PayBtnHtml .= Html::button('申请物流号',['class'=>"iv-btn btn-important",'onclick'=>"OrderCommon.getTrackNo()"]). "&nbsp;";
			//$PayBtnHtml .= Html::button('移入发货中',['class'=>"iv-btn btn-important",'onclick'=>"javascript:doaction('signwaitsend');"]). "&nbsp;";
				
			$doarr += ['changeWHSM'=>'修改仓库和运输服务'];
			$doarr += ['outOfStock'=>'标记为缺货'];
			if (in_array(@$_REQUEST['exception_status'], ['223'])){
				echo Html::button(TranslateHelper::t('合并订单'),['class'=>"iv-btn btn-important",'onclick'=>"javascript:doaction('mergeorder');"]);
				echo '<span data-qtipkey="oms_order_exception_pending_merge_merge" class ="click-to-tip"><img style="cursor:pointer;padding-top:8px;" width="16" src="/images/questionMark.png"></span>&nbsp;';
			
				//echo Html::button(TranslateHelper::t('不合并订单'),['class'=>"iv-btn btn-important",'onclick'=>"javascript:doaction('skipMerge');"]);
				//echo '<span data-qtipkey="oms_order_exception_pending_merge_skip" class ="click-to-tip"><img style="cursor:pointer;padding-top:8px;" width="16" src="/images/questionMark.png"></span>&nbsp;';
			
				//echo Html::button(TranslateHelper::t('检测订单'),['class'=>"iv-btn btn-important",'onclick'=>"javascript:doaction('checkorder');"]);
				//echo '<span data-qtipkey="oms_order_status_check" class ="click-to-tip"><img style="cursor:pointer;padding-top:8px;" width="16" src="/images/questionMark.png"></span>&nbsp;';
			}elseif (in_array(@$_REQUEST['exception_status'], ['202'])){
				echo Html::button(TranslateHelper::t('分配运输服务'),['class'=>"iv-btn btn-important",'onclick'=>"javascript:doaction('changeWHSM');"]);
				echo '<span data-qtipkey="oms_order_exception_no_shipment_assign" class ="click-to-tip"><img style="cursor:pointer;padding-top:8px;" width="16" src="/images/questionMark.png"></span>&nbsp;';
			
				//echo Html::button(TranslateHelper::t('检测订单'),['class'=>"iv-btn btn-important",'onclick'=>"javascript:doaction('checkorder');"]);
				//echo '<span data-qtipkey="oms_order_status_check" class ="click-to-tip"><img style="cursor:pointer;padding-top:8px;" width="16" src="/images/questionMark.png"></span>&nbsp;';
			}elseif (in_array(@$_REQUEST['exception_status'], ['222'])){
				echo Html::button(TranslateHelper::t('库存处理'),['class'=>"iv-btn btn-important",'onclick'=>"javascript:doaction('stockManage');"]);
				echo '<span data-qtipkey="oms_order_exception_out_of_stock_manage" class ="click-to-tip"><img style="cursor:pointer;padding-top:8px;" width="16" src="/images/questionMark.png"></span>&nbsp;';
			
				//echo Html::button(TranslateHelper::t('转仓处理'),['class'=>"iv-btn click-to-tip btn-important",'onclick'=>"javascript:doaction('assignstock');"]);echo "&nbsp;";
				echo Html::button(TranslateHelper::t('标记缺货'),['class'=>"iv-btn btn-important",'onclick'=>"javascript:doaction('outOfStock');"]);
				echo '<span data-qtipkey="oms_order_exception_out_of_stock_mark" class ="click-to-tip"><img style="cursor:pointer;padding-top:8px;" width="16" src="/images/questionMark.png"></span>&nbsp;';
			
				//echo Html::button(TranslateHelper::t('检测订单'),['class'=>"iv-btn btn-important",'onclick'=>"javascript:doaction('checkorder');"]);
				//echo '<span data-qtipkey="oms_order_status_check" class ="click-to-tip"><img style="cursor:pointer;padding-top:8px;" width="16" src="/images/questionMark.png"></span>&nbsp;';
			}elseif (in_array(@$_REQUEST['exception_status'], ['203'])){
				echo Html::button(TranslateHelper::t('分配仓库'),['class'=>"iv-btn btn-important",'onclick'=>"javascript:doaction('changeWHSM');"]);echo "&nbsp;";
				echo '<span data-qtipkey="oms_order_exception_no_warehouse_assign" class ="click-to-tip"><img style="cursor:pointer;padding-top:8px;" width="16" src="/images/questionMark.png"></span>&nbsp;';
			
				echo Html::button(TranslateHelper::t('检测订单'),['class'=>"iv-btn btn-important",'onclick'=>"javascript:doaction('checkorder');"]);
				echo '<span data-qtipkey="oms_order_status_check" class ="click-to-tip"><img style="cursor:pointer;padding-top:8px;" width="16" src="/images/questionMark.png"></span>&nbsp;';
			}elseif (in_array(@$_REQUEST['exception_status'], ['210'])){
				echo Html::button(TranslateHelper::t('生成SKU'),['class'=>"iv-btn btn-important",'onclick'=>"javascript:doaction('generateProduct');"]);echo "&nbsp;";
				echo '<span data-qtipkey="oms_order_exception_sku_not_exist_create" class ="click-to-tip"><img style="cursor:pointer;padding-top:8px;" width="16" src="/images/questionMark.png"></span>&nbsp;';
			
				//echo Html::button(TranslateHelper::t('检测订单'),['class'=>"iv-btn btn-important",'onclick'=>"javascript:doaction('checkorder');"]);
				//echo '<span data-qtipkey="oms_order_status_check" class ="click-to-tip"><img style="cursor:pointer;padding-top:8px;" width="16" src="/images/questionMark.png"></span>&nbsp;';
			}elseif (in_array(@$_REQUEST['exception_status'], ['299']) || @$_REQUEST['pay_order_type'] == 'ship' || empty($_REQUEST['pay_order_type'])){
				echo Html::button('移入发货中',['class'=>"iv-btn btn-important",'onclick'=>"javascript:doaction('signwaitsend');"]);
				echo '<span data-qtipkey="oms_batch_ship_pack" class ="click-to-tip"><img style="cursor:pointer;padding-top:8px;" width="16" src="/images/questionMark.png"></span>&nbsp;';
			}else{
				//echo Html::button('检测订单',['class'=>"iv-btn btn-important",'onclick'=>"javascript:doaction('checkorder');"]);
				//echo '<span data-qtipkey="oms_order_status_check" class ="click-to-tip"><img style="cursor:pointer;padding-top:8px;" width="16" src="/images/questionMark.png"></span>&nbsp;';
			}
			echo $PayBtnHtml;
			}
			
			$PayBtnHtml = Html::button('指定运输服务',['class'=>"iv-btn btn-important",'onclick'=>"orderCommonV3.doaction('changeWHSM');"])."&nbsp;";
			$PayBtnHtml .= Html::button('修改报关信息',['class'=>"iv-btn btn-important",'onclick'=>"orderCommonV3.doaction('changeItemDeclarationInfo');"]). "&nbsp;";
				
			$doarr += ['changeWHSM'=>'修改仓库和运输服务'];
			$doarr += ['outOfStock'=>'标记为缺货'];
			if (in_array(@$_REQUEST['exception_status'], ['223'])){
				echo Html::button(TranslateHelper::t('合并订单'),['class'=>"iv-btn btn-important",'onclick'=>"orderCommonV3.doaction('mergeorder');"]);
				echo '<span data-qtipkey="oms_order_exception_pending_merge_merge" class ="click-to-tip"><img style="cursor:pointer;padding-top:8px;" width="16" src="/images/questionMark.png"></span>&nbsp;';
			}elseif (in_array(@$_REQUEST['exception_status'], ['299']) || @$_REQUEST['pay_order_type'] == 'ship' || empty($_REQUEST['pay_order_type'])){
				echo Html::button('移入发货中',['class'=>"iv-btn btn-important",'onclick'=>"orderCommonV3.doaction('signwaitsend');"]);
				echo '<span data-qtipkey="oms_batch_ship_pack" class ="click-to-tip"><img style="cursor:pointer;padding-top:8px;" width="16" src="/images/questionMark.png"></span>&nbsp;';
			}
			
			if (!empty($_REQUEST['is_merge'])){
				echo Html::button(TranslateHelper::t('取消合并'),['class'=>"iv-btn btn-important",'onclick'=>"orderCommonV3.doaction('cancelMergeOrder');"]).'&nbsp;';
			}
			
			echo $PayBtnHtml;
		}
		if (@$_REQUEST['pay_order_type'] == 'reorder' || in_array(@$_REQUEST['exception_status'], ['203','222','202']) ){
			//echo Html::checkbox( 'chk_refresh_force','',['label'=>TranslateHelper::t('强制重新检测')]);
		}
		$doarr += ['signshipped'=>'虚拟发货(标记发货)',/*'calculat_profit'=>'计算订单利润'*/];
		if(isset($doarr['givefeedback']))
			unset($doarr['givefeedback']);
		if(isset($doarr['refreshOrder']))
			unset($doarr['refreshOrder']);
		?>
		
		<?php 
			$doDownListHtml = OrderListV3Helper::getDropdownToggleHtml('批量操作', $doarr, 'orderCommonV3.doaction3');
			echo $doDownListHtml;
			//Html::dropDownList('do','',$doarr,['onchange'=>"orderCommonV3.doaction2(this);",'class'=>'iv-input do','style'=>'width:200px;margin:0px']);
		?> 
	
		<?php 
			$excelActionItems = array(
					'0'=>array('event'=>'OrderCommon.orderExcelprint(0)','label'=>'按勾选导出'),
					'1'=>array('event'=>'OrderCommon.orderExcelprint(1)','label'=>'按所有页导出')
			);
			$excelDownListHtml = OrderListV3Helper::getDropdownToggleHtml('导出订单', $excelActionItems);
			echo $excelDownListHtml;
			//echo Html::dropDownList('orderExcelprint','',['-1'=>'导出订单','0'=>'按勾选导出','1'=>'按所有页导出'],['onchange'=>"OrderCommon.orderExcelprint($(this).val())",'class'=>'iv-input do','style'=>'width:200px;margin:0px']);
		?>
		
		<div class="" style="line-height:20px;position: relative;-moz-border-radius: 3px;-webkit-border-radius: 3px;border-radius: 3px;display: inline-block;vertical-align: middle;">
			<button type="button" class="btn" style="padding:3px 10px!important;" onclick="syncAllUnClosedOrderStatus()">同步所有未完成订单的状态</button>
		</div>
		<?php 
		if (isset($_REQUEST['order_status']) && $_REQUEST['order_status'] =='200' ){
			echo "<div style='float:right'>";
			echo Html::button(TranslateHelper::t('导入跟踪号'),['class'=>"iv-btn btn-important",'onclick'=>"OrderCommon.importTrackNoBox('priceminister')"]);
			echo "</div>";
		}
		?>
		</div>
		<?php $divTagHtml = "";?>
		<?php $div_event_html = "";?>
		<br>
			<?php
			if (!empty($showMergeOrder) && 1 == 0){
			?>
		
			<table class="table table-condensed table-bordered" style="font-size:12px;">
			<tr>
				<th width="1%">
				<input id="ck_all" class="ck_0" type="checkbox">
				</th>
				<th width="10%"><b>PM订单号</b></th>
				<th width="12%"><b>商品SKU</b></th>
				<th width="10%"><b>订单金额</b></th>
				<th width="17%"><b>付款日期</b><span qtipkey="pm_oms_order_paid_time"></span></th>
				<th width="10%">
				<?=Html::dropDownList('country',@$_REQUEST['country'],$countryArr,['prompt'=>'收件国家','style'=>'width:100px','onchange'=>"dosearch('country',$(this).val());"])?>
				</th>
				<th width="10%">
				<?=Html::dropDownList('shipmethod',@$_REQUEST['shipmethod'],$carriers,['prompt'=>'物流方式','style'=>'width:100px','onchange'=>"dosearch('shipmethod',$(this).val());"])?>
				</th>
				<th width="10%" title="Priceminister状态"><b>PM状态</b><span qtipkey="oms_order_platform_status_priceminister"></span></th></th>
				<th width="10%">
					<b>小老板状态</b><span qtipkey="oms_order_lb_status_description"></span>
				</th>
				<th width="10%"><b>物流状态</b><span qtipkey="oms_order_carrier_status_description"></span></th>
				<th ><b>操作</b><span qtipkey="oms_order_action_priceminister"></span></th>
			</tr>
			<?php $carriers=CarrierApiHelper::getShippingServices(false)?>
			<?php $pm_customer_shipped_method = CarrierHelper::getPriceministerBuyerShippingServices()?>
			<?php 
				$allUserAccounts = SaasPriceministerUser::find()->where(['uid'=>$uid])->all();
				$saasAccounts = [];
				foreach ($allUserAccounts as $account){
					$saasAccounts[$account->username] = $account;
				}
			?>
			<?php if (count($models)):foreach ($models as $order):?>
			<?php
			$showLastMessage=0;
			if (!empty($showMergeOrder)){
				$groupOrderMd5 = md5($order->selleruserid.$order->consignee.$order->consignee_address_line1.$order->consignee_address_line2.$order->consignee_address_line3);
			}
			?>
			<tr style="background-color: #f4f9fc" <?= empty($groupOrderMd5)?"":"merge-row-tag='".$groupOrderMd5."'"; ?> >
				<td><label><input type="checkbox" class="ck" name="order_id[]" value="<?=$order->order_id?>"></label>
				</td>
				<td>
					<span><b><?=$order->order_source_order_id ?></b><br>小老板id：<?=(int)$order->order_id?></span><?=($order->order_type=='FBC')?"<span class='pm_fbc_inco' title='Priceminister FBC 订单'></span>":''?><br>
					<?php if ($order->seller_commenttype=='Positive'):?>
						<span style='background:green;'><a style="color: white" title="<?=$order->seller_commenttext?>">好评</a></span><br>
					<?php elseif($order->seller_commenttype=='Neutral'):?>
						<span style='background:yellow;'><a title="<?=$order->seller_commenttext?>">中评</a></span><br>
					<?php elseif($order->seller_commenttype=='Negative'):?>
						<span style='background:red;'><a title="<?=$order->seller_commenttext?>">差评</a></span><br>
					<?php endif;?>
					<?php if (!empty($order->weird_status)):?>
						<div class="no-qtip-icon" qtipkey="pm_order_weird_status_<?=$order->weird_status ?>"><span class="pm-oms-weird-status-wfs"></span></div>
					<?php endif;?>
					<?php if ($order->exception_status>0&&$order->exception_status!='201'):?>
						<div title="<?=OdOrder::$exceptionstatus[$order->exception_status]?>" class="exception_<?=$order->exception_status?>"></div>
					<?php endif;?>
					<?php if (strlen($order->user_message)>0):?>
						<div title="<?=OdOrder::$exceptionstatus[OdOrder::EXCEP_HASMESSAGE]?>" class="exception_<?=OdOrder::EXCEP_HASMESSAGE?>"></div>
					<?php endif;?>
					<?php 
				$divTagHtml .= '<div id="div_tag_'.$order->order_id.'"  name="div_add_tag" class="div_space_toggle div_add_tag"></div>';
				$TagStr = OrderFrontHelper::generateTagIconHtmlByOrderId($order);
				//$TagStr = OrderTagHelper::generateTagIconHtmlByOrderId($order->order_id);
				
				if (!empty($TagStr)){
					$TagStr = "<span class='btn_tag_qtip".(stripos($TagStr,'egicon-flag-gray')?" div_space_toggle":"")."' data-order-id='".$order->order_id."' >$TagStr</span>";
				}
				echo $TagStr;
				?>
				</td>
				<td>
					<?php if (count($order->items)):foreach ($order->items as $item):?>
					<?php if (!empty($item->sku)&&strlen($item->sku)):?>
					<?=$item->sku?>&nbsp;X<b style="padding:2px;border-radius:5px;<?=((int)$item->quantity >1 )?'background-color:orange;color:white;':'' ?>"><?=$item->quantity?></b><br>
					<?php endif;?>
					<?php endforeach;endif;?>
				</td>
				<td style="padding:0px">
					<?php
						$currencySing = $order->currency;
						$currencyInfo = StandardConst::getCurrencyInfoByCode($currencySing);
						if(!empty($currencyInfo) && !empty($currencyInfo['html'])){
							$currencySing = $currencyInfo['html'];
						}
					?>
					<div style="float:left;width:100%;"><span style="float:left;width:40px;">产品+</span><span style="float:left;"><?=$order->subtotal?>&nbsp;<?=$currencySing?></span></div>
					<!-- 
					<div style="float:left;width:100%;"><span style="float:left;width:40px;">佣金-</span><span style="float:left;"><?=!empty($order->commission_total)?$order->commission_total:$order->discount_amount ?>&nbsp;<?=$currencySing?></span></div>
					 -->
					<div style="float:left;width:100%;"><span style="float:left;width:40px;">运费+</span><span style="float:left;"><?=$order->shipping_cost?>&nbsp;<?=$currencySing?></span></div>
					<div style="float:left;width:100%;font-weight:bold;"><span style="float:left;;width:40px;">合计=</span><span style="float:left;"><?=$order->grand_total?>&nbsp;<?=$currencySing?></span></div>
				</td>
				<td>
					<?=(empty($order->order_source_create_time)?'':'下单日期:<b>'.date('Y-m-d H:i:s',$order->order_source_create_time).'</b><br>') ?>
					<?=(empty($order->paid_time)?'':'付款日期:<b>'.date('Y-m-d H:i:s',$order->paid_time).'</b><br>') ?>
					<?=(empty($order->update_time)?'':'最后操作日期:<b>'.date('Y-m-d H:i:s',$order->update_time).'</b><br>') ?>
				</td>
				<td>
					<label title="<?=(isset($sysCountry[$order->consignee_country]))?$sysCountry[$order->consignee_country]['country_zh']:$order->consignee_country?>">
					<?=(isset($sysCountry[$order->consignee_country_code]))?$sysCountry[$order->consignee_country_code]['country_zh']:''?>(<?=$order->consignee_country_code?>)<br>
					<?=$order->consignee_country?>
					</label>
				</td>
				<td>
					[客选物流:<?=isset($pm_customer_shipped_method[$order->order_source_shipping_method])?$pm_customer_shipped_method[$order->order_source_shipping_method]:$order->order_source_shipping_method ?>]<br>
					<?php if (strlen($order->default_shipping_method_code)){?>[<?=empty($carriers[$order->default_shipping_method_code])?'未知':$carriers[$order->default_shipping_method_code] ?>]<?php }?>
				</td>
				<td>
					<!-- 付款状态图标 -->
					<!-- 
					<?php if ($order->pay_status==0):?>
					
					<?php elseif ($order->pay_status==1):?>
					<div title="已付款" class="sprite_pay_1"></div>
					<?php elseif ($order->pay_status==2):?>
					<div title="支付中" class="sprite_pay_2"></div>
					<?php elseif ($order->pay_status==3):?>
					<div title="已退款" class="sprite_pay_3"></div>
					<?php endif;?>
					 -->
					<!-- 发货图标 -->
					<!-- 					<?php if ($order->shipping_status==1):?>
					<div title="已发货" class="sprite_shipped_1"></div>
					<?php else:?>
					<div title="未发货" class="sprite_shipped_0"></div>
					<?php endif;?>
					 -->
					<b>
					 <?php	
					  if(!empty($order->order_source_status)){
					  	$source_status_mapping = PriceministerOrderHelper::$orderStatus;
					  	if(!empty($source_status_mapping[$order->order_source_status]))
					  		echo $source_status_mapping[$order->order_source_status];
					  	else 
					  		echo $order->order_source_status;
					  }else 
					  	echo '--';
					 ?>
					 </b>
				</td>
				<td>
					<b><?=OdOrder::$status[$order->order_status]?></b>
					<?=!empty(odorder::$exceptionstatus[$order->exception_status])?'<br><b style="color:#FFB0B0">'.odorder::$exceptionstatus[$order->exception_status].'</b>':'' ?>
				</td>
				<td>
					<?php 
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
						if (!empty($shipmentHealthCheckHtml))
							$shipmentHealthCheckHtml.='<br><a onclick="doactionone(\'changeWHSM\',\''.$order->order_id.'\');">'.TranslateHelper::t('设置运输服务与仓库').'</a>';
					}
					
					if (!empty($shipmentHealthCheckHtml) || !empty($carrierErrorHtml)){
						if ( ($order->order_type!='FBC') && ($order->order_status ==OdOrder::STATUS_PAY))
							echo '<div class="nopadingAndnomagin alert alert-danger">'.$carrierErrorHtml.$shipmentHealthCheckHtml."</div>";
					}
					?>
				
					<?php if ($order->order_status=='300'):?>
					<?php echo CarrierHelper::$carrier_step[$order->carrier_step].'<br>';?>
					<?php endif;?>
					<?php 
					$odOrderShipInfo = array();
					if('sm' == $order->order_relation){
						$odOrderShipInfo = OrderHelper::getMergeOrderShippingInfo($order->order_id);
// 						var_dump($odOrderShipInfo);
					}else{
						$odOrderShipInfo = $order->trackinfos;
					}
					?>
					<?php if (count($order->trackinfos)):foreach ($order->trackinfos as $ot):?>
						<?php 
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
							$class='text-invalid';
							$qtip = '<span qtipkey="tracking_number_with_invalid_status"></span>';
						}
						?>
						<?php if(!empty($ot->errors)):?>
						<br><b style="color:red;"><?=($ot->addtype=='手动标记发货')?'手动标记发货失败:':'物流处理问题:'?><?=$ot->errors ?><br></b>	
						<?php endif; ?>
						<!--  <a href="<?=$ot->tracking_link?>" title="<?=$ot->shipping_method_name?>" target="_blank" ><font class="<?php echo $class?>"><?=$ot->tracking_number?></font></a>-->
						<?php if (strlen($ot->tracking_number)):
							$track_info = TrackingApiHelper::getTrackingInfo($ot->tracking_number);
						?>
						<?php if ($track_info['success']==TRUE):?>
							<b><?=$track_info['data']['status']?></b><br>
							<?php 
							//查询中  carrier_type 也等于0  , 但不是全球邮政
							if (isset($track_info['data']['carrier_type']) && ! in_array(strtolower($track_info['data']['status']) , ['checking',"查询中","查询等候中"]) ){
								if (isset(CarrierTypeOfTrackNumber::$expressCode[$track_info['data']['carrier_type']]))
									echo "<span >(".TranslateHelper::t('通过').CarrierTypeOfTrackNumber::$expressCode[$track_info['data']['carrier_type']].TranslateHelper::t('查询到的结果').")</span><br>";
							}
							?>
						<?php endif;?>
						<?php
						$trackingOne=Tracking::find()->where(['track_no'=>$ot->tracking_number,'order_id'=>$order->order_source_order_id])
							->orderBy(['update_time'=>SORT_DESC])->one();
						if(!empty($trackingOne)) $carrier_type = $trackingOne->carrier_type;
						else $carrier_type = '';
						if(!in_array($carrier_type, $non17Track)) $tracking_info_type='17track';
							  else $tracking_info_type = '';
						?>
						<a href="javascript:void(0);" onclick="OmsViewTracker(this,'<?=$ot->tracking_number ?>')" title="<?=$ot->shipping_method_name?>" data-info-type='<?=$tracking_info_type ?>'><span class="order-info"><font class="<?php echo $class?>"><?=$ot->tracking_number?></font><?=$qtip ?></span></a>
						
						<?php //Tracker忽略物此流号操作	liang 16-02-27 start
						//当标记发货成功时，才出现忽略操作按钮
						if ($ot->status==1 && $order->logistic_status!=='ignored'){
						?>
						<span class="iconfont icon-ignore_search" onclick="ignoreTrackingNo(<?=$order->order_id?>,'<?=$ot->tracking_number?>')" data-toggle="popover" data-content="使物流查询助手忽略此物流号(不可逆操作)。当标记发货成功后，可选择此操作。忽略后，物流助手将不会再查询其信息" data-html="true" data-trigger="hover" data-placement="top" style="vertical-align:baseline;cursor:pointer;"></span>
						<?php }	?>
						<br>
						<?php 
						//组织显示物流明细的东东
						$div_event_html .= "<div id='div_more_info_".$ot->tracking_number."' class='div_more_tracking_info div_space_toggle'>";
						
						$all_events_str = "";
						
						$all_events_rt = TrackingHelper::generateTrackingEventHTML([$ot->tracking_number],[],true);
						if (!empty($all_events_rt[$ot->tracking_number])){
							$all_events_str = $all_events_rt[$ot->tracking_number];
						}
							
						$div_event_html .=  $all_events_str;
						
						$div_event_html .= "</div>";
						?>
						<?php endif;?>
					<?php endforeach;endif;?>
					
					<?php if (!empty($order->seller_weight) && (int)$order->seller_weight!==0){
						echo "称重重量：".(int)$order->seller_weight." g";
					}?>
				</td>
				<?php if(!empty($showMergeOrder)){?>
				<?php if(!empty($groupOrderMd5) && (empty($nowMd5) || $nowMd5!=$groupOrderMd5) ){
					$nowMd5 = $groupOrderMd5;
					echo "<td>";
					echo Html::button(TranslateHelper::t('合并'),['class'=>"iv-btn btn-important",'style'=>"width: 78px;",'onclick'=>"OrderCommon.mergeSameRowOrder('".$nowMd5."');"]);
					echo "</td>";
				}?>
				<?php }else{?>
				<td>
					<a href="<?=Url::to(['/order/priceminister-order/edit','orderid'=>$order->order_id])?>" target="_blank" qtipkey="cd_order_action_editorder" class="no-qtip-icon"><span class="egicon-edit toggleMenuL" title="编辑订单"></span></a>
					<?php if(in_array($order->order_source_status,['new','current','tracking','claim'])){ ?>
					<a href="#" onclick="javascript:syncOneOrderStatus('<?=$order->order_id ?>')"><span class="glyphicon glyphicon-refresh toggleMenuL" style="top:3px" title="立即同步订单状态"></span></a>
					<?php } ?>
					
					<?php //客服消息记录dialog的入口icon -- start
						$detailMessageType=MessageApiHelper::orderSessionStatus('priceminister',$order->order_source_order_id);
						if(!empty($detailMessageType['data']) && !is_null($detailMessageType['data'])){ 
						if(!empty($detailMessageType['data']['hasRead']) && !empty($detailMessageType['data']['hasReplied']))
							$envelope_class="egicon-envelope";
						else 
							$envelope_class="egicon-envelope-remove";
					?>
					<a href="javascript:void(0);" onclick="ShowDetailMessage('<?=$order->selleruserid?>','<?=$order->source_buyer_user_id?>','priceminister','','O','','','','<?=$order->order_source_order_id ?>')" title="查看订单留言"><span class="<?=$envelope_class?>"></span></a>
					<?php }  //客服消息记录dialog的入口icon -- end?>
					
					<!-- 
					<?php if ($order->is_manual_order==1):?>
					<a href="#" onclick="javascript:changemanual('<?=$order->order_id ?>',this)"><span class="glyphicon glyphicon-save toggleMenuL" title="取消挂起"></span></a>
					<?php else:?>
					<a href="#" onclick="javascript:changemanual('<?=$order->order_id ?>',this)" qtipkey="cd_order_action_changemanual" class="no-qtip-icon" ><span class="glyphicon glyphicon-open toggleMenuL" title="挂起"></span></a>
					<?php endif;?>
					 -->
					<?php 
					$doarr_one+=['signshipped'=>'虚拟发货(标记发货)'];
					if(isset($doarr_one['givefeedback']))
						unset($doarr_one['givefeedback']);
					if(isset($doarr_one['refreshOrder']))
						unset($doarr_one['refreshOrder']);
					if(isset($doarr_one['signcomplete']))
						unset($doarr_one['signcomplete']);
					
					if ($order->order_status=='200'){
 						$doarr_one+=[
 							'getorderno'=>'移入发货中',
							'outOfStock'=>'标记为缺货',
 						];	
					}
					$doarr_one+=['invoiced' => '发票'];
					$this_doarr_one = $doarr_one;
					if($order->order_capture=='Y'){
						if(isset($this_doarr_one['signshipped']))
							unset($this_doarr_one['signshipped']);
					}
					if(!(($order->order_capture == 'Y') && ($order->order_relation == 'normal'))){
						if(isset($this_doarr_one['delete_manual_order']))
							unset($this_doarr_one['delete_manual_order']);
					}
					?>
					<?=Html::dropDownList('do','',$this_doarr_one,['onchange'=>"doactionone($(this).val(),'".$order->order_id."');",'class'=>'form-control input-sm do','style'=>'width:70px;']);?>
				</td>
				<?php }?>
			</tr>
				<?php if (count($order->items)):
				//PM订单存在不需要show出来的orderItem,需要进行预处理
					$showItems=0;
					$nonDeliverySku=PriceministerOrderInterface::getNonDeliverySku();
					foreach ($order->items as $key=>$item){
						if ( !empty($item->sku) && !in_array(strtoupper($item->sku),$nonDeliverySku) )
							$showItems++;
					}
				//预处理end
				$first_item_key = '';
				foreach ($order->items as $key=>$item):?>
				<?php 
				//客服模块最后一条对话 start
					if(empty($showLastMessage)){
						$lastMessageInfo = MessageApiHelper::getOrderLastMessage($order->order_source_order_id);
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
				?>
				<?php if(empty($item->sku) or in_array(strtoupper($item->sku),$nonDeliverySku) ) continue;
					else {
						if($first_item_key=='')
							$first_item_key = $key;
					}
				?>
				<tr class="xiangqing <?=$order->order_id?> <?= ($key==$first_item_key)?"first-item":"" ?>" <?= empty($groupOrderMd5)?"":"merge-row-tag='".$groupOrderMd5."'"; ?>>
				<?php 
				$prodInfo = !empty($product_infos[$item->sku])?$product_infos[$item->sku]:[];
				//var_dump($prodInfo);
				$photo_primary = $item->photo_primary;
				/*
				if(empty($photo_primary)){
					$prodInfo = PriceministerOfferList::find()->where(['product_id'=>$item->order_source_itemid])->andWhere(['not',['img'=>null]])->one();
					if($prodInfo<>null && !empty($prodInfo->img)){
						$photos = json_decode($prodInfo->img,true);
						$photo_primary = empty($photos[0])?'':$photos[0];
					}
				}
				*/
				if(empty($photo_primary)){
					if(!empty($prodInfo))
						$photo_primary = $prodInfo['photo_primary'];
				}
				$photo_primary = ImageCacherHelper::getImageCacheUrl($photo_primary,$uid,1);
				?>
					<td style="border:1px solid #d9effc;"><img class="prod_img" src="<?=$photo_primary?>" width="60px" height="60px" data-toggle="popover" data-content="<img src='<?=$photo_primary?>'>" data-html="true" data-trigger="hover"></td>
					<td colspan="2" style="border:1px solid #d9effc;text-align:justify;">
						<?=!empty($item->product_url)?'<a href="'.((stripos($item->product_url, 'http://www.priceminister.com')===false)?'http://www.priceminister.com'.$item->product_url:$item->product_url).'" target="_blank" title="点击查看产品链接" style="cursor:pointer;">':'' ?>
						SKU:<b><?=$item->sku?></b><br>
						<?=$item->product_name?><br>
						<?=!empty($item->product_url)?'</a>':''?>
					</td>
					<td  style="border:1px solid #d9effc">
						<?=$item->quantity?>
						<?php
						if(!empty($prodInfo) && !empty($prodInfo['purchase_link'])){
							echo "<a href='".$prodInfo['purchase_link']."' target='_blank'><span class='glyphicon glyphicon-shopping-cart' title='商品已于商品模块设置了采购链接，点击打开该链接' style='cursor:pointer;color:#2ecc71;margin-left:5px;'></span></a>";	
						}elseif(!empty($prodInfo) && empty($prodInfo['purchase_link'])){
							echo "<a href='/catalog/product/list?txt_search=".$prodInfo['sku']."' target='_blank'><span class='glyphicon glyphicon-shopping-cart' style='color:rgb(139, 139, 139);cursor:pointer;margin-left:5px;' title='商品还没在商品模块设置采购链接，点击前往商品模块设置'></span></a>";
						}else{
							echo "<a href='/catalog/product/list' target='_blank'><span class='glyphicon glyphicon-shopping-cart' style='color:rgb(139, 139, 139);cursor:pointer;margin-left:5px;' title='商品还没在商品模块创建，点击前往创建商品'></span></a>";
						}?>
					</td>
					<?php if ($key=='0'):?>
					<td rowspan="<?=$showItems?>" style="border:1px solid #d9effc">
						<font color="#8b8b8b">仓库:</font><br>
						<b><?php if ($order->default_warehouse_id>0&&count($warehouses)){echo $warehouses[$order->default_warehouse_id];}?></b>
					</td>
					<td rowspan="<?=$showItems?>" style="border:1px solid #d9effc">
						<font color="#8b8b8b">priceminister店铺名 / 买家名称</font><br>
						<b><?= empty($saasAccounts[$order->selleruserid]) ? "" : $saasAccounts[$order->selleruserid]->store_name;?> / <?=str_replace(' ', '&nbsp;', $order->consignee);?></b>
					</td>
					<?php endif;?>
					<td colspan="2" style="border:1px solid #d9effc;   text-align: center; vertical-align: middle;">
						<?php if(!empty($item->order_source_order_item_id)){ 
							//$pm_order_item = PriceministerOrderDetail::find()->where(['purchaseid'=>$item->order_source_order_id,'itemid'=>$item->order_source_order_item_id])->orderBy('id desc')->asArray()->one();
							$item_status='';
							if(!empty($item['source_item_id']))
								echo "订单商品id：".$item['source_item_id'].'<br>';
							
							if(!empty($item['platform_status'])){
								$item_status = $item['platform_status'];
							}
							
							if(empty($item_status)) 
								$item_status = '--';
							echo '商品状态:<b>'.$item_status.'</b>';
							$addi_info = $item->addi_info;
							if(!empty($addi_info))
								$addi_info = json_decode($addi_info,true);
							if(($item_status=='TO_CONFIRM'|| $item_status=='REQUESTED' || $item_status=='REMINDED') && empty($addi_info['userOperated'])){
								echo '<br><button type="button" class="btn-info" onclick="pmOrder.list.operateNewSaleItem(\'accept\',\''.$item['source_item_id'].'\',\''.$order->selleruserid.'\')">接受</button><button type="button" class="btn-danger" onclick="pmOrder.list.operateNewSaleItem(\'refuse\',\''.$item['source_item_id'].'\',\''.$order->selleruserid.'\')">拒接</button>';
							}
							if(($item_status=='TO_CONFIRM'|| $item_status=='REQUESTED' || $item_status=='REMINDED') && !empty($addi_info['isNewSale']) && !empty($addi_info['userOperated'])){
								echo '<br><b>已经做过接受/拒接操作，请耐心等待同步</b>';
								if(!empty($addi_info['operate_time']))
									echo '<br>操作时间:'.$addi_info['operate_time'];
							}
						?>
						<?php } ?>
					</td>
					<?php if ($key=='0'):?>
					<td colspan="2"  rowspan="<?=$showItems?>"  width="150px" style="word-break:break-all;word-wrap:break-word;border:1px solid #d9effc">
						<font color="#8b8b8b">买家留言:</font><br>
						<b><?=!empty($order->user_message)?$order->user_message:''?>
							<?php if(!empty($lastMessage) && empty($showLastMessage)){
								echo '<a href="javascript:void(0);" onclick="ShowDetailMessage(\''.$order->selleruserid.'\',\''.$order->source_buyer_user_id.'\',\'priceminister\',\'\',\'O\',\'\',\'\',\'\',\''.$order->order_source_order_id.'\')">';
								echo $lastMessage;
								echo '</a>';
								$showLastMessage=1;
							} ?>
						</b>
					</td>
					<?php if(empty($showMergeOrder)){?>
					<td  rowspan="<?=$showItems?>" width="150" style="word-break:break-all;word-wrap:break-word;border:1px solid #d9effc">
						<span><font color="red"><?=$order->desc?></font></span>
						<a href="javascript:void(0)" style="border:1px solid #00bb9b;" onclick="updatedesc('<?=$order->order_id?>',this)" oiid="<?=$order->order_id?>"><font style="white-space: nowrap;" color="00bb9b">备注</font></a>
					</td>
					<?php }?>
					<?php endif;?>
				</tr>	
				<?php endforeach;endif;?>
				<?php if (empty($showMergeOrder)){ ?>
				<tr style="background-color: #d9d9d9;" class="xiangqing <?=$order->order_id;?>">
					<td colspan="11" class="row" id="dataline-<?=$order->order_id;?>" style="word-break:break-all;word-wrap:break-word;border:1px solid #d1d1d1;height:11px;"></td>
				</tr>
				<?php } ?>
			<?php endforeach;endif;?>
			</table>
			
			<?php
				}else{
					echo $this->render('../order/order_list_v3',[ 'carriers'=>$carriers, 'models'=>$models,
							'warehouses'=>$warehouses, 'non17Track'=>$non17Track, 'tag_class_list'=>$tag_class_list,
							'current_order_status' => empty($_REQUEST['order_status']) ? '' : $_REQUEST['order_status'],
							'current_exception_status' => empty($_REQUEST['exception_status']) ? '' : $_REQUEST['exception_status'],
							'platform_type'=>'priceminister']);
				}
			?>
			
			
			<div class="btn-group" >
			<?=LinkPager::widget([
			    'pagination' => $pages,
			]);
			?>
			</div>
			<?=SizePager::widget(['pagination'=>$pages , 'pageSizeOptions'=>array( 5 , 20 , 50 , 100 , 200 ,500), 'class'=>'btn-group dropup'])?>
		</form>
	</div>
	<?php if (@$_REQUEST['order_status'] == 200 ):?>
	<div>
	<ul>
		<li style="float: left;line-height: 60px;">你还可以查看以下处理阶段的订单：</li>
		<li style="float: left;"><?php echo OrderFrontHelper::displayOrderPaidProcessHtml($counter,'priceminister')?></li>
	</ul>
	
	</div>
	<?php endif;?>
<div style="clear: both;"></div>
</div></div>
<div style="display: none;">
<?=$divTagHtml?>
<?=$div_event_html?>
<div id="div_ShippingServices"><?=Html::dropDownList('demo_shipping_method_code',@$order->default_shipping_method_code,CarrierApiHelper::getShippingServices())?></div>
<div class="dash-board"></div>
<div class="important-change-tip-win"></div>
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
	<div>批量提交订单到 发货 物流模块<br>已付款的订单可以被多选，批量进行物流以及发货，请到 菜单顶部的 <b style="font-weight: 600;color:blue">物流</b>模块 或者 <b style="font-weight: 600;color:blue">发货</b>模块，进行物流商的运单申请 以及 批量拣货 配货 打印标签。<br>如果有部分订单处于 <b style="font-weight: 600;color:red">异常</b>状态的，建议点击右边的<b style="font-weight: 600;color:red">有异常</b>按钮，对异常订单进行处理，然后刷新该页面.</div>
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
function doaction2(obj){
	var val = $(obj).val();
	if (val != ''){
		doaction(val );
		$(obj).val('');
	}
}
//批量操作
function doaction(val){
	//如果没有选择订单，返回；
	if(val==""){
        bootbox.alert("请选择您的操作");return false;
    }
    if($('.ck:checked').length==0&&val!=''){
    	bootbox.alert("请选择要操作的订单");return false;
    }
	switch(val){
		case 'setSyncShipComplete':
			var thisOrderList =[];
			$('input[name="order_id[]"]:checked').each(function(){
				thisOrderList.push($(this).val());
			});
	
			OrderCommon.setOrderSyncShipStatusComplete(thisOrderList);
			break;
		case 'changeItemDeclarationInfo':
			var thisOrderList =[];
		
			$('input[name="order_id[]"]:checked').each(function(){
				thisOrderList.push($(this).val());
			});

			OrderCommon.showChangeItemDeclarationInfoBox(thisOrderList);
			break;
		case 'addMemo':
			var idstr = [];
			$('input[name="order_id[]"]:checked').each(function(){
				idstr.push($(this).val());
			});
			OrderCommon.showAddMemoBox(idstr);
			break;
		case 'checkorder':
			idstr='';
			$('input[name="order_id[]"]:checked').each(function(){
				idstr+=','+$(this).val();
			});
			$.post('<?=Url::to(['/order/order/checkorderstatus'])?>',{orders:idstr},function(result){
				bootbox.alert(result);
				location.reload();
			});
			break;
		case 'signshipped':
			document.a.target="_blank";
			document.a.action="<?=Url::to(['/order/order/signshipped'])?>";
			document.a.submit();
			document.a.action="";
			break;
		case 'generateProduct':
			idstr = [];
			$('input[name="order_id[]"]:checked').each(function(){
				idstr.push($(this).val());
			});
			
			//遮罩
			$.maskLayer(true);
			$.ajax({
				url: "<?=Url::to(['/order/order/generateproduct'])?>",
				data: {
					orderids:idstr,
				},
				type: 'post',
				success: function(response) {
					 var r = $.parseJSON(response);
					 if(r.result){
						 var event = $.confirmBox('<p class="text-success">'+r.message+'</p>');
					 }else{
						 var event = $.confirmBox('<p class="text-warn">'+r.message+'</p>');
					 } 
					 event.then(function(){
					  // 确定,刷新页面
					  location.reload();
					},function(){
					  // 取消，关闭遮罩
					  $.maskLayer(false);
					});
				},
				error: function(XMLHttpRequest, textStatus) {
					$.maskLayer(false);
					$.alert('<p class="text-warn">网络不稳定.请求失败,请重试!</p>','danger');
				}
			})
			break;
		case 'signcomplete':
			idstr = [];
			$('input[name="order_id[]"]:checked').each(function(){
				idstr.push($(this).val());
			});
			
			//遮罩
			$.maskLayer(true);
			$.ajax({
				url: "<?=Url::to(['/order/order/signcomplete'])?>",
				data: {
					orderids:idstr,
				},
				type: 'post',
				success: function(response) {
					 var r = $.parseJSON(response);
					 if(r.result){
						 var event = $.confirmBox('<p class="text-success">'+r.message+'</p>');
					 }else{
						 var event = $.confirmBox('<p class="text-warn">'+r.message+'</p>');
					 } 
					 event.then(function(){
					  // 确定,刷新页面
					  location.reload();
					},function(){
					  // 取消，关闭遮罩
					  $.maskLayer(false);
					});
				},
				error: function(XMLHttpRequest, textStatus) {
					$.maskLayer(false);
					$.alert('<p class="text-warn">网络不稳定.请求失败,请重试!</p>','danger');
				}
			})
			break;
		case 'suspendDelivery':
			idstr = [];
			$('input[name="order_id[]"]:checked').each(function(){
				idstr.push($(this).val());
			});
			
			$.post('<?=Url::to(['/order/order/suspenddelivery'])?>',{orders:idstr},function(result){
				bootbox.alert({  
		            buttons: {  
		                ok: {  
		                     label: '确认',  
		                     className: 'btn-myStyle'  
		                 }  
		             },  
		             message: result,  
		             callback: function() {  
		                 location.reload();
		             },  
		         });
				
			});
			break;
		case 'deleteorder':
			if(confirm('确定需要删除选中订单?平台订单可能会重新同步进入系统')){
				document.a.target="_blank";
    			document.a.action="<?=Url::to(['/order/order/deleteorder'])?>";
    			document.a.submit();
    			document.a.action="";
			}
			break;
		case 'cancelorder':
			idstr = [];
			$('input[name="order_id[]"]:checked').each(function(){
				idstr.push($(this).val());
			});
			
			$.post('<?=Url::to(['/order/order/cancelorder'])?>',{orders:idstr},function(result){
				bootbox.alert(result);
				location.reload();
			});
			break;
		case 'signwaitsend':
			idstr='';
			$('input[name="order_id[]"]:checked').each(function(){
				idstr+=','+$(this).val();
			});
			OrderCommon.shipOrderOMS(idstr);
			break;
		case 'signpayed':
			idstr='';
			$('input[name="order_id[]"]:checked').each(function(){
				idstr+=','+$(this).val();
			});
			$.post('<?=Url::to(['/order/order/signpayed'])?>',{orders:idstr},function(result){
				bootbox.alert(result);
				location.reload();
			});
			break;
		case 'reorder':
			var thisOrderList = [];
			$('input[name="order_id[]"]:checked').each(function(){
				thisOrderList.push($(this).val());
			});
			OrderCommon.reorder(thisOrderList);
			break;
		case 'givefeedback':
			document.a.target="_blank";
			document.a.action="<?=Url::to(['/order/order/feedback'])?>";
			document.a.submit();
			document.a.action="";
			break;
		case 'dispute':
			document.a.target="_blank";
			document.a.action="<?=Url::to(['/order/order/dispute'])?>";
			document.a.submit();
			document.a.action="";
			break;
		case 'mergeorder':
			document.a.target="_blank";
			document.a.action="<?=Url::to(['/order/order/mergeorder'])?>";
			document.a.submit();
			document.a.action="";
			break;
		case 'skipMerge':
			idstr = [];
			$('input[name="order_id[]"]:checked').each(function(){
				idstr.push($(this).val());
			});
			
			$.post('<?=Url::to(['/order/order/skipmerge'])?>',{orders:idstr},function(result){
				bootbox.alert({  
		            buttons: {  
		                ok: {  
		                     label: '确认',  
		                     className: 'btn-myStyle'  
		                 }  
		             },  
		             message: result,  
		             callback: function() {  
		                 location.reload();
		             },  
		         });
				
			});
			break;
		case 'changeWHSM':
			var thisOrderList =[];
			$('input[name="order_id[]"]:checked').each(function(){
				if ($(this).parents("tr:contains('已付款')").length == 0) return;
				thisOrderList.push($(this).val());
			});
			if (thisOrderList.length == 0){
				bootbox.alert('只能修改已付款状态的运输服务');
				return;
			}
			OrderCommon.showWarehouseAndShipmentMethodBox(thisOrderList);
			break;
		case 'changeshipmethod':
			var thisOrderList =[];
			idstr='';
			$('input[name="order_id[]"]:checked').each(function(){
				
				if ($(this).parents("tr:contains('已付款')").length == 0) return;
				
				thisOrderList.push($(this).val());
				if (idstr != '') idstr+=',';
				idstr+=$(this).val();
			});

			if (idstr ==''){
				bootbox.alert('只能修改已付款状态的运输服务');
				return;
			}

			var html  = '以下未发货订单已经被选中<br>'+idstr +'<br><br>请选择批量使用物流运输方式 ：<select name="change_shipping_method_code">'+$('select[name=demo_shipping_method_code]').html()+'</select>';
			bootbox.dialog({
				title: Translator.t("订单详细"),
				className: "order_info", 
				message: html,
				buttons:{
					Ok: {  
						label: Translator.t("确定"),  
						className: "btn-primary",  
						callback: function () { 
							return changeShipMethod(thisOrderList , $('select[name=change_shipping_method_code]').val());
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
			
			break;
		case 'outOfStock':
			idstr = [];
			$('input[name="order_id[]"]:checked').each(function(){
				idstr.push($(this).val());
			});
			
			$.post('<?=Url::to(['/order/order/outofstock'])?>',{orders:idstr},function(result){
				bootbox.alert({  
		            buttons: {  
		                ok: {  
		                     label: '确认',  
		                     className: 'btn-myStyle'  
		                 }  
		             },  
		             message: result,  
		             callback: function() {  
		                 location.reload();
		             },  
		         });
				
			});
			break;
		case 'calculat_profit':
			idstr='';
			$('input[name="order_id[]"]:checked').each(function(){
				idstr+=','+$(this).val();
			});
			$.showLoading();
			$.ajax({
				type: "POST",
					//dataType: 'json',
					url:'/order/order/profit-order', 
					data: {order_ids : idstr},
					success: function (result) {
						$.hideLoading();
						bootbox.dialog({
							className : "profit-order",
							//title: ''
							message: result,
						});
					},
					error: function(){
						$.hideLoading();
						bootbox.alert("出现错误，请联系客服求助...");
						return false;
					}
			});
			break;
		case 'ExternalDoprint':
			var thisOrderList =[];
			$('input[name="order_id[]"]:checked').each(function(){
				thisOrderList.push($(this).val());
			});
			OrderCommon.ExternalDoprint(thisOrderList);
			break;
		case 'delete_manual_order':
			idstr = [];
			$('input[name="order_id[]"]:checked').each(function(){
				idstr.push($(this).val());
			});
			OrderCommon.deleteManualOrder(idstr);
			break;
		default:
			return false;
			break;
	}
}
//更改物流方式

function changeShipMethod(orderIDList , shipmethod){
	$.ajax({
		type: "POST",
			dataType: 'json',
			url:'/order/order/changeshipmethod', 
			data: {orderIDList : orderIDList , shipmethod : shipmethod },
			success: function (result) {
				//bootbox.alert(result.message);
				if (result.success == false) 
					bootbox.alert(result.message);
				else{
					bootbox.alert({message:Translator.t("修改成功！") , callback: function() {  
		                window.location.reload(); 
		            },  
		            });
				}
				return false;
			},
			error: function(){
				bootbox.alert("Internal Error");
				return false;
			}
	});
	return false;
}

//单独操作
function doactionone(val,orderid){
	//如果没有选择订单，返回；
	if(val==""){
        bootbox.alert("请选择您的操作");return false;
    }
	if(orderid == ""){ bootbox.alert("订单号错误");return false;}
	switch(val){
		case 'setSyncShipComplete':
			var thisOrderList =[];
			thisOrderList.push(orderid);
			OrderCommon.setOrderSyncShipStatusComplete(thisOrderList);
			break;
		case 'changeWHSM':
			var thisOrderList =[];
			thisOrderList.push(orderid);
			OrderCommon.showWarehouseAndShipmentMethodBox(thisOrderList);
			break;
		case 'changeItemDeclarationInfo':
			var thisOrderList =[];
			thisOrderList.push(orderid);
			OrderCommon.showChangeItemDeclarationInfoBox(thisOrderList);
			break;
		case 'checkorder':
			$.post('<?=Url::to(['/order/order/checkorderstatus'])?>',{orders:orderid},function(result){
				bootbox.alert(result);
				location.reload();
			});
			break;
		case 'addMemo':
			var idstr = [];
			idstr.push(orderid);
			OrderCommon.showAddMemoBox(idstr);
			break;
		case 'editOrder':
			idstr = [];
			idstr.push(orderid);
			window.open("<?=Url::to(['/order/priceminister-order/edit'])?>"+"?orderid="+orderid,'_blank')
			break;
		case 'signshipped':
			window.open("<?=Url::to(['/order/order/signshipped'])?>"+"?order_id="+orderid,'_blank')
			break;
		case 'generateProduct':
			document.a.action="<?=Url::to(['/order/order/generateproduct'])?>"+"?order_id="+orderid;
			idstr = [];
			idstr.push(orderid);
			
			//遮罩
			$.maskLayer(true);
			$.ajax({
				url: "<?=Url::to(['/order/order/generateproduct'])?>",
				data: {
					orderids:idstr,
				},
				type: 'post',
				success: function(response) {
					 var r = $.parseJSON(response);
					 if(r.result){
						 var event = $.confirmBox('<p class="text-success">'+r.message+'</p>');
					 }else{
						 var event = $.confirmBox('<p class="text-warn">'+r.message+'</p>');
					 } 
					 event.then(function(){
					  // 确定,刷新页面
					  location.reload();
					},function(){
					  // 取消，关闭遮罩
					  $.maskLayer(false);
					});
				},
				error: function(XMLHttpRequest, textStatus) {
					$.maskLayer(false);
					$.alert('<p class="text-warn">网络不稳定.请求失败,请重试!</p>','danger');
				}
			})
			break;
		case 'suspendDelivery':
			idstr = [];
			idstr.push(orderid);
			
			$.post('<?=Url::to(['/order/order/suspenddelivery'])?>',{orders:idstr},function(result){
				bootbox.alert({  
		            buttons: {  
		                ok: {  
		                     label: '确认',  
		                     className: 'btn-myStyle'  
		                 }  
		             },  
		             message: result,  
		             callback: function() {  
		                 location.reload();
		             },  
		         });
				
			});
			break;
		case 'deleteorder':
			if(confirm('确定需要删除选中订单?平台订单可能会重新同步进入系统')){
				window.open("<?=Url::to(['/order/order/deleteorder'])?>"+"?order_id="+orderid,'_blank')
			}
			break;
		case 'cancelorder':
			idstr = [];
			idstr.push(orderid);
			$.post('<?=Url::to(['/order/order/cancelorder'])?>',{orders:idstr},function(result){
				bootbox.alert(result);
			});
			break;
		case 'signpayed':
			$.post('<?=Url::to(['/order/order/signpayed'])?>',{orders:orderid},function(result){
				bootbox.alert(result);
				location.reload();
			});
			break;
		case 'reorder':
			idstr = [];
			idstr.push(orderid);
			OrderCommon.reorder(idstr);
			break;
		case 'givefeedback':
			window.open("<?=Url::to(['/order/order/feedback'])?>"+"?order_id="+orderid,'_blank');
			break;
		case 'dispute':
			window.open("<?=Url::to(['/order/order/dispute'])?>"+"?order_id="+orderid,'_blank');
			break;
		case 'mergeorder':
			window.open("<?=Url::to(['/order/order/mergeorder'])?>"+"?order_id="+orderid,'_blank');
			break;
		case 'history':
			window.open("<?=Url::to(['/order/logshow/list'])?>"+"?orderid="+orderid,'_blank');
			break;
		case 'getorderno':
			OrderCommon.setShipmentMethod(orderid);
			break;
		case 'signwaitsend':
			OrderCommon.shipOrder(orderid);
			break;
		case 'invoiced':
			window.open("<?=Url::to(['/order/order/order-invoice'])?>"+"?order_id="+orderid,'_blank');
			break;
		case 'completecarrier':
			completecarrier(orderid);
			break;
		case 'outOfStock':
			var thisOrderList =[];
			thisOrderList.push(orderid);
			
			$.post('<?=Url::to(['/order/order/outofstock'])?>',{orders:thisOrderList},function(result){
				bootbox.alert({  
		            buttons: {  
		                ok: {  
		                     label: '确认',  
		                     className: 'btn-myStyle'  
		                 }  
		             },  
		             message: result,  
		             callback: function() {  
		                 location.reload();
		             },  
		         });
				
			});
			break;
		case 'ExternalDoprint':
			var thisOrderList =[];
			thisOrderList.push(orderid);
			OrderCommon.ExternalDoprint(thisOrderList);
			break;
		case 'delete_manual_order':
			idstr = [];
			idstr.push(orderid);
			OrderCommon.deleteManualOrder(idstr);
			break;
		default:
			return false;
			break;
	}
}
//导出订单
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

//移动订单状态到其他状态
function movestatus(val){
	if(val==""){
		bootbox.alert("请选择您的操作");return false;
    }
	if($('.ck:checked').length==0&&val!=''){
		bootbox.alert("请选择要操作的订单");return false;
    }
    var idstr='';
	$('input[name="order_id[]"]:checked').each(function(){
		idstr+=','+$(this).val();
	});
	$.post('<?=Url::to(['/order/order/movestatus'])?>',{orderids:idstr,status:val},function(result){
		bootbox.alert('操作已成功');
	});
}
//上传物流单号
function importordertracknum(){
	if($("#order_tracknum").val()){
		$.ajaxFileUpload({  
			 url:'<?=Url::to(['/order/order/importordertracknum'])?>',
		     fileElementId:'order_tracknum',
		     type:'post',
		     dataType:'json',
		     success: function (result){
			     if(result.ack=='failure'){
					bootbox.alert(result.message);
				 }else{
					bootbox.alert('操作已成功');
				 }
		     },  
			 error: function ( xhr , status , messages ){
				 bootbox.alert(messages);
		     }  
		});  
	}else{
		bootbox.alert("请添加文件");
	}
}
//修改订单的挂起状态
function changemanual(orderid,obj){
	$.post('<?=Url::to(['/order/order/changemanual'])?>',{orderid:orderid},function(result){
		if(result == 'success'){
			bootbox.alert('操作已成功');
			var str;
			str=$(obj).html();
			if(str.indexOf('取消挂起')>=0){
				$(obj).html('<span class="glyphicon glyphicon-open toggleMenuL" title="挂起"><\/span>');			
			}else{
				$(obj).html('<span class="glyphicon glyphicon-save toggleMenuL" title="取消挂起"><\/span>');	
			}
		}else{
			bootbox.alert(result);
		}
	});
}

//添加自定义标签
function setusertab(orderid,tabobj){
	var tabid = $(tabobj).val();
	$.post('<?=Url::to(['/order/order/setusertab'])?>',{orderid:orderid,tabid:tabid},function(result){
		if(result == 'success'){
			bootbox.alert('操作已成功');
		}else{
			bootbox.alert(result);
		}
	});
}

//添加备注
function updatedesc(orderid,obj){
	var desc=$(obj).prev();
    var oiid=$(obj).attr('oiid');
	var html="<textarea name='desc' style='width:200xp;height:60px'>"+desc.text()+"</textarea><input type='button' onclick='ajaxdesc(this)' value='修改' oiid='"+oiid+"'>";	
    desc.html(html);
    $(obj).toggle();
}
function ajaxdesc(obj){
	 var obj=$(obj);
	 var desc=$(obj).prev().val();
	 var oiid=$(obj).attr('oiid');
	  $.post('<?=Url::to(['/order/order/ajaxdesc'])?>',{desc:desc,oiid:oiid},function(r){
		  retArray=$.parseJSON(r);
		  if(retArray['result']){
		      obj.parent().next().toggle();
		      var html="<font color='red'>"+desc+"</font> <span id='showresult' style='background:yellow;'>"+retArray['message']+"</span>"
		      obj.parent().html(html);
		      setTimeout("showresult()",3000);
		  }else{
		      alert(retArray['message']);
		  }
	  })
}
function completecarrier(orderid){
	$.showLoading();
	var url = '/carrier/default/completecarrier?order_id='+orderid;
	$.get(url,function (data){
			$.hideLoading();
			var retinfo = eval('(' + data + ')');
			if (retinfo["code"]=="fail")  {
				bootbox.alert({title:Translator.t('错误提示') , message:retinfo["message"] });	
				return false;
			}else{
				bootbox.alert({title:Translator.t('提示'),message:retinfo["message"],callback:function(){
					window.location.reload();
					$.showLoading();
				}});
			}
		}
	);
}
function showresult(){
    $('#showresult').remove();
}

function dosearch(name,val){
	$('#'+name).val(val);
	document.form1.submit();
}
//添加备注函数结束

function cleform(){
	$(':input','#form1').not(':button, :submit, :reset, :hidden').val('').removeAttr('checked').removeAttr('selected');
	$('select[name=keys]').val('order_id');
	$('select[name=timetype]').val('soldtime');
	$('select[name=ordersort]').val('soldtime');
	$('select[name=ordersorttype]').val('desc');
}

function closeReminder(){
	var child=document.getElementById("pm-oms-reminder-content");
	var reminder=document.getElementById("pm-oms-reminder");
	reminder.removeChild(child);
}

function closeReminderToday(){
	var child=document.getElementById("pm-oms-reminder-content");
	var reminder=document.getElementById("pm-oms-reminder");
	$.post('<?=Url::to(['/order/priceminister-order/close-reminder'])?>',{},function(result){
		if(result == 'success'){
			reminder.removeChild(child);
		}else{
			console(result);
			reminder.removeChild(child);
		}
	});
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

function OmsViewTracker(obj,num){
	var s_trackingNo = $(obj).has('.text-success');
	if(typeof(s_trackingNo)!=='undefined' && s_trackingNo.length>0){
		var tracking_info_type=$(obj).data("info-type");
		if(tracking_info_type !== '17track'){
			var qtip = $(obj).find(".order-info").data('hasqtip');
			if(typeof(qtip)=='undefined')
				return false;
			var opened = $("#qtip-"+qtip).css("display");
			if(opened=='block')
				return true;
			$.ajax({
				type: "POST",
				dataType: 'json',
				url:'/order/order/oms-view-tracker', 
				data: {invoker: 'Priceminister-Oms'},
				success: function (result) {
					return true;
				},
				error :function () {
					return false;
				}
			});
		}else{
			$.showLoading();
			show17Track(num);
			$.hideLoading();
			$.ajax({
				type: "POST",
				dataType: 'json',
				url:'/order/order/oms-view-tracker', 
				data: {invoker: 'Priceminister-Oms'},
				success: function (result) {
					return true;
				},
				error :function () {
					return false;
				}
			});
		}
	}
}

function show17Track(num){
	$.ajax({
		type: "GET",
		url:'/tracking/tracking/show17-track-tracking-info?num='+num,
		success: function (result) {
			$.hideLoading();
			bootbox.dialog({
				className : "17track-trackin-info-win",
				title: Translator.t('17track查询结果'),
				message: result,
			});
		},
		error :function () {
			$.hideLoading();
			bootbox.alert(Translator.t('操作失败,后台返回异常'));
			return false;
		}
	});
}

function doTrack(num) {
    if(num===""){
        alert("Enter your number."); 
        return;
    }
    YQV5.trackSingle({
        YQ_ContainerId:"YQContainer",       //必须，指定承载内容的容器ID。
        YQ_Height:400,      //可选，指定查询结果高度，最大高度为800px，默认撑满容器。
        YQ_Fc:"0",       //可选，指定运输商，默认为自动识别。
        YQ_Lang:"zh-cn",       //可选，指定UI语言，默认根据浏览器自动识别。
        YQ_Num:num     //必须，指定要查询的单号。
    });
}

function showDashBoard(autoShow){
	$.showLoading();
	$.ajax({
		type: "GET",
		url:'/order/priceminister-order/user-dash-board?autoShow='+autoShow, 
		success: function (result) {
			$.hideLoading();
			$("#dash-board-enter").toggle();
			bootbox.dialog({
				className : "dash-board",
				title: Translator.t('Priceminister订单监测面板'),
				message: result,
				closeButton:false,
			});
			return true;
		},
		error :function () {
			$.hideLoading();
			bootbox.alert(Translator.t('打开Priceminister订单监测面板失败'));
			return false;
		}
	});
}

function ignoreTrackingNo(order_id,track_no){
	$.showLoading();
	$.ajax({
		type: "GET",
		url:'/order/order/ignore-tracking-no?order_id='+order_id+'&track_no='+track_no,
		dataType:'json',
		success: function (result) {
			$.hideLoading();
			if(result.success===true){
				bootbox.alert({
		            message: '操作成功',  
		            callback: function() {  
		            	window.location.reload();
		            }
				});
			}else{
				bootbox.alert(result.message);
				return false;
			}
		},
		error :function () {
			$.hideLoading();
			bootbox.alert(Translator.t('操作失败,后台返回异常'));
			return false;
		}
	});
}

function syncOneOrderStatus(order_id){
	$.showLoading();
	$.ajax({
		type: "GET",
		url:'/order/priceminister-order/sync-one-order-status?order_id='+order_id,
		dataType:'json',
		success: function (result) {
			$.hideLoading();
			if(result.success===true){
				bootbox.alert({
		            message: '操作成功',  
		            callback: function() {  
		            	window.location.reload();
		            }
				});
			}else{
				bootbox.alert(result.message);
				return false;
			}
		},
		error :function () {
			$.hideLoading();
			bootbox.alert(Translator.t('操作失败,后台返回异常'));
			return false;
		}
	});
}

function syncAllUnClosedOrderStatus(){
	$.showLoading();
	$.ajax({
		type: "GET",
		url:'/order/priceminister-order/sync-all-un-closed-order-status',
		dataType:'json',
		success: function (result) {
			$.hideLoading();
			if(result.success===true){
				bootbox.alert({
		            message: '操作成功,后台马上回进行同步,数分钟后可刷新页面查看结果',  
		            callback: function() {  
		            	window.location.reload();
		            }
				});
			}else{
				bootbox.alert(result.message);
				return false;
			}
		},
		error :function () {
			$.hideLoading();
			bootbox.alert(Translator.t('操作失败,后台返回异常'));
			return false;
		}
	});
}

function showImportantChangeTip(){
	$.showLoading();
	$.ajax({
		type: "GET",
		url:'/order/priceminister-order/important-change', 
		success: function (result) {
			$.hideLoading();
			bootbox.dialog({
				className : "important-change-tip-win",
				title: Translator.t('重要通知'),
				message: result,
				closeButton:true,
			});
			return true;
		},
		error :function () {
			$.hideLoading();
			bootbox.alert(Translator.t('打开重要改动提示窗口失败！'));
			return false;
		}
	});
}
function setAutoAccept(){
	var auto_accept = $("input[name='auto_accept']:checked").val();
	$.showLoading();
	$.ajax({
		type: "POST",
		dataType:'json',
		url:'/order/priceminister-order/set-auto-accept-order',
		data:{auto_accept:auto_accept},
		success: function (result) {
			$.hideLoading();
			if(result.success==true){
				bootbox.alert(Translator.t('设置成功，即将刷新页面'));
				window.location.reload();
			}else{
				bootbox.alert(result.message);
				return false;
			}
		},
		error :function () {
			$.hideLoading();
			bootbox.alert(Translator.t('操作失败,后台返回异常'));
			return false;
		}
	});
}
</script>