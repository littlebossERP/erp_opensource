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
use eagle\modules\catalog\models\ProductAliases;
use eagle\models\catalog\Product;
use eagle\modules\purchase\models\PurchaseItems;
use eagle\modules\inventory\models\ProductStock;
use eagle\modules\util\helpers\RedisHelper;

class StatisticsHelper 
{
	
	/**
	  +---------------------------------------------------------------------------------------------
	  * 获取运营汇总信息
	  +---------------------------------------------------------------------------------------------
	  * @access static
	  +---------------------------------------------------------------------------------------------
	  * @param     $params			查询条件
	  * @param     $is_sum		   	是否合计信息，0否1是
	  +---------------------------------------------------------------------------------------------
	  * @return	array[]
	  +---------------------------------------------------------------------------------------------
	  * log			name	date					note
	  * @author		lrq		2017/03/23				初始化
	  +---------------------------------------------------------------------------------------------
	  **/
	 static public function getAchievementInfo($params, $is_sum)
	 {
	 	//更新运营汇总
	 	self::CheckAndRereshDash();
	 	
	 	$per_page = empty($params['per-page']) ? 20 : $params['per-page'];
	 	$page = empty($params['page']) ? 0 : $params['page'];
	 	$period = empty($params['period']) ? 'D' : $params['period'];
	 	$currency = empty($params['currency']) ? 'USD' : $params['currency'];
	 	$start_date = empty($params['start_date']) ? date("Y-m-d", time()) : date("Y-m-d", strtotime($params['start_date']));
	 	$end_date = empty($params['end_date']) ? date("Y-m-d", time()) : date("Y-m-d", strtotime($params['end_date']));
	 	$order_type = empty($params['order_type']) ? array() : $params['order_type'];
	 	
	 	//获取汇率
	 	$currency_Symbol = '';   //金额前缀
	 	if($currency == 'RMB'){
	 		//USD转RMB汇率
	 		$EXCHANGE_RATE = \common\helpers\Helper_Currency::convert(1, 'CNY', 'USD');
	 		$currency_Symbol = '￥ ';
	 	}
	 	else{
	 		//RMB转USD汇率
	 		$EXCHANGE_RATE = \common\helpers\Helper_Currency::convert(1, 'USD', 'CNY');
	 		$currency_Symbol = '$ ';
	 	}
	 	
	 	$query = SalesDaily::find()->select("platform, seller_id, thedate, sum(sales_count) as total_sales_count, sum(sales_amount_USD) as total_sales_amount_USD, sum(profit_cny) as total_profit_cny");
	 	
	 	//筛选有效订单
	 	foreach ($params as $key=>$value){
	 		if($key!='selectstore' && $value=='')
	 			continue;
	 		switch ($key){
	 			case 'start_date':
	 				$query->andWhere("thedate>='$start_date'");//timestamp
	 				break;
	 			case 'end_date':
	 				$query->andWhere("thedate<='$end_date'");
	 				break;
	 			case 'selectstore':
	 				if(empty($value))
	 					$query->andWhere("seller_id='0'");
	 				else 
	 					$query->andWhere(['in','seller_id',$value]);
	 				break;
	 			case 'selectplatform':
	 				$query->andWhere(['in','platform',$value]);
 				case 'order_type':
 					break;
	 			case 'period':
	 				break;
 				case 'currency':
 					break;
	 			case 'page':
	 				break;
	 			case 'per-page':
	 				break;
	 			default:
	 				$query->andWhere([$key=>$value]);
	 				break;
	 		}
	 	}
	 	unset($params);
	 	
	 	//只显示已绑定的账号的信息
	 	$bind_stores = '';
	 	//$uid = \Yii::$app->subdb->getCurrentPuid();
	 	//$platformAccountInfo = PlatformAccountApi::getAllPlatformOrderSelleruseridLabelMap($uid);
	 	$platformAccountInfo = PlatformAccountApi::getAllAuthorizePlatformOrderSelleruseridLabelMap(false, false, true);//引入平台账号权限后
	 	foreach ($platformAccountInfo as $p_key=>$p_v){
	 		if(!empty($p_v)){
	 			foreach ($p_v as $s_key=>$s_v){
	 				$bind_stores[] = $s_key;
	 			}
	 		}
	 	}
	 	if($bind_stores != ''){
	 		$query->andWhere(['in','seller_id',$bind_stores]);
	 	}
	 	
	 	//订单类型
	 	$order_type_condition = '';
	 	foreach($order_type as $v){
	 	    if($v == 'normal')
	 	        $order_type_condition .= "order_type is null or order_type='NORMAL' or order_type='normal' or order_type='MFN' or ";
	 	    else if($v == 'fba')
	 	        $order_type_condition .= "order_type='AFN' or ";
	 	    else if($v == 'fbc')
	 	    	$order_type_condition .= "order_type='FBC' or ";
	 	}
	 	if(!empty($order_type_condition)){
	 	    $order_type_condition = "(platform not in ('AMAZON', 'CDISCOUNT') or (".rtrim($order_type_condition,'or ')."))";
	 	    $query->andWhere($order_type_condition);
	 	}
	 	$query->andWhere(['use_module_type' => 'statistics']);
	 	$query->groupBy("thedate");
	 
	 	//添加合计行
	 	if( $is_sum == 1){
	 	    $achievementAll = $query->asArray()->all();
	 	    //if(!empty($salesAll)){
	 	        $sum = [];
	 	        $sum['thedate'] = '合计';
	 	        $sum['startW'] = '';
	 	        $sum['endW'] = '';
	 	        $sum['platform'] = 0;
	 	        $sum['seller_id'] = 0;
	 	        $sum['total_sales_count'] = 0;
	 	        $sum['total_sales_amount_USD'] = 0;
	 	        $sum['total_profit_cny'] = 0;
	 	        foreach($achievementAll as $key => $achievement){
	 	        	$sum['total_sales_count'] = $sum['total_sales_count'] + (empty($achievement['total_sales_count']) ? 0 : $achievement['total_sales_count']);
	 	            $sum['total_sales_amount_USD'] = sprintf('%.2f', $sum['total_sales_amount_USD'] + (empty($achievement['total_sales_amount_USD']) ? 0 : $achievement['total_sales_amount_USD']));
	 	            $sum['total_profit_cny'] = $sum['total_profit_cny'] + (empty($achievement['total_profit_cny']) ? 0 : $achievement['total_profit_cny']);
	 	           
	 	        }
	 	        
	 	        if($currency == 'RMB'){
	 	        	//USD转RMB
	 	        	$sum['total_sales_amount_USD'] = round($sum['total_sales_amount_USD'] * $EXCHANGE_RATE, 2);
	 	        }
	 	        else{
	 	        	//RMB转USD
	 	        	$sum['total_profit_cny'] = round($sum['total_profit_cny'] * $EXCHANGE_RATE, 2);
	 	        }
	 	        
	 	        if(!empty($sum['total_sales_amount_USD']) && $sum['total_sales_amount_USD'] != '-')
	 	        	$sum['total_sales_amount_USD'] = $currency_Symbol.$sum['total_sales_amount_USD'];
	 	        if(!empty($sum['total_profit_cny']) && $sum['total_profit_cny'] != '-')
	 	        	$sum['total_profit_cny'] = $currency_Symbol.$sum['total_profit_cny'];
	 	        
	 	        $result['data'][] = $sum;
	 	    //}
	 	}
	 	
	 	//排序
	 	$query->orderBy("thedate desc");
    	$achievementInfo = $query
    	->asArray()
    	->all();
	 	
    	$data = array();
	 	if(!empty($achievementInfo)){
	 		//统计日期参数
	 		switch($period){
	 			case 'W':
	 				$count = 1;
	 				$end_w = $end_date;
	 				$weekDay = (int)date("w",strtotime($end_w));//周第几日，周日为第0日,周六为第6日
	 				$start_w = date("Y-m-d", strtotime($end_w) - 3600 * 24 * $weekDay);//周开始时间
	 				while($start_date < $start_w){
	 					$data[] = [
	 						"thedate" => $start_w.' ~ '. $end_w,
	 						"startW" => $start_w,
	 						"endW" => $end_w,
	 						"platform" => '-',
	 						"seller_id" => '-',
	 						"total_sales_count" => '-',
	 						"total_sales_amount_USD" => '-',
	 						"total_profit_cny" => '-',
	 					];
	 					
	 					$end_w = date("Y-m-d", strtotime($start_w) - 3600 * 24); //周结束时间
	 					$start_w = date("Y-m-d", strtotime($end_w) - 3600 * 24 * 6);//周开始时间
	 					
	 					//预防死循环
	 					if($count > 100){
	 						break;
	 					}
	 					$count++;
	 				}
	 				$data[] = [
		 				"thedate" => $start_date.' ~ '. $end_w,
		 				"startW" => $start_date,
		 				"endW" => $end_w,
		 				"platform" => '-',
		 				"seller_id" => '-',
		 				"total_sales_count" => '-',
		 				"total_sales_amount_USD" => '-',
		 				"total_profit_cny" => '-',
	 				];
	 				break;
 				case 'M':
 					$count = 1;
 					$start_m = date("Y-m", strtotime($start_date));
	 				$time = date("Y-m", strtotime($end_date));
	 				while($start_m <= $time){
	 					$data[$time] = [
		 					"thedate" => $time,
		 					"platform" => '-',
		 					"seller_id" => '-',
		 					"total_sales_count" => '-',
		 					"total_sales_amount_USD" => '-',
		 					"total_profit_cny" => '-',
	 					];
	 					
	 					//上月
	 					$time = $time.'-01';
	 					$time = date('Y-m',strtotime("$time -1 month"));
	 						
	 					//预防死循环
	 					if($count > 20){
	 						break;
	 					}
	 					$count++;
	 				}
	 				break;
	 			default:
	 				$count = 1;
	 				$time = $end_date;
	 				while($start_date <= $time){
	 					$data[$time] = [
		 					"thedate" => $time,
		 					"platform" => '-',
		 					"seller_id" => '-',
		 					"total_sales_count" => '-',
		 					"total_sales_amount_USD" => '-',
		 					"total_profit_cny" => '-',
	 					];
	 					
	 					//上一天
	 					$time = date("Y-m-d", strtotime($time) - 3600 * 24);
	 						
	 					//预防死循环
	 					if($count > 1000){
	 						break;
	 					}
	 					$count++;
	 				}
	 				break;
	 		}
	 		
	 		$place = 0;
	 		$count = count($data);
	 		
	 		foreach($achievementInfo as $achievement){
	 			if($period == 'W'){
		 			for($n = $place; $n < $count; $n++){
		 				if($achievement['thedate'] >= $data[$n]['startW'] && $achievement['thedate'] <= $data[$n]['endW']){
		 					$data[$n]['platform'] = $achievement['platform'];
		 					$data[$n]['seller_id'] = $achievement['seller_id'];
		 					$data[$n]['total_sales_count'] = $data[$n]['total_sales_count'] + $achievement['total_sales_count'];
		 					$data[$n]['total_sales_amount_USD'] = $data[$n]['total_sales_amount_USD'] + $achievement['total_sales_amount_USD'];
		 					$data[$n]['total_profit_cny'] = $data[$n]['total_profit_cny'] + $achievement['total_profit_cny'];
		 					break;
		 				}
		 			}
		 			$place = $n;
	 			}
	 			else if($period == 'M'){
	 				$thedate = date("Y-m", strtotime($achievement['thedate']));
	 				if(!empty($data[$thedate])){
	 					$data[$thedate]['platform'] = $achievement['platform'];
	 					$data[$thedate]['seller_id'] = $achievement['seller_id'];
	 					$data[$thedate]['total_sales_count'] = $data[$thedate]['total_sales_count'] + $achievement['total_sales_count'];
	 					$data[$thedate]['total_sales_amount_USD'] = $data[$thedate]['total_sales_amount_USD'] + $achievement['total_sales_amount_USD'];
	 					$data[$thedate]['total_profit_cny'] = $data[$thedate]['total_profit_cny'] + $achievement['total_profit_cny'];
	 				}
	 			}
	 			else{
	 				$thedate = $achievement['thedate'];
	 				if(!empty($data[$thedate])){
	 					$data[$thedate]['platform'] = $achievement['platform'];
	 					$data[$thedate]['seller_id'] = $achievement['seller_id'];
	 					$data[$thedate]['total_sales_count'] = $data[$thedate]['total_sales_count'] + $achievement['total_sales_count'];
	 					$data[$thedate]['total_sales_amount_USD'] = $data[$thedate]['total_sales_amount_USD'] + $achievement['total_sales_amount_USD'];
	 					$data[$thedate]['total_profit_cny'] = $data[$thedate]['total_profit_cny'] + $achievement['total_profit_cny'];
	 				}
	 			}
	 		}
	 		
	 		//换币种
	 		if($currency == 'RMB'){
	 			//USD转RMB
	 			foreach ($data as $k => $d){
	 				$data[$k]['total_sales_amount_USD'] = round($data[$k]['total_sales_amount_USD'] * $EXCHANGE_RATE, 2);
	 				
	 				if(!empty($data[$k]['total_sales_amount_USD']) && $data[$k]['total_sales_amount_USD'] != '-')
	 					$data[$k]['total_sales_amount_USD'] = $currency_Symbol.$data[$k]['total_sales_amount_USD'];
	 				if(!empty($data[$k]['total_profit_cny']) && $data[$k]['total_profit_cny'] != '-')
	 					$data[$k]['total_profit_cny'] = $currency_Symbol.$data[$k]['total_profit_cny'];
	 			}
	 		}
	 		else{
	 			//RMB转USD
	 			foreach ($data as $k => $d){
	 				$data[$k]['total_profit_cny'] = round($data[$k]['total_profit_cny'] * $EXCHANGE_RATE, 2);
	 				
	 				if(!empty($data[$k]['total_sales_amount_USD']) && $data[$k]['total_sales_amount_USD'] != '-')
	 					$data[$k]['total_sales_amount_USD'] = $currency_Symbol.$data[$k]['total_sales_amount_USD'];
	 				if(!empty($data[$k]['total_profit_cny']) && $data[$k]['total_profit_cny'] != '-')
	 					$data[$k]['total_profit_cny'] = $currency_Symbol.$data[$k]['total_profit_cny'];
	 			}
	 		}
	 		
	 		if($period == 'D' || $period == 'M'){
	 			//重置索引
	 			$data = array_values($data);
	 		}
	 		
	 		if($per_page == -1){
	 			foreach ($data as $d){
		 			$result['data'][] = $d;
		 		}
	 		}
	 		else{
		 		//分页显示
		 		$pagination = new Pagination([
		 				'page'=> $page,
		 				'pageSize' => $per_page,
		 				'totalCount' => $count,
		 				'pageSizeLimit'=>[20,200],//每页显示条数范围
		 				]);
		 		$result['pagination'] = $pagination;
		 		
		 		$p_s = $per_page * $page ;
		 		$p_e = $per_page * ($page + 1) - 1;
		 		if($p_e > $count - 1)
		 			$p_e = $count - 1;
		 		for($n = $p_s; $n <= $p_e; $n++){
		 			$result['data'][] = $data[$n];
		 		}
	 		}
	 			
	 		$result['status'] = 1;
	 	}
	 	else{
	 		//分页显示
	 		$pagination = new Pagination([
	 				'page'=> $page,
	 				'pageSize' => $per_page,
	 				'totalCount' => 0,
	 				'pageSizeLimit'=>[20,200],//每页显示条数范围
	 				]);
	 		$result['pagination'] = $pagination;
	 		
	 		$result['status'] = 1;
	 	}
	 	return $result;
	 
	 }//end of function
	 
