<?php
namespace eagle\modules\permission\helpers;

use yii\data\Pagination;
use eagle\modules\util\helpers\ResultHelper;
use eagle\modules\util\helpers\TranslateHelper;
use eagle\models\UserInfo;
use eagle\modules\permission\models\UserBase;
use common\helpers\Helper_Array;
use eagle\modules\platform\apihelpers\PlatformAccountApi;
use eagle\modules\util\helpers\RedisHelper;
use eagle\modules\order\models\OdOrder;
use eagle\modules\permission\models\UserOperationLog;
use eagle\helpers\IndexHelper;
/*+----------------------------------------------------------------------
| 小老板
+----------------------------------------------------------------------
| Copyright (c) 2011 http://www.xiaolaoban.cn All rights reserved.
+----------------------------------------------------------------------
| Author: 韩兴鲁
+----------------------------------------------------------------------
| Create Date: 2014-01-30
+----------------------------------------------------------------------
 * @version		1.0
 +------------------------------------------------------------------------------
 */

/**
 +------------------------------------------------------------------------------
 * 物流方式模块业务逻辑类
 +------------------------------------------------------------------------------
 * @category	customer
 * @package		Helper/message
 */
class UserHelper
{
	
	private static $_allPemissionData=array();
	
	// 支持权限检验模块，其他不支持的，则可以认为是公共页面
	public static $modulesKeyNameMap = array(
			'order'=>"订单",
			'delivery'=>"发货",
			'catalog'=>"商品",
			'inventory'=>"仓库",
			'purchase'=>"采购",
			'message'=>"客服",
// 			'listing'=>"刊登",// 涉及到要其他同事配合，所以先不管
			'tracking'=>"物流跟踪助手",
			// 下面三个涉及到 如果平台没有选的话，也要过滤，所以暂时只以平台来判断，去掉模块判断
			'image'=>"图片库",
// 			'comment'=>"AliExpress好评助手",
// 			'assistant'=>"AliExpress催款助手",
			'cdiscount'=>"Cd跟卖终结者",
	);
	
	// 
	
	// 其他支持权限检验模块
	public static $ohtersKeyNameMap = array(
		"profix"=>"利润统计",
		"jumia_listing_import"=>"Jumia Excel导入",			
	);
	
	// 支持权限检验模块，其他不支持的，则可以认为是公共页面
	public static $SettingmodulesKeyNameMap = array(
		'oms_setting'=>"oms设置",
		'warehouse_setting'=>"仓库设置",
		'delivery_setting'=>"物流发货设置",
		'catalog_setting'=>"商品设置",
	);
	
	// 操作日志模块列表
	public static $OperationLogModules = array(
			'order' => "订单",
			'delivery' => "发货",
			'inventory' => "仓库",
			'catalog' => "商品",
			'purchase' => "采购",
			//'message' => "客服",
			'system' => '系统',
	);
	
	/**
	 +----------------------------------------------------------------------------------------------------------------------------------
	 * 获取子账号列表数据
	 +----------------------------------------------------------------------------------------------------------------------------------
	 * @access static
	 +----------------------------------------------------------------------------------------------------------------------------------
	 * @param $sort			排序key
	 * @param $order		排序order
	 +----------------------------------------------------------------------------------------------------------------------------------
	 * @return				信息数据列表
	 +----------------------------------------------------------------------------------------------------------------------------------
	**/
	public static function helpList($sort , $order)
	{
		try{
			$userID = \Yii::$app->user->id;
			$query = UserBase::find()->where(["and", "uid<>$userID", "puid=$userID"]);

			$pagination = new Pagination([
				'defaultPageSize' => 20,
				'totalCount' => $query->count(),
			]);
				
			$erpBaseUserList = $query
			->offset($pagination->offset)
			->limit($pagination->limit)
			->orderBy($sort.' '.$order)
			->all();
			
			$permissionArr = array();
			foreach ($erpBaseUserList as $user){
				$userPermissionJsonStr = UserHelper::getUserPemission($user->uid);
				$userPermission = json_decode($userPermissionJsonStr,true);
				$userPermissionTags = array('modules'=>array(),'platforms'=>array(),'others'=>array(),'setting_modules'=>array());
				if(!empty($userPermission['modules'])){
					foreach ($userPermission['modules'] as $module){
						if(!empty(self::$modulesKeyNameMap[$module])){
							$str = '';
							if(in_array($module, ['inventory', 'catalog', 'delivery'])){
								if($module == 'catalog' && empty($userPermission['version'])){
									$str = '可编辑, ';
								}
								else if($module == 'delivery' && (empty($userPermission['version']) || $userPermission['version'] < 1.3)){
									$str = '可打包, ';
								}
								else if(in_array($module.'_edit', $userPermission['modules'])){
									if($module == 'delivery'){
										$str = '可打包, ';
									}
									else{
										$str = '可编辑, ';
									}
								}
								
								//子权限
								if($module == 'catalog'){
									if(empty($userPermission['version']) || $userPermission['version'] < 1.4){
										$str .= '可导出, 可删除, ';
									}
									else{
										if(!empty($userPermission['module_chli'])){
											if(in_array('catalog_export', $userPermission['module_chli']))
												$str .= '可导出, ';
											if(in_array('catalog_delete', $userPermission['module_chli']))
												$str .= '可删除, ';
										}
									}
								}
							}
							
							if($str != ''){
								$str = '（'.rtrim($str, ', ').'）';
							}
							$userPermissionTags['modules'][] = self::$modulesKeyNameMap[$module].$str;
						}
						//单独打包出库
						else if($module == 'delivery_edit' && !in_array('delivery', $userPermission['modules'])){
							$userPermissionTags['modules'][] = '打包出库';
						}
					}
				}
				
				//当版本号低于1.2，把云建站、图片库的权限添加上去，lrq20180305
				if(empty($userPermission['version']) || $userPermission['version'] < 1.2){
					$userPermissionTags['modules'][] = self::$modulesKeyNameMap['image'];
				}
				
				//当版本号低于1.3，把cd跟卖终结者的权限添加上去，lrq20180322
				if(empty($userPermission['version']) || $userPermission['version'] < 1.3){
					if(!empty($userPermission['modules']) && !in_array('cdiscount', $userPermission['modules'])){
						$userPermissionTags['modules'][] = self::$modulesKeyNameMap['cdiscount'];
					}
				}
						
				if(!empty($userPermission['setting_modules']))
					foreach ($userPermission['setting_modules'] as $module)
						if(!empty(self::$SettingmodulesKeyNameMap[$module]))
							$userPermissionTags['setting_modules'][] = self::$SettingmodulesKeyNameMap[$module];

				$userPermissionTags['platforms'] = $userPermission['platforms'];
				
				if(!empty($userPermission['others']))
					foreach ($userPermission['others'] as $other)
						if(!empty(self::$ohtersKeyNameMap[$other]))
							$userPermissionTags['others'][] = self::$ohtersKeyNameMap[$other];

				$userPermissionTags['version'] = empty($userPermission['version']) ? '' : $userPermission['version']; 
								
				$permissionArr[$user->uid] = $userPermissionTags;
			}
			
			return array('erpBaseUserList'=>$erpBaseUserList , 'pagination'=>$pagination , 'permission'=>$permissionArr);
		}catch (\Exception $e) {
//			 SysLogHelper::SysLog_Create("Platform",__CLASS__, __FUNCTION__,"","权限管理错误日志： $e ", "trace");
			\Yii::trace("Permission,".__CLASS__.",". __FUNCTION__.",权限管理错误日志： $e " ,"file");
			return $e;
		}
	}

	

