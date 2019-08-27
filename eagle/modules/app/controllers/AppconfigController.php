<?php

namespace eagle\modules\app\controllers;

use yii\web\Controller;
use eagle\modules\app\models\AppInfo;
use eagle\modules\app\models\UserAppInfo;
use eagle\modules\app\helpers\AppHelper;
use eagle\modules\util\helpers\TranslateHelper;
use eagle\modules\app\helpers\AppConfigHelper;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;

class AppconfigController extends Controller
{
	

	public $enableCsrfValidation = FALSE;
	
	public function behaviors()
	{
		return [
		'access' => [
		'class' => \yii\filters\AccessControl::className(),
		'rules' => [
		[
		'allow' => true,
		'roles' => ['@'],
		],
		],
		],
		'verbs' => [
		'class' => VerbFilter::className(),
		'actions' => [
		'delete' => ['post'],
		],
		],
		];
	}	
	/**
	 * 返回指定app key的参数设置页面(弹出框形式)
	 * key 参数
	 */
	public function actionView()
	{
		$get =\Yii::$app->request->get();
		if (!isset($get["key"]) or empty($get["key"])) {
			echo json_encode(["code"=>"fail","message"=>TranslateHelper::t("请指定app")]);
			return;
		}	
		$key=$get["key"];		 
		return $this->renderAjax('config_'.$key, []);	
	}

	/**
	 * 保存app的参数
	 */
	public function actionSave(){
		$post =\Yii::$app->request->post();
		AppConfigHelper::configSave($post);
		echo json_encode(["code"=>"ok","message"=>""]);
		return; 
	}
}







