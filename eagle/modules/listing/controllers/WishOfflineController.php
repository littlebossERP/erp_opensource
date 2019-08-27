<?php

/**
 *  wish （小老板系统新增Wish商品）刊登管理
 */
namespace eagle\modules\listing\controllers;

use yii;
use yii\data\Pagination;
use yii\data\ActiveDataProvider;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\data\Sort;

use eagle\models\SaasWishUser;
use eagle\modules\listing\models\WishApiQueue;
use eagle\modules\listing\models\WishFanben;
use eagle\modules\listing\models\WishFanbenVariance;
use eagle\modules\listing\models\WishOrder;
use eagle\modules\listing\models\WishOrderDetail;
use eagle\modules\listing\helpers\WishHelper;
use eagle\modules\util\helpers\OperationLogHelper;
use eagle\modules\util\helpers\StandardConst;
use eagle\modules\util\helpers\TranslateHelper;
use eagle\modules\util\helpers\ResultHelper;
use eagle\modules\platform\apihelpers\WishAccountsApiHelper;
use eagle\modules\listing\helpers\WishProxyConnectHelper;
use eagle\modules\listing\helpers\SaasWishFanbenSyncHelper;
use eagle\modules\app\apihelpers\AppTrackerApiHelper;
use eagle\modules\catalog\helpers\ProductApiHelper;
use yii\base\Exception;


