<?php
?>
<style>

.modal-box.inside-modal {
    width: 500px;
}
</style>
<div style="width: 500px;">


	<div class="alert alert-warning" role="alert" style="text-align: left;">
	  小老板会按照该平台商品信息，创建商品库里面的商品记录。<br>
	请填写该商品在商品库的SKU(商品唯一标识)，如果该商品已经存在，请填写对应存在的SKU，小老板会以该平台seller SKU/ASIN码/Product Id作为该商品的别名，保存和SKU的关系，后面再有此商品的销售，系统将会自动识别为已存在的商品SKU。
	</div>
	<form action="/order/order/generate-product">
	新商品SKU： <input name="sku" type="text" value=""/>
	<input name="itemid" type="hidden" value="<?=$itemid?>"/>
	</form>
</div>

