<?php

namespace eagle\modules\tracking;

class Module extends \yii\base\Module
{
    public $controllerNamespace = 'eagle\modules\tracking\controllers';

    public function init()
    {
        parent::init();
        \Yii::$app->params["appName"] = "tracker";
        \Yii::$app->params["isOnlyForOneApp"] = 1;
        // custom initialization code goes here
    }
}
