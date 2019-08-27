<?php
namespace console\helpers;

use eagle\modules\platform\helpers\WishAccountsHelper;
use \Yii;
use eagle\models\SaasAliexpressAutosync;
use common\api\aliexpressinterface\AliexpressInterface_Auth;
use common\api\aliexpressinterface\AliexpressInterface_Api;
use eagle\models\QueueAliexpressGetorder;
use eagle\models\QueueAliexpressGetorder2;
use eagle\models\QueueAliexpressGetfinishorder;
use common\api\aliexpressinterface\AliexpressInterface_Helper;
use eagle\modules\util\helpers\ConfigHelper;
use eagle\modules\util\helpers\TimeUtil;
use eagle\models\SaasAliexpressUser;
use eagle\models\listing\AliexpressListing;
use eagle\modules\app\apihelpers\AppApiHelper;
use eagle\models\CheckSync;
use eagle\models\QueueAliexpressPraise;
use eagle\models\QueueAliexpressPraiseInfo;
use eagle\modules\util\helpers\UserLastActionTimeHelper;
use eagle\modules\util\models\UserBackgroundJobControll;
use eagle\models\AliexpressListingDetail;
use common\helpers\Helper_Array;
use eagle\models\AliexpressCategory;
use Qiniu\json_decode;
use eagle\modules\util\helpers\ExcelHelper;
use common\helpers\Helper_Currency;

/**
 +------------------------------------------------------------------------------
 * Aliexpress 数据同步类
 +------------------------------------------------------------------------------
 */
class AliexpressTestHelper {
	public static $cronJobId=0;
	private static $aliexpressGetOrderListVersion = null;
	private static $version = null;


    protected static $active_users;

    protected static function isActiveUser($uid) {
		return true;
        // if(empty(self::$active_users)) {
            // self::$active_users = \eagle\modules\util\helpers\UserLastActionTimeHelper::getPuidArrByInterval(72);
        // }

        // if(in_array($uid, self::$active_users)) {
            // return true;
        // }

        // return false;
    }

	/**
	 * @return the $cronJobId
	 */
	public static function getCronJobId() {
		return self::$cronJobId;
	}
	
	/**
	 * @param number $cronJobId
	 */
	public static function setCronJobId($cronJobId) {
		self::$cronJobId = $cronJobId;
	}
	
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
	
	
	// 从getorder2表检查所有需要半个小时更新一次的订单数量
	public static function getAllNeedCheckOrderByHalf(){
		// 同步订单
		$connection=Yii::$app->db_queue;
		$now = time();
		$hasGotRecord=false;
		
		$active_users = \eagle\modules\util\helpers\UserLastActionTimeHelper::getPuidArrByInterval(72);
		$uidStr="";
		foreach($active_users as $uid){
			$uidStr=$uidStr.$uid.",";			
		}
		$uidStr="(".substr($uidStr,0,-1).")";
		
		$status = array('PLACE_ORDER_SUCCESS','IN_CANCEL','WAIT_SELLER_SEND_GOODS','IN_ISSUE','IN_FROZEN','WAIT_SELLER_EXAMINE_MONEY','RISK_CONTROL');
		
		//查询队列里的非新订单
		$sql = "select count(id) from  queue_aliexpress_getorder2 where order_status in ('PLACE_ORDER_SUCCESS','IN_CANCEL','WAIT_SELLER_SEND_GOODS','IN_ISSUE','IN_FROZEN','WAIT_SELLER_EXAMINE_MONEY','RISK_CONTROL') and uid in ".$uidStr;
		
		$number=$connection->createCommand($sql)->queryScalar();
		echo "number:$number \n";
		
		
		//$dataReader = $connection->createCommand($sql)->query();
		
	}
	
	
	
	
	
	
	
	
	

    /**
     * 同步Aliexpress订单120天
     * @author million 2015-04-03
     * 88028624@qq.com
     */
    static function getOrderListByDay120(){
        //Step 0, 检查当前本job版本，如果和自己的版本不一样，就自动exit吧
        $currentAliexpressGetOrderListVersion = ConfigHelper::getGlobalConfig("Order/aliexpressGetOrderListVersion",'NO_CACHE');
        if(empty($currentAliexpressGetOrderListVersion)) {
            $currentAliexpressGetOrderListVersion = 0;
        }
        //如果自己还没有定义，去使用global config来初始化自己
        if (empty(self::$aliexpressGetOrderListVersion)) {
            self::$aliexpressGetOrderListVersion = $currentAliexpressGetOrderListVersion;
        }
        //如果自己的version不同于全局要求的version，自己退出，从新起job来使用新代码
        if (self::$aliexpressGetOrderListVersion <> $currentAliexpressGetOrderListVersion){
            exit("Version new $currentAliexpressGetOrderListVersion , this job ver ".self::$aliexpressGetOrderListVersion." exits for using new version $currentAliexpressGetOrderListVersion.");
        }

        self::$cronJobId++;
        echo date('Y-m-d H:i:s').' day120 script start '.self::$cronJobId.PHP_EOL;
        $connection=Yii::$app->db;
        $hasGotRecord = false;
        $now = time();

        $dataReader = $connection->createCommand('select `id` from  `saas_aliexpress_autosync` where `is_active` = 1 AND `status` in (0,3) AND `times` < 10 AND `type`="day120" order by `last_time` ASC limit 5')->query();
        while( false !== ($row=$dataReader->read()) ) {
            //1. 先判断是否可以正常抢到该记录
            $affectRows = $connection->createCommand("update saas_aliexpress_autosync set status=1 where id =". $row['id']." and status<>1 ")->execute();
            if ($affectRows <= 0) {
                continue; //抢不到
            }

            //2. 抢到记录
            $hasGotRecord=true;
            $SAA_obj = SaasAliexpressAutosync::findOne($row['id']);
            if(!$SAA_obj) {
                echo 'exception'.$row['id'].PHP_EOL;
                continue;
            }

            // 检查授权是否过期或者是否授权,返回true，false
            if (!AliexpressInterface_Auth::checkToken( $SAA_obj->sellerloginid )) {
                $SAA_obj->message = $SAA_obj->sellerloginid . ' token 过期';
                $SAA_obj->status = 3;
                $SAA_obj->times += 1;
                $SAA_obj->last_time = $now;
                $SAA_obj->update_time = $now;
                $bool = $SAA_obj->save (false);
                if(!$bool){
                    echo __FUNCTION__ . "STEP 1 : " . var_export($SAA_obj->errors,true);
                }
                continue;
            }

            $api = new AliexpressInterface_Api ();
            $access_token = $api->getAccessToken ( $SAA_obj->sellerloginid );
            //获取访问token失败
            if ($access_token === false){
                $SAA_obj->message = $SAA_obj->sellerloginid . ' token 异常';
                $SAA_obj->status = 3;
                $SAA_obj->times += 1;
                $SAA_obj->last_time = $now;
                $SAA_obj->update_time = $now;
                $bool = $SAA_obj->save (false);
                if(!$bool) {
                    echo __FUNCTION__ . "STEP 2 : " . var_export($SAA_obj->errors, true);
                }
                continue;
            }

            $api->access_token = $access_token;
            $page = 1;
            $pageSize = 50;
            // 是否全部同步完成
            $success = true;
            $start_time = $SAA_obj->binding_time-(86400*120);
            $format_start_time = self::getLaFormatTime ("m/d/Y H:i:s", $start_time );
            $end_time = $SAA_obj->binding_time;
            $format_end_time = self::getLaFormatTime ("m/d/Y H:i:s", $end_time );
            do {
                // 接口传入参数
                $param = array (
                        'page' => $page,
                        'pageSize' => $pageSize,
                );
                ###################################################
                $param['createDateStart'] = $format_start_time;
                $param['createDateEnd'] = $format_end_time;
                #######################################################
                // 调用接口获取订单列表
                $result = $api->findOrderListSimpleQuery($param);
                // 判断是否有订单
                if (isset ( $result ['totalItem'] )) {
                    if ($result ['totalItem'] > 0) {
                        // 保存数据到同步订单详情队列
                        foreach ( $result ['orderList'] as $one ) {
                            // 订单产生时间
                            $gmtCreate = AliexpressInterface_Helper::transLaStrTimetoTimestamp($one ['gmtCreate']);
                            $QAG_obj = QueueAliexpressGetorder::findOne(['orderid'=>$one ['orderId']]);
                            if (isset ( $QAG_obj )) {
                                $QAG_obj->type = 3;
                                $QAG_obj->order_status = $one['orderStatus'];
                                $QAG_obj->order_info = json_encode ( $one );
                                $QAG_obj->update_time = $now;
                                $QAG_obj->last_time = $now;
                                $bool = $QAG_obj->save (false);
                                if(!$bool) {
                                    echo __FUNCTION__ . "STEP 3 : " . var_export($QAG_obj->errors, true);
                                }
                            } else {
                                $QAG_obj = new QueueAliexpressGetorder ();
                                $QAG_obj->uid = $SAA_obj->uid;
                                $QAG_obj->sellerloginid = $SAA_obj->sellerloginid;
                                $QAG_obj->aliexpress_uid = $SAA_obj->aliexpress_uid;
                                $QAG_obj->status = 0;
                                $QAG_obj->type = 3;
                                $QAG_obj->order_status = $one['orderStatus'];
                                $QAG_obj->orderid = $one ['orderId'];
                                $QAG_obj->times = 0;
                                $QAG_obj->order_info = json_encode ( $one );
                                $QAG_obj->last_time = 0;
                                $QAG_obj->gmtcreate = $gmtCreate;
                                $QAG_obj->create_time = $now;
                                $QAG_obj->update_time = $now;
                                $bool = $QAG_obj->save (false);
                                if(!$bool) {
                                    echo __FUNCTION__ . "STEP 4 : " . var_export($QAG_obj->errors, true);
                                }
                            }
                        }
                    }
                } else {
                    $success = false;
                }

                $page ++;
                $total = isset($result ['totalItem'])?$result ['totalItem']:0;
                $p = ceil($total/50);
            } while ( $page <= $p );
            // 是否全部同步成功
            if ($success) {
                $SAA_obj->start_time = $start_time;
                $SAA_obj->end_time = $end_time;
                $SAA_obj->last_time = $now;
                $SAA_obj->status = 4;
                $SAA_obj->times = 0;
                $bool = $SAA_obj->save (false);
                if(!$bool) {
                    echo __FUNCTION__ . "STEP 5 : " . var_export($SAA_obj->errors, true);
                }
            } else {
                $SAA_obj->message = isset($result ['error_message'])?$result ['error_message']:'接口返回结果错误'.print_r($result,true);
                $SAA_obj->last_time = $now;
                $SAA_obj->status = 3;
                $SAA_obj->times += 1;
                $bool = $SAA_obj->save (false);
                if(!$bool) {
                    echo __FUNCTION__ . "STEP 6 : " . var_export($SAA_obj->errors, true);
                }
            }
        }
        return $hasGotRecord;
    }
	
