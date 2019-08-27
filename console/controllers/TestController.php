<?php
 
namespace console\controllers;
 
//use console\components\Controller;
use yii;
use yii\console\Controller;

/**
 * Test controller
 */
class TestController extends Controller {
    
    // ./yii test/index
    public function actionIndex(){
        $aa = \eagle\models\SaasAmazonUser::find()->asArray()->all();
        print_r($aa);
    }
    
    /****************************** 后台job优雅退出版本控制  ***************************************/    
    // ./yii test/refresh-lazada-version
    public function actionRefreshDhgateVersion(){
        
        $dhgateGetOrderVersionFromConfig = \eagle\modules\util\helpers\ConfigHelper::getGlobalConfig("Order/dhgateGetOrderVersion",'NO_CACHE');
        var_dump($dhgateGetOrderVersionFromConfig);
        
        \eagle\modules\util\helpers\ConfigHelper::setGlobalConfig("Order/dhgateGetOrderVersion", time());
        
        $dhgateGetOrderVersionFromConfig = \eagle\modules\util\helpers\ConfigHelper::getGlobalConfig("Order/dhgateGetOrderVersion",'NO_CACHE');
        var_dump($dhgateGetOrderVersionFromConfig);
    }
    
    // ./yii test/refresh-lazada-version
    public function actionRefreshLazadaVersion(){
        $lazadaGetOrderVersionFromConfig = \eagle\modules\util\helpers\ConfigHelper::getGlobalConfig("Order/lazadaGetOrderVersion",'NO_CACHE');
        var_dump($lazadaGetOrderVersionFromConfig);
        \eagle\modules\util\helpers\ConfigHelper::setGlobalConfig("Order/lazadaGetOrderVersion", time());
        $lazadaGetOrderVersionFromConfig = \eagle\modules\util\helpers\ConfigHelper::getGlobalConfig("Order/lazadaGetOrderVersion",'NO_CACHE');
        var_dump($lazadaGetOrderVersionFromConfig);
    }
    
    
    // ./yii test/refresh-amazon-list-version
    public function actionRefreshAmazonListVersion(){
        $amzGetOrderVersionFromConfig = \eagle\modules\util\helpers\ConfigHelper::getGlobalConfig("Order/amazonGetOrderListVersion",'NO_CACHE');
        var_dump($amzGetOrderVersionFromConfig);
        \eagle\modules\util\helpers\ConfigHelper::setGlobalConfig("Order/amazonGetOrderListVersion", time());
        $amzGetOrderVersionFromConfig = \eagle\modules\util\helpers\ConfigHelper::getGlobalConfig("Order/amazonGetOrderListVersion",'NO_CACHE');
        var_dump($amzGetOrderVersionFromConfig);
        
    }
    
    // ./yii test/refresh-amazon-item-version
    public function actionRefreshAmazonItemVersion(){
        $amzGetOrderVersionFromConfig = \eagle\modules\util\helpers\ConfigHelper::getGlobalConfig("Order/amazonGetOrderDetailVersion",'NO_CACHE');
        var_dump($amzGetOrderVersionFromConfig);
        \eagle\modules\util\helpers\ConfigHelper::setGlobalConfig("Order/amazonGetOrderDetailVersion", time());
        $amzGetOrderVersionFromConfig = \eagle\modules\util\helpers\ConfigHelper::getGlobalConfig("Order/amazonGetOrderDetailVersion",'NO_CACHE');
        var_dump($amzGetOrderVersionFromConfig);
    }
    
    
    // ./yii test/refresh-html-catch-data-queue-version
    public function actionRefreshHtmlCatchDataQueueVersion(){
        $HtmlCatchDataQueueVersionFromConfig = \eagle\modules\util\helpers\ConfigHelper::getGlobalConfig("htmlcatcher/HtmlCatchDataQueueVersion",'NO_CACHE');
        var_dump($HtmlCatchDataQueueVersionFromConfig);
        \eagle\modules\util\helpers\ConfigHelper::setGlobalConfig("htmlcatcher/HtmlCatchDataQueueVersion", time());
        $HtmlCatchDataQueueVersionFromConfig = \eagle\modules\util\helpers\ConfigHelper::getGlobalConfig("htmlcatcher/HtmlCatchDataQueueVersion",'NO_CACHE');
        var_dump($HtmlCatchDataQueueVersionFromConfig);
    }
    
    
    
    
    
    
    /*********************************************************************/
    
    
    
    
    
    // 测试cd跟卖设置的邮箱通知功能是否正常
    // ./yii test/send-mail
    public function actionSendMail(){
        $puid = 1;
        $email = \eagle\modules\util\helpers\RedisHelper::RedisGet('user_valid_mail_address', 'cd_terminator_uid_'.$puid);
        $date = \eagle\modules\util\helpers\TimeUtil::getNow();
        $data = [];
        $data['send_to']=$email;
        $data['send_from']='service@littleboss.com';
        $data['puid']=$puid;
        $data['act_name']='CD跟卖终结者BestSeller被抢提醒：'.$date;
        $data['subject']='测试邮件：小老板Cdiscount终结者-被跟卖BestSeller被抢提醒 -'.$date;
        $data['body']="测试邮件";
        $data['from_name']='小老板通知邮件';
        $data['priority']=2;
        $Mdata[] = $data;
        //异步发送
        $rtn = \eagle\modules\message\apihelpers\MessageApiHelper::insertEdmQueue($Mdata);
        print_r($rtn);
    }
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
}
