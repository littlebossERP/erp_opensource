<?php namespace console\controllers;
 
use yii;
use yii\console\Controller;

use eagle\modules\assistant\models\SaasAliexpressUser;
use eagle\models\assistant\DpInfo;
use eagle\models\assistant\DpRule;
use eagle\models\assistant\DpEnable;
use eagle\modules\order\models\OdOrder;
use eagle\models\AliexpressOrder;
use eagle\modules\message\apihelpers\MessageApiHelper;
use eagle\modules\assistant\services\Rule;

use eagle\modules\listing\apihelpers\ListingAliexpressApiHelper;
use eagle\modules\util\helpers\ConfigHelper;
use common\api\aliexpressinterfacev2\AliexpressInterface_Helper_Qimen;

/**
 * 快递助手自动同步脚本（30分钟一次）
 */
class AssistantController extends Controller {

	private static $logText = '';

	static function println($str){
		echo $str.PHP_EOL;
	}
    public static $cronJobId=0;
    private static $aliexpressGetOrderListVersion = null;
    private static $aliexpressAutoOrderListVerion= null;
    private static $version = null;



	/**
	 * 匹配自动催款规则
	 * @return ActiveRecords_object query 
	 */
	static function getMatchDpRuleOrders(DpRule $rule,$shop_id){
		$countries = "'".join("','",explode(',',$rule->country))."'";
		$where = '';
		if($rule->country!='*'){
			$where .= " AND od_order_v2.consignee_country_code IN ($countries)";
		}
		if($rule->min_money>0){
			$where .= " AND grand_total>=".$rule->min_money;
		}
		if($rule->max_money>0){
			$where .= " AND grand_total<=".$rule->max_money;
		}

		$sql = "SELECT od_order_v2.* FROM od_order_v2 
		LEFT JOIN dp_info 
		ON od_order_v2.order_id = dp_info.order_id
		WHERE selleruserid = '{$shop_id}'
		AND order_status = ".OdOrder::STATUS_NOPAY."
		AND order_source_create_time <= ".(time() - $rule->timeout)."
		AND order_source_create_time >= ".(time() - 20*24*3600)."
		AND dp_info.order_id IS NULL
		{$where}
		";

		$orders = OdOrder::findBySql($sql);
		$_orders=[];
		// echo $orders->createCommand()->getRawSql().PHP_EOL;
		foreach($orders->all() as $order){
			// 从速卖通平台查询当前交易状态
			$platformStatus = self::getOrderStatusByPlatform($order);
			if(isset($platformStatus['exception'])){
				self::$logText.=$platformStatus['error_message'].' 订单:'.$order->order_source_order_id.'平台授权发生了异常，跳过本次催款'.PHP_EOL;
				continue;
			}
			if(in_array($platformStatus['orderStatus'], [
				'PLACE_ORDER_SUCCESS'
			])){
				// 查询是否与客服联系过
				if(!self::alreadyContact($order)){
					$_orders[] = $order;
					// var_dump($order->order_source_order_id);
				}
			}else{
				$pay_time = isset($platformStatus['gmtCreate']) && $platformStatus['gmtCreate']?$platformStatus['gmtCreate']:NULL;
				$order->paid_time = $pay_time;
				self::async($order,NULL,'买家已付款，跳过催款',2);
			}
		}
		return $_orders;
	}	

	function actionTest(){
		$rs = ListingAliexpressApiHelper::getOrderInfoById('cn1510671045','68052076854004');
		var_dump($rs['gmtCreate']);
	}

	/**
	 * 从平台获取当前订单状态
	 * @param  OdOrder $order [description]
	 * @return [type]         [description]
	 */
	static protected function getOrderStatusByPlatform(OdOrder $order){
		self::$logText .= "-- call api: ListingAliexpressApiHelper::getOrderInfoById".PHP_EOL;
		self::$logText .= 'selleruserid:'.$order->selleruserid.PHP_EOL.'order_id:'.$order->order_source_order_id.PHP_EOL;
		$rs = ListingAliexpressApiHelper::getOrderInfoById($order->selleruserid,$order->order_source_order_id);
		return $rs;
	}

