<?php
namespace eagle\modules\platform\controllers;

use yii\web\Controller;
use eagle\models\SaasWishUser;
use yii\data\Sort;
use yii\data\Pagination;
use eagle\modules\util\helpers\TranslateHelper;
use eagle\modules\platform\helpers\WishAccountsHelper;
class WishAccountsController extends \eagle\components\Controller{
	/**
	 * Wish账号列表view层显示
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * log			name		date			note
	 * @author		dzt			2015/03/04		初始化
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

		$query = SaasWishUser::find()->where(["uid" => $saasId]);
		
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
		
		$WishUserInfoList = array();
		foreach($data as $WishUser){
			$WishUser['is_active'] = $WishUser['is_active'] == 1 ? TranslateHelper::t('已启用') : TranslateHelper::t('已停用');
// 			$WishUser['create_time'] = gmdate('Y-m-d H:i:s', $WishUser['create_time'] + 8 * 3600);
// 			$WishUser['update_time'] = gmdate('Y-m-d H:i:s', $WishUser['update_time'] + 8 * 3600);
			$WishUserInfoList[] = $WishUser;
		}
		
		return $this->render('list', [ 'sort'=>$sortConfig , "pagination"=>$pagination , "WishUserInfoList"=>$WishUserInfoList]);
	}

	/**
	 +----------------------------------------------------------
	 * 增加绑定Wish账号view层显示
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * log			name			date			note
	 * @author		dzt				2015/03/04		初始化
	 +----------------------------------------------------------
	 **/
	public function actionNew() {
		return $this->renderAjax('newOrEdit', array("mode"=>"new"));
	}

	/**
	 +----------------------------------------------------------
	 * 增加绑定Wish账号的api信息
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * log			name			date			note
	 * @author		dzt				2015/03/04		初始化
	 +----------------------------------------------------------
	 **/	
	public function actionCreate() {
		list($ret,$message)=WishAccountsHelper::createWishAccount($_POST);
		if  ($ret===false)  {
			exit (json_encode(array("code"=>"fail","message"=>$message)));
		}
		exit (json_encode(array("code"=>"ok","message"=>"")));
		
	}

	/**
	 +----------------------------------------------------------
	 * 查看或编辑Wish账号信息view层显示
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * log			name			date			note
	 * @author		dzt				2015/03/04		初始化
	 +----------------------------------------------------------
	**/
	public function actionViewOrEdit() {
		$Wish_id = $_GET['wish_id'];
		$WishData = SaasWishUser::findOne($Wish_id);
		$WishData = $WishData->attributes;	
		return $this->renderAjax('newOrEdit', array("mode"=>$_GET["mode"],"WishData"=>$WishData));
	}

	/**
	 +----------------------------------------------------------
	 * 编辑Wish的账户信息
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		dzt		2015/03/04				初始化
	 +----------------------------------------------------------
	 **/
	public function actionUpdate() {
		if (!isset($_POST["Wish_uid"])){
			exit (json_encode(array("code"=>"fail","message"=>TranslateHelper::t("需要有Wish_uid"))));
		}

		list($ret,$message) = WishAccountsHelper::updateWishAccount($_POST);
		if  ($ret ===false)  {
			exit (json_encode(array("code"=>"fail","message"=>$message)));
		}		
		exit (json_encode(array("code"=>"ok","message"=>"")));
	}

	/**
	 +----------------------------------------------------------
	 * 删除用户绑定的Wish账户
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		xjq		2014/09/09				初始化
	 +----------------------------------------------------------
	 **/
	public function actionDelete() {
		if (!isset($_POST["wish_id"])){
			exit (json_encode(array("code"=>"fail","message"=>"需要有Wish_id")));
		}
		list($ret,$message)=WishAccountsHelper::deleteWishAccount($_POST["wish_id"]);
		if  ($ret ===false)  {
			exit (json_encode(array("code"=>"fail","message"=>$message)));
		}
		exit (json_encode(array("code"=>"ok","message"=>"")));
	}
	

	/**
	 * 设置Wish账号同步
	 * @author lzhl
	 */
	public function actionSetWishAccountSync(){
		if (\Yii::$app->request->isPost){
			$user = SaasWishUser::findOne(['site_id'=> $_POST['setusr']]);
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
	
	/**
	 +----------------------------------------------------------------------------------------------------------------------------
	 * wish授权的第一步
	 +----------------------------------------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2014/08/26				初始化
	 +----------------------------------------------------------------------------------------------------------------------------
	 **/
	public function actionAuth1(){
		
	} 
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * wish redirect uri , 通过 这个action 抓取出 wish  Authorization Code
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param
	 +---------------------------------------------------------------------------------------------
	 * @return
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2015/8/18				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	public function actionGetWishAuthorizationCode(){
		if (!empty($_REQUEST['code']) && !empty($_REQUEST['client_id'])){
			$model = SaasWishUser::findOne(['client_id'=>$_REQUEST['client_id']]);
	
			if (! empty($model)){
				$result = WishAccountsHelper::getWishToken($_REQUEST['client_id'], $model->client_secret, $_REQUEST['code'], $model->redirect_uri);
				if ($result['success']){
					//proxy 层成功
	
					if(!empty($result['wishReturn']) || !empty($result['wishReturn'] )){
						//WISH api 调用 成功
						if (is_string($result['wishReturn'])){
							// json 字符
							$wishReturn = json_decode($result['wishReturn'],true);
						}else if (is_array($result['wishReturn'])){
							//array
							$wishReturn = $result['wishReturn'];
						}else{
							//其他格式  todo
							$wishReturn = [];
						}
							
						if (!empty($wishReturn)){
							$model->code = $_REQUEST['code'];
							$model->token = $wishReturn['access_token'];
							$model->refresh_token = $wishReturn['refresh_token'];
							$model->expires_in = date('Y-m-d H:i:s',$wishReturn['expires_in']);
							$model->expiry_time = date('Y-m-d H:i:s',$wishReturn['expiry_time']);
							if (! $model->save()) {
								//todo write log
								var_dump($model->errors);
							}
						}
							
					}//end of if(!empty($result['wishReturn']) || !empty($result['wishReturn'] )) wish api 返回结果处理
	
				}//end of if ($result['success']) proxy 返回结果
	
			}//end of if (! empty($model)) 找到对应 wish user 账号 的model
	
	
		}//end of if (!empty($_REQUEST['code']) && !empty($_REQUEST['client_id'])) code 和 client id 不能为空
	
	}//end of actionGetWishAuthorizationCode
	
	
}