	/**
	 * 同步Aliexpress订单所有已完成
	 * @author million 2015-04-03
	 * 88028624@qq.com
	 */
	static function getOrderListByFinish(){
		//Step 0, 检查当前本job版本，如果和自己的版本不一样，就自动exit吧
		$currentAliexpressGetOrderListVersion = ConfigHelper::getGlobalConfig("Order/aliexpressGetOrderListVersion",'NO_CACHE');
		if (empty($currentAliexpressGetOrderListVersion))
			$currentAliexpressGetOrderListVersion = 'v1';
		
		//如果自己还没有定义，去使用global config来初始化自己
		if (empty(self::$aliexpressGetOrderListVersion))
			self::$aliexpressGetOrderListVersion = $currentAliexpressGetOrderListVersion;
			
		//如果自己的version不同于全局要求的version，自己退出，从新起job来使用新代码
		if (self::$aliexpressGetOrderListVersion <> $currentAliexpressGetOrderListVersion){
			exit("Version new $currentAliexpressGetOrderListVersion , this job ver ".self::$aliexpressGetOrderListVersion." exits for using new version $currentAliexpressGetOrderListVersion.");
		}
		$backgroundJobId=self::getCronJobId();
		$connection=Yii::$app->db;
		#########################
		$type = 'finish';
		$hasGotRecord=false;
		$command=$connection->createCommand('select `id` from  `saas_aliexpress_autosync` where `is_active` = 1 AND `status` in (0,2,3) AND `times` < 10 AND `type`="'.$type.'" order by `last_time` ASC limit 5');
		#################################
		$dataReader=$command->query();
		while(($row=$dataReader->read())!==false) {
			//echo '<pre>';print_r($row);exit; //8614
			//1. 先判断是否可以正常抢到该记录
			$command = $connection->createCommand("update saas_aliexpress_autosync set status=1 where id =". $row['id']." and status<>1 ") ;
			$affectRows = $command->execute();
			if ($affectRows <= 0)	continue; //抢不到
			\Yii::info("aliexress_get_order_list_by_finish gotit jobid=$backgroundJobId start");
			//2. 抢到记录，设置同步需要的参数
			$hasGotRecord=true;
			$SAA_obj = SaasAliexpressAutosync::findOne($row['id']);
			// 检查授权是否过期或者是否授权,返回true，false
			$a = AliexpressInterface_Auth::checkToken ( $SAA_obj->sellerloginid );
			if ($a) {
				echo $SAA_obj->sellerloginid."\n";
				$api = new AliexpressInterface_Api ();
				$access_token = $api->getAccessToken ( $SAA_obj->sellerloginid );
				//获取访问token失败
				if ($access_token === false){
					echo $SAA_obj->sellerloginid . 'not getting access token!' . "\n";
					\Yii::info($SAA_obj->sellerloginid . 'not getting access token!' . "\n");
					$SAA_obj->message = $SAA_obj->sellerloginid . ' not getting access token!';
					$SAA_obj->status = 3;
					$SAA_obj->times += 1;
					$SAA_obj->last_time = time ();
					$SAA_obj->update_time = time ();
					$bool = $SAA_obj->save (false);
                    if(!$bool) {
                        echo __FUNCTION__ . "STEP 1 : " . var_export($SAA_obj->errors, true);
                    }
					continue;
				}
				$api->access_token = $access_token;
				$page = 1;
				$pageSize = 50;
				// 是否全部同步完成
				$success = true;
				$exit = false;
				#####################################
				if ($SAA_obj->end_time==0){
					$start_time = $SAA_obj->binding_time-(86400*30);
					$end_time =$SAA_obj->binding_time;
					if ($SAA_obj->start_time>$start_time){
						$start_time = $SAA_obj->start_time;
					}
				}else{
					$start_time = $SAA_obj->end_time-(86400*30);
					$end_time =$SAA_obj->end_time;
					//当重新绑定或者重新同步的账号，不用同步所有的订单数据
					if ($SAA_obj->start_time>$start_time){
						$start_time = $SAA_obj->start_time;
					}
				}
	
				########################################
				do {
				// 接口传入参数
					$param = array (
					'page' => (int)$page,
							'pageSize' => $pageSize,
							);
							###################################################
// 					$param['createDateStart']=date ( "m/d/Y H:i:s",$start_time );
// 					$param['createDateEnd']=date ( "m/d/Y H:i:s", $end_time );
					$param['createDateStart'] = self::getLaFormatTime ("m/d/Y H:i:s", $start_time );
					$param['createDateEnd'] = self::getLaFormatTime ( "m/d/Y H:i:s", $end_time );
					#######################################################
					$param['orderStatus']='FINISH';
					####################################################
					// 调用接口获取订单列表
					//$result = $api->findOrderListQuery ( $param );//old
					$result = $api->findOrderListSimpleQuery($param);
					//echo print_r ( $result, 1 );exit;
					// 判断是否有订单
					if (isset ( $result ['totalItem'] )) {
					echo $result ['totalItem']."\n";
					if ($result ['totalItem'] > 0) {
					// 保存数据到同步订单详情队列
					foreach ( $result ['orderList'] as $one ) {
					// 订单产生时间
// 							$gmtCreate_str = substr ( $one ['gmtCreate'], 0, 14 );
// 							$gmtCreate = strtotime ( $gmtCreate_str );
							$gmtCreate = AliexpressInterface_Helper::transLaStrTimetoTimestamp($one ['gmtCreate']);
							//原先所有类型都在queue_aliexpress_getorder表，现在单独把finish独立出来，放表QueueAliexpressGetfinishorder
//							$QAG_obj = QueueAliexpressGetorder::findOne(['orderid'=>$one ['orderId']]);
//							if (isset ( $QAG_obj )) {
//								$QAG_obj->order_status = $one['orderStatus'];
//									$QAG_obj->order_info = json_encode ( $one );
//									$QAG_obj->update_time = time ();
//									$QAG_obj->save ();
//								} else {
//								$QAG_obj = new QueueAliexpressGetorder ();
//									$QAG_obj->uid = $SAA_obj->uid;
//									$QAG_obj->sellerloginid = $SAA_obj->sellerloginid;
//									$QAG_obj->aliexpress_uid = $SAA_obj->aliexpress_uid;
//									$QAG_obj->status = 0;
//									$QAG_obj->type = 2;
//									$QAG_obj->order_status = $one['orderStatus'];
//									$QAG_obj->orderid = $one ['orderId'];
//									$QAG_obj->times = 0;
//									$QAG_obj->order_info = json_encode ( $one );
//									$QAG_obj->last_time = 0;
//									$QAG_obj->gmtcreate = $gmtCreate;
//									$QAG_obj->create_time = time ();
//									$QAG_obj->update_time = time ();
//									$QAG_obj->save ();
//								}
                            //new 把finish的订单单独存在一张表里面
                            $QAG_finish = QueueAliexpressGetfinishorder::findOne(['orderid'=>$one ['orderId']]);
                            if (isset ( $QAG_finish )) {
								$QAG_finish->order_status = $one['orderStatus'];
								$QAG_finish->order_info = json_encode ( $one );
								$QAG_finish->update_time = time ();
								$bool = $QAG_finish->save (false);
                                if(!$bool) {
                                    echo __FUNCTION__ . "STEP 2 : " . var_export($QAG_finish->errors, true);
                                }
							} else {
							    $QAG_finish = new QueueAliexpressGetfinishorder ();
							    $QAG_finish->uid = $SAA_obj->uid;
							    $QAG_finish->sellerloginid = $SAA_obj->sellerloginid;
							    $QAG_finish->aliexpress_uid = $SAA_obj->aliexpress_uid;
								$QAG_finish->status = 0;
								$QAG_finish->type = 2;
								$QAG_finish->order_status = $one['orderStatus'];
								$QAG_finish->orderid = $one ['orderId'];
								$QAG_finish->times = 0;
								$QAG_finish->order_info = json_encode ( $one );
								$QAG_finish->last_time = 0;
								$QAG_finish->gmtcreate = $gmtCreate;
								$QAG_finish->create_time = time();
								$QAG_finish->update_time = time();
								$QAG_finish->next_time = time();
								$bool = $QAG_finish->save (false);
                                if(!$bool) {
                                    echo __FUNCTION__ . "STEP 3 : " . var_export($QAG_finish->errors, true);
                                }
							}
							}
						}else{
							$exit = true;//当已完成订单数量为0时说明已完成订单已经同步完毕
						}
					} else {
						$success = false;
					}
	
					$page ++;
					$total = isset($result ['totalItem'])?$result ['totalItem']:0;
					$p = ceil($total/50);
				} while ( $page <= $p );
				// 是否全部同步成功
				if ($success) {
                    $SAA_obj->end_time = $start_time;
                    $SAA_obj->last_time = time();
                    if ($exit) {
                        $SAA_obj->status = 4;//已完成订单全部同步
                    } else {
                        $SAA_obj->status = 2;
                    }
                    $SAA_obj->times = 0;
                    $bool = $SAA_obj->save(false);
                    if (!$bool) {
                        echo __FUNCTION__ . "STEP 4 : " . var_export($SAA_obj->errors, true);
                    }
				} else {
				$SAA_obj->message = isset($result ['error_message'])?$result ['error_message']:'接口返回结果错误'.print_r($result,true);
				$SAA_obj->last_time = time();
				$SAA_obj->status = 3;
				$SAA_obj->times += 1;
				$bool = $SAA_obj->save (false);
                    if (!$bool) {
                        echo __FUNCTION__ . "STEP 5 : " . var_export($SAA_obj->errors, true);
                    }
				}
			} else {
					echo $SAA_obj->sellerloginid . ' Unauthorized or expired!' . "\n";
					$SAA_obj->message = $SAA_obj->sellerloginid . ' Unauthorized or expired!';
					$SAA_obj->last_time = time();
					$SAA_obj->status = 3;
					$SAA_obj->times += 1;
					$bool = $SAA_obj->save (false);
                if (!$bool) {
                    echo __FUNCTION__ . "STEP 6 : " . var_export($SAA_obj->errors, true);
                }
			}
			\Yii::info("aliexress_get_order_list_by_finish gotit jobid=$backgroundJobId end");
		}
			return $hasGotRecord;
	}


    /**
     * 同步Aliexpress新产生的订单时间从绑定，重新绑定或者重新开启时间开始
     * @author million 2015-04-03
     * 88028624@qq.com
     */
    public static function getOrderListByTime() {
        //Step 0, 检查当前本job版本，如果和自己的版本不一样，就自动exit吧
        $currentAliexpressGetOrderListVersion = ConfigHelper::getGlobalConfig("Order/aliexpressGetOrderListVersion",'NO_CACHE');
        if(empty($currentAliexpressGetOrderListVersion)) {
            $currentAliexpressGetOrderListVersion = 0;
        }
        //如果自己还没有定义，去使用global config来初始化自己
        if (empty(self::$aliexpressGetOrderListVersion)) {
            self::$aliexpressGetOrderListVersion = $currentAliexpressGetOrderListVersion;
        }
        //如果自己的version不同于全局要求的version，自己退出，从新起job来使用新代码
        if (self::$aliexpressGetOrderListVersion <> $currentAliexpressGetOrderListVersion){
            exit("Version new $currentAliexpressGetOrderListVersion , this job ver ".self::$aliexpressGetOrderListVersion." exits for using new version $currentAliexpressGetOrderListVersion.");
        }

        self::$cronJobId++;
        echo date('Y-m-d H:i:s').' time script start '.self::$cronJobId.PHP_EOL;
        $connection=Yii::$app->db;

        //30分钟之前
        //$t = time()-1800;
        $nowTime=time();
        $hasGotRecord = false;

        //查询同步控制表所有time队列，最后同步时间为半小时前数据，倒序取前五条
        $sql = "select `id` from  `saas_aliexpress_autosync` where `is_active` = 1 AND `status` <>1 AND `times` < 10 AND `type`='time' AND next_time < {$nowTime}  order by `next_time` ASC limit 5 ";
        $dataReader = $connection->createCommand($sql)->query();
        echo date('Y-m-d H:i:s').' select count '.$dataReader->count().PHP_EOL;
        while( false !== ($row = $dataReader->read()) ) {
            //1. 先判断是否可以正常抢到该记录
            $nowTime=time();
            $affectRows = $connection->createCommand("update saas_aliexpress_autosync set status=1,last_time={$nowTime} where id ={$row['id']} and status<>1 ")->execute() ;
            if ($affectRows <= 0) {
                continue; //当前这条抢不到
            }

            //2. 抢到记录，设置同步需要的参数
            $hasGotRecord = true;
            $SAA_obj = SaasAliexpressAutosync::findOne($row['id']);
            if(!$SAA_obj) {
                echo date('Y-m-d H:i:s').' Exception '.$row['id'].PHP_EOL;
                continue;
            }
            $puid=$SAA_obj->uid;
            $sellerloginid=$SAA_obj->sellerloginid;
            $timeMS1=TimeUtil::getCurrentTimestampMS();
            echo date('Y-m-d H:i:s')." step1 puid=$puid,sellerloginid=$sellerloginid".PHP_EOL;
 
            echo date('Y-m-d H:i:s')." step2 puid=$puid,sellerloginid=$sellerloginid".PHP_EOL;

            // 检查授权是否过期或者是否授权,返回true，false
            if (!AliexpressInterface_Auth::checkToken ( $SAA_obj->sellerloginid )) {
                $SAA_obj->message .= " {$SAA_obj->sellerloginid} Unauthorized or expired!";
                $SAA_obj->last_time = time();
                $SAA_obj->next_time = time()+1200;
                $SAA_obj->status = 3;
                $SAA_obj->times += 1;
                $bool = $SAA_obj->save (false);
                if (!$bool) {
                    echo __FUNCTION__ . "STEP 2 : " . var_export($SAA_obj->errors, true);
                }
                continue;
            }

            $api = new AliexpressInterface_Api ();
            $access_token = $api->getAccessToken ( $SAA_obj->sellerloginid );
            //获取访问token失败
            if ($access_token === false){
                $SAA_obj->message .= " {$SAA_obj->sellerloginid} not getting access token!";
                $SAA_obj->status = 3;
                $SAA_obj->times += 1;
                $SAA_obj->last_time = time ();
                $SAA_obj->next_time = time()+1200;
                $bool = $SAA_obj->save (false);
                if (!$bool) {
                    echo __FUNCTION__ . "STEP 3 : " . var_export($SAA_obj->errors, true);
                }
                continue;
            }

            echo date('Y-m-d H:i:s')." step3 puid=$puid,sellerloginid=$sellerloginid".PHP_EOL;
            $api->access_token = $access_token;
            $timeMS2=TimeUtil::getCurrentTimestampMS();

            //分页设置
            $page = 1;
            $pageSize = 50;
            // 是否全部同步完成
            $success = true;
            #####################################
            $time = time();
            if($SAA_obj->end_time == 0) {
                //初始同步
                $start_time = $SAA_obj->binding_time;
                $end_time = $time;
            }else {
                //增量同步
                $start_time = $SAA_obj->end_time;
                $end_time = $time;
            }

            $format_start_time = self::getLaFormatTime ("m/d/Y H:i:s", $start_time );
            $format_end_time = self::getLaFormatTime ("m/d/Y H:i:s", $end_time );
            //echo date('Y-m-d H:i:s').' one shop start '.$row['id'].PHP_EOL;
            echo date('Y-m-d H:i:s')." step4 puid=$puid,sellerloginid=$sellerloginid,start_time=$start_time,end_time=$end_time,format_start_time=$format_start_time,format_end_time=$format_end_time".PHP_EOL;
            $totalOrderNum=0;
            do {
                // 接口传入参数
                $param = ['page' => $page, 'pageSize' => $pageSize, 'createDateStart' => $format_start_time, 'createDateEnd' => $format_end_time];
                // 调用接口获取订单列表
                $result = $api->findOrderListSimpleQuery($param);
                // 判断是否有订单
                if (!isset ( $result['totalItem'] )) {
                    $success = false;
                    break;
                }

                
                if($result ['totalItem'] > 0) {
                	$totalOrderNum=$totalOrderNum+$result ['totalItem'];
                	
                    // 保存数据到同步订单详情队列
                    foreach ( $result ['orderList'] as $one ) {
                        // 订单产生时间
                        $gmtCreate = AliexpressInterface_Helper::transLaStrTimetoTimestamp($one['gmtCreate']);
                        $QAG_obj = QueueAliexpressGetorder::findOne(['orderid'=>$one['orderId']]);
                        if (isset ( $QAG_obj )) {
                            $QAG_obj->type = 3;
                            $QAG_obj->times = 0;
                            $QAG_obj->order_status = $one['orderStatus'];
                            $QAG_obj->order_info = json_encode ( $one );
                            $QAG_obj->update_time = $time;
                            $bool = $QAG_obj->save (false);
                            if (!$bool) {
                                echo __FUNCTION__ . "STEP 4 : " . var_export($QAG_obj->errors, true);
                            }
                        } else {
                            $QAG_obj = new QueueAliexpressGetorder ();
                            $QAG_obj->uid = $SAA_obj->uid;
                            $QAG_obj->sellerloginid = $SAA_obj->sellerloginid;
                            $QAG_obj->aliexpress_uid = $SAA_obj->aliexpress_uid;
                            $QAG_obj->status = 0;
                            $QAG_obj->type = 3;  //新增订单标识
                            $QAG_obj->order_status = $one['orderStatus'];
                            $QAG_obj->orderid = $one ['orderId'];
                            $QAG_obj->times = 0;
                            $QAG_obj->order_info = json_encode ( $one );
                            $QAG_obj->last_time = 0;
                            $QAG_obj->gmtcreate = $gmtCreate;
                            $QAG_obj->create_time = $time;
                            $QAG_obj->update_time = $time;
                            $bool = $QAG_obj->save (false);
                            if (!$bool) {
                                echo __FUNCTION__ . "STEP 5 : " . var_export($QAG_obj->errors, true);
                            }
                        }
                    }
                }
                $page ++;
                $p = ceil($result['totalItem']/50);
            } while ( $page <= $p );
            
            echo date('Y-m-d H:i:s')." step5 puid=$puid,sellerloginid=$sellerloginid,totalOrderNum=$totalOrderNum".PHP_EOL;
            $timeMS3=TimeUtil::getCurrentTimestampMS();
            // 是否全部同步成功
            if($success) {
                $SAA_obj->start_time = $start_time;
                $SAA_obj->end_time = $end_time;
                $SAA_obj->status = 2;
                $SAA_obj->times = 0;
                $SAA_obj->message = '';
                $SAA_obj->next_time = time()+3600;
            } else {
                $SAA_obj->message .= isset($result ['error_message'])?$result['error_message']:'接口返回结果错误'.print_r($result,true);
                $SAA_obj->status = 3;
                $SAA_obj->times += 1;
                $SAA_obj->next_time = time()+1800;
            }
            $SAA_obj->last_time = $time;
            $bool = $SAA_obj->save (false);
            if (!$bool) {
                echo __FUNCTION__ . "STEP 6 : " . var_export($SAA_obj->errors, true);
            }
            $timeMS4=TimeUtil::getCurrentTimestampMS();
            
            $timeStr="t4_t3=".($timeMS4-$timeMS3).",t3_t2=".($timeMS3-$timeMS2).",t2_t1=".($timeMS2-$timeMS1).",t4_t1=".($timeMS4-$timeMS1);
            
            echo date('Y-m-d H:i:s')." step6 one shop end puid=$puid,sellerloginid=$sellerloginid,totalOrderNum=$totalOrderNum ".$timeStr.PHP_EOL;
            
            \Yii::info("step6 one shop end puid=$puid,sellerloginid=$sellerloginid,totalOrderNum=$totalOrderNum ".$timeStr,"file");
            
        }
        echo date('Y-m-d H:i:s').' time script end '.self::$cronJobId.PHP_EOL;
        return $hasGotRecord;
    }


