<?php
 
function write_log($str,$type="error") {
	global $fdErr,$fd;
	date_default_timezone_set("Asia/Hong_Kong");
	if ($type=="error"){
		if (!isset($fdErr))
		$fdErr=fopen(fopen(dirname(__FILE__).DIRECTORY_SEPARATOR."error".date('Ymd').".log", "a");
		fputs($fdErr, date("Y-m-d H:i:s")." ".$str." \n");
		
	}else {
		if (!isset($fd))
		$fd=fopen(fopen(dirname(__FILE__).DIRECTORY_SEPARATOR."info".date('Ymd').".log", "a");
		fputs($fd, date("Y-m-d H:i:s")." ".$str." \n");
	
	}

}

?>