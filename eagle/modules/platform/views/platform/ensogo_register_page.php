<?php
use eagle\modules\util\helpers\TranslateHelper;

$this->registerJsFile(\Yii::getAlias('@web')."/js/project/platform/EnsogoAccountsNewOrEdit.js", ['depends' => ['yii\web\JqueryAsset']]);
$this->registerJs("platform.EnsogoAccountsNeworedit.initWidget();" , \yii\web\View::POS_READY);

?>
<style>

.haomaTitle {
    position: relative;
    width: 604px;
    height: 80px;
    margin-left: 63px;
	margin-bottom: 25px;
    border-bottom: 1px solid #ddd;
}

.haomaTitle .haoma-inner {
    position: absolute;
    width: 100%;
    height: 25px;
    line-height: 25px;
    font-family: "微软雅黑";
    font-size: 18px;
    text-indent: 6px;
    border-left: 3px solid #59AfE4;
    margin-top: 50px;
    color: #616161;
}
input.new_txt{
	width: 284px;
    padding: 8px;
    
    line-height: 18px;
    font-size: 14px;
}

.item {
    float: left;
    text-align: right;
    width: 179px;
    padding-right: 10px;
    height: 44px;
    padding-top: 6px;
    height: 43px\9;
    _height: 44px;
    _padding-top: 8px;
    line-height: 20px;
    font-size: 14px;
}
.ipt_box {
    float: left;
    width: 306px;
    height: 50px;
    position: relative;
}
.box-row{
	display: inline-block;
}

.btnOK{
	background: #69b946;
    display: inline-block;
    height: 52px;
    width: 306px;
    text-align: center;
    cursor: pointer;
    font: 22px/52px "微软雅黑";
    color: #fff;
    -webkit-border-radius: 3px;
    -moz-border-radius: 3px;
    border-radius: 3px;
    border-style: solid;
    border-width: 1px;
    border-color: transparent;
}

