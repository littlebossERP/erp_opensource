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
class JumiaListingController extends \eagle\components\Controller {
	
	public function actionIndex() {
		return $this->render('index' );
	}
	
	// 待发布列表  
	// listing/jumia-listing/publish
	public $enableCsrfValidation = false;
	public function actionPublish(){
	    AppTrackerApiHelper::actionLog("listing_jumia", "/listing/jumia-listing/publish");
	    //获取对应客户id的商铺名
	    $puid = \Yii::$app->user->identity->getParentUid();
	    $jumiaUsers= SaasLazadaUser::find()->where(['puid'=>$puid,"platform"=>"jumia"])->andWhere('status <> 3')->asArray()->all();// 不包括解绑用户
	    
	    $siteIdNameMap = LazadaApiHelper::getLazadaCountryCodeSiteMapping('jumia');
	    $jumiaUsersDropdownList = array();
	    foreach ($jumiaUsers as $jumiaUser){
	        $jumiaUsersDropdownList[$jumiaUser['lazada_uid']] = $jumiaUser['platform_userid']."(".$siteIdNameMap[$jumiaUser['lazada_site']].")";
	    };
	    
	    $data = [];
	    $shop_name = [];
		$query = LazadaPublishListing::find()->where(["platform"=>"jumia"]);
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
	    $jumiaList = $query->orderBy(['create_time'=>SORT_DESC])->offset($pages->offset)->limit($pages->limit)->asArray()->all();
	    foreach ($jumiaList as $jumiaList_detail){
	        $public_array = [];
	        $variants = [];
	        $variation = [];//拼接所有每个产品的变参
	        $base_info = json_decode($jumiaList_detail['base_info'],true);
	        $variant_info = json_decode($jumiaList_detail['variant_info'],true);
	        $img_src = json_decode($jumiaList_detail['image_info'],true);
	        foreach ($variant_info as $variant_detail){//多个变参
	            $variants['sku'] = $variant_detail['SellerSku'] ;
	            $variants['quantity'] = $variant_detail['Quantity'];
	            $variants['price'] = $variant_detail['Price'];
	            $variation[] = $variants;
	        };
	        $public_array['id'] = $jumiaList_detail['id'];
	        $public_array['title'] = $base_info['Name'];
	        $public_array['variation'] = $variation;
	        $public_array['shop_name'] = isset($jumiaUsersDropdownList[$jumiaList_detail['lazada_uid']])?$jumiaUsersDropdownList[$jumiaList_detail['lazada_uid']]:"";// 解绑没有
	        $public_array['create_time'] = date("Y-m-d H:i:s",$jumiaList_detail['create_time']);
	        $public_array['image'] = isset($img_src['Product_photo_primary_thumbnail'])?(!empty($img_src['Product_photo_primary_thumbnail'])?$img_src['Product_photo_primary_thumbnail']:$img_src['Product_photo_primary']):$img_src['Product_photo_primary'];
	        $public_array['status'] = $jumiaList_detail['status'];
	        $data[]=$public_array;
	    };
// 	    print_r($data);exit();
// 	    print_r($jumiaList);exit();
	    return $this->render('jumiaPublic',['pages'=>$pages,'data'=>$data,'shop_name'=>$jumiaUsersDropdownList,'activeMenu'=>'待发布']);
//         return $this->render('creat-tree',['pages'=>$pages,]);
	}
	

