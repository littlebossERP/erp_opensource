<?php
namespace eagle\modules\listing\controllers;


use eagle\modules\util\helpers\ResultHelper;
use eagle\modules\lazada\apihelpers\SaasLazadaAutoSyncApiHelper;
use eagle\models\SaasLazadaUser;
use eagle\modules\util\helpers\TranslateHelper;
use eagle\modules\listing\models\LazadaPublishListing;
use eagle\modules\lazada\apihelpers\LazadaApiHelper;
use yii\data\Pagination;
use eagle\modules\util\helpers\ConfigHelper;
use eagle\modules\app\apihelpers\AppTrackerApiHelper;
use eagle\widgets\ESort;
use eagle\modules\listing\models\LazadaListing;
use common\helpers\Helper_Array;
use eagle\modules\lazada\apihelpers\SaasLazadaAutoSyncApiHelperV3;
use eagle\modules\lazada\apihelpers\SaasLazadaAutoSyncApiHelperV4;
class lazadaListingController extends \eagle\components\Controller {
	
	public function actionIndex() {
		return $this->render('index' );
	}
	
	// 待发布列表  
	// listing/lazada-listing/publish
	public $enableCsrfValidation = false;
	public function actionPublish(){
	    AppTrackerApiHelper::actionLog("listing_lazada", "/listing/lazada-listing/publish");
	    
	    //只显示有权限的账号，lrq20170828
	    $account_data = \eagle\modules\platform\apihelpers\PlatformAccountApi::getPlatformAuthorizeAccounts('lazada');
	    $selleruserids = array();
	    foreach($account_data as $key => $val){
	    	$selleruserids[] = $key;
	    }
	    //获取对应客户id的商铺名
	    $puid = \Yii::$app->user->identity->getParentUid();
	    $lazadaUsers= SaasLazadaUser::find()->where(['puid'=>$puid,"platform"=>"lazada",'platform_userid'=>$selleruserids])->andWhere('status <> 3')->asArray()->all();// 不包括解绑用户
	    
	    $siteIdNameMap = LazadaApiHelper::getLazadaCountryCodeSiteMapping('lazada');
	    $lazadaUsersDropdownList = array();
	    $lazada_uids = array();
	    foreach ($lazadaUsers as $lazadaUser){
	        $lazadaUsersDropdownList[$lazadaUser['lazada_uid']] = $lazadaUser['platform_userid']."(".$siteIdNameMap[$lazadaUser['lazada_site']].")";
	        $lazada_uids[] = $lazadaUser['lazada_uid'];
	    };

	    $data = [];
	    $shop_name = [];
	    $query = LazadaPublishListing::find()->where(["platform"=>"lazada", 'lazada_uid' => $lazada_uids]);
	    $query->andWhere(["state"=>LazadaApiHelper::PUBLISH_LISTING_STATE_DRAFT]);
	    
	    if(!empty($_REQUEST['shop_name'])){
	    	$query->andWhere(["lazada_uid"=>$_REQUEST['shop_name']]);
	    }
	    
	    if(!empty($_REQUEST['condition'])&&!empty($_REQUEST['condition_search'])){
	    	$_REQUEST['condition_search'] = trim($_REQUEST['condition_search']);
	    	if($_REQUEST['condition'] == 'title'){
	    		// json encode 不特殊处理 .* , 这里对请求内容前后加上 .*  对Name 之后 " 之前  对应的值 进行全模糊 匹配
	    		// 删除的模糊匹配有缺陷，如果搜索字符数比较短的话，就会 匹配到后面非Name 值里面的值
	    		$searchStr = json_encode(['name'=>".*".$_REQUEST['condition_search'].".*"]);
	    		$searchStr = substr($searchStr, 1 , strlen($searchStr) - 2);// 去掉 array的括号
	    		// 然后为了正则匹配反斜杠 对 反斜杠 添加一条反斜杠  (字符串里面正则匹配反斜杠 \ 需要4个 反斜杠来匹配,所以最后打印的sql 也是要 4个反斜杠匹配一条反斜杠) 
	    		$searchStr = str_replace('\\','\\\\',$searchStr);
	    		$query->andWhere(["regexp","base_info",$searchStr]);
// 	    		$where .= " and ( base_info like '%\"Name\":\"%{$_REQUEST['condition_search']}%\",%')";
	    	}
	    	if($_REQUEST['condition'] == 'sku'){
	    		$searchStr = json_encode(['SellerSku'=>".*".$_REQUEST['condition_search'].".*"]);
	    		$searchStr = substr($searchStr, 1 , strlen($searchStr) - 2);// 去掉 array的括号
	    		$searchStr = str_replace('\\','\\\\',$searchStr);
	    		$query->andWhere(["regexp","variant_info",$searchStr]);
// 	    		$where .= " and ( variant_info like '%\"SellerSku\":\"%{$_REQUEST['condition_search']}%\",%')";
	    	}
	    }

	    $pages = new Pagination(['totalCount' =>$query->count(), 'defaultPageSize'=>20 , 'pageSizeLimit'=>[5,200] , 'params'=>$_REQUEST]);
	    $lazadaList = $query->orderBy(['create_time'=>SORT_DESC])->offset($pages->offset)->limit($pages->limit)->asArray()->all();
	    foreach ($lazadaList as $lazadaList_detail){
	        $public_array = [];
	        $variants = [];
	        $variation = [];//拼接所有每个产品的变参
	        $base_info = json_decode($lazadaList_detail['base_info'],true);
	        $variant_info = json_decode($lazadaList_detail['variant_info'],true);
	        $img_src = json_decode($lazadaList_detail['image_info'],true);
	        
	        foreach ($variant_info as $variant_detail){//多个变参
	            $variants['sku'] = $variant_detail['SellerSku'] ;
	            $variants['quantity'] = empty($variant_detail['quantity'])?0:$variant_detail['quantity'];
	            $variants['price'] = empty($variant_detail['price'])?0:$variant_detail['price'];
	            $variation[] = $variants;
	            
	            // 第一个变参的首图作缩略图为产品缩略图
	            if(empty($public_array['image']) && !empty($img_src) && !empty($img_src['product_photo_thumbnails']) 
	                    && !empty($img_src['product_photo_thumbnails'][$variant_detail['skuSelData']]))
	            $public_array['image'] = $img_src['product_photo_thumbnails'][$variant_detail['skuSelData']][0];
	        }
	        $public_array['id'] = $lazadaList_detail['id'];
	        $public_array['title'] = empty($base_info['name']) ? '' : $base_info['name'];
	        $public_array['variation'] = $variation;
	        $public_array['shop_name'] = isset($lazadaUsersDropdownList[$lazadaList_detail['lazada_uid']])?$lazadaUsersDropdownList[$lazadaList_detail['lazada_uid']]:"";// 解绑没有
	        $public_array['create_time'] = date("Y-m-d H:i:s",$lazadaList_detail['create_time']);
	        empty($public_array['image'])?$public_array['image']='':'';
	        $public_array['status'] = $lazadaList_detail['status'];
	        $data[]=$public_array;
	    }
// 	    print_r($data);exit();
// 	    print_r($lazadaList);exit();
	    return $this->render('lazadaPublic',['pages'=>$pages,'data'=>$data,'shop_name'=>$lazadaUsersDropdownList,'activeMenu'=>'待发布']);
//         return $this->render('creat-tree',['pages'=>$pages,]);
	}
	

