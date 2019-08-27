<?php
namespace common\api\carrierAPI;
use common\helpers\SubmitGate;
use eagle\models\SaasEbayUser;
use eagle\modules\order\models\OdEbayTransaction;
use eagle\modules\carrier\helpers\CarrierAPIHelper;
use eagle\modules\order\models\OdOrder;
use eagle\modules\carrier\helpers\CarrierException;
use Jurosh\PDFMerge\PDFMerger;
use Qiniu\json_decode;
// include '../components/PDFMerger/PDFMerger.php';

class EbayBaseConfig {

    public $return_info = [
        'error' => 0,
        'data' => '',
        'msg' => ''
    ];

    public $service_info = null;
    public $order_info = null;
    public $order_item_info = null;
    public $account_info = null;
    public $data_info = null;
    public $check_order_info = null;
    public $puid = null;
    public $shipped = null;
    public $wsdl = null;
    public $public_info = null;
    public $senderAddressInfo = null;

    /**
     * @param $data
     * @param $public_info
     * @param $wsdl
     * @param int $user_is_login
     * @param int $is_shipped
     */
    public function __construct($data,$public_info,$wsdl,$user_is_login=0,$is_shipped=0,$is_doprint=0){

        if($is_doprint == 0){
            $this->order_info = $data['order'];//odOrder表内容
            $this->order_item_info = $data['order']['items'];
            $this->data_info = $data['data'];//获取到所需要使用的数据
            //检查订单信息
            $this->check_order_info = $this->_checkOrder($user_is_login,$is_shipped, $data['data']['customer_number']);
            if($this->check_order_info['error'] == 0){
                $this->puid = $this->check_order_info['data']['puid'];
                $this->shipped = $this->check_order_info['data']['shipped'];
            }

            $result = CarrierAPIHelper::getAllInfo($this->order_info);
            $this->service_info = $result['service'];
            $this->account_info = $result['account'];
            
            if(empty($result['senderAddressInfo'])){
            	return array('error' => 1, 'data' => '', 'msg' => '地址信息没有设置好，请到相关的货代设置地址信息');
            }
            
            $this->senderAddressInfo = $result['senderAddressInfo'];
            $this->public_info = $public_info;
            $this->wsdl = $wsdl;
        } else if($is_doprint == 1){//打印物流单号
            $this->order_info = $data;
            $this->public_info = $public_info;
            $this->wsdl = $wsdl;
        } else if($is_doprint == 2){ // 获取物流单号
            $this->order_info = $data['order'];;
            $this->public_info = $public_info;
        }

        $this->submitGate = new SubmitGate();

    }

