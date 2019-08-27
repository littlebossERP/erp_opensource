<?php

$this->registerJsFile(\Yii::getAlias('@web')."/js/project/report/inventory/listTagData.js", ['depends' => ['yii\web\JqueryAsset']]);
$this->registerJs("listTagData.init();" , \yii\web\View::POS_READY);

?>


<form >
<table cellspacing="0" cellpadding="0" width="100%" class="table table-hover">
<tr>
	<th nowrap><input type="checkbox" id="cbx-listTag-all" /></th><th nowrap>标签编号</th><th nowrap>标签名称</th><th nowrap>SKU数量</th>
</tr>
<?php 
	if(count($listTagArr) > 0){
		foreach ($listTagArr as $listTag){
?>
<tr>
	<td nowrap><input type="checkbox" class="class-tagsList" value="<?=$listTag['tag_id'].",".$listTag['tag_name']; ?>" /></td>
	<td nowrap><?=$listTag['tag_id']; ?></td><td nowrap><?=$listTag['tag_name']; ?></td><td nowrap><?=$listTag['count']; ?></td>
</tr>
<?php 
		}
	}
?>
</table>
</form>