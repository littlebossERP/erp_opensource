<?php
use eagle\modules\util\helpers\TranslateHelper;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\jui\Dialog;
use yii\data\Sort;
use yii\jui\JuiAsset;
use eagle\modules\purchase\helpers\PurchaseHelper;

$tmp_js_version = '1.06';
$baseUrl = \Yii::$app->urlManager->baseUrl;

$this->registerJsFile($baseUrl."/js/project/statistics/statisticsList.js?v=".$tmp_js_version, ['depends' => ['yii\web\JqueryAsset']]);
$this->registerJsFile($baseUrl."/js/project/statistics/profit/profitList.js?v=".$tmp_js_version, ['depends' => ['yii\web\JqueryAsset']]);
$this->registerJsFile(\Yii::getAlias('@web')."/js/project/statistics/profit/import_file.js", ['depends' => ['yii\web\JqueryAsset']]);
$this->registerJsFile(\Yii::getAlias('@web')."/js/origin_ajaxfileupload.js", ['depends' => ['yii\web\JqueryAsset']]);
$this->registerJsFile($baseUrl."js/project/util/select_country.js?v=".$tmp_js_version, ['depends' => ['yii\jui\JuiAsset','yii\bootstrap\BootstrapPluginAsset']]);

$this->registerJs("statistics.list.init();" , \yii\web\View::POS_READY);
$this->registerJs("profit.list.init();" , \yii\web\View::POS_READY);
$this->registerJs("$.initQtip();" , \yii\web\View::POS_READY);
?>

<style>
.div_inner_td{
	width: 100%;
}
.span_inner_td{
	float: left;
	padding: 6px 0px;
}
.div_choose
{
	border: 1px solid #ccc;
	width:90%;
	padding:5px 0 5px 20px;
	float:left;
}
.div_choose > span
{
	font-size: 15px; 
	line-height: 24px;
	float:left;
}
th
{
	text-align: center !important;
}
</style>
	
<div style="width: 100%; float:left;">
    <div class="div-input-group" style="float: left;margin-left:20px; margin-bottom:10px;">
    	<LABEL class="lb_choose_title">日期类型：</LABEL>
        <SELECT id="select_date_type" class="eagle-form-control" style="float:left; width:85px;margin:0px 30px 0px 0px;">
	    	<OPTION value="create_date" >下单日期</OPTION>
  			<OPTION value="ship_date" >发货日期</OPTION>
  		</SELECT>
		  			
    	<LABEL class="lb_choose_title">筛选日期：</LABEL>
		<input id="statistics_startdate" class="eagle-form-control" type="text" placeholder="'付款日期从 此日期后" 
			value="<?= (empty($_GET['start_date'])?"":$_GET['start_date']);?>" style="width:85px;margin:0px;height:28px;float:left; margin-right:0px;"/>
		<LABEL class="lb_choose_title"> ~ </LABEL>
		<input id="statistics_enddate" class="eagle-form-control" type="text" placeholder="至 此日期前" 
			value="<?= (empty($_GET['end_date'])?"":$_GET['end_date']);?>" style="width:85px;margin:0px;height:28px;float:left; margin-right:20px;"/>
		
		<LABEL class="lb_choose_title">国家：</LABEL>
		<input class="eagle-form-control" type="text" name="selected_country" placeholder="请选择国家" title="根据输入的国家查询"
		    onclick="selected_country_click()" style="width:120px;margin:0px 10px;height:28px;float:left;"/>
		<input type="hidden" name="selected_country_code" value="" />
		    
        <SELECT id="search_type" value="" class="eagle-form-control" style="float:left; margin:0px; ">
            <OPTION value="" <?=(empty($_GET['search_type']) or $_GET['search_type']=="")?"selected":"" ?>><?= TranslateHelper::t('精确搜索') ?></OPTION>
  			<OPTION value="order_id" <?=(!empty($_GET['search_type']) && $_GET['search_type']=="order_id")?"selected":"" ?>><?= TranslateHelper::t('小老板订单号') ?></OPTION>
  			<OPTION value="order_source_id" <?=(!empty($_GET['search_type']) && $_GET['search_type']=="order_source_id")?"selected":"" ?>><?= TranslateHelper::t('平台订单号') ?></OPTION>
  			<OPTION value="tracking_number" <?=(!empty($_GET['search_type']) && $_GET['search_type']=="tracking_number")?"selected":"" ?>><?= TranslateHelper::t('跟踪号') ?></OPTION>
  		</SELECT>
		<input id='search_txt' type="text" class="eagle-form-control" style="width:200px;margin:0px 20px 0px 0px; height:28px;float:left;"
			placeholder="<?= TranslateHelper::t('搜索内容，多个之间用;隔开')?>"
			value="<?= (empty($_GET['search_txt'])?'':$_GET['search_txt'])?>"/>

		<button id="profit_search" class="iv-btn btn-search btn-spacing-middle">搜索</button>
	</div>
	<div class="div_choose">
	    <span>平台渠道：</span>
	    <div style="width: 90%; float:left;">
		    	<label class="lb_check_node"><input type="checkbox" name="select_platform_all" checked>全部</label>
	  		    <?php foreach($platformAccount as $key=>$v){?>	
	      		    <label style="margin-left:0;margin-right:20px; line-height: 25px; ">
	    				<input type="checkbox" name="select_platform" checked value="<?=$v ?>">
	    				<?=$v ?>
	    			</label>
				<?php } ?>
		</div>
	</div>
	<div class="div_choose" style="border-top:none;">
	    <span>店铺账号：</span>
	    <div style="width: 90%; float:left;">
	    	<label class="lb_check_node" style="min-width:240px;"><input type="checkbox" name="select_store_all" checked>全部</label>
	  		    <?php foreach($stores as $key=>$v){?>	
	      		    <label class="lb_check_node" style="min-width:240px;">
	    				<input type="checkbox" name="select_store" checked value="<?=$v ?>" info="<?=$key ?>">
	    				<?=$key ?>
	    			</label>
				<?php } ?>
		</div>
	</div>
	<div id="div_order_type" class="div_choose" style="border-top:none; ">
	    <span>特殊订单类型：</span>
	    <div style="width: 90%; float:left;">
	    	<label class="lb_check_node"><input type="checkbox" name="select_order_type_all" >全部</label>
    		<label id="order_type_fba" style="margin-left:0;margin-right:20px; line-height: 25px; ">
    			<input type="checkbox" name="select_order_type" value="fba">
    			FBA订单
    		</label>
    		<label id="order_type_fbc" style="margin-left:0;margin-right:20px; line-height: 25px; ">
    			<input type="checkbox" name="select_order_type" value="fbc">
    			FBC订单
    		</label>
		</div>
	</div>
