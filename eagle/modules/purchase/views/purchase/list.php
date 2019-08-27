<?php
use eagle\modules\util\helpers\TranslateHelper;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\jui\Dialog;
use yii\data\Sort;
use yii\jui\JuiAsset;
use eagle\modules\purchase\helpers\PurchaseHelper;

$baseUrl = \Yii::$app->urlManager->baseUrl;
$this->registerJsFile($baseUrl."/js/project/purchase/purchase/purchaseOrderList.js?v=1.2", ['depends' => ['yii\web\JqueryAsset']]);
$this->registerJsFile($baseUrl."js/project/purchase/purchase/downloadexcel.js?v=1.0", ['depends' => ['yii\jui\JuiAsset','yii\bootstrap\BootstrapPluginAsset']]);
$this->registerJsFile($baseUrl."/js/project/purchase/purchase_link_list.js", ['depends' => ['yii\web\JqueryAsset']]);
$this->registerJsFile($baseUrl."/js/project/purchase/purchase/purchase1688Create.js?v=1.3", ['depends' => ['yii\web\JqueryAsset']]);

$this->registerJs("purchaseOrder.list.init();" , \yii\web\View::POS_READY);
$this->registerJs("$('.prod_img').popover();" , \yii\web\View::POS_READY);
?>

<style>
.create_or_edit_purchase_win .modal-dialog{
	width: 1000px;
	max-height: 650px;
	overflow-y: auto;	
}
.div_inner_td{
	width: 100%;
}
.span_inner_td{
	float: left;
	padding: 6px 0px;
}
.tr_1688_product_title2 td{
	border: 1px solid #ddd !important;
	background-color: #d9effc; 
	font: bold 12px SimSun,Arial; 
	text-align: center; 
}
.matching_1688_product .modal-dialog{
	width: 750px;
	max-height: 500px;
	overflow-y: auto;	
}
.purchase_top_button{
	float: left;
	border-style: none; 
	padding: 8px; 
	margin-left: 20px;
	margin-bottom: 5px; 
}
</style>

<!-- 
<div class="modal fade" id="myModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
        </div>
    </div>
</div>
 -->		
