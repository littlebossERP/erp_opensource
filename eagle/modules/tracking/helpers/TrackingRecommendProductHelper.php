<?php
/**
 * @link http://www.witsion.com/
 * @copyright Copyright (c) 2014 Yii Software LLC
 * @license http://www.witsion.com/
 */
namespace eagle\modules\tracking\helpers;

use yii\base\Model;
use eagle\modules\tracking\models\TrackerRecommendProduct;
use eagle\modules\listing\models\EbayItem;
use eagle\models\AliexpressChildorderlist;
use eagle\modules\message\models\CsRecommendProduct;
use eagle\modules\util\helpers\SysBaseInfoHelper;
use eagle\models\UserBase;
use eagle\modules\listing\apihelpers\ListingAliexpressApiHelper;
use eagle\modules\util\models\UserBackgroundJobControll;
use eagle\modules\util\helpers\TimeUtil;
use eagle\modules\util\helpers\ConfigHelper;


class TrackingRecommendProductHelper{


	public static $cronJobId=0;
	private static $trackerRecommendVersion = null;

	/**
	 * @return the $cronJobId
	 */
	public static function getCronJobId() {
		return self::$cronJobId;
	}
	
	/**
	 * @param number $cronJobId
	 */
	public static function setCronJobId($cronJobId) {
		self::$cronJobId = $cronJobId;
	}
	
	
	/**
	 * 该进程判断是否需要退出
	 * 通过配置全局配置数据表ut_global_config_data的Order/dhgateGetOrderVersion 对应数值
	 *
	 * @return  true or false
	 */
	private static function checkNeedExitOrNot(){
		
		$trackerRecommendVersionFromConfig = ConfigHelper::getGlobalConfig("Tracker/recommendVersion",'NO_CACHE');
		if (empty($trackerRecommendVersionFromConfig))  {
			//数据表没有定义该字段，不退出。
			//	self::$dhgateGetOrderListVersion ="v0";
			return false;
		}
	
		//如果自己还没有定义，去使用global config来初始化自己
		if (self::$trackerRecommendVersion===null)	self::$trackerRecommendVersion = $trackerRecommendVersionFromConfig;
			
		//如果自己的version不同于全局要求的version，自己退出，从新起job来使用新代码
		if (self::$trackerRecommendVersion <> $trackerRecommendVersionFromConfig){
			echo "Version new $trackerRecommendVersionFromConfig , this job ver ".self::$trackerRecommendVersion." exits \n";
			return true;
	
		}
	
		return false;
	}
	
	private static function handleBgJobError($recommendJobObj,$errorMessage){
		$nowTime=time();
		$recommendJobObj->status=3;
		$recommendJobObj->error_count=$recommendJobObj->error_count+1;
		$recommendJobObj->error_message=$errorMessage;
		$recommendJobObj->last_finish_time=$nowTime;
		$recommendJobObj->update_time=$nowTime;
		$recommendJobObj->next_execution_time=$nowTime+30*60;//半个小时后重试
		$recommendJobObj->save(false);
		return true;
	}
	

