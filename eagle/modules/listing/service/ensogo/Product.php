<?php namespace eagle\modules\listing\service\ensogo;

use eagle\modules\listing\models\EnsogoProduct;
use eagle\modules\listing\models\EnsogoVariance;
use eagle\modules\listing\models\EnsogoCategories;
use eagle\modules\listing\models\EnsogoProxy;


use eagle\modules\listing\models\EnsogoVarianceCountries;
use eagle\modules\listing\service\ensogo\Account;
use eagle\modules\listing\service\Log;
use eagle\modules\listing\service\Attributes;

use eagle\modules\listing\helpers\EnsogoProxyHelper;
use eagle\modules\listing\helpers\EnsogoHelper;
use eagle\modules\listing\helpers\EnsogoStoreMoveHelper;


class Product implements \eagle\modules\listing\service\Product
{
	const SYNC_PER_COUNT = 500;

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
	const LB_STATUS_ENSOGO_PENDING = 7;
	const LB_STATUS_ENSOGO_SUCCESS = 8;
	const LB_STATUS_ENSOGO_FAIL = 9;


	public $token;
	public $site_id;
	public $puid;
	public $account;
	private $lb_auth;

	function __construct($site_id){
		$this->site_id = $site_id;
		$this->account = Account::getAccountBySiteId($site_id);
		// var_dump($this->account);die;
		EnsogoProxyHelper::$token = $this->account->token;
		if(isset(\Yii::$app->user)){
			$this->puid = \Yii::$app->user->id;
		}else{
			$this->puid = 0;
		}
	}

	function getProductFromPlatform($product_id){
		$result = EnsogoProxyHelper::call('getProductById',[
            'site_id' => $this->site_id,
            'product_id'=>$product_id
		]);
		return $result['data'];
	}

	/**
	 * 从平台上获取所有范本信息
	 * @todo since时间戳
	 * @return boolean
	 */
	function getProductsFromPlatform($since=NULL){
		if(!$since){
	        $since = $this->account->last_product_success_retrieve_time ? $this->account->last_product_success_retrieve_time:'1970-01-01';
		}
        $since = gmdate("Y-m-d\TH:i:s",strtotime($since));

        $proxy = EnsogoProxy::getInstance($this->site_id);

		$result = $proxy->call('getProductList',[
            'site_id' => $this->site_id,
			'limit'=>self::SYNC_PER_COUNT,
			'since'=>$since
		]);
		if(!$result){
			return false;
		}
		$data = $result['data'];
		while(isset($result['paging']['next'])){
			// 自动获取下一页数据
			$result = $proxy->call('getNextData',[],[
                'site_id' => $this->site_id,
				'next'=>$result['paging']['next'],
                'since' => $since
			]);
			if($result['code']){
				$msg = '获取商品列表失败';
				Log::error($msg);
				return false;
			}else{
				$data = array_merge_recursive($data,$result['data']);
			}
		};
		return $data;
	}

	function testSyncAll(){
		$products = $this->getProductsFromPlatform();
		$this->saveAllProducts($products);
		return;
	}

	/**
	 * 保存商品信息（同步用）
	 * @param  Array  $products [description]
	 * @return [type]           [description]
	 */
	function saveAllProducts(Array $products, $callback = NULL, $site_id=NULL){
		// var_dump($products);
		foreach($products as $product){
			$result = $this->save($product,$site_id);
			if($callback){
				$callback($result,$product); 		// 回调事件
			}
		}
	}

