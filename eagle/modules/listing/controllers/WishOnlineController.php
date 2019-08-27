<?php
/**
 *  wish 在线商品管理
 */
namespace eagle\modules\listing\controllers;

use yii;
use yii\data\Pagination;
use yii\data\ActiveDataProvider;
use eagle\components\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\data\Sort;
use eagle\models\QueueProductLog;
use eagle\models\SaasWishUser;
use eagle\modules\listing\models\WishApiQueue;
use eagle\modules\listing\models\WishFanben;
use eagle\modules\listing\models\WishFanbenVariance;
use eagle\modules\listing\models\WishOrder;
use eagle\modules\listing\models\WishOrderDetail;
use eagle\modules\platform\apihelpers\WishAccountsApiHelper;
use eagle\modules\listing\helpers\WishHelper;
use eagle\modules\util\helpers\OperationLogHelper;
use eagle\modules\util\helpers\StandardConst;
use eagle\modules\util\helpers\TranslateHelper;
use eagle\modules\util\helpers\ResultHelper;
use eagle\modules\listing\helpers\WishProxyConnectHelper;
use eagle\modules\listing\helpers\SaasWishFanbenSyncHelper;
use eagle\modules\app\apihelpers\AppTrackerApiHelper;
use eagle\modules\catalog\helpers\ProductApiHelper;
use eagle\modules\listing\service\wish\Account;
use eagle\modules\listing\service\Queue;
use yii\base\Exception;
use yii\helpers\ArrayHelper;


class WishOnlineController extends Controller
{
    public $enableCsrfValidation = FALSE;

    protected $CatalogModulesAppKey = 'catalog';

    public static $default_search_condition = [
        1 => '标题',
        2 => 'Product Id',
        3 => 'SKU'
    ];

    public static $sarch_keyword = [
        1 => 'name',
        2 => 'wish_product_id',
        3 => 'parent_sku'
    ];

    public static $wish_status = [
        7 => '待审核',
        8 => '已批准',
        9 => '被拒绝',
        /*
        'posting' => '待审核',
        'online' => '已批准',
        'approved' => '已批准',
        'rejected' => '被拒绝',
        'pending' => '待审核',
        */
    ];

    public static $wish_model = [
        'title' => '标题',
        'price' => '价格',
        'inventory' => '库存'
    ];

    public static $wish_type = [
        'title' => [
            'fadd'=>'在前面添加',
            'badd'=>'在后面添加',
            'fdel'=>'在前面删除',
            'bdel'=>'在后面删除',
            'rp'=>'替换'
        ],
        'price' => [
            'add'=>'加',
            'minus'=>'减',
            'ride'=>'乘',
            'divide'=>'除',
            'increase'=>'按百分比加',
            'reduction'=>'按百分比减',
            'rp'=>'替换',
        ],
        'inventory' => [
            'add'=>'加',
            'minus'=>'减',
            'ride'=>'乘',
            'divide'=>'除',
            'conditionadd'=>'按条件加',
            'conditionmakeup'=>'按条件补货',
            'rp'=>'替换',
        ]
    ];


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
    /**
     * 获取分页信息
     * @param $totalCount 记录总数
     * @param int $defaultPageSize 每页数量
     * @param array $pageSizeLimit 分页数取件
     * @return Pagination
     */
    public function getPage($totalCount,$defaultPageSize=10,$pageSizeLimit = [5,10,20,50],$pagesize=20){
        return new Pagination([
            'totalCount' =>$totalCount,
            'defaultPageSize' => $defaultPageSize,
            'pageSize' => $pagesize,
            'pageSizeLimit'=>$pageSizeLimit,
        ]);
    }

    public function actionCheckSyncStatus(){
        $site_id = Yii::$app->request->post('type','');
        AppTrackerApiHelper::actionLog("List-wish", "/listing/wish/sync-proudct");
        if(empty($site_id)){
            return json_encode(['code' => 202,'message' => 'WISH平台账号信息不正确！']);die;
        }

        //检查WISH账号是否正确
        $wish_account = WishAccountsApiHelper::RetrieveAccountBySiteID($site_id);

        if(!$wish_account['success']){
            return json_encode(['code' => 202,'message' => 'WISH平台账号信息不正确！']);die;
        }

        $puid = Yii::$app->user->identity->getParentUid();
        //检查队列信息是否存在
        $command = \Yii::$app->db->createCommand("select * from sync_product_api_queue where platform = 'wish'  and puid = '".$puid."' and seller_id='".$wish_account['account']['store_name']."' and status in ('P','S')");
        $queueList = $command->queryAll();
        if (count($queueList)>0 || empty($wish_account['account']['last_product_success_retrieve_time'])){
            return json_encode(['code'=>201, 'message' => '商品正在同步，请耐心等待！','data'=>json_encode($queueList),'wish'=>json_encode($wish_account)]);die;
        }

        return json_encode(['code' => 200, 'message' => "商品正在同步：0"]);die;

    }


