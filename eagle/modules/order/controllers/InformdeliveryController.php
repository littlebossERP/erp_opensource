<?php
namespace eagle\modules\order\controllers;
use eagle\modules\util\helpers\TimeUtil;
use \Yii;
use yii\data\Pagination;
use eagle\modules\order\models\OdOrder;
use eagle\modules\util\helpers\ConfigHelper;
use eagle\modules\app\apihelpers\AppTrackerApiHelper;
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
use eagle\modules\order\helpers\AliexpressOrderHelper;
use eagle\modules\util\helpers\TranslateHelper;
use eagle\models\sys\SysCountry;
use eagle\modules\inventory\helpers\WarehouseHelper;
use eagle\modules\util\helpers\ExcelHelper;
use eagle\modules\listing\apihelpers\ListingAliexpressApiHelper;
use yii\db\Query;
use eagle\models\QueueSyncshipped;
use eagle\modules\order\apihelpers\OrderApiHelper;
use eagle\modules\platform\apihelpers\AliexpressAccountsApiHelper;
use eagle\models\SaasCdiscountUser;
use eagle\modules\order\helpers\CdiscountOrderHelper;


class InformdeliveryController extends \eagle\components\Controller
{
    public $enableCsrfValidation = false;

    /**
     * 未通知平台发货界面
     * dwg
     */
    public function actionNoinformdelivery()
    {

    	AppTrackerApiHelper::actionLog("Oms-aliexpress", "/order/aliexpress/list");
    	
    	$pageSize = isset($_GET['per-page'])?$_GET['per-page']:20;
    	$data=OdOrder::find();
    	$data->andWhere(['order_source'=>'aliexpress']);
    	$data->andWhere(['order_source_status'=>'WAIT_SELLER_SEND_GOODS']);
    	$data->andWhere(['shipping_status'=>OdOrder::NO_INFORM_DELIVERY]);
    //不显示 解绑的账号的订单 start
		$tmpSellerIDList =  AliexpressAccountsApiHelper::getAllAccounts(\Yii::$app->user->identity->getParentUid());
		$aliAccountList = [];
		foreach($tmpSellerIDList as $tmpSellerRow){
			$aliAccountList[] = $tmpSellerRow['sellerloginid'];
		}
		
		if (!empty($aliAccountList)){
			//不显示 解绑的账号的订单
			$data->andWhere(['selleruserid'=>$aliAccountList]);
		}
		if (!empty($_REQUEST['selleruserid'])){
			//搜索卖家账号
			$data->andWhere('selleruserid = :s',[':s'=>$_REQUEST['selleruserid']]);
		}
		
		if (!empty($_REQUEST['searchval'])){
			//搜索用户自选搜索条件
			if (in_array($_REQUEST['keys'], ['order_id','order_source_order_id'])){
				$kv=[
						'order_id'=>'order_id',
						'order_source_order_id'=>'order_source_order_id',
						
						];
				$key = $kv[$_REQUEST['keys']];
				if(!empty($_REQUEST['fuzzy'])){
					$data->andWhere("$key like :val",[':val'=>"%".$_REQUEST['searchval']."%"]);
				}else{
					$data->andWhere("$key = :val",[':val'=>$_REQUEST['searchval']]);
				}
		
			}elseif ($_REQUEST['keys']=='tracking_number'){
				if(!empty($_REQUEST['fuzzy'])){
					$ids = Helper_Array::getCols(OdOrderShipped::find()->where('tracking_number like :tn',[':tn'=>"%".$_REQUEST['searchval']."%"])->select('order_id')->asArray()->all(),'order_id');
				}else{
					$ids = Helper_Array::getCols(OdOrderShipped::find()->where('tracking_number = :tn',[':tn'=>$_REQUEST['searchval']])->select('order_id')->asArray()->all(),'order_id');
				}
				$data->andWhere(['IN','order_id',$ids]);
			}
		}
		$data->orderBy('order_source_create_time desc');
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
		//订单数量统计
		if (!empty($aliAccountList)){
			//不显示 解绑的账号的订单
			$counter = AliexpressOrderHelper::getMenuStatisticData(['selleruserid'=>$aliAccountList]);
		}else{
			$counter = AliexpressOrderHelper::getMenuStatisticData();
		}
		$selleruserids=Helper_Array::toHashmap(SaasAliexpressUser::find()->where(['uid'=>\Yii::$app->user->identity->getParentUid()])->select(['sellerloginid'])->asArray()->all(),'sellerloginid','sellerloginid');
        return $this->render('noinformdelivery',array(
        		'models' => $models,
        		'pages' => $pages,
        		'counter'=>$counter,
        		'selleruserids'=>$selleruserids,
        ));

    }

