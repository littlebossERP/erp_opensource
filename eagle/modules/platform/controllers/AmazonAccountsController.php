<?php

namespace eagle\modules\platform\controllers;

use eagle\models\LoginForm;
use eagle\models\SaasAmazonUser;
use eagle\models\SaasAmazonUserMarketplace;
use eagle\modules\amazon\apihelpers\SaasAmazonAutoSyncApiHelper;
use eagle\modules\app\apihelpers\AppTrackerApiHelper;
use eagle\modules\platform\helpers\AmazonAccountsHelper;
use eagle\modules\platform\models\SaasPlatformUnbind;
use eagle\modules\util\helpers\TranslateHelper;
use yii\data\Pagination;
use yii\data\Sort;
use yii\web\Controller;
use eagle\modules\platform\apihelpers\PlatformAccountApi;


/**
 +------------------------------------------------------------------------------
 * amazon账号模块控制类
 +------------------------------------------------------------------------------
 * @category	Platform
 * @package		Controller/AmazonAccounts
 * @subpackage  Exception
 * @author		hxl <plokplokplok@163.com>
 * @version		1.0
 +------------------------------------------------------------------------------
 */
class AmazonAccountsController extends Controller {
	
	public function behaviors() {
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
				'class' => \yii\filters\VerbFilter::className(),
				'actions' => [
					'delete' => ['post'],
				],
			],
		];
	}
	
	/**
	 * amazon账号列表view层显示
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		dzt		2015/02/26				初始化
	 +----------------------------------------------------------
	**/
	public function actionList() {
		// behaviors 确保 \Yii::$app->user->identity 对象存在
		$saasId = \Yii::$app->user->identity->getParentUid();
		if( empty($saasId) ){
			$model = new LoginForm();
			return $this->render('login', [
				'model' => $model,
			]);
		}
		
		if(!empty($_GET['sort'])){
			$sort = $_GET['sort'];
			if( '-' == substr($sort,0,1) ){
				$sort = substr($sort,1);
				$order = 'desc';
			} else {
				$order = 'asc';
			}
		}

		$sortConfig = new Sort(['attributes' => ['store_name','merchant_id','email','create_time','is_active']]);
		if(empty($sort) || !in_array($sort, array_keys($sortConfig->attributes))){
			$sort = 'create_time';
			$order = 'desc';
		}
		
		$query = SaasAmazonUser::find()->where("uid = '$saasId'");
		
		$pagination = new Pagination([
			'defaultPageSize' => 20,
			'totalCount' => $query->count(),
		]);

		$amazonAllUserInfo = $query
		->offset($pagination->offset)
		->limit($pagination->limit)
		->orderBy($sort.' '.$order)
		->asArray()
		->all();
		
		$amazonUserInfoList = array();
		foreach($amazonAllUserInfo as $amazonUserInfo){
			$amazonUserInfo['create_time'] = gmdate('Y-m-d H:i:s', $amazonUserInfo['create_time'] + 8 * 3600);
			$amazonUserInfo['update_time'] = gmdate('Y-m-d H:i:s', $amazonUserInfo['update_time'] + 8 * 3600);
				
			// 			$amazonEmail=AmazonEmail::model()->find("merchant_id=:merchant_id",array(":merchant_id"=>$amazonUser->merchant_id));
			// 			if ($amazonEmail==null) $amazonUserInfo['email']=""; else $amazonUserInfo['email']=$amazonEmail->email;
		
			$amazonMarketCol = SaasAmazonUserMarketplace::find()->where("amazon_uid=:amazon_uid",array(":amazon_uid" => $amazonUserInfo['amazon_uid']))->all();
			$countryList = "";
			foreach($amazonMarketCol as $amzonMarketplace){
				$countryCode = SaasAmazonAutoSyncApiHelper::$AMAZON_MARKETPLACE_REGION_CONFIG[$amzonMarketplace->marketplace_id];
				$countryList = $countryList." ".SaasAmazonAutoSyncApiHelper::$COUNTRYCODE_NAME_MAP[$countryCode];
		
			}
			$amazonUserInfo["country_list"] = $countryList;
			$amazonUserInfoList[] = $amazonUserInfo;
		}
		
		return $this->render('list', [ 'amazonUserInfoList'=>$amazonUserInfoList , "pagination"=>$pagination , "sort"=>$sortConfig ] );
	}
	
	//打开查看或者编辑amazon账号信息界面------展示amazon api(必填)和amazon email信息
	public function actionViewOrEdit() {
		if(isset($_GET['amazon_uid'])){
		    $puid = \Yii::$app->user->identity->getParentUid();
			$amazonUid = $_GET['amazon_uid'];
			
			$amazonUser = SaasAmazonUser::findOne(['amazon_uid'=>$amazonUid, 'uid'=>$puid]);
			if(empty($amazonUser)){
			    return "ERR:NO AMAZON USER";
			}
			
			//1.获取amazon email信息
// 			$amazonEmail=AmazonEmail::model()->find("merchant_id=:merchant_id",array(":merchant_id"=>$amazonUser->merchant_id));
// 			if ($amazonEmail<>null){
// 				$amazonEmail=$amazonEmail->attributes;
// 				$emailArr=explode("@",$amazonEmail["userid"]);  // userid目前就是email
// 				$amazonEmail["userid"]=$emailArr[0]; 
// 				$amazonEmail["userpass"]=CryptUtil::decrypt($amazonEmail["userpass"],CryptUtil::getSecretSalt());
// 			}
			
			//2.获取amazon api信息
			$userMarketplaceCol = SaasAmazonUserMarketplace::find()->where("amazon_uid=:amazon_uid",array(':amazon_uid'=>$amazonUid))->all();
			$amazonUser = $amazonUser->attributes;
			$chosenCountryList=array();
			foreach($userMarketplaceCol as $userMarketplace){
				$chosenCountryList[]="marketplace_".SaasAmazonAutoSyncApiHelper::$AMAZON_MARKETPLACE_REGION_CONFIG[$userMarketplace->marketplace_id];
				$amazonUser["access_key_id"]=$userMarketplace->access_key_id;
				$amazonUser["secret_access_key"]=$userMarketplace->secret_access_key;
				$amazonUser["mws_auth_token"]=$userMarketplace->mws_auth_token;
			}	
			
			return $this->renderAjax('newOrEditV3', ["mode"=>$_GET["mode"] ,"amazonUser"=>$amazonUser,"chosenCountryList"=>$chosenCountryList/*, "amazonEmail"=>$amazonEmail*/]);
		}else{
		    return "ERR:PARAM NOT FOUND";
		}
		
	}
	
	// 开启/关闭 amazon 账号同步
	public function actionSwitchSync() {
		if (!isset($_POST["amazon_uid"])){
			echo json_encode(array("code"=>"fail","message"=>TranslateHelper::t("需要有amazon_uid")));
			return;
		}
		
		$amazon_uid = trim($_POST['amazon_uid']);
		$status = $_POST['is_active'];
		
		$User_obj = SaasAmazonUser::find()->where('amazon_uid=:a', array(':a'=>$amazon_uid))->andWhere('is_active<>3')->one();
		if($User_obj != null){
			$User_obj->is_active = $status;// 禁用/启用
			$User_obj->update_time = time();
			if(!$User_obj->save()){
				return json_encode(array("code"=>"ok","message"=>print_r($User_obj->getErrors(),true)));
			}else{
				// 订单列表 ， 订单详情同步队列暂停获取数据
				list($ret,$message)=AmazonAccountsHelper::switchSyncAmazonAccount($status,$amazon_uid);
				if  ($ret===false)  {
					return json_encode(array("code"=>"fail","message"=>$message));
				}else{
					return json_encode(array("code"=>"ok","message"=>TranslateHelper::t("操作成功")));
				}
			}
		}else{
			return json_encode(array("code"=>"fail" , "message"=>'操作失败,账号不存在'));
		}
	}
	
	// 只做添加 amazon marketplace 列表的（已弃用）