    public function actionRealtimeSync(){
        $site_id = Yii::$app->request->post('site_id','');
        AppTrackerApiHelper::actionLog("List-wish", "/listing/wish/sync-proudct");
        if(empty($site_id)){
            return $this->renderJson(['code' => 202,'message' => 'WISH平台账号信息不正确！']);die;
        }

        //检查WISH账号是否正确
        $wish_account = WishAccountsApiHelper::RetrieveAccountBySiteID($site_id);

        if(!$wish_account['success']){
            return $this->renderJson(['code' => 202,'message' => 'WISH平台账号信息不正确！']);die;
        }
        $InsertIntoSyncQueue = new Queue('wish');
        $InsertIntoSyncQueueResult = $InsertIntoSyncQueue->addProductQueueBySiteId($site_id);
        return $this->renderJson($InsertIntoSyncQueueResult);
    
    }

    public function actionGetQueueProcess(){
        $site_id = Yii::$app->request->post('site_id','');
        $syncId = Yii::$app->request->post('syncId','');
        if(empty($site_id)){
            return json_encode(['success'=> false, 'message' => 'WISH平台账号信息不正确！']);die;
        }
        // $store_info = Account::getAccountBySiteId($site_id);
        if(!empty($syncId)){
            $GetQueueProgress = new Queue('wish');
            $getProgressNum = $GetQueueProgress->getProgress($syncId);
            if($getProgressNum == null && !empty($syncId)){
                $getProgressNum = QueueProductLog::find()->select(['total_product'])->where(['id'=>$syncId,'status'=>'C'])->asArray()->one();
                $data['total_product'] = $getProgressNum['total_product'];
                $data['status'] = 'completed';
            }else{
                $data['status'] = 'pending';
                $data['completed_num'] = $getProgressNum;
            }
            return $this->renderJson($data);
        }else{
            return $this->renderJson(['success'=>false,'message'=>'获取同步信息进度失败']);die;
        }

    }

    public function actionWishProductList(){
       $enable = Yii::$app->request->get('enable','Y');
       $data = $this->getWishProductList($enable); 
       return $this->render('list',$data);
    }

    public function actionWishBatchModify(){
        $product_arr = array_unique(Yii::$app->request->post('product_id'));
        $site_id = Yii::$app->request->post('site_id');
        $WishListCount = WishFanben::find()->where(['type'=>2,'lb_status'=>4])->asArray()->count(); 
        $data = [];
        foreach($product_arr as $product){
            $obj = WishFanben::find()->where(['id'=>$product,'site_id'=>$site_id]);
            // echo $obj->createCommand()->getRawSql()."\n";
            $data[$product] = $obj->asArray()->one(); 
            if($variance_arr = Yii::$app->request->post('variance_'.$product)){
                $variance_arr = array_unique($variance_arr);
                foreach($variance_arr as $variance){
                    $data[$product]['variance'][]=WishFanbenVariance::find()->where(['id'=>$variance,'fanben_id'=>$product])->asArray()->one();
                }
            }

        }
        return $this->render('modify',[
            'list' => $data,
            'WishListCount' => $WishListCount,
            'active' => 'Wish平台商品'
        ]);
    }

