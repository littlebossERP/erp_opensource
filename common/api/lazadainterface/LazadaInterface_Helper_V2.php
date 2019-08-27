<?php 
namespace common\api\lazadainterface;

use eagle\models\SaasLazadaUser;
use eagle\modules\util\helpers\TimeUtil;
class LazadaInterface_Helper_V2{
    // 开发者帐号 
    // TODO lazada dev account
	static $APP_KEY = null;
	static $APP_SECRET = '';
	static $APP_NAME = '';
	/**
	 * 第一步请求 授权
	 */ 
	function getAuthUrl($state='state',$redirect_uri=''){
		$params = array(
				'response_type' => 'code', // 此值固定为“code”
				'redirect_uri' => $redirect_uri, // 授权后要回调的URI，即接收Authorization Code的URI。
		        'client_id' => self::$APP_KEY, // 创建应用时获得的App Key
				'state' => $state, // 用于保持请求和回调的状态，防止第三方应用受到CSRF攻击。授权服务器在回调时（重定向用户浏览器到“redirect_uri”时），会原样回传该参数
// 				'force_login' => 0, // 是否强制登录；force_login=1 强制登陆；不强制登录，无需此参数，默认为0	
// 				'display' => 'page', // 登录和授权页面的展现样式，默认为page；page: 适用于web应用；mobile: 适用于手机等智能移动终端应用
// 				'scope' => 'basic'
		);
			
		$url =  "https://auth.lazada.com/oauth/authorize?";
		$temp = '';
		foreach($params as $key=>$value){
		    if(empty($temp)){
		        $temp .= $key."=".urlencode(trim($value));
		    }else{
		        $temp .= "&".$key."=".urlencode(trim($value));
		    }
		}
		
		return $url.$temp;
	}
#####################################################################################
	/**
	 * 第二步使用authorization_code获取refresh_token 和 access_token 
	 */
	function getToken($code=''){
	    $config = [
	        'countryCode'=>'my',
	        'APP_KEY'=>self::$APP_KEY,
	        'APP_SECRET'=>self::$APP_SECRET,
	        'userId'=>'',
	        'apiKey'=>'',
	    ];//获取access_token不需要国家。暂时先固定一个
	    $reqParams = [
	        'code'=>$code,
	    ];
		$params = array(
			"config" => json_encode($config),
            "action" => "/auth/token/create",
            "reqParams" => json_encode($reqParams)
		);
		
		$response = LazadaProxyConnectHelperV2::call_lazada_api($params);
		
		return $response;
	}
#####################################################################################
	/**
	 * 第三步根据长时令牌RefreshToken重新获取访问令牌AccessToken
	 */
	function refreshTokentoAccessToken($refresh_token=''){
	    $config = [
	        'countryCode'=>'my',
	        'APP_KEY'=>self::$APP_KEY,
	        'APP_SECRET'=>self::$APP_SECRET,
	        'userId'=>'',
	        'apiKey'=>'',
	    ];//获取access_token不需要国家。暂时先固定一个
	    $reqParams = [
	        'refresh_token'=>$refresh_token,
	    ];
		$params = array(
			"config" => json_encode($config),
            "action" => "/auth/token/refresh",
            "reqParams" => json_encode($reqParams)
		);
		
		$response = LazadaProxyConnectHelperV2::call_lazada_api($params);
		
		return $response;
	}
	
#####################################################################################
	/**
	 * 获取accessToken
	 */
	function getAccessToken($lazada_uid , $force_refresh_atoken = false){
		$SDU_obj = SaasLazadaUser::findOne(['lazada_uid'=>$lazada_uid]);
		if(!$force_refresh_atoken && strlen( $SDU_obj->access_token ) > 0 && time() < $SDU_obj->access_token_timeout ){
			return $SDU_obj->access_token;
		}
		if( $SDU_obj->refresh_token_timeout > time() + 36000 ){ // @todo要不要提前 认为 refresh token 过期 而不获取access token?
			$r = $this->refreshTokentoAccessToken($SDU_obj->refresh_token);
			if(isset($r['access_token'])){
				$SDU_obj->access_token = $r['access_token'];
				$SDU_obj->access_token_timeout = floor($r['expires_in'] / 1000);// 敦煌是24小时过期 ，@todo 这里写要控制更短的时间过期？
				$SDU_obj->save();
				return $r['access_token'];
			}else {
				return false;
			}
		}
		return false;
	}

#####################################################################################
	/**
	 * 检测是否绑定token并且是否过期
	 */
	static function checkToken($lazada_uid){
		$SDU_obj = SaasLazadaUser::findOne(['lazada_uid'=>$lazada_uid]);
		if (isset($SDU_obj)){
			if($SDU_obj->refresh_token_timeout > time()){
				return true;
			}else{
				return false;
			}
		}else{
			return false;
		}
	}
	
