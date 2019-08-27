<?php
use yii\helpers\Html;
use yii\grid\GridView;
use yii\widgets\LinkPager;
use yii\helpers\Url;
use yii\jui\JuiAsset;
use yii\web\UrlManager;
use yii\jui\DatePicker;
use eagle\modules\order\models\OdOrder;
use eagle\modules\order\models\OdOrderItem;
use eagle\modules\carrier\apihelpers\CarrierApiHelper;
use eagle\modules\inventory\helpers\InventoryApiHelper;
use eagle\modules\util\helpers\TranslateHelper;
use eagle\modules\order\helpers\OrderTagHelper;
use eagle\modules\carrier\helpers\CarrierHelper;
use eagle\widgets\SizePager;
use eagle\models\SaasBonanzaUser;
use eagle\models\catalog\Product;
use eagle\modules\listing\models\BonanzaOfferList;
use Zend\Code\Scanner\Util;
use eagle\modules\util\helpers\StandardConst;
use eagle\modules\order\helpers\BonanzaOrderInterface;
use eagle\modules\tracking\helpers\TrackingApiHelper;
use eagle\modules\tracking\helpers\TrackingHelper;
use eagle\modules\order\helpers\BonanzaOrderHelper;
use eagle\modules\tracking\models\Tracking;
use eagle\modules\util\helpers\ImageCacherHelper;
use eagle\modules\tracking\helpers\CarrierTypeOfTrackNumber;
use eagle\modules\carrier\helpers\CarrierOpenHelper;
use eagle\modules\order\helpers\OrderFrontHelper;
use eagle\modules\order\helpers\OrderHelper;
use eagle\modules\util\helpers\SysBaseInfoHelper;
use eagle\modules\order\helpers\OrderListV3Helper;
use eagle\modules\util\helpers\RedisHelper;

// $this->registerJsFile(\Yii::getAlias('@web')."/js/project/order/OrderTag.js", ['depends' => ['yii\web\JqueryAsset']]);
// $this->registerJsFile(\Yii::getAlias('@web')."/js/project/order/orderOrderList.js", ['depends' => ['yii\web\JqueryAsset']]);
// $this->registerJsFile(\Yii::getAlias('@web')."/js/origin_ajaxfileupload.js", ['depends' => ['yii\web\JqueryAsset']]);
// $this->registerJsFile(\Yii::getAlias('@web')."/js/project/order/profit/import_file.js", ['depends' => ['yii\web\JqueryAsset']]);
// $this->registerJsFile(\Yii::getAlias('@web')."/js/project/order/orderCommon.js", ['depends' => ['yii\web\JqueryAsset']]);
// $this->registerJs("OrderTag.TagClassList=".json_encode($tag_class_list).";" , \yii\web\View::POS_READY);
// $this->registerJs("OrderList.initClickTip();" , \yii\web\View::POS_READY);
$baseUrl = \Yii::$app->urlManager->baseUrl . '/';
$this->registerCssFile($baseUrl."css/message/customer_message.css");

$this->registerJsFile(\Yii::getAlias('@web')."/js/project/order/OrderTag.js", ['depends' => ['yii\web\JqueryAsset']]);
$this->registerJsFile(\Yii::getAlias('@web')."/js/project/order/orderOrderList.js", ['depends' => ['yii\web\JqueryAsset']]);
$this->registerJsFile(\Yii::getAlias('@web')."/js/origin_ajaxfileupload.js", ['depends' => ['yii\web\JqueryAsset']]);
$this->registerJsFile(\Yii::getAlias('@web')."/js/project/message/customer_message.js", ['depends' => ['yii\web\JqueryAsset']]);
$this->registerCssFile(\Yii::getAlias('@web').'/css/order/order.css');
$this->registerJs("OrderTag.TagClassList=".json_encode($tag_class_list).";" , \yii\web\View::POS_READY);
$this->registerJsFile(\Yii::getAlias('@web')."/js/project/order/orderCommon.js?v=".OrderHelper::$OrderCommonJSVersion, ['depends' => ['yii\web\JqueryAsset']]);
if (!empty($_REQUEST['country'])){
    $this->registerJs("OrderCommon.currentNation=".json_encode(array_fill_keys(explode(',', $_REQUEST['country']),true)).";" , \yii\web\View::POS_READY);
}
$this->registerJs("OrderList.initClickTip();" , \yii\web\View::POS_READY);

$this->registerJs("OrderCommon.NationList=".json_encode(@$countrys).";" , \yii\web\View::POS_READY);
$this->registerJs("OrderCommon.NationMapping=".json_encode(@$country_mapping).";" , \yii\web\View::POS_READY);
$this->registerJs("OrderCommon.initNationBox($('div[name=div-select-nation][data-role-id=0]'));" , \yii\web\View::POS_READY);

$this->registerJs("OrderCommon.customCondition=".json_encode(@$counter['custom_condition']).";" , \yii\web\View::POS_READY);

$uid = \Yii::$app->user->id;
//$next_show = \Yii::$app->redis->hget('BonanzaOms_DashBoard',"user_$uid".".next_show");
$next_show = RedisHelper::RedisGet('BonanzaOms_DashBoard',"user_$uid".".next_show" );
$show=true;
if(!empty($next_show)){
	if(time()<strtotime($next_show))
		$show=false;
}
if(!empty($_REQUEST))
	$show=false;

