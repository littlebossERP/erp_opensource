<?php
use eagle\modules\util\helpers\TranslateHelper;
use eagle\modules\purchase\models\ShippingMode;
use eagle\modules\purchase\helpers\PurchaseShippingHelper;

$baseUrl = \Yii::$app->urlManager->baseUrl;
$this->registerJsFile($baseUrl."/js/project/purchase/purchase/purchaseOrderCreate.js", ['depends' => ['yii\jui\JuiAsset','yii\bootstrap\BootstrapPluginAsset']]);
$this->registerJs("purchaseOrder.create.initWidget();", \yii\web\View::POS_READY);

$this->registerJsFile($baseUrl."/js/project/catalog/selectProduct.js", ['depends' => ['yii\jui\JuiAsset','yii\bootstrap\BootstrapPluginAsset']]);
$this->registerJsFile($baseUrl."/js/ajaxfileupload.js", ['depends' => ['yii\jui\JuiAsset','yii\bootstrap\BootstrapPluginAsset']]);
$this->registerJsFile($baseUrl."/js/jquery.watermark.min.js", ['depends' => ['yii\jui\JuiAsset','yii\bootstrap\BootstrapPluginAsset']]);

$this->registerJsFile($baseUrl."/js/project/purchase/purchase/import_file.js", ['depends' => ['yii\jui\JuiAsset','yii\bootstrap\BootstrapPluginAsset']]);
$this->registerJsFile($baseUrl."/js/project/purchase/purchase/text_import.js", ['depends' => ['yii\jui\JuiAsset','yii\bootstrap\BootstrapPluginAsset']]);
$this->registerJs("$.initQtip();" , \yii\web\View::POS_READY);

$shippingModels=PurchaseShippingHelper::getAllShippingModes();
foreach ($shippingModels as $shipping){
	$shippings[$shipping['shipping_id']]=$shipping['shipping_name'];
}
?>
<!-- <div class="modal-header"> -->

