<?php
namespace eagle\modules\platform\controllers;

use yii\data\Sort;
use yii\data\Pagination;
use eagle\modules\util\helpers\TranslateHelper;
use eagle\modules\app\apihelpers\AppTrackerApiHelper;
use eagle\models\SaasLazadaUser;
use eagle\modules\lazada\apihelpers\LazadaApiHelper;
use eagle\modules\platform\apihelpers\PlatformAccountApi;
use eagle\modules\util\helpers\TimeUtil;
use eagle\models\SaasLazadaAutosync;
use eagle\models\QueueLazadaGetorder;
use common\api\lazadainterface\LazadaInterface_Helper_V2;
use eagle\models\SaasLazadaAutosyncV2;
use eagle\models\QueueLazadaGetorderV2;
use common\helpers\Helper_Curl;
use eagle\modules\util\helpers\ResultHelper;
class LazadaAccountsController extends \eagle\components\Controller
{
	/**
	 +----------------------------------------------------------
	 * Lazada账号列表
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

		$sortConfig = new Sort(['attributes' => ['platform_userid','token','lazada_site','create_time','update_time','status']]);
		if(empty($sort) || !in_array($sort, array_keys($sortConfig->attributes))){
			$sort = 'create_time';
			$order = 'desc';
		}
		
		$user_info = \Yii::$app->user->identity;
		
		if ($user_info['puid']==0){
			$puid = $user_info['uid'];
		}else {
			$puid = $user_info['puid'];
		}
		
		$query = SaasLazadaUser::find()->where(['puid'=>$puid,"platform"=>"lazada"])->andWhere('status<>3');

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
		
		$lazadaSite = LazadaApiHelper::getLazadaCountryCodeSiteMapping();
		$rtn_users_arr = array();
		//组织列表数据
		foreach ($users as $user){
			switch ($user['status']){
				case 0 : 
					$user['status'] = TranslateHelper::t('未启用');
					break;
				case 1 : 
					$user['status'] = TranslateHelper::t('启用');
					break;
				case 2 : 
					$user['status'] = TranslateHelper::t('token失效');
					break;
				default:;
			}
			$user['create_time'] = date('Y-m-d H:i:s',$user['create_time']);
			$user['update_time'] = date('Y-m-d H:i:s',$user['update_time']);
			$user['lazada_site'] = $lazadaSite[$user['lazada_site']];
			$rtn_users_arr[] = $user;
		}
		
		return $this->render('list', [ 'sort'=>$sortConfig , "pagination"=>$pagination , "userList"=>$rtn_users_arr,'platform'=>'lazada']);
	}
	
	/**
	 +----------------------------------------------------------
	 * Linio账号列表
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		dzt		2015/11/04
	 +----------------------------------------------------------
	 **/
	public function actionLinioList()
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
	
		$sortConfig = new Sort(['attributes' => ['platform_userid','token','lazada_site','create_time','update_time','status']]);
		if(empty($sort) || !in_array($sort, array_keys($sortConfig->attributes))){
			$sort = 'create_time';
			$order = 'desc';
		}
	
		$user_info = \Yii::$app->user->identity;
	
		if ($user_info['puid']==0){
			$puid = $user_info['uid'];
		}else {
			$puid = $user_info['puid'];
		}
	
		$query = SaasLazadaUser::find()->where(['puid'=>$puid,"platform"=>"linio"])->andWhere('status<>3');
	
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
	
		$linioSite = LazadaApiHelper::getLazadaCountryCodeSiteMapping('linio');
		$rtn_users_arr = array();
		//组织列表数据
		foreach ($users as $user){
			switch ($user['status']){
				case 0 :
					$user['status'] = TranslateHelper::t('未启用');
					break;
				case 1 :
					$user['status'] = TranslateHelper::t('启用');
					break;
				case 2 :
					$user['status'] = TranslateHelper::t('token失效');
					break;
				default:;
			}
			$user['create_time'] = date('Y-m-d H:i:s',$user['create_time']);
			$user['update_time'] = date('Y-m-d H:i:s',$user['update_time']);
			$user['lazada_site'] = $linioSite[$user['lazada_site']];
			$rtn_users_arr[] = $user;
		}
	