	/**
	 * 通过访问proxy来获取订单列表
	 * @param $config --- 用户的认证信息
	 * array("userId"=>"23434@qq.com","apiKey"=>"2324234","countryCode"=>"my"),
	 * @param $reqParams --- 具体业务的请求参数
	 * array("OrderId"=>"23333")  
	 */
	public static function getOrder($config, $reqParams = array())
	{
	    $config['APP_KEY'] = self::$APP_KEY;
	    $config['APP_SECRET'] = self::$APP_SECRET;
	    $reqParams = array(
	        "config" => json_encode($config),
	        "action" => "/order/get",
	        "reqParams" => json_encode($reqParams)
	    );
	    $timeout = 200;// linio mx puid4459 lazada_uid386 Orderid:2905771 订单有100个相同sku的item ，返回超时
	    $response = LazadaProxyConnectHelperV2::call_lazada_api($reqParams,$timeout);
	    return $response;
	}
	
	/**
	 * 通过访问proxy来获取订单列表
	 * @param $config --- 用户的认证信息
	 * array("userId"=>"23434@qq.com","apiKey"=>"2324234","countryCode"=>"my"),
	 * @param $reqParams --- 具体业务的请求参数
	 * array("UpdatedAfter"=>"")
	 */
	public static function getOrderList($config, $reqParams = array(), $timeout = 60)
	{
	    $config['APP_KEY'] = self::$APP_KEY;
	    $config['APP_SECRET'] = self::$APP_SECRET;
	    $reqParams = array(
	        "config" => json_encode($config),
	        "action" => "/orders/get",
	        "reqParams" => json_encode($reqParams)
	    );
	    $response = LazadaProxyConnectHelperV2::call_lazada_api($reqParams, $timeout);
	    return $response;
	}
	
	/**
	 * 通过访问proxy来获取订单列表
	 * @param $config --- 用户的认证信息
	 * array("userId"=>"23434@qq.com","apiKey"=>"2324234","countryCode"=>"my"),
	 * @param $reqParams --- 具体业务的请求参数
	 * array("OrderId"=>"234234,445,23333")  orderId之间用逗号隔开
	 */
	public static function getOrderItems($config, $reqParams = array())
	{
	    $config['APP_KEY'] = self::$APP_KEY;
	    $config['APP_SECRET'] = self::$APP_SECRET;
	    $reqParams = array(
	        "config" => json_encode($config),
	        "action" => "/orders/items/get",
	        "reqParams" => json_encode($reqParams)
	    );
	
	    $timeout = 200;
	    $ret = LazadaProxyConnectHelperV2::call_lazada_api($reqParams,$timeout);
	    return $ret;
	}
	
