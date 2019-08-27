<?php
/**
 * @link http://www.witsion.com/
 * @copyright Copyright (c) 2014 Yii Software LLC
 * @license http://www.witsion.com/
 */
namespace eagle\modules\carrier\helpers;
use common\api\overseaWarehouseAPI\BaseOverseaWarehouseAPI;
use eagle\modules\order\models\OdOrderShipped;
use eagle\modules\carrier\models\SysShippingService;
use eagle\modules\carrier\models\SysCarrierAccount;
use yii;
use eagle\modules\carrier\helpers\CarrierException;
use eagle\modules\carrier\controllers\CarrieroperateController;
use eagle\modules\carrier\apihelpers\PrintApiHelper;
use eagle\models\CrCarrierTemplate;
use eagle\models\CarrierUserLabel;
use eagle\modules\order\models\OdOrder;
use common\helpers\Helper_Curl;
use eagle\modules\util\helpers\PDFMergeHelper;
use eagle\modules\carrier\apihelpers\PrintPdfHelper;
use eagle\modules\util\helpers\TimeUtil;
use Qiniu\json_decode;
use eagle\modules\carrier\apihelpers\PDFQueueHelper;
use eagle\modules\util\helpers\MailHelper;
use common\api\carrierAPI\LB_ALIONLINEDELIVERYCarrierAPI;

/**
 * CarrierHelper.
 *
 */
class CarrierAPIHelper {
	//将pdf文件保存到本地
	public static function savePDF($data,$puid,$carrier_code,$is_url=1,$type='pdf') {
		$basepath = Yii::getAlias('@webroot');
		//如果是正确信息, 保存pdf, 并返回包括URL的相关参数
		$filename = $puid.'_'.$carrier_code.rand(1000,9999).'.'.$type;
		if(!file_exists($basepath.'/tmp_api_pdf'))mkdir($basepath.'/tmp_api_pdf');
		//文件保存物理路径
		$file = $basepath.'/tmp_api_pdf/'.$filename;
		//URL访问路径
		$url = Yii::$app->request->hostinfo.'/tmp_api_pdf/'.$filename;
		if(file_put_contents ( $file,$data)){
			if($is_url)return $url;
			else return ['pdfUrl'=>$url,'filePath'=>$file];
		}
		return false;
	}

	/*
	 * 物流商API请求前验证
	 * @user 是否验证用户登陆信息
	 * @checkShipped  0 如果订单已存在 则报错 1 订单不存在 则报错
	 * $extra_id	强制重复发货标志
	 */
	public static function validate($user=0,$checkShipped=0,$order='',$extra_id = '',$extra_customer_number = ''){
		$puid = '';
		$shipped = null;
		//验证账户 返回puid
		if($user){
			$user=\Yii::$app->user->identity;
			if(empty($user))throw new CarrierException('用户登陆信息缺失,请重新登陆');
			$puid = $user->getParentUid();
		}
		#############################################################
		//如果用户已经上传过订单 则不需要再次上传
		if($order=='')throw new CarrierException('内部错误:请传递订单数据');
		$order_id = is_array($order)?$order['order_id']:$order->order_id;
		//物流商返回的跟踪单号
		$customer_number = is_array($order)?$order['customer_number']:$order->customer_number;
		if ($checkShipped){
			if (strlen($customer_number)==0){
				throw new CarrierException('请检查订单是否上传');
			}else{
				$shipped = OdOrderShipped::find()->where(['order_id'=>$order_id,'customer_number'=>$customer_number])->one();
			}
		}else{
			//屏蔽限制，可重复上传
			 if (strlen($customer_number)>0){
			 	if((!empty($extra_customer_number))){
			 		$shipped = OdOrderShipped::find()->where(['order_id'=>$order_id,'customer_number'=>$extra_customer_number])->one();
			 	}else{
			 		$shipped = OdOrderShipped::find()->where(['order_id'=>$order_id,'customer_number'=>$customer_number])->one();
			 	}
			 	
				$str = empty($shipped->tracking_number)?'':'<br/>跟踪号：'.$shipped->tracking_number;
				if(!$checkShipped && $shipped){//如果不需要shipped 却有发货信息 则报错
					throw new CarrierException('订单已上传,请勿重复上传'.$str);
				}
			} 
		}
		#############################################################
		//验证通过 返回shipped对象和puid
		//这个写法有问题，待改造，不应该调用基类，而且调用海外仓业务基类意图不明
		return BaseOverseaWarehouseAPI::getResult(0,['shipped'=>$shipped,'puid'=>$puid],'');
	}


	/**
	 * 通过传入订单信息 查询出订单相关需要的数据
	 * @params $order 订单类
	 * 
	 * @return
	 * service
	 * account
	 * senderAddressInfo => Array
		(
			//发货地址
		    [shippingfrom] => Array	
		        (
		            [contact] => 李四				//联系人
		            [contact_en] => lisi			//联系人(英文)
		            [company] => 					//公司
		            [company_en] => huishi			//公司(英文)
		            [phone] => 159872552			//电话
		            [mobile] => 1535862251			//手机
		            [fax] => 020-2548896			//传真
		            [email] => 591236@qq.com		//邮箱
		            [country] => CN					//国家
		            [province] => 110000			//州/省
		            [province_en] => ANHUI			//州/省(英文)
		            [city] => 中山市					//市
		            [city_en] => zhongshanshi		//市(英文)
		            [district] => 中山市				//区/县/镇
		            [district_en] => xiaolanzhen	//区/县/镇(英文)
		            [postcode] => 528415			//邮编
		            [street] => 体育馆				//街道
		            [street_en] => 体育馆			//街道(英文)
		        )
		    //揽收地址
		    [pickupaddress] => Array
		        (
		            [contact] => 李四				//联系人
		            [company] => 惠氏				//公司
		            [mobile] => 					//手机
		            [phone] => 159872552			//电话
		            [email] => 						//邮箱
		            [fax] => 020-2548896			//传真
		            [country] => CN					//国家
		            [province] => 110000			//州/省
		            [city] => 110100				//市
		            [district] => 110101			//区/县/镇
		            [street] => dd					//街道
		            [postcode] => 体育馆				//邮编
		        )
		    //退货地址
		    [returnaddress] => Array
		        (
		            [contact] => fdf				//联系人
		            [company] => 					//公司
		            [mobile] =>  					//手机
		            [phone] => 						//电话
		            [email] =>  					//邮箱
		            [fax] => 						//传真
		            [country] => CN					//国家
		            [province] => ANHUI				//州/省
		            [city] => ddd					//市
		            [district] => dd				//区/县/镇
		            [street] => dd					//街道
		            [postcode] => dd				//邮编
		        )
		)
	 */
	public static function getAllInfo($order,$getaccount = 1){
		$Account = [];
		$addressInfo = [];
		if(!is_object($order))throw new CarrierException('小老板内部错误,请传入对象');
		//运输服务表内容
		$service = SysShippingService::find()->where(['id'=>$order->default_shipping_method_code,'is_used'=>1,'is_del'=>0])->one();
		if($service==null)throw new CarrierException('请检查该订单运输服务是否已开启或已删除');
		if($getaccount){
			//用户账户表
			$Account = SysCarrierAccount::find()->where(['id'=>$service->carrier_account_id,'is_used'=>1,'is_del'=>0])->one();
			if($Account == null) throw new CarrierException('请检查该物流商帐号是否已启用或已删除');
			
			$addressInfo = CarrierOpenHelper::getCarrierAccountAddressInfoByAddressId($service->carrier_code, $service->common_address_id);
		}
		return [
			'service'=>$service,
			'account'=>$Account,
			'senderAddressInfo'=>$addressInfo,
		];
	}
	
	/*
	 * 获取到客户代码
	 */
	public static function getCustomerNum($order,$code){
		// if ($order->order_source == 'ebay'){
		// 	if ($order->order_source_srn>0){
		// 		$customer_number = 'LB'.date('ymd',time()).$order->order_source_srn.$code;
		// 	}else{
		// 		$customer_number = 'LB'.date('ymd',time()).$order->order_id.$code;
		// 	}
		// }else{
		// 	$customer_number = date('ymd',time()).$order->order_id.$code;
		// }
		//使用新的客户号，暂时先试用，如有问题在修改
		switch($order->order_source){
			case 'ebay':
				if ($order->order_source_srn>0){
					$customer_number = 'LB'.date('ymd',time()).$order->order_source_srn.$code;
				}else{
					$customer_number = 'LB'.str_pad($order->order_id, 10,0,STR_PAD_LEFT).$code;
				}
			break;
			case 'wish':
				$customer_number = 'LB'.date('ymd',time()).$order->order_id.$code;
			break;
			case 'lazada':
				$customer_number = 'LB'.str_pad($order->order_source_order_id, 10,0,STR_PAD_LEFT).$code;
			break;
			default:
				$customer_number = $order->order_source_order_id.$code;
			break;
		}
		return $customer_number;
	}
	/*
	 * 获取客户参考号2.1
	 * million
	*/
	public static function getCustomerNum2($order){
// 		$tmpXHX = '_';
		$tmpXHX = '';
		
		//获取运输服务，
		$r = CarrierOpenHelper::getCustomCarrierShippingServiceUserById($order->default_shipping_method_code, $order->default_carrier_code);
		if ($r['response']['code'] == 1){
			return '';
		}
		$service = $r['response']['data'];
		//对应平台是否有设置客户参考号规则
		if (isset($service['customer_number_config'][$order->order_source]['val'])){
			$type = $service['customer_number_config'][$order->order_source]['val'];
		}else{
			$config = CarrierOpenHelper::getCommonCarrierConfig();
			if (isset($config['customer_number_config'][$order->order_source])){
				$type = $config['customer_number_config'][$order->order_source];
			}else{
				$type = 'platform_id';
			}
		}
		$count = count($order->trackinfos)>0?count($order->trackinfos)+1:'';
		if (strlen($count)){
			$count = $tmpXHX.'U'.$count;
		}
		
		if(in_array($order->order_relation, array('fs','ss'))){
			$count .= 'S'.rand(10, 99);
		}
		
		switch($type){
				case 'serial_random_6number'://小老板单号+六位随机数
					$customer_number = $order->order_id.$tmpXHX.rand(100000, 999999).$count;
					break;
				case 'serial_date':
					$customer_number = $order->order_id.$tmpXHX.date("ymd",time()).$count;
					break;
				case 'platform_id':
					$customer_number = $order->order_source=='ebay'?$order->order_source_srn.$count:$order->order_source_order_id.$count;
					break;
				case 'platform_id_random_6number':
					$customer_number = $order->order_source=='ebay'?$order->order_source_srn.$tmpXHX.rand(100000, 999999).$count:$order->order_source_order_id.$tmpXHX.rand(100000, 999999).$count;
					break;
				case 'platform_id_date':
					$customer_number = $order->order_source=='ebay'?$order->order_source_srn.$tmpXHX.date("ymd",time()).$count:$order->order_source_order_id.$tmpXHX.date("ymd",time()).$count;
					break;
				default:
					$customer_number = $order->order_source=='ebay'?$order->order_source_srn.$count:$order->order_source_order_id.$count;
					break;
			}

		//重新发货需要加R
		if($order->reorder_type == 'after_shipment'){
			//判断第几次已出库订单补发
			$tmpSequenceNumber = '';
			
			$tmpSequenceNumberArr = \eagle\modules\order\apihelpers\OrderApiHelper::getReOrderSequenceNumber($order);
			if($tmpSequenceNumberArr['ack'] == true){
				$tmpSequenceNumber = $tmpSequenceNumberArr['data'];
			}
			
			$customer_number .= 'R'.$tmpSequenceNumber;
		}
		
		return $customer_number;
	}
	
