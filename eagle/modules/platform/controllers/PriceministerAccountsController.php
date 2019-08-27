<?php
namespace eagle\modules\platform\controllers;

use yii\web\Controller;
use yii\data\Sort;
use yii\data\Pagination;
use eagle\modules\util\helpers\TranslateHelper;
use eagle\models\SaasPriceministerUser;
use eagle\modules\platform\helpers\PriceministerAccountsHelper;
class PriceministerAccountsController extends \eagle\components\Controller{
	/**
	 * 账号列表view层显示
	**/
	public function actionList() {
		
		if(!empty($_GET['sort'])){
			$sort = $_GET['sort'];
			if( '-' == substr($sort,0,1) ){
				$sort = substr($sort,1);
				$order = 'desc';
			} else {
				$order = 'asc';
			}
		}
		
		$sortConfig = new Sort(['attributes' => ['store_name','token','is_active','create_time','update_time','last_order_success_retrieve_time','last_product_retrieve_time']]);
		if(empty($sort) || !in_array($sort, array_keys($sortConfig->attributes))){
			$sort = 'create_time';
			$order = 'desc';
		}
		
		
		$uid = \Yii::$app->user->identity->getParentUid();
		// SysLogHelper::SysLog_Create("platform",__CLASS__, __FUNCTION__,"","saasId =".print_r($saasId,true), "trace");

		$query = SaasPriceministerUser::find()->where(["uid" => $uid]);
		
		$pagination = new Pagination([
			'defaultPageSize' => 20,
			'totalCount' => $query->count(),
		]);
		
		$data = $query
		->offset($pagination->offset)
		->limit($pagination->limit)
		->orderBy($sort.' '.$order)
		->asArray()
		->all();
		
		$PmUserInfoList = array();
		foreach($data as $PmUser){
			$PmUser['is_active'] = $PmUser['is_active'] == 1 ? TranslateHelper::t('已启用') : TranslateHelper::t('已停用');
			$PmUserInfoList[] = $PmUser;
		}
		
		return $this->render('list', [ 'sort'=>$sortConfig , "pagination"=>$pagination , "PriceministerUserInfoList"=>$PmUserInfoList]);
	}

	/**
	 +----------------------------------------------------------
	 * 增加绑定账号view层显示
	 **/
	public function actionNew() {
		return $this->renderAjax('newOrEdit', array("mode"=>"new"));
	}

	/**
	 +----------------------------------------------------------
	 * 增加绑定账号的api信息
	 **/	
	public function actionCreate() {
		list($ret,$message)=PriceministerAccountsHelper::createPriceministerAccount($_POST);
		if  ($ret===false)  {
			exit (json_encode(array("code"=>"fail","message"=>$message)));
		}
		exit (json_encode(array("code"=>"ok","message"=>"")));
		
	}

	/**
	 +----------------------------------------------------------
	 * 查看或编辑账号信息view层显示
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * log			name			date			note
	 * @author		dzt				2015/03/04		初始化
	 +----------------------------------------------------------
	**/
	public function actionViewOrEdit() {
		$Pm_id = $_GET['pm_id'];
		$PriceministerData = SaasPriceministerUser::findOne($Pm_id);
		$PriceministerData = $PriceministerData->attributes;	
		return $this->renderAjax('newOrEdit', array("mode"=>$_GET["mode"],"PriceministerData"=>$PriceministerData));
	}

	/**
	 +----------------------------------------------------------
	 * 编辑的账户信息
	 **/
	public function actionUpdate() {
		if (!isset($_POST["priceminister_id"])){
			exit (json_encode(array("code"=>"fail","message"=>TranslateHelper::t("需要有priceminister_id"))));
		}

		list($ret,$message) = PriceministerAccountsHelper::updatePriceministerAccount($_POST);
		if  ($ret ===false)  {
			exit (json_encode(array("code"=>"fail","message"=>$message)));
		}
		exit (json_encode(array("code"=>"ok","message"=>"")));
	}

	/**
	 +----------------------------------------------------------
	 * 删除用户绑定的账户
	 **/
	public function actionDelete() {
		if (!isset($_POST["pm_id"])){
			exit (json_encode(array("code"=>"fail","message"=>"需要有priceminister_id")));
		}
		list($ret,$message)=PriceministerAccountsHelper::deletePriceministerAccount($_POST["pm_id"]);
		if  ($ret ===false)  {
			exit (json_encode(array("code"=>"fail","message"=>$message)));
		}
		exit (json_encode(array("code"=>"ok","message"=>"")));
	}
	

	/**
	 * 设置账号同步
	 * @author lzhl
	 */
	public function actionSetPriceministerAccountSync(){
		if (\Yii::$app->request->isPost){
			$user = SaasPriceministerUser::findOne(['site_id'=> $_POST['setuser']]);
			if ( null == $user ){
				exit (json_encode(array('success'=>false,'message'=>TranslateHelper::t('无该账号'))));
			}
			if ($_POST['setitem'] == 'is_active'){
				$user->is_active=$_POST['setval'];
				if($user->save()){
					exit (json_encode(array('success'=>true,'message'=>TranslateHelper::t('设置成功'))));
				}else{
					$rtn_message = '';
					foreach ($user->errors as $k => $anError){
						$rtn_message .= ($rtn_message==""?"":"<br>"). $k.":".$anError[0];
					}
					exit (json_encode(array('success'=>false,'message'=>$rtn_message )));
				}
			}
			else{
				exit (json_encode(array('success'=>false,'message'=>TranslateHelper::t('同步设置指定的属性非有效属性'))));
			}
		}
	}
}