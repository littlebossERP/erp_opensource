<?php
/*+----------------------------------------------------------------------
| 小老板
+----------------------------------------------------------------------
| Copyright (c) 2011 http://www.xiaolaoban.cn All rights reserved.
+----------------------------------------------------------------------
| Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
+----------------------------------------------------------------------
| Author: qfl <772821140@qq.com>
+----------------------------------------------------------------------
| Create Date: 2015-3-9
+----------------------------------------------------------------------
 */
namespace common\api\carrierAPI;
use eagle\modules\carrier\models\SysCarrierAccount;
use common\helpers\Helper_Array;
use common\api\carrierAPI\BaseCarrierAPI;
use eagle\modules\carrier\models\SysShippingService;
use eagle\modules\order\models\OdOrderShipped;
use eagle\modules\carrier\helpers\CarrierAPIHelper;
use eagle\modules\order\models\OdOrder;
use yii\base\Exception;
use common\helpers\Helper_Curl;
use eagle\modules\carrier\helpers\CarrierException;
 

/**
+------------------------------------------------------------------------------
 * SF接口业务逻辑类
+------------------------------------------------------------------------------
 * @category    Interface
 * @package     vendors/CarrierAPI/
 * @subpackage  Exception
 * @author      qfl
 * @version     1.0
+------------------------------------------------------------------------------
 */
class LB_SFCarrierAPI extends BaseCarrierAPI
{
    public static $soapClient=null;
    public $wsdl = null;
    public static $authtoken=null;
    public static $authId = null;
    public static $operate = null;
    public static $service_id = null;

    public function __construct(){
    }

    public function getOrderNO($data){
        try{
            //odOrder表内容
            $order = $data['order'];
            //用户在确认页面提交的数据
            $form_data = $data['data'];

            //重复发货 添加不同的标识码
            $extra_id = isset($data['data']['extra_id'])?$data['data']['extra_id']:'';
            $customer_number = $data['data']['customer_number'];

            if(isset($data['data']['extra_id'])){
                if($extra_id == ''){
                    return self::getResult(1, '', '强制发货标识码，不能为空');
                }
            }

            //对当前条件的验证，如果订单已存在，则报错，并返回当前用户Puid
            $checkResult = CarrierAPIHelper::validate(1,0,$order,$extra_id,$customer_number);
            $puid = $checkResult['data']['puid'];

            //获取物流商信息、运输方式信息等
            $info = CarrierAPIHelper::getAllInfo($order);
            $service = $info['service'];
            $account = $info['account'];
            
            //获取到帐号中的认证参数
            $api_params = $account->api_params;
            $ret = $this->GetAuthInfo(null, $api_params);
            if(!empty($ret['error']) && $ret['error'] == 1) 
                return self::getResult(1, '', $ret['msg']);
            
            //按照设置，当邮箱大于50时，是否清空邮箱
            $tmpEmail = $order->consignee_email;
            if(strlen($tmpEmail) > 50){
            	if(!empty($account['api_params']['emailTooLong'])){
            		if($account['api_params']['emailTooLong'] == 'Y'){
            			$tmpEmail = '';
            		}
            	}
            }
            
            $tmp_is_use_mailno = empty($service['carrier_params']['is_use_mailno']) ? '' : $service['carrier_params']['is_use_mailno'];
            $vat_code = empty($service['carrier_params']['vat_code']) ? '' : $service['carrier_params']['vat_code'];

            $from = $info['senderAddressInfo']['shippingfrom'];
            if(empty($from))
            	return self::getResult(1,'','地址信息没有设置好，请到相关的货代设置地址信息');
            
            $phone = empty($order->consignee_phone) ? $order->consignee_mobile : $order->consignee_phone;
            //当是eBay订单时，由于eBay不会把电话返回到小老板，导致电话为空，所以默认为8个0
            if(isset($order->order_source) && $order->order_source == 'ebay' && empty($phone))
                $phone = '00000000';

            //澳大利亚，州名需转为州代码
        	if($order->consignee_country_code == 'AU'){
        		$tmpProvince = strtoupper($order->consignee_province);
        		
        		switch ($tmpProvince){
        			case 'NEW SOUTH WALES':
        				$province = 'NSW';
        				break;
        			case 'WESTERN AUSTRALIA':
        				$province = 'WA';
        				break;
        			case 'VICTORIA':
        				$province = 'VIC';
        				break;
        			case 'QUEENSLAND':
        				$province = 'QLD';
        				break;
        			case 'TASMANIA':
        				$province = 'TAS';
        				break;
        			case 'SOUTH AUSTRALIA':
        				$province = 'SA';
        				break;
        			case 'NORTHERN TERRITORY':
        				$province = 'NT';
        				break;
        			case 'AUSTRALIAN CAPITAL TERRITORY':
        				$province = 'ACT';
        				break;
        			default:
        				$province = $order->consignee_province;
        				break;
        		}
        	}
        	//法国没有省份，直接用城市来替换
        	else if(($order->consignee_country_code == 'FR') && empty($order->consignee_province)){
				$province = $order->consignee_city;
			}
        	else{
        		$province = $order->consignee_province;
        	}
        	
        	$tmpconsignee_country_code = $order->consignee_country_code;
        	//英国国家简码
        	if($tmpconsignee_country_code == 'UK'){
        		$tmpconsignee_country_code = 'GB';
        	}

            $addressAndPhoneParams = array(
                'address' => array(
                    'consignee_address_line1_limit' => 100,
                    'consignee_address_line2_limit' => 100,
                    'consignee_address_line3_limit' => 100,
                ),
                'consignee_district' => 1,
                'consignee_county' => 1,
                'consignee_company' => 1,
                'consignee_phone_limit' => 100
            );
            $addressAndPhone = CarrierAPIHelper::getCarrierAddressAndPhoneInfo($order, $addressAndPhoneParams);


            $postdata_top = [
                'orderid'=>$customer_number,//客户订单号
                'express_type'=>$service->shipping_method_code,//此处传产品代码目前支持如下A1：欧洲小包挂号*
                'j_company'=>empty($from['company_en'])?$from['company']:$from['company_en'],//寄件方公司名称
                'j_contact'=>empty($from['contact_en'])?$from['contact']:$from['contact_en'],//寄件方联系人
                'j_tel'=>empty($from['phone'])?$from['mobile']:$from['phone'],//寄件方联系电话
                'j_mobile'=>$from['mobile'],//寄件方手机
                'j_address'=>(empty($from['district_en'])?$from['district']:$from['district_en']).(empty($from['street_en'])?$from['street']:$from['street_en']),//寄件方详细地址
                'j_province'=>empty($from['province_en'])?$from['province']:$from['province_en'],//寄件方所在省
                'j_city'=>empty($from['city_en'])?$from['city']:$from['city_en'],//寄件方所属城市
                'j_country'=>$from['country'],//默认为CN
                'j_post_code'=>$from['postcode'],//寄件人邮编
                'd_company'=>$order->consignee_company,//收件人公司名
                'd_contact'=>str_replace("\"","'",$order->consignee),//收件人名*
                'd_tel' => $phone,//收件人联系电话
                'd_mobile'=>empty($order->consignee_mobile) ? $phone : $order->consignee_mobile,//收件人手机
                'd_email'=>($tmpEmail=='N/A')?'':$tmpEmail,//收件人邮箱(当申报总价值大于75欧元是必填)
                'operate_flag'=>1,//操作表示(0.新增，1.新增并确认订单,默认填写1，才会有跟踪号返回)
//                'd_address'=>$order->consignee_address_line1.'-'.$order->consignee_address_line2.'-'.$order->consignee_address_line3,//收件人地址*
                'd_address'=>str_replace("\"","'",$addressAndPhone['address_line1']),//收件人地址*
                'd_province'=>$province,//收件人州或省
                'd_city'=>$order->consignee_city,//收件人城市
                'd_country'=>$tmpconsignee_country_code,//目的国家，只支持国家标准二字代码*
                'd_post_code'=>$order->consignee_postal_code,//收件人邮编，如果有尽量填写以便派送
                'parcel_quantity'=>1,//货物件数默认为1即可
                'returnsign'=>$service['carrier_params']['returnsign'],//中转异常时是否需要退件，默认为N需要退件填写Y，退件会产生退件费请先联系业务员
                'length'=>$form_data['packing_Length'],
                'width'=>$form_data['packing_Width'],
                'height'=>$form_data['packing_Height'],
                'is_battery'=>$form_data['is_battery'],//是否有电
            ];

            //如果是正式环境 增加ERP客户标识码 测试环境添加这个字段会报错
            if(isset(\Yii::$app->params["currentEnv"]) and \Yii::$app->params["currentEnv"]=='production' ){
                $postdata_top['pit_code'] = 'XLB';
            }
            //澳大利亚，选传税号
            if($order->consignee_country_code == 'AU'){
            	$postdata_top['vat_code'] = $vat_code;
            }
            $product=array();
            $weight=0;
            foreach ($order->items as $j=>$vitem){
                $product[$j]=[
                    'name'=>str_replace("\"","'",$form_data['Name'][$j]),//货物中文品名，用于海关申报
                    'ename'=>str_replace("\"","'",$form_data['EName'][$j]),//货物英文申报品名，用于海关申报*
                    'count'=>empty($form_data['DeclarePieces'][$j])?$vitem->quantity:$form_data['DeclarePieces'][$j],//货物数量*
                    'unit'=>'PCE',//货物单位默认为PCE
                    'weight'=>empty($form_data['weight'][$j])?1:$form_data['weight'][$j]/1000,//货物单位重量
                    'amount'=>$form_data['DeclaredValue'][$j],//货物单价默认为美元*
                    'diPickName'=>str_replace("\"","'",$form_data['diPickName'][$j]),//配货名称
                ];
                //海关编码
                $product[$j]['hscode'] = empty($form_data['Hscode'][$j])?'':$form_data['Hscode'][$j];
                $weight+=$product[$j]['weight']*$product[$j]['count'];
            }
            $postdata_top['cargo_total_weight']=$weight;
            $postdata['order'] = $postdata_top;
            $postdata['cargo'] = $product;
            ###########################################################################
            //数据组织完毕 准备发送
            $soap=new self();
            \Yii::info('LB_SFCarrierAPI1 puid:'.$puid.'，order_id:'.$order->order_id.'  '.print_r($postdata,1), "file");
            self::$operate = 'orderService';

            $response = $soap->sfexpressService($postdata);

            //返回信息格式不正确
            if(!isset($response['ack']))
            {
                $str ='顺丰物流系统返回信息错误，请联系小老板客服';
                \Yii::info('LB_SFCarrierAPI4，puid:'.$puid.'，order_id:'.$order->order_id.'  '.print_r($response,1), "file");
                throw new CarrierException($str);
            }
            //上传失败
            if($response['ack']=='ERR'){
                $str ='';
                foreach($response['code'] as $v){
                    $str .= $v.'<br/>';
                }
                \Yii::info('LB_SFCarrierAPI2 puid:'.$puid.'，order_id:'.$order->order_id.'  '.print_r($response,1), "file");
                throw new CarrierException($str);
            }
            //上传成功
            if($response['ack']=='OK'){
                //判断跟踪号是用服务商单号，还是顺丰单号
                $agent_mailno = $tmp_is_use_mailno=='Y' ? $response['mailno'] : $response['agent_mailno'];
                
                //当没有跟踪号返回时，以顺丰单号为跟踪号
                $agent_mailno = empty($agent_mailno) ? $response['mailno'] : $agent_mailno;
                
                $success = "运单号（顺丰单号）：".$response['mailno'].'<br/>服务商单号：'.$response['agent_mailno'].'<br/>物流跟踪号：'.$agent_mailno;

                //上传成功后进行数据的保存 订单，运输服务，客户号，订单状态，跟踪号(选填),returnNo(选填,要传数组，传字符串无效)
                $r = CarrierAPIHelper::orderSuccess($order, $service, $postdata['order']['orderid'], OdOrder::CARRIER_WAITING_PRINT, $agent_mailno, ['OrderSign' => $response['mailno']]);

                /** start 给到getCarrierLabelApiPdf()使用到的数据 并记录在数据库中，准备赛兔模式打印*/
                $api_params = $account->api_params;
                $print_param = array();
                $print_param['carrier_code'] = $service->carrier_code;
                $print_param['api_class'] = 'LB_SFCarrierAPI';
                $print_param['service_id'] = $order->default_shipping_method_code;
                $print_param['token'] = $api_params['token'];
                $print_param['authId'] = $api_params['authId'];
                $print_param['order_id'] = $postdata['order']['orderid'];
                $print_param['carrier_params'] = $service->carrier_params;

                try{
                    CarrierApiHelper::saveCarrierUserLabelQueue($puid, $order->order_id, $postdata['order']['orderid'], $print_param);
                }catch (\Exception $ex){
                }
                /** end 给到getCarrierLabelApiPdf()使用到的数据 并记录在数据库中，准备赛兔模式打印*/

                return  BaseCarrierAPI::getResult(0,$r,$success);
            }
        }catch(CarrierException $e){return self::getResult(1,'',$e->msg());}
    }

