<?php
use yii\helpers\Html;
use eagle\modules\util\helpers\TranslateHelper;
use eagle\modules\order\helpers\OrderFrontHelper;
?>
<style>
	.my-body > div>label{
		width:70px;
		text-align:right;
	}
	.my-body > div{
		margin-bottom:15px;
	}
	.my-body{
	width:800px;
	}
</style>
<script type="text/javascript">
<?php echo 'OrderCommon.overseaList='.json_encode($locList).';';?>
</script>
<div class="modal-body my-body">
<?php 
if (! isset($default_shipping_method_code)) $default_shipping_method_code = '';
OrderFrontHelper::getShippingServicesHtml('all', $warehouseList, $shipmethodList , $default_shipping_method_code )?>
</div>
<div class="text-center modal-footer">
	<button type="button" class="iv-btn btn-lg btn-primary" id="ware-ship-modal-save"> 保存</button>
	<input type="button" class="iv-btn btn-lg btn-default modal-close" value="返回">
</div>

