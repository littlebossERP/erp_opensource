<?php

namespace eagle\modules\html_catcher\controllers;

use yii\web\Controller;
use eagle\modules\html_catcher\helpers\HtmlCatcherHelper;



class HtmlCatcherController extends Controller
{
    public function actionIndex()
    {
        return $this->render('index');
    }
    
    public function actionCronjob(){
    	set_time_limit(0);
    	HtmlCatcherHelper::queueHandlerProcessing1('','cdiscount');
    }
    
    public function actionTest(){
    	echo "123";
    	return ;
    	$url = "http://www.cdiscount.com/search/10/sie4242003602980.html#_his_";
    	$roleSetting = [];
    	$roleSetting = HtmlCatcherHelper::getActiveRoleSetting('cdiscount');
    	$roleSetting = json_decode($roleSetting[0]['content'],true);
    
    	
    	$result = HtmlCatcherHelper::analyzeHtml($url,$roleSetting);
    	var_dump($result);
    	return;
    	$puid = '1';
    	$product_id = 'A1AJ19PSB66TGU';
    	$platform = 'amazon';
    	$subsite = 'cn';
    	$field_list = ['image','price','title','description'];
    	$callback = 'echo "callback is ok ";';
    	$result = HtmlCatcherHelper::requestCatchHtml($puid, $product_id, $platform, $field_list,$subsite , $callback);
    	var_dump($result);
    	/*
    	$url = "www.baidu.com";
    	
    	$result = HtmlCatcherHelper::getHtmlData($url);
    	echo $result['Response'];
    	*/
    	//
    }
    
    public function actionGetRole(){
    	//getActiveRoleSetting
    	$result = HtmlCatcherHelper::getActiveRoleSetting('cdiscount');
    	
    	echo json_encode($result);
    }
    
    public function actionSetRole(){
    	
    	$content = [
    		'title'=>['.prdtBTit'=>''],
    		'price'=>['.prdtPrice'=>''],
    		'description'=>['.prdtBDesc'=>''],
    		'primary_image'=>['.prdtBPCar>li>img'=>'data-src'],
    		'other_image'=>['.prdtBPCar>li[data-src]'=>'data-src']
    	];
    	
    	
    	
    	
    	
    	echo json_encode($content);
    	
    }
    
    public function actionTest_callback(){
    	return HtmlCatcherHelper::callback_test('1', 'auc2009972099508');
    	
    }
    
    public function actionTest_1(){
    	$thisResult = <<<thisString
{"AssociatedProducts":null,"BestOffer":{"Condition":"New","Id":"27342318","IsAvailable":true,"PriceDetails":{"Discount":{"EndDate":"0001-01-01T00:00:00","StartDate":"0001-01-01T00:00:00","Type":"StrikedPrice"},"ReferencePrice":"0","Saving":null},"ProductURL":"http:\/\/www.cdiscount.com\/opa.aspx\/?trackingid=s_PVtLNfN5VHGXxaJwE31IFKmIp-8LRExBPL6LsE4Vvzxd4ZU48m6jZnmSIK_aYG&action=product&id=AUC3662440032484&offerid=27342318","SalePrice":"7.7200","Seller":{"Id":"11648","Name":"GAME BOX CO LIMITED"},"Shippings":null,"Sizes":null},"Brand":"AUCUNE","Description":"Exclusivement con\u00e7u pour Samsung Galaxy Tab 10.1 3 pouces Tablet (GT-P5200 \/ P5210 \/ P5220), fabriqu\u00e9 \u00e0 partir de cuir PU premium Handcrafted De couverture peut \u00eatre retourn\u00e9 et pli\u00e9 pour cr\u00e9er un stand. D\u00e9coupes pr\u00e9cises permettent un acc\u00e8s facile \u00e0 tous les boutons, commandes et les ports secondaires. Doublure int\u00e9rieure douce pour prot\u00e9ger l'\u00e9cran de votre onglet, Satisfaction de la qualit\u00e9 100% garanti","Ean":"3662440032484","Id":"AUC3662440032484","Images":[{"ImageUrl":"http:\/\/i2.cdscdn.com\/pdt2\/4\/8\/4\/1\/700x700\/AUC3662440032484.jpg","ThumbnailUrl":"http:\/\/i2.cdscdn.com\/pdt2\/4\/8\/4\/1\/040x040\/AUC3662440032484.jpg"},{"ImageUrl":"http:\/\/i2.cdscdn.com\/pdt2\/4\/8\/4\/2\/700x700\/AUC3662440032484.jpg","ThumbnailUrl":"http:\/\/i2.cdscdn.com\/pdt2\/4\/8\/4\/2\/040x040\/AUC3662440032484.jpg"},{"ImageUrl":"http:\/\/i2.cdscdn.com\/pdt2\/4\/8\/4\/3\/700x700\/AUC3662440032484.jpg","ThumbnailUrl":"http:\/\/i2.cdscdn.com\/pdt2\/4\/8\/4\/3\/040x040\/AUC3662440032484.jpg"},{"ImageUrl":"http:\/\/i2.cdscdn.com\/pdt2\/4\/8\/4\/4\/700x700\/AUC3662440032484.jpg","ThumbnailUrl":"http:\/\/i2.cdscdn.com\/pdt2\/4\/8\/4\/4\/040x040\/AUC3662440032484.jpg"}],"MainImageUrl":"http:\/\/i2.cdscdn.com\/pdt2\/4\/8\/4\/1\/300x300\/AUC3662440032484.jpg","Name":"Etui Coque Housse Violet SAMSUNG GALAXY TAB 3 10.1 TABLETTE","Offers":[{"Condition":"New","Id":"27342318","IsAvailable":true,"PriceDetails":{"Discount":{"EndDate":"0001-01-01T00:00:00","StartDate":"0001-01-01T00:00:00","Type":"StrikedPrice"},"ReferencePrice":"0","Saving":null},"ProductURL":"http:\/\/www.cdiscount.com\/opa.aspx\/?trackingid=s_PVtLNfN5VHGXxaJwE31IFKmIp-8LRExBPL6LsE4Vvzxd4ZU48m6jZnmSIK_aYG&action=product&id=AUC3662440032484&offerid=27342318","SalePrice":"7.7200","Seller":{"Id":"11648","Name":"GAME BOX CO LIMITED"},"Shippings":null,"Sizes":null}],"OffersCount":"1","Rating":"3.67"}
thisString;
    	$thisResult = json_decode($thisResult,true);
    	var_dump($thisResult);
    	return;
    	$data = HtmlCatcherHelper::formatterResult($thisResult, 'cdiscount' , ''  , 'openapi-product-foramtter');
    	
    	
    	
    }
    
    
    /**
     * @invoking					./yii cdiscount/test_6
     */
    public function actionTest_6() {
    	echo "\n start  \n";
    	$uid = 1;
    	$userStoreName='gameboxamazon@gmail.com';
    	$changeProd=['AUC2009972099591','AUC2009972099508','AUC2009972017892'];
    	$field_list=array('seller_product_id','img','title','description','brand');
    	$site = '';
    	$callback = 'eagle\modules\listing\helpers\CdiscountOfferSyncHelper::webSiteInfoToDb($uid,$prodcutInfo);';
    	$rtn = HtmlCatcherHelper::requestCatchHtml($uid,$changeProd,'cdiscount',$field_list,$site,$callback);
    }
    
    
}
