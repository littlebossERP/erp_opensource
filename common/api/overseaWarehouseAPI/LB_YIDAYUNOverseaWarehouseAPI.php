<?php
namespace common\api\overseaWarehouseAPI;
use eagle\modules\carrier\models\SysCarrierAccount;
use eagle\modules\carrier\models\SysShippingService;
use eagle\modules\carrier\helpers\CarrierAPIHelper;
use eagle\modules\order\models\OdOrder;
use common\helpers\Helper_xml;
use eagle\modules\carrier\helpers\CarrierException;
use eagle\modules\order\models\OdOrderShipped;
use common\helpers\Helper_Curl;
/*+----------------------------------------------------------------------
| 小老板
+----------------------------------------------------------------------
| Copyright (c) 2011 http://www.xiaolaoban.cn All rights reserved.
+----------------------------------------------------------------------
| Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
+----------------------------------------------------------------------
| Author: lrq
+----------------------------------------------------------------------
| Create Date: 2016-07-11
+----------------------------------------------------------------------
 */

/**
 +------------------------------------------------------------------------------
 * 易达云海外仓物流商API
 +------------------------------------------------------------------------------
 * @category	vendors
 * @package		vendors/overseaWarehouseAPI
 * @subpackage  Exception
 * @author		dwg
 * @version		1.0
 +------------------------------------------------------------------------------
 */
class LB_YIDAYUNOverseaWarehouseAPI extends BaseOverseaWarehouseAPI
{
//	static private $customer_id = '';
	static private $auth = '';
	static private $key = '';
    static private $url = '';

    static private $userName = '';
    static private $password = '';

	private $soapClient = null;

	public function __construct()
	{
		if(isset(\Yii::$app->params["currentEnv"]) and \Yii::$app->params["currentEnv"]=='production' ) {
            //获取仓库信息接口是直接写地址的。没有用self::$url
            self::$url = 'http://wms.omniselling.cn/omniv4/webservice';
        }
        else {
            self::$url = 'http://wms.omniselling.net:48080/omniv4/webservice';
        }
	}
	
	/**申请订单号**/
	function getOrderNO($data)
	{
        try
        {
            //odOrder表内容
            $order = $data['order'];
            $customer_number = $data['data']['customer_number'];
            $extra_id = isset($data['data']['extra_id'])?$data['data']['extra_id']:'';
            //用户在确认页面提交的数据
            $form_data = $data['data'];

            //对当前条件的验证，如果订单已存在，则报错，并返回当前用户Puid
            $checkResult = CarrierAPIHelper::validate(1,0,$order,$extra_id,$customer_number);
            $puid = $checkResult['data']['puid'];

            //获取物流商信息、运输方式信息等
            $info = CarrierAPIHelper::getAllInfo($order);
            $service = $info['service'];
            $account = $info['account'];
            $api_params = $account->api_params;
            $carrier_params = $service->carrier_params;

            //获取收件地址街道
            $addressAndPhoneParams = array(
                'address' => array(
                    'consignee_address_line1_limit' => 100,
                    'consignee_address_line2_limit' => 100,
                    'consignee_address_line3_limit' => 100,
                ),
                'consignee_district' => 1,
                'consignee_county' => 1,
                'consignee_company' => 1,
                'consignee_phone_limit' => 50
            );

            $addressAndPhone = CarrierAPIHelper::getCarrierAddressAndPhoneInfo($order, $addressAndPhoneParams);


            //整理提交订单参数
            $request = array();
            $request = [
                'poNumber' => $order->order_source_order_id,    //平台订单号
                'currency' => empty($form_data['currency'])?'':$form_data['currency'],   //货币单位
                'IsChecked' => false,
                'warehouseId' => $service->third_party_code,    //目的仓库ID
                'courierAccountId' => $service->shipping_method_code,   //物流ID
                'shippingAddress' => [  //收件人地址
                    'name'=>$order->consignee,  //收件人,
                    'companyName'=>$order->consignee_company,   //收件人公司
                    'phone'=>empty($order->consignee_mobile) ? $order->consignee_phone : $order->consignee_mobile,  //收件人电话
                    'email'=>$order->consignee_email,   //收件邮箱
                    'country'=>$order->consignee_country_code,   //收件国家简码
                    'provState'=>$order->consignee_province,   //收件州
                    'city'=>$order->consignee_city,   //收件城市
                    'addressLine1'=>$addressAndPhone['address_line1'],   //收件地址1
                    'addressLine2'=>$addressAndPhone['address_line2'],   //收件地址2
                    'postalCode'=>$order->consignee_postal_code,   //收件邮编
                ],
            ];

            $orderTotal = 0;    //订单总价格
            $items = array();
            foreach ($order->items as $j=>$vitem)
            {
                if(empty($form_data['oversea_sku'][$j])){

                    return self::getResult(1, '', '错误信息：商品编号必填！');
                }

                $items[$j]= [
                        // 'channelSku'=>'',
                        'sku'=>$form_data['oversea_sku'][$j],
                        'description'=> empty($form_data['description'][$j])?'':$form_data['description'][$j],
                        'quantity'=>empty($form_data['quantity'][$j])?$vitem->quantity:$form_data['quantity'][$j],
                        'unitPrice'=>empty($form_data['unitPrice'][$j])?'':$form_data['unitPrice'][$j],
                        // 'channelItemId'=>'',
                    ];
                $orderTotal += $form_data['unitPrice'][$j] * $form_data['quantity'][$j];    //总价格
            }

            $request['orderTotal'] = $orderTotal;
            $request['items'] = $items;

            $requestData = array();
            $requestData['userName'] = $api_params['userName'];
            $requestData['password'] = $api_params['password'];
            $requestData['data'] = $request;
            $req_body = json_encode($requestData,true);

            $action_url = '/createOrderEx';
            $req_url = self::$url.$action_url;
            $get_head[] = "Content-Type: application/json;charset=UTF-8";

            $response = Helper_Curl::post($req_url,$req_body,$get_head);
            $responseData = json_decode($response,true);
            if(empty($responseData['errors'])){
                //上传成功后进行数据的保存 订单，运输服务，客户号，订单状态，跟踪号(选填),returnNo(选填,要传数组，传字符串无效)
                $r = CarrierAPIHelper::orderSuccess($order,$service,$responseData['data']['businessNum'],OdOrder::CARRIER_WAITING_GETCODE);
                return self::getResult(0,$r,'货代系统订单号:'.$responseData['data']['businessNum']);
            }
            else{
                return self::getResult(1, '', '错误code：'.$responseData['errors'][0]['code'].'；<br/>错误信息：'.$responseData['errors'][0]['desc']);
            }
        }
        catch(CarrierException $e) {
            return self::getResult(1,'',$e->msg());
        }

	}


