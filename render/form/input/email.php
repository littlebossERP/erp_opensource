<?php namespace render\form\input;


class Email extends \Render\Form\Base
{

	public $validPattern = '^(\w)+(\.\w+)*@(\w)+((\.\w+)+)$';

	function __toString(){
		return "<input type='email' name='{$this->name}' ".$this->getClass()." ".$this->event->bind()." pattern='{$this->validPattern}' />";
	}

} 