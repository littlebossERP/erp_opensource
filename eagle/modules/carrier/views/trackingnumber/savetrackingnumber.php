<?php 
use eagle\modules\util\helpers\TranslateHelper;
use yii\helpers\Html;
 ?>
 <div class="modal-header">
    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
    <h4 class="modal-title" id="myModalLabel">添加可用物流号</h4>
</div>
<form action="<?= \Yii::$app->urlManager->createUrl('carrier/carrier/create') ?>" method="post" id="create_form">
<!-- table -->
<div class="table-responsive">
<div class="container-fluid">
  <div class="row">
  <div class="col-md-2"><?=TranslateHelper::t('运输服务') ?></div>
  <div class="col-md-10"><?php echo Html::dropDownList('shipping_service_id','',$services,['prompt'=>'请选择运输服务','class'=>'eagle-form-control'])?></div>
</div>
 <div class="row">
  <div class="col-md-2"><?=TranslateHelper::t('物流号') ?></div>
  <div class="col-md-10"><?php echo Html::textarea('tracking_number','',['placeholder'=>"每行一个物流号,不需要标点符号和字符",'style'=>'height:300px;','rows'=>"20", 'cols'=>"50" ,'class'=>'eagle-form-control'])?></div>
</div>
</div>
</div>
<div class="modal-footer">
	<button type="button" class="btn btn-default" data-dismiss="modal"><?=TranslateHelper::t('关闭') ?></button>
	<button type="button" class="btn btn-primary" id="savebutton"><?=TranslateHelper::t('保存') ?></button>
</div>

</form>
<script>
	$('#savebutton').click(function(){
		var formdata = $('#create_form').serialize();
		$.ajax({
	        type : 'post',
	        data:formdata,
			url: '<?= \Yii::$app->urlManager->createUrl("carrier/trackingnumber/savetrackingnumber") ?>',
	        success:function(result) {
	        	var result = $.parseJSON(result);
	        	if(result.error==0){
	        		$('#checkOrder').modal('hide');
	        		if(result.data != ''){
	        			var nums = result.data.join(',');
		        		bootbox.alert({message:'跟踪号：'+nums+'在系统内已存在，其余跟踪号添加成功',callback:function(){
		        			location.reload();
		        		}});
	        		}else{
	        			location.reload();
	        		}
	        	}else{
	        		alert(result.msg);
	        	}
	        }
	    });

	})
</script>
