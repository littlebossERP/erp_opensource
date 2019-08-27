<?php
/*+----------------------------------------------------------------------
| 小老板
+----------------------------------------------------------------------
| Copyright (c) 2011 http://www.xiaolaoban.cn All rights reserved.
+----------------------------------------------------------------------
| Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
+----------------------------------------------------------------------
| Author: victor
+----------------------------------------------------------------------
| Create Date: 2016-01-26
+----------------------------------------------------------------------
 */
namespace eagle\modules\listing\helpers;
use yii;
use yii\data\Pagination;
use eagle\modules\listing\models\EnsogoProduct;
use eagle\modules\listing\models\EnsogoVariance;
use eagle\modules\listing\helpers\EnsogoProxyHelper;
use eagle\models\SaasEnsogoUser;
use eagle\models\EnsogoWishTagQueue;
use eagle\modules\listing\models\EnsogoCategories;
use eagle\modules\listing\models\EnsogoVarianceCountries;
use eagle\modules\listing\service\ensogo\Product;
use eagle\modules\listing\config\params;
use eagle\modules\manual_sync\models\Queue;
/**
 * 
 +------------------------------------------------------------------------------
 * 刊登模块模板业务
 +------------------------------------------------------------------------------
 * @category	Order
 * @package		Helper/order
 * @subpackage  Exception
 * @author		fanjs
 +------------------------------------------------------------------------------
 */
class EnsogoHelper {

	public static function getProductList($params=[], $sort='', $order='' , $pageSize = 20){
		if(empty($sort)){
			$sort = 'create_time';
			$order = 'desc';
		}

		$filterStr = '1';
		if(!empty($params)){
			foreach($params as $k => $v){
				$v = str_replace("'", '', $v);
				$v = str_replace('"','',$v);
				if($k == 'keyword'){
					$filterStr .= "and (name  like '%$v%' or parent_sku like '%$v%')";
				}else{
					if($k == 'parent_sku' || $k == 'name'){
						$filterStr .= " and $k like '%$v%'";	
					}else{
						$filterStr .= " and $k = '$v'";
					}
				}
			}
		}
		$data ['condition'] = $filterStr;
		$query = EnsogoProduct::find();
		$pagination = new Pagination([
			'defaultPageSize' => 20,
			'pageSize' => $pageSize,
			'totalCount' => $query->where($filterStr)->count(),
			'pageSizeLimit' =>[5,10,20,50], //每页显示条数范围
			'params'=>$_REQUEST,
		]);
    

		$data['pagination'] = $pagination;
		$data['data'] = $query
		->andWhere($filterStr)
		->offset($pagination->offset)
		->limit($pagination->limit)
		->orderBy("$sort $order")
		->asArray()
		->all();

		foreach ($data['data'] as &$aFanben){
			$aFanben['variance_data'] = EnsogoVariance::find()
			->andWhere([ 'parent_sku'=>$aFanben['parent_sku'] ])
			->orderBy(" sku asc ")
			->asArray()
			->all();;
		}

		return $data;
	}

    public static function setProductInfo($product_obj,$action = 'create',$variant_obj){

        $return_product = [];
        if($product_obj->ensogo_product_id && $action == 'update'){
            $return_product['id'] = $product_obj->ensogo_product_id;
            $return_product['product_id'] = $product_obj->ensogo_product_id;
        } else {
            $return_product['parent_sku'] = $product_obj->parent_sku;
            $return_product['category_id'] = $product_obj->category_id;
            $contries_info = self::setCountriesInfo($variant_obj);
            $return_product['countries'] = $contries_info['countries'];
            $return_product['prices'] = $contries_info['prices'];
            $return_product['shippings'] =  $contries_info['shippings'];
            $return_product['msrps'] = $contries_info['msrps'];
            $return_product['sold_in_countries'] = $contries_info['countries'];
            $return_product['variant_name'] = $variant_obj->name;
            $return_product['sku'] = $variant_obj->sku;
            $return_product['inventory'] = $variant_obj->inventory;
            $return_product['shipping_time'] = $variant_obj->shipping_time;
            $return_product['color'] = $variant_obj->color;
            $return_product['size'] = $variant_obj->size;
        }
        $return_product['name'] = $product_obj->name;
        $return_product['description'] = $product_obj->description;
        $return_product['tags'] = $product_obj->tags;
        $return_product['main_image'] = $product_obj->main_image;
        $return_product['brand'] = $product_obj->brand;
        $return_product['landing_page_url'] = $product_obj->landing_page_url;
        $return_product['upc'] = $product_obj->upc;
        $return_product['extra_images'] = self::getExtraImages($product_obj);

        return $return_product;
    }

