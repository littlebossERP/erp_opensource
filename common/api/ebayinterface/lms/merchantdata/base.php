<?php
/**
 * Large Merchant Service
 * 为 merchantdata 下面的各接口 提供组织流程  
 */
class EbayInterface_LMS_merchantdata_base
{
    public $site=0;
	public $version='729';
    function createRecurringJob(){
        
    }
    
    function startDownloadJob(){
    
    }
    
    function getJobStatus(){
    
    }
    
    function downloadFile(){
    
    }
    
    function uploadFile(){
    
    }
    
    function startUploadJob(){
    
    }
	
    /*****
     *  请求流程
     *
     */              
    function request(){
        
    }
    /*****
     *  接受流程
     *
     */              
    function response(){
        
    }
	/****
	 * 组织数据
     */
	function buildBulkData($payload){
		$header='<?xml version="1.0" encoding="UTF-8"?>';
		$header.="\n<BulkDataExchangeRequests>";
		$header.="\n<Header><SiteID>".$this->site."</SiteID><Version>".$this->version."</Version></Header>";
		return $header.$payload."\n</BulkDataExchangeRequests>";
	}
}