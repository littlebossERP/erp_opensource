<?php 
	namespace eagle\modules\listing\controllers;

	use eagle\modules\manual_sync\models\Queue;
	use eagle\modules\listing\models\AliexpressGroupInfo;
	use eagle\modules\listing\models\AliexpressListing;
	use eagle\modules\listing\models\AliexpressCategory;
	use eagle\models\listing\AliexpressFreightTemplate;
	use eagle\modules\listing\helpers\AliexpressHelper;
	use eagle\modules\listing\helpers\AlipressApiHelper;
	use yii\helpers\ArrayHelper;
	use yii\data\Pagination;
	use yii\filters\VerbFilter;
	use eagle\models\SaasAliexpressUser;

class AliexpressController extends \eagle\components\Controller{
		public $enableCsrfValidation = FALSE;	
		public function behaviors(){
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

	   	public function actionAdd(){

	   		return $this->render('add',[
   				'users'=> self::_GetUserSellerInfo(),
   				'menu' => self::_getMenu(),
   				'product_unit'=>AlipressApiHelper::getProductUnit(),
   				'active'=> '待发布'
   			]);		
	   	}

	   	public function actionEdit(){
	   		$id =\Yii::$app->request->get('id');
	   		$edit_status = \Yii::$app->request->get('edit_status');
	   		$aliexpress = AliexpressListing::find()->where(['id'=>$id])->one();

	   		return $this->render('edit',[
   				'users' => self::_GetUserSellerInfo(),
   				'productInfo'=>$aliexpress,
   				'menu'=> self::_getMenu(),
   				'active'=> $edit_status == 0 ? '待发布' : '发布失败'
   			]);
	   	}

	   	public function actionOnlineEdit(){
	   		$product_id = \Yii::$app->request->get('product_id');
	   		$aliexpress = AliexpressListing::find()->with('onlineDetail')->where(['productid'=>$product_id])->one();
	   		return $this->render('online_edit',[
	   			'users' => self::_GetUserSellerInfo(),
	   			'productInfo' => $aliexpress,
	   			'menu' => self::_getMenu(),
	   			'active' => '速卖通平台商品'
   			]);
	   	}

	   	public function actionCopy(){
	   		$id = \Yii:: $app->request->get('id');
	   		$a = AliexpressListing::find()->where(['id'=>$id])->one();
	   		$aliexpress =$a->_clone();	
	   		return $this->render('edit',[
   				'users' => self::_GetUserSellerInfo(),
   				'productInfo' => $aliexpress,
   				'menu' => self::_getMenu(),
   				'active' => '待发布'
   			]);
	   	}

	   	
	   	public function actionPending(){
	   		return $this->redirect('/listing/aliexpress/online-list');
	   		
			$product_status = 0;   			
			$edit_status = 0;
	   		$data= self::_product($product_status,$edit_status);
	   		return  $this->render('list',[
   				'users' => $data['users'],
   				'productInfo'=> $data['list'],
   				'menu'=> self::_getMenu(),
   				'active' => '待发布',
   				'page' => $data['page'],
   				'product_status' => $product_status,
	   			'edit_status' => $edit_status
   			]);
	   	}
	   	public function actionPosting(){
	   		$product_status = 0;
	   		$edit_status = 1;
	   		$data= self::_product($product_status,$edit_status);
	   		return  $this->render('list',[
   				'users' => $data['users'],
   				'productInfo'=> $data['list'],
   				'menu'=> self::_getMenu(),
   				'active' => '发布中',
   				'page' => $data['page'],
   				'product_status' => $product_status,
   				'edit_status' => $edit_status
   			]);
	   	}

	   	public function actionFailed(){
	   		$product_status = 0;
	   		$edit_status = 3;
	   		$data = self::_product($product_status,$edit_status);
	   		return $this->render('list',[
	   				'users' => $data['users'],
	   				'productInfo' => $data['list'],
	   				'menu' => self::_getMenu(),
	   				'active' => '发布失败',
	   				'page' => $data['page'],
	   				'product_status' => $product_status,
	   				'edit_status' => $edit_status
	   			]);
	   	}

	   	public function _product($product_status,$edit_status){
   			$users = self::_GetUserSellerInfo();	
	   		$select_status = \Yii::$app->request->get('select_status','');
	   		$search_key = \Yii::$app->request->get('search_key','');
	   		$selleruserid = \Yii::$app->request->get('selleruserid');
	   		$pageSize = \Yii::$app->request->get('per-page',20);
	   		$params = [];
	   		if(!empty($search_key)){
	   			if($select_status == 'product_code')
	   				$params['sku'] = $search_key;
	   			else
	   				$params['subject'] = $search_key;
	   		}
	   		if(!empty($selleruserid)){
	   			$params['sellerloginid'] = $selleruserid; 
	   		}
	   		$type['product_status'] = $product_status;
	   		$type['edit_status'] = $edit_status;
	   		$data = self::_getProduct($params,$type,$pageSize);
	   		$data['users'] =$users;
	   		return $data;
	   	}
	   	public function _getProduct($params,$type,$pageSize){
	   		$aliexpress = AliexpressListing::find()->leftJoin('aliexpress_listing_detail','aliexpress_listing.id=aliexpress_listing_detail.listen_id')->where(['product_status'=>$type['product_status'],'edit_status'=>$type['edit_status']]);
	   		if(isset($params['sku'])){
	   			$aliexpress->andWhere(['like','aliexpress_listing_detail.sku_code',$params['sku']]);
	   		}
	   		if(isset($params['subject'])){
	   			$aliexpress->andWhere(['like','aliexpress_listing.subject',$params['subject']]);
	   		}
	   		if(isset($params['selleruserid'])){
	   			$aliexpress->andWhere(['aliexpress_listing.selleruserid'=>$params['selleruserid']]);
	   		}
	   		if(isset($params['sellerloginid'])){
	   			$aliexpress->andWhere(['aliexpress_listing.selleruserid'=>$params['sellerloginid']]);
	   		}
	   		//只显示有权限的账号，lrq20170828
	   		$account_data = \eagle\modules\platform\apihelpers\PlatformAccountApi::getPlatformAuthorizeAccounts('aliexpress');
	   		$selleruserids = array();
	   		foreach($account_data as $key => $val){
	   			$selleruserids[] = $key;
	   		}
	   		$aliexpress->andWhere(['aliexpress_listing.selleruserid'=>$selleruserids]);
	   		
	   		$aliexpress->orderBy('created desc');
	   		$pagination = new Pagination([
	   				'defaultPageSize' => $pageSize,
	   				'totalCount' => $aliexpress->count(),
	   				'pageSizeLimit' => [20,50,100,200],
	   				'params'=>$_REQUEST
	   			]);

	   		$aliexpress->offset($pagination->offset);
	   		$aliexpress->limit($pagination->limit);
	   		$list = $aliexpress->all();
	   		$return['list'] = $list;
	   		$return['page'] = $pagination;
	   		return $return;
	   	}

	   	public function actionDelProduct(){
	   		$id = \Yii::$app->request->get('id');
	   		$product = AliexpressListing::findOne($id);
	   		if($product->detailDel){
		   		if($product->delete()){
		   			return $return = true;
		   		}else{
		   			return $return = false;
		   		}
	   		}else{
	   			return $return = false;
	   		}

	   	}

	   	public function actionGetCategory(){
	   		$pid = \Yii::$app->request->post('pid');
	   		$Ali_Category = AliexpressHelper::GetOneLevelCategory($pid);
	   		return $this->renderJson($Ali_Category);
	   	}

	   	public function actionTestCategory(){
	   		$category = AliexpressHelper::TestCategory();
	   		return $this->render('category',[
	   				'category' => $category
	   			]);
	   	}

	   	public function actionTestCompleteCategory(){
	   		$category = AliexpressHelper::GetCompleteCategory();
	   		return $this->render('test-category',[
	   				'category' => $category
	   			]);
	   	}
	   	protected function _getMenu(){
	   		return [
	            /*'刊登管理'=>[
	                'icon'=>'icon-shezhi',
	                'items'=>[
	                    '待发布' => [
	                        'url' =>'/listing/aliexpress/pending',
	                    ],
	                   	'发布中'=>[
	                      	'url'=>'/listing/aliexpress/posting',
	                  	],
	                    '发布失败'=>[
	                        'url'=>'/listing/aliexpress/failed',
	                    ],
	                ]
	            ],*/
	            '商品列表'=>[
	                'icon'=>'icon-pingtairizhi',
	                'items'=>[
	                    '速卖通平台商品'=>[

                        'url'=>'/listing/aliexpress/online-list?product_status=3',
	                    ],
	                ]
	            ]
	        ];
	   	}

	   	public function actionTestCnToPinyin(){
	   		$cate = AliexpressCategory::find()->where([])->orderBy('cateid desc')->asArray();
	   		foreach($cate->each() as $cate){
	   			// var_dump($cate['name_zh']);
	   			// var_dump($cate['cateid']);die;
				sleep(1);
				$url = 'http://string2pinyin.sinaapp.com/?str='.$cate['name_zh'];
				$content = file_get_contents($url);
				$content = json_decode($content);
				$str = '';
				if($content->status == 'T'){
					for($i=0;$i<count($content->result);$i++){
						$str .= strtoupper(substr($content->result[$i],0,1));
					}
				}
				var_dump($str);
				$temp = AliexpressCategory::find()->where(['cateid'=>$cate['cateid']])->one();
				$temp-> filterIndex = $str;
				$temp->save();
			}
		}

		/**
		 * 同步页面
		 * @return [type] [description]
		 */
		public function actionSyncStart(){
			$shops = SaasAliexpressUser::find()->where([
				'uid'=>\Yii::$app->user->id
			]);
			
			//只显示有权限的账号，lrq20170828
			$account_data = \eagle\modules\platform\apihelpers\PlatformAccountApi::getPlatformAuthorizeAccounts('aliexpress');
			$selleruserids = array();
			foreach($account_data as $key => $val){
				$selleruserids[$key] = $key;
			}
			
			return $this->renderAuto('/sync_start',[
				'type'=>'checkbox',
				'manual_sync'=>'smt:product',
				'shops'=>$selleruserids
			]);
		}

		/**
		 * 在线列表
		 * @version 2016-06-16
		 * @author hqf
		 */
		public function actionOnlineList(){
			// 店铺列表
			$shops = SaasAliexpressUser::find()->where([
				'uid'=>\Yii::$app->user->id
			]);

			// 运费模板
			$freight = AliexpressFreightTemplate::find();

			$req = function($name){
				return \Yii::$app->request->get($name);
			};

			// 查询商品
			$query = AliexpressListing::find()->leftJoin('aliexpress_listing_detail','aliexpress_listing.productid=aliexpress_listing_detail.productid');

			// 统计在线商品的数量
			$statusTotal = [];
			foreach(AliexpressListing::$product_status as $key=>$status){
				$statusTotal[$status] = AliexpressListing::find()->where([
					'product_status'=>$key
				])->count();
			}

			

			// var_dump($page);die;

			if($req('sellerloginid')){
				$query->andWhere([
					'aliexpress_listing.selleruserid'=>$req('sellerloginid')
				]);
			}
			//只显示有权限的账号，lrq20170828
			$account_data = \eagle\modules\platform\apihelpers\PlatformAccountApi::getPlatformAuthorizeAccounts('aliexpress');
			$selleruserids = array();
			foreach($account_data as $key => $val){
				$selleruserids[$key] = $key;
			}
			$query->andWhere(['aliexpress_listing.selleruserid'=>$selleruserids]);
			
			if($req('freight')){
				$query->andWhere([
					'freight_template_id'=>$req('freight')
				]);
			}
			if($req('v') && $req('search')){
				if($req('search') == 'sku_code'){
					$query->andWhere(['LIKE','aliexpress_listing_detail.sku_code',$req('v')]);	
				}else{
					$query->andWhere(['LIKE','aliexpress_listing.subject',$req('v')]);
				}
				// $query->andWhere([
				// 	'LIKE',$req('search'),$req('v')
				// ]);
			}
			if($req('product_status')){
				$query->andWhere([
					'product_status'=>$req('product_status')
				]);
			}
			// 分页
			$page = new Pagination([
				'defaultPageSize' => 20,
				'totalCount' => $query->count(),
				// 'pageSize' => \Yii::$app->request->get('per-page', 20),
				'pageSizeLimit' => [20,50,100,200],
				// 'pageOptions'=>[5,10,20,100],
				'params'=>$_REQUEST,
			]);

			$query
				->with('onlineDetail','freight')
				->offset($page->offset)
				->limit($page->limit)
				->orderBy('gmt_create DESC');


			// echo '<pre>';print_r($query->asArray()->all());die;

			return $this->render('online_list',[
				'menu'=>[
					'menu'=>self::_getMenu(),
					'active'=>'速卖通平台商品',
				],
				'shops'=>$selleruserids,
				'statusTotal'=>$statusTotal,
				'freight'=>ArrayHelper::map($freight->asArray()->all(),'templateid','template_name'),
				'search'=>[
					'subject'=>'产品标题',
					'sku_code'=>'商品编码'
				],
				'products'=>$query,
				'page'=>$page,
				'product_status' => $req('product_status')
			]);
		}

		// 批量修改品牌
		public function actionBatchEditBrand(){
			$categories = [];
			// 遍历商品，统计数量及获取分类
			foreach($_POST['productid'] as $productid){
				$product = AliexpressListing::find()->where([
					'productid'=>$productid
				])->one();
				$cate_id = $product->onlineDetail->categoryid;
				// 统计数量
				if(!isset($categories[$cate_id])){
					// 设置可选品牌
					$brands = [];
					$cate = AliexpressCategory::findOne($cate_id);
					foreach($cate->brands as $brand){
						$brands[$brand->id] = $brand->name_zh.'('.$brand->name_en.')';
					}
					$categories[$cate_id] = [
						'option'=>$brands,
						'name'=>$cate->name_zh.'('.$cate->name_en.')',
						'productid'=>[]
					];
				}
				$categories[$cate_id]['productid'][] = $productid;
			}
			// var_dump($categories);die;
			return $this->renderAuto('batch_edit_brand',[
				'categories'=>$categories
			]);
		}

		// 提交批量品牌修改
		public function actionBatchEditBrandExec(){
			// 加入edit队列
			$products = [];
			foreach($_POST['productid'] as $cate_id=>$products){
				foreach($products as $productid){
					// 修改品牌
					if($product = AliexpressListing::find()->with('detail')->where([
						'productid'=>$productid
					])->one()){
						$product->detail->brand = $_POST['brand'][$cate_id];
						$product->edit_status = 2;
						if($product->save()){
							// 加入队列数据
							$products[] = $product->id;
						}
					}
				}
			}
			$queue = Queue::add('smt:productpush',\Yii::$app->user->id.'_'.md5(json_encode($products)),[
				'products'=>$products
			]);
			return $this->renderJson([
				'response'=>[
					'success'=>true,
					'code'=>201,
					'refresh'=>true,
					'type'=>'message',
					'message'=>'操作成功',
					'queue'=>$queue
				]
			]);
		}

		// 批量上下架
		public function actionBatchEnable(){
			$shops = $result = [];
			foreach($_POST['productid'] as $productid){
				if($product = AliexpressListing::find()->where([
					'productid'=>$productid
				])->one()){
					if(!isset($shops[$product->selleruserid])){
						$shops[$product->selleruserid] = [];
					}
					$shops[$product->selleruserid][] = $productid;
					// $product->product_status = $_GET['on'] ? 1:2;
					// $product->save();
				}
			}
			foreach($shops as $shop=>$productid){
				$result[$shop] = AliexpressListing::batchEnable($shop,$productid,$_GET['on']);
			}

			return $this->renderJson([
				'response'=>[
					'success'=>true,
					'code'=>202,
					'type'=>'message',
					'message'=>'操作成功',
					'result'=>$result
				]
			]);
		}


		public function actionGetCategoryAttr(){
			$cateid = \Yii::$app->request->post('cateid');
			$data = AlipressApiHelper::getCartInfo($cateid,true);
			return $this->renderJson($data);
		}

		public function actionGetFreightTemplate(){
			$sellerloginid = \Yii::$app->request->post('sellerloginid');
			$freight_templateid = AlipressApiHelper::tongbuFreightTemplate($sellerloginid);
			return $this->renderJson($freight_templateid);
		}

		public function actionGetPromiseTemplate(){
			$sellerloginid = \Yii::$app->request->post('sellerloginid');
			$promise_templateid = AlipressApiHelper::tongbuPromiseTemplate($sellerloginid);
			return $this->renderJson($promise_templateid);
		}

		public function actionGetInfoModule(){
			$sellerloginid = \Yii::$app->request->post('sellerloginid');
			$name = \Yii::$app->request->post('name','');
			$infoModule = AlipressApiHelper::findAeProductDetailModuleListByQurey($sellerloginid,$name);
			if(empty($infoModule)){
				$infoModule = AlipressApiHelper::tongbuProductDetailModule($sellerloginid,$name);
			}
			return $this->renderJson($infoModule);
		}
		public function actionGetProductGroupList(){
			$sellerloginid = \Yii::$app->request->post('sellerloginid');
			$Group = new AliexpressGroupInfo();
			$groupList = $Group->getAllGroups($sellerloginid);
			return $this->renderJson($groupList);
		}
		public function actionSave(){
			$product = \Yii::$app->request->post('product');
			$id = \Yii::$app->request->get('id','');
			$action =  empty($id) ? 'add' : 'edit';
			$product['aeopAeProductPropertys'] = json_encode($product['aeopAeProductPropertys']);
			$product['img_url'] = AliexpressHelper::_uploadingImgToAliexpressBank($product['img_url'],$product['selleruserid']);
			$product['aeopAeProductSKUs'] = json_encode($product['aeopAeProductSKUs']);
			$product['categoryid'] = strval($product['categoryid']);
			$product['detail'] = AliexpressHelper::_getProductDetail($product['detail']);
			$returnInfo = AlipressApiHelper::saveProductInfo($product,$action,$id);
			return $this->renderJson($returnInfo);
		}
		public function actionOnlineSave(){
			$productid = \Yii::$app->request->get('productid');
			$product = \Yii::$app->request->post('product');
			$product['aeopAeProductPropertys'] = json_encode($product['aeopAeProductPropertys']);
			$product['img_url'] = AliexpressHelper::_uploadingImgToAliexpressBank($product['img_url'],$product['selleruserid']);
			$product['aeopAeProductSKUs'] = json_encode($product['aeopAeProductSKUs']);
			$product['categoryid'] = strval($product['categoryid']);
			$product['detail'] = AliexpressHelper::_getProductDetail($product['detail']);
			$action = 'edit';

			$id = AliexpressListing::find()->where(['productid'=>$productid])->one()->id;
			$returnInfo = AlipressApiHelper::saveProductInfo($product,$action,$id);
			if($returnInfo['return'] != false){

				$aliexpress = AliexpressListing::find()->where(['productid'=>$productid])->one();
				// $returnInfo  =  AlipressApiHelper::postAeProduct($product['selleruserid'],$aliexpress->id,'edit');
				$returnInfo = $aliexpress->push();
			}
			return $this->renderJson($returnInfo);
		}

		public function actionUploadImgToAliexpressBank(){
			$img = \Yii::$app->request->get('img');
			$imgName = trim(basename($img));
			$sellerloginid = \Yii::$app->request->get('sellerloginid');
			return $this->renderJson(AlipressApiHelper::uploadImage($sellerloginid,$imgName,$img));
		}

		public function actionPush(){
			$id = \Yii::$app->request->get('id');	
			$sellerloginid = \Yii::$app->request->get('sellerloginid','');
			$aliexpressListing = AliexpressListing::findOne($id);
			if(empty($sellerloginid)){
				$sellerloginid = $aliexpressListing->selleruserid;
			}
			$return = $aliexpressListing->push();
			if(is_array($return['msg'])){
				$return['msg']['error_message'] = AliexpressHelper::AliexpressErrorMessage($return['msg']['error_code']);
				return $this->renderJson([
					'response' =>[
						'success' => false,
						'code'=> $return['msg']['error_code'],
						'message'=>$return['msg']['error_message'],
						'type'=>'messsage',
					]
				]);
			}else{
				return $this->renderJson([
					'response'=>[
						'success'=> true,
						'code' => 0,
						'message' => '商品发布成功',
						'type'=>'message',
					]		
				]);
			}
		}

		public function actionEditPush(){
			$id = \Yii::$app->request->get('id');	
			$sellerloginid = \Yii::$app->request->get('sellerloginid','');
			$aliexpressListing = AliexpressListing::findOne($id);
			if(empty($sellerloginid)){
				$sellerloginid = $aliexpressListing->selleruserid;
			}
			$return = $aliexpressListing->push();
			return json_encode($return);
		}

		// public function actionShowProcess(){
		// 	return $this->renderAuto('process');		
		// }

		public function actionPushProduct(){
			$ids = \Yii::$app->request->post('ids');
			$return = AliexpressListing::batchPush($ids);
			return $this->renderJson($return);
		}
		protected function _GetUserSellerInfo(){
			// $puid = 297;
			$user=\Yii::$app->user->identity;
			$puid = $user->getParentUid();
			
			//只显示有权限的账号，lrq20170828
			$account_data = \eagle\modules\platform\apihelpers\PlatformAccountApi::getPlatformAuthorizeAccounts('aliexpress');
			$selleruserids = array();
			foreach($account_data as $key => $val){
				$selleruserids[] = $key;
			}
			
			$users = SaasAliexpressUser::find()->where(['uid'=>$puid, 'sellerloginid' => $selleruserids])->asArray()->all();
			return $users;
		}

		public function actionTestPic(){
			$img_urls = 'http://g02.a.alicdn.com/kf/HTB1DWsaKFXXXXbUXXXXq6xXFXXXk/220678964/HTB1DWsaKFXXXXbUXXXXq6xXFXXXk.jpg;http://g02.a.alicdn.com/kf/HTB1DWsaKFXXXXbUXXXXq6xXFXXXk/220678964/HTB1DWsaKFXXXXbUXXXXq6xXFXXXk.jpg';	
			$sellerloginid = 'cn1510671045';
			$imgs = AliexpressHelper::_uploadingImgToAliexpressBank($img_urls,$sellerloginid);
			var_dump($imgs);
		}

		public function actionGetCategoryName($cateName){
			$cateName = \Yii::$app->request->get('cateName');
			$cateList = AliexpressHelper::GetCategoryList($cateName);
			return $this->renderJson($cateList);
		}

		public function actionTestphpinfo(){
			phpinfo();
		}
}
