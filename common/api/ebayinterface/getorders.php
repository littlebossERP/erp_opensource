<?php
namespace common\api\ebayinterface;

use common\api\ebayinterface\base;
use console\helpers\QueueGetorderHelper;
use console\helpers\QueuePaypaltransactionHelper;
use common\helpers\Helper_xml;
use common\helpers\Helper_Array;

use eagle\models\SaasEbayUser;

use eagle\modules\order\models\OdEbayOrder;
use eagle\modules\order\models\OdEbayTransaction;
use eagle\modules\order\models\OdOrder;
use eagle\modules\order\models\OdEbayExternaltransaction;

use eagle\modules\order\helpers\OrderHelper;
use eagle\modules\util\helpers\EagleOneHttpApiHelper;
use eagle\models\QueueGetorder;
use eagle\modules\order\helpers\OrderUpdateHelper;
use eagle\models\SaasPaypalUser;
use eagle\modules\listing\models\EbayItem;
use eagle\modules\order\helpers\EbayOrderHelper;
use common\api\ebayinterface\shopping\getsingleitem;
use eagle\modules\util\helpers\RedisHelper;

/**
 * 获取订单列表
 * @package interface.ebay.tradingapi
 */
class getorders extends base{
	public $CreateTimeFrom=null;
	public $CreateTimeTo=null;
	public $OrderRole=null;
	public $OrderStatus=null;
	public $EntriesPerPage=null;
	public $PageNumber=null;
	public $ModTimeFrom=null;
	public $ModTimeTo=null;
	public $NumberOfDays=null;
	public $verb='GetOrders';


	//从ebay获取相应itemid的信息
	public function api(){
		$this->verb = 'GetOrders';
		$this->OrderRole='Seller';
		//        $this->OrderStatus='Active';//Completed ,Shipped
		$xmlArr=array(
			'OrderRole'=>$this->OrderRole,
			'IncludeFinalValueFee'=>'true',
		);
		if ($this->EntriesPerPage){
			$xmlArr['Pagination']=array(
				'EntriesPerPage'=>$this->EntriesPerPage,
				'PageNumber'=>$this->PageNumber,
			);
		}
		if ($this->NumberOfDays){
			$xmlArr['NumberOfDays']=$this->NumberOfDays;
		}
		if ($this->CreateTimeFrom){
			$xmlArr['CreateTimeFrom']=$this->CreateTimeFrom;
			$xmlArr['CreateTimeTo']=$this->CreateTimeTo;
		}
		if ($this->ModTimeFrom){
			$xmlArr['ModTimeFrom']=$this->ModTimeFrom;
			$xmlArr['ModTimeTo']=$this->ModTimeTo;
		}
		if($this->OrderStatus){
			$xmlArr['OrderStatus']=$this->OrderStatus;;
		}
		if(isset($this->_before_request_xmlarray['OrderID'])){
			$xmlArr+=array(
				'OrderIDArray'=>array(
					'OrderID'=>$this->_before_request_xmlarray['OrderID'],
				),
			);
			$xmlArr['DetailLevel']='ReturnAll';
			$xmlArr['OrderRole']='Seller';
		}
		if(!empty($this->_before_request_xmlarray['DetailLevel'])){
			$xmlArr['DetailLevel']=$this->_before_request_xmlarray['DetailLevel'];
		}
		if(isset($this->_before_request_xmlarray['OutputSelector'])){
			unset($xmlArr['DetailLevel']);
			$xmlArr['OutputSelector']=$this->_before_request_xmlarray['OutputSelector'];
		}
		$requestArr=$this->setRequestBody($xmlArr)->sendRequest(0,600);

		//Yii::log(print_r($requestArr,1));
		return $requestArr;
	}


	/***
	 * @author lxqun 2011-05-22
	 * 请求并保存订单数组
	 * 包含 多页请求
	 * 中间处理过程 与   requestOneOrder 
	 * 
	 */
	function requestOrders($ebay_user,$requested_ebayorderids=array()){
		set_time_limit(0);
		if(empty($this->EntriesPerPage)||empty($this->PageNumber)){
			$this->EntriesPerPage=20;
			$this->PageNumber=1;
		}

		$this->_before_request_xmlarray['DetailLevel']='ReturnAll';
		/*
		if ($ebay_user->order_priority >3){
			$priority2status = 9;
		}else{
			$priority2status = 0;
		}
		*/
		do{
			$result = $this->api();
			if ($result['Ack']=='Warning'&&isset($result['Errors']['ErrorCode'])&&$result['Errors']['ErrorCode']=='21917182'){
				//Invalid orderlineids.
				echo "\n ".(__function__)." E001 ".json_encode($result);
				if (empty($result['OrderArray']) && count($result['Errors']['ErrorParameters'])==1 && !empty($result['Errors']['ErrorParameters']['Value']) ){
					$effect = QueueGetorder::deleteAll(['selleruserid'=>$ebay_user->selleruserid,'ebay_orderid'=>$result['Errors']['ErrorParameters']['Value']]);
					echo "\n ".$result['Errors']['ErrorParameters']['Value'].'delete ='.$effect;
				}else{
					echo "\n E001 delete failure".json_encode($requested_ebayorderids);
				}
				return false;
			}
			if (!$this->responseIsFailure()){
				$requestArr=$this->_last_response_xmlarray;
				
				\Yii::info(print_r($requestArr,1),'requestOrders   _last_response_xmlarray');
				
				if (!isset($requestArr['OrderArray']['Order'])){
					echo "\n ".(__function__)." E002  ".json_encode($result)." \n E002 requested_ebayorderids ".json_encode($requested_ebayorderids);
					$effect = QueueGetorder::updateAllCounters(['retry_count'=>1],['selleruserid'=>$ebay_user->selleruserid,'ebay_orderid'=>$requested_ebayorderids]);
					echo "\n  retry count+1 effect=" .$effect." and order id".json_encode($requested_ebayorderids);
					return false;
				}
					
				if(isset($requestArr['OrderArray']['Order']['OrderID'])){
					$OrderArray['Order']=array($requestArr['OrderArray']['Order']);
				}elseif(Helper_xml::isArray($requestArr['OrderArray']['Order'])&&count($requestArr['OrderArray']['Order'])){
					$OrderArray['Order']=$requestArr['OrderArray']['Order'];
				}
				if(count($OrderArray['Order'])){
					$response_orderids=array();
					foreach ($OrderArray['Order'] as $o){
						if(isset($response_orderids[$o['OrderID']])){
							$response_orderids[$o['OrderID']]++;
						}else{
							$response_orderids[$o['OrderID']]=1;
						}
					}

					foreach ($OrderArray['Order'] as $o){
						// 返回的orderid 如果不存在于请求的数组中的,重新请求,
						//这样可以解决 使用 t的orderid 请求回来是 o 的orderid 的问题 
						if((strpos($o['OrderID'],'-')==false) && count($requested_ebayorderids) ){
							if(in_array($o['OrderID'], $requested_ebayorderids)){
								if($response_orderids[$o['OrderID']]>1){
									QueueGetorderHelper::Add($o['OrderID'],$ebay_user->selleruserid,$ebay_user->selleruserid);
									continue ;
								}
							}else{
								QueueGetorderHelper::Add($o['OrderID'],$ebay_user->selleruserid,$ebay_user->selleruserid);
								continue ;
							}
						}
						$logstr="\n\nselleruserid:".$ebay_user->selleruserid."\nEbay OrderID:".$o['OrderID']."\n";
						echo $logstr;
						try {
							$this->saveOneOrder($o,$o['OrderID'],$ebay_user,$ebay_user->selleruserid);
						}catch(\Exception $ex){
							echo "\n ".(__function__)." Error Message :  ". $ex->getMessage()." File:".$ex->getFile()." Line no:".$ex->getLine()."\n";
							QueueGetorder::updateAll(array('status'=>0),'ebay_orderid=:eoid',array(':eoid'=>$o['OrderID']));
						}
						//Yii::log($logstr);
					}
				}
				if($requestArr['PageNumber']>=$requestArr['PaginationResult']['TotalNumberOfPages']){
					return true;
					break 1;
				}else{
					$this->PageNumber=$requestArr['PageNumber']+1;
					$this->EntriesPerPage=$requestArr['OrdersPerPage'];
				}
			}else{
				echo "\n ".(__function__)." E004  response Is Failure!";
				break 1;
			}
		}while(1);
		return false;
	}

	/**
	 * 请求并保存订单数组
	 * 包含 多页请求
	 * 中间处理过程 与   requestOneOrder 
	 * 
	 */
	function requestShipOrders($ebay_user){
		set_time_limit(0);
		if(empty($this->EntriesPerPage)||empty($this->PageNumber)){
			$this->EntriesPerPage=20;
			$this->PageNumber=1;
		}
		do{ 
			$this->api(); 
			if (!$this->responseIsFailure()){ 
				$requestArr=$this->_last_response_xmlarray;
				if(isset($requestArr['OrderArray']['Order']['OrderID'])){ 
					$OrderArray['Order']=array($requestArr['OrderArray']['Order']);
				}elseif(Helper_xml::isArray($requestArr['OrderArray']['Order'])&&count($requestArr['OrderArray']['Order'])){ 
					$OrderArray['Order']=$requestArr['OrderArray']['Order'];
				}
				if(count($OrderArray['Order'])){
					foreach ($OrderArray['Order'] as $o){
						$this->changeOneOrder($o,$o['OrderID'],$ebay_user->selleruserid); 
						$logstr="\n\nselleruserid: ".$ebay_user->selleruserid.' Ebay OrderID '.$o['OrderID']."\n";
						print_r($logstr); 
						//Yii::log($logstr);
					}
				} 
				if($requestArr['PageNumber']>=$requestArr['PaginationResult']['TotalNumberOfPages']){
					break 1;
				}else{
					$this->PageNumber=$requestArr['PageNumber']+1;
					$this->EntriesPerPage=$requestArr['OrdersPerPage'];
				}
			}else{
				break 1;
			}
		}while(1);
	}

