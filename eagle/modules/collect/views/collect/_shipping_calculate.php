<?php
use yii\helpers\Html;
use common\helpers\Helper_Siteinfo;
use common\helpers\Helper_Array;
use eagle\models\EbayPackagetype;
/**
 * 设置物流计算的页面
 * @author fanjs
 */
?>
<script>

</script>
<?php 
if(in_array($data['siteid'],array('0'))){
	$length='inch';
	$weightb='lbs';
	$weight='oz';
}elseif (in_array($data['siteid'],array('15','2','210'))){
	$length='cm';
	$weightb='kg';
	$weight='g';
}else{
	$length='';
	$weightb='';
	$weight='';
}
?>
<table>
<tr><td>境内</td><td><?php echo Html::dropDownList('shippingdetails[shippingdomtype]',@$data['shippingdetails']['shippingdomtype'],array('Flat'=>'Flat','Calculated'=>'Calculated'),array('onchange'=>'showihc()','id'=>'shippingdetails_shippingdomtype','class'=>'iv-input main-input'))?></td></tr>
<tr><td>境外</td><td><?php echo Html::dropDownList('shippingdetails[shippinginttype]',@$data['shippingdetails']['shippinginttype'],array('Flat'=>'Flat','Calculated'=>'Calculated'),array('onchange'=>'showihc()','id'=>'shippingdetails_shippinginttype','class'=>'iv-input main-input'))?></td></tr>
<table id="ihc" <?php if (@$data['shippingdetails']['shippingdomtype']=='Flat'&&@$data['shippingdetails']['shippinginttype']=='Flat'||
		!isset($data['shippingdetails']['shippingdomtype'])&&!isset($data['shippingdetails']['shippinginttype'])){echo 'style="display:none;"';}?>>
<tr>
<td>邮编</td>
<td><?php echo Html::textInput('shippingdetails[CalculatedShippingRate][OriginatingPostalCode]',isset($data['shippingdetails']['CalculatedShippingRate']['OriginatingPostalCode'])?$data['shippingdetails']['CalculatedShippingRate']['OriginatingPostalCode']:'',array('class'=>'iv-input'));?></td>
</tr>
<tr>
<td>国内手续费</td>
<td><?php echo Html::textInput('shippingdetails[CalculatedShippingRate][PackagingHandlingCosts]',isset($data['shippingdetails']['CalculatedShippingRate']['PackagingHandlingCosts'])?$data['shippingdetails']['CalculatedShippingRate']['PackagingHandlingCosts']:'',array('class'=>'iv-input'))?>
<?php echo Helper_Siteinfo::getSiteCurrency($data['siteid'])?>
</td>
</tr>
<tr>
<td>国际手续费</td>
<td><?php echo Html::textInput('shippingdetails[CalculatedShippingRate][InternationalPackagingHandlingCosts]',isset($data['shippingdetails']['CalculatedShippingRate']['InternationalPackagingHandlingCosts'])?$data['shippingdetails']['CalculatedShippingRate']['InternationalPackagingHandlingCosts']:'',array('class'=>'iv-input'))?>
<?php echo Helper_Siteinfo::getSiteCurrency($data['siteid'])?>
</td>
</tr>
<tr>
<td>尺寸规格</td>
<td>
长:<?php echo Html::textInput('shippingdetails[CalculatedShippingRate][PackageLength]',isset($data['shippingdetails']['CalculatedShippingRate']['PackageLength'])?$data['shippingdetails']['CalculatedShippingRate']['PackageLength']:'',array('class'=>'iv-input'))?><?php echo $length?>&nbsp;&nbsp;
宽:<?php echo Html::textInput('shippingdetails[CalculatedShippingRate][PackageWidth]',isset($data['shippingdetails']['CalculatedShippingRate']['PackageWidth'])?$data['shippingdetails']['CalculatedShippingRate']['PackageWidth']:'',array('class'=>'iv-input'))?><?php echo $length?>&nbsp;&nbsp;
高:<?php echo Html::textInput('shippingdetails[CalculatedShippingRate][PackageDepth]',isset($data['shippingdetails']['CalculatedShippingRate']['PackageDepth'])?$data['shippingdetails']['CalculatedShippingRate']['PackageDepth']:'',array('class'=>'iv-input'))?><?php echo $length?>&nbsp;&nbsp;
</td>
</tr>
<tr>
<td>估量重量</td>
<td>
<?php echo Html::textInput('shippingdetails[CalculatedShippingRate][WeightMajor]',isset($data['shippingdetails']['CalculatedShippingRate']['WeightMajor'])?$data['shippingdetails']['CalculatedShippingRate']['WeightMajor']:'',array('class'=>'iv-input'))?><?php echo $weightb?>
<?php echo Html::textInput('shippingdetails[CalculatedShippingRate][WeightMinor]',isset($data['shippingdetails']['CalculatedShippingRate']['WeightMinor'])?$data['shippingdetails']['CalculatedShippingRate']['WeightMinor']:'',array('class'=>'iv-input'))?><?php echo $weight?>
</td>
</tr>
<tr>
<td>包裹大小</td>
<td>
<?php echo Html::dropDownList('shippingdetails[CalculatedShippingRate][ShippingPackage]', isset($data['shippingdetails']['CalculatedShippingRate']['ShippingPackage'])?$data['shippingdetails']['CalculatedShippingRate']['ShippingPackage']:'',
		Helper_Array::toHashmap(EbayPackagetype::findAll(['siteid' =>$data['siteid']]),'packagevalue','packagedescription'),['class'=>'iv-input'])?>
<?php echo Html::checkbox('shippingdetails[CalculatedShippingRate][ShippingIrregular]',isset($data['shippingdetails']['CalculatedShippingRate']['ShippingIrregular'])?$data['shippingdetails']['CalculatedShippingRate']['ShippingIrregular']:'')?><label for="isirregular">是否不规则</label>
</td>
</tr>
</table>
