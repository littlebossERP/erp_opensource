<?php
namespace common\api\ebayinterface;

use common\api\ebayinterface\base;
/**
 * 从eBay获取促销详情
 * @author Administrator
 *
 */
class getpromotionalsaledetails extends base{
    public $verb = 'GetPromotionalSaleDetails';
    public function api($selleruserid,$status=array('Active','Scheduled','Processing','Deleted')){
        $xmlArr=array(
                'PromotionalSaleStatus'=>$status,
        );
        $requestArr=$this->setRequestBody($xmlArr)->sendRequest(0,600);
        return $requestArr;
    }
}