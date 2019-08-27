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
use Qiniu\json_decode;
class LinioListingController extends \eagle\components\Controller {
	
	public function actionIndex() {
		return $this->render('index' );
	}
	
	// 待发布列表  
	// listing/linio-listing/publish
	public $enableCsrfValidation = false;
	public function actionPublish(){
	    AppTrackerApiHelper::actionLog("listing_linio", "/listing/linio-listing/publish");
	    //只显示有权限的账号，lrq20170828
	    $account_data = \eagle\modules\platform\apihelpers\PlatformAccountApi::getPlatformAuthorizeAccounts('linio');
	    $selleruserids = array();
	    foreach($account_data as $key => $val){
	    	$selleruserids[] = $key;
	    }
	    //获取对应客户id的商铺名
	    $puid = \Yii::$app->user->identity->getParentUid();
	    $linioUsers= SaasLazadaUser::find()->where(['puid'=>$puid,"platform"=>"linio",'platform_userid'=>$selleruserids])->andWhere('status <> 3')->asArray()->all();// 不包括解绑用户
	    
	    $siteIdNameMap = LazadaApiHelper::getLazadaCountryCodeSiteMapping('linio');
	    $linioUsersDropdownList = array();
	    $lazada_uids = array();
	    foreach ($linioUsers as $linioUser){
	        $linioUsersDropdownList[$linioUser['lazada_uid']] = $linioUser['platform_userid']."(".$siteIdNameMap[$linioUser['lazada_site']].")";
	        $lazada_uids[] = $linioUser['lazada_uid'];
	    };
	    
	    $data = [];
	    $shop_name = [];
	    $query = LazadaPublishListing::find()->where(["platform"=>"linio", 'lazada_uid' => $lazada_uids]);
	    $query->andWhere(["state"=>LazadaApiHelper::PUBLISH_LISTING_STATE_DRAFT]);
	     
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
	    $linioList = $query->orderBy(['create_time'=>SORT_DESC])->offset($pages->offset)->limit($pages->limit)->asArray()->all();
	    foreach ($linioList as $linioList_detail){
	        $public_array = [];
	        $variants = [];
	        $variation = [];//拼接所有每个产品的变参
	        $base_info = json_decode($linioList_detail['base_info'],true);
	        $variant_info = json_decode($linioList_detail['variant_info'],true);
	        $img_src = json_decode($linioList_detail['image_info'],true);
	        foreach ($variant_info as $variant_detail){//多个变参
	            $variants['sku'] = $variant_detail['SellerSku'] ;
	            $variants['quantity'] = $variant_detail['Quantity'];
	            $variants['price'] = empty($variant_detail['Price'])?0:$variant_detail['Price'];
	            $variation[] = $variants;
	        };
	        $public_array['id'] = $linioList_detail['id'];
	        $public_array['title'] = $base_info['Name'];
	        $public_array['variation'] = $variation;
	        $public_array['shop_name'] = isset($linioUsersDropdownList[$linioList_detail['lazada_uid']])?$linioUsersDropdownList[$linioList_detail['lazada_uid']]:"";// 解绑没有
	        $public_array['create_time'] = date("Y-m-d H:i:s",$linioList_detail['create_time']);
	        $public_array['image'] = isset($img_src['Product_photo_primary_thumbnail'])?(!empty($img_src['Product_photo_primary_thumbnail'])?$img_src['Product_photo_primary_thumbnail']:$img_src['Product_photo_primary']):$img_src['Product_photo_primary'];
	        $public_array['status'] = $linioList_detail['status'];
	        $data[]=$public_array;
	    };
// 	    print_r($data);exit();
// 	    print_r($linioList);exit();
	    return $this->render('linioPublic',['pages'=>$pages,'data'=>$data,'shop_name'=>$linioUsersDropdownList,'activeMenu'=>'待发布']);
//         return $this->render('creat-tree',['pages'=>$pages,]);
	}
	

