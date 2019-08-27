<?php

namespace eagle\modules\order\controllers;

use yii\data\Pagination;
use eagle\modules\order\models\OdOrder;
use eagle\modules\order\models\Excelmodel;

use common\helpers\Helper_Array;
use eagle\modules\order\models\OdOrderItem;
use eagle\modules\util\helpers\OperationLogHelper;
use eagle\modules\order\models\Usertab;
use Exception;
use eagle\models\carrier\SysShippingService;
use yii\data\Sort;

use eagle\modules\inventory\helpers\InventoryApiHelper;
use eagle\modules\order\helpers\OrderTagHelper;
use eagle\modules\order\helpers\OrderHelper;
use eagle\modules\app\apihelpers\AppTrackerApiHelper;
use eagle\models\SaasLazadaUser;
use eagle\modules\lazada\apihelpers\LazadaApiHelper;
use eagle\modules\carrier\apihelpers\CarrierApiHelper;
use eagle\modules\carrier\helpers\CarrierOpenHelper;
use eagle\models\SysCountry;
use eagle\modules\order\helpers\OrderBackgroundHelper;
use eagle\modules\inventory\helpers\WarehouseHelper;
use eagle\modules\order\helpers\JumiaOrderHelper;
use eagle\modules\order\apihelpers\OrderApiHelper;
use eagle\modules\order\helpers\OrderUpdateHelper;
use eagle\modules\order\helpers\OrderGetDataHelper;
use eagle\modules\util\helpers\ConfigHelper;
use eagle\modules\platform\apihelpers\PlatformAccountApi;
use eagle\modules\util\helpers\RedisHelper;

class JumiaOrderController extends \eagle\components\Controller{
	public $enableCsrfValidation = false;
	
