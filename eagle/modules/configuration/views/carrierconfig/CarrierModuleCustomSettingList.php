<?php use yii\helpers\Html;
use eagle\modules\util\helpers\TranslateHelper;
use yii\bootstrap\Dropdown;
use eagle\helpers\HtmlHelper;

$baseUrl = \Yii::$app->urlManager->baseUrl . '/';
$this->registerJsFile($baseUrl."js/project/configuration/carrierconfig/CarrierModuleCustomSettingList/CarrierModuleCustomSettingList.js", ['depends' => ['yii\jui\JuiAsset','yii\bootstrap\BootstrapPluginAsset']]);

$this->registerJs("initCarrierCustomSettingValidateInput();" , \yii\web\View::POS_READY);
?>

<style>
	.leftDIV{
		float:left;
		width:150px;
		text-align:right;
		line-height:26px;
	}
	.rightDIV{
		margin-left:160px;
	}
	.lShow{
		float:left;
		width:70px;
		text-align:right;
		line-height:26px;
		margin-bottom:5px;
	}
	.rShow{
		margin-left:80px;
		margin-bottom:5px;
	}
	.sizetext{
		width:35px;
	}
	.mtBtn{
		padding-left:15px;
		padding-right:15px;
	}
	.iv-input[disabled]{
		cursor: default;
		background:#F0F0F0;
	}
</style>


<?php echo $this->render('../leftmenu/_leftmenu');?>
<?php 
//判断子账号是否有权限查看，lrq20170829
if(!\eagle\modules\permission\apihelpers\UserApiHelper::checkSettingModulePermission('delivery_setting')){?>
	<div style="float:left; margin: auto; margin:50px 0px 0px 200px; ">
		<span style="font: bold 20px Arial;">亲，没有权限访问。 </span>
	</div>
<?php return;}?>

