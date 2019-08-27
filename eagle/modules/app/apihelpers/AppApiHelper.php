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
use eagle\modules\app\models\UserAppFunctionStatus;
use eagle\modules\util\helpers\UserBGJobControllHelper;
use eagle\modules\util\helpers\TranslateHelper;
use eagle\modules\platform\apihelpers\PlatformAccountApi;


/**
 * AppApiHelper 是app模块提供给外部模块调用的接口
 *
 */
class AppApiHelper {
	
	public static $BIND_PLATFORM_URL="";
	private static $_currentAppKey="";
	private static $_currentAppLabel="";
	
	//array("34"=>array("recommd"=>1),"343"=>array("recommd"=>0),"2222"=>array("recommd"=>1)..)
	private static $userpuidFunctionstatusMap=array();
	
	/**
	 * 
	 * @return multitype:
	 */
	private static function _loadUserpuidFunctionstatusMap(){
		$rows=\Yii::$app->db->createCommand("select * from user_app_function_status")->queryAll();
		self::$userpuidFunctionstatusMap=array();
		foreach($rows as $row){
			$puid=$row["puid"];
			$functionKey=$row["function_key"];
			if (!isset(self::$userpuidFunctionstatusMap[$puid])) self::$userpuidFunctionstatusMap[$puid]=array();
			self::$userpuidFunctionstatusMap[$puid][$functionKey]=$row["status"]; //status --0 表示关闭; 1表示开启; 默认是开启			 
		}
		return self::$userpuidFunctionstatusMap;
	}

	/**
	 * 查看指定puid用户的指定的功能点是否开启
	 * @param  $puid
	 * @param  $functionKey ---- 功能点的标示字符串  如推荐商品可以是 recommend_product
	 * @return 0 或者1-----  0 表示关闭; 1表示开启;
	 * 如果没有记录也是返回0
	 */
	public static function getFunctionstatusByPuidKey($puid,$functionKey){
		if (count(self::$userpuidFunctionstatusMap)==0){
			self::_loadUserpuidFunctionstatusMap();
		}
		if (!isset(self::$userpuidFunctionstatusMap[$puid])) return 0;
		if (!isset(self::$userpuidFunctionstatusMap[$puid][$functionKey])) return 0;
		
		return self::$userpuidFunctionstatusMap[$puid][$functionKey];
	}
	
	/**
	 * 获取所有用户的功能点开启情况
	 * @return
	 * array("34"=>array("recommd"=>1),"343"=>array("recommd"=>0),"2222"=>array("recommd"=>1)..) 
	 */
	public static function getAllUserpuidFunctionstatusMap(){
		if (count(self::$userpuidFunctionstatusMap)==0){
			self::_loadUserpuidFunctionstatusMap();
		}
		return self::$userpuidFunctionstatusMap;
	}

	/**
	 * 开启指定用户的指定功能点
	 * @param  $puid  ---用户主账号id
	 * @param  $functionKey ---- 功能点的标示字符串  如推荐商品可以是 tracker_recommend
	 * @return true or false
	 */
	public static function turnOnUserFunction($puid,$functionKey){
		//防止该函数在同一个进程被多次访问，所以把值也放到内存中
		if (isset(self::$userpuidFunctionstatusMap[$puid]) and isset(self::$userpuidFunctionstatusMap[$puid][$functionKey])){
			if (self::$userpuidFunctionstatusMap[$puid][$functionKey]==1) return true;
		}
		
		if (!isset(self::$userpuidFunctionstatusMap[$puid])) self::$userpuidFunctionstatusMap[$puid]=array();
		self::$userpuidFunctionstatusMap[$puid][$functionKey]=1;
		
		
		$userFucntion=UserAppFunctionStatus::find()->where(["puid"=>$puid])->andWhere(["function_key"=>$functionKey])->one();
		$nowTimeStr=date("Y-m-d H:i:s");
		if ($userFucntion===null){
			$userFucntion=new UserAppFunctionStatus;
			$userFucntion->create_time=$nowTimeStr;
			$userFucntion->update_time=$nowTimeStr;
			$userFucntion->puid=$puid;
			$userFucntion->function_key=$functionKey;
			$userFucntion->status=1;//开启
			$ret=$userFucntion->save(false);
			
			if ($functionKey==="tracker_recommend"){ //打开推荐商品的时候需要同时触发后台job程序
				return UserBGJobControllHelper::createOrActivateBgJobForUser($puid, $functionKey);
			}
			return $ret;
		}

		if ($userFucntion->status===1) return true;
		
		$userFucntion->update_time=$nowTimeStr;
		$userFucntion->status=1;//开启		
		$ret=$userFucntion->save(false);
		if ($functionKey==="tracker_recommend"){ //打开推荐商品的时候需要同时触发后台job程序
			return UserBGJobControllHelper::createOrActivateBgJobForUser($puid, $functionKey);
		}
		return $ret;
		
	}
	
