<?php

use yii\helpers\Html;
use yii\helpers\Url;

?>

<style>
.modal-body{
		width:850px;
		min-height:500px;
	}
	
.iv-modal .modal-content{
	max-height:800px;
}

</style>

<div class="col-xs-12">
<div>
	<lable>物流商：</lable><?=Html::dropDownList('warehouse_carrier_code','',$carrierIdNameMap,['class'=>'iv-input','style'=>'width:150px;','prompt'=>''])?>
	<label>运输服务名：</label><?=Html::textInput('warehouse_shipping_name','',['class'=>'iv-input','style'=>'width:150px;'])?>
	<?= Html::button('筛选',['class'=>'iv-btn btn-search','style'=>'margin-left:8px;','id'=>'btn_search','onclick'=>'btn_search_warehouse_shipping()'])?>
</div>
<div class="">
	<form id='warhouse_shipping_add_from'>
	<?=Html::hiddenInput('warehouse_id',$warehouse_id,['id'=>'warehouse_id']) ?>
	<table id="warehouse_shipping-list-table" class="table table-hover" style="table-layout:fixed;line-height:50px; margin:0;">
		<tr><th><?= Html::checkbox('check_all',false,['onclick'=>'warehouseShippingCheck()'])?>物流商（类型）</th><th>运输服务名</th><th>运输服务代码</th><th>物流账号</th></tr>
	<?php
		if(count($shippingMethodInfo['data']) > 0){
			foreach ($shippingMethodInfo['data'] as $k=>$ship){
	?>
		<tr>
     		<td><?= Html::checkbox('selectShip['.$ship['id'].']',false,['class'=>'selectShip','value'=>$ship['id']])?><?= @$ship['carrier_name'].'('.(($ship['is_custom'] == 1) ? '自定义' : 'API').')' ?></td>
     		<td><?= @$ship['service_name'].'('.(($ship['is_tracking_number'] == 1) ? '有' : '无').')' ?></td>
     		<td><?= @$ship['shipping_method_code'] ?></td>
     		<td><?= @$ship['account_name']?></td>
     	</tr>
	<?php
			}
		}
	?>
	</table>
	</form>
</div>

<?php if($shippingMethodInfo['pagination']):?>
<div>
	<div id="warehouse_shipping-list-pager" class="pager-group">
	    <?= \eagle\widgets\SizePager::widget(['isAjax'=>true , 'pagination'=>$shippingMethodInfo['pagination'] , 'pageSizeOptions'=>array( 10 , 20 , 50 , 100 , 200 ) , 'class'=>'btn-group dropup']);?>
	    <div class="btn-group" style="width: 49.6%;text-align: right;">
	    	<?=\eagle\widgets\ELinkPager::widget(['isAjax'=>true , 'pagination' => $shippingMethodInfo['pagination'],'options'=>['class'=>'pagination']]);?>
		</div>
	</div>
</div>
<?php endif;?>

<?php 
// 该例子支持通过 ajax 更新table 和 pagination页面，启动该功能需要下方代码初始化js配置，以及给下面两个widget配置"isAjax"=>true,
$options = array();
$options['pagerId'] = 'warehouse_shipping-list-pager';// 下方包裹 分页widget的id
$options['action'] = \Yii::$app->request->getPathInfo().'?warehouse_id='.$warehouse_id; // ajax请求的 action
$options['page'] = $shippingMethodInfo['pagination']->getPage();// 当前页码
$options['per-page'] = $shippingMethodInfo['pagination']->getPageSize();// 当前page size
$options['sort'] = isset($_REQUEST['sort'])?$_REQUEST['sort']:'';// 当前排序
$this->registerJs('$("#warehouse_shipping-list-table").initGetPageEvent('.json_encode($options).')' , \Yii\web\View::POS_READY);
?>
</div>

<div class="modal-footer col-xs-12">
	<button type="button" class="btn btn-primary" onclick="saveAddWarehouseShipping()">保存</button>
	<button class="iv-btn btn-default btn-sm modal-close">关 闭</button>
</div>



