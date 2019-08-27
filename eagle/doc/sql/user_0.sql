-- phpMyAdmin SQL Dump
-- version 4.1.14
-- http://www.phpmyadmin.net
--
-- Host: 127.0.0.1
-- Generation Time: Jul 27, 2015 at 08:18 AM
-- Server version: 5.6.17
-- PHP Version: 5.5.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Database: `user_0_online`
--

-- --------------------------------------------------------

--
-- Table structure for table `aliexpress_childorderlist`
--

CREATE TABLE IF NOT EXISTS `aliexpress_childorderlist` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键id',
  `memo` varchar(255) DEFAULT NULL COMMENT '速卖通子订单备注',
  `childid` varchar(20) DEFAULT NULL COMMENT '子订单id',
  `productid` varchar(20) DEFAULT NULL COMMENT '商品id',
  `orderid` varchar(30) DEFAULT NULL COMMENT '速卖通订单号主订单号',
  `lotnum` int(5) DEFAULT NULL COMMENT '单位个数',
  `productattributes` text COMMENT '商品属性',
  `productunit` varchar(50) DEFAULT NULL COMMENT '单位',
  `skucode` varchar(100) DEFAULT NULL COMMENT 'SKU',
  `productcount` int(5) DEFAULT NULL COMMENT '商品数量',
  `productprice_amount` float(10,2) DEFAULT NULL COMMENT '单价',
  `productprice_currencycode` varchar(5) DEFAULT NULL COMMENT '货币单位',
  `productname` varchar(255) DEFAULT NULL COMMENT '商品名',
  `productsnapurl` varchar(255) DEFAULT NULL COMMENT '商品链接',
  `productimgurl` varchar(255) DEFAULT NULL COMMENT '商品图片',
  `create_time` int(11) DEFAULT NULL COMMENT '创建时间',
  `update_time` int(11) DEFAULT NULL COMMENT '修改时间',
  PRIMARY KEY (`id`),
  KEY `orderid` (`orderid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `aliexpress_freight_template`
--

CREATE TABLE IF NOT EXISTS `aliexpress_freight_template` (
  `templateid` bigint(18) NOT NULL,
  `selleruserid` varchar(100) DEFAULT NULL COMMENT '速卖通账号',
  `uid` int(10) DEFAULT NULL COMMENT '小老板uid',
  `default` enum('true','false') DEFAULT 'false' COMMENT '是否默认',
  `template_name` varchar(255) DEFAULT NULL COMMENT '运费模板名',
  `freight_setting` text COMMENT '运费模板详情',
  `created` int(11) DEFAULT NULL,
  `updated` int(11) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `aliexpress_order`
--
CREATE TABLE IF NOT EXISTS `aliexpress_order` (
  `id` varchar(50) NOT NULL COMMENT '速卖通订单号',
  `selleroperatorloginid` varchar(100) NOT NULL COMMENT '卖家账号',
  `buyerloginid` varchar(100) NOT NULL COMMENT '买家账号',
  `gmtcreate` varchar(30) DEFAULT NULL COMMENT '速卖通订单创建时间',
  `gmtmodified` varchar(30) DEFAULT NULL COMMENT '速卖通订单修改时间',
  `sellersignerfullname` varchar(100) DEFAULT NULL COMMENT '卖家全名',
  `ordermsgList` varchar(255) DEFAULT NULL COMMENT '订单留言',
  `orderstatus` varchar(30) DEFAULT NULL COMMENT '速卖通订单状态',
  `buyersignerfullname` varchar(100) DEFAULT NULL COMMENT '买家全名',
  `fundstatus` varchar(30) DEFAULT NULL COMMENT '资金状态',
  `gmtpaysuccess` varchar(30) DEFAULT NULL COMMENT '付款时间',
  `issueinfo` varchar(255) DEFAULT NULL COMMENT '纠纷信息',
  `issuestatus` varchar(20) DEFAULT NULL COMMENT '纠纷状态',
  `frozenstatus` varchar(20) DEFAULT NULL COMMENT '冻结状态',
  `logisticsstatus` varchar(50) DEFAULT NULL COMMENT '物流状态',
  `loaninfo` varchar(255) DEFAULT NULL COMMENT '放款信息',
  `loanatatus` varchar(20) DEFAULT NULL COMMENT '放款状态',
  `receiptaddress_zip` varchar(20) DEFAULT NULL COMMENT '收件人邮编',
  `receiptaddress_address2` varchar(255) DEFAULT NULL COMMENT '收件人地址2',
  `receiptaddress_detailaddress` varchar(255) DEFAULT NULL COMMENT '收件人详细地址',
  `receiptaddress_country` varchar(10) DEFAULT NULL COMMENT '收件人国家代码如US',
  `receiptaddress_city` varchar(100) DEFAULT NULL COMMENT '收件人城市',
  `receiptaddress_phonenumber` varchar(30) DEFAULT NULL COMMENT '收件人电话',
  `receiptaddress_province` varchar(100) DEFAULT NULL COMMENT '收件人省、州',
  `receiptaddress_phonearea` varchar(20) DEFAULT NULL COMMENT '收件人电话区号',
  `receiptaddress_phonecountry` varchar(20) DEFAULT NULL COMMENT '收件人国家电话区号',
  `receiptaddress_contactperson` varchar(100) DEFAULT NULL COMMENT '收件人',
  `receiptaddress_mobileno` varchar(20) DEFAULT NULL COMMENT '收件人手机',
  `buyerinfo_lastname` varchar(50) DEFAULT NULL COMMENT '买家lastname',
  `buyerinfo_loginid` varchar(50) DEFAULT NULL COMMENT '买家速卖通账号',
  `buyerinfo_email` varchar(50) DEFAULT NULL COMMENT '买家邮箱',
  `buyerinfo_firstname` varchar(50) DEFAULT NULL COMMENT '买家firstname',
  `buyerinfo_country` varchar(10) DEFAULT NULL COMMENT '买家国家代码',
  `logisticsamount_amount` float(10,2) DEFAULT NULL COMMENT '运费',
  `logisticsamount_currencycode` varchar(5) DEFAULT NULL COMMENT '运费货币代码',
  `orderamount_amount` float(10,2) DEFAULT NULL COMMENT '订单总金额包括运费',
  `orderamount_currencycode` varchar(5) DEFAULT NULL COMMENT '货币代码',
  `initOderAmount_amount` float(10,2) DEFAULT NULL COMMENT '订单商品总金额',
  `initoderamount_currencycode` varchar(5) DEFAULT NULL COMMENT '货币代码',
  `create_time` int(11) DEFAULT NULL COMMENT '创建时间',
  `update_time` int(11) DEFAULT NULL COMMENT '修改时间',
  `memo` varchar(255) DEFAULT NULL COMMENT '订单备注',
  `logistics_services_name` varchar(200) NOT NULL DEFAULT '' COMMENT '买家选择的运输方式',
  PRIMARY KEY (`id`),
  KEY `selleroperatorloginid` (`selleroperatorloginid`),
  KEY `buyerloginid` (`buyerloginid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `aliexpress_profile`
--

CREATE TABLE IF NOT EXISTS `aliexpress_profile` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `uid` int(10) DEFAULT NULL,
  `selleruserid` varchar(100) DEFAULT NULL,
  `subject` varchar(255) DEFAULT NULL COMMENT '商品标题',
  `keyword` varchar(255) DEFAULT NULL COMMENT '搜索关键词',
  `productmorekeywords1` varchar(255) DEFAULT NULL COMMENT '更多关键词一',
  `productmorekeywords2` varchar(255) DEFAULT NULL COMMENT '更多关键词二',
  `deliverytime` int(11) DEFAULT NULL COMMENT '备货期',
  `productprice` varchar(20) DEFAULT NULL COMMENT '商品一口价',
  `skustock` int(10) DEFAULT NULL COMMENT '数量',
  `isimagedynamic` enum('true','false') DEFAULT NULL COMMENT '商品主图图片类型',
  `isimagewatermark` enum('true','false') DEFAULT NULL COMMENT '图片是否加水印的标识',
  `productunit` int(11) DEFAULT NULL COMMENT '商品单位',
  `packagetype` enum('true','false') DEFAULT NULL COMMENT '销售方式',
  `lotnum` int(11) DEFAULT NULL COMMENT '每包件数',
  `wsvalidnum` int(11) DEFAULT NULL COMMENT '商品有效天数',
  `src` varchar(50) DEFAULT NULL COMMENT '指此商品发布的来源',
  `sku` varchar(100) DEFAULT NULL COMMENT '商品编码即SKU',
  `isbulk` enum('0','1') DEFAULT '0' COMMENT '是否支持批发',
  `bulkorder` int(11) DEFAULT NULL COMMENT '批发最小数量',
  `bulkdiscount` int(11) DEFAULT NULL COMMENT '批发折扣',
  `packagelength` int(11) DEFAULT NULL COMMENT '商品包装长度',
  `packagewidth` int(11) DEFAULT NULL COMMENT '商品包装宽度',
  `packageheight` int(11) DEFAULT NULL COMMENT '商品包装高度',
  `grossweight` varchar(50) DEFAULT NULL COMMENT '商品毛重',
  `ispacksell` enum('true','false') DEFAULT NULL COMMENT '是否自定义计重true为自定义计重false反之',
  `baseunit` int(11) DEFAULT NULL COMMENT 'isPackSell为true时 此项必填 购买几件以内不增加运费',
  `addunit` int(11) DEFAULT NULL COMMENT 'isPackSell为true时 此项必填 每增加件数',
  `addweight` varchar(100) DEFAULT NULL COMMENT 'isPackSell为true时 此项必填 对应增加的重量',
  `promisetemplateid` bigint(18) DEFAULT NULL COMMENT '服务模板id',
  `categoryid` int(11) DEFAULT NULL COMMENT '商品所属类目ID',
  `freighttemplateid` int(11) DEFAULT NULL COMMENT '运费模版ID',
  `imageurl` varchar(255) DEFAULT NULL COMMENT '列表显示图片URL',
  `sizechartid` bigint(18) DEFAULT NULL COMMENT '尺码表模版Id',
  `groupid` int(11) DEFAULT NULL COMMENT '产品组ID',
  `created` int(11) DEFAULT NULL,
  `updated` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `aliexpress_profile_detail`
--

CREATE TABLE IF NOT EXISTS `aliexpress_profile_detail` (
  `id` int(11) unsigned NOT NULL,
  `imageurls` text COMMENT '图片',
  `detail` text COMMENT '描述',
  `summary` text COMMENT '简要描述',
  `property` text COMMENT '选中的sku属性',
  `aeopskuproperty` text COMMENT '可切换图片展示属性',
  `aeopaeproductskus` text COMMENT 'sku',
  `aeopaeproductpropertys` text COMMENT '商品细节属性',
  `created` int(11) DEFAULT NULL,
  `updated` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `aliexpress_promise_template`
--

CREATE TABLE IF NOT EXISTS `aliexpress_promise_template` (
  `templateid` bigint(18) NOT NULL COMMENT '服务模板id',
  `uid` int(10) DEFAULT NULL COMMENT '小老板uid',
  `selleruserid` varchar(100) DEFAULT NULL COMMENT '速卖通账号',
  `name` varchar(255) DEFAULT NULL COMMENT '服务模板名',
  `created` int(11) DEFAULT NULL,
  `updated` int(11) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `aliexpress_shippedorder_log`
--

CREATE TABLE IF NOT EXISTS `aliexpress_shippedorder_log` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键',
  `uid` int(10) NOT NULL COMMENT '小老板主账号ID',
  `operator` int(10) NOT NULL COMMENT '标记人ID',
  `sellerloginid` varchar(100) NOT NULL COMMENT '速卖通登陆账户',
  `aliexpress_uid` int(10) NOT NULL COMMENT '速卖通账户表ID',
  `order_id` bigint(20) NOT NULL COMMENT '小老板订单号',
  `servicename` varchar(100) NOT NULL COMMENT '实际发货物流服务（代码）',
  `servicename_cn` varchar(100) DEFAULT NULL COMMENT '发货物流服务中文名',
  `logisticsno` varchar(100) DEFAULT NULL COMMENT '物流追踪号',
  `description` varchar(100) DEFAULT NULL COMMENT '备注（只能是英文）',
  `sendtype` varchar(100) NOT NULL COMMENT '状态包括：全部发货(all)、部分发货(part)',
  `outref` varchar(100) NOT NULL COMMENT '用户需要发货的订单id，速卖通订单号',
  `trackingwebsite` varchar(100) DEFAULT NULL COMMENT '当serviceName=other的情况时，需要填写对应的追踪网址',
  `status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '标记状态1标记成功，0标记失败',
  `message` text COMMENT '错误信息',
  `create_time` int(11) DEFAULT NULL COMMENT '创建时间',
  `update_time` int(11) DEFAULT NULL COMMENT '修改时间',
  PRIMARY KEY (`id`),
  KEY `sellerloginid` (`sellerloginid`),
  KEY `order_id` (`order_id`),
  KEY `outref` (`outref`),
  KEY `status` (`status`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `amazon_item`
--

CREATE TABLE IF NOT EXISTS `amazon_item` (
  `ASIN` varchar(50) NOT NULL COMMENT '亚马逊编号',
  `Binding` varchar(255) DEFAULT NULL,
  `Brand` varchar(255) DEFAULT NULL COMMENT '品牌',
  `Feature` text COMMENT '特征（最多有5个）',
  `Label` varchar(255) DEFAULT NULL COMMENT '标签',
  `Manufacturer` varchar(255) DEFAULT NULL,
  `Model` varchar(255) DEFAULT NULL COMMENT '模型',
  `PartNumber` varchar(255) DEFAULT NULL,
  `ProductGroup` varchar(255) DEFAULT NULL,
  `ProductTypeName` varchar(255) DEFAULT NULL,
  `Publisher` varchar(255) DEFAULT NULL,
  `SmallImage` text COMMENT '小图',
  `Studio` varchar(255) DEFAULT NULL,
  `Title` text COMMENT '标题',
  `Color` varchar(100) DEFAULT NULL COMMENT '颜色',
  `marketplace_short` varchar(50) NOT NULL COMMENT '网店简写',
  `merchant_id` varchar(50) NOT NULL COMMENT '账号ID',
  `origin_ASIN` varchar(50) DEFAULT NULL COMMENT '来源ASIN(可以理解为父asin,来源于报告中asin)',
  `Product_id` varchar(100) DEFAULT NULL COMMENT '产品ID',
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '编号(唯一标记)',
  PRIMARY KEY (`ASIN`,`marketplace_short`,`merchant_id`),
  UNIQUE KEY `id` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `amazon_open_list_report`
--

CREATE TABLE IF NOT EXISTS `amazon_open_list_report` (
  `report_id` varchar(50) NOT NULL COMMENT '报告ID',
  `merchant_id` varchar(50) DEFAULT NULL COMMENT '卖家账号',
  `item_name` varchar(250) DEFAULT NULL COMMENT '商品名称',
  `listing_id` varchar(50) DEFAULT NULL COMMENT 'ListID',
  `seller_sku` varchar(50) DEFAULT NULL COMMENT '卖家SKU',
  `price` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '商品价格',
  `quantity` int(11) NOT NULL DEFAULT '0' COMMENT '商品数量',
  `open_date` datetime NOT NULL COMMENT '开售时间',
  `product_id_type` varchar(255) DEFAULT NULL COMMENT '商品ID类型（EAN CPU ASIN等）',
  `item_note` text COMMENT '商品状态补充说明',
  `item_condition` varchar(255) DEFAULT NULL COMMENT '商品状态（new like new used等）',
  `will_ship_internationally` varchar(255) DEFAULT NULL COMMENT '是否支持跨国递送',
  `expedited_shipping` varchar(255) DEFAULT NULL COMMENT '是否支持快速递送',
  `product_id` varchar(255) NOT NULL DEFAULT '' COMMENT '商品ID',
  `pending_quantity` int(11) NOT NULL DEFAULT '0' COMMENT '未付款订单数',
  `fulfillment_channel` varchar(255) DEFAULT NULL COMMENT '发货渠道',
  `process_status` char(1) NOT NULL COMMENT '产品处理进度（P=待处理 ， C=已处理）',
  `marketplace_short` varchar(50) NOT NULL COMMENT '网店简写',
  PRIMARY KEY (`report_id`,`product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `amazon_ticket`
--

CREATE TABLE IF NOT EXISTS `amazon_ticket` (
  `ticket_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `ticketID` int(11) unsigned NOT NULL DEFAULT '0',
  `dept_id` int(10) unsigned NOT NULL DEFAULT '1',
  `priority_id` int(10) unsigned NOT NULL DEFAULT '2',
  `topic_id` int(10) unsigned NOT NULL DEFAULT '0',
  `staff_id` int(10) unsigned NOT NULL DEFAULT '0',
  `email` varchar(120) NOT NULL DEFAULT '',
  `related_email_id` int(11) NOT NULL,
  `name` varchar(32) NOT NULL DEFAULT '',
  `subject` varchar(150) NOT NULL DEFAULT '[no subject]',
  `helptopic` varchar(255) DEFAULT NULL,
  `phone` varchar(16) DEFAULT NULL,
  `phone_ext` varchar(8) DEFAULT NULL,
  `ip_address` varchar(16) NOT NULL DEFAULT '',
  `status` enum('open','closed') NOT NULL DEFAULT 'open',
  `type` tinyint(4) NOT NULL COMMENT '0--其他1--订单留言2--客户非订单留言3---amazon官方信息4--卖家主动发邮件',
  `amazon_order_id` varchar(50) NOT NULL COMMENT 'amazon平台订单id',
  `source` enum('Web','Email','Phone','Other') NOT NULL DEFAULT 'Other',
  `isoverdue` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `isanswered` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `duedate` datetime DEFAULT NULL,
  `reopened` datetime DEFAULT NULL,
  `closed` datetime DEFAULT NULL,
  `lastmessage` datetime DEFAULT NULL,
  `lastresponse` datetime DEFAULT NULL,
  `created` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`ticket_id`),
  UNIQUE KEY `email_extid` (`ticketID`,`email`),
  KEY `dept_id` (`dept_id`),
  KEY `staff_id` (`staff_id`),
  KEY `status` (`status`),
  KEY `priority_id` (`priority_id`),
  KEY `created` (`created`),
  KEY `closed` (`closed`),
  KEY `duedate` (`duedate`),
  KEY `topic_id` (`topic_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `amazon_ticket_attachment`
--

CREATE TABLE IF NOT EXISTS `amazon_ticket_attachment` (
  `attach_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `ticket_id` int(11) unsigned NOT NULL DEFAULT '0',
  `ref_id` int(11) unsigned NOT NULL DEFAULT '0',
  `ref_type` enum('M','R') NOT NULL DEFAULT 'M',
  `file_size` varchar(32) NOT NULL DEFAULT '',
  `file_name` varchar(128) NOT NULL DEFAULT '',
  `file_key` varchar(128) NOT NULL DEFAULT '',
  `deleted` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `created` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated` datetime DEFAULT NULL,
  PRIMARY KEY (`attach_id`),
  KEY `ticket_id` (`ticket_id`),
  KEY `ref_type` (`ref_type`),
  KEY `ref_id` (`ref_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `amazon_ticket_message`
--

CREATE TABLE IF NOT EXISTS `amazon_ticket_message` (
  `msg_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `ticket_id` int(11) unsigned NOT NULL DEFAULT '0',
  `messageId` varchar(255) DEFAULT NULL,
  `message` text NOT NULL,
  `headers` text,
  `source` varchar(16) DEFAULT NULL,
  `ip_address` varchar(16) DEFAULT NULL,
  `created` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated` datetime DEFAULT NULL,
  PRIMARY KEY (`msg_id`),
  KEY `ticket_id` (`ticket_id`),
  KEY `msgId` (`messageId`),
  FULLTEXT KEY `message` (`message`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `amazon_ticket_response`
--

CREATE TABLE IF NOT EXISTS `amazon_ticket_response` (
  `response_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `msg_id` int(11) unsigned NOT NULL DEFAULT '0',
  `ticket_id` int(11) unsigned NOT NULL DEFAULT '0',
  `staff_id` int(11) unsigned NOT NULL DEFAULT '0',
  `staff_name` varchar(32) NOT NULL DEFAULT '',
  `response` text NOT NULL,
  `ip_address` varchar(16) NOT NULL DEFAULT '',
  `created` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`response_id`),
  KEY `ticket_id` (`ticket_id`),
  KEY `msg_id` (`msg_id`),
  KEY `staff_id` (`staff_id`),
  FULLTEXT KEY `response` (`response`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `amz_order`
--

CREATE TABLE IF NOT EXISTS `amz_order` (
  `AmazonOrderId` varchar(50) NOT NULL COMMENT 'Amazon Order Id',
  `merchant_id` varchar(55) NOT NULL DEFAULT '' COMMENT '卖家的amazon 商家编码',
  `marketplace_short` char(2) NOT NULL DEFAULT '' COMMENT 'amazon店铺国家2位代码，对应marketplace',
  `LastUpdateDate` timestamp NULL DEFAULT NULL COMMENT '最后修改时间',
  `PurchaseDate` timestamp NULL DEFAULT NULL,
  `Status` varchar(30) NOT NULL DEFAULT '' COMMENT '状态',
  `SalesChannel` varchar(45) NOT NULL DEFAULT '',
  `OrderChannel` varchar(45) NOT NULL DEFAULT '',
  `ShipServiceLevel` varchar(45) NOT NULL DEFAULT '',
  `Name` varchar(255) NOT NULL DEFAULT '',
  `AddressLine1` varchar(255) NOT NULL DEFAULT '',
  `AddressLine2` varchar(255) NOT NULL DEFAULT '',
  `AddressLine3` varchar(255) NOT NULL DEFAULT '',
  `County` varchar(255) NOT NULL DEFAULT '' COMMENT '镇,县',
  `City` varchar(255) NOT NULL DEFAULT '',
  `District` varchar(255) NOT NULL DEFAULT '',
  `State` varchar(255) NOT NULL DEFAULT '',
  `PostalCode` varchar(255) NOT NULL DEFAULT '',
  `CountryCode` char(2) NOT NULL DEFAULT '' COMMENT '国家代码',
  `Phone` varchar(255) NOT NULL DEFAULT '' COMMENT '电话号码',
  `Currency` char(3) NOT NULL DEFAULT '' COMMENT '订单货币',
  `Amount` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '订单金额',
  `PaymentMethod` varchar(45) NOT NULL DEFAULT '' COMMENT '支付方式',
  `BuyerEmail` varchar(255) NOT NULL DEFAULT '',
  `create_time` timestamp NULL DEFAULT NULL COMMENT 'Eagle系统获取并且写入这个Record的时间',
  `type` varchar(5) NOT NULL DEFAULT 'MFN' COMMENT '这个不是amazon订单header的原始属性值。 订单类型 --"MFN" : Manually Fulfillment "AFN" : Amazon Fulfillment (FBA)',
  `BuyerName` varchar(100) DEFAULT NULL COMMENT '购买人的名字',
  `NumberOfItemsShipped` int(10) DEFAULT NULL COMMENT '已经shipped的item个数',
  `NumberOfItemsUnshipped` int(10) DEFAULT NULL COMMENT '还没有shipped的item个数',
  `ShipmentServiceLevelCategory` varchar(100) DEFAULT NULL COMMENT 'The shipment service level category of the order------ Expedited FreeEconomy NextDay SameDay SecondDay Scheduled Standard',
  `EarliestShipDate` int(11) DEFAULT NULL COMMENT 'The start of the time period that you have committed to ship the order',
  `LatestShipDate` int(11) DEFAULT NULL COMMENT 'The end of the time period that you have committed to ship the order',
  `EarliestDeliveryDate` int(11) DEFAULT NULL COMMENT 'The start of the time period that you have commited to fulfill the order',
  `LatestDeliveryDate` int(11) DEFAULT NULL COMMENT 'The end of the time period that you have commited to fulfill the order',
  PRIMARY KEY (`AmazonOrderId`),
  KEY `index2` (`BuyerEmail`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Amazon Order 的信息';

-- --------------------------------------------------------

--
-- Table structure for table `amz_order_detail`
--

CREATE TABLE IF NOT EXISTS `amz_order_detail` (
  `AmazonOrderId` varchar(45) NOT NULL DEFAULT '',
  `OrderItemId` varchar(50) NOT NULL DEFAULT '' COMMENT 'amazon 的item id',
  `SellerSKU` varchar(45) NOT NULL DEFAULT '',
  `ASIN` varchar(255) NOT NULL DEFAULT '',
  `Title` varchar(501) NOT NULL DEFAULT '',
  `Amount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `QuantityOrdered` int(11) NOT NULL DEFAULT '0',
  `QuantityShipped` decimal(10,2) NOT NULL DEFAULT '0.00',
  `ItemPrice` decimal(10,2) NOT NULL DEFAULT '0.00',
  `ShippingPrice` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '该产品分享的运费',
  `ShippingDiscount` decimal(10,2) DEFAULT '0.00',
  `ShippingTax` decimal(10,2) NOT NULL DEFAULT '0.00',
  `GiftWrapPrice` decimal(10,2) NOT NULL DEFAULT '0.00',
  `GiftWrapTax` decimal(10,2) NOT NULL DEFAULT '0.00',
  `ItemTax` decimal(10,2) NOT NULL DEFAULT '0.00',
  `PromotionDiscount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `GiftMessageText` varchar(255) NOT NULL DEFAULT '',
  `GiftWrapLevel` varchar(255) NOT NULL DEFAULT '',
  `PromotionIds` varchar(255) NOT NULL DEFAULT '' COMMENT '多个promotion 的 id 以逗号隔开显示在这里',
  `CODFee` decimal(10,2) NOT NULL DEFAULT '0.00',
  `CODFeeDiscount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `InvoiceRequirement` varchar(255) NOT NULL DEFAULT '' COMMENT 'Invoice 信息',
  `BuyerSelectedInvoiceCategory` varchar(255) NOT NULL DEFAULT '' COMMENT 'Invoice 信息',
  `InvoiceTitle` varchar(255) NOT NULL DEFAULT '' COMMENT 'Invoice 信息',
  `InvoiceInformation` varchar(255) NOT NULL DEFAULT '' COMMENT 'Invoice 信息',
  PRIMARY KEY (`OrderItemId`),
  KEY `index2` (`AmazonOrderId`),
  KEY `index3` (`SellerSKU`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='amazon order details about the SKU and product info';

-- --------------------------------------------------------

--
-- Table structure for table `amz_shop_products`
--

CREATE TABLE IF NOT EXISTS `amz_shop_products` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `seller_id` varchar(50) NOT NULL COMMENT 'amazon seller id',
  `marketplace_short` char(2) NOT NULL COMMENT 'Marketplace 缩短',
  `sku` varchar(250) NOT NULL DEFAULT '' COMMENT '产品sku',
  `is_active` tinyint(4) NOT NULL COMMENT '0:inactive\n1:active',
  `qty_available` int(11) NOT NULL DEFAULT '0' COMMENT '刊登在售的数量',
  `on_sale_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'timestamp\n上架日期',
  `first_month_sold_qty` int(11) NOT NULL DEFAULT '0' COMMENT '上架首月销量',
  `last_month_sold_qty` int(11) NOT NULL DEFAULT '0' COMMENT '最近一个月销量',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Amazon 网店里的产品管理' AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `auth_role`
--

CREATE TABLE IF NOT EXISTS `auth_role` (
  `rid` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '角色ID（主键）',
  `name` varchar(50) NOT NULL DEFAULT '' COMMENT '角色名',
  `note` varchar(255) NOT NULL DEFAULT '' COMMENT '角色备注信息',
  `is_active` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '是否启用0:否 1：是',
  PRIMARY KEY (`rid`),
  UNIQUE KEY `unidx_name` (`name`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COMMENT='角色表' AUTO_INCREMENT=2 ;

--
-- Dumping data for table `auth_role`
--

INSERT INTO `auth_role` (`rid`, `name`, `note`, `is_active`) VALUES
(1, 'role1', '权限', 1);

-- --------------------------------------------------------

--
-- Table structure for table `auth_role_has_menu`
--

CREATE TABLE IF NOT EXISTS `auth_role_has_menu` (
  `role_id` int(11) unsigned NOT NULL COMMENT '关联auth_menu中id',
  `menu_id` int(11) unsigned NOT NULL COMMENT '关联系统表authmenu中id',
  PRIMARY KEY (`role_id`,`menu_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='角色与权限关系表';

--
-- Dumping data for table `auth_role_has_menu`
--

INSERT INTO `auth_role_has_menu` (`role_id`, `menu_id`) VALUES
(1, 1);

-- --------------------------------------------------------

--
-- Table structure for table `auth_role_has_user`
--

CREATE TABLE IF NOT EXISTS `auth_role_has_user` (
  `role_id` int(11) unsigned NOT NULL COMMENT '角色ID',
  `user_id` int(11) unsigned NOT NULL COMMENT '用户ID',
  PRIMARY KEY (`role_id`,`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='子用户与角色关联表';

--
-- Dumping data for table `auth_role_has_user`
--

INSERT INTO `auth_role_has_user` (`role_id`, `user_id`) VALUES
(1, 1);

-- --------------------------------------------------------

--
-- Table structure for table `cm_ebay_dispute`
--

CREATE TABLE IF NOT EXISTS `cm_ebay_dispute` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `disputeid` bigint(20) NOT NULL,
  `selleruserid` varchar(50) NOT NULL DEFAULT '' COMMENT '卖家账号',
  `buyeruserid` varchar(50) NOT NULL DEFAULT '' COMMENT '买家账号',
  `user_role` varchar(50) NOT NULL DEFAULT '' COMMENT '发起人：SELLER,BUYER,EBAY',
  `itemid` bigint(20) NOT NULL DEFAULT '0' COMMENT '刊登号',
  `transactionid` varchar(14) NOT NULL DEFAULT '' COMMENT '交易号',
  `disputereason` varchar(64) NOT NULL DEFAULT '' COMMENT '纠纷原因主要, BuyerHasNotPaid, TransactionMutuallyCanceled ',
  `disputeexplanation` varchar(64) NOT NULL DEFAULT '' COMMENT '纠纷原因的详细说明 ',
  `disputerecordtype` varchar(64) NOT NULL DEFAULT '' COMMENT '纠纷类型,  ItemNotReceived , UnpaidItem',
  `disputestate` varchar(128) NOT NULL DEFAULT '' COMMENT '纠纷状态',
  `disputestatus` varchar(128) NOT NULL DEFAULT '' COMMENT '纠纷状态附加',
  `purchaseprotection` tinyint(1) NOT NULL DEFAULT '1' COMMENT 'inr用户是否符合保护条例',
  `escalation` tinyint(1) NOT NULL DEFAULT '0' COMMENT '用户不满意并上报到ebay',
  `has_read` tinyint(1) NOT NULL DEFAULT '0' COMMENT '已读',
  `disputemessages` longtext NOT NULL COMMENT '聊天记录, 数组 ',
  `disputecreatedtime` int(11) NOT NULL COMMENT '纠纷发起时间',
  `create_time` int(11) NOT NULL,
  `update_time` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `disputeid` (`disputeid`),
  KEY `selleruserid` (`selleruserid`),
  KEY `has_read` (`has_read`) USING BTREE
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='售前纠纷表' AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `cm_ebay_feedback`
--

CREATE TABLE IF NOT EXISTS `cm_ebay_feedback` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `feedback_id` bigint(20) unsigned NOT NULL DEFAULT '0' COMMENT '评价号',
  `ebay_uid` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '所属卖家,也即被评人managedb saas_ebay_user 中ebay_uid',
  `selleruserid` varchar(50) NOT NULL DEFAULT '' COMMENT '订单来源平台卖家用户名',
  `transaction_id` varchar(14) NOT NULL DEFAULT '' COMMENT '交易号',
  `od_ebay_transaction_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT 'od_ebay_transaction表id',
  `itemid` bigint(20) unsigned NOT NULL DEFAULT '0' COMMENT 'od_ebay_item表itemid',
  `role` varchar(10) NOT NULL DEFAULT '' COMMENT '发起人',
  `commenting_user` varchar(50) NOT NULL DEFAULT '' COMMENT '评价人',
  `commenting_user_score` int(11) unsigned NOT NULL COMMENT '评价人信用',
  `comment_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '评价时间',
  `comment_text` text NOT NULL COMMENT '留言',
  `comment_type` varchar(32) NOT NULL DEFAULT '' COMMENT 'Positive,Negative,Neutral,Withdrawn,IndependentlyWithdrawn',
  `feedback_score` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '总评分数',
  `feedback_response` varchar(255) NOT NULL DEFAULT '' COMMENT '回复内容',
  `followup` varchar(255) NOT NULL DEFAULT '' COMMENT '跟踪内容',
  `response_replaced` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '是否被eBay删除',
  `has_read` tinyint(1) unsigned NOT NULL DEFAULT '1' COMMENT '已读',
  `create_time` int(11) unsigned NOT NULL DEFAULT '0',
  `update_time` int(11) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `idx_feed` (`feedback_id`) USING BTREE,
  KEY `idx_read` (`has_read`) USING BTREE,
  KEY `idx_uid` (`ebay_uid`) USING BTREE,
  KEY `idx_com` (`comment_type`) USING BTREE
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='ebay评价表' AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `cm_ebay_message_type`
--

CREATE TABLE IF NOT EXISTS `cm_ebay_message_type` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `pid` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '父级id',
  `name` varchar(255) NOT NULL DEFAULT '' COMMENT '类型名',
  `level` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '顺序',
  `create_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `idx_pid` (`pid`),
  KEY `idx_level` (`level`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='站内信分类表' AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `cm_ebay_msg_template`
--

CREATE TABLE IF NOT EXISTS `cm_ebay_msg_template` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `template_type` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '类型',
  `template` text NOT NULL COMMENT '模板内容',
  `create_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `idx_tpltype` (`template_type`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='站内信模板表' AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `cm_ebay_msg_template_type`
--

CREATE TABLE IF NOT EXISTS `cm_ebay_msg_template_type` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `pid` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '父级id',
  `name` varchar(255) NOT NULL DEFAULT '' COMMENT '类型名',
  `level` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '顺序',
  `type` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '0文件夹 1站内信模板',
  `template` varchar(255) NOT NULL DEFAULT '' COMMENT '模板内容',
  `create_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `idx_level` (`level`),
  KEY `idx_pid` (`pid`),
  KEY `idx_type` (`type`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='站内信分类表' AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `cm_ebay_mymessage`
--

CREATE TABLE IF NOT EXISTS `cm_ebay_mymessage` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `uid` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '用户uid',
  `ebay_uid` int(11) unsigned NOT NULL DEFAULT '0' COMMENT 'saas_ebay_user表id',
  `order_id` int(10) DEFAULT NULL COMMENT '订单id',
  `itemid` bigint(16) DEFAULT NULL COMMENT '商品ID',
  `messageid` bigint(16) DEFAULT NULL COMMENT '消息ID',
  `externalmessageid` bigint(16) DEFAULT NULL COMMENT 'ebay外部ID',
  `is_read` tinyint(1) DEFAULT '0' COMMENT '0未读',
  `is_flagged` tinyint(1) unsigned DEFAULT NULL COMMENT '是否加星标',
  `replied` tinyint(2) DEFAULT '0' COMMENT '已回复',
  `msg_type_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '文件夹id',
  `expirationdate` varchar(32) DEFAULT NULL COMMENT '到期时间',
  `listingstatus` varchar(16) DEFAULT NULL COMMENT '帖子状态',
  `messagetype` varchar(128) DEFAULT NULL COMMENT '消息类型',
  `questiontype` varchar(32) DEFAULT NULL COMMENT '问题类型',
  `receivedate_time` int(11) DEFAULT NULL,
  `receivedate` varchar(32) DEFAULT NULL COMMENT '信息收到日期',
  `recipientuserid` varchar(32) DEFAULT NULL COMMENT '显示的收件人uid',
  `sender` varchar(128) DEFAULT NULL COMMENT '寄件人',
  `responseenabled` varchar(8) DEFAULT NULL COMMENT '可否被回复',
  `subject` varchar(255) DEFAULT NULL COMMENT '主题',
  `from_who` varchar(50) DEFAULT NULL COMMENT 'message自动分类类类型eBay,Memgers,HighPriority',
  `highpriority` varchar(20) DEFAULT NULL COMMENT '是否是high priority站内信，true，false',
  PRIMARY KEY (`id`),
  UNIQUE KEY `messageid` (`messageid`),
  KEY `isread` (`is_read`),
  KEY `recipientuserid` (`recipientuserid`),
  KEY `sender` (`sender`),
  KEY `itemid` (`itemid`),
  KEY `order_id` (`order_id`),
  KEY `replied` (`replied`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='message表' AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `cm_ebay_mymessage_detail`
--

CREATE TABLE IF NOT EXISTS `cm_ebay_mymessage_detail` (
  `messageid` bigint(16) NOT NULL DEFAULT '0' COMMENT 'ebay上的唯一messageid ',
  `responseurl` text,
  `text` longtext COMMENT 'message详细',
  PRIMARY KEY (`messageid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='message详细表';

-- --------------------------------------------------------

--
-- Table structure for table `cm_ebay_replymessage`
--

CREATE TABLE IF NOT EXISTS `cm_ebay_replymessage` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `context` text COMMENT '回复内容',
  `created` char(13) DEFAULT NULL COMMENT '回复时间',
  `dealuid` int(11) DEFAULT NULL COMMENT '回复处理人',
  `msgid` varchar(25) DEFAULT '' COMMENT '对应msg表的ID',
  `iscomment` tinyint(2) DEFAULT '0' COMMENT '非零为评论',
  `isdraft` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '非0为草稿',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='ebay 回复 message详细表' AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `cm_ebay_template`
--

CREATE TABLE IF NOT EXISTS `cm_ebay_template` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `template_type` int(11) NOT NULL COMMENT '类型',
  `template` varchar(255) NOT NULL DEFAULT '' COMMENT '模板内容',
  `create_time` int(11) NOT NULL,
  `update_time` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_type` (`template_type`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `cm_ebay_template_type`
--

CREATE TABLE IF NOT EXISTS `cm_ebay_template_type` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `pid` int(11) NOT NULL COMMENT '父级id',
  `name` varchar(255) NOT NULL DEFAULT '' COMMENT '类型名',
  `level` int(11) NOT NULL DEFAULT '0' COMMENT '顺序',
  `create_time` int(11) NOT NULL,
  `update_time` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_pid` (`pid`,`level`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `cm_ebay_usercase`
--

CREATE TABLE IF NOT EXISTS `cm_ebay_usercase` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uid` int(11) NOT NULL,
  `mytype` tinyint(1) NOT NULL DEFAULT '0' COMMENT '基本纠纷类型: 未付款,取消订单,未收到货，货不符',
  `selleruserid` varchar(50) NOT NULL DEFAULT '' COMMENT '卖家账户',
  `buyeruserid` varchar(100) NOT NULL DEFAULT '' COMMENT '买家账号',
  `caseid` varchar(40) NOT NULL DEFAULT '' COMMENT '纠纷号',
  `type` varchar(100) NOT NULL DEFAULT '' COMMENT '纠纷类型',
  `status_type` varchar(100) NOT NULL DEFAULT '' COMMENT '纠纷状态类型',
  `status_value` varchar(100) NOT NULL DEFAULT '' COMMENT '纠纷状态类型对应的值',
  `itemid` bigint(16) NOT NULL DEFAULT '0' COMMENT '刊登号',
  `itemtitle` varchar(255) NOT NULL COMMENT 'item的title',
  `transactionid` varchar(14) NOT NULL DEFAULT '' COMMENT '交易号',
  `casequantity` int(2) NOT NULL DEFAULT '0' COMMENT '争议数量',
  `caseamount` varchar(15) NOT NULL COMMENT '争议金额',
  `created_date` int(10) NOT NULL COMMENT '创建日期',
  `lastmodified_date` int(10) NOT NULL COMMENT '最后修改日期',
  `respondbydate` int(10) NOT NULL COMMENT '失效日期',
  `has_read` tinyint(1) NOT NULL DEFAULT '0' COMMENT '已读',
  `order_id` int(10) NOT NULL,
  `create_time` int(11) NOT NULL,
  `update_time` int(11) NOT NULL,
  `order_source_srn` int(11) unsigned DEFAULT '0' COMMENT 'od_ebay_order表salesrecordnum',
  PRIMARY KEY (`id`),
  UNIQUE KEY `caseid` (`caseid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='用户售后纠纷表' AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `cm_ebay_usercase_ebpdetail`
--

CREATE TABLE `cm_ebay_usercase_ebpdetail` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `caseid` bigint(15) NOT NULL COMMENT 'caseID',
  `agreedrefundamount` varchar(20) DEFAULT '' COMMENT '同意退款金额',
  `appeal` text COMMENT '买家发起的上诉详细',
  `buyerreturnshipment` text COMMENT '买家退货信息 及退货跟踪号',
  `casedocumentinfo` text COMMENT '买家上传的case文档信息,仅德国平台',
  `decision` varchar(100) DEFAULT '' COMMENT '处决人',
  `decisiondate` varchar(100) DEFAULT NULL COMMENT '处决日期',
  `decisionreason` varchar(100) DEFAULT NULL COMMENT '处决原因',
  `decisionreasondetail` text COMMENT '处决原因详细',
  `detailstatus` varchar(100) DEFAULT '0' COMMENT 'case状态',
  `detailstatusinfo` text COMMENT 'case状态详细',
  `fvfcredited` varchar(10) DEFAULT '0' COMMENT '是否将交易费退回卖家',
  `globalid` varchar(100) DEFAULT '' COMMENT 'eBay平台号',
  `initialbuyerexpectation` varchar(100) DEFAULT '' COMMENT '买家初步意见',
  `initialbuyerexpectationdetail` text COMMENT '买家意见详细',
  `notcountedinbuyerprotectioncases` varchar(10) DEFAULT '0' COMMENT '是否对卖家绩效产生影响,true:不影响,false:影响',
  `openreason` varchar(100) DEFAULT '' COMMENT '开启原因',
  `paymentdetail` text COMMENT '支付详细,仅moneyMovement 时会出现',
  `responsehistory` longtext COMMENT '交流记录',
  `returnmerchandiseauthorization` varchar(255) DEFAULT '' COMMENT '退款单号,卖家向买家提供',
  `sellershipment` text COMMENT '买家发货信息,卖家向买家提供',
  `create_time` int(11) DEFAULT NULL,
  `update_time` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `caseid` (`caseid`)
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='用户售后纠纷详细表';

-- --------------------------------------------------------

--
-- Table structure for table `comment_and_chat`
--

CREATE TABLE IF NOT EXISTS `comment_and_chat` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键id',
  `uid` int(10) unsigned NOT NULL COMMENT '用户ID',
  `remark` text COMMENT '备注信息',
  `create_time` int(11) DEFAULT NULL COMMENT '创建时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='用户备注信息表' AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `cr_template`
--

CREATE TABLE IF NOT EXISTS `cr_template` (
  `template_id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '模版编号',
  `template_name` varchar(50) DEFAULT NULL COMMENT '模版名称',
  `template_content` text COMMENT '内容',
  `create_time` int(11) unsigned DEFAULT NULL COMMENT '创建时间',
  `update_time` int(11) unsigned DEFAULT NULL COMMENT '修改时间',
  `template_width` int(10) unsigned DEFAULT '100' COMMENT '画布宽度',
  `template_height` int(10) unsigned DEFAULT '100' COMMENT '画布高度',
  `template_type` enum('地址单','报关单','配货单','发票','商品标签') DEFAULT '地址单' COMMENT '单据类别',
  `template_content_json` text default '' COMMENT '用于记录Html的具体位置,tcpdf使用',
  `template_version` int default 0 COMMENT '用于记录是否新版模板,默认为0, 1表示新版',
  PRIMARY KEY (`template_id`),
  UNIQUE KEY `uniq_template_name` (`template_name`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `cs_message`
--

CREATE TABLE IF NOT EXISTS `cs_message` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `status` char(1) NOT NULL DEFAULT 'C' COMMENT 'S：发送中 C：已发送，F：错误',
  `cpur_id` int(11) NOT NULL DEFAULT '0' COMMENT '谁说这句话的，user id，如果是客户来的，id=0',
  `create_time` datetime NOT NULL COMMENT '说话时间',
  `subject` text NOT NULL COMMENT 'message标题',
  `content` text NOT NULL COMMENT 'msg 内容',
  `platform` enum('ebay','aliexpress','wish','amazon','dhgate') NOT NULL COMMENT '平台',
  `order_id` varchar(100) NOT NULL COMMENT '订单id 平台',
  `addi_info` text NOT NULL COMMENT '额外附加信息',
  PRIMARY KEY (`id`),
  KEY `order_id` (`order_id`,`platform`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='tracker用到的站内信内容' AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `cs_msg_template`
--

CREATE TABLE IF NOT EXISTS `cs_msg_template` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(200) NOT NULL COMMENT '模板名称',
  `subject` text NOT NULL,
  `body` text COMMENT '模板内容',
  `addi_info` varchar(200) DEFAULT NULL COMMENT 'json格式的附加内容',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='tracking模块发站内信模板' AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `cs_recommend_product`
--

CREATE TABLE IF NOT EXISTS `cs_recommend_product` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `platform` varchar(50) NOT NULL COMMENT 'ebay,wish,aliexpress等',
  `platform_account_id` varchar(255) NOT NULL COMMENT '渠道的账号标示',
  `platform_site_id` varchar(10) DEFAULT '' COMMENT '不同站点的标示, US,FR,DE,CN',
  `listing_id` varchar(100) DEFAULT '' COMMENT '刊登商品的标示，譬如平台sku，或者asin',
  `product_image_url` varchar(255) NOT NULL DEFAULT '',
  `product_name` varchar(255) NOT NULL DEFAULT '',
  `product_url` varchar(255) NOT NULL,
  `life_view_count` int(11) NOT NULL DEFAULT '0' COMMENT '历史给buyer的展示总次数',
  `life_click_count` int(11) NOT NULL DEFAULT '0' COMMENT '历史被buyer的点击总次数',
  `is_on_sale` varchar(1) NOT NULL DEFAULT 'Y' COMMENT '是否在售 Y 或者 N',
  `create_time` datetime NOT NULL,
  `update_time` datetime NOT NULL,
  `is_active` char(1) NOT NULL DEFAULT 'Y' COMMENT 'Y 或 N 该商品如果销售情况不好等 会被设置为不用可用',
  `product_price` decimal(10,2) NOT NULL DEFAULT '0.00',
  `product_min_price` decimal(10,2) NOT NULL DEFAULT '0.00',
  `product_max_price` decimal(10,2) NOT NULL DEFAULT '0.00',
  `product_price_currency` char(3) NOT NULL DEFAULT '' COMMENT '货币,CNY,USD,HKD,EUR',
  PRIMARY KEY (`id`),
  KEY `index1` (`platform`,`platform_account_id`,`platform_site_id`),
  KEY `sdf` (`listing_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `cs_auto_roles` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'role编号',
  `name` varchar(200) NOT NULL COMMENT '规则名称',
  `platform` varchar(30) NOT NULL COMMENT '来源的平台，例如Amazon，SMT，eBay,Wish',
  `accounts` varchar(500) NOT NULL COMMENT '匹配平台账号json格式',
  `nations` text COMMENT '匹配目的地国家json格式',
  `status` varchar(500) NOT NULL COMMENT '物流状态',
  `template_id` int(11) NOT NULL COMMENT '留言模板',
  `priority` int(11) NOT NULL DEFAULT '0' COMMENT '优先顺序',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8 COMMENT='自动匹配规则表';

 
CREATE TABLE IF NOT EXISTS `cs_customer` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `platform_source` varchar(50) DEFAULT NULL COMMENT '订单来源  ebay,amazon,aliexpress等等',
  `last_order_id` varchar(50) DEFAULT NULL COMMENT '最后订单单号',
  `seller_id` varchar(50) DEFAULT NULL COMMENT '卖家ID',
  `customer_id` varchar(50) DEFAULT NULL COMMENT '客户标识码，各平台优先哟好难过buyerid，然后email，phone number 来作为该客户的唯一识别方法',
  `nation_code` char(2) NOT NULL DEFAULT '' COMMENT '国家代码，如CN，US，FR，JP',
  `email` varchar(50) DEFAULT NULL COMMENT 'email address',
  `os_flag` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否outstanding状态，1为是，0为否',
  `create_time` datetime DEFAULT NULL COMMENT '创建时间',
  `update_time` datetime DEFAULT NULL COMMENT '更新时间',
  `last_message_time` datetime DEFAULT NULL COMMENT '最后回复时间',
  `msg_sent_error` char(1) NOT NULL DEFAULT 'N' COMMENT '是否有发信失败',
  `addi_info` text COMMENT '额外附加信息',
  `last_order_time` datetime DEFAULT NULL COMMENT '最后一个订单日期',
  `currency` char(3) NOT NULL DEFAULT 'CNY' COMMENT '货币',
  `life_order_amount` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '总的消费金额',
  `life_order_count` int(11) NOT NULL DEFAULT '0' COMMENT '总的消费次数',
  `customer_nickname` varchar(50) DEFAULT NULL COMMENT '买家呢称',
  PRIMARY KEY (`id`),
  KEY `customerid` (`customer_id`,`seller_id`),
  KEY `platform` (`platform_source`,`seller_id`)
) ENGINE=InnoDB AUTO_INCREMENT=4097 DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;
DROP TABLE IF EXISTS `cs_customer_tags`; 
CREATE TABLE IF NOT EXISTS `cs_customer_tags` (
  `customer_tag_id` int(11) NOT NULL AUTO_INCREMENT COMMENT '主键',
  `customer_id` varchar(30) NOT NULL DEFAULT '' COMMENT '客户号主键',
  `tag_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '标签编号',
  PRIMARY KEY (`customer_tag_id`),
  UNIQUE KEY `index2` (`customer_id`,`customer_tag_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=COMPACT COMMENT='产品和标签的对应信息';
 
CREATE TABLE IF NOT EXISTS `cs_recm_product_perform` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL DEFAULT '0' COMMENT 'recm product id',
  `theday` char(10) NOT NULL DEFAULT '' COMMENT 'e.g. 2010-05-19',
  `view_count` int(11) NOT NULL DEFAULT '0' COMMENT '被展示次数',
  `click_count` int(11) NOT NULL DEFAULT '0' COMMENT '被点击次数',
  PRIMARY KEY (`id`),
  KEY `aa` (`product_id`,`theday`)
) ENGINE=InnoDB AUTO_INCREMENT=63 DEFAULT CHARSET=utf8 COMMENT='推荐产品的表现';

DROP TABLE IF EXISTS `cs_tag`;
CREATE TABLE IF NOT EXISTS `cs_tag` (
  `tag_id` int(11) NOT NULL AUTO_INCREMENT,
  `tag_name` varchar(100) NOT NULL DEFAULT '',
  `color` varchar(20) DEFAULT NULL COMMENT '标签颜色',
  PRIMARY KEY (`tag_id`),
  KEY `index2` (`tag_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=COMPACT COMMENT='标签定义';

DROP TABLE IF EXISTS `cs_ticket_message`;
CREATE TABLE IF NOT EXISTS `cs_ticket_message` (
  `msg_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `ticket_id` int(11) DEFAULT NULL COMMENT '客服app的会话id，内部使用',
  `session_id` varchar(50) DEFAULT NULL COMMENT '对应ticket_session表的session_id，对应平台的session/ticket id',
  `message_id` varchar(255) DEFAULT NULL COMMENT '每个平台返回的主键ID',
  `related_id` varchar(50) DEFAULT NULL COMMENT '订单单号/商品sku',
  `related_type` char(1) DEFAULT NULL COMMENT '相关类别，P：商品，O：订单, S:系统平台',
  `send_or_receiv` int(1) DEFAULT NULL COMMENT '0--表示接收,1--表示发送',
  `content` text COMMENT '内容',
  `headers` text COMMENT '标题',
  `English_content` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL  COMMENT '英文翻译',
  `Chineses_content` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci  NULL COMMENT  '中文翻译',
  `has_read` int(1) DEFAULT NULL COMMENT '0--表示未读,1--表示已读',
  `msg_contact` longtext COMMENT 'msg原始数据内容',
  `created` datetime DEFAULT NULL COMMENT '创建时间',
  `updated` datetime DEFAULT NULL COMMENT '更新时间',
  `addi_info` text COMMENT '额外附加信息',
  `app_time` datetime DEFAULT NULL COMMENT 'eagle系统时间',
  `platform_time` datetime DEFAULT NULL COMMENT '平台实际时间',
  `haveFile` int(1) DEFAULT '0' COMMENT '是否含图片 0--没有图片,1--有图片',
  `fileUrl` text COMMENT '图片地址',
  `status` char(1) DEFAULT 'C' COMMENT 'Pending,Sending,Complete,Fail',
  PRIMARY KEY (`msg_id`),
  KEY `ticket_id` (`ticket_id`),
  KEY `session_id` (`session_id`),
  KEY `222` (`related_id`,`related_type`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
 
DROP TABLE IF EXISTS `cs_ticket_session`;
CREATE TABLE IF NOT EXISTS `cs_ticket_session` (
  `ticket_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `platform_source` varchar(50) DEFAULT NULL COMMENT '订单来源  ebay,amazon,aliexpress等等',
  `message_type` tinyint(4) NOT NULL COMMENT '0--其他1--订单留言2--站内信',
  `related_id` varchar(50) DEFAULT '' COMMENT '订单单号/商品sku',
  `related_type` char(1) DEFAULT NULL COMMENT '相关类别，P：商品，O：订单, S:系统平台',
  `seller_id` varchar(50) DEFAULT NULL COMMENT '卖家ID',
  `buyer_id` varchar(50) DEFAULT NULL COMMENT '买家ID,customer key',
  `session_id` varchar(50) DEFAULT NULL COMMENT '会话ID MsgId，平台的session,ticket id',
  `has_read` tinyint(1) NOT NULL DEFAULT '0' COMMENT '0--表示未读,1--表示已读',
  `has_replied` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否已回复',
  `has_handled` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否已处理',
  `created` datetime DEFAULT NULL COMMENT '创建时间',
  `updated` datetime DEFAULT NULL COMMENT '更新时间',
  `lastmessage` datetime DEFAULT NULL COMMENT '最后回复时间',
  `msg_sent_error` char(1) DEFAULT 'N' COMMENT '是否有发信失败',
  `addi_info` text COMMENT '额外附加信息',
  `last_omit_msg` text COMMENT '最后消息',
  `seller_nickname` varchar(50) DEFAULT NULL COMMENT '卖家呢称',
  `buyer_nickname` varchar(50) DEFAULT NULL COMMENT '买家呢称',
  `original_msg_type` varchar(50) DEFAULT NULL COMMENT '原始信息类型 --暂时敦煌用到',
  `list_contact` text COMMENT 'list原始数据内容 敦煌有用',
  `msgTitle` text COMMENT '标题',
  `session_type` varchar(50) DEFAULT '' COMMENT 'session类型，暂时只有cdiscount用到',
 `session_status` varchar(50) DEFAULT '' COMMENT 'session状态，暂时只有cdiscount用到', 
 `item_id` varchar(255) DEFAULT '' COMMENT 'itemId,暂时只有priceminster用到',
  PRIMARY KEY (`ticket_id`),
  KEY `customerKey` (`buyer_id`,`platform_source`,`seller_id`),
  KEY `222` (`related_id`,`related_type`),
  KEY `seller` (`seller_id`),
  KEY `222222` (`seller_nickname`),
  KEY `session_index` (`session_id`,`platform_source`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------


--
-- Table structure for table `dely_delivery`
--

CREATE TABLE IF NOT EXISTS `dely_delivery` (
  `delivery_id` int(11) unsigned zerofill NOT NULL AUTO_INCREMENT COMMENT '包裹id',
  `tracking_number` varchar(50) NOT NULL DEFAULT '' COMMENT '发货单号',
  `tracking_link` varchar(100) NOT NULL DEFAULT '' COMMENT '跟踪号查询网址',
  `serial_number` varchar(11) NOT NULL DEFAULT '' COMMENT '包裹流水号(日期+当日序号 例：14031500001)',
  `delivery_status` tinyint(2) unsigned NOT NULL DEFAULT '0' COMMENT '包裹状态 (自由仓)0:待分派,1:待交运1,2:待打单,(海外仓)5:待上传,6:待确认,7:待发货,(其他)3:已发货,4:已作废',
  `is_manual` tinyint(2) unsigned NOT NULL COMMENT '挂起状态，1：已挂起，0：未挂起',
  `selleruserid` varchar(50) NOT NULL DEFAULT '' COMMENT '订单来源平台卖家用户名',
  `saas_platform_user_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT 'saas库平台用户id',
  `order_source` varchar(50) NOT NULL DEFAULT '' COMMENT '订单来源  ebay,amazon,aliexpress,custom',
  `customer_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT 'od_customer 表id',
  `source_buyer_user_id` varchar(255) NOT NULL DEFAULT '' COMMENT '来源平台买家用户名',
  `consignee` varchar(255) NOT NULL DEFAULT '' COMMENT '收货人',
  `consignee_country` varchar(255) NOT NULL DEFAULT '' COMMENT '收货人国家',
  `consignee_country_code` char(2) NOT NULL DEFAULT '' COMMENT '收货人国家代码',
  `consignee_city` varchar(255) NOT NULL DEFAULT '' COMMENT '收货人城市',
  `consignee_province` varchar(255) NOT NULL DEFAULT '' COMMENT '收货人省',
  `consignee_district` varchar(255) NOT NULL DEFAULT '' COMMENT '收货人区',
  `consignee_county` varchar(255) NOT NULL DEFAULT '' COMMENT '收货人镇',
  `consignee_address_line1` varchar(255) NOT NULL DEFAULT '' COMMENT '收货人地址1',
  `consignee_address_line2` varchar(255) NOT NULL DEFAULT '' COMMENT '收货人地址2',
  `consignee_address_line3` varchar(255) NOT NULL DEFAULT '' COMMENT '收货人地址3',
  `consignee_postal_code` varchar(255) NOT NULL DEFAULT '' COMMENT '收货地邮编',
  `consignee_phone` varchar(255) NOT NULL DEFAULT '' COMMENT '收货人电话',
  `consignee_email` varchar(255) NOT NULL DEFAULT '' COMMENT '收货人email',
  `consignee_company` varchar(255) NOT NULL DEFAULT '' COMMENT '收货人公司',
  `warehouse_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '仓库id',
  `carrier_code` varchar(255) NOT NULL DEFAULT '' COMMENT '物流商code',
  `shipping_method_code` varchar(255) NOT NULL DEFAULT '' COMMENT '物流方式code',
  `order_source_shipping_method` varchar(255) NOT NULL DEFAULT '' COMMENT '平台物流方式code',
  `md5_identify` char(32) NOT NULL DEFAULT '' COMMENT 'md5标识(用于包裹拆分合并)',
  `delivery_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '发货时间',
  `create_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  `print_url` varchar(255) NOT NULL DEFAULT '' COMMENT '打印的url',
  `error_info` varchar(255) NOT NULL DEFAULT '' COMMENT '物流/海外仓错误信息',
  `carrier_status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否已交运0：否 1：是',
  `carrier_type` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '0:普通物流商  1:海外仓',
  `tpodo_sn` varchar(100) NOT NULL DEFAULT '' COMMENT '第三方（海外仓）出库单号',
  `tracking_status` varchar(200) NOT NULL DEFAULT '' COMMENT '查询轨迹状态说明',
  PRIMARY KEY (`delivery_id`),
  KEY `idx_delstat` (`delivery_status`) USING BTREE,
  KEY `idx_tracnumsta` (`tracking_number`,`delivery_status`) USING BTREE,
  KEY `idx_sernumsta` (`serial_number`,`delivery_status`) USING BTREE,
  KEY `idx_buyertime` (`source_buyer_user_id`,`update_time`),
  KEY `idx_sellertime` (`selleruserid`,`update_time`),
  KEY `idx_buyeremail` (`consignee_email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='包裹表' AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `dely_delivery_item`
--

CREATE TABLE IF NOT EXISTS `dely_delivery_item` (
  `delivery_item_id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '包裹商品表',
  `product_name` varchar(255) NOT NULL DEFAULT '' COMMENT '商品名称',
  `sku` varchar(250) NOT NULL DEFAULT '' COMMENT '主图',
  `order_source_order_item_id` varchar(50) NOT NULL DEFAULT '' COMMENT 'od_ebay_transaction中的id',
  `source_item_id` varchar(50) NOT NULL DEFAULT '' COMMENT '平台刊登itemid',
  `quantity` int(11) unsigned NOT NULL DEFAULT '1' COMMENT '数量',
  `order_id` int(11) unsigned zerofill NOT NULL DEFAULT '00000000000' COMMENT '商品来源订单id',
  `order_item_id` int(11) NOT NULL DEFAULT '0' COMMENT '订单商品表id',
  `delivery_id` int(11) unsigned zerofill NOT NULL DEFAULT '00000000000' COMMENT '包裹号',
  `photo_primary` varchar(255) NOT NULL DEFAULT '' COMMENT '主图',
  `create_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  `platform_sku` varchar(50) NOT NULL DEFAULT '' COMMENT '平台sku',
  `is_bundle` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '是否是捆绑商品，1：是0：否',
  `bdsku` varchar(50) NOT NULL DEFAULT '' COMMENT '捆绑sku',
  PRIMARY KEY (`delivery_item_id`),
  KEY `idx_order_id` (`order_id`) USING BTREE,
  KEY `idx_delivery_id` (`delivery_id`) USING BTREE,
  KEY `idx_itemid` (`order_source_order_item_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='包裹商品表' AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `dely_shipping_method`
--

CREATE TABLE IF NOT EXISTS `dely_shipping_method` (
  `carrier_code` varchar(50) NOT NULL DEFAULT '0' COMMENT '物流商code',
  `shipping_method_code` varchar(50) NOT NULL DEFAULT '' COMMENT '物流方式code',
  `shipping_method_name` varchar(100) NOT NULL DEFAULT '' COMMENT '用户表物流方式名',
  `platform_amazon` varchar(50) NOT NULL DEFAULT '' COMMENT '向亚马逊发送的物流商号',
  `platform_ebay` varchar(50) NOT NULL DEFAULT '' COMMENT '向ebay发送的物流商号',
  `platform_aliexpress` varchar(50) NOT NULL DEFAULT '' COMMENT '向速卖通发送的物流商号',
  `platform_wish` varchar(20) NOT NULL DEFAULT '' COMMENT '向wish发送的物流商号',
  `extra_info` varchar(500) NOT NULL DEFAULT '' COMMENT '附加信息',
  `type` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '类型：0系统类型1用户类型',
  `is_active` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '是否启用:0不启用1启用',
  `pre_weight` decimal(10,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '首重',
  `pre_weight_price` decimal(10,4) unsigned NOT NULL DEFAULT '0.0000' COMMENT '首重价',
  `add_weight` decimal(10,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '续重',
  `add_weight_price` decimal(10,4) unsigned NOT NULL DEFAULT '0.0000' COMMENT '续重价',
  `create_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  `logistics_aging` int(10) DEFAULT NULL,
  PRIMARY KEY (`shipping_method_code`,`carrier_code`),
  KEY `idx_typeacti` (`type`,`is_active`),
  KEY `idx_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='物流方式表';

-- --------------------------------------------------------

--
-- Table structure for table `dely_tracking_numbers`
--

CREATE TABLE IF NOT EXISTS `dely_tracking_numbers` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键',
  `carrier_code` varchar(50) NOT NULL DEFAULT '' COMMENT '物流商code',
  `shipping_method_code` varchar(50) NOT NULL DEFAULT '' COMMENT '物流方式code',
  `tracking_number` varchar(50) NOT NULL DEFAULT '' COMMENT '跟踪号',
  `delivery_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '包裹号',
  `status` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '0:已取消 1:正常',
  `create_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '修改时间',
  PRIMARY KEY (`id`),
  KEY `idx_did` (`delivery_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='仓库物理方式关系表' AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `dhgate_order`
--

CREATE TABLE IF NOT EXISTS `dhgate_order` (
  `orderNo` varchar(50) NOT NULL COMMENT '卖家后台登录能看到成交的订单号；示例值：1330312162',
  `sellerloginid` varchar(55) NOT NULL DEFAULT '' COMMENT '敦煌卖家登陆账号',
  `orderStatus` varchar(30) NOT NULL DEFAULT '' COMMENT '订单状态',
  `orderTotalPrice` decimal(10,2) DEFAULT '0.00' COMMENT '订单总金额',
  `actualPrice` decimal(10,2) DEFAULT '0.00' COMMENT '实收金额',
  `commissionAmount` decimal(10,2) DEFAULT '0.00' COMMENT '佣金金额',
  `fillingMoney` decimal(10,2) DEFAULT '0.00' COMMENT '订单补款金额',
  `gatewayFee` decimal(10,2) DEFAULT '0.00' COMMENT '网关手续费',
  `itemTotalPrice` decimal(10,2) DEFAULT '0.00' COMMENT '产品总计',
  `reducePrice` decimal(10,2) DEFAULT '0.00' COMMENT '订单降价金额',
  `refundMoney` decimal(10,2) DEFAULT '0.00' COMMENT '订单退款金额',
  `risePrice` decimal(10,2) DEFAULT '0.00' COMMENT '订单涨价金额',
  `sellerCouponPrice` decimal(10,2) DEFAULT '0.00' COMMENT 'seller优惠券',
  `shippingCost` decimal(10,2) DEFAULT '0.00' COMMENT '运费',
  `orderContact` text COMMENT '收货人基本信息,敦煌返回的 OrderContact 数据结构的json',
  `orderDeliveryList` text COMMENT '发货信息,敦煌返回的 OrderDeliveryInfo 数据结构的json',
  `buyerId` varchar(55) DEFAULT '' COMMENT '买家ID',
  `buyerNickName` varchar(255) DEFAULT '' COMMENT '买家别名，买家昵称；示例值：zhangsan',
  `country` varchar(255) DEFAULT '' COMMENT '收货国家，示例值：United States',
  `deliveryDate` timestamp NULL DEFAULT NULL COMMENT '发货时间',
  `shippingType` varchar(55) DEFAULT '' COMMENT '买家选择物流方式，示例值：UPS,D-LINK等',
  `isWarn` tinyint(1) DEFAULT NULL COMMENT '是否需要特别注意的订单（如高风险订单、售后纠纷订单等）。值为false或null表示订单正常，true表示此订单需要特别注意一下，例如，防止高风险订单直接发货',
  `warnReason` varchar(255) DEFAULT '' COMMENT '警告原因,示例值：高风险订单、订单发起售后纠纷、信用卡拒付等',
  `buyerConfirmDate` timestamp NULL DEFAULT NULL COMMENT '买家确认收货时间，日期格式：yyyy-MM-dd HH:mm:ss,精确到秒；示例值：2014-01-12 18:20:21',
  `cancelDate` timestamp NULL DEFAULT NULL COMMENT '交易取消时间',
  `deliveryDeadline` timestamp NULL DEFAULT NULL COMMENT '发货截止时间',
  `inAccountDate` timestamp NULL DEFAULT NULL COMMENT '入账时间',
  `payDate` timestamp NULL DEFAULT NULL COMMENT '付款时间',
  `startedDate` timestamp NULL DEFAULT NULL COMMENT '下单日期',
  `create_time` timestamp NULL DEFAULT NULL COMMENT 'Eagle系统获取并且写入这个Record的时间',
  `orderRemark` varchar(255) DEFAULT '' COMMENT '订单备注',
  PRIMARY KEY (`orderNo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='敦煌订单信息表';

-- --------------------------------------------------------

--
-- Table structure for table `dhgate_order_item`
--

CREATE TABLE IF NOT EXISTS `dhgate_order_item` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `dhgateOrderNo` varchar(50) NOT NULL COMMENT '卖家后台登录能看到成交的订单号；示例值：1330312162',
  `itemcode` varchar(50) NOT NULL DEFAULT '' COMMENT '产品编号,产品最终页的URL中有产品编码；示例值:184942450',
  `categoryName` varchar(255) DEFAULT '' COMMENT '产品类目',
  `grossWeight` decimal(10,2) DEFAULT '0.00' COMMENT '包装重量',
  `height` decimal(10,2) DEFAULT '0.00' COMMENT '产品包装尺寸:高,单位：cm',
  `length` decimal(10,2) DEFAULT '0.00' COMMENT '产品包装尺寸:长,单位：cm',
  `width` decimal(10,2) DEFAULT '0.00' COMMENT '产品包装尺寸:宽,单位：cm',
  `itemAttr` varchar(255) DEFAULT '' COMMENT '产品属性',
  `itemCount` int(11) DEFAULT '0' COMMENT '产品数量,示例值：10,如果单位是件，则为10件，如果单位是包，则是10包',
  `itemImage` varchar(255) DEFAULT '' COMMENT '产品图片URL',
  `itemName` varchar(255) DEFAULT '' COMMENT '产品名称',
  `itemPrice` decimal(10,2) DEFAULT '0.00' COMMENT '产品单价',
  `itemUrl` varchar(255) DEFAULT '' COMMENT '产品地址URL',
  `measureName` varchar(50) DEFAULT '' COMMENT '商品售卖单位,示例值：包、件、套、千克、千米',
  `packingQuantity` int(11) DEFAULT '0' COMMENT '产品打包数量，大于1表示按包买，同时也代表每包的数量,<=1表示非按包买，itemCount代表购买数量，示例值：10',
  `skuCode` varchar(45) DEFAULT '' COMMENT '卖家商品编码',
  `buyerRemark` varchar(255) DEFAULT '' COMMENT '买家备注',
  PRIMARY KEY (`id`),
  KEY `index2` (`dhgateOrderNo`),
  KEY `index3` (`skuCode`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='敦煌订单产品信息表' AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `ebay_bestoffer`
--

CREATE TABLE IF NOT EXISTS `ebay_bestoffer` (
  `bestofferid` bigint(20) unsigned NOT NULL,
  `uid` int(11) DEFAULT NULL,
  `selleruserid` varchar(255) DEFAULT NULL,
  `itemid` bigint(20) DEFAULT NULL COMMENT '对应的ebay的itemid',
  `bestoffer` text COMMENT 'bestoffer',
  `bestofferstatus` varchar(30) DEFAULT NULL COMMENT 'bestoffer状态',
  `itembestoffer` text COMMENT '预留itembestoffer',
  `createtime` int(11) DEFAULT NULL,
  `status` int(4) DEFAULT '0' COMMENT '是否已经处理',
  `desc` varchar(80) DEFAULT NULL COMMENT '议价记录',
  `counterofferprice` float(10,2) DEFAULT NULL COMMENT '议价价格',
  `operate` varchar(50) DEFAULT NULL COMMENT '处理人',
  PRIMARY KEY (`bestofferid`),
  KEY `NewIndex1` (`selleruserid`),
  KEY `bestofferstatus` (`bestofferstatus`),
  KEY `createtime` (`createtime`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `ebay_crossselling`
--

CREATE TABLE IF NOT EXISTS `ebay_crossselling` (
  `crosssellingid` int(11) NOT NULL AUTO_INCREMENT,
  `selleruserid` varchar(255) DEFAULT NULL COMMENT '绑定的ebay账号',
  `title` varchar(255) DEFAULT NULL COMMENT '交叉销售范本名',
  `createtime` int(11) DEFAULT NULL,
  `updatetime` int(11) DEFAULT NULL,
  PRIMARY KEY (`crosssellingid`),
  KEY `selleruserid` (`selleruserid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `ebay_crossselling_item`
--

CREATE TABLE IF NOT EXISTS `ebay_crossselling_item` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `crosssellingid` int(11) NOT NULL COMMENT '对应的crosssellingID',
  `sort` int(5) DEFAULT NULL COMMENT '排序',
  `data` text COMMENT '插入的交叉数据',
  `html` text COMMENT '整理出来的html',
  PRIMARY KEY (`id`),
  KEY `crosssellingid` (`crosssellingid`),
  KEY `sort` (`sort`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `ebay_item`
--

CREATE TABLE IF NOT EXISTS `ebay_item` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uid` int(10) NOT NULL,
  `itemid` bigint(13) NOT NULL COMMENT 'ItemID',
  `selleruserid` varchar(32) NOT NULL COMMENT '卖家账号',
  `itemtitle` varchar(100) NOT NULL COMMENT 'Item标题',
  `mubanid` int(10) NOT NULL COMMENT '对应模板ID',
  `quantity` int(5) DEFAULT NULL COMMENT '数量',
  `quantitysold` int(5) DEFAULT NULL COMMENT '卖出数量',
  `starttime` int(11) DEFAULT NULL COMMENT 'Item开始时间',
  `endtime` int(11) DEFAULT NULL COMMENT 'Item结束时间',
  `dispatchtime` int(5) DEFAULT NULL COMMENT '发货时间',
  `watchcount` int(5) DEFAULT NULL COMMENT '浏览量',
  `viewitemurl` varchar(255) DEFAULT NULL COMMENT '查看链接',
  `currency` varchar(8) DEFAULT NULL COMMENT '币种',
  `listingtype` varchar(20) DEFAULT NULL COMMENT '刊登方式',
  `site` char(16) DEFAULT NULL COMMENT '平台',
  `currentprice` decimal(10,2) DEFAULT NULL COMMENT '当前价格',
  `listingstatus` enum('Active','Completed','Ended','Error') DEFAULT NULL COMMENT 'Item状态',
  `listingduration` enum('Days_1','Days_10','Days_3','Days_30','Days_5','Days_7','GTC') DEFAULT NULL COMMENT '刊登时间',
  `buyitnowprice` decimal(10,2) DEFAULT NULL COMMENT '一口价',
  `startprice` decimal(10,2) DEFAULT NULL COMMENT '拍卖价',
  `desc` varchar(64) DEFAULT NULL COMMENT '备注',
  `sku` varchar(255) DEFAULT NULL COMMENT 'sku',
  `lastsolddatetime` int(11) DEFAULT NULL COMMENT '最后售出时间',
  `paypal` varchar(255) DEFAULT NULL COMMENT 'paypal',
  `outofstockcontrol` tinyint(1) DEFAULT '0' COMMENT '永久在线',
  `isvariation` tinyint(1) DEFAULT '0' COMMENT '是否多属性',
  `mainimg` varchar(255) DEFAULT NULL COMMENT '主图',
  `createtime` int(11) DEFAULT NULL,
  `updatetime` int(11) DEFAULT NULL,
  `bukucun` tinyint(2) DEFAULT '0' COMMENT '补库存开关:0关,1卖多少补多少，2自己设置',
  `less` int(3) DEFAULT NULL COMMENT '自己设置时少于多少件',
  `bu` int(3) DEFAULT NULL COMMENT '补几件',
  `storecategoryid` bigint(15) DEFAULT NULL COMMENT '店铺类目1' , 
  `primarycategory` int(10) DEFAULT NULL COMMENT '主类目',
  
  PRIMARY KEY (`id`),
  KEY `itemid` (`itemid`),
  KEY `uid` (`uid`),
  KEY `selleruserid` (`selleruserid`),
  KEY `itemtitle` (`itemtitle`),
  KEY `mubanid` (`mubanid`),
  KEY `listingtype` (`listingtype`),
  KEY `site` (`site`),
  KEY `sku` (`sku`),
  KEY `isvariation` (`isvariation`),
  KEY `outofstockcontrol` (`outofstockcontrol`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;
-- --------------------------------------------------------

--
-- Table structure for table `ebay_item_detail`
--

CREATE TABLE IF NOT EXISTS `ebay_item_detail` (
  `itemid` bigint(13) NOT NULL,
  `primarycategory` int(10) DEFAULT NULL COMMENT '主类目',
  `secondarycategory` int(10) DEFAULT NULL COMMENT '子类目',
  `lotsize` int(5) DEFAULT NULL COMMENT '份数',
  `conditionid` int(10) DEFAULT NULL COMMENT '新旧',
  `hitcounter` varchar(32) DEFAULT NULL COMMENT '计数器',
  `postalcode` varchar(20) DEFAULT NULL COMMENT '邮编',
  `location` varchar(32) DEFAULT NULL COMMENT '地区',
  `country` varchar(16) DEFAULT NULL COMMENT '国家',
  `gallery` varchar(16) DEFAULT NULL COMMENT '图片展示',
  `storecategoryid` bigint(15) DEFAULT NULL COMMENT '店铺类目1',
  `storecategory2id` bigint(15) DEFAULT NULL COMMENT '店铺类目2',
  `additemfee` decimal(10,2) DEFAULT NULL COMMENT '刊登费用',
  `additemfeecurrency` varchar(8) DEFAULT NULL COMMENT '刊登费用币种',
  `bestoffer` tinyint(1) DEFAULT '0' COMMENT '是否开启议价',
  `bestofferprice` decimal(10,2) DEFAULT NULL COMMENT '自动接收的议价',
  `minibestofferprice` decimal(10,2) DEFAULT NULL COMMENT '最低议价',
  `epid` varchar(128) DEFAULT NULL COMMENT 'productid',
  `isbn` varchar(128) DEFAULT NULL COMMENT 'productid',
  `upc` varchar(128) DEFAULT NULL COMMENT 'productid',
  `ean` varchar(128) DEFAULT NULL COMMENT 'productid',
  `subtitle` varchar(255) DEFAULT NULL COMMENT '子标题',
  `shippingdetails` text COMMENT '物流信息',
  `sellingstatus` text COMMENT '售卖状态',
  `itemdescription` text COMMENT 'item主描述',
  `paymentmethods` text COMMENT '支付信息',
  `returnpolicy` text COMMENT '退货政策',
  `listingenhancement` text,
  `imgurl` text COMMENT '图片',
  `variation` LONGTEXT COMMENT '多属性',
  `autopay` tinyint(1) DEFAULT '0',
  `privatelisting` tinyint(1) DEFAULT '0' COMMENT '私人刊登',
  `buyerrequirementdetails` text COMMENT '买家要求信息',
  `itemspecifics` text COMMENT '细节',
  `vatpercent` decimal(10,2) DEFAULT NULL COMMENT '税',
  `isreviseinventory` int(4) DEFAULT NULL,
  `createtime` int(11) DEFAULT NULL,
  `updatetime` int(11) DEFAULT NULL,
  `matching_info` text DEFAULT NULL COMMENT '配对待确认信息',
  PRIMARY KEY (`itemid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `ebay_item_variation_map`
--

CREATE TABLE IF NOT EXISTS `ebay_item_variation_map` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `itemid` bigint(13) DEFAULT NULL,
  `sku` varchar(155) DEFAULT NULL,
  `startprice` decimal(10,2) DEFAULT NULL,
  `quantity` int(5) DEFAULT NULL,
  `quantitysold` int(5) DEFAULT NULL,
  `onlinequantity` int(5) DEFAULT NULL,
  `createtime` int(10) DEFAULT NULL,
  `updatetime` int(10) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `itemid` (`itemid`),
  KEY `sku` (`sku`),
  KEY `startprice` (`startprice`),
  KEY `onlinequantity` (`onlinequantity`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `ebay_log_item`
--

CREATE TABLE IF NOT EXISTS `ebay_log_item` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `mubanid` int(11) DEFAULT NULL,
  `type` int(5) DEFAULT '0' COMMENT '类型',
  `name` varchar(50) NOT NULL COMMENT '操作人',
  `reason` varchar(255) DEFAULT NULL COMMENT '修改原因',
  `itemid` bigint(15) DEFAULT NULL,
  `content` longtext COMMENT '修改内容',
  `result` char(15) DEFAULT NULL COMMENT '结果',
  `message` text COMMENT '失败原因',
  `createtime` int(11) DEFAULT NULL,
  `transactionid` bigint(20) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `mubanid` (`mubanid`),
  KEY `transactionid` (`transactionid`),
  KEY `reason` (`reason`),
  KEY `itemid` (`itemid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `ebay_log_muban`
--

CREATE TABLE IF NOT EXISTS `ebay_log_muban` (
  `logid` int(11) NOT NULL AUTO_INCREMENT,
  `uid` int(11) DEFAULT NULL,
  `selleruserid` char(32) DEFAULT NULL,
  `timerid` int(11) DEFAULT NULL COMMENT '定时刊登器ID',
  `mubanid` int(11) DEFAULT NULL COMMENT '模板ID',
  `itemid` bigint(20) DEFAULT NULL COMMENT '刊登成功的话ItemID',
  `result` tinyint(4) DEFAULT NULL COMMENT '刊登结果',
  `siteid` int(5) DEFAULT NULL COMMENT '刊登平台',
  `method` varchar(8) DEFAULT NULL COMMENT '刊登方式，手动自动',
  `title` varchar(125) DEFAULT NULL COMMENT '刊登标题',
  `createtime` int(11) DEFAULT NULL,
  PRIMARY KEY (`logid`),
  KEY `mubanid` (`mubanid`),
  KEY `itemid` (`itemid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `ebay_log_muban_detail`
--

CREATE TABLE IF NOT EXISTS `ebay_log_muban_detail` (
  `logid` int(11) NOT NULL,
  `message` text,
  `description` text,
  `fee` text,
  `error` text,
  PRIMARY KEY (`logid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `ebay_muban`
--

CREATE TABLE IF NOT EXISTS `ebay_muban` (
  `mubanid` int(10) NOT NULL AUTO_INCREMENT,
  `uid` int(10) DEFAULT NULL,
  `siteid` int(5) DEFAULT NULL,
  `itemtitle` varchar(100) DEFAULT NULL,
  `listingtype` varchar(20) DEFAULT NULL,
  `location` char(128) DEFAULT NULL,
  `listingduration` char(10) DEFAULT NULL,
  `sku` varchar(255) DEFAULT NULL,
  `paypal` varchar(255) DEFAULT NULL,
  `selleruserid` varchar(200) DEFAULT NULL,
  `outofstockcontrol` tinyint(1) DEFAULT NULL,
  `isvariation` tinyint(1) DEFAULT NULL,
  `quantity` int(5) DEFAULT NULL,
  `startprice` decimal(10,2) DEFAULT NULL,
  `buyitnowprice` decimal(10,2) DEFAULT NULL,
  `shippingdetails` text,
  `mainimg` varchar(255) DEFAULT NULL,
  `desc` varchar(100) DEFAULT NULL,
  `createtime` int(11) DEFAULT NULL,
  `updatetime` int(11) DEFAULT NULL,
  PRIMARY KEY (`mubanid`),
  KEY `title` (`itemtitle`),
  KEY `sku` (`sku`),
  KEY `siteid` (`siteid`),
  KEY `listingduration` (`listingduration`),
  KEY `listingtype` (`listingtype`),
  KEY `quantity` (`quantity`),
  KEY `selleruserid` (`selleruserid`),
  KEY `isvariation` (`isvariation`),
  KEY `paypal` (`paypal`),
  KEY `outofstockcontrol` (`outofstockcontrol`),
  KEY `desc` (`desc`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `ebay_muban_detail`
--

CREATE TABLE IF NOT EXISTS `ebay_muban_detail` (
  `mubanid` int(10) NOT NULL,
  `epid` varchar(128) DEFAULT NULL COMMENT 'epidID',
  `isbn` varchar(128) DEFAULT NULL COMMENT 'isbnID',
  `upc` varchar(128) DEFAULT NULL COMMENT 'upcID',
  `ean` varchar(128) DEFAULT NULL COMMENT 'eanID',
  `primarycategory` int(10) DEFAULT NULL COMMENT '主类目',
  `secondarycategory` int(10) DEFAULT NULL COMMENT '子类目',
  `storecategoryid` bigint(20) DEFAULT NULL COMMENT '店铺主类目',
  `storecategory2id` bigint(20) DEFAULT NULL COMMENT '店铺子类目',
  `lotsize` int(5) DEFAULT NULL COMMENT '每份件数',
  `itemdescription` longtext COMMENT '描述',
  `imgurl` text COMMENT '图片信息',
  `listingenhancement` text COMMENT '格式',
  `hitcounter` varchar(20) DEFAULT NULL COMMENT '计数器',
  `paymentmethods` text COMMENT '支付信息',
  `postalcode` varchar(20) DEFAULT NULL COMMENT '邮编',
  `country` varchar(10) DEFAULT NULL COMMENT '国家',
  `region` int(10) DEFAULT NULL COMMENT '区域',
  `template` varchar(60) DEFAULT NULL COMMENT '模板风格',
  `basicinfo` int(10) DEFAULT NULL COMMENT '信息模板',
  `gallery` varchar(200) DEFAULT NULL,
  `dispatchtime` int(10) DEFAULT NULL COMMENT '包裹处理时间',
  `return_policy` text COMMENT '退款信息',
  `conditionid` int(11) DEFAULT NULL COMMENT '特征是否是二手的',
  `variation` longtext COMMENT '多属性',
  `specific` text COMMENT '细节值，如color，size',
  `bestoffer` tinyint(1) DEFAULT '0' COMMENT '是否议价',
  `bestofferprice` decimal(10,2) DEFAULT NULL COMMENT '自动议价价格',
  `minibestofferprice` decimal(10,2) DEFAULT NULL COMMENT '自动拒绝议价价格',
  `buyerrequirementdetails` text COMMENT '买家要求',
  `autopay` tinyint(1) DEFAULT '0' COMMENT '是否要求立即支付',
  `secondoffer` decimal(10,0) DEFAULT NULL COMMENT '二次交易',
  `privatelisting` tinyint(1) DEFAULT '0' COMMENT '是否私人刊登',
  `itemtitle2` varchar(255) DEFAULT NULL COMMENT '子标题',
  `vatpercent` decimal(10,2) DEFAULT NULL COMMENT '税',
  `crossbordertrade` tinyint(1) DEFAULT '0',
  `crossselling` int(11) DEFAULT NULL COMMENT '交叉销售的范本ID',
  `createtime` int(11) DEFAULT NULL,
  `updatetime` int(11) DEFAULT NULL,
  `itemdescription_listing` longtext,
   `crossselling_two` int(11) DEFAULT NULL,
  PRIMARY KEY (`mubanid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `ebay_sales_information`
--

CREATE TABLE IF NOT EXISTS `ebay_sales_information` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '逐渐',
  `uid` int(10) DEFAULT NULL COMMENT 'uid',
  `name` varchar(255) DEFAULT NULL COMMENT '销售信息范本名',
  `payment` text COMMENT '支付信息',
  `delivery_details` text COMMENT '运输信息',
  `terms_of_sales` text COMMENT '销售相关信息',
  `about_us` text COMMENT '关于我们',
  `contact_us` text COMMENT '联系我们',
  `created` int(11) DEFAULT NULL,
  `updated` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `ebay_storecategory`
--

CREATE TABLE IF NOT EXISTS `ebay_storecategory` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `uid` int(10) DEFAULT NULL,
  `selleruserid` varchar(100) DEFAULT NULL,
  `categoryid` bigint(19) DEFAULT NULL COMMENT '分类id',
  `category_name` varchar(100) DEFAULT NULL COMMENT 'ebay店铺分类名',
  `category_order` int(10) DEFAULT NULL COMMENT 'ebay分类排序',
  `category_parentid` bigint(19) DEFAULT NULL COMMENT '分类父id',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `excelmodel`
--

CREATE TABLE IF NOT EXISTS `excelmodel` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(200) NOT NULL COMMENT '模型名称',
  `content` text NOT NULL COMMENT '字段集合',
  `type` int(11) NOT NULL COMMENT '类型：0系统，1自定义',
  `belong` varchar(100) DEFAULT NULL COMMENT '用户名',
  `tablename` varchar(100) DEFAULT NULL COMMENT '表名',
  `keyname` varchar(100) DEFAULT 'id',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;


INSERT INTO `excelmodel` (`id`, `name`, `content`, `type`, `belong`, `tablename`, `keyname`) VALUES
(0, '默认范本', 'order_source_order_id,order_status,order_source,order_source_site_id,source_buyer_user_id,order_source_create_time,paid_time,grand_total,currency,sku,quantity,photo_primary,consignee,consignee_country_label_cn,user_message,delivery_time,tracknum,logistic_status,desc', 0, 'System', 'od_order_v2', 'id');

-- --------------------------------------------------------

--
-- Table structure for table `fn_transaction`
--

CREATE TABLE IF NOT EXISTS `fn_transaction` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '记录编号',
  `pay_date` timestamp NULL DEFAULT NULL COMMENT '支付日期',
  `type` char(1) NOT NULL DEFAULT '' COMMENT '类型\nR: 收款记录\nP：付款记录',
  `currency` char(3) NOT NULL DEFAULT '' COMMENT '币种\nCNY/USD/EUR',
  `amount` decimal(10,2) DEFAULT NULL COMMENT '金额',
  `related_purchase_id` varchar(30) NOT NULL DEFAULT '' COMMENT '相关的采购单号',
  `related_order_id` varchar(30) NOT NULL DEFAULT '' COMMENT '相关的订单号',
  `pay_to_person` varchar(45) NOT NULL DEFAULT '' COMMENT '收款人',
  `pay_to_account` varchar(45) NOT NULL DEFAULT '',
  `pay_from_person` varchar(45) NOT NULL DEFAULT '',
  `pay_from_account` varchar(45) NOT NULL DEFAULT '',
  `payment_mode` varchar(50) NOT NULL DEFAULT '' COMMENT '支付方式\n工商银行，支付宝，paypal',
  `transaction_number` varchar(50) NOT NULL DEFAULT '' COMMENT '支付凭证号\n911126547898',
  `comment` varchar(255) NOT NULL DEFAULT '' COMMENT '备注',
  `addi_info` varchar(255) NOT NULL DEFAULT '' COMMENT '额外的附加信息，使用json格式记录',
  `capture_user_id` tinyint(3) unsigned DEFAULT NULL COMMENT 'Capture User ID',
  PRIMARY KEY (`id`),
  KEY `index2` (`related_purchase_id`),
  KEY `index3` (`related_order_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='支付/收款记录的基本信息' AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `good_reputation_record`
--

CREATE TABLE IF NOT EXISTS `good_reputation_record` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `orderid` varchar(50) DEFAULT NULL,
  `evaluate_time` int(11) DEFAULT NULL,
  `result` varchar(20) DEFAULT NULL,
  `fail_reason` varchar(255) DEFAULT NULL COMMENT '失败原因',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `login_info`
--

CREATE TABLE IF NOT EXISTS `login_info` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ip` varchar(100) NOT NULL,
  `logintime` int(11) NOT NULL,
  `memo` varchar(200) NOT NULL,
  `userid` varchar(100) NOT NULL,
  `username` varchar(100) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `lt_ordertag`
--

CREATE TABLE IF NOT EXISTS `lt_ordertag` (
  `tag_id` int(11) NOT NULL AUTO_INCREMENT,
  `order_indicator_code` tinyint(4) NOT NULL DEFAULT '0' COMMENT '订单表里面的自定义标签 编号，例如 6 = order_v2.customized_tag_6',
  `tag_name` varchar(100) NOT NULL DEFAULT '',
  `color` varchar(20) DEFAULT NULL COMMENT '标签颜色',
  PRIMARY KEY (`tag_id`),
  KEY `index2` (`tag_name`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COMMENT='标签定义' AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `lt_order_tags`
--

CREATE TABLE IF NOT EXISTS `lt_order_tags` (
  `tagid` int(11) NOT NULL AUTO_INCREMENT COMMENT '主键',
  `order_id` int(11) NOT NULL DEFAULT 0 COMMENT '订单信息主键',
  `tag_id` int(11) unsigned DEFAULT NULL COMMENT '标签编号',
  PRIMARY KEY (`tagid`),
  UNIQUE KEY `index2` (`order_id`,`tag_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='订单和标签的对应信息' AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `lt_tag`
--

CREATE TABLE IF NOT EXISTS `lt_tag` (
  `tag_id` int(11) NOT NULL AUTO_INCREMENT,
  `tag_name` varchar(100) NOT NULL DEFAULT '',
  `color` varchar(20) DEFAULT NULL COMMENT '标签颜色',
  PRIMARY KEY (`tag_id`),
  KEY `index2` (`tag_name`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COMMENT='标签定义' AUTO_INCREMENT=4 ;

--
-- Dumping data for table `lt_tag`
--

INSERT INTO `lt_tag` (`tag_id`, `tag_name`, `color`) VALUES
(1, '待重发包裹', 'red'),
(2, '需要退款', 'purple'),
(3, '包裹已退回', 'blue');

-- --------------------------------------------------------

--
-- Table structure for table `lt_tracking`
--

CREATE TABLE IF NOT EXISTS `lt_tracking` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `seller_id` varchar(100) DEFAULT NULL COMMENT '平台seller账号名',
  `order_id` varchar(100) DEFAULT NULL COMMENT '平台 订单号',
  `track_no` varchar(100) DEFAULT NULL COMMENT '物流号',
  `status` varchar(30) DEFAULT NULL COMMENT '物流状态，例如：运输途中，查询不到，已签收，到达待取，递送失败',
  `state` varchar(30) DEFAULT NULL COMMENT '状态分类，表示该状态属于正常还是退回，超时，无法交运。\n这个状态需要根据不同递送方式的容忍度来判断。部分规则客户可以自定义。例如 中国邮政 5天内查不到才算无法交运，5天不到达是正常。而DHL 5天不到就算递送超时了。\n可能值：正常，失败，超时，无法交运',
  `source` char(1) DEFAULT NULL COMMENT '记录的录入来源，手工录入是M，excel是E，OMS是O',
  `platform` varchar(30) DEFAULT NULL COMMENT '来源的平台，例如Amazon，SMT，eBay',
  `parcel_type` tinyint(4) DEFAULT '0' COMMENT '包裹类型, (0)->未知, (1)->小包, (2)->大包, (3)->EMS',
  `carrier_type` int(11) DEFAULT '0' COMMENT '物流类型代号， \n0=全球邮政，100002=UPS，100001=DHL，100003=Fedex，100004=TNT，100007=DPD，100010=DPD(UK)，100011=One World，100005=GLS，100012=顺丰速运，100008=EShipper，100009=Toll，100006=Aramex，190002=飞特物流，190008=云途物流，190011=百千诚物流，190007=俄速递，190009=快达物流，190003=华翰物流，190012=燕文物流，1 /* comment truncated */ /*90013=淼信国际，190014=俄易达，190015=俄速通，190017=俄通收，190016=俄顺达*/',
  `is_active` char(1) DEFAULT 'Y' COMMENT '是否活动	如果太旧的物流号，客户可以把它关闭跟踪，我们系统也可以定义n日无变化的跟踪放弃定期检查',
  `batch_no` varchar(100) DEFAULT NULL COMMENT '提交批次	提交方式+按照时间命名, 后续查看跟踪用户可以用这个来维护某天的查询对象以及某次excel上传来的整批物流	\n可能值：M2015-02-25，Excel2015-02-05 08:30',
  `create_time` date DEFAULT NULL COMMENT '提交日期',
  `update_time` datetime DEFAULT NULL COMMENT '更新时间',
  `from_nation` char(2) DEFAULT NULL COMMENT '来源国家',
  `to_nation` char(2) DEFAULT NULL COMMENT '目的国家',
  `mark_handled` char(1) DEFAULT 'N',
  `notified_seller` char(1) DEFAULT 'N' COMMENT '是否已发送邮件通知商家',
  `notified_buyer` char(1) DEFAULT 'N' COMMENT '是否已发送邮件通知买家，消费者',
  `shipping_notified` char(1) NOT NULL DEFAULT 'N' COMMENT '是否该状态下已发信通知',
  `pending_fetch_notified` char(1) NOT NULL DEFAULT 'N' COMMENT '是否该状态下已发信通知',
  `delivery_failed_notified` char(1) NOT NULL DEFAULT 'N' COMMENT '是否该状态下已发信通知',
  `rejected_notified` char(1) NOT NULL DEFAULT 'N' COMMENT '是否该状态下已发信通知',
  `received_notified` char(1) NOT NULL DEFAULT 'N' COMMENT '是否在该状态通知过客户了',
  `ship_by` varchar(100) DEFAULT NULL COMMENT '递送公司，如 DHL，UPS，燕文，4PX',
  `delivery_fee` decimal(10,4) DEFAULT NULL COMMENT '递送费用CNY，如 3600.0000, 16.4569, 如果是粘贴快递公司的对账单，会有这个信息',
  `ship_out_date` date DEFAULT NULL COMMENT '快递单日期，如果是粘贴快递公司的对账单，会有这个信息',
  `total_days` tinyint(4) DEFAULT NULL,
  `all_event` text COMMENT '所有事件',
  `first_event_date` date DEFAULT NULL COMMENT '第一个Event的时间，用来判断，当2个物流商同一个物流号都有结果时候，用谁的结果来作准。',
  `last_event_date` date DEFAULT NULL COMMENT '最后一个物流时间时间，譬如签收时间',
  `stay_days` tinyint(4) NOT NULL DEFAULT '0' COMMENT '最后时间到现在逗留时间，如果已完成的订单，逗留时间为0即可',
  `msg_sent_error` char(1) NOT NULL DEFAULT 'N' COMMENT '是否有发信错误，如果有=Y',
  `from_lang` varchar(50) DEFAULT NULL COMMENT '发货国家的语言',
  `to_lang` varchar(50) DEFAULT NULL COMMENT '目标国家的语言',
  `first_track_result_date` date DEFAULT NULL COMMENT '第一次跟踪到结果的日期',
  `remark` text COMMENT '用户写入的备注，json格式存储',
  `addi_info` text COMMENT '其他信息',
  `ignored_time` datetime DEFAULT NULL COMMENT '忽略查询改物流号的时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `index2` (`track_no`,`order_id`),
  KEY `index3` (`order_id`),
  KEY `index4` (`batch_no`),
  KEY `index5` (`ship_by`),
  KEY `seller_orderid` (`seller_id`,`order_id`),
  KEY `status_state` (`status`,`state`),
  KEY `status_state11` (`state`,`status`),
  KEY `status_statesss` (`state`,`ship_out_date`,`create_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;
-- --------------------------------------------------------

--
-- Table structure for table `lt_tracking_tags`
--

CREATE TABLE IF NOT EXISTS `lt_tracking_tags` (
  `tracking_tag_id` int(11) NOT NULL AUTO_INCREMENT COMMENT '主键',
  `tracking_id` varchar(30) NOT NULL DEFAULT '' COMMENT '物流信息主键',
  `tag_id` int(11) unsigned DEFAULT NULL COMMENT '标签编号',
  PRIMARY KEY (`tracking_tag_id`),
  UNIQUE KEY `index2` (`tracking_id`,`tracking_tag_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='产品和标签的对应信息' AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `matching_rule`
--

CREATE TABLE IF NOT EXISTS `matching_rule` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键',
  `uid` int(10) unsigned NOT NULL COMMENT '小老板主账号ID',
  `operator` int(11) unsigned NOT NULL COMMENT '创建或者修改人ID',
  `rule_name` varchar(100) NOT NULL COMMENT '运输服务匹配规则名',
  `rules` text COMMENT '规则选择项',
  `source` text COMMENT '订单来源',
  `site` text COMMENT '站点',
  `selleruserid` text COMMENT '卖家账号',
  `buyer_transportation_service` text COMMENT '买家选择运输服务',
  `warehouse` text COMMENT '仓库',
  `receiving_country` text COMMENT '收件国家',
  `total_amount` text COMMENT '总金额',
  `freight_amount` text COMMENT '买家支付运费',
  `total_weight` text COMMENT '总重量',
  `product_tag` text COMMENT '商品标签',
  `transportation_service_id` int(10) unsigned DEFAULT NULL COMMENT '运输服务ID',
  `priority` int(3) unsigned DEFAULT NULL COMMENT '优先级',
  `is_active` tinyint(1) NOT NULL COMMENT '是否启用',
  `created` int(11) NOT NULL,
  `updated` int(11) NOT NULL DEFAULT '1',
  `volume_weight` text COMMENT '体积重',
  `volume` text COMMENT '体积',
  `skus` text COMMENT '指定sku',
  `total_cost` text COMMENT '总成本',
  `items_location_country` text COMMENT '物品所在国家',
  `items_location_provinces` text COMMENT '物品所在州/省份',
  `items_location_city` text COMMENT '物品所在城市',
  `receiving_provinces` text COMMENT '收件州/省份',
  `receiving_city` text COMMENT '收件城市',
  `proprietary_warehouse_id` int DEFAULT 0 COMMENT '仓库ID,用于新的运输服务匹配时自动匹配仓库ID',
  `total_amount_new` text default '' COMMENT '订单总金额,按币种区分',
  `postal_code` text COMMENT '邮编',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `mytemplate`
--
CREATE TABLE IF NOT EXISTS `mytemplate` (
  `id` int(8) NOT NULL AUTO_INCREMENT,
  `title` varchar(60) NOT NULL,
  `pic` varchar(255) DEFAULT NULL,
  `content` longtext NOT NULL,
  `type` tinyint(2) NOT NULL DEFAULT '0' COMMENT '风格模板类型 0简单类型 1可视化类型',
  `account` varchar(60),
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `od_customer`
--

CREATE TABLE IF NOT EXISTS `od_customer` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `user_source` varchar(50) NOT NULL DEFAULT '' COMMENT '用户来源',
  `seller_platform_uid` varchar(50) NOT NULL DEFAULT '' COMMENT '卖家平台账号',
  `customer_platform_uid` varchar(50) NOT NULL DEFAULT '' COMMENT '买家平台账号',
  `customer_email` varchar(100) NOT NULL DEFAULT '' COMMENT '卖家email',
  `accumulated_order_amount` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '累计订单数量',
  `accumulated_trading_amount` decimal(10,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '累计交易总额',
  `create_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `idx_custom` (`customer_platform_uid`),
  KEY `idx_seller` (`seller_platform_uid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='客户信息统计表' AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `od_delivery`
--

CREATE TABLE IF NOT EXISTS `od_delivery` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `deliveryid` bigint(13) NOT NULL,
  `creater` varchar(128) DEFAULT NULL COMMENT '创建人',
  `ordercount` int(5) DEFAULT NULL COMMENT '订单数量',
  `skucount` int(5) DEFAULT NULL COMMENT '包含的sku数量',
  `jianhuo_status` tinyint(1) DEFAULT '0' COMMENT '是否完成拣货',
  `peihuo_status` tinyint(1) DEFAULT '0' COMMENT '是否完成配货',
  `warehouseid` int(11) DEFAULT '0' COMMENT '拣货单对应的仓库',
  `goodscount` int(5) DEFAULT NULL COMMENT '商品数量',
  `create_picking_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '创建拣货单时间',
  `print_picking_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '打印拣货单时间',
  `picking_status` tinyint(2) unsigned NOT NULL DEFAULT '0' COMMENT '拣货单状态',
  `print_picking_operator` varchar(128) DEFAULT NULL COMMENT '打印人',
  `picking_operator` varchar(128) DEFAULT NULL COMMENT '拣货人',
  `account` float NOT NULL DEFAULT '0' COMMENT '金额',
  PRIMARY KEY (`id`),
  KEY `delivery_id` (`deliveryid`),
  KEY `jianhuo_status` (`jianhuo_status`),
  KEY `peihuo_status` (`peihuo_status`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8  AUTO_INCREMENT=1 ;


-- --------------------------------------------------------

--
-- Table structure for table `od_delivery_order`
--

CREATE TABLE IF NOT EXISTS `od_delivery_order` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `delivery_id` bigint(13) NOT NULL COMMENT '映射的拣货单号',
  `order_id` int(11) NOT NULL COMMENT '映射的订单号',
  `sku` varchar(128) DEFAULT NULL COMMENT '映射订单的sku',
  `count` int(5) DEFAULT NULL COMMENT '映射订单的sku数量',
  `good_property` varchar(128) DEFAULT NULL COMMENT '商品属性',
  `good_name` varchar(128) DEFAULT NULL COMMENT '商品名称',
  `warehouse_address_id` int(20) NOT NULL DEFAULT '0' COMMENT '库位号',
  `image_adress` varchar(128) DEFAULT NULL COMMENT '图片地址',
  `location_grid` varchar(45) NOT NULL DEFAULT '' COMMENT '仓库货位/格子',
  PRIMARY KEY (`id`),
  KEY `delivery_id` (`delivery_id`),
  KEY `orderid` (`order_id`),
  KEY `sku` (`sku`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `od_ebay_externaltransaction`
--

CREATE TABLE IF NOT EXISTS `od_ebay_externaltransaction` (
  `eid` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `ebay_orderid` varchar(50) DEFAULT NULL COMMENT 'Ebay OrderId',
  `selleruserid` varchar(50) DEFAULT NULL,
  `externaltransactionid` varchar(20) DEFAULT NULL COMMENT 'paypal id',
  `paymentorrefundamount` decimal(10,3) DEFAULT NULL COMMENT '交易金额',
  `feeorcreditamount` decimal(10,3) DEFAULT NULL COMMENT 'paypal交易费',
  `externaltransactiontime` int(10) DEFAULT NULL COMMENT '产生时间 ',
  `created` int(10) DEFAULT NULL,
  PRIMARY KEY (`eid`),
  KEY `ebay_orderid` (`ebay_orderid`),
  KEY `etid` (`externaltransactionid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `od_ebay_order`
--

CREATE TABLE IF NOT EXISTS `od_ebay_order` (
  `eorderid` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `ebay_orderid` varchar(55) DEFAULT NULL,
  `ebay_uid` int(10) DEFAULT NULL COMMENT 'ebay账号表的id',
  `orderstatus` varchar(50) DEFAULT NULL,
  `ebaypaymentstatus` varchar(50) DEFAULT NULL,
  `paymentmethod` varchar(50) DEFAULT NULL,
  `checkoutstatus` varchar(50) DEFAULT NULL,
  `integratedmerchantcreditcardenabled` varchar(50) DEFAULT NULL,
  `adjustmentamount` decimal(10,3) DEFAULT NULL,
  `amountpaid` decimal(10,3) DEFAULT NULL,
  `amountsaved` decimal(10,3) DEFAULT NULL,
  `salestaxpercent` decimal(10,3) DEFAULT NULL,
  `salestaxamount` decimal(10,3) DEFAULT NULL,
  `shippingservicecost` decimal(10,3) DEFAULT NULL,
  `subtotal` decimal(10,3) DEFAULT NULL,
  `total` decimal(10,3) DEFAULT NULL,
  `feeorcreditamount` decimal(10,3) DEFAULT NULL,
  `paymentorrefundamount` decimal(10,3) DEFAULT NULL,
  `insurancecost` decimal(10,3) DEFAULT NULL,
  `currency` char(3) DEFAULT 'usd',
  `lastmodifiedtime` int(11) DEFAULT NULL,
  `createdtime` int(11) DEFAULT NULL,
  `paidtime` int(11) DEFAULT NULL,
  `shippedtime` int(11) DEFAULT NULL,
  `buyeruserid` varchar(64) DEFAULT NULL,
  `shippingservice` varchar(50) DEFAULT NULL,
  `shippingincludedintax` int(4) DEFAULT NULL,
  `ship_name` varchar(100) DEFAULT NULL COMMENT '收件人信息',
  `ship_company` varchar(100) DEFAULT NULL COMMENT '收件人公司',
  `ship_cityname` varchar(100) DEFAULT NULL,
  `ship_stateorprovince` varchar(100) DEFAULT NULL,
  `ship_country` varchar(100) DEFAULT NULL,
  `ship_countryname` varchar(100) DEFAULT NULL,
  `ship_street1` varchar(255) DEFAULT NULL,
  `ship_street2` varchar(255) DEFAULT NULL,
  `ship_postalcode` varchar(50) DEFAULT NULL,
  `ship_phone` varchar(255) DEFAULT NULL,
  `ship_email` varchar(150) DEFAULT NULL COMMENT '收件人 email',
  `addressid` varchar(50) DEFAULT NULL,
  `addressowner` varchar(50) DEFAULT NULL,
  `externaladdressid` varchar(50) DEFAULT NULL,
  `externaltransactionid` varchar(50) DEFAULT NULL,
  `externaltransactiontime` int(11) DEFAULT NULL,
  `shippingaddress` text,
  `externaltransaction` text,
  `buyercheckoutmessage` text,
  `shippingserviceselected` text,
  `selleruserid` varchar(55) DEFAULT NULL,
  `ecid` int(11) DEFAULT NULL,
  `responsedat` int(10) DEFAULT NULL,
  `berequest` int(4) DEFAULT NULL,
  `status_berequest` int(4) DEFAULT NULL,
  `salesrecordnum` int(11) DEFAULT NULL COMMENT 'SellingMangerRecordNumber',
  PRIMARY KEY (`eorderid`),
  KEY `ebay_orderid` (`ebay_orderid`),
  KEY `selleruserid` (`selleruserid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `od_ebay_transaction`
--

CREATE TABLE IF NOT EXISTS `od_ebay_transaction` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `uid` int(10) unsigned DEFAULT NULL,
  `selleruserid` varchar(100) DEFAULT NULL COMMENT 'Ebay 账号 ',
  `transactionid` bigint(20) DEFAULT NULL COMMENT 'id',
  `order_id` int(10) unsigned DEFAULT NULL,
  `goods_id` int(10) unsigned DEFAULT '0',
  `itemid` bigint(20) DEFAULT NULL COMMENT 'Item',
  `eorderid` int(11) DEFAULT NULL,
  `orderid` varchar(64) DEFAULT NULL COMMENT 'Ebay Order,order  transaction',
  `sku` varchar(250) DEFAULT NULL,
  `storage_id` int(10) DEFAULT '0' COMMENT '仓库编号',
  `createddate` int(11) DEFAULT NULL COMMENT '创建时间',
  `quantitypurchased` int(4) DEFAULT NULL COMMENT ' 数量 ',
  `platform` varchar(20) DEFAULT NULL COMMENT ' 站点平点 ',
  `listingtype` enum('Chinese','FixedPriceItem','Other') DEFAULT 'Other',
  `buyer` text COMMENT 'buyer ',
  `title` varchar(100) DEFAULT NULL,
  `status` text COMMENT 'status  ',
  `amountpaid` decimal(10,2) DEFAULT NULL COMMENT '支付总额 ',
  `adjustmentamount` decimal(10,2) DEFAULT NULL COMMENT '折扣 ',
  `transactionprice` decimal(10,3) DEFAULT NULL,
  `shippingservicecost` decimal(10,2) DEFAULT NULL COMMENT '运费',
  `shippingservice` varchar(255) DEFAULT NULL COMMENT ' 物流 ',
  `finalvaluefee` decimal(10,2) DEFAULT NULL COMMENT ' 交易费',
  `finalvaluefee_currency` char(16) DEFAULT NULL COMMENT '交易费货币',
  `transactionsiteid` char(32) DEFAULT NULL COMMENT '站点',
  `paypalemailaddress` char(255) DEFAULT NULL COMMENT 'The seller s Paypal email address. ',
  `shippingserviceselected` text COMMENT ' 使用的物流',
  `currency` char(32) DEFAULT NULL,
  `desc` char(255) DEFAULT NULL COMMENT ' ',
  `paidtime` int(11) unsigned DEFAULT NULL COMMENT '支付时间',
  `seller_commenttype` char(32) DEFAULT NULL COMMENT '买家好评',
  `seller_commenttext` char(255) DEFAULT NULL COMMENT '买家好评内容',
  `shipped` tinyint(1) unsigned DEFAULT '0' COMMENT '是否发货',
  `rn` char(128) DEFAULT NULL COMMENT '物流单号',
  `property_id` text COMMENT '属性id',
  `status_payment` varchar(20) NOT NULL DEFAULT 'wait' COMMENT '付款状态',
  `status_feedback` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '0未评价1已评价',
  `backmoney` decimal(10,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '退款',
  `second_chance_offer_sent` tinyint(1) DEFAULT '0' COMMENT '二次购买',
  `buyer_feedback` char(100) DEFAULT NULL COMMENT '买家好评',
  `buyer_dispute` char(100) DEFAULT NULL COMMENT '买家纠纷',
  `additemfee` decimal(10,2) unsigned DEFAULT '0.00' COMMENT '刊登费',
  `additemfee_currency` char(16) DEFAULT 'USD',
  `variation` text COMMENT '多属性',
  `sendinvoice` tinyint(2) DEFAULT '0' COMMENT '发送账单',
  `salesrecordnum` int(11) DEFAULT NULL COMMENT 'SellingMangerRecordNumber',
  `buyercheckoutmessage` text,
  `orderlineitemid` varchar(55) DEFAULT NULL,
  `lasttimemodified` int(10) NOT NULL DEFAULT '0' COMMENT '修改时间',
  `goodscategory_id` int(10) DEFAULT NULL COMMENT '商品分类ID',
  `lotsize` int(4) DEFAULT '0' COMMENT 'lotsize',
  `is_peihuo` tinyint(2) DEFAULT '0' COMMENT '0未配货，1已配货',
  `peihuo_user` varchar(32) DEFAULT NULL COMMENT '配货人',
  `peihuo_time` int(11) DEFAULT '0' COMMENT '配货时间',
  `created` int(11) unsigned DEFAULT NULL,
  `updated` int(11) unsigned DEFAULT NULL,
  `shipmenttrackingdetail` text COMMENT '交易的track信息',
  PRIMARY KEY (`id`),
  KEY `NewIndex1` (`uid`),
  KEY `NewIndex4` (`order_id`),
  KEY `NewIndex5` (`itemid`,`transactionid`),
  KEY `selleruserid` (`selleruserid`),
  KEY `goods_id` (`goods_id`),
  KEY `listingtype` (`listingtype`),
  KEY `eorderid` (`eorderid`),
  KEY `storage_id` (`storage_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=55 ;

-- --------------------------------------------------------

--
-- Table structure for table `od_order`
--

CREATE TABLE IF NOT EXISTS `od_order` (
  `order_id` int(11) unsigned zerofill NOT NULL AUTO_INCREMENT COMMENT '订单id',
  `order_status` smallint(5) NOT NULL DEFAULT '0' COMMENT '订单流程状态:100:未付款,200:已付款,201:有留言,205:未分仓库,210:SKU不存在,215:报关信息不全,220:paypal/ebay金额不对,225:paypal/ebay地址不对,230:未匹配物流,300:待生成包裹,400:发货处理中,500:已发货,600:已取消',
  `order_manual_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '挂起状态 0：取消挂起状态',
  `pay_status` tinyint(2) unsigned NOT NULL DEFAULT '0' COMMENT '支付状态',
  `shipping_status` tinyint(2) unsigned NOT NULL DEFAULT '0' COMMENT '发货状态',
  `order_source` varchar(50) NOT NULL DEFAULT '' COMMENT '订单来源  ebay,amazon,aliexpress,custom',
  `order_type` varchar(50) NOT NULL DEFAULT '' COMMENT '订单类型如amazon FBA订单',
  `order_source_order_id` varchar(50) NOT NULL DEFAULT '' COMMENT '订单来源  的订单id',
  `order_source_site_id` varchar(50) NOT NULL DEFAULT '' COMMENT '订单来源平台下的站点：如eaby下的US站点',
  `selleruserid` varchar(50) NOT NULL DEFAULT '' COMMENT '订单来源平台卖家用户名(下单时候的用户名)',
  `saas_platform_user_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT 'saas库平台用户卖家账号id(ebay或者amazon卖家表中)',
  `order_source_srn` int(11) unsigned NOT NULL DEFAULT '0' COMMENT 'od_ebay_order表salesrecordnum',
  `customer_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT 'od_customer 表id',
  `source_buyer_user_id` varchar(50) NOT NULL DEFAULT '' COMMENT '来源买家用户名',
  `order_source_shipping_method` varchar(50) NOT NULL DEFAULT '' COMMENT '平台下单时用户选择的物流方式',
  `order_source_create_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '订单在来源平台的下单时间',
  `subtotal` decimal(10,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '产品总价格',
  `shipping_cost` decimal(10,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '运费',
  `discount_amount` decimal(10,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '折扣',
  `grand_total` decimal(10,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '合计金额(产品总价格 + 运费 - 折扣 = 合计金额)',
  `returned_total` decimal(10,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '退款总金额',
  `price_adjustment` decimal(10,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '价格手动调整（下单后人工调整）',
  `currency` char(3) NOT NULL DEFAULT '' COMMENT '货币',
  `consignee` varchar(255) NOT NULL DEFAULT '' COMMENT '收货人',
  `consignee_postal_code` varchar(255) NOT NULL DEFAULT '' COMMENT '收货人邮编',
  `consignee_phone` varchar(255) NOT NULL DEFAULT '' COMMENT '收货人电话',
  `consignee_email` varchar(255) NOT NULL DEFAULT '' COMMENT '收货人Email',
  `consignee_company` varchar(255) NOT NULL DEFAULT '' COMMENT '收货人公司',
  `consignee_country` varchar(255) NOT NULL DEFAULT '' COMMENT '收货人国家名',
  `consignee_country_code` char(2) NOT NULL DEFAULT '' COMMENT '收货人国家代码',
  `consignee_city` varchar(255) NOT NULL DEFAULT '' COMMENT '收货人城市',
  `consignee_province` varchar(255) NOT NULL DEFAULT '' COMMENT '收货人省',
  `consignee_district` varchar(255) NOT NULL DEFAULT '' COMMENT '收货人区',
  `consignee_county` varchar(255) NOT NULL DEFAULT '' COMMENT '收货人镇',
  `consignee_address_line1` varchar(255) NOT NULL DEFAULT '' COMMENT '收货人地址1',
  `consignee_address_line2` varchar(255) NOT NULL DEFAULT '' COMMENT '收货人地址2',
  `consignee_address_line3` varchar(255) NOT NULL DEFAULT '' COMMENT '收货人地址3',
  `default_warehouse_id` int(11) NOT NULL DEFAULT '0' COMMENT '默认的仓库id',
  `default_carrier_code` varchar(255) NOT NULL DEFAULT '' COMMENT '默认物流商代码',
  `default_shipping_method_code` varchar(255) NOT NULL DEFAULT '' COMMENT '默认物流方式code',
  `paid_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '订单付款时间',
  `delivery_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '订单发货时间',
  `create_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int(11) NOT NULL DEFAULT '0' COMMENT '更新时间 ',
  `user_message` text NOT NULL COMMENT '用户留言',
  `is_manual_order` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '是否手工订单  1：是  0：否',
  `carrier_type` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '0:普通物流商  1:海外仓',
  `is_feedback` int(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`order_id`),
  KEY `idx_statmanu` (`order_status`,`order_manual_id`) USING BTREE,
  KEY `idx_timeseller` (`order_source_create_time`,`selleruserid`) USING BTREE,
  KEY `idx_timebuyer` (`order_source_create_time`,`source_buyer_user_id`) USING BTREE,
  KEY `idx_timegrand` (`order_source_create_time`,`grand_total`),
  KEY `idx_buyeremail` (`consignee_email`),
  KEY `idx_manu` (`order_manual_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='订单表' AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `od_order_has_dely_delivery`
--

CREATE TABLE IF NOT EXISTS `od_order_has_dely_delivery` (
  `order_id` int(11) unsigned zerofill NOT NULL DEFAULT '00000000000' COMMENT '订单id',
  `delivery_id` int(11) unsigned zerofill NOT NULL DEFAULT '00000000000' COMMENT '包裹id',
  PRIMARY KEY (`order_id`,`delivery_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='包裹和订单关联表';

-- --------------------------------------------------------

--
-- Table structure for table `od_order_item`
--

CREATE TABLE IF NOT EXISTS `od_order_item` (
  `order_item_id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '订单商品id',
  `order_id` int(11) unsigned zerofill NOT NULL DEFAULT '00000000000' COMMENT '订单号',
  `order_source_srn` int(11) unsigned NOT NULL DEFAULT '0' COMMENT 'od_ebay_transaction表salesrecordnum',
  `order_source_order_item_id` varchar(50) NOT NULL DEFAULT '' COMMENT 'od_ebay_transaction表id或amazon的OrderItemId',
  `source_item_id` varchar(50) NOT NULL DEFAULT '' COMMENT '平台刊登itemid',
  `sku` varchar(250) DEFAULT '' COMMENT '商品编码',
  `product_name` varchar(255) NOT NULL DEFAULT '' COMMENT '下单时标题',
  `photo_primary` varchar(255) NOT NULL DEFAULT '' COMMENT '商品主图冗余',
  `shipping_price` decimal(10,2) unsigned DEFAULT '0.00' COMMENT '运费',
  `shipping_discount` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '运费折扣',
  `price` decimal(10,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '下单时价格',
  `promotion_discount` decimal(10,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '促销折扣',
  `ordered_quantity` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '下单时候的数量',
  `quantity` int(11) unsigned NOT NULL DEFAULT '1' COMMENT '需发货的商品数量',
  `sent_quantity` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '已发货数量',
  `packed_quantity` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '已打包数量',
  `returned_quantity` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '退货数量',
  `invoice_requirement` varchar(50) NOT NULL DEFAULT '' COMMENT '发票要求',
  `buyer_selected_invoice_category` varchar(50) NOT NULL DEFAULT '' COMMENT '发票种类',
  `invoice_title` varchar(50) NOT NULL DEFAULT '' COMMENT '发票抬头',
  `invoice_information` varchar(50) NOT NULL DEFAULT '' COMMENT '发票内容',
  `remark` varchar(255) DEFAULT NULL COMMENT '订单备注',
  `create_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  `platform_sku` varchar(50) DEFAULT '' COMMENT '平台sku',
  `is_bundle` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '是否是捆绑商品，1：是0：否',
  `bdsku` varchar(50) NOT NULL DEFAULT '' COMMENT '捆绑sku',
  PRIMARY KEY (`order_item_id`),
  KEY `idx_oid` (`order_id`),
  KEY `idx_itemid` (`order_source_order_item_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='订单商品表' AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `od_order_item_v2`
--

CREATE TABLE IF NOT EXISTS `od_order_item_v2` (
  `order_item_id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '订单商品id',
  `order_id` int(11) unsigned zerofill NOT NULL DEFAULT '00000000000' COMMENT '订单号',
  `order_source_srn` int(11) unsigned NOT NULL DEFAULT '0' COMMENT 'od_ebay_transaction表salesrecordnum',
  `order_source_order_item_id` bigint(20) unsigned NOT NULL DEFAULT '0' COMMENT 'od_ebay_transaction表id或amazon的OrderItemId',
  `sku` varchar(255) DEFAULT '' COMMENT '商品编码',
  `product_name` varchar(255) DEFAULT NULL COMMENT '标题',
  `photo_primary` text DEFAULT NULL COMMENT '主图',
  `shipping_price` decimal(10,2) unsigned DEFAULT '0.00' COMMENT '运费',
  `shipping_discount` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '运费折扣',
  `price` decimal(10,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '下单时价格',
  `promotion_discount` decimal(10,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '促销折扣',
  `ordered_quantity` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '下单时候的数量',
  `quantity` int(11) unsigned NOT NULL DEFAULT '1' COMMENT '需发货的商品数量',
  `sent_quantity` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '已发货数量',
  `packed_quantity` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '已打包数量',
  `returned_quantity` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '退货数量',
  `invoice_requirement` varchar(50) NOT NULL DEFAULT '' COMMENT '发票要求',
  `buyer_selected_invoice_category` varchar(50) NOT NULL DEFAULT '' COMMENT '发票种类',
  `invoice_title` varchar(50) NOT NULL DEFAULT '' COMMENT '发票抬头',
  `invoice_information` varchar(50) NOT NULL DEFAULT '' COMMENT '发票内容',
  `create_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  `desc` text COMMENT '订单商品备注',
  `platform_sku` varchar(50) DEFAULT '' COMMENT '平台sku',
  `is_bundle` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '是否是捆绑商品，1：是；0：否',
  `bdsku` varchar(50) NOT NULL DEFAULT '' COMMENT '捆绑sku',
  `source_item_id` varchar(50) DEFAULT NULL,
  `order_source_order_id` varchar(50) DEFAULT NULL COMMENT '订单来源平台订单号',
  `order_source_transactionid` varchar(50) DEFAULT NULL COMMENT '订单来源交易号或子订单号',
  `order_source_itemid` varchar(50) DEFAULT NULL COMMENT '产品ID listing的唯一标示',
  `product_attributes` varchar(100) DEFAULT NULL COMMENT '商品属性',
  `product_unit` varchar(50) DEFAULT NULL COMMENT '单位',
  `lot_num` int(5) DEFAULT '1' COMMENT '单位数量',
  `goods_prepare_time` int(2) DEFAULT '1' COMMENT '备货时间',
  `product_url` varchar(455) DEFAULT NULL COMMENT '商品url',
  `remark` varchar(255) DEFAULT NULL COMMENT '订单备注',
  `purchase_price` decimal(10,2) NULL DEFAULT NULL COMMENT '订单商品采购价snapshot',
  `purchase_price_form` DATETIME NULL DEFAULT NULL COMMENT '订单商品采购价snapshot生效的起始时间',
  `purchase_price_to` DATETIME NULL DEFAULT NULL COMMENT '订单商品采购价snapshot生效的结束时间',
  `is_sys_create_sku` char(1) NOT NULL DEFAULT '' COMMENT '空sku是否自动创建系统sku并保存到item表中,Y为是,N或空为不是',
  `addi_info` text COMMENT '额外信息',
  `oversea_sku` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '订单级别的海外仓sku',
  `delivery_status` varchar(20) DEFAULT NULL COMMENT '小老板中该item是否能发货',
  `platform_status` varchar(255) DEFAULT NULL COMMENT '各个平台对应的item status',
  `item_source` varchar(20) DEFAULT 'platform' COMMENT '商品来源：platform表示平台商品，local表示本地商品',
  `manual_status` varchar(20) DEFAULT 'enable' COMMENT '手工操作状态：enable表示启用，disable表示禁用',
  `root_sku` VARCHAR(255) NULL COMMENT '小老板的root sku' ,
  `declaration` TEXT NULL COMMENT '订单item级别的报关信息' ,
  PRIMARY KEY (`order_item_id`),
  KEY `idx_oid` (`order_id`),
  KEY `idx_itemid` (`order_source_order_item_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='订单商品表' AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `od_order_log`
--

CREATE TABLE IF NOT EXISTS `od_order_log` (
  `order_log_id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '日志编号',
  `order_id` int(11) unsigned zerofill NOT NULL DEFAULT '00000000000' COMMENT '订单号',
  `order_parameter` varchar(255) NOT NULL DEFAULT '' COMMENT '订单参数',
  `content` text NOT NULL COMMENT '日志详细信息',
  `user_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '操作人id',
  `user_name` varchar(20) NOT NULL DEFAULT '' COMMENT '操作人用户名',
  `create_time` int(11) unsigned NOT NULL COMMENT '创建时间',
  `update_time` int(11) unsigned NOT NULL COMMENT '更新时间',
  PRIMARY KEY (`order_log_id`,`order_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='订单日志表' AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `od_order_manual`
--

CREATE TABLE IF NOT EXISTS `od_order_manual` (
  `order_manual_id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '人工处理id',
  `order_manual_name` varchar(50) NOT NULL DEFAULT '' COMMENT '人工处理状态',
  `create_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  PRIMARY KEY (`order_manual_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='人工处理状态表' AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `od_order_remark`
--

CREATE TABLE IF NOT EXISTS `od_order_remark` (
  `order_remark_id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '订单备注id',
  `order_id` int(11) unsigned zerofill NOT NULL DEFAULT '00000000000' COMMENT '订单号',
  `content` text NOT NULL COMMENT '留言内容',
  `user_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '留言用户id',
  `user_name` varchar(20) NOT NULL DEFAULT '' COMMENT '用户名',
  `create_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  PRIMARY KEY (`order_remark_id`),
  KEY `idx_idtime` (`order_id`,`update_time`),
  KEY `idx_uidtime` (`user_id`,`update_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='订单备注表' AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `od_order_shipped`
--

CREATE TABLE IF NOT EXISTS `od_order_shipped` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `order_id` int(11) NOT NULL COMMENT '订单号',
  `order_source` varchar(50) NOT NULL DEFAULT '' COMMENT '订单来源  ebay,amazon,aliexpress,custom',
  `selleruserid` varchar(80) DEFAULT NULL COMMENT '卖家账号',
  `tracking_number` varchar(50) NOT NULL DEFAULT '' COMMENT '发货单号',
  `tracking_link` varchar(100) NOT NULL DEFAULT '' COMMENT '跟踪号查询网址',
  `shipping_method_code` varchar(50) NOT NULL DEFAULT '' COMMENT '物流方式code',
  `status` tinyint(2) NOT NULL DEFAULT '0' COMMENT '订单标记发货状态，0:未处理，1:成功，2:失败',
  `sync_to_tracker` char(1) NOT NULL DEFAULT '' COMMENT '是否同步到Tracker模块了，Y为是，默认空白为否',
  `result` varchar(20) DEFAULT NULL COMMENT '执行结果',
  `errors` varchar(255) DEFAULT NULL COMMENT '返回错误',
  `created` int(11) DEFAULT NULL COMMENT '创建时间',
  `lasttime` int(11) DEFAULT NULL COMMENT '标记时间',
  PRIMARY KEY (`id`),
  KEY `idx_oid` (`order_id`) USING BTREE
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='订单标发货表' AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `od_order_shipped_v2`
--

CREATE TABLE IF NOT EXISTS `od_order_shipped_v2` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `order_id` int(11) NOT NULL COMMENT '订单号',
  `order_source` varchar(50) NOT NULL DEFAULT '' COMMENT '订单来源  ebay,amazon,aliexpress,custom',
  `selleruserid` varchar(80) DEFAULT NULL COMMENT '卖家账号',
  `tracking_number` varchar(50) NOT NULL DEFAULT '' COMMENT '发货单号',
  `tracking_link` TEXT NOT NULL DEFAULT '' COMMENT '跟踪号查询网址',
  `shipping_method_code` varchar(50) NOT NULL DEFAULT '' COMMENT '物流方式code',
  `shipping_method_name` varchar(100) DEFAULT NULL COMMENT '物流方式名',
  `status` tinyint(2) NOT NULL DEFAULT '0' COMMENT '订单标记发货状态，0:未处理，1:成功，2:失败',
  `sync_to_tracker` char(1) NOT NULL DEFAULT '' COMMENT '是否同步到Tracker模块了，Y为是，默认空白为否',
  `result` varchar(20) DEFAULT NULL COMMENT '执行结果',
  `errors` text DEFAULT NULL COMMENT '返回错误',
  `created` int(11) DEFAULT NULL COMMENT '创建时间',
  `updated` int(11) DEFAULT NULL COMMENT '修改时间',
  `lasttime` int(11) DEFAULT NULL COMMENT '标记时间',
  `return_no` varchar(255) DEFAULT '' COMMENT '物流商返回数据',
  `shipping_service_id` int(11) DEFAULT NULL COMMENT '通过小老板平台发货 存储运输服务id',
  `order_source_order_id` varchar(50) DEFAULT NULL COMMENT '订单来源平台订单号',
  `addtype` varchar(100) DEFAULT NULL COMMENT '物流号来源',
  `signtype` varchar(10) DEFAULT NULL COMMENT '全部发货all 部分发货part',
  `description` varchar(255) DEFAULT NULL COMMENT '发货备注',
  `customer_number` varchar(50) DEFAULT NULL COMMENT '物流商返回的客户单号用户查询物流号',
  `tracker_status` varchar(30) NOT NULL DEFAULT '' COMMENT 'tracker的物流状态',
  PRIMARY KEY (`id`),
  KEY `idx_oid` (`order_id`) USING BTREE,
  KEY `sellerid_orderid` (`selleruserid`,`order_id`),
  KEY `trackno` (`tracking_number`),
  KEY `trackeruse` (`sync_to_tracker`,`order_id`,`status`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='订单标发货表' AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `od_order_v2`
--
CREATE TABLE IF NOT EXISTS `od_order_v2` (
  `order_id` int(11) unsigned zerofill NOT NULL AUTO_INCREMENT COMMENT '订单id',
  `order_status` smallint(5) NOT NULL DEFAULT '0' COMMENT '订单流程状态:100:未付款,200:已付款,201:有留言,205:未分仓库,210:SKU不存在,215:报关信息不全,220:paypal/ebay金额不对,225:paypal/ebay地址不对,230:未匹配物流,300:待生成包裹,400:发货处理中,500:已发货,600:已取消',
  `pay_status` tinyint(2) unsigned NOT NULL DEFAULT '0' COMMENT '支付状态',
  `order_source_status` varchar(50) DEFAULT NULL COMMENT '订单来源平台订单状态',
  `order_manual_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '自定义标签',
  `is_manual_order` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '是否挂起状态',
  `shipping_status` tinyint(2) unsigned NOT NULL DEFAULT '0' COMMENT '平台发货状态',
  `exception_status` smallint(5) DEFAULT '0' COMMENT '检测异常状态',
  `weird_status` char(10) DEFAULT NULL COMMENT '操作异常标签',
  `order_source` varchar(50) NOT NULL DEFAULT '' COMMENT '订单来源  ebay,amazon,aliexpress,custom',
  `order_type` varchar(50) NOT NULL DEFAULT '' COMMENT '订单类型如amazon FBA订单',
  `order_source_order_id` varchar(50) NOT NULL DEFAULT '' COMMENT '订单来源  的订单id',
  `order_source_site_id` varchar(50) NOT NULL DEFAULT '' COMMENT '订单来源平台下的站点：如eaby下的US站点',
  `selleruserid` varchar(50) NOT NULL DEFAULT '' COMMENT '订单来源平台卖家用户名(下单时候的用户名)',
  `saas_platform_user_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT 'saas库平台用户卖家账号id(ebay或者amazon卖家表中)',
  `order_source_srn` int(11) unsigned NOT NULL DEFAULT '0' COMMENT 'od_ebay_order表salesrecordnum',
  `customer_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT 'od_customer 表id',
  `source_buyer_user_id` varchar(255) NOT NULL DEFAULT '' COMMENT '来源买家用户名',
  `order_source_shipping_method` varchar(50) NOT NULL DEFAULT '' COMMENT '平台下单时用户选择的物流方式',
  `order_source_create_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '订单在来源平台的下单时间',
  `subtotal` decimal(10,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '产品总价格',
  `shipping_cost` decimal(10,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '运费',
  `antcipated_shipping_cost` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '预估运费',
  `actual_shipping_cost` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '实际运费',
  `discount_amount` decimal(10,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '折扣',
  `commission_total` decimal(10,2) DEFAULT '0.00' COMMENT '订单平台佣金',
  `paypal_fee` decimal(10,2) DEFAULT '0.00' COMMENT '暂时ebay使用的paypal 佣金（其他平台待开发其用途）',
  `grand_total` decimal(10,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '合计金额(产品总价格 + 运费 - 折扣 = 合计金额)',
  `returned_total` decimal(10,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '退款总金额',
  `price_adjustment` decimal(10,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '价格手动调整（下单后人工调整）',
  `currency` char(3) NOT NULL DEFAULT '' COMMENT '货币',
  `consignee` varchar(255) NOT NULL DEFAULT '' COMMENT '收货人',
  `consignee_postal_code` varchar(50) NOT NULL DEFAULT '' COMMENT '收货人邮编',
  `consignee_phone` varchar(255) NOT NULL DEFAULT '' COMMENT '收货人电话',
  `consignee_mobile` varchar(20) DEFAULT NULL COMMENT '收货人手机',
  `consignee_fax` varchar(255) DEFAULT NULL COMMENT '收件人传真号',
  `consignee_email` varchar(255) NOT NULL DEFAULT '' COMMENT '收货人Email',
  `consignee_company` varchar(255) NOT NULL DEFAULT '' COMMENT '收货人公司',
  `consignee_country` varchar(255) NOT NULL DEFAULT '' COMMENT '收货人国家名',
  `consignee_country_code` char(2) NOT NULL DEFAULT '' COMMENT '收货人国家代码',
  `consignee_city` varchar(255) NOT NULL DEFAULT '' COMMENT '收货人城市',
  `consignee_province` varchar(255) NOT NULL DEFAULT '' COMMENT '收货人省',
  `consignee_district` varchar(255) NOT NULL DEFAULT '' COMMENT '收货人区',
  `consignee_county` varchar(255) NOT NULL DEFAULT '' COMMENT '收货人镇',
  `consignee_address_line1` varchar(255) NOT NULL DEFAULT '' COMMENT '收货人地址1',
  `consignee_address_line2` varchar(255) NOT NULL DEFAULT '' COMMENT '收货人地址2',
  `consignee_address_line3` varchar(255) NOT NULL DEFAULT '' COMMENT '收货人地址3',
  `default_warehouse_id` int(11) NOT NULL DEFAULT '0' COMMENT '默认的仓库id',
  `default_carrier_code` varchar(50) NOT NULL DEFAULT '' COMMENT '默认物流商代码',
  `default_shipping_method_code` varchar(50) NOT NULL DEFAULT '' COMMENT '默认运输服务id',
  `paid_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '订单付款时间',
  `delivery_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '平台订单发货时间',
  `create_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int(11) NOT NULL DEFAULT '0' COMMENT '更新时间 ',
  `user_message` varchar(255) NOT NULL DEFAULT '' COMMENT '用户留言',
  `carrier_type` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '0:普通物流商  1:海外仓',
  `hassendinvoice` tinyint(1) DEFAULT '0' COMMENT '是否有发送ebay账单',
  `seller_commenttype` varchar(32) DEFAULT NULL COMMENT '卖家评价类型',
  `seller_commenttext` varchar(255) DEFAULT NULL COMMENT '卖家评价留言',
  `status_dispute` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否有发起ebay催款，0没有',
  `is_feedback` int(1) DEFAULT NULL,
  `rule_id` int(11) DEFAULT NULL COMMENT '运输服务匹配规则id',
  `customer_number` varchar(50) DEFAULT NULL COMMENT '物流商返回的客户单号用户查询物流号',
  `carrier_step` tinyint(1) DEFAULT '0' COMMENT '物流操作步骤',
  `is_print_picking` tinyint(1) DEFAULT '0' COMMENT '是否打印拣货单',
  `print_picking_operator` int(10) DEFAULT NULL COMMENT '打印拣货单操作人',
  `print_picking_time` int(11) DEFAULT NULL COMMENT '打印拣货单时间',
  `is_print_distribution` tinyint(1) DEFAULT '0' COMMENT '是否打印配货单',
  `print_distribution_operator` int(10) DEFAULT NULL COMMENT '打印配货单操作人',
  `print_distribution_time` int(11) DEFAULT NULL COMMENT '配货单打印时间',
  `is_print_carrier` tinyint(1) DEFAULT '0' COMMENT '是否打印物流单',
  `print_carrier_operator` int(10) DEFAULT NULL COMMENT '打印物流单操作人',
  `printtime` int(11) DEFAULT '0' COMMENT '订单物流单打单时间',
  `delivery_status` tinyint(2) DEFAULT '0' COMMENT '小老板发货流程状态',
  `delivery_id` bigint(13) DEFAULT NULL COMMENT '拣货单号',
  `desc` text COMMENT '订单备注',
  `carrier_error` text,
  `is_comment_status` tinyint(1) unsigned DEFAULT '0' COMMENT '该订单是否已给好评',
  `is_comment_ignore` tinyint(1) unsigned DEFAULT '0' COMMENT '该订单是否设置好评忽略',
  `issuestatus` varchar(20) DEFAULT '' COMMENT '订单纠纷状态',
  `payment_type` varchar(50) NOT NULL DEFAULT '' COMMENT '支付类型',
  `logistic_status` varchar(255) DEFAULT NULL COMMENT '物流状态',
  `logistic_last_event_time` datetime DEFAULT NULL COMMENT '物流最后更新时间',
  `fulfill_deadline` int(11) NOT NULL DEFAULT '0' COMMENT '销售平台最后发货期限',
  `profit` decimal(10,2) DEFAULT NULL COMMENT '小老板计算出的利润',
  `logistics_cost` decimal(10,2) DEFAULT NULL COMMENT '物流成本',
  `logistics_weight` decimal(10,2) DEFAULT NULL COMMENT '物流商返回的称重重量(g)',
  `addi_info` text COMMENT '额外信息',
  `distribution_inventory_status` tinyint(2) NOT NULL DEFAULT '0' COMMENT '分配库存状态2待分配，3缺货，4已分配',
  `reorder_type` varchar(50) DEFAULT NULL COMMENT '重新发货类型',
  `purchase_status` tinyint(2) NOT NULL DEFAULT '0' COMMENT '采购(缺货)状态',
  `pay_order_type` varchar(50) DEFAULT NULL COMMENT '已付款订单类型',
  `order_evaluation` tinyint(2) NOT NULL DEFAULT '0' COMMENT '订单评价1为好评，2为中评，3差评',
  `tracker_status` varchar(30) DEFAULT NULL COMMENT 'tracker的物流状态',
  `origin_shipment_detail` text COMMENT '原始的订单收件人信息',
  `order_ship_time` datetime DEFAULT NULL COMMENT '小老板发货时间',
  `shipping_notified` char(1) NOT NULL DEFAULT 'N' COMMENT '是否发送过启运通知,I为旧数据忽略',
  `pending_fetch_notified` char(1) NOT NULL DEFAULT 'N' COMMENT '是否发送过到达待取通知,I为旧数据忽略',
  `delivery_failed_notified` char(1) NOT NULL DEFAULT 'N' COMMENT '',
  `rejected_notified` char(1) NOT NULL DEFAULT 'N' COMMENT '是否发送过异常退回通知,I为旧数据忽略',
  `received_notified` char(1) NOT NULL DEFAULT 'N' COMMENT '是否发送过已签收求好评通知,I为旧数据忽略',
  `seller_weight` decimal(10,2) DEFAULT '0.00' COMMENT '卖家自己的称重重量(g)(取整)',
  `order_capture` char(1) NOT NULL DEFAULT 'N' COMMENT '是否手工订单N为否,Y为是',
  `order_relation` varchar(10) NOT NULL DEFAULT 'normal' COMMENT '订单类型 正常为normal,合并原始订单为fm,合并出来的新订单 sm,拆分的原始订单为fs,拆分后的新订单为ss ',
  `last_modify_time` datetime DEFAULT NULL COMMENT '订单的最后修改时间',
  `sync_shipped_status` char(1) NOT NULL DEFAULT 'Y' COMMENT '虚拟发货的同步状态P为待提交，S为提交中，F为提交失败，C为提交成功（小老板），Y为提交成功（非小老板）',
  `system_tag_1` char(1) NOT NULL DEFAULT '' COMMENT '系统标签1，Y为该标签标记了，N或者空白未没有',
  `system_tag_2` char(1) NOT NULL DEFAULT '' COMMENT '系统标签2，Y为该标签标记了，N或者空白未没有',
  `system_tag_3` char(1) NOT NULL DEFAULT '' COMMENT '系统标签3，Y为该标签标记了，N或者空白未没有',
  `system_tag_4` char(1) NOT NULL DEFAULT '' COMMENT '系统标签4，Y为该标签标记了，N或者空白未没有',
  `system_tag_5` char(1) NOT NULL DEFAULT '' COMMENT '系统标签5，Y为该标签标记了，N或者空白未没有',
  `customized_tag_1` char(1) NOT NULL DEFAULT '' COMMENT '自定义标签1，Y为该标签标记了，N或者空白未没有',
  `customized_tag_2` char(1) NOT NULL DEFAULT '' COMMENT '自定义标签2，Y为该标签标记了，N或者空白未没有',
  `customized_tag_3` char(1) NOT NULL DEFAULT '' COMMENT '自定义标签3，Y为该标签标记了，N或者空白未没有',
  `customized_tag_4` char(1) NOT NULL DEFAULT '' COMMENT '自定义标签4，Y为该标签标记了，N或者空白未没有',
  `customized_tag_5` char(1) NOT NULL DEFAULT '' COMMENT '自定义标签5，Y为该标签标记了，N或者空白未没有',
  `customized_tag_6` char(1) NOT NULL DEFAULT '' COMMENT '自定义标签6，Y为该标签标记了，N或者空白未没有',
  `customized_tag_7` char(1) NOT NULL DEFAULT '' COMMENT '自定义标签7，Y为该标签标记了，N或者空白未没有',
  `customized_tag_8` char(1) NOT NULL DEFAULT '' COMMENT '自定义标签8，Y为该标签标记了，N或者空白未没有',
  `customized_tag_9` char(1) NOT NULL DEFAULT '' COMMENT '自定义标签9，Y为该标签标记了，N或者空白未没有',
  `customized_tag_10` char(1) NOT NULL DEFAULT '' COMMENT '自定义标签10，Y为该标签标记了，N或者空白未没有',
  `tracking_no_state` int(3) default 0 COMMENT '物流商获取跟踪号状态 0:表示未获取过,1:表示获取失败,2:表示成功获取',
  `order_verify` varchar(20) DEFAULT NULL COMMENT '订单是否验证，ebay主要用于验证paypal地址与ebay地址是否一致',
  `items_md5` varchar(128) DEFAULT NULL COMMENT '所有商品关键值生成的md5,主要用于是否更新商品item表',
  `complete_ship_time` INT(11) NULL COMMENT '确认发货完成时间' ,
  `declaration_info` text DEFAULT '' COMMENT '用于记录最后一次上传成功的报关信息,以json格式',
  `isshow` char(1) NOT NULL DEFAULT 'Y' COMMENT '是否显示，Y为显示，N为不显示',
  `first_sku` varchar(255) DEFAULT NULL COMMENT '商品的第一个sku,可用于排序',
  `ismultipleProduct` char(1) NOT NULL DEFAULT 'N' COMMENT '是否多品订单，Y为是，N为不是',
  `tracking_number` varchar(255) DEFAULT NULL COMMENT '物流跟踪号',
  `billing_info` text COMMENT '账单地址相关信息',
  `transaction_key` varchar(255) DEFAULT NULL COMMENT '订单交易号（目前用于记录prestashop线下付款的汇款号）',
  PRIMARY KEY (`order_id`),
  KEY `idx_statmanu` (`order_status`,`order_manual_id`) USING BTREE,
  KEY `idx_timeseller` (`order_source_create_time`,`selleruserid`) USING BTREE,
  KEY `idx_timebuyer` (`order_source_create_time`,`source_buyer_user_id`) USING BTREE,
  KEY `idx_timegrand` (`order_source_create_time`,`grand_total`),
  KEY `idx_buyeremail` (`consignee_email`),
  KEY `idx_manu` (`order_manual_id`),
  KEY `order_source_order_id` (`order_source_order_id`),
  KEY `idx_order_customer` (`order_source`,`source_buyer_user_id`),
  KEY `idx_comment_status` (`is_comment_status`),
  KEY `idx_comment_ignore` (`is_comment_ignore`),
  KEY `idx_issuestatus` (`issuestatus`),
  KEY `logistic_status` (`logistic_status`,`order_source`) COMMENT 'logistic_status',
  KEY `pay_order_type` (`pay_order_type`),
  KEY `order_ship_time` (`order_ship_time`),
  KEY `shipping_notified` (`shipping_notified`,`pending_fetch_notified`,`rejected_notified`,`received_notified`),
  KEY `platform_shiptime` (`order_source`,`order_ship_time`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COMMENT='订单表' AUTO_INCREMENT=1 ;


-- --------------------------------------------------------

--
-- Table structure for table `od_paypal_transaction`
--

CREATE TABLE IF NOT EXISTS `od_paypal_transaction` (
  `ptid` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `uid` int(10) DEFAULT NULL,
  `eorderid` int(10) DEFAULT NULL COMMENT 'ebay_orderdid',
  `ebay_orderid` varchar(50) DEFAULT NULL COMMENT 'ebay_orderd',
  `order_id` int(10) DEFAULT NULL COMMENT 'order_id系统订单号',
  `transactionid` varchar(50) DEFAULT NULL,
  `transactiontype` varchar(10) DEFAULT NULL,
  `ordertime` int(10) DEFAULT NULL,
  `amt` decimal(8,3) DEFAULT NULL,
  `feeamt` decimal(8,3) DEFAULT NULL,
  `netamt` decimal(8,3) DEFAULT NULL,
  `currencycode` char(3) DEFAULT NULL,
  `buyerid` varchar(50) DEFAULT NULL,
  `email` varchar(50) DEFAULT NULL COMMENT '付款Email',
  `receiverbusiness` varchar(50) DEFAULT NULL COMMENT '收款账户',
  `receiveremail` varchar(50) DEFAULT NULL COMMENT '收款账户',
  `shiptoname` varchar(50) DEFAULT NULL,
  `shiptostreet` varchar(50) DEFAULT NULL,
  `shiptostreet2` varchar(100) DEFAULT NULL,
  `shiptocity` varchar(50) DEFAULT NULL,
  `shiptostate` varchar(50) DEFAULT NULL,
  `shiptocountrycode` varchar(3) DEFAULT NULL,
  `shiptocountryname` varchar(50) DEFAULT NULL,
  `shiptozip` varchar(20) DEFAULT NULL,
  `addressowner` varchar(20) DEFAULT NULL,
  `paymentstatus` varchar(20) DEFAULT NULL COMMENT '支付状态',
  `detail` text COMMENT '订单内容',
  `created` int(10) DEFAULT NULL,
  `updated` int(10) DEFAULT NULL,
  PRIMARY KEY (`ptid`),
  UNIQUE KEY `transactionid` (`transactionid`),
  KEY `uid` (`uid`),
  KEY `eorderid` (`eorderid`),
  KEY `ebay_orderid` (`ebay_orderid`),
  KEY `idx_oid` (`order_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `od_shippingmethod_automatic_matching`
--

CREATE TABLE IF NOT EXISTS `od_shippingmethod_automatic_matching` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `order_source` varchar(50) NOT NULL DEFAULT '' COMMENT '订单来源  ebay,amazon,aliexpress,custom',
  `order_source_site_id` varchar(255) NOT NULL DEFAULT '' COMMENT '订单来源平台下的站点：如eaby下的US站点',
  `order_selleruserid` varchar(255) NOT NULL DEFAULT '' COMMENT '订单来源平台卖家用户id',
  `order_subtotal_max` decimal(10,2) NOT NULL COMMENT '订单总金额的最大值',
  `order_subtotal_min` decimal(10,2) NOT NULL COMMENT '订单总金额的最小值',
  `order_weight_min` int(11) NOT NULL COMMENT '订单总重量的最小值',
  `order_has_sku` varchar(255) NOT NULL DEFAULT '' COMMENT '指定sku',
  `order_weight_max` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '订单总重量最大值',
  `order_country` varchar(255) NOT NULL COMMENT '目的地国家',
  `order_source_shipping_method` varchar(255) NOT NULL DEFAULT '' COMMENT '平台下单时用户选择的物流方式',
  `warehouse_id` varchar(255) NOT NULL COMMENT '分配的仓库',
  `carrier_code` varchar(255) NOT NULL DEFAULT '' COMMENT '关联物流商code',
  `shipping_method_code` varchar(255) NOT NULL DEFAULT '' COMMENT '物流方式编码',
  `rule_name` varchar(255) DEFAULT NULL COMMENT '规则名称',
  `weights` varchar(255) DEFAULT NULL COMMENT '权重',
  `is_active` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '是否开启0：未开启1：开启',
  `create_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `idx_sousite` (`is_active`,`order_source`,`order_source_site_id`,`order_selleruserid`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='物流方式自动匹配规则' AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `operation_log`
--

CREATE TABLE IF NOT EXISTS `operation_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `log_key` varchar(45) NOT NULL DEFAULT '' COMMENT '操作对象的实体唯一标识\n采购单号，产品sku等',
  `log_type` enum('purchase','stock_change','product','finance','warehouse','supplier','stock_take','delivery','order','tracking') NOT NULL COMMENT '采购单，库存变动 等日志的类型',
  `log_operation` varchar(45) NOT NULL DEFAULT '' COMMENT '具体操作的类型',
  `capture_user_name` varchar(50) NOT NULL DEFAULT '0' COMMENT '操作者的名称',
  `update_time` timestamp NULL DEFAULT NULL COMMENT '操作时间',
  `comment` varchar(255) NOT NULL DEFAULT '' COMMENT '备注',
  PRIMARY KEY (`id`),
  KEY `index2` (`log_key`,`log_type`),
  KEY `index3` (`capture_user_name`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COMMENT='操作日志，for 仓储，采购，产品，财务4个模块使用' AUTO_INCREMENT=2 ;

-- --------------------------------------------------------

--
-- Table structure for table `operation_log_v2`
--

CREATE TABLE IF NOT EXISTS `operation_log_v2` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `log_key` varchar(45) NOT NULL DEFAULT '' COMMENT '操作对象的实体唯一标识\n采购单号，产品sku等',
  `log_type` enum('purchase','stock_change','product','finance','warehouse','supplier','stock_take','delivery','order','tracking') NOT NULL COMMENT '采购单，库存变动 等日志的类型',
  `log_operation` varchar(45) NOT NULL DEFAULT '' COMMENT '具体操作的类型',
  `capture_user_name` varchar(50) NOT NULL DEFAULT '0' COMMENT '操作者的名称',
  `update_time` timestamp NULL DEFAULT NULL COMMENT '操作时间',
  `comment` varchar(255) NOT NULL DEFAULT '' COMMENT '备注',
  PRIMARY KEY (`id`),
  KEY `index2` (`log_key`,`log_type`),
  KEY `index3` (`capture_user_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='操作日志，for 仓储，采购，产品，财务4个模块使用' AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `pc_operation_log`
--

CREATE TABLE IF NOT EXISTS `pc_operation_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `log_key` varchar(45) NOT NULL DEFAULT '' COMMENT '操作对象的实体唯一标识\n采购单号，产品sku等',
  `log_type` enum('purchase','stock_change','product','finance','warehouse','supplier','stock_take') NOT NULL COMMENT '采购单，库存变动 等日志的类型\n',
  `log_operation` varchar(45) NOT NULL DEFAULT '' COMMENT '具体操作的类型',
  `capture_user_id` smallint(6) NOT NULL DEFAULT '0' COMMENT '操作者的id',
  `update_time` timestamp NULL DEFAULT NULL COMMENT '操作时间',
  `comment` varchar(255) NOT NULL DEFAULT '' COMMENT '备注',
  PRIMARY KEY (`id`),
  KEY `index2` (`log_key`,`log_type`),
  KEY `index3` (`capture_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='操作日志，for 仓储，采购，产品，财务4个模块使用' AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `pc_purchase`
--

CREATE TABLE IF NOT EXISTS `pc_purchase` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '采购单表ID',
  `purchase_order_id` varchar(255) DEFAULT '' COMMENT '系统生成的内部采购单号',
  `purchase_source_id` varchar(100) DEFAULT '' COMMENT '采购来源ID，如\n淘宝订单号：TB666986546354\n网易采购单：WY64985343456',
  `warehouse_id` tinyint(3) unsigned DEFAULT NULL COMMENT '仓库号',
  `supplier_id` smallint(5) unsigned DEFAULT NULL,
  `supplier_name` varchar(255) NOT NULL DEFAULT '' COMMENT 'supplier Name snap shot',
  `status` tinyint(3) unsigned DEFAULT NULL COMMENT '状态\n\n1：等待到货\n2：货品在途\n3：部分到货等待剩余\n4：全部到货\n5：部分到货不等剩余',
  `delivery_method` tinyint(3) unsigned DEFAULT NULL COMMENT '递送方式编号',
  `delivery_number` varchar(30) NOT NULL DEFAULT '' COMMENT '递送追踪号',
  `delivery_fee` decimal(10,2) DEFAULT NULL COMMENT '递送费金额',
  `amount` decimal(10,2) DEFAULT NULL COMMENT '总金额',
  `amount_subtotal` decimal(10,2) DEFAULT NULL,
  `amount_refunded` decimal(10,2) DEFAULT NULL COMMENT '总退款金额',
  `payment_status` tinyint(3) unsigned DEFAULT NULL COMMENT '付款状态\n1：未付款\n2：已付款',
  `pay_date` timestamp NULL DEFAULT NULL COMMENT '支付日期',
  `payment_method` tinyint(3) unsigned DEFAULT NULL COMMENT '支付方式',
  `payment_record_id` int(11) unsigned DEFAULT NULL COMMENT '支付流水号',
  `is_refunded` char(1) NOT NULL DEFAULT '' COMMENT '是否退款了\n“Y”:yes\n“N”:No',
  `is_pending_check` char(1) NOT NULL DEFAULT '' COMMENT '是否等待质检\nY: 是，有到货记录等待质检\nN: 否',
  `expected_arrival_date` timestamp NULL DEFAULT NULL,
  `is_arrive_goods` char(1) NOT NULL DEFAULT '' COMMENT '是否到货了\n“Y”:yes\n“N”:No',
  `comment` text COMMENT '备注',
  `capture_user_name` varchar(50) NOT NULL DEFAULT '' COMMENT 'Capture User Name',
  `create_time` timestamp NULL DEFAULT NULL COMMENT '创建时间',
  `update_time` timestamp NULL DEFAULT NULL COMMENT '修改时间 ',
  `reject_reason` varchar(255) DEFAULT NULL COMMENT '记录审核拒绝原因',
  `in_stock_comment` text COMMENT '入库备注',
  `order_id_1688` varchar(50) NULL DEFAULT '' COMMENT '1688订单号',
  `amount_1688` decimal(10,2)	NULL DEFAULT 0 COMMENT '1688订单金额',
  `status_1688` varchar(50)	NULL DEFAULT '' COMMENT '1688订单状态',
  `pay_url` varchar(255)	NULL DEFAULT '' COMMENT '支付链接',
  `logistics_billNo` varchar(100)	NULL DEFAULT '' COMMENT '1688物流运单号',
  `logistics_company_name` varchar(100) NULL DEFAULT '' COMMENT '1688物流公司名称',
  `logistics_status` varchar(50)	NULL DEFAULT '' COMMENT '1688物流状态',
  `addi_info` text COMMENT '额外信息',
  PRIMARY KEY (`id`),
  KEY `index2` (`payment_method`),
  KEY `index3` (`status`),
  KEY `index4` (`purchase_order_id`),
  KEY `index5` (`purchase_source_id`),
  KEY `index6` (`supplier_id`),
  KEY `index7` (`payment_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='采购单的基本信息' AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `pc_purchase_arrivals`
--

CREATE TABLE IF NOT EXISTS `pc_purchase_arrivals` (
  `purchase_arrival_id` int(11) NOT NULL AUTO_INCREMENT COMMENT '标识行',
  `purchase_arrival_name` varchar(45) DEFAULT NULL COMMENT 'Arrival name, system auto generated.\ne.g.: IN/0001',
  `purchase_id` int(11) NOT NULL DEFAULT '0' COMMENT '采购单表id',
  `status` tinyint(3) unsigned DEFAULT NULL COMMENT '状态\n1: 待质检\n2：已质检待入库\n3：已入库',
  `is_rejected` char(1) NOT NULL DEFAULT '' COMMENT '是否退款/退货了\n“Y”:yes\n“N”:No',
  `is_pending_check` char(1) NOT NULL DEFAULT '' COMMENT '是否等待质检\n“Y”:yes\n“N”:No',
  `addi_info` varchar(255) NOT NULL DEFAULT '' COMMENT '额外信息json格式\n额外的附加信息，使用json格式记录',
  `comment` varchar(255) NOT NULL DEFAULT '' COMMENT '备注',
  `capture_user_id` tinyint(3) unsigned DEFAULT NULL COMMENT 'Capture User ID',
  `create_time` timestamp NULL DEFAULT NULL COMMENT '创建时间',
  `update_time` timestamp NULL DEFAULT NULL COMMENT '修改时间 ',
  PRIMARY KEY (`purchase_arrival_id`),
  KEY `index2` (`purchase_id`),
  KEY `index3` (`purchase_arrival_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='采购单到货信息，包含了相应的质检信息，入库信息' AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `pc_purchase_arrival_detail`
--

CREATE TABLE IF NOT EXISTS `pc_purchase_arrival_detail` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `purchase_arrival_id` int(11) unsigned NOT NULL COMMENT '标识行',
  `sku` varchar(255) NOT NULL DEFAULT '' COMMENT '产品SKU号',
  `qty` smallint(5) unsigned NOT NULL DEFAULT '0' COMMENT '数量',
  `qty_passed` smallint(5) unsigned NOT NULL DEFAULT '0' COMMENT '良品数量',
  `qty_defect` smallint(5) unsigned NOT NULL DEFAULT '0' COMMENT '次品数量',
  `check_comment` varchar(255) NOT NULL DEFAULT '' COMMENT '备注',
  `check_user_id` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT 'Capture User ID',
  `check_time` timestamp NULL DEFAULT NULL COMMENT '质检时间',
  `stock_in_id` varchar(255) NOT NULL DEFAULT '' COMMENT '入库单号,如果有多个，则保存为 "," 逗号分隔的形式，例如\n"aaaaa11,bbbb22,bbbb33"',
  `stock_in_qty` smallint(5) unsigned NOT NULL DEFAULT '0' COMMENT '已入库数量',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='采购单到货信息，包含了相应的质检信息，入库信息' AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `pc_purchase_arrival_reject`
--

CREATE TABLE IF NOT EXISTS `pc_purchase_arrival_reject` (
  `arrival_reject_id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '标识行',
  `purchase_arrival_id` int(11) DEFAULT NULL COMMENT '到货编号',
  `total_amount` decimal(10,2) NOT NULL COMMENT '退回的货款',
  `addi_info` varchar(255) NOT NULL DEFAULT '' COMMENT '额外信息json格式\n额外的附加信息，使用json格式记录',
  `capture_user_id` tinyint(3) unsigned DEFAULT NULL COMMENT 'Capture User ID',
  `create_time` timestamp NULL DEFAULT NULL COMMENT '创建时间',
  PRIMARY KEY (`arrival_reject_id`),
  KEY `index2` (`purchase_arrival_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='采购单退货/退款表' AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `pc_purchase_arrival_reject_detail`
--

CREATE TABLE IF NOT EXISTS `pc_purchase_arrival_reject_detail` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `arrival_reject_id` varchar(45) NOT NULL DEFAULT '' COMMENT '到货记录退货记录号',
  `sku` varchar(250) NOT NULL DEFAULT '' COMMENT '产品SKU',
  `qty` int(11) DEFAULT '0' COMMENT '退货数量',
  `amount` decimal(10,2) DEFAULT '0.00' COMMENT '退货金额',
  `reject_type` char(2) NOT NULL DEFAULT '' COMMENT '退货明细类型，如产品SKU或者运费\n或者产品价格补偿等',
  `comment` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  KEY `index2` (`arrival_reject_id`,`sku`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='采购单退货明细' AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `pc_purchase_items`
--

CREATE TABLE IF NOT EXISTS `pc_purchase_items` (
  `purchase_item_id` int(11) NOT NULL AUTO_INCREMENT,
  `purchase_id` int(11) NOT NULL DEFAULT '0' COMMENT '采购单表id',
  `sku` varchar(255) NOT NULL DEFAULT '' COMMENT '仓库号',
  `name` varchar(255) NOT NULL DEFAULT '' COMMENT '产品名称快照',
  `supplier_id` tinyint(3) unsigned DEFAULT NULL COMMENT '供应商编号',
  `supplier_name` varchar(255) NOT NULL DEFAULT '' COMMENT 'supplier Name snap shot',
  `price` decimal(10,2) DEFAULT NULL COMMENT '单价',
  `qty` int(8) unsigned DEFAULT NULL COMMENT '数量',
  `amount` decimal(10,2) DEFAULT NULL COMMENT '总金额',
  `addi_info` varchar(255) NOT NULL DEFAULT '' COMMENT '额外信息json格式\n额外的附加信息，使用json格式记录',
  `remark` varchar(255) DEFAULT '' COMMENT '备注',
  `in_stock_qty` int(8) not null default 0 COMMENT '本次入库数量',
  `product_id` varchar(50) NOT NULL COMMENT '1688商品product_id',
  `spec_id` varchar(50) NOT NULL COMMENT '1688商品规格Id',
  `qty_1688` int(11) NULL DEFAULT 0 COMMENT '1688数量',
  PRIMARY KEY (`purchase_item_id`),
  KEY `index2` (`purchase_id`),
  KEY `index3` (`sku`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='采购单所包含的产品' AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `pc_purchase_status_history`
--

CREATE TABLE IF NOT EXISTS `pc_purchase_status_history` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `purchase_id` varchar(45) NOT NULL DEFAULT '' COMMENT '采购单号',
  `status` smallint(6) NOT NULL DEFAULT '0' COMMENT '采购单状态',
  `update_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '修改时间',
  `capture_user_id` smallint(6) NOT NULL DEFAULT '0' COMMENT '修改者id',
  PRIMARY KEY (`id`),
  KEY `purchaseid` (`purchase_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='采购单状态变化历史' AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `pc_purchase_suggestion`
--

CREATE TABLE IF NOT EXISTS `pc_purchase_suggestion` (
  `sku` varchar(255) NOT NULL,
  `pending_purchase_ship_qty` int(11) NOT NULL DEFAULT '0' COMMENT '待采购发货数量',
  `pending_stock_qty` int(11) NOT NULL DEFAULT '0' COMMENT '建议备货数量',
  `create_time` timestamp NULL DEFAULT NULL COMMENT '生成时间',
  PRIMARY KEY (`sku`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='采购建议';

-- --------------------------------------------------------

--
-- Table structure for table `pc_shipping_mode`
--

CREATE TABLE IF NOT EXISTS `pc_shipping_mode` (
  `shipping_id` int(11) NOT NULL AUTO_INCREMENT COMMENT '采购运送方式编码',
  `shipping_name` varchar(100) NOT NULL DEFAULT '' COMMENT '采购运送方式',
  `comment` varchar(255) NOT NULL DEFAULT '' COMMENT '备注',
  `addi_info` varchar(255) NOT NULL DEFAULT '' COMMENT '额外信息json格式',
  `create_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '创建时间',
  `capture_user` smallint(5) unsigned NOT NULL COMMENT '最后录入操作员',
  PRIMARY KEY (`shipping_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COMMENT='采购递送方式' AUTO_INCREMENT=6 ;

--
-- Dumping data for table `pc_shipping_mode`
--

INSERT INTO `pc_shipping_mode` (`shipping_id`, `shipping_name`, `comment`, `addi_info`, `create_time`, `capture_user`) VALUES
(0, '(未定义)', '', '', '2014-09-23 05:37:58', 0),
(1, '自提', '', '', '2014-01-23 05:18:11', 0),
(2, '顺丰', '', '', '2014-01-23 05:18:11', 0),
(3, '圆通', '', '', '2014-01-23 05:18:53', 1),
(4, 'ems', '', '', '2014-01-23 05:19:44', 1),
(5, '申通', '', '', '2014-05-15 03:30:54', 0);

-- --------------------------------------------------------

--
-- Table structure for table `pd_attributes`
--

CREATE TABLE IF NOT EXISTS `pd_attributes` (
  `attribute_id` int(11) NOT NULL AUTO_INCREMENT COMMENT '属性id',
  `name` varchar(45) NOT NULL DEFAULT '' COMMENT '属性名称',
  `use_count` smallint(5) unsigned NOT NULL,
  `values` varchar(255) NOT NULL DEFAULT '' COMMENT '可能的值，json格式记录前20个常用值',
  PRIMARY KEY (`attribute_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='记录自定义产品属性以及该属性的 top 20常用值' AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `pd_brand`
--

CREATE TABLE IF NOT EXISTS `pd_brand` (
  `brand_id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL DEFAULT '',
  `addi_info` varchar(255) NOT NULL DEFAULT '' COMMENT '额外的附加信息，使用json格式记录',
  `comment` varchar(255) NOT NULL DEFAULT '' COMMENT '备注',
  `capture_user_id` tinyint(3) unsigned DEFAULT NULL COMMENT 'Capture User ID',
  `create_time` timestamp NULL DEFAULT NULL COMMENT '创建时间',
  `update_time` timestamp NULL DEFAULT NULL COMMENT '修改时间 ',
  PRIMARY KEY (`brand_id`),
  KEY `index2` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='产品的品牌信息' AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `pd_category`
--

CREATE TABLE IF NOT EXISTS `pd_category` (
  `category_id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL DEFAULT '' COMMENT '目录名称',
  `parent_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '父级目录ID',
  `level` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '目录级别根目录=1 级，后续的是2级类推',
  `has_children` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否有下级目录',
  `addi_info` varchar(255) NOT NULL DEFAULT '' COMMENT '额外信息json格式\n\n额外的附加信息，使用json格式记录',
  `comment` varchar(255) NOT NULL DEFAULT '' COMMENT '备注',
  `capture_user_id` tinyint(3) unsigned DEFAULT NULL COMMENT 'Capture User ID',
  `create_time` timestamp NULL DEFAULT NULL COMMENT '创建时间',
  `update_time` timestamp NULL DEFAULT NULL COMMENT '修改时间 ',
  PRIMARY KEY (`category_id`),
  KEY `index2` (`name`),
  KEY `index3` (`parent_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='分类目录' AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `pd_photo`
--

CREATE TABLE IF NOT EXISTS `pd_photo` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sku` varchar(255) NOT NULL COMMENT '产品的sku',
  `priority` smallint(6) NOT NULL DEFAULT '0',
  `photo_scale` char(2) NOT NULL COMMENT '图片规模：\nTN：Thumbnail\nSM：Small\nLG：Large\nOR：Original',
  `file_name` varchar(255) NOT NULL DEFAULT '' COMMENT '上传的图片文件名',
  `photo_url` varchar(455) NOT NULL DEFAULT '' COMMENT '图片存储路径或者文件名',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='产品图片信息' AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `pd_product`
--

CREATE TABLE IF NOT EXISTS `pd_product` (
  `sku` varchar(255) NOT NULL COMMENT 'SKU 别名',
  `is_has_alias` char(1) NOT NULL DEFAULT '' COMMENT 'SKU 别名\n是否有Alias SKU\n“Y”:yes\n“N”:No',
  `name` varchar(255) NOT NULL DEFAULT '',
  `type` char(1) NOT NULL DEFAULT '' COMMENT '产品类型\n\n“S”-普通\n“C”-变参\n“B”-捆绑',
  `status` char(2) NOT NULL DEFAULT '' COMMENT '“OS”:on sale\n“RN”:running out\n“DR”:dropped\n“AC”：archived\n“RS”:re-onsale',
  `prod_name_ch` varchar(255) NOT NULL DEFAULT '' COMMENT '产品中文名称',
  `prod_name_en` varchar(255) NOT NULL DEFAULT '' COMMENT '产品英文名称',
  `declaration_ch` varchar(100) NOT NULL DEFAULT '' COMMENT '中文报关名称',
  `declaration_en` varchar(100) NOT NULL DEFAULT '' COMMENT '英文报关名称',
  `declaration_value_currency` varchar(3) DEFAULT 'USD' COMMENT '海关申报价值 货币\n默认USD',
  `declaration_value` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '海关申报价值 金额',
  `declaration_code` varchar(50) DEFAULT NULL COMMENT '报关编码',
  `battery` varchar(1) NOT NULL DEFAULT 'N' COMMENT '产品是否有锂电池：Y/N',
  `category_id` tinyint(3) unsigned DEFAULT NULL COMMENT '所属目录编号',
  `brand_id` smallint(5) unsigned NOT NULL COMMENT '品牌id',
  `is_has_tag` char(1) NOT NULL DEFAULT '' COMMENT '是否有标签\n\n“Y”:yes\n“N”:No',
  `purchase_by` int(11) unsigned NOT NULL COMMENT '默认采购员id',
  `purchase_link` text COMMENT '采购链接',
  `prod_weight` smallint(5) unsigned NOT NULL DEFAULT '0' COMMENT '重量(克)',
  `prod_width` decimal(8,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '宽(cm)',
  `prod_length` decimal(8,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '长(cm)',
  `prod_height` decimal(8,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '高(cm)',
  `other_attributes` varchar(255) NOT NULL DEFAULT '' COMMENT '额外属性集\n\nsample data structure:\n“size:L color:red”',
  `photo_primary` varchar(455) NOT NULL DEFAULT '' COMMENT '主图片缩略图 file name',
  `supplier_id` int(11) NOT NULL DEFAULT '0' COMMENT '首选供应商编码是冗余，便于查询(已废除)',
  `purchase_price` decimal(10,2) NULL DEFAULT NULL COMMENT '供应商采购价，便于统计利润',
  `check_standard` text COMMENT '质检标准',
  `comment` text COMMENT '备注',
  `capture_user_id` int(10) unsigned DEFAULT NULL COMMENT 'Capture User ID',
  `create_time` timestamp NULL DEFAULT NULL COMMENT '创建时间',
  `update_time` timestamp NULL DEFAULT NULL COMMENT '修改时间 ',
  `total_stockage` int(11) NOT NULL DEFAULT '0' COMMENT '所有仓库存总存量',
  `pending_ship_qty` int(11) NOT NULL DEFAULT '0' COMMENT '总待发货数量',
  `create_source` varchar(100) DEFAULT NULL COMMENT '商品来源网站（DSP=分销,ebay,amz=亚马逊）',
  `product_id` int(11) NOT NULL AUTO_INCREMENT COMMENT '商品唯一码',
  `additional_cost` decimal(8,2) NULL DEFAULT NULL COMMENT '其他成本',
  `addi_info` text NULL DEFAULT NULL COMMENT '额外信息',
  `class_id` int(8) NOT NULL default 0 COMMENT '分类Id',
  PRIMARY KEY (`sku`),
  UNIQUE KEY `product_id` (`product_id`),
  KEY `category` (`category_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='产品基础信息' AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `pd_product_aliases`
--

CREATE TABLE IF NOT EXISTS `pd_product_aliases` (
  `product_alias_id` int(11) NOT NULL AUTO_INCREMENT COMMENT '记录编号',
  `sku` varchar(255) NOT NULL DEFAULT '',
  `alias_sku` varchar(255) NOT NULL DEFAULT '' COMMENT '别名的SKU',
  `pack` int(11) DEFAULT '1' COMMENT 'alias 代表每次卖出的数量（x,例如2个装，10个装）',
  `forsite` varchar(50) DEFAULT NULL COMMENT '该alias 专门属于某个site的使用',
  `platform` VARCHAR(50)  NOT NULL DEFAULT '' COMMENT '平台',
  `selleruserid` VARCHAR(50) NOT NULL DEFAULT '' COMMENT '店铺id',
  `comment` text NOT NULL COMMENT '备注',
  PRIMARY KEY (`product_alias_id`),
  UNIQUE KEY `alias_platform` (`alias_sku`, `platform`, `selleruserid`),
  KEY `index3` (`alias_sku`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='产品和别名的对应信息' AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `pd_product_bundle_relationship`
--

CREATE TABLE IF NOT EXISTS `pd_product_bundle_relationship` (
  `id` int(50) NOT NULL AUTO_INCREMENT COMMENT '自增ID',
  `bdsku` varchar(250) NOT NULL COMMENT '捆绑产品sku',
  `assku` varchar(250) NOT NULL COMMENT '捆绑产品的子产品sku',
  `qty` int(50) NOT NULL COMMENT '套餐中的数量',
  `create_date` datetime NOT NULL COMMENT '创建时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `pd_product_config_relationship`
--
CREATE TABLE IF NOT EXISTS `pd_product_config_relationship` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `cfsku` varchar(255) NOT NULL COMMENT '变参父产品SKU',
  `assku` varchar(255) NOT NULL COMMENT '变参子产品SKU',
  `config_field_ids` text NOT NULL COMMENT '这个变参产品和子产品之间的 变参参数id，用逗号隔开多个',
  `create_date` datetime DEFAULT NULL COMMENT '这个记录的创建日期',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='变参产品的父产品和子产品的关系' AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `pd_product_field`
--

CREATE TABLE IF NOT EXISTS `pd_product_field` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `field_name` varchar(245) NOT NULL COMMENT '属性中文名称',
  `field_name_eng` varchar(245) NOT NULL COMMENT '属性英文名称',
  `field_name_frc` varchar(245) NOT NULL COMMENT '属性法文名称',
  `field_name_ger` varchar(245) NOT NULL COMMENT '属性德文名称',
  `use_freq` int(11) DEFAULT '0' COMMENT '使用频率（次数）',
  PRIMARY KEY (`id`),
  UNIQUE KEY `index2` (`field_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='产品属性用到的field自定义' AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `pd_product_field_value`
--

CREATE TABLE IF NOT EXISTS `pd_product_field_value` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '属性值的ID',
  `field_id` int(11) NOT NULL DEFAULT '0' COMMENT '属性ID',
  `value` varchar(245) NOT NULL COMMENT '属性值',
  `use_freq` int(11) NOT NULL DEFAULT '0' COMMENT '使用频率（次数）',
  PRIMARY KEY (`id`),
  KEY `index2` (`field_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='产品自定义属性的属性可能值' AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `pd_product_suppliers`
--

CREATE TABLE IF NOT EXISTS `pd_product_suppliers` (
  `product_supplier_id` int(11) NOT NULL AUTO_INCREMENT,
  `sku` varchar(250) NOT NULL DEFAULT '',
  `supplier_id` int(10) unsigned DEFAULT NULL COMMENT '供应商编码',
  `priority` tinyint(3) unsigned DEFAULT NULL COMMENT '优先级\n\n0:首选, 1-4 备选',
  `purchase_price` decimal(10,2) DEFAULT NULL COMMENT '供应商采购价格',
  `purchase_link` VARCHAR(255) NULL DEFAULT NULL COMMENT '采购链接',
  PRIMARY KEY (`product_supplier_id`),
  UNIQUE KEY `index2` (`sku`,`supplier_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='供应商和产品的对应信息' AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `pd_product_tags`
--

CREATE TABLE IF NOT EXISTS `pd_product_tags` (
  `product_tag_id` int(11) NOT NULL AUTO_INCREMENT,
  `sku` varchar(250) NOT NULL DEFAULT '',
  `tag_id` int(11) unsigned DEFAULT NULL COMMENT '标签编号',
  PRIMARY KEY (`product_tag_id`),
  UNIQUE KEY `index2` (`sku`,`product_tag_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='产品和标签的对应信息' AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `pd_supplier`
--

CREATE TABLE IF NOT EXISTS `pd_supplier` (
  `supplier_id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(30) NOT NULL DEFAULT '',
  `address_nation` char(2) NOT NULL DEFAULT '' COMMENT 'e.g. CN,UK',
  `address_state` varchar(100) NOT NULL DEFAULT '' COMMENT 'e.g. 广东省',
  `address_city` varchar(100) NOT NULL DEFAULT '' COMMENT 'e.g. 中山市',
  `address_street` varchar(255) NOT NULL DEFAULT '',
  `url` text,
  `post_code` varchar(45) NOT NULL DEFAULT '' COMMENT '邮编',
  `phone_number` varchar(100) NOT NULL DEFAULT '' COMMENT '固话号码',
  `fax_number` varchar(100) NOT NULL DEFAULT '',
  `contact_name` varchar(45) NOT NULL DEFAULT '' COMMENT '联系人名字',
  `mobile_number` varchar(100) NOT NULL DEFAULT '',
  `qq` varchar(100) NOT NULL DEFAULT '',
  `ali_wanwan` varchar(100) NOT NULL DEFAULT '',
  `msn` varchar(100) NOT NULL DEFAULT '',
  `email` varchar(100) NOT NULL DEFAULT '' COMMENT 'email address',
  `status` tinyint(1) unsigned DEFAULT NULL COMMENT '1:active\n2:inactive',
  `account_settle_mode` tinyint(3) unsigned DEFAULT NULL COMMENT '结算方式\n\n1-月结\n2-付款后发货\n3-到货后付款',
  `payment_mode` varchar(100) NOT NULL DEFAULT '' COMMENT '支付方式\n\n例如：\n工商银行\n支付宝\npaypal',
  `payment_account` varchar(100) NOT NULL DEFAULT '' COMMENT '支付账号\ne.g. 9011564878564521',
  `comment` varchar(255) NOT NULL DEFAULT '' COMMENT '备注',
  `capture_user_id` tinyint(3) unsigned DEFAULT NULL COMMENT 'Capture User ID',
  `create_time` timestamp NULL DEFAULT NULL COMMENT '创建时间',
  `update_time` timestamp NULL DEFAULT NULL COMMENT '修改时间 ',
  `is_disable` tinyint(1) NOT NULL DEFAULT '0',
  `al1688_user_id` int(11) NULL DEFAULT 0 COMMENT '1688店铺id',
  `al1688_company_name` varchar(255) NULL DEFAULT '' COMMENT '1688店铺名称',
  `al1688_url` varchar(255) NULL DEFAULT '' COMMENT '1688店铺链接',
  PRIMARY KEY (`supplier_id`),
  KEY `index2` (`name`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COMMENT='供应商基础信息' AUTO_INCREMENT=2 ;

--
-- Dumping data for table `pd_supplier`
--

INSERT INTO `pd_supplier` (`supplier_id`, `name`, `address_nation`, `address_state`, `address_city`, `address_street`, `post_code`, `phone_number`, `fax_number`, `contact_name`, `mobile_number`, `qq`, `ali_wanwan`, `msn`, `email`, `status`, `account_settle_mode`, `payment_mode`, `payment_account`, `comment`, `capture_user_id`, `create_time`, `update_time`, `is_disable`) VALUES
(1, '(无)', '', '', '', '', '', '', '', '', '', '', '', '', '', 1, NULL, '', '', '', NULL, NULL, NULL, 0);

-- --------------------------------------------------------

--
-- Table structure for table `pd_tag`
--

CREATE TABLE IF NOT EXISTS `pd_tag` (
  `tag_id` int(11) NOT NULL AUTO_INCREMENT,
  `tag_name` varchar(100) NOT NULL DEFAULT '',
  PRIMARY KEY (`tag_id`),
  KEY `index2` (`tag_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='标签定义' AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `queue_shippedorder_errors`
--

CREATE TABLE IF NOT EXISTS `queue_shippedorder_errors` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `order_id` int(10) NOT NULL COMMENT '订单号',
  `osid` int(10) NOT NULL COMMENT 'od_order_shipped主键',
  `order_source` varchar(50) DEFAULT '' COMMENT '订单来源  ebay,amazon,aliexpress,custom',
  `order_source_order_id` varchar(50) DEFAULT '' COMMENT '订单来源  的订单id',
  `selleruserid` varchar(80) NOT NULL COMMENT '卖家账号',
  `tracking_number` varchar(50) NOT NULL DEFAULT '' COMMENT '发货单号',
  `tracking_link` varchar(100) NOT NULL DEFAULT '' COMMENT '跟踪号查询网址',
  `shipping_method_code` varchar(50) NOT NULL DEFAULT '' COMMENT '物流方式code',
  `status` tinyint(2) NOT NULL DEFAULT '0' COMMENT '订单标记发货状态，0:未处理，1:成功，2:失败',
  `result` varchar(20) DEFAULT NULL COMMENT '执行成功标记',
  `errors` varchar(255) NOT NULL DEFAULT '' COMMENT '返回错误信息',
  `created` int(10) DEFAULT NULL,
  `updated` int(10) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `sys_address`
--

CREATE TABLE IF NOT EXISTS `sys_address` (
  `id` int(5) unsigned NOT NULL AUTO_INCREMENT,
  `address_name` varchar(100) NOT NULL,
  `type` varchar(20) NOT NULL,
  `country_code` varchar(10) NOT NULL,
  `country` varchar(100) NOT NULL,
  `country_en` varchar(100) NOT NULL,
  `province_code` varchar(10) NOT NULL,
  `province` varchar(50) NOT NULL,
  `province_en` varchar(50) NOT NULL,
  `city` varchar(50) NOT NULL,
  `city_en` varchar(50) NOT NULL,
  `district` varchar(50) DEFAULT NULL,
  `district_en` varchar(50) DEFAULT NULL,
  `county` varchar(50) DEFAULT NULL,
  `county_en` varchar(50) DEFAULT NULL,
  `address` varchar(225) DEFAULT NULL,
  `address_en` varchar(225) DEFAULT NULL,
  `zip_code` varchar(20) DEFAULT NULL,
  `company` varchar(100) DEFAULT NULL,
  `company_en` varchar(100) DEFAULT NULL,
  `connect` varchar(100) NOT NULL,
  `connect_en` varchar(100) NOT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `mobile` varchar(50) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `fax` varchar(50) DEFAULT NULL,
  `carete_time` int(11) DEFAULT NULL,
  `update_time` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `sys_carrier_account`
--

CREATE TABLE IF NOT EXISTS `sys_carrier_account` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT 'id',
  `carrier_code` varchar(50) NOT NULL COMMENT '物流商代码',
  `carrier_name` varchar(100) NOT NULL COMMENT '物流商名',
  `carrier_type` tinyint(1) unsigned NOT NULL COMMENT '0:国内物流商  1:海外物流商',
  `api_params` text COMMENT '物流商认证参数',
  `create_time` int(11) unsigned DEFAULT '0' COMMENT '创建时间',
  `update_time` int(11) unsigned DEFAULT '0' COMMENT '更新时间',
  `user_id` int(11) unsigned NOT NULL COMMENT '用户id',
  `is_used` tinyint(1) DEFAULT '0' COMMENT '是否启用 0 不启用 1 启用',
  `address` text COMMENT '发货地址,揽货地址,退货地址 数组',
  `warehouse` text,
  `is_default` tinyint(1) NOT NULL DEFAULT '0' COMMENT '0:不为默认 1:设置为默认',
  `is_del` tinyint(1) NOT NULL DEFAULT '0' COMMENT '0:未删除 1:已删除',
  `warehouse_id` int(11) DEFAULT '-1' COMMENT '海外仓用于记录属于哪个仓库ID 默认值为-1是因为仓库那边存在为0的仓库',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='物流商帐号表' AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `sys_carrier_custom`
--

CREATE TABLE IF NOT EXISTS `sys_carrier_custom` (
  `carrier_code` int(5) NOT NULL AUTO_INCREMENT,
  `carrier_name` varchar(100) NOT NULL COMMENT '自定义物流商名称',
  `carrier_type` tinyint(1) DEFAULT '0' COMMENT '自定义物流商类型 0:无数据交互 1:Excel导数据',
  `address_list` varchar(200) DEFAULT NULL COMMENT '地址列表',
  `create_time` int(11) DEFAULT NULL,
  `update_time` int(11) DEFAULT NULL,
  `is_used` tinyint(1) NOT NULL DEFAULT '0' COMMENT '0:未启用 1:已启用',
  `excel_mode` varchar(50) DEFAULT '' COMMENT 'Excel导出模式',
  `excel_format` text COMMENT 'Excel导出格式',
  `warehouse_id` int(11) default -1 COMMENT '用于记录该自定义物流关联的仓库ID',
  PRIMARY KEY (`carrier_code`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `sys_invoke_jrn`
--

CREATE TABLE IF NOT EXISTS `sys_invoke_jrn` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'journal id',
  `create_time` datetime DEFAULT NULL,
  `process_id` bigint(20) NOT NULL DEFAULT '0' COMMENT 'process id, if the invoke is by a same php thread. they will share a same job id\nSo that we can know the serial func invokings are for a same process / user operation',
  `module` enum('Amazon','Catalog','Customer','Delivery','Finance','Inventory','Order','Permission','Platform','Purchase','Report','Ticket') NOT NULL COMMENT 'module name,\nsupported values are:\n''Catalog'',''Customer'',''Delivery'',''Finance'',''Inventory'',''Order'',''Permission'',''Platform'',''Purchase'',''Report'',''Ticket''',
  `class` varchar(145) NOT NULL DEFAULT '' COMMENT 'class being called',
  `function` varchar(145) NOT NULL DEFAULT '' COMMENT 'the function name called?',
  `param_1` text NOT NULL COMMENT 'parameters being called, in Jason format if it is passed as array',
  `param_2` text NOT NULL COMMENT 'parameters being called, in Jason format if it is passed as array',
  `param_3` text NOT NULL COMMENT 'parameters being called, in Jason format if it is passed as array',
  `param_4` text NOT NULL COMMENT 'parameters being called, in Jason format if it is passed as array',
  `param_5` text NOT NULL COMMENT 'parameters being called, in Jason format if it is passed as array',
  `param_6` text NOT NULL COMMENT 'parameters being called, in Jason format if it is passed as array',
  `param_7` text NOT NULL COMMENT 'parameters being called, in Jason format if it is passed as array',
  `param_8` text NOT NULL COMMENT 'parameters being called, in Jason format if it is passed as array',
  `param_9` text NOT NULL COMMENT 'parameters being called, in Jason format if it is passed as array',
  `param_10` text NOT NULL COMMENT 'parameters being called, in Jason format if it is passed as array',
  `return_code` text NOT NULL,
  PRIMARY KEY (`id`),
  KEY `ind1` (`process_id`),
  KEY `ind2` (`function`),
  KEY `index4` (`create_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `sys_log`
--

CREATE TABLE IF NOT EXISTS `sys_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `create_time` datetime DEFAULT NULL COMMENT 'log 创建的时间',
  `job_no` varchar(145) NOT NULL DEFAULT '' COMMENT 'job 或者 batch id',
  `job_type` enum('Batch','Online','Background') NOT NULL COMMENT 'log 的产生的job 的类型\nBatch，Background，Online\n',
  `log_type` enum('Info','Error','Debug','Trace') NOT NULL COMMENT 'log 的级别，\n''Info'',''Error'',''Debug'',''Trace''',
  `module` enum('Catalog','Customer','Delivery','Finance','Inventory','Order','Permission','Platform','Purchase','Report','Ticket','Amazon') NOT NULL COMMENT 'module name,\nsupported values are:\n''Catalog'',''Customer'',''Delivery'',''Finance'',''Inventory'',''Order'',''Permission'',''Platform'',''Purchase'',''Report'',''Ticket''',
  `class` varchar(45) NOT NULL DEFAULT '' COMMENT 'class name',
  `function` varchar(45) NOT NULL DEFAULT '' COMMENT 'function name',
  `tag` varchar(45) NOT NULL DEFAULT '' COMMENT 'tag name',
  `remark` varchar(255) NOT NULL DEFAULT '' COMMENT 'remark,message of logged detail',
  PRIMARY KEY (`id`),
  KEY `11` (`create_time`),
  KEY `2` (`job_no`),
  KEY `3` (`module`,`class`,`function`),
  KEY `index5` (`tag`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='系统Online操作，batch job运行，background job 运行的log 写入' AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `sys_relationship`
--

CREATE TABLE IF NOT EXISTS `sys_relationship` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `entity_1_type` enum('Purchase_order','Purchase_arrival','Finance_transaction','Stock_change','Purchase_refund','Order','Order_refund','Order_invoice') NOT NULL COMMENT '关系中的第一个数据实体类型，可选择以下值\n''Purchase_order'',''Purchase_arrival'',''Finance_transaction'',''Stock_change'',''Purchase_refund'',''Order'',''Order_refund'',''Order_invoice''',
  `entity_1_id` varchar(145) NOT NULL COMMENT '关系中的第一个数据实体 key id',
  `entity_2_type` enum('Purchase_order','Purchase_arrival','Finance_transaction','Stock_change','Purchase_refund','Order','Order_refund','Order_invoice') NOT NULL COMMENT '关系中的第2个数据实体类型',
  `entity_2_id` varchar(145) NOT NULL COMMENT '关系中的第2个数据实体 key id',
  PRIMARY KEY (`id`),
  KEY `index2` (`entity_1_id`,`entity_1_type`),
  KEY `index3` (`entity_2_id`,`entity_2_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `sys_set`
--

CREATE TABLE IF NOT EXISTS `sys_set` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `set_key` varchar(50) NOT NULL DEFAULT '' COMMENT '配置名称key',
  `set_value` varchar(255) NOT NULL DEFAULT '' COMMENT '配置名称',
  `is_active` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '是否启用0:否 1：是',
  PRIMARY KEY (`id`),
  UNIQUE KEY `set_key` (`set_key`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COMMENT='系统设置表' AUTO_INCREMENT=4 ;

--
-- Dumping data for table `sys_set`
--

INSERT INTO `sys_set` (`id`, `set_key`, `set_value`, `is_active`) VALUES
(2, 'autoShipOrder', '自动标记发货', 1),
(3, 'autoReviseInventory', '自动补数量(卖多少补多少,无视真实库存)', 0);

-- --------------------------------------------------------

--
-- Table structure for table `sys_shipping_service`
--

CREATE TABLE IF NOT EXISTS `sys_shipping_service` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT 'id',
  `carrier_code` varchar(100) NOT NULL COMMENT '物流商代码',
  `carrier_params` text COMMENT '参数键值对',
  `ship_address` varchar(500) DEFAULT '' COMMENT '发货地址',
  `return_address` varchar(500) DEFAULT '' COMMENT '退货地址',
  `is_used` tinyint(1) unsigned NOT NULL COMMENT '是否使用 0 不启用 1 启用',
  `service_name` varchar(255) NOT NULL COMMENT '服务名称',
  `service_code` text COMMENT '平台服务代码',
  `auto_ship` tinyint(1) DEFAULT '0' COMMENT '自动发货 0 不启用 1 启用',
  `web` varchar(255) DEFAULT '' COMMENT '查询网址',
  `create_time` int(11) unsigned DEFAULT '0' COMMENT '创建时间',
  `update_time` int(11) unsigned DEFAULT '0' COMMENT '更新时间',
  `carrier_account_id` int(11) DEFAULT '0' COMMENT '物流商帐号id',
  `extra_carrier` varchar(20) DEFAULT '' COMMENT '只有ebay亚太平台需要填写 TNT FedEx Bpost(MINIPAK TRAKPAK) CNPOST',
  `carrier_name` varchar(100) DEFAULT NULL COMMENT '物流商名',
  `shipping_method_name` varchar(255) DEFAULT NULL COMMENT '物流商运输服务名',
  `shipping_method_code` varchar(100) DEFAULT NULL COMMENT '物流商运输服务代码',
  `third_party_code` varchar(100) DEFAULT NULL COMMENT '第三方仓库代码',
  `warehouse_name` varchar(100) DEFAULT NULL COMMENT '海外仓仓库名',
  `address` text COMMENT '地址信息',
  `is_custom` tinyint(1) unsigned DEFAULT '0' COMMENT '是否自定义物流服务 0 不是 1 是',
  `custom_template_print` varchar(255) DEFAULT NULL,
  `print_type` tinyint(1) NOT NULL DEFAULT '0' COMMENT '打印设置 0:API获取标签 1:小老板高仿标签 2:自定义标签',
  `print_params` text COMMENT '打印设置 参数',
  `transport_service_type` tinyint(1) NOT NULL DEFAULT '0' COMMENT '运输服务类型 0:经济 1:标准 2:特快',
  `aging` varchar(10) NOT NULL DEFAULT '' COMMENT '时效',
  `is_tracking_number` tinyint(1) NOT NULL DEFAULT '0' COMMENT '有无跟踪号 0:没有 1:有',
  `proprietary_warehouse` text COMMENT '自营仓库设置 数组',
  `declaration_max_value` decimal(10,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '最大报关价值',
  `declaration_max_currency` varchar(10) NOT NULL DEFAULT 'USD' COMMENT '最大报关币种',
  `declaration_max_weight` decimal(10,4) unsigned NOT NULL DEFAULT '0.0000' COMMENT '最高报关重量',
  `customer_number_config` text COMMENT '客户参考号配置 数组',
  `is_del` tinyint(1) NOT NULL DEFAULT '0' COMMENT '0:未删除 1:已删除',
  `common_address_id` int(11) NOT NULL DEFAULT '0' COMMENT '常用地址id 当为0时取默认',
  `is_copy` tinyint(1) NOT NULL DEFAULT '0' COMMENT '0:原始生成数据 1:复制生成数据',
  `tracking_upload_config` text default '' COMMENT '跟踪号上传模式,暂时只支持amazon/ebay这两个平台,其它平台需要调研才行',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='运输服务表' AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `sys_tracking_number`
--

CREATE TABLE IF NOT EXISTS `sys_tracking_number` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `shipping_service_id` int(10) NOT NULL COMMENT '运输服务id',
  `service_name` varchar(255) DEFAULT NULL COMMENT '运输服务名',
  `tracking_number` varchar(50) NOT NULL COMMENT '物流号',
  `is_used` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否被分配,使用状态',
  `order_id` int(11) DEFAULT NULL COMMENT '小老板订单号',
  `use_time` int(11) DEFAULT NULL COMMENT '分配时间 使用时间',
  `user_name` varchar(50) DEFAULT NULL COMMENT '创建人',
  `operator` varchar(50) DEFAULT NULL COMMENT '操作人',
  `create_time` int(11) DEFAULT NULL COMMENT '创建时间',
  `update_time` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `shipping_service_id` (`shipping_service_id`),
  KEY `is_used` (`is_used`),
  KEY `tracking_number` (`tracking_number`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `usertab`
--

CREATE TABLE IF NOT EXISTS `usertab` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `uid` int(6) NOT NULL COMMENT '创建或修改者uid',
  `tabname` varchar(128) NOT NULL COMMENT '标签名',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `user_app_info`
--

CREATE TABLE IF NOT EXISTS `user_app_info` (
  `id` smallint(6) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL COMMENT 'app名称',
  `key` varchar(50) NOT NULL COMMENT 'app的标示名---如 purchase',
  `is_active` char(1) NOT NULL COMMENT 'Y或者N。该app是否已经启用。Y表示启用',
  `install_time` datetime NOT NULL COMMENT '该app的安装时间',
  `update_time` datetime DEFAULT NULL COMMENT '状态更新时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='该用户安装了哪些app' AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `user_config`
--

CREATE TABLE IF NOT EXISTS `user_config` (
  `keyid` varchar(255) NOT NULL COMMENT '用户配置key',
  `value` text COMMENT '用户配置value',
  `type` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '类型',
  `create_time` int(11) unsigned NOT NULL DEFAULT '0',
  `update_time` int(11) unsigned NOT NULL DEFAULT '0',
  `description` varchar(255) NOT NULL COMMENT '数据描述',
  PRIMARY KEY (`keyid`),
  KEY `idx_type` (`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='用户表全局性的配置';

--
-- Dumping data for table `user_config`
--

INSERT INTO `user_config` (`keyid`, `value`, `type`, `create_time`, `update_time`, `description`) VALUES
('myaccounts_areacode', NULL, 0, 0, 0, '手机区号'),
('myaccounts_company', NULL, 0, 0, 0, '公司名'),
('myaccounts_mobile', NULL, 0, 0, 0, '用户手机号'),
('myaccounts_postcode', NULL, 0, 0, 0, '邮政编码'),
('myaccounts_telephone', NULL, 0, 0, 0, '电话号码'),
('telephone_ext_number', NULL, 0, 0, 0, '分机号'),
('user_city', NULL, 0, 0, 0, '用户所在市'),
('user_country', NULL, 0, 0, 0, '用户所在国家'),
('user_district', NULL, 0, 0, 0, '用户所在区县'),
('user_province', NULL, 0, 0, 0, '用户所在省'),
('user_street', NULL, 0, 0, 0, '街道地址');

-- --------------------------------------------------------

--
-- Table structure for table `ut_config_data`
--

CREATE TABLE IF NOT EXISTS `ut_config_data` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `path` varchar(255) NOT NULL COMMENT '命名规则 modulename/configname',
  `value` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=3 ;

-- --------------------------------------------------------

--
-- Table structure for table `ut_sys_invoke_jrn`
--

CREATE TABLE IF NOT EXISTS `ut_sys_invoke_jrn` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'journal id',
  `create_time` datetime DEFAULT NULL,
  `process_id` bigint(20) NOT NULL DEFAULT '0' COMMENT 'process id, if the invoke is by a same php thread. they will share a same job id\nSo that we can know the serial func invokings are for a same process / user operation',
  `module` enum('Amazon','Catalog','Customer','Delivery','Finance','Inventory','Order','Permission','Platform','Purchase','Report','Ticket') NOT NULL COMMENT 'module name,\nsupported values are:\n''Catalog'',''Customer'',''Delivery'',''Finance'',''Inventory'',''Order'',''Permission'',''Platform'',''Purchase'',''Report'',''Ticket''',
  `class` varchar(145) NOT NULL DEFAULT '' COMMENT 'class being called',
  `function` varchar(145) NOT NULL DEFAULT '' COMMENT 'the function name called?',
  `param_1` text NOT NULL COMMENT 'parameters being called, in Jason format if it is passed as array',
  `param_2` text NOT NULL COMMENT 'parameters being called, in Jason format if it is passed as array',
  `param_3` text NOT NULL COMMENT 'parameters being called, in Jason format if it is passed as array',
  `param_4` text NOT NULL COMMENT 'parameters being called, in Jason format if it is passed as array',
  `param_5` text NOT NULL COMMENT 'parameters being called, in Jason format if it is passed as array',
  `param_6` text NOT NULL COMMENT 'parameters being called, in Jason format if it is passed as array',
  `param_7` text NOT NULL COMMENT 'parameters being called, in Jason format if it is passed as array',
  `param_8` text NOT NULL COMMENT 'parameters being called, in Jason format if it is passed as array',
  `param_9` text NOT NULL COMMENT 'parameters being called, in Jason format if it is passed as array',
  `param_10` text NOT NULL COMMENT 'parameters being called, in Jason format if it is passed as array',
  `return_code` text NOT NULL,
  PRIMARY KEY (`id`),
  KEY `ind1` (`process_id`),
  KEY `ind2` (`function`),
  KEY `index4` (`create_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `ut_sys_log`
--

CREATE TABLE IF NOT EXISTS `ut_sys_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `create_time` datetime DEFAULT NULL COMMENT 'log 创建的时间',
  `level` enum('info','error','warning','trace') NOT NULL COMMENT 'log 的级别',
  `module` varchar(45) NOT NULL DEFAULT '' COMMENT 'module name,supported values are:''Catalog'',''Customer'',''Delivery'',''Finance'',''Inventory'',''Order'',''Permission'',''Platform'',''Purchase'',''Report'',''Ticket''',
  `class` varchar(45) NOT NULL DEFAULT '' COMMENT 'class name',
  `function` varchar(45) NOT NULL DEFAULT '' COMMENT 'function name',
  `remark` text NOT NULL COMMENT 'remark,message of logged detail',
  PRIMARY KEY (`id`),
  KEY `11` (`create_time`),
  KEY `3` (`module`,`class`,`function`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COMMENT='系统Online操作，batch job运行，background job 运行的log 写入' AUTO_INCREMENT=14 ;

-- --------------------------------------------------------

--
-- Table structure for table `ut_user_image`
--

CREATE TABLE IF NOT EXISTS `ut_user_image` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `origin_url` varchar(90) NOT NULL COMMENT '原图的amazon s3 url',
  `thumbnail_url` varchar(100) NOT NULL COMMENT '缩略图的 amazon s3 url',
  `amazon_key` varchar(60) NOT NULL COMMENT '原图在amazon s3上的唯一码。其实就是路径',
  `origin_size` int(11) NOT NULL COMMENT '原图的大小。以Byte为单位',
  `thumbnail_size` int(11) NOT NULL COMMENT '缩略图的大小。以Byte为单位',
  `create_time` datetime NOT NULL COMMENT '图片上传时间',
  `service` tinyint(1) NOT NULL DEFAULT '0' COMMENT '0是使用amazon s3上传的图片， 1为七牛',
  `memo` varchar(128) DEFAULT NULL COMMENT '图片使用memo',
  `original_name` varchar(255) DEFAULT NULL COMMENT '上传时的图片名',
  `original_width` int(11) NOT NULL DEFAULT '0' COMMENT '原图宽度',
  `original_height` int(11) NOT NULL DEFAULT '0' COMMENT '原图高度',
  `classification_id`  int(4) not NULL DEFAULT 1 COMMENT '图片类别' ,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;
-- --------------------------------------------------------


CREATE TABLE IF NOT EXISTS `ut_image_classification` (
  `ID` int(4) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL COMMENT '类别名称',
  `operation` varchar(100) NOT NULL DEFAULT '{"add":0,"alert":1,"del":1}' COMMENT '是否可以增修删',
  `parentID` int(11) NOT NULL DEFAULT 0 COMMENT '父节点',
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=2 ;


INSERT INTO `ut_image_classification` (`ID`, `name`, `operation`) VALUES (1, '未分类', '{"add":0,"alert":0,"del":0}');


--
-- Table structure for table `visit_log`
--

CREATE TABLE IF NOT EXISTS `visit_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `url_path` varchar(255) NOT NULL COMMENT '是指页面url，网站的域名就不需要提供。  如 ：/purchase/purchase/list',
  `visit_time` datetime NOT NULL COMMENT '访问时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='用户访问log' AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `wh_order_reserve_product`
--

CREATE TABLE IF NOT EXISTS `wh_order_reserve_product` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) unsigned zerofill NOT NULL COMMENT '订单号',
  `package_id` int(11) NOT NULL DEFAULT '0' COMMENT '包裹号',
  `warehouse_id` smallint(6) DEFAULT '0' COMMENT '仓库编号',
  `sku` varchar(250) NOT NULL COMMENT '产品sku',
  `reserved_qty` int(11) NOT NULL DEFAULT '0' COMMENT '预约仓库库存用来发货的数量',
  `reserved_qty_on_the_way` int(11) NOT NULL DEFAULT '0' COMMENT '预约仓库产品在途的\n用来到货后发货的数量',
  `reserve_time` timestamp NULL DEFAULT NULL COMMENT '预约的时间',
  PRIMARY KEY (`id`),
  KEY `index2` (`order_id`,`package_id`,`sku`),
  KEY `index3` (`sku`),
  KEY `index4` (`package_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='记录订单发货预约了sku 的情况' AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `wh_oversea_warehouse_stock`
--

CREATE TABLE IF NOT EXISTS `wh_oversea_warehouse_stock` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键',
  `oversea_warehouse_sku` varchar(255) NOT NULL DEFAULT '' COMMENT '海外仓sku',
  `sku` varchar(255) NOT NULL DEFAULT '' COMMENT '系统sku',
  `product_name` varchar(50) NOT NULL DEFAULT '' COMMENT '商品名称',
  `carrier_code` varchar(50) NOT NULL DEFAULT '' COMMENT '海外仓服务商code',
  `third_party_code` varchar(50) NOT NULL DEFAULT '' COMMENT '海外仓服务商仓库编号',
  `warehouse_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '系统仓库id',
  `qty_on_hand` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '在库库存',
  `qty_ordered` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '在途库存',
  `qty_reserved` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '冻结库存',
  `qty_time_out` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '超时库存',
  `specification` varchar(255) NOT NULL DEFAULT '' COMMENT '规格',
  `create_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '修改时间',
  PRIMARY KEY (`id`),
  KEY `idx_ows` (`oversea_warehouse_sku`),
  KEY `idx_sku` (`sku`),
  KEY `idx_wi` (`warehouse_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='海外仓库存表' AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `wh_product_stock`
--

CREATE TABLE IF NOT EXISTS `wh_product_stock` (
  `prod_stock_id` int(11) NOT NULL AUTO_INCREMENT,
  `warehouse_id` smallint(5) unsigned NOT NULL DEFAULT '0' COMMENT '仓库号',
  `sku` varchar(255) NOT NULL DEFAULT '',
  `location_grid` varchar(45) NOT NULL DEFAULT '' COMMENT '仓库货位/格子',
  `qty_in_stock` int(5) NOT NULL DEFAULT '0' COMMENT '在库数量',
  `qty_purchased_coming` int(8) unsigned NOT NULL DEFAULT '0' COMMENT '采购在途数量',
  `qty_ordered` int(8) unsigned NOT NULL DEFAULT '0' COMMENT '待发货',
  `qty_order_reserved` int(8) unsigned NOT NULL DEFAULT '0' COMMENT '已被订单预约',
  `average_price` decimal(10,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '历史采购成本',
  `total_purchased` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '历史采购总量',
  `safety_stock` SMALLINT(5) default 0 not null COMMENT '安全库存',
  `addi_info` varchar(255) NOT NULL DEFAULT '',
  `update_time` timestamp NULL DEFAULT NULL COMMENT '修改时间',
  PRIMARY KEY (`prod_stock_id`),
  UNIQUE KEY `warehouse_sku` (`warehouse_id`,`sku`),
  KEY `index3` (`sku`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='仓库的产品数量信息' AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `wh_stock_change`
--

CREATE TABLE IF NOT EXISTS `wh_stock_change` (
  `stock_change_id` varchar(255) NOT NULL COMMENT '出入库单号',
  `warehouse_id` smallint(5) unsigned DEFAULT '0' COMMENT '仓库号',
  `change_type` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '出库或者入库\n1：入库\n2：出库',
  `reason` smallint(5) unsigned NOT NULL DEFAULT '0' COMMENT '出入库原因--101：采购入库102：样品入库103：回收邮包104：赠品入库201：订单出库202：样品出库203：重发邮包204：赠品出库205：报废出库300：库存盘点301: 库存盘盈302: 库存盘亏',
  `comment` varchar(255) NOT NULL DEFAULT '' COMMENT '备注',
  `addi_info` varchar(255) NOT NULL DEFAULT '' COMMENT '额外的附加信息，使用json格式记录',
  `capture_user_id` smallint(5) unsigned DEFAULT '0' COMMENT 'Capture User ID',
  `create_time` timestamp NULL DEFAULT NULL COMMENT '创建时间',
  `update_time` timestamp NULL DEFAULT NULL COMMENT '修改时间 ',
  `source_id` int(11)  not null default 0  COMMENT '来源表Id',
  PRIMARY KEY (`stock_change_id`),
  KEY `index2` (`change_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='仓库的入库或者入库记录表';

-- --------------------------------------------------------

--
-- Table structure for table `wh_stock_change_detail`
--

CREATE TABLE IF NOT EXISTS `wh_stock_change_detail` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `stock_change_id` varchar(255) NOT NULL DEFAULT '' COMMENT '出入库单据编号',
  `sku` varchar(250) NOT NULL DEFAULT '',
  `qty` int(11) NOT NULL DEFAULT '0' COMMENT '数量',
  `prod_name` varchar(100) NOT NULL DEFAULT '' COMMENT '产品名称快照',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='仓库出入单的具体产品明细' AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `wh_stock_take`
--

CREATE TABLE IF NOT EXISTS `wh_stock_take` (
  `stock_take_id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '盘点单号',
  `warehouse_id` smallint(5) unsigned DEFAULT NULL COMMENT '仓库号',
  `number_of_sku` smallint(5) unsigned DEFAULT NULL COMMENT '产品品种数量',
  `comment` varchar(255) NOT NULL DEFAULT '' COMMENT '备注',
  `capture_user_id` smallint(5) unsigned DEFAULT NULL COMMENT 'Capture User ID',
  `create_time` timestamp NULL DEFAULT NULL COMMENT '创建时间',
  `update_time` timestamp NULL DEFAULT NULL COMMENT '修改时间 ',
  PRIMARY KEY (`stock_take_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='仓库的库存盘点表' AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `wh_stock_take_detail`
--

CREATE TABLE IF NOT EXISTS `wh_stock_take_detail` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `stock_take_id` int(11) NOT NULL DEFAULT '0' COMMENT '盘点单编号',
  `sku` varchar(40) NOT NULL DEFAULT '' COMMENT '产品编码SKU',
  `product_name` varchar(255) NOT NULL DEFAULT '' COMMENT '货品名称(快照)',
  `location_grid` varchar(45) NOT NULL DEFAULT '' COMMENT '仓库货位/格子',
  `qty_shall_be` int(11) NOT NULL DEFAULT '0' COMMENT '应有可用库存',
  `qty_actual` int(11) NOT NULL DEFAULT '0' COMMENT '实际盘点数',
  `qty_reported` int(11) NOT NULL DEFAULT '0' COMMENT '报损\n报溢\n\n正数是盘点报溢\n负数是盘点报损',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='盘点的具体产品SKU，数量，结果信息' AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `wh_warehouse`
--

CREATE TABLE IF NOT EXISTS `wh_warehouse` (
  `warehouse_id` smallint(5) unsigned NOT NULL AUTO_INCREMENT COMMENT '仓库号',
  `name` varchar(50) NOT NULL DEFAULT '' COMMENT '仓库名称',
  `is_active` char(1) NOT NULL DEFAULT '' COMMENT '状态可用\n“Y”:yes\n“N”:No',
  `address_nation` char(2) NOT NULL DEFAULT '' COMMENT 'e.g. CN,UK',
  `address_state` varchar(100) NOT NULL DEFAULT '' COMMENT 'e.g. 广东省',
  `address_city` varchar(100) NOT NULL DEFAULT '' COMMENT 'e.g. 中山市',
  `address_street` varchar(255) NOT NULL DEFAULT '',
  `address_postcode` varchar(45) NOT NULL DEFAULT '' COMMENT '邮编',
  `address_phone` varchar(45) NOT NULL DEFAULT '' COMMENT '电话',
  `comment` varchar(255) NOT NULL DEFAULT '' COMMENT '备注',
  `addi_info` varchar(255) NOT NULL DEFAULT '' COMMENT '额外的附加信息，使用json格式记录',
  `capture_user_id` int(11) unsigned DEFAULT NULL COMMENT 'Capture User ID',
  `create_time` timestamp NULL DEFAULT NULL COMMENT '创建时间',
  `update_time` timestamp NULL DEFAULT NULL COMMENT '修改时间',
  `is_oversea` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '0:普通仓库  1:海外仓',
  `address_params` text COMMENT '地址相关参数',
  `carrier_code` varchar(50) DEFAULT '' COMMENT '海外仓代码',
  `third_party_code` varchar(100) DEFAULT '' COMMENT '海外仓仓库代码',
  `oversea_type` int(11) DEFAULT '0' COMMENT '海外仓模式 0:API接口 1:excel导入',
  `excel_mode` varchar(50) DEFAULT '' COMMENT 'Excel导出模式',
  `excel_format` text COMMENT 'Excel导出格式',
  `is_zero_inventory` int DEFAULT 0 COMMENT '是否支持负库存出库 0:不支持 1:支持',
  PRIMARY KEY (`warehouse_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='仓库的基本信息' AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `wh_warehouse_cover_nation`
--

CREATE TABLE IF NOT EXISTS `wh_warehouse_cover_nation` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `nation` char(2) NOT NULL COMMENT '国家代号',
  `warehouse_id` int(11) NOT NULL COMMENT '仓库代号',
  `priority` tinyint(4) unsigned NOT NULL DEFAULT '200' COMMENT '数字大表示优先级高，100是中等，200最优先',
  PRIMARY KEY (`id`),
  UNIQUE KEY `id1` (`nation`,`warehouse_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='仓库能递送国家的关系表' AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `wh_warehouse_has_shipping_method`
--

CREATE TABLE IF NOT EXISTS `wh_warehouse_has_shipping_method` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '自增主键',
  `warehouse_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '仓库id',
  `carrier_code` varchar(50) NOT NULL DEFAULT '' COMMENT '物流商',
  `shipping_method_code` varchar(50) NOT NULL DEFAULT '' COMMENT '物流方式',
  `extra_info` text NOT NULL COMMENT '额外信息',
  `pre_weight` decimal(10,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '首重',
  `pre_weight_price` decimal(10,4) unsigned NOT NULL DEFAULT '0.0000' COMMENT '首重价',
  `add_weight` decimal(10,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '续重',
  `add_weight_price` decimal(10,4) unsigned NOT NULL DEFAULT '0.0000' COMMENT '续重价',
  `create_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '修改时间',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='仓库物理方式关系表' AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `wish_fanben`
--

CREATE TABLE IF NOT EXISTS `wish_fanben` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `brand` varchar(50) DEFAULT NULL COMMENT '品牌',
  `type` tinyint(4) NOT NULL DEFAULT '1' COMMENT '商品类型 1在线商品 2小老板刊登商品',
  `status` varchar(20) NOT NULL COMMENT '状态：error,editing, uploading, complete',
  `lb_status` int(11) NOT NULL DEFAULT '1' COMMENT '小老板刊登商品状态 1待发布 2平台审核中 3发布成功 4发布失败 5标记删除 6在线商品',
  `site_id` int(5) DEFAULT NULL,
  `parent_sku` varchar(50) NOT NULL DEFAULT '' COMMENT '如果是变参的，填入老爸SKU',
  `variance_count` int(11) NOT NULL DEFAULT '1' COMMENT '该范本包含的变参子产品数量',
  `name` varchar(255) NOT NULL COMMENT '产品名称',
  `tags` varchar(255) NOT NULL,
  `upc` varchar(50) DEFAULT NULL COMMENT 'UPC,EAN,barcode',
  `landing_page_url` text COMMENT '自营网店的URL',
  `internal_sku` varchar(50) DEFAULT '' COMMENT '小老板内部商品SKU',
  `msrp` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '原售价，被划掉的',
  `shipping_time` varchar(250) NOT NULL DEFAULT '' COMMENT 'just the estimated days,e.g. "3-7"',
  `main_image` text NOT NULL COMMENT '主图片，必填',
  `extra_image_1` text,
  `extra_image_2` text,
  `extra_image_3` text,
  `extra_image_4` text,
  `extra_image_5` text,
  `extra_image_6` text,
  `extra_image_7` text,
  `extra_image_8` text,
  `extra_image_9` text,
  `extra_image_10` text,
  `create_time` datetime DEFAULT NULL,
  `update_time` datetime DEFAULT NULL,
  `description` text NOT NULL,
  `capture_user_id` tinyint(4) NOT NULL COMMENT '操作者ID',
  `wish_product_id` varchar(50) DEFAULT '' COMMENT 'Wish 平台的product id，用于update一个product',
  `error_message` text COMMENT 'Wish返回的错误信息',
  `addinfo` varchar(255) NOT NULL DEFAULT '' COMMENT '备用信息，json格式',
  `price` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '商品价格',
  `inventory` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '商品库存',
  `number_saves` int(11) NOT NULL DEFAULT '0' COMMENT '商品WISH平台收藏数',
  `number_sold` int(11) NOT NULL DEFAULT '0' COMMENT '商品WISH平台销售数',
  `is_enable` tinyint(4) NOT NULL DEFAULT '1' COMMENT '变种商品是否存在下架商品 1不存在 2存在',
  `shipping` decimal(8,2) NOT NULL DEFAULT '0.00' COMMENT '运费美元',
  `matching_info` text DEFAULT NULL COMMENT '配对待确认信息',
  PRIMARY KEY (`id`),
  UNIQUE KEY `siteidandparentsku` (`site_id`,`parent_sku`),
  KEY `title` (`name`),
  KEY `sku` (`internal_sku`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `wish_fanben_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `wish_fanben_action` varchar(255) CHARACTER SET utf8 NOT NULL DEFAULT '' COMMENT '操作类型',
  `wish_fanben_info` text CHARACTER SET utf8 NOT NULL COMMENT '操作内容',
  `wish_fanben_return_info` text CHARACTER SET utf8 NOT NULL COMMENT '返回内容',
  `wish_fanben_status` int(11) NOT NULL DEFAULT '1' COMMENT '操作执行状态 1正常 2异常',
  `create_time` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '创建时间',
  `update_time` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;



-- --------------------------------------------------------

--
-- Table structure for table `wish_fanben_variance`
--

CREATE TABLE IF NOT EXISTS `wish_fanben_variance` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Variance id',
  `fanben_id` int(11) NOT NULL COMMENT '这个variance属于某个范本',
  `parent_sku` varchar(50) NOT NULL COMMENT '父产品sku',
  `sku` varchar(250) NOT NULL COMMENT '变参子产品sku',
  `sync_status` varchar(30) NOT NULL COMMENT '同步状态',
  `internal_sku` varchar(50) NOT NULL COMMENT 'Eagle系统的商品sku',
  `color` varchar(100) NOT NULL COMMENT 'wish平台的颜色',
  `size` varchar(100) NOT NULL COMMENT 'wish平台的size',
  `price` decimal(8,2) NOT NULL COMMENT 'wish平台美元售价',
  `shipping` decimal(8,2) NOT NULL COMMENT '运费美元',
  `inventory` int(11) NOT NULL COMMENT '库存量',
  `addinfo` text COMMENT '备用信息，json格式',
  `enable` char(1) NOT NULL DEFAULT 'Y' COMMENT '商品启用状态（Y=上架，N=下架）',
  `variance_product_id` varchar(50) NOT NULL COMMENT 'wish平台返回的product id',
  `image_url` varchar(255) NOT NULL DEFAULT '' COMMENT 'SKU专属图片地址',
  PRIMARY KEY (`id`),
  UNIQUE KEY `locateit` (`fanben_id`,`parent_sku`,`sku`),
  KEY `syncstatus` (`sync_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Wish刊登的变参子产品范本信息' AUTO_INCREMENT=1 ;



CREATE TABLE IF NOT EXISTS `queue_product_log` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `create_time` timestamp NULL DEFAULT NULL,
  `update_time` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `status` char(1) DEFAULT 'P',
  `total_product` int(11) DEFAULT NULL,
  `total_variance` int(11) DEFAULT NULL,
  `platform` varchar(50) NOT NULL,
  `operator` int(11) unsigned DEFAULT '0' COMMENT '0:系统  其他:uid',
  `shop` varchar(255) DEFAULT NULL COMMENT '卖家账号',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;


-- --------------------------------------------------------

--
-- Table structure for table `wish_order`
--

CREATE TABLE IF NOT EXISTS `wish_order` (
  `order_id` varchar(255) NOT NULL COMMENT '订单号',
  `order_time` datetime DEFAULT NULL COMMENT '创建时间',
  `order_total` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '总金额',
  `transaction_id` varchar(255) DEFAULT NULL COMMENT '交易号',
  `variant_id` varchar(255) DEFAULT NULL,
  `city` varchar(255) DEFAULT NULL COMMENT '城市',
  `country` varchar(255) DEFAULT NULL COMMENT '国家',
  `name` varchar(255) DEFAULT NULL COMMENT '姓名',
  `phone_number` varchar(255) DEFAULT NULL COMMENT '电话号码',
  `state` varchar(255) DEFAULT NULL COMMENT '州/省',
  `street_address1` text COMMENT '街道地址 1',
  `street_address2` text COMMENT '街道地址2',
  `zipcode` varchar(255) DEFAULT NULL COMMENT '邮编',
  `last_updated` datetime DEFAULT NULL COMMENT '最近更新时间',
  `shipping_cost` decimal(10,2) DEFAULT '0.00' COMMENT '运费成本',
  `shipping` decimal(10,2) DEFAULT '0.00' COMMENT '运费',
  `status` varchar(255) DEFAULT NULL COMMENT '状态',
  `buyer_id` varchar(50) DEFAULT NULL COMMENT 'buyerid 每个买家固定的',
  `shipping_provider` varchar(100) DEFAULT NULL COMMENT '物流商',
  `tracking_number` varchar(50) DEFAULT NULL COMMENT '物流号',
  PRIMARY KEY (`order_id`),
  KEY `buyer_id` (`buyer_id`),
  KEY `tracking_number` (`tracking_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='wish订单';

-- --------------------------------------------------------

--
-- Table structure for table `wish_order_detail`
--

CREATE TABLE IF NOT EXISTS `wish_order_detail` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '主键',
  `product_id` varchar(255) DEFAULT NULL COMMENT '商品编号',
  `quantity` int(11) DEFAULT NULL COMMENT '数量',
  `price` decimal(10,2) DEFAULT NULL COMMENT '价格',
  `cost` decimal(10,2) DEFAULT NULL COMMENT '成本',
  `shipping` decimal(10,2) DEFAULT NULL COMMENT '运费',
  `shipping_cost` decimal(10,2) DEFAULT NULL COMMENT '运费成本',
  `product_name` varchar(255) DEFAULT NULL COMMENT '商品名称',
  `product_image_url` text COMMENT '商品图片说明',
  `days_to_fulfill` int(11) DEFAULT NULL COMMENT '备货周期',
  `sku` varchar(255) DEFAULT NULL COMMENT 'sku',
  `size` varchar(255) DEFAULT NULL COMMENT '规格',
  `order_id` varchar(255) NOT NULL COMMENT '订单编号',
  PRIMARY KEY (`id`),
  UNIQUE KEY `order_sku` (`order_id`,`sku`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='wish订单明细表' AUTO_INCREMENT=1 ;


CREATE TABLE IF NOT EXISTS `aliexpress_listing` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '商品id',
  `productid` varchar(20) NOT NULL COMMENT '在线上id',
  `freight_template_id` varchar(20) DEFAULT NULL COMMENT '运费模板id',
  `owner_member_seq` varchar(20) DEFAULT NULL COMMENT '速卖通账号id号，还不知道有什么用',
  `subject` varchar(255) DEFAULT NULL COMMENT '商品标题',
  `photo_primary` varchar(255) DEFAULT NULL COMMENT '主图',
  `error_message` text COMMENT '错误信息',
  `imageurls` text COMMENT '橱窗图片多图',
  `selleruserid` varchar(20) DEFAULT NULL COMMENT '卖家账号',
  `ws_offline_date` int(11) DEFAULT NULL COMMENT '预计下架时间',
  `product_min_price` float(10,2) DEFAULT NULL COMMENT '最小售价',
  `ws_display` varchar(50) DEFAULT NULL COMMENT '下架原因',
  `product_max_price` float(10,2) DEFAULT NULL COMMENT '商品最高价',
  `gmt_modified` int(11) DEFAULT NULL COMMENT '商品最后修改时间',
  `gmt_create` int(11) DEFAULT NULL COMMENT '商品上架时间',
  `sku_stock` int(7) DEFAULT NULL COMMENT '商品在售数量',
  `created` int(11) DEFAULT NULL,
  `updated` int(11) DEFAULT NULL,
  `product_status` smallint(1) DEFAULT '0' COMMENT '0-保存未发布 1-onSelling-上架销售中 2-offline-下架 3-auditing-审核中 4-editingRequired-审核不通过',
  `edit_status` tinyint(1) DEFAULT '0' COMMENT '0-待发布  1-发布中 2-修改中 3-发布失败',
  PRIMARY KEY (`id`),
  KEY `productid` (`productid`),
  KEY `selleruserid` (`selleruserid`),
  KEY `ws_offline_date` (`ws_offline_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

INSERT INTO `cs_msg_template` (`id`, `name`, `subject`, `body`, `addi_info`) VALUES
(1, '已签收请求好评(预设1)', 'Your parcel (number [包裹物流号]) has arrived, which is shipped by [包裹递送物流商].', 'Dear [收件人名称],\r\n\r\nYour parcel (number [包裹物流号]) has arrived, which is shipped by [包裹递送物流商].\r\n\r\nShipping address:\r\n[收件人地址，包含城市].\r\n\r\nDo you encounter any problem while operating this product?\r\nPlease consult us if any issue.\r\n\r\nIf you find the product is good, would you please give us a positive evaluation?\r\nThe related order ID is [平台订单号].\r\n\r\nThanks and regards\r\nGood day.\r\n\r\nFor more details about your order and get more excellent products, please visit [买家查看包裹追踪及商品推荐链接]', '{"recom_prod":"Y","layout":"1","recom_prod_count":"4"}'),
(2, '启运通知英文 (预设1)', 'Your parcel has been shipped', 'Dear [收件人名称]\r\n\r\nThanks for your order [平台订单号]. \r\nPlease be advised that your parcel has been shipped.\r\nBy carrier [包裹递送物流商] , with delivery number [包裹物流号].\r\n\r\nCheck the status of your shipment NOW:\r\n[买家查看包裹追踪及商品推荐链接]\r\n\r\nIf you have any additional questions, please do not hesitate to contact us.\r\nThanks and regards\r\n', '{"recom_prod":"Y","layout":"1","recom_prod_count":"8"}');



CREATE TABLE IF NOT EXISTS `cdiscount_offer_list` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `best_shipping_charges` float DEFAULT NULL COMMENT '最佳运费',
  `comments` text,
  `creation_date` datetime DEFAULT NULL,
  `dea_tax` float DEFAULT NULL,
  `discount_list` text,
  `eco_tax` float DEFAULT NULL COMMENT '环境税',
  `integration_price` float DEFAULT NULL COMMENT '整合价格',
  `last_update_date` datetime DEFAULT NULL,
  `minimum_price_for_price_alignment` float DEFAULT NULL COMMENT '调整后的最低价',
  `offer_bench_mark` text,
  `offer_pool_list` text COMMENT 'json格式',
  `offer_state` varchar(100) DEFAULT NULL COMMENT 'Active/',
  `parent_product_id` varchar(100) DEFAULT NULL,
  `price` float DEFAULT NULL,
  `price_must_be_aligned` varchar(100) DEFAULT NULL COMMENT '/DontAlign',
  `product_condition` varchar(100) DEFAULT NULL COMMENT 'New/',
  `product_ean` varchar(100) DEFAULT NULL,
  `product_id` varchar(100) DEFAULT NULL,
  `product_packaging_unit` varchar(100) DEFAULT NULL,
  `product_packaging_unit_price` float DEFAULT NULL,
  `product_packaging_value` decimal(8,2) DEFAULT NULL,
  `seller_product_id` varchar(100) DEFAULT NULL,
  `shipping_information_list` text COMMENT 'json格式',
  `stock` decimal(8,2) NOT NULL DEFAULT '0.00',
  `striked_price` decimal(8,2) DEFAULT NULL COMMENT '最高价格?',
  `vat_rate` decimal(8,2) DEFAULT NULL COMMENT '增值税率',
  `name` text COMMENT '通过页面抓取获得的name信息',
  `img` text COMMENT '页面抓取到的photos,json格式',
  `description` text COMMENT '页面抓取到的产品描述',
  `sku` varchar(100) DEFAULT NULL,
  `brand` varchar(100) DEFAULT NULL,
  `is_bestseller` varchar(1) DEFAULT NULL COMMENT '自己是不是最佳卖家，Y/N',
  `bestseller_name` varchar(100) DEFAULT NULL COMMENT '最佳卖家名称',
  `bestseller_price` decimal(8,2) DEFAULT NULL COMMENT '最佳卖家此商品的售价',
  `seller_id` varchar(100) NOT NULL COMMENT '对应店铺登录名',
  `product_url` text COMMENT '商品在销售平台的链接',
  `last_15_days_sold` int(11) NOT NULL DEFAULT '0' COMMENT '最近15日售出数量，以当前日期的0:0:0为统计结束时间',
  `concerned_status` CHAR(1) NOT NULL DEFAULT 'N' COMMENT '关注状态：N：普通；I:忽略；F：关注；H：爆款',
  `terminator_active` VARCHAR(1) NULL DEFAULT NULL COMMENT '跟卖终结者是否生效',
  `matching_info` text DEFAULT NULL COMMENT '配对待确认信息',
  PRIMARY KEY (`id`),
  UNIQUE KEY `product_id` (`product_id`,`seller_id`),
  KEY `sku` (`sku`),
  KEY `product_mark` (`product_id`,`seller_product_id`),
  KEY `seller_id` (`seller_id`),
  KEY `concerned_status` (`concerned_status`),
  KEY `is_bestseller` (`is_bestseller`),
  KEY `terminator_active` (`terminator_active`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;
 
 
--
-- 表的结构 `cdiscount_order`
--
CREATE TABLE IF NOT EXISTS `cdiscount_order` (
  `ordernumber` varchar(50) NOT NULL DEFAULT '' COMMENT '订单号',
  `billing_address1` varchar(255) DEFAULT NULL,
  `billing_address2` varchar(255) DEFAULT NULL,
  `billing_building` varchar(255) DEFAULT NULL,
  `billing_transaction_id` varchar(255) DEFAULT NULL,
  `billing_city` varchar(255) DEFAULT NULL,
  `billing_civility` varchar(255) DEFAULT NULL,
  `billing_companyname` varchar(255) DEFAULT NULL,
  `billing_country` varchar(255) DEFAULT NULL,
  `billing_firstname` varchar(255) DEFAULT NULL,
  `billing_instructions` varchar(255) DEFAULT NULL,
  `billing_lastname` varchar(100) DEFAULT NULL,
  `billing_placename` varchar(100) DEFAULT NULL,
  `billing_street` varchar(255) DEFAULT NULL,
  `billing_zipcode` varchar(30) DEFAULT NULL,
  `creationdate` datetime DEFAULT NULL,
  `customer_customerid` varchar(100) DEFAULT NULL,
  `customer_mobilephone` varchar(100) DEFAULT NULL,
  `customer_phone` varchar(100) DEFAULT NULL,
  `customer_civility` varchar(100) DEFAULT NULL,
  `customer_firstname` varchar(100) DEFAULT NULL,
  `customer_lastname` varchar(100) DEFAULT NULL,
  `hasclaims` varchar(100) DEFAULT NULL,
  `initialtotalamount` float(10,2) DEFAULT '0.00' COMMENT '初始总金额',
  `initialtotalshippingchargesamount` float(10,2) DEFAULT '0.00',
  `lastupdateddate` datetime DEFAULT NULL,
  `modifieddate` datetime DEFAULT NULL,
  `offer` varchar(100) DEFAULT NULL,
  `orderstate` varchar(100) DEFAULT NULL,
  `shippedtotalamount` float(10,2) DEFAULT '0.00',
  `shippedTotalShippingCharges` float(10,2) DEFAULT '0.00',
  `shipping_address1` varchar(255) DEFAULT NULL,
  `shipping_address2` varchar(255) DEFAULT NULL,
  `shipping_apartmentnumber` varchar(255) DEFAULT NULL,
  `shipping_building` varchar(255) DEFAULT NULL,
  `shipping_city` varchar(255) DEFAULT NULL,
  `shipping_civility` varchar(255) DEFAULT NULL,
  `shipping_companyname` varchar(255) DEFAULT NULL,
  `shipping_country` varchar(255) DEFAULT NULL,
  `shipping_county` varchar(255) DEFAULT NULL,
  `shipping_firstname` varchar(100) DEFAULT NULL,
  `shipping_instructions` varchar(255) DEFAULT NULL,
  `shipping_lastname` varchar(100) DEFAULT NULL,
  `shipping_placename` varchar(100) DEFAULT NULL,
  `shipping_relayid` varchar(100) DEFAULT NULL,
  `shipping_street` varchar(255) DEFAULT NULL,
  `shipping_zipcode` varchar(30) DEFAULT NULL,
  `shippingcode` varchar(30) DEFAULT NULL,
  `sitecommissionpromisedamount` float(10,2) DEFAULT '0.00',
  `sitecommissionshippedamount` float(10,2) DEFAULT '0.00',
  `sitecommissionvalidatedamount` float(10,2) DEFAULT '0.00',
  `status` varchar(100) DEFAULT NULL,
  `validatedtotalamount` float(10,2) DEFAULT '0.00',
  `validatedtotalshippingcharges` float(10,2) DEFAULT '0.00',
  `validationstatus` varchar(100) DEFAULT NULL,
  `archiveparcellist` varchar(100) DEFAULT NULL,
  `seller_id` varchar(255) DEFAULT NULL COMMENT '卖家账号登录名',
  `addinfo` text COMMENT '其他信息 oms_auto_inserted=0/1 oms_admin_inserted=0/1 errors=',
  `updated_time` DATETIME NULL DEFAULT NULL COMMENT '小老板更新时间',
  PRIMARY KEY (`ordernumber`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='wish订单';

 
--
-- 表的结构 `cdiscount_order_detail`
--

CREATE TABLE IF NOT EXISTS `cdiscount_order_detail` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ordernumber` varchar(50) NOT NULL,
  `acceptationstate` varchar(100) DEFAULT NULL,
  `categorycode` varchar(100) DEFAULT NULL,
  `deliverydatemax` datetime DEFAULT NULL,
  `deliverydatemin` datetime DEFAULT NULL,
  `hasclaim` varchar(100) DEFAULT NULL,
  `initialprice` varchar(100) DEFAULT NULL,
  `isnegotiated` varchar(100) DEFAULT NULL,
  `isproducteangenerated` varchar(100) DEFAULT NULL,
  `name` varchar(100) DEFAULT NULL,
  `orderlinechildlist` varchar(255) DEFAULT NULL,
  `productcondition` varchar(100) DEFAULT NULL,
  `productean` varchar(100) DEFAULT NULL,
  `productid` varchar(100) NOT NULL COMMENT 'INTERETBCA：客人给的小费',
  `purchaseprice` float(10,2) NOT NULL DEFAULT '0.00',
  `quantity` int(10) DEFAULT '0',
  `rowid` varchar(100) DEFAULT NULL,
  `sellerproductid` varchar(100) DEFAULT NULL,
  `shippingdatemax` datetime DEFAULT NULL,
  `shippingdatemin` datetime DEFAULT NULL,
  `sku` varchar(100) DEFAULT NULL COMMENT 'INTERETBCA：客人给的小费',
  `skuparent` varchar(100) DEFAULT NULL,
  `unitadditionalshippingcharges` float(10,2) DEFAULT '0.00',
  `unitshippingcharges` float(10,2) DEFAULT '0.00',
  PRIMARY KEY (`id`),
  KEY `ordernumber` (`ordernumber`),
  KEY `productid` (`productid`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;



CREATE TABLE IF NOT EXISTS `lazada_order` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `OrderId` varchar(30) NOT NULL,
  `CustomerFirstName` varchar(50) DEFAULT NULL,
  `CustomerLastName` varchar(50) DEFAULT NULL,
  `OrderNumber` varchar(50) NOT NULL,
  `PaymentMethod` varchar(50) NOT NULL,
  `DeliveryInfo` varchar(255) DEFAULT NULL,
  `Remarks` varchar(255) DEFAULT NULL,
  `Price` decimal(10,2) NOT NULL DEFAULT '0.00',
  `GiftOption` tinyint(2) NOT NULL,
  `GiftMessage` text,
  `VoucherCode` varchar(255) DEFAULT NULL,
  `CreatedAt` int(11) NOT NULL,
  `UpdatedAt` int(11) NOT NULL,
  `AddressBilling` text,
  `AddressShipping` text,
  `NationalRegistrationNumber` varchar(100) DEFAULT NULL,
  `ItemsCount` int(11) NOT NULL,
  `Statuses` varchar(255) NOT NULL,
  `create_time` int(11) NOT NULL DEFAULT '0',
  `update_time` int(11) NOT NULL DEFAULT '0',
  `lazada_api_email` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `OrderId` (`OrderId`),
  KEY `OrderNumber` (`OrderNumber`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;



CREATE TABLE IF NOT EXISTS `lazada_order_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `OrderItemId` varchar(30) NOT NULL,
  `ShopId` varchar(30) NOT NULL,
  `OrderId` varchar(30) NOT NULL,
  `Name` varchar(255) NOT NULL,
  `Sku` varchar(100) NOT NULL,
  `ShopSku` varchar(100) NOT NULL,
  `ShippingType` varchar(50) NOT NULL,
  `ItemPrice` decimal(10,2) NOT NULL DEFAULT '0.00',
  `PaidPrice` decimal(10,2) NOT NULL DEFAULT '0.00',
  `Currency` varchar(10) NOT NULL,
  `WalletCredits` decimal(10,2) NOT NULL DEFAULT '0.00',
  `TaxAmount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `ShippingAmount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `VoucherAmount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `VoucherCode` varchar(255) DEFAULT NULL,
  `Status` varchar(30) NOT NULL,
  `ShipmentProvider` varchar(50) DEFAULT NULL,
  `TrackingCode` varchar(100) DEFAULT NULL,
  `Reason` varchar(255) DEFAULT NULL,
  `PurchaseOrderId` varchar(30) DEFAULT NULL,
  `PurchaseOrderNumber` varchar(30) DEFAULT NULL,
  `PackageId` varchar(30) DEFAULT NULL,
  `CreatedAt` int(11) NOT NULL,
  `UpdatedAt` int(11) NOT NULL,
  `SmallImageUrl` varchar(455) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;


CREATE TABLE IF NOT EXISTS  `dp_info` (
  `duepay_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `order_id` int(11) unsigned zerofill NOT NULL COMMENT '订单编号',
  `create_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '催款时间',
  `pay_time` timestamp NULL DEFAULT NULL COMMENT '支付时间',
  `status` tinyint(1) unsigned NOT NULL DEFAULT '1',
  `order_time` timestamp NULL DEFAULT NULL COMMENT '下单时间',
  `shop_id` varchar(50) NOT NULL COMMENT '店铺id',
  `rule_id` int(11) unsigned DEFAULT '0' COMMENT '规则id',
  `consignee_country_code` varchar(5) DEFAULT '' COMMENT '所属国家',
  `due_status` tinyint(1) unsigned DEFAULT '1' COMMENT '1:未付费，2:已付费',
  `buyer` varchar(50) DEFAULT NULL COMMENT '买家',
  `cost` decimal(10,2) DEFAULT NULL COMMENT '订单金额',
  `contacted` tinyint(1) unsigned DEFAULT '0' COMMENT '1：客服已联系过不再自动催款',
  `content` text COMMENT '催款内容',
  `source_id` varchar(255) DEFAULT NULL COMMENT '订单号',
  `update_time` timestamp NULL DEFAULT NULL COMMENT '更新时间',
  `msg_type`  tinyint(1) NULL DEFAULT 1 COMMENT '1：催款，2：>留言 ',
  PRIMARY KEY (`duepay_id`),
  KEY `order_id` (`order_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='催款提醒日志表';


CREATE TABLE IF NOT EXISTS   `cm_comment_rule` (
	`id` int(11) UNSIGNED AUTO_INCREMENT,
	-- `userid` int(11) UNSIGNED NOT NULL COMMENT '用户id',
	`selleruseridlist` varchar(255) NOT NULL COMMENT '规则包含的店铺名列表',
	`content` varchar(1000) NOT NULL COMMENT '好评内容',
	`is_dispute` tinyint(1) UNSIGNED COMMENT '是否包含纠纷订单',
	`countrylist` varchar(2000) NOT NULL COMMENT '规则包含的国家列表',
	`is_use` tinyint(1) NOT NULL DEFAULT 1 COMMENT '规则是否启用 1启用 0不启用',
	`platform` varchar(30) NOT NULL COMMENT '订单来源aliexpress,ebay,wish,amazon,dh等',
	`createtime` int(11) UNSIGNED DEFAULT 0 COMMENT '创建时间',
	`updatetime` int(11) UNSIGNED DEFAULT 0 COMMENT '修改时间',
	PRIMARY KEY (`id`),
	KEY `idx_platform` (`platform`)
) ENGINE=innodb DEFAULT CHARSET=utf8 COMMENT '自动好评规则表';

CREATE TABLE IF NOT EXISTS   `cm_comment_template` (
	`id` int(11) UNSIGNED AUTO_INCREMENT,
	`content` varchar(1000) NOT NULL COMMENT '留言内容',
	`createtime` int(11) UNSIGNED COMMENT '创建时间',
	-- `userid` int(11) UNSIGNED NOT NULL COMMENT '用户主帐号id',
	`is_use` tinyint(1) UNSIGNED DEFAULT 1 COMMENT '是否启用 1启用 0不启用',
	`platform` varchar(30) NOT NULL COMMENT '订单来源aliexpress,ebay,wish,amazon,dh等',
	PRIMARY KEY (`id`),
	KEY `idx_platform` (`platform`),
	KEY `idx_isuse` (`is_use`)
) ENGINE=MYISAM DEFAULT CHARSET=utf8 COMMENT '好评留言表';

CREATE TABLE IF NOT EXISTS   `cm_comment_log` (
	`id` int(11) UNSIGNED AUTO_INCREMENT,
	`order_id` int(11) UNSIGNED NOT NULL COMMENT '订单id',
	`order_source_order_id` varchar(50) NOT NULL COMMENT '订单来源平台id',
	`selleruserid` varchar(50) NOT NULL COMMENT '店铺id',
	`platform` varchar(30) NOT NULL COMMENT '订单来源aliexpress,ebay,wish,amazon,dh等',
	`source_buyer_user_id` varchar(50) NOT NULL DEFAULT '' COMMENT '买家用户名',
	`subtotal` decimal(10,2) UNSIGNED NOT NULL DEFAULT '0.00' COMMENT '产品总价格',
	`currency` char(3) NOT NULL DEFAULT '' COMMENT '货币',
	`paid_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '订单付款时间',
	`content` varchar(1000) NOT NULL DEFAULT '' COMMENT '好评留言内容',
	`is_success` tinyint(1) DEFAULT 0 COMMENT '是否发送成功 1成功 0失败',
	`error_msg` varchar(255) DEFAULT '' COMMENT '失败原因',
	`createtime` int(11) UNSIGNED DEFAULT 0 COMMENT '好评时间',
	`rule_id` int(11) UNSIGNED DEFAULT 0 COMMENT '好评规则id',
  `order_source_create_time` int(11) unsigned DEFAULT '0' COMMENT '下单时间',
	PRIMARY KEY (`id`),
	KEY `idx_platform` (`platform`)
) ENGINE=MYISAM DEFAULT CHARSET=utf8 COMMENT '好评记录表';

CREATE TABLE IF NOT EXISTS   `cm_comment_enable` (
	`id` int(11) UNSIGNED AUTO_INCREMENT,
	`uid` int(11) UNSIGNED NOT NULL COMMENT '小老板主帐号id',
	`selleruserid` varchar(100) NOT NULL COMMENT '卖家帐号',
	`platform` varchar(30) NOT NULL COMMENT '订单来源aliexpress,ebay,wish,amazon,dh等',
	`enable_status` tinyint(1) UNSIGNED NOT NULL DEFAULT 1 COMMENT '是否开启自动好评 0:停用，1:开启',
	`createtime` int(11) UNSIGNED DEFAULT 0 COMMENT '创建时间',
	PRIMARY KEY (`id`),
	KEY `idx_platform` (`platform`)
) ENGINE=MYISAM DEFAULT CHARSET=utf8 COMMENT '自动好评设置表';

 
 CREATE TABLE IF NOT EXISTS `od_order_old_v2` (
  `order_id` int(11) unsigned zerofill NOT NULL COMMENT '订单id',
  `order_status` smallint(5) NOT NULL DEFAULT '0' COMMENT '订单流程状态:100:未付款,200:已付款,201:有留言,205:未分仓库,210:SKU不存在,215:报关信息不全,220:paypal/ebay金额不对,225:paypal/ebay地址不对,230:未匹配物流,300:待生成包裹,400:发货处理中,500:已发货,600:已取消',
  `pay_status` tinyint(2) unsigned NOT NULL DEFAULT '0' COMMENT '支付状态',
  `order_source_status` varchar(50) DEFAULT NULL COMMENT '订单来源平台订单状态',
  `order_manual_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '自定义标签',
  `is_manual_order` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '是否挂起状态',
  `shipping_status` tinyint(2) unsigned NOT NULL DEFAULT '0' COMMENT '平台发货状态',
  `exception_status` smallint(5) DEFAULT '0' COMMENT '检测异常状态',
  `weird_status` char(10) DEFAULT NULL COMMENT '操作异常标签',
  `order_source` varchar(50) NOT NULL DEFAULT '' COMMENT '订单来源  ebay,amazon,aliexpress,custom',
  `order_type` varchar(50) NOT NULL DEFAULT '' COMMENT '订单类型如amazon FBA订单',
  `order_source_order_id` varchar(50) NOT NULL DEFAULT '' COMMENT '订单来源  的订单id',
  `order_source_site_id` varchar(50) NOT NULL DEFAULT '' COMMENT '订单来源平台下的站点：如eaby下的US站点',
  `selleruserid` varchar(50) NOT NULL DEFAULT '' COMMENT '订单来源平台卖家用户名(下单时候的用户名)',
  `saas_platform_user_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT 'saas库平台用户卖家账号id(ebay或者amazon卖家表中)',
  `order_source_srn` int(11) unsigned NOT NULL DEFAULT '0' COMMENT 'od_ebay_order表salesrecordnum',
  `customer_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT 'od_customer 表id',
  `source_buyer_user_id` varchar(255) NOT NULL DEFAULT '' COMMENT '来源买家用户名',
  `order_source_shipping_method` varchar(50) NOT NULL DEFAULT '' COMMENT '平台下单时用户选择的物流方式',
  `order_source_create_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '订单在来源平台的下单时间',
  `subtotal` decimal(10,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '产品总价格',
  `shipping_cost` decimal(10,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '运费',
  `antcipated_shipping_cost` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '预估运费',
  `actual_shipping_cost` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '实际运费',
  `discount_amount` decimal(10,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '折扣',
  `commission_total` decimal(10,2) DEFAULT '0.00' COMMENT '订单平台佣金',
  `paypal_fee` decimal(10,2) DEFAULT '0.00' COMMENT '暂时ebay使用的paypal 佣金（其他平台待开发其用途）',
  `grand_total` decimal(10,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '合计金额(产品总价格 + 运费 - 折扣 = 合计金额)',
  `returned_total` decimal(10,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '退款总金额',
  `price_adjustment` decimal(10,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '价格手动调整（下单后人工调整）',
  `currency` char(3) NOT NULL DEFAULT '' COMMENT '货币',
  `consignee` varchar(255) NOT NULL DEFAULT '' COMMENT '收货人',
  `consignee_postal_code` varchar(50) NOT NULL DEFAULT '' COMMENT '收货人邮编',
  `consignee_phone` varchar(255) NOT NULL DEFAULT '' COMMENT '收货人电话',
  `consignee_mobile` varchar(20) DEFAULT NULL COMMENT '收货人手机',
  `consignee_fax` varchar(255) DEFAULT NULL COMMENT '收件人传真号',
  `consignee_email` varchar(255) NOT NULL DEFAULT '' COMMENT '收货人Email',
  `consignee_company` varchar(255) NOT NULL DEFAULT '' COMMENT '收货人公司',
  `consignee_country` varchar(255) NOT NULL DEFAULT '' COMMENT '收货人国家名',
  `consignee_country_code` char(2) NOT NULL DEFAULT '' COMMENT '收货人国家代码',
  `consignee_city` varchar(255) NOT NULL DEFAULT '' COMMENT '收货人城市',
  `consignee_province` varchar(255) NOT NULL DEFAULT '' COMMENT '收货人省',
  `consignee_district` varchar(255) NOT NULL DEFAULT '' COMMENT '收货人区',
  `consignee_county` varchar(255) NOT NULL DEFAULT '' COMMENT '收货人镇',
  `consignee_address_line1` varchar(255) NOT NULL DEFAULT '' COMMENT '收货人地址1',
  `consignee_address_line2` varchar(255) NOT NULL DEFAULT '' COMMENT '收货人地址2',
  `consignee_address_line3` varchar(255) NOT NULL DEFAULT '' COMMENT '收货人地址3',
  `default_warehouse_id` int(11) NOT NULL DEFAULT '0' COMMENT '默认的仓库id',
  `default_carrier_code` varchar(50) NOT NULL DEFAULT '' COMMENT '默认物流商代码',
  `default_shipping_method_code` varchar(50) NOT NULL DEFAULT '' COMMENT '默认运输服务id',
  `paid_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '订单付款时间',
  `delivery_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '平台订单发货时间',
  `create_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int(11) NOT NULL DEFAULT '0' COMMENT '更新时间 ',
  `user_message` varchar(255) NOT NULL DEFAULT '' COMMENT '用户留言',
  `carrier_type` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '0:普通物流商  1:海外仓',
  `hassendinvoice` tinyint(1) DEFAULT '0' COMMENT '是否有发送ebay账单',
  `seller_commenttype` varchar(32) DEFAULT NULL COMMENT '卖家评价类型',
  `seller_commenttext` varchar(255) DEFAULT NULL COMMENT '卖家评价留言',
  `status_dispute` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否有发起ebay催款，0没有',
  `is_feedback` int(1) DEFAULT NULL,
  `rule_id` int(11) DEFAULT NULL COMMENT '运输服务匹配规则id',
  `customer_number` varchar(50) DEFAULT NULL COMMENT '物流商返回的客户单号用户查询物流号',
  `carrier_step` tinyint(1) DEFAULT '0' COMMENT '物流操作步骤',
  `is_print_picking` tinyint(1) DEFAULT '0' COMMENT '是否打印拣货单',
  `print_picking_operator` int(10) DEFAULT NULL COMMENT '打印拣货单操作人',
  `print_picking_time` int(11) DEFAULT NULL COMMENT '打印拣货单时间',
  `is_print_distribution` tinyint(1) DEFAULT '0' COMMENT '是否打印配货单',
  `print_distribution_operator` int(10) DEFAULT NULL COMMENT '打印配货单操作人',
  `print_distribution_time` int(11) DEFAULT NULL COMMENT '配货单打印时间',
  `is_print_carrier` tinyint(1) DEFAULT '0' COMMENT '是否打印物流单',
  `print_carrier_operator` int(10) DEFAULT NULL COMMENT '打印物流单操作人',
  `printtime` int(11) DEFAULT '0' COMMENT '订单物流单打单时间',
  `delivery_status` tinyint(2) DEFAULT '0' COMMENT '小老板发货流程状态',
  `delivery_id` bigint(13) DEFAULT NULL COMMENT '拣货单号',
  `desc` text COMMENT '订单备注',
  `carrier_error` text,
  `is_comment_status` tinyint(1) unsigned DEFAULT '0' COMMENT '该订单是否已给好评',
  `is_comment_ignore` tinyint(1) unsigned DEFAULT '0' COMMENT '该订单是否设置好评忽略',
  `issuestatus` varchar(20) DEFAULT '' COMMENT '订单纠纷状态',
  `payment_type` varchar(50) NOT NULL DEFAULT '' COMMENT '支付类型',
  `logistic_status` varchar(255) DEFAULT NULL COMMENT '物流状态',
  `logistic_last_event_time` datetime DEFAULT NULL COMMENT '物流最后更新时间',
  `fulfill_deadline` int(11) NOT NULL DEFAULT '0' COMMENT '销售平台最后发货期限',
  `profit` decimal(10,2) DEFAULT NULL COMMENT '小老板计算出的利润',
  `logistics_cost` decimal(10,2) DEFAULT NULL COMMENT '物流成本',
  `logistics_weight` decimal(10,2) DEFAULT NULL COMMENT '物流商返回的称重重量(g)',
  `addi_info` text COMMENT '额外信息',
  `distribution_inventory_status` tinyint(2) NOT NULL DEFAULT '0' COMMENT '分配库存状态2待分配，3缺货，4已分配',
  `reorder_type` varchar(50) DEFAULT NULL COMMENT '重新发货类型',
  `purchase_status` tinyint(2) NOT NULL DEFAULT '0' COMMENT '采购(缺货)状态',
  `pay_order_type` varchar(50) DEFAULT NULL COMMENT '已付款订单类型',
  `order_evaluation` tinyint(2) NOT NULL DEFAULT '0' COMMENT '订单评价1为好评，2为中评，3差评',
  `tracker_status` varchar(30) DEFAULT NULL COMMENT 'tracker的物流状态',
  `origin_shipment_detail` text COMMENT '原始的订单收件人信息',
  `order_ship_time` datetime DEFAULT NULL COMMENT '小老板发货时间',
  `shipping_notified` char(1) NOT NULL DEFAULT 'N' COMMENT '是否发送过启运通知,I为旧数据忽略',
  `pending_fetch_notified` char(1) NOT NULL DEFAULT 'N' COMMENT '是否发送过到达待取通知,I为旧数据忽略',
  `delivery_failed_notified` char(1) NOT NULL DEFAULT 'N' COMMENT '',
  `rejected_notified` char(1) NOT NULL DEFAULT 'N' COMMENT '是否发送过异常退回通知,I为旧数据忽略',
  `received_notified` char(1) NOT NULL DEFAULT 'N' COMMENT '是否发送过已签收求好评通知,I为旧数据忽略',
  `seller_weight` decimal(10,2) DEFAULT '0.00' COMMENT '卖家自己的称重重量(g)(取整)',
  `order_capture` char(1) NOT NULL DEFAULT 'N' COMMENT '是否手工订单N为否,Y为是',
  `order_relation` varchar(10) NOT NULL DEFAULT 'normal' COMMENT '订单类型 正常为normal,合并原始订单为fm,合并出来的新订单 sm,拆分的原始订单为fs,拆分后的新订单为ss ',
  `last_modify_time` datetime DEFAULT NULL COMMENT '订单的最后修改时间',
  `sync_shipped_status` char(1) NOT NULL DEFAULT 'Y' COMMENT '虚拟发货的同步状态P为待提交，S为提交中，F为提交失败，C为提交成功（小老板），Y为提交成功（非小老板）',
  `system_tag_1` char(1) NOT NULL DEFAULT '' COMMENT '系统标签1，Y为该标签标记了，N或者空白未没有',
  `system_tag_2` char(1) NOT NULL DEFAULT '' COMMENT '系统标签2，Y为该标签标记了，N或者空白未没有',
  `system_tag_3` char(1) NOT NULL DEFAULT '' COMMENT '系统标签3，Y为该标签标记了，N或者空白未没有',
  `system_tag_4` char(1) NOT NULL DEFAULT '' COMMENT '系统标签4，Y为该标签标记了，N或者空白未没有',
  `system_tag_5` char(1) NOT NULL DEFAULT '' COMMENT '系统标签5，Y为该标签标记了，N或者空白未没有',
  `customized_tag_1` char(1) NOT NULL DEFAULT '' COMMENT '自定义标签1，Y为该标签标记了，N或者空白未没有',
  `customized_tag_2` char(1) NOT NULL DEFAULT '' COMMENT '自定义标签2，Y为该标签标记了，N或者空白未没有',
  `customized_tag_3` char(1) NOT NULL DEFAULT '' COMMENT '自定义标签3，Y为该标签标记了，N或者空白未没有',
  `customized_tag_4` char(1) NOT NULL DEFAULT '' COMMENT '自定义标签4，Y为该标签标记了，N或者空白未没有',
  `customized_tag_5` char(1) NOT NULL DEFAULT '' COMMENT '自定义标签5，Y为该标签标记了，N或者空白未没有',
  `customized_tag_6` char(1) NOT NULL DEFAULT '' COMMENT '自定义标签6，Y为该标签标记了，N或者空白未没有',
  `customized_tag_7` char(1) NOT NULL DEFAULT '' COMMENT '自定义标签7，Y为该标签标记了，N或者空白未没有',
  `customized_tag_8` char(1) NOT NULL DEFAULT '' COMMENT '自定义标签8，Y为该标签标记了，N或者空白未没有',
  `customized_tag_9` char(1) NOT NULL DEFAULT '' COMMENT '自定义标签9，Y为该标签标记了，N或者空白未没有',
  `customized_tag_10` char(1) NOT NULL DEFAULT '' COMMENT '自定义标签10，Y为该标签标记了，N或者空白未没有',
  `tracking_no_state` int(3) default 0 COMMENT '物流商获取跟踪号状态 0:表示未获取过,1:表示获取失败,2:表示成功获取',
  `order_verify` varchar(20) DEFAULT NULL COMMENT '订单是否验证，ebay主要用于验证paypal地址与ebay地址是否一致',
  `items_md5` varchar(128) DEFAULT NULL COMMENT '所有商品关键值生成的md5,主要用于是否更新商品item表',
  `complete_ship_time` INT(11) NULL COMMENT '确认发货完成时间' ,
  `declaration_info` text DEFAULT '' COMMENT '用于记录最后一次上传的报关信息,以json格式',
  `isshow` char(1) NOT NULL DEFAULT 'Y' COMMENT '是否显示，Y为显示，N为不显示',
  `first_sku` varchar(255) DEFAULT NULL COMMENT '商品的第一个sku,可用于排序',
  `ismultipleProduct` char(1) NOT NULL DEFAULT 'N' COMMENT '是否多品订单，Y为是，N为不是',
  `tracking_number` varchar(255) DEFAULT NULL COMMENT '物流跟踪号',
  `billing_info` text COMMENT '账单地址相关信息',
  `transaction_key` varchar(255) DEFAULT NULL COMMENT '订单交易号（目前用于记录prestashop线下付款的汇款号）',
  PRIMARY KEY (`order_id`),
  KEY `idx_statmanu` (`order_status`,`order_manual_id`) USING BTREE,
  KEY `idx_timeseller` (`order_source_create_time`,`selleruserid`) USING BTREE,
  KEY `idx_timebuyer` (`order_source_create_time`,`source_buyer_user_id`) USING BTREE,
  KEY `idx_timegrand` (`order_source_create_time`,`grand_total`),
  KEY `idx_buyeremail` (`consignee_email`),
  KEY `idx_manu` (`order_manual_id`),
  KEY `order_source_order_id` (`order_source_order_id`),
  KEY `idx_order_customer` (`order_source`,`source_buyer_user_id`),
  KEY `idx_comment_status` (`is_comment_status`),
  KEY `idx_comment_ignore` (`is_comment_ignore`),
  KEY `idx_issuestatus` (`issuestatus`),
  KEY `logistic_status` (`logistic_status`,`order_source`) COMMENT 'logistic_status',
  KEY `pay_order_type` (`pay_order_type`),
  KEY `order_ship_time` (`order_ship_time`),
  KEY `shipping_notified` (`shipping_notified`,`pending_fetch_notified`,`rejected_notified`,`received_notified`),
  KEY `platform_shiptime` (`order_source`,`order_ship_time`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COMMENT='旧订单表'   ;


CREATE TABLE IF NOT EXISTS `od_order_item_old_v2` (
  `order_item_id` int(11) unsigned NOT NULL  COMMENT '订单商品id',
  `order_id` int(11) unsigned zerofill NOT NULL DEFAULT '00000000000' COMMENT '订单号',
  `order_source_srn` int(11) unsigned NOT NULL DEFAULT '0' COMMENT 'od_ebay_transaction表salesrecordnum',
  `order_source_order_item_id` bigint(20) unsigned NOT NULL DEFAULT '0' COMMENT 'od_ebay_transaction表id或amazon的OrderItemId',
  `sku` varchar(255) DEFAULT '' COMMENT '商品编码',
  `product_name` varchar(255) DEFAULT NULL COMMENT '标题',
  `photo_primary` text DEFAULT NULL COMMENT '主图',
  `shipping_price` decimal(10,2) unsigned DEFAULT '0.00' COMMENT '运费',
  `shipping_discount` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '运费折扣',
  `price` decimal(10,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '下单时价格',
  `promotion_discount` decimal(10,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '促销折扣',
  `ordered_quantity` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '下单时候的数量',
  `quantity` int(11) unsigned NOT NULL DEFAULT '1' COMMENT '需发货的商品数量',
  `sent_quantity` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '已发货数量',
  `packed_quantity` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '已打包数量',
  `returned_quantity` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '退货数量',
  `invoice_requirement` varchar(50) NOT NULL DEFAULT '' COMMENT '发票要求',
  `buyer_selected_invoice_category` varchar(50) NOT NULL DEFAULT '' COMMENT '发票种类',
  `invoice_title` varchar(50) NOT NULL DEFAULT '' COMMENT '发票抬头',
  `invoice_information` varchar(50) NOT NULL DEFAULT '' COMMENT '发票内容',
  `create_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  `desc` text COMMENT '订单商品备注',
  `platform_sku` varchar(50) DEFAULT '' COMMENT '平台sku',
  `is_bundle` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '是否是捆绑商品，1：是；0：否',
  `bdsku` varchar(50) NOT NULL DEFAULT '' COMMENT '捆绑sku',
  `source_item_id` varchar(50) DEFAULT NULL,
  `order_source_order_id` varchar(50) DEFAULT NULL COMMENT '订单来源平台订单号',
  `order_source_transactionid` varchar(50) DEFAULT NULL COMMENT '订单来源交易号或子订单号',
  `order_source_itemid` varchar(50) DEFAULT NULL COMMENT '产品ID listing的唯一标示',
  `product_attributes` varchar(100) DEFAULT NULL COMMENT '商品属性',
  `product_unit` varchar(50) DEFAULT NULL COMMENT '单位',
  `lot_num` int(5) DEFAULT '1' COMMENT '单位数量',
  `goods_prepare_time` int(2) DEFAULT '1' COMMENT '备货时间',
  `product_url` varchar(455) DEFAULT NULL COMMENT '商品url',
  `remark` varchar(255) DEFAULT NULL COMMENT '订单备注',
  `purchase_price` decimal(10,2) NULL DEFAULT NULL COMMENT '订单商品采购价snapshot',
  `purchase_price_form` DATETIME NULL DEFAULT NULL COMMENT '订单商品采购价snapshot生效的起始时间',
  `purchase_price_to` DATETIME NULL DEFAULT NULL COMMENT '订单商品采购价snapshot生效的结束时间',
  `is_sys_create_sku` char(1) NOT NULL DEFAULT '' COMMENT '空sku是否自动创建系统sku并保存到item表中,Y为是,N或空为不是',
  `addi_info` text COMMENT '额外信息',
  `oversea_sku` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '订单级别的海外仓sku',
  `delivery_status` varchar(20) DEFAULT NULL COMMENT '小老板中该item是否能发货',
  `platform_status` varchar(255) DEFAULT NULL COMMENT '各个平台对应的item status',
  `item_source` varchar(20) DEFAULT 'platform' COMMENT '商品来源：platform表示平台商品，local表示本地商品',
  `manual_status` varchar(20) DEFAULT 'enable' COMMENT '手工操作状态：enable表示启用，disable表示禁用',
  `root_sku` VARCHAR(255) NULL COMMENT '小老板的root sku' ,
  `declaration` TEXT NULL COMMENT '订单item级别的报关信息' ,
  PRIMARY KEY (`order_item_id`),
  KEY `idx_oid` (`order_id`),
  KEY `idx_itemid` (`order_source_order_item_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COMMENT='旧订单商品的数据表'  ;



CREATE TABLE IF NOT EXISTS `od_order_shipped_old_v2` (
  `id` int(11) NOT NULL COMMENT 'ID',
  `order_id` int(11) NOT NULL COMMENT '订单号',
  `order_source` varchar(50) NOT NULL DEFAULT '' COMMENT '订单来源  ebay,amazon,aliexpress,custom',
  `selleruserid` varchar(80) DEFAULT NULL COMMENT '卖家账号',
  `tracking_number` varchar(50) NOT NULL DEFAULT '' COMMENT '发货单号',
  `tracking_link` TEXT NOT NULL DEFAULT '' COMMENT '跟踪号查询网址',
  `shipping_method_code` varchar(50) NOT NULL DEFAULT '' COMMENT '物流方式code',
  `shipping_method_name` varchar(100) DEFAULT NULL COMMENT '物流方式名',
  `status` tinyint(2) NOT NULL DEFAULT '0' COMMENT '订单标记发货状态，0:未处理，1:成功，2:失败',
  `sync_to_tracker` char(1) NOT NULL DEFAULT '' COMMENT '是否同步到Tracker模块了，Y为是，默认空白为否',
  `result` varchar(20) DEFAULT NULL COMMENT '执行结果',
  `errors` text DEFAULT NULL COMMENT '返回错误',
  `created` int(11) DEFAULT NULL COMMENT '创建时间',
  `updated` int(11) DEFAULT NULL COMMENT '修改时间',
  `lasttime` int(11) DEFAULT NULL COMMENT '标记时间',
  `return_no` varchar(255) DEFAULT '' COMMENT '物流商返回数据',
  `shipping_service_id` int(11) DEFAULT NULL COMMENT '通过小老板平台发货 存储运输服务id',
  `order_source_order_id` varchar(50) DEFAULT NULL COMMENT '订单来源平台订单号',
  `addtype` varchar(100) DEFAULT NULL COMMENT '物流号来源',
  `signtype` varchar(10) DEFAULT NULL COMMENT '全部发货all 部分发货part',
  `description` varchar(255) DEFAULT NULL COMMENT '发货备注',
  `customer_number` varchar(50) DEFAULT NULL COMMENT '物流商返回的客户单号用户查询物流号',
  `tracker_status` varchar(30) NOT NULL DEFAULT '' COMMENT 'tracker的物流状态',
  KEY `idx_oid` (`order_id`) USING BTREE
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='旧订单标发货的数据表';


DROP TABLE IF EXISTS `dp_enable`;
CREATE TABLE `dp_enable` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `selleruserid` varchar(50) NOT NULL DEFAULT '',
  `platform` varchar(50) DEFAULT 'aliexpress',
  `enable` int(1) unsigned DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `om_order_message_info`;
CREATE TABLE `om_order_message_info` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `order_id` int(11) NOT NULL COMMENT 'OMS订单号',
  `order_source_order_id` varchar(50) NOT NULL DEFAULT '' COMMENT '平台
订单号',
  `order_source` varchar(50) NOT NULL DEFAULT '' COMMENT '平台',
  `rule_type` varchar(20) DEFAULT NULL COMMENT '匹配类型',
  `rule_id` int(10) DEFAULT NULL COMMENT '规则ID',
  `info_id` int(11) unsigned DEFAULT NULL COMMENT '日志ID',
  `create_time` timestamp NULL DEFAULT NULL,
  `update_time` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='消息助手日志记录';


DROP TABLE IF EXISTS `om_order_message_template`;
CREATE TABLE `om_order_message_template` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `template_name` varchar(50) NOT NULL DEFAULT '' COMMENT '模板名称',
  `content` text NOT NULL COMMENT '内容',
  `status` int(1) NOT NULL DEFAULT '1' COMMENT '删除标识',
  `is_active` int(11) NOT NULL COMMENT '是否启用',
  `create_time` timestamp NULL DEFAULT '2015-10-01 00:00:00',
  `update_time` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='消息助手模板';




DROP TABLE IF EXISTS `check_sync`;
CREATE TABLE `check_sync` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键',
  `sellerloginid` varchar(100) NOT NULL default '' COMMENT '速卖通登陆账户',
  `sync_time` int(11) not null DEFAULT '0' COMMENT '最后同步时间',
  `number` int(5) not null DEFAULT '0' COMMENT '备用字段',
  PRIMARY KEY (`id`),
  KEY `sellerloginid` (`sellerloginid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='速卖通手动同步订单最后更新时间表';

CREATE TABLE `ebay_muban_profile`( `id` INT(11) NOT NULL AUTO_INCREMENT, `savename` VARCHAR(32) COMMENT '范本名', `type` VARCHAR(16) COMMENT '模块', `detail` TEXT COMMENT '内容', `created` INT(11) COMMENT '创建时间', `updated` INT(11) COMMENT '修改时间', PRIMARY KEY (`id`), INDEX `savename` (`savename`), INDEX `type` (`type`) ) ENGINE=MYISAM CHARSET=utf8 COLLATE=utf8_general_ci;




--
-- Table structure for table `dely_carrier`
--

CREATE TABLE IF NOT EXISTS `dely_carrier` (
  `carrier_code` varchar(50) NOT NULL DEFAULT '' COMMENT '物流商编码',
  `carrier_name` varchar(100) NOT NULL DEFAULT '' COMMENT '物流商名',
  `extra_info` text NOT NULL COMMENT '额外信息',
  `type` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '类型：0系统类型1用户类型',
  `is_active` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '启用：0不启用1启用',
  `connect_address` varchar(50) NOT NULL DEFAULT '' COMMENT '联系地址',
  `connect_name` varchar(50) NOT NULL DEFAULT '' COMMENT '联系人姓名',
  `connect_phone` varchar(50) NOT NULL DEFAULT '' COMMENT '联系人电话',
  `carrier_remark` varchar(255) NOT NULL DEFAULT '' COMMENT '用户物流商备注信息 ',
  `create_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  `warehouse_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '唯一对应的仓库id',
  `carrier_type` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '0:普通物流商  1:海外仓',
  PRIMARY KEY (`carrier_code`),
  KEY `idx_typeacti` (`type`,`is_active`),
  KEY `idx_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='物流商表';




CREATE TABLE IF NOT EXISTS `lt_tracking_archive` (
  `id` int(11) NOT NULL ,
  `seller_id` varchar(100) DEFAULT NULL COMMENT '平台seller账号名',
  `order_id` varchar(100) DEFAULT NULL COMMENT '平台 订单号',
  `track_no` varchar(100) DEFAULT NULL COMMENT '物流号',
  `status` varchar(30) DEFAULT NULL COMMENT '物流状态，例如：运输途中，查询不到，已签收，到达待取，递送失败',
  `state` varchar(30) DEFAULT NULL COMMENT '状态分类，表示该状态属于正常还是退回，超时，无法交运。\n这个状态需要根据不同递送方式的容忍度来判断。部分规则客户可以自定义。例如 中国邮政 5天内查不到才算无法交运，5天不到达是正常。而DHL 5天不到就算递送超时了。\n可能值：正常，失败，超时，无法交运',
  `source` char(1) DEFAULT NULL COMMENT '记录的录入来源，手工录入是M，excel是E，OMS是O',
  `platform` varchar(30) DEFAULT NULL COMMENT '来源的平台，例如Amazon，SMT，eBay',
  `parcel_type` tinyint(4) DEFAULT '0' COMMENT '包裹类型, (0)->未知, (1)->小包, (2)->大包, (3)->EMS',
  `carrier_type` int(11) DEFAULT '0' COMMENT '物流类型代号， \n0=全球邮政，100002=UPS，100001=DHL，100003=Fedex，100004=TNT，100007=DPD，100010=DPD(UK)，100011=One World，100005=GLS，100012=顺丰速运，100008=EShipper，100009=Toll，100006=Aramex，190002=飞特物流，190008=云途物流，190011=百千诚物流，190007=俄速递，190009=快达物流，190003=华翰物流，190012=燕文物流，1 /* comment truncated */ /*90013=淼信国际，190014=俄易达，190015=俄速通，190017=俄通收，190016=俄顺达*/',
  `is_active` char(1) DEFAULT 'Y' COMMENT '是否活动	如果太旧的物流号，客户可以把它关闭跟踪，我们系统也可以定义n日无变化的跟踪放弃定期检查',
  `batch_no` varchar(100) DEFAULT NULL COMMENT '提交批次	提交方式+按照时间命名, 后续查看跟踪用户可以用这个来维护某天的查询对象以及某次excel上传来的整批物流	\n可能值：M2015-02-25，Excel2015-02-05 08:30',
  `create_time` date DEFAULT NULL COMMENT '提交日期',
  `update_time` datetime DEFAULT NULL COMMENT '更新时间',
  `from_nation` char(2) DEFAULT NULL COMMENT '来源国家',
  `to_nation` char(2) DEFAULT NULL COMMENT '目的国家',
  `mark_handled` char(1) DEFAULT 'N',
  `notified_seller` char(1) DEFAULT 'N' COMMENT '是否已发送邮件通知商家',
  `notified_buyer` char(1) DEFAULT 'N' COMMENT '是否已发送邮件通知买家，消费者',
  `shipping_notified` char(1) NOT NULL DEFAULT 'N' COMMENT '是否该状态下已发信通知',
  `pending_fetch_notified` char(1) NOT NULL DEFAULT 'N' COMMENT '是否该状态下已发信通知',
  `rejected_notified` char(1) NOT NULL DEFAULT 'N' COMMENT '是否该状态下已发信通知',
  `received_notified` char(1) NOT NULL DEFAULT 'N' COMMENT '是否在该状态通知过客户了',
  `ship_by` varchar(100) DEFAULT NULL COMMENT '递送公司，如 DHL，UPS，燕文，4PX',
  `delivery_fee` decimal(10,4) DEFAULT NULL COMMENT '递送费用CNY，如 3600.0000, 16.4569, 如果是粘贴快递公司的对账单，会有这个信息',
  `ship_out_date` date DEFAULT NULL COMMENT '快递单日期，如果是粘贴快递公司的对账单，会有这个信息',
  `total_days` tinyint(4) DEFAULT NULL,
  `all_event` text COMMENT '所有事件',
  `first_event_date` date DEFAULT NULL COMMENT '第一个Event的时间，用来判断，当2个物流商同一个物流号都有结果时候，用谁的结果来作准。',
  `last_event_date` date DEFAULT NULL COMMENT '最后一个物流时间时间，譬如签收时间',
  `stay_days` tinyint(4) NOT NULL DEFAULT '0' COMMENT '最后时间到现在逗留时间，如果已完成的订单，逗留时间为0即可',
  `msg_sent_error` char(1) NOT NULL DEFAULT 'N' COMMENT '是否有发信错误，如果有=Y',
  `from_lang` varchar(50) DEFAULT NULL COMMENT '发货国家的语言',
  `to_lang` varchar(50) DEFAULT NULL COMMENT '目标国家的语言',
  `first_track_result_date` date DEFAULT NULL COMMENT '第一次跟踪到结果的日期',
  `remark` text COMMENT '用户写入的备注，json格式存储',
  `addi_info` text COMMENT '其他信息',
  PRIMARY KEY (`id`),
  UNIQUE KEY `index2` (`track_no`,`order_id`),
  KEY `index3` (`order_id`),
  KEY `index4` (`batch_no`),
  KEY `index5` (`ship_by`),
  KEY `seller_orderid` (`seller_id`,`order_id`),
  KEY `status_state` (`status`,`state`),
  KEY `status_state11` (`state`,`status`),
  KEY `status_statesss` (`state`,`ship_out_date`,`create_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `lazada_publish_listing`;
CREATE TABLE IF NOT EXISTS `lazada_publish_listing` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `lazada_uid` int(11) NOT NULL COMMENT 'saas_lazada_user表主键',
  `platform` VARCHAR( 20 ) NOT NULL DEFAULT 'lazada' COMMENT '所属平台lazada,linio等',
  `site` varchar(10) NOT NULL COMMENT '卖家站点',
  `store_info` text NULL COMMENT 'lazada产品目录信息',
  `base_info` text NULL COMMENT '产品基本信息',
  `variant_info` text NULL COMMENT '产品变参信息',
  `image_info` text NULL COMMENT '产品图片信息',
  `description_info` text NULL COMMENT '产品描述信息',
  `shipping_info` text NULL COMMENT '产品发货信息',
  `warranty_info` text NULL COMMENT '产品保证信息',
  `state` varchar(32) NULL COMMENT '刊登产品的状态。 draft,product_upload,product_uploaded,image_upload,image_uploaded,complete,fail',
  `status` varchar(64) NULL COMMENT '产品状态的即时描述',
  `feed_id` varchar(127) NULL COMMENT 'api请求成功的feed id',
  `feed_info` text NULL COMMENT 'api请求失败原因',
  `uploaded_product` text NULL COMMENT '在于lazada的产品sku。',
  `create_time` int(11) NOT NULL DEFAULT '0' COMMENT '该记录创建时间',
  `update_time` int(11) NOT NULL DEFAULT '0' COMMENT '该记录更新时间',
  PRIMARY KEY (`id`),
  KEY `lazada_uid_id` (`lazada_uid`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COMMENT='lazada刊登产品' ;

DROP TABLE IF EXISTS `lazada_listing`;
CREATE TABLE IF NOT EXISTS `lazada_listing` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `lazada_uid_id` int(11) NOT NULL COMMENT 'saas_lazada_user的主键',
  `platform` varchar(20) NOT NULL,
  `site` varchar(10) NOT NULL,
  `SellerSku` varchar(255) NOT NULL,
  `ParentSku` varchar(255) DEFAULT NULL,
  `ShopSku` varchar(100) DEFAULT NULL,
  `Name` varchar(255) DEFAULT NULL,
  `Quantity` int(11) NOT NULL DEFAULT '0',
  `Available` int(11) NOT NULL DEFAULT '0',
  `Price` decimal(10,2) NOT NULL DEFAULT '0.00',
  `SalePrice` decimal(10,2) NOT NULL DEFAULT '0.00',
  `SaleStartDate` int(11) NOT NULL DEFAULT '0',
  `SaleEndDate` int(11) NOT NULL DEFAULT '0',
  `Status` varchar(10) NOT NULL,
  `ProductId` varchar(100) DEFAULT NULL COMMENT 'EAN/UPC/ISBN of the product, if it exists',
  `Url` varchar(255) DEFAULT NULL,
  `MainImage` varchar(350) DEFAULT NULL,
  `Variation` varchar(100) DEFAULT NULL,
  `create_time` int(11) NOT NULL,
  `update_time` int(11) NOT NULL,
  `sub_status` varchar(60) DEFAULT NULL COMMENT 'getProduct返回的Status只有active，inactive，deleted。需要增加一个字段来表示rejected,pending等等状态信息',
  `is_editing` TINYINT( 1 ) NOT NULL DEFAULT '0' COMMENT '是否修改中,等待产品信息回填',
  `feed_id` varchar(127) DEFAULT NULL COMMENT 'api请求成功的feed id',
  `error_message` text,
  `operation_log` text COMMENT '记录客户修改记录，json 数组记录每次操作',
  `FulfillmentBySellable` int(11) NOT NULL DEFAULT '0' COMMENT 'The available stock in Venture warehouse',
  `FulfillmentByNonSellable` int(11) NOT NULL DEFAULT '0' COMMENT 'The non-sellable stock in Venture warehouse',
  `ReservedStock` int(11) NOT NULL DEFAULT '0' COMMENT 'The stock reserved in Seller Center',
  `RealTimeStock` int(11) NOT NULL DEFAULT '0' COMMENT 'The stock reserved in Shop',
  `matching_info` text DEFAULT NULL COMMENT '配对待确认信息',
  
  PRIMARY KEY (`id`),
  KEY `lazada_uid_id` (`lazada_uid_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `lazada_listing_v2`;
CREATE TABLE IF NOT EXISTS `lazada_listing_v2` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `platform` varchar(20) NOT NULL,
  `site` varchar(10) NOT NULL,
  `lazada_uid` int(11) NOT NULL COMMENT 'saas_lazada_user的主键',
  `group_id` varchar(255) NOT NULL COMMENT '取产品其中一个sku',
  `SellerSku` varchar(255) NOT NULL,
  `name` varchar(255) NULL COMMENT '为方便搜索，抽出name字段',
  `PrimaryCategory` int(11) NOT NULL,
  `Attributes` text NULL,
  `Skus` text NULL,
  `sub_status` text NULL COMMENT '产品内各个Sku的getQc状态，或者是filter状态',
  `lb_status` int(11) NOT NULL DEFAULT '0' COMMENT '小老板定义的各种状态',
  `error_message` text NULL,
  `operation_log` text NULL COMMENT '记录客户修改记录，json 数组记录每次操作',
  `create_time` int(11) NOT NULL,
  `update_time` int(11) NOT NULL,
  `matching_info` text DEFAULT NULL COMMENT '配对待确认信息',
  
  PRIMARY KEY (`id`),
  KEY `platform` (`platform`),
  KEY `site` (`site`),
  KEY `SellerSku` (`SellerSku`),
  KEY `lazada_uid` (`lazada_uid`),
  KEY `group_id_lazada_uid` (`group_id`,`lazada_uid`),
  KEY `SellerSku_lazada_uid` (`SellerSku`,`lazada_uid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


CREATE TABLE IF NOT EXISTS `cs_ticket_message_tags` (
  `cs_ticket_tag_id` int(11) NOT NULL AUTO_INCREMENT,
  `cs_ticket_id` varchar(30) NOT NULL,
  `tag_id` int(11) NOT NULL,
  PRIMARY KEY (`cs_ticket_tag_id`),
  KEY `ticket_tag` (`cs_ticket_id`,`tag_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

CREATE TABLE `base_fitmentmuban` (                                                                            
	`id` int(10) unsigned NOT NULL auto_increment,                                                                                                                                        
	`siteid` int(10) NOT NULL default '100',                                                               
	`primarycategory` int(10) default NULL,                                                                
	`name` varchar(100) NOT NULL COMMENT '别名',                                                         
	`itemcompatibilitylist` longtext NOT NULL COMMENT 'fitment',                                           
	`created` int(11) NOT NULL,                                                                            
	`updated` int(11) default NULL,                                                                        
	PRIMARY KEY  (`id`)                                                                                    
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
              
              
CREATE TABLE `base_fitmenttosku` (                                              
	`id` int(10) NOT NULL,                                                        
	`fid` int(10) default NULL,                                                                                                        
	`name` varchar(100) default NULL COMMENT '别名',                            
	`sku` varchar(100) NOT NULL COMMENT 'SKU',                                    
	`addstatus` tinyint(1) default '1' COMMENT '是否允许SKU被自动添加',  
	PRIMARY KEY  (`id`)                                                           
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE `ebay_account_map` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `selleruserid` varchar(32) NOT NULL COMMENT 'ebay账号',
  `paypal` varchar(32) NOT NULL COMMENT 'paypal账号',
  `desc` varchar(32) DEFAULT NULL COMMENT '备注',
  `created` int(11) DEFAULT NULL COMMENT '记录创建时间',
  `updated` int(11) DEFAULT NULL COMMENT '记录修改时间',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=11 DEFAULT CHARSET=utf8;




CREATE TABLE IF NOT EXISTS `ensogo_variance` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Variance id',
  `product_id` int(11) NOT NULL COMMENT '这个variance属于某个范本',
  `parent_sku` varchar(150) NOT NULL COMMENT '父产品sku',
  `name` varchar(255) DEFAULT NULL,
  `sku` varchar(250) NOT NULL COMMENT '变参子产品sku',
  `sync_status` varchar(30) DEFAULT '' COMMENT '同步状态',
  `internal_sku` varchar(50) DEFAULT '' COMMENT 'Eagle系统的商品sku',
  `color` varchar(100) DEFAULT '' COMMENT 'wish平台的颜色',
  `size` varchar(100) DEFAULT '' COMMENT 'wish平台的size',
  `price` decimal(8,2) NOT NULL COMMENT 'wish平台美元售价',
  `shipping` decimal(8,2) NOT NULL COMMENT '运费美元',
  `shipping_time` varchar(50) DEFAULT '' COMMENT '运输时间',
  `inventory` int(11) NOT NULL COMMENT '库存量',
  `addinfo` text COMMENT '备用信息，json格式',
  `enable` char(1) NOT NULL DEFAULT 'Y' COMMENT '商品启用状态（Y=上架，N=下架）',
  `variance_product_id` varchar(50) DEFAULT '' COMMENT 'wish平台返回的product id',
  `image_url` varchar(255) NOT NULL DEFAULT '' COMMENT 'SKU专属图片地址',
  `msrp` decimal(10,2) DEFAULT '0.00' COMMENT '官方建议价',
  `error_message` text COMMENT 'ensogo平台返回的错误信息',
  `blocked` tinyint(1) DEFAULT '1' COMMENT '审核是否通过',
  PRIMARY KEY (`id`),
  UNIQUE KEY `locateit` (`product_id`,`parent_sku`,`sku`),
  KEY `syncstatus` (`sync_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Enosogo刊登的变参子产品范本信息' AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `ensogo_order` (
  `order_id` varchar(255) NOT NULL COMMENT '订单号',
  `order_time` datetime DEFAULT NULL COMMENT '创建时间',
  `order_total` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '总金额',
  `transaction_id` varchar(255) DEFAULT NULL COMMENT '交易号',
  `city` varchar(255) DEFAULT NULL COMMENT '城市',
  `country` varchar(255) DEFAULT NULL COMMENT '国家',
  `name` varchar(255) DEFAULT NULL COMMENT '姓名',
  `phone_number` varchar(255) DEFAULT NULL COMMENT '电话号码',
  `state` varchar(255) DEFAULT NULL COMMENT '州/省',
  `street_address1` text COMMENT '街道地址 1',
  `street_address2` text COMMENT '街道地址2',
  `zipcode` varchar(255) DEFAULT NULL COMMENT '邮编',
  `last_updated` datetime DEFAULT NULL COMMENT '最近更新时间',
  `shipping_cost` decimal(10,2) DEFAULT '0.00' COMMENT '运费成本',
  `shipping` decimal(10,2) DEFAULT '0.00' COMMENT '运费',
  `status` varchar(255) DEFAULT NULL COMMENT '状态',
  `buyer_id` varchar(50) DEFAULT NULL COMMENT 'buyerid 每个买家固定的',
  `shipping_provider` varchar(100) DEFAULT NULL COMMENT '物流商',
  `tracking_number` varchar(50) DEFAULT NULL COMMENT '物流号',
  `shipped_date` datetime DEFAULT NULL COMMENT '发货时间',
  `ship_note` text COMMENT '发货备注',
  `product_id` varchar(255) DEFAULT NULL COMMENT '商品ID',
  `variant_id` varchar(255) DEFAULT NULL,
  `variant_name` varchar(255) DEFAULT NULL COMMENT '商品variant name',
  `quantity` int(11) DEFAULT NULL COMMENT '数量',
  `price` decimal(10,2) DEFAULT NULL COMMENT '价格',
  `cost` decimal(10,2) DEFAULT NULL COMMENT '成本',
  `product_name` varchar(255) DEFAULT NULL COMMENT '商品名称',
  `product_image_url` text COMMENT '商品图片说明',
  `days_to_fulfill` int(11) DEFAULT NULL COMMENT '备货周期',
  `hours_to_fulfill` int(11) DEFAULT NULL COMMENT '备货周期',
  `sku` varchar(255) DEFAULT NULL COMMENT 'sku',
  `size` varchar(255) DEFAULT NULL COMMENT '规格',
  `refunded_by` varchar(255) DEFAULT NULL COMMENT '退款人',
  `refunded_time` datetime DEFAULT NULL COMMENT '退款时间',
  `refund_reason` varchar(255) DEFAULT NULL COMMENT '退款原因',
  `website` char(2) DEFAULT NULL COMMENT '站点',
  PRIMARY KEY (`order_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='ensogo订单';

CREATE TABLE  IF NOT EXISTS `ebay_promotion`(                                                       
	`id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,                                      
	`selleruserid` VARCHAR(100) DEFAULT NULL,                                           
	`promotionalsaleid` VARCHAR(50) DEFAULT NULL COMMENT '促销规则id',                 
	`promotionalsalename` VARCHAR(255) DEFAULT NULL COMMENT '促销名',                
	`action` VARCHAR(50) DEFAULT NULL COMMENT '对规则的操作，增删改查',      
	`discounttype` VARCHAR(50) DEFAULT NULL COMMENT '打折对象',                     
	`discountvalue` FLOAT(10,2) DEFAULT NULL COMMENT '折扣',                          
	`promotionalsaleendtime` INT(11) DEFAULT NULL COMMENT '结束时间',               
	`promotionalsalestarttime` INT(11) DEFAULT NULL COMMENT '开始时间',             
	`promotionalsaletype` VARCHAR(50) DEFAULT NULL COMMENT '促销类型',              
	`status` VARCHAR(50) DEFAULT NULL COMMENT '状态',                                 
	`created` INT(11) DEFAULT NULL,                                                     
	`updated` INT(11) DEFAULT NULL,                                                     
	PRIMARY KEY (`id`)                                                                  
	) ENGINE=MYISAM DEFAULT CHARSET=utf8;


CREATE TABLE IF NOT EXISTS `ensogo_product` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `brand` varchar(50) DEFAULT NULL COMMENT '品牌',
  `type` tinyint(4) NOT NULL DEFAULT '1' COMMENT '商品类型 1在线商品 2小老板刊登商品',
  `status` varchar(20) DEFAULT 'uploading' COMMENT '状态：error,editing, uploading, complete',
  `lb_status` int(11) DEFAULT '1' COMMENT '小老板刊登商品状态 1待发布 2平台审核中 3发布成功 4发布失败 5标记删除 6在线商品',
  `site_id` int(5) DEFAULT NULL,
  `parent_sku` varchar(255) NOT NULL DEFAULT '' COMMENT '如果是变参的，填入老爸SKU',
  `variance_count` int(11) NOT NULL DEFAULT '1' COMMENT '该范本包含的变参子产品数量',
  `name` varchar(255) NOT NULL COMMENT '产品名称',
  `tags` varchar(255) NOT NULL,
  `upc` varchar(50) DEFAULT NULL COMMENT 'UPC,EAN,barcode',
  `landing_page_url` text COMMENT '自营网店的URL',
  `internal_sku` varchar(255) DEFAULT '' COMMENT '小老板内部商品SKU',
  `category_id` int(4) DEFAULT NULL COMMENT 'ensogo分类',
  `msrp` decimal(10,2) DEFAULT '0.00' COMMENT '原售价，被划掉的',
  `shipping_time` varchar(250) DEFAULT '' COMMENT 'just the estimated days,e.g. "3-7"',
  `main_image` text COMMENT '主图片，必填',
  `extra_image_1` text,
  `extra_image_2` text,
  `extra_image_3` text,
  `extra_image_4` text,
  `extra_image_5` text,
  `extra_image_6` text,
  `extra_image_7` text,
  `extra_image_8` text,
  `extra_image_9` text,
  `extra_image_10` text,
  `create_time` datetime DEFAULT NULL,
  `update_time` datetime DEFAULT NULL,
  `description` text,
  `capture_user_id` tinyint(4) NOT NULL COMMENT '操作者ID',
  `ensogo_product_id` varchar(50) DEFAULT '' COMMENT 'Wish 平台的product id，用于update一个product',
  `error_message` text COMMENT 'Wish返回的错误信息',
  `addinfo` varchar(255) DEFAULT '' COMMENT '备用信息，json格式',
  `price` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '商品价格',
  `inventory` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '商品库存',
  `shipping` decimal(8,2) DEFAULT '0.00' COMMENT '运费美元',
  `number_saves` int(11) DEFAULT '0' COMMENT '商品WISH平台收藏数',
  `number_sold` int(11) DEFAULT '0' COMMENT '商品WISH平台销售数',
  `is_enable` tinyint(4) NOT NULL DEFAULT '1' COMMENT '变种商品是否存在下架商品 1不存在 2存在',
  `variant_name` varchar(255) DEFAULT NULL COMMENT '第一个变种name',
  `json_info` text COMMENT '其他信息',
  `request_id` varchar(255) DEFAULT NULL COMMENT 'ensogo平台返回查询id',
  `blocked` tinyint(1) DEFAULT '1' COMMENT '是否通过审核',
  `sale_type` tinyint(1) unsigned NOT NULL DEFAULT '2' COMMENT '售卖形式: 1单品 2多变种',
  PRIMARY KEY (`id`),
  KEY `site_id` (`site_id`),
  KEY `parent_sku` (`parent_sku`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;


CREATE TABLE IF NOT EXISTS `aliexpress_listing_detail` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'id',
  `productid` varchar(20) NOT NULL COMMENT '在线上id',
  `categoryid` varchar(20) NOT NULL COMMENT '所属目录id',
  `selleruserid` varchar(20) DEFAULT NULL COMMENT '卖家账号',
  `product_price` float(10,2) DEFAULT NULL COMMENT '售价',
  `product_gross_weight` float(10,3) DEFAULT NULL COMMENT '毛重kg',
  `product_length` int(10) DEFAULT NULL COMMENT '长cm',
  `product_width` int(10) DEFAULT NULL COMMENT '宽cm',
  `product_height` int(10) DEFAULT NULL COMMENT '高cm',
  `currencyCode` varchar(5) DEFAULT NULL COMMENT '货币',
  `aeopAeProductPropertys` text COMMENT '商品的类目属性',
  `sku_code` text COMMENT 'sku编号记录',
  `aeopAeProductSKUs` text COMMENT '商品的SKU信息',
  `detail` text COMMENT '详细描述（支持html）',
  `product_groups` varchar(200) DEFAULT NULL COMMENT '产品分组,支持多个,用逗号分隔',
  `product_unit` varchar(100) DEFAULT NULL COMMENT '商品单位编号',
  `package_type` tinyint(1) DEFAULT '0' COMMENT '销售方式  0-false 非打包销售 1-true',
  `reduce_strategy` tinyint(1) DEFAULT NULL COMMENT '库存减扣方式 1-下单减库存 2-支付减库存',
  `delivery_time` tinyint(2) DEFAULT NULL COMMENT '商品备货期 1-60天',
  `bulk_order` int(10) DEFAULT NULL COMMENT '批发最小数量 。取值范围2-100000。',
  `bulk_discount` tinyint(2) DEFAULT NULL COMMENT '批发折扣',
  `isPackSell` smallint(6) DEFAULT '0' COMMENT '是否自定义称重 0-false 1-true',
  `baseUnit` smallint(4) DEFAULT NULL COMMENT '购买几件以内不增加运费',
  `addUnit` smallint(4) DEFAULT NULL COMMENT '每增加件数',
  `addWeight` varchar(10) DEFAULT NULL COMMENT '对应增加的重量',
  `promise_templateid` int(10) DEFAULT NULL COMMENT '服务模板id',
  `wsValidNum` tinyint(2) DEFAULT NULL COMMENT '产品有效期14/30天',
  `listen_id` int(10) DEFAULT NULL COMMENT '未发布,保存状态下的商品主表ID',
  `lot_num` int(10) DEFAULT '1' COMMENT '每包件数。 打包销售情况，lotNum>1,非打包销售情况,lotNum=1',
  `product_mv` varchar(200) DEFAULT NULL COMMENT '介绍视频地址',
  `is_bulk` tinyint(1) DEFAULT '0' COMMENT '是否支持批发价 1-true 0-flase',
  `matching_info` text DEFAULT NULL COMMENT '配对待确认信息',
  PRIMARY KEY (`id`),
  KEY `productid` (`productid`),
  KEY `selleruserid` (`selleruserid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;


CREATE TABLE IF NOT EXISTS `od_order_goods` (
`id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY COMMENT '自增主键',
  `order_source` varchar(20) DEFAULT NULL COMMENT '销售平台来源',
  `order_id` int(11) NOT NULL COMMENT '小老板单号',
  `order_source_order_id` varchar(50) DEFAULT NULL COMMENT '销售平台订单号',
  `order_item_id` int(11) NOT NULL COMMENT 'od_order_item_v2主键',
  `order_source_itemid` varchar(50) DEFAULT NULL COMMENT '平台商品刊登号或者商品号',
  `photo_primary` varchar(255) DEFAULT NULL COMMENT '主图地址',
  `product_attributes` varchar(255) DEFAULT NULL COMMENT '物品属性可用于配货',
  `product_name` varchar(255) DEFAULT NULL COMMENT '标题或者商品配货名字',
  `order_item_sku` varchar(255) DEFAULT NULL COMMENT '小老板订单交易中的SKU',
  `order_item_quantity` int(5) DEFAULT NULL,
  `sku` varchar(255) DEFAULT NULL COMMENT '实际SKU',
  `quantity` int(5) DEFAULT NULL COMMENT '实际数量',
  `price` decimal(8,2) DEFAULT NULL COMMENT '成本参考单价用于计算毛利',
  `sold_time` datetime DEFAULT NULL,
  `paid_time` datetime DEFAULT NULL,
  `selleruserid` varchar(50) DEFAULT NULL COMMENT '卖家账号',
  `source_buyer_user_id` varchar(100) DEFAULT NULL COMMENT '买家账号',  
key(`order_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;


CREATE TABLE IF NOT EXISTS `od_order_systags_mapping` (
`id` int(11) NOT NULL AUTO_INCREMENT COMMENT '主键',
  `tag_code` varchar(50) NOT NULL COMMENT 'tag的代码',
  `order_id` int(11) NOT NULL COMMENT 'od_order_v2 中的order_id',
  PRIMARY KEY (`id`)
  
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='订单模块系统标签关联表';

CREATE TABLE IF NOT EXISTS `carrier_use_record`(
	`id` int(11) NOT NULL AUTO_INCREMENT,
	`carrier_code` varchar(50) NOT NULL DEFAULT '' COMMENT '物流商代码',
	`is_active` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '0:未启用 1:已启用',
	`create_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
	`update_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
	`is_del` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '0:未删除 1:已删除',
	`carrier_type` tinyint(1) default 0 not null COMMENT '0:为货代 1:海外仓',
	PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='user所使用的物流商';


CREATE TABLE IF NOT EXISTS `carrier_user_address`(
	`id` int(11) NOT NULL AUTO_INCREMENT,
	`carrier_code` varchar(50) NOT NULL DEFAULT '' COMMENT '物流商代码,当type为1时该值为空',
	`type` tinyint(1) not null default '0' COMMENT '0:物流所使用的地址 1:常用地址 2:回邮地址',
	`address_name` varchar(100) not null default '' COMMENT '该地址简称',
	`is_del` tinyint(1) NOT NULL DEFAULT '0' COMMENT '0:未删除 1:已删除',
	`is_default` tinyint(1) NOT NULL DEFAULT '0' COMMENT '0:不为默认 1:设置为默认',
	`address_params` text COMMENT '发货地址/揽货地址 数组',
	PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='user所使用的揽收或发货地址';


CREATE TABLE IF NOT EXISTS `warehouse_matching_rule` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键',
  `rule_name` varchar(100) NOT NULL COMMENT '仓库匹配规则名',
  `rules` text COMMENT '规则选择项',
  `warehouse_id` int(10) unsigned DEFAULT NULL COMMENT '仓库ID',
  `priority` int(3) DEFAULT 1 COMMENT '优先级',
  `is_active` tinyint(1) NOT NULL DEFAULT 0 COMMENT '是否启用',
  `created` int(11) NOT NULL DEFAULT 0 COMMENT '创建时间',
  `updated` int(11) NOT NULL DEFAULT 0 COMMENT '修改时间',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

CREATE TABLE `ensogo_variance_countries` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `variance_id` int(11) DEFAULT NULL COMMENT 'ENSOGO变种ID',
  `product_id` int(11) DEFAULT NULL COMMENT 'ENSOGO商品ID',
  `country_code` varchar(100) NOT NULL DEFAULT '0' COMMENT 'SMT订单号',
  `price` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '订单变更时间',
  `shipping` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '推送消息ID',
  `msrp` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '推送订单类型',
  `status` int(11) NOT NULL COMMENT '状态信息 1未发布 2已发布',
  `create_time` datetime DEFAULT NULL COMMENT '创建时间',
  `update_time` datetime DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `variance_id` (`variance_id`),
  KEY `product_id` (`product_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `priceminister_product_list` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `productid` varchar(30) DEFAULT NULL COMMENT 'PM内部prid id,订单获取不到的',
  `seller_id` varchar(255) NOT NULL COMMENT 'prod对应的卖家账号',
  `alias` varchar(255) DEFAULT NULL,
  `headline` text COMMENT '产品名',
  `caption` varchar(255) DEFAULT NULL COMMENT 'brand?',
  `topic` varchar(255) DEFAULT NULL,
  `offercounts` text COMMENT 'json',
  `bestprices` text COMMENT 'json',
  `url` text COMMENT '商品url',
  `image` text COMMENT '商品img',
  `barcode` varchar(100) DEFAULT NULL COMMENT 'EAN',
  `partnumber` varchar(255) DEFAULT NULL COMMENT '卖家sku',
  `reviews` text,
  `breadcrumbselements` text COMMENT 'json,搜索信息',
  PRIMARY KEY (`id`),
  KEY `seller_id & ean` (`seller_id`,`barcode`),
  KEY `seller_id & sku` (`seller_id`,`partnumber`),
  KEY `seller_id & product_id` (`seller_id`,`productid`),
  KEY `productid` (`productid`),
  KEY `barcode` (`barcode`),
  KEY `sku` (`partnumber`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `cdiscount_offer_terminator` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `addi_info` text,
  `uid` int(10) NOT NULL,
  `product_id` varchar(30) NOT NULL,
  `create` datetime DEFAULT NULL COMMENT 'create time',
  `is_parent_product` char(1) DEFAULT 'N' COMMENT 'if is a variant parent',
  `bestseller_name` varchar(100) DEFAULT NULL,
  `bestseller_price` decimal(8,2) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `uid+product_id` (`uid`,`product_id`),
  KEY `uid` (`uid`),
  KEY `product_id` (`product_id`),
  KEY `bestseller_name` (`bestseller_name`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `cdiscount_offer_terminator_daily` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` varchar(50) NOT NULL,
  `seller_product_id` varchar(100) NOT NULL COMMENT '卖家sku',
  `seller_id` varchar(100) NOT NULL COMMENT '卖家店铺登录名',
  `shop_name` varchar(100) DEFAULT NULL COMMENT '卖家店铺名',
  `name` varchar(1000) DEFAULT NULL COMMENT '商品名称',
  `img` text COMMENT '商品图片',
  `product_url` text COMMENT '商品链接',
  `date` date NOT NULL COMMENT '统计日期',
  `create_time` datetime DEFAULT NULL,
  `type` char(2) NOT NULL COMMENT '关注类型：H,F,N,I',
  `ever_been_surpassed` char(2) DEFAULT NULL COMMENT '是否曾经被挤掉bestseller位置',
  `change_history` text COMMENT '统计日内bestseller变动记录',
  PRIMARY KEY (`id`),
  KEY `product_id` (`product_id`),
  KEY `seller_id` (`seller_id`),
  KEY `shop_name` (`shop_name`),
  KEY `date` (`date`),
  KEY `type` (`type`),
  KEY `ever_been_surpassed` (`ever_been_surpassed`),
  KEY `seller_product_id` (`seller_product_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=3182 ;

CREATE TABLE `goodscollect_all` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL COMMENT '采集商品的标题',
  `description` longtext COMMENT '采集商品的内容',
  `mainimg` varchar(255) DEFAULT NULL COMMENT '采集商品的主图',
  `img` text COMMENT '采集商品的图片数据',
  `link` varchar(255) NOT NULL COMMENT '采集商品的原链接',
  `platform` varchar(55) NOT NULL COMMENT '采集商品的来源平台',
  `price` float DEFAULT NULL COMMENT '采集商品的价格',
  `toplatform` text COMMENT '商品认领到的平台',
  `wish` tinyint(1) NOT NULL DEFAULT '0',
  `ebay` tinyint(1) NOT NULL DEFAULT '0',
  `lazada` tinyint(1) NOT NULL DEFAULT '0',
  `ensogo` tinyint(1) NOT NULL DEFAULT '0',
  `createtime` int(11) NOT NULL COMMENT '采集商品采集时间',
  `customcode` text COMMENT '备用字段',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=5 DEFAULT CHARSET=utf8;


CREATE TABLE `goodscollect_ebay` (
  `mubanid` int(10) NOT NULL AUTO_INCREMENT,
  `uid` int(10) DEFAULT NULL,
  `siteid` int(5) DEFAULT NULL,
  `itemtitle` varchar(100) DEFAULT NULL,
  `listingtype` varchar(20) DEFAULT NULL,
  `location` char(128) DEFAULT NULL,
  `listingduration` char(10) DEFAULT NULL,
  `sku` varchar(255) DEFAULT NULL,
  `paypal` varchar(255) DEFAULT NULL,
  `selleruserid` varchar(200) DEFAULT NULL,
  `outofstockcontrol` tinyint(1) DEFAULT NULL,
  `isvariation` tinyint(1) DEFAULT NULL,
  `quantity` int(5) DEFAULT NULL,
  `startprice` decimal(10,2) DEFAULT NULL,
  `buyitnowprice` decimal(10,2) DEFAULT NULL,
  `shippingdetails` text,
  `mainimg` varchar(255) DEFAULT NULL,
  `desc` varchar(100) DEFAULT NULL,
  `createtime` int(11) DEFAULT NULL,
  `updatetime` int(11) DEFAULT NULL,
  PRIMARY KEY (`mubanid`),
  KEY `title` (`itemtitle`),
  KEY `sku` (`sku`),
  KEY `siteid` (`siteid`),
  KEY `listingduration` (`listingduration`),
  KEY `listingtype` (`listingtype`),
  KEY `quantity` (`quantity`),
  KEY `selleruserid` (`selleruserid`),
  KEY `isvariation` (`isvariation`),
  KEY `paypal` (`paypal`),
  KEY `outofstockcontrol` (`outofstockcontrol`),
  KEY `desc` (`desc`)
) ENGINE=MyISAM AUTO_INCREMENT=32 DEFAULT CHARSET=utf8;

CREATE TABLE `goodscollect_ebay_detail` (
  `mubanid` int(10) NOT NULL,
  `epid` varchar(128) DEFAULT NULL COMMENT 'epidID',
  `isbn` varchar(128) DEFAULT NULL COMMENT 'isbnID',
  `upc` varchar(128) DEFAULT NULL COMMENT 'upcID',
  `ean` varchar(128) DEFAULT NULL COMMENT 'eanID',
  `primarycategory` int(10) DEFAULT NULL COMMENT '主类目',
  `secondarycategory` int(10) DEFAULT NULL COMMENT '子类目',
  `storecategoryid` bigint(20) DEFAULT NULL COMMENT '店铺主类目',
  `storecategory2id` bigint(20) DEFAULT NULL COMMENT '店铺子类目',
  `lotsize` int(5) DEFAULT NULL COMMENT '每份件数',
  `itemdescription` longtext COMMENT '描述',
  `imgurl` text COMMENT '图片信息',
  `listingenhancement` text COMMENT '格式',
  `hitcounter` varchar(20) DEFAULT NULL COMMENT '计数器',
  `paymentmethods` text COMMENT '支付信息',
  `postalcode` varchar(20) DEFAULT NULL COMMENT '邮编',
  `country` varchar(10) DEFAULT NULL COMMENT '国家',
  `region` int(10) DEFAULT NULL COMMENT '区域',
  `template` varchar(60) DEFAULT NULL COMMENT '模板风格',
  `basicinfo` int(10) DEFAULT NULL COMMENT '信息模板',
  `gallery` varchar(200) DEFAULT NULL,
  `dispatchtime` int(10) DEFAULT NULL COMMENT '包裹处理时间',
  `return_policy` text COMMENT '退款信息',
  `conditionid` int(11) DEFAULT NULL COMMENT '特征是否是二手的',
  `variation` longtext COMMENT '多属性',
  `specific` text COMMENT '细节值，如color，size',
  `bestoffer` tinyint(1) DEFAULT '0' COMMENT '是否议价',
  `bestofferprice` decimal(10,2) DEFAULT NULL COMMENT '自动议价价格',
  `minibestofferprice` decimal(10,2) DEFAULT NULL COMMENT '自动拒绝议价价格',
  `buyerrequirementdetails` text COMMENT '买家要求',
  `autopay` tinyint(1) DEFAULT '0' COMMENT '是否要求立即支付',
  `secondoffer` decimal(10,0) DEFAULT NULL COMMENT '二次交易',
  `privatelisting` tinyint(1) DEFAULT '0' COMMENT '是否私人刊登',
  `itemtitle2` varchar(255) DEFAULT NULL COMMENT '子标题',
  `vatpercent` decimal(10,2) DEFAULT NULL COMMENT '税',
  `crossbordertrade` tinyint(1) DEFAULT '0',
  `crossselling` int(11) DEFAULT NULL COMMENT '交叉销售的范本ID',
  `createtime` int(11) DEFAULT NULL,
  `updatetime` int(11) DEFAULT NULL,
  `crossselling_two` int(10) DEFAULT NULL,
  PRIMARY KEY (`mubanid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `aliexpress_detail_module` (
  `id` bigint(18) NOT NULL,
  `display_content` text,
  `module_contents` text,
  `status` varchar(50) DEFAULT NULL,
  `name` varchar(100) DEFAULT NULL,
  `type` varchar(100) DEFAULT NULL,
  `ali_member_id` varchar(100) DEFAULT NULL,
  `sellerloginid` varchar(100) DEFAULT NULL,
  KEY `sellerloginid` (`sellerloginid`) USING BTREE,
  KEY `id` (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


CREATE TABLE IF NOT EXISTS `aliexpress_group_info` (
  `group_id` bigint(15) NOT NULL COMMENT '分组ID',
  `group_name` varchar(100) NOT NULL COMMENT '分组名',
  `parent_group_id` bigint(15) NOT NULL DEFAULT '0' COMMENT '父分组ID ',
  `selleruserid` varchar(100) NOT NULL,
  KEY `group_id` (`group_id`) USING BTREE,
  KEY `parent_group_id` (`parent_group_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `common_declared_info` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键id',
  `custom_name` varchar(100) COMMENT '自定义名称',
  `ch_name` varchar(100) COMMENT '中文报关名',
  `en_name` varchar(100) COMMENT '英文报关名',
  `declared_value` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '申报金额',
  `declared_weight` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '申报重量',
  `detail_hs_code` varchar(50) DEFAULT NULL COMMENT '海关编码',
  `is_default` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否默认 1表示默认,0表示不默认', 
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='常用报关信息表';

CREATE TABLE IF NOT EXISTS `edm_sent_history` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `act_name` varchar(255) NOT NULL COMMENT '调用的模块',
  `module_key` varchar(255) NOT NULL COMMENT '模块对应的key或id',
  `send_to` varchar(255) NOT NULL COMMENT '发给谁',
  `send_from` varchar(255) NOT NULL COMMENT '通过哪个邮箱发送',
  `subject` text,
  `status` int(11) NOT NULL DEFAULT '0' COMMENT '0：未处理；1：成功；2：失败',
  `error_message` text,
  `create_time` datetime DEFAULT NULL,
  `update_time` datetime DEFAULT NULL,
  `addi_info` text COMMENT 'json：subject、body、from_nane.....',
  PRIMARY KEY (`id`),
  KEY `act_name` (`act_name`),
  KEY `module_key` (`module_key`),
  KEY `send_to` (`send_to`),
  KEY `send_from` (`send_from`),
  KEY `status` (`status`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `od_order_relation` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '关联id',
  `father_orderid` int(11) NOT NULL COMMENT '父订单小老板订单号',
  `son_orderid` int(11) NOT NULL COMMENT '子订单小老板订单号',
  `type` varchar(10) NOT NULL DEFAULT '' COMMENT '关联类型merge,split',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  COMMENT='订单关联表' ;

CREATE TABLE IF NOT EXISTS `db_sales_daily` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `thedate` date NOT NULL,
  `platform` varchar(255) NOT NULL COMMENT 'CDISCOUNT,EBAY,etc',
  `seller_id` varchar(255) NOT NULL,
  `order_type` varchar(50) DEFAULT NULL COMMENT '平台订单类型，如amazon的AFN/MFN，CD的FBC/normal',
  `sales_count` int(11) NOT NULL,
  `sales_amount_original_currency` decimal(13,2) NOT NULL,
  `original_currency` char(3) NOT NULL,
  `sales_amount_USD` decimal(13,2) NOT NULL,
  `profit_cny` decimal(13,2) NOT NULL DEFAULT '0.00' COMMENT '利润，人民币计算',
  `use_module_type` varchar(20) NULL DEFAULT NULL COMMENT '使用者模块',
  PRIMARY KEY (`id`),
  UNIQUE KEY `dfsf` (`thedate`,`platform`,`seller_id`,`order_type`, `use_module_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='每个平台每个店铺每天生产一条记录' AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `wh_3rd_party_stockage` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `warehouse_id` int(11) NOT NULL COMMENT '海外仓仓库编号，对应海外仓库表',
  `platform_code` varchar(255) NOT NULL DEFAULT '' COMMENT '平台sku，也可以是库存编码',
  `sku` varchar(255) NOT NULL DEFAULT '' COMMENT '本地sku',
  `seller_sku` varchar(255) NOT NULL COMMENT '平台上面显示用户自编的sku',
  `current_inventory` int(11) NOT NULL DEFAULT '0' COMMENT '当前库存',
  `adding_inventory` int(11) NOT NULL DEFAULT '0' COMMENT '在途补货库存',
  `reserved_inventory` int(11) NOT NULL DEFAULT '0' COMMENT '占用库存',
  `usable_inventory` int(11) NOT NULL DEFAULT '0' COMMENT '可用库存',
  `title` varchar(255) NOT NULL DEFAULT '' COMMENT '产品名称',
  `suggest_inventory` int(11) NOT NULL DEFAULT '0' COMMENT '建议库存',
  `biweekly_sold_qty` int(11) NOT NULL DEFAULT '0' COMMENT '2周销量',
  `img_url` int(11) DEFAULT NULL COMMENT '图片链接',
  `account_id` varchar(255) NOT NULL DEFAULT '' COMMENT '海外仓账号编号，对应物流账号表',
  `addinfo` varchar(255) NOT NULL DEFAULT '',
  `marketplace_id` VARCHAR(50) NOT NULL DEFAULT '' COMMENT 'Amazon站点Id', 
  `not_usable_inventory` int(11) NOT NULL DEFAULT '0' COMMENT '不可售库存',
  PRIMARY KEY (`id`),
  KEY `sku` (`warehouse_id`,`sku`),
  KEY `sku_2` (`sku`),
  KEY `seller_sku` (`seller_sku`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='海外仓的产品库存' AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `order_declared_info` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `platform` varchar(50) COMMENT '对应平台',
  `itemID` VARCHAR(255) COMMENT '平台的Item ID',
  `sku` VARCHAR(255) COMMENT 'SKU',
  `ch_name` varchar(100) COMMENT '中文报关名',
  `en_name` varchar(100) COMMENT '英文报关名',
  `declared_value` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '申报金额',
  `declared_weight` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '申报重量',
  `detail_hs_code` varchar(50) DEFAULT NULL COMMENT '海关编码',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='订单级别报关信息' AUTO_INCREMENT=1;

CREATE TABLE IF NOT EXISTS `listing_move_log` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `item_id` VARCHAR(64) NULL DEFAULT NULL COMMENT '商品编号',
  `sku` varchar(128) DEFAULT NULL COMMENT '主SKU',
  `item_title` varchar(255) DEFAULT NULL COMMENT '商品标题',
  `platform_from` varchar(50) NOT NULL COMMENT '搬家源平台',
  `platform_to` varchar(50) NOT NULL COMMENT '搬家目标平台',
  `shop_from` varchar(128) NOT NULL COMMENT '搬家源商店(或源卖家)',
  `shop_to` varchar(128) NOT NULL COMMENT '搬家目标商店(或目标卖家)',
  `move_count` int(11) NOT NULL DEFAULT '1' COMMENT '添加次数',
  `create_time` timestamp NULL DEFAULT NULL COMMENT '创建时间',
  `update_time` timestamp NULL DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE IF NOT EXISTS `amazon_feedback_info` (
  `feedback_id` int(11) NOT NULL AUTO_INCREMENT,
  `create_time` int(11) unsigned NOT NULL DEFAULT '0',
  `rating` int(11) NOT NULL DEFAULT '0' COMMENT '级别，几星',
  `feedback_comments` text NOT NULL COMMENT 'feedback 内容',
  `arrived_on_time` int(1) NOT NULL DEFAULT '1' COMMENT '已准时抵达',
  `item_as_described` int(1) NOT NULL DEFAULT '1' COMMENT '与描述相符的商品',
  `customer_service` int(1) NOT NULL DEFAULT '1' COMMENT '客户服务',
  `order_source_order_id` varchar(100) NOT NULL COMMENT '平台订单号',
  `rater_email` varchar(100) NOT NULL COMMENT '评价人电子邮箱',
  `rater_role` varchar(10) DEFAULT NULL COMMENT '评价者身份',
  `respond_url` varchar(250) DEFAULT NULL COMMENT '回复url',
  `resolve_url` varchar(250) DEFAULT NULL COMMENT '解决url',
  `message_from_amazon` text COMMENT '亚马逊消息',
  `rating_status` int(1) NOT NULL DEFAULT '0' COMMENT '是否处理差评',
  `marketplace_id` varchar(50) NOT NULL COMMENT '站点Id',
  `merchant_id` varchar(50) NOT NULL COMMENT '账号Id',
  PRIMARY KEY (`feedback_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=149 ;

CREATE TABLE IF NOT EXISTS `amazon_review_info` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `review_id` varchar(50) DEFAULT NULL,
  `merchant_id` varchar(50) NOT NULL COMMENT '账号Id',
  `marketplace_id` varchar(50) NOT NULL COMMENT '站点Id',
  `asin` varchar(50) NOT NULL,
  `create_time` int(11) unsigned NOT NULL DEFAULT '0',
  `rating` decimal(2,1) NOT NULL COMMENT '级别，几星',
  `title` varchar(255) NOT NULL COMMENT '标题',
  `author` varchar(50) NOT NULL COMMENT '留言者',
  `format_strip` varchar(255) NOT NULL DEFAULT '1' COMMENT '属性',
  `verified_purchase` varchar(20) NOT NULL COMMENT '是否确认购买',
  `review_comments` text COMMENT 'review内容',
  `order_source_order_id` varchar(50) COMMENT '订单号',
  `cust_id` varchar(50) COMMENT '客户id',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1931 ;


CREATE TABLE IF NOT EXISTS `cs_mail_quest_list` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `quest_template_id` int(11) NOT NULL COMMENT '对应的模板id',
  `quest_number` varchar(100) NOT NULL COMMENT '生成的任务批次，templateId-time格式',
  `platform` varchar(100) NOT NULL,
  `seller_id` varchar(255) NOT NULL,
  `site_id` varchar(100) NOT NULL,
  `order_source_order_id` varchar(100) NOT NULL,
  `status` varchar(20) NOT NULL COMMENT '邮件状态：P(pending)-待发送，S(submit)-提交处理中，C(complete)-发送完成，F(failed)-发送失败,CF-创建失败',
  `priority` int(1) DEFAULT '5' COMMENT '优先级1最高，5最低',
  `mail_from` varchar(255) NOT NULL COMMENT '发件人',
  `mail_to` varchar(255) NOT NULL COMMENT '收件人',
  `consignee` varchar(255) DEFAULT NULL COMMENT '收件人名称',
  `subject` varchar(1000) NOT NULL COMMENT '邮件标题',
  `body` text COMMENT '邮件内容',
  `pending_send_time_location` datetime DEFAULT NULL COMMENT '预计发送时间-本地',
  `pending_send_time_consignee` datetime DEFAULT NULL COMMENT '预计发送时间-收件人时区',
  `sent_time_location` datetime DEFAULT NULL COMMENT '发送成功时间-本地',
  `sent_time_consignee` datetime DEFAULT NULL COMMENT '发送成功时间-收件人时区',
  `last_sent_time` datetime DEFAULT NULL COMMENT '最后一次尝试发送的时间',
  `created_time` datetime DEFAULT NULL,
  `update_time` datetime DEFAULT NULL,
  `retry_count` int(11) NOT NULL DEFAULT '0' COMMENT '重发次数',
  `addi_info` text,
  PRIMARY KEY (`id`),
  KEY `quest_template_id` (`quest_template_id`,`quest_number`,`platform`,`seller_id`,`site_id`,`order_source_order_id`,`status`,`priority`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `cs_quest_template` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `platform` varchar(100) NOT NULL,
  `seller_id` varchar(255) NOT NULL COMMENT 'amazon为merchant_id',
  `site_id` varchar(255) NOT NULL COMMENT 'amazon为marketplace_id',
  `name` varchar(255) NOT NULL,
  `status` varchar(100) NOT NULL DEFAULT 'active',
  `auto_generate` int(11) NOT NULL DEFAULT '0' COMMENT '是否自动生成任务：0不自动，1自动',
  `subject` varchar(1000) DEFAULT NULL,
  `contents` text,
  `for_order_type` varchar(100) DEFAULT NULL COMMENT '适用于什么类型订单，如FBA，FBM',
  `send_after_order_created_days` int(11) DEFAULT '1' COMMENT '订单创建后多少天符合规规则',
  `pending_send_time` varchar(2) DEFAULT '9' COMMENT '发送时间段',
  `order_in_howmany_days` int(11) NOT NULL COMMENT '订单创建后几天后符合规则',
  `can_send_when_reviewed` int(11) NOT NULL DEFAULT '0' COMMENT '是否有review也继续发',
  `can_send_when_feedbacked` int(11) NOT NULL DEFAULT '0' COMMENT '是否有feedback也继续发',
  `can_shen_when_contacted` int(11) NOT NULL DEFAULT '0' COMMENT '是否有邮件来往过也继续发',
  `filter_order_item_type` varchar(100) DEFAULT 'non' COMMENT '按什么方式筛选订单商品。in或者out或者non',
  `order_item_key_type` varchar(100) DEFAULT NULL COMMENT 'item key的类型筛选，key的类型(sku；platform_item_id)',
  `order_item_keys` varchar(500) DEFAULT NULL COMMENT '要筛选的item keys',
  `send_one_pre_howmany_days` int(11) NOT NULL DEFAULT '7' COMMENT '同一个买家最多每多少天才发一封',
  `can_send_to_blacklist_buyer` int(11) DEFAULT '0' COMMENT '是否对黑名单里面的买家也发',
  `create_time` datetime DEFAULT NULL,
  `update_time` datetime DEFAULT NULL,
  `last_generated_time` datetime DEFAULT NULL,
  `last_generated_log` text COMMENT '上一次生成任务的结果log',
  `addi_info` text,
  PRIMARY KEY (`id`),
  UNIQUE KEY `seller_template` (`platform`,`seller_id`,`site_id`,`name`),
  KEY `can_send_when` (`can_send_when_reviewed`,`can_send_when_feedbacked`,`can_shen_when_contacted`),
  KEY `auto_generate` (`auto_generate`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `cs_seller_email_address` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `email_address` varchar(255) NOT NULL,
  `platform` varchar(100) DEFAULT NULL,
  `seller_id` varchar(255) NOT NULL COMMENT 'amazon为merchant_id',
  `site_id` varchar(255) NOT NULL COMMENT 'amazon为marketplace_id',
  `create_time` datetime DEFAULT NULL,
  `update_time` datetime DEFAULT NULL,
  `status` varchar(100) DEFAULT NULL,
  `sent_count` int(11) NOT NULL DEFAULT '0' COMMENT '累计发送邮件数',
  `addi_info` text,
  PRIMARY KEY (`id`),
  UNIQUE KEY `seller_store` (`platform`,`seller_id`,`site_id`),
  KEY `email_address` (`email_address`,`status`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `amazon_order_info` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `create_time` int(11) unsigned DEFAULT NULL,
  `order_time` int(11) unsigned DEFAULT NULL COMMENT '订单日期',
  `order_id` varchar(50) DEFAULT NULL COMMENT 'amazon单号',
  `cust_id` varchar(50) DEFAULT NULL COMMENT '客户id',
  `marketplace_id` varchar(50) NOT NULL COMMENT '站点Id',
  `merchant_id` varchar(50) NOT NULL COMMENT '账号Id',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=149 COMMENT='Amazon后台 Order 的个别信息';

CREATE TABLE IF NOT EXISTS `cms_notification_readed` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `notification_id` int(11) NOT NULL COMMENT '消息id',
  `uid` int(11) NOT NULL,
  `create_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=164 COMMENT='消息是否已读对应关系表';

CREATE TABLE IF NOT EXISTS `amazon_listing` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `merchant_id` varchar(100) NOT NULL,
  `marketplace_id` varchar(100) NOT NULL,
  `marketplace_short` varchar(2) NOT NULL,
  `asin` varchar(255) NOT NULL,
  `product_id` VARCHAR(255) DEFAULT NULL,
  `sku` varchar(100) DEFAULT NULL,
  `ean` varchar(100) DEFAULT NULL,
  `upc` varchar(100) DEFAULT NULL,
  `title` text,
  `description` text COMMENT '商品描述',
  `price` decimal(15,2) NOT NULL DEFAULT '0.00',
  `images_src` text COMMENT '原始图片url，json格式',
  `stock` int(11) DEFAULT '0' COMMENT '线上库存',
  `condition` int(2) DEFAULT NULL COMMENT '商品成色?',
  `currency` varchar(3) DEFAULT NULL COMMENT '币种',
  `images` text COMMENT 'json格式',
  `detail_page_url` text,
  `binding` varchar(100) DEFAULT NULL,
  `brand` varchar(100) DEFAULT NULL,
  `catalog_number` varchar(100) DEFAULT NULL,
  `cat_str` text,
  `color` varchar(100) DEFAULT NULL,
  `size` varchar(100) DEFAULT NULL,
  `feature` text,
  `label` varchar(100) DEFAULT NULL,
  `publisher` varchar(100) DEFAULT NULL,
  `studio` varchar(100) DEFAULT NULL,
  `manufacturer` varchar(100) DEFAULT NULL,
  `model` varchar(100) DEFAULT NULL,
  `mpn` varchar(100) DEFAULT NULL,
  `part_number` varchar(100) DEFAULT NULL,
  `childrens` text COMMENT '子商品asin，json格式',
  `parent_asin` varchar(255) DEFAULT NULL COMMENT '父ASIN',
  `relationships` text COMMENT '关系信息json',
  `product_group` varchar(200) DEFAULT NULL,
  `product_type_name` varchar(200) DEFAULT NULL,
  `status` varchar(100) NOT NULL COMMENT '在售状态等',
  `get_info_step` int(2) NOT NULL DEFAULT '0' COMMENT '获取信息的进行步骤。0：通过report获取了基本信息；1：通过接口获取了详细信息；2上传并获取了本地图片url',
  `report_info` text COMMENT '从report 获取的产品信息',
  `prod_info` text COMMENT '从接口 获取的产品信息',
  `is_get_prod_info` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否获取产品信息,0等待获取 1正在获取 2获取完成 3上次获取失败',
  `err_times` int(11) NOT NULL DEFAULT '0',
  `err_msg` varchar(255) DEFAULT NULL,
  `batch_num` char(255) NOT NULL COMMENT '批次号，用report id填入',
  `create_time` datetime NOT NULL,
  `update_time` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `merchant_id` (`merchant_id`,`marketplace_short`,`asin`,`sku`,`upc`,`ean`),
  KEY `batch_num` (`batch_num`,`catalog_number`,`brand`),
  KEY `parent_asin` (`parent_asin`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COMMENT='amazon线上listing表，用于oms或者零售易搬家' AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `pd_product_classification` (
  `ID` int(4) NOT NULL AUTO_INCREMENT,
  `number` varchar(10)  NOT NULL DEFAULT '' COMMENT '类别编码',
  `name` varchar(50) NOT NULL COMMENT '类别名称',
  `parent_number` varchar(10)  NOT NULL DEFAULT '0' COMMENT '父节类别编码',
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COMMENT='商品分类表' AUTO_INCREMENT=9 ;

CREATE TABLE IF NOT EXISTS `ut_user_pdf` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `origin_url` varchar(255) DEFAULT NULL,
  `origin_size` varchar(50) DEFAULT NULL,
  `original_name` varchar(255) NOT NULL,
  `service` int(11) DEFAULT NULL,
  `file_key` varchar(255) DEFAULT NULL,
  `language` varchar(50) DEFAULT NULL,
  `create_time` datetime DEFAULT NULL,
  `product_ids` varchar(255) DEFAULT NULL,
  `add_info` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `user_operation_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `log_module` varchar(20) NOT NULL DEFAULT '' COMMENT '操作模块',
  `uid` int(11) NULL DEFAULT NULL COMMENT '操作者id',
  `operator_name` varchar(100) NOT NULL DEFAULT '' COMMENT '操作者名称',
  `create_time` int(11) NULL DEFAULT NULL COMMENT '操作时间',
  `operator_content` varchar(500) NOT NULL DEFAULT '' COMMENT '操作内容',
  `remark` varchar(255) NOT NULL DEFAULT '' COMMENT '备注',
  `addi_info` text COMMENT '额外信息',
  `key_id` int(11) NULL DEFAULT NULL COMMENT '对应表Id, 如order_id, product_id',
  `login_ip` varchar(100) NULL DEFAULT NULL COMMENT '登录ip',
  PRIMARY KEY (`id`),
  KEY `log_module` (`log_module`),
  KEY `uid` (`uid`),
  KEY `operator_content` (`operator_content`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COMMENT='用户操作日志';

CREATE TABLE IF NOT EXISTS `wh_stock_allocation` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `stock_allocatione_id` varchar(30) NOT NULL COMMENT '调拨单号',
  `in_warehouse_id` smallint(5) unsigned DEFAULT NULL COMMENT '调入仓库id',
  `out_warehouse_id` smallint(5) unsigned DEFAULT NULL COMMENT '调出仓库id',
  `number_of_sku` smallint(5) unsigned DEFAULT NULL COMMENT '产品品种数量',
  `comment` varchar(255) NOT NULL DEFAULT '' COMMENT '备注',
  `capture_user_id` smallint(5) unsigned DEFAULT NULL COMMENT '操作人uid',
  `create_time` timestamp NULL DEFAULT NULL COMMENT '创建时间',
  `update_time` timestamp NULL DEFAULT NULL COMMENT '修改时间 ',
  PRIMARY KEY (`id`),
  UNIQUE KEY `stock_allocatione_id` (`stock_allocatione_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='仓库调拨';

CREATE TABLE IF NOT EXISTS `wh_stock_allocation_detail` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `allocatione_id` int(11) NOT NULL COMMENT '调拨单id',
  `sku` varchar(255) NOT NULL DEFAULT '' COMMENT '产品编码SKU',
  `location_grid` varchar(45) NOT NULL DEFAULT '' COMMENT '仓库货位/格子',
  `qty` int(11) NOT NULL DEFAULT '0' COMMENT '调拨数量',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='仓库调拨明细';

CREATE TABLE IF NOT EXISTS `pc_1688_listing` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL COMMENT '1688店铺id',
  `company_name` varchar(255) NOT NULL DEFAULT '' COMMENT '1688店铺名称',
  `product_id` varchar(50) NOT NULL COMMENT '1688商品product_id',
  `sku_1688` varchar(255) NOT NULL COMMENT '1688商品skuCode 或 skuId 或 product_id',
  `spec_id` varchar(255) NOT NULL COMMENT '1688商品规格Id',
  `name` varchar(255) NOT NULL COMMENT '1688商品名称',
  `image_url` varchar(255) NOT NULL DEFAULT '' COMMENT '主图url',
  `pro_link` varchar(255) NOT NULL DEFAULT '' COMMENT '1688链接',
  `attributes` varchar(255) NOT NULL DEFAULT '' COMMENT '属性',
  `sku` varchar(255) NOT NULL COMMENT '本地商品sku',
  PRIMARY KEY (`id`),
  UNIQUE KEY `al1688_info` (`product_id`, `sku_1688`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='1688商品信息';