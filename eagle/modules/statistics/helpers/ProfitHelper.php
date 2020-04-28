<?php
/**
 * @link http://www.witsion.com/
 * @copyright Copyright (c) 2014 Yii Software LLC
 * @license http://www.witsion.com/
 */
namespace eagle\modules\statistics\helpers;

use yii;
use yii\data\Pagination;
use yii\data\Sort;
use eagle\modules\util\helpers\ConfigHelper;
use eagle\modules\util\helpers\SysLogHelper;
use eagle\modules\dash_board\models\SalesDaily;
use eagle\modules\order\helpers\OrderGetDataHelper;
use eagle\modules\order\models\OdOrder;
use eagle\modules\order\models\OdOrderItem;
use eagle\modules\platform\apihelpers\PlatformAccountApi;
use eagle\modules\util\helpers\ExcelHelper;
use eagle\modules\util\helpers\RedisHelper;
use common\helpers\Helper_Curl;
use eagle\modules\catalog\models\ProductAliases;
use eagle\modules\catalog\models\Product;
use eagle\modules\dash_board\apihelpers\DashBoardStatisticHelper;

class ProfitHelper 
{
	static private $exchange_country = [
		'AED','ALL','AOA','ARS','AUD','BAM','BGN','BHD','BND','BOB','BRL','BWP','BYR','CAD','CHF','CLP','COP','CZK',
		'DKK','DZD','EGP','EUR','GBP','GHS','GYD','HKD','HRK','HUF','IDR','ILS','INR','IQD','IRR','ISK','JOD','JPY',
		'KES','KRW','KWD','KZT','LAK','LBP','LKR','LYD','MAD','MDL','MKD','MMK','MNT','MOP','MUR','MVR','MWK','MXN',
		'MYR','NGN','NOK','NPR','NZD','OMR','PEN','PHP','PKR','PLN','PYG','QAR','RON','RSD','RUB','SAR','SDG','SDR',
		'SEK','SGD','SLL','SRD','SSP','SYP','THB','TND','TRY','TWD','TZS','UAH','UGX','USD','UYU','UZS','VEF','VND',
		'XAF','YER','ZAR','ZMW'
	];
	
	static private $baidu_api_keys = [
		'361cf2a2459552575b0e86e0f62302bc',
		'0d263364faa016e5f06075b69b799087',
		'5e7769d9361f4ad55aefc4980f5d299c',
	];
	
