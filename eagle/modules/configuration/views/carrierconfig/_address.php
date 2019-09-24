<?php 

use yii\helpers\Html;
use yii\helpers\Url;

$this->registerJs("initEditAddressValidateInput();" , \yii\web\View::POS_READY);

$country = $add_data['country'];
$province = $add_data['province'];
$city = $add_data['city'];
$district = $add_data['district'];
$more = $add_data['more'];
?>
<?php $add_data = $address['response']['data'];
		$id = $add_data['id'];
		$carrier_code = $add_data['carrier_code'];
		$type = $add_data['type'];
		$is_default = $add_data['is_default'];
		$address_params = $add_data['address_params'];
// 		print_r($address_params);
		$shippingfrom = @$address_params['shippingfrom'];//发货地址
		$pickupaddress = @$address_params['pickupaddress'];//揽收地址
		$returnaddress = @$address_params['returnaddress'];//回邮地址
		$width = 0;
		$left = 0;
		$has = '000';
		if(isset($shippingfrom)){
			$width += 680;
			$has[0] = 1;
		}
		if(isset($pickupaddress)){
			$width += 315;
			$has[1] = 1;
		}
		if(isset($returnaddress)){
// 			$width += 315;
			$has[2] = 1;
		}
		if($width > 1000){
// 			$left = -150;
		}
		$p0 = isset($shippingfrom['province'])?"'".$shippingfrom['province']."'":"''";
		$p1 = isset($pickupaddress['province'])?"'".$pickupaddress['province']."'":"''";
		$p2 = isset($returnaddress['province'])?"'".$returnaddress['province']."'":"''";
		$p3 = isset($shippingfrom['province_en'])?"'".$shippingfrom['province_en']."'":"''";
		$c0 = isset($shippingfrom['city'])?"'".$shippingfrom['city']."'":"''";
		$c1 = isset($pickupaddress['city'])?"'".$pickupaddress['city']."'":"''";
		$c2 = isset($returnaddress['city'])?"'".$returnaddress['city']."'":"''";
		$c3 = isset($shippingfrom['city_en'])?"'".$shippingfrom['city_en']."'":"''";
		$d0 = isset($shippingfrom['district'])?"'".$shippingfrom['district']."'":"''";
		$d1 = isset($pickupaddress['district'])?"'".$pickupaddress['district']."'":"''";
		$d2 = isset($returnaddress['district'])?"'".$returnaddress['district']."'":"''";
		$d3 = isset($shippingfrom['district_en'])?"'".$shippingfrom['district_en']."'":"''";
				