    public function actionDealBatchModify(){
        $title_success = 0;
        $title_fail = 0;
        $price_success = 0;
        $price_fail = 0;
        $inventory_success = 0;
        $inventory_fail = 0;
        $title_is_modify = false;
        $price_is_modify = false;
        $inventory_is_modify = false;
        $product_list = Yii::$app->request->post('product');
        foreach($product_list as $product){
                $modify_title = Yii::$app->request->post('product_title_'.$product,'');
                $product_info = WishFanben::findOne($product);
                $wish_account_list = WishHelper::getWishAccountList();
                if (!isset($wish_account_list[$product_info['site_id']])){
                    $rtn = ['code' => 201, "message"=>"店铺不存在",'success'=>false];
                    return $this->renderJson($rtn); 
                }else{
                    if($modify_title){
                        $title_is_modify = true;
                        if($product_info == NULL){
                            $data['error'][]= ['code'=> 202,"message"=>"商品ID".$product."不存在"];
                        }
                        $product_info->name = $modify_title;
                        if(!$product_info->save()){
                            $data['error'][] = ['code'=> 203 ,"message"=> "商品ID".$product."保存失败"];
                            $title_fail++;
                        }else{
                            $result = WishHelper::pushUpdateProduct($wish_account_list[$product_info['site_id']], $product_info);
                            if($result['code'] == 200){
                                $title_success++;
                            }else{
                                $title_fail++;
                                $data['error'][] = ['code'=>204,"message"=>"商品ID".$product."修改失败"];
                            }
                        }
                    }
                    
                    $variance_arr = Yii::$app->request->post('variance_'.$product);
                    foreach($variance_arr as $variance){
                        $modify_price = Yii::$app->request->post('variance_price_'.$variance, 0);
                        $modify_inventory = Yii::$app->request->post('variance_inventory_'.$variance, 0);
                        $variance_info = WishFanbenVariance::findOne($variance); 
                        if($variance_info == NULL){
                            $data['error'][] = ['code'=> 202 ,"message"=>"变种ID".$variance."不存在"];
                        }
                        if($modify_price > 0){
                            $price_is_modify = true;
                            $variance_info->price = $modify_price;
                        }
                        if($modify_inventory){
                            $inventory_is_modify = true;
                            $variance_info->inventory = $modify_inventory;
                        }
                        if(!$variance_info->save()){
                            $data['error'][] = ['code'=> 203, "message"=>"变种ID".$variance."保存失败"];
                            $price_fail++;
                        }else{
                            $result = WishHelper::pushUpdateProductVariationInfo($wish_account_list[$product_info['site_id']], $variance_info);
                            if($result['code'] == 200){
                                $price_success++;
                            }else{
                                $price_fail++;
                                $data['error'][] = ['code'=> 204,"message"=>"变种ID".$variance."修改价格失败"];
                            }
                            $result = WishHelper::pushUpdateProductVariationInfo($wish_account_list[$product_info['site_id']], $variance_info);
                            if($result['code'] == 200){
                                $inventory_success++;
                            }else{
                                $inventory_fail++;
                                $data['error'][] = ['code' => 204, "message"=>"变种ID".$variance."修改库存失败"];
                            }
                        }
                    }
                }
                if($title_fail > 0 || $price_fail > 0 || $inventory_fail >0){
                    $data['success']  = false;
                }else{
                    $data['success'] = true;
                }
                $data['message'] = '';
                if($title_is_modify){
                   $data['message'] .= '商品批量修改标题:成功'.$title_success.',失败'.$title_fail.';';
                }
                if($price_is_modify){
                    $data['message'] .= '变种批量修改价格:成功'.$price_success.',失败'.$price_fail.';';
                }
                if($inventory_is_modify){
                    $data['message'] .= '变种批量修改库存:成功'.$inventory_success.',失败'.$inventory_fail;
                }
               
        }
        return $this->renderJson($data);
    }

    public function actionOfflineProductList(){
        $data = $this->getProductList('N');
        AppTrackerApiHelper::actionLog("List-wish", "/listing/offline-wish/wish-list");
        return $this->render('offline_product_list',$data);
    }
    public function actionOnlineProductList(){
        $data = $this->getProductList('Y');
        // var_dump($data);
        AppTrackerApiHelper::actionLog("List-wish", "/listing/online-wish/wish-list");
        return $this->render('online_product_list',$data);
    }

    public function actionProductChange(){
        AppTrackerApiHelper::actionLog("List-wish", "/listing/online-wish/product-change");
        $fanben_id = Yii::$app->request->post('id',0);
        $status = Yii::$app->request->post('status','');

        if(empty($fanben_id) || ($status != '1' && $status != '2')){
            echo json_encode(['code'=>201,'message'=>'参数错误！']);die;
        }

        $status = $status == 1 ? false : true;
        $fanben_info = WishFanben::findOne($fanben_id);

        if($fanben_info == NULL){
            echo json_encode(['code'=>203,'message'=>'商品信息不存在！']);die;
        }

        $site_id = $fanben_info['site_id'];
        $wish_account = WishAccountsApiHelper::RetrieveAccountBySiteID($site_id);

        $parent_sku = $fanben_info['parent_sku'];

        $retuslt = WishHelper::changeProductStatus($wish_account['account'],$status,$parent_sku);

        if($retuslt['code'] == 200){
            $fanben_info->is_enable = $status ? 1 : 3;
            $fanben_info->save();

            //更新所有商品状态
            $enable = $status ? 'Y' : 'N';
            WishFanbenVariance::updateAll(['enable'=>$enable],['fanben_id'=>$fanben_info->id]);
        }

        echo json_encode($retuslt);die;
    }