	/**
	 * 修改商品的sku
	 * 只需要考虑修改的就可以，同步部分不会修改sku的，会作为新增的处理掉了
	 * 如果存在id，表示修改sku
	 * @param  EnsogoProduct $product [description]
	 * @return [type]                 [description]
	 */
	function updateProductSku(EnsogoProduct $product,$sku){
		$msg = false;
		$result = [];
		if( $product->ensogo_product_id ){
			$msg = '无法修改在线商品的sku'; // 商品类型 1在线商品 2小老板刊登商品
		}
		if(!$msg){
			// 判断是否已存在
			$exsist = EnsogoProduct::find()->where([
				'parent_sku' 	=> $sku,
				'type' 			=> 1,
				'site_id' 		=> $this->site_id,
			]);
			if($exsist->count()){
				$result = [
					'success'=>false,
					'message'=>'存在相同sku的在线商品'
				];
			}else{
				$product->parent_sku = $sku;
				$result = [
					'success'=>true,
				];
			}
		}else{
			$result = [
				'success'=>false,
				'message'=>$msg
			];
		}
		return $result;
	}


	/**
	 * 保存商品信息（主要方法）
	 * @author hqf 2016-01-22
	 * @return [type] [description]
	 * 新增：
	 * 1 没有 product_id 字段 或 通过id字段找不到对应的商品；
	 *
	 * 2 没有 product_id 字段 通过parent_sku也找不到
	 * 
	 */
	function save(Array $data, $site_id = NULL, $id = NULL){
		$data = new GetFromVariance($data);
		$isSync = ($data->id && $data->created_at);

		// 新增的情况：
		// 有 product_id （编辑）
		// 能根据product_id 找到数据
		if($isSync && $product = EnsogoProduct::find()
				->where([
					'parent_sku'=>$data->parent_sku,
					'site_id' => $this->site_id
				])->one()){ 						// 同步
		}elseif($data->product_id ){ 				// 编辑
			if( !$product = EnsogoProduct::find()
				->where([
					'id'=>$data->product_id
				])->one() ){
				$product = new EnsogoProduct;
				$product->create_time = date('Y-m-d H:i:s');
			}
		}else{ 										// 新增
			if(!isset($product) || !$product){
				$product = new EnsogoProduct;
				$product->create_time = date('Y-m-d H:i:s');
			}
		}
		if($isSync){ 					// 平台同步的数据
			$product->ensogo_product_id = $data->id;
			$product->lb_status = self::LB_STATUS_ENSOGO_SUCCESS;
			// $this->updateProductSku($product, $data->parent_sku);
	        $product->parent_sku = $data->parent_sku;
        	$product->site_id = $this->site_id;
			$product->type = 1;
		}else{
			// 修改SKU需要先判断是否提交到平台上
			if(in_array($product->lb_status, [
				self::LB_STATUS_ENSOGO_PENDING,
				self::LB_STATUS_ENSOGO_SUCCESS,
				self::LB_STATUS_ENSOGO_FAIL
			])){
				// 已经发布过的
				if($product->parent_sku != $data->parent_sku){ 		// 已经发布过的商品的 sku 不能修改
					return [
						'success'=>false,
						'type'=>'fail',
						'message'=>'now sku can not change'
					];
				}
				// $product->lb_status = self::LB_STATUS_ENSOGO_PENDING;
				$product->type = 1;
			}else{
				// 新增的时候判断sku是否已存在（暂时不用，以后可能需要检测）
				$skuChange = $this->updateProductSku($product, $data->parent_sku);
				if(!$skuChange['success']){
					return [
						'success'=>false,
						'type'=>'fail',
						'message'=>$skuChange['message']
					];
				}
				$product->lb_status = self::LB_STATUS_WAIT;
				$product->site_id = $this->site_id;
				$product->type = 2;
			}
		}
		$product->brand = $data->brand;
        $product->update_time = date('Y-m-d H:i:s');
		$product->category_id = $data->category_id;
        $product->capture_user_id = $this->puid;
        $product->tags = $data->tags; 		// 和wish不同，直接逗号分隔的
        $product->variance_count = count($data->variants);
        $product->description = $data->description;
        $product->name = $data->name;
        $product->json_info = $data->json_info;
        $product->status = $data->get('review_status','');
        $product->number_saves = $data->get('number_saves',0);  	//
        $product->number_sold = $data->get('number_sold',0); 		//
        $product->upc = $data->upc;
        $product->main_image = $data->get('main_image','');
        $product->landing_page_url = $data->get('landing_page_url','');
        $product->is_enabled_by_variance($data->enabled,$data->variants); 		// 上下架
        $product->setExtraImages($data);
		$product->json_info = $isSync?'Y':"N";
		$product->blocked = $data->blocked ? 0:1;
        $product->sale_type = $data->get('sale_type',2);//售卖形式: 1单品 2多变种
		$new = $product->isNewRecord;
		
		if($product->save(false)){

			$id = $product->id;
			Log::info('save product success: '.$id);
			// 更新变种信息
			$varianceSaveResult = EnsogoVariance::multiSaveByProductId($product,$data->variants,!$isSync);
			if($varianceSaveResult['success']==true){
				$product->save();
                $rtn = [
                    'success'=>true,
                    'type'=>$new ? 'insert':'update',
                    'product'=>$product
                ];
                if($isSync){
                    $varianceCountriesresult = EnsogoVarianceCountries::multiSaveByVarianceId($product,$data->variants);
                    if($varianceCountriesresult['success'] != true){
                        $rtn = [
                            'success'=>false,
                            'type'=>'fail',
                            'id'=>NULL,
                            'message'=>$varianceCountriesresult['msg']
                        ];
                    }
                }
			}else{
				$rtn = [
					'success'=>false,
					'type'=>'fail',
					'id'=>NULL,
					'message'=>$varianceSaveResult['msg']
				];
			}
		}else{
			$rtn = [
				'success'=>false,
				'type'=>'fail',
				'id'=>NULL,
				'message'=>$product->getErrors()
			];
		}
		unset($product);
		return $rtn;
	}


