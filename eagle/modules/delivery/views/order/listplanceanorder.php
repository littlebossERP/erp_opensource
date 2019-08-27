<?php 
use yii\helpers\Html;
use yii\helpers\Url;
use eagle\modules\util\helpers\TranslateHelper;
use eagle\modules\carrier\models\SysCarrierParam;
use eagle\modules\carrier\apihelpers\CarrierApiHelper;
use eagle\modules\order\models\OdOrder;
use eagle\modules\order\helpers\OrderTagHelper;
use eagle\modules\util\helpers\ConfigHelper;
use eagle\modules\carrier\helpers\CarrierOpenHelper;
use eagle\modules\carrier\apihelpers\ApiHelper;
use eagle\modules\order\helpers\OrderHelper;
use eagle\modules\inventory\helpers\InventoryApiHelper;
use eagle\modules\order\helpers\OrderFrontHelper;
use eagle\modules\order\helpers\CdiscountOrderHelper;
use eagle\modules\permission\apihelpers\UserApiHelper;
use eagle\modules\order\helpers\OrderListV3Helper;
$baseUrl = \Yii::$app->urlManager->baseUrl . '/';
$tmp_js_version = '2.26';

$canAccessModule = UserApiHelper::checkModulePermission("delivery");
if(!$canAccessModule){
	exit("您没有权限进行发货处理");
}

//上传js
$this->registerJsFile(\Yii::getAlias('@web')."/js/origin_ajaxfileupload.js", ['depends' => ['yii\web\JqueryAsset']]);
$this->registerJsFile(\Yii::getAlias('@web')."/js/project/order/orderCommon.js?v=".$tmp_js_version, ['depends' => ['yii\web\JqueryAsset']]);
if (!empty($_REQUEST['consignee_country_code'])){
	$this->registerJs("OrderCommon.currentNation=".json_encode(array_fill_keys(explode(',', $_REQUEST['consignee_country_code']),true)).";" , \yii\web\View::POS_READY);
}
//国家搜索选择js
$this->registerJs("OrderCommon.NationList=".json_encode($countrys).";" , \yii\web\View::POS_READY);
$this->registerJs("OrderCommon.NationMapping=".json_encode($country_mapping).";" , \yii\web\View::POS_READY);
$this->registerJs("OrderCommon.initNationBox($('div[name=div-select-nation][data-role-id=0]'));" , \yii\web\View::POS_READY);
//常用搜索js
$this->registerJs("OrderCommon.customCondition=".json_encode($custom_condition).";" , \yii\web\View::POS_READY);
$this->registerJs("OrderCommon.initCustomCondtionSelect();" , \yii\web\View::POS_READY);
//自定义标签三个文件
$this->registerJsFile(\Yii::getAlias('@web')."/js/project/order/OrderTag.js", ['depends' => ['yii\web\JqueryAsset']]);
$this->registerJsFile(\Yii::getAlias('@web')."/js/project/order/orderOrderList.js", ['depends' => ['yii\web\JqueryAsset']]);
$this->registerJs("OrderTag.TagClassList=".json_encode(OrderTagHelper::getTagColorMapping()).";" , \yii\web\View::POS_READY);
//订单批量操作公用js文件
$this->registerJsFile($baseUrl."js/project/order/orderActionPublic.js?v=".$tmp_js_version, ['depends' => ['yii\jui\JuiAsset','yii\bootstrap\BootstrapPluginAsset']]);
//扫描发货js文件
$this->registerJsFile($baseUrl."js/project/delivery/order/showscanninglistdistributionbox.js?v=1.2", ['depends' => ['yii\jui\JuiAsset','yii\bootstrap\BootstrapPluginAsset']]);
$this->registerJsFile($baseUrl."js/project/delivery/order/showscanninglistdeliveryonebox.js", ['depends' => ['yii\jui\JuiAsset','yii\bootstrap\BootstrapPluginAsset']]);
$this->registerJsFile($baseUrl."js/project/delivery/order/showscanninglistdeliverychoosebox.js", ['depends' => ['yii\jui\JuiAsset','yii\bootstrap\BootstrapPluginAsset']]);
//自动打印
$this->registerJsFile($baseUrl."js/project/delivery/order/csPrint.js?v=1.1", ['depends' => ['yii\jui\JuiAsset','yii\bootstrap\BootstrapPluginAsset']]);

$this->registerJsFile(\Yii::getAlias('@web')."/js/project/carrier/carrierQtip.js", ['depends' => ['yii\web\JqueryAsset']]);
$this->registerJs("carrierQtip.initCarrierQtip('".json_encode(@$carrierQtips)."');" , \yii\web\View::POS_READY);

//$this->registerJsFile($baseUrl."js/project/delivery/order/listplanceanorder.js", ['depends' => ['yii\jui\JuiAsset','yii\bootstrap\BootstrapPluginAsset']]);

//引用ebay的图标
$this->registerCssFile(\Yii::getAlias('@web').'/css/order/order.css');

$this->registerJsFile ( \Yii::getAlias ( '@web' ) . "/js/project/carrier/carrieroperate/lbalionlinedelivery.js?v=".$tmp_js_version, ['depends' => ['yii\web\JqueryAsset']]);

$this->registerJsFile ( \Yii::getAlias ( '@web' ) . "/js/project/delivery/order/deliveryOms.js?v=".$tmp_js_version, ['depends' => ['yii\web\JqueryAsset']]);
$this->registerJs("OrderTag.init();" , \yii\web\View::POS_READY);
$this->registerJs("$.initQtip();" , \yii\web\View::POS_READY);

//剩余发货时间,显示问题
$this->registerJs("$(document).ajaxComplete(function( event, xhr, settings ) {
$('.fulfill_timeleft').each(function(){
		addTimer($(this).prop('id'),$(this).data('time'));
	});
})" , \yii\web\View::POS_READY);

$tmpOdtimetype = Odorder::$timetype;
$tmpCustomsort = Odorder::$customsort;
if($_REQUEST['carrier_step'] == 'UPLOAD'){
	unset($keys['tracknum']);
	unset($tmpOdtimetype['printtime']);
	unset($tmpOdtimetype['shiptime']);
	unset($tmpCustomsort['printtime']);
	unset($tmpCustomsort['shiptime']);
}
unset($tmpCustomsort['fulfill_deadline']);
$tmpCustomsort['deadlinetime'] = '剩余发货时间';  //fulfill_deadline

$user=\Yii::$app->user->identity;
$puid = $user->getParentUid();

$use_mode = isset($_REQUEST['use_mode']) ? $_REQUEST['use_mode'] : '';

if(!isset($query_condition['default_warehouse_id'])){
	$query_condition['default_warehouse_id'] = -2;
}

$this->registerJs("$('.prod_img').popover();" , \yii\web\View::POS_READY);

$this->registerJsFile($baseUrl."js/project/util/select_country.js?v=".$tmp_js_version, ['depends' => ['yii\jui\JuiAsset','yii\bootstrap\BootstrapPluginAsset']]);

$this->registerJsFile ( \Yii::getAlias ( '@web' ) . "/js/project/delivery/order/cainiao.js?v=".\eagle\modules\order\helpers\OrderListV3Helper::$OrderCommonJSV3, ['depends' => ['yii\web\JqueryAsset']]);
$this->registerJsFile(\Yii::getAlias('@web').'js/jquery.json-2.4.js', ['depends' => ['yii\jui\JuiAsset','yii\bootstrap\BootstrapPluginAsset']]);
$this->registerJs("doConnect();" , \yii\web\View::POS_READY);

?>
<style>
.popover{
	max-width: 500px;
	min-width:200px;
}

.modal-content{
		border-color:#797979;
	}
