<?php

namespace eagle\modules\configuration\controllers;
use \Yii;
use eagle\modules\util\helpers\ConfigHelper;
use yii\helpers\ArrayHelper;
use common\helpers\Helper_Array;
use eagle\modules\catalog\helpers\ProductApiHelper;

class ProductconfigController extends \eagle\components\Controller{
    public $enableCsrfValidation = false;

    //只用于测试SKU规则返回的数据结构
    public function actionTestSkuRuleBack(){
           $sku_later = ProductApiHelper::explodeSku('A00001*1+B00002*3');
           exit(var_dump($sku_later));
    }

    public function actionIndex()
    {
        if (\Yii::$app->request->isPost){
            Helper_Array::removeEmpty($_POST["keyword"]);
            //先保存是否开启sku 解释
            $r = ConfigHelper::setConfig('configuration/productconfig/analysis_rule_active', $_POST['is_active']);
            unset($_POST['is_active']); //释放 is active 
            $data = json_encode($_POST);
            $r = ConfigHelper::setConfig("skurule", $data);
            $message = $r?'保存SKU解析规则成功！':'保存SKU解析规则失败！';
            
            exit(json_encode(array('success'=>$r,'message'=>$message)));
        }
        $skurule_str = ConfigHelper::getConfig("skurule");
//        var_dump($skurule_str);exit;
        if ($skurule_str != null){
            $skurule = json_decode($skurule_str,true);
        }else{
            $skurule = array(
                'firstKey' => 'sku',
                'quantityConnector' => '*',
                'secondKey' => 'quantity',
                'skuConnector' => '+',
                'keyword' =>array(0=>''),
                'firstChar' => '',
                'secondChar' => '',
            	'keyword_rule' => 'open',
            	'substring_rule' => 'open',
            	'split_rule' => 'open',
            );
        }
        
        foreach ($skurule['keyword'] as $index=>$keyword){
            $skurule['keyword'][$index] = \yii\helpers\Html::encode($keyword);
        }
        
        $skurule['firstChar'] = \yii\helpers\Html::encode($skurule['firstChar']);
        $skurule['secondChar'] = \yii\helpers\Html::encode($skurule['secondChar']);
        
        //是否开启sku 解释
        $isActive = ConfigHelper::getConfig('configuration/productconfig/analysis_rule_active');
        
        if (! isset($isActive)){
        	$isActive = 0;
        }
		
        $skurule['is_active'] = $isActive;
        return $this->render('analysis_rule',['skurule'=>$skurule]);
    }

