<?php
namespace eagle\modules\delivery\helpers;
use eagle\modules\delivery\models\OdDelivery;
use Exception;
use eagle\modules\util\helpers\OperationLogHelper;
use common\helpers\Helper_Array;
//use eagle\modules\delivery\apihelpers\DeliveryApiHelper;
use eagle\modules\inventory\models\Warehouse;
use eagle\modules\order\models\OdOrder;
use eagle\modules\util\helpers\TranslateHelper;
use yii\data\Pagination;
use eagle\modules\carrier\helpers\CarrierOpenHelper;
use eagle\modules\carrier\apihelpers\CarrierApiHelper;
use eagle\modules\order\apihelpers\OrderApiHelper;
use eagle\modules\util\helpers\ConfigHelper;
use eagle\modules\permission\apihelpers\UserApiHelper;
use eagle\modules\permission\helpers\UserHelper;
/**
 +------------------------------------------------------------------------------
 * 订单模块业务逻辑类
 +------------------------------------------------------------------------------
 * @category	Order
 * @package		Helper/order
 * @subpackage  Exception
 * @version		1.0
 +------------------------------------------------------------------------------
 */
class DeliveryHelper {
	/**
	 +---------------------------------------------------------------------------------------------
	 * 统计Left menu 上的order 数据
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param $warehouse_id					仓库ID
	 +---------------------------------------------------------------------------------------------
	 * @return array  	 各栏目的 订单 数
	 +---------------------------------------------------------------------------------------------
	 * log			name			date					note
	 * @author		million 		2015/01/05				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function getMenuStatisticData($warehouse_id=0 ,$use_mode = '', $win_list = ''){
		//$delivery_picking_mode 是否使用拣货模式 0:表示不使用,1:表示使用
// 		$delivery_picking_mode = ConfigHelper::getConfig('delivery_picking_mode');
// 		$delivery_picking_mode = empty($delivery_picking_mode) ? 0 : $delivery_picking_mode;
		
		//发货状态
		$deliveryStatus = OdOrder::$deliveryStatus;
		unset($deliveryStatus[0]);
		$tmpDeliveryStatus = $deliveryStatus;
		unset($tmpDeliveryStatus[1]);
		
		$tmpWarehouseSql = '';
		$tmpWarehouseSqlParam = array();
		
		$tmpWarehouseSql2 = '';
		$tmpWarehouseSql2Param = array();
		
		if($use_mode != ''){
			$tmpWarehouseSql .= ' and order_source =:order_source ';
			$tmpWarehouseSqlParam[':order_source'] = $use_mode;
			
			$tmpWarehouseSql2 = ' and order_source =:order_source ';
			$tmpWarehouseSql2Param[':order_source'] = $use_mode;
		}
		
		//子账号 权限控制 start
		$isParent = \eagle\modules\permission\apihelpers\UserApiHelper::isMainAccount();
		
		//true 为主账号， 不需要增加平台过滤 ， false 为子账号， 需要增加权限控制
		if ($isParent ==false){
			$UserAuthorizePlatform = \eagle\modules\permission\apihelpers\UserApiHelper::getUserAuthorizePlatform();
			//返回all时不用添加平台控制，加快SQL运行
			if(in_array('all', $UserAuthorizePlatform)){
				unset($UserAuthorizePlatform);
			}
		}
		//子账号 权限控制 end
		
		$a = Warehouse::find()->where('is_active = :is_active',[':is_active'=>'Y'])->select(['warehouse_id','name','is_oversea'])->asArray()->all();
		$tmpWarehouseOver = Helper_Array::toHashmap($a, 'warehouse_id', 'is_oversea');
		$a = Helper_Array::toHashmap($a, 'warehouse_id', 'name');
		
		//每个仓库发货中订单数量
        $tmpOrderFind = OdOrder::find()->select(['default_warehouse_id','count(1) as orderCount'])
        	->where(' default_shipping_method_code <> "" and order_status = '.OdOrder::STATUS_WAITSEND.$tmpWarehouseSql,$tmpWarehouseSqlParam)
        	->andWhere(['default_warehouse_id'=>array_keys($a)])
        	->andWhere(['isshow'=>'Y']);
        	
		if($win_list == 'listprintdelivery'){
			$tmpOrderFind->andWhere(['in','delivery_status',array_keys($tmpDeliveryStatus)]);
		}else{
// 			$tmpOrderFind->andWhere(['in','delivery_status',array(0,1)]);
			
			if($warehouse_id != -2){
				$tmpWarehouseSql .= ' and default_warehouse_id =:default_warehouse_id ';
				$tmpWarehouseSqlParam[':default_warehouse_id'] = $warehouse_id;
			}
		}
		
		//权限管理
		$tmpPlatform = array();
		$authorize_query = array();
		if(!empty($UserAuthorizePlatform)){
			//店铺级别权限 	start	2017-03-21 lzhl	ADD
			$authorizePlatformAccounts = UserHelper::getUserAuthorizePlatformAccounts($UserAuthorizePlatform);
			if(!empty($authorizePlatformAccounts)){
				$authorize_query = ['or'];
				foreach ($authorizePlatformAccounts as $authorize_platform=>$authorize_accounts){
					if(is_array($authorize_accounts)){
						$tmp_authorize_accounts = [];
						foreach ($authorize_accounts as $key=>$val){
							if(is_numeric($key) && (string)$key!==(string)$val)
								$tmp_authorize_accounts[] = $val;
							else 
								$tmp_authorize_accounts[] = $key;
						}
						$authorize_accounts = $tmp_authorize_accounts;
					}
					$authorize_query[] = ['order_source'=>$authorize_platform,'selleruserid'=>$authorize_accounts];
				}
				$tmpOrderFind->andWhere($authorize_query);
			}
			//店铺级别权限 	end
			
			//$tmpOrderFind->andWhere(['order_source'=>$UserAuthorizePlatform]);
			$tmpPlatform['order_source'] = $UserAuthorizePlatform;
		}
		
		//$commandQuery = clone $tmpOrderFind;
		//echo $commandQuery->createCommand()->getRawSql();
		
        $tmp_warehouse = $tmpOrderFind->groupBy('default_warehouse_id')->asArray()->all();
        
        $tmp_warehouse = Helper_Array::toHashmap($tmp_warehouse, 'default_warehouse_id', 'orderCount');
        
        $counter = array();
        
        //默认把默认仓显示出来
        if(!isset($tmp_warehouse[0])){
        	$counter[0] = 0;
        }
        
        $counter = $counter+$tmp_warehouse;
        
        $counter['warehouse_html'] = "<strong style='font-weight: bold;font-size:12px;'>仓库位置：</strong>			";
        $counter['warehouse_html'] .= "<a style='margin-right: 20px;' class=' ".($warehouse_id == '-2' ? 'text-rever-important' : '')."' value='-2' onclick='warehouseBtnClick(this,\"".$win_list."\")' >全部</a>";
        
        foreach ($counter as $tmp_key => $tmp_val){
        	if(isset($a[$tmp_key])){
        		$counter['warehouse_html'] .= "<a style='margin-right: 20px;' class=' ".($warehouse_id == $tmp_key ? 'text-rever-important' : '')."' value='".$tmp_key."' onclick='warehouseBtnClick(this,\"".$win_list."\")' >".$a[$tmp_key].'('.$tmp_val.')'."</a>";
        	}
        }
        
        $counter['warehouse_html'] = '<div style="margin:20px 0px 0px 0px">'.$counter['warehouse_html'].'</div>';
        
        if($win_list != 'listprintdelivery'){
        	if(isset($tmpWarehouseOver[$warehouse_id])){
        		$counter['warehouse_is_oversea'] = $tmpWarehouseOver[$warehouse_id];
        	}else{
        		$counter['warehouse_is_oversea'] = 0;
        	}
        }
        
		//暂时直接将所有发货中的订单展示出来,不判断delivery_status。后期再控制是否显示
		$counter[$deliveryStatus[1]]=OdOrder::find()
			->where('default_shipping_method_code <> ""  and order_status = '.OdOrder::STATUS_WAITSEND.' '.$tmpWarehouseSql2,$tmpWarehouseSql2Param)
			->andWhere($tmpPlatform)
			->andWhere(['isshow'=>'Y'])
			->andFilterWhere($authorize_query)->count();
		
		$counter[$tmpDeliveryStatus[2]]=OdOrder::find()->where('default_shipping_method_code <> ""  and order_status = '.OdOrder::STATUS_WAITSEND.
			$tmpWarehouseSql2,$tmpWarehouseSql2Param)->andWhere(['in','delivery_status',array_keys($tmpDeliveryStatus)])
				->andWhere(['isshow'=>'Y'])
				->andWhere($tmpPlatform)->andFilterWhere($authorize_query)->count();
		
		$carrier_type = OdOrder::$carrier_type;
		foreach ($carrier_type as $key=>$value){
			$allcarriers = [];
			switch ($key){
				case 1:$allcarriers = array_keys(CarrierApiHelper::getCarrierList(2,-1));break;//api
				case 2:$allcarriers = array_keys(CarrierApiHelper::getCarrierList(3,-1));break;//excel
				case 3:$allcarriers = array_keys(CarrierApiHelper::getCarrierList(4,-1));break;//trackno
			}
			$carrier_step = OdOrder::$delivery_carrier_step;
			foreach ($carrier_step as $k=>$v){
				if ($k==6){
					$ods_tmp = OdOrder::find()->where('default_shipping_method_code <> "" and carrier_step=6 and order_status = '.OdOrder::STATUS_WAITSEND.$tmpWarehouseSql,$tmpWarehouseSqlParam)
						->andWhere(['default_carrier_code'=>$allcarriers]+$tmpPlatform)
						->andWhere(['isshow'=>'Y']);
				}else{
					$ods_tmp = OdOrder::find()->where('default_shipping_method_code <> ""  and order_status = '.OdOrder::STATUS_WAITSEND.' '.$tmpWarehouseSql,$tmpWarehouseSqlParam)	//and delivery_status in (0,1)
						->andWhere(['default_carrier_code'=>$allcarriers]+$tmpPlatform)
						->andWhere(['carrier_step'=>$v])
						->andWhere(['isshow'=>'Y']);
				}
				
// 				$command = $ods_tmp->createCommand();
// 				print_r($command->getRawSql());
				$tmp_count = $ods_tmp->andFilterWhere($authorize_query)->count();
				$counter['listplanceanorder'][$key][$k]=$tmp_count;
				if(!isset($counter['listplanceanorder'][$key]['all'])){
					$counter['listplanceanorder'][$key]['all'] = 0;
				}
				if ($k!=6){
					$counter['listplanceanorder'][$key]['all'] += $tmp_count;
				}
			}
		}
		
		//兼容excel导出的数据
		if(isset($counter['listplanceanorder'][2][1]) && isset($counter['listplanceanorder'][2][2])){
			$counter['listplanceanorder'][2][1] += $counter['listplanceanorder'][2][2];
			$counter['listplanceanorder'][2][2] = 0;
		}
		
		$counter['listpicking'][0] = 0;		//未生产拣货单
		$counter['listpicking'][1] = 0;		//已生成拣货单
		$counter['listdistribution'][0] = 0;//未打印配货单
		$counter['listdistribution'][1] = 0;//已打印配货单
		
        //未分配仓库订单数
		$counter[-1] = 0;
        $counter['指定仓库和运输服务'] = $counter[-1];
        $counter['未指定运输服务'] = 0;
        $counter['已完成'] = 0;
        $counter['所有发货中'] = 0;	//所有发货中数据不再统计
        
		//仓库中处于各个流程的订单数量
		return $counter;
	
	}
	
	
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 自动 生成 top menu
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @return						string
	 
	 *
	 +---------------------------------------------------------------------------------------------
	 * log			name		date					note
	 * @author		million		2015/01/05				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
static public function getOrderNav($counter,$warehouse_id = 0,$key_word=0){
	//$delivery_picking_mode 是否使用拣货模式 0:表示不使用,1:表示使用
// 	$delivery_picking_mode = ConfigHelper::getConfig('delivery_picking_mode');
// 	$delivery_picking_mode = empty($delivery_picking_mode) ? 0 : $delivery_picking_mode;
	
		$overseaWare = Helper_Array::toHashmap( Warehouse::find()->where('is_active = :is_active and is_oversea = :is_oversea',[':is_active'=>'Y',':is_oversea'=>1])->select(array('warehouse_id'))->asArray()->All(),'warehouse_id','warehouse_id');
		$is_oversea = 0;
		if(in_array($warehouse_id, $overseaWare)){
			$is_oversea = 1;
		}
		
		if($is_oversea == 0){
			$order_nav_list = [
// 					'所有发货中'=>'/delivery/order/listalldelivery',
// 					'未指定运输服务'=>'/delivery/order/listnodistributionwarehouse' ,
					'物流商下单'=>'/delivery/order/listplanceanorder' ,
					'打包出库'=>'/delivery/order/listprintdelivery',
					'已完成'=>'/delivery/order/finishdeliveredlist',
					];
			$order_nav_active_list = [
// 					'所有发货中'=>'listalldelivery',
// 					'未指定运输服务'=>'listnodistributionwarehouse' ,
					'物流商下单'=>'listplanceanorder' ,
					'打包出库'=>'listprintdelivery',
					'已完成'=>'finishdeliveredlist' ,
					];
		}
		else if($is_oversea == 1){
			$order_nav_list = [
// 				'所有发货中'=>'/delivery/order/listalldelivery',
				'物流商下单'=>'/delivery/order/listplanceanorder',
				'已完成'=>'/delivery/order/finishdeliveredlist',
            ];
			$order_nav_active_list = [
// 				'所有发货中'=>'listalldelivery',
				'物流商下单'=>'listplanceanorder' ,
				'已完成'=>'finishdeliveredlist' ,
            ];
		}
		
		if($is_oversea == 1){
			unset($order_nav_list['打包出库']);
			unset($order_nav_active_list['打包出库']);
		}
		
		//判断是否有发货、打包出库的权限
		$canAccessModule = UserApiHelper::checkModulePermission("delivery");
		$canAccessModule_edit = UserApiHelper::checkModulePermission("delivery_edit");
		if($canAccessModule && !$canAccessModule_edit){
			if(!empty($order_nav_list['打包出库'])){
				unset($order_nav_list['打包出库']);
				unset($order_nav_active_list['打包出库']);
			}
		}
		else if(!$canAccessModule && $canAccessModule_edit){
			unset($order_nav_list['物流商下单']);
			unset($order_nav_active_list['物流商下单']);
			unset($order_nav_list['已完成']);
			unset($order_nav_active_list['已完成']);
		}
	
		$NavHtmlStr = '<ul class="main-tab">';
	
		$mappingOrderNav = array_flip($order_nav_active_list);
		
		foreach($order_nav_list as $label=>$thisUrl){
			$NavActive='';
			if (\yii::$app->controller->action->id == $order_nav_active_list[$label]){
				$NavActive = " active ";
			}
			if (($label =="缺货扫描") || ($label =="已完成")){
				$NavHtmlStr .= '<li class="'.$NavActive.'"><a href="'.$thisUrl.'">'.TranslateHelper::t($label).'</a></li>';
			}else{
				$NavHtmlStr .= '<li class="'.$NavActive.'"><a '.($counter[$label] == 0 ? "style='pointer-events:none;'" : '  ').' href="'.$thisUrl.'" class="'.($counter[$label] == 0 ? 'a_disable' : '').'">'.TranslateHelper::t($label).'('.$counter[$label].')'.'</a></li>';
			}
		}
		$NavHtmlStr.='</ul>';
	
	
		return $NavHtmlStr;
	
	}//end of getOrderNav
	/**
	 * 根据条件获取订单信息
	 * @param unknown $params
	 * @return multitype:number \yii\data\Pagination Ambigous <multitype:, multitype:\yii\db\ActiveRecord >
	 */
	public static function getOrderList($params,$delivery_status = ''){
		$pageSize = isset($params['per-page'])?$params['per-page']:20;
		$data=OdOrder::find();
		$data->andWhere(['order_status'=>OdOrder::STATUS_WAITSEND]);
		if (isset($params['warehouse_id'])){
			$data->andWhere('default_warehouse_id = :warehouse_id',[':warehouse_id'=>$params['warehouse_id']]);
		}else{
			die('Warehouse ID null!');
		}
		if(!empty($delivery_status)){
			$data->andWhere(['delivery_status'=>$delivery_status]);
		}
		$showsearch=0;
		$op_code = '';
		
		//组织数据
		$selleruserid = isset($params['selleruserid'])?trim($params['selleruserid']):"";//ok
		$keys = isset($params['keys'])?trim($params['keys']):"";//ok
		$searchval = isset($params['searchval'])?trim($params['searchval']):"";//ok
		$fuzzy = isset($params['fuzzy'])?trim($params['fuzzy']):"";//ok
		$country = isset($params['country'])?trim($params['country']):"";//ok
		$order_source = isset($params['order_source'])?trim($params['order_source']):"";//ok
		$carrier_code = isset($params['carrier_code'])?trim($params['carrier_code']):"";//od
		$shipmethod = isset($params['shipmethod'])?trim($params['shipmethod']):"";//ok
		$sel_tag = isset($params['sel_tag'])?trim($params['sel_tag']):"";
		$reorder_type = isset($params['reorder_type'])?trim($params['reorder_type']):"";
		$order_evaluation = isset($params['order_evaluation'])?trim($params['order_evaluation']):"";
		$quantity_type = isset($params['quantity_type'])?trim($params['quantity_type']):"";
		$timetype = isset($params['timetype'])?trim($params['timetype']):"";
		$startdate = isset($params['startdate'])?trim($params['startdate']):"";
		$enddate = isset($params['enddate'])?trim($params['enddate']):"";
		$ordersorttype = isset($params['ordersorttype'])?trim($params['ordersorttype']):"";
		$customsort = isset($params['customsort'])?trim($params['customsort']):"";
		$order_systags = isset($params['order_systags'])?$params['order_systags']:array();//0k
		$is_reverse = isset($params['is_reverse'])?trim($params['is_reverse']):"";//0k
		$delivery_id = isset($params['delivery_id'])?trim($params['delivery_id']):"";
		$is_print_distribution = isset($params['is_print_distribution'])?trim($params['is_print_distribution']):"";
		$is_print_carrier = isset($params['is_print_carrier'])?trim($params['is_print_carrier']):"";
		//卖家账号
		if (!empty($selleruserid)){
			//搜索卖家账号
			$data->andWhere('selleruserid = :selleruserid',[':selleruserid'=>$params['selleruserid']]);
		}else{
			//不显示 解绑的账号的订单 start
			$listUnbindingAcount = OrderApiHelper::listUnbindingAcount('aliexpress')['aliexpress'];
			$data->andWhere(['not in','selleruserid',$listUnbindingAcount]);
			//不显示 解绑的账号的订单 end
		}
		//精确搜索
		if (!empty($searchval)){
			//搜索用户自选搜索条件
			if (in_array($keys, ['order_id','order_source_order_id','buyeid','consignee','email'])){
				$kv=[
				'order_id'=>'order_id',
				'order_source_order_id'=>'order_source_order_id',
				'buyeid'=>'source_buyer_user_id',
				'email'=>'consignee_email',
				'consignee'=>'consignee',
						];
				$key = $kv[$keys];
				if(!empty($fuzzy)){
					$data->andWhere("$key like :val",[':val'=>"%".$searchval."%"]);
				}else{
					$data->andWhere("$key = :val",[':val'=>$searchval]);
				}
		
			}elseif ($keys=='sku'){
				if(!empty($fuzzy)){
					$ids = Helper_Array::getCols(OdOrderItem::find()->where('sku like :sku',[':sku'=>"%".$searchval."%"])->select('order_id')->asArray()->all(),'order_id');
				}else{
					$ids = Helper_Array::getCols(OdOrderItem::find()->where('sku = :sku',[':sku'=>$searchval])->select('order_id')->asArray()->all(),'order_id');
				}
				$data->andWhere(['IN','order_id',$ids]);
			}elseif ($keys=='tracknum'){
				if(!empty($fuzzy)){
					$ids = Helper_Array::getCols(OdOrderShipped::find()->where('tracking_number like :tracking_number',[':tracking_number'=>"%".$searchval."%"])->select('order_id')->asArray()->all(),'order_id');
				}else{
					$ids = Helper_Array::getCols(OdOrderShipped::find()->where('tracking_number = :tracking_number',[':tracking_number'=>$searchval])->select('order_id')->asArray()->all(),'order_id');
				}
				$data->andWhere(['IN','order_id',$ids]);
			}elseif ($keys=='order_source_itemid'){
				//aliexpress product id
				if(!empty($fuzzy)){
					$data->andWhere('order_id in (select order_id from od_order_item_v2 where order_source_itemid like :order_source_itemid) ',[':order_source_itemid'=>$searchval]);
				}else{
					$data->andWhere('order_id in (select order_id from od_order_item_v2 where order_source_itemid =:order_source_itemid) ',[':order_source_itemid'=>$searchval]);
				}
			}
		}
		//是否已打印配货单
		if($is_print_distribution != ''){
			if($is_print_distribution != 1){
				$data->andWhere("ifnull(`is_print_distribution`,'') != 1");
			}else{
				$data->andWhere(['is_print_distribution'=>'1']);
			}
		}
		//拣货单号
		if(!empty($delivery_id)){
			$data->andWhere(['like','delivery_id',$delivery_id]);
		}
		//国家
		if (!empty($country)){
			$data->andWhere(['in','consignee_country_code',explode(",", $country)]);
			$showsearch=1;
		}
		//销售平台
		if (!empty($order_source)){
			$data->andWhere("order_source = :order_source",[':order_source'=>$order_source]);
			$showsearch=1;
		}
		//物流商
		if (!empty($carrier_code)){
			$data->andWhere("default_carrier_code = :default_carrier_code",[':default_carrier_code'=>$carrier_code]);
			$showsearch=1;
		}
		//运输服务
		if (!empty($shipmethod)){
			$data->andWhere("default_shipping_method_code = :default_shipping_method_code",[':default_shipping_method_code'=>$shipmethod]);
			$showsearch=1;
		}
		//自定义标签
		if (!empty($sel_tag)){
			$data->andWhere('order_id in (select order_id from lt_order_tags where tag_id =:sel_tag) ',[':sel_tag'=>$sel_tag]);
			$showsearch=1;
		}
		//重新发货类型
		if (!empty($reorder_type)){
			if ($reorder_type != 'all'){
				$data->andWhere('reorder_type =:reorder_type ',[':reorder_type'=>$reorder_type]);
			}else{
				$data->andWhere(['not', ['reorder_type' => null]]);
			}
			$showsearch=1;
		}
		//评价
		if (!empty($order_evaluation)){
			$data->andWhere('order_evaluation = :order_evaluation',[':order_evaluation'=>$order_evaluation]);
			$showsearch=1;
		}
		/**
		 * @todo   商品数量
		 */
		
		//排序
		$ordersort = '';
		if (!empty ($customsort)){
			$ordersort .= $customsort.",";
			$showsearch=1;
		}
		//日期搜索
		if (!empty($startdate)||!empty($enddate)||!empty($timetype)||!empty($ordersorttype)){
			//搜索订单日期
			switch ($timetype){
				case 'soldtime':
					$tmp='order_source_create_time';
					$ordersort.='order_source_create_time';
					break;
				case 'paidtime':
					$tmp='paid_time';
					$ordersort.='paid_time';
					break;
				case 'printtime':
					$tmp='printtime';
					$ordersort.='printtime';
					break;
				case 'shiptime':
					$tmp='delivery_time';
					$ordersort.='delivery_time';
					break;
				default:
					$ordersort.= 'order_source_create_time';
					break;
			}
			if (!empty($startdate)){
				$data->andWhere("$tmp >= :starttime",[':starttime'=>strtotime($startdate)]);
			}
			if (!empty($enddate)){
				$enddate = strtotime($enddate) + 86400;
				$data->andWhere("$tmp <= :endtime",[':endtime'=>$enddate]);
			}
			if (!empty($ordersorttype)){
				$ordersort.= " ".$ordersorttype.",";
			}
			$showsearch=1;
		}
		//必须加上一个默认排序
		$ordersort .= 'order_source_create_time desc';
		
		//系统标签
		if  (!empty($order_systags)){
			$showsearch=1;
			//是否取反
			if (!empty($is_reverse)){
				$data->andWhere(['not in', 'order_id', (new \yii\db\Query())->select('order_id')->from('od_order_systags_mapping')->where(['in','tag_code',$order_systags])]);
			}else {
				$data->andWhere(['in', 'order_id', (new \yii\db\Query())->select('order_id')->from('od_order_systags_mapping')->where(['in','tag_code',$order_systags])]);
			}
		}
		if  ($is_print_carrier != ''){
			//是否取反
			if (!empty($is_reverse)){
				$data->andWhere(['not in','is_print_carrier',$is_print_carrier]);
			}else {
				$data->andWhere(['in','is_print_carrier',$is_print_carrier]);
			}
		}
		if(isset($params['is_manual_order']) && trim($params['is_manual_order']) != ''){
			$data->andWhere(['is_manual_order'=>$params['is_manual_order']]);
		}
		$carrier_step = '';
		if(isset($params['carrier_type']) && !empty($params['carrier_type']))
		switch ($params['carrier_type']){
			case '1':$carrier_step = $params['carrier_step'];
					$customCarriers = CarrierOpenHelper::getOpenCarrierArr(3);
					$data->andWhere(['not in','default_carrier_code',array_keys($customCarriers)]);
					break;
			case '2':$carrier_step = $params['excel_step'];
					$excleCarriers = CarrierOpenHelper::getOpenCarrierArr(4);
					$data->andWhere(['default_carrier_code'=>array_keys($excleCarriers)]);break;
			case '3':$carrier_step = $params['track_step'];
					$trackCarriers = CarrierOpenHelper::getOpenCarrierArr(5);
					$data->andWhere(['default_carrier_code'=>array_keys($trackCarriers)]);break;
		}
		if(!empty($carrier_step))
		{
			if($carrier_step == 'UPLOAD')
				$data->andWhere('carrier_step = '.OdOrder::CARRIER_WAITING_UPLOAD.' or carrier_step = '.OdOrder::CARRIER_CANCELED);
			else if($carrier_step == 'DELIVERY')
				$data->andWhere(['carrier_step'=>OdOrder::CARRIER_WAITING_DELIVERY]);
			else if($carrier_step == 'FINISHED')
				$data->andWhere(['carrier_step'=>OdOrder::CARRIER_FINISHED]);
		}
		
		$pages = new Pagination([
				'defaultPageSize' => 20,
				'pageSize' => $pageSize,
				'totalCount' => $data->count(),
				'pageSizeLimit'=>[5,200],//每页显示条数范围
				'params'=>$params,
				]);
		$models = $data->offset($pages->offset)
		->orderBy($ordersort)
		->limit($pages->limit)
		->all();
		
		/*echo models current sql */
// 		$command = $data->offset($pages->offset)
// 		->limit($pages->limit)->createCommand();
// 		echo $command->getRawSql();
		
		return ['models'=>$models,'pages'=>$pages,'showsearch'=>$showsearch];
	}
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 根据流程生成 操作列表数组
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param
	 * 			$code					当前操作的订单流程关键值
	 +---------------------------------------------------------------------------------------------
	 * @return array  	 各栏目的 订单 数
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2016/05/05				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function getCurrentOperationList($code =''){
		$baseOperationList = [];
		switch ($code){
			default:
				$baseOperationList = [
					'changeWHSM'=>'指定发货仓库和运输服务',
					'autoassign'=>'匹配发货仓库和运输服务',
					'reautoassign'=>'重新匹配发货仓库和运输服务',
					'suspendDelivery'=>'暂停发货',
					'outOfStock'=>'标记缺货',
					'signshipped'=>'通知平台发货',
					'signcomplete'=>'标记为已完成',
				];
		}
		return $baseOperationList;
	}//end of getCurrentOperationList
	
