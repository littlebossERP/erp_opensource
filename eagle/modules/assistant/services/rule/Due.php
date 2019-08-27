<?php namespace eagle\modules\assistant\services\rule;

use eagle\models\assistant\OmOrderMessageTemplate;
use eagle\modules\order\models\OdOrder;
// use eagle\modules\assistant\models\OdOrder;
use eagle\models\assistant\DpRule;
use eagle\models\assistant\DpEnable;
use eagle\modules\assistant\models\DpInfo;

use eagle\modules\message\apihelpers\MessageApiHelper;
use eagle\modules\listing\apihelpers\ListingAliexpressApiHelper;
use eagle\modules\assistant\config\Cfg;

class Due extends \eagle\modules\assistant\services\Rule
{

	const CS_CONTACTED = 1; 			// 客服联系过
	const CS_OTHERDUE = 2;				// 存在其他已催款订单
	const CS_HASPAID = 3;				// 存在已付款订单

	public $success_count = 0;

	protected $rules = [];
	protected $shops = [];
	protected $msgType = 'due';
	public $isTest = false;

	// 设置规则判断的优先级
	protected $ruleFunc = [
		'ruleTimes',
		'ruleTimeout', 					// 必须排在ruleTimes之后
		'rulePayStatus',					// 是否是未付款订单
		'ruleTotal',                      // 付款金额是否符合规则
		'hasOtherOrder', 					// 时间段内是否存在其他已催款订单
		'hasPaydOrder', 					// 时间段内是否存在已付款订单
		'isCustomServiceContacted', 		// 客服是否已经联系过
		'isBoleto', 						// 是否是Boleto支付方式的订单
		'ruleCountry', 						// 国家
	];


	function __construct($puid){
		// 获取当前PUID下用户的催款规则和店铺启用情况
		$this->rules = DpRule::find()
			->where([
				'is_active'=>2,
				'status'=>1,
				'puid'=>$puid
			])
			->orderBy('country DESC')
			->all();
		$this->shops = $this->getEnabledShops();
		$this->puid = $puid;
		$this->attr = new \stdClass;
		$this->attr->isCustomServiceContacted = 0;
	}

	/**
	 * 发送催款站内信
	 * @param  OdOrder $order   [description]
	 * @param  string  $message [description]
	 * @return [type]           [description]
	 */
	public function sendMsg(DpRule $rule,OdOrder $order){
		//通过rule表中的message_content中的ID查询OmOrderMessageTemplate中相对应模板
		$content = '';
		$tpl = OmOrderMessageTemplate::findOne($rule->message_content);
		if(count($tpl)){
			$content  = $tpl->content;
		}

		if($this->isTest){
			$this->log('校验发送成功！');
			$result['success'] = true;
			return $result;

		}
//		if(isset($_SERVER['ENV']) && $_SERVER['ENV'] == 'test'){
//			$result['success'] = true;
//			$this->log('测试环境模拟发送成功！');
//		}else{
			$this->log('API-SendTime:'.date("Y-m-d H:i:s"));
			$result = MessageApiHelper::sendTicketMsg($order->order_source,$order->order_source_order_id,$this->contentLabelReplace($content,$order),[],$this->puid);
			
		    $this->log('API-SendEndTime:'.date("Y-m-d H:i:s"));
			$this->log('调用接口成功');
//		}
	return $result;
	}

