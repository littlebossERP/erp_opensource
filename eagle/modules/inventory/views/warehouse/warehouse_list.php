<?php
use yii\helpers\Html;
use yii\grid\GridView;
use eagle\modules\util\helpers\TranslateHelper;
use yii\helpers\Url;
use yii\jui\Dialog;
use yii\data\Sort;

$baseUrl = \Yii::$app->urlManager->baseUrl . '/';
$this->registerJsFile($baseUrl."js/project/inventory/warehouse_list.js", ['depends' => ['yii\jui\JuiAsset','yii\bootstrap\BootstrapPluginAsset']]);
$this->registerJsFile($baseUrl."js/jquery.json-2.4.js", ['depends' => ['yii\jui\JuiAsset','yii\bootstrap\BootstrapPluginAsset']]);
$this->registerCssFile($baseUrl."css/inventory/inventory.css");
$this->registerJs("Warehouse.list.init();" , \yii\web\View::POS_READY);

//$this->title = TranslateHelper::t('仓储管理');
//$this->params['breadcrumbs'][] = $this->title;


?>
<style>
.warehouse_list th, .warehouse_list td{
	/*text-align:center;*/
	/*vertical-align: middle;*/
	padding: 4px;
	/*border: 1px solid rgb(202,202,202);*/
	word-break:break-word;
}
.list_options_tb td{
	text-align:center;
	vertical-align: middle;
	padding: 3px 1px;
	border: 0px;
	word-break:break-word;
}
</style>
<form action="<?= Url::to(['/inventory/warehouse/'.yii::$app->controller->action->id])?>" method="GET" id="warehouse_list_params_form" style="width:100%;float:left;">
	
	<?php 
		if(isset($_GET['is_active']) && !empty($_GET['is_active']))
			$is_active=$_GET['is_active'];
		else
			$is_active='All';
	?>
	<input type="hidden" name="is_active" value="">
	<table style="margin-bottom: 10px;">
		<tr>
			<td>
				<div style="float:left;font-size:12px;font-weight:500;padding:3px;"><?= TranslateHelper::t('启用状态筛选：') ?></div>
			</td>
			<td>
				<div style="float:left;">
					<button type="button" class="btn-xs <?php 
														if($is_active=='All') echo "btn-success";
														else echo "btn-default";
													?>" onclick="Warehouse.list.activeStatusFliter('All')" style="border-style:none;">
						<?= TranslateHelper::t('全部') ?>
					</button>
				</div>
			</td>
			<td>
				<div style="float:left;">
					<button type="button" class="btn-xs <?php 
														if($is_active=='Y') echo "btn-success";
														else echo "btn-default";
													?>" onclick="Warehouse.list.activeStatusFliter('Y')" style="border-style:none;">
						<?= TranslateHelper::t('启用') ?>
					</button>
				</div>
			</td>
			<td>
				<div style="float:left;">
					<button type="button" class="btn-xs <?php 
														if($is_active=='N') echo "btn-success";
														else echo "btn-default";
													?>" onclick="Warehouse.list.activeStatusFliter('N')" style="border-style:none;">
						<?= TranslateHelper::t('关闭/弃用') ?>
					</button>
				</div>
			</td>
			<td>
				<div class="div-input-group" style="float:left;margin-left:20px;">
		  			<div style="float:left;">
						<input name="keyword" class="eagle-form-control" type="text" placeholder="<?= TranslateHelper::t('输入仓库名称或备注')?>" title="<?= TranslateHelper::t('根据输入的字段模糊查询') ?>"  
							value="<?php if(!empty($_GET['keyword'])) echo $_GET['keyword'] ?>" style="width:150px;margin:0px;height:28px;float:left;"/>
		
						<button type="submit" class="btn btn-default" data-loading-text="<?= TranslateHelper::t('查询中...')?>" style="margin-left:5px;padding: 0px;height: 28px;width: 30px;border-radius: 0px;border: 1px solid #b9d6e8;" title="<?= TranslateHelper::t('查询') ?>">
							<span class="glyphicon glyphicon-search" aria-hidden="true"></span>
						</button>
						<button type="button" id="btn_clear" class="btn btn-default" style="margin-left:5px;padding: 0px;height: 28px;width: 30px;border-radius: 0px;border: 1px solid #b9d6e8;" title="<?= TranslateHelper::t('重置') ?>">
							<span class="glyphicon glyphicon-refresh" aria-hidden="true"></span>
						</button>
			  		</div>
			  	</div>
			  </td>
	  	</tr>
	</table>
