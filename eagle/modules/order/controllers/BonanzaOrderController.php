<?php

namespace eagle\modules\order\controllers;

use yii\data\Pagination;
use yii\web\Controller;
use eagle\modules\order\models\OdOrder;
use eagle\modules\order\models\Excelmodel;

use common\helpers\Helper_Array;
use eagle\modules\order\models\OdOrderItem;
use eagle\modules\util\helpers\OperationLogHelper;
use eagle\modules\order\models\Usertab;
use Exception;
use eagle\modules\order\models\OdOrderShipped;
use eagle\models\carrier\SysShippingService;
use yii\data\Sort;
use eagle\models\SaasBonanzaUser;
use eagle\modules\inventory\models\Warehouse;
use eagle\modules\order\helpers\OrderTagHelper;
use eagle\modules\util\helpers\StandardConst;
use eagle\modules\order\helpers\BonanzaOrderInterface;
use eagle\modules\order\helpers\OrderHelper;
use eagle\models\SysCountry;
use eagle\models\EbayCountry;
use eagle\modules\listing\helpers\BonanzaOfferSyncHelper;
use eagle\modules\app\apihelpers\AppTrackerApiHelper;
use eagle\modules\order\helpers\BonanzaOrderHelper;
use eagle\modules\order\models\BonanzaOrder;
use eagle\modules\order\models\BonanzaOrderDetail;
use eagle\modules\app\apihelpers\AppApiHelper;
use eagle\modules\inventory\helpers\InventoryApiHelper;
use eagle\modules\inventory\helpers\WarehouseHelper;
use eagle\modules\order\helpers\OrderBackgroundHelper;
use eagle\modules\order\apihelpers\OrderApiHelper;
use eagle\modules\order\helpers\OrderGetDataHelper;
use eagle\modules\order\helpers\OrderUpdateHelper;
use eagle\modules\util\helpers\ConfigHelper;
use eagle\modules\platform\apihelpers\PlatformAccountApi;
use eagle\modules\util\helpers\RedisHelper;

class BonanzaOrderController extends \eagle\components\Controller{
	public $enableCsrfValidation = false;
	
