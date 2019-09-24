<?php

namespace eagle\helpers;

use common\api\aliexpressinterfacev2\AliexpressInterface_Auth_V2;
use common\api\aliexpressinterfacev2\AliexpressInterface_Api_V2;
use Qiniu\json_decode;

class AliexpressApiPostHelper {

	public  static function AliexpressPost($api_name, $ret_type = ''){
		$api_name = ltrim($api_name, 'action');
		$ret_type = 'getResuult'.$ret_type;
		//Query参数
		if(empty($_GET['id'])){
			return self::$ret_type([], false, '101', 'can not find param [id]');
		}
		$selleruserid = $_GET['id'];
		
		try {
			//Body参数
			if(empty($GLOBALS['HTTP_RAW_POST_DATA'])){
				$param = $_GET;
				if(!empty($param['param1'])){
					$param['param1'] = json_decode($param['param1'], true);
				}
			}
			else{
				$param = json_decode(str_replace("&quot;", "\"", $GLOBALS['HTTP_RAW_POST_DATA']), true);
			}
			//$param = ['param1' => json_decode($_GET['param1'])];
			//$param = $_GET;
			
			// 获取token相关信息
			$time = time();
			$api = new AliexpressInterface_Api_V2 ();
			$access_token = $api->getAccessToken ( $selleruserid );
			//查询调用时间
			if(in_array($api_name, ['Getprintinfo'])){
				\Yii::info('AliexpressPost, getAccessToken, time: '.(time() - $time), "file");
			}
			if ($access_token) {
				if ($access_token == false){
					return self::$ret_type([], false, '102', 'invalid access_token');
				}else{
					$api->access_token = $access_token;
				}
	
				$time = time();
				
				$ret =  self::$api_name($api, $param, $ret_type);
				
				//查询调用时间
				if(in_array($api_name, ['Getprintinfo'])){
					\Yii::info('AliexpressPost, '.$api_name.', time: '.(time() - $time), "file");
				}
				
				return $ret;
	
			}else{
				return self::$ret_type([], false, 'sign-check-failure', 'Illegal request');
			}
		}catch (\Exception $ex){
			return self::$ret_type([], false, '103', $ex->getMessage());
		}
	}
	
	// 获取开展国内物流业务的物流公司
	public  static function Qureywlbdomesticlogisticscompany($api, $param, $ret_type){
		$data = $api->qureywlbdomesticlogisticscompany();
		if(!isset($data['result_list']['result'])){
			\Yii::info('Qureywlbdomesticlogisticscompany, error, '.json_encode($data),"file");
			
			return self::$ret_type([], false, $data['error_code'], 'result is null: '.$data['error_message']);
		}
		return self::$ret_type($data['result_list']['result'], true, '', '');
	}
	
	// 获取线上发货标签(线上物流发货专用接口)
	public  static function Getprintinfo($api, $param, $ret_type){
		if(empty($param['international_logistics_id'])){
			return self::$ret_type([], false, '101', 'can not find param [international_logistics_id]');
		}
		
		$time = time();
		$api_param = ['international_logistics_id' => $param['international_logistics_id']];
		$data = $api->getprintinfo($api_param);
		//\Yii::info('Getprintinfo, id:'.$param['international_logistics_id'].', diftime:'.(time() - $time).', '.json_encode($data),"file");
		
		if(empty($data)){
			\Yii::info('Getprintinfo, error, '.json_encode($data),"file");
			
			return self::$ret_type([], false, 0, 'result is null: ');
		}
		if(!empty($data['error_message'])){
			\Yii::info('Getprintinfo, error, '.json_encode($data),"file");
			
			return self::$ret_type([], false, (empty($data['error_code']) ? 0 : $data['error_code']), $data['error_message']);
		}
		$ret = json_decode($data, true);
		if(!empty($ret) && !empty($ret['error_desc'])){
			return self::$ret_type([], false, 1, $ret['error_desc']);
		}
		return self::$ret_type($data, true, 0, '');
	}
	
