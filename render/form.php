<?php namespace render;

class Form extends Form\Base
{


	public $loadCss = ['form.css'];
	public $loadJs = ['form.js'];

	public $valid = true;
	private $id;


	function submit($value='提交'){
		return "<input for='{$this->id}' class='btn btn-success' type='submit' value='$value' />";
	}

	function init(){
		$this->id = 'form_'.md5(uniqid(time()));
		echo "<form novalidate iv-valid id='{$this->id}'>";
	}

	function end(){
		echo "</form>";
	}


}