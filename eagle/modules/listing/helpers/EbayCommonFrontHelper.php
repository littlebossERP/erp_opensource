<?php
namespace eagle\modules\listing\helpers;
use yii;
use yii\base\Exception;
use yii\data\Pagination;
// use common\helpers\Helper_Array;
use yii\helpers\ArrayHelper;
use eagle\models\SaasEbayUser;
use eagle\modules\listing\models\EbayItem;
use eagle\modules\listing\models\EbayItemDetail;
use eagle\modules\listing\models\EbayAutoInventory;
use eagle\modules\listing\models\EbayAutoTimerListing;
use eagle\models\SaasEbayVip;
use eagle\models\SaasEbayAutosyncstatus;

class EbayCommonFrontHelper{
    function __construct(){
    }
    /**
     * [activeUser 获取有效的ebay selleruserid]
     * @author willage 2017-04-06T14:32:15+0800
     * @update willage 2017-04-06T14:32:15+0800
     */
    public static function activeUser($puid=0){
    	try{
	    	//只显示有权限的账号，lrq20170828
    		$selleruserids = array();
	    	$account_data = \eagle\modules\platform\apihelpers\PlatformAccountApi::getPlatformAuthorizeAccounts('ebay');
	    	foreach($account_data as $key => $val){
	    		$selleruserids[] = $key;
	    	}
    	}
    	catch(\Exception $ex){
    		
    	}
    	
        $userVaildArry = SaasEbayUser::find()
                        ->where('uid = '.$puid)
                        ->andwhere('listing_status = 1')
                        ->andwhere('listing_expiration_time > '.time())
                        ->andwhere(['selleruserid' => $selleruserids])
                        ->select('selleruserid')
                        ->asArray()
                        ->all();

        return $userVaildArry;
    }

    /**
     * [_switchSaasStatus description]
     * @author willage 2017-03-31T09:17:49+0800
     * @update willage 2017-03-31T09:17:49+0800
     * 凡是EbayAutoInventory对应sellerid有记录(开启),
     * 则SaasEbayAutosyncstatus保持next_execute_time为当前时间,
     * 否则SaasEbayAutosyncstatus保持next_execute_time=NULL,
     */
    public static function _switchSaasStatus($sellerid,$type){
        switch ($type) {
            case 'inventory':
                $isOpen=EbayAutoInventory::find()
                    ->where(['selleruserid'=>$sellerid])
                    ->andwhere(['status'=>1])
                    ->count();

                $autoSyncS=SaasEbayAutosyncstatus::find()
                                ->where(['selleruserid'=>$sellerid])
                                ->andwhere(['in','type',[9,10]])
                                ->all();
                if (empty($autoSyncS)) {
                    \yii::info("_switchSaasStatus error","file");
                    return false;
                }
                break;
            case 'timer_listing':
                $isOpen=EbayAutoTimerListing::find()
                    ->where(['selleruserid'=>$sellerid])
                    ->andwhere(['status'=>1])
                    ->count();

                $autoSyncS=SaasEbayAutosyncstatus::find()
                                ->where(['selleruserid'=>$sellerid])
                                ->andwhere(['type'=>11])
                                ->all();
                if (empty($autoSyncS)) {
                    \yii::info("_switchSaasStatus error","file");
                    return false;
                }
                break;
            default:
                # code...
                break;
        }

        foreach ($autoSyncS as $key => $val) {
            if ($isOpen) {//有记录,开启
                if(is_null($val->next_execute_time)){//如果之前已经开启,则不变
                    $val->next_execute_time=time();
                    $val->updated=time();
                    $val->save(false);
                }
            }else{//无记录,关闭
                if(!is_null($val->next_execute_time)){//如果之前已经是NULL,则不变
                    $val->next_execute_time=NULL;
                    $val->updated=time();
                    $val->save(false);
                }
            }
        }
        return true;
    }

    /**
     * [authorCheck 检查店铺的权限]
     * @author willage 2017-03-23T16:48:16+0800
     * @update willage 2017-03-23T16:48:16+0800
     */
    public static function _authorCheck($type,$sellerid,$puid){
        switch ($type) {
            case 'inventory':
                $vip_type='inventory';
                break;
            case 'timer_listing':
                $vip_type='timer_listing';
                break;
            default:
                # code...
                break;
        }
        $vip=0;
        $isVip=SaasEbayVip::find()
                ->where(['selleruserid'=>$sellerid])
                ->andwhere(['vip_type'=>$vip_type])
                ->andwhere(['vip_status'=>1])
                ->count();

        if (!$isVip) {//非VIP
            $vip=0;
            return $vip;
        }
        return $vip;
    }

    /**
     * [_limitCheck 检测店铺的数量限制]
     * @author willage 2017-04-06T16:29:59+0800
     * @update willage 2017-04-06T16:29:59+0800
     */
    public static function _limitCheck($type,$sellerid,$puid,$appCnt){
        /*
         * No.1-权限检测
         */
        $vip=self::_authorCheck($type,$sellerid,$puid);
        /*
         * No.2-数量计算
         */
        $limit=SaasEbayVip::$vipTypeRank[$type][$vip];
        switch ($type) {
            case 'inventory':
                $onlineCnt=EbayAutoInventory::find()
                    ->where(['selleruserid'=>$sellerid])
                    ->andwhere(['status'=>1])
                    ->count();
                break;
            case 'timer_listing':
                $onlineCnt=EbayAutoTimerListing::find()
                    ->where(['selleruserid'=>$sellerid])
                    ->andwhere(['status'=>1])
                    ->count();
                break;
            default:
                # code...
                break;
        }

        $applyCnt=$appCnt;//count($params['inventory']);
        if ($onlineCnt+$applyCnt > $limit) {//在线数量+请求数量 > 限制数量
            return false;
        }

        return true;

    }

    public static function paramsCheck($params,$type,$sellerid,$puid,$appCnt){
        $ret=true;
        switch ($type) {
            case 'inventory':
                # code...
                break;
            case 'timer_listing':
                //时间检查
                $timeStr=$params['EbayAutoTimerListing']['set_date'].' '.$params['EbayAutoTimerListing']['set_hour'].':'.$params['EbayAutoTimerListing']['set_min'].':'.'00';
                $nowTime=time();
                $datetime = new \DateTime($timeStr,new \DateTimeZone('Etc/GMT'.$params['EbayAutoTimerListing']['set_gmt']));
                $setTime=$datetime->getTimestamp();
                if ($setTime<$nowTime) {
                    return [false,"时间不正确"];
                }
                //数量检查
                if ($params['EbayAutoTimerListing']['status']) {//暂停状态,不做数量限制检查
                    if (!EbayCommonFrontHelper::_limitCheck($type,$sellerid,$puid,$appCnt)) {
                        return [false,"定时刊登超限"];
                    };
                }

                break;
            default:
                # code...
                break;
        }
        return [$ret,"params is OK !"];

    }
}//end class