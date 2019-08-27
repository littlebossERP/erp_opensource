<?php
/**
 * @link http://www.witsion.com/
 * @copyright Copyright (c) 2014 Yii Software LLC
 * @license http://www.witsion.com/
 */
namespace eagle\modules\order\helpers;

use \Yii;
use eagle\models\SaasAliexpressAutosync;
use eagle\modules\platform\apihelpers\AliexpressAccountsApiHelper;
use eagle\models\QueueAliexpressGetorder;
use eagle\modules\order\models\OdOrder;
use eagle\modules\util\helpers\TranslateHelper;
use common\api\aliexpressinterface\AliexpressInterface_Api;
use eagle\modules\manual_sync\models\Queue;
use common\api\aliexpressinterface\AliexpressInterface_Auth;
use eagle\models\QueueAliexpressGetorder4;
use common\api\aliexpressinterface\AliexpressInterface_Helper;
use eagle\models\SaasAliexpressUser;
use eagle\modules\tracking\helpers\TrackingHelper;
use yii\helpers\Url;
use eagle\modules\delivery\helpers\DeliveryHelper;
use eagle\modules\util\helpers\OperationLogHelper;
use eagle\modules\util\helpers\RedisHelper;
use eagle\modules\order\models\OdOrderItem;
use common\api\aliexpressinterfacev2\AliexpressInterface_Api_Qimen;
use common\api\aliexpressinterfacev2\AliexpressInterface_Helper_Qimen;

class AliexpressOrderHelper{
	
	//aliexpress 不可发货的item状态，lrq20171019
	public static $CANNOT_SHIP_ORDERITEM_STATUS = array('WAREHOUSE_SEND_GOODS');
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * aliexpress 订单同步情况 数据
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param $status					指定状态筛选 （可选）
	 * @param $lasttime 				指定时间筛选 （可选）
	 +---------------------------------------------------------------------------------------------
	 * @return array ('message'=>执行详细结果
	 * 				  'success'=> true 成功 false 失败	)
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2015/11/26				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function getOrderSyncInfoDataList($status = '' , $lasttime =''){
		$AccountList = AliexpressAccountsApiHelper::listActiveAccounts();
		$syncList = [];
		$model = new SaasAliexpressAutosync();
		foreach($AccountList as $account){
			if (!empty($account['sellerloginid'])){
				$detail = AliexpressAccountsApiHelper::getLastOrderSyncDetail($account['sellerloginid']);
				if (!empty($detail['success']) && !empty($detail['result'])){
					//状态过滤
					if ($status !=''){
						//如果status 是有效的值， 则表示用户使用了过滤
						if ( $detail['result']['status'] != $status) {
							continue;
						}
					}//状态过滤
					
					//时间 过滤
					if  ($lasttime != ""){
						
						if ( $detail['result']['last_time'] > $lasttime) {
							continue;
						}
					}//时间 过滤
					
					$syncList[$account['sellerloginid']] = $detail['result'];
				}else{
					if ($status == '' )
						$syncList[$account['sellerloginid']] = $model->attributes;
				}
			}
				
		}
		return $syncList;
	}//end of getOrderSyncInfoDataList
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * aliexpress 商品同步情况 数据
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param $account_key 				各个平台账号的关键值 （必需） 就是用获取对应账号信息的
	 * @param $uid			 			uid use_base 的id
	 +---------------------------------------------------------------------------------------------
	 * @return array (  'result'=>同步表的最新数据
	 * 					'message'=>执行详细结果
	 * 				    'success'=> true 成功 false 失败	)
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2015/11/25				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function getLastProductSyncDetail($account_key , $uid=0){
		//get active uid
		if (empty($uid)){
			$userInfo = \Yii::$app->user->identity;
			if ($userInfo['puid']==0){
				$uid = $userInfo['uid'];
			}else {
				$uid = $userInfo['puid'];
			}
		}
		
		//where  type 为onSelling 同步商品
		$result = SaasAliexpressAutosync::find()->where(['uid'=>$uid , 'sellerloginid'=>$account_key , 'type'=>'onSelling'])->orderBy(' last_time desc ')->asArray()->one();
		/*
		$tmpCommand = SaasAliexpressAutosync::find()->where(['uid'=>$uid , 'sellerloginid'=>$account_key , 'type'=>'time'])->orderBy(' last_time desc ')->createCommand();
		echo "<br>".$tmpCommand->getRawSql();
		*/
		if (!empty($result)){
			return  ['success'=>true , 'message'=>'' , 'result'=>$result];
		}else{
			return  ['success'=>false , 'message'=>'没有同步信息' , 'result'=>[]];
		}
		
	}//end of getLasProductSyncDetail
	
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * aliexpress 根据 order id 获取 同步 情况 
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param $account_key 				各个平台账号的关键值 （必需） 就是用获取对应账号信息的
	 * @param $uid			 			uid use_base 的id
	 * @param $order_id					订单id
	 +---------------------------------------------------------------------------------------------
	 * @return array (  'result'=>同步表的最新数据
	 * 					'message'=>执行详细结果
	 * 				    'success'=> true 成功 false 失败	)
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2015/11/25				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function getOneOrderSyncByOrderId($order_id , $account_key , $uid = 0 ){
		//get active uid
		if (empty($uid)){
			$userInfo = \Yii::$app->user->identity;
			if ($userInfo['puid']==0){
				$uid = $userInfo['uid'];
			}else {
				$uid = $userInfo['puid'];
			}
		}
		$sql = "select * from queue_aliexpress_getorder where orderid= :orderid and uid = :uid ". (empty($account_key)?"":"and sellerloginid = :sellerloginid"); 
		
		$command = \Yii::$app->db_queue->createCommand($sql);
		$command->bindValue(":orderid",$order_id,\PDO::PARAM_STR);
		$command->bindValue(":uid",$uid,\PDO::PARAM_STR);
		if (! empty( $account_key))
			$command->bindValue(":sellerloginid",$account_key,\PDO::PARAM_STR);
		
		$reuslt = $command->queryall();
		if (!empty($reuslt)){
			return ['success'=>true,'message'=>'','result'=>self::_formatterSyncOrderInfo($reuslt)];
		}
		
		$command = \Yii::$app->db_queue->createCommand("select * from queue_aliexpress_getorder2 where orderid= :orderid and uid = :uid and sellerloginid = :sellerloginid" );
		$command->bindValue(":orderid",$order_id,\PDO::PARAM_STR);
		$command->bindValue(":uid",$uid,\PDO::PARAM_STR);
		$command->bindValue(":sellerloginid",$account_key,\PDO::PARAM_STR);
		
		$reuslt = $command->queryall();
		if (!empty($reuslt)){
			return ['success'=>true,'message'=>'','result'=>self::_formatterSyncOrderInfo($reuslt)];
		}
		
		$command = \Yii::$app->db_queue->createCommand("select * from queue_aliexpress_getfinishorder where orderid= :orderid and uid = :uid and sellerloginid = :sellerloginid" );
		$command->bindValue(":orderid",$order_id,\PDO::PARAM_STR);
		$command->bindValue(":uid",$uid,\PDO::PARAM_STR);
		$command->bindValue(":sellerloginid",$account_key,\PDO::PARAM_STR);
		
		$reuslt = $command->queryall();
		if (!empty($reuslt)){
			return ['success'=>true,'message'=>'','result'=>self::_formatterSyncOrderInfo($reuslt)];
		}
		
		$model = new QueueAliexpressGetorder();
		return ['success'=>true,'message'=>'','result'=>$model->attributes];
		
	}//end of getOneOrderSyncByOrderId
	
	
	
	
	