    /**
     * 上传包裹信息
     * @return array
     */
    public function _getOrderNo(){

        $order_info = $this->return_info;
        try{
            //验证订单信息
            if($this->check_order_info['error'] == 1){
                throw new CarrierException($this->check_order_info['msg']);
            }

            //买家发货及物流取货地址信息
            $order_detail = self::getSendAddress($this->senderAddressInfo);
//             $order_detail = self::getSendAddress($this->account_info->address);
            $order_detail['EMSPickUpType'] = $this->service_info['carrier_params']['EMSPickUpType'];

            //selleruserid在本地已绑定数据中获取
            $ebayuser = SaasEbayUser::find()->where(['selleruserid'=>$this->order_info->selleruserid,'uid'=>$this->puid])->one();

            //如果在系统中查询不到绑定信息 则使用用户输入的帐号和token
            if(!$ebayuser){
                //throw new CarrierException('小老板系统中没有绑定该账户，无法获取到token值');
            }
            $this->public_info['APISellerUserID'] = $ebayuser->selleruserid;
            $this->public_info['APISellerUserToken'] = $ebayuser->token;
//            $this->public_info['APISellerUserID'] = 'testuser_hsseller1';
//            $this->public_info['APISellerUserToken'] = 'asdfasdf';
            $this->public_info['MessageID'] = self::getMessageid();
            $this->public_info['Carrier'] = $this->service_info['carrier_params']['Carrier'];
            //$this->public_info['Service'] = $this->service_info['shipping_method_code'];
            $this->public_info['Service'] = $this->getService($this->service_info['carrier_params']['Carrier']);
            //收货人地址信息
            $order_detail['ShipToAddress'] = $this->_getShipToAddress();
            //e邮宝不需要填写 包裹长宽高
            if($this->service_info['carrier_params']['Carrier'] != 'CNPOST' ){
                //包裹描述
                $order_detail['ShippingPackage'] = self::getShippingPackage($this->service_info,$this->data_info);
            }
            //关税承担方DDU买家DDP卖家
            $order_detail['ShippingPackage']['Incoterms'] = isset($this->service_info['carrier_params']['SPIncoterms'])?$this->service_info['carrier_params']['SPIncoterms']:'DDP';

            //请求接口数组
            $request['AddAPACShippingPackageRequest'] = $this->public_info ;
            $request['AddAPACShippingPackageRequest']['OrderDetail'] = $this->_getOrderDetail($order_detail);//多条商品信息列表
            /*==============================================================================*/
            //提交数据

            \Yii::info('EbayBaseConfig,request,puid:'.$this->puid.' '.json_encode($request),"carrier_api");
            
            $response = $this->submitGate->mainGate($this->wsdl, $request, 'soap', 'AddAPACShippingPackage');
            
            \Yii::info('EbayBaseConfig,response,puid:'.$this->puid.' '.json_encode($response),"carrier_api");

            if($response['error']){
                throw new CarrierException($response['msg']);
            }
            $response = $response['data'];
            $response = $response->AddAPACShippingPackageResult;
            if($response->Ack == 'Failure'){
                $str = '';
                if(count($response->NotificationList->Notification) > 1){
                    foreach($response->NotificationList->Notification as $k=>$v){
                        $str .= $v->Message.'<br/>';
                    }
                }else{
                    $str = $response->NotificationList->Notification->Message;
                }
                $order_info['error'] = 1;
                $order_info['msg'] = $str;
            } else if($response->Ack == 'Success'){
                //上传成功后进行数据的保存 订单，运输服务，客户号，订单状态，跟踪号(选填),returnNo(选填)
                $r = CarrierAPIHelper::orderSuccess($this->order_info,$this->service_info,$response->TrackCode,OdOrder::CARRIER_WAITING_DELIVERY,$response->TrackCode);
                $order_info['error'] = 0 ;
                $order_info['data'] = $r;
                $order_info['msg'] =  "物流跟踪号：".$response->TrackCode;
            } else {
                $this->_setLog($response,'getOrderNo');
                $order_info['error'] =  1;
                $order_info['msg'] = '异常错误';
            }
        }catch(CarrierException $e){
            $order_info['error'] = 1;
            $order_info['msg'] = $e->msg();
        }
        return $order_info;
    }

    /**
     * 取消并删除包裹信息
     * @return array
     */
    public function _cancelOrderNO(){

        $order_info = $this->return_info;
        try{
            //验证订单信息
            if($this->check_order_info['error'] == 1){
                throw new CarrierException($this->check_order_info['msg']);
            }

            $ebayuser = SaasEbayUser::find()->where(['selleruserid'=>$this->order_info->selleruserid,'uid'=>$this->puid])->one();
            if(!$ebayuser){
                throw new CarrierException('该订单卖家帐号系统内没有绑定');
            }
            $this->public_info['APISellerUserID'] = $ebayuser->selleruserid;
            $this->public_info['APISellerUserToken'] = $ebayuser->token;
            $this->public_info['MessageID'] = self::getMessageid();
            $this->public_info['Carrier'] = $this->service_info->carrier_params->Carrier;
            $this->public_info['TrackCode'] = $this->order_info->customer_number;//包裹跟踪号使用customer_number
            $request['CancelAPACShippingPackageRequest'] = $this->public_info;
            //返回数据处理
            $response = $this->submitGate->mainGate($this->wsdl, $request, 'soap', 'CancelAPACShippingPackage');
            if($response['error']){
                throw new CarrierException($response['msg']);
            }
            $response = $response['data'];
            $response = $response->CancelAPACShippingPackageResult;
            //如果是错误信息
            if($response->Ack == 'Failure'){
                $str = '';
                if(count($response->NotificationList->Notification) > 1){
                    foreach($response->NotificationList->Notification as $k=>$v){
                        $str .= $v->Message.'<br/>';
                    }
                }else{
                    $str = $response->NotificationList->Notification->Message;
                }
                $order_info['error'] = 1;
                $order_info['msg'] = $str;
            } else if($response->Ack == 'Success'){
                $this->shipped->delete();
                $this->order_info->carrier_step = OdOrder::CARRIER_CANCELED;
                $this->order_info->save();
                $order_info['error'] = 0 ;
                $order_info['msg'] =  '结果：订单已取消!时间:'.date('Y-m-d H:i:s',time());
            } else {
                $this->_setLog($response,'cancelOrderNO');
                $order_info['error'] = 1;
                $order_info['msg'] = '异常错误';
            }
        } catch(CarrierException $e) {
            $order_info['error'] = 1;
            $order_info['msg'] = $e->msg();
        }

        return $order_info;
    }

