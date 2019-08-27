<?php
use yii\helpers\Html;

?>

<div class="modal-header">
	<button type="button" class="close" data-dismiss="modal" aria-hidden="true">
    	&times;
    </button>
    <h4 class="modal-title" id="myModalLabel">
	议价
	</h4>
</div>
<div class="modal-body">
<label>买家议价价格</label><label><?=$bestoffer->bestoffer['Price']?></label><br>
<label>买家议价数量</label><label><?=$bestoffer->bestoffer['Quantity']?></label><br>
<form class="form-inline">
<label>我的议价</label><?=Html::textInput('myprice','0',['class'=>'form-control','onkeyup'=>"clearNoNum(this)"])?>
</form>
</div>
<div class="modal-footer">
	<button type="button" class="btn btn-default" data-dismiss="modal">关闭</button>
    <button type="button" class="btn btn-primary" onclick="doaction(<?=$bestoffer->bestofferid ?>,'counter',$('input[name=myprice]').val())"> 提交</button>
</div>
<script>
function clearNoNum(obj){
obj.value = obj.value.replace(/[^\d.]/g,"");  //清除“数字”和“.”以外的字符  
obj.value = obj.value.replace(/^\./g,"");  //验证第一个字符是数字而不是. 
obj.value = obj.value.replace(/\.{2,}/g,"."); //只保留第一个. 清除多余的.   
obj.value = obj.value.replace(".","$#$").replace(/\./g,"").replace("$#$",".");
}
</script>