	static private function _formatterSyncOrderInfo(&$reuslt){
		$statusMapping = [
		''=>TranslateHelper::t('全部'),
		'0'=>TranslateHelper::t('等待同步'),
		'1'=>TranslateHelper::t('同步中'),
		'2'=>TranslateHelper::t('同步成功'),
		'3'=>TranslateHelper::t('同步失败'),
		'4'=>TranslateHelper::t('同步完成'),
		];
		foreach($reuslt as &$row){
			foreach($row as $field_name=>&$value){
				if (in_array($field_name, ['last_time' , 'create_time' , 'update_time' , 'next_time', 'gmtcreate'])){
					$value = date("Y-m-d H:i:s",$value);
				}elseif(in_array($field_name, ['order_status'])){
					/**/
					if (!empty(OdOrder::$aliexpressStatus[$value])){
						if (!empty(OdOrder::$aliexpressStatus[$value]))
							$value = OdOrder::$aliexpressStatus[$value];
					}
					
				}elseif(in_array($field_name, ['status'])){
					if (!empty($statusMapping[$value]))
						$value = $statusMapping[$value];
				}
			}
		}
		return $reuslt;
	}//end of _formatterSyncOrderInfo
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 根据订单的流程生成 操作列表数组
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param
	 * 			$code					当前操作的订单流程关键值
	 * 			$type					s = single 单独操作 ， b = batch 批量操作
	 +---------------------------------------------------------------------------------------------
	 * @return array  	 各栏目的 订单 数
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2015/12/14				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function getAliexpressCurrentOperationList($code , $type="s"){
		$OpList = OrderHelper::getCurrentOperationList($code,$type);
		
		switch ($code){
			case OdOrder::STATUS_PAY:
				$OpList += [ 'signshipped'=>'虚拟发货(标记发货)'];
				break;
			case OdOrder::STATUS_WAITSEND:
				$OpList += ['extendsBuyerAcceptGoodsTime'=>'延长买家收货时间', 'signshipped'=>'虚拟发货(标记发货)'];
				break;
			case OdOrder::STATUS_SHIPPED:
				$OpList += ['extendsBuyerAcceptGoodsTime'=>'延长买家收货时间' , 'signshipped'=>'虚拟发货(标记发货)'];
				break;
		}
		if ($type =='s')
			$OpList += ['invoiced' => '发票'];
		return $OpList;
	}//end of getAliexpressCurrentOperationList
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 速卖通 延长收货时间 接口
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param
	 * 			$order_id					小老板订单号
	 * 			$extenddays					请求延长的具体天数
	 * 			$module						模块名
	 * 			$action						执行操作
	 +---------------------------------------------------------------------------------------------
	 * @return array  	 各栏目的 订单 数
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2015/12/14				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function ExtendsBuyerAcceptGoodsTime($order_id , $extenddays , $module="order" , $action="oms延长收货时间"){
		$odorder = OdOrder::findOne($order_id);
		if (empty($odorder)){
			return ['success'=>false ,'memo'=>'找不到相应的订单' , 'errorCode'=>'','message'=>'找不到相应的订单'];
		}
		if ($odorder->order_capture =='Y'){
			return ['success'=>false ,'memo'=>'手工订单，不能延长发货' , 'errorCode'=>'','message'=>'手工订单，不能延长发货'];
			//return $order_id.'为手工订单，不能延长发货';
		}
		$aliexpressuser = SaasAliexpressUser::find()->where('sellerloginid = :s',[':s'=>$odorder->selleruserid])->one();
		if (empty($aliexpressuser)){
			return ['success'=>false ,'memo'=>'找不到订单所对应的Aliexpress账号' , 'errorCode'=>'','message'=>'找不到订单所对应的Aliexpress账号'];
		}
		//判断此速卖通账号信息是否v2版
		if($aliexpressuser->version == 'v2'){
			//调用API
			$api = new AliexpressInterface_Api_Qimen();
			$extendday = $extenddays;
			$param = [
				'id' => $odorder->selleruserid,
				'param0' => $odorder -> order_source_order_id,
				'param1' => $extendday,
			];
			$ret = $api->extendsBuyerAcceptGoodsTime($param);
			
			\Yii::info("ExtendsBuyerAcceptGoodsTime, order_id: ".$odorder->order_source_order_id.", param: ".json_encode($param).", result: ".json_encode($ret), "file");
			
			$result = [
				'success' => $ret['result_success'],
				'errorCode' => $ret['error_code'],
				'memo' => $ret['error_message'],
			];
		}
		else{
			//调用API
			$api = new AliexpressInterface_Api();
			$access_token = $api->getAccessToken ($aliexpressuser -> sellerloginid);
			//获取访问token失败
			if ($access_token === false){
				//echo $selleruserid . 'not getting access token!' . "\n";
				//die;
				return ['success'=>false ,'memo'=>'token获取失败！', 'errorCode'=>'','message'=>'token获取失败！'];
			}
			$api->access_token = $access_token;
			$extendday = $extenddays;
			$param = array(
					'orderId' => $odorder -> order_source_order_id,
					'day' => $extendday,
			);
			
			$result = $api->extendsBuyerAcceptGoodsTime($param);
		}
		
		if (@$result['success'] == '1' || @$result['success'] == true){
			//如果标记成功，写行为日志
			OperationLogHelper::log($module,$order_id,$action,'延长收货'.$extendday.'天', \Yii::$app->user->identity->getFullName());
			return ['success'=>true ,'message'=>'操作成功！'];
		}else{
			$errorCodeList = ['100 '=>'无此订单' , '601'=>'帐号无权限', '200'=>'业务数据错误，无对应的业务数据','201'=>'延长时间超过了系统允许时间。' , '210'=>'业务数据错误无法执行此操作'];
			
			if (!empty($errorCodeList[$result['errorCode']])){
				$errorMsg = $errorCodeList[$result['errorCode']];
			}else{
				$errorMsg = "";
			}
			return ['success'=>false ,'memo'=>@$result['memo'] , 'errorCode'=>@$result['errorCode'],'message'=>$errorMsg];
		}
	}//end of ExtendsBuyerAcceptGoodsTime
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 统计Left menu 上的order 数据
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param $platform					平台
	 +---------------------------------------------------------------------------------------------
	 * @return array  	 各栏目的 订单 数
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2015/12/14				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function getMenuStatisticData($params=[],$platform='aliexpress'){
		/*
		if (!empty($_SESSION['ali_oms_left_menu']) ){
			return $_SESSION['ali_oms_left_menu'];
		} 
		*/
		$counter = OrderHelper::getMenuStatisticData($platform,$params);
		
		$OrderQuery = OdOrder::find()->where('order_source = "'.$platform.'"');
		
		$OrderQuery->andWhere(['isshow'=>'Y']); 
		if (!empty($params)){
			$OrderQuery->andWhere($params);
		}
		
		$QueryConditionList = [
		OdOrder::STATUS_PAY=>[['order_status'=>OdOrder::STATUS_PAY] , ['not in' , "ifnull(order_source_status,'')", 'RISK_CONTROL'] ,['order_relation'=>['normal','sm','fs','ss']]],
		/*
		//今日新订单
		'todayorder'=>[['>=','order_source_create_time',strtotime(date('Y-m-d'))],['<','order_source_create_time',strtotime('+1 day')] ], 
		//等待您发货
		'sendgood'=>['order_source_status'=>'WAIT_SELLER_SEND_GOODS'],
		//买家申请取消订单
		'buyercancel'=>['order_source_status'=>'IN_CANCEL'] , 
		//有纠纷的订单
		'issueorder'=>['order_source_status'=>'IN_ISSUE'],
		//未读留言
		'newmessage'=>['in', 'order_id', (new \yii\db\Query())->select('order_id')->from('od_order_systags_mapping')->where(['tag_code' => 'new_msg_tag'])],
		//等待卖家验款
		'waitsellerexamine' =>['order_source_status'=>'WAIT_SELLER_EXAMINE_MONEY'],
		//等待您留评
		'waitcomment'=>['in', 'order_id', (new \yii\db\Query())->select('order_id')->from('od_order_systags_mapping')->where(['tag_code' => 'favourable_tag'])],
		//等待放款的订单
		'waitsendmoney'=>['order_source_status'=>'CUSTOM_WAIT_SEND_MOENY'],
		//等待买家付款
		'waitpay'=>['order_source_status'=>'PLACE_ORDER_SUCCESS'],
		//等待确认收货订单
		'waitaccept'=>['order_source_status'=>['SELLER_PART_SEND_GOODS' , 'WAIT_BUYER_ACCEPT_GOODS','WAIT_SELLER_EXAMINE_MONEY']],
		*/
		###############################通知平台发货统计，暂时################################################################################################		
		//等待通知平台发货
		'shipping_status_0'=>[['order_source_status'=>'WAIT_SELLER_SEND_GOODS'],['shipping_status'=>OdOrder::NO_INFORM_DELIVERY ]],
		//通知平台发货中
		'shipping_status_2'=>['shipping_status'=>OdOrder::PROCESS_INFORM_DELIVERY],
		//已通知平台发货
		'shipping_status_1'=>['order_source_status'=>['SELLER_PART_SEND_GOODS' , 'WAIT_BUYER_ACCEPT_GOODS']],
		###############################通知平台发货统计，暂时################################################################################################
		
		];
		
		foreach($QueryConditionList as $key =>$QueryCondition){
			$cloneQuery = clone $OrderQuery;
			
			if (isset($params)){
				$cloneQuery->andWhere($params);
			}
			
			// 查询条件只有一个 ， 或者 查询条件的第一个键值是字符串 ‘in’
			if(count($QueryCondition) == 1 ||  in_array($QueryCondition[0], ['in'])){
				$counter[$key] = $cloneQuery->andWhere($QueryCondition)->count();
			}else{
				foreach($QueryCondition as $tmpCondition){
					$cloneQuery->andWhere($tmpCondition);
				}
				//echo $cloneQuery->createCommand()->getRawSql();
				$counter[$key] =$cloneQuery->count();
			}
			
		}
		
		/*
		//今日新订单
		$count['todayorder'] = OdOrder::find()->where(['>=','order_source_create_time',strtotime(date('Y-m-d'))])->andwhere(['<','order_source_create_time',strtotime('+1 day')])->count();
		//等待您发货
		$count['sendgood'] = OdOrder::find()->where(['order_source_status'=>'WAIT_SELLER_SEND_GOODS'])->count();
		//买家申请取消订单
		$count['buyercancel'] = OdOrder::find()->where(['order_source_status'=>'IN_CANCEL'])->count();
		//有纠纷的订单
		$count['issueorder'] = OdOrder::find()->where(['order_source_status'=>'IN_ISSUE'])->count();
		//未读留言
		$count['newmessage'] = OdOrder::find()->where(['in', 'order_id', (new \yii\db\Query())->select('order_id')->from('od_order_systags_mapping')->where(['tag_code' => 'new_msg_tag'])])->count();
		//等待卖家验款
		$count['waitsellerexamine'] = OdOrder::find()->where(['order_source_status'=>'WAIT_SELLER_EXAMINE_MONEY'])->count();
		//等待您留评
		$count['waitcomment'] = OdOrder::find()->where(['in', 'order_id', (new \yii\db\Query())->select('order_id')->from('od_order_systags_mapping')->where(['tag_code' => 'favourable_tag'])])->count();
		//等待放款的订单
		$count['waitsendmoney'] = OdOrder::find()->where(['order_source_status'=>'CUSTOM_WAIT_SEND_MOENY'])->count();
		//等待买家付款
		$count['waitpay'] = OdOrder::find()->where(['order_source_status'=>'PLACE_ORDER_SUCCESS'])->count();
		//等待确认收货订单
		$count['waitaccept'] = OdOrder::find()->where(['order_source_status'=>['SELLER_PART_SEND_GOODS' , 'WAIT_BUYER_ACCEPT_GOODS','WAIT_SELLER_EXAMINE_MONEY']])->count();
		*/
		
		return $counter;
	}
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 显示 aliexpress 解绑 账号
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param     na
	 +---------------------------------------------------------------------------------------------
	 * @return						array	['account1' , 'account2']
	 *
	 * @invoking					AliexpressOrderHelper::listUnbindingAcount();
	 *
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2016/3/14				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function listUnbindingAcount(){
		$unbingdingAccount = [];
		$platform = 'aliexpress';
		//获取当前 绑定 的速卖通账号
		$tmpSellerIDList =  AliexpressAccountsApiHelper::getAllAccounts(\Yii::$app->user->identity->getParentUid());
		
		$aliAccountList = [];
		foreach($tmpSellerIDList as $tmpSellerRow){
			$aliAccountList[] = $tmpSellerRow['sellerloginid'];
		}
		//获取订单上出现 过的账号
		$rt = OdOrder::find()->select(['selleruserid'])->where(['order_source'=>$platform])->groupBy('selleruserid')->asArray()->all();
		//比较出哪些是解绑账号
		foreach ($rt as $row){
			if (in_array($row['selleruserid'], $aliAccountList)){
				continue;
			}
			$unbingdingAccount[] = $row['selleruserid'];
		}
		return $unbingdingAccount;
	}//end of listUnbindingAcount
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 统计 aliexpress dash board 的缓存数据
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param     na
	 +---------------------------------------------------------------------------------------------
	 * @return						na
	 *
	 * @invoking					AliexpressOrderHelper::UserAliexpressOrderDailySummary($start_time);
	 *
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2016/3/14				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function UserAliexpressOrderDailySummary($start_time){
		//获取所有aliexpress 绑定有效的账号
		//echo SaasAliexpressUser::find()->select(['uid', 'username'=>'sellerloginid'])->where(['is_active'=>1 ])->orderBy('uid')->createcommand()->getRawSql();
		//exit();
		$accounts = SaasAliexpressUser::find()->select(['uid', 'username'=>'sellerloginid'])->where(['is_active'=>1 ])->orderBy('uid')->asArray()->all();
		$platform = 'aliexpress';
		$isShowLog = false;
		$interval = 7;
		return OrderBackgroundHelper::OrderDailySummary($start_time, $platform, $accounts,$isShowLog ,$interval);
	}//end of UserAliexpressOrderDailySummary
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 统计 aliexpress dash board 的缓存数据
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param     na
	 +---------------------------------------------------------------------------------------------
	 * @return						na
	 *
	 * @invoking					AliexpressOrderHelper::UserAliexpressOrderDailySummary($start_time);
	 *
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2016/3/14				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function getChartDataByUid_Order_Aliexpress($uid ,$days ){
		//获取所有aliexpress 绑定有效的账号
		$accounts = SaasAliexpressUser::find()->select(['uid', 'username'=>'sellerloginid'])->where(['is_active'=>1 ,'uid'=>$uid ])->asArray()->all();
		$platform = 'aliexpress';
		return OrderBackgroundHelper::getChartDataByUid_Order($uid ,$days , $platform, $accounts);
	}//end of UserAliexpressOrderDailySummary
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 获取 aliexpress dash board 的缓存数据
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param     na
	 +---------------------------------------------------------------------------------------------
	 * @return						na
	 *
	 * @invoking					AliexpressOrderHelper::UserAliexpressOrderDailySummary($start_time);
	 *
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2016/3/14				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function getOmsDashBoardData($uid,$isRefresh =false){
		$platform = 'aliexpress';
		
		//检查是否有缓存 数据， 有则直接读取缓存数据， 没有则重新生成 oms dash board 数据并保存到缓存中
		//$cacheData = \Yii::$app->redis->hget(ucfirst($platform).'Oms_DashBoard',"user_$uid"."_cacheData");
		$cacheData = RedisHelper::RedisGet(ucfirst($platform).'Oms_DashBoard',"user_$uid"."_cacheData" );
		$createTime = time();
		
		if (!empty($cacheData)) $cacheData = json_decode($cacheData,true);
		
		if (!empty($cacheData['createTime'])){
			//若cache 数据超过4小时，则清除cache
			if (($cacheData['createTime']+60*60*4 )<$createTime){
				$cacheData = [];
			}
		}
		
		if (empty($cacheData) || $isRefresh){
			$cacheData =[];
			$cacheData['order_count'] = AliexpressOrderHelper::getChartDataByUid_Order_Aliexpress($uid, 10);//订单数量统计
			//$chartData['profit_count'] = CdiscountOrderInterface::getChartDataByUid_Profit($uid,10);//oms 利润统计 aliexpress 没有先屏蔽
			$cacheData['advertData'] = OrderBackgroundHelper::getAdvertDataByUid($uid,2,$platform); // 获取OMS dashboard广告
			$cacheData['reminderData'] = OrderBackgroundHelper::getOMSReminder($platform,$uid);
			$cacheData['createTime'] = $createTime;
			
			$cacheData_json = json_encode($cacheData);
			
			//\Yii::$app->redis->hset(ucfirst($platform).'Oms_DashBoard',"user_$uid"."_cacheData",$cacheData_json);
			RedisHelper::RedisSet(ucfirst($platform).'Oms_DashBoard',"user_$uid"."_cacheData",$cacheData_json);
			
		}
		return $cacheData;
	}//end of getOmsDashBoardData
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 获取 aliexpress dash board 的缓存数据
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param     $uid		uid
	 +---------------------------------------------------------------------------------------------
	 * @return						na
	 *
	 * @invoking					AliexpressOrderHelper::getOmsDashBoardCache($uid);
	 *
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2016/5/20				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function getOmsDashBoardCache($uid){
		$platform = 'aliexpress';
		
		//$cacheData = \Yii::$app->redis->hget(ucfirst($platform).'Oms_DashBoard',"user_$uid"."_cacheData");
		$cacheData = RedisHelper::RedisGet(ucfirst($platform).'Oms_DashBoard',"user_$uid"."_cacheData");
		if (!empty($cacheData)) 
			return json_decode($cacheData,true);
		else 
			return[];
		
	}//end of getOmsDashBoardCache
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 更改 aliexpress dash board 的缓存数据
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param     $uid		uid
	 +---------------------------------------------------------------------------------------------
	 * @return						na
	 *
	 * @invoking					AliexpressOrderHelper::getOmsDashBoardCache($uid);
	 *
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2016/5/20				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function changeOmsDashBoardCache($uid , $data){
		$platform = 'aliexpress';
		
		//$cacheData = \Yii::$app->redis->hget(ucfirst($platform).'Oms_DashBoard',"user_$uid"."_cacheData");
		$cacheData = RedisHelper::RedisGet(ucfirst($platform).'Oms_DashBoard',"user_$uid"."_cacheData" );
		
		if (!empty($data['delete'])) {
			//\Yii::$app->redis->hdel(ucfirst($platform).'Oms_DashBoard',"user_$uid"."_cacheData");
			RedisHelper::RedisDel(ucfirst($platform).'Oms_DashBoard',"user_$uid"."_cacheData" );
			return ;
		}
		$cacheData = json_decode($cacheData,true);
		
		foreach($data as $key=>$value){
			$cacheData[$key] = $value;
		}
		
		//$cacheData = \Yii::$app->redis->hset(ucfirst($platform).'Oms_DashBoard',"user_$uid"."_cacheData");
		$cacheData = RedisHelper::RedisSet(ucfirst($platform).'Oms_DashBoard',"user_$uid"."_cacheData");
	}
		