    /**
     * 确认并交运包裹信息
     * @return array
     */
    public function _doDispatch(){
        $order_info = $this->return_info;
        try{
            if($this->check_order_info['error'] == 1){
                throw new CarrierException($this->check_order_info['msg']);
            }

            $ebayuser = SaasEbayUser::find()->where(['selleruserid'=>$this->order_info->selleruserid,'uid'=>$this->puid])->one();
            if(!$ebayuser){
                throw new CarrierException('该订单卖家帐号系统内没有绑定');
            }
            ############################################################################################
            $this->public_info['APISellerUserID'] =  $ebayuser->selleruserid;
            $this->public_info['APISellerUserToken'] = $ebayuser->token;
            $this->public_info['MessageID'] = self::getMessageid();
            $this->public_info['Carrier'] = $this->service_info->carrier_params->Carrier;
            $this->public_info['TrackCode'] = $this->order_info->customer_number;//包裹跟踪号使用customer_number
            $request['ConfirmAPACShippingPackageRequest'] = $this->public_info;

            /*=====================================================================================*/
            //数据组织完毕，开始发送
            $response = $this->submitGate->mainGate($this->wsdl, $request, 'soap', 'ConfirmAPACShippingPackage');
            if($response['error']){
                throw new CarrierException($response['msg']);
            }
            $response = $response['data'];
            $response = $response->ConfirmAPACShippingPackageResult;
            //如果是错误信息
            if($response->Ack == 'Failure'){
                $str = '';
                if(count($response->NotificationList->Notification) > 1){
                    foreach($response->NotificationList->Notification as $k=>$v){
                        $str .= $v->Message.'<br/>';
                    }
                }else{
                    $str = $response->NotificationList->Notification->Message;
                }
                throw new CarrierException($str);
            } else if($response->Ack == 'Success'){
                $this->order_info->carrier_step = OdOrder::CARRIER_WAITING_PRINT;
                $this->order_info->save();
                $order_info['msg'] = '结果：预报订单成功！跟踪号:'.$this->order_info->customer_number;
            } else {
                $this->_setLog($response,"doDispatch");
                $order_info['error'] = 1;
                $order_info['msg'] = '异常错误';
            }
        }catch (CarrierException $e){
            $order_info['error'] = 1;
            $order_info['msg'] = $e->msg();
        }
        return $order_info;
    }

    /**
     * 获取物流单号 亚太平台 track_number 等于 customer_number
     * @return array
     */
    public function _getTrackingNo(){
        $order_info = $this->return_info;
        try{
            if(empty($this->order_info->customer_number)){
                throw new CarrierException('该订单状态异常，请检查是否手动移动到本状态');
            }
//             $this->order_info->carrier_step = OdOrder::CARRIER_WAITING_PRINT;
            $this->order_info->save();
            $order_info['msg'] = '结果：获取物流号成功！物流号：'.$this->order_info->customer_number;

        }catch(CarrierException $e){
            $order_info['error'] = 1;
            $order_info['msg'] = $e->msg();
        }
        return $order_info;
    }