    public static function setProductVariantsInfo($variants_obj){
        $return_variant = [];
        $return_variant['product_id'] = $variants_obj->product_id;
        $return_variant['sku'] = $variants_obj->sku;
        $return_variant['name'] = $variants_obj->name;
        $return_variant['color'] = $variants_obj->color;
        $return_variant['size'] = $variants_obj->size;
        $return_variant['inventory'] = $variants_obj->inventory;
        $contries_info = self::setCountriesInfo($variants_obj);

        $return_variant['countries'] = $contries_info['countries'];
        $return_variant['prices'] = $contries_info['prices'];
        $return_variant['shippings'] =  $contries_info['shippings'];
        $return_variant['msrps'] = $contries_info['msrps'];
        $return_variant['shipping_time'] = $variants_obj->shipping_time;
        return $return_variant;
    }

    private static function getExtraImages($data){
        $extra_image = [];
        for($i=1;$i<=10;$i++){
            if(isset($data["extra_image_{$i}"]) && !empty($data["extra_image_{$i}"])){
                $extra_image[] = $data["extra_image_{$i}"];
            }
        }
        return join("|",$extra_image);
    }

	/*
   	*删除范本信息
   	*/


   	public static function delProduct($sku,$product_id,$site_id){
   		$Product_info = EnsogoProduct::find()->where(['parent_sku'=>$sku,'id'=>$product_id,'site_id'=>$site_id])->one();
   		$variance  = EnsogoVariance::deleteAll('parent_sku = :parent_sku and product_id=:product_id',[':parent_sku'=>$sku,':product_id'=>$product_id]);
        $variance_countries = EnsogoVarianceCountries::deleteAll('product_id=:product_id',[':product_id'=>$product_id]);
   		$Product_info->delete();
   		$data = EnsogoProduct::findOne($sku);
   		if(!isset($data)){
   			$data['success'] = true;
   		}else{
   			$data['success'] = false;
   			$data['message'] = '删除范本信息失败';
   		}
   		return $data;
   	}

    public static function addEnsogoTagsQueue($puid){
        $ensogo_obj = new EnsogoWishTagQueue();
        $ensogo_obj->puid = $puid;
        $ensogo_obj->platform = 'wish';
        $ensogo_obj->create_time = time();
        $ensogo_obj->update_time = time();
        $ensogo_obj->save(false);
    }