	/**
	 * Jumia订单列表页面
	 */
	public function actionList(){
		//check模块权限
		$permission = \eagle\modules\permission\apihelpers\UserApiHelper::checkPlatformPermission('jumia');
		if(!$permission)
			return $this->render('//errorview_no_close',['title'=>'访问受限:没有权限','error'=>'您还没有订单模块的权限!']);
		
		AppTrackerApiHelper::actionLog("Oms-jumia", "/order/jumia/list");
		
		//默认打开的列表记录数为上次用户选择的page size 数	//lzhl	2016-11-30
		$page_url = '/'.\Yii::$app->controller->module->id.'/'.\Yii::$app->controller->id.'/'.\Yii::$app->controller->action->id;
		$last_page_size = ConfigHelper::getPageLastOpenedSize($page_url);
		if(empty($last_page_size))
			$last_page_size = 20;//默认显示值
		if(empty($_REQUEST['per-page']) && empty($_REQUEST['page']))
			$pageSize = $last_page_size;
		else{
			$pageSize = empty($_REQUEST['per-page'])?50:$_REQUEST['per-page'];
		}
		ConfigHelper::setPageLastOpenedSize($page_url, $pageSize);
		
		$data=OdOrder::find();
		$data->andWhere(['order_source'=>'jumia']);
		
		$puid = \Yii::$app->user->identity->getParentUid();
		$isParent = \eagle\modules\permission\apihelpers\UserApiHelper::isMainAccount();
		$tmpSellerIDList = PlatformAccountApi::getPlatformAuthorizeAccounts('jumia');//添加账号权限	lzhl 2017-03
		$accountList = [];
		$selleruserids = [];
		foreach($tmpSellerIDList as $sellerloginid=>$store_name){
			$accountList[] = $sellerloginid;
			$selleruserids[$sellerloginid] = $store_name;
		}
		//如果为测试用的账号就不受平台绑定限制
		$test_userid=\eagle\modules\tool\helpers\MirroringHelper::$test_userid;
		if(empty($accountList)){
			if (!in_array($puid,$test_userid['yifeng'])){
				//无有效权限账号时
				return $this->render('//errorview_no_close',['title'=>'访问受限:没有权限','error'=>'您还没有获得任何 Jumia 账号管理权限!']);
			}
		}
			
		//不显示 解绑的账号的订单
		if (!in_array($puid,$test_userid['yifeng']))
			$data->andWhere(['selleruserid'=>$accountList]);
		
		$showsearch=0;
		$op_code = '';
		
		//不显示 解绑的账号的订单 start
		//testkh start
		if (isset(\Yii::$app->params["currentEnv"]) and \Yii::$app->params["currentEnv"]=="test" ){
			$aliAccountList = [];//testkh
		}
		//testkh end
// 		if (!empty($aliAccountList)){
// 			//不显示 解绑的账号的订单
// // 			$data->andWhere(['selleruserid'=>$aliAccountList]);
// 		}
		
		//不显示 解绑的账号的订单 end
		
// 		if (!empty($_REQUEST['order_status'])){
// 			//搜索订单状态
// 			$data->andWhere('order_status = :os',[':os'=>$_REQUEST['order_status']]);
// 			//生成操作下拉菜单的code
// 			$op_code = $_REQUEST['order_status'];
// 		}
// 		if (isset($_REQUEST['exception_status'])){
// 			//搜索异常状态
// 			if ($_REQUEST['exception_status'] == '0'){
// 				//已付款订单处理 , 默认为待检测
// 				$data->andWhere('exception_status = :es',[':es'=>$_REQUEST['exception_status']]);
// 				$data->andWhere('order_status = :os',[':os'=>OdOrder::STATUS_PAY]);
// 				//生成操作下拉菜单的code
// 				$op_code = OdOrder::STATUS_PAY;
// 			}elseif(!empty($_REQUEST['exception_status'])){
// 				//非默认状态
// 				$data->andWhere('exception_status = :es',[':es'=>$_REQUEST['exception_status']]);
// 				//生成操作下拉菜单的code
// 				$op_code = $_REQUEST['exception_status'];
// 			}
				
// 		}
// 		if (!empty($_REQUEST['cangku'])){
// 			//搜索仓库
// 			$data->andWhere('default_warehouse_id = :dwi',[':dwi'=>$_REQUEST['cangku']]);
// 			$showsearch=1;
// 		}
// 		if (!empty($_REQUEST['order_capture'])){
// 			//手工订单查询
// 			$data->andWhere('order_capture = :order_capture',[':order_capture'=>$_REQUEST['order_capture']]);
// 			$showsearch=1;
// 		}
// 		if (!empty($_REQUEST['shipmethod'])){
// 			//搜索运输服务
// 			$data->andWhere('default_shipping_method_code = :dsmc',[':dsmc'=>$_REQUEST['shipmethod']]);
// 			$showsearch=1;
// 		}
		if (!empty($_REQUEST['order_source_status'])){
				
			//Aliexpress状态
			if ($_REQUEST['order_source_status'] == 'CUSTOM_WAIT_SEND_MOENY'){
				//部分发货 , 等待买家收货 , 等待您确认金额
				$data->andWhere(['order_source_status'=>['SELLER_PART_SEND_GOODS' , 'WAIT_BUYER_ACCEPT_GOODS','WAIT_SELLER_EXAMINE_MONEY']]);
			}else{
				$data->andWhere('order_source_status = :order_source_status',[':order_source_status'=>$_REQUEST['order_source_status']]);
			}
				
			$showsearch=1;
		}
		
		/* 订单系统标签 查询*/
// 		$sysTagList = [];
// 		foreach(OrderTagHelper::$OrderSysTagMapping as $tag_code=>$label){
// 			//1.勾选了系统标签；
// 			if (!empty($_REQUEST[$tag_code]) ){
// 				//生成 tag 标签的数组
// 				$sysTagList[] = $tag_code;
// 			}
// 			if (isset($_REQUEST[$tag_code])){
// 				$showsearch=1;
// 			}
// 		}
// 		if  (!empty($sysTagList)){
// 			$showsearch=1;
				
// 			if (! empty($_REQUEST['is_reverse'])){
// 				//取反操作
// 				$reverseStr = "not ";
// 			}else{
// 				$reverseStr = "";
// 			}
				
// 			$data->andWhere([$reverseStr.'in', 'order_id', (new \yii\db\Query())->select('order_id')->from('od_order_systags_mapping')->where(['tag_code' => $sysTagList])]);
// 		}
		
		
// 		if (!empty($_REQUEST['sel_tag'])){
// 			//搜索卖家账号
// 			if  (!empty($_REQUEST['sel_tag'])){
// 				if (is_string($_REQUEST['sel_tag'])){
// 					$customTagList = explode(",", $_REQUEST['sel_tag']);
// 				}elseif(is_array($_REQUEST['sel_tag'])){
// 					$customTagList = $_REQUEST['sel_tag'];
// 				}else{
// 					$customTagList = [];
// 				}
// 				if (!empty($customTagList)){
// 					foreach($customTagList as  $row){
// 						$data->andWhere('customized_tag_'.$row.' ="Y" ');
// 					}
// 				}
				
// 				//$query->andWhere('order_id in (select order_id from lt_order_tags where tag_id in ('.implode(",", $other_params['custom_tag']).')) ');
// 			}
// 		    //$data->andWhere('order_id in (select order_id from lt_order_tags where tag_id in (:sel_tag)) ',[':sel_tag'=>implode(',', $_REQUEST['sel_tag'])]);
// 			$showsearch=1;
// 		}
// 		if (!empty($_REQUEST['order_evaluation'])){
// 			//评价
// 			$data->andWhere('order_evaluation = :order_evaluation',[':order_evaluation'=>$_REQUEST['order_evaluation']]);
// 			$showsearch=1;
// 		}
		
// 		if (!empty($_REQUEST['reorder_type'])){
// 			if ($_REQUEST['reorder_type'] != 'all'){
// 				//重新发货类型
// 				$data->andWhere('reorder_type =:reorder_type ',[':reorder_type'=>$_REQUEST['reorder_type']]);
// 			}else{
// 				$data->andWhere(['not', ['reorder_type' => null]]);
// 				//生成操作下拉菜单的code
// 				$op_code = 'reo';
// 			}
				
// 			$showsearch=1;
// 		}
		
// 		if (!empty($_REQUEST['fuhe'])){
// 			$showsearch=1;
// 			//搜索符合条件
// 			switch ($_REQUEST['fuhe']){
// 				case 'is_comment_status':
// 					$data->andWhere('is_comment_status = 0');
// 					break;
// 				default:break;
// 			}
// 		}
// 		if (!empty($_REQUEST['searchval'])){
// 			//搜索用户自选搜索条件
// 			if (in_array($_REQUEST['keys'], ['order_id','order_source_order_id','buyeid','consignee','email'])){
// 				$kv=[
// 				'order_id'=>'order_id',
// 				'order_source_order_id'=>'order_source_order_id',
// 				'buyeid'=>'source_buyer_user_id',
// 				'email'=>'consignee_email',
// 				'consignee'=>'consignee'
// 						];
// 				$key = $kv[$_REQUEST['keys']];
// 				if(!empty($_REQUEST['fuzzy'])){
// 					$data->andWhere("$key like :val",[':val'=>"%".$_REQUEST['searchval']."%"]);
// 				}else{
// 					$data->andWhere("$key = :val",[':val'=>$_REQUEST['searchval']]);
// 				}
		
// 			}elseif ($_REQUEST['keys']=='sku'){
// 				if(!empty($_REQUEST['fuzzy'])){
// 					$ids = Helper_Array::getCols(OdOrderItem::find()->where('sku like :sku',[':sku'=>"%".$_REQUEST['searchval']."%"])->select('order_id')->asArray()->all(),'order_id');
// 				}else{
// 					$ids = Helper_Array::getCols(OdOrderItem::find()->where('sku = :sku',[':sku'=>$_REQUEST['searchval']])->select('order_id')->asArray()->all(),'order_id');
// 				}
// 				$data->andWhere(['IN','order_id',$ids]);
// 			}elseif ($_REQUEST['keys']=='tracknum'){
// 				if(!empty($_REQUEST['fuzzy'])){
// 					$ids = Helper_Array::getCols(OdOrderShipped::find()->where('tracking_number like :tn',[':tn'=>"%".$_REQUEST['searchval']."%"])->select('order_id')->asArray()->all(),'order_id');
// 				}else{
// 					$ids = Helper_Array::getCols(OdOrderShipped::find()->where('tracking_number = :tn',[':tn'=>$_REQUEST['searchval']])->select('order_id')->asArray()->all(),'order_id');
// 				}
// 				$data->andWhere(['IN','order_id',$ids]);
// 			}elseif ($_REQUEST['keys']=='order_source_itemid'){
// 				//aliexpress product id
// 				$data->andWhere('order_id in (select order_id from od_order_item_v2 where order_source_itemid =:order_source_itemid) ',[':order_source_itemid'=>$_REQUEST['searchval']]);
// 			}
// 		}
// 		if (!empty($_REQUEST['selleruserid'])){
// 			//搜索卖家账号
// 			$data->andWhere('selleruserid = :s',[':s'=>$_REQUEST['selleruserid']]);
// 		}
// 		if (!empty($_REQUEST['country'])){
// 			$data->andWhere(['consignee_country_code'=>explode(',', $_REQUEST['country'])]);
// 			$showsearch=1;
// 		}
		
// 		if (!empty($_REQUEST['tracker_status'])){
// 			//logistic_status 先于erp2.1， 所以 tracker_status 废弃不使用
// 			//tracker 状态
// 			$data->andWhere('logistic_status = :tracker_status',[':tracker_status'=>$_REQUEST['tracker_status']]);
// 			$showsearch=1;
// 		}
		
// 		if (!empty($_REQUEST['pay_order_type'])){
// 			if($_REQUEST['pay_order_type'] != 'all'){
// 				//已付款订单类型
// 				$data->andWhere('pay_order_type = :pay_order_type',[':pay_order_type'=>$_REQUEST['pay_order_type']]);
// 				$showsearch=1;
// 			}
// 		}
		
// 		if (!empty($_REQUEST['is_merge'])){
// 			// 合并订单过滤
// 			$data->andWhere(['order_relation'=>'sm']);
// 		}else{
// 			$data->andWhere(['order_relation'=>['normal','sm']]);
// 		}
		
		//时间搜索
// 		if (!empty($_REQUEST['startdate'])||!empty($_REQUEST['enddate'])){
// 			//搜索订单日期
// 			switch ($_REQUEST['timetype']){
// 				case 'soldtime':
// 					$tmp='order_source_create_time';
// 					break;
// 				case 'paidtime':
// 					$tmp='paid_time';
// 					break;
// 				case 'printtime':
// 					$tmp='printtime';
// 					break;
// 				case 'shiptime':
// 					$tmp='delivery_time';
// 					break;
// 				default:
// 					$tmp='order_source_create_time';
// 					break;
// 			}
// 			if (!empty($_REQUEST['startdate'])){
// 				$data->andWhere("$tmp >= :stime",[':stime'=>strtotime($_REQUEST['startdate'])]);
// 			}
// 			if (!empty($_REQUEST['enddate'])){
// 				$enddate = strtotime($_REQUEST['enddate']) + 86400;
// 				$data->andWhere("$tmp <= :time",[':time'=>$enddate]);
// 			}
// 			$showsearch=1;
// 		}
		//排序
// 		$orderstr = 'order_source_create_time';//默认按照下单时间
// 		if (!empty ($_REQUEST['customsort'])){
				
// 			switch ($_REQUEST['customsort']){
// 				case 'soldtime':
// 					$orderstr='order_source_create_time';
// 					break;
// 				case 'paidtime':
// 					$orderstr='paid_time';
// 					break;
// 				case 'printtime':
// 					$orderstr='printtime';
// 					break;
// 				case 'shiptime':
// 					$orderstr='delivery_time';
// 					break;
// 				case 'order_id':
// 					$orderstr='order_id';
// 					break;
// 				case 'grand_total':
// 					$orderstr='grand_total';
// 					break;
// 				default:
// 					$orderstr='order_source_create_time';
// 					break;
// 			}
// 			$showsearch=1;
// 		}
		//是否升序
// 		if (!empty ($_REQUEST['ordersorttype'])){
// 			$orderstr=$orderstr.' '.$_REQUEST['ordersorttype'];
// 		}else{
// 			$orderstr=$orderstr.' '.'desc';
// 		}
		
		
		
// 		if (!empty($_REQUEST['carrier_code'])){
// 			//物流商
// 			$data->andWhere(['default_carrier_code'=>$_REQUEST['carrier_code']]);
// 			$showsearch=1;
// 		}
		
		
// 		$data->orderBy($orderstr)->with('items');
		 
		$addi_condition = ['order_source'=>'jumia'];
		$addi_condition['sys_uid'] = \Yii::$app->user->id;
		$addi_condition['selleruserid_tmp'] = $accountList;
		
		$tmp_REQUEST_text['where']=empty($data->where)?Array():$data->where;
		$tmp_REQUEST_text['orderBy']=empty($data->orderBy)?Array():$data->orderBy;
		$omsRT = OrderApiHelper::getOrderListByConditionOMS($_REQUEST,$addi_condition,$data,$pageSize,false,'all');
		if (!empty($_REQUEST['order_status'])){
		    //生成操作下拉菜单的code
		    $op_code = $_REQUEST['order_status'];
		}
		if (!empty($omsRT['showsearch'])) $showsearch = 1;
		
		$pages = new Pagination([
				'defaultPageSize' => 20,
				'pageSize' => $pageSize,
				'totalCount' => $data->count(),
				'pageSizeLimit'=>[5,200],//每页显示条数范围
				'params'=>$_REQUEST,
				]);
		$models = $data->offset($pages->offset)
		->limit($pages->limit)
		->all();
		 
		 
		// 调试sql
		/*
		 $tmpCommand = $data->createCommand();
		echo "<br>".$tmpCommand->getRawSql();
		*/
		$excelmodel	=	new Excelmodel();
		$model_sys	=	$excelmodel->find()->all();
		 
		$excelmodels=array(''=>'导出订单');
		if(isset($model_sys)&&!empty($model_sys)){
			foreach ($model_sys as $m){
				$excelmodels[$m->id]=$m->name;
			}
		}
		 
		//订单数量统计
		$counter = [];
		$hitCache = "NoHit";
		$cachedArr = array();
		$uid = \Yii::$app->user->id;
		$stroe = 'all';
		if(!empty($_REQUEST['selleruserid']))
			$stroe  = trim($_REQUEST['selleruserid']);
		 
		if($isParent){
			$gotCache = RedisHelper::getOrderCache2($puid,$uid,'jumia',"MenuStatisticData",$stroe) ;
		}else{
			if (!empty($_REQUEST['selleruserid'])){
				$gotCache = RedisHelper::getOrderCache2($puid,$uid,'jumia',"MenuStatisticData",$_REQUEST['selleruserid']) ;
			}else{
				$gotCache = RedisHelper::getOrderCache2($puid,$uid,'jumia',"MenuStatisticData",'all') ;
			}
		}
		if (!empty($gotCache)){
			 
			$cachedArr = is_string($gotCache)?json_decode($gotCache,true):$gotCache;
			$counter = $cachedArr;
			$hitCache= "Hit";
		}
		
		 
		//redis没有记录的话，则实时计算，再记录到redis
		if ($hitCache <>"Hit"){
			if (!empty($_REQUEST['selleruserid'])){
				$counter = OrderHelper::getMenuStatisticData('jumia',['selleruserid'=>$_REQUEST['selleruserid']]);
			}else{
				if(!empty($accountList)){
					$counter = OrderHelper::getMenuStatisticData('jumia',['selleruserid'=>$accountList]);
				}else{
					//无有效绑定账号
					$counter=[];
					$claimOrderIDs=[];
				}
			}
			//save the redis cache for next time use
			if (!empty($_REQUEST['selleruserid'])){
				RedisHelper::setOrderCache2($puid,$uid,'jumia',"MenuStatisticData",$_REQUEST['selleruserid'],$counter) ;
			}else{
				RedisHelper::setOrderCache2($puid,$uid,'jumia',"MenuStatisticData",'all',$counter) ;
			}
		}
		/*
		//订单数量统计
		if (!empty($_REQUEST['selleruserid'])){
			$counter = OrderHelper::getMenuStatisticData('jumia',['selleruserid'=>$_REQUEST['selleruserid']]);
			$counter['todayorder'] = OdOrder::find()->where(['and',['order_source'=>'jumia'],['>=','order_source_create_time',strtotime(date('Y-m-d'))],['<','order_source_create_time',strtotime('+1 day')] ])
			->andWhere(['selleruserid'=>$_REQUEST['selleruserid']])->count();
			$counter['sendgood'] = OdOrder::find()->where(['order_source'=>'jumia','order_source_status'=>'WaitingForShipmentAcceptation'])->andwhere('order_status < 300')
			->andWhere(['selleruserid'=>$_REQUEST['selleruserid']])->count();
			$counter['issueorder'] = OdOrder::find()->where(['order_source'=>'jumia','issuestatus'=>'IN_ISSUE'])->andWhere(['selleruserid'=>$_REQUEST['selleruserid']])->count();
		}else{
			$counter = OrderHelper::getMenuStatisticData('jumia');
			$counter['todayorder'] = OdOrder::find()->where(['and',['order_source'=>'jumia'],['>=','order_source_create_time',strtotime(date('Y-m-d'))],['<','order_source_create_time',strtotime('+1 day')] ])->count();
			$counter['sendgood'] = OdOrder::find()->where(['order_source'=>'jumia','order_source_status'=>'WaitingForShipmentAcceptation'])->andwhere('order_status < 300')->count();
			$counter['issueorder'] = OdOrder::find()->where(['order_source'=>'jumia','issuestatus'=>'IN_ISSUE'])->count();
		}
		*/	  
		
		$usertabs = Helper_Array::toHashmap(Helper_Array::toArray(Usertab::find()->all()),'id','tabname');
		$warehouseids = InventoryApiHelper::getWarehouseIdNameMap();
		//$selleruserids=Helper_Array::toHashmap(SaasLazadaUser::find()->where(['puid'=>\Yii::$app->user->identity->getParentUid()])->select(['platform_userid'])->andwhere(['platform'=>'jumia'])->andWhere("status <> 3")->asArray()->all(),'platform_userid','platform_userid');
		$countrycode=OdOrder::getDb()->createCommand('select consignee_country_code from '.OdOrder::tableName().' group by consignee_country_code')->queryColumn();
		$countrycode=array_filter($countrycode);
		// 	    $countrys=Helper_Array::toHashmap(EbayCountry::find()->where(['country'=>$countrycode])->orderBy('description asc')->select(['country','description'])->asArray()->all(),'country','description');
		$search = array('is_comment_status'=>'等待您留评');
		//tag 数据获取
		$allTagDataList = OrderTagHelper::getTagByTagID();
		$allTagList = [];
		foreach($allTagDataList as $tmpTag){
			$allTagList[$tmpTag['tag_id']] = $tmpTag['tag_name'];
		}
		 
		//订单导航
// 		if (!empty($_REQUEST['order_status']))
// 			$order_nav_key_word = $_REQUEST['order_status'];
// 		else
// 			$order_nav_key_word='';
		
		
		//国家
		$query = SysCountry::find();
		$regions = $query->orderBy('region')->groupBy('region')->select('region')->asArray()->all();
		$countrys =[];
		foreach ($regions as $region){
			$arr['name']= $region['region'];
			$arr['value']=Helper_Array::toHashmap(SysCountry::find()->where(['region'=>$region['region']])->orderBy('country_en')->select(['country_code', "CONCAT( country_zh ,'(', country_en ,')' ) as country_name "])->asArray()->all(),'country_code','country_name');
			$countrys[]= $arr;
		}
		
		$country_list=[];
		
		//获取国家列表
		$countryArr = array();
		$tmpCountryArr = OdOrder::find()->select('consignee_country_code, consignee_country')->distinct('consignee_country')->where(['order_source' => 'jumia'])->asArray()->all();
		$countryArr = Helper_Array::toHashmap($tmpCountryArr , 'consignee_country_code' , 'consignee_country');
		$countryArr = array_filter($countryArr);
		//end 获取国家列表
		
		//获取dash board cache 数据
		$uid = \Yii::$app->user->identity->getParentUid();
// 		$DashBoardCache = AliexpressOrderHelper::getOmsDashBoardCache($uid);
		
		//检查报关信息是否存在 start
		$OrderIdList = [];
		$existProductResult = OrderBackgroundHelper::getExistProductRuslt($models);
		//检查报关信息是否存在 end
		
		$doarr = JumiaOrderHelper::getCurrentOperationList($op_code,'b');
		$doarr_one = JumiaOrderHelper::getCurrentOperationList($op_code,'s');
		//增加jumia官方发票打印
		if(isset($doarr['ExternalDoprint'])){
		    $doarr['InvoiceDoprint'] = 'Jumia官方发票打印';
		}
		
		$tmp_REQUEST_text['REQUEST']=$_REQUEST;
		$tmp_REQUEST_text['order_source']=$addi_condition;
		$tmp_REQUEST_text['params']=empty($data->params)?Array():$data->params;
		$tmp_REQUEST_text=base64_encode(json_encode($tmp_REQUEST_text));
		
		return $this->render('list',array(
				'models' => $models,
				'existProductResult'=>$existProductResult,
				'pages' => $pages,
				'excelmodels'=>$excelmodels,
				'usertabs'=>$usertabs,
				'counter'=>$counter,
				'warehouseids'=>$warehouseids,
				'selleruserids'=>$selleruserids,
				'countrys'=>$countrys,
				'showsearch'=>$showsearch,
				'tag_class_list'=> OrderTagHelper::getTagColorMapping(),
				'all_tag_list'=>$allTagList,
				'doarr'=>$doarr,
				'doarr_one'=>$doarr_one,
				'country_mapping'=>$country_list,
				'region'=>WarehouseHelper::countryRegionChName(),
				'search'=>$search,
				'countryArr'=>$countryArr,
				'search_condition'=>$tmp_REQUEST_text,
				'search_count'=>$pages->totalCount,
		));
	}
	
