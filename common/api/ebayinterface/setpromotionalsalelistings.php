<?php
namespace common\api\ebayinterface;

use common\api\ebayinterface\base;
class setpromotionalsalelistings extends base{
    public $verb = 'SetPromotionalSaleListings';
    public function api($action,$PromotionalSaleID,$items=array()){
        $xmlArr=array(
                'Action'=>$action,
                'PromotionalSaleID'=>$PromotionalSaleID,
        );
        if (count($items)){
           $xmlArr['PromotionalSaleItemIDArray']['ItemID'] = $items;
        }
        $requestArr=$this->setRequestBody($xmlArr)->sendRequest(0,600);
        return $requestArr;
    }
}