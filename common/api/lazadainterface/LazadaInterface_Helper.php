<?php
namespace common\api\lazadainterface;

use eagle\modules\listing\helpers\LazadaConfig;
use eagle\modules\util\helpers\TimeUtil;

class LazadaInterface_Helper
{


    /**
     * 通过访问proxy来获取订单列表
     * @param $config --- 用户的认证信息
     * array("userId"=>"23434@qq.com","apiKey"=>"2324234","countryCode"=>"my"),
     * @param $reqParams --- 具体业务的请求参数
     * array("UpdatedAfter"=>"")
     */
    public static function getOrderList($config, $reqParams = array(), $timeout = 60)
    {
        //gmdate("Y-m-d H:i:s",gmmktime()-150)
        $reqParams = array(
            "config" => json_encode($config),
            "action" => "GetOrderList",
            "reqParams" => json_encode($reqParams)

        );
        $ret = LazadaProxyConnectHelper::call_lazada_api($reqParams, $timeout);
        return $ret;
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
        $reqParams = array(
            "config" => json_encode($config),
            "action" => "GetOrderDetail",
            "reqParams" => json_encode($reqParams)

        );
        
        $timeout = 200;// linio mx puid4459 lazada_uid386 Orderid:2905771 订单有100个相同sku的item ，返回超时
        $ret = LazadaProxyConnectHelper::call_lazada_api($reqParams,$timeout);
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
    	$reqParams['DocumentType'] = 'invoice';
    	$allReqParams = array(
    			"config" => json_encode($config),
    			"action" => "GetDocument",
    			"reqParams" => json_encode($reqParams)
    	);
    	$ret = LazadaProxyConnectHelper::call_lazada_api($allReqParams);
    	return $ret;
    }

    /**
     * 通过访问proxy来获取订单列表
     * @param $config --- 用户的认证信息
     * array("userId"=>"23434@qq.com","apiKey"=>"2324234","countryCode"=>"my"),
     * @param $reqParams --- 具体业务的请求参数
     * array(
     * 'ShopSku'=>"GE533TLCKXE1ANID-3194817",
     * 'SellerSku'=>"A006_511_K",
     * 'purge'=>true //是否更新proxy图片缓存
     * )
     *
     */
    public static function getOrderItemImage($config, $reqParams = array())
    {
        $reqParams = array(
            "config" => json_encode($config),
            "action" => "getOrderItemImage",
            "reqParams" => json_encode($reqParams)

        );
        $ret = LazadaProxyConnectHelper::call_lazada_api($reqParams);
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
        $reqParams = array(
                "config" => json_encode($config),
                "action" => "packedByMarketplace",
                "reqParams" => json_encode($reqParams)
        );
    
        $ret = LazadaProxyConnectHelper::call_lazada_api($reqParams);
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
        $reqParams = array(
            "config" => json_encode($config),
            "action" => "shipOrder",
            "reqParams" => json_encode($reqParams)
        );

        $timeout = 200;// lgs上传 出现6000ms 超时 但实际标记成功，所以这里加长一下时间看看，能不能接收到返回。
        $ret = LazadaProxyConnectHelper::call_lazada_api($reqParams,$timeout);
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
        $reqParams = array(
            "config" => json_encode($config),
            "action" => "getShipmentProviders",
            "reqParams" => json_encode($reqParams)

        );
        $ret = LazadaProxyConnectHelper::call_lazada_api($reqParams);
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
        $reqParams = array(
            "config" => json_encode($config),
            "action" => "getCategoryTree",
            "reqParams" => json_encode($reqParams)

        );
        $ret = LazadaProxyConnectHelper::call_lazada_api($reqParams);
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
        $reqParams = array(
            "config" => json_encode($config),
            "action" => "getCategoryAttributes",
            "reqParams" => json_encode($reqParams)

        );
        $ret = LazadaProxyConnectHelper::call_lazada_api($reqParams);
        return $ret;
    }

