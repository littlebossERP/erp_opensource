<?php

namespace eagle\modules\amazoncs\controllers;

use yii;
use yii\filters\VerbFilter;
use eagle\modules\amazoncs\models\AmazonFeedbackInfo;
use eagle\modules\amazoncs\models\AmazonReviewInfo;
use eagle\modules\amazoncs\models\AmazonOrderInfo;
use eagle\modules\amazoncs\helpers\ClientHelper;
use eagle\modules\amazoncs\helpers\AmazoncsHelper;
use eagle\modules\amazon\apihelpers\AmazonUserApiHelp;
use eagle\modules\amazon\apihelpers\AmazonApiHelper;
use eagle\modules\order\models\OdOrder;

class ClientController extends \eagle\components\Controller{
	
	public function behaviors()
	{
		return [
			'access' => [
				'class' => \yii\filters\AccessControl::className(),
				'rules' => [
					[
						'allow' => true,
						'roles' => ['@'],
					],
				],
			],
			'verbs' => [
				'class' => VerbFilter::className(),
				'actions' => [
					'delete' => ['post'],
				],
			],
		];
	}
	
	public $enableCsrfValidation = false;
	
	/**
	 +----------------------------------------------------------
	 * 保存feedback信息
	 +----------------------------------------------------------
	 * @param
	 +----------------------------------------------------------
	 * log			name		date			note
	 * @author		lrq		  2017/02/13		初始化
	 +----------------------------------------------------------
	 **/
	public function actionSaveFeedback(){
	    try{
        	if(!empty($_POST['Feedback'])){
        	    $feedback_json = base64_decode(str_replace(" ","+",$_POST['Feedback']));
        	    $feedback_arr = json_decode($feedback_json, true);
        	    
        	    foreach($feedback_arr['Feedback'] as $feedback){
        	        $AmazonFeedback = AmazonFeedbackInfo::findOne(['merchant_id'=>$feedback['merchantId'], 'marketplace_id'=>$feedback['marketplaceId'], 'order_source_order_id'=>$feedback['Order ID']]);
        	        if(!empty($AmazonFeedback)){
        	        	continue;
        	        }
        	        
    	    	    $AmazonFeedback = new AmazonFeedbackInfo();
    	    	    $AmazonFeedback->create_time = $feedback['Date'];
    	    	    $AmazonFeedback->rating = $feedback['Rating'];
    	    	    $AmazonFeedback->feedback_comments = str_replace(" ","+",$feedback['Comments']);
    	    	    if(strtoupper(trim($feedback['Arrived on Time'])) == 'YES'){
    	    	    	$AmazonFeedback->arrived_on_time = 1;
    	    	    }
    	    	    else{
    	    	    	$AmazonFeedback->arrived_on_time = 0;
    	    	    }
    	    	    if(strtoupper(trim($feedback['Item as Described'])) == 'YES'){
    	    	    	$AmazonFeedback->item_as_described = 1;
    	    	    }
    	    	    else{
    	    	    	$AmazonFeedback->item_as_described = 0;
    	    	    }
    	    	    if(strtoupper(trim($feedback['Customer Service'])) == 'YES'){
    	    	    	$AmazonFeedback->customer_service = 1;
    	    	    }
    	    	    else{
    	    	    	$AmazonFeedback->customer_service = 0;
    	    	    }
    	    	    $AmazonFeedback->order_source_order_id = $feedback['Order ID'];
    	    	    $AmazonFeedback->rater_email = $feedback['Rater Email'];
    	    	    $AmazonFeedback->rater_role = $feedback['Rater Role'];
    	    	    $AmazonFeedback->respond_url = $feedback['Respond Url'];
    	    	    $AmazonFeedback->resolve_url = $feedback['Resolve Url'];
    	    	    $AmazonFeedback->message_from_amazon = str_replace(" ","+",$feedback['Message from Amazon']);
    	    	    $AmazonFeedback->rating_status = $feedback['Rating Status'];
    	    	    $AmazonFeedback->marketplace_id = $feedback['marketplaceId'];
    	    	    $AmazonFeedback->merchant_id = $feedback['merchantId'];
    	    	    if(!$AmazonFeedback->save(false)){
    	    	        return "false,C003,保存Feedback信息失败: ".$AmazonFeedback->getErrors();
    	    	    }
        	    }
        	}
        	else{
        	    return "false,C001,Feedback信息缺失！";
        	}
	    }
	    catch(\Exception $ex){
	        return "false,C002,异常: ".$ex->getMessage();
	    }
    	
    	return "true,,更新成功";
    }
    
