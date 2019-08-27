<?php
namespace eagle\modules\order\helpers;

use \Yii;
use eagle\modules\util\helpers\ResultHelper;
use eagle\modules\order\models\OdOrderItem;
use eagle\modules\html_catcher\helpers\HtmlCatcherHelper;
use common\helpers\Helper_Array;

class NeweggApiHelper{
	
	public static $CustomerShippingService = array(
			'Shipped By Newegg'=>'Shipped By Newegg',
			'APO/FPO - Military Only'=>'APO/FPO - Military Only',
			'Super Saver(7-14 business days)'=>'Super Saver(7-14 business days)',
			'Standard Shipping(5-7 business days)'=>'Standard Shipping(5-7 business days)',
			'Expedited Shipping(3-5 business days)'=>'Expedited Shipping(3-5 business days)',
			'Two-Day Shipping(2 business days)'=>'Two-Day Shipping(2 business days)',
			'One-Day Shipping(Next days)'=>'One-Day Shipping(Next days)',
			'International Expedited Shipping(3-5 business days)'=>'International Expedited Shipping(3-5 business days)',
			'International Two-Day Shipping(2 business days)'=>'International Two-Day Shipping(2 business days)',
			'International Economy Shipping(8-15 business days)'=>'International Economy Shipping(8-15 business days)',
			'International Standard Shipping(5-7 business days)'=>'International Standard Shipping(5-7 business days)',
			'Newegg Premier Three-Day Shipping'=>'Newegg Premier Three-Day Shipping',
			'Newegg Premier Two-Day Shipping'=>'Newegg Premier Two-Day Shipping',
			'Newegg Premier One-Day Shipping'=>'Newegg Premier One-Day Shipping',
	);
	
	public static function getNeweggOrderCustomerShippingServiceCode(){
		return self::$CustomerShippingService;
	}
	
	
	/**
	 * 获取newegg后台job中的错误数据
	 *
	 * @author winton
	 */
	public static function getSysErrorList($last_time)
	{
		$msg = "";
		//获取saas_newegg_autosync表中的异常数据
		$type_arr = [1,2,3,4];
		foreach ($type_arr as $type){
			$ret = self::getNeweggAutoSyncError($type, $last_time);
			if(!empty($ret)){
				$msg .= '++++++++++++++++ saas_newegg_autosync (type = '.$type.')++++++++++++++++';
				$msg .= $ret;
			}
		}
		//获取saas_newegg_autosync表中的异常数据
		$ret = self::getNeweggQueueError($last_time);
		if(!empty($ret)){
			$msg .= '++++++++++++++++ queue_newegg_getorder ++++++++++++++++';
			$msg .= $ret;
		}
		//获取hc_collect_request_queue中newegg相关的错误行
		$ret = self::getNeweggLoadImageQueueSysError();
		if(!empty($ret)){
			$msg .= '++++++++++++++++ hc_collect_request_queue中newegg相关的错误行 ++++++++++++++++';
			$msg .= $ret;
		}
		//如果有错误信息，则组织邮件并发送
		if (!empty($msg)) {
			echo $msg;
			$sendto_email = ['1241423221@qq.com','395628249@qq.com','156038530@qq.com'];
			$subject = 'newegg后台Job异常错误';
			$body = $msg;
			$result = LazadaApiHelper::sendEmail($sendto_email, $subject, $body);
			if ($result === false) {
				echo "发送邮件失败";
				yii::info("发送邮件失败", "file");
			} else {
				yii::info("发送邮件成功", "file");
				echo "发送邮件成功";
			}
			return true;
		} else {
			echo "newegg没有的异常数据";
			yii::info("newegg没有的异常数据", "file");
			return false;
		}
	
	}
	//end function
	
