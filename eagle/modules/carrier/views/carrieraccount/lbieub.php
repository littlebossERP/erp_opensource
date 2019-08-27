<?php 
use yii\helpers\Html;
use eagle\modules\util\helpers\TranslateHelper;
?>
<script src='<?= \Yii::getAlias("@web")."/js/project/carrier/loadParams.js" ?>'></script>
<?php foreach($carrierObj->address_list as $k): ?>
<div class='panel col-md-4'>
	<strong>
<?= $address_list[$k];?>
</strong>
<hr>
<dl class="<?=$k?>-area">
  <dt>联系人姓名（请输入中文地址）</dt>
  <dd><input class='eagle-form-control <?=$k?>-contact' name="<?=$k?>[contact]" type="text" value="<?= @$carrierAccountObj['address'][$k]['contact'] ?>"></dd>
  <dt>公司名称</dt>
  <dd><input class='eagle-form-control <?=$k?>-company' name="<?=$k?>[company]" type="text" value="<?= @$carrierAccountObj['address'][$k]['company'] ?>"></dd>
  <dt>国家地区</dt>
  <dd><?= Html::dropDownList($k.'[country]',isset($carrierAccountObj['address'][$k]['country'])?$carrierAccountObj['address'][$k]['country']:'CN',array('CN'=>'中国','HK'=>'香港','TW'=>'台湾'),['prompt'=>'','class'=>"eagle-form-control {$k}-country"])?></dd>
  <dt>省份</dt>
  <dd><select class='eagle-form-control <?=$k?>-province' name="<?=$k?>[province]" id="<?=$k ?>_selPickUpProvince" onchange="ProvinceChange('<?=$k ?>',this.value)"></select></dd>
  <dt>城市</dt>
  <dd><select class='eagle-form-control <?=$k?>-city' name="<?=$k?>[city]" id="<?=$k ?>_selPickUpCity" onchange="CityChange('<?=$k ?>',this.value)"></select></dd>
  <dt>区/县</dt>
  <dd><select class='eagle-form-control <?=$k?>-district' name="<?=$k?>[district]" id="<?=$k ?>_selPickUpCounty" onchange="CountyChange('<?=$k ?>',this.value)" ></select></dd>
  <dt>街道地址</dt>
  <dd><input class='eagle-form-control <?=$k?>-street' name="<?=$k?>[street]" type="text" value="<?= @$carrierAccountObj['address'][$k]['street'] ?>" style='width:100px;height:21px;'></dd>
  <dt>邮政编码</dt>
  <dd><input class='eagle-form-control <?=$k?>-postcode' name="<?=$k?>[postcode]" type="number" value="<?= @$carrierAccountObj['address'][$k]['postcode'] ?>" ></dd>
  <dt>手机号码</dt>
  <dd><input class='eagle-form-control <?=$k?>-mobile' name="<?=$k?>[mobile]" type="text" value="<?= @$carrierAccountObj['address'][$k]['mobile'] ?>"></dd>
  <dt>电话</dt>
  <dd><input class='eagle-form-control <?=$k?>-phone' name="<?=$k?>[phone]" type="text" value="<?= @$carrierAccountObj['address'][$k]['phone'] ?>"></dd>
  <dt>邮箱</dt>
  <dd><input class='eagle-form-control <?=$k?>-email' name="<?=$k?>[email]" type="email" value="<?= @$carrierAccountObj['address'][$k]['email'] ?>"></dd>
</dl>
<script>Init('<?=$k ?>',"<?= @$carrierAccountObj['address'][$k]['province'] ?>","<?= @$carrierAccountObj['address'][$k]['city'] ?>","<?= @$carrierAccountObj['address'][$k]['district'] ?>");</script>
</div>
<?php endforeach;?>