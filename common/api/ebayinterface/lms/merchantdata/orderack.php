<?php
/**
 * Large Merchant Service
 * 为 merchantdata 下面的各接口 提供组织流程
 *  @author lxqun
 */
class EbayInterface_LMS_merchantdata_orderack extends EbayInterface_LMS_merchantdata_base
{
    /**
     * 需要上传的订单 
     *  @author lxqun
     * 
     */
    function uploadorders($selleruserid=0){
        echo "[".date('H:i:s').'-'.__LINE__."] selleruserid:".$selleruserid."\n";
        $eu=SaasEbayUser::model()->where('selleruserid =?',$selleruserid)->getOne();
        ibayPlatform::useDB($eu->uid,true);
        $this->eBayAuthToken=$eu->token;
        //error_log(print_r($this->eBayAuthToken,1)."\n",3,'/11.txt');
        
        set_time_limit(0);
        $Qorders=Queue_Orderackorders::find("selleruserid=?",$selleruserid)->getAll();
        $str='';
        if(count($Qorders)) foreach($Qorders as $Qorder){
            $nA=array(
                'OrderAckRequest xmlns="urn:ebay:apis:eBLBaseComponents"'=>
                array(
                    'OrderID'=>$Qorder->ebay_orderid,
                    'OrderLineItemID'=>$Qorder->itemid.'-'.$Qorder->transactionid,
                )
            );
            
            $str.=Helper_xml::simpleArr2xml($nA,0);
        }
        if(strlen($str)==0){
            return 0;
        }
        //创建上传任务
        $i=0;
        do{
            $createUploadJob=new EbayInterface_LMS_createUploadJob;
            $createUploadJob->eBayAuthToken=$this->eBayAuthToken;
            $uploadJobResponse=$createUploadJob->api(Helper_Util::getUuid() ,'OrderAck');
             //print_r($uploadJobResponse);
            
            //$uploadJobResponse['jobId'];
            //$uploadJobResponse['fileReferenceId'];
            
            if($i++>2){
            //    Queue_Orderackstatus::Add($selleruserid,$uploadJobResponse);
                echo "------------------------------------ selleruserid".$selleruserid;
                print_r($uploadJobResponse);
                echo 'Error Create Upload Job !';
            //    error_log("\n\n------------------------------------ selleruserid".$selleruserid ."\n".
            //        print_r($uploadJobResponse,1)."\n"
            //        ,3,INDEX_DIR.'/'.date('Ymd').'uploadOrderAck.txt');
                return 0;
            }else{
                if(isset($uploadJobResponse['errorMessage'])&&$uploadJobResponse['errorMessage']['error']['errorId']==7){
                    // 需要使用最后一次的 任务
                    Queue_Orderackstatus::useLastOne($selleruserid,$uploadJobResponse);
                }
            }
        }while($uploadJobResponse['ack']!='Success');
        $MEOACKS=Queue_Orderackstatus::Add($selleruserid,$uploadJobResponse);

        // 开始上传
        $uploadFile=new EbayInterface_LMS_uploadFile;
        $uploadFile->eBayAuthToken=$this->eBayAuthToken;
        $r=$uploadFile->api($uploadJobResponse['fileReferenceId'],$uploadJobResponse['jobId'] ,$this->buildBulkData($str) );

        if($r['ack']=='Success'){
            $MEOACKS->status=Queue_Orderackstatus::STATE_UPLOAD;
            $MEOACKS->save();
        }
        
        // 开始处理
        $startUploadJob=new EbayInterface_LMS_startUploadJob;
        $startUploadJob->eBayAuthToken=$this->eBayAuthToken;
        $r2=$startUploadJob->api($uploadJobResponse['jobId']);

        if($r2['ack']=='Success'){
            $MEOACKS->status=Queue_Orderackstatus::STATE_PROCESS;
            $MEOACKS->save();
        }
        // 状态
        $uploadJobStatus=new EbayInterface_LMS_getJobStatus;
        $uploadJobStatus->eBayAuthToken=$this->eBayAuthToken;
        $r3=$uploadJobStatus->api($uploadJobResponse['jobId']);
        if(isset($r3['jobProfile'])){
            $MEOACKS=Queue_Orderackstatus::uploadByJobProfile($selleruserid,$r3['jobProfile']);
        }
        
        return $r;
    }
    
