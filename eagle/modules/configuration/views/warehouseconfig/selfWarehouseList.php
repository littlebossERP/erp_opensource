<?php
use yii\helpers\Html;
use eagle\modules\util\helpers\TranslateHelper;

$this->registerJsFile(\Yii::getAlias('@web')."/js/project/configuration/warehouseconfig/selfwarehouselist.js", ['depends' => ['yii\jui\JuiAsset','yii\bootstrap\BootstrapPluginAsset']]);
$this->registerJs("selfwarehouselist.init();" , \yii\web\View::POS_READY);

if(isset($warehouseOneInfo['addi_info']['address_nation'])){
	$userInput_nation = $warehouseOneInfo['addi_info']['address_nation'];
}
?>
<style>
.div_head_info{
	float:right;
	margin:5px;
}

.div_head_info label{
	font-weight:bold;
}
.impor_red{
	color:#ED5466 ;
	background:#F5F7F7;
	padding:0 3px;
	margin-right:3px;
}
.clear {
    clear: both;
    height: 0px;
}
.table > tbody + tbody, .table > thead > tr > th, .table > tbody > tr > th, .table > tfoot > tr > th, .table > thead > tr > td, .table > tbody > tr > td, .table > tfoot > tr > td{
	text-align:center;
	border:1px solid #CBCBCB
}
.alert{
	margin-bottom: 10px;
}
</style>

<?=Html::hiddenInput('warehouse_id',@$warehouseOneInfo['warehouse_id'],['id'=>'warehouse_id']) ?>

<div class="alert alert-warning" role="alert">
	<span style='color: red;font-weight: bold;'>提示：如果您使用的仓库是自己运营，并且仓库的工作人员是直接登录小老板系统进行拣货、打单配货，此类仓库为“自营仓库”！</span>
	<span>
		<?= Html::button('添加仓库',['class'=>'iv-btn btn-search','style'=>'margin-left:8px;','onclick'=>"$.openModal('/configuration/warehouseconfig/warehouse-enable-or-create',{type:'create'},'添加仓库','get')"])?>
	</span>
</div>

<div class="panel panel-default">
	<table class="table text-center like_table" style="table-layout:fixed;line-height:50px; margin:0;">
		<tr>
			<th><?= TranslateHelper::t('仓库名')?></th>
			<th><?= TranslateHelper::t('仓库地址')?></th>
			<th><?= TranslateHelper::t('开启')?></th>
			<th><?= TranslateHelper::t('备注')?></th>
			<th><?= TranslateHelper::t('创建时间')?></th>
			<th><?= TranslateHelper::t('操作')?></th>
		</tr>
		<?php
			if(count($warehouseList) > 0){
				foreach ($warehouseList['data'] as $warehouseListOne){
		?>
		<tr <?=$warehouseListOne['is_active'] == 'N' ? "style=background-color:#ADADAD;" : '' ?>>
			<td><?=$warehouseListOne['name'] ?></td>
			<td><?=$warehouseListOne['address_street'] ?></td>
			<td><?=$warehouseListOne['is_active'] == 'Y' ? '开启' : '关闭' ?></td>
			<td><?=$warehouseListOne['comment'] ?></td>
			<td><?=$warehouseListOne['create_time'] ?></td>
			<td>
				<a class="iv-btn" onclick="<?= "$.openModal('/configuration/warehouseconfig/edit-warehouse-info',{warehouse_id:'".$warehouseListOne['warehouse_id']."'},'编辑','post')";?>" >编辑</a>
				<?php if($warehouseListOne['is_active'] == 'Y' && !empty($warehouseListOne['warehouse_id'])){?>
					<a class="iv-btn" onclick="deleteSelfWarehouse('<?= $warehouseListOne['warehouse_id']?>')" >删除</a>
				<?php }?>
			</td>
		</tr>
		<?php
				}
			}
		?>
    </table>
</div>

<?php if($warehouseList['pagination']):?>
<div id="pager-group">
    <?= \eagle\widgets\SizePager::widget(['pagination'=>$warehouseList['pagination'] , 'pageSizeOptions'=>array( 5 , 20 , 50 , 100 , 200 ) , 'class'=>'btn-group dropup']);?>
    <div class="btn-group" style="width: 49.6%; text-align: right;">
    	<?=\yii\widgets\LinkPager::widget(['pagination' => $warehouseList['pagination'],'options'=>['class'=>'pagination']]);?>
	</div>
</div>
<?php endif;?>

<!-- modal -->
<div class="modal fade" id="myModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true"></div>
