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

/**
 * BaseHelper is the base class of module BaseHelpers.
 *
 */
class TrackingHelperTest {
//状态
	public static $TRACKER_FILE_LOG = false;
	const CONST_1= 1; //Sample
	private static $Insert_Api_Queue_Buffer = array();
	private static $mainQueueVersion = '';	
	
	private static $subQueueVersion = '';
	private static $putIntoTrackQueueVersion = '';
	
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
	'last_event_date'=>"妥投时间",
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
		
		//Pagination 会自动获取Post或者get里面的page number，自动计算offset
		$pagination = new Pagination([
				'totalCount'=> $query->count(),
				'defaultPageSize'=> 50,
				'pageSize'=> $pageSize,
				'pageSizeLimit'=>  [5,  ( $noPagination ? 50000 : 200 )  ],
				]);
		
		$data['pagination'] = $pagination;
	
		if(empty($sort)){
			$sort = 'ship_out_date desc , create_time desc';
			$order = '';
		}
	
		$condition=' 1 ';
		//如果keyword不为空，用户录入了模糊查询
		if(!empty($keyword)){
			//去掉keyword的引号。免除SQL注入
			$keyword = str_replace("'","",$keyword);
			$keyword = str_replace('"',"",$keyword);
			$condition .= " and (order_id like '%$keyword%' or track_no like '%$keyword%' )";
		}
		
		//如果from日期或者to日期有，添加进去filter
		if(!empty($date_from)){
			//去掉keyword的引号。免除SQL注入
			$date_from = str_replace("'","",$date_from);
			$date_from = str_replace('"',"",$date_from);
			$condition .= " and ( ship_out_date >='$date_from' )";
		}
		if(!empty($date_to)){
			//去掉keyword的引号。免除SQL注入
			$date_to = str_replace("'","",$date_to);
			$date_to = str_replace('"',"",$date_to);
			$condition .= " and ( ship_out_date<= '$date_to' )";
		}
		