	/**
	 * 催款主函数
	 * @param  OdOrder $order 传入的订单信息
	 * @return array 必须包括rule_type,rule_id,info_id三个下标值
	 */
	public function run(OdOrder $order){
		// $this->attr = new \stdClass;
		//显示催款用户ID
		$this->log('selleruserid:'.$order->selleruserid);
		$this->log('order_id:'.$order->order_id);
		if(in_array($order->selleruserid, $this->shops)){ 	// 店铺是否启用
			//判断订单平台是否实际已付款
			if(!$this->isTest){
				$platformStatus = 	ListingAliexpressApiHelper::getOrderInfoById($order->selleruserid,$order->order_source_order_id);
				if(!isset($platformStatus['orderStatus'])) {
					$this->log(json_encode($platformStatus));
					return false;
				}

				if(!in_array($platformStatus['orderStatus'], ['PLACE_ORDER_SUCCESS'])){
					$this->log($order->order_source_order_id.'--实际订单状态不符合，跳过此单（所有规则）'.$platformStatus['orderStatus']);
					return false;
				}
			}
			$this->log('start rules');
			foreach($this->rules as $r){ 	// 对现在的所有规则进行匹配
				$rule_falg = true;
				$rule = clone($r); 			// 这里需要clone对象，规则可能会被其中的某一条临时修改
				$this->log('rule_id:'.$rule->rule_id);
				foreach($this->ruleFunc as $func){
					//循环ruleFunc内方法
					if(!call_user_func_array([$this,$func], [$rule,$order])){
						$this->log('!! '.$func.' 返回false，不进行催款['.$this->msgType.']');
						$rule_falg = false;
						break;
					}
				}
				//如果此订单不匹配该规则跳出重新匹配另外规则
				if(!$rule_falg){
					continue;
				}

				$result = $this->sendMsg($rule,$order); // 调用sendMsg方法
				if($result['success']){
					if($this->isTest){
						$this->log('校验完成且成功');
						return false;
					}
					$log = $this->saveDpInfo($rule,$order);
					$this->success_count++;
					return [
						'rule_type'=>$this->msgType,
						'rule_id'=>$rule->rule_id,
						'info_id'=>$log->duepay_id
					];
				}else{
					$this->log('!! 发送失败');
					return false;
				}
			}
		}
		else{
			$this->log('selleruserid not in DpEnable');
		}
		return false;
	}


	/**
	 * 获取订单的催款次数
	 * @return [type] [description]
	 */
	public function getOrderDueTimes(OdOrder $order){
		return DpInfo::find()
			->where([
				'source_id'=>$order->order_source_order_id,
				'contacted'=>0,
				'msg_type'=>DpInfo::TYPE_DUE_MSG
			])
			->count();
	}

	/**
	 * 保存dp_info
	 * @param  DpRule  $rule  [description]
	 * @param  OdOrder $order [description]
	 * @return DpInfo
	 */
	public function saveDpInfo(DpRule $rule, OdOrder $order){
		$type = [
			'due' => DpInfo::TYPE_DUE_MSG,
			'message' => DpInfo::TYPE_ORDER_MSG
		];
		$content = '';
		$tpl = OmOrderMessageTemplate::findOne($rule->message_content);
		if(count($tpl)){
			$content  = $tpl->content;
		}

		$dp = new DpInfo;
		$dp->order_id 					= $order->order_id;
		$dp->create_time 				= date("Y-m-d H:i:s");
		$dp->source_id 					= $order->order_source_order_id;
		$dp->buyer 						= $order->consignee;
		$dp->order_time 				= date("Y-m-d H:i:s",$order->order_source_create_time);
		$dp->shop_id 					= $order->selleruserid;
		$dp->cost 						= $order->grand_total;
		$dp->content 					= $this->contentLabelReplace($content,$order);
		$dp->consignee_country_code 	= $order->consignee_country_code;
		if($order->paid_time){
			$dp->pay_time 				= $order->paid_time;
		}
		$dp->rule_id 					= $rule->rule_id;
		$dp->contacted 					= $this->attr->isCustomServiceContacted;
		$dp->msg_type  					= $type[$this->msgType];

		$dp->save();
		return $dp;
	}

	/**
	 * 付款状态
	 * @param  DpRule  $rule  [description]
	 * @param  OdOrder $order [description]
	 * @return boolean
	 */
	public function rulePayStatus(DpRule $rule, OdOrder $order){
		// $times = $this->getOrderDueTimes($order); 		// 获取催款次数
		if($order->order_status == OdOrder::STATUS_NOPAY){
			return true;
		}else{
			return false;
		}
	}

