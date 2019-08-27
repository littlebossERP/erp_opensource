<?php

use yii\helpers\Html;
use yii\helpers\Url;
use common\helpers\Helper_Siteinfo;
use common\helpers\Helper_Array;
use eagle\models\EbayExcludeshippinglocation;
use eagle\modules\listing\helpers\EbayListingHelper;
// $this->registerJsFile(\Yii::getAlias('@web')."/js/project/listing/mubanedit.js", ['depends' => ['yii\web\JqueryAsset']]);
// $this->registerJsFile(\Yii::getAlias('@web')."/js/project/listing/mubanedit.js?v=".EbayListingHelper::$listingVer, ['depends' => ['yii\web\JqueryAsset']]);
?>
<style>
.mianbaoxie{
	margin:12px 0px;
	font-weight:bold;
}
.mianbaoxietitle{
	border-color:rgb(1,189,240);
	border-width:0px 3px;
	border-style:solid;
	margin-right:5px;
}
.main-area{
	width:700px;
}
.title{
	margin:10px 10px 10px 0px;
}
.main-input{
	width:300px;
}
.subdiv{
	background-color:rgb(249,249,249);
	padding:12px;
	margin:5px 0px;
}
.subdiv>th,th{
	width:100px;
}
td{
	padding:2px 20px;
}
.btndo{
	margin-top:20px;
	padding-bottom:40px;
}
.btndo button{
	margin-left:40px;
	padding-left:30px;
	padding-right:30px;
}
</style>
<div class="tracking-index col2-layout">
<?=$this->render('../_ebay_leftmenu',['active'=>'在线Item']);?>
<div class="content-wrapper" >
<form action="<?=Url::to(['/listing/ebayitem/mltichangeresult'])?>" method="post" id="a" name="a">
<?=Html::hiddenInput('itemid',$itemid)?>
<?php $siteid_map = Helper_Array::toHashmap(Helper_Siteinfo::getEbaySiteIdList(), 'en', 'no');$siteid = $siteid_map[$demo->site];?>
<div class="main-area">
	<p class="mianbaoxie"><span class="mianbaoxietitle"></span>基本设置</p>
	<?php if (in_array('itemtitle', $setitems)):?>
	<p class="title">标题</p>
	<?=Html::textInput('itemtitle','',['class'=>'iv-input main-input'])?>
	<?php endif;?>
	
	<?php if (in_array('subtitle', $setitems)):?>
	<p class="title">副标题</p>
	<?=Html::textInput('itemtitle2','',['class'=>'iv-input main-input'])?>
	<?php endif;?>
	
	<?php if (in_array('category', $setitems) && $qubie['categorytmp'] == 1 && $qubie['sitetmp'] == 1):?>
	<p class="title">商品分类</p>
	<input class="category iv-input main-input" id="primarycategory" name="primarycategory" size="25">
	<input type="button" class="iv-btn btn-search" value="选择分类" onclick="window.open('<?=Url::to(['/listing/ebaymuban/selectebaycategoryforrevise','siteid'=>$siteid,'elementid'=>'primarycategory'])?>')">
	<?php endif;?>
	<!-- 
	<?php if (in_array('promotion', $setitems)):?>
	<p class="title">折扣</p>
	<?=Html::dropDownList('promotion','',[])?>
	<?php endif;?>
	 -->
	<?php if (in_array('location', $setitems)):?>
	<p class="title">物品所在地</p>
	<div class="subdiv">
	  	<table>
		<tr>
		<th>国家</th>
		<td>
		<?php echo Html::dropDownList('country','',$locationarr,['class'=>'iv-input'])?>
		</td>
		</tr>
		<tr>
		<th>地区</th>
		<td>
		<?php echo Html::textInput('location','',['class'=>'iv-input'])?>
		</td>
		</tr>
		<tr>
		<th>邮编</th>
		<td>
		<?php echo Html::textInput('postalcode','',['class'=>'iv-input'])?>
		</td>
		</tr>
		</table>
	</div>
	<?php endif;?>
	
	<?php if (in_array('listingduration', $setitems) && $qubie['listingtype'] == 1):?>
	<p class="title">刊登天数</p>
	<?php $listingtype = $demo->listingtype == 'Chinese'?'Chinese':'FixedPriceItem';?>
	<?php echo Html::dropDownList('listingduration','',Helper_Siteinfo::getListingDuration($listingtype),['class'=>'iv-input'])?>
	<?php endif;?>