	/**
	 * bonanza订单列表页面
	 * 
	 * 
	 */
	public function actionList(){
		//check模块权限
		$permission = \eagle\modules\permission\apihelpers\UserApiHelper::checkPlatformPermission('bonanza');
		if(!$permission)
			return $this->render('//errorview_no_close',['title'=>'访问受限:没有权限','error'=>'您还没有订单模块的权限!']);

	    //////自动检测订单
	    $pending_checks = OdOrder::find()->where(['order_source' => 'bonanza','order_status'=>OdOrder::STATUS_PAY])
	    ->andWhere("pay_order_type='pending' or pay_order_type is null")
	    //->andWhere("pay_order_type='pending' or pay_order_type is null or exception_status is not null")
	    ->all();
	    foreach ($pending_checks as $pending_check){
	        $pending_check->checkorderstatus(null,1);
	        $pending_check->save(false);
	    }
	    //////自动检测订单end
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
		
		$data = OdOrder::find()->where(['order_source' => 'bonanza' ]);
		$showsearch=0;
		$op_code = '';
		
		// 当前user 的puid 绑定的 bonanza 卖家账号
		$puid = \Yii::$app->user->identity->getParentUid();
		$isParent = \eagle\modules\permission\apihelpers\UserApiHelper::isMainAccount();
		$bonanzaUsers = PlatformAccountApi::getPlatformAuthorizeAccounts('bonanza');//添加账号权限	lzhl 2017-03
		//$bonanzaUsers = SaasBonanzaUser::find()->where(['uid'=>$puid])->asArray()->all();
		
		//$bonanzaUsersTokenList = array();//token
		$bonanzaUsersDropdownList = array();
		$bonanzaAccountList = [];//不显示 解绑的账号的订单 
		foreach ($bonanzaUsers as $bonanzaUser){
		    $bonanzaUsersDropdownList[$bonanzaUser] = $bonanzaUser;
		    //$bonanzaUsersTokenList[$bonanzaUser['store_name']] = $bonanzaUser['token'];
		    $bonanzaAccountList[] = $bonanzaUser;
		}
		
		if (!empty($bonanzaAccountList)){
		    //不显示 解绑的账号的订单
		    $data->andWhere(['selleruserid'=>$bonanzaAccountList]);
		}else {
			//如果为测试用的账号就不受平台绑定限制
			$test_userid=\eagle\modules\tool\helpers\MirroringHelper::$test_userid;
			if (!in_array($puid,$test_userid['yifeng'])){
				return $this->render('//errorview_no_close',['title'=>'访问受限:没有权限','error'=>'您还没有获得任何 Bonanza 账号管理权限!']);
			}
		}
		
		//bonanza有两个状态
		if (!empty($_REQUEST['order_source_status_checkoutStatus'])&&empty($_REQUEST['order_source_status_orderStatus'])){
		    $data->andWhere(["regexp","order_source_status",$_REQUEST['order_source_status_checkoutStatus'].","]);//模糊查询
			$showsearch=1;
// 			$data->andWhere(['order_source_status'=>$_REQUEST['fuhe']]);
		}else if(!empty($_REQUEST['order_source_status_orderStatus'])&&empty($_REQUEST['order_source_status_checkoutStatus'])){
		    $data->andWhere(["regexp","order_source_status",",".$_REQUEST['order_source_status_orderStatus']]);//模糊查询
		    $showsearch=1;
		}else if(!empty($_REQUEST['order_source_status_checkoutStatus'])&&!empty($_REQUEST['order_source_status_orderStatus'])){
		    $data->andWhere(['order_source_status'=>$_REQUEST['order_source_status_checkoutStatus'].",".$_REQUEST['order_source_status_orderStatus']]);
		    $showsearch=1;
		}
		

		$addi_condition = ['order_source'=>'bonanza'];
		$addi_condition['sys_uid'] = \Yii::$app->user->id;
		$addi_condition['selleruserid_tmp'] = $bonanzaUsersDropdownList;
		
		$tmp_REQUEST_text['where']=empty($data->where)?Array():$data->where;
		$tmp_REQUEST_text['orderBy']=empty($data->orderBy)?Array():$data->orderBy;
		$omsRT = OrderApiHelper::getOrderListByConditionOMS($_REQUEST,$addi_condition,$data,$pageSize,false,'all');
		if (!empty($_REQUEST['order_status'])){
		    //生成操作下拉菜单的code
		    $op_code = $_REQUEST['order_status'];
		}
		if (!empty($omsRT['showsearch'])) $showsearch = 1;
			
	    $pagination = new Pagination([
	    		'totalCount' => $data->count(),
	    		'params'=>$_REQUEST,
	    		'pageSize'=>$pageSize,
	    		'pageSizeLimit'=>[5,500],//每页显示条数范围
	    	]);
	    $models = $data->offset($pagination->offset)
	        ->limit($pagination->limit)
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
	    
	    $usertabs = Helper_Array::toHashmap(Helper_Array::toArray(Usertab::find()->all()),'id','tabname');
	    $warehouseids = InventoryApiHelper::getWarehouseIdNameMap();
	    //订单数量统计
	    
	    $hitCache = "NoHit";
	    $cachedArrAll = array();
	    $uid = \Yii::$app->user->id;
	    $stroe = 'all';
	    if(!empty($_REQUEST['selleruserid']))
	    	$stroe  = trim($_REQUEST['selleruserid']);
	     
	    if($isParent){
	    	$gotCache = RedisHelper::getOrderCache2($puid,$uid,'bonanza',"MenuStatisticData",$stroe) ;
	    }else{
	    	if (!empty($_REQUEST['selleruserid'])){
	    		$gotCache = RedisHelper::getOrderCache2($puid,$uid,'bonanza',"MenuStatisticData",$_REQUEST['selleruserid']) ;
	    	}else{
	    		$gotCache = RedisHelper::getOrderCache2($puid,$uid,'bonanza',"MenuStatisticData",'all') ;
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
	    		$counter = OrderHelper::getMenuStatisticData('bonanza',['selleruserid'=>$_REQUEST['selleruserid']]);
	    		$counter[OdOrder::STATUS_WAITACCEPT] = OdOrder::find()->where(['order_status'=>50])->andWhere(['selleruserid'=>$_REQUEST['selleruserid']])->andWhere(['order_source'=>"bonanza"])->count();
	    	}else{
	    		if(!empty($bonanzaAccountList)){
	    			$counter = OrderHelper::getMenuStatisticData('bonanza',['selleruserid'=>$bonanzaAccountList]);
	    			$counter[OdOrder::STATUS_WAITACCEPT] = OdOrder::find()->where(['order_status'=>50])->andWhere(['order_source'=>"bonanza"])->andWhere(['selleruserid'=>$bonanzaAccountList])->count();
	    		}else{
	    			//无有效绑定账号
	    			$counter=[];
	    			$claimOrderIDs=[];
	    		}
	    	}
	    	//save the redis cache for next time use
	    	if (!empty($_REQUEST['selleruserid'])){
	    		RedisHelper::setOrderCache2($puid,$uid,'bonanza',"MenuStatisticData",$_REQUEST['selleruserid'],$counter) ;
	    	}else{
	    		RedisHelper::setOrderCache2($puid,$uid,'bonanza',"MenuStatisticData",'all',$counter) ;
	    	}
	    }
	    
	     
	    //获取国家列表
	    $countryArr = array();
	    $countryArr['GB'] = StandardConst::getNationChineseNameByCode('GB').'(GB)';
	    //end 获取国家列表
	     
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
	    
	    //检查报关信息是否存在 start
// 	    $OrderIdList = [];
	    $existProductResult = OrderBackgroundHelper::getExistProductRuslt($models);
	    //检查报关信息是否存在 end
	    
	     
	    $search = array('is_comment_status'=>'Complete,Active');
	     
	    //tag 数据获取
	    $allTagDataList = OrderTagHelper::getTagByTagID();
	    $allTagList = [];
	    foreach($allTagDataList as $tmpTag){
	        $allTagList[$tmpTag['tag_id']] = $tmpTag['tag_name'];
	    }
	    
	    $tmp_REQUEST_text['REQUEST']=$_REQUEST;
	    $tmp_REQUEST_text['order_source']=$addi_condition;
	    $tmp_REQUEST_text['params']=empty($data->params)?Array():$data->params;
	    $tmp_REQUEST_text=base64_encode(json_encode($tmp_REQUEST_text));
	    
		return $this->render('list',array(
			'models' => $models,
		    'existProductResult'=>$existProductResult,
		    'pages' => $pagination,
// 			'sort' => $sortConfig,
			'excelmodels'=>$excelmodels,
			'bonanzaUsersDropdownList'=>$bonanzaUsersDropdownList,
			'usertabs'=>$usertabs,
			'counter'=>$counter,
		    'countrys'=>$countrys,
			'countryArr'=>$countryArr,
		    'warehouseids'=>$warehouseids,
// 			'sysCountry'=>$sysCountry,
// 			'warhouseArr'=>$warhouseArr,
			'showsearch'=>$showsearch,
			'tag_class_list'=> OrderTagHelper::getTagColorMapping(),
// 		    'bonanzaUsersTokenList'=>$bonanzaUsersTokenList,
// 		    'order_nav_html'=>BonanzaOrderHelper::getBonanzaOmsNav($order_nav_key_word),
		    'search'=>$search,
		    'all_tag_list'=>$allTagList,
		    'doarr'=>OrderHelper::getCurrentOperationList($op_code,'b') ,
		    'doarr_one'=>OrderHelper::getCurrentOperationList($op_code,'s'),
		    'country_mapping'=>$country_list,
		    'region'=>WarehouseHelper::countryRegionChName(),
			'search_condition'=>$tmp_REQUEST_text,
			'search_count'=>$pagination->totalCount,
		));
		
	}
	
