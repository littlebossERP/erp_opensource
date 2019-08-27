<?php
namespace eagle\modules\carrier\apihelpers;

use Yii;
use common\helpers\simple_html_dom;
use eagle\models\SysCountry;
use eagle\modules\catalog\helpers\ProductApiHelper;
use yii\helpers\Url;
use eagle\modules\order\models\OdOrderShipped;
use eagle\modules\carrier\helpers\CarrierPartitionNumberHelper;
use eagle\modules\lazada\apihelpers\LazadaApiHelper;
use eagle\modules\util\helpers\ConfigHelper;
use eagle\modules\amazon\apihelpers\AmazonApiHelper;
class PrintApiHelper{

	/**
	 * 参数 
	 * $template 自定义模板对象
	 * $shippingServece 运输服务对象
	 * 获取自定义模板中字段和数据库字段关系
	 * @return array(array('数据库模型名','对应字段'))
	 */
	public static function getPrintData($template,$shippingService,$order=0,$sku='',$type="地址单", $carrierConfig = array()){
		if(empty($template)){
			return array('error' => 1, 'msg' => '请检查该运输方式是否指定了自定义面单');
		}
		$html = new simple_html_dom($template->template_content);
		//return $html->__toString();
		//地址信息
		$address = $shippingService->address;
		$shippingfrom = isset($address['shippingfrom'])?$address['shippingfrom']:array();
// 		$shippingfrom_en = isset($address['shippingfrom_en'])?$address['shippingfrom_en']:array();
		$shippingfrom_en = isset($carrierConfig['address']['shippingfrom_en'])?$carrierConfig['address']['shippingfrom_en']:array();
		$shippingfrom_en = empty($address['shippingfrom_en']) ? $shippingfrom_en : $address['shippingfrom_en'];
		
		$returnaddress = isset($address['returnaddress'])?$address['returnaddress']:array();
		$pickupaddress = isset($address['pickupaddress'])?$address['pickupaddress']:array();
		$shipingfrom_country_code = isset($shippingfrom_en['country'])?$shippingfrom_en['country']:"CN";
		$shipingfrom_country =  SysCountry::findOne(['country_code'=>$shipingfrom_country_code]);
		$shippingInfo= OdOrderShipped::find()->where(['order_id'=>$order->order_id])->andWhere(['customer_number'=>$order->customer_number])->orderBy('id desc')->one();
		if ($shippingInfo == null){
			$shippingInfo == new OdOrderShipped();
		}
		//报关信息
		$declarationInfo =CarrierApiHelper::getDeclarationInfo($order, $shippingService,true,true);
		$products =$declarationInfo['products'];
		//商品信息
		$productInfo = $products[0];
    	//处理订单的预约sku信息
    	$total_weight = $declarationInfo['total_weight'] / 1000;
    	$total_price = $declarationInfo['total_price'];
    	$total = $declarationInfo['total'];
    	$has_battery = $declarationInfo['has_battery'];
    	
    	$productInformations = '';
    	foreach ($products as $productOne){
    		$productInformations .= $productOne['sku'].' * '.$productOne['quantity'].';';
    	}
    	
    	if(!empty($productInformations)){
    		$productInformations = substr($productInformations,0,strlen($productInformations)-1);
    	}
    	
    	if($template->template_type == '配货单'){
    		$distribution_config = ConfigHelper::getConfig('d_listpicking_name');
			$distribution_config = empty($distribution_config) ? 0 : $distribution_config;
    	}
    	
    	$tmpAddressMode2 = '';
    	if(!empty($order->consignee_address_line1))
    		$tmpAddressMode2 .= $order->consignee_address_line1;
    	if(!empty($order->consignee_address_line2))
    		$tmpAddressMode2 .=' '. $order->consignee_address_line2;
    	if(!empty($order->consignee_address_line3))
    		$tmpAddressMode2 .= ' '.$order->consignee_address_line3;
    	
    	foreach($html->find('littleboss[id]') as $obj){
    		switch ($obj->id){
    				case 'RECEIVER_NAME':$obj->innertext=(empty($order->consignee) ? '' : $order->consignee);//收件人
    				break;
    				case 'RECEIVER_ADDRESS':
    					$tmpReceiverAddress = $order->consignee_address_line1.(strlen($order->consignee_address_line2)>0?'<br>'.$order->consignee_address_line2:'').(strlen($order->consignee_address_line3)>0?'<br>'.$order->consignee_address_line3:'');
    					$obj->innertext=(empty($tmpReceiverAddress) ? '' : $tmpReceiverAddress);//收件人详细地址
    				break;
    				case 'RECEIVER_ADDRESS_MODE2':$obj->innertext=(empty($tmpAddressMode2) ? '' : $tmpAddressMode2);//收件人详细地址
    				break;
    				case 'RECEIVER_AREA':$obj->innertext=(empty($order->consignee_district) ? '' : $order->consignee_district);//收件人地区/州
    				break;
    				case 'RECEIVER_CITY':$obj->innertext=(empty($order->consignee_city) ? '' : $order->consignee_city);//收件人城市
    				break;
    				case 'RECEIVER_PROVINCE':$obj->innertext=(empty($order->consignee_province) ? '' : $order->consignee_province);//收件人省份
    				break;
    				case 'RECEIVER_COUNTRY_EN':$obj->innertext=(empty($order->consignee_country) ? '' : $order->consignee_country);//收件人国家(英)
    				break;
    				case 'RECEIVER_COUNTRY_CN':
    					$country_zh = SysCountry::findOne(['country_code'=>$order->consignee_country_code])->country_zh;
    					$obj->innertext=$country_zh;//收件人国家(中)
    				break;
    				case 'RECEIVER_ZIPCODE':$obj->innertext=(empty($order->consignee_postal_code) ? '' : $order->consignee_postal_code);//收件人邮编
    				break;
    				case 'RECEIVER_ZIPCODE_BARCODE':
    					$tmpReceiverZipcodeBarcode = empty($order->consignee_postal_code) ? '' : $order->consignee_postal_code;
    					$obj->innertext= '<img src="'.Url::to(['/carrier/carrieroperate/barcode','codetype'=>'code128','thickness'=>'30','text'=>$tmpReceiverZipcodeBarcode,'font'=>0,'fontsize'=>0]).'">';
    				break;
    				case 'RECEIVER_ZIPCODE_BARCODE_PREFIX':$obj->innertext='';//邮编字符串
    				break;
    				case 'RECEIVER_TELEPHONE':$obj->innertext=(empty($order->consignee_phone) ? '' : $order->consignee_phone);//收件人电话电话1
    				break;
    				case 'RECEIVER_MOBILE':$obj->innertext=(empty($order->consignee_mobile) ? '' : $order->consignee_mobile);//收件人手机电话2
    				break;
    				case 'RECEIVER_EMAIL':$obj->innertext=(empty($order->consignee_email) ? '' : $order->consignee_email);//收件人邮箱
    				break;
    				case 'RECEIVER_COMPANY':$obj->innertext=(empty($order->consignee_company) ? '' : $order->consignee_company);//收件人公司
    				break;
    				case 'RECEIVER_COUNTRY_EN_AB':$obj->innertext=(empty($order->consignee_country_code) ? '' : $order->consignee_country_code);//收件人国家英文简称国际代码
    				break;
    				case 'INTERNATIONAL_REGISTERED_PARCEL_SORTING_AREA':	//国际挂号小包分拣区
                    case 'RECEIVER_COUNTRY_EN_NUM_WISH'://wish邮收件人国家简码+分拣码
                        $receiver_country_en_num = $order->consignee_country_code.self::getNumCode($order->consignee_country_code);
                        $obj->innertext=(empty($order->consignee_country_code) ? '' : $receiver_country_en_num);
                    break;
                    case 'INTERNATIONAL_REGISTERED_PARCEL_PARTITION':	//国际挂号小包分区
                    	$obj->innertext=(empty($order->consignee_country_code) ? '' : CarrierPartitionNumberHelper::getInternationalRegisteredParcelPartition($order->consignee_country_code));
                    break;	
                    case 'INTERNATIONAL_COMMON_PACKET_PARTITION':	//国际平常小包分区
                    	$obj->innertext=(empty($order->consignee_country_code) ? '' : CarrierPartitionNumberHelper::getInternationalCommonPacketPartition($order->consignee_country_code));
                    	break;
                    case 'UNTRACKED_R_WISH'://wish邮区别平邮与挂号(挂号==1,平邮==0)
                        $obj->innertext=$shippingService->carrier_params['otype']== 1?'<span style="font-size: xx-large;">R</span>':'untracked';
                    break;
    				case 'RECEIVER_COUNTRY_EXPRESS_AREA':$obj->innertext=(empty($order->consignee_country_code) ? '' : $order->consignee_country_code);//收件人国家分区
    				break;
    				case 'RECEIVER_PAYMENT':
    				    if($order->order_source == "lazada"){
    				        if(!empty($order->payment_type)){
    				            $obj->innertext = $order->payment_type;
    				        }else{
    				            $order_payment_array = LazadaApiHelper::getPaymentMethod($order);
    				            if($order_payment_array[0]){
    				                $obj->innertext = $order_payment_array[1];
    				            }else{
    				                $obj->innertext = '';
    				            }
    				        }
				        }else{
				            $obj->innertext=$order->payment_type;//付款方式
				        }
    				break;
    				case 'EUB_RECEIVER_ZIPCODE_BARCODE'://EUB专用邮编条码
    					$tmpEubReceiverZipcodeBarcode = empty($order->consignee_postal_code) ? '' : $order->consignee_postal_code;
    					if(!empty($obj->data)){
    						$tmpImgWidth = $obj->data;
    						$obj->innertext= '<img src="'.Url::to(['/carrier/carrieroperate/barcode','codetype'=>'code128','thickness'=>'30','text'=>$tmpEubReceiverZipcodeBarcode,'font'=>0,'fontsize'=>0]).'" width="'.$tmpImgWidth.'px;">';
    					}else{
    						$obj->innertext= '<img src="'.Url::to(['/carrier/carrieroperate/barcode','codetype'=>'code128','thickness'=>'30','text'=>$tmpEubReceiverZipcodeBarcode,'font'=>0,'fontsize'=>0]).'">';
    					}
    				break;
    				case 'EUB_RECEIVER_ZIPCODE':$obj->innertext=(empty($order->consignee_postal_code) ? '' : $order->consignee_postal_code);//EUB专用邮编
    				break;
    				###########################################################################
    				case 'SENDER_NAME':$obj->innertext=isset($shippingfrom_en['contact'])?$shippingfrom_en['contact']:'';//发件人姓名
    				break;
    				case 'SENDER_ADDRESS':$obj->innertext=isset($shippingfrom_en['street'])?$shippingfrom_en['street']:'';//发件人地址
    				break;
    				case 'SENDER_AREA':$obj->innertext=isset($shippingfrom_en['district'])?$shippingfrom_en['district']:'';//发件人地区中文
    				break;
    				case 'SENDER_AREA_EN':$obj->innertext=isset($shippingfrom_en['district'])?$shippingfrom_en['district']:'';//发件人地区英文
    				break;
    				case 'SENDER_CITY':$obj->innertext=isset($shippingfrom_en['city'])?$shippingfrom_en['city']:'';//发件人城市
    				break;
    				case 'SENDER_CITY_EN':$obj->innertext=isset($shippingfrom_en['city'])?$shippingfrom_en['city']:'';//发件人城市英文
    				break;
    				case 'SENDER_PROVINCE':$obj->innertext=isset($shippingfrom_en['province'])?$shippingfrom_en['province']:'';//发件人省份
    				break;
    				case 'SENDER_PROVINCE_EN':$obj->innertext=isset($shippingfrom_en['province'])?$shippingfrom_en['province']:'';//发件人省份英文
    				break;
    				case 'SENDER_COUNTRY_CN':$obj->innertext=(empty($shipingfrom_country->country_zh) ? '' : $shipingfrom_country->country_zh);//发件人国家
    				break;
    				case 'SENDER_COUNTRY_EN':$obj->innertext=(empty($shipingfrom_country->country_en) ? '' : $shipingfrom_country->country_en);//发件人国家英文
    				break;
    				case 'SENDER_COUNTRY_EN_AB':$obj->innertext=$shipingfrom_country_code;//发件人国家英文简称国际代码
    				break;
    				case 'SENDER_ZIPCODE':$obj->innertext=isset($shippingfrom_en['postcode'])?$shippingfrom_en['postcode']:'';//发件人邮编
    				break;
    				case 'SENDER_TELEPHONE':$obj->innertext=isset($shippingfrom_en['phone'])?$shippingfrom_en['phone']:'';//发件人电话
    				break;
    				case 'SENDER_MOBILE':$obj->innertext=isset($shippingfrom_en['mobile'])?$shippingfrom_en['mobile']:'';//发件人邮编
    				break;
    				case 'SENDER_EMAIL':$obj->innertext=isset($shippingfrom_en['email'])?$shippingfrom_en['email']:'';//发件人邮箱
    				break;
    				case 'SENDER_COMPANY_NAME':$obj->innertext=isset($shippingfrom_en['company'])?$shippingfrom_en['company']:'';//发件人公司
    				break;
    				##########################################################################
    				case 'ORDER_SHOP_NAME':
    					$obj->innertext=(($order->order_source == 'amazon') ? (AmazonApiHelper::getAmzStoreName($order->selleruserid)) : $order->selleruserid);//店铺
    				break;
    				case 'ORDER_SHOP_LGS_NAME':
						$tmpLgsStoreNameArr = LazadaApiHelper::getStoreName($order->selleruserid,$order->order_source_site_id);
						$tmpLgsStoreName = $tmpLgsStoreNameArr[0]==true ? $tmpLgsStoreNameArr[1] : '';
						$obj->innertext=empty($tmpLgsStoreName) ? '' : $tmpLgsStoreName;//LGS店铺
					break;
    				case 'ORDER_BUYER_ID':$obj->innertext=$order->source_buyer_user_id;//买家ID
    				break;
    				case 'ORDER_TRADE_NUMBER'://交易号
    					if ($order->order_source=='ebay'){
    						$obj->innertext=$order->order_source_srn;
    					}else{
    						$obj->innertext=$order->order_source_order_id;
    					}
    					break;
    				case 'ORDER_TOTAL_FEE':$obj->innertext=$order->grand_total.' '.$order->currency;//订单金额（人民币）
    				break;
    				case 'ORDER_TOTAL_FEE_ORIGIN':$obj->innertext=$order->grand_total.' '.$order->currency;//订单金额（原始货币）
    				break;
    				case 'ORDER_CURRENCY':$obj->innertext=$order->currency;//币种
    				break;
    				case 'ORDER_TOTAL_WEIGHT':$obj->innertext=$total_weight;//实际重量
    				break;
    				case 'ORDER_TOTAL_WEIGHT_FORECAST':$obj->innertext=$total_weight;//预估重量
    				break;
    				case 'ORDER_PRINT_TIME':$obj->innertext=date('Y-m-d H:i:s',time());//打印时间
    				break;
    				case 'ORDER_PRINT_TIME2':$obj->innertext=date('m/d H:i:s',time());//打印时间01/18 13:43:40
    				break;
    				case 'ORDER_PRINT_TIME3':$obj->innertext=date('Y-m-d',time());//打印时间2015-12-12
    				break;
    				case 'ORDER_PRINT_TIME_YEAR':$obj->innertext=date('Y',time());//打印时间年
    				break;
    				case 'ORDER_PRINT_TIME_MONTH':$obj->innertext=date('m',time());//打印时间月
    				break;
    				case 'ORDER_PRINT_TIME_DAY':$obj->innertext=date('d',time());//打印时间日
    				break;
    				case 'ORDER_REMARK':$obj->innertext=(empty($order->desc) ? '' : $order->desc);//订单备注
    				break;
    				case 'ORDER_EXPRESS_WAY':$obj->innertext=(empty($shippingService->shipping_method_name) ? '' : $shippingService->shipping_method_name);//平台物流方式如燕文北京平邮
    				break;
    				case 'ORDER_EXPRESS_NAME':$obj->innertext=(empty($shippingService->carrier_name) ? '' : $shippingService->carrier_name);//货运方式物流商
    				break;
    				case 'ORDER_SHIPPING_FEE':$obj->innertext=0;//实付运费
    				break;
    				case 'ORDER_SHIPPING_FEE_FORECAST':$obj->innertext=0;//预估运费
    				break;
    				case 'ORDER_PACKAGE':$obj->innertext='';//包材
    				break;
    				case 'ORDER_HAS_BATTERY':$obj->innertext=$has_battery?'有电池':'';//是否含电池
    				break;
    				case 'ORDER_CODE_BARCODE':
    					$obj->innertext= '<img src="'.Url::to(['/carrier/carrieroperate/barcode','codetype'=>'code128','thickness'=>'30','text'=>$order->order_id,'font'=>0,'fontsize'=>0]).'">';
    					break;
    				case 'ORDER_CODE_BARCODE_PREFIX':
    					$obj->innertext='';//订单号显示订单文本
    				break;
    				case 'ORDER_CODE':$obj->innertext=$order->order_id;//订单编号
    				break;
    				case 'XLB_ORDER_CODE':
    					$obj->innertext=$order->order_id;//小老板订单编号
    					break;
    				case 'XLB_ORDER_CODE_BARCODE':
    					$obj->innertext= "<img src='/carrier/carrieroperate/barcode?codetype=code128&thickness=50&text=".$order->order_id."&font=0&fontsize=0'>";
    					break;
    				case 'LAZADA_PACKAGE_CODE_BARCODE':
    					$tmpPackageId = empty($shippingInfo->return_no['PackageId']) ? '' : $shippingInfo->return_no['PackageId'];
    				    $obj->innertext= '<img src="'.Url::to(['/carrier/carrieroperate/barcode','codetype'=>'code128','thickness'=>'50','text'=>$tmpPackageId,'font'=>0,'fontsize'=>0]).'">';
    				    break;
    				case 'LAZADA_PACKAGE_CODE_BARCODE_PREFIX':
    				    $obj->innertext='';//包裹号显示文本
    				    break;
    				case 'LAZADA_PACKAGE_CODE':
    					$tmpPackageId = empty($shippingInfo->return_no['PackageId']) ? '' : $shippingInfo->return_no['PackageId'];
    				    $obj->innertext=$tmpPackageId;//包裹号
    				    break;
				    case 'ORDER_SOURCE_CODE':
				        $obj->innertext=$order->order_source_order_id;//平台来源订单号
				        break;
			        case 'ORDER_SOURCE_CODE_BARCODE':					//平台来源订单号条码
			        	$obj->innertext= '<img src="'.Url::to(['/carrier/carrieroperate/barcode','codetype'=>'code128','thickness'=>'30','text'=>$order->order_source_order_id,'font'=>0,'fontsize'=>0]).'">';
			        	break;
    				case 'ORDER_EXPRESS_CODE_BARCODE'://货运单号条码图片
    					$tmpOrderExpressCodeBarcode = empty($shippingInfo->tracking_number) ? '' : $shippingInfo->tracking_number;
    					if(!empty($obj->data)){
    						$tmpImgWidth = $obj->data;
    						$obj->innertext= '<img src="'.Url::to(['/carrier/carrieroperate/barcode','codetype'=>'code128','thickness'=>'30','text'=>$tmpOrderExpressCodeBarcode,'font'=>0,'fontsize'=>0]).'" width="'.$tmpImgWidth.'px;">';
    					}else
    					if(!empty($html->find('div[class=dropitem barcode] > img', 0)->data)){
    						$tmpImgWidth = $html->find('div[class=dropitem barcode] > img', 0)->data;
    						$obj->innertext= '<img src="'.Url::to(['/carrier/carrieroperate/barcode','codetype'=>'code128','thickness'=>'30','text'=>$tmpOrderExpressCodeBarcode,'font'=>0,'fontsize'=>0]).'" width="'.$tmpImgWidth.'px;">';
    					}else{
    						$obj->innertext= '<img src="'.Url::to(['/carrier/carrieroperate/barcode','codetype'=>'code128','thickness'=>'30','text'=>$tmpOrderExpressCodeBarcode,'font'=>0,'fontsize'=>0]).'" >';
    					}
    					break;
    				case 'ORDER_EXPRESS_CODE_BARCODE_PREFIX'://货运单号显示文本
    					$obj->innertext= '';
    					break;
    				case 'ORDER_EXPRESS_CODE'://货运单号物流号
    					$obj->innertext= empty($shippingInfo->tracking_number) ? '' : $shippingInfo->tracking_number;
    					break;
    				case 'ORDER_EXPRESS_INTERNAL_CODE_BARCODE':
    					$obj->innertext= '<img src="'.Url::to(['/carrier/carrieroperate/barcode','codetype'=>'code128','thickness'=>'30','text'=>$order->order_id,'font'=>0,'fontsize'=>0]).'">';
    					break;
    				case 'ORDER_EXPRESS_INTERNAL_CODE_BARCODE_PREFIX':$obj->innertext='';//内部单号显示文本
    				break;
    				case 'ORDER_EXPRESS_INTERNAL_CODE':$obj->innertext=$order->order_id;//内部单号
    				break;
    				case 'DELIVERY_COMPANY_YANWEN_USERNAME':$obj->innertext='';//燕文客户编号
    				break;
    				case 'ORDER_PLUS_FIELD_1':break;//扩展字段1
    				break;
    				case 'ORDER_PLUS_FIELD_2':break;//扩展字段2
    				break;
    				case 'ORDER_PLUS_FIELD_3':break;//扩展字段3
    				break;
    				case 'ORDER_PLUS_FIELD_4':break;//扩展字段4
    				break;
    				case 'ORDER_PLUS_FIELD_5':break;//扩展字段5
    				break;
    				case 'ORDER_PLUS_FIELD_6':break;//扩展字段6
    				###########################################################################
    				case 'ITEM_LIST_TOTAL_KIND':$obj->innertext = count($products);//商品种类统计
    				break;
    				case 'ITEM_LIST_TOTAL_QUANTITY':$obj->innertext = $total;//商品数量统计
    				break;
    				case 'ITEM_LIST_TOTAL_WEIGHT':$obj->innertext = $total_weight;//总重量
    				break;
    				case 'ITEM_LIST_TOTAL_AMOUNT_PRICE':$obj->innertext = $total_price;//总金额
    				break;
    				case 'ITEM_LIST_TOTAL_AMOUNT_PRICE_DECLARE':$obj->innertext = $total_price;//总金额
    				break;
    				##########################################################################
    				case 'PRODUCT_SALE_SKU':$obj->innertext = $productInfo['sku'];//主SKU编号
    				break;
    				case 'PRODUCT_ORIGINAL_SKU':$obj->innertext = $productInfo['sku'];//原厂编号
    				break;
    				case 'PRODUCT_WAREHOUSE':$obj->innertext = $productInfo['warehouse'];//仓库
    				break;
    				case 'PRODUCT_WAREHOUSE_GRID_CODE':$obj->innertext = $productInfo['location_grid'];//仓位
    				break;
    				case 'PRODUCT_NAME_CN':$obj->innertext = $productInfo['declaration_ch'];//商品名称（中）
    				break;
    				case 'PRODUCT_NAME_EN':$obj->innertext = $productInfo['declaration_en'];//商品名称（英）
    				break;
    				case 'PRODUCT_WEIGHT':$obj->innertext = $productInfo['prod_weight'];//商品重量
    				break;
    				case 'PRODUCT_BUYER_NAME':$obj->innertext = $productInfo['purchase_by'];//采购员
    				break;
    				case 'PRODUCT_STOCK_SKU_BARCODE'://库存SKU条码
    					$obj->innertext= '<img src="'.Url::to(['/carrier/carrieroperate/barcode','codetype'=>'code128','thickness'=>'30','text'=>$productInfo['sku'],'font'=>0,'fontsize'=>0]).'">';
    				break;
    				case 'PRODUCT_STOCK_SKU_BARCODE_PREFIX':$obj->innertext = '';//库存SKU文本显示
    				break;
    				case 'PRODUCT_STOCK_SKU':$obj->innertext = $productInfo['sku'];//库存SKU编号 
    				break;
    				case 'PRODUCT_INFORMATION':$obj->innertext = $productInformations;//配货信息
    				break;
    				##########################################################################
    				case 'PRODUCT_TITLE':$obj->innertext = $productInfo['name'];	//标题
    				break;
    				default:$obj->innertext='';break;
    		}
    	}
    	foreach($html->find('table[id]') as $obj){
    		switch ($obj->id){
    			case 'FULL_ITEMS_DETAIL_TABLE':
    				$demo_tr = '';
    				$fall_items_detail_table = '';
    				$html2 = new simple_html_dom($obj->innertext);
    				foreach($html2->find('tr[id=ITEM_LIST_DETAIL]') as $obj2){
    					switch ($obj2->id){
    						case 'ITEM_LIST_DETAIL':
    							$demo_tr=$obj2->innertext;
    							break;
    						default:break;
    					}
    				}
    				$productAutoID = 0;
    				foreach ($products as $product){
    					$productAutoID++;
    					$html3 = new simple_html_dom($demo_tr);
    					foreach ($html3->find('littleboss[id]') as $obj3){
    						switch ($obj3->id){
    							case 'ITEM_LIST_DETAIL_SKUAUTOID':$obj3->innertext = $productAutoID;//序号
    							break;
    							case 'ITEM_LIST_DETAIL_PICTURE':$obj3->innertext = "<img src='".$product['photo_primary']."'>";//图片
    							break;
    							case 'ITEM_LIST_DETAIL_SKU':$obj3->innertext = $product['sku'];//SKU
    							break;
    							case 'ITEM_LIST_DETAIL_ORIGINAL_SKU':$obj3->innertext = $product['sku'];//原厂SKU
    							break;
    							case 'ITEM_LIST_DETAIL_ITEM_ID':$obj3->innertext = $product['itemid'];//itemId
    							break;
    							case 'ITEM_LIST_DETAIL_NAME_CN':$obj3->innertext = (empty($distribution_config) ? $product['declaration_ch'] : (empty($product['prod_name_ch']) ? $product['declaration_ch'] : $product['prod_name_ch']));//名称
    							break;
    							case 'ITEM_LIST_DECLARE_NAME_CN':$obj3->innertext = $product['declaration_ch'];//名称
    							break;
    							case 'ITEM_LIST_DETAIL_NAME_EN':$obj3->innertext = $product['declaration_en'];//Name
    							break;
    							case 'ITEM_LIST_DECLARE_NAME_EN':$obj3->innertext = $product['declaration_en'];//Name
    							break;
    							case 'ITEM_LIST_DETAIL_PRODUCT_TITLE':$obj3->innertext = $product['name'];//Name
    							break;
    							case 'ITEM_LIST_DETAIL_WAREHOUSE':$obj3->innertext = $product['warehouse'];//仓库
    							break;
    							case 'ITEM_LIST_DETAIL_GRID_CODE':$obj3->innertext = $product['location_grid'];//仓位
    							break;
    							case 'ITEM_LIST_DETAIL_QUANTITY':$obj3->innertext = $product['quantity'];//数量
    							break;
    							case 'ITEM_LIST_DETAIL_WEIGHT':$obj3->innertext = $product['total_weight'] / 1000;//重量(g)
    							break;
    							case 'ITEM_LIST_DETAIL_PROPERTY':$obj3->innertext =  $product['product_attributes'];//多属性
    							break;
    							case 'ITEM_LIST_DETAIL_PRICE':$obj3->innertext = $product['declaration_value'];//单价
    							break;
    							case 'ITEM_LIST_DETAIL_AMOUNT_PRICE':$obj3->innertext = $product['total_price'];//小计
    							break;
    							default:break;
    						}
    					}
    					$fall_items_detail_table.= '<tr id="ITEM_LIST_DETAIL">'.$html3->__toString().'</tr>';
    				}
    				foreach ($html->find('tr[id=ITEM_LIST_DETAIL]') as $obj){
    					$obj->innertext = $fall_items_detail_table;
    				}
    				break;
    			case 'FULL_ITEMS_DETAIL_TABLE_COPY':
    				$a = $html->find('div[id=PRODUCT_LIST_OVERFLOW]',0);
    				$a->outertext='';
    				break;
    			default:break;
    		}
    	}
    	//return str_replace('littleboss', 'div', $html->__toString());
    	return $html->__toString();
	}
	public static function getPrintMap($template){
	 	return array('EUB_RECEIVER_ZIPCODE'=>'zip');
		
	}
	
