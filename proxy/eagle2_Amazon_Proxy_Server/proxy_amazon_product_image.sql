CREATE DATABASE IF NOT EXISTS `proxy` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;
CREATE TABLE IF NOT EXISTS `amazon_product_image` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `asin` varchar(50) CHARACTER SET utf8 NOT NULL COMMENT 'amazon的asin码',
  `medium_image_url` varchar(255) CHARACTER SET utf8 NOT NULL COMMENT '160*160 产品图片url',
  `marketplace_id` varchar(20) CHARACTER SET utf8 NOT NULL,
  `update_time` int(11) NOT NULL COMMENT '最近更新时间,时间戳',
  PRIMARY KEY (`id`),
  KEY `asin` (`asin`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;