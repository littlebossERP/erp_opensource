<?php
namespace common\helpers;
//数据提交入口
class SubmitGate{
	//超时设置
	static private $timeout = 20;
	static private $connecttimeout = 10;
	
	/**
	 +----------------------------------------------------------
	 * 远程表求总的入口
	 +----------------------------------------------------------
	 * @access protected
	 +----------------------------------------------------------
	 * @param $url  请求URL
	 * @param $params	数据
	 * @param $tool 使用什么方式请求curl soap restfull
	 * @param $method 请求SOAP时，调用的方法 CURL有GET和POST
	 +----------------------------------------------------------
	 * @return	$result
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		hxlu   2014/08/07				初始化
	 +----------------------------------------------------------
	**/
	public function mainGate($url, $params, $tool, $method=null, $tmp_timeout = 20){
	    try{
		  if(!is_array(get_headers($url)))
		 	return self::getResult(1, '', ' 请求'.$url.'出现错误');
	    }catch(\Exception $e){
	      	\Yii::error("mainGate get_header $url error msg:".$e->getMessage(),"file");
	      	return self::getResult(1, '', ' 请求'.$url.'出现错误');
	    }
		if($tool == 'curl')
			$response = $this->curlGate($url, $tmp_timeout, $params, $method);
		if($tool == 'soap')
			$response = $this->soapGate($url, $tmp_timeout, $params, $method);
		if($tool == 'restfull')
			$response = $this->restGate($url, $tmp_timeout, $params, $method);
		return $response;
	}
	/**
	 +----------------------------------------------------------
	 * curl 提交
	 +----------------------------------------------------------
	 * @access protected
	 +----------------------------------------------------------
	 * @param $url  请求URL
	 * @param $timeout 超时
	 * @param $params	数据
	 * @param $method GET和POST
	 +----------------------------------------------------------
	 * @return	$result
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		hxlu   2014/08/07				初始化
	 +----------------------------------------------------------
	**/
	private function curlGate($url, $timeout, $params, $method='POST'){
		if($method == 'GET') $url = $url.$params;
		$result = array();
		try {
			// 初始curl类
			$connection = curl_init();
			// 设置CURL，模拟POST
			curl_setopt($connection, CURLOPT_URL, $url);
			curl_setopt($connection, CURLOPT_SSL_VERIFYPEER, 0);
			curl_setopt($connection, CURLOPT_SSL_VERIFYHOST, 0);
			curl_setopt($connection, CURLOPT_HTTPHEADER, array('Content-Type: text/xml;charset=utf-8'));
			curl_setopt($connection, CURLOPT_CONNECTTIMEOUT, self::$connecttimeout);
			curl_setopt($connection, CURLOPT_TIMEOUT, $timeout);//只需要设置一个秒的数量就可以
			if($method == 'POST'){
				curl_setopt($connection, CURLOPT_POST, 1);
				curl_setopt($connection, CURLOPT_POSTFIELDS, $params);
			}
			curl_setopt($connection, CURLOPT_RETURNTRANSFER, 1);
			// 执行
			$response = curl_exec($connection);
			$error=curl_error($connection);
			// close curl
			curl_close($connection);
			if(!$response){
				$result = self::getResult('400013', '', '服务器连接失败，请检查网络是否正常！'.$error);
			}else{
				$result = self::getResult(0, $response, '');
			}
		} catch (Exception $e) {
			$result = self::getResult('400013', '', '服务器连接失败，请检查网络是否正常！'.$e->getMessage());
		}
		// 返回数据
		return $result;
	}
	/**
	 +----------------------------------------------------------
	 * SOAP提交
	 +----------------------------------------------------------
	 * @access protected
	 +----------------------------------------------------------
	 * @param $url  请求URL
	 * @param $timeout 超时
	 * @param $params	数据
	 * @param $method SOAP接口方法
	 +----------------------------------------------------------
	 * @return	$result
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		hxlu   2014/08/07				初始化
	 +----------------------------------------------------------
	**/
	private $soap = null;
	private $soapUrl  = null;
	private function soapGate($url, $timeout, $params, $method){
		$result = array();
		if($this->soapUrl != $url || is_null($this->soap)){
			set_time_limit(0);
			try{
				//亚太平台 CNPOST 出现了一个情况是假如直接用URL访问的话会出现异常  Start tag expected, '<' not found
				if($url == 'https://api.apacshipping.ebay.com.hk/aspapi/v4/ApacShippingService?wsdl'){
// 					$this->soap = new \SoapClient(\Yii::getAlias('@web').'docs/ApacShippingService.xml');
					$streamContext = stream_context_create(array('http'=>array('protocol_version'=>1.0)));
					$this->soap = new \SoapClient($url, array('stream_context' => $streamContext));
				}else{
					$this->soap = new \SoapClient($url);
				}
				
				$this->soapUrl = $url;
			}catch(SoapFault $e){
				$this->soap = null;
				$result = self::getResult('400013', '', '连接'.$url.'失败'.$e->getMessage());
			}
		}
		if($this->soap){
			try{
				$response = $this->soap->$method($params);
				if(!$response)
					$result = self::getResult('400013', '', 'SOAP连接'.$url.'成功，调用'.$method.'失败！');
				else
					$result = self::getResult(0, $response, '');
			}catch(Exception $e){
				$result = self::getResult('400013', '', 'SOAP连接'.$url.'成功，调用'.$method.'失败！'.$e->getMessage());
			}
		}
		return $result;
	}
	//restfull提交
	/*
	private function fgetGate($url, $timeout, $params){
		//$tmpurl = parse_url($url);
		//$tmpurl = $tmpurl['scheme'].'://'.$tmpurl['host'];
		$result = array();
		try{
			$opts = array(
				'http'=>array(
					'method'=>"GET",
					'timeout'=>$timeout,
				)
			);
			$context = stream_context_create($opts);
			$response =file_get_contents($url.$params, false, $context);
			if(!$response){
				$result = self::getResult('400013', '', "FGET连接 $url 失败！");
			}else{
				$result = self::getResult(0, $response, '');
			}
		}catch(Exception $e){
			$result = self::getResult('400013', '', "FGET连接 $url 失败！".$e->getMessage());
		}
		return $result;
	}
	 */

