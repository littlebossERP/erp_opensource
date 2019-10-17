<?php
/**
 * 
 * @author xjq
 * http://docs.aws.amazon.com/AWSECommerceService/latest/DG/Welcome.html
 * Product Advertising API 是比较特殊的api。  跟之前的order获取，订单发货的接口不是一个系统的。
 * 这可以理解为一个公共查询的接口，  需要先 申请到 	$public_key和$private_key才可以使用
 *
 */

class AmazonProductAdsAPI{

	// aws账号
	// TODO proxy dev account
	const PUBLIC_KEY="";
	const PRIVATE_KEY="";
	
	static $AMAZON_PRODUCT_ADVERTISING_URL_CONFIG = array(
		'DE'=>"webservices.amazon.de",
		'CA'=>"webservices.amazon.ca",
		'CN'=>"webservices.amazon.cn",
		'ES'=>"webservices.amazon.es",
		'FR'=>"webservices.amazon.fr",
		'IN'=>"webservices.amazon.in",
		'IT'=>"webservices.amazon.it",
		'JP'=>"webservices.amazon.co.jp",
		'UK'=>"webservices.amazon.co.uk",
		'US'=>"webservices.amazon.com",
		'MX'=>"webservices.amazon.com.mx",
		'BR'=>"webservices.amazon.com.br",
        'AU'=>"webservices.amazon.com.au", 
		'AE'=>"webservices.amazon.ae",
		'TR'=>"webservices.amazon.com.tr", 
		'SG'=>"webservices.amazon.sg", // ping 不通 还没出
	);	
		 
	static $AMAZON_MARKETPLACE_REGION_CONFIG = array(
		'A2EUQ1WTGCTBG2'=>"CA",
		'ATVPDKIKX0DER'=>"US",
		'A1PA6795UKMFR9'=>"DE",
		'A1RKKUPIHCS9HS'=>"ES",
		'A13V1IB3VIYZZH'=>"FR",
		'A21TJRUUN4KGV'=>"IN",
		'APJ6JRA9NG5V4'=>"IT",
		'A1F83G8C2ARO7P'=>"UK",
		'A1VC38T7YXB528'=>"JP",
		'AAHKV2X7AFYLW'=>"CN",
		'A1AM78C64UM0Y8'=>"MX",
        'A39IBJ37TRP1C6'=>"AU",
		'A2Q3Y263D00KWC'=>"BR",
		'A2VIGQ35RCS4UG'=>"AE",
		'A33AVAJ2PDY3EV'=>"TR",
		'A19VAU5U5O7RUS'=>"SG",
	);	
	
	// 广告联盟接口与 associates.amazon不同站点的Tracking ID之间的关联在这个AssociateTag
	// 但这个ASSOCIATETAG 对广告联盟接口获取产品信息貌似不是必要的，注册了associates.amazon.ca 之后直接用 （美国的）也可以获取产品图片
	// @todo 其他站点可能还需要注册 ， 
	static $AMAZON_MARKETPLACE_ASSOCIATETAG_CONFIG = array(
			'A2EUQ1WTGCTBG2'=>"",
			'ATVPDKIKX0DER'=>"",
			'A1PA6795UKMFR9'=>"",
			'A1RKKUPIHCS9HS'=>"",
			'A13V1IB3VIYZZH'=>"",
			'A21TJRUUN4KGV'=>"",
			'APJ6JRA9NG5V4'=>"",
			'A1F83G8C2ARO7P'=>"",
			'A1VC38T7YXB528'=>"",
			'AAHKV2X7AFYLW'=>"",
			'A1AM78C64UM0Y8'=>"",
            'A39IBJ37TRP1C6'=>"",
			'A19VAU5U5O7RUS'=>"",
	);
	
