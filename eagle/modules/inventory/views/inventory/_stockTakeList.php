<?php
use yii\helpers\Html;
use eagle\modules\util\helpers\TranslateHelper;
use yii\helpers\Url;
use eagle\helpers\UserHelper;
$baseUrl = \Yii::$app->urlManager->baseUrl . '/';
$this->registerJsFile($baseUrl."js/project/inventory/stocktake.js", ['depends' => ['yii\web\JqueryAsset']]);

$this->registerJs("inventory.stockTake.init();" , \yii\web\View::POS_READY);

?>
<style>
.create_stocktake_dialog .modal-dialog{
	width: 900px;
	max-height: 700px;
	overflow: auto;
}
.create_stocktake_dialog .modal-body{
	display: inline-block;
}
.show_detail_dialog .modal-dialog{
	width: 900px;
	max-height: 700px;
	overflow: auto;	
}
.div_inner_td{
	width: 100%;
}
</style>
<FORM action="<?= Url::to(['/inventory/inventory/'.yii::$app->controller->action->id])?>" method="GET" style="width:100%;float:left;">
	<div style="width: 100%;float: left;margin-bottom:5px;">
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
  			<div style="float:left;" class="">
				<input name="sdate" id="stocktakelist_startdate" class="eagle-form-control" type="text" placeholder="<?= TranslateHelper::t('从 此日期后')?>" 
					value="<?= (empty($_GET['sdate'])?"":$_GET['sdate']);?>" style="width:150px;margin:0px;height:28px;float:left;" title="<?= TranslateHelper::t('只显示此日期后的采购单') ?>"/>
				<input name="edate" id="stocktakelist_enddate" class="eagle-form-control" type="text" placeholder="<?= TranslateHelper::t('至 此日期前')?>" 
					value="<?= (empty($_GET['edate'])?"":$_GET['edate']);?>" style="width:150px;margin:0px;height:28px;float:left;" title="<?= TranslateHelper::t('只显示此日期前的采购单') ?>"/>
	  		</div>
	  	</div>
	
		<div class="div-input-group" style="float: left;margin-left:5px;">
  			<div style="float:left;">
				<input name="keyword" class="eagle-form-control" type="text" placeholder="<?= TranslateHelper::t('盘点单备注所含关键字')?>" title="<?= TranslateHelper::t('根据输入的盘点单备注字段模糊查询') ?>"  
					value="<?php if(!empty($_GET['keyword'])) echo $_GET['keyword'] ?>" style="width:150px;margin:0px;height:28px;float:left;"/>

				<button type="submit" class="btn btn-default" data-loading-text="<?= TranslateHelper::t('查询中...')?>" style="margin-left:5px;padding: 0px;height: 28px;width: 30px;border-radius: 0px;border: 1px solid #b9d6e8;" title="<?= TranslateHelper::t('查询') ?>">
					<span class="glyphicon glyphicon-search" aria-hidden="true"></span>
				</button>
				<button type="button" id="btn_clear_stocktake" class="btn btn-default" style="margin-left:5px;padding: 0px;height: 28px;width: 30px;border-radius: 0px;border: 1px solid #b9d6e8;" title="<?= TranslateHelper::t('重置') ?>">
					<span class="glyphicon glyphicon-refresh" aria-hidden="true"></span>
				</button>
	  		</div>
	  	</div>
	</div>
</FORM>

<div style="width:100%;float:left;">
	<div style="float:left;">
		<button type="button" onclick="inventory.stockTake.openCreateForm()" class="btn-xs btn-transparent font-color-1" style="margin:0px 5px;">
			<span class="glyphicon glyphicon-plus"></span>
			<?= TranslateHelper::t('新建盘点')?>
		</button>
	</div>
</div>
							
			
<!-- table -->
<div class="stockTakeList" style="margin-top:5px;width:100%;float:left;">
    <table cellspacing="0" cellpadding="0" style="width:100%;fontsize:12px;float:left" class="table table-hover">
		<tr class="list-firstTr">
			<th width="150px"><?=$sort->link('create_time',['label'=>TranslateHelper::t('盘点日期')]) ?></th>
			<th width="100px"><?=$sort->link('stock_take_id',['label'=>TranslateHelper::t('盘点序号')]) ?></th>
			<th width="100px"><?=$sort->link('warehouse_id',['label'=>TranslateHelper::t('仓库')]) ?></th>
			<th width="100px"><?=$sort->link('number_of_sku',['label'=>TranslateHelper::t('产品种类数')]) ?></th>
			<th width="250px"><?=TranslateHelper::t('备注') ?></th>
			<th width="100px"><?=$sort->link('capture_user_id',['label'=>TranslateHelper::t('盘点人')]) ?></th>
			<th width="100px"><?= TranslateHelper::t('操作')?></th>
		</tr>
        <?php if(count($data['datas'])>0): ?>
        <?php foreach($data['datas'] as $index=>$aData):?>
            <tr <?=!is_int($index / 2)?"class='striped-row'":"" ?>>
                <td ><?=$aData['create_time'] ?></td>
                <td ><?=$aData['stock_take_id'] ?></td>
                <td ><?php 
	            		if(isset($warehouse[$aData['warehouse_id']])) echo $warehouse[$aData['warehouse_id']];
	            		else echo TranslateHelper::t("仓库信息丢失(仓库id:".$aData['warehouse_id'].")"); 
	            	?></td>
                <td ><?=$aData['number_of_sku'] ?></td>
	            <td ><?=$aData['comment'] ?></td>
	            <td ><?=UserHelper::getFullNameByUid( $aData['capture_user_id'] ) ?></td>
				<td>
					<button type="button" onclick="inventory.stockTake.showStockTakeDetail('<?=$aData['stock_take_id'] ?>')" class="btn-xs btn-transparent font-color-1" title="<?= TranslateHelper::t('查看详情')?>">
	            		<span class="egicon-eye"></span>
	            	</button>
	            </td>
	        </tr>
         
        <?php endforeach;?>
        <?php else: ?>
        	<tr><td colspan="7" style="text-align: center;"><?=TranslateHelper::t("无记录")?></td></tr>
        <?php endif; ?>
    </table>
</div>
			
<?php if($data['pagination']):?>
<div id="pager-group">
    <?= \eagle\widgets\SizePager::widget(['pagination'=>$data['pagination'] , 'pageSizeOptions'=>array( 5 , 20 , 50 , 100 , 200 ) , 'class'=>'btn-group dropup']);?>
    <div class="btn-group" style="width: 49.6%;text-align: right;">
    	<?=\yii\widgets\LinkPager::widget(['pagination' => $data['pagination'],'options'=>['class'=>'pagination']]);?>
	</div>
</div>
<?php endif;?>

<!-- show create_form dialog -->
<div class="create_stocktake_dialog"></div>
<!-- /.show create_form dialog -->

<!-- show detail dialog -->
<div class="show_detail_dialog"></div>
<!-- /.show detail dialog -->

<!-- response Msg dialog -->
<div class="stockTake_save_result"></div>
<!-- /dialog -->