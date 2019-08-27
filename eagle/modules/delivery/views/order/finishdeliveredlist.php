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
$baseUrl = \Yii::$app->urlManager->baseUrl . '/';
$tmp_js_version = '1.94';

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

$this->registerJsFile(\Yii::getAlias('@web')."/js/project/carrier/carrierQtip.js", ['depends' => ['yii\web\JqueryAsset']]);
$this->registerJs("carrierQtip.initCarrierQtip('".json_encode(@$carrierQtips)."');" , \yii\web\View::POS_READY);

$this->registerJsFile($baseUrl."js/project/util/select_country.js?v=".$tmp_js_version, ['depends' => ['yii\jui\JuiAsset','yii\bootstrap\BootstrapPluginAsset']]);

$this->registerJsFile ( \Yii::getAlias ( '@web' ) . "/js/project/delivery/order/deliveryOms.js?v=".$tmp_js_version, ['depends' => ['yii\web\JqueryAsset']]);

$user=\Yii::$app->user->identity;
$puid = $user->getParentUid();

$this->registerJsFile ( \Yii::getAlias ( '@web' ) . "/js/project/delivery/order/cainiao.js?v=".\eagle\modules\order\helpers\OrderListV3Helper::$OrderCommonJSV3, ['depends' => ['yii\web\JqueryAsset']]);
$this->registerJsFile(\Yii::getAlias('@web').'js/jquery.json-2.4.js', ['depends' => ['yii\jui\JuiAsset','yii\bootstrap\BootstrapPluginAsset']]);
$this->registerJs("doConnect();" , \yii\web\View::POS_READY);
?>
<style>
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
<?php echo $this->render('//layouts/new/left_menu_2',['menu'=>[]]);?>

