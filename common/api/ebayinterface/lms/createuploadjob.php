<?php
/**
 * Large Merchant Service
 *
 */
class EbayInterface_LMS_createUploadJob extends EbayInterface_LMS_base_bulkdata  
{
	public $verb='createUploadJob';
	public $service_name='BulkDataExchangeService';
	function __construct(){
		parent::__construct($this->service_name);
	}
	function api($uuid,$uploadJobType,$fileType='xml'){
		$xmlArr=array(
            'UUID'=>$uuid,
			'uploadJobType'=>$uploadJobType,
            'fileType'=>$fileType,
		);
		$response=$this->setRequestBody($xmlArr)->sendRequest();
		return $response;
	}
    
}