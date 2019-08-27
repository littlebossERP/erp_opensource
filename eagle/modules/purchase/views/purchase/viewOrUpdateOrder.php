<?php
use eagle\modules\util\helpers\TranslateHelper;
use eagle\modules\purchase\helpers\PurchaseHelper;
use eagle\modules\purchase\helpers\PurchaseShippingHelper;

$this->registerJsFile(\Yii::getAlias('@web')."/js/project/purchase/purchase/purchaseOrderViewOrUpdate.js");
$this->registerJs("purchaseOrder.updateorview.initWidget()", \yii\web\View::POS_READY);

$this->registerJs("$.initQtip();" , \yii\web\View::POS_READY);

$shippingModels=PurchaseShippingHelper::getAllShippingModes();
foreach ($shippingModels as $shipping){
	$shippings[$shipping['shipping_id']]=$shipping['shipping_name'];
}

$shippingname = PurchaseShippingHelper::getShippingModeById($model->delivery_method);
if($shippingname<>null)
	$shippingname = $shippingname->shipping_name;
else
	$shippingname='';

$optionStr="";
if ($mode=="view") {
	$optionStr="readonly";
}
?>

<form id="update-purchase-form" method="post" class="form-group" > 
	<!--main table -->
	<div style="width:100%;clear:both;/*padding: 5px 0px;border-bottom: 1px solid #e5e5e5；*/">
		<table border="0" cellpadding="0" cellspacing="0" class="table purchase-info-table" style="width:100%;clear:both;/*padding:10px 16px 1px 16px;*/ margin:0px;">
			<tr>
				<th colspan="8"><?=TranslateHelper::t('采购单主要信息:')?></th>
			</tr>
			<tr>
				<td style="width:60px;text-align:right"><label for="purchase_order_id"><?php echo TranslateHelper::t('采购单号')?></label> </td>							
				<td style="width:160px;text-align:left"><input id="purchase_order_id" class="eagle-form-control" type="text" name="purchase_order_id" readonly value="<?= $model->purchase_order_id?>" /></td>
			
				<td style="width:60px;text-align:right"><label for="warehouse_id"><?php echo TranslateHelper::t('仓库')?></label> </td>							
				<td style="width:160px;text-align:left">
				<?php if ($mode=="view"){?>
					<input id="warehouse_id" class="eagle-form-control" type="text" required="required" name="warehouse_id" <?=$optionStr ?> value="<?= $warehouse[empty($model->warehouse_id)?0:$model->warehouse_id] ?>" />
				<?php }elseif ($mode=="edit") { ?>
					<select id='warehouse_id' name='warehouse_id' value="<?=$model->warehouse_id ?>" class='eagle-form-control' required="required">
						<?php foreach($warehouse as $k=>$v){
							echo "<option value='$k'";
							echo ($k==$model->warehouse_id)?"selected":'';
							echo ">$v</option>";
							}?>
					</select>
				<?php } ?>
				</td>
				
				<td style="width:60px;text-align:right"><label for="capture_user_name"><?php echo TranslateHelper::t('采购人员')?></label> </td>							
				<td style="width:160px;text-align:left"><input id="capture_user_name" class="eagle-form-control" type="text" required="required" name="capture_user_name" readonly value="<?= $model->capture_user_name?>" /></td>
			
				<td style="text-align:right;width:80px;"><label for="status"><?php echo TranslateHelper::t('采购状态')?></label><span qtipkey="purchase_status"></span></td>							
				<td style="text-align:left;width:140px;">
				<?php if ($mode=="view"){?>
					<input id="status" class="eagle-form-control" type="text" name="status"<?=$optionStr ?> value="<?= $statusIdLabelMap[$model->status]?>" />
				<?php }
					elseif ($mode=="edit") {
						echo "<select id='status' name='status' value='".$statusIdLabelMap[$model->status]."' class='eagle-form-control'>";
						foreach($statusIdLabelMap as $k=>$v){
                            if($v != '已入库' && $v != '已作废'){
    							$select='';
    							if($k==$model->status){
    								$select=' selected="selected" ';
    							}
    							echo "<option value='$k' $select>$v</option>";
							}
						}
						echo "</select>";
					}?>
				</td>
			
			</tr>

			<tr>
				<td style="text-align:right"><label for="amount_subtotal"><?php echo TranslateHelper::t('货款')?></label> </td>							
				<td style="text-align:left"><input id="amount_subtotal" class="eagle-form-control" type="text" name="amount_subtotal" readonly <?=$optionStr ?> value="<?= $model->amount_subtotal?>" /></td>

				<td style="text-align:right"><label for="payment_status"><?php echo TranslateHelper::t('付款状态')?></label> </td>							
				<td style="text-align:left">
				<?php if ($mode=="view"){?>
					<input id="payment_status" class="eagle-form-control" type="text" name="payment_status" <?=$optionStr ?> value="<?= $paymentStatus[$model->payment_status]?>" />
				<?php }
					elseif ($mode=="edit") {
						echo "<select id='payment_status' name='payment_status' value='".$model->payment_status."' class='eagle-form-control'>";
						foreach($paymentStatus as $k=>$v){
							$select='';
							if($k==$model->payment_status){
								$select=' selected="selected" ';
							}
							echo "<option value='$k' $select>$v</option>";
						}
						echo "</select>";
					}?>
				</td>
				
				<td style="text-align:right"><label for="pay_date"><?php echo TranslateHelper::t('付款时间')?></label> </td>							
				<td style="text-align:left"><input id="pay_date" class="eagle-form-control" type="text"  data-format="dd/MM/yyyy hh:mm:ss" name="pay_date" <?=$optionStr ?> value="<?=$model->pay_date?>" /></td>
				
				<td style="text-align:right"><label for="delivery_fee"><?php echo TranslateHelper::t('运费')?></label> </td>							
				<td style="text-align:left"><input id="delivery_fee" class="form-control" type="text"  name="delivery_fee" <?=$optionStr ?> value="<?= $model->delivery_fee?>" /></td>
			</tr>
			<tr class="" >
				<td style="text-align:right"><label for="delivery_method"><?php echo TranslateHelper::t('物流方式')?></label> </td>							
				<td style="text-align:left">
				<?php if ($mode=="view"){ ?>
					<input id="delivery_method" type="text" class="form-control" required="required" name="delivery_method" <?=$optionStr ?> value="<?=$shippingname ?>" />
				<?php }
					elseif ($mode=="edit") {
						echo "<select id='delivery_method' name='delivery_method' value='' class='form-control'>";
						foreach($shippings as $k=>$v){
							$select='';
							if(is_numeric($model->delivery_method) && $k==$model->delivery_method){
								$select=' selected ';
							}
							echo "<option value='$v' $select>$v</option>";
						}
						echo "</select>";
					}?>
				</td>
				
				<td style="text-align:right"><label for="delivery_number"><?php echo TranslateHelper::t('物流号码')?></label> </td>							
				<td style="text-align:left"><input id="delivery_number" class="form-control" type="text" name="delivery_number" <?=$optionStr ?> value="<?= $model->delivery_number?>" /></td>
				
				<td style="text-align:right;width:80px;"><label for="supplier_name"><?php echo TranslateHelper::t('供应商')?></label></td>							
				<?php if ($mode=="view"){ ?>
				<td style="text-align:left"><input id="supplier_name" required="required" class="form-control" type="text" name="supplier_name" <?=$optionStr ?> value="<?= $model->supplier_name?>" /></td>
				<?php }elseif ($mode=="edit") { ?>
				<td style="text-align:left;width:140px;;">
					<select id="supplier_name" required="required" class="form-control" name="supplier_name" value="">
					<?php foreach ($suppliers as $s){ ?>
					<option value="<?=$s ?>" <?=($model->supplier_name==$s)?"selected":"" ?>><?=$s ?></option>
					<?php } ?>
					</select>
				</td>
				<?php } ?>
				<td colspan="2"></td>
			</tr>
			<tr class="" >
				<td style="text-align:right"><label for="purchase_source_id"><?php echo TranslateHelper::t('供应商单号')?></label> </td>							
				<td style="text-align:left"><input id="purchase_source_id" class="form-control" type="text" name="purchase_source_id" <?=$optionStr ?> value="<?= $model->purchase_source_id?>" /></td>

				<td style="text-align:right"><label for="create_time"><?php echo TranslateHelper::t('采购时间')?></label> </td>							
				<td style="text-align:left"><input id="create_time" class="form-control" type="text" name="create_time" <?=$optionStr ?> value="<?= $model->create_time?>" /></td>
			
				<td style="text-align:right"><label for="expected_arrival_date"><?php echo TranslateHelper::t('预期到达日期')?></label> </td>							
				<td style="text-align:left"><input id="expected_arrival_date" class="form-control" type="text" name="expected_arrival_date" <?=$optionStr ?> value="<?= $model->expected_arrival_date?>" /></td>
				<td colspan="2"></td>
			</tr>
			<tr class="" >
			<?php if ($mode=="view"){ ?>
				<td style="text-align:right"><label for="comment"><?php echo TranslateHelper::t('备注')?></label> </td>							
				<td colspan="5" style="text-align:left"><div id="comment" style="border:1px solid #ccc;padding-left:10px;cursor:not-allowed;background-color: #eee;min-height:40px;" name="comment" <?php echo $optionStr; ?> ><?=$model->comment ?></div></td>
			<?php } ?>
			<?php if ($mode=="edit"){ ?>
				<td style="width:10%;text-align:right"><label for="comment"><?php echo TranslateHelper::t('添加备注')?></label> </td>							
				<td colspan="5" style="text-align:left"><textarea id="comment" class="form-control" type="text" name="comment" value="" ></textarea></td>
			<?php } ?>
			
			</tr>
		</table>
		<!--/main table -->
	</div>
	<!--items table -->
	<div style="width:100%;clear:both;padding-top:5px;">
		<table style="width:100%;" class="table">
			<tr>
				<th colspan="7"><?=TranslateHelper::t('采购单货品列表:')?></th>
			</tr>
		</table>
		<table style="width:100%;font-size:12px;" class="table-hover table purchase_order_prods">
			<tr style="width:100%;">
				<td style="width:50px;">图片</td>
				<td style="width:190px;">货品sku</td>
				<td style="width:290px;">货品名称</td>
				<td style="width:100px;">采购数量</td>
				<td style="width:100px;">采购单价(人民币)</td>
				<td style="width:100px;">采购成本(人民币)</td>
				<td style="width:100px;">备注</td>
			</tr>
			<?php 
			$index=0;
			$textarea_html='';
			foreach($detail as $item){ 
				$textarea_html .= "<textarea class='hide' name='prod[".$index."][sku]' style='display:none'>".$item['sku']."</textarea>".
							"<textarea class='hide' name='prod[".$index."][name]' style='display:none'>".$item['name']."</textarea>";
			?>
			<tr>
				<td><img src="<?=$item['img']?>" style="width:50px ! important;height:50px ! important"></td>
				<td><?= (empty($item['purchase_link']) ? $item['sku'] : '<a href="'.$item['purchase_link'].'" target="_blank">'.$item['sku'].'</a>') ?></td>
				<td><?=$item['name']?></td>
				<td><input name="prod[<?=$index?>][qty]" <?php echo $optionStr; ?> value="<?=$item['qty']?>" index="<?=$index?>" class="form-control"/></td>
				<td><input name="prod[<?=$index?>][price]" <?php echo $optionStr; ?> value="<?=$item['price']?>" index="<?=$index?>" class="form-control"/></td>
				<td><input name="prod[<?=$index?>][amount]" value="<?=$item['amount']?>" readonly index="<?=$index?>" class="form-control"/></td>
				<td><input name="prod[<?=$index?>][remark]" <?php echo $optionStr; ?> value="<?=$item['remark']?>" index="<?=$index?>" class="form-control"/></td>
			</tr>
			<?php 
			$index++;
			}?>
		</table>
		<div class="sku_name_area" style="display:none;">
		<?=$textarea_html ?>
		</div>
	</div>
	<!--/items table -->
</form>
<!-- /modal-body -->

<!-- <div class="modal-footer"> -->
<div class="modal-footer" style="padding: 15px 0 0 0;">
	<?php if($mode=="view") :?>
	<?php if($model->status < PurchaseHelper::ALL_ARRIVED){ ?>
	<a class="btn btn-primary" onclick="purchaseOrder.list.editPurchaseOrder('<?= $model->id ?>')"><?= TranslateHelper::t('修改')?></a>
    <?php } ?>
    <a class="btn btn-default" data-dismiss="modal"><?= TranslateHelper::t('关闭')?></a>
	<?php elseif($mode=="edit") :?>
	<a class="btn btn-default" data-dismiss="modal"><?= TranslateHelper::t('取消')?></a>
	<a class="btn btn-primary" onclick="purchaseOrder.updateorview.updatePurchaseOrder('<?= $model->id ?>')"><?= TranslateHelper::t('保存')?></a>
	<?php endif;?>
</div><!-- /modal-footer -->