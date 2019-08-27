<?php 

use yii\helpers\Html;
use yii\widgets\LinkPager;
use eagle\widgets\SizePager;
use common\helpers\Helper_Siteinfo;
use common\helpers\Helper_Array;
use yii\helpers\Url;


$this->registerJsFile(\Yii::getAlias('@web')."/js/project/listing/fitmentshow.js", ['depends' => ['yii\web\JqueryAsset']]);
?>
<style>
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
.doaction2{
	padding:3px;
	width:140px;
	margin:5px 0px;
}
.table a{
	color:rgb(170,170,170);
}
</style>
<div class="tracking-index col2-layout">
<?=$this->render('../_ebay_leftmenu',['active'=>'汽配兼容']);?>
<div class="content-wrapper" >
<?php if (!empty($ebaydisableuserid)) {
	echo $this->render('../_ebaylisting_authorize',['ebaydisableuserid'=>$ebaydisableuserid]);
}
?>
	<div class="mianbaoxie">
		<span></span>详细范本列表
	</div>
<!-- 搜索 -->
<div class="form-group mutisearch">
<form action="" method="post" class="form-inline">
<?=Html::textInput('name',@$_REQUEST['name'],['class'=>'iv-input','placeholder'=>'范本名'])?>
<?=Html::dropDownList('site',@$_REQUEST['site'],['3'=>'UK','15'=>'Australia','77'=>'Germany','100'=>'eBayMotors'],['class'=>'iv-input','prompt'=>'平台'])?>	
<?=Html::submitButton('查询',['class'=>'iv-btn btn-search'])?>
</form>
<div>
<div class="table-action">
	<div class="pull-left">
	</div>
	<div class="pull-right">
	<?=Html::button('新范本',['class'=>'btn btn-warning doaction2','onclick'=>"doedit()"])?>
	<?=Html::button('从在线Item抓取',['class'=>'btn btn-warning doaction2','onclick'=>"getfromitem()"])?>
	</div>
</div>
<!-- datalist -->
<table class="table table-condensed">
	<tr>
		<th>范本ID</th><th>范本名</th><th>平台</th><th>类目</th><th>操作</th>
	</tr>
	<?php if (count($mubans)):foreach ($mubans as $muban):?>
	<tr>
		<td><?=$muban->id?></td>
		<td><?=$muban->name?></td>
		<td>
			<?php $site_arr = Helper_Array::toHashmap(Helper_Siteinfo::getEbaySiteIdList(), 'no', 'zh')?>
			<?=$site_arr[$muban->siteid]?>
		</td>
		<td><?=$muban->primarycategory?></td>
		<td>
			<a href="javascript:void(0);" onclick="window.open('<?=Url::to(['ebaycompatibility/edit','fid'=>$muban->id])?>')" target="_blank" title="编辑" class="dolink"><span class="iconfont icon-yijianxiugaiyouxiaoqi"></span></a>
			<span style="color:#ddd">|</span>
			<a href="javascript:void(0);" onclick="dodel('<?=$muban->id?>')" title="删除" class="dolink"><span class="iconfont icon-guanbi"></span></a>
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
</div>
</div>

<!-- 模态框（Modal）处理再现Item抓取fitment信息 -->
<div class="modal fade" id="getitemfitmentModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
   <div class="modal-dialog">
      <div class="modal-content">
	      <div class="modal-header">
	        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
	        <h4 class="modal-title">从在线Item抓取汽配信息</h4>
	      </div>
	      <div class="modal-body">
	      	<div class="form-inline">
	      	<label for="itemid">ItemID</label>
	      	<?=Html::textInput('itemid','',['class'=>'form-control'])?>
	      	<div id="getitemresult"></div>
	      	</div>
	        <div class="form-inline">
	      	<label for="mubanname">模板名&nbsp;</label>
	      	<?=Html::textInput('mubanname','',['class'=>'form-control'])?>
	      	</div>
	      	<?=Html::button('抓取',['class'=>'btn btn-primary','onclick'=>'ajaxgetfromitem()'])?>
	      </div>  
      </div><!-- /.modal-content -->
	</div><!-- /.modal -->
</div>