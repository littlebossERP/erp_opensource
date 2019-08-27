<?php

namespace eagle\modules\app\helpers;
use eagle\modules\util\helpers\TranslateHelper;
use eagle\modules\util\helpers\ConfigHelper;

class AppConfigHelper {	

	public static function configSave($params){
		foreach($params as $configName=>$value){
			if ($configName=="appkey") {
				continue;
			}
			ConfigHelper::setConfig($configName, $value);
			
		}
		
	}
	

	
}