<?php
namespace eagle\modules\permission\controllers;

use eagle\modules\permission\helpers\UserHelper;
use yii\data\Sort;
use eagle\modules\util\helpers\ResultHelper;
use eagle\modules\permission\models\UserBase;
use eagle\modules\util\helpers\TranslateHelper;
use eagle\modules\platform\apihelpers\PlatformAccountApi;
use eagle\modules\prestashop\apihelpers\MessageManagementApiHelper;
use eagle\modules\payment\controllers\UserAccountController;
use eagle\modules\permission\apihelpers\UserApiHelper;

class UserController extends \eagle\components\Controller {
	
	/**
	 +----------------------------------------------------------
	 * 子账号列表
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		dzt		2015/03/11				初始化
	 +----------------------------------------------------------
	**/
	public function actionList() {
		// 非主账号不能进入
		if(\Yii::$app->user->id != \Yii::$app->user->identity->getParentUid())
			return false;
		
		if(!empty($_GET['sort'])){
			$sort = $_GET['sort'];
			if( '-' == substr($sort,0,1) ){
				$sort = substr($sort,1);
				$order = 'desc';
			} else {
				$order = 'asc';
			}
		}

		$sortConfig = new Sort(['attributes' => ['user_name','register_date','last_login_date','last_login_ip','email','is_active']]);
		if(empty($sort) || !in_array($sort, array_keys($sortConfig->attributes))){
			$sort = 'last_login_date';
			$order = 'desc';
		}
		
		$data = UserHelper::helpList($sort , $order);
		if(is_array($data)){
			return $this->render('list', [
					'erpBaseUsers'=>$data['erpBaseUserList'] ,
					'sort'=>$sortConfig ,
					'pagination'=>$data['pagination'] ,
					'permission'=>$data['permission'],
					'platformAccountList'=>PlatformAccountApi::getAllPlatformOrderSelleruseridLabelMap(),
			]);
		} else {// Exception 信息
			return  $data;
		}
	}

	
	/**
	 +----------------------------------------------------------
	 * 增加子账户view层显示
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		dzt		2015/03/12				初始化
	 +----------------------------------------------------------
	**/
	public function actionAdd() {
		return $this->renderAjax('add',['platformList'=>PlatformAccountApi::$platformList,'platformAccountList'=>PlatformAccountApi::getAllPlatformOrderSelleruseridLabelMap(),
        		'modules'=>UserHelper::$modulesKeyNameMap,'others'=>UserHelper::$ohtersKeyNameMap,'setting_modules'=>UserHelper::$SettingmodulesKeyNameMap]);
	}

