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
use eagle\modules\util\models\UserBackgroundJobControll;
use eagle\modules\listing\helpers\LazadaLinioJumiaProductFeedHelper;
use eagle\modules\permission\apihelpers\UserApiHelper;
use common\api\lazadainterface\LazadaInterface_Helper;
use eagle\modules\util\helpers\TimeUtil;
use eagle\modules\listing\helpers\LazadaAutoFetchListingHelperV2;
use eagle\modules\platform\apihelpers\PlatformAccountApi;

class JumiaController extends  \eagle\components\Controller
{
	public $enableCsrfValidation = FALSE;
	
	/**
	 * 用户界面在jumia在线管理界面点击"手工同步"的按钮
	 * 这里的手工同步只会触发拉取新的listing的功能
	 */
	public function actionManualSync(){
	    AppTrackerApiHelper::actionLog("listing_jumia", "/listing/jumia/manual-sync");
	    set_time_limit(0);
	    ignore_user_abort(true);// 注册并自动激活的时间比较长
		$lazadaUid = $_GET['Sync_lzd_uid'];
		if(!empty($_GET['Sync_lzd_uid'])){
		    list($ret,$message,$num)=LazadaAutoFetchListingHelper::manualSync($lazadaUid);
		    return json_encode(array('success'=>$ret,'message'=>$message,'num'=>$num));
		}else{
		    return json_encode(array('success'=>false,'message'=>'商铺信息获取失败'));
		}
		
	}
	
