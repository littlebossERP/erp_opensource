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
use eagle\modules\inventory\helpers\OverseaWarehouseHelper;
use eagle\modules\catalog\models\Product;
use eagle\modules\carrier\models\SysCarrier;

/**
+------------------------------------------------------------------------------
* 仓库盘点处理 控制类
+------------------------------------------------------------------------------
* @category		Inventory
* @package		Controller/Warehouse
* @subpackage   Exception
* @version		1.0
+------------------------------------------------------------------------------
*/

class OverseaWarehouseController extends \eagle\components\Controller
{
	public $enableCsrfValidation = false; //非网页访问方式跳过通过csrf验证的 . 如: curl 和 post man

	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 显示海外仓库存列表界面
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param
	 +---------------------------------------------------------------------------------------------
	 * @return
	 +---------------------------------------------------------------------------------------------
	 * log			name		date			note
	 * @author		lrq		  2016/11/18		初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	public function actionIndex()
	{
	    $warehouse_id = empty($_POST['warehouse_id']) ? '' : $_POST['warehouse_id'];
	    $carrier_code = empty($_POST['carrier_code']) ? '' : $_POST['carrier_code'];
	    $third_party_code = empty($_POST['third_party_code']) ? '' : $_POST['third_party_code'];
	    $accountid = empty($_POST['accountid']) ? '' : $_POST['accountid'];
	    $pageSize = empty($_POST['per_page']) ? 20 : $_POST['per_page'];
	    $page = isset($_POST['page']) ? $_POST['page'] : 0;
	    $skus = empty($_POST['skus']) ? '' : $_POST['skus'];

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
        
        $sortConfig = new Sort(['attributes' => ['sku','seller_sku']]);
        if(!in_array($sort, array_keys($sortConfig->attributes))){
        	$sort = '';
        	$order = '';
        }
        
        //已开启的海外仓仓库
        $OverseaWarehouse = OverseaWarehouseHelper::Get_OverseaWarehouse();
        
        //查询已开启的海外仓
        $carrier_use = [];
        foreach ($OverseaWarehouse as $v){
            if(!in_array($v['carrier_code'], $carrier_use)){
                $carrier_use[] = $v['carrier_code'];
            }
        }
        //查询未授权所有的海外仓信息
        $carrier_not_use = [];
        $carrier = SysCarrier::find()->select(['carrier_code','carrier_name'])->where(['carrier_type'=>'1', 'is_active'=>'1'])->asArray()->all();
        foreach ($carrier as $v){
            if(!in_array($v['carrier_code'], $carrier_use)){
                $carrier_not_use[] = $v;
            }
        }
        
        //当未选择时海外仓时，默认第一个
        if(empty($carrier_code) && count($OverseaWarehouse)> 0){
        	$carrier_code = $OverseaWarehouse[0]['carrier_code'];
        	$third_party_code = $OverseaWarehouse[0]['third_party_code'];
        	$warehouse_id = $OverseaWarehouse[0]['warehouse_id'];
        }
        
        //绑定账号
        $account = [];
        $carrierAccList = SysCarrierAccount::find()->select(['id','carrier_name','warehouse'])->where(['carrier_code'=>$carrier_code, 'carrier_type'=>'1', 'is_used'=>'1'])->all();
        foreach ($carrierAccList as $acc){
            foreach ($acc->warehouse as $wa){
                if($wa == $third_party_code){
                    $account[] = [
                        'accountid' => $acc['id'],
                        'carrier_name' => $acc['carrier_name'],
                        'third_party_code' => $third_party_code,
                    ];
                    break;
                }
            }
        }
        
        //当选择账号为空时，默认第一个账号
        if(empty($accountid) && count($account)> 0){
            $accountid = $account[0]['accountid'];
        }
        
        $queryString = array();
        $queryString['warehouse_id'] = $warehouse_id;
        $queryString['account_id'] = $accountid;
        if(!empty($skus)){
            $queryString['seller_sku'] = explode(',',$skus);
        }
        
        //查询库存信息
        $stock = OverseaWarehouseHelper::listWarehouseData($page, $pageSize, $sort, $order, $queryString);
        
        $stock['per_page'] = $pageSize;
        $stock['page'] = $page;
        $stock['skus'] = $skus;
        
        return $this->renderAuto('index', 
    	        [
                    'stockData' => $stock, 
                    'account' => $account,
            		'sort'=>$sortConfig,
                    'third_party_code' => $third_party_code,
            		'OverseaWarehouse'=>$OverseaWarehouse,
                    'carrier_code' => $carrier_code,
                    'third_party_code' => $third_party_code,
                    'accountid' => $accountid,
                    'warehouse_id' => $warehouse_id,
                    'carrier_not_use' => $carrier_not_use,
    	        ]
          );
	}
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 同步海外仓SKU和库存
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param
	 +---------------------------------------------------------------------------------------------
	 * @return
	 +---------------------------------------------------------------------------------------------
	 * log			name		date			note
	 * @author		lrq		  2016/11/18		初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	public function actionSynchronizeOverseaWSku()
	{
	    $ret['status'] = '1';
	    $ret['msg'] = '';
		$warehouse_id = empty($_POST['warehouse_id']) ? '' : $_POST['warehouse_id'];
		$third_party_code = empty($_POST['third_party_code']) ? '' : $_POST['third_party_code'];
		$accountid = empty($_POST['accountid']) ? '' : $_POST['accountid'];
		 
		$params['warehouse_code'] = $third_party_code;
		$params['accountid'] = $accountid;
		 
		$data = CarrierOpenHelper::getPubOverseasWarehouseStockList($params);
		if(!empty($data) && is_array($data)){
			if($data['error'] === 0){
				$OverseasWarehouseStockList = $data['data'];
				$skus = array();
				foreach ($OverseasWarehouseStockList as $val ){
					$skus[] = $val['sku'];
					
					$warhouse = Wh3rdPartyStockage::findOne(['warehouse_id' => $warehouse_id, 'account_id' => $accountid, 'seller_sku' => $val['sku']]);
					if(empty($warhouse)){
						$warhouse = new Wh3rdPartyStockage();
						$warhouse->warehouse_id = $warehouse_id;
						$warhouse->account_id = $accountid;
						$warhouse->seller_sku = $val['sku'];
						
						$warhouse->sku = "";
					}
					
					$warhouse->title = empty($val['productName']) ? '' : $val['productName'];
					$warhouse->current_inventory = empty($val['stock_actual']) ? 0 : $val['stock_actual'];
					$warhouse->reserved_inventory = empty($val['stock_reserved']) ? 0 : $val['stock_reserved'];
					$warhouse->adding_inventory = empty($val['stock_pipeline']) ? 0 : $val['stock_pipeline'];
					$warhouse->usable_inventory = empty($val['stock_usable']) ? 0 : $val['stock_usable'];
					 
					if(!$warhouse->save()){
					    $ret['status'] = '0';
					    $ret['msg'] = $warhouse->getErrors();
					}
				}
				
				//清除不存在的SKU
				if(count($skus) > 0){
					$dels = Wh3rdPartyStockage::find()->where(['warehouse_id' => $warehouse_id, 'account_id' => $accountid])->andWhere(['not in', 'seller_sku', $skus])->all();
					if(!empty($dels)){
						foreach($dels as $del){
							$del->delete(false);
						}
					}
				}
			}
			else {
			    $ret['status'] = '0';
			    $ret['msg'] = $data['msg'];
			}
		}
		
		return json_encode($ret);
	}
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 单个配对SKU
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param
	 +---------------------------------------------------------------------------------------------
	 * @return
	 +---------------------------------------------------------------------------------------------
	 * log			name		date			note
	 * @author		lrq		  2016/11/18		初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	public function actionMatchingOne()
	{
		$ret['status'] = '1';
		$ret['msg'] = '';
		$stock_id = empty($_POST['stock_id']) ? '' : $_POST['stock_id'];
		$sku = empty($_POST['sku']) ? '' : $_POST['sku'];
		
		$warhouse = Wh3rdPartyStockage::findOne(['id' => $stock_id]);
		if(!empty($warhouse))
		{
		    if(!empty($sku) && $sku != $warhouse->sku)
		    {
    		    //判断当前仓库、账号是否已配对此本地SKU
    		    $warehouse_id = $warhouse->warehouse_id;
    		    $accountid = $warhouse->account_id;
    		    $is_Used = Wh3rdPartyStockage::findOne(['warehouse_id' => $warehouse_id, 'account_id' => $accountid, 'sku' => $sku]);
    		    if(!empty($is_Used))
    		    {
    		        $ret['status'] = '0';
    		        $ret['msg'] = '本地SKU：'.$sku.' 已配对此仓库、此账号的其它海外SKU，不可重复！';
    		        return json_encode($ret);
    		    }
    		    
    		    //判断SKU是否存在
    		    $is_exist_sku = Product::findOne(['sku' => $sku]);
    		    if(empty($is_exist_sku))
    		    {
    		        $ret['status'] = '0';
    		        $ret['msg'] = 'SKU：'.$sku.' 不是已有商品，请录入已有sku商品！';
    		        return json_encode($ret);
    		    }
		    }
		    
		    if($warhouse->sku == $sku)
		    {
		        //跳过
		        $ret['status'] = '2';
		        $ret['msg'] = '';
		    }
		    else 
		    {
		        $warhouse->sku = $sku;
		        if(!$warhouse->save())
		        {
		        	$ret['status'] = '0';
		        	$ret['msg'] = $warhouse->getErrors();
		        }
		    }
		}
		else
		{
		    $ret['status'] = '0';
		    $ret['msg'] = '查询不到此海外仓库存信息！';
		}
		
		return json_encode($ret);
	}
	
