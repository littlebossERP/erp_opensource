<?php
/**
 * Large Merchant Service
 *
 */
class EbayInterface_LMS_deleteRecurringJob extends EbayInterface_LMS_base_bulkdata   
{
	public $verb='deleteRecurringJob';
	public $service_name='BulkDataExchangeService';
	function __construct(){
		parent::__construct($this->service_name);
	}
	function api($recurringJobId){
		$xmlArr=array(
			'recurringJobId'=>$recurringJobId,
		);
		$response=$this->setRequestBody($xmlArr)->sendRequest();
		return $response;
	}
}
