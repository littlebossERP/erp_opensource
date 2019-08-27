<?php 
use yii\helpers\Url;
use eagle\modules\util\helpers\TranslateHelper;
use eagle\modules\app\apihelpers\AppApiHelper;
use eagle\modules\permission\apihelpers\UserApiHelper;

$bindingLink = AppApiHelper::getAutoLoginPlatformBindUrl();
?>
<style>
.tracking_bind_platform_tip >.btn-warning {
    margin-bottom: 5px;
}
</style>
<div class="tracking_bind_platform_tip" >

<i><span class="glyphicon glyphicon-plus" aria-hidden="true"></span></i>
<span style="margin-right: 10px;"><?=TranslateHelper::t('绑定平台账号，自动获取运单，状态实时更新')?></span>
	<?php 
		$isMainAccount = UserApiHelper::isMainAccount();
		//子账号没有权限授权，20170614_lrq
		if($isMainAccount){?>
			<a class="btn btn-warning" href="javascript:void(0)" onclick="platform.ebayAccountsList.menuAdd()" role="button">绑定eBay账号</a>
			<a class="btn btn-warning" href="javascript:void(0)" onclick="authorizationUser(1)" role="button">绑定速卖通账号</a>
			<a class="btn btn-warning" href="<?= $bindingLink?>" role="button"  target="_blank">绑定Wish账号</a>
			<a class="btn btn-warning" href="javascript:void(0)" onclick="dhgateAuthorizationUser()" role="button">绑定敦煌账号</a>
			<a class="btn btn-warning" href="javascript:void(0)" onclick="platform.LazadaAccountsList.addLazadaAccount()" role="button">绑定Lazada账号</a>
			<a class="btn btn-warning" href="javascript:void(0)" onclick="platform.LazadaAccountsList.addLinioAccount()" role="button">绑定Linio账号</a>
			<a class="btn btn-warning" href="javascript:void(0)" onclick="platform.LazadaAccountsList.addJumiaAccount()" role="button">绑定Jumia账号</a>
	<?php }?>
</div>