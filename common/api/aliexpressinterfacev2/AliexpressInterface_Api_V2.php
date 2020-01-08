<?php
namespace common\api\aliexpressinterfacev2;

class AliexpressInterface_Api_V2 extends AliexpressInterface_Auth_V2{

	private static $logistics = 'aliexpress.logistics.';
	private static $logistics_redefining = 'aliexpress.logistics.redefining.';
	private static $trade_redefining = 'aliexpress.trade.redefining.';
	private static $message_redefining = 'aliexpress.message.redefining.';
	private static $appraise_redefining = 'aliexpress.appraise.redefining.';
	private static $qimen_aliexpress_logistics = 'qimen.aliexpress.logistics.';
	private static $postproduct_redefining = 'aliexpress.postproduct.redefining.';
	private static $aliexpress_message_redefining_versiontwo = 'aliexpress.message.redefining.versiontwo.';
	private static $taobao_tmc_user = 'taobao.tmc.user.';
	private static $taobao_tmc_messages = 'taobao.tmc.messages.';
	private static $member_redefining = 'aliexpress.member.redefining.';
###################速卖通标记发货相关接口################################
/**
 * 列出平台所支持的物流服务
 */
function listLogisticsService(){
    return $this->request(__FUNCTION__, array(), 1, self::$logistics_redefining);
}
/**
 * 订单标记发货
 serviceName    用户选择的实际发货物流服务
 logisticsNo    物流追踪号
 description    备注(只能输入英文)
 sendType   状态包括：全部发货(all)、部分发货(part)
 outRef 用户需要发货的订单id
 trackingWebsite      当serviceName=Other的情况时，需要填写对应的追踪网址

 */
function sellershipmentfortop($param=null){
    return $this->request(__FUNCTION__, $param, 1, self::$logistics);
}
/**
 * 修改发货声明
serviceName    用户选择的实际发货物流服务
logisticsNo    物流追踪号
description    备注(只能输入英文)
sendType   状态包括：全部发货(all)、部分发货(part)
outRef 用户需要发货的订单id
 */
function sellermodifiedshipmentfortop($param=null){
    return $this->request(__FUNCTION__, $param, 1, self::$logistics);
}
###################速卖通同步订单相关接口################################
/**
 * 查询订单列表
 */
function findOrderListQuery($param=null){
    return $this->request(__FUNCTION__, $param, 1, self::$trade_redefining);
}
/**
 * 查询订单简化列表
 */
function findOrderListSimpleQuery($param=null){
    return $this->request(__FUNCTION__, $param, 1, self::$trade_redefining);
}

/**
 * 查询单个订单详情
 */
function findOrderById($param=null){
    return $this->request(__FUNCTION__, $param, 1, self::$trade_redefining);
}

/**
 * 查询单个订单详情，V2版本
 */
function findOrderByIdV2($param=null){
	return $this->request('findorderbyid', $param, 1, 'aliexpress.trade.new.redefining.');
}

/**
 * 查询单个订单详情
 */
function findOrderBaseInfo($param=null){
    return $this->request(__FUNCTION__, $param, 1, self::$trade_redefining);
}
####################速卖通刊登相关接口#################################
/**
 * 获取指定类目下子类目信息
 */
function getChildrenPostCategoryById($param=null){
    return $this->request(__FUNCTION__,$param);
}

/**
 * 获取类目属性信息
 */
function getAttributesResultByCateId($param=null){
    return $this->request(__FUNCTION__,$param);
}
/**
 * 查询指定类目适合的尺码模板
 */
function sizeModelsRequiredForPostCat($param=null){
    return $this->request(__FUNCTION__,$param);
}
/**
 * 根据类目id获得适用的尺码表信息列表
 */
function getSizeChartInfoByCategoryId($param=null){
    return $this->request(__FUNCTION__,$param);
}

/**
 * 上传图片到临时目录
 */
function uploadTempImage($param=null,$imageurl){
    return $this->request2(__FUNCTION__,$param,$imageurl);
}

/**
 * 获取图片银行信息
 */
function getPhotoBankInfo($param=null){
    return $this->request(__FUNCTION__,$param);
}

/**
 * 删除未被引用图片
 */
function delUnUsePhoto($param=null){
    return $this->request(__FUNCTION__,$param);
}



/**
 * 上传图片到图片银行
 */
function uploadImage($param=null,$imageurl){
    return $this->request2(__FUNCTION__,$param,$imageurl);
}

/**
 * 上传图片到图片银行
 */
function uploadimageforsdk($param){
    return $this->request2(__FUNCTION__,$param,1,self::$photobank_redefining);
}


/**
 * 查询图片银行分组信息
 */
function listGroup($param=null){
    return $this->request(__FUNCTION__,$param);
}

/**
 * 图片银行列表分页查询
 */
function listImagePagination($param=null){
    return $this->request(__FUNCTION__,$param);
}

/**
 * 用户运费模板列表信息
 */
function listFreightTemplate($param=null){
    return $this->request(__FUNCTION__,$param);
}

/**
 * 通过模板ID获取运费模板详情
 */
function getFreightSettingByTemplateQuery($param=null){
    return $this->request(__FUNCTION__,$param);
}

/**
 * 服务模板查询
 */
function queryPromiseTemplateById($param=null){
    return $this->request(__FUNCTION__,$param);
}

/**
 * 获取当前会员的产品分组
 */
function getProductGroupList($param=null){
    return $this->request(__FUNCTION__,$param);
}

/**
 * 发布产品信息
 */
function postAeProduct($param=null){
    return $this->request(__FUNCTION__,$param);
}
################################修改在线商品相关接口########################################
/**
 * 获取属性需要优化的商品列表
 */
function getAtributeMissingProductList($param=null){
    return $this->request(__FUNCTION__,$param);
}

/**
 * 编辑商品类目属性
 */
function editProductCategoryAttributes($param=null){
    return $this->request(__FUNCTION__,$param);
}

/**
 * 商品分组设置
 */
function setGroups($param=null){
    return $this->request(__FUNCTION__,$param);
}
/**
 * 编辑产品类目、属性、sku
 */
function editProductCidAttIdSku($param=null){
    return $this->request(__FUNCTION__,$param);
}
/**
 * 编辑商品的单个字段
 */
function editSimpleProductFiled($param=null){
    return $this->request(__FUNCTION__,$param);
}
/**
 * 商品取消橱窗
 */
function offShopwindowProduct($param=null){
    return $this->request(__FUNCTION__,$param);
}
/**
 * 商品橱窗设置
 */
function setShopwindowProduct($param=null){
    return $this->request(__FUNCTION__,$param);
}
/**
 * 商品上架
 */
function onlineAeProduct($param=null){
    return $this->request(__FUNCTION__,$param);
}
/**
 * 商品下架
 */
function offlineAeProduct($param=null){
    return $this->request(__FUNCTION__,$param);
}
/**
 * 修改编辑商品信息
 */
function editAeProduct($param=null){
    return $this->request(__FUNCTION__,$param);
}
 ######################同步在线商品相关接口######################################
/**
 * 商品列表查询接口
 */
function findProductInfoListQuery($param=null){
    return $this->request(__FUNCTION__, $param, 1, self::$postproduct_redefining);
}
/**
 * 获取单个产品信息
 */
function findAeProductById($param=null){
    return $this->request(__FUNCTION__, $param, 1, self::$postproduct_redefining);
}

##############################速卖通线上发货相关接口#############################################
/**
 * 根据订单号获取线上发货物流方案
 */
function getOnlineLogisticsServiceListByOrderId($param=null){
    return $this->request(__FUNCTION__,$param);
}
/**
 * 创建线上发货物流订单
 */
function createWarehouseOrder($param=null){
    return $this->request(__FUNCTION__, $param, 1, self::$logistics);
}
/**
 * 获取线上物流发货邮政小包订单及国际物流运单号信息
 */
function getOnlineLogisticsInfo($param=null){
    return $this->request(__FUNCTION__,$param);
}
/**
 * 获取线上发货标签
 */
function getPrintInfo($param=null){
    return $this->request(__FUNCTION__, $param, 1, self::$logistics_redefining);
}


/**
 * 延长买家收货时间
 */
function extendsBuyerAcceptGoodsTime($param=null){
    return $this->request(__FUNCTION__, $param, 1, self::$trade_redefining);
}

/**
 * 获取订单留言
 * @param string $param
 */
function queryOrderMsgList($param=null){
    return $this->request(__FUNCTION__,$param);
}

/**
 * 获取站内信
 * @param string $param
 */
function queryMessageList($param=null){
    return $this->request(__FUNCTION__,$param);
}


/**
 * 查询物流追踪信息
 * @param string $param
 */
function queryTrackingResult($param=null){
    return $this->request(__FUNCTION__, $param, 1, self::$logistics_redefining);
}
/**
 * 卖家对未评价的订单进行评价
 */
function saveSellerFeedback($param=null){
	return $this->request(__FUNCTION__, $param, 1, self::$appraise_redefining);
}

//获取所有待评价订单
public function querySellerEvaluationOrderList($param=null){
	return $this->request('evaluation.querySellerEvaluationOrderList',$param);
}

