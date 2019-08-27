<?php
use yii\helpers\Html;
use yii\grid\GridView;
use yii\widgets\LinkPager;
use yii\helpers\Url;
use yii\jui\JuiAsset;
use yii\web\UrlManager;
use yii\jui\DatePicker;
use eagle\modules\order\models\OdOrder;
use eagle\modules\order\models\OdOrderItem;
use eagle\modules\carrier\apihelpers\CarrierApiHelper;
use eagle\modules\inventory\helpers\InventoryApiHelper;
use eagle\modules\util\helpers\TranslateHelper;
use eagle\modules\order\helpers\OrderTagHelper;
use eagle\modules\carrier\helpers\CarrierHelper;
use eagle\widgets\SizePager;
use eagle\models\SaasBonanzaUser;
use eagle\models\catalog\Product;
use eagle\modules\listing\models\BonanzaOfferList;
use Zend\Code\Scanner\Util;
use eagle\modules\util\helpers\StandardConst;
use eagle\modules\order\helpers\BonanzaOrderInterface;
use eagle\modules\tracking\helpers\TrackingApiHelper;
use eagle\modules\tracking\helpers\TrackingHelper;
use eagle\modules\order\helpers\BonanzaOrderHelper;


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
height: 35px;
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

	<table class="table" style="width:500px;">
		<tr><th colspan=5 style="text-align:left;"><h4>程序运行情况</h4></th></tr>
		<tr>
			<th style="width:100px;">job</th><th style="width:250px;">最后一次启动时间</th>
			<th style="width:100px;">启动次数</th><th style="width:100px;">结束次数</th>
			<th style="width:400px;">错误信息记录</th>
		</tr>
		<?php if(!empty($data['runtime'])):foreach ($data['runtime'] as $job=>$v):?>
		<tr>
			<td><?=$job ?></td>
			<td><?=((time()-strtotime($v['time']))>3600*2)?'<b style="color:red;">'.$v['time'].'</b>':$v['time'] ?></td>
			<td><?=$v['enter_times'] ?></td>
			<td><?=($v['end_times']==$v['enter_times'])?$v['end_times']:'<span style="color:red;">'.$v['end_times'].'</span>' ?></td>
			<td><?=$v['error_message'] ?></td>
		</tr>
		<?php endforeach;endif;?>
	</table>

	<table class="table">
		<tr><th colspan=11 style="text-align:left;"><h4>订单获取情况</h4></th></tr>
		<tr>
			<th>oms/原单  写入成功次数</th><th>oms单写入失败次数</th><th>原单 写入失败次数</th>
			<th>oms/原单 update次数</th><th>oms单 update失败次数</th><th>原单 update失败次数</th>
			<th>item写入成功次数</th><th>item写入失败次数</th><th>item更新成功次数</th><th>item更新失败次数</th>
			<th>日期</th>
		</tr>
		<?php if(!empty($data['orders'])):foreach ($data['orders'] as $date=>$v):
				if(!empty($v['count'])):?>
		<tr>
			<td>
				<?=($v['count']['oms_insert_success']==$v['count']['src_insert_success'])?$v['count']['oms_insert_success']:'<b style="color:red">'.$v['count']['oms_insert_success'].'</b>' ?>
				/<?=$v['count']['src_insert_success'] ?>
			</td>
			<td title="<?=$v['failed_happend_site']['oms_insert_failed_happend_site'] ?>"><b style="color:red;"><?=empty($v['count']['oms_insert_failed'])?'':$v['count']['oms_insert_failed'] ?></b></td>
			<td title="<?=$v['failed_happend_site']['src_insert_failed_happend_site'] ?>"><b style="color:red;"><?=empty($v['count']['src_insert_failed'])?'':$v['count']['src_insert_failed'] ?></b></td>
			
			<td>
				<?=($v['count']['oms_update_success']==$v['count']['src_update_success'])?$v['count']['oms_update_success']:'<b style="color:red">'.$v['count']['oms_update_success'].'</b>' ?>
				/<?=$v['count']['src_update_success'] ?>
			</td>
			<td title="<?=$v['failed_happend_site']['oms_update_failed_happend_site'] ?>"><b style="color:red;"><?=empty($v['count']['oms_update_failed'])?'':$v['count']['oms_update_failed'] ?></b></td>
			<td title="<?=$v['failed_happend_site']['src_update_failed_happend_site'] ?>"><b style="color:red;"><?=empty($v['count']['src_update_failed'])?'':$v['count']['src_update_failed'] ?></b></td>

			<td><?=$v['count']['src_detail_insert_success'] ?></td>
			<td title="<?=$v['failed_happend_site']['src_detail_insert_failed_happend_site'] ?>"><b style="color:red;"><?=empty($v['count']['src_detail_insert_failed'])?'':$v['count']['src_detail_insert_failed'] ?></b></td>
			<td><?=$v['count']['src_detail_update_success'] ?></td>
			<td title="<?=$v['failed_happend_site']['src_detail_update_failed_happend_site'] ?>"><b style="color:red;"><?=empty($v['count']['src_detail_update_failed'])?'':$v['count']['src_detail_update_failed'] ?></b></td>

			<td><?=$date ?></td>
		</tr>
		<?php endif;endforeach;endif;?>
		
	</table>
	
	<table class="table">
		<tr><th colspan=5 style="text-align:left;"><h4>error记录</h4></th></tr>
		<tr><th>error类型</th><th>出现次数</th><th>该类型的最后一次error信息</th><th>涉及账号</th><th>日期</th></tr>
		<?php if(!empty($data['errors'])):foreach ($data['errors'] as $type=>$v):?>
		<tr>
			<td><?=$type ?></td><td><?=$v['times'] ?></td>
			<td><?=$v['last_msg'] ?></td><td><?=$v['site_id'] ?></td><td><?=$v['time'] ?></td>
		</tr>
		<?php endforeach;endif;?>
	</table>
	
	<table class="table">
		<tr><th colspan=7 style="text-align:left;"><h4>用户统计</h4></th></tr>
		<tr>
			<th>用户数</th>
			<th>绑定的账号数</th>
			<th>启用数</th>
			<th>停用数</th>
			<th>token过期数</th>
			<th>未初始化</th>
			<th>日期</th>
		</tr>
		<?php if(!empty($data['user_count'])):foreach ($data['user_count'] as $date=>$v):?>
		<tr>
			<td><?=$v['all_uids'] ?></td><td><?=$v['all_accounts'] ?></td>
			<td><?=$v['active_accounts'] ?></td><td><?=$v['unActive_accounts'] ?></td>
			<td title="<?=$v['toekn_expired_site'] ?>"><?=$v['token_expired_accounts'] ?></td>
			<td title="<?=$v['un_initial_site'] ?>"><?=$v['un_initial_accounts'] ?></td>
			<td><?=$date ?></td>
		</tr>
		<?php endforeach;endif;?>
	</table>

</div>
<script>

			
</script>