<FORM action="<?= Url::to(['/purchase/purchase/'.yii::$app->controller->action->id])?>" method="GET" style="width:100%;float:left;">
  	<div style="width: 100%;float: left;margin-bottom: 10px;">
  		<div class="div-input-group" title="<?= TranslateHelper::t('按仓库过滤显示结果') ?>">
  			<div style="float:left;" class="input-group">
	  			<SELECT name="warehouse_id" value="" class="eagle-form-control" style="width:150px;margin:0px">
	  				<OPTION value="" <?=(!isset($_GET['warehouse_id']) or !is_numeric($_GET['warehouse_id']) )?" selected ":'' ?>><?= TranslateHelper::t('所在仓库') ?></OPTION>
	  					<?php foreach($warehouse as $wh_id=>$wh_name){
							echo "<option value='".$wh_id."'";
							if(isset($_GET['warehouse_id']) && $_GET['warehouse_id']==$wh_id && is_numeric($_GET['warehouse_id'])) echo " selected ";
							echo ">".$wh_name."</option>";						
						} ?>
	  			</SELECT>
  			</div>
  		</div>

  		<div class="div-input-group" style="float: left;margin-left:5px;">
  			<div style="float:left;" class="input-group" title="<?= TranslateHelper::t('按付款状态过滤显示结果') ?>">
	  			<SELECT name="payment_status" value="" style="width:150px;margin:0px" class="eagle-form-control">
		  			<OPTION value=""><?= TranslateHelper::t('付款状态') ?></OPTION>
		  				<?php foreach($paymentStatus as $k=>$v){
							echo "<option value='".$k."'";
							if(isset($_GET['payment_status'])&& $_GET['payment_status']==$k) echo ' selected="selected" ';
							echo ">".$v."</option>";
						} ?>
	  			</SELECT>
  			</div>
  		</div>
  		
		<div class="div-input-group" style="float: left;margin-left:5px;">
  			<div style="float:left;" class="input-group" title="<?= TranslateHelper::t('按采购状态过滤显示结果') ?>">
  				<SELECT name="status" value="" style="width:150px;margin:0px" class="eagle-form-control">
	  				<OPTION value=""><?= TranslateHelper::t('采购状态') ?></OPTION>
	  					<?php foreach($purchaseStatus as $k=>$v){
							echo "<option value='".$k."'";
							if(isset($_GET['status'])&& $_GET['status']==$k) echo ' selected="selected" ';
							echo ">".$v."</option>";
						} ?>
  				</SELECT>
  			</div>
  		</div>
  		
		<div class="div-input-group" style="float: left;margin-left:5px;">
  			<div style="float:left;" class="input-group" title="<?= TranslateHelper::t('按仓供应商过滤显示结果') ?>">
				<SELECT name="supplier_id" value="" style="width:150px;margin:0px"  class="eagle-form-control">
  					<OPTION value=""><?= TranslateHelper::t('供应商') ?></OPTION>
	  					<?php foreach($suppliers as $asupplier){
							echo "<option value='".$asupplier['supplier_id']."'";
							if(isset($_GET['supplier_id'])&& $_GET['supplier_id']==$asupplier['supplier_id']) echo ' selected="selected" ';
							echo ">".$asupplier['name']."</option>";
						} ?>
  				</SELECT>
	  		</div>
	  	</div>
	  	
	  	<div class="div-input-group" style="float: left;margin-left:5px;">
  			<div style="float:left;" class="">
				<input name="sdate" id="purchaselist_startdate" class="eagle-form-control" type="text" placeholder="<?= TranslateHelper::t('从 此日期后')?>" 
					value="<?= (empty($_GET['sdate'])?"":$_GET['sdate']);?>" style="width:150px;margin:0px;height:28px;float:left;" title="<?= TranslateHelper::t('只显示此日期后的采购单') ?>"/>
				<input name="edate" id="purchaselist_enddate" class="eagle-form-control" type="text" placeholder="<?= TranslateHelper::t('至 此日期前')?>" 
					value="<?= (empty($_GET['edate'])?"":$_GET['edate']);?>" style="width:150px;margin:0px;height:28px;float:left;" title="<?= TranslateHelper::t('只显示此日期前的采购单') ?>"/>
	  		</div>
	  	</div>
	  	
	  	<div class="div-input-group" style="float: left;margin-left:5px;">
  			<div style="float:left;">
  				<SELECT name="search_type" value="" style="float:left; width:80px;margin:0px"  class="eagle-form-control">
  					<OPTION value="pur_no" <?= (empty($_GET['search_type']) || $_GET['search_type']=='pur_no') ? 'selected="selected"' : ''?>>采购单号</OPTION>
  					<OPTION value="tru_no" <?= (!empty($_GET['search_type']) && $_GET['search_type']=='tru_no') ? 'selected="selected"' : ''?>>物流号码</OPTION>
  					<OPTION value="sku" <?= (!empty($_GET['search_type']) && $_GET['search_type']=='sku') ? 'selected="selected"' : ''?>>产品SKU</OPTION>
  				</SELECT>
				<input name="keyword" class="eagle-form-control" type="text" placeholder="<?= TranslateHelper::t('输入搜索内容')?>" title="<?= TranslateHelper::t('根据输入的采购单号字段模糊查询') ?>"  
					value="<?php if(!empty($_GET['keyword'])) echo $_GET['keyword'] ?>" style="width:150px;margin:0px;height:28px;float:left;"/>

				<button type="submit" class="btn btn-default" data-loading-text="<?= TranslateHelper::t('查询中...')?>" style="margin-left:5px;padding: 0px;height: 28px;width: 30px;border-radius: 0px;border: 1px solid #b9d6e8;" title="<?= TranslateHelper::t('查询') ?>">
					<span class="glyphicon glyphicon-search" aria-hidden="true"></span>
				</button>
				<button type="button" id="btn_clear" class="btn btn-default" style="margin-left:5px;padding: 0px;height: 28px;width: 30px;border-radius: 0px;border: 1px solid #b9d6e8;" title="<?= TranslateHelper::t('重置') ?>">
					<span class="glyphicon glyphicon-refresh" aria-hidden="true"></span>
				</button>
	  		</div>
	  	</div>
  	</div>
