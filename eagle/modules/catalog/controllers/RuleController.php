<?php

namespace eagle\modules\catalog\controllers;
use \Yii;
use eagle\modules\util\helpers\ConfigHelper;
use yii\helpers\ArrayHelper;
use common\helpers\Helper_Array;
use eagle\modules\catalog\helpers\ProductApiHelper;

class RuleController extends \eagle\components\Controller{
	public $enableCsrfValidation = false;
    public function actionIndex()
    { 
    	if (\Yii::$app->request->isPost){
    		Helper_Array::removeEmpty($_POST["keyword"]);
    		$data = json_encode($_POST);
    		$r = ConfigHelper::setConfig("skurule", $data);
    		$message = $r?'保存SKU解析规则成功！':'保存SKU解析规则失败！';
    		exit(json_encode(array('success'=>$r,'message'=>$message)));
    		
    	}
    	$skurule_str = ConfigHelper::getConfig("skurule");
    	if ($skurule_str != null){
    		$skurule = json_decode($skurule_str,true);
    	}else{
    		$skurule = array(
    				'firstKey' => 'sku',
    				'quantityConnector' => '*',
    				'secondKey' => 'quantity',
    				'skuConnector' => '+',
    				'keyword' =>array(0=>''),
    		);
    	}
    	
    	return $this->render('index',['skurule'=>$skurule]);
    }
    public function actionTest()
    {
    	$productInfo=[
    			'name'=>'title1',
    			'prod_name_ch'=>'title1 中文配货名',
    			'photo_primary'=>'photo_url',
    			'prod_name_en'=>'title 英文配货名',
    			'declaration_ch'=>'礼物',
    			'declaration_en'=>'wwww',
    			'declaration_value_currency'=>'USD',
    			'declaration_value'=>12,
    			'prod_weight'=>50,
    			'battery'=>'N',
    			'platform'=>'ebay',
    			'itemid'=>'123321',
    	];
    	$r = ProductApiHelper::explodeSkuAndCreateProduct('am-A00001*1+B00002*3-ebay', $productInfo);
    	var_dump($r);die;
    }

}