	// listing/lazada-listing/publishing
	// 发布中list
	public function actionPublishing(){
		AppTrackerApiHelper::actionLog("listing_lazada", "/listing/lazada-listing/publishing");
		//只显示有权限的账号，lrq20170828
		$account_data = \eagle\modules\platform\apihelpers\PlatformAccountApi::getPlatformAuthorizeAccounts('lazada');
		$selleruserids = array();
		foreach($account_data as $key => $val){
			$selleruserids[] = $key;
		}
		//获取对应客户id的商铺名
		$puid = \Yii::$app->user->identity->getParentUid();
		$lazadaUsers= SaasLazadaUser::find()->where(['puid'=>$puid,"platform"=>"lazada",'platform_userid'=>$selleruserids])->andWhere('status <> 3')->asArray()->all();// 不包括解绑用户

		$siteIdNameMap = LazadaApiHelper::getLazadaCountryCodeSiteMapping('lazada');
		$lazadaUsersDropdownList = array();
		$lazada_uids = array();
		foreach ($lazadaUsers as $lazadaUser){
			$lazadaUsersDropdownList[$lazadaUser['lazada_uid']] = $lazadaUser['platform_userid']."(".$siteIdNameMap[$lazadaUser['lazada_site']].")";
			$lazada_uids[] = $lazadaUser['lazada_uid'];
		};
	   
// 	   $where = " (state = '".LazadaApiHelper::PUBLISH_LISTING_STATE_IMAGE_UPLOAD."' or state = '".LazadaApiHelper::PUBLISH_LISTING_STATE_IMAGE_UPLOADED."' or state = '".LazadaApiHelper::PUBLISH_LISTING_STATE_PRODUCT_UPLOAD."' or state = '".LazadaApiHelper::PUBLISH_LISTING_STATE_PRODUCT_UPLOADED."')";
// 	   if(!empty($_REQUEST['shop_name'])){
// 	        $where .= " and ( lazada_uid={$_REQUEST['shop_name']})";
// 	   }
// 	   if(!empty($_REQUEST['condition'])&&!empty($_REQUEST['condition_search'])){
// 	       if($_REQUEST['condition'] == 'title'){
// 	           $where .= " and ( base_info like '%\"Name\":\"%{$_REQUEST['condition_search']}%\",%')";
// 	       }
// 	       if($_REQUEST['condition'] == 'sku'){
// 	           $where .= " and ( variant_info like '%\"SellerSku\":\"%{$_REQUEST['condition_search']}%\",%')";
// 	       }
	   
// 	   }
	   
	   // 	    print_r($lazadaUsers);exit();
		$data = [];
		$shop_name = [];
		$query = LazadaPublishListing::find()->where(["platform"=>"lazada", 'lazada_uid' => $lazada_uids]);
		$query->andWhere(["state"=>[LazadaApiHelper::PUBLISH_LISTING_STATE_IMAGE_UPLOAD,LazadaApiHelper::PUBLISH_LISTING_STATE_IMAGE_UPLOADED,LazadaApiHelper::PUBLISH_LISTING_STATE_PRODUCT_UPLOAD,LazadaApiHelper::PUBLISH_LISTING_STATE_PRODUCT_UPLOADED]]);

		if(!empty($_REQUEST['shop_name'])){
			$query->andWhere(["lazada_uid"=>$_REQUEST['shop_name']]);
		}
	    
		if(!empty($_REQUEST['condition'])&&!empty($_REQUEST['condition_search'])){
	    	$_REQUEST['condition_search'] = trim($_REQUEST['condition_search']);
	    	if($_REQUEST['condition'] == 'title'){
	    		$searchStr = json_encode(['Name'=>".*".$_REQUEST['condition_search'].".*"]);
	    		$searchStr = substr($searchStr, 1 , strlen($searchStr) - 2);// 去掉 array的括号
	    		$searchStr = str_replace('\\','\\\\',$searchStr);
	    		$query->andWhere(["regexp","base_info",$searchStr]);
	    	}
	    	if($_REQUEST['condition'] == 'sku'){
	    		$searchStr = json_encode(['SellerSku'=>".*".$_REQUEST['condition_search'].".*"]);
	    		$searchStr = substr($searchStr, 1 , strlen($searchStr) - 2);// 去掉 array的括号
	    		$searchStr = str_replace('\\','\\\\',$searchStr);
	    		$query->andWhere(["regexp","variant_info",$searchStr]);
	    	}
	    }
	   
// 	   $query = LazadaPublishListing::find();
		$pages = new Pagination(['totalCount' =>$query->count(), 'defaultPageSize'=>20 , 'pageSizeLimit'=>[5,200] , 'params'=>$_REQUEST]);
		$lazadaList = $query->orderBy(['create_time'=>SORT_DESC])->offset($pages->offset)->limit($pages->limit)->asArray()->all();
		foreach ($lazadaList as $lazadaList_detail){
			$public_array = [];
			$variants = [];
			$variation = [];
			$base_info = json_decode($lazadaList_detail['base_info'],true);
			$variant_info = json_decode($lazadaList_detail['variant_info'],true);
			$img_src = json_decode($lazadaList_detail['image_info'],true);
			foreach ($variant_info as $variant_detail){//多个变参
				$variants['sku'] = $variant_detail['SellerSku'] ;
	            $variants['quantity'] = empty($variant_detail['quantity'])?0:$variant_detail['quantity'];
	            $variants['price'] = empty($variant_detail['price'])?0:$variant_detail['price'];
	            $variation[] = $variants;
	            
	            // 第一个变参的首图作缩略图为产品缩略图
	            if(empty($public_array['image']) && !empty($img_src) && !empty($img_src['product_photo_thumbnails']) 
	                    && !empty($img_src['product_photo_thumbnails'][$variant_detail['skuSelData']]))
	            $public_array['image'] = $img_src['product_photo_thumbnails'][$variant_detail['skuSelData']][0];
			}
			$public_array['id'] = $lazadaList_detail['id'];
			$public_array['title'] =  empty($base_info['name']) ? '' : $base_info['name'];
			$public_array['variation'] = $variation;
			$public_array['shop_name'] = isset($lazadaUsersDropdownList[$lazadaList_detail['lazada_uid']])?$lazadaUsersDropdownList[$lazadaList_detail['lazada_uid']]:"";
			$public_array['create_time'] = date("Y-m-d H:i:s",$lazadaList_detail['create_time']);
			empty($public_array['image'])?$public_array['image']='':'';
			$public_array['status'] = $lazadaList_detail['status'];
			$data[]=$public_array;
	    };
		// 	    print_r($data);exit();
		// 	    print_r($lazadaList);exit();
		return $this->render('lzdPublishing',['pages'=>$pages,'data'=>$data,'shop_name'=>$lazadaUsersDropdownList,'activeMenu'=>'发布中']);
	}
	
