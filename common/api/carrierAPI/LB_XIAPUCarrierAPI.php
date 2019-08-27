<?php
namespace common\api\carrierAPI;
use common\api\carrierAPI\BaseCarrierAPI;
use eagle\modules\order\models\OdOrder;
use eagle\modules\carrier\helpers\CarrierAPIHelper;
use \Exception;
use eagle\modules\carrier\helpers\CarrierException;
use common\helpers\Helper_Curl;
use \Jurosh\PDFMerge\PDFMerger;

class LB_XIAPUCarrierAPI extends BaseCarrierAPI{
    public function __construct(){
// //         self::$ishipper='http://ishipper.harpost.com/ishipper/openapi/user/1.2/';
//         $aa=array();
//         $aa['ApiToken']='9cbdda92c36b3031efcd2d02c6213016';
// //          print_r(json_encode($aa));
//         $post_result = Helper_Curl::post('http://ishipper.harpost.com/ishipper/openapi/user/1.2/listAllShipwayCodes', json_encode($aa));
// //          print_r(json_decode($post_result));
//         $cc=json_decode($post_result);
//         $transport=$cc->Result;
//         $string='';
//         foreach ($transport as $ss){
//             $string.="{$ss->Code}:{$ss->Name};";
//         }
//         echo $string;
    }
    
