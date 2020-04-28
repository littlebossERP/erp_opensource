<?php
namespace console\helpers;
use \Yii;
use eagle\models\SaasNeweggUser;
use eagle\modules\util\helpers\ConfigHelper;
use common\api\newegginterface\NeweggInterface_Helper;
use eagle\modules\util\helpers\TimeUtil;
use eagle\modules\util\helpers\ResultHelper;
use eagle\models\SysCountry;
use eagle\modules\order\helpers\OrderHelper;
use Qiniu\json_decode;
use eagle\models\SaasNeweggAutosync;
use eagle\models\QueueNeweggGetorder;
use eagle\modules\order\helpers\NeweggApiHelper;
use eagle\modules\util\helpers\MailHelper;

class SaasNeweggAutoFetchApiHelper{
	public static $cronJobId = 0;
	private static $neweggGetOrderListVersion = null;
	private static $timespan = 1800;//半个小时
	
	//newegg平台的状态跟eagle的订单状态的对应关系
	public static $NEWEGG_EAGLE_ORDER_STATUS_MAP = array(
			'Unshipped' => 200, //买家已付款
			'Partially Shipped' => 200, //部分发货
			'Shipped' => 500,//卖家已发货
			'Invoiced'=>500,//发票
			'Voided'=>600,
	);
	
	private static $needConfirmOrderList = [];
	
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
	 * 先判断是否真的抢到待处理的autoSync记录
	 */
	private static function _lockNeweggAutoSync($site_id, $type){
		$nowTime=time();
		$connection=Yii::$app->db;
		$command = $connection->createCommand("update saas_newegg_autosync set status=1, last_start_time=$nowTime where site_id =". $site_id." and type='".$type."' and is_active=1 and status<>1 ") ;
		$affectRows = $command->execute();
		if ($affectRows <= 0)	return null; //抢不到---如果是多进程的话，有抢不到的情况
		// 抢到记录
		$SAA_autoSync = SaasNeweggAutosync::findOne(['site_id'=>$site_id, 'type'=>$type]);
		return $SAA_autoSync;
	}
	
	/**
	 * 该进程判断是否需要退出
	 * 通过配置全局配置数据表ut_global_config_data的Order/neweggGetOrderVersion 对应数值
	 *
	 * @return  true or false
	 */
	private static function checkNeedExitNot(){
		$neweggGetOrderVersionFromConfig = ConfigHelper::getGlobalConfig("Order/neweggGetOrderVersion",'NO_CACHE');
		if (empty($neweggGetOrderVersionFromConfig))  {
			//数据表没有定义该字段，不退出。
			//	self::$lazadaGetOrderListVersion ="v0";
			return false;
		}
	
		//如果自己还没有定义，去使用global config来初始化自己
		if (self::$neweggGetOrderListVersion===null)	self::$neweggGetOrderListVersion = $neweggGetOrderVersionFromConfig;
			
		//如果自己的version不同于全局要求的version，自己退出，从新起job来使用新代码
		if (self::$neweggGetOrderListVersion <> $neweggGetOrderVersionFromConfig){
			echo "Version new $neweggGetOrderVersionFromConfig , this job ver ".self::$neweggGetOrderListVersion." exits \n";
			return true;
		}
		return false;
	}
	