	/**
	 * 通过访问proxy来确认发货
	 * @param $config --- 用户的认证信息
	 * array("userId"=>"23434@qq.com","apiKey"=>"2324234","countryCode"=>"my"),
	 * @param $reqParams --- 具体业务的请求参数
	 * array (
	 *        'OrderItemIds' => "OrderItemId1,OrderItemId2", // 订单下的多个 OrderItemIds。格式为：OrderItemIds1,OrderItemIds2的字符串发送到proxy
	 *        'DeliveryType' => , "dropship" // "dropship" , "pickup" , "send_to_warehouse"
	 *        'ShippingProvider' => "AS-4PX-Postal-Singpost" // lazada支持的运输服务
	 *        'TrackingNumber' => "1Z68A9X70467731838"// 运单号。对应UPS的运单号；示例值：1Z68A9X70467731838
	 *    );
	 */
	public static function shipOrder($config, $reqParams = array())
	{
	    $config['APP_KEY'] = self::$APP_KEY;
	    $config['APP_SECRET'] = self::$APP_SECRET;
	    $reqParams = array(
	        "config" => json_encode($config),
	        "action" => "/order/rts",
	        "reqParams" => json_encode($reqParams)
	    );
	
	    $timeout = 200;// lgs上传 出现6000ms 超时 但实际标记成功，所以这里加长一下时间看看，能不能接收到返回。
	    $ret = LazadaProxyConnectHelperV2::call_lazada_api($reqParams,$timeout);
	    return $ret;
	}
	
	/**
	 * 获取跟踪号
	 * @param $config --- 用户的认证信息
	 * array("userId"=>"23434@qq.com","apiKey"=>"2324234","countryCode"=>"my"),
	 * @param $reqParams --- 具体业务的请求参数
	 * array (
	 *        'OrderItemIds' => "OrderItemId1,OrderItemId2", // 订单下的多个 OrderItemIds。格式为：OrderItemIds1,OrderItemIds2的字符串发送到proxy
	 *        'DeliveryType' => , "dropship" // "dropship" , "pickup" , "send_to_warehouse"
	 *        'ShippingProvider' => "AS-4PX-Postal-Singpost" // lazada支持的运输服务
	 *    );
	 */
	public static function packedByMarketplace($config, $reqParams = array())
	{
	    $config['APP_KEY'] = self::$APP_KEY;
	    $config['APP_SECRET'] = self::$APP_SECRET;
	    $reqParams = array(
	        "config" => json_encode($config),
	        "action" => "/order/pack",
	        "reqParams" => json_encode($reqParams)
	    );
	
	    $ret = LazadaProxyConnectHelperV2::call_lazada_api($reqParams);
	    return $ret;
	}
	
	/**
	 * 通过访问proxy来获取客户运输方式
	 * @param $config --- 用户的认证信息
	 * array("userId"=>"23434@qq.com","apiKey"=>"2324234","countryCode"=>"my"),
	 * @param $reqParams --- 具体业务的请求参数
	 */
	public static function getShipmentProviders($config, $reqParams = array())
	{
	    $config['APP_KEY'] = self::$APP_KEY;
	    $config['APP_SECRET'] = self::$APP_SECRET;
	    $reqParams = array(
	        "config" => json_encode($config),
	        "action" => "/shipment/providers/get",
	        "reqParams" => json_encode($reqParams)
	
	    );
	    $ret = LazadaProxyConnectHelperV2::call_lazada_api($reqParams);
	    return $ret;
	}
	
	/**
	 * 通过访问proxy来获取订单面单
	 * @param $config --- 用户的认证信息
	 * array("userId"=>"23434@qq.com","apiKey"=>"2324234","countryCode"=>"my"),
	 * @param $reqParams --- 具体业务的请求参数
	 * array("OrderItemIds"=>"234234,445,23333")  OrderItemId之间用逗号隔开
	 */
	public static function getOrderShippingLabel($config, $reqParams = array())
	{
	    $config['APP_KEY'] = self::$APP_KEY;
	    $config['APP_SECRET'] = self::$APP_SECRET;
	    $reqParams['DocumentType'] = 'shippingLabel';
	    $allReqParams = array(
	        "config" => json_encode($config),
	        "action" => "/order/document/get",
	        "reqParams" => json_encode($reqParams)
	    );
	    $ret = LazadaProxyConnectHelperV2::call_lazada_api($allReqParams);
	    return $ret;
	}
	