</FORM>
		
<div style="display:inline-block;width:100%;float:left;">
	<button type="button" id="btn-create" class="iv-btn btn-primary purchase_top_button" >新增采购单</button>
	<button type="button" class="iv-btn btn-primary purchase_top_button" onclick="purchaseOrder.list.batchPurchaseStockIn()" >批量到货入库</button>
	<button type="button" class="iv-btn btn-primary purchase_top_button" onclick="purchaseOrder.list.batchCancelPurchaseOrder()" >批量取消</button>
	<div class="btn-group" style=" float:left;  margin-left: 20px; margin-bottom: 5px;">
		<a data-toggle="dropdown" style="color: inherit;" aria-expanded="false">
			<button type="button" class="iv-btn btn-primary purchase_top_button" style="margin: 0px; ">导出<span class="caret"></span></button>
		</a>
		<ul class="dropdown-menu">
			<li style="font-size: 12px;"><a onclick="purchaseOrder.list.exportExeclSelect(0)">按勾选导出</a></li>
			<li style="font-size: 12px;"><a onclick="purchaseOrder.list.exportExeclSelect(1)">按所有页导出</a></li>
		</ul>
   	</div>
	<button type="button" class="iv-btn btn-primary purchase_top_button" onclick="purchaseOrder.list.printPurchaseOrder()" >打印采购单</button>
	<button type="button" class="iv-btn btn-important purchase_top_button" onclick="purchaseOrder.list.update1688OrderInfo()" style="float: right; margin-right: 2%; " >更新1688订单信息</button>
</div>
		<!-- table -->
