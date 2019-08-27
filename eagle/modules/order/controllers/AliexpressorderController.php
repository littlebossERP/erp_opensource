<?php
namespace eagle\modules\order\controllers;

use yii\data\Pagination;

use eagle\modules\order\models\OdOrder;
use eagle\modules\order\models\Excelmodel;
use common\helpers\Helper_Array;
use eagle\modules\order\models\OdOrderItem;
use eagle\modules\util\helpers\OperationLogHelper;
use eagle\modules\order\helpers\OrderHelper;
use eagle\modules\order\models\Usertab;
use Exception;
use eagle\modules\order\models\OdOrderShipped;
use eagle\models\carrier\SysShippingService;
use eagle\modules\inventory\helpers\InventoryApiHelper;
use eagle\models\EbayCountry;
use eagle\modules\order\helpers\OrderTagHelper;
use eagle\models\SaasAliexpressUser;
use common\api\aliexpressinterface\AliexpressInterface_Api;
use common\api\aliexpressinterface\AliexpressInterface_Helper;
use console\helpers\AliexpressHelper;
use eagle\modules\app\apihelpers\AppTrackerApiHelper;
use eagle\modules\util\helpers\ConfigHelper;
use eagle\modules\order\helpers\AliexpressOrderHelper;
use eagle\modules\util\helpers\TranslateHelper;
use eagle\models\sys\SysCountry;
use eagle\modules\inventory\helpers\WarehouseHelper;
use eagle\modules\platform\apihelpers\AliexpressAccountsApiHelper;
use eagle\modules\order\helpers\OrderBackgroundHelper;
use eagle\modules\app\apihelpers\AppApiHelper;
use eagle\modules\catalog\apihelpers\ProductApiHelper;
use eagle\modules\order\apihelpers\OrderApiHelper;
use eagle\modules\order\helpers\OrderGetDataHelper;
use eagle\modules\order\helpers\OrderUpdateHelper;
use eagle\modules\util\helpers\SysLogHelper;
use eagle\modules\util\helpers\RedisHelper;
use eagle\modules\platform\apihelpers\PlatformAccountApi;
use eagle\modules\dash_board\apihelpers\DashBoardStatisticHelper;
use eagle\modules\dash_board\helpers\DashBoardHelper;
use common\api\aliexpressinterfacev2\AliexpressInterface_Helper_Qimen;
use common\api\aliexpressinterfacev2\AliexpressInterface_Api_Qimen;

class AliexpressorderController extends \eagle\components\Controller{
	public $enableCsrfValidation = false;


