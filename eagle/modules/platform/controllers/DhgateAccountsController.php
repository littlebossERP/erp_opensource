<?php
namespace eagle\modules\platform\controllers;

use yii\web\Controller;
use yii\data\Sort;
use yii\data\Pagination;
use eagle\modules\util\helpers\TranslateHelper;
//use eagle\models\SaasAliexpressAutosync;
use eagle\modules\app\apihelpers\AppTrackerApiHelper;
//use eagle\models\QueueAliexpressGetorder;
use eagle\models\SaasDhgateUser;
use common\api\dhgateinterface\Auth;
use eagle\models\SaasDhgateAutosync;
use common\api\dhgateinterface\Api;
use common\api\dhgateinterface\Dhgateinterface_Auth;
use common\api\dhgateinterface\Dhgateinterface_Api;
use eagle\modules\dhgate\apihelpers\DhgateApiHelper;
use eagle\models\QueueDhgateGetorder;
use eagle\models\QueueDhgatePendingorder;
use eagle\modules\message\apihelpers\MessageApiHelper;
use eagle\modules\util\helpers\TimeUtil;
use eagle\modules\platform\apihelpers\PlatformAccountApi;

class DhgateAccountsController extends \eagle\components\Controller {
	 
	/**
	 +----------------------------------------------------------
	 * 敦煌账号列表
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		dzt		2015/06/19				
	 +----------------------------------------------------------
	 **/
	public function actionList()
	{
		if(!empty($_GET['sort'])){
			$sort = $_GET['sort'];
			if( '-' == substr($sort,0,1) ){
				$sort = substr($sort,1);
				$order = 'desc';
			} else {
				$order = 'asc';
			}
		}

		$sortConfig = new Sort(['attributes' => ['dhgate_uid','sellerloginid','create_time','is_active','refresh_token_timeout']]);
		if(empty($sort) || !in_array($sort, array_keys($sortConfig->attributes))){
			$sort = 'create_time';
			$order = 'desc';
		}
		
		$user_info = \Yii::$app->user->identity;
		
		if ($user_info['puid']==0){
			$uid = $user_info['uid'];
		}else {
			$uid = $user_info['puid'];
		}
		
		$query = SaasDhgateUser::find()->where(['uid'=>$uid])->andWhere('is_active<>3');

		$pagination = new Pagination([
			'defaultPageSize' => 20,
			'totalCount' => $query->count(),
		]);
		
		$users = $query
		->offset($pagination->offset)
		->limit($pagination->limit)
		->orderBy($sort.' '.$order)
		->asArray()
		->all();
		
		$rtn_users_arr = array();
		//组织列表数据
		foreach ($users as $user){
			switch ($user['is_active']){
				case 0 : 
					$user['is_active'] = TranslateHelper::t('未启用');
					break;
				case 1 : 
					$user['is_active'] = TranslateHelper::t('启用');
					break;
				case 2 : 
					$user['is_active'] = TranslateHelper::t('授权已过期');
					break;
				default:;
			}
			$user['create_time'] = date('Y-m-d H:i:s',$user['create_time']);
			$user['refresh_token_timeout'] = $user['refresh_token_timeout'] > 0?date('Y-m-d H:i:s',$user['refresh_token_timeout']):'未授权';
			$rtn_users_arr[] = $user;
		}
		
		return $this->render('list', [ 'sort'=>$sortConfig , "pagination"=>$pagination , "dhgateUserList"=>$rtn_users_arr]);
	}
	
	/**
	 +----------------------------------------------------------
	 * 敦煌账号详细
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		dzt		2015/06/19
	 +----------------------------------------------------------
	 **/
	public function actionView(){
		if(isset($_GET['dhgate_uid'])){
			$dhgateUid = $_GET['dhgate_uid'];
				
			$dhgateUser = SaasDhgateUser::findOne($dhgateUid);
				
			return $this->renderAjax('add', ["dhgateUser"=>$dhgateUser]);
		}
	}
	
