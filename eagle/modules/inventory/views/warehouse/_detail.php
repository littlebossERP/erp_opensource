<?php

use yii\helpers\Html;
use eagle\modules\util\helpers\TranslateHelper;
use eagle\helpers\UserHelper;
use eagle\modules\inventory\helpers\WarehouseHelper;

/* @var $this yii\web\View */
/* @var $model eagle\modules\catalog\models\Product */
$warehouseInfo['id'] = ((is_numeric($model->warehouse_id))?$model->warehouse_id:'');
$warehouseInfo['name'] = ((empty($model->name))?"":$model->name);
$warehouseInfo['is_active']= ((empty($model->is_active))?"Y":$model->is_active);
$warehouseInfo['address_nation']= ((empty($model->address_nation))?"":$model->address_nation) ;
$warehouseInfo['address_state']= ((empty($model->address_state))?"":$model->address_state);
$warehouseInfo['address_city']= ((empty($model->address_city))?"":$model->address_city) ;
$warehouseInfo['address_street']= ((empty($model->address_street))?"":$model->address_street);
$warehouseInfo['address_postcode']= ((empty($model->address_postcode))?"":$model->address_postcode);
$warehouseInfo['address_phone']= ((empty($model->address_phone))?"":$model->address_phone);
$warehouseInfo['comment']= ((empty($model->comment))?"":$model->comment);
$warehouseInfo['addi_info']= ((empty($model->addi_info))?array():json_decode($model->addi_info,true));
$warehouseInfo['capture_user_id']= ((empty($model->capture_user_id))?\Yii::$app->user->id : $model->capture_user_id);
$warehouseInfo['capture_user_name'] = ((empty($model->capture_user_id))? \Yii::$app->user->identity->getFullName() : UserHelper::getFullNameByUid($model->capture_user_id));
$warehouseInfo['create_time']= ((empty($model->create_time))?"":$model->create_time);
$warehouseInfo['update_time']= ((empty($model->update_time))?"":$model->update_time) ;
$warehouseInfo['is_oversea']= ((empty($model->is_oversea))?"0":$model->is_oversea);

if(isset($warehouseInfo['addi_info']['address_nation']))
	$userInput_nation = $warehouseInfo['addi_info']['address_nation'];
$readonly_html='';
if (!empty($_GET['tt'])){
	if ($_GET['tt']=='edit'){
		$readonly_html = 'readonly="readonly"';
	}
}

// 特殊字符处理
foreach($warehouseInfo as &$value){
	if (is_string($value))
	$value = htmlspecialchars($value);
}

