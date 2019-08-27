<?php

namespace eagle\modules\util\helpers;
use eagle\modules\util\helpers\GetControlData;
use eagle\modules\util\models\SysLog;
use eagle\modules\util\models\SysInvokeJrn;
use eagle\modules\util\models\GlobalLog;
/*+----------------------------------------------------------------------
| 小老板
+----------------------------------------------------------------------
| Copyright (c) 2011 http://www.xiaolaoban.cn All rights reserved.
+----------------------------------------------------------------------
| Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
+----------------------------------------------------------------------
| Author: yang zeng qiang
+----------------------------------------------------------------------
| Create Date: 2014-06-04
+----------------------------------------------------------------------
 */

/**
 +------------------------------------------------------------------------------
 * 基础信息模块
 +------------------------------------------------------------------------------
 * @category	util
 * @package		Helper
 * @subpackage  Exception
 * @author		XJQ
 * @version		1.0
 +------------------------------------------------------------------------------
 */
class SysBaseInfoHelper {
    
    public static $debugMode = false;
    public static $debugToken = NULL;
    public static $debugLogArr = array();
    
	private static $countryNameCodeMap=null;
	/**
	 +---------------------------------------------------------------------------------------------
	 * 获取所有的国家英文全名和缩写的对应关系。 如  United States 对应US
	 * 
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 +---------------------------------------------------------------------------------------------
	 * @return	
	 *   array("United States"=>"US", "Great Britan"=>"GB".....)
	 *
	 +---------------------------------------------------------------------------------------------
	 **/
	public static function getCountryNameCodeMap(){
		
		if (self::$countryNameCodeMap<>null) return  self::$countryNameCodeMap;
		
		self::$countryNameCodeMap=array();
		$rows=\yii::$app->db->createCommand("select * from sys_country")->queryAll();
		foreach($rows as $sysCountry){
			self::$countryNameCodeMap[$sysCountry["country_en"]]=$sysCountry["country_code"];			
		}
		
		return self::$countryNameCodeMap;
	}
	
	/**
	 * 获取帮助中心的URL链接
	 * 
	 * @param string $urlSuffix 非必填, 假如传入  word_list_188_108.html 则会返回： help.littleboss.com/word_list_188_108.html  不填则直接返回：help.littleboss.com
	 * @return string
	 */
	public static function getHelpdocumentUrl($urlSuffix = ''){
        $help_url = 'http://help.littleboss.com';
		
		
		if(!empty($urlSuffix)){
			return $help_url.'/'.$urlSuffix;
		}
		
		return $help_url;
	}
	
    /**
     * 获取当日的debug token，for 测试
     */
	public static function getDebugModeToken(){
	    if(empty(self::$debugToken))
	        self::$debugToken = md5("littboss".date("Ymd",time()));
	    
	    return self::$debugToken;
	}
	
	/**
	 * 添加测试内容到缓存数组
	 * 
	 * @param string 
	 */
	public static function addFrontDebugLog($log=""){
	    if(self::$debugToken)
	        self::$debugLogArr[] = $log;
	}
	
	/**
	 * 打印debug log
	 *
	 */
	public static function printFrontDebugLog(){
	    if(self::$debugToken && !empty(self::$debugLogArr))
	        echo "<br>".implode("<br>", self::$debugLogArr)."<br><br>";
	}
	
	
	
}