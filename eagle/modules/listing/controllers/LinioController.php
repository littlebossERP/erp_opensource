<?php


namespace eagle\modules\listing\controllers;


use yii;

use eagle\modules\util\helpers\TranslateHelper;
use eagle\modules\util\helpers\ResultHelper;

use eagle\modules\catalog\helpers\ProductApiHelper;
use yii\base\Exception;
use eagle\models\SaasLazadaAutosync;
use eagle\modules\listing\helpers\LazadaAutoFetchListingHelper;
use eagle\modules\lazada\apihelpers\SaasLazadaAutoSyncApiHelper;
use eagle\modules\listing\models\LazadaListing;
use eagle\modules\lazada\apihelpers\LazadaApiHelper;
use yii\data\Pagination;
use eagle\models\SaasLazadaUser;
use eagle\modules\app\apihelpers\AppTrackerApiHelper;

class LinioController extends  \eagle\components\Controller
{
	public $enableCsrfValidation = FALSE;
	
	/**
	 * 用户界面在linio在线管理界面点击"手工同步"的按钮
	 * 这里的手工同步只会触发拉取新的listing的功能
	 */
	public function actionManualSync(){
	    AppTrackerApiHelper::actionLog("listing_linio", "/listing/linio/manual-sync");
	    set_time_limit(0);
	    ignore_user_abort(true);// 注册并自动激活的时间比较长
		$linioUid = $_GET['Sync_lzd_uid'];
		if(!empty($_GET['Sync_lzd_uid'])){
		    list($ret,$message,$num)=LazadaAutoFetchListingHelper::manualSync($linioUid);
		    return json_encode(array('success'=>$ret,'message'=>$message,'num'=>$num));
		}else{
		    return json_encode(array('success'=>false,'message'=>'商铺信息获取失败'));
		}
		
	}
	
	// listing/linio/batch-update-price
	public function actionBatchUpdatePrice(){
	    AppTrackerApiHelper::actionLog("listing_linio", "/listing/linio/batch-update-price");
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
		list($ret,$message) = SaasLazadaAutoSyncApiHelper::batchUpdatePrice($products);
		if($ret == false){
			return ResultHelper::getResult(400, '', $message);
		}else{
			return ResultHelper::getResult(200, '', $message);
		}
	}
	
	// listing/linio/batch-update-quantity
	public function actionBatchUpdateQuantity(){
	    AppTrackerApiHelper::actionLog("listing_linio", "/listing/linio/batch-update-quantity");
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
		list($ret,$message) = SaasLazadaAutoSyncApiHelper::batchUpdateQuantity($products);
		if($ret == false){
			return ResultHelper::getResult(400, '', $message);
		}else{
			return ResultHelper::getResult(200, '', $message);
		}
	}
	
	// listing/linio/batch-update-sales-info
	public function actionBatchUpdateSalesInfo(){
	    AppTrackerApiHelper::actionLog("listing_linio", "/listing/linio/batch-update-sales-info");
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
		list($ret,$message) = SaasLazadaAutoSyncApiHelper::batchUpdateSaleInfo($products);
		if($ret == false){
			return ResultHelper::getResult(400, '', $message);
		}else{
			return ResultHelper::getResult(200, '', $message);
		}
	}
	
	// listing/linio/online-product
	public function actionOnlineProduct(){
	    AppTrackerApiHelper::actionLog("listing_linio", "/listing/linio/online-product");
	    
	    //只显示有权限的账号，lrq20170828
	    $account_data = \eagle\modules\platform\apihelpers\PlatformAccountApi::getPlatformAuthorizeAccounts('linio');
	    $selleruserids = array();
	    foreach($account_data as $key => $val){
	    	$selleruserids[] = $key;
	    }
	    
		$puid = \Yii::$app->user->identity->getParentUid();
	    $linioUsers= SaasLazadaUser::find()->where(['puid'=>$puid,"platform"=>"linio",'platform_userid'=>$selleruserids])->andWhere('status <> 3')->asArray()->all();// 不包括解绑用户
	    // 过滤 linio 在线listing
	    $linioIds = array();
	    foreach ($linioUsers as $linioUser){
	        $linioIds[] = $linioUser['lazada_uid'];
	    }
	    $base_where = ' (Status = "active" and (sub_status is null or sub_status <> "inactive"))';
	    $query = LazadaListing::find()->where(["lazada_uid_id"=>$linioIds])->andWhere($base_where);
// 	    $where = ' (Status = "active" and sub_status != "inactive")';
	    $search_status = "";
	    if(!empty($_REQUEST['shop_name'])){
	        $query->andWhere(["lazada_uid_id"=>$_REQUEST['shop_name']]);
	    }
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
		    $online_product = LazadaListing::find()->where(['ParentSku'=>$ParentSku_nums,"lazada_uid_id"=>$linioIds])->andWhere($base_where)->orderBy(['create_time'=>SORT_DESC,'id'=>SORT_ASC])->asArray()->all();
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
		
		$siteIdNameMap = LazadaApiHelper::getLazadaCountryCodeSiteMapping('linio');
		$linioUsersDropdownList = array();
		foreach ($linioUsers as $linioUser){
		    $linioUsersDropdownList[$linioUser['lazada_uid']] = $linioUser['platform_userid']."(".$siteIdNameMap[$linioUser['lazada_site']].")";
		};
	    return $this->render('online-product',['shop_name'=>$linioUsersDropdownList,'online_product'=>$return,'pages'=>$pages,'search_status'=>$search_status,'activeMenu'=>'在线商品']);
	}