	/**
	 *  @author lxqun 2011-06-14
	 *  请求并保存订单数组
	 *  @param $orderid  : 可以是单个 ebay_orderid 或者是多个 ebay_orderid Array
	 */
	function requestOneOrder($orderid,$selleruserid){
		if(empty($this->eBayAuthToken)){
			return false;
		}
		$this->_before_request_xmlarray['DetailLevel']='ReturnAll';
		$this->_before_request_xmlarray['OrderID']=$orderid;
		$this->api();
		//if($MO->lastmodifiedtime== $LastModifiedTime ) continue 1;
		if (!$this->responseIsFailure()){
			$ebay_user=SaasEbayUser::model()->where('selleruserid=?',$selleruserid)->getOne();
			$requestArr=$this->_last_response_xmlarray;
			if($requestArr['ReturnedOrderCountActual']==1){
				$o=$requestArr['OrderArray']['Order'];
				if($o['OrderID']!=$orderid){
					return $this->requestOneOrder($o['OrderID'],$selleruserid);
				}
				$this->saveOneOrder($o,$orderid,$ebay_user,$selleruserid);
			}else{
				$os=$requestArr['OrderArray']['Order'];
				foreach($os as $o){
					$this->saveOneOrder($o,$o['OrderID'],$ebay_user,$selleruserid);
				}
			}
		}
	}

/**
 * 同步订单后的保存
 * @param unknown $oA
 * @param unknown $ebay_orderid
 * @param unknown $ebay_user
 * @param unknown $selleruserid
 * @return boolean
 */
	function saveOneOrder($oA,$ebay_orderid,$ebay_user,$selleruserid){ 
		global $_CACHE_SHIPTIME;
// 		$EOderPayed=false; // 是否支付
		$EOderBackMoney=false;  // 是否退款
		// 1 , save ebay order
		
		$MEO=OdEbayOrder::find()->where('ebay_orderid=\''.$ebay_orderid.'\'')->one();
		$AMEO=array(); 
		if(is_null($MEO)||$MEO->isNewRecord){
			$AMEO=array(
				'selleruserid'=>$selleruserid,
				'ebay_uid'=>$ebay_user->ebay_uid,
			);
			$MEO=new OdEbayOrder;
		}

		$AMEO['currency']=$oA->getChildrenAttrible('Total','currencyID');
		$o=$oA->toArray();
		//$o['Total'] = (int)$o['Total'];
		
		
		//testkh20160810 追查ebay 订单金额为0
		try {
			if (empty($o['Total'])){
				echo "\n".(__function__)." uid=".@$ebay_user->uid." order_id=".$ebay_orderid." ".$selleruserid." ebay api grand_total =0\n";
			}
			
			if (empty($o['subtotal'])){
				echo "\n".(__function__)." uid=".@$ebay_user->uid." order_id=".$ebay_orderid." ".$selleruserid." ebay api subtotal =0\n";
			}
		} catch (\Exception $ex) {
			echo "\n ".(__function__)." ebay total is zero Error Message :  ". $ex->getMessage()." Line no:".$ex->getLine()."\n";
		}
		echo "\n".(__function__)." uid=".@$ebay_user->uid." order_id=".$ebay_orderid." ".$selleruserid." ebay api  srn=".@$o['ShippingDetails']['SellingManagerSalesRecordNumber']."\n";
		
		$AMEO+=array(
			'ebay_orderid'=>$ebay_orderid,
			'orderstatus'=>$o['OrderStatus'],
			'ebaypaymentstatus'=>$o['CheckoutStatus']['eBayPaymentStatus'],
			'paymentmethod'=>$o['CheckoutStatus']['PaymentMethod'],
			'checkoutstatus'=>$o['CheckoutStatus']['Status'],
			'integratedmerchantcreditcardenabled'=>$o['CheckoutStatus']['IntegratedMerchantCreditCardEnabled'],

			'adjustmentamount'=>$o['AdjustmentAmount'],
			'amountpaid'=>$o['AmountPaid'],
			'amountsaved'=>$o['AmountSaved'],
			'salestaxpercent'=>@$o['ShippingDetails']['SalesTax']['SalesTaxPercent'],
			'salestaxamount'=>@$o['ShippingDetails']['SalesTax']['SalesTaxAmount'],
			'shippingservicecost'=>@$o['ShippingServiceSelected']['ShippingServiceCost'],
			'subtotal'=>$o['Subtotal'],
			'total'=>$o['Total'],

			'buyeruserid'=>$o['BuyerUserID'],
			'shippingservice'=>$o['ShippingServiceSelected']['ShippingService'],
//			'shippingservicecost'=>$o['ShippingServiceSelected']['ShippingServiceCost'],
			'shippingincludedintax'=>($o['ShippingDetails']['SalesTax']['ShippingIncludedInTax']=='false'?0:1),

			'externaltransactionid'=>@$o['ExternalTransaction']['ExternalTransactionID'],
			'feeorcreditamount'=>@$o['ExternalTransaction']['FeeOrCreditAmount'],
			'paymentorrefundamount'=>@$o['ExternalTransaction']['PaymentOrRefundAmount'],

			'shippingaddress'=>$o['ShippingAddress'],
			'externaltransaction'=> @$o['ExternalTransaction'],

			'buyercheckoutmessage'=>@$o['BuyerCheckoutMessage'],
			'shippingserviceselected'=>$o['ShippingServiceSelected'],

			'salesrecordnum'=>@$o['ShippingDetails']['SellingManagerSalesRecordNumber'],
			'responsedat'=>time(),
			
			//ebay 手续费
			'FinalValueFee'=>@$o['TransactionArray']['Transaction']['FinalValueFee'],
		); 
		//货币
		
		//时间 需要做判断 
		if(isset($o['CheckoutStatus']['LastModifiedTime'])){
			$AMEO['lastmodifiedtime']=strtotime($o['CheckoutStatus']['LastModifiedTime']);
		}
		if(isset($o['CreatedTime'])){
			$AMEO['createdtime']=strtotime($o['CreatedTime']);
		}
		if(isset($o['PaidTime'])){
			$AMEO['paidtime']=strtotime($o['PaidTime']);
		}
		if(isset($o['ShippedTime'])){
			$AMEO['shippedtime']=strtotime($o['ShippedTime']);
		}
		if(@isset($o['ExternalTransaction']['ExternalTransactionTime'])){
			$AMEO['externaltransactiontime']=strtotime($o['ExternalTransaction']['ExternalTransactionTime']);
		}
		
		// TransactionArray 
		if (isset($oA['TransactionArray']['Transaction']['TransactionID'])){
			$ts=array($oA['TransactionArray']['Transaction']);
		}else{
			$ts=$oA['TransactionArray']['Transaction'];
		}
		$t=$ts[0];
		// ship_email
		if(isset($t['Buyer'])&&isset($t['Buyer']['Email'])){
			$AMEO['ship_email']=$t['Buyer']['Email'];
		}
		//salesrecordnum
		if(strlen($AMEO['salesrecordnum'])==0&&isset($t['ShippingDetails']['SellingManagerSalesRecordNumber'])){
			$AMEO['salesrecordnum']=$t['ShippingDetails']['SellingManagerSalesRecordNumber'];
		}

		echo 'isNewRecord:'." MEO ".$MEO['orderstatus'] ." AMEO ".$AMEO['orderstatus']." \n ";


		$ifMerge=0;
		if($MEO->isNewRecord){
			$ifMerge=3;
		}

		if($MEO['orderstatus']=='Active'|| ($MEO['orderstatus']=='Completed' && $MEO['ebaypaymentstatus']!='NoPaymentFailure') ){ // &&$AMEO['orderstatus']=='Active'
			$ifMerge&=1;
		}
		//只有部分付款和  退款 可以拆分
		if(($MEO['orderstatus']=='Active'|| $MEO['ebaypaymentstatus']!='NoPaymentFailure')  &&  $AMEO['orderstatus']=='Completed'){
			$ifMerge&=2;
		}
		//Ebay Order 订单 完成,  也即 内容 不再接受 之后的 修改
		$ifEbayOrderCompleted=0;
		if($MEO['orderstatus']=='Completed' && $MEO['ebaypaymentstatus']=='NoPaymentFailure'){
			$ifEbayOrderCompleted=true;
			$EOderPayed=true; 
		}elseif($AMEO['orderstatus']=='Completed' && $AMEO['ebaypaymentstatus']=='NoPaymentFailure'){
			$EOderPayed=true;
		}

		/**
		 * 或总金额不对, 需要重新 再取一下 . 并且跳出不保存
		 */
		if($AMEO['total']==0){ // 退款订单处理
			//Yii::log('refund  order: '.$ebay_orderid );
			echo "refund\n";
			//合并退款的订单,并且不再打回原订单号了,需要向请求打回原订单号
			if($AMEO['adjustmentamount'] >=0 ){
				if(strpos($ebay_orderid,'-')){
					$MT=OdEbayTransaction::find()->where('transactionid=:t And itemid=:i And selleruserid=:s',[':t'=>$t['TransactionID'],':i'=>$t['Item']['ItemID'],':s'=>$selleruserid])->one();
					if(!is_null($MT) && strpos($MT->orderid,'-')===false && $MT->orderid != $ebay_orderid){
						$ebay_orderid=$MT->orderid;
					}
				}
				QueueGetorderHelper::Add($ebay_orderid,$selleruserid,$ebay_user->selleruserid);
				$logstr= "$ebay_orderid :  subtotal $AMEO[subtotal]  + shippingservicecost $AMEO[shippingservicecost] + adjustmentamount  $AMEO[adjustmentamount] != total $AMEO[total] \n "
					. var_export($AMEO['subtotal'] + $AMEO['shippingservicecost'] + $AMEO['adjustmentamount'] ,1) ." : ". var_export($AMEO['total'],1) ."\n " ;
				//Yii::log($logstr );
				echo $logstr;
				return false;
			}
			// 做退款 判断异常
			if(!self::check_ebayorder_backmoney($AMEO)){
				QueueGetorderHelper::Add($ebay_orderid,$selleruserid,$ebay_user->selleruserid);
				$logstr= "$ebay_orderid :  total $AMEO[total]  , externaltransaction:\n "
					. var_export($AMEO['externaltransaction'] ,1) ." \n " ;
				//Yii::log($logstr );
				echo $logstr;
				return false;
			}

		}elseif((strval($AMEO['subtotal'] + $AMEO['shippingservicecost'] + $AMEO['adjustmentamount']) !=$AMEO['total'])){
			//正常付款时 判断 异常
			QueueGetorderHelper::Add($ebay_orderid,$selleruserid,$ebay_user->selleruserid);
			$logstr= "$ebay_orderid :  subtotal $AMEO[subtotal]  + shippingservicecost $AMEO[shippingservicecost] + adjustmentamount  $AMEO[adjustmentamount] != total $AMEO[total] \n "
				. var_export($AMEO['subtotal'] + $AMEO['shippingservicecost'] + $AMEO['adjustmentamount'] ,1) ." : ". var_export($AMEO['total'],1) ."\n " ;
			//Yii::log($logstr );
			echo $logstr;
			//2小时过去了,错误 情况 还是没有更新,启用 ExternalTransaction 的判断
			if($AMEO['lastmodifiedtime'] < time()-7200){
				$externaltransaction_amout=0;
				if(is_array($AMEO['externaltransaction']) && count($AMEO['externaltransaction']) ){
					if(isset($AMEO['externaltransaction']['ExternalTransactionID'])){
						$externaltransaction_amout=$AMEO['externaltransaction']['PaymentOrRefundAmount'];
					}elseif(count($AMEO['externaltransaction'])){
						foreach($AMEO['externaltransaction'] as $et){
							$externaltransaction_amout+=$et['PaymentOrRefundAmount'];
						}
					}
				}
				// 支付次数 与 付款总额 不对的 情况 
				if($externaltransaction_amout!=$AMEO['total']){
					$logstr= "$ebay_orderid :  externaltransaction_amout $externaltransaction_amout  != total $AMEO[total] \n ";
					//Yii::log($logstr);
					echo $logstr;
					return false;
				}
			}else{
				return false;
			}

		}
		//已经付款并且,总额与 付款额不对,  需要重新 再取一下 . 并且跳出不保存
		if($ifEbayOrderCompleted
			&& ($AMEO['amountpaid']!=$AMEO['total'])){
				QueueGetorderHelper::Add($ebay_orderid,$selleruserid,$ebay_user->selleruserid);
				$logstr= "$ebay_orderid : paid: $AMEO[amountpaid]  !=  total: $AMEO[total]  \n ";
				//Yii::log($logstr);
				echo $logstr;
				return false;
			}
		// 最后修改时间不对的,也退出更新!
		if($MEO['lastmodifiedtime'] > 0  && $AMEO['lastmodifiedtime'] >0 && $AMEO['lastmodifiedtime']<$MEO['lastmodifiedtime']){
			$logstr= "$ebay_orderid :  old > new lastmodifiedtime : $MEO[lastmodifiedtime] >  $AMEO[lastmodifiedtime]  \n";
			//Yii::log($logstr);
			echo $logstr;
			//return false;
		}

		Helper_Array::removeEmpty($AMEO);
		// order 中的 地址信息
		$o_shipping_address=self::setShippingAddress($o['ShippingAddress']);
		echo "\n".(__function__)." uid=".@$ebay_user->uid." order_id=".@$ebay_orderid." selleruserid=".@$selleruserid.' shipp address='.json_encode(@$o_shipping_address)."\n";
		$AMEO+=$o_shipping_address;
		//过滤Invalid Request
		if ($AMEO['ship_email'] == 'Invalid Request') {
			$AMEO['ship_email'] = '';
		}
		$MEO->setAttributes($AMEO,false);
		
		if(isset($o['ShippedTime'])){
			$MEO->shippedtime=strtotime($o['ShippedTime']);
		}
		$MEO->save();

		if(!$ifMerge){
			//订单数是否正常
			$ETc=OdEbayTransaction::find()->where('eorderid=:e And selleruserid=:s',array(':e'=>$MEO->eorderid,':s'=>$selleruserid))->count();
			if(count($ts)!=$ETc){
				$ifMerge=3;
			}
		}
		//退款 ,这种情况下 订单应该是不能完成的 状况下
		if(self::check_ebayorder_backmoney($MEO)){
			$ifEbayOrderCompleted=0;
			$ifMerge&=2;
			$EOderBackMoney=true;
		}
		//如果有物流信息，组织到array中
		$trackarr=[];
		if (isset($o['ShippingDetails']['ShipmentTrackingDetails']['ShipmentTrackingNumber'])){
			$trackarr['num']=$o['ShippingDetails']['ShipmentTrackingDetails']['ShipmentTrackingNumber'];
		}
		if (isset($o['ShippingDetails']['ShipmentTrackingDetails']['ShippingCarrierUsed'])){
			$trackarr['name']=$o['ShippingDetails']['ShipmentTrackingDetails']['ShippingCarrierUsed'];
		}
		
		// 保存 Transaction
		$MTs=array();
		
		//20171030目前因为 不能确定orderstatus 是否能代码发货完成， 合并付款的订单 只能再同步子订单确认发货时间
		if (count($ts)>1){
			$isCheckShipTime = true;
			
		}else{
			$isCheckShipTime = false;
		}
		$checkShipTimeCount = 0;
		
		foreach($ts as $t){
			if(Helper_xml::isArray($t)){
				//20171030
				if ($isCheckShipTime && !empty($t['OrderLineItemID'])){
					
					if (empty($t['ShippedTime'])){
						// api 返回 的结果 没有发货时间的再需要确认
						$this->eBayAuthToken=$ebay_user->token;
						// $t['OrderLineItemID'] 为合并的子订单号
						$this->_before_request_xmlarray['OrderID']=$t['OrderLineItemID'];
						$r=$this->api();
						if (!empty($r['OrderArray']['Order']['ShippedTime'])){
							$t['ShippedTime'] = $r['OrderArray']['Order']['ShippedTime'];
						}
						
						
						if (!empty($t['ShippedTime'])){
							$_CACHE_SHIPTIME [$t['OrderLineItemID']] = $t['ShippedTime'];
							$checkShipTimeCount++;
						}
						echo "\n ".$ebay_user->selleruserid."OrderLineItemID=".@$t['OrderLineItemID']." ShippedTime= ".@$t['ShippedTime'];
					}
					
				}
				
				$MTs[]=$MT=$this->saveTransaction($t,$MEO,$selleruserid,$ifEbayOrderCompleted);
			
				//记录ebay后台合并付款时，旧的ebayorderid，删除旧的od_order
				if ($MT->orderid != $MT->itemid.'-'.$MT->transactionid){
					OdOrder::deleteAll('order_source_order_id = :id',[':id'=>$MT->itemid.'-'.$MT->transactionid]);
				}
				//如果tracsaction中有物流信息，以此为准
				if (count($trackarr)==0){
					if (isset($t['ShippingDetails']['ShipmentTrackingDetails'])&&isset($t['ShippingDetails']['ShipmentTrackingDetails']['ShipmentTrackingNumber'])){
						$trackarr['num']=$t['ShippingDetails']['ShipmentTrackingDetails']['ShipmentTrackingNumber'];
					}
					if (isset($t['ShippingDetails']['ShipmentTrackingDetails'])&&isset($t['ShippingDetails']['ShipmentTrackingDetails']['ShippingCarrierUsed'])){
						$trackarr['name']=$t['ShippingDetails']['ShipmentTrackingDetails']['ShippingCarrierUsed'];
					}
				}
			}
		}


		//是否  可以生成订单
// 		if($EOderPayed || $EOderBackMoney){
			self::ebayOrder2myorder($MEO,$MTs,$ifMerge,$EOderBackMoney,$ebay_user->uid,$trackarr);
// 		}

		self::save_externaltransaction($MEO,$selleruserid,$ts);
//		self::save_shipmenttrackingnumber($ts);

		$currencyInfo = [];
		try {
			$RedisFinalValueFeeCurrency = RedisHelper::RedisGet("EbayOrderData", "FinalValueFeeCurrency_".$selleruserid);
			if (isset($o['TransactionArray']['Transaction']['FinalValueFeeCurrency'])){
				$currencyInfo['FinalValueFeeCurrency'] = $o['TransactionArray']['Transaction']['FinalValueFeeCurrency'];
				//echo "\n".(__function__)." uid=".@$ebay_user->uid." order_id=".$ebay_orderid." ".$selleruserid." ebay api  srn=".@$o['ShippingDetails']['SellingManagerSalesRecordNumber']."\n";
					
				//设置账号佣金的默认币种
				if (empty($RedisFinalValueFeeCurrency) && !empty($currencyInfo['FinalValueFeeCurrency'] )){
					if ($RedisFinalValueFeeCurrency != $currencyInfo['FinalValueFeeCurrency'] ){
						RedisHelper::RedisSet("EbayOrderData", "FinalValueFeeCurrency_".$selleruserid ,$currencyInfo['FinalValueFeeCurrency'] );
					}
				}
					
				echo "\n FinalValueFeeCurrency is ".$currencyInfo['FinalValueFeeCurrency']." and redis FinalValueFeeCurrency = $RedisFinalValueFeeCurrency *****************";
			}else{
					
				if (!empty($RedisFinalValueFeeCurrency)){
					$currencyInfo['FinalValueFeeCurrency'] = $RedisFinalValueFeeCurrency;
				}
					
				echo "\n no FinalValueFeeCurrency and redis FinalValueFeeCurrency = $RedisFinalValueFeeCurrency *****************";
			}
		} catch (\Exception $e) {
			echo "\n".(__function__)."  uid=".@$ebay_user->uid." order_id=".$ebay_orderid." ".$selleruserid." ebay api  srn=".@$o['ShippingDetails']['SellingManagerSalesRecordNumber']." error message ".$e->getMessage()." line no ".$e->getLine()."\n";
		}
		
		try {
			$RedisFeeOrCreditAmountCurrency = RedisHelper::RedisGet("EbayOrderData", "FeeOrCreditAmountCurrency_".$selleruserid);
			if (isset($o['ExternalTransaction']['FeeOrCreditAmountCurrency'])){
				$currencyInfo['FeeOrCreditAmountCurrency'] = $o['ExternalTransaction']['FeeOrCreditAmountCurrency'];
				
				//设置账号手续费的默认币种
				if (empty($RedisFeeOrCreditAmountCurrency) && !empty($currencyInfo['FeeOrCreditAmountCurrency'] )){
					if ($RedisFeeOrCreditAmountCurrency != $currencyInfo['FeeOrCreditAmountCurrency'] ){
						RedisHelper::RedisSet("EbayOrderData", "FeeOrCreditAmountCurrency_".$selleruserid ,$currencyInfo['FeeOrCreditAmountCurrency'] );
					}
				}
				
				echo "\n FeeOrCreditAmountCurrency is ".$currencyInfo['FeeOrCreditAmountCurrency']." and redis FeeOrCreditAmountCurrency = $RedisFeeOrCreditAmountCurrency ***************** ";
			}else{
				if (!empty($RedisFeeOrCreditAmountCurrency)){
					$currencyInfo['FeeOrCreditAmountCurrency'] = $RedisFeeOrCreditAmountCurrency;
				}
				echo "\n no FeeOrCreditAmountCurrency and redis FeeOrCreditAmountCurrency = $RedisFeeOrCreditAmountCurrency ***************** ";
			}
		} catch (\Exception $e) {
		}
		
		
		if (!empty($currencyInfo)){
			self::saveOrderCurrencyInfo($ebay_orderid , $selleruserid , $currencyInfo);
		}
	}