<div class="content-wrapper" >
<?php echo $order_nav_html;?>
<!-- --------------------------------------------搜索 bigin--------------------------------------------------------------- -->
	<div style="margin-top:15px">
		<!-- 搜索区域 -->
		<form class="form-inline" id="searchForm" name="form1" action="" method="post">
		<?//=Html::hiddenInput('warehouse_id',$query_condition['default_warehouse_id']);?>
		<?=Html::hiddenInput('showsearch',$showsearch,['id'=>'showsearch']);?>
		<?=Html::hiddenInput('default_shipping_method_code',@$query_condition['default_shipping_method_code'],['id'=>'default_shipping_method_code_hide']); ?>
		<?=Html::hiddenInput('order_sync_ship_status',@$query_condition['order_sync_ship_status'],['id'=>'order_sync_ship_status']); ?>
		<?=Html::hiddenInput('carrierPrintType',@$query_condition['carrierPrintType'],['id'=>'carrierPrintType']);?>
		<?=Html::hiddenInput('customsort', @$query_condition['customsort'],['id'=>'customsort_search']);?>
		<?=Html::hiddenInput('ordersorttype', @$query_condition['ordersorttype'],['id'=>'ordersorttype_search']);?>
		<div style="margin:0px 0px 0px 0px">
			<!----------------------------------------------------------- 卖家账号 ----------------------------------------------------------->
			<?=Html::dropDownList('selleruserid',isset($query_condition['selleruserid'])?$query_condition['selleruserid']:'',$selleruserids,['class'=>'iv-input','id'=>'selleruserid','style'=>'margin:0px','prompt'=>'平台 & 店铺'])?>
			<!----------------------------------------------------------- 精确搜索 ----------------------------------------------------------->
			<div class="input-group iv-input">
				<?php unset($keys['delivery_id']); ?>
				<?=Html::dropDownList('keys',isset($query_condition['keys'])?$query_condition['keys']:'',$keys,['class'=>'iv-input','style'=>'width:100px;'])?>
		      	<?=Html::textInput('searchval',isset($query_condition['searchval'])?$query_condition['searchval']:'',['class'=>'iv-input','id'=>'num'])?>
		    </div>
		    
		    <?=Html::textInput('selected_country',isset($query_condition['selected_country'])?$query_condition['selected_country']:'',['class'=>'iv-input','placeholder'=>'请选择国家','style'=>'width:130px;margin:0px','onclick'=>'selected_country_click()'])?>
			<?=Html::hiddenInput('selected_country_code', @$query_condition['selected_country_code']);?>
			
			<?=Html::dropDownList('timetype',isset($query_condition['timetype'])?$query_condition['timetype']:'',Odorder::$timetype,['class'=>'iv-input'])?>
        	<?=Html::input('date','date_from',isset($query_condition['date_from'])?$query_condition['date_from']:'',['class'=>'iv-input','style'=>'width:150px;margin:0px'])?>
        	至
			<?=Html::input('date','date_to',isset($query_condition['date_to'])?$query_condition['date_to']:'',['class'=>'iv-input','style'=>'width:150px;margin:0px'])?>
			
			<?=Html::dropDownList('reorder_type',isset($query_condition['reorder_type'])?$query_condition['reorder_type']:'',Odorder::$reorderType,['prompt'=>'重新发货类型','class'=>'iv-input','style'=>'width:130px;margin:0px'])?>
	    
		    <!----------------------------------------------------------- 提交按钮 ----------------------------------------------------------->
		    <?=Html::checkbox('fuzzy',@$_REQUEST['fuzzy'],['label'=>TranslateHelper::t('模糊搜索')])?>
		    <?=Html::submitButton('搜索',['class'=>"iv-btn btn-search btn-spacing-middle",'id'=>'search'])?>
		    
		    
		    <div style="margin:15px 0px 0px 0px;">
				<div style='float:left;margin: 5px 3px 0 0;display: inline-block;'>
					<strong style="font-weight: bold;font-size:12px;">运输服务：</strong>
				</div>
				<div style='width:83%;float:left;display: inline-block;'>
				<?php
					echo "<a class='service_a ".(@$query_condition['default_shipping_method_code'] == '' ? 'text-rever-important' : '')."' value='' onclick='searchBtnPubChange(this,\"default_shipping_method_code_hide\",\"finishdeliveredlist\")'>".'全部'."</a>";
					
					foreach ($shippingServices as $tag_code => $label){
						if(!empty($label))
							echo "<a class='service_a ".(@$query_condition['default_shipping_method_code'] == $tag_code ? 'text-rever-important' : '')."' value='".$tag_code."' onclick='searchBtnPubChange(this,\"default_shipping_method_code_hide\",\"finishdeliveredlist\")'>".$label."</a>";
					}
				?>
				</div>
			</div>
		    <div style="clear: both;"></div>
		    
		    <div style="margin:15px 0px 0px 0px">
			<strong style="font-weight: bold;font-size:12px;">打单类型：</strong>
			<?php
				$tmpCarrierPrintType = CarrierOpenHelper::$carrierPrintType;
				echo "<a style='margin-right: 30px;' class=' ".(@$query_condition['carrierPrintType'] == '' ? 'text-rever-important' : '')."' value='' onclick='carrierPrintBtnClick(this,\"finishdeliveredlist\")'>".'全部'."</a>";
				
				unset($tmpCarrierPrintType['no_print_distribution']);
				unset($tmpCarrierPrintType['print_distribution']);
				
				foreach ($tmpCarrierPrintType as $tag_code=> $label){
					echo "<a style='margin-right: 30px;' class=' ".(@$query_condition['carrierPrintType'] == $tag_code ? 'text-rever-important' : '')."' value='".$tag_code."' onclick='carrierPrintBtnClick(this,\"finishdeliveredlist\")'>".$label."</a>";
				}
			?>
			</div>
	    	
	    	<div style="margin:20px 0px 0px 0px">
			<strong style="font-weight: bold;font-size:12px;">虚拟发货状态：</strong>
			<?php
				$tmpOrderSyncShipStatus = array(''=>'全部');
				$tmpOrderSyncShipStatus += \eagle\modules\order\apihelpers\OrderApiHelper::$OrderSyncShipStatus;
				
				foreach ($tmpOrderSyncShipStatus as $tag_code=> $label){
					echo "<a style='margin-right: 30px;' class=' ".(@$query_condition['order_sync_ship_status'] == $tag_code ? 'text-rever-important' : '')."' value='".$tag_code."' onclick='searchBtnPubChange(this,\"order_sync_ship_status\",\"finishdeliveredlist\")'>".$label."</a>";
				}
			?>
			</div>
	    	
			<!-- ----------------------------------第三行--------------------------------------------------------------------------------------------- -->
			<div style="margin:20px 0px 0px 0px">
			<strong style="font-weight: bold;font-size:12px;">排序方式：</strong>
			<?php
			$tmpCustomsort = Odorder::$customsort;
			unset($tmpCustomsort['fulfill_deadline']);
			
			if(empty($query_condition['customsort'])){
				$query_condition['customsort'] = 'soldtime';
			}
			
			foreach ($tmpCustomsort as $tag_code=> $label){
				echo "<a style='margin-right: 30px;' class=' ".($query_condition['customsort'] == $tag_code ? 'text-rever-important' : '')."' value='".$tag_code.
					"' sorttype='".@$query_condition['ordersorttype']."' onclick='sortModeBtnClick(this,\"finishdeliveredlist\")'>".$label.
					($query_condition['customsort'] == $tag_code ? " <span class='glyphicon glyphicon-sort-by-attributes".(empty($query_condition['ordersorttype']) ? '-alt' : '')."'></span>" : '').
					"</a>";
			}
			?>
			</div>
			<!-- ----------------------------------第四行--------------------------------------------------------------------------------------------- -->
			<div style="margin:20px 0px 0px 0px">
			<strong style="font-weight: bold;font-size:12px;">系统标签：</strong>
			<?php foreach (OrderTagHelper::$OrderSysTagMapping as $tag_code=> $label){
				echo Html::checkbox($tag_code,isset($query_condition[$tag_code])?$query_condition[$tag_code]:'',['label'=>TranslateHelper::t($label),'onclick'=>'label_check_radio(this)']);
			}
			//echo Html::checkbox('is_reverse',isset($query_condition['is_reverse'])?$query_condition['is_reverse']:'',['label'=>TranslateHelper::t('取反')]);
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
		</form>
	</div>