	/**
	 * 高仿标签
	 * $template 自定义模板对象
	 * $shippingServece 运输服务对象
	 * 获取自定义模板中字段和数据库字段关系
	 * @return array(array('数据库模型名','对应字段'))
	 */
	public static function getHighCopyPrintData($template,$shippingService,$order,$carrierConfig){
		$html = new simple_html_dom($template->template_content);
		
		//地址信息
		$shippingfrom_en = isset($carrierConfig['address']['shippingfrom_en'])?$carrierConfig['address']['shippingfrom_en']:array();
		
		//LGS使用指定发货人地址
		if($shippingService->carrier_code == 'lb_LGS'){
			$info = \eagle\modules\carrier\helpers\CarrierAPIHelper::getAllInfo($order);
			
			if(!empty($info['senderAddressInfo']['shippingfrom'])){
				$shippingfrom_en = Array(
						'country' => $info['senderAddressInfo']['shippingfrom']['country'],
						'province' => $info['senderAddressInfo']['shippingfrom']['province_en'],
						'city' => $info['senderAddressInfo']['shippingfrom']['city_en'],
						'district' => $info['senderAddressInfo']['shippingfrom']['district_en'],
						'street' => $info['senderAddressInfo']['shippingfrom']['street_en'],
						'postcode' => $info['senderAddressInfo']['shippingfrom']['postcode'],
						'company' => $info['senderAddressInfo']['shippingfrom']['company_en'],
						'contact' => $info['senderAddressInfo']['shippingfrom']['contact_en'],
						'phone' => $info['senderAddressInfo']['shippingfrom']['phone'],
						'mobile' => $info['senderAddressInfo']['shippingfrom']['mobile'],
						'fax' => $info['senderAddressInfo']['shippingfrom']['fax'],
						'email' => $info['senderAddressInfo']['shippingfrom']['email'],
				);
			}
		}
		
		
		$shipingfrom_country_code = isset($shippingfrom_en['country'])?$shippingfrom_en['country']:"CN";
		$shipingfrom_country =  SysCountry::findOne(['country_code'=>$shipingfrom_country_code]);
		if($shippingService->carrier_code == 'lb_LGS'){
			$shippingInfo= OdOrderShipped::find()->where(['order_id'=>$order->order_id])->orderBy('addtype desc,id desc')->one();
		}else{
			$shippingInfo= OdOrderShipped::find()->where(['order_id'=>$order->order_id])->andWhere(['customer_number'=>$order->customer_number])->orderBy('id desc')->one();
		}
		
        if ($shippingInfo == null){
			return array('error' => 1, 'msg' => '请检查订单'.$order->order_id.'是否已经上传');
		}
		if($shippingService->carrier_code == 'lb_LGS'){
			if(empty($shippingInfo->tracking_number)){
				return array('error' => 1, 'msg' => '请检查订单'.$order->order_id.'是否已经获取跟踪号');
			}
		}
		//报关信息
		$declarationInfo =CarrierApiHelper::getDeclarationInfo($order, $shippingService,true,true);
		if($shippingService->carrier_code == 'lb_IEUB'){
			$products = array();
			
			$class_name = '\common\api\carrierAPI\\'.'LB_IEUBCarrierAPI';
			$interface = new $class_name('lb_IEUB');
			
			$carrier_result = $interface->getMailInfo($order);
			
			if($carrier_result['error'] == 1){
				return array('error' => 1, 'msg' => '请检查订单'.$order->order_id.'是否已经上传_E02');
			}

			if(!isset($carrier_result['data_info']['items']['item'][0])){
				$tmpItems = $carrier_result['data_info']['items']['item'];
				unset($carrier_result['data_info']['items']['item']);
				$carrier_result['data_info']['items']['item'][0] = $tmpItems;
			}
			$total = 0;
			$total_price = 0;
			$total_weight = 0;//克
			
			foreach ($carrier_result['data_info']['items']['item'] as $carrier_items_info){
				$product = array(
						'name'=>$carrier_items_info['cnname'],
						'photo_primary'=>'',
						'declaration_ch'=>$carrier_items_info['cnname'],
						'declaration_en'=>$carrier_items_info['enname'],
						'declaration_value'=>$carrier_items_info['delcarevalue'],
						'total_price'=>$carrier_items_info['delcarevalue'],
						'declaration_value_currency'=>'USD',
						'prod_weight'=>$carrier_items_info['weight'],
						'total_weight'=>$carrier_items_info['weight'],
						'battery'=>'N',
						'note'=>'',
						'quantity'=>$carrier_items_info['count'],
						'sku'=>'',
						'product_attributes'=>'',
						'transactionid'=>'',//交易号
						'itemid'=>'',//在线商品的刊登号或商品号
						'warehouse'=>'',//仓库
						'location_grid'=>'无',//货位
						'purchase_by'=>''
				);
				
				$total+=$product['quantity'];
				$total_price+=$product['total_price'];
				$total_weight+=$product['total_weight'];
				$products[] = $product;
			}
			
			$declarationInfo = array(
						'total'=>$total,
						'currency'=>$order->currency,
						'total_price'=>$total_price,
						'total_weight'=>$total_weight,//克
						'has_battery'=>false,
						'products'=>$products,
				);
			unset($products);
			
			$products = $declarationInfo['products'];
		}else if($shippingService->carrier_code == 'lb_LGS'){
		    $products = array();
		    $total = 0;
		    $total_price = 0;
		    $total_weight = 0;//克
		    	
		    foreach ($order->items as $k=>$v){
		        $product = array(
		            'name'=>'',
		            'photo_primary'=>'',
		            'declaration_ch'=>'',
		            'declaration_en'=>$v->product_name,
		            'declaration_value'=>'',
		            'total_price'=>0,
		            'declaration_value_currency'=>'USD',
		            'prod_weight'=>'',
		            'total_weight'=>0,
		            'battery'=>'',
		            'note'=>'',
		            'quantity'=>$v->quantity,
		            'sku'=>$v->sku,
		            'product_attributes'=>'',
		            'transactionid'=>'',//交易号
		            'itemid'=>$v->order_source_itemid,//在线商品的刊登号或商品号
		            'warehouse'=>'',//仓库
		            'location_grid'=>'无',//货位
		            'purchase_by'=>''
		        );
		    
		        $total+=$product['quantity'];
		        $total_price+=$product['total_price'];
		        $total_weight+=$product['total_weight'];
		        $products[] = $product;
		    }
		}else{
			$products =$declarationInfo['products'];
		}
		
		//商品信息
		$productInfo = $products[0];
		//处理订单的预约sku信息
		if($shippingService->carrier_code == 'lb_IEUB' || $shippingService->carrier_code == 'lb_shenzhenyouzheng'){
			$total_weight = ($declarationInfo['total_weight']);
		}else
			$total_weight = ($declarationInfo['total_weight'] / 1000);
		$total_price = $declarationInfo['total_price'];
		$total = $declarationInfo['total'];
		$has_battery = $declarationInfo['has_battery'];
		
		$productInformations = '';
		$tmpSKUMode2='';       //多个sku
		$productOneCount=0;
		foreach ($products as $productOne){
			$productInformations .= $productOne['sku'].' * '.$productOne['quantity'].';';

			if($shippingService->carrier_code == 'lb_shenzhenyouzheng'){      
				if(!empty($productOne['declaration_ch']))
					$tmpSKUMode2.=$productOne['declaration_ch'].' * '.$productOne['quantity'].';';
			}
			$productOneCount++;
		}
		
		if(!empty($productInformations)){
			$productInformations = substr($productInformations,0,strlen($productInformations)-1);
		}
		
		$printOrderPlusField = CarrierPartitionNumberHelper::getOrderPlusField($shippingService->carrier_account_id);
		
		if($template->template_type == '配货单'){
			$distribution_config = ConfigHelper::getConfig('d_listpicking_name');
			$distribution_config = empty($distribution_config) ? 0 : $distribution_config;
		}
		
		//地址信息
		$tmpAddressMode1 = '';
		if(!empty($order->consignee_company)){
			$tmpAddressMode1 = $order->consignee_company.';';
		}
		if(!empty($order->consignee_address_line1))
			$tmpAddressMode1 .= $order->consignee_address_line1;
		if(!empty($order->consignee_address_line2))
			$tmpAddressMode1 .=' '. $order->consignee_address_line2;
		if(!empty($order->consignee_address_line3))
			$tmpAddressMode1 .= ' '.$order->consignee_address_line3;
		if(!empty($order->consignee_district)){
			$tmpAddressMode1 .=';'. $order->consignee_district;
		}
		if(!empty($order->consignee_county)){
			$tmpAddressMode1 .=','. $order->consignee_county;
		}
		
		$tmpAddressMode2 = '';
		if(!empty($order->consignee_address_line1))
			$tmpAddressMode2 .= $order->consignee_address_line1;
		if(!empty($order->consignee_address_line2))
			$tmpAddressMode2 .=' '. $order->consignee_address_line2;
		if(!empty($order->consignee_address_line3))
			$tmpAddressMode2 .= ' '.$order->consignee_address_line3;
		
		$tmpAddressMode3 = '';
		if(!empty($tmpAddressMode2))
			$tmpAddressMode3 .= $tmpAddressMode2;
		if(!empty($order->consignee_district))
			$tmpAddressMode3 .= ','.$order->consignee_district;
		if(!empty($order->consignee_county))
			$tmpAddressMode3 .= ','.$order->consignee_county;
		if(!empty($order->consignee_city))
			$tmpAddressMode3 .= ','.$order->consignee_city;
		if(!empty($order->consignee_province))
			$tmpAddressMode3 .= ','.$order->consignee_province;
		if(!empty($order->consignee_postal_code))
			$tmpAddressMode3 .= ','.$order->consignee_postal_code;
		if(!empty($order->consignee_country))
			$tmpAddressMode3 .= ','.$order->consignee_country;
		$tmpAddressMode3 .= ','.SysCountry::findOne(['country_code'=>$order->consignee_country_code])->country_zh;
		if(!empty($order->consignee_country_code))
			$tmpAddressMode3 .= ','.$order->consignee_country_code;
		
		foreach($html->find('littleboss[id]') as $obj){
			switch ($obj->id){
				case 'RECEIVER_NAME':$obj->innertext=(empty($order->consignee) ? '' : $order->consignee);//收件人
				break;
				case 'RECEIVER_ADDRESS':$obj->innertext=$order->consignee_address_line1.(strlen($order->consignee_address_line2)>0?'<br>'.$order->consignee_address_line2:'').(strlen($order->consignee_address_line3)>0?'<br>'.$order->consignee_address_line3:'');//收件人详细地址
				break;
				case 'RECEIVER_ADDRESS_MODE2':$obj->innertext=$tmpAddressMode2;//收件人详细地址
				break;
				case 'RECEIVER_SHENZHEN_ADDRESS_MODE':$obj->innertext=$tmpAddressMode3;
				break;
				case 'RECEIVER_AREA':$obj->innertext=(empty($order->consignee_district) ? '' : $order->consignee_district);//收件人地区/州
				break;
				case 'RECEIVER_COUNTY':$obj->innertext=(empty($order->consignee_county) ? '' : $order->consignee_county);//收货人镇
				break;
				case 'RECEIVER_CITY':$obj->innertext=(empty($order->consignee_city) ? '' : $order->consignee_city);//收件人城市
				break;
				case 'RECEIVER_PROVINCE':$obj->innertext=(empty($order->consignee_province) ? '' : $order->consignee_province);//收件人省份
				break;
				case 'RECEIVER_COUNTRY_EN':$obj->innertext=(empty($order->consignee_country) ? '' : $order->consignee_country);//收件人国家(英)
				break;
				case 'RECEIVER_COUNTRY_CN':
					$country_zh = SysCountry::findOne(['country_code'=>$order->consignee_country_code])->country_zh;
					$obj->innertext=$country_zh;//收件人国家(中)
					break;
				case 'RECEIVER_ZIPCODE':$obj->innertext=(empty($order->consignee_postal_code) ? '' : $order->consignee_postal_code);//收件人邮编
				break;
				case 'RECEIVER_ZIPCODE_BARCODE':
					$obj->innertext= '<img src="'.Url::to(['/carrier/carrieroperate/barcode','codetype'=>'code128','thickness'=>'30','text'=>(empty($order->consignee_postal_code) ? '' : $order->consignee_postal_code),'font'=>0,'fontsize'=>0]).'">';
					break;
				case 'RECEIVER_ZIPCODE_BARCODE_PREFIX':$obj->innertext='';//邮编字符串
				break;
				case 'RECEIVER_TELEPHONE':$obj->innertext=(empty($order->consignee_phone) ? '' : $order->consignee_phone);//收件人电话电话1
				break;
				case 'RECEIVER_MOBILE':$obj->innertext=(empty($order->consignee_mobile) ? '' : $order->consignee_mobile);//收件人手机电话2
				break;
				case 'RECEIVER_EMAIL':$obj->innertext=(empty($order->consignee_email) ? '' : $order->consignee_email);//收件人邮箱
				break;
				case 'RECEIVER_COMPANY':$obj->innertext=(empty($order->consignee_company) ? '' : $order->consignee_company);//收件人公司
				break;
				case 'RECEIVER_COMPANY_MODE2':$obj->innertext=(empty($order->consignee_company) ? '' : $order->consignee_company.';');//收件人公司
				break;
				case 'RECEIVER_DETAILED_ADDRESS':$obj->innertext=$tmpAddressMode1;//收件人地址包含公司,区,镇等信息
				break;
				case 'RECEIVER_COUNTRY_EN_AB':$obj->innertext=(empty($order->consignee_country_code) ? '' : $order->consignee_country_code);//收件人国家英文简称国际代码
				break;
				case 'INTERNATIONAL_REGISTERED_PARCEL_SORTING_AREA':	//国际挂号小包分拣区
                case 'RECEIVER_COUNTRY_EN_NUM_WISH'://wish邮收件人国家简码+分拣码
                    $receiver_country_en_num = $order->consignee_country_code.self::getNumCode($order->consignee_country_code);
                    $obj->innertext=(empty($order->consignee_country_code) ? '' : $receiver_country_en_num);
                break;
                case 'UNTRACKED_R_WISH'://wish邮区别平邮与挂号(挂号==1,平邮==0)
                    $obj->innertext=$shippingService->carrier_params['otype']== 0?'<span style="font-size: xx-large;">R</span>':'untracked';
                break;
                case 'INTERNATIONAL_REGISTERED_PARCEL_PARTITION':	//国际挂号小包分区
                	$obj->innertext=(empty($order->consignee_country_code) ? '' : CarrierPartitionNumberHelper::getInternationalRegisteredParcelPartition($order->consignee_country_code));
                break;
                case 'INTERNATIONAL_COMMON_PACKET_PARTITION':	//国际平常小包分区
                	$obj->innertext=(empty($order->consignee_country_code) ? '' : CarrierPartitionNumberHelper::getInternationalCommonPacketPartition($order->consignee_country_code));
                	break;
				case 'RECEIVER_COUNTRY_EXPRESS_AREA':$obj->innertext=$order->consignee_country_code;//收件人国家分区
				break;
				case 'RECEIVER_PAYMENT':
		            if($order->order_source == "lazada"){
				        if(!empty($order->payment_type)){
				            $obj->innertext = $order->payment_type;
				        }else{
				            $order_payment_array = LazadaApiHelper::getPaymentMethod($order);
				            if($order_payment_array[0]){
				                $obj->innertext = $order_payment_array[1];
				            }else{
				                $obj->innertext = '';
				            }
				        }
			        }else{
			            $obj->innertext=$order->payment_type;//付款方式
			        }
				break;
				case 'EUB_RECEIVER_ZIPCODE_BARCODE'://EUB专用邮编条码gs1128
					$obj->innertext= '<img src="'.Url::to(['/carrier/carrieroperate/barcode','codetype'=>'gs1128','thickness'=>'50','text'=>(empty($order->consignee_postal_code) ? '' : '420'.$order->consignee_postal_code),'font'=>0,'fontsize'=>0]).'">';
					break;
				case 'EUB_RECEIVER_ZIPCODE':$obj->innertext=(empty($order->consignee_postal_code) ? '' : $order->consignee_postal_code);//EUB专用邮编
				break;
				###########################################################################
				case 'SENDER_NAME':$obj->innertext=isset($shippingfrom_en['contact'])?$shippingfrom_en['contact']:'';//发件人姓名
				break;
				case 'SENDER_ADDRESS':$obj->innertext=isset($shippingfrom_en['street'])?$shippingfrom_en['street']:'';//发件人地址
				break;
				case 'SENDER_AREA':$obj->innertext=isset($shippingfrom_en['district'])?$shippingfrom_en['district']:'';//发件人地区中文
				break;
				case 'SENDER_AREA_EN':$obj->innertext=isset($shippingfrom_en['district'])?$shippingfrom_en['district']:'';//发件人地区英文
				break;
				case 'SENDER_CITY':$obj->innertext=isset($shippingfrom_en['city'])?$shippingfrom_en['city']:'';//发件人城市
				break;
				case 'SENDER_CITY_EN':$obj->innertext=isset($shippingfrom_en['city'])?$shippingfrom_en['city']:'';//发件人城市英文
				break;
				case 'SENDER_PROVINCE':$obj->innertext=isset($shippingfrom_en['province'])?$shippingfrom_en['province']:'';//发件人省份
				break;
				case 'SENDER_PROVINCE_EN':$obj->innertext=isset($shippingfrom_en['province'])?$shippingfrom_en['province']:'';//发件人省份英文
				break;
				case 'SENDER_COUNTRY_CN':$obj->innertext=(empty($shipingfrom_country->country_zh) ? '' : $shipingfrom_country->country_zh);//发件人国家
				break;
				case 'SENDER_COUNTRY_EN':$obj->innertext=(empty($shipingfrom_country->country_en) ? '' : $shipingfrom_country->country_en);//发件人国家英文
				break;
				case 'SENDER_COUNTRY_EN_AB':$obj->innertext=$shipingfrom_country_code;//发件人国家英文简称国际代码
				break;
				case 'SENDER_ZIPCODE':$obj->innertext=isset($shippingfrom_en['postcode'])?$shippingfrom_en['postcode']:'';//发件人邮编
				break;
				case 'SENDER_TELEPHONE':$obj->innertext=isset($shippingfrom_en['phone'])?$shippingfrom_en['phone']:'';//发件人电话
				break;
				case 'SENDER_MOBILE':$obj->innertext=isset($shippingfrom_en['mobile'])?$shippingfrom_en['mobile']:'';//发件人邮编
				break;
				case 'SENDER_EMAIL':$obj->innertext=isset($shippingfrom_en['email'])?$shippingfrom_en['email']:'';//发件人邮箱
				break;
				case 'SENDER_COMPANY_NAME':$obj->innertext=isset($shippingfrom_en['company'])?$shippingfrom_en['company']:'';//发件人公司
				break;
				##########################################################################
				case 'ORDER_SHOP_NAME':$obj->innertext=(($order->order_source == 'amazon') ? (AmazonApiHelper::getAmzStoreName($order->selleruserid)) : $order->selleruserid);//店铺
				break;
				case 'ORDER_SHOP_LGS_NAME':
					$tmpLgsStoreNameArr = LazadaApiHelper::getStoreName($order->selleruserid,$order->order_source_site_id);
					$tmpLgsStoreName = $tmpLgsStoreNameArr[0]==true ? $tmpLgsStoreNameArr[1] : '';
					$obj->innertext=empty($tmpLgsStoreName) ? '' : $tmpLgsStoreName;//LGS店铺
				break;
				case 'ORDER_BUYER_ID':$obj->innertext=(empty($order->source_buyer_user_id) ? '' : $order->source_buyer_user_id);//买家ID
				break;
				case 'ORDER_TRADE_NUMBER'://交易号
					if ($order->order_source=='ebay'){
						$obj->innertext=(empty($order->order_source_srn) ? '' : $order->order_source_srn);
					}else{
						$obj->innertext=(empty($order->order_source_order_id) ? '' : $order->order_source_order_id);
					}
					break;
				case 'ORDER_TOTAL_FEE':$obj->innertext=$order->grand_total.' '.$order->currency;//订单金额（人民币）
				break;
				case 'ORDER_TOTAL_FEE_ORIGIN':$obj->innertext=$order->grand_total.' '.$order->currency;//订单金额（原始货币）
				break;
				case 'ORDER_CURRENCY':$obj->innertext=$order->currency;//币种
				break;
				case 'ORDER_TOTAL_WEIGHT':$obj->innertext=$total_weight;//实际重量
				break;
				case 'ORDER_TOTAL_WEIGHT_FORECAST':$obj->innertext=$total_weight;//预估重量
				break;
				case 'ORDER_PRINT_TIME':$obj->innertext=date('Y-m-d H:i:s',time());//打印时间
				break;
				case 'ORDER_PRINT_TIME2':$obj->innertext=date('m/d H:i:s',time());//打印时间01/18 13:43:40
				break;
				case 'ORDER_PRINT_TIME3':$obj->innertext=date('Y-m-d',time());//打印时间2015-12-12
				break;
				case 'ORDER_PRINT_TIME_YEAR':$obj->innertext=date('Y',time());//打印时间年
				break;
				case 'ORDER_PRINT_TIME_MONTH':$obj->innertext=date('m',time());//打印时间月
				break;
				case 'ORDER_PRINT_TIME_DAY':$obj->innertext=date('d',time());//打印时间日
				break;
				case 'ORDER_REMARK':$obj->innertext=(empty($order->desc) ? '' : $order->desc);//订单备注
				break;
				case 'ORDER_EXPRESS_WAY':$obj->innertext=(empty($shippingService->shipping_method_name) ? '' : $shippingService->shipping_method_name);//平台物流方式如燕文北京平邮
				break;
				case 'ORDER_EXPRESS_NAME':$obj->innertext=(empty($shippingService->carrier_name) ? '' : $shippingService->carrier_name);//货运方式物流商
				break;
				case 'ORDER_SHIPPING_FEE':$obj->innertext=0;//实付运费
				break;
				case 'ORDER_SHIPPING_FEE_FORECAST':$obj->innertext=0;//预估运费
				break;
				case 'ORDER_PACKAGE':$obj->innertext='';//包材
				break;
				case 'ORDER_HAS_BATTERY':$obj->innertext=$has_battery?'有电池':'';//是否含电池
				break;
				case 'ORDER_CODE_BARCODE':
					//这里因为后台需要使用到这个JPG而这个JPG会到html转PDF的服务器的时候再生成一次所以这里这么调用
// 					$obj->innertext= '<img src="'.Url::to(['/carrier/carrieroperate/barcode','codetype'=>'code128','thickness'=>'50','text'=>$shippingInfo->tracking_number,'font'=>0,'fontsize'=>0]).'">';
					$obj->innertext= "<img src='/carrier/carrieroperate/barcode?codetype=code128&thickness=50&text=".$shippingInfo->tracking_number."&font=0&fontsize=0'>";
					break;
				case 'ORDER_CODE_BARCODE_PREFIX':
					$obj->innertext='';//订单号显示订单文本
					break;
				case 'ORDER_CODE':
					$obj->innertext=$shippingInfo->tracking_number;//订单编号
				break;
				case 'XLB_ORDER_CODE':
					$obj->innertext=$order->order_id;//小老板订单编号
					break;
				case 'XLB_ORDER_CODE_BARCODE':
					$obj->innertext= "<img src='/carrier/carrieroperate/barcode?codetype=code128&thickness=50&text=".$order->order_id."&font=0&fontsize=0'>";
					break;
				case 'LAZADA_PACKAGE_CODE_BARCODE':
					$tmpPackageId = empty($shippingInfo->return_no['PackageId']) ? '' : $shippingInfo->return_no['PackageId'];
				    $obj->innertext= '<img src="'.Url::to(['/carrier/carrieroperate/barcode','codetype'=>'code128','thickness'=>'50','text'=>$tmpPackageId,'font'=>0,'fontsize'=>0]).'">';
				    break;
				case 'LAZADA_PACKAGE_CODE_BARCODE_PREFIX':
				    $obj->innertext='';//包裹号显示订单文本
				    break;
				case 'LAZADA_PACKAGE_CODE':
					$tmpPackageId = empty($shippingInfo->return_no['PackageId']) ? '' : $shippingInfo->return_no['PackageId'];
				    $obj->innertext=$tmpPackageId;//包裹号
				    break;
			    case 'ORDER_SOURCE_CODE':
			        $obj->innertext=$order->order_source_order_id;//平台来源订单号
			        break;
		        case 'ORDER_SOURCE_CODE_BARCODE':					//平台来源订单号条码
		        	$obj->innertext= '<img src="'.Url::to(['/carrier/carrieroperate/barcode','codetype'=>'code128','thickness'=>'30','text'=>$order->order_source_order_id,'font'=>0,'fontsize'=>0]).'">';
		        	break;
				case 'ORDER_EXPRESS_CODE_BARCODE'://货运单号条码图片
					$obj->innertext= '<img src="'.Url::to(['/carrier/carrieroperate/barcode','codetype'=>'code128','thickness'=>'30','text'=>$shippingInfo->tracking_number,'font'=>0,'fontsize'=>0]).'">';
					break;
				case 'ORDER_EXPRESS_CODE_BARCODE_PREFIX'://货运单号显示文本
					$obj->innertext= '';
					break;
				case 'ORDER_EXPRESS_CODE'://货运单号物流号
					$obj->innertext= $shippingInfo->tracking_number;
					break;
				case 'ORDER_EXPRESS_INTERNAL_CODE_BARCODE':
					$obj->innertext= '<img src="'.Url::to(['/carrier/carrieroperate/barcode','codetype'=>'code128','thickness'=>'30','text'=>$order->customer_number,'font'=>0,'fontsize'=>0]).'">';
					break;
				case 'ORDER_EXPRESS_INTERNAL_CODE_BARCODE_PREFIX':$obj->innertext='';//内部单号显示文本
				break;
				case 'ORDER_EXPRESS_INTERNAL_CODE':$obj->innertext=$order->customer_number;//内部单号
				break;
				case 'DELIVERY_COMPANY_YANWEN_USERNAME':$obj->innertext='';//燕文客户编号
				break;
				case 'PARTITION_NUMBER_4PX_LYT_GH':
				case 'PARTITION_NUMBER_EUB_USA':
					$obj->innertext=CarrierPartitionNumberHelper::getPartitionNumber($order->default_carrier_code,$shippingService->shipping_method_code,$order->consignee_country_code,$order->consignee_postal_code);//4px联邮通挂号LYT分区号,国际EUB美国分区号
					break;
				case 'PARTITION_NUMBER_SHENZHEN_GH':
					$obj->innertext=CarrierPartitionNumberHelper::getPartitionNumberShenzhen($order->consignee_country_code);//深圳邮政分区号
					break;
				case 'ORDER_PLUS_FIELD_1':
					$obj->innertext = empty($printOrderPlusField['PRINT_ORDER_PLUS_FIELD_1']) ? '' : $printOrderPlusField['PRINT_ORDER_PLUS_FIELD_1'];
					break;//扩展字段1
				break;
				case 'ORDER_PLUS_FIELD_2':
					$obj->innertext = empty($printOrderPlusField['PRINT_ORDER_PLUS_FIELD_2']) ? '' : $printOrderPlusField['PRINT_ORDER_PLUS_FIELD_2'];
					break;//扩展字段2
				break;
				case 'ORDER_PLUS_FIELD_3':
					$obj->innertext = empty($printOrderPlusField['PRINT_ORDER_PLUS_FIELD_3']) ? '' : $printOrderPlusField['PRINT_ORDER_PLUS_FIELD_3'];
					break;//扩展字段3
				break;
				case 'ORDER_PLUS_FIELD_4':
					$obj->innertext = empty($printOrderPlusField['PRINT_ORDER_PLUS_FIELD_4']) ? '' : $printOrderPlusField['PRINT_ORDER_PLUS_FIELD_4'];
					break;//扩展字段4
				break;
				case 'ORDER_PLUS_FIELD_5':
					$obj->innertext = empty($printOrderPlusField['PRINT_ORDER_PLUS_FIELD_5']) ? '' : $printOrderPlusField['PRINT_ORDER_PLUS_FIELD_5'];
					break;//扩展字段5
				break;
				case 'ORDER_PLUS_FIELD_6':
					$obj->innertext = empty($printOrderPlusField['PRINT_ORDER_PLUS_FIELD_6']) ? '' : $printOrderPlusField['PRINT_ORDER_PLUS_FIELD_6'];
					break;//扩展字段6
				case 'ORDER_PLUS_FIELD_7':
					$obj->innertext = empty($printOrderPlusField['PRINT_ORDER_PLUS_FIELD_7']) ? '' : $printOrderPlusField['PRINT_ORDER_PLUS_FIELD_7'];
					break;//扩展字段7
				###########################################################################
				case 'ITEM_LIST_TOTAL_KIND':$obj->innertext = count($products);//商品种类统计
				break;
				case 'ITEM_LIST_TOTAL_QUANTITY':$obj->innertext = $total;//商品数量统计
				break;
				case 'ITEM_LIST_TOTAL_WEIGHT':$obj->innertext = $total_weight;//总重量
				break;
				case 'ITEM_LIST_TOTAL_AMOUNT_PRICE':$obj->innertext = $total_price;//总金额
				break;
				case 'ITEM_LIST_TOTAL_AMOUNT_PRICE_DECLARE':$obj->innertext = $total_price;//总金额
				break;
				##########################################################################
				case 'PRODUCT_SALE_SKU':$obj->innertext = $productInfo['sku'];//主SKU编号
				break;
				case 'PRODUCT_SALE_SKU2':$obj->innertext = $tmpSKUMode2;//SKU编号2
				break;
				case 'PRODUCT_ORIGINAL_SKU':$obj->innertext = $productInfo['sku'];//原厂编号
				break;
				case 'PRODUCT_WAREHOUSE':$obj->innertext = $productInfo['warehouse'];//仓库
				break;
				case 'PRODUCT_WAREHOUSE_GRID_CODE':$obj->innertext = $productInfo['location_grid'];//仓位
				break;
				case 'PRODUCT_NAME_CN':$obj->innertext = $productInfo['declaration_ch'];//商品名称（中）
				break;
				case 'PRODUCT_NAME_EN':$obj->innertext = $productInfo['declaration_en'];//商品名称（英）
				break;
				case 'PRODUCT_WEIGHT':$obj->innertext = $productInfo['prod_weight'];//商品重量
				break;
				case 'PRODUCT_BUYER_NAME':$obj->innertext = $productInfo['purchase_by'];//采购员
				break;
				case 'PRODUCT_STOCK_SKU_BARCODE'://库存SKU条码
					$obj->innertext= '<img src="'.Url::to(['/carrier/carrieroperate/barcode','codetype'=>'code128','thickness'=>'30','text'=>$productInfo['sku'],'font'=>0,'fontsize'=>0]).'">';
					break;
				case 'PRODUCT_STOCK_SKU_BARCODE_PREFIX':$obj->innertext = '';//库存SKU文本显示
				break;
				case 'PRODUCT_STOCK_SKU':$obj->innertext = $productInfo['sku'];//库存SKU编号
				break;
				case 'PRODUCT_INFORMATION':$obj->innertext = $productInformations;//配货信息
				break;
				case 'PRODUCT_TITLE':$obj->innertext = $productInfo['name'];	//标题
				break;
				##########################################################################
				default:$obj->innertext='';break;
			}
		}

		foreach($html->find('table[id]') as $obj){
			switch ($obj->id){
				case 'FULL_ITEMS_DETAIL_TABLE':
					$demo_tr = '';
					$fall_items_detail_table = '';
					$html2 = new simple_html_dom($obj->innertext);
					foreach($html2->find('tr[id=ITEM_LIST_DETAIL]') as $obj2){
						switch ($obj2->id){
							case 'ITEM_LIST_DETAIL':
								$demo_tr=$obj2->innertext;
								break;
							default:break;
						}
					}

					$productAutoID = 0;
					foreach ($products as $product){
						$productAutoID++;
						
						//不可以这么写因为配货单也是使用这段高仿代码
// 						if($shippingService->carrier_code == 'lb_IEUB'){
// 							if($productAutoID > 5){
// 								break;
// 							}
// 						}
//                         //最大限制产品行为2行
//                         if($shippingService->carrier_code == 'lb_wishyou'){
//                             if($productAutoID > 1){
//                                 break;
//                             }
//                         }
//                         //最大限制产品行为4行
//                         if($shippingService->carrier_code == 'lb_shenzhenyouzheng'){
//                         	if($productAutoID > 4){
//                         		break;
//                         	}
//                         }
						
						$html3 = new simple_html_dom($demo_tr);
						foreach ($html3->find('littleboss[id]') as $obj3){
							switch ($obj3->id){
								case 'ITEM_LIST_DETAIL_SKUAUTOID':$obj3->innertext = $productAutoID;//序号
								break;
								case 'ITEM_LIST_DETAIL_PICTURE':$obj3->innertext = $product['photo_primary'];//图片
								break;
								case 'ITEM_LIST_DETAIL_SKU':$obj3->innertext = $product['sku'];//SKU
								break;
								case 'ITEM_LIST_DETAIL_ORIGINAL_SKU':$obj3->innertext = $product['sku'];//原厂SKU
								break;
								case 'ITEM_LIST_DETAIL_ITEM_ID':$obj3->innertext = $product['itemid'];//itemId
								break;
								case 'ITEM_LIST_DETAIL_NAME_CN':$obj3->innertext = (empty($distribution_config) ? $product['declaration_ch'] : (empty($product['prod_name_ch']) ? $product['declaration_ch'] : $product['prod_name_ch']));//名称
								break;
								case 'ITEM_LIST_DECLARE_NAME_CN':$obj3->innertext = $product['declaration_ch'];//名称
								break;
								case 'ITEM_LIST_DETAIL_NAME_EN':$obj3->innertext = $product['declaration_en'];//Name
								break;
								case 'ITEM_LIST_DECLARE_NAME_EN':$obj3->innertext = $product['declaration_en'];//Name
								break;
								case 'ITEM_LIST_DETAIL_PRODUCT_TITLE':$obj3->innertext = $product['name'];//Name
								break;
								case 'ITEM_LIST_DETAIL_WAREHOUSE':$obj3->innertext = $product['warehouse'];//仓库
								break;
								case 'ITEM_LIST_DETAIL_GRID_CODE':$obj3->innertext = $product['location_grid'];//仓位
								break;
								case 'ITEM_LIST_DETAIL_QUANTITY':$obj3->innertext = $product['quantity'];//数量
								break;
								case 'ITEM_LIST_DETAIL_WEIGHT':$obj3->innertext = ($shippingService->carrier_code == 'lb_IEUB' || $shippingService->carrier_code == 'lb_shenzhenyouzheng') ? $product['total_weight'] : ($product['total_weight'] / 1000);//重量(g)
								break;
								case 'ITEM_LIST_DETAIL_PROPERTY':$obj3->innertext =  $product['product_attributes'];//多属性
								break;
								case 'ITEM_LIST_DETAIL_PRICE':$obj3->innertext = $product['declaration_value'];//单价
								break;
								case 'ITEM_LIST_DETAIL_AMOUNT_PRICE':$obj3->innertext = $product['total_price'];//小计
								break;
								default:break;
							}
						}
                            $fall_items_detail_table.= '<tr id="ITEM_LIST_DETAIL">'.$html3->__toString().'</tr>';
					}
					foreach ($html->find('tr[id=ITEM_LIST_DETAIL]') as $obj){
						$obj->innertext = $fall_items_detail_table;
					}

                    //这里为了补全空行
					if($shippingService->carrier_code == 'lb_IEUB'){
						$tmpCountTr = count($products)-1;
						$int1 = 0;
						if($tmpCountTr > 0)
						foreach ($html->find('tr[id=ITEM_LIST_NO_DETAIL]') as $obj){
							$obj->innertext = "";
							$int1++;
							if($int1 >= $tmpCountTr)
							break;
						}
					}
					
					break;
				case 'FULL_ITEMS_DETAIL_TABLE_COPY':
					$a = $html->find('div[id=PRODUCT_LIST_OVERFLOW]',0);
					$a->outertext='';
					break;
				default:break;
			}
		}
		//return str_replace('littleboss', 'div', $html->__toString());
		return $html->__toString();
//        $otype = $shippingService->carrier_params['otype'];
	}


