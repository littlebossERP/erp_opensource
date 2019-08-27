<?php
use eagle\modules\util\helpers\TranslateHelper;
use yii\helpers\Html;
?>
<div class="modal-header" >
	<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
	<h4 class="modal-title" id="myModalLabel">运输方式高仿打印设置</h4>
</div>


<form action="<?= \Yii::$app->urlManager->createUrl('/configuration/carrierbackstage/edit-shipping-print') ?>" method="post" id="create_form">
<input type="hidden" name="check" value="1">
<input type="hidden" name="code" value="<?= $code ?>">
<input type="hidden" name="methodCode" value="<?= $methodCode ?>">
<input type="hidden" name="thirdPartyCode" value="<?= $thirdPartyCode ?>">

<div class="table-responsive" style='margin-left:10px;'>
<?= Html::checkboxList('is_api_print',@$print_is_api,['is_api_print'=>'是否支持API']) ?>
<br>
<?= Html::checkboxList('high_copy',@$print_high_copy,['high_copy'=>'是否对接高仿']) ?>
<br>
<?= Html::checkboxList('lable_list',@json_decode($shippingMethod['print_params'],true),['label_address'=>'地址单','label_declare'=>'报关单','label_items'=>'配货单']) ?>
<br>
<?= Html::checkboxList('is_close',@$is_close,['is_close'=>'是否废弃']) ?>
</div>


<div class="modal-footer">
	<button type="button" class="btn btn-default" data-dismiss="modal"><?=TranslateHelper::t('关闭') ?></button>
	<button type="button" class="btn btn-primary" id="savebutton"><?=TranslateHelper::t('更新') ?></button>
</div>

</form>
<script>
	$('#savebutton').click(function(){
		var formdata = $('#create_form').serialize();
		$.ajax({
	        type : 'post',
	        data:formdata,
			url: '<?= \Yii::$app->urlManager->createUrl("/configuration/carrierbackstage/edit-shipping-print") ?>',
	        success:function(result) {
	        	if(result){
	        		$('#carrierPrint').modal('hide');
	        		location.reload();
	        	}else{
	        		alert('开启高仿必须开启地址单');
	        	}
	        }
	    });

	})
</script>