    //用来判断是否支持打印
    public static function isPrintOk(){
        return true;
    }

    //订单标签打印
    //$params $dealtype 标签格式标识 1 没有配货单 2有配货单
    public function doPrint($data){ 
        try{
            $user=\Yii::$app->user->identity;
            if(empty($user))return BaseCarrierAPI::getResult(1, '', '用户登陆信息缺失,请重新登陆');
            $puid = $user->getParentUid();

            $orderid = '';
            foreach ($data as  $v) {
                $order = $v['order'];
                if(!isset($order->customer_number[1]))throw new CarrierException('订单列表中存在未上传的订单');
                $orderid .= $order->customer_number.',';
            }
            
            $shippingService = SysShippingService::find()->select(['carrier_params'])->where(['id'=>$v['order']['default_shipping_method_code'],'is_used'=>1])->one();
            if(empty($shippingService))throw new CarrierException('请检查该订单运输服务是否已开启');
            $params = $shippingService->carrier_params;
            $dealtype = empty($params['dealtype'])?1:$params['dealtype'];
            $pageversion = empty($params['pageversion']) ? 'a4' : $params['pageversion'];
            
            //获取账号、密钥
            $ret = $this->GetAuthInfo($v['order']);
             if(!empty($ret['error']) && $ret['error'] == 1) 
                return self::getResult(1, '', $ret['msg']);

            $postdata['@attributes']=['orderid'=>$orderid,'dealtype'=>$dealtype ,'pageversion'=>$pageversion];
            
            $soap=new self();
            self::$operate = 'OrderLabelPrintService';
            $response=$soap->sfexpressService($postdata);
            if($response['ack']=='OK'){
                //订单打印时间
                foreach($data as $v){
                    $order = $v['order'];
//                     $order->is_print_carrier = 1;
                    $order->print_carrier_operator = $puid;
                    $order->printtime = time();
                    $order->save();
                }
                //顺丰返回的直接是他们的pdf路径，所以直接使用即可
                return self::getResult(0,['pdfUrl'=>$response['pdfUrl']],'物流单已生成,请点击页面中打印按钮');
            }else{
                $str ='';
                foreach($response['code'] as $v){
                    $str .= $v.'<br/>';
                }
                foreach($data as $v){
                    $order = $v['order'];
                    $order->carrier_error = $str;
                    $order->save();
                }
                throw new CarrierException($str);
            }
        }catch(CarrierException $e){return self::getResult(1,'',$e->msg());}
    }


