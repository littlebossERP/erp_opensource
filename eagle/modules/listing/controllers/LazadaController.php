<?php


namespace eagle\modules\listing\controllers;


use yii;

use eagle\modules\util\helpers\TranslateHelper;
use eagle\modules\util\helpers\ResultHelper;

use eagle\modules\catalog\helpers\ProductApiHelper;
use yii\base\Exception;
use eagle\models\SaasLazadaAutosync;
use eagle\modules\listing\helpers\LazadaAutoFetchListingHelper;
use eagle\modules\lazada\apihelpers\SaasLazadaAutoSyncApiHelperV3;
use eagle\modules\listing\models\LazadaListing;
use eagle\modules\lazada\apihelpers\LazadaApiHelper;
use yii\data\Pagination;
use eagle\models\SaasLazadaUser;
use eagle\modules\app\apihelpers\AppTrackerApiHelper;
use eagle\modules\listing\models\LazadaListingV2;
use common\helpers\Helper_Array;
use eagle\modules\listing\helpers\LazadaAutoFetchListingHelperV3;
use eagle\modules\lazada\apihelpers\SaasLazadaAutoSyncApiHelperV4;

class lazadaController extends  \eagle\components\Controller
{
	public $enableCsrfValidation = FALSE;
	
	/**
	 * 用户界面在lazada在线管理界面点击"手工同步"的按钮
	 * 这里的手工同步只会触发拉取新的listing的功能
	 */
	public function actionManualSync(){
	    AppTrackerApiHelper::actionLog("listing_lazada", "/listing/lazada/manual-sync");
	    set_time_limit(0);
	    ignore_user_abort(true);// 注册并自动激活的时间比较长
		$lazadaUid = $_GET['Sync_lzd_uid'];
		if(!empty($_GET['Sync_lzd_uid'])){
		    list($ret,$message,$num)=LazadaAutoFetchListingHelperV3::manualSync($lazadaUid);
		    return json_encode(array('success'=>$ret,'message'=>$message,'num'=>$num));
		}else{
		    return json_encode(array('success'=>false,'message'=>'商铺信息获取失败'));
		}
		
	}
	
	// listing/lazada/batch-update-price
	public function actionBatchUpdatePrice(){
	    AppTrackerApiHelper::actionLog("listing_lazada", "/listing/lazada/batch-update-price");
		if(!empty($_POST['productIds'])){
		    $ids = explode(",",$_POST['productIds']);
		}else {
		    $ids = '';
		}
		if(isset($_POST['edit_method'])){
		    $op = $_POST['edit_method'];
		}else{
		    $op = '';
		}
		
		if(!empty($_POST['edit_input'])){
		    $value = $_POST['edit_input'];
		}else{
		    $value = 0;
		}
		$products = array();
		$products=array('productIds'=>$ids,'price'=>$value,'op'=>$op);
// 		list($ret,$message) = SaasLazadaAutoSyncApiHelperV3::batchUpdatePrice($products);
		list($ret,$message) = SaasLazadaAutoSyncApiHelperV4::batchUpdatePrice($products);
		if($ret == false){
			return ResultHelper::getResult(400, '', $message);
		}else{
			return ResultHelper::getResult(200, '', $message);
		}
	}
	
	// listing/lazada/batch-update-quantity
	public function actionBatchUpdateQuantity(){
	    AppTrackerApiHelper::actionLog("listing_lazada", "/listing/lazada/batch-update-quantity");
	    if(!empty($_POST['productIds'])){
	        $ids = explode(",",$_POST['productIds']);
	    }else {
	        $ids = '';
	    }
	    if(isset($_POST['edit_method'])){
	        $op = $_POST['edit_method'];
	    }else{
	        $op = '';
	    }
	    
// 	    if(!empty($_POST['condition'])){//调整方式
// 	        $condition = $_POST['condition'];
// 	    }else {
// 	        $condition = '';
// 	    }
	    
	    if(!empty($_POST['less_than'])){//调整条件
	        $condition_num = $_POST['less_than'];
	    }else {
	        $condition_num = '';
	    }
	    
	    if(!empty($_POST['edit_input'])){
	        $value = $_POST['edit_input'];
	    }else{
	        $value = 0;
	    }
		$products = array();
		$products=array('productIds'=>$ids,'quantity'=>$value,'condition_num'=>$condition_num,'op'=>$op);
// 		list($ret,$message) = SaasLazadaAutoSyncApiHelperV3::batchUpdateQuantity($products);
		list($ret,$message) = SaasLazadaAutoSyncApiHelperV4::batchUpdateQuantity($products);
		if($ret == false){
			return ResultHelper::getResult(400, '', $message);
		}else{
			return ResultHelper::getResult(200, '', $message);
		}
	}
	
