<?php
/**
 * Large Merchant Service
 *
 */
class EbayInterface_LMS_getJobs extends EbayInterface_LMS_base_bulkdata   
{
	public $verb='getJobs';
	public $service_name='BulkDataExchangeService';
	function __construct(){
		parent::__construct($this->service_name);
	}
	function api($jobType,$jobStatus='',$creationTimeTo='',$creationTimeFrom=''){
		$xmlArr=array(
			'jobType'=>$jobType,
		);
        if(strlen($jobStatus)) $xmlArr['jobStatus']=$jobStatus;
        if(strlen($creationTimeTo)) $xmlArr['creationTimeTo']=$creationTimeTo;
        if(strlen($creationTimeFrom)) $xmlArr['creationTimeFrom']=$creationTimeFrom;
        
		$response=$this->setRequestBody($xmlArr)->sendRequest();
		return $response;
	}
	
	/**
	 * 取得 还在 created 状态中的 orderack job
	 */
	static function getCreatedOrderAck($token){
		$api=new self;
		$api->eBayAuthToken=$token;
		$r=$api->api('OrderAck','Created');
		if(isset($r['jobProfile'])){
			if(isset($r['jobProfile'][0])){
				return array_pop($r['jobProfile']);
			}else{
				return $r['jobProfile'];
			}
		}
		return null;
	}
}
