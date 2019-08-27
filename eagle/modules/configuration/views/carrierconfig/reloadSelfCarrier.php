<?php
use yii\helpers\Html;
use eagle\modules\util\helpers\TranslateHelper;
?>

<style>
	.account_table{
		line-height:21px;
		margin:0;
		font-size:12px;
		font-family: 'Applied Font Regular', 'Applied Font';
	}
	.account_table td input{
		width:220px;
		color:black;
	}
	.account_table td label{
		margin:0 0 5px 0;
		height:25px;
		line-height:25px;
		text-align:right;
		padding: 0px;
		font-weight: 400;
		color: rgb(51, 51, 51);
 		width:100px;
		white-space:nowrap;
		float:left;
	}
	.p3{
		font-size: 15px;
    	font-weight: bold;
	}
</style>

<?php
if($carrier_code == -1){
?>
<table class='account_table'>
<tr>
<td>
<label><b style="color: red;">*</b>物流商：</label>
<?= Html::input('text','carrier_name'.$carrier_type, '',['class'=>'modal_input required iv-input']) ?>
</td>
</tr>
<tr>
<td><?= Html::button('提交',['class'=>'iv-btn btn-search pull-right','style'=>'margin-left:8px;','onclick'=>'self_carrier_save('.$carrier_type.')'])?></td>
</tr>
</table>
<?php
}else{

$customCarrier = $relatedparams['customCarrier'];
$shippingNameIdMap = $relatedparams['shippingNameIdMap'];
$shippingMethodInfo = $relatedparams['shippingMethodInfo'];
$serviceUser = $relatedparams['serviceUser'];


if($customCarrier['is_used'] == 1){

?>
<!--  
<button class="iv-btn btn-danger" style="margin:8px 0;" onclick="openOrCloseCustomCarrier('<?= $carrier_code?>',0)">关闭物流</button>
-->
<?php if($carrier_type == 1) { ?>
<a class="iv-btn btn-search" style="margin:8px 8px;" onclick="<?= "$.openModal('/configuration/carrierconfig/open_excel_format',{carrier_code:'".$carrier_code."'},'Excel导出格式编辑','post')";?>">编辑Excel导出格式</a>
<?php } ?>

<p class="p3">运输服务</p>
<hr style="margin-top:12px;margin-bottom:12px;">

<div style='margin-top: 10px;'>
<a class="iv-btn btn-search title-button" onclick="<?= "$.modal({url:'/configuration/carrierconfig/shippingservice',method:'get',data:{type:'add',id:'0',code:'".$carrier_code."',key:'custom'}},'添加运输服务',{footer:false,inside:false}).done(function(\$modal){\$('.iv-btn.btn-default.btn-sm.modal-close').click(function(){\$modal.close();});});";?>">添加运输服务</a>
<?php  /*
<?= Html::dropDownList('ship_method_list_down_list','',
		['0'=>'输入运输服务名称快速找到要用的运输服务']+$shippingNameIdMap,
		['id'=>'ship_method_list_down_list'.$carrier_type,
		'style'=>'margin-left:10px;','onchange'=>"ship_method_list_down_listonchange(".$carrier_code.",".$carrier_type.")",'class'=>'input-sm'])?>
		
<?=Html::dropDownList('doshipping','',[''=>'批量修改','shipping_close'=>'关闭'],['onchange'=>"doshippingaction(this,$(this).val(),".$carrier_type.");",'class'=>'input-sm']);?> */?>
</div>

<div style='margin-top: 10px;'>
	<div class="like_table_div" style="padding-right:21.1px;">
		<table class="table text-center" style="table-layout:fixed;line-height:50px; margin:0;">
	    	<thead>
		    <tr>
	        	<th class="text-nowrap" style='width: 50px;text-align:center;'><?= Html::checkbox('check_all'.$carrier_type,false,['onclick'=>'customShippingCheck('.$carrier_type.')'])?></th>
	        	<th class="text-nowrap"><?=TranslateHelper::t('运输服务名（代码）')?></th>
		        <th class="text-nowrap"><?=TranslateHelper::t('开启状态') ?></th>
		        <th class="text-nowrap"><?=TranslateHelper::t('运输服务匹配规则') ?></th>
		        <th class="text-nowrap"><?=TranslateHelper::t('操作')?></th>
		    </tr>
     	</thead>
     	</table>
    </div>
    <!-- show_10_line_tbody -->
    <div class="like_table_div2">
		<form id="batchship">
			<table class="table text-center like_table" style="table-layout:fixed;line-height:50px; margin:0;">
		     	<tbody id="all_shipping_shows_tbody<?=$carrier_type ?>">
		     		<div>
		     			<?php
						foreach ($shippingMethodInfo as $k=>$ship){?>
				     	<tr data='<?= $ship['id']?>'>
				     		<td style='width: 50px;text-align:center;'><?= Html::checkbox('check_all'.$carrier_type.'[]',false,['class'=>'selectShip'.$carrier_type,'value'=>$ship['id']])?></td>
				     		<td><?= @$ship['service_name'].'('.@$ship['shipping_method_code'].')' ?></td>
				     		<td><?= $ship['is_used'] == 1 ? '开启' : '关闭' ?></td>
				     		<td><?= $ship['is_used'] == 1 ? Html::dropDownList('setrules','',['-1'=>'分配规则','0'=>'添加分配规则']
				     				+(empty($serviceUser[$k]['rule']) ? array() : $serviceUser[$k]['rule']),
									[
				     				'onchange'=>'selectRuless(this,'.$ship['id'].')',
				     				'class'=>'iv-input','style'=>'width:113px;']) : '' ?></td>
				     		<td>
				     		<?php
								if($ship['is_used'] == 1){
							?>
								<a class='iv-btn' onclick="openOrCloseShipping(this,'close','custom')" >关闭</a>
								
								<a class="iv-btn" onclick="<?= "$.modal({url:'/configuration/carrierconfig/shippingservice',method:'get',data:{type:'edit',id:'".$ship['id']."',code:'".$carrier_code."',key:'custom'}},'编辑',{footer:false,inside:false}).done(function(\$modal){\$('.iv-btn.btn-default.btn-sm.modal-close').click(function(){\$modal.close();});});";?>">编辑</a>
							<?php		
								}else{
							?>
								<a class="iv-btn" onclick="<?= "$.modal({url:'/configuration/carrierconfig/shippingservice',method:'get',data:{type:'open',id:'".$ship['id']."',code:'".$carrier_code."',key:'custom'}},'开启',{footer:false,inside:false}).done(function(\$modal){\$('.iv-btn.btn-default.btn-sm.modal-close').click(function(){\$modal.close();});});";?>">开启</a>
							<?php
								}
				     		?>
				     		</td>
				     	</tr>
				     	<?php }?>
		     		</div>
		      	</tbody>
		    </table>
		</form>
	</div>
</div>

<?php if($carrier_type == 0) {
	$trackingNoList = $relatedparams['trackingNoList'];
?>
<p class="p3">跟踪号库</p>
<hr style="margin-top:12px;margin-bottom:12px;">

<div>

<?php 
	/*$dotracknoarr=[''=>'批量操作','trackno_allocated'=>'标记已分配','trackno_del'=>'删除','trackno_remove'=>'清除已分配',];
<?=Html::dropDownList('dotrackno','',$dotracknoarr,['onchange'=>"dotracknoaction(this,$(this).val());",'class'=>'input-sm']);?>*/
?>


<a class="iv-btn btn-search" onclick="$.modal({url:'/configuration/carrierconfig/insert-track?style=1',method:'get',data:{}},'添加跟踪号',{footer:false,inside:false}).done(function($modal){$('.iv-btn.btn-default.btn-sm.modal-close').click(function(){$modal.close();});});">添加跟踪号</a>
</div>

<div style='margin-top: 10px;'>
	<div class="">
	<table id="trackingno-list-table" class="table text-center showtable" style="table-layout:fixed;line-height:50px; margin:0;">
		<thead>
			<tr>
				<th class="text-nowrap" style='width: 50px;text-align:center;'><?= Html::checkbox('check_tracking_all',false,['onclick'=>'customTrackingCheck()'])?></th>
				<th><?= TranslateHelper::t('跟踪号')?></th>
				<th><?= TranslateHelper::t('自定义运输服务')?></th>
				<th><?= TranslateHelper::t('是否分配')?></th>
				<th><?= TranslateHelper::t('小老板订单号')?></th>
				<th><?= TranslateHelper::t('分配时间')?></th>
				<th><?= TranslateHelper::t('录入时间')?></th>
				<th><?= TranslateHelper::t('录入人')?></th>
			</tr>
		</thead>
		<tbody>
			<?php 
				if(!empty($trackingNoList['data']))
				foreach ($trackingNoList['data'] as $row){
					$statu = ($row['is_used'])?'是':'否';
					$creT = empty($row['create_time'])?'':date ('Y-m-d H:i:s',@$row['create_time']);
					$useT = empty($row['use_time'])?'':date ('Y-m-d H:i:s',@$row['use_time']);
			?>
			<tr data="<?= $row['id']?>">
				<td style='width: 50px;text-align:center;'><?= Html::checkbox('check_tracking[]',false,['class'=>'selectTracking','value'=>$row['id']])?></td>
				<td><?= TranslateHelper::t(@$row['tracking_number'])?></td>
				<td><?= TranslateHelper::t(@$row['shipping_method_name'])?></td>
				<td><?= TranslateHelper::t($statu)?></td>
				<td><?= TranslateHelper::t(@$row['order_id'])?></td>
				<td><?= TranslateHelper::t($useT)?></td>
				<td><?= TranslateHelper::t($creT)?></td>
				<td><?= TranslateHelper::t(@$row['user_name'])?></td>
			</tr>
			<?php }?>
		</tbody>
	</table>
	</div>
	
	<?php if($trackingNoList['pagination']):?>
	<div>
		<div id="trackingno-list-pager" class="pager-group">
		    <?= \eagle\widgets\SizePager::widget(['isAjax'=>true , 'pagination'=>$trackingNoList['pagination'] , 'pageSizeOptions'=>array( 10 , 20 , 50 , 100 , 200 ) , 'class'=>'btn-group dropup']);?>
		    <div class="btn-group" style="width: 49.6%;text-align: right;">
		    	<?=\eagle\widgets\ELinkPager::widget(['isAjax'=>true , 'pagination' => $trackingNoList['pagination'],'options'=>['class'=>'pagination']]);?>
			</div>
		</div>
	</div>
	<?php endif;?>
	
	<?php 
	// 该例子支持通过 ajax 更新table 和 pagination页面，启动该功能需要下方代码初始化js配置，以及给下面两个widget配置"isAjax"=>true,
	$options = array();
	$options['pagerId'] = 'trackingno-list-pager';// 下方包裹 分页widget的id
	$options['action'] = \Yii::$app->request->getPathInfo().'?carrier_code='.$carrier_code; // ajax请求的 action
	$options['page'] = $trackingNoList['pagination']->getPage();// 当前页码
	$options['per-page'] = $trackingNoList['pagination']->getPageSize();// 当前page size
	$options['sort'] = isset($_REQUEST['sort'])?$_REQUEST['sort']:'';// 当前排序
	$this->registerJs('$("#trackingno-list-table").initGetPageEvent('.json_encode($options).')' , \Yii\web\View::POS_READY);
	?>
</div>


<?php } ?>

<?php
}else{
?>
<div>
<span style='font-size: 25px;'><?=$customCarrier['carrier_name'] ?>物流已关闭，是否重新
<button class="iv-btn btn-search title-button" style="margin: 10px;font-size: 20px;" onclick="openOrCloseCustomCarrier('<?=$carrier_code ?>',1)">开启</button>
此物流？</span>
</div>
<?php
}
}
?>