	/*
	 * 获取客户参考号2.1c, 前面的代码已经获取号规则，所以这里不用再搜索一次数据库
	* hqw
	*/
	public static function getCustomerNum3($order, $service){
		$tmpXHX = '';
	
		//对应平台是否有设置客户参考号规则
		if (isset($service['customer_number_config'][$order->order_source])){
			$type = $service['customer_number_config'][$order->order_source];
		}else{
			$config = CarrierOpenHelper::getCommonCarrierConfig();
			if (isset($config['customer_number_config'][$order->order_source])){
				$type = $config['customer_number_config'][$order->order_source];
			}else{
				$type = 'platform_id';
			}
		}
		$count = count($order->trackinfos)>0?count($order->trackinfos)+1:'';
		if (strlen($count)){
			$count = $tmpXHX.'U'.$count;
		}
		
		if(in_array($order->order_relation, array('fs','ss'))){
			$count .= 'S'.rand(10, 99);
		}
		
		switch($type){
			case 'serial_random_6number'://小老板单号+六位随机数
				$customer_number = $order->order_id.$tmpXHX.rand(100000, 999999).$count;
				break;
			case 'serial_date':
				$customer_number = $order->order_id.$tmpXHX.date("ymd",time()).$count;
				break;
			case 'platform_id':
				$customer_number = $order->order_source=='ebay'?$order->order_source_srn.$count:$order->order_source_order_id.$count;
				break;
			case 'platform_id_random_6number':
				$customer_number = $order->order_source=='ebay'?$order->order_source_srn.$tmpXHX.rand(100000, 999999).$count:$order->order_source_order_id.$tmpXHX.rand(100000, 999999).$count;
				break;
			case 'platform_id_date':
				$customer_number = $order->order_source=='ebay'?$order->order_source_srn.$tmpXHX.date("ymd",time()).$count:$order->order_source_order_id.$tmpXHX.date("ymd",time()).$count;
				break;
			default:
				$customer_number = $order->order_source=='ebay'?$order->order_source_srn.$count:$order->order_source_order_id.$count;
				break;
		}
		
		//重新发货需要加R
		if($order->reorder_type == 'after_shipment'){
			//判断第几次已出库订单补发
			$tmpSequenceNumber = '';
			
			$tmpSequenceNumberArr = \eagle\modules\order\apihelpers\OrderApiHelper::getReOrderSequenceNumber($order);
			if($tmpSequenceNumberArr['ack'] == true){
				$tmpSequenceNumber = $tmpSequenceNumberArr['data'];
			}
			
			$customer_number .= 'R'.$tmpSequenceNumber;
		}
		
		return $customer_number;
	}

	/*
	 * 上传物流单成功后数据处理
	 */
	public static function orderSuccess($order,$service,$customer_number,$orderNextStatus,$tracking_number = '',$return_no='')
	{
		$service_codeArr = $service->service_code;
		$r= array(
			'order_source'=>$order->order_source,//订单来源
			'selleruserid'=>$order->selleruserid,//卖家账号
			'tracking_number'=>$tracking_number,//物流号（选填）
			'tracking_link'=>$service->web,//查询网址（选填）
			'shipping_method_code'=>isset($service_codeArr[$order->order_source])?$service_codeArr[$order->order_source]:'',//平台物流服务代码
			'shipping_method_name'=>$service->service_name,//平台物流服务名
			'order_source_order_id'=>$order->order_source_order_id,//平台订单号
			'return_no'=>$return_no,//物流系统的订单号（选填）
			'shipping_service_id'=>$order->default_shipping_method_code,//物流服务id（选填）
			'addtype'=>'物流API',//物流号来源
			'signtype'=>'all',//标记类型 all或者part（选填）
			//'description'=>'',//备注（选填）
			'customer_number' => $customer_number,//物流商返回的客户单号物流系统订单唯一标示
		);
		$order->customer_number = $customer_number;
		$order->carrier_step = $orderNextStatus;
		$order->save();
		return $r;
	}
	
	/**
	 * 返回地址信息和电话信息
	 *
	 * @param $orderInfo	订单信息
	 * @param $params Array
	 * Array
		 (
		 	 [consignee_phone_limit] => 30	电话的长度限制	这个只能看接口文档或者问对应的技术，没有长度限制请填10000
			 [address] => Array	该参数是货代的地址限制类型假如货代可以接收3个地址信息这里就有3个参数，假如货代只有2个就只需要填传递两个限制该consignee_address_line3_limit不要设置，
			 					假如只有一个货代只有一个地址参数只需要传consignee_address_line1_limit即可
			 (
				 [consignee_address_line1_limit] => 30	货代支持地址1支持的地址长度
				 [consignee_address_line2_limit] => 30	货代支持地址2支持的地址长度
				 [consignee_address_line3_limit] => 30	货代支持地址3支持的地址长度
			 )
			 [consignee_district] => 1	是否将收件人区也填入地址信息里面	这个只能看接口文档或者问对应的技术，没有长度限制请填10000
			 [consignee_county] => 1	是否将收货人镇也填入地址信息里面	这个只能看接口文档或者问对应的技术，没有长度限制请填10000
			 [consignee_company] => 1	是否将收货公司也填入地址信息里面	这个只能看接口文档或者问对应的技术，没有长度限制请填10000
		 )
	 * @return Array
	 * Array
		(
		    [address_line1] => Johannes-Brahms-str.39 123456789 district county company
		    [address_line2] => address2
		    [address_line3] => address31
		    [phone1] => 1597563212/01573 2288344
		    [phone2] => 1597563212
		)
	 * +-------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		hqw		2016/01/07				初始化
	 * +-------------------------------------------------------------------------------------------
	 */
	public static function getCarrierAddressAndPhoneInfo($order, $params, $link_str = ','){
		if(!isset($params['address'])){
			return ['response'=>['code'=>1, 'msg'=>'调用失败请传入地址信息', 'data'=>array()]];
		}
	
		$addressArr = array();
		$addressQty = count($params['address']);
		$tmpAddress1_1 = '';
		$tmpAddress2_1 = '';
		$tmpAddress2_2 = '';
		$tmpAddress3_1 = '';
		$tmpAddress3_2 = '';
		$tmpAddress3_3 = '';
	
		//默认先将所有地址信息连接起来
		if(!empty($order->consignee_address_line1))
			$tmpAddress1_1 = $order->consignee_address_line1;
		if(!empty($order->consignee_address_line2))
			$tmpAddress1_1 .=$link_str. $order->consignee_address_line2;
		if(!empty($order->consignee_address_line3))
			$tmpAddress1_1 .=$link_str. $order->consignee_address_line3;
		if(!empty($params['consignee_district']) && !empty($order->consignee_district)){
			$tmpAddress1_1 .=' '. $order->consignee_district;
		}
		if(!empty($params['consignee_county']) && !empty($order->consignee_county)){
			$tmpAddress1_1 .=' '. $order->consignee_county;
		}
		if(!empty($params['consignee_company']) && !empty($order->consignee_company)){
			$tmpAddress1_1 .=' '. $order->consignee_company;
		}
	
		if($addressQty > 1){
			if(!empty($order->consignee_address_line1))
				$tmpAddress2_1 = $order->consignee_address_line1;
			if(!empty($order->consignee_address_line2))
				$tmpAddress2_1 .=$link_str. $order->consignee_address_line2;
			if(!empty($order->consignee_address_line3))
				$tmpAddress2_2 = $order->consignee_address_line3;
			if(!empty($params['consignee_district']) && !empty($order->consignee_district)){
				$tmpAddress2_1 .=' '. $order->consignee_district;
			}
			if(!empty($params['consignee_county']) && !empty($order->consignee_county)){
				$tmpAddress2_1 .=' '. $order->consignee_county;
			}
			if(!empty($params['consignee_company']) && !empty($order->consignee_company)){
				$tmpAddress2_1 .=' '. $order->consignee_company;
			}
	
			if(!empty($order->consignee_address_line1))
				$tmpAddress3_1 = $order->consignee_address_line1;
			if(!empty($order->consignee_address_line2))
				$tmpAddress3_2 = $order->consignee_address_line2;
			if(!empty($order->consignee_address_line3))
				$tmpAddress3_2 .=$link_str. $order->consignee_address_line3;
			if(!empty($params['consignee_district']) && !empty($order->consignee_district)){
				$tmpAddress3_1 .=' '. $order->consignee_district;
			}
			if(!empty($params['consignee_county']) && !empty($order->consignee_county)){
				$tmpAddress3_1 .=' '. $order->consignee_county;
			}
			if(!empty($params['consignee_company']) && !empty($order->consignee_company)){
				$tmpAddress3_1 .=' '. $order->consignee_company;
			}
	
			if(!empty($order->consignee_address_line2))
				$tmpAddress3_2_1 = $order->consignee_address_line2;
			if(!empty($order->consignee_address_line3))
				$tmpAddress3_2_2 = $order->consignee_address_line3;
		}
		
		if(!isset($tmpAddress3_2_2)){
			$tmpAddress3_2_2 = '';
		}
	
		switch ($addressQty){
			case 1:
				$addressArr['address_line1'] = $tmpAddress1_1;
				break;
			case 2:
				if(strlen($tmpAddress1_1) <= (int)($params['address']['consignee_address_line1_limit'])){
					$addressArr['address_line1'] = $tmpAddress1_1;
					$addressArr['address_line2'] = '';
				}else{
					if(strlen($tmpAddress2_1) <= (int)($params['address']['consignee_address_line1_limit'])){
						$addressArr['address_line1'] = $tmpAddress2_1;
						$addressArr['address_line2'] = $tmpAddress2_2;
					}else {
						$addressArr['address_line1'] = $tmpAddress3_1;
						$addressArr['address_line2'] = $tmpAddress3_2;
					}
				}
				break;
			case 3:
				if(strlen($tmpAddress1_1) <= (int)($params['address']['consignee_address_line1_limit'])){
					$addressArr['address_line1'] = $tmpAddress1_1;
					$addressArr['address_line2'] = '';
					$addressArr['address_line3'] = '';
				}else{
					if(strlen($tmpAddress2_1) <= (int)($params['address']['consignee_address_line1_limit'])){
						$addressArr['address_line1'] = $tmpAddress2_1;
						$addressArr['address_line2'] = $tmpAddress2_2;
						$addressArr['address_line3'] = '';
					}else {
						$addressArr['address_line1'] = $tmpAddress3_1;
	
						if(strlen($tmpAddress3_2) <= (int)($params['address']['consignee_address_line2_limit'])){
							$addressArr['address_line2'] = $tmpAddress3_2;
							$addressArr['address_line3'] = '';
						}else{
							$addressArr['address_line2'] = $tmpAddress3_2_1;
							$addressArr['address_line3'] = $tmpAddress3_2_2;
						}
					}
				}
				break;
			default:
				$addressArr['address_line1'] = $tmpAddress1_1;
				break;
		}
		
		$phones = array();
		if(isset($params['consignee_phone_limit'])){
			$tmpPhone1 = '';
			
			if(!empty($order->consignee_mobile) && !empty($order->consignee_phone)){
				if($order->consignee_mobile == $order->consignee_phone){
					$tmpPhone1 = $order->consignee_mobile;
				}else{
					$tmpPhone1 = $order->consignee_mobile.'/'.$order->consignee_phone;
				}
			}else if(!empty($order->consignee_mobile) && empty($order->consignee_phone)){
				$tmpPhone1 = $order->consignee_mobile;
			}else if(empty($order->consignee_mobile) && !empty($order->consignee_phone)){
				$tmpPhone1 = $order->consignee_phone;
			}
			
			if(strlen($tmpPhone1) <= (int)($params['consignee_phone_limit'])){
				$phones['phone1'] = $tmpPhone1;
				$phones['phone2'] = empty($order->consignee_mobile) ? $order->consignee_phone : $order->consignee_mobile;
			}else{
				$phones['phone1'] = empty($order->consignee_mobile) ? $order->consignee_phone : $order->consignee_mobile;
				$phones['phone2'] = empty($order->consignee_phone) ? $order->consignee_mobile : $order->consignee_phone;
			}
		}
		
		return $addressArr+$phones;
	}
	
