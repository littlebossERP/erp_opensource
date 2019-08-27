<?php
use yii\helpers\Html;
use yii\helpers\Url;
use eagle\modules\util\helpers\TranslateHelper;

$this->registerJs("$.initQtip();" , \yii\web\View::POS_READY);
?>
<style>
.content-wrapper{
	margin-left: 20px; 
	margin-top: 10px;
}
</style>
<!-- 左侧标签快捷区域 -->
<div class="flex-row">
<div class="left_menu menu_v2" onload="$(this).bind_hideScroll();" style="overflow: hidden;">
	<ul class="menu-lv-1">
		<li>
			<a class="clearfix " onclick="$(this).next().toggle()" style="line-height:38px;">
				<i class="iconfont icon-stroe"></i>
				<span class="menu_label ">运营统计</span>
				<i class="toggle cert cert-small cert-default down"></i>
			</a>
			<ul class="menu-lv-2" style="line-height:38px;">
				<li>
					<a href="/statistics/profit/index" class="clearfix <?=((yii::$app->controller->id == 'profit') && (yii::$app->controller->action->id == 'index'))?' active':''?>" onclick="$(this).next().toggle()">
						<span class="menu_label ">利润计算</span>
					</a>
				</li>
				<li>
					<a href="/statistics/statistics/product-details" class="clearfix <?=((yii::$app->controller->id == 'statistics') && (yii::$app->controller->action->id == 'product-details'))?' active':''?>" onclick="$(this).next().toggle()">
						<span class="menu_label ">商品表现</span>
					</a>
				</li>
			</ul>
		</li>
		<li>
			<a class="clearfix " onclick="$(this).next().toggle()" style="line-height:38px;">
				<i class="iconfont icon-stroe"></i>
				<span class="menu_label ">订单统计</span>
				<i class="toggle cert cert-small cert-default down"></i>
			</a>
			<ul class="menu-lv-2" style="line-height:38px;">
				<li>
					<a href="/statistics/achievement/index" class="clearfix <?=((yii::$app->controller->id == 'achievement') && (yii::$app->controller->action->id == 'index'))?' active':''?>" onclick="$(this).next().toggle()">
						<span class="menu_label " >业绩汇总</span>
					</a>
				</li>
			</ul>
		</li>
		<li>
			<a class="clearfix " onclick="$(this).next().toggle()" style="line-height:38px;">
				<i class="iconfont icon-stroe"></i>
				<span class="menu_label ">产品统计</span>
				<i class="toggle cert cert-small cert-default down"></i>
			</a>
			<ul class="menu-lv-2" style="line-height:38px;">
				<li>
					<a href="/statistics/sales/index" class="clearfix <?=((yii::$app->controller->id == 'sales') && (yii::$app->controller->action->id == 'index'))?' active':''?>" onclick="$(this).next().toggle()">
						<span class="menu_label ">销售统计</span>
					</a>
				</li>
			</ul>
		</li>
	</ul>	
</div>
</div>

