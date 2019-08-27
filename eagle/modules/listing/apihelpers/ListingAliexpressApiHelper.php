<?php
namespace eagle\modules\listing\apihelpers;
use common\api\aliexpressinterface\AliexpressInterface_Api;
use common\api\aliexpressinterface\AliexpressInterface_Auth;
use common\api\aliexpressinterface\AliexpressInterface_Helper;
use eagle\modules\util\helpers\SysLogHelper;
use common\api\aliexpressinterfacev2\AliexpressInterface_Helper_Qimen;
use common\api\aliexpressinterfacev2\AliexpressInterface_Api_Qimen;

class ListingAliexpressApiHelper
{
	/**
	 * 获取在线商品在线数量
	 * 
	 */
	public static function getSkuStock($selleruserid,$productid){
		//****************判断此速卖通账号信息是否v2版    是则跳转     start*************
		$is_aliexpress_v2 = AliexpressInterface_Helper_Qimen::CheckAliexpressV2($selleruserid);
		if($is_aliexpress_v2){
			$result = self::getSkuStockV2($selleruserid,$productid);
			return $result;
		}
		//****************判断此账号信息是否v2版    end*************
		
		$a = AliexpressInterface_Auth::checkToken($selleruserid);
		if ($a) {
			//echo $sellerloginid."\n";
			$api = new AliexpressInterface_Api ();
			$access_token = $api->getAccessToken ( $selleruserid );
		}else{
			return  0 ;
		}
		//获取访问token失败
		if ($access_token === false){
			//echo $selleruserid . 'not getting access token!' . "\n";
			//die;
			return 0;
		}
		$api->access_token = $access_token;
		$param= array('productId'=>$productid);
		$result = $api->findAeProductById($param);
		if (isset($result['aeopAeProductSKUs']) && count($result['aeopAeProductSKUs'])>0){
			$total = 0;
			foreach ($result['aeopAeProductSKUs'] as $one){
				if ($one['skuStock']){
					$total+=(integer)$one['ipmSkuStock'];
				}
			}
			return $total;
		}else{
			return 0;
		}
	}
	