	/**
	 * 订单编辑
	 * @author fanjs
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
			$old_status = $order->order_status;
// 			$order->setAttributes($_tmp);
			$action = '修改订单';
			$module = 'order';
			$fullName = \Yii::$app->user->identity->getFullName();
			$rt = OrderUpdateHelper::updateOrder($order, $_tmp , false , $fullName , $action , $module);
			
			$new_status = $order->order_status;
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
				//$item->order_source_srn = $item_tmp['order_source_srn'][$key];
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
// 			$order->checkorderstatus();
			//处理weird_status liang 2015-12-26
			if($old_status!==$new_status && ($new_status!==500 ||$new_status!==600) ){
// 				$addtionLog = '';
				if(!empty($order->weird_status))
					$addtionLog = ',并自动清除操作超时标签';
				$order->weird_status = '';
			}//处理weird_status end
			$order->save();
			AppTrackerApiHelper::actionLog("Oms-bonanza", "/order/bonanza/edit-save");//保存bonanza订单修改
			OperationLogHelper::log('order',$order->order_id,'修改订单','编辑订单进行订单修改'.$addtionLog,\Yii::$app->user->identity->getFullName());
			echo "<script language='javascript'>window.opener.location.reload();</script>";
			return $this->render('//successview',['title'=>'编辑订单']);
		}
		if (!isset($_GET['orderid'])){
			return $this->render('//errorview',['title'=>'编辑订单','message'=>'链接有误']);
		}
		$order = OdOrder::findOne($_GET['orderid']);
		if (empty($order)||$order->isNewRecord){
			return $this->render('//errorview',['title'=>'编辑订单','message'=>'未找到相应订单']);
		}
		$countrycode=OdOrder::getDb()->createCommand('select consignee_country_code from '.OdOrder::tableName().' group by consignee_country_code')->queryColumn();
		$countrycode=array_filter($countrycode);
		$countrys=StandardConst::$COUNTRIES_CODE_NAME_CN;
		
		AppTrackerApiHelper::actionLog("Oms-bonanza", "/order/bonanza/edit-page");//打开bonanza订单编辑页面
		
		return $this->render('edit',['order'=>$order,'countrys'=>$countrys]);
	}
	
	/**
	 * 订单列表页面进行批量平台标记发货,提供给用户填写物流号，物流方式等的页面
	 * @author fanjs
	 */
	public function actionSignshipped(){
		AppTrackerApiHelper::actionLog("Oms-bonanza", "/order/bonanza/signshipped");//打开bonanza标记发货页面
		if (\Yii::$app->request->getIsPost()){
			$orders = OdOrder::find()->where(['in','order_id',\Yii::$app->request->post()['order_id']])->all();
			return $this->render('signshipped',['orders'=>$orders]);
		}else {
			$orders = OdOrder::find()->where(['in','order_id',\Yii::$app->request->get('order_id')])->all();
			return $this->render('signshipped',['orders'=>$orders]);
		}
	}
	
