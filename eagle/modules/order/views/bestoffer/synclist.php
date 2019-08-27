<?php
use yii\helpers\Html;
use yii\helpers\Url;

?>
<div class="alert ">
	<form action="" method="post">
		<table  class="table table-bordered ">
			<tr><td>
			eBay账号<?=Html::checkboxList('selleruserid','',$selleruserids)?>
			</td></tr>
		</table>
		<?=Html::submitButton('同步',['class'=>'btn btn-success'])?>
	</form>
</div>
<div>
	<table class="table table-condensed table-striped" style="font-size:12px">
	<tr>
	<th>账号</th><th>同步状态</th><th>请求时间</th></tr>
	<?php if (count($bestoffers)):foreach ($bestoffers as $bo):?>
	<tr>
	<td><?php echo $bo->selleruserid?></td>
	<td>
	<?php 
		switch ($bo->status){
			case '0':echo '未同步';break;
			case '1':echo '同步中';break;
			case '2':echo '已同步';break;
			default:echo '未同步';break;
		}
	?>
	</td>
	<td><?php echo date('Y-m-d H:i:s',$bo->updated)?></td></tr>
	<?php endforeach;endif;?>
	</table>
</div>