<style>
#result-info li{
	margin-bottom:5px;
	line-height: 1.2em;
}
.progress{
	padding:0 10px;
}
#move-progress progress{
	width:calc( 100% - 80px );
	float:right;
}
</style>

<div id="move-progress" style="min-width:650px;">
	<div class="progress" style="line-height:1.5;">
		搬家进度：<progress></progress>
	</div>
	<div class="clearfix" style="border-bottom:1px solid #ccc;padding-bottom:5px;">
		<p class="pull-left">正在搬运SKU为 <span id="sku-view"></span> 的商品</p>
		<p class="pull-right"> 
			<span id="now"></span> / 
			<span id="total"></span>
		</p>
	</div>
	<div style="height:250px;overflow-y:auto;margin-top:10px;">
		<ul id="result-info">
			<li style="color:red;">错误信息:</li>
		</ul>
	</div>
	<div style="text-align:center;">
		<input type="button" class="iv-btn btn-success modal-close" value="搬运完成" style="display:none;" />
	</div>
</div>