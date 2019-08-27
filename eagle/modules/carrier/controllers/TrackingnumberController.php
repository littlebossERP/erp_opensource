<?php

namespace eagle\modules\carrier\controllers;

use yii;
use yii\web\Controller;
use common\helpers\Helper_Array;
use yii\helpers\Url;
use eagle\modules\carrier\models\SysTrackingNumber;
use yii\data\Pagination;
use eagle\modules\carrier\apihelpers\CarrierApiHelper;
use eagle\modules\carrier\models\SysShippingService;
use yii\db\Transaction;
use eagle\modules\util\helpers\TranslateHelper;
use eagle\modules\app\apihelpers\AppTrackerApiHelper;
class TrackingnumberController extends \eagle\components\Controller
{
	public $enableCsrfValidation = false;
	public function actionList()
	{
		return "<a href='/configuration/carrierconfig/trackwarehouse'>请使用新的流程入口</a>";
		
		AppTrackerApiHelper::actionLog("eagle_v2","/carrier/trackingnumber/list");
		$pageSize = isset($_GET['per-page'])?$_GET['per-page']:15;
    	$query=SysTrackingNumber::find();
    	$pagination = new Pagination([
    			'defaultPageSize' => 15,
    			'pageSize' => $pageSize,
    			'totalCount' => $query->count(),
    			'pageSizeLimit'=>[15,200],//每页显示条数范围
    			//'params'=>$data,
    			]);
    	$result['pagination'] = $pagination;
    	$query->orderBy('id desc , shipping_service_id asc');
    	$query->limit($pagination->limit);
    	$query->offset( $pagination->offset );
    	$result['data'] = $query->asArray()->all();
    	$url_arr = array_merge(['/carrier/trackingnumber/list']);
    	$return_url = Url::to($url_arr);
    	return $this->render('list',['list'=>$result,'return_url'=>$return_url]);
	}
	
	public function actionSavetrackingnumber(){
		if(\Yii::$app->request->isPost){
			
			AppTrackerApiHelper::actionLog("eagle_v2","/carrier/trackingnumber/savetrackingnumber2");
			
			$shippingServiceId = \Yii::$app->request->post('shipping_service_id');
			if ($shippingServiceId==''){
				return json_encode(['error'=>1,'data'=>'','msg'=>'请选择运输服务！']);
			}
			$trackingNumber_str = \Yii::$app->request->post('tracking_number');
			if (strlen($trackingNumber_str)==0){
				return json_encode(['error'=>1,'data'=>'','msg'=>'请填写物流号！']);
			}
			$shippingService_obj = SysShippingService::findOne(['id'=>$shippingServiceId]);
			$trackingNumbers = explode("\n" ,$trackingNumber_str);
			Helper_Array::removeEmpty($trackingNumbers);
			$userName = \Yii::$app->user->identity->getFullName();
			if (strlen($userName)==0){
				$userName = $userName = \Yii::$app->user->identity->getUsername();
			}
			$exists = [];
			foreach ($trackingNumbers as $trackingNumber){
				$trackingNumber_obj = SysTrackingNumber::findOne(['tracking_number'=>$trackingNumber]);
				if ($trackingNumber_obj == null){
					$trackingNumber_obj = new SysTrackingNumber();
					$trackingNumber_obj->shipping_service_id = $shippingServiceId;
					$trackingNumber_obj->service_name = $shippingService_obj->service_name;
					$trackingNumber_obj->tracking_number = $trackingNumber;
					$trackingNumber_obj->is_used = 0;
					$trackingNumber_obj->user_name = $userName;
					$trackingNumber_obj->create_time = time();
					$trackingNumber_obj->update_time = time();
					$trackingNumber_obj->save();
				}else{
					$exists[] = $trackingNumber;
				}
			}
			return json_encode(['error'=>0,'data'=>$exists,'msg'=>'']);
		}
		
		AppTrackerApiHelper::actionLog("eagle_v2","/carrier/trackingnumber/savetrackingnumber");
		$services = CarrierApiHelper::getShippingServices(true,true);
		return $this->renderPartial('savetrackingnumber',['services'=>$services]);
	}
	public function actionDel(){
		try {
			$result = SysTrackingNumber::deleteAll(['id'=>\Yii::$app->request->get('id')]);
			if ($result>0){
				exit(json_encode(array("code"=>"ok","message"=>TranslateHelper::t('操作成功！'))));
			}else{
				exit(json_encode(array("code"=>"fail","message"=>TranslateHelper::t('删除失败！'))));
			}
		}catch (\Exception $ex){
			exit(json_encode(array("code"=>"fail","message"=>$ex->getMessage())));
		}
		exit(json_encode(array("code"=>"ok","message"=>TranslateHelper::t('操作成功！'))));
	}
}