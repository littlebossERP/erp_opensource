<?php
use yii\helpers\Html;
use yii\helpers\Url;

$this->title='自定义标签管理';
$this->params['breadcrumbs'][] = $this->title;
?>
<?=Html::button('创建',['class'=>'btn btn-info','data-toggle'=>'modal','data-target'=>'#createModal','onclick'=>'editusertab(0)'])?>
<table class="table table-condensed">
	<tr>
		<th>标签名</th>
		<th>操作</th>
	</tr>
	<?php if (count($tabs)):foreach ($tabs as $tab):?>
	<tr>
		<td><?=$tab->tabname?></td>
		<td>
			<?=Html::button('修改',['data-toggle'=>'modal','data-target'=>'#createModal','onclick'=>"editusertab($tab->id)"])?>
			<?=Html::button('删除',['onclick'=>"javascript:dodelete($tab->id)"])?>
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
<script>
	function editusertab(templateid) {//添加订单标签
		var Url='<?=Url::to(['/order/order/edittab'])?>';
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
		$.post("<?=Url::to(['/order/order/deletetab'])?>",{id:id},function(result){
			if(result=='success'){
				bootbox.alert('操作已成功');
				location.href="<?=Url::to(['/order/order/usertab'])?>";
			}else{
				bootbox.alert(result);
			}
		});
	}
</script>