	/**
	 * 快递助手自动同步脚本（30分钟一次）
	 * @return [type] [description]
	 */
	public function actionAsyncorders() {
		$asyncSuccessCount = $asyncFailCount = $asyncTotalCount = 0;
		$paySuccessCount = 0;
		$uid = 0;
		// 获取所有开通催款助手的速卖通用户的uid
		$shops = DpEnable::find()
			->where(['enable_status'=>2])
			->orderBy('dp_puid ASC')->all();

		foreach($shops as $shop){
			 
			if(true){
				$uid = $shop->dp_puid;
				$shop_id = $shop->dp_shop_id;
				/* --- 扫描新的订单进行发送提醒 --- */
				// 取出规则
				$rules = DpRule::find()
					->andWhere([
						'puid' 		=> $uid,
						'status' 	=> 1,
						'is_active' => 2
					])
					->orderBy('country desc')
					->all();
				foreach($rules as $rule){
					$orders = self::getMatchDpRuleOrders($rule,$shop_id);
					foreach($orders as $order){

						if($content = self::sendRemind($order,$rule->message_content,$uid)){
							if(self::async($order,$rule,$content)){
								$asyncSuccessCount++;
							}else{
								$asyncFailCount++;
							}
						}
					}
				}

				/* --- 检测提醒过的订单是否已完成付费 --- */
				$orders = DpInfo::find()
					->andWhere([
						'status'=>1,
						'due_status'=>1,
						'contacted'=>0
					])->all();
				foreach($orders as $order){
					$rawOrder = OdOrder::findOne($order->order_id);
					if($rawOrder->order_status == OdOrder::STATUS_PAY){
						// 催款成功
						$order->due_status = 2;
						$order->pay_time = date("Y-m-d H:i:s", $rawOrder->paid_time);
						if($order->save()){
							self::$logText .= "订单[{$order->order_id}]催款成功".PHP_EOL;
							$paySuccessCount++;
						}else{
							self::$logText .='订单[{$order->order_id}]催款成功记录失败。。'.PHP_EOL;
							
						}
					}
				}

			}
		}
		$asyncTotalCount = $asyncSuccessCount + $asyncFailCount;
		self::$logText .= "-- Async info --------------".PHP_EOL;
		self::$logText .= "Success: {$asyncSuccessCount}/{$asyncTotalCount}".PHP_EOL;
		self::$logText .= "Fail: {$asyncFailCount}/{$asyncTotalCount}".PHP_EOL;
		self::$logText .= "-- dueStatus info --------------".PHP_EOL;
		self::$logText .= "Paid: {$paySuccessCount}".PHP_EOL;
		self::log(self::$logText,'error');
	}

	static private function log($str='',$type='info'){
		self::println($str);
		\Yii::$type($str);
	}

	/**
	 * 记录发送过的信息到dp_info表中
	 * @param  [type] $rawOrder [description]
	 * @param  [type] $rule     [description]
	 * @param  [type] $content  [description]
	 * @return [type]           [description]
	 */
	static public function async($rawOrder,$rule = NULL,$content = '',$due_status=1){
		// 同步到dp_info库
		if(DpInfo::find()->where([
				'order_id'=>$rawOrder->order_id
			])->count()) {
			return false;
		}
		$order = new DpInfo();
		$order->order_id = $rawOrder->order_id;
		$order->create_time = date("Y-m-d H:i:s");
		$order->source_id = $rawOrder->order_source_order_id;
		$order->buyer = $rawOrder->consignee;
		$order->order_time = date("Y-m-d H:i:s",$rawOrder->order_source_create_time);
		$order->shop_id = $rawOrder->selleruserid;
		$order->cost = $rawOrder->grand_total;
		$order->content = $content;
		if($rawOrder->paid_time){
			$order->pay_time = $rawOrder->paid_time;
		}
		if($rule){
			$order->rule_id = $rule->rule_id;
			$order->contacted = 0;
		}else{
			$order->contacted = 1;
		}
		$order->consignee_country_code = $rawOrder->consignee_country_code;
		$order->due_status = $due_status;
		$result = $order->save();
		self::$logText .= PHP_EOL.'写入dp_info '.($result?'Success':'Fail').' async order '.$order->order_id.PHP_EOL;
		return $result;
	}

    //替换标签
    static protected function matchTag(OdOrder $order,$msg) {

        $msg = str_replace('[买家姓名]', $order->consignee, $msg);
        // $msg = str_replace('[卖家姓名]', $order->selleruserid, $msg);
        $msg = str_replace('[原始金额]', $order->grand_total, $msg);
        $msg = str_replace('[订单号]', $order->order_source_order_id, $msg);

        return htmlspecialchars(strip_tags($msg));
    }


