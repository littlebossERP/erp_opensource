<?php 
use yii\helpers\Html;
use eagle\modules\util\helpers\TranslateHelper;
use eagle\modules\carrier\models\SysCarrierCustom;
use eagle\modules\carrier\helpers\CarrierHelper;
if (count($carrierObj->address_list)>0){
foreach($carrierObj->address_list as $k){?>
<div class='panel col-md-4'>
<strong>
<?=CarrierHelper::$address_list[$k];?>
</strong>
<hr>
<dl>
  <dt>国家</dt>
  <dd><?= Html::dropDownList('address['.$k."][country]",isset($service['address'][$k]['country'])?$service['address'][$k]['country']:'CN',$countrys,['prompt'=>'','class'=>'eagle-form-control'])?></dd>
  <dt>州/省</dt>
  <dd><input class='eagle-form-control' name="address[<?= $k ?>][province]" type="text" value="<?=$service->address[$k]['province'] ?>"></dd>
  <dt>市</dt>
  <dd><input class='eagle-form-control' name="address[<?= $k ?>][city]" type="text"  value="<?=$service->address[$k]['city'] ?>"></dd>
  <dt>区/县</dt>
  <dd><input class='eagle-form-control' name="address[<?= $k ?>][district]" type="text"  value="<?=$service->address[$k]['district'] ?>"></dd>
  <dt>地址</dt>
  <dd><input class='eagle-form-control' name="address[<?= $k ?>][street]" type="text" value="<?=$service->address[$k]['street'] ?>"></dd>
  <dt>邮编</dt>
  <dd><input class='eagle-form-control' name="address[<?= $k ?>][postcode]" type="number"  value="<?=$service->address[$k]['postcode'] ?>"></dd>
  <dt>公司</dt>
  <dd><input class='eagle-form-control' name="address[<?= $k ?>][company]" type="text" value="<?=$service->address[$k]['company'] ?>"></dd>
  <dt>发件人</dt>
  <dd><input class='eagle-form-control' name="address[<?= $k ?>][contact]" type="text" value="<?=$service->address[$k]['contact'] ?>"></dd>
  <dt>电话</dt>
  <dd><input class='eagle-form-control' name="address[<?= $k ?>][phone]" type="text" value="<?=@$service->address[$k]['phone'] ?>"></dd>
  <dt>手机</dt>
  <dd><input class='eagle-form-control' name="address[<?= $k ?>][mobile]" type="text" value="<?=@$service->address[$k]['mobile'] ?>"></dd>
  <dt>传真</dt>
  <dd><input class='eagle-form-control' name="address[<?= $k ?>][fax]" type="text" value="<?=@$service->address[$k]['fax'] ?>"></dd>
  <dt>邮箱</dt>
  <dd><input class='eagle-form-control' name="address[<?= $k ?>][email]" type="text" value="<?=@$service->address[$k]['email'] ?>"></dd>
</dl>
</div>
<?php }}?>