<div>
	<?= Html::hiddenInput('msg',$msg,['id'=>'msg'])?>
	<div style="padding:30px 0;">
		<form id="moduleFORM" method="post" action=''>
		<div class="form-group" style='display: none;'>
			<div class="leftDIV">客户参考号配置：</div>
			<div class="rightDIV">
				<?php foreach($config['customer_number_config'] as $name=>$cnum){?>
				<div class="lShow"><?= TranslateHelper::t($name)?></div>
				<div class="rShow"><?= Html::dropdownlist('customer_number_config['.$name.']',$cnum,$platformCustomerNumberMode,['class'=>'iv-input'])?></div>
				<?php }?>
			</div>
		</div>
		<div class="form-group" style='display: none;'>
			<?php $memo = $config['carrier_memo']?>
			<div class="leftDIV">物流商下单备注：</div>
			<div class="rightDIV">
				<?= Html::hiddenInput('carrier_memo[product]',$memo['product'])?>
				<?= Html::hiddenInput('carrier_memo[sku]',$memo['sku'])?>
				<?= Html::hiddenInput('carrier_memo[qty]',$memo['qty'])?>
				<?= Html::hiddenInput('carrier_memo[order_id]',$memo['order_id'])?>
				<label><input type="checkbox" data="carrier_memo[product]" class="cmemo" <?= ($memo['product'])?'checked':''?>>商品名</label>
				<label><input type="checkbox" data="carrier_memo[sku]" class="cmemo" <?= ($memo['sku'])?'checked':''?>>SKU</label>
				<label><input type="checkbox" data="carrier_memo[qty]" class="cmemo" <?= ($memo['qty'])?'checked':''?>>数量</label>
				<label><input type="checkbox" data="carrier_memo[order_id]" class="cmemo" <?= ($memo['order_id'])?'checked':''?>>小老板单号</label>
			</div>
		</div>
		<div class="form-group">
			<div class="leftDIV">物流标签打印纸张：</div>
			<div class="rightDIV">
				<?= Html::dropdownlist('label_paper_size[val]',$config['label_paper_size']['val'],@$config['label_optional_value'],['class'=>'iv-input sizeList','id'=>'sizeList'])?>
				<div style="margin-top:10px; display: block;" id="sizeDIV">
					宽：<input type="text" name="label_paper_size[template_width]" class="iv-input sizetext" value="<?= @$config['label_paper_size']['template_width']?>"/>mm x
					长：<input type="text" name="label_paper_size[template_height]" class="iv-input sizetext" value="<?= @$config['label_paper_size']['template_height']?>"/>mm
					
				</div>
			</div>
		</div>
		<div class="form-group">
			<?php 
				$k = 'shippingfrom_en';
			?>
			
			<div class="leftDIV">发货地址(英文)：</div>
			<div class="rightDIV">
				<dl>
				  <dt>国家
				  <?= Html::dropDownList('address['.$k."][country]",isset($config['address'][$k]['country'])?$config['address'][$k]['country']:'CN',$countrys,['prompt'=>'','class'=>'eagle-form-control'])?>
				  </dt>
				  <dt>州/省
				  <input class='eagle-form-control' name="address[<?= $k ?>][province]" type="text" value="<?=isset($config['address'][$k]['province']) ? $config['address'][$k]['province'] : '' ?>">
				  </dt>
				  <dt>市
				  <input class='eagle-form-control' name="address[<?= $k ?>][city]" type="text"  value="<?=isset($config['address'][$k]['city']) ? $config['address'][$k]['city'] : '' ?>">
				  </dt>
				  <dt>区/县
				  <input class='eagle-form-control' name="address[<?= $k ?>][district]" type="text"  value="<?=isset($config['address'][$k]['district']) ? $config['address'][$k]['district'] : '' ?>">
				  </dt>
				  <dt>地址
				  <input class='eagle-form-control' name="address[<?= $k ?>][street]" type="text" value="<?=isset($config['address'][$k]['street']) ? $config['address'][$k]['street'] : '' ?>">
				  </dt>
				  <dt>邮编
				  <input class='eagle-form-control' name="address[<?= $k ?>][postcode]" type="number"  value="<?=isset($config['address'][$k]['postcode']) ? $config['address'][$k]['postcode'] : '' ?>">
				  </dt>
				  <dt>公司
				  <input class='eagle-form-control' name="address[<?= $k ?>][company]" type="text" value="<?=isset($config['address'][$k]['company']) ? $config['address'][$k]['company'] : '' ?>">
				  </dt>
				  <dt>发件人
				  <input class='eagle-form-control' name="address[<?= $k ?>][contact]" type="text" value="<?=isset($config['address'][$k]['contact']) ? $config['address'][$k]['contact'] : '' ?>">
				  </dt>
				  <dt>电话
				  <input class='eagle-form-control' name="address[<?= $k ?>][phone]" type="text" value="<?=isset($config['address'][$k]['phone']) ? $config['address'][$k]['phone'] : '' ?>">
				  </dt>
				  <dt>手机
				  <input class='eagle-form-control' name="address[<?= $k ?>][mobile]" type="text" value="<?=isset($config['address'][$k]['mobile']) ? $config['address'][$k]['mobile'] : '' ?>">
				  </dt>
				  <dt>传真
				  <input class='eagle-form-control' name="address[<?= $k ?>][fax]" type="text" value="<?=isset($config['address'][$k]['fax']) ? $config['address'][$k]['fax'] : '' ?>">
				  </dt>
				  <dt>邮箱
				  <input class='eagle-form-control' name="address[<?= $k ?>][email]" type="text" value="<?=isset($config['address'][$k]['email']) ? $config['address'][$k]['email'] : '' ?>">
				  </dt>
				  <dt>退货单位
				  <input class='eagle-form-control' name="address[<?= $k ?>][returngoods]" type="text" value="<?=isset($config['address'][$k]['returngoods']) ? $config['address'][$k]['returngoods'] : '' ?>">
				  </dt>
				</dl>
			</div>
		</div>
		<div class="form-group">
			<div class="rightDIV">
				<input type="button" class="iv-btn btn-primary mtBtn" onclick="checkReq()" value="保存">
			</div>
		</div>
		</form>
	</div>
</div>