	/**
	 +----------------------------------------------------------------------------------------------------------------------------------
	 * 添加子用户
	 +----------------------------------------------------------------------------------------------------------------------------------
	 * @access static
	 +----------------------------------------------------------------------------------------------------------------------------------
	 * @param post	 POST提交参数
	 +----------------------------------------------------------------------------------------------------------------------------------
	 * @return		信息数据列表
	 +----------------------------------------------------------------------------------------------------------------------------------
	**/
	public static function helpInsert($post)
	{
		try{
		    \Yii::info("UserHelper::helpInsert:".json_encode($post), "file");
		    
			// 输入去空
			Helper_Array::removeEmpty($post);
			
			if (!preg_match("/^([a-zA-Z0-9_-])+(\.[a-zA-Z0-9_-]+)*@([a-zA-Z0-9_-])+(\.[a-zA-Z0-9_-]+)+/i",$post['email'])) 
				return ResultHelper::getResult(401, '', TranslateHelper::t('邮箱格式不合法'));
			if(UserBase::findOne(['email'=>$post['email']]) !== null)
		   		return ResultHelper::getResult(401, '', TranslateHelper::t('邮箱已注册'));
			if(!preg_match('/^[0-9]{4,15}$/', $post['qq']))
				return ResultHelper::getResult(402, '', TranslateHelper::t('QQ号码不合法'));
			if(!$post['password']) 
				return ResultHelper::getResult(403, '', TranslateHelper::t('密码不得为空'));
			
			$message = "";
			
			//写入user_base表
			$userBase = new UserBase();
			$userBase->user_name = $post['email'];
			$userBase->password = MD5($post['password']);
			$userBase->auth_key = \Yii::$app->getSecurity()->generateRandomString();
			$userBase->register_date = time();
			$userBase->last_login_date = time();
			$userBase->last_login_ip = self::getIP();
			$userBase->puid = \Yii::$app->user->identity->getParentUid();
			$userBase->email = trim($post['email']);
			$userBase->is_active = 1;
					
			if(!$userBase->save(false)){
			    \Yii::error("Permission,".__CLASS__.",". __FUNCTION__.",权限管理错误日志 userBase：  ".json_encode($userBase->getErrors()) ,"file");
			    return ResultHelper::getResult(400, '', "账号创建失败，请联系客服查询详情。");
			}
			
			//写入用户信息表
			$userinfo = new UserInfo();
			$userinfo->uid = $userBase->uid;
			if(empty($post['familyname']))
				$post['familyname'] = $post['email'];
			
			$userinfo->familyname = $post['familyname'];
			$userinfo->qq = $post['qq'];
			$userinfo->remark = '';
			if(!$userinfo->save(false)){
			    $message = "，但是用户qq，用户名等信息保存失败，请联系客服查询详情。";
			    \Yii::error("Permission,".__CLASS__.",". __FUNCTION__.",权限管理错误日志 userinfo：  ".json_encode($userinfo->getErrors()) ,"file");
			}
 	
			// 保存权限信息
			$platforms = array();
			$modules = array();
			$others = array();
			if(!empty($post['platforms'])) $platforms = $post['platforms'];
			//lzhl 2017-03-21 平台增加账号级别权限
			$additional_platform = array();
			foreach ($platforms as $platform){
				if(!empty($post[$platform])){
					$additional_platform[$platform] = $post[$platform];
				}
			}
			if(!empty($additional_platform))
				$platforms = $additional_platform;
			
			if(!empty($post['modules'])) $modules = $post['modules'];
			if(!empty($post['others'])) $others = $post['others'];
			$setting_modules = empty($post['setting_modules']) ? [] : $post['setting_modules'];
			if(!(empty($platforms) && empty($modules) && empty($others))){
				self::saveUserPemission($userBase->uid,$modules,$platforms,$others,$setting_modules);
			}
			
			return ResultHelper::getResult(200, '', '成功创建账号'.$message);
		}catch (\Exception $e) {
			\Yii::error("Permission,".__CLASS__.",". __FUNCTION__.",权限管理错误日志： File:".$e->getFile().",line:".$e->getLine().",message:".$e->getMessage() ,"file");
			return ResultHelper::getResult(400, '', $e->getMessage());
		}
	}


