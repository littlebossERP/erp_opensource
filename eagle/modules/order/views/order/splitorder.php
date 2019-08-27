<?php
use yii\helpers\Html;
$this->registerJs("initFormInput();" , \yii\web\View::POS_READY);
?>
<form class="form-inline" action="" method="post" name="a">
<br>
<font color="#8b8b8b" font-size="12px">订单号:<b><?=$order->order_id?></b></font>
<?=Html::input('hidden','orderid',$order->order_id)?>
<table class="table table-bordered" style="font-size:12px;border:0px;">
<tr>
	<th>旧订单</th>
	<th>新订单</th>
	<th>商品名</th>
	<th>SKU</th>
</tr>
<?php foreach ($order->items as $item):?>
<tr>
	<td><?=Html::radio("item[$item->order_item_id]",true,['value'=>'old'])?></td>
	<td><?=Html::radio("item[$item->order_item_id]",false,['value'=>'new'])?></td>
	<td><?=$item->product_name?></td>
	<td><?=$item->sku?></td>
</tr>
<?php endforeach;?>
</table>
<br>
<table  class="table table-bordered" style="font-size:12px;border:0px;background-color: #f4f9fc">
<tr>
	<td>
		<label>旧订单商品总值</label><?=Html::textInput('old_subtotal',$order->subtotal,['class'=>'form-control'])?><label><?=$order->currency?></label>
	</td>
	<td>
		<label>旧订单物流费用</label><?=Html::textInput('old_shipping_cost',$order->shipping_cost,['class'=>'form-control'])?><label><?=$order->currency?></label>
	</td>
	<td>
		<label>旧订单订单总价</label><?=Html::textInput('old_grand_total',$order->grand_total,['class'=>'form-control'])?><label><?=$order->currency?></label>
	</td>
</tr>
<tr>
	<td>
		<label>新订单商品总值</label><?=Html::textInput('new_subtotal','0.00',['class'=>'form-control'])?><label><?=$order->currency?></label>
	</td>
	<td>
		<label>新订单物流费用</label><?=Html::textInput('new_shipping_cost','0.00',['class'=>'form-control'])?><label><?=$order->currency?></label>
	</td>
	<td>
		<label>新订单订单总价</label><?=Html::textInput('new_grand_total','0.00',['class'=>'form-control'])?><label><?=$order->currency?></label>
	</td>
</tr>
</table>
<center>
	<?=Html::button('确定',['class'=>'btn btn-success','onclick'=>'checkandsubmit()'])?>&nbsp;&nbsp;&nbsp;&nbsp;
	<?=Html::button('取消',['class'=>'btn btn-default','onclick'=>'window.close();'])?>
	
</center>
</form>
<script>
function checkandsubmit(){
	olditems = $("input[value='old']:checked");
	newitems = $("input[value='new']:checked");
	count = $("input[value='old']");
	if(olditems.length==count.length || newitems.length==count.length){
		bootbox.alert('拆分订单必须商品分配到新订单');return false;
	}else{
		if (!$('form').formValidation('form_validate')){
			return false;
		}
		
		document.a.submit();
	}
}


function initFormInput(){
	$(':text').formValidation({validType:['trim','amount'],tipPosition:'right',required:true});
}

</script>