	 //重新同步利润信息到dash board
	 public static function RefreshDashBoardProfit($puid){
	 	 
	 	
	 	//清除旧数据
	 	$sql = "Update db_sales_daily set profit_cny=0";
	 	$command = Yii::$app->subdb->createCommand($sql);
	 	$ret = $command->execute();
	 	
	 	$query = OdOrder::find()->select("`order_source`, `selleruserid`, sum(`profit`) as total_profit, FROM_UNIXTIME(order_source_create_time,'%Y%m%d') as `time`, `order_type`");
	 	//筛选有效订单
	 	$query = OrderGetDataHelper::formatQueryOrderConditionPurchase($query);
	 	$query->andWhere("profit is not null and order_status=500 and paid_time>0");
	 	
	 	$query->groupBy("`order_source`, `selleruserid`, `time`, `order_type`");
	 	$query->orderBy("`time` desc");
	 	$statisticsAll = $query->asArray()->all();
	 	//return $statisticsAll;
	 	//组织更新SQL
	 	$sql = '';
	 	foreach ($statisticsAll as $statistic){
	 		if(empty($statistic['order_type'])){
	 			$statistic['order_type'] = 'NORMAL';
	 		}
	 		$sql .= "Update db_sales_daily set profit_cny=".$statistic['total_profit']." where (order_type is null || order_type not in ('AFN','FBC')) and thedate='".$statistic['time']."' and platform='".$statistic['order_source']."' and order_type='".$statistic['order_type']."' and seller_id='".$statistic['selleruserid']."';";
	 	}
	 	
	 	if(!empty($sql)){
	 		$command = Yii::$app->subdb->createCommand($sql);
	 		$ret = $command->execute();
	 		
	 		if($ret != 0){
	 			return ['success' => 1, 'msg' => $ret];
	 		}
	 	}
	 	
	 	return ['success' => 1, 'msg' => ''];
	 }//end of function
	 