	/**
	 +----------------------------------------------------------
	 * REST提交
	 +----------------------------------------------------------
	 * @access protected
	 +----------------------------------------------------------
	 * @param $url  请求URL
	 * @param $timeout 超时
	 * @param $params	数据
	 * @param $method SOAP接口方法
	 +----------------------------------------------------------
	 * @return	$result
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		hxlu   2014/08/07				初始化
	 +----------------------------------------------------------
	**/
	private function restGate($url, $timeout, $params=null, $method='GET', $format='json'){
		//最终返回数据
		$result = array();
		$cparams = array(
			'http' => array(
				'method' => $method,
				'ignore_errors' => true,
				'timeout' => $timeout
			)
		);
		if ($params !== null) {
			//生成URL参数字符串
			$params = http_build_query($params);
			if ($method == 'POST') {
				$cparams['http']['header'] = "Content-type: application/x-www-form-urlencoded\r\n"
					. "Content-Length: " . strlen($params) . "\r\n";
				$cparams['http']['content'] = $params;
				//echo "<pre>";
				//print_r($cparams);
				//echo "</pre>";
			} else {
				$url .= '?' . $params;
			}
		}
		$context = stream_context_create($cparams);
		try{
			$fp = fopen($url, 'rb', false, $context);
			$response = stream_get_contents($fp);
			// HTTP头部信息
			// $meta = stream_get_meta_params($fp);
			$result = self::getResult(0, $response, '');
		}catch(Exception $e){
			$result = self::getResult('400013', '', '服务器连接失败，请检查网络是否正常！'.$e->getMessage());
		}
		/*
		switch ($format) {
			case 'json':
				$r = json_decode($res, true);
				print_r($r);exit;
				if ($r === null) {
					throw new Exception("failed to decode $res as json");
				}
				return $r;
			case 'xml':
				$r = simplexml_load_string($res);
				if ($r === null) {
					throw new Exception("failed to decode $res as xml");
				}
				return $r;
		}
		 */
		return $result;
	}
	/**
	 +----------------------------------------------------------
	 * 获取返回值方法
	 +----------------------------------------------------------
	 * @access protected
	 +----------------------------------------------------------
	 * @param $error		错误代码 0：为成功
	 * @param $params			数据
	 * @param $msg			错误消息
	 +----------------------------------------------------------
	 * @return	$result
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		hxl		2014/08/07				初始化
	 +----------------------------------------------------------
	**/
	static public function getResult($error, $data, $msg) {
		return array('error' => $error, 'data' => $data, 'msg' => $msg);
	}
}