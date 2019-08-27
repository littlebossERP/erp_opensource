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
use eagle\modules\app\apihelpers\AppTrackerApiHelper;
use eagle\modules\util\helpers\ConfigHelper;
use eagle\modules\util\helpers\TranslateHelper;
use eagle\models\sys\SysCountry;
use eagle\modules\inventory\helpers\WarehouseHelper;
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
use eagle\modules\order\helpers\ShopeeOrderHelper;
use eagle\models\SaasShopeeUser;

class ShopeeOrderController extends \eagle\components\Controller{
    public $enableCsrfValidation = false;
    
	public function actionList(){
		$puid = \Yii::$app->subdb->getCurrentPuid();
		//check模块权限
		$permission = \eagle\modules\permission\apihelpers\UserApiHelper::checkPlatformPermission('shopee');
		if(!$permission)
			return $this->render('//errorview_no_close',['title'=>'访问受限:没有权限','error'=>'您还没有订单模块的权限!']);
			
		AppTrackerApiHelper::actionLog("Oms-shopee", "/order/shopee/list");

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

		$data = OdOrder::find();
		$data->andWhere(['order_source'=>'shopee']);
		
		$showsearch=0;
		$op_code = '';
		
		$isParent = \eagle\modules\permission\apihelpers\UserApiHelper::isMainAccount();
		
		//不显示 解绑的账号的订单 start
		$tmpSellerIDList = PlatformAccountApi::getPlatformAuthorizeAccounts('shopee');//添加账号权限	lzhl 2017-03
		$shopeeAccountList = [];
		$selleruserids = [];
		
		$test_userid=\eagle\modules\tool\helpers\MirroringHelper::$test_userid;
		if (isset(\Yii::$app->params["currentEnv"]) and \Yii::$app->params["currentEnv"]=="test" ){
			$shopeeAccountList = [];
		}
		else if(in_array($puid,$test_userid['yifeng'])){
			//如果为测试用的账号就不受平台绑定限制
			$shopeeAccountList = [];
		}
		else{
			foreach($tmpSellerIDList as $shop_id => $store_name){
				$shopeeAccountList[] = $shop_id;
				$selleruserids[$shop_id] = $store_name;
			}
			if(empty($shopeeAccountList)){
				//无有效权限账号时
				return $this->render('//errorview_no_close',['title'=>'访问受限:没有权限','error'=>'您还没有获得任何 速卖通 账号管理权限!']);
			}
			
			//不显示 解绑的账号的订单
			$data->andWhere(['selleruserid' => $shopeeAccountList]);
		}
		
		$addi_condition = ['order_source' => 'shopee'];
		$addi_condition['sys_uid'] = \Yii::$app->user->id;
		$addi_condition['selleruserid_tmp'] = $shopeeAccountList;
		
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
	    
	    $excelmodel	= new Excelmodel();
	    $model_sys = $excelmodel->find()->all();
	    
	    $excelmodels=array(''=>'导出订单');
	    if(isset($model_sys)&&!empty($model_sys)){
	    	foreach ($model_sys as $m){
	    		$excelmodels[$m->id]=$m->name;
	    	}
	    }
	    
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
	    	$gotCache = RedisHelper::getOrderCache2($puid,$uid,'shopee',"MenuStatisticData",$stroe) ;
	    }else{
	    	if (!empty($_REQUEST['selleruserid'])){
	    		$gotCache = RedisHelper::getOrderCache2($puid,$uid,'shopee',"MenuStatisticData",$_REQUEST['selleruserid']) ;
	    	}else{
	    		$gotCache = RedisHelper::getOrderCache2($puid,$uid,'shopee',"MenuStatisticData",'all') ;
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
				$counter = OrderHelper::getMenuStatisticData('shopee', ['selleruserid'=>$_REQUEST['selleruserid']]);
			}else{
				if(!empty($shopeeAccountList)){
					$counter = OrderHelper::getMenuStatisticData('shopee', ['selleruserid'=>$shopeeAccountList]);
				}else{
					//无有效绑定账号
					$counter=[];
					$claimOrderIDs=[];
				}
			}
			//save the redis cache for next time use
			if (!empty($_REQUEST['selleruserid'])){
				RedisHelper::setOrderCache2($puid,$uid,'shopee', "MenuStatisticData",$_REQUEST['selleruserid'],$counter) ;
			}else{
				RedisHelper::setOrderCache2($puid,$uid,'shopee', "MenuStatisticData",'all',$counter) ;
			}
		}
	    
	    
	    $usertabs = Helper_Array::toHashmap(Helper_Array::toArray(Usertab::find()->all()),'id','tabname');
	    $warehouseids = InventoryApiHelper::getWarehouseIdNameMap();
	    
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
		$countryArr = OrderHelper::getPlatformOrderCountries($puid , 'shopee',$shopeeAccountList ,$redis_order_status);
		