    /**
     * 外部 cron 请求接口
     *  更新状态,及用来确认是否已经收到 orderack . 
     *  @param $multiprocessing   Array 控制多进程处理 
     */
    static function cronRequestJob($multiprocessing=null){
        set_time_limit(0);
        $orderAck=new EbayInterface_LMS_merchantdata_orderack;
        $select=Ebay_Bulkgetitem::find('jobtype="SoldReport" and is_recur=1');
        if(is_array($multiprocessing)&&$multiprocessing[0]>0&&isset($multiprocessing[1])){
            $multiprocessingTotal=(int)$multiprocessing[0];
            $multiprocessingNo=(int)$multiprocessing[1];
            $select->where("(id%$multiprocessingTotal)=$multiprocessingNo");
        }
        $ebs=$select->order('updated asc')->group('selleruserid')->getAll();
        foreach($ebs as $eb){
            self::requestJob($eb->selleruserid);
        }
        Queue_Orderackstatus::removeOutQueue();
        self::delProcessedXmlFile();
        Queue_Orderackorders::removeOutQueue();
    }
    /**
     * 外部cron 请求接口
     * 发送 orderack
     *  @param $multiprocessing   Array 控制多进程处理
     *  
     */
    static function cronUpdateOrderAck($multiprocessing=null){
        set_time_limit(0);
        $orderAck=new EbayInterface_LMS_merchantdata_orderack;
        $select=Ebay_Bulkgetitem::find('jobtype="SoldReport" and is_recur=1');
        if(is_array($multiprocessing)&&$multiprocessing[0]>0&&isset($multiprocessing[1])){
            $multiprocessingTotal=(int)$multiprocessing[0];
            $multiprocessingNo=(int)$multiprocessing[1];
            $select->where("(id%$multiprocessingTotal)=$multiprocessingNo");
        }
        $ebs=$select->order('updated asc')->group('selleruserid')->getAll();
        foreach($ebs as $eb){
            $r=$orderAck->uploadorders($eb->selleruserid);
        }
    }
    
    /**
     *
     * 请求 job 状态 及 下载文件
     * 对于 正在 处理中及 正在上传中的 状态的 , orderack ,去请求新的状态 .
     */
    static function requestJob($selleruserid){
        set_time_limit(0);
        $eu=SaasEbayUser::model()->where('selleruserid =?',$selleruserid)->getOne();
        ibayPlatform::useDB($eu->uid,true);
        // 更新 job 状态
        $uploadJobStatus=new EbayInterface_LMS_getJobStatus;
        $uploadJobStatus->eBayAuthToken=$eu->token;
        
        $MOACKs=Queue_Orderackstatus::find('selleruserid=? And (status=? Or status=?) ',$selleruserid,Queue_Orderackstatus::STATE_UPLOAD,Queue_Orderackstatus::STATE_PROCESS)->order('created DESC')->getAll();
        foreach($MOACKs as $MOACK){
            $r3=$uploadJobStatus->api($MOACK->jobid);
            if(isset($r3['jobProfile'])){
                $MOACK=Queue_Orderackstatus::uploadByJobProfile($selleruserid,$r3['jobProfile']);
            }else{
                var_dump($r3);
            }
        }
        
        // 下载
        $MOACKs=Queue_Orderackstatus::find('selleruserid=? And (status=? And filereferenceid is not null) ',$selleruserid,Queue_Orderackstatus::STATE_SUCCESS)->order('created DESC')->getAll();
        foreach($MOACKs as $MOACK){
            self::downloadFileSR($selleruserid,$MOACK->jobid,$MOACK->filereferenceid, $eu);
            echo "-- OrderAck downloaded . selleruserid:".$selleruserid." \n";
            // 处理
            $queue=Queue_Lmsprocess::find('jobtype="OrderAck" and (status=0 Or status=1) and selleruserid=?',$selleruserid)->getAll();
            if($queue) foreach($queue as $qp){
                if(strlen($qp->xmlfile)){
                    $xmlfilename=$qp->xmlfile;
                }else{
                    continue ;
                }
                $qp->setAttributes(array('status'=>1))->save();
                self::processOne($xmlfilename,$qp->selleruserid,$eu->uid);
                $qp->setAttributes(array('status'=>2))->save();
            }
            $MOACK->setAttributes(array('status'=>Queue_Orderackstatus::STATE_END))->save();
            echo date('Y-m-d H:i:s')."-- OrderAck Processed .  \n";
        }
    }
    
