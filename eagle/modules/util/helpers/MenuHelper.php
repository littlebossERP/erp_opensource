<?php

namespace eagle\modules\util\helpers;

use eagle\modules\util\models\ConfigData;
use eagle\modules\app\models\UserAppInfo;
use eagle\modules\app\apihelpers\AppApiHelper;
use eagle\modules\platform\apihelpers\PlatformAccountApi;
use eagle\modules\platform\helpers\PlatformHelper;
use eagle\modules\permission\apihelpers\UserApiHelper;
use eagle\modules\tool\helpers\MirroringHelper;

/**
 +------------------------------------------------------------------------------
 * MenuHelper
 +------------------------------------------------------------------------------
 * @package		Helper
 * @subpackage  Exception
 * @author		xjq
 * @version		1.0
 +------------------------------------------------------------------------------
 */
class MenuHelper {
	
	private static $menuItemsArr=array();
	
	/**
	 * 根据原始菜单配置arr，配合当前app是否开启以及参数的配置，生成最终呈现给用户的菜单数据
	 * @param  $itemsArr  -- 根据原始菜单配置arr
	 * 
	 * @return $finalItemsArr  返回整理后的菜单配置arr
	 */	
	private static function _generateFinalMenuItemsArr($itemsArr){
		$finalItemsArr=array();
		$showPlatforms = PlatformAccountApi::getAllPlatformBindingSituation();
		
		//1. 整理url和priority属性；处理每个item下的的submenu信息（递归获取submenu的信息）；
		$afterSubHandledArr=array();
		foreach($itemsArr as $itemLabel=>$itemInfo){
			//$item  ----  "ebay刊登" =>["priority"=>3]		
			
			//1.1 如果app的菜单的话(只会在一级菜单)，需要先判断该app是否已经启用
			if (isset($itemInfo["app"])){
				$appKey=$itemInfo["app"];
				$userApp=UserAppInfo::find()->where(["key"=>$appKey,"is_active"=>'Y'])->one();
				if ($userApp===null) continue;  //没启用的话，就不显示
			}
			
			// 由于菜单目前最终形式未定，这里添加一个过渡方案 ，根据平台绑定情况决定是否显示 属于相关平台的菜单。 要设置menu 的"platform" 字段
			if (isset($itemInfo["platform"]) && array_key_exists($itemInfo["platform"], $showPlatforms) && $showPlatforms[$itemInfo["platform"]] == false){
				continue;
			}
			
			//1.2 如果showCond的条件不满足就显示该menu item					
			if (isset($itemInfo["showCond"]) and !empty($itemInfo["showCond"])){
				$showConditions=$itemInfo["showCond"];
				//依赖某个app的开启
			/*	if (isset($showConditions["appDepend"]) and !empty($showConditions["appDepend"])){					
					$appList=$showConditions["appDepend"];
					foreach($appList as $appKey){
						$userApp=UserAppInfo::find()->where(["key"=>$appKey,"is_active"=>'Y'])->one();
						if ($userApp===null){
							continue 2;
						}						
					}					
				}*/
				
				//依赖某些参数的值
				if (isset($showConditions["configDepend"]) and !empty($showConditions["configDepend"])){
					$configDepends=$showConditions["configDepend"];
					foreach($configDepends as $configPath=>$value){						
						$configValue=ConfigHelper::getConfig($configPath);
						if ($configValue===null) continue 2;
						if ($configValue<>$value) continue 2;						
					}
				}
				
			}
			
			//1.3 递归获取submenu的信息
			$subMenuItems=array();
			if (isset($itemInfo["subMenu"])){
				//深度优先
				$subMenuItems=self::_generateFinalMenuItemsArr($itemInfo["subMenu"]);
				
			}
			
			//1.4 整理url和priority属性
			$tempItemArr=array();
			$tempItemArr["priority"]=0; //数字越大，优先级越高
			if (isset($itemInfo["priority"]))  $tempItemArr["priority"]=$itemInfo["priority"];
			$tempItemArr["url"]="#"; //#  表示该菜单item不能点击
			if (isset($itemInfo["url"]))  $tempItemArr["url"]=$itemInfo["url"];
			if (isset($itemInfo["matchUrl"]))  $tempItemArr["matchUrl"]=$itemInfo["matchUrl"];
			if (isset($itemInfo["app"]))  $tempItemArr["app"]=$itemInfo["app"];
			if (count($subMenuItems)>0)  $tempItemArr["subMenu"]=$subMenuItems; 
			
			$afterSubHandledArr[$itemLabel]=$tempItemArr;
		}
	   
		//2. 根据优先级排序
		//2.1 获取 priority => array(label,,,,)
		$priorityLabelMap=array();
		foreach($afterSubHandledArr as $label=>$itemInfo){
			$itemPriority=$itemInfo["priority"];
			if (!isset($priorityLabelMap[$itemPriority])){
				$priorityLabelMap[$itemPriority]=array();
			}
			$priorityLabelMap[$itemPriority][]=$label;			
		}
		//print_r($priorityLabelMap);
		
		//2.2 排序
		$priorityList=array_keys($priorityLabelMap);
		rsort($priorityList);//倒叙排序
		
		foreach($priorityList as $priority){
			$labelList=$priorityLabelMap[$priority];
			foreach($labelList as $label){				
				$finalItemsArr[TranslateHelper::t($label)]=$afterSubHandledArr[$label];
			}
			
		}
		return $finalItemsArr;
		
		
	}
	