if($show)
	$this->registerJs("showDashBoard(1);" , \yii\web\View::POS_READY);

$this->registerJs("$('.prod_img').popover();" , \yii\web\View::POS_READY);
$this->registerJs("$('.icon-ignore_search').popover();" , \yii\web\View::POS_READY);
$this->registerJs("$('.profit_detail').popover();" , \yii\web\View::POS_READY);
$this->registerJs("$('.list_profit_tip').popover();" , \yii\web\View::POS_READY);

$showMergeOrder = 0;
if (200 == @$_REQUEST['order_status'] && 223 == @$_REQUEST['exception_status']){
    $this->registerJs("OrderCommon.showMergeRow();" , \yii\web\View::POS_READY);
    $nowMd5 = "";
    $showMergeOrder = 1;
}

$orderStatus21 = OdOrder::getOrderStatus('oms21_bonanza'); // oms 2.1 的订单状态

$bn_source_status_mapping = [
    'checkoutStatus'=>[
        'Complete'=>'Complete（付款完成）',
        'Incomplete'=>'Incomplete（订单失败）',
        'InProcess'=>'InProcess（付款中）',
        'Invoiced'=>'Invoiced（卖家已接受订单）',
        'Pending'=>'Pending（待接受）',
    ],
    'orderStatus'=>[
        'Active'=>'Active（订单尚未检出）',
        'Cancelled'=>'Cancelled（订单取消）',
        'Completed'=>'Completed（发货中）',
        'Incomplete'=>'Incomplete（付款失败）',
        'InProcess'=>'InProcess（付款中）',
        'Invoiced'=>'Invoiced（等待买家付款）',
        'Proposed'=>'Proposed（等待卖家接受）',
        'Shipped'=>'Shipped（已完成）',
    ],
];

$non17Track = CarrierTypeOfTrackNumber::getNon17TrackExpressCode();

$tmpCustomsort = Odorder::$customsort;
 
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
/* height: 35px; */
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
.cd_fbc_inco{
	width:15px;
	height:15px;
	background:url("/images/bonanza/clogpicto.jpg") no-repeat;
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
#cd-oms-reminder-content{
	left: 0px;
	padding: 5px;
    border: 2px solid transparent;
    border-radius: 5px;
	float: left;
    width: 100%;
	padding-bottom: 10px;
}
#cd-oms-reminder-close{
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
#cd-oms-reminder-close-day{
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
.cd-oms-weird-status-wfs{
	background: url(/images/bonanza/bonanza_icon.png) no-repeat -1px -1627px;
    background-size: 100%;
    float: left;
    width: 18px;
    height: 18px;
}
td .popover{
	max-width: inherit;
    max-height: inherit;
}
.oms_list_profit input{

}
.text-success{
	color: #2ecc71!important;	
}
.text-warning{
	color: #8a6d3b!important;	
}
</style>	
<div class="tracking-index col2-layout">
<?=$this->render('_leftmenu',['counter'=>$counter]);?>


<div class="hidden" id="dash-board-enter" onclick="showDashBoard(0)" style="background-color:#374655;color: white;" title="展开dash-board">展开订单监测面板</div>

<div class="content-wrapper" >
	<div>
		<?php 
	     $problemAccountNames=[];
		 $allAccounts=[];
		 $problemAccounts = BonanzaOrderInterface::getUserAccountProblems($uid); 
		 if(!empty($problemAccounts['order_retrieve_failed'])){ 
		     foreach ($problemAccounts['order_retrieve_failed'] as $account){
		         $problemAccountNames["name"] = $account['store_name'];
		         $problemAccountNames["order_retrieve_message"] = $account['order_retrieve_message'];
		         $allAccounts[] = $problemAccountNames;
		     }
		 }
		?>
		<!-- 账号异常提示区域  -->
		<?php if(!empty($allAccounts)):?>
		<div class="alert alert-danger" role="alert" style="width:100%;">
		<?php foreach ($allAccounts as $detailAccounts):?>
			<span>您绑定的Bonanza账号：<?php echo $detailAccounts["name"];?>获取最近订单失败：<?php echo $detailAccounts["order_retrieve_message"];?></span><br />
		<?php endforeach;?>
		</div> 
		<?php endif;?>
	

	
<!-- ----------------------------------已付款订单分类筛选 start--------------------------------------------------------------------------------------------- -->	
	<?php 
		if (@$_REQUEST['order_status'] == 200 || in_array(@$_REQUEST['exception_status'], ['0','210','203','222','202','223','299']) ):	
		echo '<ul class="clearfix"><li style="float: left;line-height: 22px;">订单类型：</li><li style="float: left;line-height: 22px;">';
		echo OrderFrontHelper::displayOrderPaidProcessHtml($counter,'bonanza',[(string)OdOrder::PAY_PENDING]);
		echo '</li><li class="clear-both"></li></ul>';
		endif;
	?>
<!-- ----------------------------------已付款订单分类筛选 end--------------------------------------------------------------------------------------------- -->
	
<!-- ----------------------------------搜索区域 start--------------------------------------------------------------------------------------------- -->	
	<div>
		<form class="form-inline" id="form1" name="form1" action="/order/bonanza-order/list" method="post">
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
		<?php //echo Html::dropDownList('selleruserid',@$_REQUEST['selleruserid'],$bonanzaUsersDropdownList,['class'=>'iv-input','id'=>'selleruserid','style'=>'margin:0px;width:150px','prompt'=>'卖家账号'])?>
			
			<?php
			//店铺查找代码 S
			?>
			<input type="hidden" name ="selleruserid_combined" id="selleruserid_combined" value="<?php echo isset($_REQUEST['selleruserid_combined'])?$_REQUEST['selleruserid_combined']:'';?>">
			<?php
			$omsPlatformFinds = array();
