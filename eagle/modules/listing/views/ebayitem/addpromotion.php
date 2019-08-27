<?php

use yii\helpers\Html;
use yii\helpers\Url;
?>
<style>
p{
	margin:5px 0px;
}
</style>
<div class="modal-header">
    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
    <h4 class="modal-title">批量添加促销</h4>
</div>
<div class="modal-body">
	<?=Html::hiddenInput('itemids',$itemids,['id'=>'itemids'])?>
	<p><?=Html::dropDownList('promotionid','',$proms,['class'=>'iv-input'])?></p>
	<div class="act">
		<button class="iv-btn btn-search" onclick="doadd()">确定</button>
		<button class="iv-btn btn-default" data-dismiss="modal">取消</button>
	</div>
</div>
<script>
function doadd(){
	var promid = $('select[name=promotionid]').val();
	if(promid == null){
		bootbox.alert('请先创建促销规则');return false;
	}
	$.showLoading();
	$.post("<?=Url::to(['/listing/ebayitem/ajaxaddpromotion'])?>",{itemids:$('#itemids').val,promid:promid},function(result){
		$.hideLoading();
		if(result == 'success'){
			bootbox.alert('请求已成功');return false;
		}else{
			bootbox.alert('请求失败:'+result);return false;
		}
	});
}
</script>