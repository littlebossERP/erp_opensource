<?php
namespace common\api\ebayinterface;

use common\api\ebayinterface\base;
/**
 * 修改店铺分类
 * @package interface.ebay.tradingapi
 */
class setstorecategories extends base{
    /**
     * 
     */         
	public function delete($categoryId){
        $this->verb = 'SetStoreCategories';
        $xmlArr=array(
            'RequesterCredentials'=>array(
				'eBayAuthToken'=>$this->eBayAuthToken,
			),
            'ErrorLanguage'=>'zh_CN',
            'Action'=>'Delete',
            'StoreCategories'=>array(
                'CustomCategory'=>array('CategoryID'=>$categoryId),
            ),
// 			'UserID'=>$userid,
		);
		$result=$this->setRequestBody($xmlArr)->sendRequest();
		return $result;
    }
    
    public function add($customCategory,$parentCategoryId=0){
        $this->verb = 'SetStoreCategories';
        if(empty($parentCategoryId)){
            $parentCategoryId=-999;
        }
        $xmlArr=array(
            'RequesterCredentials'=>array(
				'eBayAuthToken'=>$this->eBayAuthToken,
			),
            'ErrorLanguage'=>'zh_CN',
            'Action'=>'Add',
            'StoreCategories'=>array(
                'CustomCategory'=>$customCategory
            ),
            'DestinationParentCategoryID'=>$parentCategoryId
// 			'UserID'=>$userid,
		);
		$result=$this->setRequestBody($xmlArr)->sendRequest();
		return $result;
//		if($result['Ack']=='Success'){
//			return $result;
//		}else{
//			return false;
//		}
    }
    /***
     * 移动    
     */    
    public function move($customCategory,$parentCategoryIdNew=0){
        $this->verb = 'SetStoreCategories';
        if(empty($parentCategoryIdNew)){
            $parentCategoryIdNew=-999;
        }
        $xmlArr=array(
            'RequesterCredentials'=>array(
				'eBayAuthToken'=>$this->eBayAuthToken,
			),
            'ErrorLanguage'=>'zh_CN',
            'Action'=>'Move',
            'StoreCategories'=>array(
                'CustomCategory'=>$customCategory
            ),
            'DestinationParentCategoryID'=>$parentCategoryIdNew,
// 			'UserID'=>$userid,
		);
		$result=$this->setRequestBody($xmlArr)->sendRequest();
		return $result;
//		if($result['Ack']=='Success'){
//			return $result;
//		}else{
//			return false;
//		}
    }
    
    public function rename($customCategory){
        $this->verb = 'SetStoreCategories';
        $xmlArr=array(
            'RequesterCredentials'=>array(
				'eBayAuthToken'=>$this->eBayAuthToken,
			),
            'ErrorLanguage'=>'zh_CN',
            'Action'=>'Rename',
            'StoreCategories'=>array(
                'CustomCategory'=>$customCategory
            ),
// 			'UserID'=>$userid,
		);
        
		$result=$this->setRequestBody($xmlArr)->sendRequest();
		return $result;
//		if($result['Ack']=='Success'){
//			return $result;
//		}else{
//			return false;
//		}
    }
}