	/**
	 * 订单列表页面进行批量平台标记发货,插入标记队列
	 * @author fanjs
	 */
	public function actionSignshippedsubmit(){
		if (\Yii::$app->request->getIsPost()){
			AppTrackerApiHelper::actionLog("Oms-bonanza", "/order/bonanza/signshippedsubmit");//bonanza订单列表页面进行标记发货
			$user = \Yii::$app->user->identity;
			$postarr = \Yii::$app->request->post();
			$tracker_provider_list  = BonanzaOrderInterface::getShippingCodeNameMap();
			if (count($postarr['order_id'])){
				foreach ($postarr['order_id'] as $oid){
					try {
						$order = OdOrder::findOne($oid);
						if(empty($postarr['shipmethod'])){
							return $this->render('//errorview',['title'=>'标记发货','error'=>'请选择物流方式!']);
							//echo "<script language='javascript'>alert('平台物流服务无效!');//window.close();</script>";
							//exit();
						}
						if (!empty($tracker_provider_list[$postarr['shipmethod'][$oid]])){
							$shipMethodName = $tracker_provider_list[$postarr['shipmethod'][$oid]];
						}else{
							$shipMethodName='';
						}
						
						$logisticInfoList=[
							'0'=>[
								'order_source'=>$order->order_source,
								'selleruserid'=>$order->selleruserid,
								'tracking_number'=>$postarr['tracknum'][$oid],
								'tracking_link'=>$postarr['trackurl'][$oid],
								'shipping_method_code'=>$postarr['shipmethod'][$oid],
								'shipping_method_name'=>$shipMethodName,//平台物流服务名
								'order_source_order_id'=>$order->order_source_order_id,
								'description'=>$postarr['message'][$oid],
								'addtype'=>'手动标记发货',
							]
						];
						//echo print_r($logisticInfoList,true);
					
						if(!OrderHelper::saveTrackingNumber($oid, $logisticInfoList,0,1)){
							\Yii::error(["Order",__CLASS__,__FUNCTION__,"Online",'订单'.$oid.'插入失败'],'edb\global');
						}else{
							OperationLogHelper::log('order', $oid,'标记发货','手动批量标记发货',\Yii::$app->user->identity->getFullName());
						}
	
					}catch (\Exception $ex){
						\Yii::error(["Order",__CLASS__,__FUNCTION__,"Online","save to SignShipped failure:".print_r($ex->getMessage())],'edb\global');
					}
				}
			
			//echo "<script language='javascript'>alert('操作已成功,即将关闭页面');//window.close();</script>";
			return $this->render('//successview',['title'=>'平台标记发货']);
			}
		}
	}
	
