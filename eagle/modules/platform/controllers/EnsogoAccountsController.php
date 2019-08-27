<?php
namespace eagle\modules\platform\controllers;

use yii\web\Controller;
use eagle\models\SaasEnsogoUser;
use yii\data\Sort;
use yii\data\Pagination;
use eagle\modules\util\helpers\TranslateHelper;
use eagle\modules\platform\helpers\EnsogoAccountsHelper;
use eagle\modules\listing\helpers\EnsogoProxyConnectHelper;
use eagle\modules\listing\helpers\EnsogoHelper;
use eagle\modules\platform\apihelpers\EnsogoAccountsApiHelper;
use Qiniu\json_decode;
use eagle\modules\util\helpers\SMSHelper;
use eagle\models\SaasEnsogoStore;
use eagle\modules\app\apihelpers\AppApiHelper;
class EnsogoAccountsController extends \eagle\components\Controller{
		
	public function actionTest(){
		echo "action is ok ";
	}
	
	/**
	 +----------------------------------------------------------------------------------------------------------------------------
	 * wish授权前将PUID， 店铺名保存在session先
	 +----------------------------------------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2014/08/26				初始化
	 +----------------------------------------------------------------------------------------------------------------------------
	 **/
	public function actionSetCreateInfo(){
		if (!empty($_REQUEST['store_name'])){
			$_SESSION['store_name'] = $_REQUEST['store_name'];
		}
		
		$_SESSION['puid'] = \Yii::$app->subdb->getCurrentPuid();
			
		exit(json_encode(['success'=>true,'message'=>'']));
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
		if (!empty($_REQUEST['site_id'])){
			$_SESSION['site_id'] = $_REQUEST['site_id'];
		}
			
		$_SESSION['puid'] = \Yii::$app->subdb->getCurrentPuid();
		
		// TODO proxy dev account @XXX@ 
		$url = "https://merchant.wish.com/oauth/authorize?client_id=@XXX@ ";
		$this->redirect($url);
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
	public function actionGetEnsogoAuthorizationCode(){
		
		if (!empty($_REQUEST['code']) ){
			if (!empty($_SESSION['site_id'])){
				$model = SaasEnsogoUser::findOne(['site_id'=>$_SESSION['site_id']]);
				unset($_SESSION['site_id']);
			}else if (!empty($_SESSION['puid']) && !empty($_SESSION['store_name']) ){
				$model = SaasEnsogoUser::findOne(['uid'=>$_SESSION['puid'] , 'store_name'=>$_SESSION['store_name']]);
				unset($_SESSION['puid']);
				unset($_SESSION['store_name']);
			}
			
			$wishReturn = EnsogoAccountsV2Helper::getEnsogoToken($_REQUEST['code']);
			$result = EnsogoAccountsV2Helper::saveEnsogoToken($model, $wishReturn);
			EnsogoHelper::autoSyncFanbenInfo(); //添加同步队列信息
			return $this->render('successview',['title'=>'绑定成功']);
			
		}//end of if (!empty($_REQUEST['code']) && !empty($_REQUEST['client_id'])) code 和 client id 不能为空
		else {
			return $this->render('errorview',['title'=>'绑定失败']);
			
		}
	}//end of actionGetEnsogoAuthorizationCode
	
	/**
	 +----------------------------------------------------------
	 * 增加绑定Ensogo账号view层显示
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * log			name			date			note
	 * @author		lkh			2015/8/18			初始化
	 +----------------------------------------------------------
	 **/
	public function actionNew() {
		return $this->renderAjax('newOrEdit', array("mode"=>"new"));
	}
	
	/**
	 +----------------------------------------------------------
	 * 查看或编辑Ensogo账号信息view层显示
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * log			name			date			note
	 * @author		dzt				2015/03/04		初始化
	 +----------------------------------------------------------
	 **/
	public function actionViewOrEdit() {
		$Ensogo_id = $_GET['wish_id'];
		$EnsogoData = SaasEnsogoUser::findOne($Ensogo_id);
		$EnsogoData = $EnsogoData->attributes;
		return $this->renderAjax('newOrEdit', array("mode"=>$_GET["mode"],"EnsogoData"=>$EnsogoData));
	}
	
	/**
	 +----------------------------------------------------------
	 * 删除用户绑定的Ensogo账户
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		xjq		2014/09/09				初始化
	 +----------------------------------------------------------
	 **/
	public function actionDelete() {
		if (!isset($_POST["wish_id"])){
			exit (json_encode(array("code"=>"fail","message"=>"需要有Ensogo_id")));
		}
		list($ret,$message)=EnsogoAccountsHelper::deleteEnsogoAccount($_POST["wish_id"]);
		if  ($ret ===false)  {
			exit (json_encode(array("code"=>"fail","message"=>$message)));
		}
		EnsogoHelper::delSyncFanbenInfo($site_id);//解绑触发删除刊登商品信息
		exit (json_encode(array("code"=>"ok","message"=>"")));
	}
	
	/**
	 * 设置Ensogo账号同步
	 * @author lzhl
	 */
	public function actionSetEnsogoAccountSync(){
		if (\Yii::$app->request->isPost){
			$user = SaasEnsogoUser::findOne(['site_id'=> $_POST['setusr']]);
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
	 +----------------------------------------------------------
	 * 增加绑定Ensogo账号的api信息
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * log			name			date			note
	 * @author		lkh				2016/01/16		初始化
	 +----------------------------------------------------------
	 **/
	public function actionCreate() {
		$country_code = 'cn';
		if (!empty($_REQUEST['contact_name'])){
			$contact_name = $_REQUEST['contact_name'];
		}else{
			exit(json_encode(['success'=>false , 'message'=>'联系人不能为空！']));
		}
		
		if (!empty($_REQUEST['phone']) || !empty($_REQUEST['phone_tw_input']) ){
			//大陆电话
			if (!empty($_REQUEST['phone']) )
				$phone = $_REQUEST['phone'];
			//台湾电话
			if (!empty($_REQUEST['phone_tw_input']) ){
				$phone = '+886'.$_REQUEST['phone_tw_input'];
				$country_code = 'tw';
			}
			
			$exist = SaasEnsogoStore::findOne(['moblie_phone'=>$phone]);
			if (!empty($exist)){
				exit(json_encode(['success'=>false , 'message'=>'手机'.$phone.'已经注册！']));
			}
		}else{
			
			exit(json_encode(['success'=>false , 'message'=>'手机不能为空！']));
		}
		
		if (!empty($_REQUEST['email'])){
			$email = $_REQUEST['email'];
			$exist = SaasEnsogoStore::findOne(['email'=>$email]);
			if (!empty($exist)){
				exit(json_encode(['success'=>false , 'message'=>'邮箱'.$phone.'已经注册！']));
			}
		}else{
			exit(json_encode(['success'=>false , 'message'=>'邮箱不能为空！']));
		}
		
		if (!empty($_REQUEST['store_name'])){
			$store_name = $_REQUEST['store_name'];
		}else{
			exit(json_encode(['success'=>false , 'message'=>'店铺名称不能为空！']));
		}
		
		//检查验证码是否正确
		/*20160422kh 屏蔽验证码， 方便台湾电话注册 start 
		if (!empty($_REQUEST['phonecode'])){
			if (empty($_SESSION['ensogo_verify_code'])){
				exit(json_encode(['success'=>false , 'message'=>'请发送验证码到手机！']));
			}
			if ($_REQUEST['phonecode'] == $_SESSION['ensogo_verify_code']){
				$now = time();
				
				if ($now >  $_SESSION['ensogo_verify_code_created_at']  + 60*5){
					//验证码 已经过期
					exit(json_encode(['success'=>false , 'message'=>'验证码 已经过期！']));
				}else{
					$code = $_REQUEST['phonecode'];
				}
			}
			
		}else{
			exit(json_encode(['success'=>false , 'message'=>'验证码不能为空！']));
		}
		20160422kh 屏蔽验证码， 方便台湾电话注册 end */
		$code= "";
		
		
		
		
		$registerRt = EnsogoAccountsHelper::registerEnsogoAccount($contact_name, $phone, $email, $store_name ,$country_code);
		$uid = \Yii::$app->subdb->getCurrentPuid();
		\yii::info('uid:'.$uid.' ensogo register info :'.json_encode($registerRt),'file');
		if ($registerRt['success'] && $registerRt['proxyResponse']['success']){
			if ( $registerRt['proxyResponse']['data']['code'] != 0 ){
				exit(json_encode(['success'=>false , 'message'=>$registerRt['proxyResponse']['data']['message'] ]));
			}
			
			$ensogoReturn = [];
			//success to register
			if (is_string($registerRt['proxyResponse']))
				$ensogoReturn  = json_decode($registerRt['proxyResponse']['data'],true);
			
			if (is_array($registerRt['proxyResponse']))
				$ensogoReturn = $registerRt['proxyResponse']['data'];
			//step 1 save ensogo account
			$EnsogoUserInfo = [
				'token'=>@$ensogoReturn['data']['access_token'] , 
				'store_name'=>$store_name,
				'is_active'=>1,
				'uid'=>$uid,
				'refresh_token'=>@$ensogoReturn['data']['refresh_token'] , 
				'register_by'=>'Y',
				'code'=>$code,
				'seller_id'=>@$ensogoReturn['data']['seller_id'] ,
			];
			//注册 Ensogo 的账号
			$ART = EnsogoAccountsHelper::createEnsogoAccount($EnsogoUserInfo);
			if ($ART['success']==true){
				//step 2 save store info
				$EnsogoStoreInfo = ['site_id'=>$ART['site_id'] , 'store_name'=>$store_name , 'moblie_phone'=>$phone , 'email'=>$email , 'contact_name'=>$contact_name];
				$SRT = EnsogoAccountsHelper::saveEnsogoStoreInfo($EnsogoStoreInfo);
				list($platformUrl,$label)=AppApiHelper::getPlatformMenuData();
				$SRT['platformUrl'] = $platformUrl;
				exit(json_encode($SRT));
			}else{
				exit(json_encode($ART));
			}
			
		}else{
			//failure to register
			exit (json_encode(array("success"=>false,"message"=>"" , 'site_id'=>$site_id)));
		}
	
	}
	
	public function actionBindingGuide(){
		return $this->render('binding_guide');
	}//endofactionGuide
	
	/**
	 +----------------------------------------------------------
	 * Ensogo 发送验证码
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * log			name			date			note
	 * @author		lkh				2016/01/16		初始化
	 +----------------------------------------------------------
	 **/
	public function actionPhonecodesend(){
		if (!empty($_REQUEST['phone'])){
			$_SESSION['uid'] = \Yii::$app->subdb->getCurrentPuid();
			//@todo 检查当前是否存在验证是否存在 ， 存在，则检查 验证码是否过期
			$phoneNumber = $_REQUEST['phone'];
			$rt = EnsogoAccountsHelper::sendVerifyCode($phoneNumber);
			if (!empty($rt['verifycode']) && $rt['success']){
				//保存用户 验证码信息
				$_SESSION['ensogo_verify_code'] = $rt['verifycode'];
				$_SESSION['ensogo_verify_code_created_at'] = time();
			}
			
			if (!empty($rt['verifycode'])) unset($rt['verifycode']);
			//SMSHelper::sendVerifyCode($_REQUEST['phone'], $verifyCode);
			exit(json_encode($rt));
		}else{
			exit(json_encode(['success'=>true,'msg'=>'请输入手机号码！']));
		}
	}//end of actionPhonecodesend
}