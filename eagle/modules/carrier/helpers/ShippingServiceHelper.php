<?php
/**
 * @link http://www.witsion.com/
 * @copyright Copyright (c) 2014 Yii Software LLC
 * @license http://www.witsion.com/
 */
namespace eagle\modules\carrier\helpers;

use eagle\modules\carrier\models\SysCarrier;
use yii;
use yii\helpers\Html;
use eagle\modules\carrier\models\PlatformShippingMappingLog;

/**
 * ShippingServiceHelper.
 *
 */
class ShippingServiceHelper {
	//根据物流商代码查询出物流商名 返回合集
	public static function getCarrierName($obj){
		if(empty($obj))return false;
		$result = array();
		foreach ($obj as $k=> $v) {
			$SysCarrier = SysCarrier::find()->where('carrier_code=:code',[':code'=>$v['carrier_code']])->select(['carrier_name'])->one();
			$result[$k] = $v;
			$result[$k]['carrier_name'] = $SysCarrier->carrier_name;
		}
		return $result;
	}


    /*
     * 保存运输服务管理中标记发货在各平台的映射配置数据，以便新运输服务在开启时载入推荐的配置数据
     */
    public static function saveConfigLogByShippingMapping($platform, $mapping) {
        $log = PlatformShippingMappingLog::find()->where(['platform'=>$platform, 'mapping'=>$mapping])->one();
        if(is_null($log)) {
            $now = date('Y-m-d H:i:s');
            $log = new PlatformShippingMappingLog();
            $log->platform = $platform;
            $log->mapping = $mapping;
            $log->create_time = $now;
            $log->update_time = $now;
            $log->insert();
        }else {
            $log->record++;
            $log->update();           
        }
    }


    /*
     * 新运输服务在开启时，获得推荐的配置数据
     */
    public static function getRecommendConfigByShippingMapping($platform) {
        $log = PlatformShippingMappingLog::find()->where(['platform'=>$platform])->orderBy('record DESC, update_time DESC')->one();
        if(is_null($log)) {
            return '';
        }else {
            return $log->mapping;
        }
    }
}
