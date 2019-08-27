<?php
/**
 * Large Merchant Service
 *
 */
class EbayInterface_LMS_getRecurringJobExecutionHistory  extends EbayInterface_LMS_base_bulkdata   
{
	public $verb='getRecurringJobExecutionHistory';
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
