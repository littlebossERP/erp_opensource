<?php

use yii\helpers\Html;
use yii\grid\GridView;
use eagle\modules\util\helpers\TranslateHelper;
use yii\helpers\Url;
use yii\jui\Dialog;
use yii\data\Sort;

$baseUrl = \Yii::$app->urlManager->baseUrl . '/';
// $this->registerJsFile($baseUrl."js/project/inventory/stockchange.js", ['depends' => ['yii\jui\JuiAsset','yii\bootstrap\BootstrapPluginAsset']]);
$this->registerJsFile($baseUrl."js/jquery.json-2.4.js", ['depends' => ['yii\jui\JuiAsset','yii\bootstrap\BootstrapPluginAsset']]);
$this->registerCssFile($baseUrl."css/report/reportPublic.css");
// $this->registerJsFile($baseUrl."js/project/tracking/ajaxfileupload.js", ['depends' => ['yii\jui\JuiAsset','yii\bootstrap\BootstrapPluginAsset']]);

$this->title = TranslateHelper::t('报表统计');
// $this->params['breadcrumbs'][] = $this->title;

?>

<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd"> 
<style>

.td_space_toggle{
	height: auto;
	padding: 0!important;
}

</style>
<div class="report-stockChange">
    <h1><?php echo Html::encode($this->title);?></h1>

	<ul class="list-unstyled list-inline">
		
		<li class="content_left"><?= $this->render('_menu') ?></li>
		<?php 
			$actionId = yii::$app->controller->action->id;
			if ($actionId == 'tag' ):
		?> 
		<li class="content_right"><?= $this->render('tag',['allTagData'=>$allTagData]) ?>
		</li>
		<?php elseif  ($actionId == 'tags'):?>
	
		<li class="content_right"><?= $this->render('tags') ?>
		</li>
		
		<?php elseif  ($actionId == 'get-brands-data'):?>
		
		<li class="content_right"><?= $this->render('brand',['brandsData'=>$brandsData]) ?>
		</li>
		
		<?php elseif  ($actionId == 'worth'):?>
		
		<li class="content_right"><?= $this->render('worth',['warehouseArr'=>$warehouseArr,'productInventory'=>$productInventory]) ?>
		</li>
		
		<?php elseif  ($actionId == 'product-tag'):?>
		
		<li class="content_right"><?= $this->render('../product/_tag',['shopArr' => $shopArr,'tagSaleData' => $tagSaleData]) ?>
		</li>
		
		<?php elseif  ($actionId == 'product-tags'):?>
		
		<li class="content_right"><?= $this->render('../product/_tags',['shopArr' => $shopArr]) ?>
		</li>
		
		<?php elseif  ($actionId == 'product-brand'):?>
		
		<li class="content_right"><?= $this->render('../product/_brand',['shopArr' => $shopArr,'brandsDataArr' => $brandsDataArr]) ?>
		</li>
		
		<?php elseif  ($actionId == 'product-worth'):?>
		
		<li class="content_right"><?= $this->render('../product/_worth',['shopArr' => $shopArr,'worthDataArr' => $worthDataArr]) ?>
		</li>
		
		<?php endif; ?>
		
		
		
		
		
	</ul>
</div>