	// listing/linio-listing/publishing
	// 发布中list
	public function actionPublishing(){
	   AppTrackerApiHelper::actionLog("listing_linio", "/listing/linio-listing/publishing");
	   //只显示有权限的账号，lrq20170828
	   $account_data = \eagle\modules\platform\apihelpers\PlatformAccountApi::getPlatformAuthorizeAccounts('linio');
	   $selleruserids = array();
	   foreach($account_data as $key => $val){
	   	$selleruserids[] = $key;
	   }
	   //获取对应客户id的商铺名
	   $puid = \Yii::$app->user->identity->getParentUid();
	   $linioUsers= SaasLazadaUser::find()->where(['puid'=>$puid,"platform"=>"linio",'platform_userid'=>$selleruserids])->andWhere('status <> 3')->asArray()->all();// 不包括解绑用户
	    
	   $siteIdNameMap = LazadaApiHelper::getLazadaCountryCodeSiteMapping('linio');
	   $linioUsersDropdownList = array();
	   $lazada_uids = array();
	   foreach ($linioUsers as $linioUser){
	       $linioUsersDropdownList[$linioUser['lazada_uid']] = $linioUser['platform_userid']."(".$siteIdNameMap[$linioUser['lazada_site']].")";
	       $lazada_uids[] = $linioUser['lazada_uid'];
	   };
	   // 	    print_r($linioUsers);exit();
	   $data = [];
	   $shop_name = [];
	   
	   $query = LazadaPublishListing::find()->where(["platform"=>"linio", 'lazada_uid' => $lazada_uids]);
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
	   
	   $pages = new Pagination(['totalCount' =>$query->count(), 'defaultPageSize'=>20 , 'pageSizeLimit'=>[5,200] , 'params'=>$_REQUEST]);
	   $linioList = $query->orderBy(['create_time'=>SORT_DESC])->offset($pages->offset)->limit($pages->limit)->asArray()->all();
	   foreach ($linioList as $linioList_detail){
	       $public_array = [];
	       $variants = [];
	       $variation = [];
	       $base_info = json_decode($linioList_detail['base_info'],true);
	       $variant_info = json_decode($linioList_detail['variant_info'],true);
	       $img_src = json_decode($linioList_detail['image_info'],true);
	       foreach ($variant_info as $variant_detail){//多个变参
	           $variants['sku'] = $variant_detail['SellerSku'] ;
	           $variants['quantity'] = $variant_detail['Quantity'];
	           $variants['price'] = $variant_detail['Price'];
	           $variation[] = $variants;
	       };
	       $public_array['id'] = $linioList_detail['id'];
	       $public_array['title'] = $base_info['Name'];
	       $public_array['variation'] = $variation;
	       $public_array['shop_name'] = isset($linioUsersDropdownList[$linioList_detail['lazada_uid']])?$linioUsersDropdownList[$linioList_detail['lazada_uid']]:"";
	       $public_array['create_time'] = date("Y-m-d H:i:s",$linioList_detail['create_time']);
	       $public_array['image'] = isset($img_src['Product_photo_primary_thumbnail'])?(!empty($img_src['Product_photo_primary_thumbnail'])?$img_src['Product_photo_primary_thumbnail']:$img_src['Product_photo_primary']):$img_src['Product_photo_primary'];
	       $public_array['status'] = $linioList_detail['status'];
	       $data[]=$public_array;
	   };
	   // 	    print_r($data);exit();
	   // 	    print_r($linioList);exit();
	   return $this->render('linPublishing',['pages'=>$pages,'data'=>$data,'shop_name'=>$linioUsersDropdownList,'activeMenu'=>'发布中']);
	}
	
	// listing/linio-listing/publish-fail
	// 发布失败 list
	public function actionPublishFail(){
	   AppTrackerApiHelper::actionLog("listing_linio", "/listing/linio-listing/publish-fail");
	   //只显示有权限的账号，lrq20170828
	   $account_data = \eagle\modules\platform\apihelpers\PlatformAccountApi::getPlatformAuthorizeAccounts('linio');
	   $selleruserids = array();
	   foreach($account_data as $key => $val){
	   	$selleruserids[] = $key;
	   }
	    //获取对应客户id的商铺名
	    $puid = \Yii::$app->user->identity->getParentUid();
	    $linioUsers= SaasLazadaUser::find()->where(['puid'=>$puid,"platform"=>"linio",'platform_userid'=>$selleruserids])->andWhere('status <> 3')->asArray()->all();// 不包括解绑用户
	    
	    $siteIdNameMap = LazadaApiHelper::getLazadaCountryCodeSiteMapping('linio');
	    $linioUsersDropdownList = array();
	    $lazada_uids = array();
	    foreach ($linioUsers as $linioUser){
	        $linioUsersDropdownList[$linioUser['lazada_uid']] = $linioUser['platform_userid']."(".$siteIdNameMap[$linioUser['lazada_site']].")";
	        $lazada_uids[] = $linioUser['lazada_uid'];
	    };
// 	    print_r($linioUsers);exit();
	    $data = [];
	    $shop_name = [];
	    $query = LazadaPublishListing::find()->where(["platform"=>"linio", 'lazada_uid' => $lazada_uids]);
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
	    
	    $pages = new Pagination(['totalCount' =>$query->count(), 'defaultPageSize'=>20 , 'pageSizeLimit'=>[5,200] , 'params'=>$_REQUEST]);
	    $linioList = $query->orderBy(['create_time'=>SORT_DESC])->offset($pages->offset)->limit($pages->limit)->asArray()->all();
	    foreach ($linioList as $linioList_detail){
	        $public_array = [];
	        $variants = [];
	        $variation = [];
	        $base_info = json_decode($linioList_detail['base_info'],true);
	        $variant_info = json_decode($linioList_detail['variant_info'],true);
	        $img_src = json_decode($linioList_detail['image_info'],true);
	        foreach ($variant_info as $variant_detail){//多个变参
	            $variants['sku'] = $variant_detail['SellerSku'] ;
	            $variants['quantity'] = $variant_detail['Quantity'];
	            $variants['price'] = empty($variant_detail['Price'])?0:$variant_detail['Price'];
	            $variation[] = $variants;
	        };
	        $public_array['id'] = $linioList_detail['id'];
	        $public_array['title'] = $base_info['Name'];
	        $public_array['variation'] = $variation;
	        $public_array['shop_name'] = isset($linioUsersDropdownList[$linioList_detail['lazada_uid']])?$linioUsersDropdownList[$linioList_detail['lazada_uid']]:"";
			$public_array['create_time'] = date("Y-m-d H:i:s",$linioList_detail['create_time']);
			$public_array['feed_info'] = $linioList_detail['feed_info'];
	        $public_array['image'] = isset($img_src['Product_photo_primary_thumbnail'])?(!empty($img_src['Product_photo_primary_thumbnail'])?$img_src['Product_photo_primary_thumbnail']:$img_src['Product_photo_primary']):$img_src['Product_photo_primary'];
	        $public_array['status'] = $linioList_detail['status'];
	        $data[]=$public_array;
	    };
// 	    print_r($data);exit();
// 	    print_r($linioList);exit();
	    return $this->render('linPublishFail',['pages'=>$pages,'data'=>$data,'shop_name'=>$linioUsersDropdownList,'activeMenu'=>'发布失败']);
	}
	
