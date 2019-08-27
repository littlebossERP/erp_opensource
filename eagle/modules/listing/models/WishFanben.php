<?php namespace eagle\modules\listing\models;

// hqf 2016-05-06

use eagle\modules\listing\helpers\WishHelper;
use eagle\modules\listing\models\WishFanbenVariance;
use eagle\modules\listing\models\WishProxy;
use eagle\models\SaasWishUser;


class WishFanben extends \eagle\models\WishFanben
{

    private static $manualLabels = [];

    private static $mappingProduct = [
        'id'=>'id',
        'name'=>'name',
        'tags'=>'tags',
        'shop'=>'site_id',
        'parent_sku'=>'parent_sku',
        'inventory'=>'inventory',
        'price'=>'price',
        'shipping'=>'shipping',
        'msrp'=>'msrp',
        'shipping_time'=>'shipping_time',
        'brand'=>'brand',
        'upc'=>'upc',
        'description_nohtml'=>'description',
        'landing_page_url'=>'landing_page_url',
        'main_image'=>'main_image',
        'extra_image_1'=>'extra_image_1',
        'extra_image_2'=>'extra_image_2',
        'extra_image_3'=>'extra_image_3',
        'extra_image_4'=>'extra_image_4',
        'extra_image_5'=>'extra_image_5',
        'extra_image_6'=>'extra_image_6',
        'extra_image_7'=>'extra_image_7',
        'extra_image_8'=>'extra_image_8',
        'extra_image_9'=>'extra_image_9',
        'extra_image_10'=>'extra_image_10'
    ];
    private static $mappingVariant = [
        'fanben_id'=>'fanben_id',
        'variation_sku'=>'sku',
        'variation_color'=>'color',
        'variation_size'=>'size',
        'variation_price'=>'price',
        'variation_shipping'=>'shipping',
        'variation_inventory'=>'inventory',
        'variation_image_url'=>'image_url'
    ];

    public function save($runValidation = true, $attributeNames = NULL){
        // 如果是在线商品则不能修改parent_sku
        if($this->getOldAttribute('wish_product_id') && strtolower($this->parent_sku) != strtolower($this->getOldAttribute('parent_sku')) ){
            throw new \Exception("无法修改在线商品的 parent_sku {$this->getOldAttribute('parent_sku')}", 403);
        }
        // if(!$this->isNewRecord && $this->site_id != $this->getOldAttribute('site_id')){
        //     throw new \Exception("无法修改 site_id", 403);
        // }
        return call_user_func_array('parent::save', func_get_args());
    }

    public function delete($runValidation = true, $attributeNames = NULL){
        // 如果是在线商品则不能修改sku
        if($this->getOldAttribute('variance_product_id')){
            throw new \Exception("无法删除已上线变体：".$this->sku, 403);
        }
        return call_user_func_array('parent::delete', func_get_args());
    }

    /////////////////////////////////////// ********* 神秘的分割线 *******/////////////////////////////////////////////////////////////

    private function getExtraImages(){
        $d = [];
        for($i = 1;$i<=10;$i++){
            $columnName = 'extra_image_'.$i;
            if($this->$columnName){
                $d[] = $this->$columnName;
            }
        }
        return implode('|',$d);
    }

    // 上下架设置
    public function enable(){
        //变种商品是否存在下架商品 1不存在 2存在
        $variants = WishFanbenVariance::find()->where([
            'fanben_id'=>$this->id
        ]);
        // $total = $variants->count();
        $disabledVariantCount = $variants->andWhere([
            'enable'=>'N'
        ])->count();
        $this->is_enable = $disabledVariantCount ? 2:1;
        return !$disabledVariantCount;
    }