	public function actionAliexpresslist(){
		$puid = \Yii::$app->subdb->getCurrentPuid();
		//check模块权限
		$permission = \eagle\modules\permission\apihelpers\UserApiHelper::checkPlatformPermission('aliexpress');
		if(!$permission)
			return $this->render('//errorview_no_close',['title'=>'访问受限:没有权限','error'=>'您还没有订单模块的权限!']);
 
		$current_time=explode(" ",microtime()); $time1=round($current_time[0]*1000+$current_time[1]*1000);
			
		AppTrackerApiHelper::actionLog("Oms-aliexpress", "/order/aliexpress/list");

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
		//$data->andWhere(['order_source'=>'aliexpress']);
		
		
		
		$showsearch=0;
		$op_code = '';
		
		$puid = \Yii::$app->user->identity->getParentUid();
		$isParent = \eagle\modules\permission\apihelpers\UserApiHelper::isMainAccount();
		
		//不显示 解绑的账号的订单 start
		//$tmpSellerIDList =  AliexpressAccountsApiHelper::getAllAccounts(\Yii::$app->user->identity->getParentUid());//添加账号权限前 lzhl 2017-03
		$tmpSellerIDList = PlatformAccountApi::getPlatformAuthorizeAccounts('aliexpress');//添加账号权限	lzhl 2017-03
		$aliAccountList = [];
		
		//$selleruserids=Helper_Array::toHashmap(SaasAliexpressUser::find()->where(['uid'=>\Yii::$app->user->identity->getParentUid()])->select(['sellerloginid'])->asArray()->all(),'sellerloginid','sellerloginid');
		//testkh start 
		$selleruserids = [];
		$test_userid=\eagle\modules\tool\helpers\MirroringHelper::$test_userid;
		if (isset(\Yii::$app->params["currentEnv"]) and \Yii::$app->params["currentEnv"]=="test" ){
			$aliAccountList = [];//testkh
		}
		else if(in_array($puid,$test_userid['yifeng'])){
			//如果为测试用的账号就不受平台绑定限制
			$aliAccountList = [];
		}
		else{
			
			foreach($tmpSellerIDList as $sellerloginid=>$store_name){
				$aliAccountList[] = $sellerloginid;
				$selleruserids[$sellerloginid] = $sellerloginid.'【'. $store_name.'】';
			}
			if(empty($aliAccountList)){
					//无有效权限账号时
					return $this->render('//errorview_no_close',['title'=>'访问受限:没有权限','error'=>'您还没有获得任何 速卖通 账号管理权限!']);
			}
			
			//不显示 解绑的账号的订单
			$data->andWhere(['selleruserid'=>$aliAccountList]);
		}
		//testkh end
		
		//不显示 解绑的账号的订单 end
		/*
		
		if (!empty($_REQUEST['is_merge'])){
			// 合并订单过滤
			$data->andWhere(['order_relation'=>'sm']);
		}else{
			$data->andWhere(['order_relation'=>['normal','sm']]);
		}
		
		if (!empty($_REQUEST['order_capture'])){
			//手工订单查询
			$data->andWhere(['order_capture'=>$_REQUEST['order_capture']]);
			$showsearch=1;
		}
		
		if (!empty($_REQUEST['order_status'])){
			//搜索订单状态
			$data->andWhere('order_status = :os',[':os'=>$_REQUEST['order_status']]);
			//生成操作下拉菜单的code
			$op_code = $_REQUEST['order_status'];
		}
		if (isset($_REQUEST['exception_status'])){
			//搜索异常状态
			if ($_REQUEST['exception_status'] == '0'){
				//已付款订单处理 , 默认为待检测
				$data->andWhere('exception_status = :es',[':es'=>$_REQUEST['exception_status']]);
				$data->andWhere('order_status = :os',[':os'=>OdOrder::STATUS_PAY]);
				//生成操作下拉菜单的code
				$op_code = OdOrder::STATUS_PAY;
			}elseif(!empty($_REQUEST['exception_status'])){
				//非默认状态
				$data->andWhere('exception_status = :es',[':es'=>$_REQUEST['exception_status']]);
				//生成操作下拉菜单的code
				$op_code = $_REQUEST['exception_status'];
			}
			
		}
		if (!empty($_REQUEST['cangku'])){
			//搜索仓库
			$data->andWhere('default_warehouse_id = :dwi',[':dwi'=>$_REQUEST['cangku']]);
			$showsearch=1;
		}
		if (!empty($_REQUEST['shipmethod'])){
			//搜索运输服务
			$data->andWhere('default_shipping_method_code = :dsmc',[':dsmc'=>$_REQUEST['shipmethod']]);
			$showsearch=1;
		}
		*/
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
		
		if (!empty($_REQUEST['order_status']) && $_REQUEST['order_status'] == OdOrder::STATUS_PAY){
			//Aliexpress状态
			$data->andWhere( " ifnull (order_source_status  , '' )  <> 'RISK_CONTROL' ");
		}
		/*
		// 订单系统标签 查询
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
			
			$data->andWhere([$reverseStr.'in', 'order_id', (new \yii\db\Query())->select('order_id')->from('od_order_systags_mapping')->where(['tag_code' => $sysTagList])]);
		}
		
		
		if (!empty($_REQUEST['sel_tag'])){
			//搜索卖家账号
			$data->andWhere('order_id in (select order_id from lt_order_tags where tag_id in (:sel_tag)) ',[':sel_tag'=>implode(',', $_REQUEST['sel_tag'])]);
			$showsearch=1;
		}
		if (!empty($_REQUEST['order_evaluation'])){
			//评价
			$data->andWhere('order_evaluation = :order_evaluation',[':order_evaluation'=>$_REQUEST['order_evaluation']]);
			$showsearch=1;
		}
		
		if (!empty($_REQUEST['reorder_type'])){
			if ($_REQUEST['reorder_type'] != 'all'){
				//重新发货类型
				$data->andWhere('reorder_type =:reorder_type ',[':reorder_type'=>$_REQUEST['reorder_type']]);
			}else{
				$data->andWhere(['not', ['reorder_type' => null]]);
				//生成操作下拉菜单的code
				$op_code = 'reo';
			}
			
			$showsearch=1;
		}
		
		if (!empty($_REQUEST['fuhe'])){
			$showsearch=1;
			//搜索符合条件
			switch ($_REQUEST['fuhe']){
				case 'is_comment_status':
					$data->andWhere('is_comment_status = 0');
					break;
				default:break;
			}
		}
		if (!empty($_REQUEST['searchval'])){
			//搜索用户自选搜索条件
			if (in_array($_REQUEST['keys'], ['order_id','order_source_order_id','buyeid','consignee','email'])){
				$kv=[
					'order_id'=>'order_id',
					'order_source_order_id'=>'order_source_order_id',
					'buyeid'=>'source_buyer_user_id',
					'email'=>'consignee_email',
					'consignee'=>'consignee'
				];
				$key = $kv[$_REQUEST['keys']];
				if(!empty($_REQUEST['fuzzy'])){
					$data->andWhere("$key like :val",[':val'=>"%".$_REQUEST['searchval']."%"]);
				}else{
					$data->andWhere("$key = :val",[':val'=>$_REQUEST['searchval']]);
				}
				
			}elseif ($_REQUEST['keys']=='sku'){
				if(!empty($_REQUEST['fuzzy'])){
					$ids = Helper_Array::getCols(OdOrderItem::find()->where('sku like :sku',[':sku'=>"%".$_REQUEST['searchval']."%"])->select('order_id')->asArray()->all(),'order_id');
				}else{
					$ids = Helper_Array::getCols(OdOrderItem::find()->where('sku = :sku',[':sku'=>$_REQUEST['searchval']])->select('order_id')->asArray()->all(),'order_id');
				}
				$data->andWhere(['IN','order_id',$ids]);
			}elseif ($_REQUEST['keys']=='tracknum'){
				if(!empty($_REQUEST['fuzzy'])){
					$ids = Helper_Array::getCols(OdOrderShipped::find()->where('tracking_number like :tn',[':tn'=>"%".$_REQUEST['searchval']."%"])->select('order_id')->asArray()->all(),'order_id');
				}else{
					$ids = Helper_Array::getCols(OdOrderShipped::find()->where('tracking_number = :tn',[':tn'=>$_REQUEST['searchval']])->select('order_id')->asArray()->all(),'order_id');
				}
				$data->andWhere(['IN','order_id',$ids]);
			}elseif ($_REQUEST['keys']=='order_source_itemid'){
				//aliexpress product id
				$data->andWhere('order_id in (select order_id from od_order_item_v2 where order_source_itemid =:order_source_itemid) ',[':order_source_itemid'=>$_REQUEST['searchval']]);
			}
		}
		if (!empty($_REQUEST['selleruserid'])){
			//搜索卖家账号
			$data->andWhere('selleruserid = :s',[':s'=>$_REQUEST['selleruserid']]);
		}
		if (!empty($_REQUEST['country'])){
			$data->andWhere(['consignee_country_code'=>explode(',', $_REQUEST['country'])]);
			$showsearch=1;
		}
		
		if (!empty($_REQUEST['tracker_status'])){
			//logistic_status 先于erp2.1， 所以 tracker_status 废弃不使用
			//tracker 状态
			$data->andWhere('logistic_status = :tracker_status',[':tracker_status'=>$_REQUEST['tracker_status']]);
			$showsearch=1;
		}
		
		if (!empty($_REQUEST['pay_order_type'])){
			if($_REQUEST['pay_order_type'] != 'all'){
				//已付款订单类型
				$data->andWhere('pay_order_type = :pay_order_type',[':pay_order_type'=>$_REQUEST['pay_order_type']]);
				$showsearch=1;
			}
		}
		
		
		
		//时间搜索
		if (!empty($_REQUEST['startdate'])||!empty($_REQUEST['enddate'])){
			//搜索订单日期
			switch ($_REQUEST['timetype']){
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
			if (!empty($_REQUEST['startdate'])){
				$data->andWhere("$tmp >= :stime",[':stime'=>strtotime($_REQUEST['startdate'])]);
			}
			if (!empty($_REQUEST['enddate'])){
				$enddate = strtotime($_REQUEST['enddate']) + 86400;
				$data->andWhere("$tmp <= :time",[':time'=>$enddate]);
			}
			$showsearch=1;
		}
		//排序
		$orderstr = 'order_source_create_time';//默认按照下单时间
		if (!empty ($_REQUEST['customsort'])){
			
			switch ($_REQUEST['customsort']){
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
		if (!empty ($_REQUEST['ordersorttype'])){
			$orderstr=$orderstr.' '.$_REQUEST['ordersorttype'];
		}else{
			$orderstr=$orderstr.' '.'desc';
		}
		
		
		
		if (!empty($_REQUEST['carrier_code'])){
			//物流商
			$data->andWhere(['default_carrier_code'=>$_REQUEST['carrier_code']]);
			$showsearch=1;
		}
		
		
		$data->orderBy($orderstr)->with('items');
		*/
		$addi_condition = ['order_source'=>'aliexpress'];
		$addi_condition['sys_uid'] = \Yii::$app->user->id;
		$addi_condition['selleruserid_tmp'] = $aliAccountList;
		
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
	    
	    //yzq 2017-2-21, to do bulk loading the order items, not to use lazy load
	    OrderHelper::bulkLoadOrderItemsToOrderModel($models);
	    OrderHelper::bulkLoadOrderShippedModel($models);
	    
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
	    
	    //$counter = [];
	    
	    //订单数量统计
	    /* 改为异步加载*/
	    //使用redis获取订单状态统计数
	    $hitCache = "NoHit";
	    $cachedArrAll = array();
	    $uid = \Yii::$app->user->id;
	    $stroe = 'all';
	    if(!empty($_REQUEST['selleruserid']))
	    	$stroe  = trim($_REQUEST['selleruserid']);
	    
	    if($isParent){
	    	$gotCache = RedisHelper::getOrderCache2($puid,$uid,'aliexpress',"MenuStatisticData",$stroe) ;
	    }else{
	    	if (!empty($_REQUEST['selleruserid'])){
	    		$gotCache = RedisHelper::getOrderCache2($puid,$uid,'aliexpress',"MenuStatisticData",$_REQUEST['selleruserid']) ;
	    	}else{
	    		$gotCache = RedisHelper::getOrderCache2($puid,$uid,'aliexpress',"MenuStatisticData",'all') ;
	    	}
	    }
	    if (!empty($gotCache)){
	    
	    	$cachedArrAll = is_string($gotCache)?json_decode($gotCache,true):$gotCache;
	    	$counter = $cachedArrAll;
	    	$hitCache= "Hit";
	    }
	     
	    
	    //redis没有记录的话，则实时计算，再记录到redis
	    if ($hitCache <>"Hit"){
	    	if (!empty($_REQUEST['selleruserid'])){
	    		$counter = AliexpressOrderHelper::getMenuStatisticData(['selleruserid'=>$_REQUEST['selleruserid']]);
	    	}else{
	    		if(!empty($aliAccountList)){
	    			$counter = AliexpressOrderHelper::getMenuStatisticData(['selleruserid'=>$aliAccountList]);
	    		}else{
	    			//无有效绑定账号
	    			$counter=[];
	    			$claimOrderIDs=[];
	    		}
	    	}
	    	//save the redis cache for next time use
	    	if (!empty($_REQUEST['selleruserid'])){
	    		RedisHelper::setOrderCache2($puid,$uid,'aliexpress',"MenuStatisticData",$_REQUEST['selleruserid'],$counter) ;
	    	}else{
	    		RedisHelper::setOrderCache2($puid,$uid,'aliexpress',"MenuStatisticData",'all',$counter) ;
	    	}
	    }
	    
	    
	    $usertabs = Helper_Array::toHashmap(Helper_Array::toArray(Usertab::find()->all()),'id','tabname');
	    $warehouseids = InventoryApiHelper::getWarehouseIdNameMap();
	    
	//    $countrycode=OdOrder::getDb()->createCommand('select consignee_country_code from '.OdOrder::tableName().' group by consignee_country_code')->queryColumn();
	//    $countrycode=array_filter($countrycode);
// 	    $countrys=Helper_Array::toHashmap(EbayCountry::find()->where(['country'=>$countrycode])->orderBy('description asc')->select(['country','description'])->asArray()->all(),'country','description');
		$search = array('is_comment_status'=>'等待您留评');
		//tag 数据获取
	    $allTagDataList = OrderTagHelper::getTagByTagID();
	    $allTagList = [];
	    foreach($allTagDataList as $tmpTag){
	    	$allTagList[$tmpTag['tag_id']] = $tmpTag['tag_name'];
	    }
	    
	    //订单导航
		if (!empty($_REQUEST['order_status'])) 
			$order_nav_key_word = $_REQUEST['order_status'];
		else 
			$order_nav_key_word='';
		
		
		//国家
		$countrys = OrderHelper::getCountryAndRegion();
		
		$country_list=[];
		
		
		//use this function to performance tuning, it use redis
		if (isset($_REQUEST['order_status'])){
			$redis_order_status = $_REQUEST['order_status'];
		}else{
			$redis_order_status = '';
		}
		$countryArr = OrderHelper::getPlatformOrderCountries($puid , 'aliexpress',$aliAccountList ,$redis_order_status);
		
		//获取dash board cache 数据
		$uid = \Yii::$app->user->identity->getParentUid();
		$DashBoardCache = AliexpressOrderHelper::getOmsDashBoardCache($uid);
		
		//检查报关信息是否存在 start
		$OrderIdList = [];
		$existProductResult = OrderBackgroundHelper::getExistProductRuslt($models);
		//检查报关信息是否存在 end
		
		$tmp_REQUEST_text['REQUEST']=$_REQUEST;
		$tmp_REQUEST_text['order_source']=$addi_condition;
		$tmp_REQUEST_text['params']=empty($data->params)?Array():$data->params;
		$tmp_REQUEST_text=base64_encode(json_encode($tmp_REQUEST_text));
		
		$platform = 'aliexpress';
		$SignShipWarningCount = DashBoardStatisticHelper::CounterGet($puid, DashBoardHelper::${'SIGNSHIPPED_ORDER_ERR_'.strtoupper($platform)});
		
		return $this->renderAuto('aliexpresslist',array(
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
			'doarr'=>AliexpressOrderHelper::getAliexpressCurrentOperationList($op_code,'b') ,
			'doarr_one'=>AliexpressOrderHelper::getAliexpressCurrentOperationList($op_code,'s'),
			'country_mapping'=>$country_list,
			'region'=>WarehouseHelper::countryRegionChName(),
			'search'=>$search,
			'DashBoardCache'=>$DashBoardCache,
			'search_condition'=>$tmp_REQUEST_text,
			'search_count'=>$pages->totalCount,
			'countryArr'=>$countryArr,
			'SignShipWarningCount'=>$SignShipWarningCount,
		));
		
	}
		
