-- phpMyAdmin SQL Dump
-- version 3.4.10.1deb1
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Sep 02, 2015 at 03:06 PM
-- Server version: 5.5.43
-- PHP Version: 5.3.10-1ubuntu3.18

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Database: `managedb_queue`
--

-- --------------------------------------------------------

--
-- Table structure for table `tracker_api_queue`
--

DROP TABLE IF EXISTS `tracker_api_queue`;
CREATE TABLE IF NOT EXISTS `tracker_api_queue` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `priority` tinyint(4) NOT NULL DEFAULT '5' COMMENT '优先级1-5， 1 最高，5最低',
  `puid` int(10) NOT NULL DEFAULT '0' COMMENT '帐套ID, user_x',
  `track_no` varchar(100) NOT NULL COMMENT 'tracking number',
  `status` varchar(30) NOT NULL COMMENT '请求状态,S,P,C,R,F',
  `candidate_carriers` text COMMENT '可能的首选carrier，通过智能判断，得到的候选carrier，多个由逗号分隔',
  `selected_carrier` int(11) DEFAULT '-100' COMMENT '被选中的carrier，多个candidate中被选上作为该tracking code实际carrier的判定',
  `create_time` datetime NOT NULL COMMENT '创建时间',
  `update_time` datetime NOT NULL COMMENT '更新时间',
  `run_time` smallint(6) NOT NULL DEFAULT '0' COMMENT 'api运行得到返回的时间,单位是秒',
  `try_count` tinyint(4) NOT NULL DEFAULT '0',
  `addinfo` varchar(250) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  KEY `status` (`status`,`track_no`),
  KEY `track_no` (`track_no`),
  KEY `status_2` (`status`,`priority`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=10861125 ;

-- --------------------------------------------------------

--
-- Table structure for table `tracker_api_sub_queue`
--

DROP TABLE IF EXISTS `tracker_api_sub_queue`;
CREATE TABLE IF NOT EXISTS `tracker_api_sub_queue` (
  `sub_id` int(10) NOT NULL AUTO_INCREMENT,
  `main_queue_id` int(11) NOT NULL COMMENT '这个sub queue请求是由哪个main quque生成的',
  `puid` int(10) NOT NULL DEFAULT '0' COMMENT '帐套ID, user_x',
  `track_no` varchar(100) NOT NULL COMMENT 'tracking number',
  `carrier_type` int(11) NOT NULL COMMENT '物流类型代号，  0=全球邮政，100002=UPS，100001=DHL，100003=Fedex，100004=TNT，100007=DPD，100010=DPD(UK)，100011=One World，100005=GLS，100012=顺丰速运，100008=EShipper，100009=Toll，100006=Aramex，190002=飞特物流，190008=云途物流，190011=百千诚物流，190007=俄速递，190009=快达物流，190003=华翰物流，190012=燕文物流，190013=淼信国际，190014=俄易达，190015=俄速通，190017=俄通收，190016=俄顺达',
  `sub_queue_status` varchar(30) NOT NULL COMMENT '请求状态,S,P,C,R,F',
  `result` text COMMENT '返回的值，如果是不成功，写入的是错误message',
  `create_time` datetime NOT NULL COMMENT '创建时间',
  `update_time` datetime NOT NULL COMMENT '更新时间',
  `run_time` smallint(6) NOT NULL DEFAULT '0' COMMENT 'api运行得到返回的时间,单位是秒',
  `addinfo` text,
  PRIMARY KEY (`sub_id`),
  UNIQUE KEY `main_queue_and_carrier` (`main_queue_id`,`carrier_type`),
  KEY `track_no` (`track_no`),
  KEY `sub_queue_status` (`sub_queue_status`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=12291950 ;

-- --------------------------------------------------------

--
-- Table structure for table `tracker_generate_request2queue`
--

DROP TABLE IF EXISTS `tracker_generate_request2queue`;
CREATE TABLE IF NOT EXISTS `tracker_generate_request2queue` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `puid` int(11) NOT NULL DEFAULT '0',
  `track_no` varchar(100) NOT NULL DEFAULT ' ',
  `create_time` datetime NOT NULL,
  `status` char(1) NOT NULL DEFAULT 'P' COMMENT 'P,S,C, C的会被删除',
  `user_require_update` char(1) NOT NULL DEFAULT '' COMMENT '是否用户在线要求update',
  PRIMARY KEY (`id`),
  KEY `ind_U` (`puid`,`track_no`)
) ENGINE=MEMORY  DEFAULT CHARSET=utf8 MAX_ROWS=2000000 COMMENT='用来通知异步job，帮忙做生成Tracking Queue的任务' AUTO_INCREMENT=12106050 ;

-- --------------------------------------------------------

--
-- Table structure for table `tracker_gen_request_for_puid`
--

DROP TABLE IF EXISTS `tracker_gen_request_for_puid`;
CREATE TABLE IF NOT EXISTS `tracker_gen_request_for_puid` (
  `puid` int(11) NOT NULL,
  `create_time` datetime NOT NULL,
  `status` char(1) NOT NULL COMMENT 'P,S,C,F, status C will be purged immediately',
  UNIQUE KEY `puid` (`puid`,`status`)
) ENGINE=MEMORY DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `ut_sys_invoke_jrn`
--

DROP TABLE IF EXISTS `ut_sys_invoke_jrn`;
CREATE TABLE IF NOT EXISTS `ut_sys_invoke_jrn` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'journal id',
  `create_time` datetime DEFAULT NULL,
  `process_id` bigint(20) NOT NULL DEFAULT '0' COMMENT 'process id, if the invoke is by a same php thread. they will share a same job id\nSo that we can know the serial func invokings are for a same process / user operation',
  `module` enum('Amazon','Catalog','Customer','Delivery','Finance','Inventory','Order','Permission','Platform','Purchase','Report','Ticket','Message','Wish','wish') NOT NULL COMMENT 'module name,supported values are:''Catalog'',''Customer'',''Delivery'',''Finance'',''Inventory'',''Order'',''Permission'',''Platform'',''Purchase'',''Report'',''Ticket''',
  `class` varchar(145) DEFAULT '' COMMENT 'class being called',
  `function` varchar(145) DEFAULT '' COMMENT 'the function name called?',
  `param_1` text COMMENT 'parameters being called, in Jason format if it is passed as array',
  `param_2` text COMMENT 'parameters being called, in Jason format if it is passed as array',
  `param_3` text COMMENT 'parameters being called, in Jason format if it is passed as array',
  `param_4` text COMMENT 'parameters being called, in Jason format if it is passed as array',
  `param_5` text COMMENT 'parameters being called, in Jason format if it is passed as array',
  `param_6` text COMMENT 'parameters being called, in Jason format if it is passed as array',
  `param_7` text COMMENT 'parameters being called, in Jason format if it is passed as array',
  `param_8` text COMMENT 'parameters being called, in Jason format if it is passed as array',
  `param_9` text COMMENT 'parameters being called, in Jason format if it is passed as array',
  `param_10` text COMMENT 'parameters being called, in Jason format if it is passed as array',
  `return_code` text,
  PRIMARY KEY (`id`),
  KEY `ind1` (`process_id`),
  KEY `ind2` (`function`),
  KEY `index4` (`create_time`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1673884 ;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

DROP TABLE IF EXISTS `queue_dhgate_getorder`;
CREATE TABLE IF NOT EXISTS `queue_dhgate_getorder` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键',
  `uid` int(10) NOT NULL COMMENT '小老板主账号ID',
  `dhgate_uid` int(10) NOT NULL COMMENT '敦煌账户表ID',
  `status` tinyint(2) NOT NULL COMMENT '同步状态',
  `order_status` varchar(50) DEFAULT NULL,
  `orderid` bigint(20) NOT NULL COMMENT '敦煌订单号',
  `times` tinyint(2) NOT NULL COMMENT '失败次数',
  `order_info` text COMMENT '订单列表信息',
  `last_time` int(11) DEFAULT NULL COMMENT '最后同步时间',
  `gmtcreate` int(11) NOT NULL COMMENT '敦煌订单创建时间',
  `message` text COMMENT '错误信息',
  `create_time` int(11) DEFAULT NULL COMMENT '创建时间',
  `update_time` int(11) DEFAULT NULL COMMENT '修改时间',
  `type` tinyint(1) DEFAULT NULL COMMENT '订单同步类型',
  `is_active` tinyint(1) NOT NULL DEFAULT '1' COMMENT '是否启用',
  PRIMARY KEY (`id`),
  KEY `status` (`status`),
  KEY `orderid` (`orderid`),
  KEY `last_time` (`last_time`),
  KEY `index_name` (`order_status`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 ;

DROP TABLE IF EXISTS `queue_dhgate_pendingorder`;
CREATE TABLE IF NOT EXISTS `queue_dhgate_pendingorder` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键',
  `uid` int(10) NOT NULL COMMENT '小老板主账号ID',
  `dhgate_uid` int(10) NOT NULL COMMENT '敦煌账户表ID',
  `status` tinyint(2) NOT NULL COMMENT '同步状态',
  `order_status` varchar(50) DEFAULT NULL,
  `orderid` bigint(20) NOT NULL COMMENT '敦煌订单号',
  `times` tinyint(2) NOT NULL COMMENT '失败次数',
  `order_info` text COMMENT '订单列表信息',
  `last_time` int(11) DEFAULT NULL COMMENT '最后同步时间',
  `gmtcreate` int(11) NOT NULL COMMENT '敦煌订单创建时间',
  `message` text COMMENT '错误信息',
  `next_execute_time` int(11) DEFAULT NULL COMMENT '下一次执行时间',
  `create_time` int(11) DEFAULT NULL COMMENT '创建时间',
  `update_time` int(11) DEFAULT NULL COMMENT '修改时间',
  `type` tinyint(1) DEFAULT NULL COMMENT '订单同步类型',
  `is_active` tinyint(1) NOT NULL DEFAULT '1' COMMENT '是否启用',
  PRIMARY KEY (`id`),
  KEY `status` (`status`),
  KEY `orderid` (`orderid`),
  KEY `last_time` (`last_time`),
  KEY `index_name` (`order_status`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;


CREATE TABLE IF NOT EXISTS `lt_customized_recommended_prod` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `puid` int(11) unsigned NOT NULL,
  `seller_id` varchar(50) CHARACTER SET utf8 NOT NULL,
  `platform` varchar(50) CHARACTER SET utf8 NOT NULL,
  `product_name` varchar(255) CHARACTER SET utf8 NOT NULL,
  `title` varchar(255) CHARACTER SET utf8 NOT NULL,
  `photo_url` varchar(255) CHARACTER SET utf8 NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `currency` char(3) CHARACTER SET utf8 NOT NULL,
  `sku` varchar(50) CHARACTER SET utf8 DEFAULT NULL,
  `comment` varchar(255) CHARACTER SET utf8 DEFAULT NULL,
  `group_id` int(11) unsigned DEFAULT NULL,
  `addi_info` text CHARACTER SET utf8,
  `create_time` int(11) NOT NULL DEFAULT '0',
  `update_time` int(11) NOT NULL DEFAULT '0',
  `product_url` varchar(255) CHARACTER SET utf8 NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1;

CREATE TABLE IF NOT EXISTS `lt_customized_recommended_group` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `puid` int(11) unsigned NOT NULL,
  `seller_id` varchar(50) CHARACTER SET utf8 NOT NULL,
  `platform` varchar(50) CHARACTER SET utf8 NOT NULL,
  `group_name` varchar(255) CHARACTER SET utf8 NOT NULL,
  `group_comment` varchar(255) CHARACTER SET utf8 DEFAULT NULL,
  `addi_info` text CHARACTER SET utf8,
  `member_count` int(11) NOT NULL DEFAULT '0',
  `create_time` int(11) NOT NULL DEFAULT '0',
  `update_time` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1;

CREATE TABLE IF NOT EXISTS `ebay_item_photo_queue` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '主键',
  `itemid` varchar(255) CHARACTER SET latin1 NOT NULL COMMENT 'ebay item id',
  `product_attributes` varchar(255) CHARACTER SET latin1 NOT NULL DEFAULT '' COMMENT '商品属性',
  `status` char(1) CHARACTER SET latin1 NOT NULL DEFAULT 'P' COMMENT '状态P为pending ， S为Submit , C 为Complete , R 为Retry , F为Failure',
  `photo_url` text COMMENT '商品图片',
  `create_time` datetime NOT NULL COMMENT '创建时间',
  `success_time` datetime DEFAULT NULL COMMENT '成功获取时间',
  `expire_time` datetime DEFAULT NULL COMMENT '过期时间',
  `retry_count` int(11) NOT NULL DEFAULT '0' COMMENT '重试次数',
  `puid` int(11) NOT NULL COMMENT '用户uid',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COMMENT='ebay商品图片映射表' ;

CREATE TABLE IF NOT EXISTS `import_ensogo_listing` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `aliexpress_category` varchar(255) DEFAULT NULL COMMENT '速卖通分类',
  `ensogo_category` varchar(255) DEFAULT NULL COMMENT 'ensogo分类',
  `ensogo_store` varchar(255) DEFAULT NULL COMMENT '发布店铺',
  `parent_sku` varchar(255) DEFAULT NULL COMMENT '父SKU',
  `sku` varchar(255) DEFAULT NULL COMMENT '子SKU',
  `subject` varchar(255) DEFAULT NULL COMMENT '产品标题',
  `color` varchar(255) DEFAULT NULL COMMENT '颜色',
  `size` varchar(255) DEFAULT NULL COMMENT '尺寸',
  `tag` varchar(255) DEFAULT NULL COMMENT '产品标签(用英文逗号[,]隔开)',
  `description` text COMMENT '产品描述',
  `market_price` float(10,2) NOT NULL DEFAULT '0.00' COMMENT '市场价($)',
  `price` float(10,2) NOT NULL DEFAULT '0.00' COMMENT '售价($)',
  `stock` int(10) NOT NULL DEFAULT '0' COMMENT '库存',
  `delivery_fee` float(10,2) NOT NULL DEFAULT '0.00' COMMENT '运费($)',
  `delivery_duration` varchar(255) NOT NULL DEFAULT '15-30' COMMENT '运输时间(天)',
  `brand` varchar(255) DEFAULT NULL COMMENT '品牌',
  `upc` varchar(255) DEFAULT NULL COMMENT 'UPC（通用产品代码）',
  `url` varchar(255) DEFAULT NULL COMMENT 'Landing Page URL',
  `main_image` varchar(255) DEFAULT NULL COMMENT '产品主图链接',
  `imageurl_1` varchar(255) DEFAULT NULL COMMENT '附图链接1',  
  `imageurl_2` varchar(255) DEFAULT NULL COMMENT '附图链接2',
  `imageurl_3` varchar(255) DEFAULT NULL COMMENT '附图链接3',
  `imageurl_4` varchar(255) DEFAULT NULL COMMENT '附图链接4',
  `imageurl_5` varchar(255) DEFAULT NULL COMMENT '附图链接5',
  `imageurl_6` varchar(255) DEFAULT NULL COMMENT '附图链接6',
  `imageurl_7` varchar(255) DEFAULT NULL COMMENT '附图链接7',
  `imageurl_8` varchar(255) DEFAULT NULL COMMENT '附图链接8',
  `imageurl_9` varchar(255) DEFAULT NULL COMMENT '附图链接9',
  `imageurl_10` varchar(255) DEFAULT NULL COMMENT '附图链接10',
  `batch_num` varchar(255) DEFAULT NULL COMMENT '批次号',
  `puid` int(10) NOT NULL,
  `status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '0导入中，1待处理，2处理中，3刊登成功，4刊登失败',
  `error_message` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `batch_num` (`batch_num`,`puid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `queue_aliexpress_retry_order` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键',
  `uid` int(10) NOT NULL COMMENT '小老板主账号ID',
  `sellerloginid` varchar(100) NOT NULL COMMENT '速卖通登陆账户',
  `aliexpress_uid` int(10) NOT NULL COMMENT '速卖通账户表ID',
  `status` tinyint(2) NOT NULL COMMENT '同步状态',
  `type` tinyint(1) DEFAULT NULL COMMENT '订单同步类型',
  `order_status` varchar(50) DEFAULT NULL,
  `orderid` bigint(20) NOT NULL COMMENT '速卖通订单号',
  `times` tinyint(2) NOT NULL COMMENT '失败次数',
  `order_info` text COMMENT '订单列表信息',
  `last_time` int(11) DEFAULT NULL COMMENT '最后同步时间',
  `gmtcreate` int(11) NOT NULL COMMENT '速卖通订单创建时间',
  `message` text COMMENT '错误信息',
  `create_time` int(11) DEFAULT NULL COMMENT '创建时间',
  `update_time` int(11) DEFAULT NULL COMMENT '修改时间',
  `next_time` int(11) DEFAULT '0' COMMENT '待重试时间',
  PRIMARY KEY (`id`),
  KEY `uid` (`uid`),
  KEY `sellerloginid` (`sellerloginid`),
  KEY `status` (`status`),
  KEY `type` (`type`),
  KEY `orderid` (`orderid`),
  KEY `times` (`times`),
  KEY `next_time` (`next_time`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 ;

CREATE TABLE IF NOT EXISTS `queue_aliexpress_retry_account` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键',
  `uid` int(10) NOT NULL COMMENT '小老板主账号ID',
  `sellerloginid` varchar(100) NOT NULL COMMENT '速卖通登陆账户',
  `aliexpress_uid` int(10) NOT NULL COMMENT '速卖通账户表ID',
  `status` tinyint(2) NOT NULL COMMENT '同步状态',
  `type` tinyint(1) DEFAULT NULL COMMENT '订单同步类型',
  `order_status` varchar(50) DEFAULT NULL,
  `orderid` bigint(20) NOT NULL COMMENT '速卖通订单号',
  `times` tinyint(2) NOT NULL COMMENT '失败次数',
  `order_info` text COMMENT '订单列表信息',
  `last_time` int(11) DEFAULT NULL COMMENT '最后同步时间',
  `gmtcreate` int(11) NOT NULL COMMENT '速卖通订单创建时间',
  `message` text COMMENT '错误信息',
  `create_time` int(11) DEFAULT NULL COMMENT '创建时间',
  `update_time` int(11) DEFAULT NULL COMMENT '修改时间',
  `next_time` int(11) DEFAULT '0' COMMENT '待重试时间',
  PRIMARY KEY (`id`),
  KEY `uid` (`uid`),
  KEY `sellerloginid` (`sellerloginid`),
  KEY `status` (`status`),
  KEY `type` (`type`),
  KEY `orderid` (`orderid`),
  KEY `times` (`times`),
  KEY `next_time` (`next_time`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 ;

CREATE TABLE IF NOT EXISTS `queue_aliexpress_retry_account_info` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键',
  `uid` int(10) NOT NULL COMMENT '小老板主账号ID',
  `sellerloginid` varchar(100) NOT NULL COMMENT '速卖通登陆账户',
  `orderid` bigint(20) NOT NULL COMMENT '速卖通订单号',
  `times` tinyint(2) NOT NULL COMMENT '失败次数',
  `last_time` int(11) DEFAULT NULL COMMENT '最后同步时间',
  `message` text COMMENT '错误信息',
  `create_time` int(11) DEFAULT NULL COMMENT '创建时间',
  `update_time` int(11) DEFAULT NULL COMMENT '修改时间',
  `next_time` int(11) DEFAULT '0' COMMENT '待重试时间',
  PRIMARY KEY (`id`),
  KEY `uid` (`uid`),
  KEY `sellerloginid` (`sellerloginid`),
  KEY `orderid` (`orderid`),
  KEY `times` (`times`),
  KEY `next_time` (`next_time`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 ;

CREATE TABLE IF NOT EXISTS `queue_aliexpress_retry_account_log` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键',
  `uid` int(10) NOT NULL COMMENT '小老板主账号ID',
  `sellerloginid` varchar(100) NOT NULL COMMENT '速卖通登陆账户',
  `orderid` bigint(20) NOT NULL COMMENT '速卖通订单号',
  `message` text COMMENT '错误信息',
  `create_time` int(11) DEFAULT NULL COMMENT '创建时间',
  PRIMARY KEY (`id`),
  KEY `uid` (`uid`),
  KEY `sellerloginid` (`sellerloginid`),
  KEY `orderid` (`orderid`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 ;

CREATE TABLE IF NOT EXISTS `queue_aliexpress_getorder_v2` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键',
  `uid` int(10) NOT NULL COMMENT '小老板主账号ID',
  `sellerloginid` varchar(100) NOT NULL COMMENT '速卖通登陆账户',
  `aliexpress_uid` int(10) NOT NULL COMMENT '速卖通账户表ID',
  `status` tinyint(2) NOT NULL COMMENT '同步状态',
  `type` tinyint(1) DEFAULT NULL COMMENT '订单同步类型',
  `order_status` varchar(50) DEFAULT NULL,
  `orderid` bigint(20) NOT NULL COMMENT '速卖通订单号',
  `times` tinyint(2) NOT NULL COMMENT '失败次数',
  `order_info` text COMMENT '订单列表信息',
  `last_time` int(11) DEFAULT NULL COMMENT '最后同步时间',
  `gmtcreate` int(11) NOT NULL COMMENT '速卖通订单创建时间',
  `message` text COMMENT '错误信息',
  `create_time` int(11) DEFAULT NULL COMMENT '创建时间',
  `update_time` int(11) DEFAULT NULL COMMENT '修改时间',
  `next_time` int(11) DEFAULT '0' COMMENT '需要同步时间',
  PRIMARY KEY (`id`),
  KEY `sellerloginid` (`sellerloginid`),
  KEY `status` (`status`),
  KEY `type` (`type`),
  KEY `order_status` (`order_status`),
  KEY `orderid` (`orderid`),
  KEY `times` (`times`),
  KEY `last_time` (`last_time`),
  KEY `next_time` (`next_time`),
  KEY `olindex` (`order_status`,`last_time`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 ;

CREATE TABLE IF NOT EXISTS `queue_aliexpress_getorder4_v2` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键',
  `uid` int(10) NOT NULL COMMENT '小老板主账号ID',
  `sellerloginid` varchar(100) NOT NULL COMMENT '速卖通登陆账户',
  `aliexpress_uid` int(10) NOT NULL COMMENT '速卖通账户表ID',
  `order_status` varchar(50) DEFAULT NULL,
  `orderid` bigint(20) NOT NULL COMMENT '速卖通订单号',
  `order_info` text COMMENT '订单列表信息',
  `gmtcreate` int(11) NOT NULL COMMENT '速卖通订单创建时间',
  `create_time` int(11) DEFAULT NULL COMMENT '创建时间',
  `update_time` int(11) DEFAULT NULL COMMENT '修改时间',
  PRIMARY KEY (`id`),
  KEY `orderid` (`orderid`) USING BTREE,
  KEY `sellerloginid` (`sellerloginid`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 ;

CREATE TABLE IF NOT EXISTS `queue_aliexpress_auto_order` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sellerloginid` varchar(100) DEFAULT NULL COMMENT '速卖通店铺',
  `order_status` varchar(100) DEFAULT NULL COMMENT '订单当前状态',
  `order_id` bigint(20) NOT NULL DEFAULT '0' COMMENT 'SMT订单号',
  `order_change_time` datetime DEFAULT '0000-00-00 00:00:00' COMMENT '订单变更时间',
  `msg_id` bigint(20) NOT NULL DEFAULT '0' COMMENT '推送消息ID',
  `order_type` varchar(100) NOT NULL DEFAULT '' COMMENT '推送订单类型',
  `gmtBorn` bigint(11) NOT NULL DEFAULT '0' COMMENT '推送时间',
  `ajax_message` text COMMENT '推送原始信息',
  `status` int(11) NOT NULL COMMENT '状态信息 1未更新 2更新失败 3更新成功',
  `push_status` int(11) NOT NULL COMMENT '状态信息 1同步完成 2同步失败',
  `error_message` text NOT NULL COMMENT '更新错误信息',
  `create_time` datetime DEFAULT NULL COMMENT '创建时间',
  `update_time` datetime DEFAULT NULL COMMENT '更新时间',
  `last_time` int(11) DEFAULT NULL COMMENT '最后同步时间',
  `next_time` int(11) DEFAULT '0' COMMENT '需要同步时间',
  `is_lock` tinyint(4) NOT NULL DEFAULT '0' COMMENT '锁定 0未锁定 1锁定',
  PRIMARY KEY (`id`),
  KEY `order_status` (`order_status`) USING BTREE,
  KEY `is_lock` (`is_lock`) USING BTREE,
  KEY `sellerloginid` (`sellerloginid`) USING BTREE,
  KEY `order_id` (`order_id`) USING BTREE,
  KEY `oi` (`order_id`,`is_lock`),
  KEY `sni` (`status`,`next_time`,`is_lock`) USING BTREE,
  KEY `msg_id` (`msg_id`) USING BTREE
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COMMENT='订单推送表';


CREATE TABLE IF NOT EXISTS `queue_aliexpress_auto_order_v2` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sellerloginid` varchar(50) DEFAULT NULL COMMENT '速卖通店铺',
  `order_status` varchar(50) DEFAULT NULL COMMENT '订单当前状态',
  `last_status` varchar(50) DEFAULT NULL COMMENT '订单旧状态',
  `order_id` bigint(20) NOT NULL DEFAULT '0' COMMENT 'SMT订单号',
  `order_change_time` int(11) DEFAULT 0 COMMENT '订单变更时间',
  `msg_id` bigint(20) NOT NULL DEFAULT 0 COMMENT '推送消息ID',
  `order_type` varchar(50) NOT NULL DEFAULT '' COMMENT '推送订单类型',
  `gmtBorn` bigint(11) NOT NULL DEFAULT '0' COMMENT '推送时间',
  `ajax_message` text COMMENT '推送原始信息',
  `status` tinyint(2) NOT NULL COMMENT '同步状态',
  `message` text COMMENT '错误信息',
  `create_time` int(11) DEFAULT 0 COMMENT '创建时间',
  `update_time` int(11) DEFAULT 0 COMMENT '更新时间',
  `last_time` int(11) DEFAULT NULL COMMENT '最后同步时间',
  `next_time` int(11) DEFAULT 0 COMMENT '需要同步时间',
  `times` tinyint(2) NOT NULL DEFAULT 0 COMMENT '失败次数',
  PRIMARY KEY (`id`),
  KEY `order_status` (`order_status`) USING BTREE,
  KEY `status` (`status`) USING BTREE,
  KEY `sellerloginid` (`sellerloginid`) USING BTREE,
  KEY `order_id` (`order_id`) USING BTREE,
  KEY `sni` (`status`,`next_time`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='订单推送表v2' AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `queue_aliexpress_auto_order_error` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ajax_message` text COMMENT '推送原始信息',
  `message` text COMMENT '错误信息',
  `create_time` int(11) DEFAULT 0 COMMENT '创建时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='订单推送错误表' AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `pc_suggest_queue` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `puid` int(11) NOT NULL,
  `status` char(1) NOT NULL DEFAULT 'P',
  `create_time` datetime NOT NULL,
  `update_time` datetime DEFAULT NULL,
  `message` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `puid` (`puid`),
  KEY `status` (`status`)
) ENGINE=MEMORY  DEFAULT CHARSET=utf8 COMMENT='请求执行采购建议的计算，因为耗费内存比较多，所以做成异步执行';

CREATE TABLE IF NOT EXISTS `amazon_report_requset` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uid` int(11) NOT NULL,
  `merchant_id` varchar(50) NOT NULL,
  `marketplace_short` char(2) NOT NULL,
  `report_type` varchar(100) NOT NULL,
  `request_id` bigint(20) NOT NULL,
  `report_id` bigint(20) DEFAULT NULL COMMENT 'Amazon 执行后生成的report id',
  `process_status` char(2) NOT NULL COMMENT 'RS：Request Submit ； GS：report id Get Success；GF：report id Get Failed；RD：Report Done',
  `create_time` datetime NOT NULL,
  `update_time` datetime DEFAULT NULL,
  `next_get_report_id_time` datetime DEFAULT NULL COMMENT '下次尝试获取reportId的时间',
  `get_report_id_count` tinyint(2) NOT NULL DEFAULT '0' COMMENT '尝试获取reportId的次数',
  `next_get_report_data_time` datetime DEFAULT NULL COMMENT '下次尝试获取report内容的时间',
  `get_report_data_count` tinyint(2) NOT NULL DEFAULT '0' COMMENT '尝试获取report内容的次数',
  `report_contents` text COMMENT '报告结果',
  `app` varchar(500) DEFAULT NULL COMMENT '调用的模块/app，比如littleboss-fba-inventory,0so1-listing之类',
  PRIMARY KEY (`id`),
  KEY `request_id` (`request_id`,`report_type`),
  KEY `report_id` (`report_id`),
  KEY `uid` (`uid`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `amazon_listing_fetch_addi_info_queue` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `puid` int(11) NOT NULL,
  `merchant_id` varchar(100) NOT NULL,
  `marketplace_id` varchar(100) NOT NULL,
  `asin` varchar(255) NOT NULL,
  `seller_sku` varchar(255) NOT NULL,
  `process` int(2) NOT NULL DEFAULT '0' COMMENT '执行到哪一步：0未开始，1正在获取detail，2获取完detail，3正在下载图片，4图片下载完，5正在转存图片，6转存完成',
  `process_status` int(2) NOT NULL DEFAULT '0' COMMENT '当前process的执行结果，0未有结果或未执行，1执行成功，2执行出错',
  `callback` text COMMENT '获取信息成功后的回调函数',
  `prod_info` text COMMENT '获取到的商品详细信息json',
  `img_info` text COMMENT '获取到的图片信息json',
  `priority` int(11) NOT NULL DEFAULT '5' COMMENT '优先级',
  `err_cnt` int(11) DEFAULT '0',
  `err_msg` text,
  `create_time` datetime DEFAULT NULL,
  `update_time` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `puid` (`puid`,`merchant_id`,`marketplace_id`,`asin`,`process`,`process_status`),
  KEY `seller_id` (`seller_sku`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COMMENT='amazon线上listing等待获取详细信息的队列表，获取信息成功后执行callback，将相关信息回写到对应user的db' AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `queue_order_auto_check` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '主键',
  `puid` int(11) NOT NULL COMMENT '父uid',
  `order_id` int(11) NOT NULL COMMENT '小老板订单号',
  `status` char(1) NOT NULL COMMENT '检测执行状态，p为待执行，s为执行中，c为完成 F为失败',
  `retry_count` int(11) NOT NULL DEFAULT '0' COMMENT '重试次数',
  `priority` int(11) NOT NULL COMMENT '优先级',
  `create_time` int(11) NOT NULL COMMENT '创建时间',
  `update_time` int(11) NOT NULL COMMENT '更新时间',
  `run_time` int(11) NOT NULL DEFAULT '0' COMMENT '运行时间',
  `add_info` text COMMENT '额外信息',
  `next_time` int(11) DEFAULT '0' COMMENT '下次运行时间',
  PRIMARY KEY (`id`),
  KEY `us` (`puid`,`status`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COMMENT='订单自动检测队列表';

CREATE TABLE IF NOT EXISTS `queue_newegg_getorder` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '主键',
  `uid` int(11) NOT NULL COMMENT '小老板主账号ID',
  `sellerID` varchar(255) NOT NULL COMMENT 'newegg店铺SellerID',
  `status` tinyint(2) DEFAULT '0' COMMENT '状态',
  `order_source_order_id` int(11) DEFAULT NULL COMMENT '订单来源订单号',
  `order_status` tinyint(2) DEFAULT NULL COMMENT '订单平台状态',
  `order_info_md5` text COMMENT '订单信息md5值',
  `create_time` int(11) DEFAULT '0' COMMENT '创建时间',
  `update_time` int(11) DEFAULT '0' COMMENT '更新时间',
  `last_start_time` int(11) DEFAULT '0' COMMENT '最后执行时间',
  `last_finish_time` int(11) DEFAULT '0' COMMENT '最后成功执行时间',
  `error_times` tinyint(2) DEFAULT '0' COMMENT '错误次数',
  `message` text COMMENT '错误信息',
  `type` tinyint(1) DEFAULT '1' COMMENT '同步种类',
  `is_active` tinyint(1) DEFAULT '1' COMMENT '是否启用',
  PRIMARY KEY (`id`),
  KEY `uid` (`uid`,`sellerID`),
  KEY `order_source_order_id` (`order_source_order_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `queue_shopee_getorder` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键',
  `uid` int(10) NOT NULL COMMENT '小老板主账号ID',
  `shop_id` varchar(50) NOT NULL COMMENT 'shopee shopid',
  `site` varchar(10) NOT NULL COMMENT '站点',
  `shopee_uid` int(10) NOT NULL COMMENT 'shopee账户表ID',
  `is_active` tinyint(1) NOT NULL DEFAULT '1' COMMENT '是否启用',
  `status` tinyint(2) NOT NULL COMMENT '同步状态',
  `type` tinyint(1) DEFAULT NULL COMMENT '订单同步类型',
  `order_status` varchar(50) DEFAULT NULL,
  `orderid` varchar(50) NOT NULL COMMENT 'shopee订单号',
  `times` tinyint(2) NOT NULL COMMENT '失败次数',
  `last_time` int(11) DEFAULT NULL COMMENT '最后同步时间',
  `gmtupdate` int(11) NOT NULL COMMENT '订单更新时间',
  `message` text COMMENT '错误信息',
  `create_time` int(11) DEFAULT NULL COMMENT '创建时间',
  `update_time` int(11) DEFAULT NULL COMMENT '修改时间',
  `next_time` int(11) DEFAULT '0' COMMENT '需要同步时间',
  PRIMARY KEY (`id`),
  KEY `shop_id` (`shop_id`),
  KEY `status` (`status`),
  KEY `type` (`type`),
  KEY `order_status` (`order_status`),
  KEY `orderid` (`orderid`),
  KEY `times` (`times`),
  KEY `last_time` (`last_time`),
  KEY `next_time` (`next_time`),
  KEY `olindex` (`order_status`,`last_time`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 ;

CREATE TABLE IF NOT EXISTS `amazon_client_report` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uid` int(11) NOT NULL,
  `merchant_id` varchar(50) DEFAULT NULL COMMENT 'Amazon Merchant Id',
  `marketplace_id` varchar(50) DEFAULT NULL COMMENT '站点',
  `ASIN` varchar(50) DEFAULT NULL,
  `start_time` int(11) NOT NULL COMMENT '抓取开始时间',
  `end_time` int(11) DEFAULT NULL COMMENT '抓取结束时间',
  `status` int(3) unsigned NOT NULL DEFAULT '0' COMMENT '拉取状态，0：开始拉取 1: 成功 2: 失败',
  `message` text COMMENT '错误信息',
  `last_date` int(11) DEFAULT NULL COMMENT '抓取最新的日期',
  `type` int(2) NOT NULL DEFAULT '1' COMMENT '抓取类型，1:feedback; 2:review;',
  `post_data` text NOT NULL,
  `sum_count` int(11) NOT NULL DEFAULT '0' COMMENT '总数量',
  `read_count` int(11) NOT NULL DEFAULT '0' COMMENT '本次读取数量',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `export_excel_queue` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uid` int(11) NOT NULL,
  `name` varchar(30) DEFAULT NULL COMMENT '导出项目名字',
  `status` char(1) NOT NULL DEFAULT 'S' COMMENT 'S开始，E结束',
  `export_class` varchar(300) DEFAULT NULL COMMENT '导出Excel类名',
  `export_function` varchar(100) DEFAULT NULL COMMENT '导出Excel方法名',
  `create_time` datetime NOT NULL,
  `update_time` datetime DEFAULT NULL,
  `message` varchar(255) DEFAULT NULL,
  `param_value` text COMMENT '导出信息的索引key',
  `src_file_url` varchar(100) DEFAULT NULL COMMENT '导出Excel路劲',
  `export_count` int(11) DEFAULT NULL COMMENT '导出行数',
  `taking_time` int(11) DEFAULT NULL COMMENT '耗时 秒',
  `use_module` char(2) DEFAULT NULL COMMENT '调用的模块，I商品、O订单、P采购、W仓库、F发货',
  `next_time` int(11) DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `uid` (`uid`),
  KEY `status` (`status`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `export_excel_resize_img` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `src_img_md5` varchar(128) CHARACTER SET utf8 NOT NULL COMMENT '原始URL的MD5',
  `src_img_url` varchar(1000) CHARACTER SET utf8 NOT NULL,
  `resize` varchar(50) CHARACTER SET utf8 NOT NULL COMMENT '例如：150x150',
  `resize_file_name` varchar(1000) CHARACTER SET utf8 NOT NULL,
  `type` varchar(10) CHARACTER SET utf8 NOT NULL COMMENT 'jpg/jpeg/png/gif',
  `date_ym` int(10) NOT NULL COMMENT '日期年月，如201612',
  PRIMARY KEY (`id`),
  KEY `src_img_url` (`src_img_url`(255),`resize`,`date_ym`),
  KEY `src_img_md5` (`src_img_md5`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 COMMENT='excel导出图片时的resize图片mapping表';


CREATE TABLE IF NOT EXISTS `queue_manual_order_sync` (
`id` int(11) NOT NULL AUTO_INCREMENT COMMENT '编号',
  `puid` int(10) NOT NULL COMMENT '帐套ID, user_x',
  `sellerloginid` varchar(100) NOT NULL COMMENT '速卖通登陆账号',
  `order_id` varchar(50) NOT NULL COMMENT '订单id',
  `status` char(1) NOT NULL COMMENT '状态,S,P,C,R,F , S为submit , P为pending,C为completed, R 为retry , F为failure',
  `platform` varchar(30) NOT NULL COMMENT '平台 ebay, aliexpress,wish',
  `create_time` datetime NOT NULL COMMENT '创建时间',
  `update_time` datetime DEFAULT NULL COMMENT '更新时间',
  `addi_info` text COMMENT '额外信息',
  `runtime` smallint(6) NOT NULL DEFAULT '0' COMMENT '运行得到返回的时间,单位是秒',
  `retry_count` int(11) NOT NULL DEFAULT '0' COMMENT '重试次数',
  `priority` int(1) NOT NULL DEFAULT '3' COMMENT '优先级1-5， 1 最高，5最低',
  `err_msg` text COMMENT '错误提示',
  PRIMARY KEY (`id`), 
  KEY `ss` (`status`,`priority`), 
  KEY `order_id` (`order_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='速卖通手动同步订单表';

CREATE TABLE IF NOT EXISTS `aliexpress_ajax_api_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sellerloginid` varchar(100) DEFAULT NULL COMMENT '速卖通店铺',
  `order_info` longtext DEFAULT NULL COMMENT '订单更新信息',
  `ajax_message` longtext DEFAULT NULL COMMENT '推送原始信息',
  `status` int(11) NOT NULL COMMENT '状态信息',
  `create_time` datetime DEFAULT NULL,
  `update_time` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;


CREATE TABLE IF NOT EXISTS `app_it_dash_board` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `app` varchar(200) NOT NULL DEFAULT 'CDOMS',
  `info_type` varchar(200) NOT NULL,
  `error_level` varchar(200) NOT NULL,
  `error_message` text NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT '0',
  `addinfo` text NOT NULL,
  `addinfo2` text NOT NULL,
  `the_day` date NOT NULL,
  `update_time` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `AA` (`app`,`info_type`,`error_level`,`the_day`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1;


CREATE TABLE IF NOT EXISTS `amazon_temp_orderid_queue_highpriority` (
  `order_id` varchar(50) NOT NULL,
  `saas_platform_autosync_id` int(11) NOT NULL COMMENT 'saas_amazon_autosync_v2表中的id',
  `type` varchar(10) NOT NULL DEFAULT 'MFN' COMMENT '订单类型 --"MFN" : Manually Fulfillment "AFN" : Amazon Fulfillment (FBA)',
  `process_status` tinyint(3) NOT NULL,
  `error_count` smallint(4) NOT NULL COMMENT '连续错误次数统计',
  `error_message` text COMMENT '错误信息',
  `create_time` int(11) DEFAULT NULL,
  `update_time` int(11) DEFAULT NULL,
  `order_header_json` text NOT NULL COMMENT '订单的头信息',
  `puid` int(11) NOT NULL,
  PRIMARY KEY (`order_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


CREATE TABLE IF NOT EXISTS `amazon_temp_orderid_queue_lowpriority` (
  `order_id` varchar(50) NOT NULL,
  `saas_platform_autosync_id` int(11) NOT NULL COMMENT 'saas_amazon_autosync_v2表中的id',
  `type` varchar(10) NOT NULL DEFAULT 'MFN' COMMENT '订单类型 --"MFN" : Manually Fulfillment "AFN" : Amazon Fulfillment (FBA)',
  `process_status` tinyint(3) NOT NULL,
  `error_count` smallint(4) NOT NULL COMMENT '连续错误次数统计',
  `error_message` text COMMENT '错误信息',
  `create_time` int(11) DEFAULT NULL,
  `update_time` int(11) DEFAULT NULL,
  `order_header_json` text NOT NULL COMMENT '订单的头信息',
  `puid` int(11) NOT NULL,
  PRIMARY KEY (`order_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


CREATE TABLE IF NOT EXISTS `carrier_tcpdf_img` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `photo_primary` text NOT NULL COMMENT '主图url',
  `photo_file_path` text COMMENT '缓存的图片路径',
  `puid` int(11) DEFAULT '0' COMMENT 'User ID',
  `create_time` int(11) DEFAULT '0' COMMENT '创建时间',
  `run_status` tinyint(2) DEFAULT '0' COMMENT '运行状态 1表示运行中,2表示已经完成不用再执行,',
  `times` tinyint(2) DEFAULT '0' COMMENT '失败次数',
  `update_time` int(11) DEFAULT '0' COMMENT '最后一次更新时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COMMENT='用于tcpdf读取items图片时超时的中间记录表';


CREATE TABLE IF NOT EXISTS `ut_app_push_queue` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `priority` tinyint(4) NOT NULL COMMENT '1-10, 1最低优先级，10最高',
  `status` char(1) NOT NULL COMMENT 'Pending,Complete,Failed',
  `from_app` varchar(100) NOT NULL,
  `to_app` varchar(100) NOT NULL,
  `command_line` text CHARACTER SET utf8 NOT NULL,
  `puid` int(11) NOT NULL,
  `create_time` datetime NOT NULL,
  `update_time` datetime DEFAULT NULL,
  `run_time` int(11) NOT NULL COMMENT '执行这个事件func的耗时，ms',
  `result` text CHARACTER SET utf8,
  PRIMARY KEY (`id`),
  KEY `oui` (`puid`,`status`,`create_time`),
  KEY `sdfasd` (`status`,`priority`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 COMMENT='模块间需要异步推送的请求，command line里面是执行那个 推送信息的function';


CREATE TABLE IF NOT EXISTS `hc_collect_request_queue` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '编号',
  `puid` int(10) NOT NULL COMMENT '帐套ID, user_x',
  `product_id` varchar(50) NOT NULL COMMENT '目标关键字',
  `field_list` text NOT NULL COMMENT '抓取目标字段',
  `status` char(1) NOT NULL COMMENT '状态,S,P,C,R,F , S为submit , P为pending,C为completed, R 为retry , F为failure',
  `platform` varchar(30) NOT NULL COMMENT '平台 ebay, aliexpress,wish',
  `create_time` datetime NOT NULL COMMENT '创建时间',
  `update_time` datetime DEFAULT NULL COMMENT '更新时间',
  `addi_info` text COMMENT '额外信息',
  `runtime` smallint(6) NOT NULL DEFAULT '0' COMMENT '运行得到返回的时间,单位是秒',
  `retry_count` int(11) NOT NULL DEFAULT '0' COMMENT '重试次数',
  `priority` int(1) NOT NULL DEFAULT '3' COMMENT '优先级1-5， 1 最高，5最低（0为父商品）',
  `err_msg` text COMMENT '错误提示',
  `role_id` int(11) NOT NULL DEFAULT '0' COMMENT '使用的规则',
  `subsite` varchar(20) DEFAULT NULL COMMENT '子站(UK,US)',
  `callback_function` text COMMENT '执行完成的回调',
  `step` varchar(50) DEFAULT NULL COMMENT '处理的步骤',
  `result` text COMMENT '分析结果',
  PRIMARY KEY (`id`),
  KEY `ss` (`status`,`priority`),
  KEY `product_id` (`product_id`,`status`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COMMENT='抓取信息队列表';


CREATE TABLE IF NOT EXISTS `cdot_followed_product` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `puid` int(11) NOT NULL COMMENT '所有user库都会在此报告关注的商品',
  `product_id` varchar(50) CHARACTER SET utf8 NOT NULL COMMENT '例如 AUC456879846546',
  `ean` char(13) CHARACTER SET utf8 DEFAULT NULL,
  `create_time` datetime NOT NULL COMMENT '记录创建时间',
  `update_time` datetime NOT NULL COMMENT '该记录被修改的时间',
  `last_sync_time` datetime NOT NULL COMMENT '上次尝试同步的时间',
  `last_success_sync_time` datetime NOT NULL COMMENT '上次成功同步的时间',
  `error_message` text CHARACTER SET utf8 NOT NULL COMMENT '失败原因，如果成功同步，set为空白',
  `add_info` text CHARACTER SET utf8 NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sdf` (`product_id`,`ean`),
  KEY `syncTime` (`last_success_sync_time`,`last_sync_time`),
  KEY `puidAndProductId` (`product_id`,`puid`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 COMMENT='所有user库都会在此报告关注的商品，定期job会从这个表决定哪些需要更新';


CREATE TABLE IF NOT EXISTS `cdot_hotsale_product` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `puid` int(11) NOT NULL COMMENT '所有user库都会在此报告关注的商品',
  `product_id` varchar(50) CHARACTER SET utf8 NOT NULL COMMENT '例如 AUC456879846546',
  `ean` char(13) CHARACTER SET utf8 DEFAULT NULL,
  `create_time` datetime NOT NULL COMMENT '记录创建时间',
  `update_time` datetime NOT NULL COMMENT '该记录被修改的时间',
  `last_sync_time` datetime NOT NULL COMMENT '上次尝试同步的时间',
  `last_success_sync_time` datetime NOT NULL COMMENT '上次成功同步的时间',
  `error_message` text CHARACTER SET utf8 NOT NULL COMMENT '失败原因，如果成功同步，set为空白',
  `add_info` text CHARACTER SET utf8 NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `puidAndProductId` (`product_id`,`puid`),
  KEY `sdf` (`product_id`,`ean`),
  KEY `syncTime` (`last_success_sync_time`,`last_sync_time`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 COMMENT='所有user库都会在此报告关注的商品，定期job会从这个表决定哪些需要更新';


CREATE TABLE IF NOT EXISTS `edm_email_send_queue` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `puid` int(11) NOT NULL,
  `history_id` int(11) NOT NULL COMMENT '对应user库的history表id',
  `send_from` varchar(255) NOT NULL COMMENT '通过什么邮箱发送',
  `send_to` varchar(255) NOT NULL COMMENT '发送给什么邮箱',
  `act_name` varchar(255) NOT NULL,
  `status` int(11) NOT NULL DEFAULT '0' COMMENT '状态：0未处理；1处理成功；2处理失败',
  `error_message` text,
  `create_time` datetime NOT NULL,
  `update_time` datetime DEFAULT NULL,
  `priority` int(11) NOT NULL DEFAULT '3',
  `addi_info` text COMMENT '记录标题-subject，内容-body，发送人名-from_ame；等信息',
  `pending_send_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '预期发送时间，空时为立即发送',
  `retry_count` int(2) NOT NULL DEFAULT '0' COMMENT '重试次数',
  PRIMARY KEY (`id`),
  KEY `puid` (`puid`,`send_from`,`send_to`,`status`,`priority`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;









