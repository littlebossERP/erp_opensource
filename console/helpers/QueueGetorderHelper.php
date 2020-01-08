<?php

namespace console\helpers;

use eagle\models\QueueGetorder;
use eagle\modules\util\helpers\SaasEbayUserHelper;
use common\api\ebayinterface\getorders;
use yii\base\Exception;
use eagle\modules\util\helpers\ConfigHelper;
use eagle\models\SaasPaypalUser;
use eagle\modules\order\models\OdEbayTransaction;
use eagle\modules\util\helpers\MailHelper;
/**
 * ebay 订单同步队列表 
 * @author lxqun
 * 2014-1-15
 */
class QueueGetorderHelper
{
	static public $QueueGetorderHelperVersion = '';
	/**
	 * 添加
	 * @param unknown_type $orderid
	 * @param unknown_type $selleruserid
	 * @param unknown_type $ebay_uid
	 * @param unknown_type $externalid
	 * @author lxqun
	 * @date 2014-2-6
	 */
	static function Add($orderid,$selleruserid,$ebay_uid,$externalid=null,$PayPalEmailAddress=null,$default_status=0){
		$M=QueueGetorder::find()->where('ebay_orderid=:p',array(':p'=>$orderid))->one();
		if(!$M){
			$M=new QueueGetorder;
			$M->ebay_orderid=$orderid;
			$M->selleruserid=$selleruserid;
			$M->paypalemailadress=$PayPalEmailAddress;
			//$M->ebay_uid=$ebay_uid;
			$M->created=time();
			$M->save();
		} else {
		    if($externalid == 5){// dzt20191106 不是插入的不更新
			        
			    }else{
				$M->status = $default_status;
				$M->updated = time();
				$M->save();
			}
		}
		
	}

	/**
	 * 取一条
	 * @param unknown_type $orderid
	 * @author lxqun
	 * @date 2014-5-21
	 */
	static function getOnePaypalEmailAddress($orderid){
		$M=QueueGetorder::find()->where('ebay_orderid=:p',array(':p'=>$orderid))->one();
		if(is_null($M)) return null;
		return $M->paypalemailadress;
	}
	
	/**
	 * 获取 ebay 订单是否需要检查
	 */
	static function getOnePaypalAddressOverWrite($orderid , $selleruserid){
		// 获取paypal email 地址
		$paypalEmail = OdEbayTransaction::findOne(['orderid'=>$orderid , 'selleruserid'=>$selleruserid])->paypalemailaddress;
		
		if (!empty($paypalEmail))
			$ppAccount = SaasPaypalUser::findOne(['paypal_user'=>$paypalEmail]);
		
		if (empty($ppAccount)) return '';
		
		return $ppAccount->overwrite_ebay_consignee_address;
	}

