<?php
class Utility{
	static function getUserTokenIDFromXMLString($xmlString){
		$xml = simplexml_load_string($xmlString);
		$tmp = (array)$xml;
		$tokenID = $tmp[0];
		return $tokenID ;
	}//end of getUserTokenID
	
	static function xml_to_array($xml)
	{
		if (trim($xml) == "") return array();
	$xmlstr = $xml;
	$xmlstr = preg_replace('/\sxmlns="(.*?)"/', ' _xmlns="${1}"', $xmlstr);
			$xmlstr = preg_replace('/<(\/)?(\w+):(\w+)/', '<${1}${2}_${3}', $xmlstr);
			$xmlstr = preg_replace('/(\w+):(\w+)="(.*?)"/', '${1}_${2}="${3}"', $xmlstr);
		$array = (array)(simplexml_load_string($xmlstr));
		foreach ($array as $key=>$item){
			$array[$key] = self::struct_to_array((array)$item);
		}
		return $array;
	}//end of xml_to_array

	static function struct_to_array($item) 
	{
		if(!is_string($item)) 
		{
			$item = (array)$item;
			foreach ($item as $key=>$val){
				$item[$key] = self::struct_to_array($val);
			}
		}
		return $item;
	}//end of struct_to_array
	
}//end of class Utility
