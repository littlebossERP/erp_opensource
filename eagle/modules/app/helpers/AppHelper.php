<?php

namespace eagle\modules\app\helpers;
use eagle\modules\app\models\UserAppInfo;
use eagle\modules\app\models\AppInfo;
use eagle\modules\util\helpers\TranslateHelper;
use eagle\modules\app\models\AppCategoryAppkey;
use eagle\modules\app\models\AppUserUsage;

class AppHelper{
	
	/**
	 * 获取app分类和该分类下所有app的对应关系
	 * return  ["categoryid1"=>["appkey1","appke2"],"categoryid2"=>[].....]
	 */
	public static function getAppcateogryidKeylistMap(){
		$categoryAppkeyList=AppCategoryAppkey::find()->asArray()->all();
		$categoryAppkeyMap=array();
		foreach($categoryAppkeyList as $categoryAppkey){
			$categoryId=$categoryAppkey["app_category_id"];
			if (!isset($categoryAppkeyMap[$categoryId])) $categoryAppkeyMap[$categoryId]=array();
			$categoryAppkeyMap[$categoryId][]=$categoryAppkey["app_key"];
		}
		return $categoryAppkeyMap;
			
	}
	
	public static function getAppCategoryMap(){
		//SELECT aca.app_category_id as category_id,name,count(*) as count FROM `app_category_appkey` aca join app_category ac on aca.app_category_id=ac.id group by  aca.app_category_id

		$sqlStr="SELECT aca.app_category_id as category_id,name,ac.type as type,count(*) as count FROM `app_category_appkey` aca join app_category ac on aca.app_category_id=ac.id group by  aca.app_category_id";
		$primaryConn = \Yii::$app->db;
		$command = $primaryConn->createCommand($sqlStr);
		$appCategoryInfo = $command->queryAll();
		
		$appCategoryMap=array();
		foreach($appCategoryInfo as $appCategory){
			$type=$appCategory["type"];
			if (!isset($appCategoryMap[$type])) $appCategoryMap[$type]=array();
			$appCategoryMap[$type][]=$appCategory;			
		}		
		
		return $appCategoryMap;
	}
	
	//获取该用户已经安装的app的key列表
	public static function getInstalledAppKey(){
		$installedAppList=UserAppInfo::find()->all();
		$keyList=array();
		foreach($installedAppList as $installedAppInfo){
			$keyList[]=$installedAppInfo->key;
		}
		return $keyList;
	}
	
	/**
	 * 获取该用户已经启用的app的key列表
	 * @return   array("ebay_listing","order")
	 * 如果没有已启用app，就返回array()
	 */
	public static function getActiveAppKeyList(){
		$installedAppList=UserAppInfo::find()->where(['is_active'=>'Y'])->all();
		$keyList=array();
		foreach($installedAppList as $installedAppInfo){
			$keyList[]=$installedAppInfo->key;
		}
		return $keyList;	
	}	
	

	/**
	 * 查找指定app的app.  如：仓库app依赖商品app。  这里可以查看所有依赖于商品app的app  
	 * @param  $key---  app对应的id
	 * @return array(appkey1,appkey2)--------- 
	 */	
	private static function _findAllPointingApps($appId){
		$appInfoList=AppInfo::find()->all();
		$pointingAppList=array();
		foreach($appInfoList as $appInfo){
			if (strlen(trim($appInfo->depends))>0){
				$appidlist=explode(',',trim($appInfo->depends));
				if (in_array($appId,$appidlist)){
					$pointingAppList[]=$appInfo->key;					
				}
			}
		}
		return $pointingAppList;
	}
	


