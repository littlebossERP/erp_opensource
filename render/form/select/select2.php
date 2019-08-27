<?php namespace render\form\select;


class Select2 extends \render\form\Base
{
	public $option = [];

	public $loadCss = ['select2.css'];
	public $loadJs = ['select2.js'];

	function __toString(){
		$option = [];
		foreach($this->option as $key=>$value){
			$option[] = "<option value='$key'>$value</option>";
		}
		return "<select onload='select2(this)' name='{$this->name}' ".$this->getClass()." ".$this->event->bind().">".implode(PHP_EOL,$option)."</select>";
	}


}