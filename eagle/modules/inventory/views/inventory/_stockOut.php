<?php
use yii\helpers\Html;
use eagle\modules\util\helpers\TranslateHelper;
use yii\helpers\Url;
use eagle\modules\inventory\helpers\InventoryHelper;

$baseUrl = \Yii::$app->urlManager->baseUrl;
$this->registerJsFile($baseUrl."/js/project/inventory/stockchange.js?v=1.1", ['depends' => ['yii\web\JqueryAsset']]);
$this->registerJsFile($baseUrl."/js/project/catalog/selectProduct.js?v=1.2", ['depends' => ['yii\jui\JuiAsset','yii\bootstrap\BootstrapPluginAsset']]);

$this->registerJsFile($baseUrl."/js/ajaxfileupload.js", ['depends' => ['yii\jui\JuiAsset','yii\bootstrap\BootstrapPluginAsset']]);
$this->registerJsFile($baseUrl."/js/project/inventory/import_file.js", ['depends' => ['yii\jui\JuiAsset','yii\bootstrap\BootstrapPluginAsset']]);
$this->registerJsFile($baseUrl."/js/project/inventory/text_import.js", ['depends' => ['yii\jui\JuiAsset','yii\bootstrap\BootstrapPluginAsset']]);
$this->registerJsFile($baseUrl."/js/jquery.watermark.min.js", ['depends' => ['yii\jui\JuiAsset','yii\bootstrap\BootstrapPluginAsset']]);
$this->registerCssFile($baseUrl."/css/inventory/inventory.css");
$this->registerJs("inventory.stockOut.init();" , \yii\web\View::POS_READY);

