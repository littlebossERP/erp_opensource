<?php 
use yii\helpers\Html;
use eagle\modules\util\helpers\TranslateHelper;

?>
<?php if(isset($carrierObj->address_list) && $carrierObj->address_list){foreach($carrierObj->address_list as $k){?>

<div class='panel col-md-4'>
<strong>
<?php 
	if($k == 'pickupaddress'){
		echo '揽收地址信息';
	}else if($k == 'shippingfrom_en'){
		echo '发货地址信息(英文)';
	}
	?>

<?//= $address_list[$k];?>
</strong>
<hr>
<dl class="<?=$k ?>-area">
  <dt>国家</dt>
  <dd><?= Html::dropDownList($k."[country]",isset($carrierAccountObj->address[$k]['country'])?$carrierAccountObj->address[$k]['country']:'CN',$country,['prompt'=>'','class'=>"eagle-form-control {$k}-country"])?></dd>
  <dt>州/省</dt>
  <dd><input class='eagle-form-control <?= $k ?>-province' name="<?= $k ?>[province]" type="text" value="<?=isset($carrierAccountObj->address[$k]['country'])?$carrierAccountObj->address[$k]['province']:''; ?>"></dd>
  <dt>市</dt>
  <dd><input class='eagle-form-control <?= $k ?>-city' name="<?= $k ?>[city]" type="text"  value="<?=isset($carrierAccountObj->address[$k]['city'])?$carrierAccountObj->address[$k]['city']:''; ?>"></dd>
  <dt>区/县</dt>
  <dd><input class='eagle-form-control <?= $k ?>-district' name="<?= $k ?>[district]" type="text"  value="<?=isset($carrierAccountObj->address[$k]['district'])?$carrierAccountObj->address[$k]['district']:''; ?>"></dd>
  <dt>地址</dt>
  <dd><input class='eagle-form-control <?= $k ?>-street' name="<?= $k ?>[street]" type="text" value="<?=isset($carrierAccountObj->address[$k]['street'])?$carrierAccountObj->address[$k]['street']:''; ?>"></dd>
  <dt>邮编</dt>
  <dd><input class='eagle-form-control <?= $k ?>-postcode' name="<?= $k ?>[postcode]" type="number"  value="<?=isset($carrierAccountObj->address[$k]['postcode'])?$carrierAccountObj->address[$k]['postcode']:''; ?>"></dd>
  <dt>公司</dt>
  <dd><input class='eagle-form-control <?= $k ?>-company' name="<?= $k ?>[company]" type="text" value="<?=isset($carrierAccountObj->address[$k]['company'])?$carrierAccountObj->address[$k]['company']:''; ?>"></dd>
  <dt>发件人</dt>
  <dd><input class='eagle-form-control <?= $k ?>-contact' name="<?= $k ?>[contact]" type="text" value="<?=isset($carrierAccountObj->address[$k]['contact'])?$carrierAccountObj->address[$k]['contact']:''; ?>"></dd>
  <dt>手机</dt>
  <dd><input class='eagle-form-control <?= $k ?>-mobile' name="<?=$k?>[mobile]" type="number" value="<?=isset($carrierAccountObj->address[$k]['mobile'])?$carrierAccountObj->address[$k]['mobile']:''; ?>"></dd>
  <dt>电话</dt>
  <dd><input class='eagle-form-control <?= $k ?>-phone' name="<?=$k?>[phone]" type="text" value="<?=isset($carrierAccountObj->address[$k]['phone'])?$carrierAccountObj->address[$k]['phone']:''; ?>"></dd>
  <dt>传真</dt>
  <dd><input class='eagle-form-control <?= $k ?>-fax' name="<?=$k?>[fax]" type="text" value="<?=isset($carrierAccountObj->address[$k]['fax'])?$carrierAccountObj->address[$k]['fax']:''; ?>"></dd>
  <dt>邮箱</dt>
  <dd><input class='eagle-form-control <?= $k ?>-email' name="<?=$k?>[email]" type="email" value="<?=isset($carrierAccountObj->address[$k]['email'])?$carrierAccountObj->address[$k]['email']:''; ?>"></dd>
  
</dl>
</div>
<?php }}?>
