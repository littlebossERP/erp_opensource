<?php
namespace eagle\controllers;

use Yii;
use yii\web\Controller;
use eagle\models\SystemReleaseNotes;


class BasicController extends \yii\web\Controller{
    public $enableCsrfValidation = FALSE;
    
    //更新记录
    public function actionShowNotes(){
        $notes=SystemReleaseNotes::find()->orderBy(['release_date'=>SORT_DESC])->limit(10)->all();
        return $this->render('shownotes',['result'=>$notes]);
    }
    
}