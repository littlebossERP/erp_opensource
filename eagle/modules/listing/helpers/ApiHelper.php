<?php namespace eagle\modules\listing\helpers;

class ApiHelper 
{

	private $_ch;
	private $url;

	public $data=[];
	public $params=[];
	public $time_out = 180;
	public $code = 200;
	public $errno;
	public $error;

	function __construct($url){
		$this->url = $url;
	}

	function params($params = []){
		if(count($params)){
        	$this->url .= '?'.http_build_query($params);
        }
        return $this;
	}

	function data($data=[]){
		$this->data = array_merge($this->data,$data);
		return $this;
	}


	function call(){
		try{
			$this->_ch = curl_init($this->url);
			curl_setopt($this->_ch,  CURLOPT_RETURNTRANSFER, TRUE);
	        curl_setopt($this->_ch, CURLOPT_TIMEOUT, $this->time_out);
	        if (count($this->data)>0){
	            curl_setopt($this->_ch, CURLOPT_POST, true);
	            curl_setopt($this->_ch, CURLOPT_POSTFIELDS, http_build_query($this->data));
	        }
	        $response = curl_exec($this->_ch);
	        // $curl_errno = curl_errno($this->_ch);
	        // $curl_error = curl_error($this->_ch);
	        $httpCode = curl_getinfo($this->_ch, CURLINFO_HTTP_CODE);
	        $this->errno = curl_errno($this->_ch);
	        $this->error = curl_error($this->_ch);
	        if($response){
	        	$result = [
	        		'success'=>true,
	        		'code'=>200,
	        		'httpCode'=>$httpCode,
	        		'response'=>json_decode($response,true)
	        	];
	        }else{
	        	$result = [
	        		'success'=>false,
	        		'code'=>500,
	        		'httpCode'=>$httpCode,
	        		'message'=>'curl_error:'.$this->errno.$this->error,
	        		'response'=>json_decode($response,true)
	        	];
	        }
		}catch(\Exception $e){
			$result = [
				'success'=>false,
        		'code'=>500,
				'httpCode'=>$e->getCode(),
				'message'=>$e->getMessage()
			];
		}
	    curl_close($this->_ch);
	    return $result;
	}


}