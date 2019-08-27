<?php 
use eagle\modules\listing\helpers\EbayListingHelper;
$this->registerJsFile(\Yii::getAlias('@web')."/js/project/listing/variation.js?v=".EbayListingHelper::$listingVer, ['depends' => ['yii\web\JqueryAsset']]);
// $this->registerJsFile(\Yii::getAlias('@web')."/js/project/listing/variation.js", ['depends' => ['yii\web\JqueryAsset']]);
?>
<br>
<style>
.form-group{
	width:90%;
	margin:5px 5%;
}
</style>
<div class="form-group">
<!-- 父窗口传递平台的值，如果没有，则显示没有选择平台，无法显示参考底价 -->
<input type="hidden" name="siteidbyp" id="siteidbyp"/>
<!-- 中间参数接受值，value和id -->
<input type="hidden" name="convertidv" id="convertidv"/>

	<form action="" method="post">
	<input name="nametmp" id="nametmp" type="hidden">
	<table class="iv-table mTop20" id="variation_table">
	<thead>
	<tr>
		<th>SKU</th>
		<th>数量</th>
		<th>价格</th>
		<th>
			<select name="v_productid_name" class="iv-input">
			<?php if (isset($product['upcenabled']) && $product['upcenabled'] == 'Required'){?>
			<option value="UPC" selected='true'>UPC</option>
			<?php }elseif (isset($product['isbnenabled']) && $product['isbnenabled'] == 'Required'){?>
			<option value="EAN" selected='true'>EAN</option>
			<?php }elseif (isset($product['eanenabled']) && $product['eanenabled'] == 'Required'){?>
			<option value="ISBN" selected='true'>ISBN</option>
			<?php }else{?>
				<option value="UPC">UPC</option>
				<option value="EAN">EAN</option>
				<option value="ISBN">ISBN</option>
			<?php }?>				
			</select>
		</th>
		<th>图片地址</th>
		<th>
			<span style="cursor: pointer;" onclick="add(this)" class="glyphicon glyphicon-plus" title="添加属性"></span>
		</th>
	</tr>
	</thead>
	<tbody>
	<tr>
		<td>
			<input name="sku[]" class="iv-input"  size="20">
            <label id="displaybp1" style="display:none;"></label>
		</td>
		<td><input required="required" class="iv-input" name="quantity[]" size="10"></td>
		<td><input required="required" class="iv-input" name="price[]" size="10"></td>
		<td><input required="required" class="iv-input" name="v_productid_val[]" size="10" value="Does not apply"></td>
		<td><input class="line_img iv-input" name="img[]"></td>
		<td>
			<span style="cursor: pointer;" onclick="deleteRow(this)" class="glyphicon glyphicon-minus" title="删除该sku组"></span>
		</td>
	</tr>
	</tbody>
	</table>
	<hr>
	<table>
	<tr>
		<td>
			<input type="button" value="增加属性项" onclick="addItem()" class="iv-btn btn-search">
		</td>
		<td>
			图片关联：<span id="assoc_pic_key"></span>
		</td>
	</tr>
	</table>
	<hr>
	<input type="button" value="保存" onclick="saveVariation(this)" class="iv-btn btn-search">
</form>
<hr>
	<table>
	<tr>
	<td>
		
	</td>
	<td>
	 	数量批量
	 	<select id="mulset_quantity_type" class="iv-input">
	 		<option value="=">=</option>
	 		<option value="+">+</option>
	 		<option value="-">-</option>
	 		<option value="*">*</option>
	 		<option value="/">/</option>
	 	</select>
	 	<input name="mulset_quantity" size="10" class="iv-input"><input type="button" value="批量设置数量" onclick="mulsetquantity()" class="iv-btn btn-search">&nbsp;
	 	价格批量
	 	<select id="mulset_price_type" class="iv-input">
	 		<option value="=">=</option>
	 		<option value="+">+</option>
	 		<option value="-">-</option>
	 		<option value="*">*</option>
	 		<option value="/">/</option>
	 	</select>
		<input name="mulset_price" size="10" class="iv-input"><input type="button" value="批量设置价格" onclick="mulsetprice()" class="iv-btn btn-search">&nbsp;
	</td>
	</table>
</div>
<style>
<!--
.line_img { 
	width:300px; 
 } 
-->
</style>