    /**
     +----------------------------------------------------------
     * 修改敦煌账号
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * log			name	date					note
     * @author		dzt		2015/06/19
     +----------------------------------------------------------
     **/
    function actionEdit(){
    	$dhgate_uid = trim($_POST['dhgate_uid']);
    	$sellerloginid = trim($_POST['sellerloginid']);
    	$is_active = trim($_POST['is_active']);
    	
    	$User_obj = SaasDhgateUser::find()->where('dhgate_uid=:a', array(':a'=>$dhgate_uid))->andWhere('is_active<>3')->one();
    	if($User_obj != null){
    		$User_obj->is_active = $is_active;// 禁用/启用
    		$User_obj->update_time = time();
    		if($User_obj->save(false)){
    			$rtn = DhgateApiHelper::SwitchDhgateCronjob($is_active, $dhgate_uid);
    		}else{
    			$rtn = array('success'=>false , 'message'=>print_r($User_obj->getErrors(),true));
    		}
    	}else{
    		$rtn = array('success'=>false , 'message'=>'账号不存在');
    	}
    	
    	
    	if(!empty($rtn['success'])) {
    		exit(json_encode(array("code"=>"ok","message"=>TranslateHelper::t('账号修改成功'))));
    	}else{
    		exit(json_encode(array("code"=>"fail","message"=>TranslateHelper::t('操作失败').$sellerloginid." ".$rtn['message'])));
    	}
    	
    }
    
    
    /**
     * 授权第一步组织请求url向敦煌 提交
     * dzt 2015/06/19
     */
	public function actionAuth1 (){
		try{
			$user_info = \Yii::$app->user->identity;
			if ($user_info['puid']==0){
				$uid = $user_info['uid'];
			}else {
				$uid = $user_info['puid'];
			}
		
			//app自定义参数，会原样返回，从而知道对应的账号
			$state='littleboss_'.$uid;
			$redirect_uri = \Yii::$app->request->hostInfo.\Yii::$app->urlManager->getBaseUrl().'/'.'platform/dhgate-accounts/auth2'; 
			$ApiAuth = new Dhgateinterface_Auth();
			$url = $ApiAuth->getAuthUrl($state,$redirect_uri);
			$this->redirect($url);
		}catch(\Exception $ex){
			var_dump($ex);return ;
		}
		
	}
    
