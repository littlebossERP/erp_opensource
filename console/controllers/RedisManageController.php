<?php
 
namespace console\controllers;
 
use yii\console\Controller;
use \eagle\modules\purchase\helpers\PurchaseHelper;
use \eagle\modules\tracking\helpers\TrackingHelper;
use \eagle\modules\util\helpers\ImageHelper;
use eagle\modules\amazon\apihelpers\SaasAmazonAutoSyncApiHelper;
use eagle\modules\amazon\apihelpers\AmazonSyncFetchOrderApiHelper;
use eagle\modules\util\models\GlobalConfigData;
/**
 * Test controller
 */
class RedisManageController extends Controller {

    /**
     * 表ut_global_config_data 读取到redis
     * ./yii redis-manage/globalconfig-fromdb 
     */
    public function actionGlobalconfigFromdb(){
        
        $globalConfigAll=GlobalConfigData::find()->asArray()->all();
     //   print_r($globalConfigAll);
        
        foreach($globalConfigAll as $globalConfig){
            \Yii::$app->redis->hset('global_config',$globalConfig["path"],$globalConfig["value"]);
        }
        
        //\Yii::$app->redis->hset('global_config','user_2_tracker','{sfsdfsdfdsf}');
    }
    
    //./yii redis-manage/test-get-config 
    public function actionTestGetConfig(){
        //$value=\Yii::$app->redis->hget('global_config','sdff');
        // $value 为null ，找不到时候
        
    //    Tracking/subQueueVersion
    /*   $configMap=array();  
       $pathValuesArr=\Yii::$app->redis->hgetall('global_config');
       $configNum=count($pathValuesArr)/2;
       for($i=0;$i<$configNum;$i++){           
           $configMap[$pathValuesArr[$i*2]]=$pathValuesArr[$i*2+1];
       }
       
       
       print_r($configMap);*/
        
        \Yii::$app->redis->hset('global_config',"Order/aliexpressGetOrderListVersion","v90");
        
      
       
       
     
       
       
    }


    function actionTestRedisIO(){
      do{
        \Yii::$app->redis->set('test',1);
        \Yii::$app->redis->get('test');

      }while(1);
    }
    
    
    
}