	// listing/jumia-listing/publishing
	// 发布中list
	public function actionPublishing(){
	   AppTrackerApiHelper::actionLog("listing_jumia", "/listing/jumia-listing/publishing");
	   //获取对应客户id的商铺名
	   $puid = \Yii::$app->user->identity->getParentUid();
	   $jumiaUsers= SaasLazadaUser::find()->where(['puid'=>$puid,"platform"=>"jumia"])->andWhere('status <> 3')->asArray()->all();// 不包括解绑用户
	    
	   $siteIdNameMap = LazadaApiHelper::getLazadaCountryCodeSiteMapping('jumia');
	   $jumiaUsersDropdownList = array();
	   foreach ($jumiaUsers as $jumiaUser){
	       $jumiaUsersDropdownList[$jumiaUser['lazada_uid']] = $jumiaUser['platform_userid']."(".$siteIdNameMap[$jumiaUser['lazada_site']].")";
	   };
	   // 	    print_r($jumiaUsers);exit();
	   $data = [];
	   $shop_name = [];
	   $query = LazadaPublishListing::find()->where(["platform"=>"jumia"]);
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
	   $jumiaList = $query->orderBy(['create_time'=>SORT_DESC])->offset($pages->offset)->limit($pages->limit)->asArray()->all();
	   foreach ($jumiaList as $jumiaList_detail){
	       $public_array = [];
	       $variants = [];
	       $variation = [];
	       $base_info = json_decode($jumiaList_detail['base_info'],true);
	       $variant_info = json_decode($jumiaList_detail['variant_info'],true);
	       $img_src = json_decode($jumiaList_detail['image_info'],true);
	       foreach ($variant_info as $variant_detail){//多个变参
	           $variants['sku'] = $variant_detail['SellerSku'] ;
	           $variants['quantity'] = $variant_detail['Quantity'];
	           $variants['price'] = $variant_detail['Price'];
	           $variation[] = $variants;
	       };
	       $public_array['id'] = $jumiaList_detail['id'];
	       $public_array['title'] = $base_info['Name'];
	       $public_array['variation'] = $variation;
	       $public_array['shop_name'] = isset($jumiaUsersDropdownList[$jumiaList_detail['lazada_uid']])?$jumiaUsersDropdownList[$jumiaList_detail['lazada_uid']]:"";
	       $public_array['create_time'] = date("Y-m-d H:i:s",$jumiaList_detail['create_time']);
	       $public_array['image'] = isset($img_src['Product_photo_primary_thumbnail'])?(!empty($img_src['Product_photo_primary_thumbnail'])?$img_src['Product_photo_primary_thumbnail']:$img_src['Product_photo_primary']):$img_src['Product_photo_primary'];
	       $public_array['status'] = $jumiaList_detail['status'];
	       $data[]=$public_array;
	   };
	   // 	    print_r($data);exit();
	   // 	    print_r($jumiaList);exit();
	   return $this->render('jumPublishing',['pages'=>$pages,'data'=>$data,'shop_name'=>$jumiaUsersDropdownList,'activeMenu'=>'发布中']);
	}
	
	// listing/jumia-listing/publish-fail
	// 发布失败 list
	public function actionPublishFail(){
	   AppTrackerApiHelper::actionLog("listing_jumia", "/listing/jumia-listing/publish-fail");
	   $state = LazadaApiHelper::PUBLISH_LISTING_STATE_FAIL;
	    //获取对应客户id的商铺名
	    $puid = \Yii::$app->user->identity->getParentUid();
	    $jumiaUsers= SaasLazadaUser::find()->where(['puid'=>$puid,"platform"=>"jumia"])->andWhere('status <> 3')->asArray()->all();// 不包括解绑用户
	    
	    $siteIdNameMap = LazadaApiHelper::getLazadaCountryCodeSiteMapping('jumia');
	    $jumiaUsersDropdownList = array();
	    foreach ($jumiaUsers as $jumiaUser){
	        $jumiaUsersDropdownList[$jumiaUser['lazada_uid']] = $jumiaUser['platform_userid']."(".$siteIdNameMap[$jumiaUser['lazada_site']].")";
	    };
// 	    print_r($jumiaUsers);exit();
	    $data = [];
	    $shop_name = [];
		$query = LazadaPublishListing::find()->where(["platform"=>"jumia"]);
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
	    $jumiaList = $query->orderBy(['create_time'=>SORT_DESC])->offset($pages->offset)->limit($pages->limit)->asArray()->all();
	    foreach ($jumiaList as $jumiaList_detail){
	        $public_array = [];
	        $variants = [];
	        $variation = [];
	        $base_info = json_decode($jumiaList_detail['base_info'],true);
	        $variant_info = json_decode($jumiaList_detail['variant_info'],true);
	        $img_src = json_decode($jumiaList_detail['image_info'],true);
	        foreach ($variant_info as $variant_detail){//多个变参
	            $variants['sku'] = $variant_detail['SellerSku'] ;
	            $variants['quantity'] = $variant_detail['Quantity'];
	            $variants['price'] = $variant_detail['Price'];
	            $variation[] = $variants;
	        };
	        $public_array['id'] = $jumiaList_detail['id'];
	        $public_array['title'] = $base_info['Name'];
	        $public_array['variation'] = $variation;
	        $public_array['shop_name'] = isset($jumiaUsersDropdownList[$jumiaList_detail['lazada_uid']])?$jumiaUsersDropdownList[$jumiaList_detail['lazada_uid']]:"";
			$public_array['create_time'] = date("Y-m-d H:i:s",$jumiaList_detail['create_time']);
			$public_array['feed_info'] = $jumiaList_detail['feed_info'];
	        $public_array['image'] = isset($img_src['Product_photo_primary_thumbnail'])?(!empty($img_src['Product_photo_primary_thumbnail'])?$img_src['Product_photo_primary_thumbnail']:$img_src['Product_photo_primary']):$img_src['Product_photo_primary'];
	        $public_array['status'] = $jumiaList_detail['status'];
	        $data[]=$public_array;
	    };
// 	    print_r($data);exit();
// 	    print_r($jumiaList);exit();
	    return $this->render('jumPublishFail',['pages'=>$pages,'data'=>$data,'shop_name'=>$jumiaUsersDropdownList,'activeMenu'=>'发布失败']);
	}
	
