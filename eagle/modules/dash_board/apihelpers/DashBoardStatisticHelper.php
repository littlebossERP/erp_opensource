<?php
/**
 * @link http://www.witsion.com/
 * @copyright Copyright (c) 2014 Yii Software LLC
 * @license http://www.witsion.com/
 */
namespace eagle\modules\dash_board\apihelpers;

use yii;
use yii\base\Exception;
use eagle\modules\dash_board\models\SalesDaily;
use common\helpers\Helper_Currency;
use eagle\modules\util\helpers\SysLogHelper;
use eagle\modules\util\helpers\RedisHelper;

class DashBoardStatisticHelper{
	public static $CDISCOUNT = 'CDISCOUNT';
	public static $PRICEMINISTER = 'PRICEMINISTER';
	public static $AMAZON = 'AMAZON';
	public static $EBAY = 'EBAY';
	public static $ALIEXPRESS = 'ALIEXPRESS';
	public static $BONANZA = 'BONANZA';
	public static $RUMALL = 'RUMALL';
	public static $DHGATE = 'DHGATE';
	public static $LINIO = 'LINIO';
	public static $LAZADA = 'LAZADA';
	public static $JUMIA = 'JUMIA';
	public static $WISH = 'WISH';
	
	/**
	 * 获取平台的不同order_type值，用于对Dash Board数据的筛选展示，其他方法也可按需调用
	 * @param	array	$platforms	平台array
	 * @return 	array
	 * @author	lzhl	2016/12/28	初始化
	 */
	public static function getPlatformOrderTypeList($platforms){
		//列出所有平台的订单类型可能值
		//要手动跟新
		$allPlatformOrderTypeList=[
			'amazon'=>['AFN'=>'AFN订单(FBA)','MFN'=>'MFN订单'],
			'cdiscount'=>['FBC'=>'FBC订单','NORMAL'=>'普通订单'],
		];
		
		$platformOrderTypeList=[];
		foreach ($platforms as $platform){
			if(!empty($allPlatformOrderTypeList[$platform]))
				$platformOrderTypeList[$platform] = $allPlatformOrderTypeList[$platform];
		}
		
		return $platformOrderTypeList;
	}
	
	/**
	 * 获取指定平台的order_type，在db_sales_daily中的对应值
	 * @param	string	$platforms
	 * @param	string	$order_type		调用者传入的order_type值
	 * @return 	string or NULL
	 * @author	lzhl	2016/12/28	初始化
	 */
	public static function getPlatformOrderType($platform,$order_type){
		$platform = strtolower($platform);
		switch ($platform){
			case 'amazon':
				if(strtoupper($order_type)=='AFN')
					$order_type='AFN';//amazon FBA 订单
				elseif(strtoupper($order_type)=='MFN')
					$order_type='MFN';
				else 
					$order_type='MFN';
				break;
			case 'cdiscount':
				if(!is_null($order_type) && strtoupper($order_type)=='FBC')
					$order_type='FBC';//CD FBC 订单
				else
					$order_type='NORMAL';
				break;
			default:
				break;
		}
		if(empty($order_type)) $order_type=null;
		return $order_type;
	}
####################################### 引入平台账号级别权限前的逻辑 start ######################################
	//下面是 OMS status 计数器的使用 方法，传进来的 platform 请使用本class的public常量
	public static function OMSStatusAdd($puid , $platform, $status , $increment=1,$comment='') {
		//if puid =2921, try to make journal
		if ($puid == 2921){
		//	$journal_id = SysLogHelper::InvokeJrn_Create("Dashboard",__CLASS__, __FUNCTION__ , array($puid,$platform,$status,$increment,$comment));
		}
		$key = self::combineKeyForOMSStatus($platform ,$status );
		return self::CounterAdd($puid, $key,$increment);
	}
	
	public static function OMSStatusSet($puid , $platform, $status , $increment=1) {
		if ($puid == 2921){
		//	$comment = "set this initial value";
		//	$journal_id = SysLogHelper::InvokeJrn_Create("Dashboard",__CLASS__, __FUNCTION__ , array($puid,$platform,$status,$increment,$comment));
		}
		$key = self::combineKeyForOMSStatus($platform ,$status );
		return self::CounterSet($puid, $key,$increment);
	}
	
	public static function OMSStatusDelete($puid , $platform, $status ) {
		$key = self::combineKeyForOMSStatus($platform ,$status );
		return self::CounterDelete($puid, $key);
	}
	
