<?php

namespace eagle\modules\statistics\controllers;

use Yii;

use eagle\modules\util\helpers\SysLogHelper;
use yii\filters\VerbFilter;
use yii\data\Sort;
use eagle\modules\app\apihelpers\AppTrackerApiHelper;
use yii\base\Exception;
use eagle\modules\catalog\models\ProductSuppliers;
use eagle\modules\util\helpers\ExcelHelper;
use eagle\modules\platform\apihelpers\PlatformAccountApi;
use eagle\modules\order\apihelpers\OrderApiHelper;
use eagle\widgets\SizePager;
use eagle\modules\order\helpers\CdiscountOrderInterface;
use eagle\modules\catalog\models\Product;
use eagle\modules\order\models\OdOrder;
use eagle\modules\order\models\OdOrderItem;
use eagle\modules\util\helpers\StandardConst;
use eagle\modules\util\helpers\ConfigHelper;
use eagle\modules\catalog\helpers\ProductApiHelper;
use eagle\models\OdOrderShipped;
use eagle\modules\dash_board\apihelpers\DashBoardStatisticHelper;
use eagle\modules\order\helpers\OrderGetDataHelper;
use eagle\modules\permission\apihelpers\UserApiHelper;
use eagle\modules\statistics\helpers\StatisticsHelper;
use eagle\modules\catalog\models\ProductBundleRelationship;
use eagle\modules\statistics\helpers\ProfitHelper;
use eagle\modules\util\helpers\RedisHelper;
use eagle\modules\order\helpers\OrderProfitHelper;

/**
 +----------------------------------------------------------
 * 统计
 +----------------------------------------------------------
 * log			name	date					note
 * @author		lrq 	2016/09/18				初始化
 +----------------------------------------------------------
 **/
class ProfitController extends \eagle\components\Controller
{
 
	public $enableCsrfValidation = FALSE;
	