<div class="shoplist" style="width:98%;float:left;">
	<table class="table_list_purchase" style="font-size: 12px">
		<tr style="border: 1px solid #ccc; ">
			<th style="min-width: 250px; "><input id="select_all" class="ck" type="checkbox" >商品信息</th>
			<th style="min-width: 100px; width: 10%; ">金额</th>
			<th style="min-width: 150px; width: 20%; ">物流信息</th>
			<th style="min-width: 100px; width: 15%; ">时间</th>
			<th style="min-width: 100px; width: 15%; ">状态</th>
			<th style="min-width: 50px; width: 5%; ">操作</th>
		</tr>
		<?php foreach($list as $purchase){?>
		<tr class="table_list_tr">
			<td colspan="2">
				<input type="checkbox" class="select_one" name="orderSelected" value="<?=$purchase['id']?>">
				<div class="list_order_num">
					单号：<?=$purchase['purchase_order_id']?>
					<?php if(!empty($purchase['order_id_1688'])){?>
						<span style="margin-left: 30px; ">【</span>
						<span style="color: #ff9900">1688：</span><?= $purchase['order_id_1688'] ?> 
						「<?= $purchase['pay_status_name_1688'] ?>」
						<?php if($purchase['status_1688'] == 'waitbuyerpay' && !empty($purchase['pay_url'])){?>
							<button type="button" class="iv-btn btn-primary" onclick="javascript: window.open('<?= $purchase['pay_url'] ?>'); " style="border-style: none; padding: 5px; " >立即付款</button>
						<?php }?>
						<span>】</span>
					<?php }else if($purchase['status_val'] < PurchaseHelper::ALL_ARRIVED){?>
						<a href="javascript: " style="margin-left: 30px; text-decoration: underline; " onclick="purchaseOrder.list.show1688Purchase(<?=$purchase['id']?>)">1688快速下单</a>
					<?php }?>
				</div>
			</td>
			<td colspan="2">
				<div class="list_order_num">供应商：<?=$purchase['supplier_name'] ?></div>
			</td>
			<td colspan="2" style="text-align: right; padding-right: 20px !important;">
				<div class="list_order_num">仓库：<?=$purchase['warehouse_name'] ?></div>
			</td>
			<td tag="status" orderId="<?=$purchase['id']?>" value="<?=$purchase['status_val'] ?>" style="display: none; "></td>
		</tr>
		<tr class="table_list_tr_items">
			<td>
				<table style="width: 100%; ">
					<tr>
						<td style="width: 68px; text-align: center; ">
							<div style="border: 1px solid #ccc; width: 62px; height: 62px">
								<img class="prod_img" style="max-width:100%; max-height: 100%; width:auto; height: auto; " src="<?= $purchase['photo_primary'] ?>" data-toggle="popover" data-content="<img width='250px' src='<?= str_replace('.jpg_50x50', '', $purchase['photo_primary']) ?>'>" data-html="true" data-trigger="hover">
							</div>
						</td>
						<td>
							<div>
								<p class="p_newline_v3">商品种类：<?= $purchase['sku_count'] ?></p>
								<p class="p_newline_v3">采购数量：<?= $purchase['qty_count'] ?></p>
								<a href="javascript: " class="" onclick="purchaseOrder.list.showItems(<?=$purchase['id']?>)" data-type="0">
									<span class=""></span>
									商品详情
								</a>
							</div>
						</td>
					</tr>
				</table>
			</td>
			<td style="text-align: center; ">
				<p><?= $purchase['amount'] ?></p>
				<?php echo empty($purchase['order_id_1688']) ? '' : '1688货款：'.$purchase['amount_1688'] ?>
			</td>
			<td>
				<?php if(!empty($purchase['logistics_billNo'])){?>
					<p>物流方式：<?= $purchase['logistics_company_name'] ?></p>
					<p>物流号： <?= $purchase['logistics_billNo'] ?></p>
					<p>物流状态： <?= $purchase['logistics_status_name_1688'] ?></p>
				<?php }else{?>
					<p>物流方式：<?= $purchase['delivery_method_name'] ?></p>
					<p>物流号： <?= $purchase['delivery_number'] ?></p>
				<?php }?>
			</td>
			<td>
				<p>采购时间：<?= $purchase['create_time'] ?></p>
				<p>预期到达：<?= $purchase['expected_arrival_date'] ?></p>
			</td>
			<td>
				<p>货款状态：<?= $purchase['payment_name'] ?></p>
				<p>采购状态：<?= $purchase['status'] ?></p>
			</td>
			<td style="text-align: center">
				<a href="javascript: " class="" onclick="purchaseOrder.list.viewPurchaseOrder(<?=$purchase['id']?>)" style="display: block" >查看</a>
				<?php if($purchase['status_val'] < PurchaseHelper::ALL_ARRIVED){?>
					<a href="javascript: " class="" onclick="purchaseOrder.list.editPurchaseOrder(<?=$purchase['id']?>)" style="display: block" >编辑</a>
				<?php } if($purchase['status_val'] <= PurchaseHelper::PARTIAL_ARRIVED_CANCEL_LEFT ){?>
					<a href="javascript: " class="" onclick="purchaseOrder.list.purchaseChooseStockIn(<?=$purchase['id']?>)" style="display: block" >入库</a>
				<?php } if($purchase['status_val'] <= PurchaseHelper::WAIT_FOR_ARRIVAL ){?>
					<a href="javascript: " class="" onclick="purchaseOrder.list.cancelPurchaseOrder(<?=$purchase['id']?>)" style="display: block" >取消</a>
				<?php } if($purchase['status_val'] > PurchaseHelper::WAIT_FOR_ARRIVAL && $purchase['status_val'] <= PurchaseHelper::PARTIAL_ARRIVED_CANCEL_LEFT ){?>
					<a href="javascript: " class="" onclick="purchaseOrder.list.cancelPurchaseOrder(<?=$purchase['id']?>)" style="display: block" >中止入库</a>
				<?php }?>
			</td>
		</tr>
		<tr class="table_list_tr_items" id="table_list_tr_items_<?= $purchase['id'] ?>" style="display: none; ">
			<td colspan="6" style="border: none; "></td>
		</tr>
		<?php }?>
	</table>

    <!-- Modal -->
	<div id="checkOrder" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
        </div><!-- /.modal-content -->
    </div>
    </div>
    <!-- /.modal-dialog -->
</div>

<input id="search_condition" type="hidden" value="<?php echo $search_condition;?>">
<input id="search_count" type="hidden" value="<?php echo $pagination->totalCount;?>">

