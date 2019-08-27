<?php
namespace eagle\modules\platform\controllers;

use yii\web\Controller;
use eagle\models\SaasRumallUser;
use yii\data\Sort;
use yii\data\Pagination;
use eagle\modules\util\helpers\TranslateHelper;

use eagle\modules\order\helpers\RumallOrderInterface;
use eagle\modules\platform\helpers\RumallAccountsHelper;
class RumallAccountsController extends \eagle\components\Controller{
	/**
	 * Rumall账号列表view层显示
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

		$query = SaasRumallUser::find()->where(["uid" => $saasId]);
		
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
		
		$RumallUserInfoList = array();
		foreach($data as $RumallUser){
			$RumallUser['is_active'] = $RumallUser['is_active'] == 1 ? TranslateHelper::t('已启用') : TranslateHelper::t('已停用');
// 			$WishUser['create_time'] = gmdate('Y-m-d H:i:s', $WishUser['create_time'] + 8 * 3600);
// 			$WishUser['update_time'] = gmdate('Y-m-d H:i:s', $WishUser['update_time'] + 8 * 3600);
			$RumallUserInfoList[] = $RumallUser;
		}
		
		return $this->render('list', [ 'sort'=>$sortConfig , "pagination"=>$pagination , "RumallUserInfoList"=>$RumallUserInfoList]);
	}

	/**
	 +----------------------------------------------------------
	 * 增加绑定RumallUser账号view层显示
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * log			name			date			note
	 * @author		lwj				2016/04/18		初始化
	 +----------------------------------------------------------
	 **/
	public function actionNew() {
	    $data = [
	        "mode"=>"new",
	    ];
		return $this->renderAjax('newOrEdit', $data);
	}

	/**
	 +----------------------------------------------------------
	 * 增加绑定Rumall账号的api信息
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * log			name			date			note
	 * @author		lwj				2016/04/18		初始化
	 +----------------------------------------------------------
	 **/	
	public function actionCreate() {
        list($ret,$message) = RumallAccountsHelper::createRumallAccount($_REQUEST);
        if($ret===false){
            exit (json_encode(array("code"=>"fail","message"=>$message)));
        }else{
            exit (json_encode(array("code"=>"ok","message"=>"")));
        }
		
	}

	/**
	 +----------------------------------------------------------
	 * 查看或编辑Rumall账号信息view层显示
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * log			name			date			note
	 * @author		lwj				2016/04/18		初始化
	 +----------------------------------------------------------
	**/
	public function actionViewOrEdit() {
		$Rumall_id = $_GET['rumall_id'];
		$RumallData = SaasRumallUser::findOne($Rumall_id);
		$RumallData = $RumallData->attributes;	
		if($_GET["mode"] == 'edit'){
		    $data = [
		        "mode"=>$_GET["mode"],
		        "RumallData"=>$RumallData,
		    ];
		}
// 		else{
// 		    $result = RumallOrderInterface::fetchToken();
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
// 		        "RumallData"=>$RumallData,
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
	    if (!isset($_POST["rumall_id"])){
	        exit (json_encode(array("code"=>"fail","message"=>TranslateHelper::t("需要有rumall_id"))));
	    }
	     
	    list($ret,$message) = RumallAccountsHelper::updateRumallAccount($_POST);
	    if  ($ret ===false)  {
	        exit (json_encode(array("code"=>"fail","message"=>$message)));
	    }
	    exit (json_encode(array("code"=>"ok","message"=>"")));
	    
// 	    $accept_result = RumallOrderInterface::acceptOrder('12345');
// 	    $check = strpos($accept_result['message'],'InvalidAuthTokena');
// 	    if($check === false){//至少不是token失败
// 	        if (!isset($_POST["rumall_id"])){
// 	            exit (json_encode(array("code"=>"fail","message"=>TranslateHelper::t("需要有rumall_id"))));
// 	        }
	        
// 	        list($ret,$message) = RumallAccountsHelper::updateRumallAccount($_POST);
// 	        if  ($ret ===false)  {
// 	            exit (json_encode(array("code"=>"fail","message"=>$message)));
// 	        }
// 	        exit (json_encode(array("code"=>"ok","message"=>"")));
// 	    }else{
// 	        exit (json_encode(array("code"=>"fail","message"=>"平台账号没有授权成功，不能创建帐号")));
// 	    }
		
	}

	/**
	 +----------------------------------------------------------
	 * 删除用户绑定的Rumall账户
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		lwj		2016/04/18				初始化
	 +----------------------------------------------------------
	 **/
	public function actionDelete() {
		if (!isset($_POST["rumall_id"])){
			exit (json_encode(array("code"=>"fail","message"=>"需要有Rumall_id")));
		}
		list($ret,$message)=RumallAccountsHelper::deleteRumallAccount($_POST["rumall_id"]);
		if  ($ret ===false)  {
			exit (json_encode(array("code"=>"fail","message"=>$message)));
		}
		exit (json_encode(array("code"=>"ok","message"=>"")));
	}
	

	/**
	 * 设置Rumall账号同步
	 * @author lzhl
	 */
	public function actionSetRumallAccountSync(){
		if (\Yii::$app->request->isPost){
			$user = SaasRumallUser::findOne(['site_id'=> $_POST['setusr']]);
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
