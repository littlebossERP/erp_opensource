<?php
namespace eagle\modules\order\openapi;

use eagle\components\OpenApi;                           //Ϊ��ʹ�ù��õ�output����
use eagle\modules\order\helpers\OrderTrackerApiHelper;  //����ģ�������ģ���APIҵ���߼�

/**
 * ����ģ��
 *
 * author: kincenyang
 * date: 2015-08-20
 * version: 0.1
 * ps:0.1�����汾ֻ�Ƿ�װԭ�еĲ�ѯ�����������������룬���£������������߼��Ͳ����ϵ��κθĶ���ֻ�ṩapi�ӿڵ���ڣ�������ԭ��ֱ�ӵ��ã�
 */
class OrderApi extends OpenApi{
    /**
     * ��ȡ�ѷ��������б�Ĺ�������
     * �˳�������Trackerģ����û�ȡ������ĳ�鶨ʱ��ebay��smt����ȡ�����ģ��ѷ��������Ĺ���������
     * 
     * author: kincenyang
     * date: 2015-08-20
     * version: 0.1
     * 
     * @param    $puid             ��ǰ ��puid
     * @param    $getAll         �Ƿ��ȡ��ǰ���й���������Ϣ 
     * 
     * return 
     * {
        "response": {
                "code": 0,
                "msg": "",
                "data": [
                    {
                        "order_id": "1",
                        "tracking_no": "1",
                        "subtotal": "0.00",
                        "currency": "",
                        "create_time": "0",
                        "paid_time": "0",
                        "carrier": "212",
                        "selleruserid": "1",
                        "order_source": "1",
                        "consignee_country_code": ""
                    }
                ]
            }
        }
     */
    public function getOverdueOrderShippedListModifiedAfter($v = '0.1' , $puid  , $getAll=false) {
        $result = OrderTrackerApiHelper::getOverdueOrderShippedListModifiedAfter($puid  , $getAll=false);
        return $this->output($result);
    }
    
    
    /**
     * ��ȡ�ѷ��������б�,�Ը���ʱ����ĳ��time�Ժ��
     * �˳�������Trackerģ����û�ȡ������ĳ�鶨ʱ��ebay��smt����ȡ�����ģ��ѷ��������Ĺ���������
     * 
     * author: kincenyang
     * date: 2015-08-20
     * version: 0.1
     * 
     * @param    $puid             ��ǰ ��puid
     * @param    $getAll         �Ƿ��ȡ��ǰ���й���������Ϣ 
     * 
     * return 
     * {
            "response": {
                "code": 0,
                "msg": "",
                "data": [
                    {
                        "order_id": "1",
                        "tracking_no": "1",
                        "subtotal": "0.00",
                        "currency": "",
                        "create_time": "0",
                        "paid_time": "0",
                        "carrier": "212",
                        "selleruserid": "1",
                        "order_source": "1",
                        "consignee_country_code": ""
                    }
                ]
            }
        }
     */
    public function getShippedOrderListModifiedAfter($v = '0.1' , $puid  , $getAll=false) {
        $result = OrderTrackerApiHelper::getShippedOrderListModifiedAfter($puid ,$update_time='' , $getAll=false);
        return $this->output($result);
    }
    
    
    /**
     * ��ȡĳ����������ϸ��Ϣ
     * 
     * author: kincenyang
     * date: 2015-08-20
     * version: 0.1
     * 
     * @param     $track_no   ������
     * 
     * return 
     * {
            "response": {
                "code": 0,
                "msg": "",
                "data": {
                    "order_id": "00000000001",
                    "order_status": "1",
                    "order_source_status": "1",
                    "pay_status": "1",
                    "order_manual_id": "1",
                    "is_manual_order": "1",
                    "shipping_status": "1",
                    "exception_status": "1",
                    "order_source": "1",
                    "order_type": "1",
                    "order_source_order_id": "1",
                    "order_source_site_id": "11",
                    "selleruserid": "1",
                    "saas_platform_user_id": "1",
                    "order_source_srn": "1",
                    "customer_id": "1",
                    "source_buyer_user_id": "1",
                    "order_source_shipping_method": "1",
                    "order_source_create_time": "1",
                    "subtotal": "0.00",
                    "shipping_cost": "0.00",
                    "discount_amount": "0.00",
                    "grand_total": "0.00",
                    "returned_total": "0.00",
                    "price_adjustment": "0.00",
                    "currency": "",
                    "consignee": "",
                    "consignee_postal_code": "",
                    "consignee_phone": "",
                    "consignee_mobile": null,
                    "consignee_email": "",
                    "consignee_company": "",
                    "consignee_country": "",
                    "consignee_country_code": "",
                    "consignee_city": "",
                    "consignee_province": "",
                    "consignee_district": "",
                    "consignee_county": "",
                    "consignee_address_line1": "",
                    "consignee_address_line2": "",
                    "consignee_address_line3": "",
                    "default_warehouse_id": "0",
                    "default_carrier_code": "",
                    "default_shipping_method_code": "",
                    "paid_time": "0",
                    "delivery_time": "0",
                    "create_time": "0",
                    "update_time": "0",
                    "user_message": "",
                    "carrier_type": "0",
                    "is_feedback": "0",
                    "hassendinvoice": "0",
                    "seller_commenttype": null,
                    "seller_commenttext": null,
                    "status_dispute": "0",
                    "rule_id": null,
                    "printtime": "0",
                    "carrier_step": "0",
                    "is_print_picking": null,
                    "print_picking_operator": null,
                    "print_picking_time": null,
                    "is_print_distribution": null,
                    "print_distribution_operator": null,
                    "print_distribution_time": null,
                    "is_print_carrier": null,
                    "print_carrier_operator": null,
                    "delivery_status": "0",
                    "delivery_id": null,
                    "customer_number": null,
                    "desc": null,
                    "carrier_error": null,
                    "items": [
                        {
                            "order_item_id": "1",
                            "order_id": "00000000001",
                            "order_source_srn": "1",
                            "order_source_order_item_id": "1",
                            "source_item_id": "1",
                            "sku": "1",
                            "product_name": "1",
                            "photo_primary": "1",
                            "shipping_price": "1.00",
                            "shipping_discount": "1.00",
                            "price": "1.00",
                            "promotion_discount": "1.00",
                            "ordered_quantity": "1",
                            "quantity": "1",
                            "sent_quantity": "1",
                            "packed_quantity": "1",
                            "returned_quantity": "1",
                            "invoice_requirement": "1",
                            "buyer_selected_invoice_category": "",
                            "invoice_title": "1",
                            "invoice_information": "1",
                            "remark": null,
                            "create_time": "1",
                            "update_time": "1",
                            "desc": null,
                            "platform_sku": "",
                            "is_bundle": 0,
                            "bdsku": "",
                            "order_source_order_id": null,
                            "order_source_transactionid": null,
                            "order_source_itemid": null,
                            "product_attributes": null,
                            "product_unit": null,
                            "lot_num": 1,
                            "goods_prepare_time": 1,
                            "product_url": null
                        }
                    ]
                }
            }
        }
     * 
     * 
     */
    public function getOrderDetailByTrackNo($v = '0.1' , $track_no) {
        $result = OrderTrackerApiHelper::getOrderDetailByTrackNo($track_no);
        return $this->output($result);
    }
    
    
    /**
     * ��ȡĳ����������ϸ��Ϣ
     * 
     * author: kincenyang
     * date: 2015-08-20
     * version: 0.1
     * 
     * @param     $platform_order_no   ƽ̨������Id
     * 
     * return 
     * {
            "response": {
                "code": 0,
                "msg": "",
                "data": {
                    "order_id": "00000000001",
                    "order_status": "1",
                    "order_source_status": "1",
                    "pay_status": "1",
                    "order_manual_id": "1",
                    "is_manual_order": "1",
                    "shipping_status": "1",
                    "exception_status": "1",
                    "order_source": "1",
                    "order_type": "1",
                    "order_source_order_id": "1",
                    "order_source_site_id": "11",
                    "selleruserid": "1",
                    "saas_platform_user_id": "1",
                    "order_source_srn": "1",
                    "customer_id": "1",
                    "source_buyer_user_id": "1",
                    "order_source_shipping_method": "1",
                    "order_source_create_time": "1",
                    "subtotal": "0.00",
                    "shipping_cost": "0.00",
                    "discount_amount": "0.00",
                    "grand_total": "0.00",
                    "returned_total": "0.00",
                    "price_adjustment": "0.00",
                    "currency": "",
                    "consignee": "",
                    "consignee_postal_code": "",
                    "consignee_phone": "",
                    "consignee_mobile": null,
                    "consignee_email": "",
                    "consignee_company": "",
                    "consignee_country": "",
                    "consignee_country_code": "",
                    "consignee_city": "",
                    "consignee_province": "",
                    "consignee_district": "",
                    "consignee_county": "",
                    "consignee_address_line1": "",
                    "consignee_address_line2": "",
                    "consignee_address_line3": "",
                    "default_warehouse_id": "0",
                    "default_carrier_code": "",
                    "default_shipping_method_code": "",
                    "paid_time": "0",
                    "delivery_time": "0",
                    "create_time": "0",
                    "update_time": "0",
                    "user_message": "",
                    "carrier_type": "0",
                    "is_feedback": "0",
                    "hassendinvoice": "0",
                    "seller_commenttype": null,
                    "seller_commenttext": null,
                    "status_dispute": "0",
                    "rule_id": null,
                    "printtime": "0",
                    "carrier_step": "0",
                    "is_print_picking": null,
                    "print_picking_operator": null,
                    "print_picking_time": null,
                    "is_print_distribution": null,
                    "print_distribution_operator": null,
                    "print_distribution_time": null,
                    "is_print_carrier": null,
                    "print_carrier_operator": null,
                    "delivery_status": "0",
                    "delivery_id": null,
                    "customer_number": null,
                    "desc": null,
                    "carrier_error": null,
                    "items": [
                        {
                            "order_item_id": "1",
                            "order_id": "00000000001",
                            "order_source_srn": "1",
                            "order_source_order_item_id": "1",
                            "source_item_id": "1",
                            "sku": "1",
                            "product_name": "1",
                            "photo_primary": "1",
                            "shipping_price": "1.00",
                            "shipping_discount": "1.00",
                            "price": "1.00",
                            "promotion_discount": "1.00",
                            "ordered_quantity": "1",
                            "quantity": "1",
                            "sent_quantity": "1",
                            "packed_quantity": "1",
                            "returned_quantity": "1",
                            "invoice_requirement": "1",
                            "buyer_selected_invoice_category": "",
                            "invoice_title": "1",
                            "invoice_information": "1",
                            "remark": null,
                            "create_time": "1",
                            "update_time": "1",
                            "desc": null,
                            "platform_sku": "",
                            "is_bundle": 0,
                            "bdsku": "",
                            "order_source_order_id": null,
                            "order_source_transactionid": null,
                            "order_source_itemid": null,
                            "product_attributes": null,
                            "product_unit": null,
                            "lot_num": 1,
                            "goods_prepare_time": 1,
                            "product_url": null
                        }
                    ]
                }
            }
        }
     */
    public function getOrderDetailByOrderNo($v = '0.1' , $platform_order_no) {
        $result = OrderTrackerApiHelper::getOrderDetailByOrderNo($platform_order_no);
        return $this->output($result);
    }
    
