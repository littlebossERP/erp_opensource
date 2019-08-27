<?php
namespace eagle\modules\carrier\apihelpers;

use yii;
use common\helpers\Helper_Array;
use eagle\modules\carrier\models\SysCarrier;
use eagle\modules\carrier\models\SysShippingService;
use eagle\models\carrier\SysCarrierAccount;

class ApiHelper{
	/**
	 * 
	 * @return Ambigous <multitype:, multitype:unknown >
	 */
	public static function getCarriers(){
		return 	Helper_Array::toHashmap(SysCarrier::find()->select(['carrier_code','carrier_name'])->asArray()->all(), 'carrier_code','carrier_name');
	}
	
	public static function getCarrierAccounts($carrier=NULL){
		return 	Helper_Array::toHashmap(SysCarrierAccount::find()->select(['id','carrier_name'])->asArray()->all(), 'id','carrier_name');
	}
	
	public static function getShippingServices($all=false){
		if ($all){
			return 	Helper_Array::toHashmap(SysShippingService::find()->select(['id','service_name'])->orderBy('service_name asc')->asArray()->all(), 'id','service_name');
		}else{
			return 	Helper_Array::toHashmap(SysShippingService::find()->where(['is_used'=>1])->select(['id','service_name'])->orderBy('service_name asc')->asArray()->all(), 'id','service_name');
		}
		
	}

	/*
	 *	return array (size=4)
				  11 => //海外仓帐号ID号
				    array
				      'account_name' => string '铁三角' //用户帐号昵称
				      'warehouse' =>  //账号内所有 [仓库ID => 仓库名] 键值对
				        array (size=4)
				          'USLA' => string '美国洛杉矶仓' (length=18)
				          'DEWH' => string '德国仓' (length=9)
				          'AUSY' => string '澳洲仓' (length=9)
				          'UKLH' => string '英国仓' (length=9)
	 */
	public static function getWerehouseAccounts(){
		$objs = SysCarrierAccount::find()->where(['carrier_type'=>1])->select(['id','carrier_name','carrier_code'])->all();
		$result = [];
		foreach ($objs as $obj) {
			//根据物流商代码 查询出对应的所有仓库信息
			require_once(Yii::getAlias('@web').'docs/'.$obj->carrier_code.'.php');
			$result[$obj->id] = [
				'account_name'=>$obj->carrier_name,
				'warehouse'=>$warehouse,
			];
		}
		return $result;
	}
}