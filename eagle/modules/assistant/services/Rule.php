<?php namespace eagle\modules\assistant\services;

use eagle\models\OmOrderMessageInfo;
use eagle\modules\order\models\OdOrder;

class Rule 
{

	public $runQueue = [
		['rule\due','run']
	];
	public $puid;

	protected $__instance = [];

	public function __construct($puid=NULL){
		// if(!$puid){
		// 	$puid = \Yii::$app->user->id;
		// }
		if(!$puid){
			$puid = 0;
		}
		$this->puid = $puid;
	}

	public function __run($order){
		foreach($this->__match($order) as $result){
			$this->__log($order,$result);
		}
	}

	public function __match($order){
		$this->log('************ '.$order->order_source_order_id.' is running ***************');
		$results = [];
		$param_arr = [$order]; 								// 传入给执行脚本的参数
		foreach($this->runQueue as $rule){
			$className = __NAMESPACE__.'\\'.$rule[0];
			if(!isset($this->__instance[$className])){ 		// 单例模式，由于每次切换子库都会重新new本类，因此这里单例的范围是针对每个puid的
				$this->__instance[$className] = new $className($this->puid);
			}
			$methodName = $rule[1];
			$results[] = call_user_func_array([$this->__instance[$className], $methodName], $param_arr);
		}
		return $results;
	}

	public function __log($order,$result){
		if($result){
			$info = new OmOrderMessageInfo;
			$info->order_id = $order->order_id;
			$info->order_source_order_id = $order->order_source_order_id;
			$info->order_source = $order->order_source;
			$info->rule_type = $result['rule_type'];
			$info->rule_id = $result['rule_id'];
			$info->info_id = $result['info_id'];
			$info->create_time = date("Y-m-d H:i:s");
			$info->save();
		}
	}

	// 保存日志（只对命令行和test控制器有效）
	protected function log($output,$type = 'info'){
		$_response = \Yii::$app->response;
		$str = is_string($output)?$output:print_r($output,true);
		if( (\Yii::$app->controller->module->id == 'app-console' || \Yii::$app->controller->id == 'test') ){ // 判断是否来自控制台
			if(isset($_response->format) && $_response->format=='html'){
				echo '<pre>'.$str.'</pre>';
			}else{
				echo $str.PHP_EOL;
			}
		}
		\Yii::$type($output,'file');
		return true;
	}

	public function switchSubDb($platform, $callback){
		// 获取平台用户列表
		$model = '\eagle\models\Saas'.ucfirst($platform).'User';
		$users = $model::find()
			->select('uid')->distinct()
			->where(['is_active'=>1]);
		foreach($users->all() as $user){
			 
			if(true){
				$this->log('-- changeUserDataBase success: '.$user->uid);
				$callback($user->uid);
			}else{
				$this->log('!! changeUserDataBase fail: '.$user->uid);
			}
		}
	}

	/**
	 * 消息内容的参数替换
	 * @param  String  $content [description]
	 * @param  OdOrder $order   [description]
	 * @return [type]           [description]
	 */
	public function contentLabelReplace($content, OdOrder $order){
		$arr = [
			'[买家姓名]' 			=> $order->consignee,
			'[原始金额]' 			=> $order->grand_total,
			'[订单号]' 			=> $order->order_source_order_id,
			'[订单链接]' 			=> 'http://trade.aliexpress.com/order_detail.htm?orderId='.$order->order_source_order_id,
		];
		return str_replace(array_flip($arr), $arr, $content);
	}






}