	// listing/lazada-listing/publish-fail
	// 发布失败 list
	public function actionPublishFail(){
	   AppTrackerApiHelper::actionLog("listing_lazada", "/listing/lazada-listing/publish-fail");
	   //只显示有权限的账号，lrq20170828
	   $account_data = \eagle\modules\platform\apihelpers\PlatformAccountApi::getPlatformAuthorizeAccounts('lazada');
	   $selleruserids = array();
	   foreach($account_data as $key => $val){
	   	$selleruserids[] = $key;
	   }
	   //获取对应客户id的商铺名
	   $puid = \Yii::$app->user->identity->getParentUid();
	   $lazadaUsers= SaasLazadaUser::find()->where(['puid'=>$puid,"platform"=>"lazada",'platform_userid'=>$selleruserids])->andWhere('status <> 3')->asArray()->all();// 不包括解绑用户
	    
	   $siteIdNameMap = LazadaApiHelper::getLazadaCountryCodeSiteMapping('lazada');
	   $lazadaUsersDropdownList = array();
	   $lazada_uids = array();
	   foreach ($lazadaUsers as $lazadaUser){
	       $lazadaUsersDropdownList[$lazadaUser['lazada_uid']] = $lazadaUser['platform_userid']."(".$siteIdNameMap[$lazadaUser['lazada_site']].")";
	       $lazada_uids[] = $lazadaUser['lazada_uid'];
	   };

	   $data = [];
	   $shop_name = [];
	   $query = LazadaPublishListing::find()->where(["platform"=>"lazada", 'lazada_uid' => $lazada_uids]);
	   $query->andWhere(["state"=>LazadaApiHelper::PUBLISH_LISTING_STATE_FAIL]);
	    
	   if(!empty($_REQUEST['shop_name'])){
	       $query->andWhere(["lazada_uid"=>$_REQUEST['shop_name']]);
	   }
	    
		if(!empty($_REQUEST['condition'])&&!empty($_REQUEST['condition_search'])){
	    	$_REQUEST['condition_search'] = trim($_REQUEST['condition_search']);
	    	if($_REQUEST['condition'] == 'title'){
	    		$searchStr = json_encode(['Name'=>".*".$_REQUEST['condition_search'].".*"]);
	    		$searchStr = substr($searchStr, 1 , strlen($searchStr) - 2);// 去掉 array的括号
	    		$searchStr = str_replace('\\','\\\\',$searchStr);
	    		$query->andWhere(["regexp","base_info",$searchStr]);
	    	}
	    	if($_REQUEST['condition'] == 'sku'){
	    		$searchStr = json_encode(['SellerSku'=>".*".$_REQUEST['condition_search'].".*"]);
	    		$searchStr = substr($searchStr, 1 , strlen($searchStr) - 2);// 去掉 array的括号
	    		$searchStr = str_replace('\\','\\\\',$searchStr);
	    		$query->andWhere(["regexp","variant_info",$searchStr]);
	    	}
	    }
	   
// 	   $query = LazadaPublishListing::find();
	   $pages = new Pagination(['totalCount' =>$query->count(), 'defaultPageSize'=>20 , 'pageSizeLimit'=>[5,200] , 'params'=>$_REQUEST]);
	   $lazadaList = $query->orderBy(['create_time'=>SORT_DESC])->offset($pages->offset)->limit($pages->limit)->asArray()->all();
	    foreach ($lazadaList as $lazadaList_detail){
	        $public_array = [];
	        $variants = [];
	        $variation = [];
	        $base_info = json_decode($lazadaList_detail['base_info'],true);
	        $variant_info = json_decode($lazadaList_detail['variant_info'],true);
	        $img_src = json_decode($lazadaList_detail['image_info'],true);
	        foreach ($variant_info as $variant_detail){//多个变参
	            $variants['sku'] = $variant_detail['SellerSku'] ;
	            $variants['quantity'] = empty($variant_detail['quantity'])?0:$variant_detail['quantity'];
	            $variants['price'] = empty($variant_detail['price'])?0:$variant_detail['price'];
	            $variation[] = $variants;
	            
	            // 第一个变参的首图作缩略图为产品缩略图
	            if(empty($public_array['image']) && !empty($img_src) && !empty($img_src['product_photo_thumbnails']) 
	                    && !empty($img_src['product_photo_thumbnails'][$variant_detail['skuSelData']]))
	            $public_array['image'] = $img_src['product_photo_thumbnails'][$variant_detail['skuSelData']][0];
	        }
	        $public_array['id'] = $lazadaList_detail['id'];
	        $public_array['title'] = empty($base_info['name']) ? '' : $base_info['name'];
	        $public_array['variation'] = $variation;
	        $public_array['shop_name'] = isset($lazadaUsersDropdownList[$lazadaList_detail['lazada_uid']])?$lazadaUsersDropdownList[$lazadaList_detail['lazada_uid']]:"";
	        $public_array['create_time'] = date("Y-m-d H:i:s",$lazadaList_detail['create_time']);
	        empty($public_array['image'])?$public_array['image']='':'';
			$public_array['feed_info'] = $lazadaList_detail['feed_info'];
	        $public_array['status'] = $lazadaList_detail['status'];
	        $data[]=$public_array;
	    };
// 	    print_r($data);exit();
// 	    print_r($lazadaList);exit();
	    return $this->render('lzdPublishFail',['pages'=>$pages,'data'=>$data,'shop_name'=>$lazadaUsersDropdownList,'activeMenu'=>'发布失败']);
	}
	
