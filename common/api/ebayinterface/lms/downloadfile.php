<?php
namespace common\api\ebayinterface\lms;

use common\api\ebayinterface\lms\base\filetransfer;
use DOMDocument;
use Yii;
use common\helpers\Helper_Filesys;
use ZipArchive;
/**
 * Large Merchant Service
 *
 */
class downloadfile extends filetransfer  
{
	public $verb='downloadFile';
	public $service_name='FileTransferService';
	function __construct(){
		parent::__construct($this->service_name);
	}
	function api($fileReferenceId,$taskReferenceId,$filename){
		set_time_limit(0);
		$xmlArr=array(
			'fileReferenceId'=>$fileReferenceId,
			'taskReferenceId'=>$taskReferenceId
		);
		$response=$this->setRequestBody($xmlArr)->sendRequest(1,800);
		return self::savefile($response,$filename);
	}
	
	function savexml($response){
		$beginResponseXML = strpos($response, '<?xml');
		$endResponseXML = strpos($response, '</downloadFileResponse>',
			$beginResponseXML);
		$endResponseXML += strlen('</downloadFileResponse>');
		$responseXML=substr($response, $beginResponseXML,
			$endResponseXML - $beginResponseXML);
			$dom = new DomDocument();
			$dom->preserveWhitespace = false;
			$dom->loadXML($responseXML);
			$dom->formatOutput = true;
			$responseDOM=$dom;
//			echo '<p><pre>' . htmlspecialchars( $responseDOM->saveXML() ) . '</pre></p>';
		$xopInclude = $responseDOM->getElementsByTagName('Include')->item(0);
		$uuid = $xopInclude->getAttributeNode('href')->nodeValue;
		$uuid = substr($uuid, strpos($uuid,'urn:uuid:'));
		
		$contentId = 'Content-ID: <' . $uuid . '>';
		
		$mimeBoundaryPart = strpos($response,'--MIMEBoundaryurn_uuid_');
		
		$beginFile = strpos($response, $contentId, $mimeBoundaryPart);
		$beginFile += strlen($contentId);
		
		//Accounts for the standard 2 CRLFs.
		$beginFile += 4;
		
		$endFile = strpos($response,'--MIMEBoundaryurn_uuid_',$beginFile);
		
		//Accounts for the standard 1 CRLFs.
		$endFile -= 2;
		
		$fileBytes = substr($response, $beginFile, $endFile - $beginFile);
		
		echo "<p><b>Writing File to downloadfile.zip : ";
		
		mkdir(Yii::$app->basePath.'/runtime/xml/specifics');
		$handler = fopen(Yii::$app->basePath.'/runtime/xml/specifics/downloadfile.zip', 'wb') 
			or die("Failed. Cannot Open downloadfile.zip to Write!</b></p>");
		fwrite($handler, $fileBytes);
		fclose($handler);
		
		echo 'Success.</b></p>';
		return 'Success';
	}
	function savefile($response,$filename,$unzip=1){
		$beginResponseXML = strpos($response, '<?xml');
		$endResponseXML = strpos($response, '</downloadFileResponse>',
			$beginResponseXML);
		$endResponseXML += strlen('</downloadFileResponse>');
		$responseXML=substr($response, $beginResponseXML,
			$endResponseXML - $beginResponseXML);
		$dom = new DomDocument();
		$dom->preserveWhitespace = false;
		$dom->loadXML($responseXML);
		$dom->formatOutput = true;
		$responseDOM=$dom;
		$xopInclude = $responseDOM->getElementsByTagName('Include')->item(0);
		$uuid = $xopInclude->getAttributeNode('href')->nodeValue;
		$uuid = substr($uuid, strpos($uuid,'urn:uuid:'));
		
		$contentId = 'Content-ID: <' . $uuid . '>';
		
		$mimeBoundaryPart = strpos($response,'--MIMEBoundaryurn_uuid_');
		
		$beginFile = strpos($response, $contentId, $mimeBoundaryPart);
		$beginFile += strlen($contentId);
		
		//Accounts for the standard 2 CRLFs.
		$beginFile += 4;
		
		$endFile = strpos($response,'--MIMEBoundaryurn_uuid_',$beginFile);
		
		//Accounts for the standard 1 CRLFs.
		$endFile -= 2;
		
		$fileBytes = substr($response, $beginFile, $endFile - $beginFile);
		
		
		$dirname=dirname($filename);
		Helper_Filesys::mkdirs($dirname);
		
		$handler = fopen($filename, 'wb') 
			or die("Failed. Cannot Open downloadfile.zip to Write!");
		fwrite($handler, $fileBytes);
		fclose($handler);
		if ($unzip){
			$zip=new ZipArchive();
			if ($zip->open($filename) !== TRUE) {  
			    die ("Could not open archive");  
			} 
			$zip->extractTo($dirname); //自动覆盖相同文件名的
			$zip->close();
		}
		return true;
	}
	function apigetitem($fileReferenceId,$taskReferenceId,$uid){
		$xmlArr=array(
			'fileReferenceId'=>$fileReferenceId,
			'taskReferenceId'=>$taskReferenceId
		);
		$response=$this->setRequestBody($xmlArr)->sendRequest(1);
		return self::savexmlgetitem($response,$uid);
	}
	
	function savexmlgetitem($response,$uid){
		$beginResponseXML = strpos($response, '<?xml');
		$endResponseXML = strpos($response, '</downloadFileResponse>',
			$beginResponseXML);
		$endResponseXML += strlen('</downloadFileResponse>');
		$responseXML=substr($response, $beginResponseXML,
			$endResponseXML - $beginResponseXML);
			$dom = new DomDocument();
			$dom->preserveWhitespace = false;
			$dom->loadXML($responseXML);
			$dom->formatOutput = true;
			$responseDOM=$dom;
//			echo '<p><pre>' . htmlspecialchars( $responseDOM->saveXML() ) . '</pre></p>';
		$xopInclude = $responseDOM->getElementsByTagName('Include')->item(0);
		$uuid = $xopInclude->getAttributeNode('href')->nodeValue;
		$uuid = substr($uuid, strpos($uuid,'urn:uuid:'));
		
		$contentId = 'Content-ID: <' . $uuid . '>';
		
		$mimeBoundaryPart = strpos($response,'--MIMEBoundaryurn_uuid_');
		
		$beginFile = strpos($response, $contentId, $mimeBoundaryPart);
		$beginFile += strlen($contentId);
		
		//Accounts for the standard 2 CRLFs.
		$beginFile += 4;
		
		$endFile = strpos($response,'--MIMEBoundaryurn_uuid_',$beginFile);
		
		//Accounts for the standard 1 CRLFs.
		$endFile -= 2;
		
		$fileBytes = substr($response, $beginFile, $endFile - $beginFile);
		
		echo "<p><b>Writing File to downloadfile.zip : ";
		
		mkdir(INDEX_DIR.'/link/xml/getitem');
		mkdir(INDEX_DIR.'/link/xml/getitem/'.$uid);
		$handler = fopen(INDEX_DIR.'/link/xml/getitem/'.$uid.'/downloadfile.zip', 'wb') 
			or die("Failed. Cannot Open downloadfile.zip to Write!</b></p>");
		fwrite($handler, $fileBytes);
		fclose($handler);
		
		echo 'Success.</b></p>';
		return 'Success';
	}
}
