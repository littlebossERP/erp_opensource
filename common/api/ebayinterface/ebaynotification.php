<?php
/**
 * notification 分析调度模块
 * @package interface.ebay.tradingapi
 */
class EbayInterface_EbayNotification extends EbayInterface_base{
    /**
     * 队列处理
     *
     */
    function processQueue($types=null){
        $notifications_select=Ebay_Notification::find('processed=?',0);
        if (!is_null($types)){
            $notifications_select->where('[type] in (?)',Q::normalize($types));
        }
        $notifications=$notifications_select->order('created ASC')->limit(0,50)->getAll();
        foreach ( $notifications as $notification){
            Yii::log('notification process:'.$notification->nid);
            if ($notification->production){
                $this->production=true;
                $this->_loadconfig();
            }else {
                $this->production=false;
                $this->_loadconfig();
            }
            try {
                echo "---------------------------- notification_id : ".$notification->nid."\n" ;
				$xmlfile=file_get_contents($notification->path.$notification->nid.'.xml');
				if(empty($xmlfile)){
					$xmlfile=$notification->xml;
				}
				$auth=$this->getSoap($xmlfile);
		    	//$auth=$this->getSoap($notification->xml);
            }catch (Exception $ex){
                print_r($ex);
                System::sendMail('developer@coomao.com','xml error',print_r($ex,true)."\r\n\r\n\r\n".print_r($notification,true));
                continue;
            }
            try {
                if ($auth === false){
                    $notification->auth=0;
                }
                $notification->processed=1;
                $notification->save();
            }catch (Exception $ex){
                System::sendMail('developer@coomao.com','save error',print_r($notification,true));
            }
        }
		// 删除已经处理过的文件 
		// 删除已经处理过的记录
		$outdatetime=time()-86400*3;// 10 天前
		$notifications=Ebay_Notification::find('processed=? And created<?',1,$outdatetime)->order('created ASC')->limit(0,50)->getAll();
		$deletedNids=array();
		if(count($notifications)) foreach ( $notifications as $notification){
			// delete file
			$filename=$notification->path.$notification->nid.'.xml' ;
			if(file_exists($filename)){
				unlink($filename);
			}
			$deletedNids[$notification->nid]=$notification->nid;
		}
		// 清除记录
		if(count($deletedNids)){
			Ebay_Notification::meta()->deleteWhere('nid in (?)',$deletedNids);
        }
    }
    /**
     * 接收反馈 值
     * $soapStr://取得 $_POST 的原始数据.以及 SOAP 发送来的格式 .    
     *          EX: getSoap(file_get_contents("php://input")); 
     *       
     */
    function getSoap($soapStr){
        //if(empty($soapStr)||!is_string($soapStr)) return false;
        $DOM = new DOMDocument();
        if(@!$DOM->loadXML($soapStr)){
            echo 'Error Format,Not Xml Document!';
            return false; //格式 错误 的请求
        }
        $Ack = $DOM -> getElementsByTagName('Ack') -> length > 0 ? $DOM -> getElementsByTagName("Ack") -> item(0) -> nodeValue : false;
        $NotifyType = $DOM -> getElementsByTagName("NotificationEventName") -> length > 0 ? $DOM -> getElementsByTagName("NotificationEventName") -> item(0) -> nodeValue : false;
        $eBayItemID = $DOM -> getElementsByTagName("ItemID") -> length > 0 ? $DOM -> getElementsByTagName("ItemID") -> item(0) -> nodeValue : false;
        
        $Timestamp = $DOM -> getElementsByTagName('Timestamp') -> length > 0 ? $DOM -> getElementsByTagName('Timestamp') -> item(0) -> nodeValue : false;
        $eBayNotificationSignature = $DOM -> getElementsByTagName('NotificationSignature') -> length > 0 ? $DOM -> getElementsByTagName('NotificationSignature') -> item(0) -> nodeValue : false;
        $VerificationSignature = $Timestamp ? $this->callbackSign($Timestamp) : false;
        // 通过 验证
        if($eBayNotificationSignature == $VerificationSignature){
            $valid = true;
        }else{
            echo 'Error sign';
            return false;
        }

        switch ($NotifyType) //处理
        {
            case 'EndOfAuction': // 取得 订单
            case 'AuctionCheckoutComplete':
            case 'FixedPriceEndOfTransaction':
            case 'CheckoutBuyerRequestsTotal':
            case 'FixedPriceTransaction':
            case 'ItemMarkedShipped':
                $domTransaction=parent::domxml2a($DOM->getElementsByTagName('GetItemTransactionsResponse'),1);
                $domTransaction=$domTransaction['GetItemTransactionsResponse'];
                ibayPlatform::useSellerUserID($domTransaction['RecipientUserID']);
                self::LMSnotificationTransaction($domTransaction);
                //售后第一封邮件结束
                return true;
                break;
            case 'FeedbackReceived':
                $domFeedBack=parent::domxml2a($DOM->getElementsByTagName('GetFeedbackResponse'));
                $domFeedBack=$domFeedBack['GetFeedbackResponse'];
                ibayPlatform::useSellerUserID($domFeedBack['RecipientUserID']);
                $api=new EbayInterface_getFeedback();
                $api->save($domFeedBack);
                return true;
                break;
            case 'AskSellerQuestion':
                $dom=parent::domxml2a($DOM->getElementsByTagName('GetMemberMessagesResponse'));
                $dom=$dom['GetMemberMessagesResponse'];
                ibayPlatform::useSellerUserID($dom['RecipientUserID']);
                $api=new EbayInterface_getMemberMessages();
                $api->save($dom);
                return true;
                break;
            case 'ItemClosed':
                $domItem=parent::domxml2a($DOM->getElementsByTagName('GetItemResponse'));
                $domItem=$domItem['GetItemResponse'];
                /**如果有设无限relist的，进行relist
                *  auther@fanjs
                */
                ibayPlatform::useSellerUserID($domItem['RecipientUserID']);
                
                if(isset($domItem['Item']['ItemID'])){
                	Helper_Autoadditemset::autoadditemset($domItem['Item']['ItemID']);
                }
                $ei=new EbayInterface_getitem();
                $ei->save($domItem,'ItemClosed');
                break;
            case 'MyMessagesM2MMessage':
            case 'MyMessageseBayMessage': 
            case 'MyMessagesAlert':
            case 'MyMessagesHighPriorityMessage':   
                $domMsg=parent::domxml2a($DOM->getElementsByTagName('GetMyMessagesResponse'));
                $domMsg=$domMsg['GetMyMessagesResponse'];
                ibayPlatform::useSellerUserID($domMsg['RecipientUserID']);
                $api=new EbayInterface_getMyMessages();
                $api->save($domMsg);
                return true;
                break;
            case 'BuyerResponseDispute':
            case 'INRBuyerOpenedDispute':
            case 'INRBuyerRespondedToDispute':
            case 'INRBuyerClosedDispute':
                $domDp=parent::domxml2a($DOM->getElementsByTagName('GetDisputeResponse'));
                $domDp=$domDp['GetDisputeResponse'];
                ibayPlatform::useSellerUserID($domDp['RecipientUserID']);
                $api=new EbayInterface_getDispute();
                $api->save($domDp['Dispute']);
                return true;           
                break;  
            case 'BestOffer':
            case 'BestOfferPlaced':
            case 'BestOfferDeclined':
                $domDp=parent::domxml2a($DOM->getElementsByTagName('GetBestOffersResponse'));
                $domDp=$domDp['GetBestOffersResponse'];
                ibayPlatform::useSellerUserID($domDp['RecipientUserID']);
                $api=new EbayInterface_GetBestOffers();
                $api->save($domDp);
                return true;
                break;
            case 'EBPMyResponseDue':
            case 'EBPOtherPartyResponseDue':
            case 'EBPEscalatedCase':
			case 'EBPAppealedCase':
			case 'EBPMyPaymentDue':
			case 'EBPMyPaymentDone':
			case 'EBPClosedAppeal':
			case 'EBPClosedCase':
			case 'EBPOnHoldCase':
				$domDp=parent::domxml2a($DOM->getElementsByTagName('NotificationEvent'));
				$domDp=$domDp['NotificationEvent'];
				//选库
		    	$seller=$domDp['RecipientUserID'];
				ibayPlatform::useSellerUserID($seller);
        		$EBPD=new EbayInterface_Resolution_getebpcasedetail();
	    		$EBPD->eBayAuthToken=SaasEbayUser::model()->where('selleruserid = ?',$seller)->getOne()->token;
	    		$ebpresult=$EBPD->api($domDp['CaseId'],$domDp['CaseType']);
	    		if ($ebpresult['ack']=='Success'||$ebpresult['ack']=='Warning'){
	    			$EBPD->savebynotification($ebpresult);
	    		}
				return true;
            	break;
            default : //放弃处理
                return true;
                break;
        }
    }
    /**
     * 验证码 
     */
    function callbackSign($Timestamp){
        // Not quite sure why we need the pack('H*'), but we do
        $Signature = base64_encode(pack('H*', md5($Timestamp.$this->config["devID"].$this->config["appID"].$this->config["certID"])));
        return $Signature;
    }
    
    
    
    
    
