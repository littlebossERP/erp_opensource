<?php
use yii\helpers\Html;
use eagle\modules\util\helpers\TranslateHelper;
use yii\helpers\Url;
use eagle\helpers\UserHelper;

$this->registerJsFile(\Yii::getAlias('@web')."/js/project/inventory/inventory_list.js", ['depends' => ['yii\web\JqueryAsset']]);

$this->registerJs("inventory.list.init();" , \yii\web\View::POS_READY);

?>
<style>
.viewHistory .modal-dialog{
	width: 900px;
}
#historyList_table >tbody >tr >td{
	padding: 4px;
	border: 1px solid rgb(202,202,202);
}
.pageSize-dropdown-div.btn-group.dropup {
  width: 49%;
}
</style>
<FORM id="HistoryShowParmas" style="padding-bottom: 10px;">
	<table style="font-size:12px;">
		<tr>
			<td>
				<?= TranslateHelper::t('操作日期：从')?></td>
			<td>
				<input type="text" id="history_startdate" name="sdate" class="eagle-form-control" value="<?= (empty($_GET['sdate'])?"":$_GET['sdate']);?>" style="padding:0px;margin:0px;"></td> 
			<td>
				<?= TranslateHelper::t('到')?></td>
			<td>
				<input type="text" id="history_enddate" name="edate" class="eagle-form-control" value="<?= (empty($_GET['edate'])?"":$_GET['edate']);?>" style="padding:0px;margin:0px;"></td>
			<td>  	
				<button type="button" class="btn-xs btn-warning" id="btn_historyDetail_search" warehouse_id="<?=$warehouse_id ?>" style="margin-left: 10px;border-style:none;">
					<span class="glyphicon glyphicon-search" aria-hidden="true"></span>
					<?= TranslateHelper::t('搜索')?>
				</button>
				<textarea id="historyDetail_search_sku" class="hide" style="display:none;"><?=$sku ?></textarea>
			</td>
		</tr>
	</table>
</FORM>
<SCRIPT type="text/javascript">
	$(function(){
		$( "#history_startdate" ).datepicker({dateFormat:"yy-mm-dd"});
		$( "#history_enddate" ).datepicker({dateFormat:"yy-mm-dd"});
	});
</SCRIPT>

<!--listdata table -->
<div class="historylist">
	<table id= "historyList_table" cellspacing="0" cellpadding="0" width="900px" class="table table-hover" style="font-size:12px;">
		<tr class="list-firstTr">
			<td width="150px"><?=$sort->link('create_time',['label'=>TranslateHelper::t('出入库时间')]) ?></td>
			<td width="80px"><?=$sort->link('change_type',['label'=>TranslateHelper::t('操作类型')]) ?></td>
			<td width="20%"><?=$sort->link('stock_change_id',['label'=>TranslateHelper::t('出入库单号')]) ?></td>
			<td width="10%"><?=$sort->link('reason',['label'=>TranslateHelper::t('出入库原因')]) ?></td>
			<td width="80px"><?=TranslateHelper::t('入库数量') ?></td>
			<td width="80px"><?=TranslateHelper::t('出库数量') ?></td>
			<td width="80px"><?=TranslateHelper::t('操作后库存') ?></td>
			<td width="10%"><?=TranslateHelper::t('备注') ?></td>
			<td width="70px"><?=$sort->link('capture_user_id',['label'=>TranslateHelper::t('操作人')]) ?></td>
		</tr>
		<?php if(count($history['data'])>0): ?>
	    <?php foreach($history['data'] as $aData):?>
	    <tr>
	        <td ><?=$aData['create_time'] ?></td>
	        <td ><?php if(isset($stockChangeType[$aData['change_type']])) echo $stockChangeType[$aData['change_type']]; ?></td>
	        <td ><?=$aData['stock_change_id'] ?></td>
	        <td ><?php if(isset($stockChangeReason[$aData['reason']])) echo $stockChangeReason[$aData['reason']]; ?></td>
	        <td ><?php if($aData['change_type']==1 || (in_array($aData['change_type'], ['3', '4', '5']) && $aData['qty']>0)) echo $aData['qty'];
	  	     		?>
	        </td>
	        <td ><?php if($aData['change_type']==2 || (in_array($aData['change_type'], ['3', '4', '5']) && $aData['qty']<0)) echo $aData['qty'];
	             	?>
	        </td>
		    <td ><?php if(isset($aData['snapshot_qty'])) echo $aData['snapshot_qty'] ?></td>
	        <td ><?=$aData['comment'] ?></td>
	        <td ><?php $name = UserHelper::getFullNameByUid( $aData['capture_user_id']); echo empty($name) ? $aData['capture_user_id'] : $name ?></td>
		</tr>
	    <?php endforeach;?>
	    
	    <?php else:?>
	    	<tr><td colspan="9" style="text-align:center;"><?= TranslateHelper::t('无数据记录') ?></td></tr>
	    <?php endif;?>
	</table>
</div>
<!-- pagination -->
<?php if($history['pagination']):?>
<div>
	<div id="stockage_history_view_pager">
	    <?= \eagle\widgets\SizePager::widget(['isAjax'=>true ,'pagination'=>$history['pagination'] , 'pageSizeOptions'=>array( 5 , 20 , 50 , 100 , 200 ) , 'class'=>'btn-group dropup']);?>
	    <div class="btn-group" style="width: 49.6%;text-align: right;">
	    	<?=\eagle\widgets\ELinkPager::widget(['isAjax'=>true ,'pagination' => $history['pagination'],'options'=>['class'=>'pagination']]);?>
		</div>
	</div>
</div>
<?php endif;?>
<!-- /.pagination-->

<?php 
// 该例子支持通过 ajax 更新table 和 pagination页面，启动该功能需要下方代码初始化js配置，以及给下面两个widget配置"isAjax"=>true,
$options = array();
$options['pagerId'] = 'stockage_history_view_pager';// 下方包裹 分页widget的id
$options['action'] = \Yii::$app->request->getPathInfo()."?sku=$sku&warehouse_id=$warehouse_id"; // ajax请求的 action
$options['page'] = $history['pagination']->getPage();// 当前页码
$options['per-page'] = $history['pagination']->getPageSize();// 当前page size
$options['sort'] = isset($_REQUEST['sort'])?$_REQUEST['sort']:'';// 当前排序
$this->registerJs('$("#historyList_table").initGetPageEvent('.json_encode($options).')' , \Yii\web\View::POS_READY);

?>






