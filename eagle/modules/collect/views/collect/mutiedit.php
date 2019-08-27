<?php

use yii\helpers\Url;
use yii\helpers\Html;
?>
<?=$this->render('_ebay_leftmenu',['active'=>'eBay草稿箱']);?>
<style>
#datalist>thead>tr>th{
	border-radius:0px;
	border-right:1px solid rgb(153,153,153);
	height:30px;
}
.mianbaoxie{
	margin:10px 0px;
}
.mianbaoxie>span{
	border-color:rgb(1,189,240);
	border-width:0px 3px;
	border-style:solid;
}
.doaction{
	border-width:0px;
	padding:3px;
	margin:0px 10px 10px 0px;
	color:ddd;
	width:120px;
}
#subtitle{
	background-color:#fff;
}
#subtitle a{
	color:rgb(0,107,255);
}
#subtitle span{
	color:rgb(153,153,153);
}
.modal-dialog{
	width:800px;
}
</style>
<div>
	<div class="mianbaoxie">
		<span></span>批量修改预览列表
	</div>
	<div class="table-action">
	<div class="pull-left">
		<button class="btn btn-success doaction" onclick="dochange('')">批量修改</button>
		<button class="btn btn-success doaction" onclick="window.location.href='<?=Url::to(['/collect/collect/ebay'])?>'">返回草稿箱</button>
		<button class="btn btn-success doaction" onclick="dobackup('all')">还原</button>
	</div>
	<div class="pull-right">
		<button class="btn btn-warning doaction" onclick="dosave()">保存</button>
	</div>
</div>
<table class="iv-table table-default2" id="datalist">
    <thead>
        <tr>
            <th style="width:100px;height:30px;" rowspan="2">图片</th>
            <th style="width:150px;height:30px;">SKU
            </th>
            <th>标题</th>
            <th style="width:150px;">价格</th>
            <th style="width:150px;">库存</th>
            <th style="width:80px;" rowspan="2">操作</th>
        </tr>
        <tr id="subtitle">
            <th>
            	<a onclick="dochange('sku')">修改</a> <span>|</span> <a onclick="dobackup('sku')">还原</a>
            </th>
            <th>
            	<a onclick="dochange('title')">修改</a> <span>|</span> <a onclick="dobackup('title')">还原</a>
            </th>
            <th>
            	<a onclick="dochange('price')">修改</a> <span>|</span> <a onclick="dobackup('price')">还原</a>
            </th>
            <th>
            	<a onclick="dochange('quantity')">修改</a> <span>|</span> <a onclick="dobackup('quantity')">还原</a>
            </th>
        </tr>
    </thead>
    <?php if (count($mubans)):?>
    <tbody>
    <?php foreach ($mubans as $muban):?>
    <tr id="<?=$muban->mubanid?>">
    	<td>
    		<img src="<?=!empty($muban->mainimg)&&strlen($muban->mainimg)?$muban->mainimg:'http://v2-test.littleboss.cn/images/batchImagesUploader/no-img.png'?>">
    	</td>
    	<td>
    		<span data-sku="<?=$muban->sku?>" class="c_sku"><?=$muban->sku?></span>
    	</td>
    	<td>
    		<span data-title="<?=$muban->itemtitle?>" class="c_title"><?=$muban->itemtitle?></span>
    	</td>
    	<td>
    		<span data-price="<?=$muban->startprice?>" class="c_price"><?=$muban->startprice?></span>
    	</td>
    	<td>
    		<span data-quantity="<?=$muban->quantity?>" class="c_quantity"><?=$muban->quantity?></span>
    	</td>
    	<td>
    		<button class="btn btn-primary" onclick="$(this).parent().parent().remove()">移除</button>
    	</td>
    </tr>
    <?php endforeach;?>
    </tbody>
    <?php endif;?>
</table>
</div>

<!-- 批量修改的modal -->
<!-- 模态框（Modal） -->
<div class="modal fade" id="changeModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
   <div class="modal-dialog" role="document">
      <div class="modal-content modal-style">
         
      </div><!-- /.modal-content -->
	</div><!-- /.modal -->
</div>

<script>
function dochange(str){
	$('#changeModal').modal('show');

	$.showLoading();
	var Url='<?=Url::to(['/collect/collect/mutieditoption'])?>';
	$.ajax({
        type : 'post',
        cache : 'false',
        data : {attr:str},
		url: Url,
        success:function(response) {
            $.hideLoading();
        	$('#changeModal .modal-content').html(response);
        	$('#changeModal').modal('show');
        }
    });
}

//批量还原
function dobackup(str){
	$.showLoading();
	if(str == 'all'){
		var _tmparr = ['sku','title','quantity','price'];
	}else{
		var _tmparr = [str];
	}
	$('#datalist tbody').find('tr').each(function(){
		for(var i=0;i<_tmparr.length;i++){
			_arrkey = _tmparr[i];
			$(this).find('.c_'+_arrkey).text($(this).find('.c_'+_arrkey).data(_arrkey));
		}
	});
	$.hideLoading();
}

//保存批量修改
function dosave(){
	$.showLoading();
	var result = '';
	$('#datalist tbody').children().each(function(index){
		var id = $(this).attr('id');
		var sku = $(this).find('.c_sku').text();
		var title = $(this).find('.c_title').text();
		var price = $(this).find('.c_price').text();
		var quantity = $(this).find('.c_quantity').text();

		$.post(global.baseUrl+"collect/collect/mutieditsave",{id:id,sku:sku,title:title,price:price,quantity:quantity},function(r){
			if(r=='success'){
				result = result+'ID:'+id+'保存成功'+"\n";
			}else{
				result = result+'ID:'+id+'保存失败'+"\n";
			}
		});
	});
	bootbox.alert('操作已完成');
	$.hideLoading();
}
</script>