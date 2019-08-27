<?php
use yii\helpers\Html;
use yii\helpers\Url;

?>

<div class="modal-header">
	<button type="button" class="close" data-dismiss="modal" aria-hidden="true">
    	&times;
    </button>
    <h4 class="modal-title" id="myModalLabel">
	发送站内信
	</h4>
</div>
<div class="modal-body">
收件人:<label><?=$order->source_buyer_user_id ?></label><br>
类型:<?=Html::dropDownList('type','',['General'=>'一般','MultipleItemShipping'=>'多商品订单','Payment'=>'支付','Shipping'=>'物流'],['id'=>'type','style'=>'margin:2px'])?><br>
标题:<?=Html::textInput('title','',['id'=>'title','size'=>'50','style'=>'margin:2px'])?><br>
内容:<?=Html::textarea('content','',['id'=>'content','rows'=>'10','cols'=>'50','style'=>'margin:2px'])?><br>
发送到卖家邮箱:<?=Html::checkbox('mail',true,['value'=>true,'id'=>'mail'])?>
</div>
<div class="modal-footer">
	<button type="button" class="btn btn-default" data-dismiss="modal">关闭</button>
    <button type="button" class="btn btn-primary" onclick="ajaxsendmessage('<?=$order->order_id?>',$('#type').val(),$('#title').val(),$('#content').val(),$('#mail').val())"> 提交</button>
</div>
<script>
function ajaxsendmessage(orderid,type,title,content,mail){
	$('#myMessage').modal('hide');
	$.showLoading();
	$.post("<?=Url::to(['/order/order/ajaxsendmessage'])?>",{orderid:orderid,type:type,title:title,content:content,mail:mail},function(result){
		if(result=='success'){
			$.hideLoading();
			bootbox.alert('操作已成功');
		}else{
			$.hideLoading();
			bootbox.alert('操作失败:'+result);
		}
	});
}
</script>