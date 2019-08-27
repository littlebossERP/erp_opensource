<?php
/**
 * @link http://www.witsion.com/
* @copyright Copyright (c) 2014 Yii Software LLC
* @license http://www.witsion.com/
*/
namespace eagle\modules\purchase\helpers;

use eagle\modules\purchase\models;
use eagle\modules\purchase\models\Purchase;
use eagle\modules\purchase\models\Supplier;
use eagle\modules\purchase\helpers\SupplierHelper;

use yii;
use yii\db\Query;
use yii\data\Pagination;
use eagle\modules\purchase\models\PurchaseItems;
use eagle\modules\catalog\models\Product;
use eagle\modules\util\helpers\TimeUtil;
use eagle\modules\util\helpers\TranslateHelper;
use eagle\modules\util\helpers\OperationLogHelper;
use eagle\modules\util\helpers\ExcelHelper;
use eagle\modules\purchase\models\ShippingMode;


/**
 * BaseHelper is the base class of module BaseHelpers.
 *

 */
class PurchaseShippingHelper {
	
	public static function getAllShippingModes()
	{
		self::createDefaultShippingMode();
		$rows = ShippingMode::find()->asArray()->All();
		return $rows;
	}

	public static function getShippingModeById($id)
	{
		self::createDefaultShippingMode();
		$result = ShippingMode::findOne($id);
		return $result;
	}
	
	public static function getShippingModeIdByName($shipping_name)
	{
		$result=0;
		self::createDefaultShippingMode();
		$row = ShippingMode::findOne(['shipping_name'=>$shipping_name]);
		if($row<>null){
			$result = $row->shipping_id;
		}else{
			$model=new ShippingMode();
			$model->shipping_name =$shipping_name;
			$model->create_time =TimeUtil::getNow();
			$model->capture_user =\Yii::$app->user->id;
			$model->save(false);
			$result = $model->shipping_id;
		}
		return $result;
	}
	
	private static function createDefaultShippingMode(){
		//自动创建id=0的默认快递方式
		if (ShippingMode::findOne(0)==null){
			$model=new ShippingMode();
			$model->shipping_name ='(未指定)';
			$model->create_time =TimeUtil::getNow();
			$model->capture_user =0;
			$rtn = $model->save(false);
			if (!empty($rtn)){
				$model->shipping_id = 0;
				$model->save(false);
			}
		}
	}
	
}






?>