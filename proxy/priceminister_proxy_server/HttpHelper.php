<?php
/**
 * @link http://www.witsion.com/
 * @copyright Copyright (c) 2014 Yii Software LLC
 * @license http://www.witsion.com/
 */
 
/**
 * BaseHelper is the base class of module BaseHelpers.
 *
$url='http://www.baidu.com';  
$httpRequest=new HttpRequest($url);  
$httpRequest->sendRequest();  
file_put_contents('temp.txt',$httpRequest->getResponse());  
echo $httpRequest->getResponseBody();  
  
 */
class HttpHelper {
		private $sHostAdd;//服务器ip
		private $sUri;//请求的文件
		private $iPort;//服务器端口
		private $sRequestHeader;//请求头信息
		private $sResponse;//请求信息
	
		//构造函数
		function __construct($sUrl){
			$this->initClass($sUrl);
		}

		//重置整个class的初始值，为了class重用，不需要重新new class
		function initClass($sUrl){
			$this->sHostAdd = '';//服务器ip
			$this->sUri = '';//请求的文件
			$this->iPort = '';//服务器端口
			$this->sRequestHeader = '';//请求头信息
			$this->sResponse = '';//请求信息
			
			$sPatternUrlPart = '/http:\/\/([a-z-\.0-9]+)(:(\d+)){0,1}(.*)/i';
			$arMatchUrlPart = array();
			preg_match($sPatternUrlPart, $sUrl, $arMatchUrlPart);
			$this->sHostAdd = gethostbyname($arMatchUrlPart[1]);
			if (empty($arMatchUrlPart[4])){
				$this->sUri = '/';
			}else{
				$this->sUri = $arMatchUrlPart[4];
			}
			if (empty($arMatchUrlPart[3])){
				$this->iPort = 80;
			}else{
				$this->iPort = $arMatchUrlPart[3];
			}
			$this->addRequestHeader('Host: '.$arMatchUrlPart[1]);
			$this->addRequestHeader('Connection: Close');
			$this->addRequestHeader('Content-Encoding: gzip');
		}
	
		//添加头信息
		function addRequestHeader($sHeader){
			$this->sRequestHeader .= trim($sHeader)."\r\n";
		}
	
		//发送请求
		function sendRequest($sMethod = 'GET', $sPostData = ''){
			$sRequest = $sMethod." ".$this->sUri." HTTP/1.1\r\n";
			$sRequest .= $this->sRequestHeader;
			if ($sMethod == 'POST'){
				$sRequest .= "Content-Type: application/x-www-form-urlencoded\r\n";
				$sRequest .= "Content-Length: ".strlen($sPostData)."\r\n";
				$sRequest .= "\r\n";
				$sRequest .= $sPostData."\r\n";
			}
			$sRequest .= "\r\n";
			$sockHttp = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
			if (!$sockHttp){
				die('socket_create() failed!');
			}
			$resSockHttp = socket_connect($sockHttp, $this->sHostAdd, $this->iPort);
			if (!$resSockHttp){
				die('socket_connect() failed!');
			}
			socket_write($sockHttp, $sRequest, strlen($sRequest));
			$this->sResponse = '';
			while ($sRead = socket_read($sockHttp, 4096)){
				$this->sResponse .= $sRead;
			}
			socket_close($sockHttp);
		}
	
		//获取响应
		function getResponse(){
			return $this->sResponse;
		}
	
		//获取响应正文
		function getResponseBody(){
			$sPatternSeperate = '/\r\n\r\n/';
			$arMatchResponsePart = preg_split($sPatternSeperate, $this->sResponse, 2);
			return $arMatchResponsePart[1];
		}
	
		//获取响应头
		function getResponseHead(){
			$sPatternSeperate = '/\r\n\r\n/';
			$arMatchResponsePart = preg_split($sPatternSeperate, $this->sResponse, 2);
			return $arMatchResponsePart[0];
		}
	
}