	/**
	 * 调用客服接口发送订单留言
	 * @param  [eagle\modules\order\models\OdOrder] $order_id 
	 * @return [type]           [description]
	 */
	static public function sendRemind(OdOrder $order,$message,$puid=""){
		echo $order->order_source_order_id.PHP_EOL.'正在发送催款'.PHP_EOL;

                /*
		$message = preg_replace_callback('/\[(\w+)\]/', function($match){
			switch($match[1]){
				case '买家姓名':
					return $order->source_buyer_user_id;
				case '卖家姓名':
					return $order->selleruserid;
				case '原始金额':
					return $order->grand_total;
				case '订单号':
					return $order->order_source_order_id;
				default:
					break;
			}
		}, $message);
                */
                $message = self::matchTag($order, $message);

		$result = MessageApiHelper::sendTicketMsg($order->order_source,$order->order_source_order_id,$message,[],$puid);
		
		// $result['success'] = true;

		if($result['success']){
			self::$logText .= $order->order_source_order_id.' 调用催款接口成功puid:'.$puid.PHP_EOL;
			return $message;
		}else{
			self::$logText .= $order->order_source_order_id.' 调用接口失败！'.PHP_EOL.'order_source:'.$order->order_source.PHP_EOL.'order_source_order_id:'.$order->order_source_order_id.PHP_EOL.'message:'.$message.PHP_EOL;
			return false;
		}
	}

	static public function alreadyContact(OdOrder $order){
		// return false;
		// $result = MessageApiHelper::getTicketSessionList($order->source_buyer_user_id,$order->order_source,$order->selleruserid);
		// $success = count($result['ticketSessionList']['data']);
		
		// 客服新的接口
		$success = MessageApiHelper::isOrderWithMsg($order->order_source_order_id,$order->order_source)['success'];
		if($success){
			self::async($order, null, '已与客服联系，跳过催款');
			self::$logText .= '[ '.$order->order_source_order_id.' ]已与客服联系，跳过催款'.PHP_EOL;
		}
		return $success;
		// return true;
	}


 	static public function actionRun( $uid='' ){
        self::$cronJobId++;
        $startRunTime = time();
        do {
        	$currentAliexpressGetOrderListVersion = ConfigHelper::getGlobalConfig("Order/assistantRun",'NO_CACHE');
        	if(empty($currentAliexpressGetOrderListVersion)) {
        		$currentAliexpressGetOrderListVersion = 0;
        	}
        	//如果自己还没有定义，去使用global config来初始化自己
        	if (empty(self::$aliexpressAutoOrderListVerion)) {
        		self::$aliexpressAutoOrderListVerion = $currentAliexpressGetOrderListVersion;
        	}
        	//如果自己的version不同于全局要求的version，自己退出，从新起job来使用新代码
        	if (self::$aliexpressAutoOrderListVerion <> $currentAliexpressGetOrderListVersion){
        		exit("Version new $currentAliexpressGetOrderListVersion , this job ver ".self::$aliexpressAutoOrderListVerion." exits for using new version $currentAliexpressGetOrderListVersion.");
        	}
        	
            $count = 0;
            $success_count = 0;
            //通过dp_enable表查询所有开启催款服务的卖家puid
            $shop = DpEnable::find()->where(['enable_status'=>2])->groupBy(['dp_puid'])->orderBy("last_time")->all();
            $all_count= count($shop);
            $is_ok= false;
            foreach($shop as $key=>$puid) {
				if( $uid!='' ){
					$puid->dp_puid= $uid;
				}
				//单次运行1020个
				if($key > 20){
					break;
				}
				
				//更新最后运行时间
				$dp = DpEnable::findOne(['dp_enable_id' => $puid->dp_enable_id]);
				if(!empty($dp)){
					$dp->last_time = time();
					$dp->save(false);
				}
				
				//非速卖通v2版本的接口跳过
				$is_aliexpress_v2 = AliexpressInterface_Helper_Qimen::CheckAliexpressV2($puid->dp_shop_id);
				if(!$is_aliexpress_v2){
					continue;
				}

                echo '--------------------------开始执行-用户',date("H:i:s"),$puid->dp_puid,PHP_EOL;
                $due = new \eagle\modules\assistant\services\rule\Due($puid->dp_puid);
                $dues = new \eagle\modules\assistant\services\rule\Message($puid->dp_puid);
                if(true) {
                    //查所有未付款订单，获得订单集合,odOrder表
                    $timeout = (time() - 5 * 24 * 3600);
                    $ar_order = OdOrder::find()->where(['>=', 'order_source_create_time', $timeout])->andWhere(['order_status' => 100])->andWhere(['order_source' => 'aliexpress',])->andWhere(['<>', 'order_source_status', 'RISK_CONTROL'])->orderBy("order_source_create_time desc");
                    //统计订单总量
                    $count += $ar_order->count();

                    $order = $ar_order->all();
                    self::processOrder($due, $order);
                    //当前用户的未付款订单的催款逻辑结束

                    //查所有（等待卖家发货）订单，获得订单集合,odOrder表
                    $ar_order = OdOrder::find()->where(['order_status' => 200])->andWhere(['order_source' => 'aliexpress']);
                    //统计订单总量
                    $count += $ar_order->count();

                    $order = $ar_order->all();
                    self::processOrder($dues, $order);

                    echo '--------------------用户',$puid->dp_puid,'执行完毕--',date("H:i:s"),PHP_EOL;;
                }
                $success_count += $due->success_count;
                $success_count += $dues->success_count;
                if( $all_count==$key+1 ){
                    $is_ok= true;
                }
				if( $uid!='' ) {
					exit;
				}

            }
            if( $is_ok===false ){
                sleep(10);
            }
        } while (time() < $startRunTime + 3600);
	}


