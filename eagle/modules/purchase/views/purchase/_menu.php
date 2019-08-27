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
				<span class="menu_label ">采购单</span>
				<i class="toggle cert cert-small cert-default down"></i>
			</a>
			<ul class="menu-lv-2" style="line-height:38px;">
				<li>
					<a href="/purchase/purchase/index" class="clearfix <?=((yii::$app->controller->id == 'purchase') && (yii::$app->controller->action->id == 'index'))?' active':''?>" onclick="$(this).next().toggle()">
						<span class="menu_label ">采购单列表 </span>
					</a>
				</li>
			</ul>
		</li>
		<li>
			<a class="clearfix " onclick="$(this).next().toggle()" style="line-height:38px;">
				<i class="iconfont icon-stroe"></i>
				<span class="menu_label ">采购建议</span>
				<i class="toggle cert cert-small cert-default down"></i>
			</a>
			<ul class="menu-lv-2" style="line-height:38px;">
				<li>
					<a href="/purchase/purchasesug/meet-order" class="clearfix <?=((yii::$app->controller->id == 'purchasesug') && (yii::$app->controller->action->id == 'meet-order'))?' active':''?>" onclick="$(this).next().toggle()">
						<span class="menu_label " qtipkey="meet-order-purchase">见单采购</span>
					</a>
				</li>
				<li>
					<a href="/purchase/purchasesug/sugindex" class="clearfix <?=((yii::$app->controller->id == 'purchasesug') && (yii::$app->controller->action->id == 'sugindex'))?' active':''?>" onclick="$(this).next().toggle()">
						<span class="menu_label " qtipkey="how-purchasesug-work">缺货采购建议</span>
					</a>
				</li>
				<li>
					<a href="/purchase/purchasesug/sugindexstrategies" class="clearfix <?=((yii::$app->controller->id == 'purchasesug') && (yii::$app->controller->action->id == 'sugindexstrategies'))?' active':''?>" onclick="$(this).next().toggle()">
						<span class="menu_label " qtipkey="how-purchasesug-work">备货采购建议</span>
					</a>
				</li>
				<li>
					<a href="/purchase/purchasesug/sug_strategies" class="clearfix <?=((yii::$app->controller->id == 'purchasesug') && (yii::$app->controller->action->id == 'sug_strategies'))?' active':''?>" onclick="$(this).next().toggle()">
						<span class="menu_label ">备货策略</span>
					</a>
				</li>
			</ul>
		</li>
		<li>
			<a class="clearfix " onclick="$(this).next().toggle()" style="line-height:38px;">
				<i class="iconfont icon-stroe"></i>
				<span class="menu_label ">产品供应商</span>
				<i class="toggle cert cert-small cert-default down"></i>
			</a>
			<ul class="menu-lv-2" style="line-height:38px;">
				<li>
					<a href="/purchase/supplier/index" class="clearfix <?=((yii::$app->controller->id == 'supplier') && (yii::$app->controller->action->id == 'index'))?' active':''?>" onclick="$(this).next().toggle()">
						<span class="menu_label ">供应商列表</span>
					</a>
				</li>
				<li>
					<a href="/purchase/supplier/pd-suppliers" class="clearfix <?=((yii::$app->controller->id == 'supplier') && (yii::$app->controller->action->id == 'pd-suppliers'))?' active':''?>" onclick="$(this).next().toggle()">
						<span class="menu_label ">查看报价</span>
					</a>
				</li>
			</ul>
		</li>
	</ul>	
</div>
</div>