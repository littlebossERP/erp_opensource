<?php
namespace eagle\modules\platform\helpers;
use eagle\modules\util\helpers\TranslateHelper;
use yii\helpers\VarDumper;
use eagle\modules\platform\apihelpers\PlatformAccountApi;
use eagle\models\SaasShopeeUser;
use common\api\shopeeinterface\ShopeeInterface_Api;
use eagle\models\SaasShopeeAutosync;
use eagle\modules\platform\apihelpers\ShopeeAccountsApiHelper;
use eagle\modules\app\apihelpers\AppTrackerApiHelper;
use eagle\models\QueueShopeeGetorder;


/**
 +------------------------------------------------------------------------------
 * 物流方式模块业务逻辑类
 +------------------------------------------------------------------------------
 */
class ShopeeAccountsHelper{

	private static $COUNTRYCODE_NAME_MAP = array('SG' => '新加坡', 'TW' => '中国台湾', 'ID' => '印度尼西亚', 'MY' => '马来西亚', 'TH' => '泰国', 'VN' => '越南', 'PH' => '菲律宾');// shopee站点
	
	public static function getCountryCodeSiteMapping(){
		return self::$COUNTRYCODE_NAME_MAP;
	}
     
	/**
	 * +---------------------------------------------------------------------------------------------
	 * shopee 订单同步情况 数据
	 * +---------------------------------------------------------------------------------------------
	 * @param $account_key		账号表主键
	 * @param $uid				uid use_base 的id
	 * +---------------------------------------------------------------------------------------------
	 * log            name    date           note
	 * @author        lrq     2018/04/25                初始化
	 * +---------------------------------------------------------------------------------------------
	 **/
	public static function getLastOrderSyncDetail($account_key, $uid = 0){
		if (empty($uid)) {
			$userInfo = \Yii::$app->user->identity;
			if ($userInfo['puid'] == 0) {
				$uid = $userInfo['uid'];
			} else {
				$uid = $userInfo['puid'];
			}
		}
	
		$sync = SaasShopeeAutosync::find()->where(['shopee_uid' => $account_key, 'type' => 'time'])->asArray()->one();
		if (empty($sync)) {
			return ['success' => false, 'message' => '没有同步信息', 'result' => []];
		} else {
			$result['is_active'] = $sync['is_active'];
			$result['last_time'] = $sync['end_time'];
			$result['next_time'] = $sync['next_time'];
			$result['message'] = $sync['message'];
			$result['status'] = $sync['status'];
			return ['success' => true, 'message' => '', 'result' => $result];
		}
	}
	
	/**
	 * +---------------------------------------------------------------------------------------------
	 * shopee 商品同步情况 数据
	 * +---------------------------------------------------------------------------------------------
	 * @param $account_key		账号表主键
	 * @param $uid				uid use_base 的id
	 * +---------------------------------------------------------------------------------------------
	 * log            name    date           note
	 * @author        lrq     2018/04/25                初始化
	 * +---------------------------------------------------------------------------------------------
	 **/
	public static function getLastProductSyncDetail($account_key, $uid = 0){
		if (empty($uid)) {
			$userInfo = \Yii::$app->user->identity;
			if ($userInfo['puid'] == 0) {
				$uid = $userInfo['uid'];
			} else {
				$uid = $userInfo['puid'];
			}
		}
	
		$sync = SaasShopeeAutosync::find()->where(['shopee_uid' => $account_key, 'type' => 'product'])->asArray()->one();
		if (empty($sync)) {
			return ['success' => false, 'message' => '没有同步信息', 'result' => []];
		} else {
			$result['is_active'] = $sync['is_active'];
			$result['last_time'] = $sync['end_time'];
			$result['next_time'] = $sync['next_time'];
			$result['message'] = $sync['message'];
			$result['status'] = $sync['status'];
			return ['success' => true, 'message' => '', 'result' => $result];
		}
	}
	
	/**
	 * +---------------------------------------------------------------------------------------------
	 * 保存 shopee 账号信息
	 * +---------------------------------------------------------------------------------------------
	 * @param $data		
	 * +---------------------------------------------------------------------------------------------
	 * log            name    date           note
	 * @author        lrq     2018/04/26                初始化
	 * +---------------------------------------------------------------------------------------------
	 **/
	
