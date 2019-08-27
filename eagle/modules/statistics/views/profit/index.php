<?php
use yii\helpers\Html;
use yii\grid\GridView;
use yii\helpers\Url;
use yii\jui\Dialog;
use yii\data\Sort;
use eagle\modules\util\helpers\TranslateHelper;

$baseUrl = \Yii::$app->urlManager->baseUrl . '/';
$this->registerJsFile ( $baseUrl . "js/jquery.json-2.4.js", [ 
		'depends' => [ 
				'yii\jui\JuiAsset',
				'yii\bootstrap\BootstrapPluginAsset' 
		] 
] );
$this->registerCssFile ( $baseUrl . "css/statistics/statistics.css" );

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<style>
</style>

<div class="flex-row">
	<!-- 左侧列表内容区域 -->
	<?= $this->render('/_menu') ?>
	<?php if(isset($ischeck) && $ischeck == 0){?>
    	<div style="float:left; margin: auto; margin:50px 0px 0px 200px; ">
        	<span style="font: bold 20px Arial;">亲，没有权限访问。 </span>
        </div>
        <?php }
        else{?>
    	<!-- 右侧table内容区域 -->
    	<div class="content-wrapper" style="width: 95%">
    		<?=$this->render ( 'profit_index', [
    				'profitData' => $profitData,
    				'sort' => $sort,
    		        'platformAccount' => $platformAccount,
    		        'stores'=>$stores ] )?>
    	</div> 
    	
    	<div style="font: 0px/0px sans-serif;clear: both;display: block"> </div> 
    <?php }?>
</div>



