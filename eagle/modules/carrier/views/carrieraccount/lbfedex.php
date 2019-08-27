<?php 
use yii\helpers\Html;
use eagle\modules\util\helpers\TranslateHelper;
?>
<?php foreach($carrierObj->address_list as $k){?>
<?php if ($k == 'pickupaddress'){?>
<?php $province_zh=array(
		'ANHUI'=>'安徽',
		'BEIJING'=>'北京',
		'CHONGQING'=>'重庆',
		'FUJIAN'=>'福建',
		'GANSU'=>'甘肃 ',
		'GUANGDONG'=>'广东',
		'GUANGXI'=>'广西',
		'GUIZHOU'=>'贵州',
		'HAINAN'=>'海南',
		'HEBEI'=>'河北',
		'HENAN'=>'河南',
		'HEILONGJIANG'=>'黑龙江',
		'HUBEI'=>'湖北',
		'HUNAN'=>'湖南',
		'JIANGXI'=>'江西',
		'JIANGSU'=>'江苏',
		'JILIN'=>'吉林',
		'LIAONING'=>'辽宁',
		'NEIMENGGU'=>'内蒙古',
		'NINGXIA'=>'宁夏',
		'QINGHAI'=>'青海',
		'SHANXI'=>'陕西',
		'SHANXI'=>'山西',
		'SHANDONG'=>'山东',
		'SHANGHAI'=>'上海',
		'SICHUAN'=>'四川',
		'TIANJIN'=>'天津',
		'XINJIANG'=>'新疆',
		'XIZANG'=>'西藏',
		'YUNNAN'=>'云南',
		'ZHEJIANG'=>'浙江',
		'AOMEN'=>'澳门'
)?>
<div class='panel col-md-4'>
	<strong>
<?= $address_list[$k];?>
</strong>
<hr>
<dl>
  <dt>联系人姓名（请输入英文地址）</dt>
  <dd><input class='eagle-form-control' name="<?=$k?>[contact]" type="text" value="<?= @$carrierAccountObj['address'][$k]['contact'] ?>" placeholder="请输入英文"></dd>
  <dt>公司名称</dt>
  <dd><input class='eagle-form-control' name="<?=$k?>[company]" type="text" value="<?= @$carrierAccountObj['address'][$k]['company'] ?>" placeholder="请输入英文"></dd>
  <dt>国家地区</dt>
  <dd><?= Html::dropDownList($k.'[country]',isset($carrierAccountObj['address'][$k]['country'])?$carrierAccountObj['address'][$k]['country']:'CN',array('CN'=>'中国','HK'=>'香港','TW'=>'台湾'),['prompt'=>'','class'=>'eagle-form-control'])?></dd>
  <dt>省份</dt>
   <dd><?php echo Html::dropDownList($k.'[province]',@$carrierAccountObj['address'][$k]['province'],$province_zh,['class'=>'eagle-form-control'])?></dd>
  <dt>城市</dt>
  <dd><input class='eagle-form-control' name="<?=$k?>[city]"  type="text" value="<?= @$carrierAccountObj['address'][$k]['city'] ?>" placeholder="请输入英文"></dd>
  <dt>区/县</dt>
  <dd><input class='eagle-form-control' name="<?=$k?>[district]"  type="text" value="<?= @$carrierAccountObj['address'][$k]['district'] ?>" placeholder="请输入英文"></dd>
  <dt>街道地址</dt>
  <dd><input class='eagle-form-control' name="<?=$k?>[street]" type="text" value="<?= @$carrierAccountObj['address'][$k]['street'] ?>" style='width:100px;height:21px;'  placeholder="请输入英文"></dd>
  <dt>邮政编码</dt>
  <dd><input class='eagle-form-control' name="<?=$k?>[postcode]" type="number" value="<?= @$carrierAccountObj['address'][$k]['postcode'] ?>" ></dd>
   <dt>手机号码</dt>
  <dd><input class='eagle-form-control' name="<?=$k?>[mobile]" type="text" value="<?= @$carrierAccountObj['address'][$k]['mobile'] ?>"></dd>
  <dt>电话</dt>
  <dd><input class='eagle-form-control' name="<?=$k?>[phone]" type="text" value="<?= @$carrierAccountObj['address'][$k]['phone'] ?>"></dd>
  <dt>邮箱</dt>
  <dd><input class='eagle-form-control' name="<?=$k?>[email]" type="email" value="<?= @$carrierAccountObj['address'][$k]['email'] ?>"></dd>
</dl>
</div>
<?php }elseif ($k == 'shippingfrom'){?>
<?php $province_en=array(
		'ANHUI'=>'Anhui',
		'BEIJING'=>'Beijing',
		'CHONGQING'=>'Chongqing',
		'FUJIAN'=>'Fujian',
		'GANSU'=>'Gansu ',
		'GUANGDONG'=>'Guangdong',
		'GUANGXI'=>'Guangxi',
		'GUIZHOU'=>'Guizhou',
		'HAINAN'=>'Hainan',
		'HEBEI'=>'Hebei',
		'HENAN'=>'Henan',
		'HEILONGJIANG'=>'Heilongjiang',
		'HUBEI'=>'Hubei',
		'HUNAN'=>'Hunan',
		'JIANGXI'=>'Jiangxi',
		'JIANGSU'=>'Jiangsu',
		'JILIN'=>'Jilin',
		'LIAONING'=>'Liaoning',
		'NEIMENGGU'=>'Inner',
		'NINGXIA'=>'Ningxia',
		'QINGHAI'=>'Qinghai',
		'SHANXI'=>'Shaanxi',
		'SHANXI'=>'Shanxi',
		'SHANDONG'=>'Shandong',
		'SHANGHAI'=>'Shanghai',
		'SICHUAN'=>'Sichuan',
		'TIANJIN'=>'Tianjin',
		'XINJIANG'=>'Xinjiang',
		'XIZANG'=>'Tibet',
		'YUNNAN'=>'Yunnan',
		'ZHEJIANG'=>'Zhejiang',
		'AOMEN'=>'Macao'
)?>
<div class='panel col-md-4'>
		<strong>
		发货地址(英文)
<?//= $address_list[$k];?>
</strong>
<hr>
<dl>
  <dt>Contact name（Please enter English address）</dt>
  <dd><input class='eagle-form-control' name="<?=$k?>[contact]" type="text" value="<?= @$carrierAccountObj['address'][$k]['contact'] ?>"></dd>
  <dt>Company name</dt>
  <dd><input class='eagle-form-control' name="<?=$k?>[company]" type="text" value="<?= @$carrierAccountObj['address'][$k]['company'] ?>"></dd>
  <dt>Country/Region</dt>
  <dd><?= Html::dropDownList($k.'[country]',isset($carrierAccountObj['address'][$k]['country'])?$carrierAccountObj['address'][$k]['country']:'CN',array('CN'=>'China','HK'=>'Hongkong','TW'=>'Taiwan'),['prompt'=>'','class'=>'eagle-form-control'])?></dd>
  <dt>Province</dt>
  <dd><?php echo Html::dropDownList($k.'[province]',@$carrierAccountObj['address'][$k]['province'],$province_en,['class'=>'eagle-form-control'])?></dd>
  <dt>City</dt>
  <dd><input class='eagle-form-control' name="<?=$k?>[city]"  type="text" value="<?= @$carrierAccountObj['address'][$k]['city'] ?>"></dd>
  <dt>District</dt>
  <dd><input class='eagle-form-control' name="<?=$k?>[district]"  type="text" value="<?= @$carrierAccountObj['address'][$k]['district'] ?>"></dd>
  <dt>Street</dt>
  <dd><input class='eagle-form-control' name="<?=$k?>[street]" type="text" value="<?= @$carrierAccountObj['address'][$k]['street'] ?>"></dd>
  <dt>Zip Code</dt>
  <dd><input class='eagle-form-control' name="<?=$k?>[postcode]" type="number" value="<?= @$carrierAccountObj['address'][$k]['postcode'] ?>" ></dd>
  <dt>Mobile</dt>
  <dd><input class='eagle-form-control' name="<?=$k?>[mobile]" type="text" value="<?= @$carrierAccountObj['address'][$k]['mobile'] ?>"></dd>
  <dt>Phone Number</dt>
  <dd><input class='eagle-form-control' name="<?=$k?>[phone]" type="text" value="<?= @$carrierAccountObj['address'][$k]['phone'] ?>"></dd>
  <dt>Email address</dt>
  <dd><input class='eagle-form-control' name="<?=$k?>[email]" type="email" value="<?= @$carrierAccountObj['address'][$k]['email'] ?>"></dd>
</dl>
</div>
<?php }elseif ($k == 'returnaddress'){?>
<?php $province_zh=array(
		'ANHUI'=>'安徽',
		'BEIJING'=>'北京',
		'CHONGQING'=>'重庆',
		'FUJIAN'=>'福建',
		'GANSU'=>'甘肃 ',
		'GUANGDONG'=>'广东',
		'GUANGXI'=>'广西',
		'GUIZHOU'=>'贵州',
		'HAINAN'=>'海南',
		'HEBEI'=>'河北',
		'HENAN'=>'河南',
		'HEILONGJIANG'=>'黑龙江',
		'HUBEI'=>'湖北',
		'HUNAN'=>'湖南',
		'JIANGXI'=>'江西',
		'JIANGSU'=>'江苏',
		'JILIN'=>'吉林',
		'LIAONING'=>'辽宁',
		'NEIMENGGU'=>'内蒙古',
		'NINGXIA'=>'宁夏',
		'QINGHAI'=>'青海',
		'SHANXI'=>'陕西',
		'SHANXI'=>'山西',
		'SHANDONG'=>'山东',
		'SHANGHAI'=>'上海',
		'SICHUAN'=>'四川',
		'TIANJIN'=>'天津',
		'XINJIANG'=>'新疆',
		'XIZANG'=>'西藏',
		'YUNNAN'=>'云南',
		'ZHEJIANG'=>'浙江',
		'AOMEN'=>'澳门'
)?>
<div class='panel col-md-4'>
	<strong>
		退货地址(中文)
<?//= $address_list[$k];?>
</strong>
<hr>
<dl>
  <dt>联系人姓名（请输入英文地址）</dt>
  <dd><input class='eagle-form-control' name="<?=$k?>[contact]" type="text" value="<?= @$carrierAccountObj['address'][$k]['contact'] ?>" placeholder="请输入英文"></dd>
  <dt>公司名称</dt>
  <dd><input class='eagle-form-control' name="<?=$k?>[company]" type="text" value="<?= @$carrierAccountObj['address'][$k]['company'] ?>" placeholder="请输入英文"></dd>
  <dt>国家地区</dt>
  <dd><?= Html::dropDownList($k.'[country]',isset($carrierAccountObj['address'][$k]['country'])?$carrierAccountObj['address'][$k]['country']:'CN',array('CN'=>'中国','HK'=>'香港','TW'=>'台湾'),['prompt'=>'','class'=>'eagle-form-control'])?></dd>
  <dt>省份</dt>
   <dd><?php echo Html::dropDownList($k.'[province]',@$carrierAccountObj['address'][$k]['province'],$province_zh,['class'=>'eagle-form-control'])?></dd>
  <dt>城市</dt>
  <dd><input class='eagle-form-control' name="<?=$k?>[city]"  type="text" value="<?= @$carrierAccountObj['address'][$k]['city'] ?>" placeholder="请输入英文"></dd>
  <dt>区/县</dt>
  <dd><input class='eagle-form-control' name="<?=$k?>[district]"  type="text" value="<?= @$carrierAccountObj['address'][$k]['district'] ?>" placeholder="请输入英文"></dd>
  <dt>街道地址</dt>
  <dd><input class='eagle-form-control' name="<?=$k?>[street]" type="text" value="<?= @$carrierAccountObj['address'][$k]['street'] ?>" placeholder="请输入英文"></dd>
  <dt>邮政编码</dt>
  <dd><input class='eagle-form-control' name="<?=$k?>[postcode]" type="number" value="<?= @$carrierAccountObj['address'][$k]['postcode'] ?>" ></dd>
 <dt>手机号码</dt>
  <dd><input class='eagle-form-control' name="<?=$k?>[mobile]" type="text" value="<?= @$carrierAccountObj['address'][$k]['mobile'] ?>"></dd>
  <dt>电话</dt>
  <dd><input class='eagle-form-control' name="<?=$k?>[phone]" type="text" value="<?= @$carrierAccountObj['address'][$k]['phone'] ?>"></dd>
  <dt>邮箱</dt>
  <dd><input class='eagle-form-control' name="<?=$k?>[email]" type="email" value="<?= @$carrierAccountObj['address'][$k]['email'] ?>"></dd>
</dl>
</div>
<?php }?>
<?php }?>