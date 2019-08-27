<?php

use yii\helpers\Html;
use yii\widgets\LinkPager;
use yii\helpers\Url;
?>
<style>
.dianpusearch span{
	vertical-align:middle;
}
.mianbaoxie{
	margin:10px 0px;
}
.mianbaoxie>span{
	border-color:rgb(1,189,240);
	border-width:0px 3px;
	border-style:solid;
}
.mutisearch{
	margin:10px 0px;
}
.table-action button{
	padding:3px;
	margin:0px 0px 10px 0px;
}
.table th:first-child{
	width:80px;
}
.table a{
	color:rgb(170,170,170);
}
tr>td:last-child{
	display:inline-block;
	vertical-align:center;
}
.dolink{
	padding:10px;
}
.doaction1{
	border-width:0px;
	padding:3px;
	margin:0px 10px 10px 0px;
	color:rgb(102,102,102);
}
</style>
<div class="tracking-index col2-layout">
<?=$this->render('../_ebay_leftmenu',['active'=>'刊登失败']);?>
<div class="content-wrapper" >
<!-- 搜索 -->
<div>
<form action="" method="post">
	<div class="dianpusearch form-inline">
		<span class="iconfont icon-dianpu"></span><?=Html::dropDownList('selleruserid',@$_REQUEST['selleruserid'],$ebayselleruserid,['class'=>"iv-input",'prompt'=>'我的eBay账号'])?>
		<button type="submit" class="iv-btn btn-search">
			GO	
		</button>
	</div>
	<div class="mianbaoxie">
		<span></span>定时队列列表
	</div>
	<div class="mutisearch">
		<?=Html::textInput('title',@$_REQUEST['title'],['placeholder'=>'刊登标题','class'=>"iv-input"])?>
		<?=Html::textInput('mubanid',@$_REQUEST['mubanid'],['placeholder'=>'刊登范本编号','class'=>"iv-input"])?>
		<?=Html::submitButton('搜索',['class'=>'iv-btn btn-search'])?>
	</div>
</form>
</div>
<div class="table-action">
	<div class="pull-left">
	<button type="button" class='btn btn-default doaction1' onclick='javascript:delall();'><span class="iconfont icon-shanchu"></span>删除</button>
	</div>
	<div class="pull-right">
	</div>
</div>
<table class="table .table-condensed">
<tr>
	<th><div id="ck_all" ck="2" style="float: left;">
			<input id="ck_0" class="ck_0" type="checkbox" onclick="checkall()">
		</div></th>
	<th>卖家账号</th>
	<th>刊登范本标题</th>
	<th>刊登时间</th>
	<th>刊登方式</th>
	<th>刊登范本编号</th>
</tr>
<?php if (count($sets)):foreach ($sets as $set):?>
<tr>
	<td><input type="checkbox" class="ck" name="log_id[]" value="<?=$set->logid?>"></td>
	<td><?=$set->selleruserid?></td>
	<td><b><?=$set->title?></b><br>
		<?=isset($set->detail)?$set->detail->description:''?>
	</td>
	<td><?=date('Y-m-d H:i:s',$set->createtime)?></td>
	<td><?=$set->method?></td>
	<td><?=$set->mubanid?></td>
</tr>
<?php endforeach;endif;?>
</table>
<?php echo LinkPager::widget(['pagination' => $pages]);?>
</div>
</div>
<script>
function checkall(){
	if($("#ck_all").attr("ck")=="2"){
		$(".ck").prop("checked","checked");
		$("#ck_all").attr("ck","1");
	}else{
		$(".ck").prop("checked",false);
		$("#ck_all").attr("ck","2");
	}
}

function delall(){
	var idstr='';
	$('input[name="log_id[]"]:checked').each(function(){
		idstr+=','+$(this).val();
	});
	if(idstr.length==0){
		bootbox.alert('请先选择要操作的设置');return false;
	}
	$.showLoading();
	$.post('<?=Url::to(['/listing/additemset/deletelog'])?>',{logid:idstr},function(r){
		$.hideLoading();
		if(r=='success'){
			bootbox.alert('操作已成功');
			window.location.reload();
		}else{
			bootbox.alert('操作失败'+r);
		}
	});
}
</script>