	function testSave(){
		$data = [
			 'category_id' => '2'
			,'name' => 'weawsdfasdfas'
			,'tags' => 'fasdfasdf'
			,'parent_sku' => 'TestItem010'
			,'brand' => 'fasdfasdfasf'
			,'upc' => 'asdfasdfasfas'
			,'lb_status' => '1'
			,'description' => 'afsdfasdfasdfgsdfherthsr'
			,'enabled' => '1'
			,'main_image' => 'http://image.littleboss.com/1/20160126/20160126164016-29ea697.jpg?imageView2/1/w/210/h/210'
			,'extra_image_1' => 'http://image.littleboss.com/1/20160126/20160126164017-848da0.jpg?imageView2/1/w/210/h/210'
			,'extra_image_2' => 'http://image.littleboss.com/1/20160126/20160126164017-357110c.jpg?imageView2/1/w/210/h/210'
			,'extra_image_3' => ''
			,'extra_image_4' => ''
			,'extra_image_5' => ''
			,'extra_image_6' => ''
			,'extra_image_7' => ''
			,'extra_image_8' => ''
			,'extra_image_9' => ''
			,'extra_image_10' => ''
			,'variants' => [
	            [
	                'color' => 'Ivory',
	                'size' => 'X',
	                'sku' => 'TestItem001',
	                'prices' => '1231',
	                'msrps' => '1231',
	                'inventory' => '12',
	                'shippings' => '12',
	                'shipping_time' => '12-23',
	                'enabled' => 'Y',
	            ],[
	                'color' => 'Jasper',
	                'size' => 'X',
	                'sku' => 'TestVariant 002',
	                'prices' => '1231',
	                'msrps' => '1231',
	                'inventory' => '12',
	                'shippings' => '12',
	                'shipping_time' => '12-23',
	                'enabled' => 'Y',
	            ]
			]
		];
		return $this->save($data);
	}

