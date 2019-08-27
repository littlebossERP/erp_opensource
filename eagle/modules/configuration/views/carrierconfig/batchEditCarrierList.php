<?php
use yii\helpers\Html;
use eagle\modules\util\helpers\TranslateHelper;
?>




<div style='height:65px;margin: 0 auto;text-align: center;'>

<?php
if($edit_type == 'shipping_account'){
	$bindingCarrierAccounts = $relatedparams['bindingCarrierAccounts'];
?>
物流账号：<?= Html::dropDownList('accountID','',$bindingCarrierAccounts,['class'=>'input-sm','style'=>'width:250px;']) ?>
<?php
}else if($edit_type == 'shipping_address'){
	$commonAddressArr = $relatedparams['commonAddressArr'];

	$adds = ['0'=>'默认揽收地址'];
	
	if(!empty($commonAddressArr))
		$adds = $commonAddressArr;

	echo '地址信息：'.Html::dropDownList('common_address_id','',$adds,['class'=>'input-sm','style'=>'width:250px;']);
}
?>

</div>

<div class="modal-footer col-xs-12">
	<button type="button" class="iv-btn btn-primary btn-sm" onclick="savebatchEditCarrierinfo('<?=$edit_type ?>')">确定</button>
	<button class="iv-btn btn-default btn-sm modal-close">关 闭</button>
</div>


