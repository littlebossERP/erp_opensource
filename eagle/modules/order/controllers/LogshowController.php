<?php

namespace eagle\modules\order\controllers;

use eagle\modules\util\models\OperationLog;
use yii\data\Pagination;
use eagle\modules\app\apihelpers\AppTrackerApiHelper;
class LogshowController extends \eagle\components\Controller
{
	public $enableCsrfValidation = false;
    public function actionList()
    {
    	AppTrackerApiHelper::actionLog("Oms-erp", "/logshow/list");
    	$data = OperationLog::find(); // 其他模块也可能操作订单数据 ->andWhere('log_type = "order"')
    	if (!empty($_REQUEST['orderid'])){
    		$long_order_id = str_pad($_REQUEST['orderid'], 11, "0", STR_PAD_LEFT);//11位order id
    		$int_order_id = (int)$_REQUEST['orderid'];// 整型 order id
    		$data->andWhere(['log_key'=>[$_REQUEST['orderid'],$long_order_id,$int_order_id]]);
    		//$data->andWhere('log_key = :lk',[':lk'=>$_REQUEST['orderid']]);
    	}
    	if (!empty($_REQUEST['type'])){
    		$data->andWhere('log_operation = :lo',[':lo'=>$_REQUEST['type']]);
    	}
    	$data->orderBy('update_time desc');
    	$pages = new Pagination(['totalCount' => $data->count(),'pageSize'=>'50','params'=>$_REQUEST]);
    	$logs = $data->offset($pages->offset)
    	->limit($pages->limit)
    	->all();
        return $this->render('list',['logs'=>$logs,'pages'=>$pages]);
    }

}
