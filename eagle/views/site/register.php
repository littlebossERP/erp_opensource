<?php
use eagle\modules\util\helpers\TranslateHelper;

if( isset(\Yii::$app->params["isOnlyForOneApp"]) and \Yii::$app->params["isOnlyForOneApp"]==1 ){
	$this->registerJsFile ( \Yii::getAlias ( '@web' ) . "/js/project/siteRegister.js");
	$this->registerJs ( "site.register.initWidget();", \yii\web\View::POS_END );
}else{
	$this->registerJsFile ( \Yii::getAlias ( '@web' ) . "/js/project/siteRegister.js", ['depends' => [ 'yii\web\JqueryAsset' ] ] );
	$this->registerJs ( "site.register.initWidget();", \yii\web\View::POS_READY );
}

$this->title = TranslateHelper::t('注册小老板账号');
?>

<?php if( isset(\Yii::$app->params["isOnlyForOneApp"]) and \Yii::$app->params["isOnlyForOneApp"]==1 ):?>

<style>
.userArgreementBox .modal-body {
	max-height: 450px;
	overflow-y: auto;
}

#site-index-reg-form>.form-group {
	display: block;
}

#site-index-reg-form>.form-group>label {
	width: 150px;
	text-align: right;
}

#site-index-reg-form>.form-group>label>i {
	color: red;
	padding-right: 5px;
}

.form-group {
	padding: 5px 0;
}
</style>
<div class="modal fade max-modal-height" id="register-modal" role="dialog">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title"><?= TranslateHelper::t('注册小老板账号') ?></h4>
      </div>
      <div class="modal-body">
        <div id="register-div"  class="tab reg"
			onkeydown="if(event.keyCode==13){site.register.register();}">
			<form id="site-index-reg-form" class="form-inline"
				action="<?php echo \Yii::getAlias ( '@web' )."/"?>site/register"
				method="post">
				<div class="form-group">
					<label for="email">
						<i>*</i>
						<?= TranslateHelper::t('邮箱') ?>：
					</label>
					<input type="text" name="email" id="email" class="form-control" />
					<em></em>
					<button type="button" class="btn btn-xs btn-default"
						onclick="site.register.veriemail();" id="site-index-verify-email"><?= TranslateHelper::t('发送注册码') ?></button>
				</div>
				<div class="form-group">
					<label for="password">
						<i>*</i>
						<?= TranslateHelper::t('密码') ?>：
					</label>
					<input type="password" name="password" id="password"
						class="form-control" />
					<em></em>
				</div>
				<div class="form-group">
					<label for="repassword">
						<i>*</i>
						<?= TranslateHelper::t('确认密码') ?>：
					</label>
					<input type="password" name="repassword" id="repassword"
						class="form-control" />
					<em></em>
				</div>
				<div class="form-group">
					<label for="familyname"><?= TranslateHelper::t('姓名') ?>：</label>
					<input type="text" name="familyname" id="familyname"
						class="form-control" />
				</div>
				<div class="form-group">
					<label for="address"><?= TranslateHelper::t('地址') ?>：</label>
					<input type="text" name="address" id="address" class="form-control" />
				</div>
				<div class="form-group">
					<label for="company"><?= TranslateHelper::t('公司名称') ?>：</label>
					<input type="text" name="company" id="company" class="form-control" />
				</div>
				<div class="form-group">
					<label for="cellphone">
						<i>*</i>
						<?= TranslateHelper::t('手机') ?>：
					</label>
					<input type="text" name="cellphone" id="cellphone"
						class="form-control" />
					<em></em>
				</div>
				<div class="form-group">
					<label for="telephone">
						<i>*</i>
						<?= TranslateHelper::t('电话') ?>：
					</label>
					<input type="text" name="telephone" id="telephone"
						class="form-control" />
					<em></em>
				</div>
				<div class="form-group">
					<label for="qq">
						<i>*</i>
						<?= TranslateHelper::t('QQ') ?>：
					</label>
					<input type="text" name="qq" id="qq" class="form-control" />
					<em></em>
				</div>
				<div class="checkbox form-group">
					<label for="agreement"></label>
					<input type="checkbox" name="agreement" id="agreement" value='1' />
					<?= TranslateHelper::t('我已阅读并同意') ?>
					<a href="#" class="alert_hideDiv"
						onclick="site.register.openUserAgreementBox()"><?= TranslateHelper::t('《小老板用户协议》') ?></a>
				</div>
				
				<div class="form-group">
					<label for="registercode">
						<i>*</i>
						<?= TranslateHelper::t('注册码') ?>：
					</label>
					<input type="text" name="registercode" id="registercode"
						class="form-control" />
					<em></em>
				</div>
				<div class="form-group">
					<label for="authcode">
						<i>*</i>
						<?= TranslateHelper::t('验证码') ?>：
					</label>
					<input type="text" name="authcode" id="authcode"
						class="form-control" />
					<em></em>
					<i>
						<img src="<?php echo \Yii::getAlias ( '@web' )."/"?>site/vericode/"
							id="site-index-authcode" onclick="site.register.authcode();" alt="" />
					</i>
				</div>
			</form>
		</div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal"><?= TranslateHelper::t('取消') ?></button>
        <button type="button" class="btn btn-primary"><?= TranslateHelper::t('注册') ?></button>
      </div>
    </div><!-- /.modal-content -->
  </div><!-- /.modal-dialog -->