    public function behaviors()
    {
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
                'class' => VerbFilter::className(),
                'actions' => [
                    'delete' => ['post'],
                ],
            ],
        ];
    }
	
	/**
	 +----------------------------------------------------------
	 * 利润计算界面
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		lrq 	2016/09/18				初始化
	 +----------------------------------------------------------
	 **/
    public function actionIndex()
    {
    	AppTrackerApiHelper::actionLog("statistics", "/statistics/profit/index");
    	
        //绑定平台、店铺信息
        $platformAccount = [];
        $stores = [];
        $platformAccountInfo = PlatformAccountApi::getAllAuthorizePlatformOrderSelleruseridLabelMap(false, false, true);//引入平台账号权限后
        foreach ($platformAccountInfo as $p_key=>$p_v)
        {
            if(!empty($p_v))
            {
                //已绑定平台
                $platformAccount[] = $p_key;
                
                foreach ($p_v as $s_key=>$s_v)
                {
                    //对应店铺信息
                    $stores[$s_v.' ( '.$p_key.' )'] = $s_key;
                }
            }
        }
        
        $sortConfig = new Sort(['attributes' => []]);
        
        //查询是否有显示权限
        $ischeck = UserApiHelper::checkOtherPermission('profix');
        
        return $this->render('index', [
            'profitData' => '',
        	'sort'=>$sortConfig,
            'platformAccount'=>$platformAccount,
            'stores'=>$stores,
            'ischeck'=>$ischeck,
        ]);
    }
    
    /**
     +----------------------------------------------------------
     * 获取利润明细列表
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * log			name	date					note
     * @author		lrq 	2016/09/20				初始化
     +----------------------------------------------------------
     **/
    public function actionGetOrderStatisticsInfo()
    {
    	AppTrackerApiHelper::actionLog("statistics", "/statistics/profit/get-order-statistics-info");
    	
        $params = [];
        $params['page'] = empty($_REQUEST['page']) ? 0 : $_REQUEST['page'];
        $params['per-page'] = empty($_REQUEST['per-page']) ? 20 : $_REQUEST['per-page'];
        $params['start_date'] = empty($_REQUEST['start_date']) ? '' : strtotime($_REQUEST['start_date']);
        if(!empty($_REQUEST['end_date']))
        {
            //期末时间增加一天
            $end_date = $_REQUEST['end_date'];
            $params['end_date'] = strtotime("$end_date +1 day");
        }
        else 
            $params['end_date'] = '';
        if(!empty($_REQUEST['search_txt'])){
        	$params['search_txt']  = str_replace('；', ';', $_REQUEST['search_txt']);
        	$params['search_txt']  = str_replace(' ', '', $_REQUEST['search_txt']);
        	$params['search_txt'] = rtrim($params['search_txt'],';');
        	$params['search_txt'] = explode(';', $params['search_txt']);
        }
        $params['search_type'] = empty($_REQUEST['search_type']) ? '' : $_REQUEST['search_type'];
        $params['selectplatform'] = empty($_REQUEST['selectplatform']) ? '' : explode(';', rtrim($_REQUEST['selectplatform'],';'));
        $params['selectstore'] = empty($_REQUEST['selectstore']) ? '' : explode(';', rtrim($_REQUEST['selectstore'],';'));
        $params['country'] = empty($_REQUEST['country']) ? '' : explode(',', rtrim($_REQUEST['country'],','));
        $is_sum = empty($_REQUEST['selectType']) ? 1 : 0;
        $params['date_type'] = empty($_REQUEST['date_type']) ? 'create_date' : $_REQUEST['date_type'];
        $params['order_type'] = empty($_REQUEST['order_type']) ? '' : explode(';', rtrim($_REQUEST['order_type'],';'));
        //print_r($is_sum);die;
    	$ret = OrderApiHelper::getOrderStatisticsInfo($params, $is_sum);

    	//分页信息
    	$ret['pagination'] = 
        	    SizePager::widget(['pagination'=>$ret['pagination'] , 'pageSizeOptions'=>array( 5 , 20 , 50 , 100 , 200 ) , 'class'=>'btn-group dropup']).
        	    '<div class="btn-group" style="width: 49.6%;text-align: right;">'.
        	    	\yii\widgets\LinkPager::widget(['pagination' => $ret['pagination'],'options'=>['class'=>'pagination']]).
        		"</div>";
    	
    	return json_encode($ret);
    }
    
    /**
     +----------------------------------------------------------
     * 显示利润编辑界面
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * log			name	date					note
     * @author		lrq 	2016/09/20				初始化
     +----------------------------------------------------------
     **/
    public function actionProfitOrder()
    {
    	$order_ids = [];
    	if(!empty($_REQUEST['order_ids']))
    		$order_ids = explode(',',$_REQUEST['order_ids']);
    
    	$check = self::checkOrdersBeforProfit($order_ids);
    	
    	if($check['success'])
    	{
    		return $this->renderAjax('_set_orders_cost',[
    				'order_ids'=>$order_ids,
    				'need_set_price'=>empty($check['data']['need_set_price'])?[]:$check['data']['need_set_price'],
    				'need_logistics_cost'=>empty($check['data']['need_logistics_cost'])?[]:$check['data']['need_logistics_cost'],
    				'exchange_data' => empty($check['data']['exchange'])?[]:$check['data']['exchange'],
    				'exchange_loss' => empty($check['data']['exchange_loss'])?[]:$check['data']['exchange_loss'],
    				]);
    	}
    	else 
    	    return '显示利润信息失败：'.$check['message'];
    }
    
    public function actionSetCostAndProfitOrder(){
    	AppTrackerApiHelper::actionLog("statistics", "/statistics/profit/set-cost-and-profit-order");
    	$data = $_POST;
    	$order_ids = explode(',', $_POST['order_ids']);
    	
    	$journal_id = SysLogHelper::InvokeJrn_Create("Statistics",__CLASS__, __FUNCTION__ , array($order_ids,$data));
    	$rtn = OrderProfitHelper::setOrderCost($data, $journal_id);
    	
    	if($rtn['success']){//所有成本设置成功，则进入下一步计算订单利润
    		
    		$price_type = empty($_POST['price_type'])?0:1;
    		$rtn = OrderProfitHelper::profitOrderByOrderId($order_ids,$price_type);
    			
    		$rtn['calculated_profit'] = true;
    			
    	}else{//有成本设置失败，则返回失败提示，并前段关闭价格设置窗口。
    		$rtn['calculated_profit'] = false;
    	}
    	exit(json_encode($rtn));
    }
    
    			
    
    public static function checkOrdersBeforProfit($order_ids=[]){
    	$uid = \Yii::$app->user->id;
    	$rtn['success'] = true;
    	$rtn['message'] = "";
    	$rtn['data'] = [];
    	$id_arr = [];
    	foreach ($order_ids as $order_id){
    		if(trim($order_id)!=='')
    			$id_arr[] = trim($order_id);
    	}
    	
    	if(!empty($id_arr)){
    		//@todo
    		//nonDeliverySku,暂时只有Cdiscount平台有。
    		$nonDeliverySku = CdiscountOrderInterface::getNonDeliverySku();
    			
    		$items = OdOrderItem::find()->where(['order_id'=>$id_arr])->andwhere("manual_status is null or manual_status!='disable'")->orderBy("order_item_id ASC")->asArray()->all();
    		$skus = [];
    		$order_itemids_info = [];
    		foreach ($items as $item){
    			if(!empty($item['root_sku'])){
    				$sku = $item['root_sku'];
    				if(!in_array($sku, $skus))
    					$skus[] = $sku;
    			}
    			else{
    				$sku = $item['sku']; 
    			}
    			if(in_array(strtoupper($sku),$nonDeliverySku))
    				continue;
    
    			$order_itemids_info[$sku]['source_itemid'] = $item['order_source_itemid'];
    			$rtn['data']['need_set_price'][$sku]['price_based_on_order'] = $item['purchase_price'];
    			$rtn['data']['need_set_price'][$sku]['photo_primary'] = $item['photo_primary'];
    			$rtn['data']['need_set_price'][$sku]['img'] = $item['photo_primary'];
    			$rtn['data']['need_set_price'][$sku]['name'] = $item['product_name'];
    			$rtn['data']['need_set_price'][$sku]['purchase_price'] = $item['purchase_price'];
    			$rtn['data']['need_set_price'][$sku]['additional_cost'] = '';
    		}
    		
    		//列出需要设置采购价 OR 其他成本 的商品
    		if(!empty($skus)){
    			$pds = Product::find()->where(['sku'=>$skus])->asArray()->all();
    			//未在商品模块建立的商品的sku
    			$sku_not_pd=[];
    			foreach ($skus as $sku){
    				$sku_not_pd[$sku] = $sku;
    			}
    
    			foreach ($pds as $pd){
    				unset($sku_not_pd[$pd['sku']]);
    					
    				$rtn['data']['need_set_price'][$pd['sku']]['purchase_price'] = $pd['purchase_price'];
    				$rtn['data']['need_set_price'][$pd['sku']]['additional_cost'] = $pd['additional_cost'];
    				$rtn['data']['need_set_price'][$pd['sku']]['img'] = $pd['photo_primary'];
    				$rtn['data']['need_set_price'][$pd['sku']]['name'] = $pd['prod_name_ch'];
    			}
    			
    			//查询对应的捆绑商品信息
    			$bundle = ProductBundleRelationship::find()->select(['bdsku', 'assku', 'qty'])->where(['bdsku' => $skus])->asArray()->all();
    			if(!empty($bundle)){
    				$assku_arr = [];
    				$bunle_arr = [];
    				foreach ($bundle as $val){
    					$assku_arr[] = $val['assku'];
    					$bunle_arr[$val['bdsku']][] = $val;
    				}
    				//查询子商品对应的采购价、其它成本
    				$assku_pro_arr = [];
    				$assku_pros = Product::find()->select(['sku', 'purchase_price', 'additional_cost'])->where(['sku' => $assku_arr])->asArray()->all();
    				foreach ($assku_pros as $val){
    					$assku_pro_arr[$val['sku']]['purchase_price'] = $val['purchase_price'];
    					$assku_pro_arr[$val['sku']]['additional_cost'] = $val['additional_cost'];
    				}
    				//重新计算捆绑商品采购价、其它成本
    				$purchase_price = 0;
    				$additional_cost = 0;
    				foreach ($bunle_arr as $bdsku => $bund){
    					if(!empty($rtn['data']['need_set_price'][$bdsku])){
	    					foreach ($bund as $assku_info){
	    						if(!empty($assku_info['qty'])){
		    						$assku = $assku_info['assku'];
		    						if(!empty($assku_pro_arr[$assku])){
		    							if(!empty($assku_pro_arr[$assku]['purchase_price'])){
		    								$purchase_price += $assku_pro_arr[$assku]['purchase_price'] * $assku_info['qty'];
		    							}
		    							if(!empty($assku_pro_arr[$assku]['additional_cost'])){
		    								$additional_cost += $assku_pro_arr[$assku]['additional_cost'] * $assku_info['qty'];
		    							}
		    						}
	    						}
	    					}
	    					
	    					if(!empty($purchase_price))
	    						$rtn['data']['need_set_price'][$bdsku]['purchase_price'] = $purchase_price;
	    					if(!empty($additional_cost))
	    						$rtn['data']['need_set_price'][$bdsku]['additional_cost'] = $additional_cost;
    					}
    				}
    			}
    		}
    		
    		//查询订单对应的物流成本、重量
    		$orders = OdOrder::find()->where(['order_id'=>$id_arr,/*'logistics_cost'=>null*/])->asArray()->all();
    		$currencies =[];
    		foreach ($orders as $od){
    			$rtn['data']['need_logistics_cost'][$od['order_id']] = [
    			'order_id'=>$od['order_id'],
    			'order_source_order_id'=>$od['order_source_order_id'],
    			'logistics_cost' => $od['logistics_cost'],
    			'logistics_weight' => $od['logistics_weight'],
    			];
    			if(!in_array($od['currency'], $currencies))
    				$currencies[] = $od['currency'];
    		}
    			
    		//所有用到的货币
    		$exchange_data = [];
    		$exchange_loss = [];
    		$exchange_config = ConfigHelper::getConfig("Profit/CurrencyExchange",'NO_CACHE');
    		if(empty($exchange_config))
    			$exchange_config = [];
    		else{
    			$exchange_config = json_decode($exchange_config,true);
    			if(empty($exchange_config))
    				$exchange_config = [];
    		}
    		//汇损
    		$exchange_loss_config = ConfigHelper::getConfig("Order/CurrencyExchangeLoss",'NO_CACHE');
    		if(empty($exchange_loss_config))
    			$exchange_loss_config = [];
    		else{
    			$exchange_loss_config = json_decode($exchange_loss_config,true);
    			if(empty($exchange_loss_config))
    				$exchange_loss_config = [];
    		}
    			
    		foreach ($currencies as $currency){
    			$currency = strtoupper($currency);
    			//币种对应的RMB汇率
    			$exchange_data[$currency] = '';//默认空值
    			if(isset($exchange_config[$currency])){
    				//采用用户设置过得人民币汇率
    				$exchange_data[$currency] = $exchange_config[$currency];
    			}
    			else{
    				if($currency == 'PH'){
    					$currency = 'PHP';
    				}
    				//获取最新汇率
    				$exchange_data[$currency] = \eagle\modules\statistics\helpers\ProfitHelper::GetExchangeRate($currency);
    				if(empty($exchange_data[$currency])){
    					$exchange_data[$currency] = '';
    				}
    			    
    				/*if(isset(StandardConst::$EXCHANGE_RATE_OF_RMB[$currency]))
    					$exchange_data[$currency] = StandardConst::$EXCHANGE_RATE_OF_RMB[$currency];*/
    			}
    			//币种对应汇损
    			$exchange_loss[$currency] = '';//默认空值
    			if(isset($exchange_loss_config[$currency])){
    				$exchange_loss[$currency] = $exchange_loss_config[$currency];
    			}
    		}
    		$rtn['data']['exchange'] = $exchange_data;
    		$rtn['data']['exchange_loss'] = $exchange_loss;
    	}else{//异常
    		$rtn['success'] = false;
    		$rtn['message'] = "没有指定需要处理的订单";
    		return $rtn;
    	}
    
    	return $rtn;
    }
    
    public static function getOrderItemLastPrice($sku){
    	$lastItem = OdOrderItem::find()->where(['sku'=>$sku])->orwhere(['root_sku'=>$sku])->andwhere("manual_status is null or manual_status!='disable'")->orderBy(" purchase_price_to DESC, order_item_id DESC ")->limit(1)->offset(0)->One();
    	if(!empty($lastItem)){
    		$lastPrice = $lastItem->purchase_price;
    		if(is_null($lastPrice)){
    			return false;
    		}else
    			return $lastPrice;
    	}else
    		return $lastPrice = 0.00;
    }
    
    protected static $EXCEL_PRODUCT_COST_COLUMN_MAPPING = array (
    		"A" => "sku", //SKU
    		"B" => "purchase_price", // 采购价
    		"C" => "additional_cost", // 其他费用
    );
    
    protected static $EXCEL_ORDER_LOGISTICS_COST_ORDERSOURCE_COLUMN_MAPPING = array (
    		"A" => "order_number", //原始订单号
    		"B" => "order_source", // 来源平台
    		"C" => "logistics_cost", // 物流成本
    		"D" => "logistics_weight", // 包裹重量
    );
    
    protected static $EXCEL_ORDER_LOGISTICS_COST_TRACKNUMBER_COLUMN_MAPPING = array (
    		"A" => "track_number", //跟踪号
    		"B" => "logistics_cost", // 物流成本
    		"C" => "logistics_weight", // 包裹重量
    );
    
    public function actionExcel2OrderCost()
    {
    	AppTrackerApiHelper::actionLog("statistics", "/statistics/profit/excel2OrderCost");
    	if (!empty ($_FILES["input_import_file"]))
    		$files = $_FILES["input_import_file"];
    	else
    		exit(json_encode(['success'=>false,'message'=>'文件上传失败！']));
    
    	$type = empty($_REQUEST['type'])?'':trim($_REQUEST['type']);
    	if(empty($type) || ($type!=='product_cost' && $type!=='logistics_cost_ordersource' && $type!=='logistics_cost_tracknumber')){
    		exit(json_encode(['success'=>false,'message'=>'上传类型未选择，或不支持该类型']));
    	}
    	
    	try {
    		if($type=='product_cost'){
    			$PRODUCT_COST_COLUMN_MAPPING = self::$EXCEL_PRODUCT_COST_COLUMN_MAPPING;
    			$productsData = \eagle\modules\util\helpers\ExcelHelper::excelToArray($files, $PRODUCT_COST_COLUMN_MAPPING );
    
    			$result = ProductApiHelper::importProductCostData($productsData);
    		}
    		else if($type=='logistics_cost_ordersource')
    		{
    			$ORDER_LOGISTICS_COST_ORDERSOURCE_COLUMN_MAPPING = self::$EXCEL_ORDER_LOGISTICS_COST_ORDERSOURCE_COLUMN_MAPPING;
    			$logisticsData = \eagle\modules\util\helpers\ExcelHelper::excelToArray($files, $ORDER_LOGISTICS_COST_ORDERSOURCE_COLUMN_MAPPING );

    			$result = self::importOrderLogisticsCostData($type, $logisticsData);
				 
				$rtn['calculated_profit'] = true;
    		}
    		else if($type=='logistics_cost_tracknumber')
    		{
    			$ORDER_LOGISTICS_COST_TRACKNUMBER_COLUMN_MAPPING = self::$EXCEL_ORDER_LOGISTICS_COST_TRACKNUMBER_COLUMN_MAPPING;
    			$logisticsData = \eagle\modules\util\helpers\ExcelHelper::excelToArray($files, $ORDER_LOGISTICS_COST_TRACKNUMBER_COLUMN_MAPPING );
    		
    			$result = self::importOrderLogisticsCostData($type, $logisticsData);
    			
    			$rtn['calculated_profit'] = true;
    		}
    	}
    	catch (Exception $e) {
    		SysLogHelper::SysLog_Create('Order',__CLASS__, __FUNCTION__,'error',$e->getMessage());
    		$result = ['success'=>false,'message'=>'E001：后台处理异常，请联系客服获取支援！'];
    	}
    	exit(json_encode($result));
    }
    
    /**
     * +----------------------------------------------------------
     * 导入订单的发货物流成本信息
     * +----------------------------------------------------------
     * @access static
     *+----------------------------------------------------------
     * @param	array
     *+----------------------------------------------------------
     * @return 	操作结果Array
     * +----------------------------------------------------------
     * log			name		date			note
     * @author 		lzhl		2016/03/16		初始化
     *+----------------------------------------------------------
     *
     */
    public static function importOrderLogisticsCostData($type, $logisticsData)
    {
    	$rtn['success'] = true;
    	$rtn['message'] = '';
    	$errMsg = '';
    
    	$journal_id = SysLogHelper::InvokeJrn_Create("Order", __CLASS__, __FUNCTION__ ,array($logisticsData));
    
    	if(!is_array($logisticsData)){
    		$rtn['success'] = false;
    		$rtn['message'] ='数据格式有误。';
    		return $rtn;
    	}
        $orderinfo = '';
        $exist_info = [];
        $ProfitAdds = [];
    	foreach ($logisticsData as $index=>$info)
    	{
    	    if($type == 'logistics_cost_ordersource')
    	    {
        		$order_no = trim($info['order_number']);
        		$orderinfo = '平台订单号：'.$order_no;
        		if(empty($order_no)){
        			$rtn['success'] = false;
        			$rtn['message'] .= '第'.$index.'行订单号为空，跳过该行处理;<br>';
        			continue;
        		}
        		$order_source = trim($info['order_source']);
        		$order_source = strtolower($order_source);
        		if(!empty(OdOrder::$orderSource))
        			$platforms = array_keys(OdOrder::$orderSource);
        		else
        			$platforms = ['ebay','amazon','aliexpress','wish','dhgate','cdiscount','lazada','linio','jumia','ensogo'];
        		if(!in_array($order_source,$platforms)){
        			$rtn['success'] = false;
        			$rtn['message'] .= '第'.$index.'行订单填入了错误的销售平台值，跳过该行处理;<br>';
        			continue;
        		}
        		$logistics_cost = floatval($info['logistics_cost']);
        		$logistics_weight = floatval($info['logistics_weight']);
        		
        		//符合条件的订单才可导入计算利润
    			$query = OdOrder::find();
    			$query = OrderGetDataHelper::formatQueryOrderConditionPurchase($query, true);
    			$query->andWhere("order_status=500");
    			$query->andWhere(['order_source_order_id'=>$order_no,'order_source'=>$order_source]);
    			$order = $query->orderby('order_id desc')->One();
    	    }
    	    else if($type == 'logistics_cost_tracknumber')
    	    {
    	        $track_number = trim($info['track_number']);
    	        $orderinfo = '跟踪号：'.$track_number;
    	        if(empty($track_number))
    	        {
    	        	$rtn['success'] = false;
    	        	$rtn['message'] .= '第'.$index.'行跟踪号为空，跳过该行处理;<br>';
    	        	continue;
    	        }
    	        //查询对应的跟踪信息
    	        $ship = OdOrderShipped::find()->select('order_id')->where(['tracking_number'=>$track_number])->orderby('id desc')->all();
    	        if(empty($ship))
	            {
	            	$rtn['success'] = false;
	            	$rtn['message'] .= '第'.$index.'行'.$orderinfo.'不存在，跳过该行处理;<br>';
	            	continue;
	            }
	            $order_id = array();
	            foreach($ship as $v){
	                $order_id[] = $v['order_id'];
	            }
	            
    	        $logistics_cost = floatval($info['logistics_cost']);
    	        $logistics_weight = floatval($info['logistics_weight']);
    	        
    	        //符合条件的订单才可导入计算利润
    	        $query = OdOrder::find();
    	        $query = OrderGetDataHelper::formatQueryOrderConditionPurchase($query, true);
    	        $query->andWhere("order_status=500");
    	        $query->andWhere(['order_id'=>$order_id]);
    	        $order = $query->orderby('order_id desc')->One();
    	    }
    	    
    		if(!empty($order))
    		{
    		    //当利润未计算过，则先根据商品采购价计算利润
    		    if(empty($order->profit))
    		    {
    		        $orderid = $order->order_id;
    		    	OrderProfitHelper::profitOrderByOrderId([$orderid], 0);
    		    	$order = OdOrder::find()->where(['order_id'=>$orderid])->One();
    		    }
    		    
    		    $dis_logistics_cost = $logistics_cost - $order->logistics_cost;
    			$order->logistics_cost = $logistics_cost;
    			$order->logistics_weight = $logistics_weight;
    			$order->profit = $order->profit - $dis_logistics_cost;
    
    			$transaction = \Yii::$app->get('subdb')->beginTransaction();
    			if(!$order->save(false))
    			{
    				$errMsg .= print_r($order->getErrors(),true);
    				$transaction->rollBack();
    				SysLogHelper::SysLog_Create('Order',__CLASS__, __FUNCTION__,'error',print_r($order->getErrors(),true));
    				$rtn['success'] = false;
    				$rtn['message'] .= $orderinfo.'物流成本修改失败;<br>';
    				continue;
    			}
    			
    			//整理需要更新的数据
    			$order_date = date("Y-m-d",$order->order_source_create_time);
    			$platform = $order->order_source;
				$order_type = $order->order_type;
    			$seller_id = $order->selleruserid;
				$order_status = $od->order_status;
    			$info = $order_date.'&'.$platform.'&'.$seller_id.'&'.$order_status;
    			if(!in_array($info, $exist_info))
    			{
    				$ProfitAdds[$info] = [
						'order_date' => $order_date,
						'platform' => $platform,
						'order_type'=>$order_type,
						'seller_id' => $seller_id,
						'profit_cny' => -$dis_logistics_cost,
						'order_status' => $order_status,
    				];
    				$exist_info[] = $info;
    			}
    			else
    				$ProfitAdds[$info]['profit_cny'] = $ProfitAdds[$info]['profit_cny'] - $dis_logistics_cost;
    			
    			$transaction->commit();
    		}
    		else
    		{
    			$rtn['success'] = false;
    			$rtn['message'] .= $orderinfo.'不存在，跳过该修改;<br>';
    		}
    	}
    	
    	//更新dash_board信息
    	foreach ($ProfitAdds as $key => $ProfitAdd)
    	{
    		DashBoardStatisticHelper::SalesProfitAdd($ProfitAdd['order_date'], $ProfitAdd['platform'],$ProfitAdd['order_type'],  $ProfitAdd['seller_id'], $ProfitAdd['profit_cny'], false, $ProfitAdd['order_status']);
    	}
    
    	$rtn['errMsg'] = $errMsg;
    	SysLogHelper::InvokeJrn_UpdateResult($journal_id, $rtn);
    
    	return $rtn;
    }
    
    /*
     * 导出利润信息
    */
    public function actionExportExcel()
    {
        
        AppTrackerApiHelper::actionLog("statistics", "/statistics/profit/export-excel");
        
        $params = [];
        $params['page'] = 1;
        $params['per-page'] = -1;
        $params['start_date'] = empty($_REQUEST['start_date']) ? '' : strtotime($_REQUEST['start_date']);
        if(!empty($_REQUEST['end_date']))
        {
        	//期末时间增加一天
        	$end_date = $_REQUEST['end_date'];
        	$params['end_date'] = strtotime("$end_date +1 day");
        }
        else
        	$params['end_date'] = '';
        if(!empty($_REQUEST['search_txt'])){
        	$params['search_txt']  = str_replace('；', ';', $_REQUEST['search_txt']);
        	$params['search_txt']  = str_replace(' ', '', $_REQUEST['search_txt']);
        	$params['search_txt'] = rtrim($params['search_txt'],';');
        	$params['search_txt'] = explode(';', $params['search_txt']);
        }
        $params['search_type'] = empty($_REQUEST['search_type']) ? '' : $_REQUEST['search_type'];
        $params['selectplatform'] = empty($_REQUEST['selectplatform']) ? '' : explode(';', rtrim($_REQUEST['selectplatform'],';'));
        $params['selectstore'] = empty($_REQUEST['selectstore']) ? '' : explode(';', rtrim($_REQUEST['selectstore'],';'));
        $params['country'] = empty($_REQUEST['country']) ? '' : explode(',', rtrim($_REQUEST['country'],','));
        $params['date_type'] = empty($_REQUEST['date_type']) ? 'create_date' : $_REQUEST['date_type'];
        $params['order_type'] = empty($_REQUEST['order_type']) ? '' : explode(';', rtrim($_REQUEST['order_type'],';'));
        $ret = OrderApiHelper::getOrderStatisticsInfo($params, 1);
        
    	$items_arr = ['time'=>'日期','order_id'=>'小老板单号','grand_total'=>'订单总价','commission_total'=>'佣金','paypal_fee'=>'paypal手续费','actual_charge'=>'实收费用','logistics_cost'=>'物流成本','purchase_cost'=>'采购成本','profit'=>'利润','profit_per'=>'成本利润率', 'sales_per' => '销售利润率',
    	                'logistics_weight'=>'包裹重量','order_source_order_id'=>'平台订单号','order_source'=>'平台','selleruserid'=>'店铺','service_name'=>'物流方式','tracking_number'=>'跟踪号',];
    	$keys = array_keys($items_arr);
    	$excel_data = [];
    	
    	foreach ($ret['data'] as $index=>$row)
    	{
    	    $row['time'] = $row['order_source_create_time'];
    		$tmp=[];
    		foreach ($keys as $key){
    			if(isset($row[$key])){
    				if(in_array($key,['order_id']) && is_numeric($row[$key]))
    					$tmp[$key]=' '.$row[$key];
    				else
    					$tmp[$key]=(string)$row[$key];
    			}
    			else
    				$tmp[$key]=$row[$key];
    		}
    		$excel_data[$index] = $tmp;
    	}
    	ExcelHelper::exportToExcel($excel_data, $items_arr, 'profit_'.date('Y-m-dHis',time()).".xls");
    }
    
    //手动同步最新已完成订单
    public function actionSynchronizeOrder()
    {
    	AppTrackerApiHelper::actionLog("statistics", "/statistics/profit/synchronize-order");
    	
        $uid = \Yii::$app->subdb->getCurrentPuid();
        if (empty($uid)){//异常情况
        	exit('您还未登录，不能进行该操作');
        }
        
        
        //未计算订单
		$query = OdOrder::find()->select("`order_id`");
		//筛选有效订单
		$query = OrderGetDataHelper::formatQueryOrderConditionPurchase($query, true);
		$query->andWhere("(profit is null || profit=0) and order_status=500 and paid_time>0");
		
		//只计算已绑定的账号的信息
		$bind_stores = '';
		$bind_order_souce = '';
		$platformAccountInfo = PlatformAccountApi::getAllAuthorizePlatformOrderSelleruseridLabelMap(false, false, true);//引入平台账号权限后
		foreach ($platformAccountInfo as $p_key=>$p_v){
			if(!empty($p_v)){
				foreach ($p_v as $s_key=>$s_v){
					$bind_stores[] = $s_key;
				}
			}
			$bind_order_souce[] = $p_key;
		}
			
		if($bind_stores != ''){
			$query->andWhere(['in','selleruserid',$bind_stores]);
		}
		if($bind_order_souce != ''){
			$query->andWhere(['in','order_source',$bind_order_souce]);
		}
		
		$orders = $query->orderBy("order_id desc")->limit(10000)->asArray()->all();
		
		if(!empty($orders))
		{
		    $orderids = [];
		    foreach ($orders as $order)
		        $orderids[] = $order['order_id'];
		    
		    if(!empty($orderids))
		    {
		        //分批计算，每次计算1000
		        $count = 0;
		        $batch_orderids = [];
		        for($num = 0; $num < count($orderids); $num++)
		        {
		            $count++;
		            $batch_orderids[] = $orderids[$num];
		            
		            if($count > 1000 || $num == count($orderids) - 1)
		            {
		                $rtn = OrderProfitHelper::profitOrderByOrderId($batch_orderids,0);
		                $count = 0;
		                $batch_orderids = [];
		            }
		        }
		        
		        return json_encode($rtn);
		    }
		}
		return json_encode(['succes'=>1, 'message'=>'']);
    }
    
	// /statistics/profit/refresh-rate-to-redis
    public function actionRefreshRateToRedis(){
    	ProfitHelper::RefreshRateToRedis();
    }
    
    public function actionShowRedisRate(){
    	$redis_key_lv1 = 'NewestExchangeRate';
	 	$redis_key_lv2 = 'NewestExchangeRate';
	 	$warn_record = RedisHelper::RedisGet($redis_key_lv1, $redis_key_lv2);
	 	if(!empty($warn_record)){
	 		$redis_val = json_decode($warn_record,true);
	 	}
	 	if(!empty($redis_val)){
	 		print_r($redis_val);
	 	}
    }
    
    //设置NGN汇率
    public function actionSetNgnRate(){
    	$redis_key_lv1 = 'NewestExchangeRate';
    	$redis_key_lv2 = 'NewestExchangeRate';
    	$warn_record = RedisHelper::RedisGet($redis_key_lv1, $redis_key_lv2);
    	if(!empty($warn_record)){
    		$redis_val = json_decode($warn_record,true);
    	}
    	if(!empty($redis_val)){
    	    $redis_val['data']['NGN'] = '0.0183';
    	    $ret = RedisHelper::RedisSet($redis_key_lv1, $redis_key_lv2, json_encode($redis_val));
    	}
    }
    
    //打开设置汇率界面
    public function actionShowSettingRate(){
    	$list = ProfitHelper::GetCurrencyInfo();
    	
    	return $this->renderPartial('setting_rate',[
    			'list' => $list]);
    }
    
    //保存汇率
    public function actionSaveRate(){
    	$ret = ProfitHelper::SaveCurrencyExchange($_REQUEST);
    	return json_encode($ret);
    }
    
    //重新同步利润信息到dash board
    public function actionTest1()
    {
    	$puid = \Yii::$app->user->identity->getParentUid();
    	$rtn = StatisticsHelper::RefreshDashBoardProfit($puid);
    	print_r($rtn);
    }
    
    //重新计算利润
    public function actionTest2()
    {
		$query = OdOrder::find()->select("`order_id`");
		//筛选有效订单
		$query = OrderGetDataHelper::formatQueryOrderConditionPurchase($query, true);
		$query->andWhere("order_status=500 and paid_time>0");
		if(!empty($_REQUEST['start_date'])){
			$query->andWhere("`order_source_create_time` is null or `order_source_create_time`>=".strtotime($_REQUEST['start_date']));
		}
		if(!empty($_REQUEST['end_date'])){
			//期末时间增加一天
			$end_date = $_REQUEST['end_date'];
			$query->andWhere("`order_source_create_time` is null or `order_source_create_time`<=".strtotime("$end_date +1 day"));
		}
		
		//只计算已绑定的账号的信息
		$bind_stores = '';
		$bind_order_souce = '';
		$platformAccountInfo = PlatformAccountApi::getAllAuthorizePlatformOrderSelleruseridLabelMap(false, false, true);//引入平台账号权限后
		foreach ($platformAccountInfo as $p_key=>$p_v){
			if(!empty($p_v)){
				foreach ($p_v as $s_key=>$s_v){
					$bind_stores[] = $s_key;
				}
			}
			$bind_order_souce[] = $p_key;
		}
		 
		if($bind_stores != ''){
			$query->andWhere(['in','selleruserid',$bind_stores]);
		}
		if($bind_order_souce != ''){
			$query->andWhere(['in','order_source',$bind_order_souce]);
		}
		
		$orders = $query->asArray()->all();
		$select_count = 0;
		if(!empty($orders))
		{
			$select_count = count($orders);
		    $orderids = [];
		    foreach ($orders as $order)
		        $orderids[] = $order['order_id'];
		    
		    if(!empty($orderids))
		    {
		        //分批计算，每次计算1000
		        $count = 0;
		        $batch_orderids = [];
		        for($num = 0; $num < count($orderids); $num++)
		        {
		            $count++;
		            $batch_orderids[] = $orderids[$num];
		            
		            if($count > 1000 || $num == count($orderids) - 1)
		            {
		                $rtn = OrderProfitHelper::profitOrderByOrderId($batch_orderids,0);
		                $count = 0;
		                $batch_orderids = [];
		            }
		        }
		    }
		}
		return json_encode(['succes'=>1, 'message'=>'', 'count'=>$select_count]);
    }
    
	//手动更新未计算采购成本订单的采购成本
    public function actionUpdateProductCostFromUnSet(){
    	ProfitHelper::updateProductCostFromUnSet();
    	
		return json_encode(['succes'=>1, 'message'=>'']);
    }
}