	// listing/linio-listing/publish-success
	// 发布成功
	public function actionPublishSuccess(){
		//只显示有权限的账号，lrq20170828
		$account_data = \eagle\modules\platform\apihelpers\PlatformAccountApi::getPlatformAuthorizeAccounts('linio');
		$selleruserids = array();
		foreach($account_data as $key => $val){
			$selleruserids[] = $key;
		}
	    //获取对应客户id的商铺名
	    $puid = \Yii::$app->user->identity->getParentUid();
	    $linioUsers= SaasLazadaUser::find()->where(['puid'=>$puid,"platform"=>"linio",'platform_userid'=>$selleruserids])->andWhere('status <> 3')->asArray()->all();// 不包括解绑用户
	     
	    $siteIdNameMap = LazadaApiHelper::getLazadaCountryCodeSiteMapping('linio');
	    $linioUsersDropdownList = array();
	    $lazada_uids = array();
	    foreach ($linioUsers as $linioUser){
	        $linioUsersDropdownList[$linioUser['lazada_uid']] = $linioUser['platform_userid']."(".$siteIdNameMap[$linioUser['lazada_site']].")";
	        $lazada_uids[] = $linioUser['lazada_uid'];
	    };
	    // 	    print_r($linioUsers);exit();
	    $data = [];
	    $shop_name = [];
	    $query = LazadaPublishListing::find()->where(["platform"=>"linio", 'lazada_uid' => $lazada_uids]);
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
	    $linioList = $query->offset($pages->offset)->limit($pages->limit)->asArray()->all();
	    foreach ($linioList as $linioList_detail){
	        $public_array = [];
	        $variants = [];
	        $variation = [];
	        $base_info = json_decode($linioList_detail['base_info'],true);
	        $variant_info = json_decode($linioList_detail['variant_info'],true);
	        $img_src = json_decode($linioList_detail['image_info'],true);
	        foreach ($variant_info as $variant_detail){//多个变参
	            $variants['sku'] = $variant_detail['SellerSku'] ;
	            $variants['quantity'] = $variant_detail['Quantity'];
	            $variants['price'] = $variant_detail['Price'];
	            $variation[] = $variants;
	        };
	        $public_array['id'] = $linioList_detail['id'];
	        $public_array['title'] = $base_info['Name'];
	        $public_array['variation'] = $variation;
	        $public_array['shop_name'] = isset($linioUsersDropdownList[$linioList_detail['lazada_uid']])?$linioUsersDropdownList[$linioList_detail['lazada_uid']]:"";
	        $public_array['create_time'] = date("Y-m-d H:i:s",$linioList_detail['create_time']);//
	        $public_array['image'] = isset($img_src['Product_photo_primary_thumbnail'])?(!empty($img_src['Product_photo_primary_thumbnail'])?$img_src['Product_photo_primary_thumbnail']:$img_src['Product_photo_primary']):$img_src['Product_photo_primary'];
	        $public_array['status'] = $linioList_detail['status'];
	        $data[]=$public_array;
	    };
	    // 	    print_r($data);exit();
	    // 	    print_r($linioList);exit();
	    return $this->render('linPublishSuccess',['pages'=>$pages,'data'=>$data,'shop_name'=>$linioUsersDropdownList,'activeMenu'=>'发布成功']);
	}
	
	// linio 刊登 添加产品
	public function actionCreateProduct(){
	    AppTrackerApiHelper::actionLog("listing_linio", "/listing/linio-listing/create-product");
	    //只显示有权限的账号，lrq20170828
	    $account_data = \eagle\modules\platform\apihelpers\PlatformAccountApi::getPlatformAuthorizeAccounts('linio');
	    $selleruserids = array();
	    foreach($account_data as $key => $val){
	    	$selleruserids[] = $key;
	    }
		$puid = \Yii::$app->user->identity->getParentUid();
		$linioUsers= SaasLazadaUser::find()->where(['puid'=>$puid,"platform"=>"linio",'platform_userid'=>$selleruserids])->andWhere('status <> 3')->asArray()->all();// 不包括解绑用户
		$siteIdNameMap = LazadaApiHelper::getLazadaCountryCodeSiteMapping('linio');
		$linioUsersDropdownList = array();
		foreach ($linioUsers as $linioUser){
			$linioUsersDropdownList[$linioUser['lazada_uid']] = $linioUser['platform_userid']."(".$siteIdNameMap[$linioUser['lazada_site']].")";
		}
		
		return $this->render('createOrEditProduct',['type'=>"add",'linioUsersDropdownList'=>$linioUsersDropdownList,] );
	}
	
