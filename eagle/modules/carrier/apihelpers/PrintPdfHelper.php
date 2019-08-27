<?php
namespace eagle\modules\carrier\apihelpers;

use yii;
use common\helpers\Helper_Array;
use eagle\modules\util\helpers\ConfigHelper;
use eagle\modules\carrier\models\SysShippingService;
use eagle\models\SysShippingMethod;
use eagle\models\CarrierTemplateHighcopy;
use eagle\models\SysCountry;
use eagle\modules\order\models\OdOrderShipped;
use Qiniu\json_decode;
use eagle\modules\carrier\helpers\CarrierPartitionNumberHelper;
use common\helpers\Helper_Curl;
use eagle\models\CarrierTcpdfImg;
use eagle\modules\util\helpers\BarcodeHelper;
use common\helpers\simple_html_dom;
use eagle\modules\tracking\helpers\phpQuery;
use eagle\modules\amazon\apihelpers\AmazonApiHelper;
use eagle\modules\lazada\apihelpers\LazadaApiHelper;
use eagle\modules\util\helpers\TimeUtil;
use common\api\carrierAPI\LB_IEUBNewCarrierAPI;
use eagle\models\CrCarrierTemplate;
use eagle\models\carrier\CrTemplate;
use eagle\modules\util\helpers\RedisHelper;

class PrintPdfHelper{
	
	public static function getHighcopyFormatPath($tpye = 'all'){
		$highcopyArr = array();
		
		$highcopyArr['PostWishyouGh'] = array('type'=>'carrier_lable', 'templateName'=>'wish邮挂号','templateImg'=>'/images/customprint/labelimg/wish-mail.jpg');
		
		return $highcopyArr;
	}
	
	//获取发货人相关地址信息
	public static function getSenderInfo($order){
		//获取全局设置的相关打印信息
		$carrierConfig = ConfigHelper::getConfig("CarrierOpenHelper/CommonCarrierConfig", 'NO_CACHE');
		$carrierConfig = json_decode($carrierConfig,true);
		
		$shippingfrom_en = isset($carrierConfig['address']['shippingfrom_en'])?$carrierConfig['address']['shippingfrom_en']:
			array('country' => '','province' => '','city' => '','district' => '','street' => '','postcode' => '','company' => '',
						'contact' => '','phone' => '','mobile' => '','fax' => '','email' => '','returngoods'=>'',);
		
		//LGS使用指定发货人地址
		if($order->default_carrier_code == 'lb_LGS'){
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
						'returngoods' => $info['senderAddressInfo']['returnaddress'],
				);
			}
		}
		
		$shipingfrom_country_code = isset($shippingfrom_en['country'])?$shippingfrom_en['country']:"CN";
		$shipingfrom_country =  SysCountry::findOne(['country_code'=>$shipingfrom_country_code]);
		
		$shippingfrom_en['country_zh'] = empty($shipingfrom_country->country_zh) ? '' : $shipingfrom_country->country_zh;
		$shippingfrom_en['country_en'] = empty($shipingfrom_country->country_en) ? '' : $shipingfrom_country->country_en;
		
