<?php
use yii\helpers\Html;
use yii\helpers\Url;
use eagle\modules\util\helpers\TranslateHelper;
use eagle\modules\util\helpers\ConfigHelper;
use eagle\modules\platform\apihelpers\CdiscountAccountsApiHelper;
use eagle\modules\util\helpers\RedisHelper;

$this->registerJsFile(\Yii::getAlias('@web')."/js/project/order/cdiscountOrder/offerList.js", ['depends' => ['yii\web\JqueryAsset']]);
$this->registerJs("cdOffer.TerminatorMailSetting.init();", \yii\web\View::POS_READY);

$uid = \Yii::$app->user->id;

$AccountVipInfo=CdiscountAccountsApiHelper::getCdAccountVipInfo($uid);
$vip_rank=0;
$is_vip = false;
foreach ($AccountVipInfo as $row){
	if(!empty($row['vip_rank']))
	if((int)$row['vip_rank']>$vip_rank)
		$vip_rank = (int)$row['vip_rank'];
}
if($vip_rank>0) $is_vip = true;

$user_valid_mailAddress = RedisHelper::RedisGet('user_valid_mail_address', 'cd_terminator_uid_'.$uid);

if(empty($user_valid_mailAddress) || !preg_match('/^[A-Za-z0-9]+([-_.][A-Za-z0-9]+)*@([A-Za-z0-9]+[-.])+[A-Za-z0-9]{2,5}$/', $user_valid_mailAddress)){
	$this->registerJs('$("#setting input[name!=\'user_valid_mail_address\']").attr("disabled",true);$("#setting button[type=\'submit\']").attr("disabled",true);$("#setting tr[data-row!=\'valid_mail_address\']").css("background","#a3a1a1");', \yii\web\View::POS_READY);
}
$can_send_mail = ConfigHelper::getConfig("Listing/send_cd_terminator_mail","NO_CACHE");
$can_send_announce = ConfigHelper::getConfig("Listing/send_cd_terminator_announce","NO_CACHE");
$send_announce_frequency = ConfigHelper::getConfig("Listing/send_cd_terminator_announce_frequency","NO_CACHE");
?>
<style>
#setting tr{
	border:1px solid ;
}
#setting th{
	width:300px;
}

.left_menu.menu_v2 .iconfont.icon-jinggao{
	float:none;
	color:red;
}
.left_menu.menu_v2 .iconfont.icon-jinggao+span{
	margin-right:0px;
}
</style>
<div class="tracking-index col2-layout">
	<?=$this->render('_leftmenu',['counter'=>[]]);?>
	<div>
	    <p class="alert alert-danger">暂时不支持邮件通知，需要对接邮件服务</p>
		<?php if(empty($user_valid_mailAddress)){?>
		<p class="alert alert-danger">需要设置有效邮箱后才能发送消息提示</p>
		<?php }?>
		<?php if(!empty($err_message)){ ?>
		<div>
		<?php foreach ($err_message as $e_msg): ?>
		<p class="alert alert-danger"><?=$e_msg ?></p>
		<?php endforeach;?>
		</div>
		<?php }else{ if(!empty($set)):?>
		<div class="alert alert-success">设置成功!</div>
		<?php endif; } ?>
		<form action="/listing/cdiscount/setting" method="post" id="setting">
			<table class="table" cellspacing="10">
				<tr data-row="valid_mail_address">
					<th>能够接收邮件的有效邮箱</th>
					<td>
						<input type="text" name="user_valid_mail_address"  value="<?=empty($user_valid_mailAddress)?'':$user_valid_mailAddress ?>" >
						<span style="color:red">* </span><span id="mail-address-required" style="color:red"> 必须为正确有效的邮箱地址</span>
					</td>
				</tr>
				<tr data-row="can_send_mail">
					<th>是否接收每天统计提示邮件</th>
					<td>
						<label for="send_Y">是</label><input type="radio" name="can_send_mail" id="send_Y" value="Y" <?=(!empty($can_send_mail) && $can_send_mail=='Y')?'checked':''?> ><span style="margin:0px 5px;"></span>
						<label for="send_N">否</label><input type="radio" name="can_send_mail" id="send_N" value="N" <?=(empty($can_send_mail) || $can_send_mail=='N')?'checked':''?>><span style="margin:0px 5px;"></span>
					</td>
				</tr>
				
				<tr><td colspan="2">以下设置仅CD跟卖终结者VIP可见</td></tr>
				<?php if($is_vip){ ?>
				<tr data-row="can_send_announce">
					<th>是否接收定时BestSeller被抢提醒</th>
					<td>
						<label for="announce_Y">是</label><input type="radio" name="can_send_announce" id="announce_Y" value="Y" <?=(!empty($can_send_announce) && $can_send_announce=='Y')?'checked':''?> ><span style="margin:0px 5px;"></span>
						<label for="announce_N">否</label><input type="radio" name="can_send_announce" id="announce_N" value="N" <?=(empty($can_send_announce) || $can_send_announce=='N')?'checked':''?>><span style="margin:0px 5px;"></span>
					</td>
				</tr>
				<tr data-row="send_announce_frequency">
					<th>定时BestSeller被抢提醒频率</th>
					<td>
					<?php if($vip_rank>=3){?>
						<label for="frequency_05">30分钟</label><input type="radio" name="send_announce_frequency" id="frequency_05" value="0.5h" <?=(!empty($send_announce_frequency) && $send_announce_frequency=='0.5h')?'checked':''?> ><span style="margin:0px 5px;"></span>
					<?php } ?>
						<label for="frequency_1">1小时</label><input type="radio" name="send_announce_frequency" id="frequency_1" value="1h" <?=(!empty($send_announce_frequency) && $send_announce_frequency=='1h')?'checked':''?>><span style="margin:0px 5px;"></span>
						<label for="frequency_3">3小时</label><input type="radio" name="send_announce_frequency" id="frequency_3" value="3h" <?=(empty($send_announce_frequency) || $send_announce_frequency=='3h')?'checked':''?>><span style="margin:0px 5px;"></span>
						<label for="frequency_6">6小时</label><input type="radio" name="send_announce_frequency" id="frequency_6" value="6h" <?=(!empty($send_announce_frequency) && $send_announce_frequency=='6h')?'checked':''?>><span style="margin:0px 5px;"></span>
					</td>
				</tr>
				<?php } ?>
				
			</table>
			<button type="submit" class="btn btn-primary" >设置</button>
		</form>
	</div>
</div>