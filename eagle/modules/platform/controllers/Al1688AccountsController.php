<?php
namespace eagle\modules\platform\controllers;

use yii\web\Controller;
use yii\data\Sort;
use yii\data\Pagination;
use eagle\modules\util\helpers\TranslateHelper;
use eagle\modules\app\apihelpers\AppTrackerApiHelper;
use eagle\modules\platform\apihelpers\PlatformAccountApi;
use common\api\al1688interface\Al1688Interface_Api;
use eagle\models\Saas1688User;
use eagle\models\Saas1688Autosync;
class Al1688AccountsController extends \eagle\components\Controller
{
    
    /**
     * 授权第一步组织请求url向1688 提交
     */
    function actionAuth1() {
    	try{
	    	//1688账号主键
	    	$user_info = \Yii::$app->user->identity;
	    	if ($user_info['puid']==0){
	    		$uid = $user_info['uid'];
	    	}else {
	    		$uid = $user_info['puid'];
	    	}
	    	//app自定义参数，会原样返回，从而知道对应的账号
	    	$state = 'littleboss_'.$uid;
	    	//app的入口地址
	    	if(isset($_SERVER['HTTP_REFERER'])){
	    		$tempu = parse_url($_SERVER['HTTP_REFERER']);
	    	}else{
	    		$tempu = parse_url(\Yii::$app->request->hostInfo);
	    	}
	    	$host = $tempu['host'];
	    	$redirect_uri = $host.\Yii::$app->urlManager->getBaseUrl().'/'.'platform/al1688-accounts/code-to-token';
	    	$ApiAuth = new Al1688Interface_Api();

	    	$url = $ApiAuth->startAuthUrl($state, $redirect_uri);
	    	\Yii::$app->getResponse()->redirect($url);
    	}catch(\Exception $ex){
    		var_dump($ex->getMessage());return ;
    	}
    }
    
    /**
     * 授权第二步通过code获取访问令牌和长时令牌
     */
    function actionCodeToToken(){
    	set_time_limit(0);
    		
    	\Yii::info("1688-auth2: ".json_encode($_GET), "file");
    	//返回的code用于去长时令牌和访问令牌
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
    			throw new \Exception('验证错误，不是通过过小老板进行授权');
    		}elseif(empty($code)){
    			throw new \Exception('未取得 Code !');
    		}
    		//app的入口地址
    		$tempu = parse_url(\Yii::$app->request->hostInfo);
    		$host = $tempu['host'];
    		$redirect_uri='http://'.$host.\Yii::$app->urlManager->getBaseUrl().'platform/Al1688Accounts/Auth3';
    		$ApiAuth = new Al1688Interface_Api();
    		//给用户分配的开发者账号
    		$dev_account = $ApiAuth->getDevAccount();
    