	/*
	 * 查看bonanza售卖的产品，List页面
	 */
	public function actionViewOfferList(){
		// 当前user 的puid 绑定的 bonanza 卖家账号
		$puid = \Yii::$app->user->identity->getParentUid();
		$bonanzaUsers = SaasBonanzaUser::find()->where(['uid'=>$puid])->asArray()->all();
		
		$bonanzaUsersDropdownList = array();
		foreach ($bonanzaUsers as $bonanzaUser){
			$bonanzaUsersDropdownList[$bonanzaUser['username']] = $bonanzaUser['store_name'];
		}
		
		$pageSize = !empty($_REQUEST['per-page']) ? $_REQUEST['per-page'] : 20;
		$sort = !empty($_REQUEST['sort']) ? $_REQUEST['sort'] : '-creation_date';
		 
		if( '-' == substr($sort,0,1) ){
			$sort = substr($sort,1);
			$order = 'desc';
		} else {
			$order = 'asc';
		}	
		 
		$sortConfig = new Sort(['attributes' => ['stock','price','creation_date','last_15_days_sold']]);
		if(!in_array($sort, array_keys($sortConfig->attributes))){
			$sort = '';
			$order = '';
		}
		
		$query = BonanzaOfferList::find()->where(['not' ,['seller_product_id'=>'']]);
		
		if(isset($_REQUEST['seller_id']) && $_REQUEST['seller_id']!=='')
			$query->andWhere(['seller_id'=> $_REQUEST['seller_id']]);
		
		if(isset($_REQUEST['offer_state']) && $_REQUEST['offer_state']!==''){
			if($_REQUEST['offer_state']=='Active')
				$query->andWhere(['offer_state'=>'Active']);
			else 
				$query->andWhere(['not',['offer_state'=>'Active']]);
		}
		
		if(isset($_REQUEST['is_bestseller']) && $_REQUEST['is_bestseller']!=='')
			$query->andWhere(['is_bestseller'=> $_REQUEST['is_bestseller']]);
		
		if(isset($_REQUEST['min_price']) && $_REQUEST['min_price']!=='' && is_numeric($_REQUEST['min_price'])){
			$min_price = floatval($_REQUEST['min_price']);
			$query->andWhere(" price >= $min_price ");
		}
		if(isset($_REQUEST['max_price']) && $_REQUEST['max_price']!=='' && is_numeric($_REQUEST['max_price'])){
			$max_price = floatval($_REQUEST['max_price']);
			$query->andWhere(" price <= $max_price ");
		}
		
		if(isset($_REQUEST['keyword']) && $_REQUEST['keyword']!==''){
			$keyword = trim($_REQUEST['keyword']);
			$query->andWhere([ 'or', ['like','product_ean',$keyword],
									 ['like','product_id',$keyword],
									 ['like','seller_product_id',$keyword],
									 ['like','name',$keyword],
							]);
		}
		
		$pagination = new Pagination([
				'pageSize' => $pageSize,
				'totalCount' =>$query->count(),
				'pageSizeLimit'=>[5,200],//每页显示条数范围
				]);
		$offerList['pagination'] = $pagination;

		$offerRows = $query->orderBy("$sort $order")
						->limit($pagination->limit)
						->offset($pagination->offset)
						->asArray()
						->all();
		$offerList['rows']=$offerRows;

		return $this->render('offer_list',[
				'offerList'=>$offerList,
				'bonanzaUsersDropdownList'=>$bonanzaUsersDropdownList,
				'sort'=>$sortConfig,
			]);
	}
	
	public function actionViewOffer($id){
		$errMsg = '';
		$offer=[];
		if(!is_numeric($id))
			$errMsg = '无效的在线商品 id ！';
		else{
			$offer = BonanzaOfferList::find()->where(['id'=>$id])->asArray()->One();
			if(empty($offer))
				$errMsg = '无该id的bonanza商品';
		}
		return $this->renderAjax('view_offer',[
					'offer'=>$offer,
					'errMsg'=>$errMsg,
				]);
	}
	
	public function actionPrintOffers(){
		return $this->render('print_offer');
	}
	
	/**
	 * 获取订单买家邮箱(前端触发)
	 * @params string	$_REQUEST['orderIds']
	 * @return array
	 */
	public function actionGetEmail(){
		$uid = \Yii::$app->user->id;
		if(empty($_REQUEST['orderIds'])){
			exit(json_encode(array('success'=>false,'message'=>'没有传入有效的单号')));
		}
		$orderIds = explode(',',$_REQUEST['orderIds']);
		$ret = BonanzaOrderInterface::getOrdersEmail($uid, $orderIds);
		exit(json_encode($ret));
	}
	
	
	/**
	 * 关闭提示助手(当日)
	 * @return string
	 */
	public function actionCloseReminder(){
		$uid = \Yii::$app->user->id;
		$ret = BonanzaOrderInterface::CloseReminder($uid);
		exit($ret);
	}
	
	public function actionGetChildrenOfferInfo(){
		$uid = \Yii::$app->user->id;
		$offer=[];
		$child_offer = BonanzaOfferList::find()->select("product_id")->where(['like','product_id','-0'])->asArray()->all();
		$count=0;
		foreach ($child_offer as $offer){
			$prod_id = $offer['product_id'];
			BonanzaOfferSyncHelper::syncProdInfoWhenGetOrderDetail($uid,$prod_id,$priority=1);
			$count++;
		}
		return $this->renderAjax('view_offer',[
					'offer'=>$offer,
					'errMsg'=>'find '.$count.'products,put to queue.',
				]);
	}
	
