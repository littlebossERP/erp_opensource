<?php namespace eagle\modules\listing\service\wish;

use eagle\modules\listing\service\ProxyConnectHelper;
use eagle\modules\listing\helpers\WishProxyConnectKandengHelper;
use eagle\modules\listing\service\wish\Account;
use eagle\modules\listing\helpers\SaasWishFanbenSyncHelper;
use eagle\modules\listing\helpers\WishHelper;
use eagle\modules\listing\service\Log;
use eagle\modules\listing\service\Attributes;

use eagle\modules\listing\models\WishFanben;
use eagle\modules\listing\models\WishFanbenVariance;

class Product implements \eagle\modules\listing\service\Product
{
	public $token;
	public $site_id;
	public $puid;

	const STATUS_COMPLETE = "complete";
	const STATUS_ONLINE = "online";
	const STATUS_EDITING = "editing";
	const STATUS_ERROR = "error";
	const STATUS_UPLOADING = "uploading";
	
	const LB_STATUS_WAIT = 1;
	const LB_STATUS_AUDIT = 2;
	const LB_STATUS_SUCCESS = 3;
	const LB_STATUS_FAIL = 4;
	const LB_STATUS_DELETE = 5;
	const LB_STATUS_ONLINE = 6;
	const LB_STATUS_WISH_PENDING = 7;
	const LB_STATUS_WISH_SUCCESS = 8;
	const LB_STATUS_WISH_FAIL = 9;


	const ASYNC_INTERVAL = 72000;

	static $LB_STATUS = [
		7 => ['posting','pending'],
		8 => ['online','approved'],
		9 => ['rejected']
	];


	final static function toLbStatus($wishStatus){
		foreach(self::$LB_STATUS as $id=>$status){
			if(in_array( $wishStatus, $status )){
				return $id;
			}
		}
		return self::LB_STATUS_ONLINE;
	}

	function __construct($site_id){
		$this->site_id = $site_id;
		$account = Account::getAccountBySiteId($site_id);
		$this->token = $account->token;
		$this->puid = $account->uid;
	}

	/**
	 * 根据sku查询平台上是否存在
	 * @param  [type] $sku [description]
	 * @return [type]      [description]
	 */
	function checkExistBySkuOnPlatform($sku){
		$result = ProxyConnectHelper::call_WISH_api('getproduct',[
			'sku'=>$sku,
			'token'=>$this->token
		]);
		return $result['proxyResponse']['success'];
	}

	/**
	 * 检查变种是否存在
	 * @param  [type] $sku   [description]
	 * @param  [type] $token [description]
	 * @return [type]        [description]
	 */
	function checkVariantExistBySkuOnPlatform($sku){
		$result = ProxyConnectHelper::call_WISH_api('getvariant',[
			'sku'=>$sku,
			'token'=>$this->token
		]);
		return $result['proxyResponse']['success'];
	}

	// 上下架商品
	function changeProductStatus($sku,$status=false){
		$result = ProxyConnectHelper::call_WISH_api('productstatus',[
			'sku'=>$sku,
			'enable'=>$status?'on':'off',
			'token'=>$this->token
		]);
		return $result['proxyResponse']['success'];
	}

	/**
	 * 从平台上获取所有变更的范本信息
	 * @return [type] [description]
	 */
	function getProductsFromPlatform(){
		// 先获取lastTime
		$account = Account::getAccountBySiteId($this->site_id);
		$products = SaasWishFanbenSyncHelper::getAllWishFanbenByPagination($this->token,$account->last_product_success_retrieve_time);
		// var_dump($products);die;
		if($products['success']){
			return $products['product'];
		}else{
			Log::error($products['message']);
			throw new \Exception($products['message']);
			// trigger_error($products['message']);
			return false;
		}
	}

	/**
	 * 保存商品信息（同步用）
	 * @param  Array  $products [description]
	 * @return [type]           [description]
	 */
	function saveAllProducts(Array $products,$callback=NULL,$site_id=NULL){
		foreach($products as $product){
			$product = new Attributes($product);
			$result = $this->asyncProduct($product,$site_id);
			if($callback){ 
				$callback($result,$product); 		// 回调事件
			}
		}
	}

	static private function getValFromArray($arr,$key,$ifNull=''){
		return isset($arr[$key])?$arr[$key]:$ifNull;
	}

