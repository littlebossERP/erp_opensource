<?php

namespace console\controllers;

use yii\console\Controller;
use eagle\models\UserBase;
use eagle\models\BackgroundMonitor;
use eagle\models\OrderHistoryStatisticsData;
use console\helpers\OrderUserStatisticHelper;
use eagle\modules\catalog\models\Product;
use eagle\modules\order\models\OdOrder;
use eagle\modules\order\models\OdOrderItem;
use eagle\modules\util\helpers\ConfigHelper;
use eagle\modules\util\helpers\StandardConst;
use eagle\modules\catalog\helpers\ProductApiHelper;
use eagle\modules\dash_board\apihelpers\DashBoardStatisticHelper;
use eagle\modules\order\helpers\OrderGetDataHelper;
use eagle\modules\catalog\models\ProductBundleRelationship;

/**
 * SqlExecution controller
 */

error_reporting(0);

class OrderUserStatisticController extends Controller
{

	/**
	 +----------------------------------------------------------
	 * 批量运行SQL脚本到每个UserDb
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		hqw 	2015/05/25				初始化
	 +----------------------------------------------------------
	 * @Param
	 * 
	 * @Notice 文件中假如存在 SET FOREIGN_KEY_CHECKS=0; 语句时当SQL文件有问题时捕捉不了错误。
	 * 
	 **/
    public function actionTotalOrderAmount()
    {
    	//获取数据
    	$mainUsers = UserBase::find()->where(['puid'=>0])->asArray()->all();
    	$tmpRecords = 0;
    	$result = array();
    	$registerResult = array();
    	$datesArr=array( 1,2,3,4,5,6);
    	foreach ($mainUsers as $puser){
    	//	break;//ystest
    		$puid = $puser['uid'];
    		 
    		$subdbConn=\yii::$app->subdb;
    		echo "\n".$puid." Running ...";
    			
  			//$subdbConn->createCommand($sql)->execute();

  				
		 	
			foreach ($datesArr as $month){
				$sourcesql='select currency,sum(grand_total) as totalAmount ,count(*) as totalCount from od_order_v2 where ';
					
  				$sourcesql.=' order_source_create_time >= '.strtotime("2015-".$month."-1").' AND ';
  				$sourcesql.=' order_source_create_time < '.strtotime("2015-".($month+1)."-1")." and order_relation in ('normal','sm') and order_capture='N' group by currency ";  				
  			 
  			
  			$rows=$subdbConn->createCommand($sourcesql)->queryAll();
  			
			
			
			foreach ($rows as $row){
				$currency = $row['currency'];
				$totalAmount = $row['totalAmount'];
				$totalCount = $row['totalCount'];
				
				if (!isset($result["2015-".$month][$currency])) $result["2015-".$month][$currency] = 0;
				if (!isset($result["2015-".$month]['TotalCount'])) $result["2015-".$month]['TotalCount'] = 0;
				
				$result["2015-".$month][$currency] += $totalAmount;
				$result["2015-".$month]['TotalCount'] += $totalCount;
			}//end of each result/currency
			
			
			
			
		}//end of each month
  			
  			//if ($puser['uid']>4) break;
  	}//end of each user
  	$subdbConn=\yii::$app->db;
  	foreach ($datesArr as $month){
  		//统计新增账号数 以及 新增账号绑定率
  			
  		$sourcesql='select  count(*) as totalCount from user_base where ';
  		
  		$sourcesql.=' register_date >= '.strtotime("2015-".$month."-1").' AND ';
  		$sourcesql.=' register_date < '.strtotime("2015-".($month+1)."-1").'   ';

  		$count= $subdbConn->createCommand($sourcesql)->queryScalar();
  		 
  		$registerResult["2015-".$month]['registered'] =$count;
  		
  		//这个月的新绑定用户比例 SMT
  		$bindedCount = 0;
  		$bindedUsers = array();
  		$sourcesql='SELECT a.uid FROM `user_base` a ,saas_aliexpress_user b  WHERE a.uid = b.uid  and  ';
  		$sourcesql.=' register_date >= '.strtotime("2015-".$month."-1").' AND ';
  		$sourcesql.=' register_date < '.strtotime("2015-".($month+1)."-1").'   ';
  		
  		$rows = $subdbConn->createCommand($sourcesql)->queryAll();
  		foreach ($rows as $row)
  			$bindedUsers['a'.$row['uid']] = 1;
  		
  		//Amazon
  		$sourcesql='SELECT a.uid FROM `user_base` a ,saas_amazon_user b  WHERE a.uid = b.uid  and  ';
  		$sourcesql.=' register_date >= '.strtotime("2015-".$month."-1").' AND ';
  		$sourcesql.=' register_date < '.strtotime("2015-".($month+1)."-1").'   ';
  		
  		$rows = $subdbConn->createCommand($sourcesql)->queryAll();
  		foreach ($rows as $row)
  			$bindedUsers['a'.$row['uid']] = 1;
  		
  		
  		//Ebay
  		$sourcesql='SELECT a.uid FROM `user_base` a ,saas_ebay_user b  WHERE a.uid = b.uid  and  ';
  		$sourcesql.=' register_date >= '.strtotime("2015-".$month."-1").' AND ';
  		$sourcesql.=' register_date < '.strtotime("2015-".($month+1)."-1").'   ';
  		
  		$rows = $subdbConn->createCommand($sourcesql)->queryAll();
  		foreach ($rows as $row)
  			$bindedUsers['a'.$row['uid']] = 1;
  		
  		
  		//Wish
  		$sourcesql='SELECT a.uid FROM `user_base` a ,saas_wish_user b  WHERE a.uid = b.uid  and  ';
  		$sourcesql.=' register_date >= '.strtotime("2015-".$month."-1").' AND ';
  		$sourcesql.=' register_date < '.strtotime("2015-".($month+1)."-1").'   ';
  		
  		$rows = $subdbConn->createCommand($sourcesql)->queryAll();
  		foreach ($rows as $row)
  			$bindedUsers['a'.$row['uid']] = 1;
  		
  		if ($count > 0)
  			$registerResult["2015-".$month]['bindedRate'] =count($bindedUsers) * 100 / $count;
  		else
  			$registerResult["2015-".$month]['bindedRate'] =0;
  	}//each month
  	
  		//echo print_r($result,true) ;
  		//output the result:
  		echo "\n".$puid." Total Order Result ...Please copy it to csv , and open with excel \n";
  		foreach ($result as $aMonth=>$vals){
  			echo "$aMonth , TotalCount , ".$vals['TotalCount']."\n";
  			$currencyAndVal = '';
  			foreach ($vals as $currency=>$amount){
  				 
  				if ( $currency <> 'TotalCount')
  					echo "$aMonth , $currency , $amount \n";
  			}
  		}
  		
  		echo "\n".$puid." Total Registered and binded rate ...Please copy it to csv , and open with excel \n";
  		foreach ($registerResult as $aMonth=>$vals){
  			echo "$aMonth  ";
  			foreach ($vals as $type=>$number){
  				echo ", $type , $number ";
  			}
  			echo " \n";
  		}
  		
  }//end of function
  
  
  
    
  public function actionTotalOrderAmount2()
  {
  	//获取数据
  	$mainUsers = UserBase::find()->where(['puid'=>0])->asArray()->all();
  	$tmpRecords = 0;
  	$result = array();
  	$registerResult = array();
  	$datesArr=array( 1,2,3,4,5,6);
  	foreach ($mainUsers as $puser){
  		//	break;//ystest
  		$puid = $puser['uid'];
  		 
  
  		$subdbConn=\yii::$app->subdb;
  		echo "\n".$puid." Running ...";
  		 
  		//$subdbConn->createCommand($sql)->execute();
  
  
  
  		foreach ($datesArr as $month){
  			$sourcesql='select currency,sum(grand_total) as totalAmount ,count(*) as totalCount from od_order_v2 where ';
  				
  			$sourcesql.=' create_time >= '.strtotime("2015-".$month."-1").' AND ';
  			$sourcesql.=' create_time < '.strtotime("2015-".($month+1)."-1")." and order_relation in ('normal','sm') and order_capture='N' group by currency ";
  
  				
  			$rows=$subdbConn->createCommand($sourcesql)->queryAll();
  				
  				
  				
  			foreach ($rows as $row){
  				$currency = $row['currency'];
  				$totalAmount = $row['totalAmount'];
  				$totalCount = $row['totalCount'];
  
  				if (!isset($result["2015-".$month][$currency])) $result["2015-".$month][$currency] = 0;
  				if (!isset($result["2015-".$month]['TotalCount'])) $result["2015-".$month]['TotalCount'] = 0;
  
  				$result["2015-".$month][$currency] += $totalAmount;
  				$result["2015-".$month]['TotalCount'] += $totalCount;
  			}//end of each result/currency
  				
  				
  				
  				
  		}//end of each month
  			
  		//if ($puser['uid']>4) break;
  	}//end of each user
  	$subdbConn=\yii::$app->db;
  
  	//echo print_r($result,true) ;
  	//output the result:
  	echo "\n".$puid." Total Order Result ...Please copy it to csv , and open with excel \n";
  	foreach ($result as $aMonth=>$vals){
  		echo "$aMonth , TotalCount , ".$vals['TotalCount']."\n";
  		$currencyAndVal = '';
  		foreach ($vals as $currency=>$amount){
  			
  		if ( $currency <> 'TotalCount')
  			echo "$aMonth , $currency , $amount \n";
  		}
  		}
  
  
	}//end of function
  
    
	public function actionTotalOrderAmount3()
	{
		//获取数据
		$mainUsers = UserBase::find()->where(['puid'=>0])->asArray()->all();
		$tmpRecords = 0;
		$result = array();
		$registerResult = array();
		$datesArr=array( 1);
		foreach ($mainUsers as $puser){
			//	break;//ystest
			$puid = $puser['uid'];
			 
			$subdbConn=\yii::$app->subdb;
			echo "\n".$puid." Running ...";
				
			//$subdbConn->createCommand($sql)->execute();
	
	
	
				$sourcesql='select currency,sum(grand_total) as totalAmount ,count(*) as totalCount from od_order_v2 where ';
	
				//$sourcesql.=' create_time >= '.strtotime("2015-".$month."-1").' AND ';
				$sourcesql.=' create_time < '.strtotime("2015-1-1")." and order_relation in ('normal','sm') and order_capture='N' group by currency ";
	
	
				$rows=$subdbConn->createCommand($sourcesql)->queryAll();
	
	
	
				foreach ($rows as $row){
					
					$currency = $row['currency'];
					$totalAmount = $row['totalAmount'];
					$totalCount = $row['totalCount'];
					
					
	
					if (!isset($result[$currency])) $result[$currency] = 0;
					if (!isset($result['TotalCount'])) $result['TotalCount'] = 0;
	
					$result[$currency] += $totalAmount;
					$result['TotalCount'] += $totalCount;
					
					echo "puid:$puid currency:$currency totalAmount:$totalAmount totalCount:$totalCount \n";
					print_r($result);
						
					
				}//end of each result/currency
	
	
	
				
			//if ($puser['uid']>4) break;
		}//end of each user
		
	
		print_r($result) ;
		//output the result:
	
	}//end of function
	
	
	
