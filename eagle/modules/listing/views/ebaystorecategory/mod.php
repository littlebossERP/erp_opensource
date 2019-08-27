<?php

use yii\helpers\Html;
?>
<style>
.iv-input{
	margin:5px 0px;
	width:300px;
}
.modal-body{
	margin-left:120px;
}
.modal-footer>button{
	margin:0px 20px;
}
</style>
<div class="modal-header">
	<button type="button" class="close" data-dismiss="modal" aria-hidden="true">
    	&times;
    </button>
    <h4 class="modal-title" id="myModalLabel">
	设置店铺类目
	</h4>
</div>
<div class="modal-body">
<p>用户账号</p>
<?=Html::textInput('selleruserid',$ca->selleruserid,['disabled'=>'disabled','class'=>'iv-input'])?><br/>
<?php if ($type == 'edit'):?>
<p>父类目</p>
<?=Html::textInput('pca',is_null($pca)?'根目录':$pca->category_name,['disabled'=>'disabled','class'=>'iv-input'])?><br/>
<p>操作类目</p><?=Html::textInput('ca',$ca->category_name,['id'=>'category_tmp','class'=>'iv-input'])?><br/>
<?=Html::hiddenInput('obj_cid',$ca->categoryid,['id'=>'categoryid_tmp'])?>
<?php elseif ($type == 'addlevel'):?>
<?php if (is_null($pca)):?>
<p>父类目</p>
<?=Html::textInput('pca','根目录',['disabled'=>'disabled','class'=>'iv-input'])?><br/>
<p>操作类目</p>
<?=Html::textInput('ca','',['id'=>'category_tmp','class'=>'iv-input'])?><br/>
<?=Html::hiddenInput('obj_cid',0,['id'=>'categoryid_tmp'])?>
<?php else:?>
<p>父类目</p>
<?=Html::textInput('pca',$pca->category_name,['disabled'=>'disabled','class'=>'iv-input'])?><br/>
<p>操作类目</p>
<?=Html::textInput('ca','',['id'=>'category_tmp','class'=>'iv-input'])?><br/>
<?=Html::hiddenInput('obj_cid',$pca->categoryid,['id'=>'categoryid_tmp'])?>
<?php endif;?>
<?php elseif ($type == 'addsub'):?>
<p>父类目</p>
<?=Html::textInput('pca',$ca->category_name,['disabled'=>'disabled','class'=>'iv-input'])?><br/>
<p>操作类目</p>
<?=Html::textInput('ca','',['id'=>'category_tmp','class'=>'iv-input'])?><br/>
<?=Html::hiddenInput('obj_cid',$ca->categoryid,['id'=>'categoryid_tmp'])?>
<?php endif;?>
<div class="modal-footer">
	<button type="button" class="iv-btn btn-search" onclick="doaction('<?=$type?>')"> 提交</button>
	<button type="button" class="iv-btn btn-default" data-dismiss="modal">取消</button>
</div>