	public static function OMSStatusGet($puid , $platform, $status ) {
		$key = self::combineKeyForOMSStatus($platform ,$status );
		return self::CounterGet($puid, $key);
	}
	
	//下面是 通用计数器 Counter 的用法，只要传入的key 和读取的key相同就可以了,key = 'CustomerService_Aliexpress_Unread'
	//'CustomerService_Aliexpress_Sent_Fail'
	public static function CounterAdd($puid , $key , $increment=1) {
		$rtn = 0;
		$path =  $puid."_".$key;
		try{
			//$rtn = \Yii::$app->redis->HINCRBY("DASHBOARD_STS",$path,  $increment);
			$rtn = RedisHelper::RedisAdd("DASHBOARD_STS",$path,  $increment);
			//if ($puid == 297)
			//	$journal_id = SysLogHelper::InvokeJrn_Create("Dashboard",__CLASS__, __FUNCTION__ , array($puid,"DASHBOARD_STS",$path,$rtn));
			
		}catch(\Exception $e) {
			try{ 
				sleep(4);
				//$rtn = \Yii::$app->redis->HINCRBY("DASHBOARD_STS",$path,  $increment);
				$rtn = RedisHelper::RedisAdd("DASHBOARD_STS",$path,  $increment);
			//	if ($puid == 297)
			//		$journal_id = SysLogHelper::InvokeJrn_Create("Dashboard",__CLASS__, __FUNCTION__ , array($puid,"Again after exception DASHBOARD_STS",$path,$rtn));	
			}catch(\Exception $e) {
				$rtn = -1;
			}
		}
		
		return $rtn;
	}
	
	public static function CounterSet($puid , $key , $val=0) {
		 
		$rtn = 0;
		try{
			//$rtn = \Yii::$app->redis->hset("DASHBOARD_STS", $puid."_".$key,  $val);
			$rtn = RedisHelper::RedisSet("DASHBOARD_STS", $puid."_".$key,  $val);
				
		}catch(\Exception $e) {
			try{ sleep(4);
				//$rtn = \Yii::$app->redis->hset("DASHBOARD_STS", $puid."_".$key,  $val);
				$rtn = RedisHelper::RedisSet("DASHBOARD_STS", $puid."_".$key,  $val);
			}catch(\Exception $e) {
				$rtn = -1;
			}
		}
		
		return $rtn;
		
	}
	
	public static function CounterDelete($puid , $key  ) {
	 
		$rtn = 0;
		try{
			//$rtn = \Yii::$app->redis->hdel("DASHBOARD_STS", $puid."_".$key );
			$rtn = RedisHelper::RedisDel("DASHBOARD_STS", $puid."_".$key );
		}catch(\Exception $e) {
			try{ sleep(4);
				//$rtn = \Yii::$app->redis->hdel("DASHBOARD_STS", $puid."_".$key );
				$rtn = RedisHelper::RedisDel("DASHBOARD_STS", $puid."_".$key );
			}catch(\Exception $e) {
				$rtn = -1;
			}
		}
		
		return $rtn;
	}
	
	public static function CounterGet($puid , $key  ) {
		$rtn = 0;
		try{
			//$rtn = \Yii::$app->redis->hget("DASHBOARD_STS", $puid."_".$key );
			$rtn = RedisHelper::RedisGet("DASHBOARD_STS", $puid."_".$key );
		}catch(\Exception $e) {
			try{ sleep(4);
				//$rtn = \Yii::$app->redis->hget("DASHBOARD_STS", $puid."_".$key );
				$rtn = RedisHelper::RedisGet("DASHBOARD_STS", $puid."_".$key );
			}catch(\Exception $e) {
				$rtn = -1;
			}
		}
		
		return $rtn;
	}
####################################### 引入平台账号级别权限前的逻辑end ######################################

	
	//下面是 OMS status 计数器的使用 方法，传进来的 platform 请使用本class的public常量
	public static function OMSStatusAdd2($puid, $platform, $seller_id, $status, $increment=1,$comment='') {
		$key = self::combineKeyForOMSStatus($platform, $status );
		return self::CounterAdd2($puid, $key, $seller_id, $increment);
	}
	
	public static function OMSStatusSet2($puid , $platform, $seller_id, $status , $increment=1) {
		$key = self::combineKeyForOMSStatus($platform ,$status );
		return self::CounterSet2($puid, $key, $seller_id, $increment);
	}
	
