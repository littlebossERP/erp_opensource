<?php

/**
 *  ensogo （小老板系统新增ensogo商品）刊登管理
 */
namespace eagle\modules\listing\controllers;

use eagle\models\EnsogoStoreMoveLog;
use eagle\modules\listing\helpers\EnsogoStoreMoveHelper;
use eagle\modules\listing\models\WishFanben;
use eagle\modules\listing\models\WishFanbenVariance;
use yii;
use yii\data\Pagination;
use yii\data\ActiveDataProvider;
use eagle\components\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\data\Sort;
use eagle\modules\listing\helpers\WishHelper;
use eagle\modules\listing\service\ensogo\Product;
use eagle\modules\listing\service\ensogo\Account;
use eagle\models\SaasEnsogoUser;
use eagle\modules\listing\helpers\EnsogoProxyHelper;
use eagle\modules\listing\helpers\EnsogoHelper;
use eagle\modules\listing\models\EnsogoProduct;
use eagle\modules\listing\models\EnsogoVariance;
use eagle\modules\listing\models\EnsogoVarianceCountries;
use eagle\models\EnsogoWishTagLog;
use console\helpers\EnsogoQueueHelper;
use eagle\modules\platform\apihelpers\WishAccountsApiHelper;
use eagle\modules\listing\config\params;
use console\helpers\AliexpressHelper;
use eagle\models\SaasAliexpressUser;
use eagle\models\listing\AliexpressListing;
use eagle\modules\util\models\UserBackgroundJobControll;
use eagle\modules\util\helpers\ResultHelper;
use eagle\modules\util\helpers\ExcelHelper;
use Qiniu\json_decode;
use eagle\models\ImportEnsogoListing;
use eagle\modules\listing\models\EnsogoCategories;
use eagle\modules\app\apihelpers\AppTrackerApiHelper;

