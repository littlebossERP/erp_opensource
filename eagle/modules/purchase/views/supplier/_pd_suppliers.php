<?php
use eagle\modules\util\helpers\TranslateHelper;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\jui\Dialog;
use yii\data\Sort;
use yii\jui\JuiAsset;
use eagle\modules\purchase\helpers\PurchaseHelper;

$baseUrl = \Yii::$app->urlManager->baseUrl;
$this->registerJsFile($baseUrl."/js/jquery.json-2.4.js", ['depends' => ['yii\jui\JuiAsset','yii\bootstrap\BootstrapPluginAsset']]);
$this->registerJsFile($baseUrl."/js/project/purchase/supplier/pd_suppliers.js", ['depends' => ['yii\web\JqueryAsset']]);

$this->registerJsFile($baseUrl."/js/project/catalog/selectProduct.js", ['depends' => ['yii\jui\JuiAsset','yii\bootstrap\BootstrapPluginAsset']]);
$this->registerJsFile($baseUrl."/js/ajaxfileupload.js", ['depends' => ['yii\jui\JuiAsset','yii\bootstrap\BootstrapPluginAsset']]);
$this->registerJsFile($baseUrl."/js/project/purchase/supplier/import_file.js", ['depends' => ['yii\jui\JuiAsset','yii\bootstrap\BootstrapPluginAsset']]);
$this->registerJsFile($baseUrl."/js/project/purchase/supplier/text_import.js", ['depends' => ['yii\jui\JuiAsset','yii\bootstrap\BootstrapPluginAsset']]);
$this->registerJsFile($baseUrl."/js/jquery.watermark.min.js", ['depends' => ['yii\jui\JuiAsset','yii\bootstrap\BootstrapPluginAsset']]);

$this->registerJs("pd_suppliers.list.init();" , \yii\web\View::POS_READY);
$this->registerJs("$.initQtip();" , \yii\web\View::POS_READY);
$supplier_id = empty($_REQUEST['supplier_id'])?'':$_REQUEST['supplier_id'];
?>

<style>
.create_or_edit_pdSuppliers_win .modal-dialog{
	width: 900px;
	max-height: 700px;
	overflow-y: auto;	
}
.create_new_pd_suppliers .modal-dialog{
	max-height: 700px;
	overflow-y: auto;
}
.import_excelFlie_dialog .modal-dialog{
	max-height: 700px;
	overflow-y: auto;
}
.import_excelFormatText_dialog .modal-dialog{
	max-height: 700px;
	overflow-y: auto;
}
.div_inner_td{
	width: 100%;
}
.pdSuppliers_list th, .pdSuppliers_list td{
	padding: 4px !important;
  	vertical-align: middle !important;
	border: 0px !important;
}