    //中邮挂号小包国家分区  国家简码对应国家分拣码
    public static function getNumCode($country_en){
        $num_code = '';
        $codeArr = array(
                'RU'=>21,//俄罗斯	RUSSIA
                'US'=>22,//美国	AMERICA
                'GB'=>23,//英国	BRITAIN
        		'UK'=>23,//英国	BRITAIN
                'BR'=>24,//巴西	BRAZIL
                'AU'=>25,//澳大利亚	AUSTRALIA
                'FR'=>26,//法国	FRANCE
                'ES'=>27,//西班牙	SPAIN
                'CA'=>28,//加拿大	CANADA
                'IL'=>29,//以色列	ISRAEL
                'IT'=>30,//意大利	ITALY
                'DE'=>31,//德国	GERMANY
                'CL'=>32,//智利	CHILE
                'SE'=>33,//瑞典	SWEDEN
                'BY'=>34,//白俄罗斯	BELARUS
                'NO'=>35,//挪威	NORWAY
                'NL'=>36,//荷兰	NETHERLAND
                'UA'=>37,//乌克兰	UKRAINE
                'CH'=>38,//瑞士	SWITZERLAND
                'MX'=>39,//墨西哥	MEXICO
                'PL'=>40,//波兰	POLAND
        );
        
        if(isset($codeArr[$country_en])){
        	return $codeArr[$country_en];
        }else{
        	return '';
        }
    }

}