// 	public function actionAddMarketplaceView() {
// 		if (isset($_GET["amazon_uid"])){

// 			$amazonUid = $_GET['amazon_uid'];
// 			$amazonUser = SaasAmazonUser::findOne($amazonUid);
				
// // 			1.获取amazon email信息
// // 			$amazonEmail=AmazonEmail::model()->find("merchant_id=:merchant_id",array(":merchant_id"=>$amazonUser->merchant_id));
// // 			if ($amazonEmail<>null){
// // 				$amazonEmail=$amazonEmail->attributes;
// // 				$emailArr=explode("@",$amazonEmail["userid"]);  // userid目前就是email
// // 				$amazonEmail["userid"]=$emailArr[0];
// // 				$amazonEmail["userpass"]=CryptUtil::decrypt($amazonEmail["userpass"],CryptUtil::getSecretSalt());
// // 			}
				
// 			//2.获取amazon api信息
// 			$userMarketplaceCol = SaasAmazonUserMarketplace::find()->where("amazon_uid=:amazon_uid",array(':amazon_uid'=>$amazonUid))->all();
// 			$amazonUser = $amazonUser->attributes;
// 			$chosenCountryList=array();
// 			foreach($userMarketplaceCol as $userMarketplace){
// 				$chosenCountryList[]="marketplace_".SaasAmazonAutoSyncApiHelper::$AMAZON_MARKETPLACE_REGION_CONFIG[$userMarketplace->marketplace_id];
// 				$amazonUser["access_key_id"]=$userMarketplace->access_key_id;
// 				$amazonUser["secret_access_key"]=$userMarketplace->secret_access_key;
// 			}

// 			return $this->renderAjax('addMarketplace', ["amazonUser"=>$amazonUser,"chosenCountryList"=>$chosenCountryList]);
				