	/**
	 * 关闭指定用户的指定功能点
	 * @param  $puid ---用户主账号id
	 * @param  $functionKey ---- 功能点的标示字符串  如推荐商品可以是 recommend_product
	 * @return true or false
	 */
	public static function turnOffUserFunction($puid,$functionKey){
		//防止该函数在同一个进程被多次访问，所以把值也放到内存中
		if (isset(self::$userpuidFunctionstatusMap[$puid]) and isset(self::$userpuidFunctionstatusMap[$puid][$functionKey])){
			if (self::$userpuidFunctionstatusMap[$puid][$functionKey]==0) return true;
		}
		
		if (!isset(self::$userpuidFunctionstatusMap[$puid])) self::$userpuidFunctionstatusMap[$puid]=array();
		self::$userpuidFunctionstatusMap[$puid][$functionKey]=0;
		
		
		$userFucntion=UserAppFunctionStatus::find()->where(["puid"=>$puid])->andWhere(["function_key"=>$functionKey])->one();
		$nowTimeStr=date("Y-m-d H:i:s");
		if ($userFucntion===null) return true;

		if ($userFucntion->status===0) return true;
		
		$userFucntion->update_time=$nowTimeStr;
		$userFucntion->status=0;//关闭		
		$ret=$userFucntion->save(false);
		if ($functionKey==="tracker_recommend"){ //打开推荐商品的时候需要同时触发后台job程序
			return UserBGJobControllHelper::unactivateBgJobForUser($puid, $functionKey);
		}
		return $ret;
	}
	
	
	
	
	
	
	/**
	 * 获取当前页面对应的app key
	 * 如果当前访问url路径不属于任何app， 那么app key为空
	 * @return string
	 */
	public static function getCurrentAppKey(){
		return self::$_currentAppKey;
	}
    /**
     * 设置当前页面的app key
     * @param unknown $appKey
     */	
	public static function setCurrentAppKey($appKey){
		if ($appKey==""){
			self::$_currentAppKey="";
			self::$_currentAppLabel="";
			return;
		}
			
	     $appInfo=AppInfo::find()->where(["key"=>$appKey])->one();
	     if ($appInfo===null) return;
	     self::$_currentAppKey=$appKey;
	     self::$_currentAppLabel=$appInfo->name;
	     
	}

	/**
	 * 获取当前页面对应的app key的label（name）
	 * 如果当前访问url路径不属于任何app， 那么返回为空
	 * @return string
	 */	
	public static function getCurrentAppLabel(){
		return self::$_currentAppLabel;
	}

	
	
	
	
	/**
	 * 获取该用户已经启用的app的key列表
	 * @return   array("ebay_listing","order")
	 * 如果没有已启用app，就返回array()
	 */
	public static function getActiveAppKeyList(){
		return AppHelper::getActiveAppKeyList();
	}
	
	/**
	 * 获取默认选中的app列表
	 * @return array(array("name"=>"","key"=>"",...)....)
	 */
	public static function getDefaultAppList(){
		$appList=AppInfo::find()->where(["is_default_chosen"=>'Y'])->asArray()->all();
		return $appList;
	}


	/**
	 * 获取全局所有的app列表信息
	 * @return array(array("name"=>"","key"=>"",...)....)
	 */
	public static function getAllAppList(){
		$appList=AppInfo::find()->asArray()->all();
		return $appList;
	}	
	
	/**
	 * 指定需要安装的app key列表	 
	 * @param $appKeyList -----  key的列表 如 ：  array("ebay_listing","order")
	 * @return  true or false
	 */
	public static function installFromAppKeyList($appKeyList){
		
	//	\Yii::info("installFromAppKeyList appKeyList:".print_r($appKeyList,true),"file");
 
		
		foreach($appKeyList as $appKey){
			AppHelper::installOrActivateApp($appKey);
		}
		
		//比较已经安装的app和要求安装的app
		$totalInstalledAppKeyList=AppHelper::getInstalledAppKey();
		foreach($appKeyList as $appKey){
			if (!in_array($appKey,$totalInstalledAppKeyList)){
				//有异常
				\Yii::error("$appKey should be installed as requested","file");
				return false;
			}
			
		}
		return true;
		
	}	
	/**
	 * 获取module和app key的对应关系
	 * @return array("purchase"=>"purchase" ,.....)
	 */
	public static function getModuleAppkeyMap(){
		$moduleAppMap=array();
		$appInfoList=AppInfo::find()->all();
		foreach($appInfoList as $appInfo){
			$modules=$appInfo->modules;
			$modulesArr=explode(',',$modules);
			foreach($modulesArr as $module){
				$moduleAppMap[$module]=$appInfo->key;				
			}
		}
		return $moduleAppMap;
		
	} 
	
