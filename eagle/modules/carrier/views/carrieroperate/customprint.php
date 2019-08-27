<?php 
// $this->registerJsFile(\Yii::getAlias('@web')."/js/project/carrier/customprint/jquery.min.js");
// $this->registerJsFile(\Yii::getAlias('@web')."/js/project/carrier/customprint/modernizr.min.js");
// $this->registerJsFile(\Yii::getAlias('@web')."/js/project/carrier/customprint/jquery-ui.min.js");
// $this->registerJsFile(\Yii::getAlias('@web')."/js/project/carrier/customprint/jquery-migrate.min.js");
// $this->registerJsFile(\Yii::getAlias('@web')."/js/project/carrier/customprint/bootstrap.min.js");
// $this->registerJsFile(\Yii::getAlias('@web')."/js/project/carrier/customprint/core.min.js");
// $this->registerJsFile(\Yii::getAlias('@web')."/js/project/carrier/customprint/jquery.sparkline.min.js");
// $this->registerJsFile(\Yii::getAlias('@web')."/js/project/carrier/customprint/app.min.js");
// $this->registerJsFile(\Yii::getAlias('@web')."/js/project/carrier/customprint/custom.js");

// $this->registerJsFile(\Yii::getAlias('@web')."/js/project/carrier/customprint/jquery.gritter.min.js");
// $this->registerJsFile(\Yii::getAlias('@web')."/js/project/carrier/customprint/bootbox.min.js");
// $this->registerJsFile(\Yii::getAlias('@web')."/js/project/carrier/customprint/jwerty.js");
// $this->registerJsFile(\Yii::getAlias('@web')."/js/project/carrier/customprint/run.js");
// $this->registerJsFile(\Yii::getAlias('@web')."/js/project/carrier/customprint/");



?>
<!DOCTYPE html>
<html>
<!-- START Head -->
<head>
<!-- START META SECTION -->
<meta charset="utf-8">
<title>小老板 ERP 设计打印模板</title>
<meta name="description" content="Adminre is a clean and flat admin theme build with Slim framework and Twig template engine.">
<meta name="viewport" content="width=device-width, user-scalable=no, initial-scale=1.0">

<link rel="stylesheet" type="text/css" href="<?= \Yii::getAlias('@web'); ?>/css/carrier/bootstrap.min.css" />
<link rel="stylesheet" type="text/css" href="<?= \Yii::getAlias('@web'); ?>/css/carrier/layout.min.css" />
<link rel="stylesheet" type="text/css" href="<?= \Yii::getAlias('@web'); ?>/css/carrier/uielement.min.css" />
<link rel="stylesheet" type="text/css" href="<?= \Yii::getAlias('@web'); ?>/css/carrier/jquery-ui.min.css" />
<link rel="stylesheet" type="text/css" href="<?= \Yii::getAlias('@web'); ?>/css/carrier/jquery.gritter.min.css" />
<link rel="stylesheet" type="text/css" href="<?= \Yii::getAlias('@web'); ?>/css/carrier/custom.css" />
<link rel="stylesheet" type="text/css" href="<?= \Yii::getAlias('@web'); ?>/css/carrier/print.css" />

<script src="<?= \Yii::getAlias('@web'); ?>/js/project/carrier/customprint/jquery.min.js"></script>
<script src="<?= \Yii::getAlias('@web'); ?>/js/project/carrier/customprint/modernizr.min.js"></script>
<script src="<?= \Yii::getAlias('@web'); ?>/js/project/carrier/customprint/jquery-ui.min.js"></script>
<script src="<?= \Yii::getAlias('@web'); ?>/js/project/carrier/customprint/jquery-migrate.min.js"></script>
<script src="<?= \Yii::getAlias('@web'); ?>/js/project/carrier/customprint/bootstrap.min.js"></script>
<script src="<?= \Yii::getAlias('@web'); ?>/js/project/carrier/customprint/core.min.js"></script>
<script src="<?= \Yii::getAlias('@web'); ?>/js/project/carrier/customprint/jquery.sparkline.min.js"></script>
<script src="<?= \Yii::getAlias('@web'); ?>/js/project/carrier/customprint/app.min.js"></script>
<script src="<?= \Yii::getAlias('@web'); ?>/js/project/carrier/customprint/custom.js"></script>

<script src="<?= \Yii::getAlias('@web'); ?>/js/project/carrier/customprint/jquery.gritter.min.js"></script>
<script src="<?= \Yii::getAlias('@web'); ?>/js/project/carrier/customprint/bootbox.min.js"></script>
<script src="<?= \Yii::getAlias('@web'); ?>/js/project/carrier/customprint/jwerty.js"></script>
<script src="<?= \Yii::getAlias('@web'); ?>/js/project/carrier/customprint/base64.js"></script>
<script src="<?= \Yii::getAlias('@web'); ?>/js/project/carrier/customprint/run.js"></script>

<!-- <script type="text/javascript" src="/js/datepicker_cn.js"></script> -->

