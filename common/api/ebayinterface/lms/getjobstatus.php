<?php
/**
 * Large Merchant Service
 *
 */
class EbayInterface_LMS_getJobStatus extends EbayInterface_LMS_base_bulkdata   
{
	public $verb='getJobStatus';
	public $service_name='BulkDataExchangeService';
	function __construct(){
		parent::__construct($this->service_name);
	}
	function api($jobid){
		$xmlArr=array(
			'jobId'=>$jobid,
		);
		$response=$this->setRequestBody($xmlArr)->sendRequest();
		return $response;
	}
}