	/**
	 * 获取saas_newegg_autosync表中的异常数据
	 * @param int $type
	 * @param int $last_time
	 * @return string
	 * @author winton
	 */
	public static function getNeweggAutoSyncError($type, $last_time){
		if(in_array($type, [1,2,3,4])){
			$connection = Yii::$app->db;
			$res = $connection->createCommand("
					SELECT a.id,a.uid,a.status,a.message,a.error_times,saas_newegg_user.site_id,FROM_UNIXTIME(a.last_finish_time) as lt
					FROM saas_newegg_autosync a
					LEFT JOIN saas_newegg_user ON saas_newegg_user.site_id = a.site_id
					WHERE ((a.status == 1) or ( a.error_times>=10 ))
					AND a.is_active=1 AND a.type = '{$type}' AND a.last_finish_time < '{$last_time}'
					ORDER BY a.last_finish_time DESC
					")->query();
			$result = $res->readAll();
			$msg = "";
			if (!empty($result)) {
				foreach ($result as $vs) {
					//检查是否活跃用户,在邮件主体中标记出来吧
					$mt = self::isActiveUser($vs['uid']) === false ? '非活跃用户' : '活跃用户';
					if($vs['status'] != 4){
						//如果是活跃用户,就先把status,改成0
						if ($mt == '活跃用户') {
							$id = $vs['id'];
							$update = $connection->createCommand("UPDATE saas_newegg_autosync SET `status`=0, `error_times`=0 WHERE id='{$id}'")->execute();
						}
					}
					
					$msg .= $mt . 'NeweggAutoSyncError--autoSyncID:' . $vs['id'] . '--status:' . $vs['status'] . '--' . '--PUID:' . $vs['uid'] . '--错误次数:' . $vs['error_times'] . '--错误内容:' . $vs['message'] . ',最后更新时间-- ' . $vs['lt'] . ',newegg登录账户id-- ' . $vs['site_id'] . PHP_EOL;
				}
			}
		}else {
			$msg = '不存在的type:'.$type;
		}
		
		return $msg;
	}
	
	/**
	 * 获取saas_newegg_autosync表中的异常数据
	 * @param int $type
	 * @param int $last_time
	 * @return string
	 * @author winton
	 */
	public static function getNeweggQueueError($last_time){
		$connection = Yii::$app->db_queue;
		$res = $connection->createCommand("
					SELECT q.id,q.uid,q.status,q.message,q.error_times,q.order_source_order_id,saas_newegg_user.site_id,FROM_UNIXTIME(q.last_finish_time) as lt
					FROM queue_newegg_getorder q
					LEFT JOIN saas_newegg_user ON (saas_newegg_user.uid = q.uid AND saas_newegg_user.SellerID = q.sellerID) 
					WHERE ((q.status == 1) or ( q.error_times>=10 ))
					AND q.is_active=1 AND q.last_finish_time < '{$last_time}'
					ORDER BY q.last_finish_time DESC
					")->query();
		$result = $res->readAll();
		$msg = "";
		if (!empty($result)) {
			foreach ($result as $vs) {
				//检查是否活跃用户,在邮件主体中标记出来吧
				$mt = self::isActiveUser($vs['uid']) === false ? '非活跃用户' : '活跃用户';
				if($vs['status'] != 4){
					//如果是活跃用户,就先把status,改成0
					if ($mt == '活跃用户') {
						$id = $vs['id'];
						$update = $connection->createCommand("UPDATE queue_newegg_getorder SET `status`=0, `error_times`=0 WHERE id='{$id}'")->execute();
					}
				}
									
				$msg .= $mt . 'queue_newegg_getorder Error--queueID:' . $vs['id'] . '--status:' . $vs['status'] . '--' . '--PUID:' . $vs['uid'] . '--订单来源ID:' . $vs['order_source_order_id'] . '--错误次数:' . $vs['error_times'] . '--错误内容:' . $vs['message'] . ',最后更新时间-- ' . $vs['lt'] . ',newegg登录账户id-- ' . $vs['site_id'] . PHP_EOL;
			}
		}
		return $msg;
	}
	
	/**
	 * 获取hc_collect_request_queue中newegg相关的错误行
	 */
	public static function getNeweggLoadImageQueueSysError(){
		$connection = Yii::$app->db_queue2;
		$res = $connection->createCommand("
				SELECT q.id,q.puid,q.status,q.err_msg,q.step,q.product_id,q.retry_count
				FROM hc_collect_request_queue q
				WHERE (q.status = 'F') AND q.platform = 'newegg'
				ORDER BY q.update_time DESC
				")->query();
		$result = $res->readAll();
		$msg = "";
		if (!empty($result)) {
			foreach ($result as $vs) {
				//检查是否活跃用户,在邮件主体中标记出来吧
				$mt = self::isActiveUser($vs['puid']) === false ? '非活跃用户' : '活跃用户';
				if($vs['status'] != 4){
					//如果是活跃用户,就先把status,改成0
					if ($mt == '活跃用户') {
						$id = $vs['id'];
						$update = $connection->createCommand("UPDATE hc_collect_request_queue SET `status`='P' WHERE id='{$id}'")->execute();
					}
				}
				$msg .= $mt . 'hc_collect_request_queue Error--queueID:' . $vs['id'] . '--status:' . $vs['status'] . '--' . '--PUID:' . $vs['puid'] . '--运行次数:' . $vs['retry_count'] . '--错误内容:' . $vs['err_msg'] . PHP_EOL;
			}
		}
		return $msg;
	}
	
	/**
	 * 插入到html抓取队列中
	 * @param unknown $uid
	 * @param unknown $product_id_list
	 */
	public static function insertQueueForCatchHtml($uid, $product_id_list){
		return true;//newegg 检测到我们通多代码拉取图片，页面返回不是商品信息也，拉取失效，暂时屏蔽。
		$field_list=array('seller_product_id','img','title','description','brand');
		$field_list=array('img','title');
		$site = '';
		$platform = 'newegg';
		$callback = 'eagle\modules\order\helpers\NeweggApiHelper::webSiteInfoToDb($uid,$prodcutInfo);';
		$rtn = HtmlCatcherHelper::requestCatchHtml($uid,$product_id_list,$platform,$field_list,$site,$callback);
	}
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * HtmlCatcher获取到offer信息后更新到offer表
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @params 	$uid			用户
	 * @params 	$prodcutInfo	商品信息
	 * @params 	$is_base64		过滤参数
	 +---------------------------------------------------------------------------------------------
	 * @author		winton		2016/07/28			初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	public static function webSiteInfoToDb($uid,$prodcutInfo, $is_base64=true,$seller='',$no_uid=false){
		$info = json_decode(base64_decode($prodcutInfo), true);
		
		$message = '';
 
		$r = true;
		foreach ($info as $source_itemid=>$msg){
			
			//增加一步获取order_item_id，用来避免其他平台拥有相同的sku
			$order_item_id_list = [];
			
			$items = OdOrderItem::find()
			->select('order_item_id')
			->leftJoin('od_order_v2','od_order_item_v2.order_id = od_order_v2.order_id')
			->where(['platform_sku'=>$source_itemid])
			->andwhere('od_order_v2.order_source = "newegg"')
			->asArray()
			->all();
			 
			foreach ($items as $item){
				$order_item_id_list[$item['order_item_id']] = $item['order_item_id'];
			}
			
			$r_tmp = OdOrderItem::updateAll(
					['photo_primary'=>$msg['primary_image'][0]],
					['order_item_id'=>$order_item_id_list]);
// 			$r &= $r_tmp;
// 			if(!$r_tmp){
// 				$message = '更新item失败';
// 			}
		}
		
		return ['success' => $r, 'message' => $message];
	}
	
	/**
	 * 更新newegg指定商品图片
	 * @param string $item_sku
	 * @param int $uid
	 */
	public static function updateImage($item_sku, $uid=''){
		$ret = ['success'=>0, 'message'=>''];
		
		$url = "https://www.newegg.com/Product/Product.aspx?Item=".$item_sku;
		$roleSetting = '{"title":{"[itemprop=name]":""},"primary_image":{".mainSlide":"imgzoompic"}}';
		$roleSetting = json_decode($roleSetting,true);
		ob_start();
		set_time_limit(0);//设置超时时间
		$html_catch_result = HtmlCatcherHelper::analyzeHtml($url, $roleSetting);
		ob_clean();
		ob_end_flush();
		
		if(!$html_catch_result['success']){
			$ret['message'] .= $html_catch_result['message'];
			return $ret;
		}else{
			$info[$item_sku] = $html_catch_result['data'];
			$prodcutInfo = base64_encode(json_encode($info));
			
			$no_uid = false;
			if(empty($uid)){
				$no_uid = true;
			}
			
			return NeweggApiHelper::webSiteInfoToDb($uid, $prodcutInfo, true, '', $no_uid);
		}
		
	}
	
	/**
	 * 根据订单号更新该订单下的图片
	 * @param unknown $order_id
	 * @param string $uid
	 * @return multitype:number string
	 */
	public static function updateImageByOrderID($order_id, $uid=''){
		if(empty($order_id)){
			return ['success' => 0, 'message' => '网络异常。空的订单号'];
		}
		if(is_string($order_id)){
			$order_id = explode(',', $order_id);
			Helper_Array::removeEmpty($order_id);
		}
 
		
		$itemList = OdOrderItem::find()->where(['order_id'=>$order_id])->all();
		if(empty($itemList)){
			return ['success' => 0, 'message' => '找不到对应的商品，不需要更新图片缓存'];
		}
		
		$ret = ['success'=>1, 'message'=>'', 'success_message'=>''];
		
		foreach ($itemList as $item){
			$rt = self::updateImage($item->platform_sku, $uid);
			if($rt['success']){
				$ret['success_message'] .= $item->sku.'图片更新成功';
			}else{
				$ret['message'] .= $item->sku.'图片更新失败。'.$rt['message'];
			}
		}
		
		if(!empty($ret['message'])){
			$ret['success'] = 0;
		}
		
		return $ret;
	}	
}

?>