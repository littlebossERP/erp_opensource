<?php
namespace console\helpers;

use yii\helpers\ArrayHelper;
use eagle\models\SaasEbayAutosyncstatus;
use eagle\models\SaasEbayUser;
use eagle\modules\listing\models\EbayAutoTimerListing;
use common\api\ebayinterface\additem;

/**
 *----------------------------------------------------------------
 *<------------
 *|           |
 *|   3/4<----|
 *|     |     |
 *0---->----->1------>2(2:VERIFY完成并且等待ADDITEM操作)
 *     |              |
 *     <----20<------10<---
 *                   |    |
 *                  30--->
 *----------------------------------------------------------------
 *   //状态机
 *   const VERIFY_PENDING=0;
 *   const VERIFY_RUNNING=1;
 *   const VERIFY_EXECEPT=3;
 *   const VERIFY_NOITEM=4;
 *   const ADDITEM_PENDING=2;
 *   const ADDITEM_RUNNING=10;
 *   const ADDITEM_FINISH=20;
 *   const ADDITEM_EXECEPT=30;
 *----------------------------------------------------------------
 */
class EbayAutoTimerListingCsoleHelper{
    /**
     * [getEbayTimerListing description]
     * @auhor  willage 2017-04-04T09:42:47+0800
     * @update willage 2017-04-04T09:42:47+0800
     */
    public static function getEbayTimerListing($sellerId,$process_type){
        switch ($process_type) {
            case 'timer_listing':
                $recondS=EbayAutoTimerListing::find()
                    ->where(['status'=>1])
                    ->andwhere(['selleruserid'=>$sellerId])
                    ->andwhere('status_process IN (0,2,30)')
                    ->andwhere('runtime>:time1 and runtime<:time2',[':time1'=>time()-1800,':time2'=> time()+1800])
                    ->orderBy('runtime asc')
                    ->all();
                break;
            default:
                $recondS=NULL;
                break;
        }
        return $recondS;
    }
    /**
     * [goAddItems description]
     * @auhor  willage 2017-04-04T22:48:56+0800
     * @update willage 2017-04-04T22:48:56+0800
     */
    public static function goAddItems($timingRecords){
        try{
            foreach ($timingRecords as $tkey => $tval) {
             
            /**
             * 刊登
             */
                $aapi = new additem();
                $rsp = $aapi->apiFromDraft ( $tval->ebay_muban, $tval->ebay_muban->uid, $tval->ebay_muban->selleruserid, $tval->ebay_muban->detail->storecategoryid, $tval->ebay_muban->detail->storecategory2id, $tval );
            /**
             * 结果处理
             */
                if (isset ( $rsp['ItemID'] )) {
                    echo 'ItemID:'.$rsp['ItemID']."\n";
                    $tval->updated=time();
                    $tval->status_process=EbayAutoTimerListing::ADDITEM_FINISH;
                    $tval->itemid=$rsp['ItemID'];
                    $tval->listing_result=json_encode($rsp);
                    $tval->save(false);
                } else {
                    echo 'Failure'."\n";
                    $tval->updated=time();
                    $tval->status_process=EbayAutoTimerListing::ADDITEM_EXECEPT;
                    $tval->listing_result=json_encode($rsp);
                    $tval->save(false);
                }
            }
        }catch(Exception $e){
            echo $e->getMessage()."\n";
            return EbayAutoTimerListing::CODE_ERR_DB;
        }
        return EbayAutoTimerListing::CODE_SUCCESS;

    }

    public static function deleteOldRecord($selleruserid){
        //删除超过一个月记录
        $models=EbayAutoTimerListing::find()
            ->where(['selleruserid'=>$selleruserid])
            ->andwhere('runtime <'.(time()-2592000))
            ->all();
        if (!empty($models)) {
            $models->delete();
        }

    }
}//end class