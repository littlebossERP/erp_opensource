<?php
use yii\helpers\Html;
use eagle\modules\util\helpers\TranslateHelper;
use yii\helpers\Url;

$this->registerJsFile(\Yii::getAlias('@web')."/js/project/inventory/fbawarehouse_list.js", ['depends' => ['yii\web\JqueryAsset']]);
$this->registerJs("Fbawarehouse.list.init();" , \yii\web\View::POS_READY);

$this->registerJs("$.initQtip();" , \yii\web\View::POS_READY);
?>

<style>
.div_inner_td{
	width: 100%;
}
.span_inner_td{
	float: left;
	padding: 6px 0px;
}
.div_account
{
	border: 1px solid #ccc;
	width:90%;
	padding:5px 0 5px 20px;
	float:left;
}
.div_account > span
{
	font-size: 15px; 
	line-height: 24px;
	margin-right:20px; 
	float:left;
}
.div_account a
{
    margin-left:0;
    margin-right:15px; 
    line-height: 25px; 
	padding:5px;
}
th
{
	text-align: center !important;
}
</style>

<form action="<?= Url::to(['/inventory/fba-warehouse/'.yii::$app->controller->action->id])?>" method="POST" id="fbawarehouse_list_params_form" style="width:100%;float:left;">
    <div style="width: 100%; float:left;">
        <div class="div-input-group" style="float: left;margin-left:20px; margin-bottom:10px;">
  			<SELECT name="type" value="" class="eagle-form-control" style="width:100px;margin:0px">
  			    <OPTION value="1" <?=(empty($type) or $type=="1")?"selected":"" ?>><?= TranslateHelper::t('有库存') ?></OPTION>
  				<OPTION value="2" <?=(!empty($type) && $type=="2")?"selected":"" ?>><?= TranslateHelper::t('全部') ?></OPTION>
				<OPTION value="3" <?=(!empty($type) && $type=="3")?"selected":"" ?>><?= TranslateHelper::t('无库存') ?></OPTION>
  			</SELECT>
    	
    		<input name="skus" id="search_sku" class="eagle-form-control" type="text" value="<?= empty($stockData['skus']) ? '' : $stockData['skus']?>" placeholder="<?= TranslateHelper::t('海外仓SKU，多个之间用英文逗号 , 隔开')?>" title="<?= TranslateHelper::t('根据输入的小老板订单号查询') ?>"
    		    style="width:250px;margin:0px 10px;height:28px;"/>
    		
    		<button id="profit_search" class="iv-btn btn-search btn-spacing-middle">搜索</button>
    	</div>
    </div>
  		
    <input name="merchant_id" type="text" style="display:none;" value="<?= $merchant_id?>"></input>
    <input name="marketplace_id" type="text" style="display:none;" value="<?= $marketplace_id?>"></input>
    <input name="per_page" type="text" style="display:none;" value="<?= empty($stockData['per_page']) ? '' : $stockData['per_page']?>"></input>
    <input name="page" type="text" style="display:none;" value="<?= empty($stockData['page']) ? '' : $stockData['page']?>"></input>
</form>
<!-- 功能按钮  -->
<div style="float:left; margin:10px 5px; width: 90%; ">
    <button type="button" class="iv-btn btn-important" style="border-style: none; margin-right: 20px;" onclick="Fbawarehouse.list.synchronizeSku()"><?= TranslateHelper::t('同步FBA库存') ?></button>

    <?php if(!empty($Amazon_url)) { ?>
    	<span style="font-size: 20px; color: red;">请先绑定Amazon平台，点击<a target="_blank" href=<?=$Amazon_url?>>这里</a>进入平台绑定！</span>
    <?php }?>
</div>
				
<!-- table -->
<div class="inventorylist" style="float:left;">
	<table cellspacing="0" cellpadding="0" style="float:left;" class="table table-hover">
		<tr class="list-firstTr">
			<th width="300px">SKU</th>
			<th width="300px">ASIN</th>
			<th width="150px">可用库存</th>
			<th width="150px">不可售库存</th>
		</tr>
		    <?php if(count($stockData)>0): ?>
		    <?php foreach($stockData['data'] as $index=>$stock):?>
		<tr <?=!is_int($index / 2)?"class='striped-row'":"" ?> stock_id="<?=$stock['id'] ?>">
			<td style="text-align:center;"><?=$stock['seller_sku'] ?></td>
			<td style="text-align:center;"><?=$stock['platform_code'] ?></td>
			<td style="text-align:center;"><?=$stock['usable_inventory'] ?></td>
			<td style="text-align:center;"><?=$stock['not_usable_inventory'] ?></td>
		</tr>
		<?php endforeach;?>
		<?php endif; ?>
	</table>
</div>
<div style="width: 100%; float: left;">
    <?php if(!empty($stockData['pagination'])){?>
    <div id="matching-pager-group">
        <?= \eagle\widgets\SizePager::widget(['pagination'=>$stockData['pagination'] , 'pageSizeOptions'=>array( 5 , 20 , 50 , 100 , 200 ) , 'class'=>'btn-group dropup']);?>
        <div class="btn-group" style="width: 49.6%;text-align: right;">
        	<?=\eagle\widgets\ELinkPager::widget(['pagination' => $stockData['pagination'],'options'=>['class'=>'pagination']]);?>
    	</div>
    </div>
    <?php }?>
</div>









