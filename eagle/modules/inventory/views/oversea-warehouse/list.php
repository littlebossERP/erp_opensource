<?php
use yii\helpers\Html;
use eagle\modules\util\helpers\TranslateHelper;
use yii\helpers\Url;

$this->registerJsFile(\Yii::getAlias('@web')."/js/project/inventory/overseawarehouse_list.js", ['depends' => ['yii\web\JqueryAsset']]);
$this->registerJs("OverseaWarehouse.list.init();" , \yii\web\View::POS_READY);

$this->registerJs("$.initQtip();" , \yii\web\View::POS_READY);
?>

<style>
.div_inner_td{
	width: 100%;
}
.span_inner_td{
	float: left;
	padding: 6px 0px;
}
.div_account
{
	border: 1px solid #ccc;
	width:90%;
	padding:5px 0 5px 20px;
	float:left;
}
.div_account > span
{
	font-size: 15px; 
	line-height: 24px;
	margin-right:20px; 
	float:left;
}
.div_account a
{
    margin-left:0;
    margin-right:15px; 
    line-height: 25px; 
	padding:5px;
}
th
{
	text-align: center !important;
}
</style>

<form action="<?= Url::to(['/inventory/oversea-warehouse/'.yii::$app->controller->action->id])?>" method="POST" id="overseawarehouse_list_params_form" style="width:100%;float:left;">
    <div style="width: 100%; float:left;">
        <div class="div-input-group" style="float: left;margin-left:20px; margin-bottom:10px;">
    		<input name="skus" id="search_sku" class="eagle-form-control" type="text" value="<?= $stockData['skus']?>" placeholder="<?= TranslateHelper::t('海外仓SKU，多个之间用英文逗号 , 隔开')?>" title="<?= TranslateHelper::t('根据输入的小老板订单号查询') ?>"
    		    style="width:250px;margin:0px 10px;height:28px;float:left;"/>
    		
    		<button id="profit_search" class="iv-btn btn-search btn-spacing-middle">搜索</button>
    	</div>
    	<div class="div_account">
    	    <span>绑定账号：</span>
    	    <div style="width: 85%; float:left;">
      		    <?php foreach($account as $key=>$v){?>	
      		        <a style="<?=$v['accountid'] == $accountid ? 'color:#337ab7; border:1px solid #ddd;' : 'color:#999;' ?>" href="javascript: OverseaWarehouse.list.selectAccount('<?= $v['accountid']?>');" style="margin-right:20px;">
      		            <?= $v['carrier_name']?>
      		        </a>
    			<?php } ?>
    		</div>
    	</div>
    </div>
    <input name="carrier_code" type="text" style="display:none;" value="<?= $carrier_code?>"></input>
    <input name="third_party_code" type="text" style="display:none;" value="<?= $third_party_code?>"></input>
    <input name="accountid" type="text" style="display:none;" value="<?= $accountid?>"></input>
    <input name="warehouse_id" type="text" style="display:none;" value="<?= $warehouse_id?>"></input>
    <input name="per_page" type="text" style="display:none;" value="<?= $stockData['per_page']?>"></input>
    <input name="page" type="text" style="display:none;" value="<?= $stockData['page']?>"></input>
</form>
<!-- 功能按钮  -->
<div style="float:left; margin:10px 5px; width: 90%; ">
    <button type="button" class="iv-btn btn-important" style="border-style: none; margin-right: 20px;" onclick="OverseaWarehouse.list.showAutomaticMatchingBox()"><?= TranslateHelper::t('自动配对本地SKU') ?></button>
    <button type="button" class="iv-btn btn-important" style="border-style: none; margin-right: 20px;" onclick="OverseaWarehouse.list.synchronizeSku()"><?= TranslateHelper::t('同步海外仓SKU和库存') ?></button>
    
    <div style="float:right; line-height: 24px; font-size: 15px;">
                        海外仓SKU共 <?= $stockData['count']?> 个，其中已配对
        <span id="Ymatching" style="color:#91c854;"><?= $stockData['Ymatching']?></span>
                        个，未配对
        <span id="Nmatching" style="color:#ed5466;"><?= $stockData['Nmatching']?></span>
                        个
    </div>