    // public static function 
    //获取ensogo分类目录
    public  static function _getEnsogoCategory($store_name='',$puid=''){
    	if(empty($puid))
       		$puid = \Yii::$app->user->identity->getParentUid(); 
    	
        $where = [];
        if(isset($store_name) && !empty($store_name)){
          $where['store_name'] = $store_name;
          $where['uid'] = $puid;
          $site_info = SaasEnsogoUser::find()->where($where)->asArray()->one();
        }
        if(isset($site_info)){
          EnsogoProxyHelper::$token = $site_info['token'];
          $result = EnsogoProxyHelper::call('getCategoriesList',[]);
        }else{
          $categoriesJsonStrObj = EnsogoCategories::findOne(['site'=>'all']);
          $result['data'] = json_decode($categoriesJsonStrObj->categories , true);
        }
        $title = array(
                'name_zh_tw'=>'分类', 
                'name'=>'Categories',
                'id'=>'分类代码',
            );
        $list = []; 
        foreach($result['data'] as &$v){
            $list['data_array'][$v['id']]['name_zh_tw'] = $v['name_zh_tw']; 
            $list['data_array'][$v['id']]['name'] = $v['name'];
            $list['data_array'][$v['id']]['id'] = $v['id'];
            $list['data_array'][$v['id']]['parent_id'] = $v['parent_id'];
            if(!empty($list['data_array'][$v['id']]['parent_id'])){
                if(isset($list['data_array'][$v['parent_id']])){
                  $list['data_array'][$v['id']]['name_zh_tw'] = $list['data_array'][$v['parent_id']]['name_zh_tw'].' > '.$v['name_zh_tw'];
                  $list['data_array'][$v['id']]['name'] = $list['data_array'][$v['parent_id']]['name'].' > '.$v['name'];
                }else{
                  unset($list['data_array'][$v['id']]);
                }
            }
        }
        foreach($list['data_array'] as $lk => $lt){
            if(!empty($lt['parent_id'])){
                unset($list['data_array'][$lt['parent_id']]);
                unset($list['data_array'][$lt['id']]['parent_id']);
            }
        }
        $list['filed_array'] = $title;
        return $list;
        // \eagle\modules\util\helpers\ExcelHelper::justExportToExcel($list,$excel_file_name);
        // return $this->render('test',[
        //     'title'=> $title,
        //     'list' => $result['data']
        // ]);

    }


    public static function _TestGetCategory(){
        $site_id = 18;
        $where['uid'] = 1;
        $where['site_id'] = $site_id;
        $Product = new Product($site_id);
        $category = $Product->refreshAllCategories();
        $categoriesJsonStrObj = EnsogoCategories::findOne(['site'=>'all']);
        $result = json_decode($categoriesJsonStrObj->categories , true);
        $title = array(
                'name_zh_tw'=>'分类', 
                'name'=>'Categories',
                'id'=>'分类代码',
            );
        $list = []; 
        foreach($result as &$v){
            $list['data_array'][$v['id']]['name_zh_tw'] = $v['name_zh_tw']; 
            $list['data_array'][$v['id']]['name'] = $v['name'];
            $list['data_array'][$v['id']]['id'] = $v['id'];
            $list['data_array'][$v['id']]['parent_id'] = $v['parent_id'];
            if(!empty($list['data_array'][$v['id']]['parent_id'])){
                if(isset($list['data_array'][$v['parent_id']])){
                  $list['data_array'][$v['id']]['name_zh_tw'] = $list['data_array'][$v['parent_id']]['name_zh_tw'].' > '.$v['name_zh_tw'];
                  $list['data_array'][$v['id']]['name'] = $list['data_array'][$v['parent_id']]['name'].' > '.$v['name'];
                }else{
                  unset($list['data_array'][$v['id']]);
                }
            }
        }
        foreach($list['data_array'] as $lk => $lt){
            if(!empty($lt['parent_id'])){
                unset($list['data_array'][$lt['parent_id']]);
                unset($list['data_array'][$lt['id']]['parent_id']);
            }
        }
        $list['filed_array'] = $title;
        return $list;
    }

    public static function _getCountries(){
        return 'hk|th|id|ph|sg|my';
    }

    public static function _getDataByCountries($info){
        for($i=0;$i<6;$i++){
            $data[] = $info;
        }
        return join('|',$data);
    }


    /**
     * 获取多站点数据
     * @param $variant
     * @return mixed
     */
    public static function setCountriesInfo($variant){
         // 开通多站点之后需要开启的代码段  -- hqf 4/5
        $contries = $prices = $shippings = $msrp = [];
        $variant_countries = EnsogoVarianceCountries::find()->where(["variance_id" => $variant->id])->asArray()->all();
        foreach($variant_countries as $c_data){
            
            $contries[] = $c_data['country_code'];
            $prices[] = $c_data['price'];
            $shippings[] = $c_data['shipping'];
            $msrp[] = $c_data['msrp'];
        }
        $addinfo['countries'] = join('|',$contries);
        $addinfo['prices'] = join('|',$prices);
        $addinfo['msrps'] = join('|',$msrp);
        $addinfo['shippings'] =  join('|',$shippings);
        return $addinfo;
    }

