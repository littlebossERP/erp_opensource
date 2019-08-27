<?php 
namespace eagle\modules\carrier\controllers;

use Yii;
use \Exception;
use yii\web\Controller;
use common\api\carrierAPI;
use common\helpers\Helper_Array;
use common\helpers\simple_html_dom;

use eagle\models\catalog\Product;
use eagle\models\carrier\CrTemplate;

use eagle\modules\order\models\OdOrder;
use eagle\modules\order\models\OdOrderItem;
use eagle\modules\carrier\models\SysShippingService;
use eagle\modules\carrier\models\SysCarrier;
use eagle\modules\carrier\models\SysCarrierAccount;
use eagle\modules\order\models\OdOrderShipped;
use eagle\modules\carrier\apihelpers\CarrierApiHelper;
use eagle\modules\order\helpers\OrderHelper;
use eagle\modules\carrier\helpers\CarrierHelper;
use eagle\modules\carrier\models\SysTrackingNumber;
use eagle\modules\util\helpers\BarcodeHelper;
use eagle\modules\app\apihelpers\AppTrackerApiHelper;
use eagle\modules\util\helpers\OperationLogHelper;
use eagle\models\SysShippingMethod;
use eagle\modules\util\helpers\ConfigHelper;
use common\helpers\Helper_Curl;
use eagle\models\CrCarrierTemplate;
use eagle\modules\order\apihelpers\OrderApiHelper;
use eagle\models\CarrierUserLabel;
use eagle\modules\util\helpers\PDFMergeHelper;

/*
 * 物流操作动作执行类
 *
 *
 */
class CarrieroperateController extends \eagle\components\Controller
{
    public $enableCsrfValidation = false;
    public static $orderObjs = null;
    public static $orders = null;
    public static $nums = null;
    public static $step = null;
    public function __construct($id,$module,$config=[]){
        parent::__construct($id,$module,$config);
        $this->layout = 'carrier';
        //如果是瀑布过来的 则使用传递过来的ids
        //判断出错之后进行的操作
        if(isset($_GET['ids'])){
            $_GET['order_id'] = $_GET['ids'];
        }
        $route = explode('/', yii::$app->requestedRoute);
        $operate = $route[2];
        $arr = [
            'getorderno'=>0,
            'dodispatch'=>1,
            'gettrackingno'=>2,
            'doprint'=>3,
            'cancelorderno'=>4,
            'recreate'=>5,
            'finishorder'=>6
        ];

        $step = isset($arr[$operate])?$arr[$operate]:0;
        //在执行所有操作前 进行一些订单状态判断
        if(isset($_POST['order_id'])){
            $order_id = $_POST['order_id'];
            Helper_Array::removeEmpty($order_id);
            $ids = implode(',',$order_id);

        }else if(isset($_GET['order_id'])){
            $order_id = explode(',',$_GET['order_id']);
        	$ids = $_GET['order_id'];
           
        }
        if(isset($ids)){
        	//20160223kh start 为支持 oms 2.1 ， 发货不再是改变订单状态的操作， 是封装了一系列的操作了
        	OrderApiHelper::setOrderShipped($ids);
        	//OdOrder::updateAll(['order_status'=>OdOrder::STATUS_WAITSEND], 'order_id in ('.$ids.') and order_status < 300');
        	//20160223kh end 为支持 oms 2.1 ， 发货不再是改变订单状态的操作， 是封装了一系列的操作了
        	//OdOrder::updateAll(['carrier_step'=>OdOrder::CARRIER_FINISHED], 'order_id in ('.$ids.') and default_shipping_method_code = ""');
        	
        	if($step==4 || is_null($step)){
        		$odorder = OdOrder::find()->where(['in','order_id',$order_id])->andWhere('order_status = 300')->orderBy('default_shipping_method_code asc')->all();
        	}else{
        		$odorder = OdOrder::find()->where(['in','order_id',$order_id])->andWhere('order_status = 300')->andWhere(['carrier_step'=>$step])->orderBy('default_shipping_method_code asc')->all();
        		//如果是上传订单操作,需要将已删除的状态也添加进来
        		if($step==0){
        			$canceledOrder = OdOrder::find()->where(['in','order_id',$order_id])->andWhere('order_status = 300')->andWhere(['carrier_step'=>4])->orderBy('default_shipping_method_code asc')->all();
        			$odorder = array_merge($odorder,$canceledOrder);
        		}
        	}
        	foreach($odorder as $v){
        		self::$orderObjs[] = $v;
        	}
        	
        	
            $count = OdOrder::findBySql('SELECT count(*) as c,carrier_step from od_order_v2 where order_status = 300 and is_manual_order = 0  and order_id in ('.$ids.') GROUP BY carrier_step')->asArray()->all();
            if (count($count)>0){
                $result = Helper_Array::toHashmap($count, 'carrier_step','c');
            }else {
                $result = array();
            }
            $a = isset($result[0])?$result[0]:0;
            $b = isset($result[4])?$result[4]:0;
            $waitingupload = $a+$b;
            $waitingdispatch = isset($result[1])?$result[1]:0;
            $waitinggettrackingno=isset($result[2])?$result[2]:0;
            $waitingprint=isset($result[3])?$result[3]:0;
            $carriercomplete=isset($result[6])?$result[6]:0;
            
            self::$nums = [
                $waitingupload,
                $waitingdispatch,
                $waitinggettrackingno,
                $waitingprint,
                $carriercomplete,
            ];
            self::$orders = $ids;
            self::$step = $step;
        }else{
        	self::$nums = [0,0,0,0,0,];
        	self::$step = $step;
        }
    }