	public static function getIP()
	{
		global $ip;
		if (getenv("HTTP_CLIENT_IP"))
			$ip = getenv("HTTP_CLIENT_IP");
		else if(getenv("HTTP_X_FORWARDED_FOR"))
			$ip = getenv("HTTP_X_FORWARDED_FOR");
		else if(getenv("REMOTE_ADDR"))
			$ip = getenv("REMOTE_ADDR");
		else $ip = "Unknow";
		return $ip;
	}


	 /**
	 +----------------------------------------------------------------------------------------------------------------------------------
	 * 更新用户信息
	 +----------------------------------------------------------------------------------------------------------------------------------
	 * @access static
	 +----------------------------------------------------------------------------------------------------------------------------------
	 * @param post		  包括id数组的原始post数据
	 +----------------------------------------------------------------------------------------------------------------------------------
	 * @return				信息数据列表
	 +----------------------------------------------------------------------------------------------------------------------------------
	 **/
	public static function helpUpdate($post)
	{
		try{
		    \Yii::info("UserHelper::helpUpdate:".json_encode($post), "file");// dzt20170830 客户9693 要求加log 查是否员工操作修改子账号
		    
			// 输入去空
			Helper_Array::removeEmpty($post);
			
			$uid = $post['user_id'];
			$user = UserBase::findOne($uid);
 
			$return1 = true;
			$return2 = true;
			if(!empty($post['password'])) {
			    $post['password'] = md5($post['password']);
			    $post['auth_key'] = \Yii::$app->getSecurity()->generateRandomString();
			}
			
			$user->setAttributes($post);
			if(!$user->save()){
				$return1 = false;
				\Yii::error("Permission,".__CLASS__.",". __FUNCTION__.",权限管理错误日志：  ".json_decode($user->getErrors()) ,"file");
			}
			
			if(empty($user->info)){// 测试机账号信息不全bug fix
				$userinfo = new UserInfo();
				$userinfo->uid = $uid;
				$userinfo->familyname = $user->email;
				$userinfo->save(false);
				$user->info = $userinfo;
			}
			
			$user->info->setAttributes($post);
			if(!$user->info->save()){
				$return2 = false;
				\Yii::error("Permission,".__CLASS__.",". __FUNCTION__.",权限管理错误日志：  ".json_decode($user->info->getErrors()) ,"file");
			}
			 
			// 保存权限信息
			$platforms = array();
			$modules = array();
			$others = array();
			$module_chli = array();
			if(!empty($post['platforms'])) $platforms = $post['platforms'];
			//lzhl 2017-03-21 平台增加账号级别权限
			$additional_platform = array();
			foreach ($platforms as $platform){
				if($platform=='all'){
					$additional_platform[$platform] = [];
					continue;
				}
				if(!empty($post[$platform])){
					$additional_platform[$platform] = $post[$platform];
				}
			}
			if(!empty($additional_platform))
				$platforms = $additional_platform;
			//var_dump($platforms);
			//exit();
			if(!empty($post['modules'])) $modules = $post['modules'];
			if(!empty($post['others'])) $others = $post['others'];
			$setting_modules = empty($post['setting_modules']) ? [] : $post['setting_modules'];
			$module_chli = empty($post['module_chli']) ? [] : $post['module_chli'];
			if(!(empty($platforms) && empty($modules) && empty($others))){
				self::saveUserPemission($uid,$modules,$platforms,$others,$setting_modules,$module_chli);
			}
			
			if($return1 !== false && $return2 !== false)
				return ResultHelper::getSuccess();
			else 
				return ResultHelper::getResult(400, '', TranslateHelper::t('保存失败'));
			
		}catch (\Exception $e) {
//			 SysLogHelper::SysLog_Create("Platform",__CLASS__, __FUNCTION__,"","权限管理错误日志： $e ", "trace");
			\Yii::error("Permission,".__CLASS__.",". __FUNCTION__.",权限管理错误日志： File:".$e->getFile().",line:".$e->getLine().",message:".$e->getMessage() ,"file");
			return ResultHelper::getResult(400, '', $e->getMessage());
		}
	}
	
