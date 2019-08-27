<?php 

?>
<br>
<div>
	<div>
		拣货单号:<?=$delivery->deliveryid?>  &nbsp;&nbsp;&nbsp;&nbsp;打印员:<?=$delivery->creater?>
		<hr>
	</div>
	<table class="table table-condensed table-bordered" style="font-size:12px;">
		<tr>
			<th>序号</th>
			<th>图片</th>
			<th>SKU</th>
			<th>属性</th>
			<th>商品名称</th>
			<th>数量</th>
			<th>仓位</th>
			<th>库存</th>
		</tr>
		
	<?php $i=0;$count=0;if (count($arr)):foreach ($arr as $k=>$v):$i++;?>
		<tr>
			<td><?=$i?></td>
			<td><img src="<?=$v['img']?>" width="60px" height="60px"></td>
			<td><?=$k?></td>
			<td><?=$v['attr']?></td>
			<td><?=@$v['name']?></td>
			<td><?=$v['quantity']?><?php $count+=$v['quantity']?></td>
			<td><?=@$v['cangwei']?></td>
			<td><?=@$v['stock']?></td>
		</tr>
	<?php endforeach;endif;?>
	<tr>
		<td></td><td></td><td></td><td>总计</td><td><?=$count?></td><td></td><td></td>
	</tr>
	</table>
	<br><hr>
	<table class="table table-condensed table-bordered" style="font-size:12px;">
	<?php if (count($logs)):foreach ($logs as $log):?>
	<tr>
		<td>
			<?=$log->capture_user_name?>于<?=$log->update_time.$log->log_operation.':'.$log->comment?>
		</td>
	</tr>
	<?php endforeach;endif;?>
	</table>
</div>
<center><input type="button" value="打印" onclick="javascrīpt:window.print();"></center>