    /**
     * 保存 N 中的 T ,主要是生成 T 
     * @author lxqun
     * 
     */
    public function saveOneTransaction($TA,$Mitem,$uid,$selleruserid,&$MT=null,$IA=null){
        //判断 transaction 状态
        $LastTimeModified=strtotime($TA['Status']['LastTimeModified']);
        
        //3 ,建立 Transaction  
        $Added_quantitypurchased=0; // 新添加了的 订单货品数量
        if($MT->isNewRecord){
            //3.1 新的 Transaction 初始 所属用户
            $MT->setAttributes(array(
                'uid'=>$uid,
                'selleruserid'=>$selleruserid,
                'transactionid'=>$TA['TransactionID'],
                'itemid'=>$Mitem->itemid,
               ));
            //3.2 如果是一口价的，数据库中不是第一个记录的话，成本记录为0；
        
            if ($Mitem->itemid && OdEbayTransaction::model()->where('itemid=? and selleruserid=?',array($Mitem->itemid,$selleruserid))->getCount()<1){
//              $log=Ebay_Log_Muban::find('itemid=?',$Mitem->itemid)->getOne();
                $MT->additemfee=$Mitem->additemfee;
                $MT->additemfee_currency=$Mitem->additemfee_currency;
            }
            //3.3, 商品信息  
            $MT->goods_id=$Mitem->goods_id;
            $MG=Ebay_MyGood::find('id=?',$Mitem->goods_id)->getOne();
            $MT->coomao_goods_id=$MG->coomao_goods_id;
            $MT->goodscategory_id=$MG->goodscategory_id;
            $MT->property_id=$Mitem->log->property_id;
            
            // 3.4 商品数量 
            $Added_quantitypurchased=$TA['QuantityPurchased'];
        }else{
            // 3.4  新添加了的 订单货品数量
            if($TA['QuantityPurchased']!= $MT->quantitypurchased){
                $Added_quantitypurchased=$TA['QuantityPurchased']-$MT->quantitypurchased;
            }
        }
        
        //4, 主体数据 
        $TA_v=array(
            'sku'=>$IA['SKU'],
            'buyer_id'=>$TA['Buyer']['UserID'],
            'title'=>$IA['Title'],
            'createddate'=>strtotime($TA['CreatedDate']),
            'quantitypurchased'=>$TA['QuantityPurchased'],
            'lotsize'=>$IA['LotSize'],
            'platform'=>$TA['Platform'],
            'buyer'=>Helper_xml::isArray($TA['Buyer'])?$TA['Buyer']->toArray():null,
            'status'=>Helper_xml::isArray($TA['Status'])?$TA['Status']->toArray():null ,
            'amountpaid'=>$TA['AmountPaid'],
            'transactionprice'=>$TA['TransactionPrice'],
            
            'finalvaluefee'=>@$TA['FinalValueFee'],
            'transactionsiteid'=>$TA['TransactionSiteID'],
            'paypalemailaddress'=>$TA['PayPalEmailAddress'],
            'buyercheckoutmessage'=>@$TA['BuyerCheckoutMessage'],
            'lasttimemodified'=>$LastTimeModified,
            'salesrecordnum'=>@$TA['ShippingDetails']['SellingManagerSalesRecordNumber'],
            'orderid'=>@$TA['ContainingOrder']['OrderID'],
            'orderlineitemid'=>@$TA['OrderLineItemID'],
            
        );
        //5.3 -- N I 中有
        if(isset($TA['ShippingServiceSelected'])){
            $TA_v['shippingserviceselected']=Helper_xml::isArray($TA['ShippingServiceSelected'])?$TA['ShippingServiceSelected']->toArray():null ;
        }
        // 5.4 ShippingServiceCost
        if(@isset($TA['ShippingServiceSelected']['ShippingServiceCost'])){
            $TA_v['shippingservicecost']=$TA['ShippingServiceSelected']['ShippingServiceCost'];
        }elseif(@isset($TA['ShippingDetails']['ShippingServiceOptions']['ShippingServiceCost'])){
            $TA_v['shippingservicecost']=$TA['ShippingDetails']['ShippingServiceOptions']['ShippingServiceCost'];
        }
        
        //5.4
        //if(isset($TA['PaidTime'])){
        //    $TA_v['paidtime'] =strtotime($TA['PaidTime']);
        //}
        //5.5
        if(isset($TA['BuyerMessage'])){
            $TA_v['buyermessage'] =strtotime($TA['BuyerMessage']);
        }
        //5.6 
        if(isset($TA['Variation'])) $TA_v['variation']= Helper_xml::isArray($TA['Variation'])?$TA['Variation']->toArray():null ;
        //5.7
        if(!empty($TA['ShippedTime'])){
            $TA_v['shipped']=true;
        }
		// variation 下的 sku ,及 title 
		if( isset($TA_v['variation']['VariationTitle']) && strlen($TA_v['variation']['VariationTitle'])>0){
            $TA_v['title']=$TA_v['variation']['VariationTitle'];
        }
		if( isset($TA_v['variation']['SKU']) && strlen($TA_v['variation']['SKU'])>0){
            $TA_v['sku']=$TA_v['variation']['SKU'];
        }
        //6, 支付状态 加上 付款时间 
        if(isset($TA['Status']['CheckoutStatus'])){
          if($TA['Status']['CheckoutStatus']=='CheckoutComplete'){
              if($TA['Status']['eBayPaymentStatus']=='NoPaymentFailure'&&isset($TA['PaidTime'])){
                   $TA_v['status_payment']=OdEbayTransaction::STATUS_PAYMENT_COMPLETE;
                   $TA_v['paidtime'] =strtotime($TA['PaidTime']);
              }else{
                   $TA_v['status_payment']=OdEbayTransaction::STATUS_PAYMENT_PROCESSING;
              }
          }else{
               $TA_v['status_payment']=OdEbayTransaction::STATUS_PAYMENT_WAITING;
          }
        }
        //7 ,  取 currency
        if($TA->getChildrenAttrible('TransactionPrice','currencyID')){
            $TA_v['currency']=$TA->getChildrenAttrible('TransactionPrice','currencyID');
        }
        if(isset($TA['FinalValueFee'])&&$TA->getChildrenAttrible('FinalValueFee','currencyID')){
            $TA_v['finalvaluefee_currency']=$TA->getChildrenAttrible('TransactionPrice','currencyID');
        }
        // 修复加上 orderlineitemid 
        if(empty($TA_v['orderlineitemid'])){
            $TA_v['orderlineitemid']=$Mitem->itemid.'-'.$MT['transactionid'];
        }
        Helper_Array::removeEmpty($TA_v);
        $MT->setAttributes($TA_v);
        
        //----------------------------------------------------------------------
        // 主要数据赋值后
        // 如果 sku 未被 赋值, 这里加补
        if (strlen($MT->sku)==0){
            $MT->sku=$Mitem->good->sn;
        }
        // 最后才保存
        $MT->save();
        //----------------------------------------------------------------------
        //其它操作
        //  商品库存
        // +未发货 库存 -网上在卖数量
        // $Added_quantitypurchased  是 已经存在的 $n->quantitypurchased 与 取来的 之间的差 . 
        //if (!$Mitem->good->isNewRecord && $Added_quantitypurchased!=0){
            //在线库存减少
            //$Mitem->good->updateStock('stocklisd',-$Added_quantitypurchased,$Mitem->lotsize);
            //$Mitem->good->updateStock('stockbesent',$Added_quantitypurchased,$Mitem->lotsize);
        //}
        
        // 外部订单id -- 目前 放在 sold report 中
        //if (isset($TA['ExternalTransaction'])){
        //  if (isset($TA['ExternalTransaction']['ExternalTransactionID'])){
        //      $et[0]=$TA['ExternalTransaction'];
        //  }else{
        //      $et=$TA['ExternalTransaction'];
        //  }
        //  if (count($et)){
        //      foreach ($et as $e){
        //          $oe=Order_Externaltransaction::find('externaltransaction_id = ?',$e['ExternalTransactionID'])->getOne();
        //          if($MT->myorderid){
        //              $oe->myorderid=$MT->myorderid;
        //          }
        //          $oe->tid=$MT->id;
        //          $oe->externaltransaction_id=$e['ExternalTransactionID'];
        //          $oe->externaltransaction_time=strtotime($e['ExternalTransactionTime']);
        //          $oe->externaltransaction_fee=$e['FeeOrCreditAmount'];
        //          $oe->save();
        //      }
        //  }
        //}
        return $MT;
    }
    
