<?php
use yii\helpers\Html;
use eagle\modules\util\helpers\TranslateHelper;
use yii\helpers\Url;
use eagle\modules\permission\apihelpers\UserApiHelper;

$this->registerJsFile(\Yii::getAlias('@web')."/js/project/inventory/inventory_list.js?v=1.2", ['depends' => ['yii\web\JqueryAsset']]);
$this->registerJsFile(\Yii::getAlias('@web')."/js/project/inventory/edit_stock.js?v=1.1", ['depends' => ['yii\web\JqueryAsset']]);

$this->registerJs("inventory.list.init();" , \yii\web\View::POS_READY);
$this->registerJs("$.initQtip();" , \yii\web\View::POS_READY);
$this->registerJs("$('.prod_img').popover();" , \yii\web\View::POS_READY);
?>

<style>
.egicon-edit{
	margin: 5px auto;
	display:block;
}
.table-hover tr:hover {
background-color: #afd9ff !important;
}
</style>

<?php
//判断子账号是否有编辑权限
$is_inventory_edit = true;
$isMainAccount = UserApiHelper::isMainAccount();
if(!$isMainAccount){
	$ischeck = UserApiHelper::checkModulePermission('inventory_edit');
	if(!$ischeck){
		$is_inventory_edit = false;
}}?>

<?php if(!empty($_GET['product_type']) && $_GET['product_type']=="B"){ ?>
<div class="alert alert-warning" role="alert" style="width:100%;">
	<h4><?= TranslateHelper::t('关于捆绑产品库存：') ?></h4>
	<span><?= TranslateHelper::t('捆绑产品为一种组合产品，它的子产品的库存变动时刻影响着捆绑产品的可用库存，且可能多个捆绑产品都绑定了某些相同子产品，库存实际是相互占用的。因此，这里显示的捆绑产品库存只是一个参考数值，不推荐直接作为可销售量。') ?></span><br>
	<span><?= TranslateHelper::t('捆绑产品库存数计算方式：(每个子产品的库存数)/(该子产品再捆绑产品中的捆绑数)，取结果最小的值的整数部分作为捆绑库存。') ?></span><br>
	<span><?= TranslateHelper::t('捆绑产品不显示配货待发数。') ?></span><br>
	<span><?= TranslateHelper::t('阁下可以将鼠标移动到捆绑产品的“货架位置”一栏，显示所有子产品对应的货架位置。') ?></span><br>
</div>
<?php } ?>

