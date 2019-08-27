<?php namespace render\form;

class ActionGroup extends Base 
{
	private $_buttons = [];

	function addButton($label, $icon, $link = '#', $event = ''){
		$this->_buttons[] = compact("label","icon","link","event");
	}

	function __toString(){
		$html = '<div class="action-group">';

		if(count($this->_buttons)>3){
			$html .= "<select class='iv-input' location-href='$(this).val()'>
			<option>操作</option>";
			foreach($this->_buttons as $icon){
				$html.="<option onclick='{$icon['event']}' value='{$icon['link']}'>{$icon['label']}</option>";
			}
			$html.="</select></div>";
		}else{
			foreach($this->_buttons as $icon){
				$html .= "<a href='{$icon['link']}' onclick='{$icon['event']}' title='{$icon['label']}'>
					<span class='iconfont {$icon['icon']}'></span>
				</a>";
			}
		}
		return $html."</div>";
	}

}