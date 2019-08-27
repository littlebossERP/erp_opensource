<?php

namespace eagle\modules\order\helpers;

use \Yii;
//use eagle\models\SaasAmazonAutosync;
use eagle\modules\platform\apihelpers\AmazonAccountsApiHelper;
use common\helpers\Helper_Array;
use eagle\modules\order\models\OdOrderItem;
//use eagle\models\QueueAliexpressGetorder;
use eagle\modules\order\models\OdOrder;
use eagle\modules\util\helpers\TranslateHelper;
//use common\api\aliexpressinterface\AliexpressInterface_Api;
use eagle\modules\manual_sync\models\Queue;
//use common\api\aliexpressinterface\AliexpressInterface_Auth;
//use eagle\models\QueueAliexpressGetorder4;
//use common\api\aliexpressinterface\AliexpressInterface_Helper;
use eagle\models\SaasAmazonUser;
use eagle\modules\order\models\OdOrderShipped;
use eagle\modules\util\helpers\ImageCacherHelper;

class AmazonOrderHelper{
	public static $amzapiVer = '2_2';

/**
 * [getOrderSyncInfoDataList 过滤获取amazon订单同步情况数据]
 * @Author   willage
 * @DateTime 2016-06-29T10:49:18+0800
 * @param    string                   $status   [指定状态筛选 （可选）]
 * @param    string                   $lasttime [指定时间筛选 （可选）]
 * @return   array                             [array ('message'=>执行详细结果
 * 				                                        'success'=> true 成功 false 失败	)]
 */
//////////////////////////////////////////////////////////////////////
//获取账号下所有'is_active' --> 遍历所有amazon_uid，并按参数过滤 --> 找到所有，返回 //
////////////////////////////////////////////////////////////////////
	static public function getOrderSyncInfoDataList($status = '' , $lasttime =''){
		//小老板账号可以有多个amazon平台account，调用接口/modules/platform/apihelper/
		$AccountList = AmazonAccountsApiHelper::listActiveAccounts();
		$syncList = [];
		//$model = new SaasAmazonAutosync();
		foreach($AccountList as $account){
			if (!empty($account['amazon_uid'])){
				$detail = AmazonAccountsApiHelper::getLastOrderSyncDetail($account['amazon_uid']);
				if (!empty($detail['success']) && !empty($detail['result'])){
					//状态过滤
					// if ($status !=''){
					// 	//如果status 是有效的值， 则表示用户使用了过滤
					// 	if ( $detail['result']['status'] != $status) {
					// 		continue;
					// 	}
					// }
					// //时间 过滤
					// if  ($lasttime != ""){
					// 	if ( $detail['result']['last_time'] > $lasttime) {
					// 		continue;
					// 	}
					// }
					$syncList[$account['amazon_uid']] = $detail['result'];
					//var_dump($detail['result']);
				}else{
					//what is this?
					// if ($status == '' )
					// 	$syncList[$account['amazon_uid']] = $model->attributes;
				}
			}
		}
		//var_dump($AccountList);
		//var_dump($syncList);
		//exit;
		return $syncList;
	}//end getOrderSyncInfoDataList
/**
 * [getAmazonCurrentOperationList description]
 * @Author   willage
 * @DateTime 2016-06-30T17:11:38+0800
 * @param    [type]                   $code [description]
 * @param    string                   $type [description]
 * @return   [type]                         [description]
 */
	static public function getAmazonCurrentOperationList($code , $type="s"){
		$OpList = OrderHelper::getCurrentOperationList($code,$type);
		if (isset($OpList['givefeedback'])) {
			unset($OpList['givefeedback']);//去掉“给买家好评”
		}
		$temp = [ 'signshipped'=>'虚拟发货(标记发货)'];
		//把“虚拟发货”放到第一位
		self::array_insert($OpList,1,$temp);
		//var_dump($OpList);
		switch ($code){
			case OdOrder::STATUS_PAY:
				break;
			case OdOrder::STATUS_WAITSEND:
				break;
			case OdOrder::STATUS_SHIPPED:
				if (isset($OpList['signcomplete'])) {
					unset($OpList['signcomplete']);//去掉“已出库订单补发”
				}
				// if (isset($OpList['checkorder'])) {
				// 	unset($OpList['checkorder']);//去掉“检测订单”
				// }
				break;

		}
		if ($type =='b') {
			switch ($code) {
				case OdOrder::STATUS_PAY:
					// if (isset($OpList['checkorder'])) {
					// 	unset($OpList['checkorder']);//去掉“检测订单”
					// }
					break;
				case OdOrder::STATUS_SHIPPED:
					break;
				default:
					$OpList += [ 'checkorder'=>'检测订单'];
					break;
			}

		}
		if ($type =='s'){
			$OpList += ['invoiced' => '发票'];
			$OpList += ['updateImage' => '更新图片缓存'];
		}
		
		$tmp_is_show = true;
		if($code == ''){
			$tmp_is_show = \eagle\modules\order\helpers\OrderListV3Helper::getIsShowMenuAllOtherOperation();
		}
		
		if($tmp_is_show == false){
			unset($OpList['signshipped']);
			unset($OpList['checkorder']);
		}
		
		//var_dump($OpList);
		return $OpList;
	}//end getAmazonCurrentOperationList

/**
 * [getMenuStatisticData description]
 * @Author   willage
 * @DateTime 2016-06-30T18:28:59+0800
 * @param    array                    $params   [description]
 * @param    string                   $platform [description]
 * @return   [type]                             [description]
 */
	static public function getMenuStatisticData($params=[],$platform='amazon'){
		$counter = OrderHelper::getMenuStatisticData($platform,$params);

		return $counter;
	}//end getMenuStatisticData
/**
 * [orderListDataSearch 用于过滤订单list]
 * @Author   willage
 * @DateTime 2016-07-01T15:44:19+0800
 * @param    array                   $request [要求过滤的数据key]
 * @param    array 					 $orderData[订单源数据]
 * @return   array                            [description]
 */
	static public function orderListDataSearch($request,$orderData){
		///////////////////////////
		//查询 要求<来着form data> 的订单 //
		///////////////////////////

		///////////////////////////////////////////////////
		//查询 要求<来着query string parameters(链接地址带的参数)> 的订单 //
		///////////////////////////////////////////////////
		$showsearch = 0;
		$op_code ='';
		if (!empty($request['order_status'])){
			//搜索订单状态
			$orderData->andWhere('order_status = :os',[':os'=>$request['order_status']]);
			//生成操作下拉菜单的code
			$op_code = $request['order_status'];
		}
		if (!empty($request['order_type'])){
			//搜索amazon特有MFN/AFN
			$orderData->andWhere('order_type = :ot',[':ot'=>$request['order_type']]);
			$showsearch=1;
		}
		if (!empty($request['order_capture'])){
			//手工订单查询
			$orderData->andWhere(['order_capture'=>$request['order_capture']]);
			$showsearch=1;
		}
		if (!empty($request['exception_status'])){
			//搜索异常状态
			if ($request['exception_status'] == '0'){
				//已付款订单处理 , 默认为待检测
				$orderData->andWhere('exception_status = :es',[':es'=>$request['exception_status']]);
				$orderData->andWhere('order_status = :os',[':os'=>OdOrder::STATUS_PAY]);
				//生成操作下拉菜单的code
				$op_code = OdOrder::STATUS_PAY;
			}elseif(!empty($request['exception_status'])){
				//非默认状态
				$orderData->andWhere('exception_status = :es',[':es'=>$request['exception_status']]);
				//生成操作下拉菜单的code
				$op_code = $request['exception_status'];
			}
		}
		// if (!empty($request['is_manual_order'])){
		// 	//搜索订单挂起状态
		// 	$orderData->andWhere('is_manual_order = :os',[':os'=>$request['is_manual_order']]);
		// }
		if (!empty($request['cangku'])){
			//搜索仓库
			// $orderData->andWhere('default_warehouse_id = :warehouse_id',[':warehouse_id'=>$request['cangku']]);
			// $showsearch=1;
			//搜索仓库
			$orderData->andWhere('default_warehouse_id = :dwi',[':dwi'=>$_REQUEST['cangku']]);
			$showsearch=1;
		}
		if (!empty($request['shipmethod'])){
			//搜索运输服务
			// $orderData->andWhere('default_shipping_method_code = :shipmethod',[':shipmethod'=>$request['shipmethod']]);
			//搜索运输服务
			$orderData->andWhere('default_shipping_method_code = :dsmc',[':dsmc'=>$request['shipmethod']]);
			$showsearch=1;
		}
		// if (!empty($request['fuhe'])){
		// 	//搜索符合条件
		// 	$orderData->andWhere('order_source_status = :fuhe',[':fuhe'=>$request['fuhe']]);
		// 	$showsearch=1;
		// }

		if (!empty($request['order_source_status'])){
			//Amazon状态
			if ($request['order_source_status'] == 'CUSTOM_WAIT_SEND_MOENY'){
				//部分发货 , 等待买家收货 , 等待您确认金额
				$orderData->andWhere(['order_source_status'=>['SELLER_PART_SEND_GOODS' , 'WAIT_BUYER_ACCEPT_GOODS','WAIT_SELLER_EXAMINE_MONEY']]);
			}else{
				$orderData->andWhere('order_source_status = :order_source_status',[':order_source_status'=>$request['order_source_status']]);
			}
			$showsearch=1;
		}
		/* 订单系统标签 查询 start*/
		$sysTagList = [];
		foreach(OrderTagHelper::$OrderSysTagMapping as $tag_code=>$label){
			//1.勾选了系统标签；
			if (!empty($_REQUEST[$tag_code]) ){
				//生成 tag 标签的数组
				$sysTagList[] = $tag_code;
			}
			if (isset($_REQUEST[$tag_code])){
				$showsearch=1;
			}
		}
		if  (!empty($sysTagList)){
			$showsearch=1;
			if (! empty($_REQUEST['is_reverse'])){
				//取反操作
				$reverseStr = "not ";
			}else{
				$reverseStr = "";
			}
			$orderData->andWhere([$reverseStr.'in', 'order_id', (new \yii\db\Query())->select('order_id')->from('od_order_systags_mapping')->where(['tag_code' => $sysTagList])]);
		}
		/* 订单系统标签 查询 end*/
		// if (!empty($request['searchval'])){
		// 	//搜索用户自选搜索条件
		// 	if (in_array($request['keys'], ['order_id','amazon_orderid','buyeid','consignee'])){
		// 		$kv=[
		// 			'order_id'=>'order_id',
		// 			'amazon_orderid'=>'order_source_order_id',
		// 			'buyeid'=>'source_buyer_user_id',
		// 			'consignee'=>'consignee'
		// 		];
		// 		$key = $kv[$request['keys']];
		// 		$searchval = $request['searchval'];
		// 		// 客户不小心用amazon 订单号搜索小老板订单时，amazon 订单号字符串会 被截取第一个"-"前的数字，这样导致搜索到不同的eagle订单
		// 		// 如： 用102-0375305-1663450，搜索小老板订单的话，数据库会截取102来搜索。
		// 		if('order_id' == $key){
		// 			if(intval($searchval) != $searchval){
		// 				$searchval = "";
		// 			}
		// 		}
		// 		$orderData->andWhere("$key = :val",[':val'=>$searchval]);
		// 	}elseif ($request['keys']=='sku'){
		// 		$ids = Helper_Array::getCols(OdOrderItem::find()->where('sku = :sku',[':sku'=>$request['searchval']])->select('order_id')->asArray()->all(),'order_id');
		// 		$orderData->andWhere(['IN','order_id',$ids]);
		// 	}elseif ($request['keys']=='itemid'){

		// 	}elseif ($request['keys']=='tracknum'){
		// 		$ids = Helper_Array::getCols(OdOrderShipped::find()->where('tracking_number = :tn',[':tn'=>$request['searchval']])->select('order_id')->asArray()->all(),'order_id');
		// 		$orderData->andWhere(['IN','order_id',$ids]);
		// 	}
		// }
		if (!empty($request['sel_tag'])){
			//搜索卖家账号
			//自定义标签 查询
			if  (!empty($request['sel_tag'])){
				if (is_string($request['sel_tag'])){
					$customTagList = explode(",", $request['sel_tag']);
				}elseif(is_array($request['sel_tag'])){
					$customTagList = $request['sel_tag'];
				}else{
					$customTagList = [];
				}
				if (!empty($customTagList)){
					foreach($customTagList as  $row){
						$orderData->andWhere('customized_tag_'.$row.' ="Y" ');
					}
				}
				
				//$query->andWhere('order_id in (select order_id from lt_order_tags where tag_id in ('.implode(",", $other_params['custom_tag']).')) ');
			}
			$showsearch=1;
		}
		// if (!empty($request['selleruserid'])){
		// 	//搜索卖家账号
		// 	$orderData->andWhere('selleruserid = :s',[':s'=>$request['selleruserid']]);
		// }
		if (!empty($request['order_evaluation'])){
			//评价
			$orderData->andWhere('order_evaluation = :order_evaluation',[':order_evaluation'=>$request['order_evaluation']]);
			$showsearch=1;
		}
		if (!empty($request['reorder_type'])){
			if ($request['reorder_type'] != 'all'){
				//重新发货类型
				$orderData->andWhere('reorder_type =:reorder_type ',[':reorder_type'=>$request['reorder_type']]);
			}else{
				$orderData->andWhere(['not', ['reorder_type' => null]]);
				//生成操作下拉菜单的code
				$op_code = 'reo';
			}
			$showsearch=1;
		}
		if (!empty($request['fuhe'])){
			$showsearch=1;
			//搜索符合条件
			switch ($request['fuhe']){
				case 'is_comment_status':
					$orderData->andWhere('is_comment_status = 0');
					break;
				default:break;
			}
		}
		if (!empty($request['searchval'])){
			//搜索用户自选搜索条件
			if (in_array($request['keys'], ['order_id','order_source_order_id','buyeid','consignee','email'])){
				$kv=[
					'order_id'=>'order_id',
					'order_source_order_id'=>'order_source_order_id',
					'buyeid'=>'source_buyer_user_id',
					'email'=>'consignee_email',
					'consignee'=>'consignee'
				];
				$key = $kv[$request['keys']];
				if(!empty($request['fuzzy'])){
					$orderData->andWhere("$key like :val",[':val'=>"%".$_REQUEST['searchval']."%"]);
				}else{
					$orderData->andWhere("$key = :val",[':val'=>$_REQUEST['searchval']]);
				}
			}elseif ($request['keys']=='sku'){
				if(!empty($request['fuzzy'])){
					$ids = Helper_Array::getCols(OdOrderItem::find()->where('sku like :sku',[':sku'=>"%".$request['searchval']."%"])->select('order_id')->asArray()->all(),'order_id');
				}else{
					$ids = Helper_Array::getCols(OdOrderItem::find()->where('sku = :sku',[':sku'=>$request['searchval']])->select('order_id')->asArray()->all(),'order_id');
				}
				$orderData->andWhere(['IN','order_id',$ids]);
			}elseif ($request['keys']=='tracknum'){
				if(!empty($request['fuzzy'])){
					$ids = Helper_Array::getCols(OdOrderShipped::find()->where('tracking_number like :tn',[':tn'=>"%".$request['searchval']."%"])->select('order_id')->asArray()->all(),'order_id');
				}else{
					$ids = Helper_Array::getCols(OdOrderShipped::find()->where('tracking_number = :tn',[':tn'=>$request['searchval']])->select('order_id')->asArray()->all(),'order_id');
				}
				$orderData->andWhere(['IN','order_id',$ids]);
			}elseif ($request['keys']=='order_source_itemid'){
				//aliexpress product id
				$orderData->andWhere('order_id in (select order_id from od_order_item_v2 where order_source_itemid =:order_source_itemid) ',[':order_source_itemid'=>$request['searchval']]);
			}
		}
		// if (!empty($request['country'])){
		// 	//搜索订单 目的地国家
		// 	$orderData->andWhere('consignee_country = :country',[':country'=>$request['country']]);
		// 	$showsearch=1;
		// }

		if (!empty($request['selleruserid'])){
			//搜索卖家账号
			$orderData->andWhere('selleruserid = :s',[':s'=>$request['selleruserid']]);
		}

		if (!empty($request['amzStoreDropdownList'])){
			//搜索卖家账号
			$orderData->andWhere('selleruserid = :merchant_id',[':merchant_id'=>$request['amzStoreDropdownList']]);
		}

		if (!empty($request['country'])){
			$orderData->andWhere(['consignee_country_code'=>explode(',', $request['country'])]);
			$showsearch=1;
		}
		if (!empty($request['tracker_status'])){
			//logistic_status 先于erp2.1， 所以 tracker_status 废弃不使用
			//tracker 状态
			$orderData->andWhere('logistic_status = :tracker_status',[':tracker_status'=>$request['tracker_status']]);
			$showsearch=1;
		}
		if (!empty($request['pay_order_type'])){
			if($request['pay_order_type'] != 'all'){
				//已付款订单类型
				$orderData->andWhere('pay_order_type = :pay_order_type',[':pay_order_type'=>$request['pay_order_type']]);
				$showsearch=1;
			}
		}
		if (!empty($request['is_merge'])){
			// 合并订单过滤
			$orderData->andWhere(['order_relation'=>'sm']);
		}else{
			$orderData->andWhere(['order_relation'=>['normal','sm']]);
		}
		//时间搜索
		if (!empty($request['startdate'])||!empty($request['enddate'])){
			//搜索订单日期
			switch ($request['timetype']){
				case 'soldtime':
					$tmp='order_source_create_time';
				break;
				case 'paidtime':
					$tmp='paid_time';
				break;
				case 'printtime':
					$tmp='printtime';
				break;
				case 'shiptime':
					$tmp='delivery_time';
				break;
				default:
					$tmp='order_source_create_time';
				break;
			}
			if (!empty($request['startdate'])){
				$orderData->andWhere("$tmp >= :stime",[':stime'=>strtotime($request['startdate'])]);
			}
			if (!empty($request['enddate'])){
				$enddate = strtotime($request['enddate']) + 86400;
				$orderData->andWhere("$tmp <= :time",[':time'=>$enddate]);
			}
			$showsearch=1;
		}
		//排序
		$orderstr = 'order_source_create_time';//默认按照下单时间
		if (!empty ($request['customsort'])){
			
			switch ($request['customsort']){
				case 'soldtime':
					$orderstr='order_source_create_time';
					break;
				case 'paidtime':
					$orderstr='paid_time';
					break;
				case 'printtime':
					$orderstr='printtime';
					break;
				case 'shiptime':
					$orderstr='delivery_time';
					break;
				case 'order_id':
					$orderstr='order_id';
					break;
				case 'grand_total':
					$orderstr='grand_total';
					break;
				default:
					$orderstr='order_source_create_time';
					break;
			}
			$showsearch=1;
		}
		//是否升序
		if (!empty ($request['ordersorttype'])){
			$orderstr=$orderstr.' '.$request['ordersorttype'];
		}else{
			$orderstr=$orderstr.' '.'desc';
		}
		if (!empty($request['carrier_code'])){
			//物流商
			$orderData->andWhere(['default_carrier_code'=>$request['carrier_code']]);
			$showsearch=1;
		}

		// if (empty($request['ordersort'])){
		// 	$orderstr = 'order_source_create_time';
		// }else{
		// 	switch ($request['ordersort']){
		// 		case 'soldtime':
		// 			$orderstr='order_source_create_time';
		// 			break;
		// 		case 'paidtime':
		// 			$orderstr='paid_time';
		// 			break;
		// 		case 'printtime':
		// 			$orderstr='printtime';
		// 			break;
		// 		case 'shiptime':
		// 			$orderstr='delivery_time';
		// 			break;
		// 	}
		// }
		// if (empty($request['ordersorttype'])){
		// 	$orderstr .= ' DESC';
		// }else{
		// 	$orderstr.=' '.$request['ordersorttype'];
		// }

		return [$orderData,$showsearch,$op_code,$orderstr];
	}//end orderListDataSearch
	/**
	 * [getAmazonOmsNav 自动生成topmenu]
	 * @Author   willage
	 * @DateTime 2016-07-12T16:36:32+0800
	 * @param    [type]                   $key_word [description]
	 * @return   [type]                             [description]
	 */
	static public function getAmazonOmsNav($key_word){
		$order_nav_list = [
		'同步订单'=>'/order/amazon-order/order-sync-info' ,
		'已付款'=>'/order/amazon-order/list?order_status=200&pay_order_type=pending' ,
		'发货中'=>'/order/amazon-order/list?order_status=300' ,
		'已完成'=>'/order/amazon-order/list?order_status=500' ,
		];
	
		$order_nav_active_list = [
		'同步订单'=>'' ,
		'已付款'=>'200' ,
		'发货中'=>'300' ,
		'已完成'=>'500' ,
		];
	
		$NavHtmlStr = '<ul class="main-tab">';
	
		$mappingOrderNav = array_flip($order_nav_active_list);
		foreach($order_nav_list as $label=>$thisUrl){
			$NavActive='';
	
			//$_REQUEST['order_status']
			if (isset($key_word)){
				if (empty($key_word) &&  !empty($mappingOrderNav[$key_word]) &&$mappingOrderNav[$key_word] == $label && \yii::$app->controller->action->id == 'order-sync-info'){
					$NavActive = " active ";
				}else
				if (!empty($key_word) &&  !empty($mappingOrderNav[$key_word]) &&$mappingOrderNav[$key_word] == $label && \yii::$app->controller->action->id != 'order-sync-info' ) {
					$NavActive = " active ";
				}
			}else{
				//$NavActive = " active ";
			}
			$NavHtmlStr .= '<li class="'.$NavActive.'"><a href="'.$thisUrl.'">'.TranslateHelper::t($label).'</a></li>';
			/* $NavHtmlStr .= '<div class="pull-left col-md-2">
			 <div class="rectangle-content'.$NavActive.'"><p class="p-rectangle-content'.$NavActive.'"><a href="'.$thisUrl.'">'.TranslateHelper::t($label).'</a></p></div>
			<div class="triangle-right'.$NavActive.'"></div>
			</div>';
			*/
		}
		$NavHtmlStr.='</ul>';
	
	
		return $NavHtmlStr;
	
	}//end getAmazonOmsNav
	/**
	 * [array_insert 插入到数组指定位置]
	 * @Author   willage
	 * @DateTime 2016-07-15T19:04:36+0800
	 * @param    [type]                   &$array       [description]
	 * @param    [type]                   $position     [description]
	 * @param    [type]                   $insert_array [description]
	 * @return   [type]                                 [description]
	 */
    static public function array_insert (&$array, $position, $insert_array) {
        $first_array = array_splice ($array, 0, $position);
        $array = array_merge ($first_array, $insert_array, $array);
    }


    /**
     * [urlPartEncode 用于URL部分encode]
     * 说明：因为七牛获取图片url时候,会对部分字符加密
     * @Author willage 2017-02-09T13:41:32+0800
     * @Editor willage 2017-02-09T13:41:32+0800
     * @return [type]  [description]
     */
    static public function urlPartEncode ($orig_url,$puid,$priority) {
		$urlStrSrc=ImageCacherHelper::getImageCacheUrl($orig_url,$puid,$priority);
		//No.1-用substr获取从"https:"剩下的全部字符
		$tmpUrl=substr(strstr($urlStrSrc,"https:__"),8); 
		if (empty($tmpUrl)) {
			return $urlStrSrc;
		}
		//No.2-截取后加密,并重新组合
		$urlStrNew=substr($urlStrSrc,0,strpos($urlStrSrc,"https:__")+8).urlencode($tmpUrl);
		return $urlStrNew;
    }

}//end class


?>