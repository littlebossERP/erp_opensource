<?php 

use yii\helpers\Html;
?>
<style>
.modal .warning{
	margin:0px 5px;
	color:red;
}
.modal .success{
	margin:0px 5px;
	color:rgb(46,204,113);
}
.beizhu{
	color:rgb(148,148,148);
	margin-left:10px;
}
.modal .tishi{
	background-color:rgb(249,249,249);
	padding:15px 10px;
}
.price>p,.quantity>p{
	margin:10px 5px;
}
.iv-table.table-default2 tbody tr:nth-child(2n+1) {
  background-color: #ededed;
}

.nav-tabs{
	margin:5px 0px 40px 0px;
}
.nav-tabs > li > a{
	color: #9B9B9B;
}
.nav-tabs > li.active > a, .nav-tabs > li.active > a:hover, .nav-tabs > li.active > a:focus {
	color: #9B9B9B;
	border: 1px solid #D2D2D4;
	border-bottom-color: transparent;
}
.modaltable td{
	width:400px;
	padding:5px 0px;
}
.modaltable input{
	width:380px;
}
/**弹出选择目录的样式**/
	.modal-style{
		border:0px solid white !important;
	}
	.header-style{
		background-color:#374655 !important;
	}
	.header-style h4{
		color:#ffffff !important;
	}
</style>
<script>
//处理modal的标签页显示
function showtab(str,obj){
	//div的显示
	$('.modal-body>div').hide();
	$('.'+str).show();
	//tab的显示
	$('.modal-body>ul>li').removeClass();
	$(obj).parent().addClass('active');
}

//处理修改数值
function dosub(){
	//遍历每一行的tr
	$('#datalist tbody').find('tr').each(function(){
		//遍历每一个tr的td
		var sku = $(this).find('.c_sku').text();
		var title = $(this).find('.c_title').text();
		var price = $(this).find('.c_price').text();
		var quantity = $(this).find('.c_quantity').text();
		if(sku != ''){
			sku = valuechange(sku,'sku');
			$(this).find('.c_sku').text(sku);
		}
		if(title != ''){
			title = valuechange(title,'title');
			$(this).find('.c_title').text(title);
		}
		if(price != ''){
			price = valuechange(price,'price');
			$(this).find('.c_price').text(price);
		}
		if(quantity != ''){
			quantity = valuechange(quantity,'quantity');
			$(this).find('.c_quantity').text(quantity);
		}
	});
	localStorage.removeItem('qishizhi',null);
	$('#changeModal').modal('hide');
}