    public function actionProductVariationChange(){
        $id = Yii::$app->request->post('vid','');
        $status = Yii::$app->request->post('status',''); // 1下架 2上架
        if($status == 1){
           AppTrackerApiHelper::actionLog("List-wish", "/listing/wish/batch-download-proudct"); 
        }else{
            AppTrackerApiHelper::actionLog("List-wish", "/listing/wish/batch-upload-product");
        }
        if(empty($id) || ($status != 1 && $status != 2)){
            echo json_encode(['code'=>201,'message'=>'参数错误！']);die;
        }

        $wish_account_list = WishHelper::getWishAccountList();

        if(empty($wish_account_list)){
            echo json_encode(['code'=>202,'message'=>'WISH平台账号，不存在！']);die;
        }

        $variation_info = WishFanbenVariance::findOne($id);

        if($variation_info == NULL){
            echo json_encode(['code'=>203,'message'=>'商品信息不存在！']);die;
        } else {
            $fanben_info = WishFanben::findOne($variation_info['fanben_id']);

            if(!isset($wish_account_list[$fanben_info['site_id']])){
                echo json_encode(['code'=>202,'message'=>'WISH平台账号，不存在！']);die;
            }

            $wish_account = $wish_account_list[$fanben_info['site_id']];
            $retuslt = WishHelper::changeProductVariationStatus($wish_account,$status,$variation_info['sku']);

            if($retuslt['code'] == 200){
                $variation_info->enable = $status == 1 ? 'N' : 'Y';
                $variation_info->save();
                //查看变种商品还存在下架的商品
                if($status == 1){
                    $variation_infos = WishFanbenVariance::find()->where(['enable' => 'Y','fanben_id'=>$variation_info['fanben_id']]);
                    $count = $variation_infos->count();
                    $fanben_info->is_enable = $count > 0 ? 2 : 3;
                    $fanben_info->save();
                } else {
                    $variation_infos = WishFanbenVariance::find()->where(['enable' => 'N','fanben_id'=>$variation_info['fanben_id']]);
                    $count = $variation_infos->count();
                    if($count > 0){
                        $fanben_info->is_enable = 2;
                    } else {
                        $fanben_info->is_enable = 1;
                    }
                    $fanben_info->save();
                }
            }

            echo json_encode($retuslt);
        }

    }

    public function actionSyncProductInfo(){
        $site_id = Yii::$app->request->post('site','');

        $wish_account_list = WishHelper::getWishAccountList();

        if(empty($site_id) || !isset($wish_account_list[$site_id])){
            echo json_encode(['code'=>203,'message'=>'店铺信息不正确或已不存在！']);die;
        }

        $number = 1;
        $start = 1;
        $total_number = 0;

        while($number == 1){
            $return = WishHelper::syscnProductInfo($wish_account_list[$site_id],$start);
            if($return['code'] == 200){
                $total_number += $return['total'];
                sleep(20);
                $start++;
            } else {
                $number = 0;
                break;
            }
            unset($return);
        }

        echo json_encode(['code'=>200,'message'=>"同步商品数".$total_number]);die;


    }

    public function actionProductSave(){

        $wish_model = Yii::$app->request->post('wish_model','');
        $wish_type = Yii::$app->request->post('wish_type','');
        $content = Yii::$app->request->post('content','');
        $content_rp = Yii::$app->request->post('content_rp','');
        $pid = Yii::$app->request->post('pid',0);
        AppTrackerApiHelper::actionLog("List-wish", "/listing/wish/batch-edit-product");
        if(empty($wish_model) || !isset(self::$wish_type[$wish_model])){
            return ['code' => 201 , "message" => "参数信息不完整" ];die;
        }
        if(empty($wish_type) || !isset(self::$wish_type[$wish_model][$wish_type])){
            return ['code' => 201 , "message" => "参数信息不完整" ];die;
        }
        if(empty($content)){
            return ['code' => 201 , "message" => "参数信息不完整" ];die;
        }
        if(empty($pid)){
            return ['code' => 201 , "message" => "参数信息不完整" ];die;
        }
        $return_info = [];
        $condition = [
            'wish_model'=>$wish_model,
            'wish_type'=>$wish_type,
            'content'=>$content,
            'content_rp'=>$content_rp,
            'pid'=>$pid
        ];
        switch($wish_model){
            case 'title' :
                $return_info = $this->saveProductByTitle($condition);
                break;
            case 'price' :
                $return_info = $this->saveProductVariationByPrice($condition);
                break;
            case 'inventory' :
                $return_info = $this->saveProductVariationByInventory($condition);
                break;
        }

        return json_encode($return_info);die;
    }

