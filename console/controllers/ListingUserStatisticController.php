<?php

namespace console\controllers;

use yii\console\Controller;
use eagle\models\UserBase;
use eagle\models\BackgroundMonitor;
use eagle\models\OrderHistoryStatisticsData;
use console\helpers\OrderUserStatisticHelper;

/**
 * SqlExecution controller
 */



class ListingUserStatisticController extends Controller
{
    
    public function actionGetTotalListingNumberForAli(){
    
        $connection=\Yii::$app->db;
        $now = time();
        //查新订单
        //	$sql = 'select `id`,`sellerloginid`,`status`,`orderid`,`last_time`,`order_status`, `type` from  `queue_aliexpress_getorder` where `status` <> 1 and `type` = 3 AND `times` < 10  limit 100';
        $totalNum=0;
        $sql = "select distinct uid from saas_aliexpress_user";
        $dataReader = $connection->createCommand($sql)->query();
        echo date('Y-m-d H:i:s').' select count '.$dataReader->count().PHP_EOL;
        while( false !== ($row=$dataReader->read()) ) {
            $puid=$row['uid'];
            
    
            $subdbConn=\yii::$app->subdb;
            echo "puid:".$puid." Running ... \n";
    
            $number = $subdbConn->createCommand('select count(id) from aliexpress_listing')->queryScalar();
    
    
            $totalNum=$totalNum+$number;
            echo "puid:$puid number:$number totalNum:$totalNum ... \n";
        }
    
    
    }
    // ./yii listing-user-statistic/get-total-listing-number-for-ebay
    public function actionGetTotalListingNumberForEbay(){
        
        //获取数据
        $mainUsers = UserBase::find()->where(['puid'=>0])->asArray()->all();
        $totalNum=0;
        $activeTotalNum=0;
         
        foreach ($mainUsers as $mainUser){
            $puid=$mainUser["uid"];
             
            $subdbConn=\yii::$app->subdb;
            echo "puid:".$puid." Running ... \n";
            
            $number = $subdbConn->createCommand('select count(id) from ebay_item')->queryScalar();
            
            
            $totalNum=$totalNum+$number;
                        
            $number = $subdbConn->createCommand('select count(id) from ebay_item where listingstatus="Active"')->queryScalar();            
            $activeTotalNum=$activeTotalNum+$number;
       
            echo "puid:$puid number:$number totalNum:$totalNum activeTotalNum:$activeTotalNum ... \n";
        }
    
    
    }
    
    // ./yii listing-user-statistic/get-total-listing-number-for-wish
    public function actionGetTotalListingNumberForWish(){
    
        //获取数据
        $mainUsers = UserBase::find()->where(['puid'=>0])->asArray()->all();
        $totalNum=0;
        $activeTotalNum=0;
         
        foreach ($mainUsers as $mainUser){
            $puid=$mainUser["uid"];
             
    
            $subdbConn=\yii::$app->subdb;
            echo "puid:".$puid." Running ... \n";
    
            $number = $subdbConn->createCommand('select count(id) from wish_fanben_variance')->queryScalar();
    
    
            $totalNum=$totalNum+$number;
    
            $number = $subdbConn->createCommand('select count(id) from wish_fanben_variance  where enable="Y"')->queryScalar();
            $activeTotalNum=$activeTotalNum+$number;
             
            echo "puid:$puid number:$number totalNum:$totalNum activeTotalNum:$activeTotalNum ... \n";
        }
    
    
    }    
	
}
