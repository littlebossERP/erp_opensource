<?php
use yii\helpers\Html;
use yii\helpers\Url;
use common\helpers\Helper_Array;

$this->title='Aliexpress批量评价';
$this->params['breadcrumbs'][] = $this->title;
?>
<div>
	<span style="font-size: 20px;color: red;">Aliexpress评价暂不支持中文及中文符号评价！</span>
	<div>
	<?= Html::radioList('batch_type','5',['5'=>'5星','4'=>'4星','3'=>'3星','2'=>'2星' ,'1'=>'1星']) ?>
	<?= Html::textarea('batch_message','Thank you! I wish you a happy shopping!',array('rows'=>5,'cols'=>60))?>
	
	</div>
	<?= Html::button('批量设置',['class'=>"btn btn-primary btn-xs",'id'=>'btn-batch-set','onclick'=>'batchSetValue()'])?>
</div>
<?php foreach ($orders as $order):?>
<div class="alert"  style="border:1px solid #d9effc;">
<table id="<?=$order->order_id ?>">
<tr>
	<td width="300px">订单号:<?=$order->order_id?></td>
	<td>买家:<b><?=$order->source_buyer_user_id?></b>&nbsp;&nbsp;&nbsp;&nbsp;卖家:<b><?=$order->selleruserid?></b></td>
</tr>
<tr>
	<td>
		评价类型:
		<?= Html::radioList('feedbacktype_'.$order->order_id,'5',['5'=>'5星','4'=>'4星','3'=>'3星','2'=>'2星' ,'1'=>'1星']) ?>
		<!-- 
		<label><input type="radio" id="feedbacktype_<?=$order->order_id ?>" value="5" checked="checked">5星</label>
		<label><input type="radio" id="feedbacktype_<?=$order->order_id ?>" value="4" >4星</label>
		<label><input type="radio" id="feedbacktype_<?=$order->order_id ?>" value="3" >3星</label>
		<label><input type="radio" id="feedbacktype_<?=$order->order_id ?>" value="2" >2星</label>
		<label><input type="radio" id="feedbacktype_<?=$order->order_id ?>" value="1" >1星</label>
		 -->
	</td>
	<td>
		<textarea id="feedbackval_<?=$order->order_id ?>" style="width:450px;height:50px;" onBlur="if(this.value=='') this.value='Thank you! I wish you a happy shopping';" onFocus="this.value='';" >Thank you ! I wish you a happy shopping!</textarea>
	</td>
</tr>
</table>
<div id="result_<?=$order->order_id?>" class="result">

</div>
</div>
<?php endforeach;?>
<?=Html::button('确定',['class'=>'btn btn-primary','onclick'=>'dofeedback()'])?>
<?=Html::button('返回',['class'=>'btn btn-default','onclick'=>'$(".order_info ").modal("hide");'])?>
<script>
//	$(document).ready(function() {
//		$("textarea").focus(function () {
//			$("textarea").text("");
//		});
//	});

function batchSetValue(){
	$('[name*=feedbacktype][value='+$("input[name='batch_type']:checked").val()+']').prop('checked',true);
	$('[id*=feedbackval]').val($('textarea[name=batch_message]').val());
}

function dofeedback(){
	$('.result').each(function(){
		var obj=this;
		$(obj).text('处理中...');
		$(document).queue('ajaxRequests',function(){
			
			orderid = $(obj).prev().attr('id');
			$.post('<?=Url::to(['/order/aliexpressorder/ajax-feedback'])?>',{orderid:$(obj).prev().attr('id'),feedbacktype:$('[name=feedbacktype_'+orderid+']:checked').val(),feedbackval:$('#feedbackval_'+orderid).val()},function(result){
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
