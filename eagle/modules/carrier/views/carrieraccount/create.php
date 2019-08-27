<?php 
use eagle\modules\util\helpers\TranslateHelper;
use yii\helpers\Html;
use yii\helpers\Url;
$this->registerJsFile(\Yii::getAlias('@web')."/js/project/carrier/deliverycarrieraccount.js", ['depends' => ['yii\web\JqueryAsset']]);
$baseUrl = \Yii::$app->urlManager->baseUrl . '/';

$this->title = TranslateHelper::t('添加物流账号');
//$this->params['breadcrumbs'][] = $this->title;

// print_r($carrier);
?>

<style>
body{
color:#637c99;
}
</style>
 <script>
    loadParamsUrl = '<?=\Yii::$app->urlManager->createUrl("carrier/carrieraccount/load-params") ?>';
    saveUrl = '<?= \Yii::$app->urlManager->createUrl("carrier/carrieraccount/operate") ?>';
    xmlUrl = '<?= \Yii::getAlias("@web")."/docs/CollectAddress.xml" ?>';
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
<input type="hidden" id = "carrier_id" name="id" value="<?=isset($account['id'])?$account['id']:''; ?>">
   <font style="size:14px;font-weight:bold;">物流商：</font>
   <?php 
// 	echo Html::dropDownList('carrier_code',@$account['carrier_code'],$carrier,['prompt'=>'物流商','id'=>'select_carrier','class'=>'eagle-form-control']); 
	echo "<select id='select_carrier' class='eagle-form-control' name='carrier_code' >";
	echo "<option value>物流商</option>";
	foreach ($carrier as $carrierkey => $carrierone){
		if($carrierkey == 'rtbcompany'){
			echo "<option value='rtbcompany' ".(substr(@$account['carrier_code'],-10) == 'rtbcompany' ? 'selected' : '')." >软通宝(物流商集合)</option>";
		}else{
			echo "<option value='$carrierkey' ".(@$account['carrier_code'] == $carrierkey ? 'selected' : '')." >$carrierone</option>";
		}
	}   
   echo "</select>";
   
   echo Html::dropDownList('carrierrtb_code',@$account['carrier_code'],$carrier['rtbcompany'],['id'=>'select_rtbcarrier','class'=>'eagle-form-control']);
   ?>
   &nbsp;&nbsp;
   <font style="size:14px;font-weight:bold;">账号别名：</font><?=Html::input('','carrier_name',@$account['carrier_name'],['id'=>'account-alias','class'=>'eagle-form-control','placeholder'=>"账号别名必填"])?>&nbsp;&nbsp;
   <font style="size:14px;font-weight:bold;">是否开启：</font><?=Html::dropDownList('is_used',isset($account['is_used'])?$account['is_used']:'',['1'=>'开启','0'=>'关闭'],['prompt'=>'是否开启','class'=>'eagle-form-control'])?>
    <label for="address_library">载入信息：</label>
    <!--Html::dropDownList(name, selected_value, map_array('option'=>'value'))-->
    <?php echo Html::dropDownList('', '0', $account_list,['id'=>'account_list']); ?>
    <input type="hidden" id = "account_address_library" value='<?=$account_address_library; ?>'>
    <input type="submit" class="btn btn-primary btn-sm" value="保存" id="btn-save-carrieraccount" ><br>
    <!--自动加载表单区域包含 账号，token，发货人信息-->
    <div id="inputParams"></div>
</form>
	</div>
</div>