    /**
     +----------------------------------------------------------
     * 申请订单号
     +----------------------------------------------------------
     **/
     public function getOrderNO($data){
         try{
             set_time_limit(0);
             ignore_user_abort(true);
             
             $order = $data['order'];  //object OdOrder
             $form_data = $data['data'];
             
             //重复发货 添加不同的标识码
             $extra_id = isset($data['data']['extra_id'])?$data['data']['extra_id']:'';
             $customer_number = $data['data']['customer_number'];
             
             if(isset($data['data']['extra_id'])){
                 if($extra_id == ''){
                     return self::getResult(1, '', '强制发货标识码，不能为空');
                 }
             }
             
             //对当前条件的验证  0 如果订单已存在 则报错 1 订单不存在 则报错
             $checkResult = CarrierAPIHelper::validate(0,0,$order,$extra_id,$customer_number);//第一个参数检验puid，第二个检验是否存在相关订单
             $puid = $checkResult['data']['puid'];
             
             $info = CarrierAPIHelper::getAllInfo($order);
             $account = $info['account'];
             $Service = $info['service'];//主要获取运输方式的中文名以及相关的运输代码
             //认证参数
             $params=$account->api_params;
             $token=$params['ApiToken'];
            
             $order_message=array();
             $order_list=array();
             $order_customer=array();
             $order_product_ItemList=array();
             $order_CustomerItemList=array();
             $order_message['ApiToken']=$token;
             
             $sum=count($form_data['Value']);
             foreach ($order->items as $i=>$vitem){
                 if(!empty($form_data['Quantity'][$i])){
                     $order_CustomerItemList['Quantity']=$form_data['Quantity'][$i];//必填且要为数字
                 }else{
//                      $order_CustomerItemList['Quantity']="1";
                     throw new CarrierException("数量不能为空！");
                 }
                 
                 $order_CustomerItemList['DescriptionEn']=$form_data['DescriptionEn'][$i];//必填
                 $order_CustomerItemList['DescriptionCn']=$form_data['DescriptionCn'][$i];//报关中文名称最少需要包含2个汉字，目的国家必须为台湾,加拿大,澳大利亚,香港,日本,法国,韩国,巴西,西班牙,乌克兰,新加坡,荷兰,俄罗斯,白俄罗斯,英国,美国
                 if(mb_strlen($order_CustomerItemList['DescriptionCn'],'utf-8')>64){
                     throw new CarrierException("中文报关名过长,长度不能超过64");//检查中文报关名的长度
                 }
                 if(mb_strlen($order_CustomerItemList['DescriptionEn'],'utf-8')>128){
                     throw new CarrierException("英文报关名过长，长度不能超过128");//检查英文报关名的长度
                 }
                 if(!empty($form_data['Weight'][$i])){
                     $order_CustomerItemList['Weight']=($form_data['Weight'][$i]*$order_CustomerItemList['Quantity'])/1000;//必填,单位kg
                 }else{
                     throw new CarrierException("报关重量不能为空！");
                 }
                 if(!empty($form_data['Value'][$i])){
                     $order_CustomerItemList['Value']=($form_data['Value'][$i]*$order_CustomerItemList['Quantity']);//必填且要为数字
                 }else {
                     throw new CarrierException("报关价值不能为空！");
                 }
                 $order_customer['CustomsItemList'][$i]=$order_CustomerItemList;//多个商品就多个报关
                 if(!empty($vitem->sku)){
                     $order_product_ItemList['Title']=$vitem->sku;
                 }else{
                     $order_product_ItemList['Title']="";
                 }
                 $order_product_ItemList['ImgUrl']="";
                 $order_product_ItemList['ItemUrl']="";
                 $order_product_ItemList['Sku']="";
                 $order_product_ItemList['Quantity']=$order_CustomerItemList['Quantity'];//不知道是否需要，暂时不填商品的信息
                 $order_list['OrderItemList'][$i] = $order_product_ItemList;
             }
             if(!empty($form_data['currency'])){//申报价值
                 $order_customer['Currency']=$form_data['currency'];
             }else{
                 $order_customer['Currency']="USD";
             }
             if(!empty($form_data['CustomsType'])){//申报类型
                 $order_customer['CustomsType']=$form_data['CustomsType'];
             }else{
                 $order_customer['CustomsType']="O";
             }
             
             $tmpconsignee_country_code = $order->consignee_country_code;
             if($order->consignee_country_code == 'UK'){
             	$tmpconsignee_country_code = 'GB';
             }
             
             $addressAndPhoneParams = array(
                 'address' => array(
                     'consignee_address_line1_limit' => 200,
                     'consignee_address_line2_limit' => 200,
                 ),
                 'consignee_district' => 1,
                 'consignee_county' => 1,
                 'consignee_company' => 1,
                 'consignee_phone_limit' => 100
             );
             	
             $addressAndPhone = CarrierAPIHelper::getCarrierAddressAndPhoneInfo($order, $addressAndPhoneParams);
             
//              $order_product_ItemList['Title']="";
//              $order_product_ItemList['ImgUrl']="";
//              $order_product_ItemList['ItemUrl']="";
//              $order_product_ItemList['Sku']="";
//              $order_product_ItemList['Quantity']="";//不知道是否需要，暂时不填商品的信息
             
//              $order_list['OrderId']=$order->order_source_order_id;
             $order_list['OrderId']=$customer_number;
             $order_list['ShipwayCode']=$Service->shipping_method_code;//运输方式代码，必填
             $order_list['SellerAccountName']="";
             $order_list['BuyerId']=$order->source_buyer_user_id;//需要测试是否不填
             $order_list['ReceiverName']=$order->consignee;//必填
             $order_list['AddressLine1']=$addressAndPhone['address_line1'];//必填
             $order_list['AddressLine2']=$addressAndPhone['address_line2'];//必填
             $order_list['City']=$order->consignee_city;
             $order_list['State']=empty($order->consignee_province)?$order->consignee_city:$order->consignee_province;//必填
             $order_list['CountryCode']=$tmpconsignee_country_code;//必填，且格式要正确
             $order_list['PhoneNumber']=$addressAndPhone['phone1'];
             $order_list['PostCode']=$order->consignee_postal_code;//必填
             $order_list['Email']=$order->consignee_email;
             $order_list['Remark']=$form_data['Remark'];
//              $order_list['OrderItemList'][0]=$order_product_ItemList;//可以有多个产品
             $order_list['OrderCustoms']=$order_customer;
            
             $order_message['OrderList'][0]=$order_list;//可以有多个订单
//                         print_r($order_message) ;
//                          exit();
//                          print_r(json_encode($order_message));
             \Yii::info('lb_xiapu,request,puid:'.$puid.'，order_id:'.$order->order_id.' '.json_encode($order_message),"carrier_api");
             $order_respond = Helper_Curl::post('http://ishipper.harpost.com/ishipper/openapi/user/1.2/addOrUpdateOrder', json_encode($order_message));
//              print_r(json_decode($order_result));
             $result=json_decode($order_respond,true);
//              print_r($result);
             if(!empty($result['Status'])&&$result['Status']=="success"){//验证POST数据是否成功
                 foreach ($result['Result'] as $res){
                     if($res['Status']=='success'){
                         //上传成功后进行数据的保存 订单，运输服务，客户号，订单状态，跟踪号(选填),returnNo(选填)  订单状态
                         $track_no=empty($res['TrackingNo'])?null:$res['TrackingNo'];
                         $r = CarrierAPIHelper::orderSuccess($order,$Service,$res['OrderId'],OdOrder::CARRIER_WAITING_GETCODE ,$track_no);
                         return  BaseCarrierAPI::getResult(0,$r,'操作成功!订单号'.$res['OrderId']);
                     }else{
                         if(!empty($res['Error'])){
                             throw new CarrierException($res['Error']);
                         }else {
                             throw new CarrierException("上传订单失败");
                         }
                         
                     }
                 }
             }else{
                 if(!empty($result['ErrorMessage'])){
                     throw new CarrierException($result['ErrorMessage']);
                 }else{
                     throw new CarrierException("上传数据失败");
                 }
             }
             
//              return $result;
         }catch(CarrierException $e){
             return self::getResult(1,'',$e->msg());
         }
         
     }
//      public function getOrderNO1(){
//          try{
//             $order_message=array();
//             $order_list=array();
//             $order_customer=array();
//             $order_product_ItemList=array();
//             $order_CustomerItemList=array();
//             $order_message['ApiToken']=self::$token;
            
//             $order_CustomerItemList['Quantity']="3";//必填且要为数字
//             $order_CustomerItemList['DescriptionEn']="gift";//必填
//             $order_CustomerItemList['DescriptionCn']="礼物";//报关中文名称最少需要包含2个汉字，目的国家必须为台湾,加拿大,澳大利亚,香港,日本,法国,韩国,巴西,西班牙,乌克兰,新加坡,荷兰,俄罗斯,白俄罗斯,英国,美国
//             $order_CustomerItemList['Weight']="0.2";//必填
//             $order_CustomerItemList['Value']="0.33";//必填且要为数字
            
//             $order_customer['Currency']="USD";
//             $order_customer['CustomsType']="G";
//             $order_customer['CustomsItemList'][0]=$order_CustomerItemList;//多个商品就多个报关
            
//             $order_product_ItemList['Title']="";
//             $order_product_ItemList['ImgUrl']="";
//             $order_product_ItemList['ItemUrl']="";
//             $order_product_ItemList['Sku']="";
//             $order_product_ItemList['Quantity']="5";
            
//             $order_list['OrderId']="6237974681897";
//             $order_list['ShipwayCode']="ESPEED";//必填
//             $order_list['SellerAccountName']="";
//             $order_list['BuyerId']="";//需要测试是否不填
//             $order_list['ReceiverName']="JK";//必填
//             $order_list['AddressLine1']="Center";//必填
//             $order_list['City']="New York";
//             $order_list['State']="New York";//必填
//             $order_list['CountryCode']="US";//必填，且格式要正确  
//             $order_list['PhoneNumber']="";
//             $order_list['PostCode']="12355";//必填
//             $order_list['Email']="123@qq.com";
//             $order_list['Remark']="COLOR:WHITE";
//             $order_list['OrderItemList'][0]=$order_product_ItemList;//可以有多个产品
//             $order_list['OrderCustoms']=$order_customer;
            
//             $order_message['OrderList'][0]=$order_list;//可以有多个订单
// //             print_r($order_message);
// //             print_r(json_encode($order_message));
//             $order_result = Helper_Curl::post('http://ishipper.harpost.com/ishipper/openapi/user/1.2/addOrUpdateOrder', json_encode($order_message));
//             print_r(json_decode($order_result));
            
            
//          }catch(CarrierException $e){
//              return self::getResult(1,'',$e->msg());
//          }
//      }
    /**
     +----------------------------------------------------------
     * 取消跟踪号
     +----------------------------------------------------------
    **/
     public function cancelOrderNO($data){
         return BaseCarrierAPI::getResult(1, '', '物流接口不支持取消物流单。');
     }
//      public function cancelOrderNO1(){
//           try{
//               $track_no=array();
//               $order_id=array();
//               $order_id['OrderId']="110165077187-27523715001";
//               //              $order_id1['OrderId']="62279745969151";
//               $track_no['ApiToken']=self::$token;
//               $track_no['OrdersList'][0]=$order_id;
//               //              $track_no['OrdersList'][1]=$order_id1;
//               //              print_r(json_encode($track_no));
//               $track_no_result = Helper_Curl::post('http://ishipper.harpost.com/ishipper/openapi/user/1.2/deleteOrder', json_encode($track_no));
//               print_r((Array)json_decode($track_no_result));
// //               $a=(Array)json_decode($track_no_result);
// //               $b=$a['Result'];
// //               print_r($b);
// //               foreach ($b as $c){
// //                   echo $c->ErrorMessage;
// //               }
//           }catch(CarrierException $e){
//              return self::getResult(1,'',$e->msg());
//          }
//      }
    /**
     +----------------------------------------------------------
     * 交运
     +----------------------------------------------------------
    **/
     public function doDispatch($data){
         return BaseCarrierAPI::getResult(1, '', '物流接口不支持交运物流单，上传物流单便会立即交运。');
     }
     
    
    /**
     +----------------------------------------------------------
     * 申请跟踪号
     +----------------------------------------------------------
    **/
     public function getTrackingNO($data){
         try{
             $order = $data['order'];
             //对当前条件的验证  0 如果订单已存在 则报错 1 订单不存在 则报错
             $checkResult = CarrierAPIHelper::validate(0,1,$order);
             $shipped = $checkResult['data']['shipped'];
             
             $info = CarrierAPIHelper::getAllInfo($order);
             $account = $info['account'];
             //认证参数
             $params=$account->api_params;
             $token=$params['ApiToken'];
              
             $track_no=array();
             $order_id=array();
//              $order_id['OrderId']=$order->order_source_order_id;
             $order_id['OrderId']=$order->customer_number;
             //              $order_id1['OrderId']="62279745969151";
             $track_no['ApiToken']=$token;
             $track_no['OrdersList'][0]=$order_id;
             //              $track_no['OrdersList'][1]=$order_id1;
             //              print_r(json_encode($track_no));
             $track_no_result = Helper_Curl::post('http://ishipper.harpost.com/ishipper/openapi/user/1.2/getOrderTrackingNo', json_encode($track_no));
             $result=json_decode($track_no_result,true);
             if(!empty($result['Status'])&&$result['Status']=='success'){//数据POST是否成功
//                  print_r($result);exit();
                 foreach($result['Result'] as $message){
                     if(empty($shipped->tracking_number)&&!empty($message['TrackingNo'])){
                         $shipped->tracking_number=$message['TrackingNo'];
                         $shipped->save();
                     }
                 }
                 if(!empty($message['TrackingNo'])){//有跟踪号的前提
                 	$order->tracking_number = $shipped->tracking_number;
                     $order->save();
                     return  BaseCarrierAPI::getResult(0,'','查询成功成功!跟踪号'.$message['TrackingNo']);
                 }else {//没有跟踪号
//                      return BaseCarrierAPI::getResult(0,'','暂时没有跟踪号');
                        throw new CarrierException('暂时没有跟踪号');
                 }
       
             }else{
                 if(!empty($result['ErrorMessage'])){
                     throw new CarrierException($result['ErrorMessage']);
                 }else{
                     throw new CarrierException("上传数据失败");
                 }      
             }

              
//              return $checkResult;
         }catch(CarrierException $e){return self::getResult(1,'',$e->msg());}
      
     }
     public function getTrackingNO1(){
         try{
             $track_no=array();
             $order_id=array();
             $order_id['OrderId']="6237974681897";
//              $order_id1['OrderId']="62279745969151";
             $track_no['ApiToken']=self::$token;
             $track_no['OrdersList'][0]=$order_id;
//              $track_no['OrdersList'][1]=$order_id1;
//              print_r(json_encode($track_no));
             $track_no_result = Helper_Curl::post('http://ishipper.harpost.com/ishipper/openapi/user/1.2/getOrderTrackingNo', json_encode($track_no));
             print_r((Array)json_decode($track_no_result));
         }catch(CarrierException $e){
             return self::getResult(1,'',$e->msg());
         }
         
     }
    