	// listing/jumia-listing/publish-success
	// 发布成功
	public function actionPublishSuccess(){
	    //获取对应客户id的商铺名
	    $puid = \Yii::$app->user->identity->getParentUid();
	    $jumiaUsers= SaasLazadaUser::find()->where(['puid'=>$puid,"platform"=>"jumia"])->andWhere('status <> 3')->asArray()->all();// 不包括解绑用户
	     
	    $siteIdNameMap = LazadaApiHelper::getLazadaCountryCodeSiteMapping('jumia');
	    $jumiaUsersDropdownList = array();
	    foreach ($jumiaUsers as $jumiaUser){
	        $jumiaUsersDropdownList[$jumiaUser['lazada_uid']] = $jumiaUser['platform_userid']."(".$siteIdNameMap[$jumiaUser['lazada_site']].")";
	    };
	    // 	    print_r($jumiaUsers);exit();
	    $data = [];
	    $shop_name = [];
		$query = LazadaPublishListing::find()->where(["platform"=>"jumia"]);
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
	    $jumiaList = $query->orderBy(['create_time'=>SORT_DESC])->offset($pages->offset)->limit($pages->limit)->asArray()->all();
	    foreach ($jumiaList as $jumiaList_detail){
	        $public_array = [];
	        $variants = [];
	        $variation = [];
	        $base_info = json_decode($jumiaList_detail['base_info'],true);
	        $variant_info = json_decode($jumiaList_detail['variant_info'],true);
	        $img_src = json_decode($jumiaList_detail['image_info'],true);
	        foreach ($variant_info as $variant_detail){//多个变参
	            $variants['sku'] = $variant_detail['SellerSku'] ;
	            $variants['quantity'] = $variant_detail['Quantity'];
	            $variants['price'] = $variant_detail['Price'];
	            $variation[] = $variants;
	        };
	        $public_array['id'] = $jumiaList_detail['id'];
	        $public_array['title'] = $base_info['Name'];
	        $public_array['variation'] = $variation;
	        $public_array['shop_name'] = isset($jumiaUsersDropdownList[$jumiaList_detail['lazada_uid']])?$jumiaUsersDropdownList[$jumiaList_detail['lazada_uid']]:"";
	        $public_array['create_time'] = date("Y-m-d H:i:s",$jumiaList_detail['create_time']);//
	        $public_array['image'] = isset($img_src['Product_photo_primary_thumbnail'])?(!empty($img_src['Product_photo_primary_thumbnail'])?$img_src['Product_photo_primary_thumbnail']:$img_src['Product_photo_primary']):$img_src['Product_photo_primary'];
	        $public_array['status'] = $jumiaList_detail['status'];
	        $data[]=$public_array;
	    };
	    // 	    print_r($data);exit();
	    // 	    print_r($jumiaList);exit();
	    return $this->render('jumPublishSuccess',['pages'=>$pages,'data'=>$data,'shop_name'=>$jumiaUsersDropdownList,'activeMenu'=>'发布成功']);
	}
	
	// jumia 刊登 添加产品
	public function actionCreateProduct(){
	    AppTrackerApiHelper::actionLog("listing_jumia", "/listing/jumia-listing/create-product");
		$puid = \Yii::$app->user->identity->getParentUid();
		$jumiaUsers= SaasLazadaUser::find()->where(['puid'=>$puid,"platform"=>"jumia"])->andWhere('status <> 3')->asArray()->all();// 不包括解绑用户
		$siteIdNameMap = LazadaApiHelper::getLazadaCountryCodeSiteMapping('jumia');
		$jumiaUsersDropdownList = array();
		foreach ($jumiaUsers as $jumiaUser){
			$jumiaUsersDropdownList[$jumiaUser['lazada_uid']] = $jumiaUser['platform_userid']."(".$siteIdNameMap[$jumiaUser['lazada_site']].")";
		}
		
		return $this->render('createOrEditProduct',['type'=>"add",'jumiaUsersDropdownList'=>$jumiaUsersDropdownList] );
	}
	