	/**
	 +----------------------------------------------------------------------------------------------------------------------------------
	 * 获取用户权限信息
	 +----------------------------------------------------------------------------------------------------------------------------------
	 * @access static
	 +----------------------------------------------------------------------------------------------------------------------------------
	 * @param $uid	用户id
	 * @param $type	是否读取缓存,由于目前这个function是应用于前端，默认CACHE
	 +----------------------------------------------------------------------------------------------------------------------------------
	 * @return string 用户权限json 字符串
	 +----------------------------------------------------------------------------------------------------------------------------------
	 **/
	public static function getUserPemission($uid,$type="CACHE") {
		if ($type=="NO_CACHE") {
			//$value = \Yii::$app->redis->hget('auth_manage',$uid);
			$value = RedisHelper::RedisGet('auth_manage',$uid);
			if ($value===null) return null;
		
			return $value;
		}
		
		//使用cache
		if (count(self::$_allPemissionData)==0)
			self::_loadAllUserPemission();
		if (isset(self::$_allPemissionData[$uid])) return self::$_allPemissionData[$uid];
		
		return null;
	}
	
	/**
	 +----------------------------------------------------------------------------------------------------------------------------------
	 * 获取用户平台权限信息
	 +----------------------------------------------------------------------------------------------------------------------------------
	 * @access static
	 +----------------------------------------------------------------------------------------------------------------------------------
	 * @param $uid	 用户id
	 +----------------------------------------------------------------------------------------------------------------------------------
	 * @return array	e.g. array("amazon","ebay"); 如果是全部平台的话，返回 array("all");
	 +----------------------------------------------------------------------------------------------------------------------------------
	 **/
	public static function getUserAuthorizePlatform($uid=false) {
		$isMainAccount = self::isMainAccount();
		if($isMainAccount) return array('all');
		
		if(empty($uid))
			$uid = \Yii::$app->user->id;
		
		$userPermissionJsonStr = self::getUserPemission($uid);
		if(empty($userPermissionJsonStr)) return array();
		
		$userPermission = json_decode($userPermissionJsonStr,true);
		if(empty($userPermission) || empty($userPermission['platforms'])) return array();
		
		if(in_array("all", $userPermission['platforms'])) return array('all');
		
		//2017-03-21 lzhl 加入平台账号级别权限，变成二维数组，同时要兼容旧有数据
		if(in_array("all", array_keys($userPermission['platforms'])))
			return array('all');
		foreach ($userPermission['platforms'] as $key=>$data){
			if(is_numeric($key) && is_string($data))
				return $userPermission['platforms'];
			elseif(is_array($data)){
				return array_keys($userPermission['platforms']);
			}
		}
		//default
		return $userPermission['platforms'];
	}
	
	/**
	 +----------------------------------------------------------------------------------------------------------------------------------
	 * 获取用户(单个)指定平台账号级别权限信息(返回所有有权限的店铺)
	 * @param $platform
	 * @param $uid	 用户id
	 * @return array
	 * @author lzhl 2017-03-21
	 +----------------------------------------------------------------------------------------------------------------------------------
	 **/
	public static function getUserAuthorizePlatformAccounts($platform,$uid=false) {
		$isMainAccount = self::isMainAccount();
		$puid = \Yii::$app->user->identity->getParentUid();
		if($platform==['all'])
			$platform  = OdOrder::$orderSource;
		if(is_string($platform)){//单平台查询
			$getPlatformAllAccount = PlatformAccountApi::getPlatformAllAccount($puid,$platform,true);
			$platformAllAccount[$platform] = empty($getPlatformAllAccount['data'])?[]:$getPlatformAllAccount['data'];
		}elseif(is_array($platform)){//多平台查询
			$getPlatformAllAccount = PlatformAccountApi::getAllPlatformOrderSelleruseridLabelMap($puid,true);
			$platformAllAccount = [];
			foreach ($getPlatformAllAccount as $p=>$accounts){
				if(in_array($p,$platform))
					$platformAllAccount[$p] = $accounts;
			}
		}
		
		if($isMainAccount)
			return $platformAllAccount;
		
		if(empty($uid))
			$uid = \Yii::$app->user->id;
		
		$userPermissionJsonStr = self::getUserPemission($uid,'NO_CACHE');
		
		if(empty($userPermissionJsonStr)) return array();
		
		$userPermission = json_decode($userPermissionJsonStr,true);
		if(empty($userPermission) || empty($userPermission['platforms'])) return array();
		
		//两种全权限的情况
		if(in_array("all", $userPermission['platforms']))
			return $platformAllAccount;
		if(in_array("all", array_keys($userPermission['platforms'])))
			return $platformAllAccount;
		
		
		$platformPermissionInfo = [];
		foreach ($userPermission['platforms'] as $key=>$data){
			if(is_numeric($key) && is_string($data)){
				if(is_string($platform) && $data==$platform){
					//当$data=$platform,未未开通平台账号级别权限是的数据，当时默认为平台所有账号
					return $platformAllAccount;
				}
				if(is_array($platform) && in_array($data, $platform)){
					$platformPermissionInfo[$data] = empty($platformAllAccount[$data])?[]:$platformAllAccount[$data];
				}
			}elseif(is_array($data)){
				if( (is_string($platform) && $key==$platform) || (is_array($platform) && in_array($key,$platform)) ){
					foreach ($data as $seller){
						if($seller=='all'){
							$platformPermissionInfo[$key] = empty($platformAllAccount[$key])?[]:$platformAllAccount[$key];
							break;
						}else{
							$platformPermissionInfo[$key][$seller] = empty($platformAllAccount[$key][$seller])?$seller:$platformAllAccount[$key][$seller];
						}
					}
				}else{
					continue;
				}
			}
		}
		return $platformPermissionInfo;
	}
	
	
	public static function getUserAllAuthorizePlatformAccountsArr($uid=false){
		$isMainAccount = self::isMainAccount();
		$puid = \Yii::$app->user->identity->getParentUid();
		if(empty($uid))
			$uid = \Yii::$app->user->id;
		
		$getPlatformAllAccount = PlatformAccountApi::getAllPlatformOrderSelleruseridLabelMap();
		if($isMainAccount)//主账号
			return $getPlatformAllAccount;
		
		$userPermissionJsonStr = self::getUserPemission($uid,'NO_CACHE');
		
		if(empty($userPermissionJsonStr)) return array();
		
		$userPermission = json_decode($userPermissionJsonStr,true);
		if(empty($userPermission) || empty($userPermission['platforms'])) return array();
		
		if(in_array("all", $userPermission['platforms'])){
			//拥有所有平台账号权限，则返回所有绑定的账号
			return $getPlatformAllAccount;
		}
		
		// dzt20191129 判断有问题，导致所有账号返回了，现在这个没有用了，界面点all之后会自动选择全部账号，但取消选择一个之后，这个没有反选
// 		if(in_array('all', array_keys($userPermission['platforms'])))
// 			return $getPlatformAllAccount;
		
		$platformPermissionInfo = [];
		foreach ($userPermission['platforms'] as $key=>$data){
			if(is_numeric($key) && is_string($data)){
				$platformPermissionInfo[$data] = $getPlatformAllAccount[$data];
			}elseif(is_array($data)){
				if( in_array($key , array_keys($getPlatformAllAccount)) ){
					foreach ($data as $seller){
						if($seller=='all'){
							$platformPermissionInfo[$key] = empty($getPlatformAllAccount[$key])?[]:$getPlatformAllAccount[$key];
						}else{
							$platformPermissionInfo[$key][$seller] = empty($getPlatformAllAccount[$key][$seller])?$seller:$getPlatformAllAccount[$key][$seller];
						}
					}
				}else{
					continue;
				}
			}
		}
		return $platformPermissionInfo;
	}
	