// 			$omsPlatformFinds[] = array('event'=>'OrderCommon.order_platform_find(\'oms\',\'bonanza\',\'\')','label'=>'全部');
			
			if(count($bonanzaUsersDropdownList) > 0){
				$bonanzaUsersDropdownList['select_shops_xlb'] = '选择账号';
	
				foreach ($bonanzaUsersDropdownList as $tmp_selleruserKey => $tmp_selleruserid){
					$omsPlatformFinds[] = array('event'=>'OrderCommon.order_platform_find(\'oms\',\'bonanza\',\''.$tmp_selleruserKey.'\')','label'=>$tmp_selleruserid);
				}
				
				$pcCombination = OrderListV3Helper::getPlatformCommonCombination(array('type'=>'oms','platform'=>'bonanza'));
				if(count($pcCombination) > 0){
					foreach ($pcCombination as $pcCombination_K => $pcCombination_V){
						$omsPlatformFinds[] = array('event'=>'OrderCommon.order_platform_find(\'oms\',\'bonanza\',\''.'com-'.$pcCombination_K.'-com'.'\')',
							'label'=>'组合-'.$pcCombination_K, 'is_combined'=>true, 'combined_event'=>'OrderCommon.platformcommon_remove(this,\'oms\',\'bonanza\',\''.$pcCombination_K.'\')');
					}
				}
			}
			echo OrderListV3Helper::getDropdownToggleHtml('卖家账号', $omsPlatformFinds);
			if(!empty($_REQUEST['selleruserid_combined'])){
				echo '<span onclick="OrderCommon.order_platform_find(\'oms\',\'bonanza\',\'\')" class="glyphicon glyphicon-remove" style="cursor:pointer;font-size: 10px;line-height: 20px;color: red;margin-right:5px;" aria-hidden="true" data-toggle="tooltip" data-placement="top" data-html="true" title="" data-original-title="清空卖家账号查询条件"></span>';
			}
			//店铺查找代码 E
			?>
			
			<div class="input-group iv-input">
		        <?php $sel = [
		        	'order_source_order_id'=>'Bonanza订单号',
					'sku'=>'SKU',
					'tracknum'=>'物流号',
					'buyerid'=>'买家账号',
		        	'consignee'=>'买家姓名',
					'order_id'=>'小老板单号',
					'root_sku'=>'配对SKU',
					'product_name'=>'产品标题',
				]?>
				<?=Html::dropDownList('keys',@$_REQUEST['keys'],$sel,['class'=>'iv-input','style'=>'width:150px;margin:0px'])?>
		      	<?=Html::textInput('searchval',@$_REQUEST['searchval'],['class'=>'iv-input','id'=>'num','style'=>'width:150px','placeholder'=>'多个请用分号隔开'])?>
		      	
		    </div>
		    <?=Html::checkbox('fuzzy',@$_REQUEST['fuzzy'],['label'=>TranslateHelper::t('模糊搜索')])?>
		    
		    <a id="simplesearch" href="#" style="font-size:12px;text-decoration:none;" onclick="mutisearch();">高级搜索<span class="glyphicon glyphicon-menu-down"></span></a>	
		    <?=Html::submitButton('搜索',['class'=>"iv-btn btn-search btn-spacing-middle",'id'=>'search'])?>
		    <a target="_blank"  title="手工订单" class="iv-btn btn-important" href="/order/order/manual-order-box?platform=bonanza" >手工订单</a>
		    <!----------------------------------------------------------- 同步情况 ----------------------------------------------------------->
			<?php if (!empty($_REQUEST['menu_select']) && $_REQUEST['menu_select'] =='all'):?>
			<div class="pull-right" style="height: 40px;">
				<?=Html::button(TranslateHelper::t('同步情况'),['class'=>"iv-btn btn-important",'onclick'=>"OrderCommon.showAccountSyncInfo(this);",'name'=>'btn_account_sync_info','data-url'=>'/order/bonanza-order/order-sync-info'])?>
			</div>
			<?php endif;?>
		    
		    <!--  
	    	<?=Html::button('重置',['class'=>"iv-btn btn-search btn-spacing-middle",'onclick'=>"javascript:cleform();"])?>
            -->
	    	 
	    	<!--
	    	<?php
	    	if (!empty($counter['custom_condition'])){
	    		$sel_custom_condition = array_merge(['加载常用筛选'] , array_keys($counter['custom_condition']));
	    	}else{
	    		$sel_custom_condition =['0'=>'加载常用筛选'];
	    	}
	    	
	    	echo Html::dropDownList('sel_custom_condition',@$_REQUEST['sel_custom_condition'],$sel_custom_condition,['class'=>'iv-input'])?>
	    	<?=Html::button('保存为常用筛选',['class'=>"iv-btn btn-search",'onclick'=>"showCustomConditionDialog()",'name'=>'btn_save_custom_condition'])?>
	    	-->
	    	<div class="mutisearch" <?php if ($showsearch!='1'){?>style="display: none;"<?php }?>>
			<!-- ----------------------------------第二行--------------------------------------------------------------------------------------------- -->
	    	<div style="margin:20px 0px 0px 0px">
			<div class="input-group"  name="div-select-nation"  data-role-id="0"  style='margin:0px'>
				<?=Html::textInput('country',@$_REQUEST['country'],['class'=>'iv-input','placeholder'=>'收件人国家','id'=>'country','style'=>'width:200px;margin:0px'])?>
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
			<?=Html::dropDownList('order_source_status_checkoutStatus',@$_REQUEST['order_source_status_checkoutStatus'],$bn_source_status_mapping["checkoutStatus"],['class'=>'iv-input','prompt'=>'Bonanza原始状态(checkoutStatus)','id'=>'order_source_status_checkoutStatus','style'=>'width:200px;margin:0px'])?>
			<?=Html::dropDownList('order_source_status_orderStatus',@$_REQUEST['order_source_status_orderStatus'],$bn_source_status_mapping["orderStatus"],['class'=>'iv-input','prompt'=>'Bonanza原始状态(orderStatus)','id'=>'order_source_status_orderStatus','style'=>'width:200px;margin:0px'])?>
			 <?php $PayOrderTypeList = [''=>'已付款订单类型'];
				$PayOrderTypeList+=Odorder::$payOrderType ;
				$PayOrderTypeList[OdOrder::PAY_EXCEPTION] = '待合并';
				?>
			 <?=Html::dropDownList('pay_order_type',@$_REQUEST['pay_order_type'],$PayOrderTypeList,['class'=>'iv-input','style'=>'width:200px;margin:0px'])?>
			 
			<?php $reorderTypeList = [''=>'重新发货类型'];
			$reorderTypeList+=Odorder::$reorderType ;?>
			<?=Html::dropDownList('reorder_type',@$_REQUEST['reorder_type'],$reorderTypeList,['class'=>'iv-input','style'=>'width:200px;margin:0px'])?>
			
			 <?php 
