<?php
/*+----------------------------------------------------------------------
| 小老板
+----------------------------------------------------------------------
| Copyright (c) 2011 http://www.xiaolaoban.cn All rights reserved.
+----------------------------------------------------------------------
| Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
+----------------------------------------------------------------------
| Author: lzhl
+----------------------------------------------------------------------
| Create Date: 2016-8
+----------------------------------------------------------------------
 */
namespace eagle\modules\message\helpers;
use yii;
use yii\data\Pagination;

use eagle\modules\util\helpers\OperationLogHelper;
use eagle\modules\util\helpers\StandardConst;
use eagle\modules\util\helpers\TranslateHelper;
use eagle\modules\util\helpers\GetControlData;
use yii\helpers\StringHelper;
use eagle\modules\util\helpers\ConfigHelper;
use yii\base\Exception;
use common\helpers\Helper_Array;
use eagle\modules\util\helpers\TimeUtil;
use eagle\modules\util\helpers\SQLHelper;
use eagle\models\UserEdmQuota;
use eagle\modules\util\helpers\MailHelper;
use eagle\modules\message\models\EdmSentHistory;
use eagle\modules\amazoncs\models\CsMailQuestList;
use eagle\modules\amazon\apihelpers\AmazonApiHelper;
use eagle\modules\amazoncs\helpers\AmazoncsHelper;

/**
 * 
 +------------------------------------------------------------------------------
 * EDM功能helper
 +------------------------------------------------------------------------------
 * @category	Order
 * @package		Helper/message
 * @subpackage  Exception
 * @author		lzhl
 +------------------------------------------------------------------------------
 */