// 		return $shippingfrom_en;
// 		print_r($shippingfrom_en);
// 		exit;

		$senderInfo = array();
		$senderInfo['SENDER_NAME'] = $shippingfrom_en['contact'];
		$senderInfo['SENDER_ADDRESS'] = $shippingfrom_en['street'];
		$senderInfo['SENDER_AREA'] = $shippingfrom_en['district'];
		$senderInfo['SENDER_CITY'] = $shippingfrom_en['city'];
		$senderInfo['SENDER_PROVINCE'] = $shippingfrom_en['province'];
		$senderInfo['SENDER_COUNTRY_CN'] = $shippingfrom_en['country_zh'];
		$senderInfo['SENDER_COUNTRY_EN'] = $shippingfrom_en['country_en'];
		$senderInfo['SENDER_COUNTRY_EN_AB'] = $shipingfrom_country_code;
		$senderInfo['SENDER_ZIPCODE'] = $shippingfrom_en['postcode'];
		$senderInfo['SENDER_TELEPHONE'] = $shippingfrom_en['phone'];
		$senderInfo['SENDER_MOBILE'] = $shippingfrom_en['mobile'];
		$senderInfo['SENDER_EMAIL'] = $shippingfrom_en['email'];
		$senderInfo['SENDER_COMPANY_NAME'] = $shippingfrom_en['company'];
		$senderInfo['SENDER_RETURNGOODS'] = empty($shippingfrom_en['returngoods']) ? '' : $shippingfrom_en['returngoods'];
		
		$senderInfo['SENDER_PROVINCE_EN'] = $shippingfrom_en['province'];
		$senderInfo['SENDER_CITY_EN'] = $shippingfrom_en['city'];
		$senderInfo['SENDER_AREA_EN'] = $shippingfrom_en['district'];
		
		
		
		return $senderInfo;
	}
	
	//获取收件人相关地址信息
	public static function getReceiverInfo($order){
		$receiverInfo = array();
		
		$receiver_country =  SysCountry::findOne(['country_code'=>$order->consignee_country_code]);
		
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
		
		$receiverInfo['RECEIVER_NAME'] = $order->consignee;
		$receiverInfo['RECEIVER_ADDRESS'] = $order->consignee_address_line1.(strlen($order->consignee_address_line2)>0?'<br>'.$order->consignee_address_line2:'').(strlen($order->consignee_address_line3)>0?'<br>'.$order->consignee_address_line3:'');
		$receiverInfo['RECEIVER_ADDRESS_MODE2'] = $tmpAddressMode2;
		$receiverInfo['RECEIVER_AREA'] = $order->consignee_district;
		$receiverInfo['RECEIVER_CITY'] = $order->consignee_city;
		$receiverInfo['RECEIVER_PROVINCE'] = $order->consignee_province;
		$receiverInfo['RECEIVER_COUNTRY_CN'] = empty($receiver_country->country_zh) ? '' : $receiver_country->country_zh;
		$receiverInfo['RECEIVER_COUNTRY_EN'] = empty($receiver_country->country_en) ? '' : $receiver_country->country_en;
		$receiverInfo['RECEIVER_ZIPCODE'] = $order->consignee_postal_code;
		$receiverInfo['RECEIVER_TELEPHONE'] = empty($order->consignee_phone) ? $order->consignee_mobile : $order->consignee_phone;
		$receiverInfo['RECEIVER_MOBILE'] = empty($order->consignee_mobile) ? $order->consignee_phone : $order->consignee_mobile;
		$receiverInfo['RECEIVER_EMAIL'] = $order->consignee_email;
		$receiverInfo['RECEIVER_COMPANY'] = $order->consignee_company;
		$receiverInfo['RECEIVER_COUNTRY_EN_AB'] = $order->consignee_country_code;
		$receiverInfo['RECEIVER_DETAILED_ADDRESS'] = $tmpAddressMode1;
		
		return $receiverInfo;
	}
	
	//获取订单相关的跟踪信息
	public static function getTrackingInfo($order){
		$trackingInfo = array('tracking_number'=>'','PackageId'=>'','return_no'=>array());
		
		if($order->default_carrier_code == 'lb_LGS'){
			$shippingInfo= OdOrderShipped::find()->where(['order_id'=>$order->order_id])->orderBy('addtype desc,id desc')->one();
		}else{
			$shippingInfo= OdOrderShipped::find()->where(['order_id'=>$order->order_id])->andWhere(['customer_number'=>$order->customer_number])->orderBy('id desc')->one();
		}
		
		if(!empty($shippingInfo)){
			$trackingInfo['tracking_number'] = $shippingInfo->tracking_number;
			
			if(!empty($shippingInfo->return_no['PackageId']))
				$trackingInfo['PackageId'] = $shippingInfo->return_no['PackageId'];
			
			$trackingInfo['return_no'] = $shippingInfo->return_no;
			
			//LAZADA包裹号
			$trackingInfo['LAZADA_PACKAGE_CODE'] = $trackingInfo['PackageId'];
			$trackingInfo['LAZADA_PACKAGE_CODE_BARCODE'] = $trackingInfo['PackageId'];
			
			//货运单号条码
			$trackingInfo['ORDER_EXPRESS_CODE'] = $shippingInfo->tracking_number;
			$trackingInfo['ORDER_EXPRESS_CODE_BARCODE'] = $shippingInfo->tracking_number;
		}
		
		return $trackingInfo;
	}
	
	/**
	 * 获取订单详情列表信息
	 * 
	 * @param $order				订单对象
	 * @param $is_generate_img		是否将订单图片进行转换,当使用面单需要产品图片才需要做，不然不要做生成
	 * @return array()
	 */
	public static function getItemListDetailInfo($order, $shipping_service = null, $is_generate_img = false, $is_merge_same_sku = false, $puid = false){
		if($puid == false){
			//获取puid
			$user=\Yii::$app->user->identity;
			$puid = $user->getParentUid();
		}
		
		$itemListDetailInfo = array();
		
		//定义订单list信息
		$itemListDetailInfo['lists'] = array();
		//定义订单Items信息
		$itemListDetailInfo['products'] = array();
		
		//报关信息
		$declarationInfo = CarrierApiHelper::getDeclarationInfo($order, null, true, true, $shipping_service);
		$products = $declarationInfo['products'];
		
		//记录订单总重量和总金额
		$amountWeight = 0;
		$amountPrice = 0;
		$amountQuantity = 0;
		
		//记录序号
		$productAutoID = 0;
		foreach ($products as $product){
			$itemListDetailInfo['products'][$productAutoID]['SKUAUTOID'] = $productAutoID + 1;
			$itemListDetailInfo['products'][$productAutoID]['PICTURE'] = $product['photo_primary'];
			$itemListDetailInfo['products'][$productAutoID]['SKU'] = $product['sku'];
			$itemListDetailInfo['products'][$productAutoID]['ITEM_ID'] = $product['itemid'];
			$itemListDetailInfo['products'][$productAutoID]['NAME_CN'] = empty($product['prod_name_ch']) ? $product['declaration_ch'] : $product['prod_name_ch'];
			$itemListDetailInfo['products'][$productAutoID]['DECLARE_NAME_CN'] = $product['declaration_ch'];
			$itemListDetailInfo['products'][$productAutoID]['DECLARE_NAME_EN'] = $product['declaration_en'];
			$itemListDetailInfo['products'][$productAutoID]['PRODUCT_TITLE'] = $product['name'];
			$itemListDetailInfo['products'][$productAutoID]['WAREHOUSE'] = $product['warehouse'];
			$itemListDetailInfo['products'][$productAutoID]['GRID_CODE'] = $product['location_grid'];
			$itemListDetailInfo['products'][$productAutoID]['QUANTITY'] = $product['quantity'];
			$itemListDetailInfo['products'][$productAutoID]['WEIGHT'] = $product['total_weight'] / 1000;
			$itemListDetailInfo['products'][$productAutoID]['PROPERTY'] = $product['product_attributes'];
			$itemListDetailInfo['products'][$productAutoID]['PRICE'] = $product['declaration_value'];
			$itemListDetailInfo['products'][$productAutoID]['AMOUNT_PRICE'] = $product['total_price'];
			$itemListDetailInfo['products'][$productAutoID]['PROD_WEIGHT'] = $product['prod_weight'] / 1000;
			$itemListDetailInfo['products'][$productAutoID]['PRODUCT_BUYER_NAME'] = $product['purchase_by'];
			$itemListDetailInfo['products'][$productAutoID]['PRODUCT_NAME_PICKING'] = $product['prod_name_ch'];
			
			
			$amountWeight += $product['total_weight'] / 1000;
			$amountPrice += $product['total_price'];
			$amountQuantity += $product['quantity'];
			
			$productAutoID++;
		}
		
		//是否SKU相同时合并为一个，当SKU为空时不作合并
		if($is_merge_same_sku == true){
			$tmp_merge_products = $itemListDetailInfo['products'];
			unset($itemListDetailInfo['products']);
			$itemListDetailInfo['products'] = array();
			
// 			$productAutoID = 0;
			foreach ($tmp_merge_products as $tmp_merge_products_one){
				if($tmp_merge_products_one['SKU'] != ''){
					if(count($itemListDetailInfo['products']) > 0){
						$tmp_is_add = true;
						
						foreach ($itemListDetailInfo['products'] as $tmp_products_for_key => $tmp_products_for_val){
							if(($tmp_products_for_val['SKU'] != '') && ($tmp_merge_products_one['SKU'] == $tmp_products_for_val['SKU'])){
								
								$itemListDetailInfo['products'][$tmp_products_for_key]['QUANTITY'] += $tmp_merge_products_one['QUANTITY'];
								$itemListDetailInfo['products'][$tmp_products_for_key]['WEIGHT'] += $tmp_merge_products_one['WEIGHT'];
								$itemListDetailInfo['products'][$tmp_products_for_key]['AMOUNT_PRICE'] += $tmp_merge_products_one['AMOUNT_PRICE'];
								
								$tmp_is_add = false;
							}
						}
						
						if($tmp_is_add == true){
							$itemListDetailInfo['products'][] = $tmp_merge_products_one;
						}
					}else{
						$itemListDetailInfo['products'][] = $tmp_merge_products_one;
					}
				}else{
					$itemListDetailInfo['products'][] = $tmp_merge_products_one;
				}
			}
			
			foreach ($itemListDetailInfo['products'] as $tmp_products_for_key => $tmp_products_for_val){
				$itemListDetailInfo['products'][$tmp_products_for_key]['SKUAUTOID'] = $tmp_products_for_key + 1;
			}
		}
		
		//LGS店铺
		$tmpLgsStoreName = '';
		
		try {
			$tmpLgsStoreNameArr = \eagle\modules\lazada\apihelpers\LazadaApiHelper::getStoreName($order->selleruserid,$order->order_source_site_id);
			$tmpLgsStoreName = $tmpLgsStoreNameArr[0]==true ? $tmpLgsStoreNameArr[1] : '';
		}catch (\Exception $ex){
		}
		
		//重量
		$itemListDetailInfo['lists']['TOTAL_WEIGHT'] = $amountWeight;
		$itemListDetailInfo['lists']['ORDER_TOTAL_WEIGHT'] = $amountWeight;
		//金额
		$itemListDetailInfo['lists']['TOTAL_AMOUNT_PRICE'] = $amountPrice;
		//打印时间
		$itemListDetailInfo['lists']['ORDER_PRINT_TIME'] = date('Y-m-d H:i:s',time());
		//打印时间2
		$itemListDetailInfo['lists']['ORDER_PRINT_TIME2'] = date('m/d H:i:s',time());
		//打印时间3
		$itemListDetailInfo['lists']['PRINT_TIME3'] = date('Y-m-d',time());
		$itemListDetailInfo['lists']['ORDER_PRINT_TIME3'] = date('Y-m-d',time());
		//打印时间年
		$itemListDetailInfo['lists']['ORDER_PRINT_TIME_YEAR'] = date('Y',time());
		//打印时间月
		$itemListDetailInfo['lists']['ORDER_PRINT_TIME_MONTH'] = date('m',time());
		//打印时间日
		$itemListDetailInfo['lists']['ORDER_PRINT_TIME_DAY'] = date('d',time());
		//商品种类统计
		$itemListDetailInfo['lists']['ITEM_LIST_TOTAL_KIND'] = count($itemListDetailInfo['products']);
		//商品数量统计
		$itemListDetailInfo['lists']['ITEM_LIST_TOTAL_QUANTITY'] = $amountQuantity;
		//EUB专用邮编条码
		$itemListDetailInfo['lists']['EUB_RECEIVER_ZIPCODE_BARCODE'] = $order->consignee_postal_code;
		//店铺
		$itemListDetailInfo['lists']['ORDER_SHOP_NAME'] = $order->selleruserid;
		if($order->order_source == 'amazon'){
			$itemListDetailInfo['lists']['ORDER_SHOP_NAME'] = AmazonApiHelper::getAmzStoreName($order->selleruserid);
		}else if($order->order_source == 'lazada'){
			$itemListDetailInfo['lists']['ORDER_SHOP_NAME'] = $tmpLgsStoreName;
		}
		$itemListDetailInfo['lists']['SHOP_LGS_NAME'] = $tmpLgsStoreName;
		//买家ID
		$itemListDetailInfo['lists']['ORDER_BUYER_ID'] = $order->source_buyer_user_id;
		//交易号
		$itemListDetailInfo['lists']['ORDER_TRADE_NUMBER'] = ($order->order_source=='ebay') ? $order->order_source_srn : $order->order_source_order_id;
		//平台来源订单号
		$itemListDetailInfo['lists']['ORDER_SOURCE_CODE'] = $order->order_source_order_id;
		//小老板订单编号
		$itemListDetailInfo['lists']['XLB_ORDER_CODE'] = (int)$order->order_id;
		//小老板订单编号条码
		$itemListDetailInfo['lists']['XLB_ORDER_CODE_BARCODE'] = (int)$order->order_id;
		//平台来源订单号条码
		$itemListDetailInfo['lists']['ORDER_SOURCE_CODE_BARCODE'] = $order->order_source_order_id;
		//EUB分拣码
		$itemListDetailInfo['lists']['PARTITION_YARDS_EUB'] = self::getEubSortingYardsByCountry($order->consignee_country_code, $order->consignee_postal_code);
		//国际挂号小包分拣区
		$itemListDetailInfo['lists']['INTERNATIONAL_REGISTERED_PARCEL_SORTING_AREA'] = PrintApiHelper::getNumCode($order->consignee_country_code);
		//国际挂号小包分区
		$itemListDetailInfo['lists']['INTERNATIONAL_REGISTERED_PARCEL_PARTITION'] = self::getWishGhAreaCode($order->consignee_country_code);
		//国际平常小包分区
		$itemListDetailInfo['lists']['INTERNATIONAL_COMMON_PACKET_PARTITION'] = self::getWishPyAreaCode($order->consignee_country_code);
		//订单金额
		$itemListDetailInfo['lists']['ORDER_TOTAL_FEE'] = $order->grand_total.' '.$order->currency;
		//币种
		$itemListDetailInfo['lists']['ORDER_CURRENCY'] = $order->currency;
		//付款方式
		$tmp_payment_type = '';
		if($order->order_source == "lazada"){
			if(!empty($order->payment_type)){
				$tmp_payment_type = $order->payment_type;
			}else{
				$order_payment_array = LazadaApiHelper::getPaymentMethod($order);
				if($order_payment_array[0]){
					$tmp_payment_type = $order_payment_array[1];
				}
			}
		}else{
			$tmp_payment_type = $order->payment_type;//付款方式
		}
		$itemListDetailInfo['lists']['RECEIVER_PAYMENT'] = $tmp_payment_type;
		//订单备注
		$itemListDetailInfo['lists']['ORDER_REMARK'] = $order->desc;
		//平台物流方式如燕文北京平邮
		$itemListDetailInfo['lists']['ORDER_EXPRESS_WAY'] = empty($shipping_service->shipping_method_name) ? '' : $shipping_service->shipping_method_name;
		//货运方式物流商
		$itemListDetailInfo['lists']['ORDER_EXPRESS_NAME'] = empty($shipping_service->carrier_name) ? '' : $shipping_service->carrier_name;
		//是否含电池
		$itemListDetailInfo['lists']['ORDER_HAS_BATTERY'] = $declarationInfo['has_battery'] ? '有电池' : '';
		
		
		//当需要使用图片时才做转换不然浪费时间
		if($is_generate_img == true){
			//重新读取图片来源,因为外网图片会比较慢所以不是littleboss图片库的都要先下载到本地才行.
			unset($product);
			foreach ($itemListDetailInfo['products'] as $tmp_products_key => $product){
				$tmp_orig_url = $itemListDetailInfo['products'][$tmp_products_key]['PICTURE'];
				
				if(empty($tmp_orig_url)){
					continue;
				}
				
				//CD和PM本来就有一个七牛图片库
				if(in_array($order->order_source, array('cdiscount','priceminister'))){
					$itemListDetailInfo['products'][$tmp_products_key]['PICTURE'] = \eagle\modules\util\helpers\ImageCacherHelper::getImageCacheUrl($tmp_orig_url, $puid, 1);
					
					$tmp_orig_url = $itemListDetailInfo['products'][$tmp_products_key]['PICTURE'];
				}
				
				//假如已经是小老板图片库的图片直接使用不再保存到本地
				if(stripos($tmp_orig_url, 'image.littleboss.com')!==false){
					continue;
				}
				
				//lazada图片返回双//
				if(substr($tmp_orig_url, 0, 2 ) == '//'){
					$tmp_orig_url = 'http:'.$tmp_orig_url;
				}
				
				//获取图片本来有没有做过缓存
				$tmpCarrierTcpdfImg = CarrierTcpdfImg::find()->where(['puid'=>$puid,'photo_primary'=>$tmp_orig_url])->one();
				
				if(!empty($tmpCarrierTcpdfImg['photo_file_path'])){
					$itemListDetailInfo['products'][$tmp_products_key]['PICTURE'] = $tmpCarrierTcpdfImg['photo_file_path'];
					
					$itemListDetailInfo['products'][$tmp_products_key]['PICTURE'] = str_replace("%","%25", $itemListDetailInfo['products'][$tmp_products_key]['PICTURE']);
					continue;
				}else{
					if($tmpCarrierTcpdfImg === null){
						$tmpCarrierTcpdfImg = new CarrierTcpdfImg();
						$tmpCarrierTcpdfImg->photo_primary = $tmp_orig_url;
						$tmpCarrierTcpdfImg->puid = $puid;
						$tmpCarrierTcpdfImg->create_time = time();
						$tmpCarrierTcpdfImg->update_time = 0;
						
						$tmpCarrierTcpdfImg->save(false);
					}
					
					//假如访问时间过短直接跳过暂时不再获取新的图片
					if($tmpCarrierTcpdfImg->update_time > 0){
						if(time() - $tmpCarrierTcpdfImg->update_time < 300){
							$itemListDetailInfo['products'][$tmp_products_key]['PICTURE'] = '';
							
							continue;
						}
					}
					
					//获取文件名
					preg_match('/\/([^\/]+\.[a-z]+)[^\/]*$/',$tmp_orig_url,$match);
					$tmp_img_file_name = $tmpCarrierTcpdfImg->id.(empty($match[1]) ? '' : $match[1]);
					
					//curl 控制时间为5秒
					$tmpHelp = new Helper_Curl();
					$tmpHelp::$timeout = 5;
					$response = $tmpHelp::get($tmp_orig_url);
					
					if(($response !== false) && ($tmpHelp::$last_post_info['http_code'] == 200)){
						$imgPdfPath = self::saveImg($response, $puid, $tmp_img_file_name);
						
						if($imgPdfPath['error'] == 0){
							$tmpCarrierTcpdfImg->photo_file_path = str_replace('\\','/',$imgPdfPath['filePath']);
							
							$tmpCarrierTcpdfImg->update_time = time();
							$tmpCarrierTcpdfImg->save(false);
							
							$itemListDetailInfo['products'][$tmp_products_key]['PICTURE'] = $tmpCarrierTcpdfImg->photo_file_path;
						}else{
							$tmpCarrierTcpdfImg->update_time = time();
							$tmpCarrierTcpdfImg->save(false);
						}
					}else{
						$itemListDetailInfo['products'][$tmp_products_key]['PICTURE'] = '';
						
						$tmpCarrierTcpdfImg->update_time = time();
						$tmpCarrierTcpdfImg->save(false);
					}
					
				}
				
				if(isset($itemListDetailInfo['products'][$tmp_products_key]['PICTURE'])){
					$itemListDetailInfo['products'][$tmp_products_key]['PICTURE'] = str_replace("%","%25", $itemListDetailInfo['products'][$tmp_products_key]['PICTURE']);
				}
			}
		}
		
		return $itemListDetailInfo;
	}
	
	/**
	 * 将 Img 文件保存到本地
	 *
	 * @param $data				img数据流
	 * @param $puid				puid
	 * @param $img_file_name	图片文件名
	 * @param $type				是否图片流
	 * @return
	 * +-------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		hqw		2016/03/16				初始化
	 * +-------------------------------------------------------------------------------------------
	 */
	public static function saveImg($data, $puid, $img_file_name, $type = false) {
		//如果是正确信息, 保存pdf, 并返回包括URL的相关参数
		$filename = $puid.'_'.$img_file_name;
	
		try{
			//文件保存物理路径
			$file = self::createTcpdfImgDir().DIRECTORY_SEPARATOR.$filename;
				
			if($type == false){
				if(file_put_contents($file,$data)){
					$tmpFile = str_replace(self::getTcpdfImgPathString(), "", $file);
					return ['error'=>0, 'msg'=>'', 'filePath'=>$tmpFile];
				}
			}else{
				try {
					imagepng($data, $file);
					$tmpFile = str_replace(self::getTcpdfImgPathString(), "", $file);
					return ['error'=>0, 'msg'=>'', 'filePath'=>$tmpFile];
				}catch (\Exception $ex){
					return ['error'=>1, 'msg'=>'保存文件失败', 'filePath'=>''];
				}
			}
			
			return ['error'=>1, 'msg'=>'保存文件失败', 'filePath'=>''];
		}catch (\Exception $ex){
			return ['error'=>1, 'msg'=>print_r($ex), 'filePath'=>''];
		}
	}
	
	/**
	 * 获取 img 保存路径
	 * @param	$is_create_dir 是否创建日期目录
	 * @return string
	 */
	public static function createTcpdfImgDir($is_create_dir = true){
		if($is_create_dir){
			$basepath = self::getTcpdfImgPathString().DIRECTORY_SEPARATOR.'attachment'.DIRECTORY_SEPARATOR.'tmp_img_tcpdf';
			//根据年月日生成目录，用于以后方便管理删除文件
			$dataDir = date("Ymd");
				
			if(!file_exists($basepath.DIRECTORY_SEPARATOR.$dataDir)){
				mkdir($basepath.DIRECTORY_SEPARATOR.$dataDir);
				chmod($basepath.DIRECTORY_SEPARATOR.$dataDir,0777);
			}
			return $basepath.DIRECTORY_SEPARATOR.$dataDir;
		}else{
			$basepath = self::getTcpdfImgPathString();
			return $basepath;
		}
	}
	
	/**
	 * 获取TcPdf保存的路径
	 * @return string
	 */
	public static function getTcpdfImgPathString(){
		return dirname(dirname(dirname(\Yii::getAlias('@yii')))).DIRECTORY_SEPARATOR.'eagle'.DIRECTORY_SEPARATOR.'web';
	}
	
	//获取通用配货单
	public static function getItemsLablePub($tmpX, $tmpY , &$pdf, $order, $shippingService, $format, $lableCount, $otherParams){
// 		//报关信息
// 		$declarationInfo =CarrierApiHelper::getDeclarationInfo($order, $shippingService,true,true);
		
		//获取订单详情列表信息
		$itemListDetailInfo = self::getItemListDetailInfo($order, $shippingService, true, true);
		
		$width = 0;
		if(($format == 'A4') && ($lableCount == 3)){
			$width = 110;
		}
		
		$style = array(
				'position' => '',
				'align' => 'C',
				'stretch' => false,
				'fitwidth' => true,
				'cellfitalign' => '',
				'border' => true,
				'hpadding' => 'auto',
				'vpadding' => 'auto',
				'fgcolor' => array(0,0,0),
				'bgcolor' => false, //array(255,255,255),
				'text' => true,
				'font' => 'helvetica',
				'fontsize' => 8,
				'stretchtext' => 4
		);
		
		//订单详细Items信息
		$products = $itemListDetailInfo['products'];
		
		//设置字体7号
		$pdf->SetFont($otherParams['pdfFont'], '', 7);
		
		//left边线条
		$pdf->Line(2+$tmpX, 2+$tmpY, 2+$tmpX, 25.5+$tmpY, $style=array('width' => 0.5, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
		//top边线条
		$pdf->Line(2+$tmpX, 2+$tmpY, 98+$tmpX+$width, 2+$tmpY, $style=array('width' => 0.5, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
		//right边线条
		$pdf->Line(98+$tmpX+$width, 2+$tmpY, 98+$tmpX+$width, 25.5+$tmpY, $style=array('width' => 0.5, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
		//bottom边线条
		$pdf->Line(2+$tmpX, 20.5+$tmpY, 98+$tmpX+$width, 20.5+$tmpY, $style=array('width' => 0.5, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
		
		//输出字:订单号
		$pdf->SetFont($otherParams['pdfFont'], '', 11);
		$pdf->writeHTMLCell(0, 0, 2+$tmpX, 8+$tmpY, '订单号:', 0, 1, 0, true, '', true);
		
		//条码的样式
		$style = array(
				'position' => '',
				'align' => 'C',
				'stretch' => false,
				'fitwidth' => true,
				'cellfitalign' => '',
				'border' => false,
				'hpadding' => 'auto',
				'vpadding' => 'auto',
				'fgcolor' => array(0,0,0),
				'bgcolor' => false, //array(255,255,255),
				'text' => true,
				'font' => 'helvetica',
				'fontsize' => 8,
				'stretchtext' => 0
		);
		
		//输出小老板订单号条码
		$pdf->write1DBarcode((int)$order->order_id, 'C128A', 13+$tmpX, 3+$tmpY, '', 18, 0.36, $style, 'N');
		
		//输出订单备注
		$pdf->SetFont($otherParams['pdfFont'], '', 11);
		$pdf->writeHTMLCell(47+$width, 0, 52+$tmpX, 2+$tmpY, '备注：'.$order->desc, 0, 1, 0, true, '', true);
		
		//输出跟踪号
		$pdf->SetFont($otherParams['pdfFont'], '', 8);
		if(!empty($order['customer_number'])){
			$pdf->writeHTMLCell(47+$width, 0, 52+$tmpX, 16+$tmpY, '跟踪号:'.\eagle\modules\carrier\helpers\CarrierOpenHelper::getOrderShippedTrackingNumber($order['order_id'],$order['customer_number'],$order['default_shipping_method_code']), 0, 1, 0, true, '', true);
		}
		
		//订单号条码下边第二条线条
		$pdf->Line(2+$tmpX, 25.5+$tmpY, 98+$tmpX+$width, 25.5+$tmpY, $style=array('width' => 0.5, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
		
		//输出平台订单号
		$pdf->SetFont($otherParams['pdfFont'], '', 9);
		$pdf->writeHTMLCell(0, 0, 2+$tmpX, 21+$tmpY, '平台订单号：'.$order->order_source_order_id, 0, 1, 0, true, '', true);
		
		//记录输出到第几个Item
		$tmpInt = 0;
		
		//记录假如多个Item时下一页需要重新计数
		$dynamicHight = 0;
		
		foreach ($products as $productKey => $product){
			//假如是热敏纸时每个热敏纸的Item个数有限制
			if(($format != 'A4') && ($productKey != 0)){
				if((($productKey <= 5) && (($productKey % 5) == 0)) || (($productKey > 6) && (($productKey % 6) == 0))){
					$pdf->AddPage();
					$tmpInt=0;
					
					$dynamicHight = 24.5 + (24.5 * $tmpInt);
				}
			}
			
			//假如是A4纸并且是3种面单都打印需要加Item个数限制分页
			if(($format == 'A4') && ($productKey != 0)){
				if($lableCount == 3){
					if((($productKey <= 12) && (($productKey % 12) == 0)) || (($productKey > 11) && ((($productKey-11) % 22) == 0))){
						$pdf->AddPage();
						$tmpInt=0;
						
						$dynamicHight = 120 + (120 * $tmpInt);
					}
				}else if($lableCount == 2){
					if((($productKey <= 18) && (($productKey % 18) == 0)) ){
						$pdf->AddPage();
						$tmpInt=0;
					
						$dynamicHight = 25 + (25 * $tmpInt);
					}
				}
			}
			
			//动态创建左边线
			$pdf->Line(2+$tmpX, 25.5+$tmpY+($tmpInt*13.5)-$dynamicHight, 2+$tmpX, 39+$tmpY+($tmpInt*13.5)-$dynamicHight, $style=array('width' => 0.5, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
 			//动态创建右边线
			$pdf->Line(98+$tmpX+$width, 25.5+$tmpY+($tmpInt*13.5)-$dynamicHight, 98+$tmpX+$width, 39+$tmpY+($tmpInt*13.5)-$dynamicHight, $style=array('width' => 0.5, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
			//动态创建下边线
			$pdf->Line(2+$tmpX, 39+$tmpY+($tmpInt*13.5)-$dynamicHight, 98+$tmpX+$width, 39+$tmpY+($tmpInt*13.5)-$dynamicHight, $style=array('width' => 0.5, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
			
			//假如没有图片的话默认显示No Photo
			if(empty($product['PICTURE'])){
				$product['PICTURE'] = '/images/batchImagesUploader/no-img.png';
			}
			
			//lazada图片返回双//
			if(substr($product['PICTURE'], 0, 2 ) == '//'){
				$product['PICTURE'] = 'http:'.$product['PICTURE'];
			}
			
// 			$product['PICTURE'] = '/attachment/tmp_img_tcpdf/20161025/2.jpg';
			//这里需要先判断图片是否是图片流文件不然会生成图片导致程序崩溃
			if(substr($product['PICTURE'], 0, 1 ) == '/'){
// 				$product['PICTURE'] = Yii::$app->request->hostinfo.$product['PICTURE'];
				
				$product['PICTURE'] = dirname(dirname(dirname(__DIR__))).'/web'.$product['PICTURE'];
			}
			
			$tmpGetimagesize = false;
			try{
				$tmpGetimagesize = getimagesize($product['PICTURE']);
			}catch (\Exception $ex){
			}
			
			if($tmpGetimagesize == false){
				$product['PICTURE'] = '/images/batchImagesUploader/no-img.png';
			}
			
			//显示items图片
			if(!empty($product['PICTURE'])){
				$pdf->writeHTMLCell(0, 0, 2+$tmpX, 26+$tmpY+($tmpInt*13.5)-$dynamicHight, '<img src="'.$product['PICTURE'].'"  width="35" height="35" >', 0, 0, 0, true, '', true);
			}
			
			$tmpAttributeStr = '';
			
			//速卖通的属性值
			if($order->order_source == 'aliexpress'){
				$tmpProdctAttrbutes = explode(' + ' ,$product['PROPERTY'] );
				if (!empty($tmpProdctAttrbutes)){
					foreach($tmpProdctAttrbutes as $_tmpAttr){
						$tmpAttributeStr .= $_tmpAttr;
					}
				}
			}else if($order->order_source == 'ebay'){
// 				$tmpProdctAttrbutes = json_decode($product['PROPERTY'], true);

// 				$tmpProdctAttrbutes = $product['PROPERTY'];
// 				if (is_array($tmpProdctAttrbutes)){
// 					foreach($tmpProdctAttrbutes as $_tmpAttr){
// 						if (is_array($_tmpAttr)){
// 							foreach($_tmpAttr as $_tmpAkey=>$_tmpAValue){
// 								$tmpAttributeStr .= $_tmpAkey.":".$_tmpAValue;
// 							}
// 						}else{
// 							$tmpAttributeStr = $_tmpAttr;
// 						}
// 					}
// 				}
			}
			
			//输出配货信息
			$pdf->SetFont($otherParams['pdfFont'], '', 8);
			$pdf->writeHTMLCell(60+$width, 0, 16+$tmpX, 26+$tmpY+($tmpInt*13.5)-$dynamicHight, $product['NAME_CN'].' '.$tmpAttributeStr.'<br>sku:'.$product['SKU'], 0, 0, 0, true, '', true);
			
			//输出货架位
			$pdf->SetFont($otherParams['pdfFont'], '', 8);
			$pdf->writeHTMLCell(25, 0, 74+$tmpX+$width, 26+$tmpY+($tmpInt*13.5)-$dynamicHight, '货架位：'.$product['GRID_CODE'], 0, 0, 0, true, '', true);
			
			//输出数量
			$pdf->SetFont($otherParams['pdfFont'], '', 12);
			$pdf->writeHTMLCell(30, 0, 74+$tmpX+$width, 32+$tmpY+($tmpInt*13.5)-$dynamicHight, '数量：'.$product['QUANTITY'], 0, 0, 0, true, '', true);
			
			$tmpInt++;
		}
		
	}
	
	/**
	 * 设置默认打印纸张的格式和返回对应的间隔
	 * 
	 * @param string $format	纸张格式默认A4, 10*10可以定义数组array(100,100)
	 */
	public static function getHighcopyFormatPDF($orderlist, $is_generate_pdf = 0){
		$result = array('error'=>false, 'msg'=>'');
		
		//额外的参数, 例如可以记录加打的内容
		$otherParams = array();
		
		$otherParams['pdfFont'] = 'msyh';
		
		//判断是否存在订单
		$orderRecordNum = count($orderlist);
		if($orderRecordNum == 0){
			$result['error'] = true;
			$result['msg'] = '请传入订单';
			
			return $result;
		}
		
		//设置打印间隔属性
		$pdfAttribute = array('x0'=>0, 'y0'=>0, 'x1'=>105, 'y1'=>0, 'x2'=>0, 'y2'=>99, 'x3'=>105, 'y3'=>99, 'x4'=>0, 'y4'=>198, 'x5'=>105 ,'y5'=>198);
		
		//定义打印到的位置
		$int1 = 0;
		
		//记录第几张订单
		$intOrderCount = 1;
		
		//获取订单对应的运输服务设置
		$shippingServece_obj = SysShippingService::findOne(['id'=>$orderlist[0]['default_shipping_method_code']]);
		if(empty($shippingServece_obj)){
			$result['error'] = true;
			$result['msg'] = "can't find this shippingservice";
				
			return $result;
		}
		
// 		//假如没有使用小老板高仿标签(新)，则直接提示错误
		if($shippingServece_obj->print_type != 3){
			$result['error'] = true;
			$result['msg'] = "请先开启小老板高仿标签(新)";
			
			return $result;
		}
		
		//获取物流账号别名
		$carrierAccount = \eagle\modules\carrier\models\SysCarrierAccount::findOne(['id'=>$shippingServece_obj['carrier_account_id']]);
		if(!empty($carrierAccount)){
			$otherParams['carrier_name'] = $carrierAccount['carrier_name'];
		}
		
		//需要打印的类名
		$print_calss = array();
		//需要打印的格式/方法
		$print_params = array();
		if(!empty($shippingServece_obj->print_params['label_littlebossOptionsArrNew'])){
			foreach ($shippingServece_obj->print_params['label_littlebossOptionsArrNew'] as $print_paramKey => $print_paramVal){
				if(in_array($print_paramKey, array('carrier_lable','declare_lable','items_lable'))){
					if(!empty($print_paramVal)){
						$print_params[$print_paramKey] = $print_paramVal;
					}
				}
			}
			
			$tmp_carrier_temp_highArr = CarrierTemplateHighcopy::find()->where(['id'=>$print_params])->asArray()->all();
			
			if(empty($tmp_carrier_temp_highArr)){
				$result['error'] = true;
				$result['msg'] = "请确定面单是否已经过期.";
				return $result;
			}
			
			foreach ($tmp_carrier_temp_highArr as $tmp_carrier_temp_high){
				$print_calss[$tmp_carrier_temp_high['type']] = empty($tmp_carrier_temp_high['helper_class']) ? '\eagle\modules\carrier\apihelpers\PrintPdfHelper' : $tmp_carrier_temp_high['helper_class'];
				
				$print_params[$tmp_carrier_temp_high['type']] = $tmp_carrier_temp_high['helper_function'];
			}
		}
		
		//判断打印纸张类型
		if(empty($shippingServece_obj->print_params['label_littlebossOptionsArrNew']['printFormat'])){
			$format = 'A4';
		}else{
			$format = array(100, 100);
		}
		
		//获取需要增加打印的内容
		if(!empty($shippingServece_obj->print_params['label_littlebossOptionsArrNew']['printAddVal'])){
			$tmpPrintAdd = json_decode($shippingServece_obj->print_params['label_littlebossOptionsArrNew']['printAddVal'], true);
			
			if(is_array($tmpPrintAdd)){
				foreach ($tmpPrintAdd as $tmpPrintAddKey => $tmpPrintAddVal){
					$otherParams['printAddVal'][] = $tmpPrintAddKey;
				}
			}
		}
		
// 		$format = array(100, 100);
// 		$format = 'A4';
		
// 		$print_params['carrier_lable'] = 'getPostWishyouGh';
// 		$print_params['declare_lable'] = 'getTestBg';
// 		$print_params['items_lable'] = 'getItemsLablePub';
		
		if(empty($print_params)){
			$result['error'] = true;
			$result['msg'] = "请先设置需要打印的面单类型";
			return $result;
		}
		
		//记录当时是否需要打印报关单，因为wish邮挂号的法国需要打印报关单，所以需要做特殊处理
		$tmpdeclare_lable_name = '';
		$tmpdeclare_lable_calss = '';
		if(array_key_exists('declare_lable', $print_params)){
			$tmpdeclare_lable_name = $print_params['declare_lable'];
			$tmpdeclare_lable_calss = $print_calss['declare_lable'];
		}
		
		//A4纸 打印配货单需要记录每张订单的打印位置
		if(($format == 'A4') && (array_key_exists('items_lable', $print_params))){
			//定义每个订单的间隔高度
			$tmp_items_heigt = 0;
			
			if(count($print_params) == 3){
				$tmp_items_heigt = 131;
			}else{
				$tmp_items_heigt = 95;
			}
			
			//确定打印Y坐标位置
			$pdfAttributeY = array();
			
			//记录上一次是否使用动态创建面单
			$tmpPreviousDynamic = false; 
			
			foreach ($orderlist as $orderKey => $order){
				$pdfAttributeY[$orderKey] = array();
				
				if(count($print_params) == 3){
					//当第一张订单时默认新建一页
					if($orderKey == 0){
						$pdfAttributeY[$orderKey]['is_new'] = 1;
						$pdfAttributeY[$orderKey]['now_location'] = 0;
							
						$pdfAttributeY[$orderKey]['next_location'] = ((count($order->items) - 1) * 13.5) + $tmp_items_heigt;
					}else{
						$pdfAttributeY[$orderKey]['now_location'] = $pdfAttributeY[$orderKey-1]['next_location'] + 3;
						$pdfAttributeY[$orderKey]['next_location'] = ((count($order->items) - 1) * 13.5 + $tmp_items_heigt) + $pdfAttributeY[$orderKey]['now_location'];
							
						//A4纸默认297高度 假如减去下一个位置不够显示时新建另一页
						if(297 - $pdfAttributeY[$orderKey]['next_location'] > 0){
							$pdfAttributeY[$orderKey]['is_new'] = 0;
						}else{
							$pdfAttributeY[$orderKey]['is_new'] = 1;
							$pdfAttributeY[$orderKey]['now_location'] = 0;
					
							$pdfAttributeY[$orderKey]['next_location'] = ((count($order->items) - 1) * 13.5) + $tmp_items_heigt;
						}
					}
				}else{
					$order_height = (((count($order->items) - 1) * 13.5) + 35) < 95 ? 95 : (((count($order->items) - 1) * 13.5) + 35);
					
					//wish邮法国需要动态生成报关单
					$tmpIsDynamic = self::dynamicSetPrintFormat($tmpdeclare_lable_name, $tmpdeclare_lable_calss, $order, $print_params, $print_calss, false);
					
					
					//当第一张订单时默认新建一页
					if(($orderKey == 0) || ($tmpIsDynamic) || ($tmpPreviousDynamic)){
						$pdfAttributeY[$orderKey]['is_new'] = 1;
						$pdfAttributeY[$orderKey]['now_location'] = 0;
							
// 						if($tmpIsDynamic == true){
// 							$pdfAttributeY[$orderKey]['next_location'] = ((count($order->items) - 1) * 13.5) + $tmp_items_heigt;
// 						}else{
							$pdfAttributeY[$orderKey]['next_location'] = $order_height;
// 						}
					}else{
						$pdfAttributeY[$orderKey]['now_location'] = $pdfAttributeY[$orderKey-1]['next_location'] + 3;
						$pdfAttributeY[$orderKey]['next_location'] = $order_height + $pdfAttributeY[$orderKey]['now_location'];
						
						//A4纸默认297高度 假如减去下一个位置不够显示时新建另一页
						if(297 - $pdfAttributeY[$orderKey]['next_location'] > 0){
							$pdfAttributeY[$orderKey]['is_new'] = 0;
						}else{
							$pdfAttributeY[$orderKey]['is_new'] = 1;
							$pdfAttributeY[$orderKey]['now_location'] = 0;
							
							$pdfAttributeY[$orderKey]['next_location'] = $order_height;
						}
					}
					
					$tmpPreviousDynamic = $tmpIsDynamic;
				}
			}
		}
		
// 		print_r($pdfAttributeY);
// 		exit;

		//新建
		$pdf = new \TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, $format, true, 'UTF-8', false);
		
		//设置页边距
		$pdf->SetMargins(0, 0, 0);
		
		//删除预定义的打印 页眉/页尾
		$pdf->setPrintHeader(false);
		$pdf->setPrintFooter(false);
		
		//设置不自动换页,和底部间距为0
		$pdf->SetAutoPageBreak(false, 0);
		
		//设置字体行距
// 		$pdf->setCellHeightRatio(1.1);

		//需要排序
		ksort($print_params);

		//循环订单
		foreach ($orderlist as $orderKey => $order){
			//A4纸 打印配货单需要计算高度
			if(($format == 'A4')){
				
				//wish邮法国需要动态生成报关单
				$tmpIsDynamic = self::dynamicSetPrintFormat($tmpdeclare_lable_name, $tmpdeclare_lable_calss, $order, $print_params, $print_calss);
// 				//需要排序
// 				ksort($print_params);
				
				
				//当需要打印3种面单时需要做显示位置的处理
				if(count($print_params) == 3){
					if($pdfAttributeY[$orderKey]['is_new'] == 1){
						$pdf->AddPage();
						$int1 = 0;
					}
					
					foreach ($print_params as $printParamsKey => $printParamsVal){
						//当位置为6时直接重置为0，不然会错位
						if($int1 == 6){
							$int1 = 0;
						}
						
						//当打印为配货单时,需要增加高度的控制
						$tmp_items_lable_height = 0;
						if($printParamsKey == 'items_lable'){
							$tmp_items_lable_height = 95;
						}
						
						$print_calss[$printParamsKey]::$printParamsVal($pdfAttribute['x'.$int1], $pdfAttributeY[$orderKey]['now_location']+$tmp_items_lable_height, $pdf, $order, $shippingServece_obj, $format, count($print_params), $otherParams);
				
						//当打印为配货单时,需要控制打印位置
						if($printParamsKey == 'items_lable'){
							$int1++;
						}
						
						$int1++;
					}
					
				}else if((count($print_params) == 2) && (array_key_exists('items_lable', $print_params))){
					//当需要打印面单和配货单时需要作特殊位置控制
					
					if($pdfAttributeY[$orderKey]['is_new'] == 1){
						$pdf->AddPage();
						$int1 = 0;
					}
						
					foreach ($print_params as $printParamsKey => $printParamsVal){
						$print_calss[$printParamsKey]::$printParamsVal($pdfAttribute['x'.$int1], $pdfAttributeY[$orderKey]['now_location'], $pdf, $order, $shippingServece_obj, $format, count($print_params), $otherParams);
					
						if(($printParamsKey == 'items_lable') && ($tmpIsDynamic == true)){
							$int1++;
						}
						
						$int1++;
					}
					
// 					$pdf->writeHTML($pdf->getY().' : '.$pdf->getPageHeight(), false, 0, false, 0);
				}else{
					//A4纸 只打印面单或者打印面单和报关单 时使用
					
					//wish邮法国需要动态生成报关单
					$tmpIsDynamic = self::dynamicSetPrintFormat($tmpdeclare_lable_name, $tmpdeclare_lable_calss, $order, $print_params, $print_calss);
					
					//当打印到第6个位置时直接开一页新的
					if(($int1 % 6) == 0){
						$pdf->AddPage();
						$int1 = 0;
					}
					
					foreach ($print_params as $printParamsKey => $printParamsVal){
						//需要动态生成的面单需要作特殊处理
						if(($tmpIsDynamic == true) && (in_array($int1,array(1,3,5))) && ($printParamsKey == 'carrier_lable')){
							if($int1 == 5){
								$pdf->AddPage();
								$int1 = 0;
							}else{
								$int1++;
							}
						}
						
						$print_calss[$printParamsKey]::$printParamsVal($pdfAttribute['x'.$int1], $pdfAttribute['y'.$int1], $pdf, $order, $shippingServece_obj, $format, count($print_params), $otherParams);
					
						$int1++;
					}
				}
			}else{
				//wish邮法国需要动态生成报关单
				self::dynamicSetPrintFormat($tmpdeclare_lable_name, $tmpdeclare_lable_calss, $order, $print_params, $print_calss);
				
				//10*10 纸时需要每一张面单就新建一页
				foreach ($print_params as $printParamsKey => $printParamsVal){
					$pdf->AddPage();
					$int1 = 0;
					
					$print_calss[$printParamsKey]::$printParamsVal($pdfAttribute['x'.$int1], $pdfAttribute['y'.$int1], $pdf, $order, $shippingServece_obj, $format, count($print_params), $otherParams);
				
					$int1++;
				}
			}
		}
		
		//只生成pdf
		if($is_generate_pdf && !empty($orderlist[0]['order_id'])){
			$uid = \Yii::$app->user->id;
			$key = $uid.'_'.ltrim($orderlist[0]['order_id'],'0');
			
			$tempu = parse_url(\Yii::$app->request->hostInfo);
			$host = $tempu['host'];
			
			//保存pdf
			$filename = 'cs_print_'.$key.'.pdf';
			$file = \eagle\modules\carrier\helpers\CarrierApiHelper::createCarrierLabelDir().'/'.$filename;
			$file = str_replace('\\','/',$file);
			$pdf->Output($file, 'F');
			$url = 'http://'.$host.DIRECTORY_SEPARATOR.'attachment'.DIRECTORY_SEPARATOR.'tmp_api_pdf'.DIRECTORY_SEPARATOR.date("Ymd").DIRECTORY_SEPARATOR.$filename;
			
			$redis_val['url'] = $url;
			$redis_val['carrierName'] = $shippingServece_obj->carrier_name;
			$redis_val['time'] = time();
			RedisHelper::RedisSet('CsPrintPdfUrl', $key, json_encode($redis_val));
			
			return [];
		}
		
		if($result['error'] == false){
			$pdf->Output('print.pdf', 'I');
		}else{
			return $result;
		}
	}
	
	//wish邮法国需要动态生成报关单
	public static function dynamicSetPrintFormat($tmpdeclare_lable_name, $tmpdeclare_lable_calss, $order,&$print_params, &$print_calss, $is_update_print_params = true){
		$tmpIsDynamic = false;

		if($is_update_print_params == true){
			if(!empty($tmpdeclare_lable_name)){
				$print_params['declare_lable'] = $tmpdeclare_lable_name;
				$print_calss['declare_lable'] = $tmpdeclare_lable_calss;
			}else{
				unset($print_params['declare_lable']);
				unset($print_calss['declare_lable']);
			}
		}
		
		if(($print_params['carrier_lable'] == 'getPostWishyouGh') && (!array_key_exists('declare_lable', $print_params)) && (in_array($order->consignee_country_code, array('FR', 'PL', 'CZ', 'HR', 'SK', 'SI', 'DE', 'NZ')))){
			if($is_update_print_params == true){
				$print_params['declare_lable'] = 'getFrenchEnhanceParcelDeclare';
				$print_calss['declare_lable'] =  '\eagle\modules\carrier\apihelpers\PrintPdfHelper';
			}
			
			if(empty($tmpdeclare_lable_name)){
				$tmpIsDynamic = true;
			}
		}
		
		return $tmpIsDynamic;
	}
	
	//wish邮 挂号入口
	public static function getPostWishyouGh($tmpX, $tmpY , &$pdf, $order, $shippingService, $format, $lableCount, $otherParams){
		$otherParams['is_gh'] = 1;
		
		self::getPostWishyouGhAndPy($tmpX, $tmpY , $pdf, $order, $shippingService, $format, $lableCount, $otherParams);
	}
	
	//wish邮 平邮入口
	public static function getPostWishyouPy($tmpX, $tmpY , &$pdf, $order, $shippingService, $format, $lableCount, $otherParams){
		$otherParams['is_gh'] = 0;
	
		self::getPostWishyouGhAndPy($tmpX, $tmpY , $pdf, $order, $shippingService, $format, $lableCount, $otherParams);
	}
	
	
	/**
	 * wish邮 挂号和平邮方法
	 * 
	 * @param $tmpX 				用于打印A4纸时定位打印的位置 X坐标
	 * @param $tmpY					用于打印A4纸时定位打印的位置 Y坐标
	 * @param $pdf					外部传入的PDF对象
	 * @param $order				订单对象
	 * @param $shippingService		运输服务对象
	 * @param $format				打印的格式
	 * @param $lableCount			当前格式需要打印的面单类型数，主要用于控制A4纸时候的定位问题
	 * @param $otherParams			需要打印的额外参数
	 */
	public static function getPostWishyouGhAndPy($tmpX, $tmpY , &$pdf, $order, $shippingService, $format, $lableCount, $otherParams){
		if(!empty($otherParams['is_gh']) && (in_array($order->consignee_country_code, array('FR')))){
// 			$otherParams['complete_declare'] = 'getFrenchEnhanceParcelDeclare';
			
			//wishPost挂号假如为法国时，面单样式跟其它国家是不一样的
			self::getFrenchEnhanceParcel($tmpX, $tmpY , $pdf, $order, $shippingService, $format, $lableCount, $otherParams);
		}else if(!empty($otherParams['is_gh']) && (in_array($order->consignee_country_code, array('PL','CZ','HR','SK','SI','DE','NZ')))){
			
			//wishPost挂号假如为波兰Poland时，面单样式跟其它国家是不一样的
			self::getEnhanceParcel($tmpX, $tmpY , $pdf, $order, $shippingService, $format, $lableCount, $otherParams);
		}else{
			//left边线条
			$pdf->Line(2+$tmpX, 2+$tmpY, 2+$tmpX, 95+$tmpY, $style=array('width' => 0.5, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
			//top边线条
			$pdf->Line(2+$tmpX, 2+$tmpY, 98+$tmpX, 2+$tmpY, $style=array('width' => 0.5, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
			//right边线条
			$pdf->Line(98+$tmpX, 2+$tmpY, 98+$tmpX, 95+$tmpY, $style=array('width' => 0.5, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
			//bottom边线条
			$pdf->Line(2+$tmpX, 95+$tmpY, 98+$tmpX, 95+$tmpY, $style=array('width' => 0.5, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
			
			//图片wish邮下边的线
			$pdf->Line(2+$tmpX, 13+$tmpY, 98+$tmpX, 13+$tmpY, $style=array('width' => 0.5, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
			//图片wish邮竖线
			$pdf->Line(58+$tmpX, 2+$tmpY, 58+$tmpX, 13+$tmpY, $style=array('width' => 0.5, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
			$pdf->Line(72+$tmpX, 2+$tmpY, 72+$tmpX, 13+$tmpY, $style=array('width' => 0.5, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
			
			//图片中国邮政
			$pdf->writeHTMLCell(0, 0, 2+$tmpX, 3+$tmpY, '<img src="/images/customprint/labelimg/chinapost-4.jpg"  width="65" >', 0, 1, 0, true, '', true);
			
			//图片wish邮
			$pdf->writeHTMLCell(0, 0, 74+$tmpX, 3+$tmpY, '<img src="/images/customprint/labelimg/wish-mail.jpg"  width="60" >', 0, 1, 0, true, '', true);
			
			//R字上边的线
			$pdf->Line(2+$tmpX, 34.5+$tmpY, 98+$tmpX, 34.5+$tmpY, $style=array('width' => 0.5, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
			
			//R字下边的横线
			$pdf->Line(2+$tmpX, 49+$tmpY, 98+$tmpX, 49+$tmpY, $style=array('width' => 0.5, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
			
			//退件单位下边的横线
			$pdf->Line(2+$tmpX, 54.5+$tmpY, 98+$tmpX, 54.5+$tmpY, $style=array('width' => 0.5, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
			//退件单位下边的竖线
			$pdf->Line(20+$tmpX, 49+$tmpY, 20+$tmpX, 54.5+$tmpY, $style=array('width' => 0, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
			
			//Description of Contents 表格 横线
			$pdf->Line(2+$tmpX, 60+$tmpY, 98+$tmpX, 60+$tmpY, $style=array('width' => 0.5, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
			$pdf->Line(2+$tmpX, 76+$tmpY, 98+$tmpX, 76+$tmpY, $style=array('width' => 0.5, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
			$pdf->Line(2+$tmpX, 80.5+$tmpY, 98+$tmpX, 80.5+$tmpY, $style=array('width' => 0.5, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
			
			//Description of Contents 表格 竖线
			$pdf->Line(63+$tmpX, 54.5+$tmpY, 63+$tmpX, 80.5+$tmpY, $style=array('width' => 0.5, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
			$pdf->Line(77.5+$tmpX, 54.5+$tmpY, 77.5+$tmpX, 80.5+$tmpY, $style=array('width' => 0.5, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
			
			//自编号上边的线
			$pdf->Line(2+$tmpX, 29+$tmpY, 32.5+$tmpX, 29+$tmpY, $style=array('width' => 0, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
			
			//From 的竖线
			$pdf->Line(32.5+$tmpX, 13+$tmpY, 32.5+$tmpX, 34.5+$tmpY, $style=array('width' => 0, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
			
			//获取相关跟踪号信息
			$trackingInfo = self::getTrackingInfo($order);
			
			//条码的样式
			$style = array(
				'position' => '',
				'align' => 'C',
				'stretch' => false,
				'fitwidth' => true,
				'cellfitalign' => '',
				'border' => false,
				'hpadding' => 'auto',
				'vpadding' => 'auto',
				'fgcolor' => array(0,0,0),
				'bgcolor' => false, //array(255,255,255),
				'text' => true,
				'font' => 'helvetica',
				'fontsize' => 9,
				'stretchtext' =>0
			);
			
			//输出条码
			if(empty($otherParams['is_gh'])){
				$pdf->write1DBarcode($trackingInfo['tracking_number'], 'C128', 20+$tmpX, 33.5+$tmpY, '80', 18.5, 0.53, $style, 'N');
			}else{
				$pdf->write1DBarcode($trackingInfo['tracking_number'], 'C128A', 25+$tmpX, 35+$tmpY, '', 14, 0.36, $style, 'N');
			}
			
			//设置字体
			$pdf->SetFont($otherParams['pdfFont'], '', 7);
			$pdf->writeHTMLCell(0, 0, 30+$tmpX, 5+$tmpY, 'Small Packet BY AIR', 0, 1, 0, true, '', true);
			
			//获取分拣码
			$receiver_country_en_num = $order->consignee_country_code.PrintApiHelper::getNumCode($order->consignee_country_code);
			$pdf->SetFont($otherParams['pdfFont'], '', 10);
			$pdf->writeHTMLCell(0, 0, 60+$tmpX+(strlen($receiver_country_en_num) == 2 ? 2 : 0), 5+$tmpY, $receiver_country_en_num, 0, 1, 0, true, '', true);
			
			//获取发件人信息
			$senderInfo = self::getSenderInfo($order);
			
			//获取收件人信息
			$receiverInfo = self::getReceiverInfo($order);
			
			if($order->consignee_country_code == 'RU'){
				$receiverInfo['RECEIVER_COUNTRY_EN'] = 'russia';
			}
			
			//输出From
			$pdf->SetFont($otherParams['pdfFont'], '', 6.5);
			$pdf->writeHTMLCell(32, 0, 1.5+$tmpX, 13+$tmpY, 'From：'.$senderInfo['SENDER_ADDRESS'].'<br>'.
					$senderInfo['SENDER_AREA'].' '.$senderInfo['SENDER_CITY'].' '.$senderInfo['SENDER_PROVINCE'].'<br>'.$senderInfo['SENDER_COUNTRY_EN'], 0, 1, 0, true, '', true);
			
			$pdf->writeHTMLCell(32, 0, 1.5+$tmpX, 26+$tmpY, 'Phone：'.$senderInfo['SENDER_TELEPHONE'], 0, 1, 0, true, '', true);
			
			
			//wish邮根据国家简码返回对应 分区号
			$tmpGhAreaCode = '';
			if(empty($otherParams['is_gh'])){
				$tmpGhAreaCode = self::getWishPyAreaCode($order->consignee_country_code);
				if($tmpGhAreaCode != ''){
					$tmpGhAreaCode = ' '.$tmpGhAreaCode;
				}
			}else{
				$tmpGhAreaCode = self::getWishGhAreaCode($order->consignee_country_code);
				if($tmpGhAreaCode != ''){
					$tmpGhAreaCode = ' '.$tmpGhAreaCode;
				}
			}
			
			//输出Ship To 的内容zapfdingbats   这里的字体格式为cid0jp，因为droidsansfallback这个字体不支持瑞典字体
			$pdf->SetFont($otherParams['pdfFont'], '', 8.1);
			// 		$pdf->setFontSubsetting(true);
			$pdf->writeHTMLCell(65, 0, 32+$tmpX, 13+$tmpY, 'Ship To:'.$receiverInfo['RECEIVER_NAME'].'<br>'.$receiverInfo['RECEIVER_ADDRESS_MODE2'].
					$receiverInfo['RECEIVER_AREA'].', '.$receiverInfo['RECEIVER_CITY'].', '.$receiverInfo['RECEIVER_PROVINCE'].', '.$receiverInfo['RECEIVER_COUNTRY_EN'].', '.$receiverInfo['RECEIVER_ZIPCODE'].'<br>'.
					$receiverInfo['RECEIVER_COUNTRY_EN'].$tmpGhAreaCode.' '.$receiverInfo['RECEIVER_COUNTRY_CN'].'<br>Phone:'.$receiverInfo['RECEIVER_TELEPHONE'], 0, 1, 0, true, '', true);
			
			//测试字体代码
			// 		$pdf->writeHTMLCell(68, 0, 32+$tmpX, 13+$tmpY, 'Pokémon Diamant Les blocs de construction 8 pcs / set Ship To:Johnny Johansson Lövängsvägen 86 SANDVIKEN SE 瑞典', 0, 1, 0, true, '', true);
			
			//输出 自编号 的内容
			$pdf->SetFont($otherParams['pdfFont'], '', 7);
			$pdf->writeHTMLCell(32, 0, 1.5+$tmpX, 30+$tmpY, '自编号：'.$order->order_id, 0, 1, 0, true, '', true);
			
			if(empty($otherParams['is_gh'])){
				//输出untracked
				$pdf->SetFont($otherParams['pdfFont'], '', 8);
				$pdf->writeHTMLCell(0, 0, 5+$tmpX, 39+$tmpY, 'untracked', 0, 1, 0, true, '', true);
			}else{
				//输出大写R
				$pdf->SetFont($otherParams['pdfFont'], '', 40);
				$pdf->writeHTMLCell(0, 0, 5+$tmpX, 33+$tmpY, 'R', 0, 1, 0, true, '', true);
			}
			
			$tmp_return_unit = $senderInfo['SENDER_COMPANY_NAME'];
			if(isset($shippingService['carrier_params']['return_unit'])){
				$tmp_return_unit = $shippingService['carrier_params']['return_unit'];
			}
			
			//输出 退件单位 的内容
			$pdf->SetFont($otherParams['pdfFont'], '', 7);
			$pdf->writeHTMLCell(32, 0, 1.5+$tmpX, 50+$tmpY, '退件单位：', 0, 1, 0, true, '', true);
			$pdf->writeHTMLCell(0, 0, 20+$tmpX, 50+$tmpY, $tmp_return_unit, 0, 1, 0, true, '', true);
			
			//输出 Description of Contents 描述的内容
			$pdf->SetFont($otherParams['pdfFont'], '', 7);
			$pdf->writeHTMLCell(0, 0, 18+$tmpX, 55.5+$tmpY, 'Description of Contents', 0, 1, 0, true, '', true);
			$pdf->writeHTMLCell(0, 0, 67.5+$tmpX, 55.5+$tmpY, 'kg.', 0, 1, 0, true, '', true);
			$pdf->writeHTMLCell(0, 0, 82+$tmpX, 55.5+$tmpY, 'Val(US $)', 0, 1, 0, true, '', true);
			
			//获取订单详情列表信息
			$itemListDetailInfo = self::getItemListDetailInfo($order, $shippingService);
			
			$declareNameEn = '';
			$amountWeight = 0;
			$amountPrice = 0;
			foreach ($itemListDetailInfo['products'] as $product){
				$declareNameEn .= $product['DECLARE_NAME_EN'].'x'.$product['QUANTITY'].' ';
				$amountWeight += $product['WEIGHT'];
				$amountPrice += $product['AMOUNT_PRICE'];
			}
			
			//判断假如当字符串过长时自动载取300个字符
			// 		if(mb_strwidth($declareNameEn) > 300){
			// 			$declareNameEn = mb_substr($declareNameEn, 0, 300, 'utf8');
			// 		}
			
			//设置显示订单详情
			$pdf->SetFont($otherParams['pdfFont'], '', 6);
			$pdf->writeHTMLCell(60, 0, 1.5+$tmpX, 60+$tmpY, $declareNameEn, 0, 1, 0, true, '', true);
			$pdf->writeHTMLCell(60, 0, 67+$tmpX, 61+$tmpY, $amountWeight, 0, 1, 0, true, '', true);
			$pdf->writeHTMLCell(60, 0, 84+$tmpX, 61+$tmpY, $amountPrice, 0, 1, 0, true, '', true);
			
			//显示汇总后的统计数据
			$pdf->SetFont($otherParams['pdfFont'], '', 7);
			$pdf->writeHTMLCell(60, 0, 2+$tmpX, 76.5+$tmpY, 'Total Gross Weight (Kg)', 0, 1, 0, true, '', true);
			$pdf->writeHTMLCell(60, 0, 67+$tmpX, 76.5+$tmpY, $amountWeight, 0, 1, 0, true, '', true);
			$pdf->writeHTMLCell(60, 0, 84+$tmpX, 76.5+$tmpY, $amountPrice, 0, 1, 0, true, '', true);
			
			//输出固定底部内容
			$pdf->SetFont($otherParams['pdfFont'], '', 5);
			$pdf->writeHTMLCell(100, 0, 2+$tmpX, 81.5+$tmpY, 'I certify that the particulars given in this declaration are correct and this item does not contain any dangerous <br>articles prohibited by legislation or by postal or customers regulations.', 0, 1, 0, true, '', true);
			
			//输出已验视
			$pdf->SetFont($otherParams['pdfFont'], '', 9);
			$pdf->writeHTMLCell(100, 0, 2+$tmpX, 89+$tmpY, 'Sender\'s signiture& Data Signed: '.date('Y-m-d',time()), 0, 1, 0, true, '', true);
			$pdf->writeHTMLCell(30, 0, 75+$tmpX, 89+$tmpY, '已验视 CN22', 0, 1, 0, true, '', true);
			
			//输出已验视的边框
			$pdf->writeHTMLCell(21, 0, 75+$tmpX, 89+$tmpY, '', 1, 1, 0, true, '', true);
		}
	}
	
	//法国增强小包
	public static function getFrenchEnhanceParcel($tmpX, $tmpY , &$pdf, $order, $shippingService, $format, $lableCount, $otherParams){
// 		$pdf->setCellHeightRatio(1.1);
		
		//获取发件人信息
		$senderInfo = self::getSenderInfo($order);
		
		//定义字体:黑体
		$fontSimhei = 'simhei';
		//定义字体:calibri
		$fontCalibri = 'calibri';
		
		//收件人相关地址信息
		$receiverInfo = self::getReceiverInfo($order);
		
		//获取相关跟踪号信息
		$trackingInfo = self::getTrackingInfo($order);
		
		//图片中国邮政
		$pdf->writeHTMLCell(0, 0, 2+$tmpX, 4+$tmpY, '<img src="/images/customprint/labelimg/chinapost-4.jpg"  width="68" >', 0, 1, 0, true, '', true);
		
		//图片LA_poste
		$pdf->writeHTMLCell(0, 0, 26+$tmpX, 3.5+$tmpY, '<img src="/images/customprint/labelimg/wish_LA_poste.png"  width="28" >', 0, 1, 0, true, '', true);
		
		//图片LA_poste
		$pdf->writeHTMLCell(0, 0, 88+$tmpX, 1+$tmpY, '<img src="/images/customprint/labelimg/wish_Expres.png"  width="25" >', 0, 1, 0, true, '', true);
		
		//文字:国际小包页面单
		$pdf->SetFont($fontSimhei, '', 9.3);
		$pdf->writeHTMLCell(0, 0, 45+$tmpX, 5+$tmpY, '国际小包面单', 0, 1, 0, true, '', true);
		$pdf->SetFont($fontCalibri, '', 10);
		$pdf->writeHTMLCell(0, 0, 45+$tmpX, 9+$tmpY, 'TRACKED PACKET', 0, 1, 0, true, '', true);
		
		//top边线条
		$pdf->Line(2+$tmpX, 14.5+$tmpY, 98+$tmpX, 14.5+$tmpY, $style=array('width' => 0.4, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
		//left边线条
		$pdf->Line(2+$tmpX, 14.5+$tmpY, 2+$tmpX, 91.5+$tmpY, $style=array('width' => 0.4, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
		//right边线条
		$pdf->Line(98+$tmpX, 14.5+$tmpY, 98+$tmpX, 91.5+$tmpY, $style=array('width' => 0.4, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
		//bottom边线条
		$pdf->Line(2+$tmpX, 91.5+$tmpY, 98+$tmpX, 91.5+$tmpY, $style=array('width' => 0.4, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
		
		$tmp_agreement_customers = '';
		if(isset($shippingService['carrier_params']['agreement_customers'])){
			$tmp_agreement_customers = $shippingService['carrier_params']['agreement_customers'];
		}
		
		//文字:协议客户:
		$pdf->SetFont($fontSimhei, '', 8);
		$pdf->writeHTMLCell(0, 0, 3+$tmpX, 15+$tmpY, ''.$tmp_agreement_customers, 0, 1, 0, true, '', true);
		
		//协议客户下边的线
		$pdf->Line(2+$tmpX, 19+$tmpY, 98+$tmpX, 19+$tmpY, $style=array('width' => 0.4, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
		
		//FROM:下边的线
		$pdf->Line(2+$tmpX, 35.8+$tmpY, 98+$tmpX, 35.8+$tmpY, $style=array('width' => 0.4, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
		
		//FROM:的竖线
		$pdf->Line(70.5+$tmpX, 19+$tmpY, 70.5+$tmpX, 35.8+$tmpY, $style=array('width' => 0.4, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
		
		//文字:FROM:
		$pdf->SetFont($fontCalibri, '', 9);
		$pdf->writeHTMLCell(65, 0, 2+$tmpX, 19.2+$tmpY, 'FROM:'.$senderInfo['SENDER_ADDRESS'].'<br>'.
					$senderInfo['SENDER_AREA'].' '.$senderInfo['SENDER_CITY'].' '.$senderInfo['SENDER_PROVINCE'], 0, 1, 0, true, '', true);
		
		//FROM:框的国家和邮编
		$pdf->writeHTMLCell(0, 0, 2+$tmpX, 32+$tmpY, strtoupper($senderInfo['SENDER_COUNTRY_EN']).' '.$senderInfo['SENDER_ZIPCODE'], 0, 1, 0, true, '', true);
		
		//文字:收件人邮编
		$pdf->SetFont($fontCalibri, '', 12);
		$pdf->writeHTMLCell(0, 0, 78+$tmpX, 25+$tmpY, $receiverInfo['RECEIVER_ZIPCODE'], 0, 1, 0, true, '', true);
		
		//A:下边的线
		$pdf->Line(2+$tmpX, 57.4+$tmpY, 98+$tmpX, 57.4+$tmpY, $style=array('width' => 0.4, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
		
		//A:的竖线
		$pdf->Line(15.6+$tmpX, 35.8+$tmpY, 15.6+$tmpX, 57.4+$tmpY, $style=array('width' => 0.4, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
		
		//文字:A:
		$pdf->SetFont($fontCalibri, '', 32);
		$pdf->writeHTMLCell(0, 0, 4+$tmpX, 40+$tmpY, 'A:', 0, 1, 0, true, '', true);
		
		//获取默认行距,用于设置完行距后还原不要影响其它代码
		$tmpCellHeightRatio = $pdf->getCellHeightRatio();
		$pdf->setCellHeightRatio(1);
		
		//文字:收件人地址信息
		$pdf->SetFont($fontCalibri, '', 9);
		$pdf->writeHTMLCell(80, 0, 15.5+$tmpX, 36+$tmpY, $receiverInfo['RECEIVER_NAME'].'<br>'.$receiverInfo['RECEIVER_ZIPCODE'].' '.$receiverInfo['RECEIVER_ADDRESS'].'<br>'.
				$receiverInfo['RECEIVER_AREA'].' '.$receiverInfo['RECEIVER_CITY'].' '.$receiverInfo['RECEIVER_PROVINCE'].'<br>'.
				$receiverInfo['RECEIVER_TELEPHONE'], 0, 1, 0, true, '', true);
		
		$pdf->setCellHeightRatio($tmpCellHeightRatio);
		
		//文字:FRANCE
		$pdf->writeHTMLCell(90, 0, 15.5+$tmpX, 53.5+$tmpY, 'FRANCE', 0, 1, 0, true, '', true);
			
		//退件地址:下边的线
		$pdf->Line(2+$tmpX, 64+$tmpY, 98+$tmpX, 64+$tmpY, $style=array('width' => 0.4, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
		
		$tmp_return_address = $senderInfo['SENDER_COMPANY_NAME'];
		if(isset($shippingService['carrier_params']['return_address'])){
			$tmp_return_address = $shippingService['carrier_params']['return_address'];
		}
		
		//文字:退件地址:
		$pdf->SetFont($fontSimhei, '', 9);
		$pdf->writeHTMLCell(0, 0, 2+$tmpX, 58.5+$tmpY, '退件地址:'.$tmp_return_address, 0, 1, 0, true, '', true);
		
		//图片wish_lightning
		$pdf->writeHTMLCell(0, 0, 2+$tmpX, 65+$tmpY, '<img src="/images/customprint/labelimg/wish_lightning.png"  width="24" >', 0, 1, 0, true, '', true);
		
		//图片eubfrscanright
		$pdf->writeHTMLCell(0, 0, 83+$tmpX, 65+$tmpY, '<img src="/images/customprint/labelimg/eubfrscanright.jpg"  width="23" >', 0, 1, 0, true, '', true);
		
		//文字:À FLASHER EN DISTRIBUTION
		$pdf->SetFont($fontCalibri, '', 10.5);
		$pdf->writeHTMLCell(0, 0, 28+$tmpX, 68+$tmpY, 'À FLASHER EN DISTRIBUTION', 0, 1, 0, true, '', true);
		
		//条码的样式
		$style = array(
				'position' => '',
				'align' => 'C',
				'stretch' => false,
				'fitwidth' => true,
				'cellfitalign' => '',
				'border' => false,
				'hpadding' => 'auto',
				'vpadding' => 'auto',
				'fgcolor' => array(0,0,0),
				'bgcolor' => false, //array(255,255,255),
				'text' => true,
				'font' => 'helveticaB',
				'fontsize' => 10,
				'stretchtext' => 4
		);
		
		//输出条码
		$pdf->write1DBarcode($trackingInfo['tracking_number'], 'C128B', 2.5+$tmpX, 75+$tmpY, '', 16.9, 0.47, $style, 'N');
		
// 		$pdf->write1DBarcode('LF020463033CN', 'C128B', 2.5+$tmpX, 75+$tmpY, '', 16.9, 0.47, $style, 'N');
	}
	
	//法国增强小包报关单
	public static function getFrenchEnhanceParcelDeclare($tmpX, $tmpY , &$pdf, $order, $shippingService, $format, $lableCount, $otherParams){
		//收件人相关地址信息
		$receiverInfo = self::getReceiverInfo($order);
		
		//获取发件人信息
		$senderInfo = self::getSenderInfo($order);
		
		//获取订单详情列表信息
		$itemListDetailInfo = self::getItemListDetailInfo($order, $shippingService);
		
		//定义字体:黑体
		$fontSimhei = 'simhei';
		//定义字体:calibri
		$fontCalibri = 'calibri';
		
		//文字:航 空
		$pdf->SetFont($fontSimhei, '', 9.3);
		$pdf->writeHTMLCell(0, 0, 11+$tmpX, 1.5+$tmpY, '航 空', 0, 1, 0, true, '', true);
		
		//文字:BY AIR
		$pdf->SetFont('helvetica', 'B', 9);
		$pdf->writeHTMLCell(0, 0, 10+$tmpX, 5+$tmpY, 'BY AIR', 0, 1, 0, true, '', true);
		
		//文字:SMALL PACKET 
		$pdf->SetFont('helvetica', 'B', 9);
		$pdf->writeHTMLCell(0, 0, 35.5+$tmpX, 3+$tmpY, 'SMALL PACKET', 0, 1, 0, true, '', true);
		
		if(in_array($order->consignee_country_code, array('PL','CZ','HR','SK','SI'))){
			//文字:VPG POST
			$pdf->SetFont('helvetica', 'B', 12);
			$pdf->writeHTMLCell(0, 0, 70+$tmpX, 2.5+$tmpY, 'VPG POST', 0, 1, 0, true, '', true);
		}else{
			//文字:国家代码
			$pdf->SetFont('helvetica', 'B', 15);
			$pdf->writeHTMLCell(0, 0, 75+$tmpX, 1+$tmpY, $receiverInfo['RECEIVER_COUNTRY_EN_AB'], 0, 1, 0, true, '', true);
		}
		
		//top边线条
		$pdf->Line(2+$tmpX, 9+$tmpY, 98+$tmpX, 9+$tmpY, $style=array('width' => 0.4, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
		//left边线条
		$pdf->Line(2+$tmpX, 9+$tmpY, 2+$tmpX, 91.5+$tmpY, $style=array('width' => 0.4, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
		//right边线条
		$pdf->Line(98+$tmpX, 9+$tmpY, 98+$tmpX, 91.5+$tmpY, $style=array('width' => 0.4, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
		//bottom边线条
		$pdf->Line(2+$tmpX, 91.5+$tmpY, 98+$tmpX, 91.5+$tmpY, $style=array('width' => 0.4, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
		
		//报关签条下边的线
		$pdf->Line(2+$tmpX, 18.2+$tmpY, 98+$tmpX, 18.2+$tmpY, $style=array('width' => 0.4, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
		
		//文字:报关签条
		$pdf->SetFont($fontSimhei, '', 9.3);
		$pdf->writeHTMLCell(0, 0, 3+$tmpX, 11.5+$tmpY, '报关签条', 0, 1, 0, true, '', true);
		
		//文字:(CUSTOMS DECLARATION)
		$pdf->SetFont($fontCalibri, '', 10);
		$pdf->writeHTMLCell(0, 0, 17+$tmpX, 11.5+$tmpY, '(CUSTOMS DECLARATION)', 0, 1, 0, true, '', true);
		
		//文字:邮2113
		$pdf->SetFont($fontSimhei, '', 7.5);
		$pdf->writeHTMLCell(0, 0, 59.5+$tmpX, 9.5+$tmpY, '邮2113', 0, 1, 0, true, '', true);
		
		//文字:CN22
		$pdf->SetFont($fontCalibri, '', 7.5);
		$pdf->writeHTMLCell(0, 0, 60.5+$tmpX, 14+$tmpY, 'CN22', 0, 1, 0, true, '', true);
		
		//文字:可以径行开拆
		$pdf->SetFont($fontSimhei, '', 7.5);
		$pdf->writeHTMLCell(0, 0, 76+$tmpX, 9.5+$tmpY, '可以径行开拆', 0, 1, 0, true, '', true);
		
		//文字:May be opened
		$pdf->SetFont($fontCalibri, '', 7.5);
		$pdf->writeHTMLCell(0, 0, 75.5+$tmpX, 14+$tmpY, 'May be opened', 0, 1, 0, true, '', true);
		
		//礼品下边的线
		$pdf->Line(26+$tmpX, 24+$tmpY, 98+$tmpX, 24+$tmpY, $style=array('width' => 0.4, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
		
		//邮件种类下边的线
		$pdf->Line(2+$tmpX, 30+$tmpY, 98+$tmpX, 30+$tmpY, $style=array('width' => 0.4, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
		
		//邮件种类的竖条1
		$pdf->Line(26+$tmpX, 18.2+$tmpY, 26+$tmpX, 30+$tmpY, $style=array('width' => 0.4, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
		//邮件种类的竖条2
		$pdf->Line(40.5+$tmpX, 18.2+$tmpY, 40.5+$tmpX, 30+$tmpY, $style=array('width' => 0.4, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
		//邮件种类的竖条3
		$pdf->Line(59.2+$tmpX, 18.2+$tmpY, 59.2+$tmpX, 78+$tmpY, $style=array('width' => 0.4, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
		//邮件种类的竖条4
		$pdf->Line(73.7+$tmpX, 18.2+$tmpY, 73.7+$tmpX, 30+$tmpY, $style=array('width' => 0.4, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
		
		//邮件种类的竖条3.1
		$pdf->Line(78+$tmpX, 30+$tmpY, 78+$tmpX, 78+$tmpY, $style=array('width' => 0.4, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
		
		//文字:邮件种类
		$pdf->SetFont($fontSimhei, '', 6);
		$pdf->writeHTMLCell(0, 0, 9+$tmpX, 21+$tmpY, '邮件种类', 0, 1, 0, true, '', true);
		
		//文字:Category of Item
		$pdf->SetFont($fontCalibri, '', 6.5);
		$pdf->writeHTMLCell(0, 0, 6+$tmpX, 23+$tmpY, 'Category of Item', 0, 1, 0, true, '', true);
		
		//文字:请在适当的内容前划
		$pdf->SetFont($fontSimhei, '', 6);
		$pdf->writeHTMLCell(0, 0, 1.5+$tmpX, 25+$tmpY, '请在适当的内容前划', 0, 1, 0, true, '', true);
		
		//文字:“√”
		$pdf->SetFont($fontSimhei, '', 5);
		$pdf->writeHTMLCell(0, 0, 20+$tmpX, 25+$tmpY, '“√”', 0, 1, 0, true, '', true);
		
		//文字:礼品框“√”
		$pdf->SetFont($fontSimhei, '', 8);
		$pdf->writeHTMLCell(0, 0, 30.5+$tmpX, 19+$tmpY, '√', 0, 1, 0, true, '', true);
		
		//文字:礼品
		$pdf->SetFont($fontSimhei, '', 7);
		$pdf->writeHTMLCell(0, 0, 46+$tmpX, 18+$tmpY, '礼品', 0, 1, 0, true, '', true);
		
		//文字:gift
		$pdf->SetFont($fontCalibri, '', 7.5);
		$pdf->writeHTMLCell(0, 0, 46.5+$tmpX, 21+$tmpY, 'gift', 0, 1, 0, true, '', true);
		
		//文字:文件
		$pdf->SetFont($fontSimhei, '', 7);
		$pdf->writeHTMLCell(0, 0, 46+$tmpX, 24+$tmpY, '文件', 0, 1, 0, true, '', true);
		
		//文字:Documents
		$pdf->SetFont($fontCalibri, '', 7.5);
		$pdf->writeHTMLCell(0, 0, 43+$tmpX, 27+$tmpY, 'Documents', 0, 1, 0, true, '', true);
		
		//文字:商品货样
		$pdf->SetFont($fontSimhei, '', 7);
		$pdf->writeHTMLCell(0, 0, 79+$tmpX, 18+$tmpY, '商品货样', 0, 1, 0, true, '', true);
		
		//文字:Commercial Sample
		$pdf->SetFont($fontCalibri, '', 7.5);
		$pdf->writeHTMLCell(0, 0, 74+$tmpX, 21+$tmpY, 'Commercial Sample', 0, 1, 0, true, '', true);
		
		//文字:其他
		$pdf->SetFont($fontSimhei, '', 7);
		$pdf->writeHTMLCell(0, 0, 82+$tmpX, 24+$tmpY, '其他', 0, 1, 0, true, '', true);
		
		//文字:Other
		$pdf->SetFont($fontCalibri, '', 7.5);
		$pdf->writeHTMLCell(0, 0, 81.5+$tmpX, 27+$tmpY, 'Other', 0, 1, 0, true, '', true);
		
		
		//线条:内件详细名称和重量
		$pdf->Line(2+$tmpX, 36.2+$tmpY, 98+$tmpX, 36.2+$tmpY, $style=array('width' => 0.4, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
		
// 		for ($tmp_int = 1; $tmp_int < 6; $tmp_int++){
// 			$pdf->Line(2+$tmpX, 36.2+$tmpY+($tmp_int*6), 98+$tmpX, 36.2+$tmpY+($tmp_int*6), $style=array('width' => 0.4, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
// 		}
		
		//线条:协调系统税则号列和货物原产国（只对商品邮件填写）上边
		$pdf->Line(2+$tmpX, 63.8+$tmpY, 98+$tmpX, 63.8+$tmpY, $style=array('width' => 0.4, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
		
		//线条:协调系统税则号列和货物原产国（只对商品邮件填写）下边
		$pdf->Line(2+$tmpX, 72+$tmpY, 98+$tmpX, 72+$tmpY, $style=array('width' => 0.4, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
		
		//线条:CN下边
		$pdf->Line(2+$tmpX, 78+$tmpY, 98+$tmpX, 78+$tmpY, $style=array('width' => 0.4, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
		
		//文字:内件详细名称和重量
		$pdf->SetFont($fontSimhei, '', 6.5);
		$pdf->writeHTMLCell(0, 0, 2+$tmpX, 30.5+$tmpY, '内件详细名称和重量', 0, 1, 0, true, '', true);
		
		//文字:Quantity and detailed description of contents
		$pdf->SetFont($fontCalibri, '', 6.5);
		$pdf->writeHTMLCell(0, 0, 2+$tmpX, 33+$tmpY, 'Quantity and detailed description of contents', 0, 1, 0, true, '', true);
		
		//文字:重量（千克）
		$pdf->SetFont($fontSimhei, '', 6.5);
		$pdf->writeHTMLCell(0, 0, 61.5+$tmpX, 30.5+$tmpY, '重量（千克）', 0, 1, 0, true, '', true);
		
		//文字:Weight (kg)
		$pdf->SetFont($fontCalibri, '', 6.5);
		$pdf->writeHTMLCell(0, 0, 62+$tmpX, 33+$tmpY, 'Weight (kg)', 0, 1, 0, true, '', true);
		
		//文字:价值
		$pdf->SetFont($fontSimhei, '', 6.5);
		$pdf->writeHTMLCell(0, 0, 84+$tmpX, 30.5+$tmpY, '价值', 0, 1, 0, true, '', true);
		
		//文字:Value
		$pdf->SetFont($fontCalibri, '', 6.5);
		$pdf->writeHTMLCell(0, 0, 84+$tmpX, 33+$tmpY, 'Value', 0, 1, 0, true, '', true);
		
		//文字:协调系统税则号列和货物原产国（只对商品邮件填写）
		$pdf->SetFont($fontSimhei, '', 6);
		$pdf->writeHTMLCell(0, 0, 2+$tmpX, 64+$tmpY, '协调系统税则号列和货物原产国（只对商品邮件填写）', 0, 1, 0, true, '', true);
		
		//获取默认行距,用于设置完行距后还原不要影响其它代码
		$tmpCellHeightRatio = $pdf->getCellHeightRatio();
		$pdf->setCellHeightRatio(1);
		
		//文字:HS tariff number and country of origin of goods (Forcommercial items only)
		$pdf->SetFont($fontCalibri, '', 6.5);
		$pdf->writeHTMLCell(0, 0, 2+$tmpX, 66.5+$tmpY, 'HS tariff number and country of origin of goods (For<br>commercial items only)', 0, 1, 0, true, '', true);
		
		//还原默认行距
		$pdf->setCellHeightRatio($tmpCellHeightRatio);
		
		//文字:总重量（千克）
		$pdf->SetFont($fontSimhei, '', 6);
		$pdf->writeHTMLCell(0, 0, 61.2+$tmpX, 65+$tmpY, '总重量（千克）', 0, 1, 0, true, '', true);
		
		//文字:Total Weight(kg)
		$pdf->SetFont($fontCalibri, '', 6.3);
		$pdf->writeHTMLCell(0, 0, 60.8+$tmpX, 67.5+$tmpY, 'Total Weight(kg)', 0, 1, 0, true, '', true);
		
		//文字:总价值
		$pdf->SetFont($fontSimhei, '', 6);
		$pdf->writeHTMLCell(0, 0, 83+$tmpX, 65+$tmpY, '总价值', 0, 1, 0, true, '', true);
		
		//文字:Total Value
		$pdf->SetFont($fontCalibri, '', 6.3);
		$pdf->writeHTMLCell(0, 0, 81+$tmpX, 67.5+$tmpY, 'Total Value', 0, 1, 0, true, '', true);
		
		//文字:CN
		$pdf->SetFont($fontSimhei, '', 14);
		$pdf->writeHTMLCell(0, 0, 28+$tmpX, 71.5+$tmpY, 'CN', 0, 1, 0, true, '', true);
		
		//文字:我保证上述申报准确无误，本函件内未装寄法律或邮政和海关规章禁止寄递的任何危险物品
		$pdf->SetFont($fontSimhei, '', 6);
		$pdf->writeHTMLCell(0, 0, 2+$tmpX, 78.5+$tmpY, '我保证上述申报准确无误，本函件内未装寄法律或邮政和海关规章禁止寄递的任何危险物品', 0, 1, 0, true, '', true);
		
		//文字:I, the undersigned, certify that the particulars given in this declaration are correct and this item does not contain anydangerous articles prohibited by legislation or by postal or customers regulations.
		$pdf->SetFont($fontCalibri, '', 5.6);
		$pdf->writeHTMLCell(0, 0, 2+$tmpX, 81.5+$tmpY, 'I, the undersigned, certify that the particulars given in this declaration are correct and this item does not contain any<br>dangerous articles prohibited by legislation or by postal or customers regulations.', 0, 1, 0, true, '', true);
		
		//文字:寄件人签字
		$pdf->SetFont($fontSimhei, '', 7);
		$pdf->writeHTMLCell(0, 0, 7+$tmpX, 87.3+$tmpY, '寄件人签字', 0, 1, 0, true, '', true);
		
		//文字:Sender's signature: 
		$pdf->SetFont($fontCalibri, '', 7.5);
		$pdf->writeHTMLCell(0, 0, 20+$tmpX, 87.3+$tmpY, 'Sender\'s signature: ', 0, 1, 0, true, '', true);
		
		//线条：Sender's signature: 
		$pdf->Line(43+$tmpX, 90.5+$tmpY, 82.5+$tmpX, 90.5+$tmpY, $style=array('width' => 0.4, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
		
		//文字:发件人信息
		$pdf->SetFont($fontCalibri, '', 10);
		$pdf->writeHTMLCell(0, 0, 42.5+$tmpX, 86.5+$tmpY, $senderInfo['SENDER_NAME'], 0, 1, 0, true, '', true);
		
		//文字:CN22
		$pdf->SetFont($fontSimhei, '', 9);
		$pdf->writeHTMLCell(0, 0, 89+$tmpX, 86.5+$tmpY, 'CN22', 0, 1, 0, true, '', true);
		
		//总重量
		$pdf->SetFont($otherParams['pdfFont'], '', 12);
		$pdf->writeHTMLCell(0, 0, 64+$tmpX, 72+$tmpY, $itemListDetailInfo['lists']['TOTAL_WEIGHT'], 0, 1, 0, true, '', true);
		
		//总金额
		$pdf->SetFont($otherParams['pdfFont'], '', 12);
		$pdf->writeHTMLCell(0, 0, 83+$tmpX, 72+$tmpY, $itemListDetailInfo['lists']['TOTAL_AMOUNT_PRICE'], 0, 1, 0, true, '', true);
		
		//输出报关信息
		$pdf->SetFont($otherParams['pdfFont'], '', 7);
		foreach ($itemListDetailInfo['products'] as $product_key => $product){
			
			if($product_key == 0){
				$pdf->writeHTMLCell(60, 0, 65+$tmpX, 36.5+$tmpY, $product['WEIGHT'], 0, 1, 0, true, '', true);
				$pdf->writeHTMLCell(60, 0, 85+$tmpX, 36.5+$tmpY, $product['AMOUNT_PRICE'], 0, 1, 0, true, '', true);
				
				$pdf->writeHTMLCell(60, 0, 2+$tmpX, 36.5+$tmpY, $product['DECLARE_NAME_EN'].' x '.$product['QUANTITY'], 0, 1, 0, true, '', true);
				
			}else{
				$tmpForY = $pdf->getY();
				$pdf->writeHTMLCell(60, 0, 65+$tmpX, $tmpForY, $product['WEIGHT'], 0, 1, 0, true, '', true);
				$pdf->writeHTMLCell(60, 0, 85+$tmpX, $tmpForY, $product['AMOUNT_PRICE'], 0, 1, 0, true, '', true);
				
				$pdf->writeHTMLCell(60, 0, 2+$tmpX, $tmpForY, $product['DECLARE_NAME_EN'].' x '.$product['QUANTITY'], 0, 1, 0, true, '', true);
			}
		}
	}
	
	//wish DLE/DLP公用
	public static function getPostWishyouDLPE($tmpX, $tmpY , &$pdf, $order, $shippingService, $format, $lableCount, $otherParams){
		//收件人相关地址信息
		$receiverInfo = self::getReceiverInfo($order);
		
		//获取相关跟踪号信息
		$trackingInfo = self::getTrackingInfo($order);
		
		//获取订单详情列表信息
		$itemListDetailInfo = self::getItemListDetailInfo($order, $shippingService);
		
		//图片wish_dle
		$pdf->writeHTMLCell(0, 0, 4+$tmpX, 3+$tmpY, '<img src="/images/customprint/labelimg/wish_dle.png"  width="38" >', 0, 1, 0, true, '', true);
		
		//top边线条
		$pdf->Line(2+$tmpX, 2+$tmpY, 98+$tmpX, 2+$tmpY, $style=array('width' => 0.4, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
		//left边线条
		$pdf->Line(2+$tmpX, 2+$tmpY, 2+$tmpX, 75.5+$tmpY, $style=array('width' => 0.4, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
		//right边线条
		$pdf->Line(98+$tmpX, 2+$tmpY, 98+$tmpX, 75.5+$tmpY, $style=array('width' => 0.4, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
		//bottom边线条
		$pdf->Line(2+$tmpX, 75.5+$tmpY, 98+$tmpX, 75.5+$tmpY, $style=array('width' => 0.4, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
		
		//线条:横中线
		$pdf->Line(2+$tmpX, 33.6+$tmpY, 98+$tmpX, 33.6+$tmpY, $style=array('width' => 0.4, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
		
		//文字:国家中文名
		$pdf->SetFont($otherParams['pdfFont'], '', 9);
		$pdf->writeHTMLCell(0, 0, 26+$tmpX, 5+$tmpY, $receiverInfo['RECEIVER_COUNTRY_CN'], 0, 1, 0, true, '', true);
		
		//输出条码
		if($shippingService->shipping_method_code == '9-'){
			//条码的样式
			$style = array(
					'position' => '',
					'align' => 'C',
					'stretch' => false,
					'fitwidth' => true,
					'cellfitalign' => '',
					'border' => false,
					'hpadding' => 'auto',
					'vpadding' => 'auto',
					'fgcolor' => array(0,0,0),
					'bgcolor' => false, //array(255,255,255),
					'text' => false,
					'font' => 'helvetica',
					'fontsize' => 10,
					'stretchtext' => 0
			);
			
			//DLP
			$pdf->write1DBarcode($trackingInfo['tracking_number'], 'C128', 2.8+$tmpX, 11.5+$tmpY, '', 15.2, 0.445, $style, 'N');
			
			//文字:跟踪号
			$pdf->SetFont($otherParams['pdfFont'], '', 11);
			$pdf->writeHTMLCell(0, 0, 26+$tmpX, 26+$tmpY, $trackingInfo['tracking_number'], 0, 1, 0, true, '', true);
		}else{
			$style = array(
					'position' => '',
					'align' => 'C',
					'stretch' => false,
					'fitwidth' => true,
					'cellfitalign' => '',
					'border' => false,
					'hpadding' => 'auto',
					'vpadding' => 'auto',
					'fgcolor' => array(0,0,0),
					'bgcolor' => false, //array(255,255,255),
					'text' => false,
					'font' => 'helvetica',
					'fontsize' => 9,
					'stretchtext' =>0
			);
			
			//DLE
			$pdf->write1DBarcode($trackingInfo['tracking_number'], 'C128', 0.5+$tmpX, 11.5+$tmpY, '90', 15.4, 0.445, $style, 'N');
			
			//文字:跟踪号
			$pdf->SetFont($otherParams['pdfFont'], '', 11);
			$pdf->writeHTMLCell(0, 0, 32+$tmpX, 26+$tmpY, $trackingInfo['tracking_number'], 0, 1, 0, true, '', true);
		}
		
		//输出报关信息
		$tmpDeclareName = '';
		
		$pdf->SetFont($otherParams['pdfFont'], '', 9);
		foreach ($itemListDetailInfo['products'] as $product_key => $product){
			$tmpDeclareName .= $product['NAME_CN'].'*'.$product['QUANTITY'].';';
		}
		
		$pdf->writeHTMLCell(97, 0, 2+$tmpX, 34+$tmpY, $tmpDeclareName, 0, 1, 0, true, '', true);
		
		//c_code => 产品代码(如：C02 ) q_code => 渠道代码(如：Q03) y_ code => 验证码(如：9709)
		$tmp_c_code = empty($trackingInfo['return_no']['c_code']) ? '' : $trackingInfo['return_no']['c_code'];
		$tmp_q_code = empty($trackingInfo['return_no']['q_code']) ? '' : $trackingInfo['return_no']['q_code'];
		$tmp_y_code = empty($trackingInfo['return_no']['y_code']) ? '' : $trackingInfo['return_no']['y_code'];
		
		if($shippingService->shipping_method_code == '9-'){
			//DLP
			//文字:c_code,q_code,y_code,国家简码
			$pdf->SetFont($otherParams['pdfFont'], '', 8);
			$pdf->writeHTMLCell(0, 0, 41+$tmpX, 5.4+$tmpY, $tmp_c_code, 0, 1, 0, true, '', true);
			$pdf->writeHTMLCell(0, 0, 49+$tmpX, 5.4+$tmpY, $tmp_q_code, 0, 1, 0, true, '', true);
			$pdf->writeHTMLCell(0, 0, 65+$tmpX, 5.4+$tmpY, $receiverInfo['RECEIVER_COUNTRY_EN_AB'], 0, 1, 0, true, '', true);
			$pdf->writeHTMLCell(0, 0, 83+$tmpX, 5.4+$tmpY, $tmp_y_code, 0, 1, 0, true, '', true);
			
			//文字:挂号平邮标识
			$pdf->SetFont($otherParams['pdfFont'], '', 31);
			if(!empty($shippingService->carrier_params['otype'])){
				$pdf->writeHTMLCell(0, 0, 87+$tmpX, 10+$tmpY, 'R', 0, 1, 0, true, '', true);
			}else{
				$pdf->writeHTMLCell(0, 0, 87+$tmpX, 10+$tmpY, 'U', 0, 1, 0, true, '', true);
			}
		}else if($shippingService->shipping_method_code == '10-'){
			//DLE
			
			//文字:q_code,y_code,国家简码
			$pdf->SetFont($otherParams['pdfFont'], '', 8);
			$pdf->writeHTMLCell(0, 0, 45+$tmpX, 5.4+$tmpY, $tmp_q_code, 0, 1, 0, true, '', true);
			$pdf->writeHTMLCell(0, 0, 65+$tmpX, 5.4+$tmpY, $receiverInfo['RECEIVER_COUNTRY_EN_AB'], 0, 1, 0, true, '', true);
			$pdf->writeHTMLCell(0, 0, 83+$tmpX, 5.4+$tmpY, $tmp_y_code, 0, 1, 0, true, '', true);
		}
	}
	
	// 增强小包
	public static function getEnhanceParcel($tmpX, $tmpY , &$pdf, $order, $shippingService, $format, $lableCount, $otherParams){
		// 		$pdf->setCellHeightRatio(1.1);
	
		//获取发件人信息
		$senderInfo = self::getSenderInfo($order);
	
		//定义字体:黑体
		$fontSimhei = 'simhei';
		//定义字体:calibri
		$fontCalibri = 'calibri';
	
		//收件人相关地址信息
		$receiverInfo = self::getReceiverInfo($order);
	
		//获取相关跟踪号信息
		$trackingInfo = self::getTrackingInfo($order);
	
		//图片中国邮政
		$pdf->writeHTMLCell(0, 0, 2+$tmpX, 4+$tmpY, '<img src="/images/customprint/labelimg/chinapost-4.jpg"  width="68" >', 0, 1, 0, true, '', true);
	
		
		if($order->consignee_country_code == 'DE'){
// 			图片LA_poste
			$pdf->writeHTMLCell(0, 0, 26+$tmpX, 5+$tmpY, '<img src="/images/customprint/labelimg/wish_de_post.png"  width="50" >', 0, 1, 0, true, '', true);
		}else if($order->consignee_country_code == 'NZ'){
			//图片LA_poste
			$pdf->writeHTMLCell(0, 0, 26+$tmpX, 5+$tmpY, '<img src="/images/customprint/labelimg/wish_nz_post.png"  width="50" >', 0, 1, 0, true, '', true);
		}else{
			$pdf->SetFont($fontCalibri, '', 16);
			$pdf->writeHTMLCell(0, 0, 25+$tmpX, 3.5+$tmpY, 'VPG POST', 0, 1, 0, true, '', true);
		}
		
		if($order->consignee_country_code == 'NZ'){
			$pdf->SetFont($fontCalibri, '', 10.5);
			$pdf->writeHTMLCell(0, 0, 83+$tmpX, 5.5+$tmpY, 'Economy', 0, 1, 0, true, '', true);
		}else{
			//图片LA_poste
			$pdf->writeHTMLCell(0, 0, 88+$tmpX, 1+$tmpY, '<img src="/images/customprint/labelimg/wish_Expres.png"  width="25" >', 0, 1, 0, true, '', true);
		}
	
		if($order->consignee_country_code == 'NZ'){
			//文字:国际小包页面单
			$pdf->SetFont($fontSimhei, '', 8);
			$pdf->writeHTMLCell(0, 0, 58+$tmpX, 4+$tmpY, '跟踪小包', 0, 1, 0, true, '', true);
			$pdf->SetFont($fontCalibri, '', 9);
			$pdf->writeHTMLCell(0, 0, 52+$tmpX, 8+$tmpY, 'TRACKED PACKET', 0, 1, 0, true, '', true);
		}else{
			//文字:国际小包页面单
			$pdf->SetFont($fontSimhei, '', 8);
			$pdf->writeHTMLCell(0, 0, 63+$tmpX, 4+$tmpY, '跟踪小包', 0, 1, 0, true, '', true);
			$pdf->SetFont($fontCalibri, '', 9);
			$pdf->writeHTMLCell(0, 0, 57+$tmpX, 8+$tmpY, 'TRACKED PACKET', 0, 1, 0, true, '', true);
		}
	
		//top边线条
		$pdf->Line(2+$tmpX, 14.5+$tmpY, 98+$tmpX, 14.5+$tmpY, $style=array('width' => 0.4, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
		//left边线条
		$pdf->Line(2+$tmpX, 14.5+$tmpY, 2+$tmpX, 91.5+$tmpY, $style=array('width' => 0.4, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
		//right边线条
		$pdf->Line(98+$tmpX, 14.5+$tmpY, 98+$tmpX, 91.5+$tmpY, $style=array('width' => 0.4, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
		//bottom边线条
		$pdf->Line(2+$tmpX, 91.5+$tmpY, 98+$tmpX, 91.5+$tmpY, $style=array('width' => 0.4, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
	
		$tmp_agreement_customers = '';
		if(isset($shippingService['carrier_params']['agreement_customers'])){
			$tmp_agreement_customers = $shippingService['carrier_params']['agreement_customers'];
		}
	
		//文字:协议客户:
		$pdf->SetFont($fontSimhei, '', 8);
		$pdf->writeHTMLCell(0, 0, 3+$tmpX, 15+$tmpY, ''.$tmp_agreement_customers, 0, 1, 0, true, '', true);
	
		//协议客户下边的线
		$pdf->Line(2+$tmpX, 19+$tmpY, 98+$tmpX, 19+$tmpY, $style=array('width' => 0.4, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
	
		//FROM:下边的线
		$pdf->Line(2+$tmpX, 35.8+$tmpY, 98+$tmpX, 35.8+$tmpY, $style=array('width' => 0.4, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
	
		//FROM:的竖线
		$pdf->Line(70.5+$tmpX, 19+$tmpY, 70.5+$tmpX, 35.8+$tmpY, $style=array('width' => 0.4, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
	
		//文字:FROM:
		$pdf->SetFont($fontCalibri, '', 9);
		$pdf->writeHTMLCell(65, 0, 2+$tmpX, 19.2+$tmpY, 'FROM:'.$senderInfo['SENDER_ADDRESS'].'<br>'.
				$senderInfo['SENDER_AREA'].' '.$senderInfo['SENDER_CITY'].' '.$senderInfo['SENDER_PROVINCE'], 0, 1, 0, true, '', true);
	
		//FROM:框的国家和邮编
		$pdf->writeHTMLCell(0, 0, 2+$tmpX, 32+$tmpY, strtoupper($senderInfo['SENDER_COUNTRY_EN']).' '.$senderInfo['SENDER_ZIPCODE'], 0, 1, 0, true, '', true);
	
		//文字:收件人邮编
		$pdf->SetFont($fontCalibri, '', 12);
		$pdf->writeHTMLCell(0, 0, 78+$tmpX, 25+$tmpY, $receiverInfo['RECEIVER_ZIPCODE'], 0, 1, 0, true, '', true);
	
		//A:下边的线
		$pdf->Line(2+$tmpX, 57.4+$tmpY, 98+$tmpX, 57.4+$tmpY, $style=array('width' => 0.4, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
	
		//A:的竖线
		$pdf->Line(15.6+$tmpX, 35.8+$tmpY, 15.6+$tmpX, 57.4+$tmpY, $style=array('width' => 0.4, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
	
		//文字:A:
		$pdf->SetFont($fontCalibri, '', 32);
		$pdf->writeHTMLCell(0, 0, 4+$tmpX, 40+$tmpY, 'A:', 0, 1, 0, true, '', true);
	
		//获取默认行距,用于设置完行距后还原不要影响其它代码
		$tmpCellHeightRatio = $pdf->getCellHeightRatio();
		$pdf->setCellHeightRatio(1);
	
		//文字:收件人地址信息
		$pdf->SetFont($fontCalibri, '', 9);
		$pdf->writeHTMLCell(80, 0, 15.5+$tmpX, 36+$tmpY, $receiverInfo['RECEIVER_NAME'].'<br>'.$receiverInfo['RECEIVER_ZIPCODE'].' '.$receiverInfo['RECEIVER_ADDRESS'].'<br>'.
				$receiverInfo['RECEIVER_AREA'].' '.$receiverInfo['RECEIVER_CITY'].' '.$receiverInfo['RECEIVER_PROVINCE'].'<br>'.
				$receiverInfo['RECEIVER_TELEPHONE'], 0, 1, 0, true, '', true);
	
		$pdf->setCellHeightRatio($tmpCellHeightRatio);
	
		//文字:FRANCE
		$pdf->writeHTMLCell(90, 0, 15.5+$tmpX, 53.5+$tmpY, 'FRANCE', 0, 1, 0, true, '', true);
			
		//退件地址:下边的线
		$pdf->Line(2+$tmpX, 64+$tmpY, 98+$tmpX, 64+$tmpY, $style=array('width' => 0.4, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
	
		$tmp_return_address = $senderInfo['SENDER_COMPANY_NAME'];
		if(isset($shippingService['carrier_params']['return_address'])){
			$tmp_return_address = $shippingService['carrier_params']['return_address'];
		}
	
		//文字:退件地址:
		$pdf->SetFont($fontSimhei, '', 9);
		$pdf->writeHTMLCell(0, 0, 2+$tmpX, 58.5+$tmpY, '退件地址:'.$tmp_return_address, 0, 1, 0, true, '', true);
	
// 		//图片wish_lightning
// 		$pdf->writeHTMLCell(0, 0, 2+$tmpX, 65+$tmpY, '<img src="/images/customprint/labelimg/wish_lightning.png"  width="24" >', 0, 1, 0, true, '', true);
	
// 		//图片eubfrscanright
// 		$pdf->writeHTMLCell(0, 0, 83+$tmpX, 65+$tmpY, '<img src="/images/customprint/labelimg/eubfrscanright.jpg"  width="23" >', 0, 1, 0, true, '', true);
	
		//文字:À FLASHER EN DISTRIBUTION
// 		$pdf->SetFont($fontCalibri, '', 10.5);
// 		$pdf->writeHTMLCell(0, 0, 28+$tmpX, 68+$tmpY, 'À FLASHER EN DISTRIBUTION', 0, 1, 0, true, '', true);
	
		//条码的样式
		$style = array(
				'position' => '',
				'align' => 'C',
				'stretch' => false,
				'fitwidth' => true,
				'cellfitalign' => '',
				'border' => false,
				'hpadding' => 'auto',
				'vpadding' => 'auto',
				'fgcolor' => array(0,0,0),
				'bgcolor' => false, //array(255,255,255),
				'text' => true,
				'font' => 'helveticaB',
				'fontsize' => 10,
				'stretchtext' => 4
		);
	
		//输出条码
// 		$pdf->write1DBarcode($trackingInfo['tracking_number'], 'C128B', 2.5+$tmpX, 75+$tmpY, '', 16.9, 0.47, $style, 'N');
		$pdf->write1DBarcode($trackingInfo['tracking_number'], 'C128B', 2.5+$tmpX, 75+$tmpY, '', 18, 0.47, $style, 'N');
	
		// 		$pdf->write1DBarcode('LF020463033CN', 'C128B', 2.5+$tmpX, 75+$tmpY, '', 16.9, 0.47, $style, 'N');
	}
	
	//获取自定义面单入口
	public static function getCustomFormatPDF($orderlist, $print_json_str = '', $custom_format_label = array(), $is_generate_pdf = 0){
		$result = array('error'=>false, 'msg'=>'');
	
		//额外的参数, 例如可以记录加打的内容
		$otherParams = array();
	
		$otherParams['pdfFont'] = 'msyh';
		$otherParams['print_json'] = empty($print_json_str) ? '' : '1';
		
		//判断是否存在订单
		$orderRecordNum = count($orderlist);
		if($orderRecordNum == 0){
			$result['error'] = true;
			$result['msg'] = '请传入订单';
				
			return $result;
		}
	
		//设置打印间隔属性
		$pdfAttribute = array('x0'=>0, 'y0'=>0, 'x1'=>105, 'y1'=>0, 'x2'=>0, 'y2'=>99, 'x3'=>105, 'y3'=>99, 'x4'=>0, 'y4'=>198, 'x5'=>105 ,'y5'=>198);
	
		//定义打印到的位置
		$int1 = 0;
	
		//记录第几张订单
		$intOrderCount = 1;
	
		//获取订单对应的运输服务设置
		if($print_json_str != ''){
			$shippingServece_obj = new SysShippingService();
			$shippingServece_obj->print_type = 4;
			
			$otherParams['format_label'] = $custom_format_label;
		}else{
			$shippingServece_obj = SysShippingService::findOne(['id'=>$orderlist[0]['default_shipping_method_code']]);
			if(empty($shippingServece_obj)){
				$result['error'] = true;
				$result['msg'] = "can't find this shippingservice";
			
				return $result;
			}
		}
	
		//假如没有使用小老板高仿标签(新)，则直接提示错误
		if($shippingServece_obj->print_type != 4){
			$result['error'] = true;
			$result['msg'] = "请先开启自定义标签(新)";
				
			return $result;
		}
	
		//获取物流账号别名
		$carrierAccount = \eagle\modules\carrier\models\SysCarrierAccount::findOne(['id'=>$shippingServece_obj['carrier_account_id']]);
		if(!empty($carrierAccount)){
			$otherParams['carrier_name'] = $carrierAccount['carrier_name'];
		}
		
		//判断打印纸张类型
		$format = array();
		$carrierConfig = ConfigHelper::getConfig("CarrierOpenHelper/CommonCarrierConfig", 'NO_CACHE');
		$carrierConfig = json_decode($carrierConfig,true);
		
		if(!empty($carrierConfig['label_paper_size']['val'])){
			if($carrierConfig['label_paper_size']['val'] == '210x297'){
				$format = 'A4';
			}
		}
// 		$format = array(100, 100);	//20170111

		if($print_json_str != ''){
			$format = $otherParams['format_label'];
		}

		
		//记录原始面单的大小
		if($print_json_str != ''){
			$format_label = $custom_format_label;
		}else{
			$format_label = array();
		}
		
		//需要打印的类名
		$print_calss = array();
		//需要打印的格式/方法
		$print_params = array();
		if(!empty($shippingServece_obj->print_params['label_custom_new'])){
			$print_params['carrier_lable'] = $shippingServece_obj->print_params['label_custom_new']['carrier_lable'];
			$print_params['declare_lable'] = $shippingServece_obj->print_params['label_custom_new']['declare_lable'];
			$print_params['items_lable'] = $shippingServece_obj->print_params['label_custom_new']['items_lable'];
			
			$tmp_print_params = array('地址单'=>'carrier_lable', '报关单'=>'declare_lable', '配货单'=>'items_lable');
			
			$crTemplateArr = \eagle\models\carrier\CrTemplate::find()->select(['template_id','template_width','template_height','template_type','template_content_json'])->where(['template_id'=>$print_params])->asArray()->all();
			
			if(empty($crTemplateArr)){
				$result['error'] = true;
				$result['msg'] = "请确定面单是否已经过期.";
				return $result;
			}
			
			//清空选择
			unset($print_params);
			$print_params = array();
			
			foreach ($crTemplateArr as $crTemplateOne){
				$print_params[$tmp_print_params[$crTemplateOne['template_type']]] = $crTemplateOne['template_content_json'];
			
				if(empty($format)){
					$format = array($crTemplateOne['template_width'], $crTemplateOne['template_height']);
				}
				
				if(empty($format_label)){
					$format_label = array($crTemplateOne['template_width'], $crTemplateOne['template_height']);
				}
			}
		}
		
		if($print_json_str != ''){
			$print_params['carrier_lable'] = $print_json_str;
		}
		
		if(empty($print_params)){
			$result['error'] = true;
			$result['msg'] = "请先设置需要打印的面单类型";
			return $result;
		}
		
		//需要排序
		ksort($print_params);
	
		//新建
		$pdf = new \TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, $format, true, 'UTF-8', false);
	
		//设置页边距
		$pdf->SetMargins(0, 0, 0);
	
		//删除预定义的打印 页眉/页尾
		$pdf->setPrintHeader(false);
		$pdf->setPrintFooter(false);
	
		//设置不自动换页,和底部间距为0
		$pdf->SetAutoPageBreak(false, 0);
	
		//设置字体行距
		//$pdf->setCellHeightRatio(1.1);
	
		//循环订单
		foreach ($orderlist as $orderKey => $order){
			//A4纸 打印配货单需要计算高度
			if(($format == 'A4')){
				//当需要打印3种面单时需要做显示位置的处理
				if(count($print_params) == 3){
					if(($int1 % 6) == 0){
						$pdf->AddPage();
						$int1 = 0;
					}
						
					foreach ($print_params as $printParamsKey => $printParamsVal){
						//当位置为6时直接重置为0，不然会错位
						if($int1 == 6){
							$pdf->AddPage();
							$int1 = 0;
						}
	
						$otherParams['template_content_json'] = $printParamsVal;
						$otherParams['format_label'] = $format_label;
						$otherParams['format_type'] = $printParamsKey;
						$now_height = self::getCarrierTemplatePDF($pdfAttribute['x'.$int1], $pdfAttribute['y'.$int1], $pdf, $order, $shippingServece_obj, $format, count($print_params), $otherParams);
	
						$tmp_add_int = true;
						
						//当打印为配货单时,需要控制打印位置
						if($printParamsKey == 'items_lable'){
							$int1++;
							
// 							if($int1 == 5){
// 								echo $pdfAttribute['y'.$int1] + $now_height.'<br>';
// 							}
							
							if(($int1 != 6) && ((($int1 == 1) && ($pdfAttribute['y'.$int1] + $now_height > 100)) || (($int1 == 3) && ($pdfAttribute['y'.$int1] + $now_height > 305)) || (($int1 == 5) && ($pdfAttribute['y'.$int1] + $now_height > 400)) )){
								$int1 = 0;
								$tmp_add_int = false;
								
// 								if ($int1 == 5)
// 									echo $pdfAttribute['y'.$int1] + $now_height.''.'<br>';
								
								
							}
						}
						
						if($tmp_add_int == true)
							$int1++;
					}
				}else{
					//A4纸 只打印面单或者打印面单和报关单 时使用
						
					//当打印到第6个位置时直接开一页新的
					if(($int1 % 6) == 0){
						$pdf->AddPage();
						$int1 = 0;
					}
						
					foreach ($print_params as $printParamsKey => $printParamsVal){
						$otherParams['template_content_json'] = $printParamsVal;
						$otherParams['format_label'] = $format_label;
						$otherParams['format_type'] = $printParamsKey;
						$now_height = self::getCarrierTemplatePDF($pdfAttribute['x'.$int1], $pdfAttribute['y'.$int1], $pdf, $order, $shippingServece_obj, $format, count($print_params), $otherParams);
							
						$tmp_add_int = true;
						
						//当打印为配货单时,需要控制打印位置
						if($printParamsKey == 'items_lable'){
							if(($int1 != 6) && ((($int1 == 1) && ($pdfAttribute['y'.$int1] + $now_height > 100)) || (($int1 == 3) && ($pdfAttribute['y'.$int1] + $now_height > 250))) ){
								$int1 = 0;
								$tmp_add_int = false;
							}
						}
						
						if($tmp_add_int == true)
							$int1++;
					}
				}
			}else{
				//10*10 纸时需要每一张面单就新建一页
				foreach ($print_params as $printParamsKey => $printParamsVal){
					if(is_array($format_label)){
						if(($format_label[0] == 100) && $format_label[1] == 70){
							$pdf->AddPage('L');
						}else{
							$pdf->AddPage();
						}
					}else{
						$pdf->AddPage();
					}
					
					$int1 = 0;
					
					$otherParams['template_content_json'] = $printParamsVal;
					$otherParams['format_label'] = $format_label;
					$otherParams['format_type'] = $printParamsKey;
					self::getCarrierTemplatePDF($pdfAttribute['x'.$int1], $pdfAttribute['y'.$int1], $pdf, $order, $shippingServece_obj, $format, count($print_params), $otherParams);
	
					$int1++;
				}
			}
		}
		
		//只生成pdf
		if($is_generate_pdf && !empty($orderlist[0]['order_id'])){
			$uid = \Yii::$app->user->id;
			$key = $uid.'_'.ltrim($orderlist[0]['order_id'],'0');
				
			$tempu = parse_url(\Yii::$app->request->hostInfo);
			$host = $tempu['host'];
				
			//保存pdf
			$filename = 'cs_print_'.$key.'.pdf';
			$file = \eagle\modules\carrier\helpers\CarrierApiHelper::createCarrierLabelDir().'/'.$filename;
			$file = str_replace('\\','/',$file);
			$pdf->Output($file, 'F');
			$url = 'http://'.$host.DIRECTORY_SEPARATOR.'attachment'.DIRECTORY_SEPARATOR.'tmp_api_pdf'.DIRECTORY_SEPARATOR.date("Ymd").DIRECTORY_SEPARATOR.$filename;
				
			$redis_val['url'] = $url;
			$redis_val['carrierName'] = $shippingServece_obj->carrier_name;
			$redis_val['time'] = time();
			RedisHelper::RedisSet('CsPrintPdfUrl', $key, json_encode($redis_val));
				
			return [];
		}
		
		if($result['error'] == false){
			$pdf->Output('print.pdf', 'I');
		}else{
			return $result;
		}
	}
	
	//公共自定义面单入口
	public static function getCarrierTemplatePDF($tmpX, $tmpY , &$pdf, $order, $shippingService, $format, $lableCount, $otherParams){
		$tmp_now_height = 0;

		//用于控制统一位置
		$tmpX += 1.5;
		$tmpY += 1.5;

		$pdfFont = 'msyh';

		$format_label = $otherParams['format_label'];

		$html_json = $otherParams['template_content_json'];
		$str_arr = json_decode($html_json, true);
		
		//汇总结构
		$sum_structure = array();
		
		//初始化数据用于用户预览显示数据
		if($otherParams['print_json'] == '1'){
			$tmp_print_josn = <<<EOF
{"lists":{"SHOP_LGS_NAME":"","TOTAL_WEIGHT":0.005,"ORDER_TOTAL_WEIGHT":0.005,"TOTAL_AMOUNT_PRICE":3,"ORDER_PRINT_TIME":"2017-01-13 16:08:30","ORDER_PRINT_TIME2":"01\/13 16:08:30","PRINT_TIME3":"2017-01-13","ORDER_PRINT_TIME3":"2017-01-13","ORDER_PRINT_TIME_YEAR":"2017","ORDER_PRINT_TIME_MONTH":"01","ORDER_PRINT_TIME_DAY":"13","ITEM_LIST_TOTAL_KIND":1,"ITEM_LIST_TOTAL_QUANTITY":1,"EUB_RECEIVER_ZIPCODE_BARCODE":"29790","ORDER_SHOP_NAME":"123@qq.com ","ORDER_BUYER_ID":"123456","ORDER_TRADE_NUMBER":"171-12345","ORDER_SOURCE_CODE":"171-12345","XLB_ORDER_CODE":2819,"XLB_ORDER_CODE_BARCODE":1235,"ORDER_SOURCE_CODE_BARCODE":"171-12345","PARTITION_YARDS_EUB":"","INTERNATIONAL_REGISTERED_PARCEL_SORTING_AREA":26,"INTERNATIONAL_REGISTERED_PARCEL_PARTITION":"5","INTERNATIONAL_COMMON_PACKET_PARTITION":"4","ORDER_TOTAL_FEE":"12.99 EUR","ORDER_CURRENCY":"EUR","RECEIVER_PAYMENT":"0","ORDER_REMARK":"\u6d4b\u8bd5\u5907\u6ce8","ORDER_EXPRESS_WAY":"\u8377\u5170\u5c0f\u5305E\u6302\u53f7","ORDER_EXPRESS_NAME":"\u5b89\u9a8f","ORDER_HAS_BATTERY":""},"products":[{"SKUAUTOID":1,"PICTURE":"","SKU":"SKU001","ITEM_ID":"B00AKYAFII","NAME_CN":"\u5145\u7535\u7ebf-\u7ea2","DECLARE_NAME_CN":"\u5145\u7535\u7ebf-\u7ea2","DECLARE_NAME_EN":"Charging line - red","PRODUCT_TITLE":"Gift LED","WAREHOUSE":"(\u9ed8\u8ba4\u4ed3\u5e93)","GRID_CODE":"\u65e0\u4ed3\u4f4d","QUANTITY":"1","WEIGHT":0.005,"PROPERTY":"","PRICE":"3.00","AMOUNT_PRICE":3,"PROD_WEIGHT":0.005,"PRODUCT_BUYER_NAME":""}]}
EOF;
			$itemListDetailInfo = json_decode($tmp_print_josn, true);
			
			$tmp_print_josn = '';
			$tmp_print_josn = <<<EOF
{"SENDER_NAME":"666f","SENDER_ADDRESS":"dongqu","SENDER_AREA":"cheshi","SENDER_CITY":"zhongshan","SENDER_PROVINCE":"guangdong","SENDER_COUNTRY_CN":"\u4e2d\u56fd","SENDER_COUNTRY_EN":"China","SENDER_COUNTRY_EN_AB":"CN","SENDER_ZIPCODE":"520000","SENDER_TELEPHONE":"15978612345","SENDER_MOBILE":"15978612345","SENDER_EMAIL":"77i9k","SENDER_COMPANY_NAME":"\u5e7f\u5dde\u5e02\u56fd\u9645\u7535\u5b50\u5546\u52a1\u5206\u5c40","SENDER_RETURNGOODS":"","SENDER_PROVINCE_EN":"guangdong","SENDER_CITY_EN":"zhongshan","SENDER_AREA_EN":"cheshi"}
EOF;
			$senderInfo = json_decode($tmp_print_josn, true);
			
			$tmp_print_josn = '';
			$tmp_print_josn = <<<EOF
{"RECEIVER_NAME":"Le B\u00e9on cson","RECEIVER_ADDRESS":"1 rue Bdbsdf","RECEIVER_ADDRESS_MODE2":"1 rue Bdbsdf","RECEIVER_AREA":"","RECEIVER_CITY":"Pont Croix","RECEIVER_PROVINCE":"","RECEIVER_COUNTRY_CN":"\u6cd5\u56fd","RECEIVER_COUNTRY_EN":"France","RECEIVER_ZIPCODE":"29790","RECEIVER_TELEPHONE":"061712345","RECEIVER_MOBILE":"061712345","RECEIVER_EMAIL":"123@qq.com","RECEIVER_COMPANY":"","RECEIVER_COUNTRY_EN_AB":"FR","RECEIVER_DETAILED_ADDRESS":"1 rue Bonosdf"}
EOF;
			$receiverInfo = json_decode($tmp_print_josn, true);
			
			$tmp_print_josn = '';
			$tmp_print_josn = <<<EOF
{"tracking_number":"LM026912345CN","PackageId":"","return_no":null,"LAZADA_PACKAGE_CODE":"","LAZADA_PACKAGE_CODE_BARCODE":"","ORDER_EXPRESS_CODE":"LM026912345CN","ORDER_EXPRESS_CODE_BARCODE":"LM026912345CN"}
EOF;
			$trackingInfo = json_decode($tmp_print_josn, true);
		}else{
			//获取发件人信息
			$senderInfo = self::getSenderInfo($order);
			
			//获取收件人信息
			$receiverInfo = self::getReceiverInfo($order);
			
			//获取相关跟踪号信息
			$trackingInfo = self::getTrackingInfo($order);
			
			//获取订单详情列表信息
			$itemListDetailInfo = self::getItemListDetailInfo($order, $shippingService, true, (($otherParams['format_type'] == 'items_lable') ? true : false));
		}
		
		//获取第一个商品的相关属性
		$tmpOneItemDetailInfo = $itemListDetailInfo['products'][0];
		
		//配货信息
		$productInformations = '';
		foreach ($itemListDetailInfo['products'] as $productOne){
			$productInformations .= $productOne['SKU'].' * '.$productOne['QUANTITY'].';';
		}
		 
		if(!empty($productInformations)){
			$productInformations = substr($productInformations,0,strlen($productInformations)-1);
		}
		
		$oneItemDetailInfo = array(
				'PRODUCT_SALE_SKU' => $tmpOneItemDetailInfo['SKU'],
				'PRODUCT_WAREHOUSE' => $tmpOneItemDetailInfo['WAREHOUSE'],
				'PRODUCT_WAREHOUSE_GRID_CODE' => $tmpOneItemDetailInfo['GRID_CODE'],
				'PRODUCT_NAME_CN' => $tmpOneItemDetailInfo['DECLARE_NAME_CN'],
				'PRODUCT_NAME_EN' => $tmpOneItemDetailInfo['DECLARE_NAME_EN'],
				'PRODUCT_WEIGHT' => $tmpOneItemDetailInfo['PROD_WEIGHT'],
				'PRODUCT_BUYER_NAME' => $tmpOneItemDetailInfo['PRODUCT_BUYER_NAME'],
				'PRODUCT_STOCK_SKU_BARCODE' => $tmpOneItemDetailInfo['SKU'],
				'PRODUCT_INFORMATION' => $productInformations,
				'PRODUCT_TITLE' => $tmpOneItemDetailInfo['PRODUCT_TITLE'],
		);
		
		//合并相关数据为同一个数组,方便统一获取
		$sum_structure = array_merge($senderInfo, $receiverInfo, $trackingInfo, $itemListDetailInfo['lists'], $oneItemDetailInfo);
		
		foreach ($str_arr as $show_pdf){
			$tmp_coordinate = self::getCoordinate($show_pdf['data_type'], $show_pdf['coordinate'].(empty($show_pdf['style']) ? '' : ';'.$show_pdf['style']), $format_label);
			
			if(in_array($show_pdf['data_type'], array('character', 'customtext'))){
				$tmp_text = empty($show_pdf['text']) ? '' : $show_pdf['text'];
					
				if(isset($show_pdf['ids'])){
					foreach ($show_pdf['ids'] as $tmp_littleboss_id){
						if(isset($sum_structure[$tmp_littleboss_id])){
							$tmp_text .= (empty($tmp_text) ? '' : ' ').$sum_structure[$tmp_littleboss_id];
						}
					}
				}

				$pdf->SetFont($pdfFont.($tmp_coordinate['tmpFontBold'] ? ($pdfFont == 'msyh' ? 'bd' : '') : ''), '', $tmp_coordinate['fontSize']);
// 				$pdf->writeHTMLCell($tmp_coordinate['tmpWidth'], 0, $tmp_coordinate['tmpX']+$tmpX, $tmp_coordinate['tmpY']+$tmpY, $tmp_text, 0, 1, 0, true, $tmp_coordinate['tmpTextAlign'], true);

				if($tmp_coordinate['tmpBorder'] == array(0,0,0,0)){
					$tmp_border_style = 0;
				}else{
					$tmp_border_style = array();
					if($tmp_coordinate['tmpBorder'][0] != 0)
						$tmp_border_style['T'] = array('width' => $tmp_coordinate['tmpBorder'][0] / 4, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0));
					
					if($tmp_coordinate['tmpBorder'][1] != 0)
						$tmp_border_style['R'] = array('width' => $tmp_coordinate['tmpBorder'][1] / 4, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0));
					
					if($tmp_coordinate['tmpBorder'][2] != 0)
						$tmp_border_style['B'] = array('width' => $tmp_coordinate['tmpBorder'][2] / 4, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0));
					
					if($tmp_coordinate['tmpBorder'][3] != 0)
						$tmp_border_style['L'] = array('width' => $tmp_coordinate['tmpBorder'][3] / 4, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0));
				}
// 				$tmp_border_style = 0;

				$pdf->writeHTMLCell($tmp_coordinate['tmpWidth'], 0, $tmp_coordinate['tmpX']+$tmpX, $tmp_coordinate['tmpY']+$tmpY, $tmp_text, $tmp_border_style, 1, 0, true, $tmp_coordinate['tmpTextAlign'], true);
				
			}else if(in_array($show_pdf['data_type'], array('line-x'))){
				$pdf->Line($tmp_coordinate['tmpX']+$tmpX, $tmp_coordinate['tmpY']+$tmpY, $tmp_coordinate['tmpWidth']+$tmpX, $tmp_coordinate['tmpY']+$tmpY, $style=array('width' => $tmp_coordinate['tmpHeight'], 'cap' => 'butt', 'join' => 'miter', 'dash' => $tmp_coordinate['tmpLineType'], 'color' => array(0, 0, 0)));
			}else if($show_pdf['data_type'] == 'line-y'){
				$pdf->Line($tmp_coordinate['tmpX']+$tmpX, $tmp_coordinate['tmpY']+$tmpY, $tmp_coordinate['tmpX']+$tmpX, $tmp_coordinate['tmpHeight']+$tmpY, $style=array('width' => $tmp_coordinate['tmpWidth'], 'cap' => 'butt', 'join' => 'miter', 'dash' => $tmp_coordinate['tmpLineType'], 'color' => array(0, 0, 0)));
			}else if(in_array($show_pdf['data_type'], array('onlineimage','image'))){
				$is_show_img = true;
				
				if($show_pdf['data_type'] == 'onlineimage'){
					//curl 控制时间为5秒
					$tmpHelp = new Helper_Curl();
					$tmpHelp::$timeout = 5;
					$response = $tmpHelp::get($show_pdf['img_url']);
					
					if($tmpHelp::$last_post_info['http_code'] != 200){
						$is_show_img = false;
					}
				}
				
				if($is_show_img == true)
					$pdf->writeHTMLCell(0, 0, $tmp_coordinate['tmpX']+$tmpX, $tmp_coordinate['tmpY']+$tmpY, '<img src="'.$show_pdf['img_url'].'"  width="'.$tmp_coordinate['tmpWidth'].'" height="'.$tmp_coordinate['tmpHeight'].'" >', 0, 1, 0, true, '', true);
			}else if($show_pdf['data_type'] == 'barcode'){
				$style = array(
						'position'=>'S',
						'border'=>false,
						'padding'=>0.3,
						'fgcolor'=>array(0,0,0),
						'bgcolor'=>array(255,255,255),
						'text'=>false,
						'font'=>'helvetica',
						'fontsize'=>8,
						'stretchtext'=>0
				);
				
				if(isset($show_pdf['codemunber'][0])){
					if($show_pdf['codemunber'][0] == 'Y')
						$style['text'] = true;
				}

				if(!empty($show_pdf['barcode_id'])){
					if(isset($sum_structure[$show_pdf['barcode_id']])){
						$tmp_barcode_height = $show_pdf['barcode_height'];
						$tmp_barcode_height = ($tmp_barcode_height / 92.328) * 25.4;
						
						$tmp_barcode_type = 'C128';
						$tmp_barcode_str = $sum_structure[$show_pdf['barcode_id']];
						
						if(($show_pdf['barcode_id'] == 'EUB_RECEIVER_ZIPCODE_BARCODE') && (is_numeric($sum_structure[$show_pdf['barcode_id']]))){
							$tmp_barcode_str = '420'.$tmp_barcode_str;
						}
						
						$pdf->write1DBarcode($tmp_barcode_str, $tmp_barcode_type, 1+$tmp_coordinate['tmpX']+$tmpX, 1+$tmp_coordinate['tmpY']+$tmpY, $tmp_coordinate['tmpWidth'], $tmp_barcode_height, 0.4, $style, 'N');
					}
				}
			}else if($show_pdf['data_type'] == 'skulist'){
				$tmp_thead_height = 0;
				$tmp_fixed_width = 0;
				
				if(!empty($show_pdf['thead'])){
					if(count($show_pdf['thead']) > 0){
						$tmp_thead_style = self::getCoordinate('thead_style', $show_pdf['thead_style'], $format_label);
						$tmp_thead_width = 0;
						
						if($show_pdf['no_tdborder'] == 0){
							$tmp_height = $show_pdf['thead'][0][1];
							$tmp_height = ($tmp_height / 85) * 25.4;
							
							//横线
							$pdf->Line($tmp_coordinate['tmpX']+$tmpX+$tmp_fixed_width, $tmp_coordinate['tmpY']+$tmpY, $tmp_coordinate['tmpWidth']+$tmpX-$tmp_fixed_width, $tmp_coordinate['tmpY']+$tmpY, $style=array('width' => 0.2, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
							$pdf->Line($tmp_coordinate['tmpX']+$tmpX+$tmp_fixed_width, $tmp_coordinate['tmpY']+$tmp_height+$tmpY, $tmp_coordinate['tmpWidth']+$tmpX-$tmp_fixed_width, $tmp_coordinate['tmpY']+$tmp_height+$tmpY, $style=array('width' => 0.2, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
							
							$tmp_thead_height = $tmp_coordinate['tmpY']+$tmp_height+$tmpY;
							
							//竖线
							$pdf->Line($tmp_fixed_width+$tmpX, $tmp_coordinate['tmpY']+$tmpY, $tmp_fixed_width+$tmpX, $tmp_coordinate['tmpY']+$tmp_height+$tmpY, $style=array('width' => 0.25, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
							$pdf->Line($tmp_coordinate['tmpWidth']-$tmp_fixed_width+$tmpX, $tmp_coordinate['tmpY']+$tmpY, $tmp_coordinate['tmpWidth']-$tmp_fixed_width+$tmpX, $tmp_coordinate['tmpY']+$tmp_height+$tmpY, $style=array('width' => 0.25, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
						}else{
							$tmp_height = $show_pdf['thead'][0][1];
							$tmp_height = ($tmp_height / 85) * 25.4;
							
							$tmp_thead_height = $tmp_coordinate['tmpY']+$tmp_height+$tmpY;
						}
						
						foreach ($show_pdf['thead'] as $tmp_key => $tmp_thead){
							$pdf->SetFont($pdfFont, '', $tmp_thead_style['fontSize']);
							$pdf->writeHTMLCell(0, 0, $tmp_thead_width+$tmpX, $tmp_coordinate['tmpY']+$tmpY, $tmp_thead[2], 0, 1, 0, true, '', true);
							
							$tmp_thead[0] = ($tmp_thead[0] / 88) * 25.4;
							$tmp_thead_width = $tmp_thead_width+$tmp_thead[0];
							
							//单元格分隔竖线
							if((count($show_pdf['thead'])-1 != $tmp_key) && ($show_pdf['no_tdborder'] == 0))
								$pdf->Line($tmp_thead_width+$tmpX, $tmp_coordinate['tmpY']+$tmpY, $tmp_thead_width+$tmpX, $tmp_coordinate['tmpY']+$tmp_height+$tmpY, $style=array('width' => 0.25, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
						}
					}
				}
				
				$tmp_add_page = false;	//是否当打印配货单是配货单的SKU数量过多时新增一页来打印
				if((($format == array(100, 100)) || ($format == 'A4')) && ($otherParams['format_type'] == 'items_lable')){
					$tmp_add_page = true;
				}
				
				if(!empty($show_pdf['tbody'])){
					if(count($show_pdf['tbody']) > 0){
						$tfoot_no_detail = $show_pdf['tfoot_no_detail'] + 1;
						
						$tmp_tbody_style = self::getCoordinate('tbody_style', $show_pdf['tbody_style'], $format_label);
						$tmp_tbody_width = 0;
						
						$tmp_tbody_height = $show_pdf['tbody'][0][1];
						
// 						$tmp_tbody_height = ($tmp_tbody_height / 170) * 25.4;
						$tmp_tbody_height = ($tmp_tbody_height / 90) * 25.4;
						$tmp_tbody_height_fixed = $tmp_tbody_height;
						
						if($tmp_thead_height == 0){
// 							$tmp_tbody_tdborder_height = $tmp_tbody_height + $tmp_coordinate['tmpY'] + $tmpY;
							$tmp_tbody_tdborder_height = 0 + $tmp_coordinate['tmpY'] + $tmpY;
							$tmp_interval = 0;
						}else{
							$tmp_tbody_tdborder_height = $tmp_tbody_height+$tmp_coordinate['tmpY']+$tmpY+($tmp_thead_height == 0 ? $tmp_tbody_height_fixed : 0);
							$tmp_interval = $tmp_tbody_tdborder_height - $tmp_thead_height;
						}
						
						$tmp_tbody_tdborder_height = $tmp_tbody_tdborder_height - $tmp_interval;
						
						$tmp_list_products = $itemListDetailInfo['products'];
						if(count($tmp_list_products) < $tfoot_no_detail){
							$tmp_int_detail = $tfoot_no_detail - count($tmp_list_products);
							
							for ($tmp_for_int = 0; $tmp_int_detail > $tmp_for_int; $tmp_for_int++){
								$tmp_list_products[] = array();
							}
						}
						
						foreach ($tmp_list_products as $tmp_products_key => $tmp_products){
							//20170117假如是10*10纸打印配货单时，SKU种类过多需要新建一页来打印
							if($tmp_add_page == true){
								if(($tmp_tbody_tdborder_height + $tmp_tbody_height_fixed > 100) && ($format == array(100, 100))){
									$tmp_tbody_tdborder_height = 2;
									$tmp_tbody_height = 6.5 - $tmp_coordinate['tmpY'] - $tmpY;
									$pdf->AddPage();
								}else if(($tmp_tbody_tdborder_height + $tmp_tbody_height_fixed > 297) && ($format == 'A4')){
									$tmp_tbody_tdborder_height = 2;
									$tmp_tbody_height = 6.5 - $tmp_coordinate['tmpY'] - $tmpY;
									$pdf->AddPage();
								}
							}
							
							$tmp_height = $show_pdf['tbody'][0][1];
							$tmp_height = ($tmp_height / 85) * 25.4;
							
							//记录最后的高度
							$tmp_now_height = $tmp_tbody_tdborder_height+$tmp_height;
							
							if($show_pdf['no_tdborder'] == 0){
								//横线
								$pdf->Line($tmp_coordinate['tmpX']+$tmpX+$tmp_fixed_width, $tmp_tbody_tdborder_height, $tmp_coordinate['tmpWidth']+$tmpX-$tmp_fixed_width, $tmp_tbody_tdborder_height, $style=array('width' => 0.2, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
								
								//最后一行输出横线
								if(count($tmp_list_products)-1 == $tmp_products_key)
									$pdf->Line($tmp_coordinate['tmpX']+$tmpX+$tmp_fixed_width, $tmp_tbody_tdborder_height+$tmp_height, $tmp_coordinate['tmpWidth']+$tmpX-$tmp_fixed_width, $tmp_tbody_tdborder_height+$tmp_height, $style=array('width' => 0.2, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
								
								//竖线
								$pdf->Line($tmp_fixed_width+$tmpX, $tmp_tbody_tdborder_height, $tmp_fixed_width+$tmpX, $tmp_tbody_tdborder_height+$tmp_height, $style=array('width' => 0.25, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
								$pdf->Line($tmp_coordinate['tmpWidth']-$tmp_fixed_width+$tmpX, $tmp_tbody_tdborder_height, $tmp_coordinate['tmpWidth']-$tmp_fixed_width+$tmpX, $tmp_tbody_tdborder_height+$tmp_height, $style=array('width' => 0.25, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
							}
							
// 							$tmp_thead[0] = ($tmp_thead[0] / 88) * 25.4;
// 							$tmp_thead_width = $tmp_thead_width+$tmp_thead[0];

							foreach ($show_pdf['tbody'] as $tmp_key => $tmp_tbody){
								$pdf->SetFont($pdfFont, '', $tmp_tbody_style['fontSize']);
								
								$tmp_variable = '';
								$variable = '';
								$tmp_variable_text = '';
								$is_img = false;//是否图片
								
								if(is_array($tmp_tbody[2])){
									foreach ($tmp_tbody[2] as $tmp_tbody2_val){
										if(substr($tmp_tbody2_val, 0, 14) == 'CUSTOMTEXT_ID:'){
											$tmp_tbody2_one = substr($tmp_tbody2_val, 14);
											
											$tmp_variable_text .= (empty($tmp_variable_text) ? '' : ' ').$tmp_tbody2_one;
										}else{
											if($tmp_tbody2_val == 'ITEM_LIST_DETAIL_PICTURE'){
												$is_img = true;
											}
											
											$variable = self::getHtmlVariableToItself($tmp_tbody2_val);
											$tmp_tbody2_one = isset($tmp_products[$variable]) ? $tmp_products[$variable] : '';
											$tmp_variable_text .= (empty($tmp_variable_text) ? '' : ' ').$tmp_tbody2_one;
										}
									}
								}
								
								if($is_img == true){
									$tmp_field_width = ($tmp_tbody[0] / 38) * 25.4;
									
									//假如没有图片的话默认显示No Photo
									$tmp_picture_url = empty($tmp_products[$variable]) ? '/images/batchImagesUploader/no-img.png' : $tmp_products[$variable];
									
									//lazada图片返回双//
									if(substr($tmp_picture_url, 0, 2 ) == '//'){
										$tmp_picture_url = 'http:'.$tmp_picture_url;
									}
										
									//$product['PICTURE'] = '/attachment/tmp_img_tcpdf/20161025/2.jpg';
									//这里需要先判断图片是否是图片流文件不然会生成图片导致程序崩溃
									if(substr($tmp_picture_url, 0, 1 ) == '/'){
// 										$tmp_picture_url = Yii::$app->request->hostinfo.$tmp_picture_url;	//20170517
										
										$tmp_picture_url = dirname(dirname(dirname(__DIR__))).'/web'.$tmp_picture_url;
// 										echo $tmp_picture_url;
// 										exit;
									}
										
									$tmpGetimagesize = false;
									try{
										$tmpGetimagesize = getimagesize($tmp_picture_url);
									}catch (\Exception $ex){
									}
										
									if($tmpGetimagesize == false){
										$tmp_picture_url = '/images/batchImagesUploader/no-img.png';
									}
									
									//显示items图片
									if(!empty($tmp_picture_url)){
										$pdf->writeHTMLCell(0, 0, $tmp_tbody_width+$tmpX, 0.6+$tmp_tbody_height+$tmp_coordinate['tmpY']+$tmpY-$tmp_interval-($tmp_thead_height == 0 ? $tmp_tbody_height_fixed : 0), '<img src="'.$tmp_picture_url.'"  width="'.$tmp_field_width.'" height="'.$tmp_field_width.'" >', 0, 0, 0, true, '', true);
									}
								}else{
									$tmp_field_width = ($tmp_tbody[0] / 92.328) * 25.4;
									
									$pdf->writeHTMLCell($tmp_field_width, 0, $tmp_tbody_width+$tmpX, $tmp_tbody_height+$tmp_coordinate['tmpY']+$tmpY-$tmp_interval-($tmp_thead_height == 0 ? $tmp_tbody_height_fixed : 0), $tmp_variable_text, 0, 1, 0, true, '', true);
								}
								
								//单元格分隔竖线
								if(($show_pdf['no_tdborder'] == 0) && ($tmp_key != 0)){
									$pdf->Line($tmp_tbody_width+$tmpX, $tmp_tbody_tdborder_height, $tmp_tbody_width+$tmpX, $tmp_tbody_tdborder_height+$tmp_height, $style=array('width' => 0.25, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
								}
								
// 								$tmp_thead[0] = ($tmp_thead[0] / 88) * 25.4;
// 								$tmp_thead_width = $tmp_thead_width+$tmp_thead[0];
								
								$tmp_tbody[0] = ($tmp_tbody[0] / 88) * 25.4;
								$tmp_tbody_width = $tmp_tbody_width+$tmp_tbody[0];
							}
							
							$tmp_tbody_width = 0;
							$tmp_tbody_height = $tmp_tbody_height + $tmp_tbody_height_fixed;
							$tmp_tbody_tdborder_height = $tmp_tbody_tdborder_height + $tmp_tbody_height_fixed;
						}
					}
				}
				
				if(!empty($show_pdf['tfoot'])){
					if(count($show_pdf['tfoot']) > 0){
						$tmp_tfoot_style = self::getCoordinate('thead_style', $show_pdf['thead_style'], $format_label);
						
						$tmp_tfoot_height = $show_pdf['tfoot_height'];
						$tmp_tfoot_height = ($tmp_tfoot_height / 85) * 25.4;
						
						$tfoot_width = $show_pdf['tfoot_width'];
						$tfoot_width = ($tfoot_width / 90) * 25.4;
						
						$tmp_tfoot_width = 0;
						$tmp_tfoot_text = '';
						
						$tmp_now_height = $tmp_tbody_tdborder_height+$tmp_tfoot_height;
						
						if($show_pdf['no_tdborder'] == 0){
							//底线
							$pdf->Line($tmp_coordinate['tmpX']+$tmpX+$tmp_fixed_width, $tmp_tbody_tdborder_height+$tmp_tfoot_height, $tmp_coordinate['tmpWidth']+$tmpX-$tmp_fixed_width, $tmp_tbody_tdborder_height+$tmp_tfoot_height, $style=array('width' => 0.2, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
							
							$pdf->Line($tmp_fixed_width+$tmpX, $tmp_tbody_tdborder_height, $tmp_fixed_width+$tmpX, $tmp_tbody_tdborder_height+$tmp_tfoot_height, $style=array('width' => 0.25, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
							$pdf->Line($tmp_coordinate['tmpWidth']-$tmp_fixed_width+$tmpX, $tmp_tbody_tdborder_height, $tmp_coordinate['tmpWidth']-$tmp_fixed_width+$tmpX, $tmp_tbody_tdborder_height+$tmp_tfoot_height, $style=array('width' => 0.25, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
						}
						
						foreach ($show_pdf['tfoot'] as $tmp_key => $tmp_tfoot){
							$variable = self::getHtmlVariableToItself($tmp_tfoot[0]);
							$variable_str = $sum_structure[$variable];
							
							$tmp_tfoot_text .= (empty($tmp_tfoot_text) ? '' : ' ') . $tmp_tfoot[1].$variable_str.$tmp_tfoot[2];
						}
						
						$pdf->SetFont($pdfFont, '', $tmp_tfoot_style['fontSize']);
						$pdf->writeHTMLCell($tfoot_width, 0, $tmp_tfoot_width+$tmpX, $tmp_tbody_tdborder_height+0.3, $tmp_tfoot_text, 0, 1, 0, true, 'R', true);
					}
				}
			}
		}
		
		return $tmp_now_height;
		
// 		$style5 = array('width' => 0.5, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0));
// 		$pdf->SetLineStyle($style5);
// 		$pdf->Circle(25, 50, 10);
	}
	
	/**
	 * 获取对应的位置
	 * @param $data_type
	 * @param $coordinate_str
	 * @return 
	 */
	public static function getCoordinate($data_type, $coordinate_str, $format_label){
		$tmpX = 0;
		$tmpY = 0;
		$tmpWidth = 0;
		$tmpFontSize = 12;
		$tmpHeight = 0;
		$tmpTextAlign = '';		//文本位置，居中，靠左，靠右
		$tmpFontBold = false;	//字体是否粗体
		$tmpLineType = 0;		//solid/dotted/dashed  线条的类型
		$tmpBorder = array(0, 0, 0, 0);	//边框
		
		$coordinate_str = str_replace("px", "", $coordinate_str);
		$coordinate_arr = explode(";", $coordinate_str);
		
		foreach ($coordinate_arr as $tmp_val){
			if(stripos($tmp_val, "font-weight:") !== false){
				$tmp_val = str_replace("font-weight:", "", $tmp_val);
				$tmp_val = trim($tmp_val);
				
				if($tmp_val >= 700){
					$tmpFontBold = true;
				}
			}else if(stripos($tmp_val, "padding-top:") !== false){
			}else if((stripos($tmp_val, "border-width:") !== false) && (in_array($data_type, array('character', 'customtext')))){
				//边框线
				$tmp_val = str_replace("border-width:", "", $tmp_val);

				$border_width_arr = explode(" ", $tmp_val);
				foreach ($border_width_arr as $border_width_key => $border_width_val){
					if($border_width_val == ''){
						unset($border_width_arr[$border_width_key]);
					}
				}
				
				$tmp_border_width_int = 0;
				foreach ($border_width_arr as $border_width_one){
					if(count($border_width_arr) == 1){
						$tmpBorder[0] = $tmpBorder[2] = $tmpBorder[1] = $tmpBorder[3] = $border_width_one;
					}else if(count($border_width_arr) == 2){
						if($tmp_border_width_int == 0){
							$tmpBorder[0] = $tmpBorder[2] = $border_width_one;
						}else if($tmp_border_width_int == 1){
							$tmpBorder[1] = $tmpBorder[3] = $border_width_one;
						}
					}else if(count($border_width_arr) == 3){
						if($tmp_border_width_int == 0){
							$tmpBorder[0] = $border_width_one;
						}else if($tmp_border_width_int == 1){
							$tmpBorder[1] = $tmpBorder[3] = $border_width_one;
						}else if($tmp_border_width_int == 2){
							$tmpBorder[2] = $border_width_one;
						}
					}else if(count($border_width_arr) == 4){
						$tmpBorder[$tmp_border_width_int] = $border_width_one;
					}
					
					$tmp_border_width_int++;
				}
			}else if(stripos($tmp_val, "border-top:") !== false){
				$tmp_val = str_replace("border-top:", "", $tmp_val);
				
				if(stripos($tmp_val, "dotted") !== false){
					$tmpLineType = 1;
				}else if(stripos($tmp_val, "dashed") !== false){
					$tmpLineType = 4;
				}
			}else if(stripos($tmp_val, "border-top-width:") !== false){
				if(in_array($data_type, array('character', 'customtext'))){
					$tmp_val = str_replace("border-top-width:", "", $tmp_val);
					$tmpBorder[0] = $tmp_val;
				}
			}else if(stripos($tmp_val, "border-bottom-width:") !== false){
				if(in_array($data_type, array('character', 'customtext'))){
					$tmp_val = str_replace("border-bottom-width:", "", $tmp_val);
					$tmpBorder[2] = $tmp_val;
				}
			}else if(stripos($tmp_val, "border-left-width:") !== false){
				if(in_array($data_type, array('character', 'customtext'))){
					$tmp_val = str_replace("border-left-width:", "", $tmp_val);
					$tmpBorder[3] = $tmp_val;
				}
			}else if(stripos($tmp_val, "border-right-width:") !== false){
				if(in_array($data_type, array('character', 'customtext'))){
					$tmp_val = str_replace("border-right-width:", "", $tmp_val);
					$tmpBorder[1] = $tmp_val;
				}
			}else if(stripos($tmp_val, "height:") !== false){
				$tmpHeight = str_replace("height:", "", $tmp_val);
			}else if (stripos($tmp_val, "left:") !== false){
				$tmpX = str_replace("left:", "", $tmp_val);
			}else if(stripos($tmp_val, "top:") !== false){
				$tmpY = str_replace("top:", "", $tmp_val);
			}else if(stripos($tmp_val, "width:") !== false){
				$tmpWidth = str_replace("width:", "", $tmp_val);
			}else if(stripos($tmp_val, "font-size:") !== false){
				$tmpFontSize = str_replace("font-size:", "", $tmp_val);
			}else if(stripos($tmp_val, "text-align:") !== false){
				$tmp_val = str_replace("text-align:", "", $tmp_val);
				$tmp_val = trim($tmp_val);
				
				switch ($tmp_val){
					case 'center':
						$tmpTextAlign = 'C';
						break;
					case 'right':
						$tmpTextAlign = 'R';
						break;
					default:
						$tmpTextAlign = 'L';
				}
			}
		}
		
		if($data_type == 'barcode'){
			$tmpX = ($tmpX / 102.5) * 25.4;
			$tmpY = ($tmpY / 92.328) * 25.4;
			
			$tmpWidth = ($tmpWidth / 93.5) * 25.4;
		}else if(in_array($data_type, array('onlineimage','image'))){
			$tmpX = ($tmpX / 94) * 25.4;
			$tmpY = ($tmpY / 92.328) * 25.4;
		}else if($data_type == 'skulist'){
			$tmpX = ($tmpX / 92.328) * 25.4;
			$tmpY = ($tmpY / 90) * 25.4;
		}else if($data_type == 'line-x'){
			$tmpX = ($tmpX / 93.3) * 25.4;
			$tmpY = ($tmpY / 93.3) * 25.4;
		}else if($data_type == 'line-y'){
			$tmpX = ($tmpX / 93.3) * 25.4;
			$tmpY = ($tmpY / 93.3) * 25.4;
		}else{
// 			$tmpX = ($tmpX / 92.328) * 25.4;
// 			$tmpY = ($tmpY / 92.328) * 25.4;
			
			$tmpX = ($tmpX / 95) * 25.4;
			$tmpY = ($tmpY / 95) * 25.4;
		}
		
		$tmpFontSize = ($tmpFontSize / 36) * 25.4;
		
// 		$tmpX = ($tmpX / 3.78);
// 		$tmpY = ($tmpY / 3.78);
		
// 		象素数 / DPI = 英寸数
// 		英寸数 * 25.4 = 毫米数
// 		基本上 1毫米 约等于 3.78像素

		$tmpWidth = str_replace(" ", "", $tmpWidth);
		
		if($data_type == 'skulist'){
			if($tmpWidth == '100%'){
				$tmpWidth = $format_label[0] - 3;
			}else{
				$tmpWidth = ($tmpWidth / 92.328) * 25.4;
			}
		}else if(in_array($data_type, array('onlineimage','image'))){
			$tmpWidth = ($tmpWidth / 34.5) * 25.4;
			
			$tmpHeight = ($tmpHeight / 34.5) * 25.4;
		}else if(in_array($data_type, array('line-x'))){
			$tmpHeight = $tmpHeight / 4;
			
			$tmpWidth = str_replace(" ", "", $tmpWidth);
			
			if(($format_label == array(100, 100)) && ($tmpWidth == 358)){
				$tmpWidth = 97;
			}else if($tmpWidth == '100%'){
				$tmpWidth = $format_label[0] - 3;
			}else{
				$tmpWidth = ($tmpWidth / 95) * 25.4;
				
				$tmpWidth = $tmpX + $tmpWidth;
			}
		}else if($data_type == 'line-y'){
			$tmpWidth = $tmpWidth / 4;
			
			$tmpHeight = str_replace(" ", "", $tmpHeight);
			
			if(($format_label == array(100, 100)) && ($tmpHeight == 358)){
				$tmpHeight = 97;
			}else if($tmpHeight == '100%'){
				$tmpHeight = $format_label[1] - 3;
			}else{
				$tmpHeight = ($tmpHeight / 95) * 25.4;
			
				$tmpHeight = $tmpY + $tmpHeight;
			}
		}else if(in_array($data_type, array('character', 'customtext'))){
			$tmpWidth = ($tmpWidth / 92.328) * 25.4;
		}
		
		return array('tmpX'=>$tmpX, 'tmpY'=>$tmpY, 'tmpWidth'=>$tmpWidth, 'fontSize'=>$tmpFontSize,
				'tmpHeight'=>$tmpHeight, 'tmpTextAlign'=>$tmpTextAlign, 'tmpFontBold'=>$tmpFontBold,
				'tmpLineType'=>$tmpLineType, 'tmpBorder'=>$tmpBorder);
	}

	//因为前期写的公用function时候开了新的变量名但是其实所用到的值都是一样的所以这里加一层转换来寻找对应的参数
	public static function getHtmlVariableToItself($variable){
		$variableArr = array(
				'ITEM_LIST_DETAIL_SKUAUTOID' => 'SKUAUTOID',
				'ITEM_LIST_DETAIL_PICTURE' => 'PICTURE',
				'ITEM_LIST_DETAIL_SKU' => 'SKU',
				'ITEM_LIST_DETAIL_ITEM_ID' => 'ITEM_ID',
				'ITEM_LIST_DETAIL_NAME_CN' => 'DECLARE_NAME_CN',
				'ITEM_LIST_DETAIL_NAME_EN' => 'DECLARE_NAME_EN',
				'ITEM_LIST_DETAIL_PRODUCT_TITLE' => 'PRODUCT_TITLE',
				'ITEM_LIST_DETAIL_WAREHOUSE' => 'WAREHOUSE',
				'ITEM_LIST_DETAIL_GRID_CODE' => 'GRID_CODE',
				'ITEM_LIST_DETAIL_QUANTITY' => 'QUANTITY',
				'ITEM_LIST_DETAIL_WEIGHT' => 'WEIGHT',
				'ITEM_LIST_DETAIL_PROPERTY' => 'PROPERTY',
				'ITEM_LIST_DETAIL_PRICE' => 'PRICE',
				'ITEM_LIST_DETAIL_AMOUNT_PRICE' => 'AMOUNT_PRICE',
				'ITEM_LIST_TOTAL_WEIGHT' => 'TOTAL_WEIGHT',
				'ITEM_LIST_TOTAL_AMOUNT_PRICE' => 'TOTAL_AMOUNT_PRICE',
				'ITEM_LIST_DETAIL_NAME_PICKING' => 'PRODUCT_NAME_PICKING'
		);
		
		if(isset($variableArr[$variable])){
			return $variableArr[$variable];
		}else{
			return $variable;
		}
	}
		
	//wish邮根据国家简码返回对应 挂号区号
	public static function getWishGhAreaCode($country_code){
		$countryArr = array('EG'=>'10','AI'=>'10','AG'=>'10','PR'=>'10','BW'=>'10','GQ'=>'10','GL'=>'10','GE'=>'9','KZ'=>'5','AN'=>'','GW'=>'10','CI'=>'10','HR'=>'3','RO'=>'5',
				'FK'=>'','YT'=>'10','SK'=>'3','TJ'=>'5','AM'=>'5','AL'=>'9','DZ'=>'10','AF'=>'8','AR'=>'7','AE'=>'8','AW'=>'10','OM'=>'5','AZ'=>'5','ET'=>'10','IE'=>'3',
				'EE'=>'5','AD'=>'9','AO'=>'10','AT'=>'3','AU'=>'3','BB'=>'10','PY'=>'10','PG'=>'10','BS'=>'10','PK'=>'5','PS'=>'5','BH'=>'8','PA'=>'10','BR'=>'7','BY'=>'5',
				'BM'=>'','BG'=>'3','BJ'=>'10','BE'=>'3','IS'=>'9','BA'=>'5','PL'=>'3','BO'=>'10','BZ'=>'10','BT'=>'8','BF'=>'10','BI'=>'10','KP'=>'5','DK'=>'3','DE'=>'3',
				'TP'=>'8','TG'=>'10','DO'=>'10','DM'=>'','RU'=>'11','EC'=>'10','FR'=>'5','PF'=>'10','GF'=>'10','VA'=>'9','PH'=>'5','FJ'=>'10','FI'=>'3','CV'=>'10',
				'FK'=>'10','GM'=>'10','CG'=>'','CO'=>'10','CR'=>'10','GD'=>'10','CU'=>'10','GP'=>'','GU'=>'10','GY'=>'10','HT'=>'10','KR'=>'2','NL'=>'3','ME'=>'9','HN'=>'10',
				'KI'=>'10','DJ'=>'10','KG'=>'5','GN'=>'10','CA'=>'5','GH'=>'10','GA'=>'10','KH'=>'','CZ'=>'3','ZW'=>'10','CM'=>'10','QA'=>'5','KY'=>'10','KW'=>'8','KE'=>'10',
				'LV'=>'5','LS'=>'10','LA'=>'8','LB'=>'8','LT'=>'5','LR'=>'10','LI'=>'9','RE'=>'','LU'=>'5','RW'=>'10','MG'=>'10','MV'=>'8','MT'=>'5','MY'=>'2','ML'=>'10',
				'MK'=>'9','MQ'=>'10','MU'=>'10','MR'=>'10','US'=>'5','VI'=>'10','MN'=>'5','BD'=>'8','PE'=>'8','MM'=>'8','MD'=>'9','MC'=>'9','MA'=>'10','MZ'=>'10','MX'=>'7',
				'NA'=>'10','ZA'=>'6','NR'=>'10','NP'=>'8','NI'=>'10','NE'=>'10','NG'=>'10','NO'=>'3','PW'=>'10','PT'=>'3','JP'=>'1','SE'=>'3','CH'=>'3','SV'=>'10','RS'=>'9',
				'SL'=>'10','SN'=>'10','CY'=>'5','SC'=>'10','SA'=>'5','LK'=>'5','SI'=>'5','SD'=>'10','SR'=>'10','SB'=>'10','TH'=>'2','TZ'=>'10','TO'=>'10','TT'=>'10','TN'=>'10',
				'TV'=>'10','TR'=>'4','TM'=>'5','VU'=>'10','GT'=>'10','VE'=>'10','BN'=>'8','UG'=>'10','UA'=>'5','UY'=>'10','UZ'=>'5','ES'=>'5','GR'=>'3','SG'=>'2','NC'=>'10',
				'NZ'=>'4','HU'=>'3','SY'=>'5','JM'=>'10','IQ'=>'8','IR'=>'8','IL'=>'3','IT'=>'3','IN'=>'2','ID'=>'2','GB'=>'5','VG'=>'10','JO'=>'8','VN'=>'5','ZM'=>'10','ZR'=>'10',
				'TD'=>'10','GI'=>'9','CL'=>'8','CF'=>'10','SZ'=>'10','PM'=>'','YE'=>'8','FO'=>'9','SM'=>'9','KN'=>'10','LC'=>'10','AS'=>'10','CK'=>'10','SRB'=>'9','MNE'=>'9',
				'MW'=>'','ASC'=>'','CC'=>'','CX'=>'','EH'=>'','ER'=>'','FM'=>'','GG'=>'','JU'=>'','KM'=>'','MH'=>'','MP'=>'','MS'=>'','NF'=>'','NU'=>'','SGS'=>'','SH'=>'','SJ'=>'',
				'SS'=>'','ST'=>'','TC'=>'','TK'=>'','TL'=>'','UK'=>'','UM'=>'','VC'=>'','WF'=>'','WS'=>'','XM'=>'','XN'=>'','CRO'=>'3','CS'=>'3','KT'=>'10','MDV'=>'8',);
		
		if(isset($countryArr[$country_code])){
			return $countryArr[$country_code];
		}else{
			return '';
		}
	}
	
	//wish邮根据国家简码返回对应 平邮区号
	public static function getWishPyAreaCode($country_code){
		$countryArr = array('EG'=>'5','AI'=>'6','AG'=>'6','PR'=>'6','BW'=>'5','GQ'=>'5','GL'=>'6','GE'=>'5','KZ'=>'4','AN'=>'','GW'=>'5','CI'=>'5','HR'=>'8','RO'=>'4','FK'=>'',
				'YT'=>'5','SK'=>'2','TJ'=>'4','AM'=>'4','AL'=>'5','DZ'=>'5','AF'=>'5','AR'=>'5','AE'=>'5','AW'=>'6','OM'=>'4','AZ'=>'4','ET'=>'5','IE'=>'3','EE'=>'4','AD'=>'5',
				'AO'=>'5','AT'=>'2','AU'=>'8','BB'=>'6','PY'=>'6','PG'=>'5','BS'=>'6','PK'=>'4','PS'=>'4','BH'=>'5','PA'=>'5','BR'=>'5','BY'=>'4','BM'=>'','BG'=>'2','BJ'=>'5',
				'BE'=>'3','IS'=>'5','BA'=>'4','PL'=>'3','BO'=>'6','BZ'=>'6','BT'=>'5','BF'=>'5','BI'=>'5','KP'=>'4','DK'=>'3','DE'=>'8','TP'=>'5','TG'=>'5','DO'=>'6','DM'=>'',
				'RU'=>'7','EC'=>'5','FR'=>'4','PF'=>'5','GF'=>'6','VA'=>'5','PH'=>'4','FJ'=>'5','FI'=>'3','CV'=>'5','FK'=>'6','GM'=>'5','CG'=>'','CO'=>'5','CR'=>'6','GD'=>'6',
				'CU'=>'5','GP'=>'','GU'=>'5','GY'=>'6','HT'=>'6','KR'=>'2','NL'=>'8','ME'=>'5','HN'=>'6','KI'=>'5','DJ'=>'5','KG'=>'4','GN'=>'5','CA'=>'4','GH'=>'','GA'=>'5',
				'KH'=>'','CZ'=>'3','ZW'=>'5','CM'=>'5','QA'=>'4','KY'=>'6','KW'=>'5','KE'=>'5','LV'=>'4','LS'=>'5','LA'=>'5','LB'=>'5','LT'=>'4','LR'=>'5','LI'=>'5','RE'=>'',
				'LU'=>'4','RW'=>'5','MG'=>'5','MV'=>'5','MT'=>'4','MY'=>'2','ML'=>'5','MK'=>'5','MQ'=>'6','MU'=>'5','MR'=>'5','US'=>'4','VI'=>'6','MN'=>'4','BD'=>'5','PE'=>'5',
				'MM'=>'5','MD'=>'5','MC'=>'5','MA'=>'5','MZ'=>'5','MX'=>'5','NA'=>'5','ZA'=>'5','NR'=>'5','NP'=>'5','NI'=>'6','NE'=>'','NG'=>'5','NO'=>'8','PW'=>'5','PT'=>'3',
				'JP'=>'1','SE'=>'8','CH'=>'3','SV'=>'6','RS'=>'5','SL'=>'5','SN'=>'5','CY'=>'4','SC'=>'5','SA'=>'4','LK'=>'4','SI'=>'4','SD'=>'5','SR'=>'5','SB'=>'5','TH'=>'2',
				'TZ'=>'5','TO'=>'5','TT'=>'6','TN'=>'5','TV'=>'5','TR'=>'4','TM'=>'4','VU'=>'5','GT'=>'6','VE'=>'5','BN'=>'5','UG'=>'5','UA'=>'4','UY'=>'6','UZ'=>'4','ES'=>'4',
				'GR'=>'3','SG'=>'2','NC'=>'5','NZ'=>'4','HU'=>'8','SY'=>'4','JM'=>'6','IQ'=>'5','IR'=>'5','IL'=>'8','IT'=>'3','IN'=>'2','ID'=>'2','GB'=>'8','VG'=>'6','JO'=>'5',
				'VN'=>'4','ZM'=>'5','ZR'=>'6','TD'=>'5','GI'=>'5','CL'=>'6','CF'=>'5','SZ'=>'5','PM'=>'','YE'=>'5','FO'=>'5','SM'=>'5','KN'=>'5','LC'=>'6','AS'=>'5','CK'=>'5',
				'SRB'=>'5','MNE'=>'5','MW'=>'','ASC'=>'','CC'=>'','CX'=>'','EH'=>'','ER'=>'','FM'=>'','GG'=>'','JU'=>'','KM'=>'','MH'=>'','MP'=>'','MS'=>'','NF'=>'','NU'=>'',
				'SGS'=>'','SH'=>'','SJ'=>'','SS'=>'','ST'=>'','TC'=>'','TK'=>'','TL'=>'','UK'=>'','UM'=>'','VC'=>'','WF'=>'','WS'=>'','XM'=>'','XN'=>'','CRO'=>'8','CS'=>'3',
				'KT'=>'5','MDV'=>'5',);
		
		if(isset($countryArr[$country_code])){
			return $countryArr[$country_code];
		}else{
			return '';
		}
	}
	
	/**
	 * 获取国际E邮宝的分拣码
	 * 
	 * @param $country_code		国家简码
	 * @param $postal_code		邮编
	 * @param $shipping_method_code	业务类型
	 * @return string
	 */
	public static function getEubSortingYardsByCountry($country_code, $postal_code, $shipping_method_code = 0){
		$sorting_yards = '';
		
		if($postal_code == '')
			return $sorting_yards;
		
		$result_select_code = LB_IEUBNewCarrierAPI::getCountrySelectCode($shipping_method_code, $postal_code, $country_code);
// 		$result_select_code['error'] = true;
		
		if($result_select_code['error'] == false){
			$sorting_yards = $result_select_code['codenum'];
		}else{
// 			throw new \Exception('获取国家分拣码失败,请稍后再试或者联系小老板客服:'.$result_select_code['msg']);
		}
		
		return $sorting_yards;
		
		//下面代码不再执行,因为已经执行新的标准
		//邮编为美国的
		if($country_code == 'US'){
			$tmp_postal = substr($postal_code,0,3);
			
			if(!is_numeric($tmp_postal)){
				return $sorting_yards;
			}
			
			$tmp_postal = (int)$tmp_postal;
			
			if(($tmp_postal>=0 && $tmp_postal<=69) || ($tmp_postal>=74 && $tmp_postal<=78) || ($tmp_postal>=80 && $tmp_postal<=87) || ($tmp_postal>=90 && $tmp_postal<=99)
			|| ($tmp_postal>=105 && $tmp_postal<=109) || ($tmp_postal == 115) || ($tmp_postal>=117 && $tmp_postal<=299)){
				$sorting_yards = '1F';
			}else
			if(($tmp_postal == 103) || ($tmp_postal>=110 && $tmp_postal<=114) || ($tmp_postal == 116)){
				$sorting_yards = '1P';
			}else
			if(($tmp_postal>=70 && $tmp_postal<=73) || ($tmp_postal == 79) || ($tmp_postal>=88 && $tmp_postal<=89)){
				$sorting_yards = '1Q';
			}else
			if(($tmp_postal>=100 && $tmp_postal<=102) || ($tmp_postal == 104)){
				$sorting_yards = '1R';
			}else
			if(($tmp_postal>=400 && $tmp_postal<=433) || ($tmp_postal>=437 && $tmp_postal<=439) || ($tmp_postal>=450 && $tmp_postal<=459) || ($tmp_postal>=470 && $tmp_postal<=471)
			|| ($tmp_postal>=475 && $tmp_postal<=477) || ($tmp_postal == 480) || ($tmp_postal>=483 && $tmp_postal<=485) || ($tmp_postal>=490 && $tmp_postal<=491)
			|| ($tmp_postal>=493 && $tmp_postal<=497) || ($tmp_postal>=500 && $tmp_postal<=529) || ($tmp_postal == 533) || ($tmp_postal == 536) || ($tmp_postal == 540)
			|| ($tmp_postal>=546 && $tmp_postal<=548) || ($tmp_postal>=550 && $tmp_postal<=609) || ($tmp_postal == 612) || ($tmp_postal>=617 && $tmp_postal<=619)
			|| ($tmp_postal == 621) || ($tmp_postal == 624) || ($tmp_postal == 632) || ($tmp_postal == 635) || ($tmp_postal>=640 && $tmp_postal<=699)
			|| ($tmp_postal>=740 && $tmp_postal<=758) || ($tmp_postal>=760 && $tmp_postal<=772) || ($tmp_postal>=785 && $tmp_postal<=787) || ($tmp_postal>=789 && $tmp_postal<=799)){
				$sorting_yards = '3F';
			}else
			if(($tmp_postal>=460 && $tmp_postal<=469) || ($tmp_postal>=472 && $tmp_postal<=474) || ($tmp_postal>=478 && $tmp_postal<=479)){
				$sorting_yards = '3P';
			}else
			if(($tmp_postal>=498 && $tmp_postal<=499) || ($tmp_postal>=530 && $tmp_postal<=532) || ($tmp_postal>=534 && $tmp_postal<=535) || ($tmp_postal>=537 && $tmp_postal<=539)
			|| ($tmp_postal>=541 && $tmp_postal<=545) || ($tmp_postal == 549) || ($tmp_postal>=610 && $tmp_postal<=611)){
				$sorting_yards = '3Q';
			}else
			if(($tmp_postal == 759) || ($tmp_postal>=773 && $tmp_postal<=778)){
				$sorting_yards = '3R';
			}else
			if(($tmp_postal>=613 && $tmp_postal<=616) || ($tmp_postal == 620) || ($tmp_postal>=622 && $tmp_postal<=623) || ($tmp_postal>=625 && $tmp_postal<=631)
			|| ($tmp_postal>=633 && $tmp_postal<=634) || ($tmp_postal>=636 && $tmp_postal<=639)){
				$sorting_yards = '3U';
			}else
			if(($tmp_postal>=434 && $tmp_postal<=436) || ($tmp_postal>=481 && $tmp_postal<=482) || ($tmp_postal>=486 && $tmp_postal<=489) || ($tmp_postal == 492)){
				$sorting_yards = '3C';
			}else
			if(($tmp_postal>=779 && $tmp_postal<=784) || ($tmp_postal == 788)){
				$sorting_yards = '3D';
			}else
			if(($tmp_postal>=440 && $tmp_postal<=449)){
				$sorting_yards = '3H';
			}else
			if(($tmp_postal>=813 && $tmp_postal<=849) || ($tmp_postal == 854) || ($tmp_postal>=856 && $tmp_postal<=858) || ($tmp_postal>=861 && $tmp_postal<=862)
			|| ($tmp_postal>=864 && $tmp_postal<=899) || ($tmp_postal == 906) || ($tmp_postal>=909 && $tmp_postal<=918) || ($tmp_postal>=926 && $tmp_postal<=939)){
				$sorting_yards = '4F';
			}if(($tmp_postal>=900 && $tmp_postal<=905) || ($tmp_postal>=907 && $tmp_postal<=908)){
				$sorting_yards = '4P';
			}else
			if(($tmp_postal>=850 && $tmp_postal<=853) || ($tmp_postal == 855) || ($tmp_postal>=859 && $tmp_postal<=860) || ($tmp_postal == 863)){
				$sorting_yards = '4Q';
			}else
			if(($tmp_postal>=919 && $tmp_postal<=921)){
				$sorting_yards = '4R';
			}else
			if(($tmp_postal>=922 && $tmp_postal<=925)){
				$sorting_yards = '4U';
			}else
			if(($tmp_postal == 942) || ($tmp_postal>=950 && $tmp_postal<=953) || ($tmp_postal>=956 && $tmp_postal<=979) || ($tmp_postal>=986 && $tmp_postal<=999)){
				$sorting_yards = '2F';
			}else
			if(($tmp_postal>=980 && $tmp_postal<=985)){
				$sorting_yards = '2P';
			}else
			if(($tmp_postal>=800 && $tmp_postal<=812)){
				$sorting_yards = '2Q';
			}else
			if(($tmp_postal>=945 && $tmp_postal<=948)){
				$sorting_yards = '2R';
			}else
			if(($tmp_postal>=940 && $tmp_postal<=941) || ($tmp_postal>=943 && $tmp_postal<=944) || ($tmp_postal == 949) || ($tmp_postal>=954 && $tmp_postal<=955)){
				$sorting_yards = '2U';
			}else
			if(($tmp_postal>=300 && $tmp_postal<=320) || ($tmp_postal>=322 && $tmp_postal<=326) || ($tmp_postal>=334 && $tmp_postal<=339)
			|| ($tmp_postal>=341 && $tmp_postal<=346) || ($tmp_postal>=348 && $tmp_postal<=399) || ($tmp_postal>=700 && $tmp_postal<=739)){
				$sorting_yards = '5F';
			}else
			if(($tmp_postal>=330 && $tmp_postal<=333) || ($tmp_postal == 340)){
				$sorting_yards = '5P';
			}else
			if(($tmp_postal == 321) || ($tmp_postal>=327 && $tmp_postal<=329) || ($tmp_postal == 347)){
				$sorting_yards = '5Q';
			}
			
			return $sorting_yards;
		}else if($country_code == 'RU'){
			//俄罗斯
			
			$tmp_postal = substr($postal_code,0,3);
				
			if(!is_numeric($tmp_postal)){
				return $sorting_yards;
			}
			
			$tmp_postal = (int)$tmp_postal;
			
			if(($tmp_postal >= 101 && $tmp_postal <= 157) || ($tmp_postal >= 170 && $tmp_postal <= 172) || ($tmp_postal >= 210 && $tmp_postal <= 309)
				|| ($tmp_postal >= 346 && $tmp_postal <= 347) || ($tmp_postal >= 352 && $tmp_postal <= 359) || ($tmp_postal >= 390 && $tmp_postal <= 391)
				|| ($tmp_postal == 629) || ($tmp_postal == 689)){
				$sorting_yards = 1;
			}else
			if(($tmp_postal == 630) || ($tmp_postal >= 632 && $tmp_postal <= 634) || ($tmp_postal == 636) || ($tmp_postal >= 640 && $tmp_postal <= 641)
				|| ($tmp_postal == 644) || ($tmp_postal >= 646 && $tmp_postal <= 649) || ($tmp_postal == 650) || ($tmp_postal == 651)
				|| ($tmp_postal >= 652 && $tmp_postal <= 656) || ($tmp_postal >= 658 && $tmp_postal <= 660) || ($tmp_postal >= 662 && $tmp_postal <= 688)
				|| ($tmp_postal == 690) || ($tmp_postal >= 692 && $tmp_postal <= 694)){
				$sorting_yards = 2;
			}else
			if(($tmp_postal >= 160 && $tmp_postal <= 169) || ($tmp_postal >= 173 && $tmp_postal <= 175) || ($tmp_postal >= 180 && $tmp_postal <= 188)
				|| ($tmp_postal >= 190 && $tmp_postal <= 199)){
				$sorting_yards = 3;
			}else
			if(($tmp_postal == 344) || ($tmp_postal == 350) || ($tmp_postal >= 360 && $tmp_postal <= 364) || ($tmp_postal >= 366 && $tmp_postal <= 369)
				|| ($tmp_postal >= 370 && $tmp_postal <= 384) || ($tmp_postal >= 385 && $tmp_postal <= 386) || ($tmp_postal >= 392 && $tmp_postal <= 393)
				|| ($tmp_postal >= 394 && $tmp_postal <= 399) || ($tmp_postal >= 400 && $tmp_postal <= 401) || ($tmp_postal >= 403 && $tmp_postal <= 405)
				|| ($tmp_postal >= 406 && $tmp_postal <= 409) || ($tmp_postal == 410) || ($tmp_postal >= 412 && $tmp_postal <= 414) || ($tmp_postal == 416)
				|| ($tmp_postal >= 420 && $tmp_postal <= 423) || ($tmp_postal == 424) || ($tmp_postal >= 425 && $tmp_postal <= 433) || ($tmp_postal == 440)
				|| ($tmp_postal >= 442 && $tmp_postal <= 446) || ($tmp_postal == 450) || ($tmp_postal >= 452 && $tmp_postal <= 457)
				|| ($tmp_postal >= 460 && $tmp_postal <= 462) || ($tmp_postal == 610) || ($tmp_postal >= 612 && $tmp_postal <= 614)
				|| ($tmp_postal >= 617 && $tmp_postal <= 620) || ($tmp_postal >= 622 && $tmp_postal <= 624) || ($tmp_postal >= 626 && $tmp_postal <= 628)){
				$sorting_yards = 4;
			}
			
			return $sorting_yards;
		}else if($country_code == 'CA'){
			//加拿大
			
			$tmp_postal = substr($postal_code,0,1);
			
			if((preg_match('/^[A-R]+$/', $tmp_postal)) || (preg_match('/^[a-r]+$/', $tmp_postal))){
				$sorting_yards = 1;
			}else
			if((preg_match('/^[S-Z]+$/', $tmp_postal)) || (preg_match('/^[s-z]+$/', $tmp_postal))){
				$sorting_yards = 2;
			}
			
			return $sorting_yards;
		}else if($country_code == 'AU'){
			//澳大利亚
			
			$tmp_postal = substr($postal_code,0,1);
			
			if(in_array($tmp_postal, array(1,2,4,9))){
				$sorting_yards = 1;
			}else 
			if(in_array($tmp_postal, array(3,5,6,7,8))){
				$sorting_yards = 2;
			}
			
			return $sorting_yards;
		}
		
		//最终默认返回空字符
		return $sorting_yards;
	}
	
	//获取通用配货单 (新)  不要再查数据库
	public static function getItemsLablePubNotFind($tmpX, $tmpY , &$pdf, $sumParams, $format, $lableCount, $otherParams){
		$order = $sumParams['order'];
	
		//获取订单详情列表信息
		$itemListDetailInfo = $sumParams['itemListDetailInfo'];
		$tmp_TrackingNumber = $sumParams['tracking_number'];
	
		$width = 0;
		if(($format == 'A4') && ($lableCount == 3)){
			$width = 110;
		}
	
		$style = array(
				'position' => '',
				'align' => 'C',
				'stretch' => false,
				'fitwidth' => true,
				'cellfitalign' => '',
				'border' => true,
				'hpadding' => 'auto',
				'vpadding' => 'auto',
				'fgcolor' => array(0,0,0),
				'bgcolor' => false, //array(255,255,255),
				'text' => true,
				'font' => 'helvetica',
				'fontsize' => 8,
				'stretchtext' => 4
		);
	
		//订单详细Items信息
		$products = $itemListDetailInfo['products'];
	
		//设置字体7号
		$pdf->SetFont($otherParams['pdfFont'], '', 7);
	
		//left边线条
		$pdf->Line(2+$tmpX, 2+$tmpY, 2+$tmpX, 25.5+$tmpY, $style=array('width' => 0.5, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
		//top边线条
		$pdf->Line(2+$tmpX, 2+$tmpY, 98+$tmpX+$width, 2+$tmpY, $style=array('width' => 0.5, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
		//right边线条
		$pdf->Line(98+$tmpX+$width, 2+$tmpY, 98+$tmpX+$width, 25.5+$tmpY, $style=array('width' => 0.5, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
		//bottom边线条
		$pdf->Line(2+$tmpX, 20.5+$tmpY, 98+$tmpX+$width, 20.5+$tmpY, $style=array('width' => 0.5, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
	
		//输出字:订单号
		$pdf->SetFont($otherParams['pdfFont'], '', 11);
		$pdf->writeHTMLCell(0, 0, 2+$tmpX, 8+$tmpY, '订单号:', 0, 1, 0, true, '', true);
	
		//条码的样式
		$style = array(
				'position' => '',
				'align' => 'C',
				'stretch' => false,
				'fitwidth' => true,
				'cellfitalign' => '',
				'border' => false,
				'hpadding' => 'auto',
				'vpadding' => 'auto',
				'fgcolor' => array(0,0,0),
				'bgcolor' => false, //array(255,255,255),
				'text' => true,
				'font' => 'helvetica',
				'fontsize' => 8,
				'stretchtext' => 0
		);
	
		//输出小老板订单号条码
		$pdf->write1DBarcode((int)$order['order_id'], 'C128A', 13+$tmpX, 3+$tmpY, '', 18, 0.36, $style, 'N');
	
		//输出订单备注
		$pdf->SetFont($otherParams['pdfFont'], '', 11);
		$pdf->writeHTMLCell(47+$width, 0, 52+$tmpX, 2+$tmpY, '备注：'.$order['desc'], 0, 1, 0, true, '', true);
	
		//输出跟踪号
		$pdf->SetFont($otherParams['pdfFont'], '', 8);
		if(!empty($order['customer_number'])){
			$pdf->writeHTMLCell(47+$width, 0, 52+$tmpX, 16+$tmpY, '跟踪号:'.$tmp_TrackingNumber , 0, 1, 0, true, '', true);
		}
	
		//订单号条码下边第二条线条
		$pdf->Line(2+$tmpX, 25.5+$tmpY, 98+$tmpX+$width, 25.5+$tmpY, $style=array('width' => 0.5, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
	
		//输出平台订单号
		$pdf->SetFont($otherParams['pdfFont'], '', 9);
		$pdf->writeHTMLCell(0, 0, 2+$tmpX, 21+$tmpY, '平台订单号：'.$order['order_source_order_id'], 0, 1, 0, true, '', true);
	
		//记录输出到第几个Item
		$tmpInt = 0;
	
		//记录假如多个Item时下一页需要重新计数
		$dynamicHight = 0;
	
		foreach ($products as $productKey => $product){
			//假如是热敏纸时每个热敏纸的Item个数有限制
			if(($format != 'A4') && ($productKey != 0)){
				if((($productKey <= 5) && (($productKey % 5) == 0)) || (($productKey > 6) && (($productKey % 6) == 0))){
					$pdf->AddPage();
					$tmpInt=0;
	
					$dynamicHight = 24.5 + (24.5 * $tmpInt);
				}
			}
	
			//假如是A4纸并且是3种面单都打印需要加Item个数限制分页
			if(($format == 'A4') && ($productKey != 0)){
				if($lableCount == 3){
					if((($productKey <= 12) && (($productKey % 12) == 0)) || (($productKey > 11) && ((($productKey-11) % 22) == 0))){
						$pdf->AddPage();
						$tmpInt=0;
	
						$dynamicHight = 120 + (120 * $tmpInt);
					}
				}else if($lableCount == 2){
					if((($productKey <= 18) && (($productKey % 18) == 0)) ){
						$pdf->AddPage();
						$tmpInt=0;
							
						$dynamicHight = 25 + (25 * $tmpInt);
					}
				}
			}
	
			//动态创建左边线
			$pdf->Line(2+$tmpX, 25.5+$tmpY+($tmpInt*13.5)-$dynamicHight, 2+$tmpX, 39+$tmpY+($tmpInt*13.5)-$dynamicHight, $style=array('width' => 0.5, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
			//动态创建右边线
			$pdf->Line(98+$tmpX+$width, 25.5+$tmpY+($tmpInt*13.5)-$dynamicHight, 98+$tmpX+$width, 39+$tmpY+($tmpInt*13.5)-$dynamicHight, $style=array('width' => 0.5, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
			//动态创建下边线
			$pdf->Line(2+$tmpX, 39+$tmpY+($tmpInt*13.5)-$dynamicHight, 98+$tmpX+$width, 39+$tmpY+($tmpInt*13.5)-$dynamicHight, $style=array('width' => 0.5, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0)));
	
			//假如没有图片的话默认显示No Photo
			if(empty($product['PICTURE'])){
				$product['PICTURE'] = '/images/batchImagesUploader/no-img.png';
			}
	
			//lazada图片返回双//
			if(substr($product['PICTURE'], 0, 2 ) == '//'){
				$product['PICTURE'] = 'http:'.$product['PICTURE'];
			}
	
			// 			$product['PICTURE'] = '/attachment/tmp_img_tcpdf/20161025/2.jpg';
			//这里需要先判断图片是否是图片流文件不然会生成图片导致程序崩溃
			if(substr($product['PICTURE'], 0, 1 ) == '/'){
				// 				$product['PICTURE'] = Yii::$app->request->hostinfo.$product['PICTURE'];
	
				$product['PICTURE'] = dirname(dirname(dirname(__DIR__))).'/web'.$product['PICTURE'];
			}
	
			$tmpGetimagesize = false;
			try{
				$tmpGetimagesize = getimagesize($product['PICTURE']);
			}catch (\Exception $ex){
			}
	
			if($tmpGetimagesize == false){
				$product['PICTURE'] = '/images/batchImagesUploader/no-img.png';
			}
	
			//显示items图片
			if(!empty($product['PICTURE'])){
				$pdf->writeHTMLCell(0, 0, 2+$tmpX, 26+$tmpY+($tmpInt*13.5)-$dynamicHight, '<img src="'.$product['PICTURE'].'"  width="35" height="35" >', 0, 0, 0, true, '', true);
			}
	
			$tmpAttributeStr = '';
	
			//速卖通的属性值
			if($order['order_source'] == 'aliexpress'){
				$tmpProdctAttrbutes = explode(' + ' ,$product['PROPERTY'] );
				if (!empty($tmpProdctAttrbutes)){
					foreach($tmpProdctAttrbutes as $_tmpAttr){
						$tmpAttributeStr .= $_tmpAttr;
					}
				}
			}
	
			//输出配货信息
			$pdf->SetFont($otherParams['pdfFont'], '', 8);
			$pdf->writeHTMLCell(60+$width, 0, 16+$tmpX, 26+$tmpY+($tmpInt*13.5)-$dynamicHight, $product['NAME_CN'].' '.$tmpAttributeStr.'<br>sku:'.$product['SKU'], 0, 0, 0, true, '', true);
	
			//输出货架位
			$pdf->SetFont($otherParams['pdfFont'], '', 8);
			$pdf->writeHTMLCell(25, 0, 74+$tmpX+$width, 26+$tmpY+($tmpInt*13.5)-$dynamicHight, '货架位：'.$product['GRID_CODE'], 0, 0, 0, true, '', true);
	
			//输出数量
			$pdf->SetFont($otherParams['pdfFont'], '', 12);
			$pdf->writeHTMLCell(30, 0, 74+$tmpX+$width, 32+$tmpY+($tmpInt*13.5)-$dynamicHight, '数量：'.$product['QUANTITY'], 0, 0, 0, true, '', true);
	
			$tmpInt++;
		}
	
	}
	
	//用户自定义打印拣货单功能
	public static function getThermalPickingFormatPDF($orderlist){
		$result = array('error'=>false, 'msg'=>'');
		
		//获取用户设置的内容
		$use_scan_picking_format = ConfigHelper::getConfig('use_scan_picking_format');
		
		if(!empty($use_scan_picking_format)){
			$tmpTemplateLabel = CrTemplate::find()->select(['template_height','template_width','template_content_json'])->where(['template_id'=>$use_scan_picking_format])->asArray()->one();
		}
		
		//假如没有设置过指定模板，则获取系统模板
		if(empty($tmpTemplateLabel)){
			$tmpTemplateLabel = CrCarrierTemplate::find()->select(['template_height','template_width','template_content_json'])->where(['is_use'=>1, 'template_name'=>'通用配货单', 'template_version'=>1])->asArray()->one();
		}
		
		$print_json_str = $tmpTemplateLabel['template_content_json'];
		$custom_format_label = array($tmpTemplateLabel['template_width'], $tmpTemplateLabel['template_height']);
		
		//额外的参数, 例如可以记录加打的内容
		$otherParams = array();
	
		$otherParams['pdfFont'] = 'msyh';

		//控制是否用预览数据作演示
		$otherParams['print_json'] = '';
		
		//判断是否存在订单
		$orderRecordNum = count($orderlist);
		if($orderRecordNum == 0){
			$result['error'] = true;
			$result['msg'] = '请传入订单';
				
			return $result;
		}
	
		//设置打印间隔属性
		$pdfAttribute = array('x0'=>0, 'y0'=>0, 'x1'=>105, 'y1'=>0, 'x2'=>0, 'y2'=>99, 'x3'=>105, 'y3'=>99, 'x4'=>0, 'y4'=>198, 'x5'=>105 ,'y5'=>198);
	
		//定义打印到的位置
		$int1 = 0;
	
		//记录第几张订单
		$intOrderCount = 1;
	
		
		$otherParams['format_label'] = $custom_format_label;
		//获取订单对应的运输服务设置
		$shippingServece_obj = SysShippingService::findOne(['id'=>$orderlist[0]['default_shipping_method_code']]);
		if(empty($shippingServece_obj)){
			$result['error'] = true;
			$result['msg'] = "请先选择运输服务.";
			return $result;
		}
	
		//获取物流账号别名
// 		$carrierAccount = \eagle\modules\carrier\models\SysCarrierAccount::findOne(['id'=>$shippingServece_obj['carrier_account_id']]);
// 		if(!empty($carrierAccount)){
// 			$otherParams['carrier_name'] = $carrierAccount['carrier_name'];
// 		}
		
// 		$format = array(100, 100);	//20170111
		$format = $otherParams['format_label'];

		//记录原始面单的大小
		$format_label = $custom_format_label;
		
		//需要打印的格式/方法
		$print_params = array();
		$print_params['items_lable'] = $print_json_str;
		
// 		if($print_json_str != ''){
// 			$print_params['carrier_lable'] = $print_json_str;
// 		}
		
		if(empty($print_params)){
			$result['error'] = true;
			$result['msg'] = "请先设置需要打印的面单类型";
			return $result;
		}
		
		//新建
		$pdf = new \TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, $format, true, 'UTF-8', false);
	
		//设置页边距
		$pdf->SetMargins(0, 0, 0);
	
		//删除预定义的打印 页眉/页尾
		$pdf->setPrintHeader(false);
		$pdf->setPrintFooter(false);
	
		//设置不自动换页,和底部间距为0
		$pdf->SetAutoPageBreak(false, 0);
	
		//设置字体行距
		//$pdf->setCellHeightRatio(1.1);
	
		//循环订单
		foreach ($orderlist as $orderKey => $order){
			//10*10 纸时需要每一张面单就新建一页
			foreach ($print_params as $printParamsKey => $printParamsVal){
				$pdf->AddPage();
				$int1 = 0;
				
				$otherParams['template_content_json'] = $printParamsVal;
				$otherParams['format_label'] = $format_label;
				$otherParams['format_type'] = $printParamsKey;
				self::getCarrierTemplatePDF($pdfAttribute['x'.$int1], $pdfAttribute['y'.$int1], $pdf, $order, $shippingServece_obj, $format, count($print_params), $otherParams);

				$int1++;
			}
		}
		
		if($result['error'] == false){
			$pdf->Output('print.pdf', 'I');
		}else{
			return $result;
		}
	}
	
}