<!-- --------------------------------------------搜索 end--------------------------------------------------------------- -->
<div style="height:20px;clear: both;"><hr></div>
<div>
<!-- --------------------------------------------批量操作项begin--------------------------------------------------------------- -->
<div class="pull-left" style="height: 40px;display:none;">
<?php
	$doAction=OrderHelper::getCurrentOperationListNew(OdOrder::STATUS_SHIPPED,'b','发货已完成');
?>
	<?php // Html::dropDownList('do','',$doAction,['onchange'=>"doactionnew(this);",'class'=>'iv-input do','style'=>'width:190px;margin:0px']);?> 
	<?php // Html::dropDownList('do','',$excelmodels,['onchange'=>"exportorder($(this).val());",'prompt'=>'自定义格式Excel导出','class'=>'iv-input do','style'=>'width:150px;margin:0px']);?> 
	<?php //Html::dropDownList('do','',OdOrder::$exportOperationList,['onchange'=>"OrderCommon.DeliveryBatchOperation(this);",'prompt'=>'小老板固定格式Excel导出','class'=>'iv-input do','style'=>'width:170px;margin:0px']);?> 
</div>

<div style="height:1px;clear: both;"></div>
<div class="pull-left" style="height: 40px;">
	<div class="btn-group">
	  <button type="button" class="iv-btn btn-important dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
	    批量打印 <span class="caret"></span>
	  </button>
	  
	  <ul class="dropdown-menu" role="menu" aria-labelledby="dropdownMenu" style="width:250px;">
			<li class="bgColor5 p5 pLeft10 fColor2">打印操作</li>
			<li role="presentation"><a role="menuitem" tabindex="-1" href="javascript:" onclick="doactionnew('ExternalDoprint_0')">打印面单</a></li>
			<li role="presentation"><a role="menuitem" tabindex="-1" href="javascript:" onclick="doprint('picking_product_sum','finishdeliveredlist')">打印拣货单（产品汇总）</a></li>
			<li role="presentation"><a role="menuitem" tabindex="-1" href="javascript:" onclick="doactionnew('printDistribution','finishdeliveredlist')">打印拣货单（订单汇总）</a></li>
			<li role="presentation"><a role="menuitem" tabindex="-1" href="javascript:" onclick="doprint('printDistribution_new','finishdeliveredlist')">打印拣货单-新（订单汇总）</a></li>
			<li role="presentation"><a role="menuitem" tabindex="-1" href="javascript:" onclick="doprint('picking_product_order_sum','finishdeliveredlist')">打印拣货单（产品汇总+订单汇总）</a></li>
			<li role="presentation"><a role="menuitem" tabindex="-1" href="javascript:" onclick="doprint('integrationlabel','finishdeliveredlist')">打印面单+拣货单（订单汇总）</a></li>
			<li role="presentation"><a role="menuitem" tabindex="-1" href="javascript:" onclick="doprint('invoiced_label','finishdeliveredlist')">打印发票</a></li>
			<?php foreach ($orders as $ordersone){
		    	if($ordersone->default_carrier_code=='lb_dhlexpress'){
		    		echo '<li role="presentation"><a role="menuitem" tabindex="-1" href="javascript:" onclick="doprint(\'dhlinvoice\',\'finishdeliveredlist\')">打印DHL发票</a></li>';
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
					<li role="presentation"><a role="menuitem" tabindex="-1" href="javascript:" onclick="doactionnew('batchcarrierprint_1')">已打印</a></li>
					<li role="presentation"><a role="menuitem" tabindex="-1" href="javascript:" onclick="doactionnew('batchcarrierprint_0')">未打印</a></li>
				</ul>
			</li>
			<li class="dropdown-submenu">
				<a href="javascript:" class="removefromcart dropdownSubmenuA">标记拣货单</a>
				<ul class="dropdown-menu">
					<li role="presentation"><a role="menuitem" tabindex="-1" href="javascript:" onclick="doactionnew('batchpickingprint_1')">已打印</a></li>
					<li role="presentation"><a role="menuitem" tabindex="-1" href="javascript:" onclick="doactionnew('batchpickingprint_0')">未打印</a></li>
				</ul>
			</li>
		</ul>
	  
	  
	  <?php if(1==0){ ?>
	  <ul class="dropdown-menu">
	    <li><a href="#" onclick="doactionnew('ExternalDoprint_0')">打印面单</a></li>
	    <li><a href="#" onclick="doprint('custom','finishdeliveredlist')">打印自定义面单</a></li>
	    <li><a href="#" onclick="doprint('picking_product_sum','finishdeliveredlist')">打印拣货单（产品汇总）</a></li>
	    <li><a href="#" onclick="doactionnew('printDistribution','finishdeliveredlist');">打印拣货单（订单汇总）</a></li>
	    <li><a href="#" onclick="doprint('printDistribution_new','finishdeliveredlist')">打印拣货单-新（订单汇总）</a></li>
	    <li><a href="#" onclick="doprint('picking_product_order_sum','finishdeliveredlist')">打印拣货单（产品汇总+订单汇总）</a></li>
	    <li><a href="#" onclick="doprint('integrationlabel','finishdeliveredlist')">打印面单+拣货单（订单汇总）</a></li>
	    <li><a href="#" onclick="doprint('invoiced_label','delivery')">打印发票</a></li>
	   	<?php foreach ($orders as $ordersone){
		    	if($ordersone->default_carrier_code=='lb_dhlexpress'){
		    		echo '<li><a href="#" onclick="doprint(\'dhlinvoice\',\'delivery\')">打印DHL发票</a></li>';
		    		break;
		    	}
		    }
		?>
	  </ul>
	  <?php } ?>
	  
	</div>
	<span qtipkey="print_picking_carrier_lable"></span>
	
	<div class="btn-group">
		<button type="button" class="iv-btn btn-important dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
		批量操作 <span class="caret"></span>
		</button>
		<ul class="dropdown-menu">
	<?php
		foreach ($doAction as $doActionKey => $doActionVal){
			if(!empty($doActionKey))
				echo "<li><a href='#' onclick=doactionnew('".$doActionKey."')>".$doActionVal."</a></li>";
		}
	?>
		</ul>
	</div>
</div>

	<div class="pull-right" style="height: 40px;">
		<!-- <div class="btn-group">
			<button type="button" class="iv-btn btn-important dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
			自定义格式Excel导出<span class="caret"></span>
			</button>
			<ul class="dropdown-menu">
		<?php
			foreach ($excelmodels as $excelmodelsKey => $excelmodelsVal){
				if(!empty($excelmodelsKey))
					echo "<li><a href='#' onclick=exportorder('".$excelmodelsKey."')>".$excelmodelsVal."</a></li>";
			}
		?>
			</ul>
		</div>-->
		<div class="btn-group">
			<button type="button" class="iv-btn btn-important dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
			自定义格式Excel导出<span class="caret"></span>
			</button>
			<ul class="dropdown-menu">
			<li><a href='#' onclick=OrderCommon.orderExcelprint(0)>按勾选导出</a></li>
			<li><a href='#' onclick=OrderCommon.orderExcelprint(1)>按所有页导出</a></li>
			</ul>
		</div>
		 
		<?php // Html::dropDownList('do','',OdOrder::$exportOperationList,['onchange'=>"OrderCommon.DeliveryBatchOperation(this);",'prompt'=>'小老板固定格式Excel导出','class'=>'iv-input do','style'=>'width:170px;margin:0px']);?>
		<div class="btn-group">
			<button type="button" class="iv-btn btn-important dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
			小老板固定格式Excel导出<span class="caret"></span>
			</button>
			<ul class="dropdown-menu">
		<?php
			foreach (OdOrder::$exportOperationList as $excelmodelsKey => $excelmodelsVal){
				if(!empty($excelmodelsKey))
					echo "<li><a href='#' onclick=OrderCommon.DeliveryBatchOperation('".$excelmodelsKey."')>".$excelmodelsVal."</a></li>";
			}
		?>
			</ul>
		</div>
		
		</div>

<!-- --------------------------------------------批量操作项 end--------------------------------------------------------------- -->

<form action="" method="post" target="_self" id="ordersform" name = 'a'>
<?//=Html::hiddenInput('warehouse_id',$query_condition['default_warehouse_id']);?>
	<table class="table table-condensed table-bordered" style="font-size:12px;margin-top:20px;">
		<tr>
		<th width="6%">
		<span class="glyphicon glyphicon-minus" onclick="spreadorder(this);"></span><input type="checkbox" check-all="e1" />
		</th>
		<th width="6%"><b>小老板单号</b></th>
		<th width="22%"><b>商品SKU</b></th>
		<th width="8%"><b>总价</b></th>
		<th width="10%"><b>下单日期/付款日期</b></th>
		<th width="20%"><b>运输服务</b></th>
		<th width="6%"><b>收件国家</b></th>
		<th width="6%"><b>平台/站点</b></th>
		<th width="6%"><b>平台状态</b></th>
		<th style="min-width: 120px;"><b>操作</b></th>
		</tr>

		<?php 
		$divTagHtml = "";
		?>
		<?php if (count($orders)){foreach ($orders as $order):?>
		<!-- --------------------------------------------订单--------------------------------------------------------------- -->
		<tr style="background-color: #f4f9fc;border:1px solid #d1d1d1;" class="line-<?php echo $order->order_id;?>">
			<td><span class="orderspread glyphicon glyphicon-minus" onclick="spreadorder(this,'<?=$order->order_id?>');"></span><input type="checkbox" class="ck" name="order_id[]" value="<?=$order->order_id?>" data-check="e1">
			</td>
			<td>
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
			客选物流：<?php echo $order->order_source_shipping_method;?><br>
			运输服务：<?php echo isset($allshippingservices[$order->default_shipping_method_code])?$allshippingservices[$order->default_shipping_method_code]:'';?>
			<?php if ($order->is_print_carrier ==1){echo '<span class="glyphicon glyphicon-print" aria-hidden="true" title="已打印物流面单"></span>';}?>
			</td>
			<td>
				<label title="<?=$order->consignee_country?>"><?=$order->consignee_country_code?></label>
			</td>
			<td>
				<?php echo $order->order_source.(empty($order->order_source_site_id) ? '' : '/'.$order->order_source_site_id);?>
			</td>
			<td>
				<b><?php echo $order->order_source_status;?></b>
				<?php if (!empty($order->seller_weight) && (int)$order->seller_weight!==0){
						echo "<br><b>称重重量：".(int)$order->seller_weight." g</b>";
				}?>
			</td>
			<td>
			<span style='display: none;'>
			<?php if ($order->order_source=="aliexpress"){?>
			<a href="<?=Url::to(['/order/aliexpressorder/edit','orderid'=>$order->order_id])?>" target="_blank"><span class="glyphicon glyphicon-edit" title="编辑订单"></span></a>&nbsp;
			<?php }else if($order->order_source=="ebay"){?>
			<a href="<?=Url::to(['/order/order/edit','orderid'=>$order->order_id])?>" target="_blank"><span class="glyphicon glyphicon-edit" title="编辑订单"></span></a>&nbsp;
			<?php }else if($order->order_source=="amazon"){?>
			<a href="<?=Url::to(['/order/amazon-order/edit','orderid'=>$order->order_id])?>" target="_blank"><span class="glyphicon glyphicon-edit" title="编辑订单"></span></a>&nbsp;
			<?php }else if($order->order_source=="cdiscount"){?>
			<a href="<?=Url::to(['/order/cdiscount-order/edit','orderid'=>$order->order_id])?>" target="_blank"><span class="glyphicon glyphicon-edit" title="编辑订单"></span></a>&nbsp;
			<?php }else if($order->order_source=="bonanza"){?>
			<a href="<?=Url::to(['/order/bonanza-order/edit','orderid'=>$order->order_id])?>" target="_blank"><span class="glyphicon glyphicon-edit" title="编辑订单"></span></a>&nbsp;
			<?php }else if($order->order_source=="dhgate"){?>
			<a href="<?=Url::to(['/order/dhgate-order/edit','orderid'=>$order->order_id])?>" target="_blank"><span class="glyphicon glyphicon-edit" title="编辑订单"></span></a>&nbsp;
			<?php }else if($order->order_source=="ensogo"){?>
			<a href="<?=Url::to(['/order/ensogo-order/edit','orderid'=>$order->order_id])?>" target="_blank"><span class="glyphicon glyphicon-edit" title="编辑订单"></span></a>&nbsp;
			<?php }else if($order->order_source=="jumia"){?>
			<a href="<?=Url::to(['/order/jumia-order/edit','orderid'=>$order->order_id])?>" target="_blank"><span class="glyphicon glyphicon-edit" title="编辑订单"></span></a>&nbsp;
			<?php }else if($order->order_source=="lazada"){?>
			<a href="<?=Url::to(['/order/lazada-order/edit','orderid'=>$order->order_id])?>" target="_blank"><span class="glyphicon glyphicon-edit" title="编辑订单"></span></a>&nbsp;
			<?php }else if($order->order_source=="linio"){?>
			<a href="<?=Url::to(['/order/linio-order/edit','orderid'=>$order->order_id])?>"" target="_blank"><span class="glyphicon glyphicon-edit" title="编辑订单"></span></a>&nbsp;
			<?php }else if($order->order_source=="priceminister"){?>
			<a href="<?=Url::to(['/order/priceminister-order/edit','orderid'=>$order->order_id])?>" target="_blank"><span class="glyphicon glyphicon-edit" title="编辑订单"></span></a>&nbsp;
			<?php }else if($order->order_source=="wish"){?>
			<a href="<?=Url::to(['/order/wish-order/edit','orderid'=>$order->order_id])?>" target="_blank"><span class="glyphicon glyphicon-edit" title="编辑订单"></span></a>&nbsp;
			<?php }?>
			</span>
			<a href="<?=Url::to(['/order/logshow/list','orderid'=>$order->order_id])?>" target="_blank"><span class="glyphicon glyphicon-paperclip" title="订单修改日志"></span></a>
			</td>
		</tr>
<?php if (count($order->items)):foreach ($order->items as $key=>$item):?>
		<!-- --------------------------------------------商品--------------------------------------------------------------- -->
		<tr class="xiangqing <?=$order->order_id?> line-<?php echo $order->order_id;?>">
			<td style="border:1px solid #d1d1d1;"><img src="<?= (in_array($order->order_source, array('cdiscount','priceminister')) ? eagle\modules\util\helpers\ImageCacherHelper::getImageCacheUrl($item->photo_primary, $puid, 1) : ((!empty($item->root_sku) && !empty($order_rootsku_product_image[$order->order_id][$item->root_sku])) ? $order_rootsku_product_image[$order->order_id][$item->root_sku] : $item->photo_primary)) ?>" width="60px" height="60px"></td>
			<td colspan="2" style="border:1px solid #d1d1d1;text-align:justify;">
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
					echo '跟踪号:'.CarrierOpenHelper::getOrderShippedTrackingNumber($order['order_id'],$order['customer_number'],$order['default_shipping_method_code']).'<br>';
				}
				?>
				SKU:<b><?=$item->sku?></b><br>
				<?= (empty($item->product_url) ? $item->product_name : '<a href="'.$item->product_url.'" target="_blank">'.$item->product_name.'</a>') ?>
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
			<td  style="border:1px solid #d1d1d1">
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
				<b><?=$order->selleruserid?></b><br>
				<?php } ?>
				<font color="#8b8b8b">买家姓名:</font>
				<b><?=$order->consignee?></b><br>
				<font color="#8b8b8b">买家账号:</font>
				<b><?=$order->source_buyer_user_id?></b><br>
				<font color="#8b8b8b">买家邮箱:</font>
				<b><?=$order->consignee_email?></b>
			</td>
			<td colspan="3"  rowspan="<?=count($order->items)?>"  width="150px" style="word-break:break-all;word-wrap:break-word;border:1px solid #d1d1d1;text-align:left;">
				<font color="#8b8b8b">付款备注:</font><br><b class="text-warning"><?=$order->user_message?></b>
			</td>
			<td  rowspan="<?=count($order->items)?>" width="150" style="word-break:break-all;word-wrap:break-word;border:1px solid #d1d1d1">
			<span><font color="red" id="desc-<?php echo $order->order_id;?>"><?=$order->desc?></font></span>
				<a href="javascript:void(0)" style="border:1px solid #00bb9b;" onclick="updatedesc('<?=$order->order_id?>',this)" oiid="<?=$order->order_id?>"><font color="00bb9b">备注</font></a>
			</td>
			<?php endif;?>
		</tr>	
		<?php endforeach;endif;?>
<tr style="background-color: #d9d9d9;" class="xiangqing <?=$order->order_id?>">
	<td colspan="10" class="row" id="dataline-<?php echo $order->order_id;?>" style="word-break:break-all;word-wrap:break-word;border:1px solid #d1d1d1">
	<?php if(!empty($order->carrier_error)){?>
		<div class="alert-danger" id="message-<?php echo $order->order_id;?>" role="alert" style="text-align:left;"><?php echo $order->carrier_error;?></div>
	<?php }?>
	</td>
</tr>
<?php endforeach;}?>
</table>
</form>
<!-- --------------------------------操作成功显示区域-------------------------------------------- -->
<!-- --------------------------------分页-------------------------------------------- -->
<?php if($pagination):?>
<div id="pager-group">
    <?= \eagle\widgets\SizePager::widget(['pagination'=>$pagination , 'pageSizeOptions'=>array( 5 , 20 , 50 , 100 , 200 ) , 'class'=>'btn-group dropup']);?>
    <div class="btn-group" style="width: 49.6%; text-align: right;">
    	<?=\yii\widgets\LinkPager::widget(['pagination' => $pagination,'options'=>['class'=>'pagination']]);?>
	</div>
	</div>
<?php endif;?>
<!-- --------------------------------分页-------------------------------------------- -->		
</div>
</div>
<?=$divTagHtml?>

<form name="order-related-additional-operation-form" style="display:none;" method="post" target="_self">
<input type="hidden" name="order_id" />
<input type="hidden" name="js_submit" value='js_submit' />
</form>
<div><input type='hidden' id='search_condition' value='<?=$search_condition ?>'>
<input type='hidden' id='search_count' value='<?=$search_count ?>'></div>

<?php
 echo eagle\modules\carrier\helpers\CarrierOpenHelper::getCainiaoPrintModalHtml();
?>