	// 交易订单详情查询
	public  static function Findorderbyid($api, $param, $ret_type){
		if(empty($param['param1'])){
			return self::$ret_type([], false, '101', 'can not find param [param1]');
		}
		$api_param = ['param1' => json_encode($param['param1'])];
// 		if(!empty($_GET['id']) && in_array($_GET['id'], ['cn1510671045', 'cn1512249397','cn1001633927', 'cn900867202', 'cn1520006787zlhg'])){
//             $data = $api->findorderbyidV2($api_param);
// 		}
// 		else{
// 			$data = $api->findorderbyid($api_param);
// 		}
		
		// dzt20190606 测试过后全部转接口
		$data = $api->findorderbyidV2($api_param);
		
		if(!empty($data['error_message'])){
			\Yii::info('Findorderbyid, error, '.json_encode($data),"file");
			
			return self::$ret_type($data, false, $data['error_code'], $data['error_message']);
		}
		else if(!isset($data['buyer_info'])){
			\Yii::info('Findorderbyid, error, '.json_encode($data),"file");
			
			return self::$ret_type($data, false, '105', 'result is null');
		}
		
		//清除多余的层
		$data = self::assembleOrderDetail($data);
		
		return self::$ret_type($data, true, '', '');
	}
	
	// 交易订单列表查询
	public  static function Findorderlistquery($api, $param, $ret_type){
		if(empty($param['param1'])){
			return self::$ret_type([], false, '101', 'can not find param [param1]');
		}
		
		//判断是否使用新列表查询接口
		if(isset($param['param1']['buyer_login_id']) && $param['param1']['buyer_login_id'] == 'new'){
			$param_v2 = array();
			if(!empty($param['param1']['create_date_start'])){
				$param_v2['create_date_start'] = date("Y-m-d H:i:s", strtotime($param['param1']['create_date_start']));
			}
			if(!empty($param['param1']['create_date_end'])){
				$param_v2['create_date_end'] = date("Y-m-d H:i:s", strtotime($param['param1']['create_date_end']));
			}
			if(!empty($param['param1']['modified_date_start'])){
				$param_v2['modified_date_start'] = date("Y-m-d H:i:s", strtotime($param['param1']['modified_date_start']));
			}
			if(!empty($param['param1']['modified_date_end'])){
				$param_v2['modified_date_end'] = date("Y-m-d H:i:s", strtotime($param['param1']['modified_date_end']));
			}
			if(!empty($param['param1']['page'])){
				$param_v2['current_page'] = $param['param1']['page'];
			}
			if(!empty($param['param1']['page_size'])){
				$param_v2['page_size'] = $param['param1']['page_size'];
			}
			$res = $api->getOrderList(['param_aeop_order_query' => json_encode($param_v2)]);
			if(!empty($res['error_message']) && strpos($res['error_message'], '操作成功') === false){
				\Yii::info('Findorderlistquery, error, '.json_encode($res),"file");
				
				return self::$ret_type($res, false, $res['error_code'], $res['error_message']);
			}
			else if(!isset($res['total_count'])){
				\Yii::info('Findorderlistquery, error, '.json_encode($res),"file");
			
				return self::$ret_type($res, false, '105', 'total_item is null');
			}
			else if($res['total_count'] > 0 && !isset($res['target_list'])){
				\Yii::info('Findorderlistquery, error, '.json_encode($res),"file");
					
				return self::$ret_type($res, false, '105', 'target_list is null');
			}
			
			$data = [
				'result_success' => true,
				'error_message' => '',
				'error_code' => 0,
				'total_item' => $res['total_count'],
				'order_list' => empty($res['target_list']) ? [] : $res['target_list'],
			];
		}
		else{
			$api_param = ['param1' => json_encode($param['param1'])];
			$data = $api->findorderlistquery($api_param);
	
			if(!empty($data['error_message'])){
				\Yii::info('Findorderlistquery, error, '.json_encode($data),"file");
				
				return self::$ret_type($data, false, $data['error_code'], $data['error_message']);
			}
			else if(!isset($data['total_item'])){
				\Yii::info('Findorderlistquery, error, '.json_encode($data),"file");
				
				return self::$ret_type($data, false, '105', 'total_item is null');
			}
			else if($data['total_item'] > 0 && !isset($data['order_list'])){
				\Yii::info('Findorderlistquery, error, order_list is null '.json_encode($data),"file");
			
				return self::$ret_type($data, false, '105', 'order_list is null');
			}
		}
		
		//清除多余的层
		$data = self::assembleOrderDetail($data);
		return self::$ret_type($data, true, '', '');
	}
	
