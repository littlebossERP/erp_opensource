<?php
namespace  eagle\modules\util\controllers;

use yii\web\Controller;
use eagle\modules\util\models\LabelTip;
/*+----------------------------------------------------------------------
| 小老板
+----------------------------------------------------------------------
| Copyright (c) 2011 http://www.xiaolaoban.cn All rights reserved.
+----------------------------------------------------------------------
| Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
+----------------------------------------------------------------------
| Author: xjq
+----------------------------------------------------------------------
| Create Date: 2014-09-26
+----------------------------------------------------------------------
 */

/**
 +------------------------------------------------------------------------------
 * 提示类
 +------------------------------------------------------------------------------
 * @category	application
 * @package		Controller/index
 * @subpackage  Exception
 * @author		dzt
 * @version		1.0
 +------------------------------------------------------------------------------
 */

class QtipController extends Controller {
	
	public function actionGettip(){
		if (empty($_GET["tipkey"])){
			echo " ";
			return;
		}

		$labelTip = LabelTip::find()->where("tip_key=:tip_key",array(":tip_key"=>$_GET["tipkey"]))->one();
		if ( $labelTip <> null ){
			echo $labelTip->tip;
			return;
		}
		
		echo " ";
		return;		
	}
	
	
}


?>
