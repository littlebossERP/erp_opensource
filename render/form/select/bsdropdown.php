<?php namespace render\form\select;

class BsDropdown extends \render\form\Base 
{

	function __toString(){
		$html = '<div class="dropdown">
		  <button id="dLabel" type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
		    app
		    <span class="caret"></span>
		  </button>
		  <ul class="dropdown-menu" aria-labelledby="dLabel">
		    <li>我地的奇偶ifji发窘发窘反倒是就</li>
		  </ul>
		</div>';
		return $html;
	}


}