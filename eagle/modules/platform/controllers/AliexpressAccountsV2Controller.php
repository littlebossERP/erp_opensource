<?php
namespace eagle\modules\platform\controllers;

use eagle\models\assistant\DpEnable;
use yii\web\Controller;
use eagle\models\SaasAliexpressUser;
use yii\data\Sort;
use yii\data\Pagination;
use eagle\modules\util\helpers\TranslateHelper;
use eagle\models\SaasAliexpressAutosyncV2;
use common\api\aliexpressinterfacev2\AliexpressInterface_Auth_V2;
use eagle\modules\app\apihelpers\AppTrackerApiHelper;
use eagle\models\QueueAliexpressGetorderV2;
use common\api\aliexpressinterfacev2\AliexpressInterface_Helper_V2;
use eagle\modules\message\apihelpers\MessageApiHelper;
use eagle\models\QueueAliexpressGetfinishorder;
use eagle\modules\platform\apihelpers\PlatformAccountApi;
use eagle\models\SaasAliexpressAutosync;
use eagle\models\QueueAliexpressGetorder;
use eagle\models\SaasMessageAutosync;
use common\api\aliexpressinterfacev2\AliexpressInterface_Api_Qimen;
use common\helpers\Helper_Curl;
use eagle\modules\util\helpers\ResultHelper;
class AliexpressAccountsV2Controller extends \eagle\components\Controller
{

    public static $goproxy = 0;
    /**
     * 授权第一步组织请求url向速卖通 提交
     */
    function actionAuth1() {
    	try{
	    	//速卖通账号主键
	    	$user_info = \Yii::$app->user->identity;
	    	if ($user_info['puid']==0){
	    		$uid = $user_info['uid'];
	    	}else {
	    		$uid = $user_info['puid'];
	    	}
	    	
	    	if(empty(self::$goproxy)){
	    	    //app自定义参数，会原样返回，从而知道对应的账号
	    	    $state = 'littleboss_'.$uid;
	    	    // app的入口地址
	    	    // TODO aliexpress app redirect uri
	    	    $redirect_uri = 'http://您的app域名/aliexpress-api-post/code-to-token';
	    	    $ApiAuth = new AliexpressInterface_Auth_V2();
	    	    
	    	    $url = $ApiAuth->startAuthUrl($state, $redirect_uri);
	    	}else{
	    	    $url = "https://auth.littleboss.com/platform/aliexpress-accounts-v2/open-auth1";
	    	    if(!empty($_GET['aliexpress_uid'])){
	    	        $slu = SaasAliexpressUser::findOne($_GET['aliexpress_uid']);
	    	        $url .= "?account=".$slu->sellerloginid;
	    	    }
	    	}
	    	
	    	
	    	\Yii::$app->getResponse()->redirect($url);
    	}catch(\Exception $ex){
    		header("content-type:text/html;charset=utf-8");
    		var_dump($ex->getMessage());return ;
    	}
    }
    