	/**
	 * 发布
	 * @param  EnsogoProduct $product [description]
	 * @return [type]                             [description]
	 */
	function push(EnsogoProduct $product){

		// 判断是否新增
		// 获取变种
		$variants = EnsogoVariance::find()->where([
			'parent_sku'=>$product->parent_sku,
			'product_id'=>$product->id
		])->all();
        $firstVariant = [];
		if($product->ensogo_product_id){ 		// 更新操作
			$action = "update";
			$addinfo = ['product_id'=>$product->ensogo_product_id];
			$firstVariant = $variants[0];
		}else{
			// echo 'create';
			$action = "create";
			$addinfo = [];
	        $firstVariant = array_shift($variants);
		}
		$info = [];
		\Yii::info('ensogoTime1:'.date('H:i:s'),'file');
        // foreach($variants as $v){
        //     $firstVariant = $v;
        //     break;
        // }
        $data = EnsogoHelper::setProductInfo($product,$action,$firstVariant);

		// 先删除平台上的图片
		// if($action == 'update'){
		// 	EnsogoProxyHelper::call('removeExtraImages',['product_id'=>$product->ensogo_product_id]);
		// }

		$data['site_id'] = $this->site_id;
		$productResult = EnsogoProxyHelper::call($action."Product",[],$data);
		$info['product'] = $productResult;
		// var_dump($productResult);
		// 
		$product->addinfo = json_encode($productResult);
		if($productResult['code']){
			$product->setProductStatus(EnsogoProduct::PUSH_FAIL);
			$msg = is_array($productResult['message'])?$productResult['message']:[$productResult['message']];
			$product->error_message = implode(";",$msg);
		}else{
			// 记录request_id
			try{
				$puid = \Yii::$app->user->identity->getParentUid();
			}catch(\Exception $e){
				$puid = 0;
			}
			if( !isset($productResult['data']['product_id'])){
				if($product->ensogo_product_id){
					$productResult['data']['product_id'] = $product->ensogo_product_id;
				}else{
					$productResult['data']['product_id'] = 0;
				}
			}
			if(!isset($puid,$productResult['data']['variant_id'])){
				$productResult['data']['variant_id'] = 0;
			}
			//记录日志
			EnsogoStoreMoveHelper::_saveApiAjaxLog($puid,$productResult['data']['product_id'],$productResult['data']['variant_id'],$productResult['data']['request_id']);
			if(isset($productResult['data']['variant_id']) && $productResult['data']['variant_id']){
				$firstVariant->variance_product_id = $productResult['data']['variant_id'];
				$firstVariant->save();
			}

			$product->type = 1;
			$product->request_id 			= $productResult['data']['request_id'];
			$product->ensogo_product_id 	= $productResult['data']['product_id'];
			$product->setProductStatus(EnsogoProduct::PUSH_SUCCESS);
			$product->save(false);

			\Yii::info('ensogoTime2:'.date('H:i:s'),'file');
			$variantResult = [];



			foreach($variants as $variant){
				if(strlen($variant->variance_product_id)){
					$action = "update";
					$addinfo = ['product_variants_id'=>$variant->variance_product_id];
					// var_dump($variant->attributes);
					// var_dump($addinfo);
				}else{
					$action = "create";
					$addinfo = [];
				}
				$post_data = array_merge(EnsogoHelper::setProductVariantsInfo($variant),[
						'product_id'=>$product->ensogo_product_id
					],$addinfo);
				$Result = EnsogoProxyHelper::call($action."ProductVariants",[],$post_data);
				\Yii::info('ensogoTime3:'.date('H:i:s'),'file');

				$variant->addinfo = json_encode($Result);
				if($Result['code']){
					$msg = is_array($Result['message'])?$Result['message']:[$Result['message']];
					$variant->error_message = implode(";",$msg);
				}else{
					if(isset($Result['data'])){
						$variant->addinfo = json_encode($Result['data']);
						if(isset($Result['data']['variant_id'])){
							$variant->variance_product_id = $Result['data']['variant_id'];
							EnsogoStoreMoveHelper::_saveApiAjaxLog($puid,0,$Result['data']['variant_id'],$Result['data']['request_id']);
						}

					}
				}
				$variant->save();
				$variantResult[] = $Result;
			}
			\Yii::info('ensogoTime4:'.date('H:i:s'),'file');
			// $info['variants'] = $variantResult;
		}
		$product->save();

		return $info;
		// [
		// 	'product'=>$productResult,
		// 	'variants'=>$variantResult
		// ];
	}

