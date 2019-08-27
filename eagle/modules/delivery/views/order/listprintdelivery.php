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
$baseUrl = \Yii::$app->urlManager->baseUrl . '/';
$tmp_js_version = '1.6';

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
$this->registerJsFile($baseUrl."js/project/delivery/order/showscanninglistdistributionbox.js", ['depends' => ['yii\jui\JuiAsset','yii\bootstrap\BootstrapPluginAsset']]);
$this->registerJsFile($baseUrl."js/project/delivery/order/showscanninglistdeliveryonebox.js", ['depends' => ['yii\jui\JuiAsset','yii\bootstrap\BootstrapPluginAsset']]);
$this->registerJsFile($baseUrl."js/project/delivery/order/showscanninglistdeliverychoosebox.js", ['depends' => ['yii\jui\JuiAsset','yii\bootstrap\BootstrapPluginAsset']]);

//引用ebay的图标
$this->registerCssFile(\Yii::getAlias('@web').'/css/order/order.css');
$this->registerJsFile ( \Yii::getAlias ( '@web' ) . "/js/project/carrier/carrieroperate/lbalionlinedelivery.js", ['depends' => ['yii\web\JqueryAsset']]);

$this->registerJsFile ( \Yii::getAlias ( '@web' ) . "/js/project/delivery/order/deliveryOms.js?v=".$tmp_js_version, ['depends' => ['yii\web\JqueryAsset']]);
$this->registerJs("OrderTag.init();" , \yii\web\View::POS_READY);
$this->registerJs("$.initQtip();" , \yii\web\View::POS_READY);
$tmpOdtimetype = Odorder::$timetype;
$tmpCustomsort = Odorder::$customsort;

$user=\Yii::$app->user->identity;
$puid = $user->getParentUid();

$use_mode = isset($_REQUEST['use_mode']) ? $_REQUEST['use_mode'] : '';

$this->registerJs("$('.prod_img').popover();" , \yii\web\View::POS_READY);

$this->registerJsFile ( \Yii::getAlias ( '@web' ) . "/js/project/delivery/order/cainiao.js?v=".\eagle\modules\order\helpers\OrderListV3Helper::$OrderCommonJSV3, ['depends' => ['yii\web\JqueryAsset']]);
$this->registerJsFile(\Yii::getAlias('@web').'js/jquery.json-2.4.js', ['depends' => ['yii\jui\JuiAsset','yii\bootstrap\BootstrapPluginAsset']]);
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
span.badge.badge_orange{
	background:rgb(255,153,0);
 	color:#fff;
}

</style>
<?php 
echo $this->render('//layouts/new/left_menu_2',['menu'=>[]]);
?>

