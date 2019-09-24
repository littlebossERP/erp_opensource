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
<?php echo $this->render('../leftmenu/_leftmenu');?>
<?php 
//判断子账号是否有权限查看，lrq20170829
if(!\eagle\modules\permission\apihelpers\UserApiHelper::checkSettingModulePermission('oms_setting')){?>
	<div style="float:left; margin: auto; margin:50px 0px 0px 200px; ">
		<span style="font: bold 20px Arial;">亲，没有权限访问。 </span>
	</div>
<?php return;}?>

<br>
<a class="iv-btn btn-important" style="margin:8px 0;" onclick="$.modal(
	{url:'/configuration/elseconfig/create?id=0',method:'get',data:{}},
	'创建 好评范本'
	).done(function($modal){
		$modal.on('modal.action.resolve',function(){
			SaveFeedbackTemplate();
		});
	});">创建</a>
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
	<td><?=\yii\helpers\Html::encode($list->template)?></td>
	<td>
		<a 
			class=""
			onclick="$.modal(
	{url:'/configuration/elseconfig/create?id=<?=$list->id?>',method:'get',data:{}},
	'创建 好评范本'
	).done(function($modal){
		$modal.on('modal.action.resolve',function(){
			SaveFeedbackTemplate();
		});
	});"
		>编辑</a>
		<a href="#" onclick="javascript:dodelete('<?=$list->id?>')">删除</a>
	</td>
</tr>
<?php endforeach;endif;?>
</table>
<script>
	function SaveFeedbackTemplate(){

		if (! $('#FeedbackTemplateFORM').formValidation('form_validate')){
			bootbox.alert(Translator.t('有必填项未填或格式不正确!'));
			return false;
		}
		
		var Url='<?=Url::to(['/configuration/elseconfig/save-feedback-template'])?>';
		$.ajax({
	        type : 'post',
	        cache : 'false',
	        data : $('#FeedbackTemplateFORM').serialize(),
			url: Url,
	        success:function(response) {
	        	var result = JSON.parse(response);
				if (result.error) {
					$.alert(result.msg,'danger');
				} else {
					$e = $.alert(result.msg,'danger');
					$e.then(function(){
						location.reload();
					});
				}
	        }
	    });
	}
	function dodelete(id){
		$e = $.confirmBox('<p class="text-danger">确认删除此好评范本？</p>');
		$e.then(function(){
			$.post("<?=Url::to(['/configuration/elseconfig/delete'])?>",{id:id},function(result){
				if(result=='success'){
					$e = $.alert('操作已成功','success');
					$e.then(function(){
						location.href="<?=Url::to(['/configuration/elseconfig/feedback-template-list'])?>";
					});
				}else{
					$.alert(result,'danger');
				}
			});
		});
	}

	function initFeedbackFormValidateInput(){
		$("#FeedbackTemplateFORM").find('[name="feedbackval"]').formValidation({validType:['trim', 'safeForHtml'],tipPosition:'right',required:true});
		
	}
</script>