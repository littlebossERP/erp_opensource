<?php 
use yii\helpers\Html;
use eagle\modules\util\helpers\TranslateHelper;
?>
<?php if(isset($carrierObj->address_list) && $carrierObj->address_list){foreach($carrierObj->address_list as $k){?>
<div class='panel col-md-12'>
<strong>
发货人信息 (<span class="msg">*</span>为必填)
<?//= $address_list[$k];?>
</strong>
<hr>
<style>
.msg {
  color: red;
  padding: 0 5px;
}
</style>
<dl>
  <dt>发件人姓名</dt>
  <dd class="form-group"><input class='eagle-form-control' required name="<?= $k ?>[contact]" type="text" value="<?=isset($carrierAccountObj->address[$k]['contact'])?$carrierAccountObj->address[$k]['contact']:''; ?>"><span class="msg">*</span></dd>
  <dt>公司</dt>
  <dd><input class='eagle-form-control' name="<?= $k ?>[company]" type="text" value="<?=isset($carrierAccountObj->address[$k]['company'])?$carrierAccountObj->address[$k]['company']:''; ?>"></dd>
  <dt>国家</dt>
  <dd><?= Html::dropDownList($k."[country]",isset($carrierAccountObj->address[$k]['country'])?$carrierAccountObj->address[$k]['country']:'CN',$country,['prompt'=>'','class'=>'eagle-form-control' , 'required'=>true ])?><span class="msg">*</span></dd>
  <dt>州/省</dt>
  <dd><input class='eagle-form-control' name="<?= $k ?>[province]" type="text" value="<?=isset($carrierAccountObj->address[$k]['country'])?$carrierAccountObj->address[$k]['province']:''; ?>"></dd>
  <dt>市</dt>
  <dd><input class='eagle-form-control' name="<?= $k ?>[city]" type="text"  value="<?=isset($carrierAccountObj->address[$k]['city'])?$carrierAccountObj->address[$k]['city']:''; ?>"></dd>
  <dt>地址</dt>
  <dd><input class='eagle-form-control' name="<?= $k ?>[street]" type="text" value="<?=isset($carrierAccountObj->address[$k]['street'])?$carrierAccountObj->address[$k]['street']:''; ?>"><span class="msg">*</span></dd>
  <dt>邮编</dt>
  <dd><input class='eagle-form-control' name="<?= $k ?>[postcode]" type="number"  value="<?=isset($carrierAccountObj->address[$k]['postcode'])?$carrierAccountObj->address[$k]['postcode']:''; ?>"></dd>
  <dt>区域代码</dt>
  <dd><input class='eagle-form-control' name="<?=$k?>[areacode]" type="text" value="<?=isset($carrierAccountObj->address[$k]['areacode'])?$carrierAccountObj->address[$k]['areacode']:''; ?>"></dd>
  <dt style="padding: 10px 0;">电话和手机必须至少选填一个<span class="msg">*</span></dt><dd></dd>
  <dt>电话</dt>
  <dd><input class='eagle-form-control' name="<?=$k?>[phone]" type="text" value="<?=isset($carrierAccountObj->address[$k]['phone'])?$carrierAccountObj->address[$k]['phone']:''; ?>"></dd>
  <dt>手机</dt>
  <dd><input class='eagle-form-control' name="<?=$k?>[mobile]" type="number" value="<?=isset($carrierAccountObj->address[$k]['mobile'])?$carrierAccountObj->address[$k]['mobile']:''; ?>"></dd>
  <dt>邮箱</dt>
  <dd><input class='eagle-form-control' name="<?=$k?>[email]" type="email" value="<?=isset($carrierAccountObj->address[$k]['email'])?$carrierAccountObj->address[$k]['email']:''; ?>"></dd>
  <dt>传真</dt>
  <dd><input class='eagle-form-control' name="<?=$k?>[fax]" type="text" value="<?=isset($carrierAccountObj->address[$k]['fax'])?$carrierAccountObj->address[$k]['fax']:''; ?>"></dd>
</dl>
</div>
<?php }}?>