</div><!-- /.modal -->




<?php else :?>
<style>
.userArgreementBox .modal-body {
	max-height: 450px;
	overflow-y: auto;
}

#site-index-reg-form>.form-group {
	display: block;
}

#site-index-reg-form>.form-group>label {
	width: 150px;
	text-align: right;
}

#site-index-reg-form>.form-group>label>i {
	color: red;
	padding-right: 5px;
}

.form-group {
	padding: 5px 0;
}
</style>


<div class="tab reg" style=""
	onkeydown="if(event.keyCode==13){site.register.register();}">
	<form id="site-index-reg-form" class="form-inline"
		action="<?php echo \Yii::getAlias ( '@web' )."/"?>site/register"
		method="post">
		<div class="form-group">
			<label for="email">
				<i>*</i>
				邮箱：
			</label>
			<input type="text" name="email" id="email" class="form-control" />
			<em></em>
			<button type="button" class="btn btn-xs btn-default"
				onclick="site.register.veriemail();" id="site-index-verify-email">发送注册码</button>
		</div>
		<div class="form-group">
			<label for="password">
				<i>*</i>
				密码：
			</label>
			<input type="password" name="password" id="password"
				class="form-control" />
			<em></em>
		</div>
		<div class="form-group">
			<label for="repassword">
				<i>*</i>
				确认密码：
			</label>
			<input type="password" name="repassword" id="repassword"
				class="form-control" />
			<em></em>
		</div>
		<div class="form-group">
			<label for="familyname">姓名：</label>
			<input type="text" name="familyname" id="familyname"
				class="form-control" />
		</div>
		<div class="form-group">
			<label for="address">地址：</label>
			<input type="text" name="address" id="address" class="form-control" />
		</div>
		<div class="form-group">
			<label for="company">公司名称：</label>
			<input type="text" name="company" id="company" class="form-control" />
		</div>
		<div class="form-group">
			<label for="cellphone">
				<i>*</i>
				手机：
			</label>
			<input type="text" name="cellphone" id="cellphone"
				class="form-control" />
			<em></em>
		</div>
		<div class="form-group">
			<label for="telephone">
				<i>*</i>
				电话：
			</label>
			<input type="text" name="telephone" id="telephone"
				class="form-control" />
			<em></em>
		</div>
		<div class="form-group">
			<label for="qq">
				<i>*</i>
				QQ：
			</label>
			<input type="text" name="qq" id="qq" class="form-control" />
			<em></em>
		</div>
		<div class="checkbox form-group">
			<label for="agreement"></label>
			<input type="checkbox" name="agreement" id="agreement" value='1' />
			我已阅读并同意
			<a href="#" class="alert_hideDiv"
				onclick="site.register.openUserAgreementBox()">《小老板用户协议》</a>
		</div>
		
		<div class="form-group">
			<label for="registercode">
				<i>*</i>
				注册码：
			</label>
			<input type="text" name="registercode" id="registercode"
				class="form-control" />
			<em></em>
		</div>
		<div class="form-group">
			<label for="authcode">
				<i>*</i>
				验证码：
			</label>
			<input type="text" name="authcode" id="authcode"
				class="form-control" />
			<em></em>
			<i>
				<img src="<?php echo \Yii::getAlias ( '@web' )."/"?>site/vericode/"
					id="site-index-authcode" onclick="site.register.authcode();" alt="" />
			</i>
		</div>
		<div class="form-group">
			<LABEL></LABEL>
			<button type="button" class="btn btn-primary"
				onclick="site.register.register();"><?= TranslateHelper::t('注册')?></button>
		</div>
	</form>
</div>


<?php endif;?>

