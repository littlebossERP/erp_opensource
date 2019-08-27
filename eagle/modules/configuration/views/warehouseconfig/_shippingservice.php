<?php 
use yii\helpers\Html;
use yii\helpers\Url;
?>
<style>
	.impor_red{
		color:#ED5466 ;
		background:#F5F7F7;
		padding:0 3px;
		margin-right:3px;
	}

	.iv-modal .modal-content{
/* 		max-height: none; */
		
	}

	.myl{
		float:left;
		text-align:right;
		width:165px;
		padding-right:5px;
		min-height:1px;
		min-height:25px;
		line-height:25px;
	}
	
	.myr{
/* 		float:left; */
/* 		width:480px; */
		margin-left:130px;
		padding-left:5px;
		min-height:25px;
		line-height:25px;
	}
	
	.myLongText{
		width: 200px;
	}
	
	.myserviceInput{
 		width: 280px; 
	}
	
	.modal-footer{
		margin-top:10px;
		padding: 15px 0px 2px 0px;
	}
	
	.clear>div {
 		margin-bottom:3px;
	}
	
	.clear>div .ui-combobox-input{
		width: 253px;
/* 	    height: 21px; */
/* 	    line-height: 21px; */
		border: 1px solid #ABADB3;
		color: #333333;
		border: 1px solid #cdced0;
	    background-color: #FFFFFF;
	    padding: 3px 5px;
	    position: relative;
	    -moz-border-radius: 3px;
	    -webkit-border-radius: 3px;
	    border-radius: 3px;
	    display: inline-block;
	    vertical-align: middle;
	}
	
	.clear>div .ui-combobox-toggle{
/* 		height: 21px; */
		border: 1px solid #ABADB3;
		color: #333333;
		border: 1px solid #cdced0;
	    background-color: #FFFFFF;
	    padding: 3px 5px;
	    position: relative;
	    -moz-border-radius: 3px;
	    -webkit-border-radius: 3px;
	    border-radius: 3px;
	    display: inline-block;
	    vertical-align: middle;
	}
	
	.ui-autocomplete{
		z-index:9999;
		z-index: 2000 !important;
		overflow-y: scroll;
		max-height: 320px;
	}
	.myd{
		width:100%;
		text-align: left;
	}
	.p3{
		margin-left: 10px;
	}
	.mt10{
		margin-top:15px;
	}
	.gaoji{
		cursor: pointer;
	}
	.myr{
		margin-left: 83px;
	}
</style>
<div style='width:660px;'></div>
<form id="shippingserviceForm" style="width:600px;">
<?php 
// echo Html::hiddenInput('serviceID', $type == 'copy' ? '0' : @$serviceUserById['serviceID']);
echo Html::hiddenInput('serviceID',@$serviceUserById['serviceID']);
echo Html::hiddenInput('carrier_code',@$carrier_code);
echo Html::hiddenInput('type',@$type);
?>

<div class="myl myd"><p class="p3">基本信息:</p>
<div class="myl"><span class="impor_red">*</span>运输服务名</div>
<div class="myr"><?= Html::input('text','params[service_name]',@$serviceUserById['service_name'],['class'=>'myLongText req iv-input']) ?><span qtipkey="carrier_service_name_alias"></span></div>
<?php /*
<div class="myl">运输服务代码</div>
<div class="myr">
<?php echo $key == 'custom_oversea' ? Html::input('text','params[shipping_method_code]',@$serviceUserById['shipping_method_code'],['class'=>'myLongText req iv-input']) : Html::label(@$serviceUserById['shipping_method_code'])?>
</div> */?>

<?php if($key!="custom_oversea"){
			if($account>1){
?>
<div class="myl"><span class="impor_red">*</span>物流账号</div>
<div class="myr">
	<?php 
		if($type=="copy"){
			echo "<label>".@$serviceUserById['carrierAccountList'][$serviceUserById['accountID']]."</label>";
			echo Html::hiddenInput('params[accountID]',@$serviceUserById['accountID']);
		}
		else if($account>1){
			echo Html::dropDownList('params[accountID]',@$serviceUserById['accountID'],@$serviceUserById['carrierAccountList'],['class'=>'d_width_m']);
		}
// 		if($type!="copy"){ //print_r($serviceUserById);die;
// 			echo Html::dropDownList('params[accountID]',@$serviceUserById['accountID'],@$serviceUserById['carrierAccountList'],['class'=>'d_width_m']);
// // 			echo Html::textInput('accountIDs',@$serviceUserById['carrierAccountList'][$serviceUserById['accountID']],['class'=>'myLongText iv-input',(($type!="copy")?' disabled':'')=>'']);
// // 			echo Html::hiddenInput('params[accountID]',@$serviceUserById['carrierAccountList'][$serviceUserById['accountID']]);
// 		}
// 		else
// 			echo Html::dropDownList('params[accountID]',@$serviceUserById['accountID'],@$serviceUserById['carrierAccountList'],['class'=>'d_width_m',(($type!="copy")?' disabled':'')=>'']) 
	?>
</div>
<?php }else{
	echo Html::hiddenInput('params[accountID]',@$serviceUserById['accountID']);
}

}?>

<div class="myl">查询网址</div>
<div class="myr"><?= Html::input('text','params[web]',@$serviceUserById['web'],['class'=>'myLongText iv-input']) ?></div>
</div>