	/**
	 * 拉取订单
	 * @param $type 订单拉取类型 1:新订单， 2:旧订单(Unshipped)， 3:旧订单(Partially Shipped)， 4:旧订单(Shipped)
	 * @param $STATUS 不填则按照$type进行正常拉取， 否则强制设置为只拉取当前指定状态的订单 
	 * 	0:Unshipped， 1:Partially Shippe， 2:Shipped，3:Invoiced，4:Voided
	 * 
	 * @author winton 2016/07/27
	 */
	public static function getOrderList($type, $STATUS='',$testing=false){
		
		//job出现问题的通知机制,监测开始
		\eagle\modules\dash_board\helpers\DashBoardHelper::WatchMeUp('NeweggGetOrderType'.$type,30,'akirametero@vip.qq.com');
		
		echo "++++++++++++getNeweggOrderList \n";
		//1. 检查当前本job版本，如果和自己的版本不一样，就自动exit吧
		$ret=self::checkNeedExitNot();
		if ($ret===true) exit;
		
		$backgroundJobId=self::getCronJobId(); //获取进程id号，主要是为了打印log
		
		$connection=\Yii::$app->db;
		$hasGotRecord=false;//是否抢到账号
		
		//2. 从账户同步表（订单列表同步表提取带同步的账号。
		$hasGotRecord=false;
		
		$nowTime = time();
		$Time = $nowTime-self::$timespan;
// 		$Time = date("Y-m-d H:i:s", $nowTime);
		
		// 同步状态:  status 0--没处理过, 1--执行中, 2--完成, 3--上一次执行有问题
		//正常拉取订单，时间窗口
		if($type == 1){
			$sql1 ='select `site_id` from  `saas_newegg_autosync` where `is_active` = 1 AND `status` <>1 AND `type`="'.$type.'" '.
					'AND ((`error_times` < 10 AND `last_finish_time`< "'.$Time.'") or (`error_times` >= 10 AND `last_finish_time`< "'.($nowTime-7200).'") )';
		}
		else if($type == 2 || $type == 3 || $type == 4){//拉取旧订单，正在执行和已经执行成功的就不再执行了
			$sql1 ='select `site_id` from  `saas_newegg_autosync` where `is_active` = 1 AND `status` <>1 AND `status` <>2 AND `type`="'.$type.'" '.
					'AND `error_times` < 10';	 
		}
		
		if ($testing){
			$sql1 ='select `site_id` from  `saas_newegg_autosync` where site_id=9  AND `type`="'.$type.'" ';
		}
		
		$command=$connection->createCommand($sql1);
		echo "Do $type, the sql is : $sql1 \n";
		
		$dataReader=$command->query();
		while(($row=$dataReader->read())!==false) {
			echo "++++++++dataReader->read() \n";
			
			//如果为正常同步，则先看看同步旧单的job做完没有，如果没有，则先跳过。等拉取旧单OK之后再做，避免同时拉单导致重复写入
			//加入一个时间条件,避免拉旧单的job莫名死左导致整个啦新单job一直不启动
			// 3600*24*2 时间太长，导致新单任务两天不运行，修改为2小时
			if($type==1){
				$unFinishJob = SaasNeweggAutosync::find()->where(['is_active'=>1,'status'=>[0,1],'site_id'=>$row['site_id'] ])->andWhere("type<>1 and last_finish_time > ".(time()-3600*2) )->asArray()->all();
				if(count($unFinishJob)>0){
				    echo "skip until other job done stie_id=".$row['site_id'].".\n";
					continue;
				}
			}
			// 先判断是否真的抢到待处理的autoSync记录
			echo "++++++++_lockNeweggAutoSync stie_id=".$row['site_id']." type $type \n";
			$SAA_autoSync = self::_lockNeweggAutoSync($row['site_id'], $type);
			if ($SAA_autoSync===null) {
				echo "++++++failed to lock this record, skip this one ".$row['site_id']." type $type \n";
				continue; //抢不到---如果是多进程的话，有抢不到的情况
			}
			
			// 先判断是否真的抢到待处理的autoSync记录
			$SAA_obj = SaasNeweggUser::findOne($row['site_id']);
			echo "++++++++SaasNeweggUser::findOne ".$row['site_id']." type $type \n";
			if ($SAA_obj===null) continue; //抢不到---如果是多进程的话，有抢不到的情况
			
			$hasGotRecord=true;  // 抢到记录
			
			//3. 调用渠道的api来获取对应账号的订单列表信息
			
			//3.1 整理请求参数
			$token=array(
					"SellerID"=>$SAA_obj->SellerID,
					"Authorization"=>$SAA_obj->Authorization,
					"SecretKey"=>$SAA_obj->SecretKey,
			);
			
			$nowTime = time();
			//为了避免由于服务器的时间不准确导致尝试拉取超前时间点的订单。这里end_time主动往前移5分钟。
			$nowTime=$nowTime-300;
			$nowTime_date_format = date("Y-m-d H:i:s", $nowTime);
			
			$SAA_obj_binding_time = self::_PRCToPacific(strtotime($SAA_obj->create_time));//太平洋时间
			
			//拉取新订单
			if($type == 1){
				
				if ((int)$SAA_autoSync->last_finish_time == 0){
					$start_time = $SAA_obj->create_time - 3600*24*14;
					$end_time = $nowTime;
				}else {
					$start_time = $SAA_autoSync->last_finish_time;
					$end_time = $nowTime;
				}
				
				//yzq 为了时间覆盖广一些，噶start time 前移15小时
				//$start_time -= 60*60*15; 
				
				//echo "type 1 start time $start_time , end time $end_time \n";
				
				echo "type $type start time $start_time , end time $end_time \n";
				
				$start_timeStr = self::_PRCToPacific($start_time );
				$end_timeStr = self::_PRCToPacific($end_time);'';
				$status = '';
				$downloaded = 0;
			}
			//拉取旧订单Unshipped
			else if($type == 2){
				$status = 0;
				$start_time=0;
				$start_timeStr = '';
				$end_time = $SAA_obj->create_time;
				$end_timeStr = $SAA_obj_binding_time;
				$downloaded = 0;
				//$start_time ='2016-10-10 20:00:32';//ys test
				//$end_time ='2016-10-20 20:00:32';//ys test
				
				echo "type $type start time $start_time , end time $end_time \n";
			}
			//拉取旧订单Partially Shipped
			else if($type == 3){
				$status = 1;
				$start_time=0;
				$start_timeStr = '';
				$end_time = $SAA_obj->create_time;
				$end_timeStr = $SAA_obj_binding_time;
				$downloaded = 0;
			}
			//拉取旧订单Shipped
			else if($type == 4){
				$status = 2;
				$start_time=0;
				$start_timeStr = '';
				$end_time = $SAA_obj->create_time;
				$end_timeStr = $SAA_obj_binding_time;
				$downloaded = 0;
			}
			
			//req参数
			$reqParams = [
				'start_time' => $start_timeStr,
				'end_time' => $end_timeStr,
				'PageIndex' => 1,
				'fetchType' => $type,
				'Status' => $status,
				'downloaded' => $downloaded,
			];
			
			//如果需要强制设置为只拉取当前指定状态的订单 
			if($STATUS != '' && in_array($STATUS, [0,1,2,3,4])){
				$reqParams['Status'] = $STATUS;
			}
			
			//3.2 访问api接口并保存结果
			try{
				$ret = self::_getOrderListAndSaveToEagle($token, $SAA_obj->uid, $reqParams);
			}catch (\Exception $e){
				print_r($e);
                $errorMessage = "file:" . $e->getFile() . " line:" . $e->getLine() . " message:" . $e->getMessage();
				echo $errorMessage.PHP_EOL ;
				$SAA_autoSync->message = $e->getMessage();
				$SAA_autoSync->error_times=$SAA_autoSync->error_times+1;
				$SAA_autoSync->status=3;
				$SAA_autoSync->update_time=$nowTime;//上一次运行时间
				if(!$SAA_autoSync->save()){
				    // TODO newegg console job monitor emails
					MailHelper::sendMailBySQ('xxx@xxx.com', '小老板后台job监测', 'xxx@xxx2.com', '订单同步状态回写失败', 'Newegg同步订单 time='.date("Y-m-d H:i:s",time()).' type='.$type.', uid='.$SAA_autoSync->uid.' 的时候，状态回写失败，请查看后台日志！');
				}
				continue;
			}
			if ($testing){
				echo "Got result $ret \n";
			}
			
			$ret = json_decode($ret, true);
			//存在失败
			if($ret['code'] != 200){
			    // dzt20200402 授权问题，停止同步
			    if("The specified seller id is invalid or you have not yet got the authorization from this seller." == $ret['message']){
			        $SAA_autoSync->is_active = 0;
			    }
			    
				$SAA_autoSync->message=$ret['message'];
				$SAA_autoSync->error_times=$SAA_autoSync->error_times+1;
				$SAA_autoSync->status=3;
				$SAA_autoSync->update_time=$nowTime;//上一次运行时间
				if(!$SAA_autoSync->save()){
				    // TODO newegg console job monitor emails
					MailHelper::sendMailBySQ('xxx@xxx.com', '小老板后台job监测', 'xxx@xxx2.com', '订单同步状态回写失败', 'Newegg同步订单 time='.date("Y-m-d H:i:s",time()).' type='.$type.', uid='.$SAA_autoSync->uid.' 的时候，状态回写失败，请查看后台日志！');
				}
				continue;
			}
			
			$SAA_autoSync->last_start_time=$start_time;
			$SAA_autoSync->last_finish_time=$end_time; 
			$SAA_autoSync->update_time=$nowTime;//上一次运行时间 
			$SAA_autoSync->status = 2;
			$SAA_autoSync->error_times = 0;
			$SAA_autoSync->message="";
			if(!$SAA_autoSync->save()){
				echo "SAA_autoSync->save fail: ".json_encode($SAA_autoSync->errors).PHP_EOL;
			    // TODO newegg console job monitor emails
				MailHelper::sendMailBySQ('xxx@xxx.com', '小老板后台job监测', 'xxx@xxx2.com', '订单同步状态回写失败', 'Newegg同步订单 time='.date("Y-m-d H:i:s",time()).' type='.$type.', uid='.$SAA_autoSync->uid.' 的时候，状态回写失败，请查看后台日志！');
			}
			
			//标记保存成功的订单
			self::_confirmOrder($token, self::$needConfirmOrderList);
		}
		//WatchMeDown,取消通知机制的监测
		\eagle\modules\dash_board\helpers\DashBoardHelper::WatchMeDown();
	}
	