    public static function firstToDb() {
        //Step 0, 检查当前本job版本，如果和自己的版本不一样，就自动exit吧
        $currentAliexpressGetOrderListVersion = ConfigHelper::getGlobalConfig("Order/aliexpressGetOrderListVersion",'NO_CACHE');
        if(empty($currentAliexpressGetOrderListVersion)) {
            $currentAliexpressGetOrderListVersion = 0;
        }
        //如果自己还没有定义，去使用global config来初始化自己
        if (empty(self::$aliexpressGetOrderListVersion)) {
            self::$aliexpressGetOrderListVersion = $currentAliexpressGetOrderListVersion;
        }
        //如果自己的version不同于全局要求的version，自己退出，从新起job来使用新代码
        if (self::$aliexpressGetOrderListVersion <> $currentAliexpressGetOrderListVersion){
            exit("Version new $currentAliexpressGetOrderListVersion , this job ver ".self::$aliexpressGetOrderListVersion." exits for using new version $currentAliexpressGetOrderListVersion.");
        }

        self::$cronJobId++;
        echo date('Y-m-d H:i:s').' first to db script start '.self::$cronJobId.PHP_EOL;

        // 同步订单
        $connection=Yii::$app->db_queue;
        $now = time();
        $hasGotRecord=false;
        //查新订单
        $sql = 'select `id`,`sellerloginid`,`status`,`orderid`,`last_time`,`order_status`, `type` from  `queue_aliexpress_getorder` where `status` <> 1 and `type` = 3 AND `times` < 10  limit 100';
        $dataReader = $connection->createCommand($sql)->query();
        echo date('Y-m-d H:i:s').' select count '.$dataReader->count().PHP_EOL;
        while( false !== ($row=$dataReader->read()) ) {
            
            $timeMS1=TimeUtil::getCurrentTimestampMS();
            //1. 先判断是否可以正常抢到该记录
            $affectRows = $connection->createCommand("update `queue_aliexpress_getorder` set status=1 where id ={$row['id']} and status<>1 ")->execute();
            if ($affectRows <= 0) {
                continue; //抢不到
            }

            //2. 抢到记录，设置同步需要的参数
            $hasGotRecord=true;
            $QAG_obj = QueueAliexpressGetorder::findOne($row['id']);
            if(!$QAG_obj) {
                echo date('Y-m-d H:i:s').' exception '.$row['orderid'].PHP_EOL;
                continue;
            }
            echo date('Y-m-d H:i:s').' api start '.$QAG_obj->orderid.PHP_EOL;
            $timeMS2=TimeUtil::getCurrentTimestampMS();
            // 检查授权是否过期或者是否授权,返回true，false
            if (!AliexpressInterface_Auth::checkToken ( $QAG_obj->sellerloginid )) {
                $QAG_obj->message .= " {$QAG_obj->sellerloginid} Unauthorized or expired!";
                $QAG_obj->last_time = $now;
                $QAG_obj->status = 3;
                $QAG_obj->times += 1;
                $bool = $QAG_obj->save (false);
                if (!$bool) {
                    echo __FUNCTION__ . "STEP 1 : " . var_export($QAG_obj->errors, true);
                }
                continue;
            }

            $api = new AliexpressInterface_Api ();
            $access_token = $api->getAccessToken ( $QAG_obj->sellerloginid );
            //获取访问token失败
            if ($access_token === false){
                $QAG_obj->message .= " {$QAG_obj->sellerloginid} not getting access token!";
                $QAG_obj->status = 3;
                $QAG_obj->times += 1;
                $QAG_obj->last_time = $now;
                $bool = $QAG_obj->save (false);
                if (!$bool) {
                    echo __FUNCTION__ . "STEP 2 : " . var_export($QAG_obj->errors, true);
                }
                continue;
            }

            $api->access_token = $access_token;
            // 接口传入参数速卖通订单号
            $param = ['orderId' => $row['orderid']];
            // 调用接口获取订单列表
            $result = $api->findOrderById ( $param );
            echo date('Y-m-d H:i:s').' api end '.$QAG_obj->orderid.PHP_EOL;
            // 是否同步成功
            if(isset ( $result ['error_message'] ) || empty ( $result )) {
                $QAG_obj->message .= isset ( $result ['error_message'] ) ? $result ['error_message']." findOrderById " : 'findOrderById接口返回错误，在新订单入库时';
                $QAG_obj->status = 3;
                $QAG_obj->times += 1;
                $QAG_obj->last_time = $now;
                $bool = $QAG_obj->save (false);
                if (!$bool) {
                    echo __FUNCTION__ . "STEP 3 : " . var_export($QAG_obj->errors, true);
                }
                continue;
            }

            echo date('Y-m-d H:i:s').' save start '.$QAG_obj->uid.' '.$result['id'].PHP_EOL;
            $timeMS3=TimeUtil::getCurrentTimestampMS();
            $uid = $QAG_obj->uid;
            //保存数据到订单表
            
            //平台订单id必须为字符串，不然后面在sql作为搜索where字段的时候，就使用不了索引！！！！！！
            $result['id'] = strval($QAG_obj->orderid);

            //速卖通返回的sellerOperatorLoginId是子账号的loginid（就算账号绑定的是主账号）
            //这里强制转成主账号loginid，这是为了后面保存到订单od_order_v2的订单信息能对应上绑定的账号
            $result["sellerOperatorLoginId"] = $QAG_obj->sellerloginid;
            $r = AliexpressInterface_Helper::saveAliexpressOrder ( $QAG_obj, $result );
            // 判断是否付款并且保存成功,是则删除数据，否则继续同步
            if($r['success'] != 0 || !isset($result['orderStatus'])) {
                $QAG_obj->status = 3;
                $QAG_obj->times += 1;
                $QAG_obj->message .= "速卖通订单saveAliexpressOrder " . $QAG_obj->orderid . "保存失败".$r ['message'];
                $bool = $QAG_obj->save (false);
                if (!$bool) {
                    echo __FUNCTION__ . "STEP 5 : " . var_export($QAG_obj->errors, true);
                }
                continue;
            }
            
            $timeMS4=TimeUtil::getCurrentTimestampMS();

            if ($result ['orderStatus'] == 'FINISH') {
                $bool = $QAG_obj->delete();
                if (!$bool) {
                    echo __FUNCTION__ . "STEP 6 : " . var_export($QAG_obj->errors, true);
                }
            }else {
                //写入临时表
                $QAG_two = new QueueAliexpressGetorder2();
                $QAG_two->uid = $QAG_obj->uid;
                $QAG_two->sellerloginid = $QAG_obj->sellerloginid;
                $QAG_two->aliexpress_uid = $QAG_obj->aliexpress_uid;
                $QAG_two->status = 2;
                $QAG_two->type = QueueAliexpressGetorder::NOFINISH;
                $QAG_two->order_status = $result ['orderStatus'];
                $QAG_two->orderid = $QAG_obj->orderid;
                $QAG_two->times = 0;
                $QAG_two->order_info = $QAG_obj->order_info;
                $QAG_two->last_time = $now;
                $QAG_two->gmtcreate = $QAG_obj->gmtcreate;
                $QAG_two->message = '';
                $QAG_two->create_time = $QAG_obj->create_time;
                $QAG_two->update_time = $now;
                $QAG_two->next_time = self::calcNextSyncTime($QAG_obj->uid, $result['orderStatus']);
                $bool = $saveRes = $QAG_two->save();
                if (!$bool) {
                    echo __FUNCTION__ . "STEP 7 : " . var_export($QAG_two->errors, true);
                }
                if($saveRes){
                    $bool = $QAG_obj->delete();
                    if (!$bool) {
                        echo __FUNCTION__ . "STEP 8 : " . var_export($QAG_obj->errors, true);
                    }
                }else {
                    $QAG_obj->status = 3;
                    $QAG_obj->times += 1;
                    //$QAG_obj->message .= "速卖通订单" . $QAG_obj->orderid . "保存失败".$r ['message'];
                    $QAG_obj->message .=  "QAG_two->save fails ---".print_r($QAG_two->errors,true);
                    
                    $bool = $QAG_obj->save (false);
                    if (!$bool) {
                        echo __FUNCTION__ . "STEP 9 : " . var_export($QAG_obj->errors, true);
                    }
                }
            }
            echo date('Y-m-d H:i:s').' save end '.$uid.' '.$result['id'].PHP_EOL;
            $timeMS5=TimeUtil::getCurrentTimestampMS();
            
            $logStr="aliexpress_firsttodb_finish puid=$uid,t2_1=".($timeMS2-$timeMS1).
                ",t3_2=".($timeMS3-$timeMS2).",t4_3=".($timeMS4-$timeMS3).",t5_4=".($timeMS5-$timeMS4).",t5_1=".($timeMS5-$timeMS1);
            
            echo $logStr."\n"; 
            \Yii::info($logStr,"file");
            
        }
        echo date('Y-m-d H:i:s').' first to db script end '.self::$cronJobId.PHP_EOL;
        return $hasGotRecord;
    }


	static function getOrderFinish($type,$orderBy="id",$time_interval=1800){
		//Step 0, 检查当前本job版本，如果和自己的版本不一样，就自动exit吧
		$currentAliexpressGetOrderListVersion = ConfigHelper::getGlobalConfig("Order/aliexpressGetOrderListVersion",'NO_CACHE');
		if (empty($currentAliexpressGetOrderListVersion))
			$currentAliexpressGetOrderListVersion = 0;
		
		//如果自己还没有定义，去使用global config来初始化自己
		if (empty(self::$aliexpressGetOrderListVersion))
			self::$aliexpressGetOrderListVersion = $currentAliexpressGetOrderListVersion;
			
		//如果自己的version不同于全局要求的version，自己退出，从新起job来使用新代码
		if (self::$aliexpressGetOrderListVersion <> $currentAliexpressGetOrderListVersion){
			exit("Version new $currentAliexpressGetOrderListVersion , this job ver ".self::$aliexpressGetOrderListVersion." exits for using new version $currentAliexpressGetOrderListVersion.");
		}
		$logTimeMS2_1 = TimeUtil::getCurrentTimestampMS();
		$backgroundJobId=self::getCronJobId();
		// 同步订单
		$connection=Yii::$app->db_queue;
		$order_status = array('PLACE_ORDER_SUCCESS','IN_CANCEL','WAIT_SELLER_SEND_GOODS','IN_ISSUE','IN_FROZEN','WAIT_SELLER_EXAMINE_MONEY','RISK_CONTROL');
		//只区分type是不是finish
		$t = time();
		$table = 'queue_aliexpress_getfinishorder';
		$command=$connection->createCommand('select `id`,`sellerloginid`,`status`,`orderid`,`last_time`,`order_status` from  `'.$table.'` where `status` <> 1 AND `times` < 10 AND next_time < '.$t.'  limit 100');
		
		$dataReader=$command->query();
		$hasGotRecord=false;
		$logTimeMS2_2 = TimeUtil::getCurrentTimestampMS();
		\Yii::info("aliexress_select_order_".$type." jobid=$backgroundJobId t2_1=".($logTimeMS2_2-$logTimeMS2_1),"file");
		while(($row=$dataReader->read())!==false) {
			$logTimeMS1=TimeUtil::getCurrentTimestampMS(); //获取当前时间戳，毫秒为单位，该数值只有比较意义。
			$last_time = time();
			echo $row['orderid']."\n";
			//1. 先判断是否可以正常抢到该记录
			$command = $connection->createCommand("update `".$table."` set status=1 where id =". $row['id']." and status<>1 ") ;
			$affectRows = $command->execute();
			if ($affectRows <= 0)	continue; //抢不到
			$logTimeMS2=TimeUtil::getCurrentTimestampMS();
			//2. 抢到记录，设置同步需要的参数
			$hasGotRecord=true;
			$QAG_obj = QueueAliexpressGetfinishorder::findOne($row['id']);
			// 检查授权是否过期或者是否授权,返回true，false
			$a = AliexpressInterface_Auth::checkToken ($row['sellerloginid'] );
			if ($a) {
				$api = new AliexpressInterface_Api ();
				$access_token = $api->getAccessToken ( $row['sellerloginid'] );
				//获取访问token失败
				if ($access_token === false){
					echo $QAG_obj->sellerloginid . 'not getting access token!' . "\n";
					\Yii::info("aliexress_get_order_".$type." token_not_access jobid=$backgroundJobId,sid=".$QAG_obj->sellerloginid.",puid=".$QAG_obj->uid,"file");
					$QAG_obj->message = $QAG_obj->sellerloginid . ' not getting access token!';
					$QAG_obj->status = 3;
					$QAG_obj->times += 1;
					$QAG_obj->last_time = $last_time;
					$QAG_obj->update_time = time ();
					$QAG_obj->next_time = time () + 3600;
					$bool  = $QAG_obj->save (false);
                    if (!$bool) {
                        echo __FUNCTION__ . "STEP 1 : " . var_export($QAG_obj->errors, true);
                    }
					continue;
				}
				$api->access_token = $access_token;
				// 接口传入参数速卖通订单号
				$param = array (
						'orderId' => $row['orderid']
				);
				// 调用接口获取订单列表
				$result = $api->findOrderById ( $param );
		
				$logTimeMS3=TimeUtil::getCurrentTimestampMS();
		
				// 是否同步成功
				if (isset ( $result ['error_message'] ) || empty ( $result )) {
					$QAG_obj->message = $result ['error_message'];
					$QAG_obj->status = 3;
					$QAG_obj->times += 1;
					$QAG_obj->last_time = $last_time;
					$QAG_obj->update_time = time ();
					$QAG_obj->next_time = time () + 3600;
					$bool = $QAG_obj->save (false);
                    if (!$bool) {
                        echo __FUNCTION__ . "STEP 2 : " . var_export($QAG_obj->errors, true);
                    }
				} else {
					// 同步成功保存数据到订单表
					if (true){
						//平台订单id必须为字符串，不然后面在sql作为搜索where字段的时候，就使用不了索引！！！！！！
						if (isset($result['id']))  $result['id']=strval($result['id']);
						//速卖通返回的sellerOperatorLoginId是子账号的loginid（就算账号绑定的是主账号）
						//这里强制转成主账号loginid，这是为了后面保存到订单od_order_v2的订单信息能对应上绑定的账号
						$result["sellerOperatorLoginId"]=$QAG_obj->sellerloginid;
						$r = AliexpressInterface_Helper::saveAliexpressOrder ( $QAG_obj, $result );
						$logTimeMS4=TimeUtil::getCurrentTimestampMS();
		
						//print_r($result);
						// 判断是否付款并且保存成功,是则删除数据，否则继续同步
						if ($r ['success'] == 0 && isset($result ['orderStatus']) && $result ['orderStatus']=='FINISH') {
							$bool = $QAG_obj->delete ();
                            if (!$bool) {
                                echo __FUNCTION__ . "STEP 3 : " . var_export($QAG_obj->errors, true);
                            }
						} else {
							if ($r ['success'] == 1) {
								$QAG_obj->status = 3;
								$QAG_obj->times += 1;
								$QAG_obj->message = "速卖通订单" . $QAG_obj->orderid . "保存失败".$r ['message'];
							} else {
								$QAG_obj->status = 2;
								$QAG_obj->times = 0;
							}
							$QAG_obj->order_status = isset($result ['orderStatus'])?$result ['orderStatus']:$QAG_obj->order_status;
							$QAG_obj->last_time = $last_time;
							$QAG_obj->update_time = time ();
							$QAG_obj->next_time = time() + 3600;
							$bool = $QAG_obj->save (false);
                            if (!$bool) {
                                echo __FUNCTION__ . "STEP 4 : " . var_export($QAG_obj->errors, true);
                            }
								
						}
						$logTimeMS5=TimeUtil::getCurrentTimestampMS();
		
						\Yii::info("aliexress_get_order_".$type." saveok jobid=$backgroundJobId t2_1=".($logTimeMS2-$logTimeMS1).
								",t3_2=".($logTimeMS3-$logTimeMS2).",t4_3=".($logTimeMS4-$logTimeMS3).",t5_4=".($logTimeMS5-$logTimeMS4).
								",t5_1=".($logTimeMS5-$logTimeMS1).",puid=".$QAG_obj->uid,"file");
		
					}
				}
			} else {
				//接口获取失败
				echo $QAG_obj->sellerloginid . ' Unauthorized or expired!' . "\n";
				\Yii::info("aliexress_get_order_".$type." unauthorized_or_expired jobid=$backgroundJobId,sid=".$QAG_obj->sellerloginid.",puid=".$QAG_obj->uid,"file");
				$QAG_obj->message = $QAG_obj->sellerloginid . ' Unauthorized or expired!';
				$QAG_obj->status = 3;
				$QAG_obj->times += 1;
				$QAG_obj->last_time = $last_time;
				$QAG_obj->update_time = time ();
				$QAG_obj->next_time = time() + 3600;
				$bool = $QAG_obj->save (false);
                if (!$bool) {
                    echo __FUNCTION__ . "STEP 5 : " . var_export($QAG_obj->errors, true);
                }
			}
		}
		return $hasGotRecord;
	}