    //选择本地excel上传界面
    public function actionImporttrackingnopage()
    {
        return $this->renderAjax('importtrackingnopage');
    }
    //选择本地excel上传界面
    public function actionImporttrackingnumpage()
    {
        return $this->renderAjax('importtrackingnumpage');
    }

    //Excel表数据导入成数组的key值
    private static $TRACK_NUM_EXCEL_COLUMN_MAPPING = [
        "A" => "orderid",
        "B" => "tracknum",
        "C" => "server",
        "D" => "tracklink",
    ];




    /**
     * 待获取物流号
     */
    public function actionWaitinggettrackingno(){
        AppTrackerApiHelper::actionLog("eagle_v2","/carrier/default/waitinggettrackingno");
        $pageSize = isset($_GET['per-page'])?$_GET['per-page']:20;
        if (Yii::$app->request->isPost){
            $order_id = Yii::$app->request->post('order_id');
            $customer_number = Yii::$app->request->post('customer_number');
            $default_shipping_method_code = Yii::$app->request->post('default_shipping_method_code');
            $default_carrier_code = Yii::$app->request->post('default_carrier_code');
            $data = Yii::$app->request->post();
        }else{
            $order_id = Yii::$app->request->get('order_id');
            $customer_number = Yii::$app->request->get('customer_number');
            $default_shipping_method_code = Yii::$app->request->get('default_shipping_method_code');
            $default_carrier_code = Yii::$app->request->get('default_carrier_code');
            $data = Yii::$app->request->get();
        }
        $query = OdOrder::find()->where('order_status = 300 and is_manual_order = 0  and carrier_step = '.OdOrder::CARRIER_WAITING_GETCODE);
        if (isset($order_id) && strlen($order_id)){
            $query->andWhere(['order_id'=>$order_id]);
        }
        if (isset($customer_number) && strlen($customer_number)){
            $query->andWhere(['customer_number'=>$customer_number]);
        }
        if (isset($default_shipping_method_code) && strlen($default_shipping_method_code)){
            $query->andWhere(['default_shipping_method_code'=>$default_shipping_method_code]);
        }
        if (isset($default_carrier_code) && strlen($default_carrier_code)){
            $query->andWhere(['default_carrier_code'=>$default_carrier_code]);
        }

        $pagination = new Pagination([
            'defaultPageSize' => 20,
            'pageSize' => $pageSize,
            'totalCount' => $query->count(),
            'pageSizeLimit'=>[5,200],//每页显示条数范围
            'params'=>$data,
        ]);
        $result['pagination'] = $pagination;
        $query->orderBy('order_id desc');
        $query->limit($pagination->limit);
        $query->offset( $pagination->offset );
        $result['data'] = $query->all();
        $services = CarrierApiHelper::getShippingServices();
        $carriers = CarrierApiHelper::getCarriers();
        //$selleruserids=Helper_Array::toHashmap(SaasEbayUser::find()->where(['uid'=>Yii::$app->user->identity->getParentUid()])->orderBy('selleruserid')->select(['selleruserid'])->asArray()->all(),'selleruserid','selleruserid');
        return $this->render('waitinggettrackingno',['orders'=>$result,'services'=>$services,'carriers'=>$carriers,'search_data'=>$data,'tag_class_list'=> OrderTagHelper::getTagColorMapping()]);
    }