<div class="content-wrapper" >
<?= ($use_mode == '') ? $order_nav_html : ''; ?>
<!-- --------------------------------------------搜索 bigin--------------------------------------------------------------- -->
	<div style="margin-top:15px">
		<!-- 搜索区域 -->
		<form class="form-inline" id="searchForm" name="form1" action="" method="post">
		<?=Html::hiddenInput('use_mode',$use_mode);?>
		<?=Html::hiddenInput('showsearch',$showsearch,['id'=>'showsearch']);?>
		<?=Html::hiddenInput('consignee_country_code',@$query_condition['consignee_country_code'],['id'=>'consignee_country_code']);?>
		<?=Html::hiddenInput('carrierPrintType',@$query_condition['carrierPrintType'],['id'=>'carrierPrintType']);?>
		<?=Html::hiddenInput('default_warehouse_id',@$query_condition['default_warehouse_id'],['id'=>'warehouse_search']);?>
		<div style="margin:0px 0px 0px 0px">
		<!----------------------------------------------------------- 卖家账号 ----------------------------------------------------------->
		<?=Html::dropDownList('selleruserid',isset($query_condition['selleruserid'])?$query_condition['selleruserid']:'',$selleruserids,['class'=>'iv-input','id'=>'selleruserid','style'=>'margin:0px','prompt'=>'平台 & 店铺'])?>
		<!----------------------------------------------------------- 精确搜索 ----------------------------------------------------------->
		<div class="input-group iv-input">
			<?=Html::dropDownList('keys',isset($query_condition['keys'])?$query_condition['keys']:'',$keys,['class'=>'iv-input','style'=>'width:100px;'])?>
	      	<?=Html::textInput('searchval',isset($query_condition['searchval'])?$query_condition['searchval']:'',['class'=>'iv-input','id'=>'num','onkeypress'=>"if(event.keyCode==13){searchButtonClick('listprintdelivery');return false;}"])?>
	    </div>
	    
	    <?= Html::dropdownlist('default_shipping_method_code',isset($query_condition['default_shipping_method_code'])?$query_condition['default_shipping_method_code']:'',$shippingServices,['prompt'=>'运输服务','class'=>'iv-input','style'=>'width:380px;','onchange'=>"searchButtonClick('listprintdelivery')",'id'=>'shipmethod'])?>
		    <!----------------------------------------------------------- 提交按钮 ----------------------------------------------------------->
		    <?=Html::checkbox('fuzzy',@$_REQUEST['fuzzy'],['label'=>TranslateHelper::t('模糊搜索')])?>
		    <?=Html::Button('搜索',['class'=>"iv-btn btn-search btn-spacing-middle",'id'=>'search','onclick'=>"searchButtonClick('listprintdelivery')"])?>
		    
		    <div class="pull-right" style="height: 40px;display:none;">
	    	<?php // Html::button('重置',['class'=>"iv-btn btn-search btn-spacing-middle",'onclick'=>"javascript:cleform();"])?>
	    		 
	    	</div>
	    	
	    	<!----------------------------------------------------------- 保持高级搜索展开 ----------------------------------------------------------->
	    	<div class="mutisearch">
			<!-- ----------------------------------第二行--------------------------------------------------------------------------------------------- -->
	    	<div style="margin:20px 0px 0px 0px">
			<div style='display:inline;'>
			<?=Html::dropDownList('timetype',isset($query_condition['timetype'])?$query_condition['timetype']:'',$tmpOdtimetype,['class'=>'iv-input'])?>
        	<?=Html::input('date','date_from',isset($query_condition['date_from'])?$query_condition['date_from']:'',['class'=>'iv-input','style'=>'width:150px;margin:0px'])?>
        	至
			<?=Html::input('date','date_to',isset($query_condition['date_to'])?$query_condition['date_to']:'',['class'=>'iv-input','style'=>'width:150px;margin:0px'])?>
			</div>
			
			<div style='display:inline;margin-left: 8px;'>
			<strong style="font-weight: bold;font-size:12px;">排序：</strong>
			<?=Html::dropDownList('customsort',isset($query_condition['customsort'])?$query_condition['customsort']:'',$tmpCustomsort,['class'=>'iv-input','style'=>'width:100px;margin:0px'])?>
			<?=Html::checkbox('ordersorttype',isset($query_condition['ordersorttype'])?$query_condition['ordersorttype']:'',['label'=>TranslateHelper::t('升序'),'value'=>'asc'])?>
			</div>
			
			</div>
			<?php
				echo $counter['warehouse_html'];
			?>
			<!-- ----------------------------------第三行--------------------------------------------------------------------------------------------- -->
			<div style="margin:20px 0px 0px 0px">
			<strong style="font-weight: bold;font-size:12px;">打单类型：</strong>
			<?php
				$tmpCarrierPrintType = CarrierOpenHelper::$carrierPrintType;
				echo "<a style='margin-right: 20px;' class=' ".(@$query_condition['carrierPrintType'] == '' ? 'text-rever-important' : '')."' value='' onclick='carrierPrintBtnClick(this,\"listprintdelivery\")'>".'全部'."</a>";
				
				foreach ($tmpCarrierPrintType as $tag_code=> $label){
					echo "<a style='margin-right: 20px;' class=' ".(@$query_condition['carrierPrintType'] == $tag_code ? 'text-rever-important' : '')."' value='".$tag_code."' onclick='carrierPrintBtnClick(this,\"listprintdelivery\")'>".$label."</a>";
				}
			?>
			</div>
			<!-- ----------------------------------第四行--------------------------------------------------------------------------------------------- -->
			<div style="margin:20px 0px 0px 0px">
			<strong style="font-weight: bold;font-size:12px;">系统标签：</strong>
			<?php 
				$tmpOrderTagHelper = OrderTagHelper::$OrderSysTagMapping;
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
		<?php $doAction = array('suspendDelivery'=>'暂停发货','outOfStock'=>'标记缺货'); ?>
		<?php if(empty($doAction)) $doAction = array(); ?>
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
				if(!empty($doActionKey))
					echo "<li><a href='#' onclick=doactionnew('".$doActionKey."','listprintdelivery')>".$doActionVal."</a></li>";
			}
		?>
			</ul>
		</div>
		<?php } ?>
		
		<?php echo Html::button(TranslateHelper::t('确认发货完成'),['class'=>"iv-btn btn-important",'onclick'=>"doactionnew(this)",'value'=>'setFinished_btn','style'=>'margin-right:3px;']); ?>
		<div class="btn-group">
		  <button type="button" class="iv-btn btn-important dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
		    批量打印 <span class="caret"></span>
		  </button>
		  <ul class="dropdown-menu">
		    <li><a href="#" onclick="doactionnew('ExternalDoprint_0')">打印面单</a></li>
		    <li><a href="#" onclick="doprint('picking_product_sum','listprintdelivery')">打印拣货单（产品汇总）</a></li>
		    <li><a href="#" onclick="doactionnew('printDistribution','listprintdelivery');">打印拣货单（订单汇总）</a></li>
		    <li><a href="#" onclick="doprint('picking_product_order_sum','listprintdelivery')">打印拣货单（产品汇总+订单汇总）</a></li>
		    <li><a href="#" onclick="doprint('integrationlabel','listprintdelivery')">打印面单+拣货单（订单汇总）</a></li>
		  </ul>
		</div>
		<span qtipkey="print_picking_carrier_lable"></span>
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
		 
		<?php echo Html::button(TranslateHelper::t('导入跟踪号'),['class'=>"iv-btn btn-important",'onclick'=>"OrderCommon.importTrackNoBox()"]);?>
		<?php echo"
        		<div class='btn-group'>
        		    <a data-toggle='dropdown' >
        		        <button class='iv-btn btn-important' >扫描发货<span class='caret'></span></button>
        			</a>
        			<ul class='dropdown-menu'>
        				<li style='font-size: 12px;'><a onclick='showScanningListDistributionBox(\"listprintdelivery\")'>扫描分拣包裹</a></li>
        				<li style='font-size: 12px;'><a onclick='showScanningDeliveryOneBox(\"listprintdelivery\")'>扫描逐单发货</a></li>
        				<li style='font-size: 12px;'><a onclick='showScanningDeliveryChooseBox(\"listprintdelivery\")'>扫描统一发货</a></li>
        			</ul>
        		</div>"
    		?>
		</div>
		
