<?php

namespace eagle\modules\configuration\controllers;

use yii\web\Controller;
use eagle\modules\util\helpers\ConfigHelper;
use yii\base\Exception;
use yii;
use eagle\modules\order\models\OdOrder;

class DefaultController extends \eagle\components\Controller
{
	public $defaultAction = 'Setconfig';
	public $enableCsrfValidation = false;
    public function actionIndex()
    {
        return $this->render('index');
    }
    
    /**
     * ajax设置配置公共方法
     * @author million 20160509
     */
    public function actionSetconfig(){
    	 
    	$config = isset($_POST['config'])?$_POST['config']:array();
    	if (isset($_POST['path'])){
    		$path =  $_POST['path'];
    	}else{
    		return json_encode(array('success' => false,'message' =>'未指定修改的配置，请联系小老板技术！'));
    	}
    	try {
    		$success = false;
    		//path命名规则“模块_配置民”
    		if (empty($config)){
    			$success = ConfigHelper::setConfig($path, json_encode($config));
    		}else{
    			$value = is_array($config[$path])?json_encode($config[$path]):$config[$path];
    			$success = ConfigHelper::setConfig($path, $value);
    		}
    
    		if (!$success){
    			return json_encode(array('success' => false,'message' =>'设置失败'));
    		}
    	}catch (Exception $e){
    		return json_encode(array(
    				'success' => false,
    				'message' =>$e->getMessage(),
    		));
    	}
    	
    	return  json_encode(array('success'=>true,'message'=>'设置成功'));
    }
}