    /**
     * 订单列表页面进行批量平台标记发货,插入标记队列
     * @author million
     */
    public function actionSignshippedsubmit(){

        $result['title'] = 'Aliexpress标记发货完成';
        $result['message'] = '标记结果可查看Aliexpress状态';

        if (\Yii::$app->request->getIsPost()){
            AppTrackerApiHelper::actionLog("Oms-aliexpress", "/order/aliexpress/signshippedsubmit");
            $user = \Yii::$app->user->identity;
            $postarr = \Yii::$app->request->post();
            $dataArr = array();
            $dataArr['order_id'] = $postarr['order_id'];
            $dataArr['order_source_order_id'] = $postarr['order_source_order_id'];
            $dataArr['shipmethod'] = array_combine($postarr['order_id'],$postarr['shipmethod']);
            $dataArr['tracknum'] = array_combine($postarr['order_id'],$postarr['tracknum']);
            $dataArr['signtype'] = array_combine($postarr['order_id'],$postarr['signtype']);
            $dataArr['trackurl'] = array_combine($postarr['order_id'],$postarr['trackurl']);
            $dataArr['message'] = array_combine($postarr['order_id'],$postarr['message']);

            $ali = AliexpressInterface_Helper::getShippingCodeNameMap();
            if (count($dataArr['order_id']))
            {
                foreach ($dataArr['order_id'] as $oid){
                    try {
                        $shipping_method_code = strlen($dataArr['shipmethod'][$oid])>0?$dataArr['shipmethod'][$oid]:'Other';
                        $order = OdOrder::findOne($oid);
                        $logisticInfoList=[
                            '0'=>[
                                'order_source'=>$order->order_source,
                                'selleruserid'=>$order->selleruserid,
                                'tracking_number'=>$dataArr['tracknum'][$oid],
                                'tracking_link'=>$dataArr['trackurl'][$oid],
                                'shipping_method_code'=>$shipping_method_code,
                                'shipping_method_name'=>$ali[$shipping_method_code],//平台物流服务名
                                'order_source_order_id'=>$order->order_source_order_id,
                                'description'=>$dataArr['message'][$oid],
                                'signtype'=>$dataArr['signtype'][$oid],
                                'addtype'=>'手动标记发货',
                            ]
                        ];
                        if(!OrderHelper::saveTrackingNumber($oid, $logisticInfoList,0,1)){
                            \Yii::error(["Order",__CLASS__,__FUNCTION__,"Online",'订单'.$oid.'插入失败'],'edb\global');
                        }else{
                            //标记成功， 就检查虚假发货

                            $thisRt = OrderHelper::MarkOnlyPlatformShipped($order);

                            if ($thisRt['success'] == false){
                                \Yii::error(["Order",__CLASS__,__FUNCTION__,"Online",'订单'.$oid.(empty($thisRt['message'])?"未知错误":$thisRt['message'])],'edb\global');
                            }
                        };
                    }catch (\Exception $ex){
                        \Yii::error(["Order",__CLASS__,__FUNCTION__,"Online","save to SignShipped failure:".print_r($ex->getMessage())],'edb\global');
                    }
                }

                return $this->render('//successview',['title'=>'Aliexpress标记发货完成','message'=>'标记结果可查看Aliexpress状态']);
//                return json_encode($result);

            }
        }


    }