	/**
	 +----------------------------------------------------------
	 * 获取在线商品在线数量，v2
	 +----------------------------------------------------------
	 * log			name	date			note
	 * @author		lrq		2018/01/11		初始化
	 +----------------------------------------------------------
	 **/
	public static function getSkuStockV2($selleruserid, $productid){
		$a = AliexpressInterface_Helper_Qimen::checkToken($selleruserid);
		if ($a) {
			$api = new AliexpressInterface_Api_Qimen();
		}else{
			return  0 ;
		}
		$param = ['id' => $selleruserid, 'product_id' => $productid];
		$result = $api->findAeProductById($param);
		if (isset($result['aeop_ae_product_s_k_us']) && count($result['aeop_ae_product_s_k_us']) > 0){
			$total = 0;
			foreach ($result['aeop_ae_product_s_k_us'] as $one){
				if ($one['sku_stock']){
					$total += (integer)$one['ipm_sku_stock'];
				}
			}
			return $total;
		}else{
			return 0;
		}
	}
	
	
	/**
	 * 速卖通获取n个在线listing商品
	 */
	 public static function getListingByNum($selleruserid,$number = 100){
	 	//****************判断此速卖通账号信息是否v2版    是则跳转     start*************
	 	$is_aliexpress_v2 = AliexpressInterface_Helper_Qimen::CheckAliexpressV2($selleruserid);
	 	if($is_aliexpress_v2){
	 		$result = self::getListingByNumV2($selleruserid, $number);
	 		return $result;
	 	}
	 	//****************判断此账号信息是否v2版    end*************
	 	
	    $a = AliexpressInterface_Auth::checkToken($selleruserid);
		if ($a) {
			//echo $sellerloginid."\n";
			$api = new AliexpressInterface_Api ();
			$access_token = $api->getAccessToken ( $selleruserid );
		}else{
			$re_array = array('msg'=>'no get token!');
			return $re_array;
		}
		$api->access_token = $access_token;
		$param = array(
		    'currentPage' => 1,
			'pageSize' => $number,
			'productStatusType'=>'onSelling',
		);
		$result = $api->findProductInfoListQuery($param);
		if (isset($result ['productCount']) && $result ['productCount'] > 0) {
			foreach($result['aeopAEProductDisplayDTOList'] as $one){
				$tmp = '';
			    $gmtCreate = AliexpressInterface_Helper::transLaStrTimetoTimestamp($one ['gmtCreate']);
	            $gmtModified = AliexpressInterface_Helper::transLaStrTimetoTimestamp($one ['gmtModified']);
	            $WOD = AliexpressInterface_Helper::transLaStrTimetoTimestamp($one ['wsOfflineDate']);
	            if($one['imageURLs'] != ''){
					$t = explode(';',$one['imageURLs']);
					$pp = $t[0];
				}else{
					$pp = '';
				}
				$tmp['selleruserid'] = $selleruserid;
				$tmp['gmt_modified'] = $gmtModified;
				$tmp['gmt_create'] = $gmtCreate;
				//$tmp['freight_template_id'] = $one['freightTemplateId'];
				$tmp['owner_member_seq'] = $one['ownerMemberSeq'];
				$tmp['subject'] = $one['subject'];
				$tmp['imageurls'] = $one['imageURLs'];
				$tmp['photo_primary'] = $pp;
				$tmp['ownerMemberId'] = $one['ownerMemberId'];
				$tmp['ws_offline_date'] = $WOD;
				$tmp['productid'] = $one['productId'];
				$tmp['product_min_price'] = $one['productMinPrice'];
				$tmp['ws_display'] = $one['wsDisplay'];
				$tmp['product_max_price'] = $one['productMaxPrice'];
				$re_array[] = $tmp;
			}
		}else{
		    $re_array = array();
		}
		return $re_array;
	 }
	 
	 /**
	  +----------------------------------------------------------
	  * 速卖通获取n个在线listing商品，v2
	  +----------------------------------------------------------
	  * log			name	date			note
	  * @author		lrq		2018/01/11		初始化
	  +----------------------------------------------------------
	  **/
	 public static function getListingByNumV2($selleruserid, $number = 100){
	 	$a = AliexpressInterface_Helper_Qimen::checkToken($selleruserid);
	 	if ($a) {
	 		$api = new AliexpressInterface_Api_Qimen ();
	 	}else{
	 		return ['msg' => "$selleruserid Unauthorized!"];
	 	}
	 	$param = ['id' => $selleruserid, 'aeop_a_e_product_list_query' => json_encode(['current_page' => 1, 'page_size' => $number, 'product_status_type'=>'offline'])];
	 	$result = $api->findProductInfoListQuery($param);
	 	if (isset($result ['product_count']) && $result ['product_count'] > 0) {
	 		foreach($result['aeop_a_e_product_display_d_t_o_list'] as $one){
	 			$gmtCreate = AliexpressInterface_Helper_Qimen::transLaStrTimetoTimestamp($one ['gmt_create']);
	 			$gmtModified = AliexpressInterface_Helper_Qimen::transLaStrTimetoTimestamp($one ['gmt_modified']);
	 			$WOD = AliexpressInterface_Helper_Qimen::transLaStrTimetoTimestamp($one ['ws_offline_date']);
	 			if($one['image_u_r_ls'] != ''){
	 				$t = explode(';',$one['image_u_r_ls']);
	 				$pp = $t[0];
	 			}else{
	 				$pp = '';
	 			}
	 			$tmp = [];
	 			$tmp['selleruserid'] = $selleruserid;
	 			$tmp['gmt_modified'] = $gmtModified;
	 			$tmp['gmt_create'] = $gmtCreate;
	 			//$tmp['freight_template_id'] = $one['freightTemplateId'];
	 			$tmp['owner_member_seq'] = $one['owner_member_seq'];
	 			$tmp['subject'] = $one['subject'];
	 			$tmp['imageurls'] = $one['image_u_r_ls'];
	 			$tmp['photo_primary'] = $pp;
	 			$tmp['ownerMemberId'] = empty($one['owner_member_id']) ? '' : $one['owner_member_id'];
	 			$tmp['ws_offline_date'] = $WOD;
	 			$tmp['productid'] = $one['product_id'];
	 			$tmp['product_min_price'] = $one['product_min_price'];
	 			$tmp['ws_display'] = $one['ws_display'];
	 			$tmp['product_max_price'] = $one['product_max_price'];
	 			$re_array[] = $tmp;
	 		}
	 	}else{
	 		$re_array = array();
	 	}
	 	return $re_array;
	 }