    /**
     * 批量修改变种商品库存
     * @param $condition
     * @return array
     */
    public function saveProductVariationByInventory($condition){
        AppTrackerApiHelper::actionLog("List-wish", "/listing/wish/batch-edit-product-inventory");
        $rtn = ['code'=>200,'message'=>'数据保存成功'];

        //获取变种信息
        $variation = WishFanbenVariance::find()->where(['id'=>$condition['pid']])->one();
        if($variation == NULL){
            return ['code' => 202 , "message" => "商品不存在！" ];die;
        }

        //获取商品信息
        $fanben_info = WishFanben::findOne($variation['fanben_id']);
        if( $fanben_info == NULL){
            return ['code' => 202 , "message" => "商品不存在！" ];die;
        }

        $old_variation = $variation;
        $inventory = $condition['content'];
        $inventory_rp = isset($condition['content_rp']) ? $condition['content_rp'] : 0;
        switch($condition['wish_type']){
            case 'add': //加
                $number = $variation->inventory + $inventory;
                $variation->inventory = $number > 10000 ? 9999 : $number;
                break;
            case 'divide': //除
                $number = $inventory <= 0 ? 0 : $variation->inventory / $inventory;
                $variation->inventory = $number > 10000 ? 9999 : $number;
                break;
            case 'minus'://减
                $number = $variation->inventory - $inventory;
                $variation->inventory = $number > 10000 ? 9999 : $number;
                break;
            case 'ride'://乘
                $number = $inventory <= 0 ? 0 : $variation->inventory * $inventory;
                $variation->inventory = $number > 10000 ? 9999 : $number;
                break;
            case 'conditionadd': //按条件加
                $number = $variation->inventory;
                if($number < $inventory){
                    $number = $number + $inventory_rp;
                }
                $variation->inventory = $number > 10000 ? 9999 : $number;
                break;
            case 'conditionmakeup': //按条件补充到
                $number = $variation->inventory < $inventory ? $inventory_rp : $variation->inventory;
                $variation->inventory = $number > 10000 ? 9999 : $number;
                break;
            case 'rp'://替换
                $variation->inventory = $inventory > 10000 ? 9999 : $inventory;
                break;
            default :
                $rtn['code'] = 202;
                $rtn['message'] = '操作类型不正确';
                break;
        }

        //保存数据
        if($rtn['code'] == 200 && !$variation->save()){
            $rtn['code'] = 202;
            $rtn['message'] = $variation->errors;
        } else {
            $wish_account_list = WishHelper::getWishAccountList();
            if (isset($wish_account_list[$fanben_info['site_id']])){
                $rtn = WishHelper::pushUpdateProductVariationInfo($wish_account_list[$fanben_info['site_id']], $variation);
            } else {
                $rtn['code'] = 204;
                $rtn['message'] = 'WISH平台账号，不存在或异常';
            }
        }

        WishHelper::saveLog(__FUNCTION__,var_export($old_variation,true),var_export($variation,true));

        return $rtn;

    }

    /**
     * 批量修改变种的价格
     * @param $condition
     * @return array
     */
    public function saveProductVariationByPrice($condition){
        AppTrackerApiHelper::actionLog("List-wish", "/listing/wish/batch-edit-product-price");
        $rtn = ['code'=>200,'message'=>'数据保存成功'];


        //获取变种信息
        $variation = WishFanbenVariance::find()->where(['id'=>$condition['pid']])->one();
        if($variation == NULL){
            return ['code' => 202 , "message" => "商品不存在！" ];die;
        }

        //获取商品信息
        $fanben_info = WishFanben::findOne($variation['fanben_id']);
        if( $fanben_info == NULL){
            return ['code' => 202 , "message" => "商品不存在！" ];die;
        }

        $old_variation = $variation;
        $price = $condition['content'];
        switch($condition['wish_type']){
            case 'add': //加
                $variation->price = $variation->price + $price;
                break;
            case 'divide': //除
                $number = $price <= 0 ? 0 : $variation->price / $price;
                $variation->price = $number;
                break;
            case 'minus'://减
                $number = $variation->price - $price;
                $variation->price = $number;
                break;
            case 'ride'://乘
                $number = $price <= 0 ? 0 : $variation->price * $price;
                $variation->price = $number;
                break;
            case 'increase': //百分比加
                $number = $variation->price + ($variation->price * $price)/100;
                $variation->price = $number;
                break;
            case 'reduction': //百分比减
                $number = $variation->price - ($variation->price * $price)/100;
                $variation->price = $number;
                break;
            case 'rp'://替换
                $variation->price = $price;
                break;
            default :
                $rtn['code'] = 202;
                $rtn['message'] = '操作类型不正确';
                break;
        }

        //保存数据
        if($rtn['code'] == 200 && !$variation->save()){
            $rtn['code'] = 202;
            $rtn['message'] = $variation->errors;
        } else {
            $wish_account_list = WishHelper::getWishAccountList();
            if (isset($wish_account_list[$fanben_info['site_id']])){
                $rtn = WishHelper::pushUpdateProductVariationInfo($wish_account_list[$fanben_info['site_id']], $variation);
            } else {
                $rtn['code'] = 204;
                $rtn['message'] = 'WISH平台账号，不存在或异常';
            }
        }
        WishHelper::saveLog(__FUNCTION__,json_encode($old_variation),json_encode($variation));

        return $rtn;
    }

