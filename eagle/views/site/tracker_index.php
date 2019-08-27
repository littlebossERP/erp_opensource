<!--banner-->
<div class="banner">
	<!--focus-->
	<div class="focus">
		<div id="focusPic" class="sliderwrapper">
			<div class="contentdiv" style="display:block;">
                <a href="#" class="clickAlert guanwang_index_smallboss_free"><div class="f_pic focus_1"></div></a>
			</div>
			<div class="contentdiv">
				<div class="f_pic focus_2"></div>
			</div>
			<div class="contentdiv">
				<div class="f_pic focus_3"></div>
			</div>
		</div>
		<div id="paginate-focusPic" class="pagination">
			<i class="toc"></i><i class="toc"></i><i class="toc"></i>
		</div>
	</div>
	<!--/focus-->

	<!--uesrLogin-->
	<div class="uesrLogin" onkeydown="if(event.keyCode==13){$('#guanwang-index-login').focus();}" >
		<div class="uesrLogin-m">
			<h3>用户登录</h3>

            <!--登录之显示的前-->
			<form id="guanwang-index-login-before">
				<input type="text" class="ueername" name="username_login" value="请输入邮箱" />
				<input type="password" class="password" name="password_login" value="请输入密码" />
				<input type="hidden" class="eaglesite log-eaglesite" name="eaglesite_login" value="1" />
				<div class="pass">
					<i><input type="checkbox" name="rememberMe" value="1"/> 记住密码</i>
					<em><a class="guanwang_index_forgetpass clickAlert" href="#">忘记密码?</a></em>
				</div>
				<div class="clear"></div>
				<input type="button" class="submit" id="guanwang-index-login"/>
				<div class="newuser">您是新用户？ <a href="#" class="clickAlert guanwang_index_smallboss_free">请点击此处免费注册</a></div>
			</form>
            <div class="loged" id="guanwang-index-login-after" style="display: none">
                <h4><span id="guanwang-index-login-after-name"></span> 欢迎登录小老板！</h4>
                <h4 id="guanwang-index-login-after-email"></h4>
                <p>
                    <a href="<?php echo ERP_URL;?>"><img width="138" height="39" src="<?php echo BASE_URL;?>images/project/trackerhome/index/index/log02.jpg" /></a>
                    <a href="<?php echo ERP_URL;?>/site/logout" onclick="guanwang.index.logout();"><img width="102" height="39" src="<?php echo BASE_URL;?>images/project/trackerhome/index/index/log01.jpg" /></a>
                </p>
            </div>



        </div>
	</div>
	<!--/uesrLogin-->

</div>
<script type="text/javascript">
featuredcontentslider.init({
	id: "focusPic",
	contentsource: ["inline", ""],
	toc: "#increment",
	nextprev: ["", ""], 
	revealtype: "mouseover",
	enablefade: [true, 0.15],
	autorotate: [true, 3000],
	delay: 150,
	onChange: function(previndex, curindex){}});
</script>
<!--/banner-->

<!--features-->

<div class="features-wrap feature1">
	<div class="w980">
		<div class="feature-content">
			<h1>全球物流无缝对接</h1>
			<p>
			整合全球UPU邮政最新、最详细的物流信息<br>
			<font style="color: #fc6c03;font-size: 20px;">160</font> 国物流信息统一查询，提供 <font style="color: #fc6c03;font-size: 20px;">89</font> 种语言翻译。
			</p>
			<div class="btn-version">
				<div style="float: left;">
					<BUTTON class="clickAlert guanwang_index_smallboss_free"></BUTTON>
				</div>
				<div style="float: left;">
				无需下载即可使用&gt;<br>
				版本信息：1.1.0 更新日期：2015/3/25
				</div>
			</div>
		</div>
	</div>
</div>

<div class="features-wrap feature2">
	<div class="w980">
		<div class="feature-content">
			<h1>一键智能查单</h1>
			<p>快速、全面查询订单物流情况，物流动向尽在掌握。<br>
			精准、智能归类异常订单，提升订单管理效率。</p>
			<div class="btn-version">
				<div style="float: left;">
					<BUTTON class="clickAlert guanwang_index_smallboss_free"></BUTTON>
				</div>
				<div style="float: left;">
				无需下载即可使用&gt;<br>
				版本信息：1.1.0 更新日期：2015/3/25
				</div>
			</div>
		</div>
	</div>
</div>

<div class="features-wrap feature3">
	<div class="w980">
		<div class="feature-content">
			<h1>订单自动关联</h1>
			<p>定时同步监测结果，物流订单自动更新。<br>
			查询记录永久保存，运单一键关联订单。</p>
			<div class="btn-version">
				<div style="float: left;">
					<BUTTON class="clickAlert guanwang_index_smallboss_free"></BUTTON>
				</div>
				<div style="float: left;">
				无需下载即可使用&gt;<br>
				版本信息：1.1.0 更新日期：2015/3/25
				</div>
			</div>
		</div>
	</div>
</div>
<!--/features-->