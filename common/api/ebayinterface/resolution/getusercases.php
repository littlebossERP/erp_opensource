<?php

namespace common\api\ebayinterface\resolution;

use common\api\ebayinterface\resolution\base;
use \Exception;
use eagle\modules\message\helpers\ResolutionEbayHelper;

class getusercases extends base{
	//从ebay获取case
	public $verb='getUserCases';

	public $EntriesPerPage=null;
	public $PageNumber=null;
	public $fromDate=null;
	public $toDate=null;


	/**
	 * @param $pageNu	第几页
	 * 
	 * @edit hqw date 20151117
	 */
	public function api($pageNu){
		$xmlArr=array();

		if ($this->fromDate){
			$xmlArr['creationDateRangeFilter']=array(
				'fromDate'=>$this->fromDate,
				'toDate'=>$this->toDate,
			);
		}

		if ($this->EntriesPerPage){
			$xmlArr['paginationInput']=array(
				'entriesPerPage'=>$this->EntriesPerPage,
				'pageNumber'=>$pageNu,
			);
		}
		if(isset($this->_before_request_xmlarray['caseType'])){
			$xmlArr['caseTypeFilter']=array(
				'caseType'=>$this->_before_request_xmlarray['caseType']
			);
		}
		if(isset($this->_before_request_xmlarray['OutputSelector'])){
			unset($xmlArr['DetailLevel']);
			$xmlArr['OutputSelector']=$this->_before_request_xmlarray['OutputSelector'];
		}
		
		$result=$this->setRequestBody($xmlArr)->sendRequest();
		\Yii::info(print_r($xmlArr,1));
		// 	   	$this->save($result['cases']);
		return $result;
	}

	public static function getEbayUserCases($eu,$fromDate,$toDate){
		$api= new self;
		$api->eBayAuthToken=$eu->token;
		$api->EntriesPerPage=30;
		$api->fromDate=self::dateTime($fromDate);
		$api->toDate=self::dateTime($toDate);
		
		$api->_before_request_xmlarray['caseType']=array(
// 				'CANCEL_TRANSACTION','EBP_INR','EBP_SNAD','INR','PAYPAL_INR','PAYPAL_SNAD','RETURN','SNAD','UPI'
				'EBP_INR','EBP_SNAD'
		);
		
		try{
			$pageNu = 1;
			do{
				$responseArr=$api->api($pageNu);
				
				if ($responseArr['ack'] == 'Warning'){
					$_tmp = [];
					$_tmp['error']['message'] = $responseArr['errorMessage']['error']['message'];
					return $_tmp;
				}
				if($api->responseIsFailure()){  //接口失败 退出
					$_tmp = [];
					$_tmp['error']['message'] = $responseArr['error']['message'];
					return $_tmp;
					break;
				}
				//如果没有case 退出
				if(!isset($responseArr['cases'])){
					break;
				}
				$cases=$responseArr['cases'];
				if (count($cases['caseSummary'])){
					//处理内容部分
					if (isset($cases['caseSummary']['caseId'])){
						$cases['caseSummary']=array($cases['caseSummary']);
					}
					foreach ($cases['caseSummary'] as $r){
						$refresh = true;
						ResolutionEbayHelper::ebayUserCaseApiSave($r,$eu->selleruserid,$eu->uid,$refresh);
						
						//if ($refresh){
							//同步更新EBPDetail的数据
							if(in_array($r['caseId']['type'],array('EBP_INR','EBP_SNAD') )){
								getebpcasedetail::getEbpCaseDetailOne($r['caseId']['id'],$r['caseId']['type'] ,$eu);
							}else{
								\common\api\ebayinterface\getdispute::getDisputeOne($r['caseId']['id'],$eu);
							}
						//}
					}
				}
				
				if($responseArr['paginationOutput']['pageNumber']>=$responseArr['paginationOutput']['totalPages']){
					return true;
				}else{
					$pageNu++;
				}
			}while(1);
		
		}catch(Exception $ex){
			echo "Error Message :  ". $ex->getMessage();
		}
		
		return false;
	}

	/**
	 * 开始同步  user ebp case dispute 所有的售后的纠纷
	 * @param Ebay User 用户表 $eu
	 * @author lxqun
	 * @date 2014-3-16
	 */
	static function cronRequest($eu,$fromDate,$toDate){
		$api= new self;
		$api->eBayAuthToken=$eu->token;
		$api->EntriesPerPage=30; 
		$api->fromDate=self::dateTime($fromDate);
		$api->toDate=self::dateTime($toDate);
		$api->_before_request_xmlarray['caseType']=array(
			//'UPI','CANCEL_TRANSACTION','EBP_INR','EBP_SNAD','INR','PAYPAL_INR','PAYPAL_SNAD','SNAD',
            'EBP_INR', 'EBP_SNAD'
		);
		try{
			$pageNu = 1;
			do{
				$responseArr=$api->api($pageNu);
				if($api->responseIsFailure()){  //接口失败 退出
					break;
				}
				$cases=$responseArr['cases'];
				if (count($cases['caseSummary'])){
					//处理内容部分
					if (isset($cases['caseSummary']['caseId'])){
						$cases['caseSummary']=array($cases['caseSummary']);
					}
					foreach ($cases['caseSummary'] as $r){
						CmEbayUsercaseHelper::apiSave($r,$eu->selleruserid,$eu->uid,$refresh);

						if ($refresh){
							//同步更新EBPDetail的数据
							if(in_array($r['caseId']['type'],array('EBP_INR','EBP_SNAD') )){
								EbayInterface_Resolution_getebpcasedetail::cronRequestOne($r['caseId']['id'],$r['caseId']['type'] ,$eu);
							}else{
								EbayInterface_GetDispute::cronRequestOne($r['caseId']['id'],$eu);
							}
						}
					}
				}

				// 翻页 及退出
				/*
				if($requestArr['pageNumber']>=$requestArr['paginationOutput']['totalPages']){
					break 1;
				}else{
					$this->PageNumber=$requestArr['pageNumber']+1;
					//$this->EntriesPerPage=$requestArr['entriesPerPage'];
				}
				 */
				if($responseArr['paginationOutput']['pageNumber']>=$responseArr['paginationOutput']['totalPages']){
					return true;
					break 1;
				}else{
					$pageNu++;
					//self::$PageNumber=$responseArr['pageNumber']+1;
					//$this->EntriesPerPage=$requestArr['entriesPerPage'];
				}
			}while(1);

		}catch(Exception $ex){
			echo "Error Message :  ". $ex->getMessage();
		}
		return false;
	}
}
?>
