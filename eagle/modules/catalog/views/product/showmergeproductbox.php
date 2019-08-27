<?php
use eagle\modules\util\helpers\TranslateHelper;
?>
<style>
.modal-box{
	width: 500px;
}
.on_merge_sku_group{
	clear:both;
	margin-top:5px;
	height:65px;
	overflow-y:auto;
	border:1px solid #CFD9DB;
	background-color:#fff;
	padding:5px;
	min-height:20px;
}
.skuDivCont {
    border: 1px solid #ccc;
    padding: 0 5px;
    border-radius: 4px;
    font-size: 12px;
    background-color: #fff;
    margin-right: 10px;
	line-height: 18px;
}
.removeSku{
	margin: 5px 0px 0px 5px;
	cursor: pointer;
}
</style>

<p style="color: #a2a2a2; font-size: 14px; line-height: 18px">
	当商品库存在两个以上一样商品但不同SKU时，则可通过合并商品操作，只保留一个。<br>
	合并后，所有被合并商品的所有相关操作合并到目标商品中！（如订单，发货，库存等等）。<br>
	<span style="color: red;">注意：合并后，被合并商品的原数据将被替换或删除，且此操作不可逆</span>
</p>

<div style="margin-top: 20px;">
	<div class="col-md-12">
		<div class="form-group"">
			<label style="float: left; width: 20%; line-height: 30px; "><span class="text-danger mr5">*</span>被合并SKU</label>
			<div class="col-sm-9">
				<div class="input-group" style="width: 100%;">
					<input id="add_sku_val" placeholder="输入SKU" type="text" value="" class="eagle-form-control" style="width:60%; float:left; margin:0px; height:28px; ">
					<button type="button" class="iv-btn btn-search btn-spacing-middle" onclick="productList.list.addSkuTab()" style="float:left; margin-top:1px; ">添加</button>
				</div>
				<div class="on_merge_sku_group">
				</div>
			</div>
		</div>
		<div class="form-group">
			<label style="float: left; width: 20%; line-height: 30px; "><span class="text-danger mr5">*</span>目标本地SKU</label>
			<div class="col-sm-9">
				<div class="input-group" style="width: 100%;">
					<input id="merge_sku" placeholder="输入SKU" type="text" value="" class="eagle-form-control" style="width:100%; float:left; height:28px; ">
				</div>
			</div>
		</div>
	</div>
</div>

<div>
    <input type="button" id="btn_cancel" style="float: right;" class="iv-btn btn-primary modal-close" data-dismiss="modal" value="关闭" />
    <input type="button" id="btn_merge" style="float: right; margin-right: 20px;" class="iv-btn btn-primary" data-dismiss="modal" value="确认合并" onclick="productList.list.mergeProduct()"/>
        	
</div>






