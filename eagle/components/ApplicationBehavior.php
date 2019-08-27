<?php
namespace eagle\components;


use yii\base\Behavior;
use yii\helpers\Url;
use eagle\modules\util\models\VisitLog;
use eagle\modules\util\helpers\UserLastActionTimeHelper;
use eagle\modules\app\apihelpers\AppTrackerApiHelper;

class ApplicationBehavior extends Behavior
{
	// 重载events() 使得在事件触发时，调用行为中的一些方法
	public function events()
	{
		// 在EVENT_BEFORE_VALIDATE事件触发时，调用成员函数 beforeValidate
		return [
		  \yii\base\Application::EVENT_BEFORE_REQUEST => 'beforeRequest',
		];
	}

	// 注意beforeValidate 是行为的成员函数，而不是绑定的类的成员函数。
	// 还要注意，这个函数的签名，要满足事件handler的要求。
	public function beforeRequest($event)
	{
		
		//print_r(\Yii::$app->request->pathInfo); die;
		
	//	if (isset(\Yii::$app->params["isOnlyForOneApp"]) and \Yii::$app->params["isOnlyForOneApp"]==1 ) return true;
		
		$uid=\Yii::$app->user->id;		
		if ($uid=="") return true; //没登陆

		/* //lolo20150924 屏蔽-- 用户行为记录不再自动记录，各个app中代码进行记录 AppTrackerApiHelper::actionLog
		if (\Yii::$app->params["isOnlyForOneApp"]==0 ){
			$url=\Yii::$app->request->pathInfo;			
			
			$pos = strrpos($url,'?');
			if($pos) {
				$url = substr($url,0,$pos);
			}
			$actionFilters=array("toolbar");
			$tempUrlArr = explode('/',$url);
			if (count($tempUrlArr)==3){
				$module=$tempUrlArr[0];
				$controller=$tempUrlArr[1];
				$action=$tempUrlArr[2];
				if (!in_array($action,$actionFilters))				
					AppTrackerApiHelper::actionLog("eagle_v2","/$module/$controller/$action");
				  //AppTrackerApiHelper::actionLog("eagle_v2","/eagle_v2/$controller/$action");
			}else if (count($tempUrlArr)==2){
				$module=$tempUrlArr[0];
				$controller=$tempUrlArr[1];
				//AppTrackerApiHelper::actionLog("eagle_v2","/eagle_v2/$controller");
				AppTrackerApiHelper::actionLog("eagle_v2","/$module/$controller");
			}
				
			
		}*/
		
		
		
	  /*  $visitLog=new VisitLog;
	    $visitLog->url_path=\Yii::$app->request->pathInfo;
	    $visitLog->visit_time=date("Y-m-d H:i:s");
	    $visitLog->save(false);*/
		UserLastActionTimeHelper::saveLastActionTime();
		
		return true;
	}
}