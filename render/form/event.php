<?php namespace render\form;

class Event 
{

	function __set($name,$args){
		$this->$name = $args;
	}

	function bind(){
		$e = [];
		foreach($this as $event=>$handler){
			$e[] = $event.'="'.$handler.'"';
		}
		return implode(' ',$e);
	}




}