		//如果state不为空，用户录入了模糊查询
		$bindVals = array();
		foreach ($params as $fieldName=>$val){
			if(!empty($val)){
			 
				
				if($fieldName == 'is_send'){
					//去掉keyword的引号。免除SQL注入
					$val = str_replace("'","",$val);  $val = str_replace('"',"",$val);
					$condi_internal = " and ( ( status = 'received' and received_notified='$val') ".
							" or (status = 'arrived_pending_fetch' and pending_fetch_notified='$val') ".
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
		$data ['condition'] = $condition;
		$data['data'] = $query
			->andWhere($condition,$bindVals)
			->offset($pagination->offset)
			->limit($pagination->limit)
			->orderBy(" $sort $order  , id $order ")
			->asArray()
			->all();
		
		// 调试sql    
	 /*
		 $tmpCommand = $query->createCommand();
		echo "<br>".$tmpCommand->getRawSql();
	 
		*/ 		
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
	static public function getListDataByConditionNoPagination($keyword='',$params=array(), $date_from='',$date_to='', $sort='' , $order='' , $field_label_list=[] , $maxCount = 50000 , $thispageLimit = 6000)
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
	
	static private function _convertTrackerDataToActiveData(&$TrackingData , &$data ,&$field_label_list){
		foreach($TrackingData['data'] as &$oneTracking):
			
			//$EXPORT_EXCEL_FIELD_LABEL 为需要导出的field  , array_flip后得出需要导出的field name
			foreach(array_flip($field_label_list) as $field_name){
				if ($field_name == 'last_event_date'){
					//妥投时间只有   '成功签收' , '买家已确认'   才显示
					if (in_array($oneTracking['status'],['received','platform_confirmed' , '成功签收' , '买家已确认']))
						$row['last_event_date'] = $oneTracking['last_event_date'];
					else{
						//$row['last_event_date'] = '';
						continue;
					}
			
				}
				
				if ($field_name == 'total_days'){
					//在途天数，只有total_days 》 0 才显示
					if ($oneTracking['total_days'] <= 0 )
						$row['total_days'] = "";
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
			if (empty($CACHE['Tracking']['Status']["查询中"])){
				$CACHE['Tracking']['Status']["查询中"] = Tracking::getSysStatus("查询中");
				$CACHE['Tracking']['State']["初始"] = Tracking::getSysState("初始");
			}
			$model->status= $CACHE['Tracking']['Status']["查询中"] ;
			$model->state = $CACHE['Tracking']['State']["初始"];
			
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
		
		$message = "Cronb Job Started generateTrackingRequest";
		\Yii::info(['Tracking',__CLASS__,__FUNCTION__,'Online',$message],"edb\global");

		$csld_report = ConfigHelper::getGlobalConfig("Tracking/csld_format_distribute_$yesterday" );
		
		if ( empty($csld_report) )
			$first_run_for_today = true;
		else
			$first_run_for_today = false;
		
		//step 1, get all puid from managedb
		//step 1.1, get all puid having activity during last 30 days
		$connection = Yii::$app->db;
		$command = $connection->createCommand(
				"SELECT distinct puid FROM `user_last_activity_time` WHERE `last_activity_time` >='". date('Y-m-d',strtotime('-30 days')) ."'"
						) ;
		$rows = $command->queryAll();
		
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
		foreach ($rows as $row){
			$puid = $row['puid'] ;	
			 
			//step 2.0, check whether this database exists user_x
  			  //有可能他不绑定账号，就手工录入使用，所以这个步骤不能省去的
			$sql = "select count(1) from `INFORMATION_SCHEMA`.`TABLES` where table_name ='lt_tracking' and TABLE_SCHEMA='user_$puid'";
			$command = $connection->createCommand($sql);
			$puidDbCount = $command->queryScalar();
			if ( $puidDbCount <= 0 ){
				continue;
			}
	 	

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
			//message Queue
			$command = Yii::$app->db->createCommand("delete FROM `message_api_queue` where create_time <'$days2ago' and status ='C'" );
			$command->execute();
		}
		 
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
				"SELECT distinct puid FROM `user_last_activity_time` WHERE `last_activity_time` >='". date('Y-m-d',strtotime('-30 days')) ."'"
		) ;
		$rows = $command->queryAll();

		//step 2, for each puid, call to request for each active tracking
		foreach ($rows as $row){
			$puid = $row['puid'] ;
			 
				
			//step 2.0, check whether this database exists user_x
			//有可能他不绑定账号，就手工录入使用，所以这个步骤不能省去的
			$sql = "select count(1) from `INFORMATION_SCHEMA`.`TABLES` where table_name ='lt_tracking' and TABLE_SCHEMA='user_$puid'";
			$command = $connection->createCommand($sql);
			$puidDbCount = $command->queryScalar();
			if ( $puidDbCount <= 0 ){
				continue;
			}
			 
	
	
			
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
	    echo "got puids during last 3 hours ".print_r($puids_platforms,true) ."\n";
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
		
		 

		//step 2: for those tracking 查询不到, while tried for 10 days, set them as 无法交运，然后就不要强求了
		$command = Yii::$app->subdb->createCommand("update lt_tracking set state='".Tracking::getSysState("无法交运")."'
								,status='".Tracking::getSysStatus("无法交运")."'
								, update_time='$now_str' where status='".Tracking::getSysStatus("查询不到")."' 
								and ( create_time <='$ten_days_ago'  )" ); //or ship_out_date<='$ten_days_ago'
		
		$affectRows = $command->execute();
		 
		//step 2.2: 90天前的，视为 已过期，不要搞了,但是对巴西的，稍微放松要求到 120天。
		$countriesMax120days = array("'BR'");
		$command = Yii::$app->subdb->createCommand("update lt_tracking set state='".Tracking::getSysState("已完成")."'
								,status='".Tracking::getSysStatus("过期物流号")."', update_time='$now_str' where status in ('".Tracking::getSysStatus("查询中")."','".Tracking::getSysStatus("查询不到")."')
								and ship_out_date <='$days90_ago'  
								and to_nation not in ( " .implode(",", $countriesMax120days). " )" );
		
		$affectRows = $command->execute();
		
		//巴西之类的，允许过期期限是120days
		$command = Yii::$app->subdb->createCommand("update lt_tracking set state='".Tracking::getSysState("已完成")."'
								,status='".Tracking::getSysStatus("过期物流号")."', update_time='$now_str' where status in ('".Tracking::getSysStatus("查询中")."','".Tracking::getSysStatus("查询不到")."')
				and ship_out_date <='$days120_ago'
				and to_nation in ( " .implode(",", $countriesMax120days). " )" );
		
		$affectRows = $command->execute();
						
		//step 2.3: 如果ship by 是平邮的，就更新为状态 “无挂号”，然后state是已完成就算了
		$command = Yii::$app->subdb->createCommand("update lt_tracking set state='".Tracking::getSysState("已完成")."'
								,status='".Tracking::getSysStatus("无挂号")."', update_time='$now_str' where status in 
								('".Tracking::getSysStatus("查询中")."','".Tracking::getSysStatus("查询不到")."')
								and  (ship_by like '%Ordinary%' or  ship_by like '%平邮%' or  ship_by like '%无挂号%' or  ship_by like '%非挂号%' )" );
		
		$affectRows = $command->execute();
		
		// 2.4 : 如果Aliexpress已经FINISH状态了，update 非完成的订单为已完成，已签收

		/*$select_str=" select track_no from  lt_tracking, aliexpress_order  
										where  status not in ('".Tracking::getSysStatus("成功签收")."','".Tracking::getSysStatus("买家已确认")."')  and orderstatus='FINISH' and
								aliexpress_order.id = order_id and lt_tracking.source='O' and platform='aliexpress'    ";
		*/
		//new version do not use order original table, but order v2
		$select_str=" select track_no from  lt_tracking, od_order_v2  
										where  status not in ('".Tracking::getSysStatus("成功签收")."','".Tracking::getSysStatus("买家已确认")."')  
												and order_source_status='FINISH' and
								order_source_order_id = lt_tracking.order_id and lt_tracking.source='O' and platform='aliexpress'    ";
		
		$command = Yii::$app->subdb->createCommand( $select_str );
		$rows = $command->queryAll();
		foreach ($rows as $row){
			self::manualSetOneTrackingComplete( $row['track_no']);
		}
		
		//2.4.b : 如果Dhgate已经FINISH状态了，update 非完成的订单为已完成，已签收
		/*$select_str=" select track_no from  lt_tracking, dhgate_order
										where  status not in ('".Tracking::getSysStatus("成功签收")."','".Tracking::getSysStatus("买家已确认")."')  and 
												orderStatus in ('102006','102007','102111','111111') and
												orderNo = order_id and lt_tracking.source='O' and platform='dhgate'    ";
		*/
		//new version do not use order original table, but order v2
		$select_str=" select track_no from  lt_tracking, od_order_v2
										where  status not in ('".Tracking::getSysStatus("成功签收")."','".Tracking::getSysStatus("买家已确认")."')  and
												order_source_status in ('102006','102007','102111','111111') and
												order_source_order_id = lt_tracking.order_id and lt_tracking.source='O' and platform='dhgate'    ";
		
		$command = Yii::$app->subdb->createCommand( $select_str );
		$rows = $command->queryAll();
		foreach ($rows as $row){
			self::manualSetOneTrackingComplete( $row['track_no']);
		}
		
		//Step 2.5, 判断改用户是否很久没有上来了，如果很久了，就不要他
		//$last_gen_time = ConfigHelper::getConfig("Tracking/last_gen_track_request_time",'NO_CACHE');
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
		$thoseCanDo[] = "status='".Tracking::getSysState("查询中")."' and update_time < '". date('Y-m-d H:i:s',strtotime('-3 hours'))."'";
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
		$thoseCanIgnore[] = "state not in ('".Tracking::getSysState("已完成")."'
											,'".Tracking::getSysState("已删除")."'
											,'".Tracking::getSysState("无法交运")."'	)";
		//3.98,所有物流号跟踪不超过120天，120天后就放弃跟中了
		$thoseCanIgnore[] = "ship_out_date >= '". date('Y-m-d',strtotime('-120 days')) ."'" ;

		$condition ="(";
		foreach ($thoseCanDo as $canDo){
			$condition .= ($condition =="(" ?"":" or ");
			$condition .= " $canDo"; 
		}
		
		$condition .=")";
		
		foreach ($thoseCanIgnore as $canIgnore){
			$condition .= " and $canIgnore";
		}
		
		//是否Online User发起，如果是，只对手动录入和Excel录入的记录发起API请求即可，
		if ($call_by_online){
			$condition .= " and source in ('M','E')";
		}		
		
		$trackingArray = Tracking::find()
							->select("id,track_no , addi_info") //ystest
							->andWhere($condition)
							->orderBy('update_time asc')
							->asArray()
							->all();

		//step 4, for each tracking models need to be rechecked, write one request for each
		$track_list = array();
		$unregistered_track_list = array();
		$addinfos = array();
		$ids = array();//ystest
		foreach ($trackingArray as $aTracking){	
			//如果是 国际邮政，非挂号的，就不要搞了 ex: RI633273745CN
			if (strlen($aTracking['track_no'])==13 and substr($aTracking['track_no'],0,2)=='RI' and substr($aTracking['track_no'],11,2)=='CN' )
				$unregistered_track_list [] = $aTracking['track_no'];
			else{
				$track_list[] = $aTracking['track_no'];
				$ids[] = $aTracking['id']; //ystest
			}
		} //end of each tracking

		//update 为无挂好，因此 已完成的订单
		/*
		if (!empty($unregistered_track_list)){
			$str = "''";
			foreach ($unregistered_track_list as $aTrackNo1)
				$aTrackNo1 = str_replace("'",'',$aTrackNo1);
				$aTrackNo1 = str_replace('"','',$aTrackNo1);
				$str .= ",'".$aTrackNo1."'";  
			$command = Yii::$app->subdb->createCommand("update lt_tracking set state='".Tracking::getSysState("已完成")."'
								,status='".Tracking::getSysStatus("无挂号")."', update_time='$now_str' where track_no in ($str)" );
			$affectRows = $command->execute();
		}
		*/
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
		ConfigHelper::setConfig("Tracking/left_menu_statistics", json_encode(array()));
		ConfigHelper::setConfig("Tracking/top_menu_statistics", json_encode(array()));
		
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
	static public function manualSetOneTrackingComplete($track_no){
		//array('parcel_type'=>1,'status'=>1,'carrier_type'=>1,'from_nation'=>1,'to_nation'=>1,'all_event'=>1,'total_days'=>1,'first_event_date'=>1,'from_lang'=>1,'to_lang'=>1);
		$data = array();
		$now_str = date('Y-m-d H:i:s');
		
		$data['track_no'] = $track_no;
		$data['status'] =Tracking::getSysStatus("买家已确认");
		$data['state'] =Tracking::getSysState("已完成");
		$data['update_time'] = $now_str;
		$carriers = self::getCandidateCarrierType($track_no);
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
		/*
		$allEvents= array();
		$newEvent = array();
		$newEvent['when'] = $data['first_event_date'];
		$newEvent['where'] = base64_encode( '发件人');
		$newEvent['what'] = base64_encode( "参考订单付款时间，估计处理发送订单包裹，收寄");
		$newEvent['lang'] = 'zh-cn';
		$newEvent['type'] = 'fromNation';
		$allEvents [] = $newEvent;
		
		$newEvent = array();
		$newEvent['when'] = date('Y-m-d H:i:s');
		$newEvent['where'] = base64_encode( '销售平台官网');
		$newEvent['what'] = base64_encode( "订单已被确认收货，故标记此物流号为 成功签收。考虑到邮政系统未能正常及时录入签收信息");
		$newEvent['lang'] = 'zh-cn';
		$newEvent['type'] = 'toNation';
		$allEvents [] = $newEvent;
		*/
		//Do not use this man-made event, $data['all_event'] = json_encode($allEvents);
		self::commitTrackingResultUsingValue($data );
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
		
		//判断 上次查询时间 是否已经 到了限定的可以再更新的时间 
		$limit_hours = '20';
		$limit_hours_ago = date('Y-m-d H:i:s',strtotime('-'.$limit_hours.' hours')); 			// 目前 限定的时间 为20小时 
		
		$tracking_addi_info = json_decode($aTracking['addi_info'],true);	
		$tracking_addi_info['consignee_country_code'] = $aTracking->getConsignee_country_code();
		if (!empty($tracking_addi_info['last_manual_refresh_time']) && $tracking_addi_info['last_manual_refresh_time'] > $limit_hours_ago ){
			// 
			$rtn['message'] = $aTracking['track_no']." 上次更新时间 为 ".$tracking_addi_info['last_manual_refresh_time'] .',请在'.$limit_hours.'小时后再更新!';
			$rtn['success'] = false;
			return $rtn;
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
			ConfigHelper::setConfig("Tracking/left_menu_statistics", json_encode(array()));			
			ConfigHelper::setConfig("Tracking/top_menu_statistics", json_encode(array()));
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
					
				$theExistingQueueReq->save();
				//ystest starts
				//保存之前的有效状态到 addiinfo
				if ($aTracking->status <> Tracking::getSysStatus("查询中")){
					$addi_info1 = [];
					if (!empty($aTracking->addi_info))
						$addi_info1 = json_decode($aTracking->addi_info,true);
				
					$addi_info1['last_status'] = $aTracking->status;
					$aTracking->addi_info = json_encode($addi_info1);
				}
				//ystest end
				
				$aTracking->status = Tracking::getSysStatus("查询中");
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
		
		//如果上次的 selected carrier 是可以查询得到的，就用上次的行了
		if ($aTracking['status']<>'no_info' and  $aTracking['carrier_type'] <> ''
				and ($aTracking['state']=='normal' or $aTracking['state']=='exception') ) {
			$ApiRequestModel->selected_carrier = $aTracking['carrier_type'];
			$ApiRequestModel->candidate_carriers = "".$aTracking['carrier_type']."";
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
		
		if (!empty($addi_info))
			$ApiRequestModel->addinfo = $addi_info;
		
		// 获取当前的使用puid。  如果返回为false，说明还没有在puid在使用 
		$puid = \Yii::$app->subdb->getCurrentPuid();
		$ApiRequestModel->puid = $puid;
		$key=$ApiRequestModel->puid ."-".$ApiRequestModel->track_no;
		
		//检查是否可以通过smt api 快速做掉的，如果可以的话，run time设置为 -10，让 handler1 优先处理他
		$toNation2Code = $aTracking->getConsignee_country_code();
		if ($aTracking->source =='O' and $aTracking->platform =='aliexpress' and !empty($toNation2Code ) ){
			$ApiRequestModel->run_time = -10;
		}

		self::$Insert_Api_Queue_Buffer[$key] = $ApiRequestModel->getAttributes();
		$rtn['success'] = true;
		$rtn['message'] = "更新请求已提交,稍候手动刷新页面!";
		//如果是手工触发立即更新，吧状态改为 查询中
		if ($user_require_update){
			//ystest starts
			//保存之前的有效状态到 addiinfo
			if ($aTracking->status <> Tracking::getSysStatus("查询中")){
				$addi_info1 = [];
				if (!empty($aTracking->addi_info))
					$addi_info1 = json_decode($aTracking->addi_info,true);
			
				$addi_info1['last_status'] = $aTracking->status;
				$aTracking->addi_info = json_encode($addi_info1);
			}
			//ystest end
			
			$aTracking->status = Tracking::getSysStatus("查询中");
			
		}
		
		//最后 保存当前的执行时间 
		$tracking_addi_info = [];
		if (!empty($aTracking->addi_info))
			$tracking_addi_info = json_decode($aTracking->addi_info,true);
		
		$tracking_addi_info['last_manual_refresh_time'] = date('Y-m-d H:i:s');
		$aTracking->addi_info = json_encode($tracking_addi_info);
		$aTracking->save(false);
		return $rtn;		
	}//end of function generate One Request For Tracking
	
	
	
	static public function getSuccessCarrierFromHistoryByCodePattern($pattern,$forDate){
		$selected_carrier_code='';
		$successCount = 0;
		$carrier_success_rate = ConfigHelper::getConfig("Tracking/carrier_success_rate_$forDate",'NO_CACHE');
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
	 * API队列处理器。按照priority执行一个API，然后把结果以及状态update到queue，
	 * 同时把信息写到每个user数据库的 Tracking 表中.
	 * 该方法只会执行排在最前面的一个request，然后就返回了，不会持续执行好多
	 * 该任务支持多进程并发执行
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param 
	 +---------------------------------------------------------------------------------------------
	 * @return						array('success'=true,'message'='')
	 *
	 * @invoking					TrackingHelper::queueHandlerProcessing1();
	 *
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		yzq		2015/2/9				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function queueHandlerProcessing1($target_track_no=''){
		global $CACHE ;
		if (!isset($CACHE['Tracking']['MainQueueForPuid']['priority']))
			$CACHE['Tracking']['MainQueueForPuid']['priority'] = 0;

		$WriteLog = false;
		if ($WriteLog)		
		\Yii::info(['Tracking',__CLASS__,__FUNCTION__,'Background',"MainQueue 0 Enter:".$CACHE['JOBID'] ],"edb\global");
		
		$rtn['message'] = "";
		$rtn['success'] = true;
		$now_str = date('Y-m-d H:i:s');
		$seedMax = 15;
		$seed = rand(0,$seedMax);
		$one_go_count = 10;
		//Step 0, 检查当前本job版本，如果和自己的版本不一样，就自动exit吧
		
		$JOBID=$CACHE['JOBID'];
		$current_time=explode(" ",microtime());	$start1_time=round($current_time[0]*1000+$current_time[1]*1000);	
		
		if (self::$TRACKER_FILE_LOG)	
			\Yii::info("multiple_process_main step1 mainjobid=$JOBID");

		$currentMainQueueVersion = ConfigHelper::getGlobalConfig("Tracking/mainQueueVersion",'NO_CACHE');
		if (empty($currentMainQueueVersion))
			$currentMainQueueVersion = 0;
		
		//如果自己还没有定义，去使用global config来初始化自己
		if (empty(self::$mainQueueVersion))
			self::$mainQueueVersion = $currentMainQueueVersion;
			
		//如果自己的version不同于全局要求的version，自己退出，从新起job来使用新代码
		if (self::$mainQueueVersion <> $currentMainQueueVersion){
			TrackingAgentHelper::extCallSum( );
			exit("Version new $currentMainQueueVersion , this job ver ".self::$mainQueueVersion." exits for using new version $currentMainQueueVersion.");
		}

		//step 1, try to get a pending request in queue, according to priority

		//防止一个客户太多request，每次随机一个数，优先处理puid mod 5 ==seed 的这个
		if ($target_track_no == ''){
			if (!isset($CACHE['Tracking']['MainQueueForPuid']['restTimes']))
				$CACHE['Tracking']['MainQueueForPuid']['restTimes'] = 0;
	
			if ($CACHE['Tracking']['MainQueueForPuid']['restTimes'] > 0){
				$usingHous = $CACHE['Tracking']['MainQueueForPuid']['howManyHours'];
				$andString = $CACHE['Tracking']['MainQueueForPuid']['sqlAndString'];
			}else{
				$usingHous = 3;
				$CACHE['Tracking']['MainQueueForPuid']['howManyHours']=0;
				$andString = '';
			}
			$pendingOne = null;
			while ( empty($pendingOne)  and $usingHous<=96*2){ //96 is max, means no criteria
				//actually max hours is 48, if more, skip active puid 条件
				
				if ($andString=='' and $usingHous<=48)
					$andString = self::andForPuidLastTouchDuringHours($usingHous) ;
				
				//查询3,6,12,24,48,小时内玩过的
				if ($usingHous < 96*2)
					$coreCriteria = "status='P' and puid % ".($seedMax+1)." = $seed " ;
				else
					$coreCriteria = "status='P' " ;
				
				if ($CACHE['Tracking']['MainQueueForPuid']['priority'] > 0)
					$coreCriteria .= ' and priority in (1, '.$CACHE['Tracking']['MainQueueForPuid']['priority'].")";
			 
				$pendingOne = Yii::$app->db_queue->createCommand(//run_time = -10, 也就是估计可以通过smt api 搞定的，所以早点做掉
						"select * from tracker_api_queue force index (status_2) where $coreCriteria $andString order by priority asc, run_time asc ,id asc limit $one_go_count")
						->queryAll();

				//如果得到结果了，吧结果缓存起来，未来30次使用
				if (!empty($pendingOne) ){
					if ($usingHous == $CACHE['Tracking']['MainQueueForPuid']['howManyHours'])
						$CACHE['Tracking']['MainQueueForPuid']['restTimes'] --; //等于0的时候，下次进来就会从3hours从新计算
					else{//save 这个结果，下次进来仍然使用
						$CACHE['Tracking']['MainQueueForPuid']['howManyHours'] = $usingHous;
						$CACHE['Tracking']['MainQueueForPuid']['restTimes'] = 30;
						$CACHE['Tracking']['MainQueueForPuid']['sqlAndString'] = $andString;
					}
					 
				}else {//if no result, make priroity as 0
					$CACHE['Tracking']['MainQueueForPuid']['priority']=0;
				}
				
				//下一个时段，扩大2倍，
				$usingHous *= 2;
				$andString = '';
			}
			
		}else{//如果是指定哪个track no 执行了，就load那个好了
			$pendingOne = TrackerApiQueue::find()
							->andWhere("status='P' and track_no=:track_no", array(':track_no'=>$target_track_no) )
							->limit($one_go_count)
							->asArray()
							->all();
		}
		
if ($WriteLog)		\Yii::info(['Tracking',__CLASS__,__FUNCTION__,'Background',"MainQueue 1 Enter:".$CACHE['JOBID'] ." 已经成功Load到一个pending task"],"edb\global");

     $current_time=explode(" ",microtime()); $start2_time=round($current_time[0]*1000+$current_time[1]*1000);
     if (self::$TRACKER_FILE_LOG)
		\Yii::info("multiple_process_main step2 mainjobid=$JOBID,t2_t1=".($start2_time-$start1_time));
     TrackingAgentHelper::extCallSum("Trk.MainQPickOne",$start2_time-$start1_time);
     
		//if no pending one found, return true, message = 'n/a';
		if ( empty($pendingOne)  ){
			$rtn['message'] = "n/a";
			$rtn['success'] = true;
			//echo "No pending, idle 4 sec... ";
			$current_time=explode(" ",microtime()); $start2_time=round($current_time[0]*1000+$current_time[1]*1000);
     if (self::$TRACKER_FILE_LOG)
		\Yii::info("multiple_process_main get-no-P sleep4 mainjobid=$JOBID,t2_t1=".($start2_time-$start1_time));			
			return $rtn;
		}
		
		$pendingOnes= $pendingOne;
		//step 2.0, update 这个request，告知其他队列处理器，这个我来做
		$connection = Yii::$app->db;
		$affectRows = 0;
		//尝试从5个种找到一个可以lock 为S状态的
		foreach ($pendingOnes as $pendingOne){//db_queue
			$command = Yii::$app->db_queue->createCommand("update tracker_api_queue set status='S' ,update_time='$now_str'
				where id =". $pendingOne['id'].
				($target_track_no==''?" and status='P' ":"")  );							
			
			$affectRows = $command->execute();
			if ($affectRows > 0) 
				break;			
		}//end of each of the 5 pendings
		
		if ($affectRows == 0){
			$message = "进程处理同一个请求冲突，本进程退出:".$CACHE['JOBID'] ." ";
			//if ($WriteLog)	 \Yii::info(['Tracking', __CLASS__, __FUNCTION__,'Background',$message],"edb\global");
			$rtn['message'] .= TranslateHelper::t($message);
			$rtn['success'] = false;
			$current_time=explode(" ",microtime()); $start3_time=round($current_time[0]*1000+$current_time[1]*1000);
     if (self::$TRACKER_FILE_LOG)
		\Yii::info("multiple_process_main step3 conflict mainjobid=$JOBID,t3_t2=".($start3_time-$start2_time));
			return $rtn;
		}else{//由于上面SQL进行了update field status，重新Load一次，否则YII Model save 会出bug
			$pendingOne = TrackerApiQueue::find()
			->andWhere("id=:id",array(':id'=>$pendingOne['id']) )
			->one();
			
			if ($target_track_no == ''){
				//每5次就把priority重新变回0，以防忘记了处理High priority
				if ($CACHE['Tracking']['MainQueueForPuid']['restTimes'] % 20 == 0)
					$CACHE['Tracking']['MainQueueForPuid']['priority'] = 0;
				else
					$CACHE['Tracking']['MainQueueForPuid']['priority'] = $pendingOne->priority;
			}
		}
	 
if ($WriteLog)	\Yii::info(['Tracking',__CLASS__,__FUNCTION__,'Background',"MainQueue 2 Enter:".$CACHE['JOBID'] ." 已经成功Mark S status"],"edb\global");

			     $current_time=explode(" ",microtime()); $start3_time=round($current_time[0]*1000+$current_time[1]*1000);
     if (self::$TRACKER_FILE_LOG)
		 \Yii::info("multiple_process_main step3 getit mainjobid=$JOBID,t3_t2=".($start3_time-$start2_time));

		//step 2.1, 读取tracking 实体
	$TrackingOrigState = '';
	$TrackingOrigStatus = '';
	$stay_days_too_long_try_other_carrier = false;
	
		if ($pendingOne){
			$pendingOne->update_time = $now_str;
			 
			$track_no = $pendingOne->track_no;
			$aTracking = Tracking::find()
				->andWhere("track_no=:trackno",array(':trackno'=>$track_no) )
				->one();
			
			if ($aTracking <> null){
				$TrackingOrigState = $aTracking->state;
				$TrackingOrigStatus = $aTracking->status;
				if ($aTracking->stay_days >= 10  and ($aTracking->stay_days % 5) == 0){ //and $aTracking->stay_days <=30
					$today = substr($now_str,0,10);
					$addiInfo = json_decode($aTracking->addi_info,true);
					if (empty($addiInfo[$today." try other carrier"]) )  {
						$addiInfo[$today." try other carrier"] = 1;
						$stay_days_too_long_try_other_carrier = true;
						$aTracking->addi_info = json_encode($addiInfo);
						$aTracking->save(false);
					}					
				}
			}else{
				$pendingOne->status = 'I';				
				$pendingOne->save();
				$message = self::commitTrackingResultUsingValue(array('track_no'=>$pendingOne->track_no,'status'=>'suspend'),$pendingOne->puid);
				$rtn['message'] = "ignore";
				$rtn['success'] = false;
					
				\Yii::info(['Tracking',__CLASS__,__FUNCTION__,'Background',"Failed to Load Tracking ".$pendingOne->track_no ],"edb\global");
		     
     if (self::$TRACKER_FILE_LOG)
		\Yii::info("multiple_process_main step3-1 mainjobid=$JOBID");
				return $rtn;
			}
		}else{
			$rtn['message'] = "ignore";
			$rtn['success'] = false;
				
			\Yii::info(['Tracking',__CLASS__,__FUNCTION__,'Background',"Failed to Load queue task for ".$pendingOne->track_no ],"edb\global");
     if (self::$TRACKER_FILE_LOG)
		\Yii::info("multiple_process_main step3-2 mainjobid=$JOBID");			
			return $rtn;
		}
			if ($WriteLog) \Yii::info(['Tracking',__CLASS__,__FUNCTION__,'Background',"MainQueue Handler Monitor 3 job:".$CACHE['JOBID'] ],"edb\global");
	     $current_time=explode(" ",microtime()); $start4_time=round($current_time[0]*1000+$current_time[1]*1000);
     if (self::$TRACKER_FILE_LOG)
		\Yii::info("multiple_process_main step3 getit mainjobid=$JOBID,t4_t3=".($start4_time-$start3_time));

     //ystest starts
     $TrackingLastStatus = $TrackingOrigStatus;
     if ($TrackingLastStatus == Tracking::getSysStatus("查询中")){
     	$addiInfo = json_decode($aTracking->addi_info,true);
     	if (!empty($addiInfo['last_status']))
     		$TrackingLastStatus = $addiInfo['last_status'];
     }
     //ystest ends
     $aliResultParsed = array();
     $aliLastEventDate = '';

     /*
       1）smt 订单优先使用smt 查询包裹
       2）如果smt 没有查询到结果，用17track
       3）如果smt 有结果，判断其 最新事件是否3天内，如果是，应用smt 查询结果， 否的话是用17Track，如果17Track 有结果，smt 有结果，取时间最近的结果为准
      */
			//step 0, 判断这个订单是否smt，如果是，先玩玩smt api直接parcel，如果失败了，在用17Track
			$toNation2Code = $aTracking->getConsignee_country_code();
     		if ($aTracking->source =='O' and $aTracking->platform =='aliexpress' and !empty($toNation2Code ) ){
     			$aliResult = TrackingAgentHelper::queryAliexpressParcel($aTracking->seller_id,$aTracking->ship_by,$aTracking->track_no,$toNation2Code, $aTracking->order_id);
				if ($aliResult['success']){
					$aliResultParsed = $aliResult['parsedResult'];     				
     				$aliLastEventDate = (isset($aliResultParsed['last_event_date']) ? $aliResultParsed['last_event_date'] :'');
     				
     				$daysAgo = date('Y-m-d H:i:s',strtotime('-3 days'));
     				//如果smt返回的最后时间日期是3天内的，可信，直接拿这个result了，否则要和 17track的result 对比
     				if (strlen($aliLastEventDate) >= 10 and substr($aliLastEventDate,0,10) >= substr($daysAgo,0,10) ){
     					$rtn = self::commitTrackingResultUsingValue($aliResult['parsedResult'], $pendingOne->puid);
	     				$pendingOne->status='C';
	     				$pendingOne->selected_carrier = 999000001 ; // mark for using 阿里explress查询接口
	     				$pendingOne->save(false);
	     				$rtn['message'] = "using 阿里explress查询接口 Success";
	     				$rtn['success'] = true;
	     				return $rtn;
     				}
				}
     		}

			//Step 1， 判断这个puid 的账号健康度，如果不太健康了，ignore这个task
			$track_success_distribute_str = ConfigHelper::getConfig("Tracking/success_distribute",'NO_CACHE');
			$track_success_distribute = json_decode($track_success_distribute_str,true);
			if (empty($track_success_distribute)) $track_success_distribute = array();

			//判断是否这个order 的order date 都是时间太近而不成功的，如果是，skip 时间太近的那些
			$thisDate = date('Y-m-d');
			$days_gap = -1;
			if (!empty($aTracking->ship_out_date)){
				$d1=strtotime($thisDate);
				$d2=strtotime($aTracking->ship_out_date);
				$days_gap = round(($d1-$d2)/3600/24);
			}

			if ($WriteLog) \Yii::info(['Tracking',__CLASS__,__FUNCTION__,'Background',"MainQueue Handler Monitor 4 job:".$CACHE['JOBID'] ],"edb\global");
			
			//如果优先级高，就不考虑 skip 了，都做一下查询
			if ($days_gap >= 0 and $pendingOne->priority > 2){
				$format_print_letr = CarrierTypeOfTrackNumber::getCodeFormatOfString($pendingOne->track_no,true );
				$track_letr_pattern_daysgap_str = ConfigHelper::getConfig("Tracking/letr_pattern_daysgap_$thisDate",'NO_CACHE');
				$track_letr_pattern_daysgap = json_decode($track_letr_pattern_daysgap_str,true);
				if (empty($track_letr_pattern_daysgap)) $track_letr_pattern_daysgap = array();
			
				//如果这个pattern 的物流号在这个时间段失败数量大于成功数量的10个，就不做了
				if (!isset($track_letr_pattern_daysgap["$format_print_letr $days_gap fail"]))
					$track_letr_pattern_daysgap["$format_print_letr $days_gap fail"] = 0;
				if (!isset($track_letr_pattern_daysgap["$format_print_letr $days_gap success"]))
					$track_letr_pattern_daysgap["$format_print_letr $days_gap success"] = 0;
					
				if ($track_letr_pattern_daysgap["$format_print_letr $days_gap fail"] - $track_letr_pattern_daysgap["$format_print_letr $days_gap success"] > 10
					and  $track_letr_pattern_daysgap["$format_print_letr $days_gap fail"] > 2* $track_letr_pattern_daysgap["$format_print_letr $days_gap success"]
					){
					$message ="Ignore Puid ".$pendingOne->puid." Tracking ".$pendingOne->track_no ." 这个pattern 的物流号在这个时间段失败数量大于成功数量的10个，就不做了";
					$pendingOne->status = 'I';
					$pendingOne->addinfo = $message;
					$pendingOne->save();
					self::commitTrackingResultUsingValue(array('track_no'=>$pendingOne->track_no,'status'=>'suspend'),$pendingOne->puid);
					$rtn['message'] = "ignore";
					$rtn['success'] = false;
			
					//\Yii::info(['Tracking',__CLASS__,__FUNCTION__,'Background',$message ],"edb\global");
					return $rtn;
				}
			}//end of days gap

			$original_seleted_carrier = $pendingOne->selected_carrier;
			$candidate_tried = array();
		  //step 3, 生成sub queue 任务，并发对可能的渠道 进行本请求的处理
			//step 3.1, according to 物流号规则判断是那种物流类型渠道的
			$track_no_like_global_post = false;
			$track_no_like_global_post = (CarrierTypeOfTrackNumber::getCodeFormatOfString($pendingOne->track_no) == "##*********##"); //LK484334664CN
			//如果是燕文的，就不要搞了
			if ($track_no_like_global_post and strtoupper( substr($pendingOne->track_no,0,1)) =='Y')
				$track_no_like_global_post = false;
			
			$main_queue_task_selected_carrier = $pendingOne->selected_carrier;
			if ($pendingOne->candidate_carriers <> ''){
				$candidate_carrier_types = explode(",", $pendingOne->candidate_carriers);
				
			}else{
				$candidate_carrier_types = self::getCandidateCarrierType($pendingOne->track_no);
				//when there is no returned, check all candidates
				if (empty($candidate_carrier_types) or count($candidate_carrier_types) < 1)
					$candidate_carrier_types =self::getAllCandidateCarrierType(); 
					
				$pendingOne->candidate_carriers = implode(",", $candidate_carrier_types);
			}
			 

			//step 3.2, 如果selected_carrier = -1， 表示上次执行发现推测的所有carrier都没有结果，本次是翻工
			//          那么就全部candidate carrier都试试。
			//          如果selected_carrier >= 0, 上次的candidate查找已经成功。使用上次的就可以。
			//          如果selected_carrier = -100, 初始执行，检查该用户常用的carrier type，按照常用顺序来attampt
			// 如果常用第一个就成功并且常用第一个使用频率是第二个的1倍以上，差的绝对值大于5，那么就放弃第二个可能的carrier type
			//     否则尝试第二个carrier，如果多个carrier都有结果，以时间最近的carrier 为准
			$candidate_tried = array();
			
			//Load 该客户历史统计的渠道使用比例
			$carrier_frequency_str = ConfigHelper::getConfig("Tracking/carrier_frequency",'NO_CACHE');
			$carrier_frequency = json_decode($carrier_frequency_str,true);
			/*Sample Data: $carrier_frequency = array('0'=>50, '10009'=>23) */
			if (empty($carrier_frequency)) $carrier_frequency = array();
			if (!is_array($carrier_frequency))
				$carrier_frequency = array();
						
			//	倒序排序并且保持 key 和 value 的关系
			arsort ($carrier_frequency);
			
			//如果selected_carrier > 0, 上次的candidate查找已经成功。使用上次的就可以。
			if ($pendingOne->selected_carrier >= 0){
				//echo "YS3.1.a found main queue selected carrier =".$pendingOne->selected_carrier;
				if (! $stay_days_too_long_try_other_carrier ){
					if ($pendingOne->selected_carrier <> 999000001){ //这个是smt，不需要做subQueue处理的
						$candidate_tried[ 'a'.$pendingOne->selected_carrier ] =  1;
						$rtn = self::insertOneSubQueue($pendingOne->getAttributes(), $pendingOne->selected_carrier,$TrackingLastStatus,$aTracking->getConsignee_country_code() );		//ystest
					}	
					//如果已经指定了的 carrier type 不是 0 （全球邮政），但是 物流格式是 0，那么，添加一个 使用 全球邮政 查询的请求，如果全球邮政有结果，优先用他的
					if ($pendingOne->selected_carrier <> 0 and $track_no_like_global_post){
						$candidate_tried[ 'a0' ] =  1;
						$rtn = self::insertOneSubQueue($pendingOne->getAttributes(), 0,$TrackingLastStatus,$aTracking->getConsignee_country_code());//ystest
					}
				}else{//运输途中太久，并且上次用的不是全球邮政，尝试用用其他的查查
					$candidate_carrier_types = self::getCandidateCarrierType($pendingOne->track_no);
					//\Yii::info(['Tracking',__CLASS__,__FUNCTION__,'Background',$pendingOne->track_no." stay_days_too_long_try_other_carrier : $stay_days_too_long_try_other_carrier , try to do with carrriers ".print_r($candidate_carrier_types,true)." ".$CACHE['JOBID'] ],"edb\global");
					foreach ($candidate_carrier_types as $carrier_type){
						if ($pendingOne->selected_carrier == $carrier_type)
							continue;
						
						if ($carrier_type <> 999000001){ //这个是smt，不需要做subQueue处理的
							$candidate_tried['a'.$carrier_type] = 1;
							//随便insert，如果这个main queue id 和carrier type 组合之前试过了，会skip的。
							self::insertOneSubQueue($pendingOne->getAttributes(), $carrier_type,$TrackingLastStatus,$aTracking->getConsignee_country_code());//ystest
						}
						//如果已经指定了的 carrier type 不是 0 （全球邮政），但是 物流格式是 0，那么，添加一个 使用 全球邮政 查询的请求，如果全球邮政有结果，优先用他的
						if (  $track_no_like_global_post and !isset($candidate_tried['a0'])){
							$candidate_tried[ 'a0' ] =  1;
							$rtn = self::insertOneSubQueue($pendingOne->getAttributes(), 0);
						}
					 
					}//end of each candidate carrier type
				}//end of 运输过久，用用其他的看 已签收没有
			}
			
			//如果selected_carrier = 0, 检查该用户常用的carrier type，按照常用顺序来attampt
			//如果selected_carrier = -100, 初始执行，检查该用户常用的carrier type，按照常用顺序来attampt
			//如果是-100，也就是第一次尝试，并且该用户录入的 ship by 已经有上次成功的记录了，那么先尝试只用上次的那个  
			if ($WriteLog) \Yii::info(['Tracking',__CLASS__,__FUNCTION__,'Background',"MainQueue Handler Monitor 7 job:".$CACHE['JOBID'] ],"edb\global");

			$current_time=explode(" ",microtime()); $start5_time=round($current_time[0]*1000+$current_time[1]*1000);
			
     if (self::$TRACKER_FILE_LOG)
		 \Yii::info("multiple_process_main step5 getit mainjobid=$JOBID,t5_t4=".($start5_time-$start4_time));

			if ($pendingOne->selected_carrier == -100){
				TrackingAgentHelper::extCallSum("Tracking.FirstTry",0,false);
				$history_success_carrier = self::getSuccessCarrierFromHistoryByCodePattern(CarrierTypeOfTrackNumber::getCodeFormatOfString($pendingOne->track_no,true),date('Y-m-d'));
				if ($history_success_carrier == '')
					$history_success_carrier = self::getSuccessCarrierFromHistoryByCodePattern(CarrierTypeOfTrackNumber::getCodeFormatOfString($pendingOne->track_no,true),date('Y-m-d',strtotime('-1 day')) );
				
				if ($history_success_carrier <> '')
					$carrier_frequency = array($history_success_carrier => 100);
				
				//除了历史使用统计作为参考，更重要是使用客户的ship by 作为参考，如果ship by写明了，优先是有ship by 写的方式
				$shipBy = $aTracking->ship_by;
				$biggestFreq = 100;
				if (!empty($shipBy)){
					$allShipBy =  CarrierTypeOfTrackNumber::getAllExpressCode( );
					//array('0'=>'全球邮政',	'100001'=>'DHL', ... )
					foreach ($allShipBy as $shipCode=>$shipName){
						if ($shipCode<>'0') //如果不是中国邮政，普通名字匹配就可以
							$matched = (stripos(strtolower($shipName),strtolower($shipBy)) !== false or 
										stripos(strtolower($shipBy),strtolower($shipName)) !== false  );
						else{ // 中国邮政，关键字比较多，这里展开一下
							$matched = false;
							$shipNames = array('邮政','post','中邮','USPS','E邮','EUB');
							foreach ($shipNames as $name1){
								$matched = (stripos($name1,$shipBy) !== false or stripos($shipBy,$name1) !== false);
								if ($matched) break;
							}//end of each possible for carrier code 1
						}
						
						if ( $matched ){
							$biggestFreq -- ;
							$carrier_frequency[$shipCode]=$biggestFreq;
						}
					}//end of each shipBy method
					//	倒序排序并且保持 key 和 value 的关系
					arsort ($carrier_frequency);
				}//end of ship by got iput
			}//end of when carrier type = -100, means has no idea what carrier

			//echo "ys abc ".count($carrier_frequency)."<br>";
			if ($pendingOne->selected_carrier == -100 and count($carrier_frequency)> 0){
				 
				$results_array = array();
				
				//从以往历史中，使用频率最高的开始尝试,找出
				$last_frequence = 0;
				
				foreach ($carrier_frequency as $carrier_type => $frequence){
					//	如果上一个用得多的carrier比这个多了10，那么就认为不会是这个了，skip吧
					//echo "try to fuck $carrier_type ,  last_frequence = $last_frequence ,frequence=$frequence , is global post $track_no_like_global_post <br>";
					if ($last_frequence > 0 and $last_frequence >= $frequence + 9  ){
						if (!($track_no_like_global_post and $carrier_type == '190012')){
							//echo "Post? $track_no_like_global_post Tracking ".$pendingOne->track_no ." 因为当前$carrier_type 使用频率比上一个少10。skip这个carrier尝试".print_r($carrier_frequency,true)."<br>";
							//\Yii::info(['Tracking',__CLASS__,__FUNCTION__,'Background',"Tracking ".$pendingOne->track_no ." 因为当前$carrier_type 使用频率比上一个少10，并且上一个物流已经查到结果。skip这个carrier尝试" ],"edb\global");
							//如果是 全球邮政类型的，并且又配拍到190012，那就不要跳过 燕文						
							continue;
						}
					}
					
				 
					//	将要并发进行的不同carrier type写入到sub queue，然后等待结果返回来				 
					$last_frequence = $frequence;
					
					if ($carrier_type <> 999000001){ //这个是smt，不需要做subQueue处理的
						$candidate_tried['a'.$carrier_type.''] = 1;		
						self::insertOneSubQueue($pendingOne->getAttributes(), $carrier_type,$TrackingLastStatus,$aTracking->getConsignee_country_code());//ystest
					}
					
					
					//如果已经指定了的 carrier type 不是 0 （全球邮政），但是 物流格式是 0，那么，添加一个 使用 全球邮政 查询的请求，如果全球邮政有结果，优先用他的
					if (  $track_no_like_global_post and !isset($candidate_tried['a0'])){
						$candidate_tried[ 'a0' ] =  1;
						$rtn = self::insertOneSubQueue($pendingOne->getAttributes(), 0,$TrackingLastStatus,$aTracking->getConsignee_country_code());//ystest
					}
									 								 
				}//end of each carrier frequency in history
			}//end of when selected carrier == 0, means first run of such track no
			 //echo "YS 4 ."
			//如果selected_carrier = -1， 表示上次执行发现推测的所有carrier都没有结果，本次是翻工,全部都尝试
			if ($pendingOne->selected_carrier == -1 or count($carrier_frequency)== 0){					
				foreach ($candidate_carrier_types as $carrier_type){
					
					//如果是全球邮政的 物流号，不要尝试其他无聊的，浪费
					if ($track_no_like_global_post and $carrier_type <> 0)
						continue;
					
					//记录统计这个Carrier Type for 这种track format成功与否
					//Load 该客户今天的carrier type 成功记录
					$forDate = date('Y-m-d');
					$format_print = CarrierTypeOfTrackNumber::getCodeFormatOfString($pendingOne->track_no,true );
					$carrier_success_rate = ConfigHelper::getConfig("Tracking/carrier_success_rate_$forDate",'NO_CACHE');
					$carrier_success_rate = json_decode($carrier_success_rate,true);
					/*Sample Data: $carrier_success_rate = array('RG******CN'=>array('0'=>array('Success'=>10,'Fail'=>5))) */
					if (empty($carrier_success_rate)) $carrier_success_rate = array();
					
					if (empty($carrier_success_rate[$format_print][$carrier_type]['Success']))
						$carrier_success_rate[$format_print][$carrier_type]['Success']=0;
					
					if (empty($carrier_success_rate[$format_print][$carrier_type]['Fail']))
						$carrier_success_rate[$format_print][$carrier_type]['Fail']=0;
					
					if ($carrier_success_rate[$format_print][$carrier_type]['Success'] == 0 and
						$carrier_success_rate[$format_print][$carrier_type]['Fail'] >10){
						//Do Nothing, ignore such carrier type for this format print
					}else{
						if ($carrier_type <> 999000001){ //这个是smt，不需要做subQueue处理的
							$candidate_tried['a'.$carrier_type] = 1;
							//随便insert，如果这个main queue id 和carrier type 组合之前试过了，会skip的。
							self::insertOneSubQueue($pendingOne->getAttributes(), $carrier_type,$TrackingLastStatus,$aTracking->getConsignee_country_code());//ystest
						}
						//如果已经指定了的 carrier type 不是 0 （全球邮政），但是 物流格式是 0，那么，添加一个 使用 全球邮政 查询的请求，如果全球邮政有结果，优先用他的
						if (  $track_no_like_global_post and !isset($candidate_tried['a0'])){
							$candidate_tried[ 'a0' ] =  1;
							$rtn = self::insertOneSubQueue($pendingOne->getAttributes(), 0);
						}
					}
				}//end of each candidate carrier type
			} // end of selected_carrier = -1， 表示上次执行发现推测的所有carrier都没有结果
			 if ($WriteLog) \Yii::info(['Tracking',__CLASS__,__FUNCTION__,'Background',"MainQueue Handler Monitor 8,wait for subQueue job:".$CACHE['JOBID'] ],"edb\global");			
			//step 3.3,sleep,然后获取结果，如果一个结果都没有获取得到，尝试把 seleted Candidate 设置为 -1，那么
			//下次再做这个request会全部candidate都try
			//如果有多个结果获得了，获取时间最近的那个
			$candidateDoneCount = 0;
			$SeletedSuccessCarrierType = -1;
			$elapsedTimeSeconds = 0;
			$earliestEventOfResult = '';	
			$thisCarrierTypeSuccess = 'Fail';
			 // echo " YS6 .". print_r($candidate_tried,true)." and ready to collect results" ; return;
			if (count($candidate_tried) > 0){
				do{
					$sleep_time = 1;
					sleep($sleep_time); //wait for 3 seconds each loop, max time out 60 seconds
					$elapsedTimeSeconds += $sleep_time;
					$TrackerApiSubQueuesDone = TrackerApiSubQueue::find()
						->andWhere("main_queue_id=:main_queue_id and sub_queue_status='C'",array(':main_queue_id'=>$pendingOne->id) )
						->all();
					
					foreach ($TrackerApiSubQueuesDone as $aSubQueueDone){
						if (isset($parsed17TrackResultForSubqueue[$aSubQueueDone->carrier_type]))
							continue;
						
						if ($aSubQueueDone->carrier_type=='888000001'){ //UBI
							$parse17TrackResult = TrackingAgentHelper::parseUbiResult($aSubQueueDone->result,$aSubQueueDone->track_no );
						}
						elseif ($aSubQueueDone->carrier_type=='888000002'){ //equick
							$parse17TrackResult = TrackingAgentHelper::parseEquickResult($aSubQueueDone->result,$aSubQueueDone->track_no );
						}else
							$parse17TrackResult = TrackingAgentHelper::parse17TrackResult($aSubQueueDone->result,$aSubQueueDone->track_no );
						
						$thisResult = $parse17TrackResult['parsedResult'];
						//\Yii::info(['Tracking',__CLASS__,__FUNCTION__,'Background',$aSubQueueDone->track_no ." Got result:".print_r($thisResult,true)],"edb\global");
						//when 17Track return code is not 1, the 'success' = fasel
						//ret: (1)->查询成功, (-3)->系统更新, (-4)->客户端没有授权, (-5)->客户端IP被禁止, (-7)->没有提交单号 
						if (isset($parse17TrackResult['success']) and $parse17TrackResult['success'] and 
							!empty($thisResult['first_event_date'] ) ){
							//如果已经选择了的carrier是0（全球邮政的结果），那么不要使用其他的结果了，尽管其他结果日期更加早，因为可能是错的
							//或者 如果 $track_no_like_global_post 是true，并且 有 结果的carrier type = 0 （全球邮政的结果），那么就用全球邮政吧
							if ($earliestEventOfResult < $thisResult['first_event_date'] and $SeletedSuccessCarrierType <> 0 or $aSubQueueDone->carrier_type == 0 and $track_no_like_global_post){
								$earliestEventOfResult = $thisResult['first_event_date'];
								$SeletedSuccessCarrierType = $aSubQueueDone->carrier_type;
								$thisCarrierTypeSuccess = 'Success';
							}
						}else 
							$thisCarrierTypeSuccess = 'Fail';

						$subQueueRecord[$aSubQueueDone->carrier_type] = $aSubQueueDone;
						$parsed17TrackResultForSubqueue[$aSubQueueDone->carrier_type] = $thisResult;

						//记录统计这个Carrier Type for 这种track format成功与否
						//Load 该客户今天的carrier type 成功记录
						$forDate = date('Y-m-d');
						$format_print = CarrierTypeOfTrackNumber::getCodeFormatOfString($aSubQueueDone->track_no,true );
						$carrier_success_rate = ConfigHelper::getConfig("Tracking/carrier_success_rate_$forDate",'NO_CACHE');
						$carrier_success_rate = json_decode($carrier_success_rate,true);
						/*Sample Data: $carrier_success_rate = array('RG******CN'=>array('0'=>array('Success'=>10,'Fail'=>5))) */
						if (empty($carrier_success_rate)) $carrier_success_rate = array();
						
						if (!isset($carrier_success_rate[$format_print][$aSubQueueDone->carrier_type][$thisCarrierTypeSuccess]))
							$carrier_success_rate[$format_print][$aSubQueueDone->carrier_type][$thisCarrierTypeSuccess] = 0;
						
						$carrier_success_rate[$format_print][$aSubQueueDone->carrier_type][$thisCarrierTypeSuccess] ++;
						
						ConfigHelper::setConfig("Tracking/carrier_success_rate_$forDate", json_encode($carrier_success_rate));
												
					}//end of each SubQueueDone
					
					$candidateDoneCount = count($TrackerApiSubQueuesDone);
					
					//如果以上已完成的 sub queue数量不等于tried 数量，就是还有一些是pending 或者 S
				 	if ($candidateDoneCount < count($candidate_tried) )
						$candidateDoneCount = TrackerApiSubQueue::find()->andWhere("main_queue_id=:main_queue_id and sub_queue_status in ('C','F')",array(':main_queue_id'=>$pendingOne->id) )->count();
					
				}while( $candidateDoneCount < count($candidate_tried)  and $elapsedTimeSeconds < 60 * 5); 
			}//end if candidate tried > 0
			
			$errorMsg = "candidateDoneCount = $candidateDoneCount, count(candidate_tried) = ". count($candidate_tried).",elapsedTimeSeconds=$elapsedTimeSeconds ;";
			$timeOut=false;
			if ($candidateDoneCount < count($candidate_tried)){
				$timeOut=true;
				$errorMsg .="so time out;";
			}
			//\Yii::info(['Tracking',__CLASS__,__FUNCTION__,'Background',"MainQueue Handler Monitor 10,done from subQueue job:".$CACHE['JOBID'] ],"edb\global");		
		//step 4, update such request and its tracking record
		//如果 $SeletedSuccessCarrierType = -1，就是还没有找到任何有结果的
		//把这次的carrier type使用次数+1 写到 config中，次数最大值是 100，也就是太久的使用频率不会很影响近期的carrier使用
			//Load 该客户历史统计的渠道使用比例
			$carrier_frequency_str = ConfigHelper::getConfig("Tracking/carrier_frequency",'NO_CACHE');
			$carrier_frequency = json_decode($carrier_frequency_str,true);
			/*Sample Data: $carrier_frequency = array('0'=>50, '10009'=>23) */
			if (empty($carrier_frequency)) $carrier_frequency = array();
			if (!is_array($carrier_frequency))
				$carrier_frequency = array();
			
			//	倒序排序并且保持 key 和 value 的关系
			arsort ($carrier_frequency);

			$results['success'] = false;
			//echo "4.1.a using SeletedSuccessCarrierType = $SeletedSuccessCarrierType ";
			/*
			 *   $aliResultParsed = array();
     				$aliLastEventDate = '';
     				如果17Track渠道插叙不到，但是速卖通有结果，用速卖通的
			 * */
			if ($SeletedSuccessCarrierType < 0 and !empty($aliLastEventDate)){
				$SeletedSuccessCarrierType = 999000001;//smt 官方
				$parsed17TrackResultForSubqueue[$SeletedSuccessCarrierType] = $aliResultParsed;
			}else{
				//3）如果smt 有结果，判断其 最新事件是否3天内，如果是，应用smt 查询结果， 否的话是用17Track，如果17Track 有结果，smt 有结果，取时间最近的结果为准				
				if (isset($parsed17TrackResultForSubqueue[$SeletedSuccessCarrierType]['last_event_date']) and 
					isset($aliResultParsed['last_event_date']) and
					$parsed17TrackResultForSubqueue[$SeletedSuccessCarrierType]['last_event_date'] < $aliResultParsed['last_event_date']  ){
					
					$SeletedSuccessCarrierType = 999000001;
					$parsed17TrackResultForSubqueue[$SeletedSuccessCarrierType] = $aliResultParsed;
				}//end of 使用smt 结果覆盖17结果	
			}
			
			
			if ($SeletedSuccessCarrierType >= 0){
				$a_sub_queue_reord = (isset($subQueueRecord[$SeletedSuccessCarrierType]) ? $subQueueRecord[$SeletedSuccessCarrierType] : '');
				$pendingOne->run_time = (isset($a_sub_queue_reord->run_time ) ? $a_sub_queue_reord->run_time : 0);
				//There is no result field in Main Queue, it is in Sub Queue Level now

				//17Track 返回的结果数组d里面的 carrier type，可能是乱来的，所以不可信，这里需要自己overwrite一下
				$parsed17TrackResultForSubqueue[$SeletedSuccessCarrierType]['carrier_type'] = $SeletedSuccessCarrierType;
								
				$rtn = self::commitTrackingResultUsingValue($parsed17TrackResultForSubqueue[$SeletedSuccessCarrierType], $pendingOne->puid);
				 
				$pendingOne->selected_carrier = $SeletedSuccessCarrierType;
				
				//更新历史统计数据
				if (!isset($carrier_frequency[$SeletedSuccessCarrierType]))
					$carrier_frequency[$SeletedSuccessCarrierType] = 0;
				
				$carrier_frequency[$SeletedSuccessCarrierType] ++;
				
				//如果有任何一种渠道累计大于100的，大家都减去10，防止Old data occupies the toilet forever
				if ($carrier_frequency[$SeletedSuccessCarrierType] > 100)
					foreach ($carrier_frequency as $carrier_type_1 => $used_count_1){
						$carrier_frequency[$carrier_type_1] = ($used_count_1 - 10) < 0 ? 0 : ($used_count_1 - 10);
						if ($carrier_frequency[$carrier_type_1] == 0)
							unset($carrier_frequency[$carrier_type_1]);
					}
				
				ConfigHelper::setConfig("Tracking/carrier_frequency",json_encode($carrier_frequency));
			}
			
			$pendingOne->try_count = $pendingOne->try_count + 1;
			$pendingOne->update_time = date('Y-m-d H:i:s');
			if ($WriteLog) \Yii::info(['Tracking',__CLASS__,__FUNCTION__,'Background',"MainQueue Handler Monitor 8.5 ,done from subQueue job:".$CACHE['JOBID'] ],"edb\global");
			$current_time=explode(" ",microtime()); $start6_time=round($current_time[0]*1000+$current_time[1]*1000);
     if (self::$TRACKER_FILE_LOG)
		 \Yii::info("multiple_process_main step6 getit mainjobid=$JOBID,t6_t5=".($start6_time-$start5_time));

			//Step 4.1, check if result means success = true 			
			//if not success, update it as Retry(status still = P) or Failed.(when try count >=3, mark it failed)
			if ($SeletedSuccessCarrierType == -1  ){
				$pendingOne->status = ($pendingOne->try_count >=2 ? "F" : "P");
				if ($pendingOne->status =='F')
					$errorMsg .= "F4.1;";
			}else			
				$pendingOne->status = 'C';

			//如果原来 selected_carrier == -100，是初始状态并且这次 selected Carrier=-1，那么还是让他状态是P，再做一次
			if ($pendingOne->selected_carrier == -1 and $SeletedSuccessCarrierType=-1){
				$pendingOne->status = "F";
				$errorMsg .= "F4.1a;";
				//如果要判定为 F，再做一下的final attemp，
				//判定是否已经使用了全部carrier type，如果否，尝试用全部carrier type
				//当然，要去掉这次已经尝试过的那些 carrier
				
				/*yzq comment 20150803，跳过这个步骤，不要全部carrier都尝试，成本太高了
				 $allCarries = self::getAllCandidateCarrierType();
				$allCarriesWithOutTried = [];
				foreach ($allCarries as $key => $carrierCode1){
				if (!isset($candidate_tried[ 'a'.$carrierCode1 ]))
					$allCarriesWithOutTried[] = $carrierCode1;
				}
				
				if ( count($candidate_carrier_types) < count($allCarriesWithOutTried)){
				$pendingOne->candidate_carriers = implode(",", $allCarriesWithOutTried);
				$pendingOne->status = "P";
				}//end if when not tried all carrier types
				*/
			}

			if ($pendingOne->selected_carrier == -100 and $SeletedSuccessCarrierType=-1){
				/*yzq comment on 2015-8-4
				 * 本来是让他进行重试，并且后面会扶着 selected carrier -1，就全部candidate 重试。
				 * 但是现在为了节省api开销，决定不要对全部carrier重试了，物流号格式匹配到的结果没有就算了
				 * 
				 * $pendingOne->status = "P";
				 * 
				 * */
				//\Yii::info(['Tracking', __CLASS__ , __FUNCTION__ , 'Background' , $pendingOne->track_no." need retry for rest candidates"], "edb\global");
			}
			
			$pendingOne->selected_carrier = $SeletedSuccessCarrierType;
			
			//if status ='P', means try to do once more, so save current to 'F' and then create a new one
			$thisRetry = false;
			if ($pendingOne->status == "P"){
				$aNewPendingOne = new TrackerApiQueue();
				$origData = $pendingOne->getAttributes();
				unset($origData['id']);
				$aNewPendingOne->setAttributes($origData);
				$aNewPendingOne->create_time = date('Y-m-d H:i:s');

				$aNewPendingOne->save(false);
				$thisRetry = true;
				$pendingOne->status = 'F';
				$errorMsg .= "F4.1b;";
			}
			
			//记录下来如果failed的话，原因是啥
			if ($pendingOne->status == "F"){
				$addi = json_decode( $pendingOne->addinfo , true);
				$addi['message'] = $errorMsg;
				$pendingOne->addinfo = json_encode($addi);
			}
			
			if ( $pendingOne->save(false) ){//save successfull
				//如果此次$pendingOne->status = "F"，也就是不成功，先update为查询失败
				if ($pendingOne->status == "F" and !$thisRetry){
					$message = self::commitTrackingResultUsingValue(array('track_no'=>$pendingOne->track_no,'status'=>'no_info'),$pendingOne->puid);
					
					//如果是Timeout 的，写一条在后面，让当前的做完后，尽快重试这一个track no	
				 
					if ($timeOut and strpos( $pendingOne->addinfo,'LastTryTimeOut') !== false ){
						self::generateOneRequestForTracking($pendingOne->track_no, '', json_encode(['LastTryTimeOut']) );
						//吧Api Request Buffer 的批量insert 到db
						self::postTrackingApiQueueBufferToDb();
					}
				}
				
				if ($WriteLog) \Yii::info(['Tracking',__CLASS__,__FUNCTION__,'Background',"MainQueue Handler Monitor 12 job:".$CACHE['JOBID'] ],"edb\global");
				//如果这个物流号原来是 查询中 状态，
				//或者原来是 noinfo，suspend，但是现在不是了，那么就是第一次得到结果，把结果写到统计里面，重做的就不统计了
				if ($TrackingOrigState == Tracking::getSysState("初始") 
				    or ($TrackingOrigStatus=='no_info' or $TrackingOrigStatus=='suspend') 
				    		and  $pendingOne->status == "C" ){
					$thisDate = date('Y-m-d');
					$days_gap = -1;
					if (!empty($aTracking->ship_out_date)){
						$d1=strtotime($thisDate);
						$d2=strtotime($aTracking->ship_out_date);
						$days_gap = round(($d1-$d2)/3600/24);
					}
					
					//	更新统计，判断该用户在近1日，3日，5日 内的tracking 跟踪成功或者失败次数。防止恶意用户玩野
					$track_success_distribute_str = ConfigHelper::getConfig("Tracking/success_distribute",'NO_CACHE');
					$track_success_distribute = json_decode($track_success_distribute_str,true);										
					if (empty($track_success_distribute)) $track_success_distribute = array();
					/*Sample Data: $track_success_distribute = 
					 * 	array('2015-03-10 success'=>50, '2015-03-10 success'=>23, '2015-03-10 ok percent'=50,
				 			* '2015-03-10 fail'=>10, '2015-03-11 fail'=>33, '2015-03-11 ok percent'=50, ) */

					if (!isset($track_success_distribute["$thisDate success"]))
						$track_success_distribute["$thisDate success"]  = 0;
				
					if (!isset($track_success_distribute["$thisDate fail"]))
						$track_success_distribute["$thisDate fail"]  = 0;
				
					if ($pendingOne->status == "F"){
						$track_success_distribute["$thisDate fail"] ++;
					}
				
					if ($pendingOne->status == "C"){
						$track_success_distribute["$thisDate success"] ++;
					}
				
					if ($track_success_distribute["$thisDate success"] + $track_success_distribute["$thisDate success"] > 0)
						$track_success_distribute["$thisDate ok percent"] = 100 *  $track_success_distribute["$thisDate success"] / ($track_success_distribute["$thisDate success"] + $track_success_distribute["$thisDate fail"] );
					
					ConfigHelper::setConfig("Tracking/success_distribute",json_encode($track_success_distribute));				
				
				//更新统计，对这个 带有字母的pattern，统计当天的，订单日期相隔x天的物流号 的成功数量 和失败数量。
					if ($days_gap >= 0){
					
						$format_print_letr = CarrierTypeOfTrackNumber::getCodeFormatOfString($pendingOne->track_no,true );
						$track_letr_pattern_daysgap_str = ConfigHelper::getConfig("Tracking/letr_pattern_daysgap_$thisDate",'NO_CACHE');
						$track_letr_pattern_daysgap = json_decode($track_letr_pattern_daysgap_str,true);
						if (empty($track_letr_pattern_daysgap)) $track_letr_pattern_daysgap = array();
					/*Sample Data: $track_letr_pattern_daysgap =
					 * 	array('2015-03-10 success'=>50, '2015-03-10 success'=>23, '2015-03-10 ok percent'=50,
					 		* '2015-03-10 fail'=>10, '2015-03-11 fail'=>33, '2015-03-11 ok percent'=50, ) */
					
						if (!isset($track_letr_pattern_daysgap["$format_print_letr $days_gap success"]))
							$track_letr_pattern_daysgap["$format_print_letr $days_gap success"]  = 0;
						
						if (!isset($track_letr_pattern_daysgap["$format_print_letr $days_gap fail"]))
							$track_letr_pattern_daysgap["$format_print_letr $days_gap fail"]  = 0;
						
						if ($pendingOne->status == "C"){
							$track_letr_pattern_daysgap["$format_print_letr $days_gap success"] ++;
						} 
						if ($pendingOne->status == "F"){
							$track_letr_pattern_daysgap["$format_print_letr $days_gap fail"] ++;
						}
					
						ConfigHelper::setConfig("Tracking/letr_pattern_daysgap_$thisDate",json_encode($track_letr_pattern_daysgap));
					}//end of days gap >= 0
				}//end of when 本次是该物流号的第一次查询
			}else{
				$message = "ETRK007：保存队列中API请求的执行结果，出现错误.";
				\Yii::info(['Tracking', __CLASS__ , __FUNCTION__ , 'Background' , $message], "edb\global");
				$rtn['message'] .= TranslateHelper::t($message);
				$rtn['success'] = false;
			 	
				foreach ($pendingOne->errors as $k => $anError){
					$rtn['message'] .= ($rtn['message']==""?"":"<br>"). $k.":".$anError[0];
				}//end of each error
	
				return $rtn;
			}//end of save failed
			
			if ($WriteLog)
		\Yii::info(['Tracking',__CLASS__,__FUNCTION__,'Background',"MainQueue 20 Enter:".$CACHE['JOBID'] ." 完成了一个pendign task ".$pendingOne->track_no ],"edb\global");
		
		$current_time=explode(" ",microtime()); $start7_time=round($current_time[0]*1000+$current_time[1]*1000);
     if (self::$TRACKER_FILE_LOG)
		 \Yii::info("=====multiple_process_main step7 saveok mainjobid=$JOBID,t7_t1=".($start7_time-$start1_time));

        TrackingAgentHelper::extCallSum("Trk.MainQQuery",$start7_time-$start1_time);
		
		return $rtn;
	}//end of queue handler processing

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
						Tracking::getSysStatus("查询中") ,Tracking::getSysStatus("过期物流号")
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
				
			if (!in_array($aTracking->status, [Tracking::getSysStatus("查询不到"),Tracking::getSysStatus("查询中") ,
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

		ConfigHelper::setConfig("Tracking/format_distribute_$forDate",json_encode($result));
		ConfigHelper::setConfig("Tracking/carrier_nation_distribute_$forDate",json_encode($carrier_nation_distribute));
		
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
		
		ConfigHelper::setConfig("Tracking/Unshipped_pie_$forDate",json_encode($result['Unshipped']));
		
		if (isset($result['status_pie']))
			ConfigHelper::setConfig("Tracking/status_pie_$forDate",json_encode($result['status_pie']));
		
		}
		
		if ($forApp=='' or $forApp=='RecommendProd'){
			//global $CAHCE;
			//step 8, 商品被展示总数，以及被代开总数，以及当天的 着落叶打开次数
			$recommend_prod_sts = array();
			$result['Recm_prod_perform'] = array();
			$recommend_prod_sts['browse_count'] = 0;//Recommend_prod_browse_count_
			$browse_count_str = ConfigHelper::getConfig("Recommend_prod_browse_count_$forDate","NO_CACHE");
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
			ConfigHelper::setConfig("Tracking/Recm_prod_perform_$forDate",json_encode($result['Recm_prod_perform']));
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
					";
			//echo "for $puid, query this $sql \n";
			$command1 = Yii::$app->subdb->createCommand($sql);
			$rows  = $command1->queryAll();
		    $i = 0;
		    
		    $insertSQL="INSERT INTO `recprod` (`id`, puid, `order_source`, `platform_sku`, `qty`, `product_name`,
		    		 `price`, `photo_primary`, `product_url`) VALUES
						 ";
			//做以下循环，in case，user的库 cs recommend prod或者prod perform为空，但其实有发信出去
			foreach ($rows as $row){
				if (    empty($row['order_source']))
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
	 * 针对某个Carrier Type，发送17Track请求，尝试获得该Tracking number的追中返回
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param  return_message         17Track返回的信息
	 * @param  puid                    用户数据库编号
	 +---------------------------------------------------------------------------------------------
	 * @return						array('success'=true,'message'='')
	 *
	 * @invoking					TrackingHelper::subqueueHandlerForTrackingByCarrier()
	 *
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		yzq		2015/3/2        		初始化
	 +---------------------------------------------------------------------------------------------
	 **/	
	static public function subqueueHandlerForTrackingByCarrier($sub_id1=''){
		//this is a CONST for the proxy server, linking 17Track
		global $CACHE;
		$JOBID = $CACHE['JOBID'];
		$rtn['message'] = "";
		$rtn['success'] = true;
		$now_str = date('Y-m-d H:i:s');
		
		//\Yii::info(['Tracking',__CLASS__,__FUNCTION__,'Background',"SJ 0 ".$JOBID],"file");
		$current_time=explode(" ",microtime());		$start1_time=round($current_time[0]*1000+$current_time[1]*1000);		
     if (self::$TRACKER_FILE_LOG)
		\Yii::info("multiple_process_sub step1 subjobid=$JOBID");
		
		//Step 0, 检查当前本job版本，如果和自己的版本不一样，就自动exit吧
		$currentSubQueueVersion = ConfigHelper::getGlobalConfig("Tracking/subQueueVersion",'NO_CACHE');
		if (empty($currentSubQueueVersion))
			$currentSubQueueVersion = 0;
		
		//如果自己还没有定义，去使用global config来初始化自己
		if (empty(self::$subQueueVersion))
			self::$subQueueVersion = $currentSubQueueVersion;
			
		//如果自己的version不同于全局要求的version，自己退出，从新起job来使用新代码
		if (self::$subQueueVersion <> $currentSubQueueVersion){
			TrackingAgentHelper::extCallSum( );
			exit("Version new $currentSubQueueVersion , this job ver ".self::$subQueueVersion." exits for using new version $currentSubQueueVersion.");
		}
		
		//step 1, try to get a pending request in queue, according to priority
			$pendingSubOne = TrackerApiSubQueue::find()
			->andWhere( ($sub_id1=='')?"sub_queue_status='P' ":" sub_id=$sub_id1" )
			->one();

		//if no pending one found, return true, message = 'n/a';
		if ($pendingSubOne == null){
			$rtn['message'] = "n/a";
			$rtn['success'] = true;
			
			$current_time=explode(" ",microtime()); $start2_time=round($current_time[0]*1000+$current_time[1]*1000);
     if (self::$TRACKER_FILE_LOG)
		\Yii::info("multiple_process_sub get-no-P sleep4 subjobid=$JOBID,t2_t1=".($start2_time-$start1_time));

			return $rtn;
		}
	//	\Yii::info(['Tracking',__CLASS__,__FUNCTION__,'Background',"SJ 1 ".$JOBID],"file");
	 $current_time=explode(" ",microtime()); $start2_time=round($current_time[0]*1000+$current_time[1]*1000);
     if (self::$TRACKER_FILE_LOG)
		 \Yii::info("multiple_process_sub step2 subjobid=$JOBID,t2_t1=".($start2_time-$start1_time));

	
		//step 2, 尝试Mark这个record由本进程来做，其他人就不要处理这条记录啦
		if ($pendingSubOne){
			$original_status = $pendingSubOne->sub_queue_status;
			$connection = Yii::$app->db;
			$command = Yii::$app->db_queue->createCommand("update tracker_api_sub_queue set sub_queue_status='S',update_time='$now_str'
							where sub_id=:sub_id and sub_queue_status in ('P','R') "  );
			// Bind the parameter
			$command->bindValue(':sub_id', $pendingSubOne->sub_id, \PDO::PARAM_STR);
			$affectRows = $command->execute();
			
			if ($affectRows == 0 and $sub_id1==''){
				$message = "进程处理同一个SubQueue请求冲突，本进程退出";
				//\Yii::info(['Tracking',__CLASS__,__FUNCTION__,'Background',$message.$JOBID],"edb\global");
				$rtn['message'] .= TranslateHelper::t($message);
				$rtn['success'] = false;

				$current_time=explode(" ",microtime()); $start3_time=round($current_time[0]*1000+$current_time[1]*1000);
     if (self::$TRACKER_FILE_LOG)
		 \Yii::info("multiple_process_sub conflict subjobid=$JOBID,t3_t2=".($start3_time-$start2_time));
		
				return $rtn;
		   }//end if updated the task status successfully
		   else{//防止YII model出错，重新Load一次
		   	
		   	   $current_time=explode(" ",microtime()); $getp_time=round($current_time[0]*1000+$current_time[1]*1000);
		   	
		   		$pendingSubOne = TrackerApiSubQueue::find()
		   						->andWhere("sub_id=".$pendingSubOne->sub_id )		   	
		   						->one();		  
		   }
		   $trackingNo = $pendingSubOne->track_no;
		   $current_time=explode(" ",microtime()); $start3_time=round($current_time[0]*1000+$current_time[1]*1000);
     if (self::$TRACKER_FILE_LOG)
		  \Yii::info("multiple_process_sub step2-1 subjobid=$JOBID,track_no=$trackingNo,t3_t2=".($start3_time-$start2_time));

		   if ($pendingSubOne->carrier_type=='888000001'){ //UBI
    		 if (self::$TRACKER_FILE_LOG)
				 \Yii::info("multiple_process_sub step2-2 subjobid=$JOBID,track_no=$trackingNo");
		   	  $query_result = TrackingAgentHelper::queryUbiInterface($pendingSubOne );
		   }elseif ($pendingSubOne->carrier_type=='888000002'){ //equick
    		 if (self::$TRACKER_FILE_LOG)
    		 	\Yii::info("multiple_process_sub step2-2 subjobid=$JOBID,track_no=$trackingNo");
    		 
		   	  $query_result = TrackingAgentHelper::queryEquickProxy($pendingSubOne );
		   }else{//others
    		 if (self::$TRACKER_FILE_LOG)
				\Yii::info("multiple_process_sub step2-3 subjobid=$JOBID,track_no=$trackingNo");
		      $query_result = TrackingAgentHelper::query17TrackProxy($pendingSubOne, $sub_id1, $original_status);

		      /*$query_result['result'] 有可能是
		       * {"success":true,"message":"","rtn":"{\"ret\":-5,\"msg\":\"unAllowIP1\",\"dat\":null}","proxyURL":"http:\/\/v4-api.17track.net:8044\/handlertrack.ashx?et=0&num=SYL21095555MY"}
		      * */
		      
		      
		      //判断是否17Track 遇到了网络错误等，如果需要重做的，那就重做一次吧
		      $parse17TrackResult = TrackingAgentHelper::parse17TrackResult( $query_result['result'],$pendingSubOne->track_no );
		    //  \Yii::info(['Tracking',__CLASS__,__FUNCTION__,'Background',$pendingSubOne->track_no ." Got result 2 :".print_r($parse17TrackResult,true)],"edb\global");
		      
		      do{
		      	 //如果17Track返回太平凡，暂时blocked 了IP，休眠一分钟才在尝试
		      	  if ($parse17TrackResult['action']=='wait and retry'){
		      	  			sleep(60);
		      	  			$parse17TrackResult['action']='retry';
		      	  }
		      
			      if (isset($parse17TrackResult['action']) and $parse17TrackResult['action']=='retry') {
			      	if (!empty($parse17TrackResult['message'])){
			      		$message = $pendingSubOne->track_no." ".$parse17TrackResult['message'].$pendingSubOne->carrier_type ;
			      		
			      		//\Yii::info(['Tracking',__CLASS__,__FUNCTION__,'Background',$message],"edb\global");
			      	}
			      	TrackingAgentHelper::extCallSum("Error.17Trk.".$pendingSubOne->carrier_type,0);
			      	$query_result1 = TrackingAgentHelper::query17TrackProxy($pendingSubOne, $sub_id1, $original_status);
			      	
			      	$parse17TrackResult1 = TrackingAgentHelper::parse17TrackResult( $query_result1['result'],$pendingSubOne->track_no );
			      	
			      	if (!isset($parse17TrackResult1['all_event']))
			      		$parse17TrackResult1['all_event']='';
			      	
			      	if (!isset($parse17TrackResult['all_event']))
			      		$parse17TrackResult['all_event']='';
	
			      	if (  strlen($parse17TrackResult1['all_event']) >= strlen($parse17TrackResult['all_event'])  
			      			or  $parse17TrackResult1['action']=='wait and retry' )
			      		$query_result = $query_result1;
			      	
			      }//end of retry logic
			      $parse17TrackResult = TrackingAgentHelper::parse17TrackResult( $query_result['result'],$pendingSubOne->track_no );
		      }while($parse17TrackResult['action']=='wait and retry');
		   }//end if UBI or other ways
	 
	//	   \Yii::info(['Tracking',__CLASS__,__FUNCTION__,'Background',$pendingSubOne->track_no ." Got result 3 :".print_r($query_result,true)],"edb\global");
		   
		   $current_time=explode(" ",microtime()); $start4_time=round($current_time[0]*1000+$current_time[1]*1000);
     if (self::$TRACKER_FILE_LOG)
		\Yii::info("multiple_process_sub step3 subjobid=$JOBID,track_no=$trackingNo,t4_t3=".($start4_time-$start3_time));
	   
		   $pendingSubOne->sub_queue_status = $query_result['sub_queue_status'];
		   $pendingSubOne->result = $query_result['result'];
		   $pendingSubOne->run_time = $query_result['run_time'];
		   //update the info of this Sub Queue task
		   $pendingSubOne->update_time = date('Y-m-d H:i:s');
			if ( $pendingSubOne->save(false) ){//save successfull
				// \Yii::info(['Tracking',__CLASS__,__FUNCTION__,'Background',"SJ 4 ".$JOBID],"file");
			   $current_time=explode(" ",microtime()); $start5_time=round($current_time[0]*1000+$current_time[1]*1000);
     if (self::$TRACKER_FILE_LOG)
		 \Yii::info("multiple_process_sub step4 saveok subjobid=$JOBID,track_no=$trackingNo,t5_t1=".($start5_time-$start1_time).",pt=".($start5_time-$getp_time));

			}else{
				$message = "ETRK015：保存SubQueue队列中API请求的执行结果，出现错误.";
				\Yii::error(['Tracking',__CLASS__,__FUNCTION__,'Background',$message],"file");
				$rtn['message'] .= TranslateHelper::t($message);
				$rtn['success'] = false;
			
				foreach ($pendingSubOne->errors as $k => $anError){
					$rtn['message'] .= ($rtn['message']==""?"":"<br>"). $k.":".$anError[0];
				}//end of each error
//				\Yii::info(['Tracking',__CLASS__,__FUNCTION__,'Background',"SJ 5 ".$JOBID],"file");
			   $current_time=explode(" ",microtime()); $start5_time=round($current_time[0]*1000+$current_time[1]*1000);
     if (self::$TRACKER_FILE_LOG)
		 \Yii::info("multiple_process_sub step4 savefalse subjobid=$JOBID,track_no=$trackingNo,t5_t1=".($start5_time-$start1_time).",pt=".($start5_time-$getp_time));

				return $rtn;
			}//end of save failed

		}//end of found one pending task in Sub Queue
		//\Yii::info(['Tracking',__CLASS__,__FUNCTION__,'Background',"SJ 6 ".$JOBID],"file");		
	    $current_time=explode(" ",microtime()); $start5_time=round($current_time[0]*1000+$current_time[1]*1000);
     if (self::$TRACKER_FILE_LOG)
		 \Yii::info("====multiple_process_sub step4 return subjobid=$JOBID,track_no=$trackingNo,t5_t1=".($start5_time-$start1_time));

		return $rtn;
	}
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 添加一个Tracking task 进入 Sub Queue，为了指定某个tracking number 使用某个 carrier type
	 * 一般由Main Queue 处理器触发。
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param  mainQueueInfo        array('track_no'=>'RG6546546CN','puid'=>1,...)
	 * @param  $carrier_type        0:国际邮政，(100001)->DHL, (100002)->UPS, (100003)->Fedex, (100004)->TNT,
	 +---------------------------------------------------------------------------------------------
	 * @return						array('success'=true,'message'='')
	 *
	 * @invoking					TrackingHelper::insertOneSubQueue();
	 *
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		yzq		2015/2/9				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function insertOneSubQueue($mainQueueInfo, $carrier_type='0',$trackingStatus='',$nationCode=''){ //ystest
		$rtn['message'] = "";
		$rtn['success'] = true;
	
		//if this task already in Sub Queue, skip it
		$aSubQueueModel = TrackerApiSubQueue::find()
			->andWhere("main_queue_id=".$mainQueueInfo['id']." and carrier_type=$carrier_type" )
			->one();
	
		if ($aSubQueueModel <> null){
			//step 1, check queue, if there is a such one processing but no respond for 5 minutes, update it to P
			$five_minutes_ago = date('Y-m-d H:i:s',strtotime('-5 minutes'));
			//check if it is with 5 minutes, if yes, do nothing, leave it processing
			if ($aSubQueueModel->update_time > $five_minutes_ago)
				return $rtn;			
		}else
		$aSubQueueModel = new TrackerApiSubQueue();
		
		$aSubQueueModel->setAttributes($mainQueueInfo);
		$aSubQueueModel->main_queue_id = $mainQueueInfo['id'];
		$aSubQueueModel->carrier_type = $carrier_type;
		$aSubQueueModel->sub_queue_status='P';
		$aSubQueueModel->create_time = date('Y-m-d H:i:s');
		//ystest starts
		$addiInfo['tracking_status'] = $trackingStatus;
		$addiInfo['nation_code'] = $nationCode;
		$aSubQueueModel->addinfo = json_encode($addiInfo);
		//ystest ends
		
		if ( $aSubQueueModel->save() ){//save successfull
				
		}else{
			foreach ($aSubQueueModel->errors as $k => $anError){
				$rtn['message'] .= ($rtn['message']==""?"":"<br>"). $k.":".$anError[0];
			}//end of each error
				
			$rtn['message'] .= TranslateHelper::t($message);
			$rtn['success'] = false;
				
			$message = "ETRK101：插入API SubQueue For 出现错误.".$rtn['message'];
			\Yii::info(['Tracking',__CLASS__,__FUNCTION__,'Background',$message],"edb\global");
	
		}//end of save failed
		return $rtn;
	}
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 把Tracker信息写入到用户Tracking信息。
	 * Tracker 队列处理器，使用subQueue并行获得多个carrier type的查询结果，决定使用某个来写入Tracking信息中
	 +---------------------------------------------------------------------------------------------
	
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param  trackingValue           array 格式的Tracking Values
	 * @param  puid                    用户数据库编号
	 +---------------------------------------------------------------------------------------------
	 * @return						array('success'=true,'message'='')
	 *
	 * @invoking					TrackingHelper::commitTrackingResultUsingValue();
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		yzq		2015/3/2				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function commitTrackingResultUsingValue($trackingValue,$puid=0){
		$rtn['message'] = "";
		$rtn['success'] = true;
		$now_str = date('Y-m-d H:i:s');
		global $CACHE;
		$JOBID=isset($CACHE['JOBID']) ? $CACHE['JOBID'] : "";
		//step 1, switch db for puid, and load the tracking
		 
		
		$track_no = $trackingValue['track_no'];
		$aTracking = Tracking::find()
				->andWhere("track_no=:trackno",array(':trackno'=>$track_no) )
				->one();
		
		if (empty($aTracking)){
			//		异常情况
			$rtn['message']="ETRK0012：这个物流号已经不存在了，无法update其物流信息".$track_no;
			$rtn['success'] = false;
			return $rtn;
		}
		
		$orig_data = $aTracking->getAttributes();
		
		//step 2, define which fields are to be updated.
		$updateFields = array('parcel_type'=>1,'status'=>1,'carrier_type'=>1,'from_nation'=>1,'to_nation'=>1,
						'all_event'=>1,'total_days'=>1,'first_event_date'=>1,'from_lang'=>1,'to_lang'=>1,'last_event_date'=>1);
		$populateFieldandValues = array();
		foreach ($trackingValue as $k=>$v){
			if (isset($updateFields[$k]))
				$populateFieldandValues[$k] = $v;
		}
		
		//if can not load the tracking, error
		if ($aTracking){
			$aTracking->update_time = $now_str;
			$aTracking->setAttributes($populateFieldandValues);
		}else{
			$message = "ETRK012: 17Track返回结果是后找不到这个puid里面的track no: $track_no";
			\Yii::info(['Tracking',__CLASS__,__FUNCTION__,'Background',$message],"edb\global");
			$rtn['message'] = $message;
			$rtn['success'] = false;
			return $rtn;
		}
		
		//step 4, set state according to status and created time, long ago

		//special handling for some cases
		//A. 如果Tracking 创建时间5天还没有 查询得到信息，判断为交运不成功
		$five_days_ago = date('Y-m-d H:i:s',strtotime('-5 days'));
		$fifteen_days_ago = date('Y-m-d H:i:s',strtotime('-15 days'));
		if ($aTracking->status == Tracking::getSysStatus("查询不到")
			and $aTracking->create_time < date('Y-m-d H:i:s',strtotime('-10 days'))){
			$aTracking->status = Tracking::getSysStatus("无法交运");
		}
		//B. 如果运输超过了15天了，还没有到，判断为 运输过久
		elseif ($aTracking->status == Tracking::getSysStatus("运输途中")
				and $aTracking->create_time < $fifteen_days_ago ){
				//$aTracking->status = Tracking::getSysStatus("ship_over_time");			 
		}
		
		//set default state by status
		$aTracking->state = Tracking::getParcelStateByStatus($aTracking->status);
		
		//如果本来就有结果，可是这次没有结果，就是17Track 返回有问题，不要把有问题的结果提交
		$canCommit=true;
		$aTracking->getAttributes();
		
		if ($aTracking->status <> Tracking::getSysStatus("买家已确认") and 
			$aTracking->state <> Tracking::getSysState("已完成") and 
			empty($populateFieldandValues['first_event_date'] ) and !empty($orig_data['first_event_date']) )
			$canCommit=false;
		
		if ( $canCommit ){//save successfull
			$populateFieldandValues['update_time'] = $aTracking->update_time;
			$populateFieldandValues['status'] = $aTracking->status;
			$populateFieldandValues['state'] = $aTracking->state;
			
			//如果有last event date 并且还没有完成的，就计算器停留时间天数
			if (!empty($populateFieldandValues['last_event_date']) and 
			       ( $aTracking->state ==Tracking::getSysState("正常")  or 
			       	 $aTracking->state ==Tracking::getSysState("异常")  ) 
			   )  {
				$datetime1 = strtotime (date('Y-m-d H:i:s'));
				$datetime2 = strtotime (substr($populateFieldandValues['last_event_date'], 0,10)." 00:00:00");
				$days =ceil(($datetime1-$datetime2)/86400); //60s*60min*24h
				$populateFieldandValues['stay_days'] =  $days;
			}
			
			//如果是已完成的state，停留时间为 0 天就可以
			if ($aTracking->state ==Tracking::getSysState("已完成"))
				$populateFieldandValues['stay_days'] =  0;
			
			$set_str='';
			
			foreach ($populateFieldandValues as $key =>$val){
				$set_str .= ($set_str==''?"":",");
				$set_str .= "$key='$val'";
			}
			
			$trackingNo = $track_no;
			$current_time=explode(" ",microtime()); $start2_time=round($current_time[0]*1000+$current_time[1]*1000);
		    
			$command = Yii::$app->subdb->createCommand("update lt_tracking set $set_str where track_no= '$track_no'");
			$command->execute();
			$current_time=explode(" ",microtime()); $start3_time=round($current_time[0]*1000+$current_time[1]*1000);
			
     if (self::$TRACKER_FILE_LOG)
		\Yii::info("multiple_process_main update_lt_tracking subjobid=$JOBID,track_no=$trackingNo,t3_t2=".($start3_time-$start2_time));

			//因为有新的tracking commit了，原来的统计cache就dirty了，设置为空，下次有人登陆会重新计算
			ConfigHelper::setConfig("Tracking/left_menu_statistics", json_encode(array()));
			ConfigHelper::setConfig("Tracking/top_menu_statistics", json_encode(array()));

			$rtn['message'] = $aTracking->status;
			$rtn['track_no'] = $aTracking->track_no;
		} else{//就算canNot commit，也要update一下update time，否则会自动蛢命未他 刷新的
			$command = Yii::$app->subdb->createCommand("update lt_tracking set update_time='$now_str' where track_no= '$track_no'");
			$command->execute();
			
		}
	
		return $rtn;
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
			$aTracking['track_no'] = $anOrder['tracking_no'];
			$aTracking['order_id'] = $anOrder['order_id'];
			$aTracking['seller_id'] = $anOrder['selleruserid'];
			$aTracking['platform'] = strtolower( $anOrder['order_source'] );	
			$aTracking['ship_by'] = $anOrder['carrier'];
			$aTracking['ship_out_date'] = date('Y-m-d H:i:s',$anOrder['paid_time']); 
			$addi_info['consignee_country_code'] = $anOrder['consignee_country_code'];
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
			ConfigHelper::setConfig("Tracking/last_retrieve_shipped_order_time",$now_str);
			$rtn['count'] = $insertedCount;
		}else
			$rtn['count'] = 0;
		
		//force update the top menu statistics
		ConfigHelper::setConfig("Tracking/left_menu_statistics", json_encode(array()));			
		ConfigHelper::setConfig("Tracking/top_menu_statistics", json_encode(array()));

		//check all distinct delivery companies  , write it to Config
		$carriers = array(); 
		$command= Yii::$app->get('subdb')->createCommand(
					"select distinct ship_by from lt_tracking order by ship_by ");
		
		$rows = $command->queryAll();
		foreach ($rows as $row){
			if (empty($row['ship_by'])) continue;
			$carriers[] = $row['ship_by'];
		}
		 
		$carriers = json_encode($carriers);
		$old_carriers = ConfigHelper::getConfig("Tracking/using_carriers","NO_CACHE" );
		if ($old_carriers <> $carriers)
			ConfigHelper::setConfig("Tracking/using_carriers", $carriers);

		//check all distinct delivery target nations  , write it to Config
		$carriers = array();
		$command= Yii::$app->get('subdb')->createCommand(
				"select distinct to_nation from lt_tracking where to_nation<>'' and to_nation <>'--' order by to_nation ");
		
		$rows = $command->queryAll();
		foreach ($rows as $row){
			if (empty($row['to_nation'])) continue;
			$carriers[] = $row['to_nation'];
		}
			
		$carriers = json_encode($carriers);
		$old_carriers = ConfigHelper::getConfig("Tracking/to_nations","NO_CACHE" );
		if ($old_carriers <> $carriers)
			ConfigHelper::setConfig("Tracking/to_nations", $carriers);
		
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
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		yzq		2015/2/9				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function getOrderDetailFromOMSByTrackNo($track_no){
		$detail = OrderTrackerApiHelper::getOrderDetailByTrackNo($track_no);
		$rtn = ['success'=>true , 'message'=>'' , 'order'=>$detail , 'url'=>''];
		return $rtn;
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
		$ShipByResult = Yii::$app->get('subdb')->createCommand("select distinct ship_by from lt_tracking where ifnull(ship_by,'')<> ''")->queryAll();
		foreach($ShipByResult as $tmprow){
			$ShipByList[] =  $tmprow['ship_by'];
			 
		}
		
		
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
	static public function generateTrackingEventHTML($TrackingList,$langList=[]){
		
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
		
		$all_events_str[$track_no] = "<dl>".$all_events_str[$track_no].'</dl>';
		
		
		// 设置 第一行 : 发件国 , 收件国
		$all_events_str[$track_no] = 
				'<div class="toNation">'.
				'<h6> <span class="glyphicon glyphicon-gift" aria-hidden="true" style="margin-right: 12px;font-size: 14px;"></span>'.
				'<span class="text-muted">'.TranslateHelper::t('收件国家').
				'</span>'.
				': '.$to_nation.$toOfficialLinkHtml.$platFormTitle.'</h6>'.
				'</div>' . $all_events_str[$track_no];
		
		// 设置 最后一行 : 发件国 
		$all_events_str[$track_no] .= 
				'<div class="fromNation">'.
					'<h6><span class="glyphicon glyphicon-send" aria-hidden="true" style="margin-right: 12px;font-size: 14px;"></span>'.
					'<span class="text-muted">'.TranslateHelper::t('发件国家').
					'</span>'.
					': '.$from_nation.$fromOfficialLinkHtml.'</h6>
				</div>';

		
		
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
	
		 
		$all_events_str[$track_no] .= '<div class="col-md-12"><span class="text-muted" style="float:left;padding-top:3px">'.TranslateHelper::t('翻译成中文').'</span><input class="translateBtn_'.$model->id.'" type="checkbox" value="1" onchange="ListTracking.translateEventsToZh(this,\''.$track_no.'\','.$model->id.')"></div>';
	
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
				
				if (isset(CarrierTypeOfTrackNumber::$expressCode[$row['carrier_type']]) && ! in_array(strtolower($row['status']) , ['checking',"查询中"])  )
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
		if (isset($oneTracking['carrier_type']) && ! in_array(strtolower($oneTracking['status']) , ['checking',"查询中"]) ){
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
		
		$HtmlStr .="<input type='checkbox' name='chk_tracking_record' value =".base64_encode($oneTracking['track_no']).">";
	
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
					"<td class='font-color-2'>".$oneTracking['order_id']."</td>";
					
					
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
					
					//oms  显示 ship out date 为准 
					if (in_array($oneTracking['source'], ['O'])){
						$HtmlStr .="<td class='font-color-2' nowrap>". $oneTracking['create_time']."</td>";//ship_out_date
					}else{
						//手工录入 显示 ship out date 为准
						$HtmlStr .="<td class='font-color-2' nowrap>". $oneTracking['create_time']."</td>";
					}
					
					$HtmlStr .= "<td class='font-color-2'>". substr($oneTracking['update_time'], 0 , 16)."</td>";
					$HtmlStr .= "<td>".(empty($oneTracking['ship_by'])? '':"<span class='font-color-2'>". $oneTracking['ship_by']."</span><br>").$CarrierTypeStr."</td>";
					$HtmlStr .= "<td nowrap data-status='".$oneTracking['status']."'><strong ".(empty($status_qtip_mapping[$oneTracking['status']])?"":" qtipkey='".$status_qtip_mapping[$oneTracking['status']]."'")." class='no-qtip-icon font-color-1' >". $oneTracking['status']."</strong><p class='font-color-2'><small>(".$total_days.TranslateHelper::t('天').")</small></p></td>";
					
					//2015-07-10 liang start 
					$stay_days='-';
					if(is_numeric($oneTracking['stay_days']) && $oneTracking['stay_days']>0) $stay_days=$oneTracking['stay_days'].TranslateHelper::t("天");
					$HtmlStr .="<td class='font-color-2' nowrap>".$stay_days."</td>";//ship_out_date	
					//2015-07-10 liang end
					
					//$HtmlStr .= "<td nowrap><span class='". $status_class."' ".(empty($status_qtip_mapping[$oneTracking['status']])?"":" qtipkey='".$status_qtip_mapping[$oneTracking['status']]."'")." >". $oneTracking['status']."</span><br><p style=\"margin: 5px 0 0px 25px;\"><small>".$markHandleStr.$total_days.TranslateHelper::t('天')."</small></p></td>";
					
					$HtmlStr .= "<td>";
						//立即更新   按键
						if (! in_array(strtolower($oneTracking['status']), [TranslateHelper::t("查询中") , TranslateHelper::t("已完成")]) ):
						$HtmlStr .=" <a onclick=\"ListTracking.UpdateTrackRequest('". $oneTracking['track_no'] ."',this)\"  title='".TranslateHelper::t('立即更新')."'>".'<span class="egicon-refresh"></span>'."</a>";
						endif;
					
						// 详情  按键
						//if (! in_array(strtolower($oneTracking['status']), [TranslateHelper::t("查询中") , TranslateHelper::t("查询不到")]) ):
						
						//khcomment20150610 $HtmlStr .=" <a onclick=\"ListTracking.ShowDetailView(this)\" title='".TranslateHelper::t('详情')."'>".'<span class="glyphicon glyphicon-eye-open" aria-hidden="true"></span>'."</a>";
						$HtmlStr .=" <a title='".TranslateHelper::t('详情')."' class='btn-qty-memo' data-track-id='".$oneTracking['id']."'>".'<span class="egicon-eye"></span>'."</a>";
						//endif;
						
						if ( !empty($oneTracking['seller_id']) && !empty($oneTracking['order_id'])):
						//订单详情   按键
						$HtmlStr .=" <a onclick=\"ListTracking.ShowOrderInfo('". $oneTracking['track_no'] ."')\" title='".TranslateHelper::t('订单详情')."' >".'<span class="egicon-notepad" aria-hidden="true"></span>'."</a>";
						endif;
						//添加备注 按键
						$HtmlStr .=" <a onclick=\"ListTracking.showRemarkBox('". $oneTracking['track_no'] ."')\" title='".TranslateHelper::t('添加备注')."'>".'<span class="egicon-memo-blue" aria-hidden="true"></span>'."</a>";
						//增加标签 按键 (旧版)
						//$HtmlStr .=" <a onclick=\"ListTracking.showTagBox('". $oneTracking['id'] ."','". $oneTracking['track_no'] ."')\" title='".TranslateHelper::t('添加标签')."'>".'<span class="glyphicon glyphicon-tags" aria-hidden="true"></span>'."</a>";
						if ( !empty($oneTracking['seller_id']) && !empty($oneTracking['order_id']) && in_array($oneTracking['platform'], ['ebay','aliexpress'])):
						
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
		
		$track_no_list = [];
		$row_no = 0;
		$colMapFields=[];
		$TrackingData2 = [];
		//检查excel 导入的物流信息  是否重复		
		foreach($TrackingData as $oneTracking1):
		$row_no ++;
		//对第一行，当成是 column header的处理，判断这个col到底是什么内容
		if ($row_no == 1){
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
		
		//\Yii::info(['Tracking',__CLASS__,__FUNCTION__,'Online',"Try to import track no ".print_r($oneTracking,true)  ],"edb\global");
		if (! in_array($oneTracking['track_no'], $track_no_list)){
			$track_no_list[] = $oneTracking['track_no'];
		}else{
			$result['success'] = false;
			$result['message'] = TranslateHelper::t($oneTracking['track_no'] ." 重复录入! ".$oneTracking['track_no']." 请删除其中一个相同的物流号.");
			return $result;
		}
		$TrackingData2[] = $oneTracking;
		endforeach;
		
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
		$result['message'] = TranslateHelper::t('导入'.count($TrackingData).'条物流记录成功，系统正在追踪中。');
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
	
	static public function getCandidateCarrierType($track_no){
		$results =  CarrierTypeOfTrackNumber::checkExpressOftrackingNo($track_no);
		$carrier_types = array();
		foreach ($results as $carrier=>$carrerName){
			$carrier_types["".$carrier.""] = $carrier;
		}
		
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
		$track_statistics_str = ConfigHelper::getConfig("Tracking/left_menu_statistics",'NO_CACHE');
		$track_statistics = json_decode($track_statistics_str,true);
		if (empty($track_statistics)) $track_statistics = array();
		
		if (!isset($track_statistics['all'])){
		
			$menuLabelList = [
				'normal_parcel'=>0 , 
					'shipping_parcel'=>0 ,
					'no_info_parcel'=>0 ,
					'suspend_parcel'=>0 ,
				'exception_parcel'=>0 ,
					'ship_over_time_parcel'=>0 ,
					'rejected_parcel'=>0 ,
					'arrived_pending_fetch_parcel'=>0 ,
					'unshipped_parcel'=>0 ,
				'all'=>0 ,
				'received_message'=>0,
				'arrived_pending_message'=>0,
				'rejected_message'=>0,
				'shipping_message'=>0,
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
				'unshipped_parcel'=>['not',['mark_handled'=>'Y']] ,
				'all'=>['not',['mark_handled'=>'Y']] ,
				'received_message'=>['status'=>'rejected' , 'received_notified'=>'N' ,'source'=>'O'],
				'arrived_pending_message'=>['status'=>'arrived_pending_fetch', 'pending_fetch_notified'=>'N','source'=>'O'],
				'rejected_message'=>['status'=>'rejected', 'rejected_notified'=>'N', 'source'=>'O'],
				'shipping_message'=>['status'=>'shipping', 'shipping_notified'=>'N', 'source'=>'O'],
			];
			
			//统计的时候，只需要统计 不是 complete state，并且不是 marked handled 的记录就可以
			foreach($menuLabelList as $menu_type=>&$value){
				if (! empty ($menuCondition[$menu_type])){
					
					if (stripos($menu_type, 'message')){
						$sevenDayAgoSql  = " and `ship_out_date` >= '$startdate'";
					}else{
						$sevenDayAgoSql = "";
					}
						
						
					$TrackingQuery = Tracking::find();
										
					$value = $TrackingQuery
								->andWhere($menuCondition[$menu_type])
								->andWhere(Tracking::getTrackingConditionByClassification($menu_type))
								->andWhere("state<>'complete' $sevenDayAgoSql")//and mark_handled <> 'Y'
								->andWhere("state<>'deleted' ")
								->count();
					
					/* 调试sql  
					// 过滤删除的数据
					 $tmpCommand = $TrackingQuery->andWhere($menuCondition[$menu_type])
								->andWhere(Tracking::getTrackingConditionByClassification($menu_type))
								->andWhere("state<>'complete' and mark_handled <> 'Y'")
								->andWhere("state<>'deleted' ")
					 ->createCommand();
					echo "<br><br> $menu_type : ".$tmpCommand->getRawSql();
					
					*/
					
				}
			}
		
			$track_statistics = $menuLabelList;
			
			$track_statistics['completed_parcel'] = Tracking::find()->andWhere(Tracking::getTrackingConditionByClassification('completed_parcel'))->count();
			ConfigHelper::setConfig("Tracking/left_menu_statistics", json_encode($track_statistics));
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
			ConfigHelper::setConfig("Tracking/left_menu_statistics", json_encode(array()));
			ConfigHelper::setConfig("Tracking/top_menu_statistics", json_encode(array()));

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
			if (self::$putIntoTrackQueueVersion <> $currentMainQueueVersion)
				exit("Version new $currentMainQueueVersion , this job ver ".self::$putIntoTrackQueueVersion." exits for using new version $currentMainQueueVersion.");

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

			//\Yii::info(['Tracking',__CLASS__,__FUNCTION__,'Background',"SQL to be execute:".$sql.$sql_values ],"edb\global");
			if ($sql_values <> ''){
				$command = Yii::$app->db_queue->createCommand($sql.$sql_values.";");
				$command->execute();
			}
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
			$sql = " INSERT INTO  `lt_tracking`
					( `order_id`, seller_id, `track_no`,`status`,state,source,platform,
					batch_no,create_time,update_time,ship_by,delivery_fee,
					ship_out_date,addi_info) VALUES ";

			$sql_values = '';
			$Trackings = Tracking::$Insert_Data_Buffer;
			Tracking::$Insert_Data_Buffer = array();
			foreach ($Trackings as $data){
				
				foreach ($data as $key=>$val){
					$data[$key] = self::removeYinHao($val);
				}
				
				if (empty($data['update_time']))
					$data['update_time'] = $data['create_time'];
				
				if (empty($data['delivery_fee']))
					$data['delivery_fee'] = 0.00;
				
				$sql_values .= ($sql_values==''?'':","). 
				"('".$data['order_id']."','".$data['seller_id']."','".$data['track_no']."','".$data['status']."','".$data['state']."'
					,'".$data['source']."','".$data['platform']."','".$data['batch_no']."'
					,'".$data['create_time']."','".$data['update_time']."','".$data['ship_by']."'
					,'".$data['delivery_fee']."','".$data['ship_out_date']."','".$data['addi_info']."'	
				)";
				
				if (strlen($sql_values) > 3000){
					//one sql syntax do not exceed 4800, so make 3000 as a cut here
					//\Yii::info(['Tracking',__CLASS__,__FUNCTION__,'Background',"SQL to be execute:".$sql.$sql_values ],"edb\global");
					$command = Yii::$app->subdb->createCommand($sql.$sql_values .";");
					$command->execute();
					$sql_values = '';
				}
			}//end of each track no
		
			//\Yii::info(['Tracking',__CLASS__,__FUNCTION__,'Background',"SQL to be execute:".$sql.$sql_values ],"edb\global");
			if ($sql_values <> ''){
				$command = Yii::$app->subdb->createCommand($sql.$sql_values.";");
				$command->execute();
			}
			
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
			$sql = " INSERT INTO  `tracker_api_queue`".
					"( `priority`, `puid`,`track_no`,status,candidate_carriers,".
					"selected_carrier, create_time,update_time,addinfo ) VALUES ";
		
			$sql_values = '';
			$TrackingQueueReqs = self::$Insert_Api_Queue_Buffer;
			self::$Insert_Api_Queue_Buffer = array();
			foreach ($TrackingQueueReqs as $data){
				
				foreach ($data as $key=>$val){
					$data[$key] = self::removeYinHao($val);
				}
				
				if (empty($data['update_time']))
					$data['update_time'] = $data['create_time'];
		
				$sql_values .= ($sql_values==''?'':",").
				"('".$data['priority']."','".$data['puid']."','".$data['track_no']."','".$data['status']."'".
					",'".$data['candidate_carriers']."','".$data['selected_carrier']."','".$data['create_time']."'".
					",'".$data['update_time']."','".$data['addinfo']."'".
				")";
		
				if (strlen($sql_values) > 3000){
					//one sql syntax do not exceed 4800, so make 3000 as a cut here
				//	\Yii::info(['Tracking',__CLASS__,__FUNCTION__,'Background',"SQL to be execute:".$sql.$sql_values ],"edb\global");
					$command = Yii::$app->db_queue->createCommand($sql.$sql_values .";");
					$command->execute();
					$sql_values = '';
				}
			}//end of each track no

			if ($sql_values <> ''){
			//	\Yii::info(['Tracking',__CLASS__,__FUNCTION__,'Background',"SQL to be execute:".$sql.$sql_values ],"edb\global");
				$command = Yii::$app->db_queue->createCommand($sql.$sql_values.";");
				$command->execute();
			}
				
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
		$uid = \Yii::$app->user->id;
		$uid = \Yii::$app->subdb->getCurrentPuid(); //ystest
		$result = [];
		if (in_array(strtolower($platform),['ebay','all'])){
			$ebayUserList  = EbayAccountsApiHelper::helpList('expiration_time' , 'asc');
			foreach($ebayUserList as $row){
				$account['id'] = $row['ebay_uid'];
				$account['name'] = $row['selleruserid'];
				$account['platform'] = 'ebay'; 
				$result[] = $account;
			}
		}
		
		if (in_array(strtolower($platform),['aliexpress','all'])){
			$AliexpressUserList = SaasAliexpressUser::find()->where('uid ='.$uid)
			->orderBy('refresh_token_timeout desc')
			->asArray()
			->all();
			
			foreach($AliexpressUserList as $row){
				$account['id'] = $row['aliexpress_uid'];
				$account['name'] = $row['sellerloginid'];
				$account['platform'] = 'aliexpress';
				$result[] = $account;
			}
		}
		
		if (in_array(strtolower($platform),['dhgate','all'])){
			$DhgateUserList = SaasDhgateUser::find()->where('uid ='.$uid)
			->orderBy('refresh_token_timeout desc')
			->asArray()
			->all();
				
			foreach($DhgateUserList as $row){
				$account['id'] = $row['dhgate_uid'];
				$account['name'] = $row['sellerloginid'];
				$account['platform'] = 'dhgate';
				$result[] = $account;
			}
		}
		
		if (in_array(strtolower($platform),['aliexpress','all'])){
			$wishUserList = SaasWishUser::find()->where(['uid'=>$uid , 'is_active'=>'1'])->asArray()->all();
			
			foreach($wishUserList as $row){
				$account['id'] = $row['site_id'];
				$account['name'] = $row['store_name'];
				$account['platform'] = 'wish';
				$result[] = $account;
			}
		}
		
			if (in_array(strtolower($platform),['lazada','all'])){
			$lazdaaUserList = SaasLazadaUser::find()->where(['puid'=>$uid , 'status'=>'1'])->asArray()->all();
			
			foreach($lazdaaUserList as $row){
				$account['id'] = $row['lazada_uid'];
				$account['name'] = $row['platform_userid'];
				$account['platform'] = 'lazada';
				$result[] = $account;
			}
		}
		
		if (in_array(strtolower($platform),['cdiscount','all'])){
			$lazdaaUserList = SaasCdiscountUser::find()->where(['uid'=>$uid , 'is_active'=>'1'])->asArray()->all();
				
			foreach($lazdaaUserList as $row){
				$account['id'] = $row['site_id'];
				$account['name'] = $row['username'];
				$account['platform'] = 'cdiscount';
				$result[] = $account;
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
			$result = ConfigHelper::getConfig($path );
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
					->andWhere([ 'platform'=>['ebay','aliexpress']])
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
	static public function setMessageConfig($TrackNoList , $LayOutId =1 , $ReComProdCount=8){
		$query = Tracking::find();
		$result = $query->andWhere(['track_no'=>$TrackNoList])->all();
		foreach($result as $row){
			//防止旧数据丢失
			$row['addi_info'] = str_ireplace('`', '"', $row['addi_info']);
			$addi_info = json_decode($row['addi_info'],true);
			if (isset($addi_info['layout_id'])) unset($addi_info['layout_id']);
			$addi_info['layout'] = $LayOutId;
			//$addi_info['recommend_product_count'] = $ReComProdCount;
			$addi_info['recom_prod_count'] = $ReComProdCount;
			
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
		
		//最近90天内新建物流的条件
		$RecentDate = date('Y-m-d',strtotime('-90 day'));;
		$RecentDateCondition = ['>=','create_time', $RecentDate];
		$UnshipParcelList = Tracking::find()
		->select(['track_no'])
		->andWhere($params)
		->andWhere($RecentDateCondition)
		->asArray()
		->all();
		
		$track_nos = [];
		//格式 化结果
		foreach($UnshipParcelList as $row){
			$track_nos[] = $row['track_no'];
			$row = []; //release memory
		}
		self::putIntoTrackQueueBuffer($track_nos , 'B');
		
	}//end of batchUpdateUnshipParcel
}