	/**
	 * 获取eBay订单数据(新订单)
	 */
	static function autoRequestEbayOrder($sub_id = ''){
		global $CACHE;
		
		if (empty($CACHE['JOBID']))
			$CACHE['JOBID'] = "MS".rand(0,99999);
		
		
		$JOBID = $CACHE['JOBID'];
	
		//Step 0, 检查当前本job版本，如果和自己的版本不一样，就自动exit吧
		$currentEbayAutoRequestEbayOrderVersion = ConfigHelper::getGlobalConfig("Order/ebayAutoRequestEbayOrder",'NO_CACHE');
		if (empty($currentEbayAutoRequestEbayOrderVersion))
			$currentEbayAutoRequestEbayOrderVersion = 0;
		
		//如果自己还没有定义，去使用global config来初始化自己
		if (empty(self::$QueueGetorderHelperVersion))
			self::$QueueGetorderHelperVersion = $currentEbayAutoRequestEbayOrderVersion;
			
		//如果自己的version不同于全局要求的version，自己退出，从新起job来使用新代码
		if (self::$QueueGetorderHelperVersion <> $currentEbayAutoRequestEbayOrderVersion){
			$msg = "Version new $currentOrderAutoCheckQueueVersion , this job ver ".self::$QueueGetorderHelperVersion." exits for using new version $currentEbayAutoRequestEbayOrderVersion.";
			exit($msg);
		}
		
		$totalJob = 5;
		//删除2 小时以前的 订单(进程半小时运行不玩kill，所以放在这执行一遍就行)
		QueueGetorder::deleteAll('status = 2 And created < '.(time() - 7200));
		//删除15天以前开启的请求同步，目前还是同步状态的订单
		QueueGetorder::deleteAll('status = 0 And created < '.(time() - 10*24*3600));
		//echo "\n job id = $job_id";
		
		$sub_id = '';// TODO 需要开多进程拉取再把这个去掉
		if ($sub_id !== '')
			$coreStr = ' and saas_ebay_user.uid %'.$totalJob.'= '.$sub_id;
		else 
			$coreStr = '';
		//超过时间未同步完成恢复状态
		
		$queryTmp = QueueGetorder::find()->leftJoin('saas_ebay_user',"saas_ebay_user.selleruserid  = queue_getorder.selleruserid ")->where('(queue_getorder.`status` = 0 AND queue_getorder.`updated` = 0) '.$coreStr)->orderBy('created asc')->limit(50);
		$result = $queryTmp->all();
		echo "\n $sub_id queue v2.33 count =".count($result);
		//echo "\n".$queryTmp->createCommand()->getRawSql();
		//exit();
		$temOidArr = array();
		$tempArr = array();
		foreach($result as $v) {
			$tempOidArr[$v->selleruserid][] = $v->ebay_orderid;
			$tempArr[$v->selleruserid][] = $v->attributes;
		}
		foreach($tempArr as $k => $v) {
			echo "\n[".date('H:i:s').'-'.__LINE__.'- selleruserid:'.$k."]\n";
			$eu = SaasEbayUserHelper::getOne($k);
			if (empty($eu)){
				echo "\n user $k has been deleted \n";
				QueueGetorder::deleteAll(['selleruserid'=>$k]);//清除队列中拉取的order
				continue;
			}
			 
			echo "start ebay api "."\n";
			$getOrders = new getorders();
			$getOrders->resetConfig($eu->DevAcccountID);
			$getOrders->eBayAuthToken=$eu->token;
			$getOrders->EntriesPerPage=30;
			$getOrders->PageNumber=1;

			$count = count($tempOidArr[$k]);
			for($i = 0, $c = $count; $i < $c; $i += $getOrders->EntriesPerPage) {
				$do_ebayorderids = array_slice($tempOidArr[$k], $i, $getOrders->EntriesPerPage);
				$flag = false;
				try {
					$effect = QueueGetorder::updateAll(['status'=>1,'updated'=>time()],['in','ebay_orderid',$do_ebayorderids]);
					echo "\n sync order effect = $effect and ebay_orderid=".json_encode($do_ebayorderids);
					$getOrders->_before_request_xmlarray['DetailLevel'] = 'ReturnAll';
					$getOrders->_before_request_xmlarray['OrderID'] = $do_ebayorderids;
					$flag = $getOrders->requestOrders($eu, $do_ebayorderids);
					$status = $flag ? 2 : 0;
					$effect = QueueGetorder::updateAll(['status'=>$status],['ebay_orderid'=>$do_ebayorderids]);
					echo "\n flag =$flag  status =$status effect = $effect and ebay_orderid=".json_encode($do_ebayorderids);
				} catch(\Exception $ex) {
					echo "\n".(__function__).' Error Message:'.$ex->getMessage()." Line no ".$ex->getLine();
				}
			}
		}
	}
	
