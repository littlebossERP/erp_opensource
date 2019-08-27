<?php 
use yii\helpers\Html;
?>




<?php if (is_array($salestaxstate)):?>

<div class="form-group">
	<label class="control-label col-lg-3">运费加税<span class="requirefix">*</span></label>
	<div class="col-lg-5">
	<div class="whole-onebox">
		<?php array_unshift($salestaxstate, '');?>
		<?php echo Html::dropDownList('shippingdetails[SalesTax][SalesTaxState]',isset($data['shippingdetails']['SalesTax']['SalesTaxState'])?$data['shippingdetails']['SalesTax']['SalesTaxState']:'', $salestaxstate,['class'=>'iv-input main-input'])?>
		<?php echo Html::textInput('shippingdetails[SalesTax][SalesTaxPercent]',isset($data['shippingdetails']['SalesTax']['SalesTaxPercent'])?$data['shippingdetails']['SalesTax']['SalesTaxPercent']:'',array('size'=>8,'class'=>'iv-input'))?>%
		&nbsp;&nbsp;
		<?php if ($data['siteid']==0):?>
		<?php echo Html::checkbox('shippingdetails[SalesTax][ShippingIncludedInTax]',isset($data['shippingdetails']['SalesTax']['ShippingIncludedInTax'])?$data['shippingdetails']['SalesTax']['ShippingIncludedInTax']:'')?>运费加税
		<?php endif;?>
	</div>

</div>
</div>
<?php endif;?>
