<?php
/**
 * @link http://www.witsion.com/
 * @copyright Copyright (c) 2014 Yii Software LLC
 * @license http://www.witsion.com/
 */
namespace eagle\modules\tracking\helpers;


use yii;
use yii\data\Pagination;
use eagle\modules\util\helpers\GoogleHelper;
use eagle\modules\tracking\models\Tracking;
use eagle\modules\util\models\GlobalLog;
use eagle\modules\util\helpers\HttpHelper;
use eagle\modules\tracking\helpers\CarrierTypeOfTrackNumber;
use eagle\modules\util\helpers\UserLastActionTimeHelper;
use eagle\modules\tracking\models\TrackerApiQueue;
use eagle\modules\tracking\models\TrackerApiSubQueue;
use eagle\modules\util\helpers\StandardConst;
use eagle\modules\util\helpers\TranslateHelper;
use eagle\modules\util\helpers\ExcelHelper;
use eagle\modules\util\helpers\ConfigHelper;
use eagle\modules\util\helpers\ResultHelper;
use yii\base\Exception;
use eagle\modules\util\helpers\OperationLogHelper;
use eagle\modules\platform\apihelpers\PlatformAccountApi;
use eagle\modules\util\helpers\SysLogHelper;
use eagle\modules\order\models\OdOrderShipped;


/**
 * Created by Yang Zeng Qiang. 
 * 2015-7-2
 *
 */
class TrackingApiHelper {
//状态
	const CONST_1= 1; //Sample

	/**
	 +---------------------------------------------------------------------------------------------
	 * 获取某个Tracking的全部信息
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param   tracking number     物流号

	 +---------------------------------------------------------------------------------------------
	 * @return						array('success'=true,'message'='',data=>array() )
	 *
	 * @invoking					TrackingApiHelper::getTrackingInfo($track_no );
	 *
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		yzq		2015/2/9				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
    static public function getTrackingInfo($trackNo){
    	 $rtn['success']= true;
    	 $rtn['message']= '';
    	 
    	$rtn['data'] = Tracking::find()
    		->where(" track_no=:kw2",array( ":kw2"=>$trackNo))
    		->asArray()
    		->one();

    	if (!empty($rtn['data'])){
    	$rtn['data']['status'] =Tracking::getChineseStatus($rtn['data']['status']);
    	$rtn['data']['state'] =Tracking::getChineseState($rtn['data']['state']);
    	$rtn['data']['all_event'] = json_decode($rtn['data']['all_event'] , true);
		if (empty($rtn['data']['all_event'])) 
			$rtn['data']['all_event'] = array();
    	}else 
    	{
    		$rtn['success']= false;
    		$rtn['message']= '没找到次Track Number：'.$trackNo;
    		$rtn['data'] = array();
    	}
    	
		return $rtn;
    }
    
    /**
     +---------------------------------------------------------------------------------------------
     * 更改某个Tracking的状态
     +---------------------------------------------------------------------------------------------
     * @access static
     +---------------------------------------------------------------------------------------------
     * @param   tracking number     物流号
     * @param	status				目标状态
     +---------------------------------------------------------------------------------------------
     * @return						array('success'=true,'message'='')
     +---------------------------------------------------------------------------------------------
     * log			name		date			note
     * @author		lzhl		2016/2/27		初始化
     +---------------------------------------------------------------------------------------------
     **/
    public static function changeTrackingStatus($trackNo,$status){
    	$rtn['success']= true;
    	$rtn['message']= '';
    	$model = Tracking::find()->where(['track_no'=>$trackNo])->one();
    	$journal_id = SysLogHelper::InvokeJrn_Create("Tracker",__CLASS__, __FUNCTION__ , array($trackNo,$status));
    	$errMsg='';
    	if(!empty($model)){
    		$model->status = $status;
    		$model->update_time = date("Y-m-d H:i:s");
    		
    		if(Tracking::getParcelStateByStatus($status)!=='--'){
    			$model->state = Tracking::getParcelStateByStatus($status);
    		}
    		if($status=='ignored')
    			$model->ignored_time = date("Y-m-d H:i:s");
    		
    		if(!$model->save()){
    			$rtn['success']= false;
    			$rtn['message']= '状态修改失败：E001';
    			$errMsg = print_r($model->getErrors());
    		}
    	}else{
    		$rtn['success']= false;
    		$rtn['message']= '该物流号不存在';
    	}
    	if(!empty($errMsg))
    		$rtn['errMsg']= $errMsg;
    	SysLogHelper::InvokeJrn_UpdateResult($journal_id, $rtn);
    	return $rtn;
    }
    
    /*
     * 通过tracking no和order no，查找od_order_shipped_v2，找出对应的承运商英文名
     * @author		lzhl		2016/7/22		初始化
     */
    public static function getTrackNoCarrierEnName($track_no, $order_no){
    	$carrier_name='';
    	if(!empty($track_no) && !empty($order_no)){
    		$orderShipped = OdOrderShipped::find()->where(['tracking_number'=>$track_no,'order_source_order_id'=>$order_no,'status'=>1])->one();
    		if(!empty($orderShipped)){
    			$carrier_name = $orderShipped->shipping_method_code;
    		}
    	} 
    	return $carrier_name;
    }
}
