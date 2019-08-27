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
	
	.clear {
	    clear: both;
	    height: 0px;
	}
	
	.show_10_line_tbody{
		height:370px;
		overflow-y:auto;
	}
	
	.p3{
		font-size: 15px;
    	font-weight: bold;
	}
</style>
<?php 
if(($type == 'syscarrier') && ($warehouse_id == -1)){
?>
<form id='oversea_account_form'>
<?=Html::hiddenInput('hidwarehouse') ?>
<table class='account_table'>
<tr>
<td>
<label><b style="color: red;">*</b>可用仓库：</label>
<?=Html::dropDownList('notWarehouseDropDownid','',$relatedparams['notOverseaWarehouseArr'],['class'=>'iv-input','style'=>'width:220px;','prompt'=>'','onchange'=>'notWarehouseDropDownchange()'])?>
</td>
</tr>
<tr>
<td>
<label><b style="color: red;">*</b>账号别名：</label>
<?= Html::input('text','nickname', '',['class'=>'modal_input required iv-input']) ?>
</td>
</tr>
<tr>
<td>
<div id='add_account_div'></div>
</td>
</tr>
<tr>
<td><?= Html::button('提交',['class'=>'iv-btn btn-search pull-right','style'=>'margin-left:8px;','onclick'=>'oversea_account_save(0)'])?></td>
</tr>
</table>
</form>
<?php	
}else if((($type == 'syscarrier') || ($type == 'selfcarrier')) && ($warehouse_id != -1)){

$carrierAccountInfo = $relatedparams['carrierAccountInfo'];
$shippingNameIdMap = $relatedparams['shippingNameIdMap'];
$shippingMethodInfo = $relatedparams['shippingMethodInfo'];
$warehouseOneInfo = $relatedparams['warehouseOneInfo'];
$serviceUser = $relatedparams['serviceUser'];

if ($warehouseOneInfo['is_active'] == 'Y'){

?>

<div style='margin-bottom: 20px;'>
<!-- <button class="iv-btn btn-danger" style="margin:8px 0;" onclick="closeWarehouse('<?=$warehouse_id ?>','N')">关闭仓库</button>-->
<a class="iv-btn btn-search" style="margin:8px 0px;" onclick="<?= "$.openModal('/configuration/warehouseconfig/edit-warehouse-info',{warehouse_id:'".$warehouse_id."'},'编辑仓库','post')";?>">编辑仓库地址</a>
<span><?php echo $warehouseOneInfo['is_zero_inventory'] == 0?'目前不支持负库存发货':'目前支持负库存发货'; ?></span>

<?php }
if(($type == 'selfcarrier') && ($warehouseOneInfo['oversea_type'] == 1)){
 ?>
<a class="iv-btn btn-search" style="margin:8px 8px;" onclick="<?= "$.openModal('/configuration/carrierconfig/open_excel_format',{carrier_code:'".$carrierAccountInfo."'},'Excel导出格式编辑','post')";?>">编辑Excel导出格式</a>
<?php } ?>
</div>

<?php
	if($type == 'syscarrier'){
?>

<p class="p3">物流账号</p>
<hr style='margin-top:12px;margin-bottom:12px;'>
<?php /*
<div>
<?= Html::button('添加物流账号',['class'=>'iv-btn btn-search title-button',
				'onclick'=>"$.openModal('/configuration/warehouseconfig/add-or-edit-orversea-carrier-account',
				{type:'add',carrier_code:'".@$warehouseOneInfo['carrier_code']."',warehouse_id:'".@$warehouse_id."',third_party_code:'".@$warehouseOneInfo['third_party_code']."'},'添加物流账号','get')"])?>
</div>*/?>


<div style='margin-top: 10px;'>

<table class="table table-hover table-striped table-bordered" style="border-top:none">
<tr>
	<th class="text-nowrap"><?= TranslateHelper::t('账号简称')?></th>
	<th class="text-nowrap"><?= TranslateHelper::t('创建日期')?></th>
	<th class="text-nowrap"><?= TranslateHelper::t('操作')?></th>
</tr>
<?php
	if(count($carrierAccountInfo) > 0){
		foreach ($carrierAccountInfo as $carrierAccountVal){
?>
<tr>
	<td><?= $carrierAccountVal['carrier_name'].($carrierAccountVal['is_default'] == 1 ? '(默认账号)' : '') ?></td>
    <td><?= date("Y-m-d H:i:s", $carrierAccountVal['create_time'])?></td>
    <td>
    	<?php 
    		if($warehouseOneInfo['is_active']=='Y'){
				?>
		<?php 
				if($carrierAccountVal['is_used']==1){
					echo "<a class='btn btn-xs' onclick=openOrCloseAccountOver(".$carrierAccountVal['id'].",'0','".$warehouseOneInfo['carrier_code']."','".$warehouseOneInfo['warehouse_id']."','".count($carrierAccountInfo)."','".$warehouseOneInfo['third_party_code']."')>关闭</a>";
					?><a class="btn btn-xs" onclick="<?= "$.openModal('/configuration/warehouseconfig/add-or-edit-orversea-carrier-account',{type:'add',carrier_code:'".@$warehouseOneInfo['carrier_code']."',warehouse_id:'".@$warehouseOneInfo['warehouse_id']."',third_party_code:'".@$warehouseOneInfo['third_party_code']."',id:'".$carrierAccountVal['id']."',account:'".count($carrierAccountInfo)."'},'编辑物流账号','get')";?>">编辑</a><?php 
				}
				else
					echo "<a class='btn btn-xs' data='1' onclick=openOrCloseAccountOver(".$carrierAccountVal['id'].",'1','".$warehouseOneInfo['carrier_code']."','".$warehouseOneInfo['warehouse_id']."','".count($carrierAccountInfo)."','".$warehouseOneInfo['third_party_code']."')>开启</a>";
		?>
		<?php 
		/*
				if(count($carrierAccountInfo) > 1){
					if($carrierAccountVal['is_default'])
						echo '<a class="iv-btn def_account" data="'.@$carrierAccountVal['id'].'" style="color:#FF9900;">默认账号</a>';
					else
						echo '<a class="iv-btn" data="'.@$carrierAccountVal['id'].'" onclick="setDefaultAccount('.@$carrierAccountVal['id'].',this)">设为默认</a>';
				}*/
			}
			else{
				?>
		<?php 
					if($carrierAccountVal['is_used'] == 0)
						echo "<a class='btn btn-xs' onclick='carrierActiveFalse(1)'>开启</a>";
					else{
						echo "<a class='btn btn-xs' onclick='carrierActiveFalse(1)'>关闭</a>";
						?><a class="btn btn-xs" onclick="carrierActiveFalse(1)">编辑</a><?php 
					}
		?>
		<?php 
		/*
				if(count($carrierAccountInfo) > 1){
					if($carrierAccountVal['is_default'])
						echo '<a class="iv-btn def_account" data="'.@$carrierAccountVal['id'].'" style="color:#FF9900;">默认账号</a>';
					else
						echo '<a class="iv-btn" data="'.@$carrierAccountVal['id'].'" onclick="carrierActiveFalse(1)">设为默认</a>';
				}*/
			}
    	?>
   </td>
</tr>
<?php
		}
	}
?>
</table>
</div>

<?php //} ?>

<p class="p3">运输服务</p>

<div style='margin-top: 10px;'>
<?php
if ($type == 'syscarrier')
	if($warehouseOneInfo['is_active'] == 'Y')
		echo '<button type="button" class="iv-btn btn-search title-button" onclick="updateShippingOver(\'lb_4pxOversea\')">更新运输服务</button>';
	else
		echo '<button type="button" class="iv-btn btn-search title-button" onclick="carrierActiveFalse(1)">更新运输服务</button>';
else{
?>	
<a class="iv-btn btn-search title-button" onclick="<?= "$.openModal('/configuration/warehouseconfig/oversea-shippingservice',{type:'add',id:'0',code:'".$carrierAccountInfo."',key:'custom_oversea'},'添加运输服务','get')";?>">添加运输服务</a>
<?php
}/*
?>

<?= Html::dropDownList('ship_method_list_down_list','',
		['0'=>'输入运输服务名称快速找到要用的运输服务']+$shippingNameIdMap,
		['id'=>'ship_method_list_down_list'.$warehouseOneInfo['oversea_type'],
		'style'=>'margin-left:10px;','onchange'=>"ship_method_list_down_listonchange(".$warehouseOneInfo['warehouse_id'].",".$warehouseOneInfo['oversea_type'].")",'class'=>'input-sm'])?>
		
<?=Html::dropDownList('doshipping','',[''=>'批量修改','shipping_close'=>'关闭']+($type == 'syscarrier' ? ['shipping_account'=>'物流账号'] : array()),
		['onchange'=>"doshippingaction(this,$(this).val(),".$warehouseOneInfo['oversea_type'].",'".$warehouseOneInfo['carrier_code']."');",'class'=>'input-sm']);*/?> 		
</div>

<div style='margin-top: 10px;'>
	<div class="like_table_div" style="padding-right:21.1px;">
		<table class="table text-center" style="table-layout:fixed;line-height:50px; margin:0;">
	    	<thead>
		    <tr>
	        	<th class="text-nowrap" style='width: 50px;text-align:center;'><?= Html::checkbox('check_all'.$warehouseOneInfo['oversea_type'],false,['onclick'=>'warehouseShippingCheck('.$warehouseOneInfo['oversea_type'].')'])?></th>
	        	<?php 
	        		//if(count($carrierAccountInfo)>1){
						?><th class="text-nowrap"><?=TranslateHelper::t('运输服务名（代码）')?></th>
				<?php //}
	        	?>
	        	<th class="text-nowrap" <?=$type == 'syscarrier' ? '' : "style='display:none;'" ?>><?=TranslateHelper::t('物流商运输服务名')?></th>
		        <th class="text-nowrap"><?=TranslateHelper::t('开启状态') ?></th>
		       	<?php 
	        		if(count($carrierAccountInfo)>1){
		       		 ?><th class="text-nowrap" <?=$type == 'syscarrier' ? '' : "style='display:none;'" ?>><?=TranslateHelper::t('物流账号') ?></th>
		       	<?php }
	        	?>
		        <th class="text-nowrap"><?=TranslateHelper::t('运输服务匹配规则') ?></th>
		        <th class="text-nowrap"><?=TranslateHelper::t('操作')?></th>
		    </tr>
     	</thead>
     	</table>
    </div>
    
    <div class="like_table_div2">
		<form id="batchship">
			<table class="table text-center like_table" style="table-layout:fixed;line-height:50px; margin:0;">
		     	<tbody id="all_shipping_shows_tbody<?=$warehouseOneInfo['oversea_type'] ?>">
		     		<div>
		     			<?php 
						foreach ($shippingMethodInfo as $k=>$ship){  ?> 
				     	<tr>
				     		<td style='width: 50px;text-align:center;'><?= Html::checkbox('check_all'.$warehouseOneInfo['oversea_type'].'[]',false,['class'=>'selectShip'.$warehouseOneInfo['oversea_type'],'value'=>$ship['id']])?></td>
				     		<?php 
				     			//if(count($carrierAccountInfo)>1){
									?><td><?= @$ship['service_name'].'('.@$ship['shipping_method_code'].')' ?></td>
							<?php 
								//}
				     		?>
				     		<td <?=$type == 'syscarrier' ? '' : "style='display:none;'" ?>><?= @$ship['shipping_method_name'] ?></td>
				     		<td><?= $ship['is_used'] == 1 ? '开启' : '关闭' ?></td>
				     		<?php 
	        					if(count($carrierAccountInfo)>1){
				     					?><td <?=$type == 'syscarrier' ? '' : "style='display:none;'" ?>><?= @$ship['account_name'] ?></td>
				     		<?php } 
				     			if($warehouseOneInfo['is_active'] == 'Y' && count($relatedparams['carrierAccountInfo_isused'])>0){
				     		?>
				     				<td><?= $ship['is_used'] == 1 ? Html::dropDownList('setrules','',['-1'=>'分配规则','0'=>'添加分配规则']+(empty($serviceUser[$k]) ? array() : $serviceUser[$k]),['onchange'=>'selectRuless(this,'.$ship['id'].','.$warehouseOneInfo['warehouse_id'].')','class'=>'iv-input','style'=>'width:113px;']) : ''?></td>
				     		<?php }else{?>
				     				<td><?= $ship['is_used'] == 1 ? Html::dropDownList('setrules','',['-1'=>'分配规则','0'=>'添加分配规则']+(empty($serviceUser[$k]) ? array() : $serviceUser[$k]),['onchange'=>'carrierActiveFalse(1)','class'=>'iv-input','style'=>'width:113px;']) : '' ?></td>
				     		<?php }?>
				     		<td>
				     		<?php
								if($ship['is_used'] == 1){
										if($warehouseOneInfo['is_active'] == 'Y' && count($relatedparams['carrierAccountInfo_isused'])>0){
											?><a class='iv-btn' onclick="openOrCloseShippingOver(<?=$ship['id'] ?>,'<?=$warehouseOneInfo['carrier_code'] ?>','close')" >关闭</a>
											<?php  if($type == 'syscarrier'){ ?>
											<a class="iv-btn" onclick="<?= "$.modal({url:'/configuration/warehouseconfig/oversea-shippingservice',method:'get',data:{type:'edit',id:'".$ship['id']."',code:'".@$warehouseOneInfo['carrier_code']."',key:'',warehouseid:'".$warehouseOneInfo['warehouse_id']."',account:'".count($carrierAccountInfo)."'}},'编辑',{footer:false,inside:false}).done(function(\$modal){\$('.iv-btn').click(function(){\$modal.close();});});";?>">编辑</a>
											<a class="iv-btn" onclick="<?= "$.modal({url:'/configuration/warehouseconfig/oversea-shippingservice',method:'get',data:{type:'copy',id:'".$ship['id']."',code:'".@$warehouseOneInfo['carrier_code']."',key:''}},'复制',{footer:false,inside:false}).done(function(\$modal){\$('.iv-btn').click(function(){\$modal.close();});});";?>">复制</a>
											<?php }else{ ?>
											<a class="iv-btn" onclick="<?= "$.modal({url:'/configuration/warehouseconfig/oversea-shippingservice',method:'get',data:{type:'edit',id:'".$ship['id']."',code:'".@$carrierAccountInfo."',key:'custom_oversea'}},'编辑',{footer:false,inside:false}).done(function(\$modal){\$('.iv-btn').click(function(){\$modal.close();});});";?>">编辑</a>
											<?php }
										}
										else{
											?><a class='iv-btn' onclick="carrierActiveFalse(1)" >关闭</a>
											<?php  if($type == 'syscarrier'){ ?>
											<a class="iv-btn" onclick="carrierActiveFalse(1)">编辑</a>
											<a class="iv-btn" onclick="carrierActiveFalse(1)">复制</a>
											<?php }else{ ?>
											<a class="iv-btn" onclick="carrierActiveFalse(1)";?>">编辑</a>
											<?php }
										}
										?>
							<?php		
								}else{
									if($type == 'syscarrier'){ 
										if($warehouseOneInfo['is_active'] == 'Y' && count($relatedparams['carrierAccountInfo_isused'])>0){ 
							?>
											<a class="iv-btn" onclick="<?= "$.modal({url:'/configuration/warehouseconfig/oversea-shippingservice',method:'get',data:{type:'open',id:'".$ship['id']."',code:'".@$warehouseOneInfo['carrier_code']."',key:'oversea',shipcode:'".$ship['shipping_method_code']."',thirdcode:'".@$warehouseOneInfo['third_party_code']."',warehouseid:'".$warehouseOneInfo['warehouse_id']."',account:'".count($carrierAccountInfo)."'}},'开启',{footer:false,inside:false}).done(function(\$modal){\$('.iv-btn').click(function(){\$modal.close();});});";?>">开启</a>
							<?php
										}
										else{   ?>
											<a class="iv-btn" onclick="carrierActiveFalse(1)">开启</a>
							<?php 		}
									}	
									else{
							?>
								<a class="iv-btn" onclick="<?= "$.modal({url:'/configuration/warehouseconfig/oversea-shippingservice',method:'get',data:{type:'open',id:'".$ship['id']."',code:'".@$carrierAccountInfo."',key:'custom_oversea'}},'开启',{footer:false,inside:false}).done(function(\$modal){\$('.iv-btn').click(function(){\$modal.close();});});";?>">开启</a>
							<?php
									}
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

<?php
}else{
?>
	<div><span style='font-size: 25px;'><?=$warehouseOneInfo['name'] ?>已关闭，是否重新
	<button class="iv-btn btn-search title-button" style="margin: 10px;font-size: 20px;" onclick="closeWarehouse('<?=$warehouse_id ?>','Y')">开启</button>
	此仓库？</span></div>
<?php
}

}else if(($type == 'selfcarrier') && ($warehouse_id == -1)){
?>
<form id='oversea_self_account_form'>
<table class='account_table'>
<tr>
<td>
<label><b style="color: red;">*</b>仓库名：</label>
<?= Html::input('text','nicknameself', '',['class'=>'modal_input required iv-input']) ?>
</td>
</tr>
<tr>
<td><?= Html::button('提交',['class'=>'iv-btn btn-search pull-right','style'=>'margin-left:8px;','onclick'=>'oversea_account_save(1)'])?></td>
</tr>
</table>
</form>
<?php
}

?>

<div class="modal fade" id="myModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true"></div>

