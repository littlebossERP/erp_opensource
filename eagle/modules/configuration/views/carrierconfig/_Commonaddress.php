<?php 
use yii\helpers\Html;
use yii\helpers\Url;

$country = $add_data['country'];
$province = $add_data['province'];
$city = $add_data['city'];
$district = $add_data['district'];
$more = $add_data['more'];

$country_load = $add_data_load['country'];
$province_load = $add_data_load['province'];
$city_load = $add_data_load['city'];
$district_load = $add_data_load['district'];

		$address_datas = $address['response']['data'];
		$id = $address_datas['id'];
		$carrier_code = $address_datas['carrier_code'];
		$type = $address_datas['type'];
		$is_default = $address_datas['is_default'];
		$address_params = $address_datas['address_params'];
// 		print_r($address_params);
		$shippingfrom = @$address_params['shippingfrom'];//发货地址
		$pickupaddress = @$address_params['pickupaddress'];//揽收地址
		$returnaddress = @$address_params['returnaddress'];//回邮地址
		$width = 0;
		if($myHas[0] == 1) $width += 680;
		if($myHas[1] == 1) $width += 315;
		if($myHas[2] == 1) $width += 315;
		
		$country_zh = array('0'=>'','1'=>'','2'=>'');
		$pro = array('0'=>'','1'=>'','2'=>'','3'=>'');
		$cit = array('0'=>'','1'=>'','2'=>'','3'=>'');
		$dis = array('0'=>'','1'=>'','2'=>'','3'=>'');
		$p[0] = $shippingfrom;$p[1] = $pickupaddress;$p[2] = $returnaddress;
		for($i = 0; $i < 3; $i++){
			//国家名
			$country_zh[$i] = $p[$i]['country'];//isset($country_load[$p[$i]['country']])?$country_load[$p[$i]['country']]:
			//省名
// 			print_r($province_load[$i]);
			if(!empty($province_load[$i]) && !empty($p[$i]['province'])){
				$pro[$i] = @$province_load[$i][$p[$i]['province']];
				//市名
				if(!empty($city_load[$i][$p[$i]['province']]) && !empty($p[$i]['city'])){
					$cit[$i] = @$city_load[$i][$p[$i]['province']][$p[$i]['city']];
					//区名
					if(!empty($district_load[$i][$p[$i]['city']]) && !empty($p[$i]['district'])) $dis[$i] = @$district_load[$i][$p[$i]['city']][$p[$i]['district']];
					else if(!empty($p[$i]['district'])) $dis[$i] = $p[$i]['district'];
				}
				else if(!empty($p[$i]['city'])) $cit[$i] = $p[$i]['city'];
			}
			else if(!empty($p[$i]['province'])) $pro[$i] = $p[$i]['province'];
		}

		$p0 = $shippingfrom['province'] = (!empty($pro[0]))?$pro[0]:(isset($shippingfrom['province'])?$shippingfrom['province']:"");
		$p1 = $pickupaddress['province'] = (!empty($pro[1]))?$pro[1]:(isset($pickupaddress['province'])?$pickupaddress['province']:"");
		$p2 = $returnaddress['province'] = (!empty($pro[2]))?$pro[2]:(isset($returnaddress['province'])?$returnaddress['province']:"");
		$p3 = $shippingfrom['province_en'] = (!empty($pro[3]))?$pro[3]:(isset($shippingfrom['province_en'])?$shippingfrom['province_en']:"");
		
		$c0 = $shippingfrom['city'] = (!empty($cit[0]))?$cit[0]:(isset($shippingfrom['city'])?$shippingfrom['city']:"");
		$c1 = $pickupaddress['city'] = (!empty($cit[1]))?$cit[1]:(isset($pickupaddress['city'])?$pickupaddress['city']:"");
		$c2 = $returnaddress['city'] = (!empty($cit[2]))?$cit[2]:(isset($returnaddress['city'])?$returnaddress['city']:"");
		$c3 = $shippingfrom['city_en'] = (!empty($cit[3]))?$cit[3]:(isset($shippingfrom['city_en'])?$shippingfrom['city_en']:"");
		
		$d0 = $shippingfrom['district'] = (!empty($dis[0]))?$dis[0]:(isset($shippingfrom['district'])?$shippingfrom['district']:"");
		$d1 = $pickupaddress['district'] = (!empty($dis[1]))?$dis[1]:(isset($pickupaddress['district'])?$pickupaddress['district']:"");
		$d2 = $returnaddress['district'] = (!empty($dis[2]))?$dis[2]:(isset($returnaddress['district'])?$returnaddress['district']:"");
		$d3 = $shippingfrom['district_en'] = (!empty($dis[3]))?$dis[3]:(isset($shippingfrom['district_en'])?$shippingfrom['district_en']:"");

