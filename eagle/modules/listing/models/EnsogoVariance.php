<?php namespace eagle\modules\listing\models;

use eagle\modules\listing\models\EnsogoProduct;
use eagle\modules\util\helpers\SQLHelper;
use eagle\modules\listing\service\Attributes;
use eagle\modules\listing\models\EnsogoVarianceCountries;

use eagle\modules\listing\models\EnsogoProxy;

class EnsogoVariance extends \eagle\models\EnsogoVariance 
{

	const IS_ENABLE_TRUE = 'Y';			// 全上架
	const IS_ENABLE_FALSE = 'N';			// 全下架

	public $product;
	public $countriesInfo=[];

	static public $_value = [
		'name'=>'',
		'product_id'=>'',
		'parent_sku'=>'',
		'sku'=>'',
		'price'=>'',
		'shipping'=>'',
		'inventory'=>'',
		'blocked'=>'',
		'msrp'=>'',
		'addinfo'=>'',
		'internal_sku'=>'',
		'variance_product_id'=>'',
		'shipping_time'=>'',
		'sync_status'=>'',
		'color'=>'',
		'size'=>'',
		'enable'=>'',
		'image_url'=>''
	];

	/**
	 * 批量保存变种
	 * 先删后增
	 * @return [type] [description]
	 */
	static function multiSaveByProductId(EnsogoProduct $product,Array $data, $isUpdate = true){
		// var_dump($isUpdate);
		// 删除
		if($isUpdate){
			return self::multiUpdateByProductId($product, $data);
		}

		// echo "multi-delete;";var_dump($isUpdated);die;
		$tableName = self::tableName();
		$sql = "DELETE FROM 
		{$tableName} 
		WHERE product_id = {$product->id}";
		$query = self::getDb()->createCommand($sql);
		if($query->execute()!==false){
			// 插入
			$keys = implode(',',array_keys(self::$_value));
			$values = [];
			
			foreach($data as $idx=>$item){
				$item = new Attributes($item);
				$value = self::$_value;
				// $value['site_id'] = $product->site_id;
				foreach($value as $key=>$v){
					switch($key){
						case 'image_url':
						case 'inventory':
						case 'shipping_time':
						case 'color':
						case 'size':
						default:
							$value[$key] = $item->get($key,'');
							break;
						case 'product_id':
							$value[$key] = $product->id;
							break;
						case 'name':
							if($item->name){
								$value[$key] = $item->name;
							}elseif($item->size || $item->color){
								$value[$key] = $item->size.','.$item->color;
							}else{
								$value[$key] = $item->sku;
							}
							if(!$idx){
								$firstName = $value[$key];
							}
							break;
						case 'parent_sku':
							// var_dump($product);
							$value[$key] = $product->parent_sku;
							break;
						case 'sku':
						case 'internal_sku':
							$value[$key] = $item->sku;
							break;
						case 'variance_product_id':
							$value[$key] = $item->id;
							break;
						case 'price':
                            $name = $key.'s';
                            $value[$key] = explode('|',$item->$name)[0];
                            break;
						case 'shipping':
                            $name = $key.'s';
                            $value[$key] = explode('|',$item->$name)[0];
                            break;
						case 'msrp':
							$name = $key.'s';
							$value[$key] = explode('|',$item->$name)[0];
							break;
						case 'sync_status':
							$value[$key] = $product->lb_status;
							break;
						case 'enable':
							$value[$key] = $item->enabled ? self::IS_ENABLE_TRUE:self::IS_ENABLE_FALSE;
							break;
						case 'addinfo':
							$value[$key] = json_encode($item->_attributes);
						case 'blocked':
							$value[$key] = $item->blocked ? 0:1;
							break;
					}
				}
				$values[] = $value;
			}
			// var_dump($values);
			$total = SQLHelper::groupInsertToDb($tableName , $values, "subdb");
			if($total>=0){
				return [
					'success' 	=> true,
					'total' 	=> $total,
					'firstName' => $firstName
				];
			}else{
				$msg = $query->getErrors();
			}
		}else{
			$msg = $query->getErrors();
		}
		return [
			'success'=>false,
			'msg'=>$msg
		];
	}

	/**
	 * 设置变种名称
	 * 规则：如果有则不变，否则链接颜色及尺寸，再则用sku
	 * @param EnsogoVariance $variance    [description]
	 * @param [type]         $productName [description]
	 */
	static function setVariantName(EnsogoVariance $variance,$productName){
		// if(!$variance->name){
			if($variance->color || $variance->size){
				$variance->name = implode(' ',[$variance->color,$variance->size]);
			}else{
				$variance->name = $productName;
			}
		// }
		return $variance;
	}

	static function updateVariant(EnsogoVariance $variance,$data,EnsogoProduct $product){
		$variance->product_id 		= $data->product_id;
		// $variance->name 			= $data->name?$data->name : $data->parent_sku;
		$variance->image_url 		= $product->main_image;
		$variance->parent_sku 		= $product->parent_sku;
        $price = explode('|',$data->prices);
        $shipping = explode('|',$data->shippings);
        $msrp = explode('|',$data->msrps);
		$variance->price 			= $price[0];
		$variance->shipping 		= $shipping[0];
        $variance->msrp 			= $msrp[0];
		$variance->shipping_time 	= $data->shipping_time;
		// 运输时间
		if($data->shipping_short_time || $data->shipping_long_time){
			$variance->inventory 		= $data->shipping_short_time.'-'.$data->shipping_long_time;
		}else{
			$variance->inventory 		= $data->inventory;
		}
		$variance->enable 			= $data->enabled === true || $data->enabled == 'Y' ?"Y":"N";
		$variance->sku 				= $data->sku;
		$variance->internal_sku 	= $data->sku;
		$variance->product_id 		= $product->id;
		$variance->size 			= $data->get('size','');
		$variance->color 			= $data->get('color','');
		$variance->blocked 			= $data->blocked ? 0:1;
		$result = self::setVariantName($variance,$product->name)->save();
		if(!$result){
			var_dump($variance->getErrors());die;
		} else {
            $result = EnsogoVarianceCountries::saveVarianceCountriesInfo($variance,$data);
            if(!empty($result)){
                $result = ["success"=>false,"error"=>$result];
            } else {
                $result = true;
            }
        }
		return $result;
	}