    /**
     * 队列同步商品 (run in console)
     * @return [type] [description]
    */ 
    public static function manualSyncCallback($queue){
        $site_id = $queue->site_id;
        // 抓取平台上的商品
        $user = SaasEnsogoUser::find()->where([
            'site_id'=>$site_id
        ])->one();
        if(true){
            $product = new Product($site_id);
            $allProducts = $product->getProductsFromPlatform();
            // 保存
            $result = $product->saveAllProducts($allProducts,function($result,$product)use($queue){
                // 进度增加
                $queue->addProgress();
                $sku = $product['parent_sku'];
                echo PHP_EOL.$sku.'success';
            },$site_id);
            // 修改last_time
            $user->last_product_success_retrieve_time = date("Y-m-d H:i:s");
            $user->save();
            $sql = $user->attributes();
            error_log(var_export($sql,true),3,"/tmp/ensogo_sync.log");
            return true;
        }else{
            return false;
        }
    }

    public static function GetOriginalImage($img){
        $search ="/?imageView2/1/w/210/h/210/";
        return str_replace($search,'', $img);
    }
    /**
     * 自动同步任务用
     * @return [type] [description]
     */
    public static function manualSyncGetAccounts(){
        $accounts = [];
        // 查询所有绑定的wish店铺
        $users = SaasEnsogoUser::find()->where([
            'is_active'=>1
        ])->all();
        foreach($users as $user){
            $accounts[] = $user->site_id;
        }
        return $accounts;
    }


    // 提供查询用
    public static function getProductsFromPlatform($account,$parent_sku){
        try{
            // 先根据account查询用户Uid
            if($user = \eagle\models\UserBase::find()->where([
                'user_name'=>$account
            ])->one()){
                
                if(true){
                    // 获取商品信息
                    if(!$product = EnsogoProduct::find()->where([
                        'parent_sku'=>$parent_sku
                    ])->one()){
                        throw new \Exception("product not found", 404);
                    }
                    $token = SaasEnsogoUser::find()->where([
                        'site_id'=>$product->site_id
                    ])->one()->token;
                    $result = EnsogoProxyHelper::call('getProductById',[
                        'product_id' => $product->ensogo_product_id,
                        'site_id' => $product->site_id,
                        'access_token'=> $token
                    ]);
                    return $result;
                }
            }
        }catch(\Exception $e){
            return [
                'code'=>$e->getCode(),
                'message'=>$e->getMessage()
            ];
        }
    }
    

    public static function GetOnePlatformUserName(){
        // Ebay用户
        $ebayUserArr= Yii::$app->get('db')->createCommand(" SELECT a.*,b.user_name ".
            " FROM saas_ebay_user a left join user_base b on b.uid=a.uid where 1=1 order by a.uid ")->queryAll();
        // 亚马逊用户
        $amazonUserArr= Yii::$app->get('db')->createCommand(" SELECT a.*,b.user_name ".
                " FROM saas_amazon_user a left join user_base b on b.uid=a.uid where 1=1 order by a.uid")->queryAll();
        // ali用户
        $aliexperssUserArr= Yii::$app->get('db')->createCommand(" SELECT a.*,b.user_name ".
                " FROM saas_aliexpress_user a left join user_base b on b.uid=a.uid where 1=1 order by a.uid")->queryAll();
        foreach($aliexperssUserArr as $ali => $ali_user){
            foreach($amazonUserArr as $ama => $ama_user){
                if($ali_user['uid'] == $ama_user['uid']){
                    unset($aliexperssUserArr[$ali]);
                }
            }
            foreach($ebayUserArr as $ebay => $eba_user){
                if($eba_user['uid'] == $ali_user['uid']){
                    unset($aliexperssUserArr[$ali]);
                }
            }
        }
        $result = [];
        $RepList = [];
        foreach($aliexperssUserArr as $k => $user){
            if(!in_array($user['user_name'],$RepList)){
                $result[$k]['user_name'] = $user['user_name'];
                $result[$k]['uid'] = $user['uid'];
                array_push($RepList,$user['user_name']);
            }
        
        }

        $excel_file_name = 'aliexpressUserName.xlsx';
        \eagle\modules\util\helpers\ExcelHelper::exportToExcel($result, ['user_name'=>'aliexpress账户名','uid'=>'用户uid'] , $excel_file_name );

    }