	// 检查该menuItem 是否有权限 显示
	private static function _checkPermission(&$menuItem){
		//1. 先判断模块级别的权限
		//  menu配置文件的permission的值跟 权限模块的key的对应关系
		$menuModulePermisssionMap=array(
		 "order"=>"order",
		 "purchase"=>"purchase",
		 "catalog"=>"catalog",
		 "message"=>"message",
		 "tracking"=>"tracking",
		 "delivery"=>"delivery",
		 "inventory"=>"inventory"		 
		);		
		
	    if (isset($menuItem["permission"]) and isset($menuModulePermisssionMap[$menuItem["permission"]])){
			$permissonModule=$menuModulePermisssionMap[$menuItem["permission"]];				
			if (!empty($permissonModule) and UserApiHelper::checkModulePermission($permissonModule)===false){
				
				//有打包权限，没有发货权限，也显示发货模块，lrq20180322
				if($permissonModule == 'delivery'){
					if(UserApiHelper::checkModulePermission('delivery_edit') === true){
						return true;
					}
				}
				
				return  false;
			}
		}	

		//2. 再判断平台级别的权限
		$platformPermisssionMap=array(
		'ebay'=>'ebay', 
		'aliexpress'=>'aliexpress', 
		'amazon'=>'amazon',
		'dhgate'=>'dhgate',
		'cdiscount'=>'cdiscount',
		'lazada'=>'lazada',
		'linio'=> 'linio',
		'jumia'=> 'jumia',
		'priceminister'=> 'priceminister',
		'wish'=> 'wish',  
		'bonanza'=>'bonanza',
		'rumall'=>'rumall'
		);	
		if (isset($menuItem["platform"])){
			if (isset($platformPermisssionMap[$menuItem["platform"]])){
				$platform=$platformPermisssionMap[$menuItem["platform"]];
			}else{
				$platform=$menuItem["platform"];
			}
			if (UserApiHelper::checkPlatformPermission($platform)===false){
				return false;
			}
		}
			
        return true;		
	}
	
