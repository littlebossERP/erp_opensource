<?php

use yii\helpers\Html;
use yii\grid\GridView;
use eagle\modules\util\helpers\TranslateHelper;
use yii\helpers\Url;
use yii\jui\Dialog;
use yii\data\Sort;

$baseUrl = \Yii::$app->urlManager->baseUrl . '/';
$this->registerJsFile($baseUrl."js/jquery.json-2.4.js", ['depends' => ['yii\jui\JuiAsset','yii\bootstrap\BootstrapPluginAsset']]);
$this->registerCssFile($baseUrl."css/inventory/inventory.css");

//$this->title = TranslateHelper::t('仓储管理');
//$this->params['breadcrumbs'][] = $this->title;
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd"> 
<style>

</style>
<div class="inventory-index col2-layout">
    <?= $this->render('_menu',
            [
                'accountMaketplaceMap' => $accountMaketplaceMap,
                'merchant_id' => $merchant_id,
                'marketplace_id'=>$marketplace_id,
            ]) ?>
	<!-- 右侧table内容区域 -->
	<div class="content-wrapper" style="margin-left:250px;">
	
	<?= $this->render('list',
				['stockData' => $stockData ,
				 'sort'=>$sort,
    	         'merchant_id' => $merchant_id,
                 'marketplace_id'=>$marketplace_id,
	             'Amazon_url' => $Amazon_url,
	             'type' => $type,
				]) ?>
	</div> 
	<div style="font: 0px/0px sans-serif;clear: both;display: block"> </div> 
</div>