	/**
	 * OMS获取发货模块的URL
	 * @param $platform
	 * @return string
	 */
	static public function getDeliveryModuleUrl($platform = ''){
		$url = \Yii::$app->request->hostinfo.'/delivery/order/listnodistributionwarehouse';
		
		if(!empty($platform))
			$url .= '?selleruserid='.$platform;
			
		return $url;
	}
	
	static public function getDeliveryMenuByPlatform($platform){
		//$delivery_picking_mode 是否使用拣货模式 0:表示不使用,1:表示使用
// 		$delivery_picking_mode = ConfigHelper::getConfig('delivery_picking_mode');
// 		$delivery_picking_mode = empty($delivery_picking_mode) ? 0 : $delivery_picking_mode;
		
		//获取主url
		$url = \Yii::$app->request->hostinfo;
	
		$deliveryMenuArr = array();
	
		$canAccessModule = UserApiHelper::checkModulePermission("delivery");
		$canAccessModule_edit = UserApiHelper::checkModulePermission("delivery_edit");
		if(!$canAccessModule && !$canAccessModule_edit) {
			$deliveryMenuArr['没有权限!'] = array(
					'url'=>'#',
					'target'=>'',
			);
			return $deliveryMenuArr;
		}
		if($canAccessModule){
			$deliveryMenuArr['物流商下单'] = array(
					'url'=>$canAccessModule?$url.'/delivery/order/listplanceanorder?'.'use_mode='.$platform:'#',
					'target'=>$canAccessModule?'_norefresh':'',
			);
		}

		if($canAccessModule_edit){
	// 		if($delivery_picking_mode == 1){
				$deliveryMenuArr['打包出库'] = array(
						'url'=>$canAccessModule_edit?$url.'/delivery/order/listprintdelivery?'.'use_mode='.$platform:'#',
						'target'=>$canAccessModule_edit?'_norefresh':'',
				);
	// 		}
		}
	
		return $deliveryMenuArr;
	}
	