	/**
	 * 通过接口来获取订单数据，然后保存到eagle
	 * @param unknown $token 授权信息
	 * @param unknown $uid 用户主账号主键
	 * @param unknown $reqParams 接口参数
	 * 
	 * @author winton 2016/07/27
	 */
	private static function _getOrderListAndSaveToEagle($token, $uid, $reqParams){
		
		echo "_getOrderListAndSaveToQueue  ".$reqParams['start_time'].",".$reqParams['end_time'].",pageIndex:".$reqParams['PageIndex']." \n";
		
		$msg = [];
		
		$timeout = 300;
		
		$timeMS1=TimeUtil::getCurrentTimestampMS();
		$ret = NeweggInterface_Helper::orderInfo($token, $reqParams);
		$timeMS2=TimeUtil::getCurrentTimestampMS();
		echo "\n NeweggInterface_Helper::orderInfo return:" .print_r($ret,true);
		
		$nowTime = time();
		//获取成功
		if(isset($ret['IsSuccess']) && $ret['IsSuccess']){
			$responseBody = $ret['ResponseBody'];
			
			//保存订单数据
			$OrderInfoList = @$responseBody['OrderInfoList'];
			$msg += self::saveOrderListToEagle($token['SellerID'], $uid, $OrderInfoList);
			
			//页码数据
			$PageInfo = $responseBody['PageInfo'];
			//超过一页
			if($PageInfo['TotalPageCount'] > 1){
				while($reqParams['PageIndex'] < $PageInfo['TotalPageCount']){
					$reqParams['PageIndex']++;
					echo "_getOrderListAndSaveToQueue  ".$reqParams['start_time'].",".$reqParams['end_time'].",pageIndex:".$reqParams['PageIndex']." \n";
					
					$timeout = 300;
					
					$timeMS1=TimeUtil::getCurrentTimestampMS();
					$ret = NeweggInterface_Helper::orderInfo($token, $reqParams);
					$timeMS2=TimeUtil::getCurrentTimestampMS();
					echo "_getOrderListAndSaveToQueue pageIndex:".$reqParams['PageIndex'].",result:".print_r($ret, true);
					
					if(isset($ret['IsSuccess']) && $ret['IsSuccess']){
						$responseBody = $ret['ResponseBody'];
						//保存订单数据
						$OrderInfoList = @$responseBody['OrderInfoList'];
						$msg += self::saveOrderListToEagle($token['SellerID'], $uid, $OrderInfoList);
					}else{
						$msg['page'][$reqParams['PageIndex']] = @$ret[0]['Message'];
					}
				}
			}
			
			if(empty($msg)){
				return ResultHelper::getSuccess();
			}
			
			return ResultHelper::getFailed('', 1, $msg);
		}else{
			return ResultHelper::getFailed('', 1, @$ret[0]['Message']);
		}
		
		return ResultHelper::getFailed('', 1, 'Err:NE_GOrd_002。异常错误!');
	}
	/**
	 * 将多个订单保存到eagle
	 * @param unknown $platformAccountId
	 * @param unknown $uid
	 * @param unknown $type
	 * @param unknown $orderList
	 * 
	 * @author winton 2016/07/27
	 */
	private static function saveOrderListToEagle($platformAccountId, $uid, $orderList, $oldOrderInfoList=[]){
		$msg = [];
		if(!empty($orderList)){
			//保存订单数据
			foreach ($orderList as $o){
				//如果是不存在于eagle的订单
				if(!isset($oldOrderInfoList[$o['OrderNumber']])){
					echo '+++++++++++ save to eagle (1) +++++++++++';
					//保存到eagle
					$saveToEagle_ret = self::saveOneOrderToEagle($platformAccountId, $uid, $o);
					if($saveToEagle_ret['success'] === 1){//特别注意：这里1为失败
						echo '+++++++++++ save to eagle error +++++++++++';
						$msg[$o['OrderNumber']] = 'SellerID:'.$platformAccountId.'_'.$saveToEagle_ret['message'];
					}else{
						echo '+++++++++++ save to queueForCatchHtml +++++++++++';
						//插入需要confirm的order
						self::$needConfirmOrderList[$o['OrderNumber']] = $o['OrderNumber'];
						//抓取商品图片队列
						NeweggApiHelper::insertQueueForCatchHtml($uid, $saveToEagle_ret['itemList']);
					}
					//未完全结束的订单，保存的queue队列里面
					if($o['OrderStatus'] < 3){
						echo '+++++++++++ save to queue For update +++++++++++';
						self::insertIntoQueue($platformAccountId, $uid, $o);
					}
				}
				//如果是更新已存在于eagle的订单
				else if(isset($oldOrderInfoList[$o['OrderNumber']])){
					//状态不一致或者md5值不一致才更新
					if($oldOrderInfoList[$o['OrderNumber']]['order_status'] <> $o['OrderStatus']  
						|| $oldOrderInfoList[$o['OrderNumber']]['order_info_md5'] <> md5(json_encode($o)) ){
						
						//保存到eagle
						$saveToEagle_ret = self::saveOneOrderToEagle($platformAccountId, $uid, $o);
						if($saveToEagle_ret['success'] === 1){//特别注意：这里1为失败
							$msg[$o['OrderNumber']] = 'SellerID:'.$platformAccountId.'_'.$saveToEagle_ret['message'];
						}
						//更新的queue队列里面的数据
						self::insertIntoQueue($platformAccountId, $uid, $o);
						
					}
					//已结束的订单，推送到的queue队列进行删除
					if($o['OrderStatus'] >= 3){
						self::insertIntoQueue($platformAccountId, $uid, $o);
					}
				}
				
			}
		}
		return $msg;
	}
	/**
	 * 将单个订单进行数据处理，并保存到eagle
	 * @param unknown $platformAccountId
	 * @param unknown $uid
	 * @param unknown $order
	 * 
	 * @author winton 2016/07/27
	 */
	private static function saveOneOrderToEagle($platformAccountId, $uid, $order){
			//平台代码
			$platform = 'newegg';
			//国家
			$consignee_country_code="";
			if (isset($order['ShipToCountryCode'])){
				$sysCountry=SysCountry::findOne(['country_en'=>$order['ShipToCountryCode']]);
				if ($sysCountry<>null) $consignee_country_code=$sysCountry->country_code;
			}
			//美元
			$currency = 'USD';
			//订单对应eagle的状态
			$order_status=self::$NEWEGG_EAGLE_ORDER_STATUS_MAP[$order['OrderStatusDescription']];
			//是否挂起
			$is_manual_order = 0;
			//物流信息
			$orderShipped = array();
			if (isset($order['PackageInfoList']) && !empty($order['PackageInfoList'])){//有物流号的话
				foreach ($order['PackageInfoList'] as $PackageInfoList_one){
// 					$PackageInfoList_one = @$order['PackageInfoList'][0];
					if(!empty($PackageInfoList_one)){
						$tmp = array(
								'order_source_order_id'=>$order['OrderNumber'],
								'order_source'=>$platform,
								'selleruserid'=>$platformAccountId,
								'tracking_number'=>$PackageInfoList_one["TrackingNumber"],
								'shipping_method_name'=>$PackageInfoList_one['ShipCarrier'],
								'addtype'=>'平台API',
						);
						//赋缺省值
						$orderShipped[]= array_merge(OrderHelper::$order_shipped_demo,$tmp);
					}
				}
				
			}
			
			//1.  订单header信息
			$order_arr=array(//主订单数组
					'order_status'=>$order_status,
					'order_source_status'=>$order['OrderStatusDescription'],
					'is_manual_order'=>$is_manual_order,
					'order_source'=>$platform,
					'order_type'=>'',
					'order_source_site_id'=>'',
					'order_source_order_id'=>$order['OrderNumber'],  //订单来源平台订单号
					'selleruserid'=>$platformAccountId,
					'source_buyer_user_id'=>$order['CustomerName'],	//来源买家用户名
					'order_source_create_time'=>strtotime($order["OrderDate"]), //时间戳
					'subtotal'=>$order['OrderItemAmount'],
					'shipping_cost'=>$order['ShippingAmount'],
					'grand_total'=>$order['OrderTotalAmount'],
					'discount_amount'=>$order['DiscountAmount'],
					'currency'=>$currency,
					'consignee'=>$order['ShipToFirstName']." ".$order['ShipToLastName'],
					'consignee_postal_code'=>$order['ShipToZipCode'],
					'consignee_city'=>$order['ShipToCityName'],
					'consignee_phone'=>$order['CustomerPhoneNumber'],
					'consignee_mobile'=>$order['CustomerPhoneNumber'],
					'consignee_email'=>$order['CustomerEmailAddress'],
					'consignee_country'=>$order['ShipToCountryCode'],
					'consignee_country_code'=>$consignee_country_code,
					'consignee_province'=>$order['ShipToStateCode'],
					'consignee_address_line1'=>$order['ShipToAddress1'],
					'consignee_address_line2' =>$order['ShipToAddress2'],
					'consignee_address_line3' =>empty($order['ShipToAddress3'])?'':$order['ShipToAddress3'],//$order['ShipToAddress1']." ".$order['ShipToAddress2'],
					'paid_time'=>strtotime($order["OrderDate"]), //时间戳
					'delivery_time'=>
						(isset($order['PackageInfoList']['ShipDate']) && !empty($order['PackageInfoList']['ShipDate']))?
						strtotime($order['PackageInfoList']['ShipDate']):0, //时间戳
					//'user_message'=>json_encode($OrderById['orderMsgList']),
					'orderShipped'=>$orderShipped,
					'addi_info'=> json_encode(['ShipService'=>$order['ShipService']])
			);
			
			//2. 订单的items信息
			$userMessage = '';
			$orderitem_arr=array();//订单商品数组
			$product_id_list = [];
			$orderItems = $order['ItemInfoList'];
			foreach ($orderItems as $one){
				$product_id_list[$one['NeweggItemNumber']] = $one['NeweggItemNumber'];
				$productUrl="http://www.newegg.com/Product/Product.aspx?Item=".$one['NeweggItemNumber'];//
				$orderItemsArr = array(
						'order_source_order_id'=>$order['OrderNumber'],  //订单来源平台订单号
						'order_source_order_item_id'=>$one['MfrPartNumber'],
						//'order_source_transactionid'=>$one['childid'],//订单来源交易号或子订单号
						'order_source_itemid'=>$one['NeweggItemNumber'],//产品ID listing的唯一标示
						//'order_source_itemid'=>$one['SellerPartNumber'],//产品ID listing的唯一标示
						'sent_quantity'=>$one['ShippedQty'],  
						//'promotion_discount'=>isset($one['PromotionDiscount'])?$one['PromotionDiscount']:0,   //lolo add -- 速卖通貌似没有的
						'shipping_price'=>$one['ExtendUnitPrice'],
						//'shipping_discount'=>isset($one['ShippingDiscount'])?$one['ShippingDiscount']:0,  //lolo add -- 速卖通貌似没有的
						'platform_sku'=>$one['NeweggItemNumber'],//平台原始编号
						'sku'=>$one['SellerPartNumber'],//商品编码
						'price'=>$one['UnitPrice'],//如果订单是取消状态，该字段amazon不会返回
						'ordered_quantity'=>$one['OrderedQty'],//下单时候的数量
						'quantity'=>$one['OrderedQty'],//-$one['ShippedQty'],  //需发货的商品数量,原则上和下单时候的数量相等，订单可能会修改发货数量，发货的时候按照quantity发货
						'product_name'=>@$one['Description'],//下单时标题
						'photo_primary'=>'',//商品主图冗余
						'product_url'=>$productUrl,
				);
				//赋缺省值
				$orderitem_arr[]=array_merge(OrderHelper::$order_item_demo,$orderItemsArr);
				//$userMessage = $one['memo'];
			}
			//订单商品
			$order_arr['items']=$orderitem_arr;
			//订单备注
			$order_arr['user_message']= "";
			//赋缺省值
			$myorder_arr[$uid]=array_merge(OrderHelper::$order_demo,$order_arr);
			
			//3.  订单header和items信息导入到eagle系统
			try{
		    	echo "before OrderHelper::importPlatformOrder myorder_arr:".print_r($myorder_arr,true);
				$result =  OrderHelper::importPlatformOrder($myorder_arr);
			}catch(\Exception $e){
				echo "OrderHelper::importPlatformOrder fails. Exception  \n";
				\Yii::error("OrderHelper::importPlatformOrder fails.  Exception error:".$e->getMessage()." trace:".$e->getTraceAsString(),"file");
			
				return array('success'=>1,'message'=>$e->getMessage());
			}
			//	echo "after OrderHelper::importPlatformOrder result:".print_r($result,true);
			// ！！！注意  result['success']的返回值。    0----表示ok,1---表示fail
			if ($result['success']===1){
				//	SysLogHelper::GlobalLog_Create("Order", __CLASS__,__FUNCTION__,"","OrderHelper::importPlatformOrder fails. error:".$result['message'],"error");
				\Yii::error("OrderHelper::importPlatformOrder fails.  error:".$result['message'],"file");
			}
			
			$result['itemList'] = $product_id_list;
			
			return $result;
	}
	
