<?php namespace eagle\modules\listing\service\ensogo;

/**
 * 如果有的则返回，没有的先从变种里取，如果还没有则返回NULL
 */
class GetFromVariance 
{
	private $_data = [];
	private $_variance = [];

	function __construct($data){
		$this->_data = $data;
		if(isset($data['variants']) && is_array($data['variants']) && count($data['variants'])>0){
			$this->_variance = $data['variants'][0];
		}else{
			$this->_variance = [];
		}
	}

	function __get($name){
		return $this->get($name,NULL);
	}

	function get($name,$default = NULL){
		if(isset($this->_data[$name])){
			return $this->_data[$name];
		}elseif(isset($this->_variance[$name])){
			return $this->_variance[$name];
		}else{
			return $default;
		}
	}

}