	/**
	 * 订单列表页面进行批量平台标记发货,提供给用户填写物流号，运输服务等的页面
	 * @author million
	 */
	public function actionSignshipped(){

		AppTrackerApiHelper::actionLog("Oms-aliexpress", "/order/aliexpress/signshipped");
		
		if (\Yii::$app->request->getIsPost()){
			$orders = OdOrder::find()->where(['in','order_id',\Yii::$app->request->post()['order_id']])->all();
			$ali = json_decode(file_get_contents(\Yii::getAlias('@web').'docs/aliexpressServiceCode.json'));
			return $this->render('signshipped',['orders'=>$orders,'ali'=>$ali]);
		}else {
			$orders = OdOrder::find()->where(['in','order_id',\Yii::$app->request->get('order_id')])->all();
			$ali = json_decode(file_get_contents(\Yii::getAlias('@web').'docs/aliexpressServiceCode.json'));
			return $this->render('signshipped',['orders'=>$orders,'ali'=>$ali]);
		}
	}
	
	/**
	 * 订单列表页面进行批量平台标记发货,插入标记队列
	 * @author million
	 */
	public function actionSignshippedsubmit(){
		if (\Yii::$app->request->getIsPost()){
			AppTrackerApiHelper::actionLog("Oms-aliexpress", "/order/aliexpress/signshippedsubmit");
			$user = \Yii::$app->user->identity;
			$postarr = \Yii::$app->request->post();
			$ali = AliexpressInterface_Helper::getShippingCodeNameMap();
			if (count($postarr['order_id'])){
				$message = "";
				foreach ($postarr['order_id'] as $oid){
					if (empty($postarr['tracknum'][$oid])){
						$message .= "<br>".$oid."标记发货失败！原因： 物流号不能为空！";
						continue; //物流号为空则跳过
					}
					try {
						$shipping_method_code = strlen($postarr['shipmethod'][$oid])>0?$postarr['shipmethod'][$oid]:'Other';
						$order = OdOrder::findOne($oid);
						$logisticInfoList=[
							'0'=>[
								'order_source'=>$order->order_source,
								'selleruserid'=>$order->selleruserid,
								'tracking_number'=>$postarr['tracknum'][$oid],
								'tracking_link'=>$postarr['trackurl'][$oid],
								'shipping_method_code'=>$shipping_method_code,
								'shipping_method_name'=>$ali[$shipping_method_code],//平台物流服务名
								'order_source_order_id'=>$order->order_source_order_id,
								'description'=>$postarr['message'][$oid],
								'signtype'=>$postarr['signtype'][$oid],
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
				if (empty($message)){
					return $this->render('//successview',['title'=>'Aliexpress标记发货完成','message'=>'标记结果可查看Aliexpress状态']);
				}else{
					return $this->render('//successview',['title'=>'Aliexpress标记发货完成','message'=>'<span style="color:red">'.$message."</span>"]);
				}
				
			}			
		}
	}
	/**
	 * 将订单标记为已付款
	 * @author million
	 */
	public function actionSignpayed(){
		if (\Yii::$app->request->isPost){
			$orderids = explode(',',$_POST['orders']);
			Helper_Array::removeEmpty($orderids);
			if (count($orderids)>0){
				try {
					foreach ($orderids as $orderid){
						$order = OdOrder::findOne($orderid);
						if ($order->order_status<200){
							$order->order_status = 200;
							$order->pay_order_type = OdOrder::PAY_PENDING;
							$order->paid_time = time();
							$order->save();
							OperationLogHelper::log('order',$orderid,'修改订单','修改订单状态:未付款至已付款',\Yii::$app->user->identity->getFullName());
						}
					}
					return '操作已完成';
				}catch (\Exception $e){
					return $e->getMessage();
				}	
			}else{
				return '选择的订单有问题';
			}
		}
	}
	
	/**
	 * 导入物流单号
	 * @author million
	 */
	public function actionImportordertracknum(){
		if (\yii::$app->request->isPost){
			if (isset($_FILES['order_tracknum'])){
				try {
					$result = OrderHelper::importtracknumfromexcel($_FILES['order_tracknum']);
					return $result;
				}catch(\Exception $e){
					return $e->getMessage();
				}
			}
		}
	}
	
	/**
	 * 移动订单的状态
	 * @author million
	 */
	public function actionMovestatus(){
		if (\yii::$app->request->isPost){
			$message = '';
			$orderids = explode(',',$_POST['orderids']);
			$orderids = array_filter($orderids);
			if (count($orderids)){
				foreach ($orderids as $orderid){
					$order = OdOrder::findOne($orderid);
					OperationLogHelper::log('order', $orderid,'移动订单','手动批量移动订单,状态:'.OdOrder::$status[$order->order_status].'->'.OdOrder::$status[$_POST['status']],\Yii::$app->user->identity->getFullName());
					$order->order_status = $_POST['status'];
					$order->save();
				}
			}
			return 'success';
		}
	}
	
	/**
	 * 修改订单的挂起状态
	 * @author million
	 */
	public function actionChangemanual(){
		if (\yii::$app->request->isPost){
			$order = OdOrder::findOne($_POST['orderid']);
			if (empty($order)){
				return '未找到相应订单';
			}
			if ($order->is_manual_order == 0){
				$order->is_manual_order = 1;
			}else{
				$order->is_manual_order = 0;
			}
			$order->save();
			return 'success';
		}
	}
	
	/**
	 * 用户自定义标签列表
	 * @author million
	 */
	public function actionUsertab(){
		$uids = [\Yii::$app->user->id,\Yii::$app->user->identity->getParentUid()];
		$tabs = Usertab::findAll(['uid'=>$uids]);
		return $this->render('usertab',['tabs'=>$tabs]);
	}
	
	/**
	 * 用户自定义标签编辑
	 * @author million
	 */
	public function actionEdittab(){
		if(\Yii::$app->request->isPost){
			if (isset($_POST['templateid'])){
				$template = Usertab::findOne($_POST['templateid']);
			}else{
				$template = new Usertab();
			}
			try {
				$template->tabname = $_POST['tabname'];
				$template->uid = \Yii::$app->user->id;
				$template->save();
				return $this->actionUsertab();
			}catch (\Exception $e){
				print_r($e->getMessage());
			}
		}
		if(isset($_GET['id'])&&$_GET['id']>0){
			$template = Usertab::findOne($_GET['id']);
		}else{
			$template = new Usertab();
		}
		return $this->renderPartial('tabedit',['template'=>$template]);
	}
	
	/**
	 * 用户自定义标签删除
	 * @author million
	 */
	public function actionDeletetab(){
		if(\Yii::$app->request->isPost){
			try {
				Usertab::deleteAll('id = '.$_POST['id']);
				return 'success';
			}catch (Exception $e){
				return print_r($e->getMessage());
			}
		}
	}
	
	/**
	 * 订单添加自定义标签
	 * @author million
	 */
	public function actionSetusertab(){
		if(\Yii::$app->request->isPost){
			try {
				$order = OdOrder::findOne($_POST['orderid']);
				$order->order_manual_id = $_POST['tabid'];
				if ($order->save()){
					return 'success';
				}else{
					return '添加自定义标签失败';
				}
			}catch (Exception $e){
				return print_r($e->getMessage());
			}
		}
	}
	
	/**
	 * 订单商品添加备注
	 * @author million
	 */
	public function actionAjaxdesc(){
		if(\Yii::$app->request->isPost){
			$order = OdOrder::findOne($_POST['oiid']);
			if (!empty($order)){
				$rt = OrderHelper::addOrderDescByModel($order,  $_POST['desc'], 'order', '添加备注');
				/*
				$olddesc = $order->desc;
				$order->desc = $_POST['desc'];
				$order->save();
				OperationLogHelper::log('order',$order->order_id,'添加备注','修改备注: ('.$olddesc.'->'.$_POST['desc'] .')',\Yii::$app->user->identity->getFullName());
				
				*/
				$ret_array = array (
						'result' => true,
						'message' => '修改成功'
				);
				echo json_encode ( $ret_array );
				exit();
			}
		}
	}
	
	/**
	 * 订单编辑
	 * @author million
	 */
	public function actionEdit(){
		if (\Yii::$app->request->isPost){
			if (count($_POST['item']['product_name'])==0){
				return $this->render('//errorview','订单必需有相应商品');
			}
			$order = OdOrder::findOne($_POST['orderid']);
			
			if (empty($order)){
				return $this->render('//errorview','无对应订单');
			}
			
			$item_tmp = $_POST['item'];
			$_tmp = $_POST;
			unset($_tmp['orderid']);
			unset($_tmp['item']);
			if (!empty($_tmp['default_shipping_method_code'])){
			$serviceid = SysShippingService::findOne($_tmp['default_shipping_method_code']);
			if (!empty($serviceid)||!$serviceid->isNewRecord){
				$_tmp['default_shipping_method_code']=$_tmp['default_shipping_method_code'];
				$_tmp['default_carrier_code']=$serviceid->carrier_code;
			}
			}
			
			$rt = OrderHelper::setOriginShipmentDetail($order);
			if ($rt['success'] ==false){
				return $this->render('//errorview',['title'=>'编辑订单','message'=>'内部错误，请联系客服']);
			}
			$action = '修改订单';
    		$module = 'order';
    		$fullName = \Yii::$app->user->identity->getFullName();
    		$rt = OrderUpdateHelper::updateOrder($order, $_tmp , false , $fullName , $action , $module);
			
			/*20160928 start
			foreach($_tmp as $key=>$value){
				$order->$key = $value;
			}
			//$order->setAttributes($_tmp,false);
			$order->save();
			20160928 end*/
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
				//$item->order_source_srn = $item_tmp['order_source_srn'][$key];
				//$item->price = $item_tmp['price'][$key];
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
			AppTrackerApiHelper::actionLog("Oms-aliexpress", "/order/aliexpress/edit-save");
			OperationLogHelper::log('order',$order->order_id,'修改订单','编辑订单进行订单修改',\Yii::$app->user->identity->getFullName());
			return $this->render('//successview',['title'=>'编辑订单']);
		}
		
		AppTrackerApiHelper::actionLog("Oms-aliexpress", "/order/aliexpress/edit-page");
		if (!isset($_GET['orderid'])){
			return $this->render('//errorview',['title'=>'编辑订单','message'=>'链接有误']);
		}
		$order = OdOrder::findOne($_GET['orderid']);
		if (empty($order)||$order->isNewRecord){
			return $this->render('//errorview',['title'=>'编辑订单','message'=>'未找到相应订单']);
		}
		$orderShipped = OdOrderShipped::find()->where(['order_id'=>$_GET['orderid']])->asArray()->all();
		return $this->render('edit',['order'=>$order , 'ordershipped'=>$orderShipped]);
	}
	
	
	
	/**
	 * 订单的手动选择检测
	 * @author million
	 */
	public function actionCheckorderstatus(){
		if (\Yii::$app->request->isPost){
			$orderids = explode(',',$_POST['orders']);
			Helper_Array::removeEmpty($orderids);
			if (count($orderids)>0){
				try {
					foreach ($orderids as $orderid){
						$order = OdOrder::findOne($orderid);
						if ($order->order_status=='200'){
							
							if (!empty($_REQUEST['refresh_force']) && $_REQUEST['refresh_force'] =='true'){
								$isreset = 1;
							}else{
								$isreset = 0;
							}
							$order->checkorderstatus(NULL,$isreset);
							$order->save(false);
						}
					}
					return '操作已完成';
				}catch (\Exception $e){
					return $e->getMessage();
				}
			}else{
				return '选择的订单有问题';
			}
		}
	}
	
		/**
	 * 订单合并
	 * @author million
	 */
	public function actionMergeorder(){
		if (\Yii::$app->request->isPost){
			AppTrackerApiHelper::actionLog("Oms-aliexpress", "/order/aliexpress/mergeorder");
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
	
	/**
	 * 订单拆分
	 * @author million
	 */
	public function actionSplitorder(){
		if(\Yii::$app->request->isPost){
			$oldorder = OdOrder::findOne($_POST['orderid']);
			$orderarr = $oldorder->attributes;
			unset($orderarr['order_id']);
			$orderarr['create_time']=time();
			$orderarr['update_time']=time();
			$neworder = new OdOrder();
			$neworder->setAttributes($orderarr);
			$neworder->subtotal=$_POST['new_subtotal'];
			$neworder->shipping_cost=$_POST['new_shipping_cost'];
			$neworder->grand_total=$_POST['new_grand_total'];
			if($neworder->save(false)){
				$oldorder->subtotal=$_POST['old_subtotal'];
				$oldorder->shipping_cost=$_POST['old_shipping_cost'];
				$oldorder->grand_total=$_POST['old_grand_total'];
				$oldorder->save(false);
				//将原订单的商品ID进行更换
				foreach ($_POST['item'] as $key=>$val){
					if ($val=='new'){
						$item = OdOrderItem::findOne($key);
						$item->order_id = $neworder->order_id;
						$item->save(false);
					}
				}
				OperationLogHelper::log('order', $neworder->order_id,'拆分订单','从原订单'.$oldorder->order_id.'拆分出该新订单',\Yii::$app->user->identity->getFullName());
				OperationLogHelper::log('order', $oldorder->order_id,'拆分订单','拆分该原订单,已生成新订单'.$neworder->order_id,\Yii::$app->user->identity->getFullName());
				return $this->render('//successview',['title'=>'拆分订单']);
			}else{
				return $this->render('//errorview',['title'=>'拆分订单','error'=>'新订单生成失败']);
			}
		}
		if (!isset($_GET['orderid'])){
			return $this->render('//errorview',['title'=>'拆分订单','error'=>'传入的订单ID有误']);
		}
		$order = OdOrder::findOne($_GET['orderid']);
		if (empty($order)||$order->isNewRecord){
			return $this->render('//errorview',['title'=>'拆分订单','error'=>'未找到相应订单']);
		}
		if (count($order->items)<2){
			return $this->render('//errorview',['title'=>'拆分订单','error'=>'拆封订单必须超过2件商品']);
		}
		return $this->render('splitorder',['order'=>$order]);
	}
	
	/**
	 * 提交发货，将已付款的订单转为待发货订单
	 * @author million
	 */
	function actionSignwaitsend(){
		if (\Yii::$app->request->isPost){
			$orderids = explode(',',$_POST['orders']);
			Helper_Array::removeEmpty($orderids);
			if (count($orderids)>0){
				try {
					foreach ($orderids as $orderid){
						$order = OdOrder::findOne($orderid);
						if ($order->order_status==OdOrder::STATUS_PAY){
							$order->order_status=OdOrder::STATUS_WAITSEND;
							$order->save();
							OperationLogHelper::log('order',$orderid,'修改订单','修改订单状态:已付款至待发货',\Yii::$app->user->identity->getFullName());
						}
					}
					return '操作已完成';
				}catch (\Exception $e){
					return $e->getMessage();
				}
			}else{
				return '选择的订单有问题';
			}
		}
	}
	/**
	 * 获取订单的自定义标签页面
	 * @author million
	 */
	function actionGetOneTagInfo(){
		$tagdata = [];
		if (!empty($_REQUEST['order_id'])){
			$tagdata = OrderTagHelper::getALlTagDataByOrderId($_REQUEST['order_id']);
		}
		exit(json_encode($tagdata));
	}
	
	/**
	 * 保存某个自定义的标签
	 * @author million
	 */
	function actionSaveOneTag(){
		if (!empty($_REQUEST['order_id'])){
			$order_id = $_REQUEST['order_id'];
		}else{
			exit(json_encode(['success'=>false, 'message'=>'未知错误1']));
		}
	
		if (!empty($_REQUEST['tag_name'])){
			$tag_name = $_REQUEST['tag_name'];
		}else{
			exit(json_encode(['success'=>false, 'message'=>'未知错误2']));
		}
	
		if (!empty($_REQUEST['operation'])){
			$operation = $_REQUEST['operation'];
		}else{
			exit(json_encode(['success'=>false, 'message'=>'未知错误3']));
		}
	
		if (!empty($_REQUEST['color'])){
			$color = $_REQUEST['color'];
		}else{
			exit(json_encode(['success'=>false, 'message'=>'未知错误4']));
		}
	
		$result = OrderTagHelper::saveOneOrderTag($order_id, $tag_name, $operation, $color);
		exit(json_encode($result));
	}
	
	/**
	 * 标签设置好后的更新
	 * @author million
	 */
	function actionUpdateOrderTrInfo(){
		if (!empty($_REQUEST['order_id'])){
			$row = OrderTagHelper::generateTagIconHtmlByOrderId($_REQUEST['order_id']);
			$sphtml['sphtml'] = $row;
			exit(json_encode($sphtml));
		}
	}

		/**
	 * 给买家留评价,用户可以选择一个已经存在的评价
	 * @author yuht
	 */
	public function actionFeedback(){
		if (\Yii::$app->request->getIsPost()){
			if (count($_POST['order_id'])){
				$odorders = OdOrder::find()->where(['in','order_id',$_POST['order_id']])->all();
				return $this->render('givefeedbackform',['orders'=>$odorders]);
			}else{
				return $this->render('//errorview',['title'=>'评价','error'=>'未发现选中订单']);
			}
		}else{

			if (!empty($_GET['order_id'])){
				$odorders = OdOrder::find()->where(['in','order_id',$_GET['order_id']])->all();
				return $this->renderPartial('givefeedbackform',['orders'=>$odorders]);
			}else{
				return $this->renderPartial('//errorview',['title'=>'评价','error'=>'未发现选中订单']);
			}
		}
	}

	/**
	 * Ajax发送Aliexpress评价的页面
	 * @author yuhettian
	 */
	public function actionAjaxFeedback(){


		if (\Yii::$app->request->isPost){
			$odorder = OdOrder::findOne($_POST['orderid']);
			if (empty($odorder)){return '找不到相应的订单';}
			$aliexpressuser = SaasAliexpressUser::find()->where('sellerloginid = :s',[':s'=>$odorder->selleruserid])->one();
			if (empty($aliexpressuser)){
				return '找不到订单所对应的Aliexpress账号';
			}
			
			//判断此速卖通账号信息是否v2版
			$is_aliexpress_v2 = AliexpressInterface_Helper_Qimen::CheckAliexpressV2($aliexpressuser->sellerloginid);
			if($is_aliexpress_v2){
				//调用API
				$api = new AliexpressInterface_Api_Qimen();
				$feedback = $_POST['feedbackval'];
				$score = $_POST['feedbacktype'];
				$order_id = $odorder -> order_source_order_id;
				$param = [
					'id' => $odorder->selleruserid,
					'param1' => json_encode([
						'order_id' => $order_id,
						'score' => $score,
						'feedback_content' => $feedback
					])
				];
				$ret = $api->saveSellerFeedback($param);
				
				\Yii::info("actionAjaxFeedback, order_id: $order_id, param: ".json_encode($param).", result: ".json_encode($ret), "file");
				
				$result = [
					'success' => $ret['result_success'],
					'errorCode' => $ret['error_code'],
					'errorMessage' => $ret['error_message'],
				];
			}
			else{
				//调用API
				$api = new AliexpressInterface_Api ();
				$access_token = $api->getAccessToken ($aliexpressuser -> sellerloginid);
				//获取访问token失败
				if ($access_token === false){
					//echo $selleruserid . 'not getting access token!' . "\n";
					//die;
					return $result['Errors']['LongMessage']='token获取失败！';
				}
				$api->access_token = $access_token;
				$feedback = $_POST['feedbackval'];
				$score = $_POST['feedbacktype'];
				$order_id = $odorder -> order_source_order_id;
				$param = array(
						'orderId' => $order_id,
						'score' => $score,
					    'feedbackContent' => $feedback
				);
				$result = $api->saveSellerFeedback($param);
			}
			
			if ($result['success'] == '1'){
				//如果标记成功，将成功的事件记录到OdOrder表中去
				$odorder->seller_commenttype=$score;
				$odorder->seller_commenttext=$feedback;
				if($odorder->save()){
					return 'success';
				}else{
					return '保存失败';
				};
			}elseif($result['success'] == ''){
				$code = array(
					'3002' => "此订单已评价！", 
					'2301' => "该订单不存在！",
					'1001' => "评价信息请填写完整！"
				);
				//根据错误代码进行评价
				if(isset($code[$result['errorCode']])){
					$source = $code[$result['errorCode']]; 
					if(!empty($source)){
						return $result['Errors']['LongMessage'] = $code[$result['errorCode']];
					}else{
						return $result['Errors']['LongMessage'] = "此订单评价失败";
					}
				}
				return $result['Errors']['LongMessage']= "此订单评价失败！";
			}elseif(isset($result['error_code'])){
				return $result['Errors']['LongMessage'] = "接口调用失败！";
			}else{
				return $result['Errors']['LongMessage'] = "失败！";
			}
		}
	}
	/*
	* 手动同步
	* @author yuhetian 2015/9/16
	*/
	public function actionMonualSync(){

		$uid = $_POST['sellerloginid'];
		$result = AliexpressHelper::getOrderListByManual($uid);
		echo json_encode(array('msg'=>$result));
		exit;
	}
	
	/**
	 +----------------------------------------------------------
	 * 新增一个常用筛选条件   action
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh 	2015/11/20				初始化
	 +----------------------------------------------------------
	 **/
	public function actionAppendCustomCondition(){
		
		$conditionList = ConfigHelper::getConfig("aliexpressorder/order_custom_condition");
		
		if (is_string($conditionList)){
			$conditionList = json_decode($conditionList,true);
		}
		
		if (empty($conditionList)) $conditionList = [];
		
		if (array_key_exists($_REQUEST['custom_name'],$conditionList)){
			exit(json_encode(['success'=>false , 'message'=>$_REQUEST['custom_name'].'已经存在，请不要重复添加！']));
		}else{
			$params = $_REQUEST;
			unset($params['custom_name']);
			unset($params['order_id']);
			unset($params['sel_custom_condition']);
			foreach($params as $key=>$value){
				if (!empty($value))
					$conditionList[$_REQUEST['custom_name']][$key] = $value;
			}
			//$conditionList[$_REQUEST['custom_name']] = $params;
		}
		
		ConfigHelper::setConfig("aliexpressorder/order_custom_condition", json_encode($conditionList));
		exit(json_encode(['success'=>true , 'message'=>'']));
	}//end of actionAppendCustomCondition
	
	
	/**
	 +----------------------------------------------------------
	 * 速卖通订单同步   action
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh 	2015/11/23				初始化
	 +----------------------------------------------------------
	 **/
	public function actionOrderSyncInfo(){
		
		if (isset($_REQUEST['sync_status'])){
			$status = $_REQUEST['sync_status'];
		}else{
			$status = "";
		}
		
		if (isset($_REQUEST['last_sync_time'])){
			$last_sync_time = $_REQUEST['last_sync_time'];
		}else{
			$last_sync_time = "";
		}
		
		$detail = AliexpressOrderHelper::getOrderSyncInfoDataList($status,$last_sync_time );
		
		if (!empty($_REQUEST['order_status']))
			$order_nav_key_word = $_REQUEST['order_status'];
		else
			$order_nav_key_word='';
		
		
		
		//sellerid
		//$selleruserids=Helper_Array::toHashmap(SaasAliexpressUser::find()->where(['uid'=>\Yii::$app->user->identity->getParentUid()])->select(['sellerloginid'])->asArray()->all(),'sellerloginid','sellerloginid');
		//只显示有权限的账号，lrq20170828
		$account_data = \eagle\modules\platform\apihelpers\PlatformAccountApi::getPlatformAuthorizeAccounts('aliexpress');
		$selleruserids = array();
		foreach($account_data as $key => $val){
			$selleruserids[$key] = $val;
		}
		foreach($detail as $key => $val){
			if(!array_key_exists($key, $selleruserids)){
				unset($detail[$key]);
			}
		}
		
		//订单统计数
		/*
		$tmpSellerIDList =  AliexpressAccountsApiHelper::listActiveAccounts(\Yii::$app->user->identity->getParentUid());
		$aliAccountList = [];
		foreach($tmpSellerIDList as $tmpSellerRow){
			$aliAccountList[] = $tmpSellerRow['sellerloginid'];
		}
		*/
		$counter = AliexpressOrderHelper::getMenuStatisticData(['selleruserid'=>$selleruserids]);
		
		return $this->renderAjax('order_sync',['sync_list'=>$detail,
				'counter'=>$counter,
				'selleruserids'=>$selleruserids,
				]);
	}//end of actionOrderSyncInfo
	
	/**
	 +----------------------------------------------------------
	 * 获取速卖通订单同步结果   action
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh 	2015/11/23				初始化
	 +----------------------------------------------------------
	 **/
	public function actionGetOneOrderSyncResult(){
		if (!empty($_REQUEST['order_id']) ){
			
			$result = AliexpressOrderHelper::getOneOrderSyncByOrderId($_REQUEST['order_id'], @$_REQUEST['account_key']);
			
			exit(json_encode(['success'=>true,'message'=>'','result'=>$result]));
		}else{
			exit(json_encode(['success'=>false,'message'=>TranslateHelper::t('参数不完整 ，请联系客服！')]));
		}
	}
	
	/**
	 +----------------------------------------------------------
	 * 删除重新同步订单
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh 	2014/05/06				初始化
	 +----------------------------------------------------------
	 **/
	public function actionRefreshorder(){
		if (\Yii::$app->request->isPost){
			$orderids = $_POST['orders'];
			Helper_Array::removeEmpty($orderids);
			$uid = \Yii::$app->user->identity->getParentUid();
			$error_message = "";
			if (count($orderids)>0){
				try {
					//\Yii::info("\n".(__FUNCTION__)." $uid refreshorder 1","file");
					foreach ($orderids as $order_id){
						//\Yii::info("\n".(__FUNCTION__)." $uid refreshorder 2:".$order_id,"file");
						$tmpRT = OrderHelper::requestRefreshOrderQueue($uid,  $order_id, 'aliexpress');
						//\Yii::info("\n".(__FUNCTION__)." $uid refreshorder 3:".json_encode($tmpRT),"file");
						if ($tmpRT['success']==false){
							//插入队列失败
							$error_message .= $order_id.':'.$tmpRT['message'];
						}else{
							//同步成功删除订单数据
							OdOrderItem::deleteAll(['order_id'=>$order_id]);
							OdOrder::deleteAll(['order_id'=>$order_id]);
						}
					}
		
					if (!empty($error_message)) return $error_message;
					return '操作已完成,请等待同步';
				}catch (\Exception $e){
					\Yii::info("\n".(__FUNCTION__)." $uid refreshorder E:".print_r($e->getMessage(),true),"file");
					return $e->getMessage();
				}
			}else{
				return '选择的订单有问题';
			}
		}//end of post
	}//end of actionRefreshorder
	
	
	/**
	 +----------------------------------------------------------
	 * 保存收件人信息
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh 	2014/05/06				初始化
	 +----------------------------------------------------------
	 **/
	public function actionSaveConsigneeInfo(){
		if (!empty($_REQUEST['order_id'])){
			$orderModel = OdOrder::find()->where(['order_id'=>$_REQUEST['order_id']])->One();
			
			if (empty($orderModel)) exit(json_encode(['success'=>false , 'message'=>'E002 找不到订单!']));
			
			$rt = OrderHelper::setOriginShipmentDetail($orderModel);
			if ($rt['success'] ==false){
				exit(json_encode(['success'=>false , 'message'=>'E003内部错误，请联系客服!']));
			}
			$_tmp = $_POST;
			unset($_tmp['order_id']);
			foreach($_tmp as $key=>$value){
				$orderModel->$key = $value;
			}
			
			if ($orderModel->save()){
				AppTrackerApiHelper::actionLog("Oms-aliexpress", "/order/aliexpress/edit-save");
				OperationLogHelper::log('order',$orderModel->order_id,'修改订单','编辑订单进行订单修改',\Yii::$app->user->identity->getFullName());
				exit(json_encode(['success'=>true , 'message'=>'']));
			}else{
				exit(json_encode(['success'=>false , 'message'=>'E004内部错误，请联系客服!'.json_encode($orderModel->errors)]));
			}
		}else{
			exit(json_encode(['success'=>false , 'message'=>'E001内部错误，请联系客服!']));
		}
	}//end of actionSaveConsigneeInfo
	
	/**
	 +----------------------------------------------------------
	 * 一键延长买家收货时间的界面
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh 	2016/01/23				初始化
	 +----------------------------------------------------------
	 **/
	public function actionShowExtendsBuyerAcceptGoodsTimeBox(){
		if (!empty($_REQUEST['orderIdList'])){
				
			if (is_array($_REQUEST['orderIdList'])){
				$orderList  = OdOrder::find()->where(['order_id'=>$_REQUEST['orderIdList']])->asArray()->all();
				return $this->renderPartial('_ExtendsBuyerAcceptGoodsTimeBox.php' , ['orderList'=>$orderList] );
			}else{
				return $this->renderPartial('//errorview','E001 内部错误， 请联系客服！');
			}
		}else{
			return $this->renderPartial('//errorview','没有选择订单！');
		}
	}//end of actionShowExtendsBuyerAcceptGoodsTimeBox
	
	/**
	 +----------------------------------------------------------
	 * 一键延长买家收货时间
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh 	2016/01/27				初始化
	 +----------------------------------------------------------
	 **/
	public function actionExtendsBuyerAcceptGoodsTime(){
		$ressult = ['success'=>true,'message'=>''];
		if (!empty($_REQUEST['extenddataList'])){
			foreach($_REQUEST['extenddataList'] as $row){
				$tmpRT = AliexpressOrderHelper::ExtendsBuyerAcceptGoodsTime($row['order_id'], $row['extendday']);
				
				if (empty($tmpRT['success'])){
					$errorMsg = "";
					//延长失败！
					$ressult['success'] = false;
					
					$ressult['message'] .= $row['order_id'].' ';
					
					if (!empty($tmpRT['memo']) ) $errorMsg .= $tmpRT['memo'].' ';
					if (!empty($tmpRT['message']) ) $errorMsg .= $tmpRT['message'].' ';
					
					if (empty($errorMsg)) $errorMsg = '网络异常，延长失败！';
					$ressult['message'] .= $errorMsg.'<br>';
				}else{
					$ressult['message'] .= $row['order_id'].' 成功延长！<br>';
				}
				
			}//end of each order 
			//$ressult['rt'] = $tmpRT;
			exit(json_encode($ressult));
		}else{
			exit(json_encode(['success'=>false ,'message'=>TranslateHelper::t('找不到订单信息')]));
		}
	}//end of actionExtendsBuyerAcceptGoodsTime


/**
 * 手动同步订单
 * @return [type] [description]
 */
function actionSyncOrderReady(){
	$puid =\Yii::$app->subdb->getCurrentPuid();
	// 店铺列表
	$accounts = SaasAliexpressUser::find()->where([
			'uid'=>$puid
	])->all();
	//只显示有权限的账号，lrq20170828
	$account_data = \eagle\modules\platform\apihelpers\PlatformAccountApi::getPlatformAuthorizeAccounts('aliexpress');
	foreach($accounts as $key => $val){
		if(!array_key_exists($val->sellerloginid, $account_data)){
			unset($accounts[$key]);
		}
	}
	
	AppTrackerApiHelper::actionLog("Oms-aliexpress", "/order/aliexpressorder/aliexpresslist");
	return $this->renderAuto('start-sync',[
			'accounts'=>$accounts
	]);
}
	
	/**
	 +----------------------------------------------------------
	 * 清除 左侧菜单 的缓存数据
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh 	2016/05/17				初始化
	 +----------------------------------------------------------
	 **/
	public function actionClearLeftMenuCache(){
		unset($_SESSION['ali_oms_left_menu']);
		//订单健康检查
		OdOrder::updateAll(['pay_order_type'=>OdOrder::PAY_PENDING],['order_status'=>OdOrder::STATUS_PAY ,'pay_order_type'=>null]);
	}
	
	
	/**
	 +----------------------------------------------------------
	 * 打开用户使用的dash-board
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh 	2016/05/17				初始化
	 +----------------------------------------------------------
	 **/
	public function actionUserDashBoard(){
		$uid = \Yii::$app->user->id;
		if (empty($uid))
			exit('请先登录!');
		
		if (!empty($_REQUEST['isrefresh'])){
			$isRefresh = true;
		}else{
			$isRefresh = false;
		}
		
		$cacheData = AliexpressOrderHelper::getOmsDashBoardData($uid,$isRefresh);
		
		$platform = 'aliexpress';
		$chartData['order_count'] = $cacheData['order_count']; 
		//$chartData['profit_count'] = CdiscountOrderInterface::getChartDataByUid_Profit($uid,10);//oms 利润统计 aliexpress 没有先屏蔽
		$advertData =  $cacheData['advertData'];// 获取OMS dashboard广告
		$AccountProblems = AliexpressAccountsApiHelper::getUserAccountProblems($uid); //账号信息统计
		$ret=$cacheData['reminderData'];
		$_SESSION['ali_oms_dash_board_last_time'] = time();//记录用户dash board 最后 一次访问时间
		
		list($platformUrl,$label)=AppApiHelper::getPlatformMenuData();
		
		return $this->renderAjax('_dash_board',[
				'chartData'=>$chartData,
				'advertData'=>$advertData,
				'AccountProblems'=>$AccountProblems,
				'ret'=>$ret,
				'platformUrl'=>$platformUrl,
				]);
	}
	
	/**
	 +----------------------------------------------------------
	 * 生成 dashboard 数据
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh 	2016/05/19				初始化
	 +----------------------------------------------------------
	 **/
	public function actionGenrateUserDashBoard(){
		$platform = 'aliexpress';
		$uid = \Yii::$app->user->id;
		if (empty($uid))
			exit('请先登录!');
		
		if (!empty($_REQUEST['isrefresh'])){
			$isRefresh = true;
		}else{
			$isRefresh = false;
		}
		$cacheData = AliexpressOrderHelper::getOmsDashBoardData($uid,$isRefresh);
	}
	
	/**
	 +----------------------------------------------------------
	 * 异步加载 left menu 数据
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh 	2016/05/25				初始化
	 * 				lzhl	2017/03/28				ver_2
	 +----------------------------------------------------------
	 **/
	public function actionLeftMenuAutoLoad(){
		/*
		//不显示 解绑的账号的订单 start
		$tmpSellerIDList =  AliexpressAccountsApiHelper::getAllAccounts(\Yii::$app->user->identity->getParentUid());
		$aliAccountList = [];
		foreach($tmpSellerIDList as $tmpSellerRow){
			$aliAccountList[] = $tmpSellerRow['sellerloginid'];
		}
		
		//订单数量统计
		if (isset($aliAccountList)){
			//不显示 解绑的账号的订单
			$counter = AliexpressOrderHelper::getMenuStatisticData(['selleruserid'=>$aliAccountList]);
		}else{
			$counter = AliexpressOrderHelper::getMenuStatisticData();
		}
		*/
		$puid = \Yii::$app->subdb->getCurrentPuid();
		$uid = \Yii::$app->user->id;
		$hitCache = "NoHit";
		$cachedArr = array();
		$stroe = 'all';
		if(!empty($_REQUEST['selleruserid']))
			$stroe  = trim($_REQUEST['selleruserid']);
		 
		$isParent = \eagle\modules\permission\apihelpers\UserApiHelper::isMainAccount();
		if($isParent){
			$gotCache = RedisHelper::getOrderCache2($puid,$uid,'aliexpress',"MenuStatisticData",$stroe) ;
		}else{
			if (!empty($_REQUEST['selleruserid'])){
				$gotCache = RedisHelper::getOrderCache2($puid,$uid,'aliexpress',"MenuStatisticData",$_REQUEST['selleruserid']) ;
			}else{
				$gotCache = RedisHelper::getOrderCache2($puid,$uid,'aliexpress',"MenuStatisticData",'all') ;
			}
		}
		if (!empty($gotCache)){
			$cachedArr = is_string($gotCache)?json_decode($gotCache,true):$gotCache;
			$counter = $cachedArr;
			$hitCache= "Hit";
		}
		
		if ($hitCache <>"Hit"){
			if($stroe!=='all'){
				$counter = AliexpressOrderHelper::getMenuStatisticData(['selleruserid'=>$stroe]);
			}else {
				$AccountList = PlatformAccountApi::getPlatformAuthorizeAccounts('aliexpress');
				$counter = AliexpressOrderHelper::getMenuStatisticData(['selleruserid'=>$AccountList]);
			}
		
			//save the redis cache for next time use
			RedisHelper::setOrderCache2($puid,$uid,'aliexpress',"MenuStatisticData",$stroe,$counter) ;
		}
		 
		return json_encode($counter);
	}

}

?>