    /**
     * 获取API的打印面单标签
     * 这里需要调用接口货代的接口获取10*10面单的格式
     *
     * @param $SAA_obj			表carrier_user_label对象
     * @param $print_param		相关api打印参数
     * @return array()
     * Array
    (
    [error] => 0	是否失败: 1:失败,0:成功
    [msg] =>
    [filePath] => D:\wamp\www\eagle2\eagle/web/tmp_api_pdf/20160316/1_4821.pdf
    )
     */
    public function getCarrierLabelApiPdf($SAA_obj, $print_param){
        try {
            $puid = $SAA_obj->uid;

            $normal_params = empty($print_param['carrier_params']) ? array() : $print_param['carrier_params']; ////获取打印方式

            $dealtype = empty($normal_params['dealtype'])?1:$normal_params['dealtype'];
            $pageversion = 'label';     //代表10*10格式

            $postdata['@attributes']=['orderid'=>$print_param['order_id'],'dealtype'=>$dealtype ,'pageversion'=>$pageversion];
            
            //获取账号、密钥
            $ret = $this->GetAuthInfo(null, $print_param);
            if(!empty($ret['error']) && $ret['error'] == 1) 
                return self::getResult(1, '', $ret['msg']);
            
            $soap=new self();
            self::$operate = 'OrderLabelPrintService';
            $response=$soap->sfexpressService($postdata);
            if($response['ack']=='OK'){
                $pdf_respond = Helper_Curl::get($response['pdfUrl']);
                $pdfPath = CarrierAPIHelper::savePDF2($pdf_respond,$puid,$SAA_obj->order_id.$SAA_obj->customer_number."_api_".time());
                return $pdfPath;
            }
            else{
                return ['error'=>1, 'msg'=>'打印失败！错误信息：'.$response['code'][0], 'filePath'=>''];
            }

        }catch (CarrierException $e){
            return ['error'=>1, 'msg'=>$e->msg(), 'filePath'=>''];
        }
    }


    /*
     * 用来确定打印完成后 订单的下一步状态
     */
    public static function getOrderNextStatus(){
        return OdOrder::CARRIER_FINISHED;
    }

    //取消订单
    public function cancelOrderNO($data)
    {
        return self::getResult(1,'', '该物流商不支持此操作');
    }

    public function Recreate($data){
        return self::getResult(1,'', '该物流商不支持此操作');
    }

    //确认订单
    public function doDispatch($data)
    {
        return self::getResult(1,'', '该物流商不支持此操作');
    }

    //获取订单信息
    public function getTrackingNO($data){
        try{
            $order = $data['order'];
            if(!$order->customer_number)throw new CarrierException('请检查订单是否已经提交');

            $postdata['@attributes']=['orderid'=>$order->customer_number];
            
            //获取账号、密钥
            $ret = $this->GetAuthInfo($order);
             if(!empty($ret['error']) && $ret['error'] == 1) 
                return self::getResult(1, '', $ret['msg']);
            
            $soap=new self();
            self::$operate = 'OrderSearchService';
            $response=$soap->sfexpressService($postdata);
//            print_r($response);exit;
            if($response['ack']=='ERR'){
                $str ='';
                foreach($response['code'] as $v){
                    $str .= $v.'<br/>';
                }
                throw new CarrierException($str);
            }
            $coskybillcode = empty($response['coskybillcode'])?'':'<br/>转运单号:'.$response['coskybillcode'];
            $tracking_number_success_info = empty($response['coservehawbcode'])?'':'获取物流号成功！<br/>物流跟踪号(服务商单号):'.$response['coservehawbcode'];
            $mailno_info = '暂未物流跟踪号返回！<br/>运单号(顺丰单号):'.$response['mailno'];
            if(empty($response['coservehawbcode'])){
                $info = $mailno_info;
                $tracking_number = '';
            }else{
                $info = $tracking_number_success_info;
                $tracking_number = $response['coservehawbcode'];
            }
            $status = self::getStatus($response['oscode']);
            if($response['ack']=='OK'){
                $r = [
                    'coservehawbcode'=>$response['coservehawbcode'],//服务商单号
                    'oscode'=>$response['oscode'],//订单状态代码
                    'coskybillcode'=>$response['coskybillcode'],//转运单号
                    'mailno'=>$response['mailno'],//运单号(顺丰单号)
                ];
                //将返回回来的数据保存到return_no中方便以后使用
                $shipped = OdOrderShipped::find()->where(['order_id'=>$order->order_id,'customer_number'=>$order->customer_number])->one();
                $shipped->return_no = $r;
                $shipped->tracking_number = $tracking_number;
                $shipped->save();
                $order->tracking_number = $shipped->tracking_number;
                $order->save();
                return self::getResult(0,'', $info.'<br/>订单状态:'.$status.$coskybillcode);
            }
        }catch(CarrierException $e){return self::getResult(1,'',$e->msg());}
    }


    /**
     * 查询物流轨迹
     * @param   $data[0]]   物流跟踪号
     * @return  array
     */
    public function SyncStatus($data){
		global $CACHE;
    	$pendingSubOne = isset($CACHE['subOne'])?$CACHE['subOne'] : null;
    	
    	$ret=\Yii::$app->subdb->changeUserDataBase($pendingSubOne->puid);
    	$addinfo = json_decode($pendingSubOne->addinfo,true);
     
    	$orderModel = OdOrder::find()->where(['order_source_order_id'=>$addinfo['order_id']])->one();
    	
//        $postdata['@attributes']=['tracking_type'=>1,'method_type' => 1,'tracking_number' =>'994073026837'];//puid 2896 敦煌订单
        $postdata['@attributes']=['tracking_type'=>1,'method_type' => 1,'tracking_number' => $pendingSubOne->track_no ];
//        self::$service_id = $order->default_shipping_method_code;

        self::$service_id = 1000;   //1000为对接该接口而设的一个无意义的值，目的是为了成功通过构造函数
        //获取账号、密钥
        $ret = $this->GetAuthInfo( $orderModel);
        if(!empty($ret['error']) && $ret['error'] == 1) 
                return self::getResult(1, '', $ret['msg']);
        
        $soap=new self();
        self::$operate = 'RouteService';
        $response=$soap->sfexpressService($postdata);
        return $response;

    }
    
    //获取账号、密钥
    public function GetAuthInfo($order = null, $api_params = null)
    {
        try
        {
           if(isset(\Yii::$app->params["currentEnv"]) and \Yii::$app->params["currentEnv"]=='production' )
            {
                //正式场接口
                $this->wsdl = 'http://ibse.sf-express.com/CBTA/ws/sfexpressService?wsdl';
                
                if( isset($order) && empty(self::$service_id))
                    self::$service_id = $order->default_shipping_method_code;
                
                if(isset(self::$service_id) && self::$service_id == 1000)
                {
                    //查询轨迹等特殊情况，正式场测试账号
            		self::$authtoken='03575DAFA027565F74AEEEB2C4A995B6';
            		self::$authId='5740000657';
                }
                else 
                {
                    if( empty($api_params))
                    {
                        //获取物流商信息
                        $info = CarrierAPIHelper::getAllInfo($order);
                        $account = $info['account'];
                        //获取到帐号中的认证参数
                        $api_params = $account->api_params;
                    }
                    
                    self::$authtoken = $api_params['token'];
                    self::$authId = $api_params['authId'];
                    if(empty(self::$authtoken) || empty(self::$authId))throw new CarrierException('该订单所选运输方式没有分配可用物流商帐号');
                }
            }
           else 
            {
                //测试场接口
                $this->wsdl='http://ibu-ibse.sit.sf-express.com:1091/CBTA/ws/sfexpressService?wsdl';
                
                self::$authtoken='DBC0833D5E081D384230528172E3BDE8';
                self::$authId = '7550075493';
            }
            
            if(is_null(self::$soapClient)||!is_object(self::$soapClient)){
            	try {
            		set_time_limit(100);
            		ignore_user_abort(true);
            		
            		self::$soapClient=new \SoapClient($this->wsdl,array(true));	
            	}catch (Exception $e){
            		return self::getResult(1,'','网络连接故障<br/>'.$e->getMessage());
            	}
            }
            return self::getResult(0,'','');
        }
        catch(CarrierException $e)
        {
            return self::getResult(1,'',$e->msg());
        }
    }


