<?php
namespace console\helpers;

use yii\console\Controller;
use eagle\models\OrderHistoryStatisticsData;
use console\helpers\OrderUserStatisticHelper;

class OrderUserStatisticHelper {
	
	/**
	 +----------------------------------------------------------
	 * 订单处理统计平台生成订单数组$platformResult赋值
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		hqw 	2015/06/17				初始化
	 +----------------------------------------------------------
	 */
	public static function setPlatformResult(& $platformResult, $nowYear, $nowMonth) {
		$subdbConn=\yii::$app->subdb;
		
		//因为是统计各个平台的订单生成时间，所以每次都要从2015-01-01开始统计重新统计一次
		$maxDate = '2015-01-01';
		$maxMonth = (int)date('m',strtotime($maxDate));
		$maxYear = (int)date('Y',strtotime($maxDate));
		
		do{
			for ($month = $maxMonth; $month <= ($maxYear==$nowYear ? $nowMonth : 12); $month++){
				
				$tmpDay = strtotime(date($maxYear."-".$month."-1"));
				$mdays=date('t',$tmpDay);
				$endDate=strtotime(date('Y-m-'.$mdays.' 23:59:59',$tmpDay));
				
				//相同的查询条件，所以只用一个AndSql来记录
				$sourceAndSql = '';
				
				//统计订单数据现在分开两张表来做统计，因为订单数据做了分割
				$sourcesql='select currency,sum(grand_total) as totalAmount ,count(1) as totalCount from od_order_v2 where 1 ';
					
				//统计历史订单数据
				$sourceOldsql='select currency,sum(grand_total) as totalAmount ,count(1) as totalCount from od_order_old_v2 where 1 ';
				
				$sourceAndSql.=" AND currency != '' ";
				$sourceAndSql.=' AND order_source_create_time >= '.strtotime($maxYear."-".$month."-1").'  ';
				$sourceAndSql.=' AND order_source_create_time < '.$endDate." and order_relation in ('normal','sm') and order_capture='N' group by currency ";
				
				$rows=$subdbConn->createCommand($sourcesql.$sourceAndSql)->queryAll();
				$rowOlds=$subdbConn->createCommand($sourceOldsql.$sourceAndSql)->queryAll();
					
				//合并两个数组
				$rows=array_merge($rows,$rowOlds);
				
				foreach ($rows as $row){
					$currency = $row['currency'];
					$totalAmount = $row['totalAmount'];
					$totalCount = $row['totalCount'];
				
					if (!isset($platformResult[$maxYear."-".$month][$currency])) $platformResult[$maxYear."-".$month][$currency] = 0;
					if (!isset($platformResult[$maxYear."-".$month]['TotalCount'])) $platformResult[$maxYear."-".$month]['TotalCount'] = 0;
				
					$platformResult[$maxYear."-".$month][$currency] += $totalAmount;
					$platformResult[$maxYear."-".$month]['TotalCount'] += $totalCount;
				}
		
			}//each month
		
			//因为假如年份相同代表已经执行过，可以直接退出即可
			if ($maxYear==$nowYear)
				$maxYear = $maxYear+2;
			else
				$maxYear++;
		
			$maxMonth = 1;
		}while($maxYear <= $nowYear);
	}
	