.pdSuppliers_list .btn-xs{
	padding:0px 3px !important;
	margin:0px !important;
}
</style>
<FORM action="<?= Url::to(['/purchase/supplier/'.yii::$app->controller->action->id])?>" method="GET" style="width:100%;float:left">
	<div class="div-input-group" style="float: left;margin-left:5px;">
  		<div style="float:left;" class="input-group" title="<?= TranslateHelper::t('按有无设置供应价滤显示结果') ?>">
  			<SELECT name="hasPriceOnly" value="" style="width:150px;margin:0px" class="eagle-form-control">
	  			<OPTION value="1" <?=(isset($_GET['hasPriceOnly']) && $_GET['hasPriceOnly']==1)?" selected ":''?>>
					<?=TranslateHelper::t('只显示有报价的产品')?>
				</OPTION>
				<OPTION value="0" <?=(!isset($_GET['hasPriceOnly']) or $_GET['hasPriceOnly']==0)?" selected ":''?>>
					<?=TranslateHelper::t('显示所有产品')?>
				</OPTION>
  			</SELECT>
  		</div>
  	</div>

	<div class="div-input-group" style="float: left;margin-left:5px;">
  		<div style="float:left;" class="input-group" title="<?= TranslateHelper::t('按产品品牌过滤显示结果') ?>">
  			<SELECT name="brand_id" value="" style="width:150px;margin:0px" class="eagle-form-control">
  				<OPTION value=""><?= TranslateHelper::t('品牌') ?></OPTION>
  				<?php foreach($prodBrands as $k=>$v){
					echo "<option value='".$k."'";
					if(isset($_GET['brand_id']) && $_GET['brand_id']==$k && is_numeric($_GET['brand_id'])) echo " selected ";
					echo ">".$v."</option>";
				} ?>
  			</SELECT>
  		</div>
  	</div>
		
	<div class="div-input-group" style="float: left;margin-left:5px;">
  		<div style="float:left;" class="input-group" title="<?= TranslateHelper::t('按供应商过滤显示结果') ?>">
  			<SELECT name="supplier_id" value="" style="width:150px;margin:0px" class="eagle-form-control">
	  			<OPTION value=""><?= TranslateHelper::t('供应商') ?></OPTION>
	  				<?php foreach($pdSuppliers as $k=>$v){
						echo "<option value='".$k."'";
						if(isset($_GET['supplier_id']) && $_GET['supplier_id']==$k && is_numeric($_GET['supplier_id'])) echo " selected ";
						echo ">".$v."</option>";
					} ?>
  			</SELECT>
  		</div>
  	</div>

  	<div class="div-input-group" style="float: left;margin-left:5px;">
	  	<div style="float:left;" class="input-group" title="<?= TranslateHelper::t('输入完整或部分sku字段过滤显示结果') ?>">
	  		<input name="keyword" class="eagle-form-control" type="text" placeholder="<?= TranslateHelper::t('完整或部分sku字段查询')?>" value="<?php if(isset($_GET['keyword'])) echo $_GET['keyword'] ?>"  style="width:160px;float:left;margin:0px"/>
	  		<div class="div-input-group" style="float: left;margin-left:5px;">
	  			<button type="submit" class="btn btn-default" data-loading-text="<?= TranslateHelper::t('查询中...')?>" style="margin:0px 0px 0px 5px;padding:0px;height:28px;width:30px;border-radius:0px;border:1px solid #b9d6e8;">
					<span class="glyphicon glyphicon-search" aria-hidden="true"></span>
			    </button>
			</div>
			<div class="div-input-group" style="float: left;margin-left:5px;">
			    <button type="button" id="btn_clear" class="btn btn-default" style="margin-left:5px;padding: 0px;height: 28px;width: 30px;border-radius: 0px;border: 1px solid #b9d6e8;" title="<?= TranslateHelper::t('重置') ?>">
					<span class="glyphicon glyphicon-refresh" aria-hidden="true"></span>
				</button>
			</div>
		</div>
  	</div>
</FORM>

<div style="width:100%;float:left;margin:10px 0px;">
	<div style="float:left;">
	   <button id="create_new_pdSupplier" type="button" class="btn-xs btn-transparent font-color-1">
	   	<span class="glyphicon glyphicon-plus"></span>
	   	<?=TranslateHelper::t('选择产品新建报价') ?>
	   </button>
	   <span qtipkey="general-create-pdsupplier" style="width:15px;"></span>
	</div>
	<div style="margin-left:20px;float:left;">
		<button type="button" class="btn-xs btn-transparent font-color-1" onclick="pd_suppliers.list.pdSuppliers_excel_import()">
			<span class="glyphicon glyphicon-folder-open"></span>
			<?=TranslateHelper::t('导入excel新建报价') ?>
		</button>
		<span qtipkey="excel-import-pdsupplier" style="width:10px;"></span>
	</div>
	<div style="margin-left:20px;float:left;">
		<button type="button" class="btn-xs btn-transparent font-color-1" onclick="pd_suppliers.list.pdSuppliers_text_import()">
			<span class="glyphicon glyphicon-list-alt"></span>
			<?=TranslateHelper::t('复制粘贴excel格式新建报价') ?>
		</button>
		<span qtipkey="text-import-pdsupplier" style="width:15px;"></span>
	</div>
	<div style="margin-left:20px;float:left;">
		<button type="button" class="btn-xs btn-transparent font-color-1" onclick="pd_suppliers.list.batchProductRemovePdSupplier()">
			<span class="egicon-trash" style="height: 16px;"></span>
			<?=TranslateHelper::t('批量删除报价') ?>
		</button>
		<span qtipkey="batch-remove-pdsupplier" style="width:15px;"></span>
	</div>
