<?php
use eagle\modules\util\helpers\TranslateHelper;

if( isset(\Yii::$app->params["isOnlyForOneApp"]) and \Yii::$app->params["isOnlyForOneApp"]==1 ){
	$this->registerJsFile ( \Yii::getAlias ( '@web' ) . "/js/project/siteLogin.js");
	$this->registerJs ( "site.loginPage.initWidget();", \yii\web\View::POS_END );
}else{
	$this->registerJsFile ( \Yii::getAlias ( '@web' ) . "/js/project/siteLogin.js", [ 'depends' => [ 'yii\web\JqueryAsset' ] ] );
	$this->registerJs ( "site.loginPage.initWidget();", \yii\web\View::POS_READY );
	
}

$this->title = TranslateHelper::t('欢迎登录小老板平台');
?>
<?php if( isset(\Yii::$app->params["isOnlyForOneApp"]) and \Yii::$app->params["isOnlyForOneApp"]==1 ):?>
<style>
#loginForm > .form-group {
	display: block;
}

#loginForm > .form-group > label:first-child {
	width: 150px;
	text-align: right;
}

#loginForm > .form-group > label > i {
	color: red;
	padding-right: 5px;
}

.form-group {
	padding: 5px 0;
}

</style>

<div class="modal fade" id="login-modal" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title"><?= TranslateHelper::t('欢迎登录小老板平台') ?></h4>
      </div>
      <div class="modal-body">
        <div id="login-div">
			<div class="bbs_link">
				<a href="http://www.littleboss.com/" target="_blank" title="小老板官方网站"></a>
				<a href="http://bbs.littleboss.com/" target="_blank"
					title="小老板平台交流论坛"></a>
			</div>
			<div class="login_form" onkeydown="if(event.keyCode==13){site.loginPage.login();}">
				<form id="loginForm" action="<?php echo  \Yii::getAlias ( '@web' )."/"?>site/login" class="form-inline" method="post">
					<input type="hidden" name="_csrf" value="<?= \Yii::$app->request->getCsrfToken() ?>" />
					<div class="form-group">
						<label for="user_name"><?= TranslateHelper::t('账号(用户注册邮箱)') ?>：</label>
						<input type="text" name="user_name" id="user_name" class="form-control" />
					</div>
		
					<div class="form-group">
						<label for="password"><?= TranslateHelper::t('密码') ?>：</label>
						<input type="password" name="password" id="password" class="form-control" />
						<a href="javascript:void(0)" onclick="site.index.forgetPassView();" class=""><?= TranslateHelper::t('忘记密码') ?></a>
					</div>
		
					<div class="checkbox form-group">
						<label style="cursor: auto;"></label>
						<label><input type="checkbox" name="rememberMe"><?= TranslateHelper::t('记住我') ?></label>
					</div>
				</form>
			</div>
		</div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal"><?= TranslateHelper::t('取消') ?></button>
        <button type="button" class="btn btn-primary"><?= TranslateHelper::t('登录') ?></button>
      </div>
    </div><!-- /.modal-content -->
  </div><!-- /.modal-dialog -->
</div><!-- /.modal -->


<!-- /#login_panel -->

<?php else:?>
<style>
#loginForm > .form-group {
	display: block;
}

#loginForm > .form-group > label:first-child {
	width: 150px;
	text-align: right;
}

#loginForm > .form-group > label > i {
	color: red;
	padding-right: 5px;
}

.form-group {
	padding: 5px 0;
}

</style>

<!-- #login_panel -->
<div id="login_panel">
	<div class="bbs_link">
		<a href="http://www.littleboss.com/" target="_blank" title="小老板官方网站"></a>
		<a href="http://bbs.littleboss.com/" target="_blank"
			title="小老板平台交流论坛"></a>
	</div>
	<div class="login_form" onkeydown="if(event.keyCode==13){site.loginPage.login();}">
		<form id="loginForm" action="<?php echo  \Yii::getAlias ( '@web' )."/"?>site/login" class="form-inline" method="post">
			<input type="hidden" name="_csrf" value="<?= \Yii::$app->request->getCsrfToken() ?>" />
			<div class="form-group">
				<label for="user_name">账号(用户注册邮箱)：</label>
				<input type="text" name="user_name" id="user_name" class="form-control" />
			</div>

			<div class="form-group">
				<label for="password">密码：</label>
				<input type="password" name="password" id="password" class="form-control" />
				<a href="<?= \Yii::getAlias ( '@web' )."/"?>site/request-password-reset" class="">忘记密码</a>
			</div>

			<div class="checkbox form-group">
				<label style="cursor: auto;"></label>
				<label><input type="checkbox" name="rememberMe">两周内自动登录</label>
			</div>
			<div class="form-group">
				<label></label>
				<button class="btn btn-primary" type="button" onclick="site.loginPage.login();">提交</button>
			</div>
		</form>
	</div>
</div>
<!-- /#login_panel -->

<?php endif;?>