<?php
use yii\helpers\Html;
use yii\helpers\Url;

?>
<style>
	#stockallocation_detail_table >tbody >tr >td{
		padding: 4px;
		border: 1px solid rgb(202,202,202);
		vertical-align: middle;
		text-align: center;
	}
</style>
<table id= "stockallocation_detail_table" cellspacing="0" cellpadding="0" width="900px" class="table table-hover" style="font-size:12px;">
	<tr class="list-firstTr">
		<td width="80px">图片</td>
		<td width="150px">sku</td>
		<td width="250px">产品名称</td>
		<td width="100px">调拨数量</td>
		<td width="100px">入仓货架</td>
	</tr>
	<?php if(count($detail)>0){ ?>
    <?php foreach($detail as $record){?>
    <tr>
        <td ><img src="<?=$record['photo_primary'] ?>" style="width:80px ! important;height:80px ! important" /></td>
        <td ><?=$record['sku'] ?></td>
        <td ><?=$record['name'] ?></td>
        <td ><?=$record['qty'] ?></td>
        <td ><?=$record['location_grid'] ?></td>
	</tr>
         
    <?php } ?>
    <?php }else{ ?>
    	<tr><td colspan="7" style="text-align:center;"><?= TranslateHelper::t('无数据记录') ?></td></tr>
    <?php } ?>
</table>