    /**
     * 正在通知平台发货界面
     * dwg
     */
    public function actionProcessinformdelivery()
    {
        $pageSize = isset($_GET['per-page'])?$_GET['per-page']:20;

        $data = OdOrder::find();
        $data->andWhere(['order_source'=>'aliexpress']);
		$data->andWhere(['shipping_status'=>OdOrder::PROCESS_INFORM_DELIVERY]);

        $showsearch=0;
        $op_code = '';

        //组织数据
        $selleruserid = isset($_REQUEST['selleruserid'])?trim($_REQUEST['selleruserid']):"";//ok
        $keys = isset($_REQUEST['keys'])?trim($_REQUEST['keys']):"";//ok
        $searchval = isset($_REQUEST['searchval'])?trim($_REQUEST['searchval']):"";//ok
        $fuzzy = isset($_REQUEST['fuzzy'])?trim($_REQUEST['fuzzy']):"";//ok


        ///精确搜索
        if (!empty($searchval)){
            //搜索用户自选搜索条件
            if (in_array($keys, ['order_id','order_source_order_id'])){
                $kv=[
                    'order_id'=>'order_id',
                    'order_source_order_id'=>'order_source_order_id',
                ];
                $key = $kv[$keys];
                if(!empty($fuzzy)){
                    $data->andWhere("$key like :val",[':val'=>"%".$searchval."%"]);
                }else{
                    $data->andWhere("$key = :val",[':val'=>$searchval]);
                }

            }
        }

        //必须加上一个默认排序
        $ordersort = '';
        $ordersort .= 'order_id desc';

        //卖家账号
        if(!empty($selleruserid)){
            //搜索卖家账号
            $data->andWhere('selleruserid = :selleruserid',[':selleruserid'=>$_REQUEST['selleruserid']]);
        }


        $pages = new Pagination([
            'defaultPageSize' => 20,
            'pageSize' => $pageSize,
            'totalCount' => $data->count(),
            'pageSizeLimit'=>[5,200],//每页显示条数范围
            'params'=>$_REQUEST,
        ]);

        $models = $data->offset($pages->offset)
            ->orderBy($ordersort)
            ->limit($pages->limit)
            ->asArray()
            ->all();


        /*echo models current sql */
        $command = $data->offset($pages->offset)
            ->limit($pages->limit)->createCommand();
//        echo $command->getRawSql();




        //订单数量统计
        $counter = AliexpressOrderHelper::getMenuStatisticData();
        $selleruserids=Helper_Array::toHashmap(SaasAliexpressUser::find()->where(['uid'=>\Yii::$app->user->identity->getParentUid()])->select(['sellerloginid'])->asArray()->all(),'sellerloginid','sellerloginid');

        
        //获取物流号
        foreach($models as &$row){
        	//获取队列表中未处理数据
        	$QSS = QueueSyncshipped::find()->where(['selleruserid'=>$row['selleruserid'] , 'order_source_order_id'=>$row['order_source_order_id'] , 'order_source'=>$row['order_source'] ,'uid'=>\Yii::$app->user->identity->getParentUid()])->asArray()->one();
        	if (!empty($QSS)){
        		$row['tracking_number'] = $QSS['tracking_number'];
        		$row['signtype'] = $QSS['signtype'];
        		$row['status'] = $QSS['status'];
        	}else{
        		$row['tracking_number'] = '';
        		$row['signtype'] = '';
        		$row['status'] = '';
        	}
        	
        	//获取最新的一条order ship 记录
        	$OS = OdOrderShipped::find()->where(['order_id'=>$row['order_id']])->orderBy('created desc')->asArray()->one();
        	if (!empty($OS)){
        		$row['shipping_method_name'] = $OS['shipping_method_name'];
        		$row['description'] = $OS['description'];
        		
        		
        	}else{
        		$row['shipping_method_name'] = '';
        		$row['description'] = '';
        		$row['status'] = '';
        	}
        	
        }

//        //获取复选框已选的order_id
//        $order_ids = null;
//        if(isset($_POST['order_id'])) {
//            $_SESSION['order_id'] = $_POST['order_id'];
//        }

        return $this->render('processinformdelivery',array(
            'models' => $models,
            'pages' => $pages,
            'counter'=>$counter,//页面左导航
            'selleruserids'=>$selleruserids,//卖家账号
            'showsearch'=>$showsearch,
//            'order_ids'=>$order_ids,//复选框选中的订单id
        ));

    }


