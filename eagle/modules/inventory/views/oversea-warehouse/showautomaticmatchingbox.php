<?php
use eagle\modules\util\helpers\TranslateHelper;
?>
<style>
    .matching_table
    {
    	margin:10px 20px;
    }
    .matching_table tr
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
    .matching_radio
    {
    	margin-right: 10px !important;
    }
</style>


<p>识别方式：</p>
<table class="matching_table">
    <tr>
        <td>
            <label>
                <input class="matching_radio" type="radio" name="matchingType[]" value="1" checked="checked"> 「海外仓SKU」与「本地SKU」一样时配对
            </label>
        </td>
    </tr>
    <tr>
        <td>
            <label>
                <input class="matching_radio" type="radio" name="matchingType[]" value="2"> 忽略前缀、后缀的的「海外仓SKU」与「本地SKU」配对
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
                <input class="matching_radio" type="radio" name="matchingType[]" value="3">截取后的的「海外仓SKU」与「本地SKU」配对
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

<div>
    <input type="button" id="btn_cancel" style="float: right;" class="iv-btn btn-primary modal-close" data-dismiss="modal" value="<?=TranslateHelper::t('关闭')?>" />
    <input type="button" id="btn_matching" style="float: right; margin-right: 20px;" class="iv-btn btn-primary modal-close" data-dismiss="modal" value="<?=TranslateHelper::t('开始配对')?>" />
        	
</div>





