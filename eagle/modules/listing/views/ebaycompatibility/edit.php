<?php


use yii\helpers\Html;

$this->registerJsFile(\Yii::getAlias('@web')."/js/project/listing/fitmentshow.js", ['depends' => ['yii\web\JqueryAsset']]);
?>
<style>
.do-layout{
	width:90%;
	margin:5px 5% 0 5%;
}
.row label{
	margin-top:15px;
}
.checknameresult,.checkcategoryresult{
	display:block;
	margin-top:15px;
}
span[class=success]{
	color:RGB(58,135,73);
}
span[class=failure]{
	color:rgb(237,145,58);
}
.choosefitment{
	height:50px;
}
.muti_do{
	display:none;
}
</style>
<div class="do-layout">
<form action="" method="post" name="q" onsubmit="return checkdata()">
	<div class='choosecategory'>
		<div class="row">
			<div class="col-md-1">
			<label for='site'>平台</label>
			</div>
			<div class="col-md-2">
			<?=Html::dropDownList('site',@$fitment->siteid,['100'=>'eBayMotors','3'=>'UK','15'=>'Australia','77'=>'Germany'],['class'=>'form-control'])?>
			</div>
			<div class="col-md-9"></div>
		</div>
		<div class="row">
			<div class="col-md-1">
			<label for='category'>类目</label>
			</div>
			<div class="col-md-2 form-inline">
			<?=Html::textInput('primarycategory',@$fitment->primarycategory,['class'=>'form-control','id'=>'primarycategory'])?>
			<button type="button" class="btn btn-success" onclick="choosecategory();"><span class="glyphicon glyphicon-search"></span></button>
			</div>
			<div class="col-md-1"></div>
			<div class="col-md-3 checkcategoryresult"></div>
			<div class="col-md-5"></div>
		</div>
		<div class="row">
			<div class="col-md-1">
			<label for='site'>模板名</label>
			</div>
			<div class="col-md-3 form-inline">
			<?=Html::textInput('mubanname',@$fitment->name,['class'=>'form-control'])?>
			<button type="button" class="btn btn-success"  onclick="checkname($('input[name=mubanname]').val());"><span class="glyphicon glyphicon-eye-open"></span>验证</button>
			</div>
			<div class="col-md-3 checknameresult"></div>
			<div class="col-md-5"></div>
		</div>
	</div>
	<div class="choosefitment">
		
	</div>
	<div class="choosedfitment">
		<table class="table table-condensed" id="add">
			<tr class="table_head"></tr>
			<?php if (count($fitment->itemcompatibilitylist)):foreach ($fitment->itemcompatibilitylist as $fi):?>
				<tr><td><input type="checkbox" name="occurrence"><a href="javascript:void(0)" title="删除" onclick=deloccurrence(this,"one")><span class="glyphicon glyphicon-remove"></span></a></td>
			<?php foreach ($fi as $fik=>$fiv):?>
			<td><input readonly="readonly" size="10" name = "itemcompatibilitylist[<?=$fik?>][]" value="<?php echo $fiv?>"></td>
			<?php endforeach;?>
			</tr>
			<?php endforeach;endif;?>
		</table>
	</div>
	<div class="muti_do" style="margin-top:5px;">
		<input class="btn" type="button" name="checkall" value="全选" onclick="checkalloccurrence()">&nbsp;
		<input class="btn" type="button" name="notcheck" value="全不选" onclick="cancel()">&nbsp;
		<input class="btn" type="button" name="inverse" value="反选" onclick="inverseall()">&nbsp;
		<input class="btn" type="button" name="deleteall" value="删除" onclick="deloccurrence()">
		<input style="margin:10px 0;" class="btn btn-primary" type="submit" value="提交">
	</div>
	</form>
</div>
<?php if (isset($fitment->id) && !is_null($fitment->id)):?>
<?=Html::hiddenInput('fid',$fitment->id);?>
<?php endif;?>