    /**
     * 批量修改商品标题
     * @param $condition
     * @return array
     */
    public function saveProductByTitle($condition){
        AppTrackerApiHelper::actionLog("List-wish", "/listing/wish/batch-edit-product-title");
        $rtn = ['code'=>200,'message'=>'数据保存成功'];
        //获取商品信息
        $fanben_info = WishFanben::findOne($condition['pid']);

        if($fanben_info == NULL){
            return ['code' => 202 , "message" => "商品不存在！" ];die;
        }

        $old_fanben_info = $fanben_info;
        switch($condition['wish_type']){
            case 'fadd' : //'在前面添加',
                $fanben_info->name = $condition['content'].$fanben_info->name;
                break;
            case 'badd' : //在后面添加
                $fanben_info->name = $fanben_info->name.$condition['content'];
                break;
            case 'fdel' : //在前面删除
                $name = explode($condition['content'],$fanben_info->name);
                foreach($name as $key => $value){
                    if(strtolower(trim($value)) == strtolower(trim($condition['content']))){
                        unset($name[$key]);
                        break;
                    }
                }
                $fanben_info->name = join('',$name);
                break;
            case 'bdel' : //在后面删除
                $name = explode($condition['content'],$fanben_info->name);
                $last_key = -1;
                //获取最后一个待删除数据
                foreach($name as $key => $value){
                    if(strtolower(trim($value)) == strtolower(trim($condition['content']))){
                        $last_key = $key;
                    }
                }

                if($last_key != -1){
                    unset($name[$last_key]);
                }

                $fanben_info->name = join('',$name);
                break;
            case 'rp' : //替换
                $fanben_info->name = str_replace($condition['content'],$condition['content_rp'],$fanben_info->name);
                break;
            default :
                $rtn['code'] = 202;
                $rtn['message'] = '操作类型不正确';
                break;
        }

        if($rtn['code'] == 200 && !$fanben_info->save()){
            $rtn['code'] = 202;
            $rtn['message'] = $fanben_info->errors;
        } else {
            $wish_account_list = WishHelper::getWishAccountList();
            if (isset($wish_account_list[$fanben_info['site_id']])){
                $rtn = WishHelper::pushUpdateProduct($wish_account_list[$fanben_info['site_id']], $fanben_info);
            } else {
                $rtn['code'] = 204;
                $rtn['message'] = 'WISH平台账号，不存在或异常';
            }
        }

        WishHelper::saveLog(__FUNCTION__,json_encode($old_fanben_info),json_encode($fanben_info));

        return $rtn;
    }