	/**
	 *  @author tangwc 2012-02-06
	 *  更新订单发货状态
	 *  
	 */
	function changeOneOrder($oA,$ebay_orderid,$selleruserid,$uid){
		// 1 , save ebay order
		$MEO=OdEbayOrder::model()->find('ebay_orderid=?',$ebay_orderid);
		$AMEO=array(); 
		$o=$oA->toArray();

		//时间 需要做判断 
		if(isset($o['ShippedTime'])){
			$AMEO['shippedtime']=strtotime($o['ShippedTime']);
			if($AMEO['shippedtime']){
				$order_ids=array();
				foreach($MEO->transactions as $MT){
					$MT->shipped=true;
					$order_ids[$MT->order_id]=$MT->order_id;
					$MT->save();
				}
				foreach($order_ids as $order_id){
					$MMO=OdOrder::find()->where('order_id=?',$order_id)->one();
					//$MMO->shipped_time=$AMEO['shippedtime'];
					//$MMO->recalculate();

					$MMO->save(false);
				}
			}
		}

		//
		Helper_Array::removeEmpty($AMEO);   
		$MEO->setAttributes($AMEO);
		if(!$MEO->save(false)){ 
			echo $MEO->getErrors()."\n";
		}


	}


	/**
	 *  @author lxqun 2011-06-14f
	 *  保存交易数组
	 *  @return $MT Ebay_Transaction
	 *  @param $ifEbayOrderCompleted  订单是否完成, 是否修改内容 , 1:不需要,0需要.
	 */
	function saveTransaction($t,&$MEO,$selleruserid,$ifEbayOrderCompleted=0){
		echo 'transaction '."\n";
		$MT=OdEbayTransaction::find()->where('transactionid='.$t['TransactionID'].' And itemid='.$t['Item']['ItemID'].' And selleruserid=\''.$selleruserid.'\'')->one();
		$title=@$t['Item']['Title'];
		// 并未保存完成数据的情况 
		if($ifEbayOrderCompleted&&!empty($MT)){
			if(!($MT->eorderid==$MEO->eorderid && $MT->orderlineitemid==$t['OrderLineItemID'])){
				$ifEbayOrderCompleted=0;
			}
		}

		$AMT=array();
		$AMT['quantitypurchased']=$t['QuantityPurchased'];
		$AMT_1=array(
			'transactionsiteid'=>$t['Item']['Site'],
			'orderlineitemid'=>$t['OrderLineItemID'],
			'transactionprice'=>$t['TransactionPrice'],
			'shippingservicecost'=>@$t['ActualShippingCost'],
			'platform'=>$t['Platform'],
			'salesrecordnum'=>@$t['ShippingDetails']['SellingManagerSalesRecordNumber'],
			'finalvaluefee'=>isset($t['FinalValueFee']) ? $t['FinalValueFee'] : '',

			'quantitypurchased'=>$t['QuantityPurchased'],

			'buyer'=>Helper_xml::isArray($t['Buyer'])?$t['Buyer']->toArray():null,
			'status'=>Helper_xml::isArray($t['Status'])?$t['Status']->toArray():null,

			'eorderid'=>$MEO->eorderid,
			'orderid'=>$MEO->ebay_orderid,
			'amountpaid'=>$MEO->amountpaid,
			'shippingserviceselected'=>$MEO->shippingserviceselected,
			//'paidtime'=>$MEO->paidtime,
		); 
		
		if (isset($t['ShippingDetails']['ShipmentTrackingDetails'])&&(isset($t['ShippingDetails']['ShipmentTrackingDetails']['ShipmentTrackingNumber'])||isset($t['ShippingDetails']['ShipmentTrackingDetails']['ShippingCarrierUsed']))){
			$AMT['shipmenttrackingdetail']=[
				'0'=>[
					'ShipmentTrackingNumber'=>'',
					'ShippingCarrierUsed'=>''
				]
			];
			$AMT['shipmenttrackingdetail']['0']['ShipmentTrackingNumber']=@$t['ShippingDetails']['ShipmentTrackingDetails']['ShipmentTrackingNumber'];
			$AMT['shipmenttrackingdetail']['0']['ShippingCarrierUsed']=@$t['ShippingDetails']['ShipmentTrackingDetails']['ShippingCarrierUsed'];
		}else{
			$AMT['shipmenttrackingdetail']=@$t['ShippingDetails']['ShipmentTrackingDetails'];
		}
		
		//货币
		$AMT_1['currency']=$t->getChildrenAttrible('TransactionPrice','currencyID');
		$AMT_1['finalvaluefee_currency']=$t->getChildrenAttrible('FinalValueFee','currencyID');
		$_skutmp=@$t['Item']['SKU'] == 'null' ? '' : @$t['Item']['SKU'];
		$AMT_2=array(
			//'sku'=> @$t['Item']['SKU'] == 'null' ? '' : @$t['Item']['SKU'], // Helper_SKU::EbayItemDecode(Helper_conver::decode_uft8html(@$t['Item']['SKU'])),
			'title'=>$title,
		);
		// variation 下的 sku ,及 title 
		if(isset($t['Variation'])){
			$AMT_2['variation']= Helper_xml::isArray($t['Variation'])?$t['Variation']->toArray():'' ;
		}
		if(isset($AMT_2['variation']['VariationTitle']) && strlen($AMT_2['variation']['VariationTitle'])>0){
			$AMT_2['title']=$AMT_2['variation']['VariationTitle'];
		}
		if(isset($AMT_2['variation']['SKU']) && strlen($AMT_2['variation']['SKU'])>0){
			$AMT_2['variation']['SKU']=$AMT_2['variation']['SKU'] == 'null' ? '' : $AMT_2['variation']['SKU']; // Helper_SKU::EbayItemDecode($AMT_2['variation']['SKU']);
			//$AMT_2['sku']= $AMT_2['variation']['SKU'] == 'null' ? '' : $AMT_2['variation']['SKU'];
			$_skutmp=$AMT_2['variation']['SKU'] == 'null' ? '' : $AMT_2['variation']['SKU'];
		}
		$_skutmp=preg_replace('/\&amp;/', '&', $_skutmp);
		$AMT_2['sku']=html_entity_decode(urldecode(urlencode($_skutmp)));
		//Yii::log('FixedSKU:'. print_r($AMT_2['sku'],1));
		//需要修改内容
		if(!$ifEbayOrderCompleted){
			$AMT+=$AMT_1 ;
			$AMT+=$AMT_2 ;
		}else if(strlen($MT['sku'])==0 || strlen($MT['title'])==0){
			$AMT+=$AMT_2 ;
		}

		//时间
		if(isset($t['CreatedDate'])){
			$AMT['createddate']=strtotime($t['CreatedDate']);
		}


		//支付状态
		if(isset($MEO['checkoutstatus'])){
			if($MEO['checkoutstatus']=='Complete'){
				if($MEO['ebaypaymentstatus']=='NoPaymentFailure' && $MEO->paidtime){
					$AMT['status_payment']=OdEbayTransaction::STATUS_PAYMENT_COMPLETE;
					$AMT['paidtime']=$MEO->paidtime;
				}else{
					$AMT['status_payment']=OdEbayTransaction::STATUS_PAYMENT_PROCESSING;
				}
			}else{
				$AMT['status_payment']=OdEbayTransaction::STATUS_PAYMENT_WAITING;
			}
		}
		//退款状态
		if(self::check_ebayorder_backmoney($MEO)){
			$AMT['status_payment']=OdEbayTransaction::STATUS_PAYMENT_BACKMONEY;
		}

		// 发货状态
		if($MEO['shippedtime']||isset($t['ShippedTime'])){
			$AMT['shipped']=true;
		}
		if(!is_null($MT)){
			// 不再接收 付款之后再次 改为未付款 .
			if($AMT['status_payment']!=OdEbayTransaction::STATUS_PAYMENT_COMPLETE && $AMT['status_payment']!=OdEbayTransaction::STATUS_PAYMENT_BACKMONEY 
				&&$MT['status_payment']==OdEbayTransaction::STATUS_PAYMENT_COMPLETE){
					unset($AMT['status_payment']);
				}
		}

		// 基础信息
		if(is_null($MT)){
			$AMT+=array(
				// 用户
				//'uid'=>$uid,
				'selleruserid'=>$selleruserid,
				'itemid'=>$t['Item']['ItemID'],
				'transactionid'=>$t['TransactionID'],
			);
			$Added_quantitypurchased=@$AMT['quantitypurchased'];

			$paypalemailaddress=QueueGetorderHelper::getOnePaypalEmailAddress($t['OrderLineItemID']);
			if($paypalemailaddress){
				$AMT['paypalemailaddress']=$paypalemailaddress;
			}
			
		}else{
			// 3.4  新添加了的 订单货品数量
			//             if($AMT['quantitypurchased']!= $MT->quantitypurchased){
			//                 $Added_quantitypurchased=$AMT['QuantityPurchased']-$MT->quantitypurchased;
			//             }
			if(empty($MT->paypalemailaddress)){
				$paypalemailaddress=QueueGetorderHelper::getOnePaypalEmailAddress($t['OrderLineItemID']);
				if($paypalemailaddress){
					$AMT['paypalemailaddress']=$paypalemailaddress;
				}
			}
		}
		Helper_Array::removeEmpty($AMT);
		if (is_null($MT)){$MT=new OdEbayTransaction();}
		$MT->setAttributes($AMT,false);

		// 补上空字段
		if(empty($MT['finalvaluefee_currency'])){
			$MT['finalvaluefee_currency']=$MT['currency'];
		}
		$MT->save(false);
		// dzt20190905 重新绑账号出现重复订单/item为空，发现srn为空，导致item插入失败，
		// 但对应的od_ebay_transaction记录则有数据，尝试reload看看数据能否载入
		$MT->refresh();
		return $MT;
	}