<!-- --------------------------------------------批量操作项 end--------------------------------------------------------------- -->
<form action="" method="post" target="_self" id="ordersform" name = 'a'>
	<table id='carrier-list-table' class="table table-condensed table-bordered" style="font-size:12px;margin-top:20px;">
		<tr>
		<th width="8%">
		<span style='display:none;' class="glyphicon glyphicon-minus" onclick="spreadorder(this);"></span><input type="checkbox"  onclick='ck_allOnClick(this)' /><b>小老板单号</b>
		</th>
		<th width="100" style="text-align: center;border:1px solid #d9effc;">图片</th>
		<th width="300" style="text-align: center;border:1px solid #d9effc;">中文配货名称</th>
		<th style="text-align: center;border:1px solid #d9effc;">数量</th>
		<th width="300" style="text-align: center;border:1px solid #d9effc;">商品名</th>
		<th style="text-align: center;border:1px solid #d9effc;">SKU</th>
		<th style="text-align: center;border:1px solid #d9effc;">属性</th>
		</tr>

		<?php if (count($orders)){foreach ($orders as $order){ $i =1;?>
		<?php if (count($order->items)){foreach ($order->items as $key=>$item){?>
		<?php
		//用于记录SKU,假如订单的sku为空用product_name去商品模块再查找一次
		$tmpSku = $item->root_sku;
		$tmp_product = \eagle\modules\catalog\apihelpers\ProductApiHelper::getProductInfo($tmpSku);
		?>
		<tr class="xiangqing <?=$order->order_id?> line-<?php echo $order->order_id;?>">
			<?php if($i == 1) {?>
			<td style="border:1px solid #d9effc; text-align:center; vertical-align:middle;" rowspan="<?=count($order->items);?>"><input type="checkbox" class="ck" name="order_id[]" value="<?=$order->order_id?>" data-check="e1" ><?=$order->order_id?></td>
			<?php }?>
			<td style="border:1px solid #d9effc; text-align:center; vertical-align:middle;">
				<img class="prod_img" src="<?= (in_array($order->order_source, array('cdiscount','priceminister')) ? eagle\modules\util\helpers\ImageCacherHelper::getImageCacheUrl($item->photo_primary, $puid, 1) : ((!empty($item->root_sku) && !empty($order_rootsku_product_image[$order->order_id][$item->root_sku])) ? $order_rootsku_product_image[$order->order_id][$item->root_sku] : $item->photo_primary)) ?>" width="60px" height="60px" data-toggle="popover" data-content="<img width='350px' src='<?=str_replace('.jpg_50x50','',$item->photo_primary)?>'>" data-html="true" data-trigger="hover">
			</td>
			<td style="border:1px solid #d9effc; text-align:center; vertical-align:middle;">
				<?=(empty($tmp_product['prod_name_ch']) ? '' : $tmp_product['prod_name_ch']) ?>
			</td>
			<td style="border:1px solid #d9effc; text-align:center; vertical-align:middle;">
				<?= '<span class="badge '.($item->quantity > 1 ? 'badge_orange' : '').'" >'.$item->quantity.'</span>' ?>
			</td>
			<td style="border:1px solid #d9effc; text-align:center; vertical-align:middle;">
				<?=(empty($tmp_product['name']) ? $item->product_name : $tmp_product['name']) ?>
			</td>
			<td style="border:1px solid #d9effc; text-align:center; vertical-align:middle;">
				<?=$item->sku?>
			</td>
			<td style="border:1px solid #d9effc; text-align:center; vertical-align:middle;">
				<?=$item->product_attributes?>
			</td>
		</tr>
		
		<?php $i++;}}?>
<tr style="background-color: #d9d9d9;" class="xiangqing <?=$order->order_id?>">
	<td colspan="7" class="row" id="dataline-<?php echo $order->order_id;?>" style="word-break:break-all;word-wrap:break-word;border:1px solid #d1d1d1">
	</td>
</tr>
<?php }}?>
</table>
</form>
<!-- --------------------------------操作成功显示区域-------------------------------------------- -->
<!-- --------------------------------分页-------------------------------------------- -->
<?php if($pagination):?>
<div>
	<div id="carrier-list-pager" class="pager-group">
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
	$options['action'] = \Yii::$app->request->getPathInfo().'?use_mode='.$use_mode.$tmp_and_where; // ajax请求的 action
	$options['page'] = $pagination->getPage();// 当前页码
	$options['per-page'] = $pagination->getPageSize();// 当前page size
	$options['sort'] = isset($_REQUEST['sort'])?$_REQUEST['sort']:'';// 当前排序
	$this->registerJs('$("#carrier-list-table").initGetPageEvent('.json_encode($options).')' , \Yii\web\View::POS_READY);
?>
<!-- --------------------------------分页-------------------------------------------- -->		
</div>
</div>

<form name="order-related-additional-operation-form" style="display:none;" method="post" target="_self">
<input type="hidden" name="order_id" />
<input type="hidden" name="js_submit" value='js_submit' />
</form>
<div><input type='hidden' id='search_condition' value='<?=$search_condition ?>'>
	    	 <input type='hidden' id='search_count' value='<?=$search_count ?>'></div>
	    	 
<?php
	$this->registerJs("$('[role=\"tooltip\"]').hide();" , \yii\web\View::POS_READY);
?>

<?php
 echo eagle\modules\carrier\helpers\CarrierOpenHelper::getCainiaoPrintModalHtml();
?>
