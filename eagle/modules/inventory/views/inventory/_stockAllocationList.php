<?php
use yii\helpers\Html;
use eagle\modules\util\helpers\TranslateHelper;
use yii\helpers\Url;
use eagle\helpers\UserHelper;
$baseUrl = \Yii::$app->urlManager->baseUrl . '/';
$this->registerJsFile($baseUrl."js/project/inventory/stockallocation.js", ['depends' => ['yii\web\JqueryAsset']]);

$this->registerJs("inventory.stockAllocation.init();" , \yii\web\View::POS_READY);

?>
<style>
.create_stockallocation_dialog .modal-dialog{
	width: 900px;
	max-height: 700px;
	overflow: auto;
}
.create_stockallocation_dialog .modal-body{
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
  			    <span>调出仓库: </span>
	  			<SELECT name="out_warehouse_id" value="" class="eagle-form-control" style="width:150px;margin:0px 10px">
	  				<OPTION value="" <?=(!isset($_GET['out_warehouse_id']) or !is_numeric($_GET['out_warehouse_id']) )?" selected ":'' ?>><?= TranslateHelper::t('所在仓库') ?></OPTION>
	  					<?php foreach($warehouse as $wh_id=>$wh_name){
							echo "<option value='".$wh_id."'";
							if(isset($_GET['out_warehouse_id']) && $_GET['out_warehouse_id']==$wh_id && is_numeric($_GET['out_warehouse_id'])) echo " selected ";
							echo ">".$wh_name."</option>";						
						} ?>
	  			</SELECT>
  			</div>
  		</div>
  		
  		<div class="div-input-group" title="<?= TranslateHelper::t('按仓库过滤显示结果') ?>">
  			<div style="float:left;" class="input-group">
  			    <span>调入仓库: </span>
	  			<SELECT name="in_warehouse_id" value="" class="eagle-form-control" style="width:150px;margin:0px 10px">
	  				<OPTION value="" <?=(!isset($_GET['in_warehouse_id']) or !is_numeric($_GET['in_warehouse_id']) )?" selected ":'' ?>><?= TranslateHelper::t('所在仓库') ?></OPTION>
	  					<?php foreach($warehouse as $wh_id=>$wh_name){
							echo "<option value='".$wh_id."'";
							if(isset($_GET['in_warehouse_id']) && $_GET['in_warehouse_id']==$wh_id && is_numeric($_GET['in_warehouse_id'])) echo " selected ";
							echo ">".$wh_name."</option>";						
						} ?>
	  			</SELECT>
  			</div>
  		</div>
  		
  		<div class="div-input-group" style="float: left;margin-left:5px;">
  			<div style="float:left;" class="">
				<input name="sdate" id="stockallocationlist_startdate" class="eagle-form-control" type="text" placeholder="<?= TranslateHelper::t('从 此日期后')?>" 
					value="<?= (empty($_GET['sdate'])?"":$_GET['sdate']);?>" style="width:150px;margin:0px;height:28px;float:left;" title="<?= TranslateHelper::t('只显示此日期后的采购单') ?>"/>
				<input name="edate" id="stockallocationlist_enddate" class="eagle-form-control" type="text" placeholder="<?= TranslateHelper::t('至 此日期前')?>" 
					value="<?= (empty($_GET['edate'])?"":$_GET['edate']);?>" style="width:150px;margin:0px;height:28px;float:left;" title="<?= TranslateHelper::t('只显示此日期前的采购单') ?>"/>
	  		</div>
	  	</div>
	
		<div class="div-input-group" style="float: left;margin-left:5px;">
  			<div style="float:left;">
				<input name="keyword" class="eagle-form-control" type="text" placeholder="<?= TranslateHelper::t('调拨单单号或备注所含关键字')?>" title="<?= TranslateHelper::t('根据输入的调拨单单号或备注字段模糊查询') ?>"  
					value="<?php if(!empty($_GET['keyword'])) echo $_GET['keyword'] ?>" style="width:180px;margin:0px;height:28px;float:left;"/>

				<button type="submit" class="btn btn-default" data-loading-text="<?= TranslateHelper::t('查询中...')?>" style="margin-left:5px;padding: 0px;height: 28px;width: 30px;border-radius: 0px;border: 1px solid #b9d6e8;" title="<?= TranslateHelper::t('查询') ?>">
					<span class="glyphicon glyphicon-search" aria-hidden="true"></span>
				</button>
				<button type="button" id="btn_clear_stockallocation" class="btn btn-default" style="margin-left:5px;padding: 0px;height: 28px;width: 30px;border-radius: 0px;border: 1px solid #b9d6e8;" title="<?= TranslateHelper::t('重置') ?>">
					<span class="glyphicon glyphicon-refresh" aria-hidden="true"></span>
				</button>
	  		</div>
	  	</div>
	</div>
</FORM>

<div style="width:100%;float:left;">
	<div style="float:left;">
		<button type="button" onclick="inventory.stockAllocation.openCreateForm()" class="btn-xs btn-transparent font-color-1" style="margin:0px 5px;">
			<span class="glyphicon glyphicon-plus"></span>
			<?= TranslateHelper::t('新建调拨单')?>
		</button>
	</div>
</div>
							
			
<!-- table -->
<div class="stockAllocationList" style="margin-top:5px;width:100%;float:left;">
    <table cellspacing="0" cellpadding="0" style="width:100%;fontsize:12px;float:left" class="table table-hover">
		<tr class="list-firstTr">
			<th width="150px"><?=$sort->link('create_time',['label'=>TranslateHelper::t('调拨日期')]) ?></th>
			<th width="100px"><?=$sort->link('stock_allocatione_id',['label'=>TranslateHelper::t('调拨单号')]) ?></th>
			<th width="100px"><?=$sort->link('out_warehouse_id',['label'=>TranslateHelper::t('调出仓库')]) ?></th>
			<th width="100px"><?=$sort->link('in_warehouse_id',['label'=>TranslateHelper::t('调入仓库')]) ?></th>
			<th width="100px"><?=$sort->link('number_of_sku',['label'=>TranslateHelper::t('产品种类数')]) ?></th>
			<th width="250px"><?=TranslateHelper::t('备注') ?></th>
			<th width="100px"><?=$sort->link('capture_user_id',['label'=>TranslateHelper::t('操作人')]) ?></th>
			<th width="100px"><?= TranslateHelper::t('操作')?></th>
		</tr>
        <?php if(!empty($data['datas'])){ ?>
        <?php foreach($data['datas'] as $index => $item){?>
            <tr <?=!is_int($index / 2)?"class='striped-row'":"" ?>>
                <td ><?=$item['create_time'] ?></td>
                <td ><?=$item['stock_allocatione_id'] ?></td>
                <td ><?= isset($warehouse[$item['out_warehouse_id']]) ? $warehouse[$item['out_warehouse_id']] : "仓库信息丢失(仓库id:".$item['out_warehouse_id'].")" ?></td>
                <td ><?= isset($warehouse[$item['in_warehouse_id']]) ? $warehouse[$item['in_warehouse_id']] : "仓库信息丢失(仓库id:".$item['in_warehouse_id'].")" ?></td>
                <td ><?=$item['number_of_sku'] ?></td>
	            <td ><?=$item['comment'] ?></td>
	            <td ><?=UserHelper::getFullNameByUid( $item['capture_user_id'] ) ?></td>
				<td>
					<button type="button" onclick="inventory.stockAllocation.showstockAllocationDetail('<?=$item['id'] ?>', '<?=$item['stock_allocatione_id'] ?>')" class="btn-xs btn-transparent font-color-1" title="<?= TranslateHelper::t('查看详情')?>">
	            		<span class="egicon-eye"></span>
	            	</button>
	            </td>
	        </tr>
         
        <?php }} else{?>
        	<tr><td colspan="7" style="text-align: center;"><?=TranslateHelper::t("无记录")?></td></tr>
        <?php } ?>
    </table>
</div>
			
<?php if($data['pagination']){?>
<div id="pager-group">
    <?= \eagle\widgets\SizePager::widget(['pagination'=>$data['pagination'] , 'pageSizeOptions'=>array( 5 , 20 , 50 , 100 , 200 ) , 'class'=>'btn-group dropup']);?>
    <div class="btn-group" style="width: 49.6%;text-align: right;">
    	<?=\yii\widgets\LinkPager::widget(['pagination' => $data['pagination'],'options'=>['class'=>'pagination']]);?>
	</div>
</div>
<?php }?>

<!-- show create_form dialog -->
<div class="create_stockallocation_dialog"></div>
<!-- /.show create_form dialog -->

<!-- show detail dialog -->
<div class="show_detail_dialog"></div>
<!-- /.show detail dialog -->

<!-- response Msg dialog -->
<div class="stockAllocation_save_result"></div>
<!-- /dialog -->