    /**
     * 通过访问proxy来获取feed信息
     * @param $config --- 用户的认证信息
     * array("userId"=>"23434@qq.com","apiKey"=>"2324234","countryCode"=>"my"),
     * @param $reqParams --- 具体业务的请求参数
     */
    public static function getFeedList($config, $reqParams = array())
    {
        $reqParams = array(
            "config" => json_encode($config),
            "action" => "FeedList",
            "reqParams" => json_encode($reqParams)

        );
        $ret = LazadaProxyConnectHelper::call_lazada_api($reqParams);
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
        set_time_limit(0);
        ignore_user_abort(true);// 获取品牌时间比较长
        $reqParams = array(
            "config" => json_encode($config),
            "action" => "getBrands",
            "reqParams" => json_encode($reqParams)
        );
        $timeout = 200;//获取品牌出现6000ms 超时，加长一下时间看看，能不能接收到返回。
        $ret = LazadaProxyConnectHelper::call_lazada_api($reqParams,$timeout);
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
        $reqParams = array(
            "config" => json_encode($config),
            "action" => "productCreate",
            "reqParams" => json_encode($reqParams)

        );

        $ret = LazadaProxyConnectHelper::call_lazada_api($reqParams, 300);
        
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
        $reqParams = array(
            "config" => json_encode($config),
            "action" => "productUpdate",
            "reqParams" => json_encode($reqParams)

        );

        $ret = LazadaProxyConnectHelper::call_lazada_api($reqParams);

        return $ret;
    }
    
    /**
     * 通过访问proxy来 删除产品  entry v1才有的接口
     * @param $config --- 用户的认证信息
     * array("userId"=>"23434@qq.com","apiKey"=>"2324234","countryCode"=>"my"),
     * @param $reqParams --- 具体业务的请求参数
     * array('products'=>$products);
     */
    public static function productDelete($config, $reqParams = array())
    {
        $reqParams = array(
                "config" => json_encode($config),
                "action" => "productDelete",
                "reqParams" => json_encode($reqParams)
    
        );
    
        $ret = LazadaProxyConnectHelper::call_lazada_api($reqParams);
    
        return $ret;
    }
    
    
    /**
     * 通过访问proxy来 修改产品 entry v2或以上才有的接口
     * @param $config --- 用户的认证信息
     * array("userId"=>"23434@qq.com","apiKey"=>"2324234","countryCode"=>"my"),
     * @param $reqParams --- 具体业务的请求参数
     * array('products'=>$products);
     */
    public static function productUpdatePriceQuantity($config, $reqParams = array())
    {
        $reqParams = array(
                "config" => json_encode($config),
                "action" => "productUpdatePriceQuantity",
                "reqParams" => json_encode($reqParams)
    
        );
    
        $ret = LazadaProxyConnectHelper::call_lazada_api($reqParams);
    
        return $ret;
    }

    /**
     * @deprecated 通过访问proxy来 上传产品图片
     * @param $config --- 用户的认证信息
     * array("userId"=>"23434@qq.com","apiKey"=>"2324234","countryCode"=>"my"),
     * @param $reqParams --- 具体业务的请求参数
     * array('SellerSku'=>'CS8514102609D47-1','Images'=>array('url1','url2'...))
     */
    public static function productImage($config, $reqParams = array())
    {
        $reqParams = array(
            "config" => json_encode($config),
            "action" => "productImage",
            "reqParams" => json_encode($reqParams)

        );
//        if (LazadaConfig::IS_DEBUG) {
//            $ret = array("success" => true, "message" => "in debug mode,not really call api");
//        } else {
            $ret = LazadaProxyConnectHelper::call_lazada_api($reqParams);
//        }
        return $ret;
    }
    
