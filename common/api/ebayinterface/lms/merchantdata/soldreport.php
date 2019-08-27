<?php
/**
 * @author lxqun
 * Large Merchant Service
 * 为 merchantdata 下面的各接口 提供组织流程  
 */
class EbayInterface_LMS_merchantdata_soldreport extends EbayInterface_LMS_merchantdata_base
{
    
    //下载A
    static function downloadFileSR($eb,$eu){
        $filename=INDEX_DIR.'/link/xml/'.$eu->selleruserid.'/'.$eb->jobtype.'/'.$eb->jobid.'.zip';
        
        if (!file_exists($filename)){
            $dapi=new EbayInterface_LMS_downloadFile();
            $dapi->eBayAuthToken=$eu->token;
            $dr=$dapi->api($eb->filereferenceid,$eb->jobid,$filename);
            if (!$dr){
                continue;
            }
        }
        
        // Add to process queue 
        $dirname=dirname($filename);
        $xmlfilename=$dirname.'/'.$eb->jobid.'_report.xml';
        //self::processOne($xmlfilename,$eb->selleruserid,$eb->uid);
        $queLP=Queue_Lmsprocess::Add($eb->selleruserid,$eb->jobid,$eb->jobtype,$xmlfilename);
        //clear No processed xml files .
        Queue_Lmsprocess::meta()->updateWhere(array('status'=>4),'selleruserid=? And jobtype=? And status=? And qid<?',$eb->selleruserid,$queLP->jobtype,0,$queLP->qid); 
        return $xmlfilename;
    }
    
    /**
     *  请求流程
     *  供调用
     *  @param $multiprocessing   Array 控制多进程处理 
     */              
    static function cronRequest($inRecurFunc=0,$multiprocessing=null){
        //控制 间隔时间 .
        $sleepTime=600; //10 分钟
        // 请求 ,直接 调用 startDownloadJob
        $i=3;
        do{  echo "[".date('H:i:s').'-'.__LINE__."]\n";
            set_time_limit(0);
            // 定时 去取
            $select=Ebay_Bulkgetitem::find('jobtype="SoldReport" and is_recur=1 and last_download_time + recur_min*60 +? < UNIX_TIMESTAMP()',$sleepTime);
            if(is_array($multiprocessing)&&$multiprocessing[0]>0&&isset($multiprocessing[1])){
                $multiprocessingTotal=(int)$multiprocessing[0];
                $multiprocessingNo=(int)$multiprocessing[1];
                $select->where("(id%$multiprocessingTotal)=$multiprocessingNo");
            }
            $ebs=$select->order('last_download_time asc')->getAll();
            if(count($ebs)) foreach($ebs as $eb){  echo "[".date('H:i:s').'-'.__LINE__."]\n";
                $eu=SaasEbayUser::model()->where('selleruserid =?',$eb->selleruserid)->getOne();
                ibayPlatform::useDB($eu->uid,true);
                if(empty($eu->token)||empty($eb->recur_jobid)){
                    echo " selleruserid:".$eb->selleruserid." , No user token Or No recur_jobid . \n";
                    continue 1;
                }
                //selleruserid is changed .
                //$getUser=new EbayInterface_getUser();
                //$getUser->eBayAuthToken=$eu->token;
                //$RgetUser=$getUser->api();
                //if ($getUser->responseIsFailure()){
                //  continue 1;
                //}
                //if($RgetUser['User']['UserID']!=$eb->selleruserid&&$RgetUser['User']['UserIDChanged']=='true'){
                //  $eb->is_recur=0;
                //  $eb->save();
                //  echo " selleruserid:".$eb->selleruserid." , Has changed to new ebay User : ".$RgetUser['User']['UserID']." . \n";
                //  continue 1;
                //}
                // 取得recurringjob 状态和瞎猜参数
                $api=new EbayInterface_LMS_getRecurringJobExecutionStatus();
                $api->eBayAuthToken=$eu->token;
                $r=$api->api($eb->recur_jobid);
                if ($api->responseIsFailure() || !isset($r['jobProfile']['fileReferenceId'])){
                    echo "\n --  ".date('Y-m-d H:i:s')." selleruserid:".$eb->selleruserid."  Error getRecurringJobExecutionStatus   : \n";
                    // 自动 中止
                    if($r['error']['errorId']==11002){ // Authentication failed : Token has hard-expired
                        $eb->is_recur=0;
                        $eb->save();
                    }
                    print_r($r);
                    continue;
                }
                $eb->setAttributes(array(
                    'jobid'=>$r['jobProfile']['jobId'],
                    'filereferenceid'=>$r['jobProfile']['fileReferenceId'],
                    'last_download_time'=>time(),
                    'status'=>$r['jobProfile']['jobStatus']
                ));
                $eb->save();
                //下载ActiveInventoryReport
                self::downloadFileSR($eb,$eu);
                echo "-- soldReport downloaded . selleruserid:".$eb->selleruserid." \n";
            }
            if($inRecurFunc){
                break 1;
            }else{
                if(count($ebs)){
                    $i=5;
                }else{
                    if($i<1800) $i++;
                }
                sleep($i);
            }
        }while(1);
        // 
        
        // donwloadJob
    }
    