	/**
	 *手动同步smt的订单
	 * @date 2016.05.04
	 *
	 * @author akirametero
	 */
    public static function getOrderListManual( $queue ){

        $sellerloginid= $queue->site_id;
		//$sellerloginid= 'cn1001790257';
        $connection=Yii::$app->db_queue;
        $return= array();
        // 检查授权是否过期或者是否授权,返回true，false
        
        //****************判断此速卖通账号信息是否v2版    是则跳转     start*************
        $is_aliexpress_v2 = AliexpressInterface_Helper_Qimen::CheckAliexpressV2($sellerloginid);
        if($is_aliexpress_v2){
        	$ret = AliexpressOrderV2Helper::getOrderListManual($queue);
        	return $ret;
        }
        //****************判断此账号信息是否v2版    end*************

        if (!AliexpressInterface_Auth::checkToken ( $sellerloginid )) {
			//error_log("未授权".date("Y-m-d H:i:s"),3,'/tmp/smt_push.log');
            $queue->data(['error'=>'未授权']);
            return false;
        }
        //获取
		echo date("Y-m-d");
        //同步状态值更改
        $update_arr = array();
        $update_arr['status'] = 1;
        $update_arr['last_time'] = time();

        $where_arr= array();
        $where_arr['sellerloginid']= $sellerloginid;
        $where_arr['type']= 'time';

        $res= SaasAliexpressAutosync::updateAll( $update_arr,$where_arr );
		//error_log("锁定店铺同步状态".date("Y-m-d H:i:s"),3,'/tmp/smt_push.log');
		//error_log("锁定店铺同步状态".date("Y-m-d H:i:s"),3,'/tmp/smt_push.log');

        $api = new AliexpressInterface_Api ();
        $access_token = $api->getAccessToken ( $sellerloginid );
        //获取访问token失败
        if ($access_token === false){
			//error_log("访问token失败".date("Y-m-d H:i:s"),3,'/tmp/smt_push.log');
            $queue->data(['error'=>'访问token失败']);
            return false;
        }
        $api->access_token = $access_token;

        //获取最新的同步信息
        $obj= SaasAliexpressAutosync::findOne( $where_arr );
        $time = time();
        if($obj->end_time == 0) {
            //初始同步
            $start_time = $obj->binding_time;
            $end_time = $time;
        }else {
            //增量同步
            $start_time = $obj->end_time-86400*8;//双11，先改为同步3天内，之前是10天，lrq20171111
            $end_time = $time;
        }
        $format_start_time= self::getLaFormatTime ("m/d/Y H:i:s", $start_time );
        $format_end_time= self::getLaFormatTime ("m/d/Y H:i:s", $end_time );
		echo $format_start_time.'--'.$format_end_time;


        // 是否全部同步完成
        $success = true;

        
        $uid= $obj->uid;
 

        //////////////////////////////////////////////////////////////////////////////////////////////////////////
        //非FINISH状态的订单同步/////
        //获取最近读不到图片的item
        $no_photo_item = self::GetNoPhotoItem($start_time);
        //分页设置
        $page = 1;
        $pageSize = 50;
        $orders = [];
        $sendGoodsOperator_arr= array();
        do {
			$api_time= time();//接口调用时间
            // 接口传入参数
            $param = ['page' => $page, 'pageSize' => $pageSize, 'createDateStart' => $format_start_time, 'createDateEnd' => $format_end_time];
            //调用接口获取订单列表
            $result2 = $api->findOrderListQuery($param);
            echo "get-result2",PHP_EOL;
            // 判断是否有订单
            if (!isset ( $result2['totalItem'] ) || !isset( $result2['orderList'])) {
                $success = false;
                break;
            }
            foreach ( $result2 ['orderList'] as $order ) {
				$orderid= $order['orderId'];

                $orders[$order['orderId']]['day'] = isset($order['leftSendGoodDay'])?$order['leftSendGoodDay']:0;
                $orders[$order['orderId']]['hour'] = isset($order['leftSendGoodHour'])?$order['leftSendGoodHour']:0;
                $orders[$order['orderId']]['min'] = isset($order['leftSendGoodMin'])?$order['leftSendGoodMin']:0;


				$logisticsServiceName_arr= array();
				$memo_arr= array();
				//买家物流信息
				if (isset($order['productList'])) {
					foreach ($order['productList'] as $pl) {
						//客选物流
						if (isset($pl['logisticsServiceName'])) {
							$logisticsServiceName = $pl['logisticsServiceName'];
							$productid = $pl['productId'];
							$logisticsServiceName_arr["shipping_service"][$productid] = $logisticsServiceName;
						}
						//买家备注
						if( isset($pl['memo']) ){
							$pmemo= str_replace("'","",$pl['memo']);
							if( $pmemo=='' ){
								$pmemo= '无';
							}
							$memo_arr[]= $pmemo;
							//$logisticsServiceName_arr["user_message"][$productid] = $memo;
						}
						//发货类型
						if (isset($pl['sendGoodsOperator'])) {
							$sendGoodsOperator = $pl['sendGoodsOperator'];
							$productid = $pl['productId'];
							$sendGoodsOperator_arr[$order['orderId']][$productid] = $sendGoodsOperator;
							
							//设置发货类型
							list( $order_status,$msg )= OrderHelper::updateOrderItemAddiInfoByOrderID( $orderid, $sellerloginid, $productid, 'aliexpress' , ['sendGoodsOperator' => $sendGoodsOperator] );
							if( $order_status===false ){
								echo $orderid.'可选物流更新失败--'.$msg.PHP_EOL;
							}
						}
						
						//判断是否属于无图片的item，是则更新
						if(array_key_exists($sellerloginid.'_'.$pl['productId'].'_'.$pl['childId'], $no_photo_item)){
							$item = OdOrderItem::findOne(['order_item_id' => $no_photo_item[$sellerloginid.'_'.$pl['productId'].'_'.$pl['childId']]['order_item_id'], 'order_source_itemid' => $pl['productId'], 'order_source_transactionid' => $pl['childId'], 'order_source_order_id' => $orderid]);
							if(!empty($pl['productImgUrl']) && !empty($item)){
								$item->photo_primary = $pl['productImgUrl'];
								$item->save(false);
							}
						}
					}
				}

				$memo= '';
				if( !empty( $memo_arr ) ){
					$memo_eof= false;
					foreach( $memo_arr as $memo_vss ){
						if( $memo_vss!='无' ){
							$memo_eof= true;
							break;
						}
					}
					if( $memo_eof===true ){
						foreach( $memo_arr as $key=>$memo_vss ){
							$count= $key+1;
							$memo.= "商品{$count}:{$memo_vss};";
						}
					}

				}

				//设置客选物流
				if (!empty($logisticsServiceName_arr)) {
					list( $order_status,$msg )= OrderHelper::updateOrderAddiInfoByOrderID( $orderid, $sellerloginid , 'aliexpress' , $logisticsServiceName_arr );
					if( $order_status===false ){
						echo $orderid.'可选物流更新失败--'.$msg.PHP_EOL;
					}
				}

				//判断是否存在剩余发货时间的3个属性
				if( isset( $order['leftSendGoodDay'] ) ){
					//这个是天,换算成秒数
					$leftSendGoodDay= $order['leftSendGoodDay']*86400;
				}else{
					$leftSendGoodDay= 0;
				}
				if( isset( $order['leftSendGoodHour'] ) ){
					//这个是小时,换算成秒数
					$leftSendGoodHour= $order['leftSendGoodHour']*3600;
				}else{
					$leftSendGoodHour= 0;
				}
				if( isset( $order['leftSendGoodMin'] ) ){
					//这个是分组,换算成秒数
					$leftSendGoodMin= $order['leftSendGoodMin']*60;
				}else{
					$leftSendGoodMin= 0;
				}

				//如果都是0的话,不处理最后发货时间,有一个不是0,才去处理
				if( $leftSendGoodDay>0 || $leftSendGoodHour>0 || $leftSendGoodMin>0 ){
					//在接口调用时间上,加上秒数就是最后发货时间啦
					$fulfill_deadline= ceil($leftSendGoodDay+$leftSendGoodHour+$leftSendGoodMin+$api_time);
					//更新掉字段
					Yii::$app->subdb->createCommand("UPDATE od_order_v2 SET fulfill_deadline='{$fulfill_deadline}' WHERE order_source_order_id='{$orderid}'")->execute();
					//echo "UPDATE od_order_v2 SET fulfill_deadline='{$fulfill_deadline}' WHERE order_source_order_id='{$orderid}'",PHP_EOL;
				}else{
				}

				//设置买家备注
				if( $memo!='' ) {
					//$rof= OdOrder::updateAll( ['user_message'=>$memo],"order_source_order_id='{$orderid}'" );
					//需要获取自增id
					$ro = OdOrder::findOne(['order_source_order_id' => $orderid]);
					if (!empty($ro)) {
						$sysTagRt = OrderTagHelper::setOrderSysTag($ro->order_id, 'pay_memo');
						if (isset($sysTagRt['code']) && $sysTagRt['code'] == '400') {
							echo '\n' . $ro->order_id . ' insert pay_memo failure :' . $sysTagRt['message'];
						}
					}
					$rof= Yii::$app->subdb->createCommand("UPDATE od_order_v2 SET user_message='{$memo}' WHERE order_source_order_id='{$orderid}'")->execute();
				}

			}
            $page ++;
            $p = ceil($result2['totalItem']/50);
        } while ( $page <= $p );

        //重置
        $page = 1;
        do {
            // 接口传入参数
            $param = ['page' => $page, 'pageSize' => $pageSize, 'createDateStart' => $format_start_time, 'createDateEnd' => $format_end_time];
            // 调用接口获取订单列表
            $result = $api->findOrderListSimpleQuery($param);
            echo "get-result",PHP_EOL;
            //判断是否有订单
            if (!isset ( $result['totalItem'] )) {
                $success = false;
                break;
            }

            //保存本次同步订单数
            SaasAliexpressAutosync::updateAll( ['order_item'=>$result ['totalItem']],['id'=>$obj->id] );

            if($result ['totalItem'] > 0) {
                // 保存数据到同步订单详情队列
                //foreach begain
                foreach ( $result ['orderList'] as $one ) {
                    $orderid = $one['orderId'];
					echo PHP_EOL,$orderid,PHP_EOL;
					//if( $orderid!='78668784181321' ){
						//continue;
					//}
                    //订单产生时间
                    $gmtCreate = AliexpressInterface_Helper::transLaStrTimetoTimestamp($one['gmtCreate']);
                    $order_info = self::_setOrderDay($orders, $one);
                    
                    //发货类型
                    if(!empty($sendGoodsOperator_arr[$orderid])){
                    	$order_info['sendGoodsOperator'] = $sendGoodsOperator_arr[$orderid];
                    }

                    //队列4中是否存在这个订单
                    $rs_order4 = $connection->createCommand("SELECT id FROM queue_aliexpress_getorder4 WHERE orderid='{$orderid}' ORDER BY id DESC  ")->query()->read();
                    if ( $rs_order4===false ) {
                        //没有数据,写入队列4
                        $QAG_four = new QueueAliexpressGetorder4();
                        $QAG_four->uid = $uid;
                        $QAG_four->sellerloginid = $sellerloginid;
                        $QAG_four->aliexpress_uid = $obj->aliexpress_uid;
                        $QAG_four->order_status = $one['orderStatus'];
                        $QAG_four->orderid = $orderid;
                        $QAG_four->order_info = json_encode($order_info);
                        $QAG_four->gmtcreate = $gmtCreate;
                        $boolfour = $QAG_four->save();

                        $newid= $QAG_four->primaryKey;
                    } else {
                        //有数据,不处理
                        $boolfour= true;
                        $newid= $rs_order4['id'];
                    }




                    //推送表中是否存在未锁定,未处理的数据,存在就锁定了,省的后面重复处理
                    //管他有没有这个订单号的,统一update掉算了,反正没有的话,也就update false而已
                    $auto_order_eof = $connection->createCommand("UPDATE queue_aliexpress_auto_order SET is_lock=1  WHERE order_id='{$orderid}' ")->execute();

                    //同步数据到用户表的小老板订单中
                    if ($boolfour === true) {
                        $getorder4_obj= QueueAliexpressGetorder4::findOne( $newid );
                        $param = ['orderId' => $orderid];
                        $res= $api->findOrderById ( $param );
						//print_r ($res);exit;
						$res["sellerOperatorLoginId"]= $getorder4_obj->sellerloginid;
						echo '店铺ID---',$res["sellerOperatorLoginId"],PHP_EOL;
                        $r = AliexpressInterface_Helper::saveAliexpressOrder ( $getorder4_obj, $res );
                        if( $r['success']==0 ){
                            //同步成功
							$update_t= date("Y-m-d H:i:s");
                            $connection->createCommand("UPDATE queue_aliexpress_auto_order SET `status`=3,update_time='{$update_t}'  WHERE order_id='{$orderid}' ")->execute();
							//error_log("同步成功".date("Y-m-d H:i:s"),3,'/tmp/smt_push.log');
							if( $queue!='' ){
                            	$queue->addProgress();
							}
                            echo 1;
                        }else{
                            //同步失败
							if( isset($r['message']) && isset( $r['success'] ) ){
								$error= $r['success'].'--'.$r['message'];
							}else{
								$error= '订单更新失败';
							}
							$update_t= date("Y-m-d H:i:s");

                            $connection->createCommand("UPDATE queue_aliexpress_auto_order SET `status`=2,update_time='{$update_t}',error_message='{$error}'  WHERE order_id='{$orderid}' ")->execute();
							//error_log("同步失败".date("Y-m-d H:i:s"),3,'/tmp/smt_push.log');
                            $success = false;
							echo 2;
                        }
                    }
                }
                //end foreach
            }
            $page ++;
            $p = ceil($result['totalItem']/50);
        } while ( $page <= $p );




        //////////////////////////////////////////////////////////////////////////////////////////////////////////
        //获取订单状态为FINISH的订单//////
        //分页设置
        $page = 1;
        $pageSize = 50;
        $orders = [];
        $sendGoodsOperator_arr= array();
        do {
			$api_time= time();
            // 接口传入参数
            $param = ['page' => $page, 'pageSize' => $pageSize, 'createDateStart' => $format_start_time, 'createDateEnd' => $format_end_time,'orderStatus'=>'FINISH'];
            //调用接口获取订单列表
            $result2 = $api->findOrderListQuery($param);

            // 判断是否有订单
            if (!isset ( $result2['totalItem'] ) || !isset( $result2['orderList'])) {
                $success = false;
                break;
            }
            foreach ( $result2 ['orderList'] as $order ) {
				$orderid= $order['orderId'];
                $orders[$order['orderId']]['day'] = isset($order['leftSendGoodDay'])?$order['leftSendGoodDay']:0;
                $orders[$order['orderId']]['hour'] = isset($order['leftSendGoodHour'])?$order['leftSendGoodHour']:0;
                $orders[$order['orderId']]['min'] = isset($order['leftSendGoodMin'])?$order['leftSendGoodMin']:0;

				$logisticsServiceName_arr= array();

				//买家物流信息
				if (isset($order['productList'])) {
					foreach ($order['productList'] as $pl) {
						if (isset($pl['logisticsServiceName'])) {
							$logisticsServiceName = $pl['logisticsServiceName'];
							$productid = $pl['productId'];
							$logisticsServiceName_arr["shipping_service"][$productid] = $logisticsServiceName;
						}

						//发货类型
						if (isset($pl['sendGoodsOperator'])) {
							$sendGoodsOperator = $pl['sendGoodsOperator'];
							$productid = $pl['productId'];
							$sendGoodsOperator_arr[$order['orderId']][$productid] = $sendGoodsOperator;
							
							//设置发货类型
							list( $order_status,$msg )= OrderHelper::updateOrderItemAddiInfoByOrderID( $orderid, $sellerloginid, $productid, 'aliexpress' , ['sendGoodsOperator' => $sendGoodsOperator] );
							if( $order_status===false ){
								echo $orderid.'可选物流更新失败--'.$msg.PHP_EOL;
							}
						}
					}
				}
				//设置客选物流
				if (!empty($logisticsServiceName_arr)) {
					list( $order_status,$msg )= OrderHelper::updateOrderAddiInfoByOrderID( $orderid, $sellerloginid , 'aliexpress' , $logisticsServiceName_arr );
					if( $order_status===false ){
						echo $orderid.'可选物流更新失败--'.$msg.PHP_EOL;
					}
				}


            }
            $page ++;
            $p = ceil($result2['totalItem']/50);
        } while ( $page <= $p );

        //重置
        $page = 1;
        do {
            // 接口传入参数
            $param = ['page' => $page, 'pageSize' => $pageSize, 'createDateStart' => $format_start_time, 'createDateEnd' => $format_end_time,'orderStatus'=>'FINISH'];
            // 调用接口获取订单列表
            $result = $api->findOrderListSimpleQuery($param);
            //判断是否有订单
            if (!isset ( $result['totalItem'] )) {
                $success = false;
                break;
            }

            //保存本次同步订单数
            SaasAliexpressAutosync::updateAll( ['order_item'=>$result ['totalItem']],['id'=>$obj->id] );

            if($result ['totalItem'] > 0) {
                // 保存数据到同步订单详情队列
                //foreach begain
                foreach ( $result ['orderList'] as $one ) {
                    $orderid = $one['orderId'];
					echo 'FINISH--',$orderid,PHP_EOL;
                    //订单产生时间
                    $gmtCreate = AliexpressInterface_Helper::transLaStrTimetoTimestamp($one['gmtCreate']);
                    $order_info = self::_setOrderDay($orders, $one);
                    
                    //发货类型
                    if(!empty($sendGoodsOperator_arr[$orderid])){
                    	$order_info['sendGoodsOperator'] = $sendGoodsOperator_arr[$orderid];
                    }

                    //队列4中是否存在这个订单
                    $rs_order4 = $connection->createCommand("SELECT id FROM queue_aliexpress_getorder4 WHERE orderid='{$orderid}' ORDER BY id DESC  ")->query()->read();
                    if ( $rs_order4===false ) {
                        //没有数据,写入队列4
                        $QAG_four = new QueueAliexpressGetorder4();
                        $QAG_four->uid = $uid;
                        $QAG_four->sellerloginid = $sellerloginid;
                        $QAG_four->aliexpress_uid = $obj->aliexpress_uid;
                        $QAG_four->order_status = $one['orderStatus'];
                        $QAG_four->orderid = $orderid;
                        $QAG_four->order_info = json_encode($order_info);
                        $QAG_four->gmtcreate = $gmtCreate;
                        $boolfour = $QAG_four->save();

                        $newid= $QAG_four->primaryKey;
                    } else {
                        //有数据,不处理
                        $boolfour= true;
                        $newid= $rs_order4['id'];
                    }



                    //推送表中是否存在未锁定,未处理的数据,存在就锁定了,省的后面重复处理
                    //管他有没有这个订单号的,统一update掉算了,反正没有的话,也就update false而已
                    $auto_order_eof = $connection->createCommand("UPDATE queue_aliexpress_auto_order SET is_lock=1  WHERE order_id='{$orderid}' ")->execute();


                    //同步数据到用户表的小老板订单中
                    if ($boolfour === true) {
                        $getorder4_obj= QueueAliexpressGetorder4::findOne( $newid );
                        $param = ['orderId' => $orderid];
                        $res= $api->findOrderById ( $param );
						$res["sellerOperatorLoginId"]= $getorder4_obj->sellerloginid;
						echo '店铺ID---',$res["sellerOperatorLoginId"],PHP_EOL;
                        $r = AliexpressInterface_Helper::saveAliexpressOrder ( $getorder4_obj, $res );
                        if( $r['success']==0 ){
							$update_t= date("Y-m-d H:i:s");
                            //同步成功
                            $connection->createCommand("UPDATE queue_aliexpress_auto_order SET `status`=3,update_time='{$update_t}'  WHERE order_id='{$orderid}' ")->execute();

                            //error_log("同步成功".date("Y-m-d H:i:s"),3,'/tmp/smt_push.log');
							if( $queue!='' ){
                            	$queue->addProgress();
							}
							
							//设置物流状态
							try{
								if (!empty($res['logisticsStatus'])) {
									list( $order_status,$msg )= OrderHelper::updateOrderAddiInfoByOrderID( $orderid, $sellerloginid , 'aliexpress' , ['aliexpress_logisticsStatus' => $res['logisticsStatus']] );
									if( $order_status===false ){
										echo $orderid.'物流状态更新失败--'.$msg.PHP_EOL;
									}
								}
							}
							catch(\Exception $ex){}
							
                            echo 1;
                        }else{
                            //同步失败
							if( isset($r['message']) && isset( $r['success'] ) ){
								$error= $r['success'].'--'.$r['message'];
							}else{
								$error= '订单更新失败';
							}
							$update_t= date("Y-m-d H:i:s");
                            $connection->createCommand("UPDATE queue_aliexpress_auto_order SET `status`=2,error_message='{$error}',update_time='{$update_t}'  WHERE order_id='{$orderid}' ")->execute();
                            //error_log("同步失败".date("Y-m-d H:i:s"),3,'/tmp/smt_push.log');
                            $success = false;
							echo 2;
                        }
                    }
                }
                //end foreach
            }
            $page ++;
            $p = ceil($result['totalItem']/50);
        } while ( $page <= $p );

        //////////////////////////////////////////////////////////////////////////////////////////////////////////
        //获取用户v2订单表中,所有没有设定买家物流并且符合订单状态的数据
        //$rd= Yii::$app->subdb->createCommand("SELECT order_source_order_id,order_id,order_source_status,order_source_create_time FROM od_order_v2 where addi_info is not null and  order_source ='aliexpress' and selleruserid ='{$sellerloginid}' AND ( order_source_status='WAIT_BUYER_ACCEPT_GOODS' OR order_source_status='FINISH' ) ")->query()->readAll();




        //////////////////////////////////////////////////////////////////////////////////////////////////////////

        //完了,解锁
        $update_arr = array();
        $update_arr['end_time'] = $end_time;
        $update_arr['status'] = 0;
        $update_arr['last_time'] = time();
        $update_arr['next_time']= time()+3600;

        $where_arr= array();
        $where_arr['uid']= $uid;
        $where_arr['type']= 'time';

        SaasAliexpressAutosync::updateAll( $update_arr,$where_arr );
		//error_log("解锁成功".date("Y-m-d H:i:s"),3,'/tmp/smt_push.log');
        return true;

    }
    //end function