	/**
	 * 通过访问proxy来获取 品牌信息
	 * @param $config --- 用户的认证信息
	 * array("userId"=>"23434@qq.com","apiKey"=>"2324234","countryCode"=>"my"),
	 * @param $reqParams --- 具体业务的请求参数
	 */
	public static function getBrands($config, $reqParams = array())
	{
	    $config['APP_KEY'] = self::$APP_KEY;
	    $config['APP_SECRET'] = self::$APP_SECRET;
	    set_time_limit(0);
	    ignore_user_abort(true);// 获取品牌时间比较长
	    $reqParams = array(
	        "config" => json_encode($config),
	        "action" => "/brands/get",
	        "reqParams" => json_encode($reqParams)
	    );
	    $timeout = 200;//获取品牌出现6000ms 超时，加长一下时间看看，能不能接收到返回。
	    $ret = LazadaProxyConnectHelperV2::call_lazada_api($reqParams,$timeout);
	    return $ret;
	}
	
	/**
	 * 通过访问proxy来获取目录树
	 * @param $config --- 用户的认证信息
	 * array("userId"=>"23434@qq.com","apiKey"=>"2324234","countryCode"=>"my"),
	 * @param $reqParams --- 具体业务的请求参数
	 */
	public static function getCategoryTree($config, $reqParams = array())
	{
	    $config['APP_KEY'] = self::$APP_KEY;
	    $config['APP_SECRET'] = self::$APP_SECRET;
	    $reqParams = array(
	        "config" => json_encode($config),
	        "action" => "/category/tree/get",
	        "reqParams" => json_encode($reqParams)
	
	    );
	    $ret = LazadaProxyConnectHelperV2::call_lazada_api($reqParams);
	    return $ret;
	}
	
	/**
	 * 通过访问proxy来获取目录属性
	 * @param $config --- 用户的认证信息
	 * array("userId"=>"23434@qq.com","apiKey"=>"2324234","countryCode"=>"my"),
	 * @param $reqParams --- 具体业务的请求参数
	 * array("PrimaryCategory"=>"22")
	 */
	public static function getCategoryAttributes($config, $reqParams = array())
	{
	    $config['APP_KEY'] = self::$APP_KEY;
	    $config['APP_SECRET'] = self::$APP_SECRET;
	    $reqParams = array(
	        "config" => json_encode($config),
	        "action" => "/category/attributes/get",
	        "reqParams" => json_encode($reqParams)
	
	    );
	    $ret = LazadaProxyConnectHelperV2::call_lazada_api($reqParams);
	    return $ret;
	}
	
	/**
	 * 通过访问proxy来获取listing列表
	 * @param $config --- 用户的认证信息
	 * array("userId"=>"23434@qq.com","apiKey"=>"2324234","countryCode"=>"my"),
	 * @param $reqParams --- 具体业务的请求参数
	 * array("UpdatedAfter"=>"")
	 */
	public static function getProducts($config, $reqParams = array())
	{
	    $config['APP_KEY'] = self::$APP_KEY;
	    $config['APP_SECRET'] = self::$APP_SECRET;
	    $reqParams = array(
	        "config" => json_encode($config),
	        "action" => "/products/get",
	        "reqParams" => json_encode($reqParams)
	
	    );
	    $ret = LazadaProxyConnectHelperV2::call_lazada_api($reqParams);
	    if (!isset($ret["response"]) or !isset($ret["response"]["products"])) {
	        $ret = LazadaProxyConnectHelperV2::call_lazada_api($reqParams);
	    }
	    return $ret;
	}
	