    /***
     * 处理 xml文件 
     * @param $multiprocessing   Array 控制多进程处理 
     */
    static function cronProcess($inRecurFunc=0,$multiprocessing=null){
        $i=5;
        do{   echo "[".date('H:i:s').'-'.__LINE__."]\n"; 
            set_time_limit(0);
            $select=Queue_Lmsprocess::find('jobtype="SoldReport" and status=0');
            if(is_array($multiprocessing)&&$multiprocessing[0]>0&&isset($multiprocessing[1])){
                $multiprocessingTotal=(int)$multiprocessing[0];
                $multiprocessingNo=(int)$multiprocessing[1];
                $select->where("(qid%$multiprocessingTotal)=$multiprocessingNo");
            }
            $queue=$select->asArray()->getAll();
  
            if($queue) foreach($queue as $Aqp){
                $qp=Queue_Lmsprocess::find('jobtype="SoldReport" and status=0 And qid=?',$Aqp['qid'])->getOne();
                if($qp->isNewRecord) continue ;
                
                $eu=SaasEbayUser::model()->where('selleruserid =?',$qp['selleruserid'])->getOne();
                ibayPlatform::useDB($eu->uid,true);
                if(strlen($qp['xmlfile'])&&file_exists($qp['xmlfile'])){
                    $xmlfilename=$qp['xmlfile'];
                }else{
                    $xmlfilename=INDEX_DIR.'/link/xml/'.$eu->selleruserid.'/'.$qp['jobtype'].'/'.$qp['jobid'].'_report.xml';
                }   
                $begintime=time();
                echo "-- begin process soldreport orders -- selleruserid : ".$qp['selleruserid']." -- ".date('Y-m-d H:i:s')." \n";
                Queue_Lmsprocess::meta()->updateWhere(array('status'=>1),'qid=?',$qp['qid']);
                self::processOne($xmlfilename,$qp['selleruserid'],$eu->uid,$eu);
                Queue_Lmsprocess::meta()->updateWhere(array('status'=>2),'qid=?',$qp['qid']);
                $endtime=time();    
                echo "-- End process soldreport orders -- Seconds : ".($endtime-$begintime)." \n";
                  
                echo "\n[".date('Y-m-d H:i:s')."] -- begin found Discard Ebayorders  \n";
                $discardEbayorders=self::foundDiscardEbayorders($qp['selleruserid']);   
                echo @implode(' ',$discardEbayorders);
                //Qlog::log(print_r($discardEbayorders,1));
                echo "\n[".date('Y-m-d H:i:s')."] -- End found Discard Ebayorders  \n";   
                // getOrders请求           
                $ebayorderids=self::getOutdaysOrders($eu);  
                echo  "\n GetOrders Request Ebay order ids : ". @implode(' ',$ebayorderids)."\n";
                $endtime2=time();
                echo "-- End getOrders -- Seconds : ".($endtime2-$endtime)." \n\n\n";
                
            }
            // delete xml File
            self::delProcessedXmlFile();  
             
            if($inRecurFunc){
                break 1;
            }else{
                if(count($queue)){
                    $i=5;
                }else{
                    if($i<1800) $i++;
                }
                sleep($i); 
            }
        }while(1);
    }
    
    /*****
     * 单个订单 内容 处理流程 
     * 
     */
     