	/**
	 +----------------------------------------------------------------------------------------------------------------------------------
	 * 设置用户权限信息
	 +----------------------------------------------------------------------------------------------------------------------------------
	 * @access static
	 +----------------------------------------------------------------------------------------------------------------------------------
	 * @param $uid	用户id
	 * @param $value	用户权限json string
	 +----------------------------------------------------------------------------------------------------------------------------------
	 * @return	boolean 
	 +----------------------------------------------------------------------------------------------------------------------------------
	 **/
	public static function setUserPemission($uid,$value) {
		//\Yii::$app->redis->hset('auth_manage',$uid,$value);
		RedisHelper::RedisSet('auth_manage',$uid,$value);
		self::$_allPemissionData[$uid]=$value;
		return true;
	}
	
	/**
	 +----------------------------------------------------------------------------------------------------------------------------------
	 * @todo 保存用户界面选择权限信息
	 +----------------------------------------------------------------------------------------------------------------------------------
	 * @access static
	 +----------------------------------------------------------------------------------------------------------------------------------
	 * @param array $modules 客户选择的配置权限的模块
	 * @param array $platforms 客户选择的配置权限的平台
	 * @param array $others 客户选择的配置权限的其他模块
	 * @param array $setting_modules 客户选择的配置权限的设置模块
	 +----------------------------------------------------------------------------------------------------------------------------------
	 * @return	boolean
	 +----------------------------------------------------------------------------------------------------------------------------------
	 **/
	public static function saveUserPemission($uid,$modules,$platforms,$others,$setting_modules = [],$module_chli=[]) {
		$permission = json_encode(array("modules"=>$modules,"platforms"=>$platforms,"others"=>$others,"setting_modules"=>$setting_modules, "module_chli"=>$module_chli, "version" => 1.4));  //添加版本号，用于区分新旧设置，lrq20171120
		self::setUserPemission($uid,$permission);
		$puid = \Yii::$app->user->identity->getParentUid();
		RedisHelper::delOrderCache2($puid);
		RedisHelper::delMessageCache($puid);
		return true;
	}
	
	/**
	 +----------------------------------------------------------------------------------------------------------------------------------
	 * 读取所有用户权限信息到$_allPemissionData
	 +----------------------------------------------------------------------------------------------------------------------------------
	 * @access static
	 +----------------------------------------------------------------------------------------------------------------------------------
	 * @return 信息数据列表
	 +----------------------------------------------------------------------------------------------------------------------------------
	 **/
	private static function _loadAllUserPemission(){
		$valueArr = \Yii::$app->redis->hgetall('auth_manage');
		//$valueArr = RedisHelper::RedisGetAll1('auth_manage' );
		
		// 用过\Yii::$app->redis->hget('auth_manage',$uid); 设置的值，通过\Yii::$app->redis->hgetall('auth_manage');获取是下面这样子的数组
		// array (size=4)
		// 0 => string '1' (length=1)
		// 1 => string '{"modules":["product","delivery"],"platforms":["amazon","ebay"]}' (length=64)
		// 2 => string '10' (length=2)
		// 3 => string '{"modules":["product","delivery"],"platforms":["amazon","ebay"]}' (length=64)
		
		if($valueArr===null) return;
		if (count($valueArr)%2===1) return;
		$configNum=count($valueArr)/2;
		for($i=0;$i<$configNum;$i++){
			self::$_allPemissionData[$valueArr[$i*2]]=$valueArr[$i*2+1];
		}
		
	}
	