class EdmHelper {
	public static function queueHandlerProcessing(){
		echo "\n enter queueHandlerProcessing ;";
		$queue_table  ="edm_email_send_queue";
		$one_go_count = 100;//每次运行处理的队列记录数
		$now_str = date ( 'Y-m-d H:i:s' );
		$command = \Yii::$app->db_queue2->createCommand ( "select * from $queue_table where status=0 and act_name<>'amazoncs' and (pending_send_time IS NULL or pending_send_time <= '".$now_str."') order by pending_send_time, priority asc limit $one_go_count" );
		$rows= $command->queryAll ();
		
		echo "\n get ".count($rows)."records to handle;";
		$queueDataGroupByUid = [];
		foreach ($rows as $row){
			$queueDataGroupByUid[$row['puid']][] = $row;
		}
		
		foreach ($queueDataGroupByUid as $puid=>$datas){
			echo "\n start to handle puid:".$puid." queue data";
			$sent_success = 0;
			$sent_failed = 0;
			$update_data_arr = [];//操作后需要update user库的数据
			/**********************step1 loop发送，并将返回结果保存到$update_data_arr****************************/
			foreach ($datas as $data){
				
				if(!empty($data['pending_send_time'])){
					$pending_send_time = strtotime($data['pending_send_time']);
					//未到预计发送时间，则跳过
					if($pending_send_time > time())
						continue;
				}
				
				$pk_id = $data['id'];
				$his_id = $data['history_id'];//user库的历史表id
				$thisStatus = $data['status'];
				$error_message = '';
				$addi_info = json_decode($data['addi_info'],true);
				if(empty($addi_info)) $addi_info = [];
				$fromEmail = $data['send_from'];
				$fromName = empty($addi_info['from_name'])?'LittleBoss-ERP':$addi_info['from_name'];
				$toEmail = $data['send_to'];
				$subject = empty($addi_info['subject'])?'':$addi_info['subject'];
				$body = empty($addi_info['body'])?'':$addi_info['body'];
				$actName = empty($data['act_name'])?'':$data['act_name'];
				
				//调用发送邮件接口
				$rtn = MailHelper::sendMailBySQ($fromEmail, $fromName, $toEmail, $subject, $body, $actName);
				print_r($rtn);
				if(isset($rtn->Send2Result) && ($rtn->Send2Result=='Sent success' || $rtn->Send2Result=='Your email has submited successfully and will be send out soon.')){
					$sent_success ++;
					$thisStatus = 1;
				}else{
					$sent_failed ++;
					$thisStatus = 2;
					$error_message = is_string($rtn)?$rtn : print_r($rtn,true);
				}
				$now_str = date ( 'Y-m-d H:i:s' );
				//保存发送结果
				$command = \Yii::$app->db_queue2->createCommand ( "update $queue_table set status=$thisStatus ,update_time='$now_str' "
						.(!empty($error_message)?", error_message = :err_msg ":"" )."
						where id = $pk_id" );
				if (!empty($error_message)){
					$command->bindValue ( ':err_msg', $error_message, \PDO::PARAM_STR );
				}
				$affectRows = $command->execute();
				
				$update_data_arr[$his_id] = ['status'=>$thisStatus, 'act_name'=>$actName, 'error_message'=>$error_message];
			}
			/**********************step2 update_data_arr的结果更新到user库****************************/
			 
			if($sent_success)
				echo "\n sent $sent_success mail success!";
			if($sent_failed){
				echo "\n sent $sent_failed mail failed!";
				//发送失败时，需要补充相应quota给用户，应为建立发送记录时预扣
				$ch = self::EdmQuotaChange($puid, $sent_failed,'+');
				if(!$ch['success']){
					echo "\n user EdmQuotaChange failed:".$ch['message'];
					//额度变更失败后不跳出loop，继续update
				}
			}
			echo "\n start to update history table ";
			foreach ($update_data_arr as $his_id=>$update){
				if(!empty($update['act_name']) && $update['act_name']=='amazoncs' ){
					//amazoncs app 发送结果回写
					$his_data = CsMailQuestList::findOne($his_id);
					if(!empty($his_data)){
						$his_data->status = ($update['status']==1)?'C':'F';
						if(!empty($update['error_message'])){
							$addi_info = empty($his_data->addi_info)?[]:json_decode($his_data->addi_info,true);
							$addi_info['sent_error'] = $update['error_message'];
							$his_data->addi_info = json_encode($addi_info);
						}
						$update_time = TimeUtil::getNow();
						$his_data->update_time =$update_time;
						$his_data->sent_time_location = $update_time;//sent_time_location //sent_time_consignee
						
						$consignee_country_code = '';
						switch ($his_data->platform){
							case 'amazon' :
								$consignee_country_code = AmazonApiHelper::getCountryCodeByMarketPlaceId($his_data->site_id);
							default:
								break;
						}
						
						//时区转换
						$jet_lag = AmazoncsHelper::getCountryJetLagWithGMT8($consignee_country_code);
						if(empty($jet_lag))
							$his_data->sent_time_consignee = $his_data->sent_time_location;
						else{
							$his_data->sent_time_consignee = strtotime($his_data->sent_time_location)-3600*$jet_lag;
							$his_data->sent_time_consignee = date("Y-m-d H:i:s",$his_data->sent_time_consignee);
						}
						
						if(!$his_data->save()){
							echo "\n history-$his_id update failed:";
							print_r($his_data->getErrors());
						}
					}else{
						echo "\n update failed: history-$his_id missing";
					}
				}else{
					//其他app 发送结果回写
					$his_data = EdmSentHistory::findOne($his_id);
					if(!empty($his_data)){
						$his_data->status = $update['status'];
						$his_data->error_message = $update['error_message'];
						if(!$his_data->save()){
							echo "\n history-$his_id update failed:";
							print_r($his_data->getErrors());
						}
					}else{
						echo "\n update failed: history-$his_id missing";
					}
				}
			}
			echo "\n end of update history table.";
		}//end foreach $queueDataGroupByUid
	}
	
	
	public static function queueSesHandlerProcessing(){
		echo "\n enter queueSesHandlerProcessing ;";
		$queue_table  ="edm_email_send_queue";
		$one_go_count = 100;//每次运行处理的队列记录数
		$now_str = date ( 'Y-m-d H:i:s' );
		//$command = \Yii::$app->db_queue2->createCommand ( "select * from $queue_table where status=0 and act_name='amazoncs' and (pending_send_time IS NULL or pending_send_time <= '".$now_str."') order by pending_send_time, priority asc limit $one_go_count" );
		$command = \Yii::$app->db_queue2->createCommand ( "select * from $queue_table where (`status`=0 or (`status`=2 and `retry_count`<10)) and (`pending_send_time` IS NULL or `pending_send_time` <= '".$now_str."') order by `pending_send_time`, `priority` asc limit $one_go_count" );
		
		$rows= $command->queryAll ();
	
		echo "\n get ".count($command)."records to handle;";
		$queueDataGroupByUid = [];
		foreach ($rows as $row){
			$queueDataGroupByUid[$row['puid']][] = $row;
		}
	
		foreach ($queueDataGroupByUid as $puid=>$datas){
			echo "\n start to handle puid:".$puid." queue data";
			$sent_success = 0;
			$sent_failed = 0;
			$update_data_arr = [];//操作后需要update user库的数据
			/**********************step1 loop发送，并将返回结果保存到$update_data_arr****************************/
			foreach ($datas as $data){
	
				if(!empty($data['pending_send_time'])){
					$pending_send_time = strtotime($data['pending_send_time']);
					//未到预计发送时间，则跳过
					if($pending_send_time > time())
						continue;
				}
	
				$pk_id = $data['id'];
				$his_id = $data['history_id'];//user库的历史表id
				$thisStatus = $data['status'];
				$error_message = '';
				$addi_info = json_decode($data['addi_info'],true);
				if(empty($addi_info)) $addi_info = [];
				$fromEmail = $data['send_from'];
				$fromName = empty($addi_info['from_name'])?'LittleBoss-ERP':$addi_info['from_name'];//此参数SES不需要
				$toEmail = $data['send_to'];
				$subject = empty($addi_info['subject'])?'':$addi_info['subject'];
				$body = empty($addi_info['body'])?'':$addi_info['body'];
				$actName = empty($data['act_name'])?'':$data['act_name'];//此参数SES不需要,但小老板需要
				$retry = empty($data['retry_count'])?0:(int)$data['retry_count'];
				
				try{
					//调用发送邮件接口
					//$rtn = MailHelper::sendEmailByAmazonSES($fromEmail, $fromName, $toEmail, $subject, $body, $actName);
					$rtn = MailHelper::sendEmailBySendGrid($fromEmail, $fromName, $toEmail, $toName='', $subject, $body, $actName);
					print_r($rtn);
					
					if($rtn['success']){
						$sent_success ++;
						$thisStatus = 1;
						$error_message = empty($rtn['response'])?'':print_r($rtn['response'],true);
					}else{
						$sent_failed ++;
						$thisStatus = 2;
						$error_message = is_string($rtn)?$rtn : print_r($rtn,true);
						//超时的话不算失败次数
						//if(stripos($error_message,'timed out after')==false)
						$retry += 1;
					}
				}catch(\Exception $e){
					$sent_failed ++;
					$thisStatus = 2;
					$retry += 1;
					$error_message = print_r($e->getMessage());
				}
				
				$now_str = date ( 'Y-m-d H:i:s' );
				try{
					//保存发送结果
					$command = \Yii::$app->db_queue2->createCommand ( "update $queue_table set status=$thisStatus ,update_time='$now_str', retry_count=$retry "
							.(!empty($error_message)?", error_message = :err_msg ":"" )."
							where id = $pk_id" );
					if (!empty($error_message)){
						$command->bindValue ( ':err_msg', $error_message, \PDO::PARAM_STR );
					}
					$affectRows = $command->execute();
					
					if($data['act_name']=='amazoncs'){
						$update_data_arr[$his_id] = ['status'=>$thisStatus, 'act_name'=>$actName, 'error_message'=>$error_message];
					}
				}catch(\Exception $e){
					print_r($e->getMessage());
				}
				
			}
			/**********************step2 update_data_arr的结果更新到user库****************************/
		 
			if($sent_success)
				echo "\n sent $sent_success mail success!";
				if($sent_failed){
					echo "\n sent $sent_failed mail failed!";
					
					/* #todo暂时不使用额度
					//发送失败时，需要补充相应quota给用户，应为建立发送记录时预扣
					$ch = self::EdmQuotaChange($puid, $sent_failed,'+');
					if(!$ch['success']){
						echo "\n user EdmQuotaChange failed:".$ch['message'];
						//额度变更失败后不跳出loop，继续update
					}
					*/
				}
				
				foreach ($update_data_arr as $his_id=>$update){
					echo "\n start to update history table ";
					if(!empty($update['act_name']) && $update['act_name']=='amazoncs' ){
						//amazoncs app 发送结果回写
						$his_data = CsMailQuestList::findOne($his_id);
						if(!empty($his_data)){
							$his_data->status = ($update['status']==1)?'C':'F';
							if(!empty($update['error_message'])){
								$addi_info = empty($his_data->addi_info)?[]:json_decode($his_data->addi_info,true);
								$addi_info['sent_error'] = $update['error_message'];
								$his_data->addi_info = json_encode($addi_info);
							}
							$update_time = TimeUtil::getNow();
							$his_data->update_time =$update_time;
							$his_data->sent_time_location = $update_time;//sent_time_location //sent_time_consignee

							$consignee_country_code = '';
							switch ($his_data->platform){
								case 'amazon' :
								$consignee_country_code = AmazonApiHelper::getCountryCodeByMarketPlaceId($his_data->site_id);
								default:
								break;
							}
	
							//时区转换
							$jet_lag = AmazoncsHelper::getCountryJetLagWithGMT8($consignee_country_code);
							if(empty($jet_lag))
								$his_data->sent_time_consignee = $his_data->sent_time_location;
							else{
								$his_data->sent_time_consignee = strtotime($his_data->sent_time_location)-3600*$jet_lag;
								$his_data->sent_time_consignee = date("Y-m-d H:i:s",$his_data->sent_time_consignee);
							}
	
							if(!$his_data->save()){
								echo "\n history-$his_id update failed:";
								print_r($his_data->getErrors());
							}
						}else{
							echo "\n update failed: history-$his_id missing";
						}
					}else{
						//其他app 发送结果回写
						$his_data = EdmSentHistory::findOne($his_id);
						if(!empty($his_data)){
							$his_data->status = $update['status'];
							$his_data->error_message = $update['error_message'];
							if(!$his_data->save()){
								echo "\n history-$his_id update failed:";
								print_r($his_data->getErrors());
							}
						}else{
							echo "\n update failed: history-$his_id missing";
						}
					}
					echo "\n end of update history table.";
				}
				
			}//end foreach $queueDataGroupByUid
		}
		
	/**
	 * 用户EDM额度修改(非付费调用场景)
	 * @param int 		$puid
	 * @param int 		$quota
	 * @param string 	$type like '+' or 'add';'-' or 'subtract'
	 * @return array
	 */
	public static function EdmQuotaChange($puid,$quota,$type='-'){
		$rtn = ['success'=>true,'message'=>''];
		
		$quota_info = UserEdmQuota::findOne($puid);
		if(empty($quota_info)){
			$quota_info = new UserEdmQuota();
			$quota_info->uid = $puid;
			$quota_info->remaining_quota = 20;
			$quota_info->create_time = TimeUtil::getNow();
			$addi_info['payment_record'][] = '首次使用，获得20条免费email额度';
			$quota_info->addi_info = json_encode($addi_info);
			
			if(!$quota_info->save()){
				return ['success'=>false,'message'=>'首次使用时获得免费额度失败！'];
			}
		}
		
		$old_quota = (int)$quota_info->remaining_quota;
		if($type=='+' || strtolower($type)=='add')
			$new_quota = $old_quota + $quota;
		if($type=='-' || strtolower($type)=='subtract')
			$new_quota = $old_quota - $quota;
		
		if($new_quota<0){
			return ['success'=>false,'message'=>'剩余邮件发送额度不足！'];
		}
		
		$quota_info->remaining_quota = $new_quota;
		$quota_info->update_time = TimeUtil::getNow();
		
		if(!$quota_info->save()){
			return ['success'=>false,'message'=>print_r($quota_info->getErrors())];
		}else 
			return $rtn;
	}
}