	/**
	 * 订单基础信息查询
	 */
	 public static function getOrderInfoById($selleruserid,$order_id){
	 	//****************判断此速卖通账号信息是否v2版    是则跳转     start*************
	 	$is_aliexpress_v2 = AliexpressInterface_Helper_Qimen::CheckAliexpressV2($selleruserid);
	 	if($is_aliexpress_v2){
	 		$result = self::getOrderInfoByIdV2($selleruserid, $order_id);
	 		return $result;
	 	}
	 	//****************判断此账号信息是否v2版    end*************
	 	
	    $a = AliexpressInterface_Auth::checkToken($selleruserid);
		if ($a) {
			//echo $sellerloginid."\n";
			$api = new AliexpressInterface_Api ();
			$access_token = $api->getAccessToken ( $selleruserid );
		}else{
			$result = array('msg'=>'no get token!');
			return $result;
		}
		$api->access_token = $access_token;
		$param = array(
		    'orderId' => $order_id
		);
		$result = $api->findOrderBaseInfo($param);
		return $result;
	 } 
	 
	 /**
	  +----------------------------------------------------------
	  * 订单基础信息查询，v2
	  +----------------------------------------------------------
	  * log			name	date			note
	  * @author		lrq		2018/01/11		初始化
	  +----------------------------------------------------------
	  **/
	 public static function getOrderInfoByIdV2($selleruserid, $order_id){
	 	$a = AliexpressInterface_Helper_Qimen::checkToken($selleruserid);
	 	if ($a) {
	 		$api = new AliexpressInterface_Api_Qimen ();
	 	}else{
	 		return ['msg' => "$selleruserid Unauthorized!"];
	 	}
	 	
	 	$param = ['id' => $selleruserid, 'param1' => json_encode(['order_id' => $order_id])];
	 	$res = $api->findorderbyid($param);
	 	
	 	if(!empty($res['order_status'])){
	 		$result['orderStatus'] = $res['order_status'];
	 		return $result;
	 	}
	 	return [];
	 	
	 }
	
