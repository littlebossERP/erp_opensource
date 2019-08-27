<?php namespace render\form\select;

class Dropdown extends \render\form\base
{
	public $lists=[];
	public $href='';
	public $cert = 'light';
	public $align = 'left';
	public $type = '';
	public $className = ['select','dropdownlist'];
	public $value = '';
	public $target = '';
	public $window = '';

	public function __construct(){
		call_user_func_array(['parent','__construct'], func_get_args());
		$this->addClass($this->type);
	}

	private function isActive($value){
		if(( is_array($this->value) && in_array($value, $this->value) ) || $this->value == $value){
			return true;
		}else{
			return false;
		}
	}

	function __toString(){

		$rtn ='<div '.$this->getClass().'>';
		if($this->isActive($this->href)){
			$this->addClass('active');
		}
		$rtn .='<a '.$this->getClass().' href="'.$this->href.'" '.($this->window?"target='{$this->window}'":"").'>'.$this->title;
		if(count($this->lists)){
			$rtn .= '<i class="cert cert-'.$this->cert.' cert-small down"></i>';
		};
		$rtn .= '</a>';
		$rtn .= '<ul class="dropdownlist_ul align-'.$this->align.' '.$this->target.'">';
		if($this->type == 'info'){
			$rtn .= '<div class="info-cert1"></div><div class="info-cert2"></div>';
		}
		// var_dump($this->lists);die;
		foreach($this->lists as $text=>$href){
			$a = [];
			if(!is_array($href)){
				$href = ['href'=>$href];
			}
			foreach($href as $k=>$v){
				switch($k){
					case 'url':
					case 'href':
						$a[] = "href='$v'";
						break;
					case 'isMatch':
						$a[] = "selected='selected'";
						break;
					default:
						if(!is_array($v)){
							$a[] = "$k = '$v'";
						}
						break;
				}
			}
			$rtn .= '<li '.($this->isActive($href)?"class='active'":"").'>
				<a '.implode(' ',$a).'>'.$text.'</a>
			</li>';
		}
		return $rtn .='</ul>
				</div>';
	}

	function dropDownText(){
		$rtn ='<div class="select dropdownlist '.$this->type.'">
					<a '.$this->getClass().' href="'.$this->href.'">
					<span>'.$this->lists[$this->value].'</span>';
		if(count($this->lists)){
			$rtn .= '<i class="cert cert-'.$this->cert.' cert-small down"></i>';
		};
		$rtn .= '</a>';
		$rtn .= '<ul class="dropdownlist_ul align-'.$this->align.' '.$this->type.'">';
		if($this->type == 'info'){
			$rtn .= '<div class="info-cert1"></div><div class="info-cert2"></div>';
		}
		foreach($this->lists as $key=>$text){
			$rtn .= '<li value="'.$key.'">'.$text.'</li>';
		}
		return $rtn .='</ul><input type="hidden" name="'.$this->name.'" value="'.$this->value.'" />
				</div>';
	}


	// function __toString(){

	// 	$rtn = '<div '.$this->getClass().' >
	// 	  <a id="'.$this->getId().'" type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
	// 	    '.$this->title.'
	// 	    <span class="caret"></span>
	// 	  </a>
	// 	  <ul class="dropdown-menu" aria-labelledby="'.$this->getId().'">';
	//     foreach($this->lists as $text=>$href){
	//     	$rtn .="<li>{$text}</li>";
	//     }
	// 	$rtn .='</ul>
	// 	</div>';
	// 	return $rtn;
	// }


}