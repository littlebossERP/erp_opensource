<?php 

use yii\helpers\Html;
use yii\helpers\Url;
?>
<style>
.table th{
	text-align: center;
}
.table td{
	text-align: left;
}

table{
	font-size:12px;
}
.table>td{
color:#637c99;
}
.table>th{
color:#637c99;
}
.table>tbody a{
color:#337ab7;
}
.table>tr>th {
height: 35px;
vertical-align: middle;
}
.table>tr>td {
height: 35px;
vertical-align: middle;
}
span{
	cursor:pointer;
}
</style>
<div class="modal-header">
	<button type="button" class="close" data-dismiss="modal" aria-hidden="true">
    	&times;
    </button>
    <h4 class="modal-title" id="myModalLabel">
	搜索分类
	</h4>
</div>
<div class="modal-body">
	<?=Html::hiddenInput('siteid',$siteid,['id'=>'siteid'])?>
	<?=Html::hiddenInput('typ',$typ,['id'=>'typ'])?>
	<div>
	搜索&nbsp;&nbsp;<?=Html::textInput('query','',['size'=>50,'id'=>'query'])?>&nbsp;&nbsp;
	<?=Html::submitButton('搜索',['class'=>'btn btn-primary','onclick'=>'dosearch()'])?>
	</div>
	<br>
	<div>
		<table class="table">
			<tr><th>分类号</th><th>名称</th><th>接近度</th><th></th></tr>
		</table>
	</div>
</div>
<div class="modal-footer">
	<button type="button" class="btn btn-default" data-dismiss="modal">关闭</button>
</div>
<script>
function dosearch(){
	if($('#query').val() == ''){
		bootbox.alert('请输入关键字');return false;
	}
	var Url=global.baseUrl +'listing/ebaymuban/searchcategory';
	$.ajax({
        type : 'post',
        cache : 'false',
        data : {siteid:$('#siteid').val(),query:$('#query').val()},
		url: Url,
        success:function(response) {
            $_res = eval('('+response+')');
            if($_res.Ack != 'Success'){
				//提示接口错误
				var str = "<tr><td colspan='4'>api调用错误,请联系技术人员！</td></tr>";
				$('.table').append(str);
				return false;
            }else{
                //清除table中的旧数据
                $("table tr td").each(function(){
					$(this).remove();
                });
                var $_categorys = $_res.SuggestedCategoryArray.SuggestedCategory;
				//接口数据填充到表格
				for(x in $_categorys){
					var parentpath = typeof($_categorys[x].Category.CategoryParentName)=='array'?$_categorys[x].Category.CategoryParentName.join('>'):$_categorys[x].Category.CategoryParentName;
					var str = "<tr><td>"+$_categorys[x].Category.CategoryID+"</td><td>"+parentpath+">"+
					$_categorys[x].Category.CategoryName+"</td><td>"+$_categorys[x].PercentItemFound+"%</td><td><span onclick='sel("+$_categorys[x].Category.CategoryID+");'>选择</span></td></tr>";
					$('.table').append(str);
				}
				return false;
            }
        }
    });
}
//选择相应categoryid
function sel(categoryid){
	var obj = $('#typ').val();
	if(obj == 'primary'){
		var objstr = 'primarycategory';
	}else if(obj == 'second'){
		var objstr = 'secondarycategory';
	}
	$('#'+objstr).val(categoryid);
	$('#searchcategoryModal').modal('hide');
	if(obj == 'primary'){
		window.document.a.target='';
		window.document.a.submit();
	}
}
</script>