// 			    $exceptionStatusList = [''=>'异常状态'];
// 				$exceptionStatusList+=Odorder::$exceptionstatus ;
// 				unset($exceptionStatusList['201']);//有留言
// 				unset($exceptionStatusList['299']);//可发货
// 				$weirdstatus=BonanzaOrderHelper::$CD_OMS_WEIRD_STATUS;
			 ?>
			 <?php 
// 			 echo Html::dropDownList('exception_status',@$_REQUEST['exception_status'],$exceptionStatusList,['class'=>'iv-input','style'=>'width:200px;margin:0px'])
			 ?>
			 
			 <?php 
// 			 echo Html::dropDownList('weird_status',@$_REQUEST['weird_status'],$weirdstatus,['class'=>'iv-input','prompt'=>'操作异常标签','id'=>'weird_status','style'=>'width:250px;margin:0px']);
			 ?>
			 
			 </div>
			 <!-- ----------------------------------第四行--------------------------------------------------------------------------------------------- -->
			 <div style="margin:20px 0px 0px 0px">
			<?php 
			$warehouses = InventoryApiHelper::getWarehouseIdNameMap(true); 
			if (count($warehouses)>1){
				echo Html::dropDownList('cangku',@$_REQUEST['cangku'],$warehouses,['class'=>'iv-input','prompt'=>'仓库','style'=>'width:200px;margin:0px']);
			}
			$warehouses +=['-1'=>'未分配'];//table 中可能 有-1的值
			$orderCaptureList = ['N'=>'正常订单','Y'=>'手工订单'];
			echo Html::dropDownList('order_capture',@$_REQUEST['order_capture'],$orderCaptureList,['class'=>'iv-input','prompt'=>'全部','style'=>'width:70px;']);
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
// 			echo Html::checkbox('is_reverse',@$_REQUEST['is_reverse'],['label'=>TranslateHelper::t('取反')]);
			?>
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
			<!-- ----------------------------------虚拟发货 状态--------------------------------------------------------------------------------------------- -->
			
			<?php 
			if (@$_REQUEST['order_status'] != OdOrder::STATUS_CANCEL){
				\eagle\modules\order\helpers\OrderFrontHelper::displayOrderSyncShipStatusToolbar(@$_REQUEST['order_sync_ship_status'],'bonanza');
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
	
	<div style="clear: both;">
		<form name="a" id="a" action="" method="post">
		<div class="pull-left" style="height: 40px;">
		<!--
		<?=Html::button('刷新左侧统计',['class'=>"iv-btn btn-refresh",'onclick'=>"refreshLeftMenu()",'name'=>'btn_refresh_left_menu'])?>
		<span qtipkey="oms_refresh_left_menu_aliexpress" class ="click-to-tip"></span>
		-->
		<?php 
		if (isset($_REQUEST['order_status']) && $_REQUEST['order_status'] =='100'){
// 			echo Html::button('确认到款',['class'=>"iv-btn btn-important",'onclick'=>"javascript:doaction('signpayed');"]);
// 			echo "&nbsp;";
			echo Html::button('取消订单',['class'=>"iv-btn btn-important",'onclick'=>"javascript:doaction('cancelorder');"]);
		}elseif (isset($_REQUEST['order_status']) && $_REQUEST['order_status'] =='200' ){
			if(1 == 0){
			$PayBtnHtml = Html::button('指定运输服务',['class'=>"iv-btn btn-important",'onclick'=>"javascript:doaction('changeWHSM');"])."&nbsp;";
			$PayBtnHtml .= Html::button('修改报关信息',['class'=>"iv-btn btn-important",'onclick'=>"javascript:doaction('changeItemDeclarationInfo');"]). "&nbsp;";
			//$PayBtnHtml .= Html::button('申请物流号',['class'=>"iv-btn btn-important",'onclick'=>"OrderCommon.getTrackNo()"]). "&nbsp;";
			$PayBtnHtml .= Html::button('移入发货中',['class'=>"iv-btn btn-important",'onclick'=>"javascript:doaction('signwaitsend');"]). "&nbsp;";
			$PayBtnHtml .=  '<span data-qtipkey="oms_batch_ship_pack" class ="click-to-tip"><img style="cursor:pointer;padding-top:8px;" width="16" src="/images/questionMark.png"></span>&nbsp;';
			
			if (in_array(@$_REQUEST['exception_status'], ['223'])){
				echo Html::button(TranslateHelper::t('合并订单'),['class'=>"iv-btn btn-important",'onclick'=>"javascript:doaction('mergeorder');"]);echo "&nbsp;";
				echo '<span data-qtipkey="oms_order_exception_pending_merge_merge" class ="click-to-tip"><img style="cursor:pointer;padding-top:8px;" width="16" src="/images/questionMark.png"></span>&nbsp;';			  
				
// 				echo Html::button(TranslateHelper::t('不合并订单'),['class'=>"iv-btn btn-important",'onclick'=>"javascript:doaction('skipMerge');"]);echo "&nbsp;";
// 				echo '<span data-qtipkey="oms_order_exception_pending_merge_skip" class ="click-to-tip"><img style="cursor:pointer;padding-top:8px;" width="16" src="/images/questionMark.png"></span>&nbsp;';
				
				//echo Html::button(TranslateHelper::t('检测订单'),['class'=>"iv-btn btn-important",'onclick'=>"javascript:doaction('checkorder');"]);
				//echo '<span data-qtipkey="oms_order_status_check" class ="click-to-tip"><img style="cursor:pointer;padding-top:8px;" width="16" src="/images/questionMark.png"></span>&nbsp;';
			}elseif(!empty($_REQUEST['is_merge'])){
			    echo Html::button(TranslateHelper::t('取消合并'),['class'=>"iv-btn btn-important",'onclick'=>"javascript:doaction('cancelMergeOrder');"]).'&nbsp;';
			}elseif (in_array(@$_REQUEST['exception_status'], ['202'])){
				echo Html::button(TranslateHelper::t('分配运输服务'),['class'=>"iv-btn btn-important",'onclick'=>"javascript:doaction('changeWHSM');"]);echo "&nbsp;";
				echo '<span data-qtipkey="oms_order_exception_no_shipment_assign" class ="click-to-tip"><img style="cursor:pointer;padding-top:8px;" width="16" src="/images/questionMark.png"></span>&nbsp;';
				
				//echo Html::button(TranslateHelper::t('检测订单'),['class'=>"iv-btn btn-important",'onclick'=>"javascript:doaction('checkorder');"]);
				//echo '<span data-qtipkey="oms_order_status_check" class ="click-to-tip"><img style="cursor:pointer;padding-top:8px;" width="16" src="/images/questionMark.png"></span>&nbsp;';
			}elseif (in_array(@$_REQUEST['exception_status'], ['222'])){
				echo Html::button(TranslateHelper::t('库存处理'),['class'=>"iv-btn btn-important",'onclick'=>"javascript:doaction('stockManage');"]);echo "&nbsp;";
				echo '<span data-qtipkey="oms_order_exception_out_of_stock_manage" class ="click-to-tip"><img style="cursor:pointer;padding-top:8px;" width="16" src="/images/questionMark.png"></span>&nbsp;';
				
				//echo Html::button(TranslateHelper::t('转仓处理'),['class'=>"iv-btn btn-important",'onclick'=>"javascript:doaction('assignstock');"]);echo "&nbsp;";
				echo Html::button(TranslateHelper::t('标记缺货'),['class'=>"iv-btn btn-important",'onclick'=>"javascript:doaction('outOfStock');"]);echo "&nbsp;";
				echo '<span data-qtipkey="oms_order_exception_out_of_stock_mark" class ="click-to-tip"><img style="cursor:pointer;padding-top:8px;" width="16" src="/images/questionMark.png"></span>&nbsp;';
				
				//echo Html::button(TranslateHelper::t('检测订单'),['class'=>"iv-btn btn-important",'onclick'=>"javascript:doaction('checkorder');"]);
				//echo '<span data-qtipkey="oms_order_status_check" class ="click-to-tip"><img style="cursor:pointer;padding-top:8px;" width="16" src="/images/questionMark.png"></span>&nbsp;';
			}elseif (in_array(@$_REQUEST['exception_status'], ['203'])){
				echo Html::button(TranslateHelper::t('分配仓库'),['class'=>"iv-btn btn-important",'onclick'=>"javascript:doaction('changeWHSM');"]);echo "&nbsp;";
				echo '<span data-qtipkey="oms_order_exception_no_warehouse_assign" class ="click-to-tip"><img style="cursor:pointer;padding-top:8px;" width="16" src="/images/questionMark.png"></span>&nbsp;';
				
				//echo Html::button(TranslateHelper::t('检测订单'),['class'=>"iv-btn btn-important",'onclick'=>"javascript:doaction('checkorder');"]);
				//echo '<span data-qtipkey="oms_order_status_check" class ="click-to-tip"><img style="cursor:pointer;padding-top:8px;" width="16" src="/images/questionMark.png"></span>&nbsp;';
			}elseif (in_array(@$_REQUEST['exception_status'], ['210'])){
				echo Html::button(TranslateHelper::t('生成SKU'),['class'=>"iv-btn btn-important",'onclick'=>"javascript:doaction('generateProduct');"]);echo "&nbsp;";
				echo '<span data-qtipkey="oms_order_exception_sku_not_exist_create" class ="click-to-tip"><img style="cursor:pointer;padding-top:8px;" width="16" src="/images/questionMark.png"></span>&nbsp;';
				
				//echo Html::button(TranslateHelper::t('检测订单'),['class'=>"iv-btn btn-important",'onclick'=>"javascript:doaction('checkorder');"]);
				//echo '<span data-qtipkey="oms_order_status_check" class ="click-to-tip"><img style="cursor:pointer;padding-top:8px;" width="16" src="/images/questionMark.png"></span>&nbsp;';
			}elseif (in_array(@$_REQUEST['exception_status'], ['299']) || @$_REQUEST['pay_order_type'] == 'ship'){
				//echo Html::button('提交发货',['class'=>"iv-btn btn-important",'onclick'=>"javascript:doaction('signwaitsend');"]);
				//echo '<span data-qtipkey="oms_batch_ship_pack" class ="click-to-tip"><img style="cursor:pointer;padding-top:8px;" width="16" src="/images/questionMark.png"></span>&nbsp;';
			}else{
				//echo Html::button('检测订单',['class'=>"iv-btn btn-important",'onclick'=>"javascript:doaction('checkorder');"]);
				//echo '<span data-qtipkey="oms_order_status_check" class ="click-to-tip"><img style="cursor:pointer;padding-top:8px;" width="16" src="/images/questionMark.png"></span>&nbsp;';
			}
			}else{
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
			}
			
			echo $PayBtnHtml;
			
		}
		
		if (@$_REQUEST['pay_order_type'] == 'reorder' || in_array(@$_REQUEST['exception_status'], ['203','222','202']) ){
			//echo Html::checkbox( 'chk_refresh_force','',['label'=>TranslateHelper::t('强制重新检测')]);
		}
		?>
	
		
		<?php 
			//echo Html::dropDownList('do','',$doarr,['onchange'=>"doaction2(this);",'class'=>'iv-input do','style'=>'width:200px;margin:0px']);
// 			echo Html::dropDownList('do','',$doarr,['onchange'=>"orderCommonV3.doaction2(this);",'class'=>'iv-input do','style'=>'width:200px;margin:0px']);
    		$doDownListHtml = OrderListV3Helper::getDropdownToggleHtml('批量操作', $doarr, 'orderCommonV3.doaction3');
    		echo $doDownListHtml;
		?> 
	
	
		<!--<?=Html::dropDownList('do','',$excelmodels,['onchange'=>"exportorder($(this).val());",'class'=>'iv-input do','style'=>'width:200px;margin:0px']);?>--> 
		<?php 
// 		Html::dropDownList('orderExcelprint','',['-1'=>'导出订单','0'=>'按勾选导出','1'=>'按所有页导出'],['onchange'=>"OrderCommon.orderExcelprint($(this).val())",'class'=>'iv-input do','style'=>'width:200px;margin:0px']);
		$excelActionItems = array('0'=>array('event'=>'OrderCommon.orderExcelprint(0)','label'=>'按勾选导出'), '1'=>array('event'=>'OrderCommon.orderExcelprint(1)','label'=>'按所有页导出'));
		$excelDownListHtml = OrderListV3Helper::getDropdownToggleHtml('导出订单', $excelActionItems);
		echo $excelDownListHtml;
		?>
		
		<?php $divTagHtml = "";
			$div_event_html = "";
		?>
		</div>
		<!-----------------------存款助手广告end--------------------------->
		<div class="pull-right">
		<?php
			if (isset($_REQUEST['order_status']) && $_REQUEST['order_status'] =='200' ){
				echo Html::button(TranslateHelper::t('导入跟踪号'),['class'=>"iv-btn btn-important",'onclick'=>"OrderCommon.importTrackNoBox('amazon')"]);
			}
		
			if(@$_GET['order_status'] == 100){
				echo '<div class="col-md-2"> <a class="btn" style="background-image: url(/images/cuikuan.png);width: 151px;height: 51px;margin-top:-9px;margin-left: 200px; " type="button" href="/assistant/rule/list"></a></div>';
			}
		?>
		</div>
		
		<!--<?=Html::button('手动同步订单',['class'=>'btn btn-primary','onclick'=>'dosyncorder();'])?>-->
		
        <!----------------------存款助手广告end------------------------------>
		<br><br>
			<?php
			 
				echo $this->render('../order/order_list_v3',[ 'carriers'=>$carriers, 'models'=>$models,'countrys'=>$countryArr,
						'warehouses'=>$warehouses, 'non17Track'=>$non17Track, 'tag_class_list'=>$tag_class_list,
						'current_order_status' => empty($_REQUEST['order_status']) ? '' : $_REQUEST['order_status'],
						'current_exception_status' => empty($_REQUEST['exception_status']) ? '' : $_REQUEST['exception_status'],
						'platform_type'=>'bonanza']);
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

<!-- ----------------------------------列表end--------------------------------------------------------------------------------------------- -->	
<?php if (@$_REQUEST['order_status'] == 200 ):?>
<div>
<ul>
	<li style="float: left;line-height: 60px;">你还可以查看以下处理阶段的订单：</li>
	<li style="float: left;"><?php echo OrderFrontHelper::displayOrderPaidProcessHtml($counter)?></li>
</ul>

</div>
<?php endif;?>	
	
<div style="clear: both;"></div>
</div></div>
<div style="display: none;">
<?=$divTagHtml?>
<?=$div_event_html?>
<div id="div_ShippingServices"><?=Html::dropDownList('demo_shipping_method_code',@$order->default_shipping_method_code,CarrierApiHelper::getShippingServices())?></div>

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

<div class="dash-board"></div>
<div class="profit-order"></div>
</div>
<div class="17track-trackin-info-win">  </div>
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
    	case 'cancelMergeOrder':
    		var thisOrderList =[];
    		$('input[name="order_id[]"]:checked').each(function(){
    			thisOrderList.push($(this).val());
    		});
    
    		OrderCommon.cancelMergeOrder(thisOrderList);
    		break;
    	case 'ExternalDoprint':
    		var thisOrderList =[];
    		
    		$('input[name="order_id[]"]:checked').each(function(){
    			thisOrderList.push($(this).val());
    		});
    		OrderCommon.ExternalDoprint(thisOrderList);
    		break;
    	case 'changeItemDeclarationInfo':
    		var thisOrderList =[];
    		
    		$('input[name="order_id[]"]:checked').each(function(){
    			thisOrderList.push($(this).val());
    		});
    
    		OrderCommon.showChangeItemDeclarationInfoBox(thisOrderList);
    		break;
    	case 'stockManage':
			var thisOrderList =[];
			
			$('input[name="order_id[]"]:checked').each(function(){
				thisOrderList.push($(this).val());
			});
			
			OrderCommon.showStockManageBox(thisOrderList);
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
			$.post('<?=Url::to(['/order/order/signwaitsend'])?>',{orders:idstr},function(result){
				bootbox.alert(result);
				location.reload();
			});
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
		case 'changeWHSM':
			var thisOrderList =[];
			$('input[name="order_id[]"]:checked').each(function(){
				if ($(this).parents("tr:contains('已付款')").length == 0) return;
				thisOrderList.push($(this).val());
			});
			if (thisOrderList.length == 0){
				bootbox.alert('只能修改已付款状态的物流方式');
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
				bootbox.alert('只能修改已付款状态的物流方式');
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
		case 'getEmail':
			idstr='';
			$('input[name="order_id[]"]:checked').each(function(){
				idstr+=','+$(this).val();
			});
			$.post('<?=Url::to(['/order/bonanza-order/get-email'])?>',{orderIds:idstr},function(result){
				location.reload();
			});
			break;
		case 'delete_manual_order':
			idstr = [];
			$('input[name="order_id[]"]:checked').each(function(){
				idstr.push($(this).val());
			});
			OrderCommon.deleteManualOrder(idstr);
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
					
		default:
			return false;
			break;
	}
}