    //根据优先级规则，计算等待队列中下一次更新时间
    //发货前订单状态，活跃用户半小时
    //发货前定案状态，非活跃用户2天
    //发货后订单状态，活跃用户1天
    //发货后订单状态，非活跃用户5天
    protected static function calcNextSyncTime($uid, $order_status) {
        $status = array('PLACE_ORDER_SUCCESS','IN_CANCEL','WAIT_SELLER_SEND_GOODS','IN_ISSUE','IN_FROZEN','WAIT_SELLER_EXAMINE_MONEY','RISK_CONTROL');

        $next_time = 0;
        if(in_array($order_status, $status)) {
            if(self::isActiveUser($uid)) {
                $next_time = time() + 1800;
            }else {
                $next_time = time() + 172800;
            }
        }else{
            if(self::isActiveUser($uid)) {
                $next_time = time() + 86400;
            }else {
                $next_time = time() + 432000;
            }
        }

        return $next_time;
    }


    /**
     * 更新订单状态
     * @author million 2015-04-03
     * 88028624@qq.coms
     */
    public static function getOrder2($type,$orderBy="id",$time_interval=1800){
        //Step 0, 检查当前本job版本，如果和自己的版本不一样，就自动exit吧
        $currentAliexpressGetOrderListVersion = ConfigHelper::getGlobalConfig("Order/aliexpressGetOrderListVersion",'NO_CACHE');
        if(empty($currentAliexpressGetOrderListVersion)) {
            $currentAliexpressGetOrderListVersion = 0;
        }
        //如果自己还没有定义，去使用global config来初始化自己
        if (empty(self::$aliexpressGetOrderListVersion)) {
            self::$aliexpressGetOrderListVersion = $currentAliexpressGetOrderListVersion;
        }
        //如果自己的version不同于全局要求的version，自己退出，从新起job来使用新代码
        if (self::$aliexpressGetOrderListVersion <> $currentAliexpressGetOrderListVersion){
            exit("Version new $currentAliexpressGetOrderListVersion , this job ver ".self::$aliexpressGetOrderListVersion." exits for using new version $currentAliexpressGetOrderListVersion.");
        }

        self::$cronJobId++;
        echo date('Y-m-d H:i:s').' order status change script start '.self::$cronJobId.PHP_EOL;

        // 同步订单
        $connection=Yii::$app->db_queue;
        $now = time();
        $hasGotRecord=false;

        //查询队列里的非新订单
        $sql = "select `id`,`sellerloginid`,`status`,`orderid`,`last_time`,`order_status`, `type` from  queue_aliexpress_getorder where `status` <> 1 and type=5 AND `times` < 10  limit 100";
        $dataReader = $connection->createCommand($sql)->query();
        echo date('Y-m-d H:i:s').' select count '.$dataReader->count().PHP_EOL;
        while( false !== ($row=$dataReader->read()) ) {
            //1. 先判断是否可以正常抢到该记录
            $affectRows = $connection->createCommand("update `queue_aliexpress_getorder` set status=1 where id ={$row['id']} and status<>1 ")->execute();
            if ($affectRows <= 0) {
                continue; //抢不到
            }
            //2. 抢到记录，设置同步需要的参数
            $hasGotRecord=true;

            $QAG_obj = QueueAliexpressGetorder::findOne($row['id']);
            if(!$QAG_obj) {
                echo date('Y-m-d H:i:s').' exception '.$row['orderid'].PHP_EOL;
                continue;
            }

            echo date('Y-m-d H:i:s').' api start '.$QAG_obj->orderid.PHP_EOL;
            // 检查授权是否过期或者是否授权,返回true，false
            if (!AliexpressInterface_Auth::checkToken ( $QAG_obj->sellerloginid )) {
                $QAG_obj->message .= " {$QAG_obj->sellerloginid} Unauthorized or expired!";
                $QAG_obj->last_time = $now;
                $QAG_obj->status = 3;
                $QAG_obj->times += 1;
                $bool = $QAG_obj->save (false);
                if (!$bool) {
                    echo __FUNCTION__ . "STEP 1 : " . var_export($QAG_obj->errors, true);
                }
                continue;
            }

            $api = new AliexpressInterface_Api ();
            $access_token = $api->getAccessToken ( $QAG_obj->sellerloginid );
            //获取访问token失败
            if ($access_token === false){
                $QAG_obj->message .= " {$QAG_obj->sellerloginid} not getting access token!";
                $QAG_obj->status = 3;
                $QAG_obj->times += 1;
                $QAG_obj->last_time = $now;
                $bool = $QAG_obj->save (false);
                if (!$bool) {
                    echo __FUNCTION__ . "STEP 2 : " . var_export($QAG_obj->errors, true);
                }
                continue;
            }

            $api->access_token = $access_token;

            // 接口传入参数速卖通订单号
            $param = ['orderId' => $row['orderid']];
            // 调用接口获取订单列表
            $result = $api->findOrderById ( $param );
            echo date('Y-m-d H:i:s').' api end '.$QAG_obj->orderid.PHP_EOL;
            // 是否同步成功
            if(isset ( $result['error_message'] ) || empty ( $result )) {
                $QAG_obj->message .= isset ( $result ['error_message'] ) ? $result ['error_message'] : '接口返回错误，在订单状态变更检查时';
                $QAG_obj->status = 3;
                $QAG_obj->times += 1;
                $QAG_obj->last_time = $now;
                $bool = $QAG_obj->save (false);
                if (!$bool) {
                    echo __FUNCTION__ . "STEP 3 : " . var_export($QAG_obj->errors, true);
                }
                    continue;
            }

            //订单未完成，且状态没有改变，则回到等待队列中
            if($QAG_obj->order_status === $result['orderStatus'] && $result['orderStatus'] !== 'FINISH'){
                $QAG_two = new QueueAliexpressGetorder2();
                $QAG_two->uid = $QAG_obj->uid;
                $QAG_two->sellerloginid = $QAG_obj->sellerloginid;
                $QAG_two->aliexpress_uid = $QAG_obj->aliexpress_uid;
                $QAG_two->status = 2;
                $QAG_two->type = $QAG_obj->type;
                $QAG_two->order_status = $QAG_obj->order_status;
                $QAG_two->orderid = $QAG_obj->orderid;
                $QAG_two->times = 0;
                $QAG_two->order_info = $QAG_obj->order_info;
                $QAG_two->last_time = $now;
                $QAG_two->gmtcreate = $QAG_obj->gmtcreate;
                $QAG_two->message = '';
                $QAG_two->create_time = $QAG_obj->create_time;
                $QAG_two->update_time = $now;
                $QAG_two->next_time = self::calcNextSyncTime($QAG_obj->uid, $QAG_obj->order_status);
                $bool = $saveRes = $QAG_two->save(false);
                if (!$bool) {
                    echo __FUNCTION__ . "STEP 4 : " . var_export($QAG_two->errors, true);
                }
                if($saveRes){
                    $bool = $QAG_obj->delete();
                    if (!$bool) {
                        echo __FUNCTION__ . "STEP 5 : " . var_export($QAG_obj->errors, true);
                    }
                }else{
                    $QAG_obj->message .= ' 保存等待队列失败';
                    $QAG_obj->status = 3;
                    $QAG_obj->times += 1;
                    $QAG_obj->last_time = $now;
                    $bool = $QAG_obj->save(false);
                    if (!$bool) {
                        echo __FUNCTION__ . "STEP 6 : " . var_export($QAG_obj->errors, true);
                    }
                }
                continue;
            }

            echo date('Y-m-d H:i:s').' save start '.$QAG_obj->uid.' '.$result['id'].PHP_EOL;
            $uid = $QAG_obj->uid;
            // 同步成功保存数据到订单表
            
            //平台订单id必须为字符串，不然后面在sql作为搜索where字段的时候，就使用不了索引！！！！！！
            $result['id'] = strval($result['id']);

            //速卖通返回的sellerOperatorLoginId是子账号的loginid（就算账号绑定的是主账号）
            //这里强制转成主账号loginid，这是为了后面保存到订单od_order_v2的订单信息能对应上绑定的账号
            $result["sellerOperatorLoginId"] = $QAG_obj->sellerloginid;
            $r = AliexpressInterface_Helper::saveAliexpressOrder( $QAG_obj, $result );
            // 判断是否付款并且保存成功,是则删除数据，否则继续同步
            if($r['success'] != 0 || !isset($result['orderStatus'])) {
                $QAG_obj->status = 3;
                $QAG_obj->times += 1;
                $QAG_obj->message .= "速卖通订单" . $QAG_obj->orderid . "保存失败".$r ['message'];
                $bool = $QAG_obj->save (false);
                if (!$bool) {
                    echo __FUNCTION__ . "STEP 8 : " . var_export($QAG_obj->errors, true);
                }
                continue;
            }

            if ($result ['orderStatus'] == 'FINISH') {
                $bool = $QAG_obj->delete();
                if (!$bool) {
                    echo __FUNCTION__ . "STEP 9 : " . var_export($QAG_obj->errors, true);
                }
            }else {
                //写入等待队列
                $QAG_two = new QueueAliexpressGetorder2();
                $QAG_two->uid = $QAG_obj->uid;
                $QAG_two->sellerloginid = $QAG_obj->sellerloginid;
                $QAG_two->aliexpress_uid = $QAG_obj->aliexpress_uid;
                $QAG_two->status = 2;
                $QAG_two->type = QueueAliexpressGetorder::NOFINISH;
                $QAG_two->order_status = $result ['orderStatus'];
                $QAG_two->orderid = $QAG_obj->orderid;
                $QAG_two->times = 0;
                $QAG_two->order_info = $QAG_obj->order_info;
                $QAG_two->last_time = $now;
                $QAG_two->gmtcreate = $QAG_obj->gmtcreate;
                $QAG_two->message = '';
                $QAG_two->create_time = $QAG_obj->create_time;
                $QAG_two->update_time = $now;
                $QAG_two->next_time = self::calcNextSyncTime($QAG_obj->uid, $result['orderStatus']);
                $saveRes = $QAG_two->save();
                if (!$saveRes) {
                    echo __FUNCTION__ . "STEP 10 : " . var_export($QAG_two->errors, true);
                }
                if($saveRes){
                    $bool = $QAG_obj->delete();
                    if (!$bool) {
                        echo __FUNCTION__ . "STEP 11 : " . var_export($QAG_obj->errors, true);
                    }
                }else {
                    $QAG_obj->status = 3;
                    $QAG_obj->times += 1;
                    $QAG_obj->message .= "速卖通订单" . $QAG_obj->orderid . "保存失败".$r ['message'];
                    $bool = $QAG_obj->save (false);
                    if (!$bool) {
                        echo __FUNCTION__ . "STEP 12 : " . var_export($QAG_obj->errors, true);
                    }
                }
            }
            echo date('Y-m-d H:i:s').' save end '.$uid.' '.$result['id'].PHP_EOL;
        }
        echo date('Y-m-d H:i:s').' order status change script end '.self::$cronJobId.PHP_EOL;
        return $hasGotRecord;
    }


