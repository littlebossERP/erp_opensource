<?php
use yii\helpers\Html;
use eagle\modules\util\helpers\TranslateHelper;
use yii\data\Sort;
use yii\helpers\Url;

$sortAttr = ['platform','track_no','create_time','update_time','status','ship_by','delivery_fee','notified_seller'];
$sort = new Sort(['attributes' =>$sortAttr]);

/*
$sort_label = [
	''=>TranslateHelper::t('物流商'),
	''=>TranslateHelper::t('包裹总数'),
	''=>TranslateHelper::t('平均时效'),
	''=>TranslateHelper::t('已签收'),
	''=>TranslateHelper::t('递送异常'),
	''=>TranslateHelper::t('运输途中'),
	''=>TranslateHelper::t('递送超时'),
	''=>TranslateHelper::t('无法交运'),
	''=>TranslateHelper::t('总费用'),
	''=>TranslateHelper::t('平均费用'),
	
];
*/
$country_list = $addi_params['countries'];
?>

<div>
	<div>
		<p><small class="font-color-2"><?= TranslateHelper::t('请选择需要统计的时间段，建议参考该结果，用于核实物流商的结账费用清单，不同物流商的服务质量等.');?></small></p>
		<form action="<?= Url::to(['/tracking/tracking/'.yii::$app->controller->action->id])?>" method="GET">
		<select name="to_nations"  class="eagle-form-control" >
    		<option value=""><?= TranslateHelper::t('国家 ')?></option>
			<?php foreach($country_list as $code=>$label):?>
			<option value="<?= $code?>" <?php if (! empty($_GET['to_nations'])) if ($_GET['to_nations']==$code) echo " selected " ?>><?= $label?></option>
			<?php endforeach;?>
    	</select>
		<input type="text" class="eagle-form-control" id="startdate" name="startdate" value="<?= (empty($_GET['startdate'])?"":$_GET['startdate']);?>"> 
    	<?= TranslateHelper::t('到')?>
    	<input type="text" class="eagle-form-control" id="enddate" name="enddate" value="<?= (empty($_GET['enddate'])?"":$_GET['enddate']);?>">
    	<button type="submit" class="btn btn-success btn-sm">
			<span class="glyphicon glyphicon-search" aria-hidden="true"></span>
    		<?= TranslateHelper::t('搜索')?>
    	</button>
    	<input type="hidden" name="t_search" value="t_search">
    	
    	
    	</form>
	</div>
	
	<!-- Table -->
	<table class="table table-hover">
		<thead>
		<tr>
			<th><?= TranslateHelper::t('物流商')?></th>
			<th><?= TranslateHelper::t('包裹总数')?></th>
			<th><?= TranslateHelper::t('平均时效')?></th>
			<th><?= TranslateHelper::t('已签收')?></th>
			<th><?= TranslateHelper::t('递送异常')?></th>
			<th><?= TranslateHelper::t('运输途中')?></th>
			<th><?= TranslateHelper::t('递送超时')?></th>
			<th><?= TranslateHelper::t('无法交运')?></th>
			<th><?= TranslateHelper::t('总费用')?></th>
			<th><?= TranslateHelper::t('平均费用')?></th>
			<th><?= TranslateHelper::t('操作')?></th>
		</tr>
		</thead>
		<tbody>
		<?php 
		
		foreach($analysisData['data'] as $row){
			echo "
			<tr>
				<td class='font-color-1' data-ship-by=''>".(empty($row['ship_by'])?TranslateHelper::t('无物流商'):$row['ship_by'])."</td>
				<td>".$row['total_count']."</td>
				<td>".round($row['avg_day'],1)."</td>
				<td>".$row['received_parcel']."(".round($row['received_parcel']/$row['total_count']*100 , 2) ."%)</td>
				<td>".$row['exception_parcel']."(".round($row['exception_parcel']/$row['total_count']*100 , 2) ."%)</td>
				<td>".$row['shipping_parcel']."(".round($row['shipping_parcel']/$row['total_count']*100 , 2) ."%)</td>
				<td>".$row['ship_over_time_parcel']."(".round($row['ship_over_time_parcel']/$row['total_count']*100 , 2) ."%)</td>
				<td>".$row['unshipped_parcel']."(".round($row['unshipped_parcel']/$row['total_count']*100 , 2) ."%)</td>
				<td>".round($row['total_delivery_fee'],2)."</td>
				<td>".round($row['avg_delivery_fee'],2)."</td>
				<td><small><a onclick='delivery_statistical_analysis.export_detail_excel(this)'>".TranslateHelper::t('导出明细')."</a></small></td>
			</tr>
			";
		}?>
	</tbody>
	</table>
	<?php if (!empty($_GET['t_search'])):?>
	<input type="hidden" id='tips_message' value='<?= (empty($analysisData['message'])?"":$analysisData['message']); ?>'>
	<?php endif;?>
</div>