	/**
	 +----------------------------------------------------------
	 * 订单处理统计
	 * 每隔30分钟执行一次前台是否有需求更新订单处理统计
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		hqw 	2015/06/17				初始化
	 +----------------------------------------------------------
	 * 1)订单处理统计平台生成订单数组
	 * 2)各个平台订单在eagle上的生成时间来统计
	 * 3)在使用eagle系统2015年前的订单统计，不含2015年
	 * 4)统计新增账号数 以及 新增账号绑定率
	 * 
	 * 调用方法:  .yii order-user-statistic/order-process-statistics
	 */
	public function actionOrderProcessStatistics()
	{
		$backgroundMonitor = BackgroundMonitor::findOne(['job_name'=>'OrderProcessStatistics']);
		 
		if ($backgroundMonitor === null){
			$backgroundMonitor = new BackgroundMonitor ();
			$backgroundMonitor->job_name = "OrderProcessStatistics";
			
			$backgroundMonitor->json_params = "{\"success\":[\"\"],\"failure\":[\"\"],\"notExists\":[\"\"]}";
		}else{
    		if ($backgroundMonitor->status=="Start"){
    			echo "backgroundMonitor Running...";
    			exit;
    		}
    		
    		if ($backgroundMonitor->status=="End"){
    			echo "Reception is no demand update...";
    			exit;
    		}
		}
		 
		$backgroundMonitor->last_end_time = null;
		$backgroundMonitor->last_total_time = 0;
		$backgroundMonitor->status = "Start";
		$backgroundMonitor->create_time = date('Y-m-d H:i:s');
		$backgroundMonitor->save(false);
		$sqlUserdbStarttime = microtime(true); // 所有数据库Sql运行开始时间点。 单位：毫秒
		 
		if(($backgroundMonitor->last_end_time > $backgroundMonitor->create_time)){
			echo "已经是最新";
			exit;
		}
		 
		//现时月份，年份
		$nowMonth = (int)date('m');
		$nowYear = date('Y');
		 
		//获取数据
		$mainUsers = UserBase::find()->select('uid')->where(['puid'=>0])->asArray()->all();
		 
		$platformResult = array();//平台订单生成时间统计
		$fetchResult = array();//系统拉取订单生成时间统计
		$useagoResult = array();//使用前
		$registerResult = array();//统计新增账号数 以及 新增账号绑定率
		 
		foreach ($mainUsers as $puser){
			//	break;//ystest
			$puid = $puser['uid'];
			$db_name = \Yii::$app->params['subdb']['dbPrefix'].$puser['uid'];
			 
			
			echo "\n".$db_name." Running ...";
	
			$subdbConn=\yii::$app->subdb;
	
			//1)订单处理统计平台生成订单数组$platformResult赋值
			OrderUserStatisticHelper::setPlatformResult($platformResult, $nowYear, $nowMonth);
			
			//2)各个平台订单在eagle上的生成时间来统计 向数组$fetchResult赋值
			OrderUserStatisticHelper::setfetchResult($fetchResult, $nowYear, $nowMonth);
	
			//3)在使用eagle系统2015年前的订单统计，不含2015年 向数组$useagoResult赋值
			OrderUserStatisticHelper::setUseagoResult($useagoResult);
	
		}//end of each user
		 
		
		//4)统计新增账号数 以及 新增账号绑定率  向数组$registerResult赋值
		OrderUserStatisticHelper::setRegisterResult($registerResult, $nowYear, $nowMonth);
		
		//批量保存
		$tmpSaveArr = array();
		 
		//将数组$platformResult、$fetchResult、$useagoResult、$registerResult转换成相同结构，用于批量保存
		$tmpSaveArr = array_merge($tmpSaveArr, OrderUserStatisticHelper::ResultArrToJson($platformResult, "platform"));
		$tmpSaveArr = array_merge($tmpSaveArr, OrderUserStatisticHelper::ResultArrToJson($fetchResult, "fetch"));
		 
		if (count($useagoResult) > 0){
			$tempTotalCount = $useagoResult['TotalCount'];
			unset($useagoResult['TotalCount']);
	
			$tempArr[] = array('type'=>'useago', 'history_date'=>'2015',
					'json_params'=>"{\"TotalCount\":[\"".$tempTotalCount."\"],\"currency\":[".json_encode($useagoResult)."]}");
	
			$tmpSaveArr = array_merge($tmpSaveArr, $tempArr);
		}
		
		if(count($registerResult) > 0){
			foreach ($registerResult as $aMonth=>$vals){
				$tmpSaveArr[] = array('type'=>'register', 'history_date'=>$aMonth,
						'json_params'=>"{\"registered\":[\"".$vals['registered']."\"],\"bindedRate\":[\"".$vals['bindedRate']."\"]}");
			}
		}

		//平台订单生成时间统计的历史查询数据重新生成时需要删除上一次的查询结果
		if (count($platformResult) > 0){
			$result = \yii::$app->db->createCommand("delete from order_history_statistics_data where type='platform' ")->execute();
		}
		
		if (count($tmpSaveArr) > 0){
			$model = new OrderHistoryStatisticsData();
			foreach($tmpSaveArr as $attributes)
			{
				$_model = clone $model;
				$_model->setAttributes($attributes);
				$_model->save();
			}
		}
		//批量保存 end
		
		$backgroundMonitor->status = "End";
		$backgroundMonitor->last_end_time = date('Y-m-d H:i:s');
		$sqlUserdbEndtime = microtime(true); // 所有数据库Sql运行开始时间点。 单位：毫秒
		$backgroundMonitor->last_total_time = round($sqlUserdbEndtime-$sqlUserdbStarttime,3);
		
		$backgroundMonitor->save(false);
	}
	
