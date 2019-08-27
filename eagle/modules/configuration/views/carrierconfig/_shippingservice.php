<?php 

use yii\helpers\Html;
use yii\helpers\Url;
use eagle\modules\carrier\helpers\CarrierOpenHelper;
?>
<style>
	.impor_red{
		color:#ED5466 ;
		background:#F5F7F7;
		padding:0 3px;
		margin-right:3px;
	}

	.iv-modal .modal-content{
/*   		max-height: none; */
/* 		max-height: 850px; */
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
.mBottom10 {
    margin-bottom: 10px;
}
.pLeft10 {
    padding-left: 10px;
}
.p0 {
    padding: 0;
}
.border3 {
    border: 1px solid #ccc;
}
.bgColor8 {
    background-color: #ccc;
}
.col-xs-12 {
    width: 100%;
}
.h30 {
    height: 30px;
}
#model_md, #model_bg, #model_jh {
    height: 198px;
    overflow: hidden;
}
.pRight10 {
    padding-right: 10px;
}
.m0 {
    margin: 0;
}

.checkbox, .radio {
    position: relative;
    display: block;
}
a, a:hover, a:focus {
    text-decoration: none;
}
.edis td{
	border: 1px solid #ddd !important;
}
.edis .edis_select{
	border: 1px solid #cdced0;
    background-color: #FFFFFF;
    padding: 3px 5px;
    height: 26px;
    line-height: 20px;
    position: relative;
    -moz-border-radius: 3px;
    -webkit-border-radius: 3px;
    border-radius: 3px;
    display: inline-block;
    vertical-align: middle;
}

</style>
	<div style='width:610px;'></div>
	<form id="shippingserviceForm" style='width:600px;'>
		<?php 
			echo Html::hiddenInput('serviceID',@$serviceUserById['serviceID']);
		?>
		<?= Html::hiddenInput('carrier_code',@$carrier_code)?>
		<?= Html::hiddenInput('type',@$type)?>
		<?php //print_r($serviceUserById)?>
		
		<div class="myl myd"><p class="p3">基本信息:</p>
		<div class="myl">物流商</div>
		<div class="myr"><?= @$serviceUserById['carrier_name'] ?></div>
		<div class="myl"><span class="impor_red">*</span>运输服务名</div>
		<div class="myr"><?= Html::input('text','params[service_name]',@$serviceUserById['service_name'],['class'=>'myLongText req iv-input']) ?><span qtipkey="carrier_service_name_alias"></span></div>
		<?= Html::hiddenInput('params[shipping_method_code]',@$serviceUserById['shipping_method_code'])?>
		<?php /*
		<div class="myl">运输服务代码</div>
		<div class="myr">
		<?php echo $key == 'custom' ? Html::input('text','params[shipping_method_code]',@$serviceUserById['shipping_method_code'],['class'=>'myLongText req iv-input']) : Html::label(@$serviceUserById['shipping_method_code'])?>
		</div> */ ?>
		<?php 
		if($key!="custom"){
// 			if($account>1){
		?>
		<div class="myl"><span class="impor_red"><span class="impor_red">*</span></span>物流账号</div>
		<div class="myr">
		<?php
			if($type=="copy"){
				echo "<label>".@$serviceUserById['carrierAccountList'][$serviceUserById['accountID']]."</label>";
				echo Html::hiddenInput('params[accountID]',@$serviceUserById['accountID']);
			}else{		//if($account>1)
				echo Html::dropDownList('params[accountID]',@$serviceUserById['accountID'],@$serviceUserById['carrierAccountList'],['class'=>'d_width_m']);
			}
		?>
		</div>
		<?php
// 			}else{
// 				echo Html::hiddenInput('params[accountID]',@$serviceUserById['accountID']);
// 			}
		}
		?>
		<div class="myl">查询网址</div>
		<div class="myr"><?= Html::input('text','params[web]',@$serviceUserById['web'],['class'=>'myLongText iv-input']) ?></div>
		</div>
		<!-- 这里暂时隐藏，后续功能还没有跟上 -->
		<div style='display: none;'>
			<div class="myl"><span class="impor_red">*</span>运输服务类型</div>
			<div class="myr">
				<?= Html::radioList('params[transport_service_type]',@$serviceUserById['transport_service_type'],['0'=>'经济','1'=>'标准','2'=>'特快'],['class'=>'myradiolist']) ?>
				<div class=""><label style="margin:0 0 0 12px;">时效</label><?= Html::input('text','params[aging]',@$serviceUserById['aging'],['class'=>'myText iv-input'])?></div>
			</div>
			<div class="clear"></div>
		</div>
		<div style='<?php if($key != "custom") echo "display:none"?>'>
			<div class="myl"><span class="impor_red">*</span>有无跟踪号</div>
			<div class="myr"><?= Html::radioList('params[is_tracking_number]',@$serviceUserById['is_tracking_number'],['1'=>'有','0'=>'无']) ?></div>
		</div>
		<div class="myl" style='display: none;'><span class="impor_red">*</span>自营仓库设置</div>
			<div class="myr" style='display: none;'>
			<?= Html::checkboxList('params[proprietary_warehouse]',@$serviceUserById['proprietary_warehouse'],@$serviceUserById['self_warehouse']) ?>
		</div>
		
		<div class="myl myd mt10"><p class="p3">通知平台发货承运商设置:</p>
		<div class="myr" style='margin-top: 15px;margin-left: 63px;'>
			<?php
			if(@$carrier_code == 'lb_LGS'){
				echo "<span style='margin-left:25px;'><font color='red'>LGS运输方式不能手动选择通知平台发货承运商，因为运输方式已经<br>确定使用哪种方式通知平台发货。</font></span>";
			}else if(@$carrier_code == 'lb_rumallSFguoji'){
			    echo "<span style='margin-left:25px;'><font color='red'>Rumall运输方式不能手动选择通知平台发货承运商，因为运输方式已经<br>确定使用哪种方式通知平台发货。</font></span>";
			}else if(@$carrier_code == 'lb_alionlinedelivery'){
				echo "<span style='margin-left:25px;'><font color='red'>速卖通线上发货不需要再手动选择通知平台发货承运商，小老板ERP通过接口<br>确定使用哪种方式通知平台发货。</font></span>";
			}else if(@$carrier_code == 'lb_seko'){
				echo "<span style='margin-left:25px;'><font color='red'>Seko不需要再手动选择通知平台发货承运商，小老板ERP通过接口<br>确定使用哪种方式通知平台发货。</font></span>";
			}
			