	/**
	 *  @author lxqun 2011-06-14
	 *  生成 Ibay 订单数组
	 *  @param $EOderBackMoney 订单是退款
	 */
	static function ebayOrder2myorder(&$MEO,$MTs,$ifMerge=0,$EOderBackMoney=0,$uid,$trackarr=[]){
		global $_CACHE_SHIPTIME;
		$oldorder_ids=array(); // 已经存在的 全部order_id .
		$MTnoorder_ids=array(); // 还没有order_id 的T 
		$mtcount=0; 
		$oldorder_id=null; //MT中的 order_id;
		$ifEbayOrderCompleted=false;

		foreach($MTs as $MT){
			$oldMTids[$MT->id]=$MT->id;
			if($MT->order_id>0){
				$oldorder_ids[$MT->order_id]=$MT->order_id;
				$oldorder_id= $MT->order_id;
			}else{
				$MTnoorder_ids[$MT->id]=$MT;
			}
			$mtcount++;
		}
		$error="\n ===================================== \n ".
			'-----------  '.print_r(date('Ymd H:i:s'),1)." ---  \n".
			'ebay_orderid:'.$MEO->ebay_orderid." \n ".
			'$mtcount: '.print_r($mtcount,1)." \n ".
			"oldorder_ids: ".print_r($oldorder_ids,1)." \n ";

		//Yii::log($error);
		
		$error=null;
		if($mtcount==0) return 0;
		if(empty($oldorder_id)){// 新
			if($EOderBackMoney){
				return false;  //退款的订单不用处理,订单不进入到 系统中
			}
			
			$MMO=self::ebayOrder2myorder_save($MEO,$MTs,null,false,$ifEbayOrderCompleted,$uid,$trackarr);

		}elseif($EOderBackMoney){ // 已经生成的订单  ,退款订单 ,订单 标记为 退款 
			$MMO=OdOrder::find()->where("order_id=".$oldorder_id)->one();
// 			if( OrderHelper::checkStatus($MMO,'can_backmoney')){
// 				// 在打单之前  , 判断是否 可以 退款
// 			}
			//退款 -- 不用判断 
			OrderHelper::setStatus($MMO, 'backmoney');
		}else{  // 重新生成 
			echo 'start save order:'.$oldorder_id."\n";
			$MMO=OdOrder::find()->where(['order_id'=>$oldorder_id,'order_source'=>'ebay'])->one();
			$MMO=self::ebayOrder2myorder_save($MEO,$MTs,$MMO,false,false,$uid,$trackarr);
		}
		// return $MMOs;
	}

