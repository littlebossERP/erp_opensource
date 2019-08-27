<?php
use yii\helpers\Html;
use eagle\modules\util\helpers\TranslateHelper;
use yii\helpers\Url;

$baseUrl = \Yii::$app->urlManager->baseUrl . '/';
?>

<!-- Modal -->
<div class="modal fade" id="import_excel_file" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
    <div class="modal-dialog">
    	
        <div class="modal-content">
        	<div class="modal-header">
	        	<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
	        	<h4 class="modal-title"><?= TranslateHelper::t('excel导入')?></h4>
	        	
	        </div>
	        <div class="modal-body">
	        	<div id = "import_excel_body" >
	        		<form id="form_import_excel_file"; action="<?= Url::to(['/tracking/tracking/'.yii::$app->controller->action->id])?>" method="post" enctype="multipart/form-data">
		        	<input type="file" id="input_import_excel_file" name="input_import_excel_file" style="display: inline-block">
		        	<small> <a href="<?= $baseUrl."template/Tracking Template.xls"?>"><?= TranslateHelper::t('XLS示例文件下载')?></a></small>
		        	</form>
		        	
	        	</div>
	        	
	        	<input type="hidden" id='tip_file_empty' value="<?= TranslateHelper::t('请摆选择导入的文件!')?>"/>
	        </div>
	        
			<div class="modal-footer">
				<button id="btn-import-file" type="button" class="btn btn-success" data-loading-text="<?= TranslateHelper::t('上传中')?>" ><?= TranslateHelper::t('导入')?></button>
				<button type="button" class="btn btn-default" data-dismiss="modal"><?= TranslateHelper::t('退出')?></button>
				
			</div>
        </div><!-- /.modal-content -->
    </div>
    <!-- /.modal-dialog -->
</div>