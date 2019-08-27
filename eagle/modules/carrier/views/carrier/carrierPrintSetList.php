<?php 
use eagle\modules\util\helpers\TranslateHelper;
use yii\data\Sort;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\grid\GridView;
use yii\jui\Dialog;
use eagle\modules\carrier\models\SysCarrier;
use eagle\models\SysCountry;
use common\helpers\Helper_Array;
use eagle\modules\carrier\apihelpers\CarrierApiHelper;

$baseUrl = \Yii::$app->urlManager->baseUrl . '/';
$this->registerJsFile($baseUrl."js/jquery.json-2.4.js", ['depends' => ['yii\jui\JuiAsset','yii\bootstrap\BootstrapPluginAsset']]);
$this->registerCssFile($baseUrl."css/tracking/tracking.css");
$this->title = TranslateHelper::t('物流打印设置');

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd"> 

<style>
.table td,.table th{
	text-align: center;
}

table{
	font-size:12px;
}

.table>tbody>tr:nth-of-type(even){
	background-color:#f4f9fc
}
.table>tbody td{
color:#637c99;
}
.table>tbody a{
color:#337ab7;
}
.table>thead>tr>th {
height: 35px;
vertical-align: middle;
}
.table>tbody>tr>td {
height: 35px;
vertical-align: middle;
}

.ui-autocomplete {
z-index: 2000 !important;
overflow-y: scroll;
max-height: 400px;
}

<?php
	$configLabelOptional = array('100x100' => '100mm x 100mm','210x297' => 'A4 (210mm x 297mm)');
?>

</style>
<div class="tracking-index col2-layout">
	<?= $this->render('//layouts/menu_left_carrier') ?>
	<!-- 右侧table内容区域 -->
	<div class="content-wrapper" >
		<form action="" method="post" name='form1' id='form1'>
			物流标签打印纸张：<?= Html::dropdownlist('label_paper_size[val]',$carrierConfig['label_paper_size']['val'],$configLabelOptional,['class'=>'iv-input sizeList','id'=>'sizeList'])?>
			<input type="submit" class="iv-btn btn-primary mtBtn" value="保存">
			<br><br>
			<?php 
				$k = 'shippingfrom_en';
				$countrys = CarrierApiHelper::getcountrys();
			?>
			发货地址(英文)
			<br><br>
			<dl>
			  <dt>国家</dt>
			  <dd><?= Html::dropDownList('address['.$k."][country]",isset($carrierConfig['address'][$k]['country'])?$carrierConfig['address'][$k]['country']:'CN',$countrys,['prompt'=>'','class'=>'eagle-form-control'])?></dd>
			  <dt>州/省</dt>
			  <dd><input class='eagle-form-control' name="address[<?= $k ?>][province]" type="text" value="<?=isset($carrierConfig['address'][$k]['province']) ? $carrierConfig['address'][$k]['province'] : '' ?>"></dd>
			  <dt>市</dt>
			  <dd><input class='eagle-form-control' name="address[<?= $k ?>][city]" type="text"  value="<?=isset($carrierConfig['address'][$k]['city']) ? $carrierConfig['address'][$k]['city'] : '' ?>"></dd>
			  <dt>区/县</dt>
			  <dd><input class='eagle-form-control' name="address[<?= $k ?>][district]" type="text"  value="<?=isset($carrierConfig['address'][$k]['district']) ? $carrierConfig['address'][$k]['district'] : '' ?>"></dd>
			  <dt>地址</dt>
			  <dd><input class='eagle-form-control' name="address[<?= $k ?>][street]" type="text" value="<?=isset($carrierConfig['address'][$k]['street']) ? $carrierConfig['address'][$k]['street'] : '' ?>"></dd>
			  <dt>邮编</dt>
			  <dd><input class='eagle-form-control' name="address[<?= $k ?>][postcode]" type="number"  value="<?=isset($carrierConfig['address'][$k]['postcode']) ? $carrierConfig['address'][$k]['postcode'] : '' ?>"></dd>
			  <dt>公司</dt>
			  <dd><input class='eagle-form-control' name="address[<?= $k ?>][company]" type="text" value="<?=isset($carrierConfig['address'][$k]['company']) ? $carrierConfig['address'][$k]['company'] : '' ?>"></dd>
			  <dt>发件人</dt>
			  <dd><input class='eagle-form-control' name="address[<?= $k ?>][contact]" type="text" value="<?=isset($carrierConfig['address'][$k]['contact']) ? $carrierConfig['address'][$k]['contact'] : '' ?>"></dd>
			  <dt>电话</dt>
			  <dd><input class='eagle-form-control' name="address[<?= $k ?>][phone]" type="text" value="<?=isset($carrierConfig['address'][$k]['phone']) ? $carrierConfig['address'][$k]['phone'] : '' ?>"></dd>
			  <dt>手机</dt>
			  <dd><input class='eagle-form-control' name="address[<?= $k ?>][mobile]" type="text" value="<?=isset($carrierConfig['address'][$k]['mobile']) ? $carrierConfig['address'][$k]['mobile'] : '' ?>"></dd>
			  <dt>传真</dt>
			  <dd><input class='eagle-form-control' name="address[<?= $k ?>][fax]" type="text" value="<?=isset($carrierConfig['address'][$k]['fax']) ? $carrierConfig['address'][$k]['fax'] : '' ?>"></dd>
			  <dt>邮箱</dt>
			  <dd><input class='eagle-form-control' name="address[<?= $k ?>][email]" type="text" value="<?=isset($carrierConfig['address'][$k]['email']) ? $carrierConfig['address'][$k]['email'] : '' ?>"></dd>
			</dl>
		</form>
	</div>
</div>