    public function actionGetcountnums(){
    	AppTrackerApiHelper::actionLog("eagle_v2","/carrier/carrieroperate/getcountnums");
        return json_encode(self::$nums);
    }

    /*
     * 订单上传物流商 数据组织
     */
    public function actionSubmitOrder()
    {
        
        $data = [];
        foreach($ids as $k=>$id){
            //查询出订单数据
            $odOrderItem_obj = OdOrderItem::find()->where(['order_id'=>$id])->all();
            //查询出物流商名 方便显示
            $carrier_code = OdOrder::find()->select(['default_carrier_code'])->where(['order_id'=>$id])->one();
            $carrier_name = SysCarrier::find()->select(['carrier_name'])->where(['carrier_code'=>$carrier_code->default_carrier_code])->one();
            foreach ($odOrderItem_obj as $key => $value) {
                $product = Product::find()->where(['sku'=>$value->sku])->one();
                $data[$k]['odorderitem'][$key]  = $value->attributes;
                $data[$k]['carrier'] = $carrier_name->carrier_name;
                $data[$k]['carrier_code'] = $carrier_code->default_carrier_code;
                $data[$k]['odorderitem'][$key]['product'] = empty($product)?[]:$product->attributes;
            }
        }
        return $this->render('submitorder',['data'=>$data,'ids'=>$ids,'operate_code'=>$_POST['carrier_operate_code']]);
    }

    
    /*
     * 上传订单到三方物流系统（页面展示）
     */
    public function actionGetorderno() {
    	return $this->redirect('..'.DIRECTORY_SEPARATOR.'carrierprocess'.DIRECTORY_SEPARATOR.'waitingpost');
    	
    	AppTrackerApiHelper::actionLog("eagle_v2","/carrier/carrieroperate/getorderno");
    	$services = CarrierApiHelper::getShippingServices();
    	return $this->render('getorderno',['orderObjs'=>self::$orderObjs,'services'=>$services,'ids'=>self::$orders,'nums'=>self::$nums,'step'=>self::$step]);
    }