    //呼叫api时api名的魔术处理
    //$inputStructMethodName 调用的接口名
    //$customerParameter 上传的数据
    public function __call($inputStructMethodName,$customerParameter) {
        try {

            $tmp = self::$soapClient->__getFunctions();
            if(is_array($tmp)) {
                foreach($tmp as $theValue) {
                    $pos = strpos(strtolower($theValue), strtolower($inputStructMethodName));
                    if($pos === false) {
                        continue;
                    } else {
                        return self::common($inputStructMethodName, $customerParameter);
                    }
                }

                //以上没有正常return说明没有找到指定方法
                throw new Exception('当前没有此服务方法，请检查方法名是否有误');
            } else {
                $pos = strpos($tmp, (string)$inputStructMethodName);
                if($pos === false)
                    throw new Exception('当前没有此服务方法，请检查方法名是否有误');
                else
                    return self::common($inputStructMethodName,$customerParameter);
            }
        } catch (Exception $e) {
            if($this->debug) {
                printf("检查方法时出错：<br />Message = %s",$e->__toString());
            }
            exit();
        }
    }

    public static function base64($str){ // base64转码

        return base64_encode($str);

    }

    public static function _md5($str){ // md5加密并转大写

        return strtoupper(md5($str));

    }
    private static function common($inputStructMethodName,$customerParameter) {
        try {
            $params = call_user_func_array(array(__NAMESPACE__.'\SF_Struct',self::$operate),$customerParameter);
            //生成verifyCode
            $md5Data = self::_md5($params['xml'].self::$authtoken);
            $verifyCode = self::base64($md5Data);
            $params['verifyCode'] = $verifyCode;
            
            //组织日志信息
            $info = 'puid:1';
            try{
            	if( self::$operate == 'orderService'){
	            	$user=\Yii::$app->user->identity;
	            	$puid = $user->getParentUid();
	            	$info = 'puid:'.$puid.'，order_id:'.$customerParameter[0]['order']['orderid'];
            	}
            }
            catch(\Exception $ex){}
            
            \Yii::info('LB_SFCarrierAPI3 '.$info.'  '.print_r($params,1).'  '.$inputStructMethodName, "file");
            
            $result = self::$soapClient->__soapCall($inputStructMethodName,$params);
            
            \Yii::info('LB_SFCarrierAPI6 '.$info.'，  '.print_r($result,1), "file");
            
            $status = 0;     //标记创建订单时，是否重新查询订单信息，0否1是
            //判断$result 是否xml样式
            $xml_parser = xml_parser_create();
            if( xml_parse($xml_parser,$result,true)) 
            {
                $result = self::outputStruct($result);
                
                \Yii::info('LB_SFCarrierAPI7 '.$info.'，  '.print_r($result,1), "file");
                
                if(self::$operate == 'orderService' && !empty($result['ack']) && $result['ack']=='ERR')
                {
                	$str = ' ';
                	foreach($result['code'] as $v)
                		$str .= $v.' ';
                    
                    if(stripos($str, '客户订单号存在重复') != false)
                        $status = 1;
                }
            }
            else if( self::$operate == 'orderService')
                $status = 1;
            
            //重新查询订单是否已存在，已存在则不需再上传
            if( $status == 1)
            {
                $user=\Yii::$app->user->identity;
                $puid = $user->getParentUid();
                \Yii::info('LB_SFCarrierAPI5 '.$info.'  '.print_r($result,1), "file");
                
                $postdata['@attributes']=['orderid'=>$customerParameter[0]['order']['orderid']];
                
                $soap=new self();
                self::$operate = 'OrderSearchService';
                $response=$soap->sfexpressService($postdata);
                
                \Yii::info('LB_SFCarrierAPI8 '.$info.'  '.print_r($response,1), "file");
                
                if(!empty($response['ack']) && $response['ack']=='OK')
                {
                     if(!empty($response['oscode']) && $response['oscode'] == 'P')
                     {
                         if(empty($response['coservehawbcode']))
                            $response['agent_mailno'] = $response['mailno'];
                         else 
                             $response['agent_mailno'] = $response['coservehawbcode'];
                        $result = $response;
                     }
                }
                /*else
                	$result = false;*/
            }
            
            if(is_array($result) && !empty($result)) {
            	return $result;
            } else {
            	return false;
            }

        } catch (Exception $e) {
            if($this->debug) {
                printf("方法执行错误<br />Message = %s",$e->__toString());
            }
            exit();
        }
    }

    /**
     * xML转为数组
     */
    public static function xml_to_array($xml,$main_heading = '') {
        $deXml = simplexml_load_string($xml);
        $deJson = json_encode($deXml);
        $xml_array = json_decode($deJson,TRUE);
        if (! empty($main_heading)) {
            $returned = $xml_array[$main_heading];
            return $returned;
        } else {
            return $xml_array;
        }
    }


    /*
     * 获取查看轨迹返回状态信息
     */
    public static function getTrackStatus($key){
        $arr = [
            -9=>'系统错误',
            -102=>'运单不存在'
        ];
        if(array_key_exists($key,$arr))return $arr[$key];
        else return "物流商返回数据错误";
    }



    public static function outputStruct($str) 
    { 
        try 
        {
            //处理输出数据
            $dom = new \DOMDocument();
            $dom->loadXML($str);
            $result = array();
            if(self::$operate == 'RouteService'){   //如果是物流信息查询
                $isOk = $dom->getElementsByTagName("head")->item(0)->nodeValue;
            }
            else {
                $isOk = $dom->getElementsByTagName("Head")->item(0)->nodeValue;
            }
    
            if($isOk=='OK'){
                if(self::$operate == 'RouteService')    //如果是物流信息查询
                {
                    $country = Helper_Array::toHashmap(\eagle\models\SysCountry::find()->select(['country_zh','country_code'])->asArray()->all(), 'country_zh','country_code');
                    $xml_array = self::xml_to_array($str);
                    if (!is_array($xml_array)) {
                        $result['error'] = "1";
                        //    $result['trackContent'] = self::getTrackStatus($xml_array);
                        $result['trackContent'] = "物流商返回数据错误";
                        $result['referenceNumber']='';
                    }
                    else
                    {
                        if(!empty($xml_array['body'])){
                            $routeArr = $xml_array['body']['RouteResponse']['Route'];
                            $i =0;
                            foreach($routeArr as $route){
                                $result['trackContent'][$i]['createDate'] = '';
                                $result['trackContent'][$i]['createPerson'] = '';
                                $result['trackContent'][$i]['occurAddress'] = $route['@attributes']['acceptAddress'];
                                $result['trackContent'][$i]['occurDate'] = $route['@attributes']['acceptTime'];
                                $result['trackContent'][$i]['trackCode'] = '';
                                $result['trackContent'][$i]['trackContent'] = $route['@attributes']['remark'];
                                if($i===0){
                                    $destinationCountryCode = empty($route['@attributes']['acceptAddress'])?'1':$route['@attributes']['acceptAddress'];
                                }
                                $i++;
    
                            }
                            $result['error'] = "0";
                            $result['referenceNumber'] = $xml_array['body']['RouteResponse']['@attributes']['orderid'];
                            $result['trackingNumber'] = $xml_array['body']['RouteResponse']['@attributes']['mailno'];
                            foreach($country as $countryName=>$countryCode){
                                if($countryName == $destinationCountryCode){
                                    $result['destinationCountryCode'] = $countryCode;
                                    break;
                                }
                                else {
                                    $result['destinationCountryCode'] = '数据库找不到该目的国家('.$destinationCountryCode.')对应国家简码';
                                }
                            }
    
    
                        }
                        else{
                            $result['error'] = "1";
                            $result['trackContent'] = "暂时没有任何物流信息";
                            $result['referenceNumber'] = '';
    
                        }
                    }
    
                }
                else
                {
                    $err = $dom->getElementsByTagName("Body")->item(0)->childNodes->item(0);
                    if(!empty($err)){
                        foreach($err->attributes as $k=>$v)
                        {
                            $result[$k] = $v->nodeValue;
                        };
                        $result['ack'] = 'OK';
                    }
                    else{
                    	$result['ack'] = 'ERR';
                    }
                }
    
            }else if($isOk == 'ERR'){
                $err = $dom->getElementsByTagName("ERROR")->item(0);
                $code = $err->getAttribute('code');
                $codeArr = explode(',', $code);
                $errorInfo = self::code2error();
                if(!empty($codeArr) && count($codeArr)>0){
                    foreach($codeArr as $error){
                        $result['code'][] = empty($errorInfo[$error]) ? $error : $errorInfo[$error];//返回错误是否在我们定义好的错误数组中，否则直接返回$error
                    }
                }
                $result['ack'] = 'ERR';
            }
            return $result;
        }
        catch(CarrierException $e)
        {
        	return ['error'=>1, 'data'=>'', 'msg'=>$e->msg()];
        }
    }

