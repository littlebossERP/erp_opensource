<?php
 
namespace console\controllers;
 
use yii\console\Controller;
use \eagle\modules\purchase\helpers\PurchaseHelper;
use \eagle\modules\util\helpers\ImageHelper;
use console\helpers\QueueGetorderHelper;
use console\helpers\SaasEbayAutosyncstatusHelper;
use yii\base\Exception;
use eagle\modules\order\models\OdEbayOrder;
use eagle\models\UserBase;
/**
 * Test controller
 */
class UserIpController extends Controller {
 
    public function actionAutoFill() {
    	
    	$result ['success'] = true;
    	$result ['message'] = '';
    	 
    	 
    	$userBaseAllArr = UserBase::find()->select('uid')
    	->where(' ipcn is null and last_login_ip is not null and last_login_ip != 0 ')->asArray()->all();
    	 
    	 
    	if(count($userBaseAllArr) > 0){
    		foreach ($userBaseAllArr as $userBaseAll){
    			$userBase=UserBase::find()
    			->where(' ipcn is null and last_login_ip is not null and last_login_ip != 0 and uid=:uid ',[':uid'=>$userBaseAll['uid']])->one();
    	
    			if ($userBase !== null){
    				if($userBase->last_login_ip != '127.0.0.1'){
    					echo "ip:".$userBase->last_login_ip." \n";
    					echo "before http://ip.taobao.com \n";
    					$json=file_get_contents('http://ip.taobao.com/service/getIpInfo.php?ip='.$userBase->last_login_ip);
    					echo "after http://ip.taobao.com \n";
    	
    					$arr=json_decode($json);    	
    					$userBase->ipcn=$arr->data->country.$arr->data->region.$arr->data->city;    	
    					$userBase->save(false);
    					sleep(5);
    				}
    			}
    		}
    	}
    	 
  
    }
 
}