</div>
<div class="main-area">
	<p class="mianbaoxie"><span class="mianbaoxietitle"></span>物流设置</p>
	<?php if (in_array('shippingcost', $setitems)):?>
	<p class="title">国内主运费</p>
	<?php echo Html::textInput('ShippingServiceCost','0.00',['class'=>'iv-input'])?>
	<?php echo Html::textInput('ShippingServiceAdditionalCost','0.00',['class'=>'iv-input'])?>
	<?php endif;?>
	
	<?php if (in_array('inshippingcost', $setitems)):?>
	<p class="title">国际主运费</p>
	<?php echo Html::textInput('inShippingServiceCost','0.00',['class'=>'iv-input'])?>
	<?php echo Html::textInput('inShippingServiceAdditionalCost','0.00',['class'=>'iv-input'])?>
	<?php endif;?>
	
	<?php if (in_array('salestax', $setitems) && is_array($salestaxstate) && $qubie['sitetmp'] == 1):?>
	<p class="title">运费加税</p>
	<div class="subdiv">
		<?php array_unshift($salestaxstate, '');?>
		<?php echo Html::dropDownList('shippingdetails[SalesTax][SalesTaxState]','', $salestaxstate,['class'=>'iv-input main-input'])?>
		<?php echo Html::textInput('shippingdetails[SalesTax][SalesTaxPercent]','',array('size'=>8,'class'=>'iv-input'))?>%
		&nbsp;&nbsp;
		<?php echo Html::checkbox('shippingdetails[SalesTax][ShippingIncludedInTax]')?>运费加税
	</div>
	<?php endif;?>
	
	<?php if (in_array('excludelocation', $setitems)):?>
	<p class="title">屏蔽目的地</p>
	<div id="exclude_all">
		<div>
	        <input type="checkbox" class="excludeship" >常用 <a href="#fjs" onclick='$("#excludeshipnormal").slideToggle();'>展开/收起</a> <br>
	        <div id="excludeshipnormal">
	        <?php $excludeship=EbayExcludeshippinglocation::find()->where(array('region'=>array('Domestic Location','Additional Locations'),'siteid'=>$siteid))->asArray()->all()?>
	        <?php $excludeship=Helper_Array::toHashmap($excludeship,'location','description');?>
	        <?php $htm=Html::checkboxList('shippingdetails[ExcludeShipToLocation]','', $excludeship,['itemOptions'=>['class'=>'excludeship']]);?>
			<?php echo $htm;?>
	        </div>
		</div><hr>
		<div>
			<input type="checkbox" class="excludeship" >世界 <a href="#fjs" onclick='$("#excludeshipworld").slideToggle();'>展开/收起</a> <br>
	        <div id="excludeshipworld">
	        <?php $excludeshipsj=EbayExcludeshippinglocation::find()->where(array('region'=>'Worldwide','siteid'=>$siteid))->asarray()->all();?>
	        <?php $excludeshipsj=Helper_Array::toHashmap($excludeshipsj,'location','description');?>
	        <?php $htm=Html::checkboxList('shippingdetails[ExcludeShipToLocation]','', $excludeshipsj,['itemOptions'=>['class'=>'excludeship']])?>
			<?php echo $htm;?>
	        </div>
		</div><hr>
		<div>
	        <input type="checkbox" class="excludeship" >南美 <a href="#fjs" onclick=$('#excludeshipsouthamer').slideToggle();>展开/收起</a> <br>
	        <div id="excludeshipsouthamer" >
	        <?php $excludeshipsouthamer=EbayExcludeshippinglocation::find()->where(array('region'=>'South America','siteid'=>$siteid))->asarray()->all();?>
	        <?php $excludeshipsouthamer=Helper_Array::toHashmap($excludeshipsouthamer,'location','description');?>
	        <?php $htm=Html::checkboxList('shippingdetails[ExcludeShipToLocation]','', $excludeshipsouthamer,['itemOptions'=>['class'=>'excludeship']])?>
			<?php echo $htm;?>
	        </div>
		</div><hr>
		<div>
	       	<input type="checkbox" class="excludeship" >东南亚 <a href="#fjs" onclick=$('#excludeshipsouthasia').slideToggle();>展开/收起</a> <br>
	       	<div id="excludeshipsouthasia" >
	       	<?php $excludeshipsouthasia=EbayExcludeshippinglocation::find()->where(array('region'=>'Southeast Asia','siteid'=>$siteid))->asarray()->all();?>
	       	<?php $excludeshipsouthasia=Helper_Array::toHashmap($excludeshipsouthasia,'location','description');?>
	       	<?php $htm=Html::checkboxList('shippingdetails[ExcludeShipToLocation]','', $excludeshipsouthasia,['itemOptions'=>['class'=>'excludeship']])?>
			<?php echo $htm;?>	
	      	</div>
		</div><hr>
		<div>
	       	<input type="checkbox" class="excludeship" >大洋洲 <a href="#fjs" onclick=$('#excludeshipoceania').slideToggle();>展开/收起</a> <br>
	       	<div id="excludeshipoceania" >
	       	<?php $excludeshipoceania=EbayExcludeshippinglocation::find()->where(array('region'=>'Oceania','siteid'=>$siteid))->asarray()->all();?>
	       	<?php $excludeshipoceania=Helper_Array::toHashmap($excludeshipoceania,'location','description');?>
	       	<?php $htm=Html::checkboxList('shippingdetails[ExcludeShipToLocation]','', $excludeshipoceania,['itemOptions'=>['class'=>'excludeship']])?>
			<?php echo $htm;?>
	       	</div>
		</div><hr>
		<div>
	       	<input type="checkbox" class="excludeship" >北美 <a href="#fjs" onclick=$('#excludeshipnorthamer').slideToggle();>展开/收起</a> <br>
	       	<div id="excludeshipnorthamer" >
	       	<?php $excludeshipnorthamer=EbayExcludeshippinglocation::find()->where(array('region'=>'North America','siteid'=>$siteid))->asarray()->all();?>
	       	<?php $excludeshipnorthamer=Helper_Array::toHashmap($excludeshipnorthamer,'location','description');?>
	       	<?php $htm=Html::checkboxList('shippingdetails[ExcludeShipToLocation]','', $excludeshipnorthamer,['itemOptions'=>['class'=>'excludeship']])?>
			<?php echo $htm;?>
	       	</div>
		</div><hr>
		<div>
	       	<input type="checkbox" class="excludeship" >中东 <a href="#fjs" onclick=$('#excludeshipma').slideToggle();>展开/收起</a> <br>
	       	<div id="excludeshipma" >
	       	<?php $excludeshipma=EbayExcludeshippinglocation::find()->where(array('region'=>'Middle East','siteid'=>$siteid))->asarray()->all();?>
	       	<?php $excludeshipma=Helper_Array::toHashmap($excludeshipma,'location','description');?>
	       	<?php $htm=Html::checkboxList('shippingdetails[ExcludeShipToLocation]','', $excludeshipma,['itemOptions'=>['class'=>'excludeship']])?>
			<?php echo $htm;?>
	       	</div>
		</div><hr>
		<div>
	       	<input type="checkbox" class="excludeship" >欧洲 <a href="#fjs" onclick=$('#excludeshipeur').slideToggle();>展开/收起</a> <br>
	       	<div id="excludeshipeur" >
	       	<?php $excludeshipeur=EbayExcludeshippinglocation::find()->where(array('region'=>'Europe','siteid'=>$siteid))->asarray()->all();?>
	       	<?php $excludeshipeur=Helper_Array::toHashmap($excludeshipeur,'location','description');?>
	       	<?php $htm=Html::checkboxList('shippingdetails[ExcludeShipToLocation]','', $excludeshipeur,['itemOptions'=>['class'=>'excludeship']])?>
			<?php echo $htm;?>
	       	</div>
		</div><hr>
		<div>
	       	<input type="checkbox" class="excludeship" >中美 <a href="#fjs" onclick=$('#excludeshipcaac').slideToggle();>展开/收起</a> <br>
	       	<div id="excludeshipcaac" >
	       	<?php $excludeshipcaac=EbayExcludeshippinglocation::find()->where(array('region'=>'Central America and Caribbean','siteid'=>$siteid))->asarray()->all();?>
	       	<?php $excludeshipcaac=Helper_Array::toHashmap($excludeshipcaac,'location','description');?>
	       	<?php echo Html::checkboxList('shippingdetails[ExcludeShipToLocation]','', $excludeshipcaac,['itemOptions'=>['class'=>'excludeship']])?>
	       	</div>
		</div><hr>
		<div>
	       <input type="checkbox" class="excludeship">亚洲 <a href="#fjs" onclick=$('#excludeshipasia').slideToggle();>展开/收起</a> <br>
	       <div id="excludeshipasia" >
	       <?php $excludeshipasia=EbayExcludeshippinglocation::find()->where(array('region'=>'Asia','siteid'=>$siteid))->asarray()->all();?>
	       <?php $excludeshipasia=Helper_Array::toHashmap($excludeshipasia,'location','description');?>
	       <?php echo Html::checkboxList('shippingdetails[ExcludeShipToLocation]','', $excludeshipasia,['itemOptions'=>['class'=>'excludeship']])?>
	       </div>
		</div><hr>
		<div>
	       <input type="checkbox" class="excludeship" >非洲 <a href="#fjs" onclick=$('#excludeshipafr').slideToggle();>展开/收起</a> <br>
	       <div id="excludeshipafr" >
	       <?php $excludeshipafr=EbayExcludeshippinglocation::find()->where(array('region'=>'Africa','siteid'=>$siteid))->asarray()->all();?>
	       <?php $excludeshipafr=Helper_Array::toHashmap($excludeshipafr,'location','description');?>
	       <?=Html::checkboxList('shippingdetails[ExcludeShipToLocation]','', $excludeshipafr,['itemOptions'=>['class'=>'excludeship']])?>
	       </div>
	    </div> 
	     &nbsp;&nbsp;
	    <input class="iv-btn btn-search" type="button" value="全选" onclick="$('.excludeship').prop('checked',true);"> &nbsp;&nbsp;  <input class="iv-btn btn-search" type="button" value="全不选" onclick="$('.excludeship').removeAttr('checked');">
	</div>
	<?php endif;?>
	
	<?php if (in_array('dispatchtime', $setitems) && is_array($dispatchtimemax) && $qubie['sitetmp'] == 1):?>
	<p class="title">包裹处理时间</p>
	<?php echo Html::dropDownList('dispatchtime','', $dispatchtimemax,['class'=>'iv-input main-input'])?>
	<?php endif;?>
