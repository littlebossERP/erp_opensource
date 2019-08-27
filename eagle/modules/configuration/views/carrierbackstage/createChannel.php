<?php
use eagle\modules\util\helpers\TranslateHelper;
use yii\helpers\Html;
?>
<div class="modal-header" style='<?=$carrier_type == 1 ? 'display:none;' : ''; ?>'>
	<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
	<h4 class="modal-title" id="myModalLabel">运输方式格式为：运输代码:运输方式;</h4>
	<h5>示例：<span style='color:red;'>code1:name1;code2:name2;code3:name3;code4:name4;</span></h5>
	<h6>:(英文冒号) ;(英文分号)</h6>
</div>

<div class="modal-header" style='<?=$carrier_type == 0 ? 'display:none;' : ''; ?>'>
	<h4 class="modal-title" id="myModalLabel">海外仓:在文件中添加对应的【物流商代码.php】文件 点击更新按钮直接读取该文件生成物流运输方式</h4>
</div>

<form action="<?= \Yii::$app->urlManager->createUrl('/configuration/carrierbackstage/create-channel') ?>" method="post" id="create_form">
<input type="hidden" name="check" value="1">
<input type="hidden" name="code" value="<?= $code ?>">
<div class="table-responsive" style='<?=$carrier_type == 1 ? 'display:none;' : ''; ?>'>
<textarea name='channel_textarea' rows="6" cols="76" style='margin-left: 20px;' >
</textarea>
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
			url: '<?= \Yii::$app->urlManager->createUrl("/configuration/carrierbackstage/create-channel") ?>',
	        success:function(result) {
	        	if(result){
	        		$('#checkOrder').modal('hide');
	        		location.reload();
	        	}else{
	        		alert('数据有误,请检查');
	        	}
	        }
	    });

	})
</script>