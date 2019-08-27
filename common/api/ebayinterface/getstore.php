<?php
namespace common\api\ebayinterface;

use common\api\ebayinterface\base;
use eagle\models\SaasEbayUser;
use eagle\models\EbayStoreInfo;
/**
 * 获得eBay帐号的店铺信息
 * @package interface.ebay.tradingapi
 */
class getstore extends base{
    public function api($userid){
        $this->verb = 'GetStore';
        $xmlArr=array(
            'RequesterCredentials'=>array(
				'eBayAuthToken'=>$this->eBayAuthToken,
			),
            'ErrorLanguage'=>'zh_CN',
            // 'CategoryStructureOnly'=>'True',
            // 'LevelLimit'=>3,
//             'RootCategoryID'=>'0',
			'UserID'=>$userid,
		);
		$result=$this->setRequestBody($xmlArr)->sendRequest();
// 		var_dump($result);
		if($result['Ack']=='Success'){
			return $result;
		}else{
			return $result;
			//return false;
		}
	}

	function save($selleruserid,$infoData){
        if(empty($infoData)) return false;
        $Sinfo=EbayStoreInfo::find()->where(['selleruserid'=>$selleruserid])->one();
        if (empty($Sinfo)){
            $Sinfo=new EbayStoreInfo();
        }
        $Sinfo_v=array(
            'selleruserid' => $selleruserid,
            'store_name' => $infoData["Name"],
            'store_url_path' => $infoData["URLPath"],
            'store_url' => $infoData["URL"],
            'store_logo_url' => @$infoData["Logo"]["URL"],
        );
        print_r($Sinfo_v,false);
        if($Sinfo->isNewRecord){
            $Ebay_User=SaasEbayUser::find()->where(['selleruserid'=>$selleruserid])->one();
            if (!empty($Ebay_User)){
                $Sinfo_v['uid']=$Ebay_User->uid;
            }elseif (!is_null($uid)){
                $Sinfo_v['uid']=$uid;
            }else {
                return false;
            }
        }
        // EbayUserInfo::removeInvalid($C_v);
        $Sinfo->setAttributes($Sinfo_v);
        $Sinfo->save(false);
        return true;
    }



}//end class
?>