    /**
     * ���� $customerArr ��ȡָ��ƽ̨�Ķ����б���Ϣ
     * ���Ը���ĳ��sku��ȡ���ж�����listing
     * 
     * author: kincenyang
     * date: 2015-08-20
     * version: 0.1
     * 
     * @param     $platformType //ƽ̨����
     *            $customerArr=array('source_buyer_user_id' => '');  //ʵ��: ��ͬƽ̨��customerΨһ�Բ�ȷ��,��Ҫʹ����ȷ���Լ���customer_id
     *            $params=array(sort, order, sku, source_order_id); //sort, order�������õ��Ĳ���, sku:ĳ�� sku �����ж�����listing,source_order_id������Դ  �Ķ���id
     *            $defaultPageSize //Ĭ��ÿҳ��ʾ�ļ�¼��
     * 
     * return
     * {
            "response": {
                "code": 0,
                "msg": true,
                "data": {
                    "pagination": {
                        "pageParam": "page",
                        "pageSizeParam": "per-page",
                        "forcePageParam": true,
                        "route": null,
                        "params": null,
                        "urlManager": null,
                        "validatePage": true,
                        "totalCount": "1",
                        "defaultPageSize": 20,
                        "pageSizeLimit": [
                            1,
                            50
                        ]
                    },
                    "data": [
                        {
                            "order_id": "00000000001",
                            "order_status": "1",
                            "order_source_status": "1",
                            "pay_status": "1",
                            "order_manual_id": "1",
                            "is_manual_order": "1",
                            "shipping_status": "1",
                            "exception_status": "1",
                            "order_source": "1",
                            "order_type": "1",
                            "order_source_order_id": "1",
                            "order_source_site_id": "11",
                            "selleruserid": "1",
                            "saas_platform_user_id": "1",
                            "order_source_srn": "1",
                            "customer_id": "1",
                            "source_buyer_user_id": "1",
                            "order_source_shipping_method": "1",
                            "order_source_create_time": "1",
                            "subtotal": "0.00",
                            "shipping_cost": "0.00",
                            "discount_amount": "0.00",
                            "grand_total": "0.00",
                            "returned_total": "0.00",
                            "price_adjustment": "0.00",
                            "currency": "",
                            "consignee": "",
                            "consignee_postal_code": "",
                            "consignee_phone": "",
                            "consignee_mobile": null,
                            "consignee_email": "",
                            "consignee_company": "",
                            "consignee_country": "",
                            "consignee_country_code": "",
                            "consignee_city": "",
                            "consignee_province": "",
                            "consignee_district": "",
                            "consignee_county": "",
                            "consignee_address_line1": "",
                            "consignee_address_line2": "",
                            "consignee_address_line3": "",
                            "default_warehouse_id": "0",
                            "default_carrier_code": "",
                            "default_shipping_method_code": "",
                            "paid_time": "0",
                            "delivery_time": "0",
                            "create_time": "0",
                            "update_time": "0",
                            "user_message": "",
                            "carrier_type": "0",
                            "is_feedback": "0",
                            "hassendinvoice": "0",
                            "seller_commenttype": null,
                            "seller_commenttext": null,
                            "status_dispute": "0",
                            "rule_id": null,
                            "printtime": "0",
                            "carrier_step": "0",
                            "is_print_picking": null,
                            "print_picking_operator": null,
                            "print_picking_time": null,
                            "is_print_distribution": null,
                            "print_distribution_operator": null,
                            "print_distribution_time": null,
                            "is_print_carrier": null,
                            "print_carrier_operator": null,
                            "delivery_status": "0",
                            "delivery_id": null,
                            "customer_number": null,
                            "desc": null,
                            "carrier_error": null,
                            "track_no": "",
                            "status": ""
                        }
                    ]
                }
            }
        }
     */
    public function getOrderList($v = '0.1' , $platformType, $customerArr = array(), $params = array(), $defaultPageSize = 20) {
        $res = OrderTrackerApiHelper::getOrderList($platformType, $customerArr = array(), $params = array(), $defaultPageSize = 20);
        $result = $res['orderArr'];
        $msg = $res['success'];
        return $this->output($result,$code = 0,$msg);
    }
    
    
    
