<?php
use eagle\modules\util\helpers\TranslateHelper;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\jui\Dialog;
use yii\data\Sort;
use yii\jui\JuiAsset;

use eagle\modules\purchase\helpers\PurchaseSugHelper;

$strategiesInfo = PurchaseSugHelper::getStockingStrategyInfo();
$baseUrl = \Yii::$app->urlManager->baseUrl;
$this->registerJsFile($baseUrl."/js/project/purchase/suggestion/modifyStrategies.js", ['depends' => ['yii\web\JqueryAsset']]);

$this->registerJs("purchaseSug.modifyPage.init()", \yii\web\View::POS_READY);
$this->registerJs("$('input#strategy-".$strategiesInfo['stocking_strategy']."').prop('checked',true);", \yii\web\View::POS_READY);
?>

<style>
input[type="text"]{
	width: 50px;
}
.btn-set{
	float: right;
}
.form-control{
display: inline;
padding: 0px 12px;
height: 28px;
}
</style>
<!-- Modal -->
<div class="panel panel-default">
	<div class="panel-heading"><?= TranslateHelper::t('设置备货策略') ?></div>
	<div class="panel-body">
		<form id="stock-strategy-setup-form">
			<div id="stock-strategy-setup-div">
				<p>
				<input type="radio" name="stocking_strategy" value="0" id="strategy-0">
				<label for="strategy-0"><?=TranslateHelper::t('无备货策略')?></label>
				</p>
				
				<p>
				<label for="strategy-1"><?=TranslateHelper::t('策略1：')?></label><br>
				<input type="radio" name="stocking_strategy" value="1" id="strategy-1">
				<label for="strategy-1"><?=TranslateHelper::t('每个商品常备库存')?></label><input type="text" id="normal_stock" name="normal_stock" value="<?=$strategiesInfo['normal_stock'] ?>" class="form-control" style="width:100px"><label><?=TranslateHelper::t('个，库存盈余小于')?></label><input type="text" id="min_stock" name="min_stock" value="<?=$strategiesInfo['min_stock'] ?>" class="form-control" style="width:100px"><label><?=TranslateHelper::t('个，则建议补充到常备库存量。')?></label>
				<br />
				<span style="color:rgb(160, 160, 160);">
				<?=TranslateHelper::t('(例如：某产品 如果设置常备库存 50 个，盈余小于 30 个补充。')?><br />
				<?=TranslateHelper::t('那么，当库存量 小于 50 个，如果库存盈余大于30个，不会立刻要求备库存，直到库存到了 29 个，则要求备库采购21 （50-29 = 21）个，使得常备库存达到50个)')?>
				</span>
				</p>
								
				<p>
				<label for="strategy-2"><?=TranslateHelper::t('策略2：')?></label><br>
				<input type="radio" name="stocking_strategy" value="2" id="strategy-2">
				<label for="strategy-2"><?=TranslateHelper::t('若商品库存低于近')?></label><input type="text" id="count_sales_period" name="count_sales_period" value="<?=$strategiesInfo['count_sales_period'] ?>" class="form-control" ><label for="strategy-2"><?=TranslateHelper::t('日的总销量的')?></label><input type="text" id="min_total_sales_percentage" name="min_total_sales_percentage" value="<?=$strategiesInfo['min_total_sales_percentage'] ?>" class="form-control" ><label for="strategy-2">%<?=TranslateHelper::t('，则建议备库存数量为该时段总销量的')?></label><input type="text" id="stock_total_sales_percentage" name="stock_total_sales_percentage" value="<?=$strategiesInfo['stock_total_sales_percentage'] ?>" class="form-control" ><label for="strategy-2"> %</label>
				<br />
				<span style="color:rgb(160, 160, 160);;">
				<?=TranslateHelper::t('(例如：某产品 近14天总销量 100 个，若商品库存低于近14天总销量20%，则备货库存到数量为该时段销量的 60%，')?><br />
				<?=TranslateHelper::t('那么，当库存量 小于 20 个，则推荐再购买 40 个，使得总备库存达到 60 个，而库存从 60 销售出去变成20 个的这段时间，不会每天要求备库存，直到库存再度小于 20 个)')?>
				</span>
				</p>
		
				<div class="btn-set">
					<a href="#" class="btn btn-primary" id="save-modified-strategies"><?=TranslateHelper::t('保存')?></a>
					<a href="#" class="btn btn-default" id="refresh-modified-strategies"><?=TranslateHelper::t('刷新')?></a>
				</div>
			</div>		
		</form>
	</div>
</div>

<!-- Modal -->
<div class="modified-strategies-result"></div>
<!-- /.modal-dialog -->