    /**
     * @param string $format. output time string format
     * @param timestamp $timestamp
     * @return America/Los_Angeles formatted time string
     */
    static function getLaFormatTime($format , $timestamp){
        $dt = new \DateTime();
        $dt->setTimestamp($timestamp);
        $dt->setTimezone(new \DateTimeZone('America/Los_Angeles'));
        return $dt->format($format);


    }
    //end function

    private static function _setOrderDay($orders,$one){
        if(isset($orders[$one['orderId']])){
            $one['day'] = $orders[$one['orderId']]['day'];
            $one['hour'] = $orders[$one['orderId']]['hour'];
            $one['min'] = $orders[$one['orderId']]['min'];
        } else {
            $one['day'] = 0;
            $one['hour'] = 0;
            $one['min'] = 0;
        }
        return $one;
    }
    /**
     * 获取用户绑定的速卖通账号的异常情况，如token过期之类
     * @param int $uid
     */
    public static function getUserAccountProblems($uid){
        if(empty($uid))
            return [];
    
        $AliexpressAccounts = SaasAliexpressUser::find()->where(['uid'=>$uid])->asArray()->all();
        if(empty($AliexpressAccounts))
            return [];
        
        $problems = [];
//         $accountUnActive = [];//未开启同步的账号
//         $tokenExpired = [];//授权失败的账号
//         $order_retrieve_errors = [];//获取订单失败
//         $initial_order_failed = [];//首次绑定时，获取订单失败
        foreach ($AliexpressAccounts as $account){
            $accountProblems = [];
            if($account['version'] == 'v2'){
            	if(!AliexpressInterface_Helper_Qimen::checkToken($account["sellerloginid"])){
            		$accountProblems["sellerloginid"] = $account["sellerloginid"];
            	}
            }
            else{
                $accountProblems["sellerloginid"] = $account["sellerloginid"];
            }
            
            if(!empty($accountProblems)){
                $problems[] = $accountProblems;
            }
        }
//         $problems=[
//             'unActive'=>$accountUnActive,
//             'token_expired'=>$tokenExpired,
//             'initial_failed'=>$initial_order_failed,
//             'order_retrieve_failed'=>$order_retrieve_errors,
//         ];
        return $problems;
    }
    
    
    /*
     * 获得stm oms 通用左侧菜单
     +---------------------------------------------------------------------------------------------
	 * @access 	static
	 +---------------------------------------------------------------------------------------------
	 * @param 	array 	$counter		订单数量统计数组
	 +---------------------------------------------------------------------------------------------
	 * @author	lzhl	2016/07/08		初始化
	 +---------------------------------------------------------------------------------------------
	 **/
    public static function get_aliexpress_left_menu_data($counter){
    	$MenuStatisticData = TrackingHelper::getMenuStatisticData();
    	$counter = self::getMenuStatisticData();
    	$left_menu_data = [
			TranslateHelper::t('速卖通业务待处理')=>[
				'icon'=>'icon-stroe',
				'items'=>[
					TranslateHelper::t('所有订单')=>[
						'url'=>Url::to(['/order/aliexpressorder/aliexpresslist'])."?menu_select=all"
					],
				],
			],

			TranslateHelper::t('订单业务流程')=>[
				'icon'=>'icon-stroe',
				'items'=>[
					TranslateHelper::t('同步订单')	=>[
						'url'=>Url::to(['/order/aliexpressorder/order-sync-info']),
						////'tabbar'=>'',
					],
					TranslateHelper::t('未付款')=>[
						'url'=>Url::to(['/order/aliexpressorder/aliexpresslist','order_status'=>OdOrder::STATUS_NOPAY]),
						 //'tabbar'=>$counter[OdOrder::STATUS_NOPAY]
					],
					TranslateHelper::t('已付款')=>[
						'url'=>Url::to(['/order/aliexpressorder/aliexpresslist','order_status'=>OdOrder::STATUS_PAY , 'pay_order_type'=>'all']),
						//'tabbar'=>$counter[OdOrder::STATUS_PAY]
					],		
					TranslateHelper::t('发货中')=>[
						'url'=>DeliveryHelper::getDeliveryModuleUrl('aliexpress'),
						'target'=>'_blank',
						'qtipkey'=>'@oms_faHuoZhong',
						 //'tabbar'=>$counter[OdOrder::STATUS_WAITSEND]
					],
					TranslateHelper::t('已完成')=>[
						'url'=>Url::to(['/order/aliexpressorder/aliexpresslist','order_status'=>OdOrder::STATUS_SHIPPED]),
					],
					TranslateHelper::t('已取消')=>[
						'url'=>Url::to(['/order/aliexpressorder/aliexpresslist','order_status'=>OdOrder::STATUS_CANCEL]),
						////'tabbar'=>$counter[OdOrder::STATUS_CANCEL]
					],
					TranslateHelper::t('暂停发货')=>[
						'url'=>Url::to(['/order/aliexpressorder/aliexpresslist','order_status'=>OdOrder::STATUS_SUSPEND]),
						//'tabbar'=>$counter[OdOrder::STATUS_SUSPEND]
					],
					TranslateHelper::t('缺货')=>[
						'url'=>Url::to(['/order/aliexpressorder/aliexpresslist','order_status'=>OdOrder::STATUS_OUTOFSTOCK]),
						//'tabbar'=>$counter[OdOrder::STATUS_OUTOFSTOCK]
					],
				],
			],
			/*
        	TranslateHelper::t('通知平台发货')=>[
				'icon'=>'icon-stroe',
				'items'=>[
					TranslateHelper::t('未通知平台发货')=>[
						//'qtipkey'=>'tracker_setting_platform_binding',
                	    'url'=>Url::to(['/order/informdelivery/noinformdelivery?shipping_status='.OdOrder::NO_INFORM_DELIVERY.'&is_exsit_tracking_number_order='.OdOrder::NO_TRACKING_NUMBER_ORDER]),
                	  	//'tabbar'=>$counter['shipping_status_0']
               	 	],
	                TranslateHelper::t('通知平台发货中')=>[
						//'qtipkey'=>'tracker_setting_platform_binding',
	                    'url'=>Url::to(['/order/informdelivery/processinformdelivery']),
	                  	//'tabbar'=>$counter['shipping_status_2']
	                ],
	                TranslateHelper::t('已通知平台发货')=>[
						//'qtipkey'=>'tracker_setting_platform_binding',
	                    'url'=>Url::to(['/order/informdelivery/alreadyinformdelivery?shipping_status='.OdOrder::ALREADY_INFORM_DELIVERY]),
	                    //'tabbar'=>$counter['shipping_status_1']
	                ],
				]
        	],
        	*/
        	TranslateHelper::t('通知及营销')=>[
        		'icon'=>'icon-stroe',
        		'items'=>[
        			TranslateHelper::t('启运通知')=>[
        				'tabbar'=>(!empty($MenuStatisticData['shipping_message']))?$MenuStatisticData['shipping_message']:0,
        				'qtipkey'=>'oms_tracking_request_shipping',
        				'url'=>Url::to(['/order/od-lt-message/list-tracking-message','platform'=>'aliexpress','pos'=>'RSHP','is_send'=>'N']),
	      			],
	      			/*	tracker 停用之后这两种情况可能难以获取，因此暂时不启用
        		    TranslateHelper::t('到达待取通知')=>[
        		   		'tabbar'=>(!empty($MenuStatisticData['arrived_pending_message']))?$MenuStatisticData['arrived_pending_message']:0,
        		      	'qtipkey'=>'oms_tracking_request_pending_fetch',
        		      	'url'=>Url::to(['/order/od-lt-message/list-tracking-message','platform'=>'aliexpress','pos'=>'RPF','is_send'=>'N']),
        		    ],
        		    TranslateHelper::t('异常退回通知')=>[
        		    	'tabbar'=>(!empty($MenuStatisticData['rejected_message']))?$MenuStatisticData['rejected_message']:0,
        		      	'qtipkey'=>'oms_tracking_request_rejected',
        		      	'url'=>Url::to(['/order/od-lt-message/list-tracking-message','platform'=>'aliexpress','pos'=>'RRJ','is_send'=>'N']),
        		    ],
        		    */
        		    TranslateHelper::t('已签收求好评')=>[
        		    	'tabbar'=>(!empty($MenuStatisticData['received_message']))?$MenuStatisticData['received_message']:0,
        		      	'qtipkey'=>'oms_tracking_request_good_evaluation',
        		      	'url'=>Url::to(['/order/od-lt-message/list-tracking-message','platform'=>'aliexpress','pos'=>'RGE','is_send'=>'N']),
        		    ],
        		    TranslateHelper::t('发信模板设置')=>[
						'qtipkey'=>'mail_template_setting',
        		    	'target'=>'_blank',
        		    	'url'=>Url::to(['/tracking/tracking/mail_template_setting']),
        		    ],
        		    TranslateHelper::t('二次营销商品设置')=>[
        		    	'qtipkey'=>'custom_recommend_setting',
        		    	'target'=>'_blank',
        		    	'url'=>Url::to(['/tracking/tracking-recommend-product/custom-product-list']),
        		    ],
        		    TranslateHelper::t('二次营销商品分组设置')=>[
        		   		'qtipkey'=>'custom_recommend_setting',
        		    	'target'=>'_blank',
        		    	'url'=>Url::to(['/tracking/tracking-recommend-product/group-list']),
        		    ],
        		    
        		],
        		
        	],
		];
    	return $left_menu_data;
    }







