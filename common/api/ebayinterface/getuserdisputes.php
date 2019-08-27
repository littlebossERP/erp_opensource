<?php
/**
 * 发起售后纠纷
 * @package interface.ebay.tradingapi
 */
class EbayInterface_GetUserDisputes extends EbayInterface_base{
	public $verb='GetUserDisputes';

	public $EntriesPerPage=null;
	public $PageNumber=0;
	public $ModTimeFrom=null;
	public $ModTimeTo=null;


	function api(){
		$xmlArr=array(
			'DisputeFilterType'=>'AllInvolvedDisputes'
		);
		if ($this->EntriesPerPage){
			$xmlArr['Pagination']=array(
				'EntriesPerPage'=>$this->EntriesPerPage,
				'PageNumber'=>$this->PageNumber,
			);
		}

		if ($this->ModTimeFrom){
			$xmlArr['ModTimeFrom']=$this->ModTimeFrom;
			$xmlArr['ModTimeTo']=$this->ModTimeTo;
		}
		if(!empty($this->_before_request_xmlarray['DetailLevel'])){
			$xmlArr['DetailLevel']=$this->_before_request_xmlarray['DetailLevel'];
		}

		if(!empty($this->_before_request_xmlarray['DisputeFilterType'])){
			$xmlArr['DisputeFilterType']=$this->_before_request_xmlarray['DisputeFilterType'];
		}

		if(!empty($this->_before_request_xmlarray['DisputeSortType'])){
			$xmlArr['DisputeSortType']=$this->_before_request_xmlarray['DisputeSortType'];
		}
		if(isset($this->_before_request_xmlarray['OutputSelector'])){
			//unset($xmlArr['DetailLevel']);
			$xmlArr['OutputSelector']=$this->_before_request_xmlarray['OutputSelector'];
		}
		$requestArr=$this->setRequestBody($xmlArr)->sendRequest(0,600);

		Yii::log(print_r($requestArr,1));
		return $requestArr;

	}

	/**
	 * 请求所有 售前 纠纷 ,仅未付款的  
	 * @param unknown_type $eu
	 * @author lxqun
	 * @date 2014-3-17
	 */
	static function cronRequest($eu,$modtimefrom,$modtimeto){
		$api=new self();
		$api->eBayAuthToken=$eu->token;
		$api->EntriesPerPage=30;
		//$getOrders->PageNumber=1;
		$api->_before_request_xmlarray['DetailLevel']='ReturnAll';
		$api->_before_request_xmlarray['OutputSelector']=array(
			'DisputeArray',
			'ItemsPerPage',
			'PageNumber',
			'PaginationResult',
			'DisputeArray.Dispute.DisputeMessage',
		);


		$api->_before_request_xmlarray['DisputeFilterType']='UnpaidItemDisputes';

		try{
			do{

				$api->ModTimeFrom=self::dateTime($modtimefrom);
				$api->ModTimeTo=self::dateTime($modtimeto);
				//print_r($ebayorderids);

				$responseArr=$api->api();
				if($api->responseIsFailure()){  //接口失败 退出
					break;
				}
				if(isset($responseArr['DisputeArray']) && isset($responseArr['DisputeArray']['Dispute'])){
					$disputeArray=$responseArr['DisputeArray']['Dispute'];
					if(isset($disputeArray['DisputeID'])){
						$disputeArray=array($disputeArray);
					}
					foreach($disputeArray as $disputeArray_i){
						CmEbayDisputeHelper::apiSave($disputeArray_i,$eu->selleruserid);
					}
				}
				// 翻页 及退出 
			   /*
			   if($requestArr['PageNumber']>=$requestArr['PaginationResult']['TotalNumberOfPages']){ 
				   break 1;
			   }else{
				   $this->PageNumber=$requestArr['PageNumber']+1;
			   }
				*/
				if($responseArr['PageNumber']>=$responseArr['PaginationResult']['TotalNumberOfPages']){
					return true;
					break 1;
				}else{
					//$this->PageNumber=$responseArr['PageNumber']+1;
					self::$PageNumber = $responseArr['PageNumber']+1;
				}

			}while(1);

		}catch(Exception $ex){
			echo "Error Message :  ". $ex->getMessage();
		}
		return false;
	}


}
