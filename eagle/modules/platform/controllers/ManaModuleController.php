<?php
namespace eagle\modules\platform\controllers;

use yii\web\Controller;
use eagle\models\SaasAliexpressUser;
use yii\data\Sort;
use yii\data\Pagination;
use eagle\modules\util\helpers\TranslateHelper;
use eagle\models\SaasAliexpressAutosync;
use common\api\aliexpressinterface\AliexpressInterface_Auth;
use eagle\modules\app\apihelpers\AppTrackerApiHelper;
use eagle\modules\app\models\UserAppInfo;
use eagle\modules\app\helpers\AppHelper;
class ManaModuleController extends Controller
{
	public function behaviors() {
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
				'class' => \yii\filters\VerbFilter::className(),
				'actions' => [
					'delete' => ['post'],
				],
			],
		];
	}

	/**
	 * 已经安装的app的列表界面
	 */
	public function actionList(){
		//分别获取已启用和未启用的app列表
	//	$activeAppList=UserAppInfo::find()->where(["is_active"=>"Y"])->all();
	//	$unActiveAppList=UserAppInfo::find()->where(["is_active"=>"N"])->all();
	
		$appList=UserAppInfo::find()->all();
		
		 
		return $this->render('installedlist', ['appList' => $appList]);
	}	
	/**
	 * 停用指定app
	 * key 参数
	 */
	public function actionUnactivate()
	{
		
		$post =\Yii::$app->request->post();
		if (!isset($post["key"]) or empty($post["key"])) {
			echo json_encode(["code"=>"fail","message"=>TranslateHelper::t("请指定app")]);
			return;
		}
	
		$key=$post["key"];
	
		list($ret,$message)=AppHelper::unActivateApp($key);
		if ($ret===false){
			echo json_encode(["code"=>"fail","message"=>$message]);
			return;
		}
		echo json_encode(["code"=>"ok","message"=>TranslateHelper::t("停用成功!!")]);
	}	
	
	
	/**
	 * 启用指定app
	 * key 参数
	 */
	public function actionActivate()
	{
		$post =\Yii::$app->request->post();
		if (!isset($post["key"]) or empty($post["key"])) {
			echo json_encode(["code"=>"fail","message"=>TranslateHelper::t("请指定app")]);
			return;
		}
	
		$key=$post["key"];
	
		list($ret,$message)=AppHelper::installOrActivateApp($key,"activate");
		if ($ret===false){
			echo json_encode(["code"=>"fail","message"=>$message]);
			return;
		}
		echo json_encode(["code"=>"ok","message"=>TranslateHelper::t("启用成功!!")]);
	}
		
}