	/**
	 *  @author lxqun 2011-06-14
	 *  生成 Ibay 订单数组
	 *  @param $ifEbayOrderCompleted  订单是否完成, 是否修改内容 , 1:不需要,0需要.
	 *  观察猜测 $MEO 为model ebay order  2016-11-07
	 */
	static function ebayOrder2myorder_save(&$MEO,&$MTs,$MMO=null,$merge=false,$ifEbayOrderCompleted=false,$uid,$trackarr=[]){
		global $_CACHE_SHIPTIME;
		$isNew=false;  // New Ebay order 
		$AMMO=array();
		$tempMt = null;
		$ebay_commission = 0;
		
		$isShippedByMergeOrder = count($MTs)>1; // 假如一张订单这个默认为false(未发货), 多张订单默认为true(发货)
		$MegreOrderShipTIme = '';
		
		if(is_null($MMO)||empty($MMO->selleruserid)){
			foreach($MTs as $MT){
				if(strlen($MT->selleruserid)) {
					$tempMt = $MT;
					break 1;
				}
			}
			$AMMO=array(
				'selleruserid'=>isset($tempMt->selleruserid) ? $tempMt->selleruserid : '',
				'saas_platform_user_id'=>$MEO->ebay_uid,
			);
			if(is_null($MMO)){
				$MMO=new OdOrder($AMMO);
				$isNew=true;
			}
		}
		
		//2016-11-07 无法 识别 上述逻辑的原理， 先强提ebay佣金
		foreach($MTs as $tmpMT){
			if (isset($tmpMT->finalvaluefee)){
				$ebay_commission += $tmpMT->finalvaluefee;
			}
			
			
			//假如没有合并过的订单，则需要关发货标记
			if (!empty($tmpMT->orderlineitemid)){
				$orderlineitemid = $tmpMT->orderlineitemid;
			}else if (!empty($tmpMT->OrderLineItemID)){
				$orderlineitemid = $tmpMT->OrderLineItemID;
			}else{
				$orderlineitemid = '';
			}
			if (isset($_CACHE_SHIPTIME[$orderlineitemid])){
				$MegreOrderShipTIme = $_CACHE_SHIPTIME[$orderlineitemid];
			}else{
				$isShippedByMergeOrder = false;
				
			}
		}
		
		
// 		if ($MMO->order_status > 200) {
// 			return $MMO;
// 		}
		$order_status = '';
 		if($MMO->isNewRecord||$MMO->order_status<=200||$isNew==true){
			$isNew=true;
			$order_status = 100; // 新订单默认为未付款
			if (($MEO['orderstatus'] == 'Active'&& empty($MEO['paidtime']))||($MEO['orderstatus']=='Completed' && $MEO['ebaypaymentstatus']=='PayPalPaymentInProcess')) {
				$order_status = 100;
			} else if ( $MEO['shippedtime'] ) {
				$order_status = 500;
//			} else if ($MEO['paidtime']||($MEO['checkoutstatus'] == 'Complete'&& $MEO['ebaypaymentstatus']=='NoPaymentFailure')) {
			} else if ($MEO['paidtime']) {
				$order_status = 200;
			} else if ($MEO['orderstatus']=='Cancelled') {
				$order_status = 600;
			}else if ($isShippedByMergeOrder ){
				//20171030 合并付款的订单， 检测成功后也列为已发货
				$order_status = 500;
			}
 		}else{
 			$order_status = $MMO->order_status;
 		}
			$AMMO=array(
				'selleruserid'=>isset($tempMt->selleruserid) ? $tempMt->selleruserid : (!empty($MMO->selleruserid)?$MMO->selleruserid:''),
				'order_status'=> $order_status, // 新生成的订单 都是 已经付款 状态 
				'saas_platform_user_id'=>$MEO->ebay_uid,
			);
// 		}
			
			
			
			
		$AMMO+=array(
			'order_source_order_id'=>$MEO['ebay_orderid'],
			'source_buyer_user_id'=>$MEO['buyeruserid'],
			'currency'=>$MEO['currency'],
			//支付 时间 状态
//			'payment_method'=>$MEO['paymentmethod'],

			// 运输 
			'order_source_shipping_method'=>$MEO['shippingserviceselected']['ShippingService'],

			//时间 
			'order_source_create_time'=>$MEO['createdtime'],
			'paid_time'=>$MEO['paidtime'],
			//'shipped_time'=>$MEO['shippedtime'],
			'order_source_site_id'=>isset($tempMt->transactionsiteid) ? $tempMt->transactionsiteid : ''
		);
		// 支付 状态   转换  ,及 其它 状态 转换, 
		if(($MEO['orderstatus']=='Completed' && $MEO['ebaypaymentstatus']=='NoPaymentFailure')||$MEO['paidtime']>0){
			//已经付款
			$AMMO['pay_status']=1;
		}elseif ($MEO['checkoutstatus']=='Pending'){
			$AMMO['pay_status']=2;
		}elseif($MEO['orderstatus']=='Completed' && $MEO['ebaypaymentstatus']=='PayPalPaymentInProcess'){
			$AMMO['pay_status']=2;
		}elseif($MEO['orderstatus']=='Active'){
			//未付款
			$AMMO['pay_status']=0;
		}
		
		
		
		
		///////////////////////////////////////////////////////////////////////////////////////
		//发货状态 ,是否已经发货 
		$AMMO['shipping_status']=0;
		if($MEO['shippedtime']){
			$AMMO['shipping_status']=1;
			$AMMO['delivery_time']=$MEO['shippedtime'];
		}
		
		if ($isShippedByMergeOrder && !empty($MegreOrderShipTIme)){
			$AMMO['shipping_status']=1;
			// dzt20190826 前面 $MegreOrderShipTIme从cache获取时获取的值并没有转换
			// 因为更新前报错，这个间接导致多item已发货的订单小老板状态总是停留在200，不能更新到500，
			$shipTime = strtotime($MegreOrderShipTIme);
			if($shipTime === false){
			    $AMMO['delivery_time'] = $MegreOrderShipTIme;
			}else{
			    $AMMO['delivery_time'] = $shipTime;
			}
		}
		///////////////////////////////////////////////////////////////////////////////////////////////////
		
		//testkh20160810 追查ebay 订单金额为0
		try {
			if (empty($MEO['total'])){
				echo "\n".(__function__)." uid=".@$uid." order_id=".$MEO['ebay_orderid']." ".$AMMO['selleruserid']." set AMMO  grand_total =0";
			}
				
			if (empty($MEO['subtotal'])){
				echo "\n".(__function__)." uid=".@$uid." order_id=".$MEO['ebay_orderid']." ".$AMMO['selleruserid']." set AMMO   subtotal =0";
			}
		} catch (\Exception $ex) {
			echo "\n ".(__function__)." ebay total is zero Error Message :  ". $ex->getMessage()." Line no:".$ex->getLine()."\n";
		}
		

		// 新订单需要 存主要信息
		// 未完成订单 需要存主要信息 
		if($isNew || !$ifEbayOrderCompleted ){
			$AMMO+=array(
				'order_source'=>'ebay',
				// 总额

				//'amountpaid'=>$MEO['amountpaid'], -- 已经付款金额
				//'amountsaved'=>$MEO['amountsaved'],
				'subtotal'=>$MEO['subtotal'],
				'grand_total'=>$MEO['total'],
				'discount_amount'=>$MEO['adjustmentamount'], // 折扣
				//运费
				'shipping_cost'=>$MEO['shippingservicecost'],
				// 税
				//'salestaxpercent'=>$MEO['salestaxpercent'],
				//'shippingincludedintax'=>$MEO['shippingincludedintax'],
				//'salestaxamount'=>$MEO['salestaxamount'],
				//'tax_cost'=>$MEO['salestaxamount'],
				//其它  -- 平台销售编号
				'order_source_srn'=>$MEO['salesrecordnum'],
				
			);
			//ebay 佣金
			if ( isset($ebay_commission)){
				$AMMO['commission_total'] = $ebay_commission;
				echo "\n".(__function__)." uid=".@$uid." order_id=".$MEO['ebay_orderid']." ".$AMMO['selleruserid']." ebay fee:".$ebay_commission;
			}else{
				echo "\n".(__function__)." uid=".@$uid." order_id=".$MEO['ebay_orderid']." ".$AMMO['selleruserid']." no ebay fee";
			}
			
			//paypal 手续费
			if (isset($MEO['externaltransaction']['FeeOrCreditAmount'])){
				$AMMO['paypal_fee'] = $MEO['externaltransaction']['FeeOrCreditAmount'];
				echo "\n".(__function__)." uid=".@$uid." order_id=".$MEO['ebay_orderid']." ".$AMMO['selleruserid']." paypal fee:".$MEO['externaltransaction']['FeeOrCreditAmount'];
			}else{
				echo "\n".(__function__)." uid=".@$uid." order_id=".$MEO['ebay_orderid']." ".$AMMO['selleruserid']." no paypal fee";
			}
		}
		//地址信息 ,只有为空时 才可以 编辑
		if($isNew || !$ifEbayOrderCompleted ||
			((strlen($MMO['ship_name'].$MMO['ship_country'].$MMO['ship_street1'])==0) &&
			$MEO['ship_name']&&$MEO['ship_country']&&$MEO['ship_street1'])
		){
			$AMMO+=array(
				'consignee'=>$MEO['ship_name'],
				'consignee_city'=>$MEO['ship_cityname'],
				'consignee_province'=>$MEO['ship_stateorprovince'],
				'consignee_country_code'=>$MEO['ship_country'],
				'consignee_country'=>$MEO['ship_countryname'],
				//             'consignee_address'=>$MEO['ship_street1'].(empty($MEO['ship_street2'])?'':(' '.$MEO['ship_street2'])),
				'consignee_postal_code'=>$MEO['ship_postalcode'],
				'consignee_phone'=>$MEO['ship_phone'],
				'consignee_email'=>$MEO['ship_email'],
				'consignee_address_line1'=>$MEO['ship_street1'],
				'consignee_address_line2'=>$MEO['ship_street2'],
				'consignee_company'=>$MEO['ship_company'],
				'user_message'=> $MEO['buyercheckoutmessage'],
				'create_time'=> time(),
				'update_time'=>time()
			);
		}
		if (isset($trackarr['num'])||isset($trackarr['name'])){
			$AMMO['orderShipped']['0']=[
				'order_source_order_id'=>$AMMO['order_source_order_id'],
				'order_source'=>'ebay',
				'selleruserid'=>$MMO->selleruserid,
				'tracking_number'=>@$trackarr['num'],
				'tracking_link'=>'',
				'shipping_method_name'=>@$trackarr['name'],
				'addtype'=>'订单同步获取',
			];
			$AMMO['shipping_status']=1;
		}else{
			$AMMO['orderShipped']=[];
		}
		
		//支付方式
		 if (isset($MEO['paymentmethod'])){
			$AMMO['payment_type']=$MEO['paymentmethod'];
		}
		

		Helper_Array::removeEmpty($AMMO);

		//var_dump($AMMO);

// 		$MMO->setAttributes($AMMO,false);
// 		if(!$MMO->save(false)){ 
// 			echo $MMO->getErrors();
// //			SysLogHelper::SysLog_Create("order",__CLASS__, __FUNCTION__,"Error 5.1","Save the order to fail,odOrderParams:".print_r($AMMO,true)."error:".$MMO->getErrors(), "Error");
// 		}
// 		if($isNew||$merge){
// 			foreach($MTs as $MT){
// 				$MT->order_id=$MMO->order_id;
// 				$MT->save();
// 			}
// 		}
// 		//重取对象主要是包括下面的 transaction
// // 		$MMO=OdOrder::find($MMO->order_id);
// // 		$MMO->recalculate();
// // 		$MMO->save();

// 		// 客户信息表
// 		if($isNew || !$ifEbayOrderCompleted ){
// 			OdCustomerHelper::saveCustomer($MEO['buyeruserid'],$MMO->selleruserid,$MMO);
// 		}

		//新订单拉取item信息
		if ($isNew){
			/*
			$ets = OdEbayTransaction::find ()->where ( [
					'=',
					'orderid',
					$AMMO['order_source_order_id']
					] )->all ();
			echo "\n v1.2  order_id=".$AMMO['order_source_order_id']." mts count = ".count($ets);
			*/
			$ebayItemList = [];
			$order_source_site_id = isset($AMMO['order_source_site_id'])?$AMMO['order_source_site_id']:'';
			foreach($MTs as $et){
				// 获取对应item的主图地址
				$photo = '';
				if (isset ( $et->variation ['VariationSpecifics'] ['NameValueList'] ) && is_array ( $et->variation ['VariationSpecifics'] ['NameValueList'] )) {
					$ebayVarList = [ ];
					if (isset ( $et->variation ['VariationSpecifics'] ['NameValueList'] ['Name'] )) {
						// 多个属性时与单个不一样
						$ebayVarList [] = [
						$et->variation ['VariationSpecifics'] ['NameValueList'] ['Name'] => $et->variation ['VariationSpecifics'] ['NameValueList'] ['Value']
						];
					} else {
						// 多个属性时与单个不一样
						foreach ( $et->variation ['VariationSpecifics'] ['NameValueList'] as $tmpEbayVarList ) {
				
							$ebayVarList [] = [
							$tmpEbayVarList ['Name'] => $tmpEbayVarList ['Value']
							];
						}
					}
				}
				$ebay_product_attributes = '';
				if (! empty ( $ebayVarList )) {
					$ebay_product_attributes = json_encode ( $ebayVarList );
				}
				$item = EbayItem::find ()->where ( [
						'itemid' => $et->itemid
						] )->one ();
				//多属性产品不使用主图作为图片
				if (! empty ( $item ) && empty($ebayVarList) ) {
					$photo = $item->mainimg;
				} else {
					// 以为使用异步队列
					echo "\n **** photo queue :" . $et->itemid . " @$ebay_product_attributes, $uid";
					$photo = EbayOrderHelper::getItemPhoto ( $et->itemid, @$ebay_product_attributes, $uid );
					echo "\n photo: $photo ";
					/* 20161128kh start 20161128kh end */
					if (empty ( $photo )) {
					// 即时的获取EbayItem
						$getitem_api = new getsingleitem();
						try {
							set_time_limit ( 0 );
							$r = $getitem_api->apiItem ( $et->itemid );
// 							echo "start 317" . "\n";
						} catch ( \Exception $ex ) {
							\Yii::error ( print_r ( $ex->getMessage () ) );
						}
						if (! $getitem_api->responseIsFail) {
// 							echo "start 321" . "\n";
							\Yii::info ( 'get success' );
							if (! empty ( $r ['PictureURL'] )) {
								$pic = $r ['PictureURL'];
								if (is_array ( $pic )) {
									$pic_arr = $pic;
								} else {
									$pic_arr = array (
									$pic
										);
								}
												
								$photo = $pic_arr ['0'];
							}
						}
					} // end of photo 即时的获取EbayItem
				}
				
				// listing 站点前缀
				try {
					$siteUrl = \common\helpers\Helper_Siteinfo::getSiteViewUrl ();
					$siteList = \common\helpers\Helper_Siteinfo::getSite ();
					$currentProudctUrl = 'http://www.ebay.com/itm/' . $et->id;
					$currentProudctUrl = in_array ( $order_source_site_id, $siteList ) ? $siteUrl [$order_source_site_id] . $et->id : 'http://www.ebay.com/itm/' . $et->id;
				} catch ( \Exception $ex ) {
					echo "\n" . (__function__) . ' set ebay Product Url Error Message:' . $ex->getMessage () . " Line no " . $ex->getLine () . "\n";
				}
				
				$AMMOI = array (
					//'order_id' => $orderID,
					'order_source_order_item_id' => $et->id,
					'source_item_id' => $et->itemid,
					'sku' => $et->sku,
					'platform_sku' => $et->sku,
					'product_name' => $et->title,
					'photo_primary' => $photo,
					'price' => empty($et->transactionprice)?0:$et->transactionprice,
					'shipping_price' => empty($et->shippingservicecost)?0:$et->shippingservicecost,
					'title' => $et->title,
					'order_source_srn' => $et->salesrecordnum,
					'quantity' => $et->quantitypurchased,
					'ordered_quantity' => $et->quantitypurchased,
					'order_source_order_id' => $et->orderid,
					'order_source_transactionid' => $et->transactionid,
					'order_source_itemid' => $et->itemid,
					'is_bundle' => 0,
					'product_url' => $currentProudctUrl,
					'platform_status' => @$order_status, // ebay 新订单平台状态保存
					'create_time' => time (),
					'product_attributes' => @$ebay_product_attributes,
					'delivery_status'=>'allow',
					'update_time' => time ()
				);
				
				// 20170228 ebay 空sku 处理方案 根据 order_source_itemid 与商品属性生成 sku
				if (empty ( $AMMOI ['sku'] )) {
					try {
						if (isset ( $AMMOI ['product_attributes'] )) {
							$itemAttrList = [ ];
							$suffix = '';
							$currentAttr = json_decode ( $AMMOI ['product_attributes'], true );
							// 以attribute label 的升序排列
							if (! empty ( $currentAttr )) {
								ksort ( $currentAttr );
								foreach ( $currentAttr as $tmp ) {
									if (is_string ( $tmp )) {
										$suffix .= @$tmp;
									} elseif (is_array($tmp)){
										foreach($tmp as $_subTmp){
											$suffix .= @$_subTmp;
										}
									}
								}
							}
				
						}else{
							$suffix = '';
						}
						$AMMOI['sku'] = $AMMOI['order_source_itemid'].$suffix;
						$AMMOI['is_sys_create_sku'] = 'Y';
				
					}catch (\Exception $e) {
						\yii::error((__FUNCTION__).' error message : '.$e->getMessage()." Line no:".$e->getLine().' sku empty ', 'file');
					}
				}//end of empty sku 
				$ebayItemList [] = $AMMOI;
				//print_r($AMMOI);
				//echo "\n *************************** \n";
			}//end of each ebay transation
			$AMMO['items'] = $ebayItemList;
			//print_r($AMMO['items']);
		}
		//exit();//testkh
		
		//testkh20160810 追查ebay 订单金额为0
		try {
			if (empty($AMMO['grand_total'])){
				echo "\n".(__function__)." uid=".@$uid." order_id=".$MEO['ebay_orderid']." ".$AMMO['selleruserid']." before importPlatformOrder  grand_total =0";
			}
			
			if (empty($AMMO['subtotal'])){
				echo "\n".(__function__)." uid=".@$uid." order_id=".$MEO['ebay_orderid']." ".$AMMO['selleruserid']." before importPlatformOrder  subtotal =0";
			}
		} catch (\Exception $ex) {
			echo "\n ".(__function__)." ebay total is zero Error Message :  ". $ex->getMessage()." Line no:".$ex->getLine()."\n";
		}
		
		try {
			
			OrderHelper::importPlatformOrder([$uid=>$AMMO]);
			
			if ($isNew){
				$updateSql = "update  od_ebay_transaction  a , od_order_v2 b  set a.order_id = b.order_id where a.orderid  = b.order_source_order_id and a.order_id is null and b.order_id is not null";
				$updateRT = \Yii::$app->subdb->createCommand($updateSql)->execute();
				echo "\n no order id fix effect=".$updateRT;
				
				//20180213检查新订单地址是否正常
				list($success, $rt) = EbayOrderHelper::checkOrderAddress($AMMO['selleruserid'], $MEO['ebay_orderid']);
				$rtStr = '';
				if (is_array($rt)){
					$rtStr = json_encode($rt);
				}
				
				if (is_string($rt)){
					$rtStr = $rt;
				}
				
				echo "\n".(__function__)." uid=".@$uid." order_id=".$MEO['ebay_orderid']." ".$AMMO['selleruserid']." checkOrderAddress order result=".$rtStr; 
				
			}
			
		} catch (\Exception $ex) {
			echo "\n ".(__function__)." Error Message :  ". $ex->getMessage()." File:".$ex->getFile()." Line no:".$ex->getLine()."\n";
		}
		
		$MMO = OdOrder::find()->where("`order_source` = :os AND `order_source_order_id` = :osoi",[':os'=>$AMMO['order_source'],':osoi'=>$AMMO['order_source_order_id']])->one();;
		return $MMO;
	}

