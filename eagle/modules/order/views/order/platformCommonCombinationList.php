<?php
use yii\helpers\Html;
use yii\helpers\Url;
?>

<style>
 .modal-dialog{ 
 	width: 400px;
 }

.pmb20{
	margin-bottom: 20px;
}

</style>

<div style='width: 280px;'>
	<div style='margin-left: 20px;'>
	
	<p class='pmb20'>保存所选账号为常用组合，方便以后查询</p>
	<p class='pmb20'><strong>组合名称：</strong><input type="text" id='com_name_platform_id' maxlength="6" placeholder="请填写名称"></p>
	<p><span class="explainTit">说明！</span><span class="explainContent">请使用6个以内的汉字或英文字符。</span></p>
	
	</div>
</div>

<div style="height:10px;clear: both;"><hr></div>

<div class="modal-footer" style='text-align: center;'>
	<button type="button" class="btn btn-primary platform_common">确定</button>
	<button type="button" class="btn btn-default modal-close" data-dismiss="modal" >取消</button>
</div>