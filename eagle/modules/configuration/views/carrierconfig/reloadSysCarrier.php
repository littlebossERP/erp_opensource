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
/*  		width:100px; */
		min-width:100px;
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
	
	.ui-autocomplete{
		z-index:9999;
		z-index: 2000 !important;
		overflow-y: scroll;
		max-height: 320px;
	}
	.p3{
		font-size: 15px;
    	font-weight: bold;
	}
</style>

<?php

if($carrier_code == -1){
?>
<form id='sys_account_form'>
<table class='account_table'>
<tr>
<td>
<label><b style="color: red;">*</b>物流商：</label>
<?=Html::dropDownList('notCarrierDropDownid','',$relatedparams['notOpenCarrier'],['class'=>'iv-input','style'=>'width:220px;','prompt'=>'','onchange'=>'notCarrierDropDownchange()'])?>
</td>
</tr>
<tr>
<td>
<label><b style="color: red;">*</b>账号别名：</label>
<?= Html::input('text','accountNickname', '',['class'=>'modal_input iv-input']) ?>
</td>
</tr>
<tr>
<td>
<div id='add_account_div'></div>
</td>
</tr>
<tr>
<td><?= Html::button('提交',['class'=>'iv-btn btn-search pull-right','onclick'=>'createAndOpenAccount()'])?></td>
</tr>
</table>
</form>
<?php
}else{
	$carrier_data = $relatedparams['carrier_data'];
	$carrierAccountInfo = $relatedparams['account'];
	$address = $relatedparams['address'];
	$ShippingMethodList = $relatedparams['ShippingMethodList'];
	$Shipping = $relatedparams['Shipping'];
	$serviceUser = $relatedparams['serviceUser'];

//if($carrier_data['is_active'] == 1){
?>

<!-- <button class="iv-btn btn-danger" style="margin:8px 0;" onclick="closeCarrier('<?= $carrier_data['carrier_code']?>')">关闭物流</button> 
<?= Html::submitButton('添加物流账号', ['class'=>'iv-btn btn-search title-button','onclick'=>"$.openModal('/configuration/carrierconfig/editaccount',{id:'',code:'".@$carrier_data['carrier_code']."'},'新建物流账号','post')"])?>
-->
<?php //if(count($carrierAccountInfo) > 1){ ?>
<p class="p3">物流账号</p>
<hr style='margin-top:12px;margin-bottom:12px;'>
<div style='margin-top: 10px;'>
<table class="table table-hover table-striped table-bordered" style="border-top:none">
<tr>
	<th class="text-nowrap"><?= TranslateHelper::t('账号简称')?></th>
	<th class="text-nowrap"><?= TranslateHelper::t('创建日期')?></th>
	<th class="text-nowrap"><?= TranslateHelper::t('操作')?></th>
</tr>
<?php
		foreach ($carrierAccountInfo as $carrierAccountVal){
?>
<tr>
	<td><?= $carrierAccountVal['carrier_name'].($carrierAccountVal['is_default'] == 1 ? '(默认账号)' : '') ?></td>
    <td><?= date("Y-m-d H:i:s", $carrierAccountVal['create_time'])?></td>
    <td>
    	 <?php 
    		if($carrier_data['is_user_active']==1){
					if($carrierAccountVal['is_used'] == 0)
						echo "<a class='btn btn-xs' onclick=openOrCloseAccount(".$carrierAccountVal['id'].",'1','".$carrier_data['carrier_code']."','".count($carrierAccountInfo)."')>开启</a>";
					else{
						echo "<a class='btn btn-xs' onclick=openOrCloseAccount(".$carrierAccountVal['id'].",'0','".$carrier_data['carrier_code']."','".count($carrierAccountInfo)."')>关闭</a>";
						?><a class="btn btn-xs" onclick="<?= "$.openModal('/configuration/carrierconfig/editaccount',{id:'".@$carrierAccountVal['id']."',code:'".@$carrier_data['carrier_code']."',account:'".count($carrierAccountInfo)."'},'编辑物流账号','post')";?>">编辑</a><?php 
					}	
			
    	?>			
    	<?php
//     				if(count($carrierAccountInfo) > 1){
// 						if($carrierAccountVal['is_default'])
// 							echo '<a class="iv-btn def_account" data="'.@$carrierAccountVal['id'].'" style="color:#FF9900;">默认账号</a>';
// 						else
// 							if($carrierAccountVal['is_used'] == 1)
// 								echo '<a class="iv-btn" data="'.@$carrierAccountVal['id'].'" onclick="setDefaultAccount('.@$carrierAccountVal['id'].',this)">设为默认</a>';
// 					}
			}
			else{
					if($carrierAccountVal['is_used'] == 0)
						echo "<a class='btn btn-xs' onclick='carrierActiveFalse(1)'>开启</a>";
					else{
						echo "<a class='btn btn-xs' onclick='carrierActiveFalse(1)'>关闭</a>";
						?><a class="btn btn-xs" onclick="carrierActiveFalse(1)">编辑</a>	<?php 
					}
    	?>			
    	<?php 
//     				if(count($carrierAccountInfo) > 1){
// 						if($carrierAccountVal['is_default'])
// 							echo '<a class="iv-btn def_account" data="'.@$carrierAccountVal['id'].'" style="color:#FF9900;">默认账号</a>';
// 						else
// 							if($carrierAccountVal['is_used'] == 1)
// 								echo '<a class="iv-btn" data="'.@$carrierAccountVal['id'].'" onclick="carrierActiveFalse(1)">设为默认</a>';
// 					}
			}
    	?>
   </td>
</tr>
<?php
		}
	//}
?>
</table>
</div>

<?php
	if($carrier_data['carrier_code'] == 'lb_alionlinedelivery'){
		echo "<p class='p3' style='margin-bottom:15px;'>寄件人地址信息：".
		"<font color='red'>可以在编辑运输服务的时候同步速卖通地址信息，当没有同步过的默认第一次提交物流时直接取速卖通后台的【地址管理】的地址信息，默认取【默认】的，没有默认的就取接口返回的第一个地址信息来提交物流。</font>".
		"<a target='_blank' href='http://bbs.seller.aliexpress.com/bbs/read.php?tid=513100&page=1&toread=1#tpc'>查看详情</a><br>".
		"<p class='p3' style='margin-left:120px;'><font color='red'>速卖通线上发货添加退件地址,选择退货速卖通会收取一定费用。可以小老板中编辑运输服务->高级设置->物流参数->是否退件 来设置。</font><a target='_blank' href='http://bbs.seller.aliexpress.com/bbs/read.php?tid=514111'>查看详情</a></p></p>";

	}
	else if($carrier_data['carrier_code'] == 'lb_edis'){
		echo "<p class='p3' style='margin-bottom:15px;'>寄件人地址信息：".
		"<font color='red'>可以在编辑运输服务的时候同步eDIS地址信息，当没有同步过的默认第一次提交物流时直接取eDIS后台的【地址管理】的第一个地址信息来提交物流。</font>".
		"<a target='_blank' href='https://www.edisebay.com/seller/help-center'>查看详情</a><br>";
	}

?>


<?php if($carrier_data['is_show_address']){

	if(count($address) == 0)
		echo '<input type="hidden" name="address_not_null_'.$carrier_data['carrier_code'].'" value="1">';
		//echo Html::hiddenInput('address_not_null','1');
	
?>
<p class="p3">寄件人地址信息</p>
<hr style='margin-top:12px;margin-bottom:12px;'>
<?php if($carrier_data['is_user_active']==1){ ?><button type="button" class="iv-btn btn-search title-button" onclick="$.modal({url:'/configuration/carrierconfig/address',method:'get',data:{id:0,codes:'<?=$carrier_data['carrier_code']?>',useractive:<?=$carrier_data['is_user_active']?>}},'<?=((count($address) == 0) ? '需要先添加地址信息才能开启运输服务' : '新建地址信息') ?>',{footer:false,inside:false}).done(function($modal){$('.iv-btn.btn-default.btn-sm.modal-close').click(function(){$modal.close();});});">添加寄件人地址信息</button><?php }else{
	echo '<button type="button" class="iv-btn btn-search title-button" onclick="carrierActiveFalse(1)">'.((count($address) == 0) ? '需要先添加地址信息才能开启运输服务' : '添加地址信息').'</button>';
}
?>


<div style='margin-top: 10px;'>
<table class="table table-hover table-striped table-bordered" style="border-top:none">
<tr>
	<th class="text-nowrap"><?= TranslateHelper::t('联系人')?></th>
	<th class="text-nowrap"><?= TranslateHelper::t('所在地区')?></th>
	<th class="text-nowrap"><?= TranslateHelper::t('详细地址')?></th>
	<th class="text-nowrap"><?= TranslateHelper::t('邮编')?></th>
	<th class="text-nowrap"><?= TranslateHelper::t('电话/手机')?></th>
	<th class="text-nowrap"><?= TranslateHelper::t('操作')?></th>
</tr>

<?php foreach ($address as $ad){
		$p = $ad['address_params']['shippingfrom'];
     	$country_zh = isset($country[$p['country']])?$country[$p['country']]:$p['country'];
     	$addressInfo = (empty($p['street']) ? '' : $p['street']);
?>
	<tr>
		<td><?= Html::label(@$p['contact'])?></td>
     	<td><?= Html::label(@$country_zh)?></td>
     	<td><?= Html::label(@$addressInfo)?></td>
     	<td><?= Html::label(@$p['postcode'])?></td>
     	<td><?= Html::label(@$p['phone'])?></td>
     	<td style="padding:8px 0;">
     		<?php 
     				if($carrier_data['is_user_active']==1){
					  ?><a class="btn btn-xs" onclick="<?php if($carrier_data['is_user_active']==1){ ?>$.modal({url:'/configuration/carrierconfig/address',method:'get',data:{id:<?= $ad['id']?>,codes:'<?= @$carrier_data['carrier_code']?>'}},'编辑地址信息',{footer:false,inside:false}).done(function($modal){$('.iv-btn.btn-default.btn-sm.modal-close').click(function(){$modal.close();});});<?php }else{?>carrierActiveFalse(1)<?php }?>">编辑</a>
						<a class="btn btn-xs" onclick="<?php if($carrier_data['is_user_active']==1){ ?>openDelAddressModal(<?= $ad['id']?>)<?php }else{?>carrierActiveFalse(1)<?php }?>">删除</a>
     		<?php 
     					if($ad['is_default'])
     						echo '<a class="btn btn-xs def_address" data="'.$ad['id'].'" style="color:#FF9900;">默认地址</a>';
     					else 
     						echo '<a class="btn btn-xs" data="'.$ad['id'].'" onclick="setDefaultAddress('.$ad['id'].',this)">设为默认</a>';
     				}
     				else{
					  ?><a class="btn btn-xs" onclick="carrierActiveFalse(1)">编辑</a>
     					<a class="btn btn-xs" onclick="carrierActiveFalse(1)">删除</a>
     		<?php 
     					if($ad['is_default'])
     						echo '<a class="btn btn-xs def_address" data="'.$ad['id'].'" style="color:#FF9900;">默认地址</a>';
     					else
     						echo '<a class="btn btn-xs" data="'.$ad['id'].'" onclick="carrierActiveFalse(1)">设为默认</a>';
					}
     		?>
	    </td>
	</tr>
<?php }?>
</table>
</div>
<?php } ?>

<p class="p3">运输服务</p>
<hr style='margin-top:12px;margin-bottom:12px;'>

<div style='margin-top: 10px;'>
<?php if($carrier_data['is_user_active']==1){ 
	echo '<button type="button" class="iv-btn btn-search" style="margin-left:8px;margin-right:8px;" onclick="UpdateShipping(\''.@$carrier_data['carrier_code'].'\')">更新运输服务</button>';
?>
<?php }else{
	echo '<button type="button" class="iv-btn btn-search" style="margin-left:8px;margin-right:8px;" onclick="carrierActiveFalse(1)">更新运输服务</button>';
?>
<?php }?>
</div>

<?php echo Html::hiddenInput('carrier_code',@$carrier_data['carrier_code'])?>
<div style='margin-top: 10px;'>
	<div class="like_table_div" style="padding-right:21.1px;">
		<table class="table text-center" style="table-layout:fixed;line-height:50px; margin:0;">
	    	<thead>
		    <tr>
	        	<th class="text-nowrap" style='width: 50px;text-align:center;'><?=Html::checkbox('check_all',false)?></th>
	        	<?php //if(count($carrierAccountInfo) > 1){ ?>
	        	<th class="text-nowrap"><?=TranslateHelper::t('运输服务名（代码）') ?></th>
	        	<?php //} ?>
	        	<th class="text-nowrap"><?=TranslateHelper::t('物流商运输服务名')?></th>
	        	<th class="text-nowrap"><?=TranslateHelper::t('运输服务是否已废弃')?></th>
		        <th class="text-nowrap"><?=TranslateHelper::t('开启状态') ?></th>
		        <?php if(count($carrierAccountInfo) > 1){ ?><th class="text-nowrap"><?=TranslateHelper::t('物流账号') ?></th><?php } ?>
		        <th class="text-nowrap"><?=TranslateHelper::t('运输服务匹配规则') ?></th>
		        <th class="text-nowrap"><?=TranslateHelper::t('操作')?></th>
		    </tr>
     	</thead>
     	</table>
    </div>

	<div class=" like_table_div2">    <!-- show_10_line_tbody -->
		<form id="batchship">
			<table id="table_<?= $carrier_data['carrier_code'] ?>" class="table text-center like_table" style="table-layout:fixed;line-height:50px; margin:0;word-break:break-all; word-wrap:break-all;">
		     	<tbody id="all_shipping_shows_DIV">
		     		<?php foreach ($Shipping as $k=>$ship){
		     		if($ship['is_close'] == 1){
						continue;
					}
		     			?>
		     		<tr data='<?= $ship['id']?>'>
		     			<td style='width: 50px;text-align:center;'><?= Html::checkbox('selectShip['.$ship['service_name'].']',false,['class'=>'selectShip','value'=>$ship['id']])?></td>
		     			<?php //if(count($carrierAccountInfo) > 1){ ?>
		     			<td><?= @$ship['service_name'].'('.@$ship['shipping_method_code'].')' ?></td>
		     			<?php //} ?>
		     			<td><?= @$ship['shipping_method_name']?></td>
		     			<td><?= @$ship['is_close'] == 1 ? "<font color='red'>是</font>" : '否' ?></td>
		     			<td><?= @$ship['is_used'] == 1 ? '开启' : '关闭' ?></td>
		     			<?php if(count($carrierAccountInfo) > 1){ ?><td><?php echo @$ship['accountNickname'];?></td><?php } ?>
		     			<td>
		     			<?php if($carrier_data['is_user_active']==1 && count($relatedparams['account_isused'])>0){?>
		     				<?= @$ship['is_used'] == 1 ? Html::dropDownList('setrules','',['-1'=>'分配规则','0'=>'添加分配规则']+$serviceUser[$k]['rule'],['onchange'=>'selectRuless(this,'.$ship['id'].')','class'=>'iv-input','style'=>'width:113px;']) : ''?></td>
		     			<?php }else{?>
		     				<?= @$ship['is_used'] == 1 ? Html::dropDownList('setrules','',['-1'=>'分配规则','0'=>'添加分配规则']+$serviceUser[$k]['rule'],['onchange'=>'carrierActiveFalse(1)','class'=>'iv-input','style'=>'width:113px;']) : ''?></td> 
		     			<?php }?>
		     			<td>
		     				<?php if($ship['is_copy']){?>
		     				<a class="btn btn-xs" onclick="delServiceNow(this)">删除</a>
		     				<?php }?>
		     				
		     				<?php 
		     					if($ship['is_used']){
									if($carrier_data['is_user_active']==1 && count($relatedparams['account_isused'])>0){
			     						echo "<a class='btn btn-xs' onclick=openEditServiceModelNew(this,'edit','".$carrier_data['carrier_code']."','".count($carrierAccountInfo)."')>编辑</a>";
			     						echo "<a class='btn btn-xs' onclick=openOrCloseShipping(this,'close','api','".$carrier_data['carrier_code']."')>关闭</a>";
			     						echo "<a class='btn btn-xs' onclick=openEditServiceModelNew(this,'copy','".$carrier_data['carrier_code']."')>复制</a>";
		     						}
		     						else{
										echo "<a class='btn btn-xs' onclick=carrierActiveFalse(1)>编辑</a>";
										echo "<a class='btn btn-xs' onclick=carrierActiveFalse(1)>关闭</a>";
										echo "<a class='btn btn-xs' onclick=carrierActiveFalse(1)>复制</a>";
									}
		     					}else{
									if($carrier_data['is_user_active']==1 && count($relatedparams['account_isused'])>0) {
								?>
										<a class="btn btn-xs"  onclick="openOrCloseShipping( this,'open','api','<?php echo $carrier_data['carrier_code']?>','<?php echo $ship['shipping_method_code']?>','<?php echo count($carrierAccountInfo)?>' )">开启</a>
							<?php
										//echo "<a class='btn btn-xs' onclick=openOrCloseShipping(this,'open','api','" . $carrier_data['carrier_code'] . "','" . $ship['shipping_method_code'] . "','" . count($carrierAccountInfo) . "')>开启</a>";
									}else {
										echo "<a class='btn btn-xs' onclick=carrierActiveFalse(1)>开启</a>";
									}
		     					}
		     				?>
		     			</td>
		     		</tr>
		     		<?php }?>
		      	</tbody>
		    </table>
		</form>
	</div>
</div>

<div class="modal fade" id="myModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
</div>

<script>
	ShippingJS.init();
</script>
<?php
//}
//else{
?>
<!--  
<div>
<span style='font-size: 25px;'><?=$carrier_data['carrier_name'] ?>物流已关闭，是否重新
<button class="iv-btn btn-search title-button" style="margin: 10px;font-size: 20px;" onclick="openCarrier('<?=$carrier_data['carrier_code'] ?>')">开启</button>
此物流？</span>
</div>
-->
<?php
//}

}
?>