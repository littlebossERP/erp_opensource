<?php 
use yii\helpers\Html;
use common\helpers\Helper_Siteinfo;
use eagle\models\EbayCategory;
?>

<?php
if($data['listingtype']=='Chinese'){?>
<!-- 拍卖设置部分 -->
	<div class="form-group">
		<label class="control-label col-lg-3">拍卖价</label>
		<div class="col-lg-5">
			<?php echo Html::textInput('startprice',$data['startprice'],array('class'=>'iv-input main-input'));?>
		  	该值必需≥0.01
			<?php echo Helper_Siteinfo::getSiteCurrency($data['siteid'])?>
		</div>
	</div>
	<div class="form-group">
		<label class="control-label col-lg-3">一口价</label>
		<div class="col-lg-5">
			<?php echo Html::textInput('buyitnowprice',$data['buyitnowprice'],array('class'=>'iv-input main-input'));?>
		  	该值必需≥0.01
			<?php echo Helper_Siteinfo::getSiteCurrency($data['siteid'])?>
		</div>
	</div>
		<?php }else{?>

<!-- 一口价设置部分 -->
	<div class="form-group">
		<label class="control-label col-lg-3">一口价</label>
		<div class="col-lg-5">
		<?php echo Html::textInput('buyitnowprice',$data['buyitnowprice'],array('class'=>'iv-input main-input'));?>
		<?php echo Helper_Siteinfo::getSiteCurrency($data['siteid'])?>
		<?php $category = EbayCategory::findOne(['categoryid'=>$data['primarycategory'],'siteid'=>$data['siteid'],'leaf'=>'1'])?>
		</div>
	</div>
		<?php if(!empty($category)&&$category->bestofferenabled==1){?>
	<div class="form-group">
		<label class="control-label col-lg-3">议价</label>
			<div class="col-lg-5">
			<div class="whole-onebox">
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
			</div>
	</div>
		<?php }?>
		<?php }?>
