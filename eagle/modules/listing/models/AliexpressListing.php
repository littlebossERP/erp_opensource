<?php namespace eagle\modules\listing\models;

use eagle\models\listing\AliexpressFreightTemplate;
use eagle\modules\listing\helpers\AlipressApiHelper;
use eagle\modules\listing\models\AliexpressListingDetail;
use eagle\modules\util\helpers\RedisHelper;
use eagle\models\SaasAliexpressUser;
use eagle\modules\manual_sync\models\Queue;

class AliexpressListing extends \eagle\models\listing\AliexpressListing
{
   //  0-保存未发布 1-onSelling-上架销售中 2-offline-下架 3-auditing-审核中 4-editingRequired-审核不通过
   
    private $_push = false;
    private $_transaction;
    // private $detail;

    public $_attr = [];

    public static $product_status = [
    	'local',   // 0
    	'onSelling',  // 1
    	'offline',  // 2
    	'auditing',    // 3
    	'editingRequired',     // 4
    ];

    public static $edit_status = [
        'pending',  // 0
        'sending',   // 1
        'edit',     // 2
        'postFail',   // 3
    ];

    public static $product_status_label = [
        'local'=>'本地商品',
    	'pending'=>'待发布',
        'edit'=>'修改中',
        'sending'=>'发布中',
    	'auditing'=>'审核中',
    	'editingRequired'=>'审核不通过',
    	'offline'=>'已下架',
    	'onSelling'=>'正在销售',
        'postFail'=>'发布失败'
    ];

    /**
     * 商品同步入口
     * @return [type] [description]
     */
    public static function syncAll($queue){
        $shops = $queue->data('shop');
        $result = true;
        foreach($shops as $shop){
            $r = AlipressApiHelper::syncAlipressProtuctDetail($queue,$shop);
            $result = $result && $r;
        }
        return $result;
    }

    public static function pushAll($queue){
        $puid = explode(':',$queue->site_id)[0];
        $products = $queue->data('products');
		foreach($products as $id){
			$product = self::findOne($id);
			$product->push();
			$queue->addProgress();
		}
		return true;
    }

    public static function batchEnable($shop,$productid=[],$on){
        $result = AlipressApiHelper::onoffProduct($shop,implode(';',$productid),$on?"on":"off");
        if($result){
            self::updateAll([
                'product_status'=>$on?1:2
            ],['productid'=>$productid]);
        }
        return $result;
    }

    /**
     * 批量发布
     * 
     * @author hqf
     * @version 2016-06-29
     * @param $ids array Listing表的自增id数组
     */
    public static function batchPush($ids=[],$queue=NULL){
        $_ids = [];
        foreach($ids as $id){
            if($product = self::findOne($id)){
                $product->edit_status = $product->product_status ? 2:1;
                if($product->save()){
                    $_ids[] = $id;
                }
            }
        }
        return Queue::add('smt:productpush',\Yii::$app->user->id.':'.md5(implode('_',$_ids)), [
            'products'=>$_ids
        ]);
    }

    private function k(){
        return 'smt:product:'.$this->selleruserid;
    }


    function beforeSave($insert){
        if(parent::beforeSave($insert)){
            if($this->isNewRecord){
                $this->product_status = 0;
            }
            $this->_transaction = self::getDb()->beginTransaction();
            // 判断是否是在线商品
            if($this->getOldAttribute('productid') && !$this->_push){
                $this->edit_status = 2;
                $attr = $this->attributes;
                $attr['detail'] = $this->detail->attributes;
                //RedisHelper::hSet($this->k(),$this->productid,json_encode($attr));
				RedisHelper::RedisSet($this->k(),$this->productid,json_encode($attr));
                $this->attributes = $this->getOldAttributes();
                $this->detail->attributes = $this->detail->getOldAttributes();
                $this->edit_status = 2;
                // $this->detail->abc = 2;
            }elseif($this->_push){
                $this->edit_status = 0;
            }
            return true;
        }else{
            return false;
        }
    }