	// 保存待发布产品 或 保存后再刊登
	public function actionSaveProduct(){
	    AppTrackerApiHelper::actionLog("listing_jumia", "/listing/jumia-listing/save-product");
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
		if(!empty($_POST['browseNodeCategories'])){//browseNode的id
		    $baseInfo["BrowseNodes"] = json_decode($_POST['browseNodeCategories'],true);
		}else{
		    $baseInfo["BrowseNodes"] = "";
		}
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

		$jumiaUser= SaasLazadaUser::findOne(['lazada_uid'=>$_POST['lazada_uid']]);
		if(empty($jumiaUser)){
			return ResultHelper::getResult(400, '', "账号不存在");
		}
		
		$publishListing->lazada_uid = $jumiaUser->lazada_uid;
		$publishListing->platform = $jumiaUser->platform;
		$publishListing->site = $jumiaUser->lazada_site;
		
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
			$path = LazadaApiHelper::getSelectedCategoryHistoryPath('jumia');
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
		    AppTrackerApiHelper::actionLog("listing_jumia", "/listing/jumia-listing/do-publish");
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
	// listing/jumia-listing/edit-product
	public function actionEditProduct(){
	    AppTrackerApiHelper::actionLog("listing_jumia", "/listing/jumia-listing/edit-product");
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
		$jum_uid = 0;
		if(!empty($publishListing)){
			$productId = $publishListing->id;
			$jum_uid = $publishListing->lazada_uid;
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

		
		$jumiaUsers= SaasLazadaUser::find()->where(['puid'=>$puid,"platform"=>"jumia"])->andWhere('status <> 3')->asArray()->all();// 不包括解绑用户
		$siteIdNameMap = LazadaApiHelper::getLazadaCountryCodeSiteMapping('jumia');
		$jumiaUsersDropdownList = array();
		foreach ($jumiaUsers as $jumiaUser){
			$jumiaUsersDropdownList[$jumiaUser['lazada_uid']] = $jumiaUser['platform_userid']."(".$siteIdNameMap[$jumiaUser['lazada_site']].")";
		}
		
		return $this->render('createOrEditProduct',['type'=>"edit",'jumiaUsersDropdownList'=>$jumiaUsersDropdownList,'productId'=>$productId,
				'productDataStr'=>$productDataStr,'variantDataStr'=>$variantDataStr,'storeInfo'=>$storeInfo,'jum_uid'=>$jum_uid]);
	}
	
	// listing/jumia-listing/get-category-tree
	// 获取目录树 
	// @todo 通知上线前，先在线上把所有站点的目录获取回来，以免第一次获取时时间过长。
	public function actionGetCategoryTree(){
		$jumia_uid = $_POST['lazada_uid'];
		$parentCategoryId = $_POST['parentCategoryId'];
// 		$jumia_uid = $_GET['lazada_uid'];
// 		$parentCategoryId = $_GET['parentCategoryId'];
		$jumiaUser = SaasLazadaUser::find()->where(['lazada_uid'=>$jumia_uid])->one();
		
		if(!empty($jumiaUser)){
			$config = array(
					"userId"=>$jumiaUser->platform_userid,
					"apiKey"=>$jumiaUser->token,
					"countryCode"=>$jumiaUser->lazada_site
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
	
	// listing/jumia-listing/get-category-attrs
	// jumia 刊登 确认PrimaryCategory 动态添加属性
	public function actionGetCategoryAttrs(){
		$jumia_uid = $_POST['lazada_uid'];
		$primaryCategory = $_POST['primaryCategory'];
// 		$jumia_uid = $_GET['lazada_uid'];
// 		$primaryCategory = $_GET['primaryCategory'];
		$jumiaUser = SaasLazadaUser::find()->where(['lazada_uid'=>$jumia_uid])->andWhere('status <> 3')->one();// 不包括解绑账号
		
		$config = array(
				"userId"=>$jumiaUser->platform_userid,
				"apiKey"=>$jumiaUser->token,
				"countryCode"=>$jumiaUser->lazada_site
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
	// listing/jumia-listing/get-all-categoryids
	// 通过PrimaryCategory id 来获取所有 包括自身和父目录的 目录id
	public function actionGetAllCategoryids(){
		$jumia_uid = $_POST['lazada_uid'];
		$primaryCategory = $_POST['primaryCategory'];
		list($ret,$categoryIds) = LazadaApiHelper::getAllCatIdsByPrimaryCategory($primaryCategory,$jumia_uid);
		if(false == $ret){
			return ResultHelper::getResult(400, "" , TranslateHelper::t("获取目录id失败"));
		}else{
			return ResultHelper::getResult(200, $categoryIds , "");
		}
	}
	
	// listing/jumia-listing/get-selected-category-history
	// jumia 刊登 获取选择过的目录
	public function actionGetSelectedCategoryHistory(){
		$jumia_uid = $_POST['lazada_uid'];
		$path = LazadaApiHelper::getSelectedCategoryHistoryPath('jumia');
		$path = $path.$jumia_uid;
		
		$historyCatIdsStr = ConfigHelper::getConfig($path);
		$historyCatIds = json_decode($historyCatIdsStr,true);
		
		$jumiaUser= SaasLazadaUser::findOne(['lazada_uid'=>$jumia_uid]);
		if(empty($jumiaUser)){
			return ResultHelper::getResult(400, '', "账号不存在");
		}
		$config = array(
				"userId"=>$jumiaUser->platform_userid,
				"apiKey"=>$jumiaUser->token,
				"countryCode"=>$jumiaUser->lazada_site
		);
		list($ret,$categories) = SaasLazadaAutoSyncApiHelper::getCategoryTree($config);
		if($ret == true){
			$historyCats = array();
			if(!empty($historyCatIds)){
				foreach ($historyCatIds as $historyCatId){
					$historyCats[] = $categories[$historyCatId];
				}
			}
			
			return ResultHelper::getResult(200, $historyCats, "");
		}else{
			\Yii::error("actionSaveProduct save history cat fail for:Can not get categories info".$categories,"file");
		}
		
		return ResultHelper::getResult(400, '', "无法获取历史目录");
	}	
	
	// listing/jumia-listing/get-brands
	// jumia 刊登 
	public function actionGetBrands(){
		$jumia_uid = $_POST['lazada_uid'];
	
		$jumiaUser= SaasLazadaUser::findOne(['lazada_uid'=>$jumia_uid]);
		if(empty($jumiaUser)){
			return ResultHelper::getResult(400, '', "账号不存在");
		}
		
		$config = array(
				"userId"=>$jumiaUser->platform_userid,
				"apiKey"=>$jumiaUser->token,
				"countryCode"=>$jumiaUser->lazada_site
		);
		
		// dzt20190807 KE站点品牌获取不到问题，跳过品牌检查 ，dzt20190814 还是有客户提出，改成全站点不检查
// 		if("ke" == strtolower($jumiaUser->lazada_site))
		return ResultHelper::getResult(200, [$_POST['name']], "");
		
		
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
    	$jumiaUsers= SaasLazadaUser::find()->where(['puid'=>$puid,"platform"=>"jumia"])->andWhere('status <> 3')->asArray()->all();// 不包括解绑用户
    	// 过滤 lazada 在线listing
    	$jumiaIds = array();
    	foreach ($jumiaUsers as $jumiaUser){
    		$jumiaIds[] = $jumiaUser['lazada_uid'];
    	}
    	
    	$query = LazadaListing::find()->where(["lazada_uid_id"=>$jumiaIds]);
    	
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
		$siteIdNameMap = LazadaApiHelper::getLazadaCountryCodeSiteMapping("jumia");
		$jumiaUsersDropdownList = array();
		foreach ($jumiaUsers as $jumiaUser){
			$jumiaUsersDropdownList[$jumiaUser['lazada_uid']] = $jumiaUser['platform_userid']."(".$siteIdNameMap[$jumiaUser['lazada_site']].")";
		}
		return $this->renderAjax('referrencesList' , ['references' => $data , 'sort'=>$sortConfig ,'jumiaUsersDropdownList'=>$jumiaUsersDropdownList]);
	}
	
	// listing/jumia-listing/use-reference
	// 导入引用产品数据
	public function actionUseReference(){
		if(empty($_GET['listing_id'])){
			return "请选择引用产品";
		}
		
		$listing = LazadaListing::findOne($_GET['listing_id']);// 只通过parentSku 查找商品有可能出现 其他店铺相同parentSku的产品 。
		if(empty($listing)){
			return "引用产品不存在";
		}
		
		$jum_uid = $listing->lazada_uid_id;
		
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
	    $jumiaUsers= SaasLazadaUser::find()->where(['puid'=>$puid,"platform"=>"jumia"])->andWhere('status <> 3')->asArray()->all();// 不包括解绑用户
	    $siteIdNameMap = LazadaApiHelper::getLazadaCountryCodeSiteMapping("jumia");
	    $jumiaUsersDropdownList = array();
	    foreach ($jumiaUsers as $jumiaUser){
	        $jumiaUsersDropdownList[$jumiaUser['lazada_uid']] = $jumiaUser['platform_userid']."(".$siteIdNameMap[$jumiaUser['lazada_site']].")";
	    }
	    return $this->render('createOrEditProduct',['type'=>"reference",'jumiaUsersDropdownList'=>$jumiaUsersDropdownList,
	        'productDataStr'=>$productDataStr,'variantDataStr'=>$variantDataStr,'storeInfo'=>$storeInfo,'jum_uid'=>$jum_uid]);	}
	
	// listing/jumia-listing/do-publish
	// 产品发布
	public function actionDoPublish(){
	    AppTrackerApiHelper::actionLog("listing_jumia", "/listing/jumia-listing/do-publish");
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
	// listing/jumia-listing/copy-product
	public function actionCopyProduct(){
	    AppTrackerApiHelper::actionLog("listing_jumia", "/listing/jumia-listing/copy-product");
		if(!empty($_GET['id'])){
	        $publishListing = LazadaPublishListing::findOne(['id'=>$_GET['id']]);
	    }
	    	
	    $productData = array();
	    $variantData = array();
	    $storeInfo = array();
	    $jum_uid = 0;
	    if(!empty($publishListing)){
	        $jum_uid = $publishListing->lazada_uid;
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
	    $jumiaUsers= SaasLazadaUser::find()->where(['puid'=>$puid,"platform"=>"jumia"])->andWhere('status <> 3')->asArray()->all();// 不包括解绑用户
	    $siteIdNameMap = LazadaApiHelper::getLazadaCountryCodeSiteMapping("jumia");
	    $jumiaUsersDropdownList = array();
	    foreach ($jumiaUsers as $jumiaUser){
	        $jumiaUsersDropdownList[$jumiaUser['lazada_uid']] = $jumiaUser['platform_userid']."(".$siteIdNameMap[$jumiaUser['lazada_site']].")";
	    }
	    return $this->render('createOrEditProduct',['type'=>"edit",'jumiaUsersDropdownList'=>$jumiaUsersDropdownList,
	        'productDataStr'=>$productDataStr,'variantDataStr'=>$variantDataStr,'storeInfo'=>$storeInfo,'jum_uid'=>$jum_uid]);
	
	}

	// listing/jumia-listing/delete
	public function actionDelete(){
	    AppTrackerApiHelper::actionLog("listing_jumia", "/listing/jumia-listing/delete");
		if(empty($_POST['ids'])){
			return ResultHelper::getResult(400, '', "提交参数错误");
		}
	
		$ids = $_POST['ids'];
		$targetIds = explode(',', $ids);
		$requestNum = count($targetIds);
		// dzt20160602 由于客户一旦出错一般很少再从我们系统操作了，所以产品这里不再加限制
		$condition = " id in (".implode(',', $targetIds).") and platform='jumia' ";
// 		$condition = " id in (".implode(',', $targetIds).") and uploaded_product is null and platform='jumia' ";
		try {
			$excuteNum = LazadaPublishListing::deleteAll($condition);

			// 判断如果在线产品里面已经全部 待审核或者是Live的，就可以删除
// 			$excuteNum = 0;
// 			$targets = LazadaPublishListing::findAll(['id'=>$targetIds,'platform'=>'jumia' ]);
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
	
	
	// listing/jumia-listing/confirm-uploaded-product
	// 确认产品已经上传，此操作之后，再发布产品会覆盖已存在的产品数据
	public function actionConfirmUploadedProduct(){
	    AppTrackerApiHelper::actionLog("listing_jumia", "/listing/jumia-listing/confirm-uploaded-product");
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