    /**
     +----------------------------------------------------------
     * 打单
     +----------------------------------------------------------
    **/
     public function doPrint($data){
         try{
             $pdf = new PDFMerger();
             
             $user=\Yii::$app->user->identity;
             if(empty($user))return  BaseCarrierAPI::getResult(1, '', '用户登陆信息缺失,请重新登陆');
             $puid = $user->getParentUid();
             
             $all_message = current($data);reset($data);//打印时是逐个运输方式的多张订单传入，所以获取一次account、service的信息就可以了
             $order_object=$all_message['order'];//获取订单的对象
             //获取到所需要使用的数据
//              print_r($all_message);
             $info = CarrierAPIHelper::getAllInfo($order_object);
             $account = $info['account'];
             $service = $info['service'];
//              print_r($info);
             foreach ($data as $detail_data){
                              
                 $order = $detail_data['order'];
     
                 //认证参数
                 $params=$account->api_params;
                 $token=$params['ApiToken'];
                 
//                  $order_id['OrderId']=$order->order_source_order_id;
                 $order_id['OrderId']=$order->customer_number;
                 $print_order['ApiToken']=$token;
                 $print_order['LabelFormat']=$service['carrier_params']['LableFormat']==null?"A4_2":$service['carrier_params']['LableFormat'];//打印格式
                 $print_order['OutPutFormat']="pdf";//打印方式,pdf/html
                 //下面选项都为true或false
                 $print_order['PrintBuyer']=$service['carrier_params']['PrintBuyer']==null?"false":$service['carrier_params']['PrintBuyer'];//是否打印买家帐号
                 $print_order['PrintSalesOrderNo']=$service['carrier_params']['PrintSalesOrderNo']==null?"false":$service['carrier_params']['PrintSalesOrderNo'];//是否打印订单销售编号
                 $print_order['PrintSellerAccount']=$service['carrier_params']['PrintSellerAccount']==null?"false":$service['carrier_params']['PrintSellerAccount'];//是否打印卖家帐号
                 $print_order['PrintRemark']=$service['carrier_params']['PrintRemark']==null?"false":$service['carrier_params']['PrintRemark'];//是否打印订单备注
                 $print_order['PrintCustoms']=$service['carrier_params']['PrintCustoms']==null?"false":$service['carrier_params']['PrintCustoms'];//是否打印报关单
                 $print_order['PrintProduct'] = !isset($service['carrier_params']['PrintProduct']) ?"false":$service['carrier_params']['PrintProduct'];//是否打印配货信息
//                  $print_order['PrintProduct']="false";//是否打印配货信息
                 $print_order['PrintSenderName']="false";//是否打印发件人名
                 $print_order['PrintProductImg']="false";//是否打印产品图片
                 $print_order['PrintProductPosition']="false";//是否打印配货详细信息
                 $print_order['PrintProductForma']="false";//打印配货格式,"{sku}{productName}{itemtitle}"
                 $print_order['OrdersList'][0]=$order_id;//需要打印的订单
                 $print_result=Helper_Curl::post('http://ishipper.harpost.com/ishipper/openapi/user/1.2/printOrderByLabelType', json_encode($print_order));
                 \Yii::info('XIAPU print result,puid:'.$puid.',result,orderId:'.$order->order_source_order_id.',param:'.json_encode($print_order).PHP_EOL.'print_result:'.$print_result,"carrier_api");
                 $print_pdf_result = base64_decode($print_result);
                 

                 if(strlen($print_pdf_result)>1000){
                     $pdfurl=CarrierAPIHelper::savePDF($print_pdf_result,$puid,$order->order_source_order_id.'_'.$account->carrier_code,0);
                     $pdf->addPDF($pdfurl['filePath'],'all');//合并相同运输运输方式的pdf流
//                      $order->is_print_carrier = 1;
                     $order->print_carrier_operator = $puid;
                     $order->printtime = time();
                     $order->carrier_error = '';
                     $order->save();
                 }else{
                 	if(strlen($print_result)>1000){
                 		$pdfurl=CarrierAPIHelper::savePDF($print_result,$puid,$order->order_source_order_id.'_'.$account->carrier_code,0);
                 		$pdf->addPDF($pdfurl['filePath'],'all');//合并相同运输运输方式的pdf流
//                  		$order->is_print_carrier = 1;
                 		$order->print_carrier_operator = $puid;
                 		$order->printtime = time();
                 		$order->carrier_error = '';
                 		$order->save();
                 	}else{
                 		return self::getResult(1, '', "打印失败，请联系技术人员");
                 	}
                 }
//                  if(is_array(json_decode($print_result,true))){//打印失败
//                      $respond=json_decode($print_result,true);
//                      if($respond['Status']=="fail"){
// //                          foreach($data as $v){
// //                              $order = $v['order'];
// //                              $order->carrier_error = $respond['ErrorMessage'];
// //                              $order->save();
// //                          }
//                          $order->carrier_error = $respond['ErrorMessage'];
//                          $order->save();
//                          return self::getResult(1, '', $respond['ErrorMessage']);
//                      }
//                  }else{
//                      $pdfurl=CarrierAPIHelper::savePDF($print_result,$puid,$order->order_source_order_id.'_'.$account->carrier_code,0);
//                      $pdf->addPDF($pdfurl['filePath'],'all');//合并相同运输运输方式的pdf流
//                      $order->is_print_carrier = 1;
//                      $order->print_carrier_operator = $puid;
//                      $order->printtime = time();
//                      $order->carrier_error = '';
//                      $order->save();   
//                  }
             }
             //合并多个PDF  这里还需要进一步测试
             \Yii::info('XIAPU print error,puid:'.$puid.',result,orderId:'.$order->order_source_order_id.' '.json_encode($pdfurl),"carrier_api");
             isset($pdfurl)?$pdf->merge('file', $pdfurl['filePath']):$pdfurl['filePath']='';//需要物理地址
             return self::getResult(0,['pdfUrl'=>$pdfurl['pdfUrl']],'连接已生成,请点击并打印');//访问URL地址

         }catch(CarrierException $e){
             return self::getResult(1,'',$e->msg());
         }
     }
     