    //停止发货通知
    public function actionStopdelivery(){
        $order_id = $_REQUEST['order_id'];

        $row = OdOrder::find()->where(['order_id'=>$order_id])->asArray()->one();
        
        //1.清空所有 同步订单队列
        $res = QueueSyncshipped::deleteAll(['selleruserid'=>$row['selleruserid'] , 'order_source_order_id'=>$row['order_source_order_id'] , 'order_source'=>$row['order_source'] ,'uid'=>\Yii::$app->user->identity->getParentUid()]);
        
        //2.调用 重置订单 ，shihpping status 设置为 0 
        OrderApiHelper::setOrderShippingStatus($row['order_source_order_id'], false);
        $result['message'] = null;
        if($res){
            $result['message'] = '操作成功！';
            return json_encode($result);
        }
        else{
            $result['message'] = '已经停止，请不要重复操作！';
            return json_encode($result);
        }
    }




    /**
     * 已通知平台发货界面
     * dwg
     */
    public function actionAlreadyinformdelivery()
    {

//        $order_id_long_arr = OdOrder::find()->select('order_id')->where(['order_source'=>'aliexpress'])->andWhere(['shipping_status'=>$_REQUEST['shipping_status']])->asArray()->all();
//        foreach($order_id_long_arr as $order_id_long){  //使用order_id字段前面加‘0’，填充成11长度
//            $order_id_short_arr[] = (int)$order_id_long['order_id'];
//        }

        $conn=\Yii::$app->subdb;
        $data = new Query;

        $pageSize = isset($_GET['per-page'])?$_GET['per-page']:20;
        $platform = empty($_GET['platform'])?'aliexpress':$_GET['platform'];

        $data->select("a1.*")
            ->from("od_order_shipped_v2 a1")
            ->leftJoin("od_order_v2 t", " a1.order_id = t.order_id")
            ->where(['and',"t.order_source='$platform' and t.shipping_status='".$_REQUEST['shipping_status']."' and a1.order_id= t.order_id"]);

//        $data = OdOrderShipped::find();
//        $data->andWhere(['order_source'=>'aliexpress']);
//        $data->andWhere(['in','order_id',$order_id_short_arr]);

//        $data->andWhere(['shipping_status'=>$_REQUEST['shipping_status']]);

        $showsearch=0;
        $op_code = '';

        //组织数据
        $selleruserid = isset($_REQUEST['selleruserid'])?trim($_REQUEST['selleruserid']):"";//ok
        $keys = isset($_REQUEST['keys'])?trim($_REQUEST['keys']):"";//ok
        $searchval = isset($_REQUEST['searchval'])?trim($_REQUEST['searchval']):"";//ok
        $fuzzy = isset($_REQUEST['fuzzy'])?trim($_REQUEST['fuzzy']):"";//ok



        ///精确搜索
        if (!empty($searchval)){

            //搜索用户自选搜索条件
            if (in_array($keys, ['order_id','order_source_order_id','tracking_number'])){
                $kv=[
                    'order_id'=>'t.order_id',
                    'order_source_order_id'=>'t.order_source_order_id',
                    'tracking_number' => 'a1.tracking_number',
                ];
                $key = $kv[$keys];
                if(!empty($fuzzy)){
                    $data->andWhere("$key like :val",[':val'=>"%".$searchval."%"]);
                }else{
                    $data->andWhere("$key = :val",[':val'=>$searchval]);
                }

            }
        }

        //必须加上一个默认排序
        $ordersort = '';
        $ordersort .= 'order_id desc';

        //卖家账号
        if(!empty($selleruserid)){
            //搜索卖家账号
            $data->andWhere('t.selleruserid = :selleruserid',[':selleruserid'=>$_REQUEST['selleruserid']]);
        }

        $DataCount = $data->count("1", $conn);

        $pages = new Pagination([
            'defaultPageSize' => 20,
            'pageSize' => $pageSize,
//            'totalCount' => $data->count(),
            'totalCount' => $DataCount,
            'pageSizeLimit'=>[5,200],//每页显示条数范围
            'params'=>$_REQUEST,
        ]);

        $models = $data->offset($pages->offset)
            ->orderBy($ordersort)
            ->limit($pages->limit)
//            ->all();
            ->createCommand($conn)->queryAll();

//        var_dump($models);exit;

        /*echo models current sql */
        $command = $data->offset($pages->offset)
            ->limit($pages->limit)->createCommand();
//        echo $command->getRawSql();




        //订单数量统计
        switch ($platform){
        	case 'aliexpress':
        		$counter = AliexpressOrderHelper::getMenuStatisticData();
        		$selleruserids=Helper_Array::toHashmap(SaasAliexpressUser::find()->where(['uid'=>\Yii::$app->user->identity->getParentUid()])->select(['sellerloginid'])->asArray()->all(),'sellerloginid','sellerloginid');
        		break;
        	case 'cdiscount':
        		$counter = CdiscountOrderHelper::getMenuStatisticData();
        		$selleruserids=Helper_Array::toHashmap(SaasCdiscountUser::find()->where(['uid'=>\Yii::$app->user->identity->getParentUid()])->select(['username'])->asArray()->all(),'username','username');
        		break;
        }


        //获取复选框已选的order_id
        $order_ids = null;
        if(isset($_POST['order_id'])) {
            $_SESSION['order_id'] = $_POST['order_id'];

        }
//        print_r(isset($_POST['order_id'])?$_POST['order_id']:'');
        //获取导入的excel数据
        $excel_data = null;
        if(isset($_FILES['inputExcel'])) {
            $excel_data = ExcelHelper::excelToArray($_FILES['inputExcel'], self::$TRACK_NUM_EXCEL_COLUMN_MAPPING, true);

            if(isset($_SESSION['order_id'])){
                $order_ids = $_SESSION['order_id'];
                unset($_SESSION['order_id']);
            }
//            var_dump($excel_data);
//            var_dump($order_ids);

        }

        return $this->render('alreadyinformdelivery',array(
            'models' => $models,
            'pages' => $pages,
            'counter'=>$counter,//页面左导航
            'selleruserids'=>$selleruserids,//卖家账号
            'showsearch'=>$showsearch,
            'order_ids'=>$order_ids,//复选框选中的订单id
            'excel_data'=>$excel_data,//excel导入的数据
        ));

    }

