<?php
/**
 * @link http://www.witsion.com/
 * @copyright Copyright (c) 2014 Yii Software LLC
 * @license http://www.witsion.com/
 */
namespace eagle\modules\amazoncs\helpers;

use yii;
use yii\base\Exception;
use eagle\modules\amazon\apihelpers\AmazonApiHelper;

class ClientHelper{
	
    /**
     +----------------------------------------------------------
     * 查询Amazon获取的feedback、review的最后获取时间、日期
     +----------------------------------------------------------
     * @param $param   array    筛选信息
     *        [
     *        	'merchant_id'     String
     *        	'type'            int        1 feedback 2 review
     *        	'marketplace_id'  String
     *        	'site_id'         String     站点(国家二字代码)
     *        	'asin'            array
     *        	...
     *        ]
     +----------------------------------------------------------
     * log			name		date			note
     * @author		lrq		  2017/02/13		初始化
     +----------------------------------------------------------
     **/
	public static function getClientReportDate($param){
	    $ret['sucess'] = 0;
	    $ret['msg'] = '';
	    
	    try{
    	    if(empty($param)){
    	        $ret['msg'] = '筛选参数不能为空';
    	        return $ret;
    	    }
    	    
    		$condition = "";
    		$groupby = "";
    		foreach($param as $k => $v){
    		    switch($k){
    		    	case 'asin':
    		    	    if(!empty($v)){
        		    	    $asinStr = '';
        		    	    foreach($v as $asin){
        		    	        $asinStr .= "'".$asin."',";
        		    	    }
        		    	    $condition .= $k." in (".rtrim($asinStr,',').") and";
    		    	    }
    		    	    $groupby .= $k.',';
    		    	    break;
		    	    case 'last_date':
		    	        $condition .= " last_date>0 and";
		    	        break;
	    	        case 'site_id':
	    	        	$AMAZON_MARKETPLACE_REGION_CONFIG = AmazonApiHelper::$AMAZON_MARKETPLACE_REGION_CONFIG;
	    	        	foreach($AMAZON_MARKETPLACE_REGION_CONFIG as $mark => $site){
	    	        		if($v == $site){
	    	        			$condition .= "marketplace_id='".$mark."' and ";
	    	        			break;
	    	        		}
	    	        	}
	    	        	$groupby .= 'marketplace_id,';
	    	        	break;
    		    	default:
    		    	    if(!empty($v)){
    		    	        $condition .= $k."='".$v."' and ";
    		    	    }
    		    	    $groupby .= $k.',';
    		    	    break;
    		    }
    		}
    		if($condition != ''){
    		    $condition = substr($condition, 0, strlen($condition) - 4);
    		}
    		$groupby = rtrim($groupby,',');
    	    
    		$command = \Yii::$app->db_queue->createCommand("select ".$groupby.",end_time,last_date from `amazon_client_report` where status=1 and ".$condition." group by ".$groupby." order by last_date");
    		//print_r($command->getsql());die;
    		$queue = $command->queryAll();
    		$ret['sucess'] = 1;
    		$ret['data'] = $queue;
    		return $ret;
	    }
	    catch(\Exception $ex){
	        $ret['msg'] = $ex->getMessage();
	    }
		
		return $ret;
	}

	/**
	 +----------------------------------------------------------
	 * 查询amazon账号、站点，分别的客户端最后运行时间
	 +----------------------------------------------------------
	 * @param $merchant_id   string    Amazon账号id
	 * @param $site          string    站点，国家简码
	 +----------------------------------------------------------
	 * log			name		date			note
	 * @author		lrq		  2017/02/13		初始化
	 +----------------------------------------------------------
	 **/
	public static function getClientReportDateInfo($merchant_id = 'all', $site = 'all'){
		$clientInfo = array();
	    try{
	    	$AMAZON_MARKETPLACE_REGION_CONFIG = AmazonApiHelper::$AMAZON_MARKETPLACE_REGION_CONFIG;
	    	
	    	$condition = '';
	    	if(!empty($merchant_id) && $merchant_id != 'all'){
	    		$condition = "and merchant_id='".$merchant_id."'";
	    	}
	    	if(!empty($site) && $site != 'all'){
	    		foreach($AMAZON_MARKETPLACE_REGION_CONFIG as $k => $v){
	    			if($v == $site){
	    				$condition .= " and marketplace_id='".$k."' ";
	    				break;
	    			}
	    		}
	    	}
	    	
    	    $sql = "select * from (select merchant_id, marketplace_id, end_time, type from `amazon_client_report` where status=1 ".$condition." order by merchant_id, marketplace_id, type,end_time desc) as test group by merchant_id, marketplace_id, type ";
    		$command = \Yii::$app->db_queue->createCommand($sql);
    		$rows = $command->queryAll();
    		
    		foreach ($rows as $row){
    			if(!empty($AMAZON_MARKETPLACE_REGION_CONFIG[$row['marketplace_id']])){
    				$row_site = $AMAZON_MARKETPLACE_REGION_CONFIG[$row['marketplace_id']];
    				
    				$row_merchant_id = $row['merchant_id']; 
    				if($row['type'] == '1'){
    					$clientInfo[$row_merchant_id][$row_site]['feedback_time'] = $row['end_time'];
    				}
    				else if($row['type'] == '2'){
    					$clientInfo[$row_merchant_id][$row_site]['review_time'] = $row['end_time'];
    				}
    			}
    		}
	    }
	    catch(\Exception $ex){
	    }
		
		return $clientInfo;
	}
}//end of class
?>