<?php
namespace common\api\ebayinterface;

use common\api\ebayinterface\base;
class setpromotionalsale extends base{
    public $verb = 'SetPromotionalSale';
    public function api($action,$promotion=array()){
        $xmlArr=array(
                'Action'=>$action,
                'PromotionalSaleDetails'=>array(
//                         'DiscountType'=>$promotion['DiscountType'],
//                         'DiscountValue'=>$promotion['DiscountValue'],
                        'PromotionalSaleStartTime'=>$this->dateTime($promotion['PromotionalSaleStartTime']),
                        'PromotionalSaleEndTime'=>$this->dateTime($promotion['PromotionalSaleEndTime']),
                        //'PromotionalSaleID'=>$promotion['PromotionalSaleID'],
                        'PromotionalSaleName'=>$promotion['PromotionalSaleName'],
                        'PromotionalSaleType'=>$promotion['PromotionalSaleType'],
                        ),
        );
        if (isset($promotion['PromotionalSaleID'])){
            $xmlArr['PromotionalSaleDetails']['PromotionalSaleID']=$promotion['PromotionalSaleID'];
        }
        if (isset($promotion['DiscountType'])){
        	$xmlArr['PromotionalSaleDetails']['DiscountType']=$promotion['DiscountType'];
        	$xmlArr['PromotionalSaleDetails']['DiscountValue']=$promotion['DiscountValue'];
        }
        $requestArr=$this->setRequestBody($xmlArr)->sendRequest(0,600);
        return $requestArr;
    }
    
    //删除
    public function delete($id){
    	$xmlArr=array(
    			'Action'=>'Delete',
    			'PromotionalSaleDetails'=>array(
    				'PromotionalSaleID'=>$id,
    			),
    	);
    	$requestArr=$this->setRequestBody($xmlArr)->sendRequest(0,600);
    	return $requestArr;
    }
}