	/**
	 * 刷新refresh_token 
	 * @author dzt 2015-07-13
	 */
	static function postponeToken($time_interval=86400){
		$t = time() + 86400 * 30;// refresh_token 在30 天内过期的
		$SAA_objs = SaasAliexpressUser::find()->where(' `is_active` = 1 AND `refresh_token_timeout` > '.time().' AND  `refresh_token_timeout` < '.$t)->orderBy('refresh_token_timeout asc')->all();
		echo "count:".count($SAA_objs)." \n";
		\Yii::info("There are ".count($SAA_objs)." ali users waiting for being postponeToken to eagle");
		
		if(count($SAA_objs) > 0){
			foreach ($SAA_objs as $SAU_obj) {
				$a = AliexpressInterface_Auth::checkToken ($SAU_obj->sellerloginid );
				if ($a) {
					$api = new AliexpressInterface_Api ();
					$api->access_token = $api->getAccessToken ( $SAU_obj->sellerloginid );
					$rtn = $api->postponeToken($SAU_obj->refresh_token , $api->access_token);
					if(isset($rtn['refresh_token'])){
						$SAU_obj->refresh_token = $rtn['refresh_token'];
						$SAU_obj->refresh_token_timeout = AliexpressInterface_Helper::transLaStrTimetoTimestamp($rtn['refresh_token_timeout']);
						$SAU_obj->access_token = $rtn['access_token'];
						$SAU_obj->access_token_timeout = time() + 28800;// 8 小时过期
						$SAU_obj->update_time = time();
						if(!$SAU_obj->save()){
							echo $SAU_obj->sellerloginid. ' $SAU_obj->save() save fail!' . "\n";
							\Yii::info('aliexress_postpone_token  $SAU_obj->save() save fail! Error:'.print_r($SAU_obj->getErrors(),true).' ,sid='.  $SAU_obj->sellerloginid.',puid='.$SAU_obj->uid  .' rtn Arr:'.print_r($rtn,true),"file");
						}
					}else {
						echo  $SAU_obj->sellerloginid . ' get refresh_token fail!' . "\n";
						\Yii::info('aliexress_postpone_token get refresh_token fail! ,sid='.  $SAU_obj->sellerloginid.',puid='. $SAU_obj->uid .' rtn Arr:'.print_r($rtn,true),"file");
					}
			
				}else{
					echo  $SAU_obj->sellerloginid . ' Unauthorized or expired!' . "\n";
					\Yii::info("aliexress_postpone_token  unauthorized_or_expired ,sid=".  $SAU_obj->sellerloginid.",puid=". $SAU_obj->uid ,"file");
					// @todo 要不要对user is_active之类的值进行设置
				}
			
			}
		}
		
	}
	/**
	 * 同步在线商品
	 * 
	 * @param unknown $type 商品在线状态
	 * @param string $orderBy 排序
	 * @param number $time_interval 时间间隔
	 */
	static function getListing($type,$orderBy="last_time",$time_interval=86400,$isImmediate='N'){
		//Step 0, 检查当前本job版本，如果和自己的版本不一样，就自动exit吧
		$version = ConfigHelper::getGlobalConfig("Listing/aliexpressGetListingVersion",'NO_CACHE');
		if (empty($version))
			$version = 0;
		
		//如果自己还没有定义，去使用global config来初始化自己
		if (empty(self::$version))
			self::$version = $version;
			
		//如果自己的version不同于全局要求的version，自己退出，从新起job来使用新代码
		if (self::$version <> $version){
			exit("Version new $version , this job ver ".self::$version." exits for using new version $version.");
		}
		$backgroundJobId=self::getCronJobId();
		$connection=Yii::$app->db;
		#########################
		$hasGotRecord=false;//是否抢到账号
		$t = time()-$time_interval;
		
	//	$andSql = ' AND `last_time` > 0 ';
		$andSql = '  ';
		if($isImmediate == 'Y')
			$andSql = ' AND `last_time` = 0 ';
		
		$command=$connection->createCommand('select `id`,`uid` from `saas_aliexpress_autosync` 
				where `is_active` = 1 AND `status` <> 1 AND `times` < 10 AND `type`="'.$type.'" AND `last_time` < '.$t.$andSql.' order by `last_time` ASC limit 5');
		#################################
		$dataReader=$command->query();
		$activeUsersPuidArr = UserLastActionTimeHelper::getPuidArrByInterval(7*24);
		while(($row=$dataReader->read())!==false) {
            //检查是否开启同步商品
			$ret = AppApiHelper::getFunctionstatusByPuidKey($row['uid'], "tracker_recommend");
			if($ret == 0){
                //没有开启，更新，跳过当前循环
				$command = $connection->createCommand("update saas_aliexpress_autosync set status=4 ,last_time=".time()." where id =". $row['id']) ;
				$command->execute();
				continue;
			}
			 
			
			
            $puid=$row['uid'];
            $autoSyncId=$row['id'];
			$logTimeMS1=TimeUtil::getCurrentTimestampMS(); //获取当前时间戳，毫秒为单位，该数值只有比较意义。
			//1. 先判断是否可以正常抢到该记录
			$command = $connection->createCommand("update saas_aliexpress_autosync set status=1 where id =". $row['id']." and status<>1 ") ;
			$affectRows = $command->execute();
			if ($affectRows <= 0)	continue; //抢不到
			$logTimeMS2=TimeUtil::getCurrentTimestampMS();
			echo "aliexpress_get_listing_onselling gotit puid=$puid start \n";
			\Yii::info("aliexpress_get_listing_onselling gotit id=$autoSyncId,puid=$puid start","file");
			$logPuidTimeMS1=TimeUtil::getCurrentTimestampMS();
			//2. 抢到记录
			$hasGotRecord=true;
			$SAA_obj = SaasAliexpressAutosync::findOne($autoSyncId);
			
			 
			
			
			// 检查授权是否过期或者是否授权,返回true，false
			$a = AliexpressInterface_Auth::checkToken ( $SAA_obj->sellerloginid );
			if ($a) {
				echo $SAA_obj->sellerloginid."\n";
				$api = new AliexpressInterface_Api ();
				$access_token = $api->getAccessToken ( $SAA_obj->sellerloginid );
				//获取访问token失败
				if ($access_token === false){
					echo $SAA_obj->sellerloginid . 'not getting access token!' . "\n";
					\Yii::info("Error: aliexpress_get_listing_onselling ".$SAA_obj->sellerloginid . 'not getting access token!' ,"file");
					$SAA_obj->message = $SAA_obj->sellerloginid . ' not getting access token!';
					$SAA_obj->status = 3;
					$SAA_obj->times += 1;
					$SAA_obj->last_time = time ();
					$SAA_obj->update_time = time ();
					$bool = $SAA_obj->save (false);
                    if (!$bool) {
                        echo __FUNCTION__ . "STEP 2 : " . var_export($SAA_obj->errors, true);
                    }
					continue;
				}
				$api->access_token = $access_token;
				$page = 1;
				$pageSize = 100;
				// 是否全部同步完成
				$success = true;
				do {
					// 接口传入参数
					$param = array (
					'currentPage' => $page,
					'pageSize' => $pageSize,
					'productStatusType'=>$type,
					);
					###################################################
					//$param['createDateEnd'] = self::getLaFormatTime ( "m/d/Y H:i:s", $end_time );
					//$param['productStatusType'] =$type;
					#######################################################
					// 调用接口获取订单列表
					$logTimeMS3=TimeUtil::getCurrentTimestampMS();
					try{
						$result = $api->findProductInfoListQuery( $param );
					}catch (Exception $exApi){
						$result = array();
						$success = false;
						$result['error_message'] = print_r($exApi,true);
					}
					$logTimeMS4=TimeUtil::getCurrentTimestampMS();
					
					// 判断是否有订单
					if (isset ( $result ['productCount'] )) {
						echo $result ['productCount']."\n";
						if ($result ['productCount'] > 0) {
							// 保存商品数据
							foreach ( $result ['aeopAEProductDisplayDTOList'] as $one ) {
								$logT1=TimeUtil::getCurrentTimestampMS();
								//开始保存数据逻辑
								$gmtCreate = AliexpressInterface_Helper::transLaStrTimetoTimestamp($one ['gmtCreate']);
	                            $gmtModified = AliexpressInterface_Helper::transLaStrTimetoTimestamp($one ['gmtModified']);
	                            $WOD = AliexpressInterface_Helper::transLaStrTimetoTimestamp($one ['wsOfflineDate']);
								if($one['imageURLs'] != ''){
									$t = explode(';',$one['imageURLs']);
									$pp = $t[0];
								}else{
									$pp = '';
								}
								$logT2=TimeUtil::getCurrentTimestampMS();
							 
									$logT3=TimeUtil::getCurrentTimestampMS();
									$AL_obj = AliexpressListing::findOne(['productid'=>$one ['productId']]);
									$logT4=TimeUtil::getCurrentTimestampMS();
									if (isset ( $AL_obj )) {
										$AL_obj->freight_template_id = isset($one['freightTemplateId']) ? $one['freightTemplateId'] : '';
										$AL_obj->owner_member_seq = $one['ownerMemberSeq'];
										$AL_obj->subject = $one['subject'];
										$AL_obj->photo_primary = $pp;
										$AL_obj->imageurls = $one['imageURLs'];
										$AL_obj->ws_offline_date = $WOD;
										$AL_obj->product_min_price = $one['productMinPrice'];
										$AL_obj->ws_display = $one['wsDisplay'];
										$AL_obj->product_max_price = $one['productMaxPrice'];
										$AL_obj->updated = time ();
										$bool = $AL_obj->save (false);
                                        if (!$bool) {
                                            echo __FUNCTION__ . "STEP 3 : " . var_export($AL_obj->errors, true);
                                        }
									} else {
										$AL_obj = new AliexpressListing ();
										$AL_obj->productid = $one['productId'];
										$AL_obj->freight_template_id = isset($one['freightTemplateId']) ? $one['freightTemplateId'] : '';
										$AL_obj->owner_member_seq = $one['ownerMemberSeq'];
										$AL_obj->subject = $one['subject'];
										$AL_obj->photo_primary = $pp;
										$AL_obj->imageurls = $one['imageURLs'];
										$AL_obj->selleruserid = $SAA_obj->sellerloginid;
										$AL_obj->ws_offline_date = $WOD;
										$AL_obj->product_min_price = $one['productMinPrice'];
										$AL_obj->ws_display = $one['wsDisplay'];
										$AL_obj->product_max_price = $one['productMaxPrice'];
										$AL_obj->gmt_modified = $gmtModified;
										$AL_obj->gmt_create = $gmtCreate;
										$AL_obj->sku_stock = 0;
										$AL_obj->created = time();
										$AL_obj->updated = time();
										$bool = $AL_obj->save (false);
                                        if (!$bool) {
                                            echo __FUNCTION__ . "STEP 4 : " . var_export($AL_obj->errors, true);
                                        }
								 
									$logT5=TimeUtil::getCurrentTimestampMS();
							//		\Yii::info("aliexress_get_listing_".$type." saveoneok jobid=$backgroundJobId t2_1=".($logT2-$logT1).
							//				",t3_2=".($logT3-$logT2).",t4_3=".($logT4-$logT3).",t5_4=".($logT5-$logT4).
							//				",t5_1=".($logT5-$logT1).",id=$autoSyncId,puid=".$SAA_obj->uid,"file");
								}
	                            //保存数据逻辑结束
							}
						}
					} else {
						$success = false;
					}
					$logTimeMS5=TimeUtil::getCurrentTimestampMS();
					$page ++;
					$p = isset($result['totalPage']) ? $result['totalPage'] : 0;
					\Yii::info("aliexpress_get_listing_".$type." saveok jobid=$backgroundJobId t2_1=".($logTimeMS2-$logTimeMS1).
					    ",t3_2=".($logTimeMS3-$logTimeMS2).",t4_3=".($logTimeMS4-$logTimeMS3).",t5_4=".($logTimeMS5-$logTimeMS4).
					    ",t5_1=".($logTimeMS5-$logTimeMS1).",tpage=$p,page=$page,id=$autoSyncId,puid=".$SAA_obj->uid,"file");
						
				} while ( $page <= $p );
				
				// 是否全部同步成功
				if ($success) {
					$SAA_obj->last_time = time();
					$SAA_obj->status = 2;
					$SAA_obj->times = 0;
					$bool = $SAA_obj->save (false);
                    if (!$bool) {
                        echo __FUNCTION__ . "STEP 5 : " . var_export($SAA_obj->errors, true);
                    }
				} else {
					$SAA_obj->message = isset($result ['error_message'])?$result ['error_message']:'接口返回结果错误'.print_r($result,true);
					$SAA_obj->last_time = time();
					$SAA_obj->status = 3;
					$SAA_obj->times += 1;
					$bool = $SAA_obj->save (false);
                    if (!$bool) {
                        echo __FUNCTION__ . "STEP 6 : " . var_export($SAA_obj->errors, true);
                    }
				}
				$logPuidTimeMS2=TimeUtil::getCurrentTimestampMS();
				\Yii::info("aliexpress_get_listing_puid_".$type." saveok jobid=$backgroundJobId t2_1=".($logPuidTimeMS2-$logPuidTimeMS1).
				   ",id=".$SAA_obj->id.",puid=".$SAA_obj->uid,"file");
				
				
				
			} else {
				echo $SAA_obj->sellerloginid . ' Unauthorized or expired!' . "\n";
				$SAA_obj->message = $SAA_obj->sellerloginid . ' Unauthorized or expired!';
				$SAA_obj->last_time = time();
				$SAA_obj->status = 3;
				$SAA_obj->times += 1;
				$bool = $SAA_obj->save (false);
                if (!$bool) {
                    echo __FUNCTION__ . "STEP 7 : " . var_export($SAA_obj->errors, true);
                }
			}
		}
		return $hasGotRecord;
	}

     /**
     * 手动同步Aliexpress订单
     * @author yuhettian 2015-9-14
     * 601353571@qq.com
     */
    static function getOrderListByManual(){
            

            //接收参数
            $user_info = \Yii::$app->user->identity;
            if ($user_info['puid']==0){
                $uid = $user_info['uid'];
            }else {
                $uid = $user_info['puid'];
            }

            $sellerloginid = $_POST['sellerloginid'];
            $startdate = $_POST['startdate'];
            $enddate = $_POST['enddate'];
            $synctype = $_POST['synctype'];
            $judgeTime = time();
            
            //判断用户是否符合使用条件
            $checkSync = CheckSync::findOne(['sellerloginid'=> $sellerloginid]);
            if($checkSync === null || $checkSync->sellerloginid == '' || $checkSync->sync_time == 0 ){
            	$checkSync = new CheckSync();
                $checkSync->sellerloginid = $sellerloginid;
                $checkSync->sync_time = time();
                $checkSync->save();
            }
			if(($checkSync->sync_time-$judgeTime)<3600){
                return $success = 'Today has been synchronized!';
            }

            $checkToken = AliexpressInterface_Auth::checkToken ( $sellerloginid );

            if ($checkToken) {
                //获取访问token
                $api = new AliexpressInterface_Api ();
                $access_token = $api->getAccessToken($sellerloginid);

                //获取访问token失败
                if ($access_token === false) {
                    return $success = 'Token acquisition failure!';
                }
                $api->access_token = $access_token;
               
                    $page = 1;
                    $pageSize = 50;
                    // 是否全部同步完成
                    $success = "Synchronous success";

                    #####################################
                    if (!empty($startdate) && !empty($enddate)) {
                        if (((strtotime($enddate) - strtotime($startdate)) > (86400 * 3)) || ((strtotime($enddate) - strtotime($startdate)) < 0)) {
                            return $success = "Please fill in the complete parameters";
                        } else {
                            $start_time = strtotime($startdate);
                            $end_time = strtotime($enddate);
                        }
                    } else {
                        $start_time = time() - (86400 * 2);
                        $end_time = time();
                    }


                    ########################################
                    // 接口传入参数
            do {    
                    $param = array(
                        'page' => $page,
                        'pageSize' => $pageSize,
                    );
                    $param['createDateStart'] = self::getLaFormatTime("m/d/Y H:i:s", $start_time);
                    $param['createDateEnd'] = self::getLaFormatTime("m/d/Y H:i:s", $end_time);
                    if ($synctype != "MO_CHOSE") {
                        $param['orderStatus'] = $synctype;
                    }
                    #######################################################
                    // 调用接口获取订单列表
                    //$result = $api->findOrderListQuery ( $param );//old
                    $result = $api->findOrderListSimpleQuery($param);

                    // 判断是否有订单
                    if (isset ($result ['totalItem'])) {
                        if ($result ['totalItem'] > 0) {
                            // 保存数据到同步订单详情队列
                            foreach ($result ['orderList'] as $one) {
                                // 订单产生时间
                                $one['gmtCreate'] = AliexpressInterface_Helper::transLaStrTimetoTimestamp($one ['gmtCreate']);

                                //对比相应数据
                                $orders = OdOrder::findOne(['order_source_order_id' => $one['orderId']]);
                                if ($orders->order_source_status == $one['orderStatus']) {
                                    continue;
                                }
                                //接口传入参数
                                $source = array(
                                    'orderId' => $one['orderId']
                                );
                                $results = $api->findOrderById($source);

                                //转换为object参数
                                $result_obj = new QueueAliexpressGetorder ();
                                $result_obj->uid = $uid;
                                $result_obj->order_info = json_encode($one);
                                //相关数据进saveAliexpressOrderManual方法
                                $data = AliexpressInterface_Helper::saveAliexpressOrderManual($result_obj, $results);
                                if ($data ['success'] == 0) {
                                    //error_log($data['orderId'], 3, 'D:/Aliexpress.txt');
                                    return $success;
                                } elseif ($data['success'] == 1) {
                                    //error_log($data['orderId'], 3, 'D:/Aliexpress1.txt');
                                    return $success = "Failed to save the order";
                                }
                            }
                        }
                    }
                    $page++;
                    $total = isset($result ['totalItem']) ? $result ['totalItem'] : 0;
                    $pages = ceil($total / 50);
                } while ($page <= $pages);
                // 是否全部同步成功
            }
        return $success = "Token Invalid";
    }
	
	
	/**
	 * 从queue_aliexpress_getorder2 回写 queue_aliexpress_getorder脚本
	 * @author yangjun 2015-08-24
	 */
	static function getOrderInsertQueue(){
		//Step 0, 检查当前本job版本，如果和自己的版本不一样，就自动exit吧
		$version = ConfigHelper::getGlobalConfig("Listing/aliexpressGetListingVersion",'NO_CACHE');
		if (empty($version))
			$version = 0;
		
		//如果自己还没有定义，去使用global config来初始化自己
		if (empty(self::$version))
			self::$version = $version;
			
		//如果自己的version不同于全局要求的version，自己退出，从新起job来使用新代码
		if (self::$version <> $version){
			exit("Version new $version , this job ver ".self::$version." exits for using new version $version.");
		}
		
		$t = time(); //需要同步时间节点
		$hasGotRecord = false;
	    $connection=Yii::$app->db_queue;
	    $command=$connection->createCommand('select  count(*) as c  from  `queue_aliexpress_getorder2` where `times` < 10  AND `next_time` < '.$t);
		$dataReaderCount = $command->query();
		$count = $dataReaderCount->read();
		if($count['c'] > 0){
			//符合的条数大于0
			$page = 1;
			$pageSize = 100;
			$p = ceil($count['c']/$pageSize);
			do {
				//分页查询数据，是为了不让单次查询数据过大，导致内存耗尽
				$currentPage = ($page-1)*$pageSize;
				$command = $connection->createCommand('select * from  `queue_aliexpress_getorder2` where `times` < 10  AND `next_time` < '.$t.' limit '.$currentPage.','.$pageSize);                         
				$dataReaderRow = $command->query();
				//获取100条数据的结果集，做循环插入操作（回写主表）
				while(($row = $dataReaderRow->read())!==false) {
				    if($row['type'] == 2){
				    	$QAG_obj = new QueueAliexpressGetfinishorder();
				    }else{
				    	$QAG_obj = new QueueAliexpressGetorder();
				    }
					$QAG_obj->uid = $row['uid'];
					$QAG_obj->sellerloginid = $row['sellerloginid'];
					$QAG_obj->aliexpress_uid = $row['aliexpress_uid'];
					$QAG_obj->status = $row['status'];
					$QAG_obj->type = $row['type'];
					$QAG_obj->order_status = $row['order_status'];
					$QAG_obj->orderid = $row['orderid'];
					$QAG_obj->times = $row['times'];
					$QAG_obj->order_info = $row['order_info'];
					$QAG_obj->last_time = $row['last_time'];
					$QAG_obj->gmtcreate = $row['gmtcreate'];
					$QAG_obj->create_time = $row['create_time'];
					$QAG_obj->update_time = $row['update_time'];
					$QAG_obj->next_time = $row['next_time'];
					$saveRes = $QAG_obj->save(false);
                    if (!$saveRes) {
                        echo __FUNCTION__ . "STEP 1 : " . var_export($QAG_obj->errors, true);
                    }
					if($saveRes){
						$command = $connection->createCommand("delete from queue_aliexpress_getorder2 where id = ".$row['id']);
					    $command->execute();
					}
				}
				$page ++;
			}while($page <= $p);
			$hasGotRecord = true;
		}
		return $hasGotRecord;
	}


	/**
	 * 获取需要发送好评的队列
	 * 
	 */
	static function getListingPraise(){
		//Step 0, 检查当前本job版本，如果和自己的版本不一样，就自动exit吧
		$currentAliexpressGetOrderListVersion = ConfigHelper::getGlobalConfig("Order/aliexpressGetOrderListVersion",'NO_CACHE');
		if (empty($currentAliexpressGetOrderListVersion))
			$currentAliexpressGetOrderListVersion = 0;
		
		//如果自己还没有定义，去使用global config来初始化自己
		if (empty(self::$aliexpressGetOrderListVersion))
			self::$aliexpressGetOrderListVersion = $currentAliexpressGetOrderListVersion;
			
		//如果自己的version不同于全局要求的version，自己退出，从新起job来使用新代码
		if (self::$aliexpressGetOrderListVersion <> $currentAliexpressGetOrderListVersion){
			exit("Version new $currentAliexpressGetOrderListVersion , this job ver ".self::$aliexpressGetOrderListVersion." exits for using new version $currentAliexpressGetOrderListVersion.");
		}
		
		$connection = Yii::$app->db;
		$command = $connection->createCommand("select * from queue_aliexpress_praise where `status` <> 1 limit 5 ");
		$dataReader = $command->query();
		while(($row = $dataReader->read())!==false) {
			
		    $command = $connection->createCommand("update queue_aliexpress_praise set status = 1 where id = ".$row['id']." and status != 1");
		    $affectRows = $command->execute();
		    if ($affectRows <= 0){
		        continue;
		    }
		    $a = AliexpressInterface_Auth::checkToken ($row['sellerloginid']);
		    if($a){
		    	$api = new AliexpressInterface_Api ();
				$access_token = $api->getAccessToken ($row['sellerloginid']);
				//获取访问token失败
				if ($access_token === false){
					continue;
				}
				$api->access_token = $access_token;
				$param['orderId'] = $row['orderId'];
				$param['score'] = $row['score'];
				$param['feedbackContent'] = $row['feedbackContent'];
				$result = $api->saveSellerFeedback($param);

                                //错误信息记录
                                $error = ['code'=>0, 'msg'=>''];
                                if(isset($result['errorCode'])) {
                                    $error['code'] = $result['errorCode'];
                                    $error['msg'] = $result['errorMessage'];
                                }else if(isset($result['error_code'])) {
                                    $error['code'] = $result['error_code'];
                                    $error['msg'] = $result['error_message'];
                                }

				$QAPI = new QueueAliexpressPraiseInfo();
				$QAPI->orderId = $row['orderId'];
				$QAPI->score = $row['score'];
				$QAPI->feedbackContent = $row['feedbackContent'];
				$QAPI->sellerloginid = $row['sellerloginid'];
				$QAPI->errorCode = $error['code'];
				$QAPI->errorMessage = $error['msg'];
				if(isset($result['success']) && ($result['success'] == 1 || $result['success'] == true)){
					$success = 'true';
				}else{
					$success = 'false';
				}
				$QAPI->success = $success;
				$saveRes = $QAPI->save();
				if($saveRes){
				    $command = $connection->createCommand("delete from queue_aliexpress_praise where id = ".$row['id']);
				    $command->execute();
				}
		    }
		}
	}




    /**
     * 同步Aliexpress订单所有已完成30天的
     * @author 陈斌
     * 88028624@qq.com
     */
    static function getOrderListByFinishDay30(){
        //Step 0, 检查当前本job版本，如果和自己的版本不一样，就自动exit吧
        $currentAliexpressGetOrderListVersion = ConfigHelper::getGlobalConfig("Order/aliexpressGetOrderListVersion",'NO_CACHE');
        if (empty($currentAliexpressGetOrderListVersion))
            $currentAliexpressGetOrderListVersion = 'v1';

        //如果自己还没有定义，去使用global config来初始化自己
        if (empty(self::$aliexpressGetOrderListVersion))
            self::$aliexpressGetOrderListVersion = $currentAliexpressGetOrderListVersion;

        //如果自己的version不同于全局要求的version，自己退出，从新起job来使用新代码
        if (self::$aliexpressGetOrderListVersion <> $currentAliexpressGetOrderListVersion){
            exit("Version new $currentAliexpressGetOrderListVersion , this job ver ".self::$aliexpressGetOrderListVersion." exits for using new version $currentAliexpressGetOrderListVersion.");
        }
        $backgroundJobId=self::getCronJobId();
        $connection=Yii::$app->db;
        #########################
        $type = 'finish30';
        $hasGotRecord=false;
        $command=$connection->createCommand('select `id` from  `saas_aliexpress_autosync` where `is_active` = 1 AND `status` in (0,2,3) AND `times` < 10 AND `type`="'.$type.'" order by `last_time` ASC limit 5');
        #################################
        $dataReader=$command->query();
        while(($row=$dataReader->read())!==false) {
            //echo '<pre>';print_r($row);exit; //8614
            //1. 先判断是否可以正常抢到该记录
            $command = $connection->createCommand("update saas_aliexpress_autosync set status=1 where id =". $row['id']." and status<>1 ") ;
            $affectRows = $command->execute();
            if ($affectRows <= 0)	continue; //抢不到
            \Yii::info("aliexress_get_order_list_by_finish30 gotit jobid=$backgroundJobId start");
            //2. 抢到记录，设置同步需要的参数
            $hasGotRecord=true;
            $SAA_obj = SaasAliexpressAutosync::findOne($row['id']);
            // 检查授权是否过期或者是否授权,返回true，false
            $a = AliexpressInterface_Auth::checkToken ( $SAA_obj->sellerloginid );
            if ($a) {
                echo $SAA_obj->sellerloginid."\n";
                $api = new AliexpressInterface_Api ();
                $access_token = $api->getAccessToken ( $SAA_obj->sellerloginid );
                //获取访问token失败
                if ($access_token === false){
                    echo $SAA_obj->sellerloginid . 'not getting access token!' . "\n";
                    \Yii::info($SAA_obj->sellerloginid . 'not getting access token!' . "\n");
                    $SAA_obj->message = $SAA_obj->sellerloginid . ' not getting access token!';
                    $SAA_obj->status = 3;
                    $SAA_obj->times += 1;
                    $SAA_obj->last_time = time ();
                    $SAA_obj->update_time = time ();
                    $bool = $SAA_obj->save (false);
                    if(!$bool){
                        echo __FUNCTION__ . "STEP 1 : " . var_export($SAA_obj->errors, true);
                    }
                    continue;
                }
                $api->access_token = $access_token;
                $page = 1;
                $pageSize = 50;
                // 是否全部同步完成
                $success = true;
                $exit = false;
                #####################################
                $start_time = $SAA_obj->binding_time-(86400*30);
                $end_time = $SAA_obj->binding_time;
                if ($SAA_obj->start_time>$start_time){
                    $start_time = $SAA_obj->start_time;
                }

                ########################################
                do {
                    // 接口传入参数
                    $param = array (
                        'page' => (int)$page,
                        'pageSize' => $pageSize,
                    );
                    ###################################################
                    $param['createDateStart'] = self::getLaFormatTime ("m/d/Y H:i:s", $start_time );
                    $param['createDateEnd'] = self::getLaFormatTime ( "m/d/Y H:i:s", $end_time );
                    #######################################################
                    $param['orderStatus']='FINISH';
                    ####################################################
                    // 调用接口获取订单列表
                    $result = $api->findOrderListSimpleQuery($param);
                    // 判断是否有订单
                    if (isset ( $result ['totalItem'] )) {
                        echo $result ['totalItem']."\n";
                        if ($result ['totalItem'] > 0) {
                            // 保存数据到同步订单详情队列
                            foreach ( $result ['orderList'] as $one ) {
                                // 订单产生时间
                                $gmtCreate = AliexpressInterface_Helper::transLaStrTimetoTimestamp($one ['gmtCreate']);
                                //new 把finish的订单单独存在一张表里面
                                $QAG_finish = QueueAliexpressGetfinishorder::findOne(['orderid'=>$one ['orderId']]);
                                if (isset ( $QAG_finish )) {
                                    $QAG_finish->order_status = $one['orderStatus'];
                                    $QAG_finish->order_info = json_encode ( $one );
                                    $QAG_finish->update_time = time ();
                                    $bool = $QAG_finish->save (false);
                                    if(!$bool){
                                        echo __FUNCTION__ . "STEP 2 : " . var_export($QAG_finish->errors, true);
                                    }
                                } else {
                                    $QAG_finish = new QueueAliexpressGetfinishorder ();
                                    $QAG_finish->uid = $SAA_obj->uid;
                                    $QAG_finish->sellerloginid = $SAA_obj->sellerloginid;
                                    $QAG_finish->aliexpress_uid = $SAA_obj->aliexpress_uid;
                                    $QAG_finish->status = 0;
                                    $QAG_finish->type = 2;
                                    $QAG_finish->order_status = $one['orderStatus'];
                                    $QAG_finish->orderid = $one ['orderId'];
                                    $QAG_finish->times = 0;
                                    $QAG_finish->order_info = json_encode ( $one );
                                    $QAG_finish->last_time = 0;
                                    $QAG_finish->gmtcreate = $gmtCreate;
                                    $QAG_finish->create_time = time();
                                    $QAG_finish->update_time = time();
                                    $QAG_finish->next_time = time();
                                    $bool = $QAG_finish->save (false);
                                    if(!$bool){
                                        echo __FUNCTION__ . "STEP 3 : " . var_export($QAG_finish->errors, true);
                                    }
                                }
                            }
                        }else{
                            $exit = true;//当已完成订单数量为0时说明已完成订单已经同步完毕
                        }
                    } else {
                        $success = false;
                    }

                    $page ++;
                    $total = isset($result ['totalItem'])?$result ['totalItem']:0;
                    $p = ceil($total/50);
                } while ( $page <= $p );
                // 是否全部同步成功
                if ($success) {
                    $SAA_obj->end_time = $end_time;
                    $SAA_obj->start_time = $start_time;
                    $SAA_obj->last_time = time();
                    $SAA_obj->status = 4;//已完成订单全部同步
                    $SAA_obj->times = 0;
                    $bool = $SAA_obj->save (false);
                    if(!$bool){
                        echo __FUNCTION__ . "STEP 4 : " . var_export($QAG_finish->errors, true);
                    }
                } else {
                    $SAA_obj->message = isset($result ['error_message'])?$result ['error_message']:'接口返回结果错误'.print_r($result,true);
                    $SAA_obj->last_time = time();
                    $SAA_obj->status = 3;
                    $SAA_obj->times += 1;
                    $bool = $SAA_obj->save (false);
                    if(!$bool){
                        echo __FUNCTION__ . "STEP 5 : " . var_export($SAA_obj->errors, true);
                    }
                }
            } else {
                echo $SAA_obj->sellerloginid . ' Unauthorized or expired!' . "\n";
                $SAA_obj->message = $SAA_obj->sellerloginid . ' Unauthorized or expired!';
                $SAA_obj->last_time = time();
                $SAA_obj->status = 3;
                $SAA_obj->times += 1;
                $bool = $SAA_obj->save (false);
                if(!$bool){
                    echo __FUNCTION__ . "STEP 6 : " . var_export($SAA_obj->errors, true);
                }
            }
            \Yii::info("aliexress_get_order_list_by_finish30 gotit jobid=$backgroundJobId end");
        }
        return $hasGotRecord;
    }

    public function checkOrderStatus(){
        //Step 0, 检查当前本job版本，如果和自己的版本不一样，就自动exit吧
        $currentAliexpressGetOrderListVersion = ConfigHelper::getGlobalConfig("Order/aliexpressCheckOrderStatusVersion",'NO_CACHE');
        if (empty($currentAliexpressGetOrderListVersion))
            $currentAliexpressGetOrderListVersion = 'v1';

        //如果自己还没有定义，去使用global config来初始化自己
        if (empty(self::$aliexpressGetOrderListVersion))
            self::$aliexpressGetOrderListVersion = $currentAliexpressGetOrderListVersion;

        //如果自己的version不同于全局要求的version，自己退出，从新起job来使用新代码
        if (self::$aliexpressGetOrderListVersion <> $currentAliexpressGetOrderListVersion){
            exit("Version new $currentAliexpressGetOrderListVersion , this job ver ".self::$aliexpressGetOrderListVersion." exits for using new version $currentAliexpressGetOrderListVersion.");
        }
        $backgroundJobId=self::getCronJobId();
        $connection = Yii::$app->db_queue;
        //getorder
        \Yii::info("aliexress_check_orderstatus queue_aliexpress_getorder jobid=$backgroundJobId start","file");
        #######################
        $command = $connection->createCommand('select `sellerloginid`,orderid from  `queue_aliexpress_getorder` where times >= 10 group by sellerloginid asc order by sellerloginid desc limit 100');
        #######################
        $dataReader=$command->query();
        while(($row=$dataReader->read())!==false) {
            $log = "aliexress_get_order_check_status queue_aliexpress_getorder jobid= " .$backgroundJobId;
            $sellerloginid = $row['sellerloginid'];
            $orderid = $row['orderid'];
            $params = ['sellerloginid'=>$sellerloginid];
            //检查账号是否存在
            $aliexpress_account = \Yii::$app->db->createCommand('select * from saas_aliexpress_user where sellerloginid = "'.$sellerloginid.'"')->query()->read();
            $logTimeMS1 = TimeUtil::getCurrentTimestampMS();
            if($aliexpress_account === false){
                //删除对应速卖通账号下的所有队列信息
                $getorder_number = QueueAliexpressGetorder::deleteAll($params);
                $logTimeMS2 = TimeUtil::getCurrentTimestampMS();
                $log .= ",t2_1_1=".($logTimeMS2-$logTimeMS1);
                $getorder2_number = QueueAliexpressGetorder2::deleteAll($params);
                $logTimeMS2 = TimeUtil::getCurrentTimestampMS();
                $log .= ",t2_2_1=".($logTimeMS2-$logTimeMS1);
                $finish_number = QueueAliexpressGetfinishorder::deleteAll($params);
                $logTimeMS2 = TimeUtil::getCurrentTimestampMS();
                $log .= ",t2_3_1=".($logTimeMS2-$logTimeMS1);
                $log .= ", FINISHORDER={$finish_number}, GETORDER={$getorder_number}, GETORDER2={$getorder2_number}";
            }
            $log .= ",sellerloginid=".$sellerloginid;
            \Yii::info($log,"file");
        }
        //getorder2
        \Yii::info("aliexress_check_orderstatus queue_aliexpress_getorder2 jobid=$backgroundJobId start","file");
        #######################
        $command = $connection->createCommand('select `sellerloginid`,orderid from  `queue_aliexpress_getorder2` where times >= 10 group by sellerloginid asc order by sellerloginid desc limit 100');
        #######################
        $dataReader=$command->query();
        while(($row=$dataReader->read())!==false) {
            $log = "aliexress_get_order_check_status queue_aliexpress_getorder2 jobid= " .$backgroundJobId;
            $sellerloginid = $row['sellerloginid'];
            $orderid = $row['orderid'];
            $params = ['sellerloginid'=>$sellerloginid];
            //检查账号是否存在
            $aliexpress_account = \Yii::$app->db->createCommand('select * from saas_aliexpress_user where sellerloginid = "'.$sellerloginid.'"')->query()->read();
            $logTimeMS1 = TimeUtil::getCurrentTimestampMS();
            if($aliexpress_account === false){
                //删除对应速卖通账号下的所有队列信息
                $getorder2_number = QueueAliexpressGetorder2::deleteAll($params);
                $logTimeMS2 = TimeUtil::getCurrentTimestampMS();
                $log .= ",t2_4_1=".($logTimeMS2-$logTimeMS1);
                $getorder_number = QueueAliexpressGetorder::deleteAll($params);
                $logTimeMS2 = TimeUtil::getCurrentTimestampMS();
                $log .= ",t2_5_1=".($logTimeMS2-$logTimeMS1);
                $finish_number = QueueAliexpressGetfinishorder::deleteAll($params);
                $logTimeMS2 = TimeUtil::getCurrentTimestampMS();
                $log .= ",t2_6_1=".($logTimeMS2-$logTimeMS1);
                $log .= ", FINISHORDER={$finish_number}, GETORDER={$getorder_number}, GETORDER2={$getorder2_number}";
            }
            $log .= ",sellerloginid=".$sellerloginid;
            \Yii::info($log,"file");
        }

        //getorder2
        \Yii::info("aliexress_check_orderstatus queue_aliexpress_getfinishorder jobid=$backgroundJobId start","file");
        #######################
        $command = $connection->createCommand('select `sellerloginid`,orderid from  `queue_aliexpress_getfinishorder` where times >= 10 group by sellerloginid asc order by sellerloginid desc limit 100');
        #######################
        $dataReader=$command->query();
        while(($row=$dataReader->read())!==false) {
            $log = "aliexress_get_order_check_status queue_aliexpress_getfinishorder jobid= " .$backgroundJobId;
            $sellerloginid = $row['sellerloginid'];
            $orderid = $row['orderid'];
            $params = ['sellerloginid'=>$sellerloginid];
            //检查账号是否存在
            $aliexpress_account = \Yii::$app->db->createCommand('select * from saas_aliexpress_user where sellerloginid = "'.$sellerloginid.'"')->query()->read();
            $logTimeMS1 = TimeUtil::getCurrentTimestampMS();
            if($aliexpress_account === false){
                //删除对应速卖通账号下的所有队列信息
                $finish_number = QueueAliexpressGetfinishorder::deleteAll($params);
                $logTimeMS2 = TimeUtil::getCurrentTimestampMS();
                $log .= ",t2_7_1=".($logTimeMS2-$logTimeMS1);
                $getorder_number = QueueAliexpressGetorder::deleteAll($params);
                $logTimeMS2 = TimeUtil::getCurrentTimestampMS();
                $log .= ",t2_8_1=".($logTimeMS2-$logTimeMS1);
                $getorder2_number = QueueAliexpressGetorder2::deleteAll($params);
                $logTimeMS2 = TimeUtil::getCurrentTimestampMS();
                $log .= ",t2_9_1=".($logTimeMS2-$logTimeMS1);
                $log .= ", FINISHORDER={$finish_number}, GETORDER={$getorder_number}, GETORDER2={$getorder2_number}";
            }
            $log .= ",sellerloginid=".$sellerloginid;
            \Yii::info($log,"file");
        }
        return false;
    }


    public function checkTokenStatus(){
        //Step 0, 检查当前本job版本，如果和自己的版本不一样，就自动exit吧
        $currentAliexpressGetOrderListVersion = ConfigHelper::getGlobalConfig("Order/aliexpressCheckTokenStatusVersion",'NO_CACHE');
        if (empty($currentAliexpressGetOrderListVersion))
            $currentAliexpressGetOrderListVersion = 'v1';

        //如果自己还没有定义，去使用global config来初始化自己
        if (empty(self::$aliexpressGetOrderListVersion))
            self::$aliexpressGetOrderListVersion = $currentAliexpressGetOrderListVersion;

        //如果自己的version不同于全局要求的version，自己退出，从新起job来使用新代码
        if (self::$aliexpressGetOrderListVersion <> $currentAliexpressGetOrderListVersion){
            exit("Version new $currentAliexpressGetOrderListVersion , this job ver ".self::$aliexpressGetOrderListVersion." exits for using new version $currentAliexpressGetOrderListVersion.");
        }
        $backgroundJobId=self::getCronJobId();
        $connection = Yii::$app->db_queue;

        $api = new AliexpressInterface_Api ();

        #########################getorder##########################################
        \Yii::info("aliexpress_check_token_status queue_aliexpress_checkorder jobid=$backgroundJobId start","file");
        ###获取数据and (message like '%Unauthorized or expired%' or message like '%not getting access token%')
        $command = $connection->createCommand("select sellerloginid,orderid from queue_aliexpress_getorder where times >= 10 group by sellerloginid asc limit 100");
        $dataReader=$command->query();
        while(($row=$dataReader->read())!==false) {
            $logTimeMS1 = TimeUtil::getCurrentTimestampMS();
            $log = "aliexpress_check_token_status queue_aliexpress_checkorder jobid= " .$backgroundJobId;
            $log .= " sellerloginid=".$row['sellerloginid'];
            //获取用户信息
            $aliexpress_account = self::_getAliexpressToken($row['sellerloginid']);
            $logTimeMS2 = TimeUtil::getCurrentTimestampMS();
            $log .= " t2_1 = " . ($logTimeMS2 - $logTimeMS1);

            if($aliexpress_account === false){
                self::_saveOrderInfo('queue_aliexpress_getorder',$row['sellerloginid']);
                $logTimeMS2 = TimeUtil::getCurrentTimestampMS();
                $log .= " t2_1_1 = ".($logTimeMS2 - $logTimeMS1);
                \Yii::info("aliexpress_check_token_status queue_aliexpress_checkorder account_faile sellerloginid : {$row['sellerloginid']}","file");
            } else {
                //第一次调用API，查看TOKEN是否可用
                $bool = self::_checkOrderInfo($api,$aliexpress_account,$row['orderid']);
                $logTimeMS3 = TimeUtil::getCurrentTimestampMS();
                $log .= " t3_2 = " . ($logTimeMS3 - $logTimeMS2);
                if($bool === false){
                    //获取TOKEN
                    $aliexpress_bool = self::_getAccessToken($aliexpress_account,$api);
                    $logTimeMS4 = TimeUtil::getCurrentTimestampMS();
                    $log .= " t4_3 ".($logTimeMS4 - $logTimeMS3);
                    if($aliexpress_bool !== false){
                        $aliexpress_account['access_token'] = $aliexpress_bool['access_token'];
                        $bool = self::_checkOrderInfo($api,$aliexpress_account,$row['orderid']);
                        $logTimeMS5 = TimeUtil::getCurrentTimestampMS();
                        $log .= " t5_4 = ".($logTimeMS5 - $logTimeMS4);
                        if($bool === false){
                            //执行删除操作
                            self::_saveOrderInfo('queue_aliexpress_getorder',$row['sellerloginid']);
                            $logTimeMS6 = TimeUtil::getCurrentTimestampMS();
                            $log .= " t6_1_5 = ".($logTimeMS6 - $logTimeMS5);
                        } else {
                            //执行恢复操作
                            self::_recoveryOrderInfo('queue_aliexpress_getorder',$row['sellerloginid']);
                            $logTimeMS7 = TimeUtil::getCurrentTimestampMS();
                            $log .= " t7_1_5 = ".($logTimeMS7 - $logTimeMS5);
                        }
                    } else {
                        //执行删除操作
                        self::_saveOrderInfo('queue_aliexpress_getorder',$row['sellerloginid']);
                        $logTimeMS8 = TimeUtil::getCurrentTimestampMS();
                        $log .= " t8_1_4 = ".($logTimeMS8 - $logTimeMS4);
                    }
                } else {
                    //执行恢复操作
                    self::_recoveryOrderInfo('queue_aliexpress_getorder',$row['sellerloginid']);
                    $logTimeMS9 = TimeUtil::getCurrentTimestampMS();
                    $log .= " t9_1_3 = ".($logTimeMS9 - $logTimeMS3);
                }
            }
            \Yii::info($log,"file");
        }
        \Yii::info("aliexpress_check_token_status queue_aliexpress_checkorder jobid=$backgroundJobId end","file");
        #########################getorder##########################################


        #########################getorder2##########################################
        ###获取数据
//        \Yii::info("aliexpress_check_token_status queue_aliexpress_checkorder2 jobid=$backgroundJobId start","file");
//        $command = $connection->createCommand("select sellerloginid,orderid from queue_aliexpress_getorder2 where times >= 10 group by sellerloginid asc limit 100");
//        $dataReader=$command->query();
//        while(($row=$dataReader->read())!==false) {
//            $logTimeMS1 = TimeUtil::getCurrentTimestampMS();
//            $log = "aliexpress_check_token_status queue_aliexpress_checkorder2 jobid= " .$backgroundJobId;
//            $log .= " sellerloginid=".$row['sellerloginid'];
//            //获取用户信息
//            $aliexpress_account = self::_getAliexpressToken($row['sellerloginid']);
//            $logTimeMS2 = TimeUtil::getCurrentTimestampMS();
//            $log .= " t2_1 = ".($logTimeMS2 - $logTimeMS1);
//            if($aliexpress_account === false){
//                self::_saveOrderInfo('queue_aliexpress_getorder2',$row['sellerloginid']);
//                $logTimeMS2 = TimeUtil::getCurrentTimestampMS();
//                $log .= " t2_1_1 = ".($logTimeMS2 - $logTimeMS1);
//                \Yii::info("aliexpress_check_token_status queue_aliexpress_checkorder2 account_faile sellerloginid : {$row['sellerloginid']}","file");
//            } else {
//                //第一次调用API，查看TOKEN是否可用
//                $bool = self::_checkOrderInfo($api,$aliexpress_account,$row['orderid']);
//                $logTimeMS3 = TimeUtil::getCurrentTimestampMS();
//                $log .= " t3_2 = ".($logTimeMS3 - $logTimeMS2);
//                if($bool === false){
//                    //获取TOKEN
//                    $aliexpress_bool = self::_getAccessToken($aliexpress_account,$api);
//                    $logTimeMS4 = TimeUtil::getCurrentTimestampMS();
//                    $log .= " t4_3 = ".($logTimeMS4 - $logTimeMS3);
//                    if($aliexpress_bool !== false){
//                        $aliexpress_account['access_token'] = $aliexpress_bool['access_token'];
//                        $bool = self::_checkOrderInfo($api,$aliexpress_account,$row['orderid']);
//                        $logTimeMS5 = TimeUtil::getCurrentTimestampMS();
//                        $log .= " t5_4 = ".($logTimeMS5 - $logTimeMS4);
//                        if($bool === false){
//                            //执行删除操作
//                            self::_saveOrderInfo('queue_aliexpress_getorder2',$row['sellerloginid']);
//                            $logTimeMS6 = TimeUtil::getCurrentTimestampMS();
//                            $log .= " t6_2_5 = ".($logTimeMS6 - $logTimeMS5);
//                        } else {
//                            //执行恢复操作
//                            self::_recoveryOrderInfo('queue_aliexpress_getorder2',$row['sellerloginid']);
//                            $logTimeMS7 = TimeUtil::getCurrentTimestampMS();
//                            $log .= " t7_2_5 = ".($logTimeMS7 - $logTimeMS5);
//                        }
//                    } else {
//                        //执行删除操作
//                        self::_saveOrderInfo('queue_aliexpress_getorder2',$row['sellerloginid']);
//                        $logTimeMS8 = TimeUtil::getCurrentTimestampMS();
//                        $log .= " t8_2_4 = ".($logTimeMS8 - $logTimeMS4);
//                    }
//                } else {
//                    //执行恢复操作
//                    self::_recoveryOrderInfo('queue_aliexpress_getorder2',$row['sellerloginid']);
//                    $logTimeMS9 = TimeUtil::getCurrentTimestampMS();
//                    $log .= " t9_2_3 = ".($logTimeMS9 - $logTimeMS3);
//                }
//            }
//            \Yii::info($log,"file");
//        }
//        \Yii::info("aliexpress_check_token_status queue_aliexpress_checkorder2 jobid=$backgroundJobId end","file");
        #########################getorder2##########################################

        #########################getfinishorder##########################################
        \Yii::info("aliexpress_check_token_status queue_aliexpress_checkfinishorder jobid=$backgroundJobId start","file");
        $command = $connection->createCommand("select sellerloginid,orderid from queue_aliexpress_getfinishorder where times >= 10 group by sellerloginid asc limit 100");
        $dataReader=$command->query();
        while(($row=$dataReader->read())!==false) {
            $logTimeMS1 = TimeUtil::getCurrentTimestampMS();
            $log = "aliexpress_check_token_status queue_aliexpress_checkfinishorder jobid= " .$backgroundJobId;
            $log .= " sellerloginid=".$row['sellerloginid'];
            //获取用户信息
            $aliexpress_account = self::_getAliexpressToken($row['sellerloginid']);
            $logTimeMS2 = TimeUtil::getCurrentTimestampMS();
            $log .= " t2_1 = ".($logTimeMS2 - $logTimeMS1);
            if($aliexpress_account === false){
                self::_saveOrderInfo('queue_aliexpress_getfinishorder',$row['sellerloginid']);
                $logTimeMS2 = TimeUtil::getCurrentTimestampMS();
                $log .= " t2_1_1 = ".($logTimeMS2 - $logTimeMS1);
                \Yii::info("aliexpress_check_token_status queue_aliexpress_checkfinishorder account_faile sellerloginid : {$row['sellerloginid']}","file");
            } else {
                //第一次调用API，查看TOKEN是否可用
                $bool = self::_checkOrderInfo($api,$aliexpress_account,$row['orderid']);
                $logTimeMS3 = TimeUtil::getCurrentTimestampMS();
                $log .= " t3_2 = ".($logTimeMS3 - $logTimeMS2);
                if($bool === false){
                    //获取TOKEN
                    $aliexpress_bool = self::_getAccessToken($aliexpress_account,$api);
                    $logTimeMS4 = TimeUtil::getCurrentTimestampMS();
                    $log .= " t4_3 = ".($logTimeMS4 - $logTimeMS3);
                    if($aliexpress_bool !== false){
                        $aliexpress_account['access_token'] = $aliexpress_bool['access_token'];
                        $bool = self::_checkOrderInfo($api,$aliexpress_account,$row['orderid']);
                        $logTimeMS5 = TimeUtil::getCurrentTimestampMS();
                        $log .= " t5_4 = ".($logTimeMS5 - $logTimeMS4);
                        if($bool === false){
                            //执行删除操作
                            self::_saveOrderInfo('queue_aliexpress_getfinishorder',$row['sellerloginid']);
                            $logTimeMS6 = TimeUtil::getCurrentTimestampMS();
                            $log .= " t6_3_5 = ".($logTimeMS6 - $logTimeMS5);
                        } else {
                            //执行恢复操作
                            self::_recoveryOrderInfo('queue_aliexpress_getfinishorder',$row['sellerloginid']);
                            $logTimeMS7 = TimeUtil::getCurrentTimestampMS();
                            $log .= " t7_3_5 = ".($logTimeMS7 - $logTimeMS5);
                        }
                    } else {
                        //执行删除操作
                        self::_saveOrderInfo('queue_aliexpress_getfinishorder',$row['sellerloginid']);
                        $logTimeMS8 = TimeUtil::getCurrentTimestampMS();
                        $log .= " t8_3_4 = ".($logTimeMS8 - $logTimeMS4);
                    }
                } else {
                    //执行恢复操作
                    self::_recoveryOrderInfo('queue_aliexpress_getfinishorder',$row['sellerloginid']);
                    $logTimeMS9 = TimeUtil::getCurrentTimestampMS();
                    $log .= " t9_3_3 = ".($logTimeMS9 - $logTimeMS3);
                }
            }
            \Yii::info($log,"file");
        }

        \Yii::info("aliexpress_check_token_status queue_aliexpress_checkfinishorder jobid=$backgroundJobId end","file");
        #########################getfinishorder##########################################
        return false;
    }

    /**
     * 恢复异常信息订单
     * @param $table
     * @param $sellerloginid
     * @return array
     */
    private function _recoveryOrderInfo($table,$sellerloginid){

        //
        $finish_sql = "UPDATE `{$table}` SET `message`='',`status`=0,`times`=0 WHERE sellerloginid = '{$sellerloginid}' AND `times` >= 10 AND `type` = 2";
        $finish_number = \Yii::$app->db_queue->createCommand($finish_sql)->execute();
        \Yii::info("aliexpress_check_token_status save_{$table}_abnormalorder, SQL : ({$finish_sql}), EXECUTE : {$finish_number}","file");

        $new_sql = "UPDATE `{$table}` SET `message`='',`status`=0,`times`=0 WHERE sellerloginid = '{$sellerloginid}' AND `times` >= 10 AND `type` = 3";
        $new_number = \Yii::$app->db_queue->createCommand($new_sql)->execute();
        \Yii::info("aliexpress_check_token_status save_{$table}_neworder, SQL : ({$new_sql}), EXECUTE : {$new_number}","file");

        $old_sql = "UPDATE `{$table}` SET `message`='',`status`=0,`times`=0 WHERE sellerloginid = '{$sellerloginid}' AND `times` >= 10 AND `type` = 5";
        $old_number = \Yii::$app->db_queue->createCommand($old_sql)->execute();
        \Yii::info("aliexpress_check_token_status save_{$table}_oldorder, SQL : ({$old_sql}), EXECUTE : {$old_number}","file");

        $abnormal_sql = "UPDATE `{$table}` SET `type`=3,`message`='',`status`=0,`times`=0 WHERE sellerloginid = '{$sellerloginid}' AND `times` >= 10 AND `type` = 11";
        $abnormal_number = \Yii::$app->db_queue->createCommand($abnormal_sql)->execute();
        \Yii::info("aliexpress_check_token_status save_{$table}_abnormalorder, SQL : ({$abnormal_sql}), EXECUTE : {$abnormal_number}","file");


        return ;
    }

    private function _saveOrderInfo($table,$sellerloginid){
        $command = \Yii::$app->db_queue->createCommand("select * from {$table} WHERE sellerloginid = '{$sellerloginid}'");
        $order_info = $command->query();
        while(($row=$order_info->read())!==false) {
            $sql = "select * from queue_aliexpress_check_token_order where id = " . $row['id'];
            $check_info = \Yii::$app->db_queue->createCommand($sql)->query()->read();
            if($check_info === false){
                //移动数据
                $command = \Yii::$app->db_queue->createCommand()->insert('queue_aliexpress_check_token_order',$row);
                $command->execute();
                \Yii::info("aliexpress_check_token_status insert_queue_aliexpress_check_token_order, SQL : ({$command->getRawSql()})","file");
            } else {
                $params = $row;
                unset($params['id']);
                $command = \Yii::$app->db_queue->createCommand()->update('queue_aliexpress_check_token_order',$params,['id'=>$row['id']]);
                $command->execute();
                \Yii::info("aliexpress_check_token_status update_queue_aliexpress_check_token_order, SQL : ({$command->getRawSql()})","file");
            }
            \Yii::$app->db_queue->createCommand()->delete($table,['id' => $row['id']])->execute();
            \Yii::info("aliexpress_check_token_status delete_order_id  : ({$row['id']})","file");
        }
    }

    /**
     * @param $sellerloginid
     * @return array
     */
    private function _getAliexpressToken($sellerloginid){
        return \Yii::$app->db->createCommand('select * from saas_aliexpress_user where sellerloginid = "'.$sellerloginid.'"')->query()->read();
    }

    private function _getAccessToken($aliexpress_account,&$api){
        $refresh_token_timeout = $aliexpress_account['refresh_token_timeout'];
        $current_time = time();

        $api->access_token = $aliexpress_account['access_token'];

        $api->setAppInfo($aliexpress_account['app_key'],$aliexpress_account['app_secret']);
        if($refresh_token_timeout < $current_time){//refresh_token已经过期
            $day = ($current_time - $refresh_token_timeout)/86400;//过期多少天
            if($day < 30){
                $rtn = $api->postponeToken($aliexpress_account['refresh_token'] , $api->access_token);//换取新的refreshToken
                if(isset($rtn['refresh_token'])){
                    $params = [
                        'refresh_token' => $rtn['refresh_token'],
                        'refresh_token_timeout' => AliexpressInterface_Helper::transLaStrTimetoTimestamp($rtn['refresh_token_timeout']),
                        'access_token' => $rtn['access_token'],
                        'access_token_timeout' => (time() + 28800), // 8 小时过期
                    ];
                    \Yii::$app->db->createCommand()->update('saas_aliexpress_user',$params,['aliexpress_uid' => $aliexpress_account['aliexpress_uid']])->execute();
                    \Yii::info("aliexpress_check_token_status postponeToken success, sellerloginid = {$aliexpress_account['sellerloginid']} , result :" . json_encode($rtn,true),"file");
                } else {
                    \Yii::info("aliexpress_check_token_status get_refresh_token fail, sellerloginid = {$aliexpress_account['sellerloginid']} ,error_msg :" . json_encode($rtn,true),"file");
                    return false;
                }
            } else {
                \Yii::info("aliexpress_check_token_status get_refresh_token fail, sellerloginid = {$aliexpress_account['sellerloginid']} , error_msg  day > {$day}","file");
                return false;
            }
        } else {
            //直接通过refresh_token获取新的access_token
            $rtn = $api->refreshTokentoAccessToken($aliexpress_account['refresh_token']);
            if(isset($rtn['access_token'])){
                $params = [
                    'access_token' => $rtn['access_token'],
                    'access_token_timeout' => (time() + 28800), // 8 小时过期
                ];
                \Yii::$app->db->createCommand()->update('saas_aliexpress_user',$params,['aliexpress_uid' => $aliexpress_account['aliexpress_uid']])->execute();
                \Yii::info("aliexpress_check_token_status refreshTokentoAccessToken success, sellerloginid = {$aliexpress_account['sellerloginid']} , result :" . json_encode($rtn,true),"file");
            } else {
                \Yii::info("aliexpress_check_token_status get_access_token fail, sellerloginid = {$aliexpress_account['sellerloginid']} , error_msg :" . json_encode($rtn,true),"file");
                return false;
            }
        }
        return $rtn;
    }
    /**
     * @param $api
     * @param $row
     * @param $orderid
     * @return bool
     */
    private function _checkOrderInfo(&$api,$row,$orderid){
        $api->access_token = $row['access_token'];
        $api->AppKey = $row['app_key'];
        $api->appSecret = $row['app_secret'];
        // 接口传入参数速卖通订单号
        $param = array (
            'orderId' => $orderid
        );
        // 调用接口获取订单列表
        $api->setAppInfo($row['app_key'],$row['app_secret']);
        $result =  $api->findOrderById ( $param );
        \Yii::info("queue_aliexpress_check_token_status, sellerloginid = {$row['sellerloginid']}, api_result : " . json_encode($result,true),"file");
        if(isset($result['error_message'])){
            if($result['error_message'] == 'Request need user authorized'){
                return false;
            } else {
                return true;
            }
        } else {
            return true;
        }
    }
}