	/**
	 +----------------------------------------------------------------------------------------------------------------------------------
	 * 检查用户模块权限
	 +----------------------------------------------------------------------------------------------------------------------------------
	 * @access static
	 +----------------------------------------------------------------------------------------------------------------------------------
	 * @param $module	模块名称
	 * @param $uid	用户id
	 +----------------------------------------------------------------------------------------------------------------------------------
	 * @return boolean
	 +----------------------------------------------------------------------------------------------------------------------------------
	 **/
	public static function checkModulePermission($module,$uid=false){
		$isMainAccount = self::isMainAccount();
		if($isMainAccount) return true;
		
		if(empty($uid))
			$uid = \Yii::$app->user->id;
		
		$module = strtolower($module);
		$userPermissionJsonStr = self::getUserPemission($uid);
		if(empty($userPermissionJsonStr)) return false;
		
		$userPermission = json_decode($userPermissionJsonStr,true);
		if(empty($userPermission)) return false;
		
		//权限版本号低于1.2，默认添加权限
		if(empty($userPermission['version']) || $userPermission['version'] < 1.2){
			$userPermission['modules'][] = 'image';
		}
		//权限版本号低于1.3，默认添加权限
		if(empty($userPermission['version']) || $userPermission['version'] < 1.3){
			$userPermission['modules'][] = 'cdiscount';
			
			//如果有发货权限，也添加打包出库权限
			if(in_array('delivery', $userPermission['modules'])){
				$userPermission['modules'][] = 'delivery_edit';
			}
		}
		
		if(!in_array($module, $userPermission['modules']))return false;
		
		return true;
	}
	
	/**
	 +----------------------------------------------------------------------------------------------------------------------------------
	 * 检查用户平台权限
	 +----------------------------------------------------------------------------------------------------------------------------------
	 * @access static
	 +----------------------------------------------------------------------------------------------------------------------------------
	 * @param $platform	平台名称,PlatformAccountApi::$platformList 里面的平台
	 * @param $uid	用户id
	 +----------------------------------------------------------------------------------------------------------------------------------
	 * @return boolean
	 +----------------------------------------------------------------------------------------------------------------------------------
	 **/
	public static function checkPlatformPermission($platform,$uid=false){
		$isMainAccount = self::isMainAccount();
		if($isMainAccount) return true;
		
		if(empty($uid))
			$uid = \Yii::$app->user->id;
		
		$platform = strtolower($platform);
		$userPermissionJsonStr = self::getUserPemission($uid);
		if(empty($userPermissionJsonStr)) return false;
		
		$userPermission = json_decode($userPermissionJsonStr,true);
		if(empty($userPermission)) return false;
		
		if( !empty($userPermission['platforms']) ){
			if( in_array($platform, $userPermission['platforms']))
				return true;
			if( (in_array($platform, PlatformAccountApi::$platformList) && $userPermission['platforms'] == "all") )
				return true;
			if( in_array($platform, array_keys($userPermission['platforms']) ) )
				return true;
		}
		return false;
	}
	
	/**
	 +----------------------------------------------------------------------------------------------------------------------------------
	 * 检查用户其他权限
	 +----------------------------------------------------------------------------------------------------------------------------------
	 * @access static
	 +----------------------------------------------------------------------------------------------------------------------------------
	 * @param $other	其他功能，如例如统计'profit'
	 * @param $uid	 用户id
	 +----------------------------------------------------------------------------------------------------------------------------------
	 * @return boolean
	 +----------------------------------------------------------------------------------------------------------------------------------
	 **/
	public static function checkOtherPermission($other,$uid=false){
		global $CACHE; //only cache for this session,due to this may be invoked by each order, so cache,yzq 20170222
		
		$isMainAccount = self::isMainAccount();
		if($isMainAccount) return true;
		$other = strtolower($other);
		$result = true;
		if (isset($CACHE['checkOtherPermission'][$other.'-'.$uid])){
			$result = $CACHE['checkOtherPermission'][$other.'-'.$uid];
		}else{
		
			if(empty($uid))
				$uid = \Yii::$app->user->id;
			
			$userPermissionJsonStr = self::getUserPemission($uid);
			if(empty($userPermissionJsonStr)) 
				$result = false;
			else{
				$userPermission = json_decode($userPermissionJsonStr,true);
				if(empty($userPermission)) 
					$result = false;
				else{
					if(!in_array($other, $userPermission['others']))
						$result = false;
				}
			}
			$CACHE['checkOtherPermission'][$other.'-'.$uid] = $result;
		}
		
		return $result;
	}
	
