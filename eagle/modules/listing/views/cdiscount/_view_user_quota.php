<?php
use eagle\modules\util\helpers\TranslateHelper;
use yii\helpers\Url;
use eagle\modules\platform\apihelpers\CdiscountAccountsApiHelper;

?>
<style>
.table>th,.table>td{
	border:1px solid gray!impotent;
}
.view-quota .modal-dialog{
	width:800px;
	min-height:400px;
	max-height:80%;
	overflow: auto;
}
.view-quota b{
	font-weight: bold;
}
</style>
<div>
	<div class="alert alert-info" style="text-align: left;">
		<span>每个用户所绑定的Cdiscount账号，分别有<b><?=CdiscountAccountsApiHelper::$CdiscountTerminatorDefaultMaxHotSale?>个爆款监视</b> 和 <b><?=CdiscountAccountsApiHelper::$CdiscountTerminatorDefaultMaxFellow?>个关注</b>的<b>默认额度</b></span><br>
		<br>
		<span><b>爆款商品<?=$frequency_description['H'] ?>更新一次，关注的商品会<?=$frequency_description['F'] ?>更新一次，未关注的商品每<?=$frequency_description['N'] ?>更新一次，而忽略的商品，则不再自动更新。</b></span><br>
		<br>
	</div>
	<table class="table" style="width:100%">
		<tr>
			<th>店铺</th>
			<th>已爆款监视数</th>
			<th>已使用额外爆款额度</th>
			<th>已关注数</th>
			<th>已使用额外关注额度</th>
		</tr>
		<?php if(!empty($accounts)){
		foreach($accounts as $a){?>
			<tr>
				<td style="border:1px solid"><?=$a['username'] ?></td>
				<td style="border:1px solid"><?=empty($a['hotsale'])?0:$a['hotsale'] ?></td>
				<td style="border:1px solid"><?=empty($a['used_hotsale_quota'])?'':$a['used_hotsale_quota'] ?></td>
				<td style="border:1px solid"><?=empty($a['fellow'])?0:$a['fellow'] ?></td>
				<td style="border:1px solid"><?=empty($a['used_fellow_quota'])?'':$a['used_fellow_quota'] ?></td>
			</tr>
		<?php }
		}	
		?>
		
		<tr style="background-color: rgb(0,222,95);">
			<td>当前CD跟卖终结者VIP等级：<b>VIP<?=empty($userVipInfo['vip_rank'])?0:$userVipInfo['vip_rank'] ?></b></td>
			<td>额外爆款监视额度：<b><?=empty($userVipInfo['addi_hot_sale'])?0:$userVipInfo['addi_hot_sale'] ?><?=(!empty($userVipInfo['erp_addi_hot_sale']))?'+'.$userVipInfo['erp_addi_hot_sale'].'(erp)':''?></b></td>
			<td>剩余额外爆款监视额度：<b><?=empty($userVipInfo['remaining_hotsale_quota'])?0:$userVipInfo['remaining_hotsale_quota'] ?></b></td>
			<td>额外关注额度：<b><?=empty($userVipInfo['addi_follow'])?0:$userVipInfo['addi_follow'] ?><?=(!empty($userVipInfo['erp_addi_follow']))?'+'.$userVipInfo['erp_addi_follow'].'(erp)':''?></b></td>
			<td>剩余额外关注额度：<b><?=empty($userVipInfo['remaining_fellow_quota'])?0:$userVipInfo['remaining_fellow_quota'] ?></b></td>
		</tr>
	</table>
</div>