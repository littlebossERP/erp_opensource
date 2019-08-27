<?php
use yii\helpers\Html;
use yii\helpers\Url;
use eagle\modules\util\helpers\TranslateHelper;


?>
<style>

</style>
<div>
	<?php if(empty($historys) && !empty($errMsg)): ?>
		<p><?=$errMsg ?></p>
	<?php else:?>
	<table style="width:100%;" class="table">
		<tr>
			<th style="text-align: left;">获取时间</th>
			<th style="text-align: left;">BestSeller店铺名</th>
			<th style="text-align: left;">BestSeller 售价</th>
		</tr>
		<?php foreach($historys as $index=>$history){
				$get_bestseller = false;
				if(!empty($history['bestseller_name']) && in_array($history['bestseller_name'],$shopNames)){
					$get_bestseller = true;
				}
		?>
		<tr <?=!is_int($index / 2)?"class='striped-row'":"" ?> style="<?=($get_bestseller)?'color:green':'color:red' ?>">
			<td style="text-align: left;">
				<?=empty($history['create'])?'':$history['create'] ?>
			</td>
			<td>
				<b><?=empty($history['bestseller_name'])?'未有卖家销售':$history['bestseller_name'] ?></b>
			</td>
			<td>
				<?=empty($history['bestseller_price'])?'--':$history['bestseller_price'].'€' ?>
			</td>
		</tr>
		<?php } ?>
	</table>
	<?php endif;?>
</div>