	public static function OMSStatusDelete2($puid , $platform, $status ) {
		$key = self::combineKeyForOMSStatus($platform ,$status );
		return self::CounterDelete2($puid, $key);
	}
	
	public static function OMSStatusGet2($puid , $platform, $seller_id, $status ) {
		$key = self::combineKeyForOMSStatus($platform ,$status );
		return self::CounterGet2($puid, $key, $seller_id);
	}
	
	public static function OMSStatusGetSum2($puid , $platform, $seller_ids, $status ) {
		$key = self::combineKeyForOMSStatus($platform ,$status );
		return self::CounterGetSum2($puid, $key, $seller_ids);
	}
	
	//下面是 通用计数器 Counter 的用法，只要传入的key 和读取的key相同就可以了,key = 'CustomerService_Aliexpress_Unread'
	//'CustomerService_Aliexpress_Sent_Fail'
	public static function CounterAdd2($puid , $key , $seller_id , $increment=1) {
		$rtn = 0;
		$val = array();
		$keyL = 'DASHBOARD_STS';
		$path =  $puid."_".$key;
		try{
			$record = RedisHelper::RedisGet($keyL, $path);
			//echo "key:".var_dump($key);
			// "record:".var_dump($record);
			if(empty($record) || $record==-1){
				//获取失败或者未有记录
				$val[$seller_id] = (int)$increment;
			}else{
				$val = json_decode($record,true);
				if(!is_array($val)){//非数组格式时，为旧数据，删除他
					RedisHelper::RedisDel($keyL, $path);
					$val = array();
				}
				if(!empty($val[$seller_id]))
					$val[$seller_id] = (int)$val[$seller_id] + (int)$increment;
				else 
					$val[$seller_id] = (int)$increment;
			}
			//echo "record:".var_dump($val);
			try{
				$rtn = RedisHelper::RedisSet($keyL, $path,json_encode($val));
			}catch(\Exception $e) {
				try{
					sleep(4);
					$rtn = RedisHelper::RedisSet($keyL, $path,json_encode($val));
				}catch(\Exception $e) {
					$rtn = -1;
				}
			}
		}catch(\Exception $e) {
			$rtn = -1;
		}
		return $rtn;
	}
	
	public static function CounterSet2($puid , $key , $seller_id , $increment=0) {
		$rtn = 0;
		$keyL = 'DASHBOARD_STS';
		$path =  $puid."_".$key;
		try{
			$record = RedisHelper::RedisGet($keyL, $path);
			if(empty($record) || $record==-1){
				//获取失败或者未有记录
				$val = array();
			}else{
				$val = json_decode($record,true);
			}
			if(!is_array($val)){//非数组格式时，为旧数据，删除他
				RedisHelper::RedisDel("DASHBOARD_STS", $puid."_".$key);
			}
			
			$val[$seller_id] = $increment;
			
			try{
				$rtn = RedisHelper::RedisSet($keyL, $path,json_encode($val));
			}catch(\Exception $e) {
				try{
					sleep(4);
					$rtn = RedisHelper::RedisSet($keyL, $path,json_encode($val));
				}catch(\Exception $e) {
					$rtn = -1;
				}
			}
		}catch(\Exception $e) {
			$rtn = -1;
		}
		return $rtn;
	}
	
	public static function CounterDelete2($puid , $key  ) {
		$rtn = 0;
		try{
			$rtn = RedisHelper::RedisDel("DASHBOARD_STS", $puid."_".$key );
		}catch(\Exception $e) {
			try{
				sleep(4);
				$rtn = RedisHelper::RedisDel("DASHBOARD_STS", $puid."_".$key );
			}catch(\Exception $e) {
				$rtn = -1;
			}
		}
		return $rtn;
	}
	