    /**
     * 授权第二步通过code获取访问令牌和长时令牌
     * dzt 2015/06/19
     */
    public function actionAuth2 (){// 先随机创建一个店铺名 保存， 然后再让卖家第三步做修改
		set_time_limit(0);
		ignore_user_abort(true);// 调用敦煌接口有时时间比较长
		//返回的Authorization Code 用于获取access token 和refresh token
		$code = $_GET['code'];
		$state = $_GET['state'];
		$user_info = \Yii::$app->user->identity;
		if ($user_info['puid']==0){
			$uid = $user_info['uid'];
		}else {
			$uid = $user_info['puid'];
		}
		try{
			if($state!='littleboss_'.$uid){
				throw new \Exception('验证错误，不是通过过小老板进行授权',400);// 400 自定义error code
			}elseif(empty($code)){
				throw new \Exception('未取得Authorization Code !',400);
			}
			$redirect_uri= \Yii::$app->request->hostInfo.\Yii::$app->urlManager->getBaseUrl().'/platform/dhgate-accounts/auth3'; // 授权后要回调的URI，即接收access token的URI。
			$dhApi = new Dhgateinterface_Api();
			//使用code获取长时令牌和访问令牌
			$timeMS1 = TimeUtil::getCurrentTimestampMS();
			$return = $dhApi->getToken($code,$redirect_uri);
			$timeMS2 = TimeUtil::getCurrentTimestampMS();
			
			\Yii::info("uid:$uid getToken use_time:".($timeMS2-$timeMS1)." return:".print_r($return,true),"file");
			 
			if(!empty($return['access_token'])){
				
				// 检查是否子账号，目前不允许子账号授权应用
				// $timeMS3 = TimeUtil::getCurrentTimestampMS();
				// $sellerInfo = $dhApi->dh_user_seller_get();
				// $timeMS4 = TimeUtil::getCurrentTimestampMS();
				// \Yii::info("uid:$uid dh_user_seller_get use_time:".($timeMS4-$timeMS3)." return:".print_r($sellerInfo,true),"file");
				
				// if(!isset($sellerInfo['isPowerSeller'])){
				// 	throw new \Exception('error response : '.print_r($sellerInfo,true),401);
				// }else if($sellerInfo['isPowerSeller'] != 0){
				// 	exit('<script type="text/javascript">window.opener.dhgateMyreload('.json_encode(TranslateHelper::t('操作失败,使用敦煌主账号授权。')).');window.close();</script>');
				// }
				
				// 获取卖家账号基本信息，检查账号是否已经授权
				$timeMS5 = TimeUtil::getCurrentTimestampMS();
				$sellerBaseInfo = $dhApi->dh_user_base_get();
				$timeMS6 = TimeUtil::getCurrentTimestampMS();
				\Yii::info("uid:$uid dh_user_base_get use_time:".($timeMS6-$timeMS5)." return:".print_r($sellerBaseInfo,true),"file");
				
				if( !empty($sellerBaseInfo['systemuserbase']) && !empty($sellerBaseInfo['systemuserbase']['nickname']))
					$SDU_obj = SaasDhgateUser::find()->where(['sellerloginid'=>$sellerBaseInfo['systemuserbase']['nickname']])->one();
				else 
					throw new \Exception('error response : '.print_r($sellerBaseInfo,true),401);
				
				
				if(!empty($SDU_obj)) {
					if($SDU_obj->uid != $uid && $SDU_obj->is_active != 3){
						exit('<script type="text/javascript">window.opener.dhgateMyreload('.json_encode(TranslateHelper::t('操作失败')).','.json_encode($sellerBaseInfo['systemuserbase']['nickname'].TranslateHelper::t('已被其他用户占用')).');window.close();</script>');
					}else{
					    $SDU_obj->uid = $uid;
						$SDU_obj->update_time = time();
						$SDU_obj->is_active = 1;
					}
				}else{
					$SDU_obj = new SaasDhgateUser();
					$SDU_obj->uid = $uid;
					$SDU_obj->create_time = time();
					$SDU_obj->update_time = time();
					$SDU_obj->is_active = 1;
				}
				
				$SDU_obj->sellerloginid = $sellerBaseInfo['systemuserbase']['nickname'];
				$SDU_obj->platformuserid = $sellerBaseInfo['systemuserbase']['systemuserid'];
				$SDU_obj->scope = $return['scope'];
				$SDU_obj->access_token = $return['access_token'];
				$SDU_obj->refresh_token = $return['refresh_token'];
				$SDU_obj->access_token_timeout = floor($return['expires_in'] / 1000);// access_token 1天过期
				$SDU_obj->refresh_token_timeout = floor($return['expires_in'] / 1000 + 29 * 86400); // refresh_token 30天过期
				if(!$SDU_obj->save()){
					throw new \Exception('error:'.print_r($SDU_obj->getErrors(),1),402);
				}
				
    			//绑定成功写入同步订单列表队列
    			$types = array(
    					'day120',
    					'finish',
    					'time',
    			);
    			foreach ($types as $type){
	    			$SDA_obj = SaasDhgateAutosync::find()
                        ->where('dhgate_uid=:dhgate_uid and type=:type and uid=:uid',array(':dhgate_uid'=>$SDU_obj->dhgate_uid,':type'=>$type,':uid'=>$uid))
                        ->one();
	    			if (isset($SDA_obj)){//已经有数据，只要更新
	    				$SDA_obj->is_active = $SDU_obj->is_active;
	    				$SDA_obj->status = 0;
	    				$SDA_obj->times=0;
				    	$SDA_obj->update_time = time();
				    	$SDA_obj->last_binding_time = time();
				    	$SDA_obj->save();
	    			}else{//新数据，插入一行数据
	    				$SDA_obj=new SaasDhgateAutosync();
	    				$SDA_obj->uid = $uid;
	    				$SDA_obj->dhgate_uid = $SDU_obj->dhgate_uid;
	    				$SDA_obj->is_active = $SDU_obj->is_active;
	    				$SDA_obj->status = 0;
	    				$SDA_obj->type=$type;
	    				$SDA_obj->times=0;
	    				$SDA_obj->start_time=0;
	    				$SDA_obj->end_time=0;
	    				$SDA_obj->last_time=0;
	    				$SDA_obj->message = '';
	    				$SDA_obj->binding_time=time(); 
	    				$SDA_obj->last_binding_time = time();// binding_time 用于后台订单同步，添加这个last_binding_time for memo作用
	    				$SDA_obj->create_time = time();
	    				$SDA_obj->update_time = time();
	    				$SDA_obj->save();
	    			}
    			}
    			
    			$queueAffectRows = QueueDhgateGetorder::updateAll(['is_active'=>$SDU_obj->is_active,'uid'=>$uid,'status'=>0,'times'=>0,'update_time'=>time()] , ['dhgate_uid'=>$SDU_obj->dhgate_uid]);
    			\Yii::info("QueueDhgateGetorder::updateAll is_active:".$SDU_obj->is_active.",dhgate_uid:".$SDU_obj->dhgate_uid.", affect rows:".$queueAffectRows,"file");
    			$queuePendingRows = QueueDhgatePendingorder::updateAll(['is_active'=>$SDU_obj->is_active,'uid'=>$uid,'status'=>0,'times'=>0,'update_time'=>time()] , ['dhgate_uid'=>$SDU_obj->dhgate_uid]);
    			\Yii::info("QueueDhgatePendingorder::updateAll is_active:".$SDU_obj->is_active.",dhgate_uid:".$SDU_obj->dhgate_uid.", affect rows:".$queuePendingRows,"file");
    			//绑定账号成功， 回调函数
    			PlatformAccountApi::callbackAfterRegisterAccount('dhgate',$uid);
    			//绑定账号时，将拉取站内信的app数据一并生成
    			$rtn = MessageApiHelper::setSaasMsgAutosync($uid, $SDU_obj->dhgate_uid, $SDU_obj->sellerloginid, 'dhgate');
    			if('fail' == $rtn['code']){
    				throw new \Exception('error:'.$rtn['message'],400);
    			}
    			
    			exit('<script type="text/javascript">window.opener.dhgateMyreload('.json_encode(TranslateHelper::t('操作成功')).','.json_encode($sellerBaseInfo['systemuserbase']['nickname'].TranslateHelper::t('绑定成功')).');window.close();</script>');
    			
			}elseif(isset($return['error'])){
// 				{   "error": "invalid_authorization_code",   "error_description": "Authorization Code无效(一个授权码只能使用一次)或者已经过期." }
    			throw new \Exception('Authorization Code无效(一个授权码只能使用一次)或者已经过期.',400);
    		}else{
    			throw new \Exception('error:'.print_r($return,true),401);
    		}
    	}catch(\Exception $ex){
    		\Yii::error('file:'.$ex->getFile().'line:'.$ex->getLine()." ".$ex->getMessage(),"file");
    		if(400 == $ex->getCode()){
    			exit(iconv('UTF-8','GBK',$ex->getMessage()));
    		}
    		
    		if(401 == $ex->getCode()) {
    			exit(iconv('UTF-8','GBK','敦煌接口返回出现异常，请稍后再绑定或与客服沟通.'));
    		}
    		
    		// 402 or other exception
    		exit(iconv('UTF-8','GBK','绑定敦煌账号出现异常，请稍后再绑定或与客服沟通.'));
    	}
    	
    }
    /**
     * 授权第三步暂时没什么用只是第二步填入了一个app入口地址，请求成功之后会返回到这个地址
     * dzt 2015/06/19
     */
    function actionAuth3(){
    	echo 'SUCCESS';
    }
    
