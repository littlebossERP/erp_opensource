<?php

use yii\helpers\Html;
use common\helpers\Helper_Array;
?>
<style type="text/css"> 
.mianbaoxie{
	margin:10px 0px;
}
.mianbaoxie>span:first-child{
	border-color:rgb(1,189,240);
	border-width:0px 0px 0 7px;
	border-style:solid;
}
.mianbaoxie>span:last-child{
	display:inline-block;
	width:10px;
}
.forseller,.forpaypal{
	margin:15px;
}
.forseller>span,.forpaypal>span{
	color:red;
}
.paypalinput,.paypalinput-bak{
	margin:5px 0px;
	background-color:rgb(249,249,249);
	width:350px;
	height:120px;
	padding:11px;
}
.paypalinput-bak{
	display:none;
}
.paypalinput span,.paypalinput-bak span{
	float:right;
}
.paypalinput_add{
	margin:5px 0px;
	background-color:rgb(249,249,249);
	width:350px;
	height:40px;
	padding:11px;
}
.paypalinput_add span{
	color:rgb(3,206,89);
	margin:0px 5px;
}
.form-group{
	margin:15px 10px;
}
.form-group label{
	width:50px;
}
.btngroup{
	width:350px;
	text-align:center;
	margin-top:30px;
}
.btngroup button{
	width:120px;
}
.glyphicon{
	cursor:pointer;
}
strong{
	display:block;
	padding:5px;
	color:blue;
}
</style> 
<div class="tracking-index col2-layout">
	<?=$this->render('../_ebay_leftmenu',['active'=>'账户绑定管理']);?>
	<div class="content-wrapper" >
		<div class="mianbaoxie">
			<span></span><span></span>账户绑定设置
		</div>
		<form action="" method="post" name="a" id="a" onsubmit="javascript:return(docheck());">
		<div class="mainedit">
			<div class="forseller">
			eBay账号<span>*</span><br>
			<?php if (strlen($_REQUEST['selleruserid'])):?>
				<strong><?=$_REQUEST['selleruserid']?></strong>
				<?=Html::hiddenInput('selleruserid',$_REQUEST['selleruserid'],['id'=>'selleruserid'])?>
			<?php else:?>
			<?=Html::dropDownList('selleruserid',@$_REQUEST['selleruserid'],Helper_Array::toHashmap($ebayselleruserid,'selleruserid','selleruserid'),['class'=>'iv-input'])?>
			<?php endif;?>
			</div>
			<div class="forpaypal">
				PalPal账号<span>*</span><br>
				<?php if (count($maps)==0):?>
				<div class="paypalinput">
					<div class="form-group">
						<label>账号名称</label>
						<?=Html::input('email','paypal[]','',['class'=>'iv-input'])?>
					</div>
					<div class="form-group">
						<label>备注</label>
						<?=Html::textInput('desc[]','',['class'=>'iv-input'])?>
					</div>
				</div>
				<?php endif;?>
				<?php $i=0;?>
				<?php if (count($maps)):foreach ($maps as $map):?>
				<div class="paypalinput">
					<?php if($i>0):?>
					<span class="glyphicon glyphicon-remove" onclick="divremove(this)"></span>
					<?php endif;?>
					<div class="form-group">
						<label>账号名称</label>
						<?=Html::input('email','paypal[]',$map->paypal,['class'=>'iv-input'])?>
					</div>
					<div class="form-group">
						<label>备注</label>
						<?=Html::textInput('desc[]',$map->desc,['class'=>'iv-input'])?>
					</div>
				</div><?php $i++;?>
				<?php endforeach;endif;?>
				<div class="clonediv">
				
				</div>
				<div class="paypalinput-bak">
					<span class="glyphicon glyphicon-remove" onclick="divremove(this)"></span>
					<div class="form-group">
						<label>账号名称</label>
						<?=Html::input('email','paypal[]','',['class'=>'iv-input'])?>
					</div>
					<div class="form-group">
						<label>备注</label>
						<?=Html::textInput('desc[]','',['class'=>'iv-input'])?>
					</div>
				</div>
				
				<div class="paypalinput_add">
					<span class="glyphicon glyphicon-plus" onclick="divadd(this)">添加一个PalPal账号</span>
				</div>
				
				<div class="form-group btngroup">
				<?=Html::submitButton('确定',['class'=>'btn btn-success'])?>
				<?=Html::button('取消',['class'=>'btn btn-default','onclick'=>'window.close();'])?>
				</div>
			</div>
		</div>
		</form>
	</div>
</div>
<script>
//检测数据
function docheck(){
	var selleruserid = $('select[name=selleruserid]').val();
	if(typeof(selleruserid) == "undefined"){
		selleruserid = $('#selleruserid').val();
		if(selleruserid =='' || selleruserid == null){
			bootbox.alert('请先绑定eBay账号');return false;
		}
	}else{
		if(selleruserid =='' || selleruserid == null){
			bootbox.alert('请先绑定eBay账号');return false;
		}
	}
	var obj = $('input[name^=paypal]');
	for(var i=0;i<obj.length-1;i++){
		if(obj[i].value == "" || obj[i].value == null){
			bootbox.alert('请务必填写paypal账号的值');return false;
		}
	}
//	document.a.submit();
}
//清楚相应的paypal设置区块
function divremove(obj){
	$(obj).parent().remove();
}
//插入一个paypal的设置区域
function divadd(obj){
	var ht = $('.paypalinput-bak').html();
	var str = '<div class="paypalinput">'+ht+'</div>';
	$('.clonediv').append(str);
}
</script>