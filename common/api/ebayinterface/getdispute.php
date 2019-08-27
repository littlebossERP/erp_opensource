<?php

namespace common\api\ebayinterface;

use common\api\ebayinterface\base;
use \Exception;
use eagle\modules\message\helpers\ResolutionEbayHelper;

/**
 * 获得纠纷列表
 * @package interface.ebay.tradingapi
 */
class getdispute extends base{
	public $verb='GetDispute';
	public function api($DisputeID){
		$r=$this->setRequestBody(array('DisputeID'=>$DisputeID))->sendRequest();
		return $r;
	}
	
	static function getDisputeOne($DisputeID,$eu){
		$api=new self();
		$api->eBayAuthToken=$eu->token;
		$responseArr=$api->api($DisputeID);
		
		if($api->responseIsFailure()){  //接口失败 退出
			return 1;
		}
		ResolutionEbayHelper::ebayGetDisputeApiSave($responseArr['Dispute'],$eu->selleruserid);
	}
	
	/**
	 * 请求并 保存数据 
	 * @param unknown_type $DisputeID
	 * @param unknown_type $eu
	 * @author lxqun
	 * @date 2014-4-13
	 */
	static function cronRequestOne($DisputeID,$eu){
	    $api=new self();
        $api->eBayAuthToken=$eu->token;
        $responseArr=$api->api($DisputeID);
        if($api->responseIsFailure()){  //接口失败 退出
            return 1;
        }
        ResolutionEbayHelper::apiSave($responseArr['Dispute'],$eu->selleruserid);
	}
}
