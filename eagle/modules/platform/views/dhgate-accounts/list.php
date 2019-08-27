<?php 

use eagle\modules\util\helpers\TranslateHelper;

$this->registerJsFile(\Yii::getAlias('@web')."/js/project/platform/dhgateAccountList.js", ['depends' => ['yii\web\JqueryAsset']]);

// $this->registerJs("purchaseOrder.list.init();" , \yii\web\View::POS_READY);
?>
<button type="button" class="btn btn-info" style="margin: 20px 0;" onclick="dhgateAuthorizationUser()"><?= TranslateHelper::t('授权敦煌通账号')?></button>

<table id="dg" cellspacing="0" cellpadding="0" width="100%" class="table table-hover">
    <tr class="list-firstTr">
		<th><?=$sort->link('dhgate_uid',['label'=>TranslateHelper::t('编号')]) ?></th>
		<th><?=$sort->link('sellerloginid',['label'=>TranslateHelper::t('速卖通账号')]) ?></th>
		<th><?=$sort->link('create_time',['label'=>TranslateHelper::t('创建时间')]) ?></th>
		<th><?=$sort->link('is_active',['label'=>TranslateHelper::t('是否启用')]) ?></th>
		<th><?=$sort->link('refresh_token_timeout',['label'=>TranslateHelper::t('过期时间')]) ?></th>
		<th><?= TranslateHelper::t('操作')?></th>
	</tr>
	<?php 
	foreach( $dhgateUserList as $dhgateUser):?>
		<tr>
			<td ><?=$dhgateUser['dhgate_uid'] ?></td>
			<td ><?=$dhgateUser['sellerloginid'] ?></td>
			<td ><?=$dhgateUser['create_time'] ?></td>
			<td ><?=$dhgateUser['is_active'] ?></td>
			<td ><?=$dhgateUser['refresh_token_timeout'] ?></td>
			<td>
             	<a href="#" class="btn btn-xs btn-default" onclick="dhgateEditUser('<?=$dhgateUser['dhgate_uid'] ?>')"><?= TranslateHelper::t('修 改')?></a>
	        	<a href="#" class="btn btn-xs btn-default" onclick="dhgateUnbindUser('<?=$dhgateUser['dhgate_uid'] ?>' , '<?=$dhgateUser['sellerloginid'] ?>')"><?= TranslateHelper::t('解 绑')?></a>
	        	
	        </td>
		</tr>
	<?php endforeach;?>
</table>


<?php if(!empty($pagination)):?>
<div style="text-align: right;">
 	<div class="btn-group">
    	<?=\yii\widgets\LinkPager::widget(['pagination' => $pagination,'options'=>['class'=>'pagination']]);?>
	</div>
    <?= \eagle\widgets\SizePager::widget(['pagination'=>$pagination , 'pageSizeOptions'=>array( 5 , 20 , 50 , 100 , 200 ) , 'class'=>'btn-group dropup']);?>
</div>
<?php endif;?>