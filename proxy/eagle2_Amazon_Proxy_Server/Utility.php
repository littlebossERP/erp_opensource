<?php
 
function write_log($str,$type="error") {
	global $fdErr,$fd;
	date_default_timezone_set("Asia/Hong_Kong");
	if ($type == "error"){
		if (!isset($fdErr)){
			$fdErr = fopen(dirname(__FILE__).DIRECTORY_SEPARATOR."error_log".DIRECTORY_SEPARATOR."error".date('Ymd').".log", "a");
		}
		fputs($fdErr, date("Y-m-d H:i:s")." ".$str." \n");
	}else {
		if (!isset($fd)){
			$fd = fopen(dirname(__FILE__).DIRECTORY_SEPARATOR."running_log".DIRECTORY_SEPARATOR."info".date('Ymd').".log", "a");
		}
		fputs($fd, date("Y-m-d H:i:s")." ".$str." \n");
	}
}

function parseNamespaceXml($xmlstr)
{
	$xmlstr = preg_replace('/\sxmlns="(.*?)"/', ' _xmlns="${1}"', $xmlstr);
	$xmlstr = preg_replace('/<(\/)?(\w+):(\w+)/', '<${1}${2}_${3}', $xmlstr);
	$xmlstr = preg_replace('/(\w+):(\w+)="(.*?)"/', '${1}_${2}="${3}"', $xmlstr);
	$xmlobj = simplexml_load_string($xmlstr);
	return json_decode(json_encode($xmlobj), true);
}//end of parseNamespaceXml

?>