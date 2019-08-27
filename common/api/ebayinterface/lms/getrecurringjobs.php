<?php
/**
 * Large Merchant Service
 *
 */
class EbayInterface_LMS_getRecurringJobs  extends EbayInterface_LMS_base_bulkdata   
{
	public $verb='getRecurringJobs';
	public $service_name='BulkDataExchangeService';
	function __construct(){
		parent::__construct($this->service_name);
	}
	function api(){
		$xmlArr=array(
		);
		$response=$this->setRequestBody($xmlArr)->sendRequest();
		return $response;
	}
}