<FORM class="form-horizontal" action="<?= Url::to(['/inventory/inventory/'.yii::$app->controller->action->id])?>" method="GET" style="width:100%;float:left;">
	<div style="width: 100%;float: left;margin-bottom: 10px;">
  		<div class="div-input-group" title="<?= TranslateHelper::t('按仓库过滤显示结果') ?>">
  			<div style="float:left; margin-right: 10px; " class="input-group">
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
  		
  		<div class="div-input-group" title="<?= TranslateHelper::t('按产品类型过滤显示结果') ?>">
  			<div style="float:left; margin-right: 10px; " class="input-group">
	  			<SELECT name="product_type" value="" class="eagle-form-control" style="width:150px;margin:0px">
	  				<OPTION value="" <?=(empty($_GET['product_type']) or $_GET['product_type']!=="B")?"selected":"" ?>><?= TranslateHelper::t('普通/变参子产品') ?></OPTION>
  					<OPTION value="B" <?=(!empty($_GET['product_type']) && $_GET['product_type']=="B")?"selected":"" ?>><?= TranslateHelper::t('捆绑产品') ?></OPTION>
	  			</SELECT>
  			</div>
  		</div>
  		
  		<div class="div-input-group" title="<?= TranslateHelper::t('按库存状态过滤显示结果') ?>">
  			<div style="float:left; margin-right: 10px; " class="input-group">
	  			<SELECT name="stock_status" value="" class="eagle-form-control" style="width:150px;margin:0px">
	  				<OPTION value="" <?=empty($_GET['stock_status']) ? "selected" : "" ?>><?= TranslateHelper::t('库存量状态') ?></OPTION>
  					<OPTION value="1" <?=(!empty($_GET['stock_status']) && $_GET['stock_status']=="1")?"selected":"" ?>><?= TranslateHelper::t('库存量 低于 安全库存') ?></OPTION>
  					<OPTION value="2" <?=(!empty($_GET['stock_status']) && $_GET['stock_status']=="2")?"selected":"" ?>><?= TranslateHelper::t('库存量 等于 0') ?></OPTION>
  					<OPTION value="3" <?=(!empty($_GET['stock_status']) && $_GET['stock_status']=="3")?"selected":"" ?>><?= TranslateHelper::t('库存量 大于 0') ?></OPTION>
  					<OPTION value="4" <?=(!empty($_GET['stock_status']) && $_GET['stock_status']=="4")?"selected":"" ?>><?= TranslateHelper::t('库存量 小于 0') ?></OPTION>
  					<OPTION value="5" <?=(!empty($_GET['stock_status']) && $_GET['stock_status']=="5")?"selected":"" ?>><?= TranslateHelper::t('库存量 不等于 0') ?></OPTION>
	  			</SELECT>
  			</div>
  		</div>
  		
  		<div class="div-input-group" style="float: left;margin-left:5px;">
  			<div style="float:left;">
				<input name="keyword" class="form-control" type="text" placeholder="<?= TranslateHelper::t('输入产品sku或名称')?>" title="<?= TranslateHelper::t('根据输入的产品sku或名称字段模糊查询') ?>"  
					value="<?php if(!empty($_GET['keyword'])) echo $_GET['keyword'] ?>" 
					style="width:250px;margin:0px;height:28px;float:left;"/>

				<button type="submit" class="btn btn-default" data-loading-text="<?= TranslateHelper::t('查询中...')?>" style="margin:0px;padding: 0px;height: 28px;width: 30px;border-radius: 0px;border: 1px solid #b9d6e8;" title="<?= TranslateHelper::t('查询') ?>">
					<span class="glyphicon glyphicon-search" aria-hidden="true"></span>
				</button>
				<button type="button" id="btn_clear" class="btn btn-default" style="margin:0px;padding: 0px;height: 28px;width: 30px;border-radius: 0px;border: 1px solid #b9d6e8;" title="<?= TranslateHelper::t('重置') ?>">
					<span class="glyphicon glyphicon-refresh" aria-hidden="true"></span>
				</button>
	  		</div>
	  	</div>
	  	<input name="class_id" value="<?= isset($_GET['class_id']) ? $_GET['class_id'] : ''; ?>" style="display: none; " />
  	</div>
  	<div style="float: left;">
	  	<?php if(empty($_GET['product_type']) or $_GET['product_type']!=="B"){ ?>
  		<button type="button" class="btn-xs btn-transparent font-color-1" style="margin-left: 20px; <?php if(!$is_inventory_edit) echo 'color: lightgray; '?>" onclick="inventory.list.ShowBatchEditStock()" <?php if(!$is_inventory_edit) echo 'disabled="disabled"'?>>
			<span class="glyphicon glyphicon-log-in" aria-hidden="true" style="height:16px;"></span>
			<?=TranslateHelper::t('批量编辑')?>
		</button>
	  	<button type="button" class="btn-xs btn-transparent font-color-1" style="margin-left: 20px; <?php if(!$is_inventory_edit) echo 'color: lightgray; '?>" onclick="inventory.list.batchDeleteStock()" <?php if(!$is_inventory_edit) echo 'disabled="disabled"'?>>
			<span class="egicon-trash" aria-hidden="true" style="height:16px;"></span>
			<?=TranslateHelper::t('批量删除')?>
		</button>
		<?php }?>
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
			<?php if(empty($_GET['product_type']) or $_GET['product_type']!=="B"){ ?>
			<li style="font-size: 12px;"><a onclick="inventory.list.exportExcelSelect(0)">按勾选导出</a></li>
			<?php }?>
			<li style="font-size: 12px;"><a onclick="inventory.list.exportExcelSelect(1)">按所有页导出</a></li>
		</ul>
   	</div>
   	
   	<button type="button" class="iv-btn btn-important purchase_top_button" onclick="inventory.list.UpdateStockPrice()" style="float: right; margin-right: 2%; padding: 5px 5px 5px 7px; " qtipkey="stock_update_price_log" >同步商品管理采购价</button>
</FORM>
							
