<?php 

use yii\helpers\Html;
use yii\helpers\Url;
?>
<style>
.title{
	margin:10px 10px 10px 0px;
	font-weight:bold;
}
.choosemulti button{
	width:80px;
	margin:5px 15px;
}
.choosemulti>.act{
	text-align:center;
}
</style>
<div class="modal-header">
    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
    <h4 class="modal-title">批量修改</h4>
</div>
<div class="modal-body choosemulti">
	<form action="" method="post" name="doa" id="doa">
	<?=Html::hiddenInput('itemid',$itemid,['id'=>'itemid'])?>
	<?=Html::checkbox('a','',['id'=>"checkNegative"])?>全选
	<p class="title">基本设置</p>
	<?=Html::checkboxList('setitemvalues', '', array('itemtitle'=>'标题','subtitle'=>'副标题','category'=>'商品分类','location'=>'物品所在地','listingduration'=>'刊登天数'));?>
	<p class="title">物流设置</p>
	<?=Html::checkboxList('setitemvalues', '', array('shippingcost'=>'国内主运费','inshippingcost'=>'国际主运费','salestax'=>'运费加税','excludelocation'=>'屏蔽目的地','dispatchtime'=>'包裹处理时间'));?>
	<p class="title">收货与退货</p>
	<?=Html::checkboxList('setitemvalues', '', array('paymentmethods'=>'收款方式','autopay'=>'立即付款','paymentinstructions'=>'付款说明','return_policy'=>'退货政策'));?>
	<br>
	<div class="act">
		<button class="iv-btn btn-search" onclick="doact();">下一步</button>
		<button class="iv-btn btn-default" data-dismiss="modal">取消</button>
	</div>
	</form>
</div>
<script>
$('#checkNegative').click(function(){
	var checked = $(this).is(':checked');
    $('input[name^=setitemvalues]').each(function(){
        if(checked){
        	$(this).prop('checked','true');
        }else{
        	$(this).removeAttr('checked');
        }
    });
});

function doact(){
	$('#syncModal').modal('hide');
	document.doa.action="<?=Url::to(['/listing/ebayitem/mltichangealldata'])?>";
	document.doa.target="_blank";
	document.doa.submit();
}
</script>