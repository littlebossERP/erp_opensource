<?php namespace eagle\modules\listing\service;

/**
 * 当获取不存在的属性时返回默认值，方便及美观代码用
 */
class Attributes 
{

	public $_attributes = [];
	public $nullValue = NULL;

	function __construct($data = [],$null = NULL){
		$this->_attributes = $data;
		$this->nullValue = $null;
	}

	function __get($name){
		return isset($this->_attributes[$name])?$this->_attributes[$name]:$this->nullValue;
	}

	function get($name,$defaultValue=NULL){
		if($defaultValue===NULL){
			$defaultValue = $this->nullValue;
		}
		return isset($this->_attributes[$name])?$this->_attributes[$name]:$defaultValue;
	}

	function exists($name){
		return isset($this->_attributes[$name]);
	}

	function __isset($name){
		return $this->exists($name);
	}


}