<?php namespace eagle\modules\listing\service;

use eagle\modules\util\helpers\TimeUtil;

class Log
{

	static function timestamp($addon=''){
		self::info($addon.' ---- '.TimeUtil::getCurrentTimestampMS().' ----');
	}

	static function time($addon=''){
		self::info($addon.' ---- '.date('Y-m-d H:i:s').' ----');
	}

	static function info($str){
		if(!is_string($str)){
			$str = var_export($str,true);
		}
		if(defined('TEST_MODE') && TEST_MODE === 'PAGE'){
			echo $str.PHP_EOL;
		}
		\Yii::info($str.PHP_EOL,'file');
	}

	static function error($str,$code=500,$continue = false){
		// 判断是否在console环境中
		if( isset(\Yii::$app->controller) && \Yii::$app->controller->module->id == 'app-console'){ // 判断是否来自控制台
			$continue = true;
		}
		if(!is_string($str)){
			$str = var_export($str,true);
		}
		if(isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH'])==='xmlhttprequest'){
			\Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
			echo json_encode([
				'error'=>$code,
				'message'=>$str
			]);
		}else{
			echo $str.PHP_EOL;
		}
		\Yii::error($str.PHP_EOL,'file');
		if(!$continue) 
			die;
		return;
	}

}