	// 保存待发布产品 或 保存后再刊登
	public function actionSaveProduct(){
	    AppTrackerApiHelper::actionLog("listing_linio", "/listing/linio-listing/save-product");
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
		
		// 如果没有设置主图，则自动从后面图片的第一张作为主图
		if(empty($imageInfo['Product_photo_primary']) && !empty($imageInfo['Product_photo_others'])){
			$others = explode("@,@", $imageInfo['Product_photo_others']);
			$imageInfo['Product_photo_primary'] = array_shift($others);
			$imageInfo['Product_photo_others'] = implode("@,@",$others);
		}
		if(empty($imageInfo['Product_photo_primary_thumbnail']) && !empty($imageInfo['Product_photo_others_thumbnail'])){
		    $others_thumbnail = explode("@,@", $imageInfo['Product_photo_others_thumbnail']);
		    $imageInfo['Product_photo_primary_thumbnail'] = array_shift($others_thumbnail);
		    $imageInfo['Product_photo_others_thumbnail'] = implode("@,@",$others_thumbnail);
		}
		
		
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

		$linioUser= SaasLazadaUser::findOne(['lazada_uid'=>$_POST['lazada_uid']]);
		if(empty($linioUser)){
			return ResultHelper::getResult(400, '', "账号不存在");
		}
		
		$publishListing->lazada_uid = $linioUser->lazada_uid;
		$publishListing->platform = $linioUser->platform;
		$publishListing->site = $linioUser->lazada_site;
		
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
			$path = LazadaApiHelper::getSelectedCategoryHistoryPath('linio');
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
		    AppTrackerApiHelper::actionLog("listing_linio", "/listing/linio-listing/do-publish");
			list($ret,$message) = SaasLazadaAutoSyncApiHelper::productPublish(array($publishListing->id));
			if($ret == false){
				return ResultHelper::getResult(400, '', $message);
			}else{
				return ResultHelper::getResult(200, '', "产品保存并提交发布成功，发布结果请留意产品后续提示。");
			}
		}
		
