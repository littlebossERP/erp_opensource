<?php

use yii\helpers\Url;
use yii\helpers\Html;
use eagle\modules\util\helpers\TranslateHelper;
use eagle\widgets\SizePager;

$this->registerJsFile(\Yii::getAlias('@web').'/js/project/amazoncs/amazoncs.js',['depends' => ['yii\web\JqueryAsset']]);

//$this->registerJsFile(\Yii::getAlias('@web').'js/jquery.json-2.4.js', ['depends' => ['yii\jui\JuiAsset','yii\bootstrap\BootstrapPluginAsset']]);

//$this->registerJs("amazoncs.EmailList.initBtn()", \yii\web\View::POS_READY);
$active = '';
$uid = \Yii::$app->user->id;

?>


<style>

</style>

<div class="col2-layout col-xs-12">
	<?=$this->render('_leftmenu',[]);?>
	<div class="content-wrapper">
		<form class="form-inline" id="form1" name="form1" action="/amazoncs/amazoncs/pending-send" method="post" style="margin-bottom:10px;">
			<input type="hidden" name="platform" value="<?=empty($_REQUEST['platform'])?'amazon':$_REQUEST['platform']?>">
			<?=Html::dropDownList('seller_id',@$_REQUEST['seller_site'],$seller_site_list,['class'=>'iv-input','style'=>'width:150px;margin:0px;','prompt'=>'卖家账号'])?>
			<?=Html::dropDownList('quest_template_id',@$_REQUEST['quest_template_id'],$quest_templates,['class'=>'iv-input','style'=>'width:150px;margin:0px;','prompt'=>'任务模板'])?>
			收件人：
			<input class="iv-input" type="text" name="consignee" value="<?=@$_REQUEST['consignee']?>" style="width:150px;">
			<?=Html::submitButton('搜索',['class'=>"iv-btn btn-search btn-spacing-middle",'id'=>'search'])?>
		</form>
	
		<div style="margin-bottom: 10px;">
			<a type="button" class="iv-btn btn-important" id="" onclick="amazoncs.QuestList.batchCancelPendingSend()">
			  <?=TranslateHelper::t('取消发送')?>
			</a>
		</div>
		
		<div>
			<table class="table">
				<tr>
					<th width="1%" style="display:none;">
						<input id="ck_all" class="ck_0" type="checkbox" onchange="selected_switch()">
					</th>
					<th>店铺</th>
					<th>模板名称</th>
					<th>收件人</th>
					<th>状态</th>
					<th>任务生成时间</th>
					<th>预计发送时间(本地/客户)</th>
					<th>最后发送时间</th>
					<th>操作</th>
				</tr>
				<?php if(!empty($models)): foreach ($models as $index=>$model){
				
				?>
				<tr>
					<td style="display:none;">
						<label><input type="checkbox" class="ck" name="id[]" value="<?=$model->id?>"></label>
					</td>
					<td><?=$model->seller_site?></td>
					<td><?=$model->name?></td>
					<td><?=$model->status?></td>
					<td>
						<a href="javascript:void(0)" style="" onclick="amazoncs.Template.createOrEditTmplate('edit','<?=$model->id?>')" ><span class="egicon-edit" title="查看/编辑模板"></span></a>
						<a href="javascript:void(0)" style="margin-left:5px;" onclick="amazoncs.Template.previewTmplate('saved','<?=$model->id?>')" ><span class="egicon-eye" title="预览模板"></span></a>
						<a href="javascript:void(0)" style="margin-left:5px;" onclick="amazoncs.Template.generateMailQuest(<?=$model->id?>)" ><span class="egicon-clock" title="生成邮件任务"></span></a>
						<a href="javascript:void(0)" style="margin-left:5px;" onclick="amazoncs.Template.delTemplate('<?=$model->id?>')" ><span class="egicon-trash" title="删除模板"></span></a>
					</td>
				</tr>	
				<?php }endif; ?>
			</table>
			<?=SizePager::widget(['pagination'=>$pagination , 'pageSizeOptions'=>array( 5 , 20 , 50 , 100 , 200 ), 'class'=>'btn-group dropup'])?>
		</div>
	</div>
	<div class="create-or-edit-win"></div>
	<div class="pre-view-win"></div>
</div>