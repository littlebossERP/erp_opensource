<?php
use eagle\modules\util\helpers\TranslateHelper;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\jui\Dialog;
use yii\data\Sort;
use yii\jui\JuiAsset;

$baseUrl = \Yii::$app->urlManager->baseUrl;
$this->registerJsFile($baseUrl."/js/project/purchase/suggestion/list.js", ['depends' => ['yii\web\JqueryAsset']]);
$this->registerJsFile($baseUrl."/js/project/purchase/suggestion/printSug.js", ['depends' => ['yii\web\JqueryAsset']]);
$this->registerJsFile($baseUrl."/js/project/purchase/purchase_link_list.js", ['depends' => ['yii\web\JqueryAsset']]);

$this->registerJs("purchaseSug.list.init();" , \yii\web\View::POS_READY);
$this->registerJs("$.initQtip();" , \yii\web\View::POS_READY);
?>

<style>

</style>

<div style="width: 100%;float: left;font-size:12px;margin-bottom:10px;">
	<!-- 功能按钮  -->
	<div style="float: left;margin:0px 5px;" qtipkey="purchase_suggestion_create_purchase">
		<button type="button" class="btn-xs btn-primary" id="list_generate_purchase_order" disabled="disabled" style="border-style: none;"><?= TranslateHelper::t('生成采购单') ?></button>
	</div>
	<div style="float: left;margin:0px 5px;" qtipkey="purchase_suggestion_print_suggestion">
		<button type="button" class="btn-xs btn-primary" id="print_selected_suggestion" value="2" disabled="disabled" style="border-style: none;"><?= TranslateHelper::t('打印采购建议') ?></button>
	</div>
	
	<?php if(!empty($calculateDate)){?>
	<div style="float: left;margin-left: 20px;">
		<span class="alert-warning">上次生成采购建议时间：<?=$calculateDate ?> 该时间一小时后才能生成新的采购建议</span>
	</div>
	<?php } ?>
</div>

<!-- table -->
<div class="sugList" style="width:100%;float:left;">
	<table cellspacing="0" cellpadding="0" class="table table-hover" style="font-size:12px;width:100%;float:left;">
		<tr class="list-firstTr">
			<th width="20px" tag="ck" title="<?=TranslateHelper::t('全选') ?>"><input type="checkbox" id="select_all" ></th>
			<th width="50px" tag="img" title="<?=TranslateHelper::t('图片') ?>"><?= TranslateHelper::t('图片')?>
			</th>
			<th width="150px" tag="sku" title="sku"><?=$sort->link('sku',['label'=>'sku'])?>
			</th>
			<th width="250px" tag="name" title="<?=TranslateHelper::t('产品名称') ?>"><?=TranslateHelper::t('产品名称')?>
			</th>
			<th width="100px" tag="reason" title="<?=TranslateHelper::t('采购原因') ?>"><?=TranslateHelper::t('采购原因')?>
			</th>
			<th width="100px" tag="qty" title="<?=TranslateHelper::t('建议采购量') ?>"><?=$sort->link('purchaseSug',['label'=>TranslateHelper::t('建议采购量')])?>
			</th>
			<th width="100px" tag="supplier" title="<?=TranslateHelper::t('首选供应商') ?>"><?=TranslateHelper::t('首选供应商')?>
			</th>
			<th width="80px" tag="price" title="<?=TranslateHelper::t('上次报价') ?>"><?=TranslateHelper::t('上次报价')?>
			</th>
		</tr>
		<?php if(!empty($message)):?>
		<tr><td colspan="8" style="text-align: center;"><span  class="alert alert-warning" role="alert" style="margin: auto;display: inline-block;"><?=$message ?></span></td></tr>
		<?php endif;?>
        <?php if(isset($sugData['data']) && count($sugData['data'])>0): ?>
        <?php foreach($sugData['data'] as $index=>$sug):?>
        <tr <?=!is_int($index / 2)?"class='striped-row'":"" ?>>
            <td ><input type="checkbox" class="select_one" name="sugSelected" value="" ></td>
            <td name="photo_primary[]" value="<?=$sug['photo_primary'] ?>"><img src="<?=$sug['photo_primary'] ?>" style="width:50px ! important;height:50px ! important" /></td>
			<td name="sku[]" value="<?=$sug['sku'] ?>"><?= (empty($sug['purchase_link']) ? $sug['sku'] : '<a href="'.$sug['purchase_link'].'" target="_blank" class="purchase_link_list_show" purchase_link_json=\''.$sug['purchase_link_list'].'\'>'.$sug['sku'].'</a>') ?></td>
			<td name="name[]"><?=$sug['name'] ?></td>
			<td name="purchaseReasonStr[]" value="<?=$sug['purchaseReasonStr'] ?>"><?=$sug['purchaseReasonStr'] ?></td>
			<td name="purchaseSug[]" value="<?=$sug['purchaseSug'] ?>"><?=$sug['purchaseSug'] ?></td>
			<td name="primary_supplier_name[]" value="<?=$sug['primary_supplier_name'] ?>"><?=$sug['primary_supplier_name'] ?><?=empty($sug['purchase_link'])?'':'<br>'.$sug['purchase_link'] ?></td>
			<td name="purchase_price[]" value="<?=$sug['purchase_price'] ?>"><?=$sug['purchase_price'] ?></td>
			
		</tr>
         
        <?php endforeach;?>
        <?php endif; ?>
    </table>
</div>

<?php if(isset($sugData['pagination'])):?>
<div id="pager-group">
    <?= \eagle\widgets\SizePager::widget(['pagination'=>$sugData['pagination'] , 'pageSizeOptions'=>array(20 , 50 , 100 , 200 ) , 'class'=>'btn-group dropup']);?>
    <div class="btn-group" style="width: 49.6%; text-align: right;">
    	<?=\yii\widgets\LinkPager::widget(['pagination' => $sugData['pagination'],'options'=>['class'=>'pagination']]);?>
	</div>
</div>
<?php endif;?>
			


<!-- Modal -->
<div class="selected_suggestion_generate_purchase_order_win"></div>
<!-- /.modal-dialog -->
<!-- Modal -->
<div class="operation_result"></div>
<!-- /.modal-dialog -->