	function testPush($product_id){
		$p = EnsogoProduct::find()
		->where([
			'id'=>$product_id
		]);
		// echo $p->createCommand()->getRawSql();
		// var_dump($p->one());die;
		return $this->push($p->one());
	}

	function getProductAndVariance(Array $products,$enable){
		return array_map(function($product)use($enable){
			$product->variant = EnsogoVariance::find()->where([
				'parent_sku'=>$product->parent_sku,
				'product_id'=>$product->id,
				'enable' => $enable
			])->all();
			if(empty($product->variant)){
				unset($product);
			}else{
				return $product;
			}
		},$products);

	}

	/**
	 * 获取并保存所有的目录数据到db
	 * @return  true or false
	 */
	function refreshAllCategories(){
	    $result = EnsogoProxyHelper::call('getCategoriesList',[
	         
	    ]);
	    if (!isset($result['data']) or $result['data']=="") return false;
	    $categoryTree = $result['data'];
	    
	    $parentCategoryIdsArr=array();
	    foreach($categoryTree as $oneCategory){
	    	if (isset($oneCategory['parent_id'])) $parentCategoryIdsArr[]=$oneCategory['parent_id'];
	    	
	    }
	    
	    foreach($categoryTree as &$oneCategory){
	    	
	    	if (in_array($oneCategory["id"],$parentCategoryIdsArr)){
	    		//有下级
	    		$oneCategory["is_leaf"]=0;
	    	}else{
	    		$oneCategory["is_leaf"]=1;
	    	}
	      }
	    
	     
	    $site="all";
	    // 目录先记录在数据库中，后面再考虑可能搬到redis里面
	    $categoriesJsonStrObj = EnsogoCategories::findOne(['site'=>$site]);
	    $nowTime=time();
	    if ($categoriesJsonStrObj===null){
	        $categoriesJsonStrObj = new EnsogoCategories;
	        $categoriesJsonStrObj->site=$site;
	        $categoriesJsonStrObj->create_time=$nowTime;
	    }
	    $categoriesJsonStrObj->update_time=$nowTime;
	     
	    $categoriesJsonStrObj->categories = json_encode($categoryTree);
	    if(!$categoriesJsonStrObj->save(false)){
	        \Yii::error("getCategoryTree error:".print_r($categoriesJsonStrObj->errors,true),"file");
	        return false;
	    }
	    return true;
	
	}
	
	/**
	 * 获取指定目录id的所有儿子目录*
	 * @param  $parentCategoryId----父目录id， 当$parentCategoryId=-1; 表示要获取第一层的目录
	 * @return multitype:mixed	 * Array (   [0] => Array
	 (
	 [id] => 2
	 [parent_id] => 1
	 [depth] => 1
	 [name] => Bath & Skincare
	 [name_zh_tw] => 沐浴及護膚
	 )
	 [1] => Array
	 (
	 [id] => 3
	 [parent_id] => 1
	 [depth] => 1
	 [name] => Diapers
	 [name_zh_tw] => 尿片
	 )
	 *
	 */
	
	function getChildCategories($parentCategoryId){
	
	    //$parentCategoryId=-1; 表示获取第一层的目录
	    if ($parentCategoryId==-1) $parentCategoryId=null;
	
	    $site="all";
	    $rtnCats=array();
	    // 目录先记录在数据库中，后面再考虑可能搬到redis里面
	    $categoriesJsonStrObj = EnsogoCategories::findOne(['site'=>$site]);
	    if($categoriesJsonStrObj===null){
	        $ret=$this->refreshAllCategories();
	        if ($ret===true){
	            $categoriesJsonStrObj = EnsogoCategories::findOne(['site'=>$site]);
	            if($categoriesJsonStrObj===null) return array();
	        }else{
	            return array();
	        }
	         
	    }
	     
	    //$categoriesJsonStr = $categoriesJsonStrObj->categories;
	    $categories = json_decode($categoriesJsonStrObj->categories , true);
	    foreach($categories as $category){
	        if ($category["parent_id"]==$parentCategoryId){
	            $rtnCats[] = $category;
	        }
	    }
	     
	     
	    return $rtnCats;
	
	}
	function getAllParentCategories($childCategoryId){
		$rtnCats = array();
		$category = self::getParentCategories($childCategoryId);
		if(!empty($category)){
			array_unshift($rtnCats,$category);
			for($i=0;$i<=$category['depth'];$i++){
				$category = self::getParentCategories($category['parent_id']);
				array_unshift($rtnCats,$category);
			}
		}
		return $rtnCats;
	}

