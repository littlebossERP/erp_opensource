<?php
use eagle\modules\util\helpers\TranslateHelper;
?>
<style>
    .automaticM_table
    {
    	margin:10px 20px;
    }
    .automaticM_table tr
    {
    	height:40px;
    	font-size:15px;
    }
    .input_test input
    {
    	width:100px;
    	margin:0px 10px;
    }
    .input_test td
    {
    	padding-left: 30px;
    	color:#999;
    }
    .automaticM_radio
    {
    	margin-right: 10px !important;
    }
</style>


<p>识别方式：</p>
<table class="automaticM_table">
    <tr>
        <td>
            <label>
                <input class="automaticM_radio" type="radio" name="automaticMType[]" value="1" checked="checked"> 识别「平台SKU」与「本地SKU」
            </label>
        </td>
    </tr>
    <tr>
        <td>
            <label>
                <input class="automaticM_radio" type="radio" name="automaticMType[]" value="2"> 识别忽略前缀、后缀的的「平台SKU」与「本地SKU」
            </label>
        </td>
    </tr>
    <tr class="input_test">
        <td>
                                    忽略前缀：
            <input type="text" name="startStr" value="">
                                    忽略后缀：
            <input type="text" name="endStr" value="">
        </td>
    </tr>
    <tr>
        <td>
            <label>
                <input class="automaticM_radio" type="radio" name="automaticMType[]" value="3">识别截取后的的「平台SKU」与「本地SKU」
            </label>
        </td>
    </tr>
    <tr class="input_test">
        <td>
                                    截取第：
            <input type="text" name="startLen" value="" placeholder="请输入数字">
                                    到
            <input type="text" name="endLen" value="" placeholder="请输入数字">
                                    字符
        </td>
    </tr>
</table>
<!--<hr>  -->
<div>
	<!--<label style="float: left; ">
    	<input type="checkbox" name="automaticMSame"> 启用
    	<span style="color:#999; ">(当「订单SKU」与「本地SKU」一致时，自动完成配对)</span>
    </label> -->
    
    <input type="button" id="btn_cancel" style="float: right;" class="iv-btn btn-primary modal-close" data-dismiss="modal" value="<?=TranslateHelper::t('关闭')?>" />
    <input type="button" id="btn_automaticM" style="float: right; margin-right: 20px;" class="iv-btn btn-primary modal-close" data-dismiss="modal" value="<?=TranslateHelper::t('开始识别')?>" />
        	
</div>





