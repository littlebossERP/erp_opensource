<?php
use yii\helpers\Html;
use common\helpers\Helper_Siteinfo;
/**
 * 处理刊登模板的境外物流数据设置
 * @author fanjs
 */
?>
<?php $inshippingservice=$shippingserviceall['ishippingservice']?>
<?php $shiplocation=$shippingserviceall['shiplocation']?>
<?php $inshippingservice=array_merge(array(''=>''),$inshippingservice)?>
<?php 
	if (isset($data['shippingdetails']['InternationalShippingServiceOption']['ShippingService'])){
		$_tmp = $data['shippingdetails']['InternationalShippingServiceOption'];
		$data['shippingdetails']['InternationalShippingServiceOption'] = [$_tmp];
	}
?>
<?php echo Html::hiddenInput('intmp',is_array(@$data['shippingdetails']['InternationalShippingServiceOption'])?count(@$data['shippingdetails']['InternationalShippingServiceOption']):0,['id'=>'intmp'])?>
<?php $data['shippingdetails']=(array)$data['shippingdetails'];?>
<?php 
//讲所选平台的shiptolocation转化为新进范本默认值
$default_shiptolocation = array_keys($shiplocation);
?>
<?php for($i=0;$i<5;$i++):?>
<div class="subdiv inshipping" id="inshippingservice_<?php echo $i?>" <?php if (!isset($data['shippingdetails']['InternationalShippingServiceOption'][$i]['ShippingService'])||(isset($data['shippingdetails']['InternationalShippingServiceOption'][$i]['ShippingService'])&&strlen($data['shippingdetails']['InternationalShippingServiceOption'][$i]['ShippingService'])==0)){echo 'style="display:none"';}?>>
<span class="closeshipping iconfont icon-guanbi" onclick="hideshipping(this)"></span>
<table>
<tr>
<th width="120px"><strong>第<?php echo $i+1?>组运输</strong></th><th></th>
</tr>
<tr>
<th>运输方式</th>
	<td>
	<?php echo Html::dropDownList('shippingdetails[InternationalShippingServiceOption]['.$i.'][ShippingService]',
			isset($data['shippingdetails']['InternationalShippingServiceOption'][$i]['ShippingService'])?$data['shippingdetails']['InternationalShippingServiceOption'][$i]['ShippingService']:'',$inshippingservice,['class'=>'iv-input main-input'])?>
	</td>
</tr>
<tr>
<th>主运费</th>
	<td>
	<?php echo Html::textInput('shippingdetails[InternationalShippingServiceOption]['.$i.'][ShippingServiceCost]',
			isset($data['shippingdetails']['InternationalShippingServiceOption'][$i]['ShippingServiceCost'])?$data['shippingdetails']['InternationalShippingServiceOption'][$i]['ShippingServiceCost']:'',['class'=>'iv-input'])?>
	<?php echo Helper_Siteinfo::getSiteCurrency($data['siteid'])?>
	</td>
</tr>
<th>超重运费</th>
	<td>
	<?php echo Html::textInput('shippingdetails[InternationalShippingServiceOption]['.$i.'][ShippingServiceAdditionalCost]',
			isset($data['shippingdetails']['InternationalShippingServiceOption'][$i]['ShippingServiceAdditionalCost'])?$data['shippingdetails']['InternationalShippingServiceOption'][$i]['ShippingServiceAdditionalCost']:'',['class'=>'iv-input'])?>
	<?php echo Helper_Siteinfo::getSiteCurrency($data['siteid'])?>
	</td>
</tr>
<tr>
	<th>运往目的地</th>
	<td>
	<?php echo Html::checkboxList('shippingdetails[InternationalShippingServiceOption]['.$i.'][ShipToLocation]',
			 isset($data['shippingdetails']['InternationalShippingServiceOption'][$i]['ShipToLocation'])?$data['shippingdetails']['InternationalShippingServiceOption'][$i]['ShipToLocation']:$default_shiptolocation, $shiplocation)?>
	</td>
</tr>
</table>
</div>
<?php endfor;?>
<div class="subdiv">
<span class="wuliudeal" onclick="do2show();"><span class="iconfont icon-zengjia"></span>添加一组物流</span>
<!-- <span class="wuliudeal" onclick="do2hide();"><span class="iconfont icon-shanchu"></span>移除一组物流</span> -->
</div>