	// 交易订单列表查询
	public  static function Customgetorder($api, $param, $ret_type){
	    if(empty($param['param1'])){
	        return self::$ret_type([], false, '101', 'can not find param [param1]');
	    }
		
	    // dzt20190725 for 自定义场景接口字段保存时候的类型总是保存失败，不是struct[]，所以这里做一下转换
	    $param = ['param1' => json_decode($param['param1'], true)];
	    
	    $param_v2 = array();
	    if(!empty($param['param1']['create_date_start'])){
	        $param_v2['create_date_start'] = date("Y-m-d H:i:s", strtotime($param['param1']['create_date_start']));
	    }
	    if(!empty($param['param1']['create_date_end'])){
	        $param_v2['create_date_end'] = date("Y-m-d H:i:s", strtotime($param['param1']['create_date_end']));
	    }
	    if(!empty($param['param1']['modified_date_start'])){
	        $param_v2['modified_date_start'] = date("Y-m-d H:i:s", strtotime($param['param1']['modified_date_start']));
	    }
	    if(!empty($param['param1']['modified_date_end'])){
	        $param_v2['modified_date_end'] = date("Y-m-d H:i:s", strtotime($param['param1']['modified_date_end']));
	    }
	    if(!empty($param['param1']['page'])){
	        $param_v2['current_page'] = $param['param1']['page'];
	    }
	    if(!empty($param['param1']['page_size'])){
	        $param_v2['page_size'] = $param['param1']['page_size'];
	    }
	    $res = $api->getOrderList(['param_aeop_order_query' => json_encode($param_v2)]);
	    if(!empty($res['error_message']) && strpos($res['error_message'], '操作成功') === false){
	        \Yii::info('Customgetorder, error, '.json_encode($res),"file");
	    
	        return self::$ret_type($res, false, $res['error_code'], $res['error_message']);
	    }
	    else if(!isset($res['total_count'])){
	        \Yii::info('Customgetorder, error, '.json_encode($res),"file");
	    
	        return self::$ret_type($res, false, '105', 'total_item is null');
	    }
	    else if($res['total_count'] > 0 && !isset($res['target_list'])){
	        \Yii::info('Customgetorder, error, '.json_encode($res),"file");
	    
	        return self::$ret_type($res, false, '105', 'target_list is null');
	    }
	    
	    $data = [
	            'result_success' => true,
	            'error_message' => '',
	            'error_code' => 0,
	            'total_item' => $res['total_count'],
	            'order_list' => empty($res['target_list']) ? [] : $res['target_list'],
	    ];
	
	    //清除多余的层
	    $data = self::assembleOrderDetail($data);
		return self::$ret_type($data, true, '', '');
	}
	
	// 查询物流追踪信息
	public  static function Querytrackingresult($api, $param, $ret_type){
		if(empty($param) || !is_array($param)){
			return self::$ret_type([], false, '101', 'can not find param', '');
		}
		//检测必填项
		$keys = ['logistics_no', 'origin', 'out_ref', 'service_name', 'to_area'];
		foreach($keys as $key){
			if(empty($param[$key])){
				return self::$ret_type([], false, '101', 'can not find param ['.$key.']', '');
			}
		}
		
		$data = $api->querytrackingresult($param);
	
		if(!empty($data['error_message'])){
			\Yii::info('Querytrackingresult, error, '.json_encode($data),"file");
			
			return self::$ret_type($data, false, $data['error_code'], $data['error_message'], '');
		}
		else if(!isset($data['details'])){
			\Yii::info('Querytrackingresult, error, '.json_encode($data),"file");
			
			return self::$ret_type($data, false, '105', 'details is null', '');
		}
	
		//清除多余的层
		$data = self::assembleOrderDetail($data);
	
		return self::$ret_type($data['details'], true, '', '');
	}
	
	// 查询物流订单信息
	public  static function Querylogisticsorderdetail($api, $param, $ret_type){
		if(empty($param) || !is_array($param)){
			return self::$ret_type([], false, '101', 'can not find param');
		}
		//检测必填项
		$keys = ['trade_order_id'];
		foreach($keys as $key){
			if(empty($param[$key])){
				return self::$ret_type([], false, '101', 'can not find param ['.$key.']');
			}
		}
	
		$data = $api->querylogisticsorderdetail($param);
		
		if(!empty($data['error_message'])){
			\Yii::info('Querylogisticsorderdetail, error, '.json_encode($data),"file");
			
			return self::$ret_type([], false, $data['error_code'], $data['error_message'], '');
		}
		else if(!isset($data['result_list'])){
			\Yii::info('Querylogisticsorderdetail, error, '.json_encode($data),"file");
			
			return self::$ret_type($data, false, '105', 'result_list is null');
		}
	
		//清除多余的层
		$data = self::assembleOrderDetail($data);
	
		return json_encode(['result' => $data]);
	}
	