    /**
     * 解除绑定
     * dzt 2015/06/25
     */
    public function actionUnbind(){
    	$dhgate_uid = trim($_POST['dhgate_uid']);
    	
    	$User_obj = SaasDhgateUser::find()->where('dhgate_uid=:a', array(':a'=>$dhgate_uid))->andWhere('is_active<>3')->one();
	    if($User_obj != null){
	    	$User_obj->is_active = 3;// 解绑状态
	    	$User_obj->update_time = time();
	    	if(!$User_obj->save(false)){
	    		\Yii::trace("Platform,".__CLASS__.",". __FUNCTION__.",平台绑定错误日志： ".print_r($User_obj->getErrors(),true),"file");
	    		return json_encode(array("code"=>"fail","message"=>TranslateHelper::t('账号解绑失败，请重试或联系客服')));
	    	}else{
	    		$saasId = \Yii::$app->user->identity->getParentUid();
	    		//  解绑 回调函数
	    		PlatformAccountApi::callbackAfterDeleteAccount('dhgate', $saasId,['site_id'=>$dhgate_uid,'selleruserid'=>$User_obj->sellerloginid]);
	    		
	    		// 订单列表 ， 订单详情同步队列暂停获取数据
	    		DhgateApiHelper::SwitchDhgateCronjob(0, $dhgate_uid);
	    	}
	    } else {
    		return json_encode(array("code"=>"fail" , "message"=>'操作失败账号不存在'));
	    }	
	    // 记录到  app_user_action_log  表
	    AppTrackerApiHelper::actionLog("Tracker","/platform/dhgateaccounts/unbind");
		
    	return json_encode(array("code"=>"ok","message"=>TranslateHelper::t('账号解除绑定成功')));
    }
 
}