	/**
	 +----------------------------------------------------------
	 * 打开 自动配对设置界面
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * log			name		date			note
	 * @author		lrq		  2016/11/24		初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	public function actionShowAutomaticMatchingBox()
	{
		return $this->renderPartial('showautomaticmatchingbox');
	}
	
	/**
	 +----------------------------------------------------------
	 * 开始自动配对
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * log			name		date			note
	 * @author		lrq		  2016/11/24		初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	public function actionAutomaticMatching()
	{
	    $ret['status'] = '1';
	    $ret['msg'] = '';
	    $warehouse_id = empty($_POST['warehouse_id']) ? '' : $_POST['warehouse_id'];
	    $accountid = empty($_POST['accountid']) ? '' : $_POST['accountid'];
	    $matchingType = empty($_POST['matchingType']) ? '' : $_POST['matchingType'];
	    $startStr = empty($_POST['startStr']) ? '' : $_POST['startStr'];
	    $endStr = empty($_POST['endStr']) ? '' : $_POST['endStr'];
	    $startLen = empty($_POST['startLen']) ? '' : $_POST['startLen'];
	    $endLen = empty($_POST['endLen']) ? '' : $_POST['endLen'];
	    
	    $stockArr = Wh3rdPartyStockage::find()->where(['warehouse_id' => $warehouse_id, 'account_id' => $accountid])->all();
	    
	    $matchingQty = 0;    //已配对商品数量
        foreach ($stockArr as $stock)
        {
            $sku = $stock->seller_sku;
            //「海外仓SKU」与「本地SKU」一样时配对
            if($matchingType == 1){
            }
            //忽略前缀、后缀的的「海外仓SKU」与「本地SKU」配对
            else if($matchingType == 2)
            {
                $startArr = array();
                //判断是否含有分隔符 , ， ; ； 、
                $startArr = preg_split('/[,，;；、]+/', $startStr);
                //截取前缀
                foreach ($startArr as $start){
                    if(!empty($start) && strpos($sku, $start) === 0)
                    {
                        $sku = substr($sku, strlen($start));
                        break;
                    }
                }
                $endArr = array();
                //判断是否含有分隔符 , ， ; ； 、
                $endArr = preg_split('/[,，;；、]+/', $endStr);
                //截取后缀
                foreach ($endArr as $end){
                    if(!empty($end) && substr($sku,-strlen($end))==$end)
                    {
                    	$sku = substr($sku, 0, strlen($sku) - strlen($end));
                    	break;
                    }
                }
            }
            //截取后的的「海外仓SKU」与「本地SKU」配对
            else if($matchingType == 3)
            {
            	//起始
            	if($startLen == null || $startLen == '')
            	{
            		$startLen = 1;
            	}
            	//结束
            	if($endLen == null || $endLen == '')
            	{
            		$endLen = strlen($sku);
            	}
            	$sku = substr($sku, $startLen - 1, $endLen - $startLen + 1);
            }
            
            //当此SKU已被配对，则跳过
            $is_Used = Wh3rdPartyStockage::findOne(['warehouse_id' => $warehouse_id, 'account_id' => $accountid, 'sku' => $sku]);
            if(!empty($is_Used))
            {
                continue;
            }
            
            //查询商品库是否存在此SKU
            if(!empty($sku))
            {
                $product = Product::findOne(['sku' => $sku]);
                if(!empty($product))
                {
                    $matchingQty++;
                	$stock->sku = $sku;
                	$stock->save();
                }
            }
	    }
	    
	    $ret['Qty'] = $matchingQty;
	    return json_encode($ret);
	}
}








