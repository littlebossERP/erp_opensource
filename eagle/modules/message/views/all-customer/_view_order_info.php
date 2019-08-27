<?php
use eagle\modules\util\helpers\TranslateHelper;
use eagle\modules\order\helpers\OrderTagHelper;

$this->registerJs("$.initQtip();" , \yii\web\View::POS_READY);
$this->registerJsFile(\Yii::getAlias('@web')."/js/project/order/OrderTagApi.js", ['depends' => ['yii\web\JqueryAsset']]);
$tag_class_list = OrderTagHelper::getTagColorMapping();
$this->registerJs("OrderTagApi.TagClassList=".json_encode($tag_class_list).";" , \yii\web\View::POS_READY);
$this->registerJs("OrderTagApi.init();" , \yii\web\View::POS_READY);
?>
<style>
.modal-body{
	padding:0px;
}
.panel-order-row .table{
	margin-bottom:0px;
}
.panel-order-row .table td,.panel-order-row .table th{
	padding:4px;
}
</style>
<div class="panel-order">

	<div class="panel-order-row">
		<div class="panel-order-body">
		<div class="panel-order-heading"><?= TranslateHelper::t('订单基本信息')?></div>
			<table class="table" style="">
				<tr>
					<td width="73px"><?= TranslateHelper::t('平台单号')?>:</td>
					<td width="360px"><small><?= (!empty($orderData['order_source_order_id'])?$orderData['order_source_order_id']:"")?></small>
						<!-- 
						<?= (empty($orderData['order_source_status'])?"":"(".$orderData['order_source_status'].")")?>
						 -->
					</td>
					<td width="73px"><?= TranslateHelper::t('付款日期')?>:</td>
					<td width="360px"><small><?= (!empty($orderData['paid_time'])?date("Y-m-d H:i:s",$orderData['paid_time']):"")?></small></td>
				</tr>
				<tr>
					<td><?= TranslateHelper::t('店铺名称')?>:</td>
					<td><small><?= (!empty($orderData['selleruserid'])?$orderData['selleruserid']:"")?></small></td>
					<td><?= TranslateHelper::t('销售平台')?>:</th>
					<td><small><?= (!empty($orderData['order_source'])?$orderData['order_source']:"")?>
						<?= (!empty($orderData['order_source_site_id'])?$orderData['order_source_site_id']:"")?></small>
					</td>
				</tr>
				<tr>
					<td><?= TranslateHelper::t('订单金额')?>:</td>
					<td><small><strong><?= (!empty($orderData['currency'])?$orderData['currency']:"")?></strong>
						<?= (!empty($orderData['grand_total'])?$orderData['grand_total']:"")?></small>
					</td>
					<td><?= TranslateHelper::t('运费')?>:</td>
					<td><small><strong><?= (!empty($orderData['currency'])?$orderData['currency']:"")?></strong>
						<?= (!empty($orderData['shipping_cost'])?$orderData['shipping_cost']:"")?></small>
					</td>
				</tr>
			</table>
		<div class="panel-order-heading"><?= TranslateHelper::t('订单备注及标签')?><span qtipkey="order_tag_desc_api"></span></div>
			<table class="table">
				<tr>
					<td width="73px"><?= TranslateHelper::t('订单备注')?>:</td>
					<td width="360px"><small id="desc_content_<?=$orderData['order_id'] ?>"><?= (!empty($orderData['desc'])?$orderData['desc']:"")?></small></td>
					<td width="73px"><?= TranslateHelper::t('订单标签')?>:</td>
					<td width="360px">
					<small id="order_tag_list_<?=$orderData['order_id'] ?>">
					<?php 
	    				$TagStr = OrderTagHelper::generateTagIconHtmlByOrderId($orderData['order_id']);
			    		if (!empty($TagStr)){
			    			$TagStr = "<span class='btn_order_tag_qtip' data-order-id='".$orderData['order_id']."' >$TagStr</span>";
			    		}
			    		echo $TagStr;
	    			?>
					</small>
					</td>
				</tr>
				<tr>
					<td style="border:0px;"><input type="button" class="btn_xs btn-success" id="addDesc_<?=$orderData['order_id'] ?>_btn" onclick="addDesc('<?=$orderData['order_id'] ?>')" value="添加备注 ">
    					<input type="button" class="btn_xs btn-success" id="saveDesc_<?=$orderData['order_id'] ?>_btn" onclick="saveDesc('<?=$orderData['order_id'] ?>')" style="display:none" value="添加">
    					<input type="button" class="btn_xs btn-success" id="cancleAddDesc_<?=$orderData['order_id'] ?>_btn" onclick="cancleAddDesc('<?=$orderData['order_id'] ?>')" style="display:none" value="取消">
    				</td>
					<td style="border:0px;"><textarea rows="3" cols="30" id="add_<?=$orderData['order_id'] ?>_desc" style="display:none"></textarea></td>
					<td style="border:0px;"></td>
					<td style="border:0px;"></td>
				</tr>
			</table>
		</div>
	</div>
	<div class="panel-order-row">
		<div class="panel-order-heading"><?= TranslateHelper::t('客户及递送信息')?></div>
		<div class="panel-order-body">
			<table class="table">
				<tr>
					<td width="73px"><?= TranslateHelper::t('买家账号')?>:</td>
					<td width="360px"><small><?= (!empty($orderData['source_buyer_user_id'])?$orderData['source_buyer_user_id']:"")?></small></td>
					<td width="73px" rowspan="3"><?= TranslateHelper::t('收件地址')?>:</td>
					<td width="360px" rowspan="3">
						<small>
						<?= (!empty($orderData['consignee'])?$orderData['consignee']."<br>":"")?>
						<?= (!empty($orderData['consignee_company'])?$orderData['consignee_company']."<br>":"")?>
						<?= (!empty($orderData['consignee_address_line1'])?$orderData['consignee_address_line1']."<br>":"")?>
						<?= (!empty($orderData['consignee_address_line2'])?$orderData['consignee_address_line2']."<br>":"")?>
						<?= (!empty($orderData['consignee_address_line3'])?$orderData['consignee_address_line3']."<br>":"")?>
						<?= (!empty($orderData['consignee_district '])?$orderData['consignee_district '].",":"")?>  
						<?= (!empty($orderData['consignee_county'])?$orderData['consignee_county']."<br>":"")?>
						<?= (!empty($orderData['consignee_city'])?$orderData['consignee_city'].",":"")?> 
						<?= (!empty($orderData['consignee_province'])?$orderData['consignee_province']:"")?>
						<?= (!empty($orderData['consignee_postal_code'])?$orderData['consignee_postal_code']."<br>":"")?>
						<?= (!empty($orderData['consignee_country'])?$orderData['consignee_country']."<br>":"")?>
						<?= TranslateHelper::t('Zip:')?><?= (!empty($orderData['consignee_postal_code'])?$orderData['consignee_postal_code']."  ":"")?>
						<?= TranslateHelper::t('Tel:')?><?= (!empty($orderData['consignee_phone'])?$orderData['consignee_phone']:"")?>
						</small>
					</td>
				</tr>
				<tr>
					<td><?= TranslateHelper::t('Email')?>:</td>
					<td><small><?= (!empty($orderData['consignee_email'])?$orderData['consignee_email']:"")?></small></td>
				</tr>
				<tr>
					<td><?= TranslateHelper::t('已选递送')?>:</td>
					<td><small><?= (!empty($orderData['order_source_shipping_method'])?$orderData['order_source_shipping_method']:"")?></small></td>
				</tr>
			</table>
		</div>
	</div>
	
	<div class="panel-order-row">
		<div class="panel-order-heading"><?= TranslateHelper::t('销售商品明细')?></div>
		<div  class="panel-order-body"> 
		<div class="row row-nomargin">
		<table class="table">
			<thead>
				<tr>
					<th><?= TranslateHelper::t('图片')?>/<?= TranslateHelper::t('SKU')?>/<?= TranslateHelper::t('产品名称')?></th>
					<th><?= TranslateHelper::t('数量')?></th>
					<th><?= TranslateHelper::t('单价')?></th>
					<th><?= TranslateHelper::t('折扣')?></th>
				</tr>
			</thead>
			
			<tbody>
				<?php if(!empty($orderData['items'])):?>
					<?php 
					if (is_string($orderData['items']))
						$items = json_decode($orderData['items'],true);
					else 
					$items = $orderData['items'];
					$total_qty = 0;
					foreach ($items as $anItem):?>
					<tr>
						<td>
							<img class="pull-left" style="width: 160px;" src="<?= (!empty($anItem['photo_primary'])?$anItem['photo_primary']:"")?>" />
							<p><?= (!empty($anItem['sku'])?$anItem['sku']:"")?></p>
							<p style="max-width: 800px;"><?= (!empty($anItem['product_name'])?$anItem['product_name']:"")?></p>
						</td>	
						<td><?= (!empty($anItem['ordered_quantity'])?$anItem['ordered_quantity']:0)?></td>
						<td class="text-nowrap"><strong><?= (!empty($orderData['currency'])?$orderData['currency']:"")?></strong> <?= (!empty($anItem['price'])?$anItem['price']:0)?></td>
						<td class="text-nowrap"><strong><?= (!empty($orderData['currency'])?$orderData['currency']:"")?></strong> <?= (!empty($anItem['promotion_discount'])?$anItem['promotion_discount']:"")?></td>
					</tr>
					
					<?php 
					if (!empty($anItem['ordered_quantity'])){
						$total_qty += $anItem['ordered_quantity'];
					}
					endforeach;?>
					
					<tr>
						
						
						<td><?= TranslateHelper::t('合计')?></td>
						<td><?= $total_qty?></td>
						<td><strong><?= (!empty($orderData['currency'])?$orderData['currency']:"")?></strong> <?= (!empty($orderData['subtotal'])?$orderData['subtotal']:0)?> </td>
						<td><strong><?= (!empty($orderData['currency'])?$orderData['currency']:"")?></strong> <?= (!empty($orderData['discount_amount'])?$orderData['discount_amount']:0)?></td>
					</tr>
				<?php endif;?>
			</tbody>
		</table>
		</div>
		</div>
	</div>
	<div class="order_tag_dialog_<?=$orderData['order_id'] ?>"></div>
</div>