	/**
	 * 多次催款
	 * @param  DpRule  $rule  [description]
	 * @param  OdOrder $order [description]
	 * @return [type]         [description]
	 */
	public function ruleTimes(DpRule $rule, OdOrder $order){
		if($this->isTest){
			$this->log($order->order_source_order_id.'判断催款次数是否符合规则');
		}
		// 获取此订单已经催款的次数
		$times = $this->getOrderDueTimes($order);
		if($times>0){
			$this->log($order->order_source_order_id.'> 订单催款次数已达最大值!!!!');
			return false;
		}elseif(!$times){
			if($this->isTest){
				$this->log('符合规则');
			}
			return true;
		}

		if($times>0){
			$field = 'timeout'.($times+1);
			$msg = 'message_content'.($times+1);
			if($rule->$field <= 0){
				$this->log($order->order_source_order_id.'> 订单催款次数已达最大值???');
				return false;
			}else{
				$rule->timeout = $rule->$field;
				$rule->message_content = $rule->$msg;
				if($this->isTest){
					$this->log($order->order_source_order_id.'符合规则');
				}
				return true;
			}
		}
	}

	public function ruleTimeout(DpRule $rule, OdOrder $order){
		if($this->isTest){
			$this->log($order->order_source_order_id.'判断订单超时时间是否符合规则');
		}
		if($rule->timeout>0){
			if( $order->order_source_create_time <= time() - ($rule->timeout - 800) ){
				if($this->isTest){
					$this->log($order->order_source_order_id.'符合规则');
				}
				return true;
			}else{
				return false;
			}
		}
		if($this->isTest){
			$this->log($order->order_source_order_id.'符合规则');
		}
		return true;
	}

	/**
	 * 查询是否存在时间段有其他催过款的订单
	 * @param  DpRule  $rule  [description]
	 * @param  OdOrder $order [description]
	 * @return boolean        [description]
	 */
	public function hasOtherOrder(DpRule $rule, OdOrder $order){
		if($this->isTest){
			$this->log($order->order_source_order_id.'判断时间段内是否存在其他已催款订单');
		}
		if($rule->expire_time>0){
			// 查找此订单是否dpinfo中有记录（已发送过催款contacted为2）
			$had = DpInfo::find()
				->where([
					'source_id' => $order->order_source_order_id,
					'contacted' => self::CS_OTHERDUE
				])
				->count();
//			$this->log('had:'.$had);
			if($had){
				$this->log($order->order_source_order_id.'> 已经催过款 -- 时间段内其他订单催过款');
				return false;
			}
			// 设置时间段内别的订单已联系过
			// 查找时间段内的所有催款记录
			$startTime = time()-$rule->expire_time;
			$endTime = time();
			// 获取时间段内对该买家催过款的记录数
			$dpInfo = DpInfo::find()
				->where([
					'BETWEEN',
					'create_time',
					date("Y-m-d H:i:s",$startTime),
					date("Y-m-d H:i:s",$endTime)
				])
				->andWhere([
					'buyer' => $order->consignee,
					'contacted' => 0  		// 条件判断必须是催过款的，而不是跳过催款的
				])
				->count();
//			$this->log('dpInfo:'.$dpInfo);
			if($dpInfo){
				$this->attr->isCustomServiceContacted = self::CS_OTHERDUE;
				$this->log($order->order_source_order_id.'> 已经催过款 -- 时间段内其他订单催过款');
				if($this->ruleDunning($rule,$order)){
					if($this->isTest){
						return false;
					}
					$this->saveDpInfo(new DpRule, $order);
				}
				return false;
			}
		}
		if($this->isTest){
			$this->log($order->order_source_order_id.'符合规则');
		}
		return true;
	}

