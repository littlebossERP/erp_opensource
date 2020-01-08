<?php

namespace eagle\modules\order\controllers;

use eagle\modules\order\helpers\OrderHelper;
use eagle\modules\tracking\helpers\CarrierTypeOfTrackNumber;
use eagle\modules\carrier\helpers\CarrierOpenHelper;
use eagle\modules\carrier\apihelpers\CarrierApiHelper;
use eagle\modules\tracking\models\Tracking;
use eagle\modules\order\models\OdOrder;
use eagle\modules\order\helpers\EbayOrderHelper;
use eagle\modules\inventory\helpers\InventoryApiHelper;
use eagle\modules\order\helpers\OrderTagHelper;
use eagle\modules\order\models\Excelmodel;
use eagle\modules\order\helpers\OrderBackgroundHelper;
use eagle\modules\delivery\helpers\DeliveryHelper;
use eagle\modules\util\helpers\TranslateHelper;
use yii\helpers\Url;
use eagle\modules\platform\apihelpers\EbayAccountsApiHelper;
use eagle\modules\order\models\OdEbayOrder;
use eagle\modules\carrier\helpers\CarrierHelper;
use eagle\modules\app\apihelpers\AppTrackerApiHelper;
use eagle\modules\order\models\EbayFeedbackTemplate;
use common\helpers\Helper_Array;
use common\api\ebayinterface\completesale;
use eagle\models\SaasEbayUser;
use eagle\modules\order\models\OdEbayTransaction;
use eagle\modules\util\helpers\OperationLogHelper;
use eagle\models\sys\SysCountry;
use eagle\models\EbayCountry;
use eagle\models\SaasEbayAutosyncstatus;
use eagle\modules\order\model\OdPaypalTransaction;
use eagle\modules\util\helpers\StandardConst;
use eagle\modules\carrier\models\SysShippingService;
use eagle\modules\order\models\OdOrderItem;
use eagle\modules\order\apihelpers\OrderApiHelper;
use common\api\ebayinterface\adddispute;
use eagle\modules\order\helpers\OrderGetDataHelper;
use eagle\modules\order\helpers\OrderUpdateHelper;
use eagle\modules\util\helpers\ConfigHelper;
use eagle\modules\util\helpers\SysLogHelper;
use eagle\modules\util\helpers\RedisHelper;
use eagle\modules\platform\apihelpers\PlatformAccountApi;
use eagle\models\db_queue2\EbayItemPhotoQueue;
use eagle\modules\dash_board\apihelpers\DashBoardStatisticHelper;
use eagle\modules\dash_board\helpers\DashBoardHelper;
use eagle\models\QueueGetorder;
use eagle\modules\order\helpers\OrderListV3Helper;

class EbayOrderController extends \eagle\components\Controller
{
	public $enableCsrfValidation = false;
	
