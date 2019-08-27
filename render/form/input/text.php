<?php namespace render\form\input;


class Text extends \Render\Form\Base
{

	function __toString(){
		return "<input type='text' name='{$this->name}' ".$this->getClass()." ".$this->event->bind()." />";
	}

} 