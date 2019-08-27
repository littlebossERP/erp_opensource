<?php
use yii\helpers\Html;
use yii\helpers\Url;

$this->title='eBay催款或取消订单';
$this->params['breadcrumbs'][] = $this->title;
?>

<?php foreach ($orders as $order):?>
<div class="alert"  style="border:1px solid #d9effc;">
<table id="<?=$order->order_id ?>">
<tr>
	<td width="300px">订单号:<?=$order->order_id?></td>
	<td>买家:<b><?=$order->source_buyer_user_id?></b>&nbsp;&nbsp;&nbsp;&nbsp;卖家:<b><?=$order->selleruserid?></b></td>
</tr>
<tr>
	<td>
		原因<?=Html::dropDownList('reason_'.$order->order_id,'',$reason,['class'=>'input input-sm','id'=>'reason_'.$order->order_id])?>
	</td>
	<td>
		详情
		<select name="explanation_<?=$order->order_id?>" class='input input-sm' id="explanation_<?=$order->order_id?>">
			<option only="BuyerHasNotPaid" value="BuyerHasNotResponded">BuyerHasNotResponded</option>
			<option only="BuyerHasNotPaid" value="BuyerNoLongerRegistered">BuyerNoLongerRegistered</option>
			<option only="BuyerHasNotPaid" value="BuyerNotClearedToPay">BuyerNotClearedToPay</option>
			<option only="BuyerHasNotPaid" value="BuyerRefusedToPay">BuyerRefusedToPay</option>
			<option only="TransactionMutuallyCanceled" value="BuyerPurchasingMistake">BuyerPurchasingMistake</option>
			<option only="TransactionMutuallyCanceled" value="BuyerReturnedItemForRefund">BuyerReturnedItemForRefund</option>
			<option only="TransactionMutuallyCanceled" value="UnableToResolveTerms">UnableToResolveTerms</option>
			<option only="All" value="BuyerNoLongerWantsItem">BuyerNoLongerWantsItem</option>
			<option only="All" value="PaymentMethodNotSupported">PaymentMethodNotSupported</option>
			<option only="All" value="ShipCountryNotSupported">ShipCountryNotSupported</option>
			<option only="All" value="ShippingAddressNotConfirmed">ShippingAddressNotConfirmed</option>
			<option only="All" value="OtherExplanation">OtherExplanation</option>
		</select>
	</td>
</tr>
</table>
<div id="result_<?=$order->order_id?>" class="result">

</div>
</div>
<?php endforeach;?>
<?=Html::button('确定',['class'=>'btn btn-primary','onclick'=>'send()'])?>
<script>
//$('option[only=TransactionMutuallyCanceled]').hide();
// $('select[name^=reason]').change(function(){
// 	nextTR=$(this).parents('td').next();
// 	nextTR.find('option').hide();
// 	nextTR.find('option[only=All]').show();
// 	nextTR.find('option[only='+$(this).val()+']').show();
// 	nextTR.find('select').val(nextTR.find('option:visible').eq(0).val())
// });
function send(){
	$('.result').each(function(){
		var obj=this;
		$(obj).text('处理中...');
		$(document).queue('ajaxRequests',function(){
			orderid = $(obj).prev().attr('id');
			$.post('<?=Url::to(['/order/order/ajax-dispute'])?>',{orderid:$(obj).prev().attr('id'),reason:$('#reason_'+orderid).val(),explanation:$('#explanation_'+orderid).val()},function(result){
				if(result=='success'){
					$(obj).attr('class','alert alert-success');
					$(obj).text('操作已成功');
				}else{
					$(obj).attr('class','alert alert-danger');
					$(obj).text('请求失败:'+result);
				}
			});
		});
	 	$(document).dequeue("ajaxRequests");	
	});
}
</script>