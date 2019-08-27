<?php

$this->registerJsFile ( \Yii::getAlias ( '@web' ) . "/js/project/siteUserGuide1.js");
$this->registerJs ( "site.userGuide1.init();", \yii\web\View::POS_READY );
?>
<form id="user-guide-1-form" class="form-inline" >
	<p>1.您是目前主要是通过那几个平台进行产品销售？</p>
	<div class="checkbox form-group">
		<label style="cursor: auto;"></label>
		<label><input type="checkbox" name="ebay" value="1">eBay</label>
	</div>
	<div class="checkbox form-group">
		<label style="cursor: auto;"></label>
		<label><input type="checkbox" name="amazon" value="1">Amazon</label>
	</div>
	<div class="checkbox form-group">
		<label style="cursor: auto;"></label>
		<label><input type="checkbox" name="aliexpress" value="1">AliExpress</label>
	</div>
	<div class="checkbox form-group">
		<label style="cursor: auto;"></label>
		<label><input type="checkbox" name="wish" value="1">Wish</label>
	</div>
	<div class="checkbox form-group">
		<label style="cursor: auto;"></label>
		<label><input type="checkbox" name="cdiscount" value="1">Cdiscount</label>
	</div>

	<p style="padding-top: 20px;">	2.您目前最需要在哪些环节中期待小老板给到您帮助？</p>
	<div class="checkbox form-group">
		<label style="cursor: auto;"></label>
		<label><input type="checkbox" name="catalog" value="2">商品管理</label>
	</div>
	<div class="checkbox form-group">
		<label style="cursor: auto;"></label>
		<label><input type="checkbox" name="listing" value="2">产品刊登</label>
	</div>
	<div class="checkbox form-group">
		<label style="cursor: auto;"></label>
		<label><input type="checkbox" name="order" value="2">订单管理</label>
	</div>
	<div class="checkbox form-group">
		<label style="cursor: auto;"></label>
		<label><input type="checkbox" name="delivery" value="2">发货打包</label>
	</div>
	<div class="checkbox form-group">
		<label style="cursor: auto;"></label>
		<label><input type="checkbox" name="inventory" value="2">仓储管理</label>
	</div>
	<div class="checkbox form-group">
		<label style="cursor: auto;"></label>
		<label><input type="checkbox" name="report" value="2">统计分析</label>
	</div>	
	<div class="checkbox form-group">
		<label style="cursor: auto;"></label>
		<label><input type="checkbox" name="orthers" value="2">其他</label>
	</div>	
		
</form>
