<?php 
use eagle\modules\util\helpers\TranslateHelper;
use yii\helpers\Html;
use eagle\modules\carrier\models\SysCarrierAccount;
$this->registerJsFile(\Yii::getAlias('@web')."/js/project/carrier/deliveryshippingservice.js", ['depends' => ['yii\web\JqueryAsset']]);
$baseUrl = \Yii::$app->urlManager->baseUrl . '/';
$this->registerJsFile($baseUrl."js/jquery.json-2.4.js", ['depends' => ['yii\jui\JuiAsset','yii\bootstrap\BootstrapPluginAsset']]);
$this->title = TranslateHelper::t('添加运输服务');
//$this->params['breadcrumbs'][] = $this->title;
?>
<style>

table{
	font-size:14px;
	
}
.text-right{
	font-weight:bold;
}
.text-left{
	font-weight:bold;
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

ul li a{
	cursor:pointer;
}
.ui-autocomplete {
z-index: 2000 !important;
overflow-y: scroll;
max-height: 400px;
}

.popover-content{
	background-color: rgb(255, 168, 168);
}

.ui-combobox-input{
	border-radius:0px;
}
.ui-combobox-toggle{
	border-radius:0px;
}

</style>

 <script>
    var loadParamsUrl = '<?= \Yii::$app->urlManager->createUrl("carrier/shippingservice/load-params") ?>';
    var loadAccountUrl = '<?= \Yii::$app->urlManager->createUrl("carrier/shippingservice/load-account") ?>';
 </script>
<div class="tracking-index col2-layout">
<?= $this->render('//layouts/menu_left_carrier') ?>
<div class="content-wrapper" >
<?php if (!empty($errors)){?>
<div class="alert alert-danger" role="alert">
<?php foreach ($errors as $error){?>
<?=$error?><br>
<?php }?>
</div>
<?php }?>
<form action="" method="post" id="create_form">
<?=Html::hiddenInput('return_url',$return_url);?>
<input type="hidden" name="id" value="<?= $id ?>">
<!-- table -->
    <table class="table table-condensed">
        <tr>
            <td class="text-right" style="vertical-align:middle;"></td>
            <td class="text-left">
            <?php echo $service->carrier_name?> —— <?=SysCarrierAccount::findOne(['id'=>$service->carrier_account_id])->carrier_name;?> —— <?php echo $service->shipping_method_name?>
            	<?=Html::hiddenInput('carrier_code',$service->carrier_code,['id'=>'carrier_code'])?>
            	<?=Html::hiddenInput('carrier_account_id',$service->carrier_account_id)?>
            	<?=Html::hiddenInput('shipping_method_code',$service->shipping_method_code)?>
            	<?=Html::hiddenInput('third_party_code',$service->third_party_code)?>
            </td>
	    </tr>
    	<tr>
            <td class="text-right" style="vertical-align:middle;"><?=TranslateHelper::t('物流参数') ?></td>
            <td id="params">
            </td>
	    </tr>
	    <tr>
            <td width='120px;' class="text-right" style="vertical-align:middle;"><?=TranslateHelper::t('运输服务别名') ?></td>
            <td>
                <input type="text" size=80 class='eagle-form-control' name="service_name" value="<?=@$service['service_name'] ?>">
            </td>
        </tr>
        
        <?php
			if(!empty($sysShippingMethod['is_print'])){
				if($sysShippingMethod['is_print'] == 1){

				$sysShippingMethod['print_params'] = json_decode($sysShippingMethod['print_params'],true);
				$sysLable = array();
				foreach ($sysShippingMethod['print_params'] as $tmpPrintparams){
					if ($tmpPrintparams == 'label_address')
						$sysLable[$tmpPrintparams] = '地址单';
					if ($tmpPrintparams == 'label_declare')
					$sysLable[$tmpPrintparams] = '报关单';
					if ($tmpPrintparams == 'label_items')
					$sysLable[$tmpPrintparams] = '配货单';
				}

		?>
		<tr>
			<td class="text-right" style="vertical-align:middle;"><?=TranslateHelper::t('打印模式') ?></td>
			<td>
				<label><input type="radio" name="print_type" value="0" <?= (empty($service['print_type']))?'checked':''?>> API获取标签</label>
				<br>
				<label><input type="radio" name="print_type" value="1" <?= ($service['print_type'] == 1)?'checked':''?>> 小老板高仿标签</label>
				<?= Html::checkboxList('lable_list',@$service['print_params']['label_littleboss'],$sysLable,['style'=>'display:inline-block;margin-left:15px;']) ?>
			</td>
		</tr>
		<?php
				}
			}
        ?>
        
        <tr>
            <td class="text-right" style="vertical-align:middle;"><?=TranslateHelper::t('查询网址') ?></td>
            <td>
                <input type="url" size=80 class='eagle-form-control' name="web" value="<?= isset($service['web'])?$service['web']:'http://www.17track.net'; ?>">
            </td>
        </tr>
        <tr>
            <td class="text-right" style="vertical-align:middle;"><?=TranslateHelper::t('标记发货') ?></td>
            <td style="vertical-align:middle;">
	            <table>
		            <tr>
		            <td>eBay</td><td><?=Html::input('text','service_code[ebay]',@$service['service_code']['ebay'],['placeholder'=>'请填写标记发货物流服务名','style'=>'width:300px;','class'=>'eagle-form-control'])?></td>
		            </tr>
		            <tr>
		            <td>Amazon</td>
		            <td>
		            <?php // Html::dropDownList('service_code[amazon]',@$service['service_code']['amazon'],$amazon,['prompt'=>'请选择物流服务','class'=>'eagle-form-control'])?>
		            <?php 
						if(!empty($service['service_code']['amazon'])){
							if(!isset($amazon[$service['service_code']['amazon']])){
								$amazon[$service['service_code']['amazon']] = $service['service_code']['amazon'];
							}
						}
		            ?>
		            <div style="width:200px;float:left;">
						<div class="div-input-group" style="width:100%">
							<div style="float:left;">
								<select name='service_code[amazon]'  class="eagle-form-control">
									<option value="all" <?=(!isset($service['service_code']['amazon']) or !is_numeric($service['service_code']['amazon']) )?" selected ":'' ?>><?= TranslateHelper::t("请选择物流服务")?></option>
									<?php foreach($amazon as $amazonOne):
										if (isset($service['service_code']['amazon'])) $isSelect = ($service['service_code']['amazon'] == $amazonOne)?"selected":"";
										else $isSelect = ""; ?>
									<option value="<?= $amazonOne?>" <?= $isSelect ?>><?=$amazonOne?></option>
									<?php endforeach;?>
									
								</select> 
							</div>
						</div>
					</div>
		            </td>
		            </tr>
		            <tr>
		            <td>Aliexpress</td><td><?=Html::dropDownList('service_code[aliexpress]',@$service['service_code']['aliexpress'],$ali,['prompt'=>'请选择物流服务','class'=>'eagle-form-control'])?></td>
		            </tr>
		            <tr>
		            <td>Wish</td><td><?=Html::dropDownList('service_code[wish]',@$service['service_code']['wish'],$wish,['prompt'=>'请选择物流服务','class'=>'eagle-form-control'])?></td>
		            </tr>
		            <tr>
		            <td>DHGate</td><td><?=Html::dropDownList('service_code[dhgate]',@$service['service_code']['dhgate'],$dhgate,['prompt'=>'请选择物流服务','class'=>'eagle-form-control'])?></td>
		            </tr>
		            <tr>
		            <td>Cdiscount</td><td><?=Html::input('text','service_code[cdiscount]',@$service['service_code']['cdiscount'],['placeholder'=>'请填写标记发货物流服务名','style'=>'width:300px;','class'=>'eagle-form-control'])?></td>
		            </tr>
		            <tr>
		            <td>Lazada</td><td><?=Html::dropDownList('service_code[lazada]',@$service['service_code']['lazada'],$lazada,['prompt'=>'请选择物流服务','class'=>'eagle-form-control'])?></td>
		            </tr>
		             <tr>
		            <td>Linio</td><td><?=Html::dropDownList('service_code[linio]',@$service['service_code']['linio'],$linio,['prompt'=>'请选择物流服务','class'=>'eagle-form-control'])?></td>
		            </tr>
		            <tr>
		            <td>Ensogo</td><td><?=Html::dropDownList('service_code[ensogo]',@$service['service_code']['ensogo'],$ensogo,['prompt'=>'请选择物流服务','class'=>'eagle-form-control'])?></td>
		            </tr>
	            </table>
            </td>
        </tr>
        <tr>
            <td class="text-right" style="vertical-align:middle;"><?=TranslateHelper::t('状态') ?></td>
            <td>
                <input type="radio" value="1" id="used2" name="is_used" checked <?= @$service['is_used']==1?'checked':'' ?> autocomplete="off"><label for="used2"><?=TranslateHelper::t('启用') ?></label>
 		        <input type="radio" value="0" id="used1" name="is_used" <?= isset($service['is_used'])&&$service['is_used']==0?'checked':'' ?> autocomplete="off"><label for="used1"><?=TranslateHelper::t('停用') ?></label>
            </td>
	    </tr>
        <tr>
            <td></td>
            <td>
            <input type="submit" id="savebutton" class="btn btn-primary btn-md" value="保存"/></td>
        </tr>
    </table>
</form>
</div>
</div>