<?php
/**
 * 获得用户信息
 * @package interface.ebay.tradingapi
 */
class EbayInterface_getUser extends EbayInterface_base{
    public $before_request_xmlarray=array();
    public function api(){
        $this->verb = 'GetUser';
        $xmlArr=array(
            
        );
        $xmlArr+=$this->before_request_xmlarray;
        return $this->setRequestBody($xmlArr)->sendRequest();
    }
    /**
     * UserID
     * 
     */
    public function getBuyer($userid,$itemid=null){
        $this->before_request_xmlarray=array(
            'DetailLevel'=>'ReturnSummary',
            'UserID'=>$userid,
            'ItemID'=>$itemid,
            
        );
        Helper_Array::removeEmpty($this->before_request_xmlarray);
        return $this->api();
    }
    
    static function cronQueueBuyerFeedbackScore(){
        $betimeline=time()-600;
        //$sql="select from queue_getorder 
        $sql_where="where type=0 and created <= $betimeline" ;
        $qs=Queue_Getbuyeruser::find('type=0 and created <=?',$betimeline)->limit(0,200)->getAll();
        $queue_selleruserid=array();
        foreach($qs as $rs ){
            $eu=SaasEbayUser::model()->where('selleruserid=?',$rs['selleruserid'])->getOne();
            ibayPlatform::useDB($eu->uid,true);
            $getUser=new EbayInterface_getUser();
            
            $getUser->eBayAuthToken=$eu->token;
            $r=$getUser->getBuyer($rs['buyeruserid']);
            if (!$getUser->responseIsFailure()){
                if(isset($r['User'])){
                    self::saveBuyeruser($rs['selleruserid'],$r['User']);
                }
            }
            Queue_Getbuyeruser::meta()->deleteWhere('qid=?',$rs['qid']);

        }
        Yii::app()->db->createCommand("OPTIMIZE TABLE ".Queue_Getbuyeruser::model()->tableName()." ;")->query();
        
    }
    static function saveBuyeruser($selleruserid,$t_buyer){
        if(empty($t_buyer['UserID'])) return false;
        $C=Ebay_Customers::find('buyeruserid=? And selleruserid=?',$t_buyer['UserID'],$selleruserid)->getOne();
        $C_v=array(
            'selleruserid'=>$selleruserid,
            'buyeruserid'=>$t_buyer['UserID'],
            'eiastoken'=>$t_buyer['EIASToken'],
            'email'=>$t_buyer['Email'],
            'feedbackscore'=>$t_buyer['FeedbackScore'],
            'positivefeedbackpercent'=>$t_buyer['PositiveFeedbackPercent'],
            'registrationdate'=>strtotime($t_buyer['RegistrationDate']),
            'feedbackratingstar'=>$t_buyer['FeedbackRatingStar'],
            'site'=>$t_buyer['Site'],
            'status'=>$t_buyer['Status'],
            'useridlastchanged'=>$t_buyer['UserIDLastChanged'],
            'vatstatus'=>$t_buyer['VATStatus'],
        );
        if($C->isNewRecord){
            $uid=User::getParentUidBySeller($selleruserid);
            $C_v['uid']=$uid;
        }
        Ebay_Customers::removeInvalid($C_v);
        $C->setAttributes($C_v);
        $C->save();
        return true;
    }
    
    
}