<form id="create-purchase-form" method="post" class="form-group" > 
	<!--main table -->
	<div style="width:100%;clear:both;/*border-bottom: 1px solid #e5e5e5;*/">
		<table border="0" cellpadding="0" cellspacing="0" class="table purchase-info-table" style="width:100%;clear:both;margin: 0px;">
			<tr>
				<th colspan="8"><?=TranslateHelper::t('采购单主要信息:')?></th>
			</tr>
			<tr>
				<td style="width:60px;text-align:right"><label for="purchase_order_id"><?php echo TranslateHelper::t('采购单号')?></label> </td>							
				<td style="width:160px;text-align:left"><input id="purchase_order_id" class="eagle-form-control" type="text" name="purchase_order_id" value="<?=empty($model->purchase_order_id)?'':$model->purchase_order_id ?>" /></td>
			
				<td style="width:80px;text-align:right"><label for="warehouse_id"><?php echo TranslateHelper::t('仓库')?></label> <span qtipkey="purchase_warehouse"></span></td>							
				<td style="width:140px;text-align:left"><select id='warehouse_id' name='warehouse_id' value="" class='eagle-form-control' required="required" style="float:left;width:100%;">
						<?php foreach($warehouse as $k=>$v){
							echo "<option value='$k'>$v</option>";
						}?>
					</select>
				</td>
				<td style="width:60px;text-align:right"><label for="capture_user_name"><?php echo TranslateHelper::t('采购人员')?></label> </td>							
				<td style="width:160px;text-align:left">
					<input id="capture_user_name" class="eagle-form-control" type="text" required="required" readonly name="capture_user_name" 
							value="<?php 
									$userFullName = \Yii::$app->user->identity->getFullName();
									$userEmail = \Yii::$app->user->identity->getEmail();
									if(empty($userFullName))
										echo $userEmail;
									else
										echo $userFullName; ?>" />
				</td>
				<td style="width:80px;text-align:right"><label for="status"><?php echo TranslateHelper::t('采购状态')?></label><span qtipkey="purchase_status"></span></td>	
				<td style="width:140px;text-align:left">
					<select id='status' name='status' value="" class='eagle-form-control'>
						<?php foreach($statusIdLabelMap as $k=>$v){
						    if($v != '已入库' && $v != '已作废')
							    echo "<option value='$k'>$v</option>";
						}?>
					</select>
				</td>
			</tr>
			<tr>
				<td style="text-align:right"><label for="amount_subtotal"><?php echo TranslateHelper::t('货款')?></label> </td>							
				<td style="text-align:left"><input id="amount_subtotal" class="eagle-form-control" type="text" name="amount_subtotal" readonly value="" /></td>
			
				<td style="text-align:right"><label for="payment_status"><?php echo TranslateHelper::t('付款状态')?></label> </td>							
				<td style="text-align:left">
					<select id='payment_status' name='payment_status' value="" class='eagle-form-control'>
						<?php foreach($paymentStatus as $k=>$v){
							echo "<option value='$k'>$v</option>";
						}?>
					</select>
				
				<td style="text-align:right"><label for="pay_date"><?php echo TranslateHelper::t('付款时间')?></label> </td>							
				<td style="text-align:left"><input id="pay_date" class="eagle-form-control" type="text" name="pay_date" value="" /></td>
				
				<td style="text-align:right"><label for="delivery_fee"><?php echo TranslateHelper::t('运费')?></label> </td>							
				<td style="text-align:left"><input id="delivery_fee" class="eagle-form-control" type="text"  name="delivery_fee" value="" /></td>
				
			</tr>
			<tr>
				<td style="text-align:right"><label for="delivery_method"><?php echo TranslateHelper::t('物流方式')?></label> </td>							
				<td style="text-align:left">
					<select id='delivery_method' name='delivery_method' value="" class='eagle-form-control'>
						<?php foreach($shippings as $k=>$v){
							echo "<option value='$v' ".((is_numeric($model->delivery_method) && $model->delivery_method == $k)?"selected":"").">$v</option>";
						}?>
					</select>
				</td>
				
				<td style="text-align:right"><label for="delivery_number"><?php echo TranslateHelper::t('物流单码')?></label> </td>							
				<td style="text-align:left"><input id="delivery_number" class="eagle-form-control" type="text" name="delivery_number" value="" /></td>
			
				<td style="text-align:right;width:80px;"><label for="supplier_name"><?php echo TranslateHelper::t('供应商')?></label></td>							
				<td style="text-align:left;width:140px;/*position:relative;*/">
					<select id="supplier_name" required="required" class="eagle-form-control" name="supplier_name" value="">
					<?php foreach ($suppliers as $s){ ?>
					<option value="<?=$s ?>" ><?=$s ?></option>
					<?php } ?>
					</select>
				</td>
				
				<td colspan="2"></td>
			</tr>
			<tr>
				<td style="text-align:right"><label for="purchase_source_id"><?php echo TranslateHelper::t('供应商单号')?></label> </td>							
				<td style="text-align:left"><input id="purchase_source_id" class="eagle-form-control" type="text" name="purchase_source_id" value="" /></td>
				
				<td style="text-align:right"><label for="create_time"><?php echo TranslateHelper::t('采购时间')?></label> </td>							
				<td style="text-align:left"><input id="create_time" class="eagle-form-control" type="text" name="create_time" value="" placeholder="<?= TranslateHelper::t('留空则默认当前时刻')?>"/></td>

				<td style="text-align:right"><label for="expected_arrival_date"><?php echo TranslateHelper::t('预期到达')?></label> </td>							
				<td style="text-align:left"><input id="expected_arrival_date" class="eagle-form-control" type="text" name="expected_arrival_date" value="" /></td>
				<td colspan="2"></td>
			</tr>
			<tr class="" >
				<td style="text-align:right"><label for="comment"><?php echo TranslateHelper::t('添加备注')?></label> </td>							
				<td colspan="5" style="text-align:left"><textarea id="comment" class="form-control" type="text" name="comment"></textarea></td>
			</tr>
		</table>
		<!--/main table -->
	</div>
	<!--items table -->
	<div style="width:100%;clear:both;padding-top:5px;">
		<table style="width:100%;margin:0px" class="table">
			<tr>
				<th colspan="7"><?=TranslateHelper::t('采购单货品列表:')?></th>
			</tr>
		</table>
		<!--tool buttons -->
		<div style="margin:5px 0px;">
			<button type="button" class="btn-xs btn-warning" onclick="purchaseOrder.create.selectPurchaseProd()" style="border-style: none;" data-loading-text="<?= TranslateHelper::t('查询中...')?>">
	  			<span class="glyphicon glyphicon-search" aria-hidden="true"></span>
				<?= TranslateHelper::t('选择产品')?>
		    </button>
		    <button type="button" class="btn-xs btn-primary" id="btn_purchase_import_text" style="border-style: none;">
				<span class="glyphicon glyphicon-list-alt" aria-hidden="true"></span>
	    		<?= TranslateHelper::t('复制粘贴excel格式')?>
			</button>
		    <button type="button" class="btn-xs btn-info" id="btn_purchase_import_product" style="border-style: none;">
				<span class="glyphicon glyphicon-folder-open" aria-hidden="true"></span>
	    		<?= TranslateHelper::t('Excel导入')?>
			</button>
		</div>
		<table id="purchase_order_prods_tb" style="width:100%;font-size:12px;margin:0px" class="table-hover table">
			<tr>
				<td width="50px"><?=TranslateHelper::t('图片') ?></td>
				<td width="150px"><?=TranslateHelper::t('货品sku') ?></td>
				<td width="250px"><?=TranslateHelper::t('货品名称') ?></td>
				<td width="100px"><?=TranslateHelper::t('采购数量') ?></td>
				<td width="80px"><?=TranslateHelper::t('单价(人民币)') ?></td>
				<td width="100px"><?=TranslateHelper::t('总成本(人民币)') ?></td>
				<td width="100px"><?=TranslateHelper::t('备注') ?></td>
				<td width="80px"><?=TranslateHelper::t('操作') ?></td>
			</tr>
			<tr><td colspan="8" style="text-align:center;"><b style="color:red;">没有选择具体产品，不能保存</b></td></tr>
		</table>
		<div class="sku_name_area" style="display:none;"></div>
	</div>
	<!--/items table -->
</form>
<!-- /modal-body -->

<!-- <div class="modal-footer"> -->
<div class="modal-footer" style="padding: 15px 0 0 0;">
	<a class="btn btn-danger" data-dismiss="modal"><?= TranslateHelper::t('取消')?></a>
	<a class="btn btn-success" id="save_purchase_btn" onclick="purchaseOrder.create.createPurchaseOrder()" disabled="disabled"><?= TranslateHelper::t('保存')?></a><span qtipkey="purchase_save"></span>
</div><!-- /modal-footer -->


<!-- show import_excelFormatText_dialog -->
	<div class="import_excelFormatText_dialog"></div>
	<!-- /dialog -->