	/***
	 * 组织 shippingaddress 数级
	 * 为向 order 中的 shippingaddress 赋值
	 * 
	 */
	static function setShippingAddress($shipping_address=array()){
		$n_shipping_address=array();
		if(!empty($shipping_address)){
			$n_shipping_address=array(
				//收件地
				'ship_name'=>$shipping_address['Name'],
				'ship_cityname'=>@$shipping_address['CityName'],
				'ship_stateorprovince'=>@$shipping_address['StateOrProvince'],
				'ship_country'=>@$shipping_address['Country'],
				'ship_countryname'=>@$shipping_address['CountryName'],
				'ship_street1'=>@$shipping_address['Street1'],
				'ship_postalcode'=>@$shipping_address['PostalCode'],
				'ship_phone'=>@$shipping_address['Phone'],
				'addressid'=>@$shipping_address['AddressID'],
				'addressowner'=>@$shipping_address['AddressOwner'],
			);
			//去掉  Invalid Request 
			foreach($n_shipping_address as $k=>$v){
				if($v=='Invalid Request'){
					$n_shipping_address[$k]=null;
				}
			}
			Helper_Array::removeEmpty($n_shipping_address);
			$n_shipping_address['ship_street2']=@$shipping_address['Street2'];
		}
		return $n_shipping_address;
	}