	// 获取卖家地址
	public  static function Getlogisticsselleraddresses($api, $param, $ret_type){
		if(empty($param['seller_address_query'])){
			return self::$ret_type([], false, '101', 'can not find param [seller_address_query]');
		}
		
		$param['seller_address_query'] = is_array($param['seller_address_query']) ? implode(',', $param['seller_address_query']) : $param['seller_address_query'];
		$data = $api->getlogisticsselleraddresses($param);
	
		if(!empty($data['error_desc'])){
			\Yii::info('Getlogisticsselleraddresses, error, '.json_encode($data),"file");
			
			return self::$ret_type($data, false, $data['result_error_code'], $data['error_desc']);
		}
		else if(!empty($data['error_message'])){
			\Yii::info('Getlogisticsselleraddresses, error, '.json_encode($data),"file");
			
			return self::$ret_type([], false, $data['error_code'], $data['error_message'], '');
		}
	
		//清除多余的层
		$data = self::assembleOrderDetail($data);
	
		return json_encode($data);
	}
	
	// 列出平台所支持的物流服务列表
	public  static function Listlogisticsservice($api, $param, $ret_type){
		$data = $api->listlogisticsservice();
		if(!isset($data['result_list'])){
			\Yii::info('Listlogisticsservice, error, '.json_encode($data),"file");
			
			return self::$ret_type([], false, $data['error_code'], 'result_list is null: '.$data['error_message']);
		}
		//清除多余的层
		$data = self::assembleOrderDetail($data);
		return self::$ret_type($data['result_list'], true, '', '');
	}
	
	// 声明发货接口
	public  static function Sellershipmentfortop($api, $param, $ret_type){
		if(empty($param) || !is_array($param)){
			return self::$ret_type([], false, '101', 'can not find param', '');
		}
		//检测必填项
		$keys = ['logistics_no', 'send_type', 'out_ref', 'service_name'];
		foreach($keys as $key){
			if(empty($param[$key])){
				return self::$ret_type([], false, '101', 'can not find param ['.$key.']', '');
			}
		}
	
		$data = $api->sellershipmentfortop($param);
	
		if(!empty($data['error_message'])){
			\Yii::info('Sellershipmentfortop, error, '.json_encode($data),"file");
			
			return self::$ret_type([], false, $data['error_code'], $data['error_message'], '');
		}
	
		return self::$ret_type([], $data['result_success'], $data['result_error_code'], $data['result_error_desc']);
	}
	
	// 修改声明发货
	public  static function Sellermodifiedshipmentfortop($api, $param, $ret_type){
		if(empty($param) || !is_array($param)){
			return self::$ret_type([], false, '101', 'can not find param', '');
		}
		//检测必填项
		$keys = ['old_logistics_no', 'new_logistics_no', 'send_type', 'out_ref', 'old_service_name', 'new_service_name'];
		foreach($keys as $key){
			if(empty($param[$key])){
				return self::$ret_type([], false, '101', 'can not find param ['.$key.']', '');
			}
		}
	
		$data = $api->sellermodifiedshipmentfortop($param);
	
		if(!empty($data['error_message'])){
			\Yii::info('Sellermodifiedshipmentfortop, error, '.json_encode($data),"file");
			
			return self::$ret_type([], false, $data['error_code'], $data['error_message'], '');
		}
	
		return self::$ret_type([], $data['result_success'], $data['result_error_code'], $data['result_error_desc']);
	}
	
	// 创建线上物流订单
	public  static function Createwarehouseorder($api, $param, $ret_type){
		if(empty($param) || !is_array($param)){
			return self::$ret_type([], false, '101', 'can not find param');
		}
		//检测必填项
		$keys = ['address_d_t_os', 'declare_product_d_t_os', 'domestic_logistics_company_id', 'domestic_tracking_no', 'trade_order_from', 'trade_order_id', 'warehouse_carrier_service'];
		foreach($keys as $key){
			if(empty($param[$key])){
				return self::$ret_type([], false, '101', 'can not find param ['.$key.']');
			}
		}
		if(is_array($param['declare_product_d_t_os'])){
			$param['declare_product_d_t_os'] = json_encode($param['declare_product_d_t_os']);
		}
		if(is_array($param['address_d_t_os'])){
			$param['address_d_t_os'] = json_encode($param['address_d_t_os']);
		}
	
		$data = $api->createwarehouseorder($param);
		\Yii::info('Createwarehouseorder, '.(empty($param['trade_order_id']) ? '' : $param['trade_order_id']).', '.json_encode($data),"file");
	
		if(!empty($data['error_message'])){
			\Yii::info('Createwarehouseorder, error, '.json_encode($data),"file");
			
			return self::$ret_type($data, false, $data['error_code'], $data['error_desc']);
		}
	
		\Yii::info('Createwarehouseorder, '.json_encode(self::$ret_type($data, $data['success'], $data['error_code'], $data['error_desc'])),"file");
		return self::$ret_type($data, $data['success'], $data['error_code'], $data['error_desc']);
	}
	
