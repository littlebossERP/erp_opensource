<?php

use yii\helpers\Url;
use yii\helpers\Html;
use eagle\modules\util\helpers\TranslateHelper;
use eagle\widgets\SizePager;
use eagle\modules\amazon\apihelpers\AmazonApiHelper;

$this->registerJsFile(\Yii::getAlias('@web').'/js/project/amazoncs/amazoncs.js',['depends' => ['yii\web\JqueryAsset']]);

$active = '';
$uid = \Yii::$app->user->id;

$MARKETPLACE_DOMAIN = AmazonApiHelper::$AMAZON_MARKETPLACE_DOMAIN_CONFIG;
?>


<style>
.table td hr{
	margin:0px;
	border-top: 1px solid rgb(196,196,196);
}
.star_icon{
	background-image: url(<?=\Yii::getAlias('@web').'/images/amazoncs/amazon_star_rating.png' ?>);
    background-repeat: no-repeat;
    -webkit-background-size: 344px 15px;
    background-size: 344px 15px;
    display: inline-block;
    vertical-align: top;
	width: 80px;
    height: 16px;
}
.a_star_5{
	background-position: 0px 0;
}
.a_star_4{
	background-position: -16px 0;
}
.a_star_3{
	background-position: -32px 0;
}
.a_star_2{
	background-position: -47px 0;
}
.a_star_1{
	background-position: -64px 0;
}
</style>

<div class="col2-layout col-xs-12">

	<?=$this->render('_leftmenu',[]);?>
	
	<div class="content-wrapper">
		<form class="form-inline" id="form1" name="form1" action="/amazoncs/amazoncs/review-list" method="post" style="margin-bottom:10px;">
			<input type="hidden" name="platform" value="amazon" >
			
			
			<select name="seller_site" class="form-control" id="seller_site" style="width:150px;margin:0px;">
				<option value="">卖家账号&站点</option>
				<?php foreach ($seller_site_list as $seller_site=>$name){?>
				<option value="<?=$seller_site?>" <?=($seller_site==@$_REQUEST['seller_site'])?'selected':''?> ><?=$name?></option>
				<?php } ?>
			</select>
			
			<?php $rating_arr = [
	        	'5'=>'5星',
				'4'=>'4星',
				'3'=>'3星',
				'2'=>'2星',
				'1'=>'1星',
			]?>
			<?=Html::dropDownList('rating',@$_REQUEST['rating'],$rating_arr,['class'=>'iv-input','prompt'=>'星级','style'=>'width:150px;margin:0px'])?>
			<?=Html::textInput('asin',@$_REQUEST['asin'],['class'=>'iv-input','placeholder'=>'ASIN','style'=>'width:150px'])?>
			<?=Html::textInput('author',@$_REQUEST['author'],['class'=>'iv-input','placeholder'=>'留评者','style'=>'width:150px'])?>
			
			<?=Html::submitButton('搜索',['class'=>"iv-btn btn-search btn-spacing-middle",'id'=>'search'])?>
		</form>
		
		<div class="input-group" style="float:left;width:100%;margin:5px 0px;">
			
		</div>
		
		<div>
			<table class="table">
				<tr>
					<th width="1%" style="">
						<input id="ck_all" class="ck_0" type="checkbox" onchange="selected_switch()">
					</th>
					<th>店铺(站点)</th>
					<th><i title="点击进行排序"><?=$sort->link('asin',['label'=>'ASIN']) ?></i></th>
					<th><i title="点击进行排序"><?=$sort->link('rating',['label'=>TranslateHelper::t('星级')]) ?></i></th>
					<th>留评者</th>
					<th>对应订单<span qtipkey="match_amz_review_for_order"></span></th>
					<th>Review内容</th>
					<th><i title="点击进行排序"><?=$sort->link('create_time',['label'=>TranslateHelper::t('创建时间')]) ?></i></th>
					<!-- <th>操作</th> -->
				</tr>
				<?php if(!empty($review_list)): foreach ($review_list as $index=>$review){
				?>
				<tr>
					<td>
						<label><input type="checkbox" class="ck" name="feedback_id[]" value="<?=$review->review_id?>" ></label>
					</td>
					<td>
						<span><?=empty($MerchantId_StoreName_Mapping[$review->merchant_id])?$review->merchant_id:$MerchantId_StoreName_Mapping[$review->merchant_id]?></span> 
						<br>
						<i style="font-style:italic;letter-spacing:1px;font-weight:600;">(<?=empty($MarketPlace_CountryCode_Mapping[$review->marketplace_id])?$review->marketplace_id:$MarketPlace_CountryCode_Mapping[$review->marketplace_id]?>)</i>
					</td>
					<td>
					<?php
					$this_site = empty($MarketPlace_CountryCode_Mapping[$review->marketplace_id])?'':$MarketPlace_CountryCode_Mapping[$review->marketplace_id];
					if(!empty($this_site) && !empty($MARKETPLACE_DOMAIN[$this_site]) && !empty($review->asin))
						$review_url= "http://".$MARKETPLACE_DOMAIN[$this_site]."/gp/product/".$review->asin;
					if(!empty($review_url))
						echo "<a href='".$review_url."' target='_blank'>"
					?>
					<?=$review->asin?>
					<?=empty($review_url)?'':'</a>'?>
					</td>
					<td><span class="star_icon a_star_<?=(int)$review->rating?>" tatle="<?=$review->rating ?>星"></span></td>
					<td><?=$review->author ?></td>
					<td>
					<?php if(!empty($review->order_source_order_id)){
						$source_order_id_arr = explode(',', $review->order_source_order_id);
						if(!empty($source_order_id_arr)){
							foreach ($source_order_id_arr as $index=>$order_id){
								echo '<a onclick="amazoncs.QuestList.showOrderInfo(\'amazon\',\''.$order_id.'\')">';
								echo $order_id;
								echo "</a>";
								if((int)$index<count($source_order_id_arr))
									echo "<br>";
							}
						}
						
					}?>
					
					</td>
					<td><?=base64_decode($review->review_comments) ?></td>
					<td><?=date('Y-m-d H\h',$review->create_time)?></td>
					<!-- <td></td> -->
				</tr>
				<tr style="background-color: #d9d9d9;">
					<td colspan="10" class="row" style="word-break:break-all;word-wrap:break-word;border:1px solid #d1d1d1;height:5px;padding:0px;"></td>
				</tr>
				<?php }endif; ?>
			</table>
			<?php if(! empty($pagination)):?>
			<div>
			    <?= \eagle\widgets\SizePager::widget(['pagination'=>$pagination, 'pageSizeOptions'=>array( 20 , 50 , 100) , 'class'=>'btn-group dropup']);?>
			    <div class="btn-group" style="width: 49.6%;text-align: right;">
			    	<?=\yii\widgets\LinkPager::widget(['pagination' =>$pagination,'options'=>['class'=>'pagination']]);?>
				</div>
			</div>
			<?php endif;?>
		</div>
	</div>
	<div class="order_info"></div>
</div>

<script>
function selected_switch(){
	var checked = $("#ck_all:checked").length;
	if(checked){
		$(".ck").each(function(){
			$(this).prop("checked",true);
		});
	}else{
		$(".ck").each(function(){
			$(this).prop("checked",false);
		});
	}	
	
}
</script>