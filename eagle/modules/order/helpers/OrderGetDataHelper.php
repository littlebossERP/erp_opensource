<?php
namespace eagle\modules\order\helpers;

use eagle\modules\order\models\OdOrder;
use yii\data\Pagination;
use eagle\modules\carrier\apihelpers\CarrierApiHelper;
use eagle\modules\order\apihelpers\OrderApiHelper;
use eagle\modules\platform\apihelpers\PlatformAccountApi;
use common\helpers\Helper_Array;
use eagle\modules\order\models\OdOrderItem;
use eagle\modules\dash_board\apihelpers\DashBoardStatisticHelper;
use eagle\modules\dash_board\helpers\DashBoardHelper;
use eagle;
use eagle\models\OdOrderShipped;
use eagle\modules\order\models\Ordertag;


/**
 * 此类的为封装oms 查询相关的接口
 * @author lkh
 *
 */
CLASS OrderGetDataHelper{
	/**
	 +---------------------------------------------------------------------------------------------
	 * 为采购模块自动增强过滤无效条件的接口
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param
	 * 			$query						order::find()
	 +---------------------------------------------------------------------------------------------
	 * @return	$query
	 *
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2016/09/21				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function formatQueryOrderConditionPurchase(&$query, $is_FBA = false){
		if (empty($query)) $query = OdOrder::find();
		//采购不要fba 与 fbc 的订单
		if(!$is_FBA){
			$query->andWhere(['not in' , 'order_type' , ['AFN' , 'FBC']]);
		}
		//只要正常订单和合并后的订单
		$query->andWhere(['order_relation'=>['normal' , 'sm', 'ss', 'fs']]);
		//只要没有解绑的订单
		$query->andWhere(['isshow'=>'Y']);
		//echo $query->createCommand()->getRawSql();
		return $query;
	}//end of formatQueryOrderConditionPurchase
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 获取订单 信息条件格式转换
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param
	 * 			$condition						查询订单条件
	 * 			[
	 * 			is_merge
	 * 			exception_status				string  optional	异常状态
	 * 			selleruserid
	 * 			order_status					string  optional	订单状态
	 * 			saas_platform_user_id			string  optional	saas库平台用户卖家账号id(ebay或者amazon卖家表中)
	 * 			consignee_country_code			string  optional	收件国家
	 * 			default_carrier_code			string  optional	物流商
	 * 			default_shipping_method_code 	string  optional	运输服务
	 * 			custom_tag						array	optional	自定义标签e.g. ['pay_memo'=>1 , ...] 查看 OrderTagHelper::$OrderSysTagMapping
	 * 			reorder_type					string  optional	重新发货类型 查看order moldel 相关值
	 * 			keys							string  optional	精准或模糊查询 的字段名						searchval与fuzzy一起使用
	 * 			searchval						string  optional	精准或模糊查询 的 值						keys与fuzzy一起使用
	 * 			fuzzy 							string  optional	精准或模糊查询 的 开关 1为模糊 ，0或者 空为精确		keys与searchval一起使用
	 * 			order_evaluation				string  optional	评价
	 * 			order_source					string  optional	平台			e.g. ebay , aliexpress
	 * 			tracker_status					string  optional	tracker状态
	 * 			timetype						string  optional	查询的时间字类型  e.g. soldtime, paidtime , printtime , shiptime
	 * 			date_from						string	optional	起始时间						与timetype 一起使用
	 * 			date_to							string	optional	结束时间						与timetype 一起使用
	 * 			customsort						string	optional	排序字段
	 * 			order							string	optional	升降序
	 * 			item_qty						int		optional	商品数量						与 item_qty_compare_operators 一起使用
	 * 			item_qty_compare_operators		string  optional	商品数量比较运算符   e.g. > ,= , < 	与 item_qty 一起使用
	 *
	 * 			default_warehouse_id			int		optional	仓库
	 * 			carrier_step					int		optional	物流商下单状态
	 * 			carrier_type					string	optional	物流类型	1:API	2:excel	3:跟踪号
	 * 			per-page						int		optional	每页多少行
	 * 			distribution_inventory_status	int		optional
	 * 			tracknum						string	optional	根据跟踪号查询
	 * ]
	 +---------------------------------------------------------------------------------------------
	 * @return	array
	 * 				['other_params'=>$other_params , 'eq_params'=>$eq_params , 'date_params'=>$date_params , 'like_params'=>$like_params , 'in_params'=>$in_params , 'sort'=>$sort, 'order'=>$order,'pageSize'=>$pageSize];
	 *
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2016/08/20				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function formatQueryOrderConditionOMS($condition , $addi_condition){

		$other_params=[];
		$eq_params=[];
		$date_params=[];
		$like_params=[];
		$in_params=[];
		$sort='';
		$order='';
		$showsearch='';
		//$pageSize=50;
		$eq_params['isshow'] = "Y"; //隐藏部分订单
		
		if (!empty($condition['ismultipleProduct'])){
			$eq_params['ismultipleProduct'] = $condition['ismultipleProduct'];
		}
		
		if (!empty($addi_condition['order_source'])){
			if (in_array($addi_condition['order_source'], PlatformAccountApi::$platformList)){
				$eq_params['order_source'] = $addi_condition['order_source'];
			}
		}
		//评价等级
		if(!empty($addi_condition['seller_commenttype'])){
			if($addi_condition['seller_commenttype']=='null')
				$eq_params['seller_commenttype'] = null;
			else 
				$eq_params['seller_commenttype'] = $addi_condition['seller_commenttype'];
		}
		
		if (!empty($condition['is_merge'])){
			// 合并订单过滤
			//$data->andWhere(['order_relation'=>'sm']);
			$eq_params['order_relation'] = 'sm';
		}else if(!empty($condition['order_relation_fs'])){
			//拆分订单过滤
			$eq_params['order_relation'] = ['fs','ss'];
		}else{
			//$data->andWhere(['order_relation'=>['normal','sm']]);
			$eq_params['order_relation'] = ['normal','sm','fs','ss'];
		}
		
		if (!empty($condition['exception_status'])){
			$eq_params['exception_status'] = $condition['exception_status'];
		}
		
		if (!empty($condition['issuestatus'])){
			$eq_params['issuestatus'] = $condition['issuestatus'];
		}
		//查询条件转换 start
			
		/*********************************************** 第一行  *********************************************/
		if (!empty($condition['selleruserid'])){
			//搜索卖家账号
			$eq_params['selleruserid'] = $condition['selleruserid'];
		}else{
			//显示当前绑定账号的订单
// 			if (!empty($addi_condition['selleruserid'])){
// 				$in_params['selleruserid'] = $addi_condition['selleruserid'];
// 			}

			if (!empty($condition['selleruserid_combined'])){
				$tmp_selleruserid_combined = $condition['selleruserid_combined'];
				
				if((strlen($tmp_selleruserid_combined) > 8) && (substr($tmp_selleruserid_combined, 0, 4) == 'com-') && (substr($tmp_selleruserid_combined, -4) == '-com')){
					$tmp_selleruserid_combined = substr($tmp_selleruserid_combined, 4);
					$tmp_selleruserid_combined = substr($tmp_selleruserid_combined, 0, strlen($tmp_selleruserid_combined)-4);

					if(!empty($addi_condition['order_source']) && !empty($addi_condition['sys_uid'])){
						$pcCombination = OrderListV3Helper::getPlatformCommonCombination(array('type'=>'oms','platform'=>$addi_condition['order_source'],'com_name'=>$tmp_selleruserid_combined), $addi_condition['sys_uid']);

// 						print_r($pcCombination);
// 						print_r($addi_condition);
						if(in_array($addi_condition['order_source'], array('aliexpress','wish','priceminister','linio','jumia'))){
							if(isset($addi_condition['selleruserid_tmp'])){
								if(count($addi_condition['selleruserid_tmp']) > 0){
									$tmp_selleruserid_al = $addi_condition['selleruserid_tmp'];
									unset($addi_condition['selleruserid_tmp']);
									$addi_condition['selleruserid_tmp'] = array();
									
									foreach ($tmp_selleruserid_al as $tmp_selleruserid_al_V){
										$addi_condition['selleruserid_tmp'][$tmp_selleruserid_al_V] = $tmp_selleruserid_al_V;
									}
								}
							}
						}
						

						if(count($pcCombination) > 0){

							$tmp_pcCombination = array();
							
							foreach ($pcCombination as $pcCombination_K => $pcCombination_V){

								if(!isset($addi_condition['selleruserid_tmp'][$pcCombination_V])){
								}else{
									$tmp_pcCombination[$pcCombination_V] = $pcCombination_V;
								}
							}

							
							if(count($tmp_pcCombination) > 0){
								$in_params['selleruserid'] = $tmp_pcCombination;
							}else{
								//不成功时直接查找xxxxx
								$in_params['selleruserid'] = 'xxxxx';
							}
						}else{
							//不成功时直接查找xxxxx
							$in_params['selleruserid'] = 'xxxxx';
						}
					}else{
						//不成功时直接查找xxxxx
						$in_params['selleruserid'] = 'xxxxx';
					}
				}else{
					if(in_array($addi_condition['order_source'], array('lazada'))){
					}else{
						$in_params['selleruserid'] = $tmp_selleruserid_combined;
					}
				}
			}else{
				//显示当前绑定账号的订单
				if (!empty($addi_condition['selleruserid'])){
					$in_params['selleruserid'] = $addi_condition['selleruserid'];
				}
			}
		}
		
		if (!empty($condition['searchval'])){
			//搜索用户自选搜索条件
			$condition['searchval'] = str_replace('；', ';', $condition['searchval']);
			//有空格就使用空格拆开， 否则就使用分号拆开
			if (stripos('1'.trim($condition['searchval']) , ' ')>0 && in_array($condition['keys'], ['order_source_order_id','order_id','ebay_orderid','srn','tracknum'])){
				$searchval = explode(' ', $condition['searchval']);
			}else{
				$searchval = explode(';', $condition['searchval']);
			}
			
			Helper_Array::removeEmpty($searchval);
			if(count($searchval)==1)
				$searchval = $searchval[0];
			if (in_array($condition['keys'], ['order_id','ebay_orderid','srn','buyerid','buyeid','email','consignee','transaction_key'])){
				$kv=[
				'order_id'=>'order_id',
				'ebay_orderid'=>'order_source_order_id',
				//'order_source_order_id'=>'order_source_order_id',
				'srn'=>'order_source_srn',
				'buyerid'=>'source_buyer_user_id',
				'buyeid'=>'source_buyer_user_id',
				'email'=>'consignee_email',
				'consignee'=>'consignee',
				'transaction_key'=>'transaction_key',
						];
				$key = $kv[$condition['keys']];
				if (!empty($condition['fuzzy'])){
					if(in_array($key, ['source_buyer_user_id', 'consignee']))
						$like_params["replace($key, ' ', '')"] = str_replace(' ', '', $searchval);
					else 
						$like_params[$key] = $searchval;
				}else{
					if(in_array($key, ['source_buyer_user_id', 'consignee']))
						$eq_params["replace($key, ' ', '')"] = str_replace(' ', '', $searchval);
					else
						$eq_params[$key] = $searchval;
				}
				
			}elseif ($condition['keys']=='sku'){
				$other_params['sku'] = $searchval;
			}elseif (in_array($condition['keys'],['itemid','order_source_itemid'])){
				$other_params['order_source_itemid'] = $searchval;
			}elseif ($condition['keys']=='tracknum'){
				$other_params['tracknum'] = $searchval;
		
			}elseif (in_array($condition['keys'],['order_source_order_id'])){// 为支持合并订单搜索，所以搜item表的order_source_order_id
				$other_params['order_source_order_id'] = $searchval;
			}elseif($condition['keys']=='product_name'){
				$other_params['product_name'] = $searchval;
			}elseif ($condition['keys']=='root_sku'){
				$other_params['root_sku'] = $searchval;
			}elseif ($condition['keys']=='product_name'){
				$other_params['product_name'] = $searchval;
			}elseif ($condition['keys']=='prod_name_ch'){
				$other_params['prod_name_ch'] = $searchval;
			}
			
			if(!empty($condition['fuzzy']))
				$other_params['fuzzy'] = $condition['fuzzy'];
		}
		/*********************************************** 第二行  *********************************************/
		if (!empty($condition['country'])){
			$eq_params['consignee_country_code'] = $condition['country'];
			$showsearch='in';
		}
			
		if (!empty($condition['carrier_code'])){
			//物流商
			$eq_params['default_carrier_code'] = $condition['carrier_code'];
			$showsearch='in';
		}
			
		if (!empty($condition['shipmethod'])){
			//搜索运输服务
			$eq_params['default_shipping_method_code'] = $condition['shipmethod'];
		
			$showsearch='in';
			//echo $condition['shipmethod']." and ".$showsearch;
		}
			
		if (!empty($condition['tracker_status'])){
			//logistic_status 先于erp2.1， 所以 tracker_status 废弃不使用
			//tracker 状态
			$eq_params['logistic_status'] = $condition['tracker_status'];
			$showsearch='in';
		}
		/*********************************************** 第三行  *********************************************/
		if (!empty($condition['order_status'])){
			//搜索订单状态
			$eq_params['order_status'] = $condition['order_status'];
			//生成操作下拉菜单的code
			$op_code = $condition['order_status'];
		}
		
		if (!empty($condition['order_source_status'])){
			//搜索订单原始状态
			$eq_params['order_source_status'] = $condition['order_source_status'];
		}
			
		if (!empty($condition['fuhe'])){
			$showsearch="in";
			//搜索符合条件
			switch ($condition['fuhe']){
				case 'haspayed':
					$eq_params['pay_status'] = 1;
					break;
				case 'hasnotpayed':
					$eq_params['pay_status'] = 0;
					break;
				case 'pending':
					$eq_params['pay_status'] = 0;
					break;
				case 'hassend':
					$eq_params['shipping_status'] = 1;
					break;
				case 'payednotsend':
					$eq_params['shipping_status'] = 0;
					$eq_params['pay_status'] = 1;
					break;
				case 'hasinvoice':
					$eq_params['hassendinvoice'] = 1;
					break;
				default:break;
			}
		}
			
		if (!empty($condition['pay_order_type'])){
			if($condition['pay_order_type'] != 'all'){
				//已付款订单类型
				$eq_params['pay_order_type'] = $condition['pay_order_type'];
				if (!empty($_POST['pay_order_type'])){
					$showsearch='in';
				}
					
			}
		}
			
			
		if (!empty($condition['reorder_type'])){
			if ($condition['reorder_type'] != 'all'){
				//重新发货类型
				$eq_params['reorder_type'] = $condition['reorder_type'];
			}else{
				$other_params['list_all_reorder'] = 1;
				//生成操作下拉菜单的code
				$op_code = 'reo';
			}
		
			$showsearch='in';
		}
		/*********************************************** 第四行  *********************************************/
			
		if (isset($condition['cangku']) && trim($condition['cangku']) !==''){
			//搜索仓库
			$eq_params['default_warehouse_id'] = $condition['cangku'];
			$showsearch='in';
		}
			
		if (!empty($condition['order_capture'])){
			//手工订单查询
			$eq_params['order_capture'] = $condition['order_capture'];
			$showsearch='in';
		}
		
		if (!empty($condition['order_verify'])){
			//验证通过 查询
			if ($condition['order_verify']== 'noneed'){
				//没有 开启同步
				$in_params['ifnull(order_verify,"")'] = [''];
			}else{
				$eq_params['order_verify'] = $condition['order_verify'];
			}
			$showsearch='in';
		}
			
		//时间搜索
		if (!empty($condition['startdate'])||!empty($condition['enddate'])){
			//搜索订单日期
			switch ($condition['timetype']){
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
					$tmp='complete_ship_time';
					break;
				case 'modifytime':
					$tmp='last_modify_time';
					break;
				default:
					$tmp='order_source_create_time';
					break;
			}
			if (!empty($condition['startdate'])){
				$date_params=['field_name'=>$tmp , 'date_from'=>($condition['startdate'])];
			}
			if (!empty($condition['enddate'])){
				$enddate = ($condition['enddate']) ;
				if (isset($date_params['field_name'])){
					$date_params['date_to']=$enddate;
				}else{
					$date_params=['field_name'=>$tmp , 'date_to'=>$enddate];
				}
			}
			$showsearch='in';
		}
		
		//排序
		$sort = 'order_source_create_time';//默认按照付款时间
		if (!empty ($condition['customsort'])){
			$sort = $condition['customsort'];
			/**/
			switch ($condition['customsort']){
				case 'soldtime':
					$sort='order_source_create_time';
					break;
				case 'paidtime':
					$sort='paid_time';
					//$showsearch='in';
					break;
				case 'printtime':
					$sort='printtime';
					//$showsearch='in';
					break;
				case 'shiptime':
					$sort='delivery_time';
					//$showsearch='in';
					break;
				case 'order_id':
					$sort='order_id';
					//$showsearch='in';
					break;
				case 'grand_total':
					$sort='grand_total';
					//$showsearch='in';
					break;
				case 'fulfill_deadline':
					$sort='fulfill_deadline';
					break;
				case 'first_sku':
					$sort='first_sku';
					break;
				default:
					$sort='order_source_create_time';
					//$showsearch='in';
					break;
			}
			
		}
		//是否升序
		if (!empty ($condition['ordersorttype'])){
			$order=$condition['ordersorttype'];
			//$showsearch='in';
		}else{
			$order='desc';
		}
		
		// dzt20160820 待合并排序，让可以合并在一起的订单连着，然后统一用样式框住
		if (200 == @$condition['order_status'] && 223 == @$condition['exception_status']){
			$sort = "selleruserid asc,consignee asc,consignee_address_line1 asc,consignee_address_line2 asc,consignee_address_line3 asc,$sort $order";
			$order = '';
		}
		
		/*********************************************** 第五行  *********************************************/
		/* 订单系统标签 查询*/
		$sysTagList = [];
		foreach(OrderTagHelper::$OrderSysTagMapping as $tag_code=>$label){
			//1.勾选了系统标签；
			if (!empty($condition[$tag_code]) ){
				//生成 tag 标签的数组
				$sysTagList[] = $tag_code;
			}
		}
		if  (!empty($sysTagList)){
			$other_params['sysTagList'] = $sysTagList;
			$showsearch="in";
		}
		/*********************************************** 第六行  *********************************************/
		//自定义标签
		if (!empty($condition['sel_tag'])){
			//搜索卖家账号
			$other_params['custom_tag'] =$condition['sel_tag'];
			$showsearch="in";
		}
		
		/*********************************************** 虚拟发货状态   *********************************************/
		/* 虚拟发货状态 查询*/
		if (!empty($condition['order_sync_ship_status'])){
			$eq_params['sync_shipped_status'] = $condition['order_sync_ship_status'];
			//$showsearch='in';
		}
		//查询条件转换 end
		return ['other_params'=>$other_params , 'eq_params'=>$eq_params , 'date_params'=>$date_params , 'like_params'=>$like_params , 'in_params'=>$in_params , 'sort'=>$sort, 'order'=>$order,/*'pageSize'=>$pageSize ,*/ 'showsearch'=>$showsearch];
	}//end of function formatQueryOrderConditionOMS
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 获取订单的信息
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param     $like_params			like 相关的查询条件
	 * @param     $eq_params           	equal 相关的查询条件 需要指定刷选的fields以及值，field name要和字段名一样，值可以多个可能的，逗号隔开
	 *                              例如 array( order_status=>'200',
	 *                                       )
	 * @param     $date_params		   	时间范围的查询条件
	 * @param     $in_params		    in 相关的查询条件
	 * @param     $other_params		    other 就是一些子查询之类或者特殊的条件
	 * @param     $sort            		 指定排序field
	 * @param     $order            	排序顺序
	 * @param     $pageSize         	每页显示数量，默认是50
	 * @param	  $showItem				productOnly 只显示item中真实的商品 , all  所有item
	 +---------------------------------------------------------------------------------------------
	 * @return						$data
	 *
	 * @invoking					OrderHelper::getOrderListByCondition();
	 *
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2016/2/19				初始化
	 +---------------------------------------------------------------------------------------------
	 * $page_related_params  后端调用时控制分页的页数
	 **/
	public static function getOrderListByCondition($like_params,$eq_params, $date_params , $in_params, $other_params, $sort , $order , $pageSize=50 ,$noPagination = false,$showItem='productOnly' , &$query=null , $page_related_params = array()){
		$showsearch = 0;
		$returnQuery = false;

		if (empty($query)){
			$query = OdOrder::find();
		}else{
			$returnQuery = true;
		}

		//与 getShipmethodGroupWithOrder 共用 查询条件的封装函数
		self::setOrderCondition($query, $like_params,$eq_params, $date_params , $in_params, $other_params);

		$query->orderBy("$sort $order");

		if ($returnQuery){
			$data['showsearch'] = $showsearch;
			return $data;
		}

		$tmp_max_size = 50000;
		if((isset($page_related_params['noPagination'])) && (isset($page_related_params['max_size']))){
			$noPagination = true;
			$tmp_max_size = empty($page_related_params['max_size']) ? 50000 : $page_related_params['max_size'];
			
			unset($page_related_params['noPagination']);
			unset($page_related_params['max_size']);
		}
		
		
		$tmp_page_params = array();
		if(!empty($page_related_params)){
			$tmp_page_params = $page_related_params;
		}else{
			if(empty($_REQUEST)){
				$tmp_page_params = array('per-page'=>$pageSize);
			}else{
				$tmp_page_params = $_REQUEST;
				if(!isset($_REQUEST['per-page'])){
					$tmp_page_params += array('per-page'=>$pageSize);
				}
			}
		}

		// dzt20190419 订单界面打开优化
		$t1 = \eagle\modules\util\helpers\TimeUtil::getCurrentTimestampMS();
		$subdbConn=\yii::$app->subdb;
		$indexArr = $subdbConn->createCommand("show index from `od_order_v2`")->queryAll();
		// dzt20190419 idx_all_list是给部分多订单客户打开订单首页添加的index
		$indexArrMap = Helper_Array::toHashmap($indexArr, "Key_name");
		
		$tmpCountQ = clone $query;
		$tmpCountSql = $tmpCountQ->orderBy('')->createCommand()->getRawSql();// 只是要count 去掉order by
		$tmpCountSql = str_ireplace("SELECT * FROM", "SELECT count(*) FROM ", $tmpCountSql);
		if(!empty($indexArrMap['idx_all_list'])){
		    $tmpCountSql = str_ireplace("SELECT * FROM `od_order_v2` WHERE", "SELECT count(*) FROM `od_order_v2` use INDEX (`idx_all_list`) WHERE", $tmpCountSql);
// 		    $tmpCountSql = str_ireplace("where", " use INDEX (`idx_all_list`) where ", $tmpCountSql);// join表搜item的时候把join的item表的where也替换了。
		}
            
		
		$pageCount = $subdbConn->createCommand($tmpCountSql)->queryScalar();
		$t2 = \eagle\modules\util\helpers\TimeUtil::getCurrentTimestampMS();
// 		\Yii::info('class:'.__CLASS__.',function:'.__FUNCTION__.", mark t1=".($t2 - $t1)." pageCount:$pageCount sql：".$tmpCountSql, "file");
// 		$pageCount = $query->count();
		
		
		//Pagination 会自动获取Post或者get里面的page number，自动计算offset
		$pagination = new Pagination([
				'totalCount'=> $pageCount,
				'pageSizeLimit'=>  [5,  ( $noPagination ? $tmp_max_size : 200 )  ],
				'params'=>$tmp_page_params,
				]);
		// 		$_REQUEST, $query_condition
		//$data ['condition'] = $condition;
	
		// 调试sql
		/*
		$tmpCommand = $query->createCommand();
		echo $tmpCommand->getRawSql();
		//exit();
		*/

		if ($showItem == "productOnly"){
			$query->with(['items'=>function ($query_item){
				$query_item->andWhere(['not in',"ifnull(sku,'')",CdiscountOrderInterface::getNonDeliverySku()]);
				
				//这里不在这里控制是否显示取消的Items
// 				$query_item->andWhere(['and',"ifnull(delivery_status,'') != 'ban'"]);
			},]);
		}

		$data['data'] = $query->offset($pagination->offset)
		->limit($pagination->limit)
		->orderBy(" $sort $order ")
		->all();

		$data['pagination'] = $pagination;		
		$data['showsearch'] = $showsearch;
		return $data;
	}//end of function getOrderListByCondition
	
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 封装设置 获取订单的信息的查询条件
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param     $like_params			like 相关的查询条件
	 * @param     $eq_params           	equal 相关的查询条件 需要指定刷选的fields以及值，field name要和字段名一样，值可以多个可能的，逗号隔开
	 *                              例如 array( order_status=>'200',
	 *                                       )
	 * @param     $date_params		   	时间范围的查询条件
	 * @param     $in_params		    in 相关的查询条件
	 * @param     $other_params		    other 就是一些子查询之类或者特殊的条件
	 +---------------------------------------------------------------------------------------------
	 * @return						$data
	 *
	 * @invoking					OrderHelper::getOrderListByCondition();
	 *
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2016/3/23				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function setOrderCondition(&$query , $like_params,$eq_params, $date_params , $in_params, $other_params){
		//检查 like 条件的参数集是否为空
		if (!empty($like_params)){
			foreach($like_params as $filed_name=>$filed_value){
				$query->andWhere(['like' , $filed_name , $filed_value ]);
			}
		}
		
		//检查 equal 条件的参数集是否为空
		if (!empty($eq_params)){
			foreach($eq_params as $filed_name=>$filed_value){
				if( is_string($filed_value) && (strtoupper($filed_value)=='NULL' || strtoupper($filed_value)=='NOT NULL') ){
					$query->andWhere( $filed_name." is ".strtoupper($filed_value) );
					continue;
				}
				
				$query->andWhere([ $filed_name => $filed_value ]);

				if ($filed_name == 'order_source'){
					
					//testkh start 方便本地开发
					if (isset(\Yii::$app->params["currentEnv"]) and \Yii::$app->params["currentEnv"]=="test" || !empty($in_params['selleruserid'])){
						$AccountList = [];//testkh
					}
					//testkh end
					else{
						//只显示 当前绑定 的绑定
						$AccountList = PlatformAccountApi::getPlatformAllAccount('', $filed_value);
					}
					
					//如果为测试用的账号就不受平台绑定限制
					$test_userid=eagle\modules\tool\helpers\MirroringHelper::$test_userid;
					if (in_array(\Yii::$app->subdb->getCurrentPuid(),$test_userid['yifeng'])){
						$AccountList = [];
					}

					if (isset($AccountList['data'])){
						if (!empty($AccountList['data'])){
							if (in_array($filed_value,['amazon','cdiscount','priceminister','rumall','customized','newegg', 'shopee']) ){
									
								$tmpAccount = array_flip($AccountList['data']);
							}else{
								$tmpAccount = $AccountList['data'];
							}
						}else{
							//账号全部解绑
							$tmpAccount = [];
						}
						
						$query->andWhere(['in','selleruserid',$tmpAccount]);
					}
						
				}
			}
	
		}

		//检查 in 条件的参数集是否为空
		if (!empty($in_params)){
			foreach($in_params as $filed_name=>$filed_value){
				if (! is_array($filed_value)){
					$filed_value = explode(',', $filed_value);
				}
				$query->andWhere(['in' , $filed_name , $filed_value ]);
			}
		}

		//检查 日期 参数集
		if (!empty($date_params)){
			if(!empty($date_params['field_name']) && $date_params['field_name']=='last_modify_time'){
				//起始时间
				if (!empty($date_params['field_name']) && !empty($date_params['date_from'])){
					$query->andWhere($date_params['field_name']." >= :stime",[':stime'=>$date_params['date_from']]);
				}
				
				//结束时间
				if (!empty($date_params['field_name']) && !empty($date_params['date_to'])){
					$query->andWhere($date_params['field_name']." <= :etime",[':etime'=>$date_params['date_to'].' 23:59:59' ]);
				}
			}elseif(!empty($date_params['field_name']) && $date_params['field_name']!=='last_modify_time'){
				//起始时间
				if (!empty($date_params['field_name']) && !empty($date_params['date_from'])){
					$query->andWhere($date_params['field_name']." >= :stime",[':stime'=>strtotime($date_params['date_from'])]);
				}
				
				//结束时间
				if (!empty($date_params['field_name']) && !empty($date_params['date_to'])){
					//时间格式检测
					if(strlen($date_params['date_to'])>13)//xxxx-xx-xx xx:xx:xx的格式,直接用该时间timestramp
						$etime = strtotime($date_params['date_to']);
					if(strlen($date_params['date_to'])==10)//xxxx-xx-xx 的格式,用日期结尾timestramp,即补全23：59：59
						$etime = strtotime($date_params['date_to']) + 86400;
					
					$query->andWhere($date_params['field_name']." <= :etime",[':etime'=>$etime ]);
				}
			}
	
			if (!empty($date_params['field_name']) && (!empty($date_params['date_from']) || !empty($date_params['date_to'])) && !empty($date_params['ordersorttype'])){
				$query->orderBy($date_params['field_name'].' '.$date_params['ordersorttype']);
			}
		}

		//检查 其他 特殊条件 的参数集 是否为空
		if (!empty($other_params)){
			if  (!empty($other_params['carrier_type'])){
				switch ($other_params['carrier_type']){
					case '1':
						$myCarriers = CarrierApiHelper::getCarrierList(2,-1);
						$query->andWhere(['default_carrier_code'=>array_keys($myCarriers)]);
						break;
					case '2':
						$excleCarriers = CarrierApiHelper::getCarrierList(3,-1);
						$query->andWhere(['default_carrier_code'=>array_keys($excleCarriers)]);
						break;
					case '3':
						$trackCarriers = CarrierApiHelper::getCarrierList(4,-1);
						$query->andWhere(['default_carrier_code'=>array_keys($trackCarriers)]);
						break;
				}
			}
			//自定义标签 查询
			if  (!empty($other_params['custom_tag'])){
				if (is_string($other_params['custom_tag'])){
					$customTagList = explode(",", $other_params['custom_tag']);
				}elseif(is_array($other_params['custom_tag'])){
					$customTagList = $other_params['custom_tag'];
				}else{
					$customTagList = [];
				}
				if (!empty($customTagList)){
					//查询自定义标签对应的order_indicator_code
					$order_indicator_code_list = array();
					$ordertags = Ordertag::find()->where(['tag_id' => $customTagList])->asArray()->all();
					foreach($ordertags as $tag){
						if(empty($tag['order_indicator_code']) || $tag['order_indicator_code'] > 10 || $tag['order_indicator_code'] < 1){
							continue;
						}
						$order_indicator_code_list[] = $tag['order_indicator_code'];
					}
					foreach($order_indicator_code_list as  $row){
						$query->andWhere('customized_tag_'.$row.' ="Y" ');
					}
				}
				
				//$query->andWhere('order_id in (select order_id from lt_order_tags where tag_id in ('.implode(",", $other_params['custom_tag']).')) ');
			}
			//订单系统标签 查询
			if  (!empty($other_params['sysTagList'])){
				$showsearch=1;
					
				if (! empty($other_params['sysTagList']['is_reverse'])){
					//取反操作
					$reverseStr = "not ";
				}else{
					$reverseStr = "";
				}
					
				$query->andWhere([$reverseStr.'in', 'order_id', (new \yii\db\Query())->select('order_id')->from('od_order_systags_mapping')->where(['tag_code' => $other_params['sysTagList']])]);
			}
	
			//sku
			if (!empty($other_params['sku'])){
				if (!empty($other_params['fuzzy'])){
					// 模糊搜索
					$query->andWhere(['in', 'order_id', (new \yii\db\Query())->select('order_id')->from('od_order_item_v2')->where(['like', 'sku' , $other_params['sku']  ])]);
				}else{
					// 精确搜索
					if(!is_array($other_params['sku']))
						$tmpSkus[] = $other_params['sku'];
					else 
						$tmpSkus = $other_params['sku'];
					
					/*
					$tmpRt = \eagle\modules\catalog\helpers\ProductHelper::getAllAliasRelationBySku($other_params['sku']);
					$skuList = [$other_params['sku']=>$other_params['sku']];
					
					foreach($tmpRt as $sku=>$info){
						$skuList[$sku] = $sku;
					}
					*/
					$query->andWhere(['in', 'order_id', (new \yii\db\Query())->select('order_id')->from('od_order_item_v2')->where(['sku' =>$other_params['sku']])]);
				}
			}
			
			//root_sku
			if (!empty($other_params['root_sku'])){
				if (!empty($other_params['fuzzy'])){
					// 模糊搜索
					$query->andWhere(['in', 'order_id', (new \yii\db\Query())->select('order_id')->from('od_order_item_v2')->where(['like', 'root_sku' , $other_params['root_sku']  ])]);
				}else{
					// 精确搜索
					$query->andWhere(['in', 'order_id', (new \yii\db\Query())->select('order_id')->from('od_order_item_v2')->where(['root_sku' =>$other_params['root_sku']])]);
				}
			}
			
			//product name 
			if (!empty($other_params['product_name'])){
				if (!empty($other_params['fuzzy'])){
					// 模糊搜索
					$query->andWhere(['in', 'order_id', (new \yii\db\Query())->select('order_id')->from('od_order_item_v2')->where(['like', 'product_name' , $other_params['product_name']  ])]);
				}else{
					// 精确搜索
					$query->andWhere(['in', 'order_id', (new \yii\db\Query())->select('order_id')->from('od_order_item_v2')->where(['product_name' => $other_params['product_name']])]);
				}
			}
			
			//prod_name_ch
			if (!empty($other_params['prod_name_ch'])){
				if (!empty($other_params['fuzzy'])){
					// 模糊搜索
					$query->andWhere(['in', 'order_id', (new \yii\db\Query())->select('order_id')->from('od_order_item_v2')->where(['in', 'root_sku', (new \yii\db\Query())->select('sku')->from('pd_product')->where(['like', 'prod_name_ch', $other_params['prod_name_ch'] ])])]);
				}else{
					// 精确搜索
					$query->andWhere(['in', 'order_id', (new \yii\db\Query())->select('order_id')->from('od_order_item_v2')->where(['in', 'root_sku', (new \yii\db\Query())->select('sku')->from('pd_product')->where(['prod_name_ch' => $other_params['prod_name_ch']])])]);
				}
			}
	
			//tracknum
			if (!empty($other_params['tracknum'])){
				if (!empty($other_params['fuzzy'])){
					// 模糊搜索
// 					$query->andWhere(['in', 'order_id', (new \yii\db\Query())->select('order_id')->from('od_order_shipped_v2')->where(['like', 'tracking_number' , $other_params['tracknum']])]);
					//部分货代因为跟踪号记录在return_no字段上面,买家也需要这个来查询
					$query->andWhere(['in', 'order_id', (new \yii\db\Query())->select('order_id')->from('od_order_shipped_v2')->where(['or', ['like', 'tracking_number' , $other_params['tracknum']], ['like', 'return_no' , $other_params['tracknum']] ])]);
				}else{
					// 精确搜索
					$query->andWhere(['in', 'order_id', (new \yii\db\Query())->select('order_id')->from('od_order_shipped_v2')->where(['tracking_number' =>$other_params['tracknum']])]);
				}
			}
	
			//order_source_itemid
			if (!empty($other_params['order_source_itemid'])){
				if (!empty($other_params['fuzzy'])){
					// 模糊搜索
					$query->andWhere(['in', 'order_id', (new \yii\db\Query())->select('order_id')->from('od_order_item_v2')->where(['like', 'order_source_itemid' , $other_params['order_source_itemid']])]);
				}else{
					// 精确搜索
					$query->andWhere(['in', 'order_id', (new \yii\db\Query())->select('order_id')->from('od_order_item_v2')->where(['order_source_itemid' =>$other_params['order_source_itemid']])]);
				}
			}
				
			//'order_source_order_id'
			if (!empty($other_params['order_source_order_id'])){
				if (!empty($other_params['fuzzy'])){
					// 模糊搜索
					$query->andWhere(['in', 'order_id', (new \yii\db\Query())->select('order_id')->from('od_order_item_v2')->where(['like', 'order_source_order_id' , $other_params['order_source_order_id']])]);
				}else{
					// 精确搜索
					$query->andWhere(['in', 'order_id', (new \yii\db\Query())->select('order_id')->from('od_order_item_v2')->where(['order_source_order_id' =>$other_params['order_source_order_id']])]);
				}
			}
	
			//数量
			if (isset($other_params['item_qty']) && !empty($other_params['item_qty_compare_operators'])){
				$query->andWhere(['in', 'order_id', (new \yii\db\Query())->select('order_id')->from('od_order_item_v2')->where([$other_params['item_qty_compare_operators'], 'ordered_quantity' , $other_params['item_qty']])]);
	
			}
			//未指定仓库或者运输服务
			if  (!empty($other_params['no_warehouse_or_shippingservice'])){
				$query->andWhere('default_warehouse_id = -1 or default_shipping_method_code = ""');
			}
			//已经指定仓库或者运输服务
			if  (!empty($other_params['warehouse_and_shippingservice'])){
				$query->andWhere('default_warehouse_id > -1 and default_shipping_method_code <> ""');
			}
			//未指定运输服务
			if  (!empty($other_params['is_comment_status'])){
				$query->andWhere('is_comment_status = 0');
			}
			//未指定运输服务
			if  (!empty($other_params['no_shippingservice'])){
				$query->andWhere('default_shipping_method_code = ""');
			}
			//未指定仓库
			if  (!empty($other_params['no_warehouse'])){
				$query->andWhere('default_warehouse_id = -1');
			}
				
			if(isset($other_params['order_source_aliexpress'])){
				//不显示 解绑的账号的订单 start
				$listUnbindingAcount = OrderApiHelper::listUnbindingAcount('aliexpress')['aliexpress'];
				$query->andWhere(['not in','selleruserid',$listUnbindingAcount]);
				//不显示 解绑的账号的订单 end
			}
				
			//列出所有重新发货的订单
			if (isset($other_params['list_all_reorder'])){
				$query->andWhere(['not', ['reorder_type' => null]]);
			}
			
			//当没有传入平台和seller时，查询所有有权限的店铺
			if(isset($other_params['authorize_seller_arr'])){
				$query->andWhere($other_params['authorize_seller_arr']);
			}
			
			if(isset($other_params['isExistTrackingNO'])){
				if($other_params['isExistTrackingNO'] == 'Y'){
					$query->andWhere("ifnull(tracking_number,'') != ''");
				}else{
					$query->andWhere("ifnull(tracking_number,'') = ''");
				}
			}
		}
	}//end of function setOrderCondition
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 获取补发订单当前是第几张补发
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param	array:	$orderId					小老板单号
	 +---------------------------------------------------------------------------------------------
	 * @return	int 发货次数
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2016/09/01				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	 static public function getReOrderSequenceNumber($orderID){
		//检查传递的参数 是否正确
		if ($orderID instanceof OdOrder){
			$order = $orderID;
		}else if(is_string($orderID ) || is_int($orderID )){
			$order = OdOrder::findOne($orderID);
		}else{
			return ['ack'=>false , 'message'=>'E4001参数不正确!' , 'code'=>'40001' , 'data'=>''];
		}
		
		if (empty($order)) return ['ack'=>false , 'message'=>'E4002订单无效!' , 'code'=>'40002' , 'data'=>''];
		//已出库补发
		$OrderIdList = [];
		if ($order->reorder_type == 'after_shipment'){
			
			foreach(OdOrder::find()->where(['order_source'=>$order->order_source, 'order_source_order_id'=>$order->order_source_order_id  , 'selleruserid'=>$order->selleruserid ])->asArray()->each(1) as $row){
				$OrderIdList [] = (int)$row['order_id'];
			}
			$OrderIdList = array_flip($OrderIdList);
			$no = $OrderIdList[(int)$order->order_id];
			if (empty($no)) $no ='';
			return ['ack'=>true , 'message'=>'' , 'code'=>'200' , 'data'=>$no];
		}else{
			return ['ack'=>true , 'message'=>'' , 'code'=>'200' , 'data'=>''];
		}
		
		/*
		//检查当前订单是否合并订单还是正常订单
		if ($order->order_relation == 'normal'){
			//正常订单
		}else if ($order->order_relation == 'sm'){
			//合并订单
		}
		*/
		
	 }//end of function  getReOrderSequenceNumber
	 
	 //&$query , $query_condition = [] , $addi_condition = [] 
	 static public function getOrderItemGroupListByCondition($query, $selectColumns , $groupByColumns , $query_condition , $addi_condition){
	 	$data = [];
	 	try {
	 		$returnQuery = false;
	 		 
	 		if (empty($query)){
	 			$query = OdOrderItem::find();
	 		}else{
	 			$returnQuery = true;
	 		}
	 		
	 		$params = OrderApiHelper::_formatQueryOrderCondition($query_condition);
	 		foreach($params as $key=>$value){
	 			${$key} = $value;
	 		}
	 		//与 getShipmethodGroupWithOrder 共用 查询条件的封装函数
	 		 
	 		self::setOrderCondition($query, $like_params,$eq_params, $date_params , $in_params, $other_params);
	 		$sql = "SELECT `sku`, `product_name`, `photo_primary` SUM(quantity) FROM `od_order_item_v2` i left join od_order_v2 o on i.order_id = o.order_id  GROUP BY `sku`, `product_name`, `photo_primary`";
	 		$connection = \Yii::$app->get('subdb');
	 		
	 		//$query->orderBy("$sort $order");
	 		 
	 		if ($returnQuery){
	 			return $data;
	 		}
	 		
	 		if (empty($pageSize) )  $pageSize = 50;
	 		$noPagination  = false;
	 		 
	 		//Pagination 会自动获取Post或者get里面的page number，自动计算offset
	 		$pagination = new Pagination([
	 				'totalCount'=> $query->count(),
	 				'defaultPageSize'=> $pageSize,
	 				'pageSizeLimit'=>  [5,  ( $noPagination ? 50000 : 200 )  ],
	 				'params'=>$_REQUEST,
	 				]);
	 		// 		$_REQUEST, $query_condition
	 		//$data ['condition'] = $condition;
	 		 
	 		// 调试sql
	 		/*
	 		 $tmpCommand = $query->createCommand();
	 		echo $tmpCommand->getRawSql();
	 		//exit();
	 		*/
	 		 
	 		$data['data'] = 
	 		$query->select(['sku','product_name' , 'photo_primary' , 'sum(quantity)'])
	 		->andWhere(['not in',"ifnull(sku,'')",CdiscountOrderInterface::getNonDeliverySku()])
	 		->groupBy(['sku','product_name' , 'photo_primary'])->asArray()
	 		->offset($pagination->offset)
	 		->limit($pagination->limit)
	 		->all();
	 		 
	 		$data['pagination'] = $pagination;
	 	} catch (\Exception $e) {
	 		echo __function__." ".$e->getMessage()." and line no ".$e->getLine();
	 	}
	 	
	 	return $data;
	 }//end of function getOrderItemGroupListByCondition
	 
	 
	 
	 static public function setOrderItemGourpByCondition($query, $selectColumns , $groupByColumns, $like_params,$eq_params, $date_params , $in_params, $other_params){
	 	
	 } 
	 
	 /**
	  +---------------------------------------------------------------------------------------------
	  * 获取当前虚拟发货记录的统计数量， 并更新redis
	  +---------------------------------------------------------------------------------------------
	  * @access static
	  +---------------------------------------------------------------------------------------------
	  * @param	array:	$orderId					小老板单号
	  +---------------------------------------------------------------------------------------------
	  * @return	int 发货次数
	  +---------------------------------------------------------------------------------------------
	  * log			name	date					note
	  * @author		lkh		2016/09/01				初始化
	  +---------------------------------------------------------------------------------------------
	  **/
	 static public function getOrderSyncShipSituation($platform = '',$order_status=''){
	 	$andSql = '';
	 	if (!empty($platform)){
	 		$andSql .= " and order_source = '$platform' ";
	 		
	 			
	 		//testkh start 方便本地开发
	 		if (isset(\Yii::$app->params["currentEnv"]) and \Yii::$app->params["currentEnv"]=="test" ){
	 			$AccountList = [];//testkh
	 		}
	 		//testkh end
	 		else{
	 			//只显示 当前绑定 的绑定
	 			$AccountList = PlatformAccountApi::getPlatformAllAccount('', $platform);
	 		}
	 		//获取redis数据， 后面拿进结果后进行对比 ， 不一样的就要更新redis
	 		$puid = \Yii::$app->subdb->getCurrentPuid();
	 		if (isset(DashBoardHelper::${'SIGNSHIPPED_ORDER_ERR_'.strtoupper($platform)})){
	 			$redisFCount = DashBoardStatisticHelper::CounterGet($puid, DashBoardHelper::${'SIGNSHIPPED_ORDER_ERR_'.strtoupper($platform)});
	 			
	 		}else{
	 			$redisFCount = 0;
	 		}
	 			
	 		if (isset($AccountList['data'])){
	 			if (!empty($AccountList['data'])){
	 			
	 				if (in_array($platform,['amazon','cdiscount','priceminister','rumall','newegg']) ){
	 					$tmpAccount = array_flip($AccountList['data']);
	 				}else{
	 					$tmpAccount = $AccountList['data'];
	 				}
	 				//$query->andWhere(['in','selleruserid',$tmpAccount]);
	 				
	 				foreach ($tmpAccount as $tmpi=>$tmpAccStr){
	 				    $tmpAccount[$tmpi] = addslashes($tmpAccStr);
	 				}
	 				$andSql .= " and selleruserid in ('".implode("','", $tmpAccount)."') ";
	 			}else{
	 				//账号全部解绑
	 				$andSql .= " and 1=0";
	 			}
	 		}
	 		
	 		
	 	}
	 	
	 	if (!empty($order_status)){
	 		$andSql .= " and order_status = '$order_status' ";
	 	}
	 	
	 	
	 	$redisFCount = 0;
	 	$sql = "select  sync_shipped_status , count(1) as cc from od_order_v2 where sync_shipped_status in ('P','S' ,'F') and order_relation in ('normal', 'sm') and isshow ='Y'  $andSql  group by sync_shipped_status";
	 	
	 	//echo $sql;
	 	$list = \Yii::$app->subdb->createCommand($sql)->queryAll();
	 	
	 	$rt =  Helper_Array::toHashmap($list, 'sync_shipped_status' , 'cc');
	 	
	 	// 没有 F 的记录也需要设置 为0 防止 redis 数据错误
	 	if (isset ($rt['F']) == false){
	 		$rt['F'] = 0;
	 	}
	 	
	 	if (isset ($rt['P']) == false){
	 		$rt['P'] = 0;
	 	}
	 	
	 	if (isset ($rt['S']) == false){
	 		$rt['S'] = 0;
	 	}
	 	
	 	if (  $rt['F'] != $redisFCount && isset(DashBoardHelper::${'SIGNSHIPPED_ORDER_ERR_'.strtoupper($platform)})){
	 		DashBoardStatisticHelper::CounterSet($puid, DashBoardHelper::${'SIGNSHIPPED_ORDER_ERR_'.strtoupper($platform)},$rt['F']);
	 	}
	 	
	 	
	 	return $rt;
	 }
	 
	 /**
	  +---------------------------------------------------------------------------------------------
	  * 根据订单item 信息生成 rootsku
	  +---------------------------------------------------------------------------------------------
	  * @access static
	  +---------------------------------------------------------------------------------------------
	  * @param	array:	$orderItem					订单item model或者array
	  * 		boolean	$isDefault					找不到root sku 是否返回sku
	  +---------------------------------------------------------------------------------------------
	  * @return	string rootsku
	  +---------------------------------------------------------------------------------------------
	  * log			name	date					note
	  * @author		lkh		2016/09/09				初始化
	  +---------------------------------------------------------------------------------------------
	  **/
	 static public function getRootSKUByOrderItem($orderItem , $isDefault=true , $platform='' , $selleruserid='' ){
	 	try {
	 		//检查传递的参数 是否正确
	 		if ($orderItem instanceof OdOrderItem){
	 			
	 		}elseif(is_array($orderItem)){
	 			$orderItem = (Object) $orderItem;
	 		}else{
	 			return ['ack'=>false , 'message'=>'参数不正确!' , 'code'=>'40001' ,'data'=>'' ];
	 		}
	 		
	 		$sku =  empty($orderItem->sku)?$orderItem->product_name:$orderItem->sku;
	 		$rootSKU = \eagle\modules\catalog\apihelpers\ProductApiHelper::getRootSKUByAlias($sku,$platform,$selleruserid);
	 		//假如不存在root sku 则返回 订单的 sku 
	 		if (empty($rootSKU) && $isDefault) $rootSKU = $sku;
	 		return ['ack'=>true , 'message'=>'' , 'code'=>'2000' ,'data'=>$rootSKU ];
								
	 		
	 	} catch (\Exception $e) {
	 		return ['ack'=>false , 'message'=>(__FUNCTION__)." ".$e->getMessage()." line no ".$e->getLine(), 'code'=>'40002' ,'data'=>'' ];
	 	}
	 }//end of function getRootSKUByOrderItem
	 
	
	 
	 /**
	  +---------------------------------------------------------------------------------------------
	  * 获取订单利润信息
	  +---------------------------------------------------------------------------------------------
	  * @access static
	  +---------------------------------------------------------------------------------------------
	  * @param     $params			查询条件
	  * @param     $is_sum		   	是否合计信息，0否1是
	  +---------------------------------------------------------------------------------------------
	  * @return	array[
	  * 				'status' => status,
	  * 				'data' => ['paid_time', 'order_id', 'grand_total', 'commission_total', 'logistics_cost', 'logistics_weight', 'order_source_order_id', 'order_source', 'selleruserid', 'default_shipping_method_code' ],
	  *
	  +---------------------------------------------------------------------------------------------
	  * log			name	date					note
	  * @author		lkh		2016/09/01				初始化
	  +---------------------------------------------------------------------------------------------
	  **/
	 static public function getOrderStatisticsInfo($params, $is_sum)
	 {
	 	if(empty($params['per-page']))
	 		$params['per-page'] = 20;
	 	if(empty($params['page']))
	 		$params['page'] = 0;
	 	
	 	$date_name = 'order_source_create_time';
	 	if(!empty($params['date_type'])){
	 		if($params['date_type'] == 'ship_date'){
	 			$date_name = 'complete_ship_time';
	 		}
	 		unset($params['date_type']);
	 	}
	 
	 	$query = OdOrder::find()->select("$date_name as `order_source_create_time`, `order_id`, `addi_info`, `grand_total`, `commission_total`, `paypal_fee`, `logistics_cost`, `profit`,`logistics_weight`, `seller_weight`, `order_source_order_id`, `order_source`, `selleruserid`, `default_shipping_method_code`, `order_relation`,  FROM_UNIXTIME($date_name,'%Y%m%d') as `time`, `tracking_number`");
	 	
	 	//筛选有效订单
	 	$query = OrderGetDataHelper::formatQueryOrderConditionPurchase($query, true);
	 	foreach ($params as $key=>$value)
	 	{
	 		if($value=='' || $key == 'search_txt')
	 			continue;
	 		switch ($key)
	 		{
	 			case 'start_date':
	 				$query->andWhere("`$date_name`>=$value");//timestamp
	 				break;
	 			case 'end_date':
	 				$query->andWhere("`$date_name`<=$value");//timestamp
	 				break;
	 			case 'selectstore':
	 				$query->andWhere(['in','selleruserid',$value]);
	 				break;
				case 'search_type':
					if(!empty($params['search_txt'])){
						if($value == 'order_id'){
							$query->andWhere(['order_id' => $params['search_txt']]);
						}
						else if($value == 'order_source_id'){
							$query->andWhere(['order_source_order_id' => $params['search_txt']]);
						}
						else if($value == 'tracking_number'){
							$query->andWhere(['tracking_number' => $params['search_txt']]);
						}
					}
					break;
				case 'country':
					$query->andWhere(['consignee_country_code'=>$value]);
					break;
	 			case 'selectplatform':
	 				$query->andWhere(['in','order_source',$value]);
	 				break;
 				case 'order_type':
 					if(is_array($value)){
	 					if(!in_array('fba', $value)){
	 						$query->andWhere(['not in' , 'order_type' , ['AFN']]);
	 					}
	 					if(!in_array('fbc', $value)){
	 						$query->andWhere(['not in' , 'order_type' , ['FBC']]);
	 					}
 					}
 					break;
	 			case 'page':
	 				break;
	 			case 'per-page':
	 				break;
 				case 'date_type':
 					break;
	 			default:
	 				$query->andWhere([$key=>$value]);
	 				break;
	 		}
	 	}
	 	if(empty($params['order_type'])){
	 		$query->andWhere(['not in' , 'order_type' , ['AFN', 'FBC']]);
	 	}
	 	
	 	//已完成订单，并且利润不为空，有付款时间才计算
	 	$query->andWhere("profit is not null and order_status=500 and paid_time>0");
	 	//速卖通需要存在跟踪号才计算
	 	$query->andWhere("(order_source='aliexpress' and tracking_number is not null and tracking_number!='') or order_source!='aliexpress'");
	 	
	 	//只显示已绑定的账号的信息
	 	$bind_stores = '';
	 	$bind_order_souce = '';
	 	$uid = \Yii::$app->subdb->getCurrentPuid();
	 	$platformAccountInfo = PlatformAccountApi::getAllPlatformOrderSelleruseridLabelMap($uid);
	 	//$platformAccountInfo = PlatformAccountApi::getAllAuthorizePlatformOrderSelleruseridLabelMap(false, false, true);//引入平台账号权限后
	 	foreach ($platformAccountInfo as $p_key=>$p_v){
	 		if(!empty($p_v)){
	 			foreach ($p_v as $s_key=>$s_v){
	 				$bind_stores[] = $s_key;
	 			}
	 		}
	 		$bind_order_souce[] = $p_key;
	 	}
	 	
	 	if($bind_stores != ''){
	 		$query->andWhere(['in','selleruserid',$bind_stores]);
	 	}
	 	if($bind_order_souce != ''){
	 		$query->andWhere(['in','order_source',$bind_order_souce]);
	 	}
	 	 
	 
	 	$result = array();
	 	
	 	$result['data'] = [];
	 	//添加合计行
	 	if( $is_sum == 1)
	 	{
	 	    $statisticsAll = $query->limit(30000)->asArray()->all();
	 	    if(!empty($statisticsAll))
	 	    {
	 	        $exist_order_source_order_id =  [];  //已存在平台订单号
	 	        $sum = [];
	 	        $sum['order_source_create_time'] = '合计';
	 	        $sum['order_id'] = 0;
	 	        $sum['grand_total'] = 0;
	 	        $sum['commission_total'] = 0;
	 	        $sum['paypal_fee'] = 0;
	 	        $sum['actual_charge'] = 0;
	 	        $sum['logistics_cost'] = 0;
	 	        $sum['purchase_cost'] = 0;
	 	        $sum['profit'] = 0;
	 	        $sum['logistics_weight'] = 0;
	 	        $sum['order_source_order_id'] = 0;
	 	        $sum['order_source'] = '-';
	 	        $sum['selleruserid'] = '-';
	 	        $sum['tracking_number'] = '-';
	 	        $sum['service_name'] = '-';
	 	        foreach($statisticsAll as $key => $statistics)
	 	        {
	 	            $sum['order_id'] = $sum['order_id'] + 1;
	 	            //平台订单号数量
	 	            if( !empty($statistics['order_source_order_id']) && !in_array($statistics['order_source_order_id'], $exist_order_source_order_id))
	 	            {
	 	                //当是合并订单，则取回合并订单前的平台订单号集合
	 	                if($statistics['order_relation'] == 'sm')
	 	                {
	 	                    $count = 0;
	 	                	$items = OdOrderItem::find()->select('order_source_order_id')->where(['order_id'=>$statistics['order_id']])->asarray()->all();
	 	                	foreach ($items as $k => $item)
	 	                	{
	 	                		if(!empty($item['order_source_order_id']))
	 	                		{
	 	                			if(!in_array($item['order_source_order_id'], $exist_order_source_order_id))
	 	                			{
	 	                				$count= $count + 1;
	 	                			}
	 	                		}
	 	                	}
	 	                	if($count == 0)
	 	                	    $sum['order_source_order_id'] = $sum['order_source_order_id'] + 1;
	 	                	else 
	 	                	    $sum['order_source_order_id'] = $sum['order_source_order_id'] + $count;
	 	                }
	 	                else
	 	                    $sum['order_source_order_id'] = $sum['order_source_order_id'] + 1;
	 	                
	 	                $exist_order_source_order_id[] = $statistics['order_source_order_id'];
	 	            }
	 	            $sum['logistics_cost'] = sprintf('%.2f', $sum['logistics_cost'] + (empty($statistics['logistics_cost']) ? 0 : $statistics['logistics_cost']));
	 	            $sum['logistics_weight'] = $sum['logistics_weight'] + (empty($statistics['logistics_weight']) || (float)$statistics['logistics_weight'] == 0 ? (empty($statistics['seller_weight']) ? 0 : (float)$statistics['seller_weight']) : (float)$statistics['logistics_weight']);
	 	            $sum['profit'] = $sum['profit'] + (empty($statistics['profit']) ? 0 : $statistics['profit']);
	 	            
	 	            $addi_info = $statistics['addi_info'];
	 	            $addi_info = json_decode($addi_info,true);
	 	            if(empty($addi_info))
	 	            	$addi_info = [];
	 	            $sum['commission_total'] = sprintf('%.2f', $sum['commission_total'] + (empty($addi_info['commission_total']) ? 0 : $addi_info['commission_total']));
	 	            $sum['paypal_fee'] = sprintf('%.2f', $sum['paypal_fee'] + (empty($addi_info['paypal_fee']) ? 0 : $addi_info['paypal_fee']));
	 	            $sum['purchase_cost'] = sprintf('%.2f', $sum['purchase_cost'] + (empty($addi_info['purchase_cost']) ? 0 : $addi_info['purchase_cost']));
	 	            $sum['grand_total'] = sprintf('%.2f', $sum['grand_total'] + (empty($addi_info['grand_total']) ? 0 : $addi_info['grand_total']));
	 	            $sum['actual_charge'] = sprintf('%.2f', $sum['actual_charge'] + (empty($addi_info['actual_charge']) ? 0 : $addi_info['actual_charge']));
	 	            
	 	        }
	 	        
	 	        //成本利润率
	 	        if($sum['logistics_cost'] + $sum['purchase_cost'] > 0 && $sum['profit'] > 0)
	 	        	$sum['profit_per'] = round($sum['profit'] / ($sum['logistics_cost'] + $sum['purchase_cost']) * 100, 2) .'%' ;
	 	        else
	 	        	$sum['profit_per'] = '-';
	 	        
	 	        //销售利润率
	 	        if($sum['grand_total'] > 0 && $sum['profit'] > 0)
	 	        	$sum['sales_per'] = round($sum['profit'] / $sum['grand_total'] * 100, 2) .'%' ;
	 	        else
	 	        	$sum['sales_per'] = '-';
	 	        
	 	        $result['data'][] = $sum;
	 	    }
	 	}
	 
	 	//分页显示
	 	$pagination = new Pagination([
	 			'page'=> $params['page'],
	 			'pageSize' => $params['per-page'],
	 			'totalCount' => $query->count(),
	 			'pageSizeLimit'=>[20,200],//每页显示条数范围
	 			]);
	 	$result['pagination'] = $pagination;
	 	
	 	//排序
	 	$query->orderBy("time desc, order_id desc");
	 	
	 	//当条/页为-1时，则不需分页
	 	if($params['per-page'] == -1)
	 	{
    	 	$statisticsInfo = $query
    	 	->asArray()
    	 	->all();
	 	}
	 	else 
	 	{
	 	    $statisticsInfo = $query
	 	    ->limit($pagination->limit)
	 	    ->offset($pagination->offset)
	 	    ->asArray()
	 	    ->all();
	 	}
	 	
	 	if(!empty($statisticsInfo))
	 	{
	 		//所有运输服务
	 		$allshippingservices = CarrierApiHelper::getShippingServiceList(-1,-1);
	 		//绑定平台、店铺信息
	 		$stores = [];
	 		foreach ($platformAccountInfo as $p_key=>$p_v)
	 		{
	 		    foreach ($p_v as $s_key=>$s_v)
	 		    {
	 		    	$stores[strtolower($p_key).'_'.strtolower($s_key)] = $s_v;
	 		    }
	 		}
	 		
	 		//整理信息
	 		$order_id_arr = array();
	 		foreach($statisticsInfo as $key => $statistics)
	 		{
	 		    $statistics['order_source_create_time'] = empty($statistics['order_source_create_time']) ? '' : date("Y-m-d",$statistics['order_source_create_time']);
	 			$statistics['order_id'] = preg_replace('/^0+/','', $statistics['order_id']);
	 			$statistics['service_name'] = empty($allshippingservices[$statistics['default_shipping_method_code']]) ? '' : $allshippingservices[$statistics['default_shipping_method_code']];
	 			$statistics['logistics_weight'] = empty($statistics['logistics_weight']) || (float)$statistics['logistics_weight'] == 0 ? empty($statistics['seller_weight']) ? '0' : (float)$statistics['seller_weight'] : (float)$statistics['logistics_weight'];
	 			$statistics['logistics_cost'] = empty($statistics['logistics_cost']) ? '0.00' : $statistics['logistics_cost'];
	 			$statistics['profit'] = empty($statistics['profit']) ? '0.00' : $statistics['profit'];
	 			$statistics['selleruserid'] = empty($stores[strtolower($statistics['order_source']).'_'.strtolower($statistics['selleruserid'])]) ? '' : $stores[strtolower($statistics['order_source']).'_'.strtolower($statistics['selleruserid'])];
	 			
	 			$addi_info = $statistics['addi_info'];
	 			$addi_info = json_decode($addi_info,true);
	 			if(empty($addi_info))
	 				$addi_info = [];
	 			$statistics['purchase_cost'] = empty($addi_info['purchase_cost']) ? '0.00' : sprintf('%.2f', $addi_info['purchase_cost']);
	 			$statistics['grand_total'] = empty($addi_info['grand_total']) ? '0.00' : sprintf('%.2f', $addi_info['grand_total']);
	 			$statistics['commission_total'] = empty($addi_info['commission_total']) ? '0.00' : sprintf('%.2f', $addi_info['commission_total']);
	 			$statistics['paypal_fee'] = empty($addi_info['paypal_fee']) ? '0.00' : sprintf('%.2f', $addi_info['paypal_fee']);
	 			$statistics['actual_charge'] = empty($addi_info['actual_charge']) ? '0.00' : sprintf('%.2f', $addi_info['actual_charge']);
	 			$statistics['tracking_number'] = empty($statistics['tracking_number']) ? '' : $statistics['tracking_number'];
	 			
	 			//成本利润率
	 			if($statistics['logistics_cost'] + $statistics['purchase_cost'] > 0 && $statistics['profit'] > 0)
	 			    $statistics['profit_per'] = round($statistics['profit'] / ($statistics['logistics_cost'] + $statistics['purchase_cost']) * 100, 2) .'%' ;
	 			else 
	 			    $statistics['profit_per'] = '-';
	 			
	 			//销售利润率
	 			if($statistics['grand_total'] > 0 && $statistics['profit'] > 0)
	 				$statistics['sales_per'] = round($statistics['profit'] / $statistics['grand_total'] * 100, 2) .'%' ;
	 			else
	 				$statistics['sales_per'] = '-';
	 			
	 			//当是合并订单，则取回合并订单前的平台订单号集合
	 			if($statistics['order_relation'] == 'sm')
	 			{
	 			    $exist_order = [];
	 			    $items = OdOrderItem::find()->select('order_source_order_id')->where(['order_id'=>$statistics['order_id']])->asarray()->all();
	 			    
	 			    $exist_order[] = $statistics['order_source_order_id'];
	 			    $order_source_order_id = $statistics['order_source_order_id'] .',';
	 			    foreach ($items as $k => $item)
	 			    {
	 			        if(!empty($item['order_source_order_id']))
	 			        {
    	 			        if(!in_array($item['order_source_order_id'], $exist_order))
    	 			        {
    	 			            $order_source_order_id .= $item['order_source_order_id'] .',';
    	 			            $exist_order[] = $item['order_source_order_id'];
    	 			        }
	 			        }
	 			    }
	 			    if(!empty($order_source_order_id))
	 			        $statistics['order_source_order_id'] = rtrim($order_source_order_id, ',');
	 			}
	 			$order_id_arr[] = $statistics['order_id'];
	 
	 			$result['data'][$statistics['order_id']] = $statistics;
	 		}
	 		unset($statisticsInfo);
	 		
	 		//查询平台APIF返回的物流号
	 		$shipped = OdOrderShipped::find()->select(['order_id', 'tracking_number'])->where(['status' => 1, 'addtype' => '平台API', 'order_id' => $order_id_arr])->asArray()->all();
	 		foreach($shipped as $val){
	 			if(array_key_exists($val['order_id'], $result['data'])){
	 				$tracking_number = $result['data'][$val['order_id']]['tracking_number'];
	 				if(strpos($tracking_number, $val['tracking_number']) !== 0){
	 					if(!empty($tracking_number)){
	 						$result['data'][$val['order_id']]['tracking_number'] = $tracking_number.'<br>'.$val['tracking_number'];
	 					}
	 					else{
	 						$result['data'][$val['order_id']]['tracking_number'] = $val['tracking_number'];
	 					}
	 				}
	 			}
	 		}
	 		$result['data'] = array_values($result['data']);
	 		
	 		$result['status'] = 1;
	 	}
	 	else
	 	{
	 		$result['status'] = 0;
	 		$result['data'] = array();
	 	}
	 	return $result;
	 
	 }//end of function  getOrderStatisticsInfo
	 
	 /**
	  +---------------------------------------------------------------------------------------------
	  * 获取订单
	  +---------------------------------------------------------------------------------------------
	  * @access static
	  +---------------------------------------------------------------------------------------------
	  * @param     $platform			平台账号
	  * @param     $order_id		   	小老板订单号
	  * @param     $itemid			   	商品id
	  * @param     $orderSourceOrderID	平台订单号
	  +---------------------------------------------------------------------------------------------
	  * @return	array[
	  * 				'status' => status,
	  * 				'data' => ['paid_time', 'order_id', 'grand_total', 'commission_total', 'logistics_cost', 'logistics_weight', 'order_source_order_id', 'order_source', 'selleruserid', 'default_shipping_method_code' ],
	  *
	  +---------------------------------------------------------------------------------------------
	  * log			name	date					note
	  * @author		lkh		2016/09/01				初始化
	  +---------------------------------------------------------------------------------------------
	  **/
	 static public function getOrderItemModelByItemID($platform , $order_id , $itemid, $orderSourceOrderID=''){
	 	$itemModel =[];
	 	if (in_array($platform,['lazada','linio','jumia'])){
	 		$itemModel = OdOrderItem::findOne(['order_id'=>$order_id, 'order_source_order_item_id'=>$itemid]);
	 	}elseif(in_array($platform,['priceminister'])){
	 		$param = ['order_id'=>$order_id, 'source_item_id'=>$itemid ];
	 		if (!empty($orderSourceOrderID)){
	 			$param['order_source_order_id'] = $orderSourceOrderID;
	 		}
	 		$itemModel = OdOrderItem::findOne($param);
	 	}
	 	else{
	 		$param = ['order_id'=>$order_id, 'order_source_itemid'=>$itemid];
	 		if (!empty($orderSourceOrderID)){
	 			$param['order_source_order_id'] = $orderSourceOrderID;
	 		}
	 		$itemModel = OdOrderItem::findOne($param);
	 	}
	 	
	 	return $itemModel;
	 }
	 
	 
	 /**
	  +---------------------------------------------------------------------------------------------
	  * 获取 订单item 的平台 itemid
	  +---------------------------------------------------------------------------------------------
	  * @access static
	  +---------------------------------------------------------------------------------------------
	  * @param
	  * 								$platform						订单来源平台
	  * 								$item							order item model
	  +---------------------------------------------------------------------------------------------
	  * @return						array
	  * 									string								平台 的item
	  * @invoking					OrderGetDataHelper::getOrderItemSouceItemID('amazon' , $item);
	  +---------------------------------------------------------------------------------------------
	  * log			name	date					note
	  * @author		lkh		2017/03/06				初始化
	  +---------------------------------------------------------------------------------------------
	  **/
	 static public function getOrderItemSouceItemID($platform ,  $item){
	 	if (in_array($platform,['lazada','linio','jumia'])){
	 		return $item->order_source_order_item_id;
	 	}elseif(in_array($platform,['priceminister'])){
	 		//pm order_source_order_item_id 不是唯一
	 		return $item->order_source_order_item_id.$item->order_source_order_id;
	 	}else{
	 		return $item->order_source_itemid;
	 	}
	 }//end of function getOrderItemSouceItemID
	 
	 
	 /**
	  +---------------------------------------------------------------------------------------------
	  * 根据sku 获取关联sku 订单item 信息
	  +---------------------------------------------------------------------------------------------
	  * @access static
	  +---------------------------------------------------------------------------------------------
	  * @param
	  * 								$sku						sku
	  * 								$type						all配对  unbind解绑
	  * 								$originRootSKU
	  +---------------------------------------------------------------------------------------------
	  * @return						array
	  * 																	平台 的item array
	  * @invoking					OrderGetDataHelper::getPayOrderItemBySKU('123','');
	  +---------------------------------------------------------------------------------------------
	  * log			name	date					note
	  * @author		lkh		2017/03/10				初始化
	  +---------------------------------------------------------------------------------------------
	  **/
	 static public function getPayOrderItemBySKU($sku ,$type='all', $originRootSKU=''){
	 	$query = OdOrderItem::find()
	 	->leftJoin('od_order_v2','od_order_item_v2.order_id = od_order_v2.order_id')
	 	->where(['sku'=>$sku ]);
	 	
	 	if ($type !='all'){
	 		$query->andWhere(['root_sku'=>$originRootSKU]);
	 	}
	 	
	 	//echo  $query->andwhere("od_order_v2.order_status='".OdOrder::STATUS_PAY."'")->createCommand()->getRawSql();
	 	
	 	$items = $query->andwhere("od_order_v2.order_status='".OdOrder::STATUS_PAY."'")
	 	->all();
	 	
	 	return $items;
	 }
	 
	 
	 /**
	  +---------------------------------------------------------------------------------------------
	  * 查询当前 未完成订单root sku 的使用情况 
	  +---------------------------------------------------------------------------------------------
	  * @access static
	  +---------------------------------------------------------------------------------------------
	  * @param
	  * 								$sku						root sku 
	  +---------------------------------------------------------------------------------------------
	  * @return						int
	  * 																	未完成订单root sku使用的订单数量
	  * @invoking					OrderGetDataHelper::getOrderCountByRootSKU('123');
	  +---------------------------------------------------------------------------------------------
	  * log			name	date					note
	  * @author		lkh		2017/03/28				初始化
	  +---------------------------------------------------------------------------------------------
	  **/
	 static public function getOrderCountByRootSKU($sku){
	 	$items = OdOrderItem::find()
	 	->leftJoin('od_order_v2','od_order_item_v2.order_id = od_order_v2.order_id')
	 	->where(['root_sku'=>$sku])
	 	->andwhere("(od_order_v2.order_status >='".OdOrder::STATUS_PAY."' and od_order_v2.order_status <'".OdOrder::STATUS_SHIPPED."') or od_order_v2.order_status='".OdOrder::STATUS_SUSPEND."' or od_order_v2.order_status='".OdOrder::STATUS_OUTOFSTOCK."' ")
	 	->andwhere("order_relation in ('normal' , 'sm', 'ss', 'fs')")
	 	->count();
	 	
	 	/*
	 	echo OdOrderItem::find()
	 	->leftJoin('od_order_v2','od_order_item_v2.order_id = od_order_v2.order_id')
	 	->where(['root_sku'=>$sku])
	 	->andwhere("od_order_v2.order_status >='".OdOrder::STATUS_PAY."' and od_order_v2.order_status <'".OdOrder::STATUS_SHIPPED."' ")
	 	->createCommand()->getRawSql();
	 	*/
	 	 
	 	return $items;
	 }//end of function getOrderCountByRootSKU
	 
	 
	 
}