	// listing/lazada/batch-update-sales-info
	public function actionBatchUpdateSalesInfo(){
	    AppTrackerApiHelper::actionLog("listing_lazada", "/listing/lazada/batch-update-sales-info");
	    if(!empty($_POST['productIds'])){
	        $ids = explode(",",$_POST['productIds']);
	    }else {
	        $ids = '';
	    }
	    if(isset($_POST['edit_method'])){
	        $op = $_POST['edit_method'];
	    }else{
	        $op = '';
	    }
	     
	    if(!empty($_POST['edit_input'])){
	        $value = $_POST['edit_input'];
	    }else{
	        $value = 0;
	    }
	    if(!empty($_POST['saleStartDate'])){
	        $saleStartDate = $_POST['saleStartDate'];
	    }else{
	        $saleStartDate = '';
	    }
	    if(!empty($_POST['saleEndDate'])){
	       $saleEndDate = $_POST['saleEndDate'];
	    }else{
	       $saleEndDate = '';
	    }
		$products = array();
		$products=array('productIds'=>$ids,'salePrice'=>$value,'op'=>$op,'saleStartDate'=>$saleStartDate,'saleEndDate'=>$saleEndDate);
// 		list($ret,$message) = SaasLazadaAutoSyncApiHelperV3::batchUpdateSaleInfo($products);
		list($ret,$message) = SaasLazadaAutoSyncApiHelperV4::batchUpdateSaleInfo($products);
		if($ret == false){
			return ResultHelper::getResult(400, '', $message);
		}else{
			return ResultHelper::getResult(200, '', $message);
		}
	}
	
