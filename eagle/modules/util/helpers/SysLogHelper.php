<?php

namespace eagle\modules\util\helpers;
use eagle\modules\util\helpers\GetControlData;
use eagle\modules\util\models\SysLog;
use eagle\modules\util\models\SysInvokeJrn;
use eagle\modules\util\models\GlobalLog;
/*+----------------------------------------------------------------------
| 小老板
+----------------------------------------------------------------------
| Copyright (c) 2011 http://www.xiaolaoban.cn All rights reserved.
+----------------------------------------------------------------------
| Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
+----------------------------------------------------------------------
| Author: yang zeng qiang
+----------------------------------------------------------------------
| Create Date: 2014-06-04
+----------------------------------------------------------------------
 */

/**
 +------------------------------------------------------------------------------
 * log模块
 +------------------------------------------------------------------------------
 * @category	SysLog
 * @package		Helper
 * @subpackage  Exception
 * @author		YZQ
 * @version		1.0
 +------------------------------------------------------------------------------
 */
class SysLogHelper {
	/**
	 +---------------------------------------------------------------------------------------------
	 * Write an Interface Invoke Journal
	 * This is very IMPORTANT to trace the issues by invoking between modules
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param module_name			Module Name;
	 * @param class_name			class_name;
	 * @param function_name			function_name;
	 * @param param_array			array of parameters, each parameter can be any type of object;
	 +---------------------------------------------------------------------------------------------
	 * @return						Journal Id
	 * @description					TODO::Has to create housekeeping job and hook this file around the 
	 * 								cleaning task list
	 *
	 * @invoking					SysLogHelper::InvokeJrn_Create("purchase",__CLASS__, __FUNCTION__,"",array('id'=>'OD001',comment=>'good note'));
	 *
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		yzq		2014/05/17				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	public static function InvokeJrn_Create($module_name, $class_name, $function_name, $param_array){
		$rtn = -1;
		$InvokeJrnModel = new SysInvokeJrn();
		//format the values
		$InvokeJrnModel->process_id = GetControlData::getProcessId(0);
		$InvokeJrnModel->module = ucfirst( strtolower($module_name) ); 
		$InvokeJrnModel->class = $class_name;
		$InvokeJrnModel->function = $function_name;
		$InvokeJrnModel->create_time = GetControlData::getNowDateTime_str();
		$ind = 0;
		$data = array();

		foreach ($param_array as $aParameter){
			$ind ++;
			if ($ind <= 10) //support max 10 parameters to be journaled
				$data[ "param_$ind" ] = json_encode($aParameter);	
		}

		$InvokeJrnModel->setAttributes ($data) ;

		if ( $InvokeJrnModel->save(false) ){//save successfull
			//$rtn['success']=true;
			//$rtn['message'] = TranslateHelper::t("修改成功!请手动刷新一下更新数据!") ;
		}else{
			//$rtn['success']=false;
			//$rtn['message'] .= TranslateHelper::t("ETRK014 保存Tracking数据失败") ;
			foreach ($InvokeJrnModel->errors as $k => $anError){
				echo  '<br>'. $k.":".$anError[0];
			}
		}//end of save failed

		if (isset($InvokeJrnModel->id) and $InvokeJrnModel->id <> null )
			$rtn = $InvokeJrnModel->id;

		return $rtn;
	}
	

	/**
	 +---------------------------------------------------------------------------------------------
	 * update an Interface Invoke Journal for the result code
	 * This is very IMPORTANT to trace the issues by invoking between modules
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param Journal Id			Id,int;
	 * @param result				result can be any format, array is fine;	 
	 +---------------------------------------------------------------------------------------------
	 * @return						-1: falied
	 * 								1: success
	 * @description
	 *
	 * @invoking					SysLogHelper::InvokeJrn_UpdateResult($journal_id, array('rtn'=>'true','message'=>'OK'));
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		yzq		2014/05/17				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	public static function InvokeJrn_UpdateResult($journal_id, $result){
		$rtn = -1;
		$InvokeJrnModel = SysInvokeJrn::find()->andWhere(['id'=>$journal_id])->one();
		if (!isset($InvokeJrnModel) or $InvokeJrnModel == null ){
			
		}else
		{	//update the pending ship number
			$InvokeJrnModel->return_code = json_encode($result);	
			$InvokeJrnModel->save(false);
		}
	
		return $rtn;
	}
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * Write a log 
	 * This is very IMPORTANT to trace the issues by invoking between modules
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param module				enum('Catalog', 'Customer', 'Delivery', 'Finance', 'Inventory', 'Order', 'Permission', 'Platform', 'Purchase', 'Report', 'Ticket')
	 * @param class					which class makes this log
	 * @param function              which function makes this log
	 * @param level					level of the log('info', 'error', 'warning', 'trace')
	 * @param remark				Remark description	 
	 +---------------------------------------------------------------------------------------------
	 * @return						1
	 * @description					TODO::Has to create housekeeping job and hook this file around the
	 * 								cleaning task list
	 * @invoking					SysLogHelper::SysLog_Create("purchase",__CLASS__, __FUNCTION__,"","try to return mode_id is $mode_id  but not isset", "trace");
	 *
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		yzq		2014/05/17				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	public static function SysLog_Create( $module='Inventory',$class,$function,$level='trace',$remark=''){
		//判断是否开启了 某个 log type in Main
		//if (isset(Yii::app()->sysLog[$log_type]) and Yii::app()->sysLog[$log_type] == false)
		//	return 0;
		
		$SysLogModel = new SysLog();
		//format the values 
		$SysLogModel->level = ucfirst(strtolower($level));
		$SysLogModel->module = ucfirst(strtolower($module));
		$SysLogModel->class = $class;
		$SysLogModel->function = $function;
		$SysLogModel->remark = $remark;
		$SysLogModel->create_time = GetControlData::getNowDateTime_str();
		$SysLogModel->save(false);
	
		return 1;
	}

	/**
	 +---------------------------------------------------------------------------------------------
	 * Write a log
	 * This is the record for all the users. Such as the amazon background job , order list sync
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param module				enum('Catalog', 'Customer', 'Delivery', 'Finance', 'Inventory', 'Order', 'Permission', 'Platform', 'Purchase', 'Report', 'Ticket')
	 * @param class					which class makes this log
	 * @param function              which function makes this log
	 * @param tag					tag of the log
	 * @param remark				Remark description
	 * @param log_type				enum('Info', 'Error', 'Debug', 'Trace'); Default/Blank is Info
	 * @param job_no				Job number, if online job, leave it blank; Default/Blank is OK.
	 * @param job_type				enum('Batch', 'Online', 'Background'); Default/Blank is Online
	 +---------------------------------------------------------------------------------------------
	 * @return						1
	 * @description					TODO::Has to create housekeeping job and hook this file around the
	 * 								cleaning task list
	 * @invoking					SysLogHelper::GlobalLog_Create("purchase",__CLASS__, __FUNCTION__,"","try to return mode_id is $mode_id  but not isset", "trace");
	 *
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		xjq		2014/08/01				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	public static function GlobalLog_Create( $module='Inventory',$class,$function,$tag='',$remark='',$log_type='',$job_no='', $job_type=''){
		if ($job_type=='') 		$job_type = 'Online';
		if ($log_type=='') 		$log_type = 'Info';
	
		//判断是否开启了 某个 log type in Main
		//if (isset(Yii::app()->sysLog[$log_type]) and Yii::app()->sysLog[$log_type] == false)
		//	return 0;
	
		$SysLogModel = new GlobalLog();
		//format the values
		$SysLogModel->job_no = $job_no;
		$SysLogModel->job_type = ucfirst(strtolower($job_type));
		$SysLogModel->log_type = ucfirst(strtolower($log_type));
		$SysLogModel->module = ucfirst(strtolower($module));
		$SysLogModel->class = $class;
		$SysLogModel->function = $function;
		$SysLogModel->tag = $tag;
		$SysLogModel->remark = $remark;
		$SysLogModel->create_time = GetControlData::getNowDateTime_str();
		$SysLogModel->save(false);
	
		return 1;
	}
		
	

}