	/**
	 * 前端获取获取上方菜单信息！！！
	 * @return array("menuData"=>$finalItemsArr2,"isFirstMenu"=>1,"appName"=>$appName);
	 *  finalItemsArr2---菜单数据
	 *  isFirstMenu---0或者1，  是否使用第一套菜单
	 *  appName-----当前的app 名称，  如： v2 ，tracker
	 */	
	public static function getTopMenu(){
	    $menuItemsArr=\Yii::$app->params["topmenu"];
	    $finalItemsArr=array();
	    $showPlatforms = PlatformAccountApi::getAllPlatformBindingSituation();
	    
/*	   $showPlatforms = array(
	    		'ebay'=>false,
	    		'aliexpress'=>false,
	    		'wish'=>false,
	    		'amazon'=>false,
	    		'dhgate'=>false,
	    		'lazada'=>false,
	    		'cdiscount'=>false,
	    		'linio'=>false,
	    		'jumia'=>false,
	    );*/
		

		

	    foreach($menuItemsArr as $menuLabel=>$menuItem){
	    	//如果为测试用的账号就不受平台绑定限制
	    	$test_userid=MirroringHelper::$test_userid;

	        // 由于菜单目前最终形式未定，这里添加一个过渡方案 ，根据平台绑定情况决定是否显示 属于相关平台的菜单。 要设置menu 的"platform" 字段
	        if (isset($menuItem["platform"]) && array_key_exists($menuItem["platform"], $showPlatforms)  && $showPlatforms[$menuItem["platform"]] === false && !in_array(\Yii::$app->subdb->getCurrentPuid(),$test_userid['yifeng'])){
	            continue;	            
	        }
	
			// 检查该menuItem 是否有权限 显示
			if (self::_checkPermission($menuItem)===false){
				continue;
			}
			
	        $firstItem=array();
	        if (isset($menuItem["subMenu"])){
	            $tempItem=array();
	            foreach($menuItem["subMenu"] as $secondLabel=>$secondMenuItem){
	                // 由于菜单目前最终形式未定，这里添加一个过渡方案 ，根据平台绑定情况决定是否显示 属于相关平台的菜单。 要设置menu 的"platform" 字段
	                if (isset($secondMenuItem["platform"]) && array_key_exists($secondMenuItem["platform"], $showPlatforms)  && $showPlatforms[$secondMenuItem["platform"]] === false && !in_array(\Yii::$app->subdb->getCurrentPuid(),$test_userid['yifeng'])){
	                    continue;
	                }
	               
			    	// 检查该menuItem 是否有权限 显示
			        if (self::_checkPermission($secondMenuItem)===false){
				         continue;
			        }
			        
	                $tempItem[$secondLabel]=$secondMenuItem;
	            }
	            //if (count($tempItem)==0) continue;
	            if (count($tempItem)>0) {
	               $firstItem["subMenu"]=$tempItem;
	            }
	        }
	        
	        $firstItem["url"]=$menuItem["url"];
	        if (isset($menuItem["matchUrl"])){
	        	$firstItem["matchUrl"]=$menuItem["matchUrl"];	        	
	        }
	        if (isset($menuItem["menuKey"])){
	        	$firstItem["menuKey"]=$menuItem["menuKey"];
	        }
	         
	        
	        $finalItemsArr[$menuLabel]=$firstItem;
	    }
	    
	    try{
	    	//判断子账号是否有权限查看，lrq20170829
	    	$isMainAccount = UserApiHelper::isMainAccount();
	    	if(!$isMainAccount){
			    //oms设置
			    if(!\eagle\modules\permission\apihelpers\UserApiHelper::checkSettingModulePermission('oms_setting')){
			    	unset($finalItemsArr['设置']['subMenu']['订单设置']);
			    }
			    //仓库设置
			    if(!\eagle\modules\permission\apihelpers\UserApiHelper::checkSettingModulePermission('warehouse_setting')){
			    	unset($finalItemsArr['设置']['subMenu']['仓库设置']);
			    }
			    //物流发货设置
			    if(!\eagle\modules\permission\apihelpers\UserApiHelper::checkSettingModulePermission('delivery_setting')){
			    	unset($finalItemsArr['设置']['subMenu']['选择运输服务']);
			    	unset($finalItemsArr['设置']['subMenu']['常用报关信息']);
			    	unset($finalItemsArr['设置']['subMenu']['发货设置']);
			    }
			    //商品设置
			    if(!\eagle\modules\permission\apihelpers\UserApiHelper::checkSettingModulePermission('catalog_setting')){
			    	unset($finalItemsArr['设置']['subMenu']['商品设置']);
			    }
			 
			    //图片库
			    if(!\eagle\modules\permission\apihelpers\UserApiHelper::checkModulePermission('image')){
			    	unset($finalItemsArr['独立应用']['subMenu']['图片库']);
			    }
			    //Cdiscount跟卖终结者
			    if(!\eagle\modules\permission\apihelpers\UserApiHelper::checkModulePermission('cdiscount')){
			    	unset($finalItemsArr['独立应用']['subMenu']['Cdiscount跟卖终结者']);
			    }
			    //aliexpress催款助手
			    if(!\eagle\modules\permission\apihelpers\UserApiHelper::checkPlatformPermission('aliexpress')){
			    	unset($finalItemsArr['独立应用']['subMenu']['AliExpress催款助手']);
			    }
			    
			    if(empty($finalItemsArr['商品管理']['subMenu'])){
			    	unset($finalItemsArr['商品管理']);
			    }
			    else if(empty($finalItemsArr['仓库']['subMenu'])){
			    	unset($finalItemsArr['仓库']);
			    }
	    	}
	    }
	    catch(\Exception $ex){}
	    
	 //   \Yii::info("my_top_menu:".print_r($finalItemsArr,true),"file");
	    
	    //对订单菜单特殊处理
	    $finalItemsArr=self::_topmenuOrderAdjust($finalItemsArr);

	    // 如果是CD的郑小姐，可以看到  "CD申请审核" 的菜单
	    if (PlatformHelper::cdApprovalAuthorized()){
	    	// platform/platform/cdiscount-check-page
	    	$finalItemsArr["CD审核"]=array("url"=>"/platform/platform/cdiscount-check-page",
	    			"priority"=>"1","matchUrl"=>[["platform","platform","cdiscount-check-page"]]);
	    }	    
	    
	    //根据当前的url判断那个菜单命中
	    $finalItemsArr=self::_topmenuMatch($finalItemsArr);
	    

	    
	    
	    $appName = \Yii::$app->params["appName"];// 当前 app 名称 ， 这个每个app backend都必须配置在 config/param.php
	    $result=array("menuData"=>$finalItemsArr,"isFirstMenu"=>1,"appName"=>$appName);
	    
	  //  \Yii::info("my_top_menu_final:".print_r($result,true),"file");
	    
	    if ($appName<>"v2"){
	    	$result["isFirstMenu"]=0;
	    }
	    
	    
	    
	    return $result;	    
	    
	}		
	
