<?php namespace eagle\modules\listing\models;

use eagle\modules\listing\helpers\EnsogoHelper;
use eagle\modules\listing\service\Attributes;
use eagle\models\SaasEnsogoUser;
use eagle\modules\listing\models\EnsogoProxy;


class EnsogoProduct extends \eagle\models\EnsogoProduct 
{

	const IS_ENABLE_TRUE = 1;			// 全上架
	const IS_ENABLE_PART = 2; 			// 存在部分商品下架	
	const IS_ENABLE_FALSE = 3;			// 全下架

	const PUSH_PENDING = 1;
	const PUSH_SUCCESS = 2;
	const PUSH_FAIL = 3;


	static public $batchResults = [];

	public $variant;

	public function isOnline(){
		return $this->type == 1;
	}

	function __construct(){
		call_user_func_array('parent::__construct', func_get_args());
		
	}

	// 
	public function setProductStatus($status = 1){
		switch($status){
			case self::PUSH_SUCCESS:
				$this->lb_status = $this->isOnline() ? 7:1;
				break;
			case self::PUSH_FAIL:
				$this->lb_status = $this->isOnline() ? 9:4;
				break;
		}
		return $this;
	}

	/**
	 * 上下架设置
	 * @param  [type]  $boolean [description]
	 * @return boolean          [description]
	 */
	public function is_enabled_by_variance($productEnabled,Array $variances){
		$total = count($variances);
		$disabledCount = 0;
		// $this->is_enable = self::IS_ENABLE_TRUE;
		foreach($variances as $variance){
			$variance = new Attributes($variance);
			if((is_string($variance->enabled) && $variance->enabled == 'N')|| (is_bool($variance->enabled) && $variance->enabled === false)){
				++$disabledCount;
				$this->is_enable = self::IS_ENABLE_PART;
			}
		}
		if($disabledCount == $total){
			$this->is_enable = self::IS_ENABLE_FALSE;
		}
		if(!$disabledCount){
			$this->is_enable = self::IS_ENABLE_TRUE;
		}
		return $this;
	}

	private function setPrice($v){
		return explode('|',$v)[0];
	}

	private function setShipping($v){
		return explode('|',$v)[0];
	}

	private function setShipping_time($v){
		return explode('|',$v)[0];
	}
	private function setMsrp($v){
		return explode('|',$v)[0];
	}

	public function setExtraImages($product){
		if(!($v = $product->extra_images)){
			for($i=1;$i<=10;$i++){
                $key = 'extra_image_'.$i;
                $this->$key = EnsogoHelper::GetOriginalImage($product->$key);
            }
		}elseif(!$v){
			return false;
		}else{
			$imgs = array_unique(explode('|',$v ));
			foreach($imgs as $img_id=>$extra_image){
				if($img_id>9){
					var_dump($product->parent_sku);
					echo '图片超过10张';
					break;
				}
				$key = 'extra_image_'.($img_id+1);
				$this->$key = EnsogoHelper::GetOriginalImage($extra_image);
			}
		}
		return true;
	}

	public function __set($name,$args){
		parent::__set($name,$args);
		$fnName = 'set'.ucfirst($name);
		if(method_exists($this, $fnName)){
			$this->$name = call_user_func_array([$this,$fnName], [$args]);
		}
	}

	/**********************    hqf 2016-4-21 与平台的通信方法   ***************************/

	private function _isOnline(){
		if(!$this->ensogo_product_id){
			throw new \Exception($this->parent_sku.'不是在线商品', 403);
			return false;
		}
		return true;
	}

	public function getOnline(){
		$this->_isOnline();
		$data = EnsogoProxy::getInstance($this->site_id)->call('getProductById',[
			'product_id'=>$this->ensogo_product_id
		]);
		if(!$data['success']){
			throw new \Exception($data['message'], 400);
		}
		return $data['data'];
	}

	/**
	 * 获取列表
	 * @return [type] [description]
	 */
	static public function getOnlineList($site_id,$since=NULL){
		if(!$since){
			$user = SaasEnsogoUser::findOne($site_id);
			$since = $user->last_product_success_retrieve_time;
		}
		$proxy = EnsogoProxy::getInstance($site_id);
		$result = $proxy->call('getProductList',[
            'start'=>0,
            'since'=>$since,
            'limit'=>500
        ]);
		$rtn = $result['data']['data'];
		while(isset($result['data']['paging']['next'])){
			$result = $proxy->call('getNextData',[
	            'next'=>$result['data']['paging']['next']
	        ]);
	        $rtn = array_merge_recursive($rtn,$result['data']['data']);
		}
		return $rtn;
		
	}