    static function processOne($filename,$selleruserid,$uid,$eu){
//          $dirname=dirname($filename);
//          $dirname.'/'.$eb->jobid.'_report.xml'
        $skiptimeline=time()-3600*4; // 处理时间 线 ,只处理这个时间 线外的 订单 , 不再处理
            $xmlreader=new Helper_XmlLarge($filename);
            $i=0;
            while($xmlreader->read('OrderDetails')){ 
                $o=Helper_xml::domxml2XA($xmlreader->expand());
                if(empty($o)||!isset($o['OrderDetails'])) continue 1;
                $o=$o['OrderDetails'];
                $ebay_orderid=$o['OrderID'];
                
                // TransactionArray 
                if (isset($o['OrderItemDetails']['OrderLineItem']['OrderLineItemID'])){
                    $ts=array($o['OrderItemDetails']['OrderLineItem']);
                }else{
                    $ts=$o['OrderItemDetails']['OrderLineItem'];
                }
                //echo $i++. "     ".$ebay_orderid."\n";
                // 1 , save ebay order
                $MEO=OdEbayOrder::model()->find('ebay_orderid=? ',$ebay_orderid);
                $AMEO=array(); 
                if($MEO->isNewRecord){
                    $AMEO=array(
                        'selleruserid'=>$selleruserid,
                    );
                    // 订单创建时间 在处理时间线 之后进来 的 都不处理.
                    if(strtotime($o['OrderCreationTime']) > $skiptimeline){
                        continue 1;
                    }
                    foreach($ts as $t){
                        // 查找 ts 的 selleruserid 是不是 现在的 selleruserid , 如果不是 就跳出
                        list($itemid,$transactionid)=explode('-',$t['OrderLineItemID']);
                        $MT=OdEbayTransaction::model()->where('transactionid=? And itemid=? ',array($transactionid,$$itemid))->getOne();
                        if(!$MT->isNewRecord){
                            if($MT->selleruserid!=$selleruserid){
                                if(self::checkifuseridchanged($eu,$selleruserid)==0){
                                    break 2;
                                }else{
                                	//todo判断item是否为拍卖，如果是拍卖的，而且有设置secondoffer的，发送secondoffer
                                    continue 2;
                                }
                            }
                        }
                    }
                }else{
                    //跳过 selleruserid 不符的
                    if($MEO->selleruserid!=$selleruserid){
                        if(self::checkifuseridchanged($eu,$selleruserid)==0){
                            break 1;
                        }else{
                            continue 1;
                        }
                    }
                    //跳过无 金额变化的未付款订单处理
                    if(empty($o['PaymentClearedTime'])&& empty($MEO->paidtime) && $o['OrderTotalCost']== $MEO->total){
                        continue 1;
                    }
                }

                $AMEO+=array(
                    'ebay_orderid'=>$ebay_orderid,
                    'buyeruserid'=>$o['BuyerUserID'],
                    
                    'salestaxamount'=>$o['TaxAmount'],
                    'insurancecost'=>$o['InsuranceCost'],
                    'shippingservicecost'=>$o['ShippingCost'],
                    // 'subtotal'=>$o['Subtotal'],
                    'total'=>$o['OrderTotalCost'],
                    
                    'shippingservice'=>@$o['ShippingServiceToken'],
                    
                    //'orderstatus'=>$o['OrderStatus'],
                    //'ebaypaymentstatus'=>$o['CheckoutStatus']['eBayPaymentStatus'],
                    //'paymentmethod'=>$o['CheckoutStatus']['PaymentMethod'],
                    //'checkoutstatus'=>$o['CheckoutStatus']['Status'],
                    //'integratedmerchantcreditcardenabled'=>$o['CheckoutStatus']['IntegratedMerchantCreditCardEnabled'],
                    
                    //'adjustmentamount'=>$o['AdjustmentAmount'],
                    //'amountpaid'=>$o['AmountPaid'],
                    //'amountsaved'=>$o['AmountSaved'],
                    
                    //'buyercheckoutmessage'=>$o['BuyerCheckoutMessage'],
                    'externaltransaction'=> @$o['ExternalTransaction'],
                    'salesrecordnum'=>@$o['SellingManagerSaleRecordID'],
                    'responsedat'=>time(),
                ); 
                //货币
                $AMEO['currency']=$o->getChildrenAttrible('OrderTotalCost','currencyID');
                //时间 需要做判断 
                if(isset($o['OrderCreationTime'])){
                    $AMEO['createdtime']=strtotime($o['OrderCreationTime']);
                }
                if(isset($o['ShippedTime'])){
                    $AMEO['shippedtime']=strtotime($o['ShippedTime']);
                }
                
                $isallpaiedTs=1; // 所以 T 是否都已经付款
                foreach($ts as $t){
                    if(!isset($t['PaymentClearedTime'])) $isallpaiedTs=0;
                }
                // 付款时间 
                // 判断付款状态 
                if(isset($o['PaymentClearedTime'])){
                    $AMEO['paidtime']=strtotime($o['PaymentClearedTime']);
                    if($isallpaiedTs){
                        $AMEO['orderstatus']='Completed';
                    }else{
                        $AMEO['orderstatus']='Active';
                    }
                }else{
                    $AMEO['orderstatus']='Active';
                }
                //salesrecordnum
                if(strlen($AMEO['salesrecordnum'])==0&&isset($t['SellingManagerSaleRecordID'])){
                    $AMEO['salesrecordnum']=$t['SellingManagerSaleRecordID'];
                }
                Helper_Array::removeEmpty($AMEO);
                $AMEO +=self::setShippingAddress($o);
                
                    // 更新前的 一些判断 操作
                    // 如果之前的状态是已付款的 ,就不再合并了
                    $ifMerge=0; 
                    if(($MEO->isNewRecord||$MEO['orderstatus']=='Active')){ // &&$AMEO['orderstatus']=='Active'
                        $ifMerge=1;
                    }
                    if(($MEO->isNewRecord||$MEO['orderstatus']=='Completed'||$MEO['orderstatus']=='Active') && $AMEO['orderstatus']=='Active'){
                        $ifMerge+=2;
                    }
                     
                $MEO->setAttributes($AMEO);
                $MEO->save();
                 
                // 保存 Transaction
                $MTs=array();
                
                foreach($ts as $t){
                    $MTs[]=$MT=self::saveOrderLineItem($t,$MEO,$selleruserid,$uid);
                }
                
                self::ebayOrder2myorder($MEO,$MTs,$ifMerge);
                unset($MEO);
                unset($MTs);
                unset($AMEO);
         }
    }
    /****
     * Delete xmlFiles  While  processed done
     * 删除一天前的 
     */
    static function delProcessedXmlFile(){
        $timeline=time()-86400*3; // 时间线
        $queue=Queue_Lmsprocess::find('jobtype="SoldReport" and (status=1 or status=2 or status=4) And created <?',$timeline)->getAll();
        $c=0;  
        if(count($queue)) foreach($queue as $qp){  
            $xmlfilename=$qp->xmlfile;
            if(!file_exists($xmlfilename)) continue;
            $zipfilename=str_replace('_report.xml','.zip',$xmlfilename);
            unlink($xmlfilename);
            unlink($zipfilename);
            $qp->setAttributes(array('status'=>3))->save();
            $c++;
        }  
        // 删除 不再保留了的记录
        Queue_Lmsprocess::removeOutQueue();  
        return $c;
    }
    /*****
     *  接受流程
     *  供调用
     *  
     */              
    function response(){
        
    }
    
    
    