    /**
     * 声明发货
     * $selleruserid 平台登录账号（获取token使用）
     * $serviceName 物流服务KEY	UPS
     * $logisticsNo 物流追踪号	20100810142400000-0700
     * description      交易订单收货国家(简称)	FJ,Fiji;FI,Finland;FR,France;
     * $sendType      状态包括：全部发货(all)、部分发货(part)
     * $outRef      用户需要发货的订单id
     * $trackingWebsite      当serviceName=Other的情况时，需要填写对应的追踪网址
     */
    public function actionSellershipment(){
        //组织数据
        $description = isset($_REQUEST['description'])?trim($_REQUEST['description']):"";//ok
        $tracking_website = isset($_REQUEST['tracking_website'])?trim($_REQUEST['tracking_website']):"";//ok
        $tracking_number = isset($_REQUEST['tracking_number'])?trim($_REQUEST['tracking_number']):"";//ok
        $sendType = isset($_REQUEST['sendType'])?trim($_REQUEST['sendType']):"";//ok
        $serviceName = isset($_REQUEST['serviceName'])?trim($_REQUEST['serviceName']):"";//ok
        $order_source_order_id = isset($_REQUEST['order_source_order_id'])?trim($_REQUEST['order_source_order_id']):"";//ok


        //相应的用户账号
        $order_short_id = isset($_REQUEST['orderid'])?trim($_REQUEST['orderid']):"";//ok
        $order_long_id = str_pad($order_short_id, 11, "0", STR_PAD_LEFT);
        $selleruseridArr = OdOrder::find()->select('selleruserid')->where(['order_id'=>$order_long_id])->asArray()->all();
        $res = ListingAliexpressApiHelper::sellerShipment($selleruseridArr[0]['selleruserid'],$serviceName,$tracking_number,$description,$sendType,$order_source_order_id,$tracking_website);
//        $res = ListingAliexpressApiHelper::sellerShipment('cn1510671045','SGP','RF376636563SG','','all','72656299635909','http://www.17track.net');

//        print_r($res);exit;

        if(isset($res['error_code']) && !empty($res['error_code'])){
            return json_encode(array('error'=>1,'message' => '声明发货失败！<br/>错误码：'.$res['error_code'].'<br/>失败原因：'.$res['error_message']));
        }
        if(isset($res['success']) && $res['success']==true){
            return json_encode(array('error'=>0,'message' =>'声明发货成功！'));
        }
        else{
            return json_encode(array('error'=>1,'message' =>'声明发货失败！'.$res['msg']));
        }
    }