    		//使用code获取长时令牌和访问令牌
    		$d = $ApiAuth->codeToToken($code, $redirect_uri);
    		if(isset($d['access_token'])){
    			//保存信息 
    			$SAU_obj = Saas1688User::find()->where('aliId=:p', array(':p' => $d['aliId']))->one();
    			if(!empty($SAU_obj)) {
	    			if($SAU_obj->uid != $uid){
	    				exit('<script type="text/javascript">window.opener.myreload('.json_encode(TranslateHelper::t('操作失败')).','.json_encode($d['resource_owner'].TranslateHelper::t('已被其他用户占用')).');window.close();</script>');
	    			}else{
	    				$SAU_obj->update_time = time();
	    				$SAU_obj->is_active = 1;
	    			}
    			}else{
    				$SAU_obj = new Saas1688User();
    				$SAU_obj->aliId = $d['aliId'];
    				$SAU_obj->store_name = $d['resource_owner'];
    				$SAU_obj->uid = $uid;
    				$SAU_obj->create_time = time();
    				$SAU_obj->update_time = time();
    				$SAU_obj->is_active = 1;
    			}
                //绑定分配的开发者账号
                $SAU_obj->app_key = $dev_account['app_key'];
                $SAU_obj->app_secret = $dev_account['app_secret'];

                $SAU_obj->memberId = $d['memberId'];
    			$SAU_obj->access_token = $d['access_token'];
    			$SAU_obj->refresh_token = $d['refresh_token'];
    			$SAU_obj->access_token_timeout = time() + 28800; // 设置八小时过期，本来是10个小时过期
    			$SAU_obj->refresh_token_timeout = strtotime(substr($d['refresh_token_timeout'], 0, 8)); //一般长时令牌半年过期
    			$SAU_obj->save();
    			//绑定成功写入同步订单列表队列
    			$types = array(
    				'time',
    			);
    			foreach ($types as $type){
	    			$SAA_obj = Saas1688Autosync::find()->where('aliId=:aliId and type=:type',array(':aliId'=>$SAU_obj->aliId,':type'=>$type))->one();
	    			if (isset($SAA_obj)){//已经有数据，只要更新
	    				$binding_time = $SAA_obj->binding_time;
	    				$status = $SAA_obj->status;
	    				$SAA_obj->is_active = $SAU_obj->is_active;
	    				$SAA_obj->status = 0;
	    				$SAA_obj->type=$type;
	    				$SAA_obj->times=0;
	    				$SAA_obj->binding_time=time();
				    	$SAA_obj->update_time = time();
			    		$SAA_obj->start_time=0;
			    		$SAA_obj->end_time=0;
				    	$SAA_obj->save();
	    			}else{//新数据，插入一行数据
	    				$SAA_obj=new Saas1688Autosync();
	    				$SAA_obj->uid = $uid;
	    				$SAA_obj->aliId = $SAU_obj->aliId;
	    				$SAA_obj->uid_1688 = $SAU_obj->uid_1688;
	    				$SAA_obj->is_active = $SAU_obj->is_active;
	    				$SAA_obj->status = 0;
	    				$SAA_obj->type=$type;
	    				$SAA_obj->times=0;
	    				$SAA_obj->start_time=0;
	    				$SAA_obj->end_time=0;
	    				$SAA_obj->last_time=0;
	    				$SAA_obj->binding_time=time();
	    				$SAA_obj->create_time = time();
	    				$SAA_obj->update_time = time();
	    				$SAA_obj->save();
	    			}
    			}
    			
    			exit('<script type="text/javascript">window.opener.myreload('.json_encode(TranslateHelper::t('操作成功')).','.json_encode($d['resource_owner'].TranslateHelper::t('绑定成功')).');window.close();</script>');
    		}elseif(isset($d['error'])){
    			throw new \Exception(print_r($d,1));
    		}else{
    			throw new \Exception('error:'.print_r($d,true));
    		}
    	}catch(\Exception $ex){
    		var_dump($ex->getMessage());die;
    	}
    }
    
    /**
     * 授权第三步暂时没什么用只是第二步填入了一个app入口地址，请求成功之后会返回到这个地址
     */
    function actionAuth3(){
    	echo 'SUCCESS';
    }
    
    /**
     * 设置别名 页面显示
     */
    public function actionSetaliasbox(){
    	if (!empty($_REQUEST['uid_1688'] )&& !empty( $_REQUEST['aliId'])){
    		$account = Saas1688User::find()->where(['uid_1688'=>$_REQUEST['uid_1688'] , 'aliId'=>$_REQUEST['aliId']])->asArray()->one();
    		return $this->renderPartial('setalias', ['account'=>$account ]);
    	}else{
    		return TranslateHelper::t('找不到相关的账号信息');
    	}
    	
    }//end of actionSetaliasbox
    
    public function actionSaveAlias(){
    	if (!empty($_REQUEST['uid_1688']) && !empty($_REQUEST['aliId']) ){
    		$account = Saas1688User::find()->where(['uid_1688'=>$_REQUEST['uid_1688'] , 'aliId'=>$_REQUEST['aliId']])->one();
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
    
    /**
     * 解除绑定
     * dzt 2015/03/25 add for tracking
     */
    public function actionDelete(){
    	$uid_1688 = trim($_POST['uid_1688']);
    	$aliId = trim($_POST['aliId']);
    	 
    	$User_obj = Saas1688User::find()->where('aliId=:p and uid_1688=:a', array(':p' => $aliId,':a'=>$uid_1688))->one();
    	$SAA_obj = Saas1688Autosync::find()->where('aliId=:p and uid_1688=:a',array(':p' => $aliId,':a'=>$uid_1688))->one();
    	if($User_obj != null){
    		if(!$User_obj->delete()){
    			\Yii::trace("Platform,".__CLASS__.",". __FUNCTION__.",平台绑定错误日志： ".print_r($User_obj->getErrors(),true),"file");
    			return json_encode(array("code"=>"fail","message"=>TranslateHelper::t('删除账号信息失败，请重试或联系客服')));
    		}else{
    			//删除订单列表同步队列数据
    			Saas1688Autosync::deleteAll(['aliId'=>$aliId,'uid_1688'=>$uid_1688]);
    		}
    	}
    	// 记录到  app_user_action_log  表
    	AppTrackerApiHelper::actionLog("Tracker","/platform/al1688accounts/delete");
    
    	return json_encode(array("code"=>"ok","message"=>TranslateHelper::t('账号解除绑定成功')));
    }
    
    
}