function doactionone2(obj,orderid){
	var val = $(obj).val();
	if (val != ''){
		doactionone(val,orderid );
		$(obj).val('');
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
    	case 'cancelMergeOrder':
    		var thisOrderList =[];
    		thisOrderList.push(orderid);
    		OrderCommon.cancelMergeOrder(thisOrderList);
    		break;
    	case 'ExternalDoprint':
    		var thisOrderList =[];
    		
    		$('input[name="order_id[]"]:checked').each(function(){
    			thisOrderList.push($(this).val());
    		});
    		OrderCommon.ExternalDoprint(thisOrderList);
    		break;
    	case 'changeItemDeclarationInfo':
    		var thisOrderList =[];
    		
    		$('input[name="order_id[]"]:checked').each(function(){
    			thisOrderList.push($(this).val());
    		});
    
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
			window.open("<?=Url::to(['/order/bonanza-order/edit'])?>"+"?orderid="+orderid,'_blank')
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
		case 'changeWHSM':
			var thisOrderList =[];
			thisOrderList.push(orderid);
			OrderCommon.showWarehouseAndShipmentMethodBox(thisOrderList);
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
			window.open("<?=Url::to(['/order/order/feedback'])?>"+"?order_id="+orderid,'_blank')
			break;
		case 'dispute':
			window.open("<?=Url::to(['/order/order/dispute'])?>"+"?order_id="+orderid,'_blank')
			break;
		case 'mergeorder':
			window.open("<?=Url::to(['/order/order/mergeorder'])?>"+"?order_id="+orderid,'_blank')
			break;
		case 'history':
			window.open("<?=Url::to(['/order/logshow/list'])?>"+"?orderid="+orderid,'_blank');
			break;
		case 'getorderno':
			OrderCommon.setShipmentMethod(orderid);
			/*
			$.post('<?=Url::to(['/order/order/signwaitsend'])?>',{orders:orderid},function(result){
				bootbox.alert(result);
				location.reload();
			});
			window.open("<?=Url::to(['/carrier/carrierprocess/waitingpost'])?>"+"?order_id="+orderid,'_blank');
			//window.open("<?=Url::to(['/carrier/carrierprocess/waitingpost'])?>"+"?order_id="+orderid,'_blank');
			*/
			break;
		case 'signwaitsend':
			OrderCommon.shipOrder(orderid);
			/*
			$.post('<?=Url::to(['/order/order/signwaitsend'])?>',{orders:orderid},function(result){
				bootbox.alert(result);
				location.reload();
			});
			window.open("<?=Url::to(['/delivery/order/listdistributioninventory'])?>"+"?warehouse_id=0&delivery_status=0&distribution_inventory_status=2",'_blank');
			*/
			break;
		case 'invoiced':
			window.open("<?=Url::to(['/order/order/order-invoice'])?>"+"?order_id="+orderid,'_blank');
			break;
		case 'stockManage':
			var thisOrderList =[];
			thisOrderList.push(orderid);
			
			OrderCommon.showStockManageBox(thisOrderList);
			break;
	
		case 'skipMerge':
			var thisOrderList =[];
			thisOrderList.push(orderid);
			
			$.post('<?=Url::to(['/order/order/skipmerge'])?>',{orders:thisOrderList},function(result){
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
		case 'completecarrier':
			completecarrier(orderid);
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
	var child=document.getElementById("cd-oms-reminder-content");
	var reminder=document.getElementById("cd-oms-reminder");
	reminder.removeChild(child);
}

function closeReminderToday(){
	var child=document.getElementById("cd-oms-reminder-content");
	var reminder=document.getElementById("cd-oms-reminder");
	$.post('<?=Url::to(['/order/bonanza-order/close-reminder'])?>',{},function(result){
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
				data: {invoker: 'Bonanza-Oms'},
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
				data: {invoker: 'Bonanza-Oms'},
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

function showDashBoard(autoShow){
	$.showLoading();
	$.ajax({
		type: "GET",
		url:'/order/bonanza-order/user-dash-board?autoShow='+autoShow, 
		success: function (result) {
			$.hideLoading();
			$("#dash-board-enter").toggle();
			bootbox.dialog({
				className : "dash-board",
				title: Translator.t('Bonanza订单监控面板。该功能拉取validate状态订单，请在bonanza后台设置<a href="http://help.littleboss.com/faq_55.html" target="_blank" style="font-weight:bolder;color:blue;font-size:15px;font-style:oblique;background-color:greenyellow;">auto validate</a>功能'),
				message: result,
				closeButton:false,
			});
			return true;
		},
		error :function () {
			$.hideLoading();
			bootbox.alert(Translator.t('打开Bonanza订单监测面板失败'));
			return false;
		}
	});
}

function showImportantChangeTip(){
	$.showLoading();
	$.ajax({
		type: "GET",
		url:'/order/bonanza-order/important-change', 
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
		            },  
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

function selected_switch(){
	var selected = 0;
	var selected_profited = 0;
	var profitToalt = 0;
	
	$('input[name="order_id[]"]:checked').each(function(){
		selected++;
		//console.log($(this).parents("tr").find(".profit_detail")[0]);
		var profit = $(this).parents("tr").find(".profit_detail").attr('data-profit');
		//console.log(profit);
		if(typeof(profit)!=='undefined' && profit!==''){
			profit = parseFloat(profit);
			selected_profited++;
			profitToalt += profit;
		}
	});
	$("#selected_profit_calculated_rate").val(selected_profited +'/'+selected);
	profitToalt = Math.round(profitToalt*Math.pow(10,2))/Math.pow(10,2);
	$("#selected_profit_total").val(profitToalt);

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

function IsAccept(action,order_id,token){
	if(action == 'accept'){
		var action_name = "接受订单";
	}else if(action == 'refuse'){
		var action_name = "拒绝订单";
	}
	$.showLoading();
	$.ajax({
		type: "POST",
		url:'/order/bonanza-order/is-accept?action=' + action + '&orderId=' + order_id + '&token=' + token,
		dataType:'json',
		success: function (result) {
			$.hideLoading();
			if(result.success){
				bootbox.alert(action_name+'成功！');
			}else{
				bootbox.alert(result['message']);
			}
		},
		error :function () {
			$.hideLoading();
			bootbox.alert('操作失败,后台返回异常');
			return false;
		}
	});
}

//展开，收缩订单商品
function spreadorder(obj,id){
	if(typeof(id)=='undefined'){
		//未传参数进入，全部展开或收缩
		var html = $(obj).parent().html();
		if(html.indexOf('minus')!=-1){
			//当前应该为处理收缩,'-'号存在
			$('.xiangqing').hide();
			$(obj).parent().html('<span class="glyphicon glyphicon-plus" onclick="spreadorder(this);">');
			$('.orderspread').attr('class','orderspread glyphicon glyphicon-plus');
			return false;
		}else{
			//当前应该为处理收缩,'+'号存在
			$('.xiangqing').show();
			$(obj).parent().html('<span class="glyphicon glyphicon-minus" onclick="spreadorder(this);">');
			$('.orderspread').attr('class','orderspread glyphicon glyphicon-minus');
			return false;
		}
	}else{
		//有传订单ID进入，处理单个订单相应的详情
		var html = $(obj).parent().html();
		if(html.indexOf('minus')!=-1){
			//当前应该为处理收缩,'-'号存在
			$('.'+id).hide();
			$(obj).attr('class','orderspread glyphicon glyphicon-plus');
			return false;
		}else{
			//当前应该为处理收缩,'+'号存在
			$('.'+id).show();
			$(obj).attr('class','orderspread glyphicon glyphicon-minus');
			return false;
		}
	}
}
</script>