	// listing/lazada-listing/publish-success
	// 发布成功
	public function actionPublishSuccess(){
		//只显示有权限的账号，lrq20170828
		$account_data = \eagle\modules\platform\apihelpers\PlatformAccountApi::getPlatformAuthorizeAccounts('lazada');
		$selleruserids = array();
		foreach($account_data as $key => $val){
			$selleruserids[] = $key;
		}
	   //获取对应客户id的商铺名
	   $puid = \Yii::$app->user->identity->getParentUid();
	   $lazadaUsers= SaasLazadaUser::find()->where(['puid'=>$puid,"platform"=>"lazada",'platform_userid'=>$selleruserids])->andWhere('status <> 3')->asArray()->all();// 不包括解绑用户
	    
	   $siteIdNameMap = LazadaApiHelper::getLazadaCountryCodeSiteMapping('lazada');
	   $lazadaUsersDropdownList = array();
	   $lazada_uids = array();
	   foreach ($lazadaUsers as $lazadaUser){
	       $lazadaUsersDropdownList[$lazadaUser['lazada_uid']] = $lazadaUser['platform_userid']."(".$siteIdNameMap[$lazadaUser['lazada_site']].")";
	       $lazada_uids[] = $lazadaUser['lazada_uid'];
	   };
	
	   $data = [];
	   $shop_name = [];
	   $query = LazadaPublishListing::find()->where(["platform"=>"lazada", 'lazada_uid' => $lazada_uids]);
	   $query->andWhere(["state"=>LazadaApiHelper::PUBLISH_LISTING_STATE_COMPLETE]);
	    
	   if(!empty($_REQUEST['shop_name'])){
	       $query->andWhere(["lazada_uid"=>$_REQUEST['shop_name']]);
	   }
	    
		if(!empty($_REQUEST['condition'])&&!empty($_REQUEST['condition_search'])){
	    	$_REQUEST['condition_search'] = trim($_REQUEST['condition_search']);
	    	if($_REQUEST['condition'] == 'title'){
	    		$searchStr = json_encode(['Name'=>".*".$_REQUEST['condition_search'].".*"]);
	    		$searchStr = substr($searchStr, 1 , strlen($searchStr) - 2);// 去掉 array的括号
	    		$searchStr = str_replace('\\','\\\\',$searchStr);
	    		$query->andWhere(["regexp","base_info",$searchStr]);
	    	}
	    	if($_REQUEST['condition'] == 'sku'){
	    		$searchStr = json_encode(['SellerSku'=>".*".$_REQUEST['condition_search'].".*"]);
	    		$searchStr = substr($searchStr, 1 , strlen($searchStr) - 2);// 去掉 array的括号
	    		$searchStr = str_replace('\\','\\\\',$searchStr);
	    		$query->andWhere(["regexp","variant_info",$searchStr]);
	    	}
	    }
	   
	   $pages = new Pagination(['totalCount' =>$query->count(), 'defaultPageSize'=>20 , 'pageSizeLimit'=>[5,200] , 'params'=>$_REQUEST]);
	   $lazadaList = $query->orderBy(['create_time'=>SORT_DESC])->offset($pages->offset)->limit($pages->limit)->asArray()->all();
	   foreach ($lazadaList as $lazadaList_detail){
	        $public_array = [];
	        $variants = [];
	        $variation = [];
	        $base_info = json_decode($lazadaList_detail['base_info'],true);
	        $variant_info = json_decode($lazadaList_detail['variant_info'],true);
	        $img_src = json_decode($lazadaList_detail['image_info'],true);
	        foreach ($variant_info as $variant_detail){//多个变参
	            $variants['sku'] = $variant_detail['SellerSku'] ;
	            $variants['quantity'] = empty($variant_detail['quantity'])?0:$variant_detail['quantity'];
	            $variants['price'] = empty($variant_detail['price'])?0:$variant_detail['price'];
	            $variation[] = $variants;
	            
	            // 第一个变参的首图作缩略图为产品缩略图
	            if(empty($public_array['image']) && !empty($img_src) && !empty($img_src['product_photo_thumbnails']) 
	                    && !empty($img_src['product_photo_thumbnails'][$variant_detail['skuSelData']]))
	            $public_array['image'] = $img_src['product_photo_thumbnails'][$variant_detail['skuSelData']][0];
	        }
	        $public_array['id'] = $lazadaList_detail['id'];
	        $public_array['title'] = empty($base_info['name']) ? '' : $base_info['name'];
	        $public_array['variation'] = $variation;
	        $public_array['shop_name'] = isset($lazadaUsersDropdownList[$lazadaList_detail['lazada_uid']])?$lazadaUsersDropdownList[$lazadaList_detail['lazada_uid']]:"";
	        $public_array['create_time'] = date("Y-m-d H:i:s",$lazadaList_detail['create_time']);
	        empty($public_array['image'])?$public_array['image']='':'';
	        $public_array['status'] = $lazadaList_detail['status'];
	        $data[]=$public_array;
	    };
	    // 	    print_r($data);exit();
	    // 	    print_r($lazadaList);exit();
	    return $this->render('lzdPublishSuccess',['pages'=>$pages,'data'=>$data,'shop_name'=>$lazadaUsersDropdownList,'activeMenu'=>'发布成功']);
	}
	
	// lazada 刊登 添加产品
	public function actionCreateProduct(){
	    AppTrackerApiHelper::actionLog("listing_lazada", "/listing/lazada-listing/create-product");
		$puid = \Yii::$app->user->identity->getParentUid();
		//只显示有权限的账号，lrq20170828
		$account_data = \eagle\modules\platform\apihelpers\PlatformAccountApi::getPlatformAuthorizeAccounts('lazada');
		$selleruserids = array();
		foreach($account_data as $key => $val){
			$selleruserids[] = $key;
		}
		$lazadaUsers= SaasLazadaUser::find()->where(['puid'=>$puid,"platform"=>"lazada",'platform_userid'=>$selleruserids])->andWhere('status <> 3')->asArray()->all();// 不包括解绑用户
		$siteIdNameMap = LazadaApiHelper::getLazadaCountryCodeSiteMapping('lazada');
		$lazadaUsersDropdownList = array();
		foreach ($lazadaUsers as $lazadaUser){
			$lazadaUsersDropdownList[$lazadaUser['lazada_uid']] = $lazadaUser['platform_userid']."(".$siteIdNameMap[$lazadaUser['lazada_site']].")";
		}
		
		return $this->render('createOrEditProduct',['type'=>"add",'lazadaUsersDropdownList'=>$lazadaUsersDropdownList] );
	}
	