	/**
	 +----------------------------------------------------------
	 * ebay oms 展示页面
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh 	2016/07/21				初始化
	 +----------------------------------------------------------
	 **/
    public function actionList()
    {	
    	//check模块权限
    	$permission = \eagle\modules\permission\apihelpers\UserApiHelper::checkPlatformPermission('ebay');
    	if(!$permission)
    		return $this->render('//errorview_no_close',['title'=>'访问受限:没有权限','error'=>'您还没有订单模块的权限!']);
    	
    	global $hitCache;
		$puid = \Yii::$app->subdb->getCurrentPuid();
    	AppTrackerApiHelper::actionLog("Oms-ebay", "/order/listebay");
    	$like_params = [];
    	$eq_params = [];
    	$in_params = [];
    	$other_params =[];
    	$date_params = [];
    	$sort = ''; //排序的字段
    	$order = '';//排序的字段 升降序
    	$addi_condition = [];
    	
    	
    	//默认打开的列表记录数为上次用户选择的page size 数	//lzhl	2016-11-30
    	$page_url = $page_url = '/'.\Yii::$app->controller->module->id.'/'.\Yii::$app->controller->id.'/'.\Yii::$app->controller->action->id;
    	$last_page_size = ConfigHelper::getPageLastOpenedSize($page_url);
    	if(empty($last_page_size))
    		$last_page_size = 50;//默认显示值
    	if(empty($_REQUEST['per-page']) && empty($_REQUEST['page']))
			$pageSize = $last_page_size;
		else{
			$pageSize = empty($_REQUEST['per-page'])?50:$_REQUEST['per-page'];
		}
    	ConfigHelper::setPageLastOpenedSize($page_url, $pageSize);
    	/*
    	if (!empty($_REQUEST['per-page'])){
    		$pageSize = $_REQUEST['per-page'];
    	}else{
    		$pageSize = 50; //分页
    	}
    	*/
    	$showItem ='all'; //ebay 是否展示所有商品
    	$showsearch = ''; //是否显示高级搜索 ''为不显示 'in'为显示
    	$op_code = '';
    	
    	$tmpSellerIDList =  EbayAccountsApiHelper::helpList('expiration_time' , 'desc');
    	$selleruserids = [];
    	foreach($tmpSellerIDList as $tmpSellerRow){
    		$selleruserids[$tmpSellerRow['selleruserid']] = $tmpSellerRow['selleruserid'];
    	}
    	
    	$usersEbayAuthorizeAccounts = PlatformAccountApi::getPlatformAuthorizeAccounts('ebay');
    	if(!empty($usersEbayAuthorizeAccounts))
    		$usersEbayAuthorizeAccounts = array_keys($usersEbayAuthorizeAccounts);
    	
    	$tmpAuthorizeAccounts = [];
    	foreach ($selleruserids as $selleruserid=>$name){
    		if(in_array($selleruserid,$usersEbayAuthorizeAccounts)){
    			$tmpAuthorizeAccounts[$selleruserid] = $name;
    		}
    	}
    	if(empty($tmpAuthorizeAccounts)){
    		//如果为测试用的账号就不受平台绑定限制
    		$test_userid=\eagle\modules\tool\helpers\MirroringHelper::$test_userid;
    		if (!in_array($puid,$test_userid['yifeng'])){
    			return $this->render('//errorview_no_close',['title'=>'访问受限:没有权限','error'=>'您还没有获得任何 eBay 账号管理权限!']);
    		}
    	}
    	else {
    		$selleruserids = $tmpAuthorizeAccounts;
    		$addi_condition['selleruserid'] = $selleruserids;
    	}
    	//查询条件转换 end
    	$addi_condition['order_source'] = 'ebay';
    	if(!empty($_REQUEST['seller_commenttype'])){
    		$addi_condition['seller_commenttype'] = $_REQUEST['seller_commenttype'];
    		//unset($_REQUEST['seller_commenttype']);
    	}
    	
    	$addi_condition['sys_uid'] = \Yii::$app->user->id;
    	if(isset($addi_condition['selleruserid'])){
    		$addi_condition['selleruserid_tmp'] = $addi_condition['selleruserid'];
    	}
    	
    	//var_dump($pageSize);
    	$tmpQuery = null; //由于第三个参数为引用 ， 所以 必须分配一个地址，否则会报错
    	$tmp_REQUEST_text['where']=empty($tmpQuery->where)?Array():$tmpQuery->where;
    	$tmp_REQUEST_text['orderBy']=empty($tmpQuery->orderBy)?Array():$tmpQuery->orderBy;
    	//获取ebay 订单  数据
    	$models = OrderApiHelper::getOrderListByConditionOMS($_REQUEST ,$addi_condition,$tmpQuery,$pageSize,false,'all');
    	//$models = OrderHelper::getOrderListByCondition($like_params, $eq_params, $date_params, $in_params, $other_params, $sort, $order,$pageSize,false , $showItem);
    	$showsearch = $models['showsearch'];
    	//已付款订单类型
    	$PayOrderTypeList=Odorder::$payOrderType ;
    	$PayOrderTypeList[OdOrder::PAY_EXCEPTION] = '待合并';
    	
    	//订单状态
    	$orderStatus21 = OdOrder::getOrderStatus('oms21'); // oms 2.1 的订单状态
    	//操作栏
    	
    	
    	//导出excel
    	$excelmodel	=	new Excelmodel();
    	$model_sys	=	$excelmodel->find()->all();
    	 
    	$excelmodels=array(''=>'导出订单');
    	if(isset($model_sys)&&!empty($model_sys)){
    		foreach ($model_sys as $m){
    			$excelmodels[$m->id]=$m->name;
    		}
    	}
    	
    	//检查报关信息是否存在 start
    	
    	
    	//yzq 2017-2-21, to do bulk loading the order items, not to use lazy load
    	OrderHelper::bulkLoadOrderItemsToOrderModel($models['data']);
    	OrderHelper::bulkLoadOrderShippedModel($models['data']);
    	
    	$OrderIdList = [];
    	$existProductResult = OrderBackgroundHelper::getExistProductRuslt($models['data']);
    	//检查报关信息是否存在 end

    	$OrderSourceOrderIdList = [];
    	foreach($models['data'] as $order){
    		$OrderSourceOrderIdList[] = $order->order_source_order_id;
    	}
    	
    	//获取当前  check out 状态
    	$orderCheckOutList = [];
    	$orderCheckOutList = OrderApiHelper::getEbayCheckOutInfo($OrderSourceOrderIdList);
    	
    	 /*
		//这里添加对应状态下面的国家筛选
	    if (!empty($_REQUEST['order_status'])){
	    	$countrycode=OdOrder::getDb()->createCommand('select consignee_country_code from '.OdOrder::tableName().
	    			' where order_status = :os and order_source=:order_source group by consignee_country_code',[':os'=>$_REQUEST['order_status'],':order_source'=>'ebay'])->queryColumn();
	    }else{
	    	$countrycode=OdOrder::getDb()->createCommand('select consignee_country_code from '.OdOrder::tableName().' group by consignee_country_code')->queryColumn();
	    }
	    
	    $countrycode=array_filter($countrycode);
		$countrys=Helper_Array::toHashmap(EbayCountry::find()->where(['country'=>$countrycode])->orderBy('description asc')->select(['country','description'])->asArray()->all(),'country','description');
		 */
		
    	//use this function to performance tuning, it use redis
		if (isset($_REQUEST['order_status'])){
			$redis_order_status = $_REQUEST['order_status'];
		}else{
			$redis_order_status = '';
		}
    	$countrys = OrderHelper::getPlatformOrderCountries($puid , 'ebay',$selleruserids ,$redis_order_status);

		
		if (!empty($_REQUEST['order_status'])){
			$op_code = $_REQUEST['order_status'];
		}
		
		$tmp_is_show = true;
		if($op_code == ''){
			$tmp_is_show = \eagle\modules\order\helpers\OrderListV3Helper::getIsShowMenuAllOtherOperation();
		}
		
		//操作选项
		$doarr = OrderHelper::getCurrentOperationList($op_code,'b');
		
		$doarr_one = OrderHelper::getCurrentOperationList($op_code,'s');
		
		if (isset($_REQUEST['order_status']) && $_REQUEST['order_status'] == OdOrder::STATUS_NOPAY){
			$doarr +=['dispute'=>'催款取消eBay订单'];
			$doarr_one +=['dispute'=>'催款取消eBay订单'];
		}else{
			if($tmp_is_show == true){
				$doarr +=['givefeedback'=>'评价'];
				$doarr_one +=['givefeedback'=>'评价'];
			}
		}
		
		if (isset($_REQUEST['order_status']) && $_REQUEST['order_status'] == OdOrder::STATUS_SUSPEND){
			if (empty($doarr['reorder'])){
				$doarr +=['reorder'=>'重新发货'];
			}
			
			if (empty($doarr_one['reorder'])){
				$doarr_one +=['reorder'=>'重新发货'];
			}
		}
		
		if (isset($_REQUEST['order_status']) && $_REQUEST['order_status'] == OdOrder::STATUS_PAY){
			if (empty($doarr['orderverifypass'])){
				$doarr +=['orderverifypass'=>'标记为paypal地址已同步'];
			}
				
			if (empty($doarr_one['orderverifypass'])){
				$doarr_one +=['orderverifypass'=>'标记为paypal地址已同步'];
			}
			
		}
		
		if (empty($doarr['ebayUpdateItemPhoto'])){
			$doarr +=['ebayUpdateItemPhoto'=>'更新商品图片'];
		}
		
		if (empty($doarr_one['ebayUpdateItemPhoto'])){
			$doarr_one +=['ebayUpdateItemPhoto'=>'更新商品图片'];
		}
		
		//left menu selected
		$menu_active ="";
		
		if (@$_REQUEST['order_status']==OdOrder::STATUS_NOPAY && empty($_REQUEST['custom_condition_name'])){
			$menu_active = TranslateHelper::t('未付款');
		}
		if(@$_REQUEST['order_status']==OdOrder::STATUS_PAY && empty($_REQUEST['custom_condition_name'])){
			$menu_active=TranslateHelper::t('已付款');
		}
		if(@$_REQUEST['order_status']==OdOrder::STATUS_WAITSEND && empty($_REQUEST['custom_condition_name'])){
			$menu_active=TranslateHelper::t('发货中');
		}
		if(@$_REQUEST['order_status']==OdOrder::STATUS_SHIPPED && empty($_REQUEST['custom_condition_name'])){
			$menu_active=TranslateHelper::t('已完成');
		}
		if(@$_REQUEST['order_status']==OdOrder::STATUS_CANCEL && empty($_REQUEST['custom_condition_name'])){
			$menu_active=TranslateHelper::t('已取消');
		}
		if(@$_REQUEST['order_status']==OdOrder::STATUS_SUSPEND&& empty($_REQUEST['custom_condition_name'])){
			$menu_active=TranslateHelper::t('暂停发货');
		}
		if(@$_REQUEST['order_status']==OdOrder::STATUS_OUTOFSTOCK&& empty($_REQUEST['custom_condition_name'])){
			$menu_active=TranslateHelper::t('缺货');
		}
		if(@$_REQUEST['menu_select']== 'all'){
			$menu_active= TranslateHelper::t('所有订单');
		}
		
		//listing 站点前缀
		$siteUrl = \common\helpers\Helper_Siteinfo::getSiteViewUrl();
		$siteList = \common\helpers\Helper_Siteinfo::getSite();
		
		//ebay 账号检测
		$problemAccounts = \eagle\modules\platform\apihelpers\EbayAccountsApiHelper::getProblemAccount();
		//$problemAccounts=[];
		
		//tag 数据获取
		$allTagDataList = OrderTagHelper::getTagByTagID();
		$allTagList = [];
		foreach($allTagDataList as $tmpTag){
			$allTagList[$tmpTag['tag_id']] = $tmpTag['tag_name'];
		}
		
		$tmp_REQUEST_text['REQUEST']=$_REQUEST;
		$tmp_REQUEST_text['order_source']=$addi_condition;
		$tmp_REQUEST_text['params']=empty($models->params)?Array():$models->params;
		$tmp_REQUEST_text=base64_encode(json_encode($tmp_REQUEST_text));
		
		$platform = 'ebay';
		$SignShipWarningCount = DashBoardStatisticHelper::CounterGet($puid, DashBoardHelper::${'SIGNSHIPPED_ORDER_ERR_'.strtoupper($platform)});
		
		//获取ebay账号别名
		$selleruserids_new = EbayAccountsApiHelper::getEbayAliasAccount($selleruserids);

        return $this->renderAuto('list',array(
			'models' => $models['data'], // ebay 订单  数据
			'pages'=>$models['pagination'],
        	'non17Track' =>	CarrierTypeOfTrackNumber::getNon17TrackExpressCode(),//17 track相关数据
        	'selleruserids'=>$selleruserids, //ebay账号
        	'selleruserids_new'=>$selleruserids_new,
        	'showsearch'=>$showsearch, //是否显示高级搜索 0为不显示 1为显示
        	'carriersProviderList'=>CarrierOpenHelper::getOpenCarrierArr('2'), //物流商
        	'carriers'=>CarrierApiHelper::getShippingServices(), //运输服务
        	'TrackerStatusList'=>Tracking::getChineseStatus('',true), //物流跟踪状态
        	'orderStatus21'=>$orderStatus21,
        	'ebayCondition'=>EbayOrderHelper::$ebayCondition, //ebay 专用的查询条件
        	'PayOrderTypeList'=>$PayOrderTypeList,//已付款订单类型
        	'reorderTypeList'=>Odorder::$reorderType ,  //重新发货类型
        	'warehouses'=>InventoryApiHelper::getWarehouseIdNameMap(true) , //仓库
        	'orderEvaluation'=>OdOrder::$orderEvaluation, //订单评价
        	'OrderSysTagMapping'=>OrderTagHelper::$OrderSysTagMapping, // tag 数据
        	'doarr'=>$doarr,//批量操作选项
        	'doarr_one'=>$doarr_one,//单个操作选项
        	'excelmodels'=>$excelmodels, //导出excel
        	'existProductResult'=>$existProductResult, //是否存在商品
        	'menu'=>EbayOrderHelper::getLeftMenuTree(), //左侧菜单
        	'orderCheckOutList'=>$orderCheckOutList, // ebay结算数据
        	'carrier_step'=>CarrierHelper::$carrier_step,
        	'tag_class_list'=> OrderTagHelper::getTagColorMapping(), //自定义标签数据
        	'countrys'=>$countrys,
        	'menu_active'=>$menu_active,
        	'siteUrl'=>$siteUrl,
        	'siteList'=>$siteList,
        	'problemAccounts'=>$problemAccounts,
        	'all_tag_list'=>$allTagList,
			'search_condition'=>$tmp_REQUEST_text,
        	'search_count'=>$models['pagination']->totalCount,
        	'SignShipWarningCount'=>$SignShipWarningCount,
		));
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
     * 				hqw		2017/09/08				多店铺选择
     +----------------------------------------------------------
     **/
    public function actionLeftMenuAutoLoad(){
    	//global $hitCache;
    	$puid = \Yii::$app->subdb->getCurrentPuid();
    	$uid = \Yii::$app->user->id;
    	$hitCache = "NoHit";
    	$cachedArr = array();
    	$stroe = 'all';
    	
    	if(!empty($_REQUEST['selleruserid']))
    		$stroe  = trim($_REQUEST['selleruserid']);
    	
    	//用于记录查找的账号ID
    	$tmp_account_lists = array();
    	
//     	RedisHelper::RedisDelete('global_config','Order/ebay-list-menu-statistic-data-memery_1');
//     	$pathValuesArr=RedisHelper::RedisGetAll('global_config');
//     	print_r($pathValuesArr);

//     	RedisHelper::RedisDelete('OrderTempData', $puid."_".'ebay'."_".'MenuStatisticData');
//     	$redis_data = RedisHelper::RedisGet('OrderTempData', $puid."_".'ebay'."_".'MenuStatisticData' );
//     	print_r($redis_data);

		//当为组合条件时执行
    	if((strlen($stroe) > 8) && (substr($stroe, 0, 4) == 'com-') && (substr($stroe, -4) == '-com')){
    		$tmp_selleruserid_combined = $stroe;
    		$tmp_selleruserid_combined = substr($tmp_selleruserid_combined, 4);
    		$tmp_selleruserid_combined = substr($tmp_selleruserid_combined, 0, strlen($tmp_selleruserid_combined)-4);
    		
    		//获取当时组合时的账号ID
			$pcCombination = OrderListV3Helper::getPlatformCommonCombination(array('type'=>'oms','platform'=>'ebay','com_name'=>$tmp_selleruserid_combined), $uid);
    		
			if(count($pcCombination) > 0){
				$stroe = implode(",", $pcCombination);
				
				$tmp_pcCombination = array();
				$AccountList = PlatformAccountApi::getPlatformAuthorizeAccounts('ebay');
				
				//判断是否有权限查看该账号的订单，因为存在，一开始设置好组合后，再限制店铺权限
				foreach ($pcCombination as $pcCombination_K => $pcCombination_V){
					if(!isset($AccountList[$pcCombination_V])){
					}else{
						$tmp_pcCombination[$pcCombination_V] = $pcCombination_V;
					}
				}
				
				if(count($tmp_pcCombination) > 0){
					$tmp_account_lists = $tmp_pcCombination;
				}else{
					//不成功时直接查找xxxxx
					$tmp_account_lists[] = 'xxxxx';
				}
			}else{
				//不成功时直接查找xxxxx
				$tmp_account_lists[] = 'xxxxx';
			}
    	}else{
    		$tmp_account_lists = explode(',', $stroe);
    	}
    	
    	$isParent = \eagle\modules\permission\apihelpers\UserApiHelper::isMainAccount();
    	if($isParent){
    		$gotCache = RedisHelper::getOrderCache2($puid,$uid,'ebay',"MenuStatisticData",$stroe) ;
    	}else{
    		if (!empty($_REQUEST['selleruserid'])){
    			$gotCache = RedisHelper::getOrderCache2($puid,$uid,'ebay',"MenuStatisticData",$_REQUEST['selleruserid']) ;
    		}else{
    			$gotCache = RedisHelper::getOrderCache2($puid,$uid,'ebay',"MenuStatisticData",'all') ;
    		}
    	}
    	
    	if (!empty($gotCache)){
    		$cachedArr = is_string($gotCache)?json_decode($gotCache,true):$gotCache;
    		$counter = $cachedArr;
    		$hitCache= "Hit";
    	}
    	 
    	if ($hitCache <>"Hit"){
	    	if($stroe!=='all'){
	    		$counter = EbayOrderHelper::getMenuStatisticData(['selleruserid'=>$tmp_account_lists]);
	    	}else {
	    		$AccountList = PlatformAccountApi::getPlatformAuthorizeAccounts('ebay');
	    		$counter = EbayOrderHelper::getMenuStatisticData(['selleruserid'=>$AccountList]);
	    	}
	    	
	    	//save the redis cache for next time use
	    	RedisHelper::setOrderCache2($puid,$uid,'ebay',"MenuStatisticData",$stroe,$counter) ;
    	}
    	
    	return json_encode($counter); 
    }
    
    /**
     * 给买家留评价,用户可以选择一个已经存在的评价模板
     * @author fanjs
     */
    public function actionFeedback(){
    	AppTrackerApiHelper::actionLog("Oms-ebay", "/order/feedback");
    	if (count($_REQUEST['order_id'])){
			$feedbacks = EbayFeedbackTemplate::find()->where('template_type = '.EbayFeedbackTemplate::Positive)->select(['id','template'])->asArray()->all();
			$feedbacks = Helper_Array::toHashmap($feedbacks, 'id','template');
			$odorders = OdOrder::find()->where(['in','order_id',$_REQUEST['order_id']])->all();
			return $this->renderPartial('givefeedbackform',['orders'=>$odorders,'feedbacks'=>$feedbacks]);
    	}else{
    		return $this->renderPartial('//errorview',['title'=>'评价','error'=>'未发现选中订单']);
    	}
    }
    
    
    /**
     * Ajax发送eBay评价的页面
     * @author fanjs
     */
    public function actionAjaxFeedback(){
    	if (\Yii::$app->request->isPost){
    		$odorder = OdOrder::findOne($_POST['orderid']);
    		if (empty($odorder)){return '找不到相应的订单';}
    		$odebayorder = OdEbayOrder::find()->where('ebay_orderid = :eo',[':eo'=>$odorder->order_source_order_id])->one();
    		$ebayuser = SaasEbayUser::find()->where('selleruserid = :s',[':s'=>$odorder->selleruserid])->one();
    		if (empty($ebayuser)){return '找不到订单所对应的eBay账号';}
    			
    		$api = new completesale();
    		$api->resetConfig($ebayuser->DevAcccountID); //授权配置
    		$api->eBayAuthToken = $ebayuser->token;
    		$api->setOrder($odebayorder);
    		$feedback = EbayFeedbackTemplate::findOne($_POST['feedbackval']);
    		//临时先用good idea充当评价的内容，需要建立评价范本供选择
    		$result = $api->feedback($_POST['feedbacktype'],$feedback->template);
    		if (!$api->responseIsFailure()){
    			//如果标记成功，将成功的事件记录到OdOrder表中去
    			$odorder->seller_commenttype=$_POST['feedbacktype'];
    			$odorder->seller_commenttext=$feedback->template;
    			if($odorder->save()){
    				return 'success';
    			}else{
    				return '保存失败';
    			};
    		}else{
    			//如果失败且报错信息为已经标记过，将OdOrder中的评价字段进行更新
    			if ($result['Errors']['ErrorCode']=='55'){
    				$odorder->seller_commenttype=$_POST['feedbacktype'];
    				$odorder->seller_commenttext=$feedback->template;
    				$odorder->save();
    			}
    			return $result['Errors']['LongMessage'];
    		}
    	}
    }
    
    /**
     * 针对未支付的订单,发起退款或催款请求
     * @author fanjs
     */
    public function actionDispute(){
    	AppTrackerApiHelper::actionLog("Oms-ebay", "/order/dispute");
    	if (\Yii::$app->request->getIsPost()){
    		$orderids = $_POST['order_id'];
    	}else{
    		$orderids = [];
    		$orderids[]= $_GET['order_id'];
    	}
    		
    	if (count($orderids)){
    		$odorders = OdOrder::find()->where(['in','order_id',$orderids])->all();
    		$reason = [
    		'BuyerHasNotPaid'=>'BuyerHasNotPaid',
    		'TransactionMutuallyCanceled'=>'TransactionMutuallyCanceled'
    				];
    		$explanation = [
    		'BuyerHasNotPaid'=>[
    		'BuyerHasNotResponded'=>'BuyerHasNotResponded',
    				'BuyerNoLongerRegistered'=>'BuyerNoLongerRegistered',
    						'BuyerNotClearedToPay'=>'BuyerNotClearedToPay',
    								'BuyerRefusedToPay'=>'BuyerRefusedToPay',
    										'BuyerNoLongerWantsItem'=>'BuyerNoLongerWantsItem',
    										'PaymentMethodNotSupported'=>'PaymentMethodNotSupported',
    										'ShipCountryNotSupported'=>'ShipCountryNotSupported',
    										'ShippingAddressNotConfirmed'=>'ShippingAddressNotConfirmed',
    										'OtherExplanation'=>'OtherExplanation',
    										],
    										'TransactionMutuallyCanceled'=>[
    										'BuyerPurchasingMistake'=>'BuyerPurchasingMistake',
    										'BuyerReturnedItemForRefund'=>'BuyerReturnedItemForRefund',
						'UnableToResolveTerms'=>'UnableToResolveTerms',
						'BuyerNoLongerWantsItem'=>'BuyerNoLongerWantsItem',
						'PaymentMethodNotSupported'=>'PaymentMethodNotSupported',
						'ShipCountryNotSupported'=>'ShipCountryNotSupported',
						'ShippingAddressNotConfirmed'=>'ShippingAddressNotConfirmed',
						'OtherExplanation'=>'OtherExplanation',
    						],
    		];
    		return $this->render('senddisputeform',['orders'=>$odorders,'reason'=>$reason,'explan'=>$explanation]);
    }else{
    	return $this->render('//errorview',['title'=>'eBay催款或取消订单','error'=>'未发现选中订单']);
    }
    }
    
    
    /**
     * ajax处理催款或取消订单的纠纷
     * @author fanjs
     */
    public function actionAjaxDispute(){
    	if (\Yii::$app->request->isPost){
    		$odorder = OdOrder::findOne($_POST['orderid']);
    		if (empty($odorder)){return '找不到相应的订单';}
    		$odebayorder = OdEbayOrder::find()->where('ebay_orderid = '.$odorder->order_source_order_id)->one();
    		$ebayuser = SaasEbayUser::find()->where('selleruserid = :s',[':s'=>$odorder->selleruserid])->one();
    		if (empty($ebayuser)){return '找不到订单所对应的eBay账号';}
    		$transactions = OdEbayTransaction::find()->where('order_id ='.$odorder->order_id)->all();
    		if (count($transactions)>1){
    			return '该订单不支持接口调用,eBay接口仅支持单商品订单';
    		}
    		$transaction = $transactions[0];
    		
    		$api = new adddispute();
    		$api->resetConfig($ebayuser->DevAcccountID); //授权配置
    		$api->eBayAuthToken = $ebayuser->token;
    		$api->setItemAndTransaction ( $transaction->itemid, $transaction->transactionid );
    		try {
    			$result = $api->add ($_POST['reason'],$_POST['explanation'] );
    		} catch ( \Exception $ex ) {
    			return print_r($ex->getMessage());
    		}
    		// 接口反馈成功
    		if (! $api->responseIsFailure ()) {
    			$odorder->status_dispute = 1;
    			if($odorder->save ()){
    				return 'success';
    			}else{
    				return '接口呼叫成功,状态保存失败';
    			};
    		}else{
    			if (isset($result['Errors']['LongMessage'])){
    				return $result['Errors']['LongMessage'];
    			}else{
    				return $result['Errors']['0']['LongMessage'];
    			}
    		}
    	}
    }
    
    /**
     * 手动同步ebay订单,队列优先度提前
     * @author fanjs
     */
    function actionSyncmt(){
    	$ebayuser = SaasEbayUser::find()->where(['uid'=>[\Yii::$app->user->id,\Yii::$app->user->identity->getParentUid()]])->select('selleruserid')->asArray()->all();
    	$ebayuser = Helper_Array::getCols($ebayuser,'selleruserid');
    	$sync = SaasEbayAutosyncstatus::find()->where(['selleruserid'=>$ebayuser,'type'=>'1'])->all();
    	return $this->renderPartial('syncmt',['sync'=>$sync]);
    }
    
    /**
     * 将订单标记为已付款
     * @author fanjs
     */
    public function actionSignpayed(){
    	AppTrackerApiHelper::actionLog("Oms-ebay", "/order/signpayed");
    	if (\Yii::$app->request->isPost){
    		$orderids = explode(',',$_POST['orders']);
    		Helper_Array::removeEmpty($orderids);
    		if (count($orderids)>0){
    			try {
    				foreach ($orderids as $orderid){
    					$order = OdOrder::findOne($orderid);
    					if ($order->order_status<200){
    						$odebayorder = OdEbayOrder::find()->where('ebay_orderid = :eo',[':eo'=>$order->order_source_order_id])->one();
    						$api = new completesale();
    						$ebayuser = SaasEbayUser::find()->where('selleruserid = :s',[':s'=>$order->selleruserid])->one();
    						$api->resetConfig($ebayuser->DevAcccountID); //授权配置
    						$api->eBayAuthToken = $ebayuser->token;
    						$api->setOrder($odebayorder);
    						$result = $api->paid ();
    
    						if ($api->responseIsFailure ()) {
    							return $result['Errors']['LongMessage'];
    						}
    						$order->order_status = 200;
    						$order->paid_time = time();
    						$order->pay_order_type = OdOrder::PAY_PENDING;
    						$order->save();
    						OperationLogHelper::log('order',$orderid,'修改订单','修改订单状态:未付款至已付款',\Yii::$app->user->identity->getFullName());
    					}
    				}
    				//left menu 清除redis
    				OrderHelper::clearLeftMenuCache('ebay');
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
     * 订单编辑
     * @author fanjs
     */
    public function actionEdit(){
    	AppTrackerApiHelper::actionLog("Oms-ebay", "/order/edit");
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
			unset($_tmp['statushide']);
    		$addtionLog = '';
    		if (!empty($_tmp['default_shipping_method_code'])){
    			$serviceid = SysShippingService::findOne($_tmp['default_shipping_method_code']);
    			if (!empty($serviceid)||!$serviceid->isNewRecord){
    				$_tmp['default_shipping_method_code']=$_tmp['default_shipping_method_code'];
    				$_tmp['default_carrier_code']=$serviceid->carrier_code;
    			}
    		}
    		
    		$action = '修改订单';
    		$module = 'order';
    		$fullName = \Yii::$app->user->identity->getFullName();
    		$rt = OrderUpdateHelper::updateOrder($order, $_tmp , false , $fullName , $action , $module);
    		
    		$old_status = $order->order_status;
    		$order->setAttributes($_tmp);
    		$new_status = $order->order_status;
    		/*20160928 start
    		foreach($_tmp as $key=>$value){
    			if ($order->$key !=$value ){
    				$addtionLog .=' '.$key.":".$order->$key."=>".$value;
    			}
    		}
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
    			$item->ordered_quantity = $item_tmp['quantity'][$key];
    			$item->quantity = $item_tmp['quantity'][$key];
    			$item->order_source_srn =  empty($item_tmp['order_source_srn'][$key])?$order->order_source_srn:$item_tmp['order_source_srn'][$key];
    			$item->price = $item_tmp['price'][$key];
    			$item->update_time = time();
    			$item->create_time = is_null($item->create_time)?time():$item->create_time;
    			if ($item->save()){
    				if ($OriginQty != $item_tmp['quantity'][$key]){
    					list($ack , $message , $code , $rootSKU ) = array_values(OrderGetDataHelper::getRootSKUByOrderItem($item,true , $order->order_source , $order->selleruserid));
    					
    					list($ack , $code , $message  )  = array_values( OrderBackgroundHelper::updateUnshippedQtyOMS($rootSKU, $order->default_warehouse_id, $order->default_warehouse_id, $OriginQty, $item_tmp['quantity'][$key]));
    					if ($ack){
    						$addtionLog .= "$rootSKU $OriginQty=>".$item_tmp['quantity'][$key];
    					}
    				}
    			}
    		}
    		$order->checkorderstatus();
    		
    		//处理weird_status liang 2015-12-26
    		if($old_status!==$new_status && ($new_status!==500 ||$new_status!==600) ){
    			
    			if(!empty($order->weird_status))
    				$addtionLog .= ',并自动清除操作超时标签';
	    			$order->weird_status = '';
	    		}//处理weird_status end
    		$order->save();
    		if (!empty($addtionLog)){
    			OperationLogHelper::log('order',$order->order_id,'修改订单','编辑订单进行订单修改'.$addtionLog,\Yii::$app->user->identity->getFullName());
    		}
    		if(empty($_GET['is_delivery'])){
    			echo "<script language='javascript'>window.opener.location.reload();window.close();</script>";
    		}else{
    			echo "<script language='javascript'>window.opener.deliveryImplantOmsPublic();window.close();</script>";
    		}
    		//			return $this->render('//successview',['title'=>'编辑订单']);
    	}
    	if (!isset($_GET['orderid'])){
    		return $this->render('//errorview',['title'=>'编辑订单','message'=>'链接有误']);
    	}
    	$order = OdOrder::findOne($_GET['orderid']);
    	$paypal_t = OdPaypalTransaction::findOne(['order_id'=>$order->order_id]);
    	if (empty($order)||$order->isNewRecord){
    		return $this->render('//errorview',['title'=>'编辑订单','message'=>'未找到相应订单']);
    	}
    	return $this->render('edit',['order'=>$order,'paypal'=>$paypal_t,'countrys'=>StandardConst::$COUNTRIES_CODE_NAME_EN]);
    }
    
    /**
     * 即时发送站内信的操作
     * @author fanjs
     */
    function actionSendmessage(){
    	if (\Yii::$app->request->isPost){
    		AppTrackerApiHelper::actionLog("Oms-ebay", "/order/sendmessage");
    		if (empty($_POST['orderid'])){
    			return '未传输相应的议价ID';
    		}
    		$order = OdOrder::findOne(['order_id'=>$_POST['orderid']]);
    		return $this->renderPartial('sendmessage',['order'=>$order]);
    	}
    }
    
    /**
     +----------------------------------------------------------
     * 更新 ebay 无item 的订单 
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * log			name	date					note
     * @author		lkh 	2017/04/18				初始化
     +----------------------------------------------------------
     **/
    public function actionRetrieveOrderItem(){
    	try {
    		$puid = \Yii::$app->subdb->getCurrentPuid();
    		$tmp_total = EbayOrderHelper::retrieveOrderItem($puid);
    		exit("ack=success 操作成功");
    	} catch (\Exception $e) {
    		exit("ack=fail ".'更新失败! '.$e->getMessage()."! Error code:".$e->getLine());
    	}
    	
    }
    
    /**
     +----------------------------------------------------------
     * 更新ebay订单图片
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * log			name	date					note
     * @author		lkh 	2017/05/24				初始化
     +----------------------------------------------------------
     **/
    public function actionUpdateEbayPhoto(){
    	$total = 0;
    	if (!empty($_REQUEST['orderIDList'])){
    		$items = OdOrderItem::find()->where(['order_id'=>$_REQUEST['orderIDList']])->all();
    		$uid =  \Yii::$app->subdb->getCurrentPuid();
    		$action = "更新图片";
    		$fullName = \Yii::$app->user->identity->getFullName();
    		
    		foreach($items as $item){
    			if (empty($item->order_source_itemid)){
    				// 可能是手工订单， 跳过
    				continue;
    			}else{
    				//->andWhere(['<=','expire_time' ,date("Y-m-d H:i:s",time()) ])
//     				$ebayItemPhoto = EbayItemPhotoQueue::find()->where(['itemid'=>$item->order_source_itemid , 'product_attributes'=>$item->product_attributes , 'puid'=>$uid ])->one();
    			    $ebayItemPhoto = EbayItemPhotoQueue::find()->where(['itemid'=>$item->order_source_itemid , 'puid'=>$uid ])->andWhere(["like","product_attributes",$item->product_attributes])->one();
    				$photo = @$ebayItemPhoto->photo_url;
    			
    				if (!empty($photo) && $item->photo_primary !=$photo){
    					$log = $item->order_source_itemid." 图片由 ".$item->photo_primary." 改为 ".$photo;
    					$item->photo_primary = $photo;
    					if ($item->save()){
    						$total++;
    						OperationLogHelper::log('order',$item->order_id,$action,$log,$fullName);
    					}
    					
    				}
    			}
    			
    		}//end of foreach
    	}
    	exit(json_encode(['success'=>true,'effect'=>$total]));
    }//end of function actionUpdateEbayPhoto
    
    /**
     +----------------------------------------------------------
     * 手动同步订单
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * log			name	date					note
     * @author		lkh 	2017/07/07				初始化
     +----------------------------------------------------------
     **/
    public function actionSyncOrderReady(){
    	$puid =\Yii::$app->subdb->getCurrentPuid();
    	// 店铺列表
    	$accounts = SaasEbayUser::find()->where([
    			'uid'=>$puid
    			])->all();
    	//只显示有权限的账号，lrq20170828
    	$account_data = \eagle\modules\platform\apihelpers\PlatformAccountApi::getPlatformAuthorizeAccounts('ebay');
    	foreach($accounts as $key => $val){
    		if(!array_key_exists($val->selleruserid, $account_data)){
    			unset($accounts[$key]);
    		}
    	}
    	
    	AppTrackerApiHelper::actionLog("Oms-ebay", "/order/ebay-order/list");
    	return $this->renderAuto('start-sync',[
    			'accounts'=>$accounts
    			]);
    }
    /**
     +----------------------------------------------------------
     * 手动同步订单
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * log			name	date					note
     * @author		lkh 	2017/07/07				初始化
     +----------------------------------------------------------
     **/
    public function actionGetQueue(){
    	AppTrackerApiHelper::actionLog("Oms-ebay", "/order/ebay-order/get-queue");
    	$result = [
    	'success'=>true,
    	'status'=>'P',
    	'progress'=>0,
    	'message'=>'',
    	];
    	
    	if(empty($_REQUEST['site_id'])){
    		$result['success'] = false;
    		$result['message'] ='请选择指定店铺';
    		return $this->renderJson($result);
    	}
    		
    	$site_id = (int)$_REQUEST['site_id'];
    	$puid = \Yii::$app->user->identity->getParentUid();
    	
    	try{
    		$this_saas_account = SaasEbayUser::find()->where(['ebay_uid'=>$site_id,'uid'=>$puid])->one();
    		if(empty($this_saas_account)){
    			$result['success']=false;
    			$result['message'] ='店铺与用户不匹配，请正常操作';
    			return $this->renderJson($result);
    		}
    			
    		if(empty($_REQUEST['sync_order_time'])){
    			$result['success']=false;
    			$result['message'] ='请指定要同步的订单日期';
    			return $this->renderJson($result);
    		}
    		
    		$sync_order_time = trim($_REQUEST['sync_order_time']);
    		$is_date=strtotime($sync_order_time)?strtotime($sync_order_time):false;
    		
    		if($is_date == false){
    			$result['success']=false;
    			$result['message'] ='要同步的订单日期 格式错误';
    			return $this->renderJson($result);
    		}
    		$ModTimeFrom = $is_date;
    		$eu = $this_saas_account;
    		//$flag = \common\api\ebayinterface\getsellertransactions::cronInsertIntoQueueGetOrder($eu, 0, $ModTimeFrom, time(),0,8);
    		$selleruserid = $this_saas_account->selleruserid;
    		RedisHelper::RedisSet("EbayOrderData", "ebayManualSyncOrder_".$selleruserid , $ModTimeFrom);
    		$flag = true;
    		if($flag == false){
    			$journal_id = SysLogHelper::InvokeJrn_Create("OMS",__CLASS__, __FUNCTION__ , array('Exception',$rtn['message']));
    			$result['success']=false;
    			$result['message'] ='店铺同步状态设置失败,请联系客服';
    			return $this->renderJson($result);
    		}
    	}catch(\Exception $e){
    		$journal_id = SysLogHelper::InvokeJrn_Create("OMS",__CLASS__, __FUNCTION__ , array('Exception',$e->getMessage()));
    		$result['success']=false;
    		$result['message'] =$e->getMessage();
    		$result['code'] =$e->getCode();
    	}
    	
    	if($result['success'])
    		$journal_id = SysLogHelper::InvokeJrn_Create("OMS",__CLASS__, __FUNCTION__ , array('success',$puid,$site_id));
    	return $this->renderJson($result);
    }//end of function actionGetQueue
    
    /*
     * 查询同步进度
    */
    public function actionGetProgress(){
    	$result = [
    	'success'=>true,
    	'status'=>'P',
    	'progress'=>0,
    	'message'=>'',
    	];
    
    	if(empty($_REQUEST['site_id'])){
    		$result['success'] = false;
    		$result['message'] ='请选择指定店铺';
    		return $this->renderJson($result);
    	}
    	$site_id = (int)$_REQUEST['site_id'];
    	$puid = \Yii::$app->user->identity->getParentUid();
    
    	try{
    		$this_saas_account = SaasEbayUser::find()->where(['ebay_uid'=>$site_id,'uid'=>$puid])->one();
    		
    		if(empty($this_saas_account)){
    			$result['success']=false;
    			$result['message'] ='店铺与用户不匹配，请正常操作';
    			return $this->renderJson($result);
    		}
    		$selleruserid = $this_saas_account->selleruserid;
    		$ModTimeFrom = RedisHelper::RedisGet("EbayOrderData", "ebayManualSyncOrder_".$selleruserid );
    		if (!empty($ModTimeFrom)){
    			RedisHelper::RedisSet("EbayOrderData", "ebayManualSyncOrder_".$selleruserid , 0);
    			RedisHelper::RedisSet("EbayOrderData", "ebayManualSyncOrderProgress_".$selleruserid , "P");
    			// $flag = \common\api\ebayinterface\getsellertransactions::cronInsertIntoQueueGetOrder($this_saas_account, 0, $ModTimeFrom, time(),0,8);
    			// dzt20191107 改接口拉订单先在手工订单测试一下
    			$flag = \common\api\ebayinterface\getorders::cronGetOrderByTime($this_saas_account, 0, $ModTimeFrom, time(), 5);
    			if ($flag ==false){
    				RedisHelper::RedisSet("EbayOrderData", "ebayManualSyncOrderProgress_".$selleruserid , "F");
    			}else{
    				RedisHelper::RedisSet("EbayOrderData", "ebayManualSyncOrderProgress_".$selleruserid , "C");
    			}
    		}
    		
    		$progressStatus = RedisHelper::RedisGet("EbayOrderData", "ebayManualSyncOrderProgress_".$selleruserid );
    		//echo QueueGetorder::find()->where(['selleruserid'=>$selleruserid ])->andWhere(['status'=>8])->andWhere(['<','retry_count',10])->createCommand()->getRawSql();
//     		$rt = QueueGetorder::find()->where(['selleruserid'=>$selleruserid ])->andWhere(['status'=>8])->andWhere(['<','retry_count',10])->count();
//     		echo $rt;
            
    		// dzt20191107 注释
//     		if ($progressStatus == "S"){
//     			$rt = QueueGetorder::find()->where(['selleruserid'=>$selleruserid ])->andWhere(['status'=>8])->andWhere(['<','retry_count',10])->count();
//     			//echo $rt;
//     			if ($rt == 0 ){
//     				RedisHelper::RedisSet("EbayOrderData", "ebayManualSyncOrderProgress_".$selleruserid , "C");
//     			}
//     		}
    		
    		if (in_array($progressStatus , ['P',"S"])){
    			$result['success']=true;
    			$result['status']='P';
    			$result['progress']=0;
    			$result['message']='仍在同步中';
    		}elseif($progressStatus=='F'){
    			$result['success']=true;
    			$result['status']='F';
    			$result['progress']=0;
    			$result['message']='同步失败';
    		}elseif($progressStatus=='C'){
    			$result['success']=true;
    			$result['status']='C';
    			$result['message']='';
    		}
    	}catch(\Exception $e){
    		$journal_id = SysLogHelper::InvokeJrn_Create("OMS",__CLASS__, __FUNCTION__ , array('Exception',$e->getMessage()));
    		$result['success']=false;
    		$result['message'] =$e->getMessage();
    		$result['code'] =$e->getCode();
    			
    	}
    
    	return $this->renderJson($result);
    }
}
