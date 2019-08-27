<?php 
use yii\helpers\Html;
use eagle\modules\util\helpers\TranslateHelper;
?>
<?php foreach($carrierObj->address_list as $k){?>
<div class='panel col-md-12'>
<strong>
发货人信息
<?//= $address_list[$k];?>
</strong>
<hr>
<dl class="<?=$k?>-area">
  <dt>国家</dt>
  <dd><?= Html::dropDownList($k."[country]",isset($carrierAccountObj['address'][$k]['country'])?$carrierAccountObj['address'][$k]['country']:'CN',$country,['prompt'=>'','class'=>"eagle-form-control {$k}-country"])?></dd>
  <dt>州/省</dt>
  <dd><input class='eagle-form-control <?= $k ?>-province' name="<?= $k ?>[province]" type="text" value="<?=$carrierAccountObj->address[$k]['province'] ?>"></dd>
  <dt>市</dt>
  <dd><input class='eagle-form-control <?= $k ?>-city' name="<?= $k ?>[city]" type="text"  value="<?=$carrierAccountObj->address[$k]['city'] ?>"></dd>
  <dt>区/县</dt>
  <dd><input class='eagle-form-control <?= $k ?>-district' name="<?= $k ?>[district]" type="text"  value="<?=$carrierAccountObj->address[$k]['district'] ?>"></dd>
  <dt>地址</dt>
  <dd><input class='eagle-form-control <?= $k ?>-street' name="<?= $k ?>[street]" type="text" value="<?=$carrierAccountObj->address[$k]['street'] ?>"></dd>
  <dt>邮编</dt>
  <dd><input class='eagle-form-control <?= $k ?>-postcode' name="<?= $k ?>[postcode]" type="number"  value="<?=$carrierAccountObj->address[$k]['postcode'] ?>"></dd>
  <dt>公司</dt>
  <dd><input class='eagle-form-control <?= $k ?>-company' name="<?= $k ?>[company]" type="text" value="<?=$carrierAccountObj->address[$k]['company'] ?>"></dd>
  <dt>发件人</dt>
  <dd><input class='eagle-form-control <?= $k ?>-contact' name="<?= $k ?>[contact]" type="text" value="<?=$carrierAccountObj->address[$k]['contact'] ?>"></dd>
  <dt>电话</dt>
  <dd><input class='eagle-form-control <?= $k ?>-phone' name="<?=$k?>[phone]" type="text" value="<?=@$carrierAccountObj->address[$k]['phone'] ?>"></dd>
  <dt>传真</dt>
  <dd><input class='eagle-form-control <?= $k ?>-fax' name="<?=$k?>[fax]" type="text" value="<?=@$carrierAccountObj->address[$k]['fax'] ?>"></dd>
</dl>
</div>
<?php }?>
