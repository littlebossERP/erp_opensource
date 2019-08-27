<?php 
use yii\helpers\Html;
?>
<p class="title">运输类型</p>
<div class="subdiv">
<?php echo $this->render('_shipping_calculate',array('data'=>$data))?>
</div>
<p class="title">境内物流</p>
<?php echo $this->render('_shipping_service',array('data'=>$data,'shippingserviceall'=>$shippingserviceall))?>
<p class="title">境外物流</p>
<?php echo $this->render('_shipping_inservice',array('data'=>$data,'shippingserviceall'=>$shippingserviceall))?>
<p class="title">屏蔽目的地</p>
<div class="subdiv">
<?php echo $this->render('_shipping_excludeship',array('data'=>$data))?>
</div>
<?php if (is_array($salestaxstate)):?>
<p class="title">运费加税</p>
<div class="subdiv">
	<?php array_unshift($salestaxstate, '');?>
	<?php echo Html::dropDownList('shippingdetails[SalesTax][SalesTaxState]',isset($data['shippingdetails']['SalesTax']['SalesTaxState'])?$data['shippingdetails']['SalesTax']['SalesTaxState']:'', $salestaxstate,['class'=>'iv-input main-input'])?>
	<?php echo Html::textInput('shippingdetails[SalesTax][SalesTaxPercent]',isset($data['shippingdetails']['SalesTax']['SalesTaxPercent'])?$data['shippingdetails']['SalesTax']['SalesTaxPercent']:'',array('size'=>8,'class'=>'iv-input'))?>%
	&nbsp;&nbsp;
	<?php if ($data['siteid']==0):?>
	<?php echo Html::checkbox('shippingdetails[SalesTax][ShippingIncludedInTax]',isset($data['shippingdetails']['SalesTax']['ShippingIncludedInTax'])?$data['shippingdetails']['SalesTax']['ShippingIncludedInTax']:'')?>运费加税
	<?php endif;?>
</div>
<?php endif;?>