    /*
     *  上传订单到三方物流系统（提交执行）
     *  组织需要上传到接口的数据
     *  将查询出来的数据和用户修改的数据 拼接到一起
     */
    public function actionGetData(){
    	AppTrackerApiHelper::actionLog("eagle_v2","/carrier/carrieroperate/get-data");
    	
        try{
            if(!\Yii::$app->request->getIsAjax()) {
                return false;
            }
            //是否上传成功立即交运
            $delivery = (isset($_GET['delivery']) && !empty($_GET['delivery']))?$_GET['delivery']:0;
            
            $id = $_POST['id']; //订单号
            //查订单数据
            $odOrder_obj = OdOrder::findOne($id);
            
            if($odOrder_obj->order_source == 'cdiscount')
            	$odOrder_obj->setItems(OdOrderItem::find()->where(['order_id'=>$odOrder_obj->order_id])->andwhere(['not in','sku',\eagle\modules\order\helpers\CdiscountOrderInterface::getNonDeliverySku()])->all());
            
            //查物流运输服务数据
            $shippingService_obj = SysShippingService::find()->where(['id'=>$odOrder_obj->default_shipping_method_code,'is_used'=>1])->one();
            if ($shippingService_obj == null) {
                throw new Exception('请先匹配启用的运输服务');
            }

            //自定义物流运输服务分配物流号 user库sys_tracking_number表
            if ($shippingService_obj->is_custom == 1) {
                $trackingNumber_obj = SysTrackingNumber::find()->where(['shipping_service_id'=>$shippingService_obj->id])->andWhere(['is_used'=>0])->orderBy('id asc')->one();
                //如果物流单已经全部使用，则提示用户物流单号不足
                if($trackingNumber_obj==null) {
                    throw new Exception('系统内物流单号不足，请导入新的单号');
                }
                if ((strlen($odOrder_obj->customer_number)>0) && (empty($_POST['extra_id']))) {
                    throw new Exception('请不要重复分配物流号');
                }
                //组织物流信息
                $serviceCode = $shippingService_obj->service_code;
                $extra_id = isset($_POST['extra_id'])?$_POST['extra_id']:'';
                //获取客户代码
                $customer_number = \eagle\modules\carrier\helpers\CarrierAPIHelper::getCustomerNum2($odOrder_obj);
                $logisticInfoList = array(
                        0=>array(
                                'order_source'=>$odOrder_obj->order_source,//订单来源
                                'selleruserid'=>$odOrder_obj->selleruserid,//卖家账号
                                'tracking_number'=>$trackingNumber_obj->tracking_number,//物流号（选填）
                                'tracking_link'=>$shippingService_obj->web,//查询网址（选填）
                                'shipping_method_code'=>$serviceCode[$odOrder_obj->order_source],//平台物流服务代码
                                'shipping_method_name'=>'',//平台物流服务名
                                'order_source_order_id'=>$odOrder_obj->order_source_order_id,//平台订单号
                                'return_no'=>$customer_number,//物流系统的订单号（选填）
                                'customer_number'=>$customer_number,//物流系统的订单号（选填）
                                'shipping_service_id'=>$shippingService_obj->id,//物流服务id（选填）
                                'addtype'=>'物流号分配',//物流号来源
                                'signtype'=>'all',//标记类型 all或者part（选填）
                                'description'=>'',//备注（选填）
                        )
                );
                //保存物流信息
                $result=OrderHelper::saveTrackingNumber($odOrder_obj->order_id, $logisticInfoList);
                if ($result){
                    $result = array('error' => 1, 'data' => '', 'msg' => '分配物流号成功！物流号：'.$trackingNumber_obj->tracking_number);
                    $odOrder_obj->carrier_error = '';
                    $odOrder_obj->carrier_step = OdOrder::CARRIER_WAITING_PRINT;
                    $odOrder_obj->customer_number =$customer_number;
                    $odOrder_obj->save();
                    $trackingNumber_obj->order_id =$odOrder_obj->order_id;
                    $trackingNumber_obj->is_used =1;
                    $trackingNumber_obj->use_time =time();
                    $trackingNumber_obj->save();
                    return json_encode($result);
                }else throw new Exception('分配物流号失败！');
            }


            //查询物流商
            $carrier = SysCarrier::findOne($odOrder_obj->default_carrier_code);
            if ($carrier===null) {
                throw new Exception('请先匹配运输服务！');
            }
            $class_name = '';
            //判断是否海外仓，海外仓的type=1
            if($carrier->carrier_type){
                $class_name = '\common\api\overseaWarehouseAPI\\'.$carrier->api_class;
            }else{
                $class_name = '\common\api\carrierAPI\\'.$carrier->api_class;
            }
            //开始标准物流操作流程
            if($carrier->api_class == 'LB_RTBCOMPANYCarrierAPI'){
            	//对接软通宝所属物流
            	$interface = new $class_name($carrier->carrier_code);
            }
            else{
            	$interface = new $class_name;
            }
            
            $result = $interface->getOrderNO(['order'=>$odOrder_obj,'data'=>$_POST]);
            //将返回的数据 存储到shipped表
            if($result['error']==0){
                //组织物流信息
                $logisticInfoList = [$result['data']];
                $odOrder_obj->carrier_error = '';
                //weird_status处理
                if(!empty($odOrder_obj->weird_status))
                	OperationLogHelper::log('order',$odOrder_obj->order_id,'提交物流','提交物流时自动清除操作超时标签',\Yii::$app->user->identity->getFullName());
                $odOrder_obj->weird_status = '';
                
                $odOrder_obj->save();
                //保存物流信息
                OrderHelper::saveTrackingNumber($odOrder_obj->order_id, $logisticInfoList);
                //如果用户选择了‘上传完立即交运’，且该物流当前处在交运状态，则立即交运
                if($delivery && $odOrder_obj->carrier_step == 1){
                	$result = $interface->doDispatch(['order'=>$odOrder_obj,'data'=>$_POST]);
                	//如果有错误信息 保存下来
                	if($result['error']==1){
                		$odOrder_obj->carrier_error = $result['msg'];
                		$odOrder_obj->save();
                	}else{
                		$odOrder_obj->carrier_error = '';
                		$odOrder_obj->save();
                	}
                }
            }
            if($result['error']==1){
                $odOrder_obj->carrier_error = $result['msg'];
                $odOrder_obj->save();
            }
            return json_encode($result);
        }catch(Exception $e){
            $odOrder_obj->carrier_error = $e->getMessage();
            $odOrder_obj->save();
            return json_encode(['error'=>1,'data'=>'','msg'=>$e->getMessage()]);
        }
    }
    /*
     * 交运订单
    */
    public function actionDodispatch()
    {
    	
    	AppTrackerApiHelper::actionLog("eagle_v2","/carrier/carrieroperate/dodispatch");
    	$services = CarrierApiHelper::getShippingServices();
    	return $this->render('dodispatch',['orderObjs'=>self::$orderObjs,'services'=>$services,'ids'=>self::$orders,'nums'=>self::$nums,'step'=>self::$step]);
    }
    /*
     * 交运订单
    */
    public function actionDodispatchajax()
    {
    	
    	if(!\Yii::$app->request->getIsAjax())return false;
    	AppTrackerApiHelper::actionLog("eagle_v2","/carrier/carrieroperate/dodispatchajax");
    	
    	$id = $_POST['id'];
    	//订单
    	$odOrder_obj = OdOrder::findOne($id);
    	$class_name = '';
    	//物流商
    	$carrier = SysCarrier::findOne($odOrder_obj->default_carrier_code);
    	if($carrier->carrier_type){
    		$class_name = '\common\api\overseaWarehouseAPI\\'.$carrier->api_class;
    	}else{
    		$class_name = '\common\api\carrierAPI\\'.$carrier->api_class;
    	}
    	
    	if($carrier->api_class == 'LB_RTBCOMPANYCarrierAPI'){
    		//对接软通宝所属物流
    		$interface = new $class_name($carrier->carrier_code);
    	}
    	else{
    		$interface = new $class_name;
    	}
    	
    	$result = $interface->doDispatch(['order'=>$odOrder_obj,'data'=>$_POST]);
        //如果有错误信息 保存下来
        if($result['error']==1){
            $odOrder_obj->carrier_error = $result['msg'];
            $odOrder_obj->save();
        }else{
            $odOrder_obj->carrier_error = '';
            $odOrder_obj->save();
        }
    	
    	return json_encode($result);
    }
    /*
     * 获取物流号
    */
    public function actionGettrackingno()
    {
    	AppTrackerApiHelper::actionLog("eagle_v2","/carrier/carrieroperate/gettrackingno");
    	$services = CarrierApiHelper::getShippingServices();
    	return $this->render('gettrackingno',['orderObjs'=>self::$orderObjs,'services'=>$services,'ids'=>self::$orders,'nums'=>self::$nums,'step'=>self::$step]);
    }
    /*
     * 获取物流号
    */
    public function actionGettrackingnoajax()
    {
    	if(!\Yii::$app->request->getIsAjax())return false;
    	
    	AppTrackerApiHelper::actionLog("eagle_v2","/carrier/carrieroperate/gettrackingnoajax");
    	
    	$id = $_POST['id'];
    	//订单
    	$odOrder_obj = OdOrder::findOne($id);
    	$class_name = '';
    	//物流商
    	$carrier = SysCarrier::findOne($odOrder_obj->default_carrier_code);
    	if($carrier->carrier_type){
    		$class_name = '\common\api\overseaWarehouseAPI\\'.$carrier->api_class;
    	}else{
    		$class_name = '\common\api\carrierAPI\\'.$carrier->api_class;
    	}
    	
    	if($carrier->api_class == 'LB_RTBCOMPANYCarrierAPI'){
    		//对接软通宝所属物流
    		$interface = new $class_name($carrier->carrier_code);
    	}else{
    		$interface = new $class_name;
    	}
    	
    	$result = $interface->getTrackingNO(['order'=>$odOrder_obj,'data'=>$_POST]);
        //如果有错误信息 保存下来
        if($result['error']==1){
            $odOrder_obj->carrier_error = $result['msg'];
            $odOrder_obj->save();
        }else{
            $odOrder_obj->carrier_error = '';
            $odOrder_obj->save();
        }
    	return json_encode($result);
    }
    /*
     * 打印物流单ajax 
    */
    public function actionDoprint()
    {
    	AppTrackerApiHelper::actionLog("eagle_v2","/carrier/carrieroperate/doprint");
//     	$this->layout='mainPrint';
    	//如果是打印物流单 则直接调用接口将返回数据给用户即可
		$emslist = [];
        $is_searched = [];
        $list = [];
        if(self::$orderObjs):
		    //将订单中的id全部获取到，过滤掉重复的
            foreach(self::$orderObjs as $v){
                if(!isset($is_searched[$v->default_shipping_method_code])){
                    $shipping_service = SysShippingService::find()->select(['shipping_method_name','carrier_name','print_type'])->where(['id'=>$v->default_shipping_method_code])->one();
                    //如果存在没有分配的 则直接过滤掉
                    if(is_null($shipping_service))continue;
                    $is_searched[$v->default_shipping_method_code] = $shipping_service;
                }
                
                if($shipping_service->print_type == 1)
                	$method_name = $is_searched[$v->default_shipping_method_code]['shipping_method_name'].$v->consignee_country_code;
                else
                	$method_name = $is_searched[$v->default_shipping_method_code]['shipping_method_name'];
                
                $carrier_name = $is_searched[$v->default_shipping_method_code]['carrier_name'];
                //统计出该运输方式下订单数量
                isset($count_shipping_service[$method_name])?++$count_shipping_service[$method_name]:($count_shipping_service[$method_name] = 1);
                //将订单id根据运输方式分类
                if(!isset($emslist[$method_name]))$emslist[$method_name] = [];
                // $emslist[$method_name] .= $v->order_id.',';
                isset($emslist[$method_name]['order_ids'])?'':$emslist[$method_name]['order_ids'] = '';
                $emslist[$method_name]['order_ids'] .= $v->order_id.',';
                $emslist[$method_name]['display_name'] = $carrier_name.' >>> '.$method_name;
            }
            foreach($emslist as $k=>$v){
                $name = $v['display_name'].' X '.$count_shipping_service[$k];
                $list[$name] = $v['order_ids'];
            }
        endif;
		$result = array();
		$result['emslist']=$list;
		
		return $this->render('createPDF2print',['data'=>$result,'ids'=>self::$orders,'nums'=>self::$nums,'step'=>self::$step]);
    }
    
