<?php
namespace eagle\modules\platform\helpers;
use eagle\modules\util\helpers\TranslateHelper;
use eagle\models\SaasCdiscountUser;
use eagle\modules\util\helpers\GetControlData;
use eagle\modules\listing\helpers\CdiscountProxyConnectHelper;
use eagle\modules\message\apihelpers\MessageApiHelper;
use eagle\modules\platform\apihelpers\PlatformAccountApi;
use eagle\models\SaasPriceministerUser;
use eagle\modules\util\helpers\DynamicSqlHelper;
use eagle\modules\util\helpers\SysLogHelper;
use eagle\models\SaasPriceministerAutosync;


/**
 +------------------------------------------------------------------------------
 * 物流方式模块业务逻辑类
 +------------------------------------------------------------------------------
 * @category	platform
 * @package		Helper/method
 * @subpackage  Exception
 * @author		lzhl
 * @version		1.0
 +------------------------------------------------------------------------------
 */
class PriceministerAccountsHelper
{
    //用户修改Priceminister账号
    public static function updatePriceministerAccount($params){
    	$saasId = \Yii::$app->user->identity->getParentUid();
    	if ($saasId == 0) {
    		//用户没登陆导致????
    		return array(false,TranslateHelper::t("请退出小老板并重新登录，再进行Priceminister的绑定!"));
    	}

    	$PriceministerData = SaasPriceministerUser::findOne($params["priceminister_id"]);   	
    	if ($PriceministerData==null)  return array(false,TranslateHelper::t("该Priceminister账户不存在"));
		
    	#检查$Priceminister信息的合法性(name,username是否被占用)
		if (empty($params["username"]) or empty($params["token"])){
			return array(false,TranslateHelper::t("Priceminister平台账号、token都不能为空"));
		}
		//username为平台唯一
		$filteData=SaasPriceministerUser::find()->where(array('username' => $params['username']))->andwhere(['not',['site_id'=>$params['priceminister_id']]])->one();
		if ($filteData!==null){
			return array(false,TranslateHelper::t("Priceminister平台账号 已存在（不区分大小写），不能重复使用!"));
		}
		
    	if (empty($params["store_name"])){
    		return array(false,TranslateHelper::t("自定义店铺名  不能为空"));
    	}
		//store_name为用户唯一
		$filteData = SaasCdiscountUser::find()->where(['store_name' => $params['store_name'],'uid'=>$saasId])->andwhere(['not',['site_id'=>$params['priceminister_id']]])->one();
		if ($filteData!==null){
			return array(false,TranslateHelper::t("store_name 已存在（不区分大小写），不能重复使用!"));
		}
		
    	//保存$Priceminister api的变化信息

		$PriceministerData->store_name = $params["store_name"];
		$PriceministerData->username = $params["username"];
		$PriceministerData->token = $params["token"];
		
		$PriceministerData->is_active = $params["is_active"];
		$PriceministerData->update_time = GetControlData::getNowDateTime_str();
		
		
		if ($PriceministerData->save()){
			return array(true,"");
		}else{
			$message = '';
            foreach ($PriceministerData->errors as $k => $anError){
				$message .= "Update Priceminister user ". ($message==""?"":"<br>"). $k." error:".$anError[0];
			}
            return array(false,$message);
        }
    }
    