	public function actionGetNonImgOfferInfo(){
		$uid = \Yii::$app->user->id;
		$offer=[];
		$nonImgOffer = BonanzaOfferList::find()->select("product_id")->where(['img'=>null])->andwhere(['parent_product_id'=>null,])->asArray()->all();
		$count=0;
		foreach ($nonImgOffer as $offer){
			$prod_id = $offer['product_id'];
			BonanzaOfferSyncHelper::syncProdInfoWhenGetOrderDetail($uid,$prod_id,$priority=1);
			$count++;
		}
		return $this->renderAjax('view_offer',[
				'offer'=>$offer,
				'errMsg'=>'find '.$count.'products,put to queue.',
				]);
	}
	
	/**
	 * 前端手动触发获取用户 订单
	 * @param  $uid
	 * @param  $start	订单开始时间
	 * @param  $end		订单结束时间
	 * @param  $state	需要获取的订单状态过滤(平台状态首字母   多个之间用,分割)
	 * @param  $account	需要获取订单的账号(平台状态首字母   多个之间用,分割)
	 */
	public function actionGetOrderByContoller($uid,$start,$end,$state,$account=''){
		$account_query = SaasBonanzaUser::find()->where("is_active='1' and uid= $uid");
		if($account!=='')
			$account_query->andWhere(['username'=> $account]);
		
		$SAASUSERLIST = $account_query->all();
		
		echo "\n <br>YS1 start to fetch for unfuilled uid=$uid ... ";
		if (empty($uid)){
			echo "<br>uid false";
			exit();
		}
		 
		$state = explode(',', $state);
		$state_mapping =[
			'W'=>'WaitingForShipmentAcceptation',
			'C'=>'CancelledByCustomer',
			'S'=>'Shipped',
			'R1'=>'RefusedBySeller',
			'AC'=>'AutomaticCancellation',
			'SR'=>'ShipmentRefusedBySeller',
			'R2'=>'RefusedNoShipment',
			];
		$status = [];
		foreach ($state as $s){
			if(isset($state_mapping[$s]))
				$status[] = $state_mapping[$s];
		}
		
		try {
			foreach($SAASUSERLIST as $bonanzaAccount ){
				$updateTime = $end;
				$onwTimeUTC = $end;
				$sinceTimeUTC = $start;
	
				$getOrderCount = 0;
				//update this bonanza account as last order retrieve time
				$bonanzaAccount->last_order_retrieve_time = $updateTime;
						
				if (empty($bonanzaAccount->last_order_success_retrieve_time) or $bonanzaAccount->last_order_success_retrieve_time=='0000-00-00 00:00:00'){
					//如果还没有初始化完毕，就什么都不do
					echo "\n uid=$uid haven't initial_fetched !";
				}else{
					//start to get unfulfilled orders
					$orders = BonanzaOrderHelper::getOrdersByCondition($bonanzaAccount['token'], $sinceTimeUTC, $onwTimeUTC,false,$status);
						
					if (empty($orders['success'])){
						echo "\n fail to connect proxy  :".$orders['message'];
						$bonanzaAccount->save();
						continue;
					}

					if(isset($orders['proxyResponse']['orderList']['s_Body']['s_Fault']['faultstring'])){
						echo "\n".$orders['proxyResponse']['orderList']['s_Body']['s_Fault']['faultstring'];
						$bonanzaAccount->order_retrieve_message = $orders['proxyResponse']['orderList']['s_Body']['s_Fault']['faultstring'];
					}
					if (!empty ($orders['proxyResponse']['success'])){
						if(isset($orders['proxyResponse']['orderList']['s_Body']['GetOrderListResponse']['GetOrderListResult']['OrderList']['Order']))
							echo "\n isset ".'orders[proxyResponse][orderList][s_Body][GetOrderListResponse][GetOrderListResult][OrderList][Order]';
						//sync bonanza info to bonanza order table
						if(!empty($orders['proxyResponse']['orderList']['s_Body']['GetOrderListResponse']['GetOrderListResult']['OrderList']['Order'])){
							echo "\n !empty ".'orders[proxyResponse][orderList][s_Body][GetOrderListResponse][GetOrderListResult][OrderList][Order]';
							$rtn = BonanzaOrderHelper::_InsertBonanzaOrder($orders['proxyResponse']['orderList']['s_Body']['GetOrderListResponse']['GetOrderListResult']['OrderList']['Order'] , $bonanzaAccount);
							if($rtn['success']){
								$bonanzaAccount->last_order_success_retrieve_time = $updateTime;
							}
						}else{
							echo "\n get none order";
							$bonanzaAccount->last_order_success_retrieve_time = $updateTime;
						}//end of GetOrderListResult empty or not
								
					}else{
						if (!empty ($orders['proxyResponse']['message'])){
							echo "\n uid = $uid proxy error  :".$orders['proxyResponse']['message'].$bonanzaAccount['token'];
						}else{
							echo "\n uid = $uid proxy error  : not any respone message".$bonanzaAccount['token'];
						}
					}
					//end of getting orders from bonanza server
								
					if (!empty ($orders['proxyResponse']['message'])){
						$bonanzaAccount->order_retrieve_message = $orders['proxyResponse']['message'];
					}else
						$bonanzaAccount->order_retrieve_message = '';//to clear the error msg if last attemption got issue
	
					if (!$bonanzaAccount->save()){
						echo "\n failure to save bonanza account info ,error:";
						echo "\n uid:".$bonanzaAccount['uid']."error:". print_r($bonanzaAccount->getErrors(),true);
					}else{
						echo "\n BonanzaAccount model save !";
					}
				}
			}//end of each bonanza user account
		} catch (\Exception $e) {
			echo "\n cronAutoFetchRecentOrderList Exception:".$e->getMessage();
		}
	}
	
