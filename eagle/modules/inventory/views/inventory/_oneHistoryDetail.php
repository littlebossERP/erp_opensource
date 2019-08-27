<?php
use yii\helpers\Html;
use eagle\modules\util\helpers\TranslateHelper;
use yii\helpers\Url;

$this->registerJsFile(\Yii::getAlias('@web')."/js/project/inventory/inventory_list.js", ['depends' => ['yii\web\JqueryAsset']]);


?>
<style>
	#historyList_table >tbody >tr >td{
		padding: 4px;
		border: 1px solid rgb(202,202,202);
	}
</style>
<table id= "historyList_table" cellspacing="0" cellpadding="0" width="900px" class="table table-hover" style="font-size:12px;">
	<tr class="list-firstTr">
		<td width="150px"><?=$sort->link('create_time',['label'=>TranslateHelper::t('出入库时间')]) ?></td>
		<td width="80px"><?=$sort->link('change_type',['label'=>TranslateHelper::t('操作类型')]) ?></td>
		<td width="20%"><?=$sort->link('stock_change_id',['label'=>TranslateHelper::t('出入库单号')]) ?></td>
		<td width="10%"><?=$sort->link('reason',['label'=>TranslateHelper::t('出入库原因')]) ?></td>
		<td width="80px"><?=TranslateHelper::t('入库数量') ?></td>
		<td width="80px"><?=TranslateHelper::t('出库数量') ?></td>
		<td width="80px"><?=$sort->link('snapshot_qty',['label'=>TranslateHelper::t('操作后库存')]) ?></td>
		<td width="10%"><?=TranslateHelper::t('备注') ?></td>
		<td width="70px"><?=$sort->link('capture_user_id',['label'=>TranslateHelper::t('操作人')]) ?></td>
	</tr>
	<?php if(count($history['data'])>0): ?>
    <?php foreach($history['data'] as $index=>$aData):?>
    <tr <?=!is_int($index / 2)?"class='striped-row'":"" ?>>
        <td ><?=$aData['create_time'] ?></td>
        <td ><?php if(isset($stockChangeType[$aData['change_type']])) echo $stockChangeType[$aData['change_type']]; ?></td>
        <td ><?=$aData['stock_change_id'] ?></td>
        <td ><?php if(isset($stockChangeReason[$aData['reason']])) echo $stockChangeReason[$aData['reason']]; ?></td>
        <td ><?php if($aData['change_type']==1 || ($aData['change_type']==3 && $aData['qty']>0)) echo $aData['qty'];
  	     		?>
        </td>
        <td ><?php if($aData['change_type']==2 || ($aData['change_type']==3 && $aData['qty']<0)) echo $aData['qty'];
             	?>
        </td>
	    <td ><?php if(isset($aData['snapshot_qty'])) echo $aData['snapshot_qty'] ?></td>
        <td ><?=$aData['comment'] ?></td>
        <td ><?=$aData['capture_user_id'] ?></td>
	</tr>
    <?php endforeach;?>
    
    <?php else:?>
    	<tr><td colspan="9" style="text-align:center;"><?= TranslateHelper::t('无数据记录') ?></td></tr>
    <?php endif;?>
</table>