	 /**
	  +---------------------------------------------------------------------------------------------
	  * 获取销售统计信息
	  +---------------------------------------------------------------------------------------------
	  * @access static
	  +---------------------------------------------------------------------------------------------
	  * @param     $params			查询条件
	  +---------------------------------------------------------------------------------------------
	  * @return	array[]
	  +---------------------------------------------------------------------------------------------
	  * log			name	date					note
	  * @author		lrq		2017/03/27				初始化
	  +---------------------------------------------------------------------------------------------
	  **/
	 static public function getSalesInfo($params)
	 {
	 	$per_page = empty($params['per-page']) ? 20 : $params['per-page'];
	 	$page = empty($params['page']) ? 0 : $params['page'];
	 	$currency = empty($params['currency']) ? 'USD' : $params['currency'];
	 	$start_date = empty($params['start_date']) ? time() : strtotime($params['start_date']);
	 	$end_date = empty($params['end_date']) ? time() : strtotime($params['end_date']." +1 day");
	 	$sort = $params['sort'];
	 	$sorttype = $params['sorttype'];
	 	
	 	$query = OdOrder::find()
	 		->select("od_order_v2.order_source, od_order_v2.selleruserid, od_order_v2.currency, count(od_order_v2.order_id) order_count, 
	 				  item.sku, item.product_attributes, item.product_name, item.photo_primary, sum(item.quantity) total_qty, sum(item.quantity * item.price) total")
	 		->leftJoin('od_order_item_v2 item', 'od_order_v2.order_id=item.order_id');
	 	
	 	//筛选有效订单
	 	foreach ($params as $key=>$value){
	 		if($key!='selectstore' && $value=='')
	 			continue;
	 		switch ($key){
	 			case 'start_date':
	 				$query->andWhere("od_order_v2.order_source_create_time>='$start_date'");//timestamp
	 				break;
	 			case 'end_date':
	 				$query->andWhere("od_order_v2.order_source_create_time<='$end_date'");
	 				break;
 				case 'sku':
 					$query->andWhere("sku like '%$value%'");
 					break;
 				case 'title':
 					$query->andWhere("product_name like '%$value%'");
 					break;
	 			case 'selectstore':
	 				$selectstoreStr = '';
	 				if(!empty($value)){
		 				foreach ($value as $v){
		 					$selectstoreStr .= "'".$v."',";
		 				}
	 				}
	 				$selectstoreStr = rtrim($selectstoreStr, ',');
	 				$query->andWhere("od_order_v2.selleruserid in (".$selectstoreStr.")");
	 				break;
 				case 'selectplatform':
 					$selectplatformStr = '';
 					foreach ($value as $v){
 						$selectplatformStr .= "'".$v."',";
 					}
 					$selectplatformStr = rtrim($selectplatformStr, ',');
 					$query->andWhere("od_order_v2.order_source in (".$selectplatformStr.")");
 					break;
 				case 'country':
	 				$currencyStr = '';
	 				foreach ($value as $v){
	 					$currencyStr .= "'".$v."',";
	 				}
	 				$currencyStr = rtrim($currencyStr, ',');
	 				$query->andWhere("od_order_v2.consignee_country_code in (".$currencyStr.")");
	 				break;
	 			default:
	 				break;
	 		}
	 	}
	 	unset($params);
	 	
	 	//只显示已绑定的账号的信息
	 	$bind_stores = '';
	 	$bind_order_souce = '';
	 	$uid = \Yii::$app->subdb->getCurrentPuid();
	 	$platformAccountInfo = PlatformAccountApi::getAllPlatformOrderSelleruseridLabelMap($uid);
	 	//$platformAccountInfo = PlatformAccountApi::getAllAuthorizePlatformOrderSelleruseridLabelMap(false, false, true);//引入平台账号权限后
	 	foreach ($platformAccountInfo as $p_key=>$p_v){
	 		if(!empty($p_v)){
	 			foreach ($p_v as $s_key=>$s_v){
	 				$bind_stores[] = $s_key;
	 			}
	 		}
	 		$bind_order_souce[] = $p_key;
	 	}
	 	if($bind_stores != ''){
	 		$query->andWhere(['in','selleruserid',$bind_stores]);
	 	}
	 	if($bind_order_souce != ''){
	 		$query->andWhere(['in','order_source',$bind_order_souce]);
	 	}
	 	
	 	//排除已取消、未付款，已禁用的商品
	 	$query->andWhere("od_order_v2.order_status>=200 and od_order_v2.order_status<600 and item.quantity>0 and (item.manual_status is null or item.manual_status!='disable') and order_relation in ('normal' , 'sm', 'ss', 'fs')");
	 	 
	 	$query->groupBy("od_order_v2.order_source, od_order_v2.selleruserid, od_order_v2.currency, item.sku, item.product_attributes");
	 	//排序
	 	switch ($sort){
	 		case 'sku':
	 			$query->orderBy("sku ".$sorttype);
	 			break;
 			/*case 'order_count':
 				$query->orderBy("order_count ".$sorttype.", sku");
 				break;
 			case 'total_qty':
 				$query->orderBy("total_qty ".$sorttype.", sku");
 				break;*/
	 		default:
	 			$query->orderBy("order_source ".$sorttype.", selleruserid, sku");
	 			break;
	 	}
	 	
	 	$command = $query->createCommand();
	 	$rows = $command->queryAll();
	 	//print_r($command->getSql());die;
	 	 
	 	if(!empty($rows)){
	 		$currencyInfo = array();    //汇率转换信息
	 		$currency_Symbol = '';
	 		if($currency == 'RMB'){
	 			$currency = 'CNY';
	 			$currency_Symbol = '￥ ';
	 		}
	 		else if($currency == 'USD'){
	 			$currency_Symbol = '$ ';
	 		}
	 		
	 		//绑定平台、店铺信息
	 		$stores = [];
	 		foreach ($platformAccountInfo as $p_key=>$p_v)
	 		{
	 			foreach ($p_v as $s_key=>$s_v)
	 			{
	 				$stores[strtolower($p_key).'_'.strtolower($s_key)] = $s_v;
	 			}
	 		}
	 		
	 		//整理信息
	 		$sales_data = array();
	 		foreach($rows as $k => $sales){
	 			//汇率换算
	 			if($sales['currency'] == 'RMB'){
	 				$sales['currency'] = 'CNY';
	 			}
	 			$EXCHANGE_RATE = 1;
	 			if($sales['currency'] != $currency){
	 				if(array_key_exists($sales['currency'], $currencyInfo)){
	 					$EXCHANGE_RATE = $currencyInfo[$sales['currency']];	
	 				}
	 				else if(\common\helpers\Helper_Currency::getCurrencyIsExist($sales['currency'])){
	 					$EXCHANGE_RATE = \common\helpers\Helper_Currency::convert(1, $currency, $sales['currency']);
	 					$currencyInfo[$sales['currency']] = $EXCHANGE_RATE;
	 				}
	 			}
	 			$sales['total'] = round($sales['total'] * $EXCHANGE_RATE, 2);
	 			$sales['selleruserid'] = empty($stores[strtolower($sales['order_source']).'_'.strtolower($sales['selleruserid'])]) ? '' : $stores[strtolower($sales['order_source']).'_'.strtolower($sales['selleruserid'])];
	 			$sales['product_attributes'] = empty($sales['product_attributes']) ? '' : $sales['product_attributes'];
	 			
	 			//设置唯一key值，判断是否已存在，已存在则合并
	 			$key = $sales['order_source'].'_'.$sales['selleruserid'].'_'.$sales['sku'].'_'.$sales['product_attributes'];
	 			if(array_key_exists($key, $sales_data)){
	 				$sales_data[$key]['order_count'] = $sales_data[$key]['order_count'] + $sales['order_count'];
	 				$sales_data[$key]['total_qty'] = $sales_data[$key]['total_qty'] + $sales['total_qty'];
	 				
	 				$total = str_replace($currency_Symbol, '', $sales_data[$key]['total']);
	 				$sales_data[$key]['total'] = $total + $sales['total'];
	 			}
	 			else{
	 				$sales_data[$key] = $sales;
	 			}
	 			$sales_data[$key]['sort_total'] = $sales_data[$key]['total'];
	 			$sales_data[$key]['total'] = $currency_Symbol.$sales_data[$key]['total'];
	 			
	 			//处理产品属性
	 			$product_attributes = $sales['product_attributes'];
	 			$attributes_arr = json_decode($product_attributes, true);
	 			try{
	 				if(!empty($attributes_arr) && is_array($attributes_arr)){
	 					$product_attributes = '';
	 					foreach ($attributes_arr as $k1 => $v1){
	 						if(is_array($v1)){
	 							foreach($v1 as $k2 => $v2){
	 								$product_attributes .= $k2.': '.$v2.'<br>';
	 							}
	 						}
	 						else{
	 							$product_attributes .= $k1.': '.$v1.'<br>';
	 						}
	 					}
	 					$product_attributes = rtrim($product_attributes, '<br>');
	 				}
	 				else if(strpos($product_attributes, ' + ') !== false){
	 					$attributes_arr = explode(' + ', $product_attributes);
	 					if(is_array($attributes_arr)){
	 						$product_attributes = '';
	 						foreach($attributes_arr as $v){
	 							$product_attributes .= $v.'<br>';
	 						}
	 					}
	 				}
	 				$sales_data[$key]['product_attributes'] = rtrim($product_attributes, "<br>");
	 			}
	 			catch(\Exception $ex){
	 			}
	 			
	 			//$sales_data[] = $sales;
	 		}
	 		
	 		unset($rows);
	 		
	 		//特殊列排序
	 		if($sort == 'total' || $sort == 'order_count' || $sort == 'total_qty'){
	 			if($sort == 'total'){
	 				$sort_name = 'sort_total';
	 			}
	 			else{
	 				$sort_name = $sort;
	 			}
	 			
	 			$new_data = array();
	 			$dos = array();
	 			foreach ($sales_data as $key => $val){
	 				$dos[$key] = $val[$sort_name];
	 			}
	 			//排序
	 			if(empty($sorttype))
	 				array_multisort($dos, SORT_ASC, $sales_data);
	 			else
	 				array_multisort($dos, SORT_DESC, $sales_data);
	 		}
	 		
	 		//重置索引
	 		$sales_data = array_values($sales_data);
	 		
	 		//当条/页为-1时，则不需分页
		 	if($per_page == -1){
		 		$result['data'] = $sales_data;
		 	}
		 	else{
		 		$count = count($sales_data);
		 		$pagination = new Pagination([
		 				'page'=> $page,
		 				'pageSize' => $per_page,
		 				'totalCount' => $count,
		 				'pageSizeLimit'=>[20,200],//每页显示条数范围
		 				]);
		 		$result['pagination'] = $pagination;
		 
		 		$p_s = $per_page * $page ;
		 		$p_e = $per_page * ($page + 1) - 1;
		 		if($p_e > $count - 1)
		 			$p_e = $count - 1;
		 		for($n = $p_s; $n <= $p_e; $n++){
		 			$result['data'][] = $sales_data[$n];
		 		}
		 		$result['count'] = $count;
		 	}
	 	 	
	 		$result['status'] = 1;
	 	}
	 	else{
	 		//分页显示
	 		$pagination = new Pagination([
	 				'page'=> $page,
	 				'pageSize' => $per_page,
	 				'totalCount' => 0,
	 				'pageSizeLimit'=>[20,200],//每页显示条数范围
	 				]);
	 		$result['pagination'] = $pagination;
	 
	 		$result['data'] = array();
	 		$result['status'] = 1;
	 	}
	 	
	 	return $result;
	 
	 }//end of function
	 
	 /**
	  +---------------------------------------------------------------------------------------------
	  * 获取商品表现信息
	  +---------------------------------------------------------------------------------------------
	  * @access static
	  +---------------------------------------------------------------------------------------------
	  * @param     $params			查询条件
	  +---------------------------------------------------------------------------------------------
	  * @return	array[]
	  +---------------------------------------------------------------------------------------------
	  * log			name	date					note
	  * @author		lrq		2017/09/05				初始化
	  +---------------------------------------------------------------------------------------------
	  **/
	 static public function getProductDetailsInfo($params)
	 {
	 	$result['data'] = array();
	 	$result['status'] = 1;
	 	
	 	$per_page = empty($params['per-page']) ? 20 : $params['per-page'];
	 	$page = empty($params['page']) ? 0 : $params['page'];
	 	$currency = empty($params['currency']) ? 'RMB' : $params['currency'];
	 	$start_date = empty($params['start_date']) ? time() : strtotime($params['start_date']);
	 	$end_date = empty($params['end_date']) ? time() : strtotime($params['end_date']." +1 day");
	 	$sort = $params['sort'];
	 	$sorttype = $params['sorttype'];
	 	
	 	$currency_Symbol = '';
	 	if($currency == 'RMB'){
	 		$currency = 'CNY';
	 		$currency_Symbol = '￥ ';
	 	}
	 	else if($currency == 'USD'){
	 		$currency_Symbol = '$ ';
	 	}
	 	//所有币种信息
	 	$currency_str = "(case when od_order_v2.currency='' then 1 ";
	 	$query_currency = OdOrder::find()->select("currency");
	 	foreach ($params as $key=>$value){
	 		if($value=='')
	 			continue;
	 		switch ($key){
	 			case 'start_date':
	 				$query_currency->andWhere("od_order_v2.order_source_create_time>='$start_date'");
	 				break;
	 			case 'end_date':
	 				$query_currency->andWhere("od_order_v2.order_source_create_time<='$end_date'");
	 				break;
	 			default:
	 				break;
	 		}
	 	}
	 	$currencys = $query_currency->distinct("currency")->asArray()->all();
 		foreach($currencys as $val){
 			$currencyInfo = array();    //汇率转换信息
 			
 			$from_currency = $val['currency'] == 'PH' ? 'PHP' : $val['currency'];
 			//获取最新汇率，转换RMB
 			$EXCHANGE_RATE = ProfitHelper::GetExchangeRate($from_currency, $currency);
 			if(empty($EXCHANGE_RATE)){
 				$EXCHANGE_RATE = 1;
 			}
 			$currency_str .= " when od_order_v2.currency='".$val['currency']."' then ".$EXCHANGE_RATE;
	 	}
	 	$currency_str .= " else 1 end)";
	 	
	 	// 明细信息
	 	$sql = "select count(od_order_v2.order_id) order_count,
	 				item.sku, item.root_sku, sum(item.quantity) total_qty, sum(convert(item.quantity * item.price * $currency_str, decimal(18,2) )) total
	 			 	from od_order_v2 left join od_order_item_v2 item on od_order_v2.order_id=item.order_id";
	 	
	 	//筛选有效订单
	 	$where_sql = '';
	 	foreach ($params as $key=>$value){
	 		if($value=='')
	 			continue;
	 		switch ($key){
	 			case 'start_date':
	 				$where_sql .= " and od_order_v2.order_source_create_time>='$start_date'";
	 				break;
	 			case 'end_date':
	 				$where_sql .= " and od_order_v2.order_source_create_time<='$end_date'";
	 				break;
	 			case 'sku':
	 				$where_sql .= " and (sku like '%$value%' or root_sku like '%$value%')";
	 				break;
	 			default:
	 				break;
	 		}
	 	}
	 	
	 	//只显示已绑定的账号的信息
	 	$bind_stores = '';
	 	$bind_order_souce = '';
	 	$uid = \Yii::$app->subdb->getCurrentPuid();
	 	$platformAccountInfo = PlatformAccountApi::getAllPlatformOrderSelleruseridLabelMap($uid);
	 	foreach ($platformAccountInfo as $p_key=>$p_v){
	 		if(!empty($p_v)){
	 			foreach ($p_v as $s_key=>$s_v){
	 				$bind_stores .= "'".$s_key."',";
	 			}
	 		}
	 		$bind_order_souce .= "'".$p_key."',";
	 	}
	 	if($bind_stores != ''){
	 		$bind_stores = rtrim($bind_stores, ',');
	 		$where_sql .= " and od_order_v2.selleruserid in ($bind_stores)";
	 	}
	 	if($bind_order_souce != ''){
	 		$bind_order_souce = rtrim($bind_order_souce, ',');
	 		$where_sql .= " and od_order_v2.order_source in ($bind_order_souce)";
	 	}
	 	 
	 	//排除已取消、未付款，已禁用的商品
	 	$where_sql = " where od_order_v2.order_status>=200 and od_order_v2.order_status<600 
	 	            and order_relation in ('normal' , 'sm', 'ss', 'fs') 
	 	            and item.quantity>0 and (item.manual_status is null or item.manual_status!='disable') and item.sku!='' "
	 	            .$where_sql;
	 
	 	$groupby_sql = " group by item.sku, item.root_sku";
	 	//排序
	 	$sort_sql = '';
	 	switch ($sort){
	 		case ' ':
	 			/*$query->orderBy(" case when root_sku is null or root_sku='' then sku else root_sku end ".$sorttype);
	 			break;
	 			case 'order_count':
	 			 $query->orderBy("order_count ".$sorttype.", sku");
	 			break;
	 			case 'total_qty':
	 			$query->orderBy("total_qty ".$sorttype.", sku");*/
	 			break;
	 		default:
	 			$sort_sql = ",(case when root_sku is null or root_sku='' then sku else root_sku end) ".$sorttype;
	 			break;
	 	}
	 	
	 	$sql = $sql.$where_sql.$groupby_sql.(empty($sort_sql) ? '' : ' order by '.ltrim($sort_sql, ','));
	 	$command = Yii::$app->subdb->createCommand($sql);
	 	$rows = $command->queryAll();
	 	//print_r($command->getSql());die;
	 	//print_r($rows);die;
	 	
	 	if(!empty($rows)){
	 		//查询对应别名信息
	 		$alias_sku = [];
	 		foreach($rows as $row){
	 			if(!empty($row['sku']) && empty($row['root_sku'])){
	 				$alias_sku[] = $row['sku'];
	 			}
	 		}
	 		$alias_list = [];
	 		$alias = ProductAliases::find()->select("sku, alias_sku")->where(['alias_sku' => $alias_sku])->orderBy("platform, selleruserid")->distinct("sku, alias_sku")->asArray()->all();
	 		foreach($alias as $alia){
	 			$alias_list[$alia['alias_sku']] = $alia['sku'];
	 		}
	 		
	 		//整理信息
	 		$details = array();
	 		$pro_sku = array();
	 		foreach($rows as $k => $info){
	 			$sku = '';
	 			if(!empty($info['root_sku'])){
	 				$sku = $info['root_sku'];
	 			}
	 			else{
	 				//判断sku是否属于别名
	 				if(array_key_exists($info['sku'], $alias_list)){
	 					$sku = $alias_list[$info['sku']];
	 				}
	 				else{
	 					$sku = $info['sku'];
	 				} 
	 			}
	 			$pro_sku[] = $sku;
	 			
	 			if(array_key_exists($sku, $details)){
	 				$details[$sku]['order_count'] = $details[$sku]['order_count'] + $info['order_count'];
	 				$details[$sku]['total_qty'] = $details[$sku]['total_qty'] + $info['total_qty'];
	 			
	 				$total = str_replace($currency_Symbol, '', $details[$sku]['total']);
	 				$details[$sku]['total'] = $total + $info['total'];
	 			}
	 			else{
	 				$details[$sku] = $info;
	 			}
	 			$details[$sku]['sort_total'] = $details[$sku]['total'];
	 			$details[$sku]['total'] = $currency_Symbol.$details[$sku]['total'];
	 		}
	 		unset($rows);
	 		
	 		//查询对应商品信息
	 		$pros_arr = array();
	 		$pros = Product::find()->select(['sku', 'name', 'photo_primary'])->where(['sku' => $pro_sku])->asArray()->all();
	 		foreach($pros as $pro){
	 		    $pros_arr[$pro['sku']] = $pro;
	 		}
	 		unset($pros);
	 		foreach($details as $key => $val){
	 		    if(array_key_exists($key, $pros_arr)){
	 		    	if(!empty($params['name'])){
	 		    		if(strpos($pros_arr[$key]['name'], $params['name']) === false){
	 		    			unset($details[$key]);
	 		    			continue;
	 		    		}
	 		    	}
	 				$details[$key]['name'] = $pros_arr[$key]['name'];
	 				$details[$key]['photo_primary'] = $pros_arr[$key]['photo_primary'];
	 			}
	 			else{
	 			    unset($details[$key]);
	 			}
	 		}
	 		unset($pros_arr);
	 
	 		//特殊列排序
	 		if($sort == 'total' || $sort == 'order_count' || $sort == 'total_qty'){
	 			if($sort == 'total'){
	 				$sort_name = 'sort_total';
	 			}
	 			else{
	 				$sort_name = $sort;
	 			}
	 				
	 			$new_data = array();
	 			$dos = array();
	 			foreach ($details as $key => $val){
	 				$dos[$key] = $val[$sort_name];
	 			}
	 			//排序
	 			if(empty($sorttype))
	 				array_multisort($dos, SORT_ASC, $details);
	 			else
	 				array_multisort($dos, SORT_DESC, $details);
	 		}
	 
	 		//重置索引
	 		$details = array_values($details);
	 
	 		//当条/页为-1时，则不需分页
	 		if($per_page == -1){
	 			$result['data'] = $details;
	 		}
	 		else{
	 			$count = count($details);
	 			$pagination = new Pagination([
	 					'page'=> $page,
	 					'pageSize' => $per_page,
	 					'totalCount' => $count,
	 					'pageSizeLimit'=>[20,200],//每页显示条数范围
	 					]);
	 			$result['pagination'] = $pagination;
	 				
	 			$p_s = $per_page * $page ;
	 			$p_e = $per_page * ($page + 1) - 1;
	 			if($p_e > $count - 1)
	 				$p_e = $count - 1;
	 			for($n = $p_s; $n <= $p_e; $n++){
	 				$result['data'][] = $details[$n];
	 			}
	 			$result['count'] = $count;
	 		}
	 		
	 		$skus = array();
	 		if(!empty($result['data'])){
		 		foreach($result['data'] as $val){
		 			$skus[] = $val['sku'];
		 		}
		 		if(!empty($skus)){
			 		//整理采购信息
			 		$pur_arr = array();
			 		$pur_condition = '';
			 		foreach ($params as $key=>$value){
			 			if($value=='')
			 				continue;
			 			switch ($key){
			 				case 'start_date':
			 					$pur_condition .= " and unix_timestamp(create_time)>='$start_date'";
			 					break;
			 				case 'end_date':
			 					$pur_condition .= " and unix_timestamp(create_time)<='$end_date'";
			 					break;
			 				default:
			 					break;
			 			}
			 		}
			 		$purchase = PurchaseItems::find()
			 		->select("sku, sum(qty) total_qty, sum(qty * price) total")
			 		->where(['sku' => $skus])->andWhere(" purchase_id in (select id from pc_purchase where status=5 $pur_condition)")
			 		->groupBy("sku")
			 		->asArray()->all();
			 		foreach($purchase as $val){
			 			$pur_arr[$val['sku']] = $val;
			 		}
			 		//整理库存信息
			 		$stock_arr = array();
			 		$stock = ProductStock::find()
			 		->select("sku, sum(qty_in_stock) qty_in_stock, sum(qty_purchased_coming) qty_purchased_coming")
			 		->where(['sku' => $skus])->andWhere(" warehouse_id in (select warehouse_id from wh_warehouse where is_active='Y')")
			 		->groupBy("sku")
			 		->asArray()->all();
			 		foreach($stock as $val){
			 			$stock_arr[$val['sku']] = $val;
			 		}
			 		foreach($result['data'] as $key => $val){
			 			if(array_key_exists($val['sku'], $pur_arr)){
			 				$result['data'][$key]['pur_qty'] = $pur_arr[$val['sku']]['total_qty'];
			 				$result['data'][$key]['pur_total'] = '￥ '.$pur_arr[$val['sku']]['total'];
			 			}
			 			else{
			 				$result['data'][$key]['pur_qty'] = '0';
			 				$result['data'][$key]['pur_total'] = '￥ 0';
			 			}
			 		
			 			if(array_key_exists($val['sku'], $stock_arr)){
			 				$result['data'][$key]['qty_in_stock'] = $stock_arr[$val['sku']]['qty_in_stock'];
			 				$result['data'][$key]['qty_purchased_coming'] = empty($stock_arr[$val['sku']]['qty_purchased_coming']) ? 0 : $stock_arr[$val['sku']]['qty_purchased_coming'];
			 			}
			 			else{
			 				$result['data'][$key]['qty_in_stock'] = '0';
			 				$result['data'][$key]['qty_purchased_coming'] = '0';
			 			}
			 		}
		 		}
	 		}
	 	 	
	 		$result['status'] = 1;
	 	}
	 	else{
	 		//分页显示
	 		$pagination = new Pagination([
	 				'page'=> $page,
	 				'pageSize' => $per_page,
	 				'totalCount' => 0,
	 				'pageSizeLimit'=>[20,200],//每页显示条数范围
	 				]);
	 		$result['pagination'] = $pagination;
	 
	 		$result['data'] = array();
	 		$result['status'] = 1;
	 	}
	 	
	 	return $result;
	 
	 }//end of function
	 
	 /**
	  * +----------------------------------------------------------
	  * 销售统计，导出Excel
	  * +----------------------------------------------------------
	  * @access static
	  *+----------------------------------------------------------
	  * @param	$data    导出的数据集筛选信息
	  * @param	$type    是否前端导出
	  *+----------------------------------------------------------
	  * @return
	  * +----------------------------------------------------------
	  * log			name	date					note
	  * @author 	lrq		2017/04/01				初始化
	  *+----------------------------------------------------------
	  *
	  */
	 public static function ExportSalesExcel($data, $type = false){
	 	$rtn['success'] = 1;
	 	$rtn['message'] = '';
	 
	 	try{
	 		$journal_id = SysLogHelper::InvokeJrn_Create("Statistics", __CLASS__, __FUNCTION__ ,array($data));
	 
	 		$ret = self::getSalesInfo($data);
	 		
	 		$items_arr = ['photo_primary'=>'图片','order_source'=>'平台','selleruserid'=>'店铺','sku'=>'SKU','product_name'=>'产品标题','product_attributes'=>'产品属性','order_count'=>'订单总量','total_qty'=>'销售总量','total'=>'销售金额'];
	 		$keys = array_keys($items_arr);
	 		$excel_data = [];
	 		
	 		foreach ($ret['data'] as $index=>$row)
	 		{
	 			$tmp=[];
	 			foreach ($keys as $key){
	 				if(isset($row[$key])){
	 					if(in_array($key,['sku']) && is_numeric($row[$key]))
	 						$tmp[$key]=' '.$row[$key];
	 					//替换换行符
	 					else if(in_array($key,['product_attributes']))
	 						$tmp[$key]=str_replace('<br>', ";", $row[$key]);
	 					else
	 						$tmp[$key]=(string)$row[$key];
	 				}
	 				else
	 					$tmp[$key]=$row[$key];
	 			}
	 			$excel_data[$index] = $tmp;
	 		}
	 		
	 		$rtn = ExcelHelper::exportToExcel($excel_data, $items_arr, 'sales_'.date('Y-m-dHis',time()).".xls", ['photo_primary'=>['width'=>50,'height'=>50]], $type, ['setWidth'=>20], 200);
	 		
	 		SysLogHelper::InvokeJrn_UpdateResult($journal_id, $rtn);
	 		$rtn['count'] = count($excel_data);
	 		unset($excel_data);
	 	}
	 	catch (\Exception $e) {
 			$rtn['success'] = 0;
			$rtn['message'] = '导出失败：'.$e->getMessage();
 		}
	 
	 	return $rtn;
	 }//end of function
	 
	 /**
	  * +----------------------------------------------------------
	  * 商品表现，导出Excel
	  * +----------------------------------------------------------
	  * @access static
	  *+----------------------------------------------------------
	  * @param	$data    导出的数据集筛选信息
	  * @param	$type    是否前端导出
	  *+----------------------------------------------------------
	  * @return
	  * +----------------------------------------------------------
	  * log			name	date					note
	  * @author 	lrq		2017/09/07				初始化
	  *+----------------------------------------------------------
	  *
	  */
	 public static function ExportProductDetailsExcel($data, $type = false){
	 	$rtn['success'] = 1;
	 	$rtn['message'] = '';
	
	 	try{
	 		$journal_id = SysLogHelper::InvokeJrn_Create("Statistics", __CLASS__, __FUNCTION__ ,array($data));
	 
	 		$ret = self::getProductDetailsInfo($data);
	 
	 		$items_arr = ['photo_primary'=>'图片','sku'=>'SKU','name'=>'商品名称','order_count'=>'订单总量','total_qty'=>'销售总量','total'=>'销售金额','pur_qty'=>'采购数量','pur_total'=>'采购金额','qty_in_stock'=>'库存数量','qty_purchased_coming'=>'在途数量'];
	 		$keys = array_keys($items_arr);
	 		$excel_data = [];
	 
	 		foreach ($ret['data'] as $index=>$row)
	 		{
	 			$tmp=[];
	 			foreach ($keys as $key){
	 				if(isset($row[$key])){
	 					if(in_array($key,['sku']) && is_numeric($row[$key]))
	 						$tmp[$key]=' '.$row[$key];
	 					else
	 						$tmp[$key]=(string)$row[$key];
	 				}
	 				else
	 					$tmp[$key]=$row[$key];
	 			}
	 			$excel_data[$index] = $tmp;
	 		}
	 		
	 		$rtn = ExcelHelper::exportToExcel($excel_data, $items_arr, 'product_'.date('Y-m-dHis',time()).".xls", ['photo_primary'=>['width'=>50,'height'=>50]], $type, ['setWidth'=>20], 200);
	 
	 		SysLogHelper::InvokeJrn_UpdateResult($journal_id, $rtn);
	 		$rtn['count'] = count($excel_data);
	 		unset($excel_data);
	 	}
	 	catch (\Exception $e) {
	 		$rtn['success'] = 0;
	 		$rtn['message'] = '导出失败：'.$e->getMessage();
	 	}
	 
	 	return $rtn;
	 }//end of function
	 
	 //检测重新统计dash_board
	 public static function CheckAndRereshDash(){
	 	try{
		 	$puid = \Yii::$app->subdb->getCurrentPuid();
		 	
		 	//判断是否更新过
		 	$redis_key_lv1 = 'RefreshAchievementInfo2';
		 	$redis_key_lv2 = $puid;
		 	$redis_val = RedisHelper::RedisGet($redis_key_lv1, $redis_key_lv2);
		 	
		 	if(empty($redis_val)){
		 		//判断是否需要重新刷新，当11月1日前的信息相差大时，则刷新
		 		$command = Yii::$app->subdb->createCommand("SELECT count(1) as count FROM `db_sales_daily` WHERE thedate<'2017-11-01' and use_module_type!='statistics'");
		 		$row = $command->queryOne();
		 		$count_dash = $row['count'];
		 		$command = Yii::$app->subdb->createCommand("SELECT count(1) as count FROM `db_sales_daily` WHERE thedate<'2017-11-01' and use_module_type='statistics'");
		 		$row2 = $command->queryOne();
		 		$count_satatic = $row2['count'];
		 		
		 		if($count_dash > $count_satatic * 3){
		 			\eagle\modules\dash_board\helpers\DashBoardHelper::initSalesCount($puid, 90);
		 			
		 			\eagle\modules\statistics\helpers\StatisticsHelper::RefreshDashBoardProfit($puid);
		 		}
		 		
		 		RedisHelper::RedisSet($redis_key_lv1, $redis_key_lv2, 1);
		 	}
	 	}
	 	catch(\Exception $ex){
	 		
	 	}
	 }
}