// 			}
				
				
// 	}
	
	
	/**
	 +----------------------------------------------------------
	 * 增加amazon账号view层显示
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		dzt		2015/02/26				初始化
	 +----------------------------------------------------------
	 **/
	public function actionNew() {
		return $this->renderAjax('newOrEditV3', array("mode"=>"new"));
	}
	
	
	/**
	 +----------------------------------------------------------
	 * 增加amazon账号的api和email信息的行为
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		dzt		2015/02/28				初始化
	 +----------------------------------------------------------
	 **/	
	public function actionCreate() {
		if (!isset(\Yii::$app->user->identity) && \Yii::$app->user->identity->getParentUid() == 0) {
			//用户没登陆导致????
			return json_encode(array("code"=>"ok","message"=>TranslateHelper::t("请退出小老板并重新登录，再进行amazon的绑定!")));
		}
		$puid = \Yii::$app->user->identity->getParentUid();
		$store_name = trim($_POST['store_name']);
		$merchantId = trim($_POST['merchant_id']);
		$saasAmazonUser = SaasAmazonUser::find()->where(["merchant_id"=>$merchantId])->orWhere(["store_name"=>$store_name])->andWhere('uid='.$puid)
		->one();
		
// 		$saasAmazonUser = SaasAmazonUser::find()->where(["merchant_id"=>$merchantId])->orWhere(["store_name"=>$store_name])->andWhere('uid='.$puid)
// 		->createCommand();

		if(!empty($saasAmazonUser)){
			$_POST['amazon_uid'] = $saasAmazonUser->amazon_uid;
			//willage-20170826
			if ($saasAmazonUser->is_active<>3) {
				return json_encode(array("code"=>"fail","message"=>"该账号已存在，不要重复添加（可点击'编辑'修改）"));
			}
			//账号解绑状态才允许update
			list($ret,$message)=AmazonAccountsHelper::updateAmazonAccount($_POST);
		}else{
			list($ret,$message)=AmazonAccountsHelper::createAmazonAccount($_POST);
		}
		
		if  ($ret===false)  {
			return json_encode(array("code"=>"fail","message"=>$message));
		}

		return json_encode(array("code"=>"ok","message"=>""));
		
	}

	/**
	 +----------------------------------------------------------
	 * 编辑amazon的api或者email信息
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		dzt		2015/02/28				初始化
	 +----------------------------------------------------------
	 **/
	public function actionUpdate() {
		if (!isset($_POST["amazon_uid"])){
			echo json_encode(array("code"=>"fail","message"=>TranslateHelper::t("需要有amazon_uid")));
			return;
		}
		
		list($ret,$message)=AmazonAccountsHelper::updateAmazonAccount($_POST);
		if  ($ret===false)  {
			echo json_encode(array("code"=>"fail","message"=>$message));
			return;
		}		
		echo json_encode(array("code"=>"ok","message"=>""));
		
	}	
	
	 /**
     * 解除amazon绑定
     * dzt 2015/12/18
     */
    public function actionUnbind(){
    	$amazon_uid = trim($_POST['amazon_uid']);
    	 
    	$User_obj = SaasAmazonUser::find()->where('amazon_uid=:a', array(':a'=>$amazon_uid))->andWhere('is_active<>3')->one();
    	if($User_obj != null){
    		$User_obj->is_active = 3;// 解绑状态
    		$User_obj->update_time = time();
    		if(!$User_obj->save()){
    			\Yii::trace("Platform,".__CLASS__.",". __FUNCTION__.",平台绑定错误日志： ".print_r($User_obj->getErrors(),true),"file");
    			return json_encode(array("code"=>"fail","message"=>TranslateHelper::t('账号解绑失败，请重试或联系客服')));
    		}else{
    			// 订单列表 ， 订单详情同步队列暂停获取数据
    			AmazonAccountsHelper::switchSyncAmazonAccount(3, $amazon_uid);
    			$unbindAmz=new SaasPlatformUnbind();
    			$unbindAmz->platform_name='AMAZON';
    			$unbindAmz->platform_sellerid=$User_obj->merchant_id;
    			$unbindAmz->puid=$User_obj->uid;
    			$unbindAmz->next_execute_time=time();
    			$unbindAmz->create_time=time();
    			$unbindAmz->update_time=time();
    			$unbindAmz->save(false);
    			//重置账号绑定情况到redis
    			PlatformAccountApi::callbackAfterDeleteAccount('amazon',$User_obj->uid,['selleruserid'=>$User_obj->merchant_id]);
    		}
    	} else {
    		return json_encode(array("code"=>"fail" , "message"=>'操作失败,账号不存在'));
    	}
    	// 记录到  app_user_action_log  表
//     	AppTrackerApiHelper::actionLog("Tracker","/platform/amazonaccounts/unbind");
    
    	return json_encode(array("code"=>"ok","message"=>TranslateHelper::t('账号解除绑定成功')));
    }
	
	public function actionMPlaceidCountrymap(){
		return $this->renderAjax('marketplaceidCountryMap');
	}
	
}