	/**
	 * 根据原始菜单配置arr，配合当前app是否开启以及参数的配置，生成最终呈现给用户的菜单数据
	 * @param  $originItemsArr  -- 根据原始菜单配置arr
	 * @return 
	 * 
	 */
	public static function getFinalMenuItemsArr(){
		if (count(self::$menuItemsArr)==0){
			$originItemsArr=\Yii::$app->params["menu"];
			self::$menuItemsArr=self::_generateFinalMenuItemsArr($originItemsArr);
		}
		return self::$menuItemsArr;	
	}
	
	
	private static function _itemMatch($menuItem,$currentModule,$currentController){
		if (isset($menuItem["matchUrl"])){
			foreach($menuItem["matchUrl"] as $matchUrl){
				if (  (count($matchUrl)==1 and  $matchUrl[0]==$currentModule)
				or  (count($matchUrl)==2 and  $matchUrl[0]==$currentModule and $matchUrl[1]==$currentController)){
					//找到匹配
					return true;
				}
					
			}
		}
		return false;
	}
	

	
	/**
	 * 订单管理或者app应用 菜单 调整  
	 */
	private static function _topmenuOrderAdjust($menuItemsArr){
		
		$newMenuItemsArr=array();
		list($platformUrl,$label)=AppApiHelper::getPlatformMenuData();
		// 尝试找指定matchUrl
		foreach($menuItemsArr as $menuLabel=>$menuItem){
			if (!isset($menuItem["menuKey"]) or ($menuItem["menuKey"]<>"order"  and $menuItem["menuKey"]<>"listing")){				
				$newMenuItemsArr[$menuLabel]=$menuItem;
				continue;				
			}
			
			//订单管理或者app应用 菜单
			if (!isset($menuItem["subMenu"])){
				$menuItem["url"]=$platformUrl;
				$menuItem["target"]='_blank';			
				$newMenuItemsArr[$menuLabel]=$menuItem;
				continue;
			}
			
			if ($menuItem["menuKey"]=="listing"){
				$newMenuItemsArr[$menuLabel]=$menuItem;
				continue;				
			}
			
			
			if ($menuItem["menuKey"]=="order"){
				$subMenu=$menuItem["subMenu"];
			//	if (count($subMenu)>=3){
					$newMenuItemsArr[$menuLabel]=$menuItem;
					continue;				
			//	}
			}
			foreach($subMenu as $subLabel=>$subMenuItem){
				$newMenuItemsArr[$subLabel]=$subMenuItem;
			}
			
		
		}
		return $newMenuItemsArr;
	
	}	
	