?>
<style>
	.modal-title{
		width:<?= $width+16?>px;
		position: relative;
		left:<?= $left?>px;
	}
	.modal-content{
		border-color:#797979;
		width:<?= $width?>px;
		left:<?= $left?>px;
	}
	.drop{
		width:250px;
	}
	.modal-body{
		min-height:180px;
		font-size: 13px;
    	color: rgb(51, 51, 51);
	}
	.modal-dialog{
		padding:10px 0;
		font-family: 'Applied Font Regular', 'Applied Font';
	}
	.addressDIVs{
		min-height:100px;
	}
	.shipDIV{
		width:630px;float:left;
	}
	.pickupDIV{
		float:left;width:320px;
	}
	.returnDIV{
		float:left;width:320px;
	}
	.shipDIV label{
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
		font-weight:bold;
	}
	.returnDIV div label{
		margin:0 0 5px 0;
		height:25px;
		line-height:25px;
		text-align:right;
		padding: 0px;
		font-weight: 400;
		color: rgb(51, 51, 51);
 		width:80px;
		white-space:nowrap;
		float:left;
	}
	.returnDIV div input{
		width:200px;
	}
	.pickupDIV div label{
		margin:0 0 5px 0;
		height:25px;
		line-height:25px;
		text-align:right;
		padding: 0px;
		font-weight: 400;
		color: rgb(51, 51, 51);
 		width:80px;
		white-space:nowrap;
		float:left;
	}
	.pickupDIV div input{
		width:200px;
	}
	.clear {
	    clear: both;
	    height: 0px;
	}
	.addressTable{
		line-height:21px;
		margin:0;
		width:100%;
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
	.addressTable_3{
		line-height:21px;
		margin:0;
		width:100%;
		font-size:12px;
		font-family: 'Applied Font Regular', 'Applied Font';
	}
	.addressTable_3 td label{
		margin:0 0 5px 0;
		height:25px;
		line-height:25px;
		text-align:right;
		padding: 0px;
		font-weight: 400;
		color: rgb(51, 51, 51);
 		width:100px;
		float:left;
	}
	.addressTable_3 td input{
		color:black;
	}
	.addressTable_long{
		line-height:21px;
		margin:0;
		width:100%;
		font-size:12px;
		font-family: 'Applied Font Regular', 'Applied Font';
	}
	.addressTable_long td label{
		margin:0 0 5px 0;
		height:25px;
		line-height:25px;
		text-align:right;
		padding: 0px;
		font-weight: 400;
		color: rgb(51, 51, 51);
 		width:100px;
		float:left;
	}
	.addressTable_long td input{
		width:524px;
		color:black;
	}
	.addressTable_long td select{
		height: 26px;
		width:524px;
		color:black;
	}
	.addressTable_3_2{
		line-height:21px;
		margin:0;
		width:100%;
		font-size:12px;
		font-family: 'Applied Font Regular', 'Applied Font';
	}
	.addressTable_3_2 td label{
		margin:0 0 5px 0;
		height:25px;
		line-height:25px;
		text-align:right;
		padding: 0px;
		font-weight: 400;
		color: rgb(51, 51, 51);
		float:left;
	}
	.addressTable_3_2 td .myinput{
		margin:6px 0 0 0;
 		line-height:25px;
		padding: 0px;
	}
	.addressTable_3_2 td input{
		color:black;
	}
	.addressType_div{
		margin:0;
		padding:0 0 0 110px;
		height:25px;
	}
	.impor_red{
		color:#ED5466 ;
		background:#F5F7F7;
		padding:0 3px;
		margin-right:3px;
	}
	.childtoleft>*{
		float:left;
	}
	.rpselect{
		width: 198px;
	    float: left;
	    height: 25px;
	    line-height: 25px;
	}
</style>

<script>
	/*0=>'shippingfrom',1=>'pickupaddress',2=>'returnaddress',3=>'en'*/
	$province_all = <?= json_encode(@$province)?>;
	$city_all = <?= json_encode(@$city)?>;
	$dis_all = <?= json_encode(@$district)?>;
	$p0 = <?= $p0?>;$p1 = <?= $p1?>;$p2 = <?= $p2?>;$p3 = <?= $p3?>;
	$c0 = <?= $c0?>;$c1 = <?= $c1?>;$c2 = <?= $c2?>;$c3 = <?= $c3?>;
	$d0 = <?= $d0?>;$d1 = <?= $d1?>;$d2 = <?= $d2?>;$d3 = <?= $d3?>;
</script>
<div class="modal-body" style="width:<?= $width-14?>px;">
	<form action='' method="post" id="address_form">
	<?= Html::hiddenInput('id',@$id)?>
	<?= Html::hiddenInput('carrier_code',@$carrier_code)?>
	
	<div class="addressType_div">
		<?= Html::dropDownList('list',null,['prompt'=>'加载常用揽收/发货地址']+$add_List['response']['data']['list'],['id'=>'commonList','data'=>$has,'class'=>' iv-input'])?>
    </div>
    <?php //if(isset($shippingfrom)) {?>
    <div class="addressDIVs">
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
					<td><label ><span class="impor_red">*</span>电话:</label><?= Html::input('text','shippingfrom[phone]',@$shippingfrom['phone'],['class'=>' iv-input'])?></td>
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
				<?php
					if(isset($address['response']['data']['carrier_code'])){
						if($address['response']['data']['carrier_code'] == 'lb_dhlexpress'){
?>
				<tr>
					<table class="addressTable_long"><td><label style="white-space:nowrap;"><span class="impor_red">*</span>街道(英文)2:</label><?= Html::input('text','shippingfrom[street_en2]',@$shippingfrom['street_en2'],['class'=>'addreq iv-input'])?></td></table>
				</tr>
				<tr>
					<table class="addressTable_long"><td><label style="white-space:nowrap;">街道(英文)3:</label><?= Html::input('text','shippingfrom[street_en3]',@$shippingfrom['street_en3'],['class'=>'iv-input'])?></td></table>
				</tr>
<?php 
						}else if($address['response']['data']['carrier_code'] == 'lb_dhl'){
?>
				<tr>
					<table class="addressTable_long"><td><label style="white-space:nowrap;"><span class="impor_red">*</span>街道(英文)2:</label><?= Html::input('text','shippingfrom[street_en2]',@$shippingfrom['street_en2'],['class'=>'addreq iv-input'])?></td></table>
				</tr>
<?php
						}
					}
				?>
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
							<td><div id="set_default_address" class="hidden"><label style="">地址名称：</label><input style="width:180px;" id="address_name" class="iv-input" name="address_name" type="text" value="<?= $add_data['address_name']?>"></div></td>
						</tr>
					</table>
				</tr>
			</table>
    	</div>
    	<?php //}?>
    	<?php if(isset($pickupaddress)) {?>
    	<div class="pickupDIV">
    		<div class="col-xs-12"><label style="font-weight: bold;">揽收地址</label></div>
    		<div class="col-xs-12"><label ><span class="impor_red">*</span>联系人:</label><?= Html::input('text','pickupaddress[contact]',@$pickupaddress['contact'],['class'=>'addreq iv-input'])?></div>
    		<div class="col-xs-12"><label >公司:</label><?= Html::input('text','pickupaddress[company]',@$pickupaddress['company'],['class'=>' iv-input'])?></div>
    		<div class="col-xs-12"><label ><span class="impor_red">*</span>手机:</label><?= Html::input('text','pickupaddress[mobile]',@$pickupaddress['mobile'],['class'=>'iv-input'])?></div>
    		<div class="col-xs-12"><label ><span class="impor_red">*</span>电话:</label><?= Html::input('text','pickupaddress[phone]',@$pickupaddress['phone'],['class'=>' iv-input'])?></div>
    		<div class="col-xs-12"><label ><span class="impor_red">*</span>邮箱:</label><?= Html::input('text','pickupaddress[email]',@$pickupaddress['email'],['class'=>'addreq iv-input'])?></div>
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
    	<?php if(isset($returnaddress)){?>
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
    </div>
	<div class="clear"></div>
	</form>
</div>
<div class="modal-footer">
	<button type="button" class="iv-btn btn-primary" onclick="SaveAddress(<?= "'".$id."','".$carrier_code."','".$type."'" ?>)" >保存</button>
	<button class="iv-btn btn-default btn-sm modal-close">关 闭</button>
</div>

	<script>
		$(function() {
			bind_all();
			$('#commonList').change(function(){
				$id = $(this).val();
				if($id != 'prompt'){
					$myHas = $(this).attr('data');
					$codes = $('input[name=carrier_code]').val();
					if($id.trim() != ''){
						var Url=global.baseUrl +'configuration/carrierconfig/getcommonaddress';
						$.ajax({
					        type : 'post',
					        cache : 'false',
					        data : {
						        id:$id,
						        myHas:$myHas,
						        codes:$codes,
					        },
							url: Url,
					        success:function(response) {
					        	$('.addressDIVs').html(response);
					        }
					    });
					}
				}
				$(this).val('prompt');
			});
		});
	</script>