</head>
<!--/ END Head -->
<!-- START Body -->
<body style="overflow:hidden; background:#3a4144 url(http://global.mabangerp.com/image/grid_bg_gray.png);">
	<!-- START Template Main -->
	<aside class="label-type">
		<div class="panel-group nm" id="type-group">
                				<div class="panel panel-default">
				<div class="panel-heading">
					<h4 class="panel-title">
						<a class="collapsed" href="#addressinfo" data-parent="#type-group"
							data-toggle="collapse"> <span class="arrow mr5"></span>地址信息
						</a>
					</h4>
				</div>
				<div class="panel-collapse collapse" id="addressinfo">
					<div class="panel-body">
						<!---->
						<div class="dragitem character" data-type="address"
							data-default-width="150" title="优先显示paypal地址，无paypal地址则显示平台地址">
							<strong class="title"><i class="ico-list-alt mr5"></i>收件人地址</strong>
							<span class="detail"> <span class="name"><div
										id="RECEIVER_NAME">Andrew Peachey</div></span> <span
								class="street"><div id="RECEIVER_ADDRESS">12 Great Gill,
									Walmer Bridge</div></span> <span class="area"><div
										id="RECEIVER_AREA"></div> <div id="RECEIVER_CITY">Preston</div>
									<div id="RECEIVER_PROVINCE">Lancashire</div></span> <span
								class="country"><div id="RECEIVER_COUNTRY_EN">United Kingdom</div><span
									class="country_cn" style="display: none;">(<div
											id="RECEIVER_COUNTRY_CN">英国</div>)
								</span></span> <span class="postcode"><div
										id="RECEIVER_ZIPCODE">PR45QP</div></span> <span
								class="tel1"><div id="RECEIVER_TELEPHONE">07701070799</div></span>
								<span class="tel2"><div id="RECEIVER_MOBILE">13888888888</div></span>
								<span class="email"><div id="RECEIVER_EMAIL">peachey2006@btinternet.com</div></span>
							</span>
						</div>
						<!---->
						<div class="dragitem character" data-type="address"
							data-default-width="150">
							<strong class="title"><i class="ico-list-alt mr5"></i>发件人地址</strong>
							<span class="detail"> <span class="name"><div id="SENDER_NAME">Mr
									Liu</div></span> <span class="street"><div
										id="SENDER_ADDRESS">Qibao town Ming road no. 555 on the 8th
									floor 2 floor</div></span> <span class="area"><div
										id="SENDER_AREA">Minhang district</div> <div
										id="SENDER_CITY">Shanghai</div> <div
										id="SENDER_PROVINCE">Shanghai</div></span> <span
								class="country"><div id="SENDER_COUNTRY_EN">China</div><span
									class="country_cn" style="display: none;">(<div
											id="SENDER_COUNTRY_CN">中国</div>)
								</span></span> <span class="postcode"><div
										id="SENDER_ZIPCODE">201101</div></span> <span class="tel1"><div
										id="SENDER_TELEPHONE">021-88888888</div></span> <span
								class="tel2"><div id="SENDER_MOBILE">13888888888</div></span>
								<span class="email"><div id="SENDER_EMAIL">admin@hotmail.com</div></span>
							</span>
						</div>
						<!---->
						<div class="dragitem character" data-type="barcode"
							data-default-width="142">
							<strong class="title"><i class="ico-barcode2 mr5"></i>收件人邮编条码</strong><span
								class="detail"><div id="RECEIVER_ZIPCODE_BARCODE">
								<img src="http://global.mabangerp.com/image/barcode.jpg"></div><span
								class="codemunber"><div id="RECEIVER_ZIPCODE_BARCODE_PREFIX"><span class="prefix dis-none"></span></div><div id="RECEIVER_ZIPCODE">zip434948</div></span></span>
						</div>
						<!---->
						<div class="dragitem character" data-type="barcode"
							data-default-width="142">
							<strong class="title"><i class="ico-barcode2 mr5"></i>EUB专用邮编条码</strong><span
								class="detail"><div id="EUB_RECEIVER_ZIPCODE_BARCODE">
								<img src="http://global.mabangerp.com/image/barcode.jpg"></div><span
								class="codemunber"><div id="RECEIVER_ZIPCODE_BARCODE_PREFIX"><span class="prefix dis-none"></span></div>ZIP <div id="EUB_RECEIVER_ZIPCODE">95501</div></span></span>
						</div>
						<!---->
						<div class="dragitem character" data-type="character"
							data-default-width="150">
							<strong class="title"><i class="ico-type mr5"></i>发件人公司名称</strong><span
								class="detail"><div id="SENDER_COMPANY_NAME">某某有限公司</div></span>
						</div>
						<!---->
						<div class="dragitem character" data-type="character"
							data-default-width="150">
							<strong class="title"><i class="ico-type mr5"></i>发件人姓名</strong><span
								class="detail"><div id="SENDER_NAME">Mr Liu</div></span>
						</div>
						<!---->
						<div class="dragitem character" data-type="character"
							data-default-width="150">
							<strong class="title"><i class="ico-type mr5"></i>发件人街道</strong><span
								class="detail"><div id="SENDER_ADDRESS">Qibao town Ming road
								no. 555 on the 8th floor 2 floor</div></span>
						</div>
						<!---->
						<div class="dragitem character" data-type="character"
							data-default-width="150">
							<strong class="title"><i class="ico-type mr5"></i>发件人国家</strong><span
								class="detail"><div id="SENDER_COUNTRY_EN">China</div></span>
						</div>
						<!---->
						<div class="dragitem character" data-type="character"
							data-default-width="150">
							<strong class="title"><i class="ico-type mr5"></i>发件人国家英文缩写</strong><span
								class="detail"><div id="SENDER_COUNTRY_EN_AB">CN</div></span>
						</div>
						<!---->
						<div class="dragitem character" data-type="character"
							data-default-width="150">
							<strong class="title"><i class="ico-type mr5"></i>发件人省份</strong><span
								class="detail"><div id="SENDER_PROVINCE_EN">Shanghai</div></span>
						</div>
						<!---->
						<div class="dragitem character" data-type="character"
							data-default-width="150">
							<strong class="title"><i class="ico-type mr5"></i>发件人城市</strong><span
								class="detail"><div id="SENDER_CITY_EN">Shanghai</div></span>
						</div>
						<!---->
						<div class="dragitem character" data-type="character"
							data-default-width="150">
							<strong class="title"><i class="ico-type mr5"></i>发件人地区</strong><span
								class="detail"><div id="SENDER_AREA_EN">Minhang district</div></span>
						</div>
						<!---->
						<div class="dragitem character" data-type="character"
							data-default-width="150">
							<strong class="title"><i class="ico-type mr5"></i>发件人邮编</strong><span
								class="detail"><div id="SENDER_ZIPCODE">201101</div></span>
						</div>
						<!---->
						<div class="dragitem character" data-type="character"
							data-default-width="150">
							<strong class="title"><i class="ico-type mr5"></i>发件人电话</strong><span
								class="detail"><div id="SENDER_TELEPHONE">021-88888888</div></span>
						</div>
						<!---->
						<div class="dragitem character" data-type="character"
							data-default-width="150">
							<strong class="title"><i class="ico-type mr5"></i>发件人手机</strong><span
								class="detail"><div id="SENDER_MOBILE">13888888888</div></span>
						</div>
						<!---->
						<div class="dragitem character" data-type="character"
							data-default-width="150">
							<strong class="title"><i class="ico-type mr5"></i>发件人邮箱</strong><span
								class="detail"><div id="SENDER_EMAIL">admin@hotmail.com</div></span>
						</div>
						<!---->
						<div class="dragitem character" data-type="character"
							data-default-width="150">
							<strong class="title"><i class="ico-type mr5"></i>收件人姓名</strong><span
								class="detail"><div id="RECEIVER_NAME">Andrew Peachey</div></span>
						</div>
						<!---->
						<div class="dragitem character" data-type="character"
							data-default-width="150" title="优先显示paypal地址，无paypal地址则显示平台地址">
							<strong class="title"><i class="ico-type mr5"></i>收件人街道</strong><span
								class="detail"><div id="RECEIVER_ADDRESS">12 Great Gill,
								Walmer Bridge</div></span>
						</div>
						<!---->
						<div class="dragitem character" data-type="character"
							data-default-width="150">
							<strong class="title"><i class="ico-type mr5"></i>收件人国家(中)</strong><span
								class="detail"><div id="RECEIVER_COUNTRY_CN">马来西亚</div></span>
						</div>
						<!---->
						<div class="dragitem character" data-type="character"
							data-default-width="150">
							<strong class="title"><i class="ico-type mr5"></i>收件人国家(英)</strong><span
								class="detail"><div id="RECEIVER_COUNTRY_EN">United Kingdom</div></span>
						</div>
						<!---->
						<div class="dragitem character" data-type="character"
							data-default-width="150">
							<strong class="title"><i class="ico-type mr5"></i>收件人国家英文简称</strong><span
								class="detail"><div id="RECEIVER_COUNTRY_EN_AB">EN</div></span>
						</div>
						<!---->
						<div class="dragitem character" data-type="character"
							data-default-width="150">
							<strong class="title"><i class="ico-type mr5"></i>收件人国家分区</strong><span
								class="detail"><div id="RECEIVER_COUNTRY_EXPRESS_AREA">1</div></span>
						</div>
						<!---->
						<div class="dragitem character" data-type="character"
							data-default-width="150">
							<strong class="title"><i class="ico-type mr5"></i>收件人国家分区2</strong><span
								class="detail">1</span>
						</div>
						<!---->
						<div class="dragitem character" data-type="character"
							data-default-width="150">
							<strong class="title"><i class="ico-type mr5"></i>收件人省份</strong><span
								class="detail"><div id="RECEIVER_PROVINCE">Lancashire</div></span>
						</div>
						<!---->
						<div class="dragitem character" data-type="character"
							data-default-width="150">
							<strong class="title"><i class="ico-type mr5"></i>收件人城市</strong><span
								class="detail"><div id="RECEIVER_CITY">Preston</div></span>
						</div>
						<!---->
						<div class="dragitem character" data-type="character"
							data-default-width="150">
							<strong class="title"><i class="ico-type mr5"></i>收件人地区/州</strong><span
								class="detail"><div id="RECEIVER_AREA">-</div></span>
						</div>
						<!---->
						<div class="dragitem character" data-type="character"
							data-default-width="150">
							<strong class="title"><i class="ico-type mr5"></i>收件人邮编</strong><span
								class="detail"><div id="RECEIVER_ZIPCODE">PR45QP</div></span>
						</div>
						<!---->
						<div class="dragitem character" data-type="character"
							data-default-width="150">
							<strong class="title"><i class="ico-type mr5"></i>收件人电话</strong><span
								class="detail"><div id="RECEIVER_TELEPHONE">07701070799</div></span>
						</div>
						<!---->
						<div class="dragitem character" data-type="character"
							data-default-width="150">
							<strong class="title"><i class="ico-type mr5"></i>收件人手机</strong><span
								class="detail"><div id="RECEIVER_MOBILE">13888888888</div></span>
						</div>
						<!---->
						<div class="dragitem character" data-type="character"
							data-default-width="150" title="优先显示paypal地址，无paypal地址则显示平台地址">
							<strong class="title"><i class="ico-type mr5"></i>收件人邮箱</strong><span
								class="detail"><div id="RECEIVER_EMAIL">peachey2006@btinternet.com</div></span>
						</div>
					</div>
				</div>
			</div>
			<div class="panel panel-default">
				<div class="panel-heading">
					<h4 class="panel-title">
						<a class="collapsed" href="#orderinfo" data-parent="#type-group"
							data-toggle="collapse"> <span class="arrow mr5"></span>订单物流信息
						</a>
					</h4>
				</div>
				<div class="panel-collapse collapse" id="orderinfo">
					<div class="panel-body">
						<!---->
						<div class="dragitem character" data-type="character"
							data-default-width="150">
							<strong class="title"><i class="ico-type mr5"></i>店铺</strong><span
								class="detail"><div id="ORDER_SHOP_NAME">FreeGift</div></span>
						</div>
						<!---->
						<div class="dragitem character" data-type="character"
							data-default-width="150">
							<strong class="title"><i class="ico-type mr5"></i>买家ID</strong><span
								class="detail"><div id="ORDER_BUYER_ID">Tom</div></span>
						</div>
						<!---->
						<div class="dragitem character" data-type="character"
							data-default-width="150">
							<strong class="title"><i class="ico-type mr5"></i>交易号</strong><span
								class="detail"><div id="ORDER_TRADE_NUMBER">23983742379833</div></span>
						</div>
						<!---->
						<div class="dragitem character" data-type="character"
							data-default-width="150">
							<strong class="title"><i class="ico-type mr5"></i>订单编号</strong><span
								class="detail"><div id="ORDER_CODE">1223228533466454</div></span>
						</div>
						<!---->
						<div class="dragitem character" data-type="character"
							data-default-width="150">
							<strong class="title"><i class="ico-type mr5"></i>订单金额（人民币）</strong><span
								class="detail"><div id="ORDER_TOTAL_FEE">430.45</div></span>
						</div>
						<!---->
						<div class="dragitem character" data-type="character"
							data-default-width="150">
							<strong class="title"><i class="ico-type mr5"></i>订单金额（原始货币）</strong><span
								class="detail"><div id="ORDER_TOTAL_FEE_ORIGIN">4.88</div></span>
						</div>
						<!---->
						<div class="dragitem character" data-type="character"
							data-default-width="150">
							<strong class="title"><i class="ico-type mr5"></i>币种</strong><span
								class="detail"><div id="ORDER_CURRENCY">USD</div></span>
						</div>
						<!---->
						<div class="dragitem character" data-type="character"
							data-default-width="150">
							<strong class="title"><i class="ico-type mr5"></i>实际重量</strong><span
								class="detail"><div id="ORDER_TOTAL_WEIGHT">230 g</div></span>
						</div>
						<!---->
						<div class="dragitem character" data-type="character"
							data-default-width="150">
							<strong class="title"><i class="ico-type mr5"></i>预估重量</strong><span
								class="detail"><div id="ORDER_TOTAL_WEIGHT_FORECAST">250 g</div></span>
						</div>
														<!---->
						<div class="dragitem character" data-type="character"
							data-default-width="150">
							<strong class="title"><i class="ico-type mr5"></i>打印时间</strong><span
								class="detail"><div id="ORDER_PRINT_TIME">2015-01-04 09:46:38</div></span>
						</div>
						<!---->
						<div class="dragitem character" data-type="character"
							data-default-width="150">
							<strong class="title"><i class="ico-type mr5"></i>订单备注</strong><span
								class="detail"><div id="ORDER_REMARK">修改订单状态为作废,订单编号=41019151724109,交易单号=62399-23</div></span>
						</div>
																					<!---->
						<div class="dragitem character" data-type="character"
							data-default-width="150">
							<strong class="title"><i class="ico-type mr5"></i>货运单号</strong><span
								class="detail"><div id="ORDER_EXPRESS_CODE">CD3390454534</div></span>
						</div>
						<!---->
						<div class="dragitem character" data-type="character"
							data-default-width="150">
							<strong class="title"><i class="ico-type mr5"></i>内部单号</strong><span
								class="detail"><div id="ORDER_EXPRESS_INTERNAL_CODE">CD3390454535</div></span>
						</div>
						<!---->
						<div class="dragitem character" data-type="character"
							data-default-width="150">
							<strong class="title"><i class="ico-type mr5"></i>平台物流方式</strong><span
								class="detail"><div id="ORDER_EXPRESS_WAY">燕文北京平邮</div></span>
						</div>
						<!---->
						<div class="dragitem character" data-type="character"
							data-default-width="150">
							<strong class="title"><i class="ico-type mr5"></i>货运方式</strong><span
								class="detail"><div id="ORDER_EXPRESS_NAME">北京燕文</div></span>
						</div>
						<!---->
						<div class="dragitem character" data-type="character"
							data-default-width="150">
							<strong class="title"><i class="ico-type mr5"></i>实付运费</strong><span
								class="detail"><div id="ORDER_SHIPPING_FEE">23.5</div></span>
						</div>
						<!---->
						<div class="dragitem character" data-type="character"
							data-default-width="150">
							<strong class="title"><i class="ico-type mr5"></i>预估运费</strong><span
								class="detail"><div id="ORDER_SHIPPING_FEE_FORECAST">20.0</div></span>
						</div>
						<!---->
						<div class="dragitem character" data-type="character"
							data-default-width="150">
							<strong class="title"><i class="ico-type mr5"></i>包材</strong><span
								class="detail"><div id="ORDER_PACKAGE">四号气泡袋</div></span>
						</div>
						<!---->
						<div class="dragitem character" data-type="character"
							data-default-width="150">
							<strong class="title"><i class="ico-type mr5"></i>是否含电池</strong><span
								class="detail"><div id="ORDER_HAS_BATTERY">有电池</div></span>
						</div>
														<!---->
						<div class="dragitem character" data-type="barcode"
							data-default-width="142">
							<strong class="title"><i class="ico-barcode2 mr5"></i>订单号条码</strong><span
								class="detail"><div id="ORDER_CODE_BARCODE">
								<img src="http://global.mabangerp.com/image/barcode.jpg"></div><span
								class="codemunber"><div id="ORDER_CODE_BARCODE_PREFIX"><span class="prefix dis-none"></span></div><div id="ORDER_CODE">201306072055562015</div></span></span>
						</div>
						<!---->
						<div class="dragitem character" data-type="barcode"
							data-default-width="142">
							<strong class="title"><i class="ico-barcode2 mr5"></i>货运单号条码</strong><span
								class="detail"><div id="ORDER_EXPRESS_CODE_BARCODE">
								<img src="http://global.mabangerp.com/image/barcode.jpg"></div><span
								class="codemunber"><div id="ORDER_EXPRESS_CODE_BARCODE_PREFIX"><span class="prefix dis-none"></span></div><div id="ORDER_EXPRESS_CODE">CD3390454534</div></span></span>
						</div>
						<!---->
						<div class="dragitem character" data-type="barcode"
							data-default-width="142">
							<strong class="title"><i class="ico-barcode2 mr5"></i>内部单号条码</strong><span
								class="detail"><div id="ORDER_EXPRESS_INTERNAL_CODE_BARCODE">
								<img src="http://global.mabangerp.com/image/barcode.jpg"></div><span
								class="codemunber"><div id="ORDER_EXPRESS_INTERNAL_CODE_BARCODE_PREFIX"><span class="prefix dis-none"></span></div><div id="ORDER_EXPRESS_INTERNAL_CODE">RR98834773945</div></span></span>
						</div>
                        						<!---->
						<div class="dragitem character" data-type="character"
							data-default-width="150">
							<strong class="title"><i class="ico-type mr5"></i>燕文国家分区(平)</strong><span
								class="detail">平12&nbsp;&nbsp;&nbsp;A&nbsp;&nbsp;&nbsp;序14</span>
						</div>
						<!---->
						<div class="dragitem character" data-type="character"
							data-default-width="150" title="燕文香港国家分区(平)">
							<strong class="title"><i class="ico-type mr5"></i>燕文香港国家分区(平)</strong><span
								class="detail">1</span>
						</div>
						<!---->
						<div class="dragitem character" data-type="character"
							data-default-width="150" title="燕文香港国家分区(挂)">
							<strong class="title"><i class="ico-type mr5"></i>燕文香港国家分区(挂)</strong><span
								class="detail">1</span>
						</div>
						<!---->
						<div class="dragitem character" data-type="character"
							data-default-width="150">
							<strong class="title"><i class="ico-type mr5"></i>燕文国家分区(挂)</strong><span
								class="detail">挂12&nbsp;&nbsp;&nbsp;A&nbsp;&nbsp;&nbsp;序14</span>
						</div>
						<!---->
						<div class="dragitem character" data-type="character"
							data-default-width="150">
							<strong class="title"><i class="ico-type mr5"></i>燕文客户编号</strong><span
								class="detail"><div id="DELIVERY_COMPANY_YANWEN_USERNAME">144660</div></span>
						</div>
						<!---->
						<div class="dragitem character" data-type="character"
							data-default-width="150">
							<strong class="title"><i class="ico-type mr5"></i>美国EUB省州分区</strong><span
								class="detail">1</span>
						</div>
						<!---->
						<div class="dragitem character" data-type="character"
							data-default-width="150">
							<strong class="title"><i class="ico-type mr5"></i>线上发货国家分区</strong><span
								class="detail">1</span>
						</div>
						<!---->
						<div class="dragitem character" data-type="character"
							data-default-width="150">
							<strong class="title"><i class="ico-type mr5"></i>国际挂号小包分拣区</strong><span
								class="detail">1</span>
						</div>
						<!---->
						<div class="dragitem character" data-type="character"
							data-default-width="150">
							<strong class="title"><i class="ico-type mr5"></i>国际挂号小包分区</strong><span
								class="detail">1</span>
						</div>
						<!---->
						<div class="dragitem character" data-type="character"
							data-default-width="150">
							<strong class="title"><i class="ico-type mr5"></i>国际平常小包分区</strong><span
								class="detail">1</span>
						</div>
						<!---->
						<div class="dragitem character" data-type="character"
							data-default-width="150">
							<strong class="title"><i class="ico-type mr5"></i>俄速通分拣分区</strong><span
								class="detail">6</span>
						</div>
						<!---->
						<div class="dragitem character" data-type="character"
							data-default-width="150">
							<strong class="title"><i class="ico-type mr5"></i>wish邮分区1</strong><span
								class="detail">1</span>
						</div>
						<!---->
						<div class="dragitem character" data-type="character"
							data-default-width="150">
							<strong class="title"><i class="ico-type mr5"></i>wish邮分区2(挂号)</strong><span
								class="detail">1</span>
						</div>
						<!---->
						<div class="dragitem character" data-type="character"
							data-default-width="150">
							<strong class="title"><i class="ico-type mr5"></i>wish邮分区3</strong><span
								class="detail">1</span>
						</div>
						<!---->
						<div class="dragitem character" data-type="character"
							data-default-width="150">
							<strong class="title"><i class="ico-type mr5"></i>wish邮分区4(平邮)</strong><span
								class="detail">1</span>
						</div>
						<!---->
						<div class="dragitem character" data-type="character"
							data-default-width="150" title="线上发货:仓库；4px回邮地址">
							<strong class="title"><i class="ico-type mr5"></i>扩展字段1</strong><span
								class="detail"><div id="ORDER_PLUS_FIELD_1">扩展字段1</div></span>
						</div>
						<!---->
						<div class="dragitem character" data-type="character"
							data-default-width="150" title="线上发货:速卖通物流服务方案；4px销售员代码">
							<strong class="title"><i class="ico-type mr5"></i>扩展字段2</strong><span
								class="detail"><div id="ORDER_PLUS_FIELD_2">扩展字段2</div></span>
						</div>
						<!---->
						<div class="dragitem character" data-type="character"
							data-default-width="150" title="4px客服员代码">
							<strong class="title"><i class="ico-type mr5"></i>扩展字段3</strong><span
								class="detail"><div id="ORDER_PLUS_FIELD_3">扩展字段3</div></span>
						</div>
						<!---->
						<div class="dragitem character" data-type="character"
							data-default-width="150" title="4px产品简称">
							<strong class="title"><i class="ico-type mr5"></i>扩展字段4</strong><span
								class="detail"><div id="ORDER_PLUS_FIELD_4">扩展字段4</div></span>
						</div>
						<!---->
						<div class="dragitem character" data-type="character"
							data-default-width="150" title="4px区域号">
							<strong class="title"><i class="ico-type mr5"></i>扩展字段5</strong><span
								class="detail"><div id="ORDER_PLUS_FIELD_5">扩展字段5</div></span>
						</div>
						<!---->
						<div class="dragitem character" data-type="character"
							data-default-width="150" title="4px国家分区">
							<strong class="title"><i class="ico-type mr5"></i>扩展字段6</strong><span
								class="detail"><div id="ORDER_PLUS_FIELD_6">扩展字段6</div></span>
						</div>
					</div>
				</div>
			</div>
                                				<div class="panel panel-default">
				<div class="panel-heading">
					<h4 class="panel-title">
						<a class="collapsed" href="#pickinginfo" data-parent="#type-group"
							data-toggle="collapse"> <span class="arrow mr5"></span>商品配货报关信息
						</a>
					</h4>
				</div>
				<div class="panel-collapse collapse" id="pickinginfo">
					<div class="panel-body">
                        								<!---->
						<div class="dragitem character" data-type="skulist"
							data-default-height="64">
							<strong class="title"><i class="ico-list-alt mr5"></i>配货清单</strong>
							<div class="detail">
								<table id="FULL_ITEMS_DETAIL_TABLE" class="skulist-table min">
									<thead style="font-size: 12px; font-family: Arial;">
										<tr>
											<th width="30" class="photo dis-none"><span>图片</span></th>
											<th class="sku"><span>SKU</span></th>
											<th class="sku_original dis-none"><span>原厂SKU</span></th>
											<th class="itemid dis-none"><span>itemId</span></th>
											<th width="35%" class="name"><span>名称</span></th>
											<th width="35%" class="name_en dis-none"><span>Name</span></th>
											<th class="warehouse dis-none"><span>仓库</span></th>
											<th class="position"><span>仓位</span></th>
											<th class="number"><span>数量</span></th>
											<th class="weight dis-none"><span>重量(g)</span></th>
											<th class="multi-property dis-none"><span>多属性</span></th>
											<th class="price dis-none"><span>单价</span></th>
											<th class="total dis-none"><span>小计</span></th>
										</tr>
									</thead>
									<tbody style="font-size: 12px; font-family: Arial;">
										<tr id="ITEM_LIST_DETAIL">
											<td width="30" class="photo dis-none"><span><div
														id="ITEM_LIST_DETAIL_PICTURE">
													<img src="http://global.mabangerp.com/image/product/img12.jpg"></div></span></td>
											<td class="sku"><div id="ITEM_LIST_DETAIL_SKU">
												<span title="KS0152d100_100">KS0152d100_100</span></div></td>
											<td class="sku_original dis-none"><div
													id="ITEM_LIST_DETAIL_ORIGINAL_SKU">
												<span title="KS0152d100">KS0152d100</span></div></td>
											<td class="itemid dis-none"><div
													id="ITEM_LIST_DETAIL_ITEM_ID">
												<span title="1011215412">1011215412</span></div></td>
											<td width="35%" class="name"><div
													id="ITEM_LIST_DETAIL_NAME_CN">
												<span title="玫红色 2014年夏季新品儿童连衣裙 高品质新款花朵纱裙童装">玫红色
													2014年夏季新品儿童连衣裙 高品质新款花朵纱裙童装</span></div></td>
											<td width="35%" class="name_en dis-none"><div
													id="ITEM_LIST_DETAIL_NAME_EN">
												<span
													title="Mei red dress in the summer of 2014 new children High quality new flowers veil children's clothes">Mei
													red dress in the summer of 2014 new children High quality
													new flowers veil children's clothes</span></div></td>
											<td class="warehouse dis-none"><div
													id="ITEM_LIST_DETAIL_WAREHOUSE">
												<span title="上海仓">上海仓</span></div></td>
											<td class="position"><div id="ITEM_LIST_DETAIL_GRID_CODE">
												<span title="无仓位">无仓位</span></div></td>
											<td class="number"><div id="ITEM_LIST_DETAIL_QUANTITY">
												<span>3</span></div></td>
											<td class="weight dis-none"><div
													id="ITEM_LIST_DETAIL_WEIGHT">
												<span>311.9</span></div></td>
											<td class="multi-property dis-none"><div
												id="ITEM_LIST_DETAIL_PROPERTY">
												<span>color:red</span></div></td>
											<td class="price dis-none"><div
													id="ITEM_LIST_DETAIL_PRICE">
												<span>80.0</span></div></td>
											<td class="total dis-none"><div
													id="ITEM_LIST_DETAIL_AMOUNT_PRICE">
												<span>240.0</span></div></td>
										</tr>
									</tbody>
									<tfoot
										style="font-size: 12px; font-family: Arial; text-align: right;">
										<tr>
											<td colspan="4">
												<p>
													<span class="total-sku pl5"><strong><div
																id="ITEM_LIST_TOTAL_KIND">1</div></strong> 个商品种类</span>
													<span class="total-number pl5"><strong><div
																id="ITEM_LIST_TOTAL_QUANTITY">3</div></strong> 件商品</span>
													<span class="weight pl5 dis-none">重量 <strong><div
																id="ITEM_LIST_TOTAL_WEIGHT">548.7</div></strong></span>
													<span class="price pl5 dis-none">金额 <strong><div
																id="ITEM_LIST_TOTAL_AMOUNT_PRICE">340.0</div></strong></span>
												</p>
											</td>
										</tr>
									</tfoot>
								</table>
							</div>
						</div>
                                                    							</div>
				</div>
			</div>
                				<div class="panel panel-default">
				<div class="panel-heading">
					<h4 class="panel-title">
						<a class="collapsed" href="#productinfo" data-parent="#type-group"
							data-toggle="collapse"> <span class="arrow mr5"></span>商品标签信息
						</a>
					</h4>
				</div>
				<div class="panel-collapse collapse" id="productinfo">
					<div class="panel-body">
						<!---->
						<div class="dragitem character" data-type="character"
							data-default-width="150">
							<strong class="title"><i class="ico-type mr5"></i>主SKU编号</strong><span
								class="detail"><div id="PRODUCT_SALE_SKU">L034665</div></span>
						</div>
						<!---->
						<div class="dragitem character" data-type="character"
							data-default-width="150">
							<strong class="title"><i class="ico-type mr5"></i>库存SKU编号</strong><span
								class="detail"><div id="PRODUCT_STOCK_SKU">L034665aL</div></span>
						</div>
						<!---->
						<div class="dragitem character" data-type="character"
							data-default-width="150">
							<strong class="title"><i class="ico-type mr5"></i>原厂编号</strong><span
								class="detail"><div id="PRODUCT_ORIGINAL_SKU">L034665</div></span>
						</div>
						<!---->
						<div class="dragitem character" data-type="character"
							data-default-width="150">
							<strong class="title"><i class="ico-type mr5"></i>仓库</strong><span
								class="detail"><div id="PRODUCT_WAREHOUSE">默认仓</div></span>
						</div>
						<!---->
						<div class="dragitem character" data-type="character"
							data-default-width="150">
							<strong class="title"><i class="ico-type mr5"></i>仓位</strong><span
								class="detail"><div id="PRODUCT_WAREHOUSE_GRID_CODE">2023214-03-02</div></span>
						</div>
						<!---->
						<div class="dragitem character" data-type="character"
							data-default-width="150">
							<strong class="title"><i class="ico-type mr5"></i>商品名称（中）</strong><span
								class="detail"><div id="PRODUCT_NAME_CN">玫红色 2014年夏季新品儿童连衣裙
								高品质新款花朵纱裙童装</div></span>
						</div>
						<!---->
						<div class="dragitem character" data-type="character"
							data-default-width="150">
							<strong class="title"><i class="ico-type mr5"></i>商品名称（英）</strong><span
								class="detail"><div id="PRODUCT_NAME_EN">Mei red dress in the
								summer of 2014 new children High quality new flowers veil
								children's clothes</div></span>
						</div>
														<!---->
						<div class="dragitem character" data-type="character"
							data-default-width="150">
							<strong class="title"><i class="ico-type mr5"></i>商品重量</strong><span
								class="detail"><div id="PRODUCT_WEIGHT">230 g</span>
						</div>
						<!---->
						<div class="dragitem character" data-type="character"
							data-default-width="150">
							<strong class="title"><i class="ico-type mr5"></i>采购员</strong><span
								class="detail"><div id="PRODUCT_BUYER_NAME">刘某某</div></span>
						</div>
						<!---->
						<div class="dragitem character" data-type="barcode"
							data-default-width="142">
							<strong class="title"><i class="ico-barcode2 mr5"></i>库存SKU条码</strong><span
								class="detail"><div id="PRODUCT_STOCK_SKU_BARCODE">
								<img src="http://global.mabangerp.com/image/barcode.jpg"></div><span
								class="codemunber"><div id="PRODUCT_STOCK_SKU_BARCODE_PREFIX"><span class="prefix dis-none"></span></div><div id="PRODUCT_STOCK_SKU">L034665aL</div></span></span>
						</div>
					</div>
				</div>
			</div>
			<!---->
			<div class="panel panel-default">
				<div class="panel-heading">
					<h4 class="panel-title">
						<a class="collapsed" href="#element" data-parent="#type-group"
							data-toggle="collapse"> <span class="arrow mr5"></span>构图元素
						</a>
					</h4>
				</div>
				<div class="panel-collapse collapse" id="element">
					<div class="panel-body">
						<!---->
						<div class="dragitem draw" data-type="line-x">
							<i class="ico-resize-horizontal"></i><strong class="title">水平线</strong>
						</div>
						<!---->
						<div class="dragitem draw" data-type="line-y">
							<i class="ico-resize-vertical"></i><strong class="title">垂直线</strong>
						</div>
						<!---->
						<div class="dragitem draw" data-type="customtext"
							data-default-width="100">
							<i class="ico-type"></i><strong class="title">自订文本</strong>
						</div>
						<!---->
						<div class="dragitem draw" data-type="circletext"
							data-default-width="50" data-default-height="50">
							<i class="ico-cc"></i><strong class="title">圆形标记</strong>
						</div>
						<!---->
						<div class="dragitem draw" data-type="onlineimage"
							data-default-width="50" data-default-height="50">
							<i class="ico-image"></i><strong class="title">在线图片</strong>
						</div>
					</div>
				</div>
			</div>
                				<!---->
			<div class="panel panel-default">
				<div class="panel-heading">
					<h4 class="panel-title">
						<a class="collapsed" href="#imagelibrary"
							data-parent="#type-group" data-toggle="collapse"> <span
							class="arrow mr5"></span>预设图片
						</a>
					</h4>
				</div>
				<div class="panel-collapse collapse" id="imagelibrary">
					<div class="panel-body">
						<div class="dragitem image" data-type="image"
							data-default-width="140" data-default-height="40">
							<img src="http://global.mabangerp.com/image/labelimg/chinapost-1.jpg">
						</div>
						<div class="dragitem image" data-type="image"
							data-default-width="140" data-default-height="70">
							<img src="http://global.mabangerp.com/image/labelimg/chinapost-2.jpg">
						</div>
						<div class="dragitem image" data-type="image"
							data-default-width="125" data-default-height="25">
							<img src="http://global.mabangerp.com/image/labelimg/chinapost-3.jpg">
						</div>
						<div class="dragitem image" data-type="image"
							data-default-width="160" data-default-height="80">
							<img src="http://global.mabangerp.com/image/labelimg/4px.png">
						</div>
						<div class="dragitem image" data-type="image"
							data-default-width="160" data-default-height="80">
							<img src="http://global.mabangerp.com/image/labelimg/permit_sga.png">
						</div>
						<div class="dragitem image" data-type="image"
							data-default-width="210" data-default-height="65">
							<img src="http://global.mabangerp.com/image/labelimg/pobox.png">
						</div>
						<div class="dragitem image" data-type="image"
							data-default-width="150" data-default-height="50">
							<img src="http://global.mabangerp.com/image/labelimg/australia-post.jpg">
						</div>
						<div class="dragitem image" data-type="image"
							data-default-width="125" data-default-height="35">
							<img src="http://global.mabangerp.com/image/labelimg/bpostlvs.jpg">
						</div>
						<div class="dragitem image" data-type="image"
							data-default-width="75" data-default-height="25">
							<img src="http://global.mabangerp.com/image/labelimg/eparcel.jpg">
						</div>
						<div class="dragitem image" data-type="image"
							data-default-width="75" data-default-height="25">
							<img src="http://global.mabangerp.com/image/labelimg/ePacket.jpg">
						</div>
						<div class="dragitem image" data-type="image"
							data-default-width="120" data-default-height="45">
							<img src="http://global.mabangerp.com/image/labelimg/esp.jpg">
						</div>
						<div class="dragitem image" data-type="image"
							data-default-width="50" data-default-height="65">
							<img src="http://global.mabangerp.com/image/labelimg/eubauscan.jpg">
						</div>
						<div class="dragitem image" data-type="image"
							data-default-width="120" data-default-height="60">
							<img src="http://global.mabangerp.com/image/labelimg/eubfr.jpg">
						</div>
						<div class="dragitem image" data-type="image"
							data-default-width="55" data-default-height="35">
							<img src="http://global.mabangerp.com/image/labelimg/eubfremail.jpg">
						</div>
						<div class="dragitem image" data-type="image"
							data-default-width="40" data-default-height="50">
							<img src="http://global.mabangerp.com/image/labelimg/eubfrscanright.jpg">
						</div>
						<div class="dragitem image" data-type="image"
							data-default-width="140" data-default-height="40">
							<img src="http://global.mabangerp.com/image/labelimg/eubhktitle.jpg">
						</div>
						<div class="dragitem image" data-type="image"
							data-default-width="80" data-default-height="100">
							<img src="http://global.mabangerp.com/image/labelimg/eub-postexpres.jpg">
						</div>
						<div class="dragitem image" data-type="image"
							data-default-width="90" data-default-height="50">
							<img src="http://global.mabangerp.com/image/labelimg/HSUUK.jpg">
						</div>
						<div class="dragitem image" data-type="image"
							data-default-width="65" data-default-height="65">
							<img src="http://global.mabangerp.com/image/labelimg/icon.jpg">
						</div>
						<div class="dragitem image" data-type="image"
							data-default-width="120" data-default-height="25">
							<img src="http://global.mabangerp.com/image/labelimg/ipz.jpg">
						</div>
						<div class="dragitem image" data-type="image"
							data-default-width="50" data-default-height="80">
							<img src="http://global.mabangerp.com/image/labelimg/no-signature.jpg">
						</div>
						<div class="dragitem image" data-type="image"
							data-default-width="175" data-default-height="65">
							<img src="http://global.mabangerp.com/image/labelimg/paravion.jpg">
						</div>
						<div class="dragitem image" data-type="image"
							data-default-width="150" data-default-height="70">
							<img src="http://global.mabangerp.com/image/labelimg/postnl.jpg">
						</div>
						<div class="dragitem image" data-type="image"
							data-default-width="155" data-default-height="65">
							<img src="http://global.mabangerp.com/image/labelimg/priority.jpg">
						</div>
						<div class="dragitem image" data-type="image"
							data-default-width="140" data-default-height="100">
							<img src="http://global.mabangerp.com/image/labelimg/royalmail.jpg">
						</div>
						<div class="dragitem image" data-type="image"
							data-default-width="90" data-default-height="60">
							<img src="http://global.mabangerp.com/image/labelimg/royalmail-2.jpg">
						</div>
						<div class="dragitem image" data-type="image"
							data-default-width="50" data-default-height="50">
							<img src="http://global.mabangerp.com/image/labelimg/helan3.jpg">
						</div>
						<div class="dragitem image" data-type="image"
							data-default-width="85" data-default-height="70">
							<img src="http://global.mabangerp.com/image/labelimg/swedishpackets.jpg">
						</div>
						<div class="dragitem image" data-type="image"
							data-default-width="100" data-default-height="92">
							<img src="http://global.mabangerp.com/image/labelimg/xujialiang.jpg">
						</div>
						<div class="dragitem image" data-type="image"
							data-default-width="125" data-default-height="35">
							<img src="http://global.mabangerp.com/image/labelimg/swisspost.jpg">
						</div>
						<div class="dragitem image" data-type="image"
							data-default-width="85" data-default-height="30">
							<img src="http://global.mabangerp.com/image/labelimg/wish-mail.jpg">
						</div>
						<div class="dragitem image" data-type="image"
							data-default-width="70" data-default-height="80">
							<img src="http://global.mabangerp.com/image/labelimg/EIBFrscan.jpg">
						</div>
						<div class="dragitem image" data-type="image"
							data-default-width="55" data-default-height="60">
							<img src="http://global.mabangerp.com/image/labelimg/lianyt.jpg">
						</div>
						<div class="dragitem image" data-type="image"
							data-default-width="60" data-default-height="60">
							<img src="http://global.mabangerp.com/image/labelimg/4px3.jpg">
						</div>
						<div class="dragitem image" data-type="image"
							data-default-width="120" data-default-height="55">
							<img src="http://global.mabangerp.com/image/labelimg/met.jpg">
						</div>
						<div class="dragitem image" data-type="image"
							data-default-width="120" data-default-height="65">
							<img src="http://global.mabangerp.com/image/labelimg/met2.jpg">
						</div>
						<div class="dragitem image" data-type="image" 
							data-default-width="100" data-default-height="45">
							<img src="http://global.mabangerp.com/image/labelimg/ruston_logo.jpg">
						</div>
					</div>
				</div>
			</div>
                			</div>
	</aside>
	<aside class="label-set">
		<div class="panel panel-default tab-content">
			<div class="panel-heading">
				<p class="panel-title"></p>
			</div>
			<div class="panel-toolbar-wrapper">
				<div class="panel-toolbar">
					<ul class="nav nav-tabs nav-justified semibold">
						<li class="title"><a data-toggle="tab" href="#titelset">标题</a></li>
						<li class="detail"><a data-toggle="tab" href="#detailset">内容</a></li>
						<li class="field-address"><a data-toggle="tab"
							href="#fieldset-address">字段</a></li>
						<li class="text"><a data-toggle="tab" href="#textset">文字</a></li>
						<li class="imgurl"><a data-toggle="tab" href="#imageurl">路径</a></li>
						<li class="border"><a data-toggle="tab" href="#borderset">边框</a></li>
						<li class="table"><a data-toggle="tab" href="#tableset">表格</a></li>
						<li class="field-sku"><a data-toggle="tab" href="#fieldset-sku">字段</a></li>
						<li class="field-declare"><a data-toggle="tab"
							href="#fieldset-declare">字段</a></li>
					</ul>
				</div>
			</div>
			<div class="panel-body tab-pane" id="titelset">
				<div class="form-group">
					<label class="checkbox-inline semibold"><input type="checkbox"
						value="" name="" id="viewTitle" checked>显示标题</label>
				</div>
				<div class="title-set">
					<div class="form-group">
						<p class="control-label mb5">标题文本:</p>
						<input type="text" value="" name="" class="form-control"
							id="titleName">
					</div>
					<div class="form-group">
						<label class="checkbox-inline semibold"><input type="checkbox"
							value="" name="" id="titleNowrap">标题整行显示</label>
					</div>
					<div class="moreinfo dis-none">
						<div class="form-group">
							<p class="control-label mb5">标题对齐方式:</p>
							<select class="form-control" id="titleAlign">
								<option value="left" selected>左对齐</option>
								<option value="center">中对齐</option>
								<option value="right">右对齐</option>
							</select>
						</div>
						<div class="form-group">
							<p class="control-label mb5">标题与内容间距:</p>
							<select class="form-control" id="titlePaddingBottom">
								<option value="0" selected>0px</option>
								<option value="1">1px</option>
								<option value="2">2px</option>
								<option value="3">3px</option>
								<option value="4">4px</option>
								<option value="5">5px</option>
							</select>
						</div>
					</div>
					<div class="form-group">
						<p class="control-label mb5">标题文字字体:</p>
						<select class="form-control" id="titleFontFamily">
							<option value="Arial" selected>Arial</option>
							<option value="Helvetica">Helvetica</option>
							<option value="Tahoma">Tahoma</option>
							<option value="Verdana">Verdana</option>
							<option value="Lucida Grande">Lucida Grande</option>
							<option value="Times New Roman">Times New Roman</option>
							<option value="Georgia">Georgia</option>
						</select>
					</div>
					<div class="form-group">
						<p class="control-label mb5">标题文字尺寸:</p>
						<select class="form-control" id="titleFontSize">
							<option value="6px">6px</option>
							<option value="8px">8px</option>
							<option value="9px">9px</option>
							<option value="10px">10px</option>
							<option value="11px">11px</option>
							<option value="12px" selected>12px</option>
							<option value="14px">14px</option>
							<option value="18px">18px</option>
							<option value="24px">24px</option>
							<option value="30px">30px</option>
							<option value="36px">36px</option>
							<option value="48px">48px</option>
							<option value="60px">60px</option>
							<option value="72px">72px</option>
						</select>
					</div>
					<div class="form-group">
						<p class="control-label mb5">标题行距:</p>
						<select class="form-control" id="titleLineHeight">
							<option value="1" selected>1倍</option>
							<option value="1.5">1.5倍</option>
							<option value="2">2倍</option>
							<option value="2.5">2.5倍</option>
							<option value="3">3倍</option>
						</select>
					</div>
					<div class="form-group">
						<label class="checkbox-inline semibold"><input type="checkbox"
							value="" name="" id="titleFontWeight" checked>标题文字加粗</label>
					</div>
				</div>
			</div>
			<div class="panel-body tab-pane" id="detailset">
				<div class="form-group">
					<p class="control-label mb5">内容文字对齐方式:</p>
					<select class="form-control" id="detailAlign">
						<option value="left" selected>左对齐</option>
						<option value="center">中对齐</option>
						<option value="right">右对齐</option>
					</select>
				</div>
				<div class="form-group">
					<p class="control-label mb5">内容文字字体:</p>
					<select class="form-control" id="detailFontFamily">
						<option value="Arial" selected>Arial</option>
						<option value="Helvetica">Helvetica</option>
						<option value="Tahoma">Tahoma</option>
						<option value="Verdana">Verdana</option>
						<option value="Lucida Grande">Lucida Grande</option>
						<option value="Times New Roman">Times New Roman</option>
						<option value="Georgia">Georgia</option>
					</select>
				</div>
				<div class="form-group">
					<p class="control-label mb5">内容文字尺寸:</p>
					<select class="form-control" id="detaillFontSize">
						<option value="6px">6px</option>
						<option value="8px">8px</option>
						<option value="9px">9px</option>
						<option value="10px">10px</option>
						<option value="11px">11px</option>
						<option value="12px" selected>12px</option>
						<option value="14px">14px</option>
						<option value="18px">18px</option>
						<option value="24px">24px</option>
						<option value="30px">30px</option>
						<option value="36px">36px</option>
						<option value="48px">48px</option>
						<option value="60px">60px</option>
						<option value="72px">72px</option>
					</select>
				</div>
				<div class="form-group">
					<p class="control-label mb5">内容文字行距:</p>
					<select class="form-control" id="detailLineHeight">
						<option value="1" selected>1倍</option>
						<option value="1.5">1.5倍</option>
						<option value="2">2倍</option>
						<option value="2.5">2.5倍</option>
						<option value="3">3倍</option>
					</select>
				</div>
				<div class="form-group">
					<label class="checkbox-inline semibold"><input type="checkbox"
						value="" name="" id="detailFontWeight">内容文字加粗</label>
				</div>
			</div>
			<div class="panel-body tab-pane" id="textset">
				<div class="form-group">
					<p class="control-label mb5">自定义文本:</p>
					<textarea value="" class="form-control" id="textDetail" rows="5"
						placeholder="请输入文本内容"></textarea>
				</div>
				<div class="form-group">
					<p class="control-label mb5">文本对齐方式:</p>
					<select class="form-control" id="textAlign">
						<option value="left" selected>左对齐</option>
						<option value="center">中对齐</option>
						<option value="right">右对齐</option>
					</select>
				</div>
				<div class="form-group">
					<p class="control-label mb5">文本字体:</p>
					<select class="form-control" id="textFontFamily">
						<option value="Arial" selected>Arial</option>
						<option value="Helvetica">Helvetica</option>
						<option value="Tahoma">Tahoma</option>
						<option value="Verdana">Verdana</option>
						<option value="Lucida Grande">Lucida Grande</option>
						<option value="Times New Roman">Times New Roman</option>
						<option value="Georgia">Georgia</option>
					</select>
				</div>
				<div class="form-group">
					<p class="control-label mb5">文本文字尺寸:</p>
					<select class="form-control" id="textFontSize">
						<option value="6px">6px</option>
						<option value="8px">8px</option>
						<option value="9px">9px</option>
						<option value="10px">10px</option>
						<option value="11px">11px</option>
						<option value="12px" selected>12px</option>
						<option value="14px">14px</option>
						<option value="18px">18px</option>
						<option value="24px">24px</option>
						<option value="30px">30px</option>
						<option value="36px">36px</option>
						<option value="48px">48px</option>
						<option value="60px">60px</option>
						<option value="72px">72px</option>
					</select>
				</div>
				<div class="form-group">
					<p class="control-label mb5">文本行距:</p>
					<select class="form-control" id="textLineHeight">
						<option value="1" selected>1倍</option>
						<option value="1.5">1.5倍</option>
						<option value="2">2倍</option>
						<option value="2.5">2.5倍</option>
						<option value="3">3倍</option>
					</select>
				</div>
				<div class="form-group">
					<label class="checkbox-inline semibold"><input type="checkbox"
						value="" name="" id="textFontWeight">文本文字加粗</label>
				</div>
				<div class="form-group">
					<label class="checkbox-inline semibold"><input type="checkbox"
						value="" name="" id="textCheckBox">含复选框 (<i
						class="ico-checkbox-unchecked3 pl5 pr5"></i>)</label>
				</div>
				<div class="form-group dis-none">
					<select class="form-control" id="checkBoxType">
						<option value="ico-checkbox-unchecked2" selected>未勾选</option>
						<option value="ico-checkbox">已勾选</option>
						<option value="ico-checkbox">已叉选</option>
					</select>
				</div>
			</div>
			<div class="panel-body tab-pane" id="borderset">
				<div class="form-group">
					<label class="checkbox-inline semibold"><input type="checkbox"
						id="borderTop" name="" value="">显示上边框</label>
					<div class="row mt5">
						<div class="col-sm-6">
							<p class="control-label mb5">上边框厚度:</p>
							<select class="form-control" id="borderTopWidth" disabled>
								<option value="0px">0px</option>
								<option value="1px" selected>1px</option>
								<option value="2px">2px</option>
								<option value="3px">3px</option>
								<option value="4px">4px</option>
								<option value="5px">5px</option>
							</select>
						</div>
						<div class="col-sm-6">
							<p class="control-label mb5">上边距:</p>
							<select class="form-control" id="paddingTop" disabled>
								<option value="0px" selected>0px</option>
								<option value="1px">1px</option>
								<option value="2px">2px</option>
								<option value="3px">3px</option>
								<option value="4px">4px</option>
								<option value="5px">5px</option>
								<option value="6px">6px</option>
								<option value="7px">7px</option>
								<option value="8px">8px</option>
								<option value="9px">9px</option>
								<option value="10px">10px</option>
							</select>
						</div>
					</div>
				</div>
				<div class="form-group">
					<label class="checkbox-inline semibold"><input type="checkbox"
						id="borderBottom" name="" value="">显示下边框</label>
					<div class="row mt5">
						<div class="col-sm-6">
							<p class="control-label mb5">下边框厚度:</p>
							<select class="form-control" id="borderBottomWidth" disabled>
								<option value="0px">0px</option>
								<option value="1px" selected>1px</option>
								<option value="2px">2px</option>
								<option value="3px">3px</option>
								<option value="4px">4px</option>
								<option value="5px">5px</option>
							</select>
						</div>
						<div class="col-sm-6">
							<p class="control-label mb5">下边距:</p>
							<select class="form-control" id="paddingBottom" disabled>
								<option value="0px" selected>0px</option>
								<option value="1px">1px</option>
								<option value="2px">2px</option>
								<option value="3px">3px</option>
								<option value="4px">4px</option>
								<option value="5px">5px</option>
								<option value="6px">6px</option>
								<option value="7px">7px</option>
								<option value="8px">8px</option>
								<option value="9px">9px</option>
								<option value="10px">10px</option>
							</select>
						</div>
					</div>
				</div>
				<div class="form-group">
					<label class="checkbox-inline semibold"><input type="checkbox"
						id="borderLeft" name="" value="">显示左边框</label>
					<div class="row mt5">
						<div class="col-sm-6">
							<p class="control-label mb5">左边框厚度:</p>
							<select class="form-control" id="borderLeftWidth" disabled>
								<option value="0px">0px</option>
								<option value="1px" selected>1px</option>
								<option value="2px">2px</option>
								<option value="3px">3px</option>
								<option value="4px">4px</option>
								<option value="5px">5px</option>
							</select>
						</div>
						<div class="col-sm-6">
							<p class="control-label mb5">左边距:</p>
							<select class="form-control" id="paddingLeft" disabled>
								<option value="0px" selected>0px</option>
								<option value="1px">1px</option>
								<option value="2px">2px</option>
								<option value="3px">3px</option>
								<option value="4px">4px</option>
								<option value="5px">5px</option>
								<option value="6px">6px</option>
								<option value="7px">7px</option>
								<option value="8px">8px</option>
								<option value="9px">9px</option>
								<option value="10px">10px</option>
							</select>
						</div>
					</div>
				</div>
				<div class="form-group">
					<label class="checkbox-inline semibold"><input type="checkbox"
						id="borderRight" name="" value="">显示右边框</label>
					<div class="row mt5">
						<div class="col-sm-6">
							<p class="control-label mb5">右边框厚度:</p>
							<select class="form-control" id="borderRightWidth" disabled>
								<option value="0px">0px</option>
								<option value="1px" selected>1px</option>
								<option value="2px">2px</option>
								<option value="3px">3px</option>
								<option value="4px">4px</option>
								<option value="5px">5px</option>
							</select>
						</div>
						<div class="col-sm-6">
							<p class="control-label mb5">右边距:</p>
							<select class="form-control" id="paddingRight" disabled>
								<option value="0px" selected>0px</option>
								<option value="1px">1px</option>
								<option value="2px">2px</option>
								<option value="3px">3px</option>
								<option value="4px">4px</option>
								<option value="5px">5px</option>
								<option value="6px">6px</option>
								<option value="7px">7px</option>
								<option value="8px">8px</option>
								<option value="9px">9px</option>
								<option value="10px">10px</option>
							</select>
						</div>
					</div>
				</div>
			</div>
			<div class="panel-body tab-pane" id="line-x">
				<div class="form-group">
					<p class="control-label mb5">线条宽度(单位:px):</p>
					<span class="customnum"> <a href="javascript:void(0);"
						class="subtract ico-minus-sign-alt"></a> <input id="xLineWidth"
						type="text" class="form-control"
						onbeforepaste="clipboardData.setData('text',clipboardData.getData('text').replace(/[^\d]/g,''))"
						onkeyup="value=value.replace(/[^\d]/g,'') " value="366"> <a
						href="javascript:void(0);" class="add ico-plus-sign2"></a>
					</span>
					<button type="button" class="btn btn-primary ml10" id="setMaxWidth">设为100%</button>
				</div>
				<div class="form-group">
					<p class="control-label mb5">线条类型:</p>
					<select class="form-control" id="xLineStyle">
						<option value="solid" selected>实线</option>
						<option value="dotted">点状线</option>
						<option value="dashed">虚线</option>
						<option value="double">双实线</option>
					</select>
				</div>
				<div class="form-group">
					<p class="control-label mb5">线条粗细:</p>
					<select class="form-control" id="xLineWeight">
						<option value="1px">1px</option>
						<option value="2px" selected>2px</option>
						<option value="3px">3px</option>
						<option value="4px">4px</option>
						<option value="5px">5px</option>
						<option value="6px">6px</option>
					</select>
				</div>
			</div>
			<div class="panel-body tab-pane" id="line-y">
				<div class="form-group">
					<p class="control-label mb5">线条高度(单位:px):</p>
					<span class="customnum"> <a href="javascript:void(0);"
						class="subtract ico-minus-sign-alt"></a> <input id="yLineHeight"
						type="text" class="form-control"
						onbeforepaste="clipboardData.setData('text',clipboardData.getData('text').replace(/[^\d]/g,''))"
						onkeyup="value=value.replace(/[^\d]/g,'') " value="366"> <a
						href="javascript:void(0);" class="add ico-plus-sign2"></a>
					</span>
					<button type="button" class="btn btn-primary" id="setMaxHeight">设为100%</button>
				</div>
				<div class="form-group">
					<p class="control-label mb5">线条类型:</p>
					<select class="form-control" id="yLineStyle">
						<option value="solid" selected>实线</option>
						<option value="dotted">点状线</option>
						<option value="dashed">虚线</option>
						<option value="double">双实线</option>
					</select>
				</div>
				<div class="form-group">
					<p class="control-label mb5">线条粗细:</p>
					<select class="form-control" id="yLineWeight">
						<option value="1px">1px</option>
						<option value="2px" selected>2px</option>
						<option value="3px">3px</option>
						<option value="4px">4px</option>
						<option value="5px">5px</option>
						<option value="6px">6px</option>
					</select>
				</div>
			</div>
			<div class="panel-body tab-pane" id="circletext">
				<div class="form-group">
					<p class="control-label mb5">圆边粗细:</p>
					<select class="form-control" id="circleBorderWidth">
						<option value="1px">1px</option>
						<option value="2px" selected>2px</option>
						<option value="3px">3px</option>
						<option value="4px">4px</option>
						<option value="5px">5px</option>
						<option value="6px">6px</option>
					</select>
				</div>
				<div class="form-group">
					<p class="control-label mb5">内容文本:</p>
					<input type="text" value="A" name="" class="form-control"
						id="circleText">
				</div>
				<div class="form-group">
					<p class="control-label mb5">内容文字字体:</p>
					<select class="form-control" id="circleFontFamily">
						<option value="Arial" selected>Arial</option>
						<option value="Helvetica">Helvetica</option>
						<option value="Tahoma">Tahoma</option>
						<option value="Verdana">Verdana</option>
						<option value="Lucida Grande">Lucida Grande</option>
						<option value="Times New Roman">Times New Roman</option>
						<option value="Georgia">Georgia</option>
					</select>
				</div>
				<div class="form-group">
					<p class="control-label mb5">内容文字尺寸:</p>
					<select class="form-control" id="circleFontSize">
						<option value="6px">6px</option>
						<option value="8px">8px</option>
						<option value="9px">9px</option>
						<option value="10px">10px</option>
						<option value="11px">11px</option>
						<option value="12px">12px</option>
						<option value="14px">14px</option>
						<option value="18px">18px</option>
						<option value="24px" selected>24px</option>
						<option value="30px">30px</option>
						<option value="36px">36px</option>
						<option value="48px">48px</option>
						<option value="60px">60px</option>
						<option value="72px">72px</option>
					</select>
				</div>
				<div class="form-group">
					<label class="checkbox-inline semibold"><input type="checkbox"
						value="" name="" id="circleFontWeight" checked>文字内容加粗</label>
				</div>
			</div>
			<div class="panel-body tab-pane" id="barcode">
				<div class="form-group">
					<label class="checkbox-inline semibold"><input type="checkbox"
						value="" name="" id="viewCodeNum">显示条码代码</label>
				</div>
				<div class="codenum-set">
					<div class="form-group">
						<p class="control-label mb5">条码代码文字对齐方式:</p>
						<select class="form-control" id="codeNumAlign">
							<option value="left">左对齐</option>
							<option value="center" selected>中对齐</option>
							<option value="right">右对齐</option>
						</select>
					</div>
					<div class="form-group">
						<p class="control-label mb5">条码代码文字尺寸:</p>
						<select class="form-control" id="codeNumFontSize">
							<option value="6px">6px</option>
							<option value="8px">8px</option>
							<option value="9px">9px</option>
							<option value="10px">10px</option>
							<option value="11px">11px</option>
							<option value="12px" selected>12px</option>
							<option value="14px">14px</option>
						</select>
					</div>
					<div class="form-group">
						<label class="checkbox-inline semibold"><input type="checkbox"
							value="" name="" id="codeNumFontWeight" checked>条码代码文字加粗</label>
					</div>
                    <div class="form-group">
                        <p class="control-label mb5">条码代码前缀:</p>
                        <input type="text" value="" name="" class="form-control" id="codePrefix">
                    </div>
				</div>
			</div>
			<div class="panel-body tab-pane" id="tableset">
				<div class="form-group">
					<label class="checkbox-inline semibold"><input type="checkbox"
						value="" name="" id="viewTdBorder" checked>显示表格框线</label>
				</div>
				<div class="form-group">
					<label class="checkbox-inline semibold"><input type="checkbox"
						value="" name="" id="viewThead" checked>显示表头</label>
				</div>
				<div class="moreinfo">
					<div class="form-group">
						<p class="control-label mb5">表头文字字体:</p>
						<select class="form-control" id="theadFontFamily">
							<option value="Arial" selected>Arial</option>
							<option value="Helvetica">Helvetica</option>
							<option value="Tahoma">Tahoma</option>
							<option value="Verdana">Verdana</option>
							<option value="Lucida Grande">Lucida Grande</option>
							<option value="Times New Roman">Times New Roman</option>
							<option value="Georgia">Georgia</option>
						</select>
					</div>
					<div class="form-group">
						<p class="control-label mb5">表头文字尺寸:</p>
						<select class="form-control" id="theadFontSize">
							<option value="6px">6px</option>
							<option value="8px">8px</option>
							<option value="9px">9px</option>
							<option value="10px">10px</option>
							<option value="11px">11px</option>
							<option value="12px" selected>12px</option>
						</select>
					</div>
				</div>
				<div class="form-group">
					<p class="control-label mb5">表格内容文字字体:</p>
					<select class="form-control" id="tbodyFontFamily">
						<option value="Arial" selected>Arial</option>
						<option value="Helvetica">Helvetica</option>
						<option value="Tahoma">Tahoma</option>
						<option value="Verdana">Verdana</option>
						<option value="Lucida Grande">Lucida Grande</option>
						<option value="Times New Roman">Times New Roman</option>
						<option value="Georgia">Georgia</option>
					</select>
				</div>
				<div class="form-group">
					<p class="control-label mb5">表格内容文字尺寸:</p>
					<select class="form-control" id="tbodyFontSize">
						<option value="6px">6px</option>
						<option value="8px">8px</option>
						<option value="9px">9px</option>
						<option value="10px">10px</option>
						<option value="11px">11px</option>
						<option value="12px" selected>12px</option>
					</select>
				</div>
				<div class="form-group">
					<label class="checkbox-inline semibold"><input type="checkbox"
						value="" name="" id="viewTfoot" checked>显示脚注</label>
				</div>
				<div class="moreinfo">
					<div class="form-group">
						<p class="control-label mb5">脚注文字字体:</p>
						<select class="form-control" id="tfootFontFamily">
							<option value="Arial" selected>Arial</option>
							<option value="Helvetica">Helvetica</option>
							<option value="Tahoma">Tahoma</option>
							<option value="Verdana">Verdana</option>
							<option value="Lucida Grande">Lucida Grande</option>
							<option value="Times New Roman">Times New Roman</option>
							<option value="Georgia">Georgia</option>
						</select>
					</div>
					<div class="form-group">
						<p class="control-label mb5">脚注文字尺寸:</p>
						<select class="form-control" id="tfootFontSize">
							<option value="6px">6px</option>
							<option value="8px">8px</option>
							<option value="9px">9px</option>
							<option value="10px">10px</option>
							<option value="11px">11px</option>
							<option value="12px" selected>12px</option>
						</select>
					</div>
					<!--<div class="form-group">
							<p class="control-label mb5">脚注文字对齐方式:</p>
							<select class="form-control" id="tfootAlign">
								<option value="left">左对齐</option>
								<option value="center">中对齐</option>
								<option value="right" selected>右对齐</option>
							</select>
						</div>-->
				</div>
			</div>
			<div class="panel-body tab-pane" id="fieldset-address">
				<div class="form-group multiple">
					<p class="control-label mb5">显示字段设置:</p>
					<label class="checkbox-inline semibold"><input type="checkbox"
						value="name" name="viewField" checked>显示姓名</label> <label
						class="checkbox-inline semibold"><input type="checkbox"
						value="street" name="viewField" checked>显示街道</label> <label
						class="checkbox-inline semibold"><input type="checkbox"
						value="area" name="viewField" checked>显示省份/城市/地区</label> <label
						class="checkbox-inline semibold"><input type="checkbox"
						value="country" name="viewField" checked>显示国家</label> <label
						class="checkbox-inline semibold ml10"><input type="checkbox"
						value="country_cn" name="viewField">显示国家中文名</label> <label
						class="checkbox-inline semibold"><input type="checkbox"
						value="postcode" name="viewField" checked>显示邮编</label> <label
						class="checkbox-inline semibold"><input type="checkbox"
						value="tel1" name="viewField" checked>显示电话1</label> <label
						class="checkbox-inline semibold"><input type="checkbox"
						value="tel2" name="viewField" checked>显示电话2</label> <label
						class="checkbox-inline semibold"><input type="checkbox"
						value="email" name="viewField" checked>显示E-mail</label>
				</div>
				<div class="form-group">
					<p class="control-label mb5">字段换行设置:</p>
					<label class="checkbox-inline semibold"><input type="checkbox"
						value="" name="" id="newline" checked>单字段换行</label>
				</div>
			</div>
			<div class="panel-body tab-pane" id="fieldset-sku">
				<div class="form-group">
					<label class="checkbox-inline semibold"><input type="checkbox"
						value="photo" name="viewField">显示商品缩略图</label> <input type="text"
						id="fieldTextPhoto" class="form-control mt5" name="" value="图片"
						disabled>
				</div>
				<div class="form-group">
					<label class="checkbox-inline semibold"><input type="checkbox"
						value="sku" name="viewField" checked>显示商品编号</label> <input
						type="text" id="fieldTextSku" class="form-control mt5" name=""
						value="SKU">
				</div>
				<div class="form-group">
					<label class="checkbox-inline semibold"><input type="checkbox"
						value="sku_original" name="viewField">显示原厂编号</label> <input
						type="text" id="fieldTextOriginal" class="form-control mt5"
						name="" value="原厂SKU" disabled>
				</div>
				<div class="form-group">
					<label class="checkbox-inline semibold"><input type="checkbox"
						value="itemid" name="viewField">显示itemID</label> <input
						type="text" id="fieldTextItemid" class="form-control mt5" name=""
						value="itemId" disabled>
				</div>
				<div class="form-group">
					<label class="checkbox-inline semibold"><input type="checkbox"
						value="name" name="viewField" checked>显示中文名称</label> <input
						type="text" id="fieldTextName" class="form-control mt5" name=""
						value="名称">
				</div>
				<div class="form-group">
					<label class="checkbox-inline semibold"><input type="checkbox"
						value="name_en" name="viewField">显示英文名称</label> <input type="text"
						id="fieldTextNameEn" class="form-control mt5" name="" value="name"
						disabled>
				</div>
				<div class="form-group">

					<label class="checkbox-inline semibold"><input type="checkbox"
						value="warehouse" name="viewField">显示仓库</label> <input type="text"
						id="fieldTextWarehouse" class="form-control mt5" name=""
						value="仓库" disabled>
				</div>
				<div class="form-group">
					<label class="checkbox-inline semibold"><input type="checkbox"
						value="position" name="viewField" checked>显示仓位</label> <input
						type="text" id="fieldTextPosition" class="form-control mt5"
						name="" value="仓位">
				</div>
				<div class="form-group">
					<label class="checkbox-inline semibold"><input type="checkbox"
						value="number" name="viewField" checked>显示数量</label> <input
						type="text" id="fieldTextNumber" class="form-control mt5" name=""
						value="数量">
				</div>
				<div class="form-group">
					<label class="checkbox-inline semibold"><input type="checkbox"
						value="weight" name="viewField">显示重量</label> <input type="text"
						id="fieldTextWeight" class="form-control mt5" name=""
						value="重量(kg)" disabled>
				</div>
				<div class="form-group">
					<label class="checkbox-inline semibold"><input type="checkbox"
						value="multi-property" name="viewField">显示多属性</label> <input type="text"
						id="fieldTextMultiproperty" class="form-control mt5" name="" value="多属性"
						disabled>
				</div>
				<div class="form-group">
					<label class="checkbox-inline semibold"><input type="checkbox"
						value="price" name="viewField">显示单价</label> <input type="text"
						id="fieldTextPrice" class="form-control mt5" name="" value="单价"
						disabled>
				</div>
				<div class="form-group">
					<label class="checkbox-inline semibold"><input type="checkbox"
						value="total" name="viewField">显示金额小计</label> <input type="text"
						id="fieldTextTotal" class="form-control mt5" name="" value="小计"
						disabled>
				</div>
			</div>
			<div class="panel-body tab-pane" id="fieldset-declare">
				<div class="form-group">
					<p class="control-label mb5">报关品名表头文字:</p>
					<textarea placeholder="请输入文本内容" rows="5" id="declareNameTitle"
						class="form-control" value=""></textarea>
				</div>
				<div class="form-group">
					<p class="control-label mb5">报关品名显示内容:</p>
					<select class="form-control" id="declareType">
						<option value="sort" selected>申报品名</option>
						<option value="group">多字段组合</option>
						<option value="custom">自定义文字</option>
					</select>
				</div>
				<div id="declareName">
					<div class="form-group multiple sort" style="display: block;">
						<p class="control-label mb5">商品目录内容格式:</p>
						<label class="checkbox-inline semibold"><input type="checkbox"
							value="sort" name="sorttype" checked>显示申报中文名</label> <label
							class="checkbox-inline semibold"><input type="checkbox"
							value="sort_en" name="sorttype" checked>显示申报英文名</label>
					</div>
					<div class="form-group multiple group">
						<p class="control-label mb5">多字段组合内容格式:</p>
						<label class="checkbox-inline semibold"><input type="checkbox"
							value="sku" name="catalogue" checked>显示商品编号</label> <label
							class="checkbox-inline semibold"><input type="checkbox"
							value="name" name="catalogue" checked>显示商品中文名</label> <label
							class="checkbox-inline semibold"><input type="checkbox"
							value="name_en" name="catalogue">显示商品英文名</label> <label
							class="checkbox-inline semibold"><input type="checkbox"
							value="position" name="catalogue">显示仓位</label> <label
							class="checkbox-inline semibold"><input type="checkbox"
							value="number" name="catalogue" checked>显示数量</label>
					</div>
					<div class="form-group multiple custom">
						<p class="control-label mb5">自定义文字内容:</p>
						<textarea placeholder="请输入文本内容" rows="5" id="declareNameCustom"
							class="form-control">自定义内容</textarea>
					</div>
				</div>
				<div class="form-group">
					<p class="control-label mb5">报关重量表头文字:</p>
					<textarea placeholder="请输入文本内容" rows="2" id="declareWeightTitle"
						class="form-control" value=""></textarea>
				</div>
				<div class="form-group">
					<p class="control-label mb5">报关价值表头文字:</p>
					<textarea placeholder="请输入文本内容" rows="2" id="declarePriceTitle"
						class="form-control" value=""></textarea>
				</div>
				<div class="form-group">
					<p class="control-label mb5">原产国表头文字:</p>
					<textarea placeholder="请输入文本内容" rows="5" id="declareOriginTitle"
						class="form-control" value=""></textarea>
				</div>
				<div class="form-group">
					<p class="control-label mb5">总重量表头文字:</p>
					<textarea placeholder="请输入文本内容" rows="2"
						id="declareTotalWeightTitle" class="form-control" value=""></textarea>
				</div>
				<div class="form-group">
					<p class="control-label mb5">总价值表头文字:</p>
					<textarea placeholder="请输入文本内容" rows="2"
						id="declareTotalPriceTitle" class="form-control" value=""></textarea>
				</div>
			</div>
			<div class="panel-body tab-pane" id="imageurl">
				<div class="form-group">
					<p class="control-label mb5">在线地址:</p>
					<textarea placeholder="请输入图片在线路径" rows="3" id="imageUrl"
						class="form-control" value=""></textarea>
					<button type="button" class="btn btn-default mt5 pl10 pr10"
						id="loadImgUrl">加载</button>
				</div>
			</div>
			<div class="alert alert-warning fade in group-warning">
				<p class="mb0">文字尺寸小于12px，在使用Google
					Chrome浏览器可能会出现显示异常，所以我们建议您使用Firefox或IE9以上版本浏览器执行标签打印</p>
			</div>
		</div>
		<div class="btn-group edit-btn">
			<button class="btn btn-success btn-copy" type="button">
				<i class="ico-copy4 mr5"></i>复制
			</button>
			<button class="btn btn-danger btn-clear-item" type="button">
				<i class="ico-close mr5"></i>删除
			</button>
		</div>
	</aside>
	<header class="custom-header">
		<div class="panel-body">
			<div class="form-horizontal form-bordered min">
				<form name="frmPrintTemplate" method="post" action="">
					<div class="form-group">
						<div class="col-sm-7">
							<div class="row">
								<div class="col-sm-6">
									<div class="input-group input-group">
										<span class="input-group-addon">模板名称</span> <input type="text"
											class="form-control" placeholder="" name="name"
											value="test"> <input type="hidden"
											id="id" name="id" value="3712" /> <input
											type="hidden" id="html" name="html"
											value="" />
									</div>
								</div>
								<div class="col-sm-6">
									<p class="loadtext text-muted semibold">
										单据类别:<span class="text-primary pl5 pr10">地址单</span>规格:<span
											class="text-primary pl5">10cm×10cm</span>
									</p>
								</div>
							</div>
						</div>
						<div class="col-sm-5 text-right">
							<button type="button" class="btn btn-success" id="save-data">
								<i class="ico-save mr5"></i>保存模板
							</button>
							<div class="btn-group">
								<button data-toggle="dropdown"
									class="btn btn-default dropdown-toggle" type="button">
									<i class="ico-eye-open mr5"></i><span class="text pr5">效果预览</span><span
										class="caret"></span>
								</button>
								<ul role="menu" class="dropdown-menu dropdown-menu-left">
									<li><a href="javascript:void(0);" class="btn-view"><i
											class="ico-scale-up mr5"></i>预览视图</a></li>
									<li><a href="javascript:void(0);"
										onClick="doPreviewTemplate(this)"><i class="ico-print mr5"></i>打印预览</a></li>
								</ul>
							</div>
							<button type="button"
								class="btn btn-default text-danger btn-reset">
								<i class="ico-remove6 mr5"></i>清空画布
							</button>
						</div>
					</div>
				</form>
			</div>
		</div>
	</header>
	<section class="custom-label">
		<div class="custom-content">
			<div id="divPrintTemplateHtml" class="label-group unauthorized">
										<div class="label-content" style="width:98mm; height:98mm;">
					<div class="view-mask"></div>
					<div class="custom-area"></div>
					<div class="custom-drop"></div>
				</div>
									</div>
		</div>
	</section>
	<aside class="hotkey-panel">
		<a onclick="$('.hotkey-panel').remove()" data-placement="top"
			data-original-title="我知道了" data-toggle="tooltip"
			href="javascript:void(0);" class="close-hotkey ico-cancel-circle"></a>
		<h3>快捷键</h3>
		<ul>
			<li><span class="key"><i class="ico-arrow-up3"></i></span><strong
				class="text-muted">上移</strong></li>
			<li><span class="key"><i class="ico-arrow-down3"></i></span><strong
				class="text-muted">下移</strong></li>
			<li><span class="key"><i class="ico-arrow-left3"></i></span><strong
				class="text-muted">左移</strong></li>
			<li><span class="key"><i class="ico-arrow-right4"></i></span><strong
				class="text-muted">右移</strong></li>
			<li><span class="key">Delete</span>或<span class="key">Del</span><strong
				class="text-muted">删除</strong></li>
		</ul>
	</aside>
	<aside class="view-panel">
		<div class="btn-group viewpct">
			<button type="button" class="btn btn-primary dropdown-toggle"
				data-toggle="dropdown">
				<i class=" ico-search5 mr5"></i>显示比例:<span class="text pr5 pl5">100%</span><span
					class="caret"></span>
			</button>
			<button type="button" class="btn btn-primary btn-close-view">
				<i class="ico-exit mr5"></i>退出预览
			</button>
			<ul class="dropdown-menu pull-left copytext" role="menu">
				<li><a href="javascript:void(0);" rel="default">100%</a></li>
				<li><a href="javascript:void(0);" rel="scale150">150%</a></li>
				<li><a href="javascript:void(0);" rel="scale200">200%</a></li>
				<li><a href="javascript:void(0);" rel="scale300">300%</a></li>
			</ul>
		</div>
	</aside>
</body>
<!--/ END Body -->
</html>