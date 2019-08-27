<?php
use eagle\modules\util\helpers\TranslateHelper;
use eagle\modules\util\helpers\ConfigHelper;
?>
<style>
	.title
	{
	    font-family: "Helvetica Neue",Helvetica,Arial,sans-serif;
	    font-style: normal;
	    font-size: 24px;
	    color: #333;
		margin-left:20px;
	}
	#code_input,.weigh_input
	{
		font-family: "Applied Font";
	    width: 350px;
	    height: 50px;
	    text-align: left;
	    font-size: 20px;
	    margin-bottom: 5px;
		padding-left:20px;
		color: #999;
	}
	.weigh_input
	{
		width: 100px;
		font-size: 20px;
		float: left!important;
	}
	.select_weigh
	{
		height: 50px;
		width: 50px;
		float: left!important;
		border-left:0;
		font-size: 20px;
	}
    .nav-tabs > li.active > a
    {
    	color: #337ab7;
    }
    .nav-tabs > li > a
    {
    	color: #999;
    }
    div.scang_tab
    {
    	padding:10px;
    	float:left;
    	width:680px;
    }
    .fColor1
    {
    	background-color:#009999;
    	color:#fff;
    	font-size:13px;
    }
    .fColor2
    {
    	color:#999;
    	font-size:13px;
    	line-height:20px;
    }
    .scanning_search
    {
/*     	margin-left:30px; */
    	height:50px;
    	font-size:20px;
    }
    .tab_order_ td
    {
    	border:1px solid #ccc;
    }
    .modal-body {
    	padding:0px;
    }
</style>

<div class="modal-body tab-content col-xs-12" style="width:600px;">

<div id="scanng_tab" class="scang_tab">
        <div style="height: 50px; width: 510px;float:left;">
            <span class = "title">扫描/输入：</span>
            <input type="text" id="code_input"  value="" placeholder="SKU">
        </div>
        <div style="height: 50px; width: 70px;float:left;">
        	<input type="button" id="scanning_search" class="btn_right iv-btn btn-primary scanning_search" value="<?=TranslateHelper::t('查找')?>" />
        </div>
    <div id="scanning_err" style="color: red; padding-left:100px; margin-top:52px; display:block;">&nbsp;</div>
    <div id="scanning_detail" style="display:none; margin-top:10px;">
        <table class="table tab_order">
        	<thead><tr id="scanning_th">
        		<th style="display:none;">sku</th>
        		<th style="width: 10%;">图片</th>
        		<th style="width: 60%;">商品信息</th>
        		<th style="width: 20%;">扫描数量</th>
        		<th style="display:none;">商品名称</th>
        		<th style="width: 10%;">操作</th>
        	</tr></thead>
        	<tbody></tbody>
        </table>
    </div>
</div>

</div>
<div class="modal-footer col-xs-12">
					<button type="button" class="btn btn-primary btn_enter">确定</button>
					<button type="button" class="btn btn-default btn-select-product-return modal-close" data-dismiss="modal" >取消</button>
</div>