	/**
	 * 获取产品qc状态，了解产品发布情况
	 * @param $config --- 用户的认证信息
	 * array("userId"=>"23434@qq.com","apiKey"=>"2324234","countryCode"=>"my"),
	 * @param $reqParams --- 具体业务的请求参数
	 * array("SkuSellerList"=>"123,234,345")
	 * @return string
	 */
	public static function getQcStatus($config, $reqParams = array())
	{
	    $config['APP_KEY'] = self::$APP_KEY;
	    $config['APP_SECRET'] = self::$APP_SECRET;
	    $reqParams = array(
	        "config" => json_encode($config),
	        "action" => "/product/qc/status/get",
	        "reqParams" => json_encode($reqParams)
	    );
	
	    $ret = LazadaProxyConnectHelperV2::call_lazada_api($reqParams);
	    return $ret;
	}
	
	/**
	 * 通过访问proxy来获取listing列表
	 * @param $config --- 用户的认证信息
	 * array("userId"=>"23434@qq.com","apiKey"=>"2324234","countryCode"=>"my"),
	 * @param $reqParams --- 具体业务的请求参数
	 * array("UpdatedAfter"=>"")
	 */
	public static function getFilterProducts($config, $reqParams = array(), $isFirstTime = 0)
	{
	    $config['APP_KEY'] = self::$APP_KEY;
	    $config['APP_SECRET'] = self::$APP_SECRET;
	    $reqParams = array(
	        "config" => json_encode($config),
	        "action" => "/filter/products/get",
	        "reqParams" => json_encode($reqParams)
	
	    );
	
	    $revContentTimeout = 200;
	    if ($isFirstTime == 1) {
	        $revContentTimeout = 1000;// dzt20160401 lazadaUid:365 第一次拉取老是最后超时。。Operation timed out after 500001 milliseconds with 12275920 bytes received
	    }
	    $ret = LazadaProxyConnectHelperV2::call_lazada_api($reqParams, $revContentTimeout);
	
	    // curl timeout 问题,或者因为某些问题导致没有返回allProducts的 , 重试一次 double $revContentTimeout
	    if ((!isset($ret["response"]) && false !== stripos($ret["message"], "local") && false !== stripos($ret["message"], "curl"))) {
	        $ret = LazadaProxyConnectHelperV2::call_lazada_api($reqParams, $revContentTimeout * 2);
	    }
	    return $ret;
	}
	
	public static function getFilterProductsV2($config, $reqParams = array())
	{
	    $config['APP_KEY'] = self::$APP_KEY;
	    $config['APP_SECRET'] = self::$APP_SECRET;
	    $reqParams = array(
	        "config" => json_encode($config),
	        "action" => "/filter/products/get",
	        "reqParams" => json_encode($reqParams)
	
	    );
	
	    $revContentTimeout = 1000;// dzt20160401 lazadaUid:365 第一次拉取老是最后超时。。Operation timed out after 500001 milliseconds with 12275920 bytes received
	    $ret = LazadaProxyConnectHelperV2::call_lazada_api_v2($reqParams, $revContentTimeout);
	
	    // curl timeout 问题,或者因为某些问题导致没有返回allProducts的 , 重试一次 double $revContentTimeout
	    if ((!isset($ret["response"]) && false !== stripos($ret["message"], "local") && false !== stripos($ret["message"], "curl"))) {
	        $ret = LazadaProxyConnectHelperV2::call_lazada_api_v2($reqParams, $revContentTimeout * 2);
	    }
	    return $ret;
	}
	
	/**
	 * 通过访问proxy来获取订单列表
	 * @param $config --- 用户的认证信息
	 * array("userId"=>"23434@qq.com","apiKey"=>"2324234","countryCode"=>"my"),
	 * @param $reqParams --- 具体业务的请求参数
	 * array("OrderItemIds"=>"234234,445,23333")  OrderItemId之间用逗号隔开
	 */
	public static function getOrderInvoice($config, $reqParams = array())
	{
	    $config['APP_KEY'] = self::$APP_KEY;
	    $config['APP_SECRET'] = self::$APP_SECRET;
	    $reqParams['DocumentType'] = 'invoice';
	    $allReqParams = array(
	        "config" => json_encode($config),
	        "action" => "/order/document/get",
	        "reqParams" => json_encode($reqParams)
	    );
	    $ret = LazadaProxyConnectHelperV2::call_lazada_api($allReqParams);
	    return $ret;
	}
	
