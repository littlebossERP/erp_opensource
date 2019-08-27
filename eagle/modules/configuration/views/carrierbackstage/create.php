<?php 
use eagle\modules\util\helpers\TranslateHelper;
use yii\helpers\Html;
 ?>
 <div class="modal-header">
    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
    <h4 class="modal-title" id="myModalLabel">物流商</h4>
</div>
<form action="<?= \Yii::$app->urlManager->createUrl('/configuration/carrierbackstage/create') ?>" method="post" id="create_form">
<input type="hidden" name="check" value="1">
<input type="hidden" name="code" value="<?= $code ?>">
<!-- table -->
<div class="table-responsive">
    <table cellspacing="0" cellpadding="0" class="table table-hover">
    <?php if(empty($code)): ?>
        <tr>
            <td><?=TranslateHelper::t('物流商代码') ?></td>
            <td><input type="text" name="carrier_code" value="<?= @$carrier['carrier_code'] ?>">注意:确定后将不可被修改</td>
	    </tr>
	<?php endif; ?>
        <tr>
            <td><?=TranslateHelper::t('物流商名') ?></td>
            <td><input type="text" name="carrier_name"  value="<?= @$carrier['carrier_name'] ?>"></td>
	    </tr>
        <tr>
            <td><?=TranslateHelper::t('物流商类型') ?></td>
            <td>
 		        <input type="radio" value="0" name="carrier_type" <?= @$carrier['carrier_type']==0?'checked':'' ?> autocomplete="off"><?=TranslateHelper::t('货代') ?>
            	<input type="radio" value="1" name="carrier_type" <?= @$carrier['carrier_type']==1?'checked':'' ?> autocomplete="off"><?=TranslateHelper::t('海外仓') ?>
            </td>
	    </tr>
        <tr>
            <td><?=TranslateHelper::t('接口类名') ?></td>
            <td><input type="text" name="api_class" value="<?= @$carrier['api_class'] ?>" data-toggle="tooltip" data-placement="right" title="<?=TranslateHelper::t('请输入接口名') ?>"></td>
	    </tr>
	    <tr>
            <td><?=TranslateHelper::t('卖家需提供地址') ?></td>
            <td>
            	<?= Html::checkboxList('address_list',@$carrier['address_list'],['pickupaddress'=>'揽收地址','returnaddress'=>'退货地址','shippingfrom'=>'发货地址','shippingfrom_en'=>'发货地址(英文)']) ?>
            </td>
	    </tr>
	    <tr>
	    	<td><?=TranslateHelper::t('物流商是否启用') ?></td>
	    	<?php 
		    	if(@$carrier['is_active'] == 1){
		    		$carrier_is_active = array('is_active');
		    	}else{
		    		$carrier_is_active = array();
		    	}
	    	?>
	    	<td><?= Html::checkboxList('is_active',@$carrier_is_active,['is_active'=>'是否启用']) ?></td>
	    </tr>
	    <tr>
	    	<td><?=TranslateHelper::t('小老板帮助文档URL') ?></td>
	    	<td><input style="width:300px;" type="text" name="help_url" value="<?= @$carrier['help_url'] ?>" data-toggle="tooltip" data-placement="right" title="<?=TranslateHelper::t('请输入帮助URL') ?>"></td>
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
			url: '<?= \Yii::$app->urlManager->createUrl("/configuration/carrierbackstage/create") ?>',
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