	/**
	 +----------------------------------------------------------------------------------------------------------------------------------
	 * 检查用户设置权限，lrq20170829
	 +----------------------------------------------------------------------------------------------------------------------------------
	 * @access static
	 +----------------------------------------------------------------------------------------------------------------------------------
	 * @param $module	设置名称
	 * @param $uid	用户id
	 +----------------------------------------------------------------------------------------------------------------------------------
	 * @return boolean
	 +----------------------------------------------------------------------------------------------------------------------------------
	 **/
	public static function checkSettingModulePermission($module,$uid=false){
		$isMainAccount = self::isMainAccount();
		if($isMainAccount) return true;
	
		if(empty($uid))
			$uid = \Yii::$app->user->id;
	
		$module = strtolower($module);
		$userPermissionJsonStr = self::getUserPemission($uid);
		if(empty($userPermissionJsonStr)) return false;
	
		$userPermission = json_decode($userPermissionJsonStr,true);
		if(empty($userPermission)) return false;
	
		if(isset($userPermission['setting_modules']) && !in_array($module, $userPermission['setting_modules']))return false;
	
		return true;
	}
	
	/**
	 +----------------------------------------------------------------------------------------------------------------------------------
	 * 检查用户明细操作权限，lrq20180423
	 +----------------------------------------------------------------------------------------------------------------------------------
	 * @return boolean
	 +----------------------------------------------------------------------------------------------------------------------------------
	 **/
	public static function checkModuleChliPermission($module_clid, $uid=false){
		$isMainAccount = self::isMainAccount();
		if($isMainAccount) return true;
	
		if(empty($uid))
			$uid = \Yii::$app->user->id;
	
		$userPermissionJsonStr = self::getUserPemission($uid);
		if(empty($userPermissionJsonStr)) return false;
	
		$userPermission = json_decode($userPermissionJsonStr,true);
		if(empty($userPermission)) return false;
	
		if(isset($userPermission['module_chli']) && !in_array($module_clid, $userPermission['module_chli']))return false;
	
		return true;
	}
	
	/**
	 +----------------------------------------------------------------------------------------------------------------------------------
	 * 查询设置权限版本号，lrq20171120
	 +----------------------------------------------------------------------------------------------------------------------------------
	 * @access static
	 +----------------------------------------------------------------------------------------------------------------------------------
	 * @param $uid	用户id
	 +----------------------------------------------------------------------------------------------------------------------------------
	 * @return
	 +----------------------------------------------------------------------------------------------------------------------------------
	 **/
	public static function GetPermissionVersion($uid=false){
		$isMainAccount = self::isMainAccount();
		if($isMainAccount) return "";
	
		if(empty($uid))
			$uid = \Yii::$app->user->id;
	
		$userPermissionJsonStr = self::getUserPemission($uid);
		if(empty($userPermissionJsonStr)) return "";
	
		$userPermission = json_decode($userPermissionJsonStr,true);
		if(empty($userPermission)) return "";
	
		if(!empty($userPermission['version'])){
			return $userPermission['version'];
		}
		else{
			return "";
		}
	}
	
	/**
	 +--------------------------------------------------------------------------------------------------------
	 * 判断当前账号是否主账号
	 +--------------------------------------------------------------------------------------------------------
	 * @access static
	 +--------------------------------------------------------------------------------------------------------
	 * @return int
	 +--------------------------------------------------------------------------------------------------------
	 **/
	public static function isMainAccount(){
		return \Yii::$app->user->id == \Yii::$app->user->identity->getParentUid();
	}
	
	/**
	 +--------------------------------------------------------------------------------------------------------
	 * 获取正在启用的子账号数
	 +--------------------------------------------------------------------------------------------------------
	 * @access static
	 +--------------------------------------------------------------------------------------------------------
	 * @return 
	 +--------------------------------------------------------------------------------------------------------
	 **/
	public static function getSubAccountNum(){
	    $puid = \Yii::$app->subdb->getCurrentPuid();
	    $count = UserBase::find()->where(['is_active'=>1, 'puid'=>$puid])->count();
	    
	    return $count;
	}
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 获取正在启用的子账号信息
	 +---------------------------------------------------------------------------------------------
	 * @param int $puid
	 +---------------------------------------------------------------------------------------------
	 */
	public static function getUsersNameByPuid($puid){
		$users = UserBase::find()->where(['uid' => $puid])->orWhere(['puid' => $puid])->andWhere(['is_active' => 1])->asArray()->all();
		$usersNameArr = array();
		foreach ($users as $user){
			$userInfo = UserInfo::findOne(['uid'=>$user['uid']]);
			if(!empty($userInfo) && $userInfo->familyname <> ''){
				$username = $userInfo->familyname;
			}else{
				$username = $user['email'];
			}
				
			$usersNameArr[$user['uid']] = ['uid'=>$user['uid'] , 'username'=>$username , 'puid'=>$user['puid']];
		}
	
		return $usersNameArr;
	}
	