	/**
	 * 保存未结束的订单到队列里
	 * @param unknown $SellerID
	 * @param unknown $uid
	 * @param unknown $order
	 * @return boolean
	 */
	public static function insertIntoQueue($SellerID, $uid, $order){
		$time = time();
		//是否已经存在的队列行
		$que = QueueNeweggGetorder::find()->where(['sellerID'=>$SellerID,'uid'=>$uid,'order_source_order_id'=>$order['OrderNumber']])->one();
		//是否到了不需要更新的状态
		if($order['OrderStatus'] >= 3){
			if(empty($que)){
				return true;
			}else{
				return $que->delete();
			}
		}
		if(empty($que)){
			$que = new QueueNeweggGetorder();
			$que->create_time = $time;
		}
		$que->uid = $uid;
		$que->sellerID = $SellerID;
		$que->status = 0;
		$que->order_source_order_id = $order['OrderNumber'];
		$que->order_status = $order['OrderStatus'];
		$que->order_info_md5 = md5(json_encode($order));
		$que->update_time = $time;
		$que->is_active = 1;
		
		if($que->save()){
			return true;
		}
		return false;
	}
	
	/**
	 * 先判断是否真的抢到待处理的OrderQueue记录
	 */
	private static function _lockNeweggOrderQueue($id){
		$nowTime=time();
		$connection=Yii::$app->db_queue;
		$command = $connection->createCommand("update queue_newegg_getorder set status=1, last_start_time=$nowTime where id =". $id." and is_active=1 and status<>1 ") ;
		$affectRows = $command->execute();
		if ($affectRows <= 0)	return null; //抢不到---如果是多进程的话，有抢不到的情况
		// 抢到记录
		$NG_orderQueue = QueueNeweggGetorder::findOne(['id'=>$id]);
		return $NG_orderQueue;
	}
	
