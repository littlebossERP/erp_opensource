<?php
use yii\helpers\Html;
use yii\helpers\Url;
use eagle\modules\util\helpers\ExcelHelper;
use common\helpers\Helper_Array;
use eagle\modules\order\models\Excelmodel;

$this->title='编辑自定义导出范本';
$this->params['breadcrumbs'][] = $this->title;
?>
<form class="form-inline" method="post" action="" onsubmit="return docheck()">
<?php if (!empty($model)&&$model->id>0):?>
<?=Html::hiddenInput('mid',$model->id)?>
<?php endif;?>
<div style="height:420px">
	<label>范本名称</label>
	<?=Html::textInput('modelname',$model->name,['class'=>'form-control'])?>
	<br>
	<div class="col col-md-3">
	<?php 
		$showselect_key = explode(',',$model->content);
		$showselect_key = array_filter($showselect_key);
		$showselect_tmp = array_flip($showselect_key);
		$showselect = array_intersect_key(ExcelHelper::$content,$showselect_tmp);
		$showselect = array_merge($showselect_tmp,$showselect);
	?>
	<?=Html::dropDownList('source',array_flip($showselect),ExcelHelper::$content,['multiple'=>'multiple','size'=>'20','id'=>'source'])?>
	</div>
	<div  style="margin-top: 60px" class="col col-md-1">
		<span style="width:80px;display:" id="adddiv">
		   <?=Html::button('添加',['id'=>'add','style'=>'width:80px','onclick'=>"javascript:go()"])?>
		   <br/><br/>
		   <?=Html::button('全部添加',['id'=>'alladd','style'=>'width:80px','onclick'=>"javascript:allgo()"])?>
		 </span><br/><br/>
		 <span style="width:80px;display:" id="deldiv">
		 	<?=Html::button('全部删除',['id'=>'alldel','style'=>'width:80px','onclick'=>"$('#to').empty()"])?>
		   <br/><br/>
		   <?=Html::button('删除',['id'=>'del','style'=>'width:80px','onclick'=>"javascript:dele()"])?>
		 </span><br><br/>
		 <span style="width:80px;display:" id="movediv">
		 	<?=Html::button('上移',['id'=>'up','style'=>'width:80px','onclick'=>"moveup()"])?>
		   <br/><br/>
		   <?=Html::button('下移',['id'=>'down','style'=>'width:80px','onclick'=>"movedown()"])?>
		 </span>
	</div>
	<div class="col col-md-8">
	<?=Html::dropDownList('to','',$showselect,['multiple'=>'multiple','size'=>'20','style'=>'width:250px','id'=>'to'])?>
	</div>
</div>
<div>
	<?=Html::submitButton('确定',['class'=>'btn btn-success','onclick'=>"$('#to option').attr('selected','selected');"])?>
</div>
</form>
<script type="text/javascript">
//检查提交数据
function docheck(){
	var a = $.trim($('input[name=modelname]').val()).length;
	if(a == 0){
		bootbox.alert("请填写范本名称!");
		return false;
	}
	$('#to option').prop('selected','selected');
}
//添加
function go(){
var go=$('#source :selected').clone();
go.each(function(){
	 var option=$(this);
	 var option2=option.clone();
	 var exist=false;
     $('#to option').each(function (){
	 if(option.val()==$(this).val())
	 {
		exist=true;
		return false;
	 }
	 });
	 if(!exist){
	    $('#to').append(option);
	 }
  });
}


//删除
function dele(){
// var del=$('#to :selected');
// var index=del.index();
$('#to :selected').remove();
}
//全部添加
function allgo(){
	var goall=$('#source option').clone();
	$('#to').empty();
	$('#to').append(goall);
}
//上移
function moveup(){
	var go=$('#to :selected');
    var index=go.first().index()-1;
	go.last().after($('#to  option:eq('+index+')'));
}
//下移
function movedown(){
	var go=$('#to :selected');
	var index=go.last().index()+1;
	go.first().before($('#to  option:eq('+index+')'));
}
</script>