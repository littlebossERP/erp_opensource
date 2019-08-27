<?php


use yii\widgets\LinkPager;
use eagle\widgets\SizePager;
?>
<style>
.table td,.table th{
	text-align: center;
}

table{
	font-size:12px;
}
.table>tbody td{
color:#637c99;
}
.table>tbody a{
color:#337ab7;
}
.table>thead>tr>th {
height: 35px;
vertical-align: middle;
}
.table>tbody>tr>td {
padding: 2px;
vertical-align: middle;
}
.input-group-btn > button{
  padding: 0px;
  height: 28px;
  width: 30px;
  border-radius: 0px;
  border: 1px solid #b9d6e8;
}

.div-input-group{
	  width: 150px;
  display: inline-block;
  vertical-align: middle;
	margin-top:1px;

}

.div-input-group>.input-group>input{
	height: 28px;
}

.table th,.table td{
	/*text-align:left;*/
}

</style>
<?php
$uid = \Yii::$app->user->id;
?>
<div class=" col2-layout">
	<div>
		<h4>用户订单数详情(30天)</h4>
		<table class="table" style="width:700px;">
			<tr>
				<th style="width:100px;">uid</th>
				<th style="width:100px;">登录账号</th>
				<th style="width:150px;">平均每天(30天内)</th>
				<th style="width:150px;">10天总数</th>
				<th style="width:150px;">30天总数</th>
				<th style="width:150px;">30天详情</th>
				<th style="display: none"></th>
			</tr>
			<?php
			$i=1;
			foreach ($datas as $uid=>$data):
			?>
			<tr <?=!is_int($i/ 2)?"class='striped-row'":"" ?> >
				<td><?=$uid ?></td>
				<td><?=isset($data['user_name'])?$data['user_name']:'' ?></td>
				<td><?=isset($data['avg_pre_day'])?$data['avg_pre_day']:'N/A' ?></td>
				<td><?=isset($data['last_10_days_total'])?$data['last_10_days_total']:'N/A' ?></td>
				<td><?=isset($data['last_30_days_total'])?$data['last_30_days_total']:'N/A' ?></td>
				<td><button type="button" onclick="show30Days(<?=$uid ?>)">查看</button></td>
				<td id="uid_<?=$uid ?>_30days_info" style="display: none">
					<table class="table" style="width:300px;">
						<tr>
							<th style="width:150px;">日期</th>
							<th style="width:100px;">订单数</th>
						</tr>
						<?php 
							if(isset($data['avg_pre_day'])) unset($data['avg_pre_day']);
							if(isset($data['user_name'])) unset($data['user_name']);
							if(isset($data['last_10_days_total'])) unset($data['last_10_days_total']);
							if(isset($data['last_30_days_total'])) unset($data['last_30_days_total']);
							$j=1;
							foreach ($data as $date=>$count):
							?>
						<tr  <?=!is_int($j/ 2)?"class='striped-row'":"" ?>>
							<td><?=$date ?></td><td><?=$count ?></td>
						</tr>
						<?php $j++;endforeach;?>
					</table>
				</td>
			</tr>
			
			<?php $i++;endforeach;?>
		</table>
		<?php if(!empty($pages)){?>
		<div class="btn-group" >
			<?=LinkPager::widget([
			    'pagination' => $pages,
			]);
			?>
		</div>
		<?=SizePager::widget(['pagination'=>$pages , 'pageSizeOptions'=>array( 50 , 100 , 200 ,500), 'class'=>'btn-group dropup'])?>
		<?php }?>
	</div>
	<div class="detail_in_30_days"></div>
	<h4>top 10 (平均)</h4>
	<table class="table" style="width:500px;">
		<tr>
			<th style="width:100px;">uid</th>
			<th style="width:100px;">登录账号</th>
			<th style="width:100px;">日均订单</th>
			<th style="width:150px;">最后统计日期</th></tr>
		<?php if(!empty($tops)):?>
		<?php foreach ($tops as $uid=>$info):?>
		<tr>
			<td><?=$uid ?></td>
			<td><?=$info['user_name'] ?></td>
			<td><?=$info['avg'] ?></td>
			<td><?=$info['date'] ?></td>
		</tr>
		<?php endforeach;?>
		<?php endif;?>
	</table>
</div>
<script>
	function show30Days(uid){
		var id = "uid_"+uid+"_30days_info";
		var data = $("#"+id).html();
		bootbox.dialog({
			title: "uid="+uid+" 的 30天内的订单数统计",
			className: "detail_in_30_days", 
			message: data,
			buttons:{
				Cancel: {  
					label: "返回",  
					className: "btn-default",  
					callback: function () {  
					}
				}, 
			}
		});	
	}	
</script>