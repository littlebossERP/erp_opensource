<?php
use yii\helpers\Html;
use eagle\modules\util\helpers\TranslateHelper;
use yii\helpers\Url;

$this->registerJsFile(\Yii::getAlias('@web')."/js/project/inventory/stockchange.js", ['depends' => ['yii\web\JqueryAsset']]);


?>
<style>
	#stockchange_detail_table td , #stockchange_detail_table th{
		padding: 4px;
		border: 1px solid rgb(202,202,202);
	}
</style>
<table id= "stockchange_detail_table" cellspacing="0" cellpadding="0" style="width:100%;font-size:12px;" class="table table-hover">
	<tr class="list-firstTr">
		<th width="50px"><?=TranslateHelper::t('图片') ?></th>
		<th width="160px"><?=TranslateHelper::t('sku') ?></th>
		<th width="310px"><?=TranslateHelper::t('产品名称') ?></th>
		<th width="80px"><?=TranslateHelper::t('数量') ?></th>
	</tr>
	<?php if(count($data)>0): ?>
    <?php foreach($data as $index=>$aData):?>
    <tr>
        <td style="vertical-align: middle;"><img src="<?=$aData['photo_primary'] ?>" style="width:50px ! important;height:50px ! important" /></td>
        <td style="vertical-align: middle;"><?=$aData['sku'] ?></td>
        <td style="vertical-align: middle;"><?=$aData['prod_name'] ?></td>
        <td style="vertical-align: middle;"><?=$aData['qty'] ?></td>
	</tr>
         
    <?php endforeach;?>
    <?php else:?>
    	<tr><td colspan="4" style="text-align:center;"><?= TranslateHelper::t('无数据记录') ?></td></tr>
    <?php endif;?>
</table>