	/**
	 * 时间段内存在已付款订单
	 * @param  DpRule  $rule  [description]
	 * @param  OdOrder $order [description]
	 * @return boolean        [description]
	 */
	public function hasPaydOrder(DpRule $rule, OdOrder $order){
		if($this->isTest){
			$this->log($order->order_source_order_id.'判断时间段内同用户是否存在其他已付款订单');
		}
		if($rule->expire_time>0){
			$orders = OdOrder::find()
				->where([
					'order_source'=>$order->order_source,
					'consignee'=>$order->consignee,
					//'order_status'=>OdOrder::STATUS_PAY
				])
				->andWhere([
					'>=',
					'order_status',
					OdOrder::STATUS_PAY
				])
				->andWhere([
					'>',
					'order_source_create_time',
					(time()-$rule->expire_time)
				]);
			if($orders->count()){
				$result = DpInfo::find()->where(['contacted'=>3,'source_id'=>$order->order_source_order_id])->count();
				if($result>0){
					return false;
				}
				$this->attr->isCustomServiceContacted = self::CS_HASPAID;
				$this->log($order->order_source_order_id.'> 已经催过款 -- 时间段内存在其他已付款订单');
				if($this->isTest){
					return false;
				}
				$this->saveDpInfo(new DpRule, $order);
				return false;
			}
			$this->log('not same paied order');

		}
		if($this->isTest){
			$this->log($order->order_source_order_id.'符合规则');
		}
		return true;
	}

	/**
	 * 匹配国家
	 * @param  DpRule  $rule  [description]
	 * @param  OdOrder $order [description]
	 * @return [type]         [description]
	 */
	public function ruleCountry(DpRule $rule, OdOrder $order){
		if($this->isTest){
			$this->log($order->order_source_order_id.'判断国家是否符合规则');
		}
	//	$this->log($rule->country);
	//	$this->log($order->consignee_country_code);
		// 国家匹配
		if($rule->country == '*' || in_array($order->consignee_country_code, explode(',',$rule->country))){
			if($this->isTest){
				$this->log($order->order_source_order_id.'符合规则');
			}
			return true;
		}else{
			return false;
		}
	}

	/**
	 * 判断支付方式是否是Boleto的，是的话返回的规则时间+5DAY
	 * @param  DpRule  $rule  [description]
	 * @param  OdOrder $order [description]
	 * @return DpRule
	 */
	public function isBoleto(DpRule $rule, OdOrder $order){
		if($this->isTest){
			$this->log($order->order_source_order_id.'判断订单支付方式是否被Boleto支付');
		}
		// return true;
		if(strtolower($order->payment_type == 'boleto')){
			$rule->timeout = strtotime('+5 day',$rule->timeout);
			$rule->timeout2 = strtotime('+5 day',$rule->timeout2);
			$rule->timeout3 = strtotime('+5 day',$rule->timeout3);
			$this->log($order->order_source_order_id.'> 是Boleto支付方式，规则时间增加5天！');
		}
		if($this->isTest){
			$this->log($order->order_source_order_id.'符合规则');
		}
		return true;
	}

	/**
	 * 客服是否已经联系过
	 * @param  OdOrder $order [description]
	 * @return boolean        是否继续
	 */
	public function isCustomServiceContacted(DpRule $rule,OdOrder $order){
		if($this->isTest){
			$this->log($order->order_source_order_id.'判断客服是否联系');
		}
		//to-do  判断用户行为及系统行为，通过比对客服massage表数量和dpinfo表数量来区分
		$serviceContacted = DpInfo::find()->where(['source_id'=>$order->order_source_order_id,'contacted'=>3])->all();
		if(count($serviceContacted) > 0){
			$this->log($order->order_source_order_id.'> 已联系过客服');
			return false;
		}
		//to-do 是否一定要排序？？如不需要，则可以去掉，提高查询速度
		$param = [
			'sort'=>' t.lastmessage',
			'order'=>'desc'
		];
		//调用客服接口
		$this->log("start judge contacted");
		$msg = '';
		$result = MessageApiHelper::getSessionListByOrderId($order->order_source_order_id,$order->order_source,$param);
		$tickets = $result['ticketSessionList']['data'];
		$msg += " count:".count($tickets).", ";
		if(count($tickets)){
			foreach($tickets as $ticket){
				$msg += "has_replied:".$ticket['has_replied'].", create:".$ticket['created'];
				
				if(!$ticket['has_replied']){
					$this->log($order->order_source_order_id.'> 已联系过客服');
					$this->attr->isCustomServiceContacted = self::CS_CONTACTED;
					if($this->isTest){
						return false;
					}
					$this->saveDpInfo(new DpRule, $order);
					return false;
				}

			}
		}
		$this->log($msg);
		$this->attr->isCustomServiceContacted = 0;
		if($this->isTest){
			$this->log($order->order_source_order_id.'符合规则');
		}
		return true;
	}