	/**
	 +----------------------------------------------------------
	 * 记录用户操作日志
	 +----------------------------------------------------------
	 * @param $log_module		模块
	 * @param $uid				
	 * @param $content			日志内容
	 * @param $addi_info		额外信息
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		lrq	2017/12/07				初始化
	 +----------------------------------------------------------
	 **/
	public static function insertUserOperationLog($log_module, $content, $uid = null, $key_id = null, $addi_info = []){
		try {
			// 超级密码登录时，不需记录用户操作
			if(isset(\Yii::$app->session['super_login']) && \Yii::$app->session['super_login'] == 1){
				return ['success' => true, 'msg' => '超级密码登录'];
			}
			
			$log_module = strtolower($log_module);
			if(empty($uid)){
				$uid = \Yii::$app->user->id;
			}
			
			$log = new UserOperationLog();
			//判断模块是否存在
			if(!array_key_exists($log_module, self::$OperationLogModules)){
				$log->remark = "$log_module 模块不存在";
			}
			else{
				$log->log_module = $log_module;
			}
			
			//日志内容截取
			if(strlen($content) > 500){
				$content = substr($content, 0, 480).'......';
			}
			
			$log->uid = $uid;
			$log->operator_name = \Yii::$app->user->identity->getFullName();
			$log->operator_content = $content;
			$log->create_time = time();
			$log->login_ip = IndexHelper::getClientIP();
			$log->addi_info = json_encode($addi_info);
			if(!empty($key_id)){
			    $log->key_id = $key_id;
			}
			
			if(!$log->save()){
				\Yii::info("insertUserOperationLog err: ".json_encode($log->errors), "file");
				return ['success' => false, 'msg' => json_encode($log->errors)];
			}
			return ['success' => true, 'msg' => ''];
		}
		catch(\Exception $ex){
			\Yii::info("insertUserOperationLog err: ".$ex->getMessage(), "file");
			return ['success' => false, 'msg' => $ex->getMessage()];
		}
	}
	
	/**
	 +----------------------------------------------------------
	 * 查询操作日志信息
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		lrq	2017/12/07				初始化
	 +----------------------------------------------------------
	 **/
	public static function getUserOperationLog($param){
		$day = 30; 
		$uid = 0;
		try{
			//判断是否主账号
			$isMainAccount = self::isMainAccount();
			if(!$isMainAccount){
				$uid = \Yii::$app->user->id;
			}
		}
		catch(\Exception $e){
		}
		//默认显示时间范围，vip30天，非vip2天
		$startdate = strtotime(date("Y-m-d", time() - 3600 * 24 * $day));
		$query = UserOperationLog::find()->where("create_time>=$startdate and log_module is not null && log_module!=''");
		
		if(!empty($uid)){
			$query->andWhere(['uid' => $uid]);
		}
		
		foreach($param as $key => $val){
			if(empty($val)){
				continue;
			}
			switch($key){
				case 'select_user_arr':
					if(!in_array('all', $val)){
						$query->andWhere(['uid' => $val]);
					}
					break;
				case 'select_module_arr':
					if(!in_array('all', $val)){
						$query->andWhere(['log_module' => $val]);
					}
					break;
				case 'startdate':
					$query->andWhere("create_time>=".strtotime($val));
					break;
				case 'enddate':
					$query->andWhere("create_time<=".strtotime($val));
					break;
				case 'search_txt':
					if(!empty($param['search_type'])){
						$val = trim($val);
						if(in_array($param['search_type'], ['operator_content', 'login_ip'])){
							$query->andWhere($param['search_type']." like '%$val%'");
						}
					}
					break;
				default:
					break;
			}
		}
		
		$page = new pagination([
			'totalCount' => $query->count(),
			'defaultPageSize' => 20,
			'pageSizeLimit' => [20, 50, 100],
		]);
		$log_list = $query
			->orderBy('create_time desc')
			->offset($page->offset)
			->limit($page->limit)
			->asArray()->all();
		
		//整理信息
		$user_name_arr = array();
		foreach($log_list as &$one){
			$one['create_time'] = date("Y-m-d H:i", $one['create_time']);
			$one['log_module'] = empty(self::$OperationLogModules[$one['log_module']]) ? '' : self::$OperationLogModules[$one['log_module']];

			//用户名
			if(empty($one['operator_name']) && !empty($one['uid'])){
				if(array_key_exists($one['uid'], $user_name_arr)){
					$one['operator_name'] = $user_name_arr[$one['uid']];
				}
				else{
					$userInfo = UserInfo::findOne(['uid' => $one['uid']]);
					if(!empty($userInfo) && !empty($userInfo['familyname'])){
						$one['operator_name'] = $userInfo['familyname'];
						$user_name_arr[$one['uid']] = $userInfo['familyname'];
					}
					else{
						$user = UserBase::findOne(['uid' => $one['uid']]);
						if(!empty($user) && !empty($user['user_name'])){
							$one['operator_name'] = $user['user_name'];
							$user_name_arr[$one['uid']] = $user['user_name'];
						}
					}
				}
			}
		}
		
		$data['list'] = $log_list;
		$data['page'] = $page;
		$data['uid'] = $uid;
		
		return $data;
	}
	
	/**
	 +----------------------------------------------------------
	 * 保存主账号别名
	 +----------------------------------------------------------
	 * log			name	date			note
	 * @author		lrq		2018/05/09		初始化
	 +----------------------------------------------------------
	 **/
	public static function SaveFamilyname($post) {
		if(empty($post['user_id'])){
			return ['success' => false, 'msg' => '参数缺失'];
		}
		if(empty($post['familyname']) || str_replace(' ', '', $post['familyname']) == ''){
			return ['success' => false, 'msg' => '用户名不能为空'];
		}
		$info = UserInfo::findOne(['uid' => $post['user_id']]);
		if(empty($info)){
			return ['success' => false, 'msg' => '账号信息不存在'];
		}
		$info->familyname = $post['familyname'];
		if(!$info->save(false)){
			return ['success' => false, 'msg' => '设置用户名失败'];
		}
		
		return ['success' => true, 'msg' => ''];
	}
	
}