	/**
	 * 检测未完成的订单，将其更新到最新状态
	 */
	public static function updateOrderByQueue(){
		echo "++++++++++++updateNeweggOrderByQueue \n";
		//1. 检查当前本job版本，如果和自己的版本不一样，就自动exit吧
		$ret=self::checkNeedExitNot();
		if ($ret===true) exit;
		
		$backgroundJobId=self::getCronJobId(); //获取进程id号，主要是为了打印log
		
		$connection=\Yii::$app->db_queue;
		
		//2. 从账户同步表（订单列表同步表提取带同步的账号。
		$hasGotRecord=false;//是否抢到账号
		
		$nowTime = time();
		$Time = $nowTime;
		
		$command=$connection->createCommand(
				'select `id`, `uid`, `sellerID`, `order_source_order_id`, `order_status`, `order_info_md5` from  `queue_newegg_getorder` where `is_active` = 1 AND `status` <>1  '.
				'AND `error_times` < 10 AND `next_execution_time` <= '.$Time );
		
		$dataReader=$command->query();
		while(($row=$dataReader->read())!==false) {
			echo "++++++++dataReader->read() \n";
			// 先判断是否真的抢到待处理的OrderQueue记录
			$NG_orderQueue = self::_lockNeweggOrderQueue($row['id']);
			if ($NG_orderQueue===null) continue; //抢不到---如果是多进程的话，有抢不到的情况
				
			$SAA_obj = SaasNeweggUser::findOne(['uid'=>$row['uid'], 'SellerID'=>$row['sellerID']]);
			if ($SAA_obj===null) continue; //抢不到---如果是多进程的话，有抢不到的情况
				
			$hasGotRecord=true;  // 抢到记录
				
			//3. 调用渠道的api来获取对应账号的订单列表信息
				
			//3.1 整理请求参数
			$token=array(
					"SellerID"=>$SAA_obj->SellerID,
					"Authorization"=>$SAA_obj->Authorization,
					"SecretKey"=>$SAA_obj->SecretKey,
			);
				
			$nowTime = time();
			$nowTime_date_format = date("Y-m-d H:i:s", $nowTime);
			
			$order_list = [];
			$order_list[$NG_orderQueue->order_source_order_id] = $NG_orderQueue->order_source_order_id;
			
			$order_info[$NG_orderQueue->order_source_order_id]['order_status'] = $NG_orderQueue->order_status;
			$order_info[$NG_orderQueue->order_source_order_id]['order_info_md5'] = $NG_orderQueue->order_info_md5;
			
			//req参数
			$reqParams = [
				'start_time' => '',
				'end_time' => '',
				'PageIndex' => 1,
				'Status' => '',
				'order_list' => $order_list,
				'order_info' => $order_info,
				'downloaded' => 0 
			];
			print_r($reqParams);
			//3.2 访问api接口并保存结果
			$ret = self::_checkOrderByQueue($token, $SAA_obj->uid, $reqParams);
				
			$ret = json_decode($ret, true);
			
			if(!empty($NG_orderQueue)){
				//存在失败
				if($ret['code'] != 200){
					$NG_orderQueue->message=$ret['message'];
					$NG_orderQueue->error_times=$SAA_autoSync->error_times+1;
					$NG_orderQueue->status=3;
					$NG_orderQueue->last_start_time=$nowTime;//上一次运行时间
					$NG_orderQueue->save(false);
					continue;
				}
				
				$NG_orderQueue->last_start_time=$nowTime;
				$NG_orderQueue->last_finish_time=$nowTime;//上一次运行时间
				$NG_orderQueue->next_execution_time = $nowTime + self::$timespan;//设置下一次执行的时间
				$NG_orderQueue->status = 2;
				$NG_orderQueue->error_times = 0;
				$NG_orderQueue->message="";
				$NG_orderQueue->save (false);
			}
		}
	}
	
