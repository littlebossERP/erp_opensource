<?php
class PaypalInterface_TransactionSearch extends PaypalInterface_Abstract {
	public $verb='TransactionSearch';
	function request($STARTDATE,$ENDDATE=null,$TRANSACTIONID=null){
		$param=array();
		if($STARTDATE){
			$param['STARTDATE']=$this->isodate($STARTDATE);
		}
		if($ENDDATE){
			$param['ENDDATE']=$this->isodate($ENDDATE);
		}
		if($TRANSACTIONID){
			$param['TRANSACTIONID']=$TRANSACTIONID;
		}
		if($this->paypalEmail){
			$param['RECEIVER']=urlencode($this->paypalEmail);
		}
		
		$r=$this->doNvpRequest($param);
		return $r;
	}
	
	
}