<?php
use yii\helpers\Html;
?>
<div class="modal-header">
	<button type="button" class="close" data-dismiss="modal" aria-hidden="true">
    	&times;
    </button>
    <h4 class="modal-title" id="myModalLabel">
	下架Item
	</h4>
</div>
<form class="form-inline">
<div class="modal-body">
<?=Html::hiddenInput('itemid',$itemid,['id'=>'itemid'])?>
<?php $select=[
	"Incorrect"=>'Incorrect',
	"LostOrBroken"=>'LostOrBroken',
	"NotAvailable"=>'NotAvailable',
	"OtherListingError"=>'OtherListingError',
	"SellToHighBidder"=>'SellToHighBidder',
	"Sold"=>'Sold',
];?>
请选择下架原因:<?=Html::dropDownList('reason','',$select,['id'=>'reason'])?><br>
</div>
<div class="modal-footer">
    <button type="button" class="btn btn-primary" onclick="ajaxclose($('#itemid').val(),$('#reason').val())"> 提交</button>
	<button type="button" class="btn btn-default" data-dismiss="modal">关闭</button>
</div>
</form>