	// 保存待发布产品 或 保存后再刊登
	public function actionSaveProduct(){
	    AppTrackerApiHelper::actionLog("listing_lazada", "/listing/lazada-listing/save-product");
		// 1. 组织保存各类Info
		$storeInfo = array();
		$storeInfo['primaryCategory'] = $_POST['primaryCategory'];
		$storeInfo['categories'] = json_decode($_POST['categories'],true);
		
		$variantInfo = $_POST['skus'];
		$variantData = json_decode($variantInfo,true);
		$productData = json_decode($_POST['productDataStr'],true);
		
		// 清空空值的key,以及trim 所有value
		Helper_Array::removeEmpty($storeInfo,true);
		Helper_Array::removeEmpty($variantData,true);
		Helper_Array::removeEmpty($productData,true);
		
		$variantInfo = json_encode($variantData);
		
		$baseInfo = $productData['base-info'];
		$imageInfo = $productData['image-info'];
		$descriptionInfo = $productData['description-info'];
		$shippintInfo = $productData['shipping-info'];
		$warrantyInfo = $productData['warranty-info'];
		
		// 允许编辑/保存 产品的状态
		$editStatus = array(
				LazadaApiHelper::$PUBLISH_LISTING_STATES_STATUS_MAP[LazadaApiHelper::PUBLISH_LISTING_STATE_DRAFT][0],
		);
		$editStatus = array_merge($editStatus,LazadaApiHelper::$PUBLISH_LISTING_STATES_STATUS_MAP[LazadaApiHelper::PUBLISH_LISTING_STATE_FAIL]);
		
		$nowTime = time();
		$isNew = false;
		if(!empty($_POST['id'])){
			$publishListing = LazadaPublishListing::findOne($_POST['id']);
			if(empty($publishListing)){
				return ResultHelper::getResult(400, '', "产品不存在！");
			}else if(!in_array($publishListing->status, $editStatus)){
				return ResultHelper::getResult(400, '', "产品正在处理中，不允许编辑和保存！");
			}
		}else{
			$publishListing = new LazadaPublishListing();
			$publishListing->create_time = $nowTime;
			$isNew = true;
		}

		$lazadaUser= SaasLazadaUser::findOne(['lazada_uid'=>$_POST['lazada_uid']]);
		if(empty($lazadaUser)){
			return ResultHelper::getResult(400, '', "账号不存在");
		}
		
		$publishListing->lazada_uid = $lazadaUser->lazada_uid;
		$publishListing->platform = $lazadaUser->platform;
		$publishListing->site = $lazadaUser->lazada_site;
		
		// 其他 从失败状态进入编辑的产品都 保存时候都需要转换状态LazadaApiHelper::PUBLISH_LISTING_STATE_DRAFT
		$publishListing->state = LazadaApiHelper::PUBLISH_LISTING_STATE_DRAFT;
		$status = LazadaApiHelper::$PUBLISH_LISTING_STATES_STATUS_MAP[LazadaApiHelper::PUBLISH_LISTING_STATE_DRAFT][0];
		$publishListing->status = $status;
		$publishListing->store_info = json_encode($storeInfo);
		$publishListing->base_info = json_encode($baseInfo);
		$publishListing->variant_info = $variantInfo;
		$publishListing->image_info = json_encode($imageInfo);
		$publishListing->description_info = json_encode($descriptionInfo);
		$publishListing->shipping_info = json_encode($shippintInfo);
		$publishListing->warranty_info = json_encode($warrantyInfo);
		$publishListing->update_time = $nowTime;
		
		if(!$publishListing->save()){
			\Yii::error('$publishListing->save() fail , error:'.print_r($publishListing->errors,true),"file");
			return ResultHelper::getResult(400, '', "刊登产品保存失败，请重试。");
		}
		
		if($isNew){// 保存历史选择目录
			$path = LazadaApiHelper::getSelectedCategoryHistoryPath('lazada');
			$path = $path.$publishListing->lazada_uid;
			$historyCatsStr = ConfigHelper::getConfig($path);
			$historyCats = array();
			if(empty($historyCatsStr)){
				$historyCats[] = $_POST['primaryCategory'];
			}else{
				$historyCats = json_decode($historyCatsStr,true); // 不能为空
				if(!in_array($_POST['primaryCategory'], $historyCats)){
					$historyCats[] = $_POST['primaryCategory'];
				}
			}
					
			ConfigHelper::setConfig($path, json_encode($historyCats));// config 记录字段大小有限，所以这里只记录目录id，不记录目录信息
			
		}
		
		// 发布产品
		if(isset($_POST['op']) && $_POST['op'] == 2){
		    AppTrackerApiHelper::actionLog("listing_lazada", "/listing/lazada-listing/do-publish");
		    if(!empty($lazadaUser->version)){//新用户授权
		        list($ret,$message) = SaasLazadaAutoSyncApiHelperV4::productPublish(array($publishListing->id));
		    }else{
		        list($ret,$message) = SaasLazadaAutoSyncApiHelperV3::productPublish(array($publishListing->id));
		    }
			if($ret == false){
				return ResultHelper::getResult(400, '', $message);
			}else{
				return ResultHelper::getResult(200, '', "产品保存并提交发布成功，发布结果请留意产品后续提示。");
			}
		}
		
		return ResultHelper::getResult(200, '', "刊登产品保存成功。");
	}
	
	// 编辑待待发布产品 或 保存后再刊登
	// listing/lazada-listing/edit-product
	public function actionEditProduct(){
	    AppTrackerApiHelper::actionLog("listing_lazada", "/listing/lazada-listing/edit-product");
		$publishListing = null;
		$productId = 0;
		// status 本来是基于state的，所以这里就不过滤state了,除了 draft的draft和fail的所有状态，其他不允许进入编辑页面，以及进行保存
		$editStatus = array(
			LazadaApiHelper::$PUBLISH_LISTING_STATES_STATUS_MAP[LazadaApiHelper::PUBLISH_LISTING_STATE_DRAFT][0],
		);
		$editStatus = array_merge($editStatus,LazadaApiHelper::$PUBLISH_LISTING_STATES_STATUS_MAP[LazadaApiHelper::PUBLISH_LISTING_STATE_FAIL]);
		
		if(!empty($_GET['id'])){
			$publishListing = LazadaPublishListing::findOne(['id'=>$_GET['id'],'status'=>$editStatus,]);
		}
			
		$productData = array();
		$variantData = array();
		$storeInfo = array();
		$imageInfo = array();
		$lzd_uid = 0;
		if(!empty($publishListing)){
			$productId = $publishListing->id;
			$lzd_uid = $publishListing->lazada_uid;
			$storeInfo = json_decode($publishListing->store_info,true);
			if(!isset($storeInfo['categories']) && isset($storeInfo['primaryCategory']) && isset($publishListing->lazada_uid)){// 获取目录Id
				$categoryIds = LazadaApiHelper::getAllCatIdsByPrimaryCategory($storeInfo['primaryCategory'],$publishListing->lazada_uid);
				$storeInfo['categories'] = $categoryIds;
				$publishListing->store_info = json_encode($storeInfo);
				if(!$publishListing->save()){
					\Yii::error('$publishListing->save() fail , error:'.print_r($publishListing->errors,true),"file");
				}
			}
			
			$productData['base-info'] = json_decode($publishListing->base_info,true);
			$productData['image-info'] = json_decode($publishListing->image_info,true);
			$productData['description-info'] = json_decode($publishListing->description_info,true);
			$productData['shipping-info'] = json_decode($publishListing->shipping_info,true);
			$productData['warranty-info'] = json_decode($publishListing->warranty_info,true);
			
			$variantData = json_decode($publishListing->variant_info,true);
		}

		$productDataStr = json_encode($productData, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);
		$variantDataStr = json_encode($variantData, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);
		$puid = \Yii::$app->user->identity->getParentUid();
		$lazadaUsers= SaasLazadaUser::find()->where(['puid'=>$puid,"platform"=>"lazada"])->andWhere('status <> 3')->asArray()->all();// 不包括解绑用户
		$siteIdNameMap = LazadaApiHelper::getLazadaCountryCodeSiteMapping('lazada');
		$lazadaUsersDropdownList = array();
		foreach ($lazadaUsers as $lazadaUser){
			$lazadaUsersDropdownList[$lazadaUser['lazada_uid']] = $lazadaUser['platform_userid']."(".$siteIdNameMap[$lazadaUser['lazada_site']].")";
		}
		
		return $this->render('createOrEditProduct',['type'=>"edit",'lazadaUsersDropdownList'=>$lazadaUsersDropdownList,'productId'=>$productId,
				'productDataStr'=>$productDataStr,'variantDataStr'=>$variantDataStr,'storeInfo'=>$storeInfo,'lzd_uid'=>$lzd_uid,'imageInfo'=>$imageInfo]);
	}
	
