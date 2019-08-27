<?php 
use yii\helpers\Html;
?>
<script>
	$(function(){
		insertTrackJS.init();
	});
</script>
<style>
modalfooter{
	position:absolute;
	bottom:0;
	right:0;
}
.modal-body{
	width:580px;
	height:100%;
}
.impor_red{
	color:#ED5466 ;
	background:#F5F7F7;
	padding:0 3px;
	margin-right:3px;
}
.form-group{
	color:black;
	width:580px;
}
.radioListDIV{
	float:left;
}
.radioListDIV label+label{
	margin-left:25px;
}
.left-show{
	float:left;
	line-height:22px;
	width:130px;
	text-align:right;
	padding-right:15px;
}
.mytextArea{
	height:380px;
	width:450px;
}
.mytextArea-sm{
	height:380px;
	width:400px;
}
.modal-close{
	margin:0 30px 0 10px;
}

</style>
<div style="width:560px;height:550px;">
<form id="insertTrackFORM" style="width:560px;">
<div class="row form-group">
	<div class="left-show">
		<span class="impor_red">*</span>运输服务：
	</div>
	<?php echo '<input type="hidden" id="style" value="'.$_GET['style'].'">'; ?>
	<?= Html::dropdownlist('shipping_service_id','',$methods,['class'=>'iv-input','style'=>'width:200px;'])?>
</div>
<div class="row form-group">
	<div class="left-show">添加方式：</div>
	<?= Html::radiolist('insertType',0,['0'=>'手动添加','1'=>'excel导入','2'=>'扫描录入'],['class'=>'radioListDIV'])?>
</div>
<div id="insertDIV" style="width:560px;">
<!-- 手动添加 -->
<div class="row form-group" id="insertType0">
	<div class="left-show">跟踪号：</div>
	<textarea class="iv-input mytextArea" name="0[Text]" placeholder="一行一个跟踪号"></textarea>
</div>
<!-- excel导入 -->
<div class="row form-group" id="insertType1" hidden>
	<div class="form-group" style="margin-bottom: 10px;">
		<div class="left-show">请选择文件：</div>
		<input type="button" class="iv-btn btn-search" id="btn_import_product" value='导入'>
	</div>
	<div class="form-group">
		<div class="left-show">跟踪号：</div>
		<textarea class="iv-input mytextArea-sm" name="1[Text]" id="exceltext" placeholder="一行一个跟踪号"></textarea>
	</div>
</div>
<!-- 扫描录入 -->
<div class="row form-group" id="insertType2" hidden>
	<div style="margin-bottom: 10px;">
		<div class="left-show">扫描框：</div>
		<input class="iv-input" id="scanIn" style="width: 400px;" placeholder="将鼠标光标放在此框，然后用扫描枪扫描跟踪号条形码" value="" />
	</div>
	<div class="left-show">跟踪号：</div>
	<textarea class="iv-input mytextArea-sm" name="2[Text]" id="scanText" placeholder="一行一个跟踪号"></textarea>
</div>
</div>
</form>
<modalfooter>
	<button class="iv-btn btn-sm btn-search" id="saveInsert">保 存</button>
	<button class="iv-btn btn-sm modal-close">关 闭</button>
</<modalfooter>
</div>
