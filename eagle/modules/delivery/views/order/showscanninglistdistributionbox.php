<?php
use eagle\modules\util\helpers\TranslateHelper;

?>
<script>
	$(function(){
		showscanninglistdistributionbox.init();
	});
</script>
<style>
	.title
	{
	    font-family: 'Applied Font Bold', 'Applied Font';
	    font-style: normal;
	    font-size: 24px;
		font-weight:bold;
	    color: rgb(51, 51, 51);
		margin-left:20px;
	}
	.code_input
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
    	border-left:1px solid #ddd;
    	border-right:1px solid #ddd;
    	border-bottom:1px solid #ddd; 
    	padding:10px;
    	margin-bottom:10px;
    	width: 800px;
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
    }
    .scanning_search
    {
    	margin-left:20px;
    	height:40px;
    	font-size:20px;
    }
    .tab_order_1 td, .tab_order_2 td
    {
    	border:1px solid #ccc;
    }
</style>

<!-- Nav tabs -->
<ul class="nav nav-tabs">
	<li class="active">
		<a id='tab1' data-toggle="tab"  >扫描包裹分拣</a>
	</li>
	<li>
		<a id='tab2' data-toggle="tab" >扫描SKU分拣</a>
	</li>
</ul>
<div id="scanng_tab1" class="scang_tab">
    <div>
        <span class = "title">扫描/输入：</span>
        <input type="text" id="code_input1"  value="" class="code_input" placeholder="小老板单号/跟踪号">
        <input type="button" id="scanning_search1" class="btn_right iv-btn btn-primary scanning_search" value="<?=TranslateHelper::t('查找')?>" />
    </div>
    
    <div id="scanning_err1" style="color: red; padding-left:100px; display:none;"></div>
    
    <div id="scanning_detail1" style="display:none; margin-top:10px;">
        <div style="background-color:#FFFFC9; margin-bottom:10px; padding:0 20px;">
            <table>
                <tr>
                    <td style="width: 100px;">小老板单号：</td>
                    <td id="orderid1" class="orderid" style="width: 150px;"></td>
                    <td style="width: 100px;">跟踪号：</td>
                    <td id="tracknumber1" style="width: 250px;"></td>
                    <td><input type="button" name="1" class="btn_right iv-btn btn-primary btn-print_canning" value="<?=TranslateHelper::t('打印面单')?>" /></td>
                </tr>
            </table>
        </div>
        <table class="table tab_order_1">
        	<tr>
        		<th style="width: 300px;">商品信息</th>
        		<th style="width: 100px;">数量</th>
        		<th style="width: 200px;">备注/拣货说明</th>
        	</tr>
        </table>
    </div>
</div>
<div id="scanng_tab2" style="display:none;" class="scang_tab">
    <div>
        <span class = "title">扫描/输入：</span>
        <input type="text" id="code_input2"  value="" class="code_input" placeholder="SKU">
        <input type="button" id="scanning_search2" class="btn_right iv-btn btn-primary scanning_search" value="<?=TranslateHelper::t('查找')?>" />

		<label>
		    <input type="checkbox" id="automatic_print_enable" style="position:relative;" <?=$automatic_print_enable==1 ? "checked" : "" ?>> 
		 	商品校检完毕，自动打印
		 	<br>
		 	<a target="_blank" href="http://www.littleboss.com/word_list_188_509.html" style="margin-left:30px; line-height:20px;">点击查看自动打印帮助</a>
		</label>
    </div>
    
    <div id="scanning_err2" style="color: red; padding-left:100px; display:none;"></div>
    
    <div id="scanning_detail2" style="display:none; margin-top:10px;">
        <div style="background-color:#FFFFC9; margin-bottom:10px; padding:0 20px;">
            <table>
                <tr>
                    <td style="width: 100px;">小老板单号：</td>
                    <td id="orderid2" class="orderid" style="width: 150px;"></td>
                    <td style="width: 100px;">跟踪号：</td>
                    <td id="tracknumber2" style="width: 250px;"></td>
                    <td><input type="button" name="2" class="btn_right iv-btn btn-primary btn-print_canning" value="<?=TranslateHelper::t('打印面单')?>" /></td>
                    <td style="width: 50px; padding:10px">
                    	<a href="javascript:" id="scanning_skip" >跳过</a>
                    	<input type="hidden" id="scanning_skip_val" value="" />
                    </td>
                </tr>
            </table>
        </div>
        <table class="table tab_order_2">
        	<tr>
        		<th style="width: 300px;">商品信息</th>
        		<th style="width: 50px;">数量</th>
        		<th style="width: 50px;">校验数量</th>
        		<th style="width: 50px;">校验状态</th>
        		<th style="width: 50px;">打印状态</th>
        		<th style="width: 100px;">备注/拣货说明</th>
        	</tr>
        </table>
    </div>
</div>

<div>
    <table style="width: 650px">
        <tr>
            <td style="width: 95%">
            	<span class="fColor1">说明！</span>&nbsp;
            	<span class="fColor2">通过「打印面单」进行「标记为已打印」后，会自动跳到下一个扫描作业</span>
        	</td>
        	<td>
        	    <input type="button" id="btn_cancel" class="btn_right iv-btn btn-primary modal-close" data-dismiss="modal" value="<?=TranslateHelper::t('关闭')?>" />
        	</td>
	    </tr>
    </table>
</div>


<script>
cs_print.init();
</script>