	/**
	 * 通过访问proxy来 创建产品
	 * @param $config --- 用户的认证信息
	 * array("userId"=>"23434@qq.com","apiKey"=>"2324234","countryCode"=>"my"),
	 * @param $reqParams --- 具体业务的请求参数
	 * array('products'=>$products);
	 */
	public static function productCreate($config, $reqParams = array())
	{
	    $config['APP_KEY'] = self::$APP_KEY;
	    $config['APP_SECRET'] = self::$APP_SECRET;
	    $reqParams = array(
	        "config" => json_encode($config),
	        "action" => "/product/create",
	        "reqParams" => json_encode($reqParams)
	
	    );
	
	    $ret = LazadaProxyConnectHelperV2::call_lazada_api($reqParams);
	
	    return $ret;
	}
	
	/**
	 * 通过访问proxy来 修改产品
	 * @param $config --- 用户的认证信息
	 * array("userId"=>"23434@qq.com","apiKey"=>"2324234","countryCode"=>"my"),
	 * @param $reqParams --- 具体业务的请求参数
	 * array('products'=>$products);
	 */
	public static function productUpdate($config, $reqParams = array())
	{
	    $config['APP_KEY'] = self::$APP_KEY;
	    $config['APP_SECRET'] = self::$APP_SECRET;
	    $reqParams = array(
	        "config" => json_encode($config),
	        "action" => "/product/update",
	        "reqParams" => json_encode($reqParams)
	
	    );
	
	    $ret = LazadaProxyConnectHelperV2::call_lazada_api($reqParams);
	
	    return $ret;
	}
	
	/**
	 * 通过访问proxy来 修改产品
	 * @param $config --- 用户的认证信息
	 * array("userId"=>"23434@qq.com","apiKey"=>"2324234","countryCode"=>"my"),
	 * @param $reqParams --- 具体业务的请求参数
	 * array('products'=>$products);
	 */
	public static function productUpdatePriceQuantity($config, $reqParams = array())
	{
	    $config['APP_KEY'] = self::$APP_KEY;
	    $config['APP_SECRET'] = self::$APP_SECRET;
	    $reqParams = array(
	        "config" => json_encode($config),
	        "action" => "/product/price_quantity/update",
	        "reqParams" => json_encode($reqParams)
	
	    );
	
	    $ret = LazadaProxyConnectHelperV2::call_lazada_api($reqParams);
	
	    return $ret;
	}
	
	/**
	 * 通过访问proxy来 上传产品图片到lazada ，获取回改图片的lazada图片链接
	 * @param $config --- 用户的认证信息
	 * array("userId"=>"23434@qq.com","apiKey"=>"2324234","countryCode"=>"my"),
	 * @param $reqParams --- 具体业务的请求参数
	 * array('url'=>'url')
	 *
	 */
	public static function migrateImage($config, $reqParams = array())
	{
	    $config['APP_KEY'] = self::$APP_KEY;
	    $config['APP_SECRET'] = self::$APP_SECRET;
	    $reqParams = array(
	        "config" => json_encode($config),
	        "action" => "/image/migrate",
	        "reqParams" => json_encode($reqParams)
	    );
	
	    $ret = LazadaProxyConnectHelperV2::call_lazada_api($reqParams);
	    
	    return $ret;
	}
	
