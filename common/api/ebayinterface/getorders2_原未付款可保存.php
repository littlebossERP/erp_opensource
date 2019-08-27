<?php
/**
 * 获取订单列表
 * @package interface.ebay.tradingapi
 */
class EbayInterface_getorders extends EbayInterface_base{
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
	                    $this->saveOneOrder($o,$o['OrderID'],$ebay_user,$ebay_user->selleruserid,$ebay_user->uid); 
	                    $logstr='selleruserid: '.$ebay_user->selleruserid.' Ebay OrderID '.$o['OrderID']; 
	                        print_r($logstr); 
	                        Yii::log($logstr);
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
                    $this->changeOneOrder($o,$o['OrderID'],$ebay_user->selleruserid,$ebay_user->uid); 
                    $logstr='selleruserid: '.$ebay_user->selleruserid.' Ebay OrderID '.$o['OrderID']; 
                        print_r($logstr); 
                        Yii::log($logstr);
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
    function requestOneOrder($orderid,$selleruserid,$uid){
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
                    return $this->requestOneOrder($o['OrderID'],$selleruserid,$uid);
                }
                $this->saveOneOrder($o,$orderid,$ebay_user,$selleruserid,$uid);
            }else{
                $os=$requestArr['OrderArray']['Order'];
                foreach($os as $o){
                    $this->saveOneOrder($o,$o['OrderID'],$ebay_user,$selleruserid,$uid);
                }
            }
        }
    }
    
    /**
     *  @author lxqun 2011-06-14
     *  保存订单数组
     *  
     */
    function saveOneOrder($oA,$ebay_orderid,$ebay_user,$selleruserid,$uid){ 
        // 1 , save ebay order
        $MEO=OdEbayOrder::model()->where('ebay_orderid=?',$ebay_orderid)->getOne();
        $AMEO=array(); 
        if(is_null($MEO)){
            $AMEO=array(
                'selleruserid'=>$selleruserid,
            );
            $MEO=new OdEbayOrder;
        }
        
        $AMEO['currency']=$oA->getChildrenAttrible('Total','currencyID');
        $o=$oA->toArray();
        
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
            'salestaxpercent'=>$o['ShippingDetails']['SalesTax']['SalesTaxPercent'],
            'salestaxamount'=>$o['ShippingDetails']['SalesTax']['SalesTaxAmount'],
            'shippingservicecost'=>$o['ShippingServiceSelected']['ShippingServiceCost'],
            'subtotal'=>$o['Subtotal'],
            'total'=>$o['Total'],
                        
            'buyeruserid'=>$o['BuyerUserID'],
            'shippingservice'=>$o['ShippingServiceSelected']['ShippingService'],
            'shippingservicecost'=>$o['ShippingServiceSelected']['ShippingServiceCost'],
            'shippingincludedintax'=>$o['ShippingDetails']['SalesTax']['ShippingIncludedInTax'],

            'externaltransactionid'=>@$o['ExternalTransaction']['ExternalTransactionID'],
            'feeorcreditamount'=>@$o['ExternalTransaction']['FeeOrCreditAmount'],
            'paymentorrefundamount'=>@$o['ExternalTransaction']['PaymentOrRefundAmount'],
            
            'shippingaddress'=>$o['ShippingAddress'],
            'externaltransaction'=> @$o['ExternalTransaction'],
            
            'buyercheckoutmessage'=>@$o['BuyerCheckoutMessage'],
            'shippingserviceselected'=>$o['ShippingServiceSelected'],
            
            'salesrecordnum'=>@$o['ShippingDetails']['SellingManagerSalesRecordNumber'],
            'responsedat'=>time(),
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
        
            // 更新前的 一些判断 操作
            // 如果之前的状态是已付款的 ,就不再合并了
            //if(!($MEO['checkoutstatus']=='Complete' && $MEO['ebaypaymentstatus']=='NoPaymentFailure')
            //  || !($AMEO['checkoutstatus']=='Complete' && $AMEO['ebaypaymentstatus']=='NoPaymentFailure') ){
            //  $ifMerge=true;
            //}else{
            //  $ifMerge=false;
            //}
            Yii::log( "\n\n===========================================================  \n -- ebay_orderid   " .
                      $ebay_orderid.' isNewRecord:'.var_export($MEO,1)." MEO ".$MEO['orderstatus'] ." AMEO ".$AMEO['orderstatus']." \n "
                      );
                      
                      
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
            }
            /**
             * 或总金额不对, 需要重新 再取一下 . 并且跳出不保存
             */
            if($AMEO['total']==0){ // 退款订单处理
                   Yii::log('refund  order: '.$ebay_orderid );
                  //合并退款的订单,并且不再打回原订单号了,需要向请求打回原订单号
                  if($AMEO['adjustmentamount'] >=0 ){ 
                      if(strpos($ebay_orderid,'-')){
                        $MT=OdEbayTransaction::model()->where('transactionid=? And itemid=? And selleruserid=?',array($t['TransactionID'],$t['Item']['ItemID'],$selleruserid))->getOne();
                        if(!$MT->isNewRecord && strpos($MT->orderid,'-')===false && $MT->orderid != $ebay_orderid){
                            $ebay_orderid=$MT->orderid;
                        }
                      }
                       QueueGetorderHelper::Add($ebay_orderid,$selleruserid,$ebay_user->selleruserid);
                        $logstr= "$ebay_orderid :  subtotal $AMEO[subtotal]  + shippingservicecost $AMEO[shippingservicecost] + adjustmentamount  $AMEO[adjustmentamount] != total $AMEO[total] \n "
                            . var_export($AMEO['subtotal'] + $AMEO['shippingservicecost'] + $AMEO['adjustmentamount'] ,1) ." : ". var_export($AMEO['total'],1) ."\n " ;
                        Yii::log($logstr );
                        echo $logstr;
                        return false;
                  }
                  // 做退款 判断异常
                  if(!self::check_ebayorder_backmoney($AMEO)){
                       QueueGetorderHelper::Add($ebay_orderid,$selleruserid,$ebay_user->selleruserid);
                        $logstr= "$ebay_orderid :  total $AMEO[total]  , externaltransaction:\n "
                            . var_export($AMEO['externaltransaction'] ,1) ." \n " ;
                        Yii::log($logstr );
                        echo $logstr;
                        return false;
                  }
                  
            }elseif((strval($AMEO['subtotal'] + $AMEO['shippingservicecost'] + $AMEO['adjustmentamount']) !=$AMEO['total'])){
                //正常付款时 判断 异常
                QueueGetorderHelper::Add($ebay_orderid,$selleruserid,$ebay_user->selleruserid);
                $logstr= "$ebay_orderid :  subtotal $AMEO[subtotal]  + shippingservicecost $AMEO[shippingservicecost] + adjustmentamount  $AMEO[adjustmentamount] != total $AMEO[total] \n "
                    . var_export($AMEO['subtotal'] + $AMEO['shippingservicecost'] + $AMEO['adjustmentamount'] ,1) ." : ". var_export($AMEO['total'],1) ."\n " ;
                Yii::log($logstr );
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
                		Yii::log($logstr);
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
                Yii::log($logstr);
                echo $logstr;
                return false;
            }
            // 最后修改时间不对的,也退出更新!
            if($MEO['lastmodifiedtime'] > 0  && $AMEO['lastmodifiedtime'] >0 && $AMEO['lastmodifiedtime']<$MEO['lastmodifiedtime']){
                $logstr= "$ebay_orderid :  old > new lastmodifiedtime : $MEO[lastmodifiedtime] >  $AMEO[lastmodifiedtime]  \n";
                Yii::log($logstr);
                echo $logstr;
                //return false;
            }
            
        Helper_Array::removeEmpty($AMEO);
        
        // order 中的 地址信息
        $o_shipping_address=self::setShippingAddress($o['ShippingAddress']);
        $AMEO+=$o_shipping_address;
        
        $MEO->setAttributes($AMEO);
        $MEO->save();
        
        if(!$ifMerge){
            //订单数是否正常
            $ETc=OdEbayTransaction::model()->where('eorderid=? And selleruserid=?',array($MEO->eorderid,$selleruserid))
                  ->getCount();
            if(count($ts)!=$ETc['row_count']){
                $ifMerge=3;
            }
        }
        //退款 ,这种情况下 订单应该是不能完成的 状况下
        if(self::check_ebayorder_backmoney($MEO)){
            $ifEbayOrderCompleted=0;
            $ifMerge&=2;
        }
        // 保存 Transaction
        $MTs=array();

            
        foreach($ts as $t){
            if(Helper_xml::isArray($t)){
                $MTs[]=$MT=$this->saveTransaction($t,$MEO,$selleruserid,$uid,$ifEbayOrderCompleted);
            }
        }
        self::ebayOrder2myorder($MEO,$MTs,$ifMerge,$ifEbayOrderCompleted);
        self::save_externaltransaction($MEO,$selleruserid,$ts);
        self::save_shipmenttrackingnumber($ts);
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
                $myorderids=array();
                foreach($MEO->transactions as $MT){
                    $MT->shipped=true;
                    $myorderids[$MT->myorderid]=$MT->myorderid;
                    $MT->save();
                }
                foreach($myorderids as $myorderid){
                    $MMO=OdOrder::model()->find('myorderid=?',$myorderid);
                    //$MMO->shipped_time=$AMEO['shippedtime'];
                    $MMO->recalculate();
                    $MMO->save();
                }
            }
        }
        
        //
        Helper_Array::removeEmpty($AMEO);   
        $MEO->setAttributes($AMEO);
        $MEO->save();
    }

    
    /**
     *  @author lxqun 2011-06-14f
     *  保存交易数组
     *  @return $MT Ebay_Transaction
     *  @param $ifEbayOrderCompleted  订单是否完成, 是否修改内容 , 1:不需要,0需要.
     */
    function saveTransaction($t,&$MEO,$selleruserid,$uid,$ifEbayOrderCompleted=0){
        $MT=OdEbayTransaction::model()->where('transactionid=? And itemid=? And selleruserid=?',array($t['TransactionID'],$t['Item']['ItemID'],$selleruserid))->getOne();
        $MI=OdEbayItem::model()->where('itemid=? And selleruserid=?',array($t['Item']['ItemID'],$selleruserid))->getOne();
        $title=@$t['Item']['Title'];
        
        // 并未保存完成数据的情况 
        if($ifEbayOrderCompleted){
            if(!($MT->eorderid==$MEO->eorderid && $MT->orderlineitemid==$t['OrderLineItemID'])){
                $ifEbayOrderCompleted=0;
            }
        }
        
        $AMT=array();
        $AMT_1=array(
            'transactionsiteid'=>$t['Item']['Site'],
            'orderlineitemid'=>$t['OrderLineItemID'],
            'transactionprice'=>$t['TransactionPrice'],
            'platform'=>$t['Platform'],
            'salesrecordnum'=>@$t['ShippingDetails']['SellingManagerSalesRecordNumber'],
            'finalvaluefee'=>$t['FinalValueFee'],
            
            'quantitypurchased'=>$t['QuantityPurchased'],
            
            'buyer'=>Helper_xml::isArray($t['Buyer'])?$t['Buyer']->toArray():null,
            'status'=>Helper_xml::isArray($t['Status'])?$t['Status']->toArray():null,
            
            'eorderid'=>$MEO->eorderid,
            'orderid'=>$MEO->ebay_orderid,
            'amountpaid'=>$MEO->amountpaid,
            'shippingserviceselected'=>$MEO->shippingserviceselected,
            
            //'paidtime'=>$MEO->paidtime,
        ); 
        //货币
        $AMT_1['currency']=$t->getChildrenAttrible('TransactionPrice','currencyID');
        $AMT_1['finalvaluefee_currency']=$t->getChildrenAttrible('FinalValueFee','currencyID');

        $AMT_2=array(
            'sku'=> @$t['Item']['SKU'] , // Helper_SKU::EbayItemDecode(Helper_conver::decode_uft8html(@$t['Item']['SKU'])),
            'title'=>$title,
        );
        // variation 下的 sku ,及 title 
        if(isset($t['Variation'])){
            $AMT_2['variation']= Helper_xml::isArray($t['Variation'])?$t['Variation']->toArray():null ;
        }
        if(isset($AMT_2['variation']['VariationTitle']) && strlen($AMT_2['variation']['VariationTitle'])>0){
            $AMT_2['title']=$AMT_2['variation']['VariationTitle'];
        }
        if(isset($AMT_2['variation']['SKU']) && strlen($AMT_2['variation']['SKU'])>0){
            $AMT_2['variation']['SKU']=Helper_SKU::EbayItemDecode($AMT_2['variation']['SKU']);
            $AMT_2['sku']=$AMT_2['variation']['SKU'];
        }
        Yii::log('FixedSKU:'. print_r($AMT_2['sku'],1));
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
        if(!$MT->isNewRecord){
            // 不再接收 付款之后再次 改为未付款 .
            if($AMT['status_payment']!=OdEbayTransaction::STATUS_PAYMENT_COMPLETE && $AMT['status_payment']!=OdEbayTransaction::STATUS_PAYMENT_BACKMONEY 
                &&$MT['status_payment']==OdEbayTransaction::STATUS_PAYMENT_COMPLETE){
                unset($AMT['status_payment']);
            }
        }
        // 基础信息
        if($MT->isNewRecord){
            $AMT+=array(
                // 用户
                'uid'=>$uid,
                'selleruserid'=>$selleruserid,
                'itemid'=>$t['Item']['ItemID'],
                'transactionid'=>$t['TransactionID'],
                
                // item 信息
                'additemfee'=>$MI->additemfee,
                'additemfee_currency'=>$MI->additemfee_currency,
                
            );
            $Added_quantitypurchased=$AMT['quantitypurchased'];
            
            //商品信息  
            $MT->goods_id=$MI->goods_id;
//            $MG=Ebay_MyGood::find('id=?',$MI->goods_id)->getOne();
//            if(!$MG->isNewRecord){
//                 $MT->coomao_goods_id=$MG->coomao_goods_id;
//                $MT->goodscategory_id=$MG->goodscategory_id;
//                $MT->property_id=$MI->log->property_id;
//            }
        }else{
            // 3.4  新添加了的 订单货品数量
            if($AMT['quantitypurchased']!= $MT->quantitypurchased){
                $Added_quantitypurchased=$AMT['QuantityPurchased']-$MT->quantitypurchased;
            }
        }
        Helper_Array::removeEmpty($AMT);
        $MT->setAttributes($AMT);
        
        // 补上空字段
        if(empty($MT['finalvaluefee_currency'])){
            $MT['finalvaluefee_currency']=$MT['currency'];
        }
        
        $MT->save();
        //-------------------------------------------------------------------------------
        // 生成后的操作
        // 外部订单  后续操作
//         if (isset($TA['ExternalTransaction'])&&isset($TA['ExternalTransaction']['ExternalTransactionID'])){
//             //通过paypal的交易号，找到transaction进行ipn中transaction数据的修正 
//             /*@var $ip Ipn_Paypal */
//             $ip=Ipn_Paypal::find('txn_id = ?',$TA['ExternalTransaction']['ExternalTransactionID'])->getOne();
//             if (!$ip->isNewRecord){
//                 $ip->transactionid=$MT->id;
//                 $ip->save();
//             }
//         }
        return $MT;
    }
    
    
    
    /**
     *  @author lxqun 2011-06-14
     *  生成 Ibay 订单数组
     *  @param $ifEbayOrderCompleted  订单是否完成, 是否修改内容 , 1:不需要,0需要.
     */
    static function ebayOrder2myorder(&$MEO,$MTs,$ifMerge=0,$ifEbayOrderCompleted=0){
        $oldmyorderids=array(); // 已经存在的 全部myorderid .
        $MTnomyorderids=array(); // 还没有myorderid 的T 
        $mtcount=0; 
        foreach($MTs as $MT){
            $oldMTids[$MT->id]=$MT->id;
            if($MT->myorderid>0){
                $oldmyorderids[$MT->myorderid]=$MT->myorderid;
            }else{
                $MTnomyorderids[$MT->id]=$MT;
            }
            $mtcount++;
        }
        $error="\n ===================================== \n ".
                  '-----------  '.print_r(date('Ymd H:i:s'),1)." ---  \n".
                  'ebay_orderid:'.$MEO->ebay_orderid." \n ".
                  '$oldmtcount: '.print_r($oldmtcount,1)." \n ".
                  '$mtcount: '.print_r($mtcount,1)." \n ".
                  "oldmyorderids: ".print_r($oldmyorderids,1)." \n ";
        
        Yii::log($error);
        $error=null;
        if($mtcount==0) return 0;
        if(empty($oldmyorderids)){// 新
            $MMO=self::ebayOrder2myorder_save($MEO,$MTs,null,false,$ifEbayOrderCompleted);
        }elseif(count($oldmyorderids)==1){ //都是同, T 数量是否相同 ?
            $myorderid=current($oldmyorderids);
            $MMO=OdOrder::model()->where('myorderid=?',$myorderid)->getOne();
            $oldmtcount=OdEbayTransaction::model()->where("myorderid=? And platform='eBay'",array($myorderid))->getCount();
            
            // EO 的 T 与 IO 中的 T 的数量不同 -- 比 IO 少 , 并且 IO 未付款. 执行拆分 , 拆分判断仅 eBay订单
            if($oldmtcount>$mtcount && self::ifMergeOrder($MMO)&& ($ifMerge&2)){
                // 新的 MT  , 旧的 myorder 是哪些 MT ,  为新的 MT 生成新的 .
                $nMMO=self::ebayOrder2myorder_save($MEO,$MTs,null,false,$ifEbayOrderCompleted);              
                // 原 myorder 重存一次
                $MMO->getCbyfCalculate();
                $MMO->recalculate();
                $MMO->save();
                // 记录拆分
                OdOrderLogHelper::Add(OdOrderLogHelper::TYPE_MERGE,$nMMO->myorderid,$nMMO->selleruserid,'拆分订单: ('.$MMO->myorderid.') 新订单: '. $nMMO->myorderid.' .'
                                      , $MMO->myorderid);
            }elseif($oldmtcount<$mtcount){
                if(self::ifMergeOrder($MMO,$error)){
                    Yii::log("LINE:".__LINE__."\n");
                    $MMO=self::ebayOrder2myorder_save($MEO,$MTs,$MMO,'merge',$ifEbayOrderCompleted);
                }else{
                    if(count($MTnomyorderids)){
                        Yii::log("LINE:".__LINE__."\n");
                        $MMO2=self::ebayOrder2myorder_save($MEO,$MTnomyorderids,null,false,$ifEbayOrderCompleted);
                        $MMO=self::ebayOrder2myorder_save($MEO,array_diff_key($MTs,$MTnomyorderids),$MMO,false,$ifEbayOrderCompleted);
                    }else{
                        Yii::log("LINE:".__LINE__."\n");
                        $MMO=self::ebayOrder2myorder_save($MEO,$MTs,$MMO,false,$ifEbayOrderCompleted);
                    }
                    $error="\n ------------- \n ".
                        '-----------  '.print_r(date('Ymd H:i:s'),1)." ---  \n".
                        'merge $error: '.print_r($error,1)." \n ".
                        "MTnomyorderids: ".print_r($MTnomyorderids,1)." \n ".
                        '$MMO myorderid:'.$MMO->myorderid." \n ";
                    
                    Yii::log($error);
                    $error=null;
                }
                
            }else{ // 简单更新
                if(count($MTnomyorderids)){
                    Yii::log("LINE:".__LINE__."\n");
                    $MMO2=self::ebayOrder2myorder_save($MEO,$MTnomyorderids,null,false,$ifEbayOrderCompleted);
                    $MMO=self::ebayOrder2myorder_save($MEO,array_diff_key($MTs,$MTnomyorderids),$MMO,false,$ifEbayOrderCompleted);
                }else{
                    Yii::log("LINE:".__LINE__."\n");
                    $MMO=self::ebayOrder2myorder_save($MEO,$MTs,$MMO,false,$ifEbayOrderCompleted);
                }
            }
            
        }else{ // 多个 myorder ,  未付款订单 合并 ,找出最老的  , 已经付款 , 更新 每一个   
            $oldmtcount=OdEbayTransaction::model()->where("myorderid in (?) And platform='eBay'",array($oldmyorderids))->getCount();
            $oldMTs=OdEbayTransaction::model()->where("myorderid in (?) And platform='eBay'",array($oldmyorderids))->order('created DESC')->getAll();
            
            // 判断 总数量 相同可以合并,多于就要判断 拆分, 少于的话 还是合并, 拆分判断仅 eBay订单
            $needsplitmyorderids=array();
            if($oldmtcount>$mtcount){
                foreach($oldMTs as $oMT){
                    if(!isset($oldMTids[$oMT->id])){
                        // 需要拆分的 myorder,不能合的
                        $needsplitmyorderids[$oMT->myorderid]=$oMT->myorderid;
                    }
                }
            }
            // 找出最老order,并且是没有 合并过其它订单的 , 并抛弃新 order
            $MMOs=OdOrder::model()->where('myorderid in (?)',array($oldmyorderids))->order('created DESC')->getAll();
            $MO=null;
            foreach($MMOs as $MMO){
                if(isset($needsplitmyorderids[$MMO->myorderid])) continue;
                if(empty($MO)||$MO->isNewRecord||
                   $MMO->created < $MO->created ){
                    $MO=$MMO;
                }
            }
            
            // 已经付款 
            if(self::ifMergeOrder($MMOs,$error)){ // && ($ifMerge&1)){  // 合并
                $old_myorderids=$oldmyorderids;
                if($MO) unset($old_myorderids[$MO->myorderid]);
                $MO=self::ebayOrder2myorder_save($MEO,$MTs,$MO,'merge',$ifEbayOrderCompleted);
                OdOrderLogHelper::Add(OdOrderLogHelper::TYPE_MERGE,$MO->myorderid,$MO->selleruserid,'合并订单: ('.implode(',',$old_myorderids).') 新订单: '. $MO->myorderid.' .'
                    , $old_myorderids);
                if(!empty($old_myorderids)){
                    foreach($old_myorderids as $old_myorderid){
                        if(isset($needsplitmyorderids[$old_myorderid])){
                            $MMO=OdOrder::model()->find('myorderid=?',$old_myorderid);
                            $MMO->getCbyfCalculate();
                            $MMO->recalculate();
                            $MMO->save();
                        }else{
                            Ebay_Myorders::mergeMoveChildren($old_myorderid,$MO->myorderid);
                            Ebay_Myorders::removeEmptyOrder($old_myorderid);
                        }
                    }
                }
            }else{ // 简单更新 每一个
                $error="\n ------------- \n ".
                        '-----------  '.print_r(date('Ymd H:i:s'),1)." ---  \n".
                        'merge $error: '.print_r($error,1)."   ifMerge: $ifMerge \n ".
                        "oldmyorderids: ".print_r($oldmyorderids,1)." \n ".
                        '$MMOs myorderid:'.print_r(Helper_Array::getCols($MMOs->toArray(),'myorderid'),1)." \n ";
                    
                    Yii::log($error);
                    $error=null;
                if(count($MTnomyorderids)){
                    $MMO2=self::ebayOrder2myorder_save($MEO,$MTnomyorderids,null,false,$ifEbayOrderCompleted);
                }
                foreach($MMOs as $MMO){
                    $MMO=self::ebayOrder2myorder_save($MEO,$MTs,$MMO,false,$ifEbayOrderCompleted);
                }
            }
        }
        // return $MMOs;
    }
    
    /**
     *  @author lxqun 2011-06-14
     *  生成 Ibay 订单数组
     *  @param $ifEbayOrderCompleted  订单是否完成, 是否修改内容 , 1:不需要,0需要.
     */
    static function ebayOrder2myorder_save(&$MEO,&$MTs,$MMO=null,$merge=false,$ifEbayOrderCompleted=false){
        $isNew=false;  // New Ebay order 
        $AMMO=array();
        if(empty($MMO)||empty($MMO->selleruserid)){
            foreach($MTs as $MT){
                if(strlen($MT->selleruserid)) break 1;
            }
            $AMMO=array(
                'uid'=>$MT->uid,
                'selleruserid'=>$MT->selleruserid
            );
            if(empty($MMO)){
                $MMO=new OdOrder($AMMO);
                $isNew=true;
            }
        }
        if($MMO->isNewRecord){
            $isNew=true;
        }
        $AMMO+=array(
            'ebay_orderid'=>$MEO['ebay_orderid'],
            'buyer_id'=>$MEO['buyeruserid'],
            'currency'=>$MEO['currency'],
            //支付 时间 状态
            'payment'=>$MEO['paymentmethod'],
            'integratedmerchantcreditcardenabled'=>$MEO['integratedmerchantcreditcardenabled'],
            'ebaypaymentstatus'=>$MEO['ebaypaymentstatus'],
            'status_checkoutstatus'=>$MEO['checkoutstatus'],
            'status_completestatus'=>$MEO['checkoutstatus'],
            // 运输 
            'shippingserviceselected'=>$MEO['shippingserviceselected'],
            'shippingservice'=>$MEO['shippingservice'],

            //时间 
            'createddate'=>$MEO['createdtime'],
            'paidtime'=>$MEO['paidtime'],
            //'shipped_time'=>$MEO['shippedtime'],
            'lastmodifiedtime'=>$MEO['lastmodifiedtime'],
        );
        // 新订单需要 存主要信息
        // 未完成订单 需要存主要信息 
        if($isNew || !$ifEbayOrderCompleted ){
        $AMMO+=array(
            'order_from'=>'ebay',
            // 总额
            'adjustmentamount'=>$MEO['adjustmentamount'],
            'amountpaid'=>$MEO['amountpaid'],
            'amountsaved'=>$MEO['amountsaved'],
            'total_amount'=>$MEO['subtotal'],
            'finel_amount'=>$MEO['total'],
            'discount'=>$MEO['adjustmentamount'], // 折扣
            //运费
            'shipping_cost'=>$MEO['shippingservicecost'],
            // 税
            'salestaxpercent'=>$MEO['salestaxpercent'],
            'shippingincludedintax'=>$MEO['shippingincludedintax'],
            'salestaxamount'=>$MEO['salestaxamount'],
            'tax_cost'=>$MEO['salestaxamount'],
            //其它
            'salesrecordnum'=>$MEO['salesrecordnum'],
        );
        }
        //地址信息 ,只有为空时 才可以 编辑
        if($isNew || !$ifEbayOrderCompleted ||
           ((strlen($MMO['ship_name'].$MMO['ship_country'].$MMO['ship_street1'])==0) &&
           $MEO['ship_name']&&$MEO['ship_country']&&$MEO['ship_street1'])
           ){
            $AMMO+=array(
            'ship_name'=>$MEO['ship_name'],
            'ship_cityname'=>$MEO['ship_cityname'],
            'ship_stateorprovince'=>$MEO['ship_stateorprovince'],
            'ship_country'=>$MEO['ship_country'],
            'ship_countryname'=>$MEO['ship_countryname'],
            'ship_street1'=>$MEO['ship_street1'],
            'ship_street2'=>$MEO['ship_street2'],
            'ship_postalcode'=>$MEO['ship_postalcode'],
            'ship_phone'=>$MEO['ship_phone'],
            'ship_email'=>$MEO['ship_email'],
            'addressid'=>$MEO['addressid'],
            'addressowner'=>$MEO['addressowner'],
            );
        }
        
        Helper_Array::removeEmpty($AMMO);
        $MMO->setAttributes($AMMO);
        if(!($isNew||$merge)){
            $MMO->recalculate();
        }
        
        $MMO->save();
        if($isNew||$merge){
            foreach($MTs as $MT){
                $MT->myorderid=$MMO->myorderid;
                $MT->save();
            }
            //重取对象主要是包括下面的 transaction 
            $MMO=OdOrder::model()->where('myorderid=?',$MMO->myorderid)->getOne();
            $MMO->getCbyfCalculate();
            $MMO->recalculate();
            $MMO->save();
        }
        // 客户信息表
        
        // 新订单需要 存主要信息
        // 未完成订单 需要存主要信息 
        if($isNew || !$ifEbayOrderCompleted ){
            self::saveCustomer($MEO['buyeruserid'],$MMO->selleruserid,$MEO,$MMO);
        }
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
            Helper_Array::removeEmpty($n_shipping_address);
            $n_shipping_address['ship_street2']=@$shipping_address['Street2'];
        }
        return $n_shipping_address;
    }
    
    /***
     * which order is using for transactions 
     * 一个 order 中有多个 transaction , 应该使用哪个 t 的 myorder . 
     */
    static function _oldestMyorder($myorderids){
         // 找出最老order , 并抛弃新 order
        $MMOs=OdOrder::model()->where('myorderid in (?)',$myorderids)->order('created DESC')->getAll();
        foreach($MMOs as $MMO){
            if(emtpy($MO)||$MO->isNewRecord||
               $MMO->created < $MO->created ){
                $MO=$MMO;
            }
        }
        return $MO;
    }
    
    /**
     *  判断 是否可以 合并订单
     */
    static function ifMergeOrder($MMOs=null,&$error=null){
    	
        if($MMOs instanceof Ebay_Myorders){
            $MMOs=array($MMOs);
        }
        
        foreach($MMOs as $MMO){
            
            //if(in_array($MMO->status_payment,
            //      array(
            //          Ebay_Myorders::STATUS_PAYMENT_COMPLETE_PART,
            //          Ebay_Myorders::STATUS_PAYMENT_COMPLETE
            //      ))){
            //  $error='已经付款';
            //  return false;
            //}
            // 1, 是否是 已经发货的 
            
            if($MMO->is_send==1){
                $error=$MMO->myorderid.',已经发货';
                return false;
            }
            ////2, 已经打印的
            //if(strlen($MMO->rn)>0){
            //    $error=$MMO->myorderid.',已经打印';
            //    return false;
            //}
            ////3, 已经发货的
            //if($MMO->shipped || $MMO->status_shiped){
            //    $error=$MMO->myorderid.',已经发货';
            //    return false;
            //}
            // 只有 未付款,和在 已付款 中,也即 待处理中,的可以合并拆分。   正在处理中的,都是不可以再合并拆分的。
            
            if($MMO->orderstatus>1){
                $error=$MMO->myorderid.',orderstatus '.$MMO->orderstatus.' 订单状态为已经处理';
                return false;
            }
        }
        return 1;
    }
    
    /**
     * 是否需要更新 修改
     */
    static function ifOrderModify(&$MO,$OA){
        //1 , LastModifiedTime
            $LastModifiedTime=strtotime($OA['CheckoutStatus']['LastModifiedTime']);
            if($MO->lastmodifiedtime != $LastModifiedTime ){
                return true;
            }
        //2, orderid
            if($MO->ebay_orderid!=$OA['OrderID']){
                return true;
            }
        //3, 
        return false;
    }
    
    /***
     * @author lxqun
     * 保存 买家信息
     * 
     */
    static function saveCustomer($buyeruserid,$selleruserid,&$MEO,&$MMO){
        if($MMO->ecid&&$MEO->ecid){ // 跳过不必重复保存
            return true;
        }
        Queue_Getbuyeruser::Add($selleruserid,$buyeruserid);
        $MC=Ebay_Customers::where('buyeruserid=? And selleruserid=?',$buyeruserid,$selleruserid)->getOne();
        $C_v=array();
        if($MC->isNewRecord){
            $C_v=array(
                'uid'=>$MMO->uid,
                'selleruserid'=>$selleruserid,
                'buyeruserid'=>$buyeruserid,
                
            );
        }
        // 增加用户的 订单数量 和购买总金额
        $MC->AddOrderNumAmount($MMO->finel_amount,$MMO->currency);
        
        if($MC->isNewRecord||
            empty($MC->name)||empty($MC->email)
            ){
            $C_v+=array(  // 从订单中取出 地址信息 .
                    'email'=>$MEO->ship_email,
                    'name'=>$MEO->ship_name,
                    'cityname'=>$MEO->ship_cityname,
                    'stateorprovince'=>$MEO->ship_stateorprovince,
                    'country'=>$MEO->ship_country,
                    'countryname'=>$MEO->ship_countryname,
                    'street1'=>$MEO->ship_street1,
                    'street2'=>$MEO->ship_street2,
                    'postalcode'=>$MEO->ship_postalcode,
                    'phone'=>$MEO->ship_phone,
                );
            Ebay_Customers::removeInvalid($C_v);
            $MC->setAttributes($C_v);
            
            $MC->save();
            $isNew=1;
        }
        if($MMO->ecid!=$MC->ecid){
            $MMO->ecid=$MC->ecid;
            $MMO->save();
        }
        if($MEO->ecid!=$MC->ecid){
            $MEO->ecid=$MC->ecid;
            $MEO->save();
        }
        
        if(empty($isNew)){
            // 保存时 有个 统计计算 所以 需要
            $MC->save();
        }
    }
    
    /**
     * @author lxqun 
     * 按 队列
     */
    static function cronRequestOrderQueue($multiprocessing=null){
        $betimeline=time();
        //$sql="select from queue_getorder 
        $sql_where="where created <= $betimeline" ;
        $select=Queue_Getorder::find('created <= ?',$betimeline);
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
        
        $h=Yii::app()->db->createCommand("SELECT count(*) as c,selleruserid from ".Queue_Getorder::model()->tableName()." ".$sql_where." group by selleruserid order by c DESC")->query();
        $queue_selleruserid=array();
        while($r=$h->fetchRow()){
            $queue_selleruserid[]=$r['selleruserid'];
        }
        foreach($queue_selleruserid as $selleruserid){
            echo "\n[".date('H:i:s').'-'.__LINE__.'- selleruserid:'.$selleruserid."]\n";
            
            $ebayorderids=array();
            $h2=Yii::app()->db->createCommand("SELECT ebay_orderid,selleruserid from ".Queue_Getorder::model()->tableName()." ".$sql_where." And selleruserid='$selleruserid' ")->query();
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
                    Queue_Getorder::meta()->deleteWhere('ebay_orderid in (?)',$do_ebayorderids);
                }catch(Exception $ex){
                	Helper_ExceptionLog::Add($ex,__CLASS__,__FUNCTION__,__LINE__);
                    echo "Error Message :  ". $ex->getMessage();
                   
                }
            }
            Yii::app()->db->createCommand("OPTIMIZE TABLE ".Queue_Getorder::model()->tableName()." ;")->query();
            
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
            $METs=OdEbayExternaltransaction::model()->where('ebay_orderid=?',array($MEO->ebay_orderid))->getCount();
            if($METs['row_count']!=$nC){
            foreach($externaltransaction as $ET){
                $MET=OdEbayExternaltransaction::model()->where('externaltransactionid=? And paymentorrefundamount=?',array($ET['ExternalTransactionID'],$ET['PaymentOrRefundAmount'] ))->getOne();
                if($MET->isNewRecord){
                    $MET=new OdEbayExternaltransaction(array(
                        'selleruserid'=>$selleruserid,
                        'ebay_orderid'=>$MEO['ebay_orderid'],
                        'externaltransactionid'=>$ET['ExternalTransactionID'],
                        'paymentorrefundamount'=>$ET['PaymentOrRefundAmount'],
                        'feeorcreditamount'=>$ET['FeeOrCreditAmount'],
                        'externaltransactiontime'=>$ET['ExternalTransactionTime'],
                    ));
                    $MET->save();
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
        foreach($ts as $t){
            if(!isset($t['ShippingDetails']['ShipmentTrackingDetails'])) continue ;
            $ShipmentTrackingDetails=$t['ShippingDetails']['ShipmentTrackingDetails'];
            if(isset($ShipmentTrackingDetails['ShipmentTrackingNumber'])){
                $ShipmentTrackingDetails=array($ShipmentTrackingDetails);
            }
            foreach($ShipmentTrackingDetails as $STD){
                $ShipmentTrackingNumber_array[$STD['ShipmentTrackingNumber']]=array(
                    'itemid'=>$t['Item']['ItemID'],
                    'transactionid'=>$t['TransactionID'],
                    'ShipmentTrackingDetails'=>$STD,
                );
            }
        }
        // 2 , Ebay_Myorders_Shipping 中 可以查找更新
        foreach($ShipmentTrackingNumber_array as $di){
            $itemid=$di['itemid'];
            $transactionid=$di['transactionid'];
            $ShipmentTrackingNumber=$di['ShipmentTrackingDetails']['ShipmentTrackingNumber'];
            $ShippingCarrierUsed=$di['ShipmentTrackingDetails']['ShippingCarrierUsed'];
            $MT=OdEbayTransaction::model()->where('transactionid=? And itemid=? ',array($transactionid,$itemid))->getOne();
            if($MT->isNewRecord){
                 continue 1;
            }
            //跳过,已经重发的订单 
            $MMO=OdOrder::model()->where('myorderid=?',$MT->myorderid)->getOne();
            if($MMO->is_resend){
            	 continue 1;
            }
            
            $EOS=OdOrderShipping::model()->where('myorderid=? And shipped_tracking_number=?',array($MT->myorderid,$ShipmentTrackingNumber))->getOne();
            if($EOS->isNewRecord){
                $EOS->myorderid=$MT->myorderid;
                $EOS->shipped_tracking_number=$ShipmentTrackingNumber;
                $EOS->shipped_carrier_used=$ShippingCarrierUsed;
                $EOS->uid=$MT->uid;
                $EOS->save();
            }
        }
        return 1;
    }
}
?>