	/**
	 * 设置tags
	 * 取id改成取name
	 * @author huaqingfeng 2016-04-08
	 * @param  [type] $tags [description]
	 * @return [type]       [description]
	 */
	static private function getTags($tags){
		if(!is_string($tags)){
	        $return_tag = [];
	        foreach($tags as $key => $data){
	            $return_tag[] = $data['Tag']['name'];
	        }
	        return join(',',$return_tag);
		}else{
			return $tags;
		}
    }


    /**
     * 保存商品
     * @param  [type] $data [description]
     * @return [type]       [description]
     *  'success'=>true,
	 * 	'type'=>$type,
	 * 	'id'=>$fanben->id
     */
	function asyncProduct($data,$site_id=NULL){
		// var_dump($data);die;
		$type='update';
		if(!isset($data->parent_sku)){
			$data->parent_sku = $data->variants[0]['Variant']['sku'];
		}
		// 查询是否存在
		if(!$fanben = WishFanben::find()->where([
			'parent_sku' => $data->parent_sku
		])->one()){
			$fanben = new WishFanben;
			$fanben->create_time = date('Y-m-d H:i:s');
			// $fanben->lb_status = self::toLbStatus($data->review_status);
			$fanben->site_id = $this->site_id;
			$fanben->brand = $data->brand;
			//第一次获取商品单价、运费、库存 来源是第一个变种
			$variants = new Attributes($data->variants[0]['Variant']);
			$fanben->price = $variants->price;//单价
			$fanben->inventory = $variants->inventory;//指导价格
			$fanben->msrp = $variants->msrp;//运费
			$fanben->shipping = $variants->shipping;
			$fanben->shipping_time = $variants->shipping_time;//快递时间
			$fanben->wish_product_id = $data->id;
		}
		if($site_id){
			$fanben->site_id = $site_id;
		}
		$fanben->type = 1;
		$fanben->lb_status = self::toLbStatus($data->review_status);
        $fanben->update_time = date('Y-m-d H:i:s');
        $fanben->capture_user_id = $this->puid;
        $fanben->tags = self::getTags($data->tags);
        $fanben->variance_count = count($data->variants);
        $fanben->parent_sku = $data->parent_sku;
        $fanben->description = $data->description;
        $fanben->name = $data->name;
        $fanben->status = $data->review_status;
        $fanben->number_saves = $data->number_saves;  	//
        $fanben->number_sold = $data->number_sold; 		//
        $fanben->upc = $data->upc;
        $fanben->main_image = $data->main_image;
        
		foreach( explode('|',$data->get('extra_images','')) as $img_id=>$extra_image){
			if($img_id>9){
				Log::info('图片超过10张');
				break;
			}
			$key = 'extra_image_'.($img_id+1);
			$fanben->$key = $extra_image;
		}
		if($fanben->save()){
			// 更新变种信息
			$variance = $this->saveVarianceByFanben($data->variants,$fanben);
			$ret = [
				'success'=>true,
				'type'=>$fanben->isNewRecord?"insert":"update",
				'sku'=>$data->parent_sku
			];
		}else{
			$ret = [
				'success'=>false,
				'type'=>'fail',
				'sku'=>NULL,
				'message'=>$fanben->getErrors()
			];
		}
		unset($fanben);
		return $ret;
	}

	/**
	 * 保存变种
	 * @return [type] [description]
	 */
	protected function saveVarianceByFanben($variants,WishFanben $fanben){
		// 如果变种全下架，商品也要下架
		$is_enable = 1;//变种商品是否存在下架商品 1不存在 2存在
		$disabled = 0;
		$total = count($variants);
		foreach($variants as $v){
			$item = $v['Variant'];
			// 判断是否存在
			$variance = WishFanbenVariance::find()
				->where([
					'sku'=>$item['sku']
				])->one();
			if(!$variance){
				$variance = new WishFanbenVariance;
			}
			$variance->sync_status = 'online';
			$variance->fanben_id = $fanben->id;
			$variance->sku = $item['sku'];
			$variance->parent_sku = $fanben->parent_sku;
			$variance->price = $item['price'];
			$variance->enable = $item['enabled']=='True'?'Y':'N';
			$variance->variance_product_id = $item['id'];
			$variance->shipping = $item['shipping'];
			$variance->inventory = $item['inventory'];
			$variance->image_url = $item['all_images'];
			$variance->color = self::getValFromArray($item,'color');
			$variance->size = self::getValFromArray($item,'size');
			$variance->addinfo = json_encode($item);
		//	if(!$variance->save()){     lolo20161021
			if(!$variance->saveRaw()){
				if($variance->enable == 'N'){
					$disabled++;
				}
				Log::error(PHP_EOL.'保存变种信息失败.'.$item['sku'].var_export($variance->getErrors()).PHP_EOL);
			}else{
				Log::info('variance success');
			}
		}
		if($total == $disabled){ 	// 全部商品下架
			$is_enable = 3;
		}elseif($disabled){
			$is_enable = 2;
		}
		$fanben->is_enable = $is_enable;
		return true;
	}