	/**
	 * @author lxqun 
	 * 按 队列
	 */
	static function cronRequestOrderQueue($multiprocessing=null){
		$betimeline=time();
		//$sql="select from queue_getorder 
		$sql_where="where created <= $betimeline" ;
		if(is_array($multiprocessing)&&$multiprocessing[0]>0&&isset($multiprocessing[1])){
			$multiprocessingTotal=(int)$multiprocessing[0];
			$multiprocessingNo=(int)$multiprocessing[1];
			$sql_where.=" And (selleruserid%$multiprocessingTotal)=$multiprocessingNo";
			if(isset($multiprocessing[2]) && isset($multiprocessing[3])){
				$multiprocessingTotal2=(int)$multiprocessing[2];
				$multiprocessingNo2=(int)$multiprocessing[3];
				$sql_where.=" And  (qid%$multiprocessingTotal2)=$multiprocessingNo2";
			}
		}

		$h=Yii::app()->db->createCommand("SELECT count(*) as c,selleruserid from ".QueueGetorder::model()->tableName()." ".$sql_where." group by selleruserid order by c DESC")->query();
		$queue_selleruserid=array();
		while($r=$h->fetchRow()){
			$queue_selleruserid[]=$r['selleruserid'];
		}
		foreach($queue_selleruserid as $selleruserid){
			echo "\n[".date('H:i:s').'-'.__LINE__.'- selleruserid:'.$selleruserid."]\n";

			$ebayorderids=array();
			$h2=Yii::app()->db->createCommand("SELECT ebay_orderid,selleruserid from ".QueueGetorder::model()->tableName()." ".$sql_where." And selleruserid='$selleruserid' ")->query();
			while($r=$h2->fetchRow()){
				if($r['selleruserid']!=$selleruserid) continue ;
				$ebayorderids[]=$r['ebay_orderid'];
			}


			$eu=SaasEbayUser::model()->where('selleruserid =?',$selleruserid)->getOne();
			ibayPlatform::useDB($eu->uid,true);
			$getOrders=new EbayInterface_getorders();
			$getOrders->eBayAuthToken=$eu->token;
			$getOrders->EntriesPerPage=30;
			$getOrders->PageNumber=1;

			for($i=0,$c=count($ebayorderids);$i<$c;$i+= $getOrders->EntriesPerPage){
				$do_ebayorderids=array_slice($ebayorderids,$i,$getOrders->EntriesPerPage);
				try{
					$getOrders->_before_request_xmlarray['DetailLevel']='ReturnAll';
					$getOrders->_before_request_xmlarray['OrderID']=$do_ebayorderids;
					//print_r($ebayorderids);
					$getOrders->requestOrders($eu,$do_ebayorderids);
					QueueGetorder::model()->deleteWhere('ebay_orderid in (?)',array($do_ebayorderids));
				}catch(Exception $ex){
					Helper_ExceptionLog::Add($ex,__CLASS__,__FUNCTION__,__LINE__);
					echo "Error Message :  ". $ex->getMessage();

				}
			}
			Yii::app()->db->createCommand("OPTIMIZE TABLE ".QueueGetorder::model()->tableName()." ;")->query();

		}

	}

	/**
	 * @author tangwc-2012-02-03
	 * 按 队列
	 */
	static function cronRequestShipOrderQueue($multiprocessing=null){
		$betimeline=time()-120;
		//$sql="select from queue_getorder 
		$sql_where="where created <= $betimeline" ;
		$select=Queue_Getshiporder::find('created <= ?',$betimeline);
		if(is_array($multiprocessing)&&$multiprocessing[0]>0&&isset($multiprocessing[1])){
			$multiprocessingTotal=(int)$multiprocessing[0];
			$multiprocessingNo=(int)$multiprocessing[1];
			$sql_where.=" And (selleruserid%$multiprocessingTotal)=$multiprocessingNo";
		}
		$h=Yii::app()->db->createCommand("SELECT count(*) as c,selleruserid from ".Queue_Getshiporder::model()->tableName()." ".$sql_where." group by selleruserid order by c DESC")->query();
		$queue_selleruserid=array();
		while($r=$h->fetchRow()){
			$queue_selleruserid[]=$r['selleruserid'];
		}
		foreach($queue_selleruserid as $selleruserid){
			echo "\n[".date('H:i:s').'-'.__LINE__.'- selleruserid:'.$selleruserid."]\n";
			$ebayorderids=Helper_Array::getCols(Queue_Getshiporder::find(' selleruserid=? ',$selleruserid) // 去掉这里的时间 点 限制,加快队列 
				->setColumns('ebay_orderid')
				->asArray()
				->getAll(),'ebay_orderid');
			$eu=SaasEbayUser::model()->where('selleruserid =?',$selleruserid)->getOne();
			ibayPlatform::useDB($eu->uid,true);
			$getOrders=new EbayInterface_getorders();
			$getOrders->eBayAuthToken=$eu->token;
			$getOrders->EntriesPerPage=30;
			$getOrders->PageNumber=1;
			$c=count($ebayorderids);
			for($i=0,$c;$i<$c;$i+= $getOrders->EntriesPerPage){
				$do_ebayorderids=array_slice($ebayorderids,$i,$getOrders->EntriesPerPage);
				try{
					$getOrders->_before_request_xmlarray['OrderID']=$do_ebayorderids;
					$getOrders->_before_request_xmlarray['OutputSelector']='OrderArray.Order.ShippedTime,OrderArray.Order.OrderID';
					$getOrders->requestShipOrders($eu);
					Queue_Getshiporder::meta()->deleteWhere('ebay_orderid in (?)',$do_ebayorderids);
				}catch(Exception $ex){
					Helper_ExceptionLog::Add($ex,__CLASS__,__FUNCTION__,__LINE__);
					echo "Error Message :  ". $ex->getMessage();

				}
			}
			Yii::app()->db->createCommand("OPTIMIZE TABLE ".Queue_Getshiporder::model()->tableName()." ;")->query();
		}

	}
	/**
	 * 指定日期内的同步
	 */
	static function cronRequestOrderLimitDate($eu,$NumberOfDays=1){
		ibayPlatform::useSellerUserID($eu->selleruserid);
		$getOrders=new EbayInterface_getorders();
		$getOrders->eBayAuthToken=$eu->token;
		$getOrders->EntriesPerPage=30;
		//$getOrders->PageNumber=1;
		$getOrders->NumberOfDays=$NumberOfDays;
		try{
			//print_r($ebayorderids);
			$getOrders->requestOrders($eu);
		}catch(Exception $ex){
			Helper_ExceptionLog::Add($ex,__CLASS__,__FUNCTION__,__LINE__);
			echo "Error Message :  ". $ex->getMessage();

		}

	}

