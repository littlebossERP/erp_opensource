<?php namespace eagle\modules\assistant\controllers;

use eagle\modules\assistant\services\Rule;
use eagle\modules\order\models\OdOrder;
use eagle\models\assistant\DpEnable;
use eagle\models\assistant\DpInfo;



class TestController extends \eagle\components\Controller
{
	function __construct(){
		call_user_func_array('parent::__construct', func_get_args());
//		if(!isset($_SERVER['ENV']) || $_SERVER['ENV'] !== 'test'){
//			die;
//		}
	}

	function actionRule(){
		return $this->exec_all_orders(\Yii::$app->user->id);
	}

	// 模拟控制台自动催款脚本
		function actionConsole(){
			$platforms = ['aliexpress'];
			// 获取速卖通用户id
			$rule = new Rule();
			foreach($platforms as $platform){
				$rule->switchSubDb($platform,function($puid){
					$this->exec_all_orders($puid);
				});
			}

	}


	/*******************************************************/

	// 对当前用户下所有订单进行匹配
	function exec_all_orders($puid){
		$orders = OdOrder::find()->limit(10);
		$rule = new Rule($puid);
		$rule->runQueue = [
			['rule\Due','run'],
			['rule\Message','run'],  	// 对已付费订单操作(1:修改dp_info状态，2:进行留言)
			// ['rule\due','payed']
			// ['rule\haoping','func1']
		];
		foreach($orders->all() as $order){
			$rs = $rule->__run($order);
		}
	}

	/**
	 * 单元测试页面
	 * @return [type] [description]
	 */
	function actionTest(){
		$units = [
			'是否存在其他订单'=>[
				'class'=>'\eagle\modules\assistant\services\rule\due',
				'method'=>'hasOtherOrder',
				'args'=>['rule_id','order_id']
			],
			'是否存在已付款订单'=>[
				'class'=>'\eagle\modules\assistant\services\rule\due',
				'method'=>'hasPaydOrder',
				'args'=>['rule_id','order_id']
			],
			'匹配国家'=>[
				'class'=>'\eagle\modules\assistant\services\rule\due',
				'method'=>'ruleCountry',
				'args'=>['rule_id','order_id']
			],
			'客服是否联系过'=>[
				'class'=>'\eagle\modules\assistant\services\rule\due',
				'method'=>'isCustomServiceContacted',
				'args'=>['rule_id','order_id']
			],
			'获取订单催款次数'=>[
				'class'=>'\eagle\modules\assistant\services\rule\due',
				'method'=>'getOrderDueTimes',
				'args'=>['order_id']
			],
			'匹配时间'=>[
				'class'=>'\eagle\modules\assistant\services\rule\due',
				'method'=>'ruleTimeout',
				'args'=>['rule_id','order_id']
			],
			'对已付款订单留言'=>[
				'class'=>'\eagle\modules\assistant\services\rule\message',
				'method'=>'run',
				'args'=>['order_id']
			],
			// '客服是否联系过'=>[
			// 	'class'=>'\eagle\modules\assistant\services\rule\due',
			// 	'method'=>'ruleTimeout',
			// 	'args'=>['rule_id','order_id']
			// ],
			'调用催款接口'=>[
				'class'=>'\eagle\modules\assistant\services\rule\due',
				'method'=>'sendMsg',
				'args'=>['rule_id','order_id']
			],
			'催款金额是否符合规则'=>[
				'class'=>'\eagle\modules\assistant\services\rule\due',
				'method'=>'ruleTotal',
				'args'=>['rule_id','order_id']
			]
		];
		return $this->render('//layouts/unit_test',$units);
	}

	function getRuleAndOrderFromPost($methodName){
		$puid = \Yii::$app->user->id;
		$due = new \eagle\modules\assistant\services\rule\Due($puid);
		$order = OdOrder::find()->where(['order_source_order_id'=>$_POST['order_id']])->one();
		$result = $due->$methodName($order);
		return $this->renderJson(['code'=>$result]);
	}


	/////////////////////////////////////////////////////////////////////////////////

	function actionHasotherorder(){
		return $this->getRuleAndOrderFromPost('hasOtherOrder');
	}

 
	function actionHaspaydorder(){
		return $this->getRuleAndOrderFromPost('hasPaydOrder');
	}

 
	function actionRulecountry(){
		return $this->getRuleAndOrderFromPost('ruleCountry');
	}

	function actionIscustomservicecontacted(){
		return $this->getRuleAndOrderFromPost('isCustomServiceContacted');
	}

	function actionSendmsg(){
		return $this->getRuleAndOrderFromPost('sendMsg');
	}

	function actionRuletimeout(){
		return $this->getRuleAndOrderFromPost('ruleTimeout');
	}
	function actionRuletotal(){
		return $this->getRuleAndOrderFromPost('ruleTotal');
	}
	function actionGetorderduetimes(){
		$puid = \Yii::$app->user->id;
		$due = new \eagle\modules\assistant\services\rule\due($puid);
		$order = OdOrder::find()->where(['order_source_order_id'=>$_POST['order_id']])->one();
		$rs = $due->getOrderDueTimes($order);
		echo $rs;
	}
	function actionRulerun(){
		return $this->getRuleAndOrderFromPost('run');
	}