</div>
							
<!-- table -->
<div class="inventorylist" style="width:100%;float:left;">
	<table cellspacing="0" cellpadding="0" style="width:100%;float:left;" class="table table-hover">
		<tr class="list-firstTr">
			<th width="15%">海外SKU</th>
			<th width="20%">产品标题</th>
			<th width="20%">本地SKU</th>
			<th width="8%">配对状态</th>
			<th width="6%">当前库存</th>
			<th width="6%">在途库存</th>
			<th width="6%">占用库存</th>
			<th width="6%">可用库存</th>
			<th width="8%">操作</th>
		</tr>
		    <?php if(count($stockData)>0): ?>
		    <?php foreach($stockData['data'] as $index=>$stock):?>
		<tr <?=!is_int($index / 2)?"class='striped-row'":"" ?> stock_id="<?=$stock['id'] ?>">
			<td><?=$stock['seller_sku'] ?></td>
			<td><?=$stock['title'] ?></td>
			<td><input name="sku[]" type="text" value="<?=$stock['sku'] ?>" style="width: 100%;" <?=empty($stock['sku']) ? '' : 'disabled="disabled"' ?> placeholder="<?= TranslateHelper::t('输入已存在商品库的SKU')?>"></input></td>
			<td name="matchingStatus[]" style="text-align:center;"><?=empty($stock['sku']) ? "<p style='color:#ed5466;'>未配对</p>" : "<p style='color:#91c854;'>已配对</p>" ?></td>
			<td style="text-align:center;"><?=$stock['current_inventory'] ?></td>
			<td style="text-align:center;"><?=$stock['adding_inventory'] ?></td>
			<td style="text-align:center;"><?=$stock['reserved_inventory'] ?></td>
			<td style="text-align:center;"><?=$stock['usable_inventory'] ?></td>
			<td style="text-align:center;">
    			<div class="btn-group product_btn_menu" style="white-space: nowrap;font-size:12px">
    					<button type="button" class="btn btn-default" name="btnMatching[]"
    						<?=empty($stock['sku']) ? 'onclick="OverseaWarehouse.list.matchingOne(this, 0)">配对' : 'onclick="OverseaWarehouse.list.matchingOne(this, 1)">修改' ?>
                        </button>
    					<!--  <button type="button" class="btn btn-default dropdown-toggle"
    						data-toggle="dropdown" aria-expanded="false">
    						<span class="caret"></span> <span class="sr-only">Toggle Dropdown</span>
    					</button>
    					<ul class="dropdown-menu" role="menu">
    
    						<li style="font-size:12px"><a onclick="productList.list.editProduct()"><?=TranslateHelper::t('修改') ?></a></li>
    						<li style="font-size:12px"><a
    							onclick="productList.list.deleteProduct()"><?=TranslateHelper::t('删除') ?></a></li>
    						<li style="font-size:12px"><a onclick="productList.list.copyProduct('')"><?=TranslateHelper::t('复制产品') ?></a></li>
    					</ul>-->
    				</div>
			</td>
		</tr>
		<?php endforeach;?>
		<?php endif; ?>
	</table>
</div>
<div style="width: 100%; float: left;">
    <?php if($stockData['pagination']):?>
    <div id="matching-pager-group">
        <?= \eagle\widgets\SizePager::widget(['pagination'=>$stockData['pagination'] , 'pageSizeOptions'=>array( 5 , 20 , 50 , 100 , 200 ) , 'class'=>'btn-group dropup']);?>
        <div class="btn-group" style="width: 49.6%;text-align: right;">
        	<?=\eagle\widgets\ELinkPager::widget(['pagination' => $stockData['pagination'],'options'=>['class'=>'pagination']]);?>
    	</div>
    </div>
    <?php endif;?>
</div>









