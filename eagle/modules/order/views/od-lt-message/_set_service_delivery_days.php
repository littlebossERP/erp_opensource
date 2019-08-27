<?php
use yii\helpers\Html;
use eagle\modules\util\helpers\TranslateHelper;

?>

<?php 
$dropDownOptions = [
	'5'=>'5天',
	'10'=>'10天',
	'15'=>'15天',
	'20'=>'20天',
];
?>

<style>
.set-service-delivery-days-win .modal-body{
    max-height: 600px;
	overflow-y: auto;
}
.has-error{
	border-color: #843534;
    box-shadow: inset 0 1px 1px rgba(0, 0, 0, .075), 0 0 6px #ce8483;
}
</style>
<div>
	<form id="service-delivery-days-data">
		<table class="table">
			<tr><th>物流服务名</th><th>送达耗时(天)</th></tr>
			<?php if(!empty($service_setting)){ ?>
			<?php foreach($service_setting as $id=>$data){ ?>
			<tr>
				<td><?=$data['name']?></td>
				<td>
					<?=Html::dropDownList('delivery_days['.$id.']',$data['days'],$dropDownOptions,['class'=>'eagle-form-control','style'=>'width:100px;margin:0px'])?>
				</td>
			</tr>
			<?php }?>
			<?php } ?>
		</table>
	</form>

</div>