?>
<style>
.view_wh_detail_win .modal-dialog{
	width: 900px;
	max-height: 700px;
	overflow-y: auto;	
}
#warehouse_model_form td{
	padding:4px 0px !important;
	margin:0px !important;
	vertical-align: middle !important;
}
#warehouse_model_form .eagle-form-control{
	padding:0px !important;
	margin:0px !important;
	vertical-align: middle !important;
}
#warehouse_model_form table.table input,#warehouse_model_form table.table select{
	width:100%;
}
.content_lfet{
	float:left;
}
.content_right{
	float:right;
}
.ui-autocomplete {
z-index: 2000;
}
.ui-combobox-input {
	width: 90% !important;
}
</style>
<FORM id="warehouse_model_form" role="form">
  	<ul  class="list-unstyled">
  		<li>
  			<input type="hidden" name="tt" value="<?=isset($_GET['tt'])?$_GET['tt']:'' ?>" />
  			<input type="hidden" id="warehouse_id" class="eagle-form-control" name="warehouse_id" value="<?=$warehouseInfo['id'] ?>" />
	  		<table class="table" style="width:100%;margin-bottom:5px;font-size:12px;">
	  			<tr><th colspan="8"><?= TranslateHelper::t('仓库基本信息')?></th></tr>
		  		<tr>
		  			<td style="width:55px;text-align:right;"><?=TranslateHelper::t('仓库名称') ?></td>
		  			<td style="width:160px;"><input type="text" class="eagle-form-control" id="name" name="name" value="<?=$warehouseInfo['name']?>" <?=$readonly_html?> ></td>
		  			<td style="width:55px;text-align:right;"><div><?=TranslateHelper::t('启用状态') ?></div></td>
		  			<td style="width:160px;">
		  				<?php if($_GET['tt']=='edit' or $_GET['tt']=='create'): ?>
		  				<?php if($model->warehouse_id!==0):?>
		  					<select id="is_active" name="is_active" value="" class="eagle-form-control">
		  						<option value="Y" <?=!empty($warehouseInfo['is_active']) || $warehouseInfo['is_active'] == 'Y' ? 'selected' : '' ?>><?=$active_status['Y'] ?></option>
		  						<option value="N" <?=!empty($warehouseInfo['is_active']) && $warehouseInfo['is_active'] == 'N' ? 'selected' : '' ?>><?=$active_status['N'] ?></option>
		  					</select>
		  				<?php else: ?>
		  					<select id="is_active" name="is_active" value="" class="eagle-form-control" readonly title="<?=TranslateHelper::t('默认仓库不能弃用或关闭') ?>">
		  						<option value="Y" selected><?=$active_status['Y'] ?></option>
		  					</select>
		  				<?php endif; ?>
		  				<?php else: ?>
		  					<input type="text" class="eagle-form-control" id="is_active" name="is_active" value="<?=$active_status[$warehouseInfo['is_active']] ?>">
		  				<?php endif; ?>
		  			</td>
		  			<td style="width:55px;text-align:right;"><?=TranslateHelper::t('仓库类型') ?></td>
		  			<td style="width:160px;">
		  				<input type="hidden" id="is_oversea" name="is_oversea" value="<?=$warehouseInfo['is_oversea'] ?>">
						<input type="text" class="eagle-form-control" value="<?=isset($isOversea[$warehouseInfo['is_oversea']])? $isOversea[$warehouseInfo['is_oversea']] : $warehouseInfo['is_oversea']; ?>" disabled="disabled">
					</td>
					<td style="width:55px;text-align:right;"><?=TranslateHelper::t('操作员') ?></td>
		  			<td style="width:160px;">
		  				<input type="hidden" id="capture_user_id" name="capture_user_id" value="<?=$warehouseInfo['capture_user_id'] ?>">
		  				<input type="text" class="eagle-form-control" id="capture_user_name"  value="<?=$warehouseInfo['capture_user_name'] ?>" disabled="disabled">
					</td>
		  		</tr>
		  		<tr>
		  			<td style="text-align:right;"><?=TranslateHelper::t('邮政编码') ?></td>
		  			<td>
		  				<input type="text" class="eagle-form-control" id="address_postcode" name="address_postcode" value="<?=$warehouseInfo['address_postcode'] ?>">
		  			</td>
		  			<td style="text-align:right;"><?=TranslateHelper::t('联系电话') ?></td>
		  			<td>
		  				<input type="text" class="eagle-form-control" id="address_phone" name="address_phone" value="<?=$warehouseInfo['address_phone'] ?>">
		  			</td>
		  			<td cplspan="4"></td>
		  			
		  		</tr>
		  		<tr>
		  			<td></td>
		  			<td colspan="7">
		  				<div style="width:20%;text-align:left;float:left;"><span style="padding:0px 5px;"><?=TranslateHelper::t('国家') ?></span></div>
		  				<div style="width:20%;text-align:left;float:left;"><span style="padding:0px 5px 0px 10px;"><?=TranslateHelper::t('州/省') ?></span></div>
		  				<div style="width:20%;text-align:left;float:left;"><span style="padding:0px 5px;"><?=TranslateHelper::t('市') ?></span></div>
		  				<div style="width:40%;text-align:left;float:left;"><span style="padding:0px 5px;"><?=TranslateHelper::t('街道门牌') ?></span></div>
		  			</td>
		  		</tr>
		  		<tr>
		  			<td style="text-align:right;"><?=TranslateHelper::t('仓库地址') ?></td>
		  			<td colspan="7">
		  			<?php if($_GET['tt']=='edit' or $_GET['tt']=='create'){ ?>
		  				<div style="width:20%;text-align:left;float:left;padding: 0px 4px;">
		  					<select class="eagle-form-control" id="address_nation" name="address_nation">
		  					<?php foreach ($countryComboBox as $name=>$code){
		  						$selected='';
		  						if((empty($warehouseInfo['address_nation']) or $warehouseInfo['address_nation']=='CN') and ($code=='CN' and $name=='中国') )
		  							$selected=' selected';
		  						if(!empty($warehouseInfo['address_nation']) and $code==$warehouseInfo['address_nation']){
		  							if(!empty($userInput_nation)){
										if($name==$userInput_nation)
		  									$selected=' selected';
		  							}else{
		  								$selected=' selected';
		  							}
		  						}
		  						?>
		  						<option value="<?=$name ?>" <?=$selected ?>><?=$name ?></option>
		  					<?php } ?>
		  					</select>
		  				</div>
		  			<?php }else{?>
		  				<div style="width:20%;text-align:left;float:left;padding: 0px 4px;">
		  					<input type="text" class="eagle-form-control" id="address_nation" name="address_nation" 
		  					value="<?php
		  						if(!empty($userInput_nation)) echo $userInput_nation;
		  						else echo $warehouseInfo['address_nation'];?>" 
		  					style="padding:0px 5px;">
		  				</div>
		  			<?php }?>
		  				<div style="width:20%;text-align:left;float:left;padding: 0px 4px;">
		  					<input type="text" class="eagle-form-control" id="address_nation" name="address_state" value="<?=$warehouseInfo['address_state'] ?>" style="padding:0px 5px;width:95%;float:right;">
		  				</div>
		  				<div style="width:20%;text-align:left;float:left;padding: 0px 4px;">
		  					<input type="text" class="eagle-form-control" id="address_nation" name="address_city" value="<?=$warehouseInfo['address_city'] ?>" style="padding:0px 5px;">
		  				</div>
		  				<div style="width:40%;text-align:left;float:left;padding: 0px 4px;">
		  					<input type="text" class="eagle-form-control" id="address_nation" name="address_street" value="<?=$warehouseInfo['address_street'] ?>" style="padding:0px 5px;">
		  				</div>	
		  			</td>
		  		</tr>
		  		<tr>
			  		<td style="text-align:right;"><?=TranslateHelper::t('备注') ?></td>
		  			<td colspan="7">
		  				<textarea class="form-control" rows="3" name="comment" id="comment"><?= $warehouseInfo['comment']?></textarea>	
		  			</td>
		  		</tr>
		  	</table>
  		</li>
  		<li id="receiving_country_list">
  			<?php if ($_GET['tt']=='view' or ($_GET['tt']!=='view' && $model->warehouse_id==0)): ?>
  				<div style="display: inline-block;padding: 6px 12px;margin-bottom: 0;font-size: 12px;text-align: center;vertical-align: middle;font-weight:700;background-color:rgb(202,202,202);width:100%">
  					<?= TranslateHelper::t('仓库已选择的可递送国家')?><?php if($model->warehouse_id==0) echo TranslateHelper::t('(默认仓库可递送所有国家，且不能编辑)')?>
  					<div class="btn btn-warning region_toggle" style="padding:0px;font-size:12px;border-radius:0px;"><?=TranslateHelper::t('展开/折叠') ?></div>
  				</div>
  				<div class="region_country" style="display:none">
	  				<?php if(count($countrys['receivingCountrys'])>0): 
	  							foreach ($countrys['receivingCountrys'] as $one):?>
	  					<div style="width:100%;"><div style="width:100%;clear:both;font-weight:700;"><?=$region[$one['name']].'('.$one['name'].')' ?></div>
	  					<?=Html::ul($one['value'])?>
	  					</div>
	  				<?php 		endforeach;
	  					  else: ?>
	  					<div style="padding: 6px 20px;"><?=TranslateHelper::t('没有选择任何可送达国家') ?></div>
	  				<?php endif; ?>
  				</div>
  				<div style="margin:5px 0px;clear: both;display: block;width:100%"> </div> 
  			<?php endif; ?>
  			<?php if ($_GET['tt']!=='view' && $model->warehouse_id!==0): ?>
  				<div style="display: inline-block;padding: 6px 12px;margin-bottom: 0;font-size: 12px;text-align: center;vertical-align: middle;font-weight:700;background-color:rgb(202,202,202);width:100%"><?= TranslateHelper::t('选择的可递送国家')?></div>
  				<?php if (count($countrys['receivingCountrys'])>0) :?>
  				<div style="display: inline-block;padding: 6px 12px;margin-bottom: 0;font-size: 12px;text-align: left;vertical-align: middle;font-weight:700;background-color:rgb(228,228,228);width:100%"><?= TranslateHelper::t('此前已选择的国家')?></div>
  				<div>
  					<?php foreach ($countrys['receivingCountrys'] as $one){ ?>
					<div style="border-top: 1px solid rgb(202,202,202);padding: 6px 12px;">
						<div>
							<?=Html::checkbox('receiving_region[]',1,['value'=>$one['name'],'label'=>$region[$one['name']].'('.$one['name'].')  '.TranslateHelper::t('全选/全不选'),'class'=>'all-select-had'])?>
							<button type="button" class="btn btn-warning region_toggle" style="padding:0px;font-size:12px;border-radius:0px;"><?=TranslateHelper::t('展开/折叠') ?></button>
						</div>
						<div class="region_country" style="display:none">
							<?=Html::checkboxList('receiving_country','receiving_country[]',$one['value'])?>
						</div>
					</div>
					
					<?php } ?>
  				</div>
  				<?php endif; ?>
  				<?php if ($_GET['tt']=='edit'): ?>
  				<div class="panel-heading" style="display: inline-block;padding: 6px 12px;margin-bottom: 0;font-size: 12px;text-align: left;vertical-align: middle;font-weight:700;background-color:rgb(228,228,228);width:100%"><?= TranslateHelper::t('其他可选择的国家')?></div>
  				<?php endif; ?>
  				<div>
  					<?php foreach ($countrys['sysCountrysNotInReceiving'] as $one){ ?>
					<div style="border-top: 1px solid rgb(202,202,202);padding: 6px 12px;">
						<div>
							<?=Html::checkbox('receiving_region[]',0,['value'=>$one['name'],'label'=>$region[$one['name']].'('.$one['name'].')','class'=>'all-select'])?>
							<button type="button" class="btn btn-warning region_toggle" style="padding:0px;font-size:12px;border-radius:0px;"><?=TranslateHelper::t('展开/折叠') ?></button>
						</div>
						<div class="region_country" style="display:none">
							<?=Html::checkboxList('receiving_country','receiving_country[]',$one['value'])?>
						</div>
					</div>
					
					<?php } ?>
  				</div>
  			<?php endif; ?>
  			</li>

		</ul>
  	</FORM>
	<input id="data_empty_message" type="hidden" value="<?=TranslateHelper::t('无输入数据,请重新输入') ?>">