	/**
	 * 返回配货单路径
	 * @param $SAA_obj	表carrier_user_label对象
	 * @return 
	 * Array
		(
		    [error] => 0	是否失败: 1:失败,0:成功
		    [msg] => 		错误信息
		    [filePath] => D:\wamp\www\eagle2\eagle/web/tmp_api_pdf/20160316/1_4821.pdf	pdf路径
		)
	 * +-------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		hqw		2016/03/16				初始化
	 * +-------------------------------------------------------------------------------------------
	 */
	public static function getLabelItemsHtml($SAA_obj, $returnFileType = ''){
		 
		$order = OdOrder::find()->where(['order_id'=>$SAA_obj->order_id])->one();
		
		$headHtml = '<head><meta charset="UTF-8"/>'.
			'<link rel="stylesheet" href="/css/carrier/uielement.min.css" />'.
			'<link rel="stylesheet" href="/css/carrier/custom.css" />'.
			'<link rel="stylesheet" href="/css/carrier/print.css" />'.
			'<script src="/js/project/carrier/customprint/jquery.min.js"></script>'.
			'<style type="text/css">'.
			'<!--'.
			'body { margin: 0px; padding:0;}'.
			'.noprint { display: none}'.
			'ul{padding:0px;	 margin:0px; }'.
			'li{ list-style-type:none;}'.
			'body .label-content {
				background-color: transparent;
				border-radius: 0;
				-webkit-box-shadow: none;
				-moz-box-shadow: none;
				box-shadow: none;
			}'.
			'body .one-label {
			 	page-break-after: always;  
			 	page-break-inside: avoid;  
			}'.
			'body .A4-label {
			 	page-break-after: always;  
			 	page-break-inside: avoid; 
				height:297mm; 
			}'.
			'.label-content .view-mask, .label-content .custom-area, .label-content .custom-drop .dropitem .line-handle, .label-content .custom-drop .dropitem .ui-resizable-handle{ display:none;}
			.label-content .custom-drop{ border:1px solid #fff;}
			.label-content .custom-drop .dropitem{ cursor:default;}
			.label-content .custom-drop .dropitem:hover{ color:inherit; background-color:transparent;}'.
			'-->'.
			'</style>'.
			'</head>';
		$template = CrCarrierTemplate::find()->where(['template_name'=>'ST配货单10cm×10cm','template_type'=>'配货单'])->one();
		$shippingService = SysShippingService::findOne(['id'=>$order['default_shipping_method_code']]);
		
		$bodyHtml = PrintApiHelper::getHighCopyPrintData($template, $shippingService, $order, array());
		
		if(is_array($bodyHtml)){
			if(!empty($bodyHtml['msg'])){
				return ['error'=>1, 'msg'=>$bodyHtml['msg'], 'filePath'=>''];
			}
		}
		
		$bodyHtml = '<body>'.$bodyHtml.'</body>';
		$sumhtml = '<html>'.$headHtml.$bodyHtml.'</html>';
		
		$returnFileType .= $SAA_obj->order_id;
		$result = Helper_Curl::post("http://120.55.86.71/wkhtmltopdf.php",['html'=>$sumhtml,'uid'=>$SAA_obj->uid,'pringType'=>'100*100','returnFileType'=>$returnFileType]);// 打A4还是热敏纸
		
		if(false !== $result){
			$rtn = json_decode($result,true);
			if($rtn['success'] == 1){
				$response = Helper_Curl::get($rtn['url']);
				$pdfPath = self::savePDF2($response, $SAA_obj->uid, $SAA_obj->order_id.$SAA_obj->customer_number."_items_".time());
				return $pdfPath;
			}else{
				return ['error'=>1, 'msg'=>"打印出错，请联系小老板客服。", 'filePath'=>''];
			}
		}else{
			return ['error'=>1, 'msg'=>"请重试，如果再有问题请联系小老板客服。", 'filePath'=>''];
		}
	}
	
	/**
	 * 返回配货单路径2最新版
	 * @param $SAA_obj	表carrier_user_label对象
	 * @return 
	 * Array
		(
		    [error] => 0	是否失败: 1:失败,0:成功
		    [msg] => 		错误信息
		    [filePath] => D:\wamp\www\eagle2\eagle/web/tmp_api_pdf/20160316/1_4821.pdf	pdf路径
		)
	 * +-------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		hqw		2016/11/03				初始化
	 * +-------------------------------------------------------------------------------------------
	 */
	public static function getLabelItemsPDF($SAA_obj){
 
		
		//获取订单数据
// 		$order = OdOrder::find()->where(['order_id'=>$SAA_obj->order_id])->one();
		$order = \eagle\modules\delivery\helpers\DeliveryHelper::getDeliveryOrder($SAA_obj->order_id);
		
		//获取对应的运输服务设置
		$shippingService = SysShippingService::findOne(['id'=>$order['default_shipping_method_code']]);
		
		//固定使用10*10面单
		$format = array(100, 100);
		
		//暂时
		$lableCount = 2;
		
		//设置默认字体
		$otherParams['pdfFont'] = 'msyh';
		
		$tmpX = 0;
		$tmpY = 0;
		
		//新建
		$pdf = new \TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, $format, true, 'UTF-8', false);
		
		//设置页边距
		$pdf->SetMargins(0, 0, 0);
		
		//删除预定义的打印 页眉/页尾
		$pdf->setPrintHeader(false);
		$pdf->setPrintFooter(false);
		
		//设置不自动换页,和底部间距为0
		$pdf->SetAutoPageBreak(false, 0);
		
		$pdf->AddPage();
		
		PrintPdfHelper::getItemsLablePub($tmpX, $tmpY , $pdf, $order, $shippingService, $format, $lableCount, $otherParams);
		
// 		$pdf->Output('D:\wamp\www\eagle2\eagle\web\attachment\tmp_img_tcpdf\20161103\print.pdf', 'F');

		$pdfPath = self::savePDF2('', $SAA_obj->uid, $SAA_obj->order_id.$SAA_obj->customer_number."_items_".time().'_'.rand(100,999), 'pdf', 'tcpdf', $pdf);
