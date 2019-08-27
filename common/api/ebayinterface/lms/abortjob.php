<?php
/**
 * Large Merchant Service
 *
 */
class EbayInterface_LMS_abortJob extends EbayInterface_LMS_base_bulkdata   
{
	public $verb='abortJob';
	public $service_name='BulkDataExchangeService';
	function __construct(){
		parent::__construct($this->service_name);
	}
	function api($jobid){
		$xmlArr=array(
			'jobId'=>$jobid,
		);
		$response=$this->setRequestBody($xmlArr)->sendRequest(1);
		return $response;
	}
}
