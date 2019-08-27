<?php

namespace eagle\modules\catalog\controllers;

use Yii;
use yii\filters\VerbFilter;
use eagle\modules\catalog\helpers\ProductHelper;
use eagle\modules\catalog\helpers\BrandHelper;
use eagle\modules\purchase\helpers\SupplierHelper;
use eagle\modules\catalog\models\Tag;
use yii\db\Query;
use eagle\modules\catalog\models\ProductFieldValue;
use eagle\modules\catalog\helpers\ProductFieldHelper;
use eagle\modules\app\apihelpers\AppTrackerApiHelper;
use eagle\modules\catalog\helpers\MatchingHelper;
use eagle\modules\platform\apihelpers\PlatformAccountApi;
use eagle\models\OdOrder;
use yii\data\Pagination;

/**
 * ProductController implements the CRUD actions for Product model.
 */
class MatchingController extends \eagle\components\Controller{

    public $enableCsrfValidation = false;
    
    //显示现有订单列表
    public function actionIndex(){
    	AppTrackerApiHelper::actionLog("catalog","/catalog/product/index");
    	
    	//绑定平台、店铺信息
    	$platformAccount = [];
    	$stores = [];
    	$platformAccountInfo = PlatformAccountApi::getAllAuthorizePlatformOrderSelleruseridLabelMap(false, false, true);//引入平台账号权限后
    	foreach ($platformAccountInfo as $p_key=>$p_v){
    		if(!empty($p_v)){
    			//已绑定平台
    			$platformAccount[] = $p_key;
    	
    			foreach ($p_v as $s_key=>$s_v){
    				//对应店铺信息
    				$stores[$p_key.'_'.$s_key] = [
    					'id' => $s_key,
    					'name' => $s_v,
    					'platform' => $p_key,
    				];
    			}
    		}
    	}
    	
    	$orderInfo = MatchingHelper::getMatchingOrderInfo($_REQUEST);
    	 
    	return $this->renderAuto('order_list', [
    	        'orderInfo' => $orderInfo,
    	        'menu'=>MatchingHelper::getLeftMenuTree(), //左侧菜单
    	        'menu_active'=>'现有订单',
    			'platformAccount'=>$platformAccount,
    			'stores'=>$stores,
    			'search_con' => $_REQUEST,
    			]);
    
    }
    
    //更新未配对订单
    public function actionRefreshMatching(){
        $ret = MatchingHelper::RefreshMatchingOrder();
        
        return json_encode($ret);
    }
    
    //打开自动识别窗口
    public function actionShowAutomaticMatchingBox()
    {
    	return $this->renderPartial('showautomaticmatchingbox');
    }
    
    //自动识别
    public function actionAutomaticMatching()
    {
    	$params_AM = array();
    	$params_AM['automaticMType'] = empty($_POST['automaticMType']) ? '' : $_POST['automaticMType'];
    	$params_AM['startStr'] = empty($_POST['startStr']) ? '' : $_POST['startStr'];
    	$params_AM['endStr'] = empty($_POST['endStr']) ? '' : $_POST['endStr'];
    	$params_AM['startLen'] = empty($_POST['startLen']) ? '' : $_POST['startLen'];
    	$params_AM['endLen'] = empty($_POST['endLen']) ? '' : $_POST['endLen'];
    	
    	//整理表单条件
    	$params_S = array();
    	if(!empty($_POST['formData'])){
    	    $data_arr = explode('&', $_POST['formData']);
    	    foreach ($data_arr as $a){
    	        $one_arr = explode('=', $a);
    	        if(!empty($one_arr[0]) && !empty($one_arr[1])){
    	            $params_S[$one_arr[0]] = $one_arr[1];
    	        }
    	    }
    	}
    	
    	$type = empty($_POST['type']) ? '' : $_POST['type'];
    	
    	$ret = MatchingHelper::AutomaticMatching($params_AM, $params_S, $type);
    	return json_encode($ret);
    }
    
    //打开现有订单 -> 一键生成商品窗口
    public function actionShowCreateProductBox()
    {
    	$orderInfo = MatchingHelper::GetCreateProductInfo($_REQUEST);
    	
    	return $this->renderPartial('showcreateproductbox',[
    			'orderInfo' => $orderInfo]);
    }
    
    //打开在线产品 -> 一键生成商品窗口
    public function actionShowCreateOnlineProductBox()
    {
    	$platform = empty($_REQUEST['platform']) ? 'ebay' : $_REQUEST['platform'];
    	
    	$_REQUEST['per-page'] = 100;         //最多一次性支持生成100条
    	$_REQUEST['page'] = 0;               
    	$data = MatchingHelper::getProductInfo($_REQUEST, $platform, array(), true);
    	
    	return $this->renderPartial('showcreateonlineproductbox',[
    			'productInfo' => $data['data'],
    			'platform' => $platform,
    			'search_con' => json_encode($_REQUEST),
    			]);
    }
    
    //一键生成商品
    public function actionCreateProduct()
    {
    	$ret = MatchingHelper::CreateProductInfo($_POST);
    	
    	return json_encode($ret);
    }
    
    //显示平台商品列表
    public function actionProductList(){
    	AppTrackerApiHelper::actionLog("catalog","/catalog/matching/product-list");
    	
    	$platform = empty($_REQUEST['platform']) ? 'ebay' : strtolower($_REQUEST['platform']); 
    	//绑定店铺信息
    	$stores = [];
    	$selleruser_info = [];
    	$platformAccountInfo = PlatformAccountApi::getAllAuthorizePlatformOrderSelleruseridLabelMap(false, false, true);//引入平台账号权限后
    	foreach ($platformAccountInfo as $p_key=>$p_v){
    		if($p_key == $platform && !empty($p_v)){
    			foreach ($p_v as $s_key=>$s_v){
    				//对应店铺信息
    				$stores[$s_key] = [
	    				'name' => $s_v,
	    				'platform' => $p_key,
    				];
    				
    				$selleruser_info[$s_key] = $s_v;
    			}
    			break;
    		}
    	}
    	 
    	$productInfo = MatchingHelper::getProductInfo($_REQUEST, $platform, $selleruser_info);
    
    	$personality_info = MatchingHelper::getPersonalityInfo($platform);
    	return $this->renderAuto('online_product_list', [
    			'productInfo' => $productInfo,
    			'menu' => MatchingHelper::getLeftMenuTree(), //左侧菜单
    			'menu_active' => $personality_info['show_name'],
    			'platform' => $platform,
    			'stores' => $stores,
    			'search_con' => $_REQUEST,
    			'personality_info' => $personality_info, //平台个性信息
    			'product_status_arr' => MatchingHelper::getProductStatus($platform), //商品状态
    		]);
    
    }
    
    //在线商品配对
    public function actionChangeMatchingProduct(){
    	if(empty($_POST['pro_id']) || empty($_POST['sku']) || empty($_POST['platform'])){
    		return json_encode(['success' => false, 'msg' => '参数缺省']);
    	}
    	
    	$root_sku = empty($_POST['rootsku']) ? '' : $_POST['rootsku'];
    	$matching_type = empty($_POST['matching_type']) ? 1 : $_POST['matching_type'];
    	$ret = MatchingHelper::ChangeMatchingProduct($_POST['platform'], $_POST['pro_id'], $_POST['sku'], $root_sku, $matching_type);
    	
    	return json_encode($ret);
    }
}
