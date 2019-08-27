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
// use eagle\models\SaasAmazonUser;
use yii\data\Sort;
use eagle\models\SaasRumallUser;
use eagle\modules\inventory\models\Warehouse;
use eagle\modules\order\helpers\OrderTagHelper;
use eagle\modules\util\helpers\ConfigHelper;
use eagle\modules\util\helpers\StandardConst;
use eagle\modules\order\helpers\RumallOrderInterface;
use eagle\modules\order\helpers\OrderHelper;
use eagle\models\SysCountry;
use eagle\modules\app\apihelpers\AppTrackerApiHelper;
use eagle\modules\platform\helpers\RumallAccountsV2Helper;
use eagle\modules\order\helpers\RumallOrderHelper;
use eagle\modules\util\helpers\TimeUtil;
use eagle\modules\platform\apihelpers\RumallAccountsApiHelper;
use eagle\modules\inventory\helpers\InventoryApiHelper;
use eagle\modules\order\helpers\OrderBackgroundHelper;
use eagle\modules\inventory\helpers\WarehouseHelper;
use eagle\modules\app\apihelpers\AppApiHelper;
use eagle\modules\carrier\helpers\CarrierOpenHelper;
use eagle\modules\order\apihelpers\OrderApiHelper;
use eagle\modules\order\helpers\OrderGetDataHelper;
use eagle\modules\order\helpers\OrderUpdateHelper;
use eagle\modules\platform\apihelpers\PlatformAccountApi;
use eagle\modules\util\helpers\RedisHelper;

class RumallOrderController extends Controller{
	public $enableCsrfValidation = false;
	
