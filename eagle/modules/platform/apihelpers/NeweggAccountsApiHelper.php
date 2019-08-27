<?php
namespace eagle\modules\platform\apihelpers;

use eagle\models\SaasNeweggUser;
use eagle\modules\util\helpers\ResultHelper;
use eagle\modules\util\helpers\TranslateHelper;
use common\api\newegginterface\NeweggInterface_Helper;
use Qiniu\json_decode;
use eagle\models\SaasNeweggAutosync;
use eagle\modules\util\helpers\SysLogHelper;
class NeweggAccountsApiHelper {
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 返回Newegg账号订单同步信息
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @return array
	 +---------------------------------------------------------------------------------------------
	 * log			name		date				note
	 * @author		winton		2016/07/22			初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	public static function getNeweggOrderSyncInfo($account_key,$uid){
		$account = SaasNeweggUser::find()->where(['site_id'=>$account_key,'uid'=>$uid])->one();
		$autoSync = SaasNeweggAutosync::find()->where(['site_id'=>$account_key,'uid'=>$uid,'type'=>1,'is_active'=>1])->one();

		if($account<>null && $autoSync<>null){
			$result['is_active']=$autoSync->is_active;
			$result['last_time']=$autoSync->last_finish_time;
			$result['message']=$autoSync->message;
				
			$time_past = time()-$result['last_time'];
			if(empty($autoSync->last_finish_time))
				$result['status']= 0;
			else if( $time_past<1800)
				$result['status']= 2;
			elseif($time_past>=1800 && $time_past<3600)
			$result['status']= 1;
			else
				$result['status']= 3;
			
			return  ['success'=>true , 'message'=>'' , 'result'=>$result];
	
		}else{
			return  ['success'=>false , 'message'=>'没有同步信息' , 'result'=>[]];
		}
	}
	/**
	 +---------------------------------------------------------------------------------------------
	 * 创建或修改Newegg账号信息
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @return array
	 +---------------------------------------------------------------------------------------------
	 * log			name		date				note
	 * @author		winton		2016/07/22			初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	public static function saveNeweggAccountInfo($params){
		
		$time = date("Y-m-d H:i:s", time());
		try{
			$saasId = \Yii::$app->user->identity->getParentUid();
			if ($saasId == 0) {
				//用户没登陆导致????
				return array(false,TranslateHelper::t("Err:NE001。请退出小老板并重新登录，再进行newegg的绑定!"));
			}
			//检测必填项是否为空--start
			if(!isset($params['store_name']) || empty($params['store_name'])){
				return ResultHelper::getFailed('', 1, '请输入店铺名称 !');
			}
			if(!isset($params['SellerID']) || empty($params['SellerID'])){
				return ResultHelper::getFailed('', 1, '请输入 Seller ID !');
			}
			if(!isset($params['Authorization']) || empty($params['Authorization'])){
				return ResultHelper::getFailed('', 1, '请输入 Authorization !');
			}
			if(!isset($params['SecretKey']) || empty($params['SecretKey'])){
				return ResultHelper::getFailed('', 1, '请输入 SecretKey !');
			}
			$site_id = '';
			//如果存在site_id，则为更新账号信息
			if(isset($params['site_id']) && !empty($params['site_id'])){
				$neweggAccount = SaasNeweggUser::find()->where(['site_id'=>$params['site_id']])->one();
				if(empty($neweggAccount)){
					return ResultHelper::getFailed('', 1, 'Err:NE002。网络异常，请刷新后再试 !');
				}
				$site_id = $params['site_id'];
			}
			//否则为新建账号信息
			else{
		  
				$neweggAccount = new SaasNeweggUser();
				$neweggAccount->uid = $saasId;
				$neweggAccount->create_time = $time;
				
			}
			
			//判断店铺名称是否重复
			$filteData = SaasNeweggUser::find()
				->where(['store_name' => $params['store_name'],'uid'=>$saasId])
				->andwhere(['not',['site_id'=>$site_id]])
				->one();
			if(!empty($filteData)){
				return ResultHelper::getFailed('', 1, '已存在的店铺名称（不区分大小写），不可重复使用!');
			}
			
			//判断SellerID是否重复
			$filteSellerID = SaasNeweggUser::find()
			->where(['SellerID' => $params['SellerID'],'uid'=>$saasId]);
			if(!empty($site_id))
				$filteSellerID->andwhere(['not',['site_id'=>$site_id]]);
			
			$filteData = $filteSellerID->one();
			if(!empty($filteData)){
				return ResultHelper::getFailed('', 1, '已存在的Seller ID，请不要重复绑定同一个Newegg账号!');
			}
			
			//检测是否正确的授权信息
			$ret = NeweggInterface_Helper::accountStatus($params);
			SysLogHelper::SysLog_Create('platform',__CLASS__, __FUNCTION__,'info',print_r($ret,true));
			if(isset($ret['IsSuccess']) && $ret['IsSuccess']){
				//发现可能没有这个status字段， 暂时按uid需要屏蔽，日后再继续监测	//2017-04-29 lzhl
				if((int)$saasId !== 9556){
					if(isset($ret['ResponseBody']['Status']) && $ret['ResponseBody']['Status'] == 'Active'){
						//验证成功，且账号状态为Active
					}else{
						return ResultHelper::getFailed('', 1, '绑定失败！您的newegg账号状态为 : '.@$ret['ResponseBody']['Status']);
					}
				}
			}else{
				return ResultHelper::getFailed('', 1, '绑定失败！请验证您的授权信息是否填写正确！<br>'.@$ret[0]['Message']);
			}
			$is_active = (isset($params['is_active']) && $params['is_active']);
			//赋值
			$neweggAccount->update_time = $time;
			$neweggAccount->store_name = $params["store_name"];
			$neweggAccount->SellerID = $params['SellerID'];
			$neweggAccount->Authorization = $params['Authorization'];
			$neweggAccount->SecretKey = $params['SecretKey'];
			$neweggAccount->is_active = $is_active;
			
			if($neweggAccount->save()){
				
				//1:新订单， 2:旧订单(Unshipped)， 3:旧订单(Partially Shippe)， 4:旧订单(Shipped)
				$needNewAutoSync = [1,2,3,4];
				$autoSync = SaasNeweggAutosync::find()->where(['site_id'=>$neweggAccount->site_id,'uid'=>$saasId])->all();
				if(!empty($autoSync)){
					foreach ($autoSync as $a){
						$a->is_active = $is_active;
						$a->error_times = 0;
						$a->status = 0;
						$a->update_time = time();
						$a->save(false);
					}
				}else{
					foreach ($needNewAutoSync as $type){
						$a = new SaasNeweggAutosync();
						$a->uid = $saasId;
						$a->site_id = $neweggAccount->site_id;
						$a->is_active = $is_active;
						$a->error_times = 0;
						$a->status = 0;
						$a->create_time = time();
						$a->update_time = time();
						$a->type = $type;
						$a->save(false);
					}
				}
				
				return ResultHelper::getSuccess('', 1, '绑定成功!');
			}
		}catch (\Exception $ex) {
			SysLogHelper::SysLog_Create('platform',__CLASS__, __FUNCTION__,'error',print_r($ex->getMessage(),true));
			return ResultHelper::getFailed('', 1, '绑定过程出错，请联系客服');
		}
		return ResultHelper::getFailed('', 1, 'Err:NE003。网络异常，请稍后再试！'.json_encode($neweggAccount->errors));
	}
	
	public static function getNeweggProductSyncInfo($account_key,$uid){
		$account = SaasNeweggUser::find()->where(['site_id'=>$account_key,'uid'=>$uid])->one();
		return $account;
		if($account<>null){
			$result['is_active']=$account->is_active;
			$result['last_time']=strtotime($account->last_product_retrieve_time);
			$result['message']=$account->product_retrieve_message;
	
			if( (time()-$result['last_time']=strtotime($account->last_product_retrieve_time))<3600 )
				$result['status']= 1;
			else
				$result['status']= 3;
			return  ['success'=>true , 'message'=>'' , 'result'=>$result];
	
		}else{
			return  ['success'=>false , 'message'=>'没有同步信息' , 'result'=>[]];
		}
	}
	
	public static function deleteNeweggAccount($site_id, $store_name=''){
		$saasId = \Yii::$app->user->identity->getParentUid();
		if ($saasId==0) {
			//用户没登陆导致????
			return ResultHelper::getFailed('', 1, "请退出小老板并重新登录，再进行Newegg的解除绑定!");
		}
		$model = SaasNeweggUser::find()->where(['site_id' => $site_id]);
		if(!empty($store_name)){
			$model = $model->andWhere(['store_name' => $store_name]);
		}
		$model = $model->one();
		if(empty($model)){
			return ResultHelper::getFailed('', 1, '找不到对应的newegg账号。'.$store_name);
		}
		$accountID = $model->SellerID;
		if ($model->delete()){
			//消除相关redis数据
			//PlatformAccountApi::delOnePlatformAccountSyncControlData('cdiscount', $accountID);
			 
			//删除autosync
			SaasNeweggAutosync::deleteAll(['site_id'=>$site_id]);
			
			//重置账号绑定情况到redis
			PlatformAccountApi::callbackAfterDeleteAccount('newegg',$saasId);
			try{
				//重置账号绑定情况到redis
				PlatformAccountApi::callbackAfterDeleteAccount('newegg',$saasId, ['selleruserid'=>$accountID]);
			}catch (\Exception $e){
				$message = "Delete Newegg account successed but some callback error:".print_r($e->getMessage());
				return ResultHelper::getFailed('', 1, "Delete Newegg user ". $message);
			}
			
			return ResultHelper::getSuccess('', 1, '解除绑定成功!');
		}else{
			$message = '';
			foreach ($model->errors as $k => $anError){
				$message .= ($message==""?"":"<br>"). $k." error:".$anError[0];
			}
			return ResultHelper::getFailed('', 1, "Delete Newegg user ". $message);
		}
	}
}

?>