	// listing/linio/off-shelf-product
	// 下架商品
	public function actionOffShelfProduct(){
	    AppTrackerApiHelper::actionLog("listing_linio", "/listing/linio/off-shelf-product");

	    //只显示有权限的账号，lrq20170828
	    $account_data = \eagle\modules\platform\apihelpers\PlatformAccountApi::getPlatformAuthorizeAccounts('linio');
	    $selleruserids = array();
	    foreach($account_data as $key => $val){
	    	$selleruserids[] = $key;
	    }
	    
		$puid = \Yii::$app->user->identity->getParentUid();
	    $linioUsers= SaasLazadaUser::find()->where(['puid'=>$puid,"platform"=>"linio",'platform_userid'=>$selleruserids])->andWhere('status <> 3')->asArray()->all();// 不包括解绑用户
	    // 过滤 linio 在线listing
	    $linioIds = array();
	    foreach ($linioUsers as $linioUser){
	        $linioIds[] = $linioUser['lazada_uid'];
	    }
	    $base_where = ' ( Status = "inactive" or sub_status = "inactive")';
	    $query = LazadaListing::find()->where(["lazada_uid_id"=>$linioIds])->andWhere($base_where);
	    $search_status = "";
	    if(!empty($_REQUEST['shop_name'])){
	        $query->andWhere(["lazada_uid_id"=>$_REQUEST['shop_name']]);
	    }
	    if(!empty($_REQUEST['condition'])&&!empty($_REQUEST['condition_search'])){
	        if($_REQUEST['condition'] == 'title'){
	            $query->andWhere(["like","Name",$_REQUEST['condition_search']]);
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
		    $online_product = LazadaListing::find()->where(['ParentSku'=>$ParentSku_nums,"lazada_uid_id"=>$linioIds])->andWhere($base_where)->orderBy(['create_time'=>SORT_DESC,'id'=>SORT_ASC])->asArray()->all();
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
		
		$siteIdNameMap = LazadaApiHelper::getLazadaCountryCodeSiteMapping('linio');
		$linioUsersDropdownList = array();
		foreach ($linioUsers as $linioUser){
		    $linioUsersDropdownList[$linioUser['lazada_uid']] = $linioUser['platform_userid']."(".$siteIdNameMap[$linioUser['lazada_site']].")";
		};
		return $this->render('put-off-product',['shop_name'=>$linioUsersDropdownList,'online_product'=>$return,'pages'=>$pages,'search_status'=>$search_status,'activeMenu'=>'下架商品']);
	}
	
	// listing/linio/put-on
	// 上架
	public function actionPutOn(){
	    AppTrackerApiHelper::actionLog("listing_linio", "/listing/linio/put-on");
	    if(!empty($_POST['productIds'])){
	        $products = $_POST['productIds'];
	    }else{
	        $products = '';
	    }
// 		$products=array(111 ,222,333);
		list($ret,$message) = SaasLazadaAutoSyncApiHelper::batchPutOnLine($products);
		if($ret == false){
			return ResultHelper::getResult(400, '', $message);
		}else{
			return ResultHelper::getResult(200, '', $message);
		}
	}
	
	// listing/linio/put-off
	// 下架
	public function actionPutOff(){
	    AppTrackerApiHelper::actionLog("listing_linio", "/listing/linio/put-off");
	    if(!empty($_POST['productIds'])){
	        $products = $_POST['productIds'];
	    }else{
	        $products = '';
	    }
// 		$products=array(111 ,222,333);
		list($ret,$message) = SaasLazadaAutoSyncApiHelper::batchPutOffLine($products);
		if($ret == false){
			return ResultHelper::getResult(400, '', $message);
		}else{
			return ResultHelper::getResult(200, '', $message);
		}
	}
}
