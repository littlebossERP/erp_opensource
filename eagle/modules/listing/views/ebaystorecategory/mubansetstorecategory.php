<?php

use yii\helpers\Html;
?>
<div class="modal-header">
	<button type="button" class="close" data-dismiss="modal" aria-hidden="true">
    	&times;
    </button>
    <h4 class="modal-title" id="myModalLabel">
	选择店铺类目
	</h4>
</div>
<div class="modal-body">
<?=Html::hiddenInput('cid',$cid,['id'=>'cid'])?>
<?php if (count($ct)):?>
<div id="TreeList">
	<table>
	<?php foreach ($ct as $c):?>
		<tr>
			<td><?=$c['2'].$c['1']['category_name']?></td>
			<td><span style="cursor:pointer" cid="<?=$c['1']['categoryid']?>" onclick="choice(this)">[选择]</span></td>
		</tr>
    <?php endforeach;?>
    </table>
</div> 
<?php endif;?>
</div>
<div class="modal-footer">
	<button type="button" class="btn btn-default" data-dismiss="modal">关闭</button>
</div>
<script>
function choice(obj){
	var id=$('#cid').val();
	$('#'+id).val($(obj).attr('cid'));
	$('#categorysetModal').modal('hide');
}
</script>