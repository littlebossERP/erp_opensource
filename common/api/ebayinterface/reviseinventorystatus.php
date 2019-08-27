<?php
namespace common\api\ebayinterface;

use common\api\ebayinterface\base;
use eagle\modules\listing\models\EbayItem;
use eagle\models\QueueItemprocess;
use eagle\modules\listing\models\EbayLogItem;
/***
 *  为数量即将到0的一口价商品补库存  
 *  
 */ 
class reviseinventorystatus extends base{
    public $verb = 'ReviseInventoryStatus';
    
    /**
     * [batchApi description]
     * @author willage 2017-03-03T16:17:28+0800
     * @editor willage 2017-03-03T16:17:28+0800
     * @return [type]  [description]
     */
    function batchApi($paramArr){
        $xmlArr=array('InventoryStatus'=>$paramArr);
        $respson=$this->setRequestBody($xmlArr)->sendRequest();
        return $respson;
    }

    /**
     * 
     * @param unknown_type $param
     * @return Ambigous <multitype:, xml, boolean, unknown, string, resource, mixed, xmlArray, SimpleXMLElement, NULL>
     */
    function apiCore($param) {
    	$xmlArr=array('ItemID'=>$param['ItemID']);
    	if (isset($param['Quantity'])) {
    		$xmlArr['Quantity']=$param['Quantity'];
    	}
    	if (isset($param['SKU'])) {
    		$xmlArr['Quantity']=$param['SKU'];
    	}
    	if (isset($param['StartPrice'])) {
    		$xmlArr['StartPrice']=$param['StartPrice'];
    	}
        // \Yii::info("ReviseInventoryStatus apiCore data:".print_r($xmlArr,1),"ebayapi");

        $respson=$this->setRequestBody($xmlArr)->sendRequest();
        $baselog=array(
            "base_data"=>"siteID: ".@$this->siteID.", devAccountID: ".@$this->devAccountID." api:".@$this->verb,
            "ReviseInventoryStatus_data"=>$xmlArr,
            "base_resp"=>"[Ack]: ".@$this->_last_response_array['Ack'].", [Errors]: ".print_r(@$this->_last_response_array['Errors'],1));
        \Yii::info(print_r($baselog,1),"ebayapi");
    	return $respson;
    }
    /**
     * 补货
     *
     * @param unknown_type $itemid
     * @param unknown_type $toQuantity
     * @param unknown_type $token
     * @param unknown_type $transactionQuantity
     * @param unknown_type $mubanid
     * @param unknown_type $sku
     * @return unknown
     */
    function api($itemid,$toQuantity,$eu,$transactionQuantity,$mubanid,$sku,$transactionid){
        //$num2+=$num;
        $xmlArrInventoryStatus=array(
                'ItemID'=>$itemid,
                'Quantity'=>$toQuantity,
            );
        if (strlen($sku)){
            $xmlArrInventoryStatus['SKU']=$sku;
        }
        $xmlArr=array(
            'ReviseInventoryStatusRequest xmlns="urn:ebay:apis:eBLBaseComponents"'=>array(
                'RequesterCredentials'=>array(
                    'eBayAuthToken'=>$eu->token,
                ),
                'InventoryStatus'=>$xmlArrInventoryStatus
            )
        );
        \Yii::info("ReviseInventoryStatus 自动补货 data:".print_r($xmlArr,1),"ebayapi");
        $result=$this->sendHttpRequest($xmlArr);
//        print_r($result);exit();
         // 错误的情况,使用  fixedpriceitem
         if($this->responseIsFailure()){
              $api = new revisefixedpriceitem();
              $api->resetConfig($eu->listing_devAccountID);
              $api->eBayAuthToken = $eu->listing_token;
              $result=$api->apiReviseQuantity($itemid,$toQuantity,$sku);
          }
          
        //修改在线刊登记录
        $reviseitem = new EbayLogItem();
        $reviseitem->name="system";
        $reviseitem->reason="自动补货";
        $reviseitem->itemid=$itemid;
        if($mubanid){
            $reviseitem->mubanid=$mubanid;
        }
        $reviseitem->content=array('quantity'=>$toQuantity,'sku'=>$sku);
        $reviseitem->result=$result['Ack'];
        $reviseitem->transactionid=$transactionid;
        //if (strlen($result['Errors']['LongMessage'])){
            $reviseitem->message=$result;//['Errors']['LongMessage'];
        //}
        $reviseitem->createtime = time();
        $reviseitem->save();
        
        return false;
    }
    
