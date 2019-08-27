<?php
namespace eagle\modules\amazon\apihelpers;

use \Yii;
use eagle\models\SaasAmazonUserMarketplace;
use eagle\models\SaasAmazonUser;
/**
* 
*/
class AmazonUserApiHelp{
    /**
     * [getAccountMaketplaceMap 获取用户名、站点数组]
     * @Author willage 2016-12-02T18:07:42+0800
     * @Editor willage 2016-12-02T18:07:42+0800
     * @return ['merchant_id']['marketplace_id']
     */
    public static function getAccountMaketplaceMap(){
        $uid = \Yii::$app->subdb->getCurrentPuid();
        $AmzAccount = SaasAmazonUser::find()//提取amazon用户
                                ->select("amazon_uid,merchant_id,store_name")
                                ->where(["is_active"=>1])
                                ->andwhere(["uid"=>$uid])
                                ->asArray()
                                ->all();
        if (empty($AmzAccount)) return array();
        $temp = array_column($AmzAccount,'amazon_uid');
        $AmzUserMp =SaasAmazonUserMarketplace::find()//提取用户站点
                                ->select("amazon_uid,marketplace_id")
                                ->where(["amazon_uid"=>$temp])
                                ->asArray()
                                ->all();
        // print_r($AmzUserMp,false);
        // print_r($AmzAccount,false);
        //组织数据,['merchant_id']['marketplace_id']
        foreach ($AmzAccount as $keyAcc => $valAcc) {//变2维数组
                $tmpMer[$valAcc['amazon_uid']]=$valAcc['merchant_id'];
        }
        foreach ($AmzAccount as $keyAcc => $valAcc) {//变2维数组
                $tmpStore[$valAcc['merchant_id']]=$valAcc['store_name'];
        }
        $tmpAccMp=array();
        foreach ($AmzUserMp as $keyMp => $valMp) {//组织成用户名、站点
            $enName=AmazonApiHelper::$AMAZON_MARKETPLACE_REGION_CONFIG[$valMp['marketplace_id']];
            $cnName=AmazonApiHelper::$COUNTRYCODE_NAME_MAP[$enName];
            $tmpAccMp[$tmpMer[$valMp['amazon_uid']]]["marketplace"][]=array(
                "marketplace_id"=>$valMp['marketplace_id'],
                "en_name"=>$enName,
                "cn_name"=>$cnName);
        }
        $amzAccMp=array();
        foreach ($tmpAccMp as $key => $value) {
            $amzAccMp[]=array(
                "merchant"=>array(
                                        "merchant_id"=>$key,
                                        "store_name"=>$tmpStore[$key]),
                "marketplace"=>$value['marketplace']);
        }

        // print_r($amzAccMp,false);
        return $amzAccMp;
    }




}//end class





?>