	// listing/lazada/online-product
	public function actionOnlineProduct(){
	    set_time_limit(0);
	    ignore_user_abort(true);
	    AppTrackerApiHelper::actionLog("listing_lazada", "/listing/lazada/online-product");
	    
	    //只显示有权限的账号，lrq20170828
	    $account_data = \eagle\modules\platform\apihelpers\PlatformAccountApi::getPlatformAuthorizeAccounts('lazada');
	    $selleruserids = array();
	    foreach($account_data as $key => $val){
	    	$selleruserids[] = $key;
	    }
	    //获取对应客户id的商铺名
	    $puid = \Yii::$app->user->identity->getParentUid();
	    $lazadaUsers= SaasLazadaUser::find()->where(['puid'=>$puid,"platform"=>"lazada",'platform_userid'=>$selleruserids])->andWhere('status <> 3')->asArray()->all();// 不包括解绑用户
	    $lazada_uids = array();
	    foreach ($lazadaUsers as $lazadaUser){
	    	$lazada_uids[] = $lazadaUser['lazada_uid'];
	    }
	    
	    $query = LazadaListingV2::find()->where(['platform'=>'lazada', 'lazada_uid' => $lazada_uids])->andWhere('sub_status <>"deleted" or sub_status is null ');
	    $search_status = "";
	    if(!empty($_REQUEST['condition'])&&!empty($_REQUEST['condition_search'])){
	        if($_REQUEST['condition'] == 'title'){
	            $query->andWhere(["like","name",$_REQUEST['condition_search']]);
	            $search_status = "search";
	        }
	        if($_REQUEST['condition'] == 'sku'){
	            $query->andWhere(["like","SellerSku",$_REQUEST['condition_search']]);
	            $search_status = "search";
	        }
	    }

	    if(isset($_REQUEST['sub_status']) && $_REQUEST['sub_status'] <> ''){
	    	$query->andWhere(["sub_status"=>$_REQUEST['sub_status']]);
	    }
	    
	    if(!empty($_REQUEST['shop_name'])){
	        $query->andWhere(["lazada_uid"=>$_REQUEST['shop_name']]);
	    }
	    
	    $query->orderBy(['update_time'=>SORT_DESC ,'id'=>SORT_ASC])->select(['lazada_uid','group_id'])->distinct();//以parent_sku为一条记录
	    $pages = new Pagination(['totalCount' =>$query->count(), 'defaultPageSize'=>20 , 'pageSizeLimit'=>[5,200] , 'params'=>$_REQUEST]);
	    
// 	    $tmpCommand = $query->createCommand();
//         echo "<br>".$tmpCommand->getRawSql();exit();

	    $parentNums = $query->offset($pages->offset)->limit($pages->limit)->asArray()->all();
	    
	    $diffArr = array();
	    $parentSkus = array();
	    $lazadaUids = array();
	    foreach ($parentNums as $record){//用来过滤相同parentSku不同店铺的信息
	        $diffArr[] = $record['lazada_uid'].'@_@'.$record['group_id'];
	        $parentSkus[] = $record['group_id'];
	        $lazadaUids[] = $record['lazada_uid'];
	    }
	    
// 	    var_dump($diffArr);
// 	    exit();
	    
	    $listings = array();
	    if(!empty($parentNums)){
	        $listings = LazadaListingV2::find()->where(['platform'=>'lazada', 'lazada_uid' => $lazada_uids, 'group_id'=>$parentSkus,'lazada_uid'=>$lazadaUids])
	        ->andWhere('sub_status <>"deleted" or sub_status is null ')
	        ->orderBy(['update_time'=>SORT_DESC,'id'=>SORT_ASC])->asArray()->all();
	    }
	    
	    $return = array();
	    if(!empty($listings)){
	        foreach($listings as $listing){//映射
	            $key = $listing['lazada_uid'].'@_@'.$listing['group_id'];
	            if(in_array($key, $diffArr)){
	                $skuInfo = json_decode($listing['Skus'],true);
	                $attributes = json_decode($listing['Attributes'],true);
	                if(empty($attributes)) $attributes = array();
	                $listing['MainImage'] = "";
	                if(!empty($skuInfo['Images'])){
	                    foreach($skuInfo['Images'] as $src){
	                        if(empty($listing['MainImage']) && !empty($src))
	                            $listing['MainImage'] = $src;
	                    }
	                } 
	                
	                $listing = array_merge($listing,$attributes,$skuInfo);
	                if(empty($listing['special_price'])) $listing['special_price'] = 0;
	                
	                $return[$key][] = $listing ;
	            }
	        }
	    }
	    
	    $siteIdNameMap = LazadaApiHelper::getLazadaCountryCodeSiteMapping('lazada');
	    $lazadaUsersDropdownList = array();
	    foreach ($lazadaUsers as $lazadaUser){
	        if(empty($siteIdNameMap[$lazadaUser['lazada_site']])){
	            $lazadaUsersDropdownList[$lazadaUser['lazada_uid']] = $lazadaUser['platform_userid']."(".$lazadaUser['lazada_site'].")";
	        }else{
	            $lazadaUsersDropdownList[$lazadaUser['lazada_uid']] = $lazadaUser['platform_userid']."(".$siteIdNameMap[$lazadaUser['lazada_site']].")";
	        }
	    }
	    
	    $subStatus = array();
	    $subStatusData = LazadaListingV2::find()->where(['platform'=>'lazada', 'lazada_uid' => $lazada_uids])->andWhere('sub_status <>"deleted" or sub_status is null ')
	    ->select('sub_status')->distinct()
	    ->asArray()->all();
	    $subStatus = Helper_Array::toHashmap($subStatusData, "sub_status","sub_status");
	    
	    return $this->render('online-product',['shop_name'=>$lazadaUsersDropdownList,'listings'=>$return,'pages'=>$pages,'search_status'=>$search_status,'subStatus'=>$subStatus,'activeMenu'=>'在线商品']);
	}