</div>
<!-- table -->
<div class="pdSuppliers_list" style="width:100%;float:left;">
    <table cellspacing="0" cellpadding="0" style="width:100%;font-size:12px;float:left;" class="table table-hover">
		<tr class="list-firstTr">
		 	<th nowrap width="50px"><?=TranslateHelper::t('商品图片') ?></th>
			<th nowrap width="150px"><?=$sort->link('sku',['label'=>TranslateHelper::t('SKU')]) ?></th>
			<th nowrap width="200px"><?=$sort->link('name',['label'=>TranslateHelper::t('商品名称')])?></th>
			
			<th nowrap width="100px"><?=TranslateHelper::t('商品类型')?></th>

			<th nowrap width="100px"><?=$sort->link('brand_id',['label'=>TranslateHelper::t('品牌')])?></th>
			<th nowrap width="400px"><?=TranslateHelper::t('报价') ?></th>
		</tr>
	<?php if(isset($pdSuppliersListData['data']) && count($pdSuppliersListData['data'])>0): ?>
	<?php foreach($pdSuppliersListData['data'] as $index=>$row):?>
		 <tr <?=!is_int($index / 2)?"class='striped-row'":"" ?>>
			<td><div style="height: 50px;"><img style="max-height: 50px; max-width: 80px;"src="<?=$row['photo_primary'] ?>" /></div></td>
			<td><?=$row['sku'] ?></td>
			<td><?=$row['name'] ?></td>
			<td><?=(empty($prodType[$row['type']])?$row['type']:$prodType[$row['type']]) ?></td>

			<td><?=(empty($prodBrands[$row['brand_id']])?$row['brand_id']:$prodBrands[$row['brand_id']]) ?></td>
			<td>
			<?php if(count($row['pd_supplier_info'])>0) {?>
				<table>
				<?php foreach ($row['pd_supplier_info'] as $i=>$info){
					if($info['priority']==0) $priorityStr=TranslateHelper::t('首选');
					else $priorityStr=TranslateHelper::t('备选').$info['priority'];?>
					<tr <?=($i!==0)?"style='border-top: 1px #b9d6e8 solid;'":'' ?>>
						<td width="30px"><input type="checkbox" class="chk_pd_supplier" value="<?=$info['product_supplier_id'] ?>" sku="<?=base64_encode($row['sku']) ?>"></td>
						<td width="80px"><?=$priorityStr ?></td>
						<td width="170px"><?=(isset($pdSuppliers[$info['supplier_id']]))?$pdSuppliers[$info['supplier_id']]:$info['supplier_id'].TranslateHelper::t('供应商名丢失'); ?></td>
						<td width="60px"><?=$info['purchase_price'] ?></td>
						<td width="60px"><a href="javascript:void(0)" onclick="pd_suppliers.list.productRemovePdSupplier('<?=base64_encode($row['sku']) ?>',<?=$info['product_supplier_id'] ?>)"><?=TranslateHelper::t('删除') ?></a></td>
					</tr>
				<?php }?>
				</table>
			<?php }else{?>
				<table>
					<tr>
						<td width="30px"></td>
						<td width="80px"></td>
						<td width="170px">
							<?php
							if(empty($pdSuppliers[$supplier_id])) echo TranslateHelper::t('供应商(N/A)');
							else echo $pdSuppliers[$supplier_id]; ?>
						</td>
						<td width="60px"><?=$row['purchase_price'] ?></td>
						<td width="60px"></td>
					</tr>
				</table>
			<?php }?>
			</td>
		</tr>
	
	<?php endforeach;?>
	<?php endif; ?>
    </table>

</div>
		
<?php if(isset($pdSuppliersListData['pagination'])):?>
<div id="pager-group">
    <?= \eagle\widgets\SizePager::widget(['pagination'=>$pdSuppliersListData['pagination'] , 'pageSizeOptions'=>array( 5 , 20 , 50 , 100 , 200 ) , 'class'=>'btn-group dropup']);?>
    <div class="btn-group" style="width: 49.6%;text-align: right;">
    	<?=\yii\widgets\LinkPager::widget(['pagination' =>$pdSuppliersListData['pagination'],'options'=>['class'=>'pagination']]);?>
	</div>
</div>
<?php endif;?>

<!-- Modal -->
<div class="create_new_pd_suppliers"></div>
<div class="import_excelFlie_dialog"></div>
<div class="import_excelFormatText_dialog"></div>
<!-- /.modal-dialog -->