    /**
     * author: kincenyang
     * date: 2015-08-20
     * version: 0.1
     * 
     * @param     $platformType //ƽ̨����
     *               $customer_id  //ʵ��: �ͻ�id
     *               $sku             //��Ʒsku
     * 
     * return
     * {
            "response": {
                "code": 0,
                "msg": "order",
                "data": {
                    "order_id": "00000000001",
                    "order_status": "1",
                    "order_source_status": "1",
                    "pay_status": "1",
                    "order_manual_id": "1",
                    "is_manual_order": "1",
                    "shipping_status": "1",
                    "exception_status": "1",
                    "order_source": "1",
                    "order_type": "1",
                    "order_source_order_id": "1",
                    "order_source_site_id": "11",
                    "selleruserid": "1",
                    "saas_platform_user_id": "1",
                    "order_source_srn": "1",
                    "customer_id": "1",
                    "source_buyer_user_id": "1",
                    "order_source_shipping_method": "1",
                    "order_source_create_time": "1",
                    "subtotal": "0.00",
                    "shipping_cost": "0.00",
                    "discount_amount": "0.00",
                    "grand_total": "0.00",
                    "returned_total": "0.00",
                    "price_adjustment": "0.00",
                    "currency": "",
                    "consignee": "",
                    "consignee_postal_code": "",
                    "consignee_phone": "",
                    "consignee_mobile": null,
                    "consignee_email": "",
                    "consignee_company": "",
                    "consignee_country": "",
                    "consignee_country_code": "",
                    "consignee_city": "",
                    "consignee_province": "",
                    "consignee_district": "",
                    "consignee_county": "",
                    "consignee_address_line1": "",
                    "consignee_address_line2": "",
                    "consignee_address_line3": "",
                    "default_warehouse_id": "0",
                    "default_carrier_code": "",
                    "default_shipping_method_code": "",
                    "paid_time": "0",
                    "delivery_time": "0",
                    "create_time": "0",
                    "update_time": "0",
                    "user_message": "",
                    "carrier_type": "0",
                    "is_feedback": "0",
                    "hassendinvoice": "0",
                    "seller_commenttype": null,
                    "seller_commenttext": null,
                    "status_dispute": "0",
                    "rule_id": null,
                    "printtime": "0",
                    "carrier_step": "0",
                    "is_print_picking": null,
                    "print_picking_operator": null,
                    "print_picking_time": null,
                    "is_print_distribution": null,
                    "print_distribution_operator": null,
                    "print_distribution_time": null,
                    "is_print_carrier": null,
                    "print_carrier_operator": null,
                    "delivery_status": "0",
                    "delivery_id": null,
                    "customer_number": null,
                    "desc": null,
                    "carrier_error": null,
                    "items": [
                        {
                            "order_item_id": "1",
                            "order_id": "00000000001",
                            "order_source_srn": "1",
                            "order_source_order_item_id": "1",
                            "source_item_id": "1",
                            "sku": "1",
                            "product_name": "1",
                            "photo_primary": "1",
                            "shipping_price": "1.00",
                            "shipping_discount": "1.00",
                            "price": "1.00",
                            "promotion_discount": "1.00",
                            "ordered_quantity": "1",
                            "quantity": "1",
                            "sent_quantity": "1",
                            "packed_quantity": "1",
                            "returned_quantity": "1",
                            "invoice_requirement": "1",
                            "buyer_selected_invoice_category": "",
                            "invoice_title": "1",
                            "invoice_information": "1",
                            "remark": null,
                            "create_time": "1",
                            "update_time": "1",
                            "desc": null,
                            "platform_sku": "",
                            "is_bundle": 0,
                            "bdsku": "",
                            "order_source_order_id": null,
                            "order_source_transactionid": null,
                            "order_source_itemid": null,
                            "product_attributes": null,
                            "product_unit": null,
                            "lot_num": 1,
                            "goods_prepare_time": 1,
                            "product_url": null
                        }
                    ]
                }
            }
        }
     */
    public function getOrderDetailOrSkuDetail($v = '0.1' , $platformType, $customer_id, $sku) {
        $res = OrderTrackerApiHelper::getOrderDetailOrSkuDetail($platformType, $customer_id, $sku);
        $result = $res['dataInfo'];
        $msg = $res['type'];
        return $this->output($result,$code = 0,$msg);
    }
    
    
    /**
     * ����$order_id��ȡ���һ��Tracking���ٺ�
     * 
     * author: kincenyang
     * date: 2015-08-20
     * version: 0.1
     * 
     * @param     $seller_id //����Id
     *            $order_id  //����ƽ̨������
     * 
     * return 
     * {
            "response": {
                "code": 0,
                "msg": "",
                "data": {
                    "track_no": "1212121212",
                    "status": "��ǩ��"
                }
            }
        }
     */
    public function getTrackingNoByOrderId($v = '0.1' , $seller_id, $order_id) {
        $result = OrderTrackerApiHelper::getTrackingNoByOrderId($seller_id, $order_id);
        return $this->output($result);
    }
    
    
    /**
     * �����ṩ�Ĳ���������Ҫͬ�������Ķ���������б�
     * 
     * author: kincenyang
     * date: 2015-08-26
     * version: 0.1
     * 
     * @param     Array
     *            $orderId //����Id
     *            $score   //�û����������Ĵ��
     *            $feedbackContent  //���Ҷ�δ���۵Ķ�����������(Max 1,000 characters. Please do not use HTML codes or Chinese characters.ͬʱ�������ı��Ҳ��֧�֣�
     * return 
     * {
            "response": {
                "code": 0,
                "msg": "",
                "data": ""
            }
        }
     */
     public function insertOrderToQueue($params) {
        $res = OrderTrackerApiHelper::insertOrderToQueue($params);
        $result = $res['result'];
        $msg = $res['msg'];
        return $this->output($result,$code = 0,$msg);
        
    }
     
     
     /**
      * 通过订单id返回订单评价的详细信息
      * 
      * author: kincenyang
      * date: 2015-08-26
      * version: 0.1
      * 
      * @param     Array
      *            $orderId //订单ID
      * 
      */
     public function getPraiseInfo($params) {
        $result = OrderTrackerApiHelper::getPraiseInfo($params);
		    if(empty($result)){
				return $this->output($result,$code = 1);
			}else{
				return $this->output($result,$code = 0);
			}
        
    }
    

