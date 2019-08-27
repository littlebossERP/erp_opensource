<?php

namespace eagle\modules\util\helpers;
use eagle\modules\util\models;
use eagle\modules\util\models\ConfigData;
use eagle\modules\util\helpers\ConfigHelper;
use Qiniu\json_decode;
/**
 +------------------------------------------------------------------------------
 * 管理的static数值
 +------------------------------------------------------------------------------
 * @package		Helper
 * @subpackage  Exception
 * @author		xjq
 * @version		1.0
 +------------------------------------------------------------------------------
 */
class DataStaticHelper {
	
	private static $backgroundJobId=-1;

	/**
	 +---------------------------------------------------------------------------------------------
	 * 获取后台job的id。并非一个唯一值！！！！ 
	 * 目前只为 文件log 服务
	 *
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		xjq		2015/02/15				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	public  static function getCurrentBGJobId(){
		 if (self::$backgroundJobId==-1){
		 	self::$backgroundJobId=9900000+rand(1,100000-1);
		 }	
		 return self::$backgroundJobId;
		
	}
	/**
	 +---------------------------------------------------------------------------------------------
	 * 加密函数
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		yzq		2015/07/11			初始化
	 +---------------------------------------------------------------------------------------------
	 **/	
	public static function encodeBySwap($input){
		$base32 = array ( 'a', 'b', 'c', 'd', 'e', 'f', 'g', 'h',
			'i', 'j', 'k', 'l', 'm', 'n', 'o', 'p',
			'q', 'r', 's', 't', 'u', 'v', 'w', 'x',
			'y', 'z', '0', '1', '2', '3', '4', '5' ); 
		$hex = md5($input); $hexLen = strlen($hex); $subHexLen = $hexLen / 8; $output = array(); for ($i = 0; $i < $subHexLen; $i++) { $subHex = substr ($hex, $i * 8, 8); $int = 0x3FFFFFFF & (1 * ('0x'.$subHex)); $out = ''; for ($j = 0; $j < 6; $j++) { $val = 0x0000001F & $int; $out .= $base32[$val]; $int = $int >> 5;
		} $output[] = $out;
		} 
		return $output;
	}
	/**
	 +---------------------------------------------------------------------------------------------
	 * 解密函数
	 *
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		yzq		2015/07/11			初始化
	 +---------------------------------------------------------------------------------------------
	 **/ 
	public static function decodeBySwap($txt,$key='littleboss'){
		$txt = base64_decode($txt);
		$chars = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789-=+";
		$chars = "ABC";
		$ch = $txt[0];
		$nh = strpos($chars,$ch);
		$mdKey = md5($key.$ch);
		$mdKey = substr($mdKey,$nh%8, $nh%8+7);
		$txt = substr($txt,1);
		$tmp = '';
		$i=0;$j=0; $k = 0;
		for ($i=0; $i<strlen($txt); $i++) {
			$k = $k == strlen($mdKey) ? 0 : $k;
			$j = strpos($chars,$txt[$i])-$nh - ord($mdKey[$k++]);
			while ($j<0) $j+=64;
			$tmp .= $chars[$j];
		}
		return trim(base64_decode($tmp),$key);
	}
	
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 统计某个应用的 推荐值 Top 10 的使用次数
	 * 使用方法：addUseCountFor("AmazonOms标记发货物流选项", "USPS" )
	 * 效果，系统会记录这个 path 也就是 "AmazonOms标记发货物流选项" 这个用处里面，值 "USPS" 的使用频率加1，然后下一次可以通过这个功能Load出来，得到频率排序
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		yzq		2016/08/1			初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	public static function addUseCountFor($path, $value, $totalTopX=10){
		
		$originalStr = ConfigHelper::getConfig($path);
		
		if ( empty($originalStr) )
			$originalArray = [];
		else
			$originalArray = json_decode($originalStr, true);
		
		if (!isset($originalArray[(String) $value ]))
			$originalArray[(String) $value ] = 0;
		
		//为什么加2，不是加1？这个为了让后来使用的 占据更加大的比重，这个加的数字越大，后来使用的占比重就越大
		$originalArray[(String) $value ] += 2; 
		
		if ($originalArray[(String) $value] > 1000 )
			$tooBig = true;
		else
			$tooBig = false;
		
		
		//如果 too big 了，所有值都需要除以2，使得这个值不会无限膨胀
		
		if ($tooBig){
			foreach ($originalArray as $key=>$count1){
				$count1 = $count1 / 2;
				$originalArray[$key] = $count1;
			}
		}
		//如果这个的值计数器里面的值已经超过Top X 的X 要求，删除最少次数的哪个
		if (count($originalArray) > $totalTopX ){
			$minIndex='';
			$minCount = -1;
			foreach ($originalArray as $key=>$count1){
				
				if ($minCount < 0 or $minCount > $count1){
					$minCount = $count1;
					$minIndex = $key;
				}
			}
			if ($minIndex <> '')
				unset($originalArray[ $minIndex ]);
		}
		
		arsort($originalArray);
		ConfigHelper::setConfig($path, json_encode($originalArray));	
	}

	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 获得某个应用的 推荐值 Top 10 的值
	 * 使用方法：getUseCountTopValuesFor("AmazonOms标记发货物流选项" ,$optionValues = array('DHL'=>$x,'USPS'=>$y, 'Fedex'=>'$z') )
	 * 效果，系统会记录这个 path 也就是 "AmazonOms标记发货物流选项" 这个用处里面，
		把记录在案的使用频率调出来，和optionValues 进行对比, 找到记录频率和 optionValues 重合的部分，作为recommended 返回，并且按照使用频率倒序。
		吧剩下没有在使用频率记录中的，作为 rest array中返回，
		调用者只需要在view层按照 返回的顺序显示即可
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		yzq		2016/08/1			初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	public static function getUseCountTopValuesFor($path, $optionValues=array() ){
		$rtn = [];
		$originalStr = ConfigHelper::getConfig($path);
	
		if ( empty($originalStr) )
			$useCountArray = [];
		else
			$useCountArray = json_decode($originalStr, true);

		arsort($useCountArray);
		$rtn['recommended'] = [];
		$rtn['rest'] = $optionValues;
		foreach ($useCountArray as $key => $count1){
			if (isset($optionValues[$key])){
				$rtn['recommended'][$key] = $optionValues[$key];
				unset($rtn['rest'][$key]);
			}
				
		}//end of each counted value
		
		return $rtn;
	}

}