// 		print_r($pdfPath);
// 		exit;
		
		return $pdfPath;
	}
	
	/**
	 * 保存队列数据
	 * @param $uid				用户uid
	 * @param $order_id			订单ID
	 * @param $customer_number	客户参考号
	 * @param $print_param		物流相关打印参数，这里传数组进来
	 * @return	true/false
	 * +-------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		hqw		2016/03/16				初始化
	 * +-------------------------------------------------------------------------------------------
	 */
	public static function saveCarrierUserLabelQueue($uid, $order_id, $customer_number, $print_param){
		$carrierLabel = CarrierUserLabel::find()->where(['uid'=>$uid, 'order_id'=>$order_id, 'customer_number'=>$customer_number])->one();
		if($carrierLabel == null){
			$carrierLabel = new CarrierUserLabel();
			
			$carrierLabel->uid = $uid;
			$carrierLabel->order_id = $order_id;
			$carrierLabel->customer_number = $customer_number;
			$carrierLabel->create_time = time();
			
			if(isset($print_param['run_status'])){
				$carrierLabel->run_status = $print_param['run_status'];
			}else{
				//不要直接运输后台拉取PDF,因为有的客户不使用我们系统打印的话，会造成客户在货代的后台显示面单已打印了
				$carrierLabel->run_status = 4;
			}
			
			if(isset($print_param['label_api_file_path'])){
				$carrierLabel->label_api_file_path = $print_param['label_api_file_path'];
			}
		}
		
		$carrierLabel->print_param = json_encode($print_param);
		return $carrierLabel->save(false);
	}
	
	/**
	 * 根据队列表carrier_user_label获取api PDF路径，配货单PDF路径
	 * @return	true/false
	 * +-------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		hqw		2016/03/16				初始化
	 * +-------------------------------------------------------------------------------------------
	 */
	public static function getCarrierLabelApiAndItemsByTime(){
		echo "++++++++++++ start to get carrier label for getCarrierLabelApiAndItemsByTime \n";
		//1. 检查当前本job版本，如果和自己的版本不一样，就自动exit吧
// 		$ret = self::checkNeedExitNot('getCarrierLabelApiAndItemsByTime');
// 		if ($ret===true) exit;
		
		$connection=Yii::$app->db;
		#########################
		$type = 'msgTime';
		$t = time()-600;//同一订单要10分钟后才能重试！  账号第一次绑定的时候update_time=0
		$hasGotRecord=false;//是否抢到账号
		echo "start for job getCarrierLabelApiAndItemsByTime \n";
		
		//将times大于10的也作拉取，只要判断拉取日期超过1天，并且失败次数大于等于10的重新试啦
		//run_status 运行状态，0：表示未运行过，1：表示运行中，2：表示完全运行成功，3：表示运行失败，4：表示暂时不要运行让用户自己去手动运行
		$command = $connection->createCommand('select id,uid,update_time,times from  `carrier_user_label` where `run_status` <> 1 and `run_status` <> 2 and `run_status` <> 4 AND update_time < '.$t.' order by `update_time` ASC limit 10');
		
		#################################
		$dataReader=$command->query();
		while(($row=$dataReader->read())!==false){
			$puid=$row['uid'];
			echo "try to do for id:".$row['id'].", puid:$puid \n";
			
			// 先判断是否真的抢到待处理账号
			$SAA_obj = self::_lockLabelAutosyncRecord($row['id']);
			if ($SAA_obj===null) continue; //抢不到---如果是多进程的话，有抢不到的情况
			
			//将times大于10的也作拉取，只要判断拉取日期超过1天，并且失败次数大于等于10的重新试啦
			if(($row['times'] >= 10) && ($row['update_time']+3600*24 >= $t)){
				echo "skip update_time puid ".$puid." \n";
		
				$SAA_obj->update_time = time();
				$SAA_obj->run_status = 3;
				$SAA_obj->save(false);
				continue;
			}
			
			$hasGotRecord=true;  // 抢到记录
			
			unset($result);
			try{
				$result = self::getPubCarrierLabel($SAA_obj);
			}catch (\Exception $ex) {
				$result['success']=false;
				$result['error'] = 'getLine:'.$ex->getLine().';getFile:'.$ex->getFile().';getMessage:'.json_encode($ex->getMessage());
// 				echo "Retrive from api failed: ".$result['error']."\n";
// 				\Yii::error(__CLASS__.' function:'.__FUNCTION__.' '.print_r($ex,true));
			}
			
			//4.
			if ($result['success']){
				$SAA_obj->err_msg = '';
				$SAA_obj->update_time = time();
				$SAA_obj->run_status = 2;
				$SAA_obj->times = 0;
			} else {
				$SAA_obj->err_msg = $result['error'];
				$SAA_obj->update_time = time();
				$SAA_obj->run_status = 3;
				$SAA_obj->times += 1;
			}
			
			if (!$SAA_obj->save(false)){
// 				\Yii::error(['carrierlabel',__CLASS__,__FUNCTION__, json_encode($SAA_obj->err_msg)],"edb\global");
// 				echo "Failed to update  ".$SAA_obj->uid." - ".json_encode($SAA_obj->err_msg)."\n";
			}
			
		}//end while of each binded
		return $hasGotRecord;
	}
	
	/**
	 * 获取api标签PDF,小老板配货单PDF helper
	 * @return	['success'=>false, 'error'=>'']
	 * +-------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		hqw		2016/03/16				初始化
	 * +-------------------------------------------------------------------------------------------
	 */
	public static function getPubCarrierLabel(&$SAA_obj, $returnFileType = ''){
		$result = ['success'=>false, 'error'=>''];
		try {
			$print_param = json_decode($SAA_obj->print_param, true);
			$class_name = '\common\api\carrierAPI\\'.$print_param['api_class'];
			
			$interface = new $class_name;
			
			$timeMS1 = TimeUtil::getCurrentTimestampMS();
			
// 			if($print_param['api_class'] == 'LB_ALIONLINEDELIVERYCarrierAPI'){
// 				$SAA_obj->label_api_file_path = '';
// 			}
			
			if(!empty($SAA_obj->label_api_file_path) && !empty($SAA_obj->label_items_file_path) && !empty($SAA_obj->merge_pdf_file_path)){
				$result['success'] = true;
				return $result;
			}
			
			if(empty($SAA_obj->label_api_file_path)){
				//获取API PDF路径
				if(method_exists($interface,'getCarrierLabelApiPdf')){
					$pdfPath = $interface->getCarrierLabelApiPdf($SAA_obj, $print_param);
				}else{
					$pdfPath = ['error'=>1, 'msg'=>'该物流还没有对接getCarrierLabelApiPdf这个接口', 'filePath'=>''];
				}
				
				if($pdfPath['error'] == 0) $SAA_obj->label_api_file_path = $pdfPath['filePath'];
			}else{
				$pdfPath = ['error'=>0];
			}
			
			$timeMS2 = TimeUtil::getCurrentTimestampMS();
			
			if(empty($SAA_obj->label_items_file_path)){
				//获取配货单 PDF路径
				try{
	// 				$pdfItemsPath = self::getLabelItemsHtml($SAA_obj, $returnFileType);
					$pdfItemsPath = self::getLabelItemsPDF($SAA_obj);
				}catch (\Exception $ex){
					$pdfItemsPath['error'] = 1;
					$pdfItemsPath['msg'] = 'getLine:'.$ex->getLine().';getFile:'.$ex->getFile().';getMessage:'.json_encode($ex->getMessage());
				}
				
				if($pdfItemsPath['error'] == 0) $SAA_obj->label_items_file_path = $pdfItemsPath['filePath'];
			}else{
				$pdfItemsPath = ['error'=>0];
			}
			
			$timeMS3 = TimeUtil::getCurrentTimestampMS();
			
			if(($pdfPath['error'] == 0) && ($pdfItemsPath['error'] == 0)){
				try{
					//如果是正确信息, 保存pdf, 并返回包括URL的相关参数
					$filename = $SAA_obj->uid.'_'.$SAA_obj->order_id.'_'.$SAA_obj->customer_number.'_merge_'.time().'.pdf';
						
					//文件保存物理路径
					$file = self::createCarrierLabelDir().DIRECTORY_SEPARATOR.$filename;
				}catch(\Exception $ex){
					$result['success'] = false;
					$result['error'] = 'getLine:'.$ex->getLine().';getFile:'.$ex->getFile().';getMessage:'.json_encode($ex->getMessage());
					return $result;
				}
				
				$pdfmergeResult = PDFMergeHelper::PDFMerge($file ,array(self::createCarrierLabelDir(false).$SAA_obj->label_items_file_path, self::createCarrierLabelDir(false).$SAA_obj->label_api_file_path));
				
				if($pdfmergeResult['success'] == true){
					$pdfmergeResult['filePath'] = str_replace(self::getPdfPathString(), "", $pdfmergeResult['filePath']);
					$SAA_obj->merge_pdf_file_path = $pdfmergeResult['filePath'];
				}else{
					$result['error'] = $pdfmergeResult['message'];
				}
				
				$result['success'] = $pdfmergeResult['success'];
			}
			else{
				if($pdfPath['error'] == 1) $result['error'] .= $pdfPath['msg'];
				if($pdfItemsPath['error'] == 1) $result['error'] .= $pdfItemsPath['msg'];
			}
		}catch (\Exception $ex){
			$result['success'] = false;
			$result['error'] = 'getLine:'.$ex->getLine().';getFile:'.$ex->getFile().';getMessage:'.json_encode($ex->getMessage());
		}
		
		return $result;
	}
	
	/**
	 * carrier_user_label 将其锁定，用于多进程
	 * @return	CarrierUserLabel 对象
	 * +-------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		hqw		2016/03/16				初始化
	 * +-------------------------------------------------------------------------------------------
	 */
	private static function _lockLabelAutosyncRecord($labelAutosyncId){
		$connection=Yii::$app->db;
		$command = $connection->createCommand("update carrier_user_label set run_status=1 where id =". $labelAutosyncId." and run_status<>1 and run_status<>2 ") ;
		$affectRows = $command->execute();
		if ($affectRows <= 0) return null; //抢不到---如果是多进程的话，有抢不到的情况
		
		// 抢到记录
		$SAA_obj = CarrierUserLabel::findOne($labelAutosyncId);
	
		return $SAA_obj;
	}
	
	/**
	 * 将pdf文件保存到本地
	 * 
	 * @param $data				pdf数据流
	 * @param $puid				puid
	 * @param $carrier_code		物流商代码，其实用于生成文件名
	 * @param $type				保存的文件类型
	 * @return 
	 * +-------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		hqw		2016/03/16				初始化
	 * +-------------------------------------------------------------------------------------------
	 */
	public static function savePDF2($data, $puid, $carrier_code, $type='pdf', $tool_type = '', $pdf = '') {
		//如果是正确信息, 保存pdf, 并返回包括URL的相关参数
		$filename = $puid.'_'.$carrier_code.'.'.$type;
		
		try{
			//文件保存物理路径
			$file = self::createCarrierLabelDir().'/'.$filename;
			
			if(empty($tool_type)){
				if(file_put_contents($file,$data)){
					$tmpFile = str_replace(self::getPdfPathString(), "", $file);
					return ['error'=>0, 'msg'=>'', 'filePath'=>$tmpFile];
				}
			}else{
				$tmpFormerFile = $file;
				$file = str_replace('\\','/',$file);
				$pdf->Output($file, 'F');
				
				$tmpFile = str_replace(self::getPdfPathString(), "", $tmpFormerFile);
				
				return ['error'=>0, 'msg'=>'', 'filePath'=>$tmpFile];
			}
			
			return ['error'=>1, 'msg'=>'保存文件失败', 'filePath'=>''];
		}catch (\Exception $ex){
			return ['error'=>1, 'msg'=>print_r($ex), 'filePath'=>''];
		}
	}
	
	/**
	 * 获取打印保存路径
	 * @param	$is_create_dir 是否创建日期目录
	 * @return string
	 */
	public static function createCarrierLabelDir($is_create_dir = true){
		if($is_create_dir){
			$basepath = self::getPdfPathString().DIRECTORY_SEPARATOR.'attachment'.DIRECTORY_SEPARATOR.'tmp_api_pdf';
			//根据年月日生成目录，用于以后方便管理删除文件
			$dataDir = date("Ymd");
			
			if(!file_exists($basepath.'/'.$dataDir)){
				mkdir($basepath.'/'.$dataDir);
				chmod($basepath.'/'.$dataDir,0777);
// 				system('chown -R www-data:www-data '.$basepath.'/tmp_api_pdf/'.$dataDir,$result);
			}
			return $basepath.'/'.$dataDir;
		}else{
			$basepath = self::getPdfPathString();
			return $basepath;
		}
	}
	
	public static function getPdfPathString(){
		return dirname(dirname(dirname(\Yii::getAlias('@yii')))).DIRECTORY_SEPARATOR.'eagle'.DIRECTORY_SEPARATOR.'web';
	}
	
	/**
	 * 即时执行PDF拉取和下载
	 */
	public static function getCarrierLabelApiAndItemsByNow($lableIdArr){
		$connection=Yii::$app->db;
		
		$timeMS1 = TimeUtil::getCurrentTimestampMS();
		
		$dataReader = CarrierUserLabel::find()->select(['id','uid','update_time','times'])->where(['id'=>$lableIdArr])->andWhere('`run_status` <> 1 and `run_status` <> 2')->asArray()->all();
		
		$timeMS2 = TimeUtil::getCurrentTimestampMS();
		
		if(count($dataReader) <= 0){
			return false;
		}
		
		$tmp_log_time = '';
		
		foreach ($dataReader as $dataReaderVal){
			$timeMS_2_1 = TimeUtil::getCurrentTimestampMS();
			
			// 先判断是否真的抢到待处理账号
			$SAA_obj = self::_lockLabelAutosyncRecord($dataReaderVal['id']);
			if ($SAA_obj===null) continue; //抢不到---如果是多进程的话，有抢不到的情况
			
			$timeMS_2_2 = TimeUtil::getCurrentTimestampMS();
			
			unset($result);
			try{
				$result = self::getPubCarrierLabel($SAA_obj, 'view');
			}catch (\Exception $ex) {
				$result['success']=false;
				$result['error'] = print_r($ex,true);
			}
			
			$timeMS_2_3 = TimeUtil::getCurrentTimestampMS();
			
			if ($result['success']){
				$SAA_obj->err_msg = '';
				$SAA_obj->update_time = time();
				$SAA_obj->run_status = 2;
				$SAA_obj->times = 0;
			} else {
				$SAA_obj->err_msg = $result['error'].' getCarrierLabelApiAndItemsByNow';
				$SAA_obj->update_time = time();
				$SAA_obj->run_status = 3;
				$SAA_obj->times += 1;
			}
			
			$SAA_obj->save(false);
			
			$timeMS_2_4 = TimeUtil::getCurrentTimestampMS();
			
			$tmp_log_time .= 'order:'.$SAA_obj->order_id.': '.
				' time4-3:'.($timeMS_2_4-$timeMS_2_3).' time3-2:'.($timeMS_2_3-$timeMS_2_2).' time2-1:'.($timeMS_2_2-$timeMS_2_1);
		}
		
		$timeMS3 = TimeUtil::getCurrentTimestampMS();
		
		\Yii::info('ShowPrintPdfByNow:'.' time3-2:'.($timeMS3-$timeMS2).' time2-1:'.($timeMS2-$timeMS1).' '.$tmp_log_time, "carrier_api");
		
		return true;
	}
	
	//更新异常失败的一体化面单数据
	public static function updateAbnormalCarrierLabel($puid, $orderArr){
// 		$result = \Yii::$app->get('db')->createCommand()->update('carrier_user_label',
// 				['run_status' => 4], ['and', ['uid' => $puid], ['run_status'=>'1'], ['order_id'=>$orderArr], ['<', 'create_time', time()-120]])->execute();
		
		$result = \Yii::$app->get('db')->createCommand()->update('carrier_user_label',
				['run_status' => 4, 'label_items_file_path'=>'', 'merge_pdf_file_path'=>''], ['and', ['uid' => $puid], ['order_id'=>$orderArr], ['<', 'create_time', time()-120]])->execute();
		
		return $result;
	}
	
	//单个获取速卖通面单
	public static function getAliCarrierLabel($order_arr, $puid){
		$orderCarrierLabelLists = CarrierUserLabel::find()->select(['id','uid','order_id','print_param','customer_number','run_status','update_time','label_api_file_path','times'])
			->where(['uid'=>$puid, 'order_id'=>$order_arr])->andWhere("label_api_file_path is null or label_api_file_path=''")->asArray()->all();
		
		foreach ($orderCarrierLabelLists as $orderCarrierLabelListOne){
			$SAA_obj = self::_lockLabelAutosyncRecord($orderCarrierLabelListOne['id']);
			if ($SAA_obj===null) continue;
			
			$print_param = json_decode($SAA_obj->print_param, true);
			$class_name = '\common\api\carrierAPI\\'.$print_param['api_class'];
			$interface = new $class_name;
			
			if(empty($SAA_obj->label_api_file_path)){
				//获取API PDF路径
				if(method_exists($interface,'getCarrierLabelApiPdf')){
					$pdfPath = $interface->getCarrierLabelApiPdf($SAA_obj, $print_param);
				}else{
					$pdfPath = ['error'=>1, 'msg'=>'该物流还没有对接getCarrierLabelApiPdf这个接口', 'filePath'=>''];
				}
			
				if($pdfPath['error'] == 0) $SAA_obj->label_api_file_path = $pdfPath['filePath'];
			}else{
				$pdfPath = ['error'=>0];
			}
			
			$SAA_obj->run_status = 3;
			$SAA_obj->save(false);
		}
	}
	
	//批量获取速卖通面单
	public static function getAliCarrierLabels($order_arr, $puid){
		try{
			$orderCarrierLabelLists = CarrierUserLabel::find()->select(['id', 'print_param'])
				->where(['uid'=>$puid, 'order_id'=>$order_arr])->andWhere("label_api_file_path is null or label_api_file_path=''")->asArray()->all();
			
			$list = array();
			$ids = array();
			$tracking_number_ids = array();
			foreach ($orderCarrierLabelLists as $orderCarrierLabelListOne){
				$ids[] = $orderCarrierLabelListOne['id'];
				$print_param = json_decode($orderCarrierLabelListOne['print_param'], true);
				$tracking_number_ids[$print_param['tracking_number']] =  $orderCarrierLabelListOne['id'];
				//限制批量数
				$time = time();
				$key = $print_param['selleruserid'];
				while(1){
					if(empty($list[$key]) || count($list[$key]) < 5){
						break;
					}
					$key = $key.'_';
					if($time < time() - 10){
						break;
					}
				}
				$list[$key][] = $print_param['tracking_number'];
			}
			
			//循环速卖通账号获取面单
			$api = new LB_ALIONLINEDELIVERYCarrierAPI();
			foreach($list as $selleruserid => $internationalLogisticsIds){
				$selleruserid = rtrim($selleruserid, '_');
				$ret = $api->getCarrierLabelApiPdfAlone($puid, $selleruserid, $internationalLogisticsIds);
				
				if(!$ret['error']){
					foreach($ret['filePaths'] as $internationalLogisticsId => $filePath){
						if(!$filePath['error']){
							if(array_key_exists($internationalLogisticsId, $tracking_number_ids)){
								$SAA_obj = CarrierUserLabel::findOne($tracking_number_ids[$internationalLogisticsId]);
								$SAA_obj->label_api_file_path = $filePath['filePath'];
								$SAA_obj->run_status = 3;
								$SAA_obj->save(false);
							}
						}
					}
				}
			}
		}
		catch(\Exception $ex){
			
		}
	}
	
	public static function getCarrierLabelApiAndItemsByNow_1($order_arr, $puid){
		$result = array('error'=>0, 'msg'=>'', 'data'=>'');
		
		$orderlists = OdOrder::find()->where(['order_id'=>$order_arr])->all();
		
		if(count($order_arr) != count($orderlists)){
			$result['error'] = 1;
			$result['msg'] = '传订单号异常,请联系小老板客服!';
			return $result;
		}
		
		//记录哪些是速卖通的订单
		$ali_orderids = array();
		
		//记录订单的相关拣货信息
		$order_picking_info = array();
		
		foreach ($orderlists as $order){
			unset($tmpPickingOrderInfo);
			$tmpPickingOrderInfo = self::getPickingOrderInfo($order, $puid);
			
			//因为返回的信息中可以会存在打印时间,这里暂时没有用到所以先做屏蔽
			$tmpPickingOrderInfoMd5 = $tmpPickingOrderInfo;
			unset($tmpPickingOrderInfoMd5['itemListDetailInfo']['lists']['ORDER_PRINT_TIME']);
			unset($tmpPickingOrderInfoMd5['itemListDetailInfo']['lists']['ORDER_PRINT_TIME2']);
			$tmpPickingOrderInfoMd5 = json_encode($tmpPickingOrderInfoMd5);
			
			$tmpPickingOrderInfo = json_encode($tmpPickingOrderInfo);
			$order_picking_info[$order['order_id']] = array('json'=>$tmpPickingOrderInfo, 'md5'=>md5($tmpPickingOrderInfoMd5));
			
			if($order['default_carrier_code'] == 'lb_alionlinedelivery'){
				$ali_orderids[] = $order['order_id'];
				
				foreach ($order->trackinfos as $tmp_trackinfos){
					if($tmp_trackinfos->customer_number == $order->customer_number){
						if($tmp_trackinfos->addtype != '物流API'){
							$result['error'] = 1;
							$result['msg'] = '订单号:'.$order->order_id.',该订单不是通过API上传的，所以不能打印!';
							return $result;
						}
					}
				}
			}
		}
		
		if(count($ali_orderids) > 0){
			//批量
			self::getAliCarrierLabels($ali_orderids, $puid);
			//单个，防止个别异常
			self::getAliCarrierLabel($ali_orderids, $puid);
		}
		
		$orderCarrierLabelLists = CarrierUserLabel::find()->select(['id','uid','order_id','print_param','customer_number','run_status','update_time','label_api_file_path','label_items_file_path','merge_pdf_file_path','times','order_md5'])
			->where(['uid'=>$puid, 'order_id'=>$order_arr])->asArray()->all();
		
		if(count($orderCarrierLabelLists) == 0){
			$result['error'] = 1;
			$result['msg'] = '该货代暂时不支持:打印面单+拣货单（订单汇总）打印模式.e1!';
			return $result;
		}
		
		//记录需要生成PDF的队列
		$tmp_api_or_pickings = array('api_param'=>array(), 'picking_param'=>array());
		
		//记录需要走并发的ID
		$tmp_run_ids = array();
		
		//记录总的PDF路径
		$tmp_sum_pdf_path = array();
		
		//记录总的PDF队列ID 
		$tmp_sum_pdf_path_ids = array();
		
		//记录成功的PDF队列ID
		$tmp_pdf_succeed_ids = array();
			
		foreach ($orderlists as $orderlist){
			foreach($orderCarrierLabelLists as $orderCarrierLabelList){
				if(($orderlist['order_id'] == $orderCarrierLabelList['order_id']) && ($orderlist['customer_number'] == $orderCarrierLabelList['customer_number'])){
					
					//速卖通线上发货由于不清楚LP开头的面单，所以需要判断速卖通的订单每次都需要生成PDF
					$tmp_print_param = json_decode($orderCarrierLabelList['print_param'], true);
					
// 					if($tmp_print_param['api_class'] == 'LB_ALIONLINEDELIVERYCarrierAPI'){
// 						$orderCarrierLabelList['label_api_file_path'] = '';
// 					}
					
					$tmp_sum_pdf_path[$orderCarrierLabelList['id']] = array(
							'order_id'=>$orderCarrierLabelList['order_id'],
							'customer_number'=>$orderCarrierLabelList['customer_number'],
							'label_api_file_path'=>$orderCarrierLabelList['label_api_file_path'],
							'label_items_file_path'=>$orderCarrierLabelList['label_items_file_path'],
							'merge_pdf_file_path'=>$orderCarrierLabelList['merge_pdf_file_path'],
							'is_update_merge'=>empty($orderCarrierLabelList['merge_pdf_file_path']) ? true : false,
							'error' => ''
					);
					
					$tmp_sum_pdf_path_ids[$orderCarrierLabelList['id']] = $orderCarrierLabelList['id'];
					
					if(($orderCarrierLabelList['run_status'] != 1)){
						
						//是否需要走API pdf 生成
						$tmp_api = false;
						if($orderCarrierLabelList['label_api_file_path'] == ''){
							$tmp_api = true;
						}
						
						//是否需要生成拣货单
						$tmp_picking = false;
						if(($order_picking_info[$orderlist['order_id']]['md5'] != $orderCarrierLabelList['order_md5']) || ($orderCarrierLabelList['label_items_file_path'] == '')){
							$tmp_picking = true;
						}
						
						if(($tmp_api == true) || ($tmp_picking == true)){
							if($tmp_api == true){
								$tmp_api_or_pickings['api_param'][$orderCarrierLabelList['id']] = json_encode(array(
										'uid'=>$puid, 
										'order_id'=>$orderCarrierLabelList['order_id'], 
										'customer_number'=>$orderCarrierLabelList['customer_number'], 
										'print_param'=>json_decode($orderCarrierLabelList['print_param'], true)
								));
							}
							
							if($tmp_picking == true){
								$tmp_api_or_pickings['picking_param'][$orderCarrierLabelList['id']] = $order_picking_info[$orderlist['order_id']]['json'];
							}
							
							$tmp_run_ids[$orderCarrierLabelList['id']] = $orderCarrierLabelList['id'];
							
							$tmp_pdf_succeed_ids[$orderCarrierLabelList['id']] = array(
									'api'=>array('is_create'=>$tmp_api, 'succeed'=>false),
									'picking'=>array('is_create'=>$tmp_picking, 'succeed'=>false)
							);
						}
					}
				}
			}
		}
		
		if((count($tmp_api_or_pickings['api_param']) > 0) || (count($tmp_api_or_pickings['picking_param']) > 0)){
			
			//PDF队列数组
			$tmp_insertATaskForPDF = array();
			
			foreach ($tmp_run_ids as $tmp_run_id_one){
				$record_count = self::_lockLabelAutosyncRecord_1($tmp_run_id_one, 1);
				
				if($record_count == 1){
					if(isset($tmp_api_or_pickings['picking_param'][$tmp_run_id_one])){
						$tmp_insertATaskForPDF[] = array(
								'type'=>'picking',
								'run_id'=>$tmp_run_id_one,
								'param'=>json_decode($tmp_api_or_pickings['picking_param'][$tmp_run_id_one], true));
					}
					
					if(isset($tmp_api_or_pickings['api_param'][$tmp_run_id_one])){
						$tmp_insertATaskForPDF[] = array(
								'type'=>'api',
								'run_id'=>$tmp_run_id_one,
								'param'=>json_decode($tmp_api_or_pickings['api_param'][$tmp_run_id_one], true));
					}
				}else{
					$result['error'] = 1;
					$result['msg'] = '请稍后再试';
						
					return $result;
				}
			}
			
// 			print_r($tmp_insertATaskForPDF);
// 			exit;
			
			if(count($tmp_insertATaskForPDF) > 0){
// 				//20170427
// 				echo json_encode($tmp_insertATaskForPDF);

				$timeMS1 = TimeUtil::getCurrentTimestampMS();
				$tmp_insertATaskForPDF_count = count($tmp_insertATaskForPDF);
				
				$resultTasks = PDFQueueHelper::insertATaskForPDFs($tmp_insertATaskForPDF);
				
				if($resultTasks['success'] == false){
					$result['error'] = 1;
					$result['msg'] = $resultTasks['message'];
					return $result;
				}
				
				$tmp_is_queue = true;
				$tmp_count_while = 0;
				while ($tmp_is_queue){
					//1秒=1,000,000 微秒(μs)
					usleep(300000);
					
					foreach ($tmp_insertATaskForPDF as $tmp_insertATaskForPDF_key => $tmp_insertATaskForPDF_val){
						unset($resultfor);
						$resultfor = PDFQueueHelper::getResultFor($resultTasks['key'].'/'.$tmp_insertATaskForPDF_key, true);
						
						if(!empty($resultfor)){
							$resultfor = json_decode($resultfor, true);
							
							if((empty($resultfor['filePath'])) && ($resultfor['error'] == 0)){
								$resultfor['error'] == 1;
								$resultfor['msg'] .= ' 返回为空，请稍后重试';
							}
							
							if($resultfor['error'] == 0){
								if (strpos($resultfor['filePath'], $puid.'_') === false){
									\Yii::info('pdf_print_insertATask_PUID_bug puid:'.$puid.':'.$resultfor['filePath'], "carrier_api");
									$resultfor['error'] = 1;
									$resultfor['msg'] .= ' 返回异常e01，请稍后重试';
								}
							}
							
							if($resultfor['error'] == 0){
								if($tmp_insertATaskForPDF_val['type'] == 'api'){
									$tmp_sum_pdf_path[$tmp_insertATaskForPDF_val['run_id']]['label_api_file_path'] = $resultfor['filePath'];
									$tmp_sum_pdf_path[$tmp_insertATaskForPDF_val['run_id']]['is_update_merge'] = true;
									
									$tmp_pdf_succeed_ids[$tmp_insertATaskForPDF_val['run_id']]['api']['succeed'] = true;
								}else if($tmp_insertATaskForPDF_val['type'] == 'picking'){
									$tmp_sum_pdf_path[$tmp_insertATaskForPDF_val['run_id']]['label_items_file_path'] = $resultfor['filePath'];
									$tmp_sum_pdf_path[$tmp_insertATaskForPDF_val['run_id']]['is_update_merge'] = true;
									
									$tmp_pdf_succeed_ids[$tmp_insertATaskForPDF_val['run_id']]['picking']['succeed'] = true;
								}
							}else{
								if($tmp_insertATaskForPDF_val['type'] == 'api'){
									$tmp_sum_pdf_path[$tmp_insertATaskForPDF_val['run_id']]['error'] .= ' api:'.$resultfor['msg'];
								}else if($tmp_insertATaskForPDF_val['type'] == 'picking'){
									$tmp_sum_pdf_path[$tmp_insertATaskForPDF_val['run_id']]['error'] .= ' items:'.$resultfor['msg'];
								}
							}
							
							unset($tmp_insertATaskForPDF[$tmp_insertATaskForPDF_key]);
						}
					}
					
					$tmp_count_while++;
					if($tmp_count_while>200){
						$tmp_is_queue = false;
						
						MailHelper::sendMailBySQ("IT_department@littleboss.com", "技术部自动监测", "akirametero@vip.qq.com", "PDF打印标签队列handler超时", 'puid:'.$puid.' '.json_encode($tmp_insertATaskForPDF));
					}
					
					if(count($tmp_insertATaskForPDF) == 0){
						$tmp_is_queue = false;
					}
				}
				
				$timeMS2 = TimeUtil::getCurrentTimestampMS();
				\Yii::info('pdf_print_insertATask:'.' time2-1:'.($timeMS2-$timeMS1).' insertATaskForPDF_count:'.(($tmp_insertATaskForPDF_count)), "carrier_api");
			}
		}
		
// 		print_r($tmp_sum_pdf_path);

// 		$tmp_pdf_succeed_ids[$orderCarrierLabelList['id']] = array(
// 				'api'=>array('is_create'=>$tmp_api, 'succeed'=>false),
// 				'picking'=>array('is_create'=>$tmp_picking, 'succeed'=>false)
// 		);

		\Yii::info('pdf_print_insertATask1 puid:'.$puid.' '.((json_encode($tmp_sum_pdf_path))), "carrier_api");
		
		if(count($tmp_sum_pdf_path) > 0){
			foreach ($tmp_sum_pdf_path as $tmp_sum_pdf_path_key => $tmp_sum_pdf_path_val){
// 				if(($tmp_sum_pdf_path_val['is_update_merge'] == true) && ($tmp_sum_pdf_path_val['error'] == '')){
				if((!empty($tmp_sum_pdf_path_val['label_api_file_path'])) && (!empty($tmp_sum_pdf_path_val['label_items_file_path'])) && (empty($tmp_sum_pdf_path_val['merge_pdf_file_path']))){
					try{
						//如果是正确信息, 保存pdf, 并返回包括URL的相关参数
						$filename = $puid.'_'.$tmp_sum_pdf_path_val['order_id'].'_'.$tmp_sum_pdf_path_val['customer_number'].'_merge_'.time().'.pdf';
					
						//文件保存物理路径
						$file = self::createCarrierLabelDir().DIRECTORY_SEPARATOR.$filename;
					}catch(\Exception $ex){
						$tmp_sum_pdf_path[$tmp_sum_pdf_path_key]['error'] = 'getLine:'.$ex->getLine().';getFile:'.$ex->getFile().';getMessage:'.json_encode($ex->getMessage());
					}
					
					$pdfmergeResult = PDFMergeHelper::PDFMerge($file ,array(self::createCarrierLabelDir(false).$tmp_sum_pdf_path_val['label_items_file_path'], self::createCarrierLabelDir(false).$tmp_sum_pdf_path_val['label_api_file_path']));
					
					if($pdfmergeResult['success'] == true){
						$pdfmergeResult['filePath'] = str_replace(self::getPdfPathString(), "", $pdfmergeResult['filePath']);
						$tmp_sum_pdf_path[$tmp_sum_pdf_path_key]['merge_pdf_file_path'] = $pdfmergeResult['filePath'];
					}else{
						$tmp_sum_pdf_path[$tmp_sum_pdf_path_key]['error'] = $pdfmergeResult['message'];
					}
				}
				
				if(isset($tmp_pdf_succeed_ids[$tmp_sum_pdf_path_key])){
					if(($tmp_pdf_succeed_ids[$tmp_sum_pdf_path_key]['api']['is_create'] == true) && ($tmp_pdf_succeed_ids[$tmp_sum_pdf_path_key]['api']['succeed'] == false)){
						$tmp_sum_pdf_path[$tmp_sum_pdf_path_key]['error'] .= 'api:执行失败,请稍后重试,或联系小老板客服';
					}
					
					if(($tmp_pdf_succeed_ids[$tmp_sum_pdf_path_key]['picking']['is_create'] == true) && ($tmp_pdf_succeed_ids[$tmp_sum_pdf_path_key]['picking']['succeed'] == false)){
						$tmp_sum_pdf_path[$tmp_sum_pdf_path_key]['error'] .= '拣货单:执行失败,请稍后重试,或联系小老板客服';
					}
				}
			}
			
// 			$carrier_user_labels = CarrierUserLabel::find()->select(['id','order_id','update_time','times'])
// 				->where(['id'=>$tmp_run_ids])->all();
			$carrier_user_labels = CarrierUserLabel::find()->select(['id','order_id','update_time','times'])
				->where(['id'=>$tmp_sum_pdf_path_ids])->all();
			
			foreach ($carrier_user_labels as $carrier_user_label_one){
				if(isset($tmp_sum_pdf_path[$carrier_user_label_one['id']])){
					if($tmp_sum_pdf_path[$carrier_user_label_one['id']]['error'] != ''){
						$carrier_user_label_one->err_msg = $tmp_sum_pdf_path[$carrier_user_label_one['id']]['error'].' getCarrierLabelApiAndItemsByNow_1';
						$carrier_user_label_one->update_time = time();
						$carrier_user_label_one->run_status = 3;
						$carrier_user_label_one->times += 1;
						
						if(empty($carrier_user_label_one->label_api_file_path) && (!(empty($tmp_sum_pdf_path[$carrier_user_label_one['id']]['label_api_file_path'])))){
							$carrier_user_label_one->label_api_file_path = $tmp_sum_pdf_path[$carrier_user_label_one['id']]['label_api_file_path'];
						}
						
						if(empty($carrier_user_label_one->label_items_file_path) && (!(empty($tmp_sum_pdf_path[$carrier_user_label_one['id']]['label_items_file_path'])))){
							$carrier_user_label_one->label_items_file_path = $tmp_sum_pdf_path[$carrier_user_label_one['id']]['label_items_file_path'];
							$carrier_user_label_one->order_info = $order_picking_info[$carrier_user_label_one['order_id']]['json'];
							$carrier_user_label_one->order_md5 = $order_picking_info[$carrier_user_label_one['order_id']]['md5'];
						}
					}else{
						$carrier_user_label_one->label_api_file_path = $tmp_sum_pdf_path[$carrier_user_label_one['id']]['label_api_file_path'];
						$carrier_user_label_one->label_items_file_path = $tmp_sum_pdf_path[$carrier_user_label_one['id']]['label_items_file_path'];
						$carrier_user_label_one->merge_pdf_file_path = $tmp_sum_pdf_path[$carrier_user_label_one['id']]['merge_pdf_file_path'];
						
						$carrier_user_label_one->order_info = $order_picking_info[$carrier_user_label_one['order_id']]['json'];
						$carrier_user_label_one->order_md5 = $order_picking_info[$carrier_user_label_one['order_id']]['md5'];
						
						$carrier_user_label_one->err_msg = '';
						$carrier_user_label_one->update_time = time();
						$carrier_user_label_one->run_status = 2;
						$carrier_user_label_one->times = 0;
					}
					
					$carrier_user_label_one->save(false);
				}
			}
		}
		
		if(count($tmp_sum_pdf_path) > 0){
			unset($carrier_user_labels);
			unset($carrier_user_label_one);
			$tmp_carrier_user_labels = CarrierUserLabel::find()->select(['id','order_id','update_time','times','run_status','err_msg','merge_pdf_file_path'])
				->where(['id'=>$tmp_sum_pdf_path_ids])->asArray()->all();
			
			//定义排序
			$carrier_user_labels = array();
// 			$order_arr
			
			foreach ($order_arr as $order_arr_one){
				foreach ($tmp_carrier_user_labels as $tmp_carrier_user_label_one){
					if($order_arr_one == $tmp_carrier_user_label_one['order_id']){
						$carrier_user_labels[] = $tmp_carrier_user_label_one;
						break;
					}
				}
			}
			
			//记录执行异常的ID
			$run_error_ids = array();
			
			foreach ($carrier_user_labels as $carrier_user_label_one){
				if($carrier_user_label_one['run_status'] == 2){
					$result['data'][] = $carrier_user_label_one['merge_pdf_file_path'];
				}else{
					$result['error'] = 1;
					$result['msg'] = $carrier_user_label_one['err_msg'];
	
// 					return $result;

					if(($carrier_user_label_one['run_status'] == 1) && (time() > $carrier_user_label_one['update_time']+120)){
						$run_error_ids[$carrier_user_label_one['id']] = $carrier_user_label_one['id'];
					}
				}
			}
			
			if(count($run_error_ids) > 0){
				$result_nnn = \Yii::$app->get('db')->createCommand()->update('carrier_user_label',
						['run_status' => 4,'order_md5' => ''], ['and', ['id' => $run_error_ids]])->execute();
			}
			
// 			unset($tmp_sum_pdf_path_val);
// 			foreach ($tmp_sum_pdf_path as $tmp_sum_pdf_path_val){
// 				if($tmp_sum_pdf_path_val['error'] != ''){
// 					$result['error'] = 1;
// 					$result['msg'] = $tmp_sum_pdf_path_val['error'];
					
// 					return $result;
// 				}else{
// 					$result['data'][] = $tmp_sum_pdf_path_val['merge_pdf_file_path'];
// 				}
// 			}
		}else{
			$result['error'] = 1;
			$result['msg'] = '没有相关订单信息，或者这部分订单不支持一体化面单';
		}
		
		return $result;
	}
	
	//获取订单的拣货信息
	public static function getPickingOrderInfo($order, $puid = false){
		$sumParams = array();
		
		$sumParams['order'] = array(
				'puid'=>$puid,
				'order_id'=>$order['order_id'],
				'desc'=>$order['desc'],
				'customer_number'=>$order['customer_number'],
				'order_source_order_id'=>$order['order_source_order_id'],
				'order_source'=>$order['order_source']
		);
		
		//获取订单详情列表信息
		$sumParams['itemListDetailInfo'] = PrintPdfHelper::getItemListDetailInfo($order, null, true, true, $puid);
		
		//获取跟踪号
		$sumParams['tracking_number'] = \eagle\modules\carrier\helpers\CarrierOpenHelper::getOrderShippedTrackingNumber($order['order_id'],$order['customer_number'],$order['default_shipping_method_code']);
		
		return $sumParams;
	}
	
	//控制执行的优先级 参数$run_status 1 表示前端触发，5为后台job触发
	private static function _lockLabelAutosyncRecord_1($labelAutosyncId, $run_status = 1){
		$connection=Yii::$app->db;
// 		$command = $connection->createCommand("update carrier_user_label set run_status=".$run_status." where id =". $labelAutosyncId." and run_status<>1 and run_status<>2 ") ;
		$command = $connection->createCommand("update carrier_user_label set run_status=".$run_status." where id =". $labelAutosyncId." and run_status<>1 ") ;
		$affectRows = $command->execute();
		if ($affectRows <= 0) return 0; //抢不到---如果是多进程的话，有抢不到的情况
	
		return 1;
	}
	
	/**
	 * 返回配货单路径2最新版  并发版
	 * @param $SAA_obj	表carrier_user_label对象
	 * @return
	 * Array
	 (
	 [error] => 0	是否失败: 1:失败,0:成功
	 [msg] => 		错误信息
	 [filePath] => D:\wamp\www\eagle2\eagle/web/tmp_api_pdf/20160316/1_4821.pdf	pdf路径
	 )
	 * +-------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		hqw		2016/11/03				初始化
	 * +-------------------------------------------------------------------------------------------
	 */
	public static function getLabelItemsPDF_1($params){
// 		$sumParams = json_decode($params ,true);
		$sumParams = $params;
		$puid = $sumParams['order']['puid'];
		
		//固定使用10*10面单
		$format = array(100, 100);
	
		//暂时
		$lableCount = 2;
	
		//设置默认字体
		$otherParams['pdfFont'] = 'msyh';
	
		$tmpX = 0;
		$tmpY = 0;
	
		//新建
		$pdf = new \TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, $format, true, 'UTF-8', false);
	
		//设置页边距
		$pdf->SetMargins(0, 0, 0);
	
		//删除预定义的打印 页眉/页尾
		$pdf->setPrintHeader(false);
		$pdf->setPrintFooter(false);
	
		//设置不自动换页,和底部间距为0
		$pdf->SetAutoPageBreak(false, 0);
	
		$pdf->AddPage();
	
		PrintPdfHelper::getItemsLablePubNotFind($tmpX, $tmpY, $pdf, $sumParams, $format, $lableCount, $otherParams);
		
		$pdfPath = self::savePDF2('', $puid, $sumParams['order']['order_id'].$sumParams['order']['customer_number']."_items_".time().'_'.rand(100,999), 'pdf', 'tcpdf', $pdf);
	
		return $pdfPath;
	}
	
	public static function getCarrierApiPDF($api_print_param){
// 		$api_print_param = json_decode($api_print_param, true);
		
		$tmp_SAA_obj = array('uid'=>$api_print_param['uid'], 'order_id'=>$api_print_param['order_id'], 'customer_number'=>$api_print_param['customer_number']);
		$SAA_obj = (object)$tmp_SAA_obj;
		
		$print_param = $api_print_param['print_param'];
		
		$class_name = '\common\api\carrierAPI\\'.$print_param['api_class'];
			
		$interface = new $class_name;

		//获取API PDF路径
		if(method_exists($interface,'getCarrierLabelApiPdf')){
			$pdfPath = $interface->getCarrierLabelApiPdf($SAA_obj, $print_param);
		}else{
			$pdfPath = ['error'=>1, 'msg'=>'该物流还没有对接getCarrierLabelApiPdf这个接口', 'filePath'=>''];
		}
	
		return $pdfPath;
	}
	
	public static function doPDFGenerate($taskDetail){
		if($taskDetail["type"] == "picking"){
			$pdfItemsPath = CarrierAPIHelper::getLabelItemsPDF_1($taskDetail['param']);
			
			return $pdfItemsPath;
		}else if($taskDetail["type"] == "api"){
			$pdfPath = CarrierAPIHelper::getCarrierApiPDF($taskDetail['param']);
			
			return $pdfPath;
		}
		
// 		//20170427
// 		var_dump($taskDetail);
		
		return ['error'=>1, 'msg'=>'类型不匹对', 'filePath'=>''];
	}
	
	//检查出口易新token
	public static function getChukouyiAccesstoken($account){		
		$result["success"]=1;
		$result["message"]="";
		$result["data"]="";
	
		//开发者测试账号

		//开发者正式账号
		// TODO carrier dev account @XXX@
	    $appId="@XXX@";
	    $secret="@XXX@";
		// 要到出口易开发者后台设置 redirect_uri 为 https://您的erp网址/carrier/carrier/chukouyi-auth-get
	    $tempu = parse_url(\Yii::$app->request->hostInfo);
	    $host = $tempu['host'];
	    $redirect_uri="https://{$host}/carrier/carrier/chukouyi-auth-get";
	    //openapi.chukou1.cn

		try{
			$params=$account->api_params;
			
			if(empty($params['AccessToken'])){
				$token=empty($params['token'])?"":$params['token'];
				$userKey=empty($params['userkey'])?$params['user_key']:$params['userkey'];
				
				//旧用户转一键迁移
				if(empty($token) || empty($userKey)){
					$result["success"]=0;
					$result["message"]="UserKey或者Token不正确";
					return $result;
				}
				
				$urlnew="https://openapi.chukou1.cn/OAuth2/developerauth?appId=".$appId."&secret=".$secret."&token=".$token."&userKey=".$userKey;
				$request2=Helper_Curl::post($urlnew);
							
				$a=json_decode($request2,true);

				if(isset($a['Code'])){
					$result["success"]=0;
					$result["message"]=$a['Message'];
					return $result;
				}
	
				$nextuptime=strtotime(date("Y-m-d H:i:s"))+86400*30;  //下次需要更新时间   $a['AccessTokenExpiresInMin']*60-86400
	
				$result["data"]=array(
						"AccessToken"=> $a['AccessToken'],
						"nextuptime"=>intval($nextuptime),
						"RefreshToken"=>$a['RefreshToken'],
				);  
				
			}
			else{
				//过期换新授权
				$grant_type="refresh_token";
				$urlnew="https://openapi.chukou1.cn/oauth2/token?client_id=".$appId."&client_secret=".$secret."&redirect_uri=".$redirect_uri."&grant_type=".$grant_type."&refresh_token=".$params['RefreshToken'];

				$request2=Helper_Curl::post($urlnew);		
				\Yii::info('chukouyi_change_token oldAccesstoken:'.json_encode($params).'，newAccesstoken:'.$request2, "file");
				$a=json_decode($request2,true);

				if(isset($a['Code'])){
					$result["success"]=0;
					$result["message"]=$a['Message'];
					return $result;
				}
				
				$nextuptime=strtotime(date("Y-m-d H:i:s"))+86400*30;  // 因为出口易时间又问题，变成1个月   $a['AccessTokenExpiresIn']*60-86400  下次需要更新时间,预定时间减一天
				
				$result["data"]=array(
						"AccessToken"=> $a['AccessToken'],
						"nextuptime"=>intval($nextuptime),
						"RefreshToken"=>$a['RefreshToken'],
				);
			}
			
			//保存入数据库
// 			$account = SysCarrierAccount::find()->where(['id'=>$account->id])->one();
// 			if(!empty($account)){
// 				$api_params = $account->api_params;
// 				$api_params['AccessToken'] = $result["data"]['AccessToken'];
// 				$api_params['next_time'] = $result["data"]['nextuptime'];
// 				$api_params['RefreshToken'] = $result["data"]['RefreshToken'];
// 				$account->api_params = $api_params;
// 				$account->save();
// 			}	

			$account = SysCarrierAccount::find()->where(['carrier_code'=>'lb_chukouyi'])->orWhere(['carrier_code'=>'lb_chukouyiOversea'])->all();
			if(!empty($account)){
				foreach ($account as $key=>$accountone){
					$api_params = $accountone->api_params;
					$api_params['AccessToken'] =$result["data"]['AccessToken'];
					$api_params['next_time'] = $result["data"]['nextuptime'];
					$api_params['RefreshToken'] = $result["data"]['RefreshToken'];
					$accountone->api_params = $api_params;
					$accountone->save();
				}
			}
		
			return $result;
		}
		catch (\Exception $err){
			$result["success"]=0;
			$result["message"]=$err->getMessage();
			return $result;
		}
	}

	//获取物流erp级别的物流信息
	public static function getSysCarrierAddiInfo($code,$keys=array()){
		$result=array(
				"code"=>1,
				"date"=>array(),
				"message"=>"",
		);
		
		try{
			
			$re = CarrierOpenHelper::getSysCarrierAddiInfo($code);
			if(empty($re)){
				$result["code"]=0;
				$result["message"]="物流不存在";
				return $result;
			}
	
			$addi_infos=$re['addi_infos'];
			$addi_infos_arr=json_decode($addi_infos,true);

			if(!empty($keys)){
				$key_arr=array();
				foreach ($keys as $key=>$keyone){
					$key_arr[$keyone]=empty($addi_infos_arr[$keyone])?"":$addi_infos_arr[$keyone];
				}
				$result['date']=$key_arr;
			}
			else{
				$result['date']=$re;
			}
			
			return $result;
		
		}
		catch (\Exception $err){
			$result["code"]=0;
			$result["message"]=$err->getMessage();
			return $result;
		}
		
	}
	
	//设置物流erp级别的物流信息
	public static function setSysCarrierAddiInfo($code,$pram=array()){
		$result=array(
				"code"=>1,
				"date"=>array(),
				"message"=>"",
		);
		
		try{
			if(empty($pram)){
				$result["code"]=0;
				$result["message"]="数据为空";
				return $result;
			}
			
			$re=CarrierOpenHelper::setSysCarrierAddiInfo($code,$pram);
			if(!$re){
				$result["code"]=0;
				$result["message"]="保存失败";
			}
			
			return $result;
			
		}catch (\Exception $err){
			$result["code"]=0;
			$result["message"]=$err->getMessage();
			return $result;
		}
		
	}
	

}

