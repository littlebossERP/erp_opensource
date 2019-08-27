<?php

namespace eagle\modules\carrier\models;

use Yii;
use yii\behaviors\SerializeBehavior;
/**
 * This is the model class for table "matching_rule".
 *
 * @property integer $id
 */
class MatchingRule extends \eagle\models\carrier\MatchingRule
{
	//匹配项
	static $rules = [
// 				  'source' => '订单来源',
// 				  'site' => '站点',
// 				  'selleruserid' => '卖家账号',
// 				  'buyer_transportation_service' => '买家选择运输服务',
// 				  'warehouse' => '仓库',
// 				  'receiving_country' => '收件国家',
// 				  'total_amount' => '总金额',
// 				  'freight_amount' => '买家支付运费',
// 				  'total_weight' => '总重量',
// 				  'product_tag' => '商品标签',
				  
// 				  'items_location_city' => '物品所在城市',
				  'receiving_country' => '收件国家',
				  'receiving_provinces' => '收件州/省份',
				  'receiving_city' => '收件城市',
				  'skus' => 'SKU',
				  'sources' => '平台、账号、站点',
				  // Array(
				  // 'source' => '订单来源',
				  // 'site' => '站点',
				  // 	'selleruserid' => '卖家账号',
				  // )
				  'freight_amount' => '买家支付运费',
				  'buyer_transportation_service' => '买家选择运输服务',
				  'total_amount' => '总金额(USD旧)',
				  'total_amount_new' => '总金额(推荐新)',
				  'total_weight' => '总重量',
				  'product_tag' => '商品标签',
				  'postal_code' => '邮编',
				  'items_location_country' => '物品所在国家(ebay)',
				  'items_location_provinces' => '物品所在地区(ebay)',
				  // 		'warehouse' => '仓库',
  	];
	//订单来源
	static $source = [
			'ebay'=>'eBay',
			'amazon'=>'Amazon',
			'aliexpress'=>'Aliexpress',
			'wish'=>'Wish',
			'dhgate'=>'DHGate',
			'cdiscount'=>'Cdiscount',
			'lazada'=>'Lazada',
			'linio'=>'Linio',
			'jumia'=>'Jumia',
// 			'ensogo'=>'Ensogo',
	        'priceminister'=>'Priceminister',
	        'newegg'=>'newegg',
	        'customized'=>'自定义店铺',
	        'bonanza'=>'bonanza',
	        'rumall'=>'rumall',
	        'shopee'=>'shopee'
	];
	//订单来源
	static $is_active = [
			'0'=>'停用',
			'1'=>'启用',
			];
	public function behaviors(){
		return array(
				'SerializeBehavior' => array(
						'class' => SerializeBehavior::className(),
						'serialAttributes' => array('rules','source','site','selleruserid','buyer_transportation_service','warehouse','receiving_country','total_amount','freight_amount','total_weight','product_tag','postal_code','items_location_country'),
				)
		);
	}
}
