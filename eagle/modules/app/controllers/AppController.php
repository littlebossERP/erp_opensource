<?php

namespace eagle\modules\app\controllers;

use yii\web\Controller;
use eagle\modules\app\models\AppInfo;
use eagle\modules\app\models\UserAppInfo;
use eagle\modules\app\helpers\AppHelper;
use eagle\modules\util\helpers\TranslateHelper;
use eagle\helpers\UserHelper;
use eagle\modules\app\apihelpers\AppTrackerApiHelper;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;

class AppController extends Controller
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
	 * 获取所有app的列表界面
	 */
    public function actionList()
    {
    	$userAppList=array();
    	
    	$appCategoryMap=AppHelper::getAppCategoryMap();
    	$get =\Yii::$app->request->get();
    	$chosenCategoryId=-1;
    	if (isset($get["categoryid"]) and !empty($get["categoryid"])){
    		//指定了app 的类别id
    		$chosenCategoryId=$get["categoryid"];
    	}
    	
    	//获取app分类和该分类下所有app的对应关系
    	$categoryAppkeysMap=AppHelper::getAppcateogryidKeylistMap();
    	 
    	
    	$allAppList=AppInfo::find()->asArray()->all();
    	//获取该用户已经active的key列表
    	$activeAppKeyList=AppHelper::getActiveAppKeyList();
    	//获取该用户已经install app的key列表
    	$installedAppKeyList=AppHelper::getInstalledAppKey();
    	foreach($allAppList as &$appInfo){
    		if (in_array($appInfo["key"], $installedAppKeyList)){
    			$appInfo["installed"]='Y';
    		}else $appInfo["installed"]='N';
    		if (in_array($appInfo["key"], $activeAppKeyList)){
    			$appInfo["is_active"]='Y';
    		}else $appInfo["is_active"]='N';
    	}
    	
    	
    	return $this->render('list', ['allAppList' => $allAppList,'appCategoryMap'=>$appCategoryMap,'categoryAppkeysMap'=>$categoryAppkeysMap]);
    }
    /**
     * 已经安装的app的列表界面
     */
    public function actionInstalledList(){
    	//分别获取已启用和未启用的app列表
    	$activeAppList=UserAppInfo::find()->where(["is_active"=>"Y"])->all();
    	$unActiveAppList=UserAppInfo::find()->where(["is_active"=>"N"])->all();
    	
    	return $this->render('installedlist', ['activeAppList' => $activeAppList,'unActiveAppList' => $unActiveAppList]);
    }
    
    
    /**
     * 查看指定app的详细信息
     * @params
     * key---  app key
     *  showOperation-- 0 或者 1.  在app的详情页是否展示操作按钮，如： 启用app。  默认是展示。
     */
    public function actionView()
    {    	
    	if (!isset($_REQUEST["key"]) or empty($_REQUEST["key"])) return;    	
    	$key=$_REQUEST["key"];
    	
    	//showOperation-- 0 或者 1.  在app的详情页是否展示操作按钮，如： 启用app。  默认是展示。
    	$showOperation=1;
    	if (isset($_REQUEST["showOperation"])) $showOperation=$_REQUEST["showOperation"];
    	
    	
        $appInfo=AppInfo::find()->where(['key'=>$key])->asArray()->one();
        //获取该用户已经install app的key列表
        $installedAppKeyList=AppHelper::getInstalledAppKey();
        //获取该用户已经active的key列表
        $activeAppKeyList=AppHelper::getActiveAppKeyList();
        
        if (in_array($appInfo["key"], $installedAppKeyList)){
        	$appInfo["installed"]='Y';
        }else $appInfo["installed"]='N';
        if (in_array($appInfo["key"], $activeAppKeyList)){
        	$appInfo["is_active"]='Y';
        }else $appInfo["is_active"]='N';
        
        
        
    	return $this->renderAjax("cms_".$key, ['appInfo' => $appInfo,'showOperation'=>$showOperation]);
        
    } 
    /**
     * 安装或启用指定app ---- 安装/启用的区别是， 安装是指第一次启用！！！！     
     * key 参数
     */
    public function actionInstall()
    {
    	$post =\Yii::$app->request->post();
    	
    	if (!isset($post["key"]) or empty($post["key"])) {
    		echo json_encode(["code"=>"fail","message"=>TranslateHelper::t("请指定app")]);    		
    		return;
    	}
    	 
    	$key=$post["key"];    	
    	//自动判断是安装还是启用
    	$userApp=UserAppInfo::find()->where(["key"=>$key])->one();
    	if ($userApp===null){
    		//安装
    		list($ret,$message)=AppHelper::installOrActivateApp($key);
    	}else{
    		//启用    	
    	    list($ret,$message)=AppHelper::installOrActivateApp($key,"activate");
    	}
    	if ($ret===false){
    		echo json_encode(["code"=>"fail","message"=>$message]);
    		return;
    	}
    	echo json_encode(["code"=>"ok","message"=>TranslateHelper::t("安装成功!!")]);
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
    
  /**
   * 前台自定义操作行为记录！！
   * @params
   * post的方式访问，参数： 
   * appKey-------是指每个app对应的全局唯一的key值，如： 商品模块就是product，  采购模块就是purchase等等。
   * urlPath------- 是指页面url，网站的域名就不需要提供。  如 ：/purchase/purchase/list。  这里可以自己定义唯一的url。
   * isLandpage -----Y或者N，默认是N。  是指是否把该自定义的访问看做是1个着落页的访问，是的话，后面在app的着落页访问统计中会出现。
   * 
   * @return 不需要返回值
   */    
    public function actionTracker()
    {
    	$post =\Yii::$app->request->post();
    	if (!isset($post["appKey"]) or empty($post["appKey"]) or !isset($post["urlPath"]) 
    	     or empty($post["urlPath"]) or !isset($post["isLandpage"]) or empty($post["isLandpage"])) {
    		return;
    	}
    	
    	if ($isLandpage<>'Y' and $isLandpage<>'N'){
    		return;
    	}
    
    	$appKey=$post["appKey"];
    	$urlPath=$post["urlPath"];
    	$isLandpage=$post["isLandpage"];
    	
    	AppTrackerApiHelper::log($appKey,$urlPath,$isLandpage);
    
    }    
    
}