$this->registerJs("inventory.stockOut.prodStatus=new Array();" , \yii\web\View::POS_READY);
foreach ($prodStatus as $k=>$v){
$this->registerJs("inventory.stockOut.prodStatus.push({'key':'".$k."','value':'".$v."'})",\yii\web\View::POS_READY);
}
$this->registerJs("$.initQtip();" , \yii\web\View::POS_READY);
?>
<style>
.content-wrapper{
	width: 100%;
}
.div_inner_td{
	width: 100%;
}
</style>
<FORM id="create_stockOut_data_form">
	<table style="width: 100%;margin-bottom:5px;font-size:12px;">
		<tr>
			<td style="vertical-align: middle;width:25%;">
				<div class="div_inner_td">
  					<label style="width:60px;"><?=TranslateHelper::t('仓库位置') ?></label>
  					<SELECT name="warehouse_id" value="" class="eagle-form-control" style="width:200px;">
		  			<?php foreach($warehouse as $wh_id=>$wh_name){
						echo "<option value='".$wh_id."' >".$wh_name."</option>";						
					} ?>
		  			</SELECT>
		  		</div>
		  	</td>

		  	<td style="vertical-align: middle;width:25%;">
		  		<div class="div_inner_td">
  					<label for="stock_change_id" style="width:60px;"><?= TranslateHelper::t('出库单号')?></label>
		  			<input type="text" id="stock_change_id" class="eagle-form-control" name="stock_change_id" value="<?=InventoryHelper::getNewAutoIncrementStockChangeId('SO')?>" style="width:200px;">
		  			<span qtipkey="auto_increment_stock_out_id"></span>
		  		</div>
		  	</td>

		  	<td style="vertical-align: middle;width:25%;">
		  		<div class="div_inner_td">
  					<label style="width:60px;"><?=TranslateHelper::t('操作类型') ?></label>
		  			<input name="stockChangeType" value="2" readonly hidden="hidden">
		  			<input  value="<?=TranslateHelper::t('出库') ?>" style="width:200px;" disabled="disabled" class="eagle-form-control">
		  		</div>
		  	</td>
		  	
		  	<td style="vertical-align: middle;width:25%;">
		  		<div class="div_inner_td">
  					<label style="width:60px;"><?=TranslateHelper::t('操作原因') ?></label>
  					<SELECT name="stockChangeReason" value="" class="eagle-form-control" style="width:200px;">
		  			<?php foreach($stockChangeReason as $k=>$v){
						echo "<option value='".$k."' >".$v."</option>";
					} ?>
		  			</SELECT>
	  			</div>
	  		</td>
		</tr>
		<tr>
			<td style="vertical-align:top;width:25%;">
		  		<div class="div_inner_td">
  					<label style="width:60px;"><?=TranslateHelper::t('操作人员') ?></label>
			  		<input type="text" name="user_name" disabled="disabled" class="eagle-form-control" value="<?=\Yii::$app->user->identity->getFullName() ?>" style="width:200px;">
		  		</div>
		  	</td>
		  	
		  	<td colspan="3" style="vertical-align:top;">
		  		<div class="div_inner_td">
					<label for="comment" style="width:60px;float:left;"><?= TranslateHelper::t('出库备注')?></label>
					<textarea id="comment" name="comment" class="form-control" style="width:600px !important;height:50px !important;"></textarea>
				</div>
			</td>
		</tr>
	</table>
	<div style="width:100%;">
		<button type="button" class="btn-xs btn-warning" onclick="inventory.stockOut.selectStockOutProd()" style="border-style:none;" data-loading-text="<?= TranslateHelper::t('查询中...')?>">
  			<span class="glyphicon glyphicon-search" aria-hidden="true"></span>
			    <?= TranslateHelper::t('选择产品')?>
	    </button>
	    <button type="button" class="btn-xs btn-warning" onclick="inventory.stockOut.scanningStockOutProd()" style="border-style:none;" data-loading-text="<?= TranslateHelper::t('查询中...')?>">
  			<span class="glyphicon glyphicon-search" aria-hidden="true"></span>
			    <?= TranslateHelper::t('扫描产品')?>
	    </button>
		<button type="button" class="btn-xs btn-primary" id="btn_stockOut_import_text" style="border-style:none;">
			<span class="glyphicon glyphicon-list-alt" aria-hidden="true"></span>
			<?= TranslateHelper::t('复制粘贴excel格式')?>
		</button>
		<button type="button" class="btn-xs btn-info" id="btn_stockOut_import_excel" style="border-style:none;">
			<span class="glyphicon glyphicon-folder-open" aria-hidden="true"></span>
			<?= TranslateHelper::t('Excel导入')?>
		</button>
  	</div>
  	<table id="stockOut_prodList_tb" class="table table-hover" style="width:100%;margin:5px 0px">
  		<tr>
  			<th width="80px"><?=TranslateHelper::t('图片') ?></th>
			<th width="150px"><?=TranslateHelper::t('sku') ?></th>
			<th width="250px"><?=TranslateHelper::t('产品名称') ?></th>
			<!--
			<th width="100px"><?=TranslateHelper::t('状态') ?></th>
			-->
			<th width="100px"><?=TranslateHelper::t('在库数量') ?></th>
			<th width="100px"><?=TranslateHelper::t('出库数量') ?></th>
			<th width="100px"><?=TranslateHelper::t('货架位置') ?></th>
			<th width="70px"><?=TranslateHelper::t('操作') ?></th>
  		</tr>		
  		<tr><td colspan="7" style="text-align:center;"><b style="color:red;"><?= TranslateHelper::t('没有选择具体产品，不能保存') ?></b></td></tr>
  	</table>
	<div class="sku_name_area" style="display:none;"></div>
  	<div style="width:100%;position:relative;">
  		<span qtipkey="save_stock_out" style="float:right;padding:6px 0px;"></span>
  		<button type="button" class="btn btn-success" id="btn_create_new_stockOut" onclick="inventory.stockOut.create_stockOut()" style="float:right;" data-loading-text="<?= TranslateHelper::t('保存中...')?>" disabled="disabled">
	    	<span class="glyphicon glyphicon-floppy-disk" aria-hidden="true"></span>
	    	<?= TranslateHelper::t('保存')?>
	   </button>
  	</div>	
</FORM>

<!-- response Msg dialog -->
<div class="stockOut_created_result"></div>
<!-- /dialog -->
<!-- show import_excelFormatText_dialog -->
<div class="import_excelFormatText_dialog"></div>
<!-- /dialog -->
<input id="data_empty_message" type="hidden" value="<?=TranslateHelper::t('无输入数据,请重新输入') ?>">