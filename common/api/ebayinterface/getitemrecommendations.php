<?php
/**
 * 待补充
 * @package interface.ebay.tradingapi
 */ 
class EbayInterface_GetItemRecommendations extends EbayInterface_base{
	//ebay getcategories接口实现，通过接口获取ebay的类别信息，生成文件，并解析进数据库
	public function Api($CategoryID=0,$title=''){
		$this->verb = 'GetItemRecommendations';
		$xmlArr=array(
				'RequesterCredentials'=>array(
					'eBayAuthToken'=>$this->eBayAuthToken,
				),
				'GetRecommendationsRequestContainer'=>array(
					'RecommendationEngine'=>'ItemSpecifics',
					'Item'=>array(
						'PrimaryCategory'=>array(
							'CategoryID'=>$CategoryID
						),
						'Title'=>$title
					),
					'IncludeConfidence'=>'true',
					'CorrelationID'=>1,
				),
				'Version'=>$this->config["compatabilityLevel"],
		);
		$result=$this->setRequestBody($xmlArr)->sendRequest();
		var_dump($xmlArr);
		var_dump($result);die();
		if($result['Ack']=='Success'){
			return $result['CategoryArray'];
		}else{
			return false;
		}
	}
}
?>