	// listing/lazada-listing/get-category-tree
	// 获取目录树 
	// @todo 通知上线前，先在线上把所有站点的目录获取回来，以免第一次获取时时间过长。
	public function actionGetCategoryTree(){
		$lazada_uid = $_POST['lazada_uid'];
		$parentCategoryId = $_POST['parentCategoryId'];
// 		$lazada_uid = $_GET['lazada_uid'];
// 		$parentCategoryId = $_GET['parentCategoryId'];
		$lazadaUser = SaasLazadaUser::find()->where(['lazada_uid'=>$lazada_uid])->one();
		
		if(!empty($lazadaUser)){
		    if(!empty($lazadaUser->version)){//新授权
		        $config = array(
					"userId"=>$lazadaUser->platform_userid,
					"apiKey"=>$lazadaUser->access_token,
					"countryCode"=>$lazadaUser->lazada_site
    			);
    			
    			list($ret,$categories) = SaasLazadaAutoSyncApiHelperV4::getCategoryTree($config);
		    }else{
		        $config = array(
					"userId"=>$lazadaUser->platform_userid,
					"apiKey"=>$lazadaUser->token,
					"countryCode"=>$lazadaUser->lazada_site
    			);
    			
    			list($ret,$categories) = SaasLazadaAutoSyncApiHelper::getCategoryTree($config);
		    }
			
			if($ret == true){
				$rtnCats = array();
				foreach ($categories as $category){
					if($category['parentCategoryId'] == $parentCategoryId){
						$rtnCats[] = $category;
					}
				}
				$result = ResultHelper::getResult(200, $rtnCats , "" , 0);
				return json_encode($result, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);
			}else{
				return ResultHelper::getResult(400 , '' ,$categories);
			}
		}else{
			return ResultHelper::getResult(400 , '' ,'账号不存在，请重新绑定。');
		}
	}
	
	// listing/lazada-listing/get-category-attrs
	// lazada 刊登 确认PrimaryCategory 动态添加属性
	public function actionGetCategoryAttrs(){
		$lazada_uid = $_POST['lazada_uid'];
		$primaryCategory = $_POST['primaryCategory'];
// 		$lazada_uid = $_GET['lazada_uid'];
// 		$primaryCategory = $_GET['primaryCategory'];
		$lazadaUser = SaasLazadaUser::find()->where(['lazada_uid'=>$lazada_uid])->andWhere('status <> 3')->one();// 不包括解绑账号
		if(!empty($lazadaUser->version)){//新帐号授权		    
		    $config = array(
		        "userId"=>$lazadaUser->platform_userid,
		        "apiKey"=>$lazadaUser->access_token,
		        "countryCode"=>$lazadaUser->lazada_site
		    );
		    
		    // 1.检查 类目是否存在 以及是否为子类目
		    list($ret , $categories) = SaasLazadaAutoSyncApiHelperV4::getCategoryTree($config);
		}else{
		    $config = array(
		        "userId"=>$lazadaUser->platform_userid,
		        "apiKey"=>$lazadaUser->token,
		        "countryCode"=>$lazadaUser->lazada_site
		    );
		    
		    // 1.检查 类目是否存在 以及是否为子类目
		    list($ret , $categories) = SaasLazadaAutoSyncApiHelper::getCategoryTree($config);
		}
		
		if($ret == false){
			return ResultHelper::getResult(400, "" , $categories);
		}
		
		$rtnCats = array();
		$check = false;
		foreach ($categories as $category){
			if($category['categoryId'] == $primaryCategory){
				if($category['isLeaf']){
					$check = true;
				}else{
					return ResultHelper::getResult(400, "" , TranslateHelper::t("类目非子类目，请选择子类目！"));
				}
			}
		}
		
		if(!$check){
			return ResultHelper::getResult(400, "" , TranslateHelper::t("不存在该类目，请选择子类目！"));
		}
		
		// 2.获取目录属性
		if(!empty($lazadaUser->version)){//新授权
		    list($ret,$attrs) = SaasLazadaAutoSyncApiHelperV4::getCategoryAttributes($config,$primaryCategory);
		}else{
		    list($ret,$attrs) = SaasLazadaAutoSyncApiHelper::getCategoryAttributes($config,$primaryCategory);
		}
		
		if($ret == false){
			return ResultHelper::getResult(400, "" , $attrs);
		}
		
		$result = ResultHelper::getResult(200, $attrs , "" , 0);
		return json_encode($result, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);
		
	}
	// listing/lazada-listing/get-all-categoryids
	// 通过PrimaryCategory id 来获取所有 包括自身和父目录的 目录id
	public function actionGetAllCategoryids(){
		$lazada_uid = $_POST['lazada_uid'];
		$primaryCategory = $_POST['primaryCategory'];
		list($ret,$categoryIds) = LazadaApiHelper::getAllCatIdsByPrimaryCategory($primaryCategory,$lazada_uid);
		if(false == $ret){
			return ResultHelper::getResult(400, "" , TranslateHelper::t("获取目录id失败"));
		}else{
			return ResultHelper::getResult(200, $categoryIds , "");
		}
	}
	
	// listing/lazada-listing/get-selected-category-history
	// lazada 刊登 获取选择过的目录
	public function actionGetSelectedCategoryHistory(){
		$lazada_uid = $_POST['lazada_uid'];
		$path = LazadaApiHelper::getSelectedCategoryHistoryPath('lazada');
		$path = $path.$lazada_uid;
		
		$historyCatIdsStr = ConfigHelper::getConfig($path);
		$historyCatIds = json_decode($historyCatIdsStr,true);
		
		$lazadaUser= SaasLazadaUser::findOne(['lazada_uid'=>$lazada_uid]);
		if(empty($lazadaUser)){
			return ResultHelper::getResult(400, '', "账号不存在");
		}
		if(!empty($lazadaUser->version)){//新授权
		    $config = array(
		        "userId"=>$lazadaUser->platform_userid,
		        "apiKey"=>$lazadaUser->access_token,
		        "countryCode"=>$lazadaUser->lazada_site
		    );
		    list($ret,$categories) = SaasLazadaAutoSyncApiHelperV4::getCategoryTree($config);
		}else{
		    $config = array(
		        "userId"=>$lazadaUser->platform_userid,
		        "apiKey"=>$lazadaUser->token,
		        "countryCode"=>$lazadaUser->lazada_site
		    );
		    list($ret,$categories) = SaasLazadaAutoSyncApiHelper::getCategoryTree($config);
		}
		
		if($ret == true){
			$historyCats = array();
			if(!empty($historyCatIds)){
				foreach ($historyCatIds as $historyCatId){
				    if(!empty($categories[$historyCatId])){
					   $historyCats[] = $categories[$historyCatId];
				    }else{// lazada那边过期被清理的目录cat id @todo 从数据库删除没用的
				        
				    }
				}
			}
			
			return ResultHelper::getResult(200, $historyCats, "");
		}else{
			\Yii::error("actionSaveProduct save history cat fail for:Can not get categories info".$categories,"file");
		}
		
		return ResultHelper::getResult(400, '', "无法获取历史目录");
	}	
	