	/**
	 * 获取当前页面是属于哪个菜单
	 */
	private static function _topmenuMatch($menuItemsArr){
	
		$currentAppKey="";
		$currentMenuLabel="";
		//注意： 404页面  module controller action分别是 app-eagle site error
		$currentModule=\Yii::$app->controller->module->id;
		$currentController=\Yii::$app->controller->id;
		$currentAction=\Yii::$app->controller->action->id;
		if ($currentModule=="app-eagle" and $currentController=="site"){
			return $menuItemsArr;
		}
		
		
		// 尝试找指定matchUrl
		foreach($menuItemsArr as $menuLabel=>&$menuItem){		
			if ($menuLabel=="设置") continue;	
			
			if (isset($menuItem["subMenu"])){
				
				foreach($menuItem["subMenu"] as $secondLabel=>&$secondMenuItem){
					$ret=self::_itemMatch($secondMenuItem, $currentModule, $currentController);
					if ($ret===true){
					    
					    $menuItem["subMenu"][$secondLabel]["isMatch"]=true;
					    $menuItem["isMatch"]=true;
					    return $menuItemsArr;

					    
					    
					/*	//$secondMenuItem["isMatch"]=1;
						$menuItem["isMatch"]=1;

						$menuItem["url"]=$menuItem["subMenu"][$secondLabel]["url"];
						$matchedFirstLevelMenuLabel=$menuLabel;
						$matchedSecondeLevelMenuLabel=$secondLabel;						
						unset($menuItem["subMenu"][$secondLabel]);
						
						
						
						$newMenuArr=array();
						foreach($menuItemsArr as  $menuLabel2=>$menuItem2){
							if ($matchedFirstLevelMenuLabel<>$menuLabel2){
								$newMenuArr[$menuLabel2]=$menuItem2;
							}else{
								$newMenuArr[$matchedSecondeLevelMenuLabel]=$menuItem2;
							}
							
						}
						
						//$menuItemsArr[$secondLabel]=$secondMenuItem;
					//	unset($menuItem["subMenu"][$secondLabel]);
						//$menuLabel=$secondLabel;
					//	$menuItemsArr[$secondLabel]=$menuItem;
					//	unset($menuItemsArr[$menuLabel]);
						return $newMenuArr;  */
					}
				
				}
				continue;
			}
			
			
			if (isset($menuItem["matchUrl"])){
				$ret=self::_itemMatch($menuItem, $currentModule, $currentController);
				if ($ret===true){
				//	$menuItem["isMatch"]=1;
				    $menuItem["isMatch"]=true;
					return $menuItemsArr;
				}
			
			}
		}
		return $menuItemsArr;
				
	}
	