		//检查报关信息是否存在 start
		$OrderIdList = [];
		$existProductResult = OrderBackgroundHelper::getExistProductRuslt($models);
		//检查报关信息是否存在 end
		
		$tmp_REQUEST_text['REQUEST']=$_REQUEST;
		$tmp_REQUEST_text['order_source']=$addi_condition;
		$tmp_REQUEST_text['params']=empty($data->params)?Array():$data->params;
		$tmp_REQUEST_text=base64_encode(json_encode($tmp_REQUEST_text));
		
		$platform = 'shopee';
		$SignShipWarningCount = DashBoardStatisticHelper::CounterGet($puid, DashBoardHelper::${'SIGNSHIPPED_ORDER_ERR_'.strtoupper($platform)});
		
		return $this->renderAuto('list',array(
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
			'doarr'=>ShopeeOrderHelper::getCurrentOperationList($op_code,'b') ,
			'doarr_one'=>ShopeeOrderHelper::getCurrentOperationList($op_code,'s'),
			'country_mapping'=>$country_list,
			'region'=>WarehouseHelper::countryRegionChName(),
			'search_condition'=>$tmp_REQUEST_text,
			'search_count'=>$pages->totalCount,
			'countryArr'=>$countryArr,
			'SignShipWarningCount'=>$SignShipWarningCount,
		));
		
	}
		
	 
/**
 * 手动同步订单
 * @return [type] [description]
 */
function actionSyncOrderReady(){
	$puid =\Yii::$app->subdb->getCurrentPuid();
	// 店铺列表
	$accounts = SaasShopeeUser::find()->where(['puid'=>$puid])->andWhere("status<>3")->all();
	//只显示有权限的账号，lrq20170828
	$account_data = \eagle\modules\platform\apihelpers\PlatformAccountApi::getPlatformAuthorizeAccounts('shopee');
	foreach($accounts as $key => $val){
		if(!array_key_exists($val->shop_id, $account_data)){
			unset($accounts[$key]);
		}
	}
	
	AppTrackerApiHelper::actionLog("Oms-shopee", "/order/shopee-sorder/list");
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
		unset($_SESSION['shopee_oms_left_menu']);
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
	 * 异步加载 left menu 数据
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * log			name	date			note
	 * @author		lrq 	2018/05/04		初始化
	 +----------------------------------------------------------
	 **/
	public function actionLeftMenuAutoLoad(){
		$puid = \Yii::$app->subdb->getCurrentPuid();
		$uid = \Yii::$app->user->id;
		$hitCache = "NoHit";
		$cachedArr = array();
		$stroe = 'all';
		if(!empty($_REQUEST['selleruserid']))
			$stroe  = trim($_REQUEST['selleruserid']);
		 
		$isParent = \eagle\modules\permission\apihelpers\UserApiHelper::isMainAccount();
		if($isParent){
			$gotCache = RedisHelper::getOrderCache2($puid,$uid,'shopee',"MenuStatisticData",$stroe) ;
		}else{
			if (!empty($_REQUEST['selleruserid'])){
				$gotCache = RedisHelper::getOrderCache2($puid,$uid,'shopee',"MenuStatisticData",$_REQUEST['selleruserid']) ;
			}else{
				$gotCache = RedisHelper::getOrderCache2($puid,$uid,'shopee',"MenuStatisticData",'all') ;
			}
		}
		if (!empty($gotCache)){
			$cachedArr = is_string($gotCache)?json_decode($gotCache,true):$gotCache;
			$counter = $cachedArr;
			$hitCache= "Hit";
		}
		
		if ($hitCache <>"Hit"){
			if($stroe!=='all'){
				$counter = ShopeeOrderHelper::getMenuStatisticData(['selleruserid'=>$stroe]);
			}else {
				$AccountList = PlatformAccountApi::getPlatformAuthorizeAccounts('shopee');
				if(!empty($AccountList)){
				    $AccountList = array_flip($AccountList);
				}
				$counter = ShopeeOrderHelper::getMenuStatisticData(['selleruserid'=>$AccountList]);
			}
		
			//save the redis cache for next time use
			RedisHelper::setOrderCache2($puid,$uid,'shopee',"MenuStatisticData",$stroe,$counter) ;
		}
		 
		return json_encode($counter);
	}

}

?>