	/**
	  +---------------------------------------------------------------------------------------------
	  * 读取最新的汇率信息到redis
	  +---------------------------------------------------------------------------------------------
	  * log			name	date					note
	  * @author		lrq		2017/08/24				初始化
	  +---------------------------------------------------------------------------------------------
	  **/
	 static public function RefreshRateToRedis(){
	 	try{
	 		//**********通过百度API获取汇率*********************
	 		$rate_arr = array();
	 		$url = 'http://apis.baidu.com/netpopo/exchange/single';
	 		$params = ['currency' => 'CNY'];
	 		foreach (self::$baidu_api_keys as $api_key){
	 			$header = ['apikey: '.$api_key];
	 			$response = Helper_Curl::post($url, $params, $header);
	 			$row = json_decode($response, true);
	 			if(!empty($row) && isset($row['status']) && $row['status'] == 0 && !empty($row['result']['list'])){
	 				break;
	 			}
	 			$row = array();
	 		}
	 		
	 		if(!empty($row)){
	 			foreach($row['result']['list'] as $key => $val){
	 				if(!empty($val['rate']) && is_numeric($val['rate'])){
	 					$rate_arr[$key] = round(1 / $val['rate'], 4);
	 				}
	 			}
	 		}
	 		
	 		//**********部分货币需通过抓取页面获取，更精确*********************
	 		// dzt20191219 百度USD转RMB 抓取错了另一条记录，现在能查的币也不多，直接屏蔽了，用下面的差汇率了
// 	 		foreach(self::$exchange_country as $val){
// // 		 		$data = file_get_contents("http://www.baidu.com/s?wd=$val%20RMB&rsv_spt=1");
//                 // dzt20191108 百度需要设置agent才能获取页面
// 	 		    $header = array ('User-Agent: Mozilla/5.0 (Windows NT 5.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/33.0.1750.146 Safari/537.36');
// 	 		    $data = Helper_Curl::get("http://www.baidu.com/s?wd=$val%20RMB&rsv_spt=1", array(), $header);
// 		 		preg_match("/<div>1\D*=(\d*\.\d*)\D*<\/div>/",$data, $converted);
// 		 		if(is_array($converted) && count($converted) > 1){
// 			 		$rate = preg_replace("/[^0-9.]/", "", $converted[1]);
// 		 			if(!empty($rate) && is_numeric($rate)){
// 	 					$rate_arr[$val] = $rate;
// 	 				}
// 		 		}
// 	 		}
	 		
	 		// dzt20191108 冷门汇率单独更新 XOF https://huilv.911cha.com/XOFCNY.html
	 		array_push(self::$exchange_country, "XOF");
	 		foreach(self::$exchange_country as $val){
	 		    if(!empty($rate_arr[$val])){
	 		        continue;
	 			}
			  
// 	 		    $params = ["from"=>"XOF", "to"=>"CNY", "num"=>1];
	 		    // $params = ["from"=>"CNY", "to"=>$val, "num"=>1];// 这个num是对from参数的
	 		    $params = ["from"=>$val, "to"=>"CNY", "num"=>1];
	 			$response = Helper_Curl::post("https://huilv.911cha.com", $params);
	 		    // preg_match("/>1<\/span>CNY[^0-9]*=[^0-9]*(\d*\.\d*)<\/span>{$val}<\/div>/", $response, $converted);
	 		    preg_match("/>1<\/span>{$val}[^0-9]*=[^0-9]*(\d*\.\d*)<\/span>CNY<\/div>/", $response, $converted);
	 			if(is_array($converted) && count($converted) > 1){
	 		    $rate = preg_replace("/[^0-9.]/", "", $converted[1]);
	 		    	if(!empty($rate) && is_numeric($rate)){
	 		            $rate_arr[$val] = $rate;
	 		        }
	 		    }
	 		}
	 		
// 	 		echo PHP_EOL."get finish:".json_encode($rate_arr).PHP_EOL;
	 		
		 	/************雅虎旧模式更新汇率，停止服务
		 	$str = '';
			foreach(self::$exchange_country as $val){
				$str .= $val.'CNY=x,';
			}
			$str = rtrim($str, ',');
			
			$rate_arr = [];
			header("Content-type: text/html; charset=utf-8");
			$file = fopen('http://download.finance.yahoo.com/d/quotes.csv?e=.csv&f=sl1d1t1&s='.$str,'r');
			while ($data = fgetcsv($file)){   //读取CSV文件里的每一行内容
				$currency = str_replace('CNY=x', '', $data[0]);
				if(!empty($currency) && is_numeric($data[1])){
					$rate_arr[$currency] = $data[1];
				}
			}
			print_r('http://download.finance.yahoo.com/d/quotes.csv?e=.csv&f=sl1d1t1&s='.$str);die;
			print_r($rate_arr);
			fclose($file);*/
			
			if(empty($rate_arr)){
				return ['success' => false, 'msg' => '获取汇率失败！e1'];
			}
			
			//部分币别获取异常
// 			if(!empty($rate_arr['NGN'])){
// 				unset($rate_arr['NGN']);
// 			}
			
			$redis_val['data'] = $rate_arr;
			$redis_val['Update_time'] = time();
			
			$redis_key_lv1 = 'NewestExchangeRate';
			$redis_key_lv2 = 'NewestExchangeRate';
			$ret = RedisHelper::RedisSet($redis_key_lv1, $redis_key_lv2, json_encode($redis_val));
			if(empty($ret)){
				RedisHelper::RedisSet($redis_key_lv1, $redis_key_lv2, json_encode($redis_val));
			}
			
			return ['success' => true, 'msg' => ''];
	 	}
	 	catch(\Exception $ex){
	 		return ['success' => false, 'msg' => $ex->getMessage()];
	 	}
	 }
	 