	/**
	 +---------------------------------------------------------------------------------------------
	 * 提取第一次需要运行推荐商品的客户puid，运行并提取推荐商品---后台程序触发
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param $type--  FirstTime, Update
	 * FirstTime--第一次计算推荐商品
	 *
	 +---------------------------------------------------------------------------------------------
	 * @return string
	 *
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		xjq		2015/08/14				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	public static function generateTrackerRecommendProducts($type="Update"){

		echo "++++++++++++generate_tracker_recommend_products_$type \n";
		//1. 检查当前本job版本，如果和自己的版本不一样，就自动exit吧
		$ret=self::checkNeedExitOrNot();
		if ($ret===true) exit;
		
		$backgroundJobId=self::getCronJobId(); //获取进程id号，主要是为了打印log
		$connection=\Yii::$app->db;
		$hasGotRecord=false;//是否抢到账号
		
		//2. 从账户同步表（订单列表同步表）saas_dhgate_autosync 提取带同步的账号。          status--- 0 没处理过; 2--已完成; 3--上一次执行有问题;
		if ($type=="FirstTime"){
			$recommendJobObjs=UserBackgroundJobControll::find()
			->where('is_active = "Y" AND status =0 AND job_name="tracker_recommend" ')		
			->all(); // 多到一定程度的时候，就需要使用多进程的方式来并发拉取。
		}else{ //$type=="Update"
			$nowTime=time();
			$recommendJobObjs=UserBackgroundJobControll::find()
			->where('is_active = "Y" AND status in (2,3) AND job_name="tracker_recommend" AND error_count<5 AND next_execution_time<'.$nowTime)
			->all(); // 多到一定程度的时候，就需要使用多进程的方式来并发拉取。
					
		}
		
				
	
		if(count($recommendJobObjs)){
			foreach($recommendJobObjs as $recommendJobObj) {
				//echo "=========begin SAA_obj merchantId:".$SAA_obj->merchant_id.",marketplaceId:".$SAA_obj->marketplace_id." \n";
		
				//1. 先判断是否可以正常抢到该记录
				$command = $connection->createCommand("update user_background_job_controll set status=1 ".
						" where id =". $recommendJobObj->id." and status<>1 ") ;
				$affectRows = $command->execute();
				if ($affectRows <= 0)	continue; //抢不到		
				\Yii::info("generate_tracker_recommend_products_$type puid:".$recommendJobObj->puid,"file");
		
				//2. 抢到记录，设置同步需要的参数
				$hasGotRecord=true;
				//由于SAA_obj->id对应的记录被修改，这里重新加载一次
				
				$recommendJobObj=UserBackgroundJobControll::findOne($recommendJobObj->id);
				$puid=$recommendJobObj->puid;
				
				echo "puid:$puid \n";
 
				
				$nowTime=time();
				$recommendJobObj->last_begin_run_time=$nowTime;
				
				
				$timeMS1=TimeUtil::getCurrentTimestampMS();
								
				//1. ebay的候选推荐商品
				self::_generateTrackerRecommendProductsForEbay();
				$timeMS2=TimeUtil::getCurrentTimestampMS();
				//2. aliexpress的候选推荐商品
				self::_generateTrackerRecommendProductsForAliexpress();
				$timeMS3=TimeUtil::getCurrentTimestampMS();
				
				
				$nowTime=time();
				$recommendJobObj->status=2;
				$recommendJobObj->error_count=0;
				$recommendJobObj->error_message="";
				$recommendJobObj->last_finish_time=$nowTime;
				$recommendJobObj->update_time=$nowTime;
				$recommendJobObj->next_execution_time=$nowTime+24*3600;//24个小时后重试
				$recommendJobObj->save(false);

				$logStr="generate_tracker_recommend_products_$type puid=$puid,afid_jobid=$backgroundJobId,t2_1=".($timeMS2-$timeMS1).
				",t3_2=".($timeMS3-$timeMS2).",t3_1=".($timeMS3-$timeMS1);
				echo $logStr." \n"; 
				\Yii::info($logStr,"file");
								
				
			}
		}
			
		
	}
	
	
	
	
	
	
	
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 新建或更新tracker的用户登陆页面看到的推荐商品集合---后台程序触发
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param  
	 *
	 +---------------------------------------------------------------------------------------------
	 * @return string
	 *						
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		xjq		2015/07/02				初始化
	 +---------------------------------------------------------------------------------------------	 
	 **/
/*	
	public static function generateTrackerRecommendProducts(){
		echo "generateTrackerRecommendProducts \n";
		
		// 轮询所有user库，提取候选的推荐商品
//		$mainUsers = UserBase::find()->where(['puid'=>0])->asArray()->all();		
//		foreach ($mainUsers as $puser){
		    
			
		
			//1. ebay的候选推荐商品
			//self::_generateTrackerRecommendProductsForEbay();
			//2. aliexpress的候选推荐商品
			self::_generateTrackerRecommendProductsForAliexpress();
	//	}
		
	}
*/
	
	
	