    /**
     * 打印详情单，包括A4标签格式的详情单和热敏标签格式的详情单
     */
    public function _doPrint(){
        $order_info = $this->return_info;
        try{

            $user=\Yii::$app->user->identity;
            if(empty($user)){
                throw new CarrierException('用户登陆信息缺失,请重新登陆');
            }
            $this->puid = $user->getParentUid();

            $pdf = new PDFMerger();
            $result = [];

            foreach($this->order_info as $v){
                $order = $v['order'];
                $service = CarrierAPIHelper::getAllInfo($order);
                $this->service_info = $service['service'];
                //用户账户表
                $ebayuser = SaasEbayUser::find()->where(['selleruserid'=>$order->selleruserid,'uid'=>$this->puid])->one();
                if(!$ebayuser){
                    throw new CarrierException('该订单卖家帐号系统内没有绑定');
                }

                if($this->service_info['carrier_code']=='lb_ebayubi'){
                	$this->public_info['APISellerUserID'] = $ebayuser->selleruserid;
                	$this->public_info['APISellerUserToken'] = $ebayuser->token;
                	$this->public_info['MessageID'] = self::getMessageid();
                	$this->public_info['Carrier'] = $this->service_info['carrier_params']['Carrier'];
                	$this->public_info['PageSize'] = $this->service_info['carrier_params']['PageSize'];
                	$this->public_info['TrackCode'] = $order->customer_number;
                	
                	$request['GetAPACShippingLabelRequest'] = $this->public_info;
                }
                else{         
	                $this->public_info['APISellerUserID'] = $this->service_info[''];
	                $this->public_info['APISellerUserToken'] = $this->service_info[''];
	                $this->public_info['MessageID'] = $this->service_info[''];
	                $this->public_info['Carrier'] = $this->service_info[''];
	                $this->public_info['PageSize'] = $this->service_info->carrier_params->PageSize;
	                $this->public_info['TrackCode'] = $order->customer_number;
	
	                $request['GetAPACShippingLabelRequest'] = $this->public_info;
                }

                ##############################################################################################
                //数据组织完成，进行发送
                $response = $this->submitGate->mainGate($this->wsdl, $request, 'soap', 'GetAPACShippingLabel');
                if($response['error']){
                    $result[$order->order_id] = $response;
                    continue;
                }
                $response = $response['data'];
                $response = $response->GetAPACShippingLabelResult;
                //如果是错误信息
                if($response->Ack == 'Failure'){
                    $str = '';
                    if(count($response->NotificationList->Notification) > 1){
                        foreach($response->NotificationList->Notification as $k=>$v){
                            $str .= $v->Message.' ';
                        }
                    }else{
                        $str = $response->NotificationList->Notification->Message;
                    }
                    $order_info['error'] = 1;
                    $order_info['msg'] = $str;
                    $result[$order->order_id] = $order_info;
                    continue;
                } else if ($response->Ack == 'Success' || $response->Ack == 'Warning'){
                    $pdfUrl = CarrierAPIHelper::savePDF($response->Label,$this->puid,$this->service_info->carrier_code,0);
                    $pdf->addPDF($pdfUrl['filePath'],'all');
                    //添加订单标签打印时间
//                     $order->is_print_carrier = 1;
                    $order->print_carrier_operator = $this->puid;
                    $order->printtime = time();
                    $order->save();
                } else {
                    $this->_setLog($response,"doPrint");
                    $order_info['error'] = 1;
                    $order_info['msg'] = '异常错误';
                }
                /*todo 合并多个PDF  这里还需要进一步测试*/
                if(isset($pdfUrl)){
                    $pdf->merge('file', $pdfUrl['filePath']);
                } else {
                    $pdfUrl['filePath'] = '';
                }
                $order_info['data'] = ['pdfUrl'=>$pdfUrl['pdfUrl'],'errors'=>$result];
                $order_info['msg'] = '连接已生成,请点击并打印';
            }
        } catch (CarrierException $e){
            $order_info['error'] = 1;
            $order_info['msg'] = $e->msg();
        }
        return $order_info;
    }
    /**
     * @return				随机唯一字符串
     **/
    public static function getMessageid() {
        $pre='DENG'.base_convert(time(),10,36);
        return strtoupper($pre.'0'.sprintf('%015s',base_convert(mt_rand(1000000,9999999999),10,36).'0'.base_convert(mt_rand(1000000,9999999999),10,36)));
    }

    /**
     * @param int $user_is_login 用户是否已登录 1登录 0未登录
     * @param int $check_is_shipped 检查发货状态 1不检查 0检查
     * @param $order
     * @return array
     */
    private function _checkOrder($user_is_login = 1,$check_is_shipped = 0,$order){
        $check_info = $this->return_info;
        try{
            $check_result_info = CarrierAPIHelper::validate($user_is_login,$check_is_shipped,$order);
            $check_info['data'] = $check_result_info['data'];
        }catch (CarrierException $e){
            $check_info['error'] = 1;
            $check_info['msg'] = $e->msg();
        }
        return $check_info;
    }

