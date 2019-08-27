CREATE DATABASE IF NOT EXISTS `proxy` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;
CREATE TABLE IF NOT EXISTS `lazada_product_image`(
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ShopSku` varchar(50) CHARACTER SET utf8 NOT NULL COMMENT '产品在lazada 站点id',
  `MainImage` varchar(255) CHARACTER SET utf8 NOT NULL COMMENT '产品图片url',
  `marketplace` varchar(20) CHARACTER SET utf8 NOT NULL,
  `update_time` int(11) NOT NULL COMMENT '最近更新时间,时间戳',
  PRIMARY KEY (`id`),
  KEY `ShopSku` (`ShopSku`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;