?>
<script>
	/*0=>'shippingfrom',1=>'pickupaddress',2=>'returnaddress'*/
	$province_all = <?= json_encode(@$province)?>;
	$city_all = <?= json_encode(@$city)?>;
	$dis_all = <?= json_encode(@$district)?>;
	$p0 = <?= "'".$p0."'"?>;$p1 = <?= "'".$p1."'"?>;$p2 = <?= "'".$p2."'"?>;$p3 = <?= "'".$p3."'"?>;
	$c0 = <?= "'".$c0."'"?>;$c1 = <?= "'".$c1."'"?>;$c2 = <?= "'".$c2."'"?>;$c3 = <?= "'".$c3."'"?>;
	$d0 = <?= "'".$d0."'"?>;$d1 = <?= "'".$d1."'"?>;$d2 = <?= "'".$d2."'"?>;$d3 = <?= "'".$d3."'"?>;
	
	$(function() {
		bind_all();
	});
</script>
		<?php if($myHas[0] == 1) {?>
		<div class="shipDIV">
    		<label>发货地址</label>
    		<table class="addressTable">
				<tr>
					<td><label ><span class="impor_red">*</span>联系人:</label><?= Html::input('text','shippingfrom[contact]',@$shippingfrom['contact'],['class'=>'addreq iv-input'])?></td>
					<td><label ><span class="impor_red">*</span>联系人(英文)</label><?= Html::input('text','shippingfrom[contact_en]',@$shippingfrom['contact_en'],['class'=>'addreq iv-input'])?></td>
				</tr>
				<tr>
					<td><label >公司:</label><?= Html::input('text','shippingfrom[company]',@$shippingfrom['company'],['class'=>' iv-input'])?></td>
					<td><label >公司(英文)</label><?= Html::input('text','shippingfrom[company_en]',@$shippingfrom['company_en'],['class'=>' iv-input'])?></td>
				</tr>
				<tr>
					<td><label >电话:</label><?= Html::input('text','shippingfrom[phone]',@$shippingfrom['phone'],['class'=>' iv-input'])?></td>
					<td><label ><span class="impor_red">*</span>手机:</label><?= Html::input('text','shippingfrom[mobile]',@$shippingfrom['mobile'],['class'=>'addreq iv-input'])?></td>
				</tr>
				<tr>
					<td><label >传真:</label><?= Html::input('text','shippingfrom[fax]',@$shippingfrom['fax'],['class'=>' iv-input'])?></td>
					<td><label ><span class="impor_red">*</span>邮箱:</label><?= Html::input('text','shippingfrom[email]',@$shippingfrom['email'],['class'=>'addreq iv-input'])?></td>
				</tr>
				<tr>
					<td class="childtoleft">
						<label style="width:100px;" ><span class="impor_red">*</span>国家:</label>
							<?= Html::dropDownList('shippingfrom[country]',isset($shippingfrom['country'])?$shippingfrom['country']:'CN',$country['0'],['id'=>'country','style'=>'width:66px;float:left;height:25px;line-height:25px;','class'=>'addreq'])?>
						<label style="width:59px;" ><span class="impor_red">*</span>州/省:</label>
						<?php 
							if(empty($province[0])){
								echo Html::input('text','shippingfrom[province]',@$shippingfrom['province'],['style'=>'width:84px;','class'=>'addreq iv-input']);
							}else {?>
								<select id="shippingfrom_province" name="shippingfrom[province]" style="width:80px;height:25px;line-height:25px;" class="addreq" onchange="procinceChange(0)"></select>
						<?php }?>
					</td>
					<td><label><span class="impor_red">*</span>州/省(英文):</label>
						<?php 
							if(empty($province[3])){
								echo Html::input('text','shippingfrom[province_en]',@$shippingfrom['province_en'],['class'=>'addreq iv-input']);
							}else {?>
								<select id="shippingfrom_province_en" name="shippingfrom[province_en]" style="width:207px;height:25px;line-height:25px;position:relative;left:-3px;" class="addreq" onchange="procinceChange(3)"></select>
						<?php }?>
					</td>
				</tr>
				<tr>
					<td><label ><span class="impor_red">*</span>市:</label>
						<?php 
							if(empty($city[0])){
								echo Html::input('text','shippingfrom[city]',@$shippingfrom['city'],['class'=>'addreq iv-input']);
							}else {?>
								<select id="shippingfrom_city" onchange="cityChange(0)" name="shippingfrom[city]" style="width:209px;height:25px;line-height:25px;" class="addreq"></select>
						<?php }?>
					</td>
					<td><label ><span class="impor_red">*</span>市(英文):</label>
						<?php 
							if(empty($city[3])){
								echo Html::input('text','shippingfrom[city_en]',@$shippingfrom['city_en'],['class'=>'addreq iv-input']);
							}else {?>
								<select id="shippingfrom_city_en" onchange="cityChange(3)" name="shippingfrom[city_en]" style="width:209px;height:25px;line-height:25px;" class="addreq"></select>
						<?php }?>
					</td>
				</tr>
				<tr>
					<table class="addressTable_3">
						<tr>
							<td style="width:290px;"><label><span class="impor_red">*</span>区/县/镇:</label>
								<?php 
									if(empty($district[0])){
										echo Html::input('text','shippingfrom[district]',@$shippingfrom['district'],['style'=>'width:160px;','class'=>'addreq iv-input']);
									}else {?>
										<select id="shippingfrom_district" name="shippingfrom[district]" style="width:160px;height:25px;line-height:25px;" class="addreq"></select>
								<?php }?>
							</td>
							<td style="width:250px;"><label	style="width:105px;padding:0;float:left;white-space:nowrap;"><span class="impor_red">*</span>区/县/镇(英文):</label>
								<?php 
									if(empty($district[3])){
										echo Html::input('text','shippingfrom[district_en]',@$shippingfrom['district_en'],['style'=>'width:120px;','class'=>'addreq iv-input']);
									}else {?>
										<select id="shippingfrom_district_en" name="shippingfrom[district_en]" style="width:120px;height:25px;line-height:25px;" class="addreq"></select>
								<?php }?>
							</td>
							<td style="width:141px;"><label style="width:46px;white-space:nowrap;"><span class="impor_red">*</span>邮编:</label><?= Html::input('text','shippingfrom[postcode]',@$shippingfrom['postcode'],['style'=>'width:80px;','class'=>'addreq iv-input'])?></td>
						</tr>
					</table>
				</tr>
				<tr>
					<table class="addressTable_long"><td><label style="white-space:nowrap;"><span class="impor_red">*</span>街道:</label><?= Html::input('text','shippingfrom[street]',@$shippingfrom['street'],['class'=>'addreq iv-input'])?></td></table>
				</tr>
				<tr>
					<table class="addressTable_long"><td><label style="white-space:nowrap;"><span class="impor_red">*</span>街道(英文):</label><?= Html::input('text','shippingfrom[street_en]',@$shippingfrom['street_en'],['class'=>'addreq iv-input'])?></td></table>
				</tr>
				<table class="addressTable_long">
				<?php foreach ($more['shippingfrom'] as $mShip){?>
				<tr>
					<td>
					<label><?php if($mShip['req']){?><span class="impor_red">*</span><?php }?><?= @$mShip['label']?>:</label>
					<?php if($mShip['type'] == 'text'){?>
					<input type="text" class="<?= ($mShip['req'])?'?>addreq':''?> iv-input" name="shippingfrom[<?= $mShip['name']?>]" value="<?= @$shippingfrom[$mShip['name']]?>">
					<?php }else if($mShip['type'] == 'dropdownlist'){
						echo Html::dropDownList("shippingfrom[".$mShip['name']."]",@$shippingfrom[$mShip['name']],$mShip['list'],['class'=>' '.(($mShip['req'])?'?>addreq':'')]);
					}?>
					</td>
				</tr>
				<?php }?>
				</table>
				<tr>
					<table class="addressTable_3_2">
						<tr>
							<td style="width:200px;">
								<?= Html::checkbox('is_default',@$is_default,['label'=>'默认地址','style'=>' margin-left:100px;'])?>
							</td>
							<td style="width:110px;">
								<?= Html::checkbox('isSaveCommonAddress',false,['label'=>'保存为常用地址','id'=>'isSaveCommonAddress','onclick'=>'isSaveCommonAddressChange(this)'])?>
		
							</td>
							<td><div id="set_default_address" class="hidden"><label style="">地址名称：</label><input style="width:180px;" id="address_name" class="iv-input" name="address_name" type="text" value=""></div></td>
						</tr>
					</table>
				</tr>
			</table>
    	</div>
    	<?php }?>
    	<?php if($myHas[1] == 1) {?>
    	<div class="pickupDIV">
    		<div class="col-xs-12"><label style="font-weight: bold;">揽收地址</label></div>
    		<div class="col-xs-12"><label ><span class="impor_red">*</span>联系人:</label><?= Html::input('text','pickupaddress[contact]',@$pickupaddress['contact'],['class'=>'addreq iv-input'])?></div>
    		<div class="col-xs-12"><label >公司:</label><?= Html::input('text','pickupaddress[company]',@$pickupaddress['company'],['class'=>' iv-input'])?></div>
    		<div class="col-xs-12"><label >手机:</label><?= Html::input('text','pickupaddress[mobile]',@$pickupaddress['mobile'],['class'=>'iv-input'])?></div>
    		<div class="col-xs-12"><label >电话:</label><?= Html::input('text','pickupaddress[phone]',@$pickupaddress['phone'],['class'=>' iv-input'])?></div>
    		<div class="col-xs-12"><label >邮箱:</label><?= Html::input('text','pickupaddress[email]',@$pickupaddress['email'],['class'=>'iv-input'])?></div>
    		<div class="col-xs-12"><label >传真:</label><?= Html::input('text','pickupaddress[fax]',@$pickupaddress['fax'],['class'=>' iv-input'])?></div>
    		
    		<div class="col-xs-12 childtoleft">
    			<label ><span class="impor_red">*</span>国家:</label>
				<?= Html::dropDownList('pickupaddress[country]',isset($pickupaddress['country'])?$pickupaddress['country']:'CN',$country['1'],['id'=>'country','style'=>'width:66px;float:left;height:25px;line-height:25px;','class'=>'addreq'])?>
				<label style="width:59px;" ><span class="impor_red">*</span>州/省:</label>
				<?php  if(empty($province[1])){
						echo Html::input('text','pickupaddress[province]',@$pickupaddress['province'],['style'=>'width:75px;','class'=>'addreq iv-input']);
						}else {?>
							<select id="pickupaddress_province" name="pickupaddress[province]" style="width:71px;float:left;height:25px;line-height:25px;" class="addreq" onchange="procinceChange(1)"></select>
						<?php }?>
    		</div>
    		<div class="col-xs-12"><label ><span class="impor_red">*</span>市:</label>
    		<?php  if(empty($city[1])){
					echo Html::input('text','pickupaddress[city]',@$pickupaddress['city'],['class'=>'addreq iv-input']);
					}else {?>
						<select id="pickupaddress_city" name="pickupaddress[city]" style="width:198px;float:left;height:25px;line-height:25px;" class="addreq" onchange="cityChange(1)"></select>
					<?php }?>
    		</div>
    		<div class="col-xs-12"><label ><span class="impor_red">*</span>区/县/镇:</label>
    		<?php  if(empty($district[1])){
					echo Html::input('text','pickupaddress[district]',@$pickupaddress['district'],['class'=>'addreq iv-input']);
					}else {?>
						<select id="pickupaddress_district" name="pickupaddress[district]" style="width:198px;float:left;height:25px;line-height:25px;" class="addreq"></select>
					<?php }?>
    		</div>
    		<div class="col-xs-12"><label ><span class="impor_red">*</span>街道:</label><?= Html::input('text','pickupaddress[street]',@$pickupaddress['street'],['class'=>'addreq iv-input'])?></div>
    		<div class="col-xs-12"><label ><span class="impor_red">*</span>邮编:</label><?= Html::input('text','pickupaddress[postcode]',@$pickupaddress['postcode'],['class'=>'addreq iv-input'])?></div>
    		<?php foreach ($more['pickupaddress'] as $mShip){?>
			<div class="col-xs-12">
				<label><?php if($mShip['req']){?><span class="impor_red">*</span><?php }?><?= @$mShip['label']?>:</label>
				<?php if($mShip['type'] == 'text'){?>
				<input type="text" class="<?= ($mShip['req'])?'?>addreq':''?> iv-input" name="pickupaddress[<?= $mShip['name']?>]" value="<?= @$pickupaddress[$mShip['name']]?>">
				<?php }else if($mShip['type'] == 'dropdownlist'){
						echo Html::dropDownList("pickupaddress[".$mShip['name']."]",@$pickupaddress[$mShip['name']],@$mShip['list'],['class'=>'rpselect '.(($mShip['req'])?'?>addreq':'')]);
					}?>
			</div>
			<?php }?>
    	</div>
    	<?php }?>
    	<?php if($myHas[2] == 1){?>
    	<div class="returnDIV">
    		<div class="col-xs-12"><label style="font-weight: bold;">退货地址</label></div>
    		<div class="col-xs-12"><label ><span class="impor_red">*</span>联系人:</label><?= Html::input('text','returnaddress[contact]',@$returnaddress['contact'],['class'=>'addreq iv-input'])?></div>
    		<div class="col-xs-12"><label >公司:</label><?= Html::input('text','returnaddress[company]',@$returnaddress['company'],['class'=>' iv-input'])?></div>
    		<div class="col-xs-12"><label >手机:</label><?= Html::input('text','returnaddress[mobile]',@$returnaddress['mobile'],['class'=>'iv-input'])?></div>
    		<div class="col-xs-12"><label >电话:</label><?= Html::input('text','returnaddress[phone]',@$returnaddress['phone'],['class'=>' iv-input'])?></div>
    		<div class="col-xs-12"><label >邮箱:</label><?= Html::input('text','returnaddress[email]',@$returnaddress['email'],['class'=>'iv-input'])?></div>
    		<div class="col-xs-12"><label >传真:</label><?= Html::input('text','returnaddress[fax]',@$returnaddress['fax'],['class'=>' iv-input'])?></div>
    		
    		<div class="col-xs-12 childtoleft">
    			<label ><span class="impor_red">*</span>国家:</label>
				<?= Html::dropDownList('returnaddress[country]',isset($returnaddress['country'])?$returnaddress['country']:'CN',$country['2'],['id'=>'country','style'=>'width:66px;float:left;height:25px;line-height:25px;','class'=>'addreq'])?>
				<label style="width:59px;" ><span class="impor_red">*</span>州/省:</label>
				<?php  if(empty($province[2])){
						echo Html::input('text','returnaddress[province]',@$returnaddress['province'],['style'=>'width:75px;','class'=>'addreq iv-input']);
						}else {?>
							<select id="returnaddress_province" name="returnaddress[province]" style="width:71px;height:25px;line-height:25px;" class="addreq"></select>
						<?php }?>
    		</div>
    		<div class="col-xs-12"><label ><span class="impor_red">*</span>市:</label>
    		<?php  if(empty($city[2])){
					echo Html::input('text','returnaddress[city]',@$returnaddress['city'],['class'=>'addreq iv-input']);
					}else {?>
						<select id="returnaddress_city" name="returnaddress[city]" style="width:198px;float:left;height:25px;line-height:25px;" class="addreq" onchange="cityChange(2)"></select>
					<?php }?>
			</div>
    		<div class="col-xs-12"><label ><span class="impor_red">*</span>区/县/镇:</label>
    		<?php  if(empty($district[2])){
					echo Html::input('text','returnaddress[district]',@$returnaddress['district'],['class'=>'addreq iv-input']);
					}else {?>
						<select id="returnaddress_district" name="returnaddress[district]" style="width:198px;float:left;height:25px;line-height:25px;" class="addreq"></select>
					<?php }?>
    		</div>
    		<div class="col-xs-12"><label ><span class="impor_red">*</span>街道:</label><?= Html::input('text','returnaddress[street]',@$returnaddress['street'],['class'=>'addreq iv-input'])?></div>
    		<div class="col-xs-12"><label ><span class="impor_red">*</span>邮编:</label><?= Html::input('text','returnaddress[postcode]',@$returnaddress['postcode'],['class'=>'addreq iv-input'])?></div>
    		<?php foreach ($more['returnaddress'] as $mShip){?>
			<div class="col-xs-12">
				<label><?php if($mShip['req']){?><span class="impor_red">*</span><?php }?><?= @$mShip['label']?>:</label>
				<?php if($mShip['type'] == 'text'){?>
				<input type="text" class="<?= ($mShip['req'])?'?>addreq':''?> iv-input" name="returnaddress[<?= $mShip['name']?>]" value="<?= @$returnaddress[$mShip['name']]?>">
				<?php }else if($mShip['type'] == 'dropdownlist'){
						echo Html::dropDownList("returnaddress[".$mShip['name']."]",@$returnaddress[$mShip['name']],@$mShip['list'],['class'=>'rpselect '.(($mShip['req'])?'?>addreq':'')]);
					}?>
			</div>
			<?php }?>
    	</div>
    	<?php }?>