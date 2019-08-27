<?php

namespace eagle\modules\util\controllers;

use yii\web\Controller;
use Qiniu\json_decode;

class DefaultController extends Controller
{
    public function actionIndex()
    {
        return $this->render('index');
    }
    
    //获取国家列表
    public function actionGetCountryList(){
    	$countryList = \eagle\modules\util\helpers\CountryHelper::getScopeCountry();
    	
    	$common_country = \eagle\modules\util\helpers\ConfigHelper::getConfig('common_country_user');
    	
    	if(empty($common_country)){
    		$common_country_arr = array('RU','BR','US','AU','BY','UA','FR','GB','UK','CA','IL','AR','ES','CL');
    		
    		\eagle\modules\util\helpers\ConfigHelper::setConfig('common_country_user', json_encode($common_country_arr));
    		
    		$common_country = $common_country_arr;
    	}else{
    		$common_country = json_decode($common_country, true);
    	}
    	
    	$selectedCountryArr = array();
    	
    	if(!empty($_GET['selected_country'])){
    		$tmp_selected_country = $_GET['selected_country'];
    		
    		$selectedCountryArr = explode(",",$tmp_selected_country);
    	}

    	if(isset($_GET['is_radio']) && $_GET['is_radio']==1)
    		return $this->renderPartial('countryListRadio' , ['countryList'=>$countryList, 'selectedCountryArr'=>$selectedCountryArr, 'common_country'=>$common_country]);
    	else
    		return $this->renderPartial('countryList' , ['countryList'=>$countryList, 'selectedCountryArr'=>$selectedCountryArr, 'common_country'=>$common_country]);
    }
    
    //设置常用国家简码
    public function actionSetCommonCountry(){
    	$result = array('error'=>1 ,'msg'=>'');
    	
    	if(empty($_POST['country_code'])){
    		$result['msg'] = '非法操作，请传入国家代码';
    	}
    	
    	$tmp_country_code = $_POST['country_code'];
    	
    	$common_country = \eagle\modules\util\helpers\ConfigHelper::getConfig('common_country_user');
    	
    	$common_country = json_decode($common_country, true);
    	
    	if($_POST['type'] == 'add'){
    		if(!in_array($tmp_country_code, $common_country)){
    			$common_country[] = $tmp_country_code;
    			\eagle\modules\util\helpers\ConfigHelper::setConfig('common_country_user', json_encode($common_country));
    		
    			$result['error'] = 0;
    			$result['msg'] = '添加常用国家成功';
    		}else{
    			$result['msg'] = '已存在常用国家,不能重复添加';
    		}
    	}else if($_POST['type'] == 'del'){
    		if(in_array($tmp_country_code, $common_country)){
    			$tmp_key = array_search($tmp_country_code, $common_country);
    			unset($common_country[$tmp_key]);
    			
    			\eagle\modules\util\helpers\ConfigHelper::setConfig('common_country_user', json_encode($common_country));
    		}
    		
    		$result['error'] = 0;
    		$result['msg'] = '删除常用国家成功';
    	}
    	
    	echo json_encode($result);
    }
}
