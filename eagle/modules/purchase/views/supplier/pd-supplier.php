<?php
use yii\helpers\Html;
use yii\grid\GridView;
use yii\helpers\Url;
use yii\jui\Dialog;
use yii\data\Sort;
use eagle\modules\util\helpers\TranslateHelper;

$baseUrl = \Yii::$app->urlManager->baseUrl . '/';
$this->registerCssFile ( $baseUrl . "css/purchase/purchase.css" );

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">

<div class="flex-row">
	<!-- 左侧列表内容区域 -->
	<?= $this->render('/purchase/_menu') ?>
	<!-- 右侧内容区域 -->
	<div class="content-wrapper" >
		<?=$this->render ( '_pd_suppliers', [
				'pdSuppliersListData' => $pdSuppliersListData,
				'sort' => $sort,
				'prodBrands'=>$prodBrands,
				'pdSuppliers'=>$pdSuppliers,
				'prodType'=>$prodType,
				//'supplierStatus' => $supplierStatus ,
				//'accountSettleMode'=>$accountSettleMode,
				//'countrys'=>$countrys,
				])?>
	</div> 
	
	<div style="font: 0px/0px sans-serif;clear: both;display: block"> </div> 
</div>