	// public function 
	// 获取当前用户开启的店铺
	public function getEnabledShops(){
		$results = [];
		$shops = DpEnable::find()
			->where([
				'enable_status'=>2
			])
			->all();
		foreach($shops as $shop){
			$results[] = $shop->dp_shop_id;
		}
		return $results;
	}

	// 催款金额是否符合规则
	public function ruleTotal(DpRule $rule, OdOrder $order){
		if($this->isTest){
			$this->log($order->order_source_order_id.'判断催款金额是否符合规则');
		}
		$min_money = $rule->min_money;
		$max_money = $rule->max_money;
		$grand_total = $order->grand_total;
		if($min_money === null){
			if( $max_money === null ){
				if($this->isTest){
					$this->log($order->order_source_order_id.'符合规则');
				}
				return true;
			}elseif($grand_total < $max_money){
				if($this->isTest){
					$this->log($order->order_source_order_id.'符合规则');
				}
				return true;
			}elseif($grand_total > $max_money){
				return false;
			}
			return false;
		}elseif( $grand_total > $min_money ){
			if( $max_money === null ){
				return true;
			}elseif( $grand_total > $max_money){
				return false;
			}
			if($this->isTest){
				$this->log($order->order_source_order_id.'符合规则');
			}
			return true;
		}else{
			return false;
		}
		if($this->isTest){
			$this->log($order->order_source_order_id.'符合规则');
		}
		return true;
	}

	//判断是否为最后一次催款
	public function ruleDunning(DpRule $rule,OdOrder $order){
		if($this->isTest){
			$this->log('判断是否开启二、三次催款');
		}
		//判断是否开启第二次催款
		if($rule->timeout2 == 0){
			return false;
		}elseif($rule->timeout2 > 0 && $rule->timeout3 == 0 ){
			//判断催款是否为最后一次
			$had = DpInfo::find()
				->where([
					'source_id' => $order->order_source_order_id,
					'contacted' => 0
				])
				->count();
			if($had > 1){
				return false;
			}else{
				if($this->isTest){
					$this->log('符合规则');
				}
				return true;
			}
		}
		//判断是否开启第三次催款
		if($rule->timeout3 == 0){
			return false;
		}else{
			//判断催款是否为最后一次
			$had = DpInfo::find()
				->where([
					'source_id' => $order->order_source_order_id,
					'contacted' => 0
				])
				->count();
			if($had > 2){
				return false;
			}else{
				if($this->isTest){
					$this->log('符合规则');
				}
				return true;
			}
		}
		if($this->isTest){
			$this->log('符合规则');
		}
		return true;
	}


	public function runTest(){
		// $this->attr = new \stdClass;
		//显示催款用户ID
		$this->isTest = true;

		$order_id = $_POST['order_id'];
		var_dump($order_id);
		if( empty($order_id)){
			$this->log('请填写完整信息');
			return false;
		}
		$order = OdOrder::find()->where(['order_source_order_id'=>$order_id])->one();
		$result = $this->run($order);
		return $result;
	}

}