	/**
	 * 获取eBay订单数据(旧的处理过的，可能因失败等原因卡主)
	 */
	static function autoRequestEbayOrder2($sub_id = ''){

		
		$totalJob = 5;
		$sub_id = '';// TODO 需要开多进程拉取再把这个去掉
		if ($sub_id !== '')
			$coreStr = ' and saas_ebay_user.uid %'.$totalJob.'= '.$sub_id;
		else
			$coreStr = '';
		
		
		$ReTryLimit = 10;
		
		if (!empty($ReTryLimit)){
			$coreStr .= " and retry_count<10 ";
		}
		
		//超过时间未同步完成恢复状态
		//$result = QueueGetorder::find()->where('(`status` = 1 AND `updated` < '.(time() - 240).') OR (`status` = 0 AND `created` <= '.time().' AND `updated` > 0)')->orderBy('updated asc')->limit(150)->all();
		$queryTmp = QueueGetorder::find()->leftJoin('saas_ebay_user',"saas_ebay_user.selleruserid  = queue_getorder.selleruserid ")->where('( (`status` = 1 AND `updated` < '.(time() - 240).') OR (`status` = 0 AND `created` <= '.time().' AND `updated` > 0) ) '.$coreStr)->orderBy('updated asc')->limit(150);
		$result = $queryTmp->all();
		echo "\n $sub_id queue v2.6 count =".count($result);
 		echo "\n".$queryTmp->createCommand()->getRawSql();
// 		exit();
		$temOidArr = array();
		$tempArr = array();
		foreach($result as $v) {
			$tempOidArr[$v->selleruserid][] = $v->ebay_orderid;
			$tempArr[$v->selleruserid][] = $v->attributes;
		}
		foreach($tempArr as $k => $v) {
			echo "\n[".date('H:i:s').'-'.__LINE__.'- selleruserid:'.$k."]\n";
			$eu = SaasEbayUserHelper::getOne($k);
			if (empty($eu)){
				echo "\n user $k has been deleted \n";
				QueueGetorder::deleteAll(['selleruserid'=>$k]);//清除队列中拉取的order
				continue;
			}
			 
			
			$getOrders = new getorders();
			$getOrders->resetConfig($eu->DevAcccountID);
			$getOrders->eBayAuthToken=$eu->token;
			$getOrders->EntriesPerPage=1;
			$getOrders->PageNumber=1;

			$count = count($tempOidArr[$k]);
			for($i = 0, $c = $count; $i < $c; $i += $getOrders->EntriesPerPage) {
				$do_ebayorderids = array_slice($tempOidArr[$k], $i, $getOrders->EntriesPerPage);
				$flag = false;
				try {
					$effect = QueueGetorder::updateAll(['status'=>1,'updated'=>time()],['in','ebay_orderid',$do_ebayorderids]);
					echo "\n sync order effect = $effect and ebay_orderid=".json_encode($do_ebayorderids);
					$getOrders->_before_request_xmlarray['DetailLevel'] = 'ReturnAll';
					$getOrders->_before_request_xmlarray['OrderID'] = $do_ebayorderids;
					$flag = $getOrders->requestOrders($eu, $do_ebayorderids);
					$status = $flag ? 2 : 0;
					$effect = QueueGetorder::updateAll(['status'=>$status],['ebay_orderid'=>$do_ebayorderids]);
					echo "\n flag =$flag  status =$status effect = $effect and ebay_orderid=".json_encode($do_ebayorderids);
				} catch(\Exception $ex) {
					echo "\n".(__function__).' Error Message:'.$ex->getMessage()." Line no ".$ex->getLine();
				}
			}
		}
	}
	
	//8为手工同步 
	static function autoRequestEbayOrder8($sub_id = ''){
		return self::_autoRequestEbayOrder(8,$sub_id);
	}//end of autoRequestEbayOrder8
	
	//9为大卖家
	static function autoRequestEbayOrder9($sub_id = ''){
		return self::_autoRequestEbayOrder(9,$sub_id);
		/*
		$totalJob = 5;
		$sub_id = '';// TODO 需要开多进程拉取再把这个去掉
		if ($sub_id !== '')
			$coreStr = ' and saas_ebay_user.uid %'.$totalJob.'= '.$sub_id;
		else
			$coreStr = '';
		
		
		$ReTryLimit = 10;
		
		if (!empty($ReTryLimit)){
			$coreStr .= " and retry_count<10 ";
		}
		
		//超过时间未同步完成恢复状态
		//$result = QueueGetorder::find()->where('(`status` = 1 AND `updated` < '.(time() - 240).') OR (`status` = 0 AND `created` <= '.time().' AND `updated` > 0)')->orderBy('updated asc')->limit(150)->all();
		$queryTmp = QueueGetorder::find()->leftJoin('saas_ebay_user',"saas_ebay_user.selleruserid  = queue_getorder.selleruserid ")->where('( status=9 ) '.$coreStr)->orderBy('updated asc')->limit(150);
		$result = $queryTmp->all();
		echo "\n $sub_id queue v1.0 count =".count($result);
		echo "\n".$queryTmp->createCommand()->getRawSql();
		$temOidArr = array();
		$tempArr = array();
		foreach($result as $v) {
			$tempOidArr[$v->selleruserid][] = $v->ebay_orderid;
			$tempArr[$v->selleruserid][] = $v->attributes;
		}
		if (!empty($tempArr)){
			self::_syncOrder($tempArr,$tempOidArr,9);
		}else{
			echo "\n 9 is finish ! ";
		}
		*/
		
	}//end of autoRequestEbayOrder9
	