<div class="slecest_child_photo_dialog"></div>


<script>
<?php //只读的情况下设置readonly 和  disabled
if ( in_array($_GET['tt'], ['view'])):?>
	$('#warehouse_model_form input').prop('readonly','readonly');
	$('#warehouse_model_form textarea').prop('disabled','disabled');
	$('#warehouse_model_form button').prop('disabled','disabled');
	$('#warehouse_model_form .cursor_pointer').css('display','none');
<?php endif;?>
	//
	$('.all-select-had').parent().parent().parent().find("input[name='receiving_country[]']").prop('checked','checked');
	$('.all-select-had').click(function(){
		if($(this).prop('checked')){
			$(this).parent().parent().parent().find("input[name='receiving_country[]']").prop('checked','checked');
		}else{
			$(this).parent().parent().parent().find("input[name='receiving_country[]']").removeAttr('checked');
		}
	});
	//点击checkbox批量选择
	$('.all-select').click(function(){
		if($(this).prop('checked')){
			$(this).parent().parent().parent().find("input[name='receiving_country[]']").prop('checked','checked');
		}else{
			$(this).parent().parent().parent().find("input[name='receiving_country[]']").removeAttr('checked');
		}
	});
	$("#receiving_country_list ul").addClass('list-inline');
	$(".region_toggle").click(function(){
		obj=$(this).parent().parent().find('.region_country').eq(0);
		var hidden = obj.css('display');
		if(typeof(hidden)=='undefined' || hidden=='none'){
			obj.css('display','block');
		}else if( hidden=='block'){
			obj.css('display','none');
		}
	});

	Warehouse.list.initFormValidateInput();
</script>