    /**
     * 获取收件人信息
     * @return array
     */
    private function _getShipToAddress(){
        return [
            'Contact'    => $this->order_info->consignee,//收货人
            'Company'    => $this->order_info->consignee_company,//收货人公司
            'Street'     => $this->order_info->consignee_address_line1.'-'.$this->order_info->consignee_address_line2.'-'.$this->order_info->consignee_address_line3,
            'District'	 => $this->order_info->consignee_district,//区
            'City'       => $this->order_info->consignee_city,//城市
            'Province'   => $this->order_info->consignee_province,//省
            'CountryCode'=> $this->order_info->consignee_country_code,//国家代码
            'Postcode'   => $this->order_info->consignee_postal_code,//邮编
            'Phone'      => strlen($this->order_info->consignee_phone)>4?$this->order_info->consignee_phone:$this->order_info->consigee_mobile,//电话
            'Email'      => $this->order_info->consignee_email//邮箱
        ];
    }

    /**
     * 获取多商品信息
     * @param $order_detail
     * @return mixed
     */
    private function _getOrderDetail($order_detail){

        $oet = OdEbayTransaction::find()->select(['transactionid','itemid'])->where(['order_id'=>$this->order_info->order_id])->one();
        $transactionid = isset($oet->transactionid)?$oet->transactionid:'';

        foreach($this->order_item_info as $k=>$item){
            $tmpItem = [];
            $tmpItem['EBayItemID'] = isset($oet->itemid)?$oet->itemid:''; //ebay物品号
            $tmpItem['EBayTransactionID'] =empty($item['order_source_transactionid'])?$transactionid:$item['order_source_transactionid']; //交易号,拍卖的商品为0
            $tmpItem['EBayBuyerID'] = $this->order_info->source_buyer_user_id; //买家ID
            $tmpItem['SoldQTY'] = $item['ordered_quantity'];//卖出数量
            $tmpItem['PostedQTY'] = $item['quantity'];//寄货数量 不能为0
            $tmpItem['SalesRecordNumber'] = 0; //用户从eBay 上下载的时eBay 销售编号
            // $tmpItem['SalesRecordNumber'] = $item['order_source_srn']; //用户从eBay 上下载的时eBay 销售编号
            $tmpItem['OrderSalesRecordNumber'] = 0;
            $tmpItem['EBaySiteID'] = $this->order_info->order_source_site_id;//$item['ebayTransaction']['transactionsiteid'];
            $tmpItem['EBayMessage'] = $this->order_info->user_message;
            $tmpItem['PaymentDate'] = date('Y-m-d', $this->order_info->paid_time);//买家付款日期
            $tmpItem['SoldDate'] = date('Y-m-d', $this->order_info->order_source_create_time); //卖出日期
            $tmpItem['SoldPrice'] = $this->order_info->subtotal;//卖出总价
            $tmpItem['ReceivedAmount'] = $this->order_info->grand_total;//收到金额

            //SKU信息
            $tmpItem['SKU']['DeclaredValue'] = $this->data_info['DeclaredValue'][$k];// 商品申报价 报关信息
            $tmpItem['SKU']['Weight'] = empty($this->data_info['weight'][$k])?'':intval($this->data_info['weight'][$k])/1000; //商品重量 KG
            
//             if($this->service_info['carrier_params']['Carrier'] == 'CNPOST'){
                $tmpItem['SKU']['CustomsTitle'] = $this->data_info['Name'][$k];// 商品申报 中文名
//             }
            $tmpItem['SKU']['CustomsTitleEN'] = $this->data_info['EName'][$k];// 商品申报 英文名


            $tmpItem['SKU']['OriginCountryCode'] = 'CN';//缺
            $tmpItem['SKU']['OriginCountryName'] = 'China';//缺
            $order_detail['ItemList']['Item'] = $tmpItem;
        }
        return $order_detail;
    }

    /**
     * 异常数据日志记录
     * @param $data
     * @param $type
     */
    private function _setLog($data,$type){
        $msg = $type . PHP_EOL . json_encode($data) . PHP_EOL;
        $day = time(date('Y-m-d H',time()));
        $file_name = "/tmp/ebay_{$day}.log";
       error_log($msg,3,$file_name);
    }