	public static function CounterGet2($puid , $key , $seller_ids='' ) {
		$result = [];
		if(empty($seller_ids))
			return 0;
		try{
			$rtn = RedisHelper::RedisGet("DASHBOARD_STS", $puid."_".$key );
			//echo "<br>###11###".$puid."_".$key;
			//var_dump($rtn);
		}catch(\Exception $e) {
			//echo "<br>###22###".$puid."_".$key." Exception!";
			try{
				sleep(4);
				$rtn = RedisHelper::RedisGet("DASHBOARD_STS", $puid."_".$key );
			}catch(\Exception $e) {
				return -1;
			}
		}
		
		if(empty($rtn) || $rtn==-1){
			//获取失败或者未有记录
			return 0;
		}else{
			$val = json_decode($rtn,true);
		}
		//var_dump($val);
		if(empty($val)) 
			return 0;
		if(!is_array($val)){//非数组格式时，为旧数据，删除他
			RedisHelper::RedisDel("DASHBOARD_STS", $puid."_".$key);
			return 0;
		}
		
		
		if(is_string($seller_ids))
			$seller_arr = [$seller_ids];
		else
			$seller_arr = $seller_ids;
		foreach ($seller_arr as $seller_id){
			try{
				$result[$seller_id] = empty($val[$seller_id])?0 : (int)$val[$seller_id];
			}catch(\Exception $e) {
				//echo "<br>###33###".$puid."_".$key." Exception: ".print_r($e->getMessage());
				$result[$seller_id] = 0;
			}
		}
		return empty($result)?0:$result;
	}
	
	public static function CounterGetSum2($puid , $key , $seller_ids='' ){
		if(empty($seller_ids))
			return 0;
		$counter = self::CounterGet2($puid, $key, $seller_ids);
		//var_dump($key);
		//var_dump($counter);
		if(empty($counter) || $counter==-1)
			return 0;
		else 
			return array_sum($counter);
	}
#########################################################
	//下面是订单利润统计，由统计的程序调用这个func，使得dashboard模块自动修正当天的利润情况
	//，order date 可以是时间戳，或者是 date time 的北京时间， profit cny是人民币的利润值，可以填入负数，减去当天的利润累计
	public static function SalesProfitAdd($order_date,$platform,$order_type,
			$seller_id ,$profit_cny=0,$merge=false, $order_status = ''){
		//重用计算订单金额，订单量的接口即可，不过传入金额和订单量的变化值=0
		return self::SalesStatisticAdd($order_date,$platform,$order_type,
			                        $seller_id , $currency="USD", $amount=0,$orderCount=0,$profit_cny,$merge, $order_status);
	}
	
