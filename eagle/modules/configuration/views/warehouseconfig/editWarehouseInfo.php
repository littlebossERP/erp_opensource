<?php 
use yii\helpers\Html;
use yii\helpers\Url;
?>

<style>
	.addressTable{
		line-height:21px;
		margin:0;
		margin-top: 15px;
		font-size:12px;
		font-family: 'Applied Font Regular', 'Applied Font';
	}
	.addressTable td input{
		width:209px;
		color:black;
	}
	.addressTable td label{
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
	.impor_red{
		color:#ED5466 ;
		background:#F5F7F7;
		padding:0 3px;
		margin-right:3px;
	}
</style>

<form id='address_form'>
	<table class="addressTable" style='margin-bottom: 10px;'>
	<tr>
		<td>
			<label ><span class="impor_red">*</span>仓库名:</label><?=Html::textInput('warehouse_name',@$warehouseOneInfo['name'],['class'=>'iv-input','style'=>'','id'=>'warehouse_name'])?>
		</td>
		<td><label ><span class="impor_red">*</span>使用状态:</label>
			<input style='width:30px;height:25;' type="radio" id='warehouse_activeY' name="is_active" value="Y" <?=@$warehouseOneInfo['is_active'] == 'Y' ? 'checked' : '' ?>>
			<label style='float:none;width:30px;text-align:left;' for="warehouse_activeY">开启</label>
			<input style='width:30px;height:25;' type="radio" id='warehouse_activeN' name="is_active" value="N" <?=@$warehouseOneInfo['is_active'] == 'N' ? 'checked' : '' ?>>
			<label style='float:none;width:30px;text-align:left;' for="warehouse_activeN">关闭</label>
		</td>
	</tr>
	<tr style="display: none; ">
		<td><label ><span class="impor_red">*</span>是否支持负库存:</label>
			<input style='width:30px;height:25;' type="radio" id='warehouse_zeroY' name="warehouse_zero" value="1" <?=@$warehouseOneInfo['is_zero_inventory'] == '1' ? 'checked' : '' ?>>
			<label style='float:none;width:30px;text-align:left;' for="warehouse_zeroY">支持</label>
			<input style='width:30px;height:25;' type="radio" id='warehouse_zeroN' name="warehouse_zero" value="0" <?=@$warehouseOneInfo['is_zero_inventory'] == '0' ? 'checked' : '' ?>>
			<label style='float:none;width:30px;text-align:left;' for="warehouse_zeroN">不支持</label>
		</td>
	</tr>
	</table>
	
	
	<h4>仓库地址</h4>
	<hr style='margin-top: 5px;'>
	<?=Html::hiddenInput('warehouse_id',@$warehouseOneInfo['warehouse_id'],['id'=>'warehouse_id']) ?>
	
	
	
	<table class="addressTable">
		<tr>
			<td><label ><span class="impor_red">*</span>联系人:</label><?= Html::input('text','address_params[contact]',@$warehouseOneInfo['address_params']['contact'],['class'=>'iv-input'])?></td>
			<td><label ><span class="impor_red">*</span>联系人(英文)</label><?= Html::input('text','address_params[contact_en]',@$warehouseOneInfo['address_params']['contact_en'],['class'=>'iv-input'])?></td>
		</tr>
		<tr>
			<td><label >公司:</label><?= Html::input('text','address_params[company]',@$warehouseOneInfo['address_params']['company'],['class'=>' iv-input'])?></td>
			<td><label >公司(英文)</label><?= Html::input('text','address_params[company_en]',@$warehouseOneInfo['address_params']['company_en'],['class'=>' iv-input'])?></td>
		</tr>
		<tr>
			<td><label >电话:</label><?= Html::input('text','address_phone',@$warehouseOneInfo['address_phone'],['class'=>' iv-input'])?></td>
			<td><label ><span class="impor_red">*</span>手机:</label><?= Html::input('text','address_params[mobile]',@$warehouseOneInfo['address_params']['mobile'],['class'=>'iv-input'])?></td>
		</tr>
		<tr>
			<td><label >传真:</label><?= Html::input('text','address_params[fax]',@$warehouseOneInfo['address_params']['fax'],['class'=>' iv-input'])?></td>
			<td><label ><span class="impor_red">*</span>邮箱:</label><?= Html::input('text','address_params[email]',@$warehouseOneInfo['address_params']['email'],['class'=>'iv-input'])?></td>
		</tr>
		<tr>
			<td class="childtoleft">
				<label style="width:100px;" ><span class="impor_red">*</span>国家:</label>
				
				<select id="country" name="address_nation" style="width:66px;float:left;height:25px;line-height:25px;">
				<?php 
					foreach ($countryComboBox as $name=>$code){
						$selected='';
						if((empty($warehouseOneInfo['address_nation']) or $warehouseOneInfo['address_nation']=='CN') and ($code=='CN' and $name=='中国') )
							$selected=' selected';
						if(!empty($warehouseOneInfo['address_nation']) and $code==$warehouseOneInfo['address_nation']){
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
				<label style="width:59px;" ><span class="impor_red">*</span>州/省:</label>
				<?=Html::input('text','address_state',@$warehouseOneInfo['address_state'],['style'=>'width:84px;','class'=>'iv-input']);?>
			</td>
			<td><label><span class="impor_red">*</span>州/省(英文):</label>
				<?=Html::input('text','address_params[province_en]',@$warehouseOneInfo['address_params']['province_en'],['class'=>'iv-input']);?>
			</td>
		</tr>
		<tr>
			<td><label ><span class="impor_red">*</span>市:</label>
				<?=Html::input('text','address_city',@$warehouseOneInfo['address_city'],['class'=>'iv-input']);?>
			</td>
			<td><label ><span class="impor_red">*</span>市(英文):</label>
				<?=Html::input('text','address_params[city_en]',@$warehouseOneInfo['address_params']['city_en'],['class'=>'iv-input']);?>
			</td>
		</tr>
		<tr>
			<td colspan=2>
				<div style='display:inline-block;'>
					<label><span class="impor_red">*</span>区/县/镇:</label>
					<?=Html::input('text','address_params[district]',@$warehouseOneInfo['address_params']['district'],['style'=>'width:160px;','class'=>'iv-input']);?>
				</div>
				<div style='display:inline-block;'>
					<label style="width:105px;padding:0;float:left;white-space:nowrap;"><span class="impor_red">*</span>区/县/镇(英文):</label>
					<?=Html::input('text','address_params[district_en]',@$warehouseOneInfo['address_params']['district_en'],['style'=>'width:120px;','class'=>'iv-input']);?>
				</div>
				<div style='display:inline-block;'>
					<label style="width:46px;white-space:nowrap;"><span class="impor_red">*</span>邮编:</label><?= Html::input('text','address_postcode',@$warehouseOneInfo['address_postcode'],['style'=>'width:80px;','class'=>'iv-input'])?>
				</div>
			</td>
		</tr>
		<tr>
			<td colspan=2><label style="white-space:nowrap;"><span class="impor_red">*</span>街道:</label><?= Html::input('text','address_street',@$warehouseOneInfo['address_street'],['class'=>'iv-input','style'=>'width:524px;'])?></td>
		</tr>
		<tr>
			<td colspan=2><label style="white-space:nowrap;"><span class="impor_red">*</span>街道(英文):</label><?= Html::input('text','address_params[street_en]',@$warehouseOneInfo['address_params']['street_en'],['class'=>'iv-input','style'=>'width:524px;'])?></td>
		</tr>
		<tr>
			<td colspan=2><label style="white-space:nowrap;">备注:</label><?= Html::input('text','comment',@$warehouseOneInfo['comment'],['class'=>'iv-input','style'=>'width:524px;'])?></td>
		</tr>
	</table>
</form>

<div class="modal-footer col-xs-12">
	<button type="button" class="btn btn-primary" onclick="editWarehouseInfo()">保存</button>
	<button class="iv-btn btn-default btn-sm modal-close">关 闭</button>
</div>
