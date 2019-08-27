<?php namespace render\layout;

class Layout 
{

	static public $js = [];
	static public $css = [];


	static function loadJs($jsFile){
		if(!in_array($jsFile, self::$js)){
			self::$js[] = $jsFile;
		}
	}

	static function loadCss($cssFile){
		if(!in_array($cssFile, self::$css)){
			self::$css[] = $cssFile;
		}
	}

	static function render($file,$param = [],$_title='小老板ERP'){
		// var_dump($param);
		extract($param);
		ob_start();
		if(!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || $_SERVER['HTTP_X_REQUESTED_WITH']!='XMLHttpRequest'){
			include VIEW_ROOT."layouts/header.php";
		}
		$file = self::getPath($file);
		// unset($this);
		// $this = $_this;
		include $file;
		if(!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || $_SERVER['HTTP_X_REQUESTED_WITH']!='XMLHttpRequest'){
			include VIEW_ROOT."layouts/footer.php";
		}
		self::___load();
	}

	static function getPath($view){
        if (strncmp($view, '//', 2) === 0){
        	$file = \Yii::$app->getViewPath(). DIRECTORY_SEPARATOR .$view;
        }else{
        	$file = \Yii::$app->controller->module->getViewPath(). DIRECTORY_SEPARATOR .\Yii::$app->controller->id.DIRECTORY_SEPARATOR .$view;
        }
        return $file.'.php';
    }

	static function getRoot($file,$addon=''){
		if(stripos($file,'//') !== false){
		// var_dump(stripos($file,'//'));
			return $file;
		}else{
			return HTTP_HOST.$addon.$file;
		}
	}

	static function ___load(){
		$_css = $_js = [];
		foreach(self::$css as $css){
			$_css[] = self::getRoot($css,'assets/stylesheets/css/');
		}
		foreach(self::$js as $js){
			$_js[] = self::getRoot($js,'assets/javascripts/');
		}
		header("stylesheet:".implode(',',$_css));
		header("script:".implode(',',$_js));
		$output = ob_get_contents();
		ob_end_clean();
		echo $output;
		// var_dump(self::$js);
	}

}