<?php
use yii\helpers\Html;
use yii\helpers\Url;

$this->title=$title;
$this->params['breadcrumbs'][] = $this->title;
?>
<style>
.text-left{
	margin-bottom:20px;
}
</style>

<div class="jumbotron">
	<div class="container">
		<h2 class="text-left" style='font-size: 30px;'>当前你的店铺或者子账号设置，超出了小老板ERP免费版本的服务范围。</h2>
		
		<div class="text-left" style='font-size: 20px;'>
			<?php echo isset($error)?$error:'';?>
		</div>

		<div class="text-left" style='font-size: 15px;'>
			了解更多小老板ERP的免费套餐，请 <a target="_blank" href="/payment/user-account/erp-package-list" style="font-size: 15px;">点击详情</a>。<br>
			若要持续使用免费套餐，请点击右上方的设置，解除绑定部分的店铺，或者子账号。
		</div>

		<div class="text-left" style='font-size: 15px;'>
			对发展迅速的你，小老板ERP提供多种付费套餐，希望以契约精神，提供更可靠，强大，贴心的服务，更能满足你业务的不断成长，<a target="_blank" href="/payment/user-account/erp-package-list" style="font-size: 15px;">点击详情</a>。
		</div>
	</div>
</div>


