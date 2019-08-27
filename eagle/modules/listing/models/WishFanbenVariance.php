<?php namespace eagle\modules\listing\models;

use eagle\modules\listing\helpers\WishHelper;
use eagle\modules\listing\models\WishFanben;
use eagle\modules\listing\models\WishProxy;

class WishFanbenVariance extends \eagle\models\WishFanbenVariance
{
    private $_product;
 
    public function getProduct(){
        if(!$this->_product){
            $this->_product = WishFanben::findOne($this->fanben_id);
        }
        return $this->_product;
    }

    public function save($runValidation = true, $attributeNames = NULL){
        // 如果是在线商品则不能修改sku
        if($runValidation && $this->getOldAttribute('variance_product_id') && strtolower($this->sku) != strtolower($this->getOldAttribute('sku')) ){
            throw new \Exception("无法修改在线变体的 sku。{$this->getOldAttribute('sku')} -> {$this->sku}", 403);
        }
        // 检查是否有默认变体
        $this->checkSizeColor();
        // 在线商品的上下架设置
        if($runValidation && $this->variance_product_id && $this->getOldAttribute('enable') != $this->enable){
            $this->_enabled();
        }
        return call_user_func_array('parent::'.__FUNCTION__, func_get_args());
    }
    
    /**
     * 只是简单调用父亲的save，用作商品拉取时候的保存
     */
    public function saveRaw($runValidation = true, $attributeNames = NULL){
    	return call_user_func_array('parent::save', func_get_args());
    }

    public function delete($runValidation = true, $attributeNames = NULL){
        // 如果是在线商品则不能修改sku
        if($runValidation && $this->getOldAttribute('variance_product_id')){
            throw new \Exception("无法删除已上线变体：".$this->sku, 403);
        }
        return call_user_func_array('parent::'.__FUNCTION__, func_get_args());
    }
    
    /////////////////////////////////////// ********* 神秘的分割线 *******/////////////////////////////////////////////////////////////
    
    /**
     * 检查变体
     * @version 2016-05-10 hqf
     * @return [type] [description]
     */
    public function checkSizeColor(){
        $variants = WishFanbenVariance::find()->where([
            'fanben_id'=>$this->fanben_id,
            'color'=>'',
            'size'=>''
        ])->andWhere('color IS NOT NULL')
        ->andWhere('size IS NOT NULL');
        if($this->id){
            $variants->andWhere('id <> '.$this->id);
        }
        foreach($variants->each() as $variant){
            $variant->delete(false);
        }
    }

    /**
     * 发布 上下架状态
     * @return [type] [description]
     */
    public function _enabled(){
        $param = [
            'sku'=>$this->sku,
            'enable'=>$this->enable == 'Y'?'on':'off'
        ];
        try{
            WishProxy::getInstance($this->getProduct()->site_id)->call('variantstatus',$param);
        }catch(\Exception $e){
            $this->addinfo = $e->getMessage();
            $this->save(false);
            throw new \Exception('变体上下架失败:'.$e->getMessage(), $e->getCode());
        }
    }

    /**
     * 从变体中分离出第一个变体和其他变体
     * 用在新发布的时候需要
     * 规则，如果变体的sku等于parent_sku则作为第一个变体，否则按照id排序最小的作为第一个
     * @author huaqingfeng 2016-04-08
     * @param  string $parent_sku 
     * @return [WishFanbenVariance $first, Array $else] 
     */
    static public function getFirstVariant($parent_sku){
        $variants = self::find()->where([
            'parent_sku' => $parent_sku
        ])->orderBy('id ASC');
        $firstVariant = NULL;
        $else = [];
        foreach($variants->each() as $variant){
            $else[]=$variant;
            if(!$firstVariant){
                $firstVariant = $variant;
            }elseif($variant->sku == $parent_sku){
                $firstVariant = $variant;
            }
        }
        if(!$firstVariant){
            throw new \Exception($parent_sku." 变体不存在", 500);
        }
        return [$firstVariant,$else];
    }

