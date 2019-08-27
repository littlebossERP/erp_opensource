<?php

namespace eagle\modules\util\helpers;

/*********************************************************************
    class.misc.php

    Misc collection of useful generic helper functions.

    Peter Rotich <peter@osticket.com>
    Copyright (c)  2006-2010 osTicket
    http://www.osticket.com

    Released under the GNU General Public License WITHOUT ANY WARRANTY.
    See LICENSE.TXT for details.

    vim: expandtab sw=4 ts=4 sts=4:
    $Id: $
**********************************************************************/
class UrlParamCryptHelper {
	
	public static function authCode($string, $operation = 'ENCODE'){
	  $key = "*!6O=-";
	  $key = md5($key ? $key : $key);
	  $key_length = strlen($key);
	  
	  $string = $operation == 'DECODE' ? base64_decode($string) : substr(md5($string.$key), 0, 8).$string;
	  $string_length = strlen($string);
	  
	  $rndkey = $box = array();
	  $result = '';
	  
	  for($i = 0; $i <= 255; $i++) 
	  {
	   $rndkey[$i] = ord($key[$i % $key_length]);
	   $box[$i] = $i;
	  }
	  
	  for($j = $i = 0; $i < 256; $i++) 
	  {
	   $j = ($j + $box[$i] + $rndkey[$i]) % 256;
	   $tmp = $box[$i];
	   $box[$i] = $box[$j];
	   $box[$j] = $tmp;
	  }
	  
	  for($a = $j = $i = 0; $i < $string_length; $i++) 
	  {
	   $a = ($a + 1) % 256;
	   $j = ($j + $box[$a]) % 256;
	   $tmp = $box[$a];
	   $box[$a] = $box[$j];
	   $box[$j] = $tmp;
	   $result .= chr(ord($string[$i]) ^ ($box[($box[$a] + $box[$j]) % 256]));
	  }
	  
	  if($operation == 'DECODE') 
	  {
	   if(substr($result, 0, 8) == substr(md5(substr($result, 8).$key), 0, 8))
		return substr($result, 8);
	   else
		return '';
	  }
	  else 
	   return str_replace('=', '', base64_encode($result));
	 }// end of authCode
   
}
?>
