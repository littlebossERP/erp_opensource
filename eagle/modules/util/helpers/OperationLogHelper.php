<?php

namespace eagle\modules\util\helpers;
use eagle\modules\util\models\OperationLog;
use eagle\modules\util\helpers;

class OperationLogHelper {
	
	static private $LogTypeMapping = [
		'order'=>'订单模块',
		'purchase'=>'采购模块',
		//'stock_change'=>'订单模块',
		'product'=>'商品模块',
		//'finance'=>'订单模块',
		'warehouse'=>'仓库模块',
		//'supplier'=>'订单模块',
		//'stock_take'=>'订单模块',
		'delivery'=>'发货模块',
		'wish_fanben'=>'wish刊登',
		'tracking'=>'物流跟踪助手'
		
	];
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * According to input, return the chinese log type.
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param $log_type				enum('purchase','stock_change','product','finance','warehouse','supplier','stock_take','delivery','order','wish_fanben');
	 * @param $wholeMap				是否整个映射数据全部获取
	 +---------------------------------------------------------------------------------------------
	 * @return  
	 * 			string log type 中文名 / array		log type 映射array	 		
	 +---------------------------------------------------------------------------------------------
	 * @invoking					OperationLogHelper::getChineseLogType("order");
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2016/01/05				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function getChineseLogType($logType='',$wholeMap = false){
		//when not passed valid log type , return the whole mapping array
		if ($wholeMap)
			return self::$LogTypeMapping;
		else{
			//when specified a log type , return its chinese label
			$allMap = self::$LogTypeMapping;
			if (isset($allMap[$logType]))
				return 	$allMap[$logType];
			else
				return $logType;
		}
	}//end of function getChineseLogType
	
	
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * According to input, return the operation logs.
	 * This is to load and show the operation log for order / purchase / package / warehouse operation log
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param $log_type				enum('purchase','stock_change','product','finance','warehouse','supplier','stock_take','delivery','order','wish_fanben');
	 * @param $key					orderid / purchase order id / warehouse id,etc	 
	 * @param $operation			具体的业务行为。如  修改采购单信息
	 * @param $comment				更加详细的备注	 
	 * @param $username				操作者名称。默认是操作的email，由于用户名称在eagle不是必须项。
	 +---------------------------------------------------------------------------------------------	 
	 *
	 * @invoking					OperationLogHelper::log("purchase","PO000001002","修改采购单信息");
	 *
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		xjq		2015/02/02				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	public static function log($type,$key,$operation,$comment="",$username="")
	{
		if ($username=="")  {
			if (\Yii::$app->id=="app-console") $username="system"; else
			$username=\Yii::$app->user->identity->getEmail();
		}
		$operationLogObject=new OperationLog;
		$operationLogObject->capture_user_name = $username; 
		$operationLogObject->log_key=$key;
		$operationLogObject->log_type=$type;
		$operationLogObject->update_time=TimeUtil::getNow();
		$operationLogObject->log_operation=$operation;
		$operationLogObject->comment=$comment;
		$operationLogObject->save(false);
	}
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * According to input, return the operation logs.
	 * This is to load and show the operation log for order / purchase / package / warehouse operation log
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param $type				enum('purchase','stock_change','product','finance','warehouse','supplier','stock_take','delivery','order','wish_fanben');
	 * @param $keyList				array	orderid / purchase order id / warehouse id,etc
	 * @param $operation			具体的业务行为。如  修改采购单信息
	 * @param $comment				更加详细的备注
	 * @param $username				操作者名称。默认是操作的email，由于用户名称在eagle不是必须项。
	 +---------------------------------------------------------------------------------------------
	 *
	 * @invoking					OperationLogHelper::batchInsertLog("order",[1,2,3],"重新上传");
	 *
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2017/02/22				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function batchInsertLog($type,$keyList,$operation,$comment="",$username=""){
		
		$logData = [];
		if ($username=="")  {
			if (\Yii::$app->id=="app-console") $username="system"; else
				$username=\Yii::$app->user->identity->getEmail();
		}
		foreach ($keyList as $key){
			$logData[] = [
				'capture_user_name'=>$username ,
				'log_key'=>$key,
				'log_type'=>$type,
				'update_time'=>TimeUtil::getNow(),
				'log_operation'=>$operation,
				'comment'=>$comment,
				
			];
		}
		if (!empty($logData)){
			SQLHelper::groupInsertToDb('operation_log_v2', $logData);
		}
		
	}//end of function batchInsertLog


/*
 * According to input, return the operation logs.
 * 
 * Invoke method: OperationLogHelper::loadOperationLog($type, $page, $rows, $sort, $order, $queryString);
 * 
*/
/**
	 +---------------------------------------------------------------------------------------------
	 * According to input, return the operation logs.
	 * This is to load and show the operation log for order / purchase / package / warehouse operation log
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param $type					Module Name, values are enum('purchase', 'stock_change', 'product', 'finance', 'warehouse', 'supplier', 'stock_take');
	 * @param $key					orderid / purchase order id / warehouse id,etc
	 * @param $page					PAGE NUMBER, default 1;
	 * @param $rows					rows per page, default 20;
	 * @param $sort					order field, ignore use db key field to sort;
	 * @param $order				order way, default ASC;
	 +---------------------------------------------------------------------------------------------
	 * @return						operation logs model array
	 *
	 * @invoking					OperationLogHelper::loadOperationLog("purchase","PO000001002");
	 *
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		yzq		2014/05/17				初始化
	 +---------------------------------------------------------------------------------------------
**/
/*public static function loadOperationLog($type,$key,$page, $rows, $sort, $order  ){
	$criteria = new CDbCriteria();
	$criteria->addCondition("log_type = '$type'");
	$criteria->addCondition("log_key = '$key'");
		 
	 
	if (!isset($rows) or $rows==null or $rows == 0)
		$rows = 20;
	
	if (!isset($page) or $page==null or $page == 0)
		$page = 1;
		
	$criteria->limit = $rows;
	$criteria->offset = ($page-1) * $rows;

	if (!isset($order) or $order==null or $order == 0 or $order=='')
		$order ='asc';
	
	if (!isset($sort) or $sort==null or $sort == 0 or $sort=='')
		{}else
		$criteria->order = "$sort $order";//排序条件
	
	//记录总行数
	$result['total'] = OperationLog::model()->count($criteria);
	$result['rows'] = GetControlData::formatModelsWithUserName(OperationLog::model()->findAll($criteria),"capture_user_id");
	return $result;
}*/

}

?>
