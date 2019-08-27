<?php
use yii\helpers\Html;
?>

<style>
/* .modal-body{ */
/* /*  	width:250px;  */ */
/* 	height:80px; */
/* } */

/* .iv-modal .modal-content{ */
/* 	min-height:150px; */
/* 	max-height:300px; */
/* 	height:170px; */
/* } */

/* .modal-footer{ */
/* 	border-top:none; */
/* } */


</style>

<div class="modal-body" style="margin-bottom: 10px;">
<form id='warehouse_enable_or_create_from'>

<?=Html::hiddenInput('warehouse_type',$type) ?>

<?php
if($type == 'enable'){
?>
<label style="font-weight:bold;">可开启仓库：</label>
<?=Html::dropDownList('notWarehouseDropDownid','',$notWarehouseIdNameMap,['class'=>'iv-input','style'=>'width:150px;','prompt'=>''])?>
<?php
}else if($type == 'create'){
?>
<label style="font-weight:bold;">仓库名：</label>
<?=Html::textInput('new_warehouse_name','',['class'=>'iv-input','style'=>'width:150px;','prompt'=>'']) ?>
<?php
}else if($type == 'enable_oversea'){
?>

<table>
<tr><td>可开启仓库：</td><td><?=Html::dropDownList('notWarehouseDropDownid','',$notWarehouseIdNameMap,['class'=>'iv-input','style'=>'width:150px;','prompt'=>''])?></td></tr>
</table>


<?php
}else if($type == 'create_oversea'){
?>
<div style="margin-bottom: 10px;">
<label style="font-weight:bold;">海外仓类型：</label>
<?= Html::radioList('oversea_type_radio',0,['API','Excel'],['style'=>'margin-left:5px;display: inline-block;','onchange'=>'createOverseaWarehouseType()'])?>
</div>

<div id='create_oversea_div' style="margin-bottom: 10px;">
<label style="font-weight:bold;">物流商：</label>
<?=Html::dropDownList('carrierOverseaDropDownid','',$carrierOverseaList,['class'=>'iv-input','style'=>'width:150px;','prompt'=>'','onchange'=>'carrierOverseaDropChange()'])?>
<div id='overseaWarehousediv' style='display:inline-block;margin-left:10px;'  ></div>

</div>

<div style="margin-bottom: 10px;">
<label style="font-weight:bold;">仓库名：</label>
<?=Html::textInput('new_warehouse_name','',['class'=>'iv-input','style'=>'width:150px;','prompt'=>'']) ?>
</div>

<?php
}
?>

</form>
</div>

<div class="modal-footer">
	<button onclick="enble_or_crate_warehouse()" class="iv-btn btn-sm btn-success" ><?php 
// 		($type == 'enable' ? '启用' : '保存') 
		switch ($type){
			case 'enable':
			case 'enable_oversea':
				echo '启用';
				break;
			case 'create':
			case 'create_oversea':
				echo '保存';
				break;
		}
	?></button>
	<button class="iv-btn btn-default btn-sm modal-close">关 闭</button>
</div>