	private static function _productAdsApiRequest($marketplaceId,$params){
		
	      if (!isset(self::$AMAZON_MARKETPLACE_REGION_CONFIG[$marketplaceId])) return false;
		$countryCode=self::$AMAZON_MARKETPLACE_REGION_CONFIG[$marketplaceId];
		//提供服务的域名
		$host=self::$AMAZON_PRODUCT_ADVERTISING_URL_CONFIG[$countryCode];
		$associateTag = self::$AMAZON_MARKETPLACE_ASSOCIATETAG_CONFIG[$marketplaceId]; 
		
		$public_key=self::PUBLIC_KEY;		
		$private_key=self::PRIVATE_KEY;
		$method = "GET";
		$uri = "/onca/xml";
		
		
		$params["Service"]          = "AWSECommerceService";
		$params["AWSAccessKeyId"]   = $public_key;
		$params["AssociateTag"]     = 'littleboss-20';
		$params["Timestamp"]        = gmdate("Y-m-d\TH:i:s\Z");
		$params["Version"]          = "2011-08-01";

		
		/* The params need to be sorted by the key, as Amazon does this at
		  their end and then generates the hash of the same. If the params
		  are not in order then the generated hash will be different thus
		  failing the authetication process.
		*/
		ksort($params);
		
		$canonicalized_query = array();

		foreach ($params as $param=>$value)
		{
			$param = str_replace("%7E", "~", rawurlencode($param));
			$value = str_replace("%7E", "~", rawurlencode($value));
			$canonicalized_query[] = $param."=".$value;
		}
		
		$canonicalized_query = implode("&", $canonicalized_query);

		$string_to_sign = $method."\n".$host."\n".$uri."\n".$canonicalized_query;
		
		/* calculate the signature using HMAC with SHA256 and base64-encoding.
		   The 'hash_hmac' function is only available from PHP 5 >= 5.1.2.
		*/
		$signature = base64_encode(hash_hmac("sha256", $string_to_sign, $private_key, True));
		
		/* encode the signature for the request */
		$signature = str_replace("%7E", "~", rawurlencode($signature));
		
		/* create request */
		$request = "http://".$host.$uri."?".$canonicalized_query."&Signature=".$signature;
		write_log("req:".$request,"info");

		/* I prefer using CURL */
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL,$request);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_TIMEOUT, 15);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15); //连接超时
		

		$xml_response = curl_exec($ch);
		
		$curl_errno = curl_errno($ch);
		$curl_error = curl_error($ch);
		if ($curl_errno > 0) { // network error
			write_log("product ads api--network error","error");
			return false;
		}
		
		/* Check for 404 (file not found). */
		$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		
		if ($httpCode <> '200' ) {
			write_log("product ads api--Got error respond code $httpCode from amazon","error");
			return false;
		}	

		if ($xml_response === False)	{
			
			write_log("product ads api--xml_response === False","error");
			return false;
		}
		
		return $xml_response;
	}
	
    public static function getOneMediumImageUrlByASIN($marketplaceId,$asin,$db,$purge=false){
    	
    	//1. 先查找数据库中是否存在指定asin对应的图片url
    	/**
CREATE TABLE IF NOT EXISTS `amazon_product_image` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `asin` varchar(50) CHARACTER SET utf8 NOT NULL COMMENT 'amazon的asin码',
  `medium_image_url` varchar(255) CHARACTER SET utf8 NOT NULL COMMENT '160*160 产品图片url',
  `marketplace_id` varchar(20) CHARACTER SET utf8 NOT NULL,
  `update_time` int(11) NOT NULL COMMENT '最近更新时间,时间戳',
  PRIMARY KEY (`id`),
  KEY `asin` (`asin`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;
    	 */
    	
    	
    	if(!$purge){
    		//由于asin在amazon上唯一存在的，所以这里需要把marketplace_id也作为查询条件。 marketplace_id字段更多是为了后面更新图片url的时候，找到对应的请求amazon的域名
    		$rs = $db->query("SELECT * FROM amazon_product_image where asin='".$asin."' and  marketplace_id='".$marketplaceId."' ");
    		$rows = $rs->fetchAll();
    		 
    		
    		foreach($rows as $row){
    			if(!empty($row["medium_image_url"])) {
    				if(empty($row["medium_image_url"]) || stripos($row["medium_image_url"], "no-img-sm") !== false){
    					$ret=$db->exec('delete from amazon_product_image where id='.$row['id']);
    					return array(false,"no image");
    				}
    				 
    				return array(true,$row["medium_image_url"]);
    			}
    			 
    		}
    	}else{
    		$ret=$db->exec('delete from amazon_product_image where asin="'.$asin.'" and  marketplace_id="'.$marketplaceId.'"');
    	}
    	
    	
    	//2. 通过访问amazon的 product advertising api 获取对应的图片url
		$params = array("Operation"  => "ItemLookup","IdType"   => "ASIN","ItemId"=>$asin,
			            "ResponseGroup" => "Images");

		try{
		    $xml_response=self::_productAdsApiRequest($marketplaceId,$params);
		    $parsed_xml = null;
		    if ($xml_response===false) {
		        write_log("_productAdsApiRequest xml_response===false:", "info");
		    }else{
		    $parsed_xml = @simplexml_load_string($xml_response);	
		    }
		}catch(Exception $e){
			// return array(false,$e->getMessage());			
		    write_log("_productAdsApiRequest Exception:".$e->getMessage(), "error");
		}
		
		if ($parsed_xml<>null and $parsed_xml->Items<>null and $parsed_xml->Items->Item<>null and !empty($parsed_xml->Items->Item->MediumImage))
        {  
    		$urlArr=(array)$parsed_xml->Items->Item->MediumImage->URL;
    		if(stripos($urlArr[0], "no-img-sm") !== false){
    			return array(false,"no image");
    		}
    		$nowTime=time();
    		//保存asin和图片url的信息到数据库
    		$ret=$db->exec('INSERT INTO `amazon_product_image`(`asin`, `medium_image_url`, `marketplace_id`, `update_time`) '.
    				' VALUES ("'.$asin.'","'.$urlArr[0].'","'.$marketplaceId.'",'.$nowTime.')');
    		if ($ret===false) {
    		}
    		
    		
			return array(true,$urlArr[0]); 
		}else{
			// dzt20160412 amazon product advising 接口不能调用。 临时获取图片方案
			$prodInfo = AmazonAPI::getMatchingProduct2(array($asin));
			$url = @$prodInfo['product'][0]['Product']['AttributeSets']['Any'][0]['ns2_SmallImage']['ns2_URL'];
			if(empty($url)){
			    $url = @$prodInfo['product'][0]['Products'][0]['AttributeSets']['Any'][0]['ns2_SmallImage']['ns2_URL'];
			}
			
			if(empty($url)){
				return array(false,"getMatchingProduct2 false");
			}else{
			    
			    // http://ecx.images-amazon.com/images/I/41JtLv2yEcL._SL75_.jpg 替换成160*160的图
			    $url = str_ireplace("._SL75_.", "._SL160_.", $url);
			    
				$nowTime=time();
				//保存asin和图片url的信息到数据库
				$ret=$db->exec('INSERT INTO `amazon_product_image`(`asin`, `medium_image_url`, `marketplace_id`, `update_time`) '.
						' VALUES ("'.$asin.'","'.$url.'","'.$marketplaceId.'",'.$nowTime.')');
				return array(true, $url);
			}
		}
		
		//print_r($parsed_xml->Items->Item->MediumImage->URL);
		
		
		return array(false,"no image");
  	
	}

	
	
	public static function getProductTopOfferByAsin($marketplaceId,$asin,$Operation='ItemLookup',$ResponseGroup='OfferFull' ){
    	//通过访问amazon的 product advertising api 获取ASIN 的offer信息
		$params = array("Operation"  => $Operation,"IdType"   => "ASIN","ItemId"=>$asin,
			            "ResponseGroup" => $ResponseGroup);

		try{
		    $xml_response=self::_productAdsApiRequest($marketplaceId,$params);
		    if ($xml_response===false) return array(false,"xml_response===false");		    
		    $parsed_xml = @simplexml_load_string($xml_response);	
		}catch(Exception $e){
			return array(false,$e->getMessage());			
		}
		
		if ($parsed_xml<>null /*and $parsed_xml->Items<>null and $parsed_xml->Items->Item<>null and !empty($parsed_xml->Items->Item->MediumImage)*/)
        {  
    		//$urlArr=(array)$parsed_xml->Items->Item->MediumImage->URL;
			$info=$parsed_xml;
			return $info; 
		}
		return array(false,"no info");
	}
	
	public static function getProductAttributesByAsin($marketplaceId,$asin,$Operation='ItemLookup',$ResponseGroup='ItemAttributes' ){
    	//通过访问amazon的 product advertising api ASIN 的 Attributes
		$params = array("Operation"  => $Operation,"IdType"   => "ASIN","ItemId"=>$asin,
			            "ResponseGroup" => $ResponseGroup);

		try{
		    $xml_response=self::_productAdsApiRequest($marketplaceId,$params);
		    if ($xml_response===false) return array(false,"xml_response===false");		    
		    $parsed_xml = @simplexml_load_string($xml_response);	
		}catch(Exception $e){
			return array(false,$e->getMessage());			
		}
		
		if ($parsed_xml<>null)
        {  
			$info=$parsed_xml;
			return $info; 
		}
		return array(false,"no info");
	}
	
}//end of AMAZON_API
?>