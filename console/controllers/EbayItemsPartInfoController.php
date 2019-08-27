<?php

namespace console\controllers;

use yii\console\Controller;
use eagle\models\UserBase;
use eagle\models\BackgroundMonitor;
use eagle\modules\message\models\TicketSession;
use eagle\modules\message\models\TicketMessage;
use eagle\models\UserDatabase;
use eagle\modules\listing\apihelpers\EbayGetItemApiHelper;
use common\api\ebayinterface\shopping\getsingleitem;
use common\api\ebayinterface\getitem;
use eagle\models\SaasEbayAutosyncstatus;
use eagle\modules\listing\models\EbayItemDetail;
use eagle\modules\listing\models\EbayItem;
use eagle\models\SaasEbayUser;
use yii\helpers\ArrayHelper;
use eagle\models\db_queue2\EbayItemPhotoQueue;
use eagle\modules\order\helpers\EbayOrderHelper;
use common\api\ebayinterface\shopping\getmultipleitem;


class EbayItemsPartInfoController extends Controller {
    /**
     * [actionAutoGetmultitemPhotourl 通过getmultipleitem获取图片URL]
     * @Author willage 2016-11-25T14:41:35+0800
     * @Editor willage 2017-05-26T14:16:30+0800
     * command ./yii ebay-items-part-info/auto-getmultitem-photourl
     */
    public function actionAutoGetmultitemPhotourl(){
        $startRunTime=time();
        $seed = rand(0,99999);
        $cronJobId = "EBAY-".$seed."-GET-PHONEURL";
        EbayGetItemApiHelper::setCronJobId($cronJobId);
        echo __FUNCTION__." jobid=$cronJobId start"."\n";
        \Yii::info(__FUNCTION__." jobid=$cronJobId start","file");
        do{
            $rtn = self::_autoGetmultitemPhotourl();
            //如果没有需要handle的request了，sleep 10s后再试
            if ($rtn===false){
                echo __FUNCTION__." jobid=$cronJobId sleep10 \n";
                \Yii::info(__FUNCTION__." jobid=$cronJobId sleep10","file");
                sleep(10);
            }
        }while (time() < $startRunTime+3600);
        echo __FUNCTION__." jobid=$cronJobId end \n";
        \Yii::info(__FUNCTION__." jobid=$cronJobId end","file");
    }

