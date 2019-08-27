<?php
use eagle\modules\util\helpers\TranslateHelper;
use eagle\modules\util\helpers\ConfigHelper;
?>
<script>
	$(function(){
		showscanninglistdeliveryonebox.init();
	});
</script>
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
    	margin-left:30px;
    	height:50px;
    	font-size:20px;
    }
    .tab_order_ td
    {
    	border:1px solid #ccc;
    }
</style>

<!-- Nav tabs -->

<div id="scanng_tab" class="scang_tab">
    <div>
        <?php $weighing_enable = ConfigHelper::getConfig("weighing_enable", "NO_CACHE");
            if(empty($weighing_enable))
                $weighing_enable = 0;
        ?>
        <div style="height: 50px; width: 510px; float: left;">
            <span class = "title">扫描/输入：</span>
            <input type="text" id="code_input"  value="" placeholder="小老板单号/跟踪号">
            <input type="hidden" id="old_orderid" value="" />
            <input type="hidden" id="old_tracknum" value="" />
        </div>
        <div id="weighing_enable_detail" style="height: 50px; width: 150px; float: left; display:<?=$weighing_enable==1 ? "block" : "none" ?>;">
            <input type="text" id="weigh_input"  value="" class="weigh_input" placeholder="电子称">
            <select class="select_weigh">
                <OPTION>g</OPTION>
                <OPTION>kg</OPTION>
            </select>
        </div>
        <input type="button" id="scanning_search" class="btn_right iv-btn btn-primary scanning_search" value="<?=TranslateHelper::t('查找')?>" />
        
        <div style="float: right;">
			<label>
			    <input type="checkbox" id="weighing_enable" style="position:relative;" <?=$weighing_enable==1 ? "checked" : "" ?>> 
			            启用称重功能
			</label>
		</div>
    </div>
    
    <div id="scanning_err" style="color: red; padding-left:100px; margin-top:10px; display:none;"></div>
    
    <div id="scanning_detail" style="display:none; margin-top:10px;">
        <table class="table tab_order">
        	<tr>
        		<th style="width: 100px;">小老板单号 / 跟踪号</th>
        		<th style="width: 130px;">订单号</th>
        		<th style="width: 130px;">SKU</th>
        		<th style="width: 130px;">图片</th>
        		<th style="width: 70px;">商品净重（g）</th>
        		<th style="width: 70px;">称重重量（g）</th>
        		<th style="width: 100px;">备注/提货说明</th>
        		<th style="width: 50px;">订单状态</th>
        		<th style="width: 50px;">物流状态</th>
        		<th style="width: 50px;">状态</th>
        	</tr>
        </table>
    </div>
</div>

<div>
    <table style="width: 1000px">
        <tr>
            <td style="width: 950px">
            	<span class="fColor1">说明！</span>&nbsp;
            	<span class="fColor2">建议将输入法设置为windows默认的英文输入法，第一次扫描执行查询，再次扫描执行发货。（称重功能，非USB设备请<a href="http://www.littleboss.com/word_list_83_201.html" target="_blank">下载驱动</a>）</span>
        	</td>
        	<td>
        	    <input type="button" id="btn_cancel" class="btn_right iv-btn btn-primary modal-close" data-dismiss="modal" value="<?=TranslateHelper::t('关闭')?>" />
        	</td>
	    </tr>
    </table>
</div>





