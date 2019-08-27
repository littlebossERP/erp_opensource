<?php

/**
 *  Cdiscount 小老板系统Cdiscount商品管理
 */
namespace eagle\modules\listing\controllers;

use yii;
use yii\filters\VerbFilter;
use yii\data\Sort;
use eagle\modules\util\helpers\ExcelHelper;
use eagle\modules\app\apihelpers\AppTrackerApiHelper;
use eagle\modules\listing\helpers\CdiscountOfferSyncHelper;
use eagle\modules\listing\models\CdiscountOfferList;
use eagle\models\SaasCdiscountUser;
use eagle\modules\util\helpers\SysLogHelper;
use eagle\modules\listing\models\CdiscountOfferTerminator;
use eagle\modules\listing\helpers\CdiscountOfferTerminatorHelper;
use eagle\modules\util\helpers\TimeUtil;
use eagle\modules\platform\apihelpers\CdiscountAccountsApiHelper;
use common\helpers\Helper_Array;
use eagle\modules\util\helpers\MailHelper;
use eagle\modules\util\helpers\ConfigHelper;
use eagle\modules\util\helpers\RedisHelper;
use eagle\modules\listing\models\HotsaleProduct;
use eagle\modules\listing\models\FollowedProduct;
use eagle\modules\util\helpers\SQLHelper;