	/**
	 * 通过访问proxy来 上传产品图片
	 * @param $config --- 用户的认证信息
	 * array("userId"=>"23434@qq.com","apiKey"=>"2324234","countryCode"=>"my"),
	 * @param $reqParams --- 具体业务的请求参数
	 * array('Skus'=>array(0=>array('SellerSku'=>'CS8514102609D47-1','Images'=>array('url1','url2'...))))
	 *
	 */
	public static function setImage($config, $reqParams = array())
	{
	    $config['APP_KEY'] = self::$APP_KEY;
	    $config['APP_SECRET'] = self::$APP_SECRET;
	    $reqParams = array(
	        "config" => json_encode($config),
	        "action" => "/images/set",
	        "reqParams" => json_encode($reqParams)
	    );
	
	    $ret = LazadaProxyConnectHelperV2::call_lazada_api($reqParams);
	    return $ret;
	}
	
	/**
	 * lazada 安全审核需要 登录成功/失败 调用该接口
	 */
	public static function DataMoatLogin($params=array()){
	    $reqParams = array(
            "action" => "/datamoat/login",
            
	    );
	    
	    // userId
	    // peter
	    // The account which the Lazada Seller uses to login to your app (Note: the format of the account depends on the setup of your app)
	    if(empty($params['userId']))
	        return array(false, "userId is Required.");
	    
	    // tid
	    // peter@seller.com
	    // Unique information which Lazada can identify who the seller is e.g. seller's main email address linked to the Lazada seller's seller account, short code
	    if(empty($params['tid']))
	        return array(false, "tid is Required.");
	    
	    // userIp
	    // 212.68.135.22
	    // The Lazada seller's source IP address for this access request
	    if(empty($params['userIp']))
	        return array(false, "userIp is Required.");
	    
	    // ati
	    // 202cb962ac59075b964b07152d234b70
	    // The javascript code from Device Fingeprint will automatically generate an ati parameter value in the cookie
	    if(empty($params['ati']))
	        return array(false, "ati is Required.");
	    
	    // loginResult
	    // fail
	    // Set it to success if login is successful and fail if login is unsuccessful
	    if(empty($params['loginResult']))
	        return array(false, "loginResult is Required.");
	    
	    // loginMessage
	    // password is not corret
	    // Other information such as reasons for the failed attempts
	    if(empty($params['loginMessage']))
	        return array(false, "loginMessage is Required.");
	    
	    $reqParams = array_merge($reqParams, $params);
	    $rtn = self::_callLazadaRestApi($reqParams);
	    \Yii::info(__FUNCTION__.":param:".json_encode($reqParams)."======result:".json_encode($rtn), "file");
	}
	
	
	/**
	 * lazada 安全审核需要Get risk score for this login event
	 * 调用成个则返回lazada接口返回的risk value.
	 */
	public static function DataMoatComputeRisk($params=array()){
	    $reqParams = array(
            "action" => "/datamoat/compute_risk",
	    );
	    
	    // userId
	    // peter
	    // The account which the Lazada Seller uses to login to your app (Note: the format of the account depends on the setup of your app)
	    if(empty($params['userId']))
	        return array(false, "userId is Required.");

	    // userIp
	    // 11.163.1.160
	    // The Lazada seller's source IP address for this access request
	    if(empty($params['userIp']))
	        return array(false, "userIp is Required.");
	    
	    // ati
	    // 0ca175b9c0f726a831d895e269332461
	    // The javascript code from Device Fingeprint will automatically generate an ati parameter value in the cookie
	    if(empty($params['ati']))
	        return array(false, "ati is Required.");
	    
	    $reqParams = array_merge($reqParams, $params);
	    $rtn = self::_callLazadaRestApi($reqParams);
	    \Yii::info(__FUNCTION__.":param:".json_encode($reqParams)."======result:".json_encode($rtn), "file");
	    if(!empty($rtn['success'])){
	         if(!empty($rtn['response']['result']['success'])){
	             return array(true, $rtn['response']['result']['risk']);
	         }else{
	             return array(false, $rtn['response']['result']['msg']);
	         }
	    }else{
	         return array(false, $rtn['message']);
	    }
	}
	
	
	private static function _callLazadaRestApi($getParameters=array(),$postParameters=false){
	    $rtn['success'] = true;  //跟lazada之间的网站是否ok
	    $rtn['message'] = '';
	
	    $app_key=self::$APP_KEY;
	    $app_secret = self::$APP_SECRET;
	    $url= 'api.lazada.com/rest'.$getParameters['action']."?";//以前的action，改为/brand/get之类的接口
	
        $getParameters['timestamp'] = TimeUtil::getCurrentTimestampMS();
	    $getParameters['time'] = TimeUtil::getCurrentTimestampMS();
	    $getParameters['appName'] = self::$APP_NAME;
	    
	    $getParameters['app_key'] = $app_key;
	    $getParameters['sign_method'] = 'sha256';
	
	    //获取sign的字符串链接
	    $stringToBeSigned = '';
	    $stringToBeSigned .= $getParameters['action'];
	    unset($getParameters['action']);
	
	
	    if(!empty($postParameters)){//POST的时候，需要组合sign
	        $getSign = array_merge($getParameters, $postParameters);
	    }else{
	        $getSign = $getParameters;
	    }
	
	
	    ksort($getParameters);
	    ksort($getSign);
	    $params = array();
	
	    foreach ($getSign as $name => $value) {
	        $params[] = rawurlencode($name) . '=' . rawurlencode($value);
	        $stringToBeSigned .= $name.$value;
	    }
	    // Compute signature and add it to the parameters
	    $getParameters['sign'] = strtoupper(hash_hmac('sha256', $stringToBeSigned, $app_secret));
	    $queryString = http_build_query($getParameters, '', '&', PHP_QUERY_RFC3986);
	    // Open Curl connection
	    $ch = curl_init();
	
	    $port = null;
	    $scheme = "";
	    if(true){
	        $scheme = 'https://';
	        $port = 443;
	    } else {
	        $scheme = 'http://';
	        $port = 80;
	    }
	    
	    curl_setopt($ch, CURLOPT_URL, $scheme.$url.$queryString);
	    curl_setopt($ch, CURLOPT_PORT, $port);
	
	    if(!empty($postParameters)){
	        // 			write_log("set post:".$postParameters,"info");
	        curl_setopt($ch, CURLOPT_POST, 1);
	        curl_setopt($ch, CURLOPT_POSTFIELDS, $postParameters );
	    }
	
	    curl_setopt($ch, CURLOPT_TIMEOUT, 100);
	    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15); //连接超时
	
