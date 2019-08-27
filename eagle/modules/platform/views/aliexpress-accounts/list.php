<?php 

use eagle\modules\util\helpers\TranslateHelper;

$this->registerJsFile(\Yii::getAlias('@web')."/js/project/platform/aliexpressAccountList.js", ['depends' => ['yii\web\JqueryAsset']]);

// $this->registerJs("purchaseOrder.list.init();" , \yii\web\View::POS_READY);
?>
<button type="button" class="btn btn-info" style="margin: 20px 0;" onclick="authorizationUser()"><?= TranslateHelper::t('授权速卖通账号')?></button>

<table id="dg" cellspacing="0" cellpadding="0" width="100%" class="table table-hover">
    <tr class="list-firstTr">
    	<th></th>
		<th><?=$sort->link('aliexpress_uid',['label'=>TranslateHelper::t('编号')]) ?></th>
		<th><?=$sort->link('sellerloginid',['label'=>TranslateHelper::t('速卖通账号')]) ?></th>
		<th><?=$sort->link('create_time',['label'=>TranslateHelper::t('创建时间')]) ?></th>
		<th><?=$sort->link('is_active',['label'=>TranslateHelper::t('是否启用')]) ?></th>
		<th><?=$sort->link('refresh_token_timeout',['label'=>TranslateHelper::t('过期时间')]) ?></th>
		<th><?= TranslateHelper::t('操作')?></th>
	</tr>
	<?php 
	$rowIndex = 1;
	foreach( $aliexpressUserList as $aliexpressUser):?>
		<tr>
			<td><?=$rowIndex ?></td>
			<td ><?=$aliexpressUser['aliexpress_uid'] ?></td>
			<td ><?=$aliexpressUser['sellerloginid'] ?></td>
			<td ><?=$aliexpressUser['create_time'] ?></td>
			<td ><?=$aliexpressUser['is_active'] ?></td>
			<td ><?=$aliexpressUser['refresh_token_timeout'] ?></td>
			<td>
             	<a href="#" class="btn btn-xs btn-default" onclick="editUser('<?=$aliexpressUser['aliexpress_uid'] ?>')"><?= TranslateHelper::t('修 改')?></a>
	        </td>
		</tr>
	<?php $rowIndex++;?>
	<?php endforeach;?>
</table>


<?php if(!empty($pagination)):?>
<div>
    <?= \eagle\widgets\SizePager::widget(['pagination'=>$pagination , 'pageSizeOptions'=>array( 5 , 20 , 50 , 100 , 200 ) , 'class'=>'btn-group dropup']);?>
    <div class="btn-group" style="width: 49.6%;text-align: right;">
    	<?=\yii\widgets\LinkPager::widget(['pagination' => $pagination,'options'=>['class'=>'pagination']]);?>
	</div>
</div>
<?php endif;?>