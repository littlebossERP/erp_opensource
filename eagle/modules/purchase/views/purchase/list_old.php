<?php
use eagle\modules\util\helpers\TranslateHelper;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\jui\Dialog;
use yii\data\Sort;
use yii\jui\JuiAsset;
use eagle\modules\purchase\helpers\PurchaseHelper;

$baseUrl = \Yii::$app->urlManager->baseUrl;
$this->registerJsFile($baseUrl."/js/project/purchase/purchase/purchaseOrderList.js?v=1.1", ['depends' => ['yii\web\JqueryAsset']]);
$this->registerJsFile($baseUrl."js/project/purchase/purchase/downloadexcel.js?v=1.0", ['depends' => ['yii\jui\JuiAsset','yii\bootstrap\BootstrapPluginAsset']]);
$this->registerJsFile($baseUrl."/js/project/purchase/purchase_link_list.js", ['depends' => ['yii\web\JqueryAsset']]);

$this->registerJs("purchaseOrder.list.init();" , \yii\web\View::POS_READY);
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
/*
table > tbody > tr > th{
	height: 20px;
  	padding: 3px;
  	vertical-align: middle;
	text-align: center !important;
	background-color: #d9effc;
	font: bold 12px SimSun,Arial;
	color: #374655;
}
table > tbody > tr > td{
  	vertical-align: middle;
	text-align: center;
	word-break:break-word;
}
*/
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
	<div style="float:left;">
		<button type="button" id="btn-create" class="btn-xs btn-transparent font-color-1" style="margin:0px 5px;">
			<span class="glyphicon glyphicon-plus"></span>
			<?= TranslateHelper::t('新增采购单')?>
		</button>
	</div>
			
	<div style="float:left;">
		<button type="button" id="batch_purchase_stockin_btn" class="btn-xs btn-transparent font-color-1" onclick="purchaseOrder.list.batchPurchaseStockIn()" style="margin:0px 5px;" >
			<span class="glyphicon glyphicon-log-in"></span>
			<?= TranslateHelper::t('批量到货入库')?>
		</button>
	</div>
	
	<div style="float:left;">
		<button type="button" id="batch_cancel_purchaseOrder_btn" class="btn-xs btn-transparent font-color-1" onclick="purchaseOrder.list.batchCancelPurchaseOrder()" style="margin:0px 5px;" >
			<span class="glyphicon glyphicon-remove"></span>
			<?= TranslateHelper::t('批量取消')?>
		</button>
	</div>
	<div class="btn-group" style="float:left;margin-top:2px;">
                <a data-toggle="dropdown" style="color: inherit;" aria-expanded="false">
                    <button class="iv-btn" style="background-color: transparent; padding-top:1px;">
                        <span class="glyphicon glyphicon-folder-close"></span>
                                                                        导出
                        <span class="caret"></span>
                        </button>
            	</a>
            	<ul class="dropdown-menu">
            		<li style="font-size: 12px;"><a onclick="purchaseOrder.list.exportExeclSelect(0)">按勾选导出</a></li>
            		<li style="font-size: 12px;"><a onclick="purchaseOrder.list.exportExeclSelect(1)">按所有页导出</a></li>
            	</ul>
   	</div>
   	
   	<div style="float:left;">
		<button type="button" class="btn-xs btn-transparent font-color-1" onclick="purchaseOrder.list.printPurchaseOrder()" style="margin:0px 5px;" >
			<span class="glyphicon glyphicon-remove"></span>
			<?= TranslateHelper::t('打印采购单')?>
		</button>
	</div>
</div>
		<!-- table -->