	/**
	 * 返回所有速卖通预计在$activeDays天内还在线的listing的信息
	 * @param  $activeDays  ---指定天数 
	 * @return 
	 * array(
	 *    "3434"=>array("productid"=>"3434".....),
	 *    "13434"=>array("productid"=>"13434".....),
	 * )
	 */
	public static function getAllAliexpressListingsMap($activeDays){
		$activeTimestamp=time()+$activeDays*24*3600;
		$productidListingMap=array();
		$productidSelleruserIdMap=array();
		$rows=\Yii::$app->subdb->createCommand("select * from aliexpress_listing where ws_offline_date>".$activeTimestamp)->queryAll();
		foreach($rows as $row){
			$productidListingMap[$row["productid"]]=$row;
			$productidSelleruserIdMap[$row["productid"]]=$row["selleruserid"];
		}
		
		return array($productidSelleruserIdMap,$productidListingMap);
	}
	
	
    //判断是否有库存,无库存的话，就不能作为推荐商品
	private static function _getSkuStockForAliexpressProd($sellerUserId, $productId){
		try{
			$stockNum=ListingAliexpressApiHelper::getSkuStock($sellerUserId, $productId);
		}catch(\Exception $e){
			$errorMessage="Exception error:".$e->getMessage()." trace:".$e->getTraceAsString();
			echo "$errorMessage";
			\Yii::error($errorMessage,"file");
			return array(false,-1);
		}
		
		return array(true,$stockNum);
		
	}
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 针对aliexpress渠道,新建或更新tracker的推荐商品集合
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param  $tracking_id			string	 	物流号 ID
	 *
	 +---------------------------------------------------------------------------------------------
	 * @return string
	 *							Tag Html
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		xjq		2015/07/02				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	private static function _generateTrackerRecommendProductsForAliexpress(){
		echo "Entering _generateTrackerRecommendProductsForAliexpress \n";
		//速卖通是没有销售国家的概念，所以这里对于指定的puid卖家， 根据每个速卖通账号的销售情况，每个速卖通账号选出30个候选商品
		//光顾过账号A的买家， 不管是属于哪个国家都可以有机会看到账号A对应的30个候选商品。
		
		//这里需要为每个aliexpress账号下，从找出n件候选商品， 这里n=n1+n2
		//1） n1是从订单表找出最近最热卖n1个商品。
		//2） n2是从在线listing表中找出最近刊登的n2个商品
		
		
		$activeLastingDays=1;  //要求所有推荐商品至少还有1天才会下架
		$maxCandidateProdNumForOrder=30; //某个速卖通账号，候选的产品的数量最大值
		$maxCandidateProdNum=40; //某个速卖通账号，候选的产品的数量最大值		
		$minStockNum=3; //最少库存量，少于这个数值就当做无库存。
		$productidStockMap=array();// productid和库存的对应关系
		$candidateProdIdList=array();
		
		//1. n1是从订单表找出最近最热卖n1个商品。 根据订单量，收集30天或50天之内的订单情况.这里只获取productid		
		$orderMinNum=2450;
		$days=30;
		$nowTime=time();
		$createTime=$nowTime-$days*24*3600;
		
		$row=\yii::$app->subdb->createCommand("SELECT count(*) as order_count FROM  `od_order_v2` WHERE order_source='aliexpress' and order_source_create_time>".$createTime)->queryOne();
		if ($row['order_count']==0) return array(); // 30天内一张订单都没有的话，就放弃该用户
		if ($row['order_count']<$orderMinNum){
			//订单数量太少，放大提取的时间范围
			$days=50;
			$createTime=$nowTime-$days*24*3600;
		}
		
		
		//获取在售商品信息	
		list($productidSelleruserIdMap,$productidListingMap)=self::getAllAliexpressListingsMap($activeLastingDays);
		
		$rows=\yii::$app->subdb->createCommand(
				"select * from ".
				" (SELECT od.selleroperatorloginid as selleruserid,odi.productid as productid,".
				" sum(odi.productcount) as t_quantity  FROM ".
				" (SELECT * FROM `aliexpress_order` where gmtcreate>".$createTime." LIMIT 0 ,10000) od ".
				" INNER JOIN  aliexpress_childorderlist odi ON od.id = odi.orderid ".
				" group by  od.selleroperatorloginid,odi.productid ) aa order by t_quantity DESC ")->queryAll();
	
		
		$productInfos=array();
		$handledProductIdList=array();
		foreach($rows as $row){
			
	
			//$sellerUserId=$row["selleruserid"];
			$productId=$row["productid"];
			//是否在售商品判断，在线listing获取不了的话，就不能作为推荐商品
			if (!isset($productidSelleruserIdMap[$productId])) continue;
			$sellerUserId=$productidSelleruserIdMap[$productId];
			if (!isset($productInfos[$sellerUserId])) $productInfos[$sellerUserId]=array();
			
	        //合理性判断
			if  (count($productInfos[$sellerUserId])>$maxCandidateProdNumForOrder or in_array($productId,$handledProductIdList)) {
				continue; //产品够多了
			}
			
			$handledProductIdList[]=$productId;
			
	        	
			//判断是否有库存,无库存的话，就不能作为推荐商品
			//$stockNum=ListingAliexpressApiHelper::getSkuStock($sellerUserId, $productId);
			list($ret,$stockNum)=self::_getSkuStockForAliexpressProd($sellerUserId, $productId);
			if ($ret===false){
				//速卖通接口有问题
				return;
			}
			
			
			//echo "$sellerUserId  $productId  $stockNum  \n"; 
			$productidStockMap[$productId]=$stockNum;
			if ($stockNum<$minStockNum){				
				continue;
			}
			
			$productInfos[$sellerUserId][]=$productId;
			$candidateProdIdList[]=$productId;
		}
	
		
	//	print_r($productInfos);
//		return;
		
		//2. n2是从在线listing表中找出最近刊登的n2个商品。这里只获取productid
		// 获取最近10天的刊登的active的listing。		
		$createLimitedDays=60;
		$tempTimestamp=time()+$activeLastingDays*3600*24;
		$tempTimestamp2=time()-$createLimitedDays*3600*24;
		$rows=\Yii::$app->subdb->createCommand("select selleruserid,productid from aliexpress_listing where ws_offline_date>".$tempTimestamp." and gmt_create>".$tempTimestamp2." order by gmt_create desc ")->queryAll();
		
		foreach($rows as $row){
			$sellerUserId=$row["selleruserid"];
			$productId=$row["productid"];
				
			if (!isset($productInfos[$sellerUserId])) $productInfos[$sellerUserId]=array();
			$prodidList=$productInfos[$sellerUserId];
			
			
			if  (count($prodidList)>$maxCandidateProdNum or in_array($productId,$candidateProdIdList)) {
				continue; //产品够多了 或者 该productid已经存在
			}
			
			//判断是否有库存,无库存的话，就不能作为推荐商品
			if (isset($productidStockMap[$productId])) $stockNum=$productidStockMap[$productId];
			else {
				//$stockNum=ListingAliexpressApiHelper::getSkuStock($sellerUserId, $productId);
				
				list($ret,$stockNum)=self::_getSkuStockForAliexpressProd($sellerUserId, $productId);
				if ($ret===false){
					//速卖通接口有问题
					return;
				}
				$productidStockMap[$productId]=$stockNum;
			}			
			if ($stockNum<$minStockNum){
				continue;
			}
			
			$productInfos[$sellerUserId][]=$productId;
			
		}
		
		
	
		//3.  结合订单的item信息和在线商品表的信息。   补充刚才根据销售获取的productid需要的数据，如 价格，图片，名称。
		$skuObjectMap=array();
		//产品url的前缀----		http://www.aliexpress.com/item/a/234234.html
		//$aliUrlPrefix="http://www.aliexpress.com/wholesale?SearchText=";
		$aliUrlPrefix="http://www.aliexpress.com/item/a/";
		
	
		$resultProductInfos=array();
		$gotProductIdList=array(); //处理过的productid list
		foreach($productInfos as $selleruserid=>$productidList){
			$resultProductInfos[$selleruserid]=array();
			$resultProductInfos[$selleruserid]["global"]=array();
			foreach($productidList as $productId){
				//如果该productid已经处理过，就不再处理
				if (isset($gotProductIdList[$productId])) continue;

				//在线listing获取不了的话，就不能作为推荐商品
				if (!isset($productidListingMap[$productId])) continue;
				
				//在线商品信息
				$listingRow=$productidListingMap[$productId];
				//订单item信息
				$orderItemRow=AliexpressChildorderlist::find()
				->select(["productid","productimgurl","productname","productprice_amount","productprice_currencycode"])
				->where(["productid"=>$productId])
				->orderBy(["id"=>SORT_DESC])
				->asArray()->one();
				if ($orderItemRow===null) {
					$gotProductIdList[$productId]=-1;
					continue;
				}
				
				$recommendProdRow=array();
				$recommendProdRow["productUrl"]=$aliUrlPrefix.$productId.".html";
				$recommendProdRow["productName"]=$orderItemRow["productname"];
				$recommendProdRow["productMainImgUrl"]=$listingRow["photo_primary"];
				$recommendProdRow["currency"]=$orderItemRow["productprice_currencycode"];
				
				$salePrice=$orderItemRow["productprice_amount"];
				if ($recommendProdRow["currency"]==="USD"){
					//如果订单item的商品价格和listing（半天自动更新一次）的商品价格信息（最低价和最高价）有出入，需要做调整。
					if ($salePrice<$listingRow["product_min_price"])  $salePrice=$listingRow["product_min_price"];
					if ($salePrice>$listingRow["product_max_price"])  $salePrice=$listingRow["product_max_price"];
				}	
				$recommendProdRow["price"]=$salePrice;
				
				$recommendProdRow["listingId"]=$productId;
				
				//price
				$recommendProdRow["product_min_price"]=$listingRow["product_min_price"];
				$recommendProdRow["product_max_price"]=$listingRow["product_max_price"];
				
				
	
				$gotProductIdList[$productId]=$recommendProdRow;
				
				//$resultProductInfos[$selleruserid][]=$orderItemRow;
				$resultProductInfos[$selleruserid]["global"][]=$recommendProdRow;
			}
		}
		
		
		
			
	//	print_r($resultProductInfos);
		//3. $resultProductInfos  推送到候选推荐商品数据表
		self::_importRecommendProducts($resultProductInfos, "aliexpress");
	
	}
	
	
	