	/**
	 * 从API同步
	 * @return [type] [description]
	 */
	public function sync($data = NULL){

	}

	/**
	 * push到ensogo
	 * @author hqf 2016-04-25
	 * @return [type] [description]
	 */
	public function push(){
		// 判断是add还是update
		if(!$this->ensogo_product_id){
			$result = $this->pushCreate();
		}else{
			$result = $this->pushUpdate();
		}
		return $result;
	}

	protected function joinExtraImages(){
		$extra_image = [];
		for($i=1;$i<=10;$i++){
			$key = "extra_image_".$i;
		    if($this->$key){
		        $extra_image[] = $this->$key;
		    }
		}
		return join("|",$extra_image);
	}

	protected function pushCreate(){
		$variants = EnsogoVariance::find()->where([
			'product_id'=>$this->id
		]);
		$idx = 0;
		$variantsResult = [];
		foreach($variants->each(1) as $variant){
			if(!$idx++){
				$data = [
					'name' 					=> $this->name,
					'description' 			=> $this->description,
					'tags' 					=> $this->tags,
					'brand' 				=> $this->brand,
					'landing_page_url' 		=> $this->landing_page_url,
					'upc' 					=> $this->upc,
					'main_image' 			=> $this->main_image,
					'extra_images' 			=> $this->joinExtraImages(),
					'parent_sku' 			=> $this->parent_sku,
					'category_id' 			=> $this->category_id,
					'sku' 					=> $variant->sku,
					'variant_name' 			=> $variant->name,
					'color'					=> $variant->color,
					'size' 					=> $variant->size,
					'inventory' 			=> $variant->inventory,
					'shipping_time' 		=> $variant->shipping_time,
					'countries' 			=> $variant->countries()->country_code,
					'sold_in_countries' 	=> $variant->countries()->country_code,
					'prices' 				=> $variant->countries()->price,
					'shippings' 			=> $variant->countries()->shipping,
					'msrps' 				=> $variant->countries()->msrp,
					// 'price' 					=>,
					// 'shipping' 				=>,
					// 'msrp' 					=>,
				];
				$productResult = EnsogoProxy::getInstance($this->site_id)->call("createProduct",[],$data);
				$this->ensogo_product_id 		= $productResult['data']['data']['product_id'];
				$this->request_id 				= $productResult['data']['data']['request_id'];
				$this->type 					= 1;
				$variant->ensogo_variance_id 	= $productResult['data']['data']['variant_id'];
				$variant->save();
			}else{
				$variantsResult[] 				= $variant->push();
			}
		}
		// 上架
		if(!$this->enabled(true)){
			$this->is_enable = 3;
		}
		$this->save();
		return [
			'product'=>$productResult,
			'variants'=>$variantsResult
		];
	}

	private function pushUpdate(){
		$variants = EnsogoVariance::find()->where([
			'product_id'=>$this->id
		]);
		$id = $this->ensogo_product_id;
		$idx = 0;
		$variantsResult = [];
		foreach($variants->each(1) as $variant){
			if(!$idx++){
				$data = [
					'name' 					=> $this->name,
					'description' 			=> $this->description,
					'tags' 					=> $this->tags,
					'brand' 				=> $this->brand,
					'landing_page_url' 		=> $this->landing_page_url,
					'upc' 					=> $this->upc,
					'main_image' 			=> $this->main_image,
					'extra_images' 			=> $this->joinExtraImages(),
					'sold_in_countries' 	=> $variant->countries()->country_code
				];
				$productResult = EnsogoProxy::getInstance($this->site_id)->call("updateProduct",[
					'product_id' 			=> $id
				],$data);
			}
			$variantsResult[] 				= $variant->push();
		}
		return [
			'product'=>$productResult,
			'variants'=>$variantsResult
		];
	}

	/**
	 * 上下架
	 * @param  [type] $enabled [description]
	 * @return [type]          [description]
	 */
	public function enabled($enabled){
		$this->_isOnline();
		if($enabled){
			$action = "enable";
		}else{
			$action = "disable";
		}
		$variants = EnsogoVariance::find()->where([
			'product_id' 	=> $this->id,
			'enable' 		=> $enabled ? 'N':'Y'
		]);
		foreach($variants->each(1) as $variant){
			$variant->enabled($enabled);
		}
		$rtn = true;
		if($this->is_enable != 1){
			$this->is_enable = 1;
			$result = EnsogoProxy::getInstance($this->site_id)->call($action."Product",[
				"product_id" => $this->ensogo_product_id
			]);
			$rtn = $result['success'];
		}
		return $rtn;
	}

	/**********************    hqf 2016-4-21 与平台的通信方法 end   ***************************/


}