    //返回订单状态
    public static function getStatus($code){
        $arr = [
            'A'=>'新建订单',
            'C'=>'已出货',
            'D'=>'已删除订单',
            'M'=>'平邮件',
            'P'=>'已申请投递',
            'V'=>'已收货',
        ];
        return $arr[$code];

    }

    public static function code2error(){
        $arr = [
            6101=>'请求数据缺少必选项 缺少必要参数',
            6102=>"<a href='/configuration/carrierconfig/index?tcarrier_code=lb_SF' target='_blank'>寄件方公司名称为空 关键字段校验不合法</a>",
            6103=>"<a href='/configuration/carrierconfig/index?tcarrier_code=lb_SF' target='_blank'>寄方联系人为空</a>",
            6106=>"<a href='/configuration/carrierconfig/index?tcarrier_code=lb_SF' target='_blank'>寄件方详细地址为空</a>",
            6107=>'到件方公司名称为空',
            6108=>'到件方联系人为空',
            6111=>'到件方地址为空',
            6112=>'到件方国家不能为空',
            6114=>'必须提供客户订单号',
            6115=>'到件方所属城市名称不能为空',
            6116=>'到件方所在县/区不能为空',
            6117=>'到件方详细地址不能为空',
            6118=>'订单号不能为空',
            6119=>'到件方联系电话不能为空',
            6120=>'快递类型不能为空',
            6121=>"<a href='/configuration/carrierconfig/index?tcarrier_code=lb_SF' target='_blank'>寄件方联系电话不能为空</a>",
            6122=>'筛单类别不合法',
            6123=>'运单号不能为空',
            6124=>'付款方式不能为空',
            6125=>'需生成电子运单,货物名称等不能为空',
            6126=>'月结卡号不合法',
            6127=>'增值服务名不能为空',
            6128=>'增值服务名不合法',
            6129=>'付款方式不正确',
            6130=>'体积参数不合法',
            6131=>'订单操作标识不合法',
            6132=>'路由查询方式不合法',
            6133=>'路由查询类别不合法',
            6134=>'未传入筛单数据',
            6135=>'未传入订单信息',
            6136=>'未传入订单确认信息',
            6137=>'未传入请求路由信息',
            6138=>'代收货款金额传入错误',
            6139=>'代收货款金额小于0错误',
            6140=>'代收月结卡号不能为空',
            6141=>'无效月结卡号,未配置代收货款上限',
            6142=>'超过代收货款费用限制',
            6143=>'是否自取件只能为1或2',
            6144=>'是否转寄件只能为1或2',
            6145=>'是否上门收款只能为1或2',
            6146=>'回单类型错误',
            6150=>'订单不存在',

            8000=>'报文 参数不合法 参数无效',
            8001=>'IP未授权 参数无效',
            8002=>'服务（功能）未授权',
            8003=>'查询单号超过最大限制',
            8004=>'路由查询条数超限制',
            8005=>'查询次数超限制',
            8006=>'已下单，无法接收订单确认请求',
            8007=>'此订单已经确认，无法接收订单确认请求',
            8008=>'此订单人工筛单还未确认，无法接收订单确认请求',
            8009=>'此订单不可收派, 无法接收订单确认请求。',
            8010=>'此订单未筛单, 无法接收订单确认请求。',
            8011=>'不存在该接入编码与运单号绑定关系',
            8012=>'不存在该接入编码与订单号绑定关系',
            8013=>'未传入查询单号',
            8014=>'校验码错误',
            8015=>'未传入运单号信息',
            8016=>'重复下单',
            8017=>'订单号与运单号不匹配',
            8018=>'未获取到订单信息',
            8019=>'订单已确认',
            8020=>'不存在该订单跟运单绑定关系',
            8021=>'接入编码为空',
            8022=>'校验码为空',
            8023=>'服务名为空',
            8024=>'未下单',
            8025=>'未传入服务或不提供该服务',
            8026=>'不存在的客户',
            8027=>'不存在的业务模板',
            8028=>'客户未配置此业务',
            8029=>'客户未配置默认模板',
            8030=>'未找到这个时间的合法模板',
            8031=>'数据错误，未找到模板',
            8032=>'数据错误，未找到业务配置',
            8033=>'数据错误，未找到业务属性',
            8034=>'重复注册人工筛单结果推送',
            8035=>'生成电子运单，必须存在运单号',
            8036=>'注册路由推送必须存在运单号',
            8037=>'已消单',
            8038=>'业务类型错误',
            8039=>"<a href='/configuration/carrierconfig/index?tcarrier_code=lb_SF' target='_blank'>寄方地址错误</a>",
            8040=>'到方地址错误',
            8041=>'寄件时间格式错误',
            8042=>'客户账号异常，请联系客服人员！',
            8043=>'该账号已被锁定，请联系客服人员！',
            8044=>'此订单已经处理中，无法接收订单修改请求',
            4001=>'系统发生数据错误或运行时异常',
            4002=>'报文解析错误',

            9000=>'身份验证失败',
            9001=>'客户订单号超过长度限制',
            9002=>'客户订单号存在重复',
            9003=>'客户订单号格式错误，只能包含数字和字母',
            9004=>'运输方式不能为空',
            9005=>'运输方式错误',
            9006=>'目的国家不能为空',
            9007=>'目的国家错误，请填写国家二字码',
            9008=>'收件人公司名超过长度限制',
            9009=>'收件人姓名不能为空',
            9010=>'收件人姓名超过长度限制',
            9011=>'收件人州或省超过长度限制',
            9012=>'收件人城市超过长度限制',
            9013=>'联系地址不能为空',
            9014=>'联系地址超过长度限制',
            9015=>'收件人手机号码超过长度限制',
            9016=>'收件人邮编超过长度限制',
            9017=>'收件人邮编只能是英文和数字',
            9018=>'重量数字格式不准确',
            9019=>'重量必须大于0',
            9020=>'重量超过长度限制',
            9021=>'是否退件填写错误，只能填写Y或N',
            9022=>'海关申报信息不能为空',
            9023=>'英文申报品名不能为空',
            9024=>'英文申报品名超过长度限制',
            9025=>'英文申报品名只能为英文、数字、空格、（）、()、，、,%',
            9026=>'申报价值必须大于0',
            9027=>'申报价值必须为正数',
            9028=>'申报价值超过长度限制',
            9029=>'申报品数量必须为正整数',
            9030=>'申报品数量超过长度限制',
            9031=>'中文申报品名超过长度限制',
            9032=>'中文申报品名必须为中文',
            9033=>'海关货物编号超过长度限制',
            9034=>'海关货物编号只能为数字',
            9035=>'收件人手机号码格式不正确',
            9036=>'服务商单号或顺丰单号已用完，请联系客服人员',
            9037=>"<a href='/configuration/carrierconfig/index?tcarrier_code=lb_SF' target='_blank'>寄件人姓名超过长度限制</a>",
            9038=>"<a href='/configuration/carrierconfig/index?tcarrier_code=lb_SF' target='_blank'>寄件人公司名超过长度限制</a>",
            9039=>"<a href='/configuration/carrierconfig/index?tcarrier_code=lb_SF' target='_blank'>寄件人省超过长度限制</a>",
            9040=>"<a href='/configuration/carrierconfig/index?tcarrier_code=lb_SF' target='_blank'>寄件人城市超过长度限制</a>",
            9041=>"<a href='/configuration/carrierconfig/index?tcarrier_code=lb_SF' target='_blank'>寄件人地址超过长度限制</a>",
            9042=>"<a href='/configuration/carrierconfig/index?tcarrier_code=lb_SF' target='_blank'>寄件人手机号码超过长度限制</a>",
            9043=>"<a href='/configuration/carrierconfig/index?tcarrier_code=lb_SF' target='_blank'>寄件人手机号码格式不准确</a>",
            9044=>"<a href='/configuration/carrierconfig/index?tcarrier_code=lb_SF' target='_blank'>寄件人邮编超过长度限制</a>",
            9045=>"<a href='/configuration/carrierconfig/index?tcarrier_code=lb_SF' target='_blank'>寄件人邮编只能是英文和数字</a>",
            9046=>'不支持批量操作',
            9047=>'批量交易记录数超过限制',
            9048=>'此订单已确认，不能再操作',
            9049=>'此订单已收货，不能再操作',
            9050=>'此订单已出货，不能再操作',
            9051=>'此订单已取消，不能再操作',
            9052=>'收件人电话超过长度限制',
            9053=>'收件人电话格式不正确',
            9054=>"<a href='/configuration/carrierconfig/index?tcarrier_code=lb_SF' target='_blank'>寄件人电话超过长度限制</a>",
            9055=>"<a href='/configuration/carrierconfig/index?tcarrier_code=lb_SF' target='_blank'>寄件人电话格式不正确</a>",
            9056=>'货物件数必须为正整数',
            9057=>'货物件数超过长度限制',
            9058=>"<a href='/configuration/carrierconfig/index?tcarrier_code=lb_SF' target='_blank'>寄件人国家错误，请填写国家二字码，默认为CN</a>",
            9059=>'货物单位超过长度限制，默认为PCE',
            9060=>'货物单位重量格式不正确',
            9061=>'货物单位重量超过长度限制',
            9062=>'该运输方式暂时不支持此国家的派送，请选择其他派送方式',
            9063=>'当前运输方式暂时不支持该国家此邮编的派送，请选择其他派送方式！',
            9064=>'该运输方式必须输入邮编',
            9065=>"<a href='/configuration/carrierconfig/index?tcarrier_code=lb_SF' target='_blank'>寄件人国家国家不能为空</a>",
            9066=>"<a href='/configuration/carrierconfig/index?tcarrier_code=lb_SF' target='_blank'>寄件人公司名不能为空</a>",
            9067=>"<a href='/configuration/carrierconfig/index?tcarrier_code=lb_SF' target='_blank'>寄件人公司名不能包含中文</a>",
            9068=>"<a href='/configuration/carrierconfig/index?tcarrier_code=lb_SF' target='_blank'>寄件人姓名不能为空</a>",
            9069=>"<a href='/configuration/carrierconfig/index?tcarrier_code=lb_SF' target='_blank'>寄件人姓名不能包含中文</a>",
            9070=>"<a href='/configuration/carrierconfig/index?tcarrier_code=lb_SF' target='_blank'>寄件人城市不能为空</a>",
            9071=>"<a href='/configuration/carrierconfig/index?tcarrier_code=lb_SF' target='_blank'>寄件人城市不能包含中文</a>",
            9072=>"<a href='/configuration/carrierconfig/index?tcarrier_code=lb_SF' target='_blank'>寄件人地址不能为空</a>",
            9073=>"<a href='/configuration/carrierconfig/index?tcarrier_code=lb_SF' target='_blank'>寄件人地址不能包含中文</a>",
            9074=>"<a href='/configuration/carrierconfig/index?tcarrier_code=lb_SF' target='_blank'>寄件人邮编不能为空</a>",
            9075=>"<a href='/configuration/carrierconfig/index?tcarrier_code=lb_SF' target='_blank'>寄件人邮编不能包含中文</a>",
            9076=>'收件人公司名不能为空',
            9077=>'收件人公司名不能包含中文',
            9078=>'收件人城市不能为空',
            9079=>'收件人城市不能包含中文',
            9080=>'查询类别不正确，合法值为：1（运单号），2（订单号）',
            9081=>'查询号不能不能为空。',
            9082=>'查询方法错误，合法值为：1（标准查询）',
            9083=>'查询号不能超过10个。注：多个单号，以逗号分隔。',
            9084=>'收件人电话不能为空',
            9085=>'收件人姓名不能包含中文',
            9086=>'英文申报品名必须为英文',
            9087=>'收件人手机不能包含中文',
            9088=>'收件人电话不能包含中文',
            9089=>"<a href='/configuration/carrierconfig/index?tcarrier_code=lb_SF' target='_blank'>寄件人电话不能包含中文</a>",
            9090=>"<a href='/configuration/carrierconfig/index?tcarrier_code=lb_SF' target='_blank'>寄人手机不能包含中文</a>",
            9091=>'海关货物编号不能为空',
            9092=>'联系地址不能包含中文',
            9093=>'当总申报价值超过75欧元时【收件人邮箱】不能为空',
            9094=>'收件人邮箱超过长度限制',
            9095=>'收件人邮箱格式不正确',
            9096=>"<a href='/configuration/carrierconfig/index?tcarrier_code=lb_SF' target='_blank'>寄件人省不能包含中文</a>",
            9097=>'收件人州或省超不能包含中文',
            9098=>'收件人邮编不能包含中文',
            9099=>'英文申报品名根据服务商要求，申报品名包含disc、speaker、power bank、battery、magne禁止运输，请选择其他运输方式！',

            9100=>"<a href='/configuration/carrierconfig/index?tcarrier_code=lb_SF' target='_blank'>寄件人省不能为空</a>",
            9101=>'收件人州或省不能为空',
            9102=>'收件人邮编只能为数字',
            9103=>'收件人邮编只能为4个字节',
            9104=>'【收件人邮编】,【收件人城市】,【州╲省】不匹配',
            9105=>'申报价值大于200美元时，【海关货物编号】不能为空！',
            9106=>'收件人州或省不正确',
            9107=>"<a href='/configuration/carrierconfig/index?tcarrier_code=lb_SF' target='_blank'>寄件人邮编只能包含数字</a>",
            9108=>'收件人邮编格式不正确',
            9109=>'【州╲省】美国境外岛屿、区域不支持派送！',
            9110=>'【州╲省】APO/FPO军事区域不支持派送！',
            9111=>'客户EPR不存在！',
            9112=>'【配货备注】长度超过限制！',
            9113=>'【配货名称】不能包含中文！',
            9114=>'【配货名称】长度超过限制！',
            9115=>'【包裹长（CM）】数字格式不正确！',
            9116=>'【包裹长（CM）】不能超过4位！',
            9117=>'【包裹长（CM）】必须大于0！',
            9118=>'【包裹宽（CM）】数字格式不正确！',
            9119=>'【包裹宽（CM）】不能超过4位！',
            9120=>'【包裹宽（CM）】必须大于0！',
            9121=>'【包裹高（CM）】数字格式不正确！',
            9122=>'【包裹高（CM）】不能超过4位！',
            9123=>'【包裹高（CM）】必须大于0！',
            9124=>'【收件人身份证号/护照号】只能为数字和字母！',
            9125=>'【收件人身份证号/护照号】长度不能超过18个字符！',
            9126=>'【VAT税号】只能为数字和字母！',
            9127=>'【VAT税号】长度不能超过20个字符！',
            9128=>'【是否电池】填写错误，只能填写Y或N！',
            9129=>'【报价服务】填写错误，只能填写Y或N！',
            9130=>'目的地属于APO/FPO地区，不提供派送服务！',
            9131=>'【报价服务】该产品不支持保价服务！',
            9132=>'该服务渠道不支持到付！',
            9135=>"<a href='/configuration/carrierconfig/index?tcarrier_code=lb_SF' target='_blank'>寄件人公司名不能为纯数字/纯符号</a>",
            9136=>"<a href='/configuration/carrierconfig/index?tcarrier_code=lb_SF' target='_blank'>寄件人姓名不能为纯数字/纯符号</a>",
            9137=>"<a href='/configuration/carrierconfig/index?tcarrier_code=lb_SF' target='_blank'>寄件人省不能为纯数字/纯符号</a>",
            9138=>"<a href='/configuration/carrierconfig/index?tcarrier_code=lb_SF' target='_blank'>寄件人城市不能为纯数字/纯符号</a>",
            9139=>"<a href='/configuration/carrierconfig/index?tcarrier_code=lb_SF' target='_blank'>寄件人地址不能为纯数字/纯符号</a>",
            9140=>'收件人姓名只能为字母,不能有其他符号',
            // 9141=>'收件人州或省只能为州代码，只能为字母 (废弃)',
            // 9142=>'收件人城市能为字母，不能有其他符号 (废弃)',
            9143=>'英文申报品名必须是英文或英文+数字组合，不能为纯数字/符号',
            9144=>'收件人州或省只能是英文字符',
            9145=>"<a href='/configuration/carrierconfig/index?tcarrier_code=lb_SF' target='_blank'>寄件人电话不能为空</a>",
            9146=>'重量不能为空',
            9147=>'收件人电话不能为空',
            9150=>'【商品网址链接】长度超过限制！',
            9151=>'【平台网址】长度超过限制！',
            9152=>'【店铺名称】长度超过限制！',
            9153=>'【商品网址链接】不能为空！',
            9154=>'【平台网址】不能为空！',
            9155=>'【店铺名称】不能为空！',
            9156=>'【收件人城市】,【国家】不匹配！',
            9157 =>'【目的地区域】不提供派送服务！',
            9158=>"<a href='/configuration/carrierconfig/index?tcarrier_code=lb_SF' target='_blank'>【寄件人公司名】不能为纯数字/纯符号！</a>",
            9159=>"<a href='/configuration/carrierconfig/index?tcarrier_code=lb_SF' target='_blank'>【寄件人姓名】只能为字母, 不能有其他符号！</a>",
            9160=>"<a href='/configuration/carrierconfig/index?tcarrier_code=lb_SF' target='_blank'>【寄件人省】只能为字母,不能有其他符号！</a>",
            9161=>"<a href='/configuration/carrierconfig/index?tcarrier_code=lb_SF' target='_blank'>【寄件人城市】只能为字母,不能有其他符号！</a>",
            9162=>"<a href='/configuration/carrierconfig/index?tcarrier_code=lb_SF' target='_blank'>【寄件人地址】不能为纯数字/纯符号！</a>",
            9163=>'【收件人州或省】只能为州代码，只能为字母！',
            9164=>'【英文申报品名】必须是英文或英文+数字组合，不能为纯数字/符号！',
            9165=>'很抱歉,您的账号缺少【月结卡号】,不能执行下单操作,请联系客服人员！',
            9166=>'请使用老产品代码下单！',
            9167=>"<a href='/configuration/carrierconfig/index?tcarrier_code=lb_SF' target='_blank'>请到设置页面更新运输服务，且开启新的运输服务</a>",
            9168=>'很抱歉,您的账号缺少【接入编码】,不能执行下单操作,请联系客服人员！',
            9169=>"<a href='/configuration/carrierconfig/index?tcarrier_code=lb_SF' target='_blank'>温馨提示：根据服务商要求，【寄件人姓名】不能使用数字、代号作为寄件人姓名，请规范填报的寄件人姓名</a>",
            9170=>'请先登陆顺丰国际网站进行电子签约，谢谢！',
            9171=>'收件人邮编不能为空！',
            9172=>'收件人邮编只能是英文字母和数字！',
            9173=>'【寄件人手机】格式不正确，只能包含阿拉伯数字、-',
            9174=>'【寄件人电话】格式不正确，只能包含阿拉伯数字、-',
            9175=>'【英文申报品名】只能包含阿拉伯数字、英文字母、（、）、(、)、，、,、-、_、%',
            9176=>'【收件人手机】长度不能小于6 位',
            9177=>'【收件人手机】格式不正确，只能包含阿拉伯数字、英文字母、+、-、 （）、()',
            9178=>'【收件人电话】长度不能小于6 位',
            9179=>'【收件人电话】格式不正确，只能包含阿拉伯数字、英文字母、+、-、 （）、()',
            9180=>'登陆后台管理系统签订折扣协议！',
            9181=>'收件人电话或手机不能同时为空！',
            9182=>'根据服务商要求：不能使用数字或代号作为寄件人地址，请规范填报的寄件人地址！',
            9183=>'收件人邮编不存在，请输入正确的邮编！',
            9184=>'【收件人邮箱】不能为空！',
            9185=>'收件人电话或手机只能包含阿拉伯数字！',
            9186=>'寄件人姓名只能包含英文字符！',
            9187=>'寄件人公司名只能包含英文字符！',
            9188=>'寄件人城市只能包含英文字符！',
            9189=>'寄件人省只能包含英文字符！',
            9190=>'英文申报品名只能是英文字符！',
            9191=>'寄件人地址格式不正确，不能包含特殊字符！如：`、^、~、$、%、#、+等！',
            9192=>'收件人地址格式不正确，不能包含特殊字符！如：`、^、~、$、%、#、+等！',
            9193=>'【州╲省】,【收件人城市】,【邮编】不匹配！',
            9194=>'您好！感谢您使用顺丰国际小包服务，因平邮暂停退件服务，请点击确认后取消退件服务或者重新选择顺丰其他运输方式，谢谢您的配合！！',
            9195=>'非常抱歉，此国家暂时不提供服务！',
            9196=>'当总申报价值超过15 英镑时【收件人邮箱】不能为空！',
            9197=>'单个订单申报总值必须大于等于1USD！',
            9198=>'【申报品数量】或【申报价值】不能为空！',
            9199=>'英文申报名首三位必须为英文字母！',
            9200=>'收件人地址仅允许使用英文字母加英文符号（所有英文符号）和数字！',
            9201=>'如需退件请填：Y，不需要退件则为空！',
            9202=>'只能是阿拉伯数字和中横线！',
            9295=>'收件人电话或手机格式不正确，只能包含阿拉伯数字、+、-、空格！',
            9296=>'非常抱歉，此渠道不支持该重量！',
            9297=>'重量格式错误，最大5位正整数且最多保留3位小数！',
            9298=>'货件申报价值不能大于800USD！',
            9299=>'货件申报价值需大于0USD！',
            9300=>'【收件人电话或手机】格式不正确，只能包含阿拉伯数字、英文字母、+、-、/！',
            9301=>'【寄件人电话】格式不正确，只能包含阿拉伯数字！',
            9357=>'州/省、城市与邮编不匹配！',
            9358=>'面单正在生成...请稍后再试！',
            9366=>'该产品不支持一票多件！',
            9367=>'件数只能填写1(含)至1000(含)的整数！',
            9368=>'此附加服务内容不支持！',
            9369=>'仓库代码不存在！',
            9370=>'运输方式不支持该税金支付方式！',
            9371=>'请提供正确的税号信息！',
            9372=>'【税号】不能超过12个字节！',
            9373=>'【税号】格式不正确,只能包含阿拉伯数字！',
        ];
        return $arr;
    }

