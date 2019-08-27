<?php
namespace eagle\modules\platform\controllers; 

use yii\web\Controller;
use eagle\modules\util\helpers\TranslateHelper;
use eagle\modules\platform\helpers\EbayAccountsHelper;
use yii\data\Sort;
use common\api\ebayinterface\token;
use eagle\models\SaasEbayUser;
use eagle\models\SaasEbayAutosyncstatus;
use eagle\modules\app\apihelpers\AppTrackerApiHelper;
use eagle\modules\message\apihelpers\MessageApiHelper;
use eagle\modules\platform\apihelpers\PlatformAccountApi;
use eagle\modules\platform\apihelpers\EbayAccountsApiHelper;
use eagle\modules\util\helpers\RedisHelper;
/*+----------------------------------------------------------------------
| 小老板
+----------------------------------------------------------------------
| Copyright (c) 2011 http://www.xiaolaoban.cn All rights reserved.
+----------------------------------------------------------------------
| Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
+----------------------------------------------------------------------
| Author: hxl <plokplokplok@163.com>
+----------------------------------------------------------------------
| Create Date: 2014-01-30
+----------------------------------------------------------------------
 */

/**
 +------------------------------------------------------------------------------
 * 物流商模块控制类
 +------------------------------------------------------------------------------
 * @category	Platform
 * @package		Controller/EbayAccounts
 * @subpackage  Exception
 * @author		hxl <plokplokplok@163.com>
 * @version		1.0
 +------------------------------------------------------------------------------
 */

class EbayAccountsController extends \eagle\components\Controller {
	private static $ebay_vip_register_limit = ['v1'=>500,'v2'=>500,'v3'=>500]; //vip 用户注册 数量
	private static $ebay_normal_register_limit = 10; //vip 用户注册 数量
	
	 
	/**
	 +----------------------------------------------------------
	 * ebay账号绑定列表view层显示
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
		
		$sortConfig = new Sort(['attributes' => ['ebay_uid','selleruserid','item_status','expiration_time']]);
		if(empty($sort) || !in_array($sort, array_keys($sortConfig->attributes))){
			$sort = 'expiration_time';
			$order = 'desc';
		}
		
		$data = EbayAccountsHelper::helpList($sort , $order);
		if( is_array($data) )
			return $this->render('list', [ 'ebayUserList'=>$data['ebayUserList'] , 'sort'=>$sortConfig , 'pagination'=>$data['pagination'] ]);
		else 
			return $data;
	}

	/**
	 +----------------------------------------------------------
	 * 添加ebay账号view层显示
	 +----------------------------------------------------------
	**/
	public function actionAdd() {
		return $this->renderAjax('add', []);
	}
	
	/**
	 +----------------------------------------------------------
	 * 刊登专用 绑定ebay账号 显示界面
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2016/01/12				初始化
	 +----------------------------------------------------------
	 **/
	public function actionListingAdd() {
		return $this->renderAjax('listingAdd', ['accountname'=>$_REQUEST['accountname']]);
	}

	/**
	 +----------------------------------------------------------
	 * 删除ebay账号ajax数据返回
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		hxl		2014/01/30				初始化
	 +----------------------------------------------------------
	**/
	public function actionDelete() {
		$data = strval(EbayAccountsHelper::helpDelete($_POST['keys']));
		//将记录到  app_user_action_log  表
		AppTrackerApiHelper::actionLog("Tracker","/platform/ebayaccounts/delete");
		exit(json_encode($data));
	}

   /**
	 +----------------------------------------------------------
	 * 绑定卖家账号(第一步)
	 +----------------------------------------------------------
   **/
	public function actionBindseller1(){
		$DevID = EbayAccountsApiHelper::getMinUsedEbayDeveloperAccount();
		$token = new token();
		$token->resetConfig($DevID);
		$token->config ['siteID'] = 0;
		$_SESSION['ebayDevAccountID'] = $token->devAccountID;
		$sessionId = $token->getSessionId();
		if (empty($sessionId)) {
			exit('绑定失败');
		}
		$this->redirect($token->config['tokenUrl'].$sessionId);
	}