    /*
     * 打印订单
     */
    public function actionDoprint2()
    {
    	$do_custom_print = false;
    	if(isset($_GET['orders'])&&!empty($_GET['orders'])){
    		$orders	=	$_GET['orders'];
    	}else{
    		echo "错误：未传入订单号";die;
    	}
    	
    	$carrierConfig = ConfigHelper::getConfig("CarrierOpenHelper/CommonCarrierConfig", 'NO_CACHE');
    	$carrierConfig = json_decode($carrierConfig,true);
    	 
    	if(!isset($carrierConfig['label_paper_size'])){
    		$carrierConfig['label_paper_size'] = array('val' => '100x100','template_width' => 100,'template_height' => 100);
    	}
    	
    	$printType = '100x100';
    	if($carrierConfig['label_paper_size']['val'] == '210x297'){
    		$printType = 'A4';
    	}
    	
    	AppTrackerApiHelper::actionLog("eagle_v2","/carrier/carrieroperate/doprint2");
    	$puid = \Yii::$app->user->identity->getParentUid();
        $orders = rtrim($orders,',');
    	$orderlist	=	OdOrder::find()->where("order_id in ({$orders})")->all();
    	$shippingServece_obj = SysShippingService::findOne(['id'=>$orderlist[0]['default_shipping_method_code']]);
    	if ($shippingServece_obj->is_custom==0 && $do_custom_print==false){
    		
    		if($shippingServece_obj->print_type == 1){
    			$sysShippingMethod = SysShippingMethod::find()->select(['id','print_params','carrier_code'])->where(['carrier_code'=>$shippingServece_obj->carrier_code,
    				'shipping_method_code'=>$shippingServece_obj->shipping_method_code,
    				'third_party_code'=>(empty($shippingServece_obj->third_party_code) ? '' : $shippingServece_obj->third_party_code),'is_print'=>1])->asArray()->one();
    		}
    		
    		$tmpIsCustom = false;
    		if(($shippingServece_obj->print_type == 1) && (!empty($sysShippingMethod)) && (!empty($shippingServece_obj->print_params))){
    			$lable_type = array('label_address'=>'地址单','label_declare'=>'报关单','label_items'=>'配货单');
    			$sysShippingMethod['print_params'] = json_decode($sysShippingMethod['print_params'],true);
    			 
    			$print_params = array();
    			
    			//这里把shipping_method_id为0的也添加进去查找，0代表通用的模板
    			$print_params['shipping_method_id'] = array($sysShippingMethod['id'],'0');
    			$templateArr = array();
    			 
    			foreach ($shippingServece_obj->print_params['label_littleboss'] as $print_paramone){
    				if(in_array($print_paramone, $sysShippingMethod['print_params'])){
    					$print_params['lable_type'][$print_paramone] = $lable_type[$print_paramone];
    					$templateArr[$print_paramone] = '';
    				}
    			}
    			 
    			$templateAll = CrCarrierTemplate::find()
    			->where(['carrier_code'=>$sysShippingMethod['carrier_code'],'shipping_method_id'=>$print_params['shipping_method_id'],'template_type'=>$print_params['lable_type'],'is_use'=>1])
    			->orderBy('template_type,country_codes desc,shipping_method_id desc')->all();
    			 
    			foreach ($print_params['lable_type'] as $print_paramkey => $print_paramval){
    				foreach ($templateAll as $templateAllone){
    					if($print_paramval == $templateAllone['template_type']){
    						if(empty($templateAllone['country_codes'])){
    							$templateArr[$print_paramkey] = $templateAllone;
    							break;
    						}else
    						if (strpos($templateAllone['country_codes'], $orderlist[0]->consignee_country_code) !== false){
    							$templateArr[$print_paramkey] = $templateAllone;
    							break;
    						}
    					}
    				}
    			}
    			
    			$tmpIsCustom = true;
    			foreach ($templateArr as $tmp1){
    				if(empty($tmp1)){
    					$tmpIsCustom = false;
    				}
    			}
    		}
    		
    		if(($shippingServece_obj->print_type == 1) && (!empty($sysShippingMethod)) && (!empty($shippingServece_obj->print_params)) && ($tmpIsCustom)){
//     			return $this->render('doprinthighcopy',['data'=>$orderlist,'shippingService'=>$shippingServece_obj,'carrierConfig'=>$carrierConfig,'print_params'=>$print_params,'templateArr'=>$templateArr]);
				$this->layout='/mainPrint';
				$html = $this->render('doprinthighcopy',['data'=>$orderlist,'shippingService'=>$shippingServece_obj,'carrierConfig'=>$carrierConfig,'print_params'=>$print_params,'templateArr'=>$templateArr]);
				$result = Helper_Curl::post("http://120.55.86.71/wkhtmltopdf.php",['html'=>$html,'uid'=>$puid,'pringType'=>$printType]);// 打A4还是热敏纸
				if(false !== $result){
					$rtn = json_decode($result,true);
// 					echo $html;
					if(1 == $rtn['success']){
						$response = Helper_Curl::get($rtn['url']);
						$pdfUrl = \eagle\modules\carrier\helpers\CarrierAPIHelper::savePDF($response, $puid, md5("wkhtmltopdf")."_".time());
						$this->redirect($pdfUrl);
					}else{
						return "打印出错，请联系小老板客服。";
					}
				}else{
					return "请重试，如果再有问题请联系小老板客服。";
				}
			}else{
    			//将数据通过接口上传
    			$carrier = SysCarrier::findOne($orderlist[0]['default_carrier_code']);
    			if($carrier->carrier_type){
    				$class_name = '\common\api\overseaWarehouseAPI\\'.$carrier->api_class;
    			}else{
    				$class_name = '\common\api\carrierAPI\\'.$carrier->api_class;
    			}
    			$arr	=	array();
    			
    			//处理成接口需要的数据
    			foreach($orderlist as $v){
    				$arr[]['order']=$v;
    			}
    			
    			if($carrier->api_class == 'LB_RTBCOMPANYCarrierAPI'){
    				//对接软通宝所属物流
    				$interface = new $class_name($carrier->carrier_code);
    			}
    			else{
    				$interface = new $class_name;
    			}
    			
    			$result['result'] = $interface->doPrint($arr);
    			
    			if(isset($_GET['ems'])&&!empty($_GET['ems'])){
    				$result['carrier_name'] = $_GET['ems'];
    			}else{
    				$result['carrier_name'] = "";
    			}
    			if($carrier->api_class == 'LB_IEUBNewCarrierAPI'){
    				if($result['result']['error']){
    					return $this->render('doprint2',['data'=>$result]);
    				}else{
    					//国际EUB访问速度不能过快，而且Headers信息不能被Frame包住
    					usleep(100000);
    					$this->redirect($result['result']['data']['pdfUrl']);
    				}
    			}else{
    				return $this->render('doprint2',['data'=>$result]);
    			}
    		}
    	}else{
    		//$template = CrTemplate::findOne(4);
    		// return $this->render('doprintcustom',['data'=>$orderlist,'shippingService'=>$shippingServece_obj]);
			
			$this->layout='/mainPrint';
    		$html = $this->render('doprintcustom',['data'=>$orderlist,'shippingService'=>$shippingServece_obj,'carrierConfig'=>$carrierConfig]);
    		$result = Helper_Curl::post("http://120.55.86.71/wkhtmltopdf.php",['html'=>$html,'uid'=>$puid,'pringType'=>$printType]);// 打A4还是热敏纸
    		if(false !== $result){
    			$rtn = json_decode($result,true);
//     			print_r($rtn) ;
    			if(1 == $rtn['success']){
    				$response = Helper_Curl::get($rtn['url']);
    				$pdfUrl = \eagle\modules\carrier\helpers\CarrierAPIHelper::savePDF($response, $puid, md5("wkhtmltopdf")."_".time());
    				$this->redirect($pdfUrl);
    			}else{    				
    				return "打印出错，请联系小老板客服。";
    			}
    		}else{
    			return "请重试，如果再有问题请联系小老板客服。";
    		}
    	}
    }
    /*
     * 打印完成标记
     * 
     */
    