    //洲名字转为州代码
    public static function getProvinceCode($provinceName)
    {
        $provinceNameToCode = array(
            'NEW SOUTH WALES' => 'NSW',
            'WESTERN AUSTRALIA' => 'WA',
            'VICTORIA' => 'VIC',
            'QUEENSLAND' => 'QLD',
            'TASMANIA' => 'TAS',
            'SOUTH AUSTRALIA' => 'SA',
            'NORTHERN TERRITORY' => 'NT',
            'AUSTRALIAN CAPITAL TERRITORY' => 'ACT'
        );

        $codeKeyArr = array_keys($provinceNameToCode);
        $isExist = in_array($provinceName, $codeKeyArr);

        if($isExist===true)
        {
            foreach ($provinceNameToCode as $key => $value) {
                if (strtoupper($provinceName) == $key) {
                    return $value;
                }
            }
        }
        else{
            return false;
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
    public function getVerifyCarrierAccountInformation($data)
    {
    	$result = array('is_support'=>0,'error'=>0);
    
    	try
    	{
    		/*$postdata['@attributes']=['tracking_type'=>1,'method_type' => 1,'tracking_number' =>'0000'];
    		
        	if(isset(\Yii::$app->params["currentEnv"]) and \Yii::$app->params["currentEnv"]=='production' )
                //正式场接口
                $this->wsdl = 'http://ibse.sf-express.com/CBTA/ws/sfexpressService?wsdl';
            else 
                //测试场接口
                $this->wsdl = 'http://ibu-ibse.sit.sf-express.com:1091/CBTA/ws/sfexpressService?wsdl';

    	    try 
    	    {
        		set_time_limit(100);
        		ignore_user_abort(true);
            		
        		self::$soapClient=new \SoapClient($this->wsdl,array(true));	
        	}
        	catch (Exception $e)
        	{
        		return self::getResult(1,'','网络连接故障<br/>'.$e->getMessage());
        	}

    		self::$authtoken = $data['token'];
    		self::$authId = $data['authId'];
    		
    		self::$operate = 'OrderSearchService';
    		$response=$this->sfexpressService($postdata);
    		if($response['ack'] != 'ERR' || (!empty($response['code']) && stripos($response['code'][0],'身份验证失败') === false))
    			$result['error'] = 0;
    		*/
    	}catch(CarrierException $e){
    	}
    
    	return $result;
    }

}
?>
<?php
class SF_Struct extends LB_SFCarrierAPI{
    protected static function mergeArray($arrs0, $arrs1) {//将外部数据传入到参数模中【完全覆盖】
        foreach($arrs0 as $key0 => $value0) {
            if(isset($arrs1[$key0]) && !empty($arrs1[$key0])) {
                $arrs0[$key0] = $arrs1[$key0];
            } else {
                unset($arrs0[$key0]);
            }
        }
        return $arrs0;
    }
    //拼接xml格式字符串
    public static function array2xml($lastDataArr,$service='OrderService',$lang = 'zh-CN'){
        $str1 = '<Request service="'.$service.'" lang="'.$lang.'"><Head>'.parent::$authId.'</Head><Body>';
        $orderParams = '';
        foreach($lastDataArr['order'] as $k=>$v){
            $orderParams .= ' '.$k.'="'.$v.'"';
        }
        $cargo = '';
        foreach($lastDataArr['cargo'] as $value){
            $cargoParams = '';
            foreach ($value as $k => $v) {
                $cargoParams .= ' '.$k.'="'.$v.'"';
            }
            $cargo .= '<Cargo '.$cargoParams.'></Cargo>';
        }
        $result = $str1.'<Order '.$orderParams.'>'.$cargo.'</Order></Body></Request>';

        return $result;

    }



    /**
     * 数组转为xML
     * @param $var 数组
     * @param $type xml的根节点
     * @param $tag
     * 返回xml格式
     */

    public static function arr2xml($var, $type = 'root', $tag = '') {
        $ret = '';
        if (!is_int($type)) {
            if ($tag)
                return self::arr2xml(array($tag => $var), 0, $type); else {
                $tag .= $type;
                $type = 0;
            }
        }
        $level = $type;
        $indent = str_repeat("\t", $level);
        if (!is_array($var)) {
            $ret .= $indent . '<' . $tag;
            $var = strval($var);
            if ($var == '') {
                $ret .= ' />';
            } else if (!preg_match('/[^0-9a-zA-Z@\._:\/-]/', $var)) {
                $ret .= '>' . $var . '</' . $tag . '>';
            } else {
                $ret .= "><![CDATA[{$var}]]></{$tag}>";
            }
            $ret .= "\n";
        } else if (!(is_array($var) && count($var) && (array_keys($var) !== range(0, sizeof($var) - 1))) && !empty($var)) {
            foreach ($var as $tmp)
                $ret .= self::arr2xml($tmp, $level, $tag);
        } else {
            $ret .= $indent . '<' . $tag;
            if ($level == 0)
                $ret .= '';
            if (isset($var['@attributes'])) {
                foreach ($var['@attributes'] as $k => $v) {
                    if (!is_array($v)) {
                        $ret .= sprintf(' %s="%s"', $k, $v);
                    }
                }
                unset($var['@attributes']);
            }
            $ret .= ">\n";
            foreach ($var as $key => $val) {
                $ret .= self::arr2xml($val, $level + 1, $key);
            }
            $ret .= "{$indent}</{$tag}>\n";
        }
        return $ret;
    }

    /*******在线订单操作********/
    //创建订单
    public static function orderService($Parameter) {//用php基类生成一个结构体【定义参数规则】
        $xml = self::array2xml($Parameter,'OrderService','zh-CN');
        return ['xml'=>$xml];
    }

    public static function getArrFormat($service,$lang='zh-CN'){
        return $xmlArray = [
            '@attributes' => [
                'service' => $service,
                'lang' => $lang
            ],
            'Head' => parent::$authId
        ];
    }

    //订单交运或者取消
    public static function OrderConfirmService($customerParameter) {//用php基类生成一个结构体【定义参数规则】
        $arr = self::getArrFormat(parent::$operate);
        $arr['Body']['OrderConfirm'] = $customerParameter;
        $xml = self::arr2xml($arr,'Request');
        return ['xml'=>$xml];
    }

    //查询订单信息
    public static function OrderSearchService($customerParameter) {//用php基类生成一个结构体【定义参数规则】
        $arr = self::getArrFormat(parent::$operate);
        $arr['Body']['OrderSearch'] = $customerParameter;
        $xml = self::arr2xml($arr,'Request');
//        print_r($xml);exit;
        return ['xml'=>$xml];
    }

    //修改商品重量
    public static function OrderUpdateService($customerParameter) {//用php基类生成一个结构体【定义参数规则】
        $arr = self::getArrFormat(parent::$operate);
        $arr['Body']['OrderUpdate'] = $customerParameter;
        $xml = self::arr2xml($arr,'Request');
        return ['xml'=>$xml];
    }

    //查询轨迹
    public static function RouteService($customerParameter) {//数组
        $arr = self::getArrFormat(parent::$operate);
        $arr['Body']['RouteRequest'] = $customerParameter;
        $xml = self::arr2xml($arr,'Request');
        return ['xml'=>$xml];
    }

    //打印配货单
    public static function OrderLabelPrintService($customerParameter) {//数组
        $arr = self::getArrFormat(parent::$operate);
        $arr['Body']['OrderLabelPrint'] = $customerParameter;
        $xml = self::arr2xml($arr,'Request');
        return ['xml'=>$xml];
    }

}
?>