</div>

<!-- 功能按钮  -->
<div style="float: left;margin:10px 5px;">
    <button type="button" class="iv-btn btn-important" id="calculat_profit" style="border-style: none; margin-right: 20px;"><?= TranslateHelper::t('计算利润') ?></button>
	<div class="btn-group">
        <a data-toggle="dropdown" >
            <button class="iv-btn btn-primary" >导入物流成本并计算利润<span class="caret"></span></button>
    	</a>
    	<ul class="dropdown-menu">
    		<li style="font-size: 12px;"><a onclick="profit.list.importProfitData('logistics_cost_ordersource')">按照平台订单号</a></li>
    		<li style="font-size: 12px;"><a onclick="profit.list.importProfitData('logistics_cost_tracknumber')">按照跟踪号</a></li>
    	</ul>
    </div>
	<button type="button" class="iv-btn btn-primary" id="export_purchase_cost" onclick="profit.list.importProfitData('product_cost')" style="border-style: none; "><?= TranslateHelper::t('导入商品最新采购报价') ?></button>
	<span qtipkey="statistics_import_profit_product_cost" style="margin-right: 20px;"></span>
	
	<button type="button" class="iv-btn btn-primary" onclick="profit.list.exportExecl()" style="border-style: none; margin-right: 20px;"><?= TranslateHelper::t('导出') ?></button>
	
	<button type="button" class="iv-btn btn-important" onclick="profit.list.synchronizeOrder()" style="border-style: none; "><?= TranslateHelper::t('手动同步已完成订单') ?></button>
	<span qtipkey="statistics_synchronize_order" style="margin-right: 20px;"></span>
	
	<button type="button" class="iv-btn btn-important" onclick="profit.list.updateProductCostFromUnSet()" style="border-style: none; "><?= TranslateHelper::t('更新未设置的采购成本') ?></button>
	<span qtipkey="statistics_update_productcost_from_unset" style="margin-right: 20px;"></span>
	
	<button type="button" class="iv-btn btn-important" onclick="profit.list.showSettingRate()" style="border-style: none; margin-right: 20px;"><?= TranslateHelper::t('设置汇率') ?></button>
</div>
	
<!-- table -->
<div class="shoplist" style="float:left;">
    <table id="prodit_table" cellspacing="0" cellpadding="0" class="table table-hover" style="float:left;font-size: 12px;">
		<tr class="prodit_title">
			<th style="min-width:30px;" title="<?=TranslateHelper::t('全选') ?>"><input type="checkbox" id="select_all" ></th>
			<th style="min-width:100px;">日期</th>
			<th style="min-width:60px;">小老板单号</th>
			<th style="min-width:100px;" qtipkey="statistics_grand_total">订单总价</th>
			<th style="min-width:80px;" qtipkey="statistics_commission_total">佣金</th>
			<th style="min-width:80px;" qtipkey="statistics_paypal_fee">paypal手续费</th>
			<th style="min-width:80px;" qtipkey="statistics_actual_charge">实收费用</th>
			<th style="min-width:80px;">物流成本</th>
			<th style="min-width:80px;">采购成本</th>
			<th style="min-width:80px;" qtipkey="statistics_profit">利润</th>
			<th style="min-width:100px;" qtipkey="statistics_profit_per">成本利润率</th>
			<th style="min-width:100px;" qtipkey="statistics_sales_per">销售利润率</th>
			<th style="min-width:100px;" qtipkey="statistics_weight">包裹重量</th>
			<th style="min-width:150px;">平台订单号</th>
			<th style="min-width:100px;">平台</th>
			<th style="min-width:100px;">店铺</th>
			<th style="min-width:150px;">跟踪号</th>
			<th style="min-width:200px;">物流方式</th>
		</tr>
        
    </table>
    <!-- Modal -->
	<div id="checkOrder" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
        </div><!-- /.modal-content -->
    </div>
    </div>
    <!-- /.modal-dialog -->
</div>
<div style="width: 100%; float: left;">
    <div id="statistics_pager-group" >
    </div>
</div>
<div>
    <input type="hidden" id="choose_start_date" value="" />
    <input type="hidden" id="choose_end_date" value="" />
    <input type="hidden" id="choose_selectplatform" value="" />
    <input type="hidden" id="choose_selectstore" value="" />
    <input type="hidden" id="choose_search_type" value="" />
    <input type="hidden" id="choose_search_txt" value="" />
    <input type="hidden" id="choose_country" value="" />
    <input type="hidden" id="choose_date_type" value="" />
    <input type="hidden" id="choose_order_type" value="" />
</div>