	/*
	 * 获取当前页面是属于哪个一级菜单。 获取当前页面是属于哪个一级菜单,并找到当前使用的app key！！！！！！！！
	 * 
	 * 一级菜单有2种：  app和非app菜单
	 * @params
	 * $menuItemsArr--- 菜单的array数据
	 * 
	 * @return array(currentMenuLabel,$currentAppKey)
	 * 当前url对应的一级菜单的名称，以及对应的app key。如果当前访问url路径不属于任何app， 那么app key为空        
	 */
	private static function _findMatchFirstLevelMenu($menuItemsArr){
		//判断当前页面是属于哪个一级菜单。 一级菜单有2种：  app和非app菜单
		$currentAppKey="";
		$currentMenuLabel="";
		//注意： 404页面  module controller action分别是 app-eagle site error
		$module=\Yii::$app->controller->module->id;  
		$controller=\Yii::$app->controller->id;
		$action=\Yii::$app->controller->action->id;
		if ($module=="app-eagle" and $controller=="site"){
			return array("","");
		}
		
		
		
		
		//1. 根据app和module的对应关系来匹配
	/*	$moduleAppkeyMap=AppApiHelper::getModuleAppkeyMap();
		$currentAppKey="";
		if (isset($moduleAppkeyMap[$module])){
			$currentAppKey=$moduleAppkeyMap[$module];
		}
		foreach($menuItemsArr as $menuLabel=>$menuItem){
			if (isset($menuItem["app"]) and $currentAppKey==$menuItem["app"]){
				$currentMenuLabel=$menuLabel;
				return $currentMenuLabel;
			}
		}*/
		
		//2. 尝试找指定matchUrl，
		foreach($menuItemsArr as $menuLabel=>$menuItem){
			if (isset($menuItem["matchUrl"])){
				foreach($menuItem["matchUrl"] as $matchUrl){
					$urlArr=explode("/", $matchUrl);
					if ((count($urlArr)==4 and $matchUrl=="/$module/$controller/$action")
					or (count($urlArr)==3 and $matchUrl=="/$module/$controller")
					or (count($urlArr)==2 and $matchUrl=="/$module")	){
						//命中
						if (isset($menuItem["app"])) $currentAppKey=$menuItem["app"];
						else $currentAppKey="";
						$currentMenuLabel=$menuLabel;
						return array($currentMenuLabel,$currentAppKey);
					}
				}
			}
		}

		//3 尝试找url的属性的匹配
		foreach($menuItemsArr as $menuLabel=>$menuItem){
			if ($menuItem["url"]=="#") continue;
			$matchUrl=$menuItem["url"];	
			$urlArr=explode("/", $matchUrl);
			if ((count($urlArr)==4 and $matchUrl=="/$module/$controller/$action")
			or (count($urlArr)==3 and $matchUrl=="/$module/$controller")
			or (count($urlArr)==2 and $matchUrl=="/$module")	){
				//命中
				if (isset($menuItem["app"])) $currentAppKey=$menuItem["app"];
				else $currentAppKey="";				
				$currentMenuLabel=$menuLabel;
				return array($currentMenuLabel,$currentAppKey);						
			}
		}
		
		return array("","");
	}
	
	/**
	 * 前端获取获取菜单信息！！！
	 * @return array($firstLevelhtmlArr,$currentSecondLevelMenuItems)
	 *  $firstLevelhtmlArr---菜单第一行左边的 一级菜单
	 *  $currentSecondLevelMenuItems -------  当前选中的一级菜单下的二级菜单。如果当前没有选中一级菜单，这里返回为空
	 */
	public static function getMenu(){	

		$module=\Yii::$app->controller->module->id;
		$controller=\Yii::$app->controller->id;
		$action=\Yii::$app->controller->action->id;
		
		//获取整理后的所有菜单信息
		$menuItemsArr=self::getFinalMenuItemsArr();
		
		
		//1.获取当前页面是属于哪个一级菜单,并找到当前使用的app key
	    list($currentMenuLabel,$currentAppKey)=self::_findMatchFirstLevelMenu($menuItemsArr);
	    //顺便设置到请求共享的app位置
	    if ($currentAppKey<>"") AppApiHelper::setCurrentAppKey($currentAppKey);
	    
		//2. 提取第一级菜单，以及该一级菜单下对应的二级菜单列表
		$firstLevelMenuItemsArr=array();
		$currentSecondLevelMenuItems=array();
		$hasGotCurrentMenu=false;
		foreach($menuItemsArr as $menuLabel=>$menuItem){
			if ($currentMenuLabel<>"" and $hasGotCurrentMenu===false and $currentMenuLabel==$menuLabel){
				$tempItemArr=array("label"=>$menuLabel,"url"=>$menuItem["url"],"is_active"=>true);
				$firstLevelMenuItemsArr[]=$tempItemArr;
				$hasGotCurrentMenu=true;
				if (isset($menuItem["subMenu"])) $currentSecondLevelMenuItems=$menuItem["subMenu"];
				continue;				
			}
	
			$tempItemArr=array("label"=>$menuLabel,"url"=>$menuItem["url"],"is_active"=>false);
			$firstLevelMenuItemsArr[]=$tempItemArr;				
		}
		
		//3. 根据菜单arr生成html
		$firstLevelhtmlArr=array();
		foreach ($firstLevelMenuItemsArr as $menuItem){
			$classStr="";			
			if ($menuItem['is_active']===true) $classStr=' class="active" '; 
			$itemHtml='  <li'.$classStr.'><a href="'.$menuItem["url"].'">'.$menuItem["label"].'</a></li>';
			$firstLevelhtmlArr[]=$itemHtml;
		}
		
				
		return array($firstLevelhtmlArr,$currentSecondLevelMenuItems);
		 
	}
	

	
	
	
}