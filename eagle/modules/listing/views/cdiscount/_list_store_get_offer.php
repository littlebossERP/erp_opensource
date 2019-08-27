<?php
use eagle\modules\util\helpers\TranslateHelper;
use yii\helpers\Url;

?>
<style>
.table>th,.table>td{
	border:1px solid gray!impotent;
}
.show-account-list .modal-dialog{
	min-width:700px;
	max-width:80%;
	min-height:400px;
	max-height:80%;
	overflow: auto;
}
</style>
<div>
	<div class="alert alert-warning" style="text-align: left;">
		<span >首次进入小老板Cdiscount商品管理页时，您需要手动对需要获取商品列表的平台进行一次获取操作，小老板系统后台会尽快将您该平台的商品列表同步到系统中。</span><br>
		<br>
		<span>每个账号手动获取列表的间隔不能短于2天。</span><br>
		<br>
		<span>首次获取成后，系统会根据您的关注设置进行不同频率的商品信息更新：爆款商品3小时更新一次，关注的商品会6小时更新一次，未关注的商品每6天更新一次，而忽略的商品，则不再自动更新一次。</span>
	</div>
	<table class="table">
		<tr>
			<th>店铺</th>
			<th>登录账号</th>
			<th>选中</th>
			<th>不可选原因</th>
			<th>上次获取时间</th>
		</tr>
		<?php if(!empty($accounts)){
		$could_submit_account=0;
		foreach($accounts as $a){?>
			<tr>
				<td style="border:1px solid"><?=$a->store_name ?></td>
				<td style="border:1px solid"><?=$a->username ?></td>
				<td style="border:1px solid"><?php if($a->is_active==0 || strtotime($a->fetcht_offer_list_time)>time()-3600*24*2){
					}else{
						$could_submit_account++; ?>
					<input type="checkbox" name="seller_id" value="<?=$a->username ?>"></td>
					<?php } ?>
				</td>
				<td style="border:1px solid">
					<?php  if($a->is_active==0) echo "未启用";
					elseif(strtotime($a->fetcht_offer_list_time)>time()-3600*24*2) echo "获取过完整商品列表间隔最少2天。<br>上次获取时间：".$a->fetcht_offer_list_time ;
					else echo ""; ?>
				</td>
				<td style="border:1px solid"><?=$a->fetcht_offer_list_time?></td>
			</tr>
		<?php }
		}	
		?>
	</table>
	<?php if($could_submit_account>0){?>
	<div style="text-align:center;">
		<a class="btn btn-success" href="javascript:void(0)" onclick="cdOffer.list.getFullOfferList()">提交后台</a>
	</div>
	<?php }else{?>
	<div style="text-align:center;">
		<span class="alert alert-success">所有启用账号最近2天都获取过所有商品列表，无需提交。</span>
	</div>
	<?php }?>
</div>