    /**
     * 
     * 修改在线刊登价格
     */
    function api2($itemid,$token,$mubanid,$sku,$startprice,$username="",$comment=""){
    	if (strlen($username)<1){
    		$username='system';
    	}
        //$num2+=$num;
        $xmlArrInventoryStatus=array(
                'ItemID'=>$itemid,
            );
        if ($startprice>0){
            $xmlArrInventoryStatus['StartPrice']=$startprice;
        }
    	if (strlen($sku)){
            $xmlArrInventoryStatus['SKU']=Helper_SKU::EbayItemEncode($sku,$mubanid);
        }
        $xmlArr=array(
            'ReviseInventoryStatusRequest xmlns="urn:ebay:apis:eBLBaseComponents"'=>array(
                'RequesterCredentials'=>array(
                    'eBayAuthToken'=>$token,
                ),
                'InventoryStatus'=>$xmlArrInventoryStatus
            )
        );
        \Yii::info("ReviseInventoryStatus 修改在线刊登价格 data:".print_r($xmlArr,1),"ebayapi");
        $result=$this->sendHttpRequest($xmlArr);
        
        //修改在线刊登记录
        $reviseitem = new Reviseitem_Logs();
        $reviseitem->name=$username;
        $reviseitem->reason="按sku调整价格";
        $reviseitem->itemid=$itemid;
        if($mubanid){
            $reviseitem->mubanid=$mubanid;
        }
        $reviseitem->content=array('buyitnowprice'=>$startprice,'comment'=>$comment);
        $reviseitem->result=$result['Ack'];
        $reviseitem->message=$result;
        $reviseitem->save();
    	if ($result['Ack']=='Success' ||$result['Ack']=='Warning' ){
    		$item=Ebay_Item::find('itemid = ?',$itemid)->getOne();
    		if (!$item->isNewRecord()){
    			//判断是否有多属性
				if (count($item->detail->variation)>0){
					$variation = $item->detail->variation;
					if (isset($variation['Variation']['StartPrice'])){
						$temp_arr = $variation['Variation'];
						unset($variation['Variation']);
						$variation['Variation'][0]=$temp_arr;
					}
					foreach ($variation['Variation'] as $k=>$v){
						if ($v['SKU'] == $sku){
						$variation['Variation'][$k]['StartPrice']=$startprice; 
						}
					}
					$item->detail->changeProps(array(
						'variation' => $variation,
					));
					$item->detail->save();
				}else{
					$item->changeProps(array(
						'buyitnowprice' => $startprice,
					));
					$item->save();
				}
    		}
		}
    }
    /**
     * 
     * 商品追踪自动改价格
     */
    function api3($token,$itemid,$new_startprice){
    	$xmlArrInventoryStatus=array(
    			'ItemID'=>$itemid,
    	);
    	if ($new_startprice>0){
    		$xmlArrInventoryStatus['StartPrice']=$new_startprice;
    	}
    	$xmlArr=array(
    			'ReviseInventoryStatusRequest xmlns="urn:ebay:apis:eBLBaseComponents"'=>array(
    					'RequesterCredentials'=>array(
    							'eBayAuthToken'=>$token,
    					),
    					'InventoryStatus'=>$xmlArrInventoryStatus
    			)
    	);
        \Yii::info("ReviseInventoryStatus 商品追踪自动改价格 data:".print_r($xmlArr,1),"ebayapi");
    	return $this->sendHttpRequest($xmlArr);
    	
    }
    
    /**
     * 专门的为reviseitem中的价格和数量调用的接口
     * @author fanjs
     */
    function apiforrevise($itemid,$xmlarray,$token){
    		$xmlArrInventoryStatus['ItemID']=$itemid;
    		if (isset($xmlarray['sku'])&&strlen($xmlarray['sku'])){
    			$xmlArrInventoryStatus['SKU']='<![CDATA['.$xmlarray['sku'].']]>';
    		}
    		$xmlArrInventoryStatus['StartPrice']=strlen($xmlarray['startprice'])?$xmlarray['startprice']:$xmlarray['buyitnowprice'];
    		if (strlen($xmlarray['quantity'])){
    			$xmlArrInventoryStatus['Quantity']=$xmlarray['quantity'];
    		}
    	$xmlArr=array(
    			'ReviseInventoryStatusRequest xmlns="urn:ebay:apis:eBLBaseComponents"'=>array(
    					'RequesterCredentials'=>array(
    							'eBayAuthToken'=>$token,
    					),
    					'InventoryStatus'=>$xmlArrInventoryStatus
    			)
    	);
        // \Yii::info("ReviseInventoryStatus 为reviseitem data:".print_r($xmlArr,1),"ebayapi");
    	$result=$this->sendHttpRequest($xmlArr);
        $baselog=array(
            "base_data"=>"siteID: ".@$this->siteID.", devAccountID: ".@$this->devAccountID." api:".@$this->verb,
            "ReviseInventoryStatus_for_reviseitem"=>$xmlArr,
            "base_resp"=>"[Ack]: ".@$this->_last_response_array['Ack'].", [Errors]: ".print_r(@$this->_last_response_array['Errors'],1));
        \Yii::info(print_r($baselog,1),"ebayapi");
    	return $result;
    }
    
    /**
     * @author fanjs
     * $itemid : $itemid     
     * notification 和 itemprocess 里面的接入都忽略，改用transaction创建时增加到队列
     */         
    static function AddQueue($itemid,$sku=null,$numsold=0,$transactionid=null){
    	$EI = EbayItem::findOne(['itemid'=>$itemid]);
        if (is_null($transactionid)||$transactionid==0){
        	return 0;
        }
        if(is_null($EI)){
        	return 0;
        }
        //忽略拍卖
        if ($EI->listingtype == 'Chinese') {return 0;}
        
        //再次验证必须是开启了自动补数量的才会运行
        if($EI->bukucun == '0'){
        	return false;
        }
        //  加入队列
        QueueItemprocess::AddReviseInventoryStatus($itemid,$sku,$numsold,$transactionid);
        return true;
    }
}