	/**
	 * 订单编辑
	 * @author fanjs
	 */
	public function actionEdit(){
		if (\Yii::$app->request->isPost){
			if (count($_POST['item']['product_name'])==0){
				return $this->render('//errorview',['title'=>'编辑订单','error'=>'订单必需有相应商品']);
			}
			$order = OdOrder::findOne($_POST['orderid']);
			if (empty($order)){
				return $this->render('//errorview',['title'=>'编辑订单','error'=>'无对应订单']);
			}
			$item_tmp = $_POST['item'];
			$_tmp = $_POST;
			unset($_tmp['orderid']);
			unset($_tmp['item']);
			$addtionLog = '';
			if (!empty($_tmp['default_shipping_method_code'])){
				$serviceid = SysShippingService::findOne($_tmp['default_shipping_method_code']);
				if (!empty($serviceid)||!$serviceid->isNewRecord){
					$_tmp['default_shipping_method_code']=$_tmp['default_shipping_method_code'];
					$_tmp['default_carrier_code']=$serviceid->carrier_code;
				}
			}
			
			// 订单模块支持 待发货数量更新
			$action = '修改订单';
			$module = 'order';
			$fullName = \Yii::$app->user->identity->getFullName();
			$rt = OrderUpdateHelper::updateOrder($order, $_tmp , false , $fullName , $action , $module);
// 			$order->setAttributes($_tmp);
// 			$order->save();
			//存储订单对应商品
			foreach ($item_tmp['product_name'] as $key=>$val){
				if (strlen($item_tmp['itemid'][$key])){
					$item = OdOrderItem::findOne($item_tmp['itemid'][$key]);
					$OriginQty = $item->quantity; //修改前的数量
				}else{
					$item = new OdOrderItem();
					$OriginQty = 0; //修改前的数量
				}
				$item->order_id = $order->order_id;
				$item->product_name = $item_tmp['product_name'][$key];
				$item->sku = $item_tmp['sku'][$key];
				$item->quantity = $item_tmp['quantity'][$key];
				$item->price = $item_tmp['price'][$key];
				$item->update_time = time();
				$item->create_time = is_null($item->create_time)?time():$item->create_time;
				if ($item->save()){
					if ($OriginQty != $item_tmp['quantity'][$key]){
						list($ack , $message , $code , $rootSKU ) = array_values(OrderGetDataHelper::getRootSKUByOrderItem($item));
							
						list($ack , $code , $message  )  = array_values( OrderBackgroundHelper::updateUnshippedQtyOMS($rootSKU, $order->default_warehouse_id, $order->default_warehouse_id, $OriginQty, $item_tmp['quantity'][$key]));
						if ($ack){
							$addtionLog .= "$rootSKU $OriginQty=>".$item_tmp['quantity'][$key];
						}
					}
				}
			}
			$order->checkorderstatus();
			$order->save();
			
			AppTrackerApiHelper::actionLog("Oms-jumia", "/order/jumia/edit-save");
// 			OperationLogHelper::log('order',$order->order_id,'修改订单','编辑订单进行订单修改',\Yii::$app->user->identity->getFullName());
			echo "<script language='javascript'>window.opener.location.reload();</script>";
			return $this->render('//successview',['title'=>'编辑订单']);
		}
		
		AppTrackerApiHelper::actionLog("Oms-jumia", "/order/jumia/edit-page");
		if (!isset($_GET['orderid'])){
			return $this->render('//errorview',['title'=>'编辑订单','error'=>'链接有误']);
		}
		$order = OdOrder::findOne($_GET['orderid']);
		if (empty($order)||$order->isNewRecord){
			return $this->render('//errorview',['title'=>'编辑订单','error'=>'未找到相应订单']);
		}
		
		$carriers = CarrierApiHelper::getShippingServices2_1();// 所有运输服务
		$warehouses = InventoryApiHelper::getWarehouseIdNameMap();// 所有仓库
		
		$warehouseList = InventoryApiHelper::getWarehouseIdNameMap(true);
		$shipmethodList = [];
		if(!empty($warehouseList)){
			foreach ($warehouseList as $k=>$name){
				$shippingMethodInfo = CarrierOpenHelper::getShippingMethodNameInfo(array('proprietary_warehouse'=>$k), -1);
				$shippingMethodInfo = $shippingMethodInfo['data'];
				if(!empty($shippingMethodInfo)){
					foreach ($shippingMethodInfo as $id=>$ship){
						$shipmethodList[$id] = $ship['service_name'];
					}
				}
				break;
			}
		}
		
		$selfJumiaOrderStatus = OdOrder::$status;
		unset($selfJumiaOrderStatus[100]);
		unset($selfJumiaOrderStatus[400]);
		
		return $this->render('edit',['order'=>$order,'carriers'=>$shipmethodList , 'warehouses'=>$warehouseList , 'selfJumiaOrderStatus'=>$selfJumiaOrderStatus]);
	}
	