    /**
     * @deprecated 通过访问proxy来 修改多个产品图片  entry v1才有的接口
     * @param $config --- 用户的认证信息
     * array("userId"=>"23434@qq.com","apiKey"=>"2324234","countryCode"=>"my"),
     * @param $reqParams --- 具体业务的请求参数
     * array('SellerSku'=>'CS8514102609D47-1','Images'=>array('url1','url2'...))
     */
    public static function productsImage($config, $reqParams = array())
    {
        $reqParams = array(
                "config" => json_encode($config),
                "action" => "productsImage",
                "reqParams" => json_encode($reqParams)
    
        );
        $ret = LazadaProxyConnectHelper::call_lazada_api($reqParams);
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
        $reqParams = array(
                "config" => json_encode($config),
                "action" => "migrateImage",
                "reqParams" => json_encode($reqParams)
        );
    
//         if (LazadaConfig::IS_DEBUG) {
//             $ret = array("success" => true, "message" => "in debug mode,not really call api");
//         } else {
        $ret = LazadaProxyConnectHelper::call_lazada_api($reqParams);
//         }
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
        $reqParams = array(
            "config" => json_encode($config),
            "action" => "setImage",
            "reqParams" => json_encode($reqParams)
        );
        
//         if (LazadaConfig::IS_DEBUG) {
//             $ret = array("success" => true, "message" => "in debug mode,not really call api");
//         } else {
            $ret = LazadaProxyConnectHelper::call_lazada_api($reqParams);
//         }
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
        //gmdate("Y-m-d H:i:s",gmmktime()-150)
        $reqParams = array(
            "config" => json_encode($config),
            "action" => "getProducts",
            "reqParams" => json_encode($reqParams)

        );
        $ret = LazadaProxyConnectHelper::call_lazada_api($reqParams);
        if (!isset($ret["response"]) or !isset($ret["response"]["products"])) {
            $ret = LazadaProxyConnectHelper::call_lazada_api($reqParams);
        }
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
        $reqParams = array(
            "config" => json_encode($config),
            "action" => "getFilterProducts",
            "reqParams" => json_encode($reqParams)

        );

        $revContentTimeout = 200;
        if ($isFirstTime == 1) {
            $revContentTimeout = 1000;// dzt20160401 lazadaUid:365 第一次拉取老是最后超时。。Operation timed out after 500001 milliseconds with 12275920 bytes received
        }
        $ret = LazadaProxyConnectHelper::call_lazada_api($reqParams, $revContentTimeout);

        // curl timeout 问题,或者因为某些问题导致没有返回allProducts的 , 重试一次 double $revContentTimeout 
        if ((!isset($ret["response"]) && false !== stripos($ret["message"], "local") && false !== stripos($ret["message"], "curl"))) {
            $ret = LazadaProxyConnectHelper::call_lazada_api($reqParams, $revContentTimeout * 2);
        }
        return $ret;
    }

    public static function getFilterProductsV2($config, $reqParams = array())
    {
        $reqParams = array(
            "config" => json_encode($config),
            "action" => "getFilterProducts",
            "reqParams" => json_encode($reqParams)

        );

        $revContentTimeout = 1000;// dzt20160401 lazadaUid:365 第一次拉取老是最后超时。。Operation timed out after 500001 milliseconds with 12275920 bytes received
        $ret = LazadaProxyConnectHelper::call_lazada_api_v2($reqParams, $revContentTimeout);

        // curl timeout 问题,或者因为某些问题导致没有返回allProducts的 , 重试一次 double $revContentTimeout
        if ((!isset($ret["response"]) && false !== stripos($ret["message"], "local") && false !== stripos($ret["message"], "curl"))) {
            $ret = LazadaProxyConnectHelper::call_lazada_api_v2($reqParams, $revContentTimeout * 2);
        }
        return $ret;
    }

    /**
     * 获取指定feedid的执行结果
     * @param unknown $config
     * @param unknown $reqParams
     * array("FeedID"=>"234234-345345-335")
     * @return string
     */
    public static function getFeedStatus($config, $reqParams = array())
    {
        \Yii::info("getFeedStatus start feedId:" . $reqParams['FeedID'], "file");

        $reqParams = array(
            "config" => json_encode($config),
            "action" => "getFeedDetail",
            "reqParams" => json_encode($reqParams)

        );

        $ret = LazadaProxyConnectHelper::call_lazada_api($reqParams);
        \Yii::info("getFeedStatus end feedId:" . $reqParams['FeedID'], "file");
        \Yii::info("getFeedStatus end ===>feedId:" . $reqParams['FeedID'] . ";result:" . print_r($ret, true), "file");

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
        $reqParams = array(
            "config" => json_encode($config),
            "action" => "getQcStatus",
            "reqParams" => json_encode($reqParams)
        );
    
        $ret = LazadaProxyConnectHelper::call_lazada_api($reqParams);
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
        if(empty($reqParams['DocumentType'])){
            $reqParams['DocumentType'] = 'shippingLabel';
        }
        
    	$allReqParams = array(
    			"config" => json_encode($config),
    			"action" => "GetDocument",
    			"reqParams" => json_encode($reqParams)
    	);
    	$ret = LazadaProxyConnectHelper::call_lazada_api($allReqParams);
    	return $ret;
    }
}