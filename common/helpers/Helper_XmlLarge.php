<?php
namespace common\helpers;

use XMLReader;
use Exception;
use DOMDocument;
/**
 * 大型xml文件处理助手
 * 
 * /---code php 
 * 	$xmlreader=new Helper_XmlLarge('222222_report.xml');
 * 	while ($xmlreader->read('SKUDetails')){
 * 		$sd=$xmlreader->toSimpleXmlObj();
 * 		print_r($sd);
 * 	}
 * \---
 * @package helper
 */ 
class Helper_XmlLarge{
	/**
	 * xmlReader Obj
	 *
	 * @var XMLReader
	 */
	public $xml_reader_obj=null;
	function __construct($filename){
		$this->xml_reader_obj=new XMLReader();
		$this->xml_reader_obj->open($filename);
	}
	function read($nodeName){
		while ($this->xml_reader_obj->read()){
			if ($this->xml_reader_obj->nodeType ==XMLReader::ELEMENT && $this->xml_reader_obj->name ==$nodeName){
				return true;
				break;
			}
		}
		return false;
	}
	function toSimpleXmlObj(){
		static $doc;
		static $simpleobj;
		$doc = new DOMDocument('1.0', 'UTF-8');
		$ep=$this->xml_reader_obj->expand();
		if ($ep===false){
			return false;
		}
		$simpleobj=simplexml_import_dom($doc->importNode($ep,true));
		return $simpleobj;
	}
	function expand(){
		try{
			return $this->xml_reader_obj->expand();
		}catch(Exception $ex){
			echo "Error Message :  ". $ex->getMessage();
			return null;
		}
	}
}