	/**
	 * 检查指定的app是否开启
	 * @param  $appKey ---  app的key。  如：   订单处理app的key是  order
	 *
	 * @return true 或 false。         true表示已启用
	 */
	public static function checkAppIsActive($appKey){
		$userApp=UserAppInfo::find()->where(['key'=>$appKey,'is_active'=>'Y'])->one();
		if ($userApp===null) return false;
		return true;
	}	
	
	
	/**
	 * 用户app安装向导---根据用户回答问题的答案，返回对应的推荐的app key列表
	 * @params
	 * $answerList---  array(array("ebay","amazon"),array("other"))
	 * 举例： 这里是array("ebay","amazon")是第一个问题的答案，array("other")是第二个问题的答案。
	 * 
	 * @return  array("ebay_listing","product")
	 */
	public static function getAdvisedAppListFromAnswer($answerList){
		return array("ebay_listing","product");
	}
	

	/**
	 * 获取指定用户，指定app key的最近使用时间.  (目前这里主要是后台调用，所以需要指定puid)
	 * @param  $puid ---- 用户saas 主账号id
	 * @param  $appKey
	 *
	 * @return "2015-03-16 11:11:11"
	 * 如果指定用户没有使用过该key，返回false。
	 */
	public static function getUserAppLastUseTime($puid,$appKey){
		$appUserStatistic=AppUserStatistic::find()->where(["puid"=>$puid,"key"=>$appKey])->one();
		if ($appUserStatistic===null) return false;
		return $appUserStatistic->update_time;
	
	}

	// 获取当前 app Frontend host url 
	// return boolean | string 
	public static function getFrontendUrl($appName=null){
		if(!isset(\Yii::$app->params["appHost"]) || !isset(\Yii::$app->params["appHost"])){
			return false;
		}
		
		if(empty($appName))
			$appName = \Yii::$app->params["appName"];
		
		if(!isset(\Yii::$app->params["appHost"][$appName.'_frontend'])){
			return false;
		}
		
		return \Yii::$app->params["appHost"][$appName.'_frontend'];
	}
	
	// 获取当前 app Backend host url
	// return boolean | string
	public static function getBackendUrl($appName=null){
		if(!isset(\Yii::$app->params["appHost"]) || !isset(\Yii::$app->params["appHost"])){
			return false;
		}
		
		if(empty($appName))
			$appName = \Yii::$app->params["appName"];
		
		if(!isset(\Yii::$app->params["appHost"][$appName.'_backend'])){
			return false;
		}
		
		return \Yii::$app->params["appHost"][$appName.'_backend'];
	}
	
	// 获取 从不同app source 登录 的不同跳转链接
	// @todo 新添加app时 ，如果要登录直接跳到app页面，需要在这里添加 appName=>链接
	public static function getLoginRedirectUri($appName=null){
		if(empty($appName) || "v2" == $appName || "erp" == $appName){
			return "#"; // site/directlogin action 判断这个goHome
		}
		
		if("tracker" == $appName)
			return "#"; // 由于目前登录到v2 没有跳转到 tracker模块的要求
		
		if("customer" == $appName)
			return "/message/all-customer/customer-list";

		if("kandeng" == $appName)
			return "/listing/ebayitem/list";// ebay 刊登还在分支中未合并到主线， 所以想tracker那样 跳转到ebaylisting.littleboss.com //dzt20150917 ebay刊登已合并到主干
		
		if("listing-lazada" == $appName)
			return "/listing/lazada-listing/publish";
		
		if("listing-linio" == $appName)
			return "/listing/linio-listing/publish";
		
		if("listing-jumia" == $appName)
			return "/listing/jumia-listing/publish";
		
		if("listing-wish" == $appName)
			return "/listing/wish/wish-list";
		
		if("listing-ensogo" == $appName)
			return "/listing/ensogo-offline/ensogo-post";
	 
		if("cuikuan" == $appName)
			return "/assistant/rule/list";
		
		if("haoping" == $appName)
			return "/comment/comment/rule";
		
		if("oms-aliexpress" == $appName)
			return "/order/aliexpressorder/aliexpresslist";
		
		if("oms-wish" == $appName)
			return "/order/wish-order/list";
		
		if("oms-ensogo" == $appName)
			return "/order/ensogo-order/list";
		
		if("oms-amazon" == $appName)
			return "/order/amazon-order/list";
		
		if("oms-dhgate" == $appName)
			return "/order/dhgate-order/list";
		
		if("oms-ebay" == $appName)
			return "/order/order/listebay";
		
		if("oms-lazada" == $appName)
			return "/order/lazada-order/list";
		
		if("oms-linio" == $appName)
			return "/order/linio-order/list";
		
		if("oms-jumia" == $appName)
			return "/order/jumia-order/list";
		
		if("oms-cdiscount" == $appName)
			return "/order/cdiscount-order/list";
		
		if("oms-priceminister" == $appName)
			return "/order/priceminister-order/list";
		
		if("gozens" == $appName)
			return "#";
		
		if("terminator-cdiscountfollow" == $appName)
			return "#";
		
	}
	
