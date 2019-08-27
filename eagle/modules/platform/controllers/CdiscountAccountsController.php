<?php
namespace eagle\modules\platform\controllers;

use yii\web\Controller;
use eagle\models\SaasCdiscountUser;
use yii\data\Sort;
use yii\data\Pagination;
use eagle\modules\util\helpers\TranslateHelper;
use eagle\modules\platform\helpers\CdiscountAccountsHelper;
class CdiscountAccountsController extends \eagle\components\Controller{
	/**
	 * Cdiscount账号列表view层显示
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
		
		
		$saasId = \Yii::$app->user->identity->getParentUid();
		// SysLogHelper::SysLog_Create("platform",__CLASS__, __FUNCTION__,"","saasId =".print_r($saasId,true), "trace");

		$query = SaasCdiscountUser::find()->where(["uid" => $saasId]);
		
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
		
		$CdiscountUserInfoList = array();
		foreach($data as $CdiscountUser){
			$CdiscountUser['is_active'] = $CdiscountUser['is_active'] == 1 ? TranslateHelper::t('已启用') : TranslateHelper::t('已停用');
// 			$WishUser['create_time'] = gmdate('Y-m-d H:i:s', $WishUser['create_time'] + 8 * 3600);
// 			$WishUser['update_time'] = gmdate('Y-m-d H:i:s', $WishUser['update_time'] + 8 * 3600);
			$CdiscountUserInfoList[] = $CdiscountUser;
		}
		
		return $this->render('list', [ 'sort'=>$sortConfig , "pagination"=>$pagination , "CdiscountUserInfoList"=>$CdiscountUserInfoList]);
	}

	/**
	 +----------------------------------------------------------
	 * 增加绑定CdiscountUser账号view层显示
	 +----------------------------------------------------------
	 **/
	public function actionNew() {
		return $this->renderAjax('newOrEdit', array("mode"=>"new"));
	}

	/**
	 +----------------------------------------------------------
	 * 增加绑定Cdiscount账号的api信息
	 +----------------------------------------------------------
	 **/	
	public function actionCreate() {
		list($ret,$message)=CdiscountAccountsHelper::createCdiscountAccount($_POST);
		if  ($ret===false)  {
			exit (json_encode(array("code"=>"fail","message"=>$message)));
		}
		exit (json_encode(array("code"=>"ok","message"=>"")));
		
	}

	/**
	 +----------------------------------------------------------
	 * 查看或编辑Cdiscount账号信息view层显示
	 +----------------------------------------------------------
	**/
	public function actionViewOrEdit() {
		$Cdiscount_id = $_GET['cdiscount_id'];
		$CdiscountData = SaasCdiscountUser::findOne($Cdiscount_id);
		$CdiscountData = $CdiscountData->attributes;	
		return $this->renderAjax('newOrEdit', array("mode"=>$_GET["mode"],"CdiscountData"=>$CdiscountData));
	}

	/**
	 +----------------------------------------------------------
	 * 编辑的账户信息
	 +----------------------------------------------------------
	 **/
	public function actionUpdate() {
		if (!isset($_POST["cdiscount_id"])){
			exit (json_encode(array("code"=>"fail","message"=>TranslateHelper::t("需要有cdiscount_id"))));
		}

		list($ret,$message) = CdiscountAccountsHelper::updateCdiscountAccount($_POST);
		if  ($ret ===false)  {
			exit (json_encode(array("code"=>"fail","message"=>$message)));
		}
		exit (json_encode(array("code"=>"ok","message"=>"")));
	}

	/**
	 +----------------------------------------------------------
	 * 删除用户绑定的Cdiscount账户
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		xjq		2014/09/09				初始化
	 +----------------------------------------------------------
	 **/
	public function actionDelete() {
		if (!isset($_POST["cdiscount_id"])){
			exit (json_encode(array("code"=>"fail","message"=>"需要有Cdiscount_id")));
		}
		list($ret,$message)=CdiscountAccountsHelper::deleteCdiscountAccount($_POST["cdiscount_id"]);
		if  ($ret ===false)  {
			exit (json_encode(array("code"=>"fail","message"=>$message)));
		}
		exit (json_encode(array("code"=>"ok","message"=>"")));
	}
	

	/**
	 * 设置Cdiscount账号同步
	 * @author lzhl
	 */
	public function actionSetCdiscountAccountSync(){
		if (\Yii::$app->request->isPost){
			$user = SaasCdiscountUser::findOne(['site_id'=> $_POST['setusr']]);
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