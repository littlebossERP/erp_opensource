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

$sql = "select uid,user_name from user_base where puid=0 ";
$command = \Yii::$app->db->createCommand( $sql );
$user_rows = $command->queryAll();
$user_info_data = [];
foreach ($user_rows as $row){
	$user_info_data[$row['uid']] = $row['user_name'];
}

$platform = $_REQUEST['platform'];
?>
<div class=" col2-layout">
	<div>
		<h4>用户订单数详情(30天)</h4>
		<table class="table" style="width:700px;">
			<tr>
				<th style="width:100px;">puid</th>
				<th style="width:100px;">登录账号</th>
				<th style="width:100px;">统计时间</th>
				<th style="width:150px;">平均每天(30天内)</th>
				<th style="width:150px;">oms平均每天操作数</th>
			</tr>
			<?php
			$i=1;
			foreach ($datas as $data):
			?>
			<tr <?=!is_int($i/ 2)?"class='striped-row'":"" ?> >
				<td><?=$data['puid'] ?></td>
				<td><?=empty($user_info_data[$data['puid']])?'':$user_info_data[$data['puid']] ?></td>
				<td><?=$data['update_date'] ?></td>
				<td><?=$data['orders'] ?></td>
				<td>
					<?php $oms_action_logs = empty($data['oms_action_logs'])?[]:$data['oms_action_logs'];
						if(!empty($oms_action_logs))
							$oms_action_logs = json_decode($oms_action_logs,true);
						if(isset($oms_action_logs['Oms-'.$platform]))
							echo $oms_action_logs['Oms-'.$platform];
						else
							echo "N/A";
					?>
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