	private static function _checkOrderByQueue($token, $uid, $reqParams){
		echo "_checkOrderByQueue  ".$reqParams['start_time'].",".$reqParams['end_time'].",pageIndex:".$reqParams['PageIndex']." \n";
		
		$msg = [];
		
		$timeout = 300;
		
		$timeMS1=TimeUtil::getCurrentTimestampMS();
		$ret = NeweggInterface_Helper::orderInfo($token, $reqParams);
		$timeMS2=TimeUtil::getCurrentTimestampMS();
		// 		print_r($ret);
		
		$nowTime = time();
		//获取成功
		if(isset($ret['IsSuccess']) && $ret['IsSuccess']){
			$responseBody = $ret['ResponseBody'];
				
			//保存订单数据
			$OrderInfoList = @$responseBody['OrderInfoList'];
			$msg += self::saveOrderListToEagle($token['SellerID'], $uid, $OrderInfoList, $reqParams['order_info']);
				
			//页码数据
			$PageInfo = $responseBody['PageInfo'];
			//超过一页
			if($PageInfo['TotalPageCount'] > 1){
				while($reqParams['PageIndex'] < $PageInfo['TotalPageCount']){
					$reqParams['PageIndex']++;
					echo "_checkOrderByQueue  ".$reqParams['start_time'].",".$reqParams['end_time'].",pageIndex:".$reqParams['PageIndex']." \n";
						
					$timeout = 300;
						
					$timeMS1=TimeUtil::getCurrentTimestampMS();
					$ret = NeweggInterface_Helper::orderInfo($token, $reqParams);
					$timeMS2=TimeUtil::getCurrentTimestampMS();
					// 					print_r($ret);
						
					if(isset($ret['IsSuccess']) && $ret['IsSuccess']){
						$responseBody = $ret['ResponseBody'];
						//保存订单数据
						$OrderInfoList = @$responseBody['OrderInfoList'];
						$msg += self::saveOrderListToEagle($token['SellerID'], $uid, $OrderInfoList, $reqParams['order_info']);
					}else{
						$msg['page'][$reqParams['PageIndex']] = @$ret[0]['Message'];
					}
				}
			}
				
			if(empty($msg)){
				return ResultHelper::getSuccess();
			}
				
			return ResultHelper::getFailed('', 1, $msg);
		}else{
			return ResultHelper::getFailed('', 1, @$ret[0]['Message']);
		}
		
		return ResultHelper::getFailed('', 1, 'Err:NE_CheckOrd_002。异常错误!');
	}
	
