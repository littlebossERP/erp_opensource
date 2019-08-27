<?php 
namespace render\form\select;

class Select extends \render\form\Base 
{

	function __toString(){
		$html = '<div class="iv-select">
			<select name="" '.$this->event->bind().' >';
		foreach($this->option as $key=>$val){
			$html.="<option value='{$key}'>{$val}</option>";
		}
		return $html .="</select><span class='iconfont icon-control-arr'></span></div>";
	}

}