<?php

namespace eagle\modules\app\controllers;

use yii\web\Controller;
use eagle\models\UserInfo;

class DefaultController extends Controller
{
    public function actionIndex()
    {
        return $this->render('index');
    }
    
 
}