//批量修改的函数逻辑
//str:传入的需要处理的字符串
//obj:处理的对象，sku、标题、价格、库存
function valuechange(str,obj){
	var _tmp='';
	switch(obj){
		case 'sku':
			_tmp = str;
			var qianzhui = $('input[name=qianzhui]').val();
			var qishizhi = $('input[name=qishizhi]').val();
			var weishu = $('input[name=weishu]').val();
			var houzhui = $('input[name=houzhui]').val();
			if(weishu.length>0 && qishizhi.length>0){
				var _newtmp = '';
				if(isNaN(weishu) || isNaN(qishizhi)){
					bootbox.alert('起始值与位数必须是正整数');return _tmp;
				}
				if(parseInt(qishizhi)!=qishizhi || qishizhi<0 || parseInt(weishu)!=weishu || weishu<0){
					bootbox.alert('起始值与位数必须是正整数');return _tmp;
				}
				var newqishi = localStorage.getItem('qishizhi');
				if(newqishi == null){
					newqishi = qishizhi;
					localStorage.setItem('qishizhi',qishizhi);
				}else{
					newqishi = parseInt(newqishi)+1;
					localStorage.setItem('qishizhi',newqishi);
					newqishi = String(newqishi);
				}
				if(newqishi.length<weishu){
					for(var i=0;i<weishu-newqishi.length;i++){
						_newtmp = _newtmp + '0';
					}
					_newtmp = _newtmp + newqishi;
				}else{
					_newtmp = newqishi;
				}
				_tmp = _newtmp;
			}
			if(qianzhui.length>0){
				_tmp = qianzhui+_tmp;
			}
			if(houzhui.length>0){
				_tmp = _tmp+houzhui;
			}
			break;
		case 'title':
			_tmp = str;
			var kaitoutianjia = $('input[name=kaitoutianjia]').val();
			var jieweitianjia = $('input[name=jieweitianjia]').val();
			var biaotizhong = $('input[name=biaotizhong]').val();
			var tihuanwei = $('input[name=tihuanwei]').val();
			if(kaitoutianjia.length>0){
				_tmp = kaitoutianjia+_tmp;
			}
			if(jieweitianjia.length>0){
				_tmp = _tmp+jieweitianjia;
			}
			if(biaotizhong.length>0 && tihuanwei.length>0){
				_tmp = _tmp.replace(biaotizhong,tihuanwei);
			}
			break;
		case 'price':
			_tmp = str;
			var price_choose = $('input[name=price_choose]:checked').val();
			var price_type = $('select[name=price_type]').val();
			var pricevalue = $('input[name=pricevalue]').val();
			if(isNaN(pricevalue)){
				bootbox.alert('修改价格非数值,请输入数值');return _tmp;
			}
			var zhijiepricevalue = $('input[name=zhijiepricevalue]').val();
			if(isNaN(zhijiepricevalue)){
				bootbox.alert('直接修改价格输入值非数值,请输入数值');return _tmp;
			}
			
			if(price_choose == 1 && pricevalue.length>0){
				if(price_type == 'jine'){
					_tmp = parseFloat(parseFloat(_tmp)+parseFloat(pricevalue)).toFixed(2);
				}else if(price_type == 'baifenbi'){
					_tmp = parseFloat(_tmp*(1+pricevalue/100)).toFixed(2);
				}
			}else if(price_choose == 2 && zhijiepricevalue.length>0){
				_tmp = zhijiepricevalue;
			}
			break;
		case 'quantity':
			_tmp = str;
			var quantity_choose = $('input[name=quantity_choose]:checked').val();
			var quantityvalue = $('input[name=quantityvalue]').val();
			
			var zhijiequantityvalue = $('input[name=zhijiequantityvalue]').val();

			if(quantity_choose == 1 && quantityvalue.length>0){
				if(isNaN(quantityvalue)){
					bootbox.alert('修改库存非数值,请输入数值');return _tmp;
				}
				if(parseInt(quantityvalue) !=quantityvalue){
					bootbox.alert('修改库存非整数,请输入整数');return _tmp;
				}
				_tmp = parseInt(_tmp) + parseInt(quantityvalue);
			}else if(quantity_choose == 2 && zhijiequantityvalue.length>0){
				if(isNaN(zhijiequantityvalue)){
					bootbox.alert('直接修改库存非数值,请输入数值');return _tmp;
				}
				if(parseInt(zhijiequantityvalue) !=zhijiequantityvalue){
					bootbox.alert('直接修改库存非整数,请输入整数');return _tmp;
				}
				if(zhijiequantityvalue<0){
					bootbox.alert('直接修改库存需正整数,请输入正整数');return _tmp;
				}
				_tmp = zhijiequantityvalue;
			}
			break;
		default:
			break;
	}
	return _tmp;
}
</script>
<div class="modal-header header-style">
	<button type="button" class="close" data-dismiss="modal" aria-hidden="true">
    	&times;
    </button>
    <h4 class="modal-title" id="myModalLabel">
	批量修改
	</h4>
