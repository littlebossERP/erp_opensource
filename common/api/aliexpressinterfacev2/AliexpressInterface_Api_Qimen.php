<?php
namespace common\api\aliexpressinterfacev2;

class AliexpressInterface_Api_Qimen extends AliexpressInterface_Base_Qimen{

	private static $logistics = 'aliexpress.logistics.';
	private static $trade = 'aliexpress.trade.';
	private static $postproduct = 'aliexpress.postproduct.';
	private static $message = 'aliexpress.message.';
	private static $appraise = 'aliexpress.appraise.';
	private static $taobao_tmc_user = 'taobao.tmc.user.';
	private static $taobao_tmc_messages = 'taobao.tmc.messages.';

    /**
     * 获取开展国内物流业务的物流公司
     */
    function qureywlbdomesticlogisticscompany($param=null){
    	return $this->request(__FUNCTION__, $param, 1, self::$logistics);
    }
    
    /**
     * 查询单个订单详情
     */
    function findOrderById($param=null){
    	return $this->request(__FUNCTION__, $param, 1, self::$trade);
    }
    
    /**
     * 查询订单列表
     */
    function findOrderListQuery($param=null){
    	return $this->request(__FUNCTION__, $param, 1, self::$trade);
    }
    
    /**
     * 查询物流追踪信息
     */
    function queryTrackingResult($param=null){
    	return $this->request(__FUNCTION__, $param, 1, self::$logistics);
    }
    
    /**
     * 	查询物流订单信息
     */
    function queryLogisticsOrderDetail($param=null){
    	return $this->request(__FUNCTION__, $param, 1, self::$logistics);
    }
    
    /**
     * 根据请求地址的类型：发货地址信息，揽收地址信息，返回相应的地址列表
     */
    function getLogisticsSellerAddresses($param=null){
    	return $this->request(__FUNCTION__, $param, 1, self::$logistics);
    }
    
    /**
     * 获取线上发货标签
     */
    function getPrintInfo($param=null){
    	return $this->request2(__FUNCTION__, $param, 1, self::$logistics);
    }
    
    /**
     * 列出平台所支持的物流服务列表
     */
    function listLogisticsService($param=null){
    	return $this->request(__FUNCTION__, $param, 1, self::$logistics);
    }
    
    /**
     * 声明发货接口
     */
    function sellershipmentfortop($param=null){
    	return $this->request(__FUNCTION__, $param, 1, self::$logistics);
    }
    
    /**
     * 修改声明发货
     */
    function sellermodifiedshipmentfortop($param=null){
    	return $this->request(__FUNCTION__, $param, 1, self::$logistics);
    }
    
    /**
     * 创建线上发货物流订单
     */
    function createWarehouseOrder($param=null){
    	return $this->request2(__FUNCTION__, $param, 1, self::$logistics, 'official');
    }
    
    /**
     * 面单云打印
     */
    function getPdfsByCloudPrint($param=null){
    	return $this->request2(__FUNCTION__, $param, 1, self::$logistics, 'official');
    }
    
    /**
     * 获取单个产品信息
     */
    function findAeProductById($param=null){
    	return $this->request(__FUNCTION__, $param, 1, self::$postproduct, 'custom');
    }
    
    /**
     * 商品列表查询接口
     */
    function findProductInfoListQuery($param=null){
    	return $this->request(__FUNCTION__, $param, 1, self::$postproduct, 'custom');
    }
    
    /**
     * V2.0新增站内信/订单留言
     */
    function addMsg($param=null){
    	return $this->request(__FUNCTION__, $param, 1, self::$message, 'custom');
    }
    
    /**
     * 根据买家ID获取站内信对话ID
     */
    function queryMsgChannelIdByBuyerId($param=null){
    	return $this->request(__FUNCTION__, $param, 1, self::$message, 'custom');
    }
    
    /**
     * V2.0站内信/订单留言获取关系列表
     */
    function queryMsgRelationList($param=null){
    	return $this->request(__FUNCTION__, $param, 1, self::$message, 'custom');
    }
    
    /**
     * V2.0站内信/订单留言查询详情列表
     */
    function queryMsgDetailList($param=null){
    	return $this->request(__FUNCTION__, $param, 1, self::$message, 'custom');
    }
    
    /**
     * 延长买家收货时间 
     */
    function extendsBuyerAcceptGoodsTime($param=null){
    	return $this->request(__FUNCTION__, $param, 1, self::$trade, 'custom');
    }
    
    /**
     * 卖家对未评价的订单进行评价 
     */
    function saveSellerFeedback($param=null){
    	return $this->request(__FUNCTION__, $param, 1, self::$appraise, 'custom');
    }
    
    /**
     * 获取线上发货标签
     */
    function getaloneprintinfos($param=null){
    	return $this->request(__FUNCTION__, $param, 1, self::$logistics, 'custom');
    }
    
    /**
     * 为已授权的用户开通消息服务
     */
    function tmcUserPermit($param=null){
    	return $this->request('permit', $param, 1, self::$taobao_tmc_user, 'custom');
    }
    
    /**
     * 消费多条消息
     */
    function tmcMessagesConsume($param=null){
    	return $this->request('consume', $param, 1, self::$taobao_tmc_messages, 'custom');
    }
    
    /**
     * 确认消费消息的状态
     */
    function tmcMessagesConfirm($param=null){
    	return $this->request('confirm', $param, 1, self::$taobao_tmc_messages, 'custom');
    }
    
    
    /**
     * 测试的自定义接口 拉单
     */
    function customGetOrders($param=null){
        return $this->request('', $param, 1, "custom.get.orders", 'custom');
    }
    
}
