<?php
use eagle\modules\util\helpers\TranslateHelper;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\jui\Dialog;
use yii\data\Sort;
use yii\jui\JuiAsset;


$baseUrl = \Yii::$app->urlManager->baseUrl;
$this->registerJsFile($baseUrl."/js/project/inventory/purchase_to_stockin.js", ['depends' => ['yii\web\JqueryAsset']]);

$this->registerJs("inventory.purchase2stockin.init();" , \yii\web\View::POS_READY);
//$this->registerCssFile ( $baseUrl . "/css/inventory/inventory.css" );
?>
<style>
.arrived_pruchaseOrder_win .modal-dialog{
	width: 700px;
	max-height: 700px;
	overflow-y: auto;	
}
#arrived_purchase_order_tb td , #arrived_purchase_order_tb th{
	padding: 4px;
	border: 1px solid rgb(202,202,202);
	vertical-align: middle;
}
</style>
<div style="width:100%;clear:both;margin-bottom:5px;"  class="alert alert-warning" role="alert">
<?= TranslateHelper::t('为避免采购单之间 仓库位置，数量等信息混淆，使用导入采购单的入库方式每次只能导入一个采购单。并且一旦导入后，搜索选择产品和其他导入功能将不能使用，采购数量也不能更改(暂时不支持部分到货，默认必须全部到货)。如果要重新开启这些功能，需要点击“解除采购单绑定”按钮，这样，后续操作则和选择的采购单没有关联(不过已导入的产品信息会保留在入库产品列表，如果不需要，需要手动“取消”)。') ?>
</div>
<FORM  id="filter_arrived_purchase_order_form" style="">
	<div style="width: 100%;float: left;">
  		<div class="div-input-group" title="<?= TranslateHelper::t('按仓库过滤显示结果') ?>">
  			<div style="float:left;" class="input-group">
	  			<SELECT name="warehouse_id" value="" class="eagle-form-control" style="width:150px;margin:5px 5px 5px 0px;">
	  				<OPTION value="" <?=(!isset($_GET['warehouse_id']) or !is_numeric($_GET['warehouse_id']) )?" selected ":'' ?>><?= TranslateHelper::t('所在仓库') ?></OPTION>
	  					<?php foreach($warehouse as $wh_id=>$wh_name){
							echo "<option value='".$wh_id."'";
							if(isset($_GET['warehouse_id']) && $_GET['warehouse_id']==$wh_id && is_numeric($_GET['warehouse_id'])) echo " selected ";
							echo ">".$wh_name."</option>";						
						} ?>
	  			</SELECT>
  			</div>
  		</div>
  		
  		<div class="div-input-group" title="<?= TranslateHelper::t('此操作只会显示全部到货的采购单') ?>">
  			<div style="float:left;" class="input-group">
	  			<input name="status" type="text" class="eagle-form-control" value="<?= TranslateHelper::t('全部到货')?>" disabled="disabled" style="width:150px;margin:5px"/>
  			</div>
  		</div>
  		
  		<div class="div-input-group" style="float: left;">
  			<div style="float:left;">
				<input name="keyword" class="form-control" type="text" placeholder="<?= TranslateHelper::t('输入完整或部分采购单号')?>" title="<?= TranslateHelper::t('根据输入的采购单号字段模糊查询') ?>"  
					value="<?php if(!empty($_GET['keyword'])) echo $_GET['keyword'] ?>" style="width:150px;margin:5px;height:28px;float:left;"/>

				<button type="button" class="btn btn-default" id="filter_arrived_purchase_order_btn" data-loading-text="<?= TranslateHelper::t('查询中...')?>" style="margin:5px;padding: 0px;height: 28px;width: 30px;border-radius: 0px;border: 1px solid #b9d6e8;" title="<?= TranslateHelper::t('搜索') ?>">
					<span class="glyphicon glyphicon-search" aria-hidden="true"></span>
				</button>
	  		</div>
	  	</div>
  	</div>
</FORM>
		
<!-- table -->
<div class="arrived_purchase_rder_list">
    <table id="arrived_purchase_order_tb" cellspacing="0" cellpadding="0" style="width:100%;font-size:12px;" class="table table-hover">
		<tr class="list-firstTr">
			<th width="20%"><?=$sort->link('purchase_order_id',['label'=>TranslateHelper::t('采购单号')]) ?></th>
			<th width="15%"><?=$sort->link('warehouse_id',['label'=>TranslateHelper::t('仓库名称')]) ?></th>
			<th width="15%"><?=TranslateHelper::t('供应商') ?></th>
			<th width="15%"><?=TranslateHelper::t('采购总价') ?></th>
			<th width="25%"><?=$sort->link('create_time',['label'=>TranslateHelper::t('采购日期')]) ?></th>
			<th width="10%"><?=TranslateHelper::t('操作') ?></th>
		</tr>
        <?php foreach($purchaseListData['list'] as $purchase):?>
            <tr>
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
	            <td >
	            	<button type="button" class="btn-xs btn-info" style="border-style:none;" 
	            		onclick="inventory.purchase2stockin.passPurchaseDataToStockInWin(<?=$purchase['id']?>)">
	            		<?=TranslateHelper::t('选择') ?>
	            	</button>
	            </td>
	        </tr>
         
        <?php endforeach;?>
    </table>
    <!-- Modal -->
</div>

<?php if($purchaseListData['pagination']):?>
<div id="purchase_arrived_list_view_pager">
    <?= \eagle\widgets\SizePager::widget(['isAjax'=>true ,'pagination'=>$purchaseListData['pagination'] , 'pageSizeOptions'=>array( 5 , 20 , 50 , 100 , 200 ) , 'class'=>'btn-group dropup']);?>
    <div class="btn-group" style="width: 49.6%;text-align: right;">
    	<?=\eagle\widgets\ELinkPager::widget(['isAjax'=>true ,'pagination' => $purchaseListData['pagination'],'options'=>['class'=>'pagination']]);?>
	</div>
</div>
<?php endif;?>

<!-- Modal -->
<div class="create_or_edit_purchase_win"></div>
<!-- /.modal-dialog -->
<!-- Modal -->
<div class="operation_result"></div>
<!-- /.modal-dialog -->

<?php 
$options = array();
$options['pagerId'] = 'purchase_arrived_list_view_pager';// 下方包裹 分页widget的id
$options['action'] =\Yii::$app->request->getPathInfo(); // ajax请求的 action
$options['page'] = $purchaseListData['pagination']->getPage();// 当前页码
$options['per-page'] = $purchaseListData['pagination']->getPageSize();// 当前page size
$options['sort'] = isset($_REQUEST['sort'])?$_REQUEST['sort']:'';// 当前排序
$this->registerJs('$("#arrived_purchase_order_tb").initGetPageEvent('.json_encode($options).')' , \Yii\web\View::POS_READY);

?>