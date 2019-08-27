<?php 

use yii\helpers\Html;
use yii\helpers\Url;

?>
<style>
/* 	.drop{ */
/* 		width:250px; */
/* 		height:25px; */
/* 	} */
	.modal-body{
		width:600px;
	}

</style>
<div class="modal-body">
	<p class="h4">如果还没有可启用物流商，请先新建物流商！</p>
	<p><span class="col-sm-4 text-right">可启用的物流商：</span><?php echo Html::dropDownList('openCarri',null,$notOpenCarr,['class'=>'drop iv-input','id'=>'codes','style'=>'width:250px;'])?></p>
	<form id="newAccountMsg" style="margin-top:5px;min-height:200px;"></form>
</div>
<div class="modal-footer">
	<button onclick="createAccount(this)" class="iv-btn btn-sm btn-success" >启用</button>
	<button class="iv-btn btn-default btn-sm modal-close">关 闭</button>
</div>
	
<script>
$(function(){
	loadNewAccount();
	$('#codes').change(function(){
		loadNewAccount();
	});
});
</script>