    public function actionSetprint(){
    	if(isset($_GET['orders'])&&!empty($_GET['orders'])){
    		$orders	=	$_GET['orders'];
    	}else{
    		echo "错误：未传入订单号";die;
    	}
    	
    	AppTrackerApiHelper::actionLog("eagle_v2","/carrier/carrieroperate/setprint");
    	
    	$order_str	=	rtrim($orders,',');
        //选择其中一个订单，获取物流商
        $order_id = current(explode(',', $order_str));
        //根据id获得物流商代码
        $orderObj = OdOrder::findOne($order_id);
        $carrier_code = $orderObj->default_carrier_code;
        //根据物流商代码 查询类名
        $carrier = SysCarrier::findOne($carrier_code);
        //如果carrier为空 判断为自定义物流 就不通过接口 直接修改订单状态
        $nextStatus = OdOrder::CARRIER_FINISHED;
        if($carrier !== null){
            if($carrier->carrier_type){
                $class_name = '\common\api\overseaWarehouseAPI\\'.$carrier->api_class;
            }else{
                $class_name = '\common\api\carrierAPI\\'.$carrier->api_class;
            }
            //调用对应的方法 获得订单下一步的状态
            $nextStatus = $class_name::getOrderNextStatus();
        }
    	OdOrder::updateAll(array('carrier_step' => $nextStatus), 'order_id in ('.$order_str.')');
    	echo "OK";
    }
    /*
     * 取消订单
    */
    public function actionCancelorderno()
    {
    	AppTrackerApiHelper::actionLog("eagle_v2","/carrier/carrieroperate/cancelorderno");
    	$services = CarrierApiHelper::getShippingServices();
    	return $this->render('cancelorderno',['orderObjs'=>self::$orderObjs,'services'=>$services,'ids'=>self::$orders,'nums'=>self::$nums,'step'=>self::$step]);
    }
    /*
     * 取消订单ajax
    */
    public function actionCancelordernoajax()
    {
    	if(!\Yii::$app->request->getIsAjax())return false;
    	$id = $_POST['id'];
    	//订单
    	$odOrder_obj = OdOrder::findOne($id);
    	$class_name = '';
    	//物流商
    	$carrier = SysCarrier::findOne($odOrder_obj->default_carrier_code);
    	if($carrier->carrier_type){
    		$class_name = '\common\api\overseaWarehouseAPI\\'.$carrier->api_class;
    	}else{
    		$class_name = '\common\api\carrierAPI\\'.$carrier->api_class;
    	}
    	
    	if($carrier->api_class == 'LB_RTBCOMPANYCarrierAPI'){
    		//对接软通宝所属物流
    		$interface = new $class_name($carrier->carrier_code);
    	}
    	else{
    		$interface = new $class_name;
    	}
    	
    	$result = $interface->cancelOrderNO(['order'=>$odOrder_obj,'data'=>$_POST]);
        //如果有错误信息 保存下来
        if($result['error']==1){
            $odOrder_obj->carrier_error = $result['msg'];
            $odOrder_obj->save();
        }else{
            $odOrder_obj->carrier_error = '';
            $odOrder_obj->save();
        }
    	return json_encode($result);
    }
    /*
     * 重新发货
    */
    public function actionRecreate()
    {
    	$services = CarrierApiHelper::getShippingServices();
    	return $this->render('getorderno',['orderObjs'=>self::$orderObjs,'services'=>$services,'ids'=>self::$orders,'nums'=>self::$nums,'step'=>self::$step]);
    }