    function afterSave($insert, $changedAttributes){
        try{
            parent::afterSave($insert, $changedAttributes);
            if($this->detail){
                $this->detail->listen_id = $this->id;
                if(!$this->detail->save($insert)){
                    throw new \Exception(print_r($this->detail->getErrors(),true), 400);
                }
            }
            $this->_transaction->commit();    
        }catch(\Exception $e){
            $this->_transaction->rollBack();
            // var_dump( $b->id );die;
            throw $e;
        };
    }


    function afterFind(){
        parent::afterFind();
        if($this->getOldAttribute('edit_status') == 2){
            //$data = RedisHelper::hGet($this->k(),$this->productid);
			$data = RedisHelper::RedisGet($this->k(),$this->productid);
            $attr = json_decode($data,true);
            $this->attributes = $attr;
            $this->_attr = $attr;
        }
    }

    function _clone(){
        $detail = $this->getDetail()->one();
        $clone = clone $this;
        $clone->setIsNewRecord(true);
        $clone->id = NULL;
        $clone->productid = NULL;
        $clone->product_status = 0;
        $clone->edit_status = 0;
        $detail->setIsNewRecord(true);
        $detail->id = NULL;
        $detail->productid = NULL;
        $detail->listen_id = NULL;
        $clone->detail = $detail;
        return $clone;
    }

    // function __clone(){
    //     parent::__clone();
    //     $detail = clone $this->detail;

    //     $this->setIsNewRecord(true);
    //     $this->id = NULL;
    //     $this->productid = NULL;
    //     $this->product_status = 0;
    //     $this->edit_status = 0;
    //     $detail->setIsNewRecord(true);
    //     $detail->id = NULL;
    //     $detail->productid = NULL;
    //     $detail->listen_id = NULL;

    //     $this->detail = $detail;
    // }

    function enable($on){
        if($this->productid){
            $result = AlipressApiHelper::onoffProduct($this->selleruserid,$this->productid,$on);
            $this->product_status = $on?1:2;
            if(!$this->save()){
                throw new \Exception(print_r($this->getErrors(),true), 500);
            }
            return $result;
        }
    }

    // 发布
    function push(){
        $this->edit_status = 0;
        $action = $this->productid?'edit':'add';
        $push = AlipressApiHelper::postAeProduct($this->selleruserid,$this->id,$action);
        if($push['return']){
        // if(1){
            $this->edit_status = 0;
            $this->_push = true;
            $this->product_status = $this->product_status?$this->product_status:3;      // 发布成功，对product_status为0的待发布商品 改成3审核中
            $this->error_message = NULL;
            if($save = $this->save()){
                $this->_push = false;
                //$result = RedisHelper::hDel($this->k(),$this->productid);
				$result = RedisHelper::RedisDel($this->k(),$this->productid);
            }else{
                throw new \Exception(print_r($this->getErrors(),true), 500);
            }
        }else{
            if(!$this->productid){
                $this->edit_status = 3;
            }else{
                $this->edit_status = 0;
            }
            $this->error_message = json_encode($push['msg']);
            $this->save();
        }
        return $push;
    }

    function delete(){
        if($this->detail){
            $this->detail->delete();
        }
        return parent::delete();
    }


    function getDetail(){
        return $this->hasOne(AliexpressListingDetail::className(),[
           'listen_id' => 'id'
        ]);
    }

    function setDetail($val){
        $this->detail = $val;
    }
    
    function getOnlineDetail(){
    	return $this->hasOne(AliexpressListingDetail::className(),[
    		'productid'=>'productid'
    	]);
    }

    function getDetailInfo(){
        return $this->hasOne(AliexpressListingDetail::className(),[
           'listen_id' => 'id'
        ]);  
    }

    function getDetailDel(){
       $aliexpressdetail = AliexpressListingDetail::find()->where(['listen_id'=>$this->id])->one();
       return $aliexpressdetail->delete();
    }

    function getFreight(){
    	return $this->hasOne(AliexpressFreightTemplate::className(),[
    		'templateid'=>'freight_template_id'
    	]);
    }

    function getCreateDateTime(){
    	return date('Y-m-d H:i:s',$this->created);
    }


}