	/**
	  * 通过订单获取物流状态
	  * 
	  * $selleruserid 平台登录账号（获取token使用）
	  * $serviceName 物流服务KEY	UPS	
	  * $logisticsNo 物流追踪号	20100810142400000-0700
	  * $toArea      交易订单收货国家(简称)	FJ,Fiji;FI,Finland;FR,France;
	  * $outRef      用户需要查询的订单id
	  * $origin      需要查询的订单来源 AE订单为“ESCROW” 
	  * 
	  * Array
		(
		    [success] => 1
		    [officialWebsite] => http://www.ups.com
		    [details] => Array
		        (
		            [0] => Array
		                (
		                    [eventDesc] => DELIVERED
		                    [eventDate] => 1441669380
		                    [address] => UNITED STATES,DORAL
		                    [signedName] => 
		                )
		
		            [1] => Array
		                (
		                    [eventDesc] => OUT FOR DELIVERY
		                    [eventDate] => 1441669380
		                    [address] => UNITED STATES,MIAMI
		                    [signedName] => 
		                )
		
		        )
		
		)
	  */
	public static function queryTrackingResult($selleruserid,$serviceName,$logisticsNo,$toArea,$outRef,$origin='ESCROW'){
		//****************判断此速卖通账号信息是否v2版    是则跳转     start*************
	  	$is_aliexpress_v2 = AliexpressInterface_Helper_Qimen::CheckAliexpressV2($selleruserid);
	  	if($is_aliexpress_v2){
	  		$result = self::queryTrackingResultV2($selleruserid,$serviceName,$logisticsNo,$toArea,$outRef,$origin);
	  		return $result;
	  	}
	  	//****************判断此账号信息是否v2版    end*************
	  	
	      $a = AliexpressInterface_Auth::checkToken($selleruserid);
		  if ($a) {
		      //echo $sellerloginid."\n";
			  $api = new AliexpressInterface_Api ();
			  $access_token = $api->getAccessToken ( $selleruserid );
		  }else{
			  $re_array = array('msg'=>'no get token!');
		      return $re_array;
		  }
		  $api->access_token = $access_token;
	      $param = array(
		    'serviceName' => $serviceName,
			'logisticsNo' => $logisticsNo,
			'toArea'=>$toArea,
			'origin'=>$origin,
			'outRef'=>$outRef
		  );
	      
	    //  $journal_id = SysLogHelper::InvokeJrn_Create("Tracker", __CLASS__, __FUNCTION__ , array('querySmtTrackingResult',$param));
		  $result = $api->queryTrackingResult($param);
		//  SysLogHelper::InvokeJrn_UpdateResult($journal_id, isset($result)?$result:"null");
		  
		  if (!empty($result['details'] )){//有可能返回的没有这个 index 内容
			  foreach($result['details'] as $k => $info ){
			      foreach($info as $msg => $value){
			          if($msg == 'eventDate'){
			          	try{
			              $result['details'][$k]['eventDate'] = AliexpressInterface_Helper::transLaStrTimetoTimestamp($value);
			          	}catch (Exception $e) {
						  $result['details'][$k]['eventDate'] ='';
						}
			          }
			      }
			  }
		  }
		  return $result;
	  }
	  
	  /**
	   +----------------------------------------------------------
	   * 通过订单获取物流状态，v2
	   +----------------------------------------------------------
	   * log			name	date			note
	   * @author		lrq		2018/01/11		初始化
	   +----------------------------------------------------------
	   **/
	  public static function queryTrackingResultV2($selleruserid,$serviceName,$logisticsNo,$toArea,$outRef,$origin='ESCROW'){
	  	$a = AliexpressInterface_Helper_Qimen::checkToken($selleruserid);
	  	if ($a) {
	  		$api = new AliexpressInterface_Api_Qimen ();
	  	}else{
	  		return ['msg' => "$selleruserid Unauthorized!"];
	  	}
	  	$param = [
	  		'id' => $selleruserid,
	  		'service_name' => $serviceName,
	  		'logistics_no' => $logisticsNo,
	  		'to_area' => $toArea,
	  		'origin' => $origin,
	  		'out_ref' => $outRef
	  	];
	  	 
	  	$result = $api->queryTrackingResult($param);
	  
	  	if (!empty($result['details'] )){//有可能返回的没有这个 index 内容
	  		foreach($result['details'] as $k => $info ){
	  			foreach($info as $msg => $value){
	  				if($msg == 'event_date'){
	  					try{
	  						$result['details'][$k]['eventDate'] = AliexpressInterface_Helper_Qimen::transLaStrTimetoTimestamp($value);
	  					}catch (Exception $e) {
	  						$result['details'][$k]['eventDate'] ='';
	  					}
	  				}
	  				else if($msg == 'event_desc'){
	  					$result['details'][$k]['eventDesc'] = $value;
	  				}
	  			}
	  		}
	  	}
	  	return $result;
	  }


