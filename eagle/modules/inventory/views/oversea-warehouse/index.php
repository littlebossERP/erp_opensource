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
                'OverseaWarehouse'=>$OverseaWarehouse,
                'warehouse_id'=>$warehouse_id,
                'carrier_not_use' => $carrier_not_use,
            ]) ?>
	<!-- 右侧table内容区域 -->
	<div class="content-wrapper" style="margin-left:250px;">
	
	<?= $this->render('list',
				['stockData' => $stockData ,
	             'account' => $account,
				 'sort'=>$sort,
    	        'carrier_code' => $carrier_code,
    	        'third_party_code' => $third_party_code,
    	        'accountid' => $accountid,
	            'warehouse_id' => $warehouse_id,
				]) ?>
	</div> 
	<div style="font: 0px/0px sans-serif;clear: both;display: block"> </div> 
</div>