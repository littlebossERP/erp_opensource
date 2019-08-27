<?php 
use eagle\modules\util\helpers\TranslateHelper;
use eagle\helpers\HtmlHelper;

// $this->registerCssFile(\Yii::getAlias('@web')."/css/assistant/style.css");
$this->registerJsFile(\Yii::getAlias('@web')."/js/project/assistant.js", ['depends' => ['yii\web\JqueryAsset']]);

?>
<div class="tracking-index col2-layout">
	<?= $this->render('//layouts/menu_left_assistant') ?>

	<div class="content-wrapper">
		<div class="panel panel-primary">
			<div class="panel-heading">店铺启用设置</div>
		    <table class="table table-hover table-striped table-bordered">
		        <tr class="list-firstTr">
		            <th class="text-nowrap col-lg-2">
		            	<?= TranslateHelper::t('店铺') ?>
		            </th>
		            <th class="text-nowrap col-lg-4">
		            	<?= TranslateHelper::t('平台') ?>
		            </th>
		            <th class="text-nowrap col-lg-2">
		            	<?= TranslateHelper::t('状态') ?>
		            </th>
		            <th class="text-nowrap col-lg-1">
		            	<?= TranslateHelper::t('操作') ?>
		            </th>
			    </tr>
		        <?php foreach($shops as $shop):?>
		        <tr>
		            <td class="text-nowrap"><?= $shop->dp_shop_id ?></td>
		            <td class="text-nowrap"><?= $shop->platform ?></td>
		            <td><?= $shop->enable_status==2?'启用':'停用' ?></td>
		            <td><?= HtmlHelper::SwitchButton('enable_status',$shop,[2,1],['click-confirm'=>'您确定要启用该店铺的自动催款功能吗？','tracker-key'=>'AliCuikuan']) ?></td>
		        </tr>
		        <?php endforeach;?>
		    </table>

			<div class="panel-footer" id="pager-group">
		        <?= \eagle\widgets\SizePager::widget(['pagination'=>$pages, 'pageSizeOptions'=>$pages->pageSizeLimit, 'class'=>'btn-group dropup']);?>
		        <div class="btn-group" style="width: 49.6%;text-align: right;">
		        	<?=\yii\widgets\LinkPager::widget(['pagination' => $pages,'options'=>['class'=>'pagination']]);?>
		    	</div>
			</div>

		</div>
	</div>
</div>