	//下面是订单销售量 销售额的统计方法，order date 可以是时间戳，或者是 date time 的北京时间
	public static function SalesStatisticAdd($order_date,$platform,$order_type,
			                        $seller_id , $currency, $amount=0,$orderCount=1,$profit_cny=0,$merge=false, $order_status=''){
		if (empty($amount))
			$amount = 0;
		
		//如果是timestamp，时间戳的，转为本地北京时间保存
		if (is_numeric($order_date) ){
			$order_date = date('Y-m-d H:i:s',$order_date);
		}
		if (strlen($order_date)>10){
			$order_date = substr($order_date,0,10);
		}
		
		$platform = strtoupper($platform);
		
		//吧amount转成USD的值来保存
		if ($currency=='USD')
			$amount_usd = $amount;
		else
			$amount_usd = Helper_Currency::convertThisCurrencyToUSDFromDay($currency,$amount );
		
		//如果出现同一平台同一账号，多原始币种的情况，就要将多种原始币种强制转换长USD，合并成一条记录
		if($merge!==false){
			$currency = 'USD';
		}
		
		$order_type=self::getPlatformOrderType($platform, $order_type);
		
		//为了节省IO，不适用model find，而是直接走update，绝大部分case，直接走update 1次IO就可以了
		$initSellerIdSql = "update `db_sales_daily` set sales_count=sales_count + $orderCount , sales_amount_original_currency =sales_amount_original_currency + $amount,
							original_currency=:currency, sales_amount_USD=sales_amount_USD + $amount_usd, profit_cny =  profit_cny+ $profit_cny
							where use_module_type!='statistics' and thedate=:thedate and platform=:platform and seller_id=:seller_id";
		if(is_null($order_type))
			$initSellerIdSql.=" and order_type is null ";
		else 
			$initSellerIdSql.=" and order_type='$order_type' ";
		
		$command = Yii::$app->subdb->createCommand($initSellerIdSql ) ;
		$command->bindValue(':currency', $currency, \PDO::PARAM_STR);
		$command->bindValue(':thedate', $order_date, \PDO::PARAM_STR);
		$command->bindValue(':platform', $platform, \PDO::PARAM_STR);
		$command->bindValue(':seller_id', $seller_id, \PDO::PARAM_STR);
		
		//if($platform=='CDISCOUNT'){
		//	$sql = $command->getRawSql();//
		//	echo "<br>".$sql."<br>";//
		//}
		
		$affectRows = $command->execute();
		//var_dump($affectRows);//
		//每日凌晨，还没有当天数据的时候，update会失败，改用insert
		if ($affectRows == 0){
			$salesDaily_model = new SalesDaily();
			$salesDaily_model->sales_count = $orderCount;
			$salesDaily_model->sales_amount_original_currency = $amount;
			$salesDaily_model->original_currency = $currency;
			$salesDaily_model->sales_amount_USD = $amount_usd;
			$salesDaily_model->thedate = $order_date;
			$salesDaily_model->platform = $platform;
			$salesDaily_model->seller_id = strval($seller_id);
			$salesDaily_model->profit_cny = $profit_cny;
			$salesDaily_model->order_type = $order_type;
			$salesDaily_model->use_module_type = 'dash_board';
			
			try{
				$salesDaily_model->save();
			}catch(\Exception $e) { //duplicated key,so do not insert, use update again
				echo "<br>salesDaily_model save: Exception".$e->getMessage();
				$command = Yii::$app->subdb->createCommand($initSellerIdSql ) ;
				$command->bindValue(':currency', $currency, \PDO::PARAM_STR);
				$command->bindValue(':thedate', $order_date, \PDO::PARAM_STR);
				$command->bindValue(':platform', $platform, \PDO::PARAM_STR);
				$command->bindValue(':seller_id', $seller_id, \PDO::PARAM_STR);
				$affectRows = $command->execute();
				if ($affectRows == 0){
				}		
			}
		}
		
		//更新统计表数据
		if(empty($order_status) || !in_array($order_status, ['100', '600'])){
			$initSellerIdSql = "update `db_sales_daily` set sales_count=sales_count + $orderCount , sales_amount_original_currency =sales_amount_original_currency + $amount,
			original_currency=:currency, sales_amount_USD=sales_amount_USD + $amount_usd, profit_cny =  profit_cny+ $profit_cny
			where use_module_type='statistics' and thedate=:thedate and platform=:platform and seller_id=:seller_id";
			if(is_null($order_type))
				$initSellerIdSql.=" and order_type is null ";
			else
				$initSellerIdSql.=" and order_type='$order_type' ";
			
			$command = Yii::$app->subdb->createCommand($initSellerIdSql ) ;
			$command->bindValue(':currency', $currency, \PDO::PARAM_STR);
			$command->bindValue(':thedate', $order_date, \PDO::PARAM_STR);
			$command->bindValue(':platform', $platform, \PDO::PARAM_STR);
			$command->bindValue(':seller_id', $seller_id, \PDO::PARAM_STR);
		
			$affectRows = $command->execute();
			//var_dump($affectRows);//
			//每日凌晨，还没有当天数据的时候，update会失败，改用insert
			if ($affectRows == 0){
				$salesDaily_model_statistics = new SalesDaily();
				$salesDaily_model_statistics->sales_count = $orderCount;
				$salesDaily_model_statistics->sales_amount_original_currency = $amount;
				$salesDaily_model_statistics->original_currency = $currency;
				$salesDaily_model_statistics->sales_amount_USD = $amount_usd;
				$salesDaily_model_statistics->thedate = $order_date;
				$salesDaily_model_statistics->platform = $platform;
				$salesDaily_model_statistics->seller_id = strval($seller_id);
				$salesDaily_model_statistics->profit_cny = $profit_cny;
				$salesDaily_model_statistics->order_type = $order_type;
				$salesDaily_model_statistics->use_module_type = 'statistics';
					
				try{
					$salesDaily_model_statistics->save();
				}catch(\Exception $e) { //duplicated key,so do not insert, use update again
					echo "<br>salesDaily_model save: Exception".$e->getMessage();
					$command = Yii::$app->subdb->createCommand($initSellerIdSql ) ;
					$command->bindValue(':currency', $currency, \PDO::PARAM_STR);
					$command->bindValue(':thedate', $order_date, \PDO::PARAM_STR);
					$command->bindValue(':platform', $platform, \PDO::PARAM_STR);
					$command->bindValue(':seller_id', $seller_id, \PDO::PARAM_STR);
					$affectRows = $command->execute();
					if ($affectRows == 0){
					}
				}
			}
		}
		
		return true;
	}

	
	//下面是订单销售量 销售额的统计方法，order date 可以是时间戳，或者是 date time 的北京时间,设置这个初始值
	//如果原来已经有值，原来的会被覆盖掉
	public static function SalesStatisticSet($order_date,$platform,$order_type,
			$seller_id , $currency, $amount, $orderCount=0,$merge=false, $order_status=''){
		//如果是timestamp，时间戳的，转为本地北京时间保存
		if (is_numeric($order_date) ){
			$order_date = date('Y-m-d H:i:s',$order_date);
		}
		if (strlen($order_date)>10){
			$order_date = substr($order_date,0,10);
		}
	
		$platform = strtoupper($platform);
	
		//吧amount转成USD的值来保存
		if ($currency=='USD')
			$amount_usd = $amount;
		else
			$amount_usd = Helper_Currency::convertThisCurrencyToUSDFromDay($currency,$amount );

		//如果出现同一平台同一账号，多原始币种的情况，就要将多种原始币种强制转换长USD，合并成一条记录
		if($merge!==false){
			$currency = 'USD';
		}
		
		$order_date = self::getPlatformOrderType($platform, $order_type);
		
		//为了节省IO，不适用model find，而是直接走update，绝大部分case，直接走update 1次IO就可以了
		$initSellerIdSql = "update `db_sales_daily` set sales_count= $orderCount, sales_amount_original_currency =  $amount,
		original_currency=:currency, sales_amount_USD=  $amount_usd
		where thedate=:thedate and platform=:platform and seller_id=:seller_id";
		if(is_null($order_type))
			$initSellerIdSql.=" and order_type is null ";
		else
			$initSellerIdSql.=" and order_type='$order_type' ";
		
		//未付款、已取消的订单，不包含在统计模块->业绩汇总
		if(!empty($order_status) && in_array($order_status, ['100', '600'])){
			$initSellerIdSql .= " and use_module_type!='statistics' ";
		}
	
		$command = Yii::$app->subdb->createCommand($initSellerIdSql ) ;
		$command->bindValue(':currency', $currency, \PDO::PARAM_STR);
		$command->bindValue(':thedate', $order_date, \PDO::PARAM_STR);
		$command->bindValue(':platform', $platform, \PDO::PARAM_STR);
		$command->bindValue(':seller_id', $seller_id, \PDO::PARAM_STR);
		$affectRows = $command->execute();
	
		//每日凌晨，还没有当天数据的时候，update会失败，改用insert
		if ($affectRows == 0){
			$salesDaily_model = new SalesDaily();
			$salesDaily_model->sales_count = $orderCount;
			$salesDaily_model->sales_amount_original_currency = $amount;
			$salesDaily_model->original_currency = $currency;
			$salesDaily_model->sales_amount_USD = $amount_usd;
			$salesDaily_model->thedate = $order_date;
			$salesDaily_model->platform = $platform;
			$salesDaily_model->seller_id = strval($seller_id);
			$salesDaily_model->use_module_type = 'dash_board';
			$salesDaily_model->save();
		}
		//增加统计模块对应数据
		if(empty($order_status) || !in_array($order_status, ['100', '600'])){
			$salesDaily_model_statistics = new SalesDaily();
			$salesDaily_model->sales_count = $orderCount;
			$salesDaily_model->sales_amount_original_currency = $amount;
			$salesDaily_model->original_currency = $currency;
			$salesDaily_model->sales_amount_USD = $amount_usd;
			$salesDaily_model->thedate = $order_date;
			$salesDaily_model->platform = $platform;
			$salesDaily_model->seller_id = strval($seller_id);
			$salesDaily_model_statistics->use_module_type = 'statistics';
			$salesDaily_model_statistics->save();
		}
		
		return true;
	}
	
