<?php

use yii\helpers\Url;
use yii\helpers\Html;
use eagle\modules\util\helpers\TranslateHelper;
use eagle\widgets\SizePager;
use eagle\modules\amazoncs\helpers\AmazoncsHelper;

$this->registerJsFile(\Yii::getAlias('@web').'/js/project/amazoncs/amazoncs.js',['depends' => ['yii\web\JqueryAsset']]);

//$this->registerJsFile(\Yii::getAlias('@web').'js/jquery.json-2.4.js', ['depends' => ['yii\jui\JuiAsset','yii\bootstrap\BootstrapPluginAsset']]);

//$this->registerJs("amazoncs.EmailList.initBtn()", \yii\web\View::POS_READY);
$active = '';
$uid = \Yii::$app->user->id;
$ClientReportDateInfo = eagle\modules\amazoncs\helpers\ClientHelper::getClientReportDateInfo($merchant_id = 'all', $site = 'all');
?>

<?php 
$template_status_mapping = [
	'active'=>'启用中',
	'unActive'=>'已停用',
];
?>

<style>

</style>

<div class="col2-layout col-xs-12">
	<?=$this->render('_leftmenu',[]);?>
	<div class="content-wrapper">
		<form class="form-inline" id="form1" name="form1" action="/amazoncs/amazoncs/template" method="post" style="margin-bottom:10px;">
			<input type="hidden" name="platform" value="amazon" >
			<?=Html::dropDownList('seller_id',@$_REQUEST['seller_id'],$MerchantId_StoreName_Mapping,['class'=>'iv-input','style'=>'width:150px;margin:0px;','prompt'=>'卖家账号'])?>
			<?=Html::dropDownList('site_id',@$_REQUEST['site_id'],$MarketPlace_CountryCode_Mapping,['class'=>'iv-input','style'=>'width:150px;margin:0px;','prompt'=>'站点'])?>
			模板名称：
			<input class="iv-input" type="text" name="name" value="<?=@$_REQUEST['name']?>" style="width:150px;">
			<?=Html::submitButton('搜索',['class'=>"iv-btn btn-search btn-spacing-middle",'id'=>'search'])?>
		</form>
	
		<div style="margin-bottom: 10px;">
			<a type="button" class="iv-btn btn-important" id="btn_create_template_btn" onclick="amazoncs.Template.createOrEditTmplate('create','')">
			  <?=TranslateHelper::t('添加模板')?>
			</a>
		</div>
		
		<div>
			<table class="table">
				<tr>
					<th width="1%" style="display:none;">
						<input id="ck_all" class="ck_0" type="checkbox" onchange="selected_switch()">
					</th>
					<th style="display:none;">销售平台</th>
					<th>seller</th>
					<th>站点</th>
					<th>模板名称</th>
					<th>状态</th>
					<th>是否自动生成任务</th>
					<th>上次成功生成任务时间</th>
					<th>上次运行客户端时间</th>
					<th>操作</th>
				</tr>
				<?php if(!empty($models)): foreach ($models as $index=>$model){
				
				?>
				<tr>
					<td style="display:none;">
						<label><input type="checkbox" class="ck" name="template_id[]" value="<?=$model->id?>" data-platform="<?=$model->platform?>"></label>
					</td>
					<td><?=empty($MerchantId_StoreName_Mapping[$model->seller_id])?$model->seller_id:$MerchantId_StoreName_Mapping[$model->seller_id]?></td>
					<td><?=empty($MarketPlace_CountryCode_Mapping[$model->site_id])?$model->site_id:$MarketPlace_CountryCode_Mapping[$model->site_id]?></td>
					<td><?=$model->name?></td>
					<td>
						<?=empty($template_status_mapping[$model->status])?$model->status:$template_status_mapping[$model->status] ?>
					</td>
					<td><?=empty($model->auto_generate)?'否':'是'?></td>
					<td><?=AmazoncsHelper::returnDaysAgoStr($model->last_generated_time) ?></td>
					<?php 
						$last_review_fetch = 'N/A';
						$last_feedback_fetch = 'N/A';
						$site = empty($MarketPlace_CountryCode_Mapping[$model->site_id])?$model->site_id:$MarketPlace_CountryCode_Mapping[$model->site_id];
						if(isset($ClientReportDateInfo[$model->seller_id][$site]['review_time']))
							$last_review_fetch  = date("Y-m-d H:i", $ClientReportDateInfo[$model->seller_id][$site]['review_time']);
						if(isset($ClientReportDateInfo[$model->seller_id][$site]['feedback_time']))
							$last_feedback_fetch  = date("Y-m-d H:i", $ClientReportDateInfo[$model->seller_id][$site]['feedback_time']);
					?>
					<td>最后review同步时间：<?=$last_review_fetch?><br>最后feedback同步时间：<?=$last_feedback_fetch?></td>
					<td>
						<a href="javascript:void(0)" style="" onclick="amazoncs.Template.createOrEditTmplate('edit','<?=$model->id?>')" ><span class="egicon-edit" title="查看/编辑模板"></span></a>
						<a href="javascript:void(0)" style="margin-left:5px;" onclick="amazoncs.Template.previewTmplate('saved','<?=$model->id?>')" ><span class="egicon-eye" title="预览模板"></span></a>
						<a href="javascript:void(0)" style="margin-left:5px;" onclick="amazoncs.Template.generateMailQuest(<?=$model->id?>)" ><span class="glyphicon glyphicon-time" title="生成邮件任务"></span></a>
						<a href="javascript:void(0)" style="margin-left:5px;" onclick="amazoncs.Template.viewQuestGenerateLog('<?=$model->id?>')" ><span class="glyphicon glyphicon-list-alt" title="查看上次生成任务日志"></span></a>
						<a href="javascript:void(0)" style="margin-left:5px;" onclick="amazoncs.Template.delTemplate('<?=$model->id?>')" ><span class="glyphicon glyphicon-trash" title="删除模板" style="color:red"></span></a>
					</td>
				</tr>	
				<?php }endif; ?>
			</table>
			<?=SizePager::widget(['pagination'=>$pagination , 'pageSizeOptions'=>array( 5 , 20 , 50 , 100 , 200 ), 'class'=>'btn-group dropup'])?>
		</div>
	</div>
	<div class="generate-win"></div>
	<div class="create-or-edit-win"></div>
	<div class="pre-view-win"></div>
	<div class="generate-log-win"></div>
</div>