    /**
      * 获得所有速卖通待评价订单
      * 
      * author: rice
      * date: 2015-09-28
      * version: 0.1
      * 
      * @param     Array
      *            $seller_id //速卖通卖家标识
      * 
      *
      * @return    code = 0  //成功
      *            code = 1 //token异常
      *            code = 2 //返回值异常
      *            code = aliexpress_error_code
      *
      */
     public function getEvaluationOrders($params) {

        $api = new \common\api\aliexpressinterface\AliexpressInterface_Api();

        $access_token = $api->getAccessToken($params['seller_id']);

        if ($access_token === false) {
            return $this->output([], 1, 'Token acquisition failure!');
        }
        $api->access_token = $access_token;

        //默认100条数据

        $result = $api->querySellerEvaluationOrderList(['currentPage'=>1, 'pageSize'=>$params['page_size']]);

        //解析结果
        if(isset($result['totalItem'])) {
            if($result['totalItem'] > 0) {
                if(isset($result['listResult'])&&count($result['listResult']) > 0) {
                    return $this->output($result['listResult']);
                }
            }
            return $this->output([]);
        }else if(isset($result['error_code'])) {
            return $this->output([], $result['error_code'], $result['error_message']);
        }else {
            return $this->output([], 2, 'aliexpress api data exception');
        }
    }