	/**
	 * 是从订单表找出最近最热卖n1个商品的基础信息。
	 * @param unknown $platform ---平台
	 */
	public static function getRecommProdsBaseInfoFromOrder($platform){
		//1.1 只分析30天之内的订单情况。为每个ebay账号下的每个销售站点，从订单表中找出最热卖的30件商品.然后找到商品对应的listing信息（商品url，价格，货币，图片等等信息）作为候选推荐商品
		$orderMinNum=1200;
		$platform="ebay";
		$days=30;
		$nowTime=time();
		$createTime=$nowTime-$days*24*3600;
		$row=\yii::$app->subdb->createCommand("SELECT count(*) as order_count FROM  `od_order_v2` WHERE order_source='".$platform."' and  order_source_create_time>".$createTime)->queryOne();
		if ($row['order_count']==0) {
			echo "getRecommProdsBaseInfoFromOrder -- $platform no order in latest 30 days \n" ;
			return array(); // 30天内一张订单都没有的话，就放弃该用户
		}
			
		/*	if ($row['order_count']<$orderMinNum){
		 //订单数量太少，放大提取天数
		$days=60;
		$createTime=$nowTime-$days*24*3600;
		//$row=\yii::$app->subdb->createCommand("SELECT count(*) as order_count FROM  `od_order_v2` WHERE order_source_create_time>".$createTime)->queryOne();
		}*/
			
		/*		$rows=\yii::$app->subdb->createCommand(
		 "select * from ".
		
		
				" (SELECT od.selleruserid as selleruserid,od.order_source_site_id as order_source_site_id,odi.sku as sku,".
						" sum(odi.ordered_quantity) as t_quantity  FROM ".
						" (SELECT * FROM `od_order_v2` where order_source_create_time>".$createTime." and order_source='ebay' LIMIT 0 ,5000) od ".
						" INNER JOIN od_order_item_v2 odi ON od.order_id = odi.order_id ".
						" group by  od.selleruserid,od.order_source_site_id,odi.sku ) aa order by t_quantity DESC limit 0,400")->queryAll();
		
		*/
		$rows=\yii::$app->subdb->createCommand(
				"select * from ".
				" (SELECT od.selleruserid as selleruserid,od.order_source_site_id as order_source_site_id,odi.order_source_itemid as listing_id,".
				" sum(odi.ordered_quantity) as t_quantity  FROM ".
				" (SELECT * FROM `od_order_v2` where order_source_create_time>".$createTime." and order_source='".$platform."' LIMIT 0 ,5000) od ".
				" INNER JOIN od_order_item_v2 odi ON od.order_id = odi.order_id and odi.order_source_itemid is not null ".
				" group by  od.selleruserid,od.order_source_site_id,odi.order_source_itemid ) aa order by t_quantity DESC limit 0,400")->queryAll();
		
		
		if ($rows<>null) $countryNameCodeMap=SysBaseInfoHelper::getCountryNameCodeMap();
		$maxCandidateProdNum=30; //某个账号下的 某个国家site，候选的产品的数量最大值
		$productInfos=array();
		foreach($rows as $row){
			if (!isset($productInfos[$row["selleruserid"]])) $productInfos[$row["selleruserid"]]=array();
		
			$selleruseridInfo=$productInfos[$row["selleruserid"]];
			
			// ebay的listing的site的值比较奇怪，有的是国家缩写，有的是英文全名
			$siteCode=$row["order_source_site_id"];
			if (strlen($siteCode)>2 and isset($countryNameCodeMap[$siteCode]))  $siteCode=$countryNameCodeMap[$siteCode];
				
			
			if (!isset($selleruseridInfo[$siteCode])) $productInfos[$row["selleruserid"]][$siteCode]=array();
		
			$sellersiteProdInfo=$productInfos[$row["selleruserid"]][$siteCode];
		
			if  (count($sellersiteProdInfo)>$maxCandidateProdNum) {
				continue; //产品够多了
			}
		
			$productInfos[$row["selleruserid"]][$siteCode][]=$row["listing_id"];
		
		}
		
		return $productInfos;
		
	}
	
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 针对ebay渠道,新建或更新tracker的推荐商品集合
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param  $tracking_id			string	 	物流号 ID
	 *
	 +---------------------------------------------------------------------------------------------
	 * @return string
	 *							Tag Html
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		xjq		2015/07/02				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	private static function _generateTrackerRecommendProductsForEbay(){
		echo "Entering _generateTrackerRecommendProductsForEbay \n";		
		//这里需要为每个ebay账号下的每个销售站点，从找出n件候选商品， 这里n=n1+n2
		//1） n1是从订单表找出最近最热卖n1个商品。
		// ebay一般listing的有效期是比较短的， 所以不应该拉太旧的订单作为分析。
		// 光顾过账号A的英国站的买家，只可以看到账号A英国站的候选商品吧
		//2） n2是从在线listing表中找出最近刊登的n2个商品
		
		//TODO--!!!!注意   ebay的订单和listing的数据表的销售国家字段，取值非常奇怪，  有的是US，有的Great Britain，有的是eBayMotors
		
	
		$platform="ebay";
		$resultProductInfos=array();
		//1. n1是从订单表找出最近最热卖n1个商品。
		//1.1 只分析30天之内的订单情况。为每个ebay账号下的每个销售站点，从订单表中找出最热卖的30件商品.这里只是把商品找出来信息量是不够，需要在1.2找到商品对应的listing信息（商品url，价格，货币，图片等等信息）作为候选推荐商品
		$productInfos=self::getRecommProdsBaseInfoFromOrder($platform);		
		
		//1.2 通过已经拉取下来的在线listing的信息来补全候选推荐商品的信息。商品对应的listing信息（商品url，价格，货币，图片等等信息）.
		$listingObjectMap=array();		
		foreach($productInfos as $selleruserid=>$selleruseridInfo){
			$resultProductInfos[$selleruserid]=array();
			foreach ($selleruseridInfo as $siteCode=>$listIdList){
				$aliProdList=array();
				foreach($listIdList as $listId){
											
					// 根据listing_id从ebay_item中获取对应的在售listing(listingstatus 为Active)。非在售listing需要过滤。
					$ebayItem=EbayItem::find()
					->select(["mainimg","itemtitle","currentprice","currency","viewitemurl","listingstatus"])
					->where(["itemid"=>$listId])->asArray()->one();
					// ,"listingstatus"=>"Active"
					
					if ($ebayItem==null) {
						//这里找不到。 也许是listing还没有拉取下来
						continue;
					}
					//不是active的话，也不做推荐产品
					if ($ebayItem["listingstatus"]<>"Active") continue;
					
					$row=array();
					$row["productUrl"]=$ebayItem["viewitemurl"];
					$row["productName"]=$ebayItem["itemtitle"];
					$row["productMainImgUrl"]=$ebayItem["mainimg"];
					$row["price"]=$ebayItem["currentprice"];
					$row["currency"]=$ebayItem["currency"];
					$row["listingId"]=$listId;
					
					$resultProductInfos[$selleruserid][$siteCode][]=$row;
				}
					
			}
		}
		
		//2. n2是从在线listing表中找出最近刊登的n2个商品
		// 获取最近10天的刊登的active的listing。 根据刊登开始时间starttime来判断
		
		$maxNumFromListing=10;// 对于1个账号下1个销售国家最多只提取10个候选商品
		$endTime=time();
		$beginTime=$endTime-10*24*3600; 
		$ebayItemsCol=EbayItem::find()
		->select(["selleruserid","site","itemid","mainimg","itemtitle","currentprice","currency","viewitemurl"])
		->where("starttime<$endTime and starttime>$beginTime and listingstatus='Active'")->asArray()->all();
		
		if ($ebayItemsCol<>null) $countryNameCodeMap=SysBaseInfoHelper::getCountryNameCodeMap(); 
			 
		$retFromList=array();
		foreach($ebayItemsCol as $ebayItem){
			$selleruserid=$ebayItem["selleruserid"];
			if (!isset($retFromList[$selleruserid])) $retFromList[$selleruserid]=array();
			
			// ebay的listing的site的值比较奇怪，有的是国家缩写，有的是英文全名
			if (strlen($ebayItem["site"])==2){  
				$siteCode=$ebayItem["site"];				
			}else if ($ebayItem["site"]=="eBayMotors") {
				$siteCode="eBayMotors";
			}	
			else if (!isset($countryNameCodeMap[$ebayItem["site"]])) {
				echo "site:".$ebayItem["site"]." no map  \n";
				continue;		
			} else{
			    $siteCode=$countryNameCodeMap[$ebayItem["site"]];
			}
			
			if (!isset($retFromList[$selleruserid][$siteCode])) $retFromList[$selleruserid][$siteCode]=array();			
			
			if (count($retFromList[$selleruserid][$siteCode])>$maxNumFromListing) continue;
			
			$row=array();
			$row["productUrl"]=$ebayItem["viewitemurl"];
			$row["productName"]=$ebayItem["itemtitle"];
			$row["productMainImgUrl"]=$ebayItem["mainimg"];
			$row["price"]=$ebayItem["currentprice"];
			$row["currency"]=$ebayItem["currency"];
			$row["listingId"]=$ebayItem["itemid"];
				
			$retFromList[$selleruserid][$siteCode][]=$row;
		}
		
		//3. 合并订单销售情况的候选商品（$resultProductInfos）和listing的候选商品（$retFromList）
		if(count($retFromList)>0){
			foreach($retFromList as $selleruserid=>$selleruseridInfo){
				if (!isset($resultProductInfos[$selleruserid])){
					$resultProductInfos[$selleruserid]=$selleruseridInfo;
					continue;
				}
				foreach ($selleruseridInfo as $siteCode=>$prodsInfoList){
					if (!isset($resultProductInfos[$selleruserid][$siteCode])){
						$resultProductInfos[$selleruserid][$siteCode]=$prodsInfoList;
						continue;						
					}
					//获取在订单的候选商品存在的listing					
					$gotListingIds=$productInfos[$selleruserid][$siteCode];
					foreach($prodsInfoList as $prodInfo){
						if (in_array($prodInfo["listingId"],$gotListingIds)) continue;
						$resultProductInfos[$selleruserid][$siteCode][]=$prodInfo;
					}
				}
			}
		}
		
		//print_r($resultProductInfos);				
			
		//4. $resultProductInfos  推送到候选推荐商品数据表
		self::_importRecommendProducts($resultProductInfos, "ebay");
		
	}	
	
	

