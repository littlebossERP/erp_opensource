<?php
use yii\helpers\Html;
use eagle\modules\util\helpers\TranslateHelper;
?>

<div>
	<!-- 批量设置div start -->
	<div id="div_batch_setting">
		<?=Html::label(TranslateHelper::t('延长天数'))?>
		<?=Html::textInput('batch_extend_days','',['id'=>'batch_extend_days','style'=>'margin:2px'])?>
		<?=HTML::button(TranslateHelper::t('批量设置'),['class'=>"btn-xs btn-default",'onclick'=>"javascript:$('[name=extend_days]').val($('#batch_extend_days').val())"]) ?>
	</div>
	<!-- 批量设置div end -->

	
	<!-- 订单的备列举 start -->
	<table class="table">
		<thead>
			<tr>
				<th><?= TranslateHelper::t('来源平台单号')?></th>
				<th><?= TranslateHelper::t('买家账号')?></th>
				<th><?= TranslateHelper::t('延长天数')?></th>
			</tr>
		</thead>
		<tbody>
		<?php foreach($orderList as $anOrder):?>	
		<tr>
			<td><?= $anOrder['order_source_order_id']?></td>
			<td><?= $anOrder['selleruserid']?></td>
			<td><?=Html::textInput('extend_days','0',['style'=>'margin:2px', 'data-order-id'=>$anOrder['order_id'] ,'data-selleruserid'=>$anOrder['selleruserid']])?></td>
		</tr>
		
		<?php endforeach;?>
		
		</tbody>
	</table>
	<!-- 订单的备列举 end -->
</div>

