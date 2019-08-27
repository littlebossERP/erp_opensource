<?php
/**
 * @link http://www.witsion.com/
 * @copyright Copyright (c) 2014 Yii Software LLC
 * @license http://www.witsion.com/
 */
namespace eagle\modules\app\apihelpers;

use yii;
use yii\data\Pagination;
use eagle\modules\app\models\AppInfo;
use eagle\modules\app\helpers\AppHelper;
use eagle\modules\app\models\UserAppInfo;
use eagle\modules\app\models\AppUserStatistic;
use eagle\modules\app\models\AppUserActionLog;
use eagle\models\User;



/**
 * BaseHelper is the base class of module BaseHelpers.
 *

 */
class AppTrackerApiHelper {

	
	/**
	 * 记录每个app的使用情况.
	 * @param  $appKey -------  app的key。  如： 物流跟踪模块就是 tracker 
	 * @param string $urlPath ---  url路径。 如：  /tracker/list.    不一定要跟当前用户访问的路径一样，可以自定义
	 * @param array $params=array("param1"=>1,"param2"=>2,"paramstr1"=>"dfdf")  ----------  这里提供3个额外的参数来支持用户记录业务数据。  如：tracker用户上传excel的物流号数量
	 * param1和param2对应的值，  必须为整数.   
	 * paramstr1 对应的值为字符串
	 * 
	 * @param $uid boolean|int 
	 * @param $puid boolean|int 
	 * $uid和$puid 参数为login by cookie 的时候\Yii::$app->user->id 获取不到uid 而设置。
	 * 
	 * 调用例子
	 * AppTrackerApiHelper::actionLog("tracker","/tracker/uploadexcel",["param1"=>10,"paramstr1"=>"aaaa"]);
	 */
	public static function actionLog($appKey,$urlPath,$params=array(),$uid=false,$puid=false){
		
		//return true;  // 暂时关闭该功能
		
		if (isset($_SESSION['puid']) and !empty($_SESSION['puid'])){
			//  管理员在tracker页面切换用户！！！！  不需要做记录
			return false;
		}
		
		if ($appKey=="" or $urlPath=="") return false;
		if($uid === false)
			$uid=\Yii::$app->user->id;
		if ($uid=="") return false; // 没登陆直接返回
		
		
		if($puid === false){
			if (isset(\Yii::$app->user) and isset(\Yii::$app->user->identity))
				$puid=\Yii::$app->user->identity->getParentUid(); else return false;
		}
			
		if(isset(\Yii::$app->session['super_login']) && \Yii::$app->session['super_login'] == 1){// 超级密码登录 不记录actionLog
			return true;
		}
		
	//	\Yii::info("actionLog uid:".$uid,"file");
// 		\Yii::info("uid:".print_r(\Yii::$app->user->identity,true),"file");
		
		$actionLog=new AppUserActionLog;
		
		$actionLog->uid=$uid;
		$actionLog->puid=$puid;
		$actionLog->log_time=date("Y-m-d H:i:s");
		if (count($params)>0){
			if (isset($params["param1"])) $actionLog->param1=$params["param1"];
			if (isset($params["param2"])) $actionLog->param2=$params["param2"];
			if (isset($params["paramstr1"])) $actionLog->param_str1=$params["paramstr1"];
		}
		$actionLog->app_key=$appKey;
		$actionLog->url_path=$urlPath;
		$actionLog->save(false);
		return true;
	} 
		
	
	
	/**
	 * 记录每个app的使用情况.  2个场景
	 * （1）每个用户，每个app的controller/action，每天的访问统计情况
	 * （2）后台自定义操作行为记录
	 * @param unknown $appKey
	 * @param string $urlPath
	 * @param string $isLandpage
	 */
	public static function log($appKey,$urlPath="",$isLandpage='Y'){
		$uid=\Yii::$app->user->id;
		if ($uid=="") return false; // 没登陆直接返回
		$puid=\Yii::$app->user->identity->getParentUid();		
		
		if ($urlPath==""){
			// 场景1   每个用户，每个app的controller/action，每天的访问统计情况
			$module=\Yii::$app->controller->module->id;
			$controller=\Yii::$app->controller->id;
			$action=\Yii::$app->controller->action->id;
			$urlPath="/".$module."/".$controller."/".$action;	
			$isLandpage='Y';
		}else{
			//场景2  后台自定义操作行为记录
			//这里就直接使用入参，不需要修改
		}
		
		
		$currentDate=date("Y-m-d");
		$currentDateTime=date("Y-m-d H:i:s");
		$appUserStatistic=AppUserStatistic::find()->where(["puid"=>$puid,"visit_date"=>$currentDate,"key"=>$appKey,"url_path"=>$urlPath])->one();
		if ($appUserStatistic===null){
			$appUserStatistic=new AppUserStatistic;
			$appUserStatistic->puid=$puid;
			$appUserStatistic->key=$appKey;
			$appUserStatistic->url_path=$urlPath;
			$appUserStatistic->visit_date=$currentDate;
			$appUserStatistic->update_time=$currentDateTime;
			$appUserStatistic->visit_count=1;
			$appUserStatistic->is_landpage_statistic=$isLandpage;
			$appUserStatistic->save(false);
			return;			
		}
		
		$appUserStatistic->update_time=$currentDateTime;
		$appUserStatistic->visit_count=$appUserStatistic->visit_count+1;
		$appUserStatistic->save(false);
	}
	
	

	
	
	
}