	function getParentCategories($childCategoryId){
		$site = 'all';
		$rtnCats = array();
		$categoriesJsonStrObj = EnsogoCategories::findOne(['site'=>$site]);
		if($categoriesJsonStrObj===null){
	        $ret=$this->refreshAllCategories();
	        if ($ret===true){
	            $categoriesJsonStrObj = EnsogoCategories::findOne(['site'=>$site]);
	            if($categoriesJsonStrObj===null) return array();
	        }else{
	            return array();
	        }
	         
	    }
		$categories = json_decode($categoriesJsonStrObj->categories,true);	
		foreach($categories as $category){
			if($category['id'] == $childCategoryId){
				$rtnCats = $category;
			}
		}
		return $rtnCats;
	}


	function testSavePush(){

		$rawData = 'category_id=2&name=weawsdfasdfas&tags=fasdfasdf&parent_sku=asdfasdfasdf&prices=1231&msrp=123&shipping=12&shipping_time=12-23&inventory=123&brand=fasdfasdfasf&upc=asdfasdfasfas&lb_status=1&description=afsdfasdfasdfgsdfherthsr&enabled=1&main_image=http%3A%2F%2Fimage.littleboss.com%2F1%2F20160126%2F20160126164016-29ea697.jpg%3FimageView2%2F1%2Fw%2F210%2Fh%2F210&extra_image_1=http%3A%2F%2Fimage.littleboss.com%2F1%2F20160126%2F20160126164017-848da0.jpg%3FimageView2%2F1%2Fw%2F210%2Fh%2F210&extra_image_2=http%3A%2F%2Fimage.littleboss.com%2F1%2F20160126%2F20160126164017-357110c.jpg%3FimageView2%2F1%2Fw%2F210%2Fh%2F210&extra_image_3=&extra_image_4=&extra_image_5=&extra_image_6=&extra_image_7=&extra_image_8=&extra_image_9=&extra_image_10=&variants%5B0%5D%5Bcolor%5D=Ivory&variants%5B0%5D%5Bsize%5D=X&variants%5B0%5D%5Bsku%5D=asdfasdfasdf-Ivory-X&variants%5B0%5D%5Bprices%5D=1231&variants%5B0%5D%5Bmsrps%5D=1231&variants%5B0%5D%5Binventory%5D=12&variants%5B0%5D%5Bshippings%5D=12&variants%5B0%5D%5Bshipping_time%5D=12-23&variants%5B0%5D%5Benabled%5D=Y&variants%5B1%5D%5Bcolor%5D=Jasper&variants%5B1%5D%5Bsize%5D=X&variants%5B1%5D%5Bsku%5D=asdfasdfasdf-Jasper-X&variants%5B1%5D%5Bprices%5D=1231&variants%5B1%5D%5Bmsrps%5D=1231&variants%5B1%5D%5Binventory%5D=12&variants%5B1%5D%5Bshippings%5D=12&variants%5B1%5D%5Bshipping_time%5D=12-23&variants%5B1%5D%5Benabled%5D=Y';
		parse_str($rawData,$data);

		$saveResult = $this->save($data);

		return $saveResult;

		// $pushResult = $this->push($saveResult['product']);
		// return $pushResult;
	}

	static function testStaticMethod(Array $arr){

		return array_reverse($arr);
	}


}