	// 面单云打印
	public  static function Getpdfsbycloudprint($api, $param, $ret_type){
		if(empty($param) || !is_array($param)){
			return self::$ret_type([], false, '101', 'can not find param');
		}
		//检测必填项
		$keys = ['warehouse_order_query_d_t_os'];
		foreach($keys as $key){
			if(empty($param[$key])){
				return self::$ret_type([], false, '101', 'can not find param ['.$key.']');
			}
		}
		if(is_array($param['warehouse_order_query_d_t_os'])){
			$param['warehouse_order_query_d_t_os'] = json_encode($param['warehouse_order_query_d_t_os']);
		}
		if(isset($param['print_detail'])){
			if($param['print_detail'] === true)
				$param['print_detail'] = 'true';
			else if($param['print_detail'] === false)
				$param['print_detail'] = 'false';
		}
	
		//\Yii::info('Getpdfsbycloudprint, request, '.json_encode($param),"file");
		$data = $api->getpdfsbycloudprint($param);
	
		if(!empty($data['error_message'])){
			\Yii::info('Getpdfsbycloudprint, error, '.json_encode($data),"file");
			
			return self::$ret_type($data, false, $data['error_code'], $data['error_message']);
		}
		
		//清除多余的层
		$data = self::assembleOrderDetail($data);
	
		return self::$ret_type($data, $data['success'], $data['error_code'], $data['error_message']);
	}
	
	// 获取单个产品信息
	public  static function Findaeproductbyid($api, $param, $ret_type){
		if(empty($param['product_id'])){
			return self::$ret_type([], false, '101', 'can not find param [product_id]');
		}
		$data = $api->findaeproductbyid($param);
	
		if(!empty($data['error_message'])){
			\Yii::info('Findaeproductbyid, error, '.json_encode($data),"file");
			
			return self::$ret_type($data, false, $data['error_code'], $data['error_message']);
		}
		else if(!isset($data['aeop_ae_product_s_k_us'])){
			\Yii::info('Findaeproductbyid, error, '.json_encode($data),"file");
			
			return self::$ret_type($data, false, '105', 'result is null');
		}
	
		//清除多余的层
		$data = self::assembleOrderDetail($data);
		
		return self::$ret_type($data, true, '', '');
	}
	
	// 商品列表查询接口
	public  static function Findproductinfolistquery($api, $param, $ret_type){
		if(empty($param['aeop_a_e_product_list_query'])){
			return self::$ret_type([], false, '101', 'can not find param [aeop_a_e_product_list_query]');
		}
		$param = ['aeop_a_e_product_list_query' => json_encode($param['aeop_a_e_product_list_query'])];
		$data = $api->findproductinfolistquery($param);
		
		if(!empty($data['error_message'])){
			\Yii::info('Findproductinfolistquery, error, '.json_encode($data),"file");
			
			return self::$ret_type($data, false, $data['error_code'], $data['error_message']);
		}
		else if(!isset($data['aeop_a_e_product_display_d_t_o_list'])){
			\Yii::info('Findproductinfolistquery, error, '.json_encode($data),"file");
			
			return self::$ret_type($data, false, '105', 'result is null');
		}
	
		//清除多余的层
		$data = self::assembleOrderDetail($data);
	
		return self::$ret_type($data, true, '', '');
	}
	
	// 新增站内信/订单留言(NEW)2.0
	public  static function Addmsg($api, $param, $ret_type){
		if(empty($param['create_param'])){
			return self::$ret_type([], false, '101', 'can not find param [create_param]');
		}
		$param = ['create_param' => json_encode($param['create_param'])];
		
		\Yii::info('Addmsg, request, '.json_encode($param),"file");
		$data = $api->addmsgV2($param);
		\Yii::info('Addmsg, reponse, '.json_encode($data),"file");
		
		if(!empty($data['error_msg']) && $data['error_msg'] != 'success!'){
			\Yii::info('Addmsg, error, '.json_encode($data),"file");
			
			return self::$ret_type($data, false, $data['sub_error_code'], $data['error_msg']);
		}
	
		return self::$ret_type($data, true, '', '');
	}
	