    /**
     * 保存地址信息
     * @author lxqun
     */
    public function saveAddress($t,&$MT){
        if(empty($MT->myorderid) && empty($MT->eorderid)){ return false; }
        // 修复 order 中的 地址信息
        $ShippingAddress= EbayInterface_getorders::setShippingAddress($t['Buyer']['BuyerInfo']['ShippingAddress']);
        if($MT->eorderid){
            $MEO=OdEbayOrder::model()->find('eorderid=?',$MT->eorderid);
            if(!strlen($MEO->ship_name)){
                $MEO->setAttributes($ShippingAddress);
                $MEO->save();
            }
        }
        if($MT->myorderid){
            $MMO=OdOrder::model()->find('myorderid=?',$MT->myorderid);
            if(!strlen($MMO->ship_name)){
                $MMO->setAttributes($ShippingAddress);
                $MMO->save();
            }
            $MC=Ebay_Customers::find('ecid=?',$MMO->ecid)->getOne();
        }
        // 修复 买家客户信息
        if(@$MC&&$MC->ecid&&!strlen($MC->name)&&!strlen($MC['eiastoken'])){
            if(isset($t['Buyer'])){
            $C_v=array(
                'buyeruserid'=> $t['Buyer']['UserID'],
                'eiastoken'=>@$t['Buyer']['EIASToken'],
                'email'=>@$t['Buyer']['Email'],
                'feedbackscore'=>@$t['Buyer']['FeedbackScore'],
                'positivefeedbackpercent'=>@$t['Buyer']['PositiveFeedbackPercent'],
                'registrationdate'=>@strtotime($t['Buyer']['RegistrationDate']),
                'feedbackratingstar'=>$t['Buyer']['FeedbackRatingStar'],
                'site'=>@$t['Buyer']['Site'],
                'status'=>$t['Buyer']['Status'],
                'useridlastchanged'=>@$t['Buyer']['UserIDLastChanged'],
                'vatstatus'=>$t['Buyer']['VATStatus'],
            );
            if(isset($t['Buyer']['BuyerInfo']['ShippingAddress'])){
                $C_v+=array(  // 从订单中取出 地址信息 .
                        'name'=>@$t['Buyer']['BuyerInfo']['ShippingAddress']['Name'],
                        'cityname'=>@$t['Buyer']['BuyerInfo']['ShippingAddress']['CityName'],
                        'stateorprovince'=>@$t['Buyer']['BuyerInfo']['ShippingAddress']['StateOrProvince'],
                        'country'=>@$t['Buyer']['BuyerInfo']['ShippingAddress']['Country'],
                        'countryname'=>@$t['Buyer']['BuyerInfo']['ShippingAddress']['CountryName'],
                        'street1'=>@$t['Buyer']['BuyerInfo']['ShippingAddress']['Street1'],
                        'street2'=>@$t['Buyer']['BuyerInfo']['ShippingAddress']['Street2'],
                        'postalcode'=>@$t['Buyer']['BuyerInfo']['ShippingAddress']['PostalCode'],
                        'phone'=>@$t['Buyer']['BuyerInfo']['ShippingAddress']['Phone'],
                    );
            }
            Ebay_Customers::removeInvalid($C_v);
            $MC->setAttributes($C_v);
            $MC->save();
            
            
            }
        }
    }
    
    
    /**
     * 标已经发货  ItemMarkedShipped 
     * 只需要 标记! 别的都不做!
     */
    function NotificationMarkShipped($requestArr){
        // 只有  transaction 信息
        if(!isset($requestArr['TransactionArray'])) return false;
        $t=$requestArr['TransactionArray']['Transaction'];
        $IA=$requestArr['Item'];

        $selleruserid=$requestArr['Item']['Seller']['UserID'];
        
        $Ebay_User=SaasEbayUser::model()->where('selleruserid =?',$selleruserid)->getOne();
        if ($ei->isNewRecord){
            Yii::log('Item user miss!');
            if (!$Ebay_User->isNewRecord){
                $uid=$Ebay_User->uid;
            }else { // 对未存的 selleruserid 不再接收处理
                return false;
            }
        }else {
            $uid=$ei->uid;
        }
        if(@isset($t['ContainingOrder']['OrderID'])){
            $ebay_orderid=$t['ContainingOrder']['OrderID'];
        }else{
            $ebay_orderid=$requestArr['Item']['ItemID'].'-'.$t['TransactionID'];
        }
        
        // 中间应该包含了 更多的 信息,包括 买家信息,收个地信息
        $MT=OdEbayTransaction::model()->where('transactionid=? And itemid=? And selleruserid=?',array($t['TransactionID'],$IA['ItemID'],$selleruserid))->getOne();
        
        if(!empty($t['ShippedTime'])){
            $TA_v['shipped']=true;
        }
        
    }
}
?>