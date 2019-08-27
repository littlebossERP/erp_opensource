<?php 
use yii\helpers\Html;
use yii\helpers\Url;
?>
<form action="<?=Url::to(['/order/custom/create']) ?>" method="post">
<div class="modal-header">
	<button type="button" class="close" data-dismiss="modal" aria-hidden="true">
    	&times;
    </button>
    <h4 class="modal-title" id="myModalLabel">
<?php if ($template->isNewRecord):?>            
 创建
<?php else:?>
修改 <?=Html::input('hidden','templateid',$template->id)?>
<?php endif;?>
好评范本
	</h4>
</div>
<div class="modal-body">
<?=Html::dropDownList('feedbacktype',$template->template_type,['1'=>'好评','2'=>'中评','3'=>'差评'],['class'=>'input input-sm'])?>
<br><br>
<?=Html::textInput('feedbackval',$template->template,['class'=>'input input-sm'])?>
</div>
<div class="modal-footer">
	<button type="button" class="btn btn-default" data-dismiss="modal">关闭</button>
    <button type="submit" class="btn btn-primary"> 提交</button>
</div>
</form>