	// 根据买家ID获取站内信对话ID
	public  static function Querymsgchannelidbybuyerid($api, $param, $ret_type){
		if(empty($param['buyer_id'])){
			return self::$ret_type([], false, '101', 'can not find param [buyer_id]');
		}
		$data = $api->querymsgchannelidbybuyerid($param);
	
		if(!empty($data['error_msg'])){
			\Yii::info('Querymsgchannelidbybuyerid, error, '.json_encode($data),"file");
			
			return self::$ret_type($data, false, $data['sub_error_code'], $data['error_msg']);
		}
		else if(!isset($data['channel_id'])){
			\Yii::info('Querymsgchannelidbybuyerid, error, '.json_encode($data),"file");
			
			return self::$ret_type($data, false, '105', 'channel_id is null');
		}
	
		return self::$ret_type($data['channel_id'], true, '', '');
	}
	
	// V2.0站内信/订单留言获取关系列表
	public  static function Querymsgrelationlist($api, $param, $ret_type){
		if(empty($param['query'])){
			return self::$ret_type([], false, '101', 'can not find param [query]');
		}
		$param = ['query' => json_encode($param['query'])];
		$data = $api->queryMsgRelationListV2($param);
	
		if(!empty($data['error_msg'])){
			\Yii::info('Querymsgrelationlist, error, '.json_encode($data),"file");
			
			return self::$ret_type($data, false, $data['error_code'], $data['error_msg']);
		}
	
		//清除多余的层
		$data = self::assembleOrderDetail($data);
		
		return self::$ret_type($data, true, '', '');
	}
	
	// V2.0站内信/订单留言查询详情列表
	public  static function Querymsgdetaillist($api, $param, $ret_type){
		if(empty($param) || !is_array($param)){
			return self::$ret_type([], false, '101', 'can not find param', '');
		}
		//检测必填项
		$keys = ['channel_id', 'page_size', 'current_page'];
		foreach($keys as $key){
			if(empty($param[$key])){
				return self::$ret_type([], false, '101', 'can not find param ['.$key.']', '');
			}
		}
		$data = $api->queryMsgDetailListV2($param);
		
		if(!empty($data['error_msg'])){
			\Yii::info('Querymsgdetaillist, error, '.json_encode($data),"file");
			
			return self::$ret_type($data, false, $data['error_code'], $data['error_msg']);
		}
		else if(!isset($data['message_detail_list'])){
			\Yii::info('Querymsgdetaillist, error, '.json_encode($data),"file");
			
			return self::$ret_type($data, false, 105, 'message_detail_list is null');
		}
		
		//清除多余的层
		$data = self::assembleOrderDetail($data);
		
		return self::$ret_type($data, true, 0, '');
	}
	
	// 延长买家收货时间 
	public  static function Extendsbuyeracceptgoodstime($api, $param, $ret_type){
		if(empty($param) || !is_array($param)){
			return self::$ret_type([], false, '101', 'can not find param', '');
		}
		//检测必填项
		$keys = ['param0', 'param1'];
		foreach($keys as $key){
			if(empty($param[$key])){
				return self::$ret_type([], false, '101', 'can not find param ['.$key.']', '');
			}
		}
	
		\Yii::info('Extendsbuyeracceptgoodstime, request, '.json_encode($param),"file");
		$data = $api->extendsbuyeracceptgoodstime($param);
		\Yii::info('Extendsbuyeracceptgoodstime, result, '.json_encode($data),"file");
	
		if(!empty($data['error_message'])){
			\Yii::info('Extendsbuyeracceptgoodstime, error, '.json_encode($data),"file");
				
			return self::$ret_type([], false, $data['error_code'], $data['error_message'], '');
		}
	
		return self::$ret_type([], $data['result_success'], $data['error_code'], $data['error_message']);
	}
	
	// 卖家对未评价的订单进行评价 
	public  static function Savesellerfeedback($api, $param, $ret_type){
		if(empty($param['param1'])){
			return self::$ret_type([], false, '101', 'can not find param [param1]');
		}
		$param = ['param1' => json_encode($param['param1'])];
		
		\Yii::info('Savesellerfeedback, request, '.json_encode($param),"file");
		$data = $api->savesellerfeedback($param);
		\Yii::info('Savesellerfeedback, result, '.json_encode($data),"file");
	
		if(!empty($data['error_message'])){
			\Yii::info('Savesellerfeedback, error, '.json_encode($data),"file");
	
			return self::$ret_type([], false, $data['error_code'], $data['error_message'], '');
		}
	
		return self::$ret_type([], $data['result_success'], $data['error_code'], $data['error_message']);
	}
	
