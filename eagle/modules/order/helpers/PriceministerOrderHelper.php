<?php
namespace eagle\modules\order\helpers;

use yii;
use eagle\modules\order\models\PriceministerOrder;
use eagle\modules\order\helpers\OrderHelper;
use eagle\modules\order\models\PriceministerOrderDetail;
use eagle\modules\util\helpers\OperationLogHelper;
use eagle\modules\util\helpers\TimeUtil;
use eagle\modules\util\helpers\EagleOneHttpApiHelper;
use eagle\modules\util\helpers\SQLHelper;
use eagle\models\SaasPriceministerUser;
use eagle\modules\listing\helpers\PriceministerOfferSyncHelper;
use eagle\modules\order\models\OdOrder;
use eagle\modules\order\models\OdOrderItem;
use eagle\modules\listing\models\PriceministerProductList;
use eagle\models\sys\SysCountry;
use eagle\models\SaasPriceministerAutosync;
use eagle\modules\util\helpers\SysLogHelper;
use eagle\modules\order\models\PriceministerSyncOrderItem;
use eagle\modules\util\helpers\TranslateHelper;
use eagle\modules\platform\apihelpers\PriceministerAccountsApiHelper;
use eagle\modules\util\helpers\ConfigHelper;
use eagle\modules\util\helpers\UserLastActionTimeHelper;
use eagle\modules\tracking\helpers\TrackingAgentHelper;
use eagle\modules\util\helpers\ImageCacherHelper;


/*+----------------------------------------------------------------------
| 小老板
+----------------------------------------------------------------------
| Copyright (c) 2011 http://www.xiaolaoban.cn All rights reserved.
+----------------------------------------------------------------------
| Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
+----------------------------------------------------------------------
| Author: yzq
+----------------------------------------------------------------------
| Create Date: 2014-12-08
+----------------------------------------------------------------------
 */

/**
 +------------------------------------------------------------------------------
 * Priceminister订单模板业务
 +------------------------------------------------------------------------------
 * @category	item
 * @package		Helper/item
 * @subpackage  Exception
 * @author		lzhl
 +------------------------------------------------------------------------------
 */
class PriceministerOrderHelper {
	private static $manualQueueVersion = '';
	public static $orderStatus_Unshipped='current';
	
	public static $orderStatus = array(
		'preorder'=>'预售',
		'new'=>'待接受/拒绝',
		'current'=>'已接受/拒绝',
		'tracking'=>'已发货',
		'claim'=>'有申诉/理赔',
		'refused'=>'已拒绝',
		'cancelled'=>'已取消',
		'closed'=>'已完成',
	);
	
	public static $itemStatus = array(
		'TO_CONFIRM'=>'待接受/拒绝',//Sales to accept
		'CANCELLED'=>'已取消',//Cancellation pre-acceptance by the customer
		'REFUSED'=>'卖家已拒绝',//Cancellation pre-acceptance by the merchant
		'ACCEPTED'=>'待确认收货',//Sales accepted – pending receipt of customer
		'PENDING'=>'等待商品上市',//Awaiting stock (preorder)
		'PENDING_CLAIM'=>'申诉/理赔中',//Ongoing Claim
		'REQUESTED'=>'待接受/拒绝',//Sales to accept
		'REMINDED'=>'待接受/拒绝',//Sales to accept
		'COMMITTED'=>'待确认收货',//Sales accepted – pending receipt of customer
		'ON_HOLD'=>'申诉/理赔中',//Ongoing Claim
		'CLOSED'=>'销售完成',//Sale finalized, rated seller
		'CLAIM'=>'申诉/理赔中',//Ongoing Claim
		
	);
	
	public static $ShippingCode = array(
		'Normal'=>'Normal',
		'Suivi'=>'Suivi',
		'Recommandé'=>'Recommandé',
		'So Colissimo'=>'So Colissimo',
		'Chronopost'=>'Chronopost',
		'Express'=>'Express',
		'Point relais Mondial Relay'=>'Point relais Mondial Relay',
		'Relai Kiala'=>'Relai Kiala',
		'Retrait chez le vendeur'=>'Retrait chez le vendeur',
	);
	
	public static function getPriceministerOrderShippingCode(){
		return self::$ShippingCode;
	}
	
	public static $PM_CountryCode_Mapping = [
		'France métro.'=>	['code'=>'FR','en'=>'France'],
		'Andorre'=>			['code'=>'AD','en'=>'Andorra'],//安道尔
		'Belgique'=>		['code'=>'BE','en'=>'Belgium'],//比利时
		'Luxembourg'=>		['code'=>'LU','en'=>'Luxembourg'],//卢森堡
		'Suisse'=>			['code'=>'CH','en'=>'Switzerland'],//瑞士
		'Guadeloupe'=>		['code'=>'GP','en'=>'Guadeloupe'],//瓜德罗普岛
		'Guyane'=>			['code'=>'GF','en'=>'French Guiana'],//法属圭亚那:French Guiana
		'Martinique'=>		['code'=>'MQ','en'=>'Martinique'],//Martinique:马提尼克
		'La Réunion'=>		['code'=>'RE','en'=>'Réunion'],//Réunion:留尼汪
		'Allemagne'=>		['code'=>'DE','en'=>'Germany'],//德国:Germany
		'Autriche'=>		['code'=>'AT','en'=>'Austria'],//奥地利：Austria
		'Canada'=>			['code'=>'CA','en'=>'Canada'],//加拿大:Canada
		'Danemark'=>		['code'=>'DK','en'=>'Denmark'],//丹麦:Denmark
		'Espagne'=>			['code'=>'ES','en'=>'Spain'],//西班牙：Spain
		'Etats-Unis'=>		['code'=>'US','en'=>'United States'],//美国:United States
		'Finlande'=>		['code'=>'FI','en'=>'Finland'],//芬兰:Finland
		'Gibraltar'=>		['code'=>'GI','en'=>'Gibraltar'],//直布罗陀:Gibraltar
		'Grèce'=>			['code'=>'GR','en'=>'Greece'],//希腊:Greece
		'Irlande'=>			['code'=>'IT','en'=>'Ireland'],//爱尔兰:Ireland
		'Italie'=>			['code'=>'IT','en'=>'Italy'],//意大利:Italy
		'Liechtenstein'=>	['code'=>'LI','en'=>'Liechtenstein'],//列支敦士登:Liechtenstein
		'Norvège'=>			['code'=>'NO','en'=>'Norway'],//挪威:Norway
		'Pays bas'=>		['code'=>'NL','en'=>'Netherlands'],//荷兰:Netherlands
		'Portugal'=>		['code'=>'PT','en'=>'Portugal'],//葡萄牙:Portugal
		'Royaume uni'=>		['code'=>'GB','en'=>'Great Britain'],//Great Britain
		'Suède'=>			['code'=>'SF','en'=>'Sweden'],//瑞典:Sweden
		'Vatican'=>			['code'=>'VA','en'=>'Vatican'],//Vatican:梵蒂冈
	];
	