   /**
	 +----------------------------------------------------------
	 * 绑定卖家账号(第二步)
	 +----------------------------------------------------------
   **/
	public function actionBindseller2() {
		
		$DevID = $_SESSION['ebayDevAccountID'];
		$t = new token();
		$t->resetConfig($DevID);
		//$t->config['siteID'] = 3;
		$token = $t->getToken ();
		$selleruserid = $t->getConfirmIdentity();
		if (!empty($token) && !empty($selleruserid)) {
			//先查找此ebay帐号是否已经被其他人占用
			//$uid = \Yii::$app->user->id;
			$uid = \Yii::$app->subdb->getCurrentPuid();
			$ebayUser = SaasEbayUser::find()->where('selleruserid=:p', array(':p' => $selleruserid))->one();
			if(!empty($ebayUser) && $ebayUser->uid != $uid) {
				exit(json_encode(['status'=>false , 'msg'=>TranslateHelper::t('操作失败').$selleruserid.TranslateHelper::t('已被其他用户占用')]));
			}
			//release dev account 
			if (!empty($ebayUser->DevAcccountID)){
				EbayAccountsApiHelper::unbindEbayDeveloperAccount($selleruserid);
			}
			//如果没有被其他人占用
			if ($ebayUser = EbayAccountsHelper::saveEbayToken($uid, $selleruserid, $token, strtotime($t->HardExpirationTime),$DevID)) {
				//写入六种状态
                $result = EbayAccountsHelper::AddNewEbayUser($ebayUser, $selleruserid);
                if($result instanceof \Exception){
                    exit(json_encode(['status'=>false , 'msg'=>$result->getMessage()]));
                }
                $rtn = MessageApiHelper::setSaasMsgAutosync($uid, $ebayUser, $selleruserid, 'ebay');
                if('fail' == $rtn['code']){
                	exit(json_encode(['status'=>false , 'msg'=>$rtn['message']]));
                }
                
                //20161124保存ebay 开发者账号与卖家账号关系 
               
                $rtn = EbayAccountsApiHelper::bindEbayDeveloperMapping($selleruserid, $DevID);
                if(false == $rtn['success']){
                	exit(json_encode(['status'=>false , 'msg'=>$rtn['message']]));
                }
                
				exit(json_encode(['status'=>true , 'msg'=>TranslateHelper::t('eBay账户绑定成功')]));
			}
		}
		exit(json_encode(['status'=>false , 'msg'=>TranslateHelper::t('eBay账户绑定失败，请检查操作是否有误，请检查网络连接是否正常。')]));
	}
	
	
	/**
	 +----------------------------------------------------------
	 * 刊登 绑定卖家账号(第一步)
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2017/01/12				初始化
	 +----------------------------------------------------------
	 **/
	public function actionListingbindseller1(){
		$DevID = EbayAccountsApiHelper::getUnuseListingEbayDeveloperAccount();
		if (empty($DevID)){
			exit('绑定失败：小老板刊登名额已满，请联系客服增加名额！');
		}
		$token = new token();
		$token->resetConfig($DevID);
		$token->config ['siteID'] = 0;
		$_SESSION['ebayListingDevAccountID'] = $DevID;
		$sessionId = $token->getSessionId();
		if (empty($sessionId)) {
			exit('绑定失败!error code'.$DevID);
		}
		$this->redirect($token->config['tokenUrl'].$sessionId);
	}
	
	/**
	 +----------------------------------------------------------
	 * 刊登 绑定卖家账号(第二步)
	 +----------------------------------------------------------
	 **/
	public function actionListingbindseller2() {
	
		$DevID = $_SESSION['ebayListingDevAccountID'];
		$t = new token();
		$t->resetConfig($DevID);
		//$t->config['siteID'] = 3;
		$token = $t->getToken ();
		$selleruserid = $t->getConfirmIdentity();
		$ignoreAccount = ['mr.jian'];
		
		if (($_REQUEST['accountname'] != $selleruserid) && (in_array($_REQUEST['accountname'] ,$ignoreAccount ) == false)){
			exit(json_encode(['status'=>false , 'msg'=>TranslateHelper::t('操作失败').$selleruserid.TranslateHelper::t('不能绑定到账号：').$_REQUEST['accountname']]));
		}
		if (!empty($token) && !empty($selleruserid)) {
			//先查找此ebay帐号是否已经被其他人占用
			//$uid = \Yii::$app->user->id;
			$uid = \Yii::$app->subdb->getCurrentPuid();
			$ebayUser = SaasEbayUser::find()->where('selleruserid=:p', array(':p' => $selleruserid))->one();
			if (empty($ebayUser)){
				exit(json_encode(['status'=>false , 'msg'=>TranslateHelper::t('操作失败').$selleruserid.TranslateHelper::t('请先绑定ebay账号，再刊登授权')]));
			}
			
			if(!empty($ebayUser) && $ebayUser->uid != $uid) {
				exit(json_encode(['status'=>false , 'msg'=>TranslateHelper::t('操作失败').$selleruserid.TranslateHelper::t('已被其他用户占用')]));
			}
			//release dev account
			if (!empty($ebayUser->DevAcccountID)){
				//EbayAccountsApiHelper::unbindEbayDeveloperAccount($selleruserid);
			}
			//如果没有被其他人占用
			if ($ebayUser = EbayAccountsHelper::saveListingEbayToken($uid, $selleruserid, $token, strtotime($t->HardExpirationTime),$DevID)) {
				//写入六种状态
				//EbayAccountsHelper::AddNewEbayUser($ebayUser, $selleruserid);
				/*
				$rtn = MessageApiHelper::setSaasMsgAutosync($uid, $ebayUser, $selleruserid, 'ebay');
				if('fail' == $rtn['code']){
					exit(json_encode(['status'=>false , 'msg'=>$rtn['message']]));
				}
				*/
				//20161124保存ebay 开发者账号与卖家账号关系
				 /*
				$rtn = EbayAccountsApiHelper::bindEbayDeveloperMapping($selleruserid, $DevID);
				if(false == $rtn['success']){
					exit(json_encode(['status'=>false , 'msg'=>$rtn['message']]));
				}
				*/
				exit(json_encode(['status'=>true , 'msg'=>TranslateHelper::t('eBay账户绑定成功')]));
			}
		}
		exit(json_encode(['status'=>false , 'msg'=>TranslateHelper::t('eBay账户绑定失败，请检查操作是否有误，请检查网络连接是否正常。')]));
	}
	
