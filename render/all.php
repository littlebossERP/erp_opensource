<?php

define('ROOT',dirname(__DIR__));
define('VIEW_ROOT',__DIR__.'/../eagle/views2/');
define('HTTP_HOST','');

define('ICON_CSS_LINK','//at.alicdn.com/t/font_1448442458_1247618.css');

spl_autoload_register(function($className){
	$file = str_replace(['\\','/'],DIRECTORY_SEPARATOR,strtolower($className).'.php');
	if(is_file(ROOT.'/'.$file)){
		include_once ROOT.'/'.$file;
	}
});