    /**
     * 发送站内信/订单留言新版接口，代替原addMessage/addOrderMessage
     * 站内信的标识为 msgSources='message_center';
     * 订单留言的标识为 msgSources='order_msg';
     * @param string $param
     * 
     */
    public function addMsg($param=null) {
        $res = $this->request(__FUNCTION__, $param, 1, self::$message_redefining);
        //监控日志
        //error_log(json_encode($res).PHP_EOL, 3, '/tmp/ali_msg.debug');

        $res = $this->parseMsgResult($res);
        return $res;
    }


    /*
     *  统一返回值格式：['status'=>bool, 'code'=>int, 'msg'=>string]
     *  status  true 操作成功
     *  code 表示速卖通平台返回错误码
     *  msg  表示速卖通平台返回的错误信息描述
     */
    protected function parseMsgResult($res) {
        if(isset($res['error_response'])) {
            return ['status'=>false, 'code'=>$res['sub_code'], 'msg'=>$res['sub_msg']];
        }else if(isset($res['is_success'])) {
            return ['status'=>$res['is_success'], 'code'=>$res['error_code_return'], 'msg'=>$res['error_msg']];
		}else {
            return ['status'=>false, 'code'=>-100, 'msg'=>'result data exception'];
        }
    }


    //===================旧接口区域=====================
    /**
     * 新增订单留言,速卖通接口已废弃 2015-10-15，启用新接口addMsg
     */
    public function addOrderMessage($param=null){
        //原接口参数补充，等待分支合并后做兼容
        //$param['msgSources'] = 'order_msg';
        //return $this->addMsg($param);
        return $this->request(__FUNCTION__,$param);
    }