	public static function getOrderListManualByUid( $uid,$sellid='',$mt=30 ){
		$rsle= SaasAliexpressUser::find()->where(['uid'=>$uid])->asArray()->all();
		if( $sellid!='' ){
			$rsle= SaasAliexpressUser::find()->where(['sellerloginid'=>$sellid])->asArray()->all();
		}

		if( !empty( $rsle ) ){
			foreach( $rsle as $vsle ){
				$sellerloginid= $vsle['sellerloginid'];

				$connection=Yii::$app->db_queue;
				$return= array();
				// 检查授权是否过期或者是否授权,返回true，false

				if (!AliexpressInterface_Auth::checkToken ( $sellerloginid )) {
					//error_log("未授权".date("Y-m-d H:i:s"),3,'/tmp/smt_push.log');
					continue;
				}
				//获取
				echo date("Y-m-d");
				//同步状态值更改
				$update_arr = array();
				$update_arr['status'] = 1;
				$update_arr['last_time'] = time();

				$where_arr= array();
				$where_arr['sellerloginid']= $sellerloginid;
				$where_arr['type']= 'time';

				$res= SaasAliexpressAutosync::updateAll( $update_arr,$where_arr );
				//error_log("锁定店铺同步状态".date("Y-m-d H:i:s"),3,'/tmp/smt_push.log');
				//error_log("锁定店铺同步状态".date("Y-m-d H:i:s"),3,'/tmp/smt_push.log');

				$api = new AliexpressInterface_Api ();
				$access_token = $api->getAccessToken ( $sellerloginid );
				//获取访问token失败
				if ($access_token === false){
					echo ("访问token失败");
					continue;
				}
				$api->access_token = $access_token;

				//获取最新的同步信息
				$obj= SaasAliexpressAutosync::findOne( $where_arr );
				$time = time();
				if($obj->end_time == 0) {
					//初始同步
					$start_time = $obj->binding_time;
					$end_time = $time;
				}else {
					//增量同步
					$start_time = $obj->end_time-86400*$mt;
					$end_time = $time;
				}
				$format_start_time= self::getLaFormatTime ("m/d/Y H:i:s", $start_time );
				$format_end_time= self::getLaFormatTime ("m/d/Y H:i:s", $end_time );
				echo $format_start_time.'--'.$format_end_time;


				// 是否全部同步完成
				$success = true;

				 
				$uid= $obj->uid;

				 

				//////////////////////////////////////////////////////////////////////////////////////////////////////////
				//非FINISH状态的订单同步/////
				//获取最近读不到图片的item
				$no_photo_item = self::GetNoPhotoItem($start_time);
				//分页设置
				$page = 1;
				$pageSize = 50;
				$orders = [];
				$sendGoodsOperator_arr= array();
				do {
					$api_time= time();//接口调用时间
					// 接口传入参数
					$param = ['page' => $page, 'pageSize' => $pageSize, 'createDateStart' => $format_start_time, 'createDateEnd' => $format_end_time];
					//调用接口获取订单列表
					$result2 = $api->findOrderListQuery($param);
					//print_r ($result2);
					echo "get-result2",PHP_EOL;
					// 判断是否有订单
					if (!isset ( $result2['totalItem'] ) || !isset( $result2['orderList'])) {
						$success = false;
						break;
					}
					foreach ( $result2 ['orderList'] as $order ) {
						$orderid= $order['orderId'];

						$orders[$order['orderId']]['day'] = isset($order['leftSendGoodDay'])?$order['leftSendGoodDay']:0;
						$orders[$order['orderId']]['hour'] = isset($order['leftSendGoodHour'])?$order['leftSendGoodHour']:0;
						$orders[$order['orderId']]['min'] = isset($order['leftSendGoodMin'])?$order['leftSendGoodMin']:0;


						$logisticsServiceName_arr= array();
						$memo_arr= array();
						//买家物流信息
						if (isset($order['productList'])) {
							foreach ($order['productList'] as $pl) {
								//客选物流
								if (isset($pl['logisticsServiceName'])) {
									$logisticsServiceName = $pl['logisticsServiceName'];
									$productid = $pl['productId'];
									$logisticsServiceName_arr["shipping_service"][$productid] = $logisticsServiceName;
								}
								//买家备注
								if( isset($pl['memo']) ){
									$pmemo= str_replace("'","",$pl['memo']);
									if( $pmemo=='' ){
										$pmemo= '无';
									}
									$memo_arr[]= $pmemo;
									//$logisticsServiceName_arr["user_message"][$productid] = $memo;
								}
								//发货类型
								if (isset($pl['sendGoodsOperator'])) {
									$sendGoodsOperator = $pl['sendGoodsOperator'];
									$productid = $pl['productId'];
									$sendGoodsOperator_arr[$order['orderId']][$productid] = $sendGoodsOperator;
									
									//设置发货类型
									list( $order_status,$msg )= OrderHelper::updateOrderItemAddiInfoByOrderID( $orderid, $sellerloginid, $productid, 'aliexpress' , ['sendGoodsOperator' => $sendGoodsOperator] );
									if( $order_status===false ){
										echo $orderid.'可选物流更新失败--'.$msg.PHP_EOL;
									}
								}
								
								//更新图片Item
								/*$items = OdOrderItem::find()->where(['order_source_itemid' => $pl['productId'], 'order_source_transactionid' => $pl['childId'], 'order_source_order_id' => $orderid])->andWhere("create_time>1510358400")->all();
								if(!empty($pl['productImgUrl']) && !empty($items)){
								    echo $orderid.', '.$pl['productId'].', childId: '.$pl['childId'].', img: '.$pl['productImgUrl'].PHP_EOL;
								    foreach($items as $item){
    									$item->photo_primary = $pl['productImgUrl'];
    									$item->save(false);
								    }
								}*/
								
								//判断是否属于无图片的item，是则更新
								/*if(array_key_exists($sellerloginid.'_'.$pl['productId'], $no_photo_item)){
									$item = OdOrderItem::findOne(['order_item_id' => $no_photo_item[$sellerloginid.'_'.$pl['productId']]['order_item_id'], 'order_source_itemid' => $pl['productId'], 'order_source_transactionid' => $pl['childId'], 'order_source_order_id' => $orderid]);
									if(!empty($pl['productImgUrl']) && !empty($item)){
										$item->photo_primary = $pl['productImgUrl'];
										$item->save(false);
									}
								}*/
							}
						}
						$memo= '';
						if( !empty( $memo_arr ) ){
							$memo_eof= false;
							foreach( $memo_arr as $memo_vss ){
								if( $memo_vss!='无' ){
									$memo_eof= true;
									break;
								}
							}
							if( $memo_eof===true ){
								foreach( $memo_arr as $key=>$memo_vss ){
									$count= $key+1;
									$memo.= "商品{$count}:{$memo_vss};";
								}
							}
						}


						//设置客选物流
						if (!empty($logisticsServiceName_arr)) {

							list( $order_status,$msg )= OrderHelper::updateOrderAddiInfoByOrderID( $orderid, $sellerloginid , 'aliexpress' , $logisticsServiceName_arr );
							if( $order_status===false ){
								echo $orderid.'可选物流更新失败--'.$msg.PHP_EOL;
							}
						}

						//判断是否存在剩余发货时间的3个属性
						if( isset( $order['leftSendGoodDay'] ) ){
							//这个是天,换算成秒数
							$leftSendGoodDay= $order['leftSendGoodDay']*86400;
						}else{
							$leftSendGoodDay= 0;
						}
						if( isset( $order['leftSendGoodHour'] ) ){
							//这个是小时,换算成秒数
							$leftSendGoodHour= $order['leftSendGoodHour']*3600;
						}else{
							$leftSendGoodHour= 0;
						}
						if( isset( $order['leftSendGoodMin'] ) ){
							//这个是分组,换算成秒数
							$leftSendGoodMin= $order['leftSendGoodMin']*60;
						}else{
							$leftSendGoodMin= 0;
						}

						//如果都是0的话,不处理最后发货时间,有一个不是0,才去处理
						if( $leftSendGoodDay>0 || $leftSendGoodHour>0 || $leftSendGoodMin>0 ){
							//在接口调用时间上,加上秒数就是最后发货时间啦
							$fulfill_deadline= ceil($leftSendGoodDay+$leftSendGoodHour+$leftSendGoodMin+$api_time);
							//更新掉字段
							Yii::$app->subdb->createCommand("UPDATE od_order_v2 SET fulfill_deadline='{$fulfill_deadline}' WHERE order_source_order_id='{$orderid}'")->execute();
							//echo "UPDATE od_order_v2 SET fulfill_deadline='{$fulfill_deadline}' WHERE order_source_order_id='{$orderid}'",PHP_EOL;
						}else{
						}

						//设置买家备注
						//echo ("UPDATE od_order_v2 SET user_message='{$memo}' WHERE order_source_order_id='{$orderid}'");
						//echo PHP_EOL;
						if( $memo!='' ) {
							//$rof= OdOrder::updateAll( ['user_message'=>$memo],"order_source_order_id='{$orderid}'" );
							//需要获取自增id
							$ro = OdOrder::findOne(['order_source_order_id' => $orderid]);
							if (!empty($ro)) {
								$sysTagRt = OrderTagHelper::setOrderSysTag($ro->order_id, 'pay_memo');
								if (isset($sysTagRt['code']) && $sysTagRt['code'] == '400') {
									echo '\n' . $ro->order_id . ' insert pay_memo failure :' . $sysTagRt['message'];
								}
							}
							$rof= Yii::$app->subdb->createCommand("UPDATE od_order_v2 SET user_message='{$memo}' WHERE order_source_order_id='{$orderid}'")->execute();
						}
					}
					$page ++;
					$p = ceil($result2['totalItem']/50);
				} while ( $page <= $p );

				//重置
				$page = 1;
				do {
					// 接口传入参数
					$param = ['page' => $page, 'pageSize' => $pageSize, 'createDateStart' => $format_start_time, 'createDateEnd' => $format_end_time];
					// 调用接口获取订单列表
					$result = $api->findOrderListSimpleQuery($param);
					//print_r ($result);
					echo "get-result",PHP_EOL;
					//判断是否有订单
					if (!isset ( $result['totalItem'] )) {
						$success = false;
						break;
					}

					//保存本次同步订单数
					SaasAliexpressAutosync::updateAll( ['order_item'=>$result ['totalItem']],['id'=>$obj->id] );

					if($result ['totalItem'] > 0) {
						// 保存数据到同步订单详情队列
						//foreach begain
						foreach ( $result ['orderList'] as $one ) {
							$orderid = $one['orderId'];
							echo PHP_EOL,$orderid,PHP_EOL;
							//if( $orderid!='78668784181321' ){
							//continue;
							//}
							//订单产生时间
							$gmtCreate = AliexpressInterface_Helper::transLaStrTimetoTimestamp($one['gmtCreate']);
							$order_info = self::_setOrderDay($orders, $one);
							
							//发货类型
							if(!empty($sendGoodsOperator_arr[$orderid])){
								$order_info['sendGoodsOperator'] = $sendGoodsOperator_arr[$orderid];
							}

							//队列4中是否存在这个订单
							$rs_order4 = $connection->createCommand("SELECT id FROM queue_aliexpress_getorder4 WHERE orderid='{$orderid}' ORDER BY id DESC  ")->query()->read();
							if ( $rs_order4===false ) {
								//没有数据,写入队列4
								$QAG_four = new QueueAliexpressGetorder4();
								$QAG_four->uid = $uid;
								$QAG_four->sellerloginid = $sellerloginid;
								$QAG_four->aliexpress_uid = $obj->aliexpress_uid;
								$QAG_four->order_status = $one['orderStatus'];
								$QAG_four->orderid = $orderid;
								$QAG_four->order_info = json_encode($order_info);
								$QAG_four->gmtcreate = $gmtCreate;
								$boolfour = $QAG_four->save();

								$newid= $QAG_four->primaryKey;
							} else {
								//有数据,不处理
								$boolfour= true;
								$newid= $rs_order4['id'];
							}




							//推送表中是否存在未锁定,未处理的数据,存在就锁定了,省的后面重复处理
							//管他有没有这个订单号的,统一update掉算了,反正没有的话,也就update false而已
							$auto_order_eof = $connection->createCommand("UPDATE queue_aliexpress_auto_order SET is_lock=1  WHERE order_id='{$orderid}' ")->execute();

							//同步数据到用户表的小老板订单中
							if ($boolfour === true) {
								$getorder4_obj= QueueAliexpressGetorder4::findOne( $newid );
								$param = ['orderId' => $orderid];
								$res= $api->findOrderById ( $param );
								//print_r ($res);exit;
								$res["sellerOperatorLoginId"]= $getorder4_obj->sellerloginid;
								echo '店铺ID---',$res["sellerOperatorLoginId"],PHP_EOL;
								print_r ( $getorder4_obj );
								print_r ($res);
								$r = AliexpressInterface_Helper::saveAliexpressOrder ( $getorder4_obj, $res );
								if( $r['success']==0 ){
									//同步成功
									$update_t= date("Y-m-d H:i:s");
									$connection->createCommand("UPDATE queue_aliexpress_auto_order SET `status`=3,update_time='{$update_t}'  WHERE order_id='{$orderid}' ")->execute();
									//error_log("同步成功".date("Y-m-d H:i:s"),3,'/tmp/smt_push.log');

									echo 1;
								}else{
									//同步失败
									if( isset($r['message']) && isset( $r['success'] ) ){
										$error= $r['success'].'--'.$r['message'];
									}else{
										$error= '订单更新失败';
									}
									$update_t= date("Y-m-d H:i:s");

									$connection->createCommand("UPDATE queue_aliexpress_auto_order SET `status`=2,update_time='{$update_t}',error_message='{$error}'  WHERE order_id='{$orderid}' ")->execute();
									//error_log("同步失败".date("Y-m-d H:i:s"),3,'/tmp/smt_push.log');
									$success = false;
									echo 2;
								}
							}
						}
						//end foreach
					}
					$page ++;
					$p = ceil($result['totalItem']/50);
				} while ( $page <= $p );




				//////////////////////////////////////////////////////////////////////////////////////////////////////////
				//获取订单状态为FINISH的订单//////
				//分页设置
				$page = 1;
				$pageSize = 50;
				$orders = [];
				$sendGoodsOperator_arr= array();
				do {
					$api_time= time();
					// 接口传入参数
					$param = ['page' => $page, 'pageSize' => $pageSize, 'createDateStart' => $format_start_time, 'createDateEnd' => $format_end_time,'orderStatus'=>'FINISH'];
					//调用接口获取订单列表
					$result2 = $api->findOrderListQuery($param);

					// 判断是否有订单
					if (!isset ( $result2['totalItem'] ) || !isset( $result2['orderList'])) {
						$success = false;
						break;
					}
					foreach ( $result2 ['orderList'] as $order ) {
						$orderid= $order['orderId'];
						$orders[$order['orderId']]['day'] = isset($order['leftSendGoodDay'])?$order['leftSendGoodDay']:0;
						$orders[$order['orderId']]['hour'] = isset($order['leftSendGoodHour'])?$order['leftSendGoodHour']:0;
						$orders[$order['orderId']]['min'] = isset($order['leftSendGoodMin'])?$order['leftSendGoodMin']:0;

						$logisticsServiceName_arr= array();

						//买家物流信息
						if (isset($order['productList'])) {
							foreach ($order['productList'] as $pl) {
								if (isset($pl['logisticsServiceName'])) {
									$logisticsServiceName = $pl['logisticsServiceName'];
									$productid = $pl['productId'];
									$logisticsServiceName_arr["shipping_service"][$productid] = $logisticsServiceName;
								}

								//发货类型
								if (isset($pl['sendGoodsOperator'])) {
									$sendGoodsOperator = $pl['sendGoodsOperator'];
									$productid = $pl['productId'];
									$sendGoodsOperator_arr[$order['orderId']][$productid] = $sendGoodsOperator;
									
									//设置发货类型
									list( $order_status,$msg )= OrderHelper::updateOrderItemAddiInfoByOrderID( $orderid, $sellerloginid, $productid, 'aliexpress' , ['sendGoodsOperator' => $sendGoodsOperator] );
									if( $order_status===false ){
										echo $orderid.'可选物流更新失败--'.$msg.PHP_EOL;
									}
								}
								
								//更新图片Item
								/*$items = OdOrderItem::find()->where(['order_source_itemid' => $pl['productId'], 'order_source_transactionid' => $pl['childId'], 'order_source_order_id' => $orderid])->andWhere("create_time>1510358400")->all();
								if(!empty($pl['productImgUrl']) && !empty($items)){
									echo $orderid.', '.$pl['productId'].', childId: '.$pl['childId'].', img: '.$pl['productImgUrl'].PHP_EOL;
									foreach($items as $item){
										$item->photo_primary = $pl['productImgUrl'];
										$item->save(false);
									}
								}*/
							}
						}
						//设置客选物流
						if (!empty($logisticsServiceName_arr)) {
							list( $order_status,$msg )= OrderHelper::updateOrderAddiInfoByOrderID( $orderid, $sellerloginid , 'aliexpress' , $logisticsServiceName_arr );
							if( $order_status===false ){
								echo $orderid.'可选物流更新失败'.PHP_EOL;
							}
						}


					}
					$page ++;
					$p = ceil($result2['totalItem']/50);
				} while ( $page <= $p );

				//重置
				$page = 1;
				do {
					// 接口传入参数
					$param = ['page' => $page, 'pageSize' => $pageSize, 'createDateStart' => $format_start_time, 'createDateEnd' => $format_end_time,'orderStatus'=>'FINISH'];
					// 调用接口获取订单列表
					$result = $api->findOrderListSimpleQuery($param);
					//判断是否有订单
					if (!isset ( $result['totalItem'] )) {
						$success = false;
						break;
					}

					//保存本次同步订单数
					SaasAliexpressAutosync::updateAll( ['order_item'=>$result ['totalItem']],['id'=>$obj->id] );

					if($result ['totalItem'] > 0) {
						// 保存数据到同步订单详情队列
						//foreach begain
						foreach ( $result ['orderList'] as $one ) {
							$orderid = $one['orderId'];
							echo 'FINISH--',$orderid,PHP_EOL;
							//订单产生时间
							$gmtCreate = AliexpressInterface_Helper::transLaStrTimetoTimestamp($one['gmtCreate']);
							$order_info = self::_setOrderDay($orders, $one);
							
							//发货类型
							if(!empty($sendGoodsOperator_arr[$orderid])){
								$order_info['sendGoodsOperator'] = $sendGoodsOperator_arr[$orderid];
							}

							//队列4中是否存在这个订单
							$rs_order4 = $connection->createCommand("SELECT id FROM queue_aliexpress_getorder4 WHERE orderid='{$orderid}' ORDER BY id DESC  ")->query()->read();
							if ( $rs_order4===false ) {
								//没有数据,写入队列4
								$QAG_four = new QueueAliexpressGetorder4();
								$QAG_four->uid = $uid;
								$QAG_four->sellerloginid = $sellerloginid;
								$QAG_four->aliexpress_uid = $obj->aliexpress_uid;
								$QAG_four->order_status = $one['orderStatus'];
								$QAG_four->orderid = $orderid;
								$QAG_four->order_info = json_encode($order_info);
								$QAG_four->gmtcreate = $gmtCreate;
								$boolfour = $QAG_four->save();

								$newid= $QAG_four->primaryKey;
							} else {
								//有数据,不处理
								$boolfour= true;
								$newid= $rs_order4['id'];
							}



							//推送表中是否存在未锁定,未处理的数据,存在就锁定了,省的后面重复处理
							//管他有没有这个订单号的,统一update掉算了,反正没有的话,也就update false而已
							$auto_order_eof = $connection->createCommand("UPDATE queue_aliexpress_auto_order SET is_lock=1  WHERE order_id='{$orderid}' ")->execute();


							//同步数据到用户表的小老板订单中
							if ($boolfour === true) {
								$getorder4_obj= QueueAliexpressGetorder4::findOne( $newid );
								$param = ['orderId' => $orderid];
								$res= $api->findOrderById ( $param );
								$res["sellerOperatorLoginId"]= $getorder4_obj->sellerloginid;
								echo '店铺ID---',$res["sellerOperatorLoginId"],PHP_EOL;
								$r = AliexpressInterface_Helper::saveAliexpressOrder ( $getorder4_obj, $res );
								if( $r['success']==0 ){
									$update_t= date("Y-m-d H:i:s");
									//同步成功
									$connection->createCommand("UPDATE queue_aliexpress_auto_order SET `status`=3,update_time='{$update_t}'  WHERE order_id='{$orderid}' ")->execute();

									//error_log("同步成功".date("Y-m-d H:i:s"),3,'/tmp/smt_push.log');

									echo 1;
								}else{
									//同步失败
									if( isset($r['message']) && isset( $r['success'] ) ){
										$error= $r['success'].'--'.$r['message'];
									}else{
										$error= '订单更新失败';
									}
									$update_t= date("Y-m-d H:i:s");
									$connection->createCommand("UPDATE queue_aliexpress_auto_order SET `status`=2,error_message='{$error}',update_time='{$update_t}'  WHERE order_id='{$orderid}' ")->execute();
									//error_log("同步失败".date("Y-m-d H:i:s"),3,'/tmp/smt_push.log');
									$success = false;
									echo 2;
								}
							}
						}
						//end foreach
					}
					$page ++;
					$p = ceil($result['totalItem']/50);
				} while ( $page <= $p );

				//////////////////////////////////////////////////////////////////////////////////////////////////////////
				//获取用户v2订单表中,所有没有设定买家物流并且符合订单状态的数据
				//$rd= Yii::$app->subdb->createCommand("SELECT order_source_order_id,order_id,order_source_status,order_source_create_time FROM od_order_v2 where addi_info is not null and  order_source ='aliexpress' and selleruserid ='{$sellerloginid}' AND ( order_source_status='WAIT_BUYER_ACCEPT_GOODS' OR order_source_status='FINISH' ) ")->query()->readAll();




				//////////////////////////////////////////////////////////////////////////////////////////////////////////

				//完了,解锁
				$update_arr = array();
				$update_arr['end_time'] = $end_time;
				$update_arr['status'] = 0;
				$update_arr['last_time'] = time();
				$update_arr['next_time']= time()+3600;

				$where_arr= array();
				$where_arr['uid']= $uid;
				$where_arr['type']= 'time';

				SaasAliexpressAutosync::updateAll( $update_arr,$where_arr );

			}
		}

		//error_log("解锁成功".date("Y-m-d H:i:s"),3,'/tmp/smt_push.log');
		return true;

	}
	//end function