	/**
	 +---------------------------------------------------------------------------------------------
	 * 根据array信息导入到 tracker_recommend_prodcut数据表
	 *   速卖通----由于速卖通目前没有获取当前listing，所以没有办法判断候选的推荐商品是否已经下架。
	 *   ebay---进来的推荐商品都是active的listing  
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param  $recommendProductInfos		候选推荐商品的数组形式
	 * $recommendProductInfos=
	 *      array(
	 *                array("productUrl","productName","productMainImgUrl","price","currency","listingId"),
	 *               )
	 * 
	 * @param  $platform		订单渠道 --- ebay，wish，amazon
	 *
	 +---------------------------------------------------------------------------------------------
	 * @return string
	 *							Tag Html
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		xjq		2015/07/02				初始化
	 +---------------------------------------------------------------------------------------------
	 **/	
	private static function _importRecommendProducts($recommendProductInfos,$platform){
		//推荐信息更新到 tracker_recommend_product 数据表		
		foreach($recommendProductInfos as $selleruserid=>$siteProductInfos ){
			foreach($siteProductInfos as $siteCode=>$productInfos){
				
					
								
				//设置目前该账户，该站点下所有推荐商品为非active
				$row=\yii::$app->subdb->createCommand("update cs_recommend_product set is_active='N' ".
						" where platform='".$platform."' and platform_account_id='".$selleruserid."'".
						" and platform_site_id='".$siteCode."'")->execute();
								
				
				foreach($productInfos as $productInfo){
					$nowTime=date('Y-m-d H:i:s');
					//$productInfo["productid"];
					
					$recommendProd=CsRecommendProduct::find()->where(["platform_account_id"=>$selleruserid,
							"listing_id"=>$productInfo["listingId"],"platform"=>$platform ])->one();
					if ($recommendProd===null){
						$recommendProd=new CsRecommendProduct;
						$recommendProd->create_time=$nowTime;
						$recommendProd->platform=$platform;
						$recommendProd->platform_account_id=$selleruserid;
						$recommendProd->platform_site_id=$siteCode;
						$recommendProd->listing_id=$productInfo["listingId"];
					}
					//目前 is_on_sale没有用				
					
					$recommendProd->update_time=$nowTime;
					$recommendProd->is_active='Y';
					$recommendProd->product_name=$productInfo["productName"];
					$recommendProd->product_image_url=$productInfo["productMainImgUrl"];
					$recommendProd->product_price=$productInfo["price"];
					
					if (isset($productInfo['product_min_price']) and $productInfo['product_min_price']>0){
						$recommendProd->product_min_price=$productInfo['product_min_price'];
						$recommendProd->product_max_price=$productInfo['product_max_price'];
					}else {
						$recommendProd->product_min_price=$productInfo['price'];
						$recommendProd->product_max_price=$productInfo['price'];												
					}
					
					$recommendProd->product_price_currency=$productInfo["currency"];
					$recommendProd->product_url=$productInfo["productUrl"];
					
					if (!empty($recommendProd->product_price_currency)){
						if (!$recommendProd->save(false)){
							//print_r($recommendProd->errors);
						}
					}//end of product currency is not empty
				}				
			}
			
		}
		
	
	}

	
	/**
	 * 获取指定平台，平台账号，平台站点（英国，美国。。。）推荐商品----外部调用
	 * 
	 * 3个接口外部会调用，配合方式
	 * $ret=isReadyRecommendProd  
		if ($ret==true){
		    getRecomProductsFor  
		}else {
		generateTempRecommendProducts   
		   getRecomProductsFor  
		
		}
	 * 
	 * @param string $platform --- ebay,aliexpress等等
	 * @param string $seller_id --- 平台账号id
	 * @param string $platform_site_id --- 平台销售站点 ebay和amazon是有这个概念的。  US、UK等等
	 * @param number $product_count --- 需要的商品数量
	 *
	 * @return  没有结果时候，返回array()
	 * 有结果的时候返回
	 * Return array(
    ‘listing_id1’=>array(
         'id'=>34,
		‘listing_id’=>’listing_id1’,
		’product_url’=>’http://ebay.com/sku1’,
		‘product_image’=>’http://xxxx.jpg’,
		‘sale_price’=>’19.6’,
		‘sale_currency’=>’EUR’,
		'product_min_price'=>'11.2',
		'product_max_price'=>'21.2', //保证product_min_price和product_min_price 不为0，但是可能是相等的
		’product_name’=>’Very good iphone 5 case kitty’
    ), 
    ‘listing_id2’=>array(
         'id'=>35,
	    ‘listing_id’=>’listing_id2’,
		’product_url’=>’http://ebay.com/sku2’,
		‘product_image’=>’http://xxxx.jpg’,
		‘sale_price’=>’19.69’,
		'product_min_price'=>'11.2',
		'product_max_price'=>'21.2',		
		‘sale_currency’=>’EUR’,
		’product_name’=>’Very good iphone 6 case kitty’
     ),           
}
	 * 
	 */
	public static function getRecomProductsFor($platform='',$seller_id='',$platform_site_id='',$product_count=10){
	    \Yii::info("getRecomProductsFor platform:$platform,seller_id:$seller_id,platform_site_id:$platform_site_id,product_count:$product_count","file");
		
		if ($platform=='' or $seller_id=='') return array();
	     
	     if ($platform=="aliexpress") $platform_site_id="global";
	     
	     //1. 从推荐商品表中提取推荐商品
	     $retRecommProdsArr=self::_getRecomProductsFromDb($platform, $seller_id, $platform_site_id, $product_count);
	     
	     if ($platform_site_id=="eBayMotors" and count($retRecommProdsArr)<3){
	     	$retRecommProdsArr2=self::_getRecomProductsFromDb($platform, $seller_id, "US", $product_count);
	     	if (count($retRecommProdsArr2)>3)  return $retRecommProdsArr2;
	     	
	     }
	     //2. 如果是速卖通渠道, 推荐商品没有ready的话，需要使用临时方案
	 //    if ($platform=="aliexpress" and count($retRecommProdsArr)==0){
	     	//$retRecommProdsArr=self::getTempRecomProductsForAliexpress($seller_id);
	   //  	self::generateTempRecommendProducts($platform,$seller_id,$platform_site_id);
	     	//3. 从推荐商品表中提取推荐商品
	    // 	$retRecommProdsArr=self::_getRecomProductsFromDb($platform, $seller_id, $platform_site_id, $product_count);	     	 
	    // }
	     return $retRecommProdsArr;
	}
	