		return ResultHelper::getResult(200, '', "刊登产品保存成功。");
	}
	
	// 编辑待待发布产品 或 保存后再刊登
	// listing/linio-listing/edit-product
	public function actionEditProduct(){
	    AppTrackerApiHelper::actionLog("listing_linio", "/listing/linio-listing/edit-product");
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
		$lin_uid = 0;
		if(!empty($publishListing)){
			$productId = $publishListing->id;
			$lin_uid = $publishListing->lazada_uid;
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
// 			$productData['image-info'] = json_decode($publishListing->image_info,true);
			$productData['description-info'] = json_decode($publishListing->description_info,true);
			$productData['shipping-info'] = json_decode($publishListing->shipping_info,true);
			$productData['warranty-info'] = json_decode($publishListing->warranty_info,true);
			
			$variantData = json_decode($publishListing->variant_info,true);
			//image处理
			$images = json_decode($publishListing->image_info,true);
			if(!empty($images)){
			    if(isset($images['Product_photo_primary'])){
			        $imageInfo['Product_photo_primary'] = $images['Product_photo_primary'];
			    }
			    if(isset($images['Product_photo_others'])){
			        $diff_array = explode('@,@',$images['Product_photo_others']);
			        $other_photo = array_filter($diff_array);
			        $imageInfo['Product_photo_others'] = $other_photo;
			    }
			}
		}

		$productDataStr = json_encode($productData, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);
		$variantDataStr = json_encode($variantData, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);
		$puid = \Yii::$app->user->identity->getParentUid();
		$linioUsers= SaasLazadaUser::find()->where(['puid'=>$puid,"platform"=>"linio"])->andWhere('status <> 3')->asArray()->all();// 不包括解绑用户
		$siteIdNameMap = LazadaApiHelper::getLazadaCountryCodeSiteMapping('linio');
		$linioUsersDropdownList = array();
		foreach ($linioUsers as $linioUser){
			$linioUsersDropdownList[$linioUser['lazada_uid']] = $linioUser['platform_userid']."(".$siteIdNameMap[$linioUser['lazada_site']].")";
		}
		
		return $this->render('createOrEditProduct',['type'=>"edit",'linioUsersDropdownList'=>$linioUsersDropdownList,'productId'=>$productId,
				'productDataStr'=>$productDataStr,'variantDataStr'=>$variantDataStr,'storeInfo'=>$storeInfo,'lin_uid'=>$lin_uid,'imageInfo'=>$imageInfo]);
	}
	
	// listing/linio-listing/get-category-tree
	// 获取目录树 
	// @todo 通知上线前，先在线上把所有站点的目录获取回来，以免第一次获取时时间过长。
	public function actionGetCategoryTree(){
		$linio_uid = $_POST['lazada_uid'];
		$parentCategoryId = $_POST['parentCategoryId'];
// 		$linio_uid = $_GET['lazada_uid'];
// 		$parentCategoryId = $_GET['parentCategoryId'];
		$linioUser = SaasLazadaUser::find()->where(['lazada_uid'=>$linio_uid])->one();
		
		if(!empty($linioUser)){
			$config = array(
					"userId"=>$linioUser->platform_userid,
					"apiKey"=>$linioUser->token,
					"countryCode"=>$linioUser->lazada_site
			);
			
			list($ret,$categories) = SaasLazadaAutoSyncApiHelper::getCategoryTree($config);
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
	
	// listing/linio-listing/get-category-attrs
	// linio 刊登 确认PrimaryCategory 动态添加属性
	public function actionGetCategoryAttrs(){
		$linio_uid = $_POST['lazada_uid'];
		$primaryCategory = $_POST['primaryCategory'];
// 		$linio_uid = $_GET['lazada_uid'];
// 		$primaryCategory = $_GET['primaryCategory'];
		$linioUser = SaasLazadaUser::find()->where(['lazada_uid'=>$linio_uid])->andWhere('status <> 3')->one();// 不包括解绑账号
		
		$config = array(
				"userId"=>$linioUser->platform_userid,
				"apiKey"=>$linioUser->token,
				"countryCode"=>$linioUser->lazada_site
		);
		
		// 1.检查 类目是否存在 以及是否为子类目
		list($ret , $categories) = SaasLazadaAutoSyncApiHelper::getCategoryTree($config);
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
		list($ret,$attrs) = SaasLazadaAutoSyncApiHelper::getCategoryAttributes($config,$primaryCategory);
		if($ret == false){
			return ResultHelper::getResult(400, "" , $attrs);
		}
		
		$result = ResultHelper::getResult(200, $attrs , "" , 0);
		return json_encode($result, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);
		
	}
	// listing/linio-listing/get-all-categoryids
	// 通过PrimaryCategory id 来获取所有 包括自身和父目录的 目录id
	public function actionGetAllCategoryids(){
		$linio_uid = $_POST['lazada_uid'];
		$primaryCategory = $_POST['primaryCategory'];
		list($ret,$categoryIds) = LazadaApiHelper::getAllCatIdsByPrimaryCategory($primaryCategory,$linio_uid);
		if(false == $ret){
			return ResultHelper::getResult(400, "" , TranslateHelper::t("获取目录id失败"));
		}else{
			return ResultHelper::getResult(200, $categoryIds , "");
		}
	}
	
	// listing/linio-listing/get-selected-category-history
	// linio 刊登 获取选择过的目录
	public function actionGetSelectedCategoryHistory(){
		$linio_uid = $_POST['lazada_uid'];
		$path = LazadaApiHelper::getSelectedCategoryHistoryPath('linio');
		$path = $path.$linio_uid;
		
		$historyCatIdsStr = ConfigHelper::getConfig($path);
		$historyCatIds = json_decode($historyCatIdsStr,true);
		
		$linioUser= SaasLazadaUser::findOne(['lazada_uid'=>$linio_uid]);
		if(empty($linioUser)){
			return ResultHelper::getResult(400, '', "账号不存在");
		}
		$config = array(
				"userId"=>$linioUser->platform_userid,
				"apiKey"=>$linioUser->token,
				"countryCode"=>$linioUser->lazada_site
		);
		list($ret,$categories) = SaasLazadaAutoSyncApiHelper::getCategoryTree($config);
		if($ret == true){
			$historyCats = array();
			if(!empty($historyCatIds)){
				foreach ($historyCatIds as $historyCatId){
				    if(!empty($categories[$historyCatId])){
					   $historyCats[] = $categories[$historyCatId];
				    }else{// linio那边过期被清理的目录cat id @todo 从数据库删除没用的
				        
				    }
				}
			}
			
			return ResultHelper::getResult(200, $historyCats, "");
		}else{
			\Yii::error("actionSaveProduct save history cat fail for:Can not get categories info".$categories,"file");
		}
		
		return ResultHelper::getResult(400, '', "无法获取历史目录");
	}	
	
	// listing/linio-listing/get-brands
	// linio 刊登 
	public function actionGetBrands(){
		$linio_uid = $_POST['lazada_uid'];
	
		$linioUser= SaasLazadaUser::findOne(['lazada_uid'=>$linio_uid]);
		if(empty($linioUser)){
			return ResultHelper::getResult(400, '', "账号不存在");
		}
		
		$config = array(
				"userId"=>$linioUser->platform_userid,
				"apiKey"=>$linioUser->token,
				"countryCode"=>$linioUser->lazada_site
		);
		
		if(!empty($_POST['mode']) && 'eq' == $_POST['mode']){
			$mode = $_POST['mode'];
		}else{
			$mode = "like";
		}
		
		list($ret,$brands) = SaasLazadaAutoSyncApiHelper::getBrands($config,$_POST['name'],$mode);
		
		if($ret == true){
			return ResultHelper::getResult(200, $brands, "");
		}else{
			\Yii::error("actionSaveProduct save history cat fail for:Can not get categories info".$brands,"file");
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
    	$linioUsers= SaasLazadaUser::find()->where(['puid'=>$puid,"platform"=>"linio"])->andWhere('status <> 3')->asArray()->all();// 不包括解绑用户
    	// 过滤 lazada 在线listing
    	$linioIds = array();
    	foreach ($linioUsers as $linioUser){
    		$linioIds[] = $linioUser['lazada_uid'];
    	}
    	
    	$query = LazadaListing::find()->where(["lazada_uid_id"=>$linioIds]);
    	
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
		$siteIdNameMap = LazadaApiHelper::getLazadaCountryCodeSiteMapping("linio");
		$linioUsersDropdownList = array();
		foreach ($linioUsers as $linioUser){
			$linioUsersDropdownList[$linioUser['lazada_uid']] = $linioUser['platform_userid']."(".$siteIdNameMap[$linioUser['lazada_site']].")";
		}
		return $this->renderAjax('referrencesList' , ['references' => $data , 'sort'=>$sortConfig ,'linioUsersDropdownList'=>$linioUsersDropdownList]);
	}
	
	// listing/linio-listing/use-reference
	// 导入引用产品数据
	public function actionUseReference(){
		if(empty($_GET['listing_id'])){
			return "请选择引用产品";
		}
		
		$listing = LazadaListing::findOne($_GET['listing_id']);// 只通过parentSku 查找商品有可能出现 其他店铺相同parentSku的产品 。
		if(empty($listing)){
			return "引用产品不存在";
		}
		
		$lin_uid = $listing->lazada_uid_id;
		
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
	    $linioUsers= SaasLazadaUser::find()->where(['puid'=>$puid,"platform"=>"linio"])->andWhere('status <> 3')->asArray()->all();// 不包括解绑用户
	    $siteIdNameMap = LazadaApiHelper::getLazadaCountryCodeSiteMapping("linio");
	    $linioUsersDropdownList = array();
	    foreach ($linioUsers as $linioUser){
	        $linioUsersDropdownList[$linioUser['lazada_uid']] = $linioUser['platform_userid']."(".$siteIdNameMap[$linioUser['lazada_site']].")";
	    }
	    return $this->render('createOrEditProduct',['type'=>"reference",'linioUsersDropdownList'=>$linioUsersDropdownList,
	        'productDataStr'=>$productDataStr,'variantDataStr'=>$variantDataStr,'storeInfo'=>$storeInfo,'lin_uid'=>$lin_uid]);	}
	
	// listing/linio-listing/do-publish
	// 产品发布
	public function actionDoPublish(){
	    AppTrackerApiHelper::actionLog("listing_linio", "/listing/linio-listing/do-publish");
		set_time_limit(0);
		ignore_user_abort(true);// 多账号多产品时 要轮着发布多个feed 时间较长
		
		if(empty($_GET['ids'])){
			return ResultHelper::getResult(400, '', "提交参数错误");
		}
		
		$ids = $_GET['ids'];
		$targetIds = explode(',', $ids);
		list($ret,$message) = SaasLazadaAutoSyncApiHelper::productPublish($targetIds);
		if($ret == false){
			return ResultHelper::getResult(400, '', $message);
		}else{
			return ResultHelper::getResult(200, '', $message);
		}
	}
	// listing/linio-listing/copy-product
	public function actionCopyProduct(){
	    AppTrackerApiHelper::actionLog("listing_linio", "/listing/linio-listing/copy-product");
		if(!empty($_GET['id'])){
	        $publishListing = LazadaPublishListing::findOne(['id'=>$_GET['id']]);
	    }
	    	
	    $productData = array();
	    $variantData = array();
	    $storeInfo = array();
	    $imageInfo = array();
	    $lin_uid = 0;
	    if(!empty($publishListing)){
	        $lin_uid = $publishListing->lazada_uid;
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
// 	        $productData['image-info'] = json_decode($publishListing->image_info,true);
	        $productData['description-info'] = json_decode($publishListing->description_info,true);
	        $productData['shipping-info'] = json_decode($publishListing->shipping_info,true);
	        $productData['warranty-info'] = json_decode($publishListing->warranty_info,true);
	        	
	        $variantData = json_decode($publishListing->variant_info,true);
	        //image处理
	        $images = json_decode($publishListing->image_info,true);
	        if(!empty($images)){
	            if(isset($images['Product_photo_primary'])){
	                $imageInfo['Product_photo_primary'] = $images['Product_photo_primary'];
	            }
	            if(isset($images['Product_photo_others'])){
	                $diff_array = explode('@,@',$images['Product_photo_others']);
	                $other_photo = array_filter($diff_array);
	                $imageInfo['Product_photo_others'] = $other_photo;
	            }
	        }
	    }
	    
	    $productDataStr = json_encode($productData, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);
	    $variantDataStr = json_encode($variantData, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);
	    
	    $puid = \Yii::$app->user->identity->getParentUid();
	    $linioUsers= SaasLazadaUser::find()->where(['puid'=>$puid,"platform"=>"linio"])->andWhere('status <> 3')->asArray()->all();// 不包括解绑用户
	    $siteIdNameMap = LazadaApiHelper::getLazadaCountryCodeSiteMapping("linio");
	    $linioUsersDropdownList = array();
	    foreach ($linioUsers as $linioUser){
	        $linioUsersDropdownList[$linioUser['lazada_uid']] = $linioUser['platform_userid']."(".$siteIdNameMap[$linioUser['lazada_site']].")";
	    }
	    return $this->render('createOrEditProduct',['type'=>"edit",'linioUsersDropdownList'=>$linioUsersDropdownList,
	        'productDataStr'=>$productDataStr,'variantDataStr'=>$variantDataStr,'storeInfo'=>$storeInfo,'lin_uid'=>$lin_uid,'imageInfo'=>$imageInfo]);
	
	}

	// listing/linio-listing/delete
	public function actionDelete(){
	    AppTrackerApiHelper::actionLog("listing_linio", "/listing/linio-listing/delete");
		if(empty($_POST['ids'])){
			return ResultHelper::getResult(400, '', "提交参数错误");
		}
	
		$ids = $_POST['ids'];
		$targetIds = explode(',', $ids);
		$requestNum = count($targetIds);
		// dzt20160602 由于客户一旦出错一般很少再从我们系统操作了，所以产品这里不再加限制
		$condition = " id in (".implode(',', $targetIds).") and platform='linio' ";
// 		$condition = " id in (".implode(',', $targetIds).") and uploaded_product is null and platform='linio' ";
		try {
			$excuteNum = LazadaPublishListing::deleteAll($condition);
			
			// 判断如果在线产品里面已经全部 待审核或者是Live的，就可以删除
// 			$excuteNum = 0;
// 			$targets = LazadaPublishListing::findAll(['id'=>$targetIds,'platform'=>'linio' ]);
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
	
	// 多站点发布产品创建
	// listing/linio-listing/muti-site-publish
	public function actionMutiSitePublish(){
		AppTrackerApiHelper::actionLog("listing_linio", "/listing/linio-listing/muti-site-publish");
		//只显示有权限的账号，lrq20170828
		$account_data = \eagle\modules\platform\apihelpers\PlatformAccountApi::getPlatformAuthorizeAccounts('linio');
		$selleruserids = array();
		foreach($account_data as $key => $val){
			$selleruserids[] = $key;
		}
		
		$puid = \Yii::$app->user->identity->getParentUid();
		$linioUsers= SaasLazadaUser::find()->where(['puid'=>$puid,"platform"=>"linio",'platform_userid'=>$selleruserids])->andWhere('status <> 3')->asArray()->all();// 不包括解绑用户
		$siteIdNameMap = LazadaApiHelper::getLazadaCountryCodeSiteMapping('linio');
		$linioUsersDropdownList = array();
		$linioSitesDropdownList = array();
		foreach ($linioUsers as $linioUser){
			$linioUsersDropdownList[$linioUser['lazada_uid']] = $linioUser['platform_userid']."(".$siteIdNameMap[$linioUser['lazada_site']].")";
			$linioSitesDropdownList[$linioUser['lazada_uid']] = $siteIdNameMap[$linioUser['lazada_site']];
		}
		
		return $this->render('mutiSiteCreateOrCopyProd',['type'=>"add",'linioUsersDropdownList'=>$linioUsersDropdownList,'linioSitesDropdownList'=>$linioSitesDropdownList] );
	}
	
	// 复制到多站点发布产品
	// listing/linio-listing/copy-to-muti-site
	public function actionCopyToMutiSite(){
		AppTrackerApiHelper::actionLog("listing_linio", "/listing/linio-listing/copy-to-muti-site");
	
		if(!empty($_GET['id'])){
			$publishListing = LazadaPublishListing::findOne(['id'=>$_GET['id']]);
		}
		
		$productData = array();
		$variantData = array();
		$storeInfo = array();
		$imageInfo = array();
		$lin_uid = 0;
		if(!empty($publishListing)){
			$lin_uid = $publishListing->lazada_uid;
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
// 			$productData['image-info'] = json_decode($publishListing->image_info,true);
			$productData['description-info'] = json_decode($publishListing->description_info,true);
			$productData['shipping-info'] = json_decode($publishListing->shipping_info,true);
			$productData['warranty-info'] = json_decode($publishListing->warranty_info,true);
		
			$variantData = json_decode($publishListing->variant_info,true);
			//image处理
			$images = json_decode($publishListing->image_info,true);
			if(!empty($images)){
			    if(isset($images['Product_photo_primary'])){
			        $imageInfo['Product_photo_primary'] = $images['Product_photo_primary'];
			    }
			    if(isset($images['Product_photo_others'])){
			        $diff_array = explode('@,@',$images['Product_photo_others']);
			        $other_photo = array_filter($diff_array);
			        $imageInfo['Product_photo_others'] = $other_photo;
			    }
			}
			
		}
		 
		$productDataStr = json_encode($productData, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);
		$variantDataStr = json_encode($variantData, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);
		 
		$puid = \Yii::$app->user->identity->getParentUid();
		$linioUsers= SaasLazadaUser::find()->where(['puid'=>$puid,"platform"=>"linio"])->andWhere('status <> 3')->asArray()->all();// 不包括解绑用户
		$siteIdNameMap = LazadaApiHelper::getLazadaCountryCodeSiteMapping("linio");
		$linioUsersDropdownList = array();
		$linioSitesDropdownList = array();
		foreach ($linioUsers as $linioUser){
		    $linioUsersDropdownList[$linioUser['lazada_uid']] = $linioUser['platform_userid']."(".$siteIdNameMap[$linioUser['lazada_site']].")";
		    $linioSitesDropdownList[$linioUser['lazada_uid']] = $siteIdNameMap[$linioUser['lazada_site']];
		}
		return $this->render('mutiSiteCreateOrCopyProd',['type'=>"edit",'linioUsersDropdownList'=>$linioUsersDropdownList,'linioSitesDropdownList'=>$linioSitesDropdownList,
				'productDataStr'=>$productDataStr,'variantDataStr'=>$variantDataStr,'storeInfo'=>$storeInfo,'lin_uid'=>$lin_uid,'imageInfo'=>$imageInfo]);
	
	}
	
	// listing/linio-listing/save-muti-site
	public function actionSaveMutiSite(){
		AppTrackerApiHelper::actionLog("listing_linio", "/listing/linio-listing/save-muti-site");
		
		$allSiteData = $_POST['allSiteData'];
		
		$publishListingIds = array();
		foreach ($allSiteData as $oneSite){
			
			// 1. 组织保存各类Info
			$storeInfo = array();
			$storeInfo['primaryCategory'] = $oneSite['primaryCategory'];
			$storeInfo['categories'] = json_decode($oneSite['categories'],true);
			
			$variantInfo = $oneSite['skus'];
			$variantData = json_decode($variantInfo,true);
			$productData = json_decode($oneSite['productDataStr'],true);
			
			// 清空空值的key,以及trim 所有value
			Helper_Array::removeEmpty($storeInfo,true);
			Helper_Array::removeEmpty($variantData,true);
			Helper_Array::removeEmpty($productData,true);
			
			$variantInfo = json_encode($variantData);
			$baseInfo = array_merge($productData['base-info'],$productData['store-info']);
			$imageInfo = $productData['image-info'];
			
			// 如果没有设置主图，则自动从后面图片的第一张作为主图
			if(empty($imageInfo['Product_photo_primary']) && !empty($imageInfo['Product_photo_others'])){
				$others = explode("@,@", $imageInfo['Product_photo_others']);
				$imageInfo['Product_photo_primary'] = array_shift($others);
				$imageInfo['Product_photo_others'] = implode("@,@",$others);
			}
			if(empty($imageInfo['Product_photo_primary_thumbnail']) && !empty($imageInfo['Product_photo_others_thumbnail'])){
				$others_thumbnail = explode("@,@", $imageInfo['Product_photo_others_thumbnail']);
				$imageInfo['Product_photo_primary_thumbnail'] = array_shift($others_thumbnail);
				$imageInfo['Product_photo_others_thumbnail'] = implode("@,@",$others_thumbnail);
			}
			
			
			$descriptionInfo = $productData['description-info'];
			$shippintInfo = $productData['shipping-info'];
			$warrantyInfo = $productData['warranty-info'];
			
			// 允许编辑/保存 产品的状态
			$editStatus = array(
					LazadaApiHelper::$PUBLISH_LISTING_STATES_STATUS_MAP[LazadaApiHelper::PUBLISH_LISTING_STATE_DRAFT][0],
			);
			$editStatus = array_merge($editStatus,LazadaApiHelper::$PUBLISH_LISTING_STATES_STATUS_MAP[LazadaApiHelper::PUBLISH_LISTING_STATE_FAIL]);
			
			$nowTime = time();
			$publishListing = new LazadaPublishListing();
			$publishListing->create_time = $nowTime;
			
			// 同站点多账号，保存发布产品
			$lazadaUids = explode(',', $oneSite['lazada_uid']);
			$linioUsersNum = SaasLazadaUser::find()->where(['lazada_uid'=>$lazadaUids])->count();
			if(count($lazadaUids) != $linioUsersNum){
				return ResultHelper::getResult(400, '', "提交站点的账号有变动，请确认提交站点账号都正常。");
			}
			
			foreach ($lazadaUids as $lazadaUid){
				$linioUser = SaasLazadaUser::findOne(['lazada_uid'=>$lazadaUid]);
				if(empty($linioUser)){
					\Yii::error("actionSaveMutiSite lazadaUid:$lazadaUid not exists","file");
					continue;// 不中断，以防已经保存的产品重新保存导致重复 。 前面已经有账号检查，按道理这里不会出现账号不存在的情况，只是以防万一。
				}
				
				$publishListing->lazada_uid = $linioUser->lazada_uid;
				$publishListing->platform = $linioUser->platform;
				$publishListing->site = $linioUser->lazada_site;
					
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
					\Yii::error('actionSaveMutiSite $publishListing->save() fail , error:'.print_r($publishListing->errors,true),"file");
					return ResultHelper::getResult(400, '', "刊登产品保存失败，请重试。");
				}
				
				$publishListingIds[] = $publishListing->id;
				
				// 保存历史选择目录
				$path = LazadaApiHelper::getSelectedCategoryHistoryPath('linio');
				$path = $path.$publishListing->lazada_uid;
				$historyCatsStr = ConfigHelper::getConfig($path);
				$historyCats = array();
				if(empty($historyCatsStr)){
					$historyCats[] = $oneSite['primaryCategory'];
				}else{
					$historyCats = json_decode($historyCatsStr,true); // 不能为空
					if(!in_array($oneSite['primaryCategory'], $historyCats)){
						$historyCats[] = $oneSite['primaryCategory'];
					}
				}
					
				ConfigHelper::setConfig($path, json_encode($historyCats));// config 记录字段大小有限，所以这里只记录目录id，不记录目录信息
			}
		}
		
		// 发布产品
		if(isset($_POST['op']) && $_POST['op'] == 2){
			AppTrackerApiHelper::actionLog("listing_linio", "/listing/linio-listing/do-publish");
			list($ret,$message) = SaasLazadaAutoSyncApiHelper::productPublish($publishListingIds);
			if($ret == false){
				return ResultHelper::getResult(400, '', $message);
			}else{
				return ResultHelper::getResult(200, '', "产品保存并提交发布成功，发布结果请留意产品后续提示。");
			}
		}
		
		return ResultHelper::getResult(200, '', "刊登产品保存成功。");
	}
	
	// listing/linio-listing/confirm-uploaded-product
	// 确认产品已经上传，此操作之后，再发布产品会覆盖已存在的产品数据
	public function actionConfirmUploadedProduct(){
	    AppTrackerApiHelper::actionLog("listing_linio", "/listing/linio-listing/confirm-uploaded-product");
	    if(empty($_POST['id'])){
	        return ResultHelper::getResult(400, '', "请选择产品。");
	    }
	    
	    if(!empty($_POST['id'])){
	        $publishListing = LazadaPublishListing::findOne(['id'=>$_POST['id']]);
	    }
	    
	    if(empty($publishListing)){
	        return ResultHelper::getResult(400, '', "选择的产品不存在。");
	    }
	    
	    list($ret,$message) = SaasLazadaAutoSyncApiHelper::confirmUploadedProduct($publishListing);
	    if($ret == false){
	        return ResultHelper::getResult(400, '', $message);
	    }else{
	        return ResultHelper::getResult(200, '', $message);
	    }
	}
	
	
	
	
	
	
}