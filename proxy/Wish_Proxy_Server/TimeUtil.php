<?php

class TimeUtil {
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
	
}

?>