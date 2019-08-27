<?php
use yii\helpers\Html;
use yii\helpers\Url;
use common\helpers\Helper_Array;

$this->title='发送eBay评价';
$this->params['breadcrumbs'][] = $this->title;
?>
还没有好评的范本？赶快去<?=Html::button('创建',['class'=>'btn btn-info','onclick'=>"window.open(['/order/custom/feedback-template-list']);"])?>
<?php foreach ($orders as $order):?>
<div class="alert"  style="border:1px solid #d9effc;">
<table id="<?=$order->order_id ?>">
<tr>
	<td width="300px">订单号:<?=$order->order_id?></td>
	<td>买家:<b><?=$order->source_buyer_user_id?></b>&nbsp;&nbsp;&nbsp;&nbsp;卖家:<b><?=$order->selleruserid?></b></td>
</tr>
<tr>
	<td>
		评价类型<?=Html::dropDownList('feedbacktype_'.$order->order_id,'Positive',['Positive'=>'好评'/*,'Neutral'=>'中评','Negative'=>'差评'*/],['class'=>'input input-sm','id'=>'feedbacktype_'.$order->order_id])?>
	</td>
	<td>
		<?=Html::dropDownList('feedbackval_'.$order->order_id,array_rand(array_keys($feedbacks)),$feedbacks,['class'=>'input input-sm','id'=>'feedbackval_'.$order->order_id])?>
	</td>
</tr>
</table>
<div id="result_<?=$order->order_id?>" class="result">

</div>
</div>
<?php endforeach;?>
<?=Html::button('确定',['class'=>'btn btn-primary','onclick'=>'dofeedback()'])?>
<script>
function dofeedback(){
	if(<?=count($feedbacks)?>==0){
		bootbox.alert('请先建立好评范本');return false;
	}
	$('.result').each(function(){
		var obj=this;
		$(obj).text('处理中...');
		$(document).queue('ajaxRequests',function(){
			orderid = $(obj).prev().attr('id');
			$.post('<?=Url::to(['/order/order/ajax-feedback'])?>',{orderid:$(obj).prev().attr('id'),feedbacktype:$('#feedbacktype_'+orderid).val(),feedbackval:$('#feedbackval_'+orderid).val()},function(result){
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
