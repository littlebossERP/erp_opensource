<?php 
use eagle\modules\util\helpers\TranslateHelper;
use yii\helpers\Html;
 ?>
 <div class="modal-header">
    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
    <h4 class="modal-title" id="myModalLabel">物流商</h4>
</div>
<form action="<?= \Yii::$app->urlManager->createUrl('carrier/carrier/create') ?>" method="post" id="create_form">
<input type="hidden" name="code" value="<?= $code ?>">
<!-- table -->
<div class="table-responsive">
    <table cellspacing="0" cellpadding="0" class="table table-hover">
        <tr>
            <td><?=TranslateHelper::t('自定义物流商名') ?></td>
            <td><input type="text" name="carrier_name"  value="<?= @$carrier['carrier_name'] ?>"></td>
	    </tr>
	    <tr>
            <td><?=TranslateHelper::t('卖家需提供地址') ?></td>
            <td>
            	<?= Html::checkboxList('address_list[]',@$carrier['address_list'],['pickupaddress'=>'揽收地址','returnaddress'=>'退货地址','shippingfrom'=>'发货地址','shippingfrom_en'=>'发货地址(英文)']) ?>
            </td>
	    </tr>
    </table>
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
			url: '<?= \Yii::$app->urlManager->createUrl("carrier/carrier/createcustom") ?>',
	        success:function(result) {
	        	if(result==true){
	        		$('#checkOrder').modal('hide');
	        		location.reload();
	        	}else{
	        		alert(result);
	        	}
	        }
	    });

	})
</script>