	/**
	 * 直接从推荐商品数据库表中读取信息
	 * @param unknown $platform
	 * @param unknown $seller_id
	 * @param unknown $platform_site_id
	 * @param number $product_count
	 * @return multitype:multitype:NULL
	 */
	private static function _getRecomProductsFromDb($platform,$seller_id,$platform_site_id,$product_count=10){
		$retRecommProdsArr=array();
		
		$query=CsRecommendProduct::find()->where(["platform"=>$platform,"platform_account_id"=>$seller_id,"is_active"=>"Y"]);
		if($platform_site_id!=='')
			$query->andWhere(['platform_site_id'=>$platform_site_id]);
		
		$csRecommProdsCol=$query->limit($product_count)->all();
		
		foreach($csRecommProdsCol as $csRecommProd){
			//$retRecommProdsArr
			$tempRecommObject=array();
			$tempRecommObject["id"]=$csRecommProd->id;
			$tempRecommObject["listing_id"]=$csRecommProd->listing_id;
			$tempRecommObject["product_url"]=$csRecommProd->product_url;
			$tempRecommObject["product_image"]=$csRecommProd->product_image_url;
			$tempRecommObject["sale_price"]=$csRecommProd->product_price;
			$tempRecommObject["sale_currency"]=$csRecommProd->product_price_currency;
			$tempRecommObject["product_name"]=$csRecommProd->product_name;

			//保证product_min_price和product_min_price 不为0，但是可能是相等的
			if ($csRecommProd->product_min_price==0 or $csRecommProd->product_max_price==0){
				$tempRecommObject["product_min_price"]=$csRecommProd->product_price;
				$tempRecommObject["product_max_price"]=$csRecommProd->product_price;				
			}  else{
				$tempRecommObject["product_min_price"]=$csRecommProd->product_min_price;
				$tempRecommObject["product_max_price"]=$csRecommProd->product_max_price;
			} 
			$retRecommProdsArr[$csRecommProd->listing_id]=$tempRecommObject;
			 
		}
		return $retRecommProdsArr;		
	}
	