.modal-header .modal-title {
	background:#364655;color: white;
}
.btn_tag_qtip a {
  margin-right: 5px;
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
.div-input-group{
	  width: 150px;
  display: inline-block;
  vertical-align: middle;
	margin-top:1px;

}
.div_add_tag{
	width: 600px;
}
.table td, .table th {
    text-align: center;
	word-break:break-all;
}

.order-param-group , .prod-param-group{
	width: 280px;
	float: left;
	text-align: right;
	display: block;
	margin-right: 10px;
}

.service_a{
	margin: 0 20px 5px 0;
	line-height: 16px;
	display: inline-block;
}

.b_bold_delivery{
	font-weight: bold;
}

.bgColor5 {
    background-color: #eee;
}

.fColor2 {
    color: #999;
}

.p5 {
    padding: 5px;
}

.pLeft10 {
    padding-left: 10px;
}
.used_stock_info{
	color: #999999;
	margin-left: 5px;
	font-size: 10px;
}

.dropdown-submenu {
	position: relative;
}
.dropdown-submenu > .dropdown-menu {
	top: 0;
	left: 100%;
	margin-top: -6px;
	margin-left: -1px;
	-webkit-border-radius: 0 6px 6px 6px;
	-moz-border-radius: 0 6px 6px;
	border-radius: 0 6px 6px 6px;
}
.dropdown-submenu:hover > .dropdown-menu {
	display: block;
}
.dropdown-submenu > a:after {
	display: block;
	content: " ";
	float: right;
	width: 0;
	height: 0;
	border-color: transparent;
	border-style: solid;
	border-width: 5px 0 5px 5px;
	border-left-color: #ccc;
	margin-top: 5px;
	margin-right: -10px;
}
.dropdown-submenu:hover > a:after {
	border-left-color: #fff;
}
.dropdown-submenu.pull-left {
	float: none;
}
.dropdown-submenu.pull-left > .dropdown-menu {
	left: -100%;
	margin-left: 10px;
	-webkit-border-radius: 6px 0 6px 6px;
	-moz-border-radius: 6px 0 6px 6px;
	border-radius: 6px 0 6px 6px;
}

</style>
<?php 
	echo $this->render('//layouts/new/left_menu_2',['menu'=>[]]);
?>

<div class="content-wrapper" >
<?= ($use_mode == '') ? $order_nav_html : ''; ?>
<div style="margin-top:10px;">
<ul class="nav nav-pills">
  <?php if(($counter['listplanceanorder']['2']['all'] > 0) || ($counter['listplanceanorder']['3']['all'] > 0)){ ?>
  <li role="presentation" ><a class ="iv-btn <?= ($_REQUEST['carrier_type'] == 1)?'btn-important':''?>" onclick="changeCarrierType(1,'<?=$_REQUEST['default_warehouse_id'] ?>','','<?=$use_mode ?>')">API上传(<?=$counter['listplanceanorder']['1']['all']?>)</a></li>
  <?php } ?>
  <?php if($counter['listplanceanorder']['2']['all'] > 0){ ?>
  <li role="presentation" ><a class ="iv-btn <?= ($_REQUEST['carrier_type'] == 2)?'btn-important':''?>" onclick="changeCarrierType(2,'<?=$_REQUEST['default_warehouse_id'] ?>','','<?=$use_mode ?>')">Excel导出(<?=$counter['listplanceanorder']['2']['all']?>)</a></li>
  <?php } ?>
  <?php if($counter['listplanceanorder']['3']['all'] > 0){ ?>
  <li role="presentation" ><a class ="iv-btn <?= ($_REQUEST['carrier_type'] == 3)?'btn-important':''?>" onclick="changeCarrierType(3,'<?=$_REQUEST['default_warehouse_id'] ?>','','<?=$use_mode ?>')">从跟踪号库分配跟踪号(<?=$counter['listplanceanorder']['3']['all']?>)</a></li>
  <?php } ?>
 </ul>
</div>
<div style="margin-top:10px;">
<ul class="nav nav-pills">
<?php if($_REQUEST['carrier_type'] == 1){ ?>
  <li role="presentation" ><a class ="iv-btn <?= (@$_REQUEST['carrier_step'] == 'UPLOAD')?'btn-important':''?>" onclick="changeCarrierType(1,'<?=$_REQUEST['default_warehouse_id'] ?>','UPLOAD','<?=$use_mode ?>')">待上传(<?=$counter['listplanceanorder']['1']['0']?>)</a></li>
  <li role="presentation" ><span qtipkey="carrier_upload"></span></li>
  <?php if($counter['listplanceanorder']['1']['1'] > 0){ ?>
  <li role="presentation" ><a class ="iv-btn glyphicon glyphicon-arrow-right" href="#"></a></li>
  <li role="presentation" ><a class ="iv-btn <?= (@$_REQUEST['carrier_step'] == 'DELIVERY')?'btn-important':''?>" onclick="changeCarrierType(1,'<?=$_REQUEST['default_warehouse_id'] ?>','DELIVERY','<?=$use_mode ?>')">待交运(<?=$counter['listplanceanorder']['1']['1']?>)</a></li>
  <li role="presentation" ><span qtipkey="carrier_delivery"></span></li>
  <?php } ?>
  <li role="presentation" ><a class ="iv-btn glyphicon glyphicon-arrow-right" href="#"></a></li>
  <li role="presentation" ><a class ="iv-btn <?= (@$_REQUEST['carrier_step'] == 'DELIVERYED')?'btn-important':''?>" onclick="changeCarrierType(1,'<?=$_REQUEST['default_warehouse_id'] ?>','DELIVERYED','<?=$use_mode ?>')">已交运(<?=$counter['listplanceanorder']['1']['2']?>)</a></li>
  <li role="presentation" ><span qtipkey="carrier_deliveryed"></span></li>
  <?php
  if($counter['listplanceanorder']['1']['6'] > 0){ ?>
  <li role="presentation" ><a class ="iv-btn <?= (@$_REQUEST['carrier_step'] == 'FINISHED')?'btn-important':''?>" onclick="changeCarrierType(1,'<?=$_REQUEST['default_warehouse_id'] ?>','FINISHED1','<?=$use_mode ?>')">异常移动订单(<?=$counter['listplanceanorder']['1']['6']?>)</a></li>
  <?php } ?>
<?php }else if($_REQUEST['carrier_type'] == 2){ ?>  
  <li role="presentation" ><a class ="iv-btn <?= (@$_REQUEST['carrier_step'] == 'UPLOAD')?'btn-important':''?>" onclick="changeCarrierType(2,'<?=$_REQUEST['default_warehouse_id'] ?>','UPLOAD','<?=$use_mode ?>')">未导出(<?=$counter['listplanceanorder']['2']['0']?>)</a></li>
  <li role="presentation" ><a class ="iv-btn glyphicon glyphicon-arrow-right" href="#"></a></li>
  <li role="presentation" ><a class ="iv-btn <?= (@$_REQUEST['carrier_step'] == 'DELIVERY')?'btn-important':''?>" onclick="changeCarrierType(2,'<?=$_REQUEST['default_warehouse_id'] ?>','DELIVERY','<?=$use_mode ?>')">已导出(<?=$counter['listplanceanorder']['2']['1']?>)</a></li>
  <?php if(1 == 0){ ?>
  <li role="presentation" ><a class ="iv-btn glyphicon glyphicon-arrow-right" href="#"></a></li>
  <li role="presentation" ><a class ="iv-btn <?= (@$_REQUEST['carrier_step'] == 'FINISHED')?'btn-important':''?>" onclick="changeCarrierType(2,'<?=$_REQUEST['default_warehouse_id'] ?>','FINISHED','<?=$use_mode ?>')">已完成(Excel)(<?=$counter['listplanceanorder']['2']['6']?>)</a></li>
  <?php } ?>
<?php }else if($_REQUEST['carrier_type'] == 3){ ?>
  <li role="presentation" ><a class ="iv-btn <?= (@$_REQUEST['carrier_step'] == 'UPLOAD')?'btn-important':''?>" onclick="changeCarrierType(3,'<?=$_REQUEST['default_warehouse_id'] ?>','UPLOAD','<?=$use_mode ?>')">未分配(<?=$counter['listplanceanorder']['3']['0']?>)</a></li>
  <li role="presentation" ><a class ="iv-btn glyphicon glyphicon-arrow-right" href="#"></a></li>
  <li role="presentation" ><a class ="iv-btn <?= (@$_REQUEST['carrier_step'] == 'DELIVERY')?'btn-important':''?>" onclick="changeCarrierType(3,'<?=$_REQUEST['default_warehouse_id'] ?>','DELIVERY','<?=$use_mode ?>')">已分配(<?=$counter['listplanceanorder']['3']['1']?>)</a></li>
  <?php if(1 == 0){ ?>
  <li role="presentation" ><a class ="iv-btn glyphicon glyphicon-arrow-right" href="#"></a></li>
  <li role="presentation" ><a class ="iv-btn <?= (@$_REQUEST['carrier_step'] == 'FINISHED')?'btn-important':''?>" onclick="changeCarrierType(3,'<?=$_REQUEST['default_warehouse_id'] ?>','FINISHED','<?=$use_mode ?>')">已完成(<?=$counter['listplanceanorder']['3']['6']?>)</a></li>
  <?php } ?>
<?php } ?>
</ul>
</div>
<!-- --------------------------------------------搜索 bigin--------------------------------------------------------------- -->
	<div style="margin-top:15px">
		<!-- 搜索区域 -->
		<form class="form-inline" id="searchForm" name="form1" action="" method="post">
		<?=Html::hiddenInput('use_mode',$use_mode);?>
		<?=Html::hiddenInput('carrier_step',$_REQUEST['carrier_step']);?>
		<?=Html::hiddenInput('warehouse_id',$query_condition['default_warehouse_id'],['id'=>'warehouse_search']);?>
		<?=Html::hiddenInput('showsearch',$showsearch,['id'=>'showsearch']);?>
		<?=Html::hiddenInput('carrier_type',$query_condition['carrier_type'],['id'=>'carrier_type']);?>
		<?=Html::hiddenInput('consignee_country_code',@$query_condition['consignee_country_code'],['id'=>'consignee_country_code']);?>
		<?=Html::hiddenInput('carrierPrintType',@$query_condition['carrierPrintType'],['id'=>'carrierPrintType']);?>
		<?=Html::hiddenInput('default_carrier_code',@$query_condition['default_carrier_code'],['id'=>'default_carrier_code']); ?>
		<?=Html::hiddenInput('order_sync_ship_status',@$query_condition['order_sync_ship_status'],['id'=>'order_sync_ship_status']); ?>
		<?=Html::hiddenInput('tmpdelivery_status_in',@$query_condition['tmpdelivery_status_in'],['id'=>'tmpdelivery_status_in']); ?>
		<?=Html::hiddenInput('default_shipping_method_code',@$query_condition['default_shipping_method_code'],['id'=>'default_shipping_method_code_hide']); ?>
		<?=Html::hiddenInput('ismultipleProduct',@$query_condition['ismultipleProduct'],['id'=>'ismultipleProduct']); ?>
		<?=Html::hiddenInput('isExistTrackingNO',@$query_condition['isExistTrackingNO'],['id'=>'isExistTrackingNO']); ?>
		
		<?=Html::hiddenInput('customsort', @$query_condition['customsort'],['id'=>'customsort_search']);?>
		<?=Html::hiddenInput('ordersorttype', @$query_condition['ordersorttype'],['id'=>'ordersorttype_search']);?>
		<div style="margin:0px 0px 0px 0px">
		<!----------------------------------------------------------- 卖家账号 ----------------------------------------------------------->
		<?php //echo Html::dropDownList('selleruserid',isset($query_condition['selleruserid'])?$query_condition['selleruserid']:'',$selleruserids,['class'=>'iv-input','id'=>'selleruserid','style'=>'margin:0px','prompt'=>'平台 & 店铺'])?>
		
		
		<?php
		//店铺查找代码 S
		?>
		<input type="hidden" name ="selleruserid_combined" id="selleruserid_combined" value="<?php echo isset($_REQUEST['selleruserid_combined'])?$_REQUEST['selleruserid_combined']:'';?>">
		<?php
		$omsPlatformFinds = array();
		
		if(count($selleruserids) > 0){
			$selleruserids['select_shops_xlb'] = '选择账号';

			foreach ($selleruserids as $tmp_selleruserKey => $tmp_selleruserid){
				$omsPlatformFinds[] = array('event'=>'OrderCommon.order_platform_find(\'delivery\',\''.$use_mode.'\',\''.$tmp_selleruserKey.'\')','label'=>$tmp_selleruserid);
			}
			
			$pcCombination = OrderListV3Helper::getPlatformCommonCombination(array('type'=>'delivery','platform'=>$use_mode));
			if(count($pcCombination) > 0){
				foreach ($pcCombination as $pcCombination_K => $pcCombination_V){
					$omsPlatformFinds[] = array('event'=>'OrderCommon.order_platform_find(\'delivery\',\''.$use_mode.'\',\''.'com-'.$pcCombination_K.'-com'.'\')',
						'label'=>'组合-'.$pcCombination_K, 'is_combined'=>true, 'combined_event'=>'OrderCommon.platformcommon_remove(this,\'delivery\',\''.$use_mode.'\',\''.$pcCombination_K.'\')');
				}
			}
		}
		echo OrderListV3Helper::getDropdownToggleHtml('平台 & 店铺', $omsPlatformFinds);
		if(!empty($_REQUEST['selleruserid_combined'])){
			echo '<span onclick="OrderCommon.order_platform_find(\'delivery\',\''.$use_mode.'\',\'\')" class="glyphicon glyphicon-remove" style="cursor:pointer;font-size: 10px;line-height: 20px;color: red;margin-right:5px;" aria-hidden="true" data-toggle="tooltip" data-placement="top" data-html="true" title="" data-original-title="清空卖家账号查询条件"></span>';
		}
		//店铺查找代码 E
		?>
		
		
		
		
		<!----------------------------------------------------------- 精确搜索 ----------------------------------------------------------->
		<div class="input-group iv-input">
			<?php 
				unset($keys['delivery_id']);
			?>
			<?=Html::dropDownList('keys',isset($query_condition['keys'])?$query_condition['keys']:'',$keys,['class'=>'iv-input','style'=>'width:100px;','onchange'=>'OrderCommon.keys_change_find(this)'])?>
	      	<?=Html::textInput('searchval',isset($query_condition['searchval'])?$query_condition['searchval']:'',['class'=>'iv-input','id'=>'num','placeholder'=>'多个请用空格分隔或Excel整列粘贴','onkeypress'=>"if(event.keyCode==13){searchButtonClick();return false;}"])?>
	    </div>
	    
	    <?=Html::textInput('selected_country',isset($query_condition['selected_country'])?$query_condition['selected_country']:'',['class'=>'iv-input','placeholder'=>'请选择国家','style'=>'width:150px;margin:0px','onclick'=>'selected_country_click()'])?>
		<?=Html::hiddenInput('selected_country_code', @$query_condition['selected_country_code']);?>
    	
		<div style='display:inline;'>
		<?=Html::dropDownList('timetype',isset($query_condition['timetype'])?$query_condition['timetype']:'',$tmpOdtimetype,['class'=>'iv-input'])?>
        	<?=Html::input('date','date_from',isset($query_condition['date_from'])?$query_condition['date_from']:'',['class'=>'iv-input','style'=>'width:150px;margin:0px'])?>
        	至
			<?=Html::input('date','date_to',isset($query_condition['date_to'])?$query_condition['date_to']:'',['class'=>'iv-input','style'=>'width:150px;margin:0px'])?>
		</div>
	    
	    <?php // Html::dropdownlist('default_shipping_method_code',isset($query_condition['default_shipping_method_code'])?$query_condition['default_shipping_method_code']:'',$shippingServices,['prompt'=>'运输服务','class'=>'iv-input','style'=>'width:380px;','onchange'=>"searchButtonClick()",'id'=>'shipmethod'])?>
		    <!----------------------------------------------------------- 提交按钮 ----------------------------------------------------------->
		    <?=Html::checkbox('fuzzy',@$_REQUEST['fuzzy'],['label'=>TranslateHelper::t('模糊搜索')])?>
		    <?=Html::Button('搜索',['class'=>"iv-btn btn-search btn-spacing-middle",'id'=>'search','onclick'=>"searchButtonClick()"])?>
		    
		    <div class="pull-right" style="height: 40px;display:none;">
	    	<?php // Html::button('重置',['class'=>"iv-btn btn-search btn-spacing-middle",'onclick'=>"javascript:cleform();"])?>
	    		 
	    	</div>
	    	
	    	<!----------------------------------------------------------- 保持高级搜索展开 ----------------------------------------------------------->
	    	<div class="mutisearch">
			<?php
				echo $counter['warehouse_html'];
			?>
			<div style="margin:20px 0px 0px 0px;">
			<div style='float:left;margin: 5px 3px 0 0;display: inline-block;'>
				<strong style="font-weight: bold;font-size:12px;">运输服务：</strong>
			</div>
			<div style='width:83%;float:left;display: inline-block;'>
			<?php
				echo "<a class='service_a ".(@$query_condition['default_shipping_method_code'] == '' ? 'text-rever-important' : '')."' value='' onclick='searchBtnPubChange(this,\"default_shipping_method_code_hide\")'>".'全部'."</a>";
				
				foreach ($shippingServices as $tag_code => $label){
					if(!empty($label))
						echo "<a class='service_a ".(@$query_condition['default_shipping_method_code'] == $tag_code ? 'text-rever-important' : '')."' value='".$tag_code."' onclick='searchBtnPubChange(this,\"default_shipping_method_code_hide\")'>".$label."</a>";
				}
			?>
			</div>
			</div>
			<div style="clear: both;"></div>
			<!-- ----------------------------------第三行--------------------------------------------------------------------------------------------- -->
			<?php
			if($counter['warehouse_is_oversea'] == 0){
			?>
			<div style="margin:20px 0px 0px 0px">
			<strong style="font-weight: bold;font-size:12px;">打单类型：</strong>
			<?php
				$tmpCarrierPrintType = CarrierOpenHelper::$carrierPrintType;
				echo "<a style='margin-right: 30px;' class=' ".(@$query_condition['carrierPrintType'] == '' ? 'text-rever-important' : '')."' value='' onclick='carrierPrintBtnClick(this)'>".'全部'."</a>";
				
				
				if($_REQUEST['carrier_step'] == 'UPLOAD'){
					unset($tmpCarrierPrintType['no_print_carrier']);
					unset($tmpCarrierPrintType['print_carrier']);
				}
				
				foreach ($tmpCarrierPrintType as $tag_code=> $label){
					echo "<a style='margin-right: 30px;' class=' ".(@$query_condition['carrierPrintType'] == $tag_code ? 'text-rever-important' : '')."' value='".$tag_code."' onclick='carrierPrintBtnClick(this)'>".$label."</a>";
				}
			?>
			</div>
			<?php } ?>
			<div style="margin:20px 0px 0px 0px">
			<strong style="font-weight: bold;font-size:12px;">包裹类型：</strong>
			<?php
				$tmpParcelType = array('N'=>'单品', 'Y'=>'多品');
				echo "<a style='margin-right: 30px;' class=' ".(@$query_condition['ismultipleProduct'] == '' ? 'text-rever-important' : '')."' value='' onclick='searchBtnPubChange(this,\"ismultipleProduct\")'>".'全部'."</a>";
				
				foreach ($tmpParcelType as $tag_code=> $label){
					echo "<a style='margin-right: 30px;' class=' ".(@$query_condition['ismultipleProduct'] == $tag_code ? 'text-rever-important' : '')."' value='".$tag_code."' onclick='searchBtnPubChange(this,\"ismultipleProduct\")'>".$label."</a>";
				}
			?>
			</div>
			<div style="margin:20px 0px 0px 0px">
			<strong style="font-weight: bold;font-size:12px;">打包出库：</strong>
			<?php
				$tmpdelivery_status_in = array(''=>'全部');
				$tmpdelivery_status_in += array('N'=>'否', 'Y'=>'是');
				
				foreach ($tmpdelivery_status_in as $tag_code=> $label){
					echo "<a style='margin-right: 30px;' class=' ".(@$query_condition['tmpdelivery_status_in'] == $tag_code ? 'text-rever-important' : '')."' value='".$tag_code."' onclick='searchBtnPubChange(this,\"tmpdelivery_status_in\")'>".$label."</a>";
				}
			?>
			</div>
			<div style="margin:20px 0px 0px 0px">
			<strong style="font-weight: bold;font-size:12px;">虚拟发货状态：</strong>
			<?php
				$tmpOrderSyncShipStatus = array(''=>'全部');
				$tmpOrderSyncShipStatus += \eagle\modules\order\apihelpers\OrderApiHelper::$OrderSyncShipStatus;
				
				foreach ($tmpOrderSyncShipStatus as $tag_code=> $label){
					echo "<a style='margin-right: 30px;' class=' ".(@$query_condition['order_sync_ship_status'] == $tag_code ? 'text-rever-important' : '')."' value='".$tag_code."' onclick='searchBtnPubChange(this,\"order_sync_ship_status\")'>".$label."</a>";
				}
			?>
			</div>
			<div style="margin:20px 0px 0px 0px">
			<strong style="font-weight: bold;font-size:12px;">排序方式：</strong>
			<?php
			if(empty($query_condition['customsort'])){
				$query_condition['customsort'] = 'soldtime';
			}
			
			foreach ($tmpCustomsort as $tag_code=> $label){
// 				echo "<a style='margin-right: 20px;' class=' ".(@$query_condition['customsort'] == $tag_code ? 'text-rever-important' : '')."' value='".$tag_code."' sorttype='".@$query_condition['ordersorttype']."' onclick='sortModeBtnClick(this)'>".$label."<i class='cert cert-light cert-small ".(empty($query_condition['ordersorttype']) ? 'down' : 'top')."'></i></a>";
				echo "<a style='margin-right: 30px;' class=' ".($query_condition['customsort'] == $tag_code ? 'text-rever-important' : '')."' value='".$tag_code.
					"' sorttype='".@$query_condition['ordersorttype']."' onclick='sortModeBtnClick(this)'>".$label.
					($query_condition['customsort'] == $tag_code ? " <span class='glyphicon glyphicon-sort-by-attributes".(empty($query_condition['ordersorttype']) ? '-alt' : '')."'></span>" : '').
					"</a>";
			}
			?>
			</div>
			<?php
			if($_REQUEST['carrier_step'] == 'DELIVERYED'){
			?>
			<div style="margin:20px 0px 0px 0px">
			<strong style="font-weight: bold;font-size:12px;">是否有跟踪号：</strong>
			<?php
				$tmpExistTrackingNO = array('N'=>'否', 'Y'=>'是');
				echo "<a style='margin-right: 30px;' class=' ".(@$query_condition['isExistTrackingNO'] == '' ? 'text-rever-important' : '')."' value='' onclick='searchBtnPubChange(this,\"isExistTrackingNO\")'>".'全部'."</a>";
				
				foreach ($tmpExistTrackingNO as $tag_code=> $label){
					echo "<a style='margin-right: 30px;' class=' ".(@$query_condition['isExistTrackingNO'] == $tag_code ? 'text-rever-important' : '')."' value='".$tag_code."' onclick='searchBtnPubChange(this,\"isExistTrackingNO\")'>".$label."</a>";
				}
			?>
			</div>
			<?php
			}
			?>
			<!-- ----------------------------------第四行--------------------------------------------------------------------------------------------- -->
			<div style="margin:20px 0px 0px 0px">
			<strong style="font-weight: bold;font-size:12px;">系统标签：</strong>
			<?php 
				$tmpOrderTagHelper = OrderTagHelper::$OrderSysTagMapping;
				unset($tmpOrderTagHelper['sys_unshipped_tag']);
				//不要 已给买家好评
				unset($tmpOrderTagHelper['favourable_tag']);
				foreach ($tmpOrderTagHelper as $tag_code=> $label){
					echo Html::checkbox($tag_code,isset($query_condition[$tag_code])?$query_condition[$tag_code]:'',['label'=>TranslateHelper::t($label),'onclick'=>'label_check_radio(this)']);
				}
			?>
			</div>
			<!-- ----------------------------------第五行--------------------------------------------------------------------------------------------- -->
			<?php if(count($all_tag_list) > 0){ ?>
			<div style="margin:20px 0px 0px 0px">
			<div class="pull-left">
			<strong style="font-weight: bold;font-size:12px;">自定义标签：</strong>
			</div>
			<div class="pull-left" style="height: 40px;">
			<?=Html::checkboxlist('custom_tag',isset($query_condition['custom_tag'])?$query_condition['custom_tag']:'',$all_tag_list);?>
			</div>
			</div>
			<?php } ?>
			</div> 	
				
	    </div>
		</form>
	</div>
<!-- --------------------------------------------搜索 end--------------------------------------------------------------- -->
<div style="height:20px;clear: both;"><hr></div>
<div>
<!-- --------------------------------------------批量操作项begin--------------------------------------------------------------- -->

<div class="pull-left" style="height: 40px;">
	<?php
// 	echo Html::button(TranslateHelper::t('速卖通线上发货修改国内快递'),['class'=>"iv-btn btn-important",'onclick'=>"smt_editdomestic()",'value'=>'smt_editdomestic_btn','style'=>'margin-right:3px;']);
	?>

	<?php if($_REQUEST['carrier_step'] != 'UPLOAD'){?>
		<?php if($counter['warehouse_is_oversea'] == 0){ ?>
		<div class="btn-group">
		  <button type="button" class="iv-btn btn-important dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
		    批量打印 <span class="caret"></span>
		  </button>
		  <ul class="dropdown-menu" role="menu" aria-labelledby="dropdownMenu" style="width:250px;">
			<li class="bgColor5 p5 pLeft10 fColor2">打印操作</li>
			<li role="presentation"><a role="menuitem" tabindex="-1" href="javascript:" onclick="doactionnew('ExternalDoprint_0','delivery')">打印面单</a></li>
			<?php
			if(1==0){
				?>
				<li role="presentation"><a role="menuitem" tabindex="-1" href="javascript:" onclick="doprint('custom','delivery')">打印自定义面单</a></li>
				<?php
			}
			?>
			<li role="presentation"><a role="menuitem" tabindex="-1" href="javascript:" onclick="doprint('picking_product_sum','delivery')">打印拣货单（产品汇总）</a></li>
			<li role="presentation"><a role="menuitem" tabindex="-1" href="javascript:" onclick="doactionnew('printDistribution','delivery')">打印拣货单（订单汇总）</a></li>
			<li role="presentation"><a role="menuitem" tabindex="-1" href="javascript:" onclick="doprint('printDistribution_new','delivery')">打印拣货单-新（订单汇总）</a></li>
			<li role="presentation"><a role="menuitem" tabindex="-1" href="javascript:" onclick="doprint('picking_product_order_sum','delivery')">打印拣货单（产品汇总+订单汇总）</a></li>
			<li role="presentation"><a role="menuitem" tabindex="-1" href="javascript:" onclick="doprint('integrationlabel','delivery')">打印面单+拣货单（订单汇总）</a></li>
			<li role="presentation"><a role="menuitem" tabindex="-1" href="javascript:" onclick="doprint('thermal_picking_label','delivery')">打印拣货单（热敏）</a></li>
			<li role="presentation"><a role="menuitem" tabindex="-1" href="javascript:" onclick="doprint('invoiced_label','delivery')">打印发票</a></li>
			<?php foreach ($orders as $ordersone){
		    	if($ordersone->default_carrier_code=='lb_dhlexpress'){
		    		echo '<li role="presentation"><a role="menuitem" tabindex="-1" href="javascript:" onclick="doprint(\'dhlinvoice\',\'delivery\')">打印DHL发票</a></li>';
		    		break;
		    	}
		    }
		    //jumia官方发票
		    foreach ($orders as $ordersone){
		        if($ordersone->order_source=='jumia'){
		            echo '<li role="presentation"><a role="menuitem" tabindex="-1" href="javascript:" onclick="doprint(\'jumia_invoice\',\'delivery\')">打印Jumia官方发票</a></li>';
		            break;
		        }
		    }
		    ?>
			<li class="bgColor5 p5 pLeft10 fColor2">打印状态</li>
			<li class="dropdown-submenu">
				<a href="javascript:" class="removefromcart dropdownSubmenuA">标记面单</a>
				<ul class="dropdown-menu">
					<li role="presentation"><a role="menuitem" tabindex="-1" href="javascript:" onclick="doactionnew('batchcarrierprint_1','delivery')">已打印</a></li>
					<li role="presentation"><a role="menuitem" tabindex="-1" href="javascript:" onclick="doactionnew('batchcarrierprint_0','delivery')">未打印</a></li>
				</ul>
			</li>
			<li class="dropdown-submenu">
				<a href="javascript:" class="removefromcart dropdownSubmenuA">标记拣货单</a>
				<ul class="dropdown-menu">
					<li role="presentation"><a role="menuitem" tabindex="-1" href="javascript:" onclick="doactionnew('batchpickingprint_1','delivery')">已打印</a></li>
					<li role="presentation"><a role="menuitem" tabindex="-1" href="javascript:" onclick="doactionnew('batchpickingprint_0','delivery')">未打印</a></li>
				</ul>
			</li>
		</ul>
		</div>
		<span qtipkey="print_picking_carrier_lable"></span>
		<?php }} ?>

<!-- 接口对接 -->
<?php if($_REQUEST['carrier_type'] == 1){?>
	<?php if($_REQUEST['carrier_step'] == 'UPLOAD'){
		if($is_smt_alionlinedelivery){
			echo Html::button(TranslateHelper::t('速卖通线上发货批量修改国内快递'),['class'=>"iv-btn btn-important",'onclick'=>"smt_editdomestic()",'value'=>'smt_editdomestic_btn','style'=>'margin-right:3px;']);
		}
		
		echo Html::button(TranslateHelper::t('上传且交运'),['class'=>"iv-btn btn-important",'onclick'=>"doactionnew(this)",'value'=>'uploadAndDispatch_btn','style'=>'margin-right:3px;']);
		echo Html::button(TranslateHelper::t('修改报关信息'),['class'=>"iv-btn btn-important",'onclick'=>"doactionnew(this)",'value'=>'editCustomsInfo_btn','style'=>'margin-right:3px;','data'=>'delivery']);
		
		//批量操作
		$doAction=OrderHelper::getCurrentOperationListNew(OdOrder::STATUS_WAITSEND,'b','接口上传');
		?>
	<?php }else if($_REQUEST['carrier_step'] == 'DELIVERY'){
		echo Html::button(TranslateHelper::t('交运'),['class'=>"iv-btn btn-important",'onclick'=>"doactionnew(this)",'value'=>'dodispatch_btn','style'=>'margin-right:3px;']);
		echo Html::button(TranslateHelper::t('重新上传'),['class'=>"iv-btn btn-important",'onclick'=>"doactionnew(this)",'value'=>'moveToUpload_btn','style'=>'margin-right:3px;']);
		
		//批量操作
		$doAction=OrderHelper::getCurrentOperationListNew(OdOrder::STATUS_WAITSEND,'b','接口交运');
		?>
	<?php }else if($_REQUEST['carrier_step'] == 'DELIVERYED'){
		echo Html::button(TranslateHelper::t('确认发货完成'),['class'=>"iv-btn btn-important",'onclick'=>"doactionnew(this)",'value'=>'setFinished_btn','style'=>'margin-right:3px;']);
		echo '<span qtipkey="carrier_confirm_the_delivery"></span>';
		
		echo Html::button(TranslateHelper::t('获取跟踪号'),['class'=>"iv-btn btn-important",'onclick'=>"doactionnew(this)",'value'=>'getTrackNo_btn','style'=>'margin-right:3px;']);
		
		//批量操作
		$doAction=OrderHelper::getCurrentOperationListNew(OdOrder::STATUS_WAITSEND,'b','接口已交运');
		?>
	<?php }else if($_REQUEST['carrier_step'] == 'FINISHED'){
		//批量操作
		$doAction=OrderHelper::getCurrentOperationListNew(OdOrder::STATUS_WAITSEND,'b','接口已完成');
		?>
	<?php }?>
<!-- excel对接 -->
<?php }elseif($_REQUEST['carrier_type'] == 2){?>
	<?php if($_REQUEST['carrier_step'] == 'UPLOAD'){
		//批量操作
		$doAction=OrderHelper::getCurrentOperationListNew(OdOrder::STATUS_WAITSEND,'b','excel未导出');
		?>
	<?php }else if($_REQUEST['carrier_step'] == 'DELIVERY'){
		//批量操作
		$doAction=OrderHelper::getCurrentOperationListNew(OdOrder::STATUS_WAITSEND,'b','excel已导出');
		?>
	<?php }else if($_REQUEST['carrier_step'] == 'FINISHED'){
		//批量操作
		$doAction=OrderHelper::getCurrentOperationListNew(OdOrder::STATUS_WAITSEND,'b','excel已完成');
		?>
	<?php }?>
	<?= html::dropDownList('excelCarriers',@$_REQUEST['default_carrier_code'],$allcarriers,['class'=>'iv-input','prompt'=>'物流商','onchange'=>'changeExcelCarrier($(this).val())'])?>
	<input type="button" class="iv-btn btn-important" onclick="exportExcel('<?= @$_REQUEST['default_carrier_code']?>',function(){deliveryImplantOmsPublic();})" value="导出" />&nbsp;
<!-- 无数据对接 -->
<?php }elseif($_REQUEST['carrier_type'] == 3){?>
		<?php if($_REQUEST['carrier_step'] == 'UPLOAD'){
			echo Html::button(TranslateHelper::t('分配跟踪号'),['class'=>"iv-btn btn-important",'onclick'=>"doactionnew(this)",'value'=>'setTrackNum_btn','style'=>'margin-right:3px;']);
			
			$use_scan_binding_tracking = ConfigHelper::getConfig('use_scan_binding_tracking', 'NO_CACHE');
			$use_scan_binding_tracking = empty($use_scan_binding_tracking) ? 0 : $use_scan_binding_tracking;
			
			if($use_scan_binding_tracking == 1)
				echo Html::button(TranslateHelper::t('扫描绑定跟踪号'),['class'=>'iv-btn btn-important','onclick'=>"doactionnew('scanningtrackingnumber_btn')",'style'=>'margin-right:3px;']);
			//批量操作
			$doAction=OrderHelper::getCurrentOperationListNew(OdOrder::STATUS_WAITSEND,'b','未分配');
			?>
		<?php }else if($_REQUEST['carrier_step'] == 'DELIVERY'){
			//批量操作
			$doAction=OrderHelper::getCurrentOperationListNew(OdOrder::STATUS_WAITSEND,'b','已分配');
			?>
		<?php }else if($_REQUEST['carrier_step'] == 'FINISHED'){
			//批量操作
			$doAction=OrderHelper::getCurrentOperationListNew(OdOrder::STATUS_WAITSEND,'b','已完成');
			?>
		<?php }?>
<?php }?>
<!-- </div> -->
<!--	<div class="pull-left" style="height: 40px;"> -->
		<?php
		if($counter['warehouse_is_oversea'] == 1){
			unset($doAction['moveToPacking']);
		}
		
		if(empty($doAction)) $doAction = array(); ?>
		
		<?php
		if(!empty($doAction)){
		?>
		<div class="btn-group">
			<button type="button" class="iv-btn btn-important dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
			批量操作 <span class="caret"></span>
			</button>
			<ul class="dropdown-menu">
		<?php
			foreach ($doAction as $doActionKey => $doActionVal){
				if(!empty($doActionKey)){
					$tmp_action = '';
					if($doActionKey == 'setFinished'){
						$tmp_action = '<span qtipkey=carrier_confirm_the_delivery></span>';
					}else if($doActionKey == 'signcomplete'){
						$tmp_action = '<span qtipkey=order_sign_completed></span>';
					}

					echo "<li><a href='#' onclick=doactionnew('".$doActionKey."','delivery')>".$doActionVal.$tmp_action."</a></li>";
				}
			}
		?>
			</ul>
		</div>
		<?php } ?>
	</div>
	<div class="pull-right" style="height: 40px;">
		<div class="btn-group" >
			<div class="dropdown">
				<button class="iv-btn btn-important dropdown-toggle" style="width:auto;" type="button"  data-toggle="dropdown" aria-expanded="false">
					导出订单&nbsp;<span class="caret"></span>
				</button>
				<ul class="dropdown-menu" role="menu" >
					<li class="bgColor5 p5 pLeft10 fColor2">导出订单</li>
					<li><a href='#' onclick=OrderCommon.orderExcelprint(0)>按勾选导出</a></li>
					<li><a href='#' onclick=OrderCommon.orderExcelprint(1)>按所有页导出</a></li>
					
					<li class="bgColor5 p5 pLeft10 fColor2">导出为固定格式</li>
					
					<?php
						foreach (OdOrder::$exportOperationList as $excelmodelsKey => $excelmodelsVal){
							if(!empty($excelmodelsKey))
								echo "<li><a href='#' onclick=OrderCommon.DeliveryBatchOperation('".$excelmodelsKey."')>".$excelmodelsVal."</a></li>";
						}
					?>
					
				</ul>
			</div>
		</div>
		
		<div style="display:inline;" >
		<?php echo Html::button(TranslateHelper::t('导入跟踪号'),['class'=>"iv-btn btn-important",'onclick'=>"OrderCommon.importTrackNoBox()"]);?>
		</div>
    		<?php 
    		if($_REQUEST['carrier_step'] == 'DELIVERYED' || $_REQUEST['carrier_step'] == 'DELIVERY'){
				if($counter['warehouse_is_oversea'] == 0){
	    			echo'<div class="btn-group">
	        		    <a data-toggle="dropdown" >
	        		        <button class="iv-btn btn-important" >扫描发货<span class="caret"></span></button>
	        			</a>
	        			<ul class="dropdown-menu">
	        				<li style="font-size: 12px;"><a onclick="showScanningListDistributionBox()">扫描分拣包裹</a></li>
	        				<li style="font-size: 12px;"><a onclick="showScanningDeliveryOneBox()">扫描逐单发货</a></li>
	        				<li style="font-size: 12px;"><a onclick="showScanningDeliveryChooseBox()">扫描统一发货</a></li>
	        			</ul>
	        		</div>';
    			}
			}
    		?>
	</div>
	
		
<!-- --------------------------------------------批量操作项 end--------------------------------------------------------------- -->
		
<!-- --------------------------------分页-------------------------------------------- -->
<?php if($pagination):?>
<div style="clear: both;"></div>
<div>
	<div id="carrier-list-pager" class="pager-group">
	    <?= \eagle\widgets\SizePager::widget(['isAjax'=>true , 'pagination'=>$pagination , 'pageSizeOptions'=>array( 5 , 20 , 50 , 100 ,200) , 'class'=>'btn-group dropup']);?>
	    <div class="btn-group" style="width: 49.6%;text-align: right;">
	    	<?=\eagle\widgets\ELinkPager::widget(['isAjax'=>true , 'pagination' => $pagination,'options'=>['class'=>'pagination']]);?>
		</div>
	</div>
</div>
<?php endif;?>
<!-- --------------------------------分页-------------------------------------------- -->
		
<?php if($_REQUEST['carrier_step'] != 'UPLOAD'){?>
<form action="" method="post" target="_self" id="ordersform" name = 'a'>
<?php }?>
<?php //Html::hiddenInput('warehouse_id',$query_condition['default_warehouse_id']);?>
	<table id='carrier-list-table' class="table table-condensed table-bordered" style="font-size:12px;margin-top:20px;">
		<tr>
		<th width="6%">
		<span class="glyphicon glyphicon-minus" onclick="spreadorder(this);"></span><input type="checkbox"  onclick='ck_allOnClick(this)' /> <!-- check-all="e1" -->
		</th>
		<th width="6%"><b>小老板单号</b></th>
		<th width="22%"><b>商品SKU</b></th>
		<th width="8%"><b>总价</b></th>
		<th width="10%"><b>下单时间/付款时间</b></th>
		<th width="20%"><b>运输服务</b></th>
		<th width="6%"><?php echo Html::dropDownList('country',@$_REQUEST['consignee_country_code'],$countryArr,['prompt'=>'收件国家','style'=>'width:100px','onchange'=>"countryOnChange(this);"])?></th>
		<th width="6%"><b>平台/站点</b></th>
		<th width="6%"><b>销售平台状态</b></th>
		<th style="min-width: 80px;"><b>操作</b></th>
		</tr>

		<?php 
		$divTagHtml = "";
		?>
		<?php if (count($orders)){foreach ($orders as $order):?>
		<?php
			$tmp_order_is_cancel = \eagle\modules\delivery\helpers\DeliveryHelper::getOrderIsCancel($order);
		?>
		<!-- --------------------------------------------订单--------------------------------------------------------------- -->
		<tr style="background-color: #f4f9fc;border:<?=$tmp_order_is_cancel ? '3px solid red' : '1px solid #d1d1d1' ?>;" class="line-<?php echo $order->order_id;?>">
			<td>
				<span class="orderspread glyphicon glyphicon-minus" onclick="spreadorder(this,'<?=$order->order_id?>');"></span><input type="checkbox" class="ck" name="order_id[]" value="<?=$order->order_id?>" is_cancel=<?=$tmp_order_is_cancel ? 1 : 0 ?> > <!-- data-check="e1" -->
				<?php
				if(isset(Odorder::$reorderType[$order->reorder_type])){
					echo '<br>'.Odorder::$reorderType[$order->reorder_type];
				}
				?>
			</td>
			<td>
				<?=($order->order_source == 'ebay' && !empty($order->order_source_srn)) ? 'SRN:'.$order->order_source_srn.'<br>' : ''; ?>
				<?=$order->order_id?><br>
			<?php if ($order->exception_status>0&&$order->exception_status!='201'):?>
				<div title="<?=OdOrder::$exceptionstatus[$order->exception_status]?>" class="exception_<?=$order->exception_status?>"></div>
			<?php endif;?>
			<?php if (strlen($order->user_message)>0):?>
				<div title="<?=OdOrder::$exceptionstatus[OdOrder::EXCEP_HASMESSAGE]?>" class="exception_<?=OdOrder::EXCEP_HASMESSAGE?>"></div>
			<?php endif;?>
			<?php 
		            $divTagHtml .= '<div id="div_tag_'.$order['order_id'].'"  name="div_add_tag" class="div_space_toggle div_add_tag"></div>';
		            $TagStr = OrderTagHelper::generateTagIconHtmlByOrderId($order);
		            if (!empty($TagStr)){
		            	$TagStr = "<span class='btn_tag_qtip".(stripos($TagStr,'egicon-flag-gray')?" div_space_toggle":"")."' data-order-id='".$order['order_id']."' >$TagStr</span>";
		            }
		            echo $TagStr;
		            ?>
			</td>
			<td>
				<?php if (count($order->items)):foreach ($order->items as $item):?>
				<?php if (isset($item->sku)&&strlen($item->sku)):?>
				<?=$item->sku?>&nbsp;<b>X<span <?php if ($item->quantity>1){echo 'class="multiitem"';}?>><?=$item->quantity?></span></b><br>
				<?php endif;?>
				<?php endforeach;endif;?>
			</td>
			<td>
				<?=$order->grand_total?>&nbsp;<?=$order->currency?>
			</td>
			<td>
			<?=$order->order_source_create_time>0?date('y/m/d H:i:s',$order->order_source_create_time):''?><br>
			<?=$order->paid_time>0?date('y/m/d H:i:s',$order->paid_time):''?>
			</td>
			<td style="text-align: left;">
			客选物流：
			<?php 
			if($order->order_source=="aliexpress"){
				if(!empty($order->addi_info)){
					$addi_info_arr = json_decode($order->addi_info, true);
					if(!empty($addi_info_arr)){
						if(isset($addi_info_arr['shipping_service'])){
							if(is_array($addi_info_arr['shipping_service'])){
								echo implode(', ',$addi_info_arr['shipping_service']);
							}
						}
					}
				}
			}else{
				echo "<b class='b_bold_delivery'>".$order->order_source_shipping_method.'</b>';
			}
			?>
			<br>
			运输服务：<?php echo isset($allshippingservices[$order->default_shipping_method_code])?$allshippingservices[$order->default_shipping_method_code]:'';?>
			<?php if ($order->is_print_carrier ==1){echo '<span class="glyphicon glyphicon-print" aria-hidden="true" title="已打印物流面单"></span>';}?>
			</td>
			<td>
				<label><?=$order->consignee_country_code?></label>
				
				<?php
				if(isset($countryListN[$order->consignee_country_code])){
					$tmp_country_zh = $countryListN[$order->consignee_country_code]['cn'];
					$tmp_country_en = $countryListN[$order->consignee_country_code]['en_name'];
				}else{
					$tmp_country_zh = '';
					$tmp_country_en = '';
				}
				?>
				<?php
				if($tmp_country_zh != ''){
				?>
				<span class="span_simsun_100" aria-hidden="true" data-toggle="tooltip" data-placement="top" data-html="true" data-original-title="<?=$tmp_country_en ?>" title=""><?='「'.$tmp_country_zh.'」' ?></span>
				<?php					
				}
				?>
			</td>
			<td>
				<?php echo $order->order_source.(empty($order->order_source_site_id) ? '' : '/'.$order->order_source_site_id);?>
			</td>
			<td>
				<?php
					if($tmp_order_is_cancel){
						echo "<span style='color: red;font-size: 18px;'>已取消</span>";
					}else{
						//暂时先做这几个平台后期再优化 
						if($order->order_source == 'ebay'){
							OrderFrontHelper::getEbayStatus($order,$orderCheckOutList);
						}else if($order->order_source == 'aliexpress'){
							if (isset(OdOrder::$aliexpressStatus[$order->order_source_status])){echo OdOrder::$aliexpressStatus[$order->order_source_status];}else{echo $order->order_source_status;};
						}else if($order->order_source == 'amazon'){
							echo $order->order_source_status;
						}else if($order->order_source == 'cdiscount'){
							if(empty($order->order_source_status)){
								echo "";
							}else{
								if(isset(CdiscountOrderHelper::$cd_source_status_mapping[$order->order_source_status]))
									echo CdiscountOrderHelper::$cd_source_status_mapping[$order->order_source_status];
								else
									echo $order->order_source_status;
							}
						}
					}
				?>
			</td>
			<td>
			<?php
				echo '<a onclick="OrderCommon.editOrder(\''.$order->order_id.'\',this,\'listplanceanorder\')"><span class="glyphicon egicon-edit" title="编辑订单"></span></a>&nbsp';
			?>
			<?php if(1==0){ ?>
			<?php if ($order->order_source=="aliexpress"){?>
			<a href="<?=Url::to(['/order/aliexpressorder/edit','orderid'=>$order->order_id])?>" target="_blank"><span class="glyphicon glyphicon-edit" title="查看/编辑订单详情"></span></a>&nbsp;
			<?php }else if($order->order_source=="ebay"){?>
			<a href="<?=Url::to(['/order/ebay-order/edit','orderid'=>$order->order_id,'is_delivery'=>'delivery'])?>" target="_blank"><span class="glyphicon glyphicon-edit" title="查看/编辑订单详情"></span></a>&nbsp;
			<?php }else if($order->order_source=="amazon"){?>
			<a href="<?=Url::to(['/order/amazon-order/edit','orderid'=>$order->order_id,'is_delivery'=>'delivery'])?>" target="_blank"><span class="glyphicon glyphicon-edit" title="查看/编辑订单详情"></span></a>&nbsp;
			<?php }else if($order->order_source=="cdiscount"){?>
			<a href="<?=Url::to(['/order/cdiscount-order/edit','orderid'=>$order->order_id])?>" target="_blank"><span class="glyphicon glyphicon-edit" title="查看/编辑订单详情"></span></a>&nbsp;
			<?php }else if($order->order_source=="bonanza"){?>
			<a href="<?=Url::to(['/order/bonanza-order/edit','orderid'=>$order->order_id])?>" target="_blank"><span class="glyphicon glyphicon-edit" title="查看/编辑订单详情"></span></a>&nbsp;
			<?php }else if($order->order_source=="dhgate"){?>
			<a href="<?=Url::to(['/order/dhgate-order/edit','orderid'=>$order->order_id])?>" target="_blank"><span class="glyphicon glyphicon-edit" title="查看/编辑订单详情"></span></a>&nbsp;
			<?php }else if($order->order_source=="ensogo"){?>
			<a href="<?=Url::to(['/order/ensogo-order/edit','orderid'=>$order->order_id])?>" target="_blank"><span class="glyphicon glyphicon-edit" title="查看/编辑订单详情"></span></a>&nbsp;
			<?php }else if($order->order_source=="jumia"){?>
			<a href="<?=Url::to(['/order/jumia-order/edit','orderid'=>$order->order_id])?>" target="_blank"><span class="glyphicon glyphicon-edit" title="查看/编辑订单详情"></span></a>&nbsp;
			<?php }else if($order->order_source=="lazada"){?>
			<a href="<?=Url::to(['/order/lazada-order/edit','orderid'=>$order->order_id])?>" target="_blank"><span class="glyphicon glyphicon-edit" title="查看/编辑订单详情"></span></a>&nbsp;
			<?php }else if($order->order_source=="linio"){?>
			<a href="<?=Url::to(['/order/linio-order/edit','orderid'=>$order->order_id])?>"" target="_blank"><span class="glyphicon glyphicon-edit" title="查看/编辑订单详情"></span></a>&nbsp;
			<?php }else if($order->order_source=="priceminister"){?>
			<a href="<?=Url::to(['/order/priceminister-order/edit','orderid'=>$order->order_id])?>" target="_blank"><span class="glyphicon glyphicon-edit" title="查看/编辑订单详情"></span></a>&nbsp;
			<?php }else if($order->order_source=="wish"){?>
			<a href="<?=Url::to(['/order/wish-order/edit','orderid'=>$order->order_id])?>" target="_blank"><span class="glyphicon glyphicon-edit" title="查看/编辑订单详情"></span></a>&nbsp;
			<?php }else if($order->order_source=="customized"){?>
			<a href="<?=Url::to(['/order/customized-order/edit','orderid'=>$order->order_id])?>" target="_blank"><span class="glyphicon glyphicon-edit" title="查看/编辑订单详情"></span></a>&nbsp;
			<?php }?>
			<?php } ?>
			<a href="<?=Url::to(['/order/logshow/list','orderid'=>$order->order_id])?>" target="_blank"><span class="glyphicon glyphicon-paperclip" title="订单修改日志"></span></a>
			
			</td>
		</tr>
<?php if (count($order->items)):foreach ($order->items as $key=>$item):?>
		<!-- --------------------------------------------商品--------------------------------------------------------------- -->
		<tr class="xiangqing <?=$order->order_id?> line-<?php echo $order->order_id;?>" >
			<td style="border:1px solid #d1d1d1;">
			<img class="prod_img" src="<?=(in_array($order->order_source, array('cdiscount','priceminister')) ? eagle\modules\util\helpers\ImageCacherHelper::getImageCacheUrl($item->photo_primary, $puid, 1) : ((!empty($item->root_sku) && !empty($order_rootsku_product_image[$order->order_id][$item->root_sku])) ? $order_rootsku_product_image[$order->order_id][$item->root_sku] : $item->photo_primary))?>" width="60px" height="60px" data-toggle="popover" data-content="<img width='350px' src='<?=str_replace('.jpg_50x50','',$item->photo_primary)?>'>" data-html="true" data-trigger="hover">
			</td>
			<td colspan="2" style="<?=$item->delivery_status == 'ban' ? 'border:1px solid #d1d1d1;' : 'border:1px solid #d1d1d1;' ?>text-align:justify;">
				<?php
				if($item->delivery_status == 'ban'){
					echo "<span style='color: red;font-size: 18px;'>该Item的平台状态为:".$item->platform_status.",所以该Item不能提交物流和发货操作!</span><br>";
				}
				?>
				<?php
				if($order->order_source == 'ebay' && !empty($item->order_source_srn)){
				?>
				SRN:<b style="color:#ff9900;"><?=$item->order_source_srn?></b>
				<?php
				}else{
				?>
				订单号:<b style="color:#ff9900;"><?=$item->order_source_order_id?></b>
				<?php
				}
				?>
				<br>
				<?php
				if(!empty($order['customer_number'])){
// 					echo '跟踪号:'.CarrierOpenHelper::getOrderShippedTrackingNumber($order['order_id'],$order['customer_number'],$order['default_shipping_method_code']).'<br>';
					echo '跟踪号:'.$order['tracking_number'].'<br>';
				}
				
				if($order['default_carrier_code'] == 'lb_chukouyi'){
					$odOrderShipInfo = $order->getTrackinfosPT();
					foreach ($odOrderShipInfo as $orderShipped){
						if($orderShipped['customer_number'] == $order['customer_number']){
							if(isset($orderShipped['return_no']['ItemSign'])){
								echo '出口易处理号:'.$orderShipped['return_no']['ItemSign'].'<br>';
							}
							break;
						}
					}
				}
				
				if($order['default_carrier_code'] == 'lb_winitOversea'){
					$odOrderShipInfo = $order->getTrackinfosPT();
					foreach ($odOrderShipInfo as $orderShipped){
						if($orderShipped['customer_number'] == $order['customer_number']){
							echo 'WINIT订单编号:'.$order['customer_number'].'<br>';
							break;
						}
					}
				}
				
				if($order['default_carrier_code'] == 'lb_chukouyiOversea'){
					$odOrderShipInfo = $order->getTrackinfosPT();
					foreach ($odOrderShipInfo as $orderShipped){
						if($orderShipped['customer_number'] == $order['customer_number']){
							if(isset($orderShipped['return_no']['Sign'])){
								echo '出口易处理号:'.$orderShipped['return_no']['Sign'].'<br>';
							}
							break;
						}
					}
				}
				
				?>
				SKU:<b><?=$item->sku?></b><br>
				<?php
				//是否显示root_sku
				$tmp_show_root_sku = $item->root_sku;

				if($tmp_show_root_sku != ''){
					echo "配对SKU：<b>".$tmp_show_root_sku."</b>
						<span class='used_stock_info'>（可用库存: ".(empty($stock_list[$order->default_warehouse_id][$tmp_show_root_sku]) ? '0' : $stock_list[$order->default_warehouse_id][$tmp_show_root_sku])."）</span>
						<br>";
				}
				
				?>
				
				<?php // echo (empty($item->product_url) ? $item->product_name : '<a href="'.(OrderFrontHelper::displayPlatformProductUrl($order->order_source,$item)).'" target="_blank">'.$item->product_name.'</a>') ?>
				<?php
				
				$product_name_url = \eagle\modules\order\helpers\OrderListV3Helper::getOrderProductUrl($order, $item);
				echo (empty($item->product_url) ? $item->product_name : '<a href="'.($product_name_url).'" target="_blank">'.$item->product_name.'</a>')
				?>
				
				<?php
					if($order->order_source == 'aliexpress'){
						if (!empty($item->product_attributes)){
							$tmpProdctAttrbutes = explode(' + ' ,$item->product_attributes );
							if (!empty($tmpProdctAttrbutes)){
								echo '<br/>';
								foreach($tmpProdctAttrbutes as $_tmpAttr){
									echo '<span class="label label-warning">'.$_tmpAttr.'</span>';
								}
							}
						}
					}
				?>
			</td>
			<td  style="border:1px solid #d1d1d1;">
				<?=$item->quantity?>
			</td>
			<?php if ($key=='0'):?>
			<td rowspan="<?=count($order->items)?>" style="border:1px solid #d1d1d1;text-align:left;" class="text-nowrap">
			<?php echo $order->default_warehouse_id == -1?"未指定仓库":$warehouseIdNameMap[$order->default_warehouse_id];?>
			<?php if ($order->is_print_distribution ==1){echo '<span class="glyphicon glyphicon-print" aria-hidden="true" title="已打印配货单"></span>';}?>
			</td>
			<td rowspan="<?=count($order->items)?>" style="border:1px solid #d1d1d1;text-align:left;" class="text-nowrap">
				<?php if($order->order_source == 'amazon') { ?>
				<font color="#8b8b8b">amazon店铺名:</font>
				<b><?=@substr($selleruserids[$order->selleruserid], 2, strlen($selleruserids[$order->selleruserid])-2);?></b><br>
				<?php }else{
				?>
				<font color="#8b8b8b">卖家账号:</font>
				<?php 
				if(in_array($order->order_source, array('ebay','wish', 'shopee'))){
				?>
				<b><?=(isset($selleruserids[$order->selleruserid]) ? mb_substr($selleruserids[$order->selleruserid], 2) : $order->selleruserid)  ?></b><br>
				<?php
				}else{
				?>
				<b><?=$order->selleruserid ?></b><br>
				<?php
				}
				?>
				<?php } ?>
				<font color="#8b8b8b">买家姓名:</font>
				<b><?=$order->consignee?></b><br>
				<font color="#8b8b8b">买家账号:</font>
				<b><?=$order->source_buyer_user_id?></b><br>
				<font color="#8b8b8b">买家邮箱:</font>
				<b><?=$order->consignee_email?></b>
			</td>
			<td colspan="3"  rowspan="<?=count($order->items)?>"  width="150px" style="word-break:break-all;word-wrap:break-word;border:1px solid #d1d1d1;text-align:left;">
				<?php 
				if (in_array($order->order_status , [OdOrder::STATUS_PAY , OdOrder::STATUS_WAITSEND  , OdOrder::STATUS_SHIPPING])){
					$tmpTimeLeft =  ((!empty($order->fulfill_deadline))?'<span id="timeleft_'.$order->order_id.'" class="fulfill_timeleft" data-order-id="'.$order->order_id.'" data-time="'.($order->fulfill_deadline-time()).'"></span><br>':"");
					echo $tmpTimeLeft;
				}
				?>
				<font color="#8b8b8b">付款备注:</font><br><b class="text-warning"><?=$order->user_message?></b>
			</td>
			<td  rowspan="<?=count($order->items)?>" width="150" style="word-break:break-all;word-wrap:break-word;border:1px solid #d1d1d1">
			<span><font color="red" id="desc-<?php echo $order->order_id;?>"><?=$order->desc?></font></span>
				<a href="javascript:void(0)" style="border:1px solid #00bb9b;" onclick="updatedesc('<?=$order->order_id?>',this)" oiid="<?=$order->order_id?>"><font color="00bb9b">备注</font></a>
			</td>
			<?php endif;?>
		</tr>	
		<?php endforeach;endif;?>
<!-- --------------------------------------------物流参数begin--------------------------------------------------------------- -->
<?php if ($_REQUEST['carrier_step'] == 'UPLOAD' && $_REQUEST['carrier_type']==1){?>
<tr class=" appatch orderInfo line-<?php echo $order->order_id;?> xiangqing <?=$order->order_id?>" id="formline-<?php echo $order->order_id;?>"> 
<td colspan="10" class="row" style="word-break:break-all;word-wrap:break-word;border:1px solid #d1d1d1" >
	<form class="form-inline">
	<?php 
		//获取出物流商的参数
		echo $orderHtml[$order->order_id];
	?>
	</form>
</td>
</tr>
<?php }?>
<!-- --------------------------------------------物流参数end--------------------------------------------------------------- -->
<tr style="background-color: #d9d9d9;" class="xiangqing <?=$order->order_id?>">
	<td colspan="10" class="row" id="dataline-<?php echo $order->order_id;?>" style="word-break:break-all;word-wrap:break-word;border:1px solid #d1d1d1">
	<?php if(!empty($order->carrier_error)){?>
		<div class="alert-danger" id="message-<?php echo $order->order_id;?>" role="alert" style="text-align:left;"><?php echo $order->carrier_error;?></div>
	<?php }?>
	</td>
</tr>
<?php endforeach;}?>
</table>
<?php if($_REQUEST['carrier_step'] != 'UPLOAD'){?>
</form>
<?php }?>
<!-- --------------------------------操作成功显示区域-------------------------------------------- -->
<!-- --------------------------------分页-------------------------------------------- -->
<?php if($pagination):?>
<div>
	<div id="carrier-list-pager2" class="pager-group">
	    <?= \eagle\widgets\SizePager::widget(['isAjax'=>true , 'pagination'=>$pagination , 'pageSizeOptions'=>array( 5 , 20 , 50 , 100 ,200) , 'class'=>'btn-group dropup']);?>
	    <div class="btn-group" style="width: 49.6%;text-align: right;">
	    	<?=\eagle\widgets\ELinkPager::widget(['isAjax'=>true , 'pagination' => $pagination,'options'=>['class'=>'pagination']]);?>
		</div>
	</div>
</div>
<?php endif;?>

<?php 
// 该例子支持通过 ajax 更新table 和 pagination页面，启动该功能需要下方代码初始化js配置，以及给下面两个widget配置"isAjax"=>true,
	$tmp_and_where = '';
	try{
		foreach ($_REQUEST as $tmp_and_key => $tmp_and_val){
			/**/
			if (is_array($tmp_and_val)){
				foreach($tmp_and_val as $tmp_sub_value){
					$tmp_and_where .= '&'.$tmp_and_key.'[]='.$tmp_sub_value;
				}
				
			}else{
				$tmp_and_where .= '&'.$tmp_and_key.'='.$tmp_and_val;
			}
			
			//$tmp_and_where .= '&'.$tmp_and_key.'='.$tmp_and_val;
		}
	}catch(\Exception $e){
		$e->getMessage();
	}
	
	$options = array();
	$options['pagerId'] = 'carrier-list-pager';// 下方包裹 分页widget的id
	$options['pagerId2'] = 'carrier-list-pager2';// 下方包裹 分页widget的id
	$options['action'] = \Yii::$app->request->getPathInfo().'?use_mode='.$use_mode.'&carrier_step='.$_REQUEST['carrier_step'].'&carrier_type='.$_REQUEST['carrier_type'].'&warehouse_id='.$query_condition['default_warehouse_id'].$tmp_and_where; // ajax请求的 action
	$options['page'] = $pagination->getPage();// 当前页码
	$options['per-page'] = $pagination->getPageSize();// 当前page size
	$options['sort'] = isset($_REQUEST['sort'])?$_REQUEST['sort']:'';// 当前排序
	$this->registerJs('$("#carrier-list-table").initGetPageEvent('.json_encode($options).')' , \Yii\web\View::POS_READY);
?>
<!-- --------------------------------分页-------------------------------------------- -->		
</div>
</div>
<?=$divTagHtml?>

<form name="order-related-additional-operation-form" style="display:none;" method="post" target="_self">
<input type="hidden" name="order_id" />
<input type="hidden" name="js_submit" value='js_submit' />
</form>
<div>
<input type='hidden' id='search_condition' value='<?=$search_condition ?>'>
<input type='hidden' id='search_count' value='<?=$search_count ?>'>
</div>

<?php
	$this->registerJs("$('[role=\"tooltip\"]').hide();" , \yii\web\View::POS_READY);
?>

<?php
	$this->registerJs("$('[data-toggle=\"tooltip\"]').tooltip();" , \yii\web\View::POS_READY);
?>

<?php
 echo eagle\modules\carrier\helpers\CarrierOpenHelper::getCainiaoPrintModalHtml();
?>
