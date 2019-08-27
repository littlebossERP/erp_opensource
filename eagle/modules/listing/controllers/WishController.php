<?php namespace eagle\modules\listing\controllers;


use yii;
use yii\data\Pagination;
use yii\data\ActiveDataProvider;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\data\Sort;
use eagle\models\SaasWishUser;
use eagle\models\UserBase;
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
use eagle\modules\listing\helpers\WishProxyConnectHelper;
use eagle\modules\listing\helpers\SaasWishFanbenSyncHelper;
use eagle\modules\platform\apihelpers\WishAccountsApiHelper;
use eagle\modules\catalog\helpers\ProductApiHelper;
use eagle\modules\app\apihelpers\AppTrackerApiHelper;
use yii\base\Exception;
use eagle\widgets\ESort;

class WishController extends Controller
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
		'verbs' => [
		'class' => VerbFilter::className(),
		'actions' => [
		'delete' => ['post'],
		],
		],
		];
	}

	
	
	public function actionYs(){
		$rtn =  WishHelper::getFanbenListData();
		echo "Ys done, got result:".print_r($rtn,true);
	}	
	
	
    /**
	 +---------------------------------------------------------------------------------------------
	 * After opening a capture form for fanben info, Load the fanben detail to prefill it
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param
	 +---------------------------------------------------------------------------------------------
	 * @return
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		yzq		2014/01/30				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	public function actionCapturePrefill_data(){
		if (isset($_REQUEST['id']))
			exit( json_encode(WishHelper::getfanbenDetailData($_REQUEST['id'])) );
		else
			exit( json_encode("Nothing passed to Host") );
	}	
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * To open a capture screen for use to capture a Purchase Stock In record
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param
	 +---------------------------------------------------------------------------------------------
	 * @return
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		yzq		2014/01/30				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
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
	}//end of action
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * To recieve the form and CREATE the Warehouse record / items
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param
	 +---------------------------------------------------------------------------------------------
	 * @return
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		yzq		2014/01/30				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	public function actionCreateFanBen(){
		if(!empty($_POST)) {
			//SwiftFormat::arrayTrim($_POST);
			$rtn = WishHelper::saveFanBen($_POST) ;
			
			exit( json_encode($rtn) );
		}else
			exit(json_encode("Nothing Posted to Server") );
	
	}//end of action
	
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * To recieve the modify items info , then sync to wish 
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param
	 +---------------------------------------------------------------------------------------------
	 * @return
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2014/12/25				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	public function actionBatchSaveFanBen(){
		if(!empty($_POST)) {
			$rtn = array('success'=>true , 'message'=>'修改成功！稍候会自动同步数据到wish平台');
			if (!empty($_POST['itemlist'])){
				$itemlist = json_decode($_POST['itemlist'],true);
				foreach($itemlist as $anItem){
					//由于 在线商品的存在所以不再使用posting的 为同步的标准 
					//$anItem['status'] = 'posting'; //so that when saved, it will post to wish platform 
					$result[] = WishHelper::saveFanBen($anItem) ;//2nd param is for Variance only = true
				}
			}else{
				exit(json_encode("None of Modify item !") );
			}
			
			//check up result 
			foreach ($result as $row){
				if (empty($row['success'])){
					$rtn['success'] = false;
					if (!empty($row['message'])){
						$rtn['message'] .= $row['message'];
					}
				}
			}//end of check up result
			
			exit( json_encode($rtn) );
		}else
			exit(json_encode("Nothing Posted to Server") );
	
	}//end of actionBatchSaveFanBen
	
	/**
	 * 删除范本
	 * Parameter 1: keys， 逗号隔开多个 范本id
	 * @author YZQ 2014-11-24
	 */
	function actionDeleteFanBen(){
		$data = null;
		if(empty($_POST['keys'])) {
			exit(ResultHelper::getMissingParameter($data));
		}
		/*
		$uid = $user['puid'] == 0 ? $user['uid'] : $user['puid'];
		$criteria = new CDbCriteria;
		$criteria->addCondition('uid = '.$uid);
		$criteria->addCondition("status = 'pending'");
		$criteria->addInCondition('fanben_order_id', explode(',',$_POST['keys']));
		*/
		$user = Yii::$app->user->identity->getUsername();
		//$uid = $user['puid'] == 0 ? $user['uid'] : $user['puid'];
		
		//获取当前 用户的puid
		$puid =  \Yii::$app->subdb->getCurrentPuid();
		$uid = $puid ==0 ? \Yii::$app->user->id :$puid;
		
		$count = WishApiQueue::find()
		->andwhere(' uid = '.$uid)
		->andwhere(" status = 'pending'")
		->andwhere(['fanben_order_id'=>explode(',',$_POST['keys'])])
		->count();
		if ($count>0) {
			exit(ResultHelper::getResult(400,  '', '不能删除正在队列等待刊登的刊登范本，请先进行取消刊登！'));
		}
		//订单mubanid集合（用逗号分隔）
		$data = intval(WishFanben::deleteAll(['id'=>explode(',',$_POST['keys'])]));
		
		exit( ResultHelper::getSuccess($data));
	}

	/**
	 * 开始刊登范本
	 * Parameter 1: keys， 逗号隔开多个 范本id
	 * @author YZQ 2014-11-24
	 */	
	function actionPostFanBen(){
		$data = null;
		if(empty($_POST['keys'])) {
			exit(ResultHelper::getMissingParameter($data));
		}
		$user = Yii::$app->user->identity->getUsername();
		//$uid = $user['puid'] == 0 ? $user['uid'] : $user['puid'];
		
		//获取当前 用户的puid
		$puid =  \Yii::$app->subdb->getCurrentPuid();
		$uid = $puid ==0 ? \Yii::$app->user->id :$puid;
		
		$fanben_ids = explode(',',$_POST['keys']);

		foreach ($fanben_ids as $fanben_id){
			//$rtn = WishHelper::appendAddItemToQueue($fanben_id);
			$FanBenModel = WishFanben::findone( $fanben_id );
			//if status is editing, change them to posting.
			if ($FanBenModel->status=='editing'){
				$FanBenModel->status = 'posting';
				$FanBenModel->save();
			}
			WishHelper::postFanBen($fanben_id);
		}
		
		$rtn['success'] = true;
		
		if ($rtn['success']){
			exit( ResultHelper::getSuccess($data));
		}else{
			exit(ResultHelper::getFailed($data,1,$rtn['message']));
		}
		
	}
	 
	/**
	 * 取消刊登范本
	 * Parameter 1: keys， 逗号隔开多个 范本id
	 * @author YZQ 2014-11-24
	 */
	function actionCancelFanBen(){
		$data = null;
		if(empty($_POST['keys'])) {
			exit(ResultHelper::getMissingParameter($data));
		}
		
		
		$fanben_ids = explode(',',$_POST['keys']);
		
		foreach ($fanben_ids as $fanben_id){
			$rtn = WishHelper::cancelWishAPIQueue($fanben_id);
		}
	
		if ($rtn['success']){
			//取消成功, 则将东西的状态改为编辑中
			WishFanben::updateAll(['status'=>'editing'],['id'=>$fanben_ids ,'status'=>'posting']);
			exit( ResultHelper::getSuccess($data));
		}else{
			exit(ResultHelper::getFailed($data,1,$rtn['message']));
		}
				
	}
	

	/**
	* Wish刊登首页
	* @author victorting
	*/
	

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
			$order = 'desc';
		
		if (! empty ( $_GET ['per-page'] )) {
			if (is_numeric ( $_GET ['per-page'] )) {
				$pageSize = $_GET ['per-page'];
			} else {
				$pageSize = 50;
			}
		} else {
			$pageSize = 50;
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
			if(!isset($val['store_name'])){
				unset($WishListingData['data'][$key]);
			}
		}

		$data['type'] = empty($_GET['type'])?2:$_GET['type'];
		$data['lb_status'] =empty($_GET['lb_status'])?1:$_GET['lb_status'];
		$data['WishListingData'] = $WishListingData;
		$data['store'] = $store_name;
		$data['WishListCount'] = $WishListCount;
		if($data['type']==3){
			$data['active'] = '发布中';
		}
		if($data['lb_status'] == 1){
			AppTrackerApiHelper::actionLog("List-wish", "/listing/wish/wish-wait"); 
		}else{
			AppTrackerApiHelper::actionLog("List-wish", "/listing/wish/wish-failed"); 
		}

		$data['menu'] = WishHelper::getMenu();

		return $this->render('list',$data);	
	}


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



	/**
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


	/**
	 * 保存范本 
	 * 保存并发布不走队列
	 * @author huaqingfeng
	 * @version 2014-4-8
	 */
	public function actionSaveFanBen(){
		$fanben = $_POST;
		// var_dump($fanben);die;
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
		try{
		    $product = WishHelper::WishFanbenSave($fanben);
		    if($is_post == 2){ 			// 发布
			    $product->push();
		    }
		    $result = [
		    	'success'=>true,
		    	'message'=>'OK',
		    	'fanben_id'=>$product->id
		    ];
		}catch(\Exception $e){
		    $result = [
		        'success'=>false,
		        'message'=>$e->getMessage(),
		        'file'=>$e->getFile(),
		        'line'=>$e->getLine(),
		        'code'=>$e->getCode(),
		        'postData'=>$fanben
		    ];
		};
		echo json_encode($result);		
	}

	/**
	 * 在线编辑
	 * 发布不走队列，直接发布
	 * @author huaqingfeng 2016-04-08
	 * @return [type] [description]
	 */
	public function actionOnlineSaveFanBen(){
		AppTrackerApiHelper::actionLog("List-wish", "/listing/wish/online-fan-ben-save");
		$fanben = $_POST;
		try{
			$product = WishHelper::WishFanbenSave($fanben);
			$product->push();
			$result = [
				'success'=>true,
				'message'=>'OK',
				'fanben_id'=>$product->id
			];
		}catch(\Exception $e){
			$result = [
		        'success'=>false,
		        'message'=>$e->getMessage(),
		        'file'=>$e->getFile(),
		        'line'=>$e->getLine(),
		        'code'=>$e->getCode()
    		];
		}
		echo json_encode($result);	

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
		$site_id = Yii::$app->request->post('site_id');
		$where = ['parent_sku'=> $parent_sku];
		if(!empty($fanben_id)){
			$where = ['and',['<>','id',$fanben_id],"parent_sku='{$parent_sku}'"];
		}		
		$num = WishFanben::find()->where($where)->asArray()->count();
		$wishcheckSku =new \eagle\modules\listing\service\wish\Product($site_id);
		$message = $wishcheckSku->checkExistBySkuOnPlatform($parent_sku);
		if($message == true || $num > 0){
			$data['success'] = false;
			$data['message'] = 'SKU:'.$parent_sku.'重复，请重新填写;';
		}else{
			$data['success'] = true;
		}
		echo json_encode($data);
	}

	public function actionAjaxVarianceSkuExist(){
		$variance_sku = Yii::$app->request->post('variance_sku');
		$site_id = Yii::$app->request->post('site_id');
		$fanben_id= Yii::$app->request->post('fanben_id');
		// $ExistVarianceListInWish = [];
		// $ExistVarianceListInOurPlatform = [];
		// $variance_sku_list = explode(',',$variance_sku);
		// $repVarianceList = [];
		// $AlreadyCheckVarianceList = [];
		// $repVarianceListMessage= '';
		// $checkVarianceSkuExist = new \eagle\modules\listing\service\wish\Product($site_id);
		// for($i=0;$i<count($variance_sku_list);$i++){
		// 	if(!in_array($variance_sku_list[$i], $AlreadyCheckVarianceList)){
		// 		array_push($AlreadyCheckVarianceList,$variance_sku_list[$i]);
		// 		$message = $checkVarianceSkuExist->checkVariantExistBySkuOnPlatform($variance_sku_list[$i]);
		// 		if($message == true){
		// 			array_push($ExistVarianceListInWish,$variance_sku_list[$i]);
		// 		}
		// 		$where = ["sku"=> $variance_sku_list[$i]];
		// 		if(!empty($fanben_id)){
		// 			$where = ['and',['<>','fanben_id',$fanben_id],['or',"sku='{$variance_sku_list[$i]}'","parent_sku='{$variance_sku_list[$i]}'"]];
		// 		}
		// 		$num = WishFanbenVariance::find()->where($where)->asArray()->count();
		// 		if($num > 0){
		// 			array_push($ExistVarianceListInOurPlatform,$variance_sku_list[$i]);
		// 		}

		// 	}else{
		// 		array_push($repVarianceList,$variance_sku_list[$i]);
		// 		$repVarianceListMessage .=$variance_sku_list[$i].',';
		// 	}
		// }
		$where = ['and',['<>','fanben_id',$fanben_id],['or',"sku='{$variance_sku}'","parent_sku='{$variance_sku}'"]];
		$num = WishFanbenVariance::find()->where($where)->asArray()->count();
		$message = '';
		if(empty($fanben_id)){
			$checkVarianceSkuExist = new \eagle\modules\listing\service\wish\Product($site_id);
			$message = $checkVarianceSkuExist->checkVariantExistBySkuOnPlatform($variance_sku);
		}
		$data['success'] = true;
		// $data['message'] = '';
		if($message== true|| $num > 0){
			$data['message']  = '变种SKU:'.$variance_sku.'重复，请重新填写;';
			$data['success'] = false;
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


	/**
	 * 发布商品
	 * 走队列
	 * @version 2016-04-08
	 * @return [type] [description]
	 */
	public function actionSendFanBen(){

		$lb_status = Yii::$app->request->get('lb_status');
		$site_id = Yii::$app->request->get('site_id');
		if($lb_status == 1){
			AppTrackerApiHelper::actionLog("List-wish", "/listing/wish/post-fan-ben-wait");
		}else{
			AppTrackerApiHelper::actionLog("List-wish", "/listing/wish/post-fan-ben-failed");
		}
		$id = Yii::$app->request->get('id');

		// $product = WishFanben::findOne($id);
		try{
			// 加入发送队列
			$queue = WishHelper::addProductsPushQueue($site_id,[$id]);
			$result = [
				'success'=>true,
				'log_id'=>$queue
			];
		}catch(\Exception $e){
			$result = [
				'success'=>false,
				'message'=>$e->getMessage(),
		        'file'=>$e->getFile(),
		        'line'=>$e->getLine()
			];
		}
		echo json_encode($result);
	}

	/**
	 * 批量发布
	 * @author hqf
	 * @version 2016-05-03 重构此方法
	 * @return [type] [description]
	 */
	public function actionBatchPostFanBen(){
		$lb_status = Yii::$app->request->get('lb_status');
		if($lb_status == 1){
			AppTrackerApiHelper::actionLog("List-wish", "/listing/wish/batch-post-fan-ben-wait");
		}else{
			AppTrackerApiHelper::actionLog("List-wish", "/listing/wish/batch-post-fan-ben-failed");
		}

		$keys = explode(',',Yii::$app->request->get('site_id'));
		$vals = explode(',',Yii::$app->request->get('id'));
		$sites = [];
		foreach($keys as $idx => $site_id){
			if(!isset($sites[$site_id])){
				$sites[$site_id] = [];
			}
			$sites[$site_id][] = $vals[$idx];
		}
		foreach($sites as $site_id=>$ids){
			try{
				// 加入发送队列
				$queue = WishHelper::addProductsPushQueue($site_id,$ids);
				$result = [
					'success'=>true,
					'log_id'=>$queue
				];
			}catch(\Exception $e){
			}
		}
		echo json_encode([
			'success'=>true,
			'posted_product_list'=>Yii::$app->request->get('id')
		]);
		return;


		// return;
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
					}else{
						$fanben = WishFanben::findOne($postList[$i]);
						$fanben->type = '2';
						$fanben->error_message = $model['message'];
						$fanben->lb_status = '4';
						$fanben->save();
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
				}else{
					$fanben = WishFanben::findOne($postList[$i]);
					$fanben->type = '2';
					$fanben->lb_status = '4';
					$fanben->error_message = $model['message'];
					$fanben->save();
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
		$WishListCount = WishFanben::find()->where(['type'=>2,'lb_status'=>4])->asArray()->count();
		$varianceData = WishFanbenVariance::find()->andWhere(['parent_sku'=>$model['parent_sku']])->asArray()->all();
		return $this->render('online-edit',['WishFanbenModel'=>$model , 
			'WishFanbenVarianceData'=>$varianceData,
			'store_name'=>$store_name,
			'type'=>$type,
			'WishListCount' =>$WishListCount
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
		// 	'pageSize' => $,
		// 	'totalCount' => $query->where($filterStr)->count(),
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




	/**
	 * 在线Item的数据抓取
	 * @author fanjs
	 */
public function actionFanBenDataList() {
 
	$queryString = array();
	if(!empty($_GET['fanben_id']))
	{
		$queryString['fanben_id'] = $_GET['fanben_id'];
	}
	if(!empty($_GET['txt_search']))
		$queryString['keyword'] = $_GET['txt_search'];
	
	if(!empty($_GET['date_from'])){
		$queryString['date_from'] = $_GET['date_from'];
		$date_from = $_GET['date_from'];
	}else{
		$date_from = "";
	}
	
	if(!empty($_GET['date_to'])){
		$queryString['date_to'] = $_GET['date_to'];
		$date_to  = $_GET['date_to'];
	}else{
		$date_to = "";
	}
	
	if (!empty($_GET['select_status']) && $_GET['select_status'] != 'all') {
		$queryString['status'] = $_GET['select_status'];;
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
			$pageSize = 50;
		}
	} else {
		$pageSize = 50;
	}
	
	//$puid1 = \Yii::$app->subdb->getCurrentPuid();
	//$message = "UATL:用户 $puid1 查询wish listing，条件: $keyword,$date_from,$date_to";
	//\Yii::info(['Tracking',__CLASS__,__FUNCTION__,'Online',$message],"edb\global");
	
	//调用 查询方法
	$WishListingData = WishHelper::getFanbenListData( $queryString, $sort, $order, $pageSize);
	// var_dump($WishListingData);
	// var_dump($WishListingData['data'][0]['variance_data']);
	// exit;
	$StatusMapping = WishHelper::getAllWishFanBenStatus();
	$uid = \Yii::$app->user->id;
	$tmpSql = "select max(last_product_success_retrieve_time) from saas_wish_user where uid='$uid' and is_active=1 ";
	$command = \Yii::$app->db->createCommand($tmpSql);
	$last_product_success_retrieve_time = $command->queryScalar();
	
	$addi_params = [
		'last_product_success_retrieve_time'=>$last_product_success_retrieve_time,
	];
	
	//调用 view
	return $this->render('list_wish_listing', [
			'WishListingData' => $WishListingData,
			'StatusMapping'=>$StatusMapping,
			'addi_params'=>$addi_params,
			]);
	/*
		//当前页码
		$page = 1;
		//每页数据行数
		$rows = 50;
		//排序字段
		$sort = 'id';
		//排序方式
		$order = 'desc';
		$_POST = array_filter($_POST);
		//queryString
		$queryString = array();
		if(!empty($_POST['fanben_id']))
		{
			$queryString['fanben_id'] = $_POST['fanben_id'];
		}
		if(!empty($_POST['keyword']))
			$queryString['keyword'] = $_POST['keyword'];
		
		if(!empty($_POST['date_from']))
			$queryString['date_from'] = $_POST['date_from'];
		
		if(!empty($_POST['date_to']))
			$queryString['date_to'] = $_POST['date_to'];
		
		exit(json_encode(
				WishHelper::getListData($page, $rows, $sort, $order, $queryString)
		));
		*/ 
	}
	 
	/**
	 * +----------------------------------------------------------
	 * 列出 批量关联商品 的信息
	 * +----------------------------------------------------------
	 * @return			na
	 * +----------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh	  2014/10/22				初始化
	 * +----------------------------------------------------------
	 */
	function actionlistbatchupodaterelation(){
		if (! empty($_GET['itemlist'])){
			$criteria = new CDbCriteria;
            $criteria->addInCondition('itemid', explode(',', $_GET['itemlist']));
            $tmp_relationlist = EbayItem::model()->findall($criteria);
            foreach($tmp_relationlist as $key=>$value){
            	$row = $value->attributes;
            	$row['syssku'] = $row['sku']; 
            	$relationlist[] = $row; 
            }
            $this->renderPartial('_list_batch_update_relation', array("relationlist"=>$relationlist), false, true);
		}
	}//end of actionlistbatchupodaterelation
	
	/**
	 * +----------------------------------------------------------
	 * 检查用户填写的sku 是否有效
	 * +----------------------------------------------------------
	 * @return			na
	 * +----------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh	  2014/10/22				初始化
	 * +----------------------------------------------------------
	 */
	public function actionchecksyssku(){
		$result['rowindex'] = "";
		$result['root_sku'] = "";
		if (isset($_POST['rowindex'])){
			$result['rowindex'] = $_POST['rowindex'];
		}
		
		if (! empty($_POST['syssku'])){
			$result['root_sku'] =  ProductHelper::getRootSkuByAlias($_POST['syssku']);
			
		}
		
		if (! empty($_POST['alias'])){
			$result['alias_root_sku'] =  ProductHelper::getRootSkuByAlias($_POST['alias']);
		}
		exit(json_encode($result));
	}//end of actionchecksyssku
	
	/**
	 * +----------------------------------------------------------
	 * 保存用户填写的sku 
	 * +----------------------------------------------------------
	 * @return			na
	 * +----------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh	  2014/10/27				初始化
	 * +----------------------------------------------------------
	 */
	public function actionsavesyssku(){
		
		if (!empty($_POST['sysskulist'])){
			$sysskulist = json_decode($_POST['sysskulist']); 
			$result = array(
				'total'=> count($sysskulist),
				'aliasexist'=>0 ,
				'skuexist'=>0 ,
				'sku_alias'=>0 ,
				'sku'=>0 ,
				'alias'=>0 ,
			);
			foreach($sysskulist as $row){
				$field_name = ProductHelper::saveRelationProduct($row['itemid'], $row['syssku'],$row['type']);
				$result[$field_name['message']] =  $result[$field_name['message']] +1;
			}
			
		}else{
			$result = array(
				'total'=> 0,
				'aliasexist'=>0 ,
				'skuexist'=>0 ,
				'sku_alias'=>0 ,
				'sku'=>0 ,
				'alias'=>0 ,
			);
		}
		exit(json_encode($result));
	}//end of actonsavesyssku
	
	
	/**
	 * +----------------------------------------------------------
	 * 查询 产品参数
	 * +----------------------------------------------------------
	 * @return			na
	 * +----------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh	  2015/01/13				初始化
	 * +----------------------------------------------------------
	 */
	public function actionviewvariances(){
		if (!empty($_GET['variance_id'])){
			$variance_id = $_GET['variance_id'];
		}else{
			$variance_id = null;
		}
		
	}//end of actionviewvariances
	
	/**
	 * +----------------------------------------------------------
	 * 列出 选中绑定范本的数据 
	 * +----------------------------------------------------------
	 * @return			na
	 * +----------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh	  2015/04/25				初始化
	 * +----------------------------------------------------------
	 */
	public function actionListProductBinding(){
		$variants = [];
		$fanbenInfo = [];
		if (!empty($_REQUEST['selectedlist'])){
			$variants = WishFanbenVariance::find()
			->andWhere(['fanben_id'=>$_REQUEST['selectedlist']])
			->asArray()
			->all();
			
			$fanbenData = WishFanben::find()
			->select(['id','name','main_image'])
			->andWhere(['id'=>$_REQUEST['selectedlist']])
			->asArray()
			->all();
			
			foreach($fanbenData as $row){
				$fanbenInfo[$row['id']] = $row;
			}
		}
		
		return $this->renderPartial('_list_product_binding' , ['variants'=>$variants , 'fanbenInfo'=>$fanbenInfo]);
	}//end of actionlistProductBinding
	
	
	/**
	 * +----------------------------------------------------------
	 * 绑定范本的商品SKU
	 * +----------------------------------------------------------
	 * @return			na
	 * +----------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh	  2015/04/25				初始化
	 * +----------------------------------------------------------
	 */
	public function actionBindingSku(){
		//是否启用
		//echo $this->CatalogModulesAppKey; //test 
		$result = array(
							'total'=> 0,
							'aliasexist'=>0 ,
							'skuexist'=>0 ,
							'sku_alias'=>0 ,
							'sku'=>0 ,
							'alias'=>0 ,
							'skuempty'=>0,
							'delay'=>0,
					);
		try {
			if (true){
		//test kh	if ( AppApiHelper::checkAppIsActive($this->CatalogModulesAppKey)){
				//已经启用了商品模块
				if (!empty($_POST['bindingList'])){
					$type = 'wish';
					$result['total'] = count($_POST['bindingList']);
					foreach($_POST['bindingList'] as $row){
						if (!empty($row['productid'])){
							//调用 商品模块接口
							$field_name = ProductApiHelper::saveRelationProduct($row['productid'], $row['syssku'] , $type);
							$result[$field_name['message']] =  $result[$field_name['message']] +1;
						}else{
							//product id 为空 表示还没有上传 到wish 上的
							$result['delay'] = $result['delay']+1;
						}
						
						if (!empty($row['pkid'])){
							$model = WishFanbenVariance::findOne(['id'=>$row['pkid']]);
							$model->internal_sku = $row['syssku'];
							$rt = $model->save();
							
						}
						
					}// end of each variance
				}else{
					$result ['success'] = false;
					$result ['message'] = TranslateHelper::t('没有有效参数!');
				}
			}else{
				//没有启用商品模块
				$result ['success'] = false;
				$result ['message'] = TranslateHelper::t('没有开启商品模块!');
			}
		} catch (Exception $e) {
			$result ['success'] = false;
			$result ['message'] = $e->getMessage();
		}
		
		exit(json_encode($result));
	}//end of actionBindingSku
	
	public function actionAddSyncFanbenQueue(){
		$puid = \Yii::$app->subdb->getCurrentPuid();
		$result = SaasWishFanbenSyncHelper::addSyncProductQueue($puid);
		exit(json_encode($result));
	}//end of actionAddSyncFanbenQueue
	
	public function actionTest(){
		//$pp = (new Query())->select(['parent_sku'])->from('wish_fanben_variance')->Where(['variance_product_id'=>'54d1981e09eaa31ee07bcde4']);
		//$pp =  (new Query())->select('parent_sku')->from('wish_fanben_variance')->where(['variance_product_id' => '54d1981e09eaa31ee07bcde4']);
		//var_dump($pp);
		//return ;
		//$site_id = Yii::$app->get('subdb')->createCommand("select site_id from wish_fanben_variance v , wish_fanben f   where f.parent_sku = v.parent_sku and   v.variance_product_id = '54d1981e09eaa31ee07bcde4'")->queryScalar();
		
		//$site_name = SaasWishUser::find()->select(['store_name'])->Where(['site_id'=>$site_id])->asArray()->One();
		
		//var_dump($site_name);
		//return;
		set_time_limit(60);
		SaasWishFanbenSyncHelper::cronAutoFetchWishFanben();
		return ;
		//$token = "JHBia2RmMiQxMDAkQzZHVThyNDNab3dSNGh5REVHSk1pUSQwNzZ3d01NdFBhLllKeE9LRk44U0pSTndLQ2M=";
		//$result = SaasWishFanbenSyncHelper::getWishFanBen($token);
		//$result = SaasWishFanbenSyncHelper::getWishFanBen($token);
		$result['success'] = true;
		$result['proxyResponse']['success'] = true;
		$result['proxyResponse']['product'] = [
		[
			'main_image'=> 'http://contestimg.wish.com/api/webimage/547fda2b561c483c3cba9af4-original.jpg',
			'is_promoted'=> 'False',
			'description'=> 'Estimated Delivery Time 7-14',
			'name'=> 'Newfashioned Crocodile Leather Case for Apple iPhone 6 (4.7&quot;) with Interactive View Window and Stand Feature - Rose',
			'tags'=> [
			[
				'Tag'=> [
				'id'=> 'mobilephonebagscase',
				'name'=> 'mobile phone bags&amp;cases'
				]
			],
			[
				'Tag'=> [
				'id'=> 'rose',
				'name'=> 'Rose'
				]
			],
			[
				'Tag'=> [
				'id'=> 'iphone647case',
				'name'=> 'iphone 647 case'
				]
			],
			[
				'Tag'=> [
				'id'=> 'crocodile',
				'name'=> 'crocodile'
				]
			]
			],
			'review_status'=> 'approved',
			'upc'=> '000000000000',
			'extra_images'=> '',
			'auto_tags'=> [
			[
				'Tag'=> [
				'id'=> 'case',
				'name'=> 'case'
				]
			],
			[
				'Tag'=> [
				'id'=> 'apple',
				'name'=> 'Apple'
				]
			],
			[
				'Tag'=> [
				'id'=> 'leather',
				'name'=> 'leather'
				]
			],
			[
				'Tag'=> [
				'id'=> 'stand',
				'name'=> 'Stand'
				]
			],
			[
				'Tag'=> [
				'id'=> 'leathercase',
				'name'=> 'Leather Cases'
				]
			],
			[
				'Tag'=> [
				'id'=> 'iphone5',
				'name'=> 'iphone 5'
				]
			],
			[
				'Tag'=> [
				'id'=> 'iphone4',
				'name'=> 'Iphone 4'
				]
			],
			[
				'Tag'=> [
				'id'=> 'iphone',
				'name'=> 'iphone'
				]
			]
			],
			'number_saves'=> '0',
			'variants'=> [
			[
				'sku'=> 'CS8514102609D',
				'msrp'=> '12.99',
				'product_id'=> '547fda2b561c483c3cba9af4',
				'all_images'=> '',
				'price'=> '12.99',
				'shipping_time'=> '7-21',
				'enabled'=> 'Flase',
				'id'=> '547fda2b561c483c3cba9af6',
				'shipping'=> '1.99',
				'inventory'=> '20'
			]
			],
			'parent_sku'=> 'CS8514102609D',
			'id'=> '547fda2b561c483c3cba9af4',
			'number_sold'=> '0'
		],
		[
			'main_image'=> 'http://contestimg.wish.com/api/webimage/547fda2b902c9f287fea5091-original.jpg',
			'is_promoted'=> 'False',
			'description'=> 'Estimated Delivery Time 7-14',
			'name'=> 'Newfashioned Polka Dot Leather Case for Apple iPhone 6 plus with Interactive View Window and Stand Feature - Yellow',
			'tags'=> [
			[
				'Tag'=> [
				'id'=> 'mobilephonebagscase',
				'name'=> 'mobile phone bags&amp;cases'
				]
			],
			[
				'Tag'=> [
				'id'=> 'iphone6pluscase',
				'name'=> 'iphone 6 plus case'
				]
			],
			[
				'Tag'=> [
				'id'=> 'yellow',
				'name'=> 'Yellow'
				]
			]
			],
			'review_status'=> 'approved',
			'upc'=> '000000000000',
			'extra_images'=> 'http://contestimg.wish.com/api/webimage/54af9aebd630ed525e18bd2c-1-original.jpg|http://contestimg.wish.com/api/webimage/54af9aebd630ed525e18bd2c-2-original.jpg|http://contestimg.wish.com/api/webimage/54af9aebd630ed525e18bd2c-3-original.jpg|http://contestimg.wish.com/api/webimage/54af9aebd630ed525e18bd2c-4-original.jpg',
			'auto_tags'=> [
			[
				'Tag'=> [
				'id'=> 'case',
				'name'=> 'case'
				]
			],
			[
				'Tag'=> [
				'id'=> 'apple',
				'name'=> 'Apple'
				]
			],
			[
				'Tag'=> [
				'id'=> 'leather',
				'name'=> 'leather'
				]
			],
			[
				'Tag'=> [
				'id'=> 'polka',
				'name'=> 'Polkas'
				]
			],
			[
				'Tag'=> [
				'id'=> 'polkadot',
				'name'=> 'polka dot'
				]
			],
			[
				'Tag'=> [
				'id'=> 'stand',
				'name'=> 'Stand'
				]
			],
			[
				'Tag'=> [
				'id'=> 'leathercase',
				'name'=> 'Leather Cases'
				]
			],
			[
				'Tag'=> [
				'id'=> 'iphone5',
				'name'=> 'iphone 5'
				]
			],
			[
				'Tag'=> [
				'id'=> 'iphone4',
				'name'=> 'Iphone 4'
				]
			],
			[
				'Tag'=> [
				'id'=> 'iphone',
				'name'=> 'iphone'
				]
			]
			],
			'number_saves'=> '1',
			'variants'=> [
			[
				'sku'=> 'CS8514102612B',
				'msrp'=> '12.99',
				'product_id'=> '547fda2b902c9f287fea5091',
				'all_images'=> '',
				'price'=> '12.99',
				'shipping_time'=> '7-21',
				'enabled'=> 'True',
				'id'=> '547fda2b902c9f287fea5093',
				'shipping'=> '1.99',
				'inventory'=> '20'
			]
			],
			'parent_sku'=> 'CS8514102612B',
			'id'=> '547fda2b902c9f287fea5091',
			'number_sold'=> '0'
		],
		];
		
		
		//$result = json_decode($productlist,true);
		//var_dump($result);
		if (! empty($result['success'])){
			echo "a";
			if (!empty($result['proxyResponse']['success'])){
				echo "b";
				SaasWishFanbenSyncHelper::saveOnlineFanben('1',$result['proxyResponse']['product']);
			}else{
				//wish api not actived
			}
			
		}else{
			//proxy not actived
		}
		
		
		//var_dump($result);
		return ;
		/*
		$result = WishProxyConnectHelper::testWISHAccount();
		exit($result);
		*/
	}//end of actionTest
	


	public function actionTestSyncProduct(){
		$result = WishHelper::autoSyncFanbenInfo();
	}

	public function actionTestApi(){
		return $this->render('//basic/testpage');
	}


	
}