	/**
	 * 当推荐商品没有ready的时候，需要临时方案来生成推荐商品。---外部调用
	 * 速卖通的临时方案是
	 * @param unknown $platform
	 * @param unknown $selleruserid
	 * @param string $platform_site_id
	 * @return true or false
	 */	
	public static function generateTempRecommendProducts($platform,$selleruserid,$platform_site_id=''){
		
		if ($platform<>"aliexpress") return true;
	
		$TEMP_RECOMMD_NUM=8;//临时推荐商品个数
		$number=100;
		
		//1.马上请求速卖通获取在线listing
		try{
		  $resultArr=ListingAliexpressApiHelper::getListingByNum($selleruserid, $number);
		}catch (\Exception $e){
			$errorMessage="generateTempRecommendProducts  Exception error:".$e->getMessage()." trace:".$e->getTraceAsString();
			\Yii::error($errorMessage,"file");
			return false;
		}
		$retRecommProdsArr=array();
		if (count($resultArr)==0) return $retRecommProdsArr;
		
		$retRecommProdsArr[$selleruserid]=array("global"=>array());

	//产品url的前缀----		http://www.aliexpress.com/item/a/234234.html
		//$aliUrlPrefix="http://www.aliexpress.com/wholesale?SearchText=";
		$aliUrlPrefix="http://www.aliexpress.com/item/a/";
			
		$chosenNum=0;
		foreach($resultArr as $listingInfo){
			//$retRecommProdsArr
			$tempRecommObject=array();
			$tempRecommObject["listingId"]=$listingInfo["productid"];
			$tempRecommObject["productName"]=$listingInfo["subject"];
			$tempRecommObject["productUrl"]=$aliUrlPrefix.$listingInfo["productid"].".html";
			$tempRecommObject["productMainImgUrl"]=$listingInfo["photo_primary"];
			$tempRecommObject["price"]=$listingInfo["product_min_price"];
			$tempRecommObject["currency"]="USD";
				
			$retRecommProdsArr[$selleruserid]["global"][]=$tempRecommObject;
			$chosenNum++;
			if ($chosenNum>$TEMP_RECOMMD_NUM) break;
		
		}
		
		//2. 保存临时推荐商品到推荐商品数据表中
		self::_importRecommendProducts($retRecommProdsArr, "aliexpress");
		
		return true;
	}
	
	
	
