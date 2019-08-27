<?php

use yii\helpers\Html;
use yii\widgets\LinkPager;
use eagle\widgets\SizePager;
use yii\helpers\Url;
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
	width:150px;
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
<?=$this->render('../_ebay_leftmenu',['active'=>'销售信息范本']);?>
<div class="content-wrapper" >
<?php if (!empty($ebaydisableuserid)) {
	echo $this->render('../_ebaylisting_authorize',['ebaydisableuserid'=>$ebaydisableuserid]);
}
?>
<div class="table-action">
	<div class="pull-left">
	</div>
	<div class="pull-right">
	<?=Html::button('新建销售信息范本',['class'=>'btn btn-warning doaction2','onclick'=>"window.open('".Url::to(['/listing/ebaymuban/salesinfoedit'])."')"])?>
	</div>
</div>
<table class="table">
<tr>
	<th>范本名</th>
	<th>操作</th>
</tr>
<?php if (count($list)):foreach ($list as $l):?>
<tr>
	<td><?=$l->name?></td>
	<td>
	<a href="<?=Url::to(['/listing/ebaymuban/salesinfoedit','tid'=>$l->id])?>" target="_blank" title="编辑"><span class="iconfont icon-yijianxiugaiyouxiaoqi"></span></a>
	<span style="color:#ddd">|</span>
	<a href="javascript:void(0);" onclick="javascript:dodelete('<?=$l->id?>')" title="删除"><span class="iconfont icon-shanchu"></span></a></td>
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
function dodelete(id){
	$.post(global.baseUrl + 'listing/ebaymuban/salesinfodelete',{id:id},function(result){
		if(result=='success'){
			bootbox.alert("删除成功");
			window.location.reload();
		}else{
			bootbox.alert("删除失败");
		}
	});
}
</script>