// 			print_r($serviceUserById['shipping_method_code']);
			
			if(!empty($serviceUserById['service_code']))
			foreach (@$serviceUserById['service_code'] as $k=>$service_code){
?>
				<div class="service_code[<?= $k?>] clear" style='<?=(in_array(@$carrier_code, array('lb_LGS','lb_rumallSFguoji','lb_alionlinedelivery','lb_seko')) ? 'display:none;' : '') ?>'>
					<div style="width:90px;text-align:right;float:left;"><?= $k?>：</div>
					<div style="margin-left:100px;">
						<?php switch ($service_code['display_type']){
							case 'dropdownlist':
								if(($carrier_code == 'lb_alionlinedelivery') && ($k == 'aliexpress')){
									$tmpservice_code = $serviceUserById['platform_service_code'];
									
									echo Html::textInput('params[service_code]['.$k.']',$tmpservice_code,['class'=>'myserviceInput iv-input']);
// 									echo Html::dropDownList('params[service_code]['.$k.']',$tmpservice_code,@$service_code['optional_value'],['class'=>'','style'=>'width:280px;height:26px;']);
								}else{
									echo Html::dropDownList('params[service_code]['.$k.']',@$service_code['val'],@$service_code['optional_value'],['class'=>'','style'=>'width:280px;height:26px;']);
								}
								break;
							case 'text':
								echo Html::textInput('params[service_code]['.$k.']',@$service_code['val'],['class'=>'myserviceInput iv-input']);
								break;
							}
							if($k == 'aliexpress' || $k == 'lazada'){
								echo "<a class='btn' style='padding:0;margin:0 0 0 5px;font-size:12px;' onclick='UpdatePlatform(".'"'.$k.'"'.",this)'>更新平台认可方式</a>";
							}
						?>
					</div>
				</div>
			<?php }?>
		</div>
		</div>
		<div class="myl myd mt10"><p class="p3">打印设置:</p>	
		<div class="myr" style="margin-left: 63px;">
			<div class="drop_list">
					<?php $print = @$serviceUserById['print_params']?>
					<?php if($key == "custom"){
							if(isset($print['label_custom']) && !empty($print['label_custom'])){?>
								<div><label><input type="radio" id="label_littlebossOptionsArrNew" name="params[print_type]" value="3" <?php echo !isset($serviceUserById['print_type'])?'':(($serviceUserById['print_type'] == 3)?'checked':''); ?>> 小老板高仿标签(新)</label>
											<div data="label_littlebossOptionsArrNew" style="overflow: auto;<?php echo !isset($serviceUserById['print_type'])?'display:none;':($serviceUserById['print_type'] == 3?'':'display:none;'); ?>">
													<div><label>打印类型:&nbsp;</label><?php echo Html::dropDownList('params[print_params][label_littlebossOptionsArrNew][printFormat]',$carrier_template_highcopy['printFormat'],['0'=>'A4','1'=>'10*10'],['class'=>'','style'=>'width:100px;','data'=>'label_littlebossOptionsArrNew']);?></div>
		                                            <div class="col-xs-4 mTop10 mBottom10" style="padding-left: 0px; padding-right: 10px;" id="printMbDiv">
		                                                <div class="col-xs-12 p0 border3 bgColor8" style="height:24px;line-height:24px;">&nbsp;&nbsp;面单</div>
		                                                <div class="col-xs-12 p0 border3">
		                                                    <div class="col-xs-12 p0 h30">
		                                                        <a href="javascript:;" class="mLeft15" onclick="selectMianBan('md');">选择模板</a>
		                                                    </div>
		                                                    <div class="col-xs-12 mBottom10 p0" id="model_md">
		                                                        <a href="javascript:;" onclick="selectMianBan('md');">
		                                                            <img <?php echo empty($carrier_template_highcopy['carrier_lable'])?'':'src="'.$carrier_template_highcopy['carrier_lable'][0]['template_img'].'"'; ?> style="width:100%" data="<?php echo empty($carrier_template_highcopy['carrier_lable'])?'':$carrier_template_highcopy['carrier_lable'][0]['id']; ?>">
		                                                        </a>
		                                                        <input name='params[print_params][label_littlebossOptionsArrNew][carrier_lable]' type="hidden" name="printTemplateId" id="printTemplateId" value="<?php echo empty($carrier_template_highcopy['carrier_lable'])?'':$carrier_template_highcopy['carrier_lable'][0]['id']; ?>">
		                                                        <div class="col-xs-12 text-center" style="min-height:28px;line-height: 13px;"><?php echo empty($carrier_template_highcopy['carrier_lable'])?'':$carrier_template_highcopy['carrier_lable'][0]['template_name']; ?></div>
		                                                    </div>
		                                                </div>
		                                            </div>
		                                            <div class="col-xs-4 mTop10 mBottom10" style="padding-left: 5px; padding-right: 5px;" id="printBgDiv">
		                                                <div class="col-xs-12 p0 border3 bgColor8 pLeft10 pRight10 checkbox m0" style="height:24px;line-height:24px;">&nbsp;&nbsp;报关单<label class="pull-right fRed" style="width:74px;">启用&nbsp;&nbsp;&nbsp;<input id="openBg" name="openBg" value="0" onclick="enableBg(this);" type="checkbox" <?php echo empty($carrier_template_highcopy['declare_lable'])?'':'checked'; ?> style="margin-left:0;"></label></div>
		                                                <div class="col-xs-12 p0 border3">
		                                                    <div class="col-xs-12 p0 h30">
		                                                        <a id="selectBaoguan" href="javascript:;" class="mLeft15" onclick="selectMianBan('bg');" style="<?php echo empty($carrier_template_highcopy['declare_lable'])?'display:none;':''; ?>">选择模板</a>
		                                                    </div>
		                                                    <div class="col-xs-12 mBottom10 p0" id="model_bg">
		                                                        <img <?php echo empty($carrier_template_highcopy['declare_lable'])?'':'src="'.$carrier_template_highcopy['declare_lable'][0]['template_img'].'"'; ?> style="width:100%" data="26">
		                                                        <input name='params[print_params][label_littlebossOptionsArrNew][declare_lable]' type="hidden" name="customsFormTemplateId" id="customsFormTemplateId" value="<?php echo empty($carrier_template_highcopy['declare_lable'])?'':$carrier_template_highcopy['declare_lable'][0]['id']; ?>">
		                                                        <div class="col-xs-12 text-center" style="min-height:28px;line-height: 13px;"><?php echo empty($carrier_template_highcopy['declare_lable'])?'':$carrier_template_highcopy['declare_lable'][0]['template_name']; ?></div>
		                                                    </div>
		                                                </div>
		                                            </div>
		                                            <div class="col-xs-4 mTop10 mBottom10" style="padding-left: 10px; padding-right: 0px;" id="printJhDiv">
		                                                <div class="col-xs-12 p0 border3 bgColor8 pLeft10 pRight10 checkbox m0" style="height:24px;line-height:24px;">&nbsp;&nbsp;配货单<label class="pull-right fRed" style="width:74px;">启用&nbsp;&nbsp;&nbsp;<input id="openJh" name="openJh" value="0" onclick="enableJh(this);" type="checkbox" <?php echo empty($carrier_template_highcopy['items_lable'])?'':'checked'; ?> style="margin-left:0;"></label></div>
		                                                <div class="col-xs-12 p0 border3">
		                                                    <div class="col-xs-12 p0 h30">
		                                                        <a id="selectJianhuo" href="javascript:;" class="mLeft15" onclick="selectMianBan('jh');" style="<?php echo empty($carrier_template_highcopy['items_lable'])?'display: none;':''; ?>">选择模板</a>
		                                                    </div>
		                                                    <div class="col-xs-12 mBottom10 p0" id="model_jh">
		                                                        <img <?php echo empty($carrier_template_highcopy['items_lable'])?'':'src="'.$carrier_template_highcopy['items_lable'][0]['template_img'].'"'; ?> style="width:100%" data="0">
		                                                        <input name='params[print_params][label_littlebossOptionsArrNew][items_lable]' type="hidden" name="jhTemplateId" id="jhTemplateId" value="<?php echo empty($carrier_template_highcopy['items_lable'])?'':$carrier_template_highcopy['items_lable'][0]['id']; ?>">
		                                                        <div class="col-xs-12 text-center" style="min-height:28px;line-height: 13px;"><?php echo empty($carrier_template_highcopy['items_lable'])?'':$carrier_template_highcopy['items_lable'][0]['template_name']; ?></div>
		                                                    </div>
		                                                </div>
		                                            </div>
		                                            <div>
		                                            	<div id='isAddOrder' style="<?php echo empty($carrier_template_highcopy['printAddVal']['Order_show'])?'display:none':'display:inline-block;'; ?>"><input id="addOrder" name="add[addOrder]"  type="checkbox" <?php echo empty($carrier_template_highcopy['printAddVal']['addOrder'])?'':'checked'; ?> style="margin-left:0;">加打小老板订单号&nbsp;&nbsp;</div>
		                                            	<div id='isAddSku' style="<?php echo empty($carrier_template_highcopy['printAddVal']['Sku_show'])?'display:none':'display:inline-block;'; ?>"><input id="addSku" name="add[addSku]"  type="checkbox" <?php echo empty($carrier_template_highcopy['printAddVal']['addSku'])?'':'checked'; ?> style="margin-left:0;">加打SKU&nbsp;&nbsp;</div>
		                                            	<div id='isCustomsCn' style="<?php echo empty($carrier_template_highcopy['printAddVal']['CustomsCn_show'])?'display:none':'display:inline-block;'; ?>"><input id="addCustomsCn" name="add[addCustomsCn]"  type="checkbox" <?php echo empty($carrier_template_highcopy['printAddVal']['addCustomsCn'])?'':'checked'; ?> style="margin-left:0;">中文报关名&nbsp;&nbsp;</div>
		                                            	<input id='oldAddShow' type='hidden' value='<?php echo empty($carrier_template_highcopy['printAddVal']['addshow'])?'':$carrier_template_highcopy['printAddVal']['addshow'];?>' >
		                                           </div>
		                                        </div>
								</div>
							
								<div>
									<label><input type="radio" id="label_custom_new" name="params[print_type]" value="4" <?php echo !isset($serviceUserById['print_type'])?'checked':(($serviceUserById['print_type'] == 4)?'checked':''); ?>> 自定义标签(新)</label>
									<?php
										if($serviceUserById['print_type'] == 4){
											echo Html::dropDownList('params[print_params][label_custom_new][carrier_lable]',@$print['label_custom_new']['carrier_lable'],@$print['label_custom_newCarrierArr'],['class'=>'','prompt'=>'物流面单','style'=>'width:100px;','data'=>'label_custom_new']);
											echo Html::dropDownList('params[print_params][label_custom_new][declare_lable]',@$print['label_custom_new']['declare_lable'],@$print['label_custom_newDeclareArr'],['class'=>'','prompt'=>'报关面单','style'=>'width:100px;','data'=>'label_custom_new']);
											echo Html::dropDownList('params[print_params][label_custom_new][items_lable]',@$print['label_custom_new']['items_lable'],@$print['label_custom_newItemsArr'],['class'=>'','prompt'=>'物品面单','style'=>'width:100px;','data'=>'label_custom_new']);
										}
										else{
										echo Html::dropDownList('params[print_params][label_custom_new][carrier_lable]',@$print['label_custom_new']['carrier_lable'],@$print['label_custom_newCarrierArr'],['class'=>'','prompt'=>'物流面单','style'=>'width:100px;display:none;','data'=>'label_custom_new']);
										echo Html::dropDownList('params[print_params][label_custom_new][declare_lable]',@$print['label_custom_new']['declare_lable'],@$print['label_custom_newDeclareArr'],['class'=>'','prompt'=>'报关面单','style'=>'width:100px;display:none;','data'=>'label_custom_new']);
										echo Html::dropDownList('params[print_params][label_custom_new][items_lable]',@$print['label_custom_new']['items_lable'],@$print['label_custom_newItemsArr'],['class'=>'','prompt'=>'物品面单','style'=>'width:100px;display:none;','data'=>'label_custom_new']);
										}
									?>
								</div>
							
								<?php
								//假如没有使用过则屏蔽
								$isExistCrtemplate = true;
								if(\eagle\modules\carrier\helpers\CarrierOpenHelper::isExistCrtemplateOld() == false){
									$isExistCrtemplate = false;
								}
								?>
							
								<div style='<?=($isExistCrtemplate==false) ? 'display:none;' : '' ?>'>
									<label><input type="radio" id="label_custom" name="params[print_type]" value="2" <?php echo !isset($serviceUserById['print_type'])?'checked':(($serviceUserById['print_type'] == 2)?'checked':''); ?>> 自定义标签</label>
									<?php
										if($serviceUserById['print_type'] == 2){
											echo Html::dropDownList('params[print_params][label_custom][carrier_lable]',@$print['label_custom']['carrier_lable'],@$print['label_customCarrierArr'],['class'=>'','prompt'=>'物流面单','style'=>'width:100px;']);
											echo Html::dropDownList('params[print_params][label_custom][declare_lable]',@$print['label_custom']['declare_lable'],@$print['label_customDeclareArr'],['class'=>'','prompt'=>'报关面单','style'=>'width:100px;']);
											echo Html::dropDownList('params[print_params][label_custom][items_lable]',@$print['label_custom']['items_lable'],@$print['label_customItemsArr'],['class'=>'','prompt'=>'物品面单','style'=>'width:100px;']);
										}
										else{
											echo Html::dropDownList('params[print_params][label_custom][carrier_lable]',@$print['label_custom']['carrier_lable'],@$print['label_customCarrierArr'],['class'=>'','prompt'=>'物流面单','style'=>'width:100px;display:none;','data'=>'label_custom']);
											echo Html::dropDownList('params[print_params][label_custom][declare_lable]',@$print['label_custom']['declare_lable'],@$print['label_customDeclareArr'],['class'=>'','prompt'=>'报关面单','style'=>'width:100px;display:none;','data'=>'label_custom']);
											echo Html::dropDownList('params[print_params][label_custom][items_lable]',@$print['label_custom']['items_lable'],@$print['label_customItemsArr'],['class'=>'','prompt'=>'物品面单','style'=>'width:100px;display:none;','data'=>'label_custom']);
										}
									?>
								</div>
								<div id="labelcustom" style="padding:5px 0;<?php echo !isset($serviceUserById['print_type'])?'':($serviceUserById['print_type'] == 2?'':'display:none;'); ?>">自定义标签如果没有可选项请先设置<a href="/configuration/carrierconfig/carrier-custom-label-list" target="_blank" class="btn btn-success btn-xs">自定义标签</a></div>
							<?php }?>
					<?php }else{?>
					<?php if(isset($print['label_api'])){?>
						<div>
							<label><input type="radio" id="label_api" name="params[print_type]" value="0" <?= ($serviceUserById['print_type'] == 0)?'checked':''?>> 货代系统提供面单</label>
						</div>
						<div class="drop_list" style="margin-left: 16px;">
							<?php 
								if(!empty($serviceUserById['carrierParams']))
								foreach (@$serviceUserById['carrierParams'] as $carrierParams){
									if($carrierParams['ui_type']=='param_print'){
							?>
								<div class="rightDIV<?= empty($carrierParams['is_hidden'])?"":" hidden" ?>" style="width: 310px;">
									<span>
									<?php if($carrierParams['carrier_is_required']) echo '<span class="impor_red">*</span>';?>
									<?= @$carrierParams['carrier_param_name']?></span>
									<?php switch ($carrierParams['display_type']){
										case 'dropdownlist':
											echo Html::dropDownList('params[carrierParams]['.$carrierParams['carrier_param_key'].']',$carrierParams['param_value'],$carrierParams['carrier_param_value'],['class'=>($carrierParams['carrier_is_required'])?'req':' ']);
											break;
										case 'text':
											if($carrierParams['carrier_is_encrypt']){
												echo Html::passwordInput('params[carrierParams]['.$carrierParams['carrier_param_key'].']',$carrierParams['param_value'],$carrierParams['carrier_param_value'],['class'=>($carrierParams['carrier_is_required'])?'req iv-input':' iv-input']);
											}
											else{
												echo Html::textInput('params[carrierParams]['.$carrierParams['carrier_param_key'].']',$carrierParams['param_value'],$carrierParams['carrier_param_value'],['class'=>($carrierParams['carrier_is_required'])?'req iv-input':' iv-input']);
											}
											break;
									}?>
								</div>
							<?php }}?>
						</div>		
					<?php }
					
						if(isset($print['label_custom']) && !empty($print['label_custom'])){?>
						<div><label><input type="radio" id="label_littlebossOptionsArrNew" name="params[print_type]" value="3" <?= (($serviceUserById['print_type'] == 3))?'checked':''?>> 小老板高仿标签(新)</label>
									<div data="label_littlebossOptionsArrNew" style="overflow: auto;<?php echo $serviceUserById['print_type'] == 3?'':'display:none;'; ?>">
											<div><label>打印类型:&nbsp;</label><?php echo Html::dropDownList('params[print_params][label_littlebossOptionsArrNew][printFormat]',$carrier_template_highcopy['printFormat'],['0'=>'A4','1'=>'10*10'],['class'=>'','style'=>'width:100px;','data'=>'label_littlebossOptionsArrNew']);?></div>
                                            <div class="col-xs-4 mTop10 mBottom10" style="padding-left: 0px; padding-right: 10px;" id="printMbDiv">
                                                <div class="col-xs-12 p0 border3 bgColor8" style="height:24px;line-height:24px;">&nbsp;&nbsp;面单</div>
                                                <div class="col-xs-12 p0 border3">
                                                    <div class="col-xs-12 p0 h30">
                                                        <a href="javascript:;" class="mLeft15" onclick="selectMianBan('md');">选择模板</a>
                                                    </div>
                                                    <div class="col-xs-12 mBottom10 p0" id="model_md">
                                                   		<?php
															$tmp_carrier_img = '';
															$tmp_carrier_id = '';
															$tmp_carrier_name = '';
															
															if(empty($carrier_template_highcopy['carrier_lable'])){
																if(@$carrier_code == 'lb_seko'){
																	$tmp_carrier_img = '/images/customprint/label_model/jumia_seko.png';
																	$tmp_carrier_id = 30;
																	$tmp_carrier_name = 'Jumia-Seko-NG-KE';
																}
															}else{
																$tmp_carrier_img = $carrier_template_highcopy['carrier_lable'][0]['template_img'];
																$tmp_carrier_id = $carrier_template_highcopy['carrier_lable'][0]['id'];
																$tmp_carrier_name = $carrier_template_highcopy['carrier_lable'][0]['template_name'];
															}
                                                        ?>
                                                        <a href="javascript:;" onclick="selectMianBan('md');">
                                                        	<img <?php echo empty($tmp_carrier_img)?'':'src="'.$tmp_carrier_img.'"'; ?> style="width:100%" data="<?php echo empty($tmp_carrier_id)?'':$tmp_carrier_id; ?>">
                                                        </a>
                                                        <input name='params[print_params][label_littlebossOptionsArrNew][carrier_lable]' type="hidden" name="printTemplateId" id="printTemplateId" value="<?php echo empty($tmp_carrier_id)?'':$tmp_carrier_id; ?>">
                                                        <div class="col-xs-12 text-center" style="min-height:28px;line-height: 13px;"><?php echo empty($tmp_carrier_name)?'':$tmp_carrier_name; ?></div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-xs-4 mTop10 mBottom10" style="padding-left: 5px; padding-right: 5px;" id="printBgDiv">
                                                <div class="col-xs-12 p0 border3 bgColor8 pLeft10 pRight10 checkbox m0" style="height:24px;line-height:24px;">&nbsp;&nbsp;报关单<label class="pull-right fRed" style="width:74px;">启用&nbsp;&nbsp;&nbsp;<input id="openBg" name="openBg" value="0" onclick="enableBg(this);" type="checkbox" <?php echo empty($carrier_template_highcopy['declare_lable'])?'':'checked'; ?> style="margin-left:0;"></label></div>
                                                <div class="col-xs-12 p0 border3">
                                                    <div class="col-xs-12 p0 h30">
                                                        <a id="selectBaoguan" href="javascript:;" class="mLeft15" onclick="selectMianBan('bg');" style="<?php echo empty($carrier_template_highcopy['declare_lable'])?'display:none;':''; ?>">选择模板</a>
                                                    </div>
                                                    <div class="col-xs-12 mBottom10 p0" id="model_bg">
                                                        <img <?php echo empty($carrier_template_highcopy['declare_lable'])?'':'src="'.$carrier_template_highcopy['declare_lable'][0]['template_img'].'"'; ?> style="width:100%" data="26">
                                                        <input name='params[print_params][label_littlebossOptionsArrNew][declare_lable]' type="hidden" name="customsFormTemplateId" id="customsFormTemplateId" value="<?php echo empty($carrier_template_highcopy['declare_lable'])?'':$carrier_template_highcopy['declare_lable'][0]['id']; ?>">
                                                        <div class="col-xs-12 text-center" style="min-height:28px;line-height: 13px;"><?php echo empty($carrier_template_highcopy['declare_lable'])?'':$carrier_template_highcopy['declare_lable'][0]['template_name']; ?></div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-xs-4 mTop10 mBottom10" style="padding-left: 10px; padding-right: 0px;" id="printJhDiv">
                                                <div class="col-xs-12 p0 border3 bgColor8 pLeft10 pRight10 checkbox m0" style="height:24px;line-height:24px;">&nbsp;&nbsp;配货单<label class="pull-right fRed" style="width:74px;">启用&nbsp;&nbsp;&nbsp;<input id="openJh" name="openJh" value="0" onclick="enableJh(this);" type="checkbox" <?php echo empty($carrier_template_highcopy['items_lable'])?'':'checked'; ?> style="margin-left:0;"></label></div>
                                                <div class="col-xs-12 p0 border3">
                                                    <div class="col-xs-12 p0 h30">
                                                        <a id="selectJianhuo" href="javascript:;" class="mLeft15" onclick="selectMianBan('jh');" style="<?php echo empty($carrier_template_highcopy['items_lable'])?'display: none;':''; ?>">选择模板</a>
                                                    </div>
                                                    <div class="col-xs-12 mBottom10 p0" id="model_jh">
                                                        <img <?php echo empty($carrier_template_highcopy['items_lable'])?'':'src="'.$carrier_template_highcopy['items_lable'][0]['template_img'].'"'; ?> style="width:100%" data="0">
                                                        <input name='params[print_params][label_littlebossOptionsArrNew][items_lable]' type="hidden" name="jhTemplateId" id="jhTemplateId" value="<?php echo empty($carrier_template_highcopy['items_lable'])?'':$carrier_template_highcopy['items_lable'][0]['id']; ?>">
                                                        <div class="col-xs-12 text-center" style="min-height:28px;line-height: 13px;"><?php echo empty($carrier_template_highcopy['items_lable'])?'':$carrier_template_highcopy['items_lable'][0]['template_name']; ?></div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div>
                                            	<div id='isAddOrder' style="<?php echo empty($carrier_template_highcopy['printAddVal']['Order_show'])?'display:none':'display:inline-block;'; ?>"><input id="addOrder" name="add[addOrder]"  type="checkbox" <?php echo empty($carrier_template_highcopy['printAddVal']['addOrder'])?'':'checked'; ?> style="margin-left:0;">加打小老板订单号&nbsp;&nbsp;</div>
                                            	<div id='isAddSku' style="<?php echo empty($carrier_template_highcopy['printAddVal']['Sku_show'])?'display:none':'display:inline-block;'; ?>"><input id="addSku" name="add[addSku]"  type="checkbox" <?php echo empty($carrier_template_highcopy['printAddVal']['addSku'])?'':'checked'; ?> style="margin-left:0;">加打SKU&nbsp;&nbsp;</div>
                                            	<div id='isCustomsCn' style="<?php echo empty($carrier_template_highcopy['printAddVal']['CustomsCn_show'])?'display:none':'display:inline-block;'; ?>"><input id="addCustomsCn" name="add[addCustomsCn]"  type="checkbox" <?php echo empty($carrier_template_highcopy['printAddVal']['addCustomsCn'])?'':'checked'; ?> style="margin-left:0;">中文报关名&nbsp;&nbsp;</div>
                                            	<input id='oldAddShow' type='hidden' value='<?php echo empty($carrier_template_highcopy['printAddVal']['addshow'])?'':$carrier_template_highcopy['printAddVal']['addshow'];?>' >
                                           </div>
                                        </div>
						</div>
						
						<div>
							<label><input type="radio" id="label_custom_new" name="params[print_type]" value="4" <?= (($serviceUserById['print_type'] == 4))?'checked':''?>> 自定义标签(新)</label>
							<?php
								if($serviceUserById['print_type'] == 4){
									echo Html::dropDownList('params[print_params][label_custom_new][carrier_lable]',@$print['label_custom_new']['carrier_lable'],@$print['label_custom_newCarrierArr'],['class'=>'','prompt'=>'物流面单','style'=>'width:100px;','data'=>'label_custom_new']);
									echo Html::dropDownList('params[print_params][label_custom_new][declare_lable]',@$print['label_custom_new']['declare_lable'],@$print['label_custom_newDeclareArr'],['class'=>'','prompt'=>'报关面单','style'=>'width:100px;','data'=>'label_custom_new']);
									echo Html::dropDownList('params[print_params][label_custom_new][items_lable]',@$print['label_custom_new']['items_lable'],@$print['label_custom_newItemsArr'],['class'=>'','prompt'=>'物品面单','style'=>'width:100px;','data'=>'label_custom_new']);
								}
								else{
								echo Html::dropDownList('params[print_params][label_custom_new][carrier_lable]',@$print['label_custom_new']['carrier_lable'],@$print['label_custom_newCarrierArr'],['class'=>'','prompt'=>'物流面单','style'=>'width:100px;display:none;','data'=>'label_custom_new']);
								echo Html::dropDownList('params[print_params][label_custom_new][declare_lable]',@$print['label_custom_new']['declare_lable'],@$print['label_custom_newDeclareArr'],['class'=>'','prompt'=>'报关面单','style'=>'width:100px;display:none;','data'=>'label_custom_new']);
								echo Html::dropDownList('params[print_params][label_custom_new][items_lable]',@$print['label_custom_new']['items_lable'],@$print['label_custom_newItemsArr'],['class'=>'','prompt'=>'物品面单','style'=>'width:100px;display:none;','data'=>'label_custom_new']);
								}
							?>
						</div>
						
						<?php
						//假如没有使用过则屏蔽
						$isExistCrtemplate = true;
						if(\eagle\modules\carrier\helpers\CarrierOpenHelper::isExistCrtemplateOld() == false){
							$isExistCrtemplate = false;
						}
						?>
						
						<div style='<?=($isExistCrtemplate==false) ? 'display:none;' : '' ?>'>
							<label><input type="radio" id="label_custom" name="params[print_type]" value="2" <?= (($serviceUserById['print_type'] == 2))?'checked':''?>> 自定义标签</label>
							<?php
								if($serviceUserById['print_type'] == 2){
									echo Html::dropDownList('params[print_params][label_custom][carrier_lable]',@$print['label_custom']['carrier_lable'],@$print['label_customCarrierArr'],['class'=>'','prompt'=>'物流面单','style'=>'width:100px;','data'=>'label_custom']);
									echo Html::dropDownList('params[print_params][label_custom][declare_lable]',@$print['label_custom']['declare_lable'],@$print['label_customDeclareArr'],['class'=>'','prompt'=>'报关面单','style'=>'width:100px;','data'=>'label_custom']);
									echo Html::dropDownList('params[print_params][label_custom][items_lable]',@$print['label_custom']['items_lable'],@$print['label_customItemsArr'],['class'=>'','prompt'=>'物品面单','style'=>'width:100px;','data'=>'label_custom']);
								}
								else{
								echo Html::dropDownList('params[print_params][label_custom][carrier_lable]',@$print['label_custom']['carrier_lable'],@$print['label_customCarrierArr'],['class'=>'','prompt'=>'物流面单','style'=>'width:100px;display:none;','data'=>'label_custom']);
								echo Html::dropDownList('params[print_params][label_custom][declare_lable]',@$print['label_custom']['declare_lable'],@$print['label_customDeclareArr'],['class'=>'','prompt'=>'报关面单','style'=>'width:100px;display:none;','data'=>'label_custom']);
								echo Html::dropDownList('params[print_params][label_custom][items_lable]',@$print['label_custom']['items_lable'],@$print['label_customItemsArr'],['class'=>'','prompt'=>'物品面单','style'=>'width:100px;display:none;','data'=>'label_custom']);
								}
							?>
						</div>
					<?php } ?>
					<div id="labelcustom" style="padding:5px 0;<?php echo $serviceUserById['print_type'] == 2?'':'display:none;'; ?>">自定义标签如果没有可选项请先设置<a href="/configuration/carrierconfig/carrier-custom-label-list" target="_blank" class="btn btn-success btn-xs">自定义标签</a></div>
					<?php 
						if(isset($print['label_littlebossOptionsArr'])){ 
							if(empty($print['label_littleboss']) && $type=='open'){
								$print['label_littleboss'][] = 'label_address';
								$print['label_littleboss'][] = 'label_declare';
								$print['label_littleboss'][] = 'label_items';
							}
						?>
						<div>
							<label><input type="radio" id="label_littlebossOptionsArr" name="params[print_type]" value="1" <?= ($serviceUserById['print_type'] == 1)?'checked':''?>> 小老板高仿标签</label>
							<div data="label_littlebossOptionsArr" style="<?php echo $serviceUserById['print_type'] == 1?'':'display:none;'; ?>"><?= Html::checkboxList('params[print_params][label_littleboss]',@$print['label_littleboss'],$print['label_littlebossOptionsArr']); ?></div>
						</div>
					<?php }
									
					}?>
				</div>	
		</div>
		</div>
		
		<?php if($carrier_code == "lb_alionlinedelivery"){?>
		<div  class="myl myd mt10"><p class="p3">地址信息:</p>
		<div style='margin-left: 15px;'>
			<?php
			echo "<a class='btn' style='padding:0;margin:0 0 0 5px;font-size:12px;' onclick='updateAliexpressAddressInof()'>同步速卖通地址信息</a>";
			?>
			<table class="table table-condensed table-bordered">
				<tr><th>店铺</th><th>发货地址 (英文)</th><th>发货地址 (中文)</th><th>退货地址 (中文)</th></tr>
				<?php
				if(count($aliexpressAddressInfo) > 0){
					foreach ($aliexpressAddressInfo as $aliexpressAddressInfoKey => $aliexpressAddressInfoVal){
				?>
				<tr>
					<td><?=$aliexpressAddressInfoVal['sellerloginid'] ?></td>
					
					<?php
					if(isset($serviceUserById['address']['aliexpressAddress']['sender'][$aliexpressAddressInfoKey])){
					?>
					<td><?php echo Html::dropDownList('params[address][aliexpressAddress][sender]['.$aliexpressAddressInfoKey.']',@$serviceUserById['address']['aliexpressAddress']['sender'][$aliexpressAddressInfoKey],$aliexpressAddressInfoVal['address_info']['sender'],['class'=>'','style'=>'width:100px;height:26px;']); ?></td>
					<?php
					}else{
					?>
					<td><?php echo Html::dropDownList('params[address][aliexpressAddress][sender]['.$aliexpressAddressInfoKey.']',@$serviceUserById['address']['aliexpressAddress']['sender'],$aliexpressAddressInfoVal['address_info']['sender'],['class'=>'','style'=>'width:100px;height:26px;']); ?></td>
					<?php 
					}
					?>
					
					<?php
					if(isset($serviceUserById['address']['aliexpressAddress']['pickup'][$aliexpressAddressInfoKey])){
					?>
					<td><?php echo Html::dropDownList('params[address][aliexpressAddress][pickup]['.$aliexpressAddressInfoKey.']',@$serviceUserById['address']['aliexpressAddress']['pickup'][$aliexpressAddressInfoKey],$aliexpressAddressInfoVal['address_info']['pickup'],['class'=>'','style'=>'width:100px;height:26px;']); ?></td>
					<?php
					}else{
					?>
					<td><?php echo Html::dropDownList('params[address][aliexpressAddress][pickup]['.$aliexpressAddressInfoKey.']',@$serviceUserById['address']['aliexpressAddress']['pickup'],$aliexpressAddressInfoVal['address_info']['pickup'],['class'=>'','style'=>'width:100px;height:26px;']); ?></td>
					<?php
					}
					?>
					
					<?php
					if(isset($serviceUserById['address']['aliexpressAddress']['refund'][$aliexpressAddressInfoKey])){
					?>
					<td><?php echo Html::dropDownList('params[address][aliexpressAddress][refund]['.$aliexpressAddressInfoKey.']',@$serviceUserById['address']['aliexpressAddress']['refund'][$aliexpressAddressInfoKey],$aliexpressAddressInfoVal['address_info']['refund'],['class'=>'','style'=>'width:100px;height:26px;']); ?></td>
					<?php
					}else{
					?>
					<td><?php echo Html::dropDownList('params[address][aliexpressAddress][refund]['.$aliexpressAddressInfoKey.']',@$serviceUserById['address']['aliexpressAddress']['refund'],$aliexpressAddressInfoVal['address_info']['refund'],['class'=>'','style'=>'width:100px;height:26px;']); ?></td>
					<?php 
					}
					?>
				
				</tr>
				<?php
					}
				}
				?>
			</table>
		</div>
		<?php } ?>
		
		<?php if($carrier_code == "lb_edis"){?>
		<div  class="myl myd mt10"><p class="p3">地址信息:</p>
		<div style='margin-left: 15px;'>
			<?php
			echo "<a class='btn' style='padding:0;margin:0 0 0 5px;font-size:12px;' onclick='updatesEdisAddressInof(".@$serviceUserById['serviceID'].")'>同步发货地址和偏好设置</a>";
			?>
			<table class="table table-condensed table-bordered edis">
			<?php echo $edisAddressInfo; ?>
			</table>
		</div>
		<?php } ?>
			
		<?php if($key == "custom" || !empty($serviceUserById['is_show_address'])){?>
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
		
		
		<div class="myl myd mt10"><p class="p3">高级设置:<span id="caretDown" class="caretDown gaoji" style="font-size:15px;margin-left:15px;">+ 展开</span></p>
		<div class="myl gaojiitem" style="display:none;">
		<?php if($key!="custom"){ 
			if($param_set_count>0){
		?>
		<div class="myl">物流参数</div>
		<div class="myr" style='margin-top: 28px;'>
			<?php 
				if(!empty($serviceUserById['carrierParams']))
				foreach (@$serviceUserById['carrierParams'] as $carrierParams){  
					if($carrierParams["carrier_param_key"]=="edisAddressoinfo")
						continue;
					if($carrierParams['ui_type']=='param_set' || empty($carrierParams['ui_type'])){
			?>
				<div class="rightDIV" style="width: 500px;text-align:left;float:left;margin-left:26px;">
					<span>
					<?php if($carrierParams['carrier_is_required']) echo '<span class="impor_red">*</span>';?>
					<?= @$carrierParams['carrier_param_name']?></span>
					<?php switch ($carrierParams['display_type']){
						case 'dropdownlist':
							echo Html::dropDownList('params[carrierParams]['.$carrierParams['carrier_param_key'].']',$carrierParams['param_value'],$carrierParams['carrier_param_value'],['class'=>($carrierParams['carrier_is_required'])?'req':' ']);
							break;
						case 'text':
							if($carrierParams['carrier_is_encrypt']){
								echo Html::passwordInput('params[carrierParams]['.$carrierParams['carrier_param_key'].']',$carrierParams['param_value'],$carrierParams['carrier_param_value'],['class'=>($carrierParams['carrier_is_required'])?'req iv-input':' iv-input']);
							}
							else{
								echo Html::textInput('params[carrierParams]['.$carrierParams['carrier_param_key'].']',$carrierParams['param_value'],$carrierParams['carrier_param_value'],['class'=>($carrierParams['carrier_is_required'])?'req iv-input':' iv-input']);
							}
							break;
					}?>
				</div>
			<?php }}?>
		</div>
		<?php }
		}?>
		
		<div class="clear"></div>
		<div class="myl">报关配置</div>
		<div class="myr" style='margin-top: 28px;'>
			<div class="rightDIV" style="width: 500px;text-align:left;float:left;margin-left:26px;">
				<span style='float:left;'>最大报关金额：</span>
				<div class="input-group" style="width:250px;float:left;">
					<input name="params[declaration_max_value]" type="text" class="form-control" value='<?=(empty($serviceUserById['declaration_max_value']) ? '' : ($serviceUserById['declaration_max_value'] == 0 ? '' : $serviceUserById['declaration_max_value'])); ?>'>
					<span class="input-group-addon">USD</span>
				</div>
				<div class="clear"></div>
				<div style='color: red;'>说明！ 当订单报关金额超出最大报关金额时，使用最大报关金额</div>
			</div>
		</div>
		
		<div style='display: none;'>
			<?= Html::dropDownList('params[declaration_max_currency]',@$serviceUserById['declaration_max_currency'],['USD'=>'USD'],['class'=>'d_width_x']) ?>
			最高报关重量<?= Html::input('text','params[declaration_max_weight]',@$serviceUserById['declaration_max_weight'],['class'=>'myText iv-input'])?> g
		</div>
		
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
		
		<div class="clear"></div>
		<div class="myl"><span class="impor_red">*</span>客户参考号配置</div>
		<div class="myr" style='margin-top: 28px;'>
			<?php 
			if(!empty($serviceUserById['customer_number_config']))
			foreach (@$serviceUserById['customer_number_config'] as $k=>$customer_number_config){?>
					<div class="customer_number_config <?= $k?> clear">
						<div style="width:77px;text-align:right;float:left;"><?= $k?>：</div>
						<div style="margin-left:79px;">
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
	<button type="button" class="iv-btn btn-primary btn-sm" onclick="saveShipingService('<?=@$_REQUEST['key']?>')">提交</button>
	<button class="iv-btn btn-default btn-sm modal-close">关闭</button>
</div>

<script>
	$(function(){
		ShippingJS.init2();
	});
	$.initQtip();
</script>