	/**
	 * 检查推荐商品是否ready----外部调用
	 * 针对速卖通----如果推荐商品没有ready的话，马上出发临时推荐商品方案 ，选择一部分listing的商品插入到推荐商品数据表中。同步的方式
	 * 针对ebay---- 直接返回true 
	 * 	
	 * @param string $platform --- ebay,aliexpress等等
	 * @param string $seller_id --- 平台账号id
	 * @param string $platform_site_id --- 平台销售站点 ebay和amazon是有这个概念的。  US、UK等等
	 * 
	 * @return true or false
	 * true ---表示已经ready
	 */	
	public static function isReadyRecommendProd($platform='',$seller_id='',$platform_site_id=''){
		
		\Yii::info("isReadyRecommendProd platform:$platform, seller_id:$seller_id,platform_site_id:$platform_site_id","file");
		
		if ($platform=='' or $seller_id=='') return false;
	
		if ($platform<>"aliexpress") return true;
		
		$platform_site_id="global";	
		//1. 从推荐商品表中提取推荐商品
		$retRecommProdsArr=self::_getRecomProductsFromDb($platform, $seller_id, $platform_site_id);			
		
		//2. 如果是速卖通渠道, 推荐商品没有ready的话，需要使用临时方案,选择一部分listing的商品插入到推荐商品数据表中
		if (count($retRecommProdsArr)==0){			
			//self::getTempRecomProductsForAliexpress($seller_id);
			\Yii::info("isReadyRecommendProd return false platform:$platform, seller_id:$seller_id,platform_site_id:$platform_site_id","file");
			return false;
		}
		\Yii::info("isReadyRecommendProd return true platform:$platform, seller_id:$seller_id,platform_site_id:$platform_site_id","file");
		return true;
	}
	
	/**
	 * 指定puid来生成推荐生成，目前作为测试用途
	 * 
	 */
	public static function generateTrackerRecommendProductsByPuidForTest($puid){
	
		echo "++++++++++++generateTrackerRecommendProductsByPuidForTest \n";
		//1. 检查当前本job版本，如果和自己的版本不一样，就自动exit吧
		$ret=self::checkNeedExitOrNot();
		if ($ret===true) exit;
	
		$backgroundJobId=self::getCronJobId(); //获取进程id号，主要是为了打印log
		$connection=\Yii::$app->db;
		$hasGotRecord=false;//是否抢到账号
	
		//echo "=========begin SAA_obj merchantId:".$SAA_obj->merchant_id.",marketplaceId:".$SAA_obj->marketplace_id." \n";
	
		 
	
		$nowTime=time();
	
	
		$timeMS1=TimeUtil::getCurrentTimestampMS();
	
			//1. ebay的候选推荐商品
		self::_generateTrackerRecommendProductsForEbay();
			$timeMS2=TimeUtil::getCurrentTimestampMS();
			//2. aliexpress的候选推荐商品
		self::_generateTrackerRecommendProductsForAliexpress();
		$timeMS3=TimeUtil::getCurrentTimestampMS();
	
	
//			\Yii::info("generate_tracker_recommend_products_$type puid=$puid,afid_jobid=$backgroundJobId,t2_1=".($timeMS2-$timeMS1).
//			",t3_2=".($timeMS3-$timeMS2).",t3_1=".($timeMS3-$timeMS1),"file");
	
	
		}
	
	
	
	
	
	
	
}//end of class
?>