	/**
	 +----------------------------------------------------------
	 * 各个平台订单在eagle上的生成时间来统计 $fetchResult
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		hqw 	2015/06/17				初始化
	 +----------------------------------------------------------
	 */
	public static function setfetchResult(& $fetchResult, $nowYear, $nowMonth) {
		$subdbConn=\yii::$app->subdb;
		
		$sourcesql = "select date_add(max(STR_TO_DATE(CONCAT(a.history_date,\"-1\"),'%Y-%m-%d')),interval 0 month) as maxDate
    			from order_history_statistics_data a where a.type='fetch'";
			
		$maxDate = \yii::$app->db->createCommand($sourcesql)->queryScalar();
		if($maxDate == null)
			$maxDate = '2015-01-01';
		
		$maxMonth = (int)date('m',strtotime($maxDate));
		$maxYear = (int)date('Y',strtotime($maxDate));
		
		//删除 和本月相同的日期统计 因为本月可能有新拉取订单数据
		$result = \yii::$app->db->createCommand("delete from order_history_statistics_data where type='fetch' and history_date='".$maxYear."-".$maxMonth."' ")->execute();
		
		do{
			for ($month = $maxMonth; $month <= ($maxYear==$nowYear ? $nowMonth : 12); $month++){
				$tmpDay = strtotime(date($maxYear."-".$month."-1"));
				$mdays=date('t',$tmpDay);
				$endDate=strtotime(date('Y-m-'.$mdays.' 23:59:59',$tmpDay));
				
				
				$tmpOrderHistoryStatisticsData = OrderHistoryStatisticsData::findOne(['type'=>'fetch','history_date'=>$maxYear."-".$month]);
					
				if($tmpOrderHistoryStatisticsData === null){
					//相同的查询条件，所以只用一个AndSql来记录
					$sourceAndSql = '';
					
					//统计订单数据现在分开两张表来做统计，因为订单数据做了分割
					$sourcesql='select currency,sum(grand_total) as totalAmount ,count(1) as totalCount from od_order_v2 where 1 ';
				
					//统计历史订单数据
					$sourceOldsql='select currency,sum(grand_total) as totalAmount ,count(1) as totalCount from od_order_old_v2 where 1 ';
					
					$sourceAndSql.=" AND currency != '' ";
					$sourceAndSql.=' AND create_time >= '.strtotime($maxYear."-".$month."-1").'  ';
					$sourceAndSql.=' AND create_time < '.$endDate." and order_relation in ('normal','sm') and order_capture='N' group by currency ";
				
					$rows=$subdbConn->createCommand($sourcesql.$sourceAndSql)->queryAll();
					$rowOlds=$subdbConn->createCommand($sourceOldsql.$sourceAndSql)->queryAll();
				
					$rows=array_merge($rows,$rowOlds);
					
					foreach ($rows as $row){
						$currency = $row['currency'];
						$totalAmount = $row['totalAmount'];
						$totalCount = $row['totalCount'];
				
						if (!isset($fetchResult[$maxYear."-".$month][$currency])) $fetchResult[$maxYear."-".$month][$currency] = 0;
						if (!isset($fetchResult[$maxYear."-".$month]['TotalCount'])) $fetchResult[$maxYear."-".$month]['TotalCount'] = 0;
				
						$fetchResult[$maxYear."-".$month][$currency] += $totalAmount;
						$fetchResult[$maxYear."-".$month]['TotalCount'] += $totalCount;
					}
				}
		
			}//each month
				
			//因为假如年份相同代表已经执行过，可以直接退出即可
			if ($maxYear==$nowYear)
				$maxYear = $maxYear+2;
			else
				$maxYear++;
				
			$maxMonth = 1;
		}while($maxYear <= $nowYear);
	}
	
	/**
	 +----------------------------------------------------------
	 * 在使用eagle系统2015年前的订单统计，不含2015年 $useagoResult
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		hqw 	2015/06/17				初始化
	 +----------------------------------------------------------
	 */
	public static function setUseagoResult(& $useagoResult) {
		$subdbConn=\yii::$app->subdb;
		
		$tmpOrderHistoryStatisticsData = OrderHistoryStatisticsData::findOne(['type'=>'useago']);
		
		if ($tmpOrderHistoryStatisticsData === null){
			$sourceAndSql = '';
			
			$sourcesql='select currency,sum(grand_total) as totalAmount ,count(1) as totalCount from od_order_v2 where 1 ';
			
			$sourceOldsql='select currency,sum(grand_total) as totalAmount ,count(1) as totalCount from od_order_old_v2 where 1 ';
			
			$sourceAndSql.=" AND currency != '' ";
			$sourceAndSql.=' AND create_time < '.strtotime("2015-1-1")." and order_relation in ('normal','sm') and order_capture='N' group by currency ";
			 
			$rows=$subdbConn->createCommand($sourcesql.$sourceAndSql)->queryAll();
			$rowOlds=$subdbConn->createCommand($sourceOldsql.$sourceAndSql)->queryAll();
		
			$rows=array_merge($rows,$rowOlds);
			
			foreach ($rows as $row){
				$currency = $row['currency'];
				$totalAmount = $row['totalAmount'];
				$totalCount = $row['totalCount'];
		
				if (!isset($useagoResult[$currency])) $useagoResult[$currency] = 0;
				if (!isset($useagoResult['TotalCount'])) $useagoResult['TotalCount'] = 0;
		
				$useagoResult[$currency] += $totalAmount;
				$useagoResult['TotalCount'] += $totalCount;
			}
		}
	}
	
