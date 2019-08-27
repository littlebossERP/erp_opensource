<?php
namespace eagle\modules\platform\helpers;

use Yii;
use eagle\modules\util\helpers\TranslateHelper;
use eagle\models\SaasCdiscountUser;
use eagle\modules\util\helpers\GetControlData;
use eagle\modules\order\helpers\CdiscountOrderHelper;
use eagle\modules\listing\helpers\CdiscountProxyConnectHelper;


/**
 * 判断是否允许查看cdiscount审核页面
 * @author dwg
 */
class PlatformHelper
{
    public static function cdApprovalAuthorized(){
        //$puidAuthorized=[297,4162];   //允许查看审核页面的puid
		$puidAuthorized=[4162];   //允许查看审核页面的puid
        $currentPuid = \Yii::$app->user->identity->getParentUid();  //当前的puid
        if (in_array($currentPuid, $puidAuthorized)) {
            return true;
        }
        else{
            return false;
        }

    }
}