    /**
     *  @author lxqun 2011-07-05
     *  生成 Ibay 订单数组
     *  
     */
    static function ebayOrder2myorder(&$MEO,$MTs,$ifMerge=0){
        $oldmyorderids=array(); // 已经存在的 全部myorderid .
        $MTnomyorderids=array(); // 还没有myorderid 的T 
        $mtcount=0; 
        foreach($MTs as $MT){
            $oldMTids[$MT->id]=$MT->id;
            if($MT->myorderid){
                $oldmyorderids[$MT->myorderid]=$MT->myorderid;
            }else{
                $MTnomyorderids[$MT->id]=$MT;
            }
            $mtcount++;
        } 
        error_log("\n ===================================== \n ".
                  '-----------  '.print_r(date('Ymd H:i:s'),1)." ---  \n".
                  'ebay_orderid:'.$MEO->ebay_orderid." \n ".
                  '$mtcount: '.print_r($mtcount,1)." \n ".
                  "oldmyorderids: ".print_r($oldmyorderids,1)." \n "
                  ,3,INDEX_DIR.'/_devel1010/log/'.date('Ymd').'mergeorderlogic.txt');
        if($mtcount==0) return 0;  
        if(empty($oldmyorderids)){    // 新
            $MMO=self::ebayOrder2myorder_save($MEO,$MTs); 
            Ebay_Myorders::eventNewOrder($MMO); 
            error_log("\n  [ ".__LINE__." ] -- New order . \n"
                      ,3,INDEX_DIR.'/_devel1010/log/'.date('Ymd').'mergeorderlogic.txt');
            error_log("\n --------------- \n".
                      " New myorderid : " .$MMO->myorderid ,3,INDEX_DIR.'/_devel1010/log/'.date('Ymd').'mergeorderlogic.txt');
        }elseif(count($oldmyorderids)==1){  //都是同, T 数量是否相同 ?
            $myorderid=current($oldmyorderids); 
            $MMO=OdOrder::model()->find('myorderid=?',$myorderid);
            $oldmtcount=OdEbayTransaction::model()->where("myorderid=? And platform='eBay'",array($myorderid))->getCount();
            
            // EO 的 T 与 IO 中的 T 的数量不同 -- 比 IO 少 , 并且 IO 未付款. 执行拆分 , 拆分判断仅 eBay订单
            if($oldmtcount>$mtcount && self::ifMergeOrder($MMO)&& ($ifMerge&2)){ 
                // 新的 MT  , 旧的 myorder 是哪些 MT ,  为新的 MT 生成新的 .
                $nMMO=self::ebayOrder2myorder_save($MEO,$MTs);
                // 原 myorder 重存一次
                $MMO->getCbyfCalculate();
                $MMO->recalculate();
                $MMO->save();
                // 记录拆分
                Ebay_Log_Myorder::Add(Ebay_Log_Myorder::TYPE_MERGE,$nMMO->myorderid,$nMMO->selleruserid,'拆分订单: ('.$MMO->myorderid.') 新订单: '. $nMMO->myorderid.' .'
                                      , $MMO->myorderid);
                error_log("\n  [ ".__LINE__." ] -- In One Myorder Split Order . \n"
                      ,3,INDEX_DIR.'/_devel1010/log/'.date('Ymd').'mergeorderlogic.txt');
                error_log(
                      " 拆分  New myorderid  ".$nMMO->myorderid." myorderid : " .$MMO->myorderid ,3,INDEX_DIR.'/_devel1010/log/'.date('Ymd').'mergeorderlogic.txt');
            }elseif($oldmtcount<$mtcount){ 
                if(self::ifMergeOrder($MMO,$error)){ 
                    $MMO=self::ebayOrder2myorder_save($MEO,$MTs,$MMO,'merge');
                error_log("\n  [ ".__LINE__." ] --  In One Myorder Add Ts. \n"
                      ,3,INDEX_DIR.'/_devel1010/log/'.date('Ymd').'mergeorderlogic.txt');
                }else{
                    if(count($MTnomyorderids)){
                        $MMO2=self::ebayOrder2myorder_save($MEO,$MTnomyorderids);
                        $MMO=self::ebayOrder2myorder_save($MEO,array_diff_key($MTs,$MTnomyorderids),$MMO);
                    }else{
                        $MMO=self::ebayOrder2myorder_save($MEO,$MTs,$MMO);
                    }
                error_log("\n  [ ".__LINE__." ] -- In One Myorder Not Add Ts ,Ts Make New Myorder \n"
                      ,3,INDEX_DIR.'/_devel1010/log/'.date('Ymd').'mergeorderlogic.txt');
                }
            }else{   // 简单更新
                if(count($MTnomyorderids)){
                    $MMO2=self::ebayOrder2myorder_save($MEO,$MTnomyorderids);
                    $MMO=self::ebayOrder2myorder_save($MEO,array_diff_key($MTs,$MTnomyorderids),$MMO);
                }else{
                    $MMO=self::ebayOrder2myorder_save($MEO,$MTs,$MMO);
                }
                error_log("\n  [ ".__LINE__." ]  --  In One Myorder Update Only \n"
                      ,3,INDEX_DIR.'/_devel1010/log/'.date('Ymd').'mergeorderlogic.txt');
            }
            error_log("\n --------------- \n".
                      "oldmtcount : $oldmtcount . mtcount : $mtcount".
                      " myorderid : " .$MMO->myorderid ,3,INDEX_DIR.'/_devel1010/log/'.date('Ymd').'mergeorderlogic.txt');
            
        }else{   // 多个 myorder ,  未付款订单 合并 ,找出最老的  , 已经付款 , 更新 每一个   
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
            $MMOs=OdOrder::model()->find('myorderid in (?)',$oldmyorderids)->order('created DESC')->getAll();
            $MO=null;
            foreach($MMOs as $MMO){
                if(isset($needsplitmyorderids[$MMO->myorderid])) continue;
                if(empty($MO)||$MO->isNewRecord||
                   $MMO->created < $MO->created ){
                    $MO=$MMO;
                }
            }
            //如果 都是有过合并其它订单的，只能新创订单
            if(is_null($MO)){
                
            }
            
            // 已经付款 
            if(self::ifMergeOrder($MMOs,$error) && ($ifMerge&1)){  // 合并
                $old_myorderids=$oldmyorderids;
                if($MO) unset($old_myorderids[$MO->myorderid]);
                $MO=self::ebayOrder2myorder_save($MEO,$MTs,$MO,'merge');
                Ebay_Log_Myorder::Add(Ebay_Log_Myorder::TYPE_MERGE,$MO->myorderid,$MO->selleruserid,'合并订单: ('.implode(',',$old_myorderids).') 新订单: '. $MO->myorderid.' .'
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
                error_log("\n  [ ".__LINE__." ] -- Many Myorder Merger.  \n"
                      ,3,INDEX_DIR.'/_devel1010/log/'.date('Ymd').'mergeorderlogic.txt');
                error_log("\n --------------- \n".
                      " myorderid : " .$MO->myorderid ."  old_myorderid : " .print_r($old_myorderid,1)  ,3,INDEX_DIR.'/_devel1010/log/'.date('Ymd').'mergeorderlogic.txt');
            }else{   // 简单更新 每一个
                error_log("\n  error: ".$error.'  '.print_r($MMO->status_payment,1).'   '.print_r(date('Ymd H:i:s',$MMO->updated),1) ."  \n"
                      ,3,INDEX_DIR.'/_devel1010/log/'.date('Ymd').'mergeorderlogic.txt');
                if(count($MTnomyorderids)){
                    $MMO2=self::ebayOrder2myorder_save($MEO,$MTnomyorderids);
                    error_log("\n  [ ".__LINE__." ]  --  Many Myorder Have no myorderid T. \n"
                      ,3,INDEX_DIR.'/_devel1010/log/'.date('Ymd').'mergeorderlogic.txt');
                }
                foreach($MMOs as $MMO){
                    $MMO=self::ebayOrder2myorder_save($MEO,$MTs,$MMO);
                }
                error_log("\n  [ ".__LINE__." ] -- Many Myorder update Only  \n"
                      ,3,INDEX_DIR.'/_devel1010/log/'.date('Ymd').'mergeorderlogic.txt');
                error_log("\n --------------- \n".
                      "  myorderid : " .$MMO->myorderid  ." . 简单更新每一个 " ,3,INDEX_DIR.'/_devel1010/log/'.date('Ymd').'mergeorderlogic.txt');
            }
        } 
        // return $MMOs;
    }
    
    /**
     *  @author lxqun 2011-07-05
     *  生成 Ibay 订单数组
     *  
     */
    static function ebayOrder2myorder_save(&$MEO,&$MTs,$MMO=null,$merge=false){
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
                $MMO=new Ebay_Myorders($AMMO);
                $isNew=true;
            }
        } 
        if($MMO->isNewRecord){
            $isNew=true;
        } 
        $AMMO+=array(
            'order_from'=>'ebay',
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
            //时间 
            'createddate'=>$MEO['createdtime'],
            'paidtime'=>$MEO['paidtime'],
            'shippedtime'=>$MEO['shippedtime'],
            'lastmodifiedtime'=>$MEO['lastmodifiedtime'],
            //地址信息
            'ship_name'=>$MEO['ship_name'],
            'ship_cityname'=>$MEO['ship_cityname'],
            'ship_stateorprovince'=>$MEO['ship_stateorprovince'],
            'ship_country'=>$MEO['ship_country'],
            'ship_countryname'=>$MEO['ship_countryname'],
            'ship_street1'=>$MEO['ship_street1'],
            'ship_postalcode'=>$MEO['ship_postalcode'],
            'ship_phone'=>$MEO['ship_phone'],
            'ship_email'=>$MEO['ship_email'],
            'addressid'=>$MEO['addressid'],
            'addressowner'=>$MEO['addressowner'],
        ); 
        Helper_Array::removeEmpty($AMMO);
        //不能 remove 的
        $AMMO+=array(
            'ship_street2'=>$MEO['ship_street2'],
        );
        $MMO->setAttributes($AMMO); 
        if(!($isNew||$merge)){
            $MMO->recalculate();
        }
        $MMO->save();
        //myorder _detail
        Ebay_Myorders_Detail::fromModelEbayOrders($MMO->myorderid,$MEO);
        
