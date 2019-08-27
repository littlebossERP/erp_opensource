<?php
use yii\helpers\Html;
use eagle\modules\util\helpers\TranslateHelper;
?>

<div>

	
	<!-- 订单的备列举 start -->
	<table class="table">
		<thead>
			<tr>
				<th width="25%"><?= TranslateHelper::t('来源平台单号')?></th>
				<th width="25%"><?= TranslateHelper::t('买家账号')?></th>
				<th width="50%"><?= TranslateHelper::t('商品链接')?></th>
			</tr>
		</thead>
		<tbody>
		<?php foreach($orderList as $anOrder):?>	
		<tr>
			<td width="25%" style="word-break: break-all;"><?= $anOrder['order_source_order_id']?></td>
			<td width="25%" style="word-break: break-all;"><?= $anOrder['selleruserid']?></td>
			<td width="50%" style="word-break: break-all;"><?=Html::textarea('order_memo',$anOrder['product_url'],['rows'=>'2','style'=>'margin:2px;width:100%', 'data-order-id'=>$anOrder['order_id']])?></td>
		</tr>
		
		<?php endforeach;?>
		
		</tbody>
	</table>
	<!-- 订单的备列举 end -->
</div>