<div id="pager-group">
    <?= \eagle\widgets\SizePager::widget(['pagination' => $pagination , 'pageSizeOptions'=>array( 5 , 20 , 50 , 100 , 200 ) , 'class'=>'btn-group dropup']);?>
    <div class="btn-group" style="width: 49.6%;text-align: right;">
    	<?=\yii\widgets\LinkPager::widget(['pagination' => $pagination,'options'=>['class'=>'pagination']]);?>
	</div>
</div>

<!-- Modal -->
<div class="create_or_edit_purchase_win"></div>
<!-- /.modal-dialog -->
<!-- Modal -->
<div class="operation_result"></div>
<!-- /.modal-dialog -->

<div class="modal-body tab-content" id="dialog_matching_1688_product" style="display: none; widht: 750px; ">
	<table class="table" style="width: 100%; ">
		<tr>
			<td colspan="3">
				<span>1688商品链接</span>
				<input name="get_url_1688_product" class="form-control" style="width: 300px; display: table-cell; margin: 0 15px; " placeholder="请输入1688商品的链接"></input>
				<button type="button" class="iv-btn btn-important" onclick="purchase1688Create.list.Get1688Product()" style="border-style: none; " >获取1688商品</button>
			</td>
		</tr>
		<tr class="tr_1688_product_title1">
			<td colspan="3" style="background-color: #d9effc; font: bold 16px SimSun,Arial; text-align: center;">
				<p>1688商品信息</p>
			</td>
		</tr>
		<tr class="tr_1688_product_title2" style="display: none">
		    <td>图片</td>
			<td>名称</td>
			<td>选择商品</td>
		</tr>
		<tr name="tr_1688_product_items"></tr>
	</table>
</div>

<div class="modal-body tab-content" id="dialog_matching_1688_supplier" style="display: none; ">
	<table class="table" style="width: 100%; margin: 0px; ">
		<tr>
			<td >
				<span>1688店铺链接</span>
				<input name="get_url_1688_supplier" class="form-control" style="width: 400px; display: table-cell; margin: 0 15px; " placeholder="请输入1688店铺的主页网址"></input>
			</td>
		</tr>
	</table>
</div>

<div class="modal-body tab-content" id="dialog_set_rec_add" style="display: none; text-align:center; ">
	<form id="set_rec_add_form" method="post" class="form-group">
		<input name="set_rec_add_aliId" type="hidden" value="" />
		<table class="table" border="1" cellpadding="3" cellspacing="0" style="width: 80%; margin:auto">
			<tr>
				<td>
					<span class="tb_title">收货人姓名：</span>
					<input name="fullName" class="form-control" style="width: 300px; display: table-cell; margin: 0 15px; " />
				</td>
			</tr>
			<tr>
				<td>
					<span class="tb_title">手机：</span>
					<input name="mobile" class="form-control" style="width: 300px; display: table-cell; margin: 0 15px; " />
				</td>
			</tr>
			<tr>
				<td>
					<span class="tb_title">电话：</span>
					<input name="phone" class="form-control" style="width: 300px; display: table-cell; margin: 0 15px; " />
				</td>
			</tr>
			<tr>
				<td>
					<span class="tb_title">邮编：</span>
					<input name="postCode" class="form-control" style="width: 300px; display: table-cell; margin: 0 15px; " />
				</td>
			</tr>
			<tr>
				<td>
					<span class="tb_title">省份：</span>
					<input name="province" class="form-control" style="width: 300px; display: table-cell; margin: 0 15px; " />
				</td>
			</tr>
			<tr>
				<td>
					<span class="tb_title">市：</span>
					<input name="city" class="form-control" style="width: 300px; display: table-cell; margin: 0 15px; " />
				</td>
			</tr>
			<tr>
				<td>
					<span class="tb_title">区：</span>
					<input name="area" class="form-control" style="width: 300px; display: table-cell; margin: 0 15px; " />
				</td>
			</tr>
			<tr>
				<td>
					<span class="tb_title">镇：</span>
					<input name="town" class="form-control" style="width: 300px; display: table-cell; margin: 0 15px; " />
				</td>
			</tr>
			<tr>
				<td>
					<span class="tb_title">街道地址：</span>
					<input name="address" class="form-control" style="width: 300px; display: table-cell; margin: 0 15px; " />
				</td>
			</tr>
		</table>
	</form>
</div>
