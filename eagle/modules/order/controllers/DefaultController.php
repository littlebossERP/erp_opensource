<?php

namespace eagle\modules\order\controllers;

use yii\web\Controller;
use eagle\modules\util\helpers\ConfigHelper;

class DefaultController extends Controller
{
    public function actionIndex()
    {
    	echo "ss";exit;
        return $this->render('index');
    }
    
    public function actionTestGetConfig(){
    	$path = $_REQUEST['path'];
    	$r = ConfigHelper::getConfig($path);
    	echo "$path value=".print_r($r,true);
    }
}
