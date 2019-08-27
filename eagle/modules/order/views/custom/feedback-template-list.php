<?php
use yii\helpers\Html;
use eagle\modules\order\models\EbayFeedbackTemplate;
use yii\helpers\Url;

?>
<style>
a{
	text-decoration:none;
}
a:hover{
	text-decoration:none;
} 
</style>
<br>
<?=Html::button('创建',['class'=>'btn btn-success','data-toggle'=>'modal','data-target'=>'#createModal','onclick'=>'editTemplate(0)'])?>
<br>
<table class="table table-striped">
	<tr>
		<th>评价类型</th>
		<th>评价内容</th>
		<th>操作</th>
	</tr>
<?php if (count($lists)):foreach ($lists as $list):?>
<tr>
	<td><?=EbayFeedbackTemplate::$typeval[EbayFeedbackTemplate::$type[$list->template_type]]?></td>
	<td><?=$list->template?></td>
	<td>
		<a href="#" data-toggle='modal' data-target='#createModal' onclick="javascript:editTemplate('<?=$list->id?>')">编辑</a>|
		<a href="#" onclick="javascript:dodelete('<?=$list->id?>')">删除</a>
	</td>
</tr>
<?php endforeach;endif;?>
</table>

<!-- 模态框（Modal） -->
<div class="modal fade" id="createModal" tabindex="-1" role="dialog" 
   aria-labelledby="myModalLabel" aria-hidden="true">
   <div class="modal-dialog">
      <div class="modal-content">

      </div><!-- /.modal-content -->
	</div><!-- /.modal -->
</div>
<script>
	function editTemplate(templateid) {//添加订单标签
		var Url='<?=Url::to(['/order/custom/create'])?>';
		$.ajax({
	        type : 'get',
	        cache : 'false',
	        data : {id : templateid},
			url: Url,
	        success:function(response) {
	        	$('#createModal .modal-content').html(response);
	        }
	    });
	}

	function dodelete(id){
		$.post("<?=Url::to(['/order/custom/delete'])?>",{id:id},function(result){
			if(result=='success'){
				bootbox.alert('操作已成功');
				location.href="<?=Url::to(['/order/custom/feedback-template-list'])?>";
			}else{
				bootbox.alert(result);
			}
		});
	}
</script>