	// listing/lazada/off-shelf-product
	// 下架商品
	public function actionOffShelfProduct(){
	    AppTrackerApiHelper::actionLog("listing_lazada", "/listing/lazada/off-shelf-product");
	    $puid = \Yii::$app->user->identity->getParentUid();
	    $lazadaUsers= SaasLazadaUser::find()->where(['puid'=>$puid,"platform"=>"lazada"])->andWhere('status <> 3')->asArray()->all();// 不包括解绑用户
	    // 过滤 lazada 在线listing
	    $lazadaIds = array();
	    foreach ($lazadaUsers as $lazadaUser){
	        $lazadaIds[] = $lazadaUser['lazada_uid'];
	    }
	    $base_where = ' ( Status = "inactive" or sub_status = "inactive")';
	    
	    if(!empty($_REQUEST['shop_name'])){
	        // 	        $query->andWhere(["lazada_uid_id"=>$_REQUEST['shop_name']]);
	        $base_where .= ' and ( lazada_uid_id = '.$_REQUEST['shop_name'].' )';
	    }
	    
	    $query = LazadaListing::find()->where(["lazada_uid_id"=>$lazadaIds])->andWhere($base_where);
// 	    $where = ' (Status = "active" and sub_status != "inactive")';
	    $search_status = "";
	   
	    if(!empty($_REQUEST['condition'])&&!empty($_REQUEST['condition_search'])){
	        if($_REQUEST['condition'] == 'title'){
// 	            $where .= " and ( Name like '%{$_REQUEST['condition_search']}%')";
	            $query->andWhere(["like","Name",$_REQUEST['condition_search']]);
	            $search_status = "search";
	        }
	        if($_REQUEST['condition'] == 'sku'){
// 	            $where .= " and ( SellerSku like '%{$_REQUEST['condition_search']}%' )";
	            $query->andWhere(["like","SellerSku",$_REQUEST['condition_search']]);
	            $search_status = "search";
	        }
 
	    }

	    if(isset($_REQUEST['sub_status']) && $_REQUEST['sub_status'] <> ''){
// 	    	$where .= " and ( sub_status='".$_REQUEST['sub_status']."' )";
	    	$query->andWhere(["sub_status"=>$_REQUEST['sub_status']]);
	    }
	    	    
// 	    if(!empty($_REQUEST['shop_name'])){
// 	        $where .= " and ( lazada_uid_id = {$_REQUEST['shop_name']} )";
// 	    }
// 	    if(!empty($_REQUEST['condition'])&&!empty($_REQUEST['condition_search'])){
// 	        if($_REQUEST['condition'] == 'title'){
// 	            $where .= " and ( Name like '%{$_REQUEST['condition_search']}%')";
// 	            $search_status = "search";
// 	        }
// 	        if($_REQUEST['condition'] == 'sku'){
// 	            $where .= " and ( SellerSku like '%{$_REQUEST['condition_search']}%' )";
// 	            $search_status = "search";
// 	        }
	    
// 	    }
	   
// 	    if(isset($_REQUEST['sub_status']) && $_REQUEST['sub_status'] <> ''){
// 	    	$where .= " and ( sub_status='".$_REQUEST['sub_status']."' )";
// 	    }
	    
	    $query->orderBy(['create_time'=>SORT_DESC ,'id'=>SORT_ASC])->select(['ParentSku','lazada_uid_id'])->distinct(['ParentSku','lazada_uid_id']);//以parent_sku为一条记录
	    $pages = new Pagination(['totalCount' =>$query->count(), 'defaultPageSize'=>20 , 'pageSizeLimit'=>[5,200] , 'params'=>$_REQUEST]);
		$parentNums = $query->offset($pages->offset)->limit($pages->limit)->asArray()->all();
		
		$diff_nums = array();
		foreach ($parentNums as $detail_num){//用来过滤相同parentSku不同店铺的信息
		    $diff_nums[] = $detail_num['ParentSku'].'@_@'.$detail_num['lazada_uid_id'];
		}
		
		$ParentSku_nums = array();
		foreach ($parentNums as $record){
		    $ParentSku_nums[] = $record['ParentSku'];
		}
		if(!empty($ParentSku_nums)){
		    $online_product = LazadaListing::find()->where(['ParentSku'=>$ParentSku_nums,"lazada_uid_id"=>$lazadaIds])->andWhere($base_where)->orderBy(['create_time'=>SORT_DESC,'id'=>SORT_ASC])->asArray()->all();
		}else{
		    $online_product="";
		}
		 
	    if(!empty($online_product)){
	        $return = array();
	        foreach($online_product as $details){//映射
	            if(in_array($details['ParentSku'].'@_@'.$details['lazada_uid_id'], $diff_nums)){
	                $key = $details['ParentSku'].'@_@'.$details['lazada_uid_id'];
	                if(!isset($return[$key]['parent']['quantity'])){
	                    $return[$key]['parent']['quantity'] = 0;  //初始化库存
	                }
	                if(!isset($return[$key]['parent']['lazada_uid_id'])){
	                    $return[$key]['parent']['lazada_uid_id'] = $details['lazada_uid_id'];  //初始化uid
	                }
	                if($details['ParentSku'] == $details['SellerSku']){
	                    $return[$key]['parent']['item'] = $details;
	                }else{
	                    if(!isset($return[$key]['parent']['item'])){
	                        $return[$key]['parent']['item'] = '';
	                    }
	                }
	                $return[$key]['parent']['quantity'] = $return[$key]['parent']['quantity'] + $details['Quantity'];
	                $return[$key]['items'][] = $details ;
// 	                $online_detail[$details['ParentSku']][$details['SellerSku']] = $details;//同一模版的不同预言
	            }
	        }
	    }else{
	        $return = array();
	    }
		
		$siteIdNameMap = LazadaApiHelper::getLazadaCountryCodeSiteMapping('lazada');
		$lazadaUsersDropdownList = array();
		foreach ($lazadaUsers as $lazadaUser){
		    $lazadaUsersDropdownList[$lazadaUser['lazada_uid']] = $lazadaUser['platform_userid']."(".$siteIdNameMap[$lazadaUser['lazada_site']].")";
		};
		return $this->render('put-off-product',['shop_name'=>$lazadaUsersDropdownList,'online_product'=>$return,'pages'=>$pages,'search_status'=>$search_status,'activeMenu'=>'下架商品']);
	}
	