	private static function _confirmOrder($token, $orderList){
		self::$needConfirmOrderList = [];
		$ret = NeweggInterface_Helper::orderConfirm($token, ['orderList'=>$orderList]);
// 		print_r($ret);
	}
	
	/**
	* 太平洋时间转北京时间
	*/
	
	public static function _pacificToPRC($time='', $strtotime=false){
	
		date_default_timezone_set('Pacific/Apia');
		if(empty($time)){
			$time = time();
		}
	
		date_default_timezone_set('Asia/Shanghai');
		$time = date('Y-m-d H:i:s',$time);
		if($strtotime)
			$time = strtotime($time);
	
		return $time;
	
	}
	/**
	* 北京时间转太平洋时间
	* @param timestamp
	* 
	* return  太平洋标准时间 ，例如 2016-10-29 10：00:00 ， 实际上太平洋标准时间就是北京时间-16小时  
	*/
	
	public static function _PRCToPacific($time='', $strtotime=false){
		if(empty($time)){
			date_default_timezone_set('Asia/Shanghai');
			$time = time();
		}
	
		//date_default_timezone_set('Pacific/Apia');
		$time = date('Y-m-d H:i:s',$time - 16 * 60 * 60);
		if($strtotime)
			$time = strtotime($time);
	
		return $time;
	
	}
}
?>