<!-- table -->
<div class="inventorylist" style="width:100%;float:left;">
	<table cellspacing="0" cellpadding="0" class="table table-hover" style="width:100%;float:left;" >
		<tr class="list-firstTr">
			<th width="30px" title="<?=TranslateHelper::t('全选') ?>" style="text-align:center;"><input type="checkbox" id="select_all" ></th>
			<th nowrap width="50px"><?=TranslateHelper::t('商品图片') ?></th>
			<th width="150px"><?=$sort->link('sku',['label'=>'SKU']) ?></th>
			<th width="250px"><?=$sort->link('name',['label'=>TranslateHelper::t('产品名称')]) ?></th>
			<th width="150px"><?=$sort->link('prod_name_ch',['label'=>TranslateHelper::t('中文配货名称')]) ?></th>
			<!--
			<th width="10%"><?=$sort->link('status',['label'=>TranslateHelper::t('状态')]) ?></th>
			 -->
			 <th width="100px"><?=$sort->link('class_id',['label'=>TranslateHelper::t('分类')]) ?></th>
		<?php if(empty($_GET['product_type']) or $_GET['product_type']!=="B"){ ?>
			<th width="100px"><?=$sort->link('type',['label'=>TranslateHelper::t('产品类型')]) ?></th>
		<?php }else{ ?>
			<th width="50px"><?=TranslateHelper::t('产品类型') ?></th>
		<?php } ?>
			<th width="120px"><?=$sort->link('warehouse_id',['label'=>TranslateHelper::t('仓库名称')]) ?></th>
		<?php if(empty($_GET['product_type']) or $_GET['product_type']!=="B"){ ?>
			<th width="80px"><?=$sort->link('location_grid',['label'=>TranslateHelper::t('货架位置')]) ?></th>
		<?php }else{ ?>
			<th width="80px"><?=TranslateHelper::t('货架位置') ?></th>
		<?php } ?>
			<th width="60px"><?=$sort->link('qty_in_stock',['label'=>TranslateHelper::t('库存数量')]) ?></th>
			<th width="60px"><?=$sort->link('qty_purchased_coming',['label'=>TranslateHelper::t('在途数量')]) ?></th>
			<th width="60px"><?=$sort->link('qty_ordered',['label'=>TranslateHelper::t('配货待发')]) ?></th>
			<th width="60px"><?=$sort->link('average_price',['label'=>TranslateHelper::t('单价')]) ?></th>
			<th width="60px"><?=$sort->link('stock_total',['label'=>TranslateHelper::t('总价')]) ?></th>
			<?php if(empty($_GET['product_type']) or $_GET['product_type']!=="B"){ ?>
			<th width="60px"><?=$sort->link('safety_stock',['label'=>TranslateHelper::t('安全库存')]) ?></th>
			<th width="80px" qtipkey="stock_change_log"><?= TranslateHelper::t('操作')?></th>
			<?php } ?>
		</tr>
		    <?php if(count($stockData)>0): ?>
		    <?php foreach($stockData['data'] as $index=>$stock):?>
		<tr <?=!is_int($index / 2)?"class='striped-row'":"" ?>>
			<td style="text-align:center;"><input type="checkbox" class="select_one" name="orderSelected" value="<?=$stock['prod_stock_id'] ?>" ></td>
			<td nowrap><div style="height: 50px;">
					<img class="prod_img" style="max-height: 50px; max-width: 80px;"
						src="<?=$stock['photo_primary'] ?>" 
						data-toggle="popover" data-content="<img width='250px' src='<?=$stock['photo_primary'] ?>'>" data-html="true" data-trigger="hover" />
				</div>
			</td>
			<td name="chang_sku[]"><?=$stock['sku'] ?></td>
		    <td><?=$stock['name'] ?></td>
		    <td><?=$stock['prod_name_ch'] ?></td>
		    <!-- 
		    <td><?php if(isset($prodStatus[$stock['status']])) echo $prodStatus[$stock['status']]; ?></td>
		    -->
		    <td><?php if(isset($stock['class_name'])) echo $stock['class_name']; ?></td>
		    <td><?php if(isset($prodTypes[$stock['type']])) echo $prodTypes[$stock['type']]; ?></td>
			<td><?php 
			    if(isset($warehouse[$stock['warehouse_id']])) echo $warehouse[$stock['warehouse_id']];
			    else echo TranslateHelper::t('仓库信息丢失(仓库id:'.$stock['warehouse_id'].')'); 
			?></td>
			<td title="<?=$stock['location_grid'] ?>"><?=(empty($_GET['product_type']) or $_GET['product_type']!=="B")?$stock['location_grid']:TranslateHelper::t('混合') ?></td>
			<td <?php if((empty($_GET['product_type']) or $_GET['product_type']!=="B") && $stock['qty_in_stock'] < $stock['safety_stock']) echo 'style="color:red"' ?>>
				<?=$stock['qty_in_stock'] ?>
			</td>
			<td><?=$stock['qty_purchased_coming'] ?></td>
			<td><?=$stock['qty_ordered'] ?></td>
			<td><?=$stock['average_price'] ?></td>
			<td><?=$stock['stock_total'] ?></td>
			<?php if(empty($_GET['product_type']) or $_GET['product_type']!=="B"){ ?>
			<td><?=$stock['safety_stock'] ?></td>
			<td>
				<a onclick="<?php if($is_inventory_edit) echo 'inventory.list.showEditStock('.$stock['prod_stock_id'].')' ?>" style="margin: auto; " <?php if(!$is_inventory_edit) echo 'disabled="disabled"'?>>
			    	<span class="glyphicon egicon-edit" title="更新"></span>
			    </a>
			    <div class="btn-group product_btn_menu" style="white-space: nowrap;font-size:10px">
    				<button type="button" class="btn btn-default" onclick="inventory.list.viewInventoryHistory(this,<?=$stock['warehouse_id'] ?>)" style="padding:0px; "><?=TranslateHelper::t('库存变化') ?> </button>
    				<button type="button" class="btn btn-default dropdown-toggle" style="padding:0px 5px; "
    					data-toggle="dropdown" aria-expanded="false">
    					<span class="caret"></span> <span class="sr-only">Toggle Dropdown</span>
    				</button>
    				<ul class="dropdown-menu" role="menu">
    					<li style="font-size:10px"><a onclick="<?php if($is_inventory_edit) echo 'inventory.list.showEditStock('.$stock['prod_stock_id'].')'?>" <?php if(!$is_inventory_edit) echo 'disabled="disabled"'?>><?=TranslateHelper::t('更新') ?></a></li>
    					<li style="font-size:10px"><a onclick="<?php if($is_inventory_edit) echo "inventory.list.deleteStock(".$stock['prod_stock_id'].", '".(empty($warehouse[$stock['warehouse_id']]) ? "" : "仓库: ".$warehouse[$stock['warehouse_id']].", SKU: ".$stock['sku'])."')" ?>" <?php if(!$is_inventory_edit) echo 'disabled="disabled"'?>><?=TranslateHelper::t('删除') ?></a></li>
    				</ul>
    			</div>
			</td>
			<?php } ?>
		</tr>
		<tr style="background-color: #d9d9d9;">
			<td colspan="16" border:1px solid #d1d1d1" style="padding: 2.5px !important;"></td>
		</tr>
		<?php endforeach;?>
		<?php endif; ?>
	</table>
</div>

<input id="search_condition" type="hidden" value="<?php echo $search_condition;?>">
<input id="search_count" type="hidden" value="<?php echo $stockData['pagination']->totalCount;?>">
			
<?php if($stockData['pagination']):?>
<div id="pager-group">
    <?= \eagle\widgets\SizePager::widget(['pagination'=>$stockData['pagination'] , 'pageSizeOptions'=>array( 5 , 20 , 50 , 100 , 200 ) , 'class'=>'btn-group dropup']);?>
    <div class="btn-group" style="width: 49.6%;text-align: right;">
    	<?=\yii\widgets\LinkPager::widget(['pagination' => $stockData['pagination'],'options'=>['class'=>'pagination']]);?>
	</div>
</div>
<?php endif;?>


<!-- viewInventoryHistory dialog -->
<SCRIPT type="text/javascript">
	var warehouseInfo = new Array();
	<?php foreach ($warehouse as $k=>$v){ ?>
	warehouseInfo.push({key:"<?php echo $k;?>", value:"<?php echo htmlspecialchars($v, ENT_QUOTES);?>"});
	<?php } ?>
</SCRIPT>

<div class="viewHistory"></div>