	// listing/lazada-listing/get-brands
	// lazada 刊登 
	public function actionGetBrands(){
		$lazada_uid = $_POST['lazada_uid'];
	
		$lazadaUser= SaasLazadaUser::findOne(['lazada_uid'=>$lazada_uid]);
		if(empty($lazadaUser)){
			return ResultHelper::getResult(400, '', "账号不存在");
		}
		
		if(!empty($_POST['mode']) && 'eq' == $_POST['mode']){
		    $mode = $_POST['mode'];
		}else{
		    $mode = "like";
		}
		
		if(!empty($lazadaUser->version)){//新授权
		    $config = array(
		        "userId"=>$lazadaUser->platform_userid,
		        "apiKey"=>$lazadaUser->access_token,
		        "countryCode"=>$lazadaUser->lazada_site
		    );
		    
		    list($ret,$brands) = SaasLazadaAutoSyncApiHelperV4::getBrands($config,$_POST['name'],$mode);
		}else{
		    $config = array(
		        "userId"=>$lazadaUser->platform_userid,
		        "apiKey"=>$lazadaUser->token,
		        "countryCode"=>$lazadaUser->lazada_site
		    );
		    
		    list($ret,$brands) = SaasLazadaAutoSyncApiHelper::getBrands($config,$_POST['name'],$mode);
		}
		
		
		if($ret == true){
			return ResultHelper::getResult(200, $brands, "");
		}else{
			\Yii::info("actionGetBrands :Can not get categories info:".$brands,"file");
		}
	
		return ResultHelper::getResult(400, '', "无法获取品牌");
	}
	
	// listing/lazada-listing/list-references
	// 引用产品list 产品是在线产品
	public function actionListReferences(){
// 		AppTrackerApiHelper::actionLog("eagle_v2","/listing/lazada-listing/list-references");

		\Yii::$app->request->setQueryParams($_REQUEST);
		
		$sortConfig = new ESort([ 'isAjax'=>true, 'attributes' => ['SellerSku','ParentSku']]);
    	foreach ($sortConfig->getOrders() as $name=>$direction){
    		$sort = $name;
    		$order = $direction === SORT_ASC ? 'asc' : 'desc';
    		if(!$sortConfig->enableMultiSort)
    			break;
    	}
    	if(empty($sort))$sort = 'update_time';
    	if(empty($order))$order = 'desc';
    	
    	$puid = \Yii::$app->user->identity->getParentUid();
    	$lazadaUsers= SaasLazadaUser::find()->where(['puid'=>$puid,"platform"=>"lazada"])->andWhere('status <> 3')->asArray()->all();// 不包括解绑用户
    	// 过滤 lazada 在线listing
    	$lazadaIds = array();
    	foreach ($lazadaUsers as $lazadaUser){
    		$lazadaIds[] = $lazadaUser['lazada_uid'];
    	}
    	
    	$query = LazadaListing::find()->where(["lazada_uid_id"=>$lazadaIds]);
    	
    	if (!empty($_REQUEST['lazada_uid'])){
    		//搜索卖家账号
    		$query->andWhere('lazada_uid_id = :s',[':s'=>$_REQUEST['lazada_uid']]);
    	}
    	
		if (!empty($_REQUEST['search_val'])){
			//搜索用户自选搜索条件
			if (in_array($_REQUEST['search_type'], ['Name','ParentSku','SellerSku'])){
				$key = $_REQUEST['search_type'];
				$searchval = $_REQUEST['search_val'];
				$query->andWhere("$key like '%$searchval%' ");
			}
		}
		
		$pagination = new Pagination(['defaultPageSize' => 20,'totalCount' => $query->count(),'pageSizeLimit'=>[5,200],]);
		$data['pagination'] = $pagination;
		
		$data['data'] = $query
		->offset($pagination->offset)
		->limit($pagination->limit)
		->orderBy($sort.' '.$order)
		->asArray()
		->all();
		
		// 获取所有店铺
		$siteIdNameMap = LazadaApiHelper::getLazadaCountryCodeSiteMapping("lazada");
		$lazadaUsersDropdownList = array();
		foreach ($lazadaUsers as $lazadaUser){
			$lazadaUsersDropdownList[$lazadaUser['lazada_uid']] = $lazadaUser['platform_userid']."(".$siteIdNameMap[$lazadaUser['lazada_site']].")";
		}
		return $this->renderAjax('referrencesList' , ['references' => $data , 'sort'=>$sortConfig ,'lazadaUsersDropdownList'=>$lazadaUsersDropdownList]);
	}
	
	// listing/lazada-listing/use-reference
	// 导入引用产品数据
	public function actionUseReference(){
		if(empty($_GET['listing_id'])){
			return "请选择引用产品";
		}
		
		$listing = LazadaListing::findOne($_GET['listing_id']);// 只通过parentSku 查找商品有可能出现 其他店铺相同parentSku的产品 。
		if(empty($listing)){
			return "引用产品不存在";
		}
		
		$lzd_uid = $listing->lazada_uid_id;
		
		$productData = array();
		$productData['base-info'] = array("Name"=>$listing->Name);
		$productData['image-info'] = array('Product_photo_primary'=>$listing->MainImage, 'Product_photo_others'=>'');
		
		// 引用商品引用的是商品而不是变体，不管是根据条件Product ID、SKU还是Parent SKU搜出来的都是商品。
		$variants = LazadaListing::find()->where(['lazada_uid_id'=>$listing->lazada_uid_id,'ParentSku'=>$listing->ParentSku])->asArray()->all();
		$variantData = array();
		foreach ($variants as $variant){
			$oneVariant = array();
			$oneVariant["Variation"] = $variant['Variation'];
			$oneVariant["SellerSku"] = $variant['SellerSku'];
			$oneVariant["Quantity"] = $variant['Quantity'];
			$oneVariant["Price"] = $variant['Price'];
			$oneVariant["ProductId"] = $variant['ProductId'];
			$oneVariant["SalePrice"] = $variant['SalePrice'];
			$oneVariant["SaleStartDate"] = !empty($variant['SaleStartDate'])?date("Y-m-d",$variant['SaleStartDate']):"";
			$oneVariant["SaleEndDate"] = !empty($variant['SaleEndDate'])?date("Y-m-d",$variant['SaleEndDate']):"";
			$oneVariant["Variation"] = $variant['Variation'];
			$variantData[] = $oneVariant;
		}
		
		$productDataStr = json_encode($productData, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);
	    $variantDataStr = json_encode($variantData, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);
	    
	    
	    $storeInfo = array();
	    $puid = \Yii::$app->user->identity->getParentUid();
	    $lazadaUsers= SaasLazadaUser::find()->where(['puid'=>$puid,"platform"=>"lazada"])->andWhere('status <> 3')->asArray()->all();// 不包括解绑用户
	    $siteIdNameMap = LazadaApiHelper::getLazadaCountryCodeSiteMapping("lazada");
	    $lazadaUsersDropdownList = array();
	    foreach ($lazadaUsers as $lazadaUser){
	        $lazadaUsersDropdownList[$lazadaUser['lazada_uid']] = $lazadaUser['platform_userid']."(".$siteIdNameMap[$lazadaUser['lazada_site']].")";
	    }
	    return $this->render('createOrEditProduct',['type'=>"reference",'lazadaUsersDropdownList'=>$lazadaUsersDropdownList,
	        'productDataStr'=>$productDataStr,'variantDataStr'=>$variantDataStr,'storeInfo'=>$storeInfo,'lzd_uid'=>$lzd_uid]);	}
	