	 /**
	  +---------------------------------------------------------------------------------------------
	  * 获取指定币别最新汇率
	  * +--------------------------------------------------------------------------------
	  * @param    $is_use_setting    使用用户设置的汇率
	  +---------------------------------------------------------------------------------------------
	  * log			name	date					note
	  * @author		lrq		2017/08/24				初始化
	  +---------------------------------------------------------------------------------------------
	  **/
	 static public function GetExchangeRate($from_currencys, $to_currency = 'CNY', $is_use_setting = false){
	 	if(!is_array($from_currencys)){
	 		$currency_arr = [0 => $from_currencys];
	 	}
	 	else{
	 		$currency_arr = $from_currencys;
	 	}
	 	
	 	//redis，每日更新汇率
	 	$redis_key_lv1 = 'NewestExchangeRate';
	 	$redis_key_lv2 = 'NewestExchangeRate';
	 	$warn_record = RedisHelper::RedisGet($redis_key_lv1, $redis_key_lv2);
	 	if(!empty($warn_record)){
	 		$redis_val = json_decode($warn_record,true);
	 	}
	 	//用户设置的汇率信息
	 	$exchange_config = ConfigHelper::getConfig("Profit/CurrencyExchange",'NO_CACHE');
	 	if(empty($exchange_config)){
	 		$exchange_config = [];
	 	}
	 	else{
	 		$exchange_config = json_decode($exchange_config,true);
	 		if(empty($exchange_config)){
	 			$exchange_config = [];
	 		}
	 	}
	 	
	 	$rate_arr = array();
	 	foreach($currency_arr as $currency){
	 		$EXCHANGE_RATE = 0;
	 		
		 	if($currency == 'PH'){
		 		$currency = 'PHP';
		 	}
		 	//用户设置的汇率
		 	if($is_use_setting && !empty($exchange_config[$currency])){
		 	    $EXCHANGE_RATE = $exchange_config[$currency];
		 	}
		 	//当redis的汇率不存在，则用每月更新的
		 	else if(!empty($redis_val['data'][$currency])){
		 		$EXCHANGE_RATE = $redis_val['data'][$currency];
		 	}
		 	else{
		 		if(\common\helpers\Helper_Currency::getCurrencyIsExist($currency)){
		 			$EXCHANGE_RATE = \common\helpers\Helper_Currency::convert(1, 'CNY', $currency);
		 		}
		 	}
		 	
		 	//当转换非RMB时
		 	if($to_currency != 'CNY'){
		 	 	$rate = ProfitHelper::GetExchangeRate($to_currency);
		 		if(!empty($rate)){
		 	   		if($EXCHANGE_RATE / $rate < 0.0001){
			 	   		//汇率太小时，小数位保留多点
			 	   		$EXCHANGE_RATE = round($EXCHANGE_RATE / $rate, 8);
			 	   	}
			 	   	else{
			 	   		$EXCHANGE_RATE = round($EXCHANGE_RATE / $rate, 4);
			 	   	}
		 	   }
		 	}
		 	
		 	if(!empty($EXCHANGE_RATE)){
		 		$rate_arr[$currency] = $EXCHANGE_RATE;
		 	}
	 	}
	 	
	 	if(!is_array($from_currencys)){
	 		if(!empty($rate_arr[$from_currencys])){
	 			return $rate_arr[$from_currencys];
	 		}
	 		else{
	 			return 0;
	 		}
	 	}
	 	else{
	 		return $rate_arr;
	 	}
	 }
	 
	 /**
	  +---------------------------------------------------------------------------------------------
	  * 获取币别汇率列表，只显示订单存在的币别
	  +---------------------------------------------------------------------------------------------
	  * log			name	date					note
	  * @author		lrq		2017/08/24				初始化
	  +---------------------------------------------------------------------------------------------
	  **/
	 static public function GetCurrencyInfo(){
	 	//获取订单所有货币
	 	$currency_arr = array();
	 	$rows = Yii::$app->get('subdb')->createCommand("SELECT distinct currency FROM `od_order_v2`")->queryAll();
	 	foreach($rows as $row){
	 		if(!empty($row['currency'])){
	 			$currency_arr[] = $row['currency'];
	 		}
	 	}
	 	//获取最新汇率信息
	 	$currency_rate_list = self::GetExchangeRate($currency_arr);
	 	//获取已设置的汇率信息
	 	$exchange_config = ConfigHelper::getConfig("Profit/CurrencyExchange",'NO_CACHE');
	 	if(empty($exchange_config)){
	 		$exchange_config = [];
	 	}
	 	else{
	 		$exchange_config = json_decode($exchange_config,true);
	 		if(empty($exchange_config)){
	 			$exchange_config = [];
	 		}
	 	}
	 	
	 	$list = array();
	 	foreach($currency_arr as $val){
	 		if(!empty($exchange_config[$val])){
	 			$list[$val] = [
	 				'type' => 1,
	 				'rate' => $exchange_config[$val],
	 			];
	 		}
	 		else if(!empty($currency_rate_list[$val])){
	 			$list[$val] = [
		 			'type' => 0,
		 			'rate' => $currency_rate_list[$val],
	 			];
	 		}
	 		else{
	 			$list[$val] = [
		 			'type' => 0,
		 			'rate' => 1,
	 			];
	 		}
	 	}
	 	
	 	return $list;
	 }
	 
