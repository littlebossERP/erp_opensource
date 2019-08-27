<?php

namespace eagle\modules\util\helpers;
use eagle\modules\util\models;
use eagle\modules\util\models\ConfigData;
use eagle\modules\util\models\GlobalConfigData;

use eagle\modules\util\helpers\RedisHelper;

/**
 +------------------------------------------------------------------------------
 * config模块---- 读取各个模块配置的参数数值
 +------------------------------------------------------------------------------
 * @package		Helper
 * @subpackage  Exception
 * @author		xjq
 * @version		1.0
 +------------------------------------------------------------------------------
 */
class ConfigHelper {
	
	private static $_configData=array();
	private static $_configGlobalData=array();

	/**
	 +---------------------------------------------------------------------------------------------
	 * 从config数据表中获取所有数据到_configData
	 *
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		xjq		2015/02/15				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	private static function _loadAllConfig(){
		$configDataArr =ConfigData::find()->asArray()->all();
		foreach($configDataArr as $configData){
			self::$_configData[$configData["path"]]=$configData["value"];
		}
	}
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 从ut_global_config_data数据表中获取所有数据到_configGlobalData
	 *
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		xjq		2015/02/15				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	private static function _loadAllGlobalConfig(){
	//	$configDataArr =GlobalConfigData::find()->asArray()->all();
	//	foreach($configDataArr as $configData){
	//		self::$_configGlobalData[$configData["path"]]=$configData["value"];
//		}
		
		$pathValuesArr=RedisHelper::RedisGetAll('global_config');
		if($pathValuesArr===null) return;
		if (count($pathValuesArr)%2===1) return;		
		$configNum=count($pathValuesArr)/2;
		for($i=0;$i<$configNum;$i++){
		    self::$_configGlobalData[$pathValuesArr[$i*2]]=$pathValuesArr[$i*2+1];
		    //$configMap[$pathValuesArr[$i*2]]=$pathValuesArr[$i*2+1];
		}

	}	
	
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 从config数据表中获取 path对应的value
	 *
	 +---------------------------------------------------------------------------------------------
	 * @param $path   -------- 配置的路径。 如：  purchase/maxprice	
	 * 这里path的命名规则是 moduleName/key
	 * 
	 * @param $type   -------- CACHE 或者 NO_CACHE.   读取config这个函数是否从cache中读取。
	 * 
	 +---------------------------------------------------------------------------------------------
	 * @return	----返回null表示找不到$path对应的value；否则返回 对应的value 				
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		xjq		2015/02/15				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	public static function getConfig($path,$type="CACHE"){
		//不使用cache，直接读取数据库
		if ($type=="NO_CACHE") {
			$configData = ConfigData::find()->where(['path'=>$path])->one();
			if ($configData===null) return null;
			return $configData->value;
		}
		
		//使用cache
		if (count(self::$_configData)==0) {
			self::_loadAllConfig();
		}
		if (isset(self::$_configData[$path])) return self::$_configData[$path];
		
		return null;
	}
	
	
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 从global config数据表中获取 path对应的value
	 *
	 +---------------------------------------------------------------------------------------------
	 * @param $path   -------- 配置的路径。 如：  purchase/maxprice
	 * 这里path的命名规则是 moduleName/key
	 *
	 * @param $type   -------- CACHE 或者 NO_CACHE.   读取config这个函数是否从cache中读取。
	 *
	 +---------------------------------------------------------------------------------------------
	 * @return	----返回null表示找不到$path对应的value；否则返回 对应的value
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		xjq		2015/02/15				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	public static function getGlobalConfig($path,$type="CACHE"){
		//不使用cache，直接读取数据库
		if ($type=="NO_CACHE") {
		    
		    $value=RedisHelper::RedisGet('global_config',$path);
		    if ($value===null) return null;
		    
		    return $value;
		    
//			$configData = GlobalConfigData::find()->where(['path'=>$path])->one();
//			if ($configData===null) return null;
//			return $configData->value;
			
		}
	
		//使用cache
		if (count(self::$_configGlobalData)==0) {
			self::_loadAllGlobalConfig();
		}
		if (isset(self::$_configGlobalData[$path])) return self::$_configGlobalData[$path];
	
		return null;
	}	
	
	
	
	
	
	
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 添加1个path和value的配置对
	 +---------------------------------------------------------------------------------------------
	 * @param $path   -------- 配置的路径。 如：  purchase/maxprice
	 * 这里path的命名规则是 moduleName/key
	 * @param $value  -------- path对应的value。 
	 +---------------------------------------------------------------------------------------------
	 * @return	----false or true;   false表示设置失败
	 *  
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		xjq		2015/02/15				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	
	public static function setConfig($path,$value){
		$configData = ConfigData::find()->where(['path'=>$path])->one();
		if ($configData===null){			
			$configData=new ConfigData;
			$configData->path=$path;
			$configData->value=$value;
			if (!$configData->save(false)) return false;			
		}else{
			if ($configData->value!=$value){
				$configData->value=$value;
				if (!$configData->save(false)) return false;
			}
		}
		self::$_configData[$path]=$value;
		
		return true;		
	}
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * global config ----------添加1个path和value的配置对 
	 +---------------------------------------------------------------------------------------------
	 * @param $path   -------- 配置的路径。 如：  purchase/maxprice
	 * 这里path的命名规则是 moduleName/key
	 * @param $value  -------- path对应的value。
	 +---------------------------------------------------------------------------------------------
	 * @return	----false or true;   false表示设置失败
	 *
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		xjq		2015/02/15				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	
	public static function setGlobalConfig($path,$value){
	/*	$configData = GlobalConfigData::find()->where(['path'=>$path])->one();
		if ($configData===null){
			$configData=new GlobalConfigData;
			$configData->path=$path;
			$configData->value=$value;
			if (!$configData->save(false)) return false;
		}else{
			if ($configData->value!=$value){
				$configData->value=$value;
				if (!$configData->save(false)) return false;
			}
		}*/
	    
	    
	    RedisHelper::RedisSet('global_config',$path,$value);
		self::$_configGlobalData[$path]=$value;
	
		return true;
	}	
	

	/**
	 +---------------------------------------------------------------------------------------------
	 * 添加1个页面的用户上次展示条数设置
	 * @param $page_url		页面地址。 如：'/order/order/index'
	 * @param $value		page_url对应的page size。
	 * @return				false or true;   false表示设置失败
	 * @author		lzhl	2016/11/30		初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	public static function setPageLastOpenedSize($page_url,$value){
		if((int)$value > 100) $value=100;//限制最大为100,避免初次打开页面加载过多条数导致超时
		$rtn = self::setConfig($page_url, $value);
		return $rtn;
	}
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 读取1个页面的用户上次展示条数设置
	 * @param $page_url		页面地址。 如：'/order/order/index'
	 * @return		int 	0表示无设置,或就是设置了0
	 * @author		lzhl	2016/11/30		初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	public static function getPageLastOpenedSize($page_url){
		$page_size = self::getConfig($page_url,"NO_CACHE");
		if(empty($page_size))
			return 0;
		$page_size = (int)$page_size;
		if($page_size > 100) $page_size=100;//限制最大为100,避免初次打开页面加载过多条数导致超时
		return $page_size;
	}
}