	/**
	 * 停用指定app。   这里不用考虑app之前的依赖关系。
	 */
	private static function _unactivateAppByKey($appKey){
		
		$appInfo=AppInfo::find()->where(["key"=>$appKey])->one();
		if ($appInfo===null) return false;
		$uid=\Yii::$app->user->id;
		if ($uid=="") return false;
		
		
		$puid=\Yii::$app->user->identity->getParentUid();
	
		$userAppInfo=UserAppInfo::find()->where(["key"=>$appKey])->one();
		$appUserUsage=AppUserUsage::find()->where(["key"=>$appKey,"puid"=>$puid])->one();
		if ($userAppInfo===null) return false;
				
		if ($appUserUsage===null) {
			$appUserUsage=new AppUserUsage;
			$appUserUsage->key=$appKey;
			$appUserUsage->puid=$puid;
		}
		
		//用户表app的记录
		$userAppInfo->is_active='N';
		$userAppInfo->update_time=date("Y-m-d H:i:s");
		$userAppInfo->save(false);
	
		//全局managedb表app的记录
		$appUserUsage->is_active='N';
		$appUserUsage->unactivate_time=date("Y-m-d H:i:s");
		$appUserUsage->save(false);
	
		return true;
	}	
	/**
	 * 启用指定app。   这里不用考虑app之前的依赖关系。
	 */
	private static function _activateAppByKey($appKey){
		$appInfo=AppInfo::find()->where(["key"=>$appKey])->one();
		if ($appInfo===null) return false;
		$uid=\Yii::$app->user->id;
		if ($uid=="") return false;
		$puid=\Yii::$app->user->identity->getParentUid();
		
		$userAppInfo=UserAppInfo::find()->where(["key"=>$appKey])->one();		
		$appUserUsage=AppUserUsage::find()->where(["key"=>$appKey,"puid"=>$puid])->one();
		if ($userAppInfo===null) return false;
		if ($appUserUsage===null){
			$appUserUsage=new AppUserUsage;
			$appUserUsage->key=$appKey;
			$appUserUsage->puid=$puid;
			$appUserUsage->install_time=date("Y-m-d H:i:s");
		}
		
		//用户表app的记录
		$userAppInfo->is_active='Y';
		$userAppInfo->update_time=date("Y-m-d H:i:s");
		$userAppInfo->save(false);
	
		//全局managedb表app的记录
		$appUserUsage->is_active='Y';
		$appUserUsage->activate_time=date("Y-m-d H:i:s");
		$appUserUsage->save(false);
		
		return true;
	}	
    /**
     * 安装指定app。   这里不用考虑app之前的依赖关系。
     */
	private static function _installAppByKey($appKey){
		$appInfo=AppInfo::find()->where(["key"=>$appKey])->one();
		if ($appInfo===null) return false;
		$uid=\Yii::$app->user->id;
		if ($uid=="") return false;
		$puid=\Yii::$app->user->identity->getParentUid();
		
		//用户表的记录
		$userAppInfo=new UserAppInfo;
		$userAppInfo->name=$appInfo->name;
		$userAppInfo->key=$appInfo->key;
		$userAppInfo->is_active='Y';
		$userAppInfo->install_time=date("Y-m-d H:i:s");
		$userAppInfo->update_time=$userAppInfo->install_time;
		$userAppInfo->save(false);
		
		//全局managedb表的记录
	//	$appUserUsage=AppUserUsage::find()->where(['key'=>$appKey]);
	//	if ($appUserUsage===null){
			$appUserUsage=new AppUserUsage;
			$appUserUsage->key=$appKey;
			$appUserUsage->puid=$puid;
			$appUserUsage->is_active='Y';			
			$appUserUsage->install_time=date("Y-m-d H:i:s");
			$appUserUsage->activate_time=$appUserUsage->install_time;
			$appUserUsage->save(false);
	//	}else{
			//不应该进入该代码分支的
	//		$appUserUsage->is_active='Y';
	//		$userAppInfo->save(false);				
		//}
		return true;
	}	
	