    /**
    修改声明发货
     * $selleruserid  平台登录账号（获取token使用）
     * $old_serviceName  用户需要修改的的老的发货物流服务
     * $old_tracking_number  用户需要修改的老的物流追踪号
     * $new_serviceName  新物流服务KEY	UPS
     * $new_tracking_number  新物流追踪号	20100810142400000-0700
     * $description      备注(只能输入英文)
     * $sendType      状态包括：全部发货(all)、部分发货(part)
     * $order_source_order_id      用户需要发货的订单id
     * $tracking_website      当serviceName=Other的情况时，需要填写对应的追踪网址

     */
    public function actionSellermodifiedshipment(){

        //组织数据
        $description = isset($_REQUEST['description'])?trim($_REQUEST['description']):"";//ok
        $tracking_website = isset($_REQUEST['tracking_website'])?trim($_REQUEST['tracking_website']):"";//ok
        $sendType = isset($_REQUEST['sendType'])?trim($_REQUEST['sendType']):"";//ok
        $old_tracking_number = isset($_REQUEST['old_tracking_number'])?trim($_REQUEST['old_tracking_number']):"";//ok
        $old_serviceName = isset($_REQUEST['old_serviceName'])?trim($_REQUEST['old_serviceName']):"";//ok
        $new_tracking_number = isset($_REQUEST['new_tracking_number'])?trim($_REQUEST['new_tracking_number']):"";//ok
        $new_serviceName = isset($_REQUEST['new_serviceName'])?trim($_REQUEST['new_serviceName']):"";//ok
        $order_source_order_id = isset($_REQUEST['order_source_order_id'])?trim($_REQUEST['order_source_order_id']):"";//ok

//        exit(print_r(\Yii::$app->request->post()));


        //相应的用户账号
        $order_short_id = isset($_REQUEST['orderid'])?trim($_REQUEST['orderid']):"";
        $order_long_id = str_pad($order_short_id, 11, "0", STR_PAD_LEFT);
        $selleruseridArr = OdOrder::find()->select('selleruserid')->where(['order_id'=>$order_long_id])->asArray()->all();
        $res = ListingAliexpressApiHelper::sellerModifiedShipment($selleruseridArr[0]['selleruserid'],$old_serviceName,$old_tracking_number,$new_serviceName,$new_tracking_number,$description,$sendType,$order_source_order_id,$tracking_website);


        if(isset($res['error_code']) && !empty($res['error_code'])){
            return json_encode(array('error'=>1,'message' => '修改发货失败！<br/>错误码：'.$res['error_code'].'<br/>失败原因：'.$res['error_message']));
        }
        if(isset($res['success']) && $res['success']==true){
            return json_encode(array('error'=>0,'message' =>'修改发货成功！'));
        }
        else{
            return json_encode(array('error'=>1,'message' =>'修改发货失败！'.$res['msg']));
        }

    }




}

?>