	/**订单交运/审核订单**/
	function doDispatch($data){
        return self::getResult(1,'','物流接口不支持交运');
	}


	/**获取跟踪号**/
	function getTrackingNO($data){
        try {
            //odOrder表内容
            $order = $data['order'];
            $customer_number = $order->customer_number;

            //对当前条件的验证，如果订单不存在，则报错，并返回当前用户Puid
            $checkResult = CarrierAPIHelper::validate(1,1,$order);
            $puid = $checkResult['data']['puid'];

            //获取物流商信息、运输方式信息等
            $info = CarrierAPIHelper::getAllInfo($order);
            $service = $info['service'];
            $account = $info['account'];
            $api_params = $account->api_params;

            $requestData = array();
            $requestData['userName'] = $api_params['userName'];
            $requestData['password'] = $api_params['password'];
            //  $requestData['data'] = 'ORD0014634536';    //测试有跟踪号返回的货代系统订单号
            $requestData['data'] = $customer_number;    //这里的customer_number 存放的是货代系统订单号

            $req_body = json_encode($requestData,true);

            $action_url = '/getTrackinginfoEx';
            $req_url = self::$url.$action_url;
            $get_head[] = "Content-Type: application/json;charset=UTF-8";

            $response = Helper_Curl::post($req_url,$req_body,$get_head);
            $responseData = json_decode($response,true);

            if(empty($responseData['errors'])){

                $shipped = OdOrderShipped::find()->where(['order_id'=>$order->order_id,'customer_number'=>$customer_number])->one();
                if(empty($shipped)){
                    return self::getResult(1, '', '网络异常，请稍后再试！');
                }
                $shipped->tracking_number = $responseData['data'][0]['trackingNumber'];
                $shipped->save();
                $order->tracking_number = $shipped->tracking_number;
                $order->save();
                return self::getResult(0,'', '物流跟踪号：'.$responseData['data'][0]['trackingNumber']);
            }
            else{
                return self::getResult(1, '', '错误code：'.$responseData['errors'][0]['code'].'；<br/>错误信息：'.$responseData['errors'][0]['desc']);
            }
        }
        catch(CarrierException $e) {
            return self::getResult(1,'',$e->msg());
        }

    }


	
	/**订单取消**/
	function cancelOrderNO($data){
        return self::getResult(1,'','暂时未对接取消订单接口。请联系客服！');
	}
	
	/**订单打印**/
    function doPrint($data){
        return self::getResult(1,'','该物流商不支持打印订单');
	}

    //用来判断是否支持打印
    public static function isPrintOk(){
        return false;
    }

    //重新发货
    function Recreate($data){
        return self::getResult(1,'','该物流商不支持重新发货');
    }


