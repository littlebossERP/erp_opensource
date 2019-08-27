<?php 
use yii\helpers\Html;
use eagle\modules\util\helpers\TranslateHelper;
?>
<!DOCTYPE html>
<html>
	<!-- START Head -->
	<head>
		<!-- START META SECTION -->
		<meta charset="utf-8">
		<title>小老板 ERP 设计打印模板</title>
 		<link rel="stylesheet" type="text/css" href="<?= \Yii::getAlias('@web'); ?>/css/carrier/bootstrap.min.css"/>
		<link rel="stylesheet" type="text/css" href="<?= \Yii::getAlias('@web'); ?>/css/carrier/layout.min.css"/>
		<link rel="stylesheet" type="text/css" href="<?= \Yii::getAlias('@web'); ?>/css/carrier/uielement.min.css"/>
		<link rel="stylesheet" type="text/css" href="<?= \Yii::getAlias('@web'); ?>/css/carrier/jquery-ui.min.css"/>
		<link rel="stylesheet" type="text/css" href="<?= \Yii::getAlias('@web'); ?>/css/carrier/jquery.gritter.min.css"/>
		<link rel="stylesheet" type="text/css" href="<?= \Yii::getAlias('@web'); ?>/css/carrier/custom.css"/>
		<link rel="stylesheet" type="text/css" href="<?= \Yii::getAlias('@web'); ?>/css/carrier/print.css"/>
		<link rel="stylesheet" type="text/css" href="<?= \Yii::getAlias('@web'); ?>/js/jquery.qtip.min.css"/>
        
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
		<script src="<?= \Yii::getAlias('@web'); ?>/js/project/inventory/jquery.serializejson.min.js"></script>
		
		<script src="<?= \Yii::getAlias('@web'); ?>/js/project/carrier/customprint/run_new.js"></script>
	</head>
	<!--/ END Head -->
	
	<!-- START Body -->
	<body style="overflow:hidden; background:#3a4144 url(/images/customprint/grid_bg_gray.png);">
		<!-- START Template Main -->
		<aside class="label-type">
			<div class="panel-group nm" id="type-group">
				<div class="panel panel-default">
					<div class="panel-heading">
						<h4 class="panel-title">
							<a class="collapsed" href="#addressinfo" data-parent="#type-group" data-toggle="collapse"><span class="arrow mr5"></span>地址信息</a>
						</h4>
					</div>
					<div class="panel-collapse collapse" id="addressinfo">
						<div class="panel-body">
							<!---->
							<div class="dragitem character" data-type="character" data-default-width="150">
								<strong class="title"><i class="ico-type mr5"></i>发件人公司名称</strong>
								<span class="detail">
									<littleboss id="SENDER_COMPANY_NAME">小老板</littleboss>
								</span>
							</div>
							<!---->
							<div class="dragitem character" data-type="character" data-default-width="150">
								<strong class="title"><i class="ico-type mr5"></i>发件人姓名</strong>
								<span class="detail">
									<littleboss id="SENDER_NAME">Mr Li</littleboss>
								</span>
							</div>
							<!---->
							<div class="dragitem character" data-type="character" data-default-width="150">
								<strong class="title"><i class="ico-type mr5"></i>发件人街道</strong>
								<span class="detail">
									<littleboss id="SENDER_ADDRESS">Room 605 ,NO.2539,Songhuajiang Road,Hongkou District</littleboss>
								</span>
							</div>
							<!---->
							<div class="dragitem character" data-type="character" data-default-width="150">
								<strong class="title"><i class="ico-type mr5"></i>发件人国家</strong>
								<span class="detail">
									<littleboss id="SENDER_COUNTRY_EN">China</littleboss>
								</span>
							</div>
							<!---->
							<div class="dragitem character" data-type="character" data-default-width="150">
								<strong class="title"><i class="ico-type mr5"></i>发件人国家英文缩写</strong>
								<span class="detail">
									<littleboss id="SENDER_COUNTRY_EN_AB">CN</littleboss>
								</span>
							</div>
							<!---->
							<div class="dragitem character" data-type="character" data-default-width="150">
								<strong class="title"><i class="ico-type mr5"></i>发件人省份</strong>
								<span class="detail">
									<littleboss id="SENDER_PROVINCE_EN">Shanghai</littleboss>
								</span>
							</div>
							<!---->
							<div class="dragitem character" data-type="character" data-default-width="150">
								<strong class="title"><i class="ico-type mr5"></i>发件人城市</strong>
								<span class="detail">
									<littleboss id="SENDER_CITY_EN">Shanghai</littleboss>
								</span>
							</div>
							<!---->
							<div class="dragitem character" data-type="character" data-default-width="150">
								<strong class="title"><i class="ico-type mr5"></i>发件人地区</strong>
								<span class="detail">
									<littleboss id="SENDER_AREA_EN">Hongkou District</littleboss>
								</span>
							</div>
							<!---->
							<div class="dragitem character" data-type="character" data-default-width="150">
								<strong class="title"><i class="ico-type mr5"></i>发件人邮编</strong>
								<span class="detail">
									<littleboss id="SENDER_ZIPCODE">201101</littleboss>
								</span>
							</div>
							<!---->
							<div class="dragitem character" data-type="character" data-default-width="150">
								<strong class="title"><i class="ico-type mr5"></i>发件人电话</strong>
								<span class="detail">
									<littleboss id="SENDER_TELEPHONE">021-65343623</littleboss>
								</span>
							</div>
							<!---->
							<div class="dragitem character" data-type="character" data-default-width="150">
								<strong class="title"><i class="ico-type mr5"></i>发件人手机</strong>
								<span class="detail">
									<littleboss id="SENDER_MOBILE">13811111111</littleboss>
								</span>
							</div>
							<!---->
							<div class="dragitem character" data-type="character" data-default-width="150">
								<strong class="title"><i class="ico-type mr5"></i>发件人邮箱</strong>
								<span class="detail">
									<littleboss id="SENDER_EMAIL">service@littleboss.com</littleboss>
								</span>
							</div>
							<!---->
							<div class="dragitem character" data-type="character" data-default-width="150">
								<strong class="title"><i class="ico-type mr5"></i>收件人姓名</strong>
								<span class="detail">
									<littleboss id="RECEIVER_NAME">Graham Foster</littleboss>
								</span>
							</div>
							<!---->
							<div class="dragitem character" data-type="character" data-default-width="150" title="显示平台地址">
								<strong class="title"><i class="ico-type mr5"></i>收件人街道</strong>
								<span class="detail">
									<littleboss id="RECEIVER_ADDRESS">1056 Mornington Lane</littleboss>
								</span>
							</div>
							<!---->
							<div class="dragitem character" data-type="character" data-default-width="150">
								<strong class="title"><i class="ico-type mr5"></i>收件人国家(中)</strong>
								<span class="detail">
									<littleboss id="RECEIVER_COUNTRY_CN">美国</littleboss>
								</span>
							</div>
							<!---->
							<div class="dragitem character" data-type="character" data-default-width="150">
								<strong class="title"><i class="ico-type mr5"></i>收件人国家(英)</strong>
								<span class="detail">
									<littleboss id="RECEIVER_COUNTRY_EN">United States</littleboss>
								</span>
							</div>
							<!---->
							<div class="dragitem character" data-type="character" data-default-width="150">
								<strong class="title"><i class="ico-type mr5"></i>收件人国家英文简称</strong>
								<span class="detail">
									<littleboss id="RECEIVER_COUNTRY_EN_AB">US</littleboss>
								</span>
							</div>
							<!---->
							<div class="dragitem character" data-type="character" data-default-width="150">
								<strong class="title"><i class="ico-type mr5"></i>收件人省份</strong>
								<span class="detail">
									<littleboss id="RECEIVER_PROVINCE">California</littleboss>
								</span>
							</div>
							<!---->
							<div class="dragitem character" data-type="character" data-default-width="150">
								<strong class="title"><i class="ico-type mr5"></i>收件人城市</strong>
								<span class="detail">
									<littleboss id="RECEIVER_CITY">San Ramon</littleboss>
								</span>
							</div>
							<!---->
							<div class="dragitem character" data-type="character" data-default-width="150">
								<strong class="title"><i class="ico-type mr5"></i>收件人地区/州</strong>
								<span class="detail">
									<littleboss id="RECEIVER_AREA">-</littleboss>
								</span>
							</div>
							<!---->
							<div class="dragitem character" data-type="character" data-default-width="150">
								<strong class="title"><i class="ico-type mr5"></i>收件人邮编</strong>
								<span class="detail">
									<littleboss id="RECEIVER_ZIPCODE">94582</littleboss>
								</span>
							</div>
							<!---->
							<div class="dragitem character" data-type="character" data-default-width="150">
								<strong class="title"><i class="ico-type mr5"></i>收件人电话</strong>
								<span class="detail">
									<littleboss id="RECEIVER_TELEPHONE">9258337000</littleboss>
								</span>
							</div>
							<!---->
							<div class="dragitem character" data-type="character" data-default-width="150">
								<strong class="title"><i class="ico-type mr5"></i>收件人手机</strong>
								<span class="detail">
									<littleboss id="RECEIVER_MOBILE">20178945612</littleboss>
								</span>
							</div>
							<!---->
							<div class="dragitem character" data-type="character" data-default-width="150">
								<strong class="title"><i class="ico-type mr5"></i>收件人邮箱</strong>
								<span class="detail">
									<littleboss id="RECEIVER_EMAIL">graham2015@gmail.com</littleboss>
								</span>
							</div>
							<!---->
							<div class="dragitem character" data-type="character" data-default-width="150">
								<strong class="title"><i class="ico-type mr5"></i>收件人公司</strong>
								<span class="detail">
								<littleboss id="RECEIVER_COMPANY">recipient company</littleboss>
								</span>
							</div>
							<!---->
							<div class="dragitem character" data-type="character" data-default-width="150" title="收件人地址包含公司,区,镇等信息">
								<strong class="title"><i class="ico-type mr5"></i>收件人详细地址</strong>
								<span class="detail">
								<littleboss id="RECEIVER_DETAILED_ADDRESS">Company;1056 Mornington Lane; district, county</littleboss>
								</span>
							</div>
							<!---->
							<div class="dragitem character" data-type="barcode" data-default-width="142">
                                <strong class="title"><i class="ico-barcode2 mr5"></i>EUB专用邮编条码</strong>
                                <span class="detail">
                                    <littleboss id="EUB_RECEIVER_ZIPCODE_BARCODE">
                                        <img src="/images/customprint/barcode.jpg">
                                    </littleboss>
                                    <span class="codemunber">12365</span>
                                </span>
                            </div>
                            <!---->
						</div>
					</div>
				</div>
				<!---->
				<div class="panel panel-default">
                    <div class="panel-heading">
                        <h4 class="panel-title">
                            <a class="collapsed" href="#orderinfo" data-parent="#type-group" data-toggle="collapse">
                                <span class="arrow mr5"></span>订单物流信息
                            </a>
                        </h4>
                    </div>
                    <div class="panel-collapse collapse" id="orderinfo">
                        <div class="panel-body">
                            <!---->
                            <div class="dragitem character" data-type="character" data-default-width="150">
                                <strong class="title"><i class="ico-type mr5"></i>店铺</strong>
                                <span class="detail">
                                    <littleboss id="ORDER_SHOP_NAME">bestsale</littleboss>
                                </span>
                            </div>
                            <!---->
                            <div class="dragitem character" data-type="character" data-default-width="150">
                                <strong class="title"><i class="ico-type mr5"></i>买家ID</strong>
                                <span class="detail">
                                    <littleboss id="ORDER_BUYER_ID">Tim</littleboss>
                                </span>
                            </div>
                            <!---->
                            <div class="dragitem character" data-type="character" data-default-width="150">
                                <strong class="title"><i class="ico-type mr5"></i>交易号</strong>
                                <span class="detail">
                                    <littleboss id="ORDER_TRADE_NUMBER">4568779954566</littleboss>
                                </span>
                            </div>
                            <!---->
                            <div class="dragitem character" data-type="character" data-default-width="150">
                                <strong class="title"><i class="ico-type mr5"></i>平台来源订单号</strong>
                                <span class="detail">
                                    <littleboss id="ORDER_SOURCE_CODE">6500000021</littleboss>
                                </span>
                            </div>
                            <!---->
                            <!---->
                            <div class="dragitem character" data-type="character" data-default-width="150">
                                <strong class="title"><i class="ico-type mr5"></i>小老板订单编号</strong>
                                <span class="detail">
                                    <littleboss id="XLB_ORDER_CODE">1236595</littleboss>
                                </span>
                            </div>
                            <!---->
                            <div class="dragitem character" data-type="character" data-default-width="150">
                                <strong class="title"><i class="ico-type mr5"></i>订单金额</strong>
                                <span class="detail">
                                    <littleboss id="ORDER_TOTAL_FEE">100.00</littleboss>
                                </span>
                            </div>
                            <!---->
                            <div class="dragitem character" data-type="character" data-default-width="150">
                                <strong class="title"><i class="ico-type mr5"></i>币种</strong>
                                <span class="detail">
                                    <littleboss id="ORDER_CURRENCY">USD</littleboss>
                                </span>
                            </div>
                            <!---->
                            <div class="dragitem character" data-type="character" data-default-width="150">
                                <strong class="title"><i class="ico-type mr5"></i>付款方式</strong>
                                <span class="detail">
                                    <littleboss id="RECEIVER_PAYMENT">Payment_method</littleboss>
                                </span>
                            </div>
                            <!---->
                            <div class="dragitem character" data-type="character" data-default-width="150">
                                <strong class="title"><i class="ico-type mr5"></i>打印时间</strong>
                                <span class="detail">
                                    <littleboss id="ORDER_PRINT_TIME">2015-07-28 11:10:00</littleboss>
                                </span>
                            </div>
                            <!---->
                            <div class="dragitem character" data-type="character" data-default-width="150">
                                <strong class="title"><i class="ico-type mr5"></i>打印时间格式2</strong>
                                <span class="detail">
                                    <littleboss id="ORDER_PRINT_TIME2">01/18 13:43:40</littleboss>
                                </span>
                            </div>
                            <!---->
                            <div class="dragitem character" data-type="character" data-default-width="150">
                                <strong class="title"><i class="ico-type mr5"></i>打印时间格式3</strong>
                                <span class="detail">
                                    <littleboss id="ORDER_PRINT_TIME3">2015-07-28</littleboss>
                                </span>
                            </div>
                            <!---->
                            <div class="dragitem character" data-type="character" data-default-width="150">
                                <strong class="title"><i class="ico-type mr5"></i>打印时间年</strong>
                                <span class="detail">
                                    <littleboss id="ORDER_PRINT_TIME_YEAR">2015</littleboss>
                                </span>
                            </div>
                            <!---->
                            <div class="dragitem character" data-type="character" data-default-width="150">
                                <strong class="title"><i class="ico-type mr5"></i>打印时间月</strong>
                                <span class="detail">
                                    <littleboss id="ORDER_PRINT_TIME_MONTH">01</littleboss>
                                </span>
                            </div>
                            <!---->
                            <div class="dragitem character" data-type="character" data-default-width="150">
                                <strong class="title"><i class="ico-type mr5"></i>打印时间日</strong>
                                <span class="detail">
                                    <littleboss id="ORDER_PRINT_TIME_DAY">01</littleboss>
                                </span>
                            </div>
                            <!---->
                            <div class="dragitem character" data-type="character" data-default-width="150">
                                <strong class="title"><i class="ico-type mr5"></i>订单备注</strong>
                                <span class="detail">
                                    <littleboss id="ORDER_REMARK">客户要蓝色的</littleboss>
                                </span>
                            </div>
                            <!---->
                            <div class="dragitem character" data-type="character" data-default-width="150">
                                <strong class="title"><i class="ico-type mr5"></i>跟踪号</strong>
                                <span class="detail">
                                    <littleboss id="ORDER_EXPRESS_CODE">CD3390454534</littleboss>
                                </span>
                            </div>
                            <!---->
                            <div class="dragitem character" data-type="character" data-default-width="150">
                                <strong class="title"><i class="ico-type mr5"></i>LAZADA包装号</strong>
                                <span class="detail">
                                    <littleboss id="LAZADA_PACKAGE_CODE">201306072055555015</littleboss>
                                </span>
                            </div>
                            <!---->
                            <div class="dragitem character" data-type="character" data-default-width="150">
                                <strong class="title"><i class="ico-type mr5"></i>平台物流方式</strong>
                                <span class="detail">
                                    <littleboss id="ORDER_EXPRESS_WAY">燕文北京平邮</littleboss>
                                </span>
                            </div>
                            <!---->
                            <div class="dragitem character" data-type="character" data-default-width="150">
                                <strong class="title"><i class="ico-type mr5"></i>货运方式</strong>
                                <span class="detail">
                                    <littleboss id="ORDER_EXPRESS_NAME">北京燕文</littleboss>
                                </span>
                            </div>
                            <!---->
                            <div class="dragitem character" data-type="character" data-default-width="150">
                                <strong class="title"><i class="ico-type mr5"></i>是否含电池</strong>
                                <span class="detail">
                                    <littleboss id="ORDER_HAS_BATTERY">有电池</littleboss>
                                </span>
                            </div>
                            <!---->
                            <div class="dragitem character" data-type="barcode" data-default-width="142">
                                <strong class="title"><i class="ico-barcode2 mr5"></i>LAZADA包装号条码</strong>
                                <span class="detail">
                                    <littleboss id="LAZADA_PACKAGE_CODE_BARCODE">
                                        <img src="/images/customprint/barcode.jpg">
                                    </littleboss>
                                    <span class="codemunber">1236595</span>
                                </span>
                            </div>
                            <!---->
                            <div class="dragitem character" data-type="barcode" data-default-width="142">
                                <strong class="title"><i class="ico-barcode2 mr5"></i>跟踪号条码</strong>
                                <span class="detail">
                                    <littleboss id="ORDER_EXPRESS_CODE_BARCODE">
                                        <img src="/images/customprint/barcode.jpg">
                                    </littleboss>
                                    <span class="codemunber">1236595</span>
                                </span>
                            </div>
                            <!---->
                            <div class="dragitem character" data-type="barcode" data-default-width="142">
                                <strong class="title"><i class="ico-barcode2 mr5"></i>小老板订单号条码</strong>
                                <span class="detail">
                                    <littleboss id="XLB_ORDER_CODE_BARCODE">
                                        <img src="/images/customprint/barcode.jpg">
                                    </littleboss>
                                    <span class="codemunber">1236595</span>
                                </span>
                            </div>
                            <!---->
                            <div class="dragitem character" data-type="barcode" data-default-width="142">
                                <strong class="title"><i class="ico-barcode2 mr5"></i>平台来源订单号条码</strong>
                                <span class="detail">
                                    <littleboss id="ORDER_SOURCE_CODE_BARCODE">
                                        <img src="/images/customprint/barcode.jpg">
                                    </littleboss>
                                    <span class="codemunber">1236595</span>
                                </span>
                            </div>
                            <!---->
                            <div class="dragitem character" data-type="character" data-default-width="150">
                                <strong class="title"><i class="ico-type mr5"></i>国际挂号小包分拣区</strong>
                                <span class="detail">
									<littleboss id="INTERNATIONAL_REGISTERED_PARCEL_SORTING_AREA">1</littleboss>
                                </span>
                            </div>
                            <!---->
                            <div class="dragitem character" data-type="character" data-default-width="150">
                                <strong class="title"><i class="ico-type mr5"></i>国际挂号小包分区</strong>
                                <span class="detail">
									<littleboss id="INTERNATIONAL_REGISTERED_PARCEL_PARTITION">1</littleboss>
                                </span>
                            </div>
                            <!---->
                            <div class="dragitem character" data-type="character" data-default-width="150">
                                <strong class="title"><i class="ico-type mr5"></i>国际平常小包分区</strong>
                                <span class="detail">
                                    <littleboss id="INTERNATIONAL_COMMON_PACKET_PARTITION">1</littleboss>
                                </span>
                            </div>
                            <!---->
                            <div class="dragitem character" data-type="character" data-default-width="150">
                                <strong class="title"><i class="ico-type mr5"></i>EUB分拣码</strong>
                                <span class="detail">
                                    <littleboss id="PARTITION_YARDS_EUB">1F</littleboss>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
                <!---->
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <h4 class="panel-title">
                            <a class="collapsed" href="#pickinginfo" data-parent="#type-group" data-toggle="collapse">
                                <span class="arrow mr5"></span>商品配货报关信息
                            </a>
                        </h4>
                    </div>
                    <div class="panel-collapse collapse" id="pickinginfo">
                        <div class="panel-body">
                            <!---->
                            <div class="dragitem character" data-type="skulist" data-default-height="64">
                                <strong class="title"><i class="ico-list-alt mr5"></i>配货清单</strong>
                                <div class="detail">
                                    <table id="FULL_ITEMS_DETAIL_TABLE" class="skulist-table min">
                                        <thead style="font-size: 12px; font-family: Arial;">
                                            <tr>
                                            	<th width='35' class="skuautoid">
                                                    <span>序号</span>
                                                </th>
                                                <th width="30" class="photo dis-none">
                                                    <span>图片</span>
                                                </th>
                                                <th class="sku">
                                                    <span>SKU</span>
                                                </th>
                                                <th class="itemid dis-none">
                                                    <span>itemId</span>
                                                </th>
                                                <th width="35%" class="name_picking dis-none">
                                                	<span>配货名称</span>
                                                </th>
                                                <th width="35%" class="name">
                                                    <span>名称</span>
                                                </th>
                                                <th width="35%" class="name_en dis-none">
                                                    <span>Name</span>
                                                </th>
                                                <th width="35%" class="product_title dis-none">
                                                    <span>PRODUCT_TITLE</span>
                                                </th>
                                                <th class="warehouse dis-none">
                                                    <span>仓库</span>
                                                </th>
                                                <th class="position">
                                                    <span>仓位</span>
                                                </th>
                                                <th width='40' class="number">
                                                    <span>数量</span>
                                                </th>
                                                <th class="weight dis-none">
                                                    <span>重量(g)</span>
                                                </th>
                                                <th class="multi-property dis-none">
                                                    <span>多属性</span>
                                                </th>
                                                <th width='50' class="price dis-none">
                                                    <span>单价</span>
                                                </th>
                                                <th width='55' class="total dis-none">
                                                    <span>小计</span>
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody style="font-size: 12px; font-family: Arial;">
                                            <tr id="ITEM_LIST_DETAIL">
                                            	<td class="skuautoid">
                                                    <littleboss id="ITEM_LIST_DETAIL_SKUAUTOID">
                                                        <span title="1">1</span>
                                                    </littleboss>
                                                </td>
                                                <td width="30" class="photo dis-none">
                                                    <span>
                                                        <littleboss id="ITEM_LIST_DETAIL_PICTURE">
                                                            <img src="/images/customprint/product/img12.jpg">
                                                        </littleboss>
                                                    </span>
                                                </td>
                                                <td class="sku">
                                                    <littleboss id="ITEM_LIST_DETAIL_SKU">
                                                        <span title="KS0152d100_100">KS0152d100_100</span>
                                                    </littleboss>
                                                </td>
                                                <td class="itemid dis-none">
                                                    <littleboss id="ITEM_LIST_DETAIL_ITEM_ID">
                                                        <span title="1011215412">
                                                            1011215412
                                                        </span>
                                                    </littleboss>
                                                </td>
                                                <td width="35%" class="name_picking dis-none">
                                                    <littleboss id="ITEM_LIST_DETAIL_NAME_PICKING">
                                                        <span title="玫红色">玫红色</span>
                                                    </littleboss>
                                                </td>
                                                <td width="35%" class="name">
                                                    <littleboss id="ITEM_LIST_DETAIL_NAME_CN">
                                                        <span title="玫红色 2014年夏季新品儿童连衣裙 高品质新款花朵纱裙童装">玫红色 2014年夏季新品儿童连衣裙 高品质新款花朵纱裙童装</span>
                                                    </littleboss>
                                                </td>
                                                <td width="35%" class="name_en dis-none">
                                                    <littleboss id="ITEM_LIST_DETAIL_NAME_EN">
                                                        <span>Mei red dress in the summer of 2014 new children High quality new flowers veil children</span>
                                                    </littleboss>
                                                </td>
                                                <td width="35%" class="product_title dis-none">
                                                    <littleboss id="ITEM_LIST_DETAIL_PRODUCT_TITLE">
                                                        <span title="Mei red dres">Mei red dres</span>
                                                    </littleboss>
                                                </td>
                                                <td class="warehouse dis-none">
                                                    <littleboss id="ITEM_LIST_DETAIL_WAREHOUSE">
                                                        <span title="上海仓">上海仓</span>
                                                    </littleboss>
                                                </td>
                                                <td class="position">
                                                    <littleboss id="ITEM_LIST_DETAIL_GRID_CODE">
                                                        <span title="无仓位">无仓位</span>
                                                    </littleboss>
                                                </td>
                                                <td class="number">
                                                    <littleboss id="ITEM_LIST_DETAIL_QUANTITY">
                                                        <span>3</span>
                                                    </littleboss>
                                                </td>
                                                <td class="weight dis-none">
                                                    <littleboss id="ITEM_LIST_DETAIL_WEIGHT">
                                                        <span>311.9</span>
                                                    </littleboss>
                                                </td>
                                                <td class="multi-property dis-none">
                                                    <littleboss id="ITEM_LIST_DETAIL_PROPERTY">
                                                        <span>color:red</span>
                                                    </littleboss>
                                                </td>
                                                <td class="price dis-none">
                                                    <littleboss id="ITEM_LIST_DETAIL_PRICE">
                                                        <span>80.0</span>
                                                    </littleboss>
                                                </td>
                                                <td class="total dis-none">
                                                    <littleboss id="ITEM_LIST_DETAIL_AMOUNT_PRICE">
                                                        <span>240.0</span>
                                                    </littleboss>
                                                </td>
                                            </tr>
                                        </tbody>
                                        <tfoot style="font-size: 12px; font-family: Arial; text-align: right;">
                                            <tr>
                                                <td colspan="5">
	                                                <p>
														<span class="total-sku pl5">
															<strong><littleboss id="ITEM_LIST_TOTAL_KIND">1</littleboss></strong><span>个商品种类</span>
														</span>
															<span class="total-number pl5">
														<strong><littleboss id="ITEM_LIST_TOTAL_QUANTITY">3</littleboss></strong><span>件商品</span>
															</span>
														<span class="weight pl5 dis-none"><span>重量</span>
															<strong><littleboss id="ITEM_LIST_TOTAL_WEIGHT">548.7</littleboss></strong>
														</span>
														<span class="price pl5 dis-none"><span>金额</span>
															<strong><littleboss id="ITEM_LIST_TOTAL_AMOUNT_PRICE">340.0</littleboss></strong>
														</span>
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
				<!---->
				<div class="panel panel-default">
                    <div class="panel-heading">
                        <h4 class="panel-title">
                            <a class="collapsed" href="#productinfo" data-parent="#type-group" data-toggle="collapse">
                                <span class="arrow mr5"></span>商品标签信息
                            </a>
                        </h4>
                    </div>
                    <div class="panel-collapse collapse" id="productinfo">
                        <div class="panel-body">
                            <!---->
                            <div class="dragitem character" data-type="character" data-default-width="150">
                                <strong class="title"><i class="ico-type mr5"></i>SKU编号</strong>
                                <span class="detail">
                                    <littleboss id="PRODUCT_SALE_SKU">L034665</littleboss>
                                </span>
                            </div>
                            <!---->
                            <div class="dragitem character" data-type="character" data-default-width="150">
                                <strong class="title"><i class="ico-type mr5"></i>仓库</strong>
                                <span class="detail">
                                    <littleboss id="PRODUCT_WAREHOUSE">默认仓</littleboss>
                                </span>
                            </div>
                            <!---->
                            <div class="dragitem character" data-type="character" data-default-width="150">
                                <strong class="title"><i class="ico-type mr5"></i>仓位</strong>
                                <span class="detail">
                                    <littleboss id="PRODUCT_WAREHOUSE_GRID_CODE">2023214-03-02</littleboss>
                                </span>
                            </div>
                            <!---->
                            <div class="dragitem character" data-type="character" data-default-width="150">
                                <strong class="title"><i class="ico-type mr5"></i>商品名称（中）</strong>
                                <span class="detail">
                                    <littleboss id="PRODUCT_NAME_CN">玫红色 2014年夏季新品儿童连衣裙 高品质新款花朵纱裙童装</littleboss>
                                </span>
                            </div>
                            <!---->
                            <div class="dragitem character" data-type="character" data-default-width="150">
                                <strong class="title"><i class="ico-type mr5"></i>商品名称（英）</strong>
                                <span class="detail">
                                    <littleboss id="PRODUCT_NAME_EN">Mei red dress in the summer of 2014 new children High quality new flowers veil children's clothes</littleboss>
                                </span>
                            </div>
                            <!---->
                            <div class="dragitem character" data-type="character" data-default-width="150">
                                <strong class="title"><i class="ico-type mr5"></i>商品重量</strong>
                                <span class="detail">
                                    <littleboss id="PRODUCT_WEIGHT">230</littleboss>
                                </span>
                            </div>
                            <!---->
                            <div class="dragitem character" data-type="character" data-default-width="150">
                                <strong class="title"><i class="ico-type mr5"></i>采购员</strong>
                                <span class="detail">
                                    <littleboss id="PRODUCT_BUYER_NAME">刘某某</littleboss>
                                </span>
                            </div>
                            <!---->
                            <div class="dragitem character" data-type="barcode" data-default-width="142">
                                <strong class="title"><i class="ico-barcode2 mr5"></i>SKU条码</strong>
                                <span class="detail">
                                    <littleboss id="PRODUCT_STOCK_SKU_BARCODE">
                                        <img src="/images/customprint/barcode.jpg">
                                    </littleboss>
                                    <span class="codemunber">1236595</span>
                                </span>
                            </div>
                            <!---->
                            <div class="dragitem character" data-type="character" data-default-width="150">
                                <strong class="title"><i class="ico-type mr5"></i>配货信息</strong>
                                <span class="detail">
                                    <littleboss id="PRODUCT_INFORMATION">10301B * 1</littleboss>
                                </span>
                            </div>
                            <!---->
                            <div class="dragitem character" data-type="character" data-default-width="150">
                                <strong class="title"><i class="ico-type mr5"></i>标题</strong>
                                <span class="detail">
                                    <littleboss id="PRODUCT_TITLE">ale Chaussures LED</littleboss>
                                </span>
                            </div>
                            <!---->
                            <div class="dragitem character" data-type="character" data-default-width="150">
                                <strong class="title"><i class="ico-type mr5"></i>商品总量</strong>
                                <span class="detail">
                                    <littleboss id="ORDER_TOTAL_WEIGHT">230</littleboss>
                                </span>
                            </div>
                            <!---->
                            <div class="dragitem character" data-type="character" data-default-width="150">
                                <strong class="title"><i class="ico-type mr5"></i>商品总价值</strong>
                                <span class="detail">
                                    <littleboss id="TOTAL_AMOUNT_PRICE">230</littleboss>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
                <!---->
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <h4 class="panel-title">
                            <a class="collapsed" href="#element" data-parent="#type-group" data-toggle="collapse"><span class="arrow mr5"></span>构图元素</a>
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
                            <div class="dragitem draw" data-type="customtext" data-default-width="100">
                                <i class="ico-type"></i><strong class="title">自订文本</strong>
                            </div>
                            <!---->
                            <div style='display: none;' class="dragitem draw" data-type="circletext" data-default-width="50" data-default-height="50">
                                <i class="ico-cc"></i><strong class="title">圆形标记</strong>
                            </div>
                            <!---->
                            <div class="dragitem draw" data-type="onlineimage" data-default-width="50" data-default-height="50">
                                <i class="ico-image"></i><strong class="title">在线图片</strong>
                            </div>
                        </div>
                    </div>
                </div>
                <!---->
				<div class="panel panel-default">
                    <div class="panel-heading">
                        <h4 class="panel-title">
                            <a class="collapsed" href="#imagelibrary" data-parent="#type-group" data-toggle="collapse"><span class="arrow mr5"></span>预设图片</a>
                        </h4>
                    </div>
                    <div class="panel-collapse collapse" id="imagelibrary">
                        <div class="panel-body">
                            <div class="dragitem image" data-type="image" data-default-width="140" data-default-height="40">
                                <img src="/images/customprint/labelimg/chinapost-1.jpg">
                            </div>
                            <div class="dragitem image" data-type="image" data-default-width="140" data-default-height="70">
                                <img src="/images/customprint/labelimg/chinapost-2.jpg">
                            </div>
                            <div class="dragitem image" data-type="image" data-default-width="125" data-default-height="25">
                                <img src="/images/customprint/labelimg/chinapost-3.jpg">
                            </div>
                            <div class="dragitem image" data-type="image" data-default-width="140" data-default-height="40">
                                <img src="/images/customprint/labelimg/chinapost-4.jpg">
                            </div>
                            <div class="dragitem image" data-type="image" data-default-width="160" data-default-height="80">
                                <img src="/images/customprint/labelimg/4px.png">
                            </div>
                            <div class="dragitem image" data-type="image" data-default-width="160" data-default-height="80">
                                <img src="/images/customprint/labelimg/permit_sga.png">
                            </div>
                            <div class="dragitem image" data-type="image" data-default-width="210" data-default-height="65">
                                <img src="/images/customprint/labelimg/pobox.png">
                            </div>
                            <div class="dragitem image" data-type="image" data-default-width="150" data-default-height="50">
                                <img src="/images/customprint/labelimg/australia-post.jpg">
                            </div>
                            <div class="dragitem image" data-type="image" data-default-width="125" data-default-height="35">
                                <img src="/images/customprint/labelimg/bpostlvs.jpg">
                            </div>
                            <div class="dragitem image" data-type="image" data-default-width="75" data-default-height="25">
                                <img src="/images/customprint/labelimg/eparcel.jpg">
                            </div>
                            <div class="dragitem image" data-type="image" data-default-width="75" data-default-height="25">
                                <img src="/images/customprint/labelimg/ePacket.jpg">
                            </div>
                            <div class="dragitem image" data-type="image" data-default-width="120" data-default-height="45">
                                <img src="/images/customprint/labelimg/esp.jpg">
                            </div>
                            <div class="dragitem image" data-type="image" data-default-width="50" data-default-height="65">
                                <img src="/images/customprint/labelimg/eubauscan.jpg">
                            </div>
                            <div class="dragitem image" data-type="image" data-default-width="120" data-default-height="60">
                                <img src="/images/customprint/labelimg/eubfr.jpg">
                            </div>
                            <div class="dragitem image" data-type="image" data-default-width="55" data-default-height="35">
                                <img src="/images/customprint/labelimg/eubfremail.jpg">
                            </div>
                            <div class="dragitem image" data-type="image" data-default-width="40" data-default-height="50">
                                <img src="/images/customprint/labelimg/eubfrscanright.jpg">
                            </div>
                            <div class="dragitem image" data-type="image" data-default-width="140" data-default-height="40">
                                <img src="/images/customprint/labelimg/eubhktitle.jpg">
                            </div>
                            <div class="dragitem image" data-type="image" data-default-width="80" data-default-height="100">
                                <img src="/images/customprint/labelimg/eub-postexpres.jpg">
                            </div>
                            <div class="dragitem image" data-type="image" data-default-width="90" data-default-height="50">
                                <img src="/images/customprint/labelimg/HSUUK.jpg">
                            </div>
                            <div class="dragitem image" data-type="image" data-default-width="65" data-default-height="65">
                                <img src="/images/customprint/labelimg/icon.jpg">
                            </div>
                            <div class="dragitem image" data-type="image" data-default-width="120" data-default-height="25">
                                <img src="/images/customprint/labelimg/ipz.jpg">
                            </div>
                            <div class="dragitem image" data-type="image" data-default-width="50" data-default-height="80">
                                <img src="/images/customprint/labelimg/no-signature.jpg">
                            </div>
                            <div class="dragitem image" data-type="image" data-default-width="175" data-default-height="65">
                                <img src="/images/customprint/labelimg/paravion.jpg">
                            </div>
                            <div class="dragitem image" data-type="image" data-default-width="150" data-default-height="70">
                                <img src="/images/customprint/labelimg/postnl.jpg">
                            </div>
                            <div class="dragitem image" data-type="image" data-default-width="155" data-default-height="65">
                                <img src="/images/customprint/labelimg/priority.jpg">
                            </div>
                            <div class="dragitem image" data-type="image" data-default-width="140" data-default-height="100">
                                <img src="/images/customprint/labelimg/royalmail.jpg">
                            </div>
                            <div class="dragitem image" data-type="image" data-default-width="90" data-default-height="60">
                                <img src="/images/customprint/labelimg/royalmail-2.jpg">
                            </div>
                            <div class="dragitem image" data-type="image" data-default-width="50" data-default-height="50">
                                <img src="/images/customprint/labelimg/helan3.jpg">
                            </div>
                            <div class="dragitem image" data-type="image" data-default-width="85" data-default-height="70">
                                <img src="/images/customprint/labelimg/swedishpackets.jpg">
                            </div>
                            <div class="dragitem image" data-type="image" data-default-width="100" data-default-height="92">
                                <img src="/images/customprint/labelimg/xujialiang.jpg">
                            </div>
                            <div class="dragitem image" data-type="image" data-default-width="125" data-default-height="35">
                                <img src="/images/customprint/labelimg/swisspost.jpg">
                            </div>
                            <div class="dragitem image" data-type="image" data-default-width="85" data-default-height="30">
                                <img src="/images/customprint/labelimg/wish-mail.jpg">
                            </div>
                            <div class="dragitem image" data-type="image" data-default-width="70" data-default-height="80">
                                <img src="/images/customprint/labelimg/EIBFrscan.jpg">
                            </div>
                            <div class="dragitem image" data-type="image" data-default-width="55" data-default-height="60">
                                <img src="/images/customprint/labelimg/lianyt.jpg">
                            </div>
                            <div class="dragitem image" data-type="image" data-default-width="60" data-default-height="60">
                                <img src="/images/customprint/labelimg/4px3.jpg">
                            </div>
                            <div class="dragitem image" data-type="image" data-default-width="120" data-default-height="55">
                                <img src="/images/customprint/labelimg/met.jpg">
                            </div>
                            <div class="dragitem image" data-type="image" data-default-width="120" data-default-height="65">
                                <img src="/images/customprint/labelimg/met2.jpg">
                            </div>
                            <div class="dragitem image" data-type="image" data-default-width="100" data-default-height="45">
                                <img src="/images/customprint/labelimg/ruston_logo.jpg">
                            </div>
                            <div class="dragitem image" data-type="image" data-default-width="160" data-default-height="80">
                                <img src="/images/customprint/labelimg/4px_lyt_py_de_main.png">
                            </div>
                            <div class="dragitem image" data-type="image" data-default-width="39" data-default-height="39">
                                <img src="/images/customprint/labelimg/4px_return.gif">
                            </div>
                            <div class="dragitem image" data-type="image" data-default-width="228" data-default-height="86">
                                <img src="/images/customprint/labelimg/lzd_AS-Poslaju_03.png">
                            </div>
                            <div class="dragitem image" data-type="image" data-default-width="85" data-default-height="52">
                                <img src="/images/customprint/labelimg/lzd_AS-Poslaju-MY_03.png">
                            </div>
                            <div class="dragitem image" data-type="image" data-default-width="200" data-default-height="100">
                                <img src="/images/customprint/labelimg/LGS SG1 Logo.png">
                            </div>
                            <div class="dragitem image" data-type="image" data-default-width="466" data-default-height="203">
                                <img src="/images/customprint/labelimg/LGS TH logo.png">
                            </div>
                            <div class="dragitem image" data-type="image" data-default-width="105" data-default-height="92">
                                <img src="/images/customprint/labelimg/LGS PH logo.png">
                            </div>
                        </div>
                    </div>
                </div>
				<!---->
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
                            <li class="title">
                                <a data-toggle="tab" href="#titelset">标题</a>
                            </li>
                            <li class="detail">
                                <a data-toggle="tab" href="#detailset">内容</a>
                            </li>
                            <li class="field-address">
                                <a data-toggle="tab" href="#fieldset-address">字段</a>
                            </li>
                            <li class="text">
                                <a data-toggle="tab" href="#textset">文字</a>
                            </li>
                            <li class="imgurl">
                                <a data-toggle="tab" href="#imageurl">路径</a>
                            </li>
                            <li class="border">
                                <a data-toggle="tab" href="#borderset">边框</a>
                            </li>
                            <li class="table">
                                <a data-toggle="tab" href="#tableset">表格</a>
                            </li>
                            <li class="field-sku">
                                <a data-toggle="tab" href="#fieldset-sku">字段</a>
                            </li>
                            <li class="field-declare">
                                <a data-toggle="tab" href="#fieldset-declare">字段</a>
                            </li>
                            <li class="field-address-mode2">
                                <a data-toggle="tab" href="#fieldset-address-mode2">字段</a>
                            </li>
                        </ul>
                    </div>
                </div>
                <div class="panel-body tab-pane" id="titelset">
                    <div class="form-group">
                        <label class="checkbox-inline semibold">
                            <input type="checkbox" value="" name="" id="viewTitle" checked>显示标题
                        </label>
                    </div>
                    <div class="title-set">
                        <div class="form-group">
                            <p class="control-label mb5">标题文本:</p>
                            <input type="text" value="" name="" class="form-control" id="titleName">
                        </div>
                        <div class="form-group">
                            <label class="checkbox-inline semibold">
                                <input type="checkbox" value="" name="" id="titleNowrap">标题整行显示
                            </label>
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
                                <option value="15px">15px</option>
                                <option value="18px">18px</option>
                                <option value="24px">24px</option>
                                <option value="30px">30px</option>
                                <option value="36px">36px</option>
                                <option value="48px">48px</option>
                                <option value="60px">60px</option>
                                <option value="72px">72px</option>
                            </select>
                        </div>
                        <div class="form-group" style='display: none;'>
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
                            <label class="checkbox-inline semibold">
                                <input type="checkbox" value="" name="" id="titleFontWeight" checked>标题文字加粗
                            </label>
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
							<option value="15px">15px</option>
							<option value="18px">18px</option>
							<option value="24px">24px</option>
							<option value="30px">30px</option>
							<option value="36px">36px</option>
							<option value="48px">48px</option>
							<option value="60px">60px</option>
							<option value="72px">72px</option>
                        </select>
                    </div>
                    <div class="form-group" style='display: none;'>
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
                        <label class="checkbox-inline semibold">
                            <input type="checkbox" value="" name="" id="detailFontWeight">内容文字加粗
                        </label>
                    </div>
                </div>
                <div class="panel-body tab-pane" id="textset">
                    <div class="form-group">
                        <p class="control-label mb5">自定义文本:</p>
                        <textarea value="" class="form-control" id="textDetail" rows="5" placeholder="请输入文本内容">
                        </textarea>
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
							<option value="15px">15px</option>
							<option value="18px">18px</option>
							<option value="24px">24px</option>
							<option value="30px">30px</option>
							<option value="36px">36px</option>
							<option value="48px">48px</option>
							<option value="60px">60px</option>
							<option value="72px">72px</option>
                        </select>
                    </div>
                    <div class="form-group dis-none">
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
                        <label class="checkbox-inline semibold">
                            <input type="checkbox" value="" name="" id="textFontWeight">文本文字加粗
                        </label>
                    </div>
                    <div class="form-group dis-none">
                        <label class="checkbox-inline semibold">
                            <input type="checkbox" value="" name="" id="textCheckBox">含复选框 (<i class="ico-checkbox-unchecked3 pl5 pr5"></i>)
                        </label>
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
                        <label class="checkbox-inline semibold">
                            <input type="checkbox" id="borderTop" name="" value="">显示上边框
                        </label>
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
                            <div class="col-sm-6" style='display: none;'>
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
                        <label class="checkbox-inline semibold">
                            <input type="checkbox" id="borderBottom" name="" value="">显示下边框
                        </label>
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
                            <div class="col-sm-6" style='display: none;'>
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
                        <label class="checkbox-inline semibold">
                            <input type="checkbox" id="borderLeft" name="" value="">显示左边框
                        </label>
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
                            <div class="col-sm-6" style='display: none;'>
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
                        <label class="checkbox-inline semibold">
                            <input type="checkbox" id="borderRight" name="" value="">显示右边框
                        </label>
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
                            <div class="col-sm-6" style='display: none;'>
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
                        <span class="customnum">
                            <a href="javascript:void(0);" class="subtract ico-minus-sign-alt">
                            </a>
                            <input id="xLineWidth" type="text" class="form-control" onbeforepaste="clipboardData.setData('text',clipboardData.getData('text').replace(/[^\d]/g,''))"
                            onkeyup="value=value.replace(/[^\d]/g,'') " value="366">
                            <a href="javascript:void(0);" class="add ico-plus-sign2">
                            </a>
                        </span>
                        <button type="button" class="btn btn-primary ml10" id="setMaxWidth">设为100%</button>
                    </div>
                    <div class="form-group">
                        <p class="control-label mb5">线条类型:</p>
                        <select class="form-control" id="xLineStyle">
                            <option value="solid" selected>实线</option>
                            <option value="dotted">点状线</option>
                            <option value="dashed">虚线</option>
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
                        <span class="customnum">
                            <a href="javascript:void(0);" class="subtract ico-minus-sign-alt">
                            </a>
                            <input id="yLineHeight" type="text" class="form-control" onbeforepaste="clipboardData.setData('text',clipboardData.getData('text').replace(/[^\d]/g,''))"
                            onkeyup="value=value.replace(/[^\d]/g,'') " value="366">
                            <a href="javascript:void(0);" class="add ico-plus-sign2">
                            </a>
                        </span>
                        <button type="button" class="btn btn-primary" id="setMaxHeight">设为100%</button>
                    </div>
                    <div class="form-group">
                        <p class="control-label mb5">线条类型:</p>
                        <select class="form-control" id="yLineStyle">
                            <option value="solid" selected>实线</option>
                            <option value="dotted">点状线</option>
                            <option value="dashed">虚线</option>
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
                        <input type="text" value="A" name="" class="form-control" id="circleText">
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
							<option value="12px" selected>12px</option>
							<option value="14px">14px</option>
							<option value="15px">15px</option>
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
                        <label class="checkbox-inline semibold">
                            <input type="checkbox" value="" name="" id="circleFontWeight" checked>文字内容加粗
                        </label>
                    </div>
                </div>
                <div class="panel-body tab-pane" id="barcode">
                    <div class="form-group">
                        <label class="checkbox-inline semibold">
                            <input type="checkbox" value="" name="" id="viewCodeNum">显示条码代码
                        </label>
                    </div>
                </div>
                <div class="panel-body tab-pane" id="tableset">
                    <div class="form-group">
                        <label class="checkbox-inline semibold">
                            <input type="checkbox" value="" name="" id="viewTdBorder" checked>显示表格框线
                        </label>
                    </div>
                    <div class="form-group">
                        <label class="checkbox-inline semibold">
                            <input type="checkbox" value="" name="" id="viewThead" checked>显示表头
                        </label>
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
                        <label class="checkbox-inline semibold">
                            <input type="checkbox" value="" name="" id="viewTfoot" checked>显示脚注
                        </label>
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
                    </div>
                </div>
                <div class="panel-body tab-pane" id="fieldset-address">
                    <div class="form-group multiple">
                        <p class="control-label mb5">显示字段设置:</p>
                        <label class="checkbox-inline semibold">
                            <input type="checkbox" value="name" name="viewField" checked>显示姓名
                        </label>
                        <label class="checkbox-inline semibold">
                            <input type="checkbox" value="street" name="viewField" checked>显示街道
                        </label>
                        <label class="checkbox-inline semibold">
                            <input type="checkbox" value="area" name="viewField" checked>显示省份/城市/地区
                        </label>
                        <label class="checkbox-inline semibold">
                            <input type="checkbox" value="country" name="viewField" checked>显示国家
                        </label>
                        <label class="checkbox-inline semibold ml10">
                            <input type="checkbox" value="country_cn" name="viewField">显示国家中文名
                        </label>
                        <label class="checkbox-inline semibold">
                            <input type="checkbox" value="postcode" name="viewField" checked>显示邮编
                        </label>
                        <label class="checkbox-inline semibold">
                            <input type="checkbox" value="tel1" name="viewField" checked>显示电话1
                        </label>
                        <label class="checkbox-inline semibold">
                            <input type="checkbox" value="tel2" name="viewField" checked>显示电话2
                        </label>
                        <label class="checkbox-inline semibold">
                            <input type="checkbox" value="email" name="viewField" checked>显示E-mail
                        </label>
                        <label class="checkbox-inline semibold">
                            <input type="checkbox" value="company" name="viewField" checked>显示公司
                        </label>
                    </div>
                    <div class="form-group">
                        <p class="control-label mb5">字段换行设置:</p>
                        <label class="checkbox-inline semibold">
                            <input type="checkbox" value="" name="" id="newline" checked>单字段换行
                        </label>
                    </div>
                </div>
                <div class="panel-body tab-pane" id="fieldset-address-mode2">
                    <div class="form-group multiple">
                        <p class="control-label mb5">显示字段设置:</p>
                        <label class="checkbox-inline semibold">
                            <input type="checkbox" value="company_mode2" name="viewField" checked>显示公司
                        </label>
                        <label class="checkbox-inline semibold">
                            <input type="checkbox" value="street_mode2" name="viewField" checked>显示街道
                        </label>
                        <label class="checkbox-inline semibold">
                            <input type="checkbox" value="area_mode2" name="viewField" checked>显示省份/城市/镇/地区
                        </label>
                        <label class="checkbox-inline semibold">
                            <input type="checkbox" value="postcode_mode2" name="viewField" checked>显示邮编
                        </label>
                    </div>
                    <div class="form-group">
                        <p class="control-label mb5">字段换行设置:</p>
                        <label class="checkbox-inline semibold">
                            <input type="checkbox" value="" name="" id="newline_mode2" checked>单字段换行
                        </label>
                    </div>
                </div>
                <div class="panel-body tab-pane" id="fieldset-sku">
                	<div class="form-group">
                        <label class="checkbox-inline semibold">
                            <input type="checkbox" value="skuautoid" name="viewField">显示序号
                        </label>
                        <input type="text" id="fieldTextskuAutoID" class="form-control mt5" name="" value="序号">
                    </div>
                    <div class="form-group">
                        <label class="checkbox-inline semibold">
                            <input type="checkbox" value="photo" name="viewField">显示商品缩略图
                        </label>
                        <input type="text" id="fieldTextPhoto" class="form-control mt5" name="" value="图片" disabled>
                    </div>
                    <div class="form-group">
                        <label class="checkbox-inline semibold">
                            <input type="checkbox" value="sku" name="viewField" checked>显示商品编号
                        </label>
                        <input type="text" id="fieldTextSku" class="form-control mt5" name="" value="SKU">
                    </div>
                    <div class="form-group">
                        <label class="checkbox-inline semibold">
                            <input type="checkbox" value="itemid" name="viewField">显示itemID
                        </label>
                        <input type="text" id="fieldTextItemid" class="form-control mt5" name="" value="itemId" disabled>
                    </div>
                    <div class="form-group">
                        <label class="checkbox-inline semibold">
                            <input type="checkbox" value="name_picking" name="viewField">显示配货名称
                        </label>
                        <input type="text" id="fieldTextNameEn" class="form-control mt5" name="" value="name" disabled>
                    </div>
                    <div class="form-group">
                        <label class="checkbox-inline semibold">
                            <input type="checkbox" value="name" name="viewField" checked>显示中文名称
                        </label>
                        <input type="text" id="fieldTextName" class="form-control mt5" name="" value="名称">
                    </div>
                    <div class="form-group">
                        <label class="checkbox-inline semibold">
                            <input type="checkbox" value="name_en" name="viewField">显示英文名称
                        </label>
                        <input type="text" id="fieldTextNameEn" class="form-control mt5" name="" value="name" disabled>
                    </div>
                    <div class="form-group">
                        <label class="checkbox-inline semibold">
                            <input type="checkbox" value="product_title" name="viewField">显示标题
                        </label>
                        <input type="text" id="fieldTextProducttitle" class="form-control mt5" name="" value="product_title" disabled>
                    </div>
                    <div class="form-group">
                        <label class="checkbox-inline semibold">
                            <input type="checkbox" value="warehouse" name="viewField">显示仓库
                        </label>
                        <input type="text" id="fieldTextWarehouse" class="form-control mt5" name="" value="仓库" disabled>
                    </div>
                    <div class="form-group">
                        <label class="checkbox-inline semibold">
                            <input type="checkbox" value="position" name="viewField" checked>显示仓位
                        </label>
                        <input type="text" id="fieldTextPosition" class="form-control mt5" name="" value="仓位">
                    </div>
                    <div class="form-group">
                        <label class="checkbox-inline semibold">
                            <input type="checkbox" value="number" name="viewField" checked>显示数量
                        </label>
                        <input type="text" id="fieldTextNumber" class="form-control mt5" name="" value="数量">
                    </div>
                    <div class="form-group">
                        <label class="checkbox-inline semibold">
                            <input type="checkbox" value="weight" name="viewField">显示重量
                        </label>
                        <input type="text" id="fieldTextWeight" class="form-control mt5" name="" value="重量(kg)" disabled>
                    </div>
                    <div class="form-group">
                        <label class="checkbox-inline semibold">
                            <input type="checkbox" value="multi-property" name="viewField">显示多属性
                        </label>
                        <input type="text" id="fieldTextMultiproperty" class="form-control mt5" name="" value="多属性" disabled>
                    </div>
                    <div class="form-group">
                        <label class="checkbox-inline semibold">
                            <input type="checkbox" value="price" name="viewField">显示单价
                        </label>
                        <input type="text" id="fieldTextPrice" class="form-control mt5" name="" value="单价" disabled>
                    </div>
                    <div class="form-group">
                        <label class="checkbox-inline semibold">
                            <input type="checkbox" value="total" name="viewField">显示金额小计
                        </label>
                        <input type="text" id="fieldTextTotal" class="form-control mt5" name="" value="小计" disabled>
                    </div>
                </div>
                <div class="panel-body tab-pane" id="fieldset-declare">
                    <div class="form-group">
                        <p class="control-label mb5">报关品名表头文字:</p>
                        <textarea placeholder="请输入文本内容" rows="5" id="declareNameTitle" class="form-control" value="">
                        </textarea>
                    </div>
                    <div class="form-group">
                        <p class="control-label mb5">报关品名显示内容:</p>
                        <select class="form-control" id="declareType">
                            <option value="sort" selected>申报品名</option>
                            <option value="group">多字段组合</option>
                            <option value="custom">自定义文字</option>
                        </select>
                    </div>
                    <littleboss id="declareName">
                        <div class="form-group multiple sort" style="display: block;">
                            <p class="control-label mb5">商品目录内容格式:
                            </p>
                            <label class="checkbox-inline semibold">
                                <input type="checkbox" value="sort" name="sorttype" checked>显示申报中文名
                            </label>
                            <label class="checkbox-inline semibold">
                                <input type="checkbox" value="sort_en" name="sorttype" checked>显示申报英文名
                            </label>
                        </div>
                        <div class="form-group multiple group">
                            <p class="control-label mb5">多字段组合内容格式:</p>
                            <label class="checkbox-inline semibold">
                                <input type="checkbox" value="sku" name="catalogue" checked>显示商品编号
                            </label>
                            <label class="checkbox-inline semibold">
                                <input type="checkbox" value="name" name="catalogue" checked>显示商品中文名
                            </label>
                            <label class="checkbox-inline semibold">
                                <input type="checkbox" value="name_en" name="catalogue">显示商品英文名
                            </label>
                            <label class="checkbox-inline semibold">
                                <input type="checkbox" value="position" name="catalogue">显示仓位
                            </label>
                            <label class="checkbox-inline semibold">
                                <input type="checkbox" value="number" name="catalogue" checked>显示数量
                            </label>
                        </div>
                        <div class="form-group multiple custom">
                            <p class="control-label mb5">自定义文字内容:</p>
                            <textarea placeholder="请输入文本内容" rows="5" id="declareNameCustom" class="form-control">自定义内容</textarea>
                        </div>
                    </littleboss>
                    <div class="form-group">
                        <p class="control-label mb5">报关重量表头文字:</p>
                        <textarea placeholder="请输入文本内容" rows="2" id="declareWeightTitle" class="form-control" value="">
                        </textarea>
                    </div>
                    <div class="form-group">
                        <p class="control-label mb5">报关价值表头文字:</p>
                        <textarea placeholder="请输入文本内容" rows="2" id="declarePriceTitle" class="form-control" value="">
                        </textarea>
                    </div>
                    <div class="form-group">
                        <p class="control-label mb5">原产国表头文字:</p>
                        <textarea placeholder="请输入文本内容" rows="5" id="declareOriginTitle" class="form-control" value="">
                        </textarea>
                    </div>
                    <div class="form-group">
                        <p class="control-label mb5">总重量表头文字:</p>
                        <textarea placeholder="请输入文本内容" rows="2" id="declareTotalWeightTitle" class="form-control" value="">
                        </textarea>
                    </div>
                    <div class="form-group">
                        <p class="control-label mb5">总价值表头文字:</p>
                        <textarea placeholder="请输入文本内容" rows="2" id="declareTotalPriceTitle" class="form-control" value="">
                        </textarea>
                    </div>
                </div>
                <div class="panel-body tab-pane" id="imageurl">
                    <div class="form-group">
                        <p class="control-label mb5">在线地址:</p>
                        <textarea placeholder="请输入图片在线路径" rows="3" id="imageUrl" class="form-control" value="">
                        </textarea>
                        <button type="button" class="btn btn-default mt5 pl10 pr10" id="loadImgUrl">加载</button>
                    </div>
                </div>
                <div class="alert alert-warning fade in group-warning">
                    <p class="mb0">文字尺寸小于12px，在使用Google Chrome浏览器可能会出现显示异常，所以我们建议您使用Firefox或IE9以上版本浏览器执行标签打印</p>
                </div>
            </div>
            <div class="btn-group edit-btn">
                <button class="btn btn-success btn-copy" type="button"><i class="ico-copy4 mr5"></i>复制</button>
                <button class="btn btn-danger btn-clear-item" type="button"><i class="ico-close mr5"></i>删除</button>
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
											<span class="input-group-addon">模板名称</span>
											<input type="text" class="form-control" name="name" value="<?= $template->template_name ?>"/>
											<input type="hidden" id="id" name="id" value="<?=$template->template_id ?>"/>
											<input type="hidden" id="html" name="html" value="<?=base64_encode($template->template_content) ?>"/>
											<input type="hidden" name="width" value="<?=$template->template_width ?>"/>
											<input type="hidden" name="height" value="<?=$template->template_height ?>"/>
											<input type="hidden" name="template_type" value="<?=$template->template_type ?>"/>
											<input type="hidden" id="template_content_json" name="template_content_json" value="" />
											<input type="hidden" name="template_version" value="1" />
										</div>
									</div>
									<div class="col-sm-6">
										<p class="loadtext text-muted semibold">
										单据类别:<span class="text-primary pl5 pr10"><?=$template->template_type ?></span>
										规格:<span class="text-primary pl5"><?=$template->template_width ?>mm×<?=$template->template_height ?>mm</span>
										</p>
									</div>
								</div>
							</div>
							<div class="col-sm-5 text-right">
								<button type="button" class="btn btn-success" id="save-data"><i class="ico-save mr5"></i>保存模板</button>
								<div class="btn-group">
									<button data-toggle="dropdown" class="btn btn-default dropdown-toggle" type="button">
										<i class="ico-eye-open mr5"></i>
										<span class="text pr5">效果预览</span>
										<span class="caret"></span>
									</button>
									<ul role="menu" class="dropdown-menu dropdown-menu-left">
										<li><a href="javascript:void(0);" class="btn-view"><i class="ico-scale-up mr5"></i>预览视图</a></li>
										<li><a href="javascript:void(0);" id="printPreview"><i class="ico-print mr5"></i>打印预览</a></li>
									</ul>
								</div>
								<button type="button" class="btn btn-default text-danger btn-reset"><i class="ico-remove6 mr5"></i>清空画布</button>
							</div>
						</div>
					</form>
				</div>
			</div>
		</header>
		<section class="custom-label">
            <div class="custom-content">
                <littleboss id="divPrintTemplateHtml" class="label-group unauthorized">
                	<div class="refer-tools">
						<a href="javascript:void(0)" class="refer-upload" data-toggle="popover" data-content="您可以选择一张图片作为参照" data-container="body" data-trigger="hover" data-placement="top" data-original-title="" title=""><i class="ico-image"></i><input type="file" accept="image/*" class="hide" name="referImageUpload"></a>
						<a href="javascript:void(0)" class="refer-remove" data-toggle="popover" data-content="移除参考图片" data-container="body" data-trigger="hover" data-placement="top" data-original-title="" title="" style='display: none;'><i class="ico-remove3 text-danger"></i></a>
						<a href="javascript:void(0)" class="refer-switch" data-toggle="popover" data-content="切换参考图显示方式：右侧或底部背景" data-container="body" data-trigger="hover" data-placement="top" data-original-title="" title="" style='display: none;'><i class=" ico-transmission"></i></a>
						<a href="javascript:void(0)" class="refer-view" data-toggle="popover" data-content="显示或隐藏参考图" data-container="body" data-trigger="hover" data-placement="top" data-original-title="" title="" style='display: none;'><i class="ico-eye-close text-muted"></i></a>
					</div>
					
                    <?php if($template->template_content){ echo $template->template_content; }else{ ?>
                        <div class="label-content" style="width:<?=(empty($_GET['width']) ? 100 : $_GET['width'])-2 ?>mm; height:<?=(empty($_GET['height']) ? 100 : $_GET['height'])-2 ?>mm;">
                            <div class="view-mask"></div>
                            <div class="custom-area"></div>
                            <div class="custom-drop"></div>
                        </div>
                        <?php } ?>
                </littleboss>
            </div>
        </section>
		<aside class="hotkey-panel">
			<a onclick="$('.hotkey-panel').remove()" data-placement="top" data-original-title="我知道了" data-toggle="tooltip" href="javascript:void(0);" class="close-hotkey ico-cancel-circle"></a>
			<h3>快捷键</h3>
			<ul>
				<li>
					<span class="key"><i class="ico-arrow-up3"></i></span>
					<strong class="text-muted">上移</strong>
				</li>
				<li>
					<span class="key"><i class="ico-arrow-down3"></i></span>
					<strong class="text-muted">下移</strong>
				</li>
				<li>
					<span class="key"><i class="ico-arrow-left3"></i></span>
					<strong class="text-muted">左移</strong>
				</li>
				<li>
					<span class="key"><i class="ico-arrow-right4"></i></span>
					<strong class="text-muted">右移</strong>
				</li>
				<li>
					<span class="key">Delete</span>或<span class="key">Del</span>
					<strong class="text-muted">删除</strong>
				</li>
			</ul>
		</aside>
		<aside class="view-panel">
			<div class="btn-group viewpct">
				<button type="button" class="btn btn-primary dropdown-toggle" data-toggle="dropdown">
					<i class=" ico-search5 mr5"></i>显示比例:<span class="text pr5 pl5">100%</span><span class="caret"></span>
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
		<form style="display:none;" id="toPreview" action="/configuration/carrierconfig/preview-carrier-template-new" target="_target" method="post">
			<input type="hidden" name="width" />
			<input type="hidden" name="height" />
			<input type="hidden" name="template_content" />
		</form>
	</body>
	<!--/ END Body -->
</html>