class EnsogoOfflineController extends \eagle\components\Controller
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

    public function actionAdd(){
        $puid =  \Yii::$app->subdb->getCurrentPuid();
        $site_info = SaasEnsogoUser::find()->where(['uid'=>$puid])->asArray()->all();
        $lb_status= 1;
        $items = json_encode(params::$kindeditor_items);
        AppTrackerApiHelper::actionLog("listing_ensogo", "/listing/ensogo-offline/add");
       echo $this->render('add',[
            'store' => $site_info,
            'lb_status'=>$lb_status,
            'menu' =>$this->getMenu(),
            'items'=> $items,
            'sites' => params::$ensogo_sites,
            'active' => '待发布' 
        ]);
    }
   
    public function actionEdit(){
        $parent_sku = Yii::$app->request->get('parent_sku');
        $product_id = Yii::$app->request->get('product_id');
        $site_id = Yii::$app->request->get('site_id');
        $puid =\Yii::$app->subdb->getCurrentPuid();
        $lb_status = Yii::$app->request->get('lb_status');
        $site_info = SaasEnsogoUser::find()->where(['uid'=>$puid])->asArray()->all();
        $model = EnsogoProduct::find()->where(['parent_sku'=>$parent_sku,'id'=>$product_id,'site_id'=>$site_id])->asArray()->one();
        // $model['category_info'] = json_decode($model['json_info'])->category_info;
        $Product = new Product($model['site_id']);

        $category = $Product->getAllParentCategories($model['category_id']);
        $category_info = '';
        if(!empty($category)){
            foreach($category as $k => $v){
                $category_info .= $v['name_zh_tw'] .' > ';
            }
        }
        $model['category_info'] = trim($category_info,' > ');
        $tagList = explode(',',$model['tags']);
        $model['tagList'] = $tagList;
       
        $varianceData = EnsogoVariance::find()->where(['parent_sku'=>$parent_sku,'product_id'=>$model['id'] ])->asArray()->all();
        $all_sites = array_keys(params::$ensogo_sites);
        foreach($varianceData as $key => $variance){
            $varianceData[$key]['sites'] = EnsogoVarianceCountries::find()->where(['variance_id'=>$variance['id']])->asArray()->all();
            if(empty($varianceData[$key]['sites'])){
                foreach($all_sites as $all_key => $all_site){
                    $varianceData[$key]['sites'][$all_key]['variance_id'] = $varianceData[$key]['id'];
                    $varianceData[$key]['sites'][$all_key]['product_id'] = $varianceData[$key]['product_id'];
                    $varianceData[$key]['sites'][$all_key]['country_code'] = $all_site; 
                    $varianceData[$key]['sites'][$all_key]['price'] = $varianceData[$key]['price'];
                    $varianceData[$key]['sites'][$all_key]['shipping'] = $varianceData[$key]['shipping'];
                    $varianceData[$key]['sites'][$all_key]['msrp'] = $varianceData[$key]['msrp']? '0.00' : $varianceData[$key]['msrp'];
                }                
            }
        }

        $sel_sites = [];
        foreach($varianceData[0]['sites'] as $k_s => $site){
            array_push($sel_sites,$site['country_code']);     
        }
        $shipping_time = explode('-',$varianceData[0]['shipping_time']);
        $model['shipping_short_time'] = $shipping_time[0];
        $model['shipping_long_time'] = $shipping_time[1];  
        $model['price'] = $varianceData[0]['price'];
        $model['msrp'] = isset($varianceData[0]['msrp']) ? '' : $varianceData[0]['msrp'];
        $model['inventory'] = $varianceData[0]['inventory'];
        $model['shipping'] = $varianceData[0]['shipping'];
        $active = $lb_status == 1 ? '待发布':'刊登失败';
        $app_path = $lb_status == 1 ? '/listing/ensogo-offline/ensogo-post-edit':'/listing/ensogo-offline/product-failed-post-edit';
        AppTrackerApiHelper::actionLog("listing_ensogo", $app_path);
        $items = json_encode(params::$kindeditor_items);
        return $this->render('edit',[
            'EnsogoModel' => $model,
            'EnsogoVariance'=> $varianceData,
            'store' => $site_info,
            'lb_status' => $lb_status,
            'menu' =>$this->getMenu(),
            'sites' => params::$ensogo_sites,
            'sel_sites' => $sel_sites,
            'items' => $items,
            'active' => $active 
        ]);
    } 

    public function actionGetParentCategoryId(){
        $ParentCategoryId = Yii::$app->request->post('parentCategoryId',-1);
        $site_id = Yii::$app->request->post('site_id',Yii::$app->request->get('site_id',-1));
        $Product = new Product($site_id);
        $CategoryList = $Product->getChildCategories($ParentCategoryId);
        return $this->renderJson($CategoryList);
    }
    public function actionPushProduct(){
        $parent_sku = Yii::$app->request->get('parent_sku');
        $site_id = Yii::$app->request->get('site_id');
        $id = Yii::$app->request->get('product_id');
        $product_Info = EnsogoProduct::find()->where(['id'=>$id,'parent_sku'=>$parent_sku])->one();
        $Product = new Product($site_id);
        $result = $Product->push($product_Info);        
        if(isset($result['error'])){
            if($result['error'] == 500){
                $product_Info->lb_status = 4;
                $product_Info->save();
                $data['success'] = false;
            }
        }
        if($result['product']['code'] != 0){
            $data['success'] = false;
            if(is_array($result['product']['message'])){
                $data['message'] = '商品刊登失败:'.join(',',$result['product']['message']).';';
            }else{
                $data['message'] = '商品刊登失败:'.$result['product']['message'];
            }
        }else{
            $data['success'] = true;
        } 
        $is_failed = 0;

        if(isset($result['variants'])){
            $data['message'] = '';
            $message = '';
            foreach($result['variants'] as $k => $v){
                if($v['code'] != 0){
                    $is_failed = 1;
                    if(is_array($v['message'])){
                        $message .= join(',',$v['message']).';';
                    }else{
                        $message .= $v['message'].';';
                    }
                }
            }
            if($is_failed == 1){
                $data['success'] = false;
                $data['message'] .='变种商品刊登失败:'.$message;
            }
        }
        AppTrackerApiHelper::actionLog("listing_ensogo", "/listing/ensogo-offline/push-product");
        return $this->renderJson($data);
    }

    /**
     * 保存商品
     * @return [type] [description]
     */
    public function actionSaveAndPostProduct(){
        $product_Info = $_POST;
        if(!isset($product_Info['variants'])){
            $product_Info['variants'] = [];
        }else if(isset($product_Info['product_id'])){
            $sites = explode('|',$product_Info['variants'][0]['countries']);
            foreach(params::$ensogo_sites as $key => $site){
                if(!in_array($key,$sites)){
                   $EnsogoVarianceCountries = EnsogoVarianceCountries::deleteAll('product_id=:product_id and country_code = :site',array(':product_id'=>$product_Info['product_id'],':site'=>$key));
                }
            }
        }
        $type = Yii::$app->request->get('type',1);
        $site_id = Yii::$app->request->get('site_id');
        $Product = new Product($site_id);
        $data = $Product->save($product_Info);
        // EnsogoQueueHelper::saveApiLog(['puid'=>$puid,'request_info'=>json_encode($product_Info),'result_info'=>json_encode($data)]);
        if($type == 2){
            if($data['success'] == true){
                $data['product_save'] = true;
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
                            if(is_array($v['message'])){
                                $message .= join(',',$v['message']).';';
                            }else{
                                $message .= $v['message'].';';
                            }
                        }
                    }
                }
                if($is_failed == 1){
                    $data['message'] .='变种商品刊登失败:'.$message.';';
                }
            }else{
                $data['product_save'] = false;
            }
        }
        if($type == 1){
            AppTrackerApiHelper::actionLog("listing_ensogo", "/listing/ensogo-offline/save-offline-product");
        }else{
            AppTrackerApiHelper::actionLog("listing_ensogo", "/listing/ensogo-offline/save-and-post-offline-product");
        }
        return $this->renderJson($data);
    }


    public function actionEnsogoPost(){
       $queryString = array();
        $queryString['type'] = Yii::$app->request->get('type',2); 
        $queryString['lb_status'] = Yii::$app->request->get('lb_status',1); //刊登管理 商品状态
        $select_status = Yii::$app->request->get('select_status');
        $search_key = Yii::$app->request->get('search_key');
        $site_id = Yii::$app->request->get('site_id');
        if(!empty($select_status)){
            if(!empty($search_key)){
                $queryString[$select_status]= $search_key;
                $data['search_key'] = $search_key;
            }
        }else{
            if(!empty($search_key)){
                $queryString['keyword'] = $search_key;
                $data['search_key'] = $search_key;
            }
        }
        
        if(!empty($site_id)){
            $queryString['site_id'] = $site_id;
            $data['site_id'] = $site_id;
            
        }   

        if (! empty ( $_GET ['sort'] )) {
            $sort = $_GET ['sort'];
            if ('-' == substr ( $sort, 0, 1 )) {
                $sort = substr ( $sort, 1 );
                $order = 'desc';
            } else {
                $order = 'asc';
            }
        }
    
        if (! isset ( $sort ))
            $sort = 'create_time';
        if (! isset ( $order ))
            $order = 'desc';
        
        if (! empty ( $_GET ['per-page'] )) {
            if (is_numeric ( $_GET ['per-page'] )) {
                $pageSize = $_GET ['per-page'];
            } else {
                $pageSize = 20;
            }
        } else {
            $pageSize = 20;
        }
        if(isset($_GET['select_status'])){
            $data['select_status'] = $_GET['select_status'];
        }
        //调用 查询方法
        $EnsogoListingData = EnsogoHelper::getProductList( $queryString, $sort, $order, $pageSize);
        
        $EnsogoListCount = EnsogoProduct::find()->where(['type'=>2,'lb_status'=>4])->asArray()->count();
        $uid = \Yii::$app->user->identity->getParentUid();  
        $store_info =  SaasEnsogoUser::find()->where(['uid'=>$uid])->asArray()->all();
        foreach($EnsogoListingData['data'] as $key => &$val){
            foreach($store_info as $k => $v){
                if($val['site_id'] == $v['site_id']){
                    $val['store_name'] = $v['store_name'];
                }
                $val['show_parent_sku'] = $val['parent_sku'];
                if(isset($data['search_key'])){
                    $replace =["<font style='color:RGB(255,153,96)'>".$data['search_key']."</font>",
                    "<font style='color:RGB(255,153,96)'>".ucfirst($data['search_key'])."</font>",
                    "<font style='color:RGB(255,153,96)'>".strtoupper($data['search_key'])."</font>",
                    ];
                    /*替换搜索匹配的关键字*/
                    $find = [$data['search_key'],ucfirst($data['search_key']),strtoupper($data['search_key'])];
                    if(isset($data['select_status']) && !empty($data['select_status'])){
                        if($data['select_status'] == 'name'){
                            $val['name'] = str_replace($find,$replace,$val['name']);
                        }else{
                            $val['show_parent_sku'] = str_replace($find,$replace,$val['parent_sku']);
                        }
                    }else{
                            $val['name'] = str_replace($find,$replace,$val['name']);
                            $val['show_parent_sku'] = str_replace($find,$replace,$val['parent_sku']);
                    }
                }
            }
            if(!isset($val['store_name'])){
                unset($EnsogoListingData['data'][$key]);
            }
        }
        $data['menu'] = $this->getMenu();
        $data['active'] = '待发布';
        $data['lb_status'] =1;
        $data['EnsogoListingData'] = $EnsogoListingData;
        $data['store'] = $store_info;
        $data['EnsogoListCount'] = $EnsogoListCount;
        AppTrackerApiHelper::actionLog("listing_ensogo", "/listing/ensogo-offline/ensogo-post"); 
        return $this->render('list',$data); 
    }

    public function actionProductFailedPost(){
        $queryString = array();
        $queryString['type'] = Yii::$app->request->get('type',2); 
        $queryString['lb_status'] = Yii::$app->request->get('lb_status',4); //刊登管理 商品状态
        $select_status = Yii::$app->request->get('select_status');
        $search_key = Yii::$app->request->get('search_key');
        $site_id = Yii::$app->request->get('site_id');
        if(!empty($select_status)){
            if(!empty($search_key)){
                $queryString[$select_status]= $search_key;
                $data['search_key'] = $search_key;
            }
        }else{
            if(!empty($search_key)){
                $queryString['keyword'] = $search_key;
                $data['search_key'] = $search_key;
            }
        }
        
        if(!empty($site_id)){
            $queryString['site_id'] = $site_id;
            $data['site_id'] = $site_id;
            
        }   

        if (! empty ( $_GET ['sort'] )) {
            $sort = $_GET ['sort'];
            if ('-' == substr ( $sort, 0, 1 )) {
                $sort = substr ( $sort, 1 );
                $order = 'desc';
            } else {
                $order = 'asc';
            }
        }
    
        if (! isset ( $sort ))
            $sort = 'create_time';
        if (! isset ( $order ))
            $order = 'desc';
        
        if (! empty ( $_GET ['per-page'] )) {
            if (is_numeric ( $_GET ['per-page'] )) {
                $pageSize = $_GET ['per-page'];
            } else {
                $pageSize = 20;
            }
        } else {
            $pageSize = 20;
        }
        if(isset($_GET['select_status'])){
            $data['select_status'] = $_GET['select_status'];
        }
        
        //调用 查询方法
        $EnsogoListingData = EnsogoHelper::getProductList( $queryString, $sort, $order, $pageSize);
        
        $EnsogoListCount = EnsogoProduct::find()->where(['type'=>2,'lb_status'=>4])->asArray()->count();
        $uid = \Yii::$app->user->identity->getParentUid();  
        $store_info =  SaasEnsogoUser::find()->where(['uid'=>$uid])->asArray()->all();
       
        foreach($EnsogoListingData['data'] as $key => &$val){
           foreach($store_info as $k => $v)
            if($val['site_id'] == $v['site_id']){
                $val['store_name'] = $v['store_name'];
            }
            $val['show_parent_sku'] = $val['parent_sku'];
            if(isset($data['search_key'])){
                $replace =["<font style='color:RGB(255,153,96)'>".$data['search_key']."</font>",
                "<font style='color:RGB(255,153,96)'>".ucfirst($data['search_key'])."</font>",
                "<font style='color:RGB(255,153,96)'>".strtoupper($data['search_key'])."</font>",
                ];
                /*替换搜索匹配的关键字*/
                $find = [$data['search_key'],ucfirst($data['search_key']),strtoupper($data['search_key'])];
                if(isset($data['select_status']) && !empty($data['select_status'])){
                    if($data['select_status'] == 'name'){
                        $val['name'] = str_replace($find,$replace,$val['name']);
                    }else{
                        $val['show_parent_sku'] = str_replace($find,$replace,$val['parent_sku']);
                    }
                }else{
                        $val['name'] = str_replace($find,$replace,$val['name']);
                        $val['show_parent_sku'] = str_replace($find,$replace,$val['parent_sku']);
                }
            }
            if(!isset($val['store_name'])){
                unset($EnsogoListingData['data'][$key]);
            }
        }
        $data['menu'] = $this->getMenu();
        $data['active'] = '刊登失败';
        $data['lb_status'] = 4;
        $data['EnsogoListingData'] = $EnsogoListingData;
        $data['store'] = $store_info;
        $data['EnsogoListCount'] = $EnsogoListCount;
        AppTrackerApiHelper::actionLog("listing_ensogo", "/listing/ensogo-offline/ensogo-failed-post"); 
        return $this->render('list',$data); 
    }


	public function actionProductBatchDel(){
        $parent_sku = Yii::$app->request->get('parent_sku');
        $site_id = Yii::$app->request->get('site_id');
        $product_id = Yii::$app->request->get('product_id');
        $model = EnsogoHelper::delProduct($parent_sku,$product_id,$site_id);
        AppTrackerApiHelper::actionLog("listing_ensogo", "/listing/ensogo-offline/product-batch-del"); 
        return $this->renderJson($model);
    }

    public function actionDelProduct(){
        $parent_sku = Yii::$app->request->post('parent_sku');
        $site_id = Yii::$app->request->post('site_id');
        $product_id = Yii::$app->request->post('product_id');
        $model = EnsogoHelper::delProduct($parent_sku,$product_id,$site_id);
        AppTrackerApiHelper::actionLog("listing_ensogo", "/listing/ensogo-offline/del-product"); 
        return $this->renderJson($model);
    }

    public function actionGetTags(){
        $keyword = Yii::$app->request->post('q');
        $str = WishHelper::getTagInfo($keyword);
        $newstr = [];
        if(isset($str['proxyResponse']['data'])){
            foreach($str['proxyResponse']['data']['tags'] as $key=>$val){
                $newstr[$key] = $val['tag'];
            }
        }
        return $this->renderJson($newstr);
    }

    public function actionCheckSkuEnable(){
        $site_id = Yii::$app->request->get('site_id');
        $ensogo_id = Yii::$app->request->get('ensogo_id');
        $parent_sku = Yii::$app->request->get('parent_sku');
        $isExist = true;
        $EnsogoProduct = EnsogoProduct::find()->where(['parent_sku'=>$parent_sku,'site_id'=>$site_id])->asArray()->all();
        $EnsogoProductCount = count($EnsogoProduct);
        if(empty($ensogo_id)){
            if($EnsogoProductCount >= 1){
                $isExist = false;
            }
        }else{
            if($EnsogoProductCount >= 2){
                $isExist = false;
            }
        }
        return $this->renderJson($isExist);
    }

    public function actionCheckVarianceSkuEnable(){
        $site_id = Yii::$app->request->get('site_id');
        $ensogo_id = Yii::$app->request->get('ensogo_id');
        $parent_sku = Yii::$app->request->get('parent_sku');
        $variance_sku_list = Yii::$app->request->get('variance_sku');
        $isExist = true;
        for($i=0,$len=count($variance_sku_list);$i<$len;$i++){
            $sql =  "select count(v.sku) as total from ensogo_product p,ensogo_variance v where p.site_id ={$site_id} and p.parent_sku = v.parent_sku  and v.sku = '{$variance_sku_list[$i]}'";
            $EnsogoVarianceCount = \Yii::$app->subdb->createCommand($sql)->query()->read();
            if(empty($ensogo_id)){
                if($EnsogoVarianceCount['total'] >= 1){
                    $isExist = false;
                }
            }else{
                $sql = "select count(v.sku) as total from ensogo_product p,ensogo_variance v where p.site_id = {$site_id} and p.parent_sku = v.parent_sku and v.sku = '{$variance_sku_list[$i]}' and v.product_id = {$ensogo_id}";
                $EnsogoVarianceCountById = \Yii::$app->subdb->createCommand($sql)->query()->read();
                if(($EnsogoVarianceCountById['total'] >= 1 && $EnsogoVarianceCount['total'] >= 2) || ($EnsogoVarianceCountById['total'] == 0 && $EnsogoVarianceCount['total'] >= 1)){
                    $isExist = false;
                }

            }
        }

        return $this->renderJson($isExist);
    }


    public function actionTestCategory(){
        $store_name = null;
        $data = EnsogoHelper::_TestGetCategory($store_name);
        return $this->render('test',[
                'list' => $data
            ]);
    } 
    #################################wish搬家###########################################################################

    public function actionStoreMove(){
        $type = \Yii::$app->request->get('platform','wish');

        switch($type){
            case 'wish' :
                $data = $this->getWishData();
                break;
            default :
                $data = [
                    'store_id' => 0,
                    'account_list' => []
                ];
                break;
        }

        $options = function(Array $options=[],$val=''){
            $opt = [];
            foreach($options as $value=>$label){
                $selected = $val == $value ? 'selected="selected"':'';
                $opt[] = "<option value='$value' {$selected} >{$label}</option>";
            }
            return implode(PHP_EOL,$opt);
        };

        return $this->render('store_move/'.$type,[
            'menu' => $this->getMenu(),
            'active' => ucfirst($type).'搬家 <font style="color: red">测试版</font>',
            'data' => $data,
            'options' => $options,
            'request' => \Yii::$app->request
        ]);
    }

    protected function getOptions(){
        return function(Array $options=[],$val=''){
            $opt = [];
            foreach($options as $value=>$label){
                $selected = $val == $value ? 'selected="selected"':'';
                $opt[] = "<option value='$value' {$selected} >{$label}</option>";
            }
            return implode(PHP_EOL,$opt);
        };
    }

    public function actionSaveWishMoveLog(){
        $category_id = \Yii::$app->request->post('category_id',0);
        $shipping_time = \Yii::$app->request->post('shipping_time',"15-30");
        $store_id = \Yii::$app->request->post('store_id',0);
        $total = \Yii::$app->request->post("total",0);
        $puid = \Yii::$app->user->identity->getParentUid();

        if(empty($category_id) || empty($store_id) || $total == 0){
            $result = ["success"=>false,"message"=>"参数不正确,请重新尝试"];
        } else {
            $store_obj = new EnsogoStoreMoveLog();
            $store_obj->puid = $puid;
            $store_obj->store_id = $store_id;
            $store_obj->platform = "wish";
            $store_obj->move_number = $total;
            $store_obj->success_number = 0;
            $store_obj->error_api_message = "";
            $store_obj->error_message = "";
            $store_obj->shipping_time = $shipping_time;
            $store_obj->category_id = $category_id;
            $store_obj->wish_product_id = 0;
            $store_obj->create_time = time();
            $store_obj->update_time = time();
            $bool = $store_obj->save(false);
            if($bool){
               $result = ["success"=>true,"message"=>"添加成功","id"=>$store_obj->id];
            } else {
                $result = ["success"=>false,"message"=>$store_obj->getErrors()];
            }
        }

        return $this->renderJson($result);

    }

    public function actionWishProductMove(){

        $category_id = \Yii::$app->request->post('category_id',0);
        $shipping_time = \Yii::$app->request->post('shipping_time',"15-30");
        $product_id = \Yii::$app->request->post("wish_product_id","");
        $log_id = \Yii::$app->request->post("log_id",0);
        $ensogo_site_id = \Yii::$app->request->post("ensogo_site_id");

        $result = ["success"=>false,"message"=>"参数不正确,搬家失败"];
        if(empty($product_id) || empty($category_id)){
            $result = ["success"=>false,"message"=>"参数不正确,搬家失败"];
        } else {
            $wish_fanben_info = WishFanben::find()->where(["wish_product_id" => $product_id])->asArray()->one();
            if($wish_fanben_info === false){
                $result = ["success"=>false,"message"=>"商品不存在,搬家失败"];
            } else {
                $wish_variants_info = WishFanbenVariance::find()->where(["parent_sku"=>$wish_fanben_info['parent_sku']])->asArray()->all();
                if($wish_variants_info === false){
                    $result = ["success"=>false,"message"=>"商品变种不存在,搬家失败"];
                } else {
                    try{
                    $result = EnsogoStoreMoveHelper::wishStoreMove($wish_fanben_info,$wish_variants_info,$shipping_time,$category_id,$log_id,$ensogo_site_id);
                    }catch(\Exception $e){
                        $result = ['success'=>false,'message'=>$e->getMessage().$e->getFile().$e->getLine()];
                }
            }
        }
        }

        return $this->renderJson($result);
    }

    /**
     * 批量发布选择类目界面
     * @return [type] [description]
     */
    public function actionMultiPushConfirm(){
        $accounts = SaasEnsogoUser::find()->where([
            'is_active'=>1,
            'uid' => \Yii::$app->user->id
        ]);
        return $this->renderAuto('store_move/multi-push-confirm',[
            'account_list' => $accounts->all(),
            'options' => $this->getOptions(),
            'request' => \Yii::$app->request
        ]);
    }

    /**
     * 获取批量发布进度信息
     * @return [type] [description]
     */
    public function actionGetProgress(){
        return $this->renderAuto('store_move/get-progress',[]);
    }


    protected function getWishData(){
        $store_id = \Yii::$app->request->get('site_id',0);
        $tags_name = \Yii::$app->request->get('tags_name','');
        $puid = \Yii::$app->user->identity->getParentUid();
        $account_info = WishAccountsApiHelper::ListAccounts($puid);

        $result = [
            'store_id' => $store_id,
            'account_list' => $account_info,
            'tags_name' => $tags_name,
            'page' => [],
            'tags_list' => [],
            'product_list' => []
        ];

        //根据不同的WISH ID 获取信息
        if($store_id){
            $product_list = $this->getWishProductInfo($puid,$store_id,$tags_name);
            $result['tags_list'] = $product_list['tags_list'];
            $result['product_list'] = $product_list['product_list'];
            $result['page'] = $product_list['page'];
        }

        return $result;
    }


    public function getWishProductInfo($puid,$site_id,$tags){

        $page = \Yii::$app->request->get('page',0);
        //获取生成好的WISH商品标签
        $wish_tag_obj = EnsogoWishTagLog::find()->where(["puid"=>$puid,"store_id"=>$site_id]);
        $wish_tag_obj->andWhere([">","validity_period",time() ]);
        //echo $wish_tag_obj->createCommand()->getRawSql();
        $wish_tag_info = $wish_tag_obj->asArray()->one();
        $tags_info = json_decode($wish_tag_info['tags_info'],true);

        //获取WISH 商品
        $wish_fanben_obj = WishFanben::find()->select(["wish_product_id","tags","name","parent_sku","main_image","site_id"])->where(["and" , "site_id={$site_id}", ["or","`status`='online'","`status`='approved'"] ]);
        if(!empty($tags)){
            $wish_fanben_obj->andWhere(["like","tags",$tags]);
        }

        $pagination = new Pagination([
            'totalCount' => $wish_fanben_obj->count(),
            'defaultPageSize' => 200,
            'pageSize' => 200,
            'params' => $_REQUEST
        ]);

        $product = $wish_fanben_obj->offset($pagination->offset)->limit($pagination->limit)->asArray()->all();
        return ["tags_list" => $tags_info,"product_list"=>$product,"page" => $pagination];

    }


    #################################wish搬家###########################################################################

	#################################excel搬家###########################################################################
    // excel搬家 : 上面的actionStoreMove 貌似可以让excel搬家作为一个选项加入
    // /listing/ensogo-offline/store-move-by-file
    public function actionStoreMoveByFile(){
    	$puid = \Yii::$app->user->identity->getParentUid();
    	$aliexpressUsers = SaasAliexpressUser::find()->where(['uid'=>$puid])->asArray()->all();
    	$ensogoUsers = SaasEnsogoUser::find()->where(['uid'=>$puid])->asArray()->all();
    	
    	//status： 0 未处理 ，1处理中 ，2完成 ，3失败，4已下载
    	$eportBgControls = UserBackgroundJobControll::find()->where(['puid'=>$puid,'job_name'=>"export_ali_listing"])
    	->andWhere('status<>4')->orderBy("last_finish_time desc")->asArray()->all();
    	
    	$importBgControls = UserBackgroundJobControll::find()->where(['puid'=>$puid,'job_name'=>"collect_ensogo_listing_from_excel"])
    	->andWhere(['is_active'=>"Y",'status'=>[0,1]])->asArray()->one();
    	
    	$listings = array();
    	if(empty($importBgControls)){// 没有正在执行的导入任务的话，显示上一批导入情况
    		
    		// 显示最后一批刊登最结果
    		$recentBatchNum = ImportEnsogoListing::find()->where(['puid'=>$puid])->max("batch_num");
    		 
    		$query = ImportEnsogoListing::find()->where(['puid'=>$puid,'batch_num'=>$recentBatchNum])->andWhere('status<>0');
    		// 由于只显示最后一批刊登最结果，所以不分页了
//     		$pages = new Pagination(['totalCount' =>$query->count(), 'defaultPageSize'=>50 , 'pageSizeLimit'=>[5,200] , 'params'=>$_REQUEST]);
//     		$listings = $query->offset($pages->offset)->limit($pages->limit)->asArray()->all();
    		
    		$listings = $query->asArray()->all();
    	}

    	$data = array();
    	$data['aliexpressUsers'] = $aliexpressUsers;
    	$data['ensogoUsers'] = $ensogoUsers;
    	$data['exportExecutionInfo'] = $eportBgControls;
    	$data['importExecutionInfo'] = $importBgControls;
    	$data['listingExecutionInfo'] = $listings;
//     	$data['pages'] = $pages;
    	
    	return $this->render('store_move/file_import',[
            'menu' => $this->getMenu(),
            'active' => '速卖通搬家 <font style="color: red">测试版</font>',
            'data' => $data,
        ]);
    }
    
    // excel搬家： 导出速卖通listing 到excel.
    // 产品构思是一次只能导出一个速卖通账号的，一开始没有理解到，后台job做成了可以支持多个速卖通账号导出的形式。
    // /listing/ensogo-offline/export-ali-listing
    public function actionExportAliListing(){
		$type = \Yii::$app->request->post('type','excel');
		$aliSelleruserid = \Yii::$app->request->post('ali_account');
		
		if(empty($aliSelleruserid)){
			return ResultHelper::getResult(400, "", "请选择速卖通账号。");
		}
		
		$ensogoAccount = \Yii::$app->request->post('ensogo_account');
		if(empty($ensogoAccount)){
			return ResultHelper::getResult(400, "", "请选择ensogo店铺。");
		}
		
		$SEU = SaasEnsogoUser::findOne(['site_id'=>$ensogoAccount]);
		if(empty($SEU)){
			return ResultHelper::getResult(400, "", "选择的ensogo店铺不存在。");
		}
		
		$ensogoStoreName = $SEU->store_name;
		$puid = \Yii::$app->user->identity->getParentUid();
		
		$listingNum = AliexpressListing::find()->where(['selleruserid'=>$aliSelleruserid])->count();
		
// 		$listingNum = 1001;
		if(empty($listingNum)){
			return ResultHelper::getResult(400, "", "账号：{$aliSelleruserid} 暂时没有可以导出的listing。");
		}
		
// 		if($listingNum <= 1000){// 1000个listing以内即时导出excel
// 		if($listingNum <= 500){// dzt20160319 4179 客户cn1515513223eugi 账号导出500+产品爆内存，所以这里改为500
		if(false){// dzt20160319 4146客户cn1512025537  账号导出100+产品 工4000+条记录 爆120M内存，不想在组织完数据再判断是否走后台任务，全部导出改为后台进行
			try {
				if("excel" == $type){
					AliexpressHelper::exportLiting($aliSelleruserid,$ensogoStoreName,"xls",false,$puid);
				}else{
					AliexpressHelper::exportLiting($aliSelleruserid,$ensogoStoreName,"csv",false,$puid);
				}
				
				$nowTime = time();
				// 修改显示文件生成时间
				$userBgControl = UserBackgroundJobControll::find()->where(["puid"=>$puid,"job_name"=>"export_ali_listing","custom_name"=>$aliSelleruserid])->one();
				if ($userBgControl === null){
					$userBgControl = new UserBackgroundJobControll;
					$userBgControl->puid = $puid;
					$userBgControl->job_name = "export_ali_listing";
					$userBgControl->custom_name = $aliSelleruserid;
					$userBgControl->create_time = $nowTime;
				}
				$userBgControl->is_active = "N";
				$userBgControl->status = 2;// 0 未处理 ，1处理中 ，2完成 ，3失败，4已下载
				$userBgControl->error_count = 0;
				$userBgControl->error_message = "";
				$userBgControl->additional_info = json_encode(array("exportType"=>$type,"ensogo_account"=>$ensogoStoreName));// 记录导出文件格式
				$userBgControl->next_execution_time = $nowTime;
				$userBgControl->last_begin_run_time = $nowTime;
				$userBgControl->last_finish_time = $nowTime;
				$userBgControl->update_time = $nowTime;
				$userBgControl->save(false);	
				
				// 201 导出已完成
				return ResultHelper::getResult(201, array('aliAccount'=>$aliSelleruserid), $aliSelleruserid." 商品数据已经组织完成，请点击页面 “下载” 按钮下载。");
			} catch (\Exception $e) {
				$errorMessage = "file:".$e->getFile()." line:".$e->getLine()." message:".$e->getMessage();
				yii::error("actionExportAliListing message:".$errorMessage,"file");
				return ResultHelper::getResult(400, "", $aliSelleruserid."商品数据导出错误：".$e->getMessage());
			}
			
		}else{// 导出 写入后台任务
			$nowTime = time();
			$userBgControl = UserBackgroundJobControll::find()->where(["puid"=>$puid,"job_name"=>"export_ali_listing","custom_name"=>$aliSelleruserid])->one();
			if ($userBgControl === null){
				$userBgControl = new UserBackgroundJobControll;
				$userBgControl->puid = $puid;
				$userBgControl->job_name = "export_ali_listing";
				$userBgControl->custom_name = $aliSelleruserid;
				$userBgControl->create_time = $nowTime;
			}else{
				if("Y" == $userBgControl->is_active){// 产品导出期间，不允许再进行导出
					if(1 == $userBgControl->status){
						return ResultHelper::getResult(400, "", $aliSelleruserid."商品数据正在组织，请稍候。");
					}
					
					if(0 == $userBgControl->status){
						return ResultHelper::getResult(400, "", $aliSelleruserid."商品数据正等待处理，请稍候。");
					}
				}
			}
			
			$userBgControl->status = 0;// 0 未处理 ，1处理中 ，2完成 ，3失败，4已下载
			$userBgControl->error_count = 0;
			$userBgControl->error_message = "";
			$userBgControl->is_active = "Y";// 运行完之后，关闭
			$userBgControl->additional_info = json_encode(array("exportType"=>$type,"ensogo_account"=>$ensogoStoreName));// 记录导出文件格式
			$userBgControl->next_execution_time = $nowTime;
			$userBgControl->update_time = $nowTime;
			$userBgControl->save(false);
			
			// 200 导出未完成
			return ResultHelper::getResult(200, array('aliAccount'=>$aliSelleruserid), $aliSelleruserid."商品数据正在组织，请稍候。");
		}
    }
    
    // excel搬家: 检查导出任务是否完成
    // /listing/ensogo-offline/check-export-ali-listing
    public function actionCheckExportAliListing(){
    	$puid = \Yii::$app->user->identity->getParentUid();
    	$aliSelleruserid = \Yii::$app->request->get('ali_account');
    	if(empty($aliSelleruserid)){
    		return ResultHelper::getResult(400, "", "请选择速卖通账号。");
    	}
    	
    	// 这里要不要检查文件是否存在？按道理这里文件是已经有了的
    	$userBgControl = UserBackgroundJobControll::findOne(["puid"=>$puid,"job_name"=>"export_ali_listing","custom_name"=>$aliSelleruserid]);
    	if(empty($userBgControl) || $userBgControl->status == 4)
    		return ResultHelper::getResult(400, "", "速卖通账号$aliSelleruserid 未有导出任务。");
    	
    	if($userBgControl->status == 3)
    		return ResultHelper::getResult(400, "", "速卖通账号$aliSelleruserid 导出失败。");
    	
    	if($userBgControl->status == 0 ||  $userBgControl->status == 1){
    		return ResultHelper::getResult(200, "", $aliSelleruserid."商品数据正在组织，请稍候。");
    	}else{
    		return ResultHelper::getResult(201, array('aliAccount'=>$aliSelleruserid), $aliSelleruserid." 商品数据已经组织完成，请点击页面 “下载” 按钮下载。");
    	}
    }
    
    // excel搬家: 导出 速卖通 listing 到excel
    // ajax无法输出文件流，所以要 open window的形式将 导出的excel 输出
    // /listing/ensogo-offline/download-listing-to-excel
    public function actionDownloadListingToExcel(){
    	$puid = \Yii::$app->user->identity->getParentUid();
   		$aliSelleruserid = \Yii::$app->request->get('ali_account');
		if(empty($aliSelleruserid)){
			return ResultHelper::getResult(400, "", "请选择速卖通账号。");
		}
		
		// 这里要不要检查文件是否存在？按道理这里文件是已经有了的
		$userBgControl = UserBackgroundJobControll::findOne(["puid"=>$puid,"job_name"=>"export_ali_listing","custom_name"=>$aliSelleruserid,'status'=>2]);
		if(empty($userBgControl))
			return ResultHelper::getResult(400, "", "速卖通账号$aliSelleruserid 未完成导出。");
		
		$exportType = json_decode($userBgControl->additional_info,true);
		// dzt20160318 可能是共享目录或者 nginx 有文件缓存了，导致4179 客户的export_ali_listing_cn1511363170.xls下载总是下载不到正常的excel
		if("excel" == $exportType['exportType']){
			$fileName = $userBgControl->job_name."_".$userBgControl->custom_name.".xls?t=".time();
		}else{
			$fileName = $userBgControl->job_name."_".$userBgControl->custom_name.".csv?t=".time();
		}
		
		$userBgControl->status = 4;
		$userBgControl->save();
		
    	$this->redirect("/attachment/tmp_export_file/$fileName");
    }
    
    // excel搬家： 导入excel
    // /listing/ensogo-offline/import-ensogo-listing-from-excel
    public function actionImportEnsogoListingFromExcel(){
    	
    	try {
//     		AppTrackerApiHelper::actionLog("eagle_v2","/catalog/product/excel2-product");
    		if (!empty ($_FILES["input_import_file"]))
    			$file = $_FILES["input_import_file"];
    		
    		$excelTmpPath = \Yii::getAlias("@eagle/web/attachment/tmp_export_file").DIRECTORY_SEPARATOR;
    		$name = $file["name"];
    		$originName = date ( 'YmdHis' ) . '-' .rand(1,100).substr ( md5 ( $name ), 0, 5 ) . '.' . pathinfo ( $name, PATHINFO_EXTENSION );
    		\Yii::info("actionImportEnsogoListingFromExcel unload_tmp_file: ".json_encode($_FILES).",target_file:".$excelTmpPath . $originName,"file");
    		if(move_uploaded_file ( $file["tmp_name"] , $excelTmpPath . $originName ) === false) {
    			return ResultHelper::getResult(400, "", $file["name"]." 上传失败，请重试。");
    		}
    		
	    	$puid = \Yii::$app->user->identity->getParentUid();
	    	$job_name = "collect_ensogo_listing_from_excel";
	    	$nowTime = time();
	    	
	    	// 读取ensogo 刊登后台任务
	    	$userBgControl = UserBackgroundJobControll::find()->where(["puid"=>$puid,"job_name"=>$job_name])->one();
			if ($userBgControl === null){
				$userBgControl = new UserBackgroundJobControll;
				$userBgControl->puid = $puid;
				$userBgControl->job_name = $job_name;
				$userBgControl->create_time = $nowTime;
			}else{
				if("Y" == $userBgControl->is_active){// 产品导入期间，不允许再进行导入
					if(1 == $userBgControl->status){
						return ResultHelper::getResult(400, "", "上次导入的excel正在处理中，请稍候再导入。");
					}
					
					if(0 == $userBgControl->status){
						return ResultHelper::getResult(400, "", "上次导入的excel正等待处理，请稍候再导入。");
					}
				}
			}
			
			$userBgControl->status = 0;// 0 未处理 ，1处理中 ，2完成 ，3失败
			$userBgControl->error_count = 0;
			$userBgControl->is_active = "Y";// 运行完之后，关闭
			$userBgControl->additional_info = json_encode(array("originFileName"=>$name,"fileName"=>$originName,"filePath"=>$excelTmpPath.$originName));// 记录导出文件格式
			$userBgControl->next_execution_time = $nowTime;
			$userBgControl->update_time = $nowTime;
			$userBgControl->save(false);
			
			return ResultHelper::getResult(200, "", "excel正在处理中，请稍候。");
		} catch (\Exception $e) {
			\Yii::error("File:".$e->getFile().",Line:".$e->getLine().",Message:".$e->getMessage(),"file");
			return ResultHelper::getResult(400 , "" , $e->getMessage());
		}
    }
    
    // excel搬家： 检查刊登任务是否完成
    // /listing/ensogo-offline/check-import-ali-listing
    public function actionCheckImportAliListing(){
    	$puid = \Yii::$app->user->identity->getParentUid();
    	$job_name = "collect_ensogo_listing_from_excel";
    	$nowTime = time();
    	
    	// 读取excel写入后台任务
    	$userBgControl = UserBackgroundJobControll::findOne(["puid"=>$puid,"job_name"=>$job_name]);
    	if(empty($userBgControl))
    		return ResultHelper::getResult(400, "", "未有刊登任务执行。");
    	 
    	if($userBgControl->status == 3)
    		return ResultHelper::getResult(400, "", "刊登失败。");
    	 
    	if($userBgControl->status == 0 ||  $userBgControl->status == 1){
    		return ResultHelper::getResult(200, "", "导入任务正在执行中，请稍候。");
    	}else{
    		$recentBatchNum = ImportEnsogoListing::find()->where(['puid'=>$puid])->max("batch_num");
    		$query = ImportEnsogoListing::find()->where(['puid'=>$puid,'batch_num'=>$recentBatchNum])->andWhere('status<>0');
    		$listings = $query->asArray()->all();
    		
    		return ResultHelper::getResult(201, $listings, "导入任务执行完成。");
    	}
    	
    }
    
    // excel搬家: 导出 最近一批 发布失败的 listing 到excel
    // ajax无法输出文件流，所以要 open window的形式将 导出的excel 输出
    // /listing/ensogo-offline/download-error-listing-to-excel
    public function actionDownloadErrorListingToExcel(){
    	$puid = \Yii::$app->user->identity->getParentUid();
    	
    	$recentBatchNum = ImportEnsogoListing::find()->where(['puid'=>$puid])->andWhere('status<>0')->max("batch_num");
    	
    	// 导出显示最后一批刊登失败listing
    	$listings = ImportEnsogoListing::find()->where(['status'=>4,'puid'=>$puid,'batch_num'=>$recentBatchNum])->asArray()->all();
    	
    	$storeName = "";
    	foreach ($listings as &$listing){
    		if(empty($storeName))
    			$storeName = $listing['ensogo_store'];
    		unset($listing['id']);
    		unset($listing['batch_num']);
    		unset($listing['puid']);
    		unset($listing['error_message']);
    	}
    	 
    	$filed_array = array(
			"速卖通分类",
			"*ensogo分类",
			"*发布店铺",
			"*父SKU",
			"*子SKU",
			"*产品标题",
			"颜色",
			"尺寸",
			"*产品标签(用英文逗号[,]隔开)",
			"*产品描述",
			"市场价($)",
			"*售价($)",
			"*库存",
			"*运费($)",
			"*运输时间(天)",
			"品牌",
			"UPC（通用产品代码）",
			"Landing Page URL",
			"*产品主图链接",
			"附图链接1",
			"附图链接2",
			"附图链接3",
			"附图链接4",
			"附图链接5",
			"附图链接6",
			"附图链接7",
			"附图链接8",
			"附图链接9",
			"附图链接10",
		);
    	
    	// 要导出两个sheet ，ensogo 分类在第一个，数据在第二个
    	$sheetInfo = array();
    	
    	$dataSheetIndex = 0;
    	$catSheetIndex = 1;
    	
    	$sheetInfo[$dataSheetIndex]['title'] = "搬家数据";
    	$sheetInfo[$dataSheetIndex]['filed_array'] =$filed_array;
    	$sheetInfo[$dataSheetIndex]['data_array'] = $listings;
    	
    	// 获取所有ensogo 分类
    	$categories = EnsogoHelper::_getEnsogoCategory();
    	
    	$sheetInfo[$catSheetIndex]['title'] = "分类列表";
//     	$sheetInfo[$catSheetIndex]['filed_array'] = array("分类","Categories","分类代码","所属分类");
//     	$sheetInfo[$catSheetIndex]['filed_array'] = $categories['filed_array'];

    	array_unshift($categories['data_array'],$categories['filed_array']);
    	array_unshift($categories['data_array'],array("“*ensogo分类”属性填写分类代码"),array("“*ensogo分类”和“*运费($)”为必须修改的两个属性，其他属性可根据情况修改"),array());
    	$sheetInfo[$catSheetIndex]['data_array'] = $categories['data_array'];
    	
    	ExcelHelper::justExportToExcel($sheetInfo,"export_ensogo_error_listing.xls");
    }
    
    #################################excel搬家###########################################################################
    
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

    public function actionTestGetUserName(){
        EnsogoHelper::GetOnePlatformUserName(); 
    }
}