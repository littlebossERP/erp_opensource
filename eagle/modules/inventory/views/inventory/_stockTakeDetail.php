<?php
use yii\helpers\Html;
use eagle\modules\util\helpers\TranslateHelper;
use yii\helpers\Url;
$baseUrl = \Yii::$app->urlManager->baseUrl . '/';
$this->registerJsFile($baseUrl."js/project/inventory/stocktakc.js", ['depends' => ['yii\web\JqueryAsset']]);


?>
<style>
	#stocktake_detail_table >tbody >tr >td{
		padding: 4px;
		border: 1px solid rgb(202,202,202);
		vertical-align: middle;
		text-align: center;
	}
</style>
<table id= "stocktake_detail_table" cellspacing="0" cellpadding="0" width="900px" class="table table-hover" style="font-size:12px;">
	<tr class="list-firstTr">
		<td width="80px"><?=TranslateHelper::t('图片') ?></td>
		<td width="150px"><?=TranslateHelper::t('sku') ?></td>
		<td width="250px"><?=TranslateHelper::t('产品名称') ?></td>
		<td width="100px"><?=TranslateHelper::t('应有库存') ?></td>
		<td width="100px"><?=TranslateHelper::t('实际盘点数') ?></td>
		<td width="100px"><?=TranslateHelper::t('报损') ?>/<?=TranslateHelper::t('报溢') ?></td>
		<td width="100px"><?=TranslateHelper::t('盘点后货架位置') ?></td>
	</tr>
	<?php if(count($detail)>0){ ?>
    <?php foreach($detail as $record){?>
    <tr>
        <td ><img src="<?=$record['photo_primary'] ?>" style="width:80px ! important;height:80px ! important" /></td>
        <td ><?=$record['sku'] ?></td>
        <td ><?=$record['product_name'] ?></td>
        <td ><?=$record['qty_shall_be'] ?></td>
        <td ><?=$record['qty_actual'] ?></td>
        <td ><?=$record['qty_reported'] ?></td>
        <td ><?=$record['location_grid'] ?></td> 
	</tr>
         
    <?php } ?>
    <?php }else{ ?>
    	<tr><td colspan="7" style="text-align:center;"><?= TranslateHelper::t('无数据记录') ?></td></tr>
    <?php } ?>
</table>