	static private function _autoRequestEbayOrder($status, $sub_id = '' ,$totalJob='5' ,$ReTryLimit="10"){
		global $CACHE;
		
		if (empty($CACHE['JOBID']))
			$CACHE['JOBID'] = "MS".rand(0,99999);
		
		
		$JOBID = $CACHE['JOBID'];
		
		//Step 0, 检查当前本job版本，如果和自己的版本不一样，就自动exit吧
		$currentEbayAutoRequestEbayOrderVersion = ConfigHelper::getGlobalConfig("Order/ebayAutoRequestEbayOrder",'NO_CACHE');
		if (empty($currentEbayAutoRequestEbayOrderVersion))
			$currentEbayAutoRequestEbayOrderVersion = 0;
		
		//如果自己还没有定义，去使用global config来初始化自己
		if (empty(self::$QueueGetorderHelperVersion))
			self::$QueueGetorderHelperVersion = $currentEbayAutoRequestEbayOrderVersion;
			
		//如果自己的version不同于全局要求的version，自己退出，从新起job来使用新代码
		if (self::$QueueGetorderHelperVersion <> $currentEbayAutoRequestEbayOrderVersion){
			$msg = "Version new $currentOrderAutoCheckQueueVersion , this job ver ".self::$QueueGetorderHelperVersion." exits for using new version $currentEbayAutoRequestEbayOrderVersion.";
			exit($msg);
		}
		
		$sub_id = '';// TODO 需要开多进程拉取再把这个去掉
		if ($sub_id !== '')
			$coreStr = ' and saas_ebay_user.uid %'.$totalJob.'= '.$sub_id;
		else
			$coreStr = '';
		
		
		
		if (!empty($ReTryLimit)){
			$coreStr .= " and retry_count<$ReTryLimit ";
		}
		
		//超过时间未同步完成恢复状态
		//$result = QueueGetorder::find()->where('(`status` = 1 AND `updated` < '.(time() - 240).') OR (`status` = 0 AND `created` <= '.time().' AND `updated` > 0)')->orderBy('updated asc')->limit(150)->all();
		$queryTmp = QueueGetorder::find()->leftJoin('saas_ebay_user',"saas_ebay_user.selleruserid  = queue_getorder.selleruserid ")->where('( status='.$status.' ) '.$coreStr)->orderBy('updated asc')->limit(150);
		$result = $queryTmp->all();
		echo "\n $sub_id queue v1.0 count =".count($result);
		echo "\n".$queryTmp->createCommand()->getRawSql();
		$temOidArr = array();
		$tempArr = array();
		foreach($result as $v) {
		$tempOidArr[$v->selleruserid][] = $v->ebay_orderid;
		$tempArr[$v->selleruserid][] = $v->attributes;
		}
		if (!empty($tempArr)){
			self::_syncOrder($tempArr,$tempOidArr,$status);
		}else{
			echo "\n $status is finish ! ";
		}
		return count($result);
	}//end of function _autoRequestEbayOrder
	