	/**
	 * 批量更新操作
	 */
	static function multiUpdateByProductId(EnsogoProduct $product,Array $data = []){
		
		// 取出product所有的variant
		$variants = EnsogoVariance::find()->where([
			'parent_sku' => $product->parent_sku,
			'product_id' => $product->id
		])->all();

		// var_dump($data);

		$isUpdated = [];
		$errors = [];
		$msg = NULL;
		// 遍历旧的变种数据
		foreach($variants as $variant){
			// 查询是否新增数据里存在
			foreach($data as $item){
				$item = new Attributes($item);
				if($variant->sku == $item->sku){
                    $result = self::updateVariant($variant,$item,$product);
                    if(is_array($result)){//变种多站点错误
                        $msg = $result['error'];
                    } else if(!$result){//变种错误
                        $msg = $variant->getErrors();
                    } else {
                        $isUpdated[] = $variant->sku;
                    }
				}
			}
		}
		foreach($variants as $variant){
			// 查询是否新增数据里存在
			if(!in_array($variant->sku, $isUpdated)){
				// echo "delete";var_dump($isUpdated);die;
				$variant->delete();
			}
		}
		if($msg){
			return [
				'success'=>false,
				'msg'=>$msg
			];
		}
		$msg = NULL;
		// 遍历新增的数据
		foreach($data as $item){
			$item = new Attributes($item);
			if(!in_array($item->sku, $isUpdated)){
				// 查询变种sku是否重复
				
				$sql = "SELECT 
				count(*) as count
				 FROM  
				`ensogo_variance` as v
				LEFT JOIN 
				ensogo_product as p
				ON p.parent_sku = v.parent_sku 
				WHERE sku='{$item->sku}'
				AND p.site_id = {$product->site_id}";
				$connection=\Yii::$app->subdb;
				$command = $connection->createCommand($sql) ;
				$result = $command->queryAll();
				// var_dump($result);die;
				if(count($result) && $result[0]['count'] >0){
					return [
						'success'=>false,
						'msg'=>'变种sku已存在'
					];
				}

				$variant = new EnsogoVariance();
				$result = self::updateVariant($variant,$item,$product);
                if(is_array($result)){//变种多站点错误
					$msg = $result['error'];
				} else if(!$result){//变种错误
                    $msg = $variant->getErrors();
                }
			}
		}
		if($msg){
			return [
				'success'=>false,
				'msg'=>$msg
			];
		}

		return [
			'success' 	=> true,
			'total' 	=> count($data)
		];


	}


	/**********************    hqf 2016-4-21 与平台的通信方法 end   ***************************/

	function _isOnline(){
		if(!$this->ensogo_variance_id){
			throw new \Exception($this->sku."不是在线变体", 403);
		}
	}

	function getProxy(){
		return EnsogoProxy::getInstance($this->getProduct()->site_id);
	}

	function getProduct(){
		if(!$this->product){
			$this->product = EnsogoProduct::findOne($this->product_id);
		}
		return $this->product;
	}

	function enabled($enabled){
		$action = $enabled ? "enable":"disable";
		if($this->getProxy()->call($action.'ProductVariants',[
			'product_variants_id'=>$this->variance_product_id
		])['success']){
			$this->enable = $enabled ? 'Y':'N';
			$this->save();
		}
	}

	/**
	 * 获取变体的多站点信息 "|" 分隔
	 * @param  [type] $fieldName [description]
	 * @return [type]            [description]
	 */
	function countries(){
		if(!$this->countriesInfo){
			$fs = [
				'country_code','price','shipping','msrp'
			];
			$selects = [];
			foreach($fs as $f){
				$selects[] = 'GROUP_CONCAT('.$f.' SEPARATOR "|") AS '.$f;
			}
			$countriesInfo = EnsogoVarianceCountries::find()
			->select($selects)
			->where([
				'variance_id'=>$this->id
			]);
			$this->countriesInfo = $countriesInfo->one();
		}
		return $this->countriesInfo;
	}

	/**
	 * 发布变体
	 * @return [type] [description]
	 */
	public function push(){
		$data = [
			'name' 						=>$this->name,
			'color' 					=>$this->color,
			'size' 						=>$this->size,
			'inventory' 				=>$this->inventory,
			'shipping_time' 			=>$this->shipping_time,
			'prices' 					=>$this->countries()->price,
			'shippings' 				=>$this->countries()->shipping,
			'msrps' 					=>$this->countries()->msrp,
			'countries' 				=>$this->countries()->country_code,
		];
		if(!$this->variance_product_id){
			$action = "create";
			$data = array_merge($data,[
				'sku' 					=>$this->sku,
				'product_id' 			=>$this->getProduct()->ensogo_product_id
			]);
		}else{
			$action = 'update';
			$data = array_merge($data,[
				'product_variants_id' 	=>$this->variance_product_id,
				'enabled' 				=>$this->enable == 'Y'?'true':'false'
			]);
		}
		$result = $this->getProxy()->call($action."ProductVariants",[],$data);
		return $result;
	}





}