    /**
      * 实时给速卖通订单好评
      * 
      * author: rice
      * date: 2015-09-28
      * version: 0.1
      * 
      * @param     Array
      *            $seller_id //速卖通卖家标识
      *            $order_id  //速卖通订单号
      *            $content   //评价内容
      * 
      * @return    code = 0  //成功
      *            code = 1 //token异常
      *            code = 2 //返回值异常
      *            code = aliexpress_error_code
      *
      */
     public function saveEvaluation($params) {

        $api = new \common\api\aliexpressinterface\AliexpressInterface_Api();

        $access_token = $api->getAccessToken($params['seller_id']);

        if ($access_token === false) {
            return $this->output([], 1, 'Token acquisition failure!');
        }
        $api->access_token = $access_token;
         $api_params['orderId']=$params['order_id'];
        //组织参数
         if(isset( $params['content'])){
             $api_params['feedbackContent']= $params['content'];
             
         }
         if(isset($params['score'])){
             $api_params['score']=$params['score'];
         }
        

        $result = $api->saveSellerFeedback($api_params);

        //解析结果
        if(isset($result['errorCode'])) {
            return $this->output([], $result['errorCode'], $result['errorMessage']);
        }else if(isset($result['error_code'])) {
            return $this->output([], $result['error_code'], $result['error_message']);
        }else if(isset($result['success']) && $result['success']) {
            return $this->output([]);
        }else {
            return $this->output([], 2, 'aliexpress api data exception');
        }
    }
}