<div class="shoplist" style="width:100%;float:left;">
    <table cellspacing="0" cellpadding="0" class="table table-hover" style="width:100%;float:left;font-size: 12px;">
		<tr class="list-firstTr">
			<th title="<?=TranslateHelper::t('全选') ?>"><input type="checkbox" id="select_all" ></th>
			<th width="20%"><?=$sort->link('purchase_order_id',['label'=>TranslateHelper::t('采购单号')]) ?></th>
			<th width="10%"><?=$sort->link('warehouse_id',['label'=>TranslateHelper::t('仓库名称')]) ?></th>
			<th width="10%"><?=$sort->link('supplier_id',['label'=>TranslateHelper::t('供应商')]) ?></th>
			<th width="10%"><?=$sort->link('amount',['label'=>TranslateHelper::t('采购总价')]) ?></th>
			<th width="20%"><?=$sort->link('create_time',['label'=>TranslateHelper::t('采购日期')]) ?></th>
			<th width="10%"><?=$sort->link('payment_status',['label'=>TranslateHelper::t('货款状态')]) ?></th>
			<th width="10%"><?=$sort->link('status',['label'=>TranslateHelper::t('采购状态')]) ?></th>
			<th width="10%"><?= TranslateHelper::t('操作')?></th>
		</tr>
        <?php foreach($list as $index=>$purchase):?>
            <tr <?=!is_int($index / 2)?"class='striped-row'":"" ?>>
            	<td style="text-align:center;"><input type="checkbox" class="select_one" name="orderSelected" value="<?=$purchase['id']?>" ></td>
                <td ><?=$purchase['purchase_order_id'] ?></td>
	            <td ><?php $warehouseName = TranslateHelper::t('未指定');
	          		foreach($warehouse as $wh_id=>$wh_name){
	            		if($wh_id==$purchase['warehouse_id']){
	            			$warehouseName = $wh_name;
	            			break;
	            		}
	            	}
	            	echo $warehouseName;
	            ?></td>
	            <td ><?php $supplierName = TranslateHelper::t('未指定');
	            	foreach($suppliers as $asupplier){
	            		if($asupplier['supplier_id']==$purchase['supplier_id']){
	            			$supplierName = $asupplier['name'];
	            			break;
	            		}
	           		}
	           		echo $supplierName;
	            ?></td>
	            
	            <td ><?=$purchase['amount'] ?></td>
	            <td ><?=$purchase['create_time'] ?></td>
	            <td ><?php $payment = TranslateHelper::t('未指定');
	            	foreach($paymentStatus as $k=>$v){
	            		if($k==$purchase['payment_status']){
	            			$payment = $v;
	            			break;
	            		}
	           		}
	           		echo $payment;
	            ?></td>
	            <td tag="status" orderId="<?=$purchase['id']?>" value="<?=$purchase['status_val'] ?>"><?=$purchase['status'] ?></td>
	            <td >
	            
	            	<button type="button" onclick="purchaseOrder.list.viewPurchaseOrder(<?=$purchase['id']?>)" class="btn-xs btn-transparent font-color-1" title="<?= TranslateHelper::t('查看详情')?>">
	            		<span class="egicon-eye"></span>
	            	</button>
	            <?php if($purchase['status_val'] < PurchaseHelper::ALL_ARRIVED){ ?>
	            		
	            	<button type="button" onclick="purchaseOrder.list.editPurchaseOrder(<?=$purchase['id']?>)" class="btn-xs btn-transparent font-color-1" title="<?= TranslateHelper::t('修改采购单(已到货的采购单不能修改)')?>">
	            		<span class="glyphicon glyphicon-edit" aria-hidden="true"></span>
	            	</button>
	            <?php } ?>
	            
	            <?php if($purchase['status_val'] <= PurchaseHelper::PARTIAL_ARRIVED_CANCEL_LEFT ){ ?>
	            	<button type="button" class="btn-xs btn-transparent font-color-1" onclick="purchaseOrder.list.purchaseChooseStockIn(<?=$purchase['id']?>)" title="<?= TranslateHelper::t('入库')?>">
	            		<span class="glyphicon glyphicon-log-in"></span>
	            	</button>
	            <?php } ?> 	
	            	<?php if($purchase['status_val'] <= PurchaseHelper::WAIT_FOR_ARRIVAL ){ ?>
	            	<button type="button" onclick="purchaseOrder.list.cancelPurchaseOrder(<?=$purchase['id']?>)" class="btn-xs btn-transparent font-color-1" title="<?= TranslateHelper::t('取消此采购单')?>">
	            		<span class="glyphicon glyphicon-remove"></span>
	            	</button>
	            <?php } ?>
	            <?php if($purchase['status_val'] > PurchaseHelper::WAIT_FOR_ARRIVAL && $purchase['status_val'] <= PurchaseHelper::PARTIAL_ARRIVED_CANCEL_LEFT ){ ?>
	            	<button type="button" onclick="purchaseOrder.list.cancelPurchaseOrder(<?=$purchase['id']?>)" class="btn-xs btn-transparent font-color-1" title="<?= TranslateHelper::t('中止入库')?>">
	            		<span class="glyphicon glyphicon-remove"></span>
	            	</button>
	            <?php } ?>
	            </td>
	        </tr>
         
        <?php endforeach;?>
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
    <?= \eagle\widgets\SizePager::widget(['pagination'=>$pagination , 'pageSizeOptions'=>array( 5 , 20 , 50 , 100 , 200 ) , 'class'=>'btn-group dropup']);?>
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

