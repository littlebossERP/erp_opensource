<?php
namespace common\helpers;

use Exception;

class Helper_Siteinfo{
	//获取ebay的基本信息	
	//注释的为用的比较少的平台,需要使用时，取消注释即可
	public static function getEbaySiteIdList($key = 'no',$name='') {
		$list = array(
			array(
				'no' => 0,
				'code' => 'US',
				'en' => 'US',
				'zh' => '美国',
				'currency'=>'USD',
			),
			array(
				'no' => 2,
				'code' => 'CA',
				'en' => 'Canada',
				'zh' => '加拿大(英语)',
				'currency'=>'CAD',
			),
			array(
				'no' => 3,
				'code' => 'UK',
				'en' => 'UK',
				'zh' => '英国',
				'currency'=>'GBP',
			),
			array(
				'no' => 15,
				'code' => 'AU',
				'en' => 'Australia',
				'zh' => '澳大利亚',
				'currency'=>'AUD',
			),
// 			array(
// 				'no' => 16,
// 				'code' => 'AT',
// 				'en' => 'Canada',
// 				'zh' => '奥地利',
// 				'currency'=>'EUR',
// 			),
// 			array(
// 				'no' => 23,
// 				'code' => 'BE',
// 				'en' => 'Belgium_French',
// 				'zh' => '比利时(法语)',
// 				'currency'=>'EUR',
// 			),
			array(
				'no' => 71,
				'code' => 'FR',
				'en' => 'France',
				'zh' => '法国',
				'currency'=>'EUR',
			),
			array(
				'no' => 77,
				'code' => 'DE',
				'en' => 'Germany',
				'zh' => '德国',
				'currency'=>'EUR',
			),
			array(
				'no' => 100,
				'code' => 'eBayMotors',
				'en' => 'eBayMotors',
				'zh' => 'Ebay汽车',
				'currency'=>'USD',
			),
			array(
				'no' => 101,
				'code' => 'IT',
				'en' => 'Italy',
				'zh' => '意大利',
				'currency'=>'EUR',
			),
// 			array(
// 				'no' => 123,
// 				'code' => 'BE',
// 				'en' => 'Belgium_Dutch',
// 				'zh' => '比利时(荷兰语)',
// 				'currency'=>'EUR',
// 			),
// 			array(
// 				'no' => 146,
// 				'code' => 'NL',
// 				'en' => 'Netherlands',
// 				'zh' => '荷兰',
// 				'currency'=>'EUR',
// 			),
			array(
				'no' => 186,
				'code' => 'ES',
				'en' => 'Spain',
				'zh' => '西班牙',
				'currency'=>'EUR',
			),
// 			array(
// 				'no' => 193,
// 				'code' => 'CH',
// 				'en' => 'Switzerland',
// 				'zh' => '瑞士',
// 				'currency'=>'CHF',
// 			),
			array(
				'no' => 201,
				'code' => 'HK',
				'en' => 'HongKong',
				'zh' => '香港',
				'currency'=>'HKD',
			),
// 			array(
// 				'no' => 203,
// 				'code' => 'IN',
// 				'en' => 'India',
// 				'zh' => '印度',
// 				'currency'=>'INR',
// 			),
// 			array(
// 				'no' => 205,
// 				'code' => 'IE',
// 				'en' => 'Ireland',
// 				'zh' => '爱尔兰',
// 				'currency'=>'EUR',
// 			),
// 			array(
// 				'no' => 207,
// 				'code' => 'MY',
// 				'en' => 'Malaysia',
// 				'zh' => '马来西亚',
// 				'currency'=>'MYR',
// 			),
// 			array(
// 				'no' => 210,
// 				'code' => 'CA',
// 				'en' => 'CanadaFrench',
// 				'zh' => '加拿大(法语)',
// 				'currency'=>'CAD',
// 			),
// 			array(
// 				'no' => 211,
// 				'code' => 'PH',
// 				'en' => 'Philippines',
// 				'zh' => '菲律宾',
// 				'currency'=>'PHP',
// 			),
// 			array(
// 				'no' => 212,
// 				'code' => 'PL',
// 				'en' => 'Poland',
// 				'zh' => '波兰',
// 				'currency'=>'PLN',
// 			),
// 			array(
// 				'no' => 216,
// 				'code' => 'SG',
// 				'en' => 'Singapore',
// 				'zh' => '新加坡',
// 				'currency'=>'SGD',
// 			)
		);
		$result = array();
		foreach($list as $k => $v) {
			$result[$v[$key]] = strlen($name)==0?$v:$v[$name];
		}
		return $result;
	}
	
	/**
	 +----------------------------------------------------------
	 * 获取刊登天数
	 +----------------------------------------------------------
	 * @access static
	 +----------------------------------------------------------
	 * @return				刊登方式
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		fanjs	2014/07/23				初始化
	 +----------------------------------------------------------
	 **/
	public static function getListingDuration($type){
		$val=array('Chinese'=>array('Days_1'=>'1天','Days_3'=>'3天','Days_5'=>'5天','Days_7'=>'7天','Days_10'=>'10天'),
				'FixedPriceItem'=>array('Days_3'=>'3天','Days_5'=>'5天','Days_7'=>'7天','Days_10'=>'10天','Days_30'=>'30天','GTC'=>'GTC'));
		return $val[$type];
	}
	
	/**
	 +----------------------------------------------------------
	 * 获取刊登平台币种
	 +----------------------------------------------------------
	 * @access static
	 +----------------------------------------------------------
	 * @return				刊登方式
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		fanjs	2015/05/04				初始化
	 +----------------------------------------------------------
	 **/
	public static function getSiteCurrency($siteid){
		$val=self::getEbaySiteIdList('no', 'currency');
		return $val[$siteid];
	}
	
	/**
	 *eBay的平台列表，目前使用比较多的
	 *@author fanjs 20151021
	 */
	public static function getSite(){
		return ['UK','US','Germany','Australia','Canada','eBayMotors','France','Italy','Spain','HongKong'];
	}
	
	/**
	 *eBay的平台访问链接列表，目前使用比较多的
	 *@author fanjs 20151021
	 */
	public static function getSiteViewUrl(){
		return [
			'US'=>'http://www.ebay.com/itm/',
			'eBayMotors'=>'http://www.ebay.com/itm/',
			'UK'=>'http://www.ebay.co.uk/itm/',
			'Germany'=>'http://www.ebay.de/itm/',
			'Australia'=>'http://www.ebay.com.au/itm/',
			'Canada'=>'http://www.ebay.ca/itm/',
			'France'=>'http://www.ebay.fr/itm/',
			'Italy'=>'http://www.ebay.it/itm/',
			'Spain'=>'http://www.ebay.es/itm/',
			'HongKong'=>'http://www.ebay.com.hk/itm/'
		];
	}
}
?>