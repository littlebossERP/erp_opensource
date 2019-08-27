<?php 
use yii\helpers\Html;
use common\helpers\Helper_Siteinfo;
use eagle\models\EbayCategory;
?>
<p class="title">刊登天数</p>
<?php echo Html::dropDownList('listingduration',$data['listingduration'],Helper_Siteinfo::getListingDuration($data['listingtype']),['class'=>'iv-input'])?>
<?php 
if($data['listingtype']=='Chinese'){?>
<!-- 拍卖设置部分 -->
<p class="title">拍卖价</p>
<?php echo Html::textInput('startprice',$data['startprice'],array('class'=>'iv-input main-input'));?>
  	该值必需≥0.01
<?php echo Helper_Siteinfo::getSiteCurrency($data['siteid'])?>
<p class="title">一口价</p>
<?php echo Html::textInput('buyitnowprice',$data['buyitnowprice'],array('class'=>'iv-input main-input'));?>
  	该值必需≥0.01
<?php echo Helper_Siteinfo::getSiteCurrency($data['siteid'])?>
<?php }else{?>
<!-- 一口价设置部分 -->
<p class="title">一口价</p>
<?php echo Html::textInput('buyitnowprice',$data['buyitnowprice'],array('class'=>'iv-input main-input'));?>
<?php echo Helper_Siteinfo::getSiteCurrency($data['siteid'])?>
<?php $category = EbayCategory::findOne(['categoryid'=>$data['primarycategory'],'siteid'=>$data['siteid'],'leaf'=>'1'])?>
<?php if(!empty($category)&&$category->bestofferenabled==1){?>
<p class="title">议价</p>
	<div class="subdiv">
  	<table>
		<tr>
		<td colspan="2">
		<?php echo Html::radioList('bestoffer',$data['bestoffer'],array('1'=>'开启','0'=>'关闭'));?>
		</td>
		</tr>
		<tr>
		<th>自动交易价格</th>
		<td>
		<?php echo Html::textInput('bestofferprice',$data['bestofferprice'],array('size'=>8,'class'=>'iv-input'));?>
		<?php echo Helper_Siteinfo::getSiteCurrency($data['siteid'])?>
		</td>
		</tr>
		<tr>
		<th>自动拒绝价格</th>
		<td>
		<?php echo Html::textInput('minibestofferprice',$data['minibestofferprice'],array('size'=>8,'class'=>'iv-input'));?>
		<?php echo Helper_Siteinfo::getSiteCurrency($data['siteid'])?></td>
		</tr>
	</table>
	</div>
<?php }?>
<?php }?>