    /**
     * 新增站内信，速卖通接口已废弃 2015-10-15，启用新接口addMsg
     * @param string $param
     * 
     */
    public function addMessage($param=null) {
        //原接口参数补充
        $param['msgSources'] = 'message_center';
        return $this->addMsg($param);
    }
	
	/**
	 * 获取当前用户下与当前用户建立消息关系的列表 
	 * @param string $param
	 */
	function queryMsgRelationList($param=null){
		return $this->request(__FUNCTION__, $param, 1, self::$message_redefining);
	}

	/**
	 * 站内信/订单留言查询详情列表
	 * @param string $param
	 */
	function queryMsgDetailList($param=null){
		return $this->request(__FUNCTION__, $param, 1, self::$message_redefining);
	}

    /**
     * 根据发布类目id、父属性路径（可选）获取子属性信息
     *
     */
    function getChildAttributesResultByPostCateIdAndPath($param=null){
        return $this->request4(__FUNCTION__,$param);
    }
    //end function

    /**
     *查询信息模板列表
     */
    function findAeProductDetailModuleListByQurey($param=null){
        return $this->request(__FUNCTION__,$param);
    }
    //end function


    /**
     *
     *根据请求地址的类型：发货地址信息，揽收地址信息，返回相应的地址列表。
     */
    function getLogisticsSellerAddresses( $param=null ){
        return $this->request(__FUNCTION__, $param, 1, self::$logistics_redefining);
    }
    //end function