class WishOfflineController extends Controller
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

    public function actionWishList(){
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
            $order = 'asc';
        
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
        $uid = \Yii::$app->user->identity->getParentUid();  
        //调用 查询方法
        $WishListingData = WishHelper::getFanbenListData( $queryString, $sort, $order, $pageSize);
        $store_name =  WishHelper::getWishAccountList();
        $WishListCount = WishFanben::find()->where(['type'=>2,'lb_status'=>4])->asArray()->count();
        foreach($WishListingData['data'] as $key => &$val){
            foreach($store_name as $k => $vo){
                if($val['site_id'] == $vo['site_id']){
                    $val['store_name'] = $vo['store_name'];
                }
                if(isset($data['search_key'])){
                    $replace =["<font style='color:#FF9960'>".$data['search_key']."</font>",
                    "<font style='color:red'>".ucfirst($data['search_key'])."</font>",
                    "<font style='color:red'>".strtoupper($data['search_key'])."</font>",
                    ];
                    /*替换搜索匹配的关键字*/
                    $find = [$data['search_key'],ucfirst($data['search_key']),strtoupper($data['search_key'])];
                    if(isset($data['select_status']) && !empty($data['select_status'])){
                        if($data['select_status'] == 'name'){
                            $val['name'] = str_replace($find,$replace,$val['name']);
                        }else{
                            $val['parent_sku'] = str_replace($find,$replace,$val['parent_sku']);
                        }
                    }else{
                            $val['name'] = str_replace($find,$replace,$val['name']);
                            $val['parent_sku'] = str_replace($find,$replace,$val['parent_sku']);
                    }
                }
            }
        }
        $data['type'] = empty($_GET['type'])?2:$_GET['type'];
        $data['lb_status'] =empty($_GET['lb_status'])?1:$_GET['lb_status'];
        $data['WishListingData'] = $WishListingData;
        $data['store'] = $store_name;
        $data['WishListCount'] = $WishListCount;
        if($data['lb_status'] == 1){
            AppTrackerApiHelper::actionLog("List-wish", "/listing/wish/wish-wait"); 
        }else{
            AppTrackerApiHelper::actionLog("List-wish", "/listing/wish/wish-failed"); 
        }
        return $this->render('list',$data); 
    }

    /*
    *商品新增
    *@author victorting
    */
    public function actionFanBenCapture(){
            // $model = WishFanben::findOne(['id' => $_REQUEST['id']]);
            // $varianceData = WishFanbenVariance::find()->andWhere(['fanben_id'=>$_REQUEST['id']])->asArray()->all();
            // $StatusMapping = WishHelper::getAllWishFanBenStatus();  
            // $type = 2;
            // $lb_status =!empty($lb_status)?$_GET['lb_status']:1; //默认待发布状态
            // $sku = 'CB8514110301';
        AppTrackerApiHelper::actionLog("List-wish", "/listing/wish/wish-new");
        $uid = \Yii::$app->user->identity->getParentUid();
        $store_name = WishAccountsApiHelper::ListAccounts($uid);
        $data['store_name'] = $store_name;
        $data['lb_status'] =1;
        return $this->render('edit' , $data);
    }

    /*
    * 小老板刊登商品编辑
    * @author victorting
    */
    public function actionFanBenEdit(){
        $type = empty($_GET['type'])?2:$_GET['type'];
        $lb_status =empty($_GET['lb_status'])?1:$_GET['lb_status']; //默认待发布状态
        if($lb_status == 1){
            AppTrackerApiHelper::actionLog("List-wish", "/listing/wish/fan-ben-wait-edit");
        }else{
            AppTrackerApiHelper::actionLog("List-wish", "/listing/wish/fan-ben-failed-edit");
        }
        $fanben_id = $_GET['id'];
        $uid = \Yii::$app->user->identity->getParentUid();
        $store_name = WishAccountsApiHelper::ListAccounts($uid);
        $model = WishFanben::find()->where(['id'=>$fanben_id,'type'=>$type,'lb_status'=>$lb_status])->one();
        $varianceData = WishFanbenVariance::find()->andWhere(['parent_sku'=>$model['parent_sku']])->asArray()->all();
        return $this->render('edit',['WishFanbenModel'=>$model , 
            'WishFanbenVarianceData'=>$varianceData,
            'store_name'=>$store_name,
            'lb_status'=>$lb_status
        ]);
    }

    /*
    *商品复制
    *@author victorting
    */
    public function actionCopyFanBen(){
        AppTrackerApiHelper::actionLog("List-wish", "/listing/wish/copy-fan-ben");
        $type =$_GET['type'];
        $lb_status= $_GET['lb_status'];
        $fanben_id = empty($_GET['id'])?"":$_GET['id'];
        $uid = \Yii::$app->user->identity->getParentUid();
        $store_name = WishAccountsApiHelper::ListAccounts($uid);
        if($fanben_id){
            $WishFanbenModel = WishFanben::find()->where(['id'=>$fanben_id])->one();
            $varianceData = WishFanbenVariance::find()->andwhere(['parent_sku'=>$WishFanbenModel['parent_sku']])->asArray()->all();
            unset($WishFanbenModel['id']);
            foreach($varianceData as $key => &$val){
                unset($val['fanben_id']);
                unset($val['id']);
            }
        }
        return $this->render('edit',['WishFanbenModel'=>$WishFanbenModel,
            'WishFanbenVarianceData'=>$varianceData,
            'lb_status'=>$lb_status,
            'store_name'=>$store_name
            ]);

    }


    /*
    * 保存范本
    */
    public function actionSaveFanBen(){
        $fanben = $_POST;
        $fanben['status'] = 'online';
        $site_id = $fanben['site_id'];
        if(isset($fanben['size'])){
            $fanben['addinfo']  = json_encode(['size'=>$fanben['size']]);
        }
        $is_post = Yii::$app->request->get('is_post');
        if($is_post == 1){
            AppTrackerApiHelper::actionLog("List-wish", "/listing/wish/save-fan-ben");
        }else{
            AppTrackerApiHelper::actionLog("List-wish", "/listing/wish/post-fan-ben");
        }
        // unset($fanben['size']);
        // unset($fanben['is_post']);
        // $data = WishHelper::saveFanBen($fanben);
        try{
            $result = WishHelper::WishFanbenSave($fanben);
        }catch(\Exception $e){
            $result = [
                'success'=>false,
                'message'=>$e->getMessage()
            ];
        };

        var_dump($result);die;


        if($is_post == 2 && $data['success'] =='true'){
            if(isset($fanben['fanben_id']) && !empty($fanben['fanben_id'])){
                $id = $fanben['fanben_id'];
            }else{
                $id = $data['fanben_id'];
            }
            $checkToken = WishHelper::CheckAccountBindingOrNot($site_id); 
            if($checkToken['success'] == true){
                $data = WishHelper::pushFanBen($id);
                $model = WishFanben::findOne($id);
                if($data['success'] == true){
                    $model->lb_status = 2;
                    $model->type = 1;
                    $model->save();
                    WishHelper::SyncFanbenStatus($site_id);
                }else{
                    $model->lb_status = 4;
                    $model->save();
                }
            }else{
                $data['success'] = false;
                $data['message'] = '发布失败,店铺site_id为'.$site_id.'的token失效,请重新绑定';
            }
        }
        echo json_encode($data);        
    }


    public function actionAjaxTags(){
        $keyword = Yii::$app->request->post('q');
        $str = WishHelper::getTagInfo($keyword);
        $newstr = [];
        if($str)
        foreach($str['proxyResponse']['data']['tags'] as $key=>$val){
            $newstr[$key] = $val['tag'];
        }
        echo json_encode($newstr);
    }

    public function actionAjaxSkuExist(){
        $parent_sku = Yii::$app->request->post('parent_sku');
        $fanben_id = Yii::$app->request->post('fanben_id');
        $where = ['parent_sku'=> $parent_sku];
        if(!empty($fanben_id)){
            $where = ['and',['<>','id',$fanben_id],"parent_sku='{$parent_sku}'"];
        }       
        $num = WishFanben::find()->where($where)->asArray()->count();
        if($num > 0){
            $data['success'] = false;
            $data['message'] = '该主SKU已存在，请勿重复！';
        }else{
            $data['success'] = true;
        }
        echo json_encode($data);

    }



    public function actionDelFanBen(){
        $lb_status = Yii::$app->request->get('lb_status');
        if($lb_status == 1){
            AppTrackerApiHelper::actionLog("List-wish", "/listing/wish/del-fan-ben-wait");
        }else{
            AppTrackerApiHelper::actionLog("List-wish", "/listing/wish/del-fan-ben-failed");
        }
        $id = Yii::$app->request->post('id');
        $model = WishHelper::delFanben($id);
        echo json_encode($model);
    }

    public function actionBatchDelFanBen(){
        $lb_status = Yii::$app->request->get('lb_status');
        if($lb_status == 1){
            AppTrackerApiHelper::actionLog("List-wish", "/listing/wish/batch-del-fan-ben-wait");
        }else{
            AppTrackerApiHelper::actionLog("List-wish", "/listing/wish/batch-del-ben-failed");
        }
        $ids = $_POST['id'];
        $delList = explode(',',$ids);
        for($i=0;$i<count($delList);$i++){
            $model= WishHelper::delFanben($delList[$i]);
        }
        echo json_encode($model);
    }


    public function actionSendFanBen(){

        $lb_status = Yii::$app->request->get('lb_status');
        $site_id = Yii::$app->request->get('site_id');
        if($lb_status == 1){
            AppTrackerApiHelper::actionLog("List-wish", "/listing/wish/post-fan-ben-wait");
        }else{
            AppTrackerApiHelper::actionLog("List-wish", "/listing/wish/post-fan-ben-failed");
        }
        $id = Yii::$app->request->get('id');
        $checkToken = WishHelper::CheckAccountBindingOrNot($site_id); 
        if(isset($id)){
            if($checkToken['success'] == true){
                $data = WishHelper::pushFanBen($id);
                if($data['success']=='true'){
                    $fanben = WishFanben::findOne($id);
                    $fanben->lb_status = 2;
                    $fanben->type = 1;
                    $fanben->save();
                    WishHelper::SyncFanbenStatus($site_id);
                }
            }else{
                $data['success'] = false;
                $data['message'] = '发布失败,店铺site_id为'.$site_id.'的token失效,请重新绑定';
            }
        }
        echo json_encode($data);
    }

    public function actionBatchPostFanBen(){
        $lb_status = Yii::$app->request->get('lb_status');
        if($lb_status == 1){
            AppTrackerApiHelper::actionLog("List-wish", "/listing/wish/batch-post-fan-ben-wait");
        }else{
            AppTrackerApiHelper::actionLog("List-wish", "/listing/wish/batch-post-fan-ben-failed");
        }
        $ids = Yii::$app->request->get('id');
        $site_id = Yii::$app->request->get('site_id');
        $postList = explode(',',$ids);
        $site_id_list = explode(',',$site_id);
        $model = [];
        $NotBindingSiteList =[];
        $RepSite = [];
        $AlreadyPost = [];
        for($i=0;$i<count($site_id_list);$i++){
            if(!in_array($site_id_list[$i],$RepSite)){
                $checkToken = WishHelper::CheckAccountBindingOrNot($site_id_list[$i]);
                array_push($RepSite,$site_id_list[$i]);
                if($checkToken['success'] == false){
                    array_push($NotBindingSiteList,$site_id_list[$i]);
                }else{
                    $model = WishHelper::pushFanben($postList[$i]);
                    if($model['success'] == 'true'){
                        $fanben = WishFanben::findOne($postList[$i]);
                        $fanben->type = '1';
                        $fanben->lb_status = '2';
                        $fanben->save();
                        WishHelper::SyncFanbenStatus($site_id_list[$i]);
                        array_push($AlreadyPost,$postList[$i]);
                    }
                }
            }else if(!in_array($site_id_list[$i],$NotBindingSiteList)){
                $model = WishHelper::pushFanben($postList[$i]);
                if($model['success'] == 'true'){
                    $fanben = WishFanben::findOne($postList[$i]);
                    $fanben->type = '1';
                    $fanben->lb_status = '2';
                    $fanben->save();
                    WishHelper::SyncFanbenStatus($site_id_list[$i]);
                    array_push($AlreadyPost,$postList[$i]);
                }
            }
        }
        if(!empty($NotBindingSiteList)){
            $model['posted_product_list'] = join(',',$AlreadyPost);
            $model['success'] = false;
            $model['message'] = '店铺site_id为'.join(',',$NotBindingSiteList).'的token失效,请重新绑定后再发布此店铺的商品';
            
        }else{
            $model['success'] = true;
            $model['posted_product_list'] = join(',',$AlreadyPost);
        }   
        echo json_encode($model);
    }

    public function actionOfflineFanBenEdit(){
        AppTrackerApiHelper::actionLog("List-wish", "/listing/wish/offline-fan-ben-edit");
        $type = 2;
        $fanben_id = Yii::$app->request->get('id');
        $uid = \Yii::$app->user->identity->getParentUid();
        $store_name = WishAccountsApiHelper::ListAccounts($uid);
        $model = WishFanben::find()->where(['id'=>$fanben_id])->one();
        $varianceData = WishFanbenVariance::find()->andWhere(['parent_sku'=>$model['parent_sku']])->asArray()->all();
        return $this->render('online-edit',['WishFanbenModel'=>$model , 
            'WishFanbenVarianceData'=>$varianceData,
            'store_name'=>$store_name,
            'type'=>$type
        ]);
    }

    public function actionOnlineFanBenEdit(){
        AppTrackerApiHelper::actionLog("List-wish", "/listing/wish/online-fan-ben-edit");
        $type =1;
        $fanben_id = Yii::$app->request->get('id');
        $uid = \Yii::$app->user->identity->getParentUid();
        $store_name = WishAccountsApiHelper::ListAccounts($uid);
        $model = WishFanben::find()->where(['id'=>$fanben_id])->one();
        $varianceData = WishFanbenVariance::find()->andWhere(['parent_sku'=>$model['parent_sku']])->asArray()->all();
        $WishListCount = WishFanben::find()->where(['type'=>2,'lb_status'=>4])->asArray()->count();
        return $this->render('online-edit',['WishFanbenModel'=>$model , 
            'WishFanbenVarianceData'=>$varianceData,
            'store_name'=>$store_name,
            'type'=>$type,
            'WishListCount'=>$WishListCount
        ]);
    }

    public function actionOnlineSaveFanBen(){
        AppTrackerApiHelper::actionLog("List-wish", "/listing/wish/online-fan-ben-save");
        $fanben = $_GET;
        $data = WishHelper::saveFanBen($fanben);
        if(isset($fanben['fanben_id']) && !empty($fanben['fanben_id'])){
            $id = $fanben['fanben_id'];
        }else{
            $id = $data['fanben_id'];
        }
        if($data['success'] == true){
            $data = WishHelper::pushFanben($id);
        }
        echo json_encode($data);

    }

    public function actionCiteFanBen(){
        $queryString['site_id']=Yii::$app->request->get('site_id');
        $select_status = Yii::$app->request->get('select_status');
        $search_key = Yii::$app->request->get('search_key');
        $site_id = Yii::$app->request->get('site_id');
        $queryString['type'] = 1;
        AppTrackerApiHelper::actionLog("List-wish", "/listing/wish/cite-fan-ben");
        if(!empty($select_status)){
            if(!empty($search_key)){
                $queryString[$select_status]= $search_key;
                $data['search_key'] = Yii::$app->request->get('search_key');
            }
        }else{
            if(!empty($search_key)){
                $queryString['keyword'] = $search_key;
                $data['search_key'] = $search_key;
            }
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
            $order = 'asc';

        if (! empty ( $_GET ['per-page'] )) {
            if (is_numeric ( $_GET ['per-page'] )) {
                $pageSize = $_GET ['per-page'];
            } else {
                $pageSize = 10;
            }
        } else {
            $pageSize = 10;
        }
        if(isset($select_status)){
            $data['select_status'] = $select_status;
        }

        $data['site_id'] = $site_id;
        $WishListingData = WishHelper::getFanbenListData( $queryString, $sort, $order, $pageSize);
        foreach($WishListingData['data'] as $key => &$val){
            if(isset($data['search_key'])){
                $replace =["<font style='color:#FF9960'>".$data['search_key']."</font>",
                "<font style='color:red'>".ucfirst($data['search_key'])."</font>",
                "<font style='color:red'>".strtoupper($data['search_key'])."</font>",
                ];
                /*替换搜索匹配的关键字*/
                $find = [$data['search_key'],ucfirst($data['search_key']),strtoupper($data['search_key'])];
                if(isset($data['select_status']) && !empty($data['select_status'])){
                    if($data['select_status'] == 'name'){
                        $val['name'] = str_replace($find,$replace,$val['name']);
                    }else{
                        $val['parent_sku'] = str_replace($find,$replace,$val['parent_sku']);
                    }
                }else{
                        $val['name'] = str_replace($find,$replace,$val['name']);
                        $val['parent_sku'] = str_replace($find,$replace,$val['parent_sku']);
                }
            }
        }

        $data['WishFanbenModel'] = $WishListingData;
        echo $this->renderAjax('cite.php',$data);
        // var_dump($WishListingData);
        // $where = ['and','site_id = '.$site_id , $select_status.' like "%'.$search_key.'%"'];
        // $WishFanbenModel = WishFanben::find()->where($where)->orderby('create_time desc')->asArray();
        // $pagination = new Pagination([
        //  'pageSize' => $,
        //  'totalCount' => $query->where($filterStr)->count(),
        // ]);
        // $WishFanbenModel = $WishFanbenModel->all();
        // $data['data'] =$WishFanbenModel; 
        // echo json_encode($data);
    }

    public function actionCiteAFanBen(){
        AppTrackerApiHelper::actionLog("List-wish", "/listing/wish/confirm-cite-fan-ben");
        $id =$_POST['id'];
        $WishFanbenModel = WishFanben::find()->where(['id'=>$id])->asArray()->one();
        $WishFanbenVariance = WishFanbenVariance::find()->where(['fanben_id'=>$WishFanbenModel['id']])->asArray()->all();   
        $data['fanben'] = $WishFanbenModel;
        $data['fanben']['name'] = addslashes($data['fanben']['name']);
        $data['variance'] = $WishFanbenVariance;
        echo json_encode($data);
    }

    public function actionSyncAllWishProduct(){
        $query = SaasWishUser::findBySql("select distinct(uid) from saas_wish_user where is_active = 1")->asArray()->all();
        // $query = SaasWishUser::model()->findAll(['select'=>['distinct uid','store_name'],'order'=>'uid asc']);
        $count = 0;
        $str = '';  
        echo "==============================</br>";
        foreach($query as $val){
            $count = $count +1;
            $str = "uid:".$val['uid']."</br>";
            echo $str;
            $result = SaasWishFanbenSyncHelper::addSyncProductQueue($val['uid']);
        }
        echo "==============================</br>";
        $str = '同步wish用户数:'.$count;
        echo $str;
    }   

    public function actionAddSyncFanbenQueue(){
        $puid = \Yii::$app->subdb->getCurrentPuid();
        $result = SaasWishFanbenSyncHelper::addSyncProductQueue($puid);
        exit(json_encode($result));
    }//end of actionAddSyncFanbenQueue

    public function actionTestSyncProduct(){
        $result = WishHelper::autoSyncFanbenInfo();
    }
}