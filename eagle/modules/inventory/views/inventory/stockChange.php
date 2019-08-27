<?php

use yii\helpers\Html;
use yii\grid\GridView;
use eagle\modules\util\helpers\TranslateHelper;
use yii\helpers\Url;
use yii\jui\Dialog;
use yii\data\Sort;

use eagle\modules\catalog\helpers\ProductHelper;
use eagle\modules\permission\apihelpers\UserApiHelper;

/* @var $this yii\web\View */
/* @var $dataProvider yii\data\ActiveDataProvider */

$baseUrl = \Yii::$app->urlManager->baseUrl . '/';
//$this->registerJsFile($baseUrl."js/project/tracking/manual_import.js", ['depends' => ['yii\jui\JuiAsset','yii\bootstrap\BootstrapPluginAsset']]);
$this->registerJsFile($baseUrl."js/project/inventory/stockchange.js", ['depends' => ['yii\jui\JuiAsset','yii\bootstrap\BootstrapPluginAsset']]);
$this->registerJsFile($baseUrl."js/jquery.json-2.4.js", ['depends' => ['yii\jui\JuiAsset','yii\bootstrap\BootstrapPluginAsset']]);
$this->registerCssFile($baseUrl."css/inventory/inventory.css");
$this->registerJsFile($baseUrl."js/project/tracking/ajaxfileupload.js", ['depends' => ['yii\jui\JuiAsset','yii\bootstrap\BootstrapPluginAsset']]);

//$this->title = TranslateHelper::t('仓储管理');
//$this->params['breadcrumbs'][] = $this->title;
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd"> 
<style>

.td_space_toggle{
	height: auto;
	padding: 0!important;
}

</style>
<div class="flex-row">
	<!-- 左侧列表内容区域 -->
	<?= $this->render('/inventory/_menu', ['class_html' => '']) ?>
	<!-- 右侧内容区域 -->
    <?php 
    $actionId = yii::$app->controller->action->id;
    if ($actionId == 'stock_in' || $actionId == 'stock_out'){
	    //判断是否子账号    20170614_lrq
	    $isMainAccount = UserApiHelper::isMainAccount();
	    if(!$isMainAccount){
			//查询是否有仓库修改权限，没有则屏蔽   20170614_lrq
		    $ischeck = UserApiHelper::checkModulePermission('inventory_edit');
			if(!$ischeck){?>
				<div class="bind_tip alert alert-warning" style="margin-top: 5px;">
					<span>注意：没有仓库编辑权限！</span>
				</div>
    <?php return;}}}?>
	<!-- 右侧table内容区域 -->
	<div class="content-wrapper" >
	<?php 
		if ($actionId == 'stockchange'):
		//page:stockchange list
	?> 
	<?= $this->render('_stockchangelist',[
			'sort'=>$sort,
			'data'=>$data,
			'warehouse'=>$warehouse,
			'stockChangeType'=>$stockChangeType,
			'stockChangeReason'=>$stockChangeReason,
		])?>
	<?php elseif  ($actionId == 'stock_in'):?>
		<?php 
		//page:create stockin
			$stockInReason = array();
			foreach ($stockChangeReason as $k=>$v){
				if(substr($k,0,1)=='1'){
					$stockInReason[$k] = $v;
				}
			} 
		?>
		<?= $this->render('_stockIn',[
				'warehouse'=>$warehouse,
				'stockChangeReason'=>$stockInReason,
				'prodStatus'=>ProductHelper::getProductStatus(),
			])?>
		<?php elseif  ($actionId == 'stock_out'):?>
		<?php 
			//page:create stockout
			$stockOutReason = array();
			foreach ($stockChangeReason as $k=>$v){
				if(substr($k,0,1)=='2'){
					$stockOutReason[$k] = $v;
				}
			} 
		?>
		<?= $this->render('_stockOut',[
				'warehouse'=>$warehouse,
				'stockChangeReason'=>$stockOutReason,
				'prodStatus'=>ProductHelper::getProductStatus(),
			])?>
		</li>
		<?php endif; ?>
	
	</div> 
	<div style="font: 0px/0px sans-serif;clear: both;display: block"> </div> 
</div>