	/**
	 * 前端手动触发获原始表generate到v2表
	 */
	public function actionGenerateSrcOrderToOmsIfNotExisting(){
		
		$start=empty($_REQUEST['s'])?$_REQUEST['s']:'';
		$end=empty($_REQUEST['e'])?$_REQUEST['s']:'';
		$uids = $_REQUEST['uids'];
		$uid_arr = explode(',', $uids);
		
		foreach ($uid_arr as $uid){
			if (empty($uid))
				continue;
 	
			$query = BonanzaOrder::find()->where(" (SELECT COUNT( 1 ) AS num FROM od_order_v2 WHERE bonanza_order.`ordernumber` = od_order_v2.order_source_order_id) =0 ");
			if(!empty($start))
				$query->where(" creationdate>='$start' ");
			if(!empty($end))
				$query->where(" creationdate<='$end' ");
			
			$omsNotExistingOrders = $query->asArray()->all();
			
			$user_accounts = SaasBonanzaUser::find()->where(['uid'=>$uid])->asArray()->all();
			$accounts = [];
			foreach ($user_accounts as $a){
				$accounts[$a['username']] = $a;
			}
			
			foreach ($omsNotExistingOrders as $nE){
				if(!isset($accounts[$nE['seller_id']]))
					continue;
				
				$formated_order_data = BonanzaOrderHelper::formatSrcOrderDataToOmsOrder($nE, $accounts[$nE['seller_id']],true);
				$items = BonanzaOrderDetail::find()->where(['ordernumber'=>$nE['ordernumber']])->asArray()->all();
				$formated_order_detail_data = BonanzaOrderHelper::formatSrcOrderItemsDataToOmsOrder($items, $nE['ordernumber']);
				
				$subtotal=0; //产品总价格
				if(isset($formated_order_data['total_amount']) && isset($formated_order_data['shipping_cost']) ){
					$subtotal = $formated_order_data['total_amount'] - $formated_order_data['shipping_cost'];
				}else
					$subtotal=$formated_order_detail_data['total_amount'];
				
				if(empty($formated_order_data['shipping_cost']))
					$formated_order_data['shipping_cost'] = $formated_order_detail_data['delivery_amonut']; //若订单获得的运费empty,则取由items计算出的运费
				
				$formated_order_data['grand_total'] = $formated_order_data['subtotal'] + $formated_order_data['shipping_cost'] - $formated_order_data['discount_amount'] - $formated_order_data['commission_total'] ;//合计金额
				$formated_order_data['items']=$formated_order_detail_data['items'];
				
				$reqInfo[$uid]=array_merge(OrderHelper::$order_demo,$formated_order_data);
				
				try{
					$ret =  OrderHelper::importPlatformOrder($reqInfo,-1);
				}catch(\Exception $e){
					$message = "uid=$uid importPlatformOrder fails. Exception error:".$e->getMessage()."data: \n ".print_r($reqInfo,true);
					echo $message;
				}
			}
		}
		
		exit();	
	}
	
	
	/**
	 *  OMS dash-board
	 * @param string $user
	 * @return remix
	 */
	public function actionJobMonitor($user){
		if($user!=='eagle-liang')
			return $this->render('monitor',[]);
		
		$MonitoData = BonanzaOrderInterface::getMonitorData();
		return $this->render('monitor',[
				'data'=>$MonitoData,
			]);
			
		
	}
	