    /**
     * ensogo 批量发布队列处理函数
     * @author hqf
     * @version 2016-05-06
     * @return [type] [description]
     */
    public static function ensogoPushSyncCallback($queue){
        $site_id = $queue->data('site_id');
        // 切换数据库
        $user = SaasEnsogoUser::find()->where([
                'site_id' => $site_id
            ])->one();
        echo 'site_id :'.$site_id.';'.PHP_EOL;
        if(true){
            foreach($queue->data('products') as $product_id){
                if($product = EnsogoProduct::find()->where([
                        'id'=>$product_id
                    ])->one()){
                    try{
                        $result = $product->push();
                        $error[$product->parent_sku] = $e->getMessage();
                    }catch(\Exception $e){
                        $product->error_message = $e->getMessage();
                        $product->save();
                        $error[$product->parent_sku] = $e->getMessage();
                    }
                }else{
                    echo 'no product:'.$product_id.PHP_EOL;
                }
            }
            if(count($error)){
                $queue->data(['error'=>$error]);
            }
            return true;
        }else{
            return false;
        }

        // $products_id = explode(',',$queue->date('products'));
        // foreach($products_id as $fanben_id){
            // $product = WishFanben::findOne($fanben_id);
    }


    /**
     * 批量将商品添加到发布队列
     * @author huaqingfeng
     * @version  2016-04-08
     * @param integer $site_id
     * @param Array $products 商品fanben_id集合
     */
    public static function addProductsPushQueue($site_id,Array $products = []){
        $type = 'ensogo:push';
        $queue = Queue::add($type,$site_id.'-'.join('_',$products),[
            'products'=>$products,
            'site_id'=>$site_id
        ]);
        return $queue;
    }

    public static function  GetBatchEditData(){
        $data = [];
        $all_site = array_keys(params::$ensogo_sites);
        $products = \Yii::$app->request->post('product');
        foreach($products as $product){
            $EnsogoProduct = EnsogoProduct::find()->where(['id'=>$product])->one();
            $data[$product]['name'] = $EnsogoProduct['name'];
            $data[$product]['product_id'] = $EnsogoProduct['id'];
            $data[$product]['parent_sku'] = $EnsogoProduct['parent_sku'];
            $data[$product]['main_image'] = $EnsogoProduct['main_image'];
            if(!empty($EnsogoProduct)){
                $variances = \Yii::$app->request->post('variance_'.$product);
                foreach($variances as $variance){
                    $EnsogoVariance = EnsogoVariance::find()->where(['product_id'=>$product,'sku'=>$variance])->one();
                    if(!empty($EnsogoVariance)){
                        $EnsogoVarianceCountries = EnsogoVarianceCountries::find()->where(['product_id'=>$product,'variance_id'=>$EnsogoVariance['id']])->all();
                        $Sites = [];
                        if(!empty($EnsogoVarianceCountries)){
                            foreach($EnsogoVarianceCountries as $key => $country){
                                $i = array_search($country['country_code'],$all_site);
                                $Sites[$i]['price'] = $country['price'];
                                $Sites[$i]['shipping'] = $country['shipping'];
                                $Sites[$i]['msrp'] = $country['msrp'];
                                $Sites[$i]['country_code'] = $country['country_code'];
                            }
                            ksort($Sites);
                            if(!isset($data[$product]['variance'])){
                                $data[$product]['variance'] = [] ;
                            }
                            array_push($data[$product]['variance'],[
                                'sku'=> $EnsogoVariance['sku'],
                                'shipping_time'=> $EnsogoVariance['shipping_time'],
                                'inventory'=> $EnsogoVariance['inventory'],
                                'color' => $EnsogoVariance['color'],
                                'size' => $EnsogoVariance['size'],
                                'price' => $EnsogoVariance['price'],
                                'sites' => $Sites                           
                            ]);
                        }
                    }
                }
            }
        }
        return $data;
    }

}