    /**
     * 发布商品及变体
     * @author huaqingfeng 2016-04-08
     * @return [type] [description]
     */
    public function push(){
        $data = [
            'name'              => $this->name,
            'description'       => $this->description,
            'tags'              => $this->tags,
            'main_image'        => $this->main_image,
            'brand'             => $this->brand,
            'landing_page_url'  => $this->landing_page_url,
            'upc'               => $this->upc,
            'msrp'              => $this->msrp,
            'shipping_time'     => $this->shipping_time,
            'extra_images'      => $this->getExtraImages()
        ];
        $get = [];
        if($this->wish_product_id){             // update

            $action = 'updateproduct'; 
            // $data['id'] = $this->wish_product_id;
            $data['parent_sku'] = $this->parent_sku;
            $variants = WishFanbenVariance::find()->where([
                'parent_sku'=>$this->parent_sku
            ])->all();
        }else{                                  // create
            $action = 'createproduct';
            // 从变体中获取部分信息
            list($firstVariant,$variants) = WishFanbenVariance::getFirstVariant($this->parent_sku);
            $data = array_merge($data,[
                'parent_sku'    => $this->parent_sku,
                'sku'           => $firstVariant->sku,
                'color'         => $firstVariant->color,
                'size'          => $firstVariant->size,
                'price'         => $firstVariant->price,
                'shipping'      => $firstVariant->shipping,
                'inventory'     => $firstVariant->inventory,
            ]);
        }
        // 发布商品
        try{
            $proxy = WishProxy::getInstance($this->site_id);
            $result = $proxy->call($action,$get,$data);
        }catch(\Exception $e){



            $this->error_message = $e->getMessage();
            $this->lb_status = 4;
            $this->type = $this->wish_product_id?1:2;
            $this->save();
            throw new \Exception('商品发布失败:'.$e->getMessage(), $e->getCode());
        }
        $this->error_message = '';
        // $this->lb_status = 6;
        $lbStatus = [
            'posting'   => 7,
            'pending'   => 7,
            'approved'  => 8,
            'rejected'  => 9,
        ];
        // 回写product_id  更新的状态回写要在同步的地方去执行（wish不提供update接口返回信息）
        if($action == 'createproduct'){
            $this->wish_product_id = $result['Product']['id'];
            $this->type = 1;
            $this->status = $result['Product']['review_status'];
            $this->lb_status = $lbStatus[$this->status];
            // 回写变种id
            $firstVariant->variance_product_id = $result['Product']['variants'][0]['Variant']['id'];
            if(!$firstVariant->save()){
                throw new \Exception('变体发布失败:'.json_encode($firstVariant->getErrors(),JSON_UNESCAPED_UNICODE), 500);
            }
        }
        if(!$this->save()){
            throw new \Exception('保存发布失败:'.json_encode($this->getErrors(),JSON_UNESCAPED_UNICODE), 500);
        }
        // 发布变体
        foreach($variants as $variant){
            $variant->push($this);
        }
        return $result;
    }



    static private function setSelectAlias($mapping=[],$labels = []){
        $rtn = [];
        $labels = array_merge(self::$manualLabels,$labels);
        foreach($labels as $label){
            if(isset($mapping[$label])){
                $rtn[] = $mapping[$label].' AS '.$label;
            }
        }
        return $rtn;
    }


    // 搬家
    static function getManualFormat($parent_skus,$labels = []){
        if(!count($labels)){
            $labels = array_keys(array_merge(self::$mappingProduct,self::$mappingVariant));
        }
        self::$manualLabels = $labels;
        $query = self::find()
            ->where([
                'IN','parent_sku',$parent_skus
            ])
            ->select(self::setSelectAlias(self::$mappingProduct,[
                'parent_sku',
                'id'
            ]))
            ->with('variants')
            ->asArray()->all();
        return $query;
    }

    /**
     * 变体 relation 
     * hasMany
     */
    public function getVariants(){
        $query = $this->hasMany(WishFanbenVariance::className(),[
            'fanben_id'=>'id'
        ]);
        if(count(self::$manualLabels)){
            $query->select(self::setSelectAlias(self::$mappingVariant,[
                'fanben_id','id'
            ]));
        }
        return $query;
    }

    public function getShop(){
        return $this->hasOne(SaasWishUser::className(),[
            'site_id'=>'site_id'
        ]);
    }
    
}