class CdiscountController extends \eagle\components\Controller
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


    /**
     +---------------------------------------------------------------------------------------------
     * 列表页面
     * @author		lzhl	2016/07/--		初始化
     +---------------------------------------------------------------------------------------------
     **/
    public function actionIndex(){
    	//check模块权限
    	if(!\eagle\modules\permission\apihelpers\UserApiHelper::checkModulePermission('cdiscount')){
    		return $this->render('//errorview_no_close',['title'=>'访问受限:没有权限','error'=>'您还没有Cdiscount跟卖终结者的权限!']);
    	}
    	
        AppTrackerApiHelper::actionLog("cdOfferTerminator", "/listing/cdiscount/index");
		
        $params=[];
        $sort = !empty($_REQUEST['sort']) ? $_REQUEST['sort'] : '-creation_date';
        if( '-' == substr($sort,0,1) ){
        	$sort = substr($sort,1);
        	$order = 'desc';
        } else {
        	$order = 'asc';
        }
        $sortConfig = new Sort(['attributes' => ['stock','price','creation_date','last_15_days_sold']]);
        if(!in_array($sort, array_keys($sortConfig->attributes))){
        	$sort = '';
        	$order = '';
        }
        if(!empty($sort) && !empty($order))
        	$params['orderBy'] = $sort.' '.$order;
        
        foreach ($_REQUEST as $param=>$value){
        	//if(in_array($param,['sort','page'])){
        	//	
        	//}
        	if($value=='')
        		continue;
        	$params[$param] = $value;
        }
        
        $page_url = '/listing/cdiscount/index';
        $last_page_size = ConfigHelper::getPageLastOpenedSize($page_url);
        if(empty($last_page_size))
        	$last_page_size = 50;//默认显示值
        $params['per-page'] = empty($_REQUEST['per-page'])?$last_page_size:$_REQUEST['per-page'];
        ConfigHelper::setPageLastOpenedSize($page_url,$params['per-page']);
        
        $offers = CdiscountOfferSyncHelper::getOfferListByCondition($params);
        // 当前user 的puid 绑定的 cdiscount 卖家账号
        $puid = \Yii::$app->user->identity->getParentUid();
        $cdiscountUsers = SaasCdiscountUser::find()->where(['uid'=>$puid])->asArray()->all();
        $cdiscountUsersDropdownList = array();
        $accountShopList = array();
        foreach ($cdiscountUsers as $cdiscountUser){
        	$cdiscountUsersDropdownList[$cdiscountUser['username']] = $cdiscountUser['store_name'];
        	if(!empty($cdiscountUser['shopname']))
        		$accountShopList[] = $cdiscountUser['shopname'];
        }
        $isCustomizationUser = false;
        $customizationUsers = CdiscountOfferTerminatorHelper::$customizationUsers;
        if(in_array($puid, $customizationUsers))
        	$isCustomizationUser = true;
        
       	if($isCustomizationUser){
			$userShops = RedisHelper::RedisGet('CDOT_CustomizationUserShops', "user_$puid");
			$userShops = json_decode($userShops,true);
			if(!empty($userShops)){
				foreach ($userShops as $shopname){
					if(!in_array($shopname, $accountShopList)){
						$cdiscountUsersDropdownList[$shopname] = $shopname;
					}
				}
			}
       	} 
        
        $counter = CdiscountOfferTerminatorHelper::getMenuCounter($params);
        
        return $this->render('index',[
        	'offerList'=>$offers,
        	'sort'=>$sortConfig,
        	'cdiscountUsersDropdownList'=>$cdiscountUsersDropdownList,
        	'counter'=>$counter,
        	'isCustomizationUser'=>$isCustomizationUser,
        ]);
    }

    /**
     +---------------------------------------------------------------------------------------------
     * offer库存和价格 列表页面
     * @author		lzhl	2016/07/--		初始化
     +---------------------------------------------------------------------------------------------
     **/
    public function actionExcelStockPrice(){
    	AppTrackerApiHelper::actionLog("cdOfferTerminator", "/listing/cdiscount/excel-stock-price");
    	$params=[];
    	$sort = !empty($_REQUEST['sort']) ? $_REQUEST['sort'] : '-seller_product_id';
    	if( '-' == substr($sort,0,1) ){
    		$sort = substr($sort,1);
    		$order = 'desc';
    	} else {
    		$order = 'asc';
    	}
    	if(!empty($sort) && !empty($order))
    		$params['orderBy'] = $sort.' '.$order;
    	
    	foreach ($_REQUEST as $param=>$value){
    		if(in_array($param,['sort'/*,'page'*/]) || $value=='')
    			continue;
    		if($param=='keyword'){
    			$value = str_replace('；', ';', $value);
    			$value = str_replace(array("\r\n", "\r", "\n"), ";", $value);
    		}
    		
    		$params[$param] = $value;
    	}
    	
    	$offers = CdiscountOfferSyncHelper::getOfferListByCondition($params);
    	//CdiscountOfferList::find();
    	// 当前user 的puid 绑定的 cdiscount 卖家账号
    	$puid = \Yii::$app->user->identity->getParentUid();
    	$cdiscountUsers = SaasCdiscountUser::find()->where(['uid'=>$puid])->asArray()->all();
    	$cdiscountUsersDropdownList = array();
    	foreach ($cdiscountUsers as $cdiscountUser){
    		$cdiscountUsersDropdownList[$cdiscountUser['username']] = $cdiscountUser['store_name'];
    	}
    	
    	return $this->render('excel_stock_price',[
    			'offerList'=>$offers,
    			'cdiscountUsersDropdownList'=>$cdiscountUsersDropdownList,
    			]);
    	
    }
    
    /**
     +---------------------------------------------------------------------------------------------
     * offer库存和价格  导出
     * @author		lzhl	2016/07/--		初始化
     +---------------------------------------------------------------------------------------------
     **/
    public function actionStockPrice2Excel(){
    	AppTrackerApiHelper::actionLog("cdOfferTerminator", "/listing/cdiscount/stock-price-2-excel");
    	$params=[];
    	$sort = !empty($_REQUEST['sort']) ? $_REQUEST['sort'] : '-seller_product_id';
    	if( '-' == substr($sort,0,1) ){
    		$sort = substr($sort,1);
    		$order = 'desc';
    	} else {
    		$order = 'asc';
    	}
    	if(!empty($sort) && !empty($order))
    		$params['orderBy'] = $sort.' '.$order;
    	 
    	foreach ($_REQUEST as $param=>$value){
    		if(in_array($param,['sort',/*'page'*/]) || $value=='')
    			continue;
    		if($param=='keyword'){
    			$value = str_replace('；', ';', $value);
    			$value = str_replace(array("\r\n", "\r", "\n"), ";", $value);
    		}
    		$params[$param] = $value;
    	}
    	
    	$offerData = CdiscountOfferSyncHelper::getOfferListByCondition($params);
    	$offers = empty($offerData['rows'])?[]:$offerData['rows'];
    	//print_r($offers);
    	//exit();
    	$items_arr = ['seller_product_id'=>'SKU','product_id'=>'ProductId','product_ean'=>'EAN','name'=>'商品名称','stock'=>'库存','price'=>'售价(欧元)','bestseller_price'=>'BestSeller价格(欧元)','is_bestseller'=>'是否BestSeller','bestseller_name'=>'BestSeller店名'];
    	$keys = array_keys($items_arr);
    	$excel_data = [];
    	foreach ($offers as $index=>$row){
    		$tmp=[];
    		foreach ($keys as $key){
    			if(isset($row[$key])){
    				if(in_array($key,['seller_product_id','product_ean']) && is_numeric($row[$key]))
    					$tmp[$key]=' '.$row[$key];
    				elseif($key=='is_bestseller'){
    					if($row[$key]=='Y')
    						$tmp[$key]='是';
    					else
    						$tmp[$key]='否';
    				}
    				else 
    					$tmp[$key]=(string)$row[$key];
    			}
    		}
    		$excel_data[$index] = $tmp;
    	}
    	ExcelHelper::exportToExcel($excel_data, $items_arr, 'cd_offer_'.date('Y-m-dHis',time()).".xls");
    }
    
    /**
     +---------------------------------------------------------------------------------------------
     * offer 关注状态等级 设置
     * @author		lzhl	2016/07/--		初始化
     +---------------------------------------------------------------------------------------------
     **/
    public function actionSetConcerned(){
    	AppTrackerApiHelper::actionLog("cdOfferTerminator", "/listing/cdiscount/set-concerned-status");
    	$offer_ids = $_REQUEST['offer_ids'];
    	$offer_arr = explode(';', $offer_ids);
    	$setType = $_REQUEST['type'];
    	$rtn = ['success'=>true,'message'=>''];
    	if(empty($setType) || !in_array($setType,['N','F','I','H'])){
    		$rtn['success'] = false;
    		$rtn['message'] = '无效的操作类型！';
    		exit(json_encode($rtn));
    	}
    	if($setType=='N'){//设置未普通
    		$rtn = CdiscountOfferSyncHelper::setConcernedNormalOffer($offer_arr);
    	}
    	elseif($setType=='H'){//设置爆款
    		$rtn = CdiscountOfferSyncHelper::setHotSaleOffer($offer_arr);
    	}
    	elseif($setType=='F'){//设置关注
    		$rtn = CdiscountOfferSyncHelper::setConcernedOffer($offer_arr);
    	}
    	elseif($setType=='I'){//设置忽略
    		$rtn = CdiscountOfferSyncHelper::setConcernedIgnoreOffer($offer_arr);
    	}
    	$puid = \Yii::$app->user->identity->getParentUid();
    	CdiscountAccountsApiHelper::clearCdTerminatorVipQuotaSnapshot($puid);
    	exit(json_encode($rtn));
    }
   
    /**
     +---------------------------------------------------------------------------------------------
     * 重新生效  超限失效的offer 状态
     * @author		lzhl	2016/07/--		初始化
     +---------------------------------------------------------------------------------------------
     **/
    public function actionReActiveTStatus(){
    	AppTrackerApiHelper::actionLog("cdOfferTerminator", "/listing/cdiscount/reactive-terminator-status");
    	$offer_ids = $_REQUEST['offer_ids'];
    	$offer_arr = explode(';', $offer_ids);
    	
    	$rtn = CdiscountOfferSyncHelper::reActiveTerminatorStatus($offer_arr);
    	
    	$puid = \Yii::$app->user->identity->getParentUid();
    	CdiscountAccountsApiHelper::clearCdTerminatorVipQuotaSnapshot($puid);
    	exit(json_encode($rtn));
    	
    }
    
    /**
     +---------------------------------------------------------------------------------------------
     * 账号信息
     * @author		lzhl	2016/07/--		初始化
     +---------------------------------------------------------------------------------------------
     **/
    public function actionAccountList(){
    	$puid = \Yii::$app->user->identity->getParentUid();
    	if(empty($puid))
    		return '请先登录。';
    	$userAccounts = SaasCdiscountUser::find()->where(['uid'=>$puid])->all();
    	return $this->renderAjax('_list_store_get_offer',[
    			'accounts'=>$userAccounts,
    		]);
    }
    
    /**
     +---------------------------------------------------------------------------------------------
     * 设置账号fetcht_offer_list_time，重新获取full offer list
     * @author		lzhl	2016/07/--		初始化
     +---------------------------------------------------------------------------------------------
     **/
    public function actionGetFullOfferList(){
    	AppTrackerApiHelper::actionLog("cdOfferTerminator", "/listing/cdiscount/get-full-offer-list");
    	$rtn = ['success'=>true,'message'=>''];
    	$puid = \Yii::$app->user->identity->getParentUid();
    	if(empty($puid))
    		exit(json_encode(['success'=>false,'message'=>'请先登录。']));
    	$seller = empty($_REQUEST['seller_id'])?'':trim($_REQUEST['seller_id']);
    	if(empty($seller)){
    		exit(json_encode(['success'=>false,'message'=>'请选择优先的Cdiscount账号']));
    	}
    	$sellers = explode(';', $seller);
    	//print_r($sellers);
    	$seller_arr=[];
    	foreach ($sellers as $s){
    		if(trim($s)!=='')
    			$seller_arr[]=$s;
    	}
    	//print_r($seller_arr);
    	if(!empty($seller_arr)){
    		try {
    			foreach ($seller_arr as $username){
    				$account = SaasCdiscountUser::find()->where(['uid'=>$puid,'username'=>$username])->one();
    				if(empty($account)){
    					$rtn['success'] = false;
    					$rtn['message'] += $username.'未绑定;<br>';
    					continue;
    				}
    				$account->fetcht_offer_list_time = null;
    				if(!$account->save()){
    					$rtn['success'] = false;
    					$rtn['message'] += $username.'信息更新失败;<br>';
    				}
    			}
    		}catch (\Exception $e) {
				$rtn['success'] = false;
				$rtn['message'] = '更新信息到数据库失败！';
				SysLogHelper::SysLog_Create('Listing',__CLASS__, __FUNCTION__,'error',is_array($e->getMessage())?json_encode($e->getMessage()):$e->getMessage());				
			}
    	}
    	exit(json_encode($rtn));
    } 
    
    /**
     +---------------------------------------------------------------------------------------------
     * 查看offer addi info
     * @author		lzhl	2016/07/--		初始化
     +---------------------------------------------------------------------------------------------
     **/
    public function actionViewOffer($id){
    	$errMsg = '';
    	$offer=[];
    	if(!is_numeric($id))
    		$errMsg = '无效的在线商品 id ！';
    	else{
    		$offer = CdiscountOfferList::find()->where(['id'=>$id])->asArray()->One();
    		if(empty($offer))
    			$errMsg = '无该id的cdiscount商品';
    	}
    	return $this->renderAjax('view_offer',[
    			'offer'=>$offer,
    			'errMsg'=>$errMsg,
    			]);
    }
   
    /**
     +---------------------------------------------------------------------------------------------
     *查看offer 的跟卖历史
     * @author		lzhl	2016/07/--		初始化
     +---------------------------------------------------------------------------------------------
     **/
    public function actionViewHistory($product_id){
    	AppTrackerApiHelper::actionLog("cdOfferTerminator", "/listing/cdiscount/view-histroy");
    	$errMsg = '';
    	$historys=[];
    	if(empty($product_id))
    		$errMsg = '未指定product id !';
    	else{
    		if(preg_match('/\-/', $product_id)){//is a variant child
    			$productIdStr = explode('-', $product_id);
    			if(!empty($productIdStr[0])){
    				$product_id = $productIdStr[0];//product_id = parent_id
    			}
    		}
    		$historys = CdiscountOfferTerminator::find()->where(['product_id'=>$product_id])->orderBy("`create` DESC")->asArray()->All();
    		if(empty($historys))
    			$errMsg = '未有获取历史,或该商品未有卖家销售';
    	}
    	$uid = \Yii::$app->user->id;
    	$userSellerAccounts = SaasCdiscountUser::find()->where(['uid'=>$uid])->all();
    	$userSellerNames = [];
    	foreach ($userSellerAccounts as $account){
    		$userSellerNames[]=$account->shopname;
    	}
    	$customizationVips = CdiscountOfferTerminatorHelper::$customizationUsers;
    	if(in_array($uid, $customizationVips)){
    		$customizationUserShops = RedisHelper::RedisGet("CDOT_CustomizationUserShops","user_$uid");
    		$customizationUserShops = empty($customizationUserShops)?[]:json_decode($customizationUserShops,true);
    		foreach ($customizationUserShops as $shop_name){
    			if(!in_array($shop_name,$userSellerNames))
    				$userSellerNames[] = $shop_name;
    		}
    	}
    	return $this->renderAjax('_view_history',[
    			'historys'=>$historys,
    			'errMsg'=>$errMsg,
    			'shopNames'=>$userSellerNames,
    			]);
    }
    
    /**
     +---------------------------------------------------------------------------------------------
     * 查看额度信息
     * @author		lzhl	2016/07/--		初始化
     +---------------------------------------------------------------------------------------------
     **/
    public function actionViewQuota(){
    	AppTrackerApiHelper::actionLog("cdOfferTerminator", "/listing/cdiscount/view-quota");
    	$puid = \Yii::$app->user->identity->getParentUid();
    	$data = CdiscountOfferSyncHelper::getUserQuotaInfo($puid);
    	$frequency_description = CdiscountOfferTerminatorHelper::get_vip_fetch_frequency_description($data['vip_info']['vip_rank']);
    	return $this->renderAjax('_view_user_quota',[
    				'accounts'=>$data['accounts'],
    				'userVipInfo'=>$data['vip_info'],
    	            'frequency_description'=>$frequency_description,
    			]);
    }
    
    /**
     +---------------------------------------------------------------------------------------------
     * 重设额度redis数据
     * @author		lzhl	2016/07/--		初始化
     +---------------------------------------------------------------------------------------------
     **/
    public function actionResetRedis(){
    	$uid = \Yii::$app->user->identity->getParentUid();
    	//\Yii::$app->redis->hdel("CdiscountAccountMaxFellow","user_$uid");
    	//\Yii::$app->redis->hdel("CdiscountAccountAddiFellow","user_$uid");
    	
    	//\Yii::$app->redis->hdel("CdiscountAccountMaxHotSale","user_$uid");
    	//\Yii::$app->redis->hdel("CdiscountAccountAddiHotSale","user_$uid");
    	//\Yii::$app->redis->hset("CdOffernNewlyLostBestSeller","user_$uid",json_encode(['MOK3662440665606','AUC3662440660540']));
		
		RedisHelper::RedisDel("CdiscountAccountMaxFellow","user_$uid" );
		RedisHelper::RedisDel("CdiscountAccountAddiFellow","user_$uid" );
		
		RedisHelper::RedisDel("CdiscountAccountMaxHotSale","user_$uid");
		RedisHelper::RedisDel("CdiscountAccountAddiHotSale","user_$uid");
		RedisHelper::RedisSet("CdOffernNewlyLostBestSeller","user_$uid");
    }
    
    public function actionResetLostBestSellerRedis(){
    	$uid = \Yii::$app->user->identity->getParentUid();
    	RedisHelper::RedisSet("CdOffernNewlyLostBestSeller","user_$uid");
    }
    
    protected function getMenu(){
        return [
            '刊登管理'=>[
                'icon'=>'icon-shezhi',
                'items'=>[
                    'Wish搬家 <font style="color: red">测试版</font>' => [
                        'url' => '/listing/ensogo-offline/store-move?platform=wish',
                    ],
                   	'速卖通搬家 <font style="color: red">测试版</font>'=>[
                      	'url'=>'/listing/ensogo-offline/store-move-by-file',
                  	],
                    '待发布'=>[
                        'url'=>'/listing/ensogo-offline/ensogo-post',
                    ],
                    '刊登失败'=>[
                        'url'=>'/listing/ensogo-offline/product-failed-post',
                    ],
                ]
            ],
            '商品列表'=>[
                'icon'=>'icon-pingtairizhi',
                'items'=>[
                    '在线商品'=>[
                        'url'=>'/listing/ensogo-online/online-product-list',
                    ],
                    '下架商品'=>[
                        'url'=>'/listing/ensogo-online/offline-product-list',
                    ],
                ]
            ],
        ];
    }
    
    public function actionDailyStatistics(){
    	AppTrackerApiHelper::actionLog("cdOfferTerminator", "/listing/cdiscount/daily-statistics");
    	$uid = \Yii::$app->user->identity->getParentUid();
    	
    	$params = [];
    	//卖家账号参数
    	$seller_id = (empty($_REQUEST['seller_id']))?'':trim($_REQUEST['seller_id']);
    	if(!empty($seller_id))
    		$params['seller_id'] = $seller_id;
    	//日期参数
    	$date = (empty($_REQUEST['date']))?'':trim($_REQUEST['date']);
    	if(!empty($date))
    		$params['date'] = $date;
    	//是否有被抢过buyerbox
    	$ever_been_surpassed = (empty($_REQUEST['been_surpassed']))?'':trim($_REQUEST['been_surpassed']);
    	if(!empty($ever_been_surpassed) && strtoupper($ever_been_surpassed)=='Y')
    		$params['ever_been_surpassed'] = 'Y';
    	//关注等级查询参数
    	if(!empty($_REQUEST['type']))
    		$params['type'] = trim($_REQUEST['type']);
    	//商品查询
    	$key_word = [];
    	if(!empty($_REQUEST['key_word'])){
    		$tmp_keyword = str_replace('；', ';', $_REQUEST['key_word']);
    		$key_word = explode(';', $tmp_keyword);
    		$key_word = Helper_Array::removeEmpty($key_word);
    	}
    	
    	if(!empty($key_word))
    		$params['key_word'] = $key_word;
    	//排序参数
    	$sort = !empty($_REQUEST['sort']) ? $_REQUEST['sort'] : '-seller_product_id';
    	if( '-' == substr($sort,0,1) ){
    		$sort = substr($sort,1);
    		$order = 'desc';
    	} else {
    		$order = 'asc';
    	}
    	//分页参数
    	if(!empty($_REQUEST['per-page'])){
    		$pageSize = (int)$_REQUEST['per-page'];
    	}else
    		$pageSize = 50;
    	
    	$sortConfig = new Sort(['attributes' => ['seller_product_id','type','ever_been_surpassed','seller_id']]);
    	if(!in_array($sort, array_keys($sortConfig->attributes))){
    		$sort = '';
    		$order = '';
    	}
    	
    	$listDatas = CdiscountOfferTerminatorHelper::getTerminatorDailyStatisticsList($sort,$order,$pageSize,$_REQUEST,$params);
    	
    	$accounts = CdiscountAccountsApiHelper::ListAccounts($uid);
    	$userAccounts = [];
    	$shop_names=[];
    	foreach ($accounts as $account){
    		$userAccounts[$account['username']] = $account['shopname'].'('.$account['store_name'].')';
    		$shop_names[$account['shopname']] = $account['shopname'].'('.$account['store_name'].')';
    	}
    	
    	//$counter = CdiscountOfferTerminatorHelper::getMenuCounter($params);
    	
    	return $this->render('terminator_daily_statistics',[
    		'data'=>$listDatas['data'],
    		'pagination'=>$listDatas['pagination'],
    		'accounts' =>$userAccounts,
    		'shop_names'=>$shop_names,
    		'counter'=>[],
    			
    	]);
    }
    
    
    public function actionSetting(){
    	$err_message = [];
    	$set = false;//是否有设置操作
    	$uid = \Yii::$app->user->id;
    	if(isset($_REQUEST['user_valid_mail_address'])){
    		$_REQUEST['user_valid_mail_address'] = trim($_REQUEST['user_valid_mail_address']);
	    	if(empty($_REQUEST['user_valid_mail_address']) ){
	    		$err_message[] = "必须输入一个有效的邮箱地址";
	    	}else{
	    		//$set = ConfigHelper::setConfig("User/user_valid_mail_address",$_REQUEST['user_valid_mail_address']);
	    		$set = RedisHelper::RedisSet('user_valid_mail_address', 'cd_terminator_uid_'.$uid ,$_REQUEST['user_valid_mail_address']);
	    		if($set==-1)
	    			$err_message[] = '收件邮箱设置失败E_V001!';
	    	}
    	}
    	//设置是否支持发送每日统计邮件
    	if(isset($_REQUEST['can_send_mail'])){
    		$set = true;
	    	if(empty($_REQUEST['can_send_mail'])) $can_send_mail = 'Y';
	    	else $can_send_mail = $_REQUEST['can_send_mail'];
	    	
	    	if($can_send_mail!=='Y' && $can_send_mail!=='N')
	    		$err_message[] = '每日统计邮件发送设置失败：无效的设置类型';
	    	
	    	$set = ConfigHelper::setConfig("Listing/send_cd_terminator_mail",$can_send_mail);
	    	if(!$set)
	    		$err_message[] = '每日统计邮件发送设置失败E_M001!';
    	}
    	
    	//下面设置仅对VIP有效
    	if(isset($_REQUEST['can_send_announce'])){
    		$set = true;
    		//设置是否支持发送定时自动bestseller被抢通知
	    	$can_send_announce = $_REQUEST['can_send_announce'];
	    	 
	    	if($can_send_announce!=='Y' && $can_send_announce!=='N')
	    		$err_message[] = '定时BestSeller被抢提醒设置失败：无效的设置类型';
	    	
	    	$set = ConfigHelper::setConfig("Listing/send_cd_terminator_announce",$can_send_announce);
	    	if(!$set)
	    		$err_message[] = '定时BestSeller被抢提醒设置失败E_A001!';
	    	
	    	//设置发送定时自动bestseller被抢通知频率
	    	if(empty($_REQUEST['send_announce_frequency'])) $send_announce_frequency = '3h';
	    	else $send_announce_frequency = $_REQUEST['send_announce_frequency'];
	    	 
	    	if(!in_array($send_announce_frequency,['0.5h','1h','3h','6h']))
	    		$err_message[] = '定时BestSeller被抢提醒频率设置失败：无效的设置类型';
	    	 
	    	$set = ConfigHelper::setConfig("Listing/send_cd_terminator_announce_frequency",$send_announce_frequency);
	    	if(!$set)
	    		$err_message[] = '定时BestSeller被抢提醒频率设置失败E_A002!';
    	}
    	
    	return $this->render('setting',['err_message'=>$err_message,'set'=>$set]);
    }
    
    public function actionExcelUpload(){
    	return $this->renderPartial('_import_sku_box',[]);
    }
    
    /**
     * 导入SKu
     * @author lzhl
     */
    public function actionImportSku(){
    	if (\yii::$app->request->isPost){
    		if (isset($_FILES['sku_list_excel'])){
    			try {
    				$puid = \Yii::$app->user->identity->getParentUid();
    				if(!$puid)
    					return json_encode(['ack'=>'failure','message'=>'请先登录']);
    				$result = CdiscountOfferTerminatorHelper::importProductIdFromExcel($_FILES['sku_list_excel'],$puid);
    				return $result;
    			}catch(\Exception $e){
    				return $e->getMessage();
    			}
    		}
    	}
    }
    
    public function actionTestMail(){
    	$puid= \Yii::$app->user->identity->getParentUid();
    	$date = date("Y-m-d",time()-3600*24);
    	$email = "shanshan.yang@witsion.com";
    	$rtn = CdiscountOfferTerminatorHelper::sendTerminatorDailyStatisticsMailToUser($puid,$email,$date);
    	
    	exit();
    }
    
    public function actionTestFullJob(){
    	$rtn = CdiscountOfferTerminatorHelper::TerminatorDailyStatistics();
    	return;
    }
    
    public function actionTestCdApi(){
    	$ids=['HONOR8PRONOIR','USI2009834185158','AUC0761710804431','AUC0699962164662','AUC0638264989818','SHA2009840289819','SHA2009840290488','SHA2009842955286','SHA6699387548911','SHA2009853140961','SHA2009852473541','SHA6699387548911'];
    	foreach ($ids as $id){
    		self::localtestapi($id);
    	}
    }
    
    public static function localtestapi($id){
    	$params=[
    		'post'=>[
    			'ApiKey'=>"a62eba53-04de-477e-bdee-68edc00a09bd",
    			'ProductRequest'=>[
    				'ProductIdList'=>[$id],
    				'Scope'=>[
    					'Offers'=>true,
    					'AssociatedProducts'=>true,
        				'Images'=>true,
						'Ean'=>true,
					],
				],
			],
			/*
			'header'=>[
				'Content-Type: application/json;charset=utf-8'
	        ],
	        */
		];
		$url = 'https://api.cdiscount.com/OpenApi/json/GetProduct';
    	
		if (!empty($params['get'])){
			$get_params = $params['get'];
    	}
    	
    	if (!empty($params['post'])){
    	$post_params = $params['post'];
    	}
    	
    	if (!empty($params['header'])){
    		$header = $params['header'];
    	}
    	
    	if (!empty($get_params))
    		$url .= "?".http_build_query($get_params);
    	 
    	$handle = curl_init($url);
    	echo "<br>".$url."";//test kh
    	
    	curl_setopt($handle,  CURLOPT_RETURNTRANSFER, TRUE);
    	curl_setopt($handle, CURLOPT_TIMEOUT, $TIME_OUT=60);
    	//echo "time out : ".$TIME_OUT;
    	
    	if (!empty($header)){
    		curl_setopt($handle, CURLOPT_HTTPHEADER, $header);
    	}
    	//  output  header information
    	curl_setopt($handle, CURLINFO_HEADER_OUT , true);
    	curl_setopt($handle, CURLOPT_FOLLOWLOCATION, 1);
    	if (!empty($post_params)){
    		curl_setopt($handle, CURLOPT_POST, true);
    		 
    		if (is_array($post_params))
    			$post_params = json_encode($post_params);
    		curl_setopt($handle, CURLOPT_POSTFIELDS, $post_params );
    		echo "<br> post : ".json_encode($post_params)."<br> " ;
    	}
    	/* Get the HTML or whatever is linked in $url. */
    	$response = curl_exec($handle);
    	$curl_errno = curl_errno($handle);
    	$httpCode = curl_getinfo($handle, CURLINFO_HTTP_CODE);
    	var_dump($curl_errno);
    	var_dump($httpCode);
    	var_dump($response);
    	curl_close($handle);
    }
    
    public function actionTestCurl(){
    	$urls = [
    		"https://api.cdiscount.com/OpenApi/json/GetProduct",
    		"https://www.baidu.com",
    		"https://www.yangrenwu.com",
    		"https://www.facebook.com",
    		"https://www.imsupperman.com",
    	];
    	foreach ($urls as $url){
	    	echo $url."<br>";
	    	$handle = curl_init($url);
	    	curl_setopt($handle,  CURLOPT_RETURNTRANSFER, TRUE);
	    	curl_setopt($handle, CURLOPT_TIMEOUT, $TIME_OUT=60);
	    	curl_setopt($handle, CURLINFO_HEADER_OUT , true);
	    	
	    	$response = curl_exec($handle);
	    	$curl_errno = curl_errno($handle);
	    	$httpCode = curl_getinfo($handle, CURLINFO_HTTP_CODE);
	    	var_dump($curl_errno);
	    	var_dump($httpCode);
	    	//var_dump($response);
	    	curl_close($handle);
    	}
    }
    
    public function actionOfferStatusToQueue(){
    	$puid= \Yii::$app->user->identity->getParentUid();
    	$status = $_REQUEST['status'];
    	$rtn = ['success'=>true,'message'=>''];
    	if(!in_array($status,['H','F','N','I'])){
    		$rtn['success'] = false;
    		$rtn['message'] = '无效的操作类型！';
    		exit(json_encode($rtn));
    	}
    	$query = CdiscountOfferList::find()->select('product_id')->where(["concerned_status"=>$status]);
    	
    	if($status=='H' || $status=='F'){
    		$del_query = CdiscountOfferList::find()->select('product_id')->where(["concerned_status"=>$status])->asArray()->all();
    		$del_arr = [];
    		foreach ($del_query as $del_pid){
    			$del_arr[] = $del_pid['product_id'];
    		}
    		if(!empty($del_arr)){
    			if($status=='H')
    				FollowedProduct::deleteAll(['puid'=>$puid,'product_id'=>$del_arr]);
    			if($status=='F')
    				HotsaleProduct::deleteAll(['puid'=>$puid,'product_id'=>$del_arr]);
    		}
    		
    		if($status=='H'){
    			$exist = HotsaleProduct::find()->select('product_id')->where(["puid"=>$puid,])->asArray()->all();
    		}
    		if($status=='F'){
    			$exist = FollowedProduct::find()->select('product_id')->where(["puid"=>$puid,])->asArray()->all();
    		}
    		
    		$exist_ids = [];
    		foreach ($exist as $ex){
    			$exist_ids[] = $ex['product_id'];
    		}
    		if(!empty($exist_ids))
    			$query->andWhere(["not", ["product_id"=>$exist_ids]]);
    		$to_inster = $query->asArray()->all();
    		$to_inster_datas = [];
    		foreach ($to_inster as $to_id){
    			$tmpData = [];
    			$tmpData['puid'] = $puid;
    			$tmpData['product_id'] = $to_id['product_id'];
    			$tmpData['create_time'] = $tmpData['update_time'] = date("Y-m-d H:i:s",time());
    			$to_inster_datas[] = $tmpData;
    		}
    		if(!empty($to_inster_datas)){
    			if($status=='H'){
    				$table_name = 'cdot_hotsale_product';
    			}
    			if($status=='F'){
    				$table_name = 'cdot_followed_product';
    			}
    			$r = SQLHelper::groupInsertToDb($table_name, $to_inster_datas, 'db_queue');
    		}
    	}elseif($status=='N' || $status=='I'){
    		$del_query = CdiscountOfferList::find()->select('product_id')->where(["concerned_status"=>$status])->asArray()->all();
    		$del_arr = [];
    		foreach ($del_query as $del_pid){
    			$del_arr[] = $del_pid['product_id'];
    		}
    		if(!empty($del_arr)){
    			$r = FollowedProduct::deleteAll(['puid'=>$puid,'product_id'=>$del_arr]);
    			$r += HotsaleProduct::deleteAll(['puid'=>$puid,'product_id'=>$del_arr]);
    		}
    	}
    	
    	$rtn['message'] = '影响了'.$r.'条记录';
    	exit(json_encode($rtn));
    }
	
	public function actionPushProductToQueue(){
		$pw = $_REQUEST['pw'];
		$type = $_REQUEST['type'];
		if($pw!=='eagleasd123')
			return;
		
		$puid= \Yii::$app->user->identity->getParentUid();
		if($type=='H'){
			CdiscountOfferTerminatorHelper::pushHotSaleProductToQueue($puid);
		}elseif($type=='F'){
			CdiscountOfferTerminatorHelper::pushFollowedProductToQueue($puid);
		}
	}
}