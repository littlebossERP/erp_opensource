<?php 
use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\LinkPager;
use eagle\modules\util\helpers\ExcelHelper;

?>
<style>
a{
	text-decoration:none;
}
a:hover{
	text-decoration:none;
} 
</style>
<div class="col col-md-6">
<?=Html::button('添加范本',['class'=>'btn btn-success','onclick'=>"doaction('edit',0)"])?>
<!-- 列表主体内容 -->
<div>
<table class="table table-condensed table-striped" style="font-size:12px;">
	<tr><th>样式名称</th><th>标签</th><th style="width:90px">操作</th></tr>
	<?php $all=ExcelHelper::$content?>
	<?php if (count($models)):foreach($models as $model):?>
	<tr>
		<td><?=$model->name?></td>
		<td>
		<?php 
			$content_arr = explode(',',$model->content);
			foreach ($content_arr as $a):
		?>
		<span style="background-color:#f4f9fc"><?=$all[$a]?></span>&nbsp;
		<?php endforeach;?>
		</td>
		<td>
			<a href="#" onclick="javascript:doaction('edit','<?=$model->id?>')">编辑</a>|
			<a href="#" onclick="javascript:doaction('delete','<?=$model->id?>')">删除</a>
		</td>
	</tr>
	<?php endforeach;endif;?>
</table>
<?=LinkPager::widget(['pagination'=>$pages])?>
</div>
</div>
<script>
function doaction(type,modelid){
	switch(type){
		case 'edit':
			window.open('<?=Url::to(['/order/excel/excelmodel_edit'])?>'+'?mid='+modelid);
		break;
		case 'delete':
			$.post('<?=Url::to(['/order/excel/excelmodel_del'])?>',{mid:modelid},function(msg){
				if(msg=='success'){
					location.reload();
					bootbox.alert('操作已成功');
				}else{
					bootbox.alert(msg);
				}
			});
		break;
		default:break;
	}
}
</script>


