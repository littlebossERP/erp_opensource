<?php
use yii\helpers\Html;
use yii\helpers\Url;
use eagle\modules\platform\apihelpers\AmazonAccountsApiHelper;
use eagle\modules\amazoncs\models\CsSellerEmailAddress;
use eagle\modules\app\apihelpers\AppApiHelper;


$uid = \Yii::$app->user->id;

$showQuickStatistic = true;
?>

<style>


</style>
<div class="col2-layout col-xs-12">
	<?=$this->render('_leftmenu',[]);?>
	<div class="content-wrapper">
		<?php
		$bindingAccounts = AmazonAccountsApiHelper::listActiveAccounts($uid);
		if(empty($bindingAccounts)){
	//		未有绑定有效的amazon账号
			$showQuickStatistic= false;
			$platformBindUrl = list($platformBindUrl,$label)=AppApiHelper::getPlatformMenuData();
		?>
			<div class='alert alert-warning' role="alert">
				您还未绑定有效的Amazon账号，需要先绑定账号才能使用这和功能<a class="btn btn-info" target='_blank' href="http://auth.littleboss.com<?=$platformBindUrl?>">前往绑定</a>
			</div>
		<?php } ?>
		<?php 
			$bindingEmails = CsSellerEmailAddress::find()->where(['status'=>'active'])->all();
			if(empty($bindingEmails)){
	//		未有绑定有效的邮箱
			$showQuickStatistic= false;
		?>
			<div class='alert alert-warning' role="alert">
				您还未绑定有效的邮箱地址，需要先绑定邮箱地址才能使用这和功能<a class="btn btn-info" href="/amazoncs/amazoncs/email-list">前往绑定</a>
			</div>
		
		<?php } ?>
		<?php 
			if($showQuickStatistic){
		?>
			
		<?php } ?>
	</div>
</div>

<script> 

</script>