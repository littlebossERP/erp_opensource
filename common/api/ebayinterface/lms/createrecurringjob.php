<?php
/**
 * Large Merchant Service
 *
 */
class EbayInterface_LMS_createRecurringJob extends EbayInterface_LMS_base_bulkdata   
{
	public $verb='createRecurringJob';
	public $service_name='BulkDataExchangeService';
	function __construct(){
		parent::__construct($this->service_name);
	}
	function api($downloadJobType,$frequencyInMinutes,$uuid=null){
		if (is_null($uuid)){
			$uuid=Helper_Util::getUuid();
		}
		$xmlArr=array(
			'downloadJobType'=>$downloadJobType,
			'UUID'=>$uuid,
			'frequencyInMinutes'=>$frequencyInMinutes
		);
		$response=$this->setRequestBody($xmlArr)->sendRequest();
		return $response;
	}
}