    /*
    * 已完成订单
    */
    public function actionFinishorder(){
    	AppTrackerApiHelper::actionLog("eagle_v2","/carrier/carrieroperate/finishorder");
        $services = CarrierApiHelper::getShippingServices();
        return $this->render('finishorder',['orderObjs'=>self::$orderObjs,'services'=>$services,'ids'=>self::$orders,'nums'=>self::$nums,'step'=>self::$step]);
    }

/**
 * 生成条码
 * million
 */
    public function actionBarcode(){
    	AppTrackerApiHelper::actionLog("eagle_v2","/carrier/carrieroperate/barcode");
    	$codetype = \Yii::$app->request->get('codetype');//条码类型
    	$thickness = \Yii::$app->request->get('thickness');//厚度
    	$text = \Yii::$app->request->get('text');//条码字符
    	//$imgtype = \Yii::$app->request->get('imgtype');//条码字符
    	$font = \Yii::$app->request->get('font');
    	$fontsize = \Yii::$app->request->get('fontsize');
    	$resolution = \Yii::$app->request->get('resolution');
    	$resolution = empty($resolution) ? 1 : $resolution;
    	BarcodeHelper::generate($codetype,$thickness,$text,BarcodeHelper::IMAGE_TYPE_PNG,'', $font, $fontsize, $resolution);
    }

