<?php 
namespace eagle\components;

use yii;
use yii\helpers\Html;
use yii\base\InvalidCallException;
use yii\base\InvalidParamException;
/**
 * 改写yii的view类
 */
class View extends \yii\web\View 
{

	public $_path;

	function findViewFile($view,$context=NULL){
		if(!$this->_path){
			return parent::findViewFile($view,$context);
		}
		 if (strncmp($view, '@', 1) === 0) {
            // e.g. "@app/views/main"
            $file = Yii::getAlias($view);
        } elseif (strncmp($view, '//', 2) === 0) {
            // e.g. "//layouts/main"
            $file = Yii::$app->getViewPath() . DIRECTORY_SEPARATOR . ltrim($view, '/');
        } elseif (strncmp($view, '/', 1) === 0) {
            // e.g. "/site/index"
            if (Yii::$app->controller !== null) {
                $file = Yii::$app->controller->module->getViewPath() . DIRECTORY_SEPARATOR . ltrim($view, '/');
            } else {
                throw new InvalidCallException("Unable to locate view file for view '$view': no active controller.");
            }
        } elseif ($context instanceof ViewContextInterface) {
            $file = $context->getViewPath() . DIRECTORY_SEPARATOR . $view;
        } elseif (($currentViewFile = $this->getViewFile()) !== false) {
            $file = dirname($currentViewFile) . DIRECTORY_SEPARATOR . $view;
        } else {
        	if($this->_path){
        		 $file = \Yii::$app->controller->module->getViewPath().'/'.$this->_path.'/'.$view;
        	}else{
            	throw new InvalidCallException("Unable to resolve view file for view '$view': no active view context.");
        	}
        }

        if (pathinfo($file, PATHINFO_EXTENSION) !== '') {
            return $file;
        }
        $path = $file . '.' . $this->defaultExtension;
        if ($this->defaultExtension !== 'php' && !is_file($path)) {
            $path = $file . '.php';
        }

        return $path;
	}



	function endBody(){
		\Yii::$app->response->headers->set('X-Title',urlencode($this->title));
		parent::endBody();
	}


	// function beginPage(){
	// 	parent::beginPage();
	// 	var_dump($this->__cssFiles);
	// 	foreach($this->__cssFiles as $cssFile){
	// 		echo "<link rel='stylesheet' href='{$cssFile}' />";
	// 	}
	// }

	// function endBody(){
	// 	foreach($this->__jsFiles as $jsFile){
	// 		echo "<script src='{$jsFile}'></script>";
	// 	}
	// 	if(is_array($this->blocks) && isset($this->blocks['js'])){
	// 		echo $this->blocks['js'];
	// 	}
	// 	parent::endBody();
	// }

	// findViewFile

}