	/**
	 * 订单列表页面进行批量平台标记发货,提供给用户填写物流号，运输服务等的页面
	 * @author dzt
	 */
	public function actionSignshipped(){
		AppTrackerApiHelper::actionLog("Oms-jumia", "/order/jumia/signshipped");
		$jumiaShippingMethod = LazadaApiHelper::getJumiaShippingCodeNameMap();
		if (\Yii::$app->request->getIsPost()){
			$orders = OdOrder::find()->where(['in','order_id',\Yii::$app->request->post()['order_id']])->all();
			return $this->render('signshipped',['orders'=>$orders,'jumiaShippingMethod'=>$jumiaShippingMethod]);
		}else {
			$orders = OdOrder::find()->where(['in','order_id',\Yii::$app->request->get('order_id')])->all();
			return $this->render('signshipped',['orders'=>$orders,'jumiaShippingMethod'=>$jumiaShippingMethod]);
		}
	}
	
	/**
	 * 订单列表页面进行批量平台标记发货,插入标记队列
	 * @author dzt
	 */
	public function actionSignshippedsubmit(){		
		
		if (\Yii::$app->request->getIsPost()){			
			AppTrackerApiHelper::actionLog("Oms-jumia", "/order/jumia/signshippedsubmit");
			
			$user = \Yii::$app->user->identity;
			$postarr = \Yii::$app->request->post();
			$jumiaShippingMethod = LazadaApiHelper::getJumiaShippingCodeNameMap();
			if (count($postarr['order_id'])){
				foreach ($postarr['order_id'] as $oid){
					try {
						if(empty($postarr['shipmethod'][$oid])){
							return $this->render('//errorview',['title'=>'订单合并','error'=>"订单：$oid 请选择jumia平台对应的运输服务"]);
						}
							
						$shipping_method_code = $postarr['shipmethod'][$oid];
						$order = OdOrder::findOne($oid);
						$logisticInfoList=[
						'0'=>[
						'order_source'=>$order->order_source,
						'selleruserid'=>$order->selleruserid,
						'tracking_number'=>$postarr['tracknum'][$oid],
						'tracking_link'=>"http://www.17track.net",//jumia 标记发货屏蔽了tracking link填写。为防止其他地方有用，所以这里hardcode了
						'shipping_method_code'=>$shipping_method_code,
						'shipping_method_name'=>$jumiaShippingMethod[$shipping_method_code],//平台物流服务名
						'order_source_order_id'=>$order->order_source_order_id,
						'description'=>'',//jumia 标记发货屏蔽了发货备注，没用
						'signtype'=>"all",//jumia 根据发货产品自己判断是否部分发货，所以这里的标记没用，mark个 all
						'addtype'=>'手动标记发货',
						]
						];
						if(!OrderHelper::saveTrackingNumber($oid, $logisticInfoList,0,1)){
							\Yii::error(["Order",__CLASS__,__FUNCTION__,"Online",'订单'.$oid.'插入失败'],'edb\global');
						}else{
							OperationLogHelper::log('order', $oid,'标记发货','手动批量标记发货',\Yii::$app->user->identity->getFullName());
						}
					}catch (\Exception $ex){
						\Yii::error(["Order",__CLASS__,__FUNCTION__,"Online","save to SignShipped failure:".print_r($ex->getMessage())],'edb\global');
					}
				}
				return $this->render('//successview',['title'=>'Jumia标记发货完成','message'=>'标记结果可查看Jumia状态']);
			}
		}
	}
	