	/**
	 +----------------------------------------------------------
	 * 主user编辑弹窗层显示
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		dzt		2015/03/12				初始化
	 +----------------------------------------------------------
	**/
    public function actionEdit() {
        if(empty($_POST) || empty($_POST['user_id'])) exit("参数缺失！");
        
        $user = UserBase::findOne($_POST['user_id']);
        $puid = \Yii::$app->user->identity->getParentUid();
        if($user->puid != $puid) exit("该用户的主账号不是当前账号！");
        
        if(empty($user)) $user = new UserBase();
        
        $permissionJsonStr = UserHelper::getUserPemission($_POST['user_id']);
        $permission = json_decode($permissionJsonStr,true);
        
        $tmp_platformAccountList = PlatformAccountApi::getAllPlatformOrderSelleruseridLabelMap(false, true, true);
        
        if(!empty($tmp_platformAccountList['wish'])){
        	$tmp_wishM = $tmp_platformAccountList['wish'];
        	unset($tmp_platformAccountList['wish']);
        	$tmp_platformAccountList['wish'] = \eagle\modules\platform\apihelpers\WishAccountsApiHelper::getWishAliasAccount($tmp_wishM);
        }
        
        return $this->renderAjax('edit', array('user'=>$user,'platformList'=>PlatformAccountApi::$platformList,'platformAccountList'=>$tmp_platformAccountList,
        		'modules'=>UserHelper::$modulesKeyNameMap,'others'=>UserHelper::$ohtersKeyNameMap,"permission"=>$permission,'setting_modules'=>UserHelper::$SettingmodulesKeyNameMap));
    }

	
	/**
	 +----------------------------------------------------------
	 * 增加或修改erp user ajax数据返回
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		dzt		2015/03/12				初始化
	 +----------------------------------------------------------
	**/
	public function actionSave() {
		if(isset($_POST['user_add_label']))
			return UserHelper::helpInsert($_POST);
		
		if(empty($_POST['user_id']))
			return ResultHelper::getResult(400, '', TranslateHelper::t('请选择保存的账号！'));
		
		//检查信息
		$puid = \Yii::$app->user->identity->getParentUid();
		$uid = \Yii::$app->user->id;
		if($puid != $uid && $_POST["user_id"] != $uid){
			return ResultHelper::getResult(400, '', TranslateHelper::t('非主账号不可修改其他账号信息！'));
		}
		
		$user = UserBase::findOne($_POST["user_id"]);
		if(empty($user)){
			return ResultHelper::getResult(400, '', TranslateHelper::t('账号不存在，请重新刷新页面！'));
		}
		
		if(!empty($_POST['user_edit_label']) && 2 == $_POST['user_edit_label']){// 修改密码
			if(empty($_POST['formerpassword'])){
				return ResultHelper::getResult(400, '', TranslateHelper::t('请输入原密码！'));
			}
				
			if(md5($_POST['formerpassword']) != $user->password){
				return ResultHelper::getResult(400, '', TranslateHelper::t('原密码错误！'));
			}
		}
		
		// 账号开启，关闭按钮
		if(isset($_POST['is_active']) && $user->puid == 0){
			return ResultHelper::getResult(400, '', TranslateHelper::t('主账号不可以更改状态'));
		}
		
		
		return UserHelper::helpUpdate($_POST);
			
	}
	
	/**
	 +----------------------------------------------------------
	 * user编辑view层新页面显示
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		dzt		2015/06/16				初始化
	 +----------------------------------------------------------
	 **/
	public function actionAccountEdit() {
		$uid = \Yii::$app->user->id;
        $user = UserBase::find()->where(['uid'=>$uid])->one();
        if(empty($user)) $user = new UserBase();
        return $this->render('edit2', array('user'=>$user));
	}
	
	/**
	 +----------------------------------------------------------
	 * user操作日志列表
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * log			name	date			note
	 * @author		lrq		2017/12/08		初始化
	 +----------------------------------------------------------
	 **/
	public function actionOperationLog(){
		$puid = \Yii::$app->user->identity->getParentUid();
		if(empty($puid)) $puid = \Yii::$app->user->id;
		
		//整理筛选信息
		$param = $_GET;
		if(!isset($param['select_user_strs'])){
		    $param['select_user_strs'] = 'all';
		}
		if(!isset($param['select_module_strs'])){
			$param['select_module_strs'] = 'all';
		}
		$param['select_user_strs'] = rtrim(trim($param['select_user_strs']), ',');
		$param['select_user_arr'] = explode(',', $param['select_user_strs']);
		$param['select_module_strs'] = rtrim(trim($param['select_module_strs']), ',');
		$param['select_module_arr'] = explode(',', $param['select_module_strs']);
		//起始、结束时间，添加默认值
		if(empty($param['startdate'])){
			$param['startdate'] = date("Y-m-d", time() - 3600 * 24).'T00:00';
		}
		if(empty($param['enddate'])){
			$param['enddate'] = date("Y-m-d",  time() + 3600 * 24).'T00:00';
		}
		
		$data = UserHelper::getUserOperationLog($param);
		
		$menu = ['系统管理'=>[
				'icon'=>'icon-shezhi',
				'items'=>[
					'操作日志'=>[
						'url'=>'/permission/user/operation-log',
					],
				]
			]];
	
		return $this->render('operationloglist',$data + [
			'menu' => $menu,
			'user_list' => UserHelper::getUsersNameByPuid($puid),
			'module_list' => UserHelper::$OperationLogModules,
			'active' => '操作日志',
			'param' => $param,
		]);
	}
	
	/**
	 +----------------------------------------------------------
	 * 保存主账号别名
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * log			name	date			note
	 * @author		lrq		2018/05/09		初始化
	 +----------------------------------------------------------
	 **/
	public function actionSaveFamilyname() {
		return json_encode(UserHelper::SaveFamilyname($_POST));
			
	}
}