	/**
	 * 设置ebay账号同步
	 */
	public function actionSetSync(){
		if (\Yii::$app->request->isPost){
			//$uid = \Yii::$app->user->id;
			$uid = \Yii::$app->subdb->getCurrentPuid();
		 
			$current = 0;
			$limit = 1;
			
			
			if ($current <$limit ||  $_POST['setval'] !='1'){
				$rt = PlatformAccountApi::resetSyncSetting('ebay', $_POST['setusr'], $_POST['setval'], $uid);
				if ($rt['success'] == false){
					exit (json_encode(array('message'=>TranslateHelper::t($rt['message']))));
				}
				 
				exit (json_encode(array('message'=>TranslateHelper::t('设置成功'))));
			}else{
				exit (json_encode(array('message'=>TranslateHelper::t('绑定数量超过上限'.$limit.'，如想增加请联系客服。'))));
			}
			
			
		}
	}
	
	/**
	 * 设置ebay账号刊登同步开启与关闭
	 */
	public function actionListingSetSync(){
		if (\Yii::$app->request->isPost){
			$ebay_uid = $_POST['setusr'];
			$uid = \Yii::$app->subdb->getCurrentPuid();
			//listing_status
 
	 
			$current = 0;
			$limit = 1;
			if ($current <$limit ||  $_POST['setval'] !='1'){
				$ebayUser = SaasEbayUser::find()->where(['ebay_uid'=>$ebay_uid])->one();
				$ebayUser->listing_status = $_POST['setval'];
				$ebayUser->save();
				exit (json_encode(array('message'=>TranslateHelper::t('设置成功'))));
			}else{
				exit (json_encode(array('message'=>TranslateHelper::t('刊登数量超过上限'.$limit.'，如想增加请联系客服。'))));
			}
			
			
			
		}
		
	}//end of function actionListingSetSync
	
	/**
	 * 设置别名 页面显示
	 * hqw 2017/10/09
	 */
	public function actionSetaliasbox(){
		if (!empty($_REQUEST['ebay_uid'] )){
			$account = SaasEbayUser::find()->where(['ebay_uid'=>$_REQUEST['ebay_uid']])->asArray()->one();
			return $this->renderPartial('setalias', ['account'=>$account ]);
		}else{
			return TranslateHelper::t('找不到相关的账号信息');
		}
		 
	}//end of actionSetaliasbox
	
	//保存ebay别名
	public function actionSaveAlias(){
		if (!empty($_REQUEST['ebay_uid'])){
			$account = SaasEbayUser::find()->where(['ebay_uid'=>$_REQUEST['ebay_uid']])->one();
			if ($account->store_name == $_REQUEST['store_name']) return json_encode(['success'=>false , 'message'=>TranslateHelper::t('别名已经是').$_REQUEST['store_name']]);
			$account->store_name = $_REQUEST['store_name'];
			if ($account->save()){
				return json_encode(['success'=>true , 'message'=>'']);
			}else{
				$errors = $account->getErrors();
				$msg = "";
				foreach($errors as $row){
					$msg .= $row;
				}
				return json_encode(['success'=>false , 'message'=>$msg]);
			}
		}else{
			return json_encode(['success'=>false , 'message'=>TranslateHelper::t('找不到相关的账号信息')]);
		}
	}
	
	
}
