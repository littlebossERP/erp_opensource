<?php 
use eagle\modules\util\helpers\TranslateHelper;
?>
<style>
#quota_history_tb th,#quota_history_tb td{
	vertical-align: middle;
	text-align: center;
	padding:4px 4px;
	border:1px solid;
}
</style>
<div>
	<table id="quota_history_tb" class="table">
		<tr><th>剩余额度</th></tr>
		<tr><td><b style="font-size: 15px;font-weight: bolder;padding-right: 10px;"><?=empty($remaining_quota)?0:$remaining_quota?></b>
			<a href="/payment/user-account/package-list" target="_blank" class="btn btn-info" style="padding: 0px 5px;">
				<i class="iconfont icon-icon" style="font-size:15px;vertical-align: middle;"></i>
				前往购买
			</a>
			</td></tr>
		<tr><th>充值记录</th></tr>
	<?php foreach($historys as $his){ ?>
		<tr>
			<td><?=$his?></td>
		</tr>
	<?php } ?>
	</table>
	<div>
	
	</div>
	
</div>