    function getPostCategoryById( $param=null ){
        return $this->request(__FUNCTION__,$param);
    }
    
    function queryMerchant( $param=null ){
        return $this->request6(__FUNCTION__,$param);
    }
    
    /**
     *云打印服务打印物流面单，lrq20171013
     */
    function getPdfsByCloudPrint($param=null){
    	return $this->request(__FUNCTION__, $param, 1, self::$logistics);
    }
    
    /**
     *新查询物流订单详情（推荐），lrq20171225
     */
    function queryLogisticsOrderDetail($param=null){
    	return $this->request(__FUNCTION__, $param, 1, self::$logistics);
    }
    
    /**
     * 获取开展国内物流业务的物流公司 ，奇门接口
     */
    function qureywlbdomesticlogisticscompany($param=null){
    	return $this->request(__FUNCTION__, $param, 1, self::$logistics_redefining);
    }
    
    /**
     * V2.0新增站内信/订单留言
     */
    function addmsgV2($param=null){
    	return $this->request('addmsg', $param, 1, self::$aliexpress_message_redefining_versiontwo);
    }
    
    /**
     * 根据买家ID获取站内信对话ID
     */
    function querymsgchannelidbybuyerid($param=null){
    	return $this->request(__FUNCTION__, $param, 1, self::$aliexpress_message_redefining_versiontwo);
    }
    
    /**
     * V2.0站内信/订单留言获取关系列表
     */
    function queryMsgRelationListV2($param=null){
    	return $this->request('querymsgrelationlist', $param, 1, self::$aliexpress_message_redefining_versiontwo);
    }
    
    /**
     * V2.0站内信/订单留言查询详情列表
     */
    function queryMsgDetailListV2($param=null){
    	return $this->request('querymsgdetaillist', $param, 1, self::$aliexpress_message_redefining_versiontwo);
    }
    
    /**
     * 批量获取线上发货标签
     */
    function getPrintInfos($param=null){
    	return $this->request(__FUNCTION__, $param, 1, self::$logistics_redefining);
    }
    
    /**
     * 通过aeLoginId查询tbNick
     */
    function uicQueryTbNick($param=null){
    	return $this->request(__FUNCTION__, $param, 1, self::$member_redefining);
    }
    
    /**
     * 为已授权的用户开通消息服务
     */
    function tmcUserPermit($param=null){
    	return $this->request('permit', $param, 1, self::$taobao_tmc_user);
    }
    
    /**
     * 获取用户已开通消息
     */
    function tmcUserGet($param=null){
    	return $this->request('get', $param, 1, self::$taobao_tmc_user);
    }
    
    /**
     * 消费多条消息
     */
    function tmcMessagesConsume($param=null){
    	return $this->request('consume', $param, 1, self::$taobao_tmc_messages);
    }
    
    /**
     * 确认消费消息的状态
     */
    function tmcMessagesConfirm($param=null){
    	return $this->request('confirm', $param, 1, self::$taobao_tmc_messages);
    }
    
    /**
     * 取消用户的消息服务
     */
    function tmcUserCancel($param=null){
    	return $this->request('cancel', $param, 1, self::$taobao_tmc_user);
    }
    
    /**
     * 订单列表查询，新版
     */
    function getOrderList($param=null){
    	return $this->request('', $param, 1, 'aliexpress.trade.seller.orderlist.get');
    }
    
}
