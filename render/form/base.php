<?php namespace render\form;

use render\layout\Layout;

class Base 
{
	public $className = [];
	public $name;
	public $value;
	public $event;
	public $_id;
	public $loadCss = [];
	public $loadJs = [];

	function __construct($param=[]){
		$this->event = new Event;
		if(isset($param['class'])){
			$this->addClass($param['class']);
			unset($param['class']);
		}
		foreach($param as $key=>$val){
			$this->$key = $val;
		}
		foreach($this->loadCss as $css){
			Layout::loadCss($css);
		}
		foreach($this->loadJs as $js){
			Layout::loadJs($js);
		}
		$this->_id = 'iv'.md5(uniqid(microtime()));
	}

	function addClass($className){
		$this->className[] = $className;
		return $this;
	}

	function removeClass($className){
		$this->className[$className] = NULL;
		return $this;
	}

	function getClass(){
		return "class='".implode(' ',$this->className)."'";
	}

	function on($event,$handle){
		switch($event){
			default:
				$event = 'on'.ucfirst($event);
				break;
		}
		$this->event->$event = $handle;
		return $this;
	}

	function getId(){
		return $this->_id;
	}

	function data($arr){
		$this->data = array_merge($this->data,$arr);
		return $this;
	}

	function getData(){
		$o = [];
		foreach($this->data as $k=>$v){
			$o[] = "$k='$v'";
		}
		return implode(' ',$o);
	}

}