	public function behaviors() {
        return [
         	'access' => [
                'class' => \yii\filters\AccessControl::className(),
                'rules' => [
                    [
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
            'verbs' => [
                'class' => \yii\filters\VerbFilter::className(),
                'actions' => [
                    'delete' => ['post'],
                ],
            ],
        ];
    }
	/**
	 * rumall订单列表页面
	 * 
	 * 
	 */
	public function actionList(){
		//check模块权限
		$permission = \eagle\modules\permission\apihelpers\UserApiHelper::checkPlatformPermission('rumall');
		if(!$permission)
			return $this->render('//errorview_no_close',['title'=>'访问受限:没有权限','error'=>'您还没有订单模块的权限!']);
 
		AppTrackerApiHelper::actionLog("Oms-rumall", "/order/rumall/list");
		
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
		
		$data = OdOrder::find()->where(['order_source' => 'rumall' ]);
		
		
		$showsearch=0;
		$op_code = '';
		
		
		$tmpSellerIDList = PlatformAccountApi::getPlatformAuthorizeAccounts('rumall');//添加账号权限	lzhl 2017-03
		$accountList = [];
		$rumallUsersDropdownList = [];
		foreach($tmpSellerIDList as $sellerloginid=>$store_name){
			$accountList[] = $sellerloginid;
			$rumallUsersDropdownList[$sellerloginid] = $store_name;
		}
		if(empty($accountList)){
			//如果为测试用的账号就不受平台绑定限制
			$test_userid=\eagle\modules\tool\helpers\MirroringHelper::$test_userid;
			if (!in_array(\Yii::$app->user->identity->getParentUid(),$test_userid['yifeng'])){
				//无有效权限账号时
				return $this->render('//errorview_no_close',['title'=>'访问受限:没有权限','error'=>'您还没有获得任何 丰卖网  账号管理权限!']);
			}
		}

		$addi_condition = ['order_source'=>'rumall'];
		$addi_condition['sys_uid'] = \Yii::$app->user->id;
		$addi_condition['selleruserid_tmp'] = $rumallUsersDropdownList;
		
		$tmp_REQUEST_text['where']=empty($data->where)?Array():$data->where;
		$tmp_REQUEST_text['orderBy']=empty($data->orderBy)?Array():$data->orderBy;
		$omsRT = OrderApiHelper::getOrderListByConditionOMS($_REQUEST,$addi_condition,$data,$pageSize,false,'all');
		if (!empty($_REQUEST['order_status'])){
		    //生成操作下拉菜单的code
		    $op_code = $_REQUEST['order_status'];
		}
		if (!empty($omsRT['showsearch'])) $showsearch = 1;
			
        $pagination = new Pagination([
				'defaultPageSize' => 20,
				'pageSize' => $pageSize,
				'totalCount' => $data->count(),
				'pageSizeLimit'=>[5,200],//每页显示条数范围
				'params'=>$_REQUEST,
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
	    
	    // 当前user 的puid 绑定的 rumall 卖家账号
	    $puid = \Yii::$app->user->identity->getParentUid();
	    /*
	    $rumallUsers = SaasRumallUser::find()->where(['uid'=>$puid])->asArray()->all();
	    $rumallUsersDropdownList = array();
	    foreach ($rumallUsers as $rumallUser){
	    	$rumallUsersDropdownList[$rumallUser['company_code']] = $rumallUser['store_name'];
	    }
	    */
	    $usertabs = Helper_Array::toHashmap(Helper_Array::toArray(Usertab::find()->all()),'id','tabname');
	    $warehouseids = InventoryApiHelper::getWarehouseIdNameMap();
	    
	    //订单数量统计
	    $counter = [];
	    $hitCache = "NoHit";
	    $cachedArr = array();
	    $uid = \Yii::$app->user->id;
	    $stroe = 'all';
	    if(!empty($_REQUEST['selleruserid']))
	    	$stroe  = trim($_REQUEST['selleruserid']);
	    
	    $isParent = \eagle\modules\permission\apihelpers\UserApiHelper::isMainAccount();
	    if($isParent){
	    	$gotCache = RedisHelper::getOrderCache2($puid,$uid,'rumall',"MenuStatisticData",$stroe) ;
	    }else{
	    	if (!empty($_REQUEST['selleruserid'])){
	    		$gotCache = RedisHelper::getOrderCache2($puid,$uid,'rumall',"MenuStatisticData",$_REQUEST['selleruserid']) ;
	    	}else{
	    		$gotCache = RedisHelper::getOrderCache2($puid,$uid,'rumall',"MenuStatisticData",'all') ;
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
	    		$counter = OrderHelper::getMenuStatisticData('rumall',['selleruserid'=>$_REQUEST['selleruserid']]);
	    	}else{
	    		if(!empty($accountList)){
	    			$counter = OrderHelper::getMenuStatisticData('rumall',['selleruserid'=>$accountList]);
	    		}else{
	    			//无有效绑定账号
	    			$counter=[];
	    			$claimOrderIDs=[];
	    		}
	    	}
	    	//save the redis cache for next time use
	    	if (!empty($_REQUEST['selleruserid'])){
	    		RedisHelper::setOrderCache2($puid,$uid,'rumall',"MenuStatisticData",$_REQUEST['selleruserid'],$counter) ;
	    	}else{
	    		RedisHelper::setOrderCache2($puid,$uid,'rumall',"MenuStatisticData",'all',$counter) ;
	    	}
	    }
	    /*
	    //订单数量统计
	    if (!empty($rumallAccountList)){
	        //不显示 解绑的账号的订单
	        $counter = OrderHelper::getMenuStatisticData('rumall',['selleruserid'=>$rumallAccountList]);
	    }else{
	        $counter = OrderHelper::getMenuStatisticData('rumall');
	    }
// 	    $countrycode=OdOrder::getDb()->createCommand('select consignee_country_code from '.OdOrder::tableName().' group by consignee_country_code')->queryColumn();
// 	    $countrycode=array_filter($countrycode);
		*/
	    $search = array('is_comment_status'=>'等待您留评');
	    
	    //tag 数据获取
	    $allTagDataList = OrderTagHelper::getTagByTagID();
	    $allTagList = [];
	    foreach($allTagDataList as $tmpTag){
	        $allTagList[$tmpTag['tag_id']] = $tmpTag['tag_name'];
	    }
	    
	    //订单导航
// 	    if (!empty($_REQUEST['order_status']))
// 	        $order_nav_key_word = $_REQUEST['order_status'];
// 	    else
// 	        $order_nav_key_word='';
	    
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
	    
	    //获取dash board cache 数据
	    $uid = \Yii::$app->user->identity->getParentUid();
	    $DashBoardCache = RumallOrderHelper::getOmsDashBoardCache($uid);
	    
	    //检查报关信息是否存在 start
	    $OrderIdList = [];
	    $existProductResult = OrderBackgroundHelper::getExistProductRuslt($models);
	    //检查报关信息是否存在 end
	    
	    //获取国家列表
	    $countryArr = array();
// 	    $tmpCountryArr = \Yii::$app->get('db')->createCommand("select a.amazon_site_code,a.country_label,a.country_code from amazon_site a")->queryAll();
	     
// 	    foreach ($tmpCountryArr as $tmpCountry){
// 	    	$countryArr[$tmpCountry['country_code']] = $tmpCountry['country_label']."(".$tmpCountry['country_code'].")";
// 	    }
	    
	    $nations_array = json_decode(ConfigHelper::getConfig("RumallOMS/nations"),true);
	    
	    if(empty($nations_array)){
	        $new_nations_array = array();
	        if(count($models)){//获取所有国家，当前订单
	            foreach ($models as $detail_order){
	                if(!empty($detail_order->consignee_country_code)){
	                    $new_nations_array[$detail_order->consignee_country_code] = $detail_order->consignee_country_code;
	                }
	            }
	            if(!empty($new_nations_array)){
	                ConfigHelper::setConfig("RumallOMS/nations", json_encode($new_nations_array));
	            }
	        }
	        $nations_array = $new_nations_array;
	    }else if(is_array($nations_array)){
	       $hasChange = false; 
	       if(count($models)){//获取所有国家，当前订单
	            foreach ($models as $detail_order){
	                if(!empty($detail_order->consignee_country_code)){
	                    if(!isset($nations_array[$detail_order->consignee_country_code])){
	                        $nations_array[$detail_order->consignee_country_code] = $detail_order->consignee_country_code;
	                        $hasChange = true;
	                    }
	                }
	            }
	        }
	        if($hasChange){
	            ConfigHelper::setConfig("RumallOMS/nations", json_encode($nations_array));
	        }
	    }
	    
	    foreach ($nations_array as $nation_code=>$val){
	        $countryArr[$nation_code] = StandardConst::getNationChineseNameByCode($nation_code).'('.$nation_code.')';
	    }
	    //end 获取国家列表
	     
	    //获取所有国家编码对应国家中文名称
// 	    $sysCountry = [];
// 	    $countryModels = SysCountry::find()->asArray()->all();
// 		foreach ($countryModels as $countryModel){
// 			$sysCountry[$countryModel['country_code']] = $countryModel['country_zh'];
// 		}
	    //end 获取所有国家编码对应国家中文名称
	     
	    //获取仓库列表
// 	    $warhouseArr = array();
// 	    $tmpWarhouseArr = Warehouse::find()->select(['warehouse_id','name'])->where(['is_active' => "Y"])->asArray()->all();
// 	    foreach ($tmpWarhouseArr as $tmpWarhouse){
// 	    	$warhouseArr[$tmpWarhouse['warehouse_id']] = $tmpWarhouse['name'];
// 	    }
	    //end 获取仓库列表
	    

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
// 			'rumallUsersDropdownList'=>$rumallUsersDropdownList,
			'usertabs'=>$usertabs,
			'counter'=>$counter,
		    'warehouseids'=>$warehouseids,
		    'selleruserids'=>$rumallUsersDropdownList,
		    'countrys'=>$countrys,
			'countryArr'=>$countryArr,
// 			'sysCountry'=>$sysCountry,
// 			'warhouseArr'=>$warhouseArr,
			'showsearch'=>$showsearch,
			'tag_class_list'=> OrderTagHelper::getTagColorMapping(),
		    'all_tag_list'=>$allTagList,
		    'doarr'=>RumallOrderHelper::getRumallCurrentOperationList($op_code,'b') ,
		    'doarr_one'=>RumallOrderHelper::getRumallCurrentOperationList($op_code,'s'),
		    'country_mapping'=>$country_list,
		    'region'=>WarehouseHelper::countryRegionChName(),
		    'search'=>$search,
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
			
			$rt = OrderHelper::setOriginShipmentDetail($order);
			if ($rt['success'] ==false){
			    return $this->render('//errorview',['title'=>'编辑订单','message'=>'内部错误，请联系客服']);
			}
			
			$action = '修改订单';
			$module = 'order';
			$fullName = \Yii::$app->user->identity->getFullName();
			$rt = OrderUpdateHelper::updateOrder($order, $_tmp , false , $fullName , $action , $module);
			
// 			foreach($_tmp as $key=>$value){
// 			    $order->$key = $value;
// 			}
			
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
				//$item->order_source_srn = $item_tmp['order_source_srn'][$key];
// 				$item->price = $item_tmp['price'][$key];
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
			$order->save();
			AppTrackerApiHelper::actionLog("Oms-rumall", "/order/rumall/edit-save");
			OperationLogHelper::log('order',$order->order_id,'修改订单','编辑订单进行订单修改',\Yii::$app->user->identity->getFullName());
// 			echo "<script language='javascript'>window.opener.location.reload();</script>";
			return $this->render('//successview',['title'=>'编辑订单']);
		}
		
		if (!isset($_GET['orderid'])){
			return $this->render('//errorview',['title'=>'编辑订单','message'=>'链接有误']);
		}
		$order = OdOrder::findOne($_GET['orderid']);
		if (empty($order)||$order->isNewRecord){
			return $this->render('//errorview',['title'=>'编辑订单','message'=>'未找到相应订单']);
		}
		AppTrackerApiHelper::actionLog("Oms-rumall", "/order/rumall/edit-page");
		
		$orderShipped = OdOrderShipped::find()->where(['order_id'=>$_GET['orderid']])->asArray()->all();
		return $this->render('edit',['order'=>$order,'ordershipped'=>$orderShipped]);
	}
	
	/**
	 * 订单列表页面进行批量平台标记发货,提供给用户填写物流号，运输服务等的页面
	 * @author fanjs
	 */
	public function actionSignshipped(){
		AppTrackerApiHelper::actionLog("Oms-rumall", "/order/rumall/signshipped");
		$rumallShippingMethod = RumallOrderHelper::getShippingCodeNameMap();
		if (\Yii::$app->request->getIsPost()){
			$orders = OdOrder::find()->where(['in','order_id',\Yii::$app->request->post()['order_id']])->all();
			return $this->render('signshipped',['orders'=>$orders,'rumallShippingMethod'=>$rumallShippingMethod]);
		}else {
			$orders = OdOrder::find()->where(['in','order_id',\Yii::$app->request->get('order_id')])->all();
			return $this->render('signshipped',['orders'=>$orders,'rumallShippingMethod'=>$rumallShippingMethod]);
		}
	}
	
	/**
	 * 订单列表页面进行批量平台标记发货,插入标记队列
	 * @author fanjs
	 */
	public function actionSignshippedsubmit(){
		if (\Yii::$app->request->getIsPost()){
			AppTrackerApiHelper::actionLog("Oms-rumall", "/order/rumall/signshippedsubmit");
			$user = \Yii::$app->user->identity;
			$postarr = \Yii::$app->request->post();
			$tracker_provider_list  = RumallOrderHelper::getShippingCodeNameMap();
			if (count($postarr['order_id'])){
				foreach ($postarr['order_id'] as $oid){
					try {
						$order = OdOrder::findOne($oid);
						if (!empty($tracker_provider_list[$postarr['shipmethod'][$oid]])){
							$shipMethodName = $tracker_provider_list[$postarr['shipmethod'][$oid]];
						}else{
							echo "<script language='javascript'>alert('平台物流服务无效!');window.close();</script>";
							exit();
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
			
			echo "<script language='javascript'>alert('操作已成功,即将关闭页面');window.close();</script>";
			return $this->render('//successview',['title'=>'平台标记发货']);
		}
		}
	}
	
// 	/**
// 	 +---------------------------------------------------------------------------------------------
// 	 * 手动同步rumall订单,队列优先度提前
// 	 +---------------------------------------------------------------------------------------------
// 	 * @access static
// 	 +---------------------------------------------------------------------------------------------
// 	 * @param
// 	 +---------------------------------------------------------------------------------------------
// 	 * @return
// 	 +---------------------------------------------------------------------------------------------
// 	 * log			name	date					note
// 	 * @author		lkh		2015/8/18				初始化
// 	 +---------------------------------------------------------------------------------------------
// 	 **/
// 	function actionSyncmt(){
		
// 		$sync = SaasRumallUser::find()->where(['is_active'=>'1' , 'uid'=>\Yii::$app->user->id,])->all();
		
// 		return $this->renderPartial('syncmt',['sync'=>$sync]);
// 	}
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * ajax处理同步订单优先请求的处理
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param
	 +---------------------------------------------------------------------------------------------
	 * @return
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2015/8/18				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	function actionAjaxsyncmt(){
		if (\Yii::$app->request->isPost){
			try {
				//检查site id 是否存在
				if (empty($_POST['site_id'])){
					return json_encode(['ack'=>'failure','msg'=>'未知错误， 请联系客服！']);
				}
				
				//检查账号是否存在
				$model = SaasRumallUser::findOne(['site_id'=>$_POST['site_id']]);
				if (!empty($model)){
					$result = RumallAccountsV2Helper::setManualRetrieveOrder($_POST['site_id']);
					if ($result['success'] == false){
						return json_encode(['ack'=>'failure','msg'=>$result['message']]);
					}
				}else{
					return json_encode(['ack'=>'failure','msg'=>'账号异常， 请联系客服！']);
				}
				
			}catch (\Exception $e){
				return json_encode(['ack'=>'failure','msg'=>$e->getMessage()]);
			}
			return json_encode(['ack'=>'success']);
		}
	}
	
	function actionUnlockOrderQueue(){
		// 当前user 的puid 绑定的 rumall 卖家账号
		$puid = \Yii::$app->user->identity->getParentUid();
		if ($puid !="1"){
			exit('no found');
		}
		
		if (!empty($_REQUEST['site_id'])   ){
			$msg = empty($_REQUEST['msg'])?"":$_REQUEST['msg'];
			$status = empty($_REQUEST['oq_status'])?"":$_REQUEST['oq_status'];
			RumallOrderHelper::unlockRumallOrderQueue($_REQUEST['site_id'],$msg , $status);
			exit('OK');
		}else{
			exit('no site id');
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
	                    $tmpRT = OrderHelper::requestRefreshOrderQueue($uid,  $order_id, 'rumall');
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
	
	    $cacheData = RumallOrderHelper::getOmsDashBoardData($uid,$isRefresh);
	
	    $platform = 'rumall';
	    $chartData['order_count'] = $cacheData['order_count'];
	    //$chartData['profit_count'] = CdiscountOrderInterface::getChartDataByUid_Profit($uid,10);//oms 利润统计 aliexpress 没有先屏蔽
	    $advertData =  $cacheData['advertData'];// 获取OMS dashboard广告
	    $AccountProblems = RumallOrderHelper::getUserAccountProblems($uid); //账号信息统计
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
	    $platform = 'rumall';
	    $uid = \Yii::$app->user->id;
	    if (empty($uid))
	        exit('请先登录!');
	
	    if (!empty($_REQUEST['isrefresh'])){
	        $isRefresh = true;
	    }else{
	        $isRefresh = false;
	    }
	    $cacheData = RumallOrderHelper::getOmsDashBoardData($uid,$isRefresh);
	}
	
	/**
	 +----------------------------------------------------------
	 * rumall订单同步   action
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
	
	    $detail = RumallOrderHelper::getOrderSyncInfoDataList($status,$last_sync_time);
	
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