    public function getProductList($enable = 'Y'){

        $site_id = Yii::$app->request->get('site_id',0);
        $search_condition = Yii::$app->request->get('search_condition',3);
        $search_value = Yii::$app->request->get('search_value','');
        $pstatus = Yii::$app->request->get('pstatus',0);
        $sort = Yii::$app->request->get('sort','');
        $perpage = Yii::$app->request->get('per-page','20');
        $product_status = self::$wish_status;
        $WishListCount = WishFanben::find()->where(['type'=>2,'lb_status'=>4])->asArray()->count();     // 刊登失败的数量 ===》左侧菜单中的 count
        $data = [
            'site_id' => $site_id,
            'search_condition_name' => self::$default_search_condition[$search_condition],
            'search_condition' => $search_condition,
            'search_value' => $search_value,
            'default_search_condition' => self::$default_search_condition,
            'wish_model' => self::$wish_model,
            'wish_type' => json_encode(self::$wish_type),
            'product_status' => $product_status,
            'pstatus' => $pstatus,
            'sort' => $sort,
            'sold' => '',
            'saves' => '',
            'wish_account' => [],
            'list' => [],
            'page' => [],
            'WishListCount'=> $WishListCount
        ];

        //WISH平台账户
        $wish_account_list = WishHelper::getWishAccountList();
        foreach($wish_account_list as $key => $val){
            $checkToken = WishHelper::CheckAccountBindingOrNot($val['site_id']); 
            if($checkToken['success'] == false){
                unset($wish_account_list[$key]);
            }else{
                if(empty($site_id)){
                    $site_id = $val['site_id'];
                    continue;
                }
            }
        }

        $data['wish_account']  = $wish_account_list;
        // var_dump($wish_account_list);
        // var_dump($site_id);
        if(!empty($wish_account_list) && isset($wish_account_list[$site_id])) {

            if (isset(self::$default_search_condition[$search_condition])) {
                $condition = [
                    'type' => 1
                ];

                //WISH平台账户
                if (!empty($site_id)) {
                    $condition['site_id'] = $site_id;
                    $data['site_id'] = $site_id;
                }
                //产品状态
                if(!empty($pstatus)){
                    $condition['lb_status'] = $pstatus;
                }

                $data['sort'] = $sort;
                if(empty($sort)){
                    $sort = "create_time DESC";
                } else {
                    $sort = explode('-',$sort);
                    if($sort[0] == 'saves'){
                        $data['sort'] = $data['saves'] = 'saves-'.$sort[1];
                        $sort = $sort[1] == 'up' ? "number_saves ASC" : "number_saves DESC";
                    }else if($sort[0] == 'sold'){
                        $data['sort'] = $data['sold'] = 'sold-'.$sort[1];
                        $sort = $sort[1] == 'up' ? "number_sold ASC" : "number_sold DESC";
                    }else {
                        $sort = "create_time DESC";
                    }
                }

                $fanben_obj = WishFanben::find()->where($condition);
                //添加动态查询条件
                if (isset(self::$sarch_keyword[$search_condition]) && !empty($search_value)) {
                    $fanben_obj->andWhere(["like",self::$sarch_keyword[$search_condition],$search_value]);
                }

                if($enable == 'Y'){
                    $fanben_obj->andWhere(["<",'is_enable',3]);
                } else {
                    $fanben_obj->andWhere([">",'is_enable',1]);
                }
                //分页
                $pagination = new Pagination([
                        'totalCount' =>$fanben_obj->count(),
                        'defaultPageSize' => 20,
                        'pageSize' => $perpage,
                        'pageSizeLimit'=>[5,20,50,100,200],
                    ]);
                $data['page'] = $pagination;

                $fanben_info = $fanben_obj->offset($pagination->offset)->limit($pagination->limit)->orderBy($sort)->asArray()->all();
                //循环商品信息获取变种参数
                $find = ['Ooops! You must specify either a size or a color.'];
                $replace = '';
                foreach ($fanben_info as $key => $fanben) {
                    $total = 0;
                    if(empty($site_id)){
                        $data['site_id'] = isset($wish_account_list[$fanben['site_id']]) ?$wish_account_list[$fanben['site_id']]['site_id']:0;
                    }
                    $variation_infos = WishFanbenVariance::find()->where(['fanben_id' => $fanben['id'],'enable'=>$enable])->orderBy("inventory ASC")->asArray()->all();
                    $fanben_info[$key]['variation'] = empty($variation_infos) ? [] : $variation_infos;
                    $fanben_info[$key]['store_name'] = isset($wish_account_list[$fanben['site_id']]) ? $wish_account_list[$fanben['site_id']]['store_name'] : '';
                    $fanben_info[$key]['status_name'] = isset(self::$wish_status[$fanben['lb_status']])?self::$wish_status[$fanben['lb_status']]:'';
                    $fanben_info[$key]['error_message'] = str_replace($find,$replace,$fanben['error_message']);
                    if(!empty($variation_infos)){
                        foreach($variation_infos as $variation_info){
                            $total += $variation_info['inventory'];
                        }
                    }
                    $fanben_info[$key]['total_inventory'] = $total;

                }
                $data['list'] = $fanben_info;
            }
        }
        return $data;
    }

    /**
     * 同步界面
     * @return [type] [description]
     */
    function actionStartSync(){
        $wish_account = SaasWishUser::find()->where([
            'uid'=>\Yii::$app->user->identity->getParentUid()
        ]);

        return $this->renderAuto('/sync_start',[
            'shops'=>ArrayHelper::map($wish_account->asArray()->all(),'site_id','store_name'),
            'type'=>'checkbox',
            'manual_sync'=>'wish:product',
        ]);

    }
	