	static public function processOrder($due, $ary_order) {
		foreach ($ary_order as $order) {
			$rs = $due->run($order);
		}
	}
	/*
	 * 订单催款成功回写
	 */
	static public function actionSuccess(){
        $currentAliexpressGetOrderListVersion = ConfigHelper::getGlobalConfig("Order/assistantSuccess",'NO_CACHE');
        if(empty($currentAliexpressGetOrderListVersion)) {
            $currentAliexpressGetOrderListVersion = 0;
        }
        //如果自己还没有定义，去使用global config来初始化自己
        if (empty(self::$aliexpressAutoOrderListVerion)) {
            self::$aliexpressAutoOrderListVerion = $currentAliexpressGetOrderListVersion;
        }
        //如果自己的version不同于全局要求的version，自己退出，从新起job来使用新代码
        if (self::$aliexpressAutoOrderListVerion <> $currentAliexpressGetOrderListVersion){
            exit("Version new $currentAliexpressGetOrderListVersion , this job ver ".self::$aliexpressAutoOrderListVerion." exits for using new version $currentAliexpressGetOrderListVersion.");
        }
        self::$cronJobId++;

        $startRunTime = time();
        do {
            $is_ok = false;
            $shop = DpEnable::find()->where(['enable_status' => 2])->groupBy(['dp_puid'])->all();
            $all_count = count($shop);
            foreach($shop as $key=>$puid) {
                if(true) {
                    $paySuccessCount = 0;
                    /* --- 检测提醒过的订单是否已完成付费 --- */
                    $startTime = time() - 20 * 24 * 3600;
                    $endTime = time();
                    $orders = DpInfo::find()
                        ->Where([
                            'status' => 1,
                            'due_status' => 1,
                        ])->andWhere([
                            'BETWEEN',
                            'create_time',
                            date("Y-m-d H:i:s", $startTime),
                            date("Y-m-d H:i:s", $endTime)
                        ])->all();
                    echo ('not_paid_count:') . count($orders) . PHP_EOL;
                    foreach ($orders as $_order) {
                        $rawOrder = OdOrder::findOne($_order->order_id);
                        if ($rawOrder->order_status == OdOrder::STATUS_PAY) {
                            // 催款成功
                            $_order->due_status = 2;
                            $_order->pay_time = date("Y-m-d H:i:s", $rawOrder->paid_time);
                            if ($_order->save()) {
                                echo ('order_write_back_success') . PHP_EOL;
                                $paySuccessCount++;
                            }
                        }
                    }
                }
                if( $all_count==$key+1 ){
                    $is_ok= true;
                }
            }
            if ($is_ok === false) {
                sleep(10);
            }
        }while (time() < $startRunTime + 3600);
		//echo 'pay_success_count:'.$paySuccessCount.PHP_EOL;
	}
	
	 
}