	public static function SalesStatisticDelete($order_date,$platform,
			$seller_id  ){
		//如果是timestamp，时间戳的，转为本地北京时间保存
		if (is_numeric($order_date) ){
			$order_date = date('Y-m-d H:i:s',$order_date);
		}
		if (strlen($order_date)>10){
			$order_date = substr($order_date,0,10);
		}
	
		$platform = strtoupper($platform);

			
		//为了节省IO，不适用model find，而是直接走update，绝大部分case，直接走update 1次IO就可以了
		$initSellerIdSql = "delete from `db_sales_daily`  
		where thedate=:thedate and platform=:platform and seller_id=:seller_id";
	
		$command = Yii::$app->subdb->createCommand($initSellerIdSql ) ;
	 
		$command->bindValue(':thedate', $order_date, \PDO::PARAM_STR);
		$command->bindValue(':platform', $platform, \PDO::PARAM_STR);
		$command->bindValue(':seller_id', $seller_id, \PDO::PARAM_STR);
		$affectRows = $command->execute();
	
		 
		return true;
		}
	
	/*
	 * 如果platform 传入是空白，则不区分渠道，
	 * 如果 seller id传入是空白，则不区分店铺
	 * 如果 currency 传入是空白，则返回 USD 的销售价格，数量
	 * 
	 * 参数 doPreFetchChildren ： 默认是true，如果是true的话，会吧当前读取级别的 下级级别也读取出来，放到缓存中，同一个进程再次读取 他的下级，会从cache读取，减少IO
	 * 
	 * 
		if   platform 为空 ，seller id 为空，则返回格式 所有 platform的统计结果,每一个platform对应返回的一个row
	 *  return  [ array('total_sales_amount_original_currency'=>4000,'total_sales_amount_USD'=>'4500','total_sales_count'=300,'currency'=>'EUR',platform=>'CDISCOUNT'),
	 *            array('total_sales_amount_original_currency'=>4000,'total_sales_amount_USD'=>'4500','total_sales_count'=300,'currency'=>'EUR',platform=>'PRICEMINISTER'),
	 *          ]
	 *          
	 *  if 传入了 platform 非空，seller id 非空，则返回这个店铺下的 累加值，例如
	 *  return array('total_sales_amount_original_currency'=>4000,'total_sales_amount_USD'=>'4500','total_sales_count'=300,'currency'=>'EUR',platform=>'PRICEMINISTER');
	 *  
	 *  
	 * */
	public static function SalesStatisticGet($order_date_from,$order_date_to, $doPreFetchChildren=true, $platform='',
			$seller_id='' , $order_type='all', $currency='USD' ){
		global $CACHE;
		$sql = "select sum(sales_count) total_sales_count, sum(sales_amount_original_currency) total_sales_amount_original_currency,
					sum(sales_amount_USD) total_sales_amount_USD, sum(profit_cny) total_profit_cny,original_currency currency,platform
					from db_sales_daily where thedate >= '$order_date_from' and  thedate <= '$order_date_to' and use_module_type!='statistics' ";
		
		if (!empty($platform))
			$sql .= " and platform =:platform ";
		
		if (!empty($seller_id)){
			if(is_string($seller_id))
				$sql .= " and seller_id =:seller_id ";
			else {
				$sql .= " and seller_id in ( ";
				$i=0;
				foreach ($seller_id as $sellerid){
					$sql .= ":sellerid".$i;
					$val_of_i[$i] = $sellerid;
					$i++;
					if($i<count($seller_id))
						$sql .= ",";
				}
				$sql .= " )";
			}
		}
		
		if($order_type!=='all' && !empty($order_type) && !empty($platform)){
			$sql .= " and order_type =:order_type ";
		}
		
		if (!empty($doPreFetchChildren) && empty($seller_id)){
			$sql .= " group by platform ";
		}
		
		//echo "<br>".$sql;
		$command = Yii::$app->subdb->createCommand( $sql );
		
		if (!empty($platform))
		$command->bindValue(':platform', $platform, \PDO::PARAM_STR);
		
		if (!empty($seller_id)){
			if(is_string($seller_id))
				$command->bindValue(':seller_id', $seller_id, \PDO::PARAM_STR);
			else{
				foreach ($val_of_i as $i=>$sellerid)
				$command->bindValue(':sellerid'.$i, $sellerid, \PDO::PARAM_STR);
			}
		}
		
		if($order_type!=='all' && !empty($order_type) && !empty($platform)){
			$order_type = self::getPlatformOrderType($platform, $order_type);
			$command->bindValue(':order_type', $order_type, \PDO::PARAM_STR);
		}
		//echo "<br>".$command->getRawSql();
		$rows = $command->queryAll();
		$totalCount = 0;
		$totalAmount = 0;
		return $rows;

	}
	
	
	private static function combineKeyForOMSStatus($platform='',$status=''){
		return "OMS_". strtoupper($platform)."_STATUS_". strtoupper($status);
	}

	
	public static function postDoUpdate($OMSStatusAdd=array() , $SalesStatisticAdd=array()){
		
	}	
	
}//end of class
?>