    /**
    声明发货
     * $selleruserid 平台登录账号（获取token使用）
     * $serviceName 物流服务KEY	UPS
     * $logisticsNo 物流追踪号	20100810142400000-0700
     * description      交易订单收货国家(简称)	FJ,Fiji;FI,Finland;FR,France;
     * $sendType      状态包括：全部发货(all)、部分发货(part)
     * $outRef      用户需要发货的订单id
     * $trackingWebsite      当serviceName=Other的情况时，需要填写对应的追踪网址

     */
    public static function sellerShipment($selleruserid,$serviceName,$logisticsNo,$description,$sendType,$outRef,$trackingWebsite){
    	//****************判断此速卖通账号信息是否v2版    是则跳转     start*************
    	$is_aliexpress_v2 = AliexpressInterface_Helper_Qimen::CheckAliexpressV2($selleruserid);
    	if($is_aliexpress_v2){
    		$result = self::sellerShipmentV2($selleruserid,$serviceName,$logisticsNo,$description,$sendType,$outRef,$trackingWebsite);
    		return $result;
    	}
    	//****************判断此账号信息是否v2版    end*************
    	
        $a = AliexpressInterface_Auth::checkToken($selleruserid);
        if ($a) {
            //echo $sellerloginid."\n";
            $api = new AliexpressInterface_Api ();
            $access_token = $api->getAccessToken ( $selleruserid );
        }else{
            $re_array = array('msg'=>'no get token!');
            return $re_array;
        }
        $api->access_token = $access_token;
        $param = array(
            'serviceName' => $serviceName,
            'logisticsNo' => $logisticsNo,
            'description'=>$description,
            'sendType'=>$sendType,
            'outRef'=>$outRef,
            'trackingWebsite'=>$trackingWebsite
        );

        //  $journal_id = SysLogHelper::InvokeJrn_Create("Tracker", __CLASS__, __FUNCTION__ , array('querySmtTrackingResult',$param));
        $result = $api->sellerShipment($param);
        //  SysLogHelper::InvokeJrn_UpdateResult($journal_id, isset($result)?$result:"null");


//        if (!empty($result['details'] )){//有可能返回的没有这个 index 内容
//            foreach($result['details'] as $k => $info ){
//                foreach($info as $msg => $value){
//                    if($msg == 'eventDate'){
//                        try{
//                            $result['details'][$k]['eventDate'] = AliexpressInterface_Helper::transLaStrTimetoTimestamp($value);
//                        }catch (Exception $e) {
//                            $result['details'][$k]['eventDate'] ='';
//                        }
//                    }
//                }
//            }
//        }
        return $result;
    }

    /**
     +----------------------------------------------------------
     *  声明发货，v2
     +----------------------------------------------------------
     * log			name	date			note
     * @author		lrq		2018/01/11		初始化
     +----------------------------------------------------------
     **/
    public static function sellerShipmentV2($selleruserid,$serviceName,$logisticsNo,$description,$sendType,$outRef,$trackingWebsite){
    	$a = AliexpressInterface_Helper_Qimen::checkToken($selleruserid);
    	if ($a) {
    		$api = new AliexpressInterface_Api_Qimen ();
    	}else{
    		return ['msg' => "$selleruserid Unauthorized!"];
    	}
    	$param = [
    		'id' => $selleruserid,
    		'service_name' => $serviceName,
    		'logistics_no' => $logisticsNo,
    		'description'=>$description,
    		'send_type'=>$sendType,
    		'out_ref'=>$outRef,
    		'tracking_website'=>$trackingWebsite
    	];
    
    	$result = $api->sellershipmentfortop($param);
    	if(!empty($result['result_success'])){
    		$result['success'] = true;
    	}
    	else{
    		$result['success'] = false;
    	}
    	return $result;
    }


