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
use eagle\models\SaasEnsogoUser;
use eagle\modules\inventory\models\Warehouse;
use eagle\modules\order\helpers\OrderTagHelper;
use eagle\modules\util\helpers\ConfigHelper;
use eagle\modules\util\helpers\StandardConst;
use eagle\modules\order\helpers\EnsogoOrderInterface;
use eagle\modules\order\helpers\OrderHelper;
use eagle\models\SysCountry;
use eagle\modules\app\apihelpers\AppTrackerApiHelper;
use eagle\modules\platform\helpers\EnsogoAccountsHelper;
use eagle\modules\order\helpers\EnsogoOrderHelper;
use common\api\ensogointerface\EnsogoInterface_Helper;


class EnsogoOrderController extends Controller{
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
	 * ensogo订单列表页面
	 * 
	 * 
	 */
	public function actionList(){
		AppTrackerApiHelper::actionLog("Oms-ensogo", "/order/ensogo/list");
		
		$data = OdOrder::find()->where(['order_source' => 'ensogo' ]);
		$showsearch=0;
		
		if (!empty($_REQUEST['order_status'])){
			//搜索订单状态
			$data->andWhere('order_status = :os',[':os'=>$_REQUEST['order_status']]);
		}
		if (!empty($_REQUEST['exception_status'])){
			//搜索订单挂起状态
			$data->andWhere('exception_status = :os',[':os'=>$_REQUEST['exception_status']]);
		}
		if (!empty($_REQUEST['is_manual_order'])){
			//搜索订单挂起状态
			$data->andWhere('is_manual_order = :os',[':os'=>$_REQUEST['is_manual_order']]);
		}
		
		if (!empty($_REQUEST['cangku'])){
			//搜索仓库
			$data->andWhere('default_warehouse_id = :warehouse_id',[':warehouse_id'=>$_REQUEST['cangku']]);
			$showsearch=1;
		}
		if (!empty($_REQUEST['shipmethod'])){
			//搜索运输服务
			$data->andWhere('default_shipping_method_code = :shipmethod',[':shipmethod'=>$_REQUEST['shipmethod']]);
			$showsearch=1;
		}
		if (!empty($_REQUEST['fuhe'])){
			$showsearch=1;
			$data->andWhere(['order_source_status'=>$_REQUEST['fuhe']]);
			//搜索符合条件
			/*
			switch ($_REQUEST['fuhe']){
				case 'hasnotpayed':
					$data->andWhere('pay_status = 0');
					break;
				case 'haspayed':
					$data->andWhere('pay_status = 1');
					break;
				case 'payedsend':
					$data->andWhere('shipping_status = 1');
					break;
				default:break;
			}
			*/
		}
		if (!empty($_REQUEST['searchval'])){
			//搜索用户自选搜索条件
			if (in_array($_REQUEST['keys'], ['order_id','ensogo_orderid','buyeid'])){
				$kv=[
					'order_id'=>'order_id',
					'ensogo_orderid'=>'order_source_order_id',
					'buyeid'=>'consignee',
				];
				$key = $kv[$_REQUEST['keys']];
				$data->andWhere("$key = :val",[':val'=>$_REQUEST['searchval']]);
			}elseif ($_REQUEST['keys']=='sku'){
				$ids = Helper_Array::getCols(OdOrderItem::find()->where('sku = :sku',[':sku'=>$_REQUEST['searchval']])->select('order_id')->asArray()->all(),'order_id');
				$data->andWhere(['IN','order_id',$ids]);
			}elseif ($_REQUEST['keys']=='itemid'){
				
			}elseif ($_REQUEST['keys']=='tracknum'){
				$ids = Helper_Array::getCols(OdOrderShipped::find()->where('tracking_number = :tn',[':tn'=>$_REQUEST['searchval']])->select('order_id')->asArray()->all(),'order_id');
				$data->andWhere(['IN','order_id',$ids]);
			}
		}
		if (!empty($_REQUEST['selleruserid'])){
			//搜索卖家账号
			$data->andWhere('selleruserid = :s',[':s'=>$_REQUEST['selleruserid']]);
		}
		if (!empty($_REQUEST['country'])){
			$showsearch=1;
			//搜索订单国家
			$data->andWhere('consignee_country_code = :consignee_country_code',[':consignee_country_code'=>$_REQUEST['country']]);
		}
		if (!empty($_REQUEST['startdate'])||!empty($_REQUEST['enddate'])){
			$showsearch=1;
			//搜索订单日期
			switch ($_REQUEST['timetype']){
				case 'soldtime':
					$tmp='order_source_create_time';
				break;
				case 'paidtime':
					$tmp='paid_time';
				break;
				case 'shiptime':
					$tmp='delivery_time';
				break;
			}
			if (!empty($_REQUEST['startdate'])){
				$data->andWhere($tmp.' >= :timeS',[':timeS'=>strtotime($_REQUEST['startdate'])]);
			}
			if (!empty($_REQUEST['enddate'])){
				$data->andWhere($tmp.' <= :timeE',[':timeE'=>strtotime($_REQUEST['enddate']." 23:59:59")]);
			}
		}
		
		
		if (empty($_REQUEST['ordersort'])){
			$orderstr = 'order_source_create_time';
		}else{
			switch ($_REQUEST['ordersort']){
				case 'soldtime':
					$orderstr='order_source_create_time';
					break;
				case 'paidtime':
					$orderstr='paid_time';
					break;
				case 'shiptime':
					$orderstr='delivery_time';
					break;
			}
		}
		if (empty($_REQUEST['ordersorttype'])){
			$orderstr .= ' DESC';
		}else{
			$orderstr.=' '.$_REQUEST['ordersorttype'];
		}
		
		\Yii::$app->request->setQueryParams($_REQUEST);
		$sortConfig = new Sort(['attributes' => ['grand_total','create_time','order_source_create_time','paid_time','delivery_time']]);
		
		/*
		foreach ($sortConfig->getOrders() as $name=>$direction){
			$sort = $name;
			$order = $direction === SORT_ASC ? 'asc' : 'desc';
			if(!$sortConfig->enableMultiSort)
				break;
		}
		
		if(!empty($sort) && !empty($order)){
			$orderstr = "$sort $order";
		} else {
			$orderstr = "create_time DESC";
		}
		*/
		
		$data->orderBy($orderstr);
//	    $pagination = new Pagination(['totalCount' => $data->count(),'params'=>$_REQUEST]);
        $pagination = new Pagination(['totalCount' => $data->count(),'defaultPageSize'=>50 , 'pageSizeLimit'=>[1,200] ]);
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
	    
	    // 当前user 的puid 绑定的 ensogo 卖家账号
	    $puid = \Yii::$app->user->identity->getParentUid();
	    $ensogoUsers = SaasEnsogoUser::find()->where(['uid'=>$puid])->asArray()->all();
	    $ensogoUsersDropdownList = array();
	    foreach ($ensogoUsers as $ensogoUser){
	    	$ensogoUsersDropdownList[$ensogoUser['store_name']] = $ensogoUser['store_name'];
	    }
	    
	    $usertabs = Helper_Array::toHashmap(Helper_Array::toArray(Usertab::find()->all()),'id','tabname');
	    
	    $counter[OdOrder::STATUS_NOPAY] = OdOrder::find()->where(['order_source' => 'ensogo','order_status'=>OdOrder::STATUS_NOPAY])->count();
	    $counter[OdOrder::STATUS_PAY] = OdOrder::find()->where(['order_source' => 'ensogo' ,'order_status'=>OdOrder::STATUS_PAY ])->count();
	    $counter[OdOrder::STATUS_WAITSEND] = OdOrder::find()->where(['order_source' => 'ensogo','order_status'=>OdOrder::STATUS_WAITSEND ])->count();
	    $counter['all'] = OdOrder::find()->where(['order_source' => 'ensogo' ])->count();
	    $counter['guaqi'] = OdOrder::find()->where(['order_source' => 'ensogo','is_manual_order' => '1'])->count();
	    $counter[OdOrder::STATUS_SHIPPING] = OdOrder::find()->where(['order_source' => 'ensogo' , 'order_status'=>OdOrder::STATUS_SHIPPING])->count();
	    $counter[OdOrder::STATUS_SHIPPED] = OdOrder::find()->where(['order_source' => 'ensogo' , 'order_status'=>OdOrder::STATUS_SHIPPED])->count();
	    $counter[OdOrder::STATUS_CANCEL] = OdOrder::find()->where(['order_source' => 'ensogo' , 'order_status'=>OdOrder::STATUS_CANCEL])->count();
	    
	    $counter[OdOrder::EXCEP_HASMESSAGE] = OdOrder::find()->where(['order_source' => 'ensogo' , 'exception_status'=>OdOrder::EXCEP_HASMESSAGE])->count();
	    $counter[OdOrder::EXCEP_HASNOSHIPMETHOD] = OdOrder::find()->where(['order_source' => 'ensogo' , 'exception_status'=>OdOrder::EXCEP_HASNOSHIPMETHOD])->count();
	    $counter[OdOrder::EXCEP_PAYPALWRONG] = OdOrder::find()->where(['order_source' => 'ensogo' , 'exception_status'=>OdOrder::EXCEP_PAYPALWRONG])->count();
	    $counter[OdOrder::EXCEP_SKUNOTMATCH] = OdOrder::find()->where(['order_source' => 'ensogo' , 'exception_status'=>OdOrder::EXCEP_SKUNOTMATCH])->count();
	    $counter[OdOrder::EXCEP_NOSTOCK] = OdOrder::find()->where(['order_source' => 'ensogo' , 'exception_status'=>OdOrder::EXCEP_NOSTOCK])->count();
	    $counter[OdOrder::EXCEP_WAITMERGE] = OdOrder::find()->where(['order_source' => 'ensogo' , 'exception_status'=>OdOrder::EXCEP_WAITMERGE])->count();
	     
	    //获取国家列表
	    $countryArr = array();
// 	    $tmpCountryArr = \Yii::$app->get('db')->createCommand("select a.amazon_site_code,a.country_label,a.country_code from amazon_site a")->queryAll();
	     
// 	    foreach ($tmpCountryArr as $tmpCountry){
// 	    	$countryArr[$tmpCountry['country_code']] = $tmpCountry['country_label']."(".$tmpCountry['country_code'].")";
// 	    }
	    
	    $nations_array = EnsogoAccountsHelper::$DefaultOpenSiteList;
	    
	    if(is_array($nations_array)){
		    foreach ($nations_array as $nation_code){
		    	$nation_code = strtoupper($nation_code);
		    	$countryArr[$nation_code] = StandardConst::getNationChineseNameByCode($nation_code).'('.$nation_code.')';
		    }
	    }
	    
	    //end 获取国家列表
	     
	    //获取所有国家编码对应国家中文名称
	    $sysCountry = [];
	    $countryModels = SysCountry::find()->asArray()->all();
		foreach ($countryModels as $countryModel){
			$sysCountry[$countryModel['country_code']] = $countryModel['country_zh'];
		}
	    //end 获取所有国家编码对应国家中文名称
	     
	    //获取仓库列表
	    $warhouseArr = array();
	    $tmpWarhouseArr = Warehouse::find()->select(['warehouse_id','name'])->where(['is_active' => "Y"])->asArray()->all();
	    foreach ($tmpWarhouseArr as $tmpWarhouse){
	    	$warhouseArr[$tmpWarhouse['warehouse_id']] = $tmpWarhouse['name'];
	    }
	    //end 获取仓库列表
	    
	    
		return $this->render('list',array(
			'models' => $models,
		    'pages' => $pagination,
			'sort' => $sortConfig,
			'excelmodels'=>$excelmodels,
			'ensogoUsersDropdownList'=>$ensogoUsersDropdownList,
			'usertabs'=>$usertabs,
			'counter'=>$counter,
			'countryArr'=>$countryArr,
			'sysCountry'=>$sysCountry,
			'warhouseArr'=>$warhouseArr,
			'showsearch'=>$showsearch,
			'tag_class_list'=> OrderTagHelper::getTagColorMapping()
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
			$order->setAttributes($_tmp);
			$order->save();
			//存储订单对应商品
			foreach ($item_tmp['product_name'] as $key=>$val){
				if (strlen($item_tmp['itemid'][$key])){
					$item = OdOrderItem::findOne($item_tmp['itemid'][$key]);
				}else{
					$item = new OdOrderItem();
				}
				$item->order_id = $order->order_id;
				$item->product_name = $item_tmp['product_name'][$key];
				$item->sku = $item_tmp['sku'][$key];
				$item->ordered_quantity = $item_tmp['ordered_quantity'][$key];
				//$item->order_source_srn = $item_tmp['order_source_srn'][$key];
				$item->price = $item_tmp['price'][$key];
				$item->update_time = time();
				$item->create_time = is_null($item->create_time)?time():$item->create_time;
				$item->save();
			}
			$order->checkorderstatus();
			$order->save();
			OperationLogHelper::log('order',$order->order_id,'修改订单','编辑订单进行订单修改',\Yii::$app->user->identity->getFullName());
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
		
		AppTrackerApiHelper::actionLog("Oms-ensogo", "/order/ensogo/edit-page");
		return $this->render('edit',['order'=>$order,'countrys'=>StandardConst::$COUNTRIES_CODE_NAME_CN]);
	}
	
	/**
	 * 订单列表页面进行批量平台标记发货,提供给用户填写物流号，运输服务等的页面
	 * @author fanjs
	 */
	public function actionSignshipped(){
		AppTrackerApiHelper::actionLog("Oms-ensogo", "/order/ensogo/signshipped");
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
			AppTrackerApiHelper::actionLog("Oms-ensogo", "/order/ensogo/signshippedsubmit");
			$user = \Yii::$app->user->identity;
			$postarr = \Yii::$app->request->post();
			$tracker_provider_list  = EnsogoOrderInterface::getShippingCodeNameMap();
			if (count($postarr['order_id'])){
				foreach ($postarr['order_id'] as $oid){
					try {
						$order = OdOrder::findOne($oid);
						if (!empty($tracker_provider_list[$postarr['shipmethod'][$oid]])){
							$shipMethodName = $tracker_provider_list[$postarr['shipmethod'][$oid]];
						}else{
							header("Content-Type: text/html; charset=utf8");//保证为utf8 不乱码
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
			header("Content-Type: text/html; charset=utf8"); //保证为utf8 不乱码
			echo "<script language='javascript'>alert('操作已成功,即将关闭页面');window.close();</script>";
			return $this->render('//successview',['title'=>'平台标记发货']);
		}
		}
	}
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 手动同步ensogo订单,队列优先度提前
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
	function actionSyncmt(){
		
		$sync = SaasEnsogoUser::find()->where(['is_active'=>'1' , 'uid'=>\Yii::$app->user->id,])->all();
		
		return $this->renderPartial('syncmt',['sync'=>$sync]);
	}
	
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
				$model = SaasEnsogoUser::findOne(['site_id'=>$_POST['site_id']]);
				if (!empty($model)){
					$result = EnsogoAccountsHelper::setManualRetrieveOrder($_POST['site_id']);
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
		// 当前user 的puid 绑定的 ensogo 卖家账号
		$puid = \Yii::$app->user->identity->getParentUid();
		if ($puid !="1"){
			exit('no found');
		}
		
		if (!empty($_REQUEST['site_id'])   ){
			$msg = empty($_REQUEST['msg'])?"":$_REQUEST['msg'];
			$status = empty($_REQUEST['oq_status'])?"":$_REQUEST['oq_status'];
			EnsogoOrderHelper::unlockEnsogoOrderQueue($_REQUEST['site_id'],$msg , $status);
			exit('OK');
		}else{
			exit('no site id');
		}
	}
	
}

?>