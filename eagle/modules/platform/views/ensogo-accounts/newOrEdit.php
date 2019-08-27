<?php
use eagle\modules\util\helpers\TranslateHelper;

$this->registerJsFile(\Yii::getAlias('@web')."/js/project/platform/EnsogoAccountsNewOrEdit.js", ['depends' => ['yii\web\JqueryAsset']]);
$this->registerJs("platform.EnsogoAccountsNeworedit.initWidget();" , \yii\web\View::POS_READY);

?>
<style>
.imgBlock .modal-dialog{
	width: 1210px;
	height: 500px;
}
</style>

<div style="padding:10px 15px 0px 15px">
	<form id="platform-EnsogoAccounts-form" action="<?php echo \Yii::getAlias('@web');?>/platform/EnsogoAccounts/save" method="post">
	
	<?php if ($mode<>"new"){ ?>
	    <input class="form-control" name="Ensogo_uid" type=hidden value="<?php echo $EnsogoData['uid']; ?>" />
		<input class="form-control" name="Ensogo_id" type=hidden value="<?php echo $EnsogoData['site_id']; ?>" />
	<?php } ?>    
	
		<div>
			
			 <div class="row" style="margin-bottom:15px;">
			             <div class="col-xs-3" style="text-align: right;padding-top: 15px;"><span style="color:red;">*</span>店铺名称 ：</div>
			             <div class="col-xs-9">
				             <input type="text" id="storeName" name="store_name"  class="form-control" data-maxlength="32">
				             <span style="color:red;"> (温馨提示：店铺名称请填写英文，中文店铺名可能会导致店铺停用。)</span>
						 </div>
			       </div>
			       <div class="row" style="margin-bottom:15px;">
			             <div class="col-xs-3" style="text-align: right;padding-top: 15px;"><span style="color:red;">*</span>
			             	<select id='phone_type'>
								<option value='cn'>大陆</option>
								<option value='tw'>台湾</option>
							</select>
			             		手机：</div>
			             <div class="col-xs-9">
			             	<div id='phone_cn' class="input-group" style="display: block">
								<input type="text" id="phone" name="phone"  class="form-control" data-maxlength="11">
							</div>
							
							<div class="input-group" id="phone_tw" style="display: none">
						      <span class="input-group-addon" style="line-height: 15px;">+886</span>
						      <input type="text" id="phone_tw_input" name="phone_tw_input"  class="form-control" data-maxlength="14">
						    </div>
						 </div>
			       </div>
			       <!-- 
			       <div class="row" style="margin-bottom:15px;">
			             <div class="col-xs-3" style="text-align: right;padding-top: 15px;"><span style="color:red;">*</span>验证码：</div>
			             <div class="col-xs-3">
			             	<input type="text" id="phonecode" name="phonecode"  class="form-control" data-maxlength="6">
						 </div>
						 <div class="col-xs-6">
			             	<button type="button" class="btn btn-primary btn-small" id="phonecodesend" data-loading-text="发送中">发送验证码</button>
						 </div>
			       </div>
			        -->
			       <div class="row" style="margin-bottom:15px;">
			             <div class="col-xs-3" style="text-align: right;padding-top: 15px;"><span style="color:red;">*</span>邮箱：</div>
			             <div class="col-xs-9">
			             	<input type="text" id="email" name="email"  class="form-control" data-maxlength="64">
			             	<span style="color:red;"> (温馨提示：邮箱会作为ensogo后台的登录名，并且ensogo会将账号密码通过邮件发送给您，请正确填写你的邮箱。)</span>
						 </div>
			       </div>
			       <div class="row" style="margin-bottom:15px;">
			             <div class="col-xs-3" style="text-align: right;padding-top: 15px;"><span style="color:red;">*</span>联系人：</div>
			             <div class="col-xs-9">
			             	<input type="text" id="contactName" name="contact_name" class="form-control" data-maxlength="16">
						 </div>
			       </div>
        </div>
	</form>
	
</div>
