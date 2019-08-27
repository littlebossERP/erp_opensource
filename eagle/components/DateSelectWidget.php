<?php

// namespace yii\jui;

namespace eagle\components;

use Yii;
use yii\base\InvalidParamException;
use yii\helpers\FormatConverter;
use yii\helpers\Html;
use yii\helpers\Json;
use yii\base\Widget;


class DateSelectWidget extends Widget 
{
	public function init() {
		
	}
	
	public function run() {
		return $this->render('DateSelectWidget');
	}
}
?>