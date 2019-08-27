<style>
.my-group{
	width:100%;
	height:22px;
}
.my-group>label{
	float:left;
	width:150px;
	text-align:right;
	line-height: 2;
	padding-right:5px;
}
.my-group>input{
	float:left;
}
</style>
<div class="form-group my-group">
	<label>中文报关名</label><input type="text" class="iv-input" id="customsName" name="customsName" style="width:300px" placeholder="">
</div>
<div class="form-group my-group">
	<label>英文报关名</label><input type="text" class="iv-input" id="customsEName" name="customsEName" style="width:300px" placeholder="">
</div>
<div class="form-group my-group">
	<label>报关价值</label><input type="text" class="iv-input" id="customsDeclaredValue" name="customsDeclaredValue" style="width:300px" placeholder="">
</div>
<div class="form-group my-group">
	<label>重量g</label><input type="text" class="iv-input" id="customsweight" name="customsweight" style="width:300px" placeholder="">
</div>
<div class="form-group col-sm-12 text-center">
	<input type="checkbox" id="chk_isEditToSku" name="chk_isEditToSku">
	<label for="chk_isEditToSku">是否更改到商品SKU</label>
</div>
<div class="form-group col-sm-12 text-center">
	<button type="button" class="iv-btn btn-lg btn-primary" onclick="saveCustomsInfo(this)"> 保存</button>
	<input type="button" class="iv-btn btn-lg btn-success modal-close" value="关 闭">
</div>