	// listing/lazada/put-on
	// 上架
	public function actionPutOn(){
	    AppTrackerApiHelper::actionLog("listing_lazada", "/listing/lazada/put-on");
	    if(!empty($_POST['productIds'])){
	        $products = $_POST['productIds'];
	    }else{
	        $products = '';
	    }
// 		$products=array(111 ,222,333);
// 		list($ret,$message) = SaasLazadaAutoSyncApiHelperV3::batchPutOnLine($products);
	    list($ret,$message) = SaasLazadaAutoSyncApiHelperV4::batchPutOnLine($products);
		if($ret == false){
			return ResultHelper::getResult(400, '', $message);
		}else{
			return ResultHelper::getResult(200, '', $message);
		}
	}
	
	// listing/lazada/put-off
	// 下架
	public function actionPutOff(){
	    AppTrackerApiHelper::actionLog("listing_lazada", "/listing/lazada/put-off");
	    if(!empty($_POST['productIds'])){
	        $products = $_POST['productIds'];
	    }else{
	        $products = '';
	    }
// 		$products=array(111 ,222,333);
// 		list($ret,$message) = SaasLazadaAutoSyncApiHelperV3::batchPutOffLine($products);
	    list($ret,$message) = SaasLazadaAutoSyncApiHelperV4::batchPutOffLine($products);
		if($ret == false){
			return ResultHelper::getResult(400, '', $message);
		}else{
			return ResultHelper::getResult(200, '', $message);
		}
	}
}