	/**
	 * 指定一个订单更新
	 * akirametero
	 */
	public static function updateOrderOrder( $orderid,$sellerloginid ){
		$db= Yii::$app->db;
		$connection=Yii::$app->db_queue;
		$rs= $db->createCommand("SELECT uid,aliexpress_uid FROM saas_aliexpress_user WHERE sellerloginid ='{$sellerloginid}' AND is_active=1 ")->query();
		$rs_user= $rs->read();

		 
		$api = new AliexpressInterface_Api ();
		if (!AliexpressInterface_Auth::checkToken ( $sellerloginid )) {
			return array( false,'授权失败' );
		}
		$access_token = $api->getAccessToken ( $sellerloginid );
		//获取访问token失败
		if ($access_token === false){
			return array( false,'token无法使用' );
		}
		$api->access_token = $access_token;

		//通过orderid 判断用户库的小老板订单表是否有这个订单
		$rf_od= Yii::$app->subdb->createCommand("SELECT order_id,paid_time,fulfill_deadline,order_source_create_time,order_source_status FROM od_order_v2 WHERE order_source_order_id='{$orderid}' AND order_source ='aliexpress' ")->query()->read();
		if( !empty( $rf_od ) ){
			// 接口传入参数速卖通订单号
			$param = ['orderId' => $orderid];
			$res= $api->findOrderById ( $param );
			$paramx = array(
				'page' => 1,
				'pageSize' => 50,
			);
			//先试试1秒的误差
			$start_time= $rf_od['order_source_create_time'];
			$end_time= $rf_od['order_source_create_time'];
			$orderStatus= $rf_od['order_source_status'];
			$paramx['createDateStart'] = self::getLaFormatTime("m/d/Y H:i:s", $start_time);
			$paramx['createDateEnd'] = self::getLaFormatTime("m/d/Y H:i:s", $end_time);
			//$paramx['orderStatus']= $orderStatus;
			$api_time= time();//接口调用时间
			$result2 = $api->findOrderListQuery($paramx);
			$order_info = array();
			if( isset( $result2['totalItem'] ) && isset( $result2['orderList'] ) ){
				if( !empty( $result2['orderList'] ) ){

					$leftSendGoodDay= 0;
					$leftSendGoodHour= 0;
					$leftSendGoodMin= 0;
					//买家指定的物流服务
					$logisticsServiceName_arr= array();
					$memo_arr= array();
					foreach ( $result2 ['orderList'] as $ordervs ) {
						if($ordervs['orderId'] != $orderid){
							continue;
						}
						$order_info = [
							'bizType' => $ordervs['bizType'],
							'gmtCreate' => $ordervs['gmtCreate'],
							'memo' => '',
							'orderId' => $ordervs['orderId'],
							'orderStatus' => $ordervs['orderStatus'],
							'product_list' => [],
							'day' => isset($ordervs['leftSendGoodDay']) ? $ordervs['leftSendGoodDay'] : 0,
							'hour' => isset($ordervs['leftSendGoodHour']) ? $ordervs['leftSendGoodHour'] : 0,
							'min' => isset($ordervs['leftSendGoodMin']) ? $ordervs['leftSendGoodMin'] : 0,
							'api_time' => $api_time,
						];
						//循环找到对应的orderid的数组
						if( isset( $ordervs['productList'] ) ){
							\Yii::info("用户puid".$rs_user['uid'].json_encode($ordervs['productList']),"file");
							foreach( $ordervs['productList'] as $pl ){
								$order_info['productList'][] = [
									'childId' => number_format($pl['childId'], 0, '', ''),
									'moneyBack3x' => $pl['moneyBack3x'],
									'productCount' => $pl['productCount'],
									'productId' => strval($pl['productId']),
									'productImgUrl' => $pl['productImgUrl'],
									'productName' => $pl['productName'],
									'productSnapUrl' => $pl['productSnapUrl'],
									'productUnit' => $pl['productUnit'],
									'productUnitPrice' => $pl['productUnitPrice']['amount'],
									'productUnitPrice' => $pl['productUnitPrice']['currencyCode'],
									'skuCode' => empty($pl['skuCode']) ? '' : $pl['skuCode'],
									'sonOrderStatus' => $pl['sonOrderStatus'],
								];
								
								//客选物流
								if( isset( $pl['logisticsServiceName'] ) ){
									$logisticsServiceName= $pl['logisticsServiceName'];
									$productid= $pl['productId'];
									$logisticsServiceName_arr["shipping_service"][$productid]= $logisticsServiceName;
								}
								//买家备注
								if( isset($pl['memo']) ){
									$pmemo= str_replace("'","",$pl['memo']);
									if( $pmemo=='' ){
										$pmemo= '无';
									}
									$memo_arr[]= $pmemo;
									//$logisticsServiceName_arr["user_message"][$productid] = $memo;
								}
								//发货类型
								if (isset($pl['send_goods_operator'])) {
									$productid= strval($pl['product_id']);
									$sendGoodsOperator = $pl['send_goods_operator'];
									$sendGoodsOperator_arr[$productid] = $sendGoodsOperator;
								}
							}
						}
						
						//客选物流
						if(!empty($logisticsServiceName_arr)){
							$order_info['logisticsServiceName_arr'] = $logisticsServiceName_arr;
						}
						//买家备注
						if(!empty($memo_arr)){
							$order_info['memo_arr'] = $memo_arr;
						}
						//发货类型
						if(!empty($sendGoodsOperator_arr)){
							$order_info['sendGoodsOperator'] = $sendGoodsOperator_arr;
						}
						//判断是否存在剩余发货时间的3个属性
						if( isset( $ordervs['leftSendGoodDay'] ) ){
							//这个是天,换算成秒数
							$leftSendGoodDay= $ordervs['leftSendGoodDay']*86400;
						}else{
						}
						if( isset( $ordervs['leftSendGoodHour'] ) ){
							//这个是小时,换算成秒数
							$leftSendGoodHour= $ordervs['leftSendGoodHour']*3600;
						}else{
						}
						if( isset( $ordervs['leftSendGoodMin'] ) ){
							//这个是分组,换算成秒数
							$leftSendGoodMin= $ordervs['leftSendGoodMin']*60;
						}else{
						}
						//update 买家指定的物流服务商
						print_r ($logisticsServiceName_arr);
						if( !empty( $logisticsServiceName_arr ) ){
							list( $order_status,$msg )= OrderHelper::updateOrderAddiInfoByOrderID( $orderid, $sellerloginid , 'aliexpress' , $logisticsServiceName_arr );

						}
						//处理最后发货时间,如果都是0的话,不处理最后发货时间,有一个不是0,才去处理
						if( $leftSendGoodDay>0 || $leftSendGoodHour>0 || $leftSendGoodMin>0 ){
							//在接口调用时间上,加上秒数就是最后发货时间啦
							$fulfill_deadline= ceil($leftSendGoodDay+$leftSendGoodHour+$leftSendGoodMin+$api_time);
							//更新掉字段
							Yii::$app->subdb->createCommand("UPDATE od_order_v2 SET fulfill_deadline='{$fulfill_deadline}' WHERE order_id=".$rf_od['order_id'])->execute();
						}else{
						}

						$memo= '';
						if( !empty( $memo_arr ) ){
							$memo_eof= false;
							foreach( $memo_arr as $memo_vss ){
								if( $memo_vss!='无' ){
									$memo_eof= true;
									break;
								}
							}
							if( $memo_eof===true ){
								foreach( $memo_arr as $key=>$memo_vss ){
									$count= $key+1;
									$memo.= "商品{$count}:{$memo_vss};";
								}
							}
						}
						//处理买家备注
						if( $memo='' ){
							//update
							Yii::$app->subdb->createCommand("UPDATE od_order_v2 SET user_message='{$memo}' WHERE order_id=".$rf_od['order_id'])->execute();
							$sysTagRt = OrderTagHelper::setOrderSysTag($rf_od['order_id'], 'pay_memo');
						}
					}
				}
			}

			//更新订单状态以及更新订单信息
			$rs_orderinfo= $connection->createCommand("SELECT id FROM queue_aliexpress_getorder4 WHERE orderid='{$orderid}' ORDER BY  id DESC")->query()->read();
			if( empty( $rs_orderinfo ) ){
				//没有数据,写入队列4
				$QAG_four = new QueueAliexpressGetorder4();
				$QAG_four->uid = $rs_user['uid'];
				$QAG_four->sellerloginid = $sellerloginid;
				$QAG_four->aliexpress_uid = $rs_user['aliexpress_uid'];
				$QAG_four->order_status = $res['orderStatus'];
				$QAG_four->orderid = $orderid;
				$QAG_four->order_info = json_encode($order_info);
				$QAG_four->gmtcreate = AliexpressInterface_Helper::transLaStrTimetoTimestamp($res['gmtCreate']);
				$QAG_four->save();
					
				$newid= $QAG_four->primaryKey;
			} else {
				$newid= $rs_orderinfo['id'];
			}
			$QAG_obj = QueueAliexpressGetorder4::findOne($newid);
			if(empty($QAG_obj)){
				return '数据异常';
			}
			
			if (isset($res['id']))  $res['id']=strval($res['id']);
			//这里强制转成主账号loginid，这是为了后面保存到订单od_order_v2的订单信息能对应上绑定的账号
			$res["sellerOperatorLoginId"]= $QAG_obj->sellerloginid;

			$r = AliexpressInterface_Helper::saveAliexpressOrder ( $QAG_obj, $res );
			if($r['success'] != 0 ) {
				return array(false,'更新失败');
			}else{
				return array(true,'更新成功');
			}
		}else{
			return array( false,'订单不存在' );
		}
	}

	//end function
	
	/*
	 * 获取订单不正常的item信息
	+---------------------------------------------------------------------------------------------
	* @author	lrq		2017/10/23		初始化
	+---------------------------------------------------------------------------------------------
	**/
	public static function GetNoPhotoItem($start_time){
		$data = array();
		try{
			$connection = Yii::$app->subdb;
			$items = $connection->createCommand("SELECT item.order_item_id, item.order_source_itemid,od.selleruserid,order_source_transactionid FROM `od_order_item_v2` item left join od_order_v2 od on od.order_id = item.order_id where od.order_source_create_time>$start_time and od.order_source='aliexpress' and photo_primary like '%no_photo.gif%'")->queryall();
			if(!empty($items)){
				foreach($items as $item){
					$data[$item['selleruserid'].'_'.$item['order_source_itemid'].'_'.$item['order_source_transactionid']] = $item;
				}
			}
		}
		catch(\Exception $ex){
		}
		
		return $data;
	}
}