</div>
<div class="main-area">
	<p class="mianbaoxie"><span class="mianbaoxietitle"></span>收货与退货</p>
	<?php if (in_array('paymentmethods', $setitems) && is_array($paymentoption) && $qubie['sitetmp'] == 1):?>
	<p class="title">收款方式</p>
	<?php echo Html::checkBoxList('paymentmethods','', $paymentoption)?>
	<?php endif;?>
	
	<?php if (in_array('autopay', $setitems)):?>
	<p class="title">立即付款</p>
	<?php echo Html::checkBox('autopay','',array('value'=>true))?>是否要求买家立即付款
	<?php endif;?>
	
	<?php if (in_array('paymentinstructions', $setitems)):?>
	<p class="title">付款说明</p>
	<div class="subdiv">
	<?php echo Html::textArea('PaymentInstructions','',array('rows'=>5,'cols'=>60))?>
	</div>
	<?php endif;?>
	
	<?php if (in_array('return_policy', $setitems) && $qubie['sitetmp'] == 1 && is_array($return_policy) && isset($return_policy['ReturnsAccepted'])):?>
	<p class="title">退货政策</p>
	<?php echo Html::dropDownList('return_policy[ReturnsAcceptedOption]','',Helper_Array::toHashmap($return_policy['ReturnsAccepted'],'ReturnsAcceptedOption','Description'),array('onchange'=>'return_policy_trigger(this)','class'=>'iv-input'))?>
	<div class="subdiv">
	<table>
	<?php if (isset($return_policy['Refund'])):?>
	<tr class="return_accepted_only">
		<th>退货方式</th>
	    <td>
	    <?php echo Html::dropDownList('return_policy[RefundOption]','',
	    		Helper_Array::toHashmap($return_policy['Refund'], 'RefundOption', 'Description'),['class'=>'iv-input'])?>
	    </td>
	</tr>
	<?php endif;?>
	<?php if (isset($return_policy['ReturnsWithin'])):?>
	<tr class="return_accepted_only">
		<th>接受退货天数</th>
	    <td>
	    <?php echo Html::dropDownList('return_policy[ReturnsWithinOption]', '',
	    		Helper_Array::toHashmap($return_policy['ReturnsWithin'], 'ReturnsWithinOption', 'Description'),['class'=>'iv-input'])?>
	    </td>
	</tr>
	<?php endif;?>
	<?php if (isset($return_policy['ShippingCostPaidBy'])):?>
	<tr class="return_accepted_only">
	    <th>退费承担</th>
	    <td>
	    <?php echo Html::dropDownList('return_policy[ShippingCostPaidByOption]','',
	    		Helper_Array::toHashmap($return_policy['ShippingCostPaidBy'], 'ShippingCostPaidByOption', 'Description'),['class'=>'iv-input'])?>
	    </td>
	</tr>
	<?php endif;?>
	<?php if (isset($return_policy['RestockingFeeValue'])):?>
	<tr class="return_accepted_only">
	    <th>RestockingFeeValue</th>
	    <td>
	    <?php echo Html::dropDownList('return_policy[RestockingFeeValue]','',
	    		Helper_Array::toHashmap($return_policy['RestockingFeeValue'], 'RestockingFeeValueOption', 'Description'),['class'=>'iv-input'])?>
	    </td>
	    </td>
	</tr>
	<?php endif;?>
	<?php if ($return_policy['Description']==true):?>
	<tr class="return_accepted_only">
	    <th>退货说明</th>
	    <td>
	    <?php echo Html::textarea('return_policy[Description]',@$data['return_policy']['Description'],array('rows'=>5,'cols'=>60))?>
	    </td>
	</tr>
	<?php endif;?>
	</table>
	</div>
	<?php endif;?>
</div>
<?=Html::hiddenInput('setitems',implode(',',$setitems))?>
<div class="btndo">
<?php echo Html::submitButton('确定',array('class'=>'iv-btn btn-search'))?>
<?php echo Html::button('取消',array('onclick'=>'window.close();','class'=>'iv-btn btn-default'))?>
</div>
</form>
</div>
</div>