<div id='hiddenContent' style="display: none;">
	<div id="user-agreement-title">小老板用户协议</div>
	<div id="user-agreement-content">
		<p>一、用户协议的确认</p>
		<span>小老板（www.littleboss.com），是上海创邺信息科技有限公司独立拥有完全产权并全权负责运营的服务性网站。本服务协议限于且仅限于用户通过上海创邺信息科技有限公司提供的各项服务。同意并依照本协议在小老板登记注册的用户，方有资格享受小老板提供的各项服务，并接受本协议条款的约束。如一旦用户注册为小老板用户，即视作已了解并完全同意本服务协议各项内容。本协议内容包括协议正文以及所有小老板已经发布的或将来可能发布的各类规则。所有规则为本协议不可分割的组成部分，与协议正文具有同等法律效力。</span>
		<p>二、内容所有权</p>
		<span>小老板提供的网络服务内容包括文字、软件、声音、图片、录象、图表等。所有这些内容受版权、商标和其它财产所有权法律的保护。用户只有在获得小老板或其他相关权利人的授权之后才能使用这些内容，而不能擅自复制、再造这些内容，或创造与内容有关的派生产品。</span>
		<p>三、用户信息</p>
		<span>1、注册资格。
			用户须具有法定的相应权利能力和行为能力的自然人、法人或其他组织，能够独立承担法律责任。您完成注册程序或其他小老板同意的方式实际使用本平台服务时，即视为您确认自己具备主体资格，能够独立承担法律责任。若因您不具备主体资格，而导致的一切后果，由您及您的监护人自行承担。</span>
		<br />
		<span>2、注册资料。
			为保障用户的合法权益，避免在服务时因用户注册资料与真实情况不符而发生纠纷，请用户注册时务必按照真实、全面、准确的原则填写。对因用户自身原因而造成的不能服务情况，小老板将概不负责。如用户提供的资料包含有不正确或不真实的信息，有故意隐瞒、欺骗的未遂或已遂行为，并因此造成纠纷，小老板保留结束该用户使用服务资格乃至追究法律责任的权利。</span>
		<p>四、用户享有的权利与服务</p>
		<span>1、用户有权随时对自己的个人资料进行查询、修改和删除。从客户服务安全的角度考虑，帐号不能随意更改。</span>
		<br />
		<span>2、用户享有小老板所提供的各种服务内容。</span>
		<br />
		<span>3、小老板服务具体内容由上海创邺信息科技有限公司根据实际情况提供，小老板保留随时变更、中断或终止部分或全部网络服务的权利。</span>
		<br />
		<span>4、除非本服务协议另有其它明文规定，上海创邺信息科技有限公司所推出的新产品、新功能、新服务，均受到本服务协议之规范。</span>
		<br />
		<span>5、小老板仅提供相关的网络服务，除此之外与相关网络服务有关的设备（如电脑、调制解调器及其他与接入互联网有关的装置）及所需的费用（如为接入互联网而支付的电话费及上网费）均应由用户自行负担。</span>
		<p>五、用户信息的保护</p>
		<span>1、用户不应将其帐号、密码转让或出借予他人使用。如用户发现其帐号遭他人非法使用，应立即通知小老板。如因用户自己对帐号的不安全操作或使用所造成的一切纠纷和后果，小老板不承担任何责任。因黑客行为或用户的保管疏忽导致帐号、密码遭他人非法使用，小老板不承担任何责任。</span>
		<br />
		<span>2、小老板保证不对外公开或向第三方提供用户注册资料及用户在使用网络服务时存储在小老板的非公开内容，但下列情况除外：(1)事先获得用户的明确授权；(2)根据有关的法律法规要求；(3)按照相关政府主管部门的要求；(4)为维护社会公众的利益；(5)为维护小老板的合法权益。</span>
		<br />
		<span>3、若发现账号泄漏，请及时与小老板工作人员联系。</span>
		<p>六、协议终止</p>
		<span>
			出现以下情况时，小老板有权直接以注销账户的方式终止本协议:1、您注册信息中的主要内容不真实或不准确或不及时或不完整。2、本协议（含规则）变更时，您明示并通知小老板不愿接受新的服务协议；3、其它小老板认为应当终止服务的情况。
			<p>七、违约责任</p>
			<span>用户同意保障和维护小老板及其他用户的利益，如因用户违反有关法律、法规或本协议任何条款而给小老板或任何其他第三人造成损失的，用户同意承担所有责任，并承担由此造成的损害赔偿。</span>
			<p>八、法律管辖及适用</p>
			<span>本协议的订立、执行和解释及争议的解决均适用在中华人民共和国大陆地区适用之有效法律（但不包括其冲突法规则）。如发生本协议与适用之法律相抵触时，则这些条款将完全按法律规定重新解释，而其他条款继续有效。如缔约方就本协议内容或其执行发生任何争议，双方应尽力友好协商解决；协商不成时，任何一方均可向有管辖权的中华人民共和国大陆地区法院提起诉讼。</span>
			<p>九、其他</p>
			<span>1、本协议包含了您使用小老板需遵守的一般性规范，一般性规范如与特殊性规范不一致或有冲突，则特殊性规范具有优先效力。</span>
			<br />
			<span>2、如本协议中的任何条款无论因何种原因完全或部分无效或不具有执行力，本协议的其余条款仍应有效并且有约束力。</span>
			<br />
			<span>3、本协议适用中华人民共和国法律，因任何一方出现违反法律的行为而造成协议条款的不能执行，都应由责任方自行负责并补偿由此而给对方造成的损失。</span>
			<br />
			<span>4、小老板尊重您的合法权利，本协议及本站上发布的各类内容，均是为了更好地、更加便利地为您提供服务。本站欢迎您和社会各界提出意见和建议，小老板将虚心接受并适时修改本协议及本站上的各类规则。</span>
			<br />
			<span>5、本协议履行过程中，因您使用小老板服务产生争议应由小老板与您沟通并协商处理。协商不成时，双方均同意以小老板管理者住所地人民法院为管辖法院。</span>
			<br />
			<span>6、本协议最终解释权属于上海创邺信息科技有限公司。</span>
		</span>
	</div>
</div>
