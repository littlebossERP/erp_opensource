<?php
use yii\helpers\Html;
use yii\helpers\Url;
use eagle\modules\util\helpers\TranslateHelper;
use eagle\modules\platform\apihelpers\PlatformAccountApi;

$active = '';
$uid = \Yii::$app->user->id;
//$this->registerJs("$('.bind-email-win button.btn.btn-success').prop('disabled',true);" , \yii\web\View::POS_READY);
$this->registerJs("$('#longprogressBar').html('正在等待您的授权！<br><br>使用小老板Amazon邮件客服前，您需要先授权小老板系统为该邮箱使用Amazon邮件服务。<br>验证邮件至您输入的邮箱地址，请根据邮件提示进行授权。<br>完成授权后再次点击“添加”按钮。');" , \yii\web\View::POS_READY);

$this->registerJs("amazoncs.EmailList.store_info=".json_encode($MerchantId_StoreName_Mapping).";" , \yii\web\View::POS_READY);


$MerchantId_StoreName = [];
$default_selection = '';
foreach ($MerchantId_StoreName_Mapping as $merchant_id=>$merchant_info){
	if(empty($default_selection))
		$default_selection = $merchant_id;
	
	$MerchantId_StoreName[$merchant_id] = $merchant_info['store_name'];
}
?>
<style>
.progressBar, .longprogressBar {
	width: auto;
    height: auto;
    border: solid 2px #86A5AD;
    background: white url(/images/loading.gif) no-repeat 10px 10px;
    font-size: 12px;
    line-height: 100%;
    font-family: Arial, sans-serif;
    text-align: left;
    font-weight: bold;
    padding: 17px 10px 10px 50px;
    display: block;
    position: fixed;
    top: 50%;
    left: 50%;
    margin-left: -275px;
    margin-top: -74px;
    z-index: 2001;
}
</style>

<div>
	<div>
		<span style="width:120px;display:inline-block;text-align: right;">选择店铺：</span>
		<?=Html::dropDownList('merchant_id',$default_selection,$MerchantId_StoreName,['class'=>'iv-input','style'=>'width:150px;margin:0px;','onchange'=>"amazoncs.EmailList.selectionChange()"])?>
		<span style="width:100px;display:inline-block;text-align: right;">选择站点：</span>
		<select class="iv-input" name="market_places_id" style="width:120px;">
		<?php 
		$selecting_store_market_place = @$MerchantId_StoreName_Mapping[$default_selection]['market_places'];
		if(!empty($selecting_store_market_place)){
			foreach ($selecting_store_market_place as $country_code=>$market_place){
		?>
			<option value="<?=$market_place?>"><?=$country_code ?></option>
		<?php
			}
		 }
		 ?>
		</select>
	</div>
	<br>
	<div style="margin-top: 10px;">
		<label for="email_address" style="width:120px;display:inline-block;text-align: right;">对应的邮箱地址：</label>
		<input type="text" class="iv-input" id="email_address" name="email_address" value="" style="width:200px;">
		<!-- <a type="button" class="iv-btn btn-important" onclick="amazoncs.EmailList.sendVerifyeEmail()">发送验证邮件</a> -->
		
	</div>
	<br>
	<div class="alert alert-info" style="margin-top: 10px;text-align:left;">
		<span style="margin-bottom:5px;display:inline-block;">使用小老板Amazon邮件客服前，您需要先授权小老板系统为该邮箱使用Amazon邮件服务。</span><br>
		<span style="margin-bottom:5px;display:inline-block;">若您过往曾经授权过了，则可以点击"添加"直接保存绑定。</span><br>
		<span style="margin-bottom:5px;display:inline-block;">若您还未授权过，请点击下方的"添加"按钮，Amazon会立即发送一封验证邮件至您输入的邮箱地址，请根据邮件提示进行授权。</span><br>
		<span>完成授权后，点击"添加"保存此邮箱绑定了。</span>
	</div>
	<input type="hidden" value="0" name="verifyed" id="verifyed">
	<div style="margin-top: 10px;text-align:center;display:none;">
		<input type="button" class="iv-btn btn-important" value="发送Amazon验证邮件" onclick="amazoncs.EmailList.sendVerifyeEmail(this)" /> 
		<input type="button" class="iv-btn btn-important" value="检查授权结果" onclick="amazoncs.EmailList.checkVerifye()" /> 
	</div>
	<div class="alert alert-success" id="verifye_success" style="margin-top:10px;display:none;">
		授权成功！再次点击“添加”保存绑定。
	</div>
	<div class="alert alert-danger" id="verifye_failed" style="margin-top:10px;display:none;">
	</div>
</div>