	// 批量获取线上单独的发货标签
	public  static function Getaloneprintinfos($api, $param, $ret_type){
		if(empty($param['international_logistics_ids'])){
			return self::$ret_type([], false, '101', 'can not find param [international_logistics_ids]');
		}
		$international_logistics_id_list = explode(';', $param['international_logistics_ids']);
	
		$result = array();
		foreach($international_logistics_id_list as $international_logistics_id){
			$api_param = ['international_logistics_id' => $international_logistics_id];
			$data = $api->getprintinfo($api_param);
			
			if(empty($data)){
				\Yii::info('Getaloneprintinfos, error, '.$international_logistics_id.', '.json_encode($data),"file");
			
				return self::$ret_type([], false, 0, $international_logistics_id.', result is null: ');
			}
			if(!empty($data['error_message'])){
				\Yii::info('Getaloneprintinfos, error, '.$international_logistics_id.', '.json_encode($data),"file");
			
				return self::$ret_type([], false, (empty($data['error_code']) ? 0 : $data['error_code']), $international_logistics_id.', '.$data['error_message']);
			}
			$ret = json_decode($data, true);
			if(!empty($ret) && !empty($ret['error_desc'])){
				return self::$ret_type([], false, 1, $international_logistics_id.', '.$ret['error_desc']);
			}
			
			$result[] = [
				'international_logistics_id' => $international_logistics_id,
				'pdf_body' => $data,
			];
		}
	
		
		return self::$ret_type($result, true, 0, '');
	}
	
	// 为已授权的用户开通消息服务
	public  static function Taobaotmcuserpermit($api, $param, $ret_type){
		
		//$param = ['topics' => 'aliexpress_order_PlaceOrderSuccess,aliexpress_order_RiskControl,aliexpress_order_WaitSellerSendGoods,aliexpress_order_SellerPartSendGoods,aliexpress_order_WaitBuyerAcceptGoods,aliexpress_order_InCancel'];
		$data = $api->tmcUserPermit($param);
		$data['error_code'] = isset($data['error_code']) ? $data['error_code'] : '';
	
		if(!empty($data['error_message'])){
			\Yii::info('Taobaotmcuserpermit, error, '.json_encode($data),"file");
	
			return self::$ret_type([], false, $data['error_code'], $data['error_message']);
		}
	
		return self::$ret_type([], $data['result_success'], $data['error_code'], $data['error_message']);
	}
	
	// 消费多条消息
	public  static function Taobaotmcmessagesconsume($api, $param, $ret_type){
	
		$data = $api->tmcMessagesConsume($param);
		$data['error_code'] = isset($data['error_code']) ? $data['error_code'] : '';
		
		if(!empty($data['error_message'])){
			\Yii::info('Taobaotmcmessagesconsume, error, '.json_encode($data),"file");
	
			return self::$ret_type([], false, $data['error_code'], $data['error_message']);
		}
		else if(empty($data['tmc_message'])){
			return self::$ret_type([], false, $data['error_code'], $data['error_message']);
		}
	
		return self::$ret_type($data['tmc_message'], true, $data['error_code'], $data['error_message']);
	}
	
	// 确认消费消息的状态
	public  static function Taobaotmcmessagesconfirm($api, $param, $ret_type){
	
		$data = $api->tmcMessagesConfirm($param);
		$data['error_code'] = isset($data['error_code']) ? $data['error_code'] : '';
	
		if(!empty($data['error_message'])){
			\Yii::info('Taobaotmcmessagesconfirm, error, '.json_encode($data),"file");
	
			return self::$ret_type([], false, $data['error_code'], $data['error_message']);
		}
	
		return self::$ret_type([], $data['result_success'], $data['error_code'], $data['error_message']);
	}
	
	// 获取用户已开通消息
	public  static function Taobaotmcuserget($api, $param, $ret_type){
	
		$data = $api->tmcUserGet($param);
		$data['error_code'] = isset($data['error_code']) ? $data['error_code'] : '';
	
		if(!empty($data['error_message'])){
			\Yii::info('Taobaotmcmessagesconfirm, error, '.json_encode($data),"file");
	
			return self::$ret_type($data, false, $data['error_code'], $data['error_message']);
		}
		if(empty($data['tmc_user'])){
			\Yii::info('Taobaotmcmessagesconfirm, error, '.json_encode($data),"file");
		
			return self::$ret_type($data, false, $data['error_code'], 'tmc_user is null');
		}
	
		return self::$ret_type($data['tmc_user'], $data['result_success'], $data['error_code'], $data['error_message']);
	}
	