	function actionRuntest(){
		return $this->getRuleAndOrderFromPost('runTest');
	}

	function actionCheckdevice(){
		$units = [
			'催款订单校验器'=>[
				'class'=>'\eagle\modules\assistant\services\rule\Due',
				'method'=>'runTest',
				'args'=>['order_id']
			]
		];
		return $this->render('//layouts/unit_test',$units);
	}
//	function actionRun(){
//		$puid = \Yii::$app->user->id;
//		$due = new \eagle\modules\assistant\services\rule\message($puid);
//		$order = OdOrder::find()->where(['order_source_order_id'=>$_POST['order_id']])->one();
//		$rs = $due->run($order);
//		var_dump($rs);
//	}

//	function actionRun(){
//
//		//通过dp_enable表查询所有开启催款服务的卖家puid
//
//		$shop = DpEnable::find()->where(['enable_status'=>2])->all();
//		foreach($shop as $puid) {
//
//			$due = new \eagle\modules\assistant\services\rule\message($puid->dp_puid);
//
//			//切换数据库到$puid子库
//			if(true){
//				//查所有未付款订单，获得订单集合,odOrder表
//				$order = OdOrder::find()->where(['order_status'=>100])->andWhere('order_source_create_time'>=(time() - 20*24*3600))->all();
//				$this->processOrder($due, $order);
//				//当前用户的未付款订单的催款逻辑结束
//				//查所有（等待卖家发货）订单，获得订单集合,odOrder表
//				$ary_order = OdOrder::find()->where(['order_status'=>200])->all();
//				$this->processOrder($due, $ary_order);
//			}
//			//$order = OdOrder::find()->where(['order_source_order_id'=>$_POST['order_id']])->one();
//		}
//
//	}
//
//
//	function processOrder($due, $ary_order) {
//		foreach ($ary_order as $order) {
//			$rs = $due->run($order);
//			var_dump($rs);
//		}
//	}
	public function actionRun(){
		$count = 0;
		$success_count = 0;
		$paySuccessCount = 0;
		//脚本开始时间
		echo 'run-start:'.date("Y-m-d H:i:s").PHP_EOL;
		//通过dp_enable表查询所有开启催款服务的卖家puid
		$shop = DpEnable::find()->where(['enable_status'=>2])->groupBy(['dp_puid'])->all();
		foreach($shop as $puid) {

			$due = new \eagle\modules\assistant\services\rule\Due($puid->dp_puid);
			$dues = new \eagle\modules\assistant\services\rule\Message($puid->dp_puid);
			 
			if(true){
				//查所有未付款订单，获得订单集合,odOrder表
				$timeout = (time()-20*24*3600);
				$ar_order = OdOrder::find()->where(['>=','order_source_create_time',$timeout])->andWhere(['order_status'=>100])->andWhere(['order_source'=>'aliexpress',])->andWhere(['!=','order_source_status','RISK_CONTROL']);
				//统计订单总量
				$count += $ar_order->count();

				$order = $ar_order->all();
				self::processOrder($due, $order);
				//当前用户的未付款订单的催款逻辑结束

				//查所有（等待卖家发货）订单，获得订单集合,odOrder表
				$ar_order = OdOrder::find()->where(['order_status'=>200])->andWhere(['order_source'=>'aliexpress']);
				//统计订单总量
				$count += $ar_order->count();

				$order = $ar_order->all();
				self::processOrder($dues, $order);

				/* --- 检测提醒过的订单是否已完成付费 --- */
				$startTime = time()-20*24*3600;
				$endTime = time();
				$orders = DpInfo::find()
					->Where([
						'status'=>1,
						'due_status'=>1,
					])->andWhere([
						'BETWEEN',
						'create_time',
						date("Y-m-d H:i:s",$startTime),
						date("Y-m-d H:i:s",$endTime)
					])->all();
				echo ('not_paid_count:').count($orders).PHP_EOL;
				foreach($orders as $_order){
					$rawOrder = OdOrder::findOne($_order->order_id);
					if($rawOrder->order_status == OdOrder::STATUS_PAY){
						// 催款成功
						$_order->due_status = 2;
						$_order->pay_time = date("Y-m-d H:i:s", $rawOrder->paid_time);
						if($_order->save()){
							echo('order_write_back_success').PHP_EOL;
							$paySuccessCount++;
						}
					}
				}
			}

			$success_count += $due->success_count;
			$success_count += $dues->success_count;
		}


		echo 'pay_success_count:'.$paySuccessCount.PHP_EOL;
		echo 'all-order-number:'.$count.PHP_EOL;
		echo 'success_order:'.$success_count.PHP_EOL;
		//脚本结束时间

		echo 'run-end:'.date("Y-m-d H:i:s").PHP_EOL;

	}


	public function processOrder($due, $ary_order) {
//		foreach ($ary_order as $order) {
//			$rs = $due->run($order);
//		}
		return true;
	}




}