.ipt_box span{
	margin: 5px 0 5px 0;
    line-height: 1.5;
}
.ensogo-box{
	padding-left:30%;
}
</style>

	<div class="ensogo-box">
		<div class="haomaTitle">
			<div class="haoma-inner" id="haoma-inner">Ensogo账号注册</div>
		</div>
		<form id="platform-EnsogoAccounts-form" action="<?php echo \Yii::getAlias('@web');?>/platform/EnsogoAccounts/save" method="post">
		<div class="box-row">
			<label class="item" for="storeName">Ensogo平台介绍</label>
			<div class="ipt_box">
				<span style="line-height: 30px;">
				<a href="http://www.littleboss.com/website/article/58.html" style="margin-right:10px;"  target="_blank" >商家进驻流程</a>
				<a href="http://www.littleboss.com/website/article/57.html"  target="_blank" >商户须知</a>
				</span>
			</div>
		</div>
		<!-- Usage as a class -->
		<div class="clearfix"></div>
		<div class="box-row">
			<label class="item" for="storeName">店铺名称</label>
			<div class="ipt_box">
				<input type="text" id="storeName" name="store_name"  class="form-control" data-maxlength="32">
				
			</div>
			
		</div>
		
		<div class="clearfix"></div>
		<div class="box-row">
			<div class="item"></div>
			<div class="ipt_box">
				<span style="color:red;">(温馨提示：店铺名称请填写英文，中文店铺名可能会导致店铺停用。)</span>
			</div>
			
		</div>
		<!-- Usage as a class -->
		<div class="clearfix"></div>
		<div class="box-row">
			
			<label class="item" for="phone">
				<select id='phone_type'>
					<option value='cn'>大陆</option>
					<option value='tw'>台湾</option>
				</select>
			手机</label>
			<div class="ipt_box">
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
		<div class="clearfix"></div>
		<div class="box-row">
			<label class="item" for="phonecode">验证码</label>
			<div class="ipt_box" style="width: 217px;">
				
				<input type="text" id="phonecode" name="phonecode"  class="form-control" data-maxlength="6">
			</div>
			
			<div style="float:left;margin-left:5px;margin-top:5px;">
					<button type="button" class="btn btn-primary btn-sm" id="phonecodesend" data-loading-text="发送中">发送验证码</button>
			</div>
			
		</div>
		 -->
		<div class="clearfix"></div>
		<div class="box-row">
			<label class="item" for="email">邮箱</label>
			<div class="ipt_box">
				<input type="text" id="email" name="email"  class="form-control" data-maxlength="64">
			</div>
		</div>
		
		<div class="clearfix"></div>
		<div class="box-row">
			<div class="item"></div>
			<div class="ipt_box">
				<span style="color:red;">(温馨提示：邮箱会作为ensogo后台的登录名，并且ensogo会将账号密码通过邮件发送给您，请正确填写你的邮箱。)</span>
			</div>
		</div>
		
		<div class="clearfix"></div>
		<div class="box-row">
			<label class="item" for="contactName">联系人</label>
			<div class="ipt_box">
				<input type="text" id="contactName" name="contact_name" class="form-control" data-maxlength="16">
			</div>
		</div>
		
		</form>
		<div class="clearfix"></div>
		<div class="box-row">
			<div  class="item"></div>
			<div class="ipt_box">
				<input type='button' name="btnOK" class="btnOK" value="提交注册" onclick="platform.EnsogoAccountsNeworedit.registerEnsogoAccount()"/>
			</div>
		</div>
		
		<div class="iv-alert alert-remind" style="margin-top:10px; width:604px;line-height:25px;margin-left: 60px;">
		注册成功后Ensogo会向您的注册邮箱发送邮件，邮件中含有Ensogo后台登陆网址，登陆账号和密码。此邮件可能被放入<span style="color: red;font-weight: bold;">垃圾箱</span>中。<br>
		注册完毕之后请上传至少<span style="color: red;font-weight: bold;">5</span>个商品，Ensogo才会对商品和店铺进行审核。
		
		</div>
	</div>
	

	<!--
<div id="ensogo-box" >
	
	<div id='ensogo-content-box'>
	
		<form id="platform-EnsogoAccounts-form" action="<?php echo \Yii::getAlias('@web');?>/platform/EnsogoAccounts/save" method="post">
		
	 
		
			<div>
				
				 <div class="row" style="margin-bottom:15px;">
							 <div class="col-xs-3" style="text-align: right;padding-top: 15px;"><span style="color:red;">*</span>店铺名称 ：</div>
							 <div class="col-xs-9">
								 <input type="text" id="storeName" name="store_name"  class="form-control" data-maxlength="32">
								 <span style="color:red;"> (温馨提示：店铺名称请填写英文，中文店铺名可能会导致店铺停用。)</span>
							 </div>
					   </div>
					   <div class="row" style="margin-bottom:15px;">
							 <div class="col-xs-3" style="text-align: right;padding-top: 15px;"><span style="color:red;">*</span>手机：</div>
							 <div class="col-xs-9">
								<input type="text" id="phone" name="phone"  class="form-control" data-maxlength="11">
							 </div>
					   </div>
					   <div class="row" style="margin-bottom:15px;">
							 <div class="col-xs-3" style="text-align: right;padding-top: 15px;"><span style="color:red;">*</span>验证码：</div>
							 <div class="col-xs-3">
								<input type="text" id="phonecode" name="phonecode"  class="form-control" data-maxlength="6">
							 </div>
							 <div class="col-xs-6">
								<button type="button" class="btn btn-primary btn-small" id="phonecodesend" data-loading-text="发送中">发送验证码</button>
							 </div>
					   </div>
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
		<center><input type='button' name="btnOK" class="btn btn-default btn-small" value="注册" onclick="platform.EnsogoAccountsNeworedit.registerEnsogoAccount()"/></center>
	</div>
</div>
-->