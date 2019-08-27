<?php
use eagle\modules\util\helpers\TranslateHelper;
use eagle\modules\purchase\helpers\PurchaseHelper;
use eagle\modules\purchase\helpers\PurchaseShippingHelper;

$this->registerJsFile(\Yii::getAlias('@web')."/js/project/purchase/purchase/purchaseOrderViewOrUpdate.js");
$this->registerJs("purchaseOrder.updateorview.initWidget()", \yii\web\View::POS_READY);

$this->registerJs("$.initQtip();" , \yii\web\View::POS_READY);

$shippingname = PurchaseShippingHelper::getShippingModeById($model->delivery_method);
if($shippingname<>null)
	$shippingname = $shippingname->shipping_name;
else
	$shippingname='';
?>

<style>
.stock_in_dialog .modal-dialog {
	width: 1000px;
}
div .purchase_product_detail{
	height: 350px;
	overflow-y:scroll;
}
div .tb_title{
	width: 90px;
	display: inline-block;
	font-weight: 600;
    font-size: 15px;
	text-align: right;
}
.purchase_order_prods th{
	font-size: 15px;
    text-align: center;
}
.purchase_product_detail th a{
	color: #428bca;
}
.create_or_edit_purchase_win .modal-dialog{
	max-height: 810px !important;
}
div.purchase_tab{
	border-left:1px solid #ddd;
	border-right:1px solid #ddd;
	border-bottom:1px solid #ddd; 
	padding:10px;
	margin-bottom:10px;
}
div .in_stcok_title{
	cursor: pointer;
}
.right10{
	margin-right: 10px;
}
</style>

<ul class="nav nav-tabs">
	<li class="active">
		<a id='tab1' data-toggle="tab"  >基本信息</a>
	</li>
	<li>
		<a id='tab2' data-toggle="tab" >入库记录</a>
	</li>
</ul>

<div id="purchase_tab1" class="purchase_tab">
	<div style="width:100%;clear:both;">
		<table style="width:100%;font-size:12px;" class="table purchase_order_prods">
			<tr>
				<td style="width: 250px;"><div class="tb_title">采购单号：</div><?= $model->purchase_order_id?></td>
				<td style="width: 250px;"><div class="tb_title">仓库：</div><?= $warehouse[empty($model->warehouse_id)?0:$model->warehouse_id] ?></td>
				<td style="width: 300px;"><div class="tb_title">采购人员：</div><?= $model->capture_user_name?></td>
				<td style="width: 200px;"><div class="tb_title">采购状态：</div><?= empty($purchaseStatus[$model->status]) ? '' : $purchaseStatus[$model->status]?></td>
			</tr>
			<tr>
				<td><div class="tb_title">供应商：</div><?= $model->supplier_name ?></td>
				<td><div class="tb_title">采购时间：</div><?= $model->create_time ?></td>
				<td><div class="tb_title">物流方式：</div><?= $shippingname ?></td>
				<td><div class="tb_title">物流号码：</div><?= $model->delivery_number ?></td>
			</tr>
			<tr>
				<td><div class="tb_title">货款：</div><?= $model->amount_subtotal ?></td>
				<td><div class="tb_title">付款状态：</div><?= empty($paymentStatus[$model->payment_status]) ? '' : $paymentStatus[$model->payment_status] ?></td>
				<td><div class="tb_title">付款时间：</div><?= $model->pay_date ?></td>
				<td><div class="tb_title">运费：</div><?= $model->delivery_fee ?></td>
			</tr>
			<tr>
				<td><div class="tb_title">供应商单号：</div><?= $model->purchase_source_id ?></td>
				<td><div class="tb_title" style="font-size: 12px;">预期到达日期：</div><?= $model->expected_arrival_date ?></td>
				<td></td>
				<td></td>
			</tr>
			<tr>
				<td colspan="2">
					<div class="tb_title" style="float: left">采购备注：</div>
					<div style="border:1px solid #ccc;padding-left:10px; min-height: 40px; max-height:100px; overflow-y: scroll;" ><?=$model->comment ?></div>
				</td>
				<td colspan="2">
					<div class="tb_title" style="float: left">入库备注：</div>
					<div style="border:1px solid #ccc;padding-left:10px; min-height: 40px; max-height:100px; overflow-y: scroll;" ><?=$model->in_stock_comment ?></div>
				</td>
			</tr>
		</table>
	</div>
	<div class="purchase_product_detail" style="width:100%;clear:both;padding-top:5px;">
		<table style="width:100%;font-size:12px;" class="table-hover table purchase_order_prods">
			<tr style="width:100%;">
				<th style="width:500px;">商品信息</th>
				<th style="width:80px;">采购数</th>
				<th style="width:80px;">已入库数</th>
				<th style="width:100px;">采购单价</td>
				<th style="width:100px;">采购成本</td>
				<th style="width:100px;">备注</td>
			</tr>
			<?php
			$purchase_items = array();
			$index=0;
			foreach($detail as $item){
				$surplus_qty = ($item['qty'] - $item['in_stock_qty']) > 0 ? ($item['qty'] - $item['in_stock_qty']) : 0;
				$purchase_items[$item['sku']] = [
					'name' => $item['name'],
					'img' => $item['img'],
				];
			?>
			<tr style="<?= $surplus_qty==0 ? 'background-color: #dff0d8; ' : '' ?>">
				<td>
					<div style="float: left; margin-right: 10px; ">
						<img src="<?=$item['img']?>" style="width:50px ! important;height:50px ! important">
					</div>
					<div>
						<?= (empty($item['purchase_link']) ? $item['sku'] : '<a href="'.$item['purchase_link'].'" target="_blank">'.$item['sku'].'</a>') ?>
						</br>
						<?=$item['name']?>
					</div>
					<input type="hidden" name="prod[<?=$index?>][sku]" value="<?= $item['sku'] ?>">
				</td>
				<td style="text-align: center"><?=$item['qty']?></td>
				<td style="text-align: center"><?=$item['in_stock_qty']?></td>
				<td style="text-align: center"><?=$item['price']?></td>
				<td style="text-align: center"><?=$item['amount']?></td>
				<td style="text-align: center"><?=$item['remark']?></td>
			</tr>
			<?php 
			$index++;
			}?>
		</table>
	</div>
