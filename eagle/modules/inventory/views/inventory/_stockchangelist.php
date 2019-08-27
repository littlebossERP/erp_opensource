<?php
use yii\helpers\Html;
use eagle\modules\util\helpers\TranslateHelper;
use yii\helpers\Url;

$this->registerJsFile(\Yii::getAlias('@web')."/js/project/inventory/stockChange.js", ['depends' => ['yii\web\JqueryAsset']]);

$this->registerJs("inventory.stockchangeList.init();" , \yii\web\View::POS_READY);

?>
<style>
.div_inner_td{
	width: 100%;
}
.span_inner_td{
	float: left;
	padding: 6px 0px;
}
.viewStockChangeDetail .modal-body{
	max-height: 600px;
	overflow-y: auto;	
}
</style>
<FORM action="<?= Url::to(['/inventory/inventory/'.yii::$app->controller->action->id])?>" method="GET" style="width:100%;float:left;">
  	<div style="width: 100%;float: left;margin-bottom: 10px;">
  		<div class="div-input-group" title="<?= TranslateHelper::t('按仓库过滤显示结果') ?>" style="float: left;margin-left:5px;">
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
  		
  		<div class="div-input-group" title="<?= TranslateHelper::t('按出入库操作类型过滤显示结果') ?>" style="float: left;margin-left:5px;">
  			<div style="float:left;" class="input-group">
	  			<SELECT name="stockChangeType" value="" class="eagle-form-control" style="width:150px;margin:0px">
	  				<OPTION value="" <?=(!isset($_GET['stockChangeType']) or empty($_GET['warehouse_id']) )?" selected ":'' ?>><?= TranslateHelper::t('操作类型') ?></OPTION>
	  					<?php foreach($stockChangeType as $k=>$v){
							echo "<option value='".$k."'";
							if(isset($_GET['stockChangeType'])&& $_GET['stockChangeType']==$k) echo ' selected="selected" ';
							echo ">".$v."</option>";
						} ?>
	  			</SELECT>
  			</div>
  		</div>
  		
  		<div class="div-input-group" style="float: left;margin-left:5px;">
  			<div style="float:left;" class="">
				<input name="sdate" id="changelist_startdate" class="eagle-form-control" type="text" placeholder="<?= TranslateHelper::t('从 此日期后')?>" 
					value="<?= (empty($_GET['sdate'])?"":$_GET['sdate']);?>" style="width:150px;margin:0px;height:28px;float:left;" title="<?= TranslateHelper::t('只显示此日期后的采购单') ?>"/>
				<input name="edate" id="changelist_enddate" class="eagle-form-control" type="text" placeholder="<?= TranslateHelper::t('至 此日期前')?>" 
					value="<?= (empty($_GET['edate'])?"":$_GET['edate']);?>" style="width:150px;margin:0px;height:28px;float:left;" title="<?= TranslateHelper::t('只显示此日期前的采购单') ?>"/>
	  		</div>
	  	</div>
  	
  		<div class="div-input-group" style="float: left;margin-left:5px;">
  			<div style="float:left;">
				<input name="keyword" class="form-control" type="text" placeholder="<?= TranslateHelper::t('输入出入库单号或备注')?>" title="<?= TranslateHelper::t('根据输入的出入库单号或备注字段模糊查询') ?>"  
					value="<?php if(!empty($_GET['keyword'])) echo $_GET['keyword'] ?>" style="width:150px;margin:0px;height:28px;float:left;"/>

				<button type="submit" class="btn btn-default" data-loading-text="<?= TranslateHelper::t('查询中...')?>" style="margin-left:5px;padding: 0px;height: 28px;width: 30px;border-radius: 0px;border: 1px solid #b9d6e8;" title="<?= TranslateHelper::t('查询') ?>">
					<span class="glyphicon glyphicon-search" aria-hidden="true"></span>
				</button>
				<button type="button" id="btn_clear_stockchange" class="btn btn-default" style="margin-left:5px;padding: 0px;height: 28px;width: 30px;border-radius: 0px;border: 1px solid #b9d6e8;" title="<?= TranslateHelper::t('重置') ?>">
					<span class="glyphicon glyphicon-refresh" aria-hidden="true"></span>
				</button>
	  		</div>
	  	</div>
  	</div>
</FORM>
			
<!-- table -->
<div class="stockChangeList" style="width:100%;float:left;">
    <table cellspacing="0" cellpadding="0" style="width:100%;font-size:12px;float:left;" class="table table-hover">
		<tr class="list-firstTr">
			<th width="150px"><?=$sort->link('stock_change_id',['label'=>TranslateHelper::t('出入库单号')]) ?></th>
			<th width="150px"><?=$sort->link('create_time',['label'=>TranslateHelper::t('出入库日期')]) ?></th>
			<th width="150px"><?=$sort->link('warehouse_id',['label'=>TranslateHelper::t('所在仓库')]) ?></th>
			<th width="80px"><?=$sort->link('change_type',['label'=>TranslateHelper::t('类型')]) ?></th>
			<th width="100px"><?=$sort->link('reason',['label'=>TranslateHelper::t('出入库原因')]) ?></th>
			<th width="250px"><?=$sort->link('comment',['label'=>TranslateHelper::t('备注')]) ?></th>
			<th width="80px"><?=$sort->link('capture_user_id',['label'=>TranslateHelper::t('操作者')]) ?></th>
			<th width="80px"><?=TranslateHelper::t('操作') ?></th>
		</tr>
        <?php if(count($data)>0): ?>
        <?php foreach($data['data'] as $index=>$adata):?>
            <tr <?=!is_int($index / 2)?"class='striped-row'":"" ?>>
                <td><?=$adata['stock_change_id'] ?></td>
                <td><?=$adata['create_time'] ?></td>
	            <td><?php 
	            		if(isset($warehouse[$adata['warehouse_id']])) echo $warehouse[$adata['warehouse_id']];
	            		else echo TranslateHelper::t("仓库信息丢失(仓库id:".$adata['warehouse_id'].")"); 
	            	?></td>
	             <td><?php 
	            		if(isset($stockChangeType[$adata['change_type']])) echo $stockChangeType[$adata['change_type']];
	            		else echo TranslateHelper::t("出入库类型信息丢失(仓库id:".$adata['change_type'].")"); 
	            	?></td>
	            <td><?php 
	            		if(isset($stockChangeReason[$adata['reason']])) echo $stockChangeReason[$adata['reason']];
	            		else echo TranslateHelper::t("出入库原因信息丢失(".$adata['reason'].")"); 
	            	?></td>
	            <td><?=$adata['comment'] ?></td>
	            <td><?=$adata['user_name'] ?></td>
	            <td>
	            	<button type="button" onclick="inventory.stockchangeList.showChangeDetail('<?=$adata['stock_change_id'] ?>','<?=base64_encode($adata['stock_change_id']) ?>')" class="btn-xs btn-transparent font-color-1" title="<?= TranslateHelper::t('查看')?>">
	            		<span class="egicon-eye"></span>
	            	</button>
	            </td>
	        </tr>
         
        <?php endforeach;?>
        <?php else:?>
        	<tr><td colspan="8" style="text-align:center;"><?= TranslateHelper::t('无数据记录') ?></td></tr>
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

<!-- viewInventoryHistory dialog -->
<div class="viewStockChangeDetail"></div>