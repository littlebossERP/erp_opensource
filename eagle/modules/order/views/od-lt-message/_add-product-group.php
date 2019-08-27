<?php 
use eagle\modules\util\helpers\TranslateHelper;
use eagle\helpers\HtmlHelper;
use yii\helpers\Html;
?>

<style>
.select-style{
	height:25px;
	width:200px;
}
.select-center{
	text-align:center;
}
.add-button-group{
	margin-top:15px;
}
.add-button-group input{
	margin-left:20px;
}
</style>
<div class="select-center">
    <form id="addToGroup">
        <input type="hidden" value='<?php print_r($ids)?>' name="product_ids" id="product_ids">
        <span>将商品添加到</span>
        <?=Html::dropDownList('group_name',@$_REQUEST['group_name'],$group_array,['onchange'=>"",'class'=>'iv-select select-style','id'=>'group_name','style'=>'','prompt'=>'选择商品组'])?>
    </form>
</div>
<div class="select-center add-button-group">
    <input class="btn btn-success" type="button" id="saveToGroup" value="保存"> 
    <input class="btn btn-success" type="button" id="windowDisplay" value="返回">
</div>
 