	/**
	 +----------------------------------------------------------
	 * 利润计算，计算未计算的已完成订单
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		lrq 	2016/09/26				初始化
	 +----------------------------------------------------------
	 * @Param
	 *
	 * .yii order-user-statistic/profit-order
	 *
	 **/
	public function actionProfitOrder()
	{
		//更新最新汇率到redis
		echo "\n running refresh exchangeRate at ".date('Y-m-d H:i:s').". \n";
		$ret = \eagle\modules\statistics\helpers\ProfitHelper::RefreshRateToRedis();
		if(!$ret['success']){
			\eagle\modules\statistics\helpers\ProfitHelper::RefreshRateToRedis();
			echo "\n refresh exchangeRate fail: ".$ret['msg'].". \n";
		}
		else{
			echo "\n running refresh exchangeRate success. \n";
		}
		
		//获取数据
		$mainUsers = UserBase::find()->where(['puid'=>0])->asArray()->all();
		$tmpRecords = 0;
		$result = array();
		$registerResult = array();
		$datesArr=array( 1,2,3,4,5,6);
		foreach ($mainUsers as $puser){
			try{
				//	break;//ystest
				$puid = $puser['uid'];
				 
		
				$subdbConn=\yii::$app->subdb;
				echo "\n p".$puid." Running ...";
				
				//获取已设置的汇率
				$exchange_config = ConfigHelper::getConfig("Profit/CurrencyExchange",'NO_CACHE');
				if(empty($exchange_config))
					$exchange_config = [];
				else{
					$exchange_config = json_decode($exchange_config,true);
					if(empty($exchange_config))
						$exchange_config = [];
				}
				//获取已设置的RMB汇损
				$exchange_loss_data = [];
				$exchange_loss_config = ConfigHelper::getConfig("Order/CurrencyExchangeLoss",'NO_CACHE');
				if(empty($exchange_loss_config))
					$exchange_loss_config = [];
				else{
					$exchange_loss_config = json_decode($exchange_loss_config,true);
					if(empty($exchange_loss_config))
						$exchange_loss_config = [];
				}
				
				//未计算订单
				$query = OdOrder::find()->select("`order_id`");
				//筛选有效订单
				$query = OrderGetDataHelper::formatQueryOrderConditionPurchase($query, true);
				$query->andWhere("(profit is null || profit=0) and order_status=500 and paid_time>0");
				$orderids = $query->limit(10000)->asArray()->all();
				
			    $rtn['message'] = '';
				$exist_info = [];
				$ProfitAdds = [];
				$err_count = 0;
				foreach ($orderids as $orderid)
				{
				    try {
	    			    $od = OdOrder::findOne($orderid['order_id']);
	    			    
	    				$product_cost = 0;//商品成本
	    				$product_cost_str = '';//商品成本计算式
	    				$commission = 0;   //佣金
	    				
	    				$logistics_cost = empty($od->logistics_cost)?0:floatval($od->logistics_cost);//物流成本
	    				
	    				$currency = strtoupper($od->currency);//币种
	    				
	    				if(!empty($exchange_config[$currency])){
	    					$EXCHANGE_RATE = $exchange_config[$currency];
	    				}
	    				else{
	    				    if($currency == 'PH'){
	    				    	$currency = 'PHP';
	    				    }
    				    	//获取最新汇率
    				    	$EXCHANGE_RATE = \eagle\modules\statistics\helpers\ProfitHelper::GetExchangeRate($currency);
    				    	if(empty($EXCHANGE_RATE)){
    				    		$EXCHANGE_RATE = '--';
    				    	}
	    				    
	    					//$EXCHANGE_RATE = !empty(StandardConst::$EXCHANGE_RATE_OF_RMB[$currency])?StandardConst::$EXCHANGE_RATE_OF_RMB[$currency]:'--';//币种对应的RMB汇率
	    				}
	    				if($EXCHANGE_RATE=='--'){
	    					$rtn['success'] = false;
	    					$rtn['message'] .= $od->order_source_order_id."利润计算失败:未设置币种".$currency."对应的人民币汇率;<br>";
	    					continue;
	    				}
	    				
	    				$EXCHANGE_LOSS = isset($exchange_loss_config[$currency])?floatval($exchange_loss_config[$currency]):0;
	    				 
	    				$od_items = OdOrderItem::find()->where(['order_id'=>$od->order_id])->andwhere("manual_status is null or manual_status!='disable'")->all();
	    				foreach ($od_items as $od_item)
	    				{
	    				    if(!empty($od_item->root_sku)){
	    				    	$sku = $od_item->root_sku;
	    				    }
	    				    else{
	    				    	//$sku = $od_item->sku;
	    				    	$sku = '';
	    				    }
	    					$quantity = floatval($od_item->quantity);
	    					
	    					//取商品的首选采购价
	    					$purchase_price = 0;
	    					$pd = Product::findOne($sku);
	    					//如果商品存在于商品模块，采用商品模块设定的采购价
	    					if(!empty($pd))
	    					{
	    						$purchase_price = floatval($pd['purchase_price']);
	    	
	    						//当是捆绑商品时，从子产品计算出采购价、其它成本
	    						if($pd['type'] == 'B'){
	    							//查询对应的捆绑商品信息
	    							$bundle = ProductBundleRelationship::find()->select(['assku', 'qty'])->where(['bdsku' => $sku])->asArray()->all();
	    							if(!empty($bundle)){
	    								$asskus = [];
	    								$assku_arr = [];
	    								foreach ($bundle as $val){
	    									$asskus[] = $val['assku'];
	    									$assku_arr[$val['assku']] = $val['qty'];
	    								}
	    								//查询子商品对应的采购价、其它成本
	    								$assku_pro_arr = [];
	    								$assku_pros = Product::find()->select(['sku', 'purchase_price', 'additional_cost'])->where(['sku' => $asskus])->asArray()->all();
	    								foreach ($assku_pros as $val){
	    									$assku_pro_arr[$val['sku']]['purchase_price'] = $val['purchase_price'];
	    									$assku_pro_arr[$val['sku']]['additional_cost'] = $val['additional_cost'];
	    								}
	    								//重新计算捆绑商品采购价、其它成本
	    								$purchase_price2 = 0;
	    								$additional_cost2 = 0;
	    								foreach ($assku_arr as $assku => $qty){
	    									if(!empty($qty)){
	    										if(!empty($assku_pro_arr[$assku])){
	    											if(!empty($assku_pro_arr[$assku]['purchase_price'])){
	    												$purchase_price2 += $assku_pro_arr[$assku]['purchase_price'] * $qty;
	    											}
	    											if(!empty($assku_pro_arr[$assku]['additional_cost'])){
	    												$additional_cost2 += $assku_pro_arr[$assku]['additional_cost'] * $qty;
	    											}
	    										}
	    									}
	    						
	    									if(!empty($purchase_price2))
	    										$purchase_price = $purchase_price2;
	    									if(!empty($additional_cost2))
	    										$pd['additional_cost'] = $additional_cost2;
	    						
	    								}
	    							}
	    						}
	    						
	    						$additional_cost = empty($pd['additional_cost'])?0:floatval($pd['additional_cost']);
	    						$product_cost += $additional_cost * $quantity;
	    						
	    						//计算产品佣金
	    						if(!empty($pd['addi_info'])){
	    							$addi_info = json_decode($pd['addi_info'], true);
	    							if(!empty($addi_info['commission_per'][$od->order_source])){
	    								$per = $addi_info['commission_per'][$od->order_source];
	    								if(!empty($per) && !empty($od_item->quantity) && !empty($od_item->price)){
	    									$commission += $od_item->quantity * $od_item->price * $per / 100;
	    								}
	    							}
	    						}
	    					}
	    					
	    					if(floatval($od_item->purchase_price)!==$purchase_price)
	    					{
	    						$od_item->purchase_price = $purchase_price;
	    						$od_item->purchase_price_form = null;
	    						$od_item->purchase_price_to = null;
	    					}
	    					
	    					$product_cost += $purchase_price * $quantity;
	    					$itme_cost_str = '<br>&nbsp;&nbsp;&nbsp;&nbsp;'.$od_item->sku.'：(采购价'.$purchase_price.(isset($additional_cost)?"+额外$additional_cost)":')').'*'.$quantity;
	    					$product_cost_str = empty($product_cost_str)?$itme_cost_str : $product_cost_str.$itme_cost_str;
	    				}
	    				
	    				//总价 RMB  产品总价 + 运费 - 折扣------cd 的折扣存在的是佣金内容，则不需扣减
	    				//当是手工订单时，用产品总价
	    				if($od->order_capture == 'Y' || $od->order_source == 'aliexpress')
	    					$grand_total = floatval($od->grand_total) * $EXCHANGE_RATE * (1-$EXCHANGE_LOSS/100);
						else if(in_array($od->order_source, ['cdiscount', 'lazada', 'linio']))
						    $grand_total = floatval($od->subtotal + $od->shipping_cost) * $EXCHANGE_RATE * (1-$EXCHANGE_LOSS/100);
						else 
						    $grand_total = floatval($od->subtotal + $od->shipping_cost - $od->discount_amount) * $EXCHANGE_RATE * (1-$EXCHANGE_LOSS/100);
	    				$grand_total = empty($grand_total) ? 0 : round($grand_total, 2);
	    				
	    				//佣金 RMB，ebay、wish、cd，用平台拉取的佣金计算，其它用本地计算佣金计算
	    				if(in_array($od->order_source, ['cdiscount', 'wish', 'ebay'])){
	    					$commission_total = floatval($od->commission_total) * $EXCHANGE_RATE * (1-$EXCHANGE_LOSS/100);
	    				}
	    				else{
	    					$commission_total = floatval($commission) * $EXCHANGE_RATE * (1-$EXCHANGE_LOSS/100);
	    				}
	    				$commission_total = empty($commission_total) ? 0 : round($commission_total, 2);
	    				
	    				//paypal手续费 RMB
	    				$paypal_fee = floatval($od->paypal_fee) * $EXCHANGE_RATE * (1-$EXCHANGE_LOSS/100);
	    				//实收费用
	    				$actual_charge = $grand_total - $commission_total - $paypal_fee;
	    				//利润
	    				$profit = $actual_charge - $product_cost - $logistics_cost;
	    				//日利润差异
	    				$profit_cny = $profit - $od->profit;
	    			
	    				$od->profit = $profit;
	    				$addi_info = $od->addi_info;
	    				$addi_info = json_decode($addi_info,true);
	    				if(empty($addi_info))
	    					$addi_info = [];
	    				  
	    				$addi_info['exchange_rate'] = $EXCHANGE_RATE;
	    				$addi_info['exchange_loss'] = $EXCHANGE_LOSS;
	    				$addi_info['product_cost'] = $product_cost_str;
	    				$addi_info['purchase_cost'] = $product_cost;
	    				$addi_info['grand_total'] = $grand_total;
	    				$addi_info['logistics_cost'] = $logistics_cost;
	    				$addi_info['commission_total'] = $commission_total;
	    				$addi_info['paypal_fee'] = $paypal_fee;
	    				$addi_info['actual_charge'] = $actual_charge;
	    				$od->addi_info = json_encode($addi_info);
	    				 
	    				//保存利润信息
	    				if($od->save(false))
	    				{
	    					foreach ($od_items as $od_item)
	    					{
	    						$od_item->save(false);
	    					}
	    					
	    					//整理需要更新的数据
	    					$order_date = date("Y-m-d",$od->paid_time);
	    					$platform = $od->order_source;
							$order_type = $od->order_type;
	    					$seller_id = $od->selleruserid;
							$order_status = $od->order_status;
	    					$info = $order_date.'&'.$platform.'&'.$seller_id.'&'.$order_status;
	    					if(!in_array($info, $exist_info))
	    					{
	    						$ProfitAdds[$info] = [
									'order_date' => $order_date,
									'platform' => $platform,
									'order_type'=>$order_type,
									'seller_id' => $seller_id,
									'profit_cny' => $profit_cny,
									'order_status' => $order_status,
	    						];
	    						$exist_info[] = $info;
	    					}
	    					else
	    						$ProfitAdds[$info]['profit_cny'] = $ProfitAdds[$info]['profit_cny'] + $profit_cny;
	    				}
				    }
				    catch (\Exception $e){
				    	echo "\n p".$puid." errer:".$e->getMessage()."\n";
				    	$err_count++;
				    	if($err_count > 10){
				    		break;
				    	}
				    }
				}
				
				//更新dash_board信息
				foreach ($ProfitAdds as $key => $ProfitAdd)
				{
				    DashBoardStatisticHelper::SalesProfitAdd($ProfitAdd['order_date'], $ProfitAdd['platform'],$ProfitAdd['order_type'], $ProfitAdd['seller_id'], $ProfitAdd['profit_cny'], false, $ProfitAdd['order_status']);
				}
				
				echo "\n p".$puid." succes\n";
				}
			catch (\Exception $e){
				echo "\n errer:".$e->getMessage()."\n";
			}
		}
			
	}//end of function
}