	//获取对应的订单是否已经被平台取消
	public static function getOrderIsCancel($order){
		$is_cancel = false;
		
		switch ($order->order_source){
			case 'aliexpress':
				if($order->order_source_status == 'IN_CANCEL'){
					$is_cancel = true;
				}
				break;
			case 'amazon':
				if($order->order_source_status == 'cancel'){
					$is_cancel = true;
				}
				break;
			case 'wish':
				if($order->order_source_status == 'REFUNDED'){
					$is_cancel = true;
				}
				break;
			case 'bonanza':
				if(in_array($order->order_source_status, array('Complete,Cancelled', 'Incomplete,Cancelled', 'InProcess,Cancelled', 'Invoiced,Cancelled'))){
					$is_cancel = true;
				}
				break;
			case 'lazada':
			case 'linio':
			case 'jumia':
				if(in_array($order->order_source_status, array('returned','failed','canceled'))){
					$is_cancel = true;
				}
				break;
			case 'cdiscount':
				if(in_array($order->order_source_status, array('CancelledByCustomer','RefusedBySeller','AutomaticCancellation','PaymentRefused','ShipmentRefusedBySeller','RefusedNoShipment'))){
					$is_cancel = true;
				}
				break;
			case 'newegg':
				if($order->order_source_status == 'Voided'){
					$is_cancel = true;
				}
				break;
			case 'priceminister':
				if(in_array($order->order_source_status, array('claim','refused','cancelled'))){
					$is_cancel = true;
				}
				break;
			case 'dhgate':
				if($order->order_source_status == '111000'){
					$is_cancel = true;
				}
				break;
		}
		
		$count_ban = 0;
		
		foreach ($order->items as $item){
			if($item->delivery_status == 'ban')
				$count_ban++;
		}
		
		if($count_ban == count($order->items))
			$is_cancel = true;
		
		return $is_cancel;
	}
	
	//发货模块获取可以发货的订单Items方法   hqw 20161222
	public static function getDeliveryOrder($order_id, $is_all = false){
		$order_mode = OdOrder::find()->where(['order_id'=>$order_id]);
		
		$order_mode->with(['items'=>function ($query_item){
			$query_item->andWhere(['not in',"ifnull(sku,'')", \eagle\modules\order\helpers\CdiscountOrderInterface::getNonDeliverySku()]);
			$query_item->andWhere(['and',"ifnull(delivery_status,'') != 'ban'"]);
		},]);
		
		if($is_all == false)
			$order = $order_mode->one();
		else
			$order = $order_mode->all();
		
		return $order;
	}
}