	// listing/jumia/batch-update-price
	public function actionBatchUpdatePrice(){
	    AppTrackerApiHelper::actionLog("listing_jumia", "/listing/jumia/batch-update-price");
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
	
	// listing/jumia/batch-update-quantity
	public function actionBatchUpdateQuantity(){
	    AppTrackerApiHelper::actionLog("listing_jumia", "/listing/jumia/batch-update-quantity");
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
	
	// listing/jumia/batch-update-sales-info
	public function actionBatchUpdateSalesInfo(){
	    AppTrackerApiHelper::actionLog("listing_jumia", "/listing/jumia/batch-update-sales-info");
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
	
	// listing/jumia/online-product
	public function actionOnlineProduct(){
	    AppTrackerApiHelper::actionLog("listing_jumia", "/listing/jumia/online-product");
	    
		$puid = \Yii::$app->user->identity->getParentUid();
	    $jumiaUsers= SaasLazadaUser::find()->where(['puid'=>$puid,"platform"=>"jumia"])->andWhere('status <> 3')->asArray()->all();// 不包括解绑用户
	    // 过滤 jumia 在线listing
	    $jumiaIds = array();
	    foreach ($jumiaUsers as $jumiaUser){
	        $jumiaIds[] = $jumiaUser['lazada_uid'];
	    }
	    $base_where = ' (Status = "active" and (sub_status is null or sub_status <> "inactive"))';
	    $query = LazadaListing::find()->where(["lazada_uid_id"=>$jumiaIds])->andWhere($base_where);
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
		    $online_product = LazadaListing::find()->where(['ParentSku'=>$ParentSku_nums,"lazada_uid_id"=>$jumiaIds])->andWhere($base_where)->orderBy(['create_time'=>SORT_DESC,'id'=>SORT_ASC])->asArray()->all();
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
		
		$siteIdNameMap = LazadaApiHelper::getLazadaCountryCodeSiteMapping('jumia');
		$jumiaUsersDropdownList = array();
		foreach ($jumiaUsers as $jumiaUser){
		    $jumiaUsersDropdownList[$jumiaUser['lazada_uid']] = $jumiaUser['platform_userid']."(".$siteIdNameMap[$jumiaUser['lazada_site']].")";
		}
	    return $this->render('online-product',['shop_name'=>$jumiaUsersDropdownList,'online_product'=>$return,'pages'=>$pages,'search_status'=>$search_status,'activeMenu'=>'在线商品']);
	}

	// listing/jumia/off-shelf-product
	// 下架商品
	public function actionOffShelfProduct(){
	    AppTrackerApiHelper::actionLog("listing_jumia", "/listing/jumia/off-shelf-product");
		
		$puid = \Yii::$app->user->identity->getParentUid();
	    $jumiaUsers= SaasLazadaUser::find()->where(['puid'=>$puid,"platform"=>"jumia"])->andWhere('status <> 3')->asArray()->all();// 不包括解绑用户
	    // 过滤 jumia 在线listing
	    $jumiaIds = array();
	    foreach ($jumiaUsers as $jumiaUser){
	        $jumiaIds[] = $jumiaUser['lazada_uid'];
	    }
	    $base_where = ' ( Status = "inactive" or sub_status = "inactive")';
	    $query = LazadaListing::find()->where(["lazada_uid_id"=>$jumiaIds])->andWhere($base_where);
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
	    	$online_product = LazadaListing::find()->where(['ParentSku'=>$ParentSku_nums,"lazada_uid_id"=>$jumiaIds])->andWhere($base_where)->orderBy(['create_time'=>SORT_DESC,'id'=>SORT_ASC])->asArray()->all();
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
	    
	    $siteIdNameMap = LazadaApiHelper::getLazadaCountryCodeSiteMapping('jumia');
	    $jumiaUsersDropdownList = array();
	    foreach ($jumiaUsers as $jumiaUser){
	    	$jumiaUsersDropdownList[$jumiaUser['lazada_uid']] = $jumiaUser['platform_userid']."(".$siteIdNameMap[$jumiaUser['lazada_site']].")";
	    }
		return $this->render('put-off-product',['shop_name'=>$jumiaUsersDropdownList,'online_product'=>$return,'pages'=>$pages,'search_status'=>$search_status,'activeMenu'=>'下架商品']);
	}
	
	// listing/jumia/put-on
	// 上架
	public function actionPutOn(){
	    AppTrackerApiHelper::actionLog("listing_jumia", "/listing/jumia/put-on");
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
	
	// listing/jumia/put-off
	// 下架
	public function actionPutOff(){
	    AppTrackerApiHelper::actionLog("listing_jumia", "/listing/jumia/put-off");
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
	
	// /listing/jumia/search-save-product
	// 调用接口拉取产品信息更新到本地
	public function actionSearchSaveProduct(){
	    AppTrackerApiHelper::actionLog("listing_jumia", "/listing/jumia/search-save-product");
	    
	    if(empty($_POST["lazada_uid"]))
	        return ResultHelper::getResult(400 , "" , "请选择要搜索产品的店铺");
	    
	    $puid = \Yii::$app->user->identity->getParentUid();
	    $SLU = SaasLazadaUser::findOne(["lazada_uid"=>$_POST["lazada_uid"], "puid"=>$puid, "platform"=>"jumia"]);
	    if(empty($SLU))
	        return ResultHelper::getResult(400 , "" , "选择的账号不存在");
	    
	    $_POST["search_content"] = trim($_POST["search_content"]);
	    if(empty($_POST["search_content"]))
	        return ResultHelper::getResult(400 , "" , "请输入搜索内容");
	    
	    $config =  array(
            "userId" => $SLU->platform_userid,
            "apiKey" => $SLU->token,
            "platform" => $SLU->platform,
            "countryCode" => $SLU->lazada_site,
            "puid" => $SLU->puid,
            "store_name" => $SLU->store_name,
            "lazada_uid" => $SLU->lazada_uid,
	    );
	    
	    
	    $apiReqParams = ['Search'=>$_POST["search_content"]];
	    list($ret, $message) = LazadaAutoFetchListingHelperV2::searchAndSaveProduct($config, $apiReqParams);
	    if($ret){
	        return ResultHelper::getResult(200, "", $message);
	    }else{
	        return ResultHelper::getResult(400, "", $message);
	    }
	    
	}
	

	// excel导入： 导入excel
	// /listing/jumia/import-listing-from-excel
	public function actionImportListingFromExcel(){
	    try {
	        AppTrackerApiHelper::actionLog("listing_jumia","/listing/jumia/import-listing-from-excel");
	        set_time_limit(0);
	        ignore_user_abort(true);// 大文件上传的时间比较长
	        
	        if (!empty ($_FILES["input_import_file"]))
	            $file = $_FILES["input_import_file"];
	        else 
	            return ResultHelper::getResult(400 , "" , "请选择导入文件");
	        
	        if(empty($_POST["lazada_uid"]))
	            return ResultHelper::getResult(400 , "" , "请选择要导入刊登产品的店铺");
	        
	        $accounts = json_decode($_POST["lazada_uid"], true);
	        $puid = \Yii::$app->user->identity->getParentUid();
	        
	        // dzt20190725 支持多账号选择
	        foreach ($accounts as $lazada_uid){
	            $SLU = SaasLazadaUser::findOne(["lazada_uid"=>$lazada_uid, "puid"=>$puid, "platform"=>"jumia"]);
	            if(empty($SLU))
	                return ResultHelper::getResult(400 , "" , "选择的账号不存在");
	        }
	        
	        
	        $excelTmpPath = \Yii::getAlias("@eagle/web/attachment/tmp_export_file").DIRECTORY_SEPARATOR;
	        $name = $file["name"];
	        if(!in_array(strtolower(pathinfo ( $name, PATHINFO_EXTENSION )), ['csv', 'xls', 'xlsx']))
	            return ResultHelper::getResult(400 , "" , "请上传csv, xls, xlsx格式的文件");
	        
	        $originName = date ( 'YmdHis' ) . '-' .rand(1,100).substr ( md5 ( $name ), 0, 5 ) . '.' . pathinfo ( $name, PATHINFO_EXTENSION );
	        \Yii::info("actionImportListingFromExcel unload_tmp_file: ".json_encode($_FILES).",target_file:".$excelTmpPath . $originName,"file");
	        if(move_uploaded_file ( $file["tmp_name"] , $excelTmpPath . $originName ) === false) {
	            return ResultHelper::getResult(400, "", $file["name"]." 上传失败，请重试。");
	        }
	
	        
	        $nowTime = time();
	
	        // dzt20190725 支持多账号选择
	        foreach ($accounts as $lazada_uid){
	            $userBgControl = new UserBackgroundJobControll();
	            $userBgControl->puid = $puid;
	            $userBgControl->job_name = LazadaLinioJumiaProductFeedHelper::$job_name;
	            $userBgControl->create_time = $nowTime;
	             
	            if(!empty($_POST['import_type']))
	                $userBgControl->custom_name = $_POST['import_type'];
	            else
	                $userBgControl->custom_name = 'import';
	             
	            $userBgControl->status = 0;// 0 未处理 ，1处理中 ，2完成 ，3失败
	            $userBgControl->error_count = 0;
	            $userBgControl->is_active = "Y";// 运行完之后，关闭
	            // 记录导入文件
	            $userBgControl->additional_info = json_encode(array(
	                    "originFileName"=>$name,"fileName"=>$originName,"filePath"=>$excelTmpPath.$originName,
// 	                    "lazada_uid"=>$SLU->lazada_uid,
	                    "lazada_uid"=>$lazada_uid,
	            ));
	             
	             
	            if(!empty($_POST["excute_time"]))
	                $userBgControl->next_execution_time = strtotime($_POST["excute_time"]);
	            else
	                $userBgControl->next_execution_time = $nowTime;
	             
	            $userBgControl->update_time = $nowTime;
	            $userBgControl->save(false);
	        }
	        
	        	
	        return ResultHelper::getResult(200, "", "文件上传成功");
	    } catch (\Exception $e) {
	        \Yii::error("File:".$e->getFile().",Line:".$e->getLine().",Message:".$e->getMessage(),"file");
	        return ResultHelper::getResult(400 , "" , $e->getMessage());
	    }
	}
	
	// excel导入： 任务列表
	// /listing/jumia/import-listing-job-list
	public function actionImportListingJobList(){
        AppTrackerApiHelper::actionLog("listing_jumia","/listing/jumia/import-listing-job-list");

        //查询是否有显示权限
        $permission = UserApiHelper::checkOtherPermission('jumia_listing_import');
        if(!$permission)
            return $this->render('//errorview_no_close',['title'=>'访问受限:没有权限','error'=>'您还没有该模块的权限!']);
        
	    $puid = \Yii::$app->user->identity->getParentUid();
	    
	    $accountList = [];
	    $isMain = UserApiHelper::isMainAccount();
	    if(!$isMain){
	        $tmpSellerIDList = PlatformAccountApi::getPlatformAuthorizeAccounts('jumia');
	        foreach($tmpSellerIDList as $sellerloginid=>$store_name){
	            $accountList[] = $sellerloginid;
	        }
	    }
	    
	    $lazadauserMap = self::_getAllJumiaAccountInfoMap($accountList);
	    
	    // 创建时间倒序展示
	    $query = UserBackgroundJobControll::find()->where(["puid"=>$puid, "job_name"=>LazadaLinioJumiaProductFeedHelper::$job_name]);
	    
	    $pagination = new Pagination([
            'defaultPageSize' => 20,//当per-page获取不到值时的默认值
            'totalCount' => $query->count(),
            'pageSizeLimit'=>[5,200],// 每页显示条数的范围，默认 1-50。所以如果不修改这里，不管per-page返回什么每页最多显示50条记录。
	    ]);
	    
	    $jobs = $query
	    ->orderBy("create_time desc")
	    ->limit($pagination->limit)
	    ->offset($pagination->offset)
	    ->asArray()->all();
	    
	    
	    
	    foreach ($jobs as $index=>&$job){
            $addInfo = json_decode($job["additional_info"], true);
            $job = array_merge($job, $addInfo);
            
            $storeInfo = [];
            if(!empty($lazadauserMap[$addInfo["lazada_uid"]]))
                $storeInfo = $lazadauserMap[$addInfo["lazada_uid"]];
            
            $job["opBan"] = 0;
            if(!$isMain && empty($storeInfo)){// 其他账号的任务，不能操作，由于没有sql账号过滤，所以不要屏蔽了
                $job["opBan"] = 1;
            }
            
            if(empty($storeInfo))
                $job["store_name"] = "账号不存在";
            elseif(empty($storeInfo['store_name']))
                $job["store_name"] = $storeInfo["platform_userid"]."(".$storeInfo["lazada_site"].")";
            else 
                $job["store_name"] = $storeInfo["store_name"];
	        
            $job["op"] = empty(LazadaLinioJumiaProductFeedHelper::$custom_name_arr[$job["custom_name"]])?$job["custom_name"]:LazadaLinioJumiaProductFeedHelper::$custom_name_arr[$job["custom_name"]];
            $job["status_name"] = empty(LazadaLinioJumiaProductFeedHelper::$status_name_map[$job["status"]])?$job["status"]:LazadaLinioJumiaProductFeedHelper::$status_name_map[$job["status"]];
            $job["create_time"] = date("Y-m-d H:i:s", $job["create_time"]);
            $job["next_execution_time"] = date("Y-m-d H:i:s", $job["next_execution_time"]);
	    }
	    
	    // 要求修改成可以选择多站点
	    $jumiaUsersDropdownList = array();
	    $siteList = array();
	    $jumiaUsersSiteMap = array();
	    $allJumiaSite = LazadaApiHelper::getLazadaCountryCodeSiteMapping('jumia');
	    foreach ($lazadauserMap as $lazada_uid=>$jumiaUser){
// 	        if(!empty($jumiaUser["store_name"]))
// 	            $jumiaUsersDropdownList[$lazada_uid] = $jumiaUser["store_name"];
// 	        else 
//                 $jumiaUsersDropdownList[$lazada_uid] = $jumiaUser["userId"]."(".$jumiaUser["countryCode"].")";

            $siteList[$jumiaUser["countryCode"]] = $allJumiaSite[$jumiaUser["countryCode"]];
            
            if(empty($jumiaUsersSiteMap[$jumiaUser["countryCode"]]))
                $jumiaUsersSiteMap[$jumiaUser["countryCode"]] = array();
            
            if(!empty($jumiaUser["store_name"]))
	            $jumiaUsersSiteMap[$jumiaUser["countryCode"]][$lazada_uid] = $jumiaUser["store_name"];
	        else
                $jumiaUsersSiteMap[$jumiaUser["countryCode"]][$lazada_uid] = $jumiaUser["userId"]."(".$jumiaUser["countryCode"].")";
            
	    }
	    
	    
	    return $this->render('job_list', [
            'jobs'=>$jobs, 
            'pagination'=>$pagination,
// 	        'jumiaUsersDropdownList'=>$jumiaUsersDropdownList,
            'siteList'=>$siteList,
            'jumiaUsersSiteMap'=>$jumiaUsersSiteMap,
            'jobsDropdownList'=>LazadaLinioJumiaProductFeedHelper::$custom_name_arr,
	    ]);
	}
	
	// excel导入： 删除任务
	// /listing/jumia/delete-import-job
	public function actionDeleteImportJob(){
	    AppTrackerApiHelper::actionLog("listing_jumia","/listing/jumia/delete-import-job");
	    
	    if(empty($_POST["jobId"]))
	        return ResultHelper::getResult(400 , "" , "请选择要删除的任务");
	    
	    $puid = \Yii::$app->user->identity->getParentUid();
	    
	    // 未处理、已完成的任务可以删除 
	    $jobObj = UserBackgroundJobControll::findOne(["id"=>$_POST["jobId"], "status"=>[0,2,5], "puid"=>$puid, "job_name"=>LazadaLinioJumiaProductFeedHelper::$job_name]);
	    if(empty($jobObj))
	        return ResultHelper::getResult(400 , "" , "没有可以被删除的任务，请重新选择要删除的任务");
	    
	    try {
	       $jobObj->delete();
	    } catch (\Exception $e) {
	        \Yii::error("File:".$e->getFile().",Line:".$e->getLine().",Message:".$e->getMessage(),"file");
	        return ResultHelper::getResult(400 , "" , $e->getMessage());
	    }
	    
	    return ResultHelper::getResult(200 , "" , "删除成功");
	}
	
	// excel导入： 中止任务
	// /listing/jumia/stop-import-job
	public function actionStopImportJob(){
	    AppTrackerApiHelper::actionLog("listing_jumia","/listing/jumia/stop-import-job");
	    
	    if(empty($_POST["jobId"]))
	        return ResultHelper::getResult(400 , "" , "请选择要中止的任务");
	     
	    $puid = \Yii::$app->user->identity->getParentUid();
	    // 错误等待、间隔等待的任务可以中止
	    $jobObj = UserBackgroundJobControll::findOne(["id"=>$_POST["jobId"], "status"=>[3,5], "puid"=>$puid, "job_name"=>LazadaLinioJumiaProductFeedHelper::$job_name]);
	    if(empty($jobObj))
	        return ResultHelper::getResult(400 , "" , "没有可以被删除的任务，请重新选择要中止的任务");
	    
	    
	    try {
	        
	        $jobObj->is_active = "N";
	        $jobObj->status = 6;
	        $jobObj->save(false);
	    } catch (\Exception $e) {
	        \Yii::error("File:".$e->getFile().",Line:".$e->getLine().",Message:".$e->getMessage(),"file");
	        return ResultHelper::getResult(400 , "" , $e->getMessage());
	    }
	     
	    return ResultHelper::getResult(200 , "" , "操作成功");
	    
	}
	
	
	/**
	 * 获取所有lazada用户的api访问信息。 email,token,销售站点
	 */
	private static function _getAllJumiaAccountInfoMap($accountList = array())
	{
	    $lazadauserMap = array();
	    $puid = \Yii::$app->user->identity->getParentUid();
	    $queue = SaasLazadaUser::find()->where('status<>3 and platform="jumia"')->andWhere(['puid'=>$puid]);
	    if(!empty($accountList))
	        $queue->andWhere(['platform_userid'=>$accountList]);
	    
	    $lazadaUsers = $queue->all();
	    foreach ($lazadaUsers as $lazadaUser) {
	        $lazadauserMap[$lazadaUser->lazada_uid] = array(
	                "userId" => $lazadaUser->platform_userid,
	                "apiKey" => $lazadaUser->token,
	                "platform" => $lazadaUser->platform,
	                "countryCode" => $lazadaUser->lazada_site,
	                "puid" => $lazadaUser->puid,
	                "store_name" => $lazadaUser->store_name,
	        );
	    }
	
	    return $lazadauserMap;
	}
	
	
	

}
