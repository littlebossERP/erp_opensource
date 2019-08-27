<?php
/**
 * 获取指定订单的详细信息和商品信息
 * @package interface.ebay.tradingapi
 */
class EbayInterface_getordertransactions extends EbayInterface_base{
    //从ebay获取相应itemid的信息
    public function api($order_id){
        $this->verb = 'GetOrderTransactions';
        $xmlArr=array(
			'DetailLevel'=>'ReturnAll',
			'ErrorLanguage'=>'zh_CN',
			'IncludeFinalValueFees'=>'True',
// 				'ItemTransactionIDArray'=>array(
//                     'ItemTransactionID'=>array(
//                         'ItemID'=>'110041726429',
//                         //'SKU'=>'',
//                         'TransactionID'=>'24444281001'
//                     )
//                 ),
			'OrderIDArray'=>array(
				'OrderID'=>$order_id
			),
			'Platform'=>'eBay'
		);
		if(isset($this->_before_request_xmlarray['OutputSelector'])){
            unset($xmlArr['DetailLevel']);
            $xmlArr['OutputSelector']=$this->_before_request_xmlarray['OutputSelector'];
        }
		
		$requestArr=$this->setRequestBody($xmlArr)->sendRequest(0,600);
		return $requestArr;
    }
    /**
     * 保存 到 订单 表中
     */         
    public function saveTransaction($requestArr,$transaction){
//        $requestArr=$this->api($order_id);
        if(!isset($requestArr['OrderArray']['Order'])) return false;
        $o=$requestArr['OrderArray']['Order'];
        //$t=$requestArr['OrderArray']['Order']['TransactionArray'];
        $tids=array();
        if(isset($requestArr['OrderArray']['Order']['TransactionArray'])&&is_array($requestArr['OrderArray']['Order']['TransactionArray']['Transaction'])){

        	$transactionArray=$requestArr['OrderArray']['Order']['TransactionArray']['Transaction'];
        	if (isset($transactionArray['Item'])){
				$transactionArray=array($transactionArray);
        	}
            foreach($requestArr['OrderArray']['Order']['TransactionArray']['Transaction'] as $t){
                // AmountPaid
                // Item ItemId
                //TransactionID
                //Status 
                //更新 transaction 状态
                $et=OdEbayTransaction::model()->where('transactionid=? And itemid=?',array($t['TransactionID'],$t['Item']['ItemID']))->getOne();
                if($et->id==$transaction->id){
                    continue 1;
                }
                $oldmyorderid=$et->myorderid;
    			$et_v=array(
    				'createddate'=>strtotime($t['CreatedDate']),
    				'quantitypurchased'=>$t['QuantityPurchased'],
    				'platform'=>$t['Platform'],
    				'itemid'=>$t['Item']['ItemID'],
    				'uid'=>$transaction->uid,
    				'selleruserid'=>$transaction->selleruserid,
    				'buyer'=>$t['Buyer'],
    				'title'=>$t['Item']['Title'],
    				'shippingservicecost'=>$requestArr['OrderArray']['Order']['ShippingServiceSelected']['ShippingServiceCost'],
    				'finalvaluefee'=>$t['FinalValueFee'],
    				'paypalemailaddress'=>$t['PayPalEmailAddress'],
    				'shippingserviceselected'=>$requestArr['OrderArray']['Order']['ShippingServiceSelected'],
    				'status'=>$t['Status'],
    				'amountpaid'=>$t['AmountPaid'],
    				'transactionprice'=>$t['TransactionPrice'],
    				'transactionsiteid'=>$t['TransactionSiteID'],
    				'orderid'=>$o['OrderID'],
    				'myorderid'=>$transaction->myorderid,	#此值100%有
    				'salesrecordnum'=>$requestArr['OrderArray']['Order']['ShippingDetails']['SellingManagerSalesRecordNumber'],
    				'finalvaluefee_currency'=>$transaction->currency,
    				'currency'=>$transaction->currency
    			);
           		if (isset($requestArr['OrderArray']['Order']['PaidTime'])){
				    $et_v['paidtime'] =strtotime($requestArr['OrderArray']['Order']['PaidTime']);
				}
    			// 外部订单
            	if (isset($t['ExternalTransaction'])){
					if (!isset($t['ExternalTransaction']['ExternalTransactionID'])){
						reset($t['ExternalTransaction']);
					}
					$et_v=array_merge($et_v,array(
						'externaltransaction_id'=>$t['ExternalTransaction']['ExternalTransactionID'] ,
						'externaltransaction_time'=>strtotime($t['ExternalTransaction']['ExternalTransactionTime']) ,
						'externaltransaction_fee'=>$t['ExternalTransaction']['FeeOrCreditAmount'] ,
					));
				}
    			if($et->isNewRecord){
    			    $et_v=array_merge($et_v,
                      array(
                          'transactionid'=>$t['TransactionID'],
                          'itemid'=>$t['Item']['ItemID'],
                          
                      ));
                }
    			// 支付状态 
                if($t['Status']['CheckoutStatus']=='CheckoutComplete'){
                    if($t['Status']['eBayPaymentStatus']=='NoPaymentFailure' && !empty($o['PaidTime'])){
                         $et->status_payment=OdEbayTransaction::STATUS_PAYMENT_COMPLETE;
                    }else{
                         $et->status_payment=OdEbayTransaction::STATUS_PAYMENT_PROCESSING;
                    }
                }else{
                     $et->status_payment=OdEbayTransaction::STATUS_PAYMENT_WAITING;
                }
    			Helper_Array::removeEmpty($et_v);
                $et->setAttributes($et_v);
    			$et->save();
            	#关闭以前的订单
//            	if ($oldmyorderid){
//            		Ebay_Myorders::meta()->destroyWhere('myorderid=?',$oldmyorderid);
//            	}
                
    			$tids[]=$et->id;
    			//更新 item 状态 , 必须向 Ebay 请求 getItem
    			if($transaction->itemid<>$t['Item']['ItemID']){
                    $ei=new EbayInterface_getitem;
                    $ei->eBayAuthToken=$this->eBayAuthToken;
//                     $ei->siteID=;
                    $item=$ei->api($t['Item']['ItemID']);
                    if($item){
                        $ei->save($item);
                        //判断是否需要进行自动补库存操作
    		            EbayInterface_reviseitem::checkDo($item['ItemID'],$item['Quantity'],$item['SellingStatus']['QuantitySold']);
		            }
                }
            }
        }
        return $tids;
    }
	/**
	 * 取得 transaction的 paypal 
	 */
	static function requestForPaypal($eu,$orderid){
		$api=new EbayInterface_getordertransactions();
		$api->eBayAuthToken=$eu->token;
		$api->_before_request_xmlarray['OutputSelector']="OrderArray.Order.TransactionArray.Transaction.PayPalEmailAddress,OrderArray.Order.OrderID,OrderArray.Order.TransactionArray.Transaction.Item.ItemID,OrderArray.Order.TransactionArray.Transaction.TransactionID";
		$r=$api->api($orderid);
		$requestArr=$api->_last_response_xmlarray; 
		if(isset($requestArr['OrderArray']['Order']['OrderID'])){
			$OrderArray['Order']=array($requestArr['OrderArray']['Order']);
		}elseif(Helper_xml::isArray($requestArr['OrderArray']['Order'])&&count($requestArr['OrderArray']['Order'])){ 
			$OrderArray['Order']=$requestArr['OrderArray']['Order'];
		}
		if(count($OrderArray['Order'])){
			foreach ($OrderArray['Order'] as $o){
				
				
				$ts=$o['TransactionArray']['Transaction'];
				if(isset($o['TransactionArray']['Transaction']['TransactionID'])){
					$ts=array($o['TransactionArray']['Transaction']);
				}else{
					$ts=$o['TransactionArray']['Transaction'];
				}
				foreach($ts as $t){
					$ItemID=$t['Item']['ItemID'];
					$TransactionID=$t['TransactionID'];
					if($t['PayPalEmailAddress']){
						$MT=OdEbayTransaction::model()->where('itemid=? And transactionid=?',array($ItemID,$TransactionID))->getOne();
						$MT->paypalemailaddress =$t['PayPalEmailAddress'];
						$MT->save();
					}
				}
				
				$logstr='selleruserid: '.$eu->selleruserid.' Ebay OrderID '.$o['OrderID']; 
					
					Yii::log($logstr);
			}
		}
		return 1;
	}
}