	public static function saveShopeeAccount($data){
		//检测填写信息是否完整
		$is_create = true;
		$cols = ['shopee_uid' => 'shopee_uid', 'store_name' => '店铺名称', 'partner_id' => 'PartnerID', 'shop_id' => 'ShopID', 'secret_key' => 'SecretKey', 'site' => '站点'];
		foreach($cols as $col => $name){
			if($col == 'shopee_uid'){
				if(isset($data[$col]) && empty($data[$col])){
					return self::getResult(false, '账号信息缺失！');
				}
				else if(!empty($data[$col])){
					$is_create = false;
				}
			}
			else{
				if(empty($data[$col]) || trim($data[$col]) == ''){
					return self::getResult(false, $name.' 不能为空！');
				}
				$data[$col] = trim($data[$col]);
			}
		}
		
		$puid = \Yii::$app->user->identity->getParentUid();
 
		
		//判断授权信息是否已被其它客户授权
		$user = SaasShopeeUser::find()->where(['shop_id' => $data['shop_id']])->andWhere("status<>3")->one();
		if(!empty($user)){
			if($user['puid'] != $puid){
				return self::getResult(false, '已有其它客户授权，shop_id: '.$data['shop_id']);
			}
			if(empty($data['shopee_uid']) || $user['shopee_uid'] != $data['shopee_uid']){
				return self::getResult(false, 'shop_id: '.$data['shop_id'].' 已授权');
			}
		}
		
		//检测授权信息是否有效
		$ret_check = self::CheckShopeeInfo($data['shop_id'], $data['partner_id'], $data['secret_key']);
		if(!$ret_check['success']){
			\Yii::info('CheckShopeeInfo err,shop_id:'.$data['shop_id'].',partner_id:'.$data['partner_id'].',secret_key:'.$data['secret_key'].',result:'.$ret_check['result'], 'file');
			return self::getResult(false, $ret_check['msg']);
		}
		
		//保存到user表
		$time = time();
		if(!$is_create){
			$user = SaasShopeeUser::findOne(['shopee_uid' => $data['shopee_uid']]);
			if(empty($user)){
				return self::getResult(false, '账号信息丢失！');
			}
		}
		else{
			$user = SaasShopeeUser::findOne(['shop_id' => $data['shop_id']]);
			if(empty($user)){
				$user = new SaasShopeeUser();
			}
		}
		if($is_create){
			$user->create_time = $time;
			$user->puid = $puid;
			$user->shop_id = $data['shop_id'];
			$user->site = $data['site'];
		}
		
		$user->update_time = $time;
		$user->store_name = $data['store_name'];
		$user->partner_id = $data['partner_id'];
		$user->secret_key = $data['secret_key'];
		$user->status = isset($data['status']) ? $data['status'] : 1;
		if($user->save()){
			//插入到同步job autosync表, time: 定时获取订单，unFinish: 120天内未发货订单, finish: 3天内未完成订单,  product: 同步产品
			$types = ['time', 'unFinish', 'finish', 'product'];
			foreach($types as $type){
				$sync = SaasShopeeAutosync::find()->where(['shop_id' => $user->shop_id, 'type' => $type])->one();
				if($is_create || empty($sync)){
					if(empty($sync)){
						$sync = new SaasShopeeAutosync();
					}
					$sync->shop_id = $user->shop_id;
					$sync->site = $user->site;
					$sync->shopee_uid = $user->shopee_uid;
					$sync->type = $type;
					$sync->status = 0;
					$sync->message = '';
					$sync->create_time = $time;
					$sync->next_time = in_array($type, ['finish', 'product']) ? $time + 1800 : 0;
					$sync->binding_time = $time;
				}
				$sync->puid = $puid;
				$sync->is_active = $user->status;
				$sync->times = 0;
				$sync->update_time = $time;
				if(!$sync->save()){
					return self::getResult(false, '插入同步信息失败！');
				}
			}
			//更新订单队列表状态
			QueueShopeeGetorder::updateAll(['is_active' => 1, 'update_time' => time()], ['shopee_uid' => $user->shopee_uid]);
			
			//绑定成功调用
			if($is_create){
				PlatformAccountApi::callbackAfterRegisterAccount('shopee', $puid, ['selleruserid' => $user->shop_id, 'order_source_site_id' => strtoupper($user->site)]);
			}
			
			return self::getResult(true, '', $user->store_name.',请后续在编辑中完成授权操作.');
		}
		
		return self::getResult(false, '账号信息保存失败！');
		
	}
	
	/**
	 检测授权信息是否有效，用logistics.GetAddress接口验证
	**/
	public static function CheckShopeeInfo($shop_id, $partner_id, $secret_key){
		$api = new ShopeeInterface_Api();
		$api->shop_id = $shop_id;
		$api->partner_id = $partner_id;
		$api->secret_key = $secret_key;
		
		$ret = $api->GetAddress();
		if(!empty($ret['msg'])){
			if(in_array($ret['msg'], ['error_param', 'error_auth'])){
				return self::getResult(false, '授权信息无效！', json_encode($ret));
			}
			else{
				return self::getResult(false, $ret['msg'], json_encode($ret));
			}
		}
		return self::getResult(true);
	}
	
	public static function getResult($success, $msg = '', $result = ''){
		return ['success' => $success, 'msg' => $msg, 'result' => $result];
	}
	
