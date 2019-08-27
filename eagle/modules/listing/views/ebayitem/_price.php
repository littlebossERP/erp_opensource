<?php 
use yii\helpers\Html;
use common\helpers\Helper_Siteinfo;
?>

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
<?php if(empty($data['variation'])):?>
<p class="title">一口价</p>
<?php echo Html::textInput('startprice',$data['startprice'],array('class'=>'iv-input main-input'));?>
<?php echo Helper_Siteinfo::getSiteCurrency($data['siteid'])?>
<?php endif;?>
<?php }?>