    /**
     +----------------------------------------------------------
     * 保存客户端获取信息报表
     +----------------------------------------------------------
     * @param
     +----------------------------------------------------------
     * log			name		date			note
     * @author		lrq		  2017/02/13		初始化
     +----------------------------------------------------------
     **/
    public function actionSaveReport(){
    	try{
	        $uid = \Yii::$app->subdb->getCurrentPuid();
	        if(empty($uid))
	        	return "false,C001,登录信息失效！";
	        if(empty($_POST['startTime']))
	            return "false,C002,开始时间缺省！";
	        if(empty($_POST['type']))
	        	return "false,C003,报表类型缺省！";
	        
	        $start_time = $_POST['startTime'];
	        $type = $_POST['type'];
	        $end_time = empty($_POST['endTime']) ? "0" : $_POST['endTime'];
	        $merchant_id = empty($_POST['merchantId']) ? "" : $_POST['merchantId'];
	        $marketplace_id = empty($_POST['marketplaceId']) ? "" : $_POST['marketplaceId'];
	        $status = empty($_POST['status']) ? "0" : $_POST['status'];
	        $message = empty($_POST['message']) ? "" : base64_decode(str_replace(" ","+",$_POST['message']));
	        $asin = empty($_POST['asin']) ? "" : $_POST['asin'];
	        $last_date = empty($_POST['last_date']) ? "0" : $_POST['last_date'];
	        $post_data = empty($_POST['post_data']) ? "" : $_POST['post_data'];
	        $sum_count = empty($_POST['sum_count']) ? "0" : $_POST['sum_count'];
	        $read_count = empty($_POST['read_count']) ? "0" : $_POST['read_count'];
	        
	        $command = \Yii::$app->db_queue->createCommand("select id from `amazon_client_report` where `uid`=$uid and `start_time`=$start_time and `type`=$type");
	        $queue = $command->execute();
	        if(empty($queue)){
	            $command = \Yii::$app->db_queue->createCommand("INSERT INTO `amazon_client_report`
	                    (`uid`,`merchant_id`,`marketplace_id`,`start_time`,`end_time`,`type`,`status`,`message`, `asin`, `last_date`)
	                    VALUES
	                    ($uid,'$merchant_id','$marketplace_id',$start_time,$end_time,$type,$status,'$message','$asin', $last_date)");
	            $record = $command->execute();
	            if(!$record)
	                return "false,C004,插入报表信息失败！".$command->getsql();
	        }
	        else{
	            $sql = "UPDATE `amazon_client_report` SET 
	                    `merchant_id`='$merchant_id',`marketplace_id`='$marketplace_id',`end_time`=$end_time,`status`=$status,`message`='$message',`last_date`=$last_date,`sum_count`=$sum_count,`read_count`=$read_count,`post_data`='$post_data'";
	            $sql .= " where `uid`=$uid and `start_time`=$start_time and `type`=$type";
	            
	            $command = \Yii::$app->db_queue->createCommand($sql);
	            $record = $command->execute();
	            if(!$record)
	            	return "false,C005,更新报表信息失败".$command->getsql();
	        }
        }
        catch(\Exception $ex){
        	return "false,C006,异常: ".$ex->getMessage();
        }
        
        return "true,,更新成功";
    }
    
    /**
     +----------------------------------------------------------
     * 返回最后一次成功读取的时间
     +----------------------------------------------------------
     * @param
     +----------------------------------------------------------
     * log			name		date			note
     * @author		lrq		  2017/02/13		初始化
     +----------------------------------------------------------
     **/
    public function actionReturnDate(){
    	try{
	        if(empty($_POST['merchantId']) || empty($_POST['type'])){
	        	return "false,C001,传递参数缺省";
	        }
	        
	        $param['merchant_id'] = $_POST['merchantId'];
	        $param['type'] = $_POST['type'];
	        $param['marketplace_id'] = '';
	        $param['asin'] = '';
	        $param['last_date'] = '';
	        
	        if(!empty($_POST['marketplace_id'])){
	            $param['marketplace_id'] = $_POST['marketplaceId'];
	        }
	        else if(!empty($_POST['asin'])){
	            $param['asin'] = explode(',',$_POST['asin']);
	        }
	        
	        $msg = 'get_date:';
	        $ret = ClientHelper::getClientReportDate($param);
	        if($ret['sucess'] == 0){
	            return "false,C002,".$ret['msg'];
	        }
	        else{
	            foreach($ret['data'] as $report){
	                $msg .= $report['merchant_id'].',';
	                $msg .= $report['marketplace_id'].',';
	                $msg .= $report['asin'].',';
	                $msg .= $report['last_date'].';';
	            }
	        }
        }
        catch(\Exception $ex){
        	return "false,C003,异常: ".$ex->getMessage();
        }
        
        return $msg;
    }
    
    /**
     +----------------------------------------------------------
     * 根据账号、站点，返回订单ASIN
     +----------------------------------------------------------
     * @param
     +----------------------------------------------------------
     * log			name		date			note
     * @author		lrq		  2017/02/13		初始化
     +----------------------------------------------------------
     **/
    public function actionReturnAsin(){
    	try{
	    	if(empty($_POST['merchantId']) || empty($_POST['marketplaceId'])){
	    		return "false,C001,传递参数缺省";
	    	}
	    	
	    	$uid = \Yii::$app->subdb->getCurrentPuid();
	    	if(empty($uid))
	    		return "false,C002,登录信息失效";
	    	
	    	$merchant_id = $_POST['merchantId'];
	    	$marketplace_id = $_POST['marketplaceId'];
	    	
	    	$site = '';
	    	$AMAZON_MARKETPLACE_REGION_CONFIG = AmazonApiHelper::$AMAZON_MARKETPLACE_REGION_CONFIG;
	    	if(!empty($AMAZON_MARKETPLACE_REGION_CONFIG[$marketplace_id])){
	    		$site = $AMAZON_MARKETPLACE_REGION_CONFIG[$marketplace_id];
	    	}
	    	else{
	    		return "false,C003,站点信息不存在";
	    	}
	    	
	    	$asin = '';
	    	$ret = AmazoncsHelper::getAsinListByAccountSite($uid, $merchant_id, $site);
	    	if($ret['success']){
	    		$msg = 'get_asin:';
	    		foreach ($ret['asin'] as $v){
	    			$asin .= $v.',';
	    		}
	    		if($asin == ''){
	    			return "false,C004,没有此站点的AISN信息";
	    		}
	    		
	    		$ret = rtrim($asin, ',');
	    		return $ret;
	    	}
	    	else{
	    		return "false,C005,".$ret['message'];
	    	}
    	}
    	catch(\Exception $ex){
    		return "false,C002,异常: ".$ex->getMessage();
    	}
        
        return $ret;
    }
    
    /**
     +----------------------------------------------------------
     * 保存review信息
     +----------------------------------------------------------
     * @param
     +----------------------------------------------------------
     * log			name		date			note
     * @author		lrq		  2017/02/17		初始化
     +----------------------------------------------------------
     **/
    public function actionSaveReview(){
    	try{
	        if(empty($_POST['merchantId']) || empty($_POST['marketplaceId']) || empty($_POST['asin']) || empty($_POST['review'])){
	            return "false,C001,传递参数缺省";
	        }
	        $merchant_id = $_POST['merchantId'];
	        $marketplace_id = $_POST['marketplaceId'];
	        $asin = $_POST['asin'];
	        $review_post = $_POST['review'];
	        
			$review_json = base64_decode(str_replace(" ","+",$review_post));
			$review_arr = json_decode($review_json, true);
			
			$buyer_name_arr = array();
			$order_info = array();
			//整理对应ASIN，买家名与订单号的对应关系，根据od_order_v2
			$orderInfo = OdOrder::find()->select(['order_source_order_id', 'source_buyer_user_id'])
				->Where(['order_source' => 'amazon'])
				->andWhere("order_status>=200 and order_status<600 and order_id in (select order_id from od_order_item_v2 where order_source_itemid='".$asin."')")
				->asarray()->all();
			if(!empty($orderInfo)){
				foreach ($orderInfo as $order){
					$order_source_order_id = $order['order_source_order_id'];
					$buyer_name = strtoupper($order['source_buyer_user_id']);
					if(empty($buyer_name_arr[$buyer_name]) || !in_array($order_source_order_id, $buyer_name_arr)){
						$buyer_name_arr[$buyer_name][] = $order_source_order_id;
					}
				}
			}
			//整理客户id与订单号的关系，根据amazon_order_info
			$amazon_order = AmazonOrderInfo::find()->select(['cust_id', 'order_id'])->where(['merchant_id'=>$merchant_id, 'marketplace_id'=>$marketplace_id])->asArray()->all();
			foreach($amazon_order as $v){
				if(!empty($v['cust_id'])){
					$order_info[$v['cust_id']] = $v['order_id'];
				}
			}
			
			//保存最新的review信息
			foreach($review_arr['Review'] as $review){
			    $AmazonReview = AmazonReviewInfo::findOne(['asin'=>$asin, 'merchant_id'=>$merchant_id, 'marketplace_id'=>$marketplace_id, 'review_id'=>$review['reviewId'], 'create_time'=>$review['date']]);
			    if(empty($AmazonReview)){
				    $AmazonReview = new AmazonReviewInfo();
					$AmazonReview->merchant_id = $merchant_id;
					$AmazonReview->marketplace_id = $marketplace_id;
					$AmazonReview->asin = $asin;
					
					$AmazonReview->review_id = $review['reviewId'];
					$AmazonReview->create_time = $review['date'];
					$AmazonReview->rating = $review['rating'];
					$AmazonReview->title = str_replace(" ","+",$review['title']);
					try{
						$AmazonReview->author = base64_decode(str_replace(" ","+",$review['author']));
					}
					catch(\Exception $ex){}
					$AmazonReview->format_strip = $review['format_strip'];
					$AmazonReview->verified_purchase = $review['verified_purchase'];
					$AmazonReview->review_comments = str_replace(" ","+",$review['review_comments']);
					$AmazonReview->order_source_order_id = '';
				}
				
				if(empty($AmazonReview->order_source_order_id)){
					//根据抓取的Amazon后台订单信息，匹配
					if(!empty($review['cust_id'])){
						$AmazonReview->cust_id = $review['cust_id'];
						
						if(!empty($order_info[$review['cust_id']]))
							$AmazonReview->order_source_order_id = $order_info[$review['cust_id']];
					}
					else{
						//根据留言者匹配
						if(!empty($buyer_name_arr) && !empty($AmazonReview->author)){
							//用source_buyer_user_id匹配订单号
							$author = $AmazonReview->author;
							$author = strtoupper($author);
							if(!empty($buyer_name_arr[$author])){
								foreach ($buyer_name_arr[$author] as $id){
									$AmazonReview->order_source_order_id .= $id.',';
								}
							}
							//判断留言者是否以.结尾，是则继续匹配
							else if(substr($author, strlen($author) - 1) == '.'){
								$author = rtrim($author, '.');
								foreach ($buyer_name_arr as $k => $v){
									if(substr($k, 0, strlen($author)) == $author){
										foreach ($v as $id){
											$AmazonReview->order_source_order_id .= $id.',';
										}
									}
								}
							}
							$AmazonReview->order_source_order_id = rtrim($AmazonReview->order_source_order_id, ',');
						}
					}
					
					if(!$AmazonReview->save(false)){
	    				return "false,C003,保存Review信息失败: ".$AmazonReview->getErrors();
	    			}
				}
			}
		}
		catch(\Exception $ex){
			return "false,C002,异常: ".$ex->getMessage();
		}
		
		return "true,,更新成功";
    }
    
    /**
     +----------------------------------------------------------
     * 返回已绑定的Amazon账号、站点信息
     +----------------------------------------------------------
     * @param
     +----------------------------------------------------------
     * log			name		date			note
     * @author		lrq		  2017/03/13		初始化
     +----------------------------------------------------------
     **/
    public function actionReturnAmazonInfo(){
    	//amazom店铺、站点组
    	$accountMaketplaceMap = AmazonUserApiHelp::getAccountMaketplaceMap();
    	if(empty($accountMaketplaceMap)){
    		return "false,C001,未在小老板上绑定Amazon账号";
    	}
    	
    	$amazonInfo = 'get_amazoninfo:';
    	foreach($accountMaketplaceMap as $account){
    		$amazonInfo .= $account['merchant']['merchant_id'].',';
    		$amazonInfo .= $account['merchant']['store_name'].':';
    		foreach ($account['marketplace'] as $marketplace){
    			$amazonInfo .= $marketplace['marketplace_id'].',';
    			$amazonInfo .= $marketplace['en_name'].';';
    		}
    		$amazonInfo .= '|';
    	}
    	return $amazonInfo;
    }
    
    /**
     +----------------------------------------------------------
     * 保存order信息
     +----------------------------------------------------------
     * @param
     +----------------------------------------------------------
     * log			name		date			note
     * @author		lrq		  2017/05/27		初始化
     +----------------------------------------------------------
     **/
    public function actionSaveOrder(){
    	try{
    		if(!empty($_POST['Order'])){
    			$order_json = base64_decode(str_replace(" ","+",$_POST['Order']));
    			$order_arr = json_decode($order_json, true);
    			 
    			foreach($order_arr['Order'] as $order){
    				$AmazonOrder = AmazonOrderInfo::findOne(['merchant_id'=>$order['merchantId'], 'marketplace_id'=>$order['marketplaceId'], 'order_id'=>$order['order_id']]);
    				if(!empty($AmazonOrder)){
    					continue;
    				}
    					
    				$AmazonOrder = new AmazonOrderInfo();
    				$AmazonOrder->order_time = $order['date'];
    				$AmazonOrder->create_time = time();
    				$AmazonOrder->order_id = $order['order_id'];
    				$AmazonOrder->cust_id = $order['cust_id'];
    				$AmazonOrder->marketplace_id = $order['marketplaceId'];
    				$AmazonOrder->merchant_id = $order['merchantId'];
    				if(!$AmazonOrder->save(false)){
    					return "false,C003,保存Order信息失败: ".$AmazonOrder->getErrors();
    				}
    			}
    		}
    		else{
    			return "false,C001,Order信息缺失！";
    		}
    	}
    	catch(\Exception $ex){
    		return "false,C002,异常: ".$ex->getMessage();
    	}
    	 
    	return "true,,更新成功";
    }
    
    public function actionTest1(){
    	$ret = ClientHelper::getClientReportDateInfo();
    	print_r($ret);
    }
}
