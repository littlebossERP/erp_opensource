<?php
use yii\helpers\Html;
use eagle\modules\util\helpers\TranslateHelper;
use yii\helpers\Url;

$baseUrl = \Yii::$app->urlManager->baseUrl;
$this->registerJsFile($baseUrl."/js/project/catalog/selectProduct.js", ['depends' => ['yii\jui\JuiAsset','yii\bootstrap\BootstrapPluginAsset']]);

$this->registerJsFile($baseUrl."/js/ajaxfileupload.js", ['depends' => ['yii\jui\JuiAsset','yii\bootstrap\BootstrapPluginAsset']]);
$this->registerJsFile($baseUrl."/js/project/inventory/import_file.js", ['depends' => ['yii\jui\JuiAsset','yii\bootstrap\BootstrapPluginAsset']]);
$this->registerJsFile($baseUrl."/js/project/inventory/text_import.js", ['depends' => ['yii\jui\JuiAsset','yii\bootstrap\BootstrapPluginAsset']]);
$this->registerJsFile($baseUrl."/js/jquery.watermark.min.js", ['depends' => ['yii\jui\JuiAsset','yii\bootstrap\BootstrapPluginAsset']]);
$this->registerCssFile($baseUrl."/css/inventory/inventory.css");

$this->registerJs("inventory.stockAllocation.newstockAllocation();" , \yii\web\View::POS_READY);
?>
<style>
</style>
<FORM id="create_stockAllocation_data_form" style="width:100%;">
	<table class="table" style="margin-bottom:5px;font-size:12px;">
		<tr>
			<td style="width: 60px; padding:0px;margin:0px;text-align:right;vertical-align:middle;">盘点单号</td>
			<td style="width: 15%; padding:0px;margin:0px;text-align:center;vertical-align:middle;">
			  	<input type="text" name="stock_allocatione_id" class="eagle-form-control" value="<?= $stock_allocatione_id ?>">
		  	</td>
		  	
		  	<td style="width: 60px; padding:0px;margin:0px 30px;text-align:right;vertical-align:middle;">调出仓: </td>
			<td style="width: 15%; padding:0px;margin:0px  30px;text-align:center;vertical-align:middle;">
  				<SELECT name="out_warehouse_id" value="" class="eagle-form-control" style="max-width: 150px">
  				<?php foreach($warehouse as $wh_id=>$wh_name){
					echo "<option value='".$wh_id."' >".$wh_name."</option>";						
				} ?>
		  		</SELECT>
		  	</td>
		  	
			<td style="width: 60px; padding:0px; margin:0px 30px; text-align:right;vertical-align:middle;">调入仓: </td>
			<td style="width: 15%; padding:0px; margin:0px 30px; text-align:center;vertical-align:middle;">
  				<SELECT name="in_warehouse_id" value="" class="eagle-form-control" style="max-width: 150px">
  				<?php foreach($warehouse as $wh_id=>$wh_name){
					echo "<option value='".$wh_id."' >".$wh_name."</option>";						
				} ?>
		  		</SELECT>
		  	</td>

		  	<td style="width: 60px; padding:0px;margin:0px;text-align:right;vertical-align:middle;">操作人员</td>
			<td style="width: 15%; padding:0px;margin:0px;text-align:center;vertical-align:middle;">
			  	<input type="text" name="user_name" disabled="disabled" class="eagle-form-control" value="<?=\Yii::$app->user->identity->getFullName() ?>">
		  	</td>
			
		</tr>
		<tr>
			<td style="width:60px;padding:0px;margin:0px;text-align:right;vertical-align:middle;">盘点备注</td>
			<td colspan="7">
				<textarea id="comment" class="form-control" name="comment" value="" style="width:100%"></textarea>
		  	</td>
		</tr>
	</table>
	<div>
		<button type="button" class="btn-xs btn-warning" onclick="inventory.stockAllocation.selectstockAllocationProd()" style="margin:0px;border-style:none;" data-loading-text="<?= TranslateHelper::t('查询中...')?>">
			<span class="glyphicon glyphicon-search" aria-hidden="true"></span>
			<?= TranslateHelper::t('选择产品')?>
		</button>
		<button type="button" class="btn-xs btn-primary" id="btn_stockAllocation_import_text" style="margin:0px;border-style:none;">
			<span class="glyphicon glyphicon-list-alt" aria-hidden="true"></span>
			<?= TranslateHelper::t('复制粘贴excel格式')?>
		</button>
		<button type="button" class="btn-xs btn-info" id="btn_stockAllocation_import_product" style="margin:0px;border-style:none;">
			<span class="glyphicon glyphicon-folder-open" aria-hidden="true"></span>
			<?= TranslateHelper::t('Excel导入')?>
		</button>
	</div>
	<table id="stockAllocation_prodList_tb" class="table table-hover" style="width:100%; margin:10px 0px;">
		<tr>
			<th width="10%" style=""><?=TranslateHelper::t('图片') ?></th>
			<th width="20%" style="">sku</th>
			<th width="30%" style=""><?=TranslateHelper::t('产品名称') ?></th>
			<th width="10%" style=""><?=TranslateHelper::t('出仓库存') ?></th>
			<th width="10%" style=""><?=TranslateHelper::t('调拨数量') ?></th>
			<th width="10%" style=""><?=TranslateHelper::t('入仓货架') ?></th>
			<th width="8%" style=""><?=TranslateHelper::t('操作') ?></th>
		</tr>
  					
		<tr id='none_product_tr'><td colspan="8" style="text-align:center;"><b style="color:red;">没有选择具体产品，不能保存</b></td></tr>
	</table>
	<div class="sku_name_area" style="display:none;"></div>
	<div style="width:100%;position:relative;">
  		<button type="button" class="btn btn-success" id="save_newstockAllocation_btn" style="float:right;" data-loading-text="<?= TranslateHelper::t('保存中...')?>" disabled="disabled">
	    	<span class="glyphicon glyphicon-floppy-disk" aria-hidden="true"></span>
	    	<?= TranslateHelper::t('保存')?>
	   </button>
  	</div>			
</FORM>
<!-- show import_excelFormatText_dialog -->
<div class="import_excelFormatText_dialog"></div>
<!-- /dialog -->
<input id="data_empty_message" type="hidden" value="<?=TranslateHelper::t('无输入数据,请重新输入') ?>">

