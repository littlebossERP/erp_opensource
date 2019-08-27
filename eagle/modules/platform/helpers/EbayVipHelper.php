<?php
namespace eagle\modules\platform\helpers;

use eagle\models\SaasEbayVip;


class EbayVipHelper
{
    public static function helpCreate($viptype,$selleruserid,$ebay_uid,$puid){
        $vip=new SaasEbayVip();
        switch ($viptype) {
            case 'inventory':
                $vip->puid=$puid;
                $vip->ebay_uid=$ebay_uid;
                $vip->selleruserid=$selleruserid;
                $vip->vip_type='inventory';
                $vip->vip_rank=0;//默认等级0
                $vip->vip_status=1;
                $vip->valid_period=time()+10*365*24*3600;//有效期10年
                $vip->create_time=time();
                $vip->update_time=time();
                break;
            default:
                # code...
                break;
        }
        $vip->save(false);
    }


	// public static function helpList( $sort , $order )
	// {

	// }

	// public static function helpEdit($pk) {
 //        try{
 //        }catch (Exception $e) {
 //        }
	// }

	// public static function helpUpdate($post) {
 //        try{

 //        }catch (Exception $e) {
 //            // SysLogHelper::SysLog_Create("Platform",__CLASS__, __FUNCTION__,"","平台绑定错误日志： $e ", "trace");
 //            return $e;
 //        }
	// }

	// public static function helpDelete($keys) 
	// {
 //        try{

 //        }catch (\Exception $e) {
 //            return $e;
 //        }
	// }


}
