<?php namespace eagle\modules\listing\controllers;

use \yii\data\Pagination;
use eagle\modules\listing\models\WishFanben;
use eagle\modules\listing\models\ListingDraftLog;
use eagle\models\SaasWishUser;
use eagle\models\SaasLazadaUser;
use eagle\models\SaasAliexpressUser;
use eagle\models\SaasEbayUser;
 
class ListingDraftController extends \eagle\components\Controller
{

	protected function getMenu($active=''){
		$wish = SaasWishUser::find()->where([
			'uid'=>\Yii::$app->user->id
		]);
		 
		$items = array_merge(
			[],
			$wish->count()?[
				'Wish'=>[
					'url'=>'/listing/listing-draft/wish-lists'
				]
			]:[]
		);
		// $drafts = array_merge(
			// [],
			// $wish->count()?[
				// 'XX草稿箱'=>[
                    // 'url'=>'/listing/xx-listing/draft-list',
                // ]
			// ]:[]
		// );
		$drafts = [];
		return [
			'menu'=>[
				'店铺搬家'=>[
					'icon'=>'icon-yijianxiugaifahuoriqi',

				],


				'草稿箱'=>[
		            'icon'=>'icon-21',
		            'items'=>$drafts
		        ],

		        '教程'=>[
		        	'url'=>'http://www.littleboss.com/announce_info_48.html',
		        	'icon'=>'icon-lijikandeng',
		        	'target'=>'_blank'
		        ]
			],
			'active' => $active 
		];
	}

	/**
	 * 选择店铺模态框
	 * @return [type] [description]
	 */
	function actionSelectShop(){
		
		return $this->renderAuto('select_shop',[

		]);
	}


	function actionWishLists(){

		// 店铺
		$shops = SaasWishUser::find()->where([
			'uid'=>\Yii::$app->user->id
		]);
		//查询全部商铺的id		
		$siteids = (new \yii\db\Query)->select('site_id')->	from('saas_wish_user')->where(['uid'=>\yii::$app->user->id])->all();
		//var_dump($siteids);exit;

		// 根据query进行查询
		#获取搜索类型
		$search_type=empty(\Yii::$app->request->get('search-type'))?'parent_sku':\Yii::$app->request->get('search-type');
		#获取搜索内容
		$cont=\Yii::$app->request->get('content-search',false);
		#获取所选商铺的siteid
		$siteid=empty(\Yii::$app->request->get('site_id'))?$siteids:\Yii::$app->request->get('site_id');
		//var_dump($siteid);
		$products = WishFanben::find()->where(['and'
			,['type'=>1]
			,['site_id'=>$siteid]
			,['like',$search_type,$cont]
		]);
		
		$page = new Pagination([
			'defaultPageSize' => 20,
			'totalCount' => $products->count(),
			'pageSize' => \Yii::$app->request->get('per-page', 20),	
			'pageSizeLimit' => [20, 50, 100, 200],
		]);

		$products->offset($page->offset)->limit($page->limit);

		return $this->render('wish_lists',[
			'menu'=>$this->getMenu('Wish'),	
			'shops'=>$shops,
			'products'=>$products->all(),
			'page'=>$page
		]);
	}

	/**
	 * 同步页面
	 * @return [type] [description]
	 */
	public function actionSyncStart(){
		$shops = SaasWishUser::find()->where([
			'uid'=>\Yii::$app->user->id
		]);
		return $this->renderAuto('/sync_start',[
			'type'=>'checkbox',
			'manual_sync'=>'wish:product',
			'shops'=>\yii\helpers\ArrayHelper::map($shops->asArray()->all(),'site_id','store_name')
		]);
	}
	
	function actionViewDraftLog(){

		$data = [
			'wish'=>[],
			'lazada'=>[],
			'aliexpress'=>[]
		];

		$logs = ListingDraftLog::find()->where([
			'parent_sku'=>$_GET['parent_sku']
		])
		->andWhere([
			'<>','platform_to',$_GET['platform_from']
		]);

		foreach($logs->each(1) as $log){
			if(isset( $data[$log->platform_to] )){
				$data[$log->platform_to][] = $log->shopName;
			}
		}

		// var_dump($data);die;

		return $this->renderAuto('view_draft_log',[
			'data'=>$data
		]);

	}

	function actionModal(){
		#商品的id
		$parent_sku=\Yii::$app->request->get('parent_sku');
		//var_dump($goodId);exit;
 
		$shopLists = [];

		//var_dump($shopLists);exit;
		
		return $this->renderAuto('wish_lists_modal',[
			'shopLists'=>$shopLists,
			'parent_sku'=>$parent_sku,	
		]);
	}

	#执行搬家到操作		
	function actionMove(){
		if(\yii::$app->request->isPost){
			$parent_sku =\Yii::$app->request->post('parent_sku');
			$storeIds = \Yii::$app->request->post('shops');

			$wishProds = WishFanben::getManualFormat($parent_sku);
			$time = date('Y-m-d H:i:s');
			foreach($wishProds as $pd){
				foreach($storeIds as $to){
					if(!$log = ListingDraftLog::find()->where([
						'parent_sku'=>$pd['parent_sku'],
						'platform_from'=>'wish',
						'shop_from'=>$pd['shop'],
						'platform_to'=>'xxx',// to add platform
						'shop_to'=>$to,
					])->one()){
						$log = new ListingDraftLog;
						$log->setAttributes([
							'parent_sku'=>$pd['parent_sku'],
							'platform_from'=>'wish',
							'shop_from'=>$pd['shop'],
							'platform_to'=>'xxx',// to add platform
							'shop_to'=>$to,
							'create_time'=>$time
						]);
						$log->save();
					}
				}
			}
			try{
				// $res =  moveWishProductToDraft($wishProds,$storeIds);
				return $this->renderAuto('move_success');
			}catch(\Exception $e){
				return $this->renderJson([
					'response'=>[
						'type'=>'message',
						'message'=>$e->getMessage()
					]
				]);
			}

		}
		

		// \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

		return $this->renderJson([
			'data'=>$res
		]);
		
	}


}