</form>
<div style="float: left;margin-bottom:10px;width:100%;">
	<div style="float:left;">
		<button type="button" id="create_new_locally_warehouse" class="btn-xs btn-transparent font-color-1" style="">
			<span class="glyphicon glyphicon-plus"></span>
			<?= TranslateHelper::t('新建本地仓库')?>
		</button>
	</div>

</div>
<!-- table -->
<div class="warehouse_list" style="width:100%;float:left;">
	<table cellspacing="0" cellpadding="0" style="width:100%;font-size:12px;float:left;" class="table table-hover">
		<tr class="list-firstTr">
			<th width="0%" style="display: none"><?=TranslateHelper::t('仓库ID') ?></th>
			<th width="15%" title="<?=TranslateHelper::t('仓库名称') ?>"><?=$sort->link('name',['label'=>TranslateHelper::t('仓库名称')])?></th>
			<th width="10%" title="<?=TranslateHelper::t('是否启用') ?>"><?=$sort->link('is_active',['label'=>TranslateHelper::t('是否启用')])?></th>
			<th width="15%" title="<?=TranslateHelper::t('仓库所在地') ?>"><?=$sort->link('address_nation',['label'=>TranslateHelper::t('仓库所在地')])?></th>
			<th width="23%" title="<?=TranslateHelper::t('仓库备注') ?>"><?=TranslateHelper::t('仓库备注')?></th>
			<th width="12%" title="<?=TranslateHelper::t('创建时间') ?>"><?=$sort->link('create_time',['label'=>TranslateHelper::t('创建时间')])?></th>
			<th width="10%"><?=TranslateHelper::t('操作')?></th>
		</tr>
        <?php if(count($warehouseList)>0): ?>
        <?php foreach($warehouseList['data'] as $index=>$data):?>
        <tr <?=!is_int($index / 2)?"class='striped-row'":"" ?>>
            <td warehouse_id="<?php $data['warehouse_id']?>" width="0%" style="display: none"><?=$data['warehouse_id'] ?></td>
            <td><?=$data['name'] ?></td>
			<td><?=$active_status[$data['is_active']] ?></td>
			<?php
				if(empty($data['addi_info'])) $userinput_nation=$data['address_nation'];
				else{
					$add_info = json_decode($data['addi_info'],true);
					if(!empty($add_info['address_nation'])) $userinput_nation=$add_info['address_nation'];
					else $userinput_nation=$data['address_nation'];
				}
				?>
			<td title="<?=$userinput_nation ?> <?=$data['address_state'] ?> <?=$data['address_city'] ?> <?=$data['address_street']?>"><?=$data['address_nation'] ?></td>
			<td><?=$data['comment'] ?></td>
			<td><?=$data['create_time'] ?></td>
			<td>
				<button type="button" onclick="Warehouse.list.viewWarehouseDetail('<?=$data['warehouse_id'] ?>')" class="btn-xs btn-transparent font-color-1" title="<?= TranslateHelper::t('查看仓库的详细信息')?>">
            		<span class="egicon-eye"></span>
            	</button>
				<button type="button" onclick="Warehouse.list.editWarehouse('<?=$data['warehouse_id'] ?>')" class="btn-xs btn-transparent font-color-1" title="<?= TranslateHelper::t('修改仓库信息')?>">
            		<span class="glyphicon glyphicon-edit" aria-hidden="true"></span>
            	</button>
			<!--
			<?php if ($data['is_active']=='Y' ): ?>
			<?php if(intval($data['warehouse_id'])!==0) {?>
				<button type="button" onclick="Warehouse.list.changeActiveStatus('<?=$data['warehouse_id'] ?>','N')" class="btn-xs btn-transparent font-color-1" title="<?= TranslateHelper::t('将仓库状态设置为关闭停用')?>">
            		<span class="glyphicon glyphicon-remove-sign" aria-hidden="true"></span>
            	</button>
            <?php } ?>
			<?php else: ?>
				<button type="button" onclick="Warehouse.list.changeActiveStatus('<?=$data['warehouse_id'] ?>','Y')" class="btn-xs btn-transparent font-color-1" title="<?= TranslateHelper::t('将已关闭停用的仓库重新启用')?>">
            		<span class="glyphicon glyphicon-ok-sign" aria-hidden="true"></span>
            	</button>
			<?php endif; ?>
			-->

			</td>
		</tr>
        <?php endforeach;?>
        <?php endif; ?>
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

<!-- Modal -->
<div class="view_wh_detail_win"></div>
<div class="create_new_wh_win"></div>
<!-- /Model -->


