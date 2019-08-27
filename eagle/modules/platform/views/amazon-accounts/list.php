<?php

use eagle\modules\util\helpers\TranslateHelper;
use yii\data\Sort;
$this->registerJsFile(\Yii::getAlias('@web').'/js/project/platform/amazonAccountsList.js');

$this->registerJs('platform.amazonAccountsList.initWidget();', \yii\web\View::POS_READY);

?>

<button type="button" id="new-amazon-account-btn" href="#" class="btn btn-info" style="margin-top: 15px;margin-bottom:15px;"><?php echo TranslateHelper::t('新增账号');?></button>

<!--  open the new/edit window for amazon account -->
<!-- Modal -->
<div id="new-or-edit-amazon-account-win" class="modal fade" tabindex="-1" data-backdrop="static"  >
    <div class="modal-dialog">
        <div class="modal-content"></div><!-- /.modal-content -->
    </div>
</div>
<!-- /.modal-dialog -->

<div class="account-list">
    <table cellspacing="0" cellpadding="0" width="100%" class="table table-hover">
		<tr class="list-firstTr">
			<?php if(!empty($sort)):?>
			<td><?=$sort->link('store_name',['label'=>TranslateHelper::t('店铺名称')]) ?></td>
			<td><?=$sort->link('merchant_id',['label'=>TranslateHelper::t('MerchantId(SellerId)')]) ?></td>
			<td><?=TranslateHelper::t('Marketplace列表 ') ?></td>
			<!-- <td><?=$sort->link('email',['label'=>TranslateHelper::t('email')]) ?></td> -->
			<td><?=$sort->link('create_time',['label'=>TranslateHelper::t('创建时间')]) ?></td>
			<td><?=$sort->link('is_active',['label'=>TranslateHelper::t('是否已启用 ')]) ?></td>
			
			<?php else:?>
			<td><?= TranslateHelper::t('店铺名称')?></td>
			<td><?= TranslateHelper::t('MerchantId(SellerId)')?></td>
			<td><?= TranslateHelper::t('Marketplace列表 ')?></td>
			<!-- <td><?=TranslateHelper::t('email') ?></td> -->
			<td><?= TranslateHelper::t('创建时间')?></td>
			<td><?= TranslateHelper::t('是否已启用')?></td>
			<?php endif;?>
			<td><?= TranslateHelper::t('操作')?></td>
		</tr>
        <?php foreach($amazonUserInfoList as $amazonUserInfo):?>
            <tr>
                <td ><?=$amazonUserInfo['store_name'] ?></td>
	            <td ><?=$amazonUserInfo['merchant_id'] ?></td>
	            <td ><?=$amazonUserInfo['country_list'] ?></td>
	            <!-- <td ><?//=$amazonUserInfo['email'] ?></td> -->
	            <td ><?=$amazonUserInfo['create_time'] ?></td>
	            <?php if ($amazonUserInfo['is_active'] == 1) :?>
	           	<td ><?=TranslateHelper::t('已启用') ?></td>
	            <?php else :?>
           	 	<td ><?=TranslateHelper::t('已停用') ?></td>
	            <?php endif;?>
	            <td >
	            	<button type="button" onclick="platform.amazonAccountsList.openViewWindow('<?=$amazonUserInfo['amazon_uid'] ?>')" class="btn btn-xs btn-default"><?=TranslateHelper::t('查看') ?></button>
	            	<button type="button" onclick="platform.amazonAccountsList.openEditWindow(this,'<?=$amazonUserInfo['amazon_uid'] ?>')" class="btn btn-xs btn-default"><?=TranslateHelper::t('编辑') ?></button>
	            </td>
	        </tr>
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
