<?php
use yii\helpers\Html;
use common\helpers\Helper_Siteinfo;
use eagle\models\EbayCategory;
/**
 * 处理刊登模板的境内物流数据设置
 * @author fanjs
 */
?>
<?php $shippingservice=$shippingserviceall['shippingservice']?>
<?php $shippingservice=array_merge(array(''=>''),$shippingservice)?>
<?php echo Html::hiddenInput('tmp',count(isset($data['shippingdetails']['ShippingServiceOptions'])?$data['shippingdetails']['ShippingServiceOptions']:''),['id'=>'tmp'])?>
<?php $data['shippingdetails']=(array)$data['shippingdetails'];?>
<?php for($i=0;$i<4;$i++):?>
<div class="subdiv shipping" id="shippingservice_<?php echo $i ?>" <?php if ($i>0&&strlen(@$data['shippingdetails']['ShippingServiceOptions'][$i]['ShippingService'])==0){echo 'style="display:none"';}?>>
<?php if ($i>0):?>
<span class="closeshipping iconfont icon-guanbi" onclick="hideshipping(this)"></span>
<?php endif;?>
<table>
<tr>
<th><strong>第<?php echo $i+1?>组运输</strong></th><th></th>
</tr>
<tr>
<th>运输方式</th>
	<td>
	<?php echo Html::dropDownList('shippingdetails[ShippingServiceOptions]['.$i.'][ShippingService]',
			isset($data['shippingdetails']['ShippingServiceOptions'][$i]['ShippingService'])?$data['shippingdetails']['ShippingServiceOptions'][$i]['ShippingService']:'',$shippingservice,['class'=>'iv-input main-input'])?>
	</td>
</tr>
<tr>
<th>主运费</th>
	<td>
	<?php echo Html::textInput('shippingdetails[ShippingServiceOptions]['.$i.'][ShippingServiceCost]',@$data['shippingdetails']['ShippingServiceOptions'][$i]['ShippingServiceCost'],['id'=>'shippingdetails_ShippingServiceOptions_'.$i.'_ShippingServiceCost','class'=>'iv-input'])?>
	<?php echo Helper_Siteinfo::getSiteCurrency($data['siteid'])?>
	<?php if ($i==0):?>
	<?php echo Html::button('免邮',array('class'=>'iv-btn btn-search','onclick'=>"$('#shippingdetails_ShippingServiceOptions_0_ShippingServiceCost').val('0.00');$('#shippingdetails_ShippingServiceOptions_0_ShippingServiceAdditionalCost').val('0.00');$('#shippingdetails_ShippingServiceOptions_0_ShippingSurcharge').val('0.00');"))?>
	<?php endif;?>
	</td>
</tr>
<th>超重运费</th>
	<td>
	<?php echo Html::textInput('shippingdetails[ShippingServiceOptions]['.$i.'][ShippingServiceAdditionalCost]',@$data['shippingdetails']['ShippingServiceOptions'][$i]['ShippingServiceAdditionalCost'],['id'=>'shippingdetails_ShippingServiceOptions_'.$i.'_ShippingServiceAdditionalCost','class'=>'iv-input'])?>
	<?php echo Helper_Siteinfo::getSiteCurrency($data['siteid'])?>
	</td>
</tr>
<!-- 需要过滤美国站或者是motor站的p&a类目下方可显示 -->
<?php if (strlen($data['primarycategory'])):?>
<?php if ($data['siteid']==0||($data['siteid']==100)&&EbayCategory::getRootcategoryid($data['primarycategory'],$data['siteid'])==6028):?>
<tr>
	<th>AK,HI,PR特殊地区收费</th>
	<td>
	<?php echo Html::textInput('shippingdetails[ShippingServiceOptions]['.$i.'][ShippingSurcharge]',strlen(@$data['shippingdetails']['ShippingServiceOptions'][$i]['ShippingSurcharge'])?@$data['shippingdetails']['ShippingServiceOptions'][$i]['ShippingSurcharge']:'0.00',['id'=>'shippingdetails_ShippingServiceOptions_'.$i.'_ShippingSurcharge','class'=>'iv-input'])?>
	<?php echo Helper_Siteinfo::getSiteCurrency($data['siteid'])?>
	</td>
</tr>
<?php endif;?>
<?php endif;?>
</table>
</div>
<?php endfor;?>
<div class="subdiv">
<span class="wuliudeal" onclick="doshow();"><span class="iconfont icon-zengjia"></span>添加一组物流</span>
<!-- <span class="wuliudeal" onclick="dohide();"><span class="iconfont icon-shanchu"></span>移除一组物流</span> -->
</div>