	// listing/lazada-listing/do-publish
	// 产品发布
	public function actionDoPublish(){
	    AppTrackerApiHelper::actionLog("listing_lazada", "/listing/lazada-listing/do-publish");
		set_time_limit(0);
		ignore_user_abort(true);// 多账号多产品时 要轮着发布多个feed 时间较长
		
		if(empty($_GET['ids'])){
			return ResultHelper::getResult(400, '', "提交参数错误");
		}
		
		$ids = $_GET['ids'];
		$targetIds = explode(',', $ids);
		
// 		list($ret,$message) = SaasLazadaAutoSyncApiHelperV3::productPublish($targetIds);
		list($ret,$message) = SaasLazadaAutoSyncApiHelperV4::productPublish($targetIds);
		if($ret == false){
			return ResultHelper::getResult(400, '', $message);
		}else{
			return ResultHelper::getResult(200, '', $message);
		}
	}
	// listing/lazada-listing/copy-product
	public function actionCopyProduct(){
	    AppTrackerApiHelper::actionLog("listing_lazada", "/listing/lazada-listing/copy-product");
		if(!empty($_GET['id'])){
	        $publishListing = LazadaPublishListing::findOne(['id'=>$_GET['id']]);
	    }
	    	
	    $productData = array();
	    $variantData = array();
	    $storeInfo = array();
	    $lzd_uid = 0;
	    if(!empty($publishListing)){
	        $lzd_uid = $publishListing->lazada_uid;
	        $storeInfo = json_decode($publishListing->store_info,true);
	        if(!isset($storeInfo['categories']) && isset($storeInfo['primaryCategory']) && isset($publishListing->lazada_uid)){// 获取目录Id
	            $categoryIds = LazadaApiHelper::getAllCatIdsByPrimaryCategory($storeInfo['primaryCategory'],$publishListing->lazada_uid);
	            $storeInfo['categories'] = $categoryIds;
	            $publishListing->store_info = json_encode($storeInfo);
	            if(!$publishListing->save()){
	                \Yii::error('$publishListing->save() fail , error:'.print_r($publishListing->errors,true),"file");
	            }
	        }
	        	
	        $productData['base-info'] = json_decode($publishListing->base_info,true);
	        $productData['image-info'] = json_decode($publishListing->image_info,true);
	        $productData['description-info'] = json_decode($publishListing->description_info,true);
	        $productData['shipping-info'] = json_decode($publishListing->shipping_info,true);
	        $productData['warranty-info'] = json_decode($publishListing->warranty_info,true);
	        
	        $variantData = json_decode($publishListing->variant_info,true);
	    }
	    
	    $productDataStr = json_encode($productData, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);
	    $variantDataStr = json_encode($variantData, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);
	    
	    $puid = \Yii::$app->user->identity->getParentUid();
	    $lazadaUsers= SaasLazadaUser::find()->where(['puid'=>$puid,"platform"=>"lazada"])->andWhere('status <> 3')->asArray()->all();// 不包括解绑用户
	    $siteIdNameMap = LazadaApiHelper::getLazadaCountryCodeSiteMapping("lazada");
	    $lazadaUsersDropdownList = array();
	    foreach ($lazadaUsers as $lazadaUser){
	        $lazadaUsersDropdownList[$lazadaUser['lazada_uid']] = $lazadaUser['platform_userid']."(".$siteIdNameMap[$lazadaUser['lazada_site']].")";
	    }
	    return $this->render('createOrEditProduct',['type'=>"edit",'lazadaUsersDropdownList'=>$lazadaUsersDropdownList,
	        'productDataStr'=>$productDataStr,'variantDataStr'=>$variantDataStr,'storeInfo'=>$storeInfo,'lzd_uid'=>$lzd_uid]);
	
	}

	// listing/lazada-listing/delete
	public function actionDelete(){
	    AppTrackerApiHelper::actionLog("listing_lazada", "/listing/lazada-listing/delete");
		if(empty($_POST['ids'])){
			return ResultHelper::getResult(400, '', "提交参数错误");
		}
	
		$ids = $_POST['ids'];
		$targetIds = explode(',', $ids);
		$requestNum = count($targetIds);
		// dzt20160602 由于客户一旦出错一般很少再从我们系统操作了，所以产品这里不再加限制
		$condition = " id in (".implode(',', $targetIds).") and platform='lazada' ";
// 		$condition = " id in (".implode(',', $targetIds).") and uploaded_product is null and platform='lazada' ";
		try {
			$excuteNum = LazadaPublishListing::deleteAll($condition);

			// 判断如果在线产品里面已经全部 待审核或者是Live的，就可以删除
// 			$excuteNum = 0;
// 			$targets = LazadaPublishListing::findAll(['id'=>$targetIds,'platform'=>'lazada' ]);
// 			foreach ($targets as $target){
// 				if(empty($target->uploaded_product)){
// 					$target->delete();
// 					$excuteNum++;
// 				}else{
// 					$existingUpSkus = json_decode($target->uploaded_product,true);
// 					$readyToDelNum = LazadaListing::find()->where(['lazada_uid_id'=>$target->lazada_uid,'SellerSku'=>$existingUpSkus,'sub_status'=>['live','pending']])->count();
// 					if($readyToDelNum == count($existingUpSkus)){
// 						$target->delete();
// 						$excuteNum++;
// 					}
// 				}
// 			}
			
			$returnStr = "提交了 ".$requestNum." 个，成功删除 ".$excuteNum." 个。";
// 			if($excuteNum != $requestNum){
// 				$returnStr .= "已上传的产品不能被删除，请查看是否有被选产品已经上传过。";
// 			}
			return ResultHelper::getResult(200, "", $returnStr);
		}catch (\Exception $e){
			\Yii::error("actionDelete Exception".print_r($e,true),"file");
			return ResultHelper::getResult(400, '', $e->getMessage());
		}
	}
	
	// listing/lazada-listing/confirm-uploaded-product
	// 确认产品已经上传，此操作之后，再发布产品会覆盖已存在的产品数据
	public function actionConfirmUploadedProduct(){
	    AppTrackerApiHelper::actionLog("listing_lazada", "/listing/lazada-listing/confirm-uploaded-product");
	    if(empty($_POST['id'])){
	        return ResultHelper::getResult(400, '', "请选择产品。");
	    }
	     
	    if(!empty($_POST['id'])){
	        $publishListing = LazadaPublishListing::findOne(['id'=>$_POST['id']]);
	    }
	     
	    if(empty($publishListing)){
	        return ResultHelper::getResult(400, '', "选择的产品不存在。");
	    }
	     
// 	    list($ret,$message) = SaasLazadaAutoSyncApiHelperV3::confirmUploadedProduct($publishListing);
	    list($ret,$message) = SaasLazadaAutoSyncApiHelperV4::confirmUploadedProduct($publishListing);
	    if($ret == false){
	        return ResultHelper::getResult(400, '', $message);
	    }else{
	        return ResultHelper::getResult(200, '', $message);
	    }
	}
	
	
	
	
	
	
	
	
	
	
}