<?php
use eagle\modules\purchase\helpers\PurchaseHelper;
?>

<style>
.create_1688_dialog .modal-dialog{
	width: 1000px;
}
div .purchase_product_detail{
	height: 400px;
	overflow-y: scroll;
}
div .tb_title{
	width: 100px;
	display: inline-block;
	font-weight: 600;
	font-size: 15px;
	text-align: right;
}
.purchase_product_detail .table th{
	font-size: 15px;
	text-align: center;
}
.purchase_product_detail th a{
	color: #428bca;
}
.purchase_prods td{
	border: none !important;
}
</style>

<ul class="nav nav-tabs create_1688_tab">
	<li class="active" tab-type="1">
		<a id="tab1" data-toggle="tab">1688下单</a>
	</li>
	<li tab-type="2">
		<a id="tab2" data-toggle="tab">关联1688订单</a>
	</li>
</ul>

<div id="purchase_1688_tab1" class="purchase_1688_tab">
	<form id="create_1688_form" method="post" class="form-group">
		<input type="hidden" name="purchase_id" value="<?= $purchase['purchase_id'] ?>" />
		<div style="width: 100%; clear: both;">
			<table style="width: 100%; font-size: 12px; " class="table purchase_order_prods"
				<tr>
					<td>
						<div class="tb_title">供应商：</div>
						<input type="hidden" name="supplier_id" value="<?= $purchase['supplier_id'] ?>" />
						<?= empty($purchase['name']) ? '' : $purchase['name']?>
						<br>
						<div class="tb_title" style="color: #999999">1688供应商：</div>
						<div style="display: inline-block; ">
							<?php if(!empty($purchase['al1688_user_id'])){?>
							    <input type="hidden" name="al1688_user_id" value="<?= $purchase['al1688_user_id'] ?>" />
							    <a target="_blank" href="<?= $purchase['al1688_url'] ?>" style="margin-right: 20px;"><?= $purchase['al1688_company_name'] ?></a>
							    <a href="javascript: " onclick="purchase1688Create.list.showMatching1688Supplier(this)" >替换</a>
							<?php }else{?>
								<a href="javascript: " onclick="purchase1688Create.list.showMatching1688Supplier(this)" style="color: red; " >请先点击关联1688店铺</a>
							<?php }?>
						</div>
					</td>
					<td>
						<div class="tb_title">1688账号：</div>
						<select id="select_1688_user" name="select_1688_user" value="" class="eagle-form-control" required="required" style="width: 200px; ">
							<?php foreach($al_1688_users as $k => $v){ ?>
								<option value="<?= $k ?>" > <?= $v['name'] ?> </option>
							<?php }?>
						</select>
						<a target="_blank" href=<?=$binding_url?> style="margin-left: 10px;">授权1688账号</a>
					</td>
				</tr>
				<tr>
					<td colspan="2">
						<div class="tb_title">收货地址：</div>
						<select id="select_receive_add" name="select_receive_add" value="" class="eagle-form-control" required="required" style="width: 500px; ">
							<?php foreach(PurchaseHelper::warehouse() as $k => $v){ ?>
								<option value="<?= $k ?>" <?= $k == 1 ? 'selected' : ''; ?> > <?= $v ?> </option>
							<?php }?>
						</select>
						<a href="javascript: " onclick="purchase1688Create.list.get1688Add()" style="margin-right: 10px; margin-left: 10px; " >同步1688收货地址</a>
						<a href="javascript: " onclick="purchase1688Create.list.showSetRecAdd()" >设置本地收货地址</a>
					</td>
				</tr>
				<tr>
					<td>
						<div class="tb_title" style="float: left; ">买家留言：</div>
						<textarea class="form-control" type="text" name="message" value="" style="float: left; width: 60%; "></textarea>
					</td>
					<td>
						<div class="tb_title">下单流程：</div>
						<div style="display: inline-block;">
							<label style="margin-right: 20px; "><input type="radio" name="flow" value="general" checked />大市场订单</label>
							<label><input type="radio" name="flow" value="saleproxy" />代销市场订单</label>
						</div>
					</td>
				</tr>
			</table>
		</div>
		<div class="purchase_product_detail" style="width: 100%; clear: both; padding-top: 5px;">
			<table style="width: 100%; font-size: 12px; " class="table-hover table purchase_order_prods"
				<tr style="width: 100%">
					<th style="width:300px;">库存商品信息</th>
					<th style="width:300px;">1688商品信息</th>
					<th style="width:80px;">数量</th>
					<!-- <th style="width:100px;">操作</th>  -->
				</tr>
				<?php foreach($items as $index => $item){?>
				<tr>
					<td>
						<table style="width: 100%; " class="purchase_prods">
							<tr>
								<td style="width: 68px; text-align: center; ">
									<div style="border: 1px solid #ccc; width: 62px; height: 62px">
										<img class="prod_img" style="max-width:100%; max-height: 100%; width:auto; height: auto; " src="<?= $item['photo_primary'] ?>" data-toggle="popover" data-content="<img width='250px' src='<?= $item['photo_primary'] ?>'>" data-html="true" data-trigger="hover">
									</div>
								</td>
								<td>
									<?= $item['sku']?>
									</br>
									<?= $item['name']?>
								</td>
							</tr>
						</table>
						<input type="hidden" name="prod[<?=$index?>][purchase_item_id]" value="<?= $item['purchase_item_id'] ?>">
						<input type="hidden" name="prod[<?=$index?>][sku]" value="<?= $item['sku'] ?>">
					</td>
					<td style="text-align: center">
						<?php if(!empty($item['matching_1688'])){ ?>
							<table style="width: 100%; " class="purchase_prods" name="<?= $item['matching_1688']['product_id'].'_'.$item['matching_1688']['sku_1688'] ?>">
								<tr>
									<td style="width: 68px; text-align: center; ">
										<div style="border: 1px solid #ccc; width: 62px; height: 62px">
											<img class="prod_img" style="max-width:100%; max-height: 100%; width:auto; height: auto; " src="<?= $item['matching_1688']['image_url'] ?>" data-toggle="popover" data-content="<img width='250px' src='<?= $item['matching_1688']['image_url'] ?>'>" data-html="true" data-trigger="hover">
										</div>
									</td>
									<td><a target="_blank" href="<?= $item['matching_1688']['pro_link'] ?>"><?= $item['matching_1688']['name'] ?></a></br><?= $item['matching_1688']['attributes'] ?></td>
								</tr>
							</table>
							<a href="javascript: " onclick="purchase1688Create.list.showMatching1688Product(this, '<?= $item['purchase_item_id'] ?>', '<?= $item['sku'] ?>', '<?= $index ?>')">更换1688商品</a>
							<input type="hidden" name="prod[<?= $index ?>][product_id]" value="<?= $item['matching_1688']['product_id'] ?>">
							<input type="hidden" name="prod[<?= $index ?>][sku_1688]" value="<?= $item['matching_1688']['sku_1688'] ?>">
							<input type="hidden" name="prod[<?= $index ?>][spec_id]" value="<?= $item['matching_1688']['spec_id'] ?>">
						<?php }else{?>
							<a href="javascript: " onclick="purchase1688Create.list.showMatching1688Product(this, '<?= $item['purchase_item_id'] ?>', '<?= $item['sku'] ?>', '<?= $index ?>')">请先点击这里匹配1688商品</a>
						<?php }?>
					</td>
					<td>
						<input name="prod[<?=$index?>][qty]" type="number" value="<?= $item['qty'] ?>" class="form-control" pattern="^\d*$"/>
					</td>
					<!-- <td></td> -->
				</tr>
				<?php $index++; }?>
			</table>
		</div>
	</form>
</div>
<div id="purchase_1688_tab2" class="purchase_1688_tab" style="display: none; ">
	<input type="hidden" name="purchase_id" value="<?= $purchase['purchase_id'] ?>" />
	<div style="width: 100%; clear: both;">
		<table style="width: 100%; font-size: 12px; " class="table purchase_order_prods"
			<tr>
				<td>
					<div class="tb_title">1688账号：</div>
					<select id="select_1688_user_2" value="" class="eagle-form-control" required="required" style="width: 200px; ">
						<?php foreach($al_1688_users as $k => $v){ ?>
							<option value="<?= $k ?>" > <?= $v['name'] ?> </option>
						<?php }?>
					</select>
				</td>
				<td>
					<div class="tb_title">1688订单号：</div>
					<input id="binding_1688_order_id" class="form-control" style="width: 200px; display: table-cell; " />
				</td>
			</tr>
		</table>
	</div>
</div>










