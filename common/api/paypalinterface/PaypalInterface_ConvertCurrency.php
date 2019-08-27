<?php
class PaypalInterface_ConvertCurrency extends PaypalInterface_Abstract {
	public $verb='ConvertCurrency';
	function request(){
		$arr=array(
			'baseAmountList.currency(0).code'=>'USD',
			'baseAmountList.currency(0).amount'=>1,
			'convertToCurrencyList.currencyCode(0)'=>'AUD',	
		);
				
		return $this->doNvpRequest($arr);
	}
}