	// 过渡方案，显示所有菜单，合并所有app 菜单及 order 菜单 
	public static function genMenuWithLv2Menu(){
		$originItemsArr=\Yii::$app->params["menu"];
		
		$onlyLv1Items = array();
		$appItems = array();
		$orderItems = array();
		$showPlatforms = PlatformAccountApi::getAllPlatformBindingSituation();

		// 生成app一级目录数组  和order 一级目录
		foreach ($originItemsArr as $label=>$item){
			// 根据平台绑定情况决定是否显示 属于相关平台的菜单。 要设置menu 的"platform" 字段
			if (isset($item["platform"]) && array_key_exists($item["platform"], $showPlatforms) && $showPlatforms[$item["platform"]] == false){
				continue;
			}
			
			if(isset($item['app']))
				$appItems[$label] = $item;
// 			else if(isset($item['platform'])) // dzt20150915 oms 菜单都作为第一级菜单列出来，放到最左边（菜单priority越大，优先级越高越靠左）
// 				$orderItems[$label] = $item;
			else
				$onlyLv1Items[$label] = $item;
		}
		
		$lv1Items = $onlyLv1Items;
		if(!empty($appItems)){
			$lv1Items["app"] = array("priority"=>"0");
		}
		if(!empty($orderItems)){
			$lv1Items["平台订单"] = array("priority"=>"1");// 平台订单在前
		}
		
		$sortedLv1Items = self::_sortMenu($lv1Items);
		$sortedAppItems = self::_sortMenu($appItems);
		$sortedOrderItems = self::_sortMenu($orderItems);
		
		$finalMenuItems = array();
		foreach ($sortedLv1Items as $label=>$lv1Item){
			if("app" == $label){
				$appItems = array();
				foreach ($sortedAppItems as $lv2Label=>$lv2AppItems){
					if(!isset($lv2AppItems['url']))
						$lv2AppItems['url'] = "#";
					// dzt20150917 ebay刊登已合并到主干
// 					if("ebay刊登" == $lv2Label){// 由于ebay刊登还在分支上 未合并到v2，所以需要直接跳转到ebaylisting.littleboss.com
// 						$appItems[] = '<li><a href="http://ebaylisting.littleboss.com/">'.TranslateHelper::t($lv2Label).'</a></li>';
// 					}else{
						$appItems[] = array('label' =>TranslateHelper::t($lv2Label), 'url' =>  [$lv2AppItems['url']]);
// 					}
				}
				$finalMenuItems[] = array('label'=>TranslateHelper::t($label),'items'=>$appItems ,'options' => ['class'=>'lv2-menu-container']);
				continue;
			}
			
			if("平台订单" == $label){
				$orderItems = array();
				foreach ($sortedOrderItems as $lv2Label=>$lv2OrderItems){
					if(!isset($lv2OrderItems['url']))
						$lv2OrderItems['url'] = "#";
					$orderItems[] = array('label' =>TranslateHelper::t($lv2Label), 'url' =>  [$lv2OrderItems['url']]);
				}
				$finalMenuItems[] = array('label'=>TranslateHelper::t($label),'items'=>$orderItems ,'options' => ['class'=>'lv2-menu-container']);
				continue;
			}
			
			if(!isset($lv1Item['url']))
				$lv1Item['url'] = "#";
			$finalMenuItems[] = array('label' =>TranslateHelper::t($label), 'url' =>  [$lv1Item['url']]);
		}
		
		return $finalMenuItems;
	}
	