	/**
	 +----------------------------------------------------------
	 * 统计新增账号数 以及 新增账号绑定率 $registerResult
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		hqw 	2015/06/17				初始化
	 +----------------------------------------------------------
	 *
	 * @note 这个方法把生成的之前查询过的数据记录到某张数据表中,可能存在的情况是:
	 * 		  假如有个客户6月30号注册暂时没有绑定账号，然后我们7月1号的时候先生成了报表后，客户才绑定账号那么，这个分析就不怎么准确了。
	 *
	 */
	public static function setRegisterResult(& $registerResult, $nowYear, $nowMonth){
		$subdbConn=\yii::$app->db;
			
		$sourcesql = "select date_add(max(STR_TO_DATE(CONCAT(a.history_date,\"-1\"),'%Y-%m-%d')),interval 0 month) as maxDate
    			from order_history_statistics_data a where a.type='register'";
			
		$maxDate = $subdbConn->createCommand($sourcesql)->queryScalar();
		if($maxDate == null)
			$maxDate = '2015-01-01';
		
		$maxMonth = (int)date('m',strtotime($maxDate));
		$maxYear = (int)date('Y',strtotime($maxDate));
		
		//删除 和本月相同的日期统计 因为本月可能有查询后客户都有新增账号绑定
		$result = \yii::$app->db->createCommand("delete from order_history_statistics_data where type='register' and history_date='".$maxYear."-".$maxMonth."' ")->execute();
		
		do{
			for ($month = $maxMonth; $month <= ($maxYear==$nowYear ? $nowMonth : 12); $month++){
				//统计新增账号数 以及 新增账号绑定率
				
				$tmpDay = strtotime(date($maxYear."-".$month."-1"));
				$mdays=date('t',$tmpDay);
				$endDate=strtotime(date('Y-m-'.$mdays.' 23:59:59',$tmpDay));
				
			
				$sourcesql='select count(1) as totalCount from user_base where puid=0 and ';
			
				$sourcesql.=' register_date >= '.strtotime($maxYear."-".$month."-1").' AND ';
				$sourcesql.=' register_date < '.$endDate.'   ';
				
				$count= $subdbConn->createCommand($sourcesql)->queryScalar();
			
				$registerResult[$maxYear."-".$month]['registered'] =$count;
			
				//初始化
				$bindedUsers = array();
				
				//这个月的新绑定用户比例 SMT
				$sourcesql='SELECT a.uid FROM `user_base` a ,saas_aliexpress_user b  WHERE a.uid = b.uid  and  ';
				$sourcesql.=' register_date >= '.strtotime($maxYear."-".$month."-1").' AND ';
				$sourcesql.=' register_date < '.$endDate.'   ';
			
				$rows = $subdbConn->createCommand($sourcesql)->queryAll();
				foreach ($rows as $row)
					$bindedUsers['a'.$row['uid']] = 1;
			
				//Amazon
				$sourcesql='SELECT a.uid FROM `user_base` a ,saas_amazon_user b  WHERE a.uid = b.uid  and  ';
				$sourcesql.=' register_date >= '.strtotime($maxYear."-".$month."-1").' AND ';
				$sourcesql.=' register_date < '.$endDate.'   ';
			
				$rows = $subdbConn->createCommand($sourcesql)->queryAll();
				foreach ($rows as $row)
					$bindedUsers['a'.$row['uid']] = 1;
			
				//Ebay  ebay绑定时比较特殊记录的是uid,其它平台记录的是puid,所以ebay需要做转换
				$sourcesql='SELECT case when a.puid = 0 then a.uid else a.puid end as uid FROM `user_base` a ,saas_ebay_user b  WHERE a.uid = b.uid  and  ';
				$sourcesql.=' register_date >= '.strtotime($maxYear."-".$month."-1").' AND ';
				$sourcesql.=' register_date < '.$endDate.'   ';
			
				$rows = $subdbConn->createCommand($sourcesql)->queryAll();
				foreach ($rows as $row)
					$bindedUsers['a'.$row['uid']] = 1;
			
				//Wish
				$sourcesql='SELECT a.uid FROM `user_base` a ,saas_wish_user b  WHERE a.uid = b.uid  and  ';
				$sourcesql.=' register_date >= '.strtotime($maxYear."-".$month."-1").' AND ';
				$sourcesql.=' register_date < '.$endDate.'   ';
				
				$rows = $subdbConn->createCommand($sourcesql)->queryAll();
				foreach ($rows as $row)
					$bindedUsers['a'.$row['uid']] = 1;
				
				//dhgate
				$sourcesql='SELECT a.uid FROM `user_base` a ,saas_dhgate_user b  WHERE a.uid = b.uid  and  ';
				$sourcesql.=' register_date >= '.strtotime($maxYear."-".$month."-1").' AND ';
				$sourcesql.=' register_date < '.$endDate.'   ';
					
				$rows = $subdbConn->createCommand($sourcesql)->queryAll();
				foreach ($rows as $row)
					$bindedUsers['a'.$row['uid']] = 1;
				
				//lazada,linio,jumia
				$sourcesql='SELECT a.uid FROM `user_base` a ,saas_lazada_user b  WHERE a.uid = b.puid  and  ';
				$sourcesql.=' register_date >= '.strtotime($maxYear."-".$month."-1").' AND ';
				$sourcesql.=' register_date < '.$endDate.'   ';
					
				$rows = $subdbConn->createCommand($sourcesql)->queryAll();
				foreach ($rows as $row)
					$bindedUsers['a'.$row['uid']] = 1;
				
				//Cdiscount
				$sourcesql='SELECT a.uid FROM `user_base` a ,saas_cdiscount_user b  WHERE a.uid = b.uid  and  ';
				$sourcesql.=' register_date >= '.strtotime($maxYear."-".$month."-1").' AND ';
				$sourcesql.=' register_date < '.$endDate.'   ';
				
				$rows = $subdbConn->createCommand($sourcesql)->queryAll();
				foreach ($rows as $row)
					$bindedUsers['a'.$row['uid']] = 1;
				
				if ($count > 0)
					$registerResult[$maxYear."-".$month]['bindedRate'] =count($bindedUsers) * 100 / $count;
				else
					$registerResult[$maxYear."-".$month]['bindedRate'] = 0;
				
			}//each month
			
			//因为假如年份相同代表已经执行过，可以直接退出即可
			if ($maxYear==$nowYear) 
				$maxYear = $maxYear+2;
			else 
				$maxYear++;
			
			$maxMonth = 1;
		}while($maxYear <= $nowYear);
		
		
	}
	
	/**
	 +----------------------------------------------------------
	 * 将$platformResult、$fetchResult数组转换为保存时的$tmpSaveArr结构
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		hqw 	2015/06/17				初始化
	 +----------------------------------------------------------
	 */
	public static function ResultArrToJson($paramArr, $type){
		$tempArr = array();
	
		foreach ($paramArr as $aMonth=>$vals){
			$tempTotalCount = $vals['TotalCount'];
			unset($vals['TotalCount']);
	
			$tempArr[] = array('type'=>$type, 'history_date'=>$aMonth,
					'json_params'=>"{\"TotalCount\":[\"".$tempTotalCount."\"],\"currency\":[".json_encode($vals)."]}");
		}
			
		return $tempArr;
	}
}