	public function getWishProductList($enable = 'Y'){

        $site_id = Yii::$app->request->get('site_id',0);
        $search_condition = Yii::$app->request->get('search_condition',3);
        $search_value = Yii::$app->request->get('search_value','');
        $pstatus = Yii::$app->request->get('pstatus',7);
        $sort = Yii::$app->request->get('sort','');
        $perpage = Yii::$app->request->get('per-page','20');
        $product_status = self::$wish_status;
        $WishListCount = WishFanben::find()->where(['type'=>2,'lb_status'=>4])->asArray()->count();     // 刊登失败的数量 ===》左侧菜单中的 count
        $data = [
            'site_id' => $site_id,
            'search_condition_name' => self::$default_search_condition[$search_condition],
            'search_condition' => $search_condition,
            'search_value' => $search_value,
            'default_search_condition' => self::$default_search_condition,
            'wish_model' => self::$wish_model,
            'wish_type' => json_encode(self::$wish_type),
            'product_status' => $product_status,
            'pstatus' => $pstatus,
            'sort' => $sort,
            'sold' => '',
            'saves' => '',
            'wish_account' => [],
            'list' => [],
            'page' => [],
            'WishListCount'=> $WishListCount,
            'enable' => $enable
        ];

        //WISH平台账户
        $wish_account_list = WishHelper::getWishAccountList();
        foreach($wish_account_list as $key => $val){
            $checkToken = WishHelper::CheckAccountBindingOrNot($val['site_id']); 
            if($checkToken['success'] == false){
                unset($wish_account_list[$key]);
            }else{
                if(empty($site_id)){
                    $site_id = $val['site_id'];
                    continue;
                }
            }
        }

        $data['wish_account']  = $wish_account_list;
        // var_dump($wish_account_list);
        // var_dump($site_id);
        if(!empty($wish_account_list) && isset($wish_account_list[$site_id])) {

            if (isset(self::$default_search_condition[$search_condition])) {
                $condition = [
                    'type' => 1
                ];

                //WISH平台账户
                if (!empty($site_id)) {
                    $condition['site_id'] = $site_id;
                    $data['site_id'] = $site_id;
                }
                //产品状态
                if(!empty($pstatus)){
                    $condition['lb_status'] = $pstatus;
                }

                $data['sort'] = $sort;
                if(empty($sort)){
                    $sort = "create_time DESC";
                } else {
                    $sort = explode('-',$sort);
                    if($sort[0] == 'saves'){
                        $data['sort'] = $data['saves'] = 'saves-'.$sort[1];
                        $sort = $sort[1] == 'up' ? "number_saves ASC" : "number_saves DESC";
                    }else if($sort[0] == 'sold'){
                        $data['sort'] = $data['sold'] = 'sold-'.$sort[1];
                        $sort = $sort[1] == 'up' ? "number_sold ASC" : "number_sold DESC";
                    }else {
                        $sort = "create_time DESC";
                    }
                }

                $fanben_obj = WishFanben::find()->where($condition);
                //添加动态查询条件
                if (isset(self::$sarch_keyword[$search_condition]) && !empty($search_value)) {
                    $fanben_obj->andWhere(["like",self::$sarch_keyword[$search_condition],$search_value]);
                }

                if($enable == 'Y'){
                    $fanben_obj->andWhere(["<",'is_enable',3]);
                } else {
                    $fanben_obj->andWhere([">",'is_enable',1]);
                }
                //分页
                $pagination = new Pagination([
                        'totalCount' =>$fanben_obj->count(),
                        'defaultPageSize' => 20,
                        'pageSize' => $perpage,
                        'pageSizeLimit'=>[5,20,50,100,200],
                    ]);
                $data['page'] = $pagination;

                $fanben_info = $fanben_obj->offset($pagination->offset)->limit($pagination->limit)->orderBy($sort)->asArray()->all();
                //循环商品信息获取变种参数
                $find = ['Ooops! You must specify either a size or a color.'];
                $replace = '';
                foreach ($fanben_info as $key => $fanben) {
                    $total = 0;
                    if(empty($site_id)){
                        $data['site_id'] = isset($wish_account_list[$fanben['site_id']]) ?$wish_account_list[$fanben['site_id']]['site_id']:0;
                    }
                    $variation_infos = WishFanbenVariance::find()->where(['fanben_id' => $fanben['id'],'enable'=>$enable])->orderBy("inventory ASC")->asArray()->all();
                    $fanben_info[$key]['variation'] = empty($variation_infos) ? [] : $variation_infos;
                    $fanben_info[$key]['store_name'] = isset($wish_account_list[$fanben['site_id']]) ? $wish_account_list[$fanben['site_id']]['store_name'] : '';
                    $fanben_info[$key]['status_name'] = isset(self::$wish_status[$fanben['lb_status']])?self::$wish_status[$fanben['lb_status']]:'';
                    $fanben_info[$key]['error_message'] = str_replace($find,$replace,$fanben['error_message']);
                    if(!empty($variation_infos)){
                        foreach($variation_infos as $variation_info){
                            $total += $variation_info['inventory'];
                        }
                        $fanben_info[$key]['total_inventory'] = $total;
                    }else{
                        unset($fanben_info[$key]);
                    }

                }
                $data['list'] = $fanben_info;
            }
        }
        return $data;
    }













}
