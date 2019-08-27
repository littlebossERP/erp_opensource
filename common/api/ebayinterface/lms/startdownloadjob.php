<?php
/**
 * Large Merchant Service
 *
 */
class EbayInterface_LMS_startDownloadJob extends EbayInterface_LMS_base_bulkdata   
{
	public $verb='startDownloadJob';
	public $service_name='BulkDataExchangeService';
	function __construct(){
		parent::__construct($this->service_name);
	}
	function api($uuid,$downloadJobType='ActiveInventoryReport'){
		$xmlArr=array(
			'downloadJobType'=>$downloadJobType,
			'UUID'=>$uuid,
		);
		$response=$this->setRequestBody($xmlArr)->sendRequest();
		return $response;
	}
}
