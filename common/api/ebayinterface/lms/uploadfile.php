<?php
/**
 * Large Merchant Service
 *
 */
class EbayInterface_LMS_uploadFile extends EbayInterface_LMS_base_filetransfer  
{
	public $verb='uploadFile';
	public $service_name='FileTransferService';
    /****
	 * eBay Api Version
	 */
	public $version='1.1.0';
    
	
    function __construct(){
		parent::__construct($this->service_name);
	}
	
	function getHeaders($init=null){
		
		$contentType = 'multipart/related; boundary='.$this->_mime_boundary.'; type="application/xop+xml"; ';
		$contentType .= 'start="<0.urn:uuid:'.$this->xmlUuid.'>"; start-info="text/xml"';
		$h= parent::getHeaders(array('CONTENT-TYPE'=>$contentType));
		 return $h;
	}
    /***
     * 添加文件内容
     *      
     */ 
	function api($fileReferenceId,$taskReferenceId,$filedata,$filetype='xml'){
		// Uuid 
		$this->fileUuid= md5(Helper_Util::getUuid()); 
		
		$this->xmlUuid= md5(Helper_Util::getUuid());
		
		// File 
		if($filetype=='zip'||$filetype=='gzip'){
			 
        }else{
            //gzip
            $filedata= gzencode($filedata);
			$filetype='gzip';
        }
		
        $filesize=strlen($filedata);
		
		// xml
		$xmlArr=array(
			'uploadFileRequest xmlns:sct="http://www.ebay.com/soaframework/common/types" xmlns="http://www.ebay.com/marketplace/services"'=>array(
				'fileReferenceId'=>$fileReferenceId,
				'taskReferenceId'=>$taskReferenceId, // jobid
				'fileFormat'=>$filetype,
				'fileAttachment'=>array(
					'Data'=>'<xop:Include xmlns:xop="http://www.w3.org/2004/08/xop/include" href="cid:urn:uuid:'.$this->fileUuid.'" />',
					'Size'=> $filesize
				)
			)
		);
		$xmlData=Helper_xml::simpleArr2xml($xmlArr,0);
		
		$xmlbodyfile=$this->buildXmlBodyFile($xmlData,$filedata);
//		@file_put_contents(INDEX_DIR.'/_tmp1010/'.$fileReferenceId.'.gz',$filedata);
//		error_log("\n\n------------------------------------ \n".
//                  print_r($xmlbodyfile,1)."\n",3,INDEX_DIR.'/'.date('Ymd').'uploadfileDATA.txt');
		// upload request 
		return $this->sendHttpRequest($xmlbodyfile);
	}
    
}