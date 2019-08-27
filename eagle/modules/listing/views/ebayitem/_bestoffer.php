<?php 

use eagle\models\EbayCategory;
use yii\helpers\Html;
use common\helpers\Helper_Siteinfo;
?>
<?php $category = EbayCategory::findOne(['categoryid'=>$data['primarycategory'],'siteid'=>$data['siteid'],'leaf'=>'1']);?>
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