    public function actionTestSkuRule()
    {
        //是否需要解析完的sku删除“”空格
        if (\Yii::$app->request->isPost) {
            $dataArr = $_POST;
            $message = null;
            //print_r($dataArr);exit;

            /**--------步骤一：“前后缀关键字”解析 ----------*/
            $sku_ago = $dataArr['sku_ago'];     //解析前的捆绑SKU
            $keywordArr = $dataArr['keyword'];
            $str_one = str_replace($keywordArr, "", $sku_ago);

            /**--------步骤二：“SKU截取规则”解析 ----------*/
            $firstChar = $dataArr['firstChar'];
            $secondChar = $dataArr['secondChar'];

            if($firstChar!= '')
            {
                if(strpos($str_one, $firstChar)===false) {
                    $message['error'] = '解析SKU失败！请检查“起始符号”是否存在捆绑SKU中，或是否遵循规则进行填写！';
                    return json_encode($message);
                }
            }
            if($secondChar != '')
            {
                if(strpos($str_one, $secondChar)===false) {
                    $message['error'] = '解析SKU失败！请检查“终止符号”是否存在捆绑SKU中，或是否遵循规则进行填写！';
                    return json_encode($message);
                }
            }


            if (($firstChar != '') && ($secondChar != '') && ($firstChar != $secondChar)) {
                $str_two = self::_cut($firstChar, $secondChar, $str_one);
                //print_r($str_two);
            }
            if (($firstChar == '') && ($secondChar == '')) {
                $str_two = $str_one;
                //print_r($str_two);
            }
            if (($firstChar != '') && ($secondChar == '')) {
                $strStart = mb_strpos($str_one, $firstChar);
                $str_two = mb_substr($str_one, $strStart + 1);
                //print_r($str_two);
            }
            if (($firstChar == '') && ($secondChar != '')) {
                $strEnd = mb_strpos($str_one, $secondChar);
                $str_two = mb_substr($str_one, 0, $strEnd);
                //print_r($str_two);
            }
            if (($firstChar != '') && ($secondChar != '') && ($firstChar == $secondChar)) {
                $strStart = mb_strpos($str_one, $firstChar);
                $strEnd = self::_check($str_one, $secondChar, 2);
                $str_two = mb_substr($str_one, $strStart + 1, $strEnd - $strStart - 1);
                //print_r($str_two);
            }

            /**--------步骤三：“捆绑SKU拆分规则”解析 ----------*/
            $firstKey = $dataArr['firstKey'];
            $secondKey = $dataArr['secondKey'];
            $skuConnector = $dataArr['skuConnector'];
            $quantityConnector = $dataArr['quantityConnector'];
            $split_rule = $dataArr['split_rule'];

            if($split_rule=='open')
            {
                if($quantityConnector==''){
                    $message['error'] = '解析SKU失败！捆绑SKU拆分规则：连接符1 必填！';
                    return json_encode($message);
                }
                if($skuConnector==''){
                    $dataFinal = $str_two;
                }

                if($quantityConnector!='') {
                    if(strpos($str_two, $quantityConnector)===false) {
                        $message['error'] = '解析SKU失败！捆绑SKU拆分规则：连接符1 不存在捆绑SKU中！';
                        return json_encode($message);
                    }
                }

                if($skuConnector!='') {
                    if(strpos($str_two, $skuConnector)===false ) {
                        $message['error'] = '解析SKU失败！捆绑SKU拆分规则：连接符2 不存在捆绑SKU中！';
                        return json_encode($message);
                    }
                }
            }

            if($skuConnector=='' && $quantityConnector=='') {
                $dataFinal = $str_two;
                //    return json_encode(array('dataFinal'=>$dataFinal));
            }

            if($skuConnector!='' && $quantityConnector!='')
            {
                $sku_quantity_arr = explode($skuConnector, $str_two);
                $str_three = null;

                foreach ($sku_quantity_arr as $sku_quantity) {
                    $tmp_arr = explode($quantityConnector, $sku_quantity);

                    if ($firstKey == 'sku') {
                        if (count($tmp_arr) == 1) {
                            $sku = $tmp_arr[0];
                            $quantity = 1;
                            $str_three .= $sku . $quantityConnector . $quantity . $skuConnector;
                        } else {
                            list($sku, $quantity) = $tmp_arr;
                            (integer)$quantity;
                            $str_three .= $sku . $quantityConnector . $quantity . $skuConnector;
                        }
                    }
                    else {
                        if (count($tmp_arr) == 1) {
                            $sku = $tmp_arr[0];
                            $quantity = 1;
                            $str_three .= $quantity . $quantityConnector . $sku . $skuConnector;
                        }
                        else {
                            list($sku, $quantity) = $tmp_arr;
                            (integer)$quantity;
                            $str_three .= $quantity . $quantityConnector . $sku . $skuConnector;
                        }
                    }
                }
                $dataFinal = rtrim($str_three, $skuConnector);
            }

            if(!empty($dataFinal)){
                $message['success'] = '解析SKU成功！';
            }

            return json_encode(array('dataFinal'=>$dataFinal,'success'=>$message));

        }
    }

    //截取特定2个字符之间的字符串
    static public function _cut($begin,$end,$str){
        $b = mb_strpos($str,$begin) + mb_strlen($begin);
        $e = mb_strpos($str,$end) - $b;
        return mb_substr($str,$b,$e);
    }

    //检查特定字符在字符串中第N次出现的位置
    static public function _check($s, $s1, $n){
        $s = '@'.$s;
        $j = $x = $y = 0;
        for($i = 0; $i < strlen($s); $i++){
            if($index = strpos($s, $s1, $y? ($y + 1):$y)){
                $j++;
                if($j == $n){
                    $x = $index;
                    break;
                }else{
                    $y = $index;
                }
            }
        }
        return $x - 1;
    }

    static public function _delete($begin,$end,$str){

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
