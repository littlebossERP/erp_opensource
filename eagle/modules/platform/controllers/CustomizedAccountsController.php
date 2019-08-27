<?php
namespace eagle\modules\platform\controllers;

use yii\web\Controller;
use yii\data\Sort;
use yii\data\Pagination;
use eagle\modules\util\helpers\TranslateHelper;
use eagle\models\SaasPriceministerUser;
use eagle\modules\platform\helpers\PriceministerAccountsHelper;
use eagle\models\SaasPaypalUser;
use common\api\paypalinterface\PaypalInterface_GetTransactionDetails;
use eagle\models\SaasCustomizedUser;
use eagle\modules\util\helpers\TimeUtil;
class CustomizedAccountsController extends \eagle\components\Controller{
	/**
	 * Customized账号列表view层显示
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * @author		lzhl	2016/12/20		初始化
	 +----------------------------------------------------------
	**/
	public function actionList() {
		$uid = \Yii::$app->user->identity->getParentUid();
		
		$query = SaasCustomizedUser::find()->where(["uid" => $uid]);
		
		$pagination = new Pagination([
			'defaultPageSize' => 20,
			'totalCount' => $query->count(),
		]);
		
		$CustomizedAccounts = $query
			->offset($pagination->offset)
			->limit($pagination->limit)
			->orderBy(' site_id ASC ')
			->asArray()
			->all();
		
		return $this->render('list', [
				"pagination"=>$pagination ,
				"CustomizedAccounts"=>$CustomizedAccounts
			]);
	}

	/**
	 +----------------------------------------------------------
	 * 增加绑定Customized账号view层显示
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * @author		lzhl	2016/12/20		初始化
	 +----------------------------------------------------------
	 **/
	public function actionNew() {
		return $this->renderAjax('newOrEdit', array(
				"mode"=>"new",
				"CustomizedData"=>new SaasCustomizedUser(),
		));
	}

	/**
	 +----------------------------------------------------------
	 * 编辑绑定Customized账号view层显示
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * @author		lzhl	2016/12/20		初始化
	 +----------------------------------------------------------
	 **/
	public function actionEdit() {
		if(empty($_REQUEST['site_id']))
			exit('需要有自定义店铺小老板id');
		return $this->renderAjax('newOrEdit', array(
				"mode"=>"edit",
				"CustomizedData"=>SaasCustomizedUser::findOne($_REQUEST['site_id']),
		));
	}
	
	/**
	 +----------------------------------------------------------
	 * 增加绑定Customized账号的api信息
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * @author		lzhl	2016/12/20		初始化
	 +----------------------------------------------------------
	 **/	
	public function actionSave() {
		$store_name = empty($_POST['store_name'])?'':trim($_POST['store_name']);
		$user_name = empty($_POST['user_name'])?'':trim($_POST['user_name']);
		if(empty($store_name) || empty($user_name))
			exit (json_encode(array("code"=>"fail","message"=>"提交的信息不完整，绑定失败")));
		
		$uid = \Yii::$app->user->id;
		
		$mode = empty($_REQUEST['mode'])?'new':$_REQUEST['mode'];
		
		$is_active = empty($_POST['is_active'])?0:1;
		
		if($mode=='new'){
			$record = SaasCustomizedUser::find()->where(['uid'=>$uid])
				->andWhere([ 'or', ['store_name'=>$store_name],['username'=>$user_name] ])
				->one();
			if(!empty($record)){
				exit (json_encode(array("code"=>"fail","message"=>"店铺名称 或 店铺登录名 已存在，绑定失败")));
			}
			$CustomizedUser= new SaasCustomizedUser();
			$CustomizedUser->create_time = TimeUtil::getNow();
		}elseif($mode=='edit'){
			if(empty($_REQUEST['site_id']))
				exit (json_encode(array("code"=>"fail","message"=>"需要有自定义店铺小老板id")));
				
			$record = SaasCustomizedUser::find()->where(['uid'=>$uid])->andWhere(" site_id<>".$_REQUEST['site_id'])
				->andWhere([ 'or', ['store_name'=>$store_name],['username'=>$user_name] ])
				->one();
			
			if(!empty($record)){
				exit (json_encode(array("code"=>"fail","message"=>"店铺名称 或 店铺登录名 已存在，修改失败")));
			}
			$CustomizedUser = SaasCustomizedUser::findOne($_REQUEST['site_id']);
		}else{
			exit (json_encode(array("code"=>"fail","message"=>"不支持的操作模式")));
		}
		
		$CustomizedUser->store_name = $store_name;
		$CustomizedUser->username = $user_name;
		$CustomizedUser->is_active = $is_active;
		$CustomizedUser->update_time = TimeUtil::getNow();
		$CustomizedUser->uid = $uid;
		
		if(!$CustomizedUser->save()){
			$message = '';
			foreach ($CustomizedUser->errors as $k => $anError){
				$message .= "Save Customized user feiled!". ($message==""?"":"<br>"). $k." error:".$anError[0];
			}
			exit (json_encode(array("code"=>"fail","message"=>$message)));
		}
		
		exit (json_encode(array("code"=>"ok","message"=>"")));
	}
	
	/**
	 +----------------------------------------------------------
	 * 停用/启用 用户绑定的自定义店铺账户
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * @author		lzhl	2016/12/20		初始化
	 +----------------------------------------------------------
	 **/
	public function actionSwitchActive() {
		if (!isset($_POST["site_id"])){
			exit (json_encode(array("code"=>"fail","message"=>"需要有自定义店铺小老板id")));
		}
		$site_id = (int)$_REQUEST['site_id'];
		$account = SaasCustomizedUser::findOne($site_id);
		$is_active = empty($_REQUEST['is_active'])?0:1;
		
		if(!empty($account)){
			$account->is_active = $is_active;
			$account->update_time = TimeUtil::getNow();
			
			if(!$account->save()){
				$message = '';
				foreach ($account->errors as $k => $anError){
					$message .= "Update Customized user feiled!". ($message==""?"":"<br>"). $k." error:".$anError[0];
				}
				exit (json_encode(array("code"=>"fail","message"=>$message)));
			}
		}else{
			exit (json_encode(array("code"=>"fail","message"=>"查找不到对应的自定义店铺信息，删除失败")));
		}
		
		exit (json_encode(array("code"=>"ok","message"=>"")));
	}
	
	/**
	 +----------------------------------------------------------
	 * 删除用户绑定的自定义店铺账户
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * @author		lzhl	2016/12/20		初始化
	 +----------------------------------------------------------
	 **/
	public function actionDelete() {
		if (!isset($_POST["site_id"])){
			exit (json_encode(array("code"=>"fail","message"=>"需要有自定义店铺小老板id")));
		}
		$site_id = (int)$_REQUEST['site_id'];
		$rtn = SaasCustomizedUser::deleteAll("site_id=$site_id");
		if  (empty($rtn))  {
			exit (json_encode(array("code"=>"fail","message"=>"删除失败，请刷新页面后重试")));
		}
		exit (json_encode(array("code"=>"ok","message"=>"")));
	}
	
}