    /**
	 * 赛兔模式打印订单
	 * hqw
	 * 20160316
     */
    public function actionDoprintSaitu(){
    	$do_custom_print = false;
    	if(isset($_GET['orders'])&&!empty($_GET['orders'])){
    		$orders	=	$_GET['orders'];
    	}else{
    		echo "错误：未传入订单号";die;
    	}
    	 
    	AppTrackerApiHelper::actionLog("eagle_v2","/carrier/carrieroperate/doprint-saitu");
    	$puid = \Yii::$app->user->identity->getParentUid();
    	$orders = rtrim($orders,',');
    	$orderlists = OdOrder::find()->where("order_id in ({$orders})")->orderBy('default_carrier_code,default_shipping_method_code')->asArray()->all();
    	
    	$orderCarrierLabelLists = CarrierUserLabel::find()->where(['uid'=>$puid,'run_status'=>2])->andWhere("order_id in ({$orders})")->asArray()->all();
    	
    	$tmpPdfArr = array();
    	
    	foreach ($orderlists as $orderlist){
    		foreach($orderCarrierLabelLists as $orderCarrierLabelList){
    			if(($orderlist['order_id'] == $orderCarrierLabelList['order_id']) && ($orderlist['customer_number'] == $orderCarrierLabelList['customer_number'])){
    				$tmpPdfArr[] = \eagle\modules\carrier\helpers\CarrierAPIHelper::getPdfPathString(false).$orderCarrierLabelList['merge_pdf_file_path'];
//     				$tmpPdfArr[] = $orderCarrierLabelList['merge_pdf_file_path'];
    			}
    		}
    	}
    	
    	$result = ['error'=>1,'data'=>'','msg'=>''];
    	
    	if((!empty($tmpPdfArr)) && (count($orderlists) == count($tmpPdfArr))){
    		if(count($tmpPdfArr) == 1){
//     			$pathPDF = \eagle\modules\carrier\helpers\CarrierAPIHelper::createCarrierLabelDir(false);
    			$pdfmergeResult['success'] = true;
    			$url = Yii::$app->request->hostinfo.str_replace(\eagle\modules\carrier\helpers\CarrierAPIHelper::getPdfPathString(false), "", $tmpPdfArr[0]);
    			
//     			print_r($url);
    		}else{
    			$pathPDF = \eagle\modules\carrier\helpers\CarrierAPIHelper::createCarrierLabelDir();
    			$tmpName = $puid.'_summerge_'.rand(10,99).time().'.pdf';
    			$pdfmergeResult = PDFMergeHelper::PDFMerge($pathPDF.'/'.$tmpName , $tmpPdfArr);
    			
    			$url = Yii::$app->request->hostinfo.str_replace(\eagle\modules\carrier\helpers\CarrierAPIHelper::getPdfPathString(false), "", $pathPDF).'/'.$tmpName;
    			
//     			print_r($url);
    		}
    		
    		\Yii::info('actionDoprintSaitu'.$puid.''.$url, "file");
    		
    		if($pdfmergeResult['success'] == true){
    			$result['data'] = ['pdfUrl'=>$url];
    			$result['error'] = 0;
    		}else{
    			$result['msg'] = $pdfmergeResult['message'];
    			$result['error'] = 1;
    		}
    	}else{
    		$result['msg'] = '这部分订单还没有生成完毕，请等待后台再试';
    	}
    	
//     	print_r($result);
    	
    	return $this->render('doprint2',['data'=>array('result'=>$result,'carrier_name'=>'')]);
    }
}
