<?php

namespace common\api\ebayinterface\resolution;

use common\api\ebayinterface\resolution\base;
use \Exception;
use eagle\modules\message\helpers\ResolutionEbayHelper;

class getebpcasedetail extends base {
    //从ebay获取case详情
    public $verb='getEBPCaseDetail';
    public function api($id,$type){
       	$xmlArr=array(
    		'caseId'=>array(
       			'id'=>$id,
       			'type'=>$type,
       		)
       	);
		return $this->setRequestBody($xmlArr)->sendRequest();
	}
	
	/**
	 +----------------------------------------------------------
	 * 请求一条 case 
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		hqw 	2015/11/18				初始化
	 +----------------------------------------------------------
	 * @param $caseid
	 * @param $casetype
	 * @param $eu
	 * 
	 **/
	public static function getEbpCaseDetailOne($caseid,$casetype,$eu){
		$EBPD=new self();
		$EBPD->eBayAuthToken=$eu->token;
		$ebpresult=$EBPD->api($caseid,$casetype);
		if ($ebpresult['ack']=='Success'||$ebpresult['ack']=='Warning'){
			ResolutionEbayHelper::ebayUserCaseEbpdetailApiSave($caseid,$ebpresult['caseDetail'],$eu);
		
		}
	}
	
	/**
	 * 请求一条 case 
	 * @param int $caseid
	 * @param str $casetype
	 * @param   $eu
	 * @author lxqun
	 * @date 2014-3-23
	 */
	public function cronRequestOne($caseid,$casetype,$eu){
	    $EBPD=new self();
	    $EBPD->eBayAuthToken=$eu->token;
	    $ebpresult=$EBPD->api($caseid,$casetype);
	    if ($ebpresult['ack']=='Success'||$ebpresult['ack']=='Warning'){
	        CmEbayUsercaseEbpdetailHelper::apiSave($caseid,$ebpresult['caseDetail'],$eu );
	         
	    }
	}

}
?>