	public function actionUserOrderCount($user){
		if($user!=='eagle-liang')
			return $this->render('monitor',[]);
	
		$UserOrderCountDatas = BonanzaOrderInterface::getUserOrderCountDatas();
		return $this->render('_user_order_count',[
				'datas'=>$UserOrderCountDatas['count_datas'],
				'pages'=>$UserOrderCountDatas['pagination'],
				'tops'=>$UserOrderCountDatas['tops'],
				]);
			
	
	}

	/**
	 * 打开用户使用的dash-board
	 * @param	int		$autoShow	调用类型：0:手动展示，1:自动展示
	 * @return	mixed
	 */
	public function actionUserDashBoard($autoShow=1){
		$uid = \Yii::$app->user->id;
		if (empty($uid))
			exit('请先登录!');
		$chartData['order_count'] = BonanzaOrderInterface::getChartDataByUid_Order($uid,10);
		$chartData['profit_count'] = BonanzaOrderInterface::getChartDataByUid_Profit($uid,10);
		$advertData = BonanzaOrderInterface::getAdvertDataByUid($uid,2);
		
		$autoShow = (int)$autoShow;
		if(!empty($autoShow)){//自动展示时，如果用户不手动收起dashboard，则认为用户未有任何操作，下次打开oms时无论是何时，依旧展示
			//此情况下，可以不设置next time,或设置成now
			//$set_redis = \Yii::$app->redis->hset('BonanzaOms_DashBoard',"user_$uid".".next_show",date("Y-m-d H:i:s",time()));
		}else{//用户手动展示dashboard，则认为用户已经浏览了dashboard，下次展示为4小时后
			//$set_redis = \Yii::$app->redis->hset('BonanzaOms_DashBoard',"user_$uid".".next_show",date("Y-m-d H:i:s",time()+3600*4));
			$set_redis = RedisHelper::RedisSet('BonanzaOms_DashBoard',"user_$uid".".next_show",date("Y-m-d H:i:s",time()+3600*4));
		}
		list($platformUrl,$label)=AppApiHelper::getPlatformMenuData();
		
		return $this->renderAjax('_dash_board',[
				'chartData'=>$chartData,
				'advertData'=>$advertData,
		        'platformUrl'=>$platformUrl,
			]);
	}
	
	
	public function actionHideDashBoard(){
		$uid = \Yii::$app->user->id;
		if (empty($uid))
			return false;
		//$set_redis = \Yii::$app->redis->hset('BonanzaOms_DashBoard',"user_$uid".".next_show",date("Y-m-d H:i:s",time()+3600*4));
		$set_redis = RedisHelper::RedisSet('BonanzaOms_DashBoard',"user_$uid".".next_show",date("Y-m-d H:i:s",time()+3600*4));
		return $set_redis;
	}
	
	
	public function actionIsAccept(){
	    if(!empty($_REQUEST['action'])&&!empty($_REQUEST['orderId'])&&!empty($_REQUEST['token'])){
	        BonanzaOrderInterface::setBonanzaToken($_REQUEST['token']);
            if($_REQUEST['action'] == 'accept'){
                $result = BonanzaOrderInterface::acceptOrder($_REQUEST['orderId']);
                $check = strpos($result['message'],'OfferCantBeAccepted');
                if($check === false){
                    $message = $result['message'];
                }else{
                    $message = '订单不能被接受';
                }
                return json_encode(array('success'=>$result['success'],'message'=>$message));
            }else if($_REQUEST['action'] == 'refuse'){
                $result = BonanzaOrderInterface::refuseOrder($_REQUEST['orderId']);
                $check = strpos($result['message'],'OfferCantBeDenied');
                if($check === false){
                    $message = $result['message'];
                }else{
                    $message = '订单不能被拒绝';
                }
                return json_encode(array('success'=>$result['success'],'message'=>$message));
            }
	    }else{
	        return json_encode(array('success'=>false,'message'=>'传输数据有误!'));
	    }
	    
	}
	
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
	
	    $detail = BonanzaOrderHelper::getOrderSyncInfoDataList($status,$last_sync_time);
	
	    if (!empty($_REQUEST['order_status']))
	        $order_nav_key_word = $_REQUEST['order_status'];
	    else
	        $order_nav_key_word='';
	
// 	    $counter = OrderHelper::getMenuStatisticData('bonanza');
	
	    return $this->renderAjax('order_sync',[
	        'sync_list'=>$detail,
// 	        'order_nav_html'=>BonanzaOrderHelper::getBonanzaOmsNav($order_nav_key_word),
// 	        'counter'=>$counter,
	    ]);
	}//end of actionOrderSyncInfo
	
	
}

?>