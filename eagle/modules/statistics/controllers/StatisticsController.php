<?php

namespace eagle\modules\statistics\controllers;

use Yii;
use yii\base\Exception;
use yii\data\Sort;
use yii\filters\VerbFilter;
use eagle\modules\platform\apihelpers\PlatformAccountApi;
use eagle\modules\util\helpers\ConfigHelper;
use eagle\modules\permission\apihelpers\UserApiHelper;
use eagle\modules\statistics\helpers\StatisticsHelper;
use eagle\widgets\SizePager;
use eagle\modules\util\helpers\ExcelHelper;
use eagle\modules\app\apihelpers\AppTrackerApiHelper;

/**
 +----------------------------------------------------------
 * 统计
 +----------------------------------------------------------
 * log			name	date					note
 * @author		lrq 	2017/03/27				初始化
 +----------------------------------------------------------
 **/
class StatisticsController extends \eagle\components\Controller
{
 
	public $enableCsrfValidation = FALSE;
	
    public function behaviors()
    {
        return [
         	'access' => [
                'class' => \yii\filters\AccessControl::className(),
                'rules' => [
                    [
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'delete' => ['post'],
                ],
            ],
        ];
    }
	
	/**
	 +----------------------------------------------------------
	 * 显示商品表现台头信息
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		lrq 	2017/09/05				初始化
	 +----------------------------------------------------------
	 **/
    public function actionProductDetails()
    {
    	AppTrackerApiHelper::actionLog("statistics", "/statistics/statistics/product_details");
        
        //排序字段
        $sort_list = [
        	'sku' => 'SKU',
        	'order_count' => '订单总量',
        	'total_qty' => '销售总量',
        	'total' => '销售金额',
        ];
        
        //查询是否有显示权限
        $ischeck = UserApiHelper::checkOtherPermission('profix');
        
        return $this->render('/product_details', [
            'ischeck'=>$ischeck,
        	'sort_list' => $sort_list,
        ]);
    }
    
    /**
     +----------------------------------------------------------
     * 获取销售统计列表
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * log			name	date					note
     * @author		lrq 	2017/03/27				初始化
     +----------------------------------------------------------
     **/
    public function actionGetProductDetails()
    {
        $params = [];
        $params['page'] = empty($_REQUEST['page']) ? 0 : $_REQUEST['page'];
        $params['per-page'] = empty($_REQUEST['per-page']) ? 20 : $_REQUEST['per-page'];
        $params['start_date'] = empty($_REQUEST['start_date']) ? '' : $_REQUEST['start_date'];
        $params['end_date'] = empty($_REQUEST['end_date']) ? '' : $_REQUEST['end_date'];
        $params['currency'] = empty($_REQUEST['currency']) ? 'RMB' : $_REQUEST['currency'];
        $params['sort'] = empty($_REQUEST['sort']) ? 'order_source' : $_REQUEST['sort'];
        $params['sorttype'] = empty($_REQUEST['sorttype']) ? '' : $_REQUEST['sorttype'];
        if(!empty($_REQUEST['choose_type'])){
        	$params[$_REQUEST['choose_type']] = empty($_REQUEST['choose_value']) ? '' : $_REQUEST['choose_value'];
        }
        
    	$ret = StatisticsHelper::getProductDetailsInfo($params);
    	
    	//分页信息
    	$ret['pagination'] = 
        	    SizePager::widget(['pagination'=>$ret['pagination'] , 'pageSizeOptions'=>array( 5 , 20 , 50 , 100 , 200 ) , 'class'=>'btn-group dropup']).
        	    '<div class="btn-group" style="width: 49.6%;text-align: right;">'.
        	    	\yii\widgets\LinkPager::widget(['pagination' => $ret['pagination'],'options'=>['class'=>'pagination']]).
        		"</div>";
    	return json_encode($ret);
    }
    
    /**
     +----------------------------------------------------------
     * 商品表现，前端导出
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * log			name	date					note
     * @author		lrq 	2017/09/07				初始化
     +----------------------------------------------------------
     **/
    public function actionDownstageExportExcelProduct()
    {
    	AppTrackerApiHelper::actionLog("statistics", "/statistics/statistics/downstage-export-excel-product");
    	
    	$params = [];
    	$params['page'] = 1;
        $params['per-page'] = -1;
    	$params['start_date'] = empty($_REQUEST['start_date']) ? '' : $_REQUEST['start_date'];
    	$params['end_date'] = empty($_REQUEST['end_date']) ? '' : $_REQUEST['end_date'];
    	$params['currency'] = empty($_REQUEST['currency']) ? 'RMB' : $_REQUEST['currency'];
    	$params['sort'] = empty($_REQUEST['sort']) ? 'order_source' : $_REQUEST['sort'];
    	$params['sorttype'] = empty($_REQUEST['sorttype']) ? '' : $_REQUEST['sorttype'];
    	if(!empty($_REQUEST['choose_type'])){
    		$params[$_REQUEST['choose_type']] = empty($_REQUEST['choose_value']) ? '' : $_REQUEST['choose_value'];
    	}
    	
    	StatisticsHelper::ExportProductDetailsExcel($params, true);
    }
    
    /**
     +----------------------------------------------------------
     * 插入导出Excel队列
     +----------------------------------------------------------
     * @param
     +----------------------------------------------------------
     * log			name		date			note
     * @author		lrq		  2017/04/01		初始化
     +----------------------------------------------------------
     **/
    public function actionBackstageExportExcelProduct(){
    	AppTrackerApiHelper::actionLog("statistics", "/statistics/statistics/backstage-export-excel-product");
    	
    	$params = [];
    	$params['page'] = 1;
    	$params['per-page'] = -1;
    	$params['start_date'] = empty($_REQUEST['start_date']) ? '' : $_REQUEST['start_date'];
    	$params['end_date'] = empty($_REQUEST['end_date']) ? '' : $_REQUEST['end_date'];
    	$params['currency'] = empty($_REQUEST['currency']) ? 'RMB' : $_REQUEST['currency'];
    	$params['sort'] = empty($_REQUEST['sort']) ? 'order_source' : $_REQUEST['sort'];
    	$params['sorttype'] = empty($_REQUEST['sorttype']) ? '' : $_REQUEST['sorttype'];
    	if(!empty($_REQUEST['choose_type'])){
    		$params[$_REQUEST['choose_type']] = empty($_REQUEST['choose_value']) ? '' : $_REQUEST['choose_value'];
    	}
    	$params = json_encode($params);
    	$params = base64_encode($params);
    
    	$className = addslashes('\eagle\modules\statistics\helpers\StatisticsHelper');
    	$functionName = 'ExportProductDetailsExcel';
    
    	$rtn = ExcelHelper::insertExportCrol($className, $functionName, $params, 'export_statistics_product');
    
    	if($rtn['success'] == 1){
    		return $this->renderPartial('/sales/down_load_excel',[
    				'pending_id'=>$rtn['pending_id'],
    				]);
    	}
    	else{
    		return $rtn['message'];
    	}
    }
    
    /**
     +----------------------------------------------------------
     * 查询已导出Excel的路劲
     +----------------------------------------------------------
     * @param
     +----------------------------------------------------------
     * log			name		date			note
     * @author		lrq		  2017/01/01		初始化
     +----------------------------------------------------------
     **/
    public function actionGetExcelUrl(){
    	$pending_id = empty($_POST['pending_id']) ? '0' : $_POST['pending_id'];
    	 
    	return ExcelHelper::getExcelUrl($pending_id);
    }
}