     public static function getOrderNextStatus(){
		return OdOrder::CARRIER_FINISHED;
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
	 public function getVerifyCarrierAccountInformation($data){
	     $result = array('is_support'=>1,'error'=>1);
	 
	     try{
	         $api_array = array();
	         $api_array['ApiToken'] = $data['ApiToken'];
	         $post_result = Helper_Curl::post('http://ishipper.harpost.com/ishipper/openapi/user/1.2/listAllShipwayCodes', json_encode($api_array));
	         $return_result = json_decode($post_result,true);
	         if($return_result['Status'] == 'success'){
	             $result['error'] = 0;
	         }
	     }catch(CarrierException $e){
	     }
	 
	     return $result;
	 }
	 
	 //获取运输方式
	 public function getCarrierShippingServiceStr($account){
	     try{
	         $aa=array();
			 // TODO carrier user account @XXX@
        	 $aa['ApiToken']='@XXX@';
        	 $post_result = Helper_Curl::post('http://ishipper.harpost.com/ishipper/openapi/user/1.2/listAllShipwayCodes', json_encode($aa));
        	 $cc=json_decode($post_result);
        	 $transport=$cc->Result;
        	 $channelStr="";
        	 foreach ($transport as $ss){
        	     $channelStr.="{$ss->Code}:{$ss->Name};";
        	 }
        	 
	         if(empty($channelStr)){
	             return self::getResult(1, '', '');
	         }else{
	             return self::getResult(0, $channelStr, '');
	         }
	     }catch(CarrierException $e){return self::getResult(1,'',$e->msg());}
	 }
	 
//      public function doPrint1(){
//          try{
// //             $print_order=array();
// //             $order_id=array();
//             $order_id['OrderId']="6237974681897";
// //             $order_id1['OrderId']="62279745989777";
            
//             $print_order['ApiToken']=self::$token;
//             $print_order['LabelFormat']="A4_2";//打印格式
//             $print_order['OutPutFormat']="html";//打印方式,pdf/html
//             //下面选项都为true或false
//             $print_order['PrintBuyer']="false";//是否打印买家帐号
//             $print_order['PrintSalesOrderNo']="false";//是否打印订单销售编号
//             $print_order['PrintSellerAccount']="false";//是否打印卖家帐号
//             $print_order['PrintRemark']="false";//是否打印订单备注
//             $print_order['PrintCustoms']="true";//是否打印报关单
//             $print_order['PrintProduct']="false";//是否打印配货信息
//             $print_order['PrintSenderName']="false";//是否打印发件人名
//             $print_order['PrintProductImg']="false";//是否打印产品图片
//             $print_order['PrintProductPosition']="false";//是否打印配货详细信息
//             $print_order['PrintProductForma']="false";//打印配货格式,"{sku}{productName}{itemtitle}"
//             $print_order['OrdersList'][0]=$order_id;//需要打印的订单
// //             $print_order['OrdersList'][1]=$order_id1;
//             $print_result=Helper_Curl::post('http://ishipper.harpost.com/ishipper/openapi/user/1.2/printOrderByLabelType', json_encode($print_order));
// //             print_r(json_encode($print_order));
//             CarrierAPIHelper::savePDF($print_result,1,"lb_xiapu");
//             if(is_array(json_decode($print_result,true))){
//                 print_r(json_decode($print_result,true));
//             }else{
//                 print_r($print_result);
//             }    
// //             print_r(json_decode($print_result,true));
//          }catch(CarrierException $e){
//              return self::getResult(1,'',$e->msg());
//          }
//      }
}