    /**
     * 上下架设置
     * 不需要额外调用save方法
     * @param  [type] $enabled [description]
     * @return [type]          [description]
     */
    public function enable($enabled){
        $this->enable = $enabled ? 'Y':'N';
        if(!$this->save()){
            throw new \Exception('保存变体状态失败'.json_encode($this->getErrors(),JSON_UNESCAPED_UNICODE), 500);
        }
        $product = WishFanben::findOne($this->fanben_id);
        if($enabled && $product->is_enable==1){
            $all = self::find()->where([
                'fanben_id'=>$this->fanben_id
            ]);
            $product->is_enable = $all->count() == $all->andWhere(['enable'=>'Y'])->count() ? 1:2;
        }else{
            $product->is_enable = 2;
        }
        if(!$product->save()){
            throw new \Exception('保存商品状态失败'.json_encode($product->getErrors(),JSON_UNESCAPED_UNICODE), 500);
        }
        return $product->is_enable;
    }

    /**
     * 保存变体属性
     * @param WishFanbenVariance $variant [description]
     * @param Array             $data    [description]
     */
    static private function setData(WishFanbenVariance $variant,$data){
        $keys = ['color','size','sku','price','inventory','shipping','image_url'];
        foreach($keys as $key){
            if(isset($data[$key])){
                $variant->$key = $data[$key];
            }
        }
        if(isset($data['enable']) && strtoupper($data['enable'])=='Y'){
            $variant->enable = 'Y';
        }else{
            $variant->enable = 'N';
        }
        return $variant->save(); // 变种商品是否存在下架商品 1不存在 2存在
    }

    static private function saveVariants($variatns,WishFanben $product,$data=[]){
        $exists = [];
        // 对现有变体进行操作
        foreach($variatns->each() as $variant){

            foreach($data as $v){
                if($v['sku'] == $variant->sku){
                    if(!self::setData($variant, $v)){
                        throw new \Exception("保存变体失败：{$variant->sku}".json_encode($variant->getErrors(),JSON_UNESCAPED_UNICODE), 400);
                    }
                    $exists[] = $variant->sku;
                    continue 2;
                }
            }
            $variant->delete();
        }
        // 检查新增的
        foreach($data as $v){
            if(!in_array($v['sku'], $exists)){
                $variant = new self;
                $variant->parent_sku    = $product->parent_sku;
                $variant->fanben_id     = $product->id;
                if(!self::setData($variant, $v)){
                    throw new \Exception("新增变体失败：{$variant->sku}".json_encode($variant->getErrors(),JSON_UNESCAPED_UNICODE), 400);
                }
            }
        }
        return true;
    }

    static function saveByProduct($product,$data=[]){
        // 先查出现有变体
        $query = self::find()->where([
            'fanben_id'=>$product->id
        ]);
        return self::saveVariants($query,$product,$data);
    }

    // 同步用
    static function saveByWishProductId($product,$data=[]){
        // 先查出现有变体
        // $query = self::find()->where([
        //     'fanben_id'=>$product_id
        // ])->andWhere('variance_product_id');
        // return self::saveVariants($query,$data);
    }

    /**
     * 发布
     * @return [type] [description]
     */
    function push($product = NULL){
        if(!$product || !$product instanceof WishFanben){
            $product = $this->getProduct();
        }
        $data = [
            'sku'               => $this->sku,
            'color'             => $this->color,
            'size'              => $this->size,
            'inventory'         => $this->inventory,
            'price'             => $this->price,
            'shipping'          => $this->shipping,
            'msrp'              => $product->msrp,
            'shipping_time'     => $product->shipping_time,
            'main_image'        => $this->image_url
        ];
        $get = [];
        if($this->variance_product_id){             // update
            $action = 'updatevariant'; 
        }else{                                  // create
            $action = 'createvariant';
            $data['parent_sku'] = $this->parent_sku;
        }
        try{
            // 发布商品
            $result = WishHelper::callApi($product->site_id,$action,$get,$data);
            unset($product);
        }catch(\Exception $e){
            if($e->getCode()==400){         // 记录错误信息
                $this->addinfo = $e->getMessage();
                $this->save();
            }
            throw new \Exception('变体发布失败:'.$e->getMessage(), $e->getCode());
        }
        $this->addinfo = '';
        // 回写variant_id
        if($action == 'createvariant'){
            $this->variance_product_id = $result['Variant']['id'];
        }
        if(!$this->save()){
            throw new \Exception('变体保存发布失败:'.json_encode($this->getErrors(),JSON_UNESCAPED_UNICODE), 500);
        }
        return $result;
    }


}