	/**
	 * 保存
	 * @param  array  $data   [description]
	 * @param  boolean $insert [description]
	 * @return WishFanben object          [description]
	 */
	function save(Array $data,$insert = false){
		$sku = $data['parent_sku'];
		if(!$Product = WishFanben::find()->where([
			'parent_sku' => $sku
		])->one()){
			$Product = new WishFanben;
		}
	}


	/**
	 * 发布
	 * @param  WishFanben $product [description]
	 * @return [type]              [description]
	 */
	function push(WishFanben $product){
		$result = ['product'=>[],'variants'=>[]];
		if($product->wish_product_id){
			$product_result = $this->pushUpdateProduct($product);
		}else{
			$product_result = $this->pushCreateProduct($product);
		}
		if(!$product_result['success'] || !$product_result['proxyResponse']['success']){
			$product->error_message = $product_result['proxyResponse']['success'];
			$result['product'] = [
				'success'=>false,
				'message'=>$product_result['proxyResponse']['success']
			];
		}else{
			$responseProduct = $result['proxyResponse']['data']['Product'];
			// 回写状态
			$product->type = 1;
			$product->lb_status = 7;
			$product->status = $responseProduct['review_status'];
			$product->wish_product_id = $responseProduct['id'];

			// 发布变体
			$result['variatns'] = [];

		}

		return $result;

	}

	private function pushCreateProduct(WishFanben $product){
		// 新增
		$data = [
			'name' 				=> $product->name,
			'description' 		=> $product->description,
			'tags' 				=> $product->tags,
			'sku' 				=> $product->parent_sku,
			'parent_sku' 		=> $product->parent_sku,
			'color' 			=> $product->color,
			'size' 				=> $product->size,
			'inventory' 		=> $product->inventory,
			'price' 			=> $product->price,
			'shipping' 			=> $product->shipping,
			'msrp' 				=> $product->msrp,
			'shipping_time' 	=> $product->shipping_time,
			'main_image' 		=> $product->main_image,
			'parent_sku' 		=> $product->parent_sku,
			'brand' 			=> $product->brand,
			'landing_page_url' 	=> $product->landing_page_url,
			'upc' 				=> $product->upc,
			'extra_images' 		=> $product->extra_images

		];
		// 发布商品
		$product_result = WishProxyConnectKandengHelper::call_WISH_api("createproduct",[
			'access_token' => $this->token
		],$data);
		return $product_result;
	}

	private function pushUpdateProduct(WishFanben $product){

	}

	private function pushCreateVariant(WishFanbenVariance $variant){

	}

	private function pushUpdateVariant(WishFanbenVariance $variant){

	}


	function testSave($sku){
		return $this->save(['parent_sku'=>$sku]);
	}

	function testViewProduct($sku){
		$result = ProxyConnectHelper::call_WISH_api('getproduct',[
			'sku'=>$sku,
			'token'=>$this->token
		]);
		return $result['proxyResponse'];
	}

	function testSyncProductBySku($sku){
		$result = ProxyConnectHelper::call_WISH_api('getproduct',[
			'sku'=>$sku,
			'token'=>$this->token
		])['proxyResponse'];
		return $this->save($result['wishReturn']['data']['Product']);
	}

	function getOnlineInfo($sku){
		$result = ProxyConnectHelper::call_WISH_api('getproduct',[
			'sku'=>$sku,
			'token'=>$this->token
		])['proxyResponse'];
		return $result;
	}

	function postOne($sku){
		$product = WishFanben::find()->where([
			'parent_sku'=>$sku
		])->one();
		return $product->push();
	}


}