    /**
     * 获取"头程"运输方式列表
     *      头程：其实就是交给海外物流商之前，交给国内的物流商运输
     */
    public function getshippingmethod($userName,$password){
        try {
            
            $arr = array();
            $arr['userName'] = $userName;
            $arr['password'] = $password;
            $req_body = json_encode($arr,true);

            $action_url = '/listRevCourierAccountInfo';
            $req_url = self::$url.$action_url;
            $get_head[] = "Content-Type: application/json;charset=UTF-8";

            $response = Helper_Curl::post($req_url,$req_body,$get_head);

            return $response;


        }catch (CarrierException $e){
            return self::getResult(1,'',$e->msg());
        }
    }


    /**
     * 获取公共仓库列表信息
     */
    public function getListPublicWarehouseInfo($userName,$password){
        try {
 
            $arr = array();
            $arr['userName'] = $userName;
            $arr['password'] = $password;
            $req_body = json_encode($arr,true);

            $action_url = '/listPublicWarehouseInfo';
            $req_url = self::$url.$action_url;
            $get_head[] = "Content-Type: application/json;charset=UTF-8";

            $response = Helper_Curl::post($req_url,$req_body,$get_head);

            return $response;


        }catch (CarrierException $e){
            return self::getResult(1,'',$e->msg());
        }

    }


    /**
     * 获取仓库物流信息
     */
    public function getListWarehouseCourierInfo($userName,$password,$warehouseId){
        try {
 
            $arr = array();
            $arr['userName'] = $userName;
            $arr['password'] = $password;
            $arr['data'] = $warehouseId;  //仓库ID
            $req_body = json_encode($arr,true);

            $action_url = '/listWarehouseCourierInfo';
            $req_url = self::$url.$action_url;
            $get_head[] = "Content-Type: application/json;charset=UTF-8";

            $response = Helper_Curl::post($req_url,$req_body,$get_head);

            return $response;

        }
        catch (CarrierException $e){
            return self::getResult(1,'',$e->msg());
            }

        }

	
	/**
	 * 用于验证物流账号信息是否真实
	 * $data 用于记录所需要的认证信息
	 *
	 * return array(is_support,error,msg)
	 * 			is_support:表示该货代是否支持账号验证  1表示支持验证，0表示不支持验证
	 * 			error:表示验证是否成功	1表示失败，0表示验证成功
	 * 			msg:成功或错误详细信息
	 */
//	public function getVerifyCarrierAccountInformation($data)
//	{
//		$result = array('is_support'=>1,'error'=>1);
//
//		try
//		{
//	    	$postdata['dohCode'] = '00001';
//	    	$response = $this->SendRequest('cancel', $postdata, null, $data);
//
//	    	if($response['ack'] == 'ERR' && $response['code'][0] == '订单记录不存在')
//	    	    $result['error'] = 0;
//		}
//		catch(CarrierException $e){}
//
//		return $result;
//	}



	/**
	 * 获取海外仓库存列表
	 * 
	 * @param 
	 * 			$data['accountid'] 			表示账号小老板对应的账号id
	 * 			$data['warehouse_code']		表示需要的仓库ID
	 * @return 
	 */

	function getOverseasWarehouseStockList($data = array()){

		//认证信息
 
		$api_params['userName'] = $data['api_params']['userName'];
		$api_params['password'] = $data['api_params']['password'];

        $requestData = array();
        $requestData['userName'] = $api_params['userName'];
        $requestData['password'] = $api_params['password'];
        $requestData['data']['sku'] = '*';  //输入*，表示返回所有 SKU 库存信息。
        $requestData['data']['warehouseId'] = $data['warehouse_code'];

        $req_body = json_encode($requestData,true);
        $action_url = '/queryInventoryEx';
        $req_url = 'http://wms.omniselling.cn/omniv4/webservice'.$action_url;
        $get_head[] = "Content-Type: application/json;charset=UTF-8";

        $response = Helper_Curl::post($req_url,$req_body,$get_head);
        $responseData = json_decode($response,true);

		$resultStockList = array();
        if(empty($responseData['errors'])){
            foreach ($responseData['data'] as $valList){
                $resultStockList[$valList['sku']] = array(
                    'sku'=>$valList['sku'],
                    'productName'=>'',
                    'stock_actual'=>$valList['totalInventory'],			//实际库存
                    'stock_reserved'=>$valList['forOutboundInventory'],	//占用库存
                    'stock_pipeline'=>0,	                            //在途库存
                    'stock_usable'=>$valList['qty'],	                //可用库存
                    'warehouse_code'=>$valList['warehouseId']		    //仓库代码
                );
            }
        }

		return self::getResult(0, $resultStockList ,'');
	}




}
?>