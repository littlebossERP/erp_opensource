<?php

use eagle\modules\util\helpers\TranslateHelper;

$this->registerJsFile(\Yii::getAlias('@web')."/js/project/platform/LazadaAccountsList.js", ['depends' => ['yii\web\JqueryAsset']]);
$this->registerJs("platform.LazadaAccountsList.initWidget();" , \yii\web\View::POS_READY);
$this->title = TranslateHelper::t(ucfirst($platform)."账号列表");
?>
<style>
.lazadaAccountInfo .modal-body{
	max-height: 400px;
	overflow-y: auto;
}
</style>


<!--  open the new/edit window for Lazada account -->
<a id="new-lazada-account-btn" href="#" class="btn btn-info" style="margin-top: 15px;margin-bottom:15px;" onclick="platform.LazadaAccountsList.add<?=ucfirst($platform) ?>Account()"><?= TranslateHelper::t('新增账号')?></a>

<div class="account-list bs-callout bs-callout-warning">
    <table cellspacing="0" cellpadding="0" width="100%" class="table table-hover">
		<tr class="list-firstTr">
			<td width="5%"></td>
			<td width="15%"><?=$sort->link('platform_userid',['label'=>TranslateHelper::t('账号邮箱')]) ?></td>
			<td width="20%"><?=$sort->link('token',['label'=>TranslateHelper::t('API key')]) ?></td>
			<td width="10%"><?=$sort->link('lazada_site',['label'=>TranslateHelper::t('卖家站点')]) ?></td>
			<td width="10%"><?=$sort->link('status',['label'=>TranslateHelper::t('是否已启用')]) ?></td>
			<td width="10%"><?=$sort->link('create_time',['label'=>TranslateHelper::t('创建时间')]) ?></td>
			<td width="10%"><?=$sort->link('update_time',['label'=>TranslateHelper::t('修改时间')]) ?></td>
			<td ><?= TranslateHelper::t('操作')?></td>
		</tr>
        <?php 
        $rowIndex = 1;
        foreach($userList as $user):?>
            <tr>
	            <td><?=$rowIndex ?></td>
                <td><?=$user['platform_userid'] ?></td>
	            <td style="word-break:break-all;"><?=$user['token'] ?></td>
	            <td><?=$user['lazada_site'] ?></td>
	            <td><?=$user['status'] ?></td>
	            <td><?=$user['create_time'] ?></td>
	            <td><?=$user['update_time'] ?></td>
	            <td>
					<a class="btn btn-default btn-xs" href="#" onclick="platform.LazadaAccountsList.openViewWindow('<?=$user['lazada_uid'] ?>','<?=$platform ?>')"><?=TranslateHelper::t('查看') ?></a> 
					<a class="btn btn-default btn-xs" href="#" onclick="platform.LazadaAccountsList.openEditWindow('<?=$user['lazada_uid'] ?>','<?=$platform ?>')"><?=TranslateHelper::t('编辑') ?></a>
					<a class="btn btn-default btn-xs" href="#" onclick="platform.LazadaAccountsList.unbindLazadaAccount('<?=$user['lazada_uid'] ?>','<?=$platform ?>')"><?=TranslateHelper::t('解绑') ?></a>
	            </td>
	        </tr>
	       <?php $rowIndex++;?>
        <?php endforeach;?>
    </table>
</div>


<?php if(!empty($pagination)):?>
<div>
    <?= \eagle\widgets\SizePager::widget(['pagination'=>$pagination , 'pageSizeOptions'=>array( 5 , 20 , 50 , 100 , 200 ) , 'class'=>'btn-group dropup']);?>
    <div class="btn-group" style="width: 49.6%;text-align: right;">
    	<?=\yii\widgets\LinkPager::widget(['pagination' => $pagination,'options'=>['class'=>'pagination']]);?>
	</div>
</div>
<?php endif;?>