	 /**
	  +---------------------------------------------------------------------------------------------
	  * 保存客户设置的汇率
	  +---------------------------------------------------------------------------------------------
	  * log			name	date					note
	  * @author		lrq		2017/08/24				初始化
	  +---------------------------------------------------------------------------------------------
	  **/
	 static public function SaveCurrencyExchange($data){
	 	//整理信息
	 	$list = array();
    	foreach($data as $key => $arr){
    		foreach($arr as $num => $val){
    			$list[$num][$key] = $val;
    		}
    	}
    	
	 	$exchange_config = ['1' => ''];
 		foreach ($list as $val){
 			if(!empty($val['currency']) && !empty($val['rate']) && is_numeric($val['rate'])){
 				$currency = strtoupper($val['currency']);
 				$exchange_config[$currency] = floatval($val['rate']);
 			}
 		}
 		
	 	if(!empty($exchange_config))
	 		ConfigHelper::setConfig("Profit/CurrencyExchange", json_encode($exchange_config));
	 	
	 	return ['success' => true, 'msg' => ''];
	 }
	 
	 /**
	  +---------------------------------------------------------------------------------------------
	  * 更新未设置采购成本的订单的采购成本
	  +---------------------------------------------------------------------------------------------
	  * log			name	date			note
	  * @author		lrq		2017/12/21		初始化
	  +---------------------------------------------------------------------------------------------
	  **/
	 public static function updateProductCostFromUnSet(){
	 	try{
		 	//获取需设置采购成本的订单
		 	$sql = "select distinct order_id from od_order_item_v2 where (purchase_price is null or purchase_price=0) and 
		 				order_id in (select order_id from od_order_v2 where order_relation in ('normal' , 'sm', 'ss', 'fs') and profit is not null and order_status=500 and paid_time>0)
		 				order by order_id desc
		 				limit 0, 500";
		 	$rows = Yii::$app->get('subdb')->createCommand($sql)->queryAll();
		 	//订单id
		 	$order_ids = array();
		 	foreach($rows as $row){
		 		$order_ids[] = $row['order_id'];
		 	}
		 	//item信息
		 	$skus = array();
		 	$sku_price_list = array();
		 	$items = OdOrderItem::find()->where(['order_id' => $order_ids])->andWhere("manual_status is null or manual_status!='disable'");
		 	foreach($items->each() as $item){
		 		$sku = $item->sku;
		 		if(!empty($item->root_sku)){
		 			$sku = $item->root_sku;
		 		}
		 		
		 		if(!in_array($sku, $skus)){
		 			$skus[] = $sku;
		 		}
		 	}
		 	unset($items);
		 	//查询别名对应的主SKU信息
		 	$aliases_list = array();
		 	$aliases = ProductAliases::find()->where(['alias_sku' => $skus]);
		 	foreach($aliases->each() as $one){
		 		if(!in_array($one->sku, $skus)){
		 			$skus[] = $one->sku;
		 		}
		 		$aliases_list[$one->alias_sku] = $one->sku;
		 	}
		 	unset($aliases);
		 	//对应的商品信息、采购价
		 	$pd_list = array();
		 	$pds = Product::find()->select(['sku', 'purchase_price', 'additional_cost'])->where(['sku' => $skus]);
		 	foreach($pds->each() as $one){
		 		if(!empty($one->purchase_price) && $one->purchase_price > 0){
		 			$pd_list[$one->sku]['purchase_price'] = $one->purchase_price;
		 			$pd_list[$one->sku]['additional_cost'] = empty($one->additional_cost) ? 0 : $one->additional_cost;
		 		}
		 	}
		 	unset($pds);
		 	//查询所有sku对应的采购单价信息
		 	foreach($skus as $sku){
		 		if(array_key_exists($sku, $pd_list)){
		 			$sku_price_list[$sku] = $pd_list[$sku];
		 		}
		 		else if(array_key_exists($sku, $aliases_list)){
		 			if(array_key_exists($aliases_list[$sku], $pd_list)){
		 				$sku_price_list[$sku] = $pd_list[$aliases_list[$sku]];
		 			}
		 		}
		 	}
		 	unset($skus);
		 	unset($pd_list);
		 	
		 	//循环更新订单采购成本
		 	$exist_info = array();
		 	$ProfitAdds = array();
		 	$orders = OdOrder::find()->where(['order_id' => $order_ids])->all();
		 	foreach($orders as $order){
		 		$addi_info = json_decode($order->addi_info, true);
		 		if(empty($addi_info) || !isset($addi_info['actual_charge'])){
		 			continue;
		 		}
		 		
		 		//获取item信息，并计算采购成本
		 		$purchase_cost = 0;
		 		$additional_cost = 0;
		 		$product_cost_str = '';
		 		$items = OdOrderItem::find()->where(['order_id' => $order->order_id])->andWhere("manual_status is null or manual_status!='disable'")->all();
		 		foreach($items as &$item){
			 		$sku = $item->sku;
			 		if(!empty($item->root_sku)){
			 			$sku = $item->root_sku;
			 		}
		 			if(array_key_exists($sku, $sku_price_list)){
		 				$additional_cost = $sku_price_list[$sku]['additional_cost'];
		 				$purchase_cost += ($sku_price_list[$sku]['purchase_price'] + $additional_cost) * $item->quantity;
		 				$item->purchase_price = $sku_price_list[$sku]['purchase_price'];
		 				
		 				$itme_cost_str = '<br>&nbsp;&nbsp;&nbsp;&nbsp;'.$item->sku.'：(采购价'.$item->purchase_price.(isset($additional_cost)?"+额外$additional_cost)":')').'*'.$item->quantity;
		 				$product_cost_str .= $itme_cost_str;
		 			}
		 		}
		 		if(!empty($purchase_cost)){
		 			$logistics_cost = !empty($order->logistics_cost) ? $order->logistics_cost : (empty($addi_info['logistics_cost']) ? 0 : $addi_info['logistics_cost']);
		 			//更新订单采购成本、利润
		 			$profit = $addi_info['actual_charge'] - $purchase_cost - $logistics_cost;
		 			$dis_logistics_cost = $profit - $order->profit;
		 			$addi_info['purchase_cost'] = $purchase_cost;
		 			$addi_info['product_cost'] = $product_cost_str;
		 			$order->addi_info = json_encode($addi_info);
		 			$order->profit = $profit;
		 			
		 			if($order->save()){
		 				//更新对应item
		 				foreach($items as $item){
		 					$item->save(false);
		 				}
		 				
		 				//整理需要更新的数据
		 				$order_date = date("Y-m-d",$order->order_source_create_time);
		 				$platform = $order->order_source;
		 				$order_type = $order->order_type;
		 				$seller_id = $order->selleruserid;
		 				$order_status = $order->order_status;
		 				$info = $order_date.'&'.$platform.'&'.$seller_id.'&'.$order_status;
		 				if(!in_array($info, $exist_info)){
		 					$ProfitAdds[$info] = [
			 					'order_date' => $order_date,
			 					'platform' => $platform,
			 					'order_type'=>$order_type,
			 					'seller_id' => $seller_id,
			 					'profit_cny' => $dis_logistics_cost,
			 					'order_status' => $order_status,
		 					];
		 					$exist_info[] = $info;
		 				}
		 				else{
		 					$ProfitAdds[$info]['profit_cny'] = $ProfitAdds[$info]['profit_cny'] + $dis_logistics_cost;
		 				}
		 			}
		 		}
		 	}
		 	//更新dash_board信息
		 	foreach ($ProfitAdds as $key => $ProfitAdd){
		 		DashBoardStatisticHelper::SalesProfitAdd($ProfitAdd['order_date'], $ProfitAdd['platform'], $ProfitAdd['order_type'], $ProfitAdd['seller_id'], $ProfitAdd['profit_cny'], false, $ProfitAdd['order_status']);
		 	}
	 	}
	 	catch(\Exception $ex){
	 		print_r($ex);die;
	 	}
	 }
}




