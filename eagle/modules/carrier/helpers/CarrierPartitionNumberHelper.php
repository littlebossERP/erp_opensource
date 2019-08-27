<?php
namespace eagle\modules\carrier\helpers;


use eagle\modules\carrier\models\SysCarrierAccount;
use Qiniu\json_decode;
Class CarrierPartitionNumberHelper{
	/**
	 * 获取分拣号
	 * @param $carrier_code
	 * @param $method_code
	 * @param $country_code
	 * @param $postal_code
	 * @return string
	 */
	public static function getPartitionNumber($carrier_code, $method_code, $country_code, $postal_code){
		$partition_number = '';
		
		switch ($carrier_code){
			case 'lb_4px':
				$partition_number = self::getPartitionNumber4px($method_code, $country_code, $postal_code);
				break;
			case 'lb_IEUB':
			case 'lb_IEUBNew':
				$partition_number = self::getPartitionNumberEub($method_code, $country_code, $postal_code);
				break;
			default:$partition_number = '';
			break;
		}
		
		return $partition_number;
	}
	
	/**
	 * 获取扩展字段的方法
	 * 
	 * @param $carrier_account_id	物流账户ID
	 * @return $result
	 */
	public static function getOrderPlusField($carrier_account_id){
		$result = array();
		
		$carrierAccount = SysCarrierAccount::find()->select(['carrier_code','api_params'])->where(['id'=>$carrier_account_id])->asArray()->one();
		
		if(count($carrierAccount) == 0){
			return $result;
		}
		
		if($carrierAccount['carrier_code'] == 'lb_4px'){
			$carrierAccount['api_params'] = unserialize($carrierAccount['api_params']);
			
			$result = $carrierAccount['api_params'];
		}
		return $result;
	}
	
	/**
	 * 获取Eub分拣号
	 * @param $method_code
	 * @param $country_code
	 * @param $postal_code
	 * @return string
	 */
	private static function getPartitionNumberEub($method_code, $country_code, $postal_code){
		$partition_number = '';
	
		if(($method_code == '0') && (in_array($country_code, array('US'))) && (!empty($postal_code))){

			$tmp_postal = substr($postal_code,0,3);
			
			if(!is_numeric($tmp_postal)){
				return $partition_number;
			}
			
			$tmp_postal = (int)$tmp_postal;
			
			if(($tmp_postal>=0 && $tmp_postal<=69) || ($tmp_postal>=74 && $tmp_postal<=78) || ($tmp_postal>=80 && $tmp_postal<=87) || ($tmp_postal>=90 && $tmp_postal<=99)
				|| ($tmp_postal>=105 && $tmp_postal<=109) || ($tmp_postal == 115) || ($tmp_postal>=117 && $tmp_postal<=299)){
				$partition_number = '1F';
			}else 
			if(($tmp_postal == 103) || ($tmp_postal>=110 && $tmp_postal<=114) || ($tmp_postal == 116)){
				$partition_number = '1P';
			}else
			if(($tmp_postal>=70 && $tmp_postal<=73) || ($tmp_postal == 79) || ($tmp_postal>=88 && $tmp_postal<=89)){
				$partition_number = '1Q';
			}else
			if(($tmp_postal>=100 && $tmp_postal<=102) || ($tmp_postal == 104)){
				$partition_number = '1R';
			}else
			if(($tmp_postal>=400 && $tmp_postal<=433) || ($tmp_postal>=437 && $tmp_postal<=439) || ($tmp_postal>=450 && $tmp_postal<=459) || ($tmp_postal>=470 && $tmp_postal<=471) 
				|| ($tmp_postal>=475 && $tmp_postal<=477) || ($tmp_postal == 480) || ($tmp_postal>=483 && $tmp_postal<=485) || ($tmp_postal>=490 && $tmp_postal<=491) 
				|| ($tmp_postal>=493 && $tmp_postal<=497) || ($tmp_postal>=500 && $tmp_postal<=529) || ($tmp_postal == 533) || ($tmp_postal == 536) || ($tmp_postal == 540) 
				|| ($tmp_postal>=546 && $tmp_postal<=548) || ($tmp_postal>=550 && $tmp_postal<=609) || ($tmp_postal == 612) || ($tmp_postal>=617 && $tmp_postal<=619) 
				|| ($tmp_postal == 621) || ($tmp_postal == 624) || ($tmp_postal == 632) || ($tmp_postal == 635) || ($tmp_postal>=640 && $tmp_postal<=699)
				|| ($tmp_postal>=740 && $tmp_postal<=758) || ($tmp_postal>=760 && $tmp_postal<=772) || ($tmp_postal>=785 && $tmp_postal<=787) || ($tmp_postal>=789 && $tmp_postal<=799)){
				$partition_number = '3F';
			}else
			if(($tmp_postal>=460 && $tmp_postal<=469) || ($tmp_postal>=472 && $tmp_postal<=474) || ($tmp_postal>=478 && $tmp_postal<=479)){
				$partition_number = '3P';
			}else
			if(($tmp_postal>=498 && $tmp_postal<=499) || ($tmp_postal>=530 && $tmp_postal<=532) || ($tmp_postal>=534 && $tmp_postal<=535) || ($tmp_postal>=537 && $tmp_postal<=539) 
				|| ($tmp_postal>=541 && $tmp_postal<=545) || ($tmp_postal == 549) || ($tmp_postal>=610 && $tmp_postal<=611)){
				$partition_number = '3Q';
			}else
			if(($tmp_postal == 759) || ($tmp_postal>=773 && $tmp_postal<=778)){
				$partition_number = '3R';
			}else
			if(($tmp_postal>=613 && $tmp_postal<=616) || ($tmp_postal == 620) || ($tmp_postal>=622 && $tmp_postal<=623) || ($tmp_postal>=625 && $tmp_postal<=631) 
				|| ($tmp_postal>=633 && $tmp_postal<=634) || ($tmp_postal>=636 && $tmp_postal<=639)){
				$partition_number = '3U';
			}else
			if(($tmp_postal>=434 && $tmp_postal<=436) || ($tmp_postal>=481 && $tmp_postal<=482) || ($tmp_postal>=486 && $tmp_postal<=489) || ($tmp_postal == 492)){
				$partition_number = '3C';
			}else
			if(($tmp_postal>=779 && $tmp_postal<=784) || ($tmp_postal == 788)){
				$partition_number = '3D';
			}else
			if(($tmp_postal>=440 && $tmp_postal<=449)){
				$partition_number = '3H';
			}else
			if(($tmp_postal>=813 && $tmp_postal<=849) || ($tmp_postal == 854) || ($tmp_postal>=856 && $tmp_postal<=858) || ($tmp_postal>=861 && $tmp_postal<=862)
				 || ($tmp_postal>=864 && $tmp_postal<=899) || ($tmp_postal == 906) || ($tmp_postal>=909 && $tmp_postal<=918) || ($tmp_postal>=926 && $tmp_postal<=939)){
				$partition_number = '4F';
			}if(($tmp_postal>=900 && $tmp_postal<=905) || ($tmp_postal>=907 && $tmp_postal<=908)){
				$partition_number = '4P';
			}else
			if(($tmp_postal>=850 && $tmp_postal<=853) || ($tmp_postal == 855) || ($tmp_postal>=859 && $tmp_postal<=860) || ($tmp_postal == 863)){
				$partition_number = '4Q';
			}else
			if(($tmp_postal>=919 && $tmp_postal<=921)){
				$partition_number = '4R';
			}else
			if(($tmp_postal>=922 && $tmp_postal<=925)){
				$partition_number = '4U';
			}else
			if(($tmp_postal == 942) || ($tmp_postal>=950 && $tmp_postal<=953) || ($tmp_postal>=956 && $tmp_postal<=979) || ($tmp_postal>=986 && $tmp_postal<=999)){
				$partition_number = '2F';
			}else
			if(($tmp_postal>=980 && $tmp_postal<=985)){
				$partition_number = '2P';
			}else
			if(($tmp_postal>=800 && $tmp_postal<=812)){
				$partition_number = '2Q';
			}else
			if(($tmp_postal>=945 && $tmp_postal<=948)){
				$partition_number = '2R';
			}else
			if(($tmp_postal>=940 && $tmp_postal<=941) || ($tmp_postal>=943 && $tmp_postal<=944) || ($tmp_postal == 949) || ($tmp_postal>=954 && $tmp_postal<=955)){
				$partition_number = '2U';
			}else
			if(($tmp_postal>=300 && $tmp_postal<=320) || ($tmp_postal>=322 && $tmp_postal<=326) || ($tmp_postal>=334 && $tmp_postal<=339)
				|| ($tmp_postal>=341 && $tmp_postal<=346) || ($tmp_postal>=348 && $tmp_postal<=399) || ($tmp_postal>=700 && $tmp_postal<=739)){
				$partition_number = '5F';
			}else
			if(($tmp_postal>=330 && $tmp_postal<=333) || ($tmp_postal == 340)){
				$partition_number = '5P';
			}else
			if(($tmp_postal == 321) || ($tmp_postal>=327 && $tmp_postal<=329) || ($tmp_postal == 347)){
				$partition_number = '5Q';
			}
		}
	
		return $partition_number;
	}
	
	/**
	 * 获取4px分拣号
	 * @param $method_code
	 * @param $country_code
	 * @param $postal_code
	 * @return string
	 */
	private static function getPartitionNumber4px($method_code, $country_code, $postal_code){
		$partition_number = '';
		
		if(($method_code == 'A6') && (in_array($country_code, array('GB','UK'))) && (!empty($postal_code))){
			$partitionNumberArr = self::$partitionNumber4pxLytGhArr;
			krsort($partitionNumberArr);
			
			foreach($partitionNumberArr as $key => $val){
				if(strpos(strtoupper($postal_code),$key) === 0){
					$partition_number = $val;
					break;
				}
			}
		}
		
		return $partition_number;
	}
	
	/**
	 * 深圳邮政获取分区号
	 * @param $country_code
	 */
	public static function getPartitionNumberShenzhen($country_code){
		$partition_number = '0';
		foreach(self::$PartitionNumberShenzhen as $key => $val){
			if($country_code==$key){
				$partition_number = $val;
				break;
			}
		}
		return $partition_number;
	}
	
	/**
	 * 国际挂号小包分区
	 * @param $country_code
	 */
	public static function getInternationalRegisteredParcelPartition($country_code){
		//这里的国家因为我们的sys_country不全，所以这里只是部分
		$country_json = '{"JP":"1","KR":"2","MY":"2","TH":"2","SG":"2","IN":"2","ID":"2","AT":"3","AU":"3","IE":"3","BG":"3","PL":"3","BE":"3","DE":"3","DK":"3","FI":"3","NL":"3","CZ":"3","HR":"3","NO":"3","PT":"3","SE":"3","CH":"3","SK":"3","GR":"3","HU":"3","IT":"3","IL":"3","TR":"4","NZ":"4","OM":"5","AZ":"5","EE":"5","BY":"5","PK":"5","KP":"5","FR":"5","PH":"5","KZ":"5","CA":"5","QA":"5","RO":"5","LU":"5","LT":"5","LV":"5","MT":"5","MN":"5","US":"5","LK":"5","SI":"5","CY":"5","SA":"5","TJ":"5","TM":"5","UA":"5","UZ":"5","ES":"5","SY":"5","GB":"5","AM":"5","VN":"5","ZA":"6","AR":"7","BR":"7","MX":"7","AF":"8","BT":"8","BH":"8","KH":"8","KW":"8","LB":"8","LA":"8","MM":"8","MV":"8","BD":"8","PE":"8","NP":"8","BN":"8","JO":"8","IR":"8","IQ":"8","CL":"8","AL":"9","IS":"9","GE":"9","ME":"9","LI":"9","MD":"9","MC":"9","MK":"9","RS":"9","SM":"9","GI":"9","DZ":"10","AO":"10","EG":"10","AW":"10","ET":"10","AG":"10","BB":"10","PG":"10","BW":"10","PR":"10","BS":"10","BF":"10","BI":"10","PY":"10","BO":"10","BZ":"10","BJ":"10","PA":"10","GQ":"10","TG":"10","EC":"10","ER":"10","FJ":"10","GF":"10","CU":"10","GM":"10","GU":"10","CO":"10","GD":"10","CR":"10","GY":"10","HT":"10","HN":"10","AN":"10","ZW":"10","DJ":"10","KI":"10","GH":"10","GN":"10","GW":"10","GA":"10","CK":"10","KM":"10","CM":"10","KY":"10","KE":"10","LR":"10","LY":"10","RE":"10","LS":"10","RW":"10","MG":"10","FM":"10","ML":"10","MA":"10","MU":"10","MR":"10","MW":"10","MZ":"10","MH":"10","MQ":"10","NU":"10","NI":"10","NR":"10","NA":"10","NE":"10","NG":"10","PW":"10","SD":"10","SV":"10","SH":"10","SL":"10","SB":"10","SR":"10","LC":"10","SO":"10","AS":"10","SN":"10","PM":"10","SC":"10","SZ":"10","TO":"10","TC":"10","TT":"10","TN":"10","TZ":"10","TV":"10","GT":"10","UG":"10","UY":"10","VU":"10","VE":"10","NC":"10","EH":"10","WS":"10","JM":"10","VG":"10","ZM":"10","TD":"10","RU":"11"}';
		$countryArr = json_decode($country_json, true);
		
		if(isset($countryArr[$country_code])){
			return $countryArr[$country_code];
		}else{
			return '';
		}
	}
	
	/**
	 * 国际平常小包分区
	 * @param $country_code
	 */
	public static function getInternationalCommonPacketPartition($country_code){
		//这里的国家因为我们的sys_country不全，所以这里只是部分
		$country_json = '{"JP":"1","AT":"2","BG":"2","KR":"2","HR":"8","MY":"2","SK":"2","TH":"2","SG":"2","HU":"8","IN":"2","ID":"2","AU":"8","IE":"3","PL":"3","BE":"3","DE":"8","DK":"3","FI":"3","NL":"8","CZ":"3","NO":"8","PT":"3","SE":"8","CH":"3","GR":"3","IT":"3","GB":"8","IL":"8","OM":"4","AZ":"4","EE":"4","BY":"4","PK":"4","KP":"4","RU":"7","FR":"4","PH":"4","KZ":"4","CA":"4","QA":"4","RO":"4","LU":"4","LT":"4","LV":"4","MT":"4","MN":"4","US":"4","LK":"4","SI":"4","CY":"4","SA":"4","TR":"4","TJ":"4","TM":"4","UA":"4","UZ":"4","ES":"4","SY":"4","NZ":"4","AM":"4","VN":"4","AL":"5","DZ":"5","AF":"5","AO":"5","AR":"5","EG":"5","ET":"5","PG":"5","BW":"5","BT":"5","IS":"5","BF":"5","BH":"5","BI":"5","BJ":"5","PA":"5","BR":"5","GQ":"5","TG":"5","EC":"5","ER":"5","FJ":"5","CU":"5","GM":"5","GU":"5","CO":"5","GE":"5","ME":"5","ZW":"5","DJ":"5","KI":"5","GH":"5","GN":"5","GW":"5","GA":"5","KH":"5","CK":"5","KM":"5","CM":"5","KE":"5","KW":"5","LR":"5","LB":"5","LY":"5","LS":"5","LA":"5","RW":"5","LI":"5","MM":"5","MG":"5","MV":"5","MD":"5","BD":"5","FM":"5","PE":"5","ML":"5","MA":"5","MU":"5","MR":"5","MW":"5","MC":"5","MK":"5","MZ":"5","MH":"5","MX":"5","NU":"5","NP":"5","ZA":"5","NR":"5","NA":"5","NE":"5","NG":"5","PW":"5","SD":"5","RS":"5","SH":"5","KN":"5","SL":"5","SB":"5","SR":"5","SO":"5","SM":"5","AS":"5","SN":"5","SC":"5","SZ":"5","TO":"5","TN":"5","TZ":"5","TV":"5","UG":"5","BN":"5","VU":"5","VE":"5","NC":"5","EH":"5","JO":"5","IR":"5","IQ":"5","YE":"5","GI":"5","ZM":"5","TD":"5","AW":"6","AG":"6","BB":"6","PR":"6","BS":"6","PY":"6","BO":"6","BZ":"6","GF":"6","GL":"6","GD":"6","CR":"6","GY":"6","HT":"6","HN":"6","AN":"6","KY":"6","MQ":"6","NI":"6","SV":"6","LC":"6","PM":"6","TC":"6","TT":"6","GT":"6","UY":"6","JM":"6","VG":"6","CL":"6"}';
		$countryArr = json_decode($country_json, true);
	
		if(isset($countryArr[$country_code])){
			return $countryArr[$country_code];
		}else{
			return '';
		}
	}
	
	/**
	 * 4px联邮通挂号分区码邮编对应关系数组定义
	 */
	private static $partitionNumber4pxLytGhArr = array(
			'W14'=>'0',
			'W13'=>'0',
			'W12'=>'0',
			'W11'=>'0',
			'W10'=>'0',
			'W9'=>'0',
			'W8'=>'0',
			'W7'=>'0',
			'W6'=>'0',
			'W5'=>'0',
			'W4'=>'0',
			'W3'=>'0',
			'W2'=>'0',
			'BFPO'=>'0',
			'UB'=>'0',
			'SL'=>'0',
			'HA'=>'0',
			'NW'=>'0',
			'BL'=>'1',
			'OL'=>'1',
			'BB'=>'1',
			'FY'=>'1',
			'LA'=>'1',
			'HR'=>'1',
			'WV'=>'1',
			'ST'=>'1',
			'DY'=>'1',
			'MK'=>'1',
			'TF'=>'1',
			'SK'=>'1',
			'M'=>'1',
			'PR'=>'1',
			'WR'=>'1',
			'LE'=>'1',
			'NN'=>'1',
			'WS'=>'1',
			'DE'=>'1',
			'CV'=>'1',
			'B'=>'1',
			'CW'=>'2',
			'WA'=>'2',
			'CH'=>'2',
			'BD'=>'2',
			'HX'=>'2',
			'DN'=>'2',
			'LS'=>'2',
			'WF'=>'2',
			'SY'=>'2',
			'WN'=>'2',
			'L'=>'2',
			'LL'=>'2',
			'HD'=>'2',
			'YO'=>'2',
			'S'=>'2',
			'NG'=>'2',
			'LN'=>'2',
			'HU'=>'2',
			'HG'=>'2',
			'CM'=>'3',
			'EC'=>'3',
			'PE'=>'3',
			'SS'=>'3',
			'CB'=>'3',
			'CO'=>'3',
			'PRDC'=>'3',
			'HWDC'=>'3',
			'N'=>'3',
			'WC'=>'3',
			'W1'=>'3',
			'AB'=>'4',
			'IV'=>'4',
			'KW'=>'4',
			'CA'=>'4',
			'DD'=>'4',
			'FK'=>'4',
			'ZE'=>'4',
			'TR'=>'4',
			'PL'=>'4',
			'HS'=>'4',
			'DG'=>'4',
			'IM'=>'4',
			'JE'=>'4',
			'GY'=>'4',
			'BT'=>'4',
			'KA'=>'4',
			'ML'=>'4',
			'PA'=>'4',
			'G'=>'4',
			'PH'=>'4',
			'TD'=>'4',
			'KY'=>'4',
			'EH'=>'4',
			'NE'=>'5',
			'DL'=>'5',
			'KT'=>'5',
			'OX'=>'5',
			'TS'=>'5',
			'SR'=>'5',
			'DH'=>'5',
			'GU'=>'5',
			'TW'=>'5',
			'SW'=>'5',
			'SN'=>'5',
			'RG'=>'5',
			'IP'=>'5',
			'NR'=>'5',
			'EX'=>'6',
			'SO'=>'6',
			'BH'=>'6',
			'BA'=>'6',
			'TQ'=>'6',
			'LD'=>'6',
			'GL'=>'6',
			'SA'=>'6',
			'SP'=>'6',
			'PO'=>'6',
			'DT'=>'6',
			'NP'=>'6',
			'CF'=>'6',
			'BS'=>'6',
			'TA'=>'6',
			'BN'=>'7',
			'RM'=>'7',
			'CR'=>'7',
			'BR'=>'7',
			'SE'=>'7',
			'E'=>'7',
			'IG'=>'7',
			'DA'=>'7',
			'TN'=>'7',
			'ME'=>'7',
			'RH'=>'7',
			'SM'=>'7',
			'CT'=>'7',
			'WD'=>'8',
			'EN'=>'8',
			'SG'=>'8',
			'HP'=>'8',
			'LU'=>'8',
			'AL'=>'8',
	);
	
	private static $PartitionNumberShenzhen= array(
			'MY'=>'1',    
			'IN'=>'1',     
			'SG'=>'1',     
			'KR'=>'1',   
			'TH'=>'1',
			'IL'=>'2',
			'US'=>'2',
			'CA'=>'2',
			'AU'=>'2',
			'DE'=>'2',
			'GB'=>'2',
			'MX'=>'2',
			'JP'=>'2',
			'TR'=>'2',
			'CH'=>'2',
			'IT'=>'2',
			'ES'=>'2',
			'RU'=>'3',
			'FI'=>'3',
			'DK'=>'3',
			'BR'=>'4',
			'CO'=>'4',
			'PE'=>'4',
			'CL'=>'4',
			'KE'=>'4',
			'NG'=>'4'
	);
}