	/**
	 * +---------------------------------------------------------------------------------------------
	 * 变更shopee后台job状态
	 * +---------------------------------------------------------------------------------------------
	 * @param status			状态
	 * @param $shopee_uid		账号表主键
	 * +---------------------------------------------------------------------------------------------
	 * log            name    date           note
	 * @author        lrq     2018/04/26                初始化
	 * +---------------------------------------------------------------------------------------------
	 **/
	public static function SwitchShopeeCronjob($status, $shopee_uid){
		try {
            if (1 == $status) {
                $asyncAffectRows = SaasShopeeAutosync::updateAll(['is_active' => $status, 'status' => 0, 'times' => 0, 'update_time' => time()], ['shopee_uid' => $shopee_uid]);
               \Yii::info("SaasShopeeAutosync::updateAll $status,$shopee_uid .affect rows:" . $asyncAffectRows, "file");

                $queueAffectRows = QueueShopeeGetorder::updateAll(['is_active' => $status, 'status' => 0, 'times' => 0, 'update_time' => time()], ['shopee_uid' => $shopee_uid]);
                \Yii::info("QueueShopeeGetorder::updateAll $status,$shopee_uid .affect rows:" . $queueAffectRows, "file");

            } else {
                $asyncAffectRows = SaasShopeeAutosync::updateAll(['is_active' => $status, 'update_time' => time()], ['shopee_uid' => $shopee_uid]);
                \Yii::info("SaasShopeeAutosync::updateAll $status,$shopee_uid .affect rows:" . $asyncAffectRows, "file");

                $queueAffectRows = QueueShopeeGetorder::updateAll(['is_active' => $status, 'update_time' => time()], ['shopee_uid' => $shopee_uid]);
                \Yii::info("QueueShopeeGetorder::updateAll $status,$shopee_uid .affect rows:" . $queueAffectRows, "file");
            }
        } catch (\Exception $ex) {
            \Yii::info("SwitchShopeeCronjob Exception:" . print_r($ex, true), "file");
            return self::getResult(false, $ex->getMessage());
        }

        return self::getResult(true);
	}
	
	/**
	 * +---------------------------------------------------------------------------------------------
	 * 解绑 shopee 账号
	 * +---------------------------------------------------------------------------------------------
	 * @param $data
	 * +---------------------------------------------------------------------------------------------
	 * log            name    date           note
	 * @author        lrq     2018/04/26                初始化
	 * +---------------------------------------------------------------------------------------------
	 **/
	public static function Unbind($data){
		if(empty($data['shopee_uid']) || trim($data['shopee_uid']) == ''){
			return self::getResult(false, '参数丢失！');
		}
		$shopee_uid = trim($data['shopee_uid']);
		
		$user = SaasShopeeUser::find()->where(['shopee_uid' => $shopee_uid])->andWhere("status<>3")->one();
		if(empty($user)){
			return self::getResult(false, '账号信息不存在！');
		}
		$user->status = 3; //解绑状态
		$user->update_time = time();
		if($user->save()){
			//关掉对应后台job
			ShopeeAccountsApiHelper::SwitchShopeeCronjob(0, $shopee_uid);
			
			//重置账号绑定情况到redis
			$puid = \Yii::$app->user->identity->getParentUid();
			PlatformAccountApi::callbackAfterDeleteAccount('shopee', $puid, ['selleruserid' => $user->shop_id, 'order_source_site_id' => strtoupper($user->site)]);
		}
		else{
			\Yii::info('shopee unbind, shopee_uid:'.$shopee_uid.', err:'.print_r($user->getErrors(), true), "file");
			return self::getResult(false, '账号解绑失败，请联系客服！'); 
		}
		
		// 记录到  app_user_action_log  表
		AppTrackerApiHelper::actionLog("Tracker","/platform/shopeeaccounts/unbind");
		
		return self::getResult(true);
	}
	
	/**
	 * +---------------------------------------------------------------------------------------------
	 * 获取用户绑定的账号的异常情况，如同步订单失败之类
	 * +---------------------------------------------------------------------------------------------
	 * log        name    date           note
	 * @author    lrq     2018/05/04                初始化
	 * +---------------------------------------------------------------------------------------------
	 **/
	public static function getUserAccountProblems($uid = ''){
		if(empty($uid)){
			$uid=\Yii::$app->user->id;
		}
		
		$accounts = SaasShopeeUser::find()->where(['puid'=>$uid])->andWhere("status<>3")->asArray()->all();
		if(empty($accounts))
			return [];
	
		$accountUnActive = [];//未开启同步的账号
		$order_retrieve_errors = [];//获取订单失败
		$initial_order_failed = [];//首次绑定时，获取订单失败
		foreach ($accounts as $account){
			if(empty($account['status'])){
				$accountUnActive[] = $account;
				continue;
			}
			$autoSyncList = SaasShopeeAutosync::find()->where(['puid'=>$uid, 'shopee_uid'=>$account['shopee_uid'], 'type'=>['time','unFinish', 'finish']])->asArray()->all();
			foreach($autoSyncList as $row ){
				if(empty($autoSync['last_time']) && $row['type']=='day120'){
					$initial_order_failed[] = $account;
					continue;
				}
				//同步状态  0-等待同步 1-同步中 2-商品同步成功 3-同步失败
				if(  $row['type']=='time' && $row['status'] == 3){
					$order_retrieve_errors[] = $account;
					continue;
				}
			}
				
				
		}
		return [
    		'unActive'=>$accountUnActive,
    		'initial_failed'=>$initial_order_failed,
    		'order_retrieve_failed'=>$order_retrieve_errors,
		];
	}
	
	
	
	
}


