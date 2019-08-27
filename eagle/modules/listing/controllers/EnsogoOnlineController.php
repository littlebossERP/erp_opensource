<?php

/**
 *  ensogo （小老板系统新增ensogo商品）刊登管理
 */
namespace eagle\modules\listing\controllers;

use yii;
use yii\data\Pagination;
use yii\data\ActiveDataProvider;
use eagle\components\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use eagle\modules\listing\service\ensogo\Product;
use eagle\models\SaasEnsogoUser;
use eagle\modules\listing\helpers\EnsogoProxyHelper;
use eagle\modules\listing\helpers\EnsogoHelper;
use eagle\modules\listing\models\EnsogoProduct;
use eagle\modules\listing\models\EnsogoVariance;
use eagle\modules\listing\models\EnsogoVarianceCountries;
use eagle\modules\app\apihelpers\AppTrackerApiHelper;
use eagle\modules\listing\service\Queue;
use eagle\modules\listing\service\Attributes;
use eagle\modules\listing\config\params;
use eagle\models\QueueProductLog;

class EnsogoOnlineController extends \eagle\components\Controller
{
    public $enableCsrfValidation = FALSE;

    protected $CatalogModulesAppKey = 'catalog';

    public function behaviors()
    {
        return [
            'access' => [
                'class' => \yii\filters\AccessControl::className(),
                'rules' => [
                    [
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
            'verbs'  => [
                'class'   => VerbFilter::className(),
                'actions' => [
                    'delete' => ['post'],
                ],
            ],
        ];
    }


    public function actionIndex(){
        //crm
        $params = [];
        $str = __CLASS__ . __FUNCTION__.var_export($params,true);
    }


    public function actionEnabledInfo(){
        $type = \Yii::$app->request->post('type');//product或者variants
        $result = [];
        switch($type){
            case 'product':
                $parent_sku = \Yii::$app->request->post('parent_sku');
                $product_obj = EnsogoProduct::find()->where(["parent_sku" => $parent_sku])->one();
                EnsogoProxyHelper::$token = EnsogoProxyHelper::getTokenByProduct($product_obj);
                if($product_obj !== false && $product_obj->ensogo_product_id != ''){
                    $return = EnsogoProxyHelper::call("enableProduct",["product_id" => $product_obj->ensogo_product_id]);
                    if($return['code'] == 0){
                        $product_obj->is_enable = 1;
                        $product_obj->save(false);
                        //上架所有变种商品
                        EnsogoVariance::updateAll(['enable'=>"Y"],['product_id' => $product_obj->id]);
                        $result = ["success"=>true,"message"=>"上架商品成功"];
                    } else {
                        $message = !empty($return["message"]) ? $return["message"] : "上架商品失败";
                        $result = ['success'=>false,"message"=>$message];
                    }
                } else {
                    $result = ['success'=>false,"message"=>"商品信息不存在"];
                }
                break;
            case 'variants':
                $variants_id = \Yii::$app->request->post('pvid');
                $parent_sku = \Yii::$app->request->post('parent_sku');
                $variant_obj = EnsogoVariance::find()->where(["parent_sku" => $parent_sku]);
                $variance = $variant_obj->one();
                EnsogoProxyHelper::$token = EnsogoProxyHelper::getTokenByVariance($variance);
                $return = EnsogoProxyHelper::call("enableProductVariants",["product_variants_id" => $variants_id]);
                if($return['code'] == 0){
                    if($variant_obj->count() > 0){
                        EnsogoProduct::updateAll(['is_enable' => 2],["parent_sku"=>$parent_sku]);
                    } else {
                        EnsogoProduct::updateAll(['is_enable' => 1],["parent_sku"=>$parent_sku]);
                    }
                    $variance->enable = 'Y';
                    $variance->save();
                    $result = ["success"=>true,"message"=>"上架变种商品成功"];
                } else {
                    $message = !empty($return["message"]) ? $return["message"] : "上架变种商品失败";
                    $result = ['success'=>false,"message"=>$message];
                }
                break;
            default :
                $result = ['success'=>false,"message"=>"商品信息不存在"];
                break;
        }
        AppTrackerApiHelper::actionLog("listing_ensogo", "/listing/ensogo-online/enable-info"); 
        return json_encode($result);
    }

    /**
     * @param $_POST['type','parent_sku','pvid']
     * @return [type] [description]
     */
    public function actionDisableInfo(){
        $type = \Yii::$app->request->post('type');//product或者variants
        $result = [];
        switch($type){
            case 'product':
                $parent_sku = \Yii::$app->request->post('parent_sku');
                $product_obj = EnsogoProduct::find()->where(["parent_sku" => $parent_sku])->one();
                EnsogoProxyHelper::$token = EnsogoProxyHelper::getTokenByProduct($product_obj);
                if($product_obj !== false && $product_obj->ensogo_product_id != ''){
                    $return = EnsogoProxyHelper::call("disableProduct",["product_id" => $product_obj->ensogo_product_id]);
                    if($return['code'] == 0){
                        $product_obj->is_enable = 3;
                        $product_obj->save(false);
                        //上架所有变种商品
                        EnsogoVariance::updateAll(['enable'=>"N"],['product_id' => $product_obj->id]);
                        $result = ["success"=>true,"message"=>"下架商品成功"];
                    } else {
                        $message = !empty($return["message"]) ? $return["message"] : "下架商品失败";
                        $result = ['success'=>false,"message"=>$message];
                    }
                } else {
                    $result = ['success'=>false,"message"=>"商品信息不存在"];
                }
                break;
            case 'variants':
                $variants_id = \Yii::$app->request->post('pvid');
                $parent_sku = \Yii::$app->request->post('parent_sku');
                $variant_obj = EnsogoVariance::find()->where(["parent_sku" => $parent_sku]);
                $variance = $variant_obj->one();
                EnsogoProxyHelper::$token = EnsogoProxyHelper::getTokenByVariance($variance);
                $return = EnsogoProxyHelper::call("disableProductVariants",["product_variants_id" => $variants_id]);
                if($return['code'] == 0){
                    $is_enable = self::getIsEnable($variant_obj);
                    EnsogoProduct::updateAll(['is_enable' => $is_enable],["parent_sku"=>$parent_sku]);
                    $variance->enable='N';
                    $variance->save();
                    $result = ["success"=>true,"message"=>"下架变种商品成功"];
                } else {
                    $message = !empty($return["message"]) ? $return["message"] : "下架变种商品失败";
                    $result = ['success'=>false,"message"=>$message];
                }
                break;
            default :
                $result = ['success'=>false,"message"=>"商品信息不存在"];
                break;
        }
        AppTrackerApiHelper::actionLog("listing_ensogo", "/listing/ensogo-online/disable-info");
        return json_encode($result);
    }

    public function actionEdit(){
        $parent_sku = Yii::$app->request->get('parent_sku');
        $site_id  = Yii::$app->request->get('site_id');
        $type = Yii::$app->request->get('menu_type');
        $puid =\Yii::$app->subdb->getCurrentPuid();
        $site_info = SaasEnsogoUser::find()->where(['uid'=>$puid])->asArray()->all();
        $model = EnsogoProduct::find()->where([
            'parent_sku'=>$parent_sku,
            'site_id'=>$site_id
        ])->asArray()->one();
        $Product = new Product($model['site_id']);
        $category = $Product->getAllParentCategories($model['category_id']);
        $category_info = '';
        foreach($category as $k => $v){
            $category_info .= $v['name'] .' > ';
        }
        $model['category_info'] = trim($category_info,' > ');
        $tagList = explode(',',$model['tags']);
        $model['tagList'] = $tagList;
        $where['parent_sku'] = $parent_sku;
        $where['product_id'] = $model['id'];
        // $where['enable'] = $type == 'online' ? 'Y':'N';
        $varianceData = EnsogoVariance::find()->where($where)->orderBy('id desc')->asArray()->all();
        foreach($varianceData as $key => $variance){
            $varianceData[$key]['sites'] = EnsogoVarianceCountries::find()->where(['variance_id'=>$variance['id'],'product_id'=>$model['id']])->asArray()->all();
        }
        $sel_sites = [];
        $firstVariance = $varianceData[0];
        foreach($firstVariance['sites'] as $k_s => $site){
            array_push($sel_sites,$site['country_code']);
        }
        $shipping_time = explode('-',$firstVariance['shipping_time']);
        $model['shipping_short_time'] = $shipping_time[0];
        $model['shipping_long_time'] = $shipping_time[1];  
        $model['price'] = $firstVariance['price'];
        $model['msrp'] = isset($firstVariance['msrp'])?'':$firstVariance['msrp'];
        $model['inventory'] = $firstVariance['inventory'];
        $model['shipping'] = $firstVariance['shipping'];
        $items = json_encode(params::$kindeditor_items);
        $active = ($type == 'online')?'在线商品':'下架商品';
        $app_path = ($type == 'online')?'/listing/ensogo-online/online-edit':'/listing/ensogo-online/offline-edit';
        AppTrackerApiHelper::actionLog("listing_ensogo", $app_path);
        return $this->renderAuto('edit',[
            'menu'=>$this->getMenu(),
            'EnsogoModel' => $model,
            'active' =>  $active,
            'EnsogoVariance'=> $varianceData,
            'sites' => params::$ensogo_sites,
            'sel_sites'=> $sel_sites,
            'items'=> $items,
            'store' => $site_info,
            'isOnline'=> ($type="online") ? true: false
        ]);
    } 

    public function actionSaveProduct(){
        $product_info = $_POST;
        $site_id = Yii::$app->request->get('site_id');
        $model = EnsogoProduct::find()->where(['parent_sku'=>$product_info['parent_sku']])->one();
        $product_info['category_id'] = $model['category_id'];
        $Product = new Product($site_id);
        $data = $Product->save($product_info);
        if($data['success'] == true){
            $result = $Product->push($data['product']);
            $data['message'] = '';
            if($result['product']['code'] != 0 ){
                $data['success'] = false;
                $data['message'] .=  '商品刊登失败:'.join(',',$result['product']['message']).';'; 
            } 
            $message = '';
            $is_failed = 0;
            if(isset($result['variants'])){
                foreach($result['variants'] as $k => $v){
                    if($v['code'] != 0){
                        $is_failed = 1;
                        $data['success'] = false;
                        $message .= $v['message'].';';
                    }
                }
            }
            if($is_failed == 1){
                $data['message'] .='变种商品刊登失败:'.$message.';';
            }
        }
        AppTrackerApiHelper::actionLog("listing_ensogo", "/listing/ensogo-online/save-product");
        return $this->renderJson($data);
    }

    /**
     * 获取当前同步队列进度，如果已经完成则从数据库返回总数
     * @param  $_GET['queue_id']
     * @return [type] [description]
     */
    public function actionSyncProductProgress(){
        $queue_id = $_GET['queue_id'];
        $queue = new Queue('ensogo');
        $progress = $queue->getProgress($queue_id);
        if($progress === NULL){
            $progress = QueueProductLog::find()->where([
                'id'=>$queue_id
            ])->one()->total_product;
            $status = 'C';
        }else{
            $status = 'S';
        }
        return $this->renderJson([
            'progress'=>$progress,
            'status'=>$status
        ]);
    }

    public function actionOfflineProductList(){
        AppTrackerApiHelper::actionLog("listing_ensogo", "/listing/offline-ensogo/offline-product-list");

        $puid =\Yii::$app->subdb->getCurrentPuid();;
        // 店铺列表
        $accounts = SaasEnsogoUser::find()->where([
            'uid'=>$puid
        ])->all();

        // 设置查询条件
        $request = $this->getSearchValue([
            'site_id'       => $accounts[0]->site_id,
            'search_type'   => '',
            'search_value'  => '',
            'lb_status'     => [
                Product::LB_STATUS_ENSOGO_PENDING,
                Product::LB_STATUS_ENSOGO_SUCCESS,
                Product::LB_STATUS_ENSOGO_FAIL
            ]
        ],$_GET);

        $lb_status = is_array($request['lb_status']) ? $request['lb_status'] : [$request['lb_status']];

        $products = EnsogoProduct::find()->where([
            'type'          => 1,
            'site_id'       => $request['site_id'],
            'lb_status'     => $lb_status,
            'is_enable'     => [2,3]
        ]);
        if($request['search_type']){
            $products->andWhere([
                'LIKE',$request['search_type'],$request['search_value']
            ]);
        }

        $Product = new Product($request['site_id']);

        $page = new Pagination([
            'totalCount' => $products->count(), 
            'defaultPageSize' => 20,
            'pageSizeLimit'=>[5,10,20,50],

        ]);

        $products
            ->offset($page->offset)
            ->limit($page->limit)->orderBy('create_time desc');

        $data = [
            'menu'      => $this->getMenu(),
            'active'    => '下架商品',
            'accounts'  => $accounts,
            'products'  => $Product->getProductAndVariance($products->all(),'N'),
            'page'      => $page,
            'site_id'   => $request['site_id'],
            'request'   => new Attributes($_REQUEST),
            'menu_type'      => 'offline'
        ];
        return $this->renderAuto('offline_product_list',$data);
    }

    public function actionOnlineProductList(){
        AppTrackerApiHelper::actionLog("listing_ensogo", "/listing/offline-ensogo/online-product-list");

        $puid =\Yii::$app->subdb->getCurrentPuid();
        // 店铺列表
        $accounts = SaasEnsogoUser::find()->where([
            'uid'=>$puid
        ])->all();

        //get 过来的 site_id
        $site_id = \Yii::$app->request->get('site_id',0);
        // 设置查询条件
        $request = $this->getSearchValue([
            'site_id'       => $site_id ? $site_id :$accounts[0]->site_id,
            'search_type'   => '',
            'search_value'  => '',
            'lb_status'     => [
                Product::LB_STATUS_ENSOGO_PENDING,
                Product::LB_STATUS_ENSOGO_SUCCESS,
                Product::LB_STATUS_ENSOGO_FAIL
            ]
        ],$_GET);

        $lb_status = is_array($request['lb_status']) ? $request['lb_status'] : [$request['lb_status']];

        $products = EnsogoProduct::find()->where([
            'type'          => 1,
            'site_id'       => $request['site_id'],
            'lb_status'     => $lb_status,
            'is_enable'     => [1,2]
        ]);
        if($request['search_type']){
            $products->andWhere([
                'LIKE',$request['search_type'],$request['search_value']
            ]);
        }

        $Product = new Product($request['site_id']);

        $page = new Pagination([
            'totalCount' => $products->count(), 
            'defaultPageSize' => 20,
            'pageSizeLimit'=>[5,10,20,50]
        ]);

        $products
            ->offset($page->offset)
            ->limit($page->limit)->orderBy('create_time desc');
        $data = [
            'menu'      => $this->getMenu(),
            'active'    => '在线商品',
            'accounts'  => $accounts,
            'products'  => $Product->getProductAndVariance($products->all(),'Y'),
            'page'      => $page,
            'site_id'   => $request['site_id'],
            'request'   => new Attributes($_REQUEST),
            'menu_type'      => 'online'
        ];
        return $this->renderAuto('online_product_list',$data);
    }
    /*
    * 批量修改
    *@author victorting
    */
    public function actionBatchEdit(){
        $menu_type = \Yii::$app->request->post('menu_type');
        $site_id = \Yii::$app->request->get('site_id');
        $data = EnsogoHelper::GetBatchEditData();
        return $this->render('batch-edit',[
            'data'=>$data,
            'site_id'=>$site_id,
            'menu'=>self::getMenu(),
            'menu_type' => $menu_type,
            'active'=> $menu_type == "online" ? '在线商品':'下架商品'
        ]);
    }

    /*
    * 批量修改保存
    *@author victorting
    */
    public function actionBatchEditSave(){
        $products = \Yii::$app->request->post('product');
        $site_id = \Yii::$app->request->get('site_id'); 
        $message = '';
        $queue_data = []; 
        $Product = new Product($site_id);
        foreach($products as $product){
            array_push($queue_data,$product['product_id']);
            foreach($product['variance'] as $variance){
                $EnsogoVariance = EnsogoVariance::find()->where(['product_id'=>$product['product_id'],'sku'=>$variance['sku']])->one();
                $EnsogoVariance->shipping_time = $variance['shipping_time'];
                $EnsogoVariance->inventory = $variance['inventory'];
                if($EnsogoVariance->save()){
                    $message .= '变种SKU:'.$variance['sku'].' 保存失败;';
                }
                foreach($variance['sites'] as $site){
                    $EnsogoVarianceCountries = EnsogoVarianceCountries::find()->where(['product_id'=>$product['product_id'],'variance_id'=>$EnsogoVariance['id'],'country_code'=>$site['country_code']])->one();
                    $EnsogoVarianceCountries->price = $site['price'];
                    $EnsogoVarianceCountries->msrp = $site['msrp'];
                    $EnsogoVarianceCountries->shipping = $site['shipping'];
                    if($EnsogoVarianceCountries->save()){
                        $message .= '变种SKU:'.$variance['sku'].' site:'.$site['country_code'].'保存失败;';
                    }
                }
            }
            $EnsogoProduct = EnsogoProduct::find()->where(['id'=>$product['product_id'],'parent_sku'=>$product['parent_sku']])->one();

        }
        EnsogoHelper::addProductsPushQueue($site_id,array_values(array_unique($queue_data,SORT_NUMERIC)));
        $data['success'] = true;
        $data['message'] = '修改成功';
        return $this->renderJson($data); 
    }

    /**
     * 手动同步商品
     * @return [type] [description]
     */
    function actionSyncProductReady(){
        $puid =\Yii::$app->subdb->getCurrentPuid();;
        // 店铺列表
        $accounts = SaasEnsogoUser::find()->where([
            'uid'=>$puid
        ])->all();
        AppTrackerApiHelper::actionLog("listing_ensogo", "/listing/online-ensogo/sysnc-ensogo");
        return $this->renderAuto('start-sync',[
            'accounts'=>$accounts
        ]);
    }

    /**
     * 同步执行，POST请求
     * @return [type] [description]
     */
    function actionSyncProduct(){
        $queue = new Queue('ensogo');
        $result = $queue->addProductQueueBySiteId($_POST['site_id']);
        return $this->renderJson($result);
    }


    protected function getSearchValue(Array $array = [],$REQUEST = []){
        $arr = [];
        foreach($array as $key => $val){
            $arr[$key] = (isset($REQUEST[$key]) && $REQUEST[$key]) ? $REQUEST[$key] : $val;
        }
        return $arr;
    }

    protected function getMenu(){
        return [
            '刊登管理'=>[
                'icon'=>'icon-shezhi',
                'items'=>[
                     'Wish搬家 <font style="color: red">测试版</font>' => [
                        'url' => '/listing/ensogo-offline/store-move?platform=wish',
                    ],
                   	'速卖通搬家 <font style="color: red">测试版</font>'=>[
                      	'url'=>'/listing/ensogo-offline/store-move-by-file',
                  	],
                    '待发布'=>[
                        'url'=>'/listing/ensogo-offline/ensogo-post',
                    ],
                    '刊登失败'=>[
                        'url'=>'/listing/ensogo-offline/product-failed-post',
                    ],
                ]
            ],
            '商品列表'=>[
                'icon'=>'icon-pingtairizhi',
                'items'=>[
                    '在线商品'=>[
                        'url'=>'/listing/ensogo-online/online-product-list',
                    ],
                    '下架商品'=>[
                        'url'=>'/listing/ensogo-online/offline-product-list',
                    ],
                ]
            ],
        ];
    }



    private function getIsEnable($variant_obj){
        $variant_info = $variant_obj->asArray()->all();
        $total = count($variant_info);
        $number = 0;
        foreach($variant_info as $variant){
            //下架状态
            if(strtoupper($variant['enable']) == 'N'){
                $number += 1;
            }
        }
        return $total == $number ? 3 : 2;
    }


}