<div class="myl myd mt10"><p class="p3">通知平台发货承运商设置:</p>
<div class="myr">
	<?php
	if(!empty($serviceUserById['service_code']))
	foreach (@$serviceUserById['service_code'] as $k=>$service_code){?>
		<div class="service_code[<?= $k?>] clear">
			<div style="width:90px;text-align:right;float:left;"><?= $k?>：</div>
			<div style="margin-left:100px;">
				<?php switch ($service_code['display_type']){
					case 'dropdownlist':
						echo Html::dropDownList('params[service_code]['.$k.']',@$service_code['val'],@$service_code['optional_value'],['class'=>'','style'=>'width:280px;height:26px;']);
						break;
					case 'text':
						echo Html::textInput('params[service_code]['.$k.']',@$service_code['val'],['class'=>'myserviceInput iv-input']);
						break;
					}
					if($k == 'aliexpress'){
						echo "<a class='btn' style='padding:0;margin:0 0 0 5px;font-size:12px;' onclick='UpdatePlatform(".'"'.$k.'"'.",this)'>更新平台认可方式</a>";
					}
				?>
			</div>
		</div>
	<?php }?>
</div>
</div>

<?php if(!empty($serviceUserById['is_show_address'])){?>
<div  class="myl myd mt10"><p class="p3">地址信息:</p>
<div class="myl"><span class="impor_red">*</span>揽收/发货地址</div>
<div class="myr">
	<?php
		$adds = ['0'=>'默认揽收地址'];
		if(isset($serviceUserById['commonAddressArr']) && !empty($serviceUserById['commonAddressArr']))
			$adds = @$serviceUserById['commonAddressArr'];
		echo Html::dropDownList('params[common_address_id]',@$serviceUserById['common_address_id'],$adds,['class'=>'d_width_m']);
	?>
</div>
</div>
<?php }else{
	echo Html::hiddenInput('params[common_address_id]',@$serviceUserById['common_address_id']);
}?>

<div class="clear"></div>


<div class="myl myd mt10"><p class="p3">高级设置:<span id="caretDown" class="caretDown gaoji" style="font-size:15px;margin-left:15px;">+ 展开</span></p>
<div class="myl gaojiitem" style="display:none;">
<?php if($key!="custom_oversea"){
	if($param_set_count>0){
?>
<div class="myl">物流参数</div>
<div class="myr">
	<?php 
		if(!empty($serviceUserById['carrierParams']))
		foreach (@$serviceUserById['carrierParams'] as $carrierParams){
			if($carrierParams['ui_type']=='param_set' || empty($carrierParams['ui_type'])){
?>
		<div class="rightDIV" style="width: 660px;text-align:left;float:left;margin-left:26px;">
			<span>
			<?php if($carrierParams['carrier_is_required']) echo '<span class="impor_red">*</span>';?>
			<?= @$carrierParams['carrier_param_name']?></span>
			<?php switch ($carrierParams['display_type']){
				case 'dropdownlist':
					echo Html::dropDownList('params[carrierParams]['.$carrierParams['carrier_param_key'].']',$carrierParams['param_value'],$carrierParams['carrier_param_value'],['class'=>($carrierParams['carrier_is_required'])?'req':'']);
					break;
				case 'text':
					if($carrierParams['carrier_is_encrypt']){
						echo Html::passwordInput('params[carrierParams]['.$carrierParams['carrier_param_key'].']',$carrierParams['param_value'],$carrierParams['carrier_param_value'],['class'=>($carrierParams['carrier_is_required'])?'req iv-input myLongText':'iv-input']);
					}
					else{
						echo Html::textInput('params[carrierParams]['.$carrierParams['carrier_param_key'].']',$carrierParams['param_value'],$carrierParams['carrier_param_value'],['class'=>($carrierParams['carrier_is_required'])?'req iv-input myLongText':'iv-input']);
					}
					break;
			}?>
		</div>
	<?php }
	}?>
</div>
<?php }
}?>

<?php
if(!empty($serviceUserById['tracking_upload_config'])){
?>
<div class="clear"></div>
<div class="myl"><span class="impor_red">*</span>跟踪号通知平台方式</div>
<div class="myr" style='margin-top: 28px;'>
<?php
echo '<table style="width:435px;">';
foreach (@$serviceUserById['tracking_upload_config'] as $k=>$tracking_upload_config){?>
	<tr><td style='text-align: right;'><?= $k?>：</td><td><?= Html::radioList('params[tracking_upload_config]['.$k.']', $tracking_upload_config['val'], $tracking_upload_config['optional_value'], ['style'=>'height:26px;']); ?></td></tr>
<?php }
echo '</table>';
?>
</div>
<?php } ?>


<div class="myl"><span class="impor_red">*</span>客户参考号配置</div>
<div class="myr">
	<?php
	if(!empty($serviceUserById['customer_number_config']))
	foreach (@$serviceUserById['customer_number_config'] as $k=>$customer_number_config){?>
			<div class="customer_number_config <?= $k?> clear">
				<div style="width:90px;text-align:right;float:left;"><?= $k?>：</div>
				<div style="margin-left:100px;">
				<?php echo Html::dropDownList('params[customer_number_config]['.$k.']',@$customer_number_config['val'],@$customer_number_config['optional_value'],['class'=>'','style'=>'width:280px;height:26px;']);?>
				</div>
			</div>
	<?php }?>
</div>
</div>
</div>

<div class="clear"></div>
</form>

<div class="modal-footer">
	<button type="button" class="iv-btn btn-primary btn-sm" onclick="saveShipingServiceOver()">确定</button>
	<button class="iv-btn btn-default btn-sm modal-close">关 闭</button>
</div>

<script>
	$(function(){
		overseaWarehouseList.init2();
	});
	$.initQtip();
</script>