        if($isNew||$merge){
            foreach($MTs as $MT){
                $MT->myorderid=$MMO->myorderid;
                $MT->save();
            }
            //重取对象主要是包括下面的 transaction 
            $MMO=OdOrder::model()->find('myorderid=?',$MMO->myorderid);
            $MMO->getCbyfCalculate();
            $MMO->recalculate();
            $MMO->save();
        } 
        if($MEO->isDoPay){
            Ebay_Myorders::eventPayed($MMO);
        } 
        // 客户信息表
        self::saveCustomer($MEO['buyeruserid'],$MMO->selleruserid,$MEO,$MMO); 
        return $MMO;
    }

    /***
     * 保存 买家信息
     * 
     */
    static function saveCustomer($buyeruserid,$selleruserid,&$MEO,&$MMO){
        if($MMO->ecid&&$MEO->ecid){ // 跳过不必重复保存
            return true;
        }
        Queue_Getbuyeruser::Add($selleruserid,$buyeruserid);
        $MC=Ebay_Customers::find('buyeruserid=? And selleruserid=?',$buyeruserid,$selleruserid)->getOne();
        $C_v=array();
        if($MC->isNewRecord){
            $C_v=array(
                'uid'=>$MMO->uid,
                'selleruserid'=>$selleruserid,
                'buyeruserid'=>$buyeruserid,
                
            );
        }
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
     *  判断 是否可以 合并订单
     */
    static function ifMergeOrder($MMOs=null,&$error=null){
        if(!is_array($MMOs)){
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
            if(isset($MMO->is_send)&&$MMO->is_send==1){
                $error='已经发货';
                return false;
            }
            //2, 已经打印的
            if(isset($MMO->is_print)&&$MMO->is_print>0){
                $error='已经打印';
                return false;
            }
            //3, 已经发货的
            if($MMO->shipped || strlen($MMO->rn)){
                $error='已经发货';
                return false;
            }
            
        }
        return 1;
    }
    
    /**
     * 取出 过了30 天的 ， 不能处理的 订单
     * 或30天内,需要标为已经 发货的订单
     */
    static function getOutdaysOrders($eu){
        $selleruserid=$eu->selleruserid;
        $selleruserid=$eu->selleruserid;
        $outdaytimelineF= time()- 86400*29 ;
        $outdaytimelineE= time()- 86400*40 ;
        $outdaytimelineE2= time()- 86400*63 ;
        $responsedat=time()- 86400; // 每  5 小时更新
        $responsedat2=time()- 86400*2; // 每 24 小时更新
        set_time_limit(0);
         
        //$ebayorderids=Helper_Array::getCols(OdEbayOrder::model()->find("selleruserid=? And createdtime < ? And createdtime > ? And (orderstatus ='Active') And (responsedat is null OR  responsedat<? )  ",$selleruserid,$outdaytimelineF,$outdaytimelineE,$responsedat)
        //    ->setColumns('ebay_orderid')
        //    ->asArray()
        //    ->getAll(),'ebay_orderid');
        //
        //$ebayorderids2=Helper_Array::getCols(OdEbayOrder::model()->find("selleruserid=? And createdtime < ? And createdtime > ? And (orderstatus ='Active') And (responsedat is null OR  responsedat<? )  ",$selleruserid,$outdaytimelineE,$outdaytimelineE2,$responsedat2)
        //    ->setColumns('ebay_orderid')
        //    ->asArray()
        //    ->getAll(),'ebay_orderid');
         
        $ebayorderids3=Helper_Array::getCols(OdEbayOrder::model()->find("selleruserid=? And orderstatus is null And status_berequest is null",$selleruserid)
            ->setColumns('ebay_orderid')
            ->asArray()
            ->getAll(),'ebay_orderid');
        
        //$ebayorderids4=Helper_Array::getCols(OdEbayOrder::model()->find("selleruserid=? And status_berequest=1 ",$selleruserid)
        //    ->setColumns('ebay_orderid')
        //    ->asArray()
        //    ->getAll(),'ebay_orderid');
        //首次同步SoldReport,需要加入 同步 getOrder
        if(!System::getCooInf('unfirst_soldreport_'.$selleruserid)){
            $ebayorderids4=Helper_Array::getCols(OdEbayOrder::model()->find("selleruserid=? And (orderstatus is null Or orderstatus=?) And createdtime<?",$selleruserid,'Completed',$outdaytimelineE)
                ->setColumns('ebay_orderid')
                ->asArray()
                ->getAll(),'ebay_orderid');
            if(count($ebayorderids4))
                System::setCooInf('unfirst_soldreport_'.$selleruserid,1);
        }
        
        $ebayorderids=array_merge($ebayorderids3,$ebayorderids4);
        if(empty($ebayorderids)) return 0;
        // 加入队列 中 
        foreach($ebayorderids as $ebay_orderid){
            QueueGetorderHelper::Add($ebay_orderid,$selleruserid,$selleruserid);
        }
        // 使用 
        return $ebayorderids;
    }
    
        /*****
     * 
     * 找出所有 不在使用的 Ebay orders 
     * 标识为 废订单 .
     */
    static function foundDiscardEbayorders($selleruserid=null){
        $eorderids=array();
        set_time_limit(0);  
        $select=OdEbayOrder::model()->find("orderstatus is null And status_berequest is null And ebay_orderid like '%-%' ");
        if(strlen($selleruserid)){
            $select->where('selleruserid=?',$selleruserid);
        }  
        $MEOs=$select->setColumns(array('eorderid','ebay_orderid'))->getAll();     
        if(count($MEOs)) foreach($MEOs as $MEO){  
            list($itemid,$transactionid)=explode('-',$MEO->ebay_orderid);
            $t=OdEbayTransaction::model()->where('itemid=? And transactionid=?',array($itemid,$transactionid))->getOne();
            if($t->isNewRecord||empty($t->eorderid)){
                continue 1;
            }
            if($t->eorderid!=$MEO->eorderid){
                $eorderids[]=$MEO->eorderid;
            }
        }  
        if(count($eorderids)){  
            Ebay_Orders::meta()->updateWhere(array('status_berequest'=>5),'eorderid in (?)',$eorderids);
        }  
        return $eorderids;
    }
    
    /**
     * 判断 用户是否 有改名 .
     */
    static function checkifuseridchanged($eu,$selleruserid){
        
        $getUser=new EbayInterface_getUser();
        $getUser->eBayAuthToken=$eu->token;
        $RgetUser=$getUser->api();
        if ($getUser->responseIsFailure()){
            return 1;
        }
        if($RgetUser['User']['UserID']!=$selleruserid&&$RgetUser['User']['UserIDChanged']=='true'){
            $eb=Ebay_Bulkgetitem::find('jobtype="SoldReport" and is_recur=1 and selleruserid=?',$selleruserid)->getOne();
            
            $eb->is_recur=0;
            $eb->save();
            echo " selleruserid:".$eb->selleruserid." , Has changed to new ebay User : ".$RgetUser['User']['UserID']." . \n";
            return 0;
        }
        return 2;
    }
    
    /***
     * 组织 shippingaddress 数级
     * 为向 order 中的 shippingaddress 赋值
     * 
     */
    static function setShippingAddress($o){
        $n_shipping_address=array();
        $ship_countryname='';
        if(isset($o['ShipCountryName'])&&strlen($o['ShipCountryName'])){
            $ECounty=Ebay_Country::find('country=?',$o['ShipCountryName'])->getOne();
            $ship_countryname=$ECounty->chinese;
        }
        $n_shipping_address=array(
            // order 中的 地址信息
            'ship_email'=>$o['BuyerEmail'],
            'ship_phone'=>$o['BuyerPhone'],
            'ship_name'=>@$o['ShipRecipientName'],
            'ship_street1'=>@$o['ShipStreet1'],
            'ship_street2'=>@$o['ShipStreet2'],
            'ship_cityname'=>@$o['ShipCityName'],
            'ship_stateorprovince'=>@$o['ShipStateOrProvince'],
            'ship_postalcode'=>@$o['ShipPostalCode'],
            'ship_country'=>@$o['ShipCountryName'],
            'ship_countryname'=>$ship_countryname,
        );
        return  $n_shipping_address;
    }
}