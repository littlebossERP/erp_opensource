<?php
use yii\helpers\Html;
use eagle\modules\util\helpers\TranslateHelper;
use yii\helpers\Url;

$baseUrl = \Yii::$app->urlManager->baseUrl;
$this->registerJsFile($baseUrl."/js/project/purchase/supplier/supplier_list.js", ['depends' => ['yii\web\JqueryAsset']]);

$this->registerJs("supplier.list.innerInit();" , \yii\web\View::POS_READY);

?>
<style>
.supplier_prod_win .modal-body {
	max-height: 700px;
	overflow-y: auto;
}

.supplier_prod_win .modal-dialog {
	width: 900px;
}
#supplier_prod_list_table th,#supplier_prod_list_table td {
	padding:4px 0px;
	vertical-align: middle;
}
</style>
<div style="width: 100%;">
  	<FORM id="supplier_prod_filter" style="padding-bottom: 10px;width:100%">
  		<table style="width:100%;">
  			<tr style="width:100%;">
  				<td style="border:0px;width:50%;padding:0px">
  					<div class="div-input-group" style="width:250px">
  						<div style="" class="input-group" style="float:left;">
							<input name='keyword' type="text" class="form-control" style="height:28px;float:left;width:100%;" 
								placeholder="<?= TranslateHelper::t('输入产品sku或名称字段')?>"
								value="<?= (!isset($_GET['keyword'])?"":$_GET['keyword']);?>"/>
							<span class="input-group-btn" style="">
								<button type="button" class="btn btn-default" id="btn_supplier_prod_search" style="" onclick="supplier.list.supplierProductSearch()">
									<span class="glyphicon glyphicon-search" aria-hidden="true"></span>
							    </button>
						    </span>
						</div>
  					</div>
				</td>
				<td style="border:0px;width:50%;padding:0px">
				    <button type="button" class="btn-xs btn-transparent font-color-1" onclick="supplier.list.supplierBatchRemoveProds(<?=$supplier_id ?>)" style="float:right;padding-right: 20px;">
				   		<span class="egicon-trash" aria-hidden="true" style="height:16px;"></span>
				   		<?= TranslateHelper::t('批量移除供应') ?>
				   	</button>
				</td>
		    </tr>
	    </table>
  	</FORM>
						
	<table id="supplier_prod_list_table" cellspacing="0" cellpadding="0" class="table table-hover" style="font-size:12px;width:100%">
		<tr class="list-firstTr">
			<th width="30px">
				<input type="checkbox" name="chk_ps_all">
			</th>
			<th style="display:none;width:0px;">product_id</th>
			<th width="60px"><?=TranslateHelper::t('图片') ?></th>
			<th width="150px"><?=TranslateHelper::t('sku') ?></th>
			<th width="250px"><?=TranslateHelper::t('名称') ?></th>
			<th width="60px"><?=TranslateHelper::t('采购价') ?></th>
			<th width="100px"><?=TranslateHelper::t('类型') ?></th>
			<!--
			<th width="80px"><?=TranslateHelper::t('状态') ?></th>  -->
			<th width="100px"><?=TranslateHelper::t('优先次序') ?></th>
			<th width="60px"><?=TranslateHelper::t('操作') ?></th>
		</tr>
		<?php if(count($prods['data'])>0): ?>
	    <?php foreach($prods['data'] as $aData):?>
	    <tr>
	    	<td>
	    		<input type="checkbox" name="chk_ps_info" value="<?=base64_encode($aData['sku'])?>">
	    	</td>
	    	<td style="display:none;width:0px;padding:0px;"><?=$aData['product_id'] ?></td>
	        <td><div style="height: 50px;">
					<img style="max-height: 50px; max-width: 80px;" src="<?=empty($aData['photo_primary'])?'':$aData['photo_primary'] ?>" /></div>
			</td>
	        <td><?=htmlentities($aData['sku']) ?></td>
	        <td><?=$aData['name'] ?></td>
	        <td><?= empty($aData['purchase_price']) ? 0 : (float)$aData['purchase_price']?></td>
	        <td><?=isset($prodType[$aData['type']])?$prodType[$aData['type']]:'--' ?></td>
	        <!--
	        <td style="padding:0px;"><?=isset($prodStatus[$aData['status']])?$prodStatus[$aData['status']]:'--' ?></td>  -->
	        <td>
	        <?php
	        if(isset($aData['priority'])){
	        	if($aData['priority']==0){
					echo TranslateHelper::t('首选');
				}else{
					echo TranslateHelper::t('次要供应商').$aData['priority'];
	       		}
	        } ?>
	        </td>
	        <td>
	        	<button type="button" class="btn-xs btn-transparent font-color-1" onclick="supplier.list.supplierRemoveProd('<?=base64_encode($aData['sku']) ?>',<?=$supplier_id ?>,this,'one')" title="<?=TranslateHelper::t('删除供应关系') ?>">
	        		<span class="egicon-trash" aria-hidden="true"></span>
	        	</button>
	        </td>
		</tr>
	    <?php endforeach;?>
	    <?php else:?>
	    	<tr><td colspan="9" style="text-align:center;">
	    			<?= TranslateHelper::t('该供应商无产品供应') ?>
	    		</td>
	    	</tr>
	    <?php endif;?>
	</table>
		 
<!-- pagination -->
	<?php if($prods['pagination']):?>
	<div id="supplier_prod_view_pager">
	    <?= \eagle\widgets\SizePager::widget(['isAjax'=>true ,'pagination'=>$prods['pagination'] , 'pageSizeOptions'=>array( 5 , 20 , 50 , 100 , 200 ) , 'class'=>'btn-group dropup']);?>
	    <div class="btn-group" style="width: 49.6%;text-align: right;">
	    	<?=\eagle\widgets\ELinkPager::widget(['isAjax'=>true ,'pagination' => $prods['pagination'],'options'=>['class'=>'pagination']]);?>
		</div>
	</div>
	<?php endif;?>
<!-- /.pagination-->

</div>

<?php 
// 该例子支持通过 ajax 更新table 和 pagination页面，启动该功能需要下方代码初始化js配置，以及给下面两个widget配置"isAjax"=>true,
$options = array();
$options['pagerId'] = 'supplier_prod_view_pager';// 下方包裹 分页widget的id
$options['action'] = \Yii::$app->request->getPathInfo()."?supplier_id=$supplier_id"; // ajax请求的 action
$options['page'] = $prods['pagination']->getPage();// 当前页码
$options['per-page'] = $prods['pagination']->getPageSize();// 当前page size
$options['sort'] = isset($_REQUEST['sort'])?$_REQUEST['sort']:'';// 当前排序

$this->registerJs('$("#supplier_prod_list_table").initGetPageEvent('.json_encode($options).')' , \Yii\web\View::POS_READY);

?>