	// 取消用户的消息服务
	public  static function Taobaotmcusercancel($api, $param, $ret_type){
	
		$data = $api->tmcUserCancel($param);
		$data['err_code'] = isset($data['err_code']) ? $data['err_code'] : '';
	
		if(!empty($data['err_msg'])){
			\Yii::info('Taobaotmcmessagesconfirm, error, '.json_encode($data),"file");
	
			return self::$ret_type([], false, $data['err_code'], $data['err_msg']);
		}
	
		return self::$ret_type([], $data['result_success'], $data['err_code'], $data['err_msg']);
	}
	
	public static function getResuult($data, $success, $code, $msg){
		return json_encode(['result_list' => $data, 'result_success' => $success, 'error_code' => $code, 'error_desc' => $msg ]);
	}
	
	public static function getResuult2($data, $success, $code, $msg){
		if(empty($data)){
			return json_encode(['result_success' => $success, 'error_code' => $code, 'error_message' => $msg ]);
		}
		else{
			return json_encode(['result' => $data, 'result_success' => $success, 'error_code' => $code, 'error_message' => $msg ]);
		}
	}
	
	public static function getResuult3($data, $success, $code, $msg, $official_website = ''){
		return json_encode(['details' => $data, 'result_success' => $success, 'error_code' => $code, 'error_desc' => $msg , 'official_website' => $official_website ]);
	}
	
	public static function getResuult4($data, $success, $code, $msg){
		$data['success'] = $success;
		$data['error_code'] = $code;
		$data['error_desc'] = $msg;
		return json_encode(['result' => $data]);
	}
	
	public static function getResuult5($data, $success, $code, $msg){
		$data['result_success'] = $success;
		$data['result_error_code'] = $code;
		$data['error_desc'] = $msg;
		return json_encode($data);
	}
	
	public static function getResuult6($data, $success, $code, $msg){
		return json_encode(['result' => $data, 'result_success' => $success, 'error_code' => $code, 'error_desc' => $msg ]);
	}
	
	public static function getResuult7($data, $success, $code, $msg){
		return json_encode(['result_success' => $success, 'result_error_code' => $code, 'result_error_desc' => $msg ]);
	}
	
	public static function getResuult8($data, $success, $code, $msg){
		$data['success'] = $success;
		$data['error_code'] = empty($code) ? 0 : $code;
		$data['error_desc'] = $msg;
		return json_encode(['result_success' => $success, 'result' => $data]);
	}
	
	public static function getResuult9($data, $success, $code, $msg){
		$data['success'] = $success;
		$data['error_code'] = $code;
		$data['error_message'] = $msg;
		return json_encode(['result' => $data]);
	}
	
	public static function getResuult10($data, $success, $code, $msg){
		return json_encode(['channel_id' => $data, 'result_success' => $success, 'error_code' => $code, 'error_message' => $msg ]);
	}
	
	public static function getResuult11($data, $success, $code, $msg){
		return json_encode(['result' => $data, 'result_success' => $success, 'error_code' => $code, 'error_message' => $msg ]);
	}
	
	public static function getResuult12($data, $success, $code, $msg){
	    if(empty($data)){
	        return json_encode(['result_success' => $success, 'err_code' => $code, 'err_msg' => $msg ]);
	    }
	    else{
	        return json_encode(['result' => $data, 'result_success' => $success, 'err_code' => $code, 'err_msg' => $msg ]);
	    }
	}
	
	//订单接口，清除多余的层
	public static $assembleCos = ['aeop_ae_product_propertys', 'aeop_ae_product_s_k_us'];
	public static function assembleOrderDetail($ordersDetail, $times = 3){
		if($times > 0){
			foreach($ordersDetail as $key => $item){
				if(is_array($item) && !empty($item)){
					if(!is_numeric($key)){
						if((strpos($key,'list') !== false || strpos($key,'details') !== false || in_array($key, self::$assembleCos))){
							$ordersDetail[$key] = current($item);
						}
						else if(in_array($key, ['group_ids']) && !empty($item['number'])){
							$ordersDetail[$key] = current($item);
						}
					}
					//继续清除下一级
					if(is_array($ordersDetail[$key])){
						$ordersDetail[$key] = self::assembleOrderDetail($ordersDetail[$key], $times - 1);
					}
				}
				
				if(in_array($key, ['loan_info', 'file_path_list', 'refund_info', 'escrow_fee']) && empty($item)){
					unset($ordersDetail[$key]);
				}
			}
		}
		return $ordersDetail;
	}
	
	public static function getLaFormatTime($format, $timestamp){
		$dt = new \DateTime();
		$dt->setTimestamp($timestamp);
		$dt->setTimezone(new \DateTimeZone('America/Los_Angeles'));
		return $dt->format($format);
	
	
	}

}