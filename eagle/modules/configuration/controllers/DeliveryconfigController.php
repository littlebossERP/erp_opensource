<?php

namespace eagle\modules\configuration\controllers;
use \Yii;
use eagle\modules\util\helpers\ConfigHelper;
use yii\helpers\ArrayHelper;
use common\helpers\Helper_Array;
use eagle\modules\catalog\helpers\ProductApiHelper;

class DeliveryconfigController extends \eagle\components\Controller{
	public $enableCsrfValidation = false;
	/**
	 +----------------------------------------------------------
	 * 发货模块习惯设置
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * log			name	    date					note
	 * @author		million 	2015/01/11				初始化
	 +----------------------------------------------------------
	 **/
	public function actionCustomsettings()
	{
		if (\Yii::$app->request->isPost){
			$support_zero_inventory_shipments = $_POST["support_zero_inventory_shipments"];
			$r = ConfigHelper::setConfig("support_zero_inventory_shipments", $support_zero_inventory_shipments);
			$message = $r?'发货模块习惯设置成功！':'发货模块习惯设置失败！';
			return $this->render('customsettings',['result'=>array('result'=>$r,'message'=>$message)]);
		}
		
		return $this->render('customsettings');
	}

    /**
    +----------------------------------------------------------
     * 发货模块-拣货单设置
    +----------------------------------------------------------
     * @access public
    +----------------------------------------------------------
     * log			name	    date					note
     * @author		dwg 	  2015/01/28				初始化
    +----------------------------------------------------------
     **/
    public function actionPickingsettings()
    {
        if (\Yii::$app->request->isPost){
            $no_show_product_image = empty($_POST["no_show_product_image"])?'Y':$_POST["no_show_product_image"];
            ConfigHelper::setConfig("no_show_product_image", $no_show_product_image);
            return $this->render('pickingsettings',['result'=>'success','message'=>'设置成功']);
        }

        return $this->render('pickingsettings');
    }
	
}