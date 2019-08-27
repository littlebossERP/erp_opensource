<?php

use yii\helpers\Url;
use yii\helpers\Html;
use eagle\modules\util\helpers\TranslateHelper;
use eagle\widgets\SizePager;
use eagle\modules\util\helpers\SysBaseInfoHelper;

$this->registerJsFile(\Yii::getAlias('@web').'/js/project/amazoncs/amazoncs.js',['depends' => ['yii\web\JqueryAsset']]);

$active = '';
$uid = \Yii::$app->user->id;
?>


<style>

.bold-dot{
    width: 8px;
    height: 8px;
    border-radius: 5px;
    display: inline-block;
    background-color: #666666;
}
.help-contents span{
	margin-bottom:5px;
}
span b {
	font-weight: bold;
	color:#01bdf0;
}
</style>

<div class="col2-layout col-xs-12">
	<?=$this->render('_leftmenu',[]);?>
	<div class="content-wrapper help-contents">
		<h4 style="margin-bottom: 10px;">关于 推荐商品 和 推荐商品分组</h4>
		<div style="font-size: 16px; display: inline-block;">
			<div style="float:left;clear:both;">
				<span style="float:left;">当用户通过小老板系统，向买家发送消息(站内信、邮件等)时，在销售平台允许的情况下，可以通过在小老板的查询物流页面中嵌入推荐/推销的商品的形式，进行营销。<br>设置了推荐商品和其所属分组，才能使该功能正常生效。</span>
			</div>
			
		</div>
		
		<h4 style="margin:20px 0px 10px 0px;clear:both;">设置须知</h4>
		<div style="font-size: 16px; display: inline-block;">
			<div style="float:left;clear:both;margin-top:10px;">
				<span class="bold-dot" style="margin:4px 10px 0px 20px;float:left;clear:both;"></span><span style="float:left;">多店铺须知：</span>
				<span style="margin-left:60px;float:left;clear:both;">一个分组只能对应一个店铺,因此如果用户绑定了多个店铺，不同的店铺之间推荐商品和分组不能共用，单同一店铺的多个分组之间可以共用商品。</span>
				<span style="margin-left:60px;float:left;clear:both;">模板选择推荐商品分组的时候，需要对应店铺。如果推荐商品和店铺不对应，最终页面打开的时候，推荐商品设置不会生效。</span>
			</div>
			
			<div style="float:left;clear:both;margin-top:10px;">
				<span class="bold-dot" style="margin:4px 10px 0px 20px;float:left;"></span><span style="float:left;">数量限制：</span>
				<span style="margin-left:60px;float:left;clear:both;">目前，一个推荐商品分组最多可以展示8个推荐商品（即使分组包含的商品超过8个）， 因此用户需要尽量精选。</span>
				<span style="margin-left:60px;float:left;clear:both;">可以设置多个推荐商品分组，应以不同类型的订单、买家，选择不同的分组，达到最佳效果。</span>
			</div>
		</div>
		
		<h4 style="margin:20px 0px 10px 0px;clear:both;">设置教程</h4>
		<div style="font-size: 16px; display: inline-block;">
			<div style="float:left;clear:both;margin-top:10px;">
				<span class="bold-dot" style="margin:4px 10px 0px 20px;float:left;"></span><span style="float:left;">图文教程：</span>
				<span style="margin-left:60px;float:left;clear:both;"><a href="<?=SysBaseInfoHelper::getHelpdocumentUrl('word_list_15_285.html')?>" target="_blank">查看图文教程</a></span>
			</div>
		</div>
		
	</div>
	<div class=""></div>
</div>