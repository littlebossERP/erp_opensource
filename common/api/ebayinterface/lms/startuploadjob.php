<?php
/**
 * Large Merchant Service
 *
 */
class EbayInterface_LMS_startUploadJob extends EbayInterface_LMS_base_bulkdata   
{
	public $verb='startUploadJob';
	public $service_name='BulkDataExchangeService';
	function __construct(){
		parent::__construct($this->service_name);
	}
	function api($jobId){
		$xmlArr=array(
			'jobId'=>$jobId,
		);
		$response=$this->setRequestBody($xmlArr)->sendRequest();
		return $response;
	}
}