	    // Save response to the variable $data
	    curl_setopt($ch, CURLOPT_FOLLOWLOCATION,1);
	    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
	    $data = curl_exec($ch);
	
	    $curl_errno = curl_errno($ch);
	    $curl_error = curl_error($ch);
	    if ($curl_errno > 0) { // network error
	        $rtn['message']="cURL Error $curl_errno : $curl_error";
	        $rtn['success'] = false ;
	        $rtn['response'] = "";
	        $rtn['state_code'] = $curl_error;
	        curl_close($ch);
	        return $rtn;
	    }
	
	    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	
	    //echo $httpCode.$response."\n";
	    if ($httpCode == '200' ){
	
	        if (false){	// strtolower($getParameters['Format']) == 'xml' lazada接口返回一定是json了
	            $rtn['response'] = json_decode(json_encode((array) simplexml_load_string($data)), true);
	        } else
	            $rtn['response'] = json_decode($data , true);
	        $rtn['state_code'] = 200;
	        if ($rtn['response']==null){
	            // json_decode fails
	            $rtn['message'] = "Content return from lazada is not in json format.";
	            $rtn['success'] = false ;
	            $rtn['state_code'] = 500;
	        }
	    }else{ // network error
	        $rtn['message'] = "Failed for ".$getParameters["action"]." , Got error respond code $httpCode from local.";
	        $rtn['success'] = false ;
	        $rtn['response'] = "";
	        $rtn['state_code'] = $httpCode;
	    }
	    curl_close($ch);// Close Curl connection
	    return $rtn;
	}
}