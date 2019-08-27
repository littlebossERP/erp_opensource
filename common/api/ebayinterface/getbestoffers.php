<?php
namespace common\api\ebayinterface;

use common\api\ebayinterface\base;
use eagle\modules\order\models\EbayBestoffer;
use eagle\models\SaasEbayUser;
/**
 * 获得BestOffer列表
 * @package interface.ebay.tradingapi
 */
class getbestoffers extends base{
	public $verb='GetBestOffers';
	
	public function api($itemid=null,$bestofferid=null){
		$this->verb = 'GetBestOffers';
        $xmlArr=array();
        
        if (!is_null($itemid)){
        	$xmlArr['ItemID']=$itemid;
        }
        
		if($bestofferid){
            $xmlArr['BestOfferID']=$bestofferid;
        }
        $xmlArr['DetailLevel']='ReturnAll';
		
		if(!empty($this->_before_request_xmlarray['EntriesPerPage']) && !empty($this->_before_request_xmlarray['PageNumber'])){
			$xmlArr['Pagination']=array(
				'EntriesPerPage'=>$this->_before_request_xmlarray['EntriesPerPage'],
				'PageNumber'=>$this->_before_request_xmlarray['PageNumber'],
			);
        }
		return $this->setRequestBody($xmlArr)->sendRequest(0,600);
	}
	
	//save
	public function save($array,$itemid=0,$selleruserid){
		$_tmp=array();//用来临时整理多item多bestoffer的数据形式
		if (isset($array['ItemBestOffersArray']['ItemBestOffers'])){
			if (isset($array['ItemBestOffersArray']['ItemBestOffers']['BestOfferArray'])){
				$_tmp['0']=$array['ItemBestOffersArray']['ItemBestOffers'];
			}else{
				$_tmp=$array['ItemBestOffersArray']['ItemBestOffers'];
			}
		}else{
			$_tmp=$array;
		}
		foreach ($_tmp as $array){
	        if(!empty($array['Item']['ItemID'])){
	            $itemid=$array['Item']['ItemID'];
	        }
	        if(empty($itemid)){
	            \Yii::info('sync bestoffer no itemid!');
	            return false;
	        }
			if (count($array['BestOfferArray'])>0){
	            
	            if(isset($array['BestOfferArray']['BestOffer'])){
	                $datas[]=$array['BestOfferArray']['BestOffer'];
	            }else{
	                $datas=$array['BestOfferArray']; 
	            }
	            if(isset($datas['BestOfferID'])){
	                $datas=array($datas);
	            }
	            
				foreach ($datas as $a){
	                $uid=SaasEbayUser::find()->where('selleruserid = :s',[':s'=>$selleruserid])->one()->uid;
					$eb=EbayBestoffer::find()->where('bestofferid=:b',[':b'=>$a['BestOfferID']])->one();
					if(empty($eb)){
						$eb = new EbayBestoffer();
					}
					$eb->selleruserid=$selleruserid;
					$eb->uid=$uid;
					$eb->bestofferid=$a['BestOfferID'];
					$eb->itemid=$itemid;
					$eb->bestoffer=$a;
					$eb->bestofferstatus=$a['Status'];
					if (is_null($eb->status)){
						$eb->status=0;
					}
					$eb->save();
				}
			}
		}
		return true;
	}
	
	/**
	 * 保存一个单独的bestoffer的数据组的格式
	 * @author fanjs
	 * [BestOfferArray],[Item]
	 */
	public function saveone($response,$selleruserid){
		$arr = [$response];
		return $this->save($arr, 0,$selleruserid);
	}
    

    /**
     * 同步所有 item的 bestoffer
     */         
    static function syncBestOffers($selleruserid){
        $MEU = SaasEbayUser::find()->where ('selleruserid=:s',[':s'=>$selleruserid])->one ();
        if (empty($MEU)){
        	echo 'can not find the seller account:'.$selleruserid;
        	\Yii::info('can not find the seller account:'.$selleruserid);
        	return false;
        }
        set_time_limit(0);        
		$api=new self();
		$api->eBayAuthToken=$MEU->token;
		$r=$api->api();
		if(!$api->responseIsFailure()){
			$api->save($r,0,$selleruserid);
		}
    }
}