</div>
<div id="purchase_tab2" style="display:none" class="purchase_tab">
	<?php foreach($inStockInfo as $inStock){ ?>
	<div class="panel panel-default ng-isolate-scope" >
		<div class="panel-heading accordion-toggle in_stcok_title">
			<span class="gly glyphicon pull-left glyphicon-triangle-right right10 point_sign" data-isleaf="close"></span>
			<span class="right10"><?= $inStock['stock_change_id'] ?></span>
			<span class="right10"><?= $inStock['create_time'] ?></span>
			<span class="right10"><?= empty($inStock['sku_count']) ? '' : 'SKU: '.$inStock['sku_count'] ?></span>
			<span class="right10"><?= empty($inStock['qty_count']) ? '' : '数量: '.$inStock['qty_count'] ?></span>
		</div>
		<div name="view_in_stock_detail" style="width:100%;clear:both;padding-top:5px; display: none">
			<table style="width:100%;font-size:12px;" class="table-hover table purchase_order_prods">
				<tr style="width:100%;">
					<th style="width:500px;">商品信息</th>
					<th style="width:80px;">数量</th>
				</tr>
				<?php 
				$index=0;
				if(!empty($inStock['item'])){
					foreach($inStock['item'] as $item){
						if(!empty($purchase_items[$item['sku']])){
				?>
				<tr>
					<td>
						<div style="float: left; margin-right: 10px; ">
							<img src="<?=$purchase_items[$item['sku']]['img']?>" style="width:50px ! important;height:50px ! important">
						</div>
						<div>
							<?= $item['sku'] ?>
							</br>
							<?= $purchase_items[$item['sku']]['name']?>
						</div>
						<input type="hidden" name="prod[<?=$index?>][sku]" value="<?= $item['sku'] ?>">
					</td>
					<td style="text-align: center"><?=$item['qty']?></td>
				</tr>
				<?php 
				}}}?>
			</table>
		</div>
	</div>
	<?php }?>
</div>

<script>
$(function() {
	//切换tab
	$('#tab1').on('click',function(){
		$('#purchase_tab2').css("display","none");
		$('#purchase_tab1').css("display","block");
	});
	$('#tab2').on('click',function(){
		$('#purchase_tab1').css("display","none");
		$('#purchase_tab2').css("display","block");
	});
	//弹出入库明细
	$('.in_stcok_title').on('click',function(){
		if($(this).next().css('display') == 'none'){
			$('.in_stcok_title').next().slideUp();
			$('.in_stcok_title').find('.point_sign').removeClass('glyphicon-triangle-bottom');
			$('.in_stcok_title').find('.point_sign').addClass('glyphicon-triangle-right');
			
			$(this).next().slideToggle();
			$(this).find('.point_sign').removeClass('glyphicon-triangle-right');
			$(this).find('.point_sign').addClass('glyphicon-triangle-bottom');
		}
		else{
			$(this).next().slideUp();
			$(this).find('.point_sign').removeClass('glyphicon-triangle-bottom');
			$(this).find('.point_sign').addClass('glyphicon-triangle-right');
		}
	});
});
</script>