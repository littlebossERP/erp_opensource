<?php

use yii\helpers\Url;
use yii\helpers\Html;
?>
<style>

</style>
<script>
function doaddfitment(){
	$.showLoading();
	$.post("<?=Url::to(['/listing/ebayitem/ajaxaddfitment'])?>",{itemid:$('#itemid').val(),mubanid:$('select[name=fitmentmuban]').val()},function(result){
		$.hideLoading();
		result = eval("("+result+")")
		if(result.ack == 'success'){
			bootbox.alert("操作已成功");
		}else{
			bootbox.alert(result.msg);
		}
	});
}
</script>
<div class="modal-header">
    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
    <h4 class="modal-title">添加汽配信息</h4>
</div>
<div class="modal-body">
	<div class="choosemuban">
		<?=Html::hiddenInput('itemid',$itemid,['id'=>'itemid'])?>
		<?php if (count($fitmentmuban)==0):?>
		<div class="jumbotron">
		  <p>目前还没有汽配信息范本,请先创建范本</p>
		  <p><a class="btn btn-success create" href="<?=Url::to(['ebaycompatibility/show'])?>" role="button" target="_blank">创建</a></p>
		</div>
		<?php else:?>
		<div class="form-inline">
		<label for="fitmentmuban">汽配范本</label>
		<select name="fitmentmuban" class="form-control">
			<?php foreach ($fitmentmuban as $f):?>
			<option value="<?=$f->id?>"><?=$f->name?></option>
			<?php endforeach;?>
		</select>
		</div>
		<?=Html::button('确定',['class'=>'btn btn-primary','onclick'=>'doaddfitment()'])?>
		<?php endif;?>
	</div>
</div>