    /**
     * [_autoGetmultitemPhotourl description]
     * @author willage 2017-05-26T14:16:30+0800
     * @update willage 2017-05-26T14:16:30+0800
     * @return [type]
     */
    public function _autoGetmultitemPhotourl(){
        $jobid=EbayGetItemApiHelper::getCronJobId();
        /**
         * No.1-清楚status为S的过期记录,
         */
        self::_clearRecords();
        /**
         * No.2-获取EbayItemPhotoQueue记录,
         */
        $queueArry=EbayItemPhotoQueue::find()
                    ->select(['itemid','id'])
                    ->where(['in','status',['P','R']])
                    ->andwhere('retry_count < 5')
                    ->limit(40)
                    ->orderBy('create_time DESC')//
                    ->asArray()
                    ->all();
        if (count($queueArry)==0) {//没有记录返回
            echo $jobid."-no record about EbayItemPhotoQueue\n";
            return false;
        }

        $qArry=self::_lockRecords($queueArry);

        // print_r($qArry,false);

        $IPQs=EbayItemPhotoQueue::find()
            ->where(['id'=>ArrayHelper::getColumn($qArry, 'id')])
            ->andwhere(['status'=>'S'])
            ->andwhere('retry_count < 5')
            ->orderBy('create_time DESC')
            ->all();
        $itemIdArry=ArrayHelper::getColumn($qArry,'itemid');
        print_r($itemIdArry,false);
        //No.2-拉取
        try{
            $getAPI = new getmultipleitem();
            $getAPI->includeSelector='Details,Variations';
            $result=$getAPI->apiItem($itemIdArry);
        }catch(\Exception $e){
            print_r($e);
            return false;
        }

        if ($getAPI->responseIsFail==true) {//失败则设置为R重试
            foreach ($IPQs as $IPQ) {
                $IPQ->status="R";
                $IPQ->retry_count++;
                $IPQ->update_time=date('Y-m-d H:i:s',time());
                $IPQ->save(false);
            }
            print_r($result); //查看失败原因
            return false;
        }
        // foreach ($result as $rval) {
        //     echo "result :";
        //     print_r($rval['ItemID']."\n",false);
        // }
        // echo count($IPQs)."\n";
        // foreach ($IPQs as $IPQ) {
        //     echo "IPQs :";
        //     print_r($IPQ->itemid."\n",false);
        // }

        //No.3-保存
        foreach ($IPQs as $IPQ) {
            echo $jobid."-"."start ".$IPQ->id." ".$IPQ->itemid."\n";
            $matchSts=false;//用于标记获取不到信息的itemID
            foreach ($result as $rval) {
                //对应ItemID,保存PictureURL
                if($IPQ->itemid == $rval['ItemID']){
                    $matchSts=true;
                    print_r($jobid."-".$rval['ItemID']."\n",false);
                    //默认使用item主图
                    $IPQ->photo_url=isset($rval['PictureURL'][0])?$rval['PictureURL'][0]:NULL;
                    //获取指定多属性值
                    $tmpAttri=json_decode($IPQ->product_attributes,true);
                    $arrAttri =array();
                    if (!empty($tmpAttri)) {
                        foreach ($tmpAttri as $keyAtt => $valAtt) {//变2维数组
                            $arrAttri =array_merge($arrAttri,$valAtt);
                        }
                    }
                    print_r($arrAttri,false);
                    //遍历是否使用多属性图片
                    if (isset($rval['Variations']['Pictures'][0]['VariationSpecificName']) &&(!empty($arrAttri))) {
                        $specName=$rval['Variations']['Pictures'][0]['VariationSpecificName'];
                        foreach ($arrAttri as $keyAttr => $valAttr) {
                             if ($specName==$keyAttr) {//匹配SpecificName
                                $specValArr=$rval['Variations']['Pictures'][0]['VariationSpecificPictureSet'];
                                foreach ($specValArr as $keyVal => $specVal) {
                                    if($specVal['VariationSpecificValue']==$valAttr){//匹配SpecificValue
                                        //如果有对应的多属性图片,使用多属性图片
                                        $IPQ->photo_url=isset($specVal['PictureURL'][0])?$specVal['PictureURL'][0]:NULL;
                                        print_r($jobid."-".$IPQ->photo_url,false);
                                    }
                                }
                            }
                        }
                    }
                    $IPQ->status="C";
                }
            }
            //更新队列IPQ数据
            if (empty($IPQ->photo_url) || ($matchSts==false)) {
                $IPQ->status="F";
                echo $jobid."-"."Fail status EbayItemPhotoQueue itemid ".$IPQ->itemid."\n";
            }else{
                $IPQ->success_time=date('Y-m-d H:i:s',time());
            }
            $IPQ->update_time=date('Y-m-d H:i:s',time());
            $IPQ->save(false);
            //更新后重读
            $newIPQ=EbayItemPhotoQueue::find()
                    ->where(['id'=>$IPQ->id])
                    ->andwhere('retry_count < 5')
                    ->one();
            //回调保存到orderitem
            if($IPQ->status=="C"){
                try{
                    list($success,$UpdateMSG) = EbayOrderHelper::updateEbayOrderItemPhotoUrl($newIPQ->itemid,$newIPQ->product_attributes,$newIPQ->puid,$newIPQ->photo_url);
                    echo $jobid."-"."\n v1.1 uid=".$newIPQ->puid." itemid=".$newIPQ->itemid." $UpdateMSG and attr=".print_r($newIPQ->product_attributes,true);
                    echo $jobid."-"."\n photoURL= ".$newIPQ->photo_url."\n";
                }catch(\Exception $e){
                    echo $jobid."-"."call back updateEbayOrderItemPhotoUrl error ".$e->getMessage()."\n";
                };
            }
            
        }
        return true;
    }//end function

    private static function _lockRecords($syncRecords){
        $cnt = 0;
        $recordArry=array();
        foreach ($syncRecords as $val) {
            if ($cnt>=20) {
                break;
            }
            $connection = \Yii::$app->db_queue2;
            $command = $connection->createCommand("UPDATE `ebay_item_photo_queue` SET `status`='S',`update_time`='".date('Y-m-d H:i:s',time())."' WHERE id =".$val['id']." AND `status`!='S'") ;
            $affectRows = $command->execute();
            if ($affectRows > 0) {
                $cnt++;
                $recordArry[]=$val;
            }
        }
        return $recordArry;
    }
    private static function _clearRecords(){
        $preOnehour=date('Y-m-d H:i:s',time()-2*3600);
        $connection = \Yii::$app->db_queue2;
        $command = $connection->createCommand("UPDATE `ebay_item_photo_queue` SET `status`='R',`update_time`='".date('Y-m-d H:i:s',time())."' WHERE `update_time`<'".$preOnehour."' AND `status`='S'") ;

        $affectRows = $command->execute();
        print_r("clear records: ".$affectRows);
    }


}//end class
?>