		return $this->render('list', [ 'sort'=>$sortConfig , "pagination"=>$pagination , "userList"=>$rtn_users_arr,'platform'=>'linio']);
	}
	
	/**
	 +----------------------------------------------------------
	 * Jumia账号列表
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		dzt		2015/11/04
	 +----------------------------------------------------------
	 **/
	public function actionJumiaList()
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
	
		$sortConfig = new Sort(['attributes' => ['platform_userid','token','lazada_site','create_time','update_time','status']]);
		if(empty($sort) || !in_array($sort, array_keys($sortConfig->attributes))){
			$sort = 'create_time';
			$order = 'desc';
		}
	
		$user_info = \Yii::$app->user->identity;
	
		if ($user_info['puid']==0){
			$puid = $user_info['uid'];
		}else {
			$puid = $user_info['puid'];
		}
	
		$query = SaasLazadaUser::find()->where(['puid'=>$puid,"platform"=>"jumia"])->andWhere('status<>3');
	
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
	
		$jumiaSite = LazadaApiHelper::getLazadaCountryCodeSiteMapping('jumia');
		$rtn_users_arr = array();
		//组织列表数据
		foreach ($users as $user){
			switch ($user['status']){
				case 0 :
					$user['status'] = TranslateHelper::t('未启用');
					break;
				case 1 :
					$user['status'] = TranslateHelper::t('启用');
					break;
				case 2 :
					$user['status'] = TranslateHelper::t('token失效');
					break;
				default:;
			}
			$user['create_time'] = date('Y-m-d H:i:s',$user['create_time']);
			$user['update_time'] = date('Y-m-d H:i:s',$user['update_time']);
			$user['lazada_site'] = $jumiaSite[$user['lazada_site']];
			$rtn_users_arr[] = $user;
		}
	
		return $this->render('list', [ 'sort'=>$sortConfig , "pagination"=>$pagination , "userList"=>$rtn_users_arr,'platform'=>'jumia']);
	}

	/**
	 +----------------------------------------------------------
	 * 增加绑定Lazada账号view层显示
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * log			name			date			note
	 * @author		dzt				2015/08/19		初始化
	 +----------------------------------------------------------
	 **/
	public function actionNew() {
		$platform = empty($_GET['platform'])?'lazada':$_GET['platform'];
		$lazadaSite = LazadaApiHelper::getLazadaCountryCodeSiteMapping($platform);
		return $this->renderAjax('newOrEdit', array("mode"=>"new",'platform'=>$platform,'lazadaSite'=>$lazadaSite));
	}
	
	/**
	 +----------------------------------------------------------
	 * 增加绑定Lazada账号的api信息
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * log			name			date			note
	 * @author		dzt				2015/08/20		初始化
	 +----------------------------------------------------------
	 **/
	public function actionCreate() {
		list($ret,$message) = LazadaApiHelper::createLazadaAccount($_POST);
		if  ($ret===false)  {
			exit (json_encode(array("code"=>"fail","message"=>$message)));
		}
		exit (json_encode(array("code"=>"ok","message"=>"")));
	
	}
	
	/**
	 +----------------------------------------------------------
	 * 查看或编辑Lazada账号信息view层显示
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * log			name			date			note
	 * @author		dzt				2015/08/20		初始化
	 +----------------------------------------------------------
	 **/
	public function actionViewOrEdit() {
		if(isset($_GET['lazada_uid'])){
			$platform = empty($_GET['platform'])?'lazada':$_GET['platform'];
			$lazada_uid = $_GET['lazada_uid'];
			$User_obj = SaasLazadaUser::find()->where('lazada_uid=:a', array(':a'=>$lazada_uid))->andWhere('status<>3')->one();
	    	if($User_obj != null){
	    		$LazadaUserData = $User_obj->attributes;
	    		$lazadaSite = LazadaApiHelper::getLazadaCountryCodeSiteMapping($platform);
	    		return $this->renderAjax('newOrEdit', array("mode"=>$_GET["mode"],"LazadaUserData"=>$LazadaUserData,'platform'=>$platform,'lazadaSite'=>$lazadaSite));
	    	}else{
	    		$rtn = array('success'=>false , 'message'=>'账号不存在');
	    	}
		}
	}
	
	/**
	 +----------------------------------------------------------
	 * 获取Lazada授权信息view层显示
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * log			name			date			note
	 * @author		dzt				2019/04/10		初始化
	 +----------------------------------------------------------
	 **/
	public function actionGetAuthInfoWindow() {
	    $lazadaSite = LazadaApiHelper::getLazadaCountryCodeSiteMapping('lazada');
	    return $this->renderAjax('getAuthInfoWindow', ["lazadaSite"=>$lazadaSite]);
	}
	
    /**
     +----------------------------------------------------------
     * 修改Lazada账号
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * log			name	date					note
     * @author		dzt		2015/06/19
     +----------------------------------------------------------
     **/
    function actionUpdate(){
    	if (!isset($_POST["lazada_uid"])){
    		exit (json_encode(array("code"=>"fail","message"=>TranslateHelper::t("找不到lazada账号"))));
    	}
	    list($ret,$message) = LazadaApiHelper::updateLazadaAccount($_POST);
    	
    	if  ($ret ===false)  {
    		exit (json_encode(array("code"=>"fail","message"=>$message)));
    	}
    	exit (json_encode(array("code"=>"ok","message"=>"")));
    	
    }
    
    /**
     * 设置Lazada账号同步
     * @author dzt
     */
    public function actionSetLazadaAccountSync(){
        $platform = trim($_POST['platform']);
    	$lazada_uid = trim($_POST['lazada_uid']);
    	$sellerloginid = trim($_POST['platform_userid']);
    	$status = $_POST['status'];
    	$uid = \Yii::$app->user->id;
    	$rtn = PlatformAccountApi::resetSyncSetting($platform, $lazada_uid, $status, $uid);
    	 
    	if(!empty($rtn['success'])) {
    		exit(json_encode(array("code"=>"ok","message"=>TranslateHelper::t('账号修改成功'))));
    	}else{
    		exit(json_encode(array("code"=>"fail","message"=>TranslateHelper::t('操作失败').$sellerloginid." ".$rtn['message'])));
    	}
    }
    
    /**
     * Tracker 解除lazada绑定
     * dzt 2015/08/21
     */
    public function actionUnbind(){
    	$lazada_uid = trim($_POST['lazada_uid']);
    	 
    	$User_obj = SaasLazadaUser::find()->where('lazada_uid=:a', array(':a'=>$lazada_uid))->andWhere('status<>3')->one();
    	if($User_obj != null){
    		$User_obj->status = 3;// 解绑状态
    		$User_obj->update_time = time();
    		if(!$User_obj->save(false)){
    			\Yii::trace("Platform,".__CLASS__.",". __FUNCTION__.",平台绑定错误日志： ".print_r($User_obj->getErrors(),true),"file");
    			return json_encode(array("code"=>"fail","message"=>TranslateHelper::t('账号解绑失败，请重试或联系客服')));
    		}else{
    			// 订单列表 ， 订单详情同步队列暂停获取数据,新旧队列都暂停
    			LazadaApiHelper::SwitchLazadaCronjob(0, $lazada_uid);
    			LazadaApiHelper::SwitchLazadaCronjobV2(0, $lazada_uid);
    			$uid = \Yii::$app->user->id;
    			
    			if ($User_obj->platform == "lazada"){
    			    if(isset(LazadaApiHelper::$COUNTRYCODE_COUNTRYCode2_MAP[$User_obj->lazada_site])){
    			        $countryCode2 = LazadaApiHelper::$COUNTRYCODE_COUNTRYCode2_MAP[$User_obj->lazada_site];
    			    }else{
    			        $newMap = ['id'=>'ID','th'=>'TH'];
    			        $countryCode2 = $newMap[$User_obj->lazada_site];
    			    }
    			}else{
    			    $countryCode2 = strtoupper($User_obj->lazada_site);
    			}
    			//重置账号绑定情况到redis
    			PlatformAccountApi::callbackAfterDeleteAccount($User_obj->platform,$uid,['selleruserid'=>$User_obj->platform_userid, 'order_source_site_id'=>$countryCode2]);
    		}
    	} else {
    		return json_encode(array("code"=>"fail" , "message"=>'操作失败,账号不存在'));
    	}
    	// 记录到  app_user_action_log  表
    	AppTrackerApiHelper::actionLog("Tracker","/platform/lazadaaccounts/unbind");
    
    	return json_encode(array("code"=>"ok","message"=>TranslateHelper::t('账号解除绑定成功')));
    }
    
    /**
     * 授权第一步组织请求url向lazada 提交
     * dzt 2015/06/19
     */
    public function actionAuth1 (){
        try{
//             $user_info = \Yii::$app->user->identity;
//             if ($user_info['puid']==0){
//                 $uid = $user_info['uid'];
//             }else {
//                 $uid = $user_info['puid'];
//             }
            
//             //app自定义参数，会原样返回，从而知道对应的账号
//             $state='littleboss_'.$uid;
//             $redirect_uri = \Yii::$app->request->hostInfo.\Yii::$app->urlManager->getBaseUrl().'/'.'platform/lazada-accounts/auth2';
//             $ApiAuth = new LazadaInterface_Helper_V2();
//             $url = $ApiAuth->getAuthUrl($state,$redirect_uri);
            
            $url = "https://auth.littleboss.com/platform/lazada-accounts/open-auth1";
            if(!empty($_GET['lzd_uid'])){
                $slu = SaasLazadaUser::findOne($_GET['lzd_uid']);
                $url .= "?account=".$slu->platform_userid."_".$slu->lazada_site;
            }
            
            $this->redirect($url);
        }catch(\Exception $ex){
            // var_dump($ex);return ;
			\Yii::info("uid:$uid actionAuth1 Exception:".print_r($ex, true),"file");
			return $this->render('errorview',['title'=>'绑定失败','error'=>$ex->getMessage()]);
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
            $redirect_uri= \Yii::$app->request->hostInfo.\Yii::$app->urlManager->getBaseUrl().'/platform/lazada-accounts/auth3'; // 授权后要回调的URI，即接收access token的URI。
            $dhApi = new LazadaInterface_Helper_V2();
            //使用code获取长时令牌和访问令牌
            $timeMS1 = TimeUtil::getCurrentTimestampMS();
            $response = $dhApi->getToken($code);
//             print_r($response);exit();
            $timeMS2 = TimeUtil::getCurrentTimestampMS();
            \Yii::info("uid:$uid getToken use_time:".($timeMS2-$timeMS1)." return:".print_r($response,true),"file");
    
            if(!empty($response['response']['data']['access_token'])){
                  $return = $response['response']['data'];
                // 获取卖家账号基本信息，检查账号是否已经授权
//                 \Yii::info("uid:$uid dh_user_base_get use_time:".($timeMS6-$timeMS5)." return:".print_r($sellerBaseInfo,true),"file");
    
                $lazada_obj = SaasLazadaUser::find()->where(['platform_userid'=>$return['account'],'lazada_site' => $return['country']])->andWhere('status<>3')->one();
                $newMap = ['id'=>'co.id','th'=>'co.th',];
                if(isset($newMap[$return['country']])&&empty($lazada_obj)){//旧账号的泰国 印尼 国家代码是4位，需要兼容
                    $lazada_obj =  SaasLazadaUser::find()->where(['platform_userid'=>$return['account'],'lazada_site' => $newMap[$return['country']]])->andWhere('status<>3')->one();
                }
                $nowTime = time();
                if(!empty($lazada_obj)) {
                    if($lazada_obj->puid != $uid && $lazada_obj->status != 3){
                        $newCodeMap = array('co.id' => '印尼', 'id' => '印尼', 'my' => '马来西亚', 'ph' => '菲律宾', 'sg' => '新加坡', 'co.th' => '泰国', 'th' => '泰国', 'vn' => '越南');
                        return $this->render('errorview',['title'=>'绑定失败','error'=>"站点：".$newCodeMap[$return['country']].",".$return['account'].TranslateHelper::t('的API账号邮箱已存在，不能重复绑定账号到同一个站点')]);
                    }else{
                        $lazada_obj->puid = $uid;
                        $lazada_obj->update_time = $nowTime;
                        $lazada_obj->status = 1;
                    }
                }else{
					 
					//假如有旧的解绑帐号，就用旧的解绑帐号记录
					$lazada_obj = SaasLazadaUser::find()->where(['platform_userid'=>$return['account'],'lazada_site' => $return['country']])->orderBy(['update_time'=>SORT_DESC])->one();
					if(isset($newMap[$return['country']])&&empty($lazada_obj)){//旧账号的泰国 印尼 国家代码是4位，需要兼容
					    $lazada_obj =  SaasLazadaUser::find()->where(['platform_userid'=>$return['account'],'lazada_site' => $newMap[$return['country']]])->orderBy(['update_time'=>SORT_DESC])->one();
					}
					
					if(empty($lazada_obj)){
					    $lazada_obj = new SaasLazadaUser();
					    $lazada_obj->create_time = $nowTime;
					}
                    
					$lazada_obj->puid = $uid;
                    $lazada_obj->update_time = $nowTime;
                    $lazada_obj->status = 1;
                }
    
                $lazada_obj->platform_userid = $return['account'];
                $lazada_obj->lazada_site = $return['country'];
                if(empty($lazada_obj->token)){//完全的新授权 token不能为空 所以填个值
                    $lazada_obj->token = $return['access_token'];
                }
                $lazada_obj->platform = "lazada";
                $lazada_obj->access_token = $return['access_token'];
                $lazada_obj->refresh_token = $return['refresh_token'];
                $lazada_obj->token_timeout = (time() + $return['expires_in'] - 24 * 3600);// access_token 最好提前1天刷新
                $lazada_obj->refresh_token_timeout = (time() + $return['refresh_expires_in']); // refresh_token 13天过期
                $lazada_obj->country_user_info = json_encode($return['country_user_info']);
                $lazada_obj->account_platform = $return['account_platform'];
                $lazada_obj->version = "v2";//新授权
                if(!$lazada_obj->save()){
                    throw new \Exception('Update Lazada user error:'.print_r($lazada_obj->getErrors(),1)."country_user_info:".print_r($return['country_user_info'],true),402);
                }
    
                // 绑定成功写入autosync表 添加同步订单job  同步job类型:1--获取旧订单,2--获取最新create产生的订单,3--获取最新update产生的订单,4----获取listing。
                $types = array(1, 2, 3, 4, 5);// dzt20151225 lazada刊登上线，3个平台同时加type 4 // dzt20160503 type 5 漏单查找
                foreach ($types as $type) {
                    $SLA_obj = SaasLazadaAutosyncV2::find()->where('lazada_uid=:lazada_uid and type=:type', array(':lazada_uid' => $lazada_obj->lazada_uid, ':type' => $type))->one();
                    if (isset($SLA_obj)) {//已经有数据，只要更新
                        $SLA_obj->puid = $uid;// dzt20160308 重新绑定puid可能换了，这里要重新修改。 puid 2501 改绑lazada_uid 125,126到2501,但这里没有修改puid
                        $SLA_obj->is_active = $lazada_obj->status;
                        $SLA_obj->status = 0;
                        $SLA_obj->error_times = 0;
                        $SLA_obj->update_time = $nowTime;
                        $SLA_obj->last_binding_time = $nowTime;
                        $SLA_obj->save();
                    } else {//新数据，插入一行数据
                        $SLA_obj = new SaasLazadaAutosyncV2();
                        $SLA_obj->puid = $uid;
                        $SLA_obj->lazada_uid = $lazada_obj->lazada_uid;
                        $SLA_obj->platform = $lazada_obj->platform;
                        $SLA_obj->site = $lazada_obj->lazada_site;
                        $SLA_obj->is_active = $lazada_obj->status;// 是否启用
                        $SLA_obj->status = 0; // 同步状态
                        $SLA_obj->type = $type;// 同步job类型
                        $SLA_obj->error_times = 0;
                        $SLA_obj->start_time = 0;// 同步时间段开始时间
                        $SLA_obj->end_time = 0;// 同步时间段结束时间
                        $SLA_obj->last_finish_time = 0;
                
                        if ($type <> 1) $SLA_obj->next_execution_time = $nowTime + 1800;
                        else $SLA_obj->next_execution_time = 0;
                
                        $SLA_obj->message = '';
                        $SLA_obj->binding_time = $nowTime;
                        $SLA_obj->last_binding_time = $nowTime;//最近一次账号的绑定时间,暂时是memo作用
                        $SLA_obj->create_time = $nowTime;
                        $SLA_obj->update_time = $nowTime;
                        if (!$SLA_obj->save()){
                             \Yii::error("lazada autosync V2 create error:" . print_r($SLA_obj->getErrors(), true), "file");
                        }else{//将旧的队列停用
//                            $oldAutosync = SaasLazadaAutosync::updateAll(['is_active' => 0, 'update_time' => $nowTime], ['lazada_uid' => $lazada_obj->lazada_uid]);
//                            \Yii::info("STOP SaasLazadaAutosync::updateAll " . $lazada_obj->status . "," . $lazada_obj->lazada_uid . " .affect rows:" . $oldAutosync, "file");
//                            $oldQueueGetorder = QueueLazadaGetorder::updateAll(['is_active' => 0, 'update_time' => $nowTime], ['lazada_uid' => $lazada_obj->lazada_uid, 'is_active' => 1]);
//                            \Yii::info("STOP QueueLazadaGetorder::updateAll " . $lazada_obj->status . "," . $lazada_obj->lazada_uid . " .affect rows:" . $oldQueueGetorder, "file");
                        }
                            
                    }
                }
                
                $oldAutosync = SaasLazadaAutosync::updateAll(['is_active' => 0, 'update_time' => $nowTime], ['lazada_uid' => $lazada_obj->lazada_uid]);
                \Yii::info("STOP SaasLazadaAutosync::updateAll " . $lazada_obj->status . "," . $lazada_obj->lazada_uid . " .affect rows:" . $oldAutosync, "file");
                $oldQueueGetorder = QueueLazadaGetorder::updateAll(['is_active' => 0, 'update_time' => $nowTime], ['lazada_uid' => $lazada_obj->lazada_uid, 'is_active' => 1]);
                \Yii::info("STOP QueueLazadaGetorder::updateAll " . $lazada_obj->status . "," . $lazada_obj->lazada_uid . " .affect rows:" . $oldQueueGetorder, "file");
                
                // 开启/关闭 已有同步job 新授权
                $queueAffectRows = QueueLazadaGetorderV2::updateAll(['is_active' => $lazada_obj->status, 'update_time' => $nowTime], ['lazada_uid' => $lazada_obj->lazada_uid]);
                \Yii::info("V2 QueueLazadaGetorder::updateAll " . $lazada_obj->status . "," . $lazada_obj->lazada_uid . " .affect rows:" . $queueAffectRows, "file");
                
                if ($lazada_obj->platform == "lazada"){
//                     $countryCode2 = LazadaApiHelper::$COUNTRYCODE_COUNTRYCode2_MAP[$lazada_obj->lazada_site];
                    //新接口，两位国家代码
                    $countryCode2 = strtoupper($lazada_obj->lazada_site);
                }else{
                    $countryCode2 = strtoupper($lazada_obj->lazada_site);
                }
                
                //绑定成功调用
                PlatformAccountApi::callbackAfterRegisterAccount('lazada', $uid, ['selleruserid'=>$lazada_obj->platform_userid, 'order_source_site_id'=>$countryCode2]);
                
                return $this->render('successview',['title'=>'操作成功','message'=>$return['account']."绑定成功。若没填写店铺名，请点击编辑，填写并保存"]);
            }elseif(!$response['success']){
                // 				{   "error": "invalid_authorization_code",   "error_description": "Authorization Code无效(一个授权码只能使用一次)或者已经过期." }
                throw new \Exception($response['message'],400);
            }
        }catch(\Exception $ex){
            \Yii::error('file:'.$ex->getFile().'line:'.$ex->getLine()." ".$ex->getMessage(),"file");
            if(400 == $ex->getCode()){
                exit(iconv('UTF-8','GBK',$ex->getMessage()));
            }
    
            if(401 == $ex->getCode()) {
                exit(iconv('UTF-8','GBK','Lazada接口返回出现异常，请稍后再绑定或与客服沟通.'));
            }
    
            // 402 or other exception
            exit(iconv('UTF-8','GBK','绑定Lazada账号出现异常，请稍后再绑定或与客服沟通.'));
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
     * 从小老板获取授权信息，添加账号
     */
    function actionAuth4(){
        
        if(empty($_POST['account'])){
            return ResultHelper::getResult(400, "", "请输入lazada卖家账号邮箱。");
        }
        
        if(empty($_POST['country'])){
            return ResultHelper::getResult(400, "", "请输入站点。");
        }

        try {
            
            $uid = \Yii::$app->subdb->getCurrentPuid();
            $ip = \eagle\helpers\IndexHelper::getClientIP();
            $param = array('account'=>$_POST['account']."_".$_POST['country'], 'ip'=>$ip, 'host'=>\Yii::$app->request->hostInfo);
            $rtn = Helper_Curl::post("https://auth.littleboss.com/platform/lazada-accounts/open-auth2", $param);
            
//             echo $rtn;
            \Yii::info("actionAuth4:rtn:".$rtn, "file");
            if(empty($rtn))
                return ResultHelper::getResult(400, "", "获取数据失败。");
            
            $result = json_decode($rtn, true);
            if($result['code'] != 200)
                return ResultHelper::getResult(400, "", "获取数据失败：".$result['message']);
            
            
            if(empty($result['data']['access_token'])){
                return ResultHelper::getResult(400, "", "获取授权数据失败：".$result['data']['message']);
            }
                
            $return = $result['data'];
            // 获取卖家账号基本信息，检查账号是否已经授权
        
            $lazada_obj = SaasLazadaUser::find()->where(['platform_userid'=>$return['account'],'lazada_site' => $return['country']])->andWhere('status<>3')->one();
            $nowTime = time();
            $newCodeMap = array('co.id' => '印尼', 'id' => '印尼', 'my' => '马来西亚', 'ph' => '菲律宾', 'sg' => '新加坡', 'co.th' => '泰国', 'th' => '泰国', 'vn' => '越南');
            if(!empty($lazada_obj)) {
                if($lazada_obj->puid != $uid && $lazada_obj->status != 3){
                    return ResultHelper::getResult(400, "", "站点：".$newCodeMap[$return['country']].",".$return['account'].'的API账号邮箱已存在，不能重复绑定账号到同一个站点');
                }else{
                    $lazada_obj->puid = $uid;
                    $lazada_obj->update_time = $nowTime;
                    $lazada_obj->status = 1;
                }
            }else{
            	 
            	//假如有旧的解绑帐号，就用旧的解绑帐号记录
            	$lazada_obj = SaasLazadaUser::find()->where(['platform_userid'=>$return['account'],'lazada_site' => $return['country']])->orderBy(['update_time'=>SORT_DESC])->one();
            	
            	if(empty($lazada_obj)){
            	    $lazada_obj = new SaasLazadaUser();
            	    $lazada_obj->create_time = $nowTime;
            	}
                
            	$lazada_obj->puid = $uid;
                $lazada_obj->update_time = $nowTime;
                $lazada_obj->status = 1;
            }
            
            // 检查返回的授权信息如果比当前的旧就不用覆盖了
            if(($return['get_time'] + $return['refresh_expires_in']) <= $lazada_obj->refresh_token_timeout || 
                    ($return['get_time'] + $return['expires_in'] - 86400) <= $lazada_obj->token_timeout){
                return ResultHelper::getResult(400, "", "站点：".$newCodeMap[$return['country']].",".$return['account'].'的授权信息是旧的，请重新操作授权或者新的授权信息。');
            }
        
            if(empty($lazada_obj->token))
                $lazada_obj->token = $return['access_token'];
            $lazada_obj->platform_userid = $return['account'];
            $lazada_obj->lazada_site = $return['country'];
            $lazada_obj->platform = "lazada";
            $lazada_obj->access_token = $return['access_token'];
            $lazada_obj->refresh_token = $return['refresh_token'];
            $lazada_obj->token_timeout = ($return['get_time'] + $return['expires_in'] - 86400);// access_token 最好提前1天刷新
            $lazada_obj->refresh_token_timeout = ($return['get_time'] + $return['refresh_expires_in']); // refresh_token 13天过期
            $lazada_obj->country_user_info = json_encode($return['country_user_info']);
            $lazada_obj->account_platform = $return['account_platform'];
            $lazada_obj->version = "v2";//新授权
            if(!$lazada_obj->save()){
                throw new \Exception('Update Lazada user error:'.print_r($lazada_obj->getErrors(),1)."country_user_info:".print_r($return['country_user_info'],true),402);
            }
        
            // 绑定成功写入autosync表 添加同步订单job  同步job类型:1--获取旧订单,2--获取最新create产生的订单,3--获取最新update产生的订单,4----获取listing。
            $types = array(1, 2, 3, 4, 5);// dzt20151225 lazada刊登上线，3个平台同时加type 4 // dzt20160503 type 5 漏单查找
            foreach ($types as $type) {
                $SLA_obj = SaasLazadaAutosyncV2::find()->where('lazada_uid=:lazada_uid and type=:type', array(':lazada_uid' => $lazada_obj->lazada_uid, ':type' => $type))->one();
                if (isset($SLA_obj)) {//已经有数据，只要更新
                    $SLA_obj->puid = $uid;// dzt20160308 重新绑定puid可能换了，这里要重新修改。 puid 2501 改绑lazada_uid 125,126到2501,但这里没有修改puid
                    $SLA_obj->is_active = $lazada_obj->status;
                    $SLA_obj->status = 0;
                    $SLA_obj->error_times = 0;
                    $SLA_obj->update_time = $nowTime;
                    $SLA_obj->last_binding_time = $nowTime;
                    $SLA_obj->save();
                } else {//新数据，插入一行数据
                    $SLA_obj = new SaasLazadaAutosyncV2();
                    $SLA_obj->puid = $uid;
                    $SLA_obj->lazada_uid = $lazada_obj->lazada_uid;
                    $SLA_obj->platform = $lazada_obj->platform;
                    $SLA_obj->site = $lazada_obj->lazada_site;
                    $SLA_obj->is_active = $lazada_obj->status;// 是否启用
                    $SLA_obj->status = 0; // 同步状态
                    $SLA_obj->type = $type;// 同步job类型
                    $SLA_obj->error_times = 0;
                    $SLA_obj->start_time = 0;// 同步时间段开始时间
                    $SLA_obj->end_time = 0;// 同步时间段结束时间
                    $SLA_obj->last_finish_time = 0;
            
                    if ($type <> 1) $SLA_obj->next_execution_time = $nowTime + 1800;
                    else $SLA_obj->next_execution_time = 0;
            
                    $SLA_obj->message = '';
                    $SLA_obj->binding_time = $nowTime;
                    $SLA_obj->last_binding_time = $nowTime;//最近一次账号的绑定时间,暂时是memo作用
                    $SLA_obj->create_time = $nowTime;
                    $SLA_obj->update_time = $nowTime;
                    if (!$SLA_obj->save()){
                         \Yii::error("lazada autosync V2 create error:" . print_r($SLA_obj->getErrors(), true), "file");
                    } 
                }
            }
            
            // 关闭 旧同步job
            $oldAutosync = SaasLazadaAutosync::updateAll(['is_active' => 0, 'update_time' => $nowTime], ['lazada_uid' => $lazada_obj->lazada_uid]);
            \Yii::info("STOP SaasLazadaAutosync::updateAll " . $lazada_obj->status . "," . $lazada_obj->lazada_uid . " .affect rows:" . $oldAutosync, "file");
            $oldQueueGetorder = QueueLazadaGetorder::updateAll(['is_active' => 0, 'update_time' => $nowTime], ['lazada_uid' => $lazada_obj->lazada_uid, 'is_active' => 1]);
            \Yii::info("STOP QueueLazadaGetorder::updateAll " . $lazada_obj->status . "," . $lazada_obj->lazada_uid . " .affect rows:" . $oldQueueGetorder, "file");
            
            // 开启/关闭 已有同步job 新授权
            $queueAffectRows = QueueLazadaGetorderV2::updateAll(['is_active' => $lazada_obj->status, 'update_time' => $nowTime], ['lazada_uid' => $lazada_obj->lazada_uid]);
            \Yii::info("V2 QueueLazadaGetorder::updateAll " . $lazada_obj->status . "," . $lazada_obj->lazada_uid . " .affect rows:" . $queueAffectRows, "file");
            
            $countryCode2 = strtoupper($lazada_obj->lazada_site);
            //绑定成功调用
            PlatformAccountApi::callbackAfterRegisterAccount('lazada', $uid, ['selleruserid'=>$lazada_obj->platform_userid, 'order_source_site_id'=>$countryCode2]);
            
        }catch(\Exception $ex){
            \Yii::error('file:'.$ex->getFile().'line:'.$ex->getLine()." ".$ex->getMessage(),"file");
        
            return ResultHelper::getResult(400, "", "获取数据失败e。".$ex->getMessage());
        }
        
        return ResultHelper::getResult(200, "", $return['account']."绑定成功。若没填写店铺名，请点击编辑，填写并保存");
        
    }
    
    
    
    
    
}