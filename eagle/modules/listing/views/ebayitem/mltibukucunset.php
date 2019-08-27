<?php
use yii\helpers\Html;
?>
<style>
.modal-body{
	font-size:10px;
}
</style>
<div class="modal-header">
	<button type="button" class="close" data-dismiss="modal" aria-hidden="true">
    	&times;
    </button>
    <h4 class="modal-title" id="myModalLabel">
	补库存设置
	</h4>
</div>
<form class="form-inline">
<div class="modal-body">
<?=Html::hiddenInput('itemid',$itemids,['id'=>'itemid'])?>
<p><?=Html::radio('bukucun','',['class'=>'bukucun','value'=>'0'])?>关闭</p>
<p><?=Html::radio('bukucun','',['class'=>'bukucun','value'=>'1'])?>卖多少补多少</p>
<p><?=Html::radio('bukucun','',['class'=>'bukucun','value'=>'2'])?>在线少于<?=Html::textInput('less',0,['size'=>4])?>件时,补货<?=Html::textInput('bu',0,['size'=>4])?>件</p>
<strong>*多属性商品的补库存需要有设置SKU，否则无法补库存</strong>
</div>
<div class="modal-footer">
    <button type="button" class="btn btn-primary" onclick="dobukucunset();"> 提交</button>
	<button type="button" class="btn btn-default" data-dismiss="modal">关闭</button>
</div>
</form>