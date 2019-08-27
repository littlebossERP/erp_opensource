<?php 
$this->registerJsFile(\Yii::getAlias('@web')."/js/project/listing/mubanedit_selectcategory.js", ['depends' => ['yii\web\JqueryAsset']]);
?>
<h3> 选择Ebay 分类 </h3>
<form action="" method="post" id="f">
	<input type="hidden" name="siteid" id='siteid' value="<?=$siteid?>"  />
	<input type="hidden" name="primaryCategory" value="0"  />
	<input type="hidden" name="primaryCategoryName" value=""  />
</form>
<div id="bar" colspan="4" align="left" style=" text-align:left;" class="span-15">
<div id="waitingforloading" style='float:right;color:#F00;size:18px;font-weight:bold; display:none'>
	等待载入!
</div>

</div>
