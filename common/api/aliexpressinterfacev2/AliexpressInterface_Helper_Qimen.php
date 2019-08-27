<?php
namespace common\api\aliexpressinterfacev2;
use \Yii;
use eagle\models\AliexpressOrder;
use eagle\models\SysCountry;
use eagle\models\AliexpressChildorderlist;
use eagle\modules\order\helpers\OrderHelper;
use eagle\modules\util\helpers\EagleOneHttpApiHelper;
use Exception;
use eagle\modules\util\helpers\TimeUtil;
use eagle\modules\order\models\OdOrder;
use eagle\models\SysShippingCodeNameMap;
use common\helpers\Helper_Array;
use eagle\modules\util\helpers\CountryHelper;
use eagle\modules\util\helpers\RedisHelper;
use eagle\models\SaasAliexpressUser;

class AliexpressInterface_Helper_Qimen{
	
	/**
	 * 检测是否绑定token并且是否过期
	 */
	public static function checkToken($sellerloginid){
		$SAU_obj = SaasAliexpressUser::findOne(['sellerloginid' => $sellerloginid, 'version' => 'v2']);
		if (isset($SAU_obj)){
			if($SAU_obj->refresh_token_timeout > time()){
	    		return true;
	    	}else{
	    		return false;
	    	}
		}else{
			return false;
		}
	}
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 判断速卖通账号是否属于v2版本
	 +---------------------------------------------------------------------------------------------
	 * @param $sellerloginid
	 +---------------------------------------------------------------------------------------------
	 * @return	boolean
	 +---------------------------------------------------------------------------------------------
	 * log			name	date			note
	 * @author		lrq		2018/01/09		初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	public static function CheckAliexpressV2( $sellerloginid){
		try{
			$ali_user = SaasAliexpressUser::findOne(['sellerloginid' => $sellerloginid]);
			if(!empty($ali_user) && $ali_user->version == 'v2'){
				return true;
			}
		}
		catch(\Exception $ex){
			
		}
		return false;
	}
	
	/**
	 * 速卖通返回的时间，转换为时间戳
	 * lrq
	 */
	static function transLaStrTimetoTimestamp($gmttime_str, $type = 'CN'){
		$time_str_arr = explode('-', $gmttime_str);
		$time_str_arr[0] = substr($time_str_arr[0],0,14);
		$serverLocalTime = new \DateTime(implode('-', $time_str_arr));
		
		//使用订单新接口，下单时间为美国时间，需转换为北京时间，时差15小时
		if($type == 'US'){
			return  $serverLocalTime->getTimestamp() + 3600 * 15;
		}
		return  $serverLocalTime->getTimestamp();
	}
	
}