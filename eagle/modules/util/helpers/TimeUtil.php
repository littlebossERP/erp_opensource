<?php

namespace eagle\modules\util\helpers;

class TimeUtil {
	
	
	public static $timestampMarkArr=array();
	
	/**
	 * 重置一个计算
	 */
	public static function beginTimestampMark($markKey="global"){
		self::$timestampMarkArr[$markKey]=array();
	}
	/**
	 * 
	 * @param unknown $key
	 */
	public static function markTimestamp($timeKey,$markKey="global"){
		self::$timestampMarkArr[$markKey][$timeKey]=self::getCurrentTimestampMS();
	}
	
	/**
	 *
	 *    使用例子
	 *    
	 *   TimeUtil::beginTimestampMark("test");
         TimeUtil::markTimestamp("t1","test");
         sleep(1);
         TimeUtil::markTimestamp("t2","test");
         TimeUtil::markTimestamp("t3","test");
      
         echo TimeUtil::getTimestampMarkInfo("test");
                        输出  "t2_t1=1000,t3_t2=1,t3_t1=1001"
	 * 
	 */
	public static function getTimestampMarkInfo($markKey="global"){
		$logStr="";
		$i=0;
		if (!isset(self::$timestampMarkArr[$markKey])) return "";
		$timestampMarkArr=self::$timestampMarkArr[$markKey];
		$arrLen=count($timestampMarkArr);
		foreach($timestampMarkArr as $timeKey=>$timestamp){
			$i++;
			if ($i==1){
				$timeKeyFirst=$timeKey;
				$timestampFirst=$timestamp;
			}else if ($i==2){
				$logStr="{$timeKey}_{$timeKeyPrev}=".($timestamp-$timestampPrev);				
			}else if ($i==$arrLen){
				$logStr=$logStr.",{$timeKey}_{$timeKeyPrev}=".($timestamp-$timestampPrev);
				if ($timeKeyPrev<>$timeKeyFirst) $logStr=$logStr.",{$timeKey}_{$timeKeyFirst}=".($timestamp-$timestampFirst);
			}else{
				$logStr=$logStr.",{$timeKey}_{$timeKeyPrev}=".($timestamp-$timestampPrev);
			}
			
			$timeKeyPrev=$timeKey;
			$timestampPrev=$timestamp;
			
		}
		
		return $logStr;
		
	}
	
	
	
	
	
	
	/**********************************************************************************
	* input parm: n/a
	* function description: This is to return the current GMT+8 (China) timezone date only
    *                       e.g. "2014-01-25"
	***********************************************************************************/	
	public static function getNowDate(){
	$time1 = self::getNow();
	return substr($time1, 0,10);
	}	

	/**********************************************************************************
	 * input parm: n/a
	* function description: This is to return the current GMT+8 (China) timezone date and time
	*                       e.g. "2014-01-25 15:20:56"
	***********************************************************************************/	
	public static function getNow(){
		 $mktime = time();
		 $cfg_cli_time = 8;
		//if($mktime==""||ereg("[^0-9]",$mktime)) return "";
		return gmdate("Y-m-d H:i:s",$mktime + 3600 * $cfg_cli_time);
	}

	/**********************************************************************************
	 * input parm: n/a
	* function description: This is to return the current GMT+8 (China) timezone TimeStamp
	*                       e.g. "115465465464" for data base writing use
	***********************************************************************************/	
	public static function getNowTimestamp(){
		 $mktime = time();
		 $cfg_cli_time = 8;
		//if($mktime==""||ereg("[^0-9]",$mktime)) return 0;
		return ($mktime + 3600 * $cfg_cli_time);
	}
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * Convert normal format time to UTC format, which is required in Amazon API
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param
	 * 1:date_normal,  In local time zone is fine. e.g. "2014-03-17 08:56:32"
	 +---------------------------------------------------------------------------------------------
	 * @return		    Formated UTC time format string. e.g. "2014-03-20T03:49:35Z"
	 * @Description		This is to foramt the time into UTC time format.
	 * 					It can considerate the local time zone and adjust the timezone for UTC +0 					
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		yzq		2014/01/30				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	public static function getUTCTimeFormat($date_normal){
		$date1 = date_parse($date_normal);
		// Format date time in UTC format
		return gmdate("Y-m-d\TH:i:s\Z", mktime ($date1['hour'], $date1['minute'], $date1['second'], $date1['month'], $date1['day'], $date1['year']));
	}
	
	/**
	 * 获取当前毫秒级别的时间戳，这个时间戳主要是用作比较。
	 * 如： 想要获取某个函数运行的耗时，就可以在运行前和运行后各记录一次然后求差。 
	 */
	public static function getCurrentTimestampMS(){
		$current_time=explode(" ",microtime()); 
		return round($current_time[0]*1000+$current_time[1]*1000);
	}
	
	/**
	 * 把ISO8601格式的时间（"2015-08-26T22:18:52+0800"）转化为时间戳
	 *  
	 *  return--时间戳
	 */
	public static function getTimestampFromISO8601($myTimeStr){
		$serverLocalTime = new \DateTime($myTimeStr);
		return  $serverLocalTime->getTimestamp();
		
	}
	
	/**
	 * 把时间转化为时间戳指定语言的 年月日 时间格式
	 * @return	string	day-months-year
	 * @todo
	 * 开始只支持 英文，法文，德文 三种语言。日后出现新语言需求要不断更新
	 */
	public static function getDateStr($date_times,$lang)
	{
		$lang_arr = ['EN','FR','DE'];//支持的转换语言
		if(!in_array($lang,$lang_arr))//若为非支持余语言，则返回原始日期时间
			return $date_times;
		$dd = substr($date_times,0,10);
		$year = substr($dd,0,4);
		$month = substr($dd,5,2);
		$day = substr($dd,8,2);
		$months['EN']=array("January","February","March","April","May","June","July","August","September","October","November" ,"December");
		$months['FR']=array("Janvier", "Février", "Mars", "Avril", "Mai", "Juin", "Juillet", "Août", "Septembre", "Octobre", "Novembre" ,"Décembre");
		$months['DE']=array("January", "Februar", "March", "April", "May", "June", "Juli", "August", "September", "October", "November", "Dezember");
		$month -= 1;
	
		return $day." ".$months[$lang][$month]." ".$year;
	}
	
	
}

?>