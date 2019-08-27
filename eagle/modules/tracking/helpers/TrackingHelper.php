<?php
/**
 * @link http://www.witsion.com/
 * @copyright Copyright (c) 2014 Yii Software LLC
 * @license http://www.witsion.com/
 */
namespace eagle\modules\tracking\helpers;

use yii;
use yii\data\Pagination;
use eagle\modules\util\helpers\GoogleHelper;
use eagle\modules\tracking\models\Tracking;
use eagle\modules\util\models\GlobalLog;
use eagle\modules\util\helpers\SQLHelper;
use eagle\modules\util\helpers\RedisHelper;
use eagle\modules\util\helpers\HttpHelper;
use eagle\modules\tracking\helpers\CarrierTypeOfTrackNumber;
use eagle\modules\util\helpers\UserLastActionTimeHelper;
use eagle\modules\tracking\models\TrackerApiQueue;
use eagle\modules\tracking\models\TrackerApiSubQueue;
use eagle\modules\tracking\models\TrackerGenerateRequest2queue;
use eagle\modules\util\helpers\StandardConst;
use eagle\modules\util\helpers\TranslateHelper;
use eagle\modules\util\helpers\ExcelHelper;
use eagle\modules\util\helpers\ConfigHelper;
use eagle\modules\util\helpers\ResultHelper;
use yii\base\Exception;
use eagle\modules\util\helpers\OperationLogHelper;
use eagle\modules\platform\apihelpers\PlatformAccountApi;
use yii\caching\DbDependency;
use eagle\models\SaasAliexpressUser;
use eagle\modules\carrier\apihelpers\CarrierApiHelper;
use eagle\models\SaasCdiscountUser;
use eagle\models\SaasLazadaUser;
use eagle\models\SaasDhgateUser;
use eagle\modules\platform\apihelpers\EbayAccountsApiHelper;
use eagle\modules\message\helpers\MessageHelper;
use eagle\modules\util\helpers\TimeUtil;
use eagle\models\SaasWishUser;
use eagle\modules\order\helpers\OrderTrackerApiHelper;
use eagle\modules\message\models\MsgTemplate;
use eagle\modules\message\helpers\TrackingMsgHelper;
use eagle\modules\tracking\models\Tag;
use eagle\modules\order\models\OdOrder;
use eagle\modules\util\helpers\SysLogHelper;
use eagle\modules\order\apihelpers\OrderApiHelper;
use eagle\modules\app\apihelpers\AppApiHelper;
use yii\helpers\Url;
use eagle\modules\dash_board\helpers\DashBoardHelper;
use common\helpers\Helper_Array;
use eagle\models\SaasAmazonUser;
use eagle\modules\permission\helpers\UserHelper;
use eagle\models\SaasEbayUser;
use eagle\modules\amazoncs\helpers\AmazoncsHelper; 

/**
 * BaseHelper is the base class of module BaseHelpers.
 *
 */
class TrackingHelper {
//状态
	public static $TRACKER_FILE_LOG = false;
	const CONST_1= 1; //Sample
	private static $Insert_Api_Queue_Buffer = array();
	private static $mainQueueVersion = '';	
	
	private static $subQueueVersion = '';
	private static $putIntoTrackQueueVersion = '';
	
	public static $vip_tracker_excel_import_limit = [
		'1150'=>5000,
		'3110'=>5000,
	];
	
	public static $tracker_import_limit = 50;
	public static $tracker_guest_import_limit = 10;
	
	private static $EXCEL_COLUMN_MAPPING = [
	"A" => "ship_by",//快递公司
	"B" => "ship_out_date",//快递单日期 
	"C" => "track_no",//物流号
	"D" => "order_id",//订单号
	"E" => "delivery_fee",//运费 
	];
	
	public static $EXPORT_EXCEL_FIELD_LABEL = [
	"ship_by" => "物流商",
	"ship_out_date" => "录入日期",
	"track_no" => "物流号",
	"order_id" => "订单号",
	"delivery_fee" => "运费",
	"status"=>"包裹状态",
	'total_days'=>"在途天数",
	'to_nation'=>'目的地国家',
	'last_event_date'=>"妥投时间",
	'last_event'=>'最新事件',
	'tags'=>'标签',
	'remark'=>'备注',
	];
	
	/*Data Conversion:
	 * For those tracking number comes from OMS, and without ship out date, try to ask OMS and 
	 * complete the ship out date.
	 * OBSOLLETE
	 * */
	static public function fixOMSTrackingShipOutDate(){
		$rtn['data'] = array();
		$rtn['success'] = true;
		$puid = \Yii::$app->subdb->getCurrentPuid();
		$rtn['puid'] = $puid;
		$aTrackings = Tracking::find()
			->andWhere("source = 'O' and state<>'complete' and  (ship_out_date is null or ship_out_date='') " )
			->all();
		
		 foreach ($aTrackings as $aTracking){
		//step 3.0，如果有order id 的，尝试判断他的 order date 是否4个月以前，如果是的，不要查了，浪费资源		
			if (empty($aTracking->ship_out_date) ){
				//如果发出时间是 空，我们尝试从oms获取他的 order date，然后作为ship out date 的判断依据
				$getOmsOrder = self::getOrderDetailFromOMSByTrackNo($aTracking->track_no);
				if ($getOmsOrder['success']){
					$order_time = !empty($getOmsOrder['order']['paid_time'])?$getOmsOrder['order']['paid_time']:(!empty($getOmsOrder['order']['1428548196'])?$getOmsOrder['order']['1428548196']:"");
					if ($order_time <> ""){
						$order_time = date('Y-m-d H:i:s',$order_time);
						$aTracking->ship_out_date = $order_time;
						$aTracking->save();
						$rtn['data'][]=$aTracking->track_no."-".$order_time;
					}
				}
			}//end of when ship out date is empty
		}//end of each
		return $rtn;
	}
	
	/*
	 +---------------------------------------------------------------------------------------------
	 * 获取Tracking的用户行为记录
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param     $puid				puid, default 0 = all
	 * @param     $keyword			模糊查询的text
	 * @param     action type       string of user selected,default "" = get all
     * @param     $date_from		指定创建时间从xxx开始
	 * @param     $date_to          指定创建时间到xxx为止
	 * @param     $sort             指定排序field
	 * @param     $order            排序顺序
	 * @param     $pageSize         每页显示数量，默认是40
	 * @param     $pageNo         每页显示数量，默认是1
	 * @param     $params           =array ('')
	 * @return    array( success=>true/false
               			data => array of rows
		 	   			action_types =>array('Excel导入','手工录入','可能无聊的攻击')
		 	   			puids => array('1'=>199.'2'=>50) //id=>total_records 
		 				total_rows=> 500		 		
	       				)
	*/
	static public function getUserActionTrackList($puid=0,$keyword='',$action_type='',$date_from='',$date_to='', $sort='' , $order='' , $pageSize = 40,$pageNo=1,$params=array() )
	{	$rtn['data'] = array();
		$rtn['success'] = true;
		$action_type_array = ['Excel导入','手工录入','查询记录','绑定账号'];
		$rtn['success'] = true;
		$rtn['action_types'] = $action_type_array;
		$query = GlobalLog::find();
		if(empty($sort)){
			$sort = 'create_time';
			$order = 'desc';
		}
	
		$condition = " remark like '%用户%' and remark  not like '%backdoor%' "; //这个不包含对puid 的约束
		$condition_puid =' and 1 ';
		
		//如果puid = 0，就是for 所有的客户，如果不是0，那么就是for特定某个客户而已
		if ($puid <> 0){
			$condition_puid .= " and (remark like '%用户 $puid %'  )";
		}
		
		//如果keyword不为空，用户录入了模糊查询
		if(!empty($keyword)){
			//去掉keyword的引号。免除SQL注入
			$keyword = str_replace("'","",$keyword);
			$keyword = str_replace('"',"",$keyword);
			$condition .= " and (remark like '%$keyword%'  )";
		}
		
		//如果from日期或者to日期有，添加进去filter
		if(!empty($date_from)){
			//去掉keyword的引号。免除SQL注入
			$date_from = str_replace("'","",$date_from);
			$date_from = str_replace('"',"",$date_from);
			$condition .= " and ( date(create_time) >='$date_from' )";
		}
		if(!empty($date_to)){
			//去掉keyword的引号。免除SQL注入
			$date_to = str_replace("'","",$date_to);
			$date_to = str_replace('"',"",$date_to);
			$condition .= " and ( date(create_time)<= '$date_to' )";
		}
		
		//如果state不为空，用户录入了模糊查询
		foreach ($params as $fieldName=>$val){
			if(!empty($val)){
				//去掉keyword的引号。免除SQL注入
				$val = str_replace("'","",$val);
				$val = str_replace('"',"",$val);
				$val_array = explode(",",$val);
				$condi_internal =" and ( 0 ";
				
				foreach ($val_array as $aVal){
					$condi_internal .= " or $fieldName='$aVal'";
				}
				
				$condi_internal .= ")";
				
				$condition .= $condi_internal;
			}
		}//end of each filter

		//action_type: ['Excel导入','手工录入','查询记录','绑定账号']
		if (!empty($action_type)){
			if ($action_type == 'Excel导入'){
				$condition .= " and remark like '%by excel%'";
			}
			if ($action_type == '手工录入'){
				$condition .= " and remark like '%录入查询%'";
			}
			if ($action_type == '查询记录'){
				$condition .= " and remark like '%查看全部物流记录%'";
			}
			if ($action_type == '绑定账号'){
				$condition .= " and remark like '%添加绑定%'";
			}
		}
		
		$rtn['condition'] = $condition.$condition_puid;
		$rtn['total_rows'] = $query->andWhere($condition.$condition_puid)->count();
		$rtn['data'] = $query
			->andWhere($condition.$condition_puid)
			->offset(($pageNo - 1 )*$pageSize) 
			->limit( $pageSize)
			->orderBy(" $sort $order")
			->asArray()
			->all();

		foreach ($rtn['data'] as &$row){
			$row['remark'] =  str_ireplace('物流号来源是,', '', $row['remark']);
		}
		
		//try to work out how many puid for this condition and each puid has how many records
		$command = Yii::$app->db->createCommand("select substring(remark,9,LOCATE(' ', remark,9) -9 ) as puid,count(*) as record_count  from ut_global_log where  $condition  group by substring(remark,9,LOCATE(' ', remark,9) -9 )") ;
		$puids_arr = $command->queryAll();
		
		$rtn['puids'] = array();
		foreach ($puids_arr as $aRow){
			$rtn['puids'][$aRow['puid']] =  $aRow['record_count'];
		}	 
		 return $rtn;
	}
	/**
	 +---------------------------------------------------------------------------------------------
	 * 获取Tracking的Listing
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param     $keyword			模糊查询的text
	 * @param     $params           需要指定刷选的fields以及值，field name要和字段名一样，值可以多个可能的，逗号隔开
	 *                              例如 array( state=>'initial,normal',
	 *                                         status=>'shipping',
	 *                                         source=>'O,M',
	 *                                         platform =>... ,
	 *                                         batch_no =>
	 *                                         hasComment =>'Y'/'N'
	 *                                         mark_handled =>'Y'/'N',
	 *                                         deleted =>'Y'/'N',
	 *                                       )				
	 * @param     $date_from		指定创建时间从xxx开始
	 * @param     $date_to          指定创建时间到xxx为止	 
	 * @param     $sort             指定排序field
	 * @param     $order            排序顺序
	 * @param     $pageSize         每页显示数量，默认是40
	 +---------------------------------------------------------------------------------------------
	 * @return						$data
	 *
	 * @invoking					TrackingHelper::getListDataByCondition();
	 *
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		yzq		2015/2/9				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function getListDataByCondition($keyword='',$params=array(),$date_from='',$date_to='', $sort='' , $order='' , $pageSize = 50,$noPagination = false  )
	{	 
		$query = Tracking::find();
		$date_filter_field_name = 'ship_out_date';
		
		//kh20160218 start 导出选中的物流号
		if (isset($params['export_track_no_list'])){
			//存在export_track_no_list 则表示是导出选中的物流号的相关信息excel 
			$export_track_no_list = $params['export_track_no_list'];
			unset($params['export_track_no_list']);// 排除新功能 对原有logic的影响 
		}
		//kh20160218 end   导出选中的物流号
		if (isset($params['export_track_id_list'])){
			//存在export_track_no_list 则表示是导出选中的物流号的相关信息excel
			$export_track_id_list = $params['export_track_id_list'];
			unset($params['export_track_id_list']);// 排除新功能 对原有logic的影响
		}
		
		
		// $params pos是区别客服提示的
		if (!empty($params['pos'])){
			//到达待取通知 = RPF ,  异常退回通知 = RRJ ,  投递失败=DE, 已签收请求好评 = RGE
			//上述的三种情况使用 last_event_date
			if (in_array($params['pos'], ['RPF' , 'RGE','DF','RRJ'])){
				$date_filter_field_name = 'last_event_date';
			}
			
			unset ($params['pos']);
			//加入一个搜索限制,避免事件只有一条或者只有备货中事件 情况
			$query->andWhere(" `first_event_date` IS NOT NULL  and (`first_event_date` <> `last_event_date`) ");
		}else if (@$params['status'] == 'no_info,checking'){
			//kh20160119 客户要求 查询到5天没有未上网的需求 ， ship_out_date 不能满足要求 ， 改为录入时间 起来查询
			$date_filter_field_name = 'create_time';
		}
		//Pagination 会自动获取Post或者get里面的page number，自动计算offset
		$pagination = new Pagination([
				'totalCount'=> $query->count(),
				'defaultPageSize'=> 50,
				'pageSize'=> $pageSize,
				'pageSizeLimit'=>  [5,  ( $noPagination ? 50000 : 200 )  ],
				]);
		
		$data['pagination'] = $pagination;
		
		if(empty($sort)){
			$sort = $date_filter_field_name.' desc , create_time desc';
			$order = '';
		}
	
		$condition=' 1 ';
		//如果keyword不为空，用户录入了模糊查询
		if(!empty($keyword)){
			//去掉keyword的引号。免除SQL注入
			$keyword = str_replace("'","",$keyword);
			$keyword = str_replace('"',"",$keyword);
			$condition .= " and (order_id like '$keyword' or track_no like '$keyword' )";
		}
		
		//如果from日期或者to日期有，添加进去filter
		if(!empty($date_from)){
			//去掉keyword的引号。免除SQL注入
			$date_from = str_replace("'","",$date_from);
			$date_from = str_replace('"',"",$date_from);
			$condition .= " and ( $date_filter_field_name >='$date_from' )";
		}
		if(!empty($date_to)){
			//去掉keyword的引号。免除SQL注入
			$date_to = str_replace("'","",$date_to);
			$date_to = str_replace('"',"",$date_to);
			$condition .= " and ( $date_filter_field_name<= '$date_to' )";
		}
		
		//如果state不为空，用户录入了模糊查询
		$bindVals = array();
		
		if(isset($params['page'])) unset($params['page']);
		if(isset($params['pre-page'])) unset($params['pre-page']);
		
		foreach ($params as $fieldName=>$val){
			if(!empty($val)){
			 	
				if($fieldName == 'is_send'){
					//去掉keyword的引号。免除SQL注入
					$val = str_replace("'","",$val);  $val = str_replace('"',"",$val);
					$condi_internal = " and ( ( status in ( 'received' ,  'platform_confirmed') and received_notified='$val') ".
							" or (status = 'arrived_pending_fetch' and pending_fetch_notified='$val') ".
							" or (status = 'delivery_failed' and delivery_failed_notified='$val') ".
							" or (status = 'rejected' and rejected_notified='$val') ".
							" or (status = 'shipping' and shipping_notified='$val') )";
					
				}elseif($fieldName == 'tagid'){
					//去掉keyword的引号。免除SQL注入
					$val = str_replace("'","",$val);  $val = str_replace('"',"",$val);
					$condi_internal = " and id in ( select tracking_id from lt_tracking_tags where tag_id = '$val')";
				}elseif ($fieldName == 'hasComment'){
					//去掉keyword的引号。免除SQL注入
					$val = str_replace("'","",$val);  $val = str_replace('"',"",$val);
					if ($val == "Y")
						$condi_internal  = " and remark <> '' and remark is not null ";

					if ($val == "N")
						$condi_internal  = " and (remark = '' or remark is null )";	
				}elseif($fieldName == 'deleted'){
					if ($val == "N")
						$condi_internal  = " and state <> 'deleted'";
					if ($val == "Y")
						$condi_internal  = " and state ='deleted'";
				}
				//停留时间 区间搜索	2017-10-31	lzhl
				elseif($fieldName=='stay_days'){
					if(!is_array($val)){
						$condi_internal = " and stay_days=$val ";
					}else{
						$condi_internal = '';
						foreach ($val as $vv){
							if(isset($vv['operator']) && isset($vv['days'])){
								$condi_internal .= " and stay_days".$vv['operator'].$vv['days']." ";
							}
						}
					}
				}//中运输天数	 区间搜索	2017-12-01	lzhl
				elseif($fieldName=='total_days'){
					if(!is_array($val)){
						$condi_internal = " and total_days=$val ";
					}else{
						$condi_internal = '';
						foreach ($val as $vv){
							if(isset($vv['operator']) && isset($vv['days'])){
								$condi_internal .= " and total_days".$vv['operator'].$vv['days']." ";
							}
						}
					}
				}else{
					//不是指定备注有与否
					$val_array = explode(",",$val);
					$condi_internal =" and ( 0 ";
				    
					$i = 10000;
					foreach ($val_array as $aVal){
						if (in_array($aVal,[TranslateHelper::t('无物流商')])){
							$aVal = '';
						}
						$i++;
						$condi_internal .= (" or $fieldName=:". $fieldName.$i);
						$bindVals[":".$fieldName.$i] = $aVal;
					}
				
					$condi_internal .= ")";
				}
				
				$condition .= $condi_internal;
			}
		}//end of each filter
		
		$current_time=explode(" ",microtime()); $time1=round($current_time[0]*1000+$current_time[1]*1000);
		
		if (!empty($export_track_no_list)){
			//kh20160218 start   导出选中的物流号
			$data ['condition'] = $export_track_no_list;
			$query->andWhere(['track_no'=>$export_track_no_list])
				->orderBy(" $sort $order  , id $order ");
			$uid = \Yii::$app->user->id;
			//if($uid==7394){
			//	$commandQuery = clone $query;
			//	echo $commandQuery->createCommand()->getRawSql();
			//}
			$data['data'] = $query->asArray()
				->all();
			//kh20160218 end   导出选中的物流号
		}elseif(!empty($export_track_id_list)){
			$data ['condition'] = $export_track_id_list;
			$query->andWhere(['id'=>$export_track_id_list])
			->orderBy(" $sort $order  , id $order ");
			$uid = \Yii::$app->user->id;
			//if($uid==7394){
			//	$commandQuery = clone $query;
			//	echo $commandQuery->createCommand()->getRawSql();
			//}
			$data['data'] = $query->asArray()
			->all();
		}else{
			$data ['condition'] = $condition;
			$query->andWhere($condition,$bindVals)
				->offset($pagination->offset)
				->limit($pagination->limit)
				->orderBy(" $sort $order  , id $order ");
			$uid = \Yii::$app->user->id;
			//if($uid==7394){
			//	$commandQuery = clone $query;
			//	echo $commandQuery->createCommand()->getRawSql();
			//}
			$data['data'] = $query	->asArray()
				->all();
		}
		
		$current_time=explode(" ",microtime()); $time2=round($current_time[0]*1000+$current_time[1]*1000);
		
		// 调试sql    
	 //ysperformance
		 $tmpCommand = $query->createCommand();
// 		echo "<br>Used Query time ".($time2 - $time1)." ms<br>".$tmpCommand->getRawSql();
		$finalSql = $tmpCommand->getRawSql();
	 	
		//如果没有任何data，并且search keyword，日期选项都是空白，返回sample data
		//do not use sample data anyway
		/*
		if ( count($data['data']) == 0 and empty($keyword) and empty($date_from) and empty($date_to)){
			$existingCount = Tracking::find()->count();
			if ($existingCount == 0)
				$data = Tracking::getTrackingSampleData();
		}
		*/
		
		//如果没有任何结果并且keyword是有填写的，尝试ignore 其他条件，再试试看获取该track no only
		if ( count($data['data']) == 0 and !empty($keyword) ){
			$query = Tracking::find();
			$data['data'] = $query
				->where("order_id =:kw1 or track_no=:kw2",array(":kw1"=>$keyword,":kw2"=>$keyword))
				->offset($pagination->offset)
				->limit($pagination->limit)
				->orderBy(" $sort $order , id $order ")
				->asArray()
				->all();
		}
		
		foreach ($data['data'] as $key => $val) {
			$data['data'][$key]['status'] =Tracking::getChineseStatus($val['status']);
			$data['data'][$key]['state'] =Tracking::getChineseState($val['state']);
		}
		$pagination->totalCount = $query->count();
		//\Yii::info(['Tracking',__CLASS__,__FUNCTION__,'Online',"ListTracking ".print_r($params,true) . " SQL:$condition , order by  $sort $order "],"edb\global");
		//SysLogHelper::SysLog_Create('Tracking',__CLASS__, __FUNCTION__,'info',"ListTracking final SQL:$finalSql , order by  $sort $order ");
		//\Yii::info(['Tracking',__CLASS__,__FUNCTION__,'Online',"Got data ".print_r($data,true) ],"edb\global");
		return $data;
	}
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 不使用分页 获取所有 符合条件 的 Tracking Listing 
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param     $keyword				模糊查询的text
	 * @param     $params           	需要指定刷选的fields以及值，field name要和字段名一样，值可以多个可能的，逗号隔开
	 *                              	例如 array( state=>'initial,normal',
	 *                                         status=>'shipping',
	 *                                         source=>'O,M',
	 *                                         platform =>... ,
	 *                                         batch_no =>
	 *                                       )
	 * @param     $date_from			指定创建时间从xxx开始
	 * @param     $date_to         		指定创建时间到xxx为止
	 * @param     $sort             	指定排序field
	 * @param     $order            	排序顺序
	 * @param     $field_label_list     指定需要的字段 (导出excel 专用 )
	 * @param	  $maxCount				最多获取数据 总数 目前最多 5W条	
	 * @param	  $thispageLimit		每一次获取 条数(循环获取数据 到上限)
	 +---------------------------------------------------------------------------------------------
	 * @return						$data
	 *
	 * @invoking					TrackingHelper::getListDataByConditionNoPagination();
	 *
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2015/6/19				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function getListDataByConditionNoPagination($keyword='',$params=array(), $date_from='',$date_to='', $sort='' , $order='' , $field_label_list=[] , $maxCount = 50000 , $thispageLimit = 3000)
	{	
		$noPagination = true;
		$sumGetCount = 1;
		// 导出 excel 的 header
		$data [] = $field_label_list;
		$TrackingData = self::getListDataByCondition($keyword ,$params ,$date_from ,$date_to , $sort  , $order, $thispageLimit, $noPagination );
		
		//需要 field  
		if (!empty($field_label_list)){
			$totalCount = $TrackingData['pagination']->totalCount;
			//假如数据 总数多于 上限 , 只拿到上限就停止 , 防止 内存溢出
			if ($maxCount < $totalCount) $totalCount = $maxCount;
			
			//计算 循环的次数
			if ($totalCount > $thispageLimit ){
				$sumGetCount = ceil($totalCount / $thispageLimit);
			}
			//将model 获取的数据 进行处理 , 释放内存(model 格式 的数据  占用内存大 , 越早释放越安全)
			self::_convertTrackerDataToActiveData($TrackingData, $data, $field_label_list);
		}else{
			return $TrackingData;
		}
		
		//获取剩余的数据
		for ($i = 1; $i < $sumGetCount; $i++) {
			//性能调试log
			/*
			if (isset(\Yii::$app->params["currentEnv"]) and \Yii::$app->params["currentEnv"]=="test" ){
				$logTimeMS1=TimeUtil::getCurrentTimestampMS();
				$logMemoryMS1 = (memory_get_usage()/1024/1024);
				echo __FUNCTION__.' step get all '.$i.'.1  :'.(memory_get_usage()/1024/1024). 'M<br>'; //test kh
			}
			*/
			//切换分页
			$_GET['page'] =  $i+1;
			//获取当前 页的数据
			$TrackingData = self::getListDataByCondition($keyword ,$params ,$date_from ,$date_to , $sort  , $order, $thispageLimit, $noPagination );
			if (!empty($TrackingData)){
				self::_convertTrackerDataToActiveData($TrackingData, $data, $field_label_list);
			}
			
			//性能调试log
			/*
			if (isset(\Yii::$app->params["currentEnv"]) and \Yii::$app->params["currentEnv"]=="test" ){
				$logTimeMS2=TimeUtil::getCurrentTimestampMS();
				$logMemoryMS2 = (memory_get_usage()/1024/1024);
					
				echo __FUNCTION__.' step get all  '.$i.'.2  T=('.($logTimeMS2-$logTimeMS1).') and M='.($logMemoryMS2-$logMemoryMS1).'M and Now Memory='.(memory_get_usage()/1024/1024). 'M and total '.count($data).'<br>'; //test kh
				\Yii::info("get lt_tracking data  total=".count($data).",t2_1=".($logTimeMS2-$logTimeMS1).
						",memory=".($logMemoryMS2-$logMemoryMS1)."M ","file");
				
			}
			*/
		}
		return $data;
	}//end of getListDataByConditionNoPagination
	
	static public function getListDataByConditionByPagination($keyword='',$params=array(), $date_from='',$date_to='', $sort='' , $order='' , $field_label_list=[])
	{
		$noPagination = false;
		$thispageLimit = empty($_REQUEST['per-page'])?50:(int)$_REQUEST['per-page'];
		// 导出 excel 的 header
		$data [] = $field_label_list;
		$TrackingData = self::getListDataByCondition($keyword ,$params ,$date_from ,$date_to , $sort  , $order, $thispageLimit, $noPagination );
	
		//需要 field
		if (!empty($field_label_list)){
			//将model 获取的数据 进行处理 , 释放内存(model 格式 的数据  占用内存大 , 越早释放越安全)
			self::_convertTrackerDataToActiveData($TrackingData, $data, $field_label_list);
		}else{
			return $TrackingData;
		}
		return $data;
	}
	
	static private function _convertTrackerDataToActiveData(&$TrackingData , &$data ,&$field_label_list){
		foreach($TrackingData['data'] as &$oneTracking):
			
			//$EXPORT_EXCEL_FIELD_LABEL 为需要导出的field  , array_flip后得出需要导出的field name
			foreach(array_flip($field_label_list) as $field_name){
				if ($field_name == 'last_event_date'){
					//妥投时间只有   '成功签收'  才显示
					if (in_array($oneTracking['status'],['received', '成功签收' ]))
						$row['last_event_date'] = $oneTracking['last_event_date'];
					else{
						$row['last_event_date'] = '';
						//continue;
					}
					continue;
				}
				//国家名称转换
				if (in_array($field_name, ['to_nation', 'from_nation'])){
					if (!empty($oneTracking[$field_name])){
						$row[$field_name] = self::autoSetCountriesNameMapping($oneTracking[$field_name]);
					}else{
						$row[$field_name] = $oneTracking[$field_name];
					}
					continue;
				}
				
				if ($field_name == 'total_days'){
					//在途天数，只有total_days 》 0 才显示 并且 状态为买家已确认则不显示这个 在途天数
					if ($oneTracking['total_days'] <= 0  || in_array($oneTracking['status'],['platform_confirmed',  '买家已确认' ]) )
						$row['total_days'] = "";
					else{
						$row[$field_name] = $oneTracking[$field_name];
					}
					
					continue;
				}
				//最新事件 liang 2016-01-20
				if($field_name == 'last_event'){
					$row['last_event']='';
					if(!empty($oneTracking['all_event'])){
						$all_event = json_decode($oneTracking['all_event'],true);
						if(!empty($all_event)){
							$last_event = $all_event[0];
							$last_event_when = empty($last_event['when'])?'':$last_event['when'];
							$last_event_where = empty($last_event['where'])?'':base64_decode($last_event['where']);
							$last_event_what = empty($last_event['what'])?'':base64_decode($last_event['what']);
							$row['last_event']=$last_event_when.'  '.(empty($last_event_where)?'':$last_event_where.' - ').$last_event_what;
						}
					}	
					continue;
				}
				//备注 liang 2016-01-21
				if($field_name == 'remark'){
					$row['remark']='';
					if(!empty($oneTracking['remark'])){
						$remarks = json_decode($oneTracking['remark']);
						if(!empty($remarks)){
							foreach ($remarks as $r){
								$row['remark'].= (empty($r->who)?'未知':$r->who).'于'.(empty($r->when)?' 年 月  日':$r->when).'添加了备注：'.(empty($r->what)?'':$r->what).';';
							}
						}
					}
					continue;
				}
				//tags liang 2016-01-21
				if($field_name == 'tags'){
					$row['tags']='N/A';
					$tag_data = TrackingTagHelper::getTrackingTagsByTrackId($oneTracking['id']);
					$tag_ids = [];
					foreach ($tag_data as $tracking_tag){
						$tag_ids[] = $tracking_tag['tag_id'];
					}
					$TagList = Tag::find()->where(['tag_id'=>$tag_ids])->asArray()->all();
					foreach ($TagList as $tag){
						if(empty($row['tags']) || $row['tags']=='N/A')
							$row['tags'] = $tag['tag_name'];
						else 
							$row['tags'] = $row['tags'].','.$tag['tag_name'];
					}
					continue;
				}
					
				//循环所有需要导出的field 并将值 放入临时变量 row 中
				$row[$field_name] = $oneTracking[$field_name];
				//test if it is numeric, add " " in front of it, in case excel 科学计数法
				if (is_numeric($row[$field_name]) and strlen($row[$field_name]) > 8 )
					$row[$field_name] = ' '.$row[$field_name];
					
			}
			
			// 获取一个tracking 完成后放进  $data_array 中
			$data [] = $row;
			// 释放内存
			$oneTracking=[];
			unset($oneTracking);
			$row = [];
		endforeach;
		unset($TrackingData);
	}//end of _convertTrackerDataToActiveData
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 获取某个Tracking的All Events，执行输出的语言，自动提供翻译
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param   tracking number     物流号
	 * @param   lang				输出翻译后的语言，zh-CN,zh-TW,zh-TW,en,fr 等
	 *                              "" 表示不翻译
	 +---------------------------------------------------------------------------------------------
	 * @return						array('success'=true,'message'='',allEvents=>array() )
	 *
	 * @invoking					TrackingHelper::getTrackingAllEvents($track_no, $lang ='');
	 *
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		yzq		2015/2/9				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function getTrackingAllEvents($track_no, $lang =''){
		$rtn['message']="";
		$rtn['success'] = false;
		$rtn['allEvents'] = array();
		$now_str = date('Y-m-d H:i:s');

		if (empty($track_no) ){
			$rtn['message']="没有有效Tracking No输入";
			$rtn['success'] = false;
			return $rtn;
		}
				
		$model = Tracking::find()->andWhere("track_no=:track_no",array(":track_no"=>$track_no))->one();
			
		//step 1: when not found such record, skip it
		if ($model == null){
			$rtn['message']="找不到该Tracking No的记录：$track_no";
			$rtn['success'] = false;
			return $rtn;
		}
		
		//step 2: 获取all events，并且进行翻译
		$allEvents = json_decode($model->all_event , true);
		if (empty($allEvents)) $allEvents = array();
		//$rtn['original'] = $allEvents;
		 
		//do the transalte
		if ($lang <> ''){
			$translated_Events = array();
			foreach ($allEvents as $anEvent){
				$anEvent['what'] = base64_decode($anEvent['what']);
				$anEvent['where'] = base64_decode($anEvent['where']);
				if ($anEvent['lang'] <>'' and substr( strtolower($anEvent['lang']),0,3) <>'zh-'){	
					$anEvent['where'] = GoogleHelper::google_translate(strtolower($anEvent['where']),$anEvent['lang'],$lang);
					//如果全部是大写，有问题，把他改为小写
					if (strtoupper( $anEvent['what'])  === $anEvent['what'])
						$anEvent['what'] = str_replace("post","Post",strtolower( $anEvent['what']));
					$anEvent['what'] = GoogleHelper::google_translate( $anEvent['what'] ,$anEvent['lang'],$lang);
				}
				$translated_Events[] = $anEvent;
			}
			$allEvents = $translated_Events;
		}//end of need translate
		
		$rtn['allEvents'] = $allEvents;
		return $rtn;
	}
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 标记Tracking已经被处理，标识一下,通常用于对异常状态的进行标记，
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param   tracking_no_array   array of tracking no, e.g.: array('RGdfsdfsdf','RG342342','RG34234234') 
	 +---------------------------------------------------------------------------------------------
	 * @return						array('success'=true,'message'='')
	 *
	 * @invoking					TrackingHelper::markTrackingHandled($tracking_no_array);
	 *
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		yzq		2015/2/9				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function markTrackingHandled($tracking_no_array){
		$rtn['message']="";
		$rtn['success'] = false;
		$now_str = date('Y-m-d H:i:s');
	
		//对录入参数里面的每个track no进行update
		foreach ($tracking_no_array as $track_no){
			if (!isset($track_no) or trim($track_no)==''){
		 		continue;
			}
			
			$model = Tracking::find()->andWhere("id=:id",array(":id"=>$track_no))->one();
			
			//step 1: when not found such record, skip it
			if ($model == null){
				continue;
			}
			
			//step 2 : when neither exception  nor uhshipped  , skip it
			if (!in_array(strtolower($model->state) ,['exception' , 'unshipped'] )){
				continue;
			}
			
			//step 3 : when mark_handled already equal to Y , skip it 
			if (strtoupper($model->mark_handled) == 'Y'){
				continue;
			}
			 
			//step 4, mark the flag as handled
			$model->update_time = $now_str;
			$model->mark_handled = "Y";
			
			//step 5: save the data to Tracking table
			if ( $model->save() ){//save successfull
				$rtn['success']=true;
				$rtn['message'] = TranslateHelper::t("修改成功!请手动刷新一下更新数据!") ;
			}else{
				$rtn['success']=false;
				$rtn['message'] .= TranslateHelper::t("ETRK014 保存Tracking数据失败") ;
				foreach ($model->errors as $k => $anError){
					$rtn['message'] .= "ETRK002". ($rtn['message']==""?"":"<br>"). $k.":".$anError[0];
				}
			}//end of save failed	
		}//end of each track number
		return $rtn;
	}//end of mark tracking handled
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 标记Tracking已经完成
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param   tracking_no_array   array of tracking no, e.g.: array('RGdfsdfsdf','RG342342','RG34234234') 
	 +---------------------------------------------------------------------------------------------
	 * @return						array('success'=true,'message'='')
	 *
	 * @invoking					TrackingHelper::markTrackingCompleted($tracking_no_array);
	 *
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2016/7/14				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function markTrackingCompleted($tracking_no_array){
		$rtn['message']="";
		$rtn['success'] = false;
		$now_str = date('Y-m-d H:i:s');
		$puid = \Yii::$app->subdb->getCurrentPuid();
		$userName = \Yii::$app->user->identity->getFullName();
		//对录入参数里面的每个track no进行update
		foreach ($tracking_no_array as $track_no){
			if (!isset($track_no) or trim($track_no)==''){
		 		continue;
			}
			
			$model = Tracking::find()->andWhere("id=:id",array(":id"=>$track_no))->one();
			
			//step 1: when not found such record, skip it
			if ($model == null){
				continue;
			}
			
			//step 2 : when state eaqual to complete , skip it
			if (in_array(strtolower($model->state) ,['complete' ] )){
				continue;
			}
			 
			//step 3, mark the flag as handled
			$model->update_time = $now_str;
			$old_status = $model->status;
			$model->state = "complete";
			$model->status ='received'; // platform_confirmed,received
			
			//记录手工移动状态log
			$addi_info = $model->addi_info;
			$addi_info = json_decode($addi_info,true);
			if(empty($addi_info)) $addi_info = [];
			$addi_info['manual_status_move_logs'][] = [
				'capture_user_name'=>$userName,
				'old_status'=>$old_status,
				'new_status'=>'complete',
				'time'=>$now_str,
			];
			$model->addi_info = json_encode($addi_info);
			
			//step 4: save the data to Tracking table
			if ( $model->save() ){//save successfull
				$rtn['success']=true;
				$rtn['message'] = TranslateHelper::t("修改成功!请手动刷新一下更新数据!") ;
				//push oms 
				self::pushToOMS($puid, $model->order_id,$model->status,$model->last_event_date);
			}else{
				$rtn['success']=false;
				$rtn['message'] .= TranslateHelper::t("ETRK014 保存Tracking数据失败") ;
				foreach ($model->errors as $k => $anError){
					$rtn['message'] .= "ETRK002". ($rtn['message']==""?"":"<br>"). $k.":".$anError[0];
				}
			}//end of save failed	
		}//end of each track number
		return $rtn;
	}//end of function markTrackingCompleted
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 标记Tracking 运输途中
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param   tracking_no_array   array of tracking no, e.g.: array('RGdfsdfsdf','RG342342','RG34234234') 
	 +---------------------------------------------------------------------------------------------
	 * @return						array('success'=true,'message'='')
	 *
	 * @invoking					TrackingHelper::markTrackingCompleted($tracking_no_array);
	 *
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2016/7/14				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function markTrackingShipping($tracking_no_array){
		$rtn['message']="";
		$rtn['success'] = false;
		$now_str = date('Y-m-d H:i:s');
		$puid = \Yii::$app->subdb->getCurrentPuid();
		$userName = \Yii::$app->user->identity->getFullName();
		//对录入参数里面的每个track no进行update
		foreach ($tracking_no_array as $track_no){
			if (!isset($track_no) or trim($track_no)==''){
		 		continue;
			}
			
			$model = Tracking::find()->andWhere("id=:id",array(":id"=>$track_no))->one();
			
			//step 1: when not found such record, skip it
			if ($model == null){
				continue;
			}
			 
			//step 2, mark the flag as handled
			$model->update_time = $now_str;
			$old_status = $model->status;
			$model->state = "normal";
			$model->status ='shipping'; // shipping
			
			//记录手工移动状态log
			$addi_info = $model->addi_info;
			$addi_info = json_decode($addi_info,true);
			if(empty($addi_info)) $addi_info = [];
			$addi_info['manual_status_move_logs'][] = [
				'capture_user_name'=>$userName,
				'old_status'=>$old_status,
				'new_status'=>'shipping',
				'time'=>$now_str,
			];
			$model->addi_info = json_encode($addi_info);
			
			//step 3: save the data to Tracking table
			if ( $model->save() ){//save successfull
				$rtn['success']=true;
				$rtn['message'] = TranslateHelper::t("修改成功!请手动刷新一下更新数据!") ;
				//push oms 
				self::pushToOMS($puid, $model->order_id,$model->status,$model->last_event_date);
			}else{
				$rtn['success']=false;
				$rtn['message'] .= TranslateHelper::t("ETRK014 保存Tracking数据失败") ;
				foreach ($model->errors as $k => $anError){
					$rtn['message'] .= "ETRK002". ($rtn['message']==""?"":"<br>"). $k.":".$anError[0];
				}
			}//end of save failed	
		}//end of each track number
		return $rtn;
	}//end of function markTrackingShipping
		
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 标记Track No 为忽略查询
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param   tracking_no_array   array of tracking no, e.g.: array('RGdfsdfsdf','RG342342','RG34234234')
	 +---------------------------------------------------------------------------------------------
	 * @return						array('success'=true,'message'='')
	 +---------------------------------------------------------------------------------------------
	 * log			name		date					note
	 * @author		lzhl		2016/11/07				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function markTrackingIgnore($tracking_no_array){
		$rtn['message']="";
		$rtn['success'] = false;
		$now_str = date('Y-m-d H:i:s');
		$puid = \Yii::$app->subdb->getCurrentPuid();
		$userName = \Yii::$app->user->identity->getFullName();
		//对录入参数里面的每个track no进行update
		foreach ($tracking_no_array as $track_no){
			if (!isset($track_no) or trim($track_no)==''){
				continue;
			}
			
			$model = Tracking::find()->andWhere("id=:id",array(":id"=>$track_no))->one();
			
			//step 1: when not found such record, skip it
			if ($model == null){
				continue;
			}
	
			//step 2, mark the flag as handled
			$model->update_time = $now_str;
			$old_status = $model->status;
			$model->state = "normal";
			$model->status ='ignored';
				
			//记录手工移动状态log
			$addi_info = $model->addi_info;
			$addi_info = json_decode($addi_info,true);
			if(empty($addi_info)) $addi_info = [];
			$addi_info['manual_status_move_logs'][] = [
			'capture_user_name'=>$userName,
			'old_status'=>$old_status,
			'new_status'=>'ignored',
			'time'=>$now_str,
			];
			$model->addi_info = json_encode($addi_info);
				
			//step 3: save the data to Tracking table
			if ( $model->save() ){//save successfull
				$rtn['success']=true;
				$rtn['message'] = TranslateHelper::t("修改成功!请手动刷新一下更新数据!") ;
				//push oms
				self::pushToOMS($puid, $model->order_id,$model->status,$model->last_event_date);
			}else{
				$rtn['success']=false;
				$rtn['message'] .= TranslateHelper::t("ETRK014 保存Tracking数据失败") ;
				foreach ($model->errors as $k => $anError){
					$rtn['message'] .= "ETRK002". ($rtn['message']==""?"":"<br>"). $k.":".$anError[0];
				}
			}//end of save failed
		}//end of each track number
		return $rtn;
	}//end of function markTrackingIgnore
	

	/**
	 +---------------------------------------------------------------------------------------------
	 * 将已忽略的运单重新设置为为查询
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param   tracking_no_array   array of tracking id
	 +---------------------------------------------------------------------------------------------
	 * @return						array('success'=true,'message'='')
	 +---------------------------------------------------------------------------------------------
	 * log			name		date					note
	 * @author		lzhl		2017/09/28				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function ignoredTrackingReSearch($tracking_no_array){
		$rtn['message']="";
		$rtn['success'] = false;
		$now_str = date('Y-m-d H:i:s');
		$puid = \Yii::$app->subdb->getCurrentPuid();
		$userName = \Yii::$app->user->identity->getFullName();
		$shipByArr = [];//包含的物流商
		//对录入参数里面的每个track no进行update
		foreach ($tracking_no_array as $track_no){
			if (!isset($track_no) or trim($track_no)==''){
				continue;
			}
				
			$model = Tracking::find()->andWhere("id=:id",array(":id"=>$track_no))->one();
				
			//step 1: when not found such record, skip it
			if ($model == null){
				continue;
			}
			if(!empty($model->ship_by) && !in_array($model->ship_by,$shipByArr))
				$shipByArr[] = $model->ship_by;
			
			//step 2, mark the flag as handled
			$model->update_time = $now_str;
			$old_status = $model->status;
			$model->state = "initial";
			$model->status ='checking';
	
			//记录手工移动状态log
			$addi_info = $model->addi_info;
			$addi_info = json_decode($addi_info,true);
			if(empty($addi_info)) $addi_info = [];
			$addi_info['manual_status_move_logs'][] = [
			'capture_user_name'=>$userName,
			'old_status'=>$old_status,
			'new_status'=>'checking',
			'time'=>$now_str,
			];
			$model->addi_info = json_encode($addi_info);
	
			//step 3: save the data to Tracking table
			if ( $model->save() ){//save successfull
				$rtn['success']=true;
				$rtn['message'] = TranslateHelper::t("修改成功!请手动刷新一下更新数据!") ;
				$generateOneRequest = self::generateOneRequestForTracking($model,true);
				if(!empty($generateOneRequest['message']) && empty($generateOneRequest['success'])){
					$rtn['message'] .= $generateOneRequest['message'];
					$rtn['success'] = false;
				}
				else 
					TrackingHelper::postTrackingApiQueueBufferToDb();
				//push oms
				self::pushToOMS($puid, $model->order_id,$model->status,$model->last_event_date);
			}else{
				$rtn['success']=false;
				$rtn['message'] .= TranslateHelper::t("ETRK014 保存Tracking数据失败") ;
				foreach ($model->errors as $k => $anError){
					$rtn['message'] .= "ETRK002". ($rtn['message']==""?"":"<br>"). $k.":".$anError[0];
				}
			}//end of save failed
		}//end of each track number

		//reActive ship_by
		try{
			$ignroedSetting = self::getUserIgnoredCheckCarriers($puid);
			if($ignroedSetting['success']){
				$settedShipBy = empty($ignroedSetting['data'])?[]:$ignroedSetting['data'];
				$newIgnr = [];
				foreach ($settedShipBy as $setted){
					if(!in_array($setted,$shipByArr))
						$newIgnr[] = $setted;
				}
				$key = 'userIgnoredCheckCarriers';
				RedisHelper::RedisSet('Tracker_AppTempData', 'uid_'.$puid.'_'.$key,json_encode($newIgnr));
				ConfigHelper::setConfig('IgnoreToCheck_ShipType', json_encode($newIgnr));
			}
		}catch (Exception $e) {
			$rtn['message'] .= '重新开启物流商的自动查询失败';
		}
		return $rtn;
	}//end of function markTrackingIgnore
	
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 标记Track No 为已发送通知
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @params   tracking_no_array	array of tracking no, e.g.: array('RGdfsdfsdf','RG342342','RG34234234')
	 * @params   $status			'shipping' or 'arrived_pending_fetch' or 'rejected' or 'received'
	 +---------------------------------------------------------------------------------------------
	 * @return						array('success'=true,'message'='')
	 +---------------------------------------------------------------------------------------------
	 * log			name		date					note
	 * @author		lzhl		2016/11/17				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function markTrackingIsSent($tracking_no_array,$status){
		$rtn['message']="";
		$rtn['success'] = false;
		$now_str = date('Y-m-d H:i:s');
		$puid = \Yii::$app->subdb->getCurrentPuid();
		$userName = \Yii::$app->user->identity->getFullName();
		//对录入参数里面的每个track no进行update
		foreach ($tracking_no_array as $track_no){
			if (!isset($track_no) or trim($track_no)==''){
				continue;
			}
				
			$model = Tracking::find()->andWhere("id=:id",array(":id"=>$track_no))->one();
				
			//step 1: when not found such record, skip it
			if ($model == null){
				continue;
			}
	
			//step 2, mark the notified as handled (Y)
			$NSM = TrackingHelper::getNotifiedFieldNameByStatus($status);
			$model->$NSM = 'Y';
	
			//记录手工移动状态log
			$addi_info = $model->addi_info;
			$addi_info = json_decode($addi_info,true);
			if(empty($addi_info)) $addi_info = [];
			$addi_info['manual_notified_mark_sent']= [
				'capture_user_name'=>$userName,
				'time'=>$now_str,
			];
			$model->addi_info = json_encode($addi_info);
	
			//step 3: save the data to Tracking table
			if ( $model->save() ){//save successfull
				$rtn['success']=true;
				$rtn['message'] = TranslateHelper::t("修改成功!请手动刷新一下更新数据!") ;
				//push oms
				if(!empty($model->order_id))
					OdOrder::updateAll([$NSM=>'Y'], ['order_source_order_id'=>$model->order_id,'order_source'=>$model->platform]);
			}else{
				$rtn['success']=false;
				$rtn['message'] .= TranslateHelper::t("ETRK014 保存Tracking数据失败") ;
				foreach ($model->errors as $k => $anError){
					$rtn['message'] .= "ETRK002". ($rtn['message']==""?"":"<br>"). $k.":".$anError[0];
				}
			}//end of save failed
		}//end of each track number
		return $rtn;
	}//end of function markTrackingIgnore
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 用户前端录入或者OMS给到信息后，创建tracking，
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param     $data
	 * @param     $source             为录入来源，M=手工录入，E=Excel上传，O 为OMS 过来的
	 +---------------------------------------------------------------------------------------------
	 * @return						array('success'=true,'message'='')
	 *
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		yzq		2015/2/9				初始化
	 +---------------------------------------------------------------------------------------------
	 **/	
	static public function addTracking($data,$source='',$total=0){
		global $CACHE;
		$puid  = \Yii::$app->subdb->getCurrentPuid();
		$rtn['message']="";
		$rtn['success'] = false;
		$now_str = date('Y-m-d H:i:s');
		
		if (empty($data['track_no'])){
			$rtn['message']= TranslateHelper::t("ETRK009 没有正确的 物流号 填入，请查证" );
			$rtn['success'] = false;
			return $rtn;
		}
		
		$data['track_no'] = trim(str_ireplace(array("\r\n", "\r", "\n"), '', $data['track_no']));
		if (! empty($data['order_id']))
		$data['order_id'] = trim(str_ireplace(array("\r\n", "\r", "\n"), '', $data['order_id']));
		//step 1, check if there is valid tracking number post, if not, return with error
		if (!isset($data['track_no']) or trim($data['track_no'])==''){
			$rtn['message']= TranslateHelper::t("ETRK001 没有正确的 物流号 填入，请查证");
			$rtn['success'] = false;
			return $rtn;
		}
		
		//step 2, try to load this track no record
		$model = Tracking::find()->andWhere("track_no=:track_no",
						array(":track_no"=>$data['track_no']  ) )->one();
		$isCreate= false;
		if ($model == null){
			 
			//重复使用一个Model装载，设置数据
			$isCreate = true;
			if (Tracking::$A_New_Record == null)
				Tracking::$A_New_Record = new Tracking();
			
			$model = Tracking::$A_New_Record;
			$model->create_time = $now_str;
			//设置默认状态是 查询中
			if (empty($CACHE['Tracking']['Status']["查询等候中"])){
				$CACHE['Tracking']['Status']["查询等候中"] = Tracking::getSysStatus("查询等候中");
				$CACHE['Tracking']['State']["初始"] = Tracking::getSysState("初始");
			}
			$model->status= $CACHE['Tracking']['Status']["查询等候中"] ;
			$model->state = $CACHE['Tracking']['State']["初始"];
			
			//check quotao sufficient?
			/*
			$used_count = TrackingHelper::getTrackerUsedQuota($puid);
			$max_import_limit = TrackingHelper::getTrackerQuota($puid);
			if ($max_import_limit <= $used_count){
				$model->status= 'quota_insufficient' ;
				$model->state = 'exception';
			}else{//扣除
				TrackingHelper::addTrackerQuotaToRedis("trackerImportLimit_".$CACHE['TrackerSuffix'.$puid] ,   1);
				
			}
			*/
			//判断是不是这种运输方式是不是要忽略的
			
			//默认的创建来源，如果是空白的话，给M 作为Manual录入的
			if ($source == '')
				$source = "M";
		}//end of not existing such record, create it
		else{//for existing record, do not override the batch no field
			//we want to overwrite batch no, 2015-3-18 
			//unset($data['batch_no']);
		}
		
		if (empty($model->source) or $model->source <> 'O') //OMS 的就不要被改掉了
			$model->source = $source;
		
		//step 3, put the data into model
		//因为excel导入，可能有些字段没有给到有效值，这种情况，就不要用输入的东西覆盖原来的值了
		//怕用excel覆盖了订单号，物流商等重要OMS过来的信息，或者之前录入已经有的信息
		foreach ($data as $key=>$val){
			if (empty($val))
				unset($data[$key]);
		}
		$model->setAttributes($data);
		
		//\Yii::info(['Tracking',__CLASS__,__FUNCTION__,'Online',"set tracking data ".print_r($data,true)],"edb\global");
		
		$model->update_time = $now_str;
		
		//如果本来 ship out date 有指定了，例如OMS来的，那么就不需要用 now
		if (empty($model->ship_out_date) or $model->ship_out_date >  date('Y-m-d') )
			$model->ship_out_date = date('Y-m-d');
			
		//step 4: save the data to Tracking table
		if ($isCreate){ //when new a record, user buffer and post, not one by one
			$tempData = $model->getAttributes();
			$orderid = $tempData['order_id']; 
			$orderid = str_replace("'","",$orderid);
			$orderid = str_replace('"',"",$orderid);
			$tempData['order_id'] = $orderid;
			
			$track_no = $tempData['track_no'];
			$track_no = str_replace("'","",$track_no);
			$track_no = str_replace('"',"",$track_no);
			$tempData['track_no'] = $track_no;
			//防止插入多个 unique key 的记录
			Tracking::$Insert_Data_Buffer["$orderid - $track_no"] = $tempData;
			$rtn['success']=true;
		}else {
			//ystest starts
			//check whether there is such orderid - trackno combination in data aleady, otherwise, updating would violate the unique index
			$model_already = Tracking::find()->andWhere("track_no=:track_no and order_id=:orderid",
					array(":track_no"=>$data['track_no'], ":orderid"=>$model->order_id) )->one();
			if (!empty($model_already) and $model_already->id <> $model->id ){
				 
				$model->delete();
			} else {
				//ystest ends
				
			if ( $model->save(false) ){//save successfull
				$rtn['success']=true;
			}else{
				$rtn['success']=false;
				$rtn['message'] .= TranslateHelper::t("ETRK014 保存Tracking数据失败") ;
				foreach ($model->errors as $k => $anError){
					$rtn['message'] .= "ETRK002". ($rtn['message']==""?"":"<br>"). $k.":".$anError[0];
				}
			}//end of save failed
			}//ystest
		}//end of when upadte a record
		return $rtn;
	}//end of addTracking
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 这是Tracking的Monitor，这个job会每3个小时被cronjob 启动一次，
	 * 然后对每一个puid进行查看他的Tracking是否有需要生成Tracking 追踪请求。
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param 
	 +---------------------------------------------------------------------------------------------
	 * @return						array('success'=true,'message'='')
	 *
	 * @invoking					TrackingHelper::generateTrackingRequest();
	 *
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		yzq		2015/2/9				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function generateTrackingRequest($target_puid=0){
		$rtn['message'] = "";
		$rtn['success'] = true;
		$now_str = date('Y-m-d H:i:s');
		$todayDate = date('Y-m-d');
		$yesterday = date('Y-m-d',strtotime('-1 day'));
		$days4ago = date('Y-m-d',strtotime('-4 day'));
		$days2ago = date('Y-m-d',strtotime('-2 day'));
		$days180ago = date('Y-m-d',strtotime('-180 day'));
		$daytime90ago = date('Y-m-d H:i:s',strtotime('-90 day'));
		
		$message = "Cronb Job Started generateTrackingRequest";
		\Yii::info(['Tracking',__CLASS__,__FUNCTION__,'Online',$message],"edb\global");

		$csld_report = ConfigHelper::getGlobalConfig("Tracking/csld_format_distribute_$yesterday" ,'NO_CACHE');
		
		if ( empty($csld_report) )
			$first_run_for_today = true;
		else
			$first_run_for_today = false;
		
		//step 1, get all puid from managedb
		//step 1.1, get all puid having activity during last 30 days
		$connection = Yii::$app->db;
		/*$command = $connection->createCommand(
				"SELECT distinct puid FROM `user_last_activity_time` WHERE `last_activity_time` >='". date('Y-m-d',strtotime('-30 days')) ."'"
						) ;
		$rows = $command->queryAll();
		*/
		$puids_live_recent_30days = UserLastActionTimeHelper::getPuidArrByInterval(30*24);
		$puids_live_recent_5days1 = UserLastActionTimeHelper::getPuidArrByInterval(5*24);
		$puids_live_recent_5days = [];
		foreach ($puids_live_recent_5days1 as $puid)
			$puids_live_recent_5days[strval($puid)] =$puid;
			
		//step 1.2, 获取5日内没有登录过
		
		//step 1.5，找找5个小时内的，因为另外一个for new account 的job 每三分钟一次，会吧3小时内绑定的优先做了，
		//本job不要重复做相同的，排除5个小时内绑定的账号
		//step 1, get all puid having new account during last 12 hours
		$puids_platforms = PlatformAccountApi::getLastestBindingPuidTimeMap(5);
		$puidsCreated5Hours = array();
		foreach ($puids_platforms as $platform=>$ids){
			foreach ($ids as $id=>$create_time){
				$puidsCreated5Hours[$id] = $create_time;
			}
		}
		
		//step 2, for each puid, call to request for each active tracking
		foreach ($puids_live_recent_30days as $puid){
			//$puid = $row['puid'] ;	
			 
			
			//如果这个客户5天内没有活动过，那么只在每天的第一次才做他的 oms copy，其余时间不做他。每天一次
			if (!$first_run_for_today and !isset($puids_live_recent_5days[strval($puid)]))
				continue;
			 
			
			//step 2.0, check whether this database exists user_x
  			  //有可能他不绑定账号，就手工录入使用，所以这个步骤不能省去的
		/*	$sql = "select count(1) from `INFORMATION_SCHEMA`.`TABLES` where table_name ='lt_tracking' and TABLE_SCHEMA='user_$puid'";
			$command = $connection->createCommand($sql);
			$puidDbCount = $command->queryScalar();
			if ( $puidDbCount <= 0 ){
				continue;
			}
	 	*/

			//Step 2.1 Todo: get OMS shipped/completed orders into our tracking
		 
			if (!isset($puidsCreated5Hours[$puid])){
				//echo "try to get oms for $puid /";
				do {//每次获取不多于300条记录，防止服务器死掉，如果获取得到的是300条，可能还有的，继续获取一次看还有没有
					$rtn = self::copyTrackingFromOmsShippedComplete( $puid );
					echo " Copy OMS shipped Order for puid $puid , got records count=".$rtn['count'];
				}while($rtn['count'] > 290);
			}
			 
		 
			//echo "Step 2.4 .";
			//Step 2.15, 当在一天新的日子首次运行，也就是 凌晨 00：00 运行，做上一天的统计
			if ( $first_run_for_today ){
				echo "try to gen report for $puid /";
				$reports[$puid] = self::generateResultReportForPuid($yesterday,$puid);//,'RecommendProd'
				
				
				//step 2.16 找到该用户的tracker记录所有的 platform sellerid 组合，分别计算昨天的总数
				$platform_seller_ids = Yii::$app->subdb->createCommand(//run_time = -10, 也就是估计可以通过smt api 搞定的，所以早点做掉
						"select distinct platform,seller_id from lt_tracking   ")->queryAll();
				foreach ($platform_seller_ids as $aPlatformSellerid)
					self::summaryForThisAccountForLastNdays('Tracker',$aPlatformSellerid['platform'],$aPlatformSellerid['seller_id']);
			
				//Tracker data 6 months 180 days only
				$command = Yii::$app->subdb->createCommand("delete FROM `lt_tracking` where create_time <'$days180ago'  " );
				$command->execute();
			} 
			//echo "Step 2.5 .";
			//Step 2.2 Generate api request for all tracking
		 
			if (!isset($puidsCreated5Hours[$puid])){
				//write a request is enough, other jobs will do that
				$command = Yii::$app->db_queue->createCommand("replace into `tracker_gen_request_for_puid` (
								`puid` ,`create_time` ,	`status`) VALUES ('$puid',  '$now_str',  'P' ) " );
				$command->execute();
				 
			}  
			//echo "Step 2.6 .";
		}//end of each puid
		
		//"Step 2.7 . Generate api request for all tracking ,for each puid";
		//Step final, 做一个总的report
		if ( $first_run_for_today ){
			//echo "Try to gen CONSOLIDATED report HEHE";
			$message = "Try to gen CONSOLIDATED report for $yesterday";
			echo $message;
			\Yii::info(['Tracking',__CLASS__,__FUNCTION__,'Online',$message],"edb\global");
			self::generateConsolidatedReport($yesterday,$reports);
			//Housekeeping 顺便，每日一次
			$command = Yii::$app->db_queue->createCommand("delete FROM `tracker_api_queue` where create_time <'$days2ago'" );
			$command->execute();
			$command = Yii::$app->db_queue->createCommand("delete FROM `tracker_api_sub_queue` where create_time <'$days2ago'" );
			$command->execute();
			
			$command = Yii::$app->db->createCommand("delete FROM `ut_global_log` where create_time <'$days2ago'" );
			$command->execute();
			//journal 
			$command = Yii::$app->db_queue->createCommand("delete FROM `ut_sys_invoke_jrn` where create_time <'$days2ago'" );
			$command->execute();
			
			//CD 商品详情 抓取job
			$command = Yii::$app->db_queue2->createCommand("delete FROM `hc_collect_request_queue` where create_time <'$days2ago' and status in ('C','S','F')" );
			$command->execute();
			
			// amazon fba 库存report
			$command = Yii::$app->db_queue2->createCommand("DELETE FROM  `amazon_report_requset` WHERE
			(`process_status`='RD' AND `create_time`<'$days2ago' ) or
			(`process_status`='GF' AND `create_time`<'$days2ago' and (`get_report_id_count`=10 or `get_report_data_count`=10))" );
			$command->execute();

			//message Queue
			$command = Yii::$app->db->createCommand("delete FROM `message_api_queue` where create_time <'$days2ago' and status in ('C','S', 'F')" );
			$command->execute();
			
			
			
			//app data push queue
			$command = Yii::$app->db_queue->createCommand("delete FROM `ut_app_push_queue` where create_time <'$days2ago'   " );
			$command->execute();
			
			//redis housekeeping
			$keys = RedisHelper::RedisExe ('hkeys',array('Tracker_AppTempData'));
			
			if (!empty($keys)){
				foreach ($keys as $keyName){
					if (strpos($keyName, $days4ago) !== false  or strpos($keyName, $days2ago."_print") !== false)
						RedisHelper::RedisExe ('hdel',array('Tracker_AppTempData',$keyName));
				}//end of each key name, like user_1.carrier_frequency
			}
			
			//purge 2日前 tracker MainQueue 用到的redis
			$prefixDate = substr($days2ago, 5,2).substr($days2ago, 8,2); //2011-05-20 
			$prefixDateToday = substr($todayDate, 5,2).substr($todayDate, 8,2); //2011-05-20  
			$keys = RedisHelper::RedisExe ('hkeys',array('TrackMainQ'));
			if (!empty($keys)){
				foreach ($keys as $keyName){
					if (substr($keyName, 0,4) <= $prefixDate or substr($keyName, 0,4) > $prefixDateToday )
						RedisHelper::RedisExe ('hdel',array('TrackMainQ',$keyName));
				}//end of each key name, like user_1.carrier_frequency
			}
			
			$keys = RedisHelper::RedisExe ('hkeys',array('TrackerCommitQueue_LowP'));
			if (!empty($keys)){
				foreach ($keys as $keyName){
					if (substr($keyName, 0,4) <= $prefixDate or substr($keyName, 0,4) > $prefixDateToday )
						RedisHelper::RedisExe ('hdel',array('TrackerCommitQueue_LowP',$keyName));
				}//end of each key name, like user_1.carrier_frequency
			}
			
			$keys = RedisHelper::RedisExe ('hkeys',array('TrackerCommitQueue_HighP'));
			if (!empty($keys)){
				foreach ($keys as $keyName){
					if (substr($keyName, 0,4) <= $prefixDate or substr($keyName, 0,4) > $prefixDateToday )
						RedisHelper::RedisExe ('hdel',array('TrackerCommitQueue_HighP',$keyName));
				}//end of each key name, like user_1.carrier_frequency
			}
			
			$timeStamp2hoursAgo = time() - 2*60*60;
			$keys = RedisHelper::RedisExe ('hkeys',array('PDF_TASK_DONE_URL'));
			if (!empty($keys)){
				foreach ($keys as $keyName){
					$arr1 = explode("_",$keyName);
					if (isset($arr1[0]) and is_numeric($arr1[0]) and $arr1[0]<$timeStamp2hoursAgo)
						RedisHelper::RedisExe ('hdel',array('PDF_TASK_DONE_URL',$keyName));
				}//end of each key name, like user_1.carrier_frequency
			}
			
			$keys = RedisHelper::RedisExe ('hkeys',array('PDF_TASK_DETAIL'));
			if (!empty($keys)){
				foreach ($keys as $keyName){
					$arr1 = explode("_",$keyName);
					if (isset($arr1[0]) and is_numeric($arr1[0]) and $arr1[0]<$timeStamp2hoursAgo)
						RedisHelper::RedisExe ('hdel',array('PDF_TASK_DETAIL',$keyName));
				}//end of each key name, like user_1.carrier_frequency
			}
			
			DashBoardHelper::houseKeepingJobData();
			
			echo "try to do Amazon CS task Generating at".date('Y-m-d H:i:s')."\n";
			AmazoncsHelper::cronAutoGenerateAmzCsTemplateQuest();
			echo "finished doing Amazon CS task Generating at".date('Y-m-d H:i:s')."\n";
			
			
			
			$todayDate = date('Y-m-d');
			if (substr($todayDate, 8,2) == '02'){ // do this only once per month  2017-05-01
				//用户分析的data，保留90日即可
				$command = Yii::$app->db->createCommand("insert into   app_user_action_log_bk2017Q1  select * from  app_user_action_log where log_time <'$daytime90ago'" );
				$command->execute();
				
				$command = Yii::$app->db->createCommand("delete from  app_user_action_log where log_time <'$daytime90ago'" );
				$command->execute();
				
				//Lazada Listing clean up
				\eagle\modules\lazada\apihelpers\LazadaApiHelper::clearLazadaListingBeforeThreeMonth();
			}
		}//end of $first_run_for_today
		 
	}//end of batch job for generating api request for tracking
	
	static public function generateProdReports($target_puid=0){
		$rtn['message'] = "";
		$rtn['success'] = true;
		$now_str = date('Y-m-d H:i:s');
		$todayDate = date('Y-m-d');
		$reports = array();
	
		$message = "Cronb Job Started generateProdReport";
		 
		//step 1, get all puid from managedb
		//step 1.1, get all puid having activity during last 30 days
		$connection = Yii::$app->db;
		$command = $connection->createCommand(
				"SELECT distinct puid FROM `user_last_activity_time` WHERE `last_activity_time` >='". date('Y-m-d',strtotime('-90 days')) ."'"
		) ;
		$rows = $command->queryAll();

		//step 2, for each puid, call to request for each active tracking
		foreach ($rows as $row){
			$puid = $row['puid'] ;
			 
		//echo "Step 2.4 .";
		//Step 2.15, 当在一天新的日子首次运行，也就是 凌晨 00：00 运行，做上一天的统计
		 
			echo "try to gen report for $puid /";
			$reports[$puid] = self::generateProdSalesReportForPuid($puid);//,'RecommendProd'
		 
		// 
		//echo "Step 2.6 .";
		}//end of each puid
	
		//"Step 2.7 . Generate api request for all tracking ,for each puid";
		 
		//Step final, 做一个总的report
	 
		return $reports;	
		}//end of batch job for generating api request for tracking
	
	
	static public function doGenerateTrackingForPuid(){
		$command = Yii::$app->db_queue->createCommand("select * from `tracker_gen_request_for_puid` order by create_time " );
		$rows = $command->queryAll();
		foreach ($rows as $row){
			$puid = $row['puid'];
 
			
			echo "try to purgeUnbindedPlatformTrackingNo for $puid /";
			self::purgeUnbindedPlatformTrackingNo();
			echo "try to requestTrackingForUid for $puid /";
			$rtn = self::requestTrackingForUid($puid );
			$command = Yii::$app->db_queue->createCommand("delete  from `tracker_gen_request_for_puid` where puid= $puid " );
			$command->execute();
		}
	}
	
	static public function purgeUnbindedPlatformTrackingNo(){
		$puid = \Yii::$app->subdb->getCurrentPuid();
		$sellerIds = array();
		//step 1: Load all binded smt and ebay account user ids
		$connection = Yii::$app->db;  
		$command = $connection->createCommand(
				"select selleruserid from saas_ebay_user where uid=$puid " ) ;
		$rows = $command->queryAll();
		foreach ($rows as $aRow ){
			$sellerIds[] = "'".str_replace("'","\'",$aRow['selleruserid'])  ."'";
		}
		
		$command = $connection->createCommand("select sellerloginid from saas_aliexpress_user where  uid=$puid  " ) ;
		$rows = $command->queryAll();
		foreach ($rows as $aRow ){
			$sellerIds[] = "'". str_replace("'","\'",$aRow['sellerloginid']) ."'";
		}
		
		$command = $connection->createCommand("select merchant_id from saas_amazon_user where  uid=$puid  " ) ;
		$rows = $command->queryAll();
		foreach ($rows as $aRow ){
			$sellerIds[] = "'". str_replace("'","\'",$aRow['merchant_id']) ."'";
		}
		
		$command = $connection->createCommand("select sellerloginid from saas_dhgate_user where  uid=$puid  " ) ;
		$rows = $command->queryAll();
		foreach ($rows as $aRow ){
			$sellerIds[] = "'".str_replace("'","\'",$aRow['sellerloginid'])  ."'";
		}
		
		$command = $connection->createCommand("select username from saas_cdiscount_user where  uid=$puid  " ) ;
		$rows = $command->queryAll();
		foreach ($rows as $aRow ){
			$sellerIds[] = "'".str_replace("'","\'",$aRow['username'])  ."'";
		}
		
		$command = $connection->createCommand("select username from saas_priceminister_user where  uid=$puid  " ) ;
		$rows = $command->queryAll();
		foreach ($rows as $aRow ){
			$sellerIds[] = "'".str_replace("'","\'",$aRow['username'])  ."'";
		}
		
		$command = $connection->createCommand("select store_name from saas_bonanza_user where  uid=$puid  " ) ;
		$rows = $command->queryAll();
		foreach ($rows as $aRow ){
			$sellerIds[] = "'".str_replace("'","\'",$aRow['store_name'])  ."'";
		}
		
		$command = $connection->createCommand("select platform_userid from saas_lazada_user where  puid=$puid  " ) ;
		$rows = $command->queryAll();
		foreach ($rows as $aRow ){
			$sellerIds[] = "'".str_replace("'","\'",$aRow['platform_userid'])  ."'";
		}
				 
		$command = $connection->createCommand("select store_name from saas_wish_user where  uid=$puid " ) ;
		$rows = $command->queryAll();
		foreach ($rows as $aRow ){
			$sellerIds[] = "'".str_replace("'","\'",$aRow['store_name'])  ."'";
		}
		
		$command = $connection->createCommand(
				"select store_name from saas_ensogo_user where uid=$puid " );
		$rows = $command->queryAll();
		foreach ($rows as $aRow ){
			$sellerIds[] = "'".str_replace("'","\'",$aRow['store_name'])  ."'";
		}
		
		//step 1.5, make sure the OMS tracking has got its seller id
		//$initSellerIdSql = "update `lt_tracking`, od_order_shipped set `seller_id` = selleruserid  WHERE seller_id is null and  source='O' and tracking_number =`track_no`";
		//$command = Yii::$app->subdb->createCommand($initSellerIdSql ) ;
		//$affectRows = $command->execute();
		 
		//step 2, delete those Oms type tracking, not from the above seller ids
		$sql = '';
		 
		$allSelleridStr = implode(",", $sellerIds);
		if (trim($allSelleridStr)  =='')
			$allSelleridStr = "'x'";
		$sql = "delete  FROM lt_tracking
				WHERE source =  'O'
				and seller_id is not null AND seller_id <>'' and seller_id not   
				IN ( 
					$allSelleridStr 
					)";
		$connection = Yii::$app->subdb;
		$command = $connection->createCommand($sql ) ;
		$affectRows = $command->execute();			 
		 
		return $sql;
	}
	/**
	 +---------------------------------------------------------------------------------------------
	 * 这是Tracking的Monitor，这个job会每 3分钟 启动一次，
	 * 然后对近12小时绑定的进行copy OMS 物流单号。
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param
	 +---------------------------------------------------------------------------------------------
	 * @return						array('success'=true,'message'='')
	 *
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		yzq		2015/2/9				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function initTrackingForNewAccounts(){
		$rtn['message'] = "";
		$rtn['success'] = true;
		$now_str = date('Y-m-d H:i:s');
		//step 1, get all puid having new account during last 12 hours
		$puids_platforms = PlatformAccountApi::getLastestBindingPuidTimeMap(3);
	   // echo "got puids during last 3 hours ".print_r($puids_platforms,true) ."\n";
		//step 1.5, 把返回的格式，提取unique 的puid.
		$puids = array();
		foreach ($puids_platforms as $platform=>$ids){
			foreach ($ids as $id=>$create_time){
				$puids[$id] = $create_time;
			}
		}
		
		//step 2, for each puid, call to request for each active tracking
		foreach ($puids as $puid=>$create_time){
			 
			echo "start to retrieve oms for puid $puid \n";
			do {//每次获取不多于300条记录，防止服务器死掉，如果获取得到的是300条，可能还有的，继续获取一次看还有没有
				$rtn = self::copyTrackingFromOmsShippedComplete( $puid );
				//echo "got oms for $puid , result=".print_r($rtn,true)."\n";
			}while($rtn['count'] > 290);

		//Step 2.2 Generate api request for all tracking
		$rtn = self::requestTrackingForUid($puid );	
		}//end of each puid
			
	}//end of batch job for generating api request for New accounts binded
		
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 对这个puid 下面的所有未完成的 物流号，进行判断其超时状态，已经对未完成的物流号，生成tracking request到队列中
	 *
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param $platform				'':all
	 *                              aliexpress: 只返回阿里巴巴的订单物流号统计
	 *                              ebay: 只返回阿里巴巴的订单物流号统计
	 +---------------------------------------------------------------------------------------------
	 * @return						当没有新账号绑定的时候，直接返回success=false
	 *                                     array('success'=true,'message'='',
	 *                                     state_distribution=[ ['state'='complete' cc=20],['state'='exception' cc=60]])
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		yzq		2015/2/9				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function progressOfTrackingDataInit($platform=''){
		$rtn['message'] = "";
		$rtn['success'] = true;
		
		//call to 检查是否有近5小时绑定过账号，如果有，才做统计，否则浪费性能
		$puid = \Yii::$app->subdb->getCurrentPuid();
		$ebay_aliexpress_new = PlatformAccountApi::getAcountBindingInfo($puid,5);
		
		if (empty($ebay_aliexpress_new['ebay']) and empty( $ebay_aliexpress_new['dhgate'] ) and  empty($ebay_aliexpress_new['aliexpress'])
				and  empty($ebay_aliexpress_new['wish']) and  empty($ebay_aliexpress_new['cdiscount']) and  empty($ebay_aliexpress_new['lazada'])
		      or ($platform<>'' and empty($ebay_aliexpress_new[$platform]) )  ){
			$rtn['success'] = false;
			return $rtn;
		}
					
		$criteria = "";
		if ($platform <> ''){
			$criteria = " and platform='$platform' ";
		}
		
		$isNotCompleted =  Tracking::find()->where("source='O' and state='initial' $criteria  ")->exists();

		if ($isNotCompleted){
			//	查询所有这个平台的订单以及state 分布
			$rtn['state_distribution'] = Yii::$app->get('subdb')->createCommand(
				" select state,count(*) as cc from lt_tracking where source='O' $criteria group by state"
				        )->queryAll();
		}else{
			$rtn['success'] = false;
		}	
		return $rtn;
	}
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 对这个puid 下面的所有未完成的 物流号，进行判断其超时状态，已经对未完成的物流号，生成tracking request到队列中
	 * 
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param $puid					每个用户的帐套不同
	 * @param $call by online       是否Online User发起，如果是，只对手动录入和Excel录入的记录发起API请求即可，
	 *                              默认是 否，那么对所有记录都发起API请求。
	 +---------------------------------------------------------------------------------------------
	 * @return						array('success'=true,'message'='')
	 *
	 * @invoking					TrackingHelper::requestTrackingForUid(1);
	 *
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		yzq		2015/2/9				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function requestTrackingForUid($puid = 0 ,$call_by_online = false){
		//echo "\n requestTrackingForUid 1";
		$rtn['message'] = "";
		$rtn['success'] = true;
		$now_str = date('Y-m-d H:i:s');
		$six_hours_ago = date('Y-m-d H:i:s',strtotime('-6 hours'));
		$ten_days_ago = date('Y-m-d',strtotime('-10 days'));
		$days90_ago = date('Y-m-d',strtotime('-90 days'));
		$days120_ago = date('Y-m-d',strtotime('-120 days'));
		
		//step 1: change to this puid database
		if ($puid == 0)
			$puid = \Yii::$app->subdb->getCurrentPuid();
		
		 
	//	echo "\n requestTrackingForUid 2";
		//step 2: for those tracking 查询不到, while tried for 10 days, set them as 无法交运，然后就不要强求了
		$command = Yii::$app->subdb->createCommand("update lt_tracking set state='".Tracking::getSysState("无法交运")."'
								,status='".Tracking::getSysStatus("无法交运")."'
								, update_time='$now_str' where status='".Tracking::getSysStatus("查询不到")."' 
								and ( create_time <='$ten_days_ago'  )" ); //or ship_out_date<='$ten_days_ago'
		
		$affectRows = $command->execute();
		 
		//step 2.2: 90天前的，视为 已过期，不要搞了,但是对巴西的，稍微放松要求到 120天。
		$countriesMax120days = array("'BR'");
		$command = Yii::$app->subdb->createCommand("update lt_tracking set state='".Tracking::getSysState("已完成")."'
								,status='".Tracking::getSysStatus("过期物流号")."', update_time='$now_str' where status in ('".Tracking::getSysStatus("查询等候中")."','".Tracking::getSysStatus("查询不到")."')
								and (ship_out_date <='$days90_ago' and  ship_out_date >'1990-1-1' or create_time <='$days90_ago')
								and to_nation not in ( " .implode(",", $countriesMax120days). " )" );
		
		$affectRows = $command->execute();
		//echo "\n requestTrackingForUid 3";
		//巴西之类的，允许过期期限是120days
		$command = Yii::$app->subdb->createCommand("update lt_tracking set state='".Tracking::getSysState("已完成")."'
								,status='".Tracking::getSysStatus("过期物流号")."', update_time='$now_str' where status in ('".Tracking::getSysStatus("查询等候中")."','".Tracking::getSysStatus("查询不到")."')
				and (ship_out_date <='$days120_ago' and  ship_out_date >'1990-1-1' or create_time <='$days120_ago')   
				and to_nation in ( " .implode(",", $countriesMax120days). " )" );
		
		$affectRows = $command->execute();
		//echo "\n requestTrackingForUid 3.1";
		//step 2.3: 如果ship by 是平邮的，就更新为状态 “无挂号”，然后state是已完成就算了
		$command = Yii::$app->subdb->createCommand("update lt_tracking set state='".Tracking::getSysState("已完成")."'
								,status='".Tracking::getSysStatus("无挂号")."', update_time='$now_str' where status in 
								('".Tracking::getSysStatus("查询等候中")."','".Tracking::getSysStatus("查询不到")."')
								and  (  ship_by like '%平邮%' or  ship_by like '%无挂号%' or  ship_by like '%非挂号%' )" );
		
		$affectRows = $command->execute();
		//echo "\n requestTrackingForUid 3.2";
		// 2.4 : 如果Aliexpress已经FINISH状态了，update 非完成的订单为已完成，已签收
		//new version do not use order original table, but order v2
		$select_str=" select track_no,all_event from  lt_tracking, od_order_v2  
										where  status not in ('".Tracking::getSysStatus("成功签收")."','".Tracking::getSysStatus("买家已确认")."')  
												and order_source_status in ('FINISH') and
								order_source_order_id = lt_tracking.order_id and lt_tracking.source='O' and platform='aliexpress'    ";
		//echo "\n requestTrackingForUid 3.3";
		$command = Yii::$app->subdb->createCommand( $select_str );
	//	echo "\n requestTrackingForUid 3.4";
		$rows = $command->queryAll();
	//	echo "\n requestTrackingForUid 3.5";
		foreach ($rows as $row){
			if( $puid=='18870' && $row['all_event']!='' ){
				continue;
			}
			self::manualSetOneTrackingComplete( $row['track_no']);

		}
	//	echo "\n requestTrackingForUid 4";
		//2.4.b : 如果Dhgate已经FINISH状态了，update 非完成的订单为已完成，已签收
		//new version do not use order original table, but order v2
		$select_str=" select track_no,all_event from  lt_tracking, od_order_v2
										where  status not in ('".Tracking::getSysStatus("成功签收")."','".Tracking::getSysStatus("买家已确认")."')  and
												order_source_status in ('102006','102007','102111','111111') and
												order_source_order_id = lt_tracking.order_id and lt_tracking.source='O' and platform='dhgate'    ";
		
		$command = Yii::$app->subdb->createCommand( $select_str );
		$rows = $command->queryAll();
		foreach ($rows as $row){
			if( $puid=='18870' && $row['all_event']!='' ){
				continue;
			}
			self::manualSetOneTrackingComplete( $row['track_no']);
		}
		//echo "\n requestTrackingForUid 5";
		//2.4.c : 如果物流服务在自动忽略名单，就忽略
		$ignoreList1 = self::getUserIgnoredCheckCarriers($puid);
		if ($ignoreList1['success']){
			$ignoreList= $ignoreList1['data'];
		}else
			$ignoreList=[];
		foreach ($ignoreList as $ship_by){
			Tracking::updateAll(['status'=>'ignored']," ship_by=:ship_by and state!='complete' and state!='deleted' ",[':ship_by'=>$ship_by]);
		}
	 
		
		//Step 2.5, 判断改用户是否很久没有上来了，如果很久了，就不要他
		//$last_gen_time = self::getTrackerTempDataFromRedis("last_gen_track_request_time");
		$lastTouch = UserLastActionTimeHelper::getLastTouchTimeByPuid($puid);
		$checkInterval = date('Y-m-d H:i:s',strtotime('-12 hours'));;
		if (empty($lastTouch))
			$lastTouch = date('Y-m-d H:i:s',strtotime('-6 days'));
		
		//1.客户6天前有访问过的，更新频率每3天更新一次
		//2.客户3天前有访问过的，更新频率每1天更新一次
		//3.客户2天前有访问过的，更新频率每12小时一次
		//3.客户2天内有访问过的，更新频率每6小时一次
		if ($lastTouch <= date('Y-m-d H:i:s',strtotime('-30 days')) )
			$checkInterval = date('Y-m-d H:i:s',strtotime('-60 days'));
				
		elseif ($lastTouch <= date('Y-m-d H:i:s',strtotime('-20 days')) )
			$checkInterval = date('Y-m-d H:i:s',strtotime('-30 days'));
		
		elseif ($lastTouch <= date('Y-m-d H:i:s',strtotime('-15 days')) )
			$checkInterval = date('Y-m-d H:i:s',strtotime('-20 days'));
						
		elseif ($lastTouch <= date('Y-m-d H:i:s',strtotime('-10 days')) )
			$checkInterval = date('Y-m-d H:i:s',strtotime('-10 days'));
								
		elseif ($lastTouch <= date('Y-m-d H:i:s',strtotime('-6 days')) )
			$checkInterval = date('Y-m-d H:i:s',strtotime('-3 days'));
		
		elseif ($lastTouch < date('Y-m-d H:i:s',strtotime('-3 days')) )
			$checkInterval = date('Y-m-d H:i:s',strtotime('-1 day'));
				
		elseif ($lastTouch < date('Y-m-d H:i:s',strtotime('-2 days')) )
			$checkInterval = date('Y-m-d H:i:s',strtotime('-18 hours'));
		
		elseif ($lastTouch < date('Y-m-d H:i:s',strtotime('-1 day')) )
			$checkInterval = date('Y-m-d H:i:s',strtotime('-12 hours'));
		//echo "\n requestTrackingForUid 7";
		$message ="Generate Puid $puid , lastTouch is $lastTouch, work for those updated before $checkInterval ";
		//\Yii::info(['Tracking',__CLASS__,__FUNCTION__,'Background',$message ],"edb\global");
		 
		//step 3: get all tracking 
		 
		//已完成或者无法交运的订单就不再check了。
		//新插入的记录是查询中,state=初始
		$condition = "";
		$thoseCanDo = array();
		//below canDos are Or 关系between each other
		//3.1 新来的物流号，立即进行查询
		$thoseCanDo[] = "state='".Tracking::getSysState("初始")."'";
		//3.2: 查询不到  ，更新周期24小时
		$thoseCanDo[] = "status='no_info' and update_time < '". date('Y-m-d H:i:s',strtotime('-24 hours'))."' and update_time<'$checkInterval'"; //ystest
		//3.3: 延缓查询 ，更新周期24小时
		$thoseCanDo[] = "status='suspend' and update_time < '". date('Y-m-d H:i:s',strtotime('-24 hours'))."' and update_time<'$checkInterval'"; //ystest
		//3.4: 运输中  并且第一个查询到的时间，距离现在2天以前，一般2天内不会到吧
		//3.4，另外如果是运输途中的，按照客户活跃度 间隔N更新一次
		$thoseCanDo[] = "status='shipping' and first_event_date < '". date('Y-m-d H:i:s',strtotime('-2 days'))."' and first_event_date>='". date('Y-m-d H:i:s',strtotime('-60 days'))."' and update_time<'$checkInterval'";
		$thoseCanDo[] = "status='shipping' and first_event_date < '". date('Y-m-d H:i:s',strtotime('-2 days'))."' and first_event_date>='". date('Y-m-d H:i:s',strtotime('-80 days'))."' and update_time<'". date('Y-m-d H:i:s',strtotime('-3 days'))."'";
		$thoseCanDo[] = "status='shipping' and first_event_date < '". date('Y-m-d H:i:s',strtotime('-2 days'))."' and first_event_date>='". date('Y-m-d H:i:s',strtotime('-150 days'))."' and update_time<'". date('Y-m-d H:i:s',strtotime('-5 days'))."'";
		//3.5: state是异常，更新间隔24小时了
		$thoseCanDo[] = "state='".Tracking::getSysState("异常")."' and update_time < '$checkInterval'";
		$thoseCanDo[] = "status='".Tracking::getSysStatus("查询等候中")."' and update_time < '". date('Y-m-d H:i:s',strtotime('-3 hours'))."'";
		//3.6： 无法查询 ， 在10-30天那个时间，10-20天是每天查询1次，21-30天就每隔2天查询一次
		/*yzq, 20150802, 无法交运的不要查询了，太耗费资源了
		$thoseCanDo[] = "state='".Tracking::getSysState("无法交运")."' and create_time <  '". date('Y-m-d H:i:s',strtotime('-10 days'))."'
									  and create_time >=  '". date('Y-m-d H:i:s',strtotime('-20 days'))."'  and update_time < '". date('Y-m-d H:i:s',strtotime('-2 day'))."' ";
		$thoseCanDo[] = "state='".Tracking::getSysState("无法交运")."' and create_time <  '". date('Y-m-d H:i:s',strtotime('-20 days'))."'
									  and create_time >=  '". date('Y-m-d H:i:s',strtotime('-30 days'))."'  and update_time < '". date('Y-m-d H:i:s',strtotime('-3 days'))."' ";		
		$thoseCanDo[] = "state='".Tracking::getSysState("无法交运")."' and create_time <  '". date('Y-m-d H:i:s',strtotime('-30 days'))."'
									  and create_time >=  '". date('Y-m-d H:i:s',strtotime('-60 days'))."'  and update_time < '". date('Y-m-d H:i:s',strtotime('-10 days'))."' ";
		*/
		$thoseCanIgnore = array();
		//Below are and 关系，between 互相并且和 thoseCanDo 也是And 关系
		//3.99, 以下几个state的不要做了。已完成，无法交运，已删除
		$thoseCanIgnore[] = "( state not in ('".Tracking::getSysState("已完成")."'
											,'".Tracking::getSysState("已删除")."'
											,'".Tracking::getSysState("无法交运")."'	) 
													or  status='".Tracking::getSysStatus("查询等候中")."' )";
		//3.98,所有物流号跟踪不超过120天，120天后就放弃跟中了
		$thoseCanIgnore[] = "ship_out_date >= '". date('Y-m-d',strtotime('-120 days')) ."' or ship_out_date is null or ship_out_date='1970-01-01' or status='".Tracking::getSysStatus("查询等候中")."'" ;
		//3.99：已忽略的不再查询
		$thoseCanIgnore[] = "status!='ignored'";
		$condition ="(";
		foreach ($thoseCanDo as $canDo){
			$condition .= ($condition =="(" ?"":" or ");
			$condition .= " ( $canDo )"; 
		}
		
		$condition .=")";
		
		foreach ($thoseCanIgnore as $canIgnore){
			$condition .= " and ( $canIgnore )";
		}
		
		//是否Online User发起，如果是，只对手动录入和Excel录入的记录发起API请求即可，
		if ($call_by_online){
			$condition .= " and source in ('M','E')";
		}		
		/*
		$trackingArray = Tracking::find()
							->select("id,track_no , addi_info") //ystest
							->andWhere($condition)
							->asArray()
							->all();
       */
	//	echo "SELECT `id`, `track_no`, `addi_info` FROM `lt_tracking`  	force index (status_state)  WHERE $condition";
		$command = Yii::$app->subdb->createCommand( "SELECT `id`, `track_no`, `addi_info`,`all_event` FROM `lt_tracking`  
								force index (status_state) 		 WHERE $condition   and status<>'quota_insufficient' " ); //
		$trackingArray = $command->queryAll();
		
		//step 4, for each tracking models need to be rechecked, write one request for each
		$track_list = array();
		$unregistered_track_list = array();
		$addinfos = array();
		$ids = array();//ystest
		foreach ($trackingArray as $aTracking){
			if( $puid=='18870' && $aTracking['all_event']!='' ){
				continue;
			}
				$track_list[] = $aTracking['track_no'];
				$ids[] = $aTracking['id']; //ystest
		} //end of each tracking

	
		// 使用buffer 异步机制，有异步job自动捡起来做的
		self::putIntoTrackQueueBuffer($track_list, ! Tracking::$IS_USER_REQUIRE_UPDATE );
		
		//ystest starts
		//update the lt_tracking update time so that when this job run again, do not reGenerate requests for them, they are in queue already
		if (!empty($ids)){
			$command = Yii::$app->subdb->createCommand("update lt_tracking set update_time='$now_str' where id in (".implode(",", $ids)." )" );
			$affectRows = $command->execute();
		}
		//ystest ends
		
		$rtn['condition'] = $condition;
		//force update the top menu statistics
		self::setTrackerTempDataToRedis("left_menu_statistics", json_encode(array()));
		self::setTrackerTempDataToRedis("top_menu_statistics", json_encode(array()));
		
		return $rtn;
		
	}//end of requestTracking
	
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 通过速卖通平台已经设置收货，判定这个物流号其实已经签收
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param $track_no	   
	 +---------------------------------------------------------------------------------------------
	 * @return						array('success'=true,'message'='')
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		yzq		2015/2/9				初始化
	 +---------------------------------------------------------------------------------------------
	 **/	
	static public function manualSetOneTrackingComplete($track_no ){
		//array('parcel_type'=>1,'status'=>1,'carrier_type'=>1,'from_nation'=>1,'to_nation'=>1,'all_event'=>1,'total_days'=>1,'first_event_date'=>1,'from_lang'=>1,'to_lang'=>1);
		$data = array();
		$now_str = date('Y-m-d H:i:s');
		
		$data['track_no'] = $track_no;
		$data['status'] =Tracking::getSysStatus("买家已确认");
		$data['state'] =Tracking::getSysState("已完成");
		$data['update_time'] = $now_str;
	//	$carriers = self::getCandidateCarrierType($track_no);
	//	$data['carrier_type'] = (isset($carriers[0])  ? $carriers[0] : 0);
		$aTracking = Tracking::find()
			->andWhere("track_no=:trackno",array(':trackno'=>$track_no) )->asArray()
			->one();
		
		$aTracking['addi_info'] = str_replace("`",'"',$aTracking['addi_info']);
		$addinfo = json_decode(   $aTracking['addi_info'],true);
		if (!empty($addinfo['consignee_country_code']))
			$data['to_nation'] = $addinfo['consignee_country_code'];
 
		if (empty($aTracking['first_event_date'])){
			$aTracking['first_event_date'] = $aTracking['ship_out_date'];
			$data['first_event_date'] = $aTracking['ship_out_date'];
		}
		
		if (!empty($aTracking['first_event_date']) and strlen($aTracking['first_event_date']) >= 10){
			$datetime1 = strtotime (date('Y-m-d H:i:s'));
			$datetime2 = strtotime (substr($aTracking['first_event_date'], 0,10)." 00:00:00");
			$days =ceil(($datetime1-$datetime2)/86400); //60s*60min*24h
			$data['total_days'] =  $days  ;
		}
		
		$data['last_event_date'] = $now_str;
	 
		//Do not use this man-made event, $data['all_event'] = json_encode($allEvents);
		TrackingQueueHelper::commitTrackingResultUsingValue($data );
	}
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 对一个指定的tracking，生成一条API track request，放到队列里面
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param $aTrackingOrTrackNo	可以使Tracking的Model或者一个tracking no
	 * @param $user_require_update	是否用户前端要求update，如果是，一定要生成request并且优先级为高			
	 * @param $addi_info            addition info, 例如是 timeout重试的，可以写特殊值，让重试的进程读取得到	
	 * @param $addi_params			addition parameter  附加参数    批量更新 eg.['batchupdate' =>true/false , .... ] 
	 +---------------------------------------------------------------------------------------------
	 * @return						array('success'=true,'message'='')
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		yzq		2015/2/9				初始化
	 +---------------------------------------------------------------------------------------------
	 **/	
	static public function generateOneRequestForTracking($aTrackingOrTrackNo, $user_require_update = false ,$addi_info='' , $addi_params=[] ){
		global $CACHE;
		$rtn['message'] = "";
		$rtn['success'] = true;
		if ($user_require_update == "Y")
			$user_require_update = true;
		
		$puid = \Yii::$app->subdb->getCurrentPuid();
		
		if (is_numeric($aTrackingOrTrackNo)){
			$aTrackingOrTrackNo = (String)$aTrackingOrTrackNo;
		}
		
		//判断是否传进来的是Tracking 的model 还是只不过 一个tracking no
		if (! is_string($aTrackingOrTrackNo))
			$aTracking = $aTrackingOrTrackNo;
		else //如果是track no，需要Load一下record
			$aTracking = Tracking::find()
					->andWhere("track_no=:trackno",array(':trackno'=>$aTrackingOrTrackNo) )
					->one();
		
		if (!isset($aTracking['track_no']) or $aTracking['track_no'] ==''){
			$rtn['message'] = "没有正确的物流号输入，请输入正确的物流号或者物流Model";
			$rtn['success'] = false;
			return $rtn;
		}
		
		if ($aTracking['status']=='quota_insufficient'){
			$used_count = TrackingHelper::getTrackerUsedQuota($puid);
			$max_import_limit = TrackingHelper::getTrackerQuota($puid);
			if ($max_import_limit <= $used_count){
				$rtn['message'] = "当前超出了物流号新增查询配额，请确认有足够的物流查询额度.";
				$rtn['success'] = false;
			}else{//扣除
				//等到确定要进去了才扣除 
				//TrackingHelper::addTrackerQuotaToRedis("trackerImportLimit_".$CACHE['TrackerSuffix'] ,   1);	
			}
		}
		//如果是18870这个用户的数据，all_event如果不是空的，就不更新了，只查一次
		if( $puid=='18870' ){
			if( $aTracking['all_event']!='' ){
				$rtn['message'] = "这个单号已经查过一次，不用再查了";
				$rtn['success'] = false;
			}
		}

		//判断 上次查询时间 是否已经 到了限定的可以再更新的时间 
		if ($aTracking['state'] == Tracking::getSysState("无法交运") ){
			//无法交运耗费资源多， 所以自动刷新和手动刷新都限定为20小时， 其他状态为8小时一次
			$limit_hours = '20';
			$limit_hours_ago = date('Y-m-d H:i:s',strtotime('-'.$limit_hours.' hours')); 
			$limit_hours_ago2 = date('Y-m-d H:i:s',time()-3600); 									//无法交运 手动设置查询使用的运输服务 刷新率 限定为1小时
			$limit_hours2 = '1小时';
		}else{
			$limit_hours = '8';
			$limit_hours_ago = date('Y-m-d H:i:s',strtotime('-'.$limit_hours.' hours'));
			$limit_hours_ago2 = date('Y-m-d H:i:s',time()-600); 									//无法交运 手动设置查询使用的运输服务 刷新率 限定为10分钟
			$limit_hours2 = '10分钟';
		}
		
		$tracking_addi_info = json_decode($aTracking['addi_info'],true);	
		$tracking_addi_info['consignee_country_code'] = $aTracking->getConsignee_country_code();
		
		if(empty($addi_params['setCarrierType'])){
			if (!empty($tracking_addi_info['last_manual_refresh_time']) && $tracking_addi_info['last_manual_refresh_time'] > $limit_hours_ago ){
				// 
				$rtn['message'] = $aTracking['track_no']." 上次更新时间 为 ".$tracking_addi_info['last_manual_refresh_time'] .',请在'.$limit_hours.'小时后再更新!';
				$rtn['success'] = false;
				return $rtn;
			}
		}else{//手动设置查询使用的运输服务的情况
			if (!empty($tracking_addi_info['last_set_carrier_type_time']) && $tracking_addi_info['last_set_carrier_type_time'] > $limit_hours_ago2 ){
				$rtn['message'] = $aTracking['track_no']." 上次设置查询使用的运输服务的时间 为 ".$tracking_addi_info['last_set_carrier_type_time'] .',请在'.$limit_hours2.'后再更新!';
				$rtn['success'] = false;
				return $rtn;
			}
		}
		//step 3.0，如果OMS and 有order id 的，尝试判断他的 order date 是否4个月以前，如果是的，不要查了，浪费资源
		if ($aTracking['source'] =='O' and !empty($aTracking['order_id'])){		
			if (empty($aTracking['ship_out_date']) ){				
				//如果发出时间是 空，我们尝试从oms获取他的 order date，然后作为ship out date 的判断依据 
				$getOmsOrder = self::getOrderDetailFromOMSByTrackNo($aTracking['track_no']);
				if ($getOmsOrder['success']){					
					$order_time = !empty($getOmsOrder['order']['paid_time'])?$getOmsOrder['order']['paid_time']:(!empty($getOmsOrder['order']['delivery_time'])?$getOmsOrder['order']['delivery_time']:"");
					if ($order_time <> ""){
						$order_time = date('Y-m-d H:i:s',$order_time);
						$aTracking->ship_out_date = $order_time;						
						$aTracking->save(false);
						$aTracking['ship_out_date'] = $order_time;
					}
				}
			}//end of when ship out date is empty
			
			if ( !$user_require_update and !empty($aTracking['ship_out_date'])  ){
				$days_120_ago = date('Y-m-d H:i:s',strtotime('-90 days'));
				//如果物流号的ship out date 是120 天以前的，直接skip
				if ($aTracking['ship_out_date'] < $days_120_ago   ){
					$aTracking->update_time = date('Y-m-d H:i:s');
					$aTracking->status = Tracking::getSysStatus("过期物流号");					
					$aTracking->state = Tracking::getSysState("已完成");
					//$aTracking->save(false);
			 
					$command = Yii::$app->subdb->createCommand("update lt_tracking set 
							update_time='".date('Y-m-d H:i:s')."' ,
							status='".$aTracking->status."',
							state='".$aTracking->state."' where track_no = '".$aTracking->track_no . "' "  );
								
					$affectRows = $command->execute();

					$rtn['message'] = "物流号".$aTracking['track_no']." 是4个月以前的，".$aTracking['ship_out_date'].",过期了,不需要跟踪";
	//				\Yii::info(['Tracking',__CLASS__,__FUNCTION__,'Background',$rtn['message'] ],"edb\global");
					return $rtn;
				}
			}
		}//end of order id presents
		
		if ($user_require_update){
			//force update the top menu statistics
			$track_statistics_str = self::getTrackerTempDataFromRedis("left_menu_statistics");
			$track_statistics = json_decode($track_statistics_str,true);
			if(isset($track_statistics[$aTracking->seller_id]))
				unset($track_statistics[$aTracking->seller_id]);
			if(isset($track_statistics['all']))
				unset($track_statistics['all']);
			self::setTrackerTempDataToRedis("left_menu_statistics", json_encode($track_statistics));			
			self::setTrackerTempDataToRedis("top_menu_statistics", json_encode(array()));
		}
	 
		//step 4.1, check queue, if there is a such one pending, skip writing a new one
		$theExistingQueueReq = TrackerApiQueue::find()
						->andWhere("track_no=:trackno and status='P' and puid=$puid",array(':trackno'=>$aTracking['track_no']))
						->one();
		
		/* 调试 
		$tmpCommand = TrackerApiQueue::find()
		->andWhere("track_no=:trackno and status='P' and puid=$puid",array(':trackno'=>$aTracking['track_no']))
		->createCommand();
		echo "<br>".$tmpCommand->getRawSql();
			*/
		//if already having pending api request, just skip this
		if ($theExistingQueueReq != null){
			//如果是用户前端要求立即更新，吧优先级修改为高
			if ($user_require_update){
				if ($aTracking['state'] == Tracking::getSysState("无法交运")  &&  !empty($addi_params['batchupdate']) ){
					$theExistingQueueReq->priority = 2;
				}else{
					$theExistingQueueReq->priority = 1;
				}
				
				//用户手动设置查询方式而需要刷新物流信息的时候
				if(!empty($addi_params['setCarrierType']) && isset($addi_params['CarrierType'])){
					$theExistingQueueReq->selected_carrier = -100;//重置为default value
					$theExistingQueueReq->candidate_carriers = $addi_params['CarrierType'];
				}
				
				$theExistingQueueReq->save();
				//ystest starts
				//保存之前的有效状态到 addiinfo
				if ($aTracking->status <> Tracking::getSysStatus("查询等候中")){
					$addi_info1 = [];
					if (!empty($aTracking->addi_info))
						$addi_info1 = json_decode($aTracking->addi_info,true);
				
					$addi_info1['last_status'] = $aTracking->status;
					$aTracking->addi_info = json_encode($addi_info1);
				}
				//ystest end
				
				$aTracking->status = Tracking::getSysStatus("查询等候中");
				$aTracking->save(false);
			}else{
				if ($theExistingQueueReq->priority >2 and $aTracking->source=='M'){
					$theExistingQueueReq->priority = 2;
					$theExistingQueueReq->save(false);			
				}
			}
			return $rtn;
		}
		
		//step 4.2, check queue, if there is a such one processing but no respond for 5 minutes, update it to failed
		$five_minutes_ago = date('Y-m-d H:i:s',strtotime('-5 minutes'));
		$existingOne = TrackerApiQueue::find()
						->andWhere("track_no=:trackno and status='S'  and puid=$puid ",array(':trackno'=>$aTracking['track_no']) )
						->one();
			
		if ($existingOne){
			//check if it is with 5 minutes, if yes, do nothing, leave it processing
			if ($existingOne->update_time > $five_minutes_ago){
				$rtn['message'] = "请求过频密, 请稍候重试!";
				$rtn['success'] = false;
				return $rtn;
			}
		
			//if it is 5 minutes ago, kill it and make a new request
			$existingOne->update_time = date('Y-m-d H:i:s');
			$existingOne->status = 'F';
			$addi_info1 = json_decode($existingOne->addinfo,true);
			$addi_info1['failReason'] = 'E1';
			$existingOne->addinfo = json_encode($addi_info1);
			
			if ( $existingOne->save(false) ){//save successfull
				//$rtn['message'] = "更新请求已提交,稍候手动刷新页面!";
			}else{
				$rtn['success'] = false;
				$rtn['message'] .= TranslateHelper::t($existingOne->track_no."ETRK004：把做了5分钟超时的Track request修改为失败，出现错误.");
				foreach ($existingOne->errors as $k => $anError){
					$rtn['message'] .= ($rtn['message']==""?"":"<br>"). $k.":".$anError[0];
				}//end of each error
				\Yii::error(['Tracking',__CLASS__,__FUNCTION__,'Background',$rtn['message']],"edb\global");
				return $rtn;
			}//end of save failed
		}//end of if there is S sattus
		
		//step 4.3, write a new request in queue
		$now_str = date('Y-m-d H:i:s');
		//使用Cache 机制，减少每次New 一个Object的耗费
		global $CACHE;
		if (!isset($CACHE['Tracking']['TrackerApiQueue_NewRecord']))
			$CACHE['Tracking']['TrackerApiQueue_NewRecord'] = new TrackerApiQueue();
		$ApiRequestModel = $CACHE['Tracking']['TrackerApiQueue_NewRecord'];
		$ApiRequestModel->selected_carrier = -100; //default value
		$ApiRequestModel->create_time = $now_str;
		$ApiRequestModel->update_time = $now_str;
		//设置默认状态是 查询中
		$ApiRequestModel->status = "P" ;
		$ApiRequestModel->track_no = $aTracking['track_no'];
 
		$ApiRequestModel->addinfo = $aTracking['addi_info'];
		
		//ys0922 remove 鬼影
		$ApiRequestModel->selected_carrier = 0;
		$ApiRequestModel->candidate_carriers = '';
		
		
		if(!isset($tracking_addi_info['set_carrier_type'])){
			//如果上次的 selected carrier 是可以查询得到的，就用上次的行了 //且不是用户手动设定的情况
			if ($aTracking['status']<>'no_info' and  $aTracking['carrier_type'] <> ''
					and ($aTracking['state']=='normal' or $aTracking['state']=='exception') ) {
				$ApiRequestModel->selected_carrier = $aTracking['carrier_type'];
				$ApiRequestModel->candidate_carriers = "".$aTracking['carrier_type']."";
			}
		}else{
			//如果用户手动设置查询方式，则不指定 selected carrier，将carrier_type作为candidate_carriers
			/*
			if ($aTracking['carrier_type']<>'' and $aTracking['state']!=='complete' && $aTracking['state']!=='deleted'){
				$ApiRequestModel->selected_carrier = -100;//重置为default value
				$ApiRequestModel->candidate_carriers = "".$aTracking['carrier_type']."";
			}*/
			//既然指定了setCarrierType，就用它了
			$ApiRequestModel->selected_carrier = $tracking_addi_info['set_carrier_type']; 
			$ApiRequestModel->candidate_carriers = $tracking_addi_info['set_carrier_type'];
		}
		
		
		//不同情况设置不同优先级,优先级1-5， 1最高
		//default is 5，非常低的优先级
		$ApiRequestModel->priority = 5;
			
		//如果是手动录入并且是首次执行的，优先级为高
		
		if ($aTracking['source'] =='M' and $aTracking['state']== Tracking::getSysState("初始") )
			$ApiRequestModel->priority = 2;
			
		//Excel 导入并且是首次执行的，优先级为中
		if ($aTracking['source'] =='E' and $aTracking['state']== Tracking::getSysState("初始") )
			$ApiRequestModel->priority = 3;
			
		//OMS 导入并且是首次执行的，优先级为低
		if ($aTracking['source'] =='O' and $aTracking['state']== Tracking::getSysState("初始") ){
			$ApiRequestModel->priority = 4;
			//如果是2天到 2周内 的，加优先级
			if ($aTracking['ship_out_date'] <= date('Y-m-d',strtotime('-1 day')) 
				and $aTracking['ship_out_date'] > date('Y-m-d',strtotime('-14 days'))){
				$ApiRequestModel->priority -- ;
			}
		}
		if ($user_require_update){
			$ApiRequestModel->priority = 1;
		}
		
		//如果是批量更新无法交运的物流单号, 优先级设置 为2
		if ($aTracking['state'] == Tracking::getSysState("无法交运")  &&  !empty($addi_params['batchupdate']) ){
			$ApiRequestModel->priority = 2;
		}
		
		
		//如果这单东西是oms来的，而又没有shipping method code，那么就抄过来一次，以后不用再抄了
		
		if (  $aTracking->source =='O' ){// and $aTracking->platform =='aliexpress' yzq 2017-3-17
			$addi_info1 = json_decode($aTracking->addi_info,true);
			$array1 = json_decode($ApiRequestModel->addinfo,true);
			
			
			if (!isset($addi_info1['shipping_method_code'])){
				//copy from order_sihpping_v2,yzq 20170123
				$sql = "select * from  od_order_shipped_v2  where  tracking_number ='".$ApiRequestModel->track_no."'";

				$command = Yii::$app->get('subdb')->createCommand($sql);
				$shipped_row = $command->queryOne();
				
				if (!empty($shipped_row)){
					
					//20170123 this can be removed after 2017-2-5
					//对速卖通旧的进行mapping，或者 shipping method code
					if ( empty($shipped_row['shipping_method_code'])){
$mapping1=[
'China Post Ordinary Small Packet Plus'=>'YANWEN_JYT',
'4PX Singapore Post OM Pro'=>'SGP_OMP',
'Correos Economy'=>'SINOTRANS_PY',
'OMNIVA Economic Air Mail'=>'OMNIVA_ECONOMY',
'Posti Finland Economy'=>'ITELLA_PY',
'Royal Mail Economy'=>'ROYAL_MAIL_PY',
'Ruston Economic Air Mail'=>'RUSTON_ECONOMY',
'SF Economic Air Mail'=>'SF_EPARCEL_OM',
'SunYou Economic Air Mail'=>'SUNYOU_ECONOMY',
'Yanwen Economic Air Mail'=>'YANWEN_ECONOMY',
'AliExpress Saver Shipping'=>'CAINIAO_SAVER',
'AliExpress Standard Shipping'=>'CAINIAO_STANDARD',
'139 ECONOMIC Package'=>'ECONOMIC139',
'4PX RM'=>'FOURPX_RM',
'Asendia'=>'ASENDIA',
'Aramex'=>'ARAMEX',
'Austrian Post'=>'ATPOST',
'Bpost International'=>'BPOST',
'Canada Post'=>'CAPOST',
'CDEK'=>'CDEK',
'China Post Registered Air Mail'=>'CPAM',
'China Post Air Parcel'=>'CPAP',
'Chukou1'=>'CHUKOU1',
'CNE Express'=>'CNE',
'CORREOS PAQ 72'=>'SINOTRANS_AM',
'DHL Global Mail'=>'EMS_SH_ZX_US',
'DPD'=>'DPD',
'Enterprise des Poste Lao'=>'LAOPOST',
'ePacket'=>'EMS_ZX_ZX_US',
'Equick'=>'EQUICK',
'Flyt Express'=>'FLYT',
'GLS'=>'GLS',
'HongKong Post Air Mail'=>'HKPAM',
'HongKong Post Air Parcel'=>'HKPAP',
'J-NET'=>'CTR_LAND_PICKUP',
'Magyar Post'=>'HUPOST',
'Meest'=>'MEEST',
'Miuson Europe'=>'MIUSON',
'Mongol Post'=>'MNPOST',
'New Zealand Post'=>'NZPOST',
'Omniva'=>'EEPOST',
'One World Express'=>'ONEWORLD',
'PONY'=>'PONY',
'POS Malaysia'=>'POST_MY',
'Posti Finland'=>'ITELLA',
'PostNL'=>'POST_NL',
'RETS-EXPRESS'=>'RETS',
'Russia Parcel Online'=>'RPO',
'Russian Air'=>'CPAM_HRB',
'SF eParcel'=>'SF_EPARCEL',
'SFCService'=>'SFC',
'Singapore Post'=>'SGP',
'Special Line-YW'=>'YANWEN_AM',
'SunYou'=>'SUNYOU_RM',
'Sweden Post'=>'SEP',
'Swiss Post'=>'CHP',
'TaiwanPost'=>'TWPOST',
'TEA-POST'=>'TEA',
'Thailand Post'=>'THPOST',
'Turkey Post'=>'PTT',
'UBI'=>'UBI',
'Ukrposhta'=>'UAPOST',
'VietNam Post'=>'VNPOST',
'YODEL'=>'YODEL',
'YunExpress'=>'YUNTU',
'AliExpress Premium Shipping'=>'CAINIAO_PREMIUM',
'DHL'=>'DHL',
'DHL e-commerce'=>'DHLECOM',
'DPEX'=>'TOLL',
'EMS'=>'EMS',
'e-EMS'=>'E_EMS',
'GATI'=>'GATI',
'Russia Express-SPSR'=>'SPSR_CN',
'SF Express'=>'SF',
'Speedpost'=>'SPEEDPOST',
'TNT'=>'TNT',
'UPS Expedited'=>'UPSE',
'UPS Express Saver'=>'UPS',
'Fedex IE'=>'FEDEX_IE',
'Fedex IP'=>'FEDEX',
"Seller's Shipping Method"=>'Other',
'Russian Post'=>'RUSSIAN_POST',
'CDEK_RU'=>'CDEK_RU',
'IML Express'=>'IML',
'PONY_RU'=>'PONY_RU',
'SPSR_RU'=>'SPSR_RU',
"Seller's Shipping Method - RU"=>'OTHER_RU',
'USPS'=>'USPS',
'UPS'=>'UPS_US',
"Seller's Shipping Method - US"=>'OTHER_US',
'Royal Mail'=>'ROYAL_MAIL',
'DHL_UK'=>'DHL_UK',
"Seller's Shipping Method - UK"=>'OTHER_UK',
"Deutsche Post"=>'DEUTSCHE_POST',
'DHL_DE'=>'DHL_DE',
"Seller's Shipping Method - DE"=>'OTHER_DE',
'Envialia'=>'ENVIALIA',
'Correos'=>'CORREOS',
'DHL_ES'=>'DHL_ES',
"Seller's Shipping Method - ES"=>'OTHER_ES',
'LAPOSTE'=>'LAPOSTE',
'DHL_FR'=>'DHL_FR',
"Seller's Shipping Method - FR"=>'OTHER_FR',
'Posteitaliane'=>'POSTEITALIANE',
'DHL_IT'=>'DHL_IT',
"Seller's Shipping Method - IT"=>'OTHER_IT',
'AUSPOST'=>'AUSPOST',
"Seller's Shipping Method - AU"=>'OTHER_AU',
'JNE'=>'JNE',
'aCommerce'=>'ACOMMERCE'
];
					
				if (isset($mapping1[trim($shipped_row['shipping_method_name'])]) ){
						
					$shipping_method_code =$mapping1[trim($shipped_row['shipping_method_name']) ];
					$shipped_row['shipping_method_code']= $mapping1[trim($shipped_row['shipping_method_name']) ];
					
					$sql = "update od_order_shipped_v2 set shipping_method_code='$shipping_method_code' where  tracking_number ='".$ApiRequestModel->track_no."'";
					
					$command = Yii::$app->get('subdb')->createCommand($sql);
					$command->execute();
				}
					}
					//end of to be commented
					
					
					
					$shipping_method_code = $shipped_row['shipping_method_code'];
					$addi_info1['shipping_method_code'] = $shipping_method_code;
					$array1['shipping_method_code'] = $shipping_method_code;
					$aTracking->addi_info = json_encode($addi_info1);
				}
			}
			$array1['order_id'] = $aTracking->order_id;
			$ApiRequestModel->addinfo = json_encode($array1);
		}
		
		// 获取当前的使用puid。  如果返回为false，说明还没有在puid在使用 
		$puid = \Yii::$app->subdb->getCurrentPuid();
		$ApiRequestModel->puid = $puid;
		$key = $ApiRequestModel->puid ."-".$ApiRequestModel->track_no;
		
		//检查是否可以通过smt api 快速做掉的，如果可以的话，run time设置为 -10，让 handler1 优先处理他
		$toNation2Code = $aTracking->getConsignee_country_code();
		if ($aTracking->source =='O' and $aTracking->platform =='aliexpress' and !empty($toNation2Code ) ){
			$ApiRequestModel->run_time = -10;
		}
		
		//看看优先级
		if (!isset($CACHE["isVIP"]["p".$puid])){
			$CACHE["isVIP"]["p".$puid] = 0;
		}
		
		$ApiRequestModel->priority += $CACHE["isVIP"]["p".$puid];
		
		self::$Insert_Api_Queue_Buffer[$key] = $ApiRequestModel->getAttributes();
		$rtn['success'] = true;
		$rtn['message'] = "更新请求已提交,稍候手动刷新页面!";
		//如果是手工触发立即更新，吧状态改为 查询中
		if ($user_require_update){
			//ystest starts
			//保存之前的有效状态到 addiinfo
			if ($aTracking->status <> Tracking::getSysStatus("查询等候中")){
				$addi_info1 = [];
				if (!empty($aTracking->addi_info))
					$addi_info1 = json_decode($aTracking->addi_info,true);
			
				$addi_info1['last_status'] = $aTracking->status;
				$aTracking->addi_info = json_encode($addi_info1);
			}
			//ystest end
			
			$aTracking->status = Tracking::getSysStatus("查询等候中");	
		}
		
		//最后 保存当前的执行时间 
		$tracking_addi_info = [];
		if (!empty($aTracking->addi_info))
			$tracking_addi_info = json_decode($aTracking->addi_info,true);
		
		$tracking_addi_info['last_manual_refresh_time'] = date('Y-m-d H:i:s');
		
		//如果create time 是2017-9-17 日以后的，那么，要扣除quota，如果还没有扣除
		if ($aTracking->create_time >= '2017-09-17' and empty($tracking_addi_info['quotaUsed'])  ){

			//等到真的进去队列查询了，才耗费quota吧,check quota sufficient when put to query queue
			$puid1 = $puid;
			$used_count = TrackingHelper::getTrackerUsedQuota($puid);
			$max_import_limit = TrackingHelper::getTrackerQuota($puid);
			if ($max_import_limit <= $used_count){
				$rtn['message'] = "当前超出了物流号新增查询配额，请确认有足够的物流查询额度.";
				$rtn['success'] = false;
				unset(self::$Insert_Api_Queue_Buffer[$key]);
				$aTracking->status= 'quota_insufficient' ;
				$aTracking->state = 'exception';
			}else{
				$suffix = $CACHE['TrackerSuffix'.$puid1];
				TrackingHelper::addTrackerQuotaToRedis("trackerImportLimit_".$suffix ,  1);//20170912 使用redisadd 的新接口
				$tracking_addi_info['quotaUsed'] = 1;
			}
		}
		
		
		$aTracking->addi_info = json_encode($tracking_addi_info);
		$aTracking->save(false);
		return $rtn;		
	}//end of function generate One Request For Tracking
	
	
	
	static public function getSuccessCarrierFromHistoryByCodePattern($pattern,$forDate){
		$selected_carrier_code='';
		$successCount = 0;
		$carrier_success_rate = self::getTrackerTempDataFromRedis("carrier_success_rate_$forDate");
		$carrier_success_rate = json_decode($carrier_success_rate,true);
		if (isset($carrier_success_rate[$pattern])){
			//检查这个code pattern成功，失败率，选取成功次数最多的carrier
			foreach($carrier_success_rate[$pattern] as $carrierCode=>$SuccessOrFail){
				if (isset($SuccessOrFail['Success']) and $SuccessOrFail['Success'] > $successCount)
					$selected_carrier_code = $carrierCode;
			}//end of each carrier in history for this pattern
		}
		$rtn['selected'] = $selected_carrier_code;
		$rtn['pattern']=$pattern;
		$rtn['forDate']=$forDate;
		return $selected_carrier_code;
	}
	
	static public function andForPuidLastTouchDuringHours($hours){
		global $CACHE;
		$now_str = date('Y-m-d H:i:s');
		$five_minutes_ago = date('Y-m-d H:i:s',strtotime('-5 minutes'));
		//查询3小时内玩过的
		//Step 1.1, 获取当前活跃的用户列表，优先处理这些用户的 request
		//获取3小时内有玩的用户，优先搞他们，这个结果要存起来，不要老是访问数据库计算
		if (!isset($CACHE['getPuidArrByInterval']["$hours hours"]['cache_time']) or
		$CACHE['getPuidArrByInterval']["$hours hours"]['cache_time'] < $five_minutes_ago ){
			$CACHE['getPuidArrByInterval']["$hours hours"]['puids'] = UserLastActionTimeHelper::getPuidArrByInterval($hours);
			$CACHE['getPuidArrByInterval']["$hours hours"]['cache_time'] = $now_str;
		}
		$puidsXHour = $CACHE['getPuidArrByInterval']["$hours hours"]['puids'];
			
		$puidXHourCriteria = "";
		if (count($puidsXHour) > 0)
			$puidXHourCriteria = " and puid in (".implode(",", $puidsXHour).") ";
			
		return $puidXHourCriteria;
	}


	/**
	 +---------------------------------------------------------------------------------------------
	 * 对该客户某一天的所有 物流号，是否成功进行分析统计，结果写到该客户的 ut_configData 中
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param  forDate               日期，一般是当天或者昨天
	 * @param  puid			         puid
	 +---------------------------------------------------------------------------------------------
	 * @return						Array ( [created] => Array ( [Success] => Array ( [********************] => 1 [##*********##] => 12 [**************#] => 7 [**********] => 6 [*********] => 5 [***************] => 1 [**************] => 2 [**********************] => 4 [*##***************] => 1 ) ) [updated] => Array ( ) )
	 *
	 * @invoking					
	 *
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		yzq		2015/3/2        		初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	public static function generateResultReportForPuid($forDate='',$puid='',$forApp=''){
		echo "\n generateResultReportForPuid 1";
		global $CAHCE;
		$result = array();
		if (empty($forDate))
			$forDate = date('Y-m-d');
		
		if (empty($puid)){
			$puid = \Yii::$app->subdb->getCurrentPuid();
		}
 
		if ($forApp=='' or $forApp=='Tracker'){
			 
		//Step 1，统计今天新建的单子的成功率
		$created_order = array();			
		$TrackingAll = Tracking::find()->where("date(create_time)='$forDate'")->all();

		foreach ($TrackingAll as $aTracking){
			$code_format = CarrierTypeOfTrackNumber::getCodeFormatOfString($aTracking->track_no);
			//初始化为0			
			if ($aTracking->status == Tracking::getSysStatus("查询不到")){
				if (!isset($created_order['Fail'][$code_format]))
					$created_order['Fail'][$code_format] = 0;
				$created_order['Fail'][$code_format] ++;				
			}
			
			if ( !in_array( $aTracking->status, [Tracking::getSysStatus("查询不到"),Tracking::getSysStatus("无挂号"),
						Tracking::getSysStatus("查询等候中") ,Tracking::getSysStatus("过期物流号")
					   ,Tracking::getSysStatus("无法交运") ]  ) ){
				if (!isset($created_order['Success'][$code_format]))
					$created_order['Success'][$code_format] = 0;
				$created_order['Success'][$code_format] ++;
			}
		}//end of each tracking

		//Step 2，统计不是今天新建，只是今天查询的单子的成功率
		$updated_order = array();
		$TrackingAll = Tracking::find()->where("date(create_time)<>'$forDate' and date(update_time)='$forDate'")->all();
		foreach ($TrackingAll as $aTracking){
			$code_format = CarrierTypeOfTrackNumber::getCodeFormatOfString($aTracking->track_no);
			//初始化为0
			if ($aTracking->status == Tracking::getSysStatus("查询不到")){
				if (!isset($updated_order['Fail'][$code_format]))
					$updated_order['Fail'][$code_format] = 0;
				$updated_order['Fail'][$code_format] ++;
			}
				
			if (!in_array($aTracking->status, [Tracking::getSysStatus("查询不到"),Tracking::getSysStatus("查询等候中") ,
							Tracking::getSysStatus("延迟查询") ,Tracking::getSysStatus("买家已确认") ,Tracking::getSysStatus("无法交运"),
							Tracking::getSysStatus("无挂号"), Tracking::getSysStatus("过期物流号") ])    ){
				if (!isset($updated_order['Success'][$code_format]))
					$updated_order['Success'][$code_format] = 0;
				$updated_order['Success'][$code_format] ++;
			}
		}//end of each Tracking
		
		$result = array('created'=>$created_order,'updated'=>$updated_order);
		
		//step 3, 统计今日未完成的 渠道地分布，以及渠道分布，范围包括不是 complete state 的并且不是 no_info 的
		$TrackingAll = Tracking::find()
					->select("carrier_type,count(1) as cc")
					->where(" state <>'".Tracking::getSysState("已完成")."' or status ='".Tracking::getSysStatus("成功签收")."'")
					->andWhere(" status <>'".Tracking::getSysStatus("查询不到")."'")
					->andWhere(" date(update_time)='$forDate'")
					->asArray()->groupBy("carrier_type")->all();
		
		$carrier_nation_distribute['carrier'] =array();
		foreach ($TrackingAll as $aTracking){
			$carrier_nation_distribute['carrier'][$aTracking['carrier_type'].""] = $aTracking['cc'];
		}

		//step 4, 统计今日未完成的 渠道地分布，以及到达国家分布，范围包括不是 complete state 的并且不是 no_info 的
		$TrackingAll = Tracking::find()
					->select("to_nation,count(1) as cc")
					->where(" state <>'".Tracking::getSysState("已完成")."' or status ='".Tracking::getSysStatus("成功签收")."'")
					->andWhere(" status <>'".Tracking::getSysStatus("查询不到")."'")
					->andWhere(" date(update_time)='$forDate'")
					->asArray()->groupBy("to_nation")->all();
		
		$carrier_nation_distribute['to_nation'] =array();
		foreach ($TrackingAll as $aTracking){
			$carrier_nation_distribute['to_nation'][$aTracking['to_nation'].""] = $aTracking['cc'];
		}
		
		//step 5, check how many is still pending, not complete
		$sql = "select count(1) from lt_tracking where status not in ('rejected') and state not in ('complete','unshipped') ";
		$command = Yii::$app->subdb->createCommand($sql);
		$os_count = $command->queryScalar();
		$carrier_nation_distribute['os_count'] = $os_count; 

		self::setTrackerTempDataToRedis("format_distribute_$forDate",json_encode($result));
		self::setTrackerTempDataToRedis("carrier_nation_distribute_$forDate",json_encode($carrier_nation_distribute));
		
		$result['carrier_nation_distribute'] = $carrier_nation_distribute;
		
		//step 6, 统计这个时刻客户的tracking status 分布
		$sql = "select count(1) as total_count,status from lt_tracking  group by status ";
		$command = Yii::$app->subdb->createCommand($sql);
		$rows = $command->queryAll();
		foreach ($rows as $row){
			$result['status_pie'][$row['status']] = $row['total_count'];
		}
		
		//step 7, 统计input——source的分布 pie
		$sql = "select count(1) as total_count,status,source from lt_tracking  group by source,status ";
		$command = Yii::$app->subdb->createCommand($sql);
		$rows = $command->queryAll();
		foreach ($rows as $row){
			$result['source_pie'][$row['source']][$row['status']] = $row['total_count'];
		}
		
		//step 8, 统计无法交运有多少条
		$sql = "select count(1) as total_count  from lt_tracking  where state='".Tracking::getSysState("无法交运")."'
					 and create_time >='".date('Y-m-d H:i:s',strtotime('-30 days'))."'";
		$command = Yii::$app->subdb->createCommand($sql);
		$result['Unshipped']['Days30_Count']  = $command->queryScalar();
		 
		$sql = "select count(1) as total_count  from lt_tracking  where state='".Tracking::getSysState("无法交运")."'
					 and create_time >='".date('Y-m-d H:i:s',strtotime('-20 days'))."'";
		$command = Yii::$app->subdb->createCommand($sql);
		$result['Unshipped']['Days20_Count']  = $command->queryScalar();
		
		$sql = "select count(1) as total_count  from lt_tracking  where state='".Tracking::getSysState("无法交运")."'
					 and create_time >='".date('Y-m-d H:i:s',strtotime('-10 days'))."'";
		$command = Yii::$app->subdb->createCommand($sql);
		$result['Unshipped']['Days10_Count']  = $command->queryScalar();
		
		self::setTrackerTempDataToRedis("Unshipped_pie_$forDate",json_encode($result['Unshipped']));
		
		if (isset($result['status_pie']))
			self::setTrackerTempDataToRedis("status_pie_$forDate",json_encode($result['status_pie']));
		
		}
		
		if ($forApp=='' or $forApp=='RecommendProd'){
			//global $CAHCE;
			//step 8, 商品被展示总数，以及被代开总数，以及当天的 着落叶打开次数
			$recommend_prod_sts = array();
			$result['Recm_prod_perform'] = array();
			$recommend_prod_sts['browse_count'] = 0;//Recommend_prod_browse_count_
			$browse_count_str = self::getTrackerTempDataFromRedis("Recommend_prod_browse_count_$forDate");
			if (!empty($browse_count_str)){
				$Recommend_prod_browse_count = json_decode( $browse_count_str,true);
			}else 
				$Recommend_prod_browse_count = array();

			//get send count
			$sql = "select count(1) as send_count,platform  from  message_api_queue where puid=$puid and status in ('C') and date(`update_time`) ='$forDate'   and content like '%littleboss.17track.net%' group by platform";
			$command1 = Yii::$app->db->createCommand($sql);
			$rows  = $command1->queryAll();
			$sendCount = array();
			foreach ($rows as $row){
				$sendCount[$row['platform']] = $row['send_count'];
			}
			//做以下循环，in case，user的库 cs recommend prod或者prod perform为空，但其实有发信出去
			foreach ($rows as $row){
				if (empty($row['platform']))
					continue;
				$recommend_prod_sts = array();
				$recommend_prod_sts['prod_show_count'] = empty($row['v'])?0:$row['v'];
				$recommend_prod_sts['prod_click_count'] = empty($row['c'])?0:$row['c'];
				$recommend_prod_sts['browse_count'] = empty($Recommend_prod_browse_count[$row['platform']]) ?0:$Recommend_prod_browse_count[$row['platform']];
			
				$recommend_prod_sts['send_count'] = empty($sendCount[$row['platform']]) ?0:$sendCount[$row['platform']];
			
				$result['Recm_prod_perform'][$row['platform']] = $recommend_prod_sts;
			}
			
			//user的库 cs recommend prod或者prod perform 统计
			$sql = "select platform,sum(view_count) as v, sum(click_count) as c from cs_recm_product_perform , cs_recommend_product 
					where product_id = cs_recommend_product.id  and theday='$forDate' ";
			$command = Yii::$app->subdb->createCommand($sql);
			$rows = $command->queryAll();
			foreach ($rows as $row){
				if (empty($row['platform']))
					continue;
				$recommend_prod_sts = array();
				if (!empty($result['Recm_prod_perform'][$row['platform']]))
					$recommend_prod_sts = $result['Recm_prod_perform'][$row['platform']];
				$recommend_prod_sts['prod_show_count'] = empty($row['v'])?0:$row['v'];
				$recommend_prod_sts['prod_click_count'] = empty($row['c'])?0:$row['c'];
				$recommend_prod_sts['browse_count'] = empty($Recommend_prod_browse_count[$row['platform']]) ?0:$Recommend_prod_browse_count[$row['platform']];
				
				$recommend_prod_sts['send_count'] = empty($sendCount[$row['platform']]) ?0:$sendCount[$row['platform']];
				
				$result['Recm_prod_perform'][$row['platform']] = $recommend_prod_sts;
			}
			self::setTrackerTempDataToRedis("Recm_prod_perform_$forDate",json_encode($result['Recm_prod_perform']));
		}
		return $result;
	}
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 对该客户某一天的所有 物流号，是否成功进行分析统计，结果写到该客户的 ut_configData 中
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param  forDate               日期，一般是当天或者昨天
	 * @param  puid			         puid
	 +---------------------------------------------------------------------------------------------
	 * @return						Array ( [created] => Array ( [Success] => Array ( [********************] => 1 [##*********##] => 12 [**************#] => 7 [**********] => 6 [*********] => 5 [***************] => 1 [**************] => 2 [**********************] => 4 [*##***************] => 1 ) ) [updated] => Array ( ) )
	 *
	 * @invoking
	 *
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		yzq		2015/3/2        		初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	public static function generateProdSalesReportForPuid($puid, $forApp=''){
		global $CAHCE;
		$result = array();
		if (empty($forDate))
			$forDate = date('Y-m-d');
	
		if (empty($puid)){
			$puid = \Yii::$app->subdb->getCurrentPuid();
		}
	
 
		//15 days before YS2 
	    $fromTime =strtotime('-15 days');
		
		if ($forApp=='' or $forApp=='RecommendProd'){
			//global $CAHCE;
			$recommend_prod_sts = array();
			$result['prodSales'] = array();

			//get send count
			$sql = "
					SELECT  order_source,platform_sku,sum(ordered_quantity) as qty, product_name, max(price) as price1, 
					photo_primary,product_url  FROM `od_order_v2` a, od_order_item_v2 b 
					WHERE a.`order_id` = b.order_id and order_source_create_time > $fromTime 
					and (consignee_country_code like 'FR' or consignee_country like 'FR')
					group by  order_source,platform_sku, 
					 product_name, photo_primary ,product_url order by sum(ordered_quantity)  desc
					"; //and (consignee_country_code like 'FR' or consignee_country like 'FR')
			//echo "for $puid, query this $sql \n";
			$command1 = Yii::$app->subdb->createCommand($sql);
			$rows  = $command1->queryAll();
		    $i = 0;
		    
		    $insertSQL="INSERT INTO `recprod` (`id`, puid, `order_source`, `platform_sku`, `qty`, `product_name`,
		    		 `price`, `photo_primary`, `product_url`) VALUES
						 ";
			//做以下循环，in case，user的库 cs recommend prod或者prod perform为空，但其实有发信出去
			foreach ($rows as $row){
				if (    empty($row['order_source']) or $row['price1'] < 4)
					continue;
			    
			 
				
				$insertSQL .= ($i>0?",":'')."(NULL, $puid,'".self::removeYinHao($row['order_source'])."', 
						    '".self::removeYinHao($row['platform_sku'])."',
						    '".self::removeYinHao($row['qty'])."',
						    '".self::removeYinHao($row['product_name'])."',
						    '".self::removeYinHao($row['price1'])."', 
						    '".self::removeYinHao($row['photo_primary'])."',
						    '".self::removeYinHao($row['product_url'])."')";
				
				$i ++;
				if ($i > 10) break;
			}
			
			if ($i > 0){
				//echo "for $puid, insert this $insertSQL \n";
				$command = Yii::$app->db_queue->createCommand( $insertSQL );
				$affectRows = $command->execute();
			}
		}
		return $result;
	}
		
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 对所有客户某一天的所有 物流号，是否成功进行分析统计，结果写到Global的 模块业务数据 中
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param  forDate               日期，一般是当天或者昨天
	 * @param  reports		         array of each puid's report
	 * 									each report like:
	 * 									Array ( [created] => Array ( [Success] => Array ( [********************] => 1 [##*********##] => 12 [**************#] => 7 [**********] => 6 [*********] => 5 [***************] => 1 [**************] => 2 [**********************] => 4 [*##***************] => 1 ) ) [updated] => Array ( ) )
	 +---------------------------------------------------------------------------------------------
	 * @return						result consolidated, like Array ( [created] => Array ( [Success] => Array ( [********************] => 1 [##*********##] => 12 [**************#] => 7 [**********] => 6 [*********] => 5 [***************] => 1 [**************] => 2 [**********************] => 4 [*##***************] => 1 ) ) [updated] => Array ( ) )
	 *
	 * @invoking					
	 *
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		yzq		2015/3/2        		初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	public static function generateConsolidatedReport($forDate,$reports){
		$consolidatedReport = array();
		foreach ($reports as $puid=>$aReport){			 
			self::arrayPlus($aReport, $consolidatedReport);
		}//end of each report
	 
		ConfigHelper::setGlobalConfig("Tracking/csld_format_distribute_$forDate",json_encode($consolidatedReport));
		return $consolidatedReport;
	}
	
	private static function arrayPlus($anArray, &$bigArray){
		foreach ($anArray as $k => $v){
			if (is_array($v)){
				self::arrayPlus($v, $bigArray[$k]);
			}else{
				//when it is number
				if (!isset($bigArray[$k]))
					$bigArray[$k] = 0;
				
				$bigArray[$k] += $v;
			}	
		}
	}
	
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 调用OMS接口，得到OMS在某个时间后更新的 Shipped，Complete的订单
	 * OMS需要每次获取了Shipped，Complete订单或者更新了其运单号后，修改自己的记录的update_time，
	 * 本函数会从某个update_time开始获取之后的，之前的supposed上一次运行已经拿下来了。
	 +---------------------------------------------------------------------------------------------
	
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param  puid                    用户数据库编号
	 +---------------------------------------------------------------------------------------------
	 * @return						array('success'=true,'message'='')
	 *
	 * @invoking					TrackingHelper::copyTrackingFromOmsShippedComplete();
	 * @Call Eagle 1                http://erp.littleboss.com/api/GetOrderList?update_time=2015-2-1 13:51:25&puid=1
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		yzq		2015/2/9				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function copyTrackingFromOmsShippedComplete($puid=''){
		$rtn['message'] = "";
		$rtn['success'] = true;
		$now_str = date('Y-m-d H:i:s');
	
		if (empty($puid))
			$puid = \Yii::$app->subdb->getCurrentPuid();

		/* 这个不能常开
		//conversion for data bug fix.
		$conversion_sql = "update  `od_order_shipped_v2`  set `sync_to_tracker` ='' where sync_to_tracker='Y' 
							and created >=1456884000
							and `tracking_number` not in (select track_no from lt_tracking)";
		$command = Yii::$app->subdb->createCommand($conversion_sql );
		$affectRows = $command->execute();
		*/
		//$message = "向OMS获取puid $puid 的最新订单物流号";
		
		$all_orders = OrderTrackerApiHelper::getShippedOrderListModifiedAfter($puid);
		
		if (empty($all_orders)) $all_orders = array();
		
		$rtn['all_orders'] = $all_orders;
		
		//get all overdue data
		$del_orders =OrderTrackerApiHelper::getOverdueOrderShippedListModifiedAfter($puid);
		foreach($del_orders as &$del_order){
			//echo " order_id='".$del_order['order_id']."' and track_no='".$del_order['tracking_no']."' <br>";
			Tracking::deleteAll(['order_id'=>$del_order['order_id'] , 'track_no'=>$del_order['tracking_no']]);
			$rtn['deleted'][] = ['order_id'=>$del_order['order_id'] , 'track_no'=>$del_order['tracking_no'] ];
			$del_order = [];//release memory
			
		}//end of delete overdue data
		
		unset($del_orders);//release memory
//ystest
		//step 3.5, check if the seller id is unbinded
		$sellerIds = array();
		$sqls = array();
		//step 3.5.1: Load all binded smt and ebay account user ids
		$connection = Yii::$app->db;  
		$command = $connection->createCommand(
				"select selleruserid from saas_ebay_user where uid=$puid " ) ;
		$rows = $command->queryAll();
		foreach ($rows as $aRow ){
			$sellerIds[] = "".$aRow['selleruserid']."";
		//skip this,otherwise user deleted ones will show again.	$sqls [] = "update `od_order_shipped_v2` set `sync_to_tracker`='N'  WHERE `selleruserid`='".$aRow['selleruserid']."' and `tracking_number` not in (select track_no from lt_tracking where seller_id='".$aRow['selleruserid']."')";
		}
		
		$command = $connection->createCommand("select sellerloginid from saas_aliexpress_user where  uid=$puid " ) ;
		$rows = $command->queryAll();
		foreach ($rows as $aRow ){
			$sellerIds[] = "".$aRow['sellerloginid']."";
		//skip this,otherwise user deleted ones will show again.	$sqls [] = "update `od_order_shipped_v2` set `sync_to_tracker`='N'  WHERE `selleruserid`='".$aRow['sellerloginid']."' and `tracking_number` not in (select track_no from lt_tracking where seller_id='".$aRow['sellerloginid']."')";
		}
		$command = $connection->createCommand("select sellerloginid from saas_dhgate_user where  uid=$puid " ) ;
		$rows = $command->queryAll();
		foreach ($rows as $aRow ){
			$sellerIds[] = "".$aRow['sellerloginid']."";
			//skip this,otherwise user deleted ones will show again.	$sqls [] = "update `od_order_shipped_v2` set `sync_to_tracker`='N'  WHERE `selleruserid`='".$aRow['sellerloginid']."' and `tracking_number` not in (select track_no from lt_tracking where seller_id='".$aRow['sellerloginid']."')";
		}	

		$command = $connection->createCommand("select platform_userid from saas_lazada_user where  puid=$puid  " ) ;
		$rows = $command->queryAll();
		foreach ($rows as $aRow ){		
			$sellerIds[] = "".$aRow['platform_userid']."";
		}
		
		$command = $connection->createCommand("select username from saas_cdiscount_user where  uid=$puid " ) ;
		$rows = $command->queryAll();
		foreach ($rows as $aRow ){
			$sellerIds[] = "".$aRow['username']."";		
		}
				
		$command = $connection->createCommand("select store_name from saas_wish_user where  uid=$puid " ) ;
		$rows = $command->queryAll();
		foreach ($rows as $aRow ){
			$sellerIds[] = "".$aRow['store_name']."";
		}
		
		$command = $connection->createCommand("select store_name from saas_ensogo_user where  uid=$puid " ) ;
		$rows = $command->queryAll();
		foreach ($rows as $aRow ){
			$sellerIds[] = "".$aRow['store_name']."";
		}
		
		$command = $connection->createCommand("select merchant_id  from saas_amazon_user where  uid=$puid " ) ;
		$rows = $command->queryAll();
		foreach ($rows as $aRow ){
			$sellerIds[] = "".  str_replace("'","\'",$aRow['merchant_id'])  ."";
		}
		
		$command = $connection->createCommand("select username from saas_priceminister_user where  uid=$puid  " ) ;
		$rows = $command->queryAll();
		foreach ($rows as $aRow ){
			$sellerIds[] = "".str_replace("'","\'",$aRow['username'])  ."";
		}
		
		$command = $connection->createCommand("select store_name from saas_bonanza_user where  uid=$puid  " ) ;
		$rows = $command->queryAll();
		foreach ($rows as $aRow ){
			$sellerIds[] = "".str_replace("'","\'",$aRow['store_name'])  ."";
		}
		
		foreach ($sqls as $sql){
			$command = Yii::$app->subdb->createCommand($sql );
			$affectRows = $command->execute();
		}
		//ystest
		 
		
		//step 4, for each order, get its tracking number and add to tracking in this module
		$insertedCount = 0;
		
		if (!empty($all_orders) and is_array($all_orders))
		foreach ($all_orders as $anOrder){
			if (empty($anOrder['tracking_no']))
				continue;
			$rtn['inserted'][] = ['tracking no'=>$anOrder['tracking_no'] ,"order id"=>$anOrder['order_id'] ];
			//step 4.1 , 如果是解绑的账号，不要copy到tracker
			if (! in_array($anOrder['selleruserid'], $sellerIds))
				continue;
			
			$aTracking = array();
			$return_no = array();
			$aTracking['track_no'] = $anOrder['tracking_no'];
			$aTracking['order_id'] = $anOrder['order_id'];
			$aTracking['seller_id'] = $anOrder['selleruserid'];
			$aTracking['platform'] = strtolower( $anOrder['order_source'] );	
			$aTracking['ship_by'] = $anOrder['carrier_name']. (empty($anOrder['carrier'])?"":$anOrder['carrier']);
			$aTracking['ship_out_date'] = date('Y-m-d H:i:s',$anOrder['paid_time']);
			if (empty($aTracking['ship_out_date'] ) or $aTracking['ship_out_date'] <'1990-01-01')
				$aTracking['ship_out_date'] = date('Y-m-d',time());
			
			//$aTracking['paid_time'] = date('Y-m-d H:i:s',$anOrder['paid_time']);
			$addi_info['consignee_country_code'] = $anOrder['consignee_country_code'];
			$addi_info['carrier_name'] = $anOrder['carrier_name'];
			$addi_info['shipping_method_code'] = $anOrder['shipping_method_code'];
			
			if (!empty($anOrder['return_no']))
				$return_no = unserialize($anOrder['return_no']);
			
			if (!empty($return_no['TrackingNo']))
				$addi_info['return_no'] = $return_no['TrackingNo'];//这个是物流商的服务单号，例如顺丰需要用这个来查询tracking
			
			//记录order的付款时间，用来展示方便
			if (!empty($anOrder['paid_time']))
				$addi_info['order_paid_time'] = $anOrder['paid_time'];
			
			$aTracking['addi_info'] = json_encode($addi_info,true);
			
			//这个只是把new tracking 放进去缓存，还没有提交到DB
			$rtn1 = self::addTracking($aTracking,"O");
			if (!$rtn1['success']){
				$message = "Failed to insert tracking for $puid - ".$aTracking['track_no'];
				$rtn['message'] .= $message;
				\Yii::info(['Tracking',__CLASS__,__FUNCTION__,'Background',$message],"edb\global");			
			}else 
				$insertedCount ++;
			
		}//end of each order
		
		//call this to put all Tracking into DB
		self::postTrackingBufferToDb();
		
		//\Yii::info(['Tracking',__CLASS__,__FUNCTION__,'Background',"Copy from OMS for $puid, got $insertedCount" ],"edb\global");
		//step 5, mark the last order retrieve time as this shot
		//update the last retrieve time only when we really got result, if empty returned or failed connetion, do not update, so that next run will retry this time frame
		if (!empty($all_orders) and is_array($all_orders) and count($all_orders)>0){
			self::setTrackerTempDataToRedis("last_retrieve_shipped_order_time",$now_str);
			
		 
			$count = self::getTrackerTempDataFromRedis(date('Y-m-d')."_inserted");
			if (empty($count))
				$count = 0;
			
			$count += $insertedCount;
			self::setTrackerTempDataToRedis(date('Y-m-d')."_inserted", $count);
			
			//delete yesterday record
			self::delTrackerTempDataToRedis(date('Y-m-d',strtotime('-1 day'))."_inserted");
			
			$rtn['count'] = $insertedCount;
		}else
			$rtn['count'] = 0;
		
		//force update the top menu statistics
		self::setTrackerTempDataToRedis("left_menu_statistics", json_encode(array()));			
		self::setTrackerTempDataToRedis("top_menu_statistics", json_encode(array()));

		return $rtn;
	}
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 调用OMS接口，某个Order的详细信息
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param  track_no             物流编号
	 +---------------------------------------------------------------------------------------------
	 * @return				array('success'=true,'message'='',order='')
	 *
	 * @invoking			TrackingHelper::getOrderDetailFromOMSByTrackNo();
	 * @Call Eagle 1        http://erp.littleboss.com/api/GetOrderDetail?track_no=RG234234232CN&puid=1
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		yzq		2015/2/9				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function getOrderDetailFromOMSByTrackNo($track_no){
		$detail = OrderTrackerApiHelper::getOrderDetailByTrackNo($track_no);
		$rtn = ['success'=>true , 'message'=>'' , 'order'=>$detail , 'url'=>''];
		return $rtn;
		
		/* test kh  */
		/*
		if (isset(\Yii::$app->params["currentEnv"]) and \Yii::$app->params["currentEnv"]=="test" ){
			$DUMP_OMS_DATA =  '{"order_id":"00000037852","order_status":"500","pay_status":"0","order_source_status":"WAIT_BUYER_ACCEPT_GOODS","order_manual_id":"0","is_manual_order":"0","shipping_status":"0","exception_status":"0","order_source":"aliexpress","order_type":"","order_source_order_id":"67319632656486","order_source_site_id":"","selleruserid":"cn118912741","saas_platform_user_id":"0","order_source_srn":"0","customer_id":"26693","source_buyer_user_id":"cr1094836136","order_source_shipping_method":"","order_source_create_time":"1431953581","subtotal":"11.37","shipping_cost":"0.00","discount_amount":"0.00","grand_total":"11.37","returned_total":"0.00","price_adjustment":"0.00","currency":"USD","consignee":"Dania Espinoza","consignee_postal_code":"08805","consignee_phone":"1 732-7898222","consignee_mobile":"","consignee_email":"macha.89@hotmail.com","consignee_company":"","consignee_country":"United States","consignee_country_code":"US","consignee_city":"Boun Brook","consignee_province":"New Jersey","consignee_district":"","consignee_county":"","consignee_address_line1":"302 East","consignee_address_line2":"","consignee_address_line3":"","default_warehouse_id":"0","default_carrier_code":"","default_shipping_method_code":"","paid_time":"1431953722","delivery_time":"1432486623","create_time":"1433367347","update_time":"1434400045","user_message":"","carrier_type":"0","hassendinvoice":"0","seller_commenttype":"","seller_commenttext":"","status_dispute":"0","is_feedback":"0","rule_id":null,"customer_number":null,"carrier_step":"0","is_print_picking":"0","print_picking_operator":null,"print_picking_time":null,"is_print_distribution":"0","print_distribution_operator":null,"print_distribution_time":null,"is_print_carrier":"0","print_carrier_operator":null,"printtime":"0","delivery_status":"0","items":[{"order_item_id":"59522","order_id":"00000037852","order_source_srn":"0","order_source_order_item_id":"59556","source_item_id":"","sku":"AW-SB-1128","product_name":"New Fashion Floral Flower GENEVA Watch GARDEN BEAUTY BRACELET WATCH Women Dress Watches Quartz Wristwatch Watches AW-SB-1128","photo_primary":"http:\/\/g02.a.alicdn.com\/kf\/HTB1IwuDGFXXXXb_XVXXq6xXFXXXx.jpg_50x50.jpg","shipping_price":"0.00","shipping_discount":"0.00","price":"3.79","promotion_discount":"0.00","ordered_quantity":"3","quantity":"3","sent_quantity":"0","packed_quantity":"0","returned_quantity":"0","invoice_requirement":"","buyer_selected_invoice_category":"","invoice_title":"","invoice_information":"","remark":null,"create_time":"1433367346","update_time":"1433367346","platform_sku":"AW-SB-1128","is_bundle":"0","bdsku":""}]}';
			return ['success'=>true,'order'=>json_decode($DUMP_OMS_DATA,true)];
		}

//		\Yii::info(['Tracking',__CLASS__,__FUNCTION__,'Online',"Try to load oms for ship out date ".$track_no ],"edb\global");
		$puid = \Yii::$app->subdb->getCurrentPuid();
		$rtn['message'] = "";
		$rtn['success'] = true;
		$rtn['order'] = array();
		$now_str = date('Y-m-d H:i:s');

		$EAGLE_1_API_URL = "https://erp.littleboss.com/api/GetOrderDetail?token=tyhedfgS823_E2348,DFdgy&track_no=@track_no&puid=$puid";
		$target_url = str_replace("@track_no",$track_no,$EAGLE_1_API_URL);
	 	
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, $target_url);
		curl_setopt($curl, CURLOPT_HEADER, 0);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);//这个是重点。
		$resultStr = curl_exec($curl);
		//$resultStr = curl_getinfo($curl);
		curl_close($curl);
		
		$rtn['order'] = json_decode($resultStr,true);	
		$rtn['url']=$target_url;
		return $rtn;
		*/
	}
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 校验日期格式是否正确
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param string $date 日期
	 * @param string $formats 需要检验的格式数组
	 +---------------------------------------------------------------------------------------------
	 * @return boolean
	 *
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2015/2/14				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function checkDateIsValid($date, $formats = array("Y-m-d", "Y/m/d")) {
		$unixTime = strtotime($date);
		if (!$unixTime) { //strtotime转换不对，日期格式显然不对。
			return false;
		}else{
			return true;
		}
		//暂时不校验日期的有效性
		//校验日期的有效性，只要满足其中一个格式就OK
		foreach ($formats as $format) {
			if (date($format, $unixTime) == $date) {
				return true;
			}
		}
	
		return false;
	}//end of checkDateIsValid

	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 智能生成 手工录入数据 字段顺序
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param array $data 前台用户从excel复制录入的数据
	 * 				[
	 * 					'0'=> ['4px', '2015-02-14','RXXXXCN' , 'OD001'] ,......
	 * 				]
	 +---------------------------------------------------------------------------------------------
	 * @return array	$result ['success'] boolean
	 * 					$result ['message'] string 运行结果的提示信息
	 * 					$result ['ImportDataFieldMapping']  array 用户前台从excel复制录入的数据识别col对应数据库的字段
	 *
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2015/2/14				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function checkManualImportFormat($data){
		$result ['success'] = true;
		$result ['message'] = '';
		$result ['ImportData'] = [];
		
		if (! is_array($data)){
			$result ['success'] = false;
			$result ['message'] = TranslateHelper::t('该格式无效');
			return $result;
		}
		
		//1.判断是否空数组 
		
		//空数组 ， 直接跳过
		if (count($data) == 0){
			$result ['success'] = false;
			$result ['message'] = TranslateHelper::t('该格式无效');
			return $result;
		}
		
		//2.判断列数是否为一列
		foreach($data as $onetrack){
			//空数组 ， 直接跳过
			if (count($onetrack) == 0){
				$result ['success'] = false;
				$result ['message'] = TranslateHelper::t('该格式无效');
				return $result;
			}
			
			// 只有一列表示是物流号
			if (count($onetrack)==1){
				$fieldArr['track_no'] = 0;
				$result ['ImportDataFieldMapping'] = $fieldArr;
				return $result;
			}
		}
		

		$fieldArr = [];
		// 将各列的数据 分成不同的数组 用于判断是否有重复值
		foreach($data as $onetrack){
			//分割成4个array
			for($i = 0;$i<count($onetrack);$i++){
				
				$sortList[$i][] =trim($onetrack[$i]);
			}
		
		}
		 
		//3.列数大于一列
		 
		//统计重复值 出现的次数
		
		$ShipByList = []; // 所有递送公司的数组 
		//$ShipByResult = Yii::$app->get('subdb')->createCommand("select distinct ship_by from lt_tracking where ifnull(ship_by,'')<> ''")->queryAll();
		$ShipByList = json_decode (self::getTrackerTempDataFromRedis("using_carriers" ),true);
		/*
		foreach($ShipByResult as $tmprow){
			$ShipByList[] =  $tmprow['ship_by']; 
		}
		*/
		
		$repeatColumn = []; //记录重复列的数组 
		
		for($i = 0;$i<count($sortList);$i++):
			//删除重复 的值 
			$tmpNoRepeat[$i] = array_unique($sortList[$i]);
			//检查是否有重复值
			if (count($tmpNoRepeat[$i]) < count($sortList[$i])){
				$repeatColumn[] = $i;
			}
			
			foreach($sortList[$i] as $value){
				if (trim($value)=="") continue;
				//日期类型检查
				if (self::checkDateIsValid($value)):
					//$fieldArr[$i] = 'ship_out_date';
					$fieldArr['ship_out_date'] = $i;
					break;
				endif;
				
				//数字类型为运费
				if (is_numeric($value)):
					//$fieldArr[$i] = 'delivery_fee';
					$fieldArr['delivery_fee'] = $i;
					break;
				endif;
				 
				//出现过的的快递方式
				if (in_array($value, $ShipByList)):
					//$fieldArr[$i] = 'ship_by';
					$fieldArr['ship_by'] = $i;
					break;
				endif;
				
				//检查是否快递单号
				unset($is_track_no);
				
				$is_track_no = CarrierTypeOfTrackNumber::checkExpressOftrackingNo($value);
				if (! empty($is_track_no)){
					$fieldArr['track_no'] = $i;
					break;
				}
				
			}
		
			//$sort_result[$i] = array_count_values($sortList[$i]);
		endfor;
		
		//根据重复的column 比较出ship_by
		
		/******************            上面是数据检测, 下面是智能分析                           *****************/
		
		//--- 重复的column数据 减去 确认了的($fieldArr) 的差值 就是未确认, 而且重复的column
		$repeat_diff_result =  array_diff($repeatColumn, $fieldArr);
		
		/*	
		 * 很遗憾 , 数据检测过后还不能识别数组的角色 , 则会做一次"智能"识别
		 * 分析方案:
		 * 		步骤1: 处理未知重复的列 :
		 * 			处理方法 :A 只有一列重复 , 并物流方法没有设置  , 识别为物流商 ;
		 * 					B 多于一列则表示无法识别 , 剩下一个非物流方法重复column , 或者是多个重复column , 说明 订单号可能重复;
		 * 		步骤2: 处理不重复的列:
		 * 			结果 :A 剩下一列 
		 * 						1.track no 还没有检测出来 , 则识别为track no 
		 * 						2.track no 已经识别  还有一个不重复的列  
		 * 							处理方法: 1.假如 多于1行数据   则优先认识是订单号 , 因为物流商重复的机率大
		 * 									2.假如 等于1行数据  则无法识别 
		 * 				B 剩下两列 
		 * 						1.	track no 还没有检测出来 
		 * 							处理方法:1.只有物流商(ship_by)识别的情况下, 自动判断前者是track no , 后者为order id 
		 * 						2.  track no 已经识别 
		 * 							处理方法: 物流商和订单号的顺序无法 识别 , 返回无法识别	
		 * 				C 剩下三列 
		 * 						1.	track no 还没有检测出来 
		 * 							处理方法:1.存在快递公司则无法识别
		 * 								   2.快递公司不存在, 默认认为物流为第一 ,快递单号第二, 订单号第三
		 * 						2.  track no 已经识别 
		 * 							处理方法:1. 无法识别 ,
		 * 				D 大于三列 	
		 * 						1.	track no 还没有检测出来 
		 * 							处理方法:1. 无法识别  
		 * 						2.  track no 已经识别 
		 * 							处理方法:1. 无法识别 
		 * 						所以直接 返回无法 识别	
		 * 
		 */ 
		
		
		// 还存在未知 的重复column 
		if (isset($repeat_diff_result) ){
			// 步骤1: 处理未知重复的列  :处理方法 只有一列重复的话为识别为物流商 , 多于一列则表示无法识别
			if (count($repeat_diff_result)>0){
				//步骤1-A 只有一列重复 , 并物流方法没有设置  , 识别为物流商 
				if (count($repeat_diff_result) == 1 && empty($fieldArr['ship_by'])){
					$fieldArr['ship_by'] = current($repeat_diff_result);
				}else{
					//步骤1-B剩下一个非物流方法重复column , 或者是多个重复column , 说明 订单号可能重复
					$result ['success'] = false;
					$result ['message'] = TranslateHelper::t(' 订单号可能重复 或者 无法自动识别格式,请使用excel导入');
					return $result;
				}
			}
		}
		
		//步骤2: 处理不重复的列:
		
		//余下的column继续检查 
		$rest_count = count($sortList)-count($fieldArr);
		//交换数组中的键和值。
		$tmpfieldArr = array_flip($fieldArr);
		//只有一个字符串 
		if ($rest_count == 1 ){
			//步骤2-A-1: 剩下一列 不重复的列 并 track no 还没有检测出来 , 则识别为track no 
			if ((! isset($fieldArr['track_no']) )){
				for($i = 0;$i<count($sortList);$i++):
				if (!array_key_exists($i,$tmpfieldArr)):
				$fieldArr['track_no'] = $i;
				break;
				endif;
				endfor;
					
				$result ['ImportDataFieldMapping'] = $fieldArr;
				return $result;
			}else{
				//步骤2-A-2 track no 已经识别  还有一个不重复的列   (假如 该列多于一个的情况下则优先认识是订单号 , 因为物流商重复的机率大)
				$existTrackNo_restColumn =  array_diff(self::$EXCEL_COLUMN_MAPPING , $tmpfieldArr);
				//由于 本来就只剩下一个column 所以 不用做其他判断了
				if (!empty ($existTrackNo_restColumn)){
					//找出 剩下的column index
					for($i = 0;$i<count($sortList);$i++):
						if (!array_key_exists($i,$tmpfieldArr)):
							if (count($sortList[$i])>1){
								//步骤2-A-2-1 假如 该列多于一个的情况下则优先认识是订单号 , 因为物流商重复的机率大
								$fieldArr['order_id'] = $i;
								break;
							}else{
								//步骤2-A-2-2 只有一行数据的话 ,则无法别 
								$result ['success'] = false;
								$result ['message'] = TranslateHelper::t('无法自动识别格式,请使用excel导入');
								return $result;
							}
						endif;
					endfor;
				}else{
					// 理论上这里不会进来 , 不过安全起见 加上
					$result ['success'] = false;
					$result ['message'] = TranslateHelper::t('无法自动识别格式,请使用excel导入');
					return $result;
				}
				
			}
			
		
		}elseif ($rest_count == 2 ){
			//步骤2-B-1 track no 还没有检测出来
			if ((! isset($fieldArr['track_no']) )){
				
				if (isset($fieldArr['ship_by'])){
					//已知ship by
					for($i = 0;$i<count($sortList);$i++):
					if (!array_key_exists($i,$tmpfieldArr)):
				
					if(!isset($fieldArr['track_no']))
						$fieldArr['track_no'] = $i;
					else
						$fieldArr['order_id'] = $i;
						
					//交换数组中的键和值。
					$tmpfieldArr = array_flip($fieldArr);
					endif;
					endfor;
				}else{
					// 未知道ship by 有两种情况 1.初次使用或者快递公司资料不完善,2没有输入快递公司
					$result ['success'] = false;
					$result ['message'] = TranslateHelper::t('无法自动识别格式,请使用excel导入');
					return $result;
				}
			}else{
				//步骤2-B-2 track no 已经识别 
				$result ['success'] = false;
				$result ['message'] = TranslateHelper::t('无法自动识别格式,请使用excel导入');
				return $result;
			}
				
		}elseif ($rest_count == 3  ){
			//步骤2-C-1 track no 还没有检测出来 
			if ((! isset($fieldArr['track_no']) )){
				//等于于3列的情况
				if (isset($fieldArr['ship_by'])){
					//存在快递公司则无法识别
					$result ['success'] = false;
					$result ['message'] = TranslateHelper::t('无法自动识别格式,请使用excel导入');
					return $result;
				}else{
					//不存在, 物流为第一 ,快递单号第二, 订单号第三
					for($i = 0;$i<count($sortList);$i++):
					if (!array_key_exists($i,$tmpfieldArr)):
				
					if (!isset($fieldArr['ship_by'])){
						$fieldArr['ship_by'] = $i;
						$tmpfieldArr = array_flip($fieldArr);
						continue;
					}
						
					if(!isset($fieldArr['track_no']))
						$fieldArr['track_no'] = $i;
					else
						$fieldArr['order_id'] = $i;
				
					//交换数组中的键和值。
					$tmpfieldArr = array_flip($fieldArr);
					endif;
					endfor;
						
				}
			}else{
				//步骤2-C-2
				$result ['success'] = false;
				$result ['message'] = TranslateHelper::t('无法自动识别格式,请使用excel导入');
			}
				
		}elseif($rest_count > 3  ){
			//步骤2-D-1
			if ((! isset($fieldArr['track_no']) )){
				
				//多于3列的情况
				$result ['success'] = false;
				$result ['message'] = TranslateHelper::t('无法自动识别格式,请使用excel导入');
				return $result;
			}else{
				//步骤2-D-2
				$result ['success'] = false;
				$result ['message'] = TranslateHelper::t('无法自动识别格式,请使用excel导入');
				return $result;
			}
			
		}
		
		$result ['ImportDataFieldMapping'] = $fieldArr;
		return $result;
	}//end of checkManualImportFormat
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 生成 备注 的HTML 代码  , 为方便调用 维护 , 又不想开碎片化的view代码 , 所以封装到一个function 中
	 +---------------------------------------------------------------------------------------------
	 * @access static 
	 +---------------------------------------------------------------------------------------------
	 * @param string $remark  
	 * @param string $sort  desc 倒序显示 (default) , asc 为顺序显示
	 +---------------------------------------------------------------------------------------------
	 * @return array	remark HTML STR
	 *
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2015/2/15				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function generateRemarkHTML($remark , $sort = 'desc'){
		//检查参数是否需要json decode
		if (is_array($remark)){
			$RemarkList = $remark;
		}else{
			$RemarkList = json_decode($remark,true);
		}
		
		if (!empty($RemarkList)){
			if (strtolower($sort)== 'desc' )
				$reSortRemarklist = $reSortRemarklist = array_reverse($RemarkList);
			else
				$reSortRemarklist = $RemarkList;
		}else $reSortRemarklist = [];
		
		
		$result = "<section>";
			foreach($reSortRemarklist as $oneRemark):
			//<dt><small>".$oneRemark['who']."</small> </dt>
			$result .="
				<dl>
					<dt><small><time>".$oneRemark['when']."</time></small></dt>
					<dd><small>".nl2br($oneRemark['what'])."</small></dd>
				</dl>";
			endforeach;
			$result .= "</section>"; 
		return $result;
	}//end of generateRemarkHTML
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 获取 物流信息 所有事件
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param array $track no  可是一个,也可以是多个 ,注意一个也需要使用数组格式 
	 * @param array $langList  = [['123'=>'zh-cn']] 快递单号与语言对应的关系
	 +---------------------------------------------------------------------------------------------
	 * @return array	$result [$track no] HTML STR
	 *
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2015/2/15				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function generateTrackingEventHTML($TrackingList,$langList=[],$is_vip=false){
		
		$all_events_str = [];
		$translateBtn = "";
		$platFormTitle = "";
		
		if (empty($toLang))
			$tolang = TranslateHelper::getCurrentLanguague();
		
		foreach($TrackingList as $track_no):
		if ( !empty($langList[$track_no]))
			$lang = $langList[$track_no];
		else 
			$lang = "";
		$model = Tracking::find()->andWhere("track_no=:track_no",array(":track_no"=>$track_no))->one();
		
		//空数据 跳过
		if (empty($model)) continue;
		//生成 物流事件
		$CarrierTypeStr='';
		//查询中  carrier_type 也等于0  , 但不是全球邮政
		if (isset($model->carrier_type) && ! in_array(strtolower($model->status) , ['checking',"查询中","查询等候中"]) ){
			if (isset(CarrierTypeOfTrackNumber::$expressCode[$model->carrier_type]))
				$CarrierTypeStr = " <h6><span style='margin-right:24px;font-size:14px;'></span><span class='text-muted'>(".TranslateHelper::t('通过')."</span>".CarrierTypeOfTrackNumber::$expressCode[$model->carrier_type]."<span class='text-muted'>".TranslateHelper::t('查询到的结果').")</span></h6>";
		}
		
		$tmp_rt = self::getTrackingAllEvents($track_no,$lang);
		
		if (!empty($tmp_rt['allEvents']))
			$all_events = $tmp_rt['allEvents'];
		else 
			$all_events = [];
		
		//平台
		if (! empty($model->platform)){
			$platFormMapping = [
				'ebay'=>'Ebay',
				'sms'=>'速卖通',
			];
			
			if (! empty($platFormMapping[$model->platform])){
				$platFormTitle = "<span class=\"label label-default\" style=\"margin-left: 30px;\">".$platFormMapping[$model->platform]."</span>";
			}
		}
		if (empty($model->to_nation)||  $model->to_nation == '--'){
			$model->to_nation = $model->getConsignee_country_code();
		}
		
		//收件国
		if (! empty($model->to_nation)){
			$to_nation = self::autoSetCountriesNameMapping($model->to_nation);	
		}else{
			$to_nation = self::autoSetCountriesNameMapping('--');
		}
		
		//发件国
		if (! empty($model->from_nation)){
			$from_nation = self::autoSetCountriesNameMapping($model->from_nation);		
		}else {
			$from_nation = self::autoSetCountriesNameMapping('--');
		}
		
		//parlce type is：大包，小包，EMS 
		$parcel_type_label = Tracking::get17TrackParcelTypeLabel($model->parcel_type);
	/*
		if (empty($lang)){
			$translateBtn = "<div class=\"col-md-2\" > <input id='tsl_$track_no' class='btn btn-info' style='font-size: 12px' data-translate-code='' data-loading-text='".TranslateHelper::t('翻译中')."' value='".TranslateHelper::t('翻译成中文')."' type=\"button\"  onclick=\"ListTracking.translateContent('".$track_no."','$tolang',this)\"/>   </div>";
		}else{
			$translateBtn = "<div class=\"col-md-2\" > <input id='tsl_$track_no' class='btn btn-info' data-translate-code='$tolang' data-loading-text='".TranslateHelper::t('翻译中')."'  value='".TranslateHelper::t('显示原文')."' type=\"button\"  onclick=\"ListTracking.translateContent('".$track_no."','',this)\"/>   </div>";
		}
	*/	
		/*
		 * 
		 * 官方网站link的逻辑：先获取 lt_tracking.carrier_type, 
		 * 1. 如果carrier_type >0, url = EXPRESS_ENUM. b, where EXPRESS_ENUM.a = carrier_type .
		 * 2. if carrier_type =0, url = POST_ENUM.x.b,  x = 17TrackNationCode * 10 + parcel_type.
		 *  
		 * */
		$FromOfficialLink =""; // 发件国官网link
		$ToOfficialLink = "";  // 收件国官网link
		if (isset($model->carrier_type)){
			//carrier_type 存在 根据规则生成 官网link
			if ($model->carrier_type > 0 ){
				// carrier_type > 0 是dhl 收件国 与 发件国的官网 是一样
				$FromOfficialLink = Tracking::get17TrackExpressUrlByCode($model->carrier_type);
				$ToOfficialLink = $FromOfficialLink;
			}elseif ($model->carrier_type == 0 ){
				// carrier_type = 0 是ems 收件国 与 发件国的官网 是不一样
				if (!empty($model->from_nation)){
					unset($from_nation_code);
					if (isset($model->parcel_type)){
						if (is_numeric($model->parcel_type)){
							$nation_code = Tracking::get17TrackNationCodeByStandardNationCode($model->from_nation);
							$from_nation_code = $nation_code*10+ $model->parcel_type;
						}
					}
					
					if (!empty($from_nation_code))
						$FromOfficialLink = Tracking::get17TrackNationUrlByCode($from_nation_code);
				}
				
				if (!empty($model->to_nation)){
					unset($to_nation_code);
					if (isset($model->parcel_type)){
						if (is_numeric($model->parcel_type)){
							$nation_code = Tracking::get17TrackNationCodeByStandardNationCode($model->to_nation);
							$to_nation_code = $nation_code*10+ $model->parcel_type;
						}
					}
						
					if (!empty($to_nation_code))
					$ToOfficialLink = Tracking::get17TrackNationUrlByCode($to_nation_code);
				}
				
				
			}
			
		}
		
		
		if (!empty($FromOfficialLink)){
			$fromOfficialLinkHtml = "<small style=\"margin-left: 42px;\"><a href='$FromOfficialLink' target=\"target\">".TranslateHelper::t('查看官方网站')."</a></small>";
		}else{
			$fromOfficialLinkHtml = "";
		}
		
		
		if (!empty($ToOfficialLink)){
			$toOfficialLinkHtml = "<small style=\"margin-left: 42px;\"><a href='$ToOfficialLink' target=\"target\">".TranslateHelper::t('查看官方网站')."</a></small>";
		}else{
			$toOfficialLinkHtml = "";
		} 
		
		
		// 设置 第一行 : 发件国 , 收件国
		/*
		$all_events_str[$track_no] = '<dd>'.
				'<div class="col-md-12 toNation">'. 
						'<h6> <span class="glyphicon glyphicon-gift" aria-hidden="true" style="margin-right: 12px;font-size: 14px;"></span>'.
						'<span class="text-muted">'.TranslateHelper::t('收件国家').
						'</span>'.
						': '.$to_nation.$toOfficialLinkHtml.$platFormTitle.'</h6>'.
				'</div>'.
				$translateBtn.'</dd>';
				*/
		$all_events_str[$track_no] = "";
		
		//获取所有事件
		if (is_array($all_events)){
			foreach($all_events as $anEvent){
				//传递lang 表示需要翻译 , 外语使用base 64 加密 , 中文则不需要 
				if (empty($lang)){
					$anEvent['where'] = base64_decode($anEvent['where']);
					$anEvent['what'] = base64_decode($anEvent['what']);
					//防止这个时间是一个无效的日期，例如 1900 年
					if (!empty($anEvent['when']) and strlen($anEvent['when']) >=10 and substr($anEvent['when'],0,10)<'2014-01-01' )
						$anEvent['when'] = ''; 
					/*
					if (empty($all_events_str[$track_no]))
						$translateBtn = "<div class=\"col-md-2\" > <input id='tsl_$track_no' class='btn btn-info' data-translate-code='' data-loading-text='".TranslateHelper::t('翻译中')."' value='".TranslateHelper::t('翻译成中文')."' type=\"button\"  onclick=\"ListTracking.translateContent('".$track_no."','$tolang',this)\"/>   </div>";
					else 
						$translateBtn = "";
						*/
				}else{
					/*
					if (empty($all_events_str[$track_no]))
						$translateBtn = "<div class=\"col-md-2\" > <input id='tsl_$track_no' class='btn btn-info' data-translate-code='$tolang' data-loading-text='".TranslateHelper::t('翻译中')."'  value='".TranslateHelper::t('显示原文')."' type=\"button\"  onclick=\"ListTracking.translateContent('".$track_no."','',this)\"/>   </div>";
					else
						$translateBtn = "";
						*/
				}
				
				if (!empty($anEvent['type'])){
					$class_nation = $anEvent['type'];
				}
				
				if (empty($className)){
					$className = 'orange_bold';
				}else{
					$className = 'font_normal';
				}
				
				//detail view message
				//$all_events_str[$track_no] .= $anEvent['when'].$anEvent['where'].$anEvent['what'].".<br>";
				
				$all_events_str[$track_no]  .= '<dd>'.
						'<div class="col-md-12 '.$className.'">'.
						'<i class="'.(($className=='orange_bold')?"egicon-arrow-on-yellow":"egicon-arrow-on-gray").'"></i>'.
						'<time '.(($className=='orange_bold')?'style="color: #f0ad4e;" ':'').'>'. $anEvent['when'].'</time>'.
						'<p>'.$anEvent['where'].((empty($anEvent['where']))?"":",").
						$anEvent['what']."</p></div>".
						"</dd>";
			}
		
		}
		
		$all_events_str[$track_no] = "<dl lang='src'>".$all_events_str[$track_no].'</dl>';
		
		//物流号2
		$addi_info = json_decode($model->addi_info,true);
		if(!empty($addi_info['return_no']))
			$abroad_no_str = '<span style="color:blue"> (境外查询号:'.$addi_info['return_no'].') </span>';
		else 
			$abroad_no_str='';
		// 设置 第一行 : 发件国 , 收件国
		$all_events_str[$track_no] = 
				'<div class="toNation">'.
				'<h6> <span class="glyphicon glyphicon-gift" aria-hidden="true" style="margin-right: 12px;font-size: 14px;"></span>'.
				'<span class="text-muted">'.TranslateHelper::t('收件国家').
				'</span>'.
				': '.$to_nation.$abroad_no_str.$toOfficialLinkHtml.$platFormTitle.'</h6>'.
				'</div>' . $all_events_str[$track_no];
		
		// 设置 最后一行 : 发件国 
		$all_events_str[$track_no] .= 
				'<div class="fromNation">'.
					'<h6><span class="glyphicon glyphicon-send" aria-hidden="true" style="margin-right: 12px;font-size: 14px;"></span>'.
					'<span class="text-muted">'.TranslateHelper::t('发件国家').
					'</span>'.
					': '.$from_nation.$fromOfficialLinkHtml.$CarrierTypeStr.'</h6>
				</div>';
		if($is_vip){
			$all_events_str[$track_no] .= '<div class="col-md-12"><span class="text-muted" style="float:left;padding-top:3px;line-height:24px;padding-left: 10px;padding-right: 10px;color:#3c763d;background-color:#dff0d8;border-color:#d6e9c6;">'.TranslateHelper::t('快速翻译').'</span>';
			$all_events_str[$track_no] .= '<button type="button" class="translateBtn_'.$model->id.' translate-btn translate-btn-checked" lang="src" onclick="ListTracking.translateEventsToZh(this,\''.$track_no.'\','.$model->id.')">原始</button>';
			$all_events_str[$track_no] .= '<button type="button" class="translateBtn_'.$model->id.' translate-btn" lang="zh" onclick="ListTracking.translateEventsToZh(this,\''.$track_no.'\','.$model->id.')">中文</button>';
			$all_events_str[$track_no] .= '<button type="button" class="translateBtn_'.$model->id.' translate-btn" lang="en" onclick="ListTracking.translateEventsToZh(this,\''.$track_no.'\','.$model->id.')">英文</button>';
			$all_events_str[$track_no] .= '</div>';
		}
		
		endforeach; //end of each track no list
		
		
		return $all_events_str;
	}//end of generateTrackingEventHTML
	
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 获取 物流信息 所有事件 用于发送给用户
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param array $track no  可是一个,也可以是多个 ,注意一个也需要使用数组格式
	 * @param array $toCountry 快递单号目的地国家代码
	 +---------------------------------------------------------------------------------------------
	 * @return array	$result [$track no] HTML STR
	 *
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2015/2/15				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function generateTrackingEventHTML_forMSG($TrackingList,$langList=[],$formNationStr='Origin Country',$toNationStr='Destination Country',$from_nation,$to_nation){
		 
		$all_events_str = [];
		$translateBtn = "";
		$platFormTitle = "";
	
		if (empty($toLang))
			$tolang = TranslateHelper::getCurrentLanguague();
	
		foreach($TrackingList as $track_no):
		if ( !empty($langList[$track_no]))
			$lang = $langList[$track_no];
		else
			$lang = "";
		$model = Tracking::find()->andWhere("track_no=:track_no",array(":track_no"=>$track_no))->one();
	
		//空数据 跳过
		if (empty($model)) continue;
		//生成 物流事件
	
		$tmp_rt = self::getTrackingAllEvents($track_no,$lang);
	
		if (!empty($tmp_rt['allEvents']))
			$all_events = $tmp_rt['allEvents'];
		else
			$all_events = [];
	
		//平台
		if (! empty($model->platform)){
			$platFormMapping = [
			'ebay'=>'Ebay',
			'sms'=>'速卖通',
			];
				
			if (! empty($platFormMapping[$model->platform])){
				$platFormTitle = "<span class=\"label label-default\" style=\"margin-left: 30px;\">".$platFormMapping[$model->platform]."</span>";
			}
		}
		
		/*
		//收件国
		if (! empty($model->to_nation)){
			$to_nation = self::autoSetCountriesNameMapping($model->to_nation);
		}else{
			$to_nation = self::autoSetCountriesNameMapping('--');
		}
	
		//发件国
		if (! empty($model->from_nation)){
			$from_nation = self::autoSetCountriesNameMapping($model->from_nation);
		}else {
			$from_nation = self::autoSetCountriesNameMapping('--');
		}
		*/
	
		//parlce type is：大包，小包，EMS
		$parcel_type_label = Tracking::get17TrackParcelTypeLabel($model->parcel_type);
		/*
			if (empty($lang)){
		$translateBtn = "<div class=\"col-md-2\" > <input id='tsl_$track_no' class='btn btn-info' style='font-size: 12px' data-translate-code='' data-loading-text='".TranslateHelper::t('翻译中')."' value='".TranslateHelper::t('翻译成中文')."' type=\"button\"  onclick=\"ListTracking.translateContent('".$track_no."','$tolang',this)\"/>   </div>";
		}else{
		$translateBtn = "<div class=\"col-md-2\" > <input id='tsl_$track_no' class='btn btn-info' data-translate-code='$tolang' data-loading-text='".TranslateHelper::t('翻译中')."'  value='".TranslateHelper::t('显示原文')."' type=\"button\"  onclick=\"ListTracking.translateContent('".$track_no."','',this)\"/>   </div>";
		}
		*/
		/*
		 *
		* 官方网站link的逻辑：先获取 lt_tracking.carrier_type,
		* 1. 如果carrier_type >0, url = EXPRESS_ENUM. b, where EXPRESS_ENUM.a = carrier_type .
		* 2. if carrier_type =0, url = POST_ENUM.x.b,  x = 17TrackNationCode * 10 + parcel_type.
		*
		* */
		$FromOfficialLink =""; // 发件国官网link
		$ToOfficialLink = "";  // 收件国官网link
		if (isset($model->carrier_type)){
			//carrier_type 存在 根据规则生成 官网link
			if ($model->carrier_type > 0 ){
				// carrier_type > 0 是dhl 收件国 与 发件国的官网 是一样
				$FromOfficialLink = Tracking::get17TrackExpressUrlByCode($model->carrier_type);
				$ToOfficialLink = $FromOfficialLink;
			}elseif ($model->carrier_type == 0 ){
				// carrier_type = 0 是ems 收件国 与 发件国的官网 是不一样
				if (!empty($model->from_nation)){
					unset($from_nation_code);
					if (isset($model->parcel_type)){
						if (is_numeric($model->parcel_type)){
							$nation_code = Tracking::get17TrackNationCodeByStandardNationCode($model->from_nation);
							$from_nation_code = $nation_code*10+ $model->parcel_type;
						}
					}
						
					if (!empty($from_nation_code))
						$FromOfficialLink = Tracking::get17TrackNationUrlByCode($from_nation_code);
				}
	
				if (!empty($model->to_nation)){
					unset($to_nation_code);
					if (isset($model->parcel_type)){
						if (is_numeric($model->parcel_type)){
							$nation_code = Tracking::get17TrackNationCodeByStandardNationCode($model->to_nation);
							$to_nation_code = $nation_code*10+ $model->parcel_type;
						}
					}
	
					if (!empty($to_nation_code))
						$ToOfficialLink = Tracking::get17TrackNationUrlByCode($to_nation_code);
				}
	
	
			}
				
		}
	
		$TranslateMappings = TrackingMsgHelper::getTranslateMapping();
		$display_lang = TrackingMsgHelper::getToNationLanguage($to_nation);
		if(isset($TranslateMappings[$display_lang]))
			$translateMapping = $TranslateMappings[$display_lang];
		else 
			$translateMapping = $TranslateMappings['EN'];
		if (!empty($FromOfficialLink)){
			$fromOfficialLinkHtml = "<a style=\"margin-left: 30px;\" href='$FromOfficialLink' target=\"target\">".$translateMapping['Go to official website']."</a>";
		}else{
			$fromOfficialLinkHtml = "";
		}
	
	
		if (!empty($ToOfficialLink)){
			$toOfficialLinkHtml = "<a href='$ToOfficialLink' target=\"target\" style=\"margin-left:30px;\">".$translateMapping['Go to official website']."</a>";
		}else{
			$toOfficialLinkHtml = "";
		}
		
	
		// 设置 第一行 : 发件国 , 收件国
		/*
			$all_events_str[$track_no] = '<dd>'.
		'<div class="col-md-12 toNation">'.
		'<h6> <span class="glyphicon glyphicon-gift" aria-hidden="true" style="margin-right: 12px;font-size: 14px;"></span>'.
		'<span class="text-muted">'.TranslateHelper::t('收件国家').
		'</span>'.
		': '.$to_nation.$toOfficialLinkHtml.$platFormTitle.'</h6>'.
		'</div>'.
		$translateBtn.'</dd>';
		*/
		$all_events_str[$track_no] = "";
	
		//获取所有事件
		if (is_array($all_events)){
			$c=count($all_events);
			$index=0;
			foreach($all_events as $anEvent){
				//传递lang 表示需要翻译 , 外语使用base 64 加密 , 中文则不需要
				if (empty($lang)){
					$anEvent['where'] = base64_decode($anEvent['where']);
					$anEvent['what'] = base64_decode($anEvent['what']);
					//防止这个时间是一个无效的日期，例如 1900 年
					if (!empty($anEvent['when']) and strlen($anEvent['when']) >=10 and substr($anEvent['when'],0,10)<'2014-01-01' )
						$anEvent['when'] = '';
					/*
						if (empty($all_events_str[$track_no]))
						$translateBtn = "<div class=\"col-md-2\" > <input id='tsl_$track_no' class='btn btn-info' data-translate-code='' data-loading-text='".TranslateHelper::t('翻译中')."' value='".TranslateHelper::t('翻译成中文')."' type=\"button\"  onclick=\"ListTracking.translateContent('".$track_no."','$tolang',this)\"/>   </div>";
					else
						$translateBtn = "";
					*/
				}else{
					/*
						if (empty($all_events_str[$track_no]))
						$translateBtn = "<div class=\"col-md-2\" > <input id='tsl_$track_no' class='btn btn-info' data-translate-code='$tolang' data-loading-text='".TranslateHelper::t('翻译中')."'  value='".TranslateHelper::t('显示原文')."' type=\"button\"  onclick=\"ListTracking.translateContent('".$track_no."','',this)\"/>   </div>";
					else
						$translateBtn = "";
					*/
				}
	
				if (!empty($anEvent['type'])){
					$class_nation = $anEvent['type'];
				}
	
				if ($index==0){
					$className = 'new';
				}elseif($index==($c-1)){
					$className = 'begin';
				}else{
					$className = '';
				}
				$index++;
				//detail view message
				//$all_events_str[$track_no] .= $anEvent['when'].$anEvent['where'].$anEvent['what'].".<br>";
	
				$all_events_str[$track_no]  .= '<dd class="'.$className.'">'.
						'<i></i>'.
						'<span>'. $anEvent['when'].'</span>'.
						'<p style="margin:0 0 10px !important;">'.$anEvent['where'].((empty($anEvent['where']))?"":",").
						$anEvent['what']."</p>".
						"</dd>";
			}
	
		}
	
		$all_events_str[$track_no] = "<dl class='all_events'>".$all_events_str[$track_no].'</dl>';
	
	
		// 设置 第一行 : 收件国
		$all_events_str[$track_no] =
		'<div class="toNation" style="padding-left:0px;background-color:transparent;">'.
		'<dl><dt>'.$toNationStr.' - '.$to_nation.$toOfficialLinkHtml.'</dt></dl>'.
		/*
		'<h6> <span class="toNationIcon" aria-hidden="true" style="margin-right: 12px;font-size: 14px;"></span>'.
		'<span class="text-muted">'.$toNationStr.
		'</span>'.
		': '.$to_nation.$toOfficialLinkHtml.$platFormTitle.'</h6>'.
		
		 */
		'</div>' .
	
		//  发件国
		'<div class="fromNation" style="padding-left:0px;background-color:transparent;">'.
		'<dl><dt>'.$formNationStr.' - '.$from_nation.$fromOfficialLinkHtml.'</dt></dl>'.
		/*
		'<h6><span class="glyphicon glyphicon-send" aria-hidden="true" style="margin-right: 12px;font-size: 14px;"></span>'.
		'<span class="text-muted">'.$formNationStr.
		'</span>'.
		': '.$from_nation.$fromOfficialLinkHtml.'</h6>
		*/
		'</div>'. $all_events_str[$track_no];
	
		if(true){
			$all_events_str[$track_no] .= '<div class="col-md-12"><span class="text-muted" style="float:left;padding-top:3px;line-height:24px;padding-left: 10px;padding-right: 10px;color:#3c763d;background-color:#dff0d8;border-color:#d6e9c6;">'.TranslateHelper::t('快速翻译').'</span>';
			$all_events_str[$track_no] .= '<button type="button" class="translateBtn_'.$model->id.' translate-btn translate-btn-checked" lang="src" onclick="ListTracking.translateEventsToZh(this,\''.$track_no.'\','.$model->id.')">原始</button>';
			$all_events_str[$track_no] .= '<button type="button" class="translateBtn_'.$model->id.' translate-btn" lang="zh" onclick="ListTracking.translateEventsToZh(this,\''.$track_no.'\','.$model->id.')">中文</button>';
			$all_events_str[$track_no] .= '<button type="button" class="translateBtn_'.$model->id.' translate-btn" lang="en" onclick="ListTracking.translateEventsToZh(this,\''.$track_no.'\','.$model->id.')">英文</button>';
			$all_events_str[$track_no] .= '</div>';
		}
		
		endforeach; //end of each track no list
		return $all_events_str;
	}//end of generateTrackingEventHTML
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 获取 手工 录入 界面 物流信息 详细信息
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param array $track no  可是一个,也可以是多个 ,注意一个也需要使用数组格式
	 *  @param array $langList  = [['123'=>'zh-cn']] 快递单号与语言对应的关系
	 +---------------------------------------------------------------------------------------------
	 * @return array	$result [$track no]  HTML
	 *
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2015/3/3				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static function generateTrackingInfoHTML($TrackingList, $langlist=[]){
		$HTMLStr = [];
		$status_class_mapping = [
			"checking"=>"label label-default",
			"shipping"=>"label label-primary",
			"no_info"=>"label label-default",
			"ship_over_time"=>"label label-danger",
			"arrived_pending_fetch"=>"label label-danger",
			"received"=>"label label-success",
			"rejected"=>"label label-danger"
			
		];
		
		foreach($TrackingList as $track_no):
			if ( !empty($langList[$track_no]))
				$lang = $langList[$track_no];
			else
				$lang = "";
			$model = Tracking::find()->andWhere("track_no=:track_no",array(":track_no"=>$track_no))->one();
			//空数据 跳过
			if (empty($model)) continue;
			$row = $model->attributes;
			
			//设置 全部事件
			$all_events = json_decode($row['all_event'],true);
			if (empty($all_events)) $all_events = array();
			$all_events_str = "";
			$events_str = '';
			
			if (is_array($all_events)){
				foreach($all_events as $anEvent){
					if (empty($lang)){
						$anEvent['where'] = base64_decode($anEvent['where']);
						$anEvent['what'] = base64_decode($anEvent['what']);
					}
					
					//最近事件
					if (empty($events_str)){
						$events_str = $anEvent['when'].'<p>'.$anEvent['where'].((empty($anEvent['where']))?"":",").$anEvent['what'].".</p>";
					}else{
						continue;
					}
				}
			
			}else{
				$events_str = "";
			}
			
			//发件国家
			if (!empty($row['from_nation'])){
				$from_nation = self::autoSetCountriesNameMapping($row['from_nation']);
			}else{
				$from_nation = StandardConst::$COUNTRIES_CODE_NAME_CN['--'];
			}
			//目的国家
			if (!empty($row['to_nation'])){
				$to_nation = self::autoSetCountriesNameMapping($row['to_nation']);
			}else{
				$to_nation = StandardConst::$COUNTRIES_CODE_NAME_CN['--'];
			}
			//carrier type //查询中  carrier_type 也等于0  , 但不是全球邮政
			$CarrierTypeStr = "";
			$parcel_type_label = "";
			if (isset($row['carrier_type'])){
				//根据17track 设置 包裹类型
				if (!empty($model->parcel_type)){
					$parcel_type_label = Tracking::get17TrackParcelTypeLabel($model->parcel_type);
				}
				
				if (isset(CarrierTypeOfTrackNumber::$expressCode[$row['carrier_type']]) && ! in_array(strtolower($row['status']) , ['checking',"查询中","查询等候中"])  )
					$CarrierTypeStr = "(".CarrierTypeOfTrackNumber::$expressCode[$row['carrier_type']].".".$parcel_type_label.")";
			}
			// 根据status 设置 class 
			if (!empty($status_class_mapping[$row['status']]))
				$status_class = $status_class_mapping[$row['status']];
			else 
				$status_class = 'label label-default';
			
			//设置 物流耗时
			$total_days = 0;
			if (isset($row['total_days'])){
				if ($row['total_days']>0)
				$total_days = $row['total_days'];
			}
			
			if (empty($total_days)){
				$time= time();
				$total_days  = ceil(($time-strtotime($row['create_time']))/(24*3600));
			}
			$total_days_html_str = "";
			//正在查找 的不需要计算天数
			if ($row['status'] != "checking")
			$total_days_html_str = "<br><p style=\"margin: 5px 0 0px 25px;\"><small>".$total_days.TranslateHelper::t('天')."</small></p>";
			 
			$status_class .= " status_label";
			$HTMLStr [$row['track_no']] =   "
				<td>".$row['order_id']."</td>
				<td>".$row['track_no'].(empty($row['track_no'])?"":"<br>").$CarrierTypeStr."</td>
				<td>".$from_nation."</td>
				<td>".$to_nation."</td>
				<td>".$events_str."</td>
				<td nowrap data-status='".$row['status']."'><strong>".Tracking::getChineseStatus($row['status'])."</strong>$total_days_html_str</td>
				<td nowrap><a id='a_".$row['track_no']."'  class=\"btn-qty-memo\" data-track-id='".$row['id']."'  title='".TranslateHelper::t('详细')."'>".'<span class="egicon-eye" aria-hidden="true"></span>'."</a>"
						." <a onclick=\"manual_import.list.showRemarkBox('". $row['track_no'] ."')\" title='".TranslateHelper::t('添加备注')."'>".'<span class="egicon-memo-blue" aria-hidden="true"></span>'."</a>"
						." <a onclick=\"manual_import.list.DelTrack('". $row['track_no'] ."')\" title='".TranslateHelper::t('删除')."'>".'<span class="egicon-trash" aria-hidden="true"></span>'."</a>"
						
						."</td>
			";
		endforeach;
		return $HTMLStr;
	} // end of generateTrackingInfoHTML
	
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 获取 查询记录, 订单跟踪 界面 物流信息 详细信息
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param array $track no  可是一个,也可以是多个 ,注意一个也需要使用数组格式
	 * @param boolean  显示显示订单详情
	 *  @param array $langList  = [['123'=>'zh-cn']] 快递单号与语言对应的关系
	 +---------------------------------------------------------------------------------------------
	 * @return array	$result [$track no]  HTML
	 *
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2015/3/3				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function generateQueryTrackingInfoHTML($oneTracking , $isPlatform = true, $langlist=[]){
		$status_class_mapping = [
		"查询中"  =>"label status_label label-default",
		"查询等候中"=>"label status_label label-default",
		"运输途中"=>"label status_label label-primary",
		"查询不到"=>"label status_label label-default",
		"运输过久"=>"label status_label label-danger",
		"到达待取"=>"label status_label label-danger",
		"成功签收"=>"label status_label label-success",
		"异常退回"=>"label status_label label-danger",
		"买家已确认"=>"label status_label label-success",
		];
		
		$status_qtip_mapping = [
		"查询中"  =>"",
		"查询等候中"=>"",
		"运输途中"=>"tracker_shipping",
		"查询不到"=>"tracker_no_info",
		"运输过久"=>"tracker_ship_over_time",
		"到达待取"=>"tracker_arrived_pending_fetch",
		"成功签收"=>"tracker_complete_parcel",
		"异常退回"=>"tracker_rejected" , 
		"延迟查询"=>"tracker_suspend_parcel",
		"无法交运"=>"tracker_unshipped",
		"无挂号"=>"tracker_unregistered",
		"买家已确认"=>"tracker_platform_confirmed",
		"忽略(不再查询)"=>"",
		];
		
		$model = new Tracking();
		$CarrierTypeStr = "";
		$parcel_type_label = "";
		
		if (!empty($oneTracking['parcel_type'])){
			//根据17track 设置 包裹类型
			if (!empty($oneTracking['parcel_type'])){
				$parcel_type_label = Tracking::get17TrackParcelTypeLabel($oneTracking['parcel_type']);
			}
		}
		// 根据status 设置 class
		if (!empty($status_class_mapping[$oneTracking['status']]))
			$status_class = $status_class_mapping[$oneTracking['status']];
		else
			$status_class = 'label status_label label-default';
		//查询中  carrier_type 也等于0  , 但不是全球邮政
		if (isset($oneTracking['carrier_type']) && ! in_array(strtolower($oneTracking['status']) , ['checking',"查询中","查询等候中"]) ){
			if (isset(CarrierTypeOfTrackNumber::$expressCode[$oneTracking['carrier_type']]))
				$CarrierTypeStr = "<span class='font-color-1'>(".CarrierTypeOfTrackNumber::$expressCode[$oneTracking['carrier_type']].")</span>";
		}
		
		//设置 物流耗时
		$total_days = 0;
		if ( isset($oneTracking['total_days'])){
			if ($oneTracking['total_days']>0)
				$total_days = $oneTracking['total_days'];
		}
			
		if (empty($total_days)){
			$time= time();
			$total_days  = ceil(($time-strtotime($oneTracking['create_time']))/(24*3600));
		}
		
		//$HtmlStr = "<tr id=\"tr_info_".$oneTracking['track_no']."\" track_no=\"".$oneTracking['track_no']."\">";
		$HtmlStr = "<td>";
		
		$HtmlStr .="<input type='checkbox' name='chk_tracking_record' value =".base64_encode($oneTracking['track_no'])." data-track-id='".$oneTracking['id']."' data-order-platform='".$oneTracking['platform']."'>";
	
		if (strtoupper($oneTracking['mark_handled'])=='Y' && (in_array($oneTracking['state'], ['异常' , '无法交运']))) {
			$markHandleStr = '<a title="'.TranslateHelper::t('己处理').'"><span class="egicon-ok-blue"></span></a>';
			$markHandleLink = '';
		}else{
			$markHandleStr = '';
			if (strtoupper($oneTracking['mark_handled'])=='N' && (in_array($oneTracking['state'], ['异常' , '无法交运'])))
				$markHandleLink = " <a onclick=\"ListTracking.MarkOneHndled('". $oneTracking['id'] ."')\" title='".TranslateHelper::t('标记已处理')."'>".'<span class="egicon-ok-blue" aria-hidden="true"></span>'."</a>";
			else 
				$markHandleLink = "";
		}
		if (empty($oneTracking['remark']))
			$imgStr = "";
		else
			$imgStr = '<span style="cursor: pointer;" class="egicon-memo-orange" data-track-id="'.$oneTracking['id'].'" ></span> <div class="div_space_toggle">'.self::generateRemarkHTML($oneTracking['remark']).'</div>';
			
		$msgIconStr = "";
		if ($oneTracking['msg_sent_error'] == 'Y'){
			$ct = MessageHelper::getFailureMessageCount();
			$msgIconStr = '<a style="cursor: pointer;" onclick="StationLetter.showMessageBox(\''.$oneTracking['order_id'].'\''.",'". $oneTracking['track_no'] ."'".',\'history\' ,'.$ct.')"><span class="egicon-envelope-remove"></span></a>';
		}			
		elseif ($oneTracking['msg_sent_error'] == 'C')
			$msgIconStr = '<a style="cursor: pointer;" onclick="StationLetter.showMessageBox(\''.$oneTracking['order_id'].'\''.",'". $oneTracking['track_no'] ."'".',\'history\' , 0)"><span class="egicon-envelope-ok"></span></a>';
		
		//var_dump($oneTracking['msg_sent_error']);
		$TagStr = TrackingTagHelper::generateTagIconHtmlByTrackingId($oneTracking['id']);

		if (!empty($TagStr)){
			$TagStr = "<span class='btn_tag_qtip".(stripos($TagStr,'egicon-flag-gray')?" div_space_toggle":"")."' data-track-id='".$oneTracking['id']."' >$TagStr</span>";
		}
					$HtmlStr .=" </td>".
							"<td><p class='noBottom font-color-1'>".$oneTracking['track_no']."</p>".$imgStr.$msgIconStr.$markHandleStr.$TagStr."</td>".
					"<td class='font-color-2'>".(empty($oneTracking['order_id'])?"<span  qtipkey='tracker_no_order_id'></span>":'<a title="订单详情" onclick="ListTracking.ShowOrderInfo(\''.$oneTracking['track_no'].'\')">'.$oneTracking['order_id']."</a>");
					
					$HtmlStr .= "</td>";
					
					//seller id 
					//$HtmlStr .="<td>".$oneTracking['seller_id']."</td>";
					
					/**/
					if (empty($oneTracking['to_nation']) || $oneTracking['to_nation'] =='--' || $oneTracking['to_nation'] =='未知'){
						$model->attributes = $oneTracking;
						$oneTracking['to_nation'] = $model->getConsignee_country_code();
					}
					
					$toNation = self::autoSetCountriesNameMapping($oneTracking['to_nation']);
					$fromNation = self::autoSetCountriesNameMapping($oneTracking['from_nation']);
					
					if (in_array($oneTracking['status'], ['成功签收']))
						$arrivedClass = "";
					else{
						$arrivedClass = "arrived";
						
					}
					$toNationEn = StandardConst::getNationEnglishNameByCode($oneTracking['to_nation']);
					$all_event = json_decode($oneTracking['all_event'],true);
					
					if (is_array($all_event)){
						foreach( $all_event as &$an_event){
							$an_event['what'] = base64_decode($an_event['what']);
							$an_event['where'] = base64_decode($an_event['where']);
						}
					}
					
					if ( stripos($oneTracking['all_event'],'toNation')>0  || stripos(json_encode($all_event),$toNationEn)>0){
						//有收件国的物流记录则表示到了 收件国  或含 有收件国的关键字 , 反之为发 件国
						$HtmlStr .= "<td><small> <span class='btn_qtip_from_nation font-color-1'>".
							(($oneTracking['from_nation']<>'' and $oneTracking['from_nation']<>'--')?$oneTracking['from_nation']:'')."</span>".
						'<div class="div_space_toggle">'.(($fromNation<>'' and $fromNation<>'--')?$fromNation:'').'</div>'.
						(($oneTracking['from_nation']<>'' and $oneTracking['from_nation']<>'--')?' - ':"").
						"<span class='btn_qtip_to_nation $arrivedClass'>".( ($oneTracking['to_nation']<>'' and $oneTracking['to_nation']<>'--')?$oneTracking['to_nation']:'')."</span>".
						'<div class="div_space_toggle">'.(($toNation<>'' and $toNation<>'--')?$toNation:'').'</div>'."</small></td>";
					}else{
						$HtmlStr .= "<td><small><span class='btn_qtip_from_nation $arrivedClass' >".
							(($oneTracking['from_nation']<>'' and $oneTracking['from_nation']<>'--')?$oneTracking['from_nation']:'')."</span>".'<div class="div_space_toggle">'.(($fromNation<>'' and $fromNation<>'--')?$fromNation:'').'</div>'.(($oneTracking['from_nation']<>'' and $oneTracking['from_nation']<>'--')?' - ':"")."<span  class='btn_qtip_to_nation font-color-1'>".( ($oneTracking['to_nation']<>'' and $oneTracking['to_nation']<>'--')?$oneTracking['to_nation']:'')."</span>".'<div class="div_space_toggle">'.(($toNation<>'' and $toNation<>'--')?$toNation:'').'</div>'."</small></td>";
					}
					
					if ( !empty($oneTracking['addi_info']) ){
						$addi_info = json_decode($oneTracking['addi_info'],true);
						if(empty($addi_info)) $addi_info = [];
					}else 
						$addi_info = [];
					//oms  显示 ship out date 为准 
					if (in_array($oneTracking['source'], ['O'])){
						$HtmlStr .="<td class='font-color-2' nowrap>";
						//ys0929,显示 付款时间，如果有的话
						if (!empty($addi_info['order_paid_time'])){
							$HtmlStr .= TranslateHelper::t('付款时间:')."<br>".date('Y-m-d',$addi_info['order_paid_time']);
						}
						$HtmlStr .= "</td>";//ship_out_date
					}else{
						//手工录入 显示 ship out date 为准
						$HtmlStr .="<td class='font-color-2' nowrap>".TranslateHelper::t('录入时间:')."<br>".$oneTracking['create_time']."</td>";
					}
					
					$HtmlStr .= "<td class='font-color-2'>". substr($oneTracking['update_time'], 0 , 16)."</td>";
					//
					$tmp_onclick_function = 'ListTracking.ignoreShipType';
					$tmp_class_name = 'egicon-ok-blue';
					$tmp_class_title = '将这种物流运输方式设置为自动忽略查询';
					global $CACHE;
					if (!empty($CACHE['IgnoreToCheck_ShipType'])){
						if(in_array($oneTracking['ship_by'],$CACHE['IgnoreToCheck_ShipType'])){
							$tmp_onclick_function = 'ListTracking.reActiveShipType';
							$tmp_class_name = 'iconfont icon-guanbi';
							$tmp_class_title = '取消这种物流运输方式的自动忽略设定';
						}
					}
					//
					$HtmlStr .= "<td>".
							(
								empty($oneTracking['ship_by'])? '':"<span class='font-color-2'>". $oneTracking['ship_by'].'</span>'.
								'<span class=\''.$tmp_class_name.'\' onclick="'.$tmp_onclick_function.'(\''.base64_encode($oneTracking['ship_by']).'\')" title="'.$tmp_class_title.'" style="cursor:pointer;margin-left:5px;"></span>'
							).
							//手动设置查询使用的物流渠道按钮	//liang 2017-01-10
						" <span onclick=\"ListTracking.setCarrierType('". $oneTracking['id'] ."','')\" title='".TranslateHelper::t('指定查询的物流渠道')."' class=\"egicon-binding\" aria-hidden=\"true\" style=\"cursor:pointer;\"></span>".
						((!empty($addi_info['set_carrier_type']) && !empty($addi_info['set_carrier_type_time']))?"<br><span style='cursor:pointer;color:#00bb4f;font-style:italic;' title='于".$addi_info['set_carrier_type_time']."由用户指定'>指定使用".@CarrierTypeOfTrackNumber::$expressCode[$addi_info['set_carrier_type']]."查询</span>":"").	
					"</td>";
					
					$canIgnoreStatus = Tracking::getCanIgnoreStatus('ZH');
					$HtmlStr .= "<td nowrap data-status='".$oneTracking['status']."'><strong ".(empty($status_qtip_mapping[$oneTracking['status']])?"":" qtipkey='".$status_qtip_mapping[$oneTracking['status']]."'")." class='no-qtip-icon font-color-1' >". $oneTracking['status']."</strong>";
					
					$HtmlStr .= (!empty($status_qtip_mapping[$oneTracking['status']]) && $status_qtip_mapping[$oneTracking['status']]=='tracker_no_info')?'<span onclick="reportTrackerNoInfo('.$oneTracking['id'].')" class="egicon-people" style="cursor:pointer;" title="可查反馈"></span>':'';
					$HtmlStr .= in_array($oneTracking['status'],$canIgnoreStatus)?'<span onclick="ignoreTrackingNo('.$oneTracking['id'].')" class="iconfont icon-ignore_search" style="cursor:pointer;vertical-align:middle;font-size:14px" title="忽略(不再查询)"></span>':'';
					
					//显示手工移动状态的log
					if(!empty($addi_info['manual_status_move_logs'])){
						foreach ($addi_info['manual_status_move_logs'] as $move_log){
							$HtmlStr .= '<br>('.@$move_log['time'].'被'.$move_log['capture_user_name'].'由"'
									.Tracking::getChineseStatus($move_log['old_status']).'"移动到"'
									.Tracking::getChineseStatus($move_log['new_status']).'")';
						}
					}
					
					$HtmlStr .= "<p class='font-color-2'><small>(".$total_days.TranslateHelper::t('天').")</small></p></td>";
					// 
					//2015-07-10 liang start 
					$stay_days='-';
					if(is_numeric($oneTracking['stay_days']) && $oneTracking['stay_days']>0) $stay_days=$oneTracking['stay_days'].TranslateHelper::t("天");
					$HtmlStr .="<td class='font-color-2' nowrap>".$stay_days."</td>";//ship_out_date	
					//2015-07-10 liang end
					
					//$HtmlStr .= "<td nowrap><span class='". $status_class."' ".(empty($status_qtip_mapping[$oneTracking['status']])?"":" qtipkey='".$status_qtip_mapping[$oneTracking['status']]."'")." >". $oneTracking['status']."</span><br><p style=\"margin: 5px 0 0px 25px;\"><small>".$markHandleStr.$total_days.TranslateHelper::t('天')."</small></p></td>";
					
					$HtmlStr .= "<td>";
						//立即更新   按键
						if (! in_array(strtolower($oneTracking['status']), [TranslateHelper::t("查询中") , TranslateHelper::t("查询等候中"), TranslateHelper::t("已完成"),TranslateHelper::t("忽略(不再查询)")]) ):
						$HtmlStr .=" <a onclick=\"ListTracking.UpdateTrackRequest('". $oneTracking['track_no'] ."',this)\"  title='".TranslateHelper::t('立即更新')."'>".'<span class="egicon-refresh"></span>'."</a>";
						endif;
					
						// 详情  按键
						//if (! in_array(strtolower($oneTracking['status']), [TranslateHelper::t("查询中") , TranslateHelper::t("查询不到")]) ):

						//khcomment20150610 $HtmlStr .=" <a onclick=\"ListTracking.ShowDetailView(this)\" title='".TranslateHelper::t('详情')."'>".'<span class="glyphicon glyphicon-eye-open" aria-hidden="true"></span>'."</a>";
						
						//liang 2016-03-24 17track iframe start
						$addi_info = json_decode($oneTracking['addi_info'],true);
						if(!empty($addi_info['return_no']))
							$abroad_no = $addi_info['return_no'];
						else
							$abroad_no='';
						$non17Track = CarrierTypeOfTrackNumber::getNon17TrackExpressCode();
						if (! in_array(strtolower($oneTracking['status']), [TranslateHelper::t("查询不到") , TranslateHelper::t("无法交运")]) ){
							if(!in_array($oneTracking['carrier_type'],$non17Track)){
								$HtmlStr .=' <a title="'.TranslateHelper::t('详情').'" onclick="iframe_17Track(\''.(empty($abroad_no)?$oneTracking['track_no']:$abroad_no).'\',this)" data-track-id="'.$oneTracking['id'].'">'.'<span class="egicon-eye"></span>'."</a>";
							}else{
								$HtmlStr .=" <a title='".TranslateHelper::t('详情')."' class='btn-qty-memo' data-track-id='".$oneTracking['id']."'>".'<span class="egicon-eye"></span>'."</a>";
							}
						}//liang 2016-03-24 17track iframe end

						//endif;
						
						if ( !empty($oneTracking['seller_id']) && !empty($oneTracking['order_id'])):
						//订单详情   按键
						$HtmlStr .=" <a onclick=\"ListTracking.ShowOrderInfo('". $oneTracking['track_no'] ."')\" title='".TranslateHelper::t('订单详情')."' >".'<span class="egicon-notepad" aria-hidden="true"></span>'."</a>";
						endif;
						//添加备注 按键
						$HtmlStr .=" <a onclick=\"ListTracking.showRemarkBox('". $oneTracking['track_no'] ."')\" title='".TranslateHelper::t('添加物流备注')."'>".'<span class="egicon-memo-blue" aria-hidden="true"></span>'."</a>";
						//增加标签 按键 (旧版)
						//$HtmlStr .=" <a onclick=\"ListTracking.showTagBox('". $oneTracking['id'] ."','". $oneTracking['track_no'] ."')\" title='".TranslateHelper::t('添加标签')."'>".'<span class="glyphicon glyphicon-tags" aria-hidden="true"></span>'."</a>";
						if ( !empty($oneTracking['seller_id']) && !empty($oneTracking['order_id']) && in_array($oneTracking['platform'], ['ebay','aliexpress','amazon','cdiscount'])):
						
						//站内信 按钮
						$HtmlStr .=" <a onclick=\"StationLetter.showMessageBox('". $oneTracking['order_id'] ."','". $oneTracking['track_no'] ."','role')\" title='".TranslateHelper::t('发送站内信')."'>".'<span class="egicon-envelope" aria-hidden="true"></span>'."</a>";
						endif;
						//删除  按键
						$HtmlStr .=" <a onclick=\"ListTracking.DelTrack('". $oneTracking['track_no'] ."')\" title='".TranslateHelper::t('删除')."'>".'<span class="egicon-trash" aria-hidden="true"></span>'."</a>".$markHandleLink;
						
					$HtmlStr .=" </td>".
				"</tr>";
				
				
			$result[$oneTracking['track_no']] = $HtmlStr;
							
			return $result;
			
	}//end of generateQueryTrackingInfoHTML
	
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * excel 导入 物流信息
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param array $ExcelFile 用户按照excel模版 格式制定的 excel数据 限xls 文件
	 +---------------------------------------------------------------------------------------------
	 * @return array	$result ['success'] boolean
	 * 					$result ['message'] string  运行结果的提示信息
	 * 					$result ['ImportDataFieldMapping']  array 用户前台从excel复制录入的数据识别col对应数据库的字段
	 *
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2015/2/17				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function importTrackingDataByExcel($ExcelFile){
		try {
			$puid = \Yii::$app->subdb->getCurrentPuid();
			//初始化 返回结果
			$result['success'] = true;
			
			//获取 excel数据
			
			$TrackingData = ExcelHelper::excelToArray($ExcelFile , [
					"A" => "A",//"ship_by",//快递公司
					"B" => "B",// "ship_out_date",//快递单日期
					"C" => "C",// "track_no",//物流号
					"D" => "D",// "order_id",//订单号
					"E" => "E",// "delivery_fee",//运费
					], false); //false is to keep first row
			
			$map = [
			"递送公司" => "ship_by",//快递公司
			"快递单日期" =>   "ship_out_date",//
			"快递单号" =>   "track_no",//
			"订单号" =>  "order_id",//
			"递送费用(CNY)" =>  "delivery_fee",//
			"物流号" =>   "track_no",//
			"物流号码" =>   "track_no",//
			"追踪号" =>   "track_no",//
			"追踪号码" =>   "track_no",//
			];
			
			//2015-09-17 start  限制excel单次倒入不超过500单，每天最多上传1000单
			if( $puid=='18870' ){
				$per_limit= '5000';
			}else{
				$per_limit = 100; //限制excel单次倒入不超过500单,
			}


			//ys0919, 老板话捂好要你个限制了，所以我改成非常大噶限制，相当于无限制
			foreach($TrackingData[1] as $value){
				if (array_key_exists($value,$map)){
					$per_limit ++;
					break;
				}
			}
			
			//限制excel单次导入不超过500单
			if (count($TrackingData)-1>$per_limit){
				$result['success'] = false;
				$result['message'] = TranslateHelper::t(" excel单次导入不超过".$per_limit."单! ");
				return $result;
			}
			
			
			$VipLevel = 'v0';
			 
    		
    		if ($VipLevel == 'v0'){
    			//免费用户
    			$suffix = date('Ymd');
    		}else{
    			$suffix = 'vip';
    		}
			
			$limt_count =  self::getTrackerTempDataFromRedis("trackerImportLimit_".$suffix );
			if (empty($limt_count)) $limt_count=0;
			
 
			$max_import_limit = TrackingHelper::getTrackerQuota($puid);
			if ( $limt_count + count($TrackingData)-1 > $max_import_limit ){
					
				if (TrackingHelper::$tracker_guest_import_limit == $max_import_limit){
					$tips = "如想增加物流跟踪助手查询限额到".TrackingHelper::$tracker_import_limit.",请绑定平台账号。";
				}else{
					$tips = '';
				}
				
				$result['success'] = false;
				if ($limt_count == 0 ){
					$result['message'] = TranslateHelper::t("每天手动录入与excel上传总数上限".$max_import_limit."单! "." 本次导入记录超出上限！".$tips);
				}else{
					$result['message'] = TranslateHelper::t("每天最多上传".$max_import_limit."单,当前已经导入".$limt_count."条物流! "." 本次导入".(count($TrackingData)-1)."条记录失败！".$tips);
				}
				
				return $result;
			}
			
			//2015-09-17 end  限制excel单次倒入不超过300单，每天最多上传1000单
			
			
			$track_no_list = [];
			$row_no = 0;
			$colMapFields=[];
			$TrackingData2 = [];
			$repeat_track_no_list = []; //保存重复的track no 检查是否重复使用 201501016kh
			//检查excel 导入的物流信息  是否重复
			foreach($TrackingData as $oneTracking1):
			$row_no ++;
			//对第一行，当成是 column header的处理，判断这个col到底是什么内容
			if ($row_no == 1){
					
				foreach ($oneTracking1 as $key => $val){
					$val=trim($val);
					if (isset($map[$val]))
						$colMapFields[$key] = $map[$val]; //key='A', $val=快递单号 ， $map[$val] = track_no
				}
				continue;
			}
			
			//for 第二行以后的，执行这个mapping
			foreach ($oneTracking1 as $key=>$val){
				if (isset($colMapFields[$key]))
					$oneTracking[$colMapFields[$key]] = $val;
			}
			
			// 201501016kh 空物流号跳过， 不做处理
			if (empty($oneTracking['track_no'])) continue;
			
			//\Yii::info(['Tracking',__CLASS__,__FUNCTION__,'Online',"Try to import track no ".print_r($oneTracking,true)  ],"edb\global");
			if (! in_array($oneTracking['track_no'], $track_no_list)){
				$track_no_list[] = $oneTracking['track_no'];
			}else{
				// 201501016kh  重复的track no 会记录下来， 统计完成后将会生成一个结果给用户看
				if (isset($repeat_track_no_list[$oneTracking['track_no']]))
					$repeat_track_no_list[$oneTracking['track_no']] ++ ;
				else 
					$repeat_track_no_list[$oneTracking['track_no']] =2 ;
			}
			$TrackingData2[] = $oneTracking;
			endforeach;
			
			//201501016kh 如果重复了则不导入tracker
			if (!empty($repeat_track_no_list)){
				$repeat_message = "";
				foreach($repeat_track_no_list as $repeat_track_no => $repeat_count){
					
					$repeat_message .= "$repeat_track_no 出现了$repeat_count 次<br>";
				}
				$result['success'] = false;
				$result['message'] = TranslateHelper::t($repeat_message." 请把重复的物流号处理后再导入.");
				return $result;
			}
			
			
			$TrackingData = $TrackingData2;
			
			$batch_no = "E".date('YmdHi');
			
			//step 1 保存 track 信息
			$doneCount = 0;
			$totalCountExcel = count($TrackingData);
			$track_nos = array();
			\Yii::info(['Tracking',__CLASS__,__FUNCTION__,'Background',"Start to Import 1000 tracking"],"edb\global");
			foreach ($TrackingData as $oneTracking):
			$oneTracking['batch_no'] = $batch_no;
			//below is just to add Tracking to Buffer first
			$rtn1 = self::addTracking($oneTracking,'E',$totalCountExcel);
				
			if (isset($rtn1['success']) and $rtn1['success']){
				$doneCount ++;
				$track_nos[] = $oneTracking['track_no'];
			}
			endforeach;
			
			//call this to put all Tracking into DB
			self::postTrackingBufferToDb();
			
			\Yii::info(['Tracking',__CLASS__,__FUNCTION__,'Background',"Complete to Import 1000 tracking"],"edb\global");
			//step 2 生成api queue，这个循环不放在上面做，是因为如果很多tracking添加了，那么一起做会造成php 超时。
			//需要先保证上面的 step 1 做完，这里的step 2就算被apache强行结束了也不要紧，cronb job 会重新生成queue api request 的
			if ($doneCount < 30){ //when <= 40 records, 立即生成tracking 排队
				foreach ($TrackingData as $oneTracking):
				self::generateOneRequestForTracking($oneTracking['track_no']);
				endforeach;
				//吧Api Request Buffer 的批量insert 到db
				self::postTrackingApiQueueBufferToDb();
			} else //when 上传的记录大于 40 条，使用buffer 异步机制，有异步job自动捡起来做的
				self::putIntoTrackQueueBuffer($track_nos );
			
			$result['count'] = $doneCount ;
			$result['batch_no'] = $batch_no;
			$result['message'] = TranslateHelper::t('导入'.count($TrackingData).'条物流记录成功，系统正在追踪中。<br>当天还可以导入'.($max_import_limit-$limt_count - count($TrackingData)).'条物流记录。');
			//记录当前查询的次数
// 			self::setTrackerTempDataToRedis("trackerImportLimit_".$suffix , $limt_count + count($TrackingData)-1);//不能处理并发问题， 改为新接口
			TrackingHelper::addTrackerQuotaToRedis("trackerImportLimit_".$suffix ,  count($TrackingData)-1);//20170912 使用redisadd 的新接口
		} catch (\Exception $e) {
			$result = ['success'=>false , 'message'=> TranslateHelper::t('excel格式有问题， 详情请联系客服！' ).$e->getMessage()." error code:".$e->getLine()];
		}
		return $result;
		
	}//end of importTrackingDataByExcel 
	

	/**
	 +---------------------------------------------------------------------------------------------
	 * 渠道统计分析 :
	 * 		考虑数据量的关系 , 所以设置了最大日期间隔的参数 .
	 * 		 假如   [起始时间]  超出最大日期间隔 ,则返回空数组  ;
	 * 		 不超出则继续执行查询
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param date $from 渠道统计分析 查询的起始时间  e.g. 2015-02-28 
	 * @param date $to 渠道统计分析 查询的结束时间   e.g. 2015-02-28
	 * @param int $max_interval 渠道统计分析 查询的是最大天数   e.g. 90
	 * 
	 +---------------------------------------------------------------------------------------------
	 * @return array	$result ['success'] boolean
	 * 					$result ['message'] string  运行结果的提示信息
	 * 					$result ['data']  array 渠道统计分析
	 *
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2015/2/28				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function getDeliveryStatisticalAnalysis($from , $to , $to_nation , $max_interval=90){
		$result ['success'] = true;
		$result ['message'] = "";
		$result ['data'] = [];
		//检查 起始日期 是否有效
		if (empty($from)){
			$result ['success'] = false;
			$result ['message'] = TranslateHelper::t('起始时间不能为空!');
			return $result;
		}
		
		//检查 起始日期  当天 间隔的天数是否超出查询 strtotime 会有时间 在, 所以减上一天
		if (strtotime("- "+($max_interval+1)+" day") > strtotime($from)){
			$result ['success'] = false;
			$result ['message'] = TranslateHelper::t('不能查询'.$max_interval.'天前的数据!');
			return $result;
		}
		
		$andSql = "";
		if (!empty($from)){
			$andSql .= " and ship_out_date >= '$from' ";
		}
		
		if (!empty($to)){
			$andSql .= " and ship_out_date <= '$to' ";
		}
		
		if (!empty($to_nation)){
			$andSql .= " and to_nation = '$to_nation' ";
		}
		//$whereSql = " where 1=1 and '$from' '$to'";
			
		// 统计 所有 物流商 的  包裹总数 , 平均时效 , 总费用 , 平均费用
		$sql = "select ship_by , count(1) as total_count,  avg(total_days) as avg_day , sum(delivery_fee) as total_delivery_fee ,  avg(delivery_fee) as avg_delivery_fee from lt_tracking where 1 $andSql group by ship_by";
		$ShipByResult = Yii::$app->get('subdb')->createCommand($sql)->queryAll();
		//echo "".$sql;//test kh
		$Tracking = new Tracking();
		
		// 统计  各个物流商的  成功签收
		$parcel_classification = 'received_parcel';
		
		self::_set_delivery_statistical_analysis_data($parcel_classification, $ShipByResult, $Tracking,$from , $to , $to_nation);
		
		// 统计  各个物流商的  递送异常
		
		$parcel_classification = 'exception_parcel';
		
		self::_set_delivery_statistical_analysis_data($parcel_classification, $ShipByResult, $Tracking,$from , $to, $to_nation);
		
		// 统计  各个物流商的 运输途中
		
		$parcel_classification = 'shipping_parcel';
		
		self::_set_delivery_statistical_analysis_data($parcel_classification, $ShipByResult, $Tracking,$from , $to, $to_nation);
		
		// 统计  各个物流商的 递送超时
		
		$parcel_classification = 'ship_over_time_parcel';
		
		self::_set_delivery_statistical_analysis_data($parcel_classification, $ShipByResult, $Tracking,$from , $to, $to_nation);
		
		// 统计  各个物流商的 无法交运
		
		$parcel_classification = 'unshipped_parcel';
		
		self::_set_delivery_statistical_analysis_data($parcel_classification, $ShipByResult, $Tracking,$from , $to, $to_nation);
		
		//返回结果
		$result ['data'] = $ShipByResult;
		return $result;
		
	}//end of getDeliveryStatisticalAnalysis
	
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 渠道统计分析 :
	 * 		考虑数据量的关系 , 所以设置了最大日期间隔的参数 .
	 * 		 假如   [起始时间]  超出最大日期间隔 ,则返回空数组  ;
	 * 		 不超出则继续执行查询
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param string $parcel_classification 包裹类型
	 * @param array $ShipByResult 渠道统计分析结果
	 * @param model $Tracking tracking model 
	 *
	 +---------------------------------------------------------------------------------------------
	 * @return null
	 *
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2015/2/28				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static private function _set_delivery_statistical_analysis_data($parcel_classification , &$ShipByResult , &$Tracking, $from='' , $to='' , $to_nation=''){
		try {
			$condition = Tracking::getTrackingConditionByClassification ( $parcel_classification );
			
			foreach($ShipByResult as &$oneShipBy){
				$tmp_condition = $condition;
				$tmp_condition['ship_by'] = $oneShipBy['ship_by'];
				/*
				$tmp_result = $Tracking->find()->andwhere($tmp_condition)->count();
				*/
				$query = $Tracking->find();
				if (!empty($from))
					$query = $query->andWhere(['>=','create_time', $from]);
				
				if (!empty($to))
					$query = $query->andWhere(['<=','create_time', $to]);
				
				if (!empty($to_nation)){
					$query = $query->andWhere(['to_nation'=>$to_nation]);
				}
				
				$oneShipBy[$parcel_classification] = $query->andwhere($tmp_condition)->count();
				
				/* 
				if (isset(\Yii::$app->params["currentEnv"]) and \Yii::$app->params["currentEnv"]=="test" ):
				$tmpCommand = $query->createCommand();
				echo "<br>".$tmpCommand->getRawSql();
				endif;
				*/
				
				
			}
		} catch (Exception $e) {
			echo $e->getMessage();
		}
		
	}//end of _set_delivery_statistical_analysis_data
	
	static public function getCandidateCarrierType($track_no, $ship_by1=''){
		
		//priority 1: 用user 自己指定的来玩, 后面的流程就不需要了
		$userSpecified = self::getUserShipByAndCarrierTypeMappingFromRedisForShipBy($ship_by1);
		if ($userSpecified <> ''){
			$carrier_types["".$userSpecified.""] = $userSpecified;
			return $carrier_types;
		}
		
		//priority 2，用其他方法
		$results =  CarrierTypeOfTrackNumber::checkExpressOftrackingNo($track_no);
		$carrier_types = array();
		foreach ($results as $carrier=>$carrerName){
			$carrier_types["".$carrier.""] = $carrier;
		}
		
		//加入global的指定，如果有match
		$globalSpecified = self::getGlobalShipByAndCarrierTypeMappingFromRedisForShipBy($ship_by1);
		if ($globalSpecified <> '')
			$carrier_types["".$globalSpecified.""] = $globalSpecified;
		
		//如果没有匹配任何规则，用全球邮政来玩玩吧
		if (empty($carrier_types))
			$carrier_types['0']='0';
		
		return $carrier_types;
	}
	
	static public function getAllCandidateCarrierType(){
		$results =  CarrierTypeOfTrackNumber::getAllExpressCode( );
		//array('0'=>'全球邮政',	'100001'=>'DHL', ... )
		$carrier_types = array();
		foreach ($results as $carrier=>$carrerName){			
			$carrier_types["".$carrier.""] = $carrier;
		}
		
		if (isset($carrier_types["888000001"]))
			unset( $carrier_types["888000001"] );
		
		if (isset($carrier_types["888000002"]))
			unset( $carrier_types["888000002"] );
	
		return $carrier_types;
	}	
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 自动映射国家名字:
	 * 		根据 国家代码 自动映射 国家名字, 优先中文名称, 其次英文名称 , 最后查找不到返回 中文 "未知"
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param string $country_code 国家代号
	 *
	 +---------------------------------------------------------------------------------------------
	 * @return string 国家名
	 *
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2015/3/10				初始化
	 +---------------------------------------------------------------------------------------------
	 **/    
	static public function autoSetCountriesNameMapping($country_code = "--"){
		if (isset(StandardConst::$COUNTRIES_CODE_NAME_CN[$country_code])){
			//优先选择中文名
			$country_name = StandardConst::$COUNTRIES_CODE_NAME_CN[$country_code];
		}else{
			//找不到的情况查找英文版的国家名
			if(isset(StandardConst::$COUNTRIES_CODE_NAME_EN[$country_code]))
				$country_name = StandardConst::$COUNTRIES_CODE_NAME_EN[$country_code];
		}
		
		//中英文都查找不了就返回未知
		if (empty($country_name))
			$country_name = StandardConst::$COUNTRIES_CODE_NAME_CN['--'];
			
		return $country_name;
		
		
	}//end of autoSetCountriesNameMapping
	
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 清除空数组:
	 * 		根据 国家代码 自动映射 国家名字, 优先中文名称, 其次英文名称 , 最后查找不到返回 中文 "未知"
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param array $arr   清除空数组的对象 
	 *
	 +---------------------------------------------------------------------------------------------
	 * @return array  $arr
	 *
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2015/3/13				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function array_no_empty($arr) {
		if (is_array($arr)) {
			foreach ( $arr as $k => $v ) {
				if (empty($v)) unset($arr[$k]);
				elseif (is_array($v)) {
					$arr[$k] = array_no_empty($v);
				}
			}
		}
		return $arr;
	}//end of array_no_empty
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 根据每天的统计报告，导出excel
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param array $arr   清除空数组的对象
	 *
	 +---------------------------------------------------------------------------------------------
	 * @return array  $arr
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		yzq		2015/3/17				初始化
	 +---------------------------------------------------------------------------------------------
	 **/	
	static public function showTrackingReportFor($date_from,$date_to){
		$csld_format_distribute = ConfigHelper::getGlobalConfig("Tracking/csld_format_distribute_$date_from",'NO_CACHE');
		$csld_format_distribute = json_decode($csld_format_distribute,true);
		if (!isset($csld_format_distribute))
			$csld_format_distribute = array();
		
		//step 1，统计当天的创建查询的总数和更新查询的总数，以及成功数等
		$createOrUpdate = ['created','updated'];
		$SuccessOrFail = ['Success','Fail'];
		$carrierNation_all = isset($csld_format_distribute['carrier_nation_distribute']) ? $csld_format_distribute['carrier_nation_distribute'] : array();
		
		foreach ($createOrUpdate as $createOrUpdateLabel)
			foreach ($SuccessOrFail as $SuccessOrFailLabel){
				$CountFor[$createOrUpdateLabel][$SuccessOrFailLabel] = 0;
				if (isset($csld_format_distribute[$createOrUpdateLabel][$SuccessOrFailLabel]))
				foreach($csld_format_distribute[$createOrUpdateLabel][$SuccessOrFailLabel] as $codeFormat=>$count){
					$CountFor[$createOrUpdateLabel][$SuccessOrFailLabel] += $count;	
					$CountFor['Total'][$codeFormat] = (isset($CountFor['Total'][$codeFormat])?$CountFor['Total'][$codeFormat]:0 ) + $count;
					$CountFor[$SuccessOrFailLabel][$codeFormat] = (isset($CountFor[$SuccessOrFailLabel][$codeFormat])?$CountFor[$SuccessOrFailLabel][$codeFormat]:0 ) + $count;
				}

				if (!isset($CountFor[$createOrUpdateLabel][$SuccessOrFailLabel])) 
					$CountFor[$createOrUpdateLabel][$SuccessOrFailLabel] = 0;
		}
		
		$status_pie = isset($csld_format_distribute['status_pie']) ? $csld_format_distribute['status_pie'] : array(); 
		$source_pie = isset($csld_format_distribute['source_pie']) ? $csld_format_distribute['source_pie'] : array();
		$recommend_pie = isset($csld_format_distribute['Recm_prod_perform']) ? $csld_format_distribute['Recm_prod_perform'] : array();
		
		//Step 2, 开始make a report
		$excelRows = array();
		$excelRows [] = array("$date_from Tracking Report"); //line 1
		$excelRows [] = array();  //line 2
		
		//step 2.0, show section for 推荐发信着落叶发送以及view count，click count
		$excelRows [] = array("所有客户当日使用二次营销情况，此数据来源为快照时间 $date_from"." 23:59:59"); //line 1 of section
		$excelRows [] = array("销售平台","发信数量","打开次数", "商品展示次数" , "商品点击次数",'点击/展示'); //table header
		 /*$recommend_pie = array('aliexpress'=>array(browse_count=>411,view_count=>30,click_count=10))
		  * */
		$Summary['platform'] = 0;
		$Summary['send_count'] = 0;
		$Summary['browse_count'] = 0;
		$Summary['prod_show_count'] = 0;
		$Summary['prod_click_count'] = 0;		
		foreach ( $recommend_pie as $platform=>$count){
		 		if (empty($count['send_count'])) $count['send_count'] = 0;
		 		if (empty($count['browse_count'])) $count['browse_count'] = 0;
		 		if (empty($count['prod_show_count'])) $count['prod_show_count'] = 0;
		 		if (empty($count['prod_click_count'])) $count['prod_click_count'] = 0;
		 		
		 		$Summary['send_count'] += $count['send_count'];
		 		$Summary['browse_count'] += $count['browse_count'];
		 		$Summary['prod_show_count'] += $count['prod_show_count'];
		 		$Summary['prod_click_count'] += $count['prod_click_count'];
		 		
				$excelRows [] = array($platform , number_format($count['send_count'],0) ,
						number_format($count['browse_count'],0)  ,  
						number_format($count['prod_show_count'],0),
						number_format($count['prod_click_count'],0),
						empty($count['prod_show_count'])?"": number_format($count['prod_click_count'] *100 /$count['prod_show_count'],2) ."%" ,
						);
		}//end of each status
		
		$excelRows [] = array("Total" , number_format($Summary['send_count'],0) ,
				number_format($Summary['browse_count'],0)  ,
				number_format($Summary['prod_show_count'],0),
				number_format($Summary['prod_click_count'],0),
				empty($Summary['prod_show_count'])?"": number_format($Summary['prod_click_count'] *100 /$Summary['prod_show_count'],2) ."%" ,
		);
		
		$excelRows [] = array();  //line gap
		//step 2.1 物流号的分布总图
		$excelRows [] = array("所有客户物流号状态分布图，此数据来源为快照时间 $date_from"." 23:59:59"); //line 1 of section
		$status_total = 0;
		$status_call_total = 0;
		foreach ( $status_pie as $status=>$count){
			$status_total += $count;
		}
		//echo "got data ".print_r($csld_format_distribute,true)."<br>";
		//echo "status count ".count($status_pie)." total count $status_total <br>";
		//step 2.1.a, Load all 查询次数分布for this date
		$command = Yii::$app->db->createCommand("select * from ut_ext_call_summary where substr(time_slot,1,10)='$date_from' and ext_call like 'Tracking.17@%'") ;
		$extCalls = $command->queryAll();
		$extCallsForStatus = [];
		foreach ($extCalls as $aStatusCall){
			$status_call_total += $aStatusCall['total_count'];
			if (!isset($extCallsForStatus[ $aStatusCall['ext_call'] ])) 
				$extCallsForStatus[ $aStatusCall['ext_call'] ] = 0;
			
			$extCallsForStatus[ $aStatusCall['ext_call'] ] += $aStatusCall['total_count'];
		}
		
		$excelRows [] = array("物流状态","数量","占比", "查询次数" , "占比",'录入来源 OMS/手动录入'); //table header

		$Summary['count'] = 0;
		$Summary['oms'] = 0;
		$Summary['manual'] = 0;
		foreach ( $status_pie as $status=>$count){
			//calculate this status 查询次数
			$callCount = 0;
		//	echo "try to do for $status $count <br>";
			if ($status_call_total > 0 and isset($extCallsForStatus[ 'Tracking.17@'.Tracking::getChineseStatus($status) ]  )){
				$callCount = $extCallsForStatus[ 'Tracking.17@'.Tracking::getChineseStatus($status) ];				
				$excelRows [] = array(Tracking::getChineseStatus($status), number_format($count,0) , 
						number_format($count * 100 /$status_total,2)
						 ."%" ,  number_format($callCount,0) ,  
						number_format($callCount * 100 /$status_call_total,2) ."%" , 
						   number_format( (isset($source_pie['O'][$status])?$source_pie['O'][$status]:0),0) . " / ".
							number_format(((isset($source_pie['E'][$status])?$source_pie['E'][$status]:0)  + 
							  (isset($source_pie['M'][$status])?$source_pie['M'][$status]:0) ) ,0)  );
				
				$Summary['count'] += $count;
				$Summary['oms'] += (isset($source_pie['O'][$status])?$source_pie['O'][$status]:0);
				$Summary['manual'] += ((isset($source_pie['E'][$status])?$source_pie['E'][$status]:0)  + 
							  (isset($source_pie['M'][$status])?$source_pie['M'][$status]:0) );
			}
		}//end of each status
		//summary
		$excelRows [] = array("合计", number_format($Summary['count'],0) ,
						'' , '' ,	'' , number_format($Summary['oms'],0). " / ".	number_format($Summary['manual'],0) );
		
		$excelRows [] = array();  //line gap
		
		//step 2.1.5 不同来源(OMS，Excel，手工)的统计pie
		/* 
		$excelRows [] = array("所有物流号的录入来源 $date_from"." 23:59:59"); //line 1 of section
		$colHeader = array("录入方式" );
		$all_souce_vs_status_report = array();
		 
		foreach ( $source_pie as $source_code=>$source_code_has){
			self::arrayPlus($source_code_has, $all_souce_vs_status_report);
		}
		
		foreach ($all_souce_vs_status_report as $status_code =>$total_count){
			$colHeader[] = Tracking::getChineseStatus($status_code);
		}
		
		$excelRows [] = $colHeader;
		
		$status_total = 0;
		$status_call_total = 0;
		foreach ( $source_pie as $source_code=>$source_code_has){
			$aRow = array();
			if ($source_code=='O')
				$aRow[] = 'OMS';
			else
				$aRow[] = '手动录入';
			
			foreach ($all_souce_vs_status_report as $status_code =>$count){
				//add a column for this status count
				$theCount = 0;
				if (isset($source_code_has[$status_code]))
					$theCount = $source_code_has[$status_code];

				$aRow[] = $theCount;
				
			}//end of each status
			$excelRows [] = $aRow;
			
		}//end of each source code
		
		//subtotal
		$aRow = array();
		$aRow[] = '合计';
		foreach ($all_souce_vs_status_report as $status_code =>$count){
			//add a column for this status count
			$aRow[] = $count;
		}//end of each status
		$excelRows [] = $aRow;
		*/
		 
		$excelRows [] = array();  //line gap
		
		//step 2.2 查询成功失败的物流号分布
		$excelRows [] = array("","物流号数量","查询成功",	"占比例"	,"查询不到",	"占比例");
		
		$createdTotal = $CountFor['created']['Success'] +$CountFor['created']['Fail'];
		$createdSuccessPercent = ResultHelper::formatPercentage( $createdTotal> 0 ? $CountFor['created']['Success']/$createdTotal : 0);
		$createdFailPercent = ResultHelper::formatPercentage( $createdTotal> 0 ? $CountFor['created']['Fail']/$createdTotal : 0);		
		$excelRows [] = ["创建查询",$createdTotal,$CountFor['created']['Success'], $createdSuccessPercent,$CountFor['created']['Fail'], $createdFailPercent ];
		
		$updatedTotal = $CountFor['updated']['Success'] +$CountFor['updated']['Fail'];
		$updatedSuccessPercent = ResultHelper::formatPercentage( $updatedTotal> 0 ? $CountFor['updated']['Success']/$updatedTotal : 0);
		$updatedFailPercent = ResultHelper::formatPercentage( $updatedTotal> 0 ? $CountFor['updated']['Fail']/$updatedTotal : 0);
		$excelRows [] = ["更新查询",$updatedTotal,$CountFor['updated']['Success'], $updatedSuccessPercent,$CountFor['updated']['Fail'], $updatedFailPercent ];
		
		$Total = $updatedTotal+$createdTotal;
		$SuccessPercent = ResultHelper::formatPercentage( $Total> 0 ? ($CountFor['updated']['Success']+$CountFor['created']['Success'])/$Total : 0);
		$FailPercent = ResultHelper::formatPercentage( $Total> 0 ? ($CountFor['updated']['Fail']+$CountFor['created']['Fail'])/$Total : 0);
		$excelRows [] = ["合计",$Total,$CountFor['updated']['Success'] + $CountFor['created']['Success'] , 
							$SuccessPercent,$CountFor['updated']['Fail']+$CountFor['created']['Fail'], $FailPercent ];
		
		//step 3, show 最失败的code format top 5
		$FailCodeFormats = isset($CountFor['Fail'])?$CountFor['Fail'] : array();
		//	倒序排序并且保持 key 和 value 的关系
		arsort ($FailCodeFormats);
		$excelRows [] = array();
		$excelRows [] = ["失败最多物流号格式",'',"成功总数","失败总数",'创建失败','更新失败','主要客户号','物流渠道可能'];
		$ind = 0;
		foreach($FailCodeFormats as $codeFormat => $count){
			$ind++;
			if ($ind > 5) continue;
			
			if (isset($csld_format_distribute['created']['Fail'][$codeFormat]))
				$createdErrorCount = $csld_format_distribute['created']['Fail'][$codeFormat];
			else 
				$createdErrorCount = 0;
			
			if (isset($csld_format_distribute['updated']['Fail'][$codeFormat]))
				$updatedErrorCount = $csld_format_distribute['updated']['Fail'][$codeFormat];
			else
				$updatedErrorCount = 0;
			
			if (isset($csld_format_distribute['created']['Success'][$codeFormat]))
				$createdSuccessCount = $csld_format_distribute['created']['Success'][$codeFormat];
			else
				$createdSuccessCount = 0;
				
			if (isset($csld_format_distribute['updated']['Success'][$codeFormat]))
				$updatedSuccessCount = $csld_format_distribute['updated']['Success'][$codeFormat];
			else
				$updatedSuccessCount = 0;			
			
			$code1 = str_replace("#","A",$codeFormat);
			$code1 = str_replace("*","1",$code1);
			$carriers = CarrierTypeOfTrackNumber::checkExpressOftrackingNo($code1);
			
			$excelRows [] = ["$ind",$codeFormat,$updatedSuccessCount+$createdSuccessCount, $count,$createdErrorCount,$updatedErrorCount,'暂不提供', implode(",", $carriers)];
		}
		
		//step 3.5, show total os count
		$excelRows [] = [""];
		if(!isset($carrierNation_all['os_count']))
			$carrierNation_all['os_count'] = 0;
		$excelRows [] = ["总共未完成的物流号有  ".$carrierNation_all['os_count']. " 个"];
		
		//step 4, try to show distribution for Nations
		$excelRows [] = [""];
		$excelRows [] = ["投递国家分布"];
		$maxRow = 20;
		
		$excelRows [] = ["排行","国家Code","国家","数量","比例"];
		if (isset($carrierNation_all['to_nation'])){
			arsort($carrierNation_all['to_nation']);
			$rowCount=0;
			$totalCount = 0;
			foreach ($carrierNation_all['to_nation'] as $nationCode=>$nationCount)
				$totalCount +=  $nationCount;
			
			foreach ($carrierNation_all['to_nation'] as $nationCode=>$nationCount){
				$rowCount++;
				if ($rowCount>$maxRow) 
					break;
				if (trim($nationCode)=='')
					continue;
				
				$excelRows [] = [$rowCount ,$nationCode,
								StandardConst::getNationChineseNameByCode($nationCode)  ,
								$nationCount, round($nationCount *100 /$totalCount,2) ." %"];
				
			}
		}
		
		//Step 5, try to show distribution for Carrier Types
		$excelRows [] = [""];
		$excelRows [] = ["递送渠道分布"];
		$maxRow = 20;
		
		$excelRows [] = ["排行", "物流商","数量","比例"];
		if (isset($carrierNation_all['carrier'])){
			arsort($carrierNation_all['carrier']);
			$rowCount=0;
			$totalCount = 0;
			$allCarriers =  CarrierTypeOfTrackNumber::getAllExpressCode( );
			//array('0'=>'全球邮政',	'100001'=>'DHL', ... )
			
			foreach ($carrierNation_all['carrier'] as $carrierCode=>$carrierCount)
				$totalCount +=  $carrierCount;
				
			foreach ($carrierNation_all['carrier'] as $carrierCode=>$carrierCount){
				$rowCount++;
				if ($rowCount>$maxRow)
					break;
				if (trim($carrierCode)=='')
					continue;
		
				$excelRows [] = [$rowCount ,
				isset($allCarriers[$carrierCode])?$allCarriers[$carrierCode]: $carrierCode ,
				$carrierCount, round($carrierCount *100 /$totalCount,2) ." %"];
		
			}
		}
		
		return $excelRows;		
	}
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 统计Left menu 上的tracking 数据
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param na  
	 +---------------------------------------------------------------------------------------------
	 * @return array  $menuLabelList 各栏目的tracking 数
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2015/3/21				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function getMenuStatisticData(){
		//step 1, 尝试load 统计的cache，如果没有，才立即计算，并且放入cache
		$track_statistics_str = self::getTrackerTempDataFromRedis("left_menu_statistics");
		$track_statistics = json_decode($track_statistics_str,true);
		if (empty($track_statistics)) $track_statistics = array();
		
		$scope = 'all';
		if(!empty($_GET['sellerid']))
			$scope = $_GET['sellerid'];
		
		if($scope == 'all')
		$track_statistics = array();
			
		if (!isset($track_statistics[$scope]['all']) ){
		
			$menuLabelList = [
				'normal_parcel'=>0 , 
					'shipping_parcel'=>0 ,
					'no_info_parcel'=>0 ,
					'suspend_parcel'=>0 ,
				'exception_parcel'=>0 ,
					'ship_over_time_parcel'=>0 ,
					'rejected_parcel'=>0 ,
					'arrived_pending_fetch_parcel'=>0 ,
					'delivery_failed_parcel'=>0,
					'unshipped_parcel'=>0 ,
				'all'=>0 ,
				'received_message'=>0,
				'arrived_pending_message'=>0,
				'delivery_failed_message'=>0,
				'rejected_message'=>0,
				'shipping_message'=>0,
				'ignored_parcel'=>0,
				'quota_insufficient'=>1
			];
			$Tracking = new Tracking();
		
			$d=strtotime("-7 days");
			$startdate = date("Y-m-d", $d);
			
			$menuCondition = [
				'normal_parcel'=>['not',['mark_handled'=>'Y']],
				'shipping_parcel'=>['not',['mark_handled'=>'Y']],
				'no_info_parcel'=>['not',['mark_handled'=>'Y']] ,
				'suspend_parcel'=>['not',['mark_handled'=>'Y']] ,
				'exception_parcel'=>['not',['mark_handled'=>'Y']] ,
				'ship_over_time_parcel'=>['not',['mark_handled'=>'Y']] ,
				'rejected_parcel'=>['not',['mark_handled'=>'Y']] ,
				'arrived_pending_fetch_parcel'=>['not',['mark_handled'=>'Y']] ,
				'delivery_failed_parcel'=>['not',['mark_handled'=>'Y']] ,
				'unshipped_parcel'=>['not',['mark_handled'=>'Y']] ,
				'all'=>['not',['mark_handled'=>'Y']] ,
				'received_message'=>['status'=>['platform_confirmed','received'], 'received_notified'=>'N' ,'source'=>'O'],
				'arrived_pending_message'=>['status'=>'arrived_pending_fetch', 'pending_fetch_notified'=>'N','source'=>'O'],
				'delivery_failed_message'=>['status'=>'delivery_failed', 'delivery_failed_notified'=>'N','source'=>'O'],
				'rejected_message'=>['status'=>'rejected', 'rejected_notified'=>'N', 'source'=>'O'],
				'shipping_message'=>['status'=>'shipping', 'shipping_notified'=>'N', 'source'=>'O'],
				'ignored_parcel'=>['status'=>'ignored'],
				'quota_insufficient'=>['status'=>'quota_insufficient'],
			];
			
			//统计的时候，只需要统计 不是 complete state，并且不是 marked handled 的记录就可以
			foreach($menuLabelList as $menu_type=>&$value){
				if (! empty ($menuCondition[$menu_type])){
					
					if (stripos($menu_type, 'message')){
						if ($menu_type== 'shipping_message')
							$sevenDayAgoSql  = " and `ship_out_date` >= '$startdate' ";
						else 
							$sevenDayAgoSql  = " and `last_event_date` >= '$startdate' ";
						
						$sevenDayAgoSql .=" and first_event_date IS NOT NULL and first_event_date <> last_event_date ";
					}else{
						$sevenDayAgoSql = "";
					}
						
						
					$TrackingQuery = Tracking::find();
										
					$TrackingQuery
						->andWhere($menuCondition[$menu_type])
						->andWhere(Tracking::getTrackingConditionByClassification($menu_type))
						->andWhere(" 1=1 $sevenDayAgoSql ")// state<>'complete' and mark_handled <> 'Y'
						->andWhere("state<>'deleted' ");
					
				
					
					if(!empty($_GET['sellerid']))
						$TrackingQuery->andWhere(['seller_id'=>$_GET['sellerid']]);
					
					$value = $TrackingQuery->count();
					
					/* 调试sql  
					// 过滤删除的数据
					 $TrackingQuery = Tracking::find()->andWhere($menuCondition[$menu_type])
								->andWhere(Tracking::getTrackingConditionByClassification($menu_type))
								->andWhere("1=1 $sevenDayAgoSql ")// state<>'complete' and mark_handled <> 'Y'
								->andWhere("state<>'deleted' ");
					
					
					 
					 if(!empty($_GET['sellerid']))
					 	$TrackingQuery->andWhere(['seller_id'=>$_GET['sellerid']]);
					 
					$tmpCommand = $TrackingQuery->createCommand();
					echo "<br><br> $menu_type : ".$tmpCommand->getRawSql();
					*/
					
					
				}
			}
		
			$track_statistics_scope = $menuLabelList;
			
			if(empty($_GET['sellerid']))
				$track_statistics_scope['completed_parcel'] = Tracking::find()->andWhere(Tracking::getTrackingConditionByClassification('completed_parcel'))->count();
			else 
				$track_statistics_scope['completed_parcel'] = Tracking::find()->andWhere(Tracking::getTrackingConditionByClassification('completed_parcel'))->andWhere(['seller_id'=>$_GET['sellerid']])->count();
			
			$track_statistics[$scope] = $track_statistics_scope;
			
			self::setTrackerTempDataToRedis("left_menu_statistics", json_encode($track_statistics));
		}//end of not cached
		return $track_statistics;
	} //end of getMenuStatisticData	
	
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 删除tracking相关的记录, 并清空数据
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param track_no string or list   物流单号  required
	 +---------------------------------------------------------------------------------------------
	 * @return array  
	 * 					success  boolean  调用 是否成功
	 * 					message  string   调用结果说明
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2015/4/9				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function deleteTracking($trackNoOrList){
		$result ['success'] = true;
		$result ['message'] = '';
		$delItems = [];
		
		//左侧菜单redis数据
		$track_statistics_str = self::getTrackerTempDataFromRedis("left_menu_statistics");
		$track_statistics = json_decode($track_statistics_str,true);
		
		try {
			// 找出 有效的物流单号 
			$TrackingList = Tracking::findAll(['track_no'=>$trackNoOrList]);
			
			//获取当前 用户的puid
			$puid = \Yii::$app->subdb->getCurrentPuid();
			$transaction = Yii::$app->get('subdb')->beginTransaction();
			$userName = Yii::$app->user->identity->getUsername();
			foreach($TrackingList as $aTrack){
				$track_no = $aTrack->track_no;
				$aTrack->state =  Tracking::getSysState("已删除");
				$delRt = $aTrack->delete();
				 
					//写log
					$type = 'tracking';
					$key = $aTrack->track_no;
					$operation = "删除";
				//	OperationLogHelper::log($type, $key, $operation , '' , $userName);
				//}
				 
				//用于检查删除
				$delItems[] = $track_no;
				
				if(isset($track_statistics[$aTrack->seller_id]))
					unset($track_statistics[$aTrack->seller_id]);
				if(isset($track_statistics['all']))
					unset($track_statistics['all']);
			}//end of foreach
			$result ['success'] = (count($delItems) != 0 );
			
			$DelApiQRt = TrackerApiQueue::deleteAll(['track_no'=>$delItems,'puid'=>$puid,'status'=>'P']);
			$DelApiSubQRt = TrackerApiSubQueue::deleteAll(['track_no'=>$delItems,'puid'=>$puid,'sub_queue_status'=>'P']);
			
			/*
			if ( count($delItem) == 0 ){
				$result ['success'] = false;
			}else{
				$result ['success'] = true;
			}
			*/
			// 检查 trackNoOrList 是数组还是单号物流单号
			if (is_array($trackNoOrList)){
				//物流单号
				$result['message'] = TranslateHelper::t("提交删除物流号") .count($trackNoOrList) . TranslateHelper::t('个, 其中有效的物流号并成功') . count($delItem).TranslateHelper::t('个');
			}else{
				if ($result ['success'] ){
					$result['message'] = $trackNoOrList . (TranslateHelper::t('已经删除成功!'));
				}else{
					$result ['message'] = $trackNoOrList . (TranslateHelper::t('已经删除或者不存在!'));
				}
			}
			
			//force update the top menu statistics
			
			self::setTrackerTempDataToRedis("left_menu_statistics", json_encode($track_statistics));
			self::setTrackerTempDataToRedis("top_menu_statistics", json_encode(array()));

			$transaction->commit ();
		} catch (Exception $e) {
			$transaction->rollBack ();
			$result ['success'] = false;
			$result ['message'] = $e->getMessage();
		}
		return $result;
	}//end of DeleteTracking
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 附加  tracking 备注
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param track_no 	string   物流单号  		required
	 * @param remark 	string 	  备注			required
	 +---------------------------------------------------------------------------------------------
	 * @return array  
	 * 					success  boolean  调用 是否成功
	 * 					message  string   调用结果说明
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2015/4/9				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function appendTrackingRemark($track_no , $remark){
		try {
			//get user name
			$userName = Yii::$app->user->identity->getUsername();
			
			//get tracking
			$model = Tracking::findOne(['track_no'=>$track_no]);
			
			//check model empty or not , if empty , return error message
			if (empty($model)){
				$result ['success'] = false;
				$result ['message'] = $track_no . TranslateHelper::t('不是有效的物流号');
				return $result;
			}
			$remarkArr = [];
			// get origin remark and decode it
			if (!empty($model->remark) )
				$remarkArr =  json_decode($model->remark);
			
			//set append remark
			$row['who'] = $userName;
			$row['when'] = date('Y-m-d H:i:s');
			$row['what'] = $remark;
			
			//push new remark into origin remark
			$remarkArr [] = $row;

			//save remark,因为涉及到json encode，特殊字符多，还是用model稳妥一些			
			$affectedRows = 0;
			$models = Tracking::findAll(['track_no'=>$track_no]);
			foreach ($models as $model1){
				$model1->remark = json_encode($remarkArr,true);
				$model1->save(false);
				$affectedRows++;
			}
			
			if ($affectedRows > 0){
			
				$result ['success'] = true;
				$result ['message'] = TranslateHelper::t('添加成功');
				return $result;
			}else{
				$result ['success'] = false;
				
				return $result;
			}
		} catch (Exception $e) {
			$result ['success'] = false;
			$result ['message'] = print_r($e->getMessage(),true);
			return $result;
		}
	}//end of AppendTrackingRemark

	/**
	 +---------------------------------------------------------------------------------------------
	 * 设置某个tracking no 发送站内信失败
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param     $order_id    platform order id
	 * @param     $error       string of 错误信息
	 +---------------------------------------------------------------------------------------------
	 * @return					array ('success' => true, 'message'='')
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		yzq		2015/5/30				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function setMsgSendError($order_id,$error=''){
		$result ['success'] = true;
		$result ['message'] = '';

			//Load the object
		 
			$track_obj = Tracking::find()->where(['order_id'=>$order_id])->asArray()->one();
			if (empty($track_obj)){
				$result ['success'] = false;
				$result ['message'] = 'Failed to Load object for Tracking orderid '.$order_id;
				return $result;
			}
			 
		if (!empty($error)){
			$addi_info = json_decode($track_obj['addi_info'],true);
			$addi_info['send_msg_error'] = $error;
			$track_obj['addi_info'] = json_encode($addi_info);
		}
		
		//msg_sent_error = "Y";
		$command = Yii::$app->subdb->createCommand("update lt_tracking set msg_sent_error='Y', addi_info=:addi_info where order_id  = :order_id"  );
		$command->bindValue(':addi_info', $track_obj['addi_info'], \PDO::PARAM_STR);
		$command->bindValue(':order_id', $order_id, \PDO::PARAM_STR);
		$affectRows = $command->execute();

		return $result;	
	}//end of  function
	
	/**
		 +---------------------------------------------------------------------------------------------
		 * 从准备队列中，提取需要生产track api request 的tracking no，然后批量插入到track queue中
		 * 次个是常驻的job
		 +---------------------------------------------------------------------------------------------
		 * @access static
		 +---------------------------------------------------------------------------------------------
		 +---------------------------------------------------------------------------------------------
		 * @return array
		 * 					success  boolean  调用 是否成功
		 * 					message  string   调用结果说明
		 +---------------------------------------------------------------------------------------------
		 * log			name	date					note
		 * @author		yzq		2015/4/9				初始化
		 +---------------------------------------------------------------------------------------------
		 **/
		static public function postBufferIntoTrackQueue(){
			global $CACHE;
			$now_str = date('Y-m-d H:i:s');
			$rtn['message'] = "";
			$rtn['success'] = true;
			//Step 0, 检查当前本job版本，如果和自己的版本不一样，就自动exit吧
			$currentMainQueueVersion = ConfigHelper::getGlobalConfig("Tracking/postBufferIntoTrackQueueVersion",'NO_CACHE');
			if (empty($currentMainQueueVersion))
				$currentMainQueueVersion = 0;
			
			//如果自己还没有定义，去使用global config来初始化自己
			if (empty(self::$putIntoTrackQueueVersion))
				self::$putIntoTrackQueueVersion = $currentMainQueueVersion;
				
			//如果自己的version不同于全局要求的version，自己退出，从新起job来使用新代码
			if (self::$putIntoTrackQueueVersion <> $currentMainQueueVersion){
				TrackingAgentHelper::markJobUpDown("Trk.PostBufferTrackQueueDown",$CACHE['jobStartTime']);
				DashBoardHelper::WatchMeDown();
				exit("Version new $currentMainQueueVersion , this job ver ".self::$putIntoTrackQueueVersion." exits for using new version $currentMainQueueVersion.");
			}
			//查询 pending records
			$command = Yii::$app->db_queue->createCommand("select * from tracker_generate_request2queue order by user_require_update desc limit 300") ;
			$pendings = $command->queryAll();

			//if no pending one found, return true, message = 'n/a';
			if (empty($pendings) or count($pendings) < 1){
				$rtn['message'] = "n/a";
				$rtn['success'] = true;
				//echo "No pending, idle 4 sec... ";
				return $rtn;
			}

			//step 2, 逐个生成
			$doneIds = array();
			$changedPuid = array();
			$this_puid = 0;
			foreach ($pendings as $aPending){
 
				if (strtoupper($aPending['user_require_update']) == "B"){
					//2015-08-25 kh user_require_update ==   B 就是批量无法交运的情况
					self::generateOneRequestForTracking($aPending['track_no'],true,'',['batchupdate' =>true]);
				}else{
					self::generateOneRequestForTracking($aPending['track_no'], $aPending['user_require_update'] =='Y' );
				}
				
				$changedPuid[$this_puid] = $this_puid;
				$doneIds[] = $aPending['id'];
			}//end of each pending

			//吧Api Request Buffer 的批量insert 到db
			self::postTrackingApiQueueBufferToDb();
			
			$command = Yii::$app->db_queue->createCommand("delete from tracker_generate_request2queue  where id in ( -1,". implode(",", $doneIds) .")");
			$command->execute();
			return $rtn;
		}//end of function putIntoTrackQueue
		
		/**
		 +---------------------------------------------------------------------------------------------
		 * 写申请，想对该track no进行查询申请，写入申请后，会有background job 拿起来帮忙做
		 * 自己本进程就可以尽快返回客户了
		 +---------------------------------------------------------------------------------------------
		 * @access static
		 +---------------------------------------------------------------------------------------------
	 	 * @param Tracking Nos 	           a string of tracking code, or array of many Tracking codes
	 	 * @param User_require_update      an indicator, default false, when true，higher priority will 
	 	 *                                 be adopt when putting in API request Queue                 
		 +---------------------------------------------------------------------------------------------
		 * @return array
		 * 					
		 +---------------------------------------------------------------------------------------------
		 * log			name	date					note
		 * @author		yzq		2015/4/9				初始化
		 +---------------------------------------------------------------------------------------------
		 **/
		static public function putIntoTrackQueueBuffer($track_nos , $user_require_update=false ){
			/*
			//性能 调试 log
			$logTimeMS1=TimeUtil::getCurrentTimestampMS();
			$logMemoryMS1 = (memory_get_usage()/1024/1024);
			*/
			
			if (! in_array($user_require_update, ['Y','B']))
				$user_require_update ="";
			
			$now_str = date('Y-m-d H:i:s');
			$puid = \Yii::$app->subdb->getCurrentPuid();
			//use sql PDO, not Model here, for best performance
			$sql = " replace INTO  `tracker_generate_request2queue` 
					( `puid`, `track_no`,`create_time`,user_require_update) VALUES ";
			
			$sql_values = '';
			if (!is_array($track_nos)){
				$track_no1 = $track_nos;
				$track_nos = array();
				$track_nos[] = $track_no1;
			}
			
			foreach ($track_nos as $track_no){
				$puid = self::removeYinHao($puid);
				$track_no = self::removeYinHao($track_no);
				
				$sql_values .= ($sql_values==''?'':","). "('$puid','$track_no','$now_str','$user_require_update' )";
				if (strlen($sql_values) > 3000){
					//one sql syntax do not exceed 4800, so make 3000 as a cut here
					//避免 memeroy table 爆了，要等等他清空到 小于30000 个才放进去队列
					
					$command = Yii::$app->db_queue->createCommand("select count(1) from tracker_generate_request2queue");
					$QueueDepth = $command->queryScalar();
					while ($QueueDepth > 30000){
						sleep(10);
						$command = Yii::$app->db_queue->createCommand("select count(1) from tracker_generate_request2queue");
						$QueueDepth = $command->queryScalar();
					}
					
					$command = Yii::$app->db_queue->createCommand($sql.$sql_values .";");
					$command->execute();
					$sql_values = '';
				}
			}//end of each track no
			
			/*
			//性能 调试 log
			$logTimeMS2=TimeUtil::getCurrentTimestampMS();
			$logMemoryMS2 = (memory_get_usage()/1024/1024);
			$current_time_cost = $logTimeMS2-$logTimeMS1;
			$current_memory_cost = $logMemoryMS2-$logMemoryMS1;
			$msg = (__FUNCTION__)."   ,t1_2=".($current_time_cost).",memory=".($current_memory_cost)."M ";
			\Yii::info(['Tracking',__CLASS__,__FUNCTION__,'Background',$msg],"edb\global");
			*/
			//\Yii::info(['Tracking',__CLASS__,__FUNCTION__,'Background',"SQL to be execute:".$sql.$sql_values ],"edb\global");
			if ($sql_values <> ''){
				$command = Yii::$app->db_queue->createCommand($sql.$sql_values.";");
				$command->execute();
			}
			/*
			//性能 调试 log
			$logTimeMS3=TimeUtil::getCurrentTimestampMS();
			$logMemoryMS3 = (memory_get_usage()/1024/1024);
			$current_time_cost = $logTimeMS3-$logTimeMS2;
			$current_memory_cost = $logMemoryMS3-$logMemoryMS2;
			$msg = (__FUNCTION__)."   ,t2_3=".($current_time_cost).",memory=".($current_memory_cost)."M ";
			\Yii::info(['Tracking',__CLASS__,__FUNCTION__,'Background',$msg],"edb\global");
			*/
		}//end of function wantIntoTrackQueue
	
		/**
		 +---------------------------------------------------------------------------------------------
		 * 把Tracking：：buffer 里面的要新建的Tracking 读取，批量insert 到数据库中
		 +---------------------------------------------------------------------------------------------
		 * @access static
		 +---------------------------------------------------------------------------------------------
		 +---------------------------------------------------------------------------------------------
		 * @return array
		 *
		 +---------------------------------------------------------------------------------------------
		 * log			name	date					note
		 * @author		yzq		2015/4/9				初始化
		 +---------------------------------------------------------------------------------------------
		 **/
		static public function postTrackingBufferToDb(){	 
			//use sql PDO, not Model here, for best performance
			/*
			$sql = " INSERT INTO  `lt_tracking`
					( `order_id`, seller_id, `track_no`,`status`,state,source,platform,
					batch_no,create_time,update_time,ship_by,delivery_fee,
					ship_out_date,addi_info) VALUES ";
*/
			$updateFields = array('order_id'=>1,'seller_id'=>1, 'track_no'=>1,
					 'status'=>1, 'state'=>1, 'source'=>1, 'platform'=>1,
					'batch_no'=>1, 'create_time'=>1, 'update_time'=>1, 'ship_by'=>1,
					'ship_out_date'=>1 , 'delivery_fee'=>1, 'addi_info'=>1
			  );
			$Trackings = Tracking::$Insert_Data_Buffer;
			Tracking::$Insert_Data_Buffer = array();
			
			SQLHelper::groupInsertToDb(Tracking::tableName(), $Trackings,'subdb', $updateFields);

		}//end of function postTrackingBufferToDb
		
			
		/**
		 +---------------------------------------------------------------------------------------------
		 * 把Buffer data 放到 Tracking Queue db里面 ，批量insert 到数据库中
		 +---------------------------------------------------------------------------------------------
		 * @access static
		 +---------------------------------------------------------------------------------------------
		 +---------------------------------------------------------------------------------------------
		 * @return array
		 *
		 +---------------------------------------------------------------------------------------------
		 * log			name	date					note
		 * @author		yzq		2015/4/9				初始化
		 +---------------------------------------------------------------------------------------------
		 **/
		static public function postTrackingApiQueueBufferToDb(){			
			//use sql PDO, not Model here, for best performance
			global $CACHE;
			$sql = " INSERT INTO  `tracker_api_queue`".
					"( `priority`, `puid`,`track_no`,status,candidate_carriers,".
					"selected_carrier, create_time,update_time,addinfo ) VALUES ";
		
			$TrackingQueueReqs = self::$Insert_Api_Queue_Buffer;
			self::$Insert_Api_Queue_Buffer = array();
			
			$updateFields = array('priority'=>1,'puid'=>1, 'track_no'=>1,
					'status'=>1, 'candidate_carriers'=>1, 'selected_carrier'=>1, 
					'create_time'=>1,'update_time'=>1, 'addinfo'=>1 
			);
		 
			
			
			SQLHelper::groupInsertToDb(TrackerApiQueue::tableName(), $TrackingQueueReqs,'db_queue', $updateFields);
			
		}//end of function postTrackingBufferToDb

		public static function removeYinHao($keyword){
			$keyword = str_replace("'","`",$keyword);
			$keyword = str_replace('"',"`",$keyword);
			return $keyword;
		}
		
		public static function healthCheckEach(){
		
		}
		
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 获取 账号的相关信息
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param     $platform			平台  eg : all , ebay , aliexpress
	 +---------------------------------------------------------------------------------------------
	 * @return						$data [
	 * 									0 =>[
	 * 									id => 对应 lt_tracking.sellerid
	 * 									platform=> 平台
	 *									name=> 账号名 ]...... ]
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2015/5/16				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function getAccountFilterData($platform='all'){
	 
		$uid = \Yii::$app->subdb->getCurrentPuid(); //ystest
		//权限过滤	//liang
		$AllAuthorizePlatformAccounts = UserHelper::getUserAllAuthorizePlatformAccountsArr();
		$result = [];
		if (in_array(strtolower($platform),['ebay','all'])){
			if(!empty($AllAuthorizePlatformAccounts['ebay'])){
			//$ebayUserList  = EbayAccountsApiHelper::helpList('expiration_time' , 'asc');
				$ebayUserList = SaasEbayUser::find()->where("uid = '$uid'")->andWhere(['selleruserid'=>array_keys($AllAuthorizePlatformAccounts['ebay'])])
				->asArray()->all();
				foreach($ebayUserList as $row){
					$account = [];
					$account['id'] = $row['ebay_uid'];
					$account['name'] = $row['selleruserid'];
					$account['platform'] = 'ebay'; 
					$result[] = $account;
				}
			}
		}
		
		if (in_array(strtolower($platform),['aliexpress','all'])){
			if(!empty($AllAuthorizePlatformAccounts['aliexpress'])){
				$AliexpressUserList = SaasAliexpressUser::find()->where('uid ='.$uid)
				->andWhere(['sellerloginid'=>array_keys($AllAuthorizePlatformAccounts['aliexpress'])])
				->orderBy('refresh_token_timeout desc')
				->asArray()
				->all();
				
				foreach($AliexpressUserList as $row){
					$account = [];
					$account['id'] = $row['aliexpress_uid'];
					$account['name'] = $row['sellerloginid'];
					$account['store_name'] = $row['store_name'];
					$account['platform'] = 'aliexpress';
					$result[] = $account;
				}
			}
		}
		
		if (in_array(strtolower($platform),['dhgate','all'])){
			if(!empty($AllAuthorizePlatformAccounts['dhgate'])){
				$DhgateUserList = SaasDhgateUser::find()->where('uid ='.$uid)
				->andWhere(['sellerloginid'=>array_keys($AllAuthorizePlatformAccounts['dhgate'])])
				->orderBy('refresh_token_timeout desc')
				->asArray()
				->all();
					
				foreach($DhgateUserList as $row){
					$account = [];
					$account['id'] = $row['dhgate_uid'];
					$account['name'] = $row['sellerloginid'];
					$account['platform'] = 'dhgate';
					$result[] = $account;
				}
			}
		}
		
		if (in_array(strtolower($platform),['wish','all'])){
			if(!empty($AllAuthorizePlatformAccounts['wish'])){
				$wishUserList = SaasWishUser::find()->where(['uid'=>$uid , 'is_active'=>'1'])
				->andWhere(['store_name'=>array_keys($AllAuthorizePlatformAccounts['wish'])])
				->asArray()->all();
				
				foreach($wishUserList as $row){
					$account = [];
					$account['id'] = $row['site_id'];
					$account['name'] = $row['store_name'];
					$account['platform'] = 'wish';
					$result[] = $account;
				}
			}
		}
		
		if (in_array(strtolower($platform),['lazada','all'])){
			if(!empty($AllAuthorizePlatformAccounts['lazada'])){
				$lazdaaUserList = SaasLazadaUser::find()->where(['puid'=>$uid , 'status'=>'1'])
				->andWhere(['platform_userid'=>array_keys($AllAuthorizePlatformAccounts['lazada'])])
				->asArray()->all();
				
				foreach($lazdaaUserList as $row){
					$account = [];
					$account['id'] = $row['lazada_uid'];
					$account['name'] = $row['platform_userid'];
					$account['platform'] = 'lazada';
					$result[] = $account;
				}
			}
		}
		
		if (in_array(strtolower($platform),['cdiscount','all'])){
			if(!empty($AllAuthorizePlatformAccounts['cdiscount'])){
				$lazdaaUserList = SaasCdiscountUser::find()->where(['uid'=>$uid , 'is_active'=>'1'])
				->andWhere(['username'=>array_keys($AllAuthorizePlatformAccounts['cdiscount'])])
				->asArray()->all();
					
				foreach($lazdaaUserList as $row){
					$account = [];
					$account['id'] = $row['site_id'];
					$account['name'] = $row['username'];
					//$account['store_name'] = $row['store_name'];
					$account['platform'] = 'cdiscount';
					$result[] = $account;
				}
			}
		}
		
		if (in_array(strtolower($platform),['amazon','all'])){
			if(!empty($AllAuthorizePlatformAccounts['amazon'])){
				$amzUserList = SaasAmazonUser::find()->where(['uid'=>$uid , 'is_active'=>'1'])
				->andWhere(['merchant_id'=>array_keys($AllAuthorizePlatformAccounts['amazon'])])
				->asArray()->all();
			
				foreach($amzUserList as $row){
					$account = [];
					$account['id'] = $row['amazon_uid'];
					$account['merchant_id'] = $row['merchant_id'];
					$account['name'] = $row['store_name'];
					//$account['store_name'] = $row['store_name'];
					$account['platform'] = 'amazon';
					$result[] = $account;
				}
			}
		}
		
		return $result;
	}//end of getAccountFilterData
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 获取 账号的编号 与名称 的对应 
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param     $platform			平台  eg : all , ebay , aliexpress
	 +---------------------------------------------------------------------------------------------
	 * @return						$data [
	 * 									platform =>[
	 * 									id => name
	 * 									 ]...... ]
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2015/5/16				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function getAccountMappingNameData($platform='all'){
		$target = self::getAccountFilterData($platform);
		$result = [];
		foreach($target as $row){
			$result[$row['platform']][$row['id']] = $row['name'];
		}
		unset($target);
		return $result;
	}//end of getAccountMappingNameData
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 获取 账号的编号 与名称 的对应
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param     $platform			平台  eg : all , ebay , aliexpress
	 +---------------------------------------------------------------------------------------------
	 * @return						$data [
	 * 									platform =>[
	 * 									name  => id
	 * 									 ]...... ]
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2015/5/16				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function getAccountMappingIDData($platform='all'){
		$target = self::getAccountFilterData($platform);
		$result = [];
		foreach($target as $row){
			$result[$row['platform']][$row['name']] = $row['id'];
		}
		unset($target);
		return $result;
	}//end of getAccountMappingNameData
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 智能 获取上一次使用的 template id
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param     $platform			平台  eg : all , ebay , aliexpress
	 +---------------------------------------------------------------------------------------------
	 * @return						$data [
	 * 									0 =>[
	 * 									id => 对应 lt_tracking.sellerid
	 * 									platform=> 平台
	 *									name=> 账号名 ]...... ]
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2015/5/16				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function getDefaultTemplate($track_no_list){
		$TrackingData = Tracking::find()->andWhere(['track_no'=>$track_no_list])->asArray()->One();
		$platform = empty($TrackingData['platform'])?"na":$TrackingData['platform'];
		$seller_id = empty($TrackingData['seller_id'])?"na":$TrackingData['seller_id'];
		$status = empty($TrackingData['status'])?"na":$TrackingData['status'];
		//step 1 platform + sellerid + 状态
		$pathlist[] = 'Tracking/DT_'.$platform.'_'.$seller_id.'_'.$status;
		//step 2 platform + 状态
		$pathlist[] = 'Tracking/DT_'.$platform.'_'.$status;
		//step 3 sellerid + 状态
		$pathlist[] = 'Tracking/DT_'.$seller_id.'_'.$status;
		//step 4 状态
		$pathlist[] = 'Tracking/DT_'.$status;
		foreach($pathlist as $path){
			$result = self::getTrackerTempDataFromRedis($path );
			if (!empty($result))
				return ['path'=>$path , 'template_id'=>$result];
		}
		return ['path'=>'Tracking/DT_'.$platform.'_'.$seller_id.'_'.$status , 'template_id'=>1];
	}//end of getDefaultTemplate
	
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 根据 传递的状态  获取 通知字段名  当不传递状态时则返回映射数组
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param     $status			状态   eg : received , arrived_pending_fetch , rejected
	 +---------------------------------------------------------------------------------------------
	 * @return						string or array 
	 * 								received_notified or [
	 * 														'received'=> 'received_notified',
	 * 														'arrived_pending_fetch'=>'pending_fetch_notified',
	 * 														'rejected'=>'rejected_notified',
	 * 														'shipping'=>'shipping_notified',
	 * 													];
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2015/5/16				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function getNotifiedFieldNameByStatus($status=''){
		$NOTIFIED_STATUS_MAPPING = [
		'received'=> 'received_notified',
		'arrived_pending_fetch'=>'pending_fetch_notified',
		'delivery_failed'=>'delivery_failed_notified',
		'rejected'=>'rejected_notified',
		'shipping'=>'shipping_notified',
		];
		
		if (!empty($status)){
			if (!empty($NOTIFIED_STATUS_MAPPING[$status])){
				return $NOTIFIED_STATUS_MAPPING[$status];
			}else{
				return '';
			}
		}else{
			return $NOTIFIED_STATUS_MAPPING;
		}
	}//end of getNotifiedFieldNameByStatus
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 根据 传递的包裹名  获取 对应的状态  当不传递 包裹名  时则返回映射数组
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param     $Parcel			状态   eg : completed_parcel , arrived_pending_fetch_parcel , rejected_parcel
	 +---------------------------------------------------------------------------------------------
	 * @return						string or array
	 * 								received or [
	 * 														'completed_parcel'=>'received' ,
	 * 														'arrived_pending_fetch_parcel'=>'arrived_pending_fetch' ,
	 * 														'rejected_parcel'=>'rejected' ,
	 * 														'shipping_parcel'=>'shipping' ,
	 * 													];
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2015/5/16				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function getStatusByParcel($Parcel=''){ 
		$pacel_mapping = [
		'completed_parcel'=>'received' ,
		'arrived_pending_fetch_parcel'=>'arrived_pending_fetch' ,
		'delivery_failed_parcel'=>'delivery_failed' ,
		'rejected_parcel'=>'rejected' ,
		'shipping_parcel'=>'shipping' ,
		];
		
		if (!empty($Parcel)){
			if (!empty($pacel_mapping[$Parcel])){
				return $pacel_mapping[$Parcel];
			}else{
				return '';
			}
		}else{
			return $pacel_mapping;
		}
	}//end of getStatusByParcel
	
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 根据 传递的track no 判断是否 为可以发送站内信
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param     $TrackNoList		string/array			物流号   eg : '123' or ['123','124']
	 +---------------------------------------------------------------------------------------------
	 * @return						string or array
	 * 								'123' or ['123','124']
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2015/6/5				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function getActiveTrackNo($TrackNoList){
		//目前只有ebay 与 aliexpress支持站内信
		$result = Tracking::find()
					->select(['track_no'])
					->andWhere(['track_no'=>$TrackNoList])
					->andWhere([ 'platform'=>['ebay','aliexpress','amazon','cdiscount']])
					->andWhere(' LENGTH(order_id) >0')
					->andWhere(' LENGTH(seller_id) >0')
					->asArray()->all();
		
		$rt = [];
		foreach($result as $row){
			$rt[] = $row['track_no'];
		}
		return $rt;
	}//end of getTrackNoExistOrderId
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 获取发信模板list
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param   	$sort
	 * @param		$order
	 +---------------------------------------------------------------------------------------------
	 * @return		array
	 +---------------------------------------------------------------------------------------------
	 * log			name		date			note
	 * @author		lzhl		2015/7/24		初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function getMsgTemplate($sort,$order){
		$data=[];
		$query = MsgTemplate::find()->where(['not',['id'=>0]]);
		if(!empty($sort) && !empty($order))
			$query->orderBy("$sort $order");
		
		$pagination = new Pagination([
				'defaultPageSize' => 20,
				'totalCount' => $query->count(),
				'pageSizeLimit'=>[5,200],
				]);
		$query->limit($pagination->limit);
		$query->offset($pagination->offset);
		
		$data['data']=$query->asArray()->all();
		$data['pagination']=$pagination;
		
		return $data;
	}//end of getMsgTemplate

	/**
	 +---------------------------------------------------------------------------------------------
	 * 删除发信模板
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param   	array|$ids
	 +---------------------------------------------------------------------------------------------
	 * @return		array
	 +---------------------------------------------------------------------------------------------
	 * log			name		date			note
	 * @author		lzhl		2015/7/25		初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function deleteMsgTemplate($ids){
		$result['success']=true;
		$result['message']='';
		
		try{
			MsgTemplate::deleteAll(['in','id',$ids]);
		}catch (Exception $e) {
			$result['success'] = false;
			$result['message'] = $e->getMessage();
		}
		return $result;
	}//end of deleteMsgTemplate
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 记下 站内信的 当前布局编号 和 推荐 商品数
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param     $TrackNoList		string/array			物流号   eg : '123' or ['123','124']
	 * 			  $LayOutId			int						布局编号 eg:1,2,3
	 * 			  $ReComProdCount	int						推荐 商品数    eg:1,2,3		
	 +---------------------------------------------------------------------------------------------
	 * @return						na
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2015/7/22				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function setMessageConfig($TrackNoList , $LayOutId =1 , $ReComProdCount=8, $ReComGroup=0){
		$query = Tracking::find();
		$result = $query->andWhere(['track_no'=>$TrackNoList])->all();
		foreach($result as $row){
			//防止旧数据丢失
			$row['addi_info'] = str_ireplace('`', '"', $row['addi_info']);
			$addi_info = json_decode($row['addi_info'],true);
			if (isset($addi_info['layout_id'])) unset($addi_info['layout_id']);
			$addi_info['layout'] = $LayOutId;
			$addi_info['recom_prod_count'] = $ReComProdCount;
			$addi_info['recom_prod_group'] = $ReComGroup;
			
			$row['addi_info'] = json_encode($addi_info);
			if (! $row->save()){
				$message =  [ 'param'=>[$TrackNoList , $LayOutId , $ReComProdCount] , 'message'=>$row->errors];
				$message = json_encode($message);
				//保存失败 , 记录当然的参数
				\Yii::error(['Tracking',__CLASS__,__FUNCTION__,'Online',$message],"edb\global");
			}
		}
	}//end of setMessageConfig
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 记下 站内信的 当前布局编号 和 推荐 商品数 BY OMS
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param     $orderIdList		string/array			物流号   eg : '123' or ['123','124']
	 * 			  $LayOutId			int						布局编号 eg:1,2,3
	 * 			  $ReComProdCount	int						推荐 商品数    eg:1,2,3
	 +---------------------------------------------------------------------------------------------
	 * @return						na
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2015/7/22				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function setMessageConfigByOms($orderIdList , $LayOutId =1 , $ReComProdCount=8, $ReComGroup=0){
		//step1  保存设置到order表
		$query = OdOrder::find();
		$result = $query->where(['order_source_order_id'=>$orderIdList])->all();
		$orderNoList=[];
		foreach($result as $row){
			$orderNoList[] = $row->order_source_order_id;
			if(empty($row->addi_info))
				$addi_info = json_decode($row->addi_info,true);
			if(empty($addi_info))
				$addi_info = [];
			$addi_info['layout'] = $LayOutId;
			$addi_info['recom_prod_count'] = $ReComProdCount;
			$addi_info['recom_prod_group'] = $ReComGroup;
			
			$row->addi_info = json_encode($addi_info);
			if (! $row->save()){
				$message =  [ 'param'=>[$orderIdList , $LayOutId , $ReComProdCount, $ReComGroup] , 'message'=>$row->errors];
				$message = json_encode($message);
				//保存失败 , 记录当时的参数
				\Yii::error(['Order',__CLASS__,__FUNCTION__,'Online',$message],"edb\global");
			}
		}
		
		//step2 保存设置到lt_tracking
		$result = Tracking::find()->where(['order_id'=>$orderNoList])->all();
		foreach($result as $row){
			//防止旧数据丢失
			$addi_info = str_ireplace('`', '"', $row->addi_info);
			$addi_info = json_decode($addi_info,true);
			if (isset($addi_info['layout_id'])) unset($addi_info['layout_id']);
			$addi_info['layout'] = $LayOutId;
			$addi_info['recom_prod_count'] = $ReComProdCount;
			$addi_info['recom_prod_group'] = $ReComGroup;
				
			$row->addi_info = json_encode($addi_info);
			if (! $row->save()){
				$message =  [ 'param'=>[$orderIdList , $LayOutId , $ReComProdCount, $ReComGroup] , 'message'=>$row->errors];
				$message = json_encode($message);
				//保存失败 , 记录当然的参数
				\Yii::error(['Tracking',__CLASS__,__FUNCTION__,'Online',$message],"edb\global");
			}
		}
	}//end of setMessageConfig
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 指定 track no 然后从规则库中逐一将其归类成 匹配成功或 匹配失败两种情况
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param     $TrackNoList		string/array			物流号   eg : '123' or ['123','124']
	 * 			
	 +---------------------------------------------------------------------------------------------
	 * @return						['matchRoleTracking'=>[0=>['track_no'=>'123',
	 *															'role_name'=>'role name',
	 *															'platform'=>'ebay', // ebay , wish , aliexpress , dhgate ... 
	 *															'order_id'=>'123',
	 *															'nation'=>'中国',
	 *															'template_id'=>1, ],..... ] , 
	 * 								'unMatchRoleTracking'=>[0=>['track_no'=>'123',
	 *															'role_name'=>'role name',
	 *															'platform'=>'ebay', // ebay , wish , aliexpress , dhgate ... 
	 *															'order_id'=>'123',
	 *															'nation'=>'中国', ] , .....]	
	 * 									]
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2015/7/31				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function matchMessageRole($TracknoList){
		$query = Tracking::find();
		//$trackInfo =  $query->andWhere(['track_no'=>$TracknoList])->asArray()->all();
		$trackInfo =  $query->andWhere(['track_no'=>$TracknoList])->all();
		
		$AccountMapping = self::getAccountMappingIDData();
		$data['matchRoleTracking'] = [];
		$data['unMatchRoleTracking'] = [];
		$order_source_arr = [];//所有订单来源平台
		$order_seller_arr = [];//所有订单来源seller
		$isSameSeller = false;//判断订单是否是同一个卖家账号
		foreach($trackInfo as $tracking ){
			if (!empty($AccountMapping[$tracking['platform']][$tracking['seller_id']]))
				$account_id = $AccountMapping[$tracking['platform']][$tracking['seller_id']];
			else{
				$account_id = 0;
			}
			/**/
			
			$tmp_platform = (!empty($tracking['platform']))?$tracking['platform']:'';
			if (empty($tracking['to_nation']) || $tracking['to_nation'] =='--')
				$tracking['to_nation'] = $tracking->getConsignee_country_code();
			$tmp_to_nation = (!empty($tracking['to_nation']) && empty($tmp_to_nation) )?$tracking['to_nation']:'';
			$tmp_to_nation = (!empty($tracking['to_nation']))?$tracking['to_nation']:'';
			
			if(!in_array($tmp_platform, $order_source_arr))
				$order_source_arr[] = $tmp_platform;
			if(!in_array($account_id, $order_seller_arr))
				$order_seller_arr[] = $account_id;
			
			$tmp_status = (!empty($tracking['status']))?$tracking['status']:'';
			$role = MessageHelper::getTopTrackerAuotRule($tmp_platform, $account_id, $tmp_to_nation, $tmp_status);
			//echo $tracking['platform']." =". $account_id." =". $tracking['to_nation']." =". $tracking['status'].'<br>';
			if (!empty($role['name']))
				$roleName = $role['name'];
			else
				$roleName ='';
				
			$roleName = MessageHelper::getTopTrackerAuotRuleName($tracking['platform'], $account_id, $tracking['to_nation'], $tracking['status']);
			//echo $tracking['track_no'].":".$tracking['platform']." =". $account_id." =". $tracking['to_nation']." =". $tracking['status'].'<br>';
			if (!empty($role['template_id'] ))
				$templateId = $role['template_id'] ;
			else
				$templateId = 0;
			if (!empty($roleName)){
				//matched role
				$data['matchRoleTracking'][] = [
				'track_no'=>$tracking['track_no'],
				'role_name'=>$roleName,
				'platform'=>$tracking['platform'],
				'order_id'=>$tracking['order_id'],
				'nation'=>$label = self::autoSetCountriesNameMapping($tracking['to_nation']),
				'template_id'=>$templateId,
				];
			}else{
				//unmatch role
				$data['unMatchRoleTracking'][] = [
				'track_no'=>$tracking['track_no'],
				'role_name'=>'',
				'platform'=>$tracking['platform'],
				'order_id'=>$tracking['order_id'],
				'nation'=>self::autoSetCountriesNameMapping($tracking['to_nation']),
				];
			}
		}
		if(count($order_source_arr)==1 && count($order_seller_arr)==1)
			$isSameSeller = true;
		$data['isSameSeller'] = $isSameSeller;
		
		return $data;
	}//end of matchMessageRole
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 校验日期格式是否正确
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param string $date 日期
	 * @param string $formats 需要检验的格式数组
	 +---------------------------------------------------------------------------------------------
	 * @return boolean
	 *
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2015/2/14				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function batchUpdateUnshipParcel(){
		$params = [];
		//无法交运 的条件
		$params = Tracking::getTrackingConditionByClassification ('unshipped_parcel');
		/*
		//性能 调试 log
		$logTimeMS1=TimeUtil::getCurrentTimestampMS();
		$logMemoryMS1 = (memory_get_usage()/1024/1024);
		*/
		
		//最近90天内新建物流的条件
		$RecentDate = date('Y-m-d',strtotime('-90 day'));;
		$RecentDateCondition = ['>=','create_time', $RecentDate];
		$UnshipParcelList = Tracking::find()
			->select(['track_no'])
			->andWhere($params)
			->andWhere($RecentDateCondition)
			->asArray()
			->all();
		/*
		//性能 调试 log
		$logTimeMS2=TimeUtil::getCurrentTimestampMS();
		$logMemoryMS2 = (memory_get_usage()/1024/1024);
		$current_time_cost = $logTimeMS2-$logTimeMS1;
		$current_memory_cost = $logMemoryMS2-$logMemoryMS1;
		$msg = (__FUNCTION__)."   ,t1_2=".($current_time_cost).",memory=".($current_memory_cost)."M ";
		\Yii::info(['Tracking',__CLASS__,__FUNCTION__,'Background',$msg],"edb\global");
		*/
		
		
		$track_nos = [];
		//格式 化结果
		foreach($UnshipParcelList as $row){
			$track_nos[] = $row['track_no'];
			$row = []; //release memory
		}
		self::putIntoTrackQueueBuffer($track_nos , 'B');
		/*
		//性能 调试 log
		$logTimeMS3=TimeUtil::getCurrentTimestampMS();
		$logMemoryMS3 = (memory_get_usage()/1024/1024);
		$current_time_cost = $logTimeMS3-$logTimeMS2;
		$current_memory_cost = $logMemoryMS3-$logMemoryMS2;
		$msg = (__FUNCTION__)."   ,t2_3=".($current_time_cost).",memory=".($current_memory_cost)."M ";
		\Yii::info(['Tracking',__CLASS__,__FUNCTION__,'Background',$msg],"edb\global");
		*/
	}//end of batchUpdateUnshipParcel

	static public function summaryForThisAccountForLastNdays($app='Tracker',$platform="",$seller_id="",$puid=0){
		$now_date = date('Y-m-d');

		if (empty($seller_id))
			$seller_id = '';
		
		if ($puid == 0)
			$puid = \Yii::$app->subdb->getCurrentPuid();
		
		$days_10_ago = date('Y-m-d',strtotime('-10 days'));

		$command= Yii::$app->db->createCommand(//run_time = -10, 也就是估计可以通过smt api 搞定的，所以早点做掉
		"select * from ut_app_summary_daily   where app='Tracker' and platform='$platform' and puid=$puid and  
				seller_id =:seller_id and thedate >='$days_10_ago'
								 ");
		$command->bindValue(':seller_id', $seller_id, \PDO::PARAM_STR);
		$recent_10_days = $command->queryAll();
		
		$recent_10_days_Sorted = [];
		foreach($recent_10_days as $aDayRec){
			$recent_10_days_Sorted[$aDayRec['thedate']] = $aDayRec;
		}
		
		//对近十天的统计数据，看看有没有现成的，没有就立刻做
		for($i=1;  $i<=10 ; $i++){
			$targetDate = date('Y-m-d',strtotime('-'.$i.' days'));
			if (isset($recent_10_days_Sorted[$targetDate]))
				continue;

			$command= Yii::$app->subdb->createCommand(//run_time = -10, 也就是估计可以通过smt api 搞定的，所以早点做掉
				"select count(1) from lt_tracking where platform='$platform' and 
					 seller_id =:seller_id and date(create_time)='$targetDate' ");
			$command->bindValue(':seller_id', $seller_id, \PDO::PARAM_STR);
			$totalCount = $command->queryScalar();
				
			$command= Yii::$app->db->createCommand("insert into ut_app_summary_daily (app,platform,puid,seller_id,thedate,create_count) values
				('Tracker','$platform','$puid',:seller_id,'$targetDate',$totalCount)");
			$command->bindValue(':seller_id', $seller_id, \PDO::PARAM_STR);
			$command->execute();	
		}
	} 
	
	//注册需要推送这个物流状态到 oms去
	public static function pushToOMS($puid, $order_id,$status,$last_event_date){
		$puid0 = \Yii::$app->subdb->getCurrentPuid();
		
		 
		if (empty($last_event_date))
			$last_event_date = "2012-01-01"; //hardcode in case this is empty
		
		Yii::$app->subdb->createCommand( 
		//如果原来就有有效的status，并且这次的status是失败的，就不要用失败的覆盖有效的了
			"update od_order_v2 set logistic_status='$status', logistic_last_event_time='$last_event_date' 
			where order_source_order_id = '$order_id' and ( logistic_status is NULL or 
			not ('$status' in ('untrackable','expired','no_info','suspend') and logistic_status
			not in ('untrackable','expired','no_info','suspend','checking','')	
				) 
			)")
			->execute();
	}
	
	public static function getTrackerTempDataFromRedis($key,$puid1=0){
		//获取当前 用户的puid
		if ($puid1 > 0)
			$puid = $puid1;
		else
			$puid = \Yii::$app->subdb->getCurrentPuid();
		
		$classification = "Tracker_AppTempData";
		//return RedisHelper::RedisGet($classification,"user_$puid".".".$key);
		$TrackerTempData =  RedisHelper::RedisGet($classification,"user_$puid".".".$key);
		
		if(empty($TrackerTempData) && $key=='using_carriers'){
			//值为空时，即刻初始化		lzhl 	2017-02-27
			$using_carriers = array();
			$allCarriers = Yii::$app->subdb->createCommand(//run_time = -10, 也就是估计可以通过smt api 搞定的，所以早点做掉
					"select distinct ship_by from lt_tracking   ")->queryAll();
			foreach ($allCarriers as $aCarrier){
				$using_carriers[ $aCarrier['ship_by']  ] = $aCarrier['ship_by'] ;
			}
			TrackingHelper::setTrackerTempDataToRedis("using_carriers", json_encode($using_carriers),$puid);
			$TrackerTempData = RedisHelper::RedisGet($classification,"user_$puid".".".$key);
		}
		return $TrackerTempData;
	}
	
	/*
	 * 封装 更新tracker quota 到 reids
	 */
	public static function addTrackerQuotaToRedis($key,$val,$puid1=0){
		//获取当前 用户的puid
		if ($puid1 > 0)
			$puid = $puid1;
		else
			$puid = \Yii::$app->subdb->getCurrentPuid();
		
		$classification = "Tracker_AppTempData";
		return RedisHelper::RedisAdd($classification,"user_$puid".".".$key,$val);
		 
	}//end of function addTrackerQuotaToRedis
	
	public static function setTrackerTempDataToRedis($key,$val,$puid1=0){
		//获取当前 用户的puid
		if ($puid1 > 0)
			$puid = $puid1;
		else
			$puid = \Yii::$app->subdb->getCurrentPuid();
		
		$classification = "Tracker_AppTempData";
		return RedisHelper::RedisSet($classification,"user_$puid".".".$key,$val);
		 
	}
	
	public static function delTrackerTempDataToRedis($key){
		//获取当前 用户的puid
		$puid = \Yii::$app->subdb->getCurrentPuid();
		$classification = "Tracker_AppTempData";
		return RedisHelper::RedisDel($classification,"user_$puid".".".$key);
	}
	
	static public function queueHandlerProcessing1($target_track_no=''){
		return TrackingQueueHelper::queueHandlerProcessing1($target_track_no);
	}
	
	static public function subqHandlerByCarrierNon17Track($sub_id1='' ){
		return TrackingQueueHelper::subqHandlerByCarrierNon17Track($sub_id1  );
	}
	
	static public function subqHandlerByCarrier17Track($sub_id1='' ){
		return TrackingQueueHelper::subqHandlerByCarrier17Track($sub_id1  );
	}
	
	
	public static function getTrackerChartDataByUid($uid,$days){
		$today = date('Y-m-d');
		$daysAgo = date('Y-m-d', strtotime($today)-3600*24*$days);
		//用户近目标日期内所有数据
		$query = "SELECT * FROM `ut_app_summary_daily` WHERE `puid`=$uid and `thedate`>='".$daysAgo."' and `thedate`<'".$today."'";
		$command = Yii::$app->db->createCommand($query);
		$records = $command->queryAll();

		$chart['type'] = 'column';
		$chart['title'] = '近'.$days.'日Tracker物流号数';
		$chart['subtitle'] = '';
		$chart['xAxis'] = [];
		$chart['yAxis'] = '物流单数';
		$chart['series'] = [];
		
		$series = [];
		for ($i=$days;$i>=0;$i--){
			$total=0;
			$theday = date('Y-m-d', strtotime($today)-3600*24*$i);
			if($i==0){
				$chart['xAxis'][] = '今日';
				$total = self::getTrackerTempDataFromRedis(date('Y-m-d')."_inserted");
				if (empty($total))
					$total = 0;
				//$chart['series'][] = ['name'=>'今日','datqa'=>$total];
				$series[] = $total;
			}
			else{
				$chart['xAxis'][] = date('m-d', strtotime($theday));//只显示月，日
				foreach ($records as &$record){
					if($record['thedate']==$theday){
						$total += (int)$record['create_count'];
						unset($record);
					}
				}
				//$chart['series'][] = ['name'=>$theday,'data'=>$total];
				$series[] = $total;
			}
		}
		$chart['series'][]=['name'=>'所有账号合计','data'=>$series];
		
		return $chart;
	}
	/**
	 * 获取tracker dash-board广告
	 * @param unknown	$uid
	 * @param number 	$every_time_shows	每次展示广告数
	 */
	public static function getAdvertDataByUid($uid,$every_time_shows=2){
		$advertData = [];
		$last_advert_id = RedisHelper::RedisGet('Tracker_DashBoard',"user_$uid".".last_advert");
		if(empty($last_advert_id))
			$last_advert_id=0;
		$new_last_advert_id = $last_advert_id;
		if(!empty($last_advert_id)){
			$query = "SELECT * FROM `od_dash_advert` WHERE (`app`='Tracker') and `id`>".(int)$last_advert_id."  ORDER BY `id` ASC limit 0,$every_time_shows ";
			$command = Yii::$app->db->createCommand($query);
			$advert_records = $command->queryAll();
			foreach ($advert_records as $advert){
				$advertData[$advert['id']] = $advert;
				$new_last_advert_id = $advert['id'];
			}
			if(count($advertData)<$every_time_shows){
				$reLimit = $every_time_shows - count($advertData);
				$query_r = "SELECT * FROM `od_dash_advert` WHERE `app`='Tracker' ORDER BY `id` ASC limit 0,$reLimit ";
				$command = Yii::$app->db->createCommand($query_r);
				$advert_records_r = $command->queryAll();
				foreach ($advert_records_r as $advert_r){
					if(in_array($advert_r['id'],array_keys($advertData)))
						continue;
					$advertData[$advert_r['id']] = $advert_r;
					$new_last_advert_id = $advert_r['id'];
				}
			}
		}else{
			$query = "SELECT * FROM `od_dash_advert` WHERE `app`='Tracker'  ORDER BY `id` ASC limit 0,$every_time_shows ";
			$command = Yii::$app->db->createCommand($query);
			$advert_records = $command->queryAll();
			foreach ($advert_records as $advert){
				$advertData[$advert['id']] = $advert;
				$new_last_advert_id = $advert['id'];
			}
		}
	
		$set_advert_redis = RedisHelper::RedisSet('Tracker_DashBoard',"user_$uid".".last_advert",$new_last_advert_id);
		return $advertData;
	}
	
	public static function saveCase($uid,$data){
		$result['success']=true;
		$result['message']='';
		$track_no = empty($data['track_no'])?'':$data['track_no'];
		$order_id = empty($data['order_id'])?'':$data['order_id'];
		if(empty($track_no)){
			$result['success']=false;
			$result['message']='E001：运单号信息缺失！';
		}
			
		$carrier_type = !isset($data['carrier_type'])?'':$data['carrier_type'];
		$customer_url = empty($data['customer_url'])?'':$data['customer_url'];
		if($carrier_type=='' || empty($customer_url)){
			$result['success']=false;
			$result['message']='E002：物流方式或查询平台不能为空！';
			return $result;
		}
		$desc = trim($data['desc']);
		try{
			$query = "SELECT * FROM `tracker_cases` WHERE `uid`=$uid and `track_no`=:track_no ";
			$command = Yii::$app->db->createCommand($query);
			$command->bindValue(':track_no', $track_no, \PDO::PARAM_STR);
			$record = $command->queryOne();
			
			if(empty($data['act']) || $data['act']=='add'){
				if(!empty($record)){
					$result['success']=false;
					$result['message']='运单:'.$track_no.'已经提交过，不能重复提交。E003 ';
					return $result;
				}
				$query = "INSERT INTO `tracker_cases`
						(`uid`, `track_no`, `order_id`, `carrier_type`, `customer_url`, `desc`, `status`,  `create_time`, `update_time`) VALUES 
						($uid,:track_no,:order_id,:carrier_type,:customer_url,:desc,'P','".date("Y-m-d H:i:s")."','".date("Y-m-d H:i:s")."')";
				$command = Yii::$app->db->createCommand($query);
				$command->bindValue(':track_no', $track_no, \PDO::PARAM_STR);
				$command->bindValue(':order_id', $order_id, \PDO::PARAM_STR);
				$command->bindValue(':carrier_type', $carrier_type, \PDO::PARAM_STR);
				$command->bindValue(':customer_url', $customer_url, \PDO::PARAM_STR);
				$command->bindValue(':desc', $desc, \PDO::PARAM_STR);
				$insert = $command->execute();
				if(!empty($insert)){
					return $result;
				}else{
					$result['success']=false;
					$result['message']='提交失败:保存数据失败 。E004';
					return $result;
				}
			}else{
				if(!empty($record)){
					$query = "UPDATE `tracker_cases` SET `carrier_type`=:carrier_type,`customer_url`=:customer_url,`desc`=:desc,`status`='P',`update_time`='".date("Y-m-d H:i:s")."' 
						WHERE `uid`=$uid and `track_no`=:track_no";
					$command = Yii::$app->db->createCommand($query);
					$command->bindValue(':track_no', $track_no, \PDO::PARAM_STR);
					$command->bindValue(':carrier_type', $carrier_type, \PDO::PARAM_STR);
					$command->bindValue(':customer_url', $customer_url, \PDO::PARAM_STR);
					$command->bindValue(':desc', $desc, \PDO::PARAM_STR);
					$update = $command->execute();
					if(!empty($update)){
						return $result;
					}else{
						$result['success']=false;
						$result['message']='修改失败:保存数据失败 。E005';
						return $result;
					}
				}
			}
		}catch (Exception $e) {
			$result['success']=false;
			$result['message']= $e->getMessage();
			return $result;
		}
	}
	
	public static function ignoreTrackerNo($track_id){
		$rtn['message']="";
		$rtn['success'] = true;
		if(empty($track_id)){
			$rtn['message']="没有指定需要操作的物流号！";
			$rtn['success'] = false;
			return $rtn;
		}
		
		$uid = \Yii::$app->user->id;
		
		$journal_id = SysLogHelper::InvokeJrn_Create("Tracker",__CLASS__, __FUNCTION__ , array($track_id));
		
		$canIgnoreStatus = Tracking::getCanIgnoreStatus('EN');
		$tracks = Tracking::find()->where(['id'=>$track_id,'status'=>$canIgnoreStatus])->all();
		foreach ($tracks as $aTrack){
			$aTrack->ignored_time = date("Y-m-d H:i:s");
			$aTrack->status = 'ignored';
			
			//记录手工移动状态log
			$addi_info = $aTrack->addi_info;
			$addi_info = json_decode($addi_info,true);
			if(empty($addi_info)) $addi_info = [];
			$addi_info['manual_status_move_logs'][] = [
			'capture_user_name'=>$userName,
			'old_status'=>$old_status,
			'new_status'=>'ignored',
			'time'=>$now_str,
			];
			$aTrack->addi_info = json_encode($addi_info);
			
			//更改lt_tracking的status
			if($aTrack->save()){
				//更新status后，清除od_order_v2的5天未上网标签
				$order = OdOrder::find()->where(['order_source_order_id'=>$aTrack->order_id])->asArray()->one();
				if(!empty($order)){
					//清除od_order_v2的5天未上网标签
					if($order['weird_status']=='tuol')
						$weird_status='';
					else 
						$weird_status = $order['weird_status'];
					OdOrder::updateAll(['weird_status'=>$weird_status,'logistic_status'=>'ignored'],['order_source_order_id'=>$aTrack->order_id]);
					//模拟od_order_shipped_v2表已经被同步
					OrderApiHelper::setOrderShippedInfo($order['order_id'],$aTrack->track_no, ['sync_to_tracker'=>'Y','tracker_status'=>'ignored']);
				}
				
				//已提交的查询不到反馈状态设置为已完成
				$query = "UPDATE `tracker_cases` SET `status`='C',`update_time`='".date("Y-m-d H:i:s")."',`comment`='用户已忽略该物流单'
				WHERE `uid`=$uid and `track_no`=:track_no";
				$command = Yii::$app->db->createCommand($query);
				$command->bindValue(':track_no', $aTrack->track_no, \PDO::PARAM_STR);
				$update = $command->execute();
			}else{
				$rtn['message'] .= $aTrack->track_no."忽略失败，E001；";
				$rtn['success'] = false;
				continue;
			}
		}
		SysLogHelper::InvokeJrn_UpdateResult($journal_id, $rtn);
		return $rtn;
	}
	
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 封装tracker 的左侧菜单 代码逻辑
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param     na				获取$_GET 上的参数
	 +---------------------------------------------------------------------------------------------
	 * @return						[menu array , active string]
	 *
	 * @invoking					TrackingHelper::getMenuParams();
	 *
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2016/04/07				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function getMenuParams(){
		$menu_platform = (!empty($_GET['platform'])?strtolower($_GET['platform']):"");
		$menu_parcel_classification = (!empty($_GET['parcel_classification'])?strtolower($_GET['parcel_classification']):"");
		
		
		//var_dump($menu_parcel_classification);
		$seller_statistics_span='';
		$menu_label_count = TrackingHelper::getMenuStatisticData();
		$get_sellerid='';
		if(!empty($_GET['sellerid'])){
			$get_sellerid = $_GET['sellerid'];
			$menu_label_count = $menu_label_count[$_GET['sellerid']];
			$seller_statistics_span = "<span class='no-qtip-icon' title='".$_GET['sellerid']."' qtipkey='cs_filtered_account'>(".(strlen($_GET['sellerid'])>13?substr($_GET['sellerid'],0,9)."..":$_GET['sellerid']). ")</span>";
		}
		else
			$menu_label_count = $menu_label_count['all'];
		
		
		$active = '';
		$d=strtotime("-7 days");
		$startdate = date("Y-m-d", $d);
		$RequestGoodEvaluationLink = Url::to(['/tracking/tracking/list-tracking?platform=all&pos=RGE&parcel_classification=received_parcel&select_parcel_classification=received_parcel&is_send=N&startdate='.$startdate]);
		$RequestPendingFetchLink = Url::to(['/tracking/tracking/list-tracking?platform=all&pos=RPF&parcel_classification=arrived_pending_fetch_parcel&select_parcel_classification=arrived_pending_fetch_parcel&is_send=N&startdate='.$startdate]);
		$DeliveryFailedFetchLink = Url::to(['/tracking/tracking/list-tracking?platform=all&pos=DF&parcel_classification=delivery_failed_parcel&select_parcel_classification=delivery_failed_parcel&is_send=N&startdate='.$startdate]);
		$RequestShippingLink = Url::to(['/tracking/tracking/list-tracking?platform=all&pos=RSHP&parcel_classification=shipping_parcel&select_parcel_classification=shipping_parcel&is_send=N&startdate='.$startdate]);
		$RequestRejectedLink = Url::to(['/tracking/tracking/list-tracking?platform=all&pos=RRJ&parcel_classification=rejected_parcel&select_parcel_classification=rejected_parcel&is_send=N&startdate=' . $startdate
				] );
		 
		// 绑定平台
		list($bindingLink,$label) = AppApiHelper::getPlatformMenuData();
		
		
		$normalParcelItem = [];
		if ($menu_parcel_classification == 'shipping_parcel' || (!empty($menu_label_count['shipping_parcel'])) ){
			$normalParcelItem[TranslateHelper::t('运输途中')] = [
			'url'=>Url::to(['/tracking/tracking/list-tracking?parcel_classification=shipping_parcel&sellerid='.$get_sellerid]),
			'tabbar'=>$menu_label_count['shipping_parcel'],
			'qtipkey'=>'@tracker_shipping',
			];
		}
		
		if ($menu_parcel_classification == 'shipping_parcel'&& empty($menu_platform)){
			$active = TranslateHelper::t('运输途中');
		}
		
		if ($menu_parcel_classification == 'no_info_parcel' || (!empty($menu_label_count['no_info_parcel'])) ){
			$normalParcelItem[TranslateHelper::t('未查询到')] = [
			'url'=>Url::to(['/tracking/tracking/list-tracking?parcel_classification=no_info_parcel&sellerid='.$get_sellerid]),
			'tabbar'=>$menu_label_count['no_info_parcel'],
			'qtipkey'=>'@tracker_no_info',
			];
		}
		if ($menu_parcel_classification == 'no_info_parcel'&& empty($menu_platform)){
			$active = TranslateHelper::t('未查询到');
		}
		
		if ($menu_parcel_classification == 'suspend_parcel' || (!empty($menu_label_count['suspend_parcel'])) ){
			$normalParcelItem[TranslateHelper::t('延迟查询')]=[
			'url'=>Url::to(['/tracking/tracking/list-tracking?parcel_classification=suspend_parcel&sellerid='.$get_sellerid]),
			'tabbar'=>$menu_label_count['suspend_parcel'],
			'qtipkey'=>'@tracker_suspend_parcel',
			];
		}
		if ($menu_parcel_classification == 'suspend_parcel'&& empty($menu_platform)){
			$active = TranslateHelper::t('延迟查询');
		}
		
		$normalParcelItem[TranslateHelper::t('已完成')]=[
		'url'=>Url::to(['/tracking/tracking/list-tracking?parcel_classification=completed_parcel&sellerid='.$get_sellerid]),
		'tabbar'=>$menu_label_count['completed_parcel'],
		'qtipkey'=>'@tracker_complete_parcel',
		];
		
		if ($menu_parcel_classification == 'completed_parcel'&& empty($menu_platform)){
			$active = TranslateHelper::t('已完成');
		}
		
		
		$exceptionParcelItem = [];
		if ($menu_parcel_classification == 'rejected_parcel' || (!empty($menu_label_count['rejected_parcel'])) ){
			$exceptionParcelItem[TranslateHelper::t('异常退回 ')] = [
			'url'=>Url::to(['/tracking/tracking/list-tracking?parcel_classification=rejected_parcel&sellerid='.$get_sellerid]),
			'tabbar'=>$menu_label_count['rejected_parcel'],
			'qtipkey'=>'@tracker_rejected',
			];
		}
		if ($menu_parcel_classification == 'rejected_parcel'&& empty($menu_platform)){
			$active = TranslateHelper::t ( '异常退回' );
		}
		
		if ($menu_parcel_classification == 'ship_over_time_parcel' || (!empty($menu_label_count['ship_over_time_parcel'])) ){
			$exceptionParcelItem[TranslateHelper::t('运输过久')] = [
			'url'=>Url::to(['/tracking/tracking/list-tracking?parcel_classification=ship_over_time_parcel&sellerid='.$get_sellerid]),
			'tabbar'=>$menu_label_count['ship_over_time_parcel'],
			'qtipkey'=>'@tracker_ship_over_time',
			];
		}
		
		if ($menu_parcel_classification == 'ship_over_time_parcel'&& empty($menu_platform)){
			$active = TranslateHelper::t ( '运输过久' );
		}
		
		if ($menu_parcel_classification == 'arrived_pending_fetch_parcel' || (!empty($menu_label_count['arrived_pending_fetch_parcel'])) ){
			$exceptionParcelItem[TranslateHelper::t('到达待取')] = [
			'url'=>Url::to(['/tracking/tracking/list-tracking?parcel_classification=arrived_pending_fetch_parcel&sellerid='.$get_sellerid]),
			'tabbar'=>$menu_label_count['arrived_pending_fetch_parcel'],
			'qtipkey'=>'@tracker_arrived_pending_fetch',
			];
		}
		
		if ($menu_parcel_classification == 'arrived_pending_fetch_parcel'&& empty($menu_platform)){
			$active = TranslateHelper::t ( '到达待取' );
		}
		
		if ($menu_parcel_classification == 'delivery_failed_parcel' || (!empty($menu_label_count['delivery_failed_parcel'])) ){
			$exceptionParcelItem[TranslateHelper::t('投递失败')] = [
			'url'=>Url::to(['/tracking/tracking/list-tracking?parcel_classification=delivery_failed_parcel&sellerid='.$get_sellerid]),
			'tabbar'=>$menu_label_count['delivery_failed_parcel'],
			'qtipkey'=>'@tracker_delivery_failed',
			];
		}
		
		if ($menu_parcel_classification == 'delivery_failed_parcel'&& empty($menu_platform)){
			$active = TranslateHelper::t ( '投递失败' );
		}
		
		if ($menu_parcel_classification == 'unshipped_parcel' || (!empty($menu_label_count['unshipped_parcel'])) ){
			$exceptionParcelItem[TranslateHelper::t('无法交运')] = [
			'url'=>Url::to(['/tracking/tracking/list-tracking?parcel_classification=unshipped_parcel&sellerid='.$get_sellerid]),
			'tabbar'=>$menu_label_count['unshipped_parcel'],
			'qtipkey'=>'@tracker_unshipped',
			];
		}
		if ($menu_parcel_classification == 'ignored_parcel' || (!empty($menu_label_count['ignored_parcel'])) ){
			$exceptionParcelItem[TranslateHelper::t('已忽略')] = [
			'url'=>Url::to(['/tracking/tracking/list-tracking?parcel_classification=ignored_parcel&sellerid='.$get_sellerid]),
			'tabbar'=>$menu_label_count['ignored_parcel'],
			];
		}
		//配额不足
		if ($menu_parcel_classification == 'quota_insufficient' || !empty($menu_label_count['quota_insufficient']) ){
			$exceptionParcelItem[TranslateHelper::t('配额不足')] = [
			'url'=>Url::to(['/tracking/tracking/list-tracking?parcel_classification=quota_insufficient&sellerid='.$get_sellerid]),
			'tabbar'=>$menu_label_count['quota_insufficient'],
			'qtipkey'=>'tracker_quota_insufficient',
			];
		}
		
		$customerRemandItem = [
			TranslateHelper::t('启运通知')=>[
				'url'=>$RequestShippingLink,
				'tabbar'=>$menu_label_count['shipping_message'],
				'qtipkey'=>'@tracker_request_shipping',
			],
			TranslateHelper::t('到达待取通知')=>[
				'url'=>$RequestPendingFetchLink,
				'tabbar'=>$menu_label_count['arrived_pending_message'],
				'qtipkey'=>'@tracker_request_pending_fetch',
			],
			TranslateHelper::t('投递失败通知')=>[
				'url'=>$DeliveryFailedFetchLink,
				'tabbar'=>$menu_label_count['delivery_failed_message'],
				'qtipkey'=>'@tracker_delivery_failed',
			],
			TranslateHelper::t('异常退回通知')=>[
				'url'=>$RequestRejectedLink,
				'tabbar'=>$menu_label_count['rejected_message'],
				'qtipkey'=>'@tracker_request_rejected',
			],
			TranslateHelper::t('已签收请求好评')=>[
				'url'=>$RequestGoodEvaluationLink,
				'tabbar'=>$menu_label_count['received_message'],
				'qtipkey'=>'@tracker_request_good_evaluation',
			],
		];
		
		if (!empty($_GET['pos']) && $menu_parcel_classification == 'shipping_parcel' ){
			$active = TranslateHelper::t ( '启运通知' );
		}
		if (!empty($_GET['pos']) && $menu_parcel_classification == 'arrived_pending_fetch_parcel' ){
		$active = TranslateHelper::t ( '到达待取通知' );
		}
		if (!empty($_GET['pos']) && $menu_parcel_classification == 'delivery_failed_parcel' ){
			$active = TranslateHelper::t ( '投递失败通知' );
		}
		if (!empty($_GET['pos']) && $menu_parcel_classification == 'rejected_parcel' ){
		$active = TranslateHelper::t ( '异常退回通知' );
		}
		if (!empty($_GET['pos']) && $menu_parcel_classification == 'completed_parcel' ){
		$active = TranslateHelper::t ( '已签收请求好评' );
		}
		
		$menu = [
			TranslateHelper::t ( '快速查询' )=>[
			'icon'=>'icon-sousuo1',
			'url'=>Url::to(['/tracking/tracking/index']),
				
			],
			TranslateHelper::t ( '全部记录' )=>[
				'icon'=>'icon-liebiao1',
				'url'=>Url::to(['/tracking/tracking/list-tracking?parcel_classification=all_parcel']),
				'qtipkey'=>'@tracker_cx_all',
				'items'=>[
						TranslateHelper::t('正常包裹')=>[
							'items'=>$normalParcelItem,
							'url'=>Url::to(['/tracking/tracking/list-tracking?parcel_classification=normal_parcel&sellerid='.$get_sellerid]),
							],
						TranslateHelper::t('异常包裹')=>[
								'items'=>$exceptionParcelItem,
							],
						],
			],
			TranslateHelper::t('自助客服提醒')=>[
				'icon'=>'icon-fa-mail',
				'items'=>$customerRemandItem,
			],
			TranslateHelper::t('统计分析')=>[
				'icon'=>'icon-iconfontshujutongji',
				'items'=>[
					TranslateHelper::t('渠道统计分析')=>[
						'url'=>Url::to(['/tracking/tracking/delivery_statistical_analysis']),
					],
					TranslateHelper::t('商品推荐统计')=>[
						'url'=>Url::to(['/tracking/tracking-recommend-product/product-list']),
					],
				],
			],
			TranslateHelper::t('设置')=>[
				'icon'=>'icon-shezhi',
				'items'=>[
					TranslateHelper::t('平台绑定')=>[
						'url'=>$bindingLink,
						'target'=>'_blank',
						'qtipkey'=>'@tracker_setting_platform_binding',
					],
					TranslateHelper::t('发信模板设置')=>[
						'url'=>Url::to(['/tracking/tracking/mail_template_setting']),
					],
				    TranslateHelper::t('二次营销商品列表')=>[
				        'url'=>Url::to(['/tracking/tracking-recommend-product/custom-product-list']),
				    ],
				    TranslateHelper::t('二次营销商品组列表')=>[
				        'url'=>Url::to(['/tracking/tracking-recommend-product/group-list']),
				    ],
				    TranslateHelper::t('新绑定账号同步天数')=>[
				    	'url'=>Url::to(['/tracking/tracking/get-od-trackno-days-set']),
		    		],
				],
			],
		
		];
		
		if (($menu_parcel_classification == 'all_parcel' && empty($menu_platform) )){
			$active = TranslateHelper::t ( '快速查询' );
		}
		if ($menu_parcel_classification == 'normal_parcel'&& empty($menu_platform)){
			$active = TranslateHelper::t ( '正常包裹' );
		}
		if ($menu_parcel_classification == 'exception_parcel'&& empty($menu_platform)){
			$active = TranslateHelper::t ( '异常包裹' );
		}
			
		if ($menu_parcel_classification == 'all_parcel' && empty($menu_platform) ){
			$active = TranslateHelper::t ( '全部记录' );
		}
		if (yii::$app->controller->action->id == 'delivery_statistical_analysis'){
			$active = TranslateHelper::t ( '渠道统计分析' );
		}
		if (yii::$app->controller->action->id == 'product-list'){
			$active = TranslateHelper::t ( '商品推荐统计' );
		}
		if (yii::$app->controller->action->id == 'platform_account_binding'){
			$active = TranslateHelper::t ( '平台绑定' );
		}
		if (yii::$app->controller->action->id == 'mail_template_setting'){
			$active = TranslateHelper::t ( '发信模板设置' );
		}
			
		return [$menu , $active];
		
	}//end of getMenuParams
	
	/**
	 *获取用户已经设置的自动忽略查询的物流运输方式	
	 **/
	public static function getAutoIgnoreToCheckShipType(){
		global $CACHE;
		if(isset($CACHE['IgnoreToCheck_ShipType']))
			return $CACHE['IgnoreToCheck_ShipType'];
		
		$config = ConfigHelper::getConfig('IgnoreToCheck_ShipType','NO_CACHE');
		if(!empty($config))
			$config = json_decode($config,true);
		else
			$config = [];
		
		return $config;
	}//end of getAutoIgnoreToCheckShipType
	
	/*
	 * 用户设置物流商服务查询使用的查询渠道 mapping记录到redis -- set
	 * @param	array	$mapping	like:['4px express'=>'999000002','NG-JG-Seko-China'=>'0',]
	 * @param	int		$puid
	 * @author	lzhl	2017/01		初始化
	 */
	public static function setUserShipByAndCarrierTypeMappingToRedis($mapping,$puid=0){
		//获取当前 用户的puid
		if (empty($puid))
			$puid = \Yii::$app->subdb->getCurrentPuid();
		
		$classification = "Tracker_AppTempData";
		$key = "ShipByMapping";
		return RedisHelper::RedisSet($classification,"user_$puid".".".$key,json_encode($mapping));
	}
	
	/*
	 * 用户设置物流商服务查询使用的查询渠道 mapping记录到redis -- add
	 * @param	array	$mapping	like:['4px express'=>'999000002','NG-JG-Seko-China'=>'0',]
	 * @param	int		$puid
	 * @author	lzhl	2017/01		初始化
	 */
	public static function addUserShipByAndCarrierTypeMappingToRedis($mapping,$puid=0){
		if (empty($puid))
			$puid = \Yii::$app->subdb->getCurrentPuid();
		
		$classification = "Tracker_AppTempData";
		$key = "ShipByMapping";
		$oldRedisData = self::getUserShipByAndCarrierTypeMappingFromRedis($puid);
		$newRedisData = [];
		
		$is_changed = false;
	 
		if(!empty($oldRedisData)){	 
			$newRedisData = $oldRedisData; 
		}
		foreach ($mapping as $ship_by=>$carrier_type){
			$ship_by= strtolower(trim($ship_by));
			if (empty($ship_by))
				continue;
			
			if (!isset($newRedisData[$ship_by]) or $newRedisData[$ship_by]<>$carrier_type)
				$is_changed = true;
			
			$newRedisData[$ship_by] = $carrier_type;
		}
		
		if(!empty($newRedisData) and $is_changed)
			return RedisHelper::RedisSet($classification,"user_$puid".".".$key,json_encode($newRedisData));
		else 
			return 0;
	}
	
	/*
	 * 从redis获取 用户设置物流商服务查询使用的查询渠道 mapping
	 * @author	lzhl	2017/01		初始化 
	 */
	public static function getUserShipByAndCarrierTypeMappingFromRedis($puid=0){
		if (empty($puid))
			$puid = \Yii::$app->subdb->getCurrentPuid();
		
		$classification = "Tracker_AppTempData";
		$key = "ShipByMapping";
		$redisData = RedisHelper::RedisGet($classification,"user_$puid".".".$key);
		
		if(!empty($redisData))
			$aa = json_decode($redisData,true);
		else
			$aa = [];
		
		return $aa;
	}
	
	/*
	 * 从redis删除  用户设置物流商服务查询使用的查询渠道 mapping
	 * @author	lzhl	2017/01		初始化
	 */
	public static function delUserShipByAndCarrierTypeMappingFromRedis(){
		if (empty($puid))
			$puid = \Yii::$app->subdb->getCurrentPuid();
		
		$classification = "Tracker_AppTempData";
		$key = "ShipByMapping";
		return RedisHelper::RedisDel($classification,"user_$puid".".".$key);
	}
	
	/*
	 * 物流商服务查询使用的查询渠道 全局 mapping记录到redis -- add
	 * @param	array	$mapping
	 *          $free_text_name =>$carrier_type, ...	
	 *      like:['4px express'=>'999000002','NG-JG-Seko-China'=>'0','DHL'=>'10003',...]
	 * @return	int		RedisSet的结果or 0
	 * @author	lzhl	2017/01		初始化
	 */
	public static function addGlobalShipByAndCarrierTypeMappingToRedis($mapping){
		//global $CACHE;
		$classification = "Tracker_AppTempData";
		$key = "GlobalShipByMapping";
		$oldRedisData = self::getGlobalShipByAndCarrierTypeMappingFromRedis();
		$newRedisData = [];
		if(!empty($oldRedisData)){
			$newRedisData = $oldRedisData;
		}
		$is_changed = false;
		foreach ($mapping as $ship_by=>$carrier_type){
			//用小写，防止free text DHL dhl 之类导致匹配失败，重复数据
			$ship_by = trim(strtolower($ship_by));
			if (empty($ship_by))
				continue;
			
			if (!isset($newRedisData[$ship_by]) or $newRedisData[$ship_by]<>$carrier_type)
				$is_changed = true;
			
			$newRedisData[$ship_by] = $carrier_type;
		}
	 
		$CACHE['GlobalShipByMapping']['MappingData'] = $newRedisData;
		
		if(!empty($newRedisData) and $is_changed)
			return RedisHelper::RedisSet($classification,$key,json_encode($newRedisData));
		else
			return 0;
	}
	
	/*
	 * 从redis获取 全局 物流商服务查询使用的查询渠道 mapping
	 * @return	array	$CACHE['GlobalShipByMapping']['MappingData']
	 * @author	lzhl	2017/01		初始化
	 */
	public static function getGlobalShipByAndCarrierTypeMappingFromRedis(){
		//防止并发冲突，还是不要缓存起来了  global $CACHE;
		//如果内存里面有数据且数据为近1分钟内，则返回内存结果
		/*
		if( !empty($CACHE['GlobalShipByMapping']['CacheTime']) && $CACHE['GlobalShipByMapping']['CacheTime']<( time()-60 ) && 
			isset($CACHE['GlobalShipByMapping']['MappingData']) 
		){
			//echo "<br><br> CACHE['GlobalShipByMapping']['MappingData'] isset";
			return $CACHE['GlobalShipByMapping']['MappingData'];
		}
		*/
		//无内存结果，则读redis和将redis结果写入缓存
		$classification = "Tracker_AppTempData";
		$key = "GlobalShipByMapping";
		$redisData = RedisHelper::RedisGet($classification,$key);
		
		if(!empty($redisData))
			$CACHE['GlobalShipByMapping']['MappingData'] = json_decode($redisData,true);
		else 
			$CACHE['GlobalShipByMapping']['MappingData'] = [];
		
		//echo "<br><br> CACHE['GlobalShipByMapping']['MappingData'] create";
		$CACHE['GlobalShipByMapping']['CacheTime'] = time();
		//var_dump($CACHE['GlobalShipByMapping']);
		return $CACHE['GlobalShipByMapping']['MappingData'];
	}
	
	/*
	 * 输入ship by 的free text，如果有mapping，输出结果，如果没有，返回‘’
	 * */
	public static function getGlobalShipByAndCarrierTypeMappingFromRedisForShipBy($ship_by){
		$allShipBy = self::getGlobalShipByAndCarrierTypeMappingFromRedis();
		//用小写，防止free text DHL dhl 之类导致匹配失败，重复数据
		$ship_by = trim(strtolower($ship_by));
		$mappingResult = "";	
		if (isset($allShipBy[$ship_by])){
			$mappingResult = $allShipBy[$ship_by];
		}
		return $mappingResult;
	}

	public static function getUserShipByAndCarrierTypeMappingFromRedisForShipBy($ship_by){
		global $CACHE;
		$allShipBy = self::getUserShipByAndCarrierTypeMappingFromRedis($CACHE['puid']);
		//用小写，防止free text DHL dhl 之类导致匹配失败，重复数据
		$ship_by = trim(strtolower($ship_by));
		$mappingResult = "";
		if (isset($allShipBy[$ship_by])){
			$mappingResult = $allShipBy[$ship_by];
		}
		return $mappingResult;
	}
	
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 根据 绑定账号来改变 tracker 的查询限额
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param $uid 							用户对应 的id
	 +---------------------------------------------------------------------------------------------
	 * @return	int				tracker quota
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2017/03/27				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	public static function getTrackerQuota($uid = ''){
	 
		$account = PlatformAccountApi::getPlatformInfoInRedis($uid);
		$isbind = false;
		$accountList = json_decode($account,true);
		if (!empty($accountList)){
			foreach($accountList as $pf=>$v){
				//自定义店铺不算绑定 账号
				if ($pf == 'customized') continue;
				if ($v){
					$isbind = true;
					break;
				}
			}
		}
		
		if ($isbind){
			return self::$tracker_import_limit;
		}else{
			return self::$tracker_guest_import_limit;
		}
 
		
	}//end of getTrackerQuota
	
	/**
	 * 查询某用户的平台店铺，tracker默认拉取多少天以前的订单运单号
	 * @param	string	$platform
	 * @param	string	$selleruser
	 * @param	int		$puid
	 * @return	int		//how many days
	 * @author	lzhl	2017-09-14
	 */
	public static function getPlatformGetHowLongAgoOrderTrackNo($platform,$puid=0){
		if(empty($puid))
			$puid = \Yii::$app->user->identity->getParentUid();
		$platform = strtolower($platform);
		$key = 'PlatformGetOrderTrackNoDays';
		$setting = RedisHelper::RedisGet('Tracker_AppTempData', 'uid_'.$puid.'_'.$key);
		if(!empty($setting))
			$setting = json_decode($setting,true);
		else
			$setting = [];
		
		if(!isset($setting[$platform]))
			return 7;
		else
			$days = (int)$setting[$platform];
		return $days;
	}
	
	/**
	 * 设置某用户的平台店铺，tracker默认拉取多少天以前的订单运单号
	 * @param	string	$platform
	 * @param	string	$selleruser
	 * @param	int		$days
	 * @param	int		$puid
	 * @return	boolean	redis set result
	 * @author	lzhl	2017-09-14
	 */
	public static function setPlatformGetHowLongAgoOrderTrackNo($platform,$days=7,$puid=0){
		if(empty($puid))
			$puid = \Yii::$app->user->identity->getParentUid();
		$key = 'PlatformGetOrderTrackNoDays';
		$platform = strtolower($platform);
		
		$setting = RedisHelper::RedisGet('Tracker_AppTempData', 'uid_'.$puid.'_'.$key);
		if(!empty($setting))
			$setting = json_decode($setting,true);
		else
			$setting = [];
		
		$setting[$platform] = $days;
		$rtn = RedisHelper::RedisSet('Tracker_AppTempData', 'uid_'.$puid.'_'.$key,json_encode($setting));
		if($rtn==-1)
			return false;
		return true;
	}
	
	/**
	 * 获得用户设置了不查询的物流商
	 * @param	int		$puid
	 * @return	mixed
	 * @author	lzhl	2017-09-14
	 */
	public static function getUserIgnoredCheckCarriers($puid=0){
		if(empty($puid))
			$puid = \Yii::$app->user->identity->getParentUid();
		$key = 'userIgnoredCheckCarriers';
		$rtn = RedisHelper::RedisGet('Tracker_AppTempData', 'uid_'.$puid.'_'.$key);
		if($rtn==-1)
			return ['success'=>false,'message'=>'获取设置失败','data'=>''];
		$data = json_decode($rtn,true);
		if(empty($data)) $data = [];
			return ['success'=>true,'message'=>'','data'=>$data];
	}
	
	/**
	 * 设置用户不查询的物流商
	 * @param	int		$puid
	 * @param	array	$carriers
	 * @return	mixed
	 * @author	lzhl	2017-09-14
	 */
	public static function setUserIgnoredCheckCarriers($puid=0,$carriers=[]){
		if(empty($puid))
			$puid = \Yii::$app->user->identity->getParentUid();
		$key = 'userIgnoredCheckCarriers';
		$rtn = RedisHelper::RedisSet('Tracker_AppTempData', 'uid_'.$puid.'_'.$key,json_encode($carriers));
		if($rtn==-1)
			return ['success'=>false,'message'=>'设置失败'];
		
		try{
			foreach ($carriers as $carrier){
				Tracking::updateAll(['status'=>'ignored']," ship_by=:ship_by and state!='complete' and state!='deleted' ",[':ship_by'=>$carrier]);
			}
		}catch(\Exception $e) {
			$result = ['success'=>false , 'message'=> 'update db failed'.$e->getMessage()];
		}
		return ['success'=>true,'message'=>''];
	}


	public static function getTrackerUsedQuota($puid1){
		global $CACHE;
		if (empty($CACHE['TrackerSuffix'.$puid1])){
 
			$VipLevel = 'v0';
			if ($VipLevel == 'v0'){
				$suffix = date('Ymd');
			}else{
				$suffix = 'vip';
			}
			
			$CACHE['TrackerSuffix'.$puid1] = $suffix;
		}
		$suffix = $CACHE['TrackerSuffix'.$puid1];
		//$limt_count =  ConfigHelper::getConfig("Tracking/trackerImportLimit_".$suffix , 'NO_CACHE');
		$limt_count =  TrackingHelper::getTrackerTempDataFromRedis("trackerImportLimit_".$suffix );
		if (empty($limt_count)) $limt_count=0;
		
		return $limt_count;
	}	

	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 获取 物流信息 所有事件
	 * @param string $track_no
	 * @param string $lang
	 * @return
	 * @author		lzhl		2017/10/15		初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function translateTrackingEvent($track_no,$to_lang){
		$all_events_str = [];
	
		$model = Tracking::find()->andWhere(["track_no"=>$track_no])->one();
	
		//空数据 跳过
		if (empty($model)) 
			return ['success'=>false,'message'=>'没有找到物流号信息!','eventHtml'=>''];
		
		//生成 物流事件
		$tmp_rt = self::getTranslatedEvents($track_no, 'auto', $to_lang,true);
		
		if(!$tmp_rt['success']){
			return ['success'=>false,'message'=>@$tmp_rt['message'],'eventHtml'=>''];
		}
		
		if (!empty($tmp_rt['allEvents']))
			$all_events = $tmp_rt['allEvents'];
		else
			$all_events = [];
	
		$all_events_str = "";
		//获取所有事件
		if (is_array($all_events)){
			foreach($all_events as $anEvent){
				$anEvent['where'] = base64_decode($anEvent['where']);
// 				$anEvent['what'] = base64_decode($anEvent['what']);
				//防止这个时间是一个无效的日期，例如 1900 年
				if (!empty($anEvent['when']) and strlen($anEvent['when']) >=10 and substr($anEvent['when'],0,10)<'2014-01-01' )
					$anEvent['when'] = '';
	
				if (!empty($anEvent['type'])){
					$class_nation = $anEvent['type'];
				}
	
				if (empty($className)){
					$className = 'orange_bold';
				}else{
					$className = 'font_normal';
				}
				$all_events_str  .= '<dd>'.
						'<div class="col-md-12 '.$className.'">'.
						'<i class="'.(($className=='orange_bold')?"egicon-arrow-on-yellow":"egicon-arrow-on-gray").'"></i>'.
						'<time '.(($className=='orange_bold')?'style="color: #f0ad4e;" ':'').'>'. $anEvent['when'].'</time>'.
						'<p>'.$anEvent['where'].((empty($anEvent['where']))?"":",").
						$anEvent['what']."</p></div>".
						"</dd>";
			}
		}
		
		$all_events_str = "<dl lang='".$to_lang."'>".$all_events_str.'</dl>';
		return ['success'=>true,'message'=>'','eventHtml'=>$all_events_str];
	}//end of generateTrackingEventHTML
	
	
	
	public static function getTranslatedEvents($track_no,$from_lang,$to_lang,$save_to_info=false){
		$rtn['message']="";
		$rtn['success'] = true;
		$rtn['allEvents'] = array();
		$now_str = date('Y-m-d H:i:s');
		
		if (empty($track_no) ){
			$rtn['message']="没有有效Tracking No输入";
			$rtn['success'] = false;
			return $rtn;
		}
		try{	
			$model = Tracking::find()->andWhere("track_no=:track_no",array(":track_no"=>$track_no))->one();
			//step 1: when not found such record, skip it
			if ($model == null){
				$rtn['message']="找不到该Tracking No的记录：$track_no";
				$rtn['success'] = false;
				return $rtn;
			}
			
			//step 2: 获取all events，并且进行翻译
			$allEvents = json_decode($model->all_event , true);
			if (empty($allEvents)) $allEvents = array();
			$new_event_md5 = md5(json_encode($allEvents));
			//$rtn['original'] = $allEvents;
			global $CACHE;
			$translated_Events = array();//用于输出的翻译
			$events_for_save= [];//用于记录在db和缓存的翻译数组
			$track_addi_info = empty($model->addi_info)?[]:json_decode($model->addi_info,true);
			
			//case 1a:track_no有翻译缓存
			if(!empty($CACHE['trackerBaiduTranslate'][$from_lang][$track_no])){
				$old_event_md5 = @$CACHE['trackerBaiduTranslate'][$to_lang][$track_no]['md5'];
				$tmp_translated_Events =  @$CACHE['trackerBaiduTranslate'][$to_lang][$track_no]['events'];
				
				if($new_event_md5!==$old_event_md5){
					//case 1a.2a:rack_no事件比较上次翻译时有变动
					$translated_md5s = array_keys($tmp_translated_Events['events']);
					foreach ($allEvents as $src_event){
						$src_event_64 = empty($src_event['what'])?'':$src_event['what'];
						$src_md5 = md5($src_event_64);
						$tmp_e_arr = $src_event;
						if(in_array($scr_md5, $translated_md5s)){
							//有缓存
							$tmp_e_arr['what'] = $tmp_translated_Events['events'][$scr_md5];
							$translated_Events[]= $tmp_e_arr;
							$events_for_save[$src_md5] = $tmp_translated_Events['events'][$scr_md5];
						}else{
							//无缓存
							$r = TranslateHelper::translate(base64_decode($src_event_64), $from_lang, $to_lang);
							//检查是否翻译成功
							if(isset($r['error_code'])){
								$rtn['message'] = "翻译失败,原因".$r['error_msg'];
								$rtn['success'] = false;
								$events_for_db[$src_md5] = base64_decode($src_event_64);
								$translated_Events[] =  $src_event;
							}else{
								$tmp_e_arr['what'] = $r['trans_result'][0]['dst'];
								$translated_Events[] = $tmp_e_arr;
								$events_for_save[$src_md5] = $r['trans_result'][0]['dst'];
							}
						}
					}
				}else{
					//case 1a.2b:rack_no事件比较上次翻译时无变动
					if(!empty($tmp_translated_Events)){
						foreach ($allEvents as $src_event){
							$src_event_64 = empty($src_event['what'])?'':$src_event['what'];
							$src_md5 = md5($src_event_64);
							$tmp_e_arr = $src_event;
							if(isset($tmp_translated_Events['events'][$src_md5]))
								$tmp_e_arr['what'] = $tmp_translated_Events['events'][$scr_md5];
							
							$translated_Events[] = $tmp_e_arr;
							$events_for_save[$src_md5] = $tmp_e_arr['what'];
						}
					}
				}
			}else{
			//case 1b:track_no无翻译缓存
				if(!empty($track_addi_info['translated_events'][$to_lang])){
					//case 1b.2a:track_no有翻译db数据
					if(@$track_addi_info['translated_events'][$to_lang]['md5']==$new_event_md5){
						//case 1b.2a.3a:rack_no事件比较上次翻译时无变动
						$tmp_translated_Events = @$track_addi_info['translated_events'][$to_lang]['events'];
						if(!empty($tmp_translated_Events)){
							foreach ($allEvents as $src_event){
								$src_event_64 = empty($src_event['what'])?'':$src_event['what'];
								$src_md5 = md5($src_event_64);
								$tmp_e_arr = $src_event;
								if(isset($tmp_translated_Events[$src_md5]))
									$tmp_e_arr['what'] = $tmp_translated_Events[$src_md5];
								
								$translated_Events[] = $tmp_e_arr;
								$events_for_save[$src_md5] = $tmp_e_arr['what'];
							}
						}
					}else{
						//case 1b.2a.3b:rack_no事件比较上次翻译时有变动
						$translated_md5s = array_keys($track_addi_info['translated_events'][$to_lang]['events']);
						$tmp_translated_Events = @$track_addi_info['translated_events'][$to_lang]['events'];
						foreach ($allEvents as $src_event){
							$src_event_64 = empty($src_event['what'])?'':$src_event['what'];
							$src_md5 = md5($src_event_64);
							$tmp_e_arr = $src_event;
							if(in_array($src_md5, $translated_md5s)){
								//有缓存
								$tmp_e_arr['what'] = $tmp_translated_Events[$src_md5];
								$translated_Events[]= $tmp_e_arr;
								$events_for_save[$src_md5] = $tmp_e_arr['what'];
							}else{
								//无缓存
								$r = TranslateHelper::translate(base64_decode($src_event_64), $from_lang, $to_lang);
								//检查是否翻译成功
								if(isset($r['error_code'])){
									$rtn['message'] = "翻译失败,原因".$r['error_msg'];
									$rtn['success'] = false;
									$events_for_db[$src_md5] = base64_decode($src_event_64);
									$translated_Events[] =  $tmp_e_arr;
								}else{
									$tmp_e_arr['what'] = $r['trans_result'][0]['dst'];
									$translated_Events[] = $tmp_e_arr;
									$events_for_save[$src_md5] = $tmp_e_arr['what'];
								}
							}
						}
					}
				}else{
					//case 1b.2a:track_no无翻译db数据
					foreach ($allEvents as $src_event){
						$src_event_64 = empty($src_event['what'])?'':$src_event['what'];
						$src_md5 = md5($src_event_64);
						$tmp_e_arr = $src_event;
						$r = TranslateHelper::translate(base64_decode($src_event_64), $from_lang, $to_lang);
						//检查是否翻译成功
						if(isset($r['error_code'])){
							$rtn['message'] = "翻译失败,原因".$r['error_msg'];
							$rtn['success'] = false;
							$events_for_db[$src_md5] = base64_decode($src_event_64);
							$translated_Events[] =  $tmp_e_arr;
						}else{
							$tmp_e_arr['what'] = $r['trans_result'][0]['dst'];
							$translated_Events[] = $tmp_e_arr;
							$events_for_save[$src_md5] = $tmp_e_arr['what'];
						}
						
					}
				}
			}
			//所有翻译成功时，保存结果到缓存和db
			if($rtn['success']){
				$rtn['allEvents'] = $translated_Events;
				$new_event_md5 = md5(json_encode($allEvents));
				$CACHE['trackerBaiduTranslate'][$to_lang][$track_no]['md5'] = $new_event_md5;
				$CACHE['trackerBaiduTranslate'][$to_lang][$track_no]['events'] = $events_for_save;
				if($save_to_info){
					$track_addi_info['translated_events'][$to_lang]['md5'] = $new_event_md5;
					$track_addi_info['translated_events'][$to_lang]['events'] = $events_for_save;
					$model->addi_info = json_encode($track_addi_info);
					$model->save();
				}
			}
		}catch (Exception $e) {
			$rtn['success'] = false;
			$rtn['message'] .= '处理出错';
		}
		return $rtn;
	}
}