	public static function test(){
		try {
			$half_hours_ago = date('Y-m-d H:i:s',strtotime('-30 minutes'));//600 m is test ,real value is 30
			$month_ago = date('Y-m-d H:i:s',strtotime('-30 days'));
			//update the new accounts first
			$command = Yii::$app->db->createCommand("update saas_priceminister_user set last_order_success_retrieve_time='0000-00-00 00:00:00',last_order_retrieve_time='0000-00-00 00:00:00'
										where last_order_success_retrieve_time is null or last_order_retrieve_time is null"  );
			$affectRows = $command->execute();
				
			$SAASCDISCOUNTUSERLIST = SaasPriceministerUser::find()->where("is_active='1' ")->orderBy("last_order_success_retrieve_time asc")->all();
				
			//retrieve orders  by  each wish account
			foreach($SAASCDISCOUNTUSERLIST as $priceministerAccount ){
				$uid = $priceministerAccount['uid'];
		
				echo "<br>YS1 start to fetch for unfuilled uid=$uid ... ";
				if (empty($uid)){
				//异常情况
					$message = "site id :".$priceministerAccount['site_id']." uid:0";
					echo $message;
					return false;
				}
		
 
		
				$updateTime =  TimeUtil::getNow();
				//update this wish account as last order retrieve time
				$priceministerAccount->last_order_retrieve_time = $updateTime;
				$PmOrderInterface = new PriceministerOrderInterface();
				$PmOrderInterface->setStoreNamePwd($priceministerAccount['username'], $priceministerAccount['token']);
				$apiReturn = $PmOrderInterface->GetSales('current');
					
				print_r($apiReturn);

			}//end of each wish user account
		} catch (\Exception $e) {
			echo "<br>uid retrieve order :".$e->getMessage();
		}
		
	}
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 订单同步情况 数据
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param $status					指定状态筛选 （可选）
	 * @param $lasttime 				指定时间筛选 （可选）
	 +---------------------------------------------------------------------------------------------
	 * @return array ('message'=>执行详细结果
	 * 				  'success'=> true 成功 false 失败	)
	 +---------------------------------------------------------------------------------------------
	 * log			name		date				note
	 * @author		lzhl		2016/05/27			初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function getOrderSyncInfoDataList($status = '' , $lasttime =''){
		$userInfo = \Yii::$app->user->identity;
		if ($userInfo['puid']==0){
			$uid = $userInfo['uid'];
		}else {
			$uid = $userInfo['puid'];
		}
		$AccountList = PriceministerAccountsApiHelper::ListAccounts($uid);
		$syncList = [];
		foreach($AccountList as $account){
			$orderSyncInfo = PriceministerAccountsApiHelper::getPriceministerOrderSyncInfo($account['site_id'],$account['uid']);
			if($orderSyncInfo['success']){
				$syncList[$account['username']] = $orderSyncInfo['result'];//is_active;last_time;message;status
				$syncList[$account['username']]['store_name'] = $account['store_name'];
			}else{
				$syncList[$account['username']]['store_name'] = $account['store_name'];
				$syncList[$account['username']]['is_active'] = '--';
				$syncList[$account['username']]['last_time'] = '--';
				$syncList[$account['username']]['message'] = '无该账号信息';
				$syncList[$account['username']]['status'] = '';
			}
		}
		return $syncList;
	}//end of getOrderSyncInfoDataList
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 后台获取新绑定帐号一个月内的订单(current)
	 +---------------------------------------------------------------------------------------------
	 * @description			获取一个月内的订单
	 +---------------------------------------------------------------------------------------------
	 * log			name		date				note
	 * @author		lzhl		2016-3-31			初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	public static function cronAutoFetchNewAccountOrderList(){
		try {
			$half_hours_ago = date('Y-m-d H:i:s',strtotime('-30 minutes'));//600 m is test ,real value is 30
			$month_ago = date('Y-m-d H:i:s',strtotime('-30 days'));
				
			$assaPmAccounts = SaasPriceministerUser::find()->where("is_active='1' and initial_fetched_changed_order_since is null or initial_fetched_changed_order_since='0000-00-00 00:00:00' or last_order_success_retrieve_time='0000-00-00 00:00:00' ")->all();
				
			//retrieve orders  by  each priceminister account
			foreach($assaPmAccounts as $priceministerAccount ){
				$uid = $priceministerAccount['uid'];
				echo "\n <br>YS1 start to fetch for unfuilled uid=$uid ... ";
				if (empty($uid)){
				//异常情况
					$message = "site id :".$priceministerAccount['site_id'].", uid:0";
					echo "\n ".$message;
					//\Yii::error(['priceminister',__CLASS__,__FUNCTION__,'Background',$message],"edb\global");
					continue;
				}
 
				
				$updateTime = TimeUtil::getNow();
				//update this priceminister account as last order retrieve time
				$priceministerAccount->last_order_retrieve_time = $updateTime;

				//start to get unfulfilled orders
				echo "\n".$updateTime." start to get $uid unfufilled order for ".$priceministerAccount['store_name']." \n"; //ystest
				
				$getOrderCount = 0;

				$PmOrderInterface = new PriceministerOrderInterface();
				$PmOrderInterface->setStoreNamePwd($priceministerAccount['username'], $priceministerAccount['token']);
				$apiReturn = $PmOrderInterface->GetSales('current');
				if(!empty($apiReturn['url']))
					echo "\n proxy url:".$apiReturn['url'];
				
				if (empty($apiReturn['success'])){
					echo "\n fail to connect proxy  :".$apiReturn['message'];
					//\Yii::error(['priceminister',__CLASS__,__FUNCTION__,'Background',"fail to connect proxy  :".$orders['message']],"edb\global");
					$priceministerAccount->order_retrieve_message = PriceministerOrderInterface::apiReturnMessageZhMapping($apiReturn['message']);
					$priceministerAccount->save();
					continue;
				}

				if(stripos($apiReturn['message'], 'Done,got sales for seller id:')===false){//api get order error
					echo "\n".$apiReturn['message'];
					$priceministerAccount->order_retrieve_message = PriceministerOrderInterface::apiReturnMessageZhMapping($apiReturn['message']);
					$priceministerAccount->save();
					continue;
				}
				
				if (isset($apiReturn['orders'])){
					if(!empty($apiReturn['orders'])){
						echo "\n api return  ".count($apiReturn['orders'])." orders;" ;
						//sync priceminister info to priceminister order table
						$rtn = PriceministerOrderHelper::_InsertPriceministerOrder($apiReturn['orders'],$priceministerAccount,$apiReturn['seller_id'],'current');
						if($rtn['success']){//insert to oms done
							$priceministerAccount->initial_fetched_changed_order_since = $updateTime;
							$priceministerAccount->routine_fetched_changed_order_from = $updateTime;
							$priceministerAccount->last_order_success_retrieve_time = $updateTime;
							$priceministerAccount->order_retrieve_message = '';
						}else{//insert to oms failed
							$priceministerAccount->order_retrieve_message = '订单写入OMS系统失败!';
							$priceministerAccount->save();
						}
					}
					else{
						echo "\n api return  null orders;" ;
						$priceministerAccount->initial_fetched_changed_order_since = $updateTime;
						$priceministerAccount->routine_fetched_changed_order_from = $updateTime;
						$priceministerAccount->last_order_success_retrieve_time = $updateTime;
						$priceministerAccount->order_retrieve_message = '';
					}
				}else{
					$priceministerAccount->order_retrieve_message = '成功调用api,但返回orders数据丢失!';
				}
				//end of getting orders from priceminister server
				if (!$priceministerAccount->save()){
					echo "\n failure to save priceminister account info ,error:";
					echo "\n uid:".$priceministerAccount['uid']."error:". print_r($priceministerAccount->getErrors(),true);
				}else{
					echo "\n PriceministerAccount model save !";
				}
			}//end of each priceminister user account
		}
		catch (\Exception $e) {
			echo "\n cronAutoFetchNewAccountOrderList Exception:".$e->getMessage();
			//\Yii::error(['priceminister',__CLASS__,__FUNCTION__,'Background',"uid retrieve order :".$e->getMessage()],"edb\global");
		}
	}
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 后台获取最近订单(new/current)
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param $job_id		job action number
	 +---------------------------------------------------------------------------------------------
	 * @return				order
	 * @description			获取某个时间之后状态变化的订单
	 +---------------------------------------------------------------------------------------------
	 * log		name	date		note
	 * @author	lzhl	2016/3/31	初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	public static function cronAutoFetchRecentOrderList($job_id,$the_day=''){
		echo "\n entry cronAutoFetchRecentOrderList. ";
		try {
			
			$half_hours_ago = date('Y-m-d H:i:s',strtotime('-30 minutes'));
			$twoHoursAgo = date('Y-m-d H:i:s',strtotime('-120 minutes'));
			//update the new accounts first
			$command = Yii::$app->db->createCommand("update saas_priceminister_user set last_order_success_retrieve_time='0000-00-00 00:00:00',last_order_retrieve_time='0000-00-00 00:00:00'  
									where last_order_success_retrieve_time is null or last_order_retrieve_time is null"  );
			$affectRows = $command->execute();
			
			// dzt20191103 fix record
			$command = Yii::$app->db->createCommand("update saas_priceminister_user set sync_status='F'
									where sync_status='R' and last_order_retrieve_time<'$twoHoursAgo'"  );
			$affectRows = $command->execute();
			echo PHP_EOL." fix count:$affectRows... ===".$twoHoursAgo;
			
			$saas_query = SaasPriceministerUser::find()->where("is_active='1' and initial_fetched_changed_order_since is not null 
					and last_order_success_retrieve_time<'$half_hours_ago'")
				// ->andwhere(" uid%3=$job_id ") // TODO 开启PM多进程时候做相应去注释
				;
				
			$currentTime = date("H");
			$account_count = $saas_query->orderBy("last_order_success_retrieve_time asc")->count();
				
			$handled_account_count = 0;
			
			
			
			//retrieve orders  by  each priceminister account 
			do{
				$account_query =  SaasPriceministerUser::find()
					->where("is_active='1' and initial_fetched_changed_order_since is not null and last_order_success_retrieve_time<'$half_hours_ago'")
					// ->andwhere(" uid%3=$job_id ") // TODO 开启PM多进程时候做相应去注释
					->andWhere(" sync_status<>'R' or sync_status is null");
				if( $currentTime>=6 ){
					$account_query->andWhere(['uid'=>$puids_live_recent_3days]);
				}
				
				$priceministerAccount = $account_query->orderBy("last_order_retrieve_time asc")->one();
				$handled_account_count ++;
				if(empty($priceministerAccount))
					continue;
				
				$uid = $priceministerAccount['uid'];
				echo "\n <br>YS1 start to fetch for unfuilled uid=$uid site id :".$priceministerAccount['site_id']."... ";
				if (empty($uid)){
					//异常情况 
					$message = "site id :".$priceministerAccount['site_id']." uid:0";
					echo "\n ".$message;
					//\Yii::error(['priceminister',__CLASS__,__FUNCTION__,'Background',$message],"edb\global");					
					continue;
				}
 

				$updateTime =  TimeUtil::getNow();
				$getOrderCount = 0;
				if (empty($priceministerAccount->last_order_success_retrieve_time) or $priceministerAccount->last_order_success_retrieve_time=='0000-00-00 00:00:00'){
					//如果还没有初始化完毕，就什么都不do
					echo "\n uid=$uid haven't initial_fetched !";
					continue;
				}else{
					
					self::markSaasAccountOrderSynching($priceministerAccount, 'R');
					//update this priceminister account as last order retrieve time
					$priceministerAccount->last_order_retrieve_time = $updateTime;
					//start to get current orders
					echo "\n".TimeUtil::getNow()." start to get uid=$uid's unfufilled order for ".$priceministerAccount['store_name']." \n"; //ystest
					$autoAccept = ConfigHelper::getConfig("PriceministerOrder/AutoAccept",'NO_CACHE');//是否开启了自动接受订单
					if(!empty($autoAccept) && $autoAccept!=='false'){
						//if auto accept orders:
						echo "\n user set auto accept order;";
						$PmOrderInterface = new PriceministerOrderInterface();
						$PmOrderInterface->setStoreNamePwd($priceministerAccount['username'], $priceministerAccount['token']);
						$apiReturn = $PmOrderInterface->GetSales('new');
						if(!empty($apiReturn['url']))
							echo "\n get new sale, proxy url:".$apiReturn['url'];
							
						if (empty($apiReturn['success'])){
							echo "\n fail to connect proxy :".$apiReturn['message'];
							//\Yii::error(['priceminister',__CLASS__,__FUNCTION__,'Background',"fail to connect proxy  :".$orders['message']],"edb\global");
							$priceministerAccount->order_retrieve_message = PriceministerOrderInterface::apiReturnMessageZhMapping($apiReturn['message']);
							$priceministerAccount->save();
							self::markSaasAccountOrderSyncFinished($priceministerAccount, 'F', 0,$priceministerAccount->order_retrieve_message);
							continue;
						}
						
						if(stripos($apiReturn['message'], 'Done,got sales for seller id:')===false){//api get order error
							echo "\n".$apiReturn['message'];
							$priceministerAccount->order_retrieve_message = PriceministerOrderInterface::apiReturnMessageZhMapping($apiReturn['message']);
							$priceministerAccount->save();
							self::markSaasAccountOrderSyncFinished($priceministerAccount, 'F', 0,$priceministerAccount->order_retrieve_message);
							continue;
						}
						
						if (isset($apiReturn['orders'])){
							if(!empty($apiReturn['orders'])){
								echo "\n api return  ".count($apiReturn['orders'])." orders;" ;
								//sync priceminister info to priceminister order table
								$rtn = PriceministerOrderHelper::_autoAcceptOrders($priceministerAccount,$apiReturn['orders']);
								//自动接受订单不记录操作到saas表,接下来的get current将会获取到这些订单
							}
							else{
								echo "\n api return null new orders;" ;
								$priceministerAccount->last_order_success_retrieve_time = $updateTime;
								$priceministerAccount->order_retrieve_message = '';
							}
						}else{
							$priceministerAccount->order_retrieve_message = '成功调用api,但返回orders数据丢失!';
							$priceministerAccount->save();
							self::markSaasAccountOrderSyncFinished($priceministerAccount, 'F', 0,$priceministerAccount->order_retrieve_message);
							continue;
						}
					}
					
					$PmOrderInterface = new PriceministerOrderInterface();
					$PmOrderInterface->setStoreNamePwd($priceministerAccount['username'], $priceministerAccount['token']);
					
					echo "\n try to get current order :";
					$apiReturn = $PmOrderInterface->GetSales('current');
					if(!empty($apiReturn['url']))
						echo "\n proxy url:".$apiReturn['url'];
					if (empty($apiReturn['success'])){
						echo "\n fail to connect proxy  :".$apiReturn['message'];
						//\Yii::error(['priceminister',__CLASS__,__FUNCTION__,'Background',"fail to connect proxy  :".$orders['message']],"edb\global");
						$priceministerAccount->order_retrieve_message = PriceministerOrderInterface::apiReturnMessageZhMapping($apiReturn['message']);
						$priceministerAccount->save();
						self::markSaasAccountOrderSyncFinished($priceministerAccount, 'F', 0,$priceministerAccount->order_retrieve_message);
					}
					
					if(stripos($apiReturn['message'], 'Done,got sales for seller id:')===false){//api get order error
						echo "\n".$apiReturn['message'];
						$priceministerAccount->order_retrieve_message = PriceministerOrderInterface::apiReturnMessageZhMapping($apiReturn['message']);
						$priceministerAccount->save();
						self::markSaasAccountOrderSyncFinished($priceministerAccount, 'F', 0,$priceministerAccount->order_retrieve_message);
					}
					
					if (isset($apiReturn['orders'])){
						if(!empty($apiReturn['orders'])){
							echo "\n api return  ".count($apiReturn['orders'])." orders;" ;
							//sync priceminister info to priceminister order table
							$rtn = PriceministerOrderHelper::_InsertPriceministerOrder($apiReturn['orders'],$priceministerAccount,$apiReturn['seller_id'],'current');
							if($rtn['success']){//insert to oms done
								$priceministerAccount->last_order_success_retrieve_time = $updateTime;
								$priceministerAccount->order_retrieve_message = '';
								$getOrderCount += count($apiReturn['orders']);
							}else{//insert to oms failed
								$priceministerAccount->order_retrieve_message = '订单写入OMS系统失败!';
								$priceministerAccount->save();
								self::markSaasAccountOrderSyncFinished($priceministerAccount, 'F', 0,$priceministerAccount->order_retrieve_message);
							}
						}
						else{
							echo "\n api return  null orders;" ;
							$priceministerAccount->last_order_success_retrieve_time = $updateTime;
							$priceministerAccount->order_retrieve_message = '';
						}
					}else{
						$priceministerAccount->order_retrieve_message = '成功调用api,但返回orders数据丢失!';
						self::markSaasAccountOrderSyncFinished($priceministerAccount, 'F', 0,$priceministerAccount->order_retrieve_message);
					}
					//end of getting current orders from priceminister server
					if (!$priceministerAccount->save()){
						echo "\n failure to save priceminister account info after get current orders , error:";
						echo "\n uid:".$priceministerAccount['uid']."error:". print_r($priceministerAccount->getErrors(),true);
					}else{
						echo "\n PriceministerAccount model saved after get current orders !";
					}
					
					//未设置autoAccept时，需要将new订单也写入oms
					//由于存在讨价单，所以无论用户书否设置了自动接受订单， 都要将new写入
					//if(empty($autoAccept) || $autoAccept=='false'){
						//if get current orders and instered into oms,to get new sales:
						$apiReturn = $PmOrderInterface->GetSales('new');
						if(!empty($apiReturn['url']))
							echo "\n proxy url:".$apiReturn['url'];
						
						if (empty($apiReturn['success'])){
							echo "\n fail to connect proxy  :".$apiReturn['message'];
							//\Yii::error(['priceminister',__CLASS__,__FUNCTION__,'Background',"fail to connect proxy  :".$orders['message']],"edb\global");
							$priceministerAccount->order_retrieve_message = PriceministerOrderInterface::apiReturnMessageZhMapping($apiReturn['message']);
							$priceministerAccount->save();
							self::markSaasAccountOrderSyncFinished($priceministerAccount, 'F', 0,$priceministerAccount->order_retrieve_message);
							continue;
						}
							
						if(stripos($apiReturn['message'], 'Done,got sales for seller id:')===false){//api get order error
							echo "\n".$apiReturn['message'];
							$priceministerAccount->order_retrieve_message = PriceministerOrderInterface::apiReturnMessageZhMapping($apiReturn['message']);
							$priceministerAccount->save();
							self::markSaasAccountOrderSyncFinished($priceministerAccount, 'F', 0,$priceministerAccount->order_retrieve_message);
							continue;
						}
							
						if (isset($apiReturn['orders'])){
							if(!empty($apiReturn['orders'])){
								echo "\n api return  ".count($apiReturn['orders'])." orders;" ;
								//sync priceminister info to priceminister order table
								$rtn = PriceministerOrderHelper::_InsertPriceministerOrder($apiReturn['orders'],$priceministerAccount,$apiReturn['seller_id'],'new');
								if($rtn['success']){//insert to oms done
									$priceministerAccount->last_order_success_retrieve_time = $updateTime;
									$priceministerAccount->order_retrieve_message = '';
									$getOrderCount += count($apiReturn['orders']);
								}else{//insert to oms failed
									$priceministerAccount->order_retrieve_message = '订单写入OMS系统失败!';
									$priceministerAccount->save();
									self::markSaasAccountOrderSyncFinished($priceministerAccount, 'F', 0,$priceministerAccount->order_retrieve_message);
									continue;
								}
							}
							else{
								echo "\n api return  null orders;" ;
								$priceministerAccount->last_order_success_retrieve_time = $updateTime;
								$priceministerAccount->order_retrieve_message = '';
							}
						}else{
							$priceministerAccount->order_retrieve_message = '成功调用api,但返回orders数据丢失!';
							$priceministerAccount->save();
							self::markSaasAccountOrderSyncFinished($priceministerAccount, 'F', 0,$priceministerAccount->order_retrieve_message);
							continue;
						}
					//}
					//end of getting new orders from priceminister server
					if (!$priceministerAccount->save()){
						echo "\n failure to save priceminister account info after get new orders , error:";
						echo "\n uid:".$priceministerAccount['uid']."error:". print_r($priceministerAccount->getErrors(),true);
						continue;
					}else{
						echo "\n PriceministerAccount model saved after get new orders !";
					}
					//end of get new orders 
					self::markSaasAccountOrderSyncFinished($priceministerAccount, 'C', $getOrderCount,'');
				}
			}while ($handled_account_count < $account_count);//end of each priceminister user account
			return array('success'=>true,'message'=>''); 
		} catch (\Exception $e) {
			echo "\n cronAutoFetchRecentOrderList Exception:".$e->getMessage();
			return array('success'=>false,'message'=>"cronAutoFetchRecentOrderList Exception:".$e->getMessage());
			//\Yii::error(['priceminister',__CLASS__,__FUNCTION__,'Background',"uid retrieve order :".$e->getMessage()],"edb\global");
		}
			
	}//end of cronAutoFetchUnFulfilledOrderList
	
	
	private static function _fetchOrder($priceministerAccount){
		$rtn = ['success'=>true,'message'=>''];
		$uid = $priceministerAccount['uid'];
		echo "\n <br>YS1 start to fetch for unfuilled uid=$uid ... ";
		
		//异常情况
		if (empty($uid)){
			$message = "site id :".$priceministerAccount['site_id']." uid:0";
			return $rtn = ['success'=>false,'message'=>$message];
		}
 
		
		$updateTime =  TimeUtil::getNow();
		$getOrderCount = 0;
		if (empty($priceministerAccount->last_order_success_retrieve_time) or $priceministerAccount->last_order_success_retrieve_time=='0000-00-00 00:00:00'){
			//如果还没有初始化完毕，就什么都不do
			$message = "uid=$uid haven't initial_fetched !";
			return $rtn = ['success'=>false,'message'=>$message];
		}else{
			//锁定账号同步中状态
			self::markSaasAccountOrderSynching($priceministerAccount, 'R');
			//update this priceminister account as last order retrieve time
			$priceministerAccount->last_order_retrieve_time = $updateTime;
			//start to get current orders
			echo "\n".TimeUtil::getNow()." start to get $uid unfufilled order for ".$priceministerAccount['store_name']." \n"; //ystest
			
			//是否开启了自动接受订单
			$autoAccept = ConfigHelper::getConfig("PriceministerOrder/AutoAccept",'NO_CACHE');
			if(!empty($autoAccept) && $autoAccept!=='false'){
				//if auto accept orders:
				echo "\n user set auto accept order;";
				$PmOrderInterface = new PriceministerOrderInterface();
				$PmOrderInterface->setStoreNamePwd($priceministerAccount['username'], $priceministerAccount['token']);
				$apiReturn = $PmOrderInterface->GetSales('new');
				if(!empty($apiReturn['url']))
					echo "\n get new sale, proxy url:".$apiReturn['url'];
											
				if (empty($apiReturn['success'])){
					$message = "fail to connect proxy :".$apiReturn['message'];
					//\Yii::error(['priceminister',__CLASS__,__FUNCTION__,'Background',"fail to connect proxy  :".$orders['message']],"edb\global");
					$priceministerAccount->order_retrieve_message = PriceministerOrderInterface::apiReturnMessageZhMapping($apiReturn['message']);
					$priceministerAccount->save();
					self::markSaasAccountOrderSyncFinished($priceministerAccount, 'F', 0,$priceministerAccount->order_retrieve_message);
					return $rtn = ['success'=>false,'message'=>$message];
				}
		
				if(stripos($apiReturn['message'], 'Done,got sales for seller id:')===false){//api get order error
					$message =  "api get order error:".$apiReturn['message'];
					$priceministerAccount->order_retrieve_message = PriceministerOrderInterface::apiReturnMessageZhMapping($apiReturn['message']);
					$priceministerAccount->save();
					self::markSaasAccountOrderSyncFinished($priceministerAccount, 'F', 0,$priceministerAccount->order_retrieve_message);
					return $rtn = ['success'=>false,'message'=>$message];
				}
		
				if (isset($apiReturn['orders'])){
					if(!empty($apiReturn['orders'])){
						echo "\n api return  ".count($apiReturn['orders'])." orders;" ;
						//sync priceminister info to priceminister order table
						$rtn = PriceministerOrderHelper::_autoAcceptOrders($priceministerAccount,$apiReturn['orders']);
						//自动接受订单不记录操作到saas表,接下来的get current将会获取到这些订单
					}
					else{
						echo "\n api return null new orders;" ;
						$priceministerAccount->last_order_success_retrieve_time = $updateTime;
						$priceministerAccount->order_retrieve_message = '';
					}
				}else{
					$priceministerAccount->order_retrieve_message = '成功调用api,但返回orders数据丢失!';
					$priceministerAccount->save();
					self::markSaasAccountOrderSyncFinished($priceministerAccount, 'F', 0,$priceministerAccount->order_retrieve_message);
					return $rtn = ['success'=>false,'message'=>$priceministerAccount->order_retrieve_message];
				}
			}
			
			$PmOrderInterface = new PriceministerOrderInterface();
			$PmOrderInterface->setStoreNamePwd($priceministerAccount['username'], $priceministerAccount['token']);
			
			//start to get current
			$apiReturn = $PmOrderInterface->GetSales('current','');
			if(!empty($apiReturn['url']))
				echo "\n proxy url:".$apiReturn['url'];
			if (empty($apiReturn['success'])){
				$message = "fail to connect proxy  :".$apiReturn['message'];
				//\Yii::error(['priceminister',__CLASS__,__FUNCTION__,'Background',"fail to connect proxy  :".$orders['message']],"edb\global");
				$priceministerAccount->order_retrieve_message = PriceministerOrderInterface::apiReturnMessageZhMapping($apiReturn['message']);
				$priceministerAccount->save();
				self::markSaasAccountOrderSyncFinished($priceministerAccount, 'F', 0,$priceministerAccount->order_retrieve_message);
				return $rtn = ['success'=>false,'message'=>$message];
			}
			
			if(stripos($apiReturn['message'], 'Done,got sales for seller id:')===false){//api get order error
				$message = "api get order error:".$apiReturn['message'];
				$priceministerAccount->order_retrieve_message = PriceministerOrderInterface::apiReturnMessageZhMapping($apiReturn['message']);
				$priceministerAccount->save();
				self::markSaasAccountOrderSyncFinished($priceministerAccount, 'F', 0,$priceministerAccount->order_retrieve_message);
				return $rtn = ['success'=>false,'message'=>$message];
			}
											
			if (isset($apiReturn['orders'])){
				if(!empty($apiReturn['orders'])){
					echo "\n api return  ".count($apiReturn['orders'])." orders;" ;
					//sync priceminister info to priceminister order table
					$rtn = PriceministerOrderHelper::_InsertPriceministerOrder($apiReturn['orders'],$priceministerAccount,$apiReturn['seller_id'],'current');
					if($rtn['success']){//insert to oms done
						$priceministerAccount->last_order_success_retrieve_time = $updateTime;
						$priceministerAccount->order_retrieve_message = '';
						$getOrderCount += count($apiReturn['orders']);
					}else{//insert to oms failed
						$priceministerAccount->order_retrieve_message = '订单写入OMS系统失败!';
						$priceministerAccount->save();
						self::markSaasAccountOrderSyncFinished($priceministerAccount, 'F', 0,$priceministerAccount->order_retrieve_message);
						return $rtn = ['success'=>false,'message'=>$priceministerAccount->order_retrieve_message];
					}
				}
				else{
					echo "\n api return  null orders;" ;
					$priceministerAccount->last_order_success_retrieve_time = $updateTime;
					$priceministerAccount->order_retrieve_message = '';
				}
			}else{
				$priceministerAccount->order_retrieve_message = '成功调用api,但返回orders数据丢失!';
				self::markSaasAccountOrderSyncFinished($priceministerAccount, 'F', 0,$priceministerAccount->order_retrieve_message);
				return $rtn = ['success'=>false,'message'=>$priceministerAccount->order_retrieve_message];
			}
			//end of getting current orders from priceminister server
			
			if (!$priceministerAccount->save()){
				echo "\n failure to save priceminister account info after get current orders , error:";
				echo "\n uid:".$priceministerAccount['uid']."error:". print_r($priceministerAccount->getErrors(),true);
				return $rtn = ['success'=>false,'message'=>'failure to save priceminister account info after get current orders'];
			}else{
				echo "\n PriceministerAccount model saved after get current orders !";
			}
			
			//未设置autoAccept时，需要将new订单也写入oms
			if(empty($autoAccept) || $autoAccept=='false'){
				//if get current orders and instered into oms,to get new sales:
				$apiReturn = $PmOrderInterface->GetSales('new');
				if(!empty($apiReturn['url']))
					echo "\n proxy url:".$apiReturn['url'];
		
				if (empty($apiReturn['success'])){
					$message  = "fail to connect proxy  :".$apiReturn['message'];
					//\Yii::error(['priceminister',__CLASS__,__FUNCTION__,'Background',"fail to connect proxy  :".$orders['message']],"edb\global");
					$priceministerAccount->order_retrieve_message = PriceministerOrderInterface::apiReturnMessageZhMapping($apiReturn['message']);
					$priceministerAccount->save();
					self::markSaasAccountOrderSyncFinished($priceministerAccount, 'F', 0,$priceministerAccount->order_retrieve_message);
					return $rtn = ['success'=>false,'message'=>$message];
				}
										
				if(stripos($apiReturn['message'], 'Done,got sales for seller id:')===false){//api get order error
					$message = "api get order error:".$apiReturn['message'];
					$priceministerAccount->order_retrieve_message = PriceministerOrderInterface::apiReturnMessageZhMapping($apiReturn['message']);
					$priceministerAccount->save();
					self::markSaasAccountOrderSyncFinished($priceministerAccount, 'F', 0,$priceministerAccount->order_retrieve_message);
					return $rtn = ['success'=>false,'message'=>$message];
				}
										
				if (isset($apiReturn['orders'])){
					if(!empty($apiReturn['orders'])){
						echo "\n api return  ".count($apiReturn['orders'])." orders;" ;
						//sync priceminister info to priceminister order table
						$rtn = PriceministerOrderHelper::_InsertPriceministerOrder($apiReturn['orders'],$priceministerAccount,$apiReturn['seller_id'],'new');
						if($rtn['success']){//insert to oms done
							$priceministerAccount->last_order_success_retrieve_time = $updateTime;
							$priceministerAccount->order_retrieve_message = '';
							$getOrderCount += count($apiReturn['orders']);
						}else{//insert to oms failed
							$priceministerAccount->order_retrieve_message = '订单写入OMS系统失败!';
							$priceministerAccount->save();
							self::markSaasAccountOrderSyncFinished($priceministerAccount, 'F', 0,$priceministerAccount->order_retrieve_message);
							return $rtn = ['success'=>false,'message'=>$priceministerAccount->order_retrieve_message];
						}
					}
					else{
						echo "\n api return  null orders;" ;
						$priceministerAccount->last_order_success_retrieve_time = $updateTime;
						$priceministerAccount->order_retrieve_message = '';
					}
				}else{
					$priceministerAccount->order_retrieve_message = '成功调用api,但返回orders数据丢失!';
					$priceministerAccount->save();
					self::markSaasAccountOrderSyncFinished($priceministerAccount, 'F', 0,$priceministerAccount->order_retrieve_message);
					return $rtn = ['success'=>false,'message'=>$priceministerAccount->order_retrieve_message];
				}
			}
			//end of getting new orders from priceminister server
			
			if (!$priceministerAccount->save()){
				echo "\n failure to save priceminister account info after get new orders , error:";
				echo "\n uid:".$priceministerAccount['uid']."error:". print_r($priceministerAccount->getErrors(),true);
				return $rtn = ['success'=>false,'message'=>'failure to save priceminister account info after get orders'];
			}else{
				echo "\n PriceministerAccount model saved after get new orders !";
			}
			//end of get new orders
			self::markSaasAccountOrderSyncFinished($priceministerAccount, 'C', $getOrderCount,'');
			return $rtn;
		}
	}
	
	public static function cronManualFetchOrder(){
		global $CACHE;
		if (empty($CACHE['JOBID']))
			$CACHE['JOBID'] = "MS".rand(0,99999);
		
		//echo '\n background service enter, job id:'.$CACHE['JOBID'];
		
		//Step 0, 检查当前本job版本，如果和自己的版本不一样，就自动exit
		
		$JOBID=$CACHE['JOBID'];
		$current_time=explode(" ",microtime());	$start1_time=round($current_time[0]*1000+$current_time[1]*1000);
		
		$currentManualQueueVersion = ConfigHelper::getGlobalConfig("PriceministerOrder/manualQueueVersion",'NO_CACHE');
		if (empty($currentManualQueueVersion))
			$currentManualQueueVersion = 0;
		
		//如果自己还没有定义，去使用global config来初始化自己
		if (empty(self::$manualQueueVersion))
			self::$manualQueueVersion = $currentManualQueueVersion;
			
		//如果自己的version不同于全局要求的version，自己退出，从新起job来使用新代码
		if (self::$manualQueueVersion <> $currentManualQueueVersion){
			TrackingAgentHelper::extCallSum("",0,true);
			exit("Version new $currentManualQueueVersion , this job ver ".self::$manualQueueVersion." exits for using new version $currentManualQueueVersion.");
		}
		
		$priceministerAccount = SaasPriceministerUser::find()
			->where("is_active='1' and initial_fetched_changed_order_since is not null ")
			->andWhere(" sync_status='R' and sync_type='M' ")
			->orderBy("last_order_success_retrieve_time asc")->one();
		if(empty($priceministerAccount)){//没有需要处理的账号
			echo "\n none account to handel;";
			return 'n/a';
		}
		$rtn = self::_fetchOrder($priceministerAccount);
		if(!$rtn['success'])
			echo "\n ".$rtn['message'];
		else 
			echo "\n uid:".$priceministerAccount->uid." store:".$priceministerAccount->username."manual fetch done!";
		return $rtn;
	}
	/**
	 +---------------------------------------------------------------------------------------------
	 * priceminister api 获取的订单数组 保存到订单模块中
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param $orders				priceminister api 返回的结果
	 * @param $priceministerAccount		priceminister user model 
	 * @param $the_day				job启动当前日期
	 +---------------------------------------------------------------------------------------------
	 * @return				array
	 * @description			priceminister order  调用eagle order 接口 的事前准备当中还hardcode了一默认值
	 +---------------------------------------------------------------------------------------------
	 * log			name	date			note
	 * @author		lzhl	2016/3/31		初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	public static function _InsertPriceministerOrder($orders , $priceministerAccount , $seller_id='', $orderType='current',$the_day=''){
		//echo "\n YS0.0 Start to insert order "; //ystest
		try {
			/*
			//删除v2表里面不存在的原始订单
			$command = Yii::$app->subdb->createCommand("delete  FROM `priceminister_order` WHERE `purchaseid` not in (select order_source_order_id from od_order_v2 where order_source='priceminister') ");
			$command->execute();
			*/
			//print_r($orders);

			$oms_insert_success=0;
			$oms_update_success=0;
			$oms_insert_failed=0;
			$oms_update_failed=0;
			
			$err_type=[
				'1'=>['times'=>0,'last_msg'=>'','site_id'=>'','time'=>''],//写入OMS失败
			];
			
			$oms_insert_failed_happend_site ='';
			$oms_update_failed_happend_site ='';
			
			$ImportOrderArr = array();
			$tempPriceministerOrderModel = new PriceministerOrder();
			$tempPriceministerOrderDetailModel = new PriceministerOrderDetail();
			$PriceministerOrderModelAttr = $tempPriceministerOrderModel->getAttributes();
			$PriceministerOrderDetailModelAttr = $tempPriceministerOrderDetailModel->getAttributes();
			//print_r($PriceministerOrderModelAttr);
			$rtn['message'] ='';
			$rtn['success'] = true;
			
			//单订单判断
			/*
			if(isset($orders['OrderNumber'])){
				$o=$orders;
				$orders=[];
				$orders[]=$o;
			}
			*/
			//记录保存失败的订单号
			$insertErrOrder['omsTbale']=[];
			$insertErrOrder['uid']=$priceministerAccount['uid'];
			$insertErrOrder['store_name']=$priceministerAccount['store_name'];
			
			//同步商品信息原始数组
			$syncProdInfoData=[];
			
			//对这个子库下面的进行所有 平台原始订单，找出来和这次api返回相关的记录，缓存起来，这样1次IO就可以判断n个记录是否已经存在了
			$inOrderTable_key = array();
			$ids = array();
			foreach($orders as $anOrder){//$anOrder is an object
				$ids[] = (string)$anOrder->purchaseid ; 
			}
			
			$inOrderTable_models = OdOrder::find()->where(['order_source_order_id'=>$ids,'selleruserid'=>$priceministerAccount['username'],'order_source'=>'priceminister'])->all();
			
			foreach ($inOrderTable_models as $aRec){
				$inOrderTable_key[(string)$aRec->order_source_order_id ] = $aRec;
			}
			
			foreach($orders as $anOrder){//$anOrder is an object
				//echo "\n YS0.1 Start to insert order "; //ystest	
				$OrderParms = array();
				//不要对每一个订单进行数据库IO，看内存数据就ok
				//$orderModel = PriceministerOrder::findOne((string)$anOrder->purchaseid);
				$orderModel = isset($inOrderTable_key[(string)$anOrder->purchaseid ])? $inOrderTable_key[(string)$anOrder->purchaseid ] : null;
				$newCreateOrder = false;
			 
				if (empty($orderModel)){
					//new order 
					$orderModel = new OdOrder();
					$newCreateOrder = true;
				}
				
				//set order info 
				$orderInfo = array();
				$orderDetailInfo = array();
				$subtotal = 0;
				$grand_total = 0;
				//echo "\n YS0.2   "; //ystest
				if(!empty($anOrder->purchaseid))
					$orderInfo['purchaseid'] = (string)$anOrder->purchaseid;
				else{
					echo "\n error: empty orderObj->purchaseid,skip;";
					continue;
				}
				if(empty($anOrder->items->item)){
					echo "\n error: empty orderObj->items,skip;";
					continue;
				}
				$orderInfo['order_status'] = $orderType;
				$orderInfo['purchasedate'] = empty($anOrder->purchasedate)?null:(string)$anOrder->purchasedate.'';
				
				$orderInfo['shippingtype'] = (!empty($anOrder->deliveryinformation->shippingtype) /*&& is_string($anOrder->deliveryinformation->shippingtype)*/)?
					(string)$anOrder->deliveryinformation->shippingtype : null;
				$orderInfo['isfullrsl'] = (!empty($anOrder->deliveryinformation->isfullrsl) /*&& is_string($anOrder->deliveryinformation->isfullrsl)*/)?
					(string)$anOrder->deliveryinformation->isfullrsl:null;
				$orderInfo['purchasebuyerlogin'] = (!empty($anOrder->deliveryinformation->purchasebuyerlogin) /*&& is_string($anOrder->deliveryinformation->purchasebuyerlogin)*/)?
					(string)$anOrder->deliveryinformation->purchasebuyerlogin:null;
				$orderInfo['purchasebuyeremail'] = (!empty($anOrder->deliveryinformation->purchasebuyeremail) /*&& is_string($anOrder->deliveryinformation->purchasebuyeremail)*/)?
					(string)$anOrder->deliveryinformation->purchasebuyeremail:null;
				
				$orderInfo['deliveryaddress_civility'] = (!empty($anOrder->deliveryinformation->deliveryaddress->civility) /*&& is_string($anOrder->deliveryinformation->deliveryaddress->civility)*/)?
					(string)$anOrder->deliveryinformation->deliveryaddress->civility:null;
				$orderInfo['deliveryaddress_lastname'] = (!empty($anOrder->deliveryinformation->deliveryaddress->lastname) /*&& is_string($anOrder->deliveryinformation->deliveryaddress->lastname)*/)?
					(string)$anOrder->deliveryinformation->deliveryaddress->lastname:null;
				$orderInfo['deliveryaddress_firstname'] = (!empty($anOrder->deliveryinformation->deliveryaddress->firstname) /*&& is_string($anOrder->deliveryinformation->deliveryaddress->firstname)*/)?
					(string)$anOrder->deliveryinformation->deliveryaddress->firstname:null;
				$orderInfo['deliveryaddress_address1'] = (!empty($anOrder->deliveryinformation->deliveryaddress->address1) /*&& is_string($anOrder->deliveryinformation->deliveryaddress->address1)*/)?
					(string)$anOrder->deliveryinformation->deliveryaddress->address1:null;
				$orderInfo['deliveryaddress_address2'] = (!empty($anOrder->deliveryinformation->deliveryaddress->address2) /*&& is_string($anOrder->deliveryinformation->deliveryaddress->address2)*/)?
					(string)$anOrder->deliveryinformation->deliveryaddress->address2:null;
				$orderInfo['deliveryaddress_zipcode'] = (!empty($anOrder->deliveryinformation->deliveryaddress->zipcode) /*&& is_string($anOrder->deliveryinformation->deliveryaddress->zipcode)*/)?
					(string)$anOrder->deliveryinformation->deliveryaddress->zipcode:null;
				$orderInfo['deliveryaddress_city'] = empty($anOrder->deliveryinformation->deliveryaddress->city)?null:(string)$anOrder->deliveryinformation->deliveryaddress->city.'';
				$orderInfo['deliveryaddress_country'] = (!empty($anOrder->deliveryinformation->deliveryaddress->country) /*&& is_string($anOrder->deliveryinformation->deliveryaddress->country)*/)?
					(string)$anOrder->deliveryinformation->deliveryaddress->country:null;
				$orderInfo['deliveryaddress_countryalpha2'] = (!empty($anOrder->deliveryinformation->deliveryaddress->countryalpha2) /*&& is_string($anOrder->deliveryinformation->deliveryaddress->countryalpha2)*/)?
					(string)$anOrder->deliveryinformation->deliveryaddress->countryalpha2:null;
				$orderInfo['deliveryaddress_phonenumber1'] = (!empty($anOrder->deliveryinformation->deliveryaddress->phonenumber1) /*&& is_string($anOrder->deliveryinformation->deliveryaddress->phonenumber1)*/)?
					(string)$anOrder->deliveryinformation->deliveryaddress->phonenumber1:null;
				$orderInfo['deliveryaddress_phonenumber2'] = (!empty($anOrder->deliveryinformation->deliveryaddress->phonenumber2) /*&& is_string($anOrder->deliveryinformation->deliveryaddress->phonenumber2)*/)?
					(string)$anOrder->deliveryinformation->deliveryaddress->phonenumber2:null;
				
				$orderInfo['seller_login'] = $priceministerAccount['username'];
				$orderInfo['seller_id'] = $seller_id;
				$orderInfo['create'] = TimeUtil::getNow();
				$orderInfo['update'] = TimeUtil::getNow();
				$items=[];
				if(empty($anOrder->items->item))
					$items = [];
				elseif(is_object($anOrder->items->item)){
					foreach ($anOrder->items->item as $i)
						$items[] = $i;
				}
				else{
					$items[] = $anOrder->items->item;	
				}
				//print_r($items);
				$currencies = [];
				$order_has_claim = false;
				$order_has_cancelled = false;
				$order_has_refused = false;
				foreach ($items as $index=>$item){//$item is an object
					$orderDetailInfo[$index]['sku'] = (!empty($item->sku) /*&& is_string($item->sku)*/)?(string)$item->sku :null;
					$orderDetailInfo[$index]['advertid'] = (!empty($item->advertid) /*&& is_string($item->advertid)*/)?(string)$item->advertid :null;
					$orderDetailInfo[$index]['itemid'] = (!empty($item->itemid) /*&& is_string($item->itemid)*/)?(string)$item->itemid :null;
					$orderDetailInfo[$index]['headline'] = (!empty($item->headline) /*&& is_string($item->headline)*/)?(string)$item->headline :null;
					$orderDetailInfo[$index]['itemstatus'] = (!empty($item->itemstatus) /*&& is_string($item->itemstatus)*/)?(string)$item->itemstatus :null;
					if(strtoupper($orderDetailInfo[$index]['itemstatus'])=='REFUSED'){
						$order_has_refused = true;
					}
					if(strtoupper($orderDetailInfo[$index]['itemstatus'])=='CANCELLED'){
						$order_has_cancelled = true;
					}
					if(strtoupper($orderDetailInfo[$index]['itemstatus'])=='ON_HOLD' || strtolower($orderDetailInfo[$index]['itemstatus'])=='PENDING_CLAIM'){
						$order_has_claim = true;
					}
					$orderDetailInfo[$index]['ispreorder'] = (!empty($item->ispreorder) /*&& is_string($item->ispreorder)*/)?(string)$item->ispreorder :null;
					$orderDetailInfo[$index]['isnego'] = (!empty($item->isnego) /*&& is_string($item->isnego)*/)?(string)$item->isnego :null;
					$orderDetailInfo[$index]['negotiationcomment'] = (!empty($item->negotiationcomment) /*&& is_string($item->negotiationcomment)*/)?(string)$item->negotiationcomment :null;
					$orderDetailInfo[$index]['isrsl'] = (!empty($item->isrsl) /*&& is_string($item->isrsl)*/)?(string)$item->isrsl :null;
					$orderDetailInfo[$index]['isbn'] = (!empty($item->isbn) /*&& is_string($item->isbn)*/)?(string)$item->isbn :null;
					$ean = (!empty($item->ean) /*&& is_string($item->ean)*/)?(string)$item->ean :null;
					if(!empty($ean)){
						$ean_arr = explode(';',$ean);//pm返回的ean为多个，现在不用ean做product info抓取的话，只留下一个
						$ean = trim($ean_arr[0]);
					}
					$orderDetailInfo[$index]['ean'] = $ean;
					$orderDetailInfo[$index]['paymentstatus'] = (!empty($item->paymentstatus) /*&& is_string($item->paymentstatus)*/)?(string)$item->paymentstatus:null;
					$orderDetailInfo[$index]['sellerscore'] = (!empty($item->sellerscore) /*&& is_string($item->sellerscore)*/)?(string)$item->sellerscore :null;

					$orderDetailInfo[$index]['advertprice_amount'] = (!empty($item->advertpricelisted->amount) /*&& is_string($item->advertpricelisted->amount)*/)?
						(string)$item->advertpricelisted->amount : null;
					$orderDetailInfo[$index]['advertprice_currency'] = (!empty($item->advertpricelisted->currency) /*&& is_string($item->advertpricelisted->currency)*/)?
						(string)$item->advertpricelisted->currency : null;
					
					$orderDetailInfo[$index]['price_amount'] = (!empty($item->price->amount) /*&& is_string($item->price->amount)*/)?
						(string)$item->price->amount : null;
					$subtotal += $orderDetailInfo[$index]['price_amount'];
					$grand_total += $orderDetailInfo[$index]['price_amount'];
					$orderDetailInfo[$index]['price_currency'] = (!empty($item->price->currency) /*&& is_string($item->price->currency)*/)?
						(string)$item->price->currency : null;
					
					if(!empty($orderDetailInfo[$index]['price_currency']) && !in_array($orderDetailInfo[$index]['price_currency'], $currencies))
						$currencies[] = $orderDetailInfo[$index]['price_currency'];
				}
				
				if($order_has_claim){
					$orderInfo['order_status'] = 'claim';
				}
				if($order_has_cancelled){
					$orderInfo['order_status'] = 'cancelled';
				}
				if($order_has_refused){
					$orderInfo['order_status'] = 'refused';
				}
				
				$orderInfo['subtotal'] = $subtotal;
				$orderInfo['grand_total'] = $grand_total;
				if(count($currencies)==1)
					$orderInfo['currency'] = $currencies[0];
				$addi_info = [];
				if($orderType=='new'){
					$addi_info['isNewSale']=true;
					$addi_info['userOperated']=false;
				}else{
					$addi_info['isNewSale']=false;
				}
				/*FBC判断暂时不开启
				$addinfo['FBC'] = false;
				if(isset($anOrder['IsCLogistiqueOrder']) && $anOrder['IsCLogistiqueOrder']!=='false')
					$addinfo['FBC'] = true;
				*/
				//echo "\n YS0.3   "; //ystest
				
				/*弃用原始表
				if (!empty($orderInfo)){
					$orderModel->setAttributes($orderInfo);
					echo " \n YS1 try to inset pm order for ".$orderModel->purchaseid;
					
					if (  $orderModel->save() ){
						echo "\n save priceminister order success!";
						if($newCreateOrder)
							$src_insert_success ++;
						else
							$src_update_success ++;
					}else{
						echo "\n failure to save priceminister order,uid:".$priceministerAccount['uid']."error:".print_r($orderModel->getErrors(),true);
						$rtn['message'].=(empty($orderModel->purchaseid)?'':$orderModel->purchaseid)."原始订单保存失败,";
						$rtn['success']=false;
						$insertErrOrder['srcTbale'][]=empty($orderModel->purchaseid)?'':$orderModel->purchaseid;
						if($newCreateOrder){
							$src_insert_failed ++;
							$src_insert_failed_happend_site = $priceministerAccount['site_id'];
							$err_type['1']['times'] +=1;
							$err_type['1']['site_id']=$priceministerAccount['site_id'];
							$err_type['1']['last_msg']="failure to insert priceminister order,site_id:".$priceministerAccount['site_id']."error:".print_r($orderModel->getErrors(),true);
						}
						else{
							$src_update_failed ++;
							$src_update_failed_happend_site =  $priceministerAccount['site_id'];
							$err_type['4']['times'] +=1;
							$err_type['4']['site_id']=$priceministerAccount['site_id'];
							$err_type['4']['last_msg']="failure to update priceminister order,site_id:".$priceministerAccount['site_id']."error:".print_r($orderModel->getErrors(),true);
						}
						continue;
						//\Yii::error(['priceminister',__CLASS__,__FUNCTION__,'Background',"failure to save priceminister order,uid:".$priceministerAccount['uid']."error:". print_r($orderModel->getErrors(),true)],"edb\global");
					}
				}else{
					echo 'failure to save priceminister order,orderInfo lost!';
					$rtn['message'].="原始订单信息缺失";
					$rtn['success']=false;
					if($newCreateOrder){
						$src_insert_failed++;
						$src_insert_failed_happend_site=$priceministerAccount['site_id'];
						$err_type['1']['times'] +=1;
						$err_type['1']['site_id']=$priceministerAccount['site_id'];
						$err_type['1']['last_msg']='failure to insert priceminister order,orderInfo lost!';
					}
					else{
						$src_update_failed ++;
						$src_update_failed_happend_site =  $priceministerAccount['site_id'];
						$err_type['4']['times'] +=1;
						$err_type['4']['site_id']=$priceministerAccount['site_id'];
						$err_type['4']['last_msg']='failure to update priceminister order,orderInfo lost!';
					}
					
					continue;
				}
				*/
			 	//echo "\n YS0.4   "; //ystest
				//save order detail 
				
				/*弃用原始item表
				if (!empty($orderDetailInfo)){
					//save order details's prod to offer list table if details have new prod that table hadn't,
					
					if ($newCreateOrder){
						foreach ($orderDetailInfo as $aDelail){
							$aDelail['purchaseid'] = (string)$anOrder->purchaseid;
							$orderDetails = new PriceministerOrderDetail();
							$orderDetails->setAttributes ($aDelail);
							if (!$orderDetails->save()){
								echo "\n failure to save priceminister order details ,uid:".$priceministerAccount['uid']."error:". print_r($orderDetails->getErrors(),true);
								$rtn['message'].=(string)$anOrder->purchaseid."保存原始订单商品失败;";
								$rtn['success']=false;
								$insertErrOrder['srcTbale'][]=empty($anOrder->purchaseid)?'':(string)$anOrder->purchaseid;
								
								$src_detail_insert_failed ++;
								$src_detail_insert_failed_happend_site= $priceministerAccount['site_id'];
								$err_type['2']['times'] +=1;
								$err_type['2']['site_id']=$priceministerAccount['site_id'];
								$err_type['2']['last_msg']='failure to insert priceminister order detail ,insert to db failed!';
								
								continue;
							}else{
								echo "\n save priceminister order detail success!";
								$src_detail_insert_success ++;
								$syncProdInfoData[] = $aDelail;
								if($orderType=='current' || $orderType=='new'){
									$syncRecord = PriceministerSyncOrderItem::find()->where(['item_id'=>$aDelail['itemid'],'puid'=>$priceministerAccount['uid']])->one();
									if(empty($syncRecord)){
										$syncRecord = new PriceministerSyncOrderItem();
										$syncRecord->item_id = $aDelail['itemid'];
										$syncRecord->puid = $priceministerAccount['uid'];
										$syncRecord->seller_id = $priceministerAccount['username'];
										$syncRecord->purchase_id = $aDelail['purchaseid'];
										$syncRecord->status = 'P';
										$syncRecord->item_status =  $aDelail['itemstatus'];
										$syncRecord->times = 0;
										$syncRecord->create = TimeUtil::getNow();
										$syncRecord->update = TimeUtil::getNow();
										if(!$syncRecord->save())
											echo "\n save item sync record failed:". print_r($syncRecord->getErrors(),true);
									}
								}
							}
						}
					}
					else{
						foreach ($orderDetailInfo as $aDelail){
							$aDelail['purchaseid'] = (string)$anOrder->purchaseid;
							$orderDetails = PriceministerOrderDetail::find()->where(['purchaseid'=>(string)$anOrder->purchaseid,'sku'=>$aDelail['sku'],'itemid'=>$aDelail['itemid'] ])->one();
							if (empty($orderDetails)){
								$orderDetails = new PriceministerOrderDetail();
							}
							if(!empty($orderDetails->itemstatus)){
								if($orderDetails->itemstatus=='new' || $orderDetails->itemstatus=='current')
									$aDelail['itemstatus'] = $orderType;
								else 
									$aDelail['itemstatus'] = $orderDetails->itemstatus;
							}
							$orderDetails->setAttributes ($aDelail);
							if (!$orderDetails->save()){
								echo "\n failure to save priceminister order details ,uid:".$priceministerAccount['uid']."error:". print_r($orderDetails->getErrors());
								$rtn['message'].=(string)$anOrder->purchaseid."更新原始订单商品失败;";
								$rtn['success']=false;
								$insertErrOrder['srcTbale'][]=empty($anOrder->purchaseid)?'':(string)$anOrder->purchaseid;
								
								$src_detail_update_failed++;
								$src_detail_update_failed_happend_site=$priceministerAccount['site_id'];
								$err_type['5']['times'] +=1;
								$err_type['5']['site_id']=$priceministerAccount['site_id'];
								$err_type['5']['last_msg']='failure to update priceminister order detail ,update to db failed!';
								
								continue;
							}else{
								echo "\n save priceminister order detail success!";
								$src_detail_update_success++;
								$syncProdInfoData[] = $aDelail;
								if($orderType=='current' || $orderType=='new'){
									$syncRecord = PriceministerSyncOrderItem::find()->where(['item_id'=>$aDelail['itemid'],'puid'=>$priceministerAccount['uid']])->one();
									if(empty($syncRecord)){
										$syncRecord = new PriceministerSyncOrderItem();
										$syncRecord->item_id = $aDelail['itemid'];
										$syncRecord->puid = $priceministerAccount['uid'];
										$syncRecord->seller_id = $priceministerAccount['username'];
										$syncRecord->purchase_id = $aDelail['purchaseid'];
										$syncRecord->status = 'P';
										$syncRecord->item_status =  $aDelail['itemstatus'];
										$syncRecord->times = 0;
										$syncRecord->create = TimeUtil::getNow();
										$syncRecord->update = TimeUtil::getNow();
										if(!$syncRecord->save())
											echo "\n save item sync record failed:". print_r($syncRecord->getErrors(),true);
									}
								}
							}
						}
					}
				}else{
					echo 'failure to save priceminister order detail, orderDetailInfo lost!';
					$rtn['message'].="原始订单商品信息丢失;";
					$rtn['success']=false;
					$insertErrOrder['srcTbale'][]=empty($orderModel->purchaseid)?'':$orderModel->purchaseid;
					if ($newCreateOrder){
						$src_detail_insert_failed ++;
						$src_detail_insert_failed_happend_site = $priceministerAccount['site_id'];
						$err_type['2']['times'] +=1;
						$err_type['2']['site_id']=$priceministerAccount['site_id'];
						$err_type['2']['last_msg']='failure to insert priceminister order detail, detail info lost!';
					}
					else{
						$src_detail_update_failed++;
						$src_detail_update_failed_happend_site = $priceministerAccount['site_id'];
						$err_type['5']['times'] +=1;
						$err_type['5']['site_id']=$priceministerAccount['site_id'];
						$err_type['5']['last_msg']='failure to update priceminister order detail, detail info lost!';
					}
					continue;
				}
				*/
				
				//format Order Data
				$orderInfo['saas_platform_user_id'] = $priceministerAccount['site_id'];
				
				//format import order data
				//echo "\n YS0.5 start to formated order data";
				$formated_order_data = self::_formatImportOrderData( $orderInfo , $priceministerAccount, $getEmail=true);
				$formated_order_detail_data = self::_formatImportOrderItemsData( $orderDetailInfo,(string)$anOrder->purchaseid);
				echo "\n formated order data done;";
				if(empty($formated_order_data['shipping_cost']))
					$formated_order_data['shipping_cost']=0;

				//echo "\n YS0.7 start to _savePriceministerOrderToEagle";
				//	Step 2: save this order to eagle OMS 2.0, using the same record ID	
				if(!empty($addi_info)){
					foreach ($formated_order_detail_data['items'] as &$formated_item){
						$formated_item['addi_info'] = json_encode($addi_info);
					}
				}
				
				$formated_order_data['items']=$formated_order_detail_data['items'];
				
				//echo "\n ******liang test 001 ***** \n";
				//echo "\n formated_order_data's order_status:".(empty($formated_order_data['order_status'])?'':$formated_order_data['order_status'])." \n";
				$importOrderResult=self::_savePriceministerOrderToEagle($formated_order_data,$eagleOrderRecordId=-1);
				if (!isset($importOrderResult['success']) or $importOrderResult['success']==1){
					echo "\n failure insert an order to oms 2,result:";
					print_r($importOrderResult);
					$insertErrOrder['omsTbale'][]=empty($anOrder->purchaseid)?'':(string)$anOrder->purchaseid;
					$rtn['message'].=empty($anOrder->purchaseid)?'':(string)$anOrder->purchaseid."订单保存到oms失败;";
					$addinfo['oms_auto_inserted'] = 0;
					$addinfo['errors'] = '订单保存到oms失败';
					$rtn['success']=false;
					
					if($newCreateOrder){
						$oms_insert_failed ++;
						$oms_insert_failed_happend_site =  $priceministerAccount['site_id'];
						$err_type['1']['times'] +=1;
						$err_type['1']['site_id']=$priceministerAccount['site_id'];
						$err_type['1']['last_msg']='failure to insert priceminister order to oms!';
					}else{
						$oms_update_failed ++;
						$oms_update_failed_happend_site =  $priceministerAccount['site_id'];
						$err_type['1']['times'] +=1;
						$err_type['1']['site_id']=$priceministerAccount['site_id'];
						$err_type['1']['last_msg']='failure to update priceminister order to oms!';
					}
					continue;
				}else{
					$addinfo['oms_auto_inserted'] = 1;
					echo "\n Success insert an order to oms 2.";
					
					$order = OdOrder::find()->where(['order_source_order_id'=>$formated_order_data['order_source_order_id']])->one();
					if($newCreateOrder && !empty($order)){
						$oms_insert_success ++;
					}
					if($newCreateOrder && empty($order) && !isset($importOrderResult['message'])){
						echo "\n error:oms insert success but order model not find!";
						$oms_insert_failed ++;
						$oms_insert_failed_happend_site =  $priceministerAccount['site_id'];
					}
					if($newCreateOrder && $order==null && isset($importOrderResult['message'])){
						if( stripos($importOrderResult['message'], 'E009')===false){
							$oms_insert_failed ++;
							$oms_insert_failed_happend_site =  $priceministerAccount['site_id'];
						}else{
							$oms_insert_success ++;//合并过的订单
						}
					}
					if(!$newCreateOrder){
						if(empty($order)){
							$oms_insert_failed ++;
							$oms_insert_failed_happend_site =  $priceministerAccount['site_id'];
						}else{
							$oms_update_success ++;
						}
					}
				}
				
				//sync Product info from offer list
				if (!empty($orderDetailInfo) && $newCreateOrder){
					foreach ($orderDetailInfo as $aDelail){
						$syncProdInfoData[] = $aDelail;
					}
				}
				
			}//end of each order 
			
			if(!empty($syncProdInfoData))
				self::saveOrderDetailToOfferListIfIsNew($syncProdInfoData, $priceministerAccount);
			
			if(!empty($insertErrOrder['omsTbale'])){
				\Yii::error(['priceminister',__CLASS__,__FUNCTION__,'Background',date('Y-m-d H:i:s')." CdInsertOrderError:".json_encode($insertErrOrder) ],"edb\global");
			}
			
			/*	//todo
			echo "\n Start to calculateSalesWithin15Days: ";
			$nowTimeStr = TimeUtil::getNow();
			self::calculateSalesWithin15Days($priceministerAccount['store_name'], $priceministerAccount['username'],$nowTimeStr);
			*/
			
			//set dash-broad log
			if(empty($the_day))
				$the_day=date("Y-m-d",time());
			
			$command = Yii::$app->db_queue->createCommand("select * from app_it_dash_board where app='PMOMS' and info_type='orders' and `the_day`='".$the_day."' "  );
			$record = $command->queryOne();
			if(!empty($record)){
				//echo "\n liang test log 1";
				$orders_count_str = $record['addinfo'];
				$order_count = json_decode($orders_count_str,true);
				
				$order_count['oms_insert_success'] = empty($order_count['oms_insert_success'])?$oms_insert_success:$order_count['oms_insert_success']+$oms_insert_success;
				$order_count['oms_insert_failed'] = empty($order_count['oms_insert_failed'])?$oms_insert_failed:$order_count['oms_insert_failed']+$oms_insert_failed;
				$order_count['oms_update_success'] = empty($order_count['oms_update_success'])?$oms_update_success:$order_count['oms_update_success']+$oms_update_success;
				$order_count['oms_update_failed'] = empty($order_count['oms_update_failed'])?$oms_update_failed:$order_count['oms_update_failed']+$oms_update_failed;	
				
				$failed_happend_site_str = $record['addinfo2'];
				$failed_happend_site = json_decode($failed_happend_site_str,true);
				
				if(!empty($failed_happend_site['oms_insert_failed_happend_site'])){
					if(!in_array($oms_insert_failed_happend_site,$failed_happend_site['oms_insert_failed_happend_site']))
						$failed_happend_site['oms_insert_failed_happend_site'][] = $oms_insert_failed_happend_site;
				}else{
					if(!empty($oms_insert_failed_happend_site))
					$failed_happend_site['oms_insert_failed_happend_site'] = array($oms_insert_failed_happend_site);
				}
				if(!empty($failed_happend_site['oms_update_failed_happend_site'])){
					if(!in_array($oms_update_failed_happend_site,$failed_happend_site['oms_update_failed_happend_site']))
						$failed_happend_site['oms_update_failed_happend_site'][] = $oms_update_failed_happend_site;
				}else{
					if(!empty($oms_update_failed_happend_site))
						$failed_happend_site['oms_update_failed_happend_site'] = array($oms_update_failed_happend_site);
				}

				$command = Yii::$app->db_queue->createCommand("update app_it_dash_board set `update_time`='".date("Y-m-d H:i:s",time())."',`addinfo`='".json_encode($order_count)."',`addinfo2`='".json_encode($failed_happend_site)."'  where app='PMOMS' and info_type='orders' and `the_day`='".$the_day."' "  );
				$affect_record = $command->execute();
			}else{
				//echo "\n liang test log 2";
				$order_count=[];
				$order_count['oms_insert_success'] = $oms_insert_success;
				$order_count['oms_insert_failed'] = $oms_insert_failed;
				$order_count['oms_update_success'] = $oms_update_success;
				$order_count['oms_update_failed'] = $oms_update_failed;
					
				$failed_happend_site = [];
				$failed_happend_site['oms_insert_failed_happend_site'] = empty($oms_insert_failed_happend_site)?[]:array($oms_insert_failed_happend_site);
				$failed_happend_site['oms_update_failed_happend_site'] = empty($oms_update_failed_happend_site)?[]:array($oms_update_failed_happend_site);
				
				$command = Yii::$app->db_queue->createCommand("INSERT INTO `app_it_dash_board`(`app`, `info_type`, `error_level`, `error_message`, `addinfo`, `addinfo2`, `the_day`, `update_time`) VALUES ('PMOMS','orders','normal','','".json_encode($order_count)."','".json_encode($failed_happend_site)."','".$the_day."','".date("Y-m-d H:i:s",time())."')"  );
				$affect_record = $command->execute();
			}//set runtime log end
			
			$need_mark_log = false;
			foreach ($err_type as $code=>$v){
				if($v['times']!==0)
					$need_mark_log=true;
			}
			if($need_mark_log){
				$command = Yii::$app->db_queue->createCommand("select * from app_it_dash_board where app='PMOMS' and info_type='err_type' and `the_day`='".$the_day."' "  );
				$record = $command->queryOne();
				echo "\n select from app_it_dash_board , info_type='err_type' : ";
				if(!empty($record)){
					//echo "\n liang test log 3";
					$err_type_info_str = $record['addinfo'];
					$err_type_info = json_decode($err_type_info_str,true);
					foreach ($err_type as $t=>$v){
						$err_type_info[$t]['times'] += $v['times'];
						$err_type_info[$t]['last_msg'] = $v['last_msg'];
						if(!in_array($v['site_id'],$err_type_info[$t]['site_id']))
							$err_type_info[$t]['site_id'][] = $v['site_id'];
						$err_type_info[$t]['time'] = $the_day;
					}
					$command = Yii::$app->db_queue->createCommand("update app_it_dash_board set `update_time`='".date("Y-m-d H:i:s",time())."',`addinfo`='".json_encode($err_type_info)."',`addinfo2`=' '  where app='PMOMS' and info_type='err_type' and `the_day`='".$the_day."' "  );
					$affect_record = $command->execute();
				}
				else{
					//echo "\n liang test log 4";
					$err_type_info = [];
					foreach ($err_type as $t=>$v){
						$err_type_info[$t]['times'] = $v['times'];
						$err_type_info[$t]['last_msg'] = $v['last_msg'];
						$err_type_info[$t]['site_id'][] = $v['site_id'];
						$err_type_info[$t]['time'] = $the_day;
					}
					$command = Yii::$app->db_queue->createCommand("INSERT INTO `app_it_dash_board`(`app`, `info_type`, `error_level`, `error_message`, `addinfo`, `addinfo2`, `the_day`, `update_time`) VALUES ('PMOMS','err_type','error','','".json_encode($err_type_info)."',' ','".$the_day."','".date("Y-m-d H:i:s",time())."')"  );
					$affect_record = $command->execute();
				}
			}
			
			/*
			//记录用户订单数
			if(!empty($src_insert_success)){
				$uid = $priceministerAccount['uid'];
				$sellerid=$priceministerAccount['username'];
				$classification = "PriceministerOms_TempData";
				
				//$temp_count = \Yii::$app->redis->hget($classification,"user_$uid".".".$the_day);
				//$seller_temp_count = \Yii::$app->redis->hget($classification,"user_$uid".".".$sellerid.".".$the_day);
				if(empty($temp_count))
					$temp_count = $src_insert_success;
				else 
					$temp_count = (int)$temp_count + $src_insert_success;
				
				if(empty($seller_temp_count))
					$seller_temp_count = $src_insert_success;
				else 
					$seller_temp_count = (int)$seller_temp_count + $src_insert_success;
				
				//$set_redis = \Yii::$app->redis->hset($classification,"user_$uid".".".$the_day,$temp_count);
				//$set_seller_redis = \Yii::$app->redis->hset($classification,"user_$uid".".".$sellerid.".".$the_day,$seller_temp_count);
				echo "\n set redis return : $set_redis;$set_seller_redis";
			}
			//set dash-broad log end
			*/
			
			return $rtn;
		} catch (\Exception $e) {
			//\Yii::error(['priceminister',__CLASS__,__FUNCTION__,'Background',"insert priceminister order :".$e->getMessage() ],"edb\global");
			$rtn['success'] = false;
			$rtn['message'] = $e->getMessage();
			echo "\n error: ".$rtn['message'];
			return $rtn;
		}
	}//end of _InsertPriceministerOrder
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * priceminister api 获取的数组 赋值到  eagle order 接口格式 的数组 中
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param $priceministerOrderData		priceminister 数据
	 * @param $priceministerAccount			账号 数据
	 * @param $getEmail					是否需要获取买家email
	 +---------------------------------------------------------------------------------------------
	 * @return		$importOrderData	eagle order 接口 的数组
	 * 									调用eagle order 接口 的事前准备当中还hardcode了一默认值
	 +---------------------------------------------------------------------------------------------
	 * log		name	date		note
	 * @author	lzhl	2015-07-07	初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	private static function _formatImportOrderData($priceministerOrderData,$priceministerAccount,$getEmail=false){
		$importOrderData = array();

	
		/*
		 *  eagle order status
		 *  100（未付款）、200（已付款）、300（发货处理中）、400（已发货）、500（已完成）、600（已取消）
		
		Priceminister Status list of ordered items:
		
		Description					|	GetNewSales	|	GetCurrentSales	|	GetItemsInfos	|	GetBilingInformation
		------------------------------------------------------------------------------------------------------------
		Sales to accept				|	TO_CONFIRM	|					|	REQUESTED or	|
									|				|					|	REMINDED		|
		------------------------------------------------------------------------------------------------------------
		Cancellation pre-acceptance |	CANCELLED	|					|					|
		by the customer				|				|					|					|
		------------------------------------------------------------------------------------------------------------
		Expired Sales				|				|					|					|
		------------------------------------------------------------------------------------------------------------
		Cancellation pre-acceptance |	REFUSED		|					|					|
		by the merchant				|				|					|					|
		------------------------------------------------------------------------------------------------------------
		Sales accepted – pending 	|	ACCEPTED	|	ACCEPTED		|	COMMITTED		|	ACCEPTED
		receipt of customer			|				|					|					|
		------------------------------------------------------------------------------------------------------------
		Awaiting stock (preorder)	|				|	PENDING			|	PENDING			|
		------------------------------------------------------------------------------------------------------------
		Ongoing Claim				|				|	PENDING_CLAIM	|	ON_HOLD			|	CLAIM
		------------------------------------------------------------------------------------------------------------
		Canceled	 				|				|					|	CANCELLED		|	CANCELLED
		------------------------------------------------------------------------------------------------------------
		Sale finalized, rated seller|				|					|	CLOSED			|	CLOSED
		 
		 * 
		 * */
		if($priceministerOrderData['order_status']=='new'){
			$importOrderData['order_status'] =200;
			$importOrderData['order_source_status'] = 'new';
		}
		elseif($priceministerOrderData['order_status']=='current'){
			if(!empty($priceministerOrderData['purchaseid']))
				$od = OdOrder::find()->where(['order_source_order_id'=>$priceministerOrderData['purchaseid'],'order_source'=>'priceminister'])->asArray()->one();
			if(!empty($od)){
				$eagle_order_status = (int)$od['order_status'];
				if(!empty($eagle_order_status)){
					if (!in_array($eagle_order_status, array(100, 200,300, 400, 500, 600))){
						$importOrderData['order_status'] = 200;//非正式状态 强制转换成已付款
					}else
						$importOrderData['order_status'] = $eagle_order_status;
				}else{
					$importOrderData['order_status'] = 200;
				}
				
				$eagle_order_source_status = $od['order_source_status'];
				if(empty($eagle_order_source_status) || $eagle_order_source_status=='new'){
					$importOrderData['order_source_status'] = 'current';
				}else{
					$importOrderData['order_source_status'] = $eagle_order_source_status;
				}
			}else{
				$importOrderData['order_status'] =200;
				$importOrderData['order_source_status'] = 'current';
			}	
		}else{
			if($priceministerOrderData['order_status']=='claim')
				$importOrderData['order_status'] =OdOrder::STATUS_CLAIM;
			elseif($priceministerOrderData['order_status']=='refused')
				$importOrderData['order_status'] =OdOrder::STATUS_REFUND;
			$importOrderData['order_status'] =500;
		}
		//发货状态：1.已发货；0.未发货 #非已发货状态暂时定义为未发	//暂时所有PM订单锁定为未发货
		$importOrderData['shipping_status'] = 0;//暂时所有PM订单锁定为未发货
		$importOrderData['pay_status'] = 1;//暂时所有PM订单锁定为已付款
		
		$importOrderData['order_source'] = 'priceminister';//订单来源
		
		$importOrderData['order_type'] = '';//订单类型
		/*FBC类型判断未明确
		if($priceministerOrderData['IsCLogistiqueOrder']!=='false')
			$importOrderData['order_type'] = 'FBC';
		
		if($importOrderData['order_type']=='FBC'){//如果是FBC订单，则认为是已发货。
			$importOrderData['order_status']=500;
			$importOrderData['shipping_status']=1;
		}
		*/
		if(isset($priceministerOrderData['shippingtype']) && !is_null($priceministerOrderData['shippingtype']))
			$importOrderData['order_source_shipping_method'] = $priceministerOrderData['shippingtype'];
		
		if (isset($priceministerOrderData['purchaseid'])){
			$importOrderData['order_source_order_id'] = $priceministerOrderData['purchaseid']; //订单来源的订单id
		}

		$importOrderData['order_source_site_id'] = 'FR'; //订单来源平台下的站点,Priceminister暂无分站点,只有法国站
		
		
		if (isset($priceministerOrderData['seller_login']) && !is_null($priceministerOrderData['seller_login']))
			$importOrderData['selleruserid'] = $priceministerOrderData['seller_login']; //订单来源平台卖家用户名(下单时候的用户名)

		if (isset($priceministerOrderData['saas_platform_user_id']) && !is_null($priceministerOrderData['saas_platform_user_id']))
			$importOrderData['saas_platform_user_id'] = $priceministerOrderData['seller_id']; //订单来源平台卖家用户id
		else 
			$importOrderData['saas_platform_user_id']='';
		//$Civility['MR'] = "M.";
		//$Civility['MRS'] = "Mme";
		//$Civility['MISS'] = "Mlle";
		$buyer_name='';
		if(isset($priceministerOrderData['deliveryaddress_civility']) && !is_null($priceministerOrderData['deliveryaddress_civility']))
			$buyer_name.=$priceministerOrderData['deliveryaddress_civility']." ";
		if(isset($priceministerOrderData['deliveryaddress_firstname']) && !is_null($priceministerOrderData['deliveryaddress_firstname']))
			$buyer_name.=$priceministerOrderData['deliveryaddress_firstname']." ";
		if(isset($priceministerOrderData['deliveryaddress_lastname']) && !is_null($priceministerOrderData['deliveryaddress_lastname']))
			$buyer_name.=$priceministerOrderData['deliveryaddress_lastname'];
			
		$importOrderData['source_buyer_user_id'] = empty($priceministerOrderData['purchasebuyerlogin'])?'':$priceministerOrderData['purchasebuyerlogin']; //买家名称
		$importOrderData['consignee'] = trim($buyer_name); //收货人
		
		if (isset($priceministerOrderData['purchasedate']) && !is_null($priceministerOrderData['purchasedate'])){
			//时间格式需要处理转换成正常格式
			$purchasedate = explode('-', $priceministerOrderData['purchasedate']);
			$create_date = $purchasedate[0];
			$create_date = explode('/', $create_date);
			$create_time = $purchasedate[1];
			$datetime = $create_date[2].'-'.$create_date[1].'-'.$create_date[0].' '.$create_time;
			
			$importOrderData['order_source_create_time'] = strtotime($datetime); //订单在来源平台的下单时间
			$importOrderData['paid_time'] = strtotime($datetime); //订单在来源平台的下单时间
		}
		if (isset($priceministerOrderData['deliveryaddress_zipcode']) && !is_null($priceministerOrderData['deliveryaddress_zipcode']))
			$importOrderData['consignee_postal_code'] = $priceministerOrderData['deliveryaddress_zipcode']; //收货人邮编
		
		if (isset($priceministerOrderData['deliveryaddress_phonenumber2']) && !is_null($priceministerOrderData['deliveryaddress_phonenumber2']))
			$importOrderData['consignee_phone'] =$priceministerOrderData['deliveryaddress_phonenumber2']; //收货人电话
		
		if (isset($priceministerOrderData['deliveryaddress_phonenumber1']) && !is_null($priceministerOrderData['deliveryaddress_phonenumber1']))
			$importOrderData['consignee_mobile'] =$priceministerOrderData['deliveryaddress_phonenumber1']; //收货移动电话
		
		if (isset($priceministerOrderData['purchasebuyeremail']) && !is_null($priceministerOrderData['purchasebuyeremail']))
			$importOrderData['consignee_email'] =$priceministerOrderData['purchasebuyeremail']; //收货人Email
		else 
			$importOrderData['consignee_email'] = 'N/A';
		/*
		$getEmail_retry = 0;
		while (empty($importOrderData['consignee_email']) && $getEmail && $getEmail_retry<2){
			$importOrderData['consignee_email'] = PriceministerOrderInterface::getEmailByOrderID($priceministerAccount, $priceministerOrderData['OrderNumber']);
			$getEmail_retry++;
		}
		if(empty($importOrderData['consignee_email']) || !is_string($importOrderData['consignee_email']))
			$importOrderData['consignee_email'] = 'N/A';
		*/
		/*
		if (isset($priceministerOrderData['ShippingAddress']['CompanyName']) && is_string($priceministerOrderData['ShippingAddress']['CompanyName']))
			$importOrderData['consignee_company'] =$priceministerOrderData['ShippingAddress']['CompanyName']; //收货人公司
		*/
		if (!empty($priceministerOrderData['deliveryaddress_country']) && is_string(($priceministerOrderData['deliveryaddress_country'])) ){
			if(isset(self::$PM_CountryCode_Mapping[$priceministerOrderData['deliveryaddress_country']])){
				$importOrderData['consignee_country'] = self::$PM_CountryCode_Mapping[$priceministerOrderData['deliveryaddress_country']]['en']; //收货人国家名
				$importOrderData['consignee_country_code'] = self::$PM_CountryCode_Mapping[$priceministerOrderData['deliveryaddress_country']]['code']; //收货人国家代码
			}else{
				$importOrderData['consignee_country'] = 'France'; //收货人国家名
				$importOrderData['consignee_country_code'] = 'FR'; //收货人国家代码
			}
			
		}else{
			$importOrderData['consignee_country'] = 'FR'; //收货人国家名
			$importOrderData['consignee_country_code'] = 'FR'; //收货人国家代码
		}
		
		if (isset($priceministerOrderData['deliveryaddress_city']) && !is_null($priceministerOrderData['deliveryaddress_city']))
			$importOrderData['consignee_city'] = $priceministerOrderData['deliveryaddress_city']; //收货人城市
		/*
		if (isset($priceministerOrderData['ShippingAddress']['PlaceName']) && !is_null($priceministerOrderData['ShippingAddress']['PlaceName']))
			$importOrderData['consignee_district'] = $priceministerOrderData['ShippingAddress']['PlaceName']; //收货人地区
		*/
		$importOrderData['consignee_address_line1']='';
		if (isset($priceministerOrderData['deliveryaddress_address1']) && !is_null($priceministerOrderData['deliveryaddress_address1']))
			$importOrderData['consignee_address_line1'] = $priceministerOrderData['deliveryaddress_address1']; //收货人地址1
		/*
		//Apartment
		if (isset($priceministerOrderData['ShippingAddress']['ApartmentNumber']) and is_string($priceministerOrderData['ShippingAddress']['ApartmentNumber']) ){
			if(stripos($priceministerOrderData['ShippingAddress']['ApartmentNumber'],"apt")===false && stripos($priceministerOrderData['ShippingAddress']['ApartmentNumber'],"app")===false && stripos($priceministerOrderData['ShippingAddress']['ApartmentNumber'],"Apartment")===false)
				$Apartment = "Apt.".$priceministerOrderData['ShippingAddress']['ApartmentNumber'];
			else
				$Apartment = $priceministerOrderData['ShippingAddress']['ApartmentNumber'];
				
			if(empty($importOrderData['consignee_address_line1']))
				$importOrderData['consignee_address_line1'] = "Apt.".$priceministerOrderData['ShippingAddress']['ApartmentNumber'];
			else
				$importOrderData['consignee_address_line1'] = $Apartment.";".$importOrderData['consignee_address_line1'];
		}
		//Building
		if(isset($priceministerOrderData['ShippingAddress']['Building']) && is_string($priceministerOrderData['ShippingAddress']['Building'])){
			if(stripos($priceministerOrderData['ShippingAddress']['Building'],"bât")===false && stripos($priceministerOrderData['ShippingAddress']['Building'],"BTMENT")===false && stripos($priceministerOrderData['ShippingAddress']['Building'],"Bâtiment")===false)
				$Btment = "bât.".$priceministerOrderData['ShippingAddress']['Building'];
			else
				$Btment = $priceministerOrderData['ShippingAddress']['Building'];

			if(empty($importOrderData['consignee_address_line1']))
				$importOrderData['consignee_address_line1'] = $Btment;
			else
				$importOrderData['consignee_address_line1'] = $Btment.';'.$importOrderData['consignee_address_line1'];
		}
		*/
		//echo "\n fianl consignee_address_line1 is:".$importOrderData['consignee_address_line1'];//liang test
		if (isset($priceministerOrderData['deliveryaddress_address2']) && !is_null($priceministerOrderData['deliveryaddress_address2']))
			$importOrderData['consignee_address_line2'] = $priceministerOrderData['deliveryaddress_address2']; //收货人地址2
		/*
		if (isset($importOrderData['order_source_create_time'] ))
			$importOrderData['paid_time'] = $importOrderData['order_source_create_time'] ; //订单付款时间
		*/
		if(!empty($priceministerOrderData['subtotal']))
			$importOrderData['subtotal'] = $priceministerOrderData['subtotal'];
			
		if(!empty($priceministerOrderData['grand_total']))
			$importOrderData['grand_total'] = $priceministerOrderData['grand_total'];
		if(!empty($priceministerOrderData['currency']))
			$importOrderData['currency'] = $priceministerOrderData['currency']; //货币
		else
			$importOrderData['currency'] = 'EUR'; //货币

		return $importOrderData;
	}//end of _formatImportOrderData
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * priceminister api 获取的item数组 赋值到  eagle order item接口格式 的数组 中
	 +---------------------------------------------------------------------------------------------
	 * @access 		static
	 +---------------------------------------------------------------------------------------------
	 * @param 		$priceministerOrderLine		priceminister 数据
	 +---------------------------------------------------------------------------------------------
	 * @return		$importOrderItems		eagle order item接口 的数组
	 * 										调用eagle order item接口 的事前准备当中还hardcode了一默认值
	 +---------------------------------------------------------------------------------------------
	 * log		name	date		note
	 * @author	lzhl	2015-07-07	初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	private static function _formatImportOrderItemsData($priceministerOrderItems,$purchaseid){
		$importOrderItems = array();
		$total_amount=0;
		$delivery_amonut=0;
		$total_qty=0;
		
		foreach ($priceministerOrderItems as $orderline){
			$row=array();
			$row['order_source_order_id'] = $purchaseid;
			
			$row['sku'] = '';
			if(!empty($orderline['sku']) && is_string($orderline['sku']))
				$row['sku']=$orderline['sku'];
			if (empty($row['sku']))
				$row['sku'] = !empty($orderline['advertid'])?$orderline['advertid']:'';
			
			$row['source_item_id'] = !empty($orderline['itemid'])?$orderline['itemid']:'';
			$row['order_source_order_item_id'] = !empty($orderline['itemid'])?$orderline['itemid']:'';

			$row['product_name']='';
			if(!empty($orderline['headline']) && is_string($orderline['headline'])){
				$row['product_name']=$orderline['headline'];
			}
			/*
			 * else{
				$srcOffer = PriceministerOfferList::find()->where(['seller_product_id'=>$row['sku']])->one();
				if($srcOffer<>null)
					$row['product_name'] = $srcOffer->name;
			}*/
			if(empty($row['product_name'])) $row['product_name']=$row['sku'];
			
			$row['order_source_itemid'] = !empty($orderline['advertid'])?$orderline['advertid']:'';
			
			if(!empty($orderline['price_amount']))
				$row['price'] = $orderline['price_amount'];
			
			$row['ordered_quantity'] = 1;
			$row['quantity'] = 1;
			//$priceministerOrderLine['RowId']=0;
			
			$row['platform_status']=$orderline['itemstatus'];
			
			if($orderline['itemstatus']=='CLOSED'){
				$row['sent_quantity']=1;
				$row['packed_quantity']=1;
			}
			
			$row['photo_primary']='';//priceminister response none product photo info
			$items[]=$row;
		}
		
		$importOrderItems['items']=$items;
		return $importOrderItems;
	}
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 获取某个时间之间的所有的订单
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param $dateStart		获取订单的起始时间
	 +---------------------------------------------------------------------------------------------
	 * @return				order
	 * @description			获取某个时间之间的所有的订单
	 +---------------------------------------------------------------------------------------------
	 * log			name	date			note
	 * @author		lzhl	2014/12/08		初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	/*
	private static function _getAllOrdersSince($priceminister_token , $createtime='', $endtime='',$newBinding =false){
		$timeout=240; //s
		echo "\n enter function : _getAllOrdersSince";
		//\Yii::info(['wish',__CLASS__,__FUNCTION__,'Background',"start to call proxy for crt/upd order,token $wish_token"],"edb\global");
		$config = array('tokenid' => $priceminister_token);
		$get_param['config'] = json_encode($config);
		
		if($newBinding){
			if ($createtime !== ''){
				$params['begincreationdate'] = $createtime;
			}
			if ($endtime !== ''){
				$params['endcreationdate'] = $endtime;
			}
		}else{
			if ($createtime !== ''){
				$params['beginmodificationdate'] = $createtime;
			}
			if ($endtime !== ''){
				$params['endmodificationdate'] = $endtime;
			}
		}
	
		//$params['state'] = array();
		$params['state'] = self::$orderStatus;
		$get_param['query_params'] = json_encode($params);
	
		$retInfo=PriceministerProxyConnectHelper::call_Priceminister_api("getOrderList",$get_param,$post_params=array(),$timeout );
		//\Yii::info(['wish',__CLASS__,__FUNCTION__,'Background',"complete calling proxy for crt/upd order,token $wish_token"],"edb\global");

		return $retInfo;
	}//end of _getAllChangeOrdersSince
	*/
	
	/**
	 * 通过前端手动触发获取CD订单
	 * @param unknown $priceminister_token
	 * @param string $createtime
	 * @param string $endtime
	 * @param string $newBinding
	 * @param unknown $state
	 * @return string
	 */
	public static function getOrdersByCondition($priceminister_token ,$username, $createtime='', $endtime='',$type='new'){
		$timeout=240; //s
		echo "\n enter function : _getAllOrdersSince";
		$Pm_interface = new PriceministerOrderInterface();
		$Pm_interface->setStoreNamePwd($username, $priceminister_token);
		//get type : new / current
		$retInfo = $Pm_interface->GetSales($type);
		//print_r($retInfo);
		return $retInfo;
		
	}
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 获取某个时间之后未发货的订单
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param $dateStart		获取未发货订单的起始时间 
	 +---------------------------------------------------------------------------------------------
	 * @return				order
	 * @description			获取某个时间之后未发货的订单
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2014/12/08				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	/*
	private static function _getAllUnfulfilledOrdersSince($priceminister_token , $createtime='', $endtime=''){
		$timeout=240; //s
		echo "\n enter function : _getAllUnfulfilledOrdersSince";
		//\Yii::info(['wish',__CLASS__,__FUNCTION__,'Background',"start to call proxy for crt/upd order,token $wish_token"],"edb\global");
		$config = array('tokenid' => $priceminister_token);
		$get_param['config'] = json_encode($config);
		if ($createtime !== ''){
			$params['begincreationdate'] = $createtime;
			$params['beginmodificationdate'] = $createtime;
		}
		if ($endtime !== ''){
			$params['endcreationdate'] = $endtime;
			$params['endmodificationdate'] = $endtime;	
		}
		
		//$params['state'] = array();
		$params['state'] = array(self::$orderStatus_Unshipped);
	 
		$get_param['query_params'] = json_encode($params);
		
		$retInfo=PriceministerProxyConnectHelper::call_Priceminister_api("getOrderList",$get_param,$post_params=array(),$timeout );
		//\Yii::info(['wish',__CLASS__,__FUNCTION__,'Background',"complete calling proxy for crt/upd order,token $wish_token"],"edb\global");

		//check the return info
		return $retInfo;
	}//end of _getAllChangeOrdersSince
	*/


	/**
	 * 把Priceminister的订单信息header和items 同步到eagle1系统中user_库的od_order和od_order_item。
	 * 这里主要是通过eagle1提供的 http api的方式
	 * @param unknown $orderHeaderInfo
	 * @param unknown $orderItems
	 * @param unknown $uid
	 * @param unknown $merchantId
	 * @param unknown $marketplaceId
	 * @return multitype:number NULL |Ambigous <\eagle\modules\order\helpers\订单id, multitype:number string >
	 */
	private static function _savePriceministerOrderToOldEagle1($oneOrderReq){
 		return ['success'=>0 , 'responseStr'=>'{"success": 0,"eagle_order_id": -1}'];
		//1. 总的请求信息
		$reqInfo=array();
		$ordersReq=array();
		$ordersReq[]=$oneOrderReq;
		//$uid=$merchantidUidMap[$orderHeaderInfo["merchant_id"]];
		//其中Uid是saas_Wish_user中的uid，这里为了便于eagle的api找到合适的数据库。
		$uid=\Yii::$app->subdb->getCurrentPuid();

		$reqInfo[$uid]=$ordersReq;
		$reqInfoJson=json_encode($reqInfo,true);
	
		//echo "YSa before OrderHelper::importPlatformOrder info:".json_encode($reqInfo,true)."\n";
		$postParams=array("orderinfo"=>$reqInfoJson);
		$result=EagleOneHttpApiHelper::sendOrderInfoToEagle($postParams);
		//echo "YSb result:".print_r($result,true);
		return $result;
	}
	
	
	/**
	 * 把Priceminister的订单信息header和items 同步到eagle系统中user_库的od_order和od_order_item
	 * @param unknown $orderHeaderInfo
	 * @param unknown $orderItems
	 * @param unknown $uid
	 * @param unknown $merchantId
	 * @param unknown $marketplaceId
	 * @return multitype:number NULL |Ambigous <\eagle\modules\order\helpers\订单id, multitype:number string >
	 */
	private static function _savePriceministerOrderToEagle( $oneOrderReq, $eagleOrderId=-1){
		$result = ['success'=>1,'message'=>''];
		$uid=\Yii::$app->subdb->getCurrentPuid();
		
		$reqInfo[$uid]=array_merge(OrderHelper::$order_demo,$oneOrderReq);
		
		try{
			$result =  OrderHelper::importPlatformOrder($reqInfo,$eagleOrderId);
			//SysLogHelper::SysLog_Create('Order',__CLASS__, __FUNCTION__,'info','data='.json_encode($reqInfo));//test liang
		}catch(\Exception $e){
			$message = "importPlatformOrder fails.  PriceministerId=$eagleOrderId  Exception error:".$e->getMessage()."data: \n ".print_r($reqInfo,true);
			//\Yii::error(['priceminister',__CLASS__,__FUNCTION__,'Background',"Step 1.5a ". $message ],"edb\global");
			echo $message;
			return ['success'=>1,'message'=>$message];
		}
	
		return $result;
	}
	
	/**
	 * set priceminister store token,when it's token is null or token was expired
	 * @param	object	$priceministerUser
	 * @param	string	$token
	 * return	array	$priceministerUser
	 */
	Public static function setPriceministerUserToken($priceministerUser,$token){
		//echo "\n setPriceministerUserToken 0";
		$rtn['success'] = true;
		$rtn['message'] = '';
		if(!empty($token)){
			//echo "\n setPriceministerUserToken 0-1";
			$priceministerUser->token = $token;
			$priceministerUser->token_expired_date = date('Y-m-d H:i:s');
			$priceministerUser->update_time = date('Y-m-d H:i:s');

			if(!$priceministerUser->save()){
				$rtn['success'] = false;
				$rtn['message'] = '设置token失败，请联系客服';
			}
		}
		else{
			$rtn['success'] = false;
			$rtn['message'] = 'token不能为空！';
		}
		return $rtn;
	}

	/**
	 +---------------------------------------------------------------------------------------------
	 * cron auto accept orders 
	 +---------------------------------------------------------------------------------------------
	 * @access 	static
	 +---------------------------------------------------------------------------------------------
	 * @param 	$priceministerAccount		saas表数据
	 * @param 	$orders						api返回的NewSale订单数据
	 * @param 	$status						string : 'Accept' or 'Refuse'
	 +---------------------------------------------------------------------------------------------
	 * @return	boolean
	 +---------------------------------------------------------------------------------------------
	 * log			name		date				note
	 * @author		lzhl		2016/06/06			初始化
	 *
	 +---------------------------------------------------------------------------------------------
	 **/
	public static function _autoAcceptOrders($priceministerAccount, $orders=[], $state='Accept'){
		echo "\n entry _autoAcceptOrders;";
		$timeout=240; //s
		$rtn = [];
		//echo "\n enter PriceministerOrderHelper function : AcceptOrRefuseOrders ,uid=$uid ,storeName=$storeName;";
		//echo "\n orderId=".print_r($orderIds);
	
		if(empty($priceministerAccount['token']) || empty($priceministerAccount['username'])){
			$rtn['message']="PM account username/token info lost!";
			$rtn['success'] = false ;
			return $rtn;
		}
		
		$pm_api = new PriceministerOrderInterface();
		$pm_api->setStoreNamePwd($priceministerAccount['username'], $priceministerAccount['token']);
		
		foreach ($orders as $anOrder){
			$items=[];
			if(empty($anOrder->items->item))
				$items = [];
			elseif(is_object($anOrder->items->item)){
				foreach ($anOrder->items->item as $i)
					$items[] = $i;
			}
			else{
				$items[] = $anOrder->items->item;
			}
			
			//订单可能是买家讨价单，这种单不要做自动接受
			$isnego_order = false;
			foreach ($items as $index=>$item){
				if(!empty($item->isnego) && $item->isnego=='Y')
					$isnego_order = true;
			}
			if($isnego_order)
				continue;
			//讨价单判断 end
			
			foreach ($items as $index=>$item){//$item is an object
				if(!empty($item->itemid)){
					$one_item = (string)$item->itemid;
					$api_rtn = $pm_api->AcceptRefuseOrderItem($state,$one_item);
					echo "\n item id : ".$one_item." auto accpepted by console;";
				}else 
					continue;
			}
		}
		
		return $rtn;
	}
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * to update the state of orders
	 +---------------------------------------------------------------------------------------------
	 * @access 	static
	 +---------------------------------------------------------------------------------------------
	 * @param 	$uid			int
	 * @param 	$storeName		平台账号 string
	 * @param 	$orderIds		平台订单号
	 *								array like : array('aaa','bbb','ccc')
	 * @param 	$status			string : 'Accept' or 'Refuse'
	 +---------------------------------------------------------------------------------------------
	 * @return	array
	 +---------------------------------------------------------------------------------------------
	 * log			name		date				note
	 * @author		lzhl		2015/08/31			初始化
	 *
	 +---------------------------------------------------------------------------------------------
	 **/
	public static function AcceptOrRefuseOrders($uid , $storeName , $orderIds=[], $state='Accept'){
		$timeout=240; //s
		$rtn = [];
		//echo "\n enter PriceministerOrderHelper function : AcceptOrRefuseOrders ,uid=$uid ,storeName=$storeName;";
		//echo "\n orderId=".print_r($orderIds);
		
		$priceministerAccount = SaasPriceministerUser::find()->where(["uid"=>$uid,"username"=>$storeName])->asArray()->one();
		if(empty($priceministerAccount)){
		$rtn['message']="have no match account!";
			$rtn['success'] = false ;
			$rtn['proxyResponse'] = "";
			return $rtn;
		}
		$priceminister_token = $priceministerAccount['token'];
		$pm_order_items = OdOrderItem::find()->select('order_source_order_item_id')->where(['order_id'=>$orderIds])->asArray()->all();
		//order_source_order_item_id
		
		$pm_api = new PriceministerOrderInterface();
		$pm_api->setStoreNamePwd($storeName, $priceminister_token);
		
		foreach ($pm_order_items as $pm_item){
			$itemid = $pm_item['order_source_order_item_id'];
			$api_rtn = $pm_api->AcceptRefuseOrderItem($state,$itemid);
			if(!$api_rtn['success']){
				$rtn['success'] = false ;
				$rtn['proxyResponse'] .= $api_rtn['message'];
			}else{
				self::updateItemStatusAfterAcceptOrRefuse($uid, $itemid, $storeName, $state);
			}
		}
		
		return $rtn;
	}
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * to update the state of order's item
	 +---------------------------------------------------------------------------------------------
	 * @access 	static
	 +---------------------------------------------------------------------------------------------
	 * @param 	$uid			int
	 * @param 	$storeName		平台账号 string
	 * @param 	$itemId			平台订单ItemId
	 * @param 	$status			string : 'Accept' or 'Refuse'
	 +---------------------------------------------------------------------------------------------
	 * @return	array
	 +---------------------------------------------------------------------------------------------
	 * log			name		date				note
	 * @author		lzhl		2016/04/06			初始化
	 *
	 +---------------------------------------------------------------------------------------------
	 **/
	public static function AcceptOrRefuseItem($uid, $itemId, $storeName, $state='Accept'){
		$timeout=240; //s
		$rtn['message']="";
		$rtn['success'] = true ;
		$rtn['proxyResponse'] = "";
	
		$priceministerAccount = SaasPriceministerUser::find()->where(["uid"=>$uid,"username"=>$storeName])->asArray()->one();
		if(empty($priceministerAccount)){
			$rtn['message']="have no match account!";
			$rtn['success'] = false ;
			$rtn['proxyResponse'] = "";
			return $rtn;
		}
		$priceminister_token = $priceministerAccount['token'];
	
		$pm_api = new PriceministerOrderInterface();
		$pm_api->setStoreNamePwd($storeName, $priceminister_token);
		
		$api_rtn = $pm_api->AcceptRefuseOrderItem($state,$itemId);
		if(!$api_rtn['success']){
			$rtn['success'] = false ;
			$rtn['message'] = $api_rtn['message'];
			$rtn['proxyResponse'] .= $api_rtn['message'];
		}
		return $rtn;
	}
	
	
	public static function updateItemStatusAfterAcceptOrRefuse($uid, $itemId, $storeName, $state){
		$rtn=['success'=>true,'message'=>''];
		$go_on = true;
		$purchaseid = '';
		
		$od_orderitem = OdOrderItem::find()->where(['order_source_order_item_id'=>$itemId])->one();
		if(!empty($od_orderitem->order_id)){
			$order = OdOrder::findOne($od_orderitem->order_id);
			if(!empty($order->order_id)){
				$orderid = $order->order_id;
				$purchaseid = $order->order_source_order_id;
			}else{
				$rtn['success']=false;
				$rtn['message'] .= 'item对应的订单缺失;';
				$go_on = false;
			}
		}else{
			$rtn['success']=false;
			$rtn['message'] .= 'od_orderitem缺失;';
			$go_on = false;
		}
		if(!$go_on){
			if(!empty($orderid)){
				OperationLogHelper::log('order', $orderid, $state.' order item', $rtn['message'] , \Yii::$app->user->identity->getFullName());
				return $rtn;
			}else 
				return $rtn;
		}
		
		if(strtolower($state)=='accept')
			$itemstatus = 'ACCEPTED';
		elseif(strtolower($state)=='refuse')
			$itemstatus = 'CANCELLED';
		else{
			$rtn=['success'=>false,'message'=>'非法的操作类型。'];
			$go_on = false;
		}
		
		//废弃原始表
		//PriceministerOrderDetail::updateAll(['itemstatus'=>$itemstatus],'itemid=:itemid',[':itemid'=>$itemId]);

		if($go_on){
			$addi_info = empty($od_orderitem->addi_info)?[]:json_decode($od_orderitem->addi_info,true);
			if(empty($addi_info))
				$addi_info = [];
			$addi_info['isNewSale'] = false;
			$addi_info['userOperated'] = true;
			$addi_info['operate_time'] = TimeUtil::getNow();
			
			$un_operated_items = OdOrderItem::find()->where(" order_item_id <>".$od_orderitem->order_item_id )
				->andwhere(['order_id'=>$od_orderitem->order_id,'platform_status'=>['TO_CONFIRM','REQUESTED','REMINDED'] ])
				->count();
			
			if(empty($un_operated_items))
				$order->order_source_status = 'current';
			
			
			$od_orderitem->addi_info = json_encode($addi_info);
			$od_orderitem->platform_status = ($itemstatus=='ACCEPTED')?'COMMITTED':$itemstatus;
			
			if($state=='accept' && $od_orderitem->manual_status!=='disable'){
				$od_orderitem->delivery_status = 'allow';
			}else 
				$od_orderitem->delivery_status = 'ban';
			
			$order->update_time = time();
			if(!$od_orderitem->save(false)){
				$rtn['success']=false;
				$rtn['message'] .= 'order item update failed;';
			}
			if(!$order->save(false)){
				$rtn['success']=false;
				$rtn['message'] .= 'order failed;';
			}
		}
		
		OperationLogHelper::log('order', $orderid, $state.' order item', empty($rtn['message'])?'success':$rtn['message'] , \Yii::$app->user->identity->getFullName());
		return $rtn;
	}
	
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * Calculate sales within 15 days by one priceminister account.
	 +---------------------------------------------------------------------------------------------
	 * @access 	static
	 +---------------------------------------------------------------------------------------------
	 * @param 	$store_name		eagle2平台cd账号名	string
	 * @param 	$user_name		cd平台登录账号 string
	 * @param 	$nowTimeStr		调用时间 （like:"2015-11-03 15:20:56"）string
	 +---------------------------------------------------------------------------------------------
	 * @return	array(product_id=>sales_count)
	 +---------------------------------------------------------------------------------------------
	 * log			name		date				note
	 * @author		lzhl		2015/11/03			初始化
	 *
	 +---------------------------------------------------------------------------------------------
	 **/
	/*
	public static function calculateSalesWithin15Days($store_name,$user_name,$nowTimeStr){
		$date= substr($nowTimeStr, 0, 10);
		$datetime = $date." 00:00:00";
		$datetime = strtotime($datetime);
		$fifteenDaysAgo = $datetime-3600*24*15;
		$oneDayAgo = strtotime($date)-3600;
		
		$calculateInDaysAgo = false;//是否统计与1日前
		
		$calculateSales = ConfigHelper::getConfig("PriceministerOrder/CalculateSalesDate",'NO_CACHE');
		
		//
		//$calculateSalesInfo=>[
		// 	'user_name1'=>$date1,
	  	//	'user_name2'=>$date2,
	  	//	.
	  	//	.
	  	//	.
		//]
		//
		if($calculateSales != null){
			$calculateSalesInfo = json_decode($calculateSales,true);
			if(isset($calculateSalesInfo[$user_name])){
				$calculateDate = $calculateSalesInfo[$user_name];
				if( strtotime($calculateDate) < $oneDayAgo )
					$calculateInDaysAgo = true;
			}
			else 
				$calculateInDaysAgo = true;
		}else{
			$calculateInDaysAgo = true;
		}
		
		//上次统计时间在一天以内，跳过统计
		if (!$calculateInDaysAgo) {
			echo "need not to calculate.";
			return true;
		}
		//开始统计
		try{
			$sql="SELECT o.selleruserid, d.sku, d.order_source_itemid, sum(d.quantity) quantity
			FROM `od_order_v2` o,`od_order_item_v2` d
			WHERE o.`order_source_order_id`=d.`order_source_order_id` 
				AND d.sku<>'' 
				AND o.`selleruserid`=:store_name 
				AND (o.`create_time`>=$fifteenDaysAgo AND o.`create_time`<=$datetime) 
			GROUP BY o.`selleruserid`, d.`sku`, d.`order_source_itemid` ";
			
			$command = \Yii::$app->get('subdb')->createCommand($sql);
			$command->bindValue(":store_name",$store_name,\PDO::PARAM_STR);
			$finalSql = $command->getRawSql();
			echo "\n calculateSalesWithin15Days sql:".$finalSql;//liang test
			$rows = $command->queryAll();
			
			$update_offer_id = [];
			foreach ($rows as $index=>$row){
				if(!empty($row['quantity'])){
					$offer = PriceministerOfferList::find()
								->where(['product_id'=>$row['order_source_itemid'],'seller_product_id'=>$row['sku'],'seller_id'=>$user_name])
								->One();
					if($offer<>null){
						$offer->last_15_days_sold = $row['quantity'];
						echo "\n find offer need to update product_id:".$row['order_source_itemid'].',seller_product_id:'.$row['sku'].',seller_id:'.$user_name.',quantity:'.$row['quantity']; //liang test
						if($offer->save(false))
							$update_offer_id[]=$offer->id;
					}
				}
			}
			
			if(!empty($update_offer_id)){
				$id_str = implode(',', $update_offer_id);
				$resetCount = PriceministerOfferList::updateAll(['last_15_days_sold'=>0],"id not in ($id_str)");
				echo "\n update have no sold in 15 days prods,count=$resetCount";
			}
		
			$calculateSalesInfo[$user_name] = TimeUtil::getNow();
			ConfigHelper::setConfig("PriceministerOrder/CalculateSalesDate",json_encode($calculateSalesInfo));
		}catch (\Exception $e) {
			echo "\n calculateSalesWithin15Days fails :".$e->getMessage();
		}
		
		
	}
	*/
	/**
	 +---------------------------------------------------------------------------------------------
	 * if order detail's prod that offer list hadn't ,create it.
	 +---------------------------------------------------------------------------------------------
	 * @access 	static
	 +---------------------------------------------------------------------------------------------
	 * @param 	$orderDetailInfo		array
	 * @param 	$priceministerAccount		array
	 +---------------------------------------------------------------------------------------------
	 * @return	createRecordCount		int
	 +---------------------------------------------------------------------------------------------
	 * log			name		date				note
	 * @author		lzhl		2015/11/11			初始化
	 *
	 +---------------------------------------------------------------------------------------------
	 **/
	static private function saveOrderDetailToOfferListIfIsNew($orderDetailInfo,$priceministerAccount){
		echo '\n enter saveOrderDetailToOfferListIfIsNew';
		$puid = \Yii::$app->subdb->getCurrentPuid();
		$PmOrderInterface = new PriceministerOrderInterface();
		$PmOrderInterface->setStoreNamePwd($priceministerAccount['username'], $priceministerAccount['token']);
		
		$item_id_list = [];//本次获得的所有item id 数组
		foreach ($orderDetailInfo as $detail){
			if(!empty($detail['itemid']))
				$item_id_list[]=$detail['itemid'];
		}
		if(empty($item_id_list)){
			echo '\n none item id exist;';
			return false;
		}
		$item_id_list_str = '\''.implode('\',\'', $item_id_list).'\'';
		$command = Yii::$app->subdb->createCommand("
				select `order_source_order_item_id` from `od_order_item_v2` 
				where  (`photo_primary` is null or `photo_primary`='' or `photo_primary` like '%no-img.png%') 
				and (`product_url` is null or `product_url`='') 
				and `order_source_order_item_id` in ($item_id_list_str) 
				and `order_id` in (select `order_id` from `od_order_v2` where `order_source`='priceminister')
				");
		$itemRows = $command->queryAll();//本次item中，需要进行get info的item
		$need_info_items = [];
		foreach ($itemRows as $row){
			if(empty($row['order_source_order_item_id']))
				continue;
			$need_info_items[] = $row['order_source_order_item_id'];
		}
		
		//由于pm允许不同商品取一样的sku，需要按商品对item id进行分组
		$unique_prods=[];
		foreach ($orderDetailInfo as $detail){
			if(!in_array($detail['itemid'],$need_info_items))
				continue;
			$keys='';
			//以sku，headline，价格3个值比较是否是同一样商品
			$keys.=empty($detail['sku'])?'':$detail['sku'].'@@';
			$keys.=empty($detail['headline'])?'':$detail['headline'].'@@';
			$keys.=empty($detail['price_amount'])?'':$detail['price_amount'];
				
			$unique_prods[$keys][]=$detail['itemid'];
		}
		
		foreach ($unique_prods as $key=>$itemids){
			//取每种商品其中一个item来获取信息，更新到所有同样的商品中
			$one_id = empty($itemids[0])?'':$itemids[0];
			if(empty($one_id))
				continue;
			$ct_rt = $PmOrderInterface->GetItemInfos($one_id);
			if (!empty($ct_rt['success']) && !empty($ct_rt['iteminfo']) ){
				$thisResult = $ct_rt['iteminfo'];
				
				if(is_object($thisResult)){
					$result =self::formatterResult($thisResult);
				}elseif(is_string($thisResult)){
					$result =json_decode($thisResult,true);
				}else{
					$result =$thisResult;
				}
				$photo = empty($result['photo_primary'])?'':$result['photo_primary'];
				$product_url = empty($result['product_url'])?'':$result['product_url'];
				if($product_url!=='' && stripos($product_url, 'http://www.priceminister.com')===false){
					$product_url = 'http://www.priceminister.com'.$product_url;
				}
				if(!empty($photo) && !empty($product_url)){
					//七牛缓存
					ImageCacherHelper::getImageCacheUrl($photo,$puid,3);
					
					$itemIdsStr = '\''.implode('\',\'', $itemids).'\'';
					OdOrderItem::updateAll([
						'photo_primary'=>$photo,'product_url'=>$product_url], 
						" (photo_primary is null or photo_primary='' or `photo_primary` like '%no-img.png%') and  (product_url is null or product_url='') and order_source_order_item_id in ($itemIdsStr)"
					);
				}
			}else{
				$error_message = (!empty($ct_rt['message'])?$ct_rt['message']:"");
				echo "\n proxy failed to get result : ".$error_message;
			}
		}
		
		
		/****旧处理逻辑，模仿CD offer list的处理，但是PM支持多商品同SKU，此逻辑失效
		$create = 0;
		$product_info_arr = [];
		$item_id_arr=[];
		$product_sku_list = [];
		
		foreach ($orderDetailInfo as $detail){
			if(empty($detail['sku']) && empty($detail['itemid']))//无任何商品标识
				continue;
			if(!empty($detail['sku'])){//有sku
				if(in_array($detail['sku'], $product_sku_list)){
					continue;
				}else{
					$product_sku_list[] = $detail['sku'];
					$product_info_arr[$detail['sku']] = array(
						'sku'=>empty($detail['sku'])?'':$detail['sku'],
						'ean'=>empty($detail['ean'])?'':$detail['ean'],
						'itemid'=>empty($detail['itemid'])?'':$detail['itemid'],
					);
				}
			}else{//无sku
				$item_id_arr[] = $detail['itemid'];
			}
		}
		if(!empty($item_id_arr))
			PriceministerOfferSyncHelper::syncProdInfoWhenGetOrderDetail($priceministerAccount['uid'],$priceministerAccount['username'], $sku_arr=[], $item_id_arr,2,$type='itemid');
		
		if(empty($product_info_arr))
			return $create;
		
		try{
			$prod_sku_arr = [];
			foreach ($product_info_arr as $info){
				$sku = $info['sku'];
				if(!in_array($sku,$prod_sku_arr)){
					$prod_sku_arr[] = $sku;
					//$prod_sku_arr[] = $info['sku'];//Ean在以前已经排除了重复，这里的Ean没有重复值
				}
			}
			//echo "<br>prod_id_arr:".print_r($prod_id_arr);
			if(!empty($prod_sku_arr)){
				//找出offer表里面没有的ean，保存他们
				$offerList = PriceministerProductList::find()->select(['barcode'])
					->where(['partnumber'=>$prod_sku_arr])
					->andWhere(['seller_id'=>$priceministerAccount['username']])
					->asArray()->all();
				
				$not_insert_sku_list = [];
				foreach ($offerList as $offer){
					$not_insert_sku_list[] = $offer['partnumber'];
				}
				$insert_sku_list = array_diff($prod_sku_arr,$not_insert_sku_list);
				$syn_itemid_list = [];
				$syn_sku_list = [];
				$insert_prod_info_arr = [];
				foreach ($insert_sku_list as $sku){
					if(!empty($product_info_arr[$sku])){
						$insert_prod_info_arr[]=array(
							'barcode'=>$product_info_arr[$sku]['ean'],
							'partnumber'=>$product_info_arr[$sku]['sku'],
							'seller_id'=>$priceministerAccount['username'],
						);
						$syn_sku_list[] = $sku;
						$syn_itemid_list[] = $product_info_arr[$sku]['itemid'];
					}
				}
				//echo "<br>insert_prod_info_arr:<br>";
				//print_r($insert_prod_info_arr);
				
				if(!empty($insert_prod_info_arr)){
					$offerInsert = SQLHelper::groupInsertToDb('priceminister_product_list', $insert_prod_info_arr);
					//mark error log
					if($offerInsert!=count($insert_prod_info_arr)){
						\Yii::error(['priceminister',__CLASS__,__FUNCTION__,'Background',date('Y-m-d H:i:s')." PmInsertOfferError:".count($insert_prod_info_arr)."need to insert, only ".$offerInsert." inserted" ],"edb\global");
					}else 
						$create = $offerInsert;
				}
				
				//抓取商品信息
				PriceministerOfferSyncHelper::syncProdInfoWhenGetOrderDetail($priceministerAccount['uid'],$priceministerAccount['username'], $syn_sku_list, $syn_itemid_list, 2, $type='sku');
			}
			return $create;
		}catch (\Exception $e) {
			echo "\n saveOrderDetailToOfferListIfIsNew error :".$e->getMessage();
			return $create;
		}
		*/
	}
	
	
	public static $OMS_STATUS_PM_STATUS_MAPPING = [
		'100'=>['AcceptedBySeller', 'PaymentInProgress' ],
		'200'=>['WaitingForShipmentAcceptation' ],
		'300'=>['WaitingForShipmentAcceptation' ],
		'400'=>['WaitingForShipmentAcceptation' ],//CD平台如果已发货，将会同步回来Shipped状态，订单自动转到已完成，否则就是同步出现问题。因此这里发货中状态还是设置为WaitingForShipmentAcceptation
		'500'=>['Shipped' ],
		'600'=>['CancelledByCustomer','RefusedBySeller','AutomaticCancellation','RefusedNoShipment','ShipmentRefusedBySeller','PaymentRefused',],
	];
	
	public static $PM_OMS_WEIRD_STATUS = [
		'sus'=>'PM后台状态和小老板状态不同步',//satatus unSync 状态不同步
		'wfs'=>'提交发货或提交物流',//waiting for shipment
		'wfd'=>'交运至物流商',//waiting for dispatch
		'wfss'=>'等待手动标记发货，或物流模块"确认已发货"',//waiting for sing shipped ,or confirm dispatch
		'tuol'=>'物流未上网',//track unOnLine
	];
	
	
	/**
	 * @description			定期自动检测用户的CD订单，如果检测到订单有可能异常，自动add tag
	 * @access static
	 * @param $job_id		job action number
	 * @return				array
	 +---------------------------------------------------------------------------------------------
	 * log		name	date		note
	 * @author	lzhl	2015/12/25	初始化
	 */
	public static function cronAutoAddTagToPriceministerOrder($job_id){
		try {
			//set runtime log
			$the_day=date("Y-m-d",time());
			$command = Yii::$app->db_queue->createCommand("select * from app_it_dash_board where app='CDOMS' and info_type='runtime-CheckOrder' and `addinfo`='job_id=".$job_id."' "  );
			$record = $command->queryOne();
			if(!empty($record)){
				$sql = "update app_it_dash_board set `update_time`='".date("Y-m-d H:i:s",time())."',`the_day`='".$the_day."'  where app='CDOMS' and info_type='runtime-CheckOrder' and `addinfo`='job_id=".$job_id."' ";
				$command = Yii::$app->db_queue->createCommand( $sql );
				$affect_record = $command->execute();
			}else{
				$sql = "INSERT INTO `app_it_dash_board`(`app`, `info_type`, `error_level`, `error_message`, `addinfo`, `addinfo2`, `the_day`, `update_time`) VALUES ('CDOMS','runtime-CheckOrder','normal','','job_id=".$job_id."','','".$the_day."','".date("Y-m-d H:i:s",time())."')";
				$command = Yii::$app->db_queue->createCommand( $sql );
				$affect_record = $command->execute();
			}//set runtime log end
			if(empty($affect_record))
				echo "\n set runtime log falied, sql:".$sql;
	
			$two_days_ago = strtotime('-2 days');
			$five_days_ago = strtotime('-5 days');
	
			$SAASCDISCOUNTUSERLIST = SaasPriceministerUser::find()->select(" distinct `uid` ")->where("is_active='1' and initial_fetched_changed_order_since is not null")
				// ->andwhere(" uid%3=$job_id ") // TODO 开启PM多进程时候做相应去注释
				->orderBy("last_order_success_retrieve_time asc")->asArray()->all();
						
			foreach($SAASCDISCOUNTUSERLIST as $priceministerAccount ){
				$uid = $priceministerAccount['uid'];
				if (empty($uid)){
					//异常情况
					$message = "site id :".$priceministerAccount['site_id']." uid:0";
					echo "\n ".$message;
					continue;
				}
	
 
	
				$updateTime =  TimeUtil::getNow();
				
				//删除已完成订单的weird_status
				OdOrder::updateAll(['weird_status'=>'']," order_status in (500,600) and (weird_status is not null or weird_status<>'') and order_source='priceminister' ");
				echo "\n cleared all complete order's weird_status";
				
				$ot_orders = OdOrder::find()
					->where(['order_source'=>'priceminister'])
					->andWhere(" update_time<='$two_days_ago' ")
					->andWhere(['in','order_status',[200,300,400]])
					->asArray()->all();
				echo "\n has ot_order :".count($ot_orders);
				
				$status_mapping = self::$OMS_STATUS_CD_STATUS_MAPPING;
				$updateOrders=[];
				$updateOrders['sus']=[];
				$updateOrders['wfs']=[];
				$updateOrders['wfd']=[];
				$updateOrders['wfss']=[];
				$updateOrders['tuol']=[];
				foreach ($ot_orders as $ot){
					//状态对应不上,weird_status暂时设置其优先级比操作超时优先级高(覆盖)
					if(isset( $status_mapping[$ot['order_status']]) && !in_array($ot['order_source_status'], $status_mapping[$ot['order_status']])){
						$updateOrders['sus'][] = $ot['order_id'];
						continue;
					}
					if($ot['order_status']==200){
						$updateOrders['wfs'][] = $ot['order_id'];
						continue;
					}
					if($ot['order_status']==300){
						$updateOrders['wfd'][] = $ot['order_id'];
						continue;
					}
					if($ot['order_status']==400){
						$updateOrders['wfss'][] = $ot['order_id'];
						continue;
					}
					//5天未上网
					if($ot['delivery_time']<$five_days_ago){
						$untrackStatus = array('checking','no_info','suspend','untrackable');//未上网状态mapping
						if(in_array($ot['logistic_status'],$untrackStatus)){
							$updateOrders['tuol'][] = $ot['order_id'];
							continue;
						}
					}
				}
				
				foreach ($updateOrders as $s=>$id_arr){
					if(!empty($id_arr)){
						OdOrder::updateAll(['weird_status'=>$s],['order_source'=>'priceminister','order_id'=>$id_arr]);
					}
				}
			}//end of each priceminister user uid
		} catch (\Exception $e) {
			echo "\n cronAutoFetchRecentOrderList Exception:".$e->getMessage();
		}
	}//end of cronAutoFetchUnFulfilledOrderList
	
	/*
	 * 手动同步单个order的item status
	 */
	public static function SyncOrderItemStatusByOrder($order_id,$uid=''){
		$rtn['success'] = true;
		$rtn['message'] = '';
		if($uid=='')
			$uid = \Yii::$app->user->id;
		if (empty($uid)){
			return ['success'=>false,'message'=>'请先登录!'];
		}
		$order = OdOrder::findOne($order_id);
		if(empty($order)){
			return ['success'=>false,'message'=>'该订单不存在!'];
		}
		$seller = $order->selleruserid;
		if(empty($seller)){
			return ['success'=>false,'message'=>'订单卖家账号信息缺失!'];
		}
		$odItems = OdOrderItem::find()->where(['order_id'=>$order->order_id])->all();
		if(empty($odItems)){
			return ['success'=>false,'message'=>'订单商品详情缺失!'];
		}
		
		foreach ($odItems as $item){
			if(empty($item->source_item_id))
				return ['success'=>false,'message'=>'商品原始item id缺失!'];
		}
		
		$PM_Account = SaasPriceministerUser::find()->where(['uid'=>$uid,'username'=>$seller])->one();
		if(!empty($PM_Account->token) && $PM_Account->order_retrieve_message!=='账号或token错误'){
			$token = $PM_Account->token;
		}else{
			return ['success'=>false,'message'=>'账号token有误!'];
		}
		
		$all_cancelled = true;//是否全部已取消
		$all_closed = true;//是否全部已完成
		$closed_or_cancelled = false;
		$have_track = false;//是否有已经发货
		$have_claim = false;//是否有理赔
		$preorder = false;//预售
		foreach ($odItems as $item){
			$thisStatus = $item->platform_status;
			try{
				$pm_api = new PriceministerOrderInterface();
				$pm_api->setStoreNamePwd($seller, $token);
				$api_rtn = $pm_api->GetItemInfos($item->source_item_id);
				
				if (!empty($api_rtn['success']) && !empty($api_rtn['iteminfo']) ){
					$thisResult = $api_rtn['iteminfo'];
				}else{
					//获取失败
					$rtn['success'] = false;
					$rtn['message'] .= "api调用出错:".$api_rtn['message'];
					continue;
				}
				$tmpResult = $thisResult;
				if(is_object($thisResult)){
					$iteminfo =self::object_to_array($thisResult);
				}else{
					$iteminfo =$thisResult;
				}
				
				//加入手工同步订单状态时更新商品图片，URL
				if(is_object($tmpResult)){
					$tmpItemInfo =self::formatterResult($tmpResult);
				}elseif(is_string($tmpResult)){
					$tmpItemInfo =json_decode($tmpResult,true);
				}else{
					$tmpItemInfo =$tmpResult;
				}
				$photo = empty($tmpItemInfo['photo_primary'])?'':$tmpItemInfo['photo_primary'];
				$product_url = empty($tmpItemInfo['product_url'])?'':$tmpItemInfo['product_url'];
				if($product_url!=='' && stripos($product_url, 'http://www.priceminister.com')===false){
					$product_url = 'http://www.priceminister.com'.$product_url;
				}
				if((!empty($photo) || !empty($product_url)) && ($photo!==$item->photo_primary || $product_url!==$item->product_url)){
					$item->photo_primary = $photo;
					$item->product_url = $product_url;
					$item->save(false);
				}
				//
					
					
				if(!empty($iteminfo['itemstatus'])){
					$thisStatus = $iteminfo['itemstatus'];
					
					/*
					$srcItem = PriceministerOrderDetail::find()->where(['itemid'=>$item->source_item_id])->orderBy("id")->One();
					if(empty($srcItem)){//如果原始item detail丢失，依旧update order status
						switch ($iteminfo['itemstatus']){
							case 'COMMITTED':
								if(!empty($iteminfo['itemlog'])){
									foreach ($iteminfo['itemlog'] as $log){
										foreach ($log as $k=>$v){
											if($k=='itemlogcode' && $v=='TRACKING')
												$thisStatus = 'TRACKING';
										}
									}
								}
								break;
							default:
								$thisStatus = $iteminfo['itemstatus'];
								break;
						}
						continue;
					}
					*/
					
					//对$iteminfo['itemstatus']分析
					switch ($iteminfo['itemstatus']){
						case 'COMMITTED':
							//$srcItem->itemstatus_of_get_item_infos='COMMITTED';
							$is_track = false;
							if(!empty($iteminfo['itemlog'])){
								foreach ($iteminfo['itemlog'] as $log){
									foreach ($log as $k=>$v){
										if($k=='itemlogcode' && $v=='TRACKING')
											$is_track = true;
									}
								}
							}
							if($is_track){
								//$srcItem->itemstatus_of_get_item_infos='TRACKING';
								$thisStatus = 'TRACKING';
							}
							break;
						default:
							//$srcItem->itemstatus_of_get_item_infos = $iteminfo['itemstatus'];
							//$thisStatus = $iteminfo['itemstatus'];
							break;
					}
					/*
					if(!$srcItem->save()){
						$rtn['success'] = false;
						$rtn['message'] .= 'item id:'.$item->source_item_id.'信息更新失败';
						continue;
					}
					*/
				}else {
					$rtn['success'] = false;
					$rtn['message'] .= 'item id:'.$item->source_item_id.'获取状态失败';
					continue;
				}
			
				//更新订单item原始状态到od_order_item_v2
				//$item->platform_status = $thisStatus;
				//$item->save();
				$updateItemData=[];
				$updateItemData['order_source_order_item_id']=$item->order_source_order_item_id;
				$updateItemData['platform_status'] = $thisStatus;
				$updateItemResult = OrderUpdateHelper::updateItem('priceminister', $order->order_id, $updateItemData);
				if(empty($updateItemResult['ack'])){
					$rtn['success'] = false;
					$rtn['message'] .= empty($updateItemResult['message'])?'订单商品状态update失败':$updateItemResult['message'];
					return $rtn;
				}
				
				if($thisStatus=='PENDING'){
					$preorder = true;
					continue;
				}
				
				if($thisStatus=='COMMITTED'){
					$all_cancelled = false;
					$all_closed = false;
					continue;
				}
				if($thisStatus=='TRACKING'){
					$have_track = true;
					$all_cancelled = false;
					$all_closed = false;
					continue;
				}
				if($thisStatus=='ON_HOLD'){
					$all_cancelled = false;
					$all_closed = false;
					$have_claim = true;
					continue;
				}
				if($thisStatus=='CLOSED'){
					$all_cancelled = false;
					$closed_or_cancelled = true;
					continue;
				}
				if($thisStatus=='CANCELLED'){
					$all_closed = false;
					$closed_or_cancelled = true;
					continue;
				}
			}catch (\Exception $e) {
				$rtn['success'] = false;
				$rtn['message'] = $e->getMessage();
				return $rtn;
			}
		}

		//预售订单,暂时不会对预售订单做什么处理
		$LB_order_status = '';
		$this_order_source_status = $order->order_source_status;//订单原始状态
		$this_order_status = $order->order_status;//小老板订单状态
		if($preorder){
			$LB_order_status = '预售订单';
			$this_order_source_status = 'preorder';
		}
		//'有申诉/理赔',最优先状态
		if(!$all_cancelled && !$all_closed && $have_claim){
			$LB_order_status = '有申诉/理赔';
			$this_order_source_status = 'claim';
			//$order->order_status = odorder::STATUS_CLAIM;
		}
		//'已发货';
		if(!$all_cancelled && !$all_closed && $have_track){
			$LB_order_status = '已发货';
			$this_order_source_status = 'tracking';
			if((int)$order->order_status <= odorder::STATUS_PAY && (!empty($order->sync_shipped_status) && $order->sync_shipped_status=='Y') ){
				$this_order_status = odorder::STATUS_SHIPPING;
			}
		}
		//'已接受/拒绝';
		if(!$all_cancelled && !$all_closed && !$have_track && !$have_claim){
			$LB_order_status = '已接受/拒绝';
			$this_order_source_status = 'current';
			if( (int)$order->order_status <= odorder::STATUS_PAY){
				$this_order_status = odorder::STATUS_PAY;
			}
		}
		//'已取消';
		if($all_cancelled){
			$LB_order_status = '已取消';
			$this_order_source_status = 'cancelled';
			if((int)$order->order_status == odorder::STATUS_PAY){
				$this_order_status = odorder::STATUS_CANCEL;
			}
		}
		//'已完成';
		if($all_closed){
			$LB_order_status = '已完成';
			$this_order_source_status = 'closed';
			if((int)$order->order_status <= odorder::STATUS_PAY && (!empty($order->sync_shipped_status) && $order->sync_shipped_status=='Y') ){
				$this_order_status = odorder::STATUS_SHIPPED;
			}
		}
		$odNewAttrs = [
			'order_source_status'=>$this_order_source_status,
			'order_status'=>$this_order_status,
		];
		$orderUpdateResult = OrderUpdateHelper::updateOrder($order->order_id, $odNewAttrs,false,\Yii::$app->user->identity->getFullName(),'UserSyncOrderStatus','order');
		if(empty($orderUpdateResult['ack'])){
			$rtn['success'] = false;
			$rtn['message'] .= empty($orderUpdateResult['message'])?"订单update失败":$orderUpdateResult['message'];
			return $rtn;
		}
		
		/*
		if($order->save()){
			OperationLogHelper::log('order', $order->order_id,'状态更新','同步状态为：'.$LB_order_status, 'system');
		}else{
			$rtn['success'] = false;
			$rtn['message'] .= '更新订单数据库状态失败';
		}
		*/
		return $rtn;
	}

	
	/*
	 * 后台同步current状态订单的item status,以求映射LB的order status
	 * 自动同步频率为6小时一次
	 */
	public static function cronSyncOrderItemStatus($job_id=0,$type='A'){
		$rtn['success'] = true;
		$rtn['message'] = '';
		echo "\n start to cronSyncOrderItemStatus by type $type at .".TimeUtil::getNow();
		
		//自动检测用户是否存在于saas_priceminister_autosync表，无则自动创建
		$saasAccounts = SaasPriceministerUser::find()->where(['is_active'=>1])
		// ->andWhere(" uid%3=$job_id ") // TODO 开启PM多进程时候做相应去注释
		->all();
		foreach ($saasAccounts as $saas){
			$syncQueue = SaasPriceministerAutosync::find()->where(['uid'=>$saas->uid,'sellerloginid'=>$saas->username])->one();
			if(empty($syncQueue)){
				$syncQueue = new SaasPriceministerAutosync();
				$syncQueue->uid = $saas->uid;
				$syncQueue->pm_uid = $saas->site_id;
				$syncQueue->sellerloginid = $saas->username;
				$syncQueue->is_active = 1;
				$syncQueue->status = 'P';
				$syncQueue->type = $type;
				$syncQueue->times = 0;
				$syncQueue->order_item = 0;
				$syncQueue->last_time = date("Y-m-d H:i:s",strtotime('-10 days'));//设置为10日前，确保马上可以get一次
				$syncQueue->create_time = TimeUtil::getNow();
				if($syncQueue->save()){
					echo "\n new inster SaasPriceministerAutosync successed;";
				}else{
					echo "\n new inster SaasPriceministerAutosync failed:".print_r($syncQueue->getErrors(),true);
				}
			}
		}
		
		$condition = [
			'is_active'=>1,
			'type'=>$type,
			'status'=>['P','C','F']
		];
		$hoursAge = date("Y-m-d H:i:s",strtotime('-6 hours'));	//6小时前
		
		$query = SaasPriceministerAutosync::find()->where($condition)
		// ->andwhere(" uid%3=$job_id ") // TODO 开启PM多进程时候做相应去注释
		;
		
		if($type=='A'){
			$query->andWhere(" last_time <'$hoursAge' ");
		}
		$userAccounts = $query->all();
		echo "\n get user pending sync accounts:".count($userAccounts);
		foreach ($userAccounts as $account){
			try{
				$account->status = 'S';
				$account->start_time = TimeUtil::getNow();
				if(!$account->save()){
					echo "\n lock uid:".$account->uid.",seller:".$account->sellerloginid." failed!error:".print_r($account->getErrors(),true) ;	
					continue;
				}
				
				$uid=$account->uid;
				$seller = $account->sellerloginid;
				
				echo "\n start to sync Order Item Status for uid=$uid seller=$seller... ";
				
				$PM = SaasPriceministerUser::find()->where(['uid'=>$uid,'username'=>$seller])->one();
				
				if (empty($uid)){
					echo  "queue id :".$account->id." uid:0";
					self::unLockQueue($account,'F','uid=0!');
					continue;
				}
				
				$needSyncItems = PriceministerSyncOrderItem::find()->where(['seller_id'=>$seller,'puid'=>$uid])->all();
				echo "\n try to sync ".count($needSyncItems)." item status...";
				
 
				
				if(!empty($PM->token) && $PM->order_retrieve_message!=='账号或token错误'){
					$token = $PM->token;
				}else{
					echo "\n queue id :".$account->id." account have not token or token not correct";
					self::unLockQueue($account,'F','account have not token or token not correct');
					continue;
				}
				
				
				
				$orderStatusUpdateList = [];//有item状态更新了的订单array，后面需要对其状态进行相应更改
				foreach ($needSyncItems as $item){
					echo "\n item id :".$item->item_id;
					$pm_api = new PriceministerOrderInterface();
					$pm_api->setStoreNamePwd($seller, $token);
					$api_rtn = $pm_api->GetItemInfos($item->item_id);
					
					if (!empty($api_rtn['success']) && !empty($api_rtn['iteminfo']) ){
						$thisResult = $api_rtn['iteminfo'];
					}else{
						//获取失败
						$item->status = "F";
						$item->err_message = (!empty($api_rtn['message'])?$api_rtn['message']:"");
						echo "\n api return error:".$item->err_message;
						$rtn['message'] .= "api return error:".$item->err_message;
						$item->update = TimeUtil::getNow();
						$item->save();
						continue;
					}
					
					if(is_object($thisResult)){
						$iteminfo =self::object_to_array($thisResult);
					}else{
						$iteminfo =$thisResult;
					}
					
					if(!empty($iteminfo['itemstatus'])){
						$thisStatus = $iteminfo['itemstatus'];
						/*
						$srcItem = PriceministerOrderDetail::find()->where(['itemid'=>$item->item_id])->orderBy("id")->One();
						if(empty($srcItem)){
							echo "\n src item record lost, skip it;";
							continue;
						}
						*/
						
						//记录上次同步时的get_item_infos_status
						$old_get_item_infos_status = $item->item_status;
						switch ($iteminfo['itemstatus']){
							case 'COMMITTED':
								$is_track = false;
								if(!empty($iteminfo['itemlog'])){
									foreach ($iteminfo['itemlog'] as $log){
										foreach ($log as $k=>$v){
											if($k=='itemlogcode' && $v=='TRACKING')
												$is_track = true;
										}
									}
								}
								if($is_track)
									$thisStatus = 'TRACKING';
								break;
							default:
								//$srcItem->itemstatus_of_get_item_infos = $iteminfo['itemstatus'];
								break;
						}
						/*
						if(!$srcItem->save()){
							echo "\n PriceministerOrderDetail update failed:".print_r($srcItem->getErrors(),true);
							continue;
						}
						*/
						//如果状态有改变，则将purchase_id记录下来备用
						if($old_get_item_infos_status!==$thisStatus){
							$odOrder = '';
							if(!isset($orderStatusUpdateList[$item->purchase_id]))
								$odOrder = OdOrder::find(['order_source'=>'priceminister','order_source_order_id'=>$item->purchase_id,'selleruserid'=>$seller])->one();
							if(!empty($odOrder)){
								$orderStatusUpdateList[$item->purchase_id] = $odOrder;
								//更新拉取到的状态都od_order_item_v2
								$updateItemData = [
									'order_source_order_item_id'=>$item->item_id,
									'platform_status'=>$thisStatus
								];
								$updateItem = OrderUpdateHelper::updateItem('priceminister', $odOrder->order_id, $updateItemData);
								if(empty($updateItem['ack'])){
									print_r($updateItem);
									continue;
								}
							}
							/*
							OdOrderItem::updateAll(['platform_status'=>$thisStatus],
								'source_item_id=:source_item_id and order_source_order_id=:order_id',
								[':source_item_id'=>$item->item_id,':order_id'=>$item->purchase_id]
							);
							*/
						}
						//如果item status为'CANCELLED','CLOSED'则删除item的同步,其他状态则update记录，等待下次同步
						if(in_array($thisStatus,['CANCELLED','CLOSED','REFUSED'])){
							echo "\n item was CLOSED or REFUSED, del from PriceministerSyncOrderItem!";
							$item->delete();
						}else{
							$item->status = 'C';
							$item->item_status = $thisStatus;
							$item->result = json_encode($iteminfo);
							$item->times = $item->times+1;
							$item->update = TimeUtil::getNow();
							$item->err_message = '';
							if($item->save()){
								echo "\n PriceministerSyncOrderItem update successed!";
							}else{
								echo "\n PriceministerSyncOrderItem update error:".print_r($item->getErrors(),true);
							}
						}
					}else {
						echo "\n itemstatus lost;";
						continue;
					}
				}//end of call api to get infos for each item 
				
				//update order status
				if(!empty($orderStatusUpdateList)){
					foreach ($orderStatusUpdateList as $order){
						//$order = OdOrder::find()->where(['order_source_order_id'=>$purchaseid,'selleruserid'=>$seller])->one();
						if(empty($order)){
							echo "\n order model not find;";
							continue;
						}
						
						$all_accpeted_or_refused = true;//是否全部已经接受或拒绝
						$all_cancelled = true;//是否全部已取消
						$all_closed = true;//是否全部已完成
						$closed_or_cancelled = false;
						$have_track = false;//是否有已经发货
						$have_claim = false;//是否有理赔
						$preorder = false;//预售
						
						$items = OdOrderItem::find()->where(['order_id'=>$order->order_id,'order_source_order_id'=>$order->order_source_order_id])->all();
						foreach ($items as $i){
							if($i->platform_status=='PENDING'){
								$preorder = true;
								continue;
							}
							if($i->platform_status=='REQUESTED' || $i->platform_status=='REMINDED'){
								$all_accpeted_or_refused = false;
								$all_cancelled = false;
								$all_closed = false;
								continue;
							}
							if($i->platform_status=='COMMITTED' || $i->platform_status=='ACCEPTED'){
								$all_cancelled = false;
								$all_closed = false;
								continue;
							}
							if($i->platform_status=='TRACKING'){
								$have_track = true;
								$all_cancelled = false;
								$all_closed = false;
								continue;
							}
							if($i->platform_status=='ON_HOLD'){
								$all_closed = false;
								$all_closed = false;
								$have_claim = true;
								continue;
							}
							if($i->platform_status=='CLOSED'){
								$all_cancelled = false;
								$closed_or_cancelled = true;
								continue;
							}
							if($i->platform_status=='CANCELLED'){
								$all_closed = false;
								$closed_or_cancelled = true;
								continue;
							}
						}
						
						$this_order_source_status = $order->order_source_status;
						$this_order_status = $order->order_status;
						$addi_info = empty($order->addi_info)?[]:json_decode($order->addi_info,true);
						if(empty($addi_info))
							$addi_info = [];
						//预售订单,暂时不会对预售订单做什么处理
						if($preorder){
							$addi_info['LB_order_status'] = '预售订单';
							$this_order_source_status = 'preorder';
						}
						//待全部接受或者取消
						if(!$all_accpeted_or_refused){
							$addi_info['LB_order_status'] = '待接受/取消';
							$this_order_source_status = 'new';
							//当订单状态在付款以前，更新成已付款
							if((int)$order->order_status < odorder::STATUS_PAY)
								$this_order_status = odorder::STATUS_PAY;
						}
						//'有申诉/理赔',最优先状态
						if(!$all_cancelled && !$all_closed && $have_claim){
							$addi_info['LB_order_status'] = '有申诉/理赔';
							$this_order_source_status = 'claim';
							//不做任何处理
							//$order->order_status = odorder::STATUS_CLAIM;
						}
						//'已发货';
						if(!$all_cancelled && !$all_closed && $have_track){
							$addi_info['LB_order_status'] = '已发货';
							$this_order_source_status = 'tracking';
							//当订单从已付款直接转换成完成(用户未在小老板做任何处理，通过其他途径完成了) 才自动更新状态
							if((int)$order->order_status <= odorder::STATUS_PAY && (!empty($order->sync_shipped_status) && $order->sync_shipped_status=='Y') )
								$this_order_status = odorder::STATUS_SHIPPED;
						}
						//'已接受/拒绝';
						if(!$all_cancelled && !$all_closed && !$have_track && !$have_claim){
							$addi_info['LB_order_status'] = '已接受/拒绝';
							$this_order_source_status = 'current';
							//$order->order_status = odorder::STATUS_PAY;
						}
						//'已取消';
						if($all_cancelled){
							$addi_info['LB_order_status'] = '已取消';
							$this_order_source_status = 'cancelled';
							//当订单从已付款直接转换成已取消时(用户未做任何处理)才自动更新状态
							if((int)$order->order_status == odorder::STATUS_PAY)
								$this_order_status = odorder::STATUS_CANCEL;
						}
						//'已完成';
						if($all_closed){
							$addi_info['LB_order_status'] = '已完成';
							$this_order_source_status = 'closed';
							//当订单从已付款直接转换成完成(用户未在小老板做任何处理，通过其他途径完成了) 才自动更新状态
							if((int)$order->order_status <= odorder::STATUS_PAY && (!empty($order->sync_shipped_status) && $order->sync_shipped_status=='Y') )
								$this_order_status = odorder::STATUS_SHIPPED;
						}
						
						$odNewAttrs = [
							'order_source_status'=>$this_order_source_status,
							'order_status'=>$this_order_status,
						];
						$orderUpdateResult = OrderUpdateHelper::updateOrder($order->order_id, $odNewAttrs,false,'system','AutoSyncOrderStatus','order');
						if(empty($orderUpdateResult['ack'])){
							echo "\n order:".$order->order_source_order_id." status update error:";
							echo "OrderUpdateHelper::updateOrder ".@$orderUpdateResult['message'];
							continue;
						}else{
							echo "\n order:".$order->order_source_order_id." status update success";
						}
						/*
						$order->addi_info = json_encode($addi_info);
						if($order->save()){
							echo "\n order:".$order->order_source_order_id." status update success";
							//OperationLogHelper::log('order', $order->order_id,'状态更新','同步状态为：'.$addi_info['LB_order_status'], 'system');
						}else{
							echo "\n order model update error:".print_r($order->getErrors(),true);
						}
						*/
					}//end of each need update order
				}else{
					echo "\n non order need to update status";
				}
				
				$account->status = 'C';
				$account->type = 'A';//手动同步后也转为自动同步
				$time = TimeUtil::getNow();
				$account->end_time = $time;
				$account->last_time = $time;
				$account->update_time = $time;
				$account->message = $rtn['message'];
				if(!$account->save()){
					echo "\n lock uid:".$account->uid.",seller:".$account->sellerloginid." failed!error:".print_r($account->getErrors(),true) ;
					continue;
				}
			}catch (\Exception $e) {
				$rtn['success'] = false;
				$rtn['message'] = $e->getMessage();
				self::unLockQueue($account,'F',$e->getMessage());
			}
		}//end for each account
		
		return $rtn;
	}
	
	/*
	 * 用户手动触发同步order item 状态，将后台队列的状态改成U，提高优先级
	 */
	public static function userSyncOrderItemStatus($uid,$seller_id=''){
		$rtn['success'] = true;
		$rtn['message'] = '';
		try{
			$query = SaasPriceministerUser::find()->select("site_id")->where(['uid'=>$uid]);
			if(!empty($seller_id))
				$query->andWhere(['username'=>$seller_id]);
			$accounts = $query->asArray()->all();
			$pm_sites = [];
			foreach ($accounts as $a){
				$pm_sites[] = $a['site_id'];
			}
			
			if(empty($seller_id))
				$queues_query = SaasPriceministerAutosync::find()->where(['uid'=>$uid]);
			else 
				$queues_query = SaasPriceministerAutosync::find()->where(['uid'=>$uid,'sellerloginid'=>$seller_id]);
			if(!empty($pm_sites))
				$queues_query->andWhere(['pm_uid'=>$pm_sites]);
			
			$queues = $queues_query->all();
			
			if(empty($queues)){
				if(empty($seller_id))
					return ['success'=>false,'message'=>'您还没有任何PM账号处于同步队列中。'];
				else 
					return ['success'=>false,'message'=>'操作失败:PM账号 '.$seller_id.' 还未处于同步队列中。'];
			}
			
			foreach ($queues as $q){
				if($q->status=='S'){
					$rtn['success'] = false;
					$rtn['message'] .= 'PM账号: '.$q->sellerloginid.' 正在同步中，不需要重复同步。';
					continue;
				}
				$last_time = strtotime($q->last_time);
				if($last_time > time()-1800 ){
					$rtn['success'] = false;
					$rtn['message'] .= 'PM账号: '.$q->sellerloginid.' 上次同步时间到现在还不足半小时，请不要过于频繁同步。上次同步时间为:'.$q->last_time.'。';
					continue;
				}
				if($q->status=='F' && !empty($q->message)){
					$rtn['success'] = false;
					$rtn['message'] .= 'PM账号: '.$q->sellerloginid.' 上次同步出现错误，错误信息:'.$q->message.'，请先解决该问题。';
					continue;
				}
				if(!$q->is_active){
					$rtn['success'] = false;
					$rtn['message'] .= 'PM账号: '.$q->sellerloginid.' 未开启订单商品同步。';
					continue;
				}
				$nowTime = TimeUtil::getNow();
				$q->status = 'P';
				$q->type = 'U';
				$q->last_time = $nowTime;
				$q->message = '';
				$q->times = 0;
				$q->update_time = $nowTime;
				$q->order_item = 0;
				if(!$q->save()){
					$rtn['success'] = false;
					$rtn['message'] .= 'PM账号: '.$q->sellerloginid.' 队列信息更新失败。';
					SysLogHelper::SysLog_Create('Order', __CLASS__, __FUNCTION__, 'error', print_r($q->getErrors(),true));
				}
			}
			return $rtn;
		}catch (\Exception $e) {
			$rtn['success'] = false;
			$rtn['message'] = '后台处理出现报错，请联系客服。';
			SysLogHelper::SysLog_Create('Order', __CLASS__, __FUNCTION__, 'error', $e->getMessage());
			return $rtn;
		}
	}
	
	private static function object_to_array($obj){
		if(is_array($obj)){
			return $obj;
		}
		if(is_object($obj)){
			$_arr = get_object_vars($obj);
		}else{
			$_arr = $obj;
		}
		$arr = [];
		foreach ($_arr as $key=>$val){
			$val=(is_array($val)) || is_object($val)?self::object_to_array($val):$val;
			$arr[$key] = $val;
		}
		return $arr;
	}
	
	
	private static function unLockQueue($queue,$status='P',$message=''){
		
		$queue->status = $status;
		$queue->message = $message;
		$time = TimeUtil::getNow();
		$queue->end_time = $time;
		$queue->last_time = $time;
		if(!$queue->save())
			return ['success'=>false,'message'=>print_r($queue->getErrors(),true)];
		else 
			return ['success'=>true,'message'=>''];
		
		
	}
	
	private static function formatterResult($data ){
		
		if (isset($data->itemid) && !is_null($data->itemid))
			$result['itemid'] = (String)$data->itemid;
		//商品属性信息
		if (isset($data->date) && !is_null($data->date))
			$result['date'] = (String)$data->date;
		if (isset($data->product->url) && !is_null($data->product->url))
			$result['product_url'] = (String)$data->product->url;
		if (isset($data->product->headline) && !is_null($data->product->headline))
			$result['headline'] = (String)$data->product->headline;
		if (isset($data->product->topic) && !is_null($data->product->topic))
			$result['topic'] = (String)$data->product->topic;
		if (isset($data->product->caption) && !is_null($data->product->caption))
			$result['caption'] = (String)$data->product->caption;
		if (isset($data->product->image->url) && !is_null($data->product->image->url))
			$result['photo_primary'] = (String)$data->product->image->url;
		if (isset($data->comment) && !is_null($data->comment))
			$result['comment'] = (String)$data->comment;
		if (isset($data->gallery->image) && !is_null($data->gallery->image) && is_array($data->gallery->image))
			$result['photo_other'] = $data->gallery->image;
		
		//item状态
		if (isset($data->itemstatus) && !is_null($data->itemstatus))
			$result['itemstatus'] = (String)$data->itemstatus;
		if (isset($data->itemstate) && !is_null($data->itemstate))
			$result['itemstate'] = (String)$data->itemstate;
	
		return $result;
	}//end of formatterResult
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 自动 生成 top menu
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param     $keyword				关键字， 来控制选中的样式  '' , 100 , 200 , 300 , 400 , 500
	 +---------------------------------------------------------------------------------------------
	 * @return						string  html 代码
	 *
	 +---------------------------------------------------------------------------------------------
	 * log			name		date			note
	 * @author		lzhil		2016/05/27		初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function getPriceministerOmsNav($key_word){
		$order_nav_list = [
		'同步订单'=>'/order/priceminister-order/order-sync-info' ,
		'已付款'=>'/order/priceminister-order/list?order_status=200&pay_order_type=pending' ,
		'发货中'=>'/order/priceminister-order/list?order_status=300' ,
		'已完成'=>'/order/priceminister-order/list?order_status=500' ,
		];
	
		$order_nav_active_list = [
		'同步订单'=>'' ,
		'已付款'=>'200' ,
		'发货中'=>'300' ,
		'已完成'=>'500' ,
		];
	
		$NavHtmlStr = '<ul class="main-tab">';
	
		$mappingOrderNav = array_flip($order_nav_active_list);
		foreach($order_nav_list as $label=>$thisUrl){
			$NavActive='';
	
			//$_REQUEST['order_status']
			if (isset($key_word)){
				if (empty($key_word) &&  !empty($mappingOrderNav[$key_word]) &&$mappingOrderNav[$key_word] == $label && \yii::$app->controller->action->id == 'order-sync-info'){
					$NavActive = " active ";
				}else
				if (!empty($key_word) &&  !empty($mappingOrderNav[$key_word]) &&$mappingOrderNav[$key_word] == $label && \yii::$app->controller->action->id != 'order-sync-info' ) {
					$NavActive = " active ";
				}
			}else{
				//$NavActive = " active ";
			}
			$NavHtmlStr .= '<li class="'.$NavActive.'"><a href="'.$thisUrl.'">'.TranslateHelper::t($label).'</a></li>';
			/* $NavHtmlStr .= '<div class="pull-left col-md-2">
			 <div class="rectangle-content'.$NavActive.'"><p class="p-rectangle-content'.$NavActive.'"><a href="'.$thisUrl.'">'.TranslateHelper::t($label).'</a></p></div>
			<div class="triangle-right'.$NavActive.'"></div>
			</div>';
			*/
		}
		$NavHtmlStr.='</ul>';
	
	
		return $NavHtmlStr;
	
	}//end of getOrderNav
	

	/*
	 * 查询PM账号的同步进行情况
	* @param int 		$puid
	* @param string 	$seller
	* @return	array	[sync_status,sync_type,sync_info]
	* @author	lzhl	2016/8/23	初始化
	*/
	public static function checkSyncJobState($puid,$seller=[]){
		if(empty($seller))
			$saasAccount = SaasPriceministerUser::find()->where(['uid'=>$puid,'is_active'=>1])->all();
		else
			$saasAccount = SaasPriceministerUser::find()->where(['uid'=>$puid,'username'=>$seller,'is_active'=>1])->all();
	
		$sync_info = [];
		foreach ($saasAccount as $account){
			$tmp = [];
			$tmp['username'] = $account->username;
			if(empty($account)){
				$tmp['sync_status'] = '';
				$tmp['sync_type'] = 'N';
				$tmp['sync_info'] = [];
			}else{
				$tmp['sync_status'] = $account->sync_status;
				$tmp['sync_type'] = $account->sync_type;
				$addi_info = json_decode($account->sync_info,true);
				$tmp['sync_info'] = empty($addi_info)?[]:$addi_info;
			}
			$sync_info[$account->site_id] = $tmp;
		}
		return $sync_info;
	}
	
	/*
	 * 将PM账号标记为正在同步
	* @param	model		$account
	* @param	string		$sync_type
	* @return	array[success,message]
	* @author	lzhl	2016/8/23	初始化
	*/
	public static function markSaasAccountOrderSynching($account,$sync_type){
		$addi_info = json_decode($account->sync_info,true);
		if(empty($addi_info)) $addi_info = [];
		$account->sync_type = $sync_type;
		$account->sync_status = 'R';
		$addi_info['start_time'] = TimeUtil::getNow();
		$addi_info['end_time'] = '';
		$account->sync_info = json_encode($addi_info);
	
		if(!$account->save()){
			return ['success'=>false,'message'=>print_r($account->errors)];
		}else{
			return ['success'=>true,'message'=>''];
		}
	}
	
	/*
	 * 同步完成后更改同步状态信息
	* @param	model		$account
	* @param	string		$status		'C' or 'F'
	* @param	int			$sync_order_count
	* @param	string		$error_log
	* @return	array[success,message]
	* @author	lzhl	2016/8/23	初始化
	*/
	public static function markSaasAccountOrderSyncFinished($account,$status,$sync_order_count,$error_log=''){
		$addi_info = json_decode($account->sync_info,true);
		if(empty($addi_info)) $addi_info = [];
	
		$account->sync_status = $status;
		$addi_info['end_time'] = TimeUtil::getNow();
		$addi_info['order_count'] = $sync_order_count;
		$addi_info['error_log'] = $error_log;
		$account->sync_info = json_encode($addi_info);
	
		if(!$account->save()){
			return ['success'=>false,'message'=>print_r($account->errors)];
		}else{
			return ['success'=>true,'message'=>''];
		}
	}
	
	/**
	 * 出现故障导致拉单失败，故障恢复后，要重置失败开始时间后所有拉单失败的用户的 拉单状态 和 last success time，使得拉单job帮他们重新拉取
	 * @param	dateTime/stamp	$time		//故障开始时间
	 * @param	array			$puids		//可选，指定修复的puid
	 * @return	array('success'=>boolean ,'msg'=>string)
	 * @author	lzhl	2018-05-03
	 */
	public static function resetSyncOrderErr($time,$puids=[]){
		if(!is_numeric($time)){
			$stampTime = strtotime($time);
			$dateTime = $time;
		}else{
			$stampTime = $time;
			$dateTime = date("Y-m-d H:i:s",$time);
		}
		$toDateTime = date("Y-m-d H:i:s", ($stampTime-3600*12) );//重新拉取的目标时间为故障前的12小时
	
	
		if(!empty($puids)){
			SaasPriceministerUser::updateAll(["sync_status"=>"F","last_order_retrieve_time"=>$toDateTime,"last_order_success_retrieve_time"=>$toDateTime]," `is_active`=1 and (`sync_status`='R' or `sync_status`='P') and `last_order_retrieve_time`>'$toDateTime' and uid in (".implode(',', $puids).") ");
		}else{
			SaasPriceministerUser::updateAll(["sync_status"=>"F","last_order_retrieve_time"=>$toDateTime,"last_order_success_retrieve_time"=>$toDateTime]," `is_active`=1 and (`sync_status`='R' or `sync_status`='P') and `last_order_retrieve_time`>'$toDateTime' ");
		}
	}
}
?>