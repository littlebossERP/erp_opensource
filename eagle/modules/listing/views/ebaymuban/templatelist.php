<?php

use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\LinkPager;
use eagle\widgets\SizePager;
?>
<style>
.doaction{
	font-size:10px;
	border-width:0px;
	padding:3px;
	margin:0px 10px 10px 0px;
	color:rgb(102,102,102);
}
.doaction2{
	padding:3px;
	width:120px;
}
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
.quantity{
	margin:0px 3px;
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
</style>
<div class="tracking-index col2-layout">
<?=$this->render('../_ebay_leftmenu',['active'=>'商品信息模板']);?>
<div class="content-wrapper" >
<?php if (!empty($ebaydisableuserid)) {
	echo $this->render('../_ebaylisting_authorize',['ebaydisableuserid'=>$ebaydisableuserid]);
}
?>
<!-- 搜索 -->
<div>
<form action="" method="post" class="form-inline">
	<div class="mianbaoxie">
		<span></span>商品信息模板列表
	</div>
	<div class="mutisearch">
		<?=Html::textInput('title',@$_REQUEST['title'],['placeholder'=>'模板名称','class'=>"iv-input"])?>
		<?=Html::dropDownList('mubantype',@$_REQUEST['mubantype'],['0'=>'简单','1'=>'可视化'],['prompt'=>'模板类型','class'=>"iv-input"])?>
		<?=Html::submitButton('搜索',['class'=>'iv-btn btn-search'])?>
	</div>
</form>
</div>
<div class="table-action">
	<div class="pull-left">
	<button type="button" class='btn btn-default doaction' onclick="doalldelete()"><span class="iconfont icon-shanchu"></span>删除</button>
	</div>
	<div class="pull-right">
	<?=Html::button('新建简单模板',['class'=>'btn btn-warning doaction2','onclick'=>"window.open('".Url::to(['/listing/ebaymuban/templateedit'])."')"])?>
	<?php $puid = \Yii::$app->subdb->getCurrentPuid();?>
	<?php if ($puid<=5950):?>
	<!-- <?//=Html::button('新建可视化模板',['class'=>'btn btn-warning doaction2','onclick'=>"window.open('".Url::to(['/listing/ebay-template/edit'])."')"])?> -->
	<?php endif;?>
	</div>
</div>

<table class="table">
<tr>
	<th>
		<div id="ck_all" ck="2" style="float: left;">
			<input id="ck_0" class="ck_0" type="checkbox" onclick="checkall()">
		</div>
	</th>
	<th>缩略图</th>
	<th>风格标题</th>
	<th>类型</th>
	<th>操作</th>
</tr>
<?php if (count($list)):foreach ($list as $l):?>
<tr>
	<td><input type="checkbox" class="ck" name="id[]" value="<?=$l->id?>"></td>
	<td><img alt="<?=$l->title?>" src="<?=$l->pic?>" width="60px" height="60px"/></td>
	<td><?=$l->title?></td>
	<td>
	<?php echo $l->type=='1'?'可视化':'简单';?>
	</td>
	<td>
		<?php if ($l->type=='0'):?><!-- 简单模板编辑 -->
		<a href="<?=Url::to(['/listing/ebaymuban/templateedit','tid'=>$l->id])?>" target="_blank" title="编辑" class="dolink"><span class="iconfont icon-yijianxiugaiyouxiaoqi"></span></a>
		<?php else:?><!-- 可视化模板编辑 -->
		<a href="<?=Url::to(['/listing/ebay-template/edit','template_id'=>$l->id])?>" target="_blank" title="编辑" class="dolink"><span class="iconfont icon-yijianxiugaiyouxiaoqi"></span></a>
		<?php endif;?>
		<span style="color:#ddd">|</span><a href="javascript:void(0);" onclick="javascript:dodelete('<?=$l->id?>')" title="删除" class="dolink"><span class="iconfont icon-shanchu"></span></a>
	</td>
</tr>
<?php endforeach;endif;?>
</table>
<div class="btn-group" >
<?=LinkPager::widget([
    'pagination' => $pages,
]);
?>
</div>
<?=SizePager::widget(['pagination'=>$pages , 'pageSizeOptions'=>array( 5 , 20 , 50 , 100 , 200 ), 'class'=>'btn-group dropup'])?>
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
function dodelete(id){
	$.post(global.baseUrl + 'listing/ebaymuban/templatedelete',{id:id},function(result){
		if(result=='success'){
			bootbox.alert("删除成功");
			window.location.reload();
		}else{
			bootbox.alert("删除失败");
		}
	});
}
function doalldelete(){
	if($('.ck:checked').length==0){
    	bootbox.alert("请选择要操作的模板");return false;
    }
    idstr='';
	$('input[name="id[]"]:checked').each(function(){
		idstr+=','+$(this).val();
	});
	dodelete(idstr);
}
</script>