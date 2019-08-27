<?php
namespace eagle\modules\platform\helpers;

use eagle\models\SaasAmazonUserMarketplace;
use eagle\models\SaasAmazonUser;
use eagle\modules\amazon\apihelpers\AmazonProxyConnectApiHelper;
//use eagle\models\SaasAmazonAutosync;
use eagle\modules\util\helpers\TimeUtil;
use eagle\modules\amazon\apihelpers\AmazonSyncFetchOrderV2BaseHelper;
use eagle\models\SaasAmazonAutosyncV2;


class AmazonAccountsv2BaseHelper{
	public static $amzSyncType = array(
		'amzOldUnshippedAll',
		'amzNewNotFba',
		'amzNewFba',
		'amzOldNotFbaNotUnshipped',
		'amzOldFbaNotUnshipped',
		'amzFbaInventory',
	);
	/**
	 * [InsertSaasAmazonAutosyncV2 插入记录到amazon的订单同步信息数据表]
	 * @Author   willage
	 * @DateTime 2016-08-06T13:45:10+0800
	 */
	public static function InsertSaasAmazonAutosyncV2($marketplace,$merchantid){
		foreach (self::$amzSyncType as $type_val) {
			$saasAmazonAutosyncV2Object = new SaasAmazonAutosyncV2();
			$saasAmazonAutosyncV2Object->eagle_platform_user_id = $marketplace->amazon_uid;
			$saasAmazonAutosyncV2Object->platform_user_id = $merchantid;
			$saasAmazonAutosyncV2Object->site_id = $marketplace->marketplace_id;
			$saasAmazonAutosyncV2Object->status = $marketplace->is_active;
			$saasAmazonAutosyncV2Object->process_status = 0; //没同步
			$saasAmazonAutosyncV2Object->create_time = $marketplace->create_time ;
			$saasAmazonAutosyncV2Object->update_time = $marketplace->update_time ;
			$saasAmazonAutosyncV2Object->type = $type_val;
			if($type_val=="amzOldUnshippedAll"){
				$saasAmazonAutosyncV2Object->deadline_time=$marketplace->create_time-30*24*3600;
			}
			if($type_val=="amzOldNotFbaNotUnshipped"||$type_val=="amzOldFbaNotUnshipped"){
				$saasAmazonAutosyncV2Object->deadline_time=$marketplace->create_time-2*24*3600;
			}
			if ($type_val=="amzNewFba"||$type_val=="amzOldNotFbaNotUnshipped"||$type_val=="amzOldFbaNotUnshipped") {//其他情况默认为0
				$saasAmazonAutosyncV2Object->slip_window_size=24*3600;//1天
			}
			if ($type_val=="amzOldUnshippedAll") {//避免同时触发，添加随机数
				$saasAmazonAutosyncV2Object->execution_interval=600+rand(0,100);
				$saasAmazonAutosyncV2Object->next_execute_time=$marketplace->create_time+5*60+rand(0,100);
			}else if ($type_val=="amzNewNotFba") {
				$saasAmazonAutosyncV2Object->execution_interval=800+rand(0,100);
				$saasAmazonAutosyncV2Object->next_execute_time=$marketplace->create_time+45*60+rand(0,100);
			}else if ($type_val=="amzNewFba") {
				$saasAmazonAutosyncV2Object->execution_interval=3200+rand(100,200);
				$saasAmazonAutosyncV2Object->next_execute_time=$marketplace->create_time+1*3600+rand(0,100);
			}else if ($type_val=="amzOldNotFbaNotUnshipped") {
				$saasAmazonAutosyncV2Object->execution_interval=6400+rand(100,200);
				$saasAmazonAutosyncV2Object->next_execute_time=$marketplace->create_time+5*3600+rand(0,100);
			}else if ($type_val=="amzOldFbaNotUnshipped") {
				$saasAmazonAutosyncV2Object->execution_interval =6400+rand(100,200);
				$saasAmazonAutosyncV2Object->next_execute_time=$marketplace->create_time+5*3600+rand(0,100);
			}else if ($type_val=="amzFbaInventory") {
				$saasAmazonAutosyncV2Object->execution_interval=43200+rand(0,200);
			}
			if (!$saasAmazonAutosyncV2Object->save()){
				\Yii::error('Platform,' . __CLASS__ . ',' . __FUNCTION__ .',saasAmazonAutosyncV2Object->save() '.print_r($saasAmazonAutosyncV2Object->errors,true) , "file");
				\Yii::info('Platform fail,' . __CLASS__ . ',' . __FUNCTION__ , "file");
				return false;//出现异常，请联系小老板的相关客服
			}
		}
		\Yii::info('Platform OK,' . __CLASS__ . ',' . __FUNCTION__ , "file");
		return true;

	}//end InsertSaasAmazonAutosyncV2

	/**
	 * [UpdateSaasAmazonAutosyncV2 更新到amazon的订单同步信息数据表]
	 * @Author   willage
	 * @DateTime 2016-08-06T14:51:34+0800
	 */
	public static function UpdateSaasAmazonAutosyncV2($marketplace,$merchantid){
		$saasAmazonAutosyncV2Objs = SaasAmazonAutosyncV2::find()->where(['eagle_platform_user_id'=>$marketplace->amazon_uid,'platform_user_id'=>$merchantid,'site_id'=>$marketplace->marketplace_id])->all();
		foreach ($saasAmazonAutosyncV2Objs as $key => $Objone) {
			$Objone->status=$marketplace->is_active;
			$Objone->err_cnt = 0;
			$Objone->err_msg = "";
			if (!$Objone->save()){
				\Yii::error('Platform,' . __CLASS__ . ',' . __FUNCTION__ .',saasAmazonAutosyncV2Object->save() '.print_r($Objone->errors,true) , "file");
				return false;
			}
			
			// dzt20190415 处理queue表
			AmazonAccountsHelper::processQueue($Objone->status, $Objone->id);
		}
		\Yii::info('Platform OK,' . __CLASS__ . ',' . __FUNCTION__ , "file");
		return true;

	}//end UpdateSaasAmazonAutosyncV2




}
?>