	// 针对 genMenuWithLv2Menu function 排序目录 ， priority越大，优先级越高
	private static function _sortMenu($originItems){
		if(empty($originItems)){
			return array();
		}
		
		$priorityLabelMap=array();
		foreach($originItems as $label=>$itemInfo){
			if (!isset($itemInfo["priority"]))  $itemInfo["priority"] = 0;
			$itemPriority = $itemInfo["priority"];
			if (!isset($priorityLabelMap[$itemPriority])){
				$priorityLabelMap[$itemPriority] = array();
			}
			$priorityLabelMap[$itemPriority][] = $label;
		}
		//print_r($priorityLabelMap);
		
		//2.2 排序
		$priorityList=array_keys($priorityLabelMap);
		rsort($priorityList);//倒叙排序
		
		$sortedItems = array();
		foreach($priorityList as $priority){
			$labelList=$priorityLabelMap[$priority];
			foreach($labelList as $label){
				$sortedItems[$label] = $originItems[$label];
			}
		}
		
		return $sortedItems;
	}
	
	// 获取发送 重置密码链接 backend url
	public static function getResetTokenBackendUrl($appName=null){
		$source = "v2";// 过渡方案，由于有些app 还没有backend 将所有  重置密码链接 指向v2
		if(!isset(\Yii::$app->params["appHost"]) || !isset(\Yii::$app->params["appHost"])){
			return false;
		}
		
		if(empty($appName))
			$appName = \Yii::$app->params["appName"];
		
		if(!isset(\Yii::$app->params["appHost"][$appName.'_backend'])){
			return false;
		}
		return \Yii::$app->params["appHost"][$appName.'_backend'];
		
	}
	
	// 前台登录/注册等 接口传到backend的source与 module name 匹配
	// @param  $source ---- 前台登录/注册等 接口传到backend的source
	// @return string | array
	// @todo 新添加app时 ，需要在这里添加 source 与module name 的映射
	public static function getSourceModuleNameMapping($source=NUll){
		$source_module_map = array(
				'v2'	 		=>'eagle_v2',
				'tracker'		=>'tracker',
				'erp'			=>'erp',// 功能模块
				'kandeng'		=>'listing_ebay',// 刊登管理，目前是ebay刊登 。// dzt20150917 把kandeng修改为listing_ebay
				'listing-lazada'=>'listing_lazada',
				'listing-linio'	=>'listing_linio',// listing-linio为前端source 以及产品前后端url source名，listing_linio为action log 模块名
				'listing-jumia'	=>'listing_jumia',
				'listing-wish'	=>'List-wish',
				'listing-ensogo'=>'listing_ensogo',
				'customer'		=>'message',// 客服管理
				'cuikuan'		=>'alicuikuan',// 速卖通催款助手
				'haoping'		=>'alihaoping',// 好评助手
				'oms-aliexpress'=>'oms-aliexpress',
				'oms-wish'		=>'oms-wish',
				'oms-ensogo'	=>'oms-ensogo',
				'oms-amazon'	=>'oms-amazon',
				'oms-dhgate'	=>'oms-dhgate',
				'oms-ebay'		=>'oms-ebay',
				'oms-lazada'	=>'oms-lazada',
				'oms-linio'		=>'oms-linio',
				'oms-jumia'		=>'oms-jumia',
				'oms-cdiscount'	=>'oms-cdiscount',
				'oms-priceminister'=>'oms-priceminister',
				'ppsupplier'	=>'eagle_v2',// 这不算是app 只是一个着落页，所以登录暂时算 eagle_v2模块的，@todo 后面要归到自建站
				'gozens'		=>'eagle_v2',// 归为eagle_v2 道理同上 
	'terminator-cdiscountfollow'=>'eagle_v2',// 归为eagle_v2 道理同上
		);
		
		if(!empty($source) && isset($source_module_map[$source])){
			return $source_module_map[$source];
		}else{
			return $source_module_map;
		}
	}
	
	/**
	 * 获取账号绑定的菜单的data
	 * 返回array($platformUrl,TranslateHelper::t('平台绑定'));
	 */
	public static function getPlatformMenuData(){
		
		$directLoginUrlSuffix="/platform/platform/all-platform-account-binding";
		
		$platformUrl= self::$BIND_PLATFORM_URL.$directLoginUrlSuffix;
		
		
		return array($platformUrl,TranslateHelper::t('平台授权'));
	}
	
	
	/**
	 * 获取账号绑定的菜单的html
	 */
	public static function getPlatformMenu(){
		
		list($platformUrl,$label)=self::getPlatformMenuData();
		
		$menuHtml = '<li><a href="'.$platformUrl.'" target="_blank">'.$label.'</a></li>';
		
		return $menuHtml;
		
	}
	
}