    /**
    修改声明发货
     * $selleruserid  平台登录账号（获取token使用）
     * $oldServiceName  用户需要修改的的老的发货物流服务
     * $oldLogisticsNo  用户需要修改的老的物流追踪号
     * $newServiceName  新物流服务KEY	UPS
     * $newLogisticsNo  新物流追踪号	20100810142400000-0700
     * $description      备注(只能输入英文)
     * $sendType      状态包括：全部发货(all)、部分发货(part)
     * $outRef      用户需要查询的订单id
     * $trackingWebsite      当serviceName=Other的情况时，需要填写对应的追踪网址

     */
    public static function sellerModifiedShipment($selleruserid,$oldServiceName,$oldLogisticsNo,$newServiceName,$newLogisticsNo,$description,$sendType,$outRef,$trackingWebsite){
    	//****************判断此速卖通账号信息是否v2版    是则跳转     start*************
    	$is_aliexpress_v2 = AliexpressInterface_Helper_Qimen::CheckAliexpressV2($selleruserid);
    	if($is_aliexpress_v2){
    		$result = self::sellerModifiedShipmentV2($selleruserid,$oldServiceName,$oldLogisticsNo,$newServiceName,$newLogisticsNo,$description,$sendType,$outRef,$trackingWebsite);
    		return $result;
    	}
    	//****************判断此账号信息是否v2版    end*************
    	
        $a = AliexpressInterface_Auth::checkToken($selleruserid);
        if ($a) {
            //echo $sellerloginid."\n";
            $api = new AliexpressInterface_Api ();
            $access_token = $api->getAccessToken ( $selleruserid );
        }else{
            $re_array = array('msg'=>'no get token!');
            return $re_array;
        }
        $api->access_token = $access_token;
        $param = array(
            'oldServiceName' => $oldServiceName,
            'oldLogisticsNo' => $oldLogisticsNo,
            'newServiceName' => $newServiceName,
            'newLogisticsNo' => $newLogisticsNo,
            'description'=>$description,
            'sendType'=>$sendType,
            'outRef'=>$outRef,
            'trackingWebsite'=>$trackingWebsite
        );

        //  $journal_id = SysLogHelper::InvokeJrn_Create("Tracker", __CLASS__, __FUNCTION__ , array('querySmtTrackingResult',$param));
        $result = $api->sellerModifiedShipment($param);
        //  SysLogHelper::InvokeJrn_UpdateResult($journal_id, isset($result)?$result:"null");


//        if (!empty($result['details'] )){//有可能返回的没有这个 index 内容
//            foreach($result['details'] as $k => $info ){
//                foreach($info as $msg => $value){
//                    if($msg == 'eventDate'){
//                        try{
//                            $result['details'][$k]['eventDate'] = AliexpressInterface_Helper::transLaStrTimetoTimestamp($value);
//                        }catch (Exception $e) {
//                            $result['details'][$k]['eventDate'] ='';
//                        }
//                    }
//                }
//            }
//        }
        return $result;
    }
    
    /**
     +----------------------------------------------------------
     *  修改声明发货，v2
     +----------------------------------------------------------
     * log			name	date			note
     * @author		lrq		2018/01/11		初始化
     +----------------------------------------------------------
     **/
    public static function sellerModifiedShipmentV2($selleruserid,$oldServiceName,$oldLogisticsNo,$newServiceName,$newLogisticsNo,$description,$sendType,$outRef,$trackingWebsite){
    	$a = AliexpressInterface_Helper_Qimen::checkToken($selleruserid);
    	if ($a) {
    		$api = new AliexpressInterface_Api_Qimen ();
    	}else{
    		return ['msg' => "$selleruserid Unauthorized!"];
    	}
    	$param = [
    		'id' => $selleruserid,
    		'old_service_name' => $oldServiceName,
    		'old_logistics_no' => $oldLogisticsNo,
    		'new_service_name' => $newServiceName,
    		'new_logistics_no' => $newLogisticsNo,
    		'description' => $description,
    		'send_type' => $sendType,
    		'out_ref' => $outRef,
    		'tracking_website' => $trackingWebsite
    	];
    
    	$result = $api->sellermodifiedshipmentfortop($param);
    	if(!empty($result['result_success'])){
    		$result['success'] = true;
    	}
    	else{
    		$result['success'] = false;
    	}
    	return $result;
    }
}
?>