	/**
	 * 安装或者启用指定的app key
	 * @param  $key---  app对应的key
	 * @param  $command ------ install 或者 activate
	 * @return array($ret,$message)---------  $ret返回true或者false.  
	 * 1）当ret false 的时候 $message是错误信息
	 * 2）当ret true的时候， command=="activate"的话，$message 为空字符串
	 * 3）当ret true的时候， command=="install"的话，$message 为这次请求一共安装了哪些app key， json格式
	 */
	public static function installOrActivateApp($key,$command="install"){
        
		$mainAppInfo=AppInfo::find()->where(['key'=>$key])->one();
		$installedKeyList=array(); //这次请求一共安装了哪些app key
		//1.合理性检查
		if ($mainAppInfo===null){
			//该app不存在
			//return [false,TranslateHelper::t("指定的app不存在!!")];
			return [false,TranslateHelper::t("Error3010:系统异常.请联系小老板客服!")];
		}
		if ($command=="install"){
			$userAppInfo=UserAppInfo::find()->where(['key'=>$key])->one();
			if ($userAppInfo===null){ }else{
				return [false,TranslateHelper::t("指定的app已经安装,不可以重复安装!!")];				
			}
		}else{
			$userAppInfo=UserAppInfo::find()->where(['key'=>$key])->one();
			if ($userAppInfo===null){
				//return [false,TranslateHelper::t("指定的app还没有安装,不能启用!!")];
				return [false,TranslateHelper::t("Error3011:系统异常.请联系小老板客服!")];
			}
			if ($userAppInfo->is_active=='Y'){
				//return [false,TranslateHelper::t("指定的app已启用!!")];
				return [false,TranslateHelper::t("指定的功能模块已启用!!")];
			}
				
		}
		
		//2.需要先安装并启用所依赖的app。这里不玩递归，appInfo->depends就是该app所有依赖的app
		if (strlen(trim($mainAppInfo->depends))>0){
			$dependsAppidList=explode(',', $mainAppInfo->depends);
			
			foreach($dependsAppidList as $appId){
				$appInfo=AppInfo::find()->where(['id'=>$appId])->one();
				$key=$appInfo->key;
				//检查依赖的app是否已经安装并且启用
				$userAppInfo=UserAppInfo::find()->where(['key'=>$key])->one();
				if ($userAppInfo===null){
					//安装依赖的app
					$installedKeyList[]=$key;
					self::_installAppByKey($key);
/*					$userAppInfo=new UserAppInfo;
					$userAppInfo->name=$appInfo->name;
					$userAppInfo->key=$appInfo->key;
					$userAppInfo->is_active='Y';					
					$userAppInfo->install_time=date("Y-m-d H:i:s");
					$userAppInfo->update_time=$userAppInfo->install_time;
					$userAppInfo->save(false);		*/
					continue;		
				}
				if ($userAppInfo->is_active=='N'){
					//启用依赖的app
					self::_activateAppByKey($key);
//					$userAppInfo->is_active='Y';
//					$userAppInfo->update_time=date("Y-m-d H:i:s");
//					$userAppInfo->save(false);
				}
			}
		}
		
		
		//3.安装或启用指定的app		 
		if ($command=="install"){
			$installedKeyList[]=$mainAppInfo->key;
			/*$userAppInfo=new UserAppInfo;
			$userAppInfo->name=$mainAppInfo->name;
			$userAppInfo->key=$mainAppInfo->key;
			$userAppInfo->is_active='Y';
			$userAppInfo->install_time=date("Y-m-d H:i:s");
			$userAppInfo->update_time=$userAppInfo->install_time;
			$userAppInfo->save(false);*/
			self::_installAppByKey($mainAppInfo->key);
			return [true,json_encode($installedKeyList)];
		} 
			
	/*	$userAppInfo=UserAppInfo::find()->where(['key'=>$mainAppInfo->key])->one();
		$userAppInfo->is_active='Y';
		$userAppInfo->update_time=date("Y-m-d H:i:s");
		$userAppInfo->save(false);*/
		self::_activateAppByKey($mainAppInfo->key);
		return [true,""];
		 		
	} 
	/**
	 * 停用指定的app key--- 需要考虑的app依赖关系！！！！
	 * @param  $key---  app对应的key
	 * @return array($ret,$message)---------  $ret返回true或者false
	 */
	public static function unActivateApp($key){
		$mainAppInfo=AppInfo::find()->where(['key'=>$key])->one();
		//1.合理性检查
		if ($mainAppInfo===null){
			//该app不存在
			return [false,TranslateHelper::t("Error3005:系统异常.请联系小老板客服!")];
		}
		$reqAppInfo=UserAppInfo::find()->where(['key'=>$key])->one();
		if ($reqAppInfo===null){
			//return [false,TranslateHelper::t("指定的app还没有安装,不能停用!!")];
			return [false,TranslateHelper::t("Error3006:系统异常.请联系小老板客服!")];
		}
		if ($reqAppInfo->is_active=='N'){
			//return [false,TranslateHelper::t("指定的app已停用!!")];
			return [false,TranslateHelper::t("指定的功能模块已停用!!")];
		}
	
		//2.依赖检查--- 该app可以停用的前提是，所有依赖该app的app都已经停用或者未安装。
		$pointingAppKeyList=self::_findAllPointingApps($mainAppInfo->id);
		$warnAppNameList=array();
		foreach($pointingAppKeyList as $appKey){
			$userAppInfo=UserAppInfo::find()->where(["key"=>$appKey])->one();
			if ($userAppInfo and $userAppInfo->is_active=='Y'){
				// 依赖该app的app 不可以处于启用状态
				$warnAppNameList[]=$userAppInfo->name;
			}
		}
		if (count($warnAppNameList)>=1){
			return [false,"app列表: '".implode(',',$warnAppNameList)."' 需要先被停用，才可以停用选中的app: '".$mainAppInfo->name."'"];
		}
	
		//3. 停用指定app
		/*	$reqAppInfo->is_active='N';
			$reqAppInfo->update_time=date("Y-m-d H:i:s");
		$reqAppInfo->save(false);*/
	
		self::_unactivateAppByKey($reqAppInfo->key);
	
		return [true,""];
	
	
	}
	
	
}