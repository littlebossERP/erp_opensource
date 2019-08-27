<?php

namespace crm\controllers;

use Yii;
use yii\filters\VerbFilter;
use \Exception;
use yii\web\Controller;
use crm\models\User;
use yii\filters\AccessControl;
use crm\helpers\CrmOperationLogHelper;

class CrmuserController extends \yii\web\Controller
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
	 +----------------------------------------------------------
	 * app Signup页面
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		hqw 	2015/04/03				初始化
	 +----------------------------------------------------------
	 * @Parameters
	 **/
	public function actionSignup(){
		if(Yii::$app->user->identity->role == 1){
			return $this->render('signup');
		}else{
			return $this->render('@crm/views/crmuser/crmUserErrorAuthority',[]);
		}
	}
	
	
	/**
	 +----------------------------------------------------------
	 * app Signup页面注册写落数据库
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		hqw 	2015/04/03				初始化
	 +----------------------------------------------------------
	 * @Parameters
	 * $_POST['signupusername'] 用户名
	 * $_POST['signuppassword'] 用户密码
	 * $_POST['signupemail'] 邮箱
	 **/
	public function actionSignupSave(){
		//$result ['success'] 记录是否添加成功状态
		//$result ['message'] 记录是否错误或成功的信息
		$result ['success'] = true;
		$result ['message'] = '';
		
		
		$crmUser = User::findOne(['username'=>$_POST['signupusername']]);
		
		if (count($crmUser)>0){
			$result ['success'] = false;
			$result ['message'] = '用户名已重复';
		}
		
		if ($result['success']==false) exit(json_encode($result));
			
		$crmUser = User::findOne(['email'=>$_POST['signupemail']]);
		if (count($crmUser)>0){
			$result ['success'] = false;
			$result ['message'] = '邮箱已重复';
		}
		
		if ($result['success']==false) exit(json_encode($result));
		
		//创建$crmUser对象
		$crmUser = new User();
		$crmUser->username = $_POST ['signupusername'];
		$crmUser->setPassword($_POST['signuppassword']);
		$crmUser->email = $_POST['signupemail'];
		$crmUser->generateAuthKey();
		$crmUser->generatePasswordResetToken();
		
		if ($crmUser->save()){
			$result ['success'] = true;
			$result ['message'] = 'yes';
			
			CrmOperationLogHelper::Log($_SERVER['REQUEST_URI'],"CrmUser:创建,id:".$crmUser->id);
		}else{
			$result ['success'] = false;
			$result ['message'] = '保存失败';
		}
		 
		exit(json_encode($result));
	}
	
	
	/**
	 +----------------------------------------------------------
	 * CrmUserManage页面 Crm账户管理
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		hqw 	2015/04/08				初始化
	 +----------------------------------------------------------
	 * @Param
	 **/
	public function actionCrmUserManage(){
		if(Yii::$app->user->identity->role == 1){
			$crmUserArr = User::find()->asArray()->all();
		}else{
			$crmUserArr = User::find()->where(['username'=>Yii::$app->user->identity->username])->asArray()->all();
		}
		
		return $this->render('crmUserManage',['crmUserArr'=>$crmUserArr]);
	}
	
	/**
	 +----------------------------------------------------------
	 * CrmUserManage账户管理修改页面
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		hqw 	2015/04/08				初始化
	 +----------------------------------------------------------
	 * @Param
	 * $_GET ['id'] 获取需要修改的ID
	 **/
	public function actionCrmUserEdit(){
		$crmUser = array();
		
		if (!empty( $_GET ['id'] )) {
			$crmUser = User::findOne(['id'=>$_GET ['id']]);
		}
		
		return $this->renderAjax("crmuseredit",["crmUser"=>$crmUser]);
	}
	
	/**
	 +----------------------------------------------------------
	 * CrmUserManage账户管理修改页面保存方法
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		hqw 	2015/04/08				初始化
	 +----------------------------------------------------------
	 * @Param
	 * $_POST ['keyid'] 获取需要修改的ID
	 * $_POST['signupform-password'] 需要获取旧密码是否和新密码一致
	 * $_POST['signupform-username'] 获取新的用户名称
	 * $_POST['signupform-email'] 获取新的email名称
	 * $_POST['IsEditPassWord'] 获取是否需要改密码
	 * $_POST['newPassWordID'] 获取新密码
	 * 
	 **/
	public function actionCrmUserEditSave(){
		//$result ['success'] 记录是否添加成功状态
		//$result ['message'] 记录是否错误或成功的信息
		$result ['success'] = true;
		$result ['message'] = '';
		
		if (empty($_POST['keyid'])){
			$result ['success'] = false;
			$result ['message'] = '请先选定特定人员修改';
			exit(json_encode($result));
		}
		
		$crmUser = User::findOne(['id'=>$_POST['keyid']]);
		
		
		if(count($crmUser)<=0){
			$result ['success'] = false;
			$result ['message'] = '不存在改人员';
			exit(json_encode($result));
		}
		
		if(!empty($_POST['signupform-password'])){
			//1. 判断旧密码是否正确
			$oldHashPasswd = $crmUser->password_hash;
			$userInputPasswd=$_POST['signupform-password'];
			
// 			\Yii::info($oldHashPasswd);写Log来判断问题所在
			$test = crypt($userInputPasswd, $oldHashPasswd);
			$n = strlen($test);
			if ($n !== 60) {
				$result ['success'] = false;
				$result ['message'] = '密码不相同，不能保存2！';
				exit(json_encode($result));
			}
			$ret=\Yii::$app->security->compareString($test, $oldHashPasswd);
			
			if ($ret===false){
				$result ['success'] = false;
				$result ['message'] = '旧密码不相同，不能保存！';
				exit(json_encode($result));
			}
		}
		else{
			$result ['success'] = false;
			$result ['message'] = '旧密码不能为空，不能保存！';
			exit(json_encode($result));
		}
		
		if(!empty($_POST['signupform-username'])){
			$conn=\Yii::$app->db;
			
			$crmUserIS=$conn->createCommand('select count(1) as usercount from crm_user a
					 where 1=1 and a.username=:username and a.id<>:id ',
					[":username"=>$_POST['signupform-username'],":id"=>$_POST['keyid']])->queryAll();

			if ($crmUserIS[0]['usercount']>0){
					$result ['success'] = false;
					$result ['message'] = '用户名已重复';
			}
			
			if ($result['success']==false) exit(json_encode($result));
			
			$crmUser->username=$_POST['signupform-username'];
		}
		
		
		if(!empty($_POST['signupform-email'])){
			$conn=\Yii::$app->db;
				
			$crmUserIS=$conn->createCommand('select count(1) as usercount from crm_user a
					 where 1=1 and a.email=:email and a.id<>:id ',
					[":email"=>$_POST['signupform-email'],":id"=>$_POST['keyid']])->queryAll();
			
			if ($crmUserIS[0]['usercount']>0){
				$result ['success'] = false;
				$result ['message'] = '邮箱已重复';
			}
			
			if ($result['success']==false) exit(json_encode($result));
			
			$crmUser->email=$_POST['signupform-email'];
		}
		
		if(!empty($_POST['IsEditPassWord']) && !empty($_POST['newPassWordID']) && !empty($_POST['newPassWordSureID'])){
			
			if ($_POST['newPassWordID']!=$_POST['newPassWordSureID']){
				$result ['success'] = false;
				$result ['message'] = '新密码和密码确认不一致';
				exit(json_encode($result));
			}
			
			if ($_POST['newPassWordID']==""){
				$result ['success'] = false;
				$result ['message'] = '新密码不能为空';
				exit(json_encode($result));
			}
			
			if (($_POST['IsEditPassWord']=="YesPassWord") && ($_POST['newPassWordID']!="")){
				$crmUser->setPassword($_POST['newPassWordID']);
			}
		}
		
		
		$crmUser->updated_at=date('Y-m-d H:i:s');
		
		if ($crmUser->save()){
			$result ['success'] = true;
			$result ['message'] = 'yes';
			
			CrmOperationLogHelper::Log($_SERVER['REQUEST_URI'],"CrmUser:修改,id:".$crmUser->id);
		}else{
			$result ['success'] = false;
			$result ['message'] = '保存失败';
		}
		
		exit(json_encode($result));
	}
	
	//CRM用户权限不足页面
	public function actionCrmUserErrorAuthority(){
		return $this->render('crmUserErrorAuthority',[]);
	}
	
	//CRM user 保存权限
	public function actionCrmUserRoleSave(){
		$result ['success'] = true;
		$result ['message'] = '';
		
		$crm_user_id = isset($_POST['crm_user_id']) ? $_POST['crm_user_id'] : 0;
		$role_id = isset($_POST['role_id']) ? $_POST['role_id'] : 0;
		 
		if($crm_user_id == 0){
			$result ['success'] = false;
			$result ['message'] = '非法操作';
			exit(json_encode($result));
		}
		 
		if($role_id == 0){
			$result ['success'] = false;
			$result ['message'] = '非法操作2';
			exit(json_encode($result));
		}
		
		$crmUser = User::findOne($crm_user_id);
		
		if($crmUser == null){
			$result ['success'] = false;
			$result ['message'] = '非法操作3';
			exit(json_encode($result));
		}
		
		if($crmUser->role != $role_id){
			CrmOperationLogHelper::Log($_SERVER['REQUEST_URI'],"CrmUser:修改权限,role:".$crmUser->role.' 改为 '.$role_id);
			
			$crmUser->role = $role_id;
			$crmUser->save(false);
		}
		
		$result ['message'] = 'yes';
		exit(json_encode($result));
	}
	
}