    /**
     * 授权第二步通过code获取访问令牌和长时令牌
     */
    function actionAuthToDb(){
    	set_time_limit(0);
    	 
    	\Yii::info("aliexpress-auth-to-db: ".json_encode($_GET), "file");
    	
    	//返回的code用于去长时令牌和访问令牌
    	$sellerloginid = empty($_GET['sellerloginid']) ? '' : $_GET['sellerloginid'];
    	$state = empty($_GET['state']) ? '' : $_GET['state'];
    	$expire_time = empty($_GET['expire_time']) ? 0 : $_GET['expire_time'];
    	$user_info = \Yii::$app->user->identity;
    	if ($user_info['puid']==0){
    		$uid = $user_info['uid'];
    	}else {
    		$uid = $user_info['puid'];
    	}
    	try{
    		if($state != 'littleboss_'.$uid){
    			throw new \Exception('验证错误，不是通过小老板进行授权！');
    		}
    		if(empty($sellerloginid)){
    			throw new \Exception('账号丢失！');
    		}
   			//保存信息
   			$SAU_obj = SaasAliexpressUser::find()->where(['sellerloginid' => $sellerloginid])->one();
   			if(!empty($SAU_obj)) {
   				if($SAU_obj->uid != $uid){
   					exit('<script type="text/javascript">window.opener.myreload('.json_encode(TranslateHelper::t('操作失败')).','.json_encode($sellerloginid.TranslateHelper::t('已被其他用户占用')).');window.close();</script>');
   					throw new \Exception('操作失败: 已被其他用户占用！');
   				}else{
   					$SAU_obj->update_time = time();
   					$SAU_obj->is_active = 1;
   					$SAU_obj->version = 'v2';
   					
   					//清除旧版订单同步信息
   					SaasAliexpressAutosync::deleteAll(['sellerloginid' => $sellerloginid, 'aliexpress_uid' => $SAU_obj->aliexpress_uid]);
   					QueueAliexpressGetorder::deleteAll(['sellerloginid' => $sellerloginid, 'aliexpress_uid' => $SAU_obj->aliexpress_uid]);
   					QueueAliexpressGetfinishorder::deleteAll(['sellerloginid' => $sellerloginid, 'aliexpress_uid' => $SAU_obj->aliexpress_uid]);
   					SaasMessageAutosync::deleteAll(['sellerloginid' => $sellerloginid, 'platform_uid' => $SAU_obj->aliexpress_uid, 'platform_source' => 'aliexpress', 'type' => 'OrdermsgTime']);
   				}
  			}else{
   				$SAU_obj = new SaasAliexpressUser();
   				$SAU_obj->sellerloginid = $sellerloginid;
   				$SAU_obj->uid = $uid;
   				$SAU_obj->create_time = time();
   				$SAU_obj->update_time = time();
   				$SAU_obj->is_active = 1;
   				//$SAU_obj->addi_info = json_encode($d);
   				$SAU_obj->version = 'v2';
   			}
   			//绑定分配的开发者账号
   			//$SAU_obj->app_key = $dev_account['app_key'];
   			//$SAU_obj->app_secret = $dev_account['app_secret'];
   
   			//$SAU_obj->access_token = $d['access_token'];
   			//$SAU_obj->refresh_token = $d['refresh_token'];
   			$SAU_obj->access_token_timeout = $expire_time;
   			$SAU_obj->refresh_token_timeout = $expire_time;
   			$SAU_obj->save();
   			//绑定成功写入同步订单列表队列
   			//绑定成功写入同步订单列表队列
   			$types = array(
   				'day120',
   				//'finish',
   				//'finish30',
   				'time',
   				'onSelling',
   			);
   			foreach ($types as $type){
   				$SAA_obj = SaasAliexpressAutosyncV2::find()->where('sellerloginid=:sellerloginid and type=:type',array(':sellerloginid'=>$SAU_obj->sellerloginid,':type'=>$type))->one();
   				if (isset($SAA_obj)){//已经有数据，只要更新
   					$binding_time = $SAA_obj->binding_time;
   					$status = $SAA_obj->status;
   					$SAA_obj->is_active = $SAU_obj->is_active;
   					$SAA_obj->status = 0;
   					$SAA_obj->type=$type;
   					$SAA_obj->times=0;
   					$SAA_obj->binding_time=time();
   					$SAA_obj->update_time = time();
   					if ($type == 'finish'){
   						if ($status == 4){
   							$SAA_obj->start_time=$binding_time;
   							$SAA_obj->end_time=0;
   						}else{
   							$SAA_obj->start_time=0;
   							$SAA_obj->end_time=0;
   						}
    					}elseif ($type == 'time'){
   						$SAA_obj->start_time=0;
   						$SAA_obj->end_time=0;
   					}
   					$SAA_obj->save();
   				}else{//新数据，插入一行数据
   					$SAA_obj=new SaasAliexpressAutosyncV2();
   					$SAA_obj->uid = $uid;
   					$SAA_obj->sellerloginid = $SAU_obj->sellerloginid;
   					$SAA_obj->aliexpress_uid = $SAU_obj->aliexpress_uid;
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
   			//绑定账号时，将拉取站内信的app数据一并生成
   			$rtn = MessageApiHelper::setSaasMsgAutosync($uid, $SAU_obj->aliexpress_uid, $SAU_obj->sellerloginid, 'aliexpress');
   			 
   			//绑定账号成功， 回调函数
   			PlatformAccountApi::callbackAfterRegisterAccount('aliexpress',$uid,['selleruserid'=>$SAU_obj->sellerloginid]);
   			if('fail' == $rtn['code']){
   				throw new \Exception('error:'.$rtn['message'],1);
   			}
   			
   			//授权推送消息
   			$api = new AliexpressInterface_Api_Qimen();
   			$response = $api->tmcUserPermit(['id' => $SAU_obj->sellerloginid, 'topics' => 'aliexpress_order_PlaceOrderSuccess,aliexpress_order_RiskControl,aliexpress_order_WaitSellerSendGoods,aliexpress_order_SellerPartSendGoods,aliexpress_order_WaitBuyerAcceptGoods,aliexpress_order_InCancel']);
   			if($response['result_success']){
   				$SAU_obj->is_Push = 1;
   				$SAU_obj->save(false);
   			}
    			 
   			exit('<script type="text/javascript">window.opener.myreload('.json_encode(TranslateHelper::t('操作成功')).','.json_encode($sellerloginid.TranslateHelper::t('绑定成功')).');window.close();</script>');
   			//exit('<script type="text/javascript">alert('.json_encode(TranslateHelper::t('操作成功')).','.json_encode($sellerloginid.TranslateHelper::t('绑定成功')).');window.close();</script>');
    		
    	}catch(\Exception $ex){
    		header("content-type:text/html;charset=utf-8");
    		var_dump($ex->getMessage());die;
    	}
    	 
    }
    
    /**
     * 授权第三步暂时没什么用只是第二步填入了一个app入口地址，请求成功之后会返回到这个地址
     * dzt 2015/03/03
     */
    function actionAuth3(){
    	echo 'SUCCESS';
    }
    
    function actionAuthErr(){
    	try{
	    	$err_code = empty($_GET['err_code']) ? '' : $_GET['err_code'];
	    	$msg = empty($_GET['msg']) ? '系统内部错误，请联系客服！' : $_GET['msg'];
	    	if(!empty($err_code)){
	    		if($err_code == 102){
	    			$msg = '请先到速卖通服务市场，购买小老板ERP服务！';
	    		}
	    		
	    		exit('<script type="text/javascript">window.opener.myreload('.json_encode(TranslateHelper::t('操作失败')).','.json_encode(TranslateHelper::t($msg)).');window.close();</script>');
	    		throw new \Exception($msg);
	    	}
    	}catch(\Exception $ex){
    		header("content-type:text/html;charset=utf-8");
    		var_dump($ex->getMessage());die;
    	}
    }
    
    /**
     * 从小老板获取授权信息，添加账号
     */
    function actionAuth4(){
        if(empty($_POST['account'])){
            return ResultHelper::getResult(400, "", "请输入aliexpress卖家账号。");
        }
    
        try {
    
            $uid = \Yii::$app->subdb->getCurrentPuid();
            $ip = \eagle\helpers\IndexHelper::getClientIP();
            $param = array('account'=>$_POST['account'], 'ip'=>$ip, 'host'=>\Yii::$app->request->hostInfo);
            $rtn = Helper_Curl::post("https://auth.littleboss.com/platform/aliexpress-accounts-v2/open-auth2", $param);
    
//             echo $rtn;
            \Yii::info("actionAuth4:rtn:".$rtn.PHP_EOL."param:".print_r($param, true), "file");
            if(empty($rtn))
                return ResultHelper::getResult(400, "", "获取数据失败。");
    
            $result = json_decode($rtn, true);
            if($result['code'] != 200)
                return ResultHelper::getResult(400, "", "获取数据失败：".$result['message']);
    
    
            $return = $result['data'];
            // 获取卖家账号基本信息，检查账号是否已经授权
    
            $sellerloginid = empty($return['sellerloginid']) ? '' : $return['sellerloginid'];
            $expire_time = empty($return['expire_time']) ? 0 : $return['expire_time'];
            
            
            $SAU_obj = SaasAliexpressUser::find()->where(['sellerloginid' => $sellerloginid])->one();
   			if(!empty($SAU_obj)) {
   				if($SAU_obj->uid != $uid){
   					return ResultHelper::getResult(400, "", "操作失败: 已被其他用户占用！");
   				}else{
   					$SAU_obj->update_time = time();
   					$SAU_obj->is_active = 1;
   					$SAU_obj->version = 'v2';
   					
   				}
  			}else{
   				$SAU_obj = new SaasAliexpressUser();
   				$SAU_obj->sellerloginid = $sellerloginid;
   				$SAU_obj->uid = $uid;
   				$SAU_obj->create_time = time();
   				$SAU_obj->update_time = time();
   				$SAU_obj->is_active = 1;
   				$SAU_obj->version = 'v2';
   			}
   			
   			$SAU_obj->access_token_timeout = $expire_time;
   			$SAU_obj->refresh_token_timeout = $expire_time;
   			$SAU_obj->save();
   			//绑定成功写入同步订单列表队列
   			//绑定成功写入同步订单列表队列
   			$types = array(
   				'day120',
   				'time',
   				'onSelling',
   			);
   			foreach ($types as $type){
   				$SAA_obj = SaasAliexpressAutosyncV2::find()->where('sellerloginid=:sellerloginid and type=:type',array(':sellerloginid'=>$SAU_obj->sellerloginid,':type'=>$type))->one();
   				if (isset($SAA_obj)){//已经有数据，只要更新
   					$binding_time = $SAA_obj->binding_time;
   					$status = $SAA_obj->status;
   					$SAA_obj->is_active = $SAU_obj->is_active;
   					$SAA_obj->status = 0;
   					$SAA_obj->type=$type;
   					$SAA_obj->times=0;
   					$SAA_obj->binding_time=time();
   					$SAA_obj->update_time = time();
   					if ($type == 'finish'){
   						if ($status == 4){
   							$SAA_obj->start_time=$binding_time;
   							$SAA_obj->end_time=0;
   						}else{
   							$SAA_obj->start_time=0;
   							$SAA_obj->end_time=0;
   						}
    					}elseif ($type == 'time'){
   						$SAA_obj->start_time=0;
   						$SAA_obj->end_time=0;
   					}
   					$SAA_obj->save();
   				}else{//新数据，插入一行数据
   					$SAA_obj=new SaasAliexpressAutosyncV2();
   					$SAA_obj->uid = $uid;
   					$SAA_obj->sellerloginid = $SAU_obj->sellerloginid;
   					$SAA_obj->aliexpress_uid = $SAU_obj->aliexpress_uid;
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
   			//绑定账号时，将拉取站内信的app数据一并生成
   			$rtn = MessageApiHelper::setSaasMsgAutosync($uid, $SAU_obj->aliexpress_uid, $SAU_obj->sellerloginid, 'aliexpress');
   			 
   			//绑定账号成功， 回调函数
   			PlatformAccountApi::callbackAfterRegisterAccount('aliexpress',$uid,['selleruserid'=>$SAU_obj->sellerloginid]);
   			if('fail' == $rtn['code']){
   			    return ResultHelper::getResult(400, "", "操作失败error:".$rtn['message']);
   			}
   			
   			//授权推送消息
   			$api = new AliexpressInterface_Api_Qimen();
   			$response = $api->tmcUserPermit(['id' => $SAU_obj->sellerloginid, 'topics' => 'aliexpress_order_PlaceOrderSuccess,aliexpress_order_RiskControl,aliexpress_order_WaitSellerSendGoods,aliexpress_order_SellerPartSendGoods,aliexpress_order_WaitBuyerAcceptGoods,aliexpress_order_InCancel']);
   			if($response['result_success']){
   				$SAU_obj->is_Push = 1;
   				$SAU_obj->save(false);
   			}
    			 
   			return ResultHelper::getResult(200, "", "操作成功:绑定成功");
        }catch(\Exception $ex){
            \Yii::error('file:'.$ex->getFile().'line:'.$ex->getLine()." ".$ex->getMessage(),"file");
    
            return ResultHelper::getResult(400, "", "获取数据失败e:".$ex->getMessage());
        }
    
    }
    
    /**
     * 解除绑定
     * dzt 2015/03/25 add for tracking 
     */
    public function actionDelete(){
    	return false;
    	$aliexpress_uid = trim($_POST['aliexpress_uid']);
    	$sellerloginid = trim($_POST['sellerloginid']);
    	
    	$User_obj = SaasAliexpressUser::find()->where('sellerloginid=:p and aliexpress_uid=:a', array(':p' => $sellerloginid,':a'=>$aliexpress_uid))->one();
    	$SAA_obj = SaasAliexpressAutosyncV2::find()->where('sellerloginid=:p and aliexpress_uid=:a',array(':p' => $sellerloginid,':a'=>$aliexpress_uid))->one();
	    if($User_obj != null){
	    	if(!$User_obj->delete()){
	    		\Yii::trace("Platform,".__CLASS__.",". __FUNCTION__.",平台绑定错误日志： ".print_r($User_obj->getErrors(),true),"file");
	    		return json_encode(array("code"=>"fail","message"=>TranslateHelper::t('删除账号信息失败，请重试或联系客服')));
	    	}else{
	    		$saasId = \Yii::$app->user->identity->getParentUid();
	    		//删除 redis 同步 队列数据
	    		PlatformAccountApi::callbackAfterDeleteAccount('aliexpress', $saasId,['site_id'=>$aliexpress_uid, 'selleruserid'=>$sellerloginid]);
	    		//删除订单列表同步队列数据
	    		SaasAliexpressAutosyncV2::deleteAll(['sellerloginid'=>$sellerloginid,'aliexpress_uid'=>$aliexpress_uid]);
	    		//删除订单详情同步队列数据
	    		QueueAliexpressGetorderV2::deleteAll(['sellerloginid'=>$sellerloginid,'aliexpress_uid'=>$aliexpress_uid]);
	    		
	    		//QueueAliexpressGetorder2V2::deleteAll(['sellerloginid'=>$sellerloginid,'aliexpress_uid'=>$aliexpress_uid]);
	    		
	    		QueueAliexpressGetfinishorder::deleteAll(['sellerloginid'=>$sellerloginid,'aliexpress_uid'=>$aliexpress_uid]);
	    		//删除dp_enable中解绑数据，避免冗余。
	    		DpEnable::deleteAll(['dp_shop_id'=>$sellerloginid,'dp_puid'=>$User_obj->uid]);
	    		
	    		//删除消息同步记录	lzhl	2017-07-25
	    		MessageApiHelper::delSaasMsgAutosync($saasId, $aliexpress_uid, $sellerloginid, 'aliexpress');
	    	}
	    } 	
	    // 记录到  app_user_action_log  表
	    AppTrackerApiHelper::actionLog("Tracker","/platform/aliexpressaccounts/delete");
		
    	return json_encode(array("code"=>"ok","message"=>TranslateHelper::t('账号解除绑定成功')));
    }
    
    /**
     * 设置别名 页面显示
     * lkh 2016/06/15 
     */
    public function actionSetaliasbox(){
    	if (!empty($_REQUEST['uid'] )&& !empty( $_REQUEST['sellerid'])){
    		$account = SaasAliexpressUser::find()->where(['aliexpress_uid'=>$_REQUEST['uid'] , 'sellerloginid'=>$_REQUEST['sellerid']])->asArray()->one();
    		return $this->renderPartial('setalias', ['account'=>$account ]);
    	}else{
    		return TranslateHelper::t('找不到相关的账号信息');
    	}
    	
    }//end of actionSetaliasbox
    
    public function actionSaveAlias(){
    	if (!empty($_REQUEST['aliexpress_uid']) && !empty($_REQUEST['sellerloginid']) ){
    		$account = SaasAliexpressUser::find()->where(['aliexpress_uid'=>$_REQUEST['aliexpress_uid'] , 'sellerloginid'=>$_REQUEST['sellerloginid']])->one();
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