    //用户绑定Priceminister账号
    public static function createPriceministerAccount($params){
    	$saasId = \Yii::$app->user->identity->getParentUid();
    	if ($saasId==0) {
    		//用户没登陆导致????
    		return array(false,"请退出小老板并重新登录，再进行Priceminister的绑定!");
    	}
    	$info=array();
    	
		#检查Priceminister信息的合法性(store_name,username是否被占用)
		if (empty($params["username"]) or empty($params["token"])){
			return array(false,TranslateHelper::t("Priceminister平台账号、token  都不能为空"));
		}
		$filteData = SaasPriceministerUser::find()->where(array('username' => $params['username']))->one();
		if ($filteData!==null){
			return array(false, TranslateHelper::t("绑定店铺的登录名 已存在（不区分大小写），不能重复使用!"));
		}
		
    	if (empty($params["store_name"])){
    		return array(false,TranslateHelper::t("自定义店铺名  不能为空"));
    	}
    	//store_name为用户唯一
    	$filteData = SaasPriceministerUser::find()->where(['store_name' => $params['store_name'],'uid'=>$saasId])->one();
    	if ($filteData!==null){
    		return array(false,TranslateHelper::t("store_name 已存在（不区分大小写），不能重复使用!"));
    	}
    	
    	#保存Priceminister信息到db
    	$info['store_name'] = $params['store_name'];
    	$info['username'] = $params['username'];
    	$info['token'] = $params['token'];
    	$info['create_time'] = GetControlData::getNowDateTime_str();    	
    	$info['is_active']=$params['is_active'];    	
    	
    	$sql_run = DynamicSqlHelper::run("priceminister");//注册成功后新建priceminister相关的user表
    	if(!$sql_run){
    		SysLogHelper::SysLog_Create('Platform',__CLASS__, __FUNCTION__,'error','DynamicSqlHelper::run("priceminister") return false!');//test liang
    		return array(false,TranslateHelper::t('绑定失败：数据库创建Priceminister相关数据失败！'));
    	}
    	
    	$ret=self::insertPriceministerUserInfo($info,$saasId);
    	if ($ret !==true ){
    		return array(false,$ret);
    	}else{
    		$Pm_Account = SaasPriceministerUser::find()->where(['uid'=>$saasId,'username'=>$info['username']])->one();
    		MessageApiHelper::setSaasMsgAutosync($saasId, $Pm_Account->site_id, $Pm_Account->username, 'priceminister');

    		return array(true,TranslateHelper::t('绑定成功。'));
    	}
    }
	
    
    public static function insertPriceministerUserInfo($info,$saasId){
    	$now = GetControlData::getNowDateTime_str();
    	//time();
    	
    	$user = new SaasPriceministerUser();
    	$user->token = $info['token'];
    	$user->username = $info['username'];
    	$user->store_name = $info['store_name'];
    	
    	$user->create_time = $info['create_time']; 	 
    	$user->update_time = $now;
    	$user->is_active = $info['is_active'];
    	$user->uid = $saasId;
    	
    	if (!$user->save()) {
			$message = '';
            foreach ($user->errors as $k => $anError){
				$message .= "Insert Priceminister user ". ($message==""?"":"<br>"). $k." error:".$anError[0];
			}
			return $message;
    	}
    	PlatformAccountApi::callbackAfterRegisterAccount('priceminister',$saasId);
    	return true;
    }    
    
    
	//用户删除priceminister账号
    public static function deletePriceministerAccount($id) 
    {
		$saasId = \Yii::$app->user->identity->getParentUid();
    	if ($saasId==0) {
    		//用户没登陆导致????
    		return array(false,TranslateHelper::t("请退出小老板并重新登录，再进行Priceminister的账号操作!"));
    	}
		$model = SaasPriceministerUser::findOne($id);
		$accountID = $model->username;
		
		//删除后需要同时删除同步需求
		$del_uid = $model->uid;
		$del_sellerloginid = $model->username;
		
        if ($model->delete()){
        	//消除相关redis数据
        	//PlatformAccountApi::delOnePlatformAccountSyncControlData('priceminister', $accountID);
        	
        	try{
        		//重置账号绑定情况到redis
        		PlatformAccountApi::callbackAfterDeleteAccount('priceminister',$saasId, ['selleruserid'=>$accountID]);
        		//删除同步需求
        		SaasPriceministerAutosync::deleteAll(['uid'=>$del_uid,'sellerloginid'=>$del_sellerloginid]);
        	}catch (\Exception $e){
        		$message = "Delete Priceminister account successed but some callback error:".print_r($e->getMessage());
        		return array(false,$message);
        	}
        	
            return true;
        }else{
            $message = '';
            foreach ($model->errors as $k => $anError){
				$message .= "Delete Priceminister user ". ($message==""?"":"<br>"). $k." error:".$anError[0];
			}
            return array(false,$message);
        }
    }
    
    /**
     * get Priceminister store token
     * @param	string	$username,$password
     * return	array	array(success=>true/false,'message'=>$message,'token'=>token)
     */
    private static function getPriceministerToken($username,$password){
    	$result['success']=false;
    	$result['message']='';
    	if(!empty($username) && !empty($password)){
    		$config = array();
    		$config['username']=$username;
    		$config['password']=$password;
    		$get_param['config'] = json_encode($config);
			
    		$reqInfo=PriceministerProxyConnectHelper::call_Priceminister_api("getTokenID",$get_param,$post_params=array() );
    			
    		if($reqInfo['success']){
    			if($reqInfo['proxyResponse']['success']){
    				$result['success']=true;
    				$result['token'] = $reqInfo['proxyResponse']['tokenMessage'];
    			}else{
    				$result['message']=$reqInfo['proxyResponse']['message'];
    			}
    		}
    		else{
    			$result['message']=$reqInfo['message'];
    		}
    	}
    	return $result;
    }
}