    /**
     * 获取地址信息
     * @return				地址列表
     **/
    private static function getSendAddress($carrier_data)
    {
        /*******************组织地址相关数据****************************************************************************/
        $order_detail = [];//申请单个跟踪号时，提交信息的数组
        //揽收地址
        $order_detail['PickUpAddress']['CountryCode'] = $carrier_data['pickupaddress']['country'];
        unset($carrier_data['pickupaddress']['country']);
        unset($carrier_data['pickupaddress']['fax']);
        foreach($carrier_data['pickupaddress'] as $k=>$v){
            $order_detail['PickUpAddress'][ucfirst($k)] = $v;
        }
        //发货地址
        $order_detail['ShipFromAddress']['CountryCode'] = $carrier_data['shippingfrom']['country'];
        $order_detail['ShipFromAddress']['Contact'] = $carrier_data['shippingfrom']['contact_en'];
        $order_detail['ShipFromAddress']['Company'] = $carrier_data['shippingfrom']['company_en'];
        $order_detail['ShipFromAddress']['Province'] = $carrier_data['shippingfrom']['province_en'];
        $order_detail['ShipFromAddress']['City'] = $carrier_data['shippingfrom']['city_en'];
        $order_detail['ShipFromAddress']['District'] = $carrier_data['shippingfrom']['district_en'];
        $order_detail['ShipFromAddress']['Street'] = $carrier_data['shippingfrom']['street_en'];
        unset($carrier_data['shippingfrom']['country']);
        unset($carrier_data['shippingfrom']['contact']);
        unset($carrier_data['shippingfrom']['contact_en']);
        unset($carrier_data['shippingfrom']['company']);
        unset($carrier_data['shippingfrom']['company_en']);
        unset($carrier_data['shippingfrom']['province']);
        unset($carrier_data['shippingfrom']['province_en']);
        unset($carrier_data['shippingfrom']['city']);
        unset($carrier_data['shippingfrom']['city_en']);
        unset($carrier_data['shippingfrom']['district']);
        unset($carrier_data['shippingfrom']['district_en']);
        unset($carrier_data['shippingfrom']['street']);
        unset($carrier_data['shippingfrom']['street_en']);
        unset($carrier_data['shippingfrom']['fax']);
        foreach($carrier_data['shippingfrom'] as $k=>$v){
            $order_detail['ShipFromAddress'][ucfirst($k)] = $v;
        }
        //退货地址
        $order_detail['ReturnAddress']['CountryCode'] = $carrier_data['returnaddress']['country'];
        unset($carrier_data['returnaddress']['country']);
        unset($carrier_data['returnaddress']['fax']);
        foreach($carrier_data['returnaddress'] as $k=>$v){
            $order_detail['ReturnAddress'][ucfirst($k)] = $v;
        }
        return $order_detail;
    }

    /**
     * TNT或fedEx包裹信息
     * @param 单位 m
     * @return				包裹信息数组
     **/
    private static function getShippingPackage($service_info,$data_info){
        $carrier = $service_info['carrier_params']['Carrier'];
        $shipping_package = [];
        switch($carrier){
            case 'BPOST':
                $shipping_package['Length'] = 1.6;
                $shipping_package['Height'] = 1;
                $shipping_package['Width'] = 1.1;
                break;
            case 'TNT':
                $shipping_package['Length'] = $data_info['SPLength'] > 0 ? $data_info['SPLength'] : 2.4;
                $shipping_package['Height'] = $data_info['SPHeight'] > 0 ? $data_info['SPHeight'] : 1.5;
                $shipping_package['Width'] = $data_info['SPWidth'] > 0 ? $data_info['SPWidth'] : 1.2;
                break;
            case 'FedEx':
                $shipping_package['Length'] = 1.6;
                $shipping_package['Height'] = 1;
                $shipping_package['Width'] = 1.1;
                break;
            case 'UBI':
                $shipping_package['Length'] = $data_info['SPLength'] > 0 ? $data_info['SPLength'] : 0.05;
                $shipping_package['Height'] = $data_info['SPHeight'] > 0 ? $data_info['SPHeight'] : 0.05;
                $shipping_package['Width'] = $data_info['SPWidth'] > 0 ? $data_info['SPWidth'] : 0.05;
                break;
            default :
                $shipping_package['Length'] = 0.3;
                $shipping_package['Height'] = 0.3;
                $shipping_package['Width'] = 0.3;
                break;
        }
        return $shipping_package;
    }

    private static function getService($code){

        switch($code){
            case 'CNPOST':
                $service = 'EPACK';
                break;
            case 'TNT':
                $service = 'EXPR_15N';
                break;
            case 'FEDEX':
                $service = 'INT_EC';
                break;
            case 'UBI':
                $service = 'E-Parcel';
                break;
            default :
                $service = '';
                break;
        }

        return $service;
    }
}