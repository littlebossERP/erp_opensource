<?php
use yii\helpers\Html;
use eagle\modules\util\helpers\TranslateHelper;
?>
<?php foreach($carrierObj->address_list as $k){?>
    <?php if ($k == 'pickupaddress'){?>
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
                揽收地址(英文)
            </strong>
            <hr>
            <dl class="<?=$k?>-area">
                <dt>Contact name（Please enter English address）</dt>
                <dd><input class='eagle-form-control <?=$k?>-contact' name="<?=$k?>[contact]" type="text" value="<?= @$carrierAccountObj['address'][$k]['contact'] ?>"></dd>
                <dt>Company name</dt>
                <dd><input class='eagle-form-control <?=$k?>-company' name="<?=$k?>[company]" type="text" value="<?= @$carrierAccountObj['address'][$k]['company'] ?>"></dd>
                <dt>Country/Region</dt>
                <dd><?= Html::dropDownList($k.'[country]',isset($carrierAccountObj['address'][$k]['country'])?$carrierAccountObj['address'][$k]['country']:'CN',array('CN'=>'China','HK'=>'Hongkong','TW'=>'Taiwan'),['prompt'=>'','class'=>"eagle-form-control {$k}-country"])?></dd>
                <dt>Province</dt>
                <dd><?php echo Html::dropDownList($k.'[province]',@$carrierAccountObj['address'][$k]['province'],$province_en,['class'=>"eagle-form-control {$k}-province"])?></dd>
                <dt>City</dt>
                <dd><input class='eagle-form-control <?=$k?>-city' name="<?=$k?>[city]"  type="text" value="<?= @$carrierAccountObj['address'][$k]['city'] ?>"></dd>
                <dt>District</dt>
                <dd><input class='eagle-form-control <?=$k?>-district' name="<?=$k?>[district]"  type="text" value="<?= @$carrierAccountObj['address'][$k]['district'] ?>"></dd>
                <dt>Street</dt>
                <dd><input class='eagle-form-control <?=$k?>-street' name="<?=$k?>[street]" type="text" value="<?= @$carrierAccountObj['address'][$k]['street'] ?>"></dd>
                <dt>Zip Code</dt>
                <dd><input class='eagle-form-control <?=$k?>-postcode' name="<?=$k?>[postcode]" type="number" value="<?= @$carrierAccountObj['address'][$k]['postcode'] ?>" ></dd>
                <dt>Mobile</dt>
                <dd><input class='eagle-form-control <?=$k?>-mobile' name="<?=$k?>[mobile]" type="text" value="<?= @$carrierAccountObj['address'][$k]['mobile'] ?>"></dd>
                <dt>Phone Number</dt>
                <dd><input class='eagle-form-control <?=$k?>-phone' name="<?=$k?>[phone]" type="text" value="<?= @$carrierAccountObj['address'][$k]['phone'] ?>"></dd>
                <dt>Email address</dt>
                <dd><input class='eagle-form-control <?=$k?>-email' name="<?=$k?>[email]" type="email" value="<?= @$carrierAccountObj['address'][$k]['email'] ?>"></dd>
            </dl>
        </div>
    <?php }elseif ($k == 'shippingfrom_en'){?>
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
            <dl class="<?=$k?>-area">
                <dt>Contact name（Please enter English address）</dt>
                <dd><input class='eagle-form-control <?=$k?>-contact' name="<?=$k?>[contact]" type="text" value="<?= @$carrierAccountObj['address'][$k]['contact'] ?>"></dd>
                <dt>Company name</dt>
                <dd><input class='eagle-form-control <?=$k?>-company' name="<?=$k?>[company]" type="text" value="<?= @$carrierAccountObj['address'][$k]['company'] ?>"></dd>
                <dt>Country/Region</dt>
                <dd><?= Html::dropDownList($k.'[country]',isset($carrierAccountObj['address'][$k]['country'])?$carrierAccountObj['address'][$k]['country']:'CN',array('CN'=>'China','HK'=>'Hongkong','TW'=>'Taiwan'),['prompt'=>'','class'=>"eagle-form-control {$k}-country"])?></dd>
                <dt>Province</dt>
                <dd><?php echo Html::dropDownList($k.'[province]',@$carrierAccountObj['address'][$k]['province'],$province_en,['class'=>"eagle-form-control {$k}-province"])?></dd>
                <dt>City</dt>
                <dd><input class='eagle-form-control <?=$k?>-city' name="<?=$k?>[city]"  type="text" value="<?= @$carrierAccountObj['address'][$k]['city'] ?>"></dd>
                <dt>District</dt>
                <dd><input class='eagle-form-control <?=$k?>-district' name="<?=$k?>[district]"  type="text" value="<?= @$carrierAccountObj['address'][$k]['district'] ?>"></dd>
                <dt>Street</dt>
                <dd><input class='eagle-form-control <?=$k?>-street' name="<?=$k?>[street]" type="text" value="<?= @$carrierAccountObj['address'][$k]['street'] ?>"></dd>
                <dt>Zip Code</dt>
                <dd><input class='eagle-form-control <?=$k?>-postcode' name="<?=$k?>[postcode]" type="number" value="<?= @$carrierAccountObj['address'][$k]['postcode'] ?>" ></dd>
                <dt>Mobile</dt>
                <dd><input class='eagle-form-control <?=$k?>-mobile' name="<?=$k?>[mobile]" type="text" value="<?= @$carrierAccountObj['address'][$k]['mobile'] ?>"></dd>
                <dt>Phone Number</dt>
                <dd><input class='eagle-form-control <?=$k?>-phone' name="<?=$k?>[phone]" type="text" value="<?= @$carrierAccountObj['address'][$k]['phone'] ?>"></dd>
                <dt>Email address</dt>
                <dd><input class='eagle-form-control <?=$k?>-email' name="<?=$k?>[email]" type="email" value="<?= @$carrierAccountObj['address'][$k]['email'] ?>"></dd>
            </dl>
        </div>
    <?php }elseif ($k == 'returnaddress'){?>
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
                退货地址(英文)
                <?//= $address_list[$k];?>
            </strong>
            <hr>
            <dl class="<?=$k?>-area">
                <dt>Contact name（Please enter English address）</dt>
                <dd><input class='eagle-form-control <?=$k?>-contact' name="<?=$k?>[contact]" type="text" value="<?= @$carrierAccountObj['address'][$k]['contact'] ?>"></dd>
                <dt>Company name</dt>
                <dd><input class='eagle-form-control <?=$k?>-company' name="<?=$k?>[company]" type="text" value="<?= @$carrierAccountObj['address'][$k]['company'] ?>"></dd>
                <dt>Country/Region</dt>
                <dd><?= Html::dropDownList($k.'[country]',isset($carrierAccountObj['address'][$k]['country'])?$carrierAccountObj['address'][$k]['country']:'CN',array('CN'=>'China','HK'=>'Hongkong','TW'=>'Taiwan'),['prompt'=>'','class'=>"eagle-form-control {$k}-country"])?></dd>
                <dt>Province</dt>
                <dd><?php echo Html::dropDownList($k.'[province]',@$carrierAccountObj['address'][$k]['province'],$province_en,['class'=>"eagle-form-control {$k}-province"])?></dd>
                <dt>City</dt>
                <dd><input class='eagle-form-control <?=$k?>-city' name="<?=$k?>[city]"  type="text" value="<?= @$carrierAccountObj['address'][$k]['city'] ?>"></dd>
                <dt>District</dt>
                <dd><input class='eagle-form-control <?=$k?>-district' name="<?=$k?>[district]"  type="text" value="<?= @$carrierAccountObj['address'][$k]['district'] ?>"></dd>
                <dt>Street</dt>
                <dd><input class='eagle-form-control <?=$k?>-street' name="<?=$k?>[street]" type="text" value="<?= @$carrierAccountObj['address'][$k]['street'] ?>"></dd>
                <dt>Zip Code</dt>
                <dd><input class='eagle-form-control <?=$k?>-postcode' name="<?=$k?>[postcode]" type="number" value="<?= @$carrierAccountObj['address'][$k]['postcode'] ?>" ></dd>
                <dt>Mobile</dt>
                <dd><input class='eagle-form-control <?=$k?>-mobile' name="<?=$k?>[mobile]" type="text" value="<?= @$carrierAccountObj['address'][$k]['mobile'] ?>"></dd>
                <dt>Phone Number</dt>
                <dd><input class='eagle-form-control <?=$k?>-phone' name="<?=$k?>[phone]" type="text" value="<?= @$carrierAccountObj['address'][$k]['phone'] ?>"></dd>
                <dt>Email address</dt>
                <dd><input class='eagle-form-control <?=$k?>-email' name="<?=$k?>[email]" type="email" value="<?= @$carrierAccountObj['address'][$k]['email'] ?>"></dd>
            </dl>
        </div>
    <?php }?>
<?php }?>