	static private function _syncOrder($tempArr ,$tempOidArr, $default_status = 0){
		foreach($tempArr as $k => $v) {
			echo "\n[".date('H:i:s').'-'.__LINE__.'- selleruserid:'.$k."]\n";
			$eu = SaasEbayUserHelper::getOne($k);
			if (empty($eu)){
				echo "\n user $k has been deleted \n";
				QueueGetorder::deleteAll(['selleruserid'=>$k]);//清除队列中拉取的order
				continue;
			}
			 
			$getOrders = new getorders();
			$getOrders->resetConfig($eu->DevAcccountID);
			$getOrders->eBayAuthToken=$eu->token;
			$getOrders->EntriesPerPage=1;
			$getOrders->PageNumber=1;
		
			$count = count($tempOidArr[$k]);
			for($i = 0, $c = $count; $i < $c; $i += $getOrders->EntriesPerPage) {
				$do_ebayorderids = array_slice($tempOidArr[$k], $i, $getOrders->EntriesPerPage);
				$flag = false;
				try {
					$effect = QueueGetorder::updateAll(['status'=>1,'updated'=>time()],['in','ebay_orderid',$do_ebayorderids]);
					echo "\n sync order effect = $effect and ebay_orderid=".json_encode($do_ebayorderids);
					$getOrders->_before_request_xmlarray['DetailLevel'] = 'ReturnAll';
					$getOrders->_before_request_xmlarray['OrderID'] = $do_ebayorderids;
					$flag = $getOrders->requestOrders($eu, $do_ebayorderids);
					$status = $flag ? 2 : $default_status;
					$effect = QueueGetorder::updateAll(['status'=>$status],['ebay_orderid'=>$do_ebayorderids]);
					echo "\n flag =$flag  status =$status effect = $effect and ebay_orderid=".json_encode($do_ebayorderids);
				} catch(\Exception $ex) {
					echo "\n".(__function__).' Error Message:'.$ex->getMessage()." Line no ".$ex->getLine();
				}
			}
		}
	}
	
	/**
	 +----------------------------------------------------------
	 * 对ebay 同步 订单的健康检查
	 +----------------------------------------------------------
	 * @access static
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh    2016-12-15				初始化
	 +----------------------------------------------------------
	 **/
	static public function syncEbayOrderHealthCheck($sendEmail='N'){
		/*
		 * 1小时内 没有拉ebay heading list 的账号数量
		 * 订单同步队列半小时前的数据堆积数量
		 */
		$sqlList = [
			'listing'=>'select count(1) as cc from saas_ebay_autosyncstatus where type = 1  and status = 1  and lastprocessedtime <= UNIX_TIMESTAMP()-3600 ',
			'detail'=>'SELECT count(1) as cc   FROM `queue_getorder` WHERE  status < 2  and retry_count < 10  and created <= UNIX_TIMESTAMP()-1800 ',
		];
		
		$report = '';
		
		foreach($sqlList as $type=>$sql ){
			$row=\Yii::$app->db->createCommand($sql)->queryAll();
			if ($row[0]['cc']>0){
				if ($type == 'listing'){
					$report .= "ebay Order Listing 拉取延迟数量： ".$row[0]['cc'].' .  <br>'.$sql.' <br> '.
					"select *,FROM_UNIXTIME(lastprocessedtime ,'%Y-%m-%d %H:%i:%s') as lst from saas_ebay_autosyncstatus where type = 1  and status = 1  and lastprocessedtime <= UNIX_TIMESTAMP()-3600 order by lst asc";
				}
				
				if ($type == 'detail'){
					$report .= "ebay Order detail 拉取延迟数量： ".$row[0]['cc'].' .  <br>'.$sql.' <br>';
				}
			}
		}
		
		if ( ! empty($report)){
			echo $report."\n";
			if ($sendEmail == 'Y'){
				// TODO ebay order health check emails
				$anEmailAddr = 'xxx@xxx2.com';
				$rtn1 = MailHelper::sendMailBySQ("xxx@xxx.com", "后台job监测", $anEmailAddr, "监测有ebay同步订单数据异常 ","$report <br> check time:  ".date("Y-m-d H:i:s"));
				echo "Sent email to $anEmailAddr , sent return ".print_r($rtn1,true)." \n";
			}
			
		}
		return true;
		
	}//end of function syncEbayOrderHealthCheck
	
	/**
	 +----------------------------------------------------------
	 * 对ebay 同步 订单的同步 优先级进行调整
	 +----------------------------------------------------------
	 * @access static
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh    2017-05-27				初始化
	 +----------------------------------------------------------
	 **/
	static public function setEbayOrderPriority(){
		$sql = "update queue_getorder set status = 9 where selleruserid in (select selleruserid from saas_ebay_user where order_priority =5) and status =0";
		$effect =\Yii::$app->db->createCommand($sql)->execute();
		echo "\n order priority 5 set status = 9 effect=".$effect;
	}

}
