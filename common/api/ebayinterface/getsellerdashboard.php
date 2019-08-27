<?php
/**
 * 获得SellerDashBoard信息
 * @package interface.ebay.tradingapi
 *
 */
class EbayInterface_getsellerdashboard extends EbayInterface_base{
	public $verb='GetSellerDashboard';
    public function api($token=null,$id=null){
    	if (!is_null($token)){
    		$this->eBayAuthToken=$token;
    	}
    	$result=$this->setRequestBody(array())->sendRequest();
    	return $result;
//   	    $this->save($result,$id);
    }
    
    public function doapi($id){
        $token = SaasEbayUser::model()->where('selleruserid=?',$id)->getOne()->token;
        return $this->api($token,$id);
    }
    
    public function save($rs,$id){
        $eu = SaasEbayUser::model()->where('selleruserid=?',$id)->getOne();
        $eu->dashboard=$rs;
        $eu->save();
        return;
    }
}
?>