</div>
<div class="modal-body">
	<ul class="nav nav-tabs nav-justified">
		<li role="presentation" <?php if ($attr == 'sku' || $attr ==''){?>class="active"<?php }?>><a onclick="showtab('sku',this)">SKU</a></li>
		<li <?php if ($attr == 'title'){?>class="active"<?php }?>><a onclick="showtab('title',this)">产品标题</a></li>
		<li <?php if ($attr == 'price'){?>class="active"<?php }?>><a onclick="showtab('price',this)">零售价</a></li>
		<li <?php if ($attr == 'quantity'){?>class="active"<?php }?>><a onclick="showtab('quantity',this)">库存</a></li>
	</ul>
	<div class="sku"<?php if ($attr != 'sku' && $attr !=''){?>style="display: none;"<?php }?>>
		<p class="tishi"><span class="warning">注:批量修改后,变种信息将同步更改确定。</span>生成SKU示例如下:EB000008US  EB000009US  EB00010US EB00011US</p>
		<table class="modaltable">
		<tr><td>前缀</td><td>起始值</td></tr>
		<tr><td>
			<?=Html::textInput('qianzhui','',['class'=>'input iv-input','placeholder'=>'示例:EB'])?>
			</td>
			<td>
			<?=Html::textInput('qishizhi','',['class'=>'input iv-input','placeholder'=>'示例:8'])?>
			</td>
		</tr>
		<tr><td>位数</td><td>后缀</td></tr>
		<tr>
			<td>
				<?=Html::textInput('weishu','',['class'=>'input iv-input','placeholder'=>'示例:6'])?>
			</td>
			<td>
				<?=Html::textInput('houzhui','',['class'=>'input iv-input','placeholder'=>'示例:US'])?>
			</td>
		</tr>
		</table>
	</div>
	<div class="title" <?php if ($attr != 'title'){?>style="display: none;"<?php }?>>
		<table class="modaltable">
		<tr><td colspan="2">标题开头添加</td></tr>
		<tr>
			<td colspan="2">
			<?=Html::textInput('kaitoutianjia','',['class'=>'input iv-input','style'=>'width:765px;'])?>
			</td>
		</tr>
		<tr><td colspan="2">标题结尾添加</td></tr>
		<tr>
			<td colspan="2">
			<?=Html::textInput('jieweitianjia','',['class'=>'input iv-input','style'=>'width:765px;'])?>
			</td>
		</tr>
		<tr><td>标题中的</td><td>替换为</td></tr>
		<tr>
			<td>
				<?=Html::textInput('biaotizhong','',['class'=>'input iv-input'])?>
			</td>
			<td>
				<?=Html::textInput('tihuanwei','',['class'=>'input iv-input'])?>
			</td>
		</tr>
		</table>
	</div>
	<div class="price" <?php if ($attr != 'price'){?>style="display: none;"<?php }?>>
		<p class="tishi"><span class="success">提示:MSRP的值，基于价格的值进行变动</span><span class="warning">注:批量修改后,变种信息将同步更改。</span></p>
		<p><?=Html::radio('price_choose',true,['value'=>1])?>按<?=Html::dropDownList('price_type','',['jine'=>'金额','baifenbi'=>'百分比'],['class'=>'input iv-input'])?>增加<?=Html::textInput('pricevalue','',['class'=>'input iv-input'])?><span class="pricedanwei">美元</span><span class="beizhu">提示:如果减少,可输入负数</span></p>
		<p><?=Html::radio('price_choose','',['value'=>2])?><?=Html::textInput('zhijiepricevalue','',['class'=>'input iv-input'])?><span class="beizhu">提示:直接修改价格</span></p>
	</div>
	<div class="quantity" <?php if ($attr != 'quantity'){?>style="display: none;"<?php }?>>
		<p class="tishi"><span class="warning">注:批量修改后,变种信息将同步更改</span></p>
		<p><?=Html::radio('quantity_choose',true,['value'=>1])?>按现有库存量增加<?=Html::textInput('quantityvalue','',['class'=>'input iv-input'])?><span class="beizhu">提示:如果减少,可输入负数</span></p>
		<p><?=Html::radio('quantity_choose','',['value'=>2])?><?=Html::textInput('zhijiequantityvalue','',['class'=>'input iv-input'])?><span class="beizhu">提示:直接修改价格</span></p>
	</div>
</div>
<div class="modal-footer">
    <button type="button" class="btn btn-success" onclick="dosub()"> 确定</button>
	<button type="button" class="btn btn-default" data-dismiss="modal">关闭</button>
</div>