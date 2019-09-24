<?php 
use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\LinkPager;
use eagle\modules\util\helpers\ExcelHelper;

$this->registerJsFile(\Yii::getAlias('@web').'js/jquery.json-2.4.js', ['depends' => ['yii\jui\JuiAsset','yii\bootstrap\BootstrapPluginAsset']]);
$this->registerJsFile(\Yii::getAlias('@web').'js/project/configuration/elseconfig/addexportshow.js?v=1.0', ['depends' => ['yii\jui\JuiAsset','yii\bootstrap\BootstrapPluginAsset']]);
?>
<style>
a{
	text-decoration:none;
}
a:hover{
	text-decoration:none;
} 
.col-md-8{
	width: 90%;
} 
</style>
<?php echo $this->render('../leftmenu/_leftmenu');?>
<?php 
//判断子账号是否有权限查看，lrq20170829
if(!\eagle\modules\permission\apihelpers\UserApiHelper::checkSettingModulePermission('oms_setting')){?>
	<div style="float:left; margin: auto; margin:50px 0px 0px 200px; ">
		<span style="font: bold 20px Arial;">亲，没有权限访问。 </span>
	</div>
<?php return;}?>

<div class="col col-md-8">
<!-- <a class="iv-btn btn-important" style="margin:8px 0;" onclick="$.modal(
	{url:'/configuration/elseconfig/excelmodel_edit?mid=0',method:'get',data:{}},
	'添加范本'
	).done(function($modal){
		$modal.on('modal.action.resolve',function(){
			$('#to option').attr('selected','selected');
			$('#edit2_fanben_FORM').submit();
		});
	});">添加范本</a>-->
	<a class="iv-btn btn-important" style="margin:8px 0;" onclick="create(-1)">添加范本</a>
<!-- 列表主体内容 -->
<div>
<table class="table table-condensed table-striped" style="font-size:12px;">
	<thead><tr><th>样式名称</th><th>标签</th><th style="width:90px">操作</th></tr></thead>
	<?php $all=ExcelHelper::$content?>
	<?php 
	$item_arr=array();
	if (count($models)):foreach($models as $model):?>
	<tr>
		<td><?=\yii\helpers\Html::encode($model->name)?></td>
		<td>
		<?php 
			$content_arr = explode(',',\yii\helpers\Html::encode($model->content));
			foreach ($content_arr as $b):
			$a_arr=explode(':',$b);
			if(isset($all[$a_arr[0]]) || strstr($a_arr[0],'-custom-')!=false){
				$a=$a_arr[0];
				if(empty($a_arr[1]))
					$item_arr[$a_arr[0]]=$all[$a_arr[0]];
				else
					$item_arr[$a_arr[0]]=$a_arr[1];
			}
		?>
		<span style="background-color:#f4f9fc"><?=$item_arr[$a]?></span>&nbsp;
		<?php endforeach;?>
		</td>
		<td>
			<!-- <a 
				class=""
				title="编辑范本"
				
				onclick="$.modal(
	{url:'/configuration/elseconfig/excelmodel_edit?mid=<?=$model->id?>',method:'get',data:{}},
	'编辑范本'
	).done(function($modal){
		$modal.on('modal.action.resolve',function(){
			
			$('#edit2_fanben_FORM').submit();
		});
	});"
			>编辑</a>-->
			<a onclick="create(<?=$model->id?>)">编辑</a>
			<a href="#" onclick="javascript:doaction('delete','<?=$model->id?>')">删除</a>
		</td>
	</tr>
		<tr style="background-color: #d9d9d9;"><td colspan="3" border:1px="" solid="" #d1d1d1"="" style="padding: 1.5px;"></td></tr>
	<?php endforeach;endif;?>
</table>
<?=LinkPager::widget(['pagination'=>$pages])?>
</div>
</div>
<script>
function doaction(type,modelid){
	switch(type){
		case 'edit':
			window.open('<?=Url::to(['/configuration/elseconfig/excelmodel_edit'])?>'+'?mid='+modelid);
		break;
		case 'delete':
			$e = $.confirmBox('<p class="text-danger">确认删除此范本？</p>');
			$e.then(function(){
				$.post('<?=Url::to(['/configuration/elseconfig/excelmodel_del'])?>',{mid:modelid},function(msg){
					if(msg=='success'){
						$s = $.alert('操作已成功','success');
						$s.then(function(){
							location.reload();
						});
					}else{
						$.alert(msg);
					}
				});
			});
			
		break;
		default:break;
	}
}

function create(val){
	$.modal({
		  url:'/configuration/elseconfig/excelmodel_edit_new?mid='+val,
		  method:'get',
		  data:{}
		},'创建导出订单范本',{footer:false,inside:false}).done(function($modal){
			$('.modal-close').click(function(){$modal.close();});
		}
		);
}

</script>


