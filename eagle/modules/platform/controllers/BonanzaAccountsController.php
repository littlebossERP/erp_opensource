<?php
namespace eagle\modules\platform\controllers;

use yii\web\Controller;
use eagle\models\SaasBonanzaUser;
use yii\data\Sort;
use yii\data\Pagination;
use eagle\modules\util\helpers\TranslateHelper;

use eagle\modules\order\helpers\BonanzaOrderInterface;
use eagle\modules\platform\helpers\BonanzaAccountsHelper;
class BonanzaAccountsController extends \eagle\components\Controller{
	/**
	 * Bonanza账号列表view层显示
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * log			name		date			note
	 * @author		lwj			2016/04/18		初始化
	 +----------------------------------------------------------
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

		$query = SaasBonanzaUser::find()->where(["uid" => $saasId]);
		
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
		
		$BonanzaUserInfoList = array();
		foreach($data as $BonanzaUser){
			$BonanzaUser['is_active'] = $BonanzaUser['is_active'] == 1 ? TranslateHelper::t('已启用') : TranslateHelper::t('已停用');
			$BonanzaUserInfoList[] = $BonanzaUser;
		}
		
		return $this->render('list', [ 'sort'=>$sortConfig , "pagination"=>$pagination , "BonanzaUserInfoList"=>$BonanzaUserInfoList]);
	}

	/**
	 +----------------------------------------------------------
	 * 增加绑定BonanzaUser账号view层显示
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * log			name			date			note
	 * @author		lwj				2016/04/18		初始化
	 +----------------------------------------------------------
	 **/
	public function actionNew() {
	    $result = BonanzaOrderInterface::fetchToken();
	    if($result['success'] == true){
	        $url = $result['authenticationURL'];
	        $token = $result['authToken'];
	        $success = $result['success'];
	        $message = '';
	    }else{
	        $url = '';
	        $token = '';
	        $success = $result['success'];
	        $message = $result['message'];
	    }
	    $data = [
	        "mode"=>"new",
	        "url"=>$url,
	        "token"=>$token,
	        "success"=>$success,
	        "message"=>$message,
	    ];
		return $this->renderAjax('newOrEdit', $data);
	}

	/**
	 +----------------------------------------------------------
	 * 增加绑定Bonanza账号的api信息
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * log			name			date			note
	 * @author		lwj				2016/04/18		初始化
	 +----------------------------------------------------------
	 **/	
	public function actionCreate() {
	    BonanzaOrderInterface::setBonanzaToken($_REQUEST['token']);
	    $accept_result = BonanzaOrderInterface::acceptOrder('12345');
	    $check = strpos($accept_result['message'],'InvalidAuthTokena');
	    if($check === false){//至少不是token失败
	        list($ret,$message) = BonanzaAccountsHelper::createBonanzaAccount($_REQUEST);
	        if  ($ret===false)  {
	            exit (json_encode(array("code"=>"fail","message"=>$message)));
	        }
	        exit (json_encode(array("code"=>"ok","message"=>"")));
	    }else{
	        exit (json_encode(array("code"=>"fail","message"=>"平台账号没有授权成功，不能创建帐号")));
	    }
		
		
	}

	/**
	 +----------------------------------------------------------
	 * 查看或编辑Bonanza账号信息view层显示
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * log			name			date			note
	 * @author		lwj				2016/04/18		初始化
	 +----------------------------------------------------------
	**/
	public function actionViewOrEdit() {
		$Bonanza_id = $_GET['bonanza_id'];
		$BonanzaData = SaasBonanzaUser::findOne($Bonanza_id);
		$BonanzaData = $BonanzaData->attributes;	
		if($_GET["mode"] == 'edit'){
		    $data = [
		        "mode"=>$_GET["mode"],
		        "BonanzaData"=>$BonanzaData,
		    ];
		}
// 		else{
// 		    $result = BonanzaOrderInterface::fetchToken();
// 		    if($result['success'] == true){
// 		        $url = $result['authenticationURL'];
// 		        $token = $result['authToken'];
// 		        $success = $result['success'];
// 		        $message = '';
// 		    }else{
// 		        $url = '';
// 		        $token = '';
// 		        $success = $result['success'];
// 		        $message = $result['message'];
// 		    }
// 		    $data = [
// 		        "mode"=>$_GET["mode"],
// 		        "url"=>$url,
// 		        "token"=>$token,
// 		        "success"=>$success,
// 		        "message"=>$message,
// 		        "BonanzaData"=>$BonanzaData,
// 		    ];
// 		}
		
		return $this->renderAjax('newOrEdit', $data);
	}

	/**
	 +----------------------------------------------------------
	 * 编辑Wish的账户信息
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		lwj		2016/04/18				初始化
	 +----------------------------------------------------------
	 **/
	public function actionUpdate() {
	    if (!isset($_POST["bonanza_id"])){
	        exit (json_encode(array("code"=>"fail","message"=>TranslateHelper::t("需要有bonanza_id"))));
	    }
	     
	    list($ret,$message) = BonanzaAccountsHelper::updateBonanzaAccount($_POST);
	    if  ($ret ===false)  {
	        exit (json_encode(array("code"=>"fail","message"=>$message)));
	    }
	    exit (json_encode(array("code"=>"ok","message"=>"")));
	    
	}

	/**
	 +----------------------------------------------------------
	 * 删除用户绑定的Bonanza账户
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		lwj		2016/04/18				初始化
	 +----------------------------------------------------------
	 **/
	public function actionDelete() {
		if (!isset($_POST["bonanza_id"])){
			exit (json_encode(array("code"=>"fail","message"=>"需要有Bonanza_id")));
		}
		list($ret,$message)=BonanzaAccountsHelper::deleteBonanzaAccount($_POST["bonanza_id"]);
		if  ($ret ===false)  {
			exit (json_encode(array("code"=>"fail","message"=>$message)));
		}
		exit (json_encode(array("code"=>"ok","message"=>"")));
	}
	

	/**
	 * 设置Bonanza账号同步
	 * @author lzhl
	 */
	public function actionSetBonanzaAccountSync(){
		if (\Yii::$app->request->isPost){
			$user = SaasBonanzaUser::findOne(['site_id'=> $_POST['setusr']]);
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