    static function processOne($filename,$selleruserid,$uid){
        set_time_limit(0);
        $xmlreader=new Helper_XmlLarge($filename);
        while($xmlreader->read('OrderAckResponse')){
            $o=Helper_xml::domxml2XA($xmlreader->expand());
            $o=$o['OrderAckResponse'];
            if($o['Ack']=='Success'){

                list($itemid,$transactionid)=explode('-',$o['OrderLineItemID']);
                $Q=Queue_Orderackorders::find("itemid=? And transactionid=?",$itemid,$transactionid)->getOne();
                if($Q->isNewRecord) continue 1;
                Ebay_Orders::meta()->updateWhere(array('status_berequest'=>4),'ebay_orderid=?',$Q->ebay_orderid);
                Queue_Orderackorders::meta()->deleteWhere("itemid=? And transactionid=?",$itemid,$transactionid);
            }
            //print_r($o);
        }
        Yii::app()->db->createCommand("OPTIMIZE TABLE ".Queue_Orderackorders::model()->tableName()." ;")->query();
    }
    //下载 xml
    static function downloadFileSR($selleruserid,$jobid,$filereferenceid,$eu){
        $jobtype='OrderAck';
        $filename=INDEX_DIR.'/link/xml/'.$eu->selleruserid.'/'.$jobtype.'/'.$jobid.'.zip';
        
        if (!file_exists($filename)){
            $dapi=new EbayInterface_LMS_downloadFile();
            $dapi->eBayAuthToken=$eu->token;
            $dr=$dapi->api($filereferenceid,$jobid,$filename);
            if (!$dr){
                continue;
            }
        }
        
        // Add to process queue 
        $dirname=dirname($filename);
        $xmlfilename=$dirname.'/'.$jobid.'_responses.xml';
        //self::processOne($xmlfilename,$eb->selleruserid,$eb->uid);
        Queue_Lmsprocess::Add($selleruserid,$jobid,$jobtype,$xmlfilename);
        return $xmlfilename;
    }
    
    /**
     * Delete xmlFiles  While  processed done
     * 删除一天前的 
     */
    static function delProcessedXmlFile(){
        $timeline=time()-86400; // 时间线
        $queue=Queue_Lmsprocess::find('jobtype="OrderAck" and status=2 And created <?',$timeline)->getAll();
        $c=0;
        if(count($queue)) foreach($queue as $qp){
            $xmlfilename=$qp->xmlfile;
            if(!file_exists($xmlfilename)) continue;
            $zipfilename=str_replace('_responses.xml','.zip',$xmlfilename);
            unlink($xmlfilename);
            unlink($zipfilename);
            $qp->setAttributes(array('status'=>3))->save();
            $c++;
        }
        // 删除 不再保留了的记录
        Queue_Lmsprocess::removeOutQueue();
        return $c;
    }
    /**
     * 
     */
    static function AbortNoOrderAck(){
        
    }
}