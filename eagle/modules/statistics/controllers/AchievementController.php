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
use eagle\modules\app\apihelpers\AppTrackerApiHelper;
use eagle\modules\util\helpers\ExcelHelper;

/**
 +----------------------------------------------------------
 * 统计
 +----------------------------------------------------------
 * log			name	date					note
 * @author		lrq 	2017/03/27				初始化
 +----------------------------------------------------------
 **/
class AchievementController extends \eagle\components\Controller
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
	 * 显示运营汇总台头信息
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		lrq 	2017/03/27				初始化
	 +----------------------------------------------------------
	 **/
    public function actionIndex()
    {
    	AppTrackerApiHelper::actionLog("statistics", "/statistics/achievement/index");
    	
        //绑定平台、店铺信息
        $platformAccount = [];
        $stores = [];
        $platformAccountInfo = PlatformAccountApi::getAllAuthorizePlatformOrderSelleruseridLabelMap(false, false, true);//引入平台账号权限后
        foreach ($platformAccountInfo as $p_key=>$p_v)
        {
            if(!empty($p_v))
            {
                //已绑定平台
                $platformAccount[] = $p_key;
                
                foreach ($p_v as $s_key=>$s_v)
                {
                    //对应店铺信息
                    $stores[$s_v.' ( '.$p_key.' )'] = $s_key;
                }
            }
        }
        
        $sortConfig = new Sort(['attributes' => []]);
        
        //查询是否有显示权限
        $ischeck = UserApiHelper::checkOtherPermission('profix');
        
        return $this->render('index', [
            'profitData' => '',
        	'sort'=>$sortConfig,
            'platformAccount'=>$platformAccount,
            'stores'=>$stores,
            'ischeck'=>$ischeck,
        ]);
    }
    
    /**
     +----------------------------------------------------------
     * 获取运营汇总列表
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * log			name	date					note
     * @author		lrq 	2017/03/27				初始化
     +----------------------------------------------------------
     **/
    public function actionGetAchievementInfo()
    {
        $params = [];
        $params['page'] = empty($_REQUEST['page']) ? 0 : $_REQUEST['page'];
        $params['per-page'] = empty($_REQUEST['per-page']) ? 20 : $_REQUEST['per-page'];
        $params['start_date'] = empty($_REQUEST['start_date']) ? '' : $_REQUEST['start_date'];
        $params['end_date'] = empty($_REQUEST['end_date']) ? '' : $_REQUEST['end_date'];
        $params['selectplatform'] = empty($_REQUEST['selectplatform']) ? '' : explode(';', rtrim($_REQUEST['selectplatform'],';'));
        $params['selectstore'] = empty($_REQUEST['selectstore']) ? '' : explode(';', rtrim($_REQUEST['selectstore'],';'));
        $params['period'] = empty($_REQUEST['period']) ? 'D' : $_REQUEST['period'];
        $params['currency'] = empty($_REQUEST['currency']) ? 'USD' : $_REQUEST['currency'];
        $params['order_type'] = empty($_REQUEST['order_type']) ? '' : explode(';', rtrim($_REQUEST['order_type'],';'));
        $is_sum = empty($_REQUEST['selectType']) ? 1 : 0;
        
    	$ret = StatisticsHelper::getAchievementInfo($params, $is_sum);
    	
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
     * 导出Excel
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * log			name	date					note
     * @author		lrq 	2017/04/05				初始化
     +----------------------------------------------------------
     **/
    public function actionExportExcel()
    {
    	AppTrackerApiHelper::actionLog("statistics", "/statistics/achievement/export-excel");
    
    	$params = [];
        $params['page'] = 1;
        $params['per-page'] = -1;
        $params['start_date'] = empty($_REQUEST['start_date']) ? '' : $_REQUEST['start_date'];
        $params['end_date'] = empty($_REQUEST['end_date']) ? '' : $_REQUEST['end_date'];
        $params['selectplatform'] = empty($_REQUEST['selectplatform']) ? '' : explode(';', rtrim($_REQUEST['selectplatform'],';'));
        $params['selectstore'] = empty($_REQUEST['selectstore']) ? '' : explode(';', rtrim($_REQUEST['selectstore'],';'));
        $params['period'] = empty($_REQUEST['period']) ? 'D' : $_REQUEST['period'];
        $params['currency'] = empty($_REQUEST['currency']) ? 'USD' : $_REQUEST['currency'];
        $params['order_type'] = empty($_REQUEST['order_type']) ? '' : explode(';', rtrim($_REQUEST['order_type'],';'));
        
    	$ret = StatisticsHelper::getAchievementInfo($params, 1);
    	
    	$items_arr = ['time'=>'日期','total_sales_count'=>'订单总量','total_sales_amount_USD'=>'订单总金额','total_profit_cny'=>'订单总利润'];
    	$keys = array_keys($items_arr);
    	$excel_data = [];
    	 
    	foreach ($ret['data'] as $index=>$row)
    	{
    		$row['time'] = $row['thedate'];
    		$tmp=[];
    		foreach ($keys as $key){
    			if(isset($row[$key])){
    				if(in_array($key,['order_id']) && is_numeric($row[$key]))
    					$tmp[$key]=' '.$row[$key];
    				else
    					$tmp[$key]=(string)$row[$key];
    			}
    			else
    				$tmp[$key]=$row[$key];
    		}
    		$excel_data[$index] = $tmp;
    	}
    	
    	ExcelHelper::exportToExcel($excel_data, $items_arr, 'achieve_'.date('Y-m-dHis',time()).".xls", [], true, ['setWidth'=>30]);
    }
}
