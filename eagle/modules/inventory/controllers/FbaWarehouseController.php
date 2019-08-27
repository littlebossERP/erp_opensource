<?php

namespace eagle\modules\inventory\controllers;

use Yii;

use yii\data\ActiveDataProvider;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use eagle\modules\util\helpers\TranslateHelper;
use eagle\modules\util\helpers\StandardConst;

use eagle\modules\inventory\models\Warehouse;

use eagle\modules\util\helpers\ExcelHelper;
use eagle\modules\util\helpers\SwiftFormat;
use eagle\modules\util\helpers\GoogleHelper;
use eagle\modules\util\helpers\GetControlData;
use yii\base\Action;
use yii\data\Sort;
use eagle\widgets\ESort;
use yii\base\ExitException;
use yii\base\Exception;
use eagle\modules\inventory\helpers\WarehouseHelper;
use eagle\modules\inventory\models\WarehouseCoverNation;
use eagle\modules\util\helpers\SysLogHelper;
use eagle\models\SysCountry;
use eagle\modules\inventory\models\ProductStock;
use eagle\models\OdOrder;
use eagle\modules\order\models\OdOrderItem;
use yii\data\Pagination;
use eagle\modules\inventory\apihelpers\InventoryApiHelper;
use eagle\modules\inventory\helpers\InventoryHelper;
use eagle\modules\catalog\helpers\ProductHelper;
use eagle\modules\carrier\helpers\CarrierOpenHelper;
use yii\db\Query;
use eagle\modules\carrier\models\SysCarrierAccount;
use eagle\modules\inventory\models\Wh3rdPartyStockage;
use eagle\modules\inventory\helpers\FbaWarehouseHelper;
use eagle\modules\catalog\models\Product;
use eagle\modules\amazon\apihelpers\AmazonUserApiHelp;
use eagle\modules\amazon\apihelpers\AmazonApiHelper;

class FbaWarehouseController extends \eagle\components\Controller
{
	public $enableCsrfValidation = false; //非网页访问方式跳过通过csrf验证的 . 如: curl 和 post man

	/**
	 +---------------------------------------------------------------------------------------------
	 * 显示fba库存列表界面
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param
	 +---------------------------------------------------------------------------------------------
	 * @return
	 +---------------------------------------------------------------------------------------------
	 * log			name		date			note
	 * @author		lrq		  2016/12/05		初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	public function actionIndex()
	{
	    try {
    	    $merchant_id = empty($_POST['merchant_id']) ? '' : $_POST['merchant_id'];
    	    $marketplace_id = empty($_POST['marketplace_id']) ? '' : $_POST['marketplace_id'];
    	    $pageSize = empty($_POST['per_page']) ? 20 : $_POST['per_page'];
    	    $page = isset($_POST['page']) ? $_POST['page'] : 0;
    	    $skus = empty($_POST['skus']) ? '' : $_POST['skus'];
    	    $type = empty($_POST['type']) ? '1' : $_POST['type'];
    	    
    	    if(!empty($skus)){
    	        $page = 0;
    	    }
    
            if(!empty($_GET['sort'])){
            	$sort = $_GET['sort'];
            	if( '-' == substr($sort,0,1) ){
            		$sort = substr($sort,1);
            		$order = 'desc';
            	} else {
            		$order = 'asc';
            	}
            }else{
            	$sort = 'seller_sku';
            	$order = 'asc';
            }
            
            $sortConfig = new Sort(['attributes' => ['seller_sku']]);
            if(!in_array($sort, array_keys($sortConfig->attributes))){
            	$sort = '';
            	$order = '';
            }
            
            //amazom店铺、站点组
            $accountMaketplaceMap = AmazonUserApiHelp::getAccountMaketplaceMap();
            
            //默认第一个站点
            if(empty($marketplace_id) && !empty($accountMaketplaceMap)){
            	$acc = $accountMaketplaceMap[0];
            	if(!empty($acc['merchant']['merchant_id'])){
            		$merchant_id = $acc['merchant']['merchant_id'];
            
            		if(!empty($acc['marketplace'][0])){
            			$marketplace_id = $acc['marketplace'][0]['marketplace_id'];
            		}
            	}
            }
            
            $queryString = array();
            $queryString['account_id'] = $merchant_id;
            $queryString['marketplace_id'] = $marketplace_id;
            $queryString['type'] = $type;
            if(!empty($skus))
            {
                $queryString['seller_sku'] = explode(',',$skus);
            }
            
            //查询库存信息
            $stock = FbaWarehouseHelper::listWarehouseData($page, $pageSize, $sort, $order, $queryString);
            
            $stock['per_page'] = $pageSize;
            $stock['page'] = $page;
            $stock['skus'] = $skus;
            
            //平台绑定连接
			list($Amazon_url,$label) = \eagle\modules\app\apihelpers\AppApiHelper::getPlatformMenuData();
            
            if(!empty($accountMaketplaceMap))
                $Amazon_url = '';
            
            return $this->renderAuto('index', 
        	        [
                        'stockData' => $stock, 
                        'accountMaketplaceMap' => $accountMaketplaceMap,
                		'sort'=>$sortConfig,
                        'merchant_id' => $merchant_id,
                		'marketplace_id'=>$marketplace_id,
                        'Amazon_url' => $Amazon_url,
                        'type' => $type,
        	        ]
            );
	    }
	    catch (\Exception $ex){
	        return $this->renderAuto('index',
	        		[
    	        		'stockData' => array(),
    	        		'accountMaketplaceMap' => array(),
    	        		'sort'=>$sortConfig,
    	        		'merchant_id' => $merchant_id,
    	        		'marketplace_id'=>$marketplace_id,
    	        		'Amazon_url' => '',
	                    'type' => $type,
	        		]
	        );
	    }
	}
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 同步fba SKU和库存
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param
	 +---------------------------------------------------------------------------------------------
	 * @return
	 +---------------------------------------------------------------------------------------------
	 * log			name		date			note
	 * @author		lrq		  2016/12/05		初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	public function actionSynchronizeFbaSku()
	{
		$ret['status'] = '1';
		$ret['msg'] = '';
		$merchant_id = empty($_POST['merchant_id']) ? '' : $_POST['merchant_id'];
		$marketplace_id = empty($_POST['marketplace_id']) ? '' : $_POST['marketplace_id'];
			
		$reponse = AmazonApiHelper::webSyncAmzFbaInventory($merchant_id,$marketplace_id);
		if(!empty($reponse) && is_array($reponse))
		{
		   if($reponse['success'] != 1) {
		       $ret['status'] = '0';
		       $ret['msg'] = $reponse['message'];
		       
		       if($ret['msg'] == 'request return null request id;'){
		           $ret['msg'] = '无fba库存信息返回！';
		       }
		   }
		}
		
		return json_encode($ret);
	}
}