	/**
	 * 订单合并
	 * @author dzt
	 */
	public function actionMergeorder(){
		if (\Yii::$app->request->isPost){
			AppTrackerApiHelper::actionLog("Oms-jumia", "/order/jumia/mergeorder");
			
			$orders = OdOrder::find()->where(['in','order_id',$_POST['order_id']])->all();
			$_tmporder = $orders[0];
			$ismerge = true;//判断选择的订单是否符合合并，买家名，地址等是否一样
			$error = '';
			if (count($orders)<2||count($orders)!=count($_POST['order_id'])){
				$ismerge = false;
				$error='合并订单数必须大于2,或传入的订单数ID与系统获取的订单数不符';
			}
			if ($ismerge){
				foreach ($orders as $order){
					if ($order->order_status != OdOrder::STATUS_PAY){
						$ismerge = false;
						$error='合并订单的状态必须为[已付款]';
						break;
					}
					if ($order->selleruserid != $_tmporder->selleruserid
					||$order->source_buyer_user_id != $_tmporder->source_buyer_user_id
					||$order->consignee != $_tmporder->consignee
					||$order->consignee_address_line1 != $_tmporder->consignee_address_line1){
						$ismerge = false;
						$error='合并订单的收件人等信息必须一致';
						break;
					}
					if ($order->order_source != $_tmporder->order_source||
					$order->currency != $_tmporder->currency){
						$ismerge = false;
						$error='合并订单的平台及币种必须一致';
						break;
					}
					// 由于系统的jumia账号是通过jumia用户邮箱+站点来确认唯一的，为了方便查找jumia账号，发货队列的记录记录了订单站点id。
					// 确保合并订单时，jumia不同站点订单不能合并，否则查找jumia账号会找错账号。
					if ($order->order_source_site_id != $_tmporder->order_source_site_id){
						$ismerge = false;
						$error='jumia合并订单的站点要一致';
						break;
					}
				}
			}
			if (!$ismerge){
				return $this->render('//errorview',['title'=>'订单合并','error'=>$error]);
			}else{
				//开始合并订单
				$droporderids = [];
				$shipping_cost = 0;
				$subtotal = 0;
				$grand_total=0;
				foreach ($orders as $order){
					foreach ($order->items as $item){
						//原订单的商品修正订单号
						$item->order_id = $_tmporder->order_id;
						$item->save();
					}
					$shipping_cost+=$order->shipping_cost;
					$subtotal+=$order->subtotal;
					$grand_total+=$order->grand_total;
					if ($order->order_id!=$_tmporder->order_id){
						array_push($droporderids,$order->order_id);
					}
				}
				$_tmporder->setAttributes([
						'shipping_cost'=>$shipping_cost,
						'subtotal'=>$subtotal,
						'grand_total'=>$grand_total
						]);
				$_tmporder->save();
				//删除旧订单数据
				foreach ($droporderids as $id){
					OdOrder::deleteAll('order_id = :oi',[':oi'=>$id]);
					OperationLogHelper::log('order', $id,'合并订单','删除该订单,合并到订单'.$_tmporder->order_id,\Yii::$app->user->identity->getFullName());
					OperationLogHelper::log('order', $_tmporder->order_id,'合并订单','合并原订单'.$id.'到该订单',\Yii::$app->user->identity->getFullName());
				}
				echo "<script language='javascript'>alert('Success');window.opener.location.reload();window.close();</script>";
				//return $this->render('//successview',['title'=>'合并已付款订单']);
			}
		}
	}
	
}



?>