	/**
	 * 检验是否是退款
	 */
	static function check_ebayorder_backmoney(&$MEO){
		if(is_array($MEO['externaltransaction']) && count($MEO['externaltransaction']) && !isset($MEO['externaltransaction']['ExternalTransactionID'])){
			if(count($MEO['externaltransaction'])){
				$et_amout_positive=0;
				$et_amout_minus=0;
				foreach($MEO['externaltransaction'] as $et){
					if($et['PaymentOrRefundAmount']>0)
						$et_amout_positive+=$et['PaymentOrRefundAmount'];
					if($et['PaymentOrRefundAmount']<=0)
						$et_amout_minus+=$et['PaymentOrRefundAmount'];
				}
				if($et_amout_minus<0 && $et_amout_positive + $et_amout_minus ==0){//退款
					return true;
				}
			}
		}
		return false;
	}

	/**
	 * 保存externaltransaction 
	 */
	static function save_externaltransaction(&$MEO,$selleruserid,$ts){
		$externaltransaction = null;
		if(is_array($MEO['externaltransaction']) && count($MEO['externaltransaction'])){
			if(isset($MEO['externaltransaction']['ExternalTransactionID'])){
				$externaltransaction=array($MEO['externaltransaction']);
			}else{
				$externaltransaction=$MEO['externaltransaction'];
			}
		}
		//
		$using_externaltransactionid=null;
		if($externaltransaction){
			$nC=count($externaltransaction);
			$METs=OdEbayExternaltransaction::find()->where('ebay_orderid=:eo',array(':eo'=>$MEO->ebay_orderid))->count();
			if($METs!=$nC){
				foreach($externaltransaction as $ET){
					$MET=OdEbayExternaltransaction::find()->where('externaltransactionid=:e And paymentorrefundamount=:p',array(':e'=>$ET['ExternalTransactionID'],':p'=>$ET['PaymentOrRefundAmount'] ))->one();
					if(is_null($MET)){
						$MET=new OdEbayExternaltransaction(array(
							'selleruserid'=>$selleruserid,
							'ebay_orderid'=>$MEO['ebay_orderid'],
							'externaltransactionid'=>$ET['ExternalTransactionID'],
							'paymentorrefundamount'=>$ET['PaymentOrRefundAmount'],
							'feeorcreditamount'=>$ET['FeeOrCreditAmount'],
							'externaltransactiontime'=>$ET['ExternalTransactionTime'],
						));
						// $MET->save(); -- bug11
					}
					if($ET['PaymentOrRefundAmount']>0){
						$using_externaltransactionid=$ET['ExternalTransactionID'];
					}
				}
			}
		}
		//查询 ,是否存在 . 
		if($using_externaltransactionid){
			$itemids=array();
			foreach($ts as $t){
				$itemids[]=$t['Item']['ItemID'];
			}
			
			QueuePaypaltransactionHelper::Add($MEO->eorderid,$MEO['ebay_orderid'],$selleruserid,$using_externaltransactionid,$itemids);
		}

		return 1;
	}

	/**
	 * 保存 transaction  shipmenttrackingnumber 
	 */
	static function save_shipmenttrackingnumber($ts){
		$ShipmentTrackingNumber_array=array();
		// 1, transaction 中 有 shipmenttrackingnumber
		if(empty($ts)) return false;
		$transaction_num = count($ts);
		$i = 0;
		foreach($ts as $t){
			if(!isset($t['ShippingDetails']['ShipmentTrackingDetails'])) continue ;
			$ShipmentTrackingDetails=$t['ShippingDetails']['ShipmentTrackingDetails'];
			if(isset($ShipmentTrackingDetails['ShipmentTrackingNumber'])){
				$ShipmentTrackingDetails=array($ShipmentTrackingDetails);
			}
			foreach($ShipmentTrackingDetails as $STD){
				$ShipmentTrackingNumber_array[]=array(
					'itemid'=>$t['Item']['ItemID'],
					'transactionid'=>$t['TransactionID'],
					'ShipmentTrackingDetails'=>$STD,
				);
			}
			$i++;
		}
		// 2 , Ebay_Myorders_Shipping 中 可以查找更新
		$arr=array();
		foreach($ShipmentTrackingNumber_array as $di){
			$itemid=$di['itemid'];
			$transactionid=$di['transactionid'];
			$ShipmentTrackingNumber=$di['ShipmentTrackingDetails']['ShipmentTrackingNumber'];
			$ShippingCarrierUsed=$di['ShipmentTrackingDetails']['ShippingCarrierUsed'];
			$MT=OdEbayTransaction::find()->where('transactionid=:t And itemid=:i ',array(':t'=>$transactionid,':i'=>$itemid))->one();
			$order_id = $MT->order_id;
			if(is_null($MT)){
				continue 1;
			}
			//跳过,已经重发的订单 
			//$MMO=OdOrder::model()->where('order_id=?',$MT->order_id)->getOne();

			//跳过 没有跟踪号的。
			if(empty($ShipmentTrackingNumber)) continue;
			if(empty($MT->order_id)) continue;
			$arr[]=array(
// 				'order_id'=>$MT->order_id,
				'id'=>$MT->id,
				'shipped_tracking_number'=>$ShipmentTrackingNumber,
				'shipped_carrier_used'=>$ShippingCarrierUsed
			);
		}
		//如果订单是部分发货
		if ($i > 0 && ($i != $transaction_num) && $order_id) {
			$Order = OdOrder::find($order_id);
			if ($Order->order_status == 500 || $Order->order_status == 200) {
				$update_res = OdOrder::updateAll(array('order_status' => 200,'shipping_status' => 1), 'order_id='.$order_id);
//				SysLogHelper::SysLog_Create("order",__CLASS__, __FUNCTION__,"Error 5.1","Change the order status for part of the delivery failure,odOrderParams:".print_r($Order,true)."error:".$update_res, "Error");
			}
		}
		//将已经存在的 跟踪号,物流方式记录到 包裹表中
// 		if(!empty($arr)){
// 			DeliveryHelper::initDelivery($order_id, $arr);
// 		}
		//同步发货数量
// 		if ($order_id) {
// 			OrderHelper::syncUnsentAndUnpackedItem($order_id);
// 		}
		return 1;
	}
	
	/**
	 * 把ebay的订单信息同步到eagle1系统中user_库的od_order和od_order_item。
	 * 这里主要是通过eagle1提供的 http api的方式
	 * @param unknown $orderHeaderInfo
	 * @param unknown $orderItems
	 * @param unknown $uid
	 * @param unknown $merchantId
	 * @param unknown $marketplaceId
	 * @return multitype:number NULL |Ambigous <\eagle\modules\order\helpers\订单id, multitype:number string >
	 */
	private static function _saveEbayOrderToOldEagle1($orderarr,$uid){
		
		// 设置item的信息
		$MTs=OdEbayTransaction::find()->where(['platform'=>'eBay','orderid'=>$orderarr['order_source_order_id']])->all();
		$itemsInfo=[];
		if (count($MTs)){
			foreach($MTs as $MT){
				// 生成 订单商品
				$AMMOI=array(
					'order_source_order_item_id'=>$MT->id,
					'source_item_id'=>$MT->itemid,
					'sku' => $MT->sku,
					'platform_sku' => $MT->sku,
					'product_name'=> $MT->title,
					'photo_primary'=> '',
					'price'=>$MT->transactionprice,
					'shipping_price'=>$MT->shippingservicecost,
					'title'=>$MT->title,
					'order_source_srn'=>$MT->salesrecordnum,
					'quantity'=>$MT->quantitypurchased,
					'ordered_quantity'=>$MT->quantitypurchased,
					'order_source_order_id'=>$MT->orderid,
					'order_source_transactionid'=>$MT->transactionid,
					'order_source_itemid'=>$MT->itemid,
					'is_bundle'=> 0,
					'create_time'=> time(),
					'update_time'=>time()
				);
				$itemsInfo[]=$AMMOI;
			}
		}
		$oneOrderReq["items"]=$itemsInfo;
		$oneOrderReq+=$orderarr;
		
		//总的请求信息
		$reqInfo=array();
		$ordersReq=array();
		$ordersReq[]=$oneOrderReq;
		
		$reqInfo[$uid]=$ordersReq;
		$reqInfoJson=json_encode($reqInfo,true);
		
		echo "before OrderHelper::importPlatformOrder info:".json_encode($reqInfo,true)."\n";
		$postParams=array("orderinfo"=>$reqInfoJson);
		$result=EagleOneHttpApiHelper::sendOrderInfoToEagle($postParams);
		echo "result:".print_r($result,true);
		\Yii::info(print_r($result,true),'file');
		return $result;
	}
	
	static public function saveOrderCurrencyInfo($ebayOrderId, $selleruserid , $currencyInfo){
		$isForce = false ;
		$fullName = 'System';
		$action='同步订单' ;
		$module ='order';
		
		$order = OdOrder::find()->where(['order_source_order_id'=>$ebayOrderId , 'selleruserid'=>$selleruserid])->one();
		if (!empty($order->addi_info)){
			$addInfo = json_decode($order->addi_info,true);
		}else{
			$addInfo = [];
		}
		
		foreach($currencyInfo as $key=>$value){
			$addInfo[$key] = $value;
		}
		
		if (!empty($addInfo)){
			$newAttr = ['addi_info'=>json_encode($addInfo)];
			OrderUpdateHelper::updateOrder($order, $newAttr,$isForce,$fullName,$action,$module);
		}
		
	}//end of function saveOrderCurrencyInfo
}
