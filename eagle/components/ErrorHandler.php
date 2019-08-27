<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace eagle\components;

use Yii;
use yii\base\Exception;
use yii\base\ErrorException;
use yii\base\UserException;
use yii\helpers\VarDumper;
use yii\web\Response;
use yii\web\HttpException;

/**
 * ErrorHandler handles uncaught PHP errors and exceptions.
 *
 * ErrorHandler displays these errors using appropriate views based on the
 * nature of the errors and the mode the application runs at.
 *
 * ErrorHandler is configured as an application component in [[\yii\base\Application]] by default.
 * You can access that instance via `Yii::$app->errorHandler`.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @author Timur Ruziev <resurtm@gmail.com>
 * @since 2.0
 */
class ErrorHandler extends \yii\web\ErrorHandler
{
    

    /**
     * Renders the exception.
     * @param \Exception $exception the exception to be rendered.
     */
    protected function renderException($exception)
    {
    	// dzt20160903 配合子账号提示修改statusCode 大于等于400小于500的错误不屏蔽， 而其他没有statusCode的还是屏蔽
    	if(isset($exception->statusCode) && $exception->statusCode >= 400 && $exception->statusCode < 500){
    		parent::renderException($exception);
    		return ;
    	}
    	
    	if(!isset(\Yii::$app->params["isHideError"]) || \Yii::$app->params["isHideError"] == 1){    	
	     	echo '<html lang="en-US"><head>	<meta http-equiv="X-UA-Compatible" content="IE=edge">   <meta charset="UTF-8"/></head>'; 
			echo '<body><div style="margin:25px 0 0 25px;font-size:30px;">该小老板页面有异常，请联系客服！</div></body></html>'; 
	   	}
    	
        if (Yii::$app->has('response')) {
            $response = Yii::$app->getResponse();
            $response->isSent = false;
        } else {
            $response = new Response();
        }
         $useErrorView = $response->format === Response::FORMAT_HTML && (!YII_DEBUG || $exception instanceof UserException);
        
       

        
        if ($useErrorView && $this->errorAction !== null) {
            $result = Yii::$app->runAction($this->errorAction);
            if ($result instanceof Response) {
                $response = $result;
            } else {
                $response->data = $result;
            }
        } elseif ($response->format === Response::FORMAT_HTML) {
            if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest' || YII_ENV_TEST) {
                // AJAX request
                $response->data = '<pre>' . $this->htmlEncode($this->convertExceptionToString($exception)) . '</pre>';
            } else {
                // if there is an error during error rendering it's useful to
                // display PHP error in debug mode instead of a blank screen
                if (YII_DEBUG) {
                    ini_set('display_errors', 1);
                }
                $file = $useErrorView ? $this->errorView : $this->exceptionView;
                $response->data = $this->renderFile($file, [
                    'exception' => $exception,
                ]);
            }
        } elseif ($response->format === Response::FORMAT_RAW) {
            $response->data = $exception;
        } else {
            $response->data = $this->convertExceptionToArray($exception);
        }

        if ($exception instanceof HttpException) {
            $response->setStatusCode($exception->statusCode);
        } else {
            $response->setStatusCode(500);
        }
        
        
        $url=\Yii::$app->request->pathInfo;        	
        $pos = strrpos($url,'?');
        if($pos) {
        	$url = substr($url,0,$pos);
        }
        $urlPath="";
        $tempUrlArr = explode('/',$url);
        if (count($tempUrlArr)==3){
        	$module=$tempUrlArr[0];
        	$controller=$tempUrlArr[1];
        	$action=$tempUrlArr[2];
        	$urlPath=$module."_".$controller."_".$action;
        }else if (count($tempUrlArr)==2){
        	$module=$tempUrlArr[0];
        	$controller=$tempUrlArr[1];
        	$urlPath=$module."_".$controller;
         }else{
			 $urlPath=str_replace("/", "_", $url);
		 }

      if(!isset(\Yii::$app->params["isHideError"]) || \Yii::$app->params["isHideError"] == 1){ 
        $filename=date("Ymd_His")."_".rand(10,1000)."_".$urlPath.".html";      
        //$filename=\Yii::getAlias('@runtime').DIRECTORY_SEPARATOR."logs".DIRECTORY_SEPARATOR."error_pages".DIRECTORY_SEPARATOR.$filename;      
        $filename=\Yii::getAlias('@runtime').DIRECTORY